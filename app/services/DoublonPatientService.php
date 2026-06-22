<?php
/**
 * SimCare+ — Dossier Médical Électronique (DME)
 * Copyright (c) 2024-2026 Franck Simeni. Tous droits réservés.
 * Développé pour la gestion hospitalière, et le bien être numérique des patients.
 *
 * Toute reproduction, modification ou distribution de ce logiciel,
 * en tout ou en partie, sans autorisation écrite préalable de l'auteur
 * est strictement interdite et constitue une contrefaçon.
 *
 * Protected under OAPI Agreement — Annexe VII · Berne Convention
 */

/* ============================================================================
 * DoublonPatientService.php
 * Service centralisé de détection intelligente des doublons patients.
 *
 * Niveaux de correspondance (score) :
 *   100 — Nom + Prénom exacts + Date de naissance exacte   → TRÈS PROBABLE
 *    90 — Nom + Prénom exacts + Téléphone identique         → TRÈS PROBABLE
 *    80 — Nom + Prénom exacts (sans autre critère)          → PROBABLE
 *    70 — Nom exact + Prénom approché (LIKE) + Tél/DDN      → POSSIBLE
 *    60 — Téléphone identique seul                          → À VÉRIFIER
 *
 * Usage :
 *   $svc = new DoublonPatientService($pdo);
 *   $res = $svc->verifier('DUPONT', 'Jean', '1985-03-12', '677123456');
 *   // → ['doublons' => [...], 'score_max' => 100, 'has_critical' => true]
 * ============================================================================ */

class DoublonPatientService
{
    private PDO $db;

    // Seuil au-delà duquel on bloque (TRÈS PROBABLE = doublon quasi-certain)
    const SCORE_BLOQUANT  = 90;
    // Seuil pour afficher un avertissement
    const SCORE_AVERTISSEMENT = 60;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Vérifie les doublons potentiels.
     *
     * @param string      $nom          Nom (sera normalisé UPPER/TRIM)
     * @param string      $prenom       Prénom (sera normalisé UPPER/TRIM)
     * @param string|null $date_naissance  Format Y-m-d ou null
     * @param string|null $telephone    Numéro ou null
     * @param int|null    $exclude_id   ID patient à exclure (édition)
     *
     * @return array {
     *   doublons: [{id, dossier_numero, nom, prenom, date_naissance, telephone,
     *               statut_parcours, nb_consultations, score, niveau}],
     *   score_max: int,
     *   has_critical: bool,
     *   has_warning: bool
     * }
     */
    public function verifier(
        string  $nom,
        string  $prenom,
        ?string $date_naissance = null,
        ?string $telephone      = null,
        ?int    $exclude_id     = null
    ): array {

        $nom    = strtoupper(trim($nom));
        $prenom = strtoupper(trim($prenom));
        if (strlen($nom) < 2) {
            return $this->vide();
        }

        $doublons = [];

        // ── Stratégie 1 : Nom + Prénom exacts + DDN exacte ─────────────────
        if ($date_naissance) {
            $rows = $this->requete(
                "UPPER(TRIM(p.nom)) = ? AND UPPER(TRIM(p.prenom)) = ? AND p.date_naissance = ?",
                [$nom, $prenom, $date_naissance],
                $exclude_id
            );
            foreach ($rows as $r) {
                $doublons[$r['id']] = array_merge($r, ['score' => 100, 'niveau' => 'CRITIQUE']);
            }
        }

        // ── Stratégie 2 : Nom + Prénom exacts + Téléphone ──────────────────
        if ($telephone && strlen($telephone) >= 6) {
            $rows = $this->requete(
                "UPPER(TRIM(p.nom)) = ? AND UPPER(TRIM(p.prenom)) = ? AND p.telephone LIKE ?",
                [$nom, $prenom, '%' . $telephone . '%'],
                $exclude_id
            );
            foreach ($rows as $r) {
                if (!isset($doublons[$r['id']])) {
                    $doublons[$r['id']] = array_merge($r, ['score' => 90, 'niveau' => 'CRITIQUE']);
                }
            }
        }

        // ── Stratégie 3 : Nom + Prénom exacts (sans autre critère) ─────────
        $rows = $this->requete(
            "UPPER(TRIM(p.nom)) = ? AND UPPER(TRIM(p.prenom)) = ?",
            [$nom, $prenom],
            $exclude_id
        );
        foreach ($rows as $r) {
            if (!isset($doublons[$r['id']])) {
                $doublons[$r['id']] = array_merge($r, ['score' => 80, 'niveau' => 'ATTENTION']);
            }
        }

        // ── Stratégie 4 : Nom exact + Prénom approché (LIKE) + Tél/DDN ─────
        // IMPORTANT : on exige un critère supplémentaire (DDN OU téléphone) pour
        // éviter les faux positifs sur les simples homonymes (même nom de famille).
        if (strlen($prenom) >= 3 && ($date_naissance || ($telephone && strlen($telephone) >= 6))) {
            $prenomLike = substr($prenom, 0, 3) . '%';
            $extraWhere = [];
            $extraParams = [$nom, $prenomLike];
            if ($date_naissance) {
                $extraWhere[]  = "p.date_naissance = ?";
                $extraParams[] = $date_naissance;
            }
            if ($telephone && strlen($telephone) >= 6) {
                $extraWhere[]  = "p.telephone LIKE ?";
                $extraParams[] = '%' . $telephone . '%';
            }
            $rows = $this->requete(
                "UPPER(TRIM(p.nom)) = ? AND UPPER(TRIM(p.prenom)) LIKE ? AND (" . implode(' OR ', $extraWhere) . ")",
                $extraParams,
                $exclude_id
            );
            foreach ($rows as $r) {
                if (!isset($doublons[$r['id']])) {
                    $doublons[$r['id']] = array_merge($r, ['score' => 70, 'niveau' => 'ATTENTION']);
                }
            }
        }

        // ── Stratégie 5 : Téléphone identique seul ─────────────────────────
        if ($telephone && strlen($telephone) >= 8) {
            $rows = $this->requete(
                "p.telephone = ? AND p.telephone != ''",
                [$telephone],
                $exclude_id
            );
            foreach ($rows as $r) {
                if (!isset($doublons[$r['id']])) {
                    $doublons[$r['id']] = array_merge($r, ['score' => 60, 'niveau' => 'POSSIBLE']);
                }
            }
        }

        // Trier par score décroissant
        usort($doublons, fn($a, $b) => $b['score'] - $a['score']);
        $doublons = array_slice(array_values($doublons), 0, 8);

        $scoreMax = !empty($doublons) ? $doublons[0]['score'] : 0;

        return [
            'doublons'     => $doublons,
            'score_max'    => $scoreMax,
            'has_critical' => $scoreMax >= self::SCORE_BLOQUANT,
            'has_warning'  => $scoreMax >= self::SCORE_AVERTISSEMENT,
        ];
    }

    /** Exécute une requête de recherche doublons et retourne les lignes */
    private function requete(string $where, array $params, ?int $excludeId): array
    {
        $excludeClause = $excludeId ? " AND p.id != $excludeId" : '';
        $sql = "
            SELECT p.id, p.dossier_numero, p.nom, p.prenom,
                   p.date_naissance, p.telephone, p.sexe,
                   p.statut_parcours, p.type_client, p.created_at,
                   (SELECT COUNT(*) FROM consultations WHERE patient_id = p.id) AS nb_consultations
            FROM patients p
            WHERE p.actif = 1
              AND ($where)
              $excludeClause
            ORDER BY p.created_at DESC
            LIMIT 5
        ";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log('[DoublonPatientService] ' . $e->getMessage());
            return [];
        }
    }

    private function vide(): array
    {
        return ['doublons' => [], 'score_max' => 0, 'has_critical' => false, 'has_warning' => false];
    }

    /**
     * Formate les doublons pour l'affichage JSON (API)
     */
    public static function formaterPourApi(array $result): array
    {
        $labels = ['CRITIQUE' => 'Doublon probable', 'ATTENTION' => 'Possible doublon', 'POSSIBLE' => 'À vérifier'];
        $colors = ['CRITIQUE' => '#dc2626', 'ATTENTION' => '#d97706', 'POSSIBLE' => '#0891b2'];

        foreach ($result['doublons'] as &$d) {
            $d['niveau_label'] = $labels[$d['niveau']] ?? $d['niveau'];
            $d['niveau_color'] = $colors[$d['niveau']] ?? '#94a3b8';
            $d['age']          = !empty($d['date_naissance']) && $d['date_naissance'] !== '1900-01-01'
                ? date_diff(date_create($d['date_naissance']), date_create('today'))->y . ' ans'
                : '—';
        }
        return $result;
    }
}
