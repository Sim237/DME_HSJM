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

require_once __DIR__ . '/../../config/database.php';

class Consultation {
    private $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->ensureAtcdColumns();
    }

    /**
     * Auto-migration : garantit les colonnes antécédents toxicologiques
     * et médicamenteux (une seule vérification par session).
     */
    private function ensureAtcdColumns(): void {
        if (!empty($_SESSION['_migration_atcd_v2_ok'])) return;
        try {
            $cols = $this->db->query(
                "SELECT COLUMN_NAME FROM information_schema.COLUMNS
                  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'consultations'
                    AND COLUMN_NAME IN ('atcd_toxicologiques','atcd_medicamenteux','commentaires_parametres')"
            )->fetchAll(PDO::FETCH_COLUMN);

            if (!in_array('atcd_toxicologiques', $cols, true)) {
                $this->db->exec("ALTER TABLE consultations ADD COLUMN atcd_toxicologiques TEXT NULL AFTER atcd_allergies");
            }
            if (!in_array('atcd_medicamenteux', $cols, true)) {
                $this->db->exec("ALTER TABLE consultations ADD COLUMN atcd_medicamenteux TEXT NULL AFTER atcd_toxicologiques");
            }
            if (!in_array('commentaires_parametres', $cols, true)) {
                $this->db->exec("ALTER TABLE consultations ADD COLUMN commentaires_parametres TEXT NULL AFTER glycemie_capillaire");
            }
        } catch (\Throwable $e) {
            error_log('[Consultation::ensureAtcdColumns] ' . $e->getMessage());
        }
        $_SESSION['_migration_atcd_v2_ok'] = true;
    }

    /**
     * Cache statique des colonnes existantes dans la table consultations
     * (évite de relire SHOW COLUMNS à chaque save).
     */
    private function getConsultColumns(): array {
        static $cache = null;
        if ($cache === null) {
            $cache = $this->db->query("SHOW COLUMNS FROM consultations")->fetchAll(PDO::FETCH_COLUMN);
        }
        return $cache;
    }

    /**
     * Extrait les nouveaux paramètres vitaux du POST en parsant aussi
     * "120/80" pour les TA bras gauche/droit. Retourne uniquement les
     * couples (col, valeur) pour les colonnes qui existent réellement.
     */
    private function buildExtraVitauxFields(array $data): array {
        $cols = $this->getConsultColumns();
        $out  = []; // [col => valeur]

        // Helper : ajouter un couple si la colonne existe en BDD
        $add = function(string $col, $value) use (&$out, $cols) {
            if (in_array($col, $cols, true)) {
                $out[$col] = ($value === '' || $value === null) ? null : $value;
            }
        };

        // Antécédents toxicologiques et médicamenteux
        $add('atcd_toxicologiques', isset($data['atcd_toxicologiques']) ? trim((string)$data['atcd_toxicologiques']) : null);
        $add('atcd_medicamenteux',  isset($data['atcd_medicamenteux'])  ? trim((string)$data['atcd_medicamenteux'])  : null);

        // Fréquence respiratoire
        $add('frequence_respiratoire', isset($data['frequence_respiratoire']) && $data['frequence_respiratoire'] !== ''
            ? (int)$data['frequence_respiratoire'] : null);

        // Pouls
        $add('pouls', isset($data['pouls']) && $data['pouls'] !== '' ? (int)$data['pouls'] : null);

        // Saturation en oxygène (SpO2)
        $add('saturation', isset($data['saturation']) && $data['saturation'] !== ''
            ? (float)$data['saturation'] : null);

        // Glycémie capillaire (g/L)
        $add('glycemie_capillaire', isset($data['glycemie_capillaire']) && $data['glycemie_capillaire'] !== ''
            ? (float)$data['glycemie_capillaire'] : null);

        // TA bras gauche : peut être au format "120/80"
        $taG = trim((string)($data['ta_bras_gauche'] ?? ''));
        $gSys = $data['ta_bras_gauche_systolique']  ?? null;
        $gDia = $data['ta_bras_gauche_diastolique'] ?? null;
        if ($taG !== '') {
            $p = explode('/', $taG);
            if (isset($p[0]) && is_numeric(trim($p[0]))) $gSys = (int)trim($p[0]);
            if (isset($p[1]) && is_numeric(trim($p[1]))) $gDia = (int)trim($p[1]);
        }
        $add('ta_bras_gauche_systolique',  $gSys === '' ? null : $gSys);
        $add('ta_bras_gauche_diastolique', $gDia === '' ? null : $gDia);

        // TA bras droit
        $taD = trim((string)($data['ta_bras_droit'] ?? ''));
        $dSys = $data['ta_bras_droit_systolique']  ?? null;
        $dDia = $data['ta_bras_droit_diastolique'] ?? null;
        if ($taD !== '') {
            $p = explode('/', $taD);
            if (isset($p[0]) && is_numeric(trim($p[0]))) $dSys = (int)trim($p[0]);
            if (isset($p[1]) && is_numeric(trim($p[1]))) $dDia = (int)trim($p[1]);
        }
        $add('ta_bras_droit_systolique',  $dSys === '' ? null : $dSys);
        $add('ta_bras_droit_diastolique', $dDia === '' ? null : $dDia);

        // Commentaires libres sur les paramètres vitaux
        $add('commentaires_parametres', isset($data['commentaires_parametres'])
            ? trim((string)$data['commentaires_parametres']) : null);

        // TA non prenable (checkbox)
        $add('tension_non_prenable', !empty($data['tension_non_prenable']) ? 1 : 0);
        $add('motif_tension_non_prenable',
            !empty($data['tension_non_prenable']) && !empty($data['motif_tension_non_prenable'])
                ? trim($data['motif_tension_non_prenable']) : null);

        // ── Tests de Diagnostic Rapide (TDR) — JSON dans une colonne TEXT ──
        if (in_array('tests_tdr', $cols, true)) {
            $tdrRaw = $data['tests_tdr'] ?? null;
            $tdrValue = null;
            if (!empty($tdrRaw) && is_string($tdrRaw)) {
                $decoded = json_decode($tdrRaw, true);
                if (is_array($decoded) && !empty($decoded)) {
                    $tdrValue = json_encode($decoded, JSON_UNESCAPED_UNICODE);
                }
            }
            $out['tests_tdr'] = $tdrValue;
        }

        // ── Antécédents Gynécologiques — JSON dans une colonne TEXT ──
        if (in_array('atcd_gyneco', $cols, true)) {
            $gynRaw = $data['atcd_gyneco'] ?? null;
            $gynValue = null;
            if (!empty($gynRaw) && is_string($gynRaw)) {
                $decoded = json_decode($gynRaw, true);
                if (is_array($decoded) && !empty($decoded)) {
                    $gynValue = json_encode($decoded, JSON_UNESCAPED_UNICODE);
                }
            }
            $out['atcd_gyneco'] = $gynValue;
        }

        return $out;
    }

    public function create($data) {
        try {
            $data = $this->cleanEmptyStrings($data);

            // Décomposer tension_arterielle "120/80" → systolique / diastolique
            $ta = $data['tension_arterielle'] ?? '';
            $taParts = explode('/', $ta);
            $taSys = isset($taParts[0]) && is_numeric(trim($taParts[0])) ? (int)trim($taParts[0]) : ($data['tension_systolique'] ?? null);
            $taDia = isset($taParts[1]) && is_numeric(trim($taParts[1])) ? (int)trim($taParts[1]) : ($data['tension_diastolique'] ?? null);

            // ── Colonnes additionnelles (ajoutées via migration) ────────
            // Construites dynamiquement pour rester compatibles si la
            // migration n'a pas encore été exécutée.
            $extraVitaux = $this->buildExtraVitauxFields($data);

            $colsBase = [
                'patient_id', 'medecin_id', 'type_consultation', 'date_consultation', 'motif_consultation',
                'histoire_maladie', 'automedication', 'complement_anamnese',
                'atcd_medicaux', 'atcd_chirurgicaux', 'atcd_familiaux', 'atcd_allergies',
                'temperature', 'tension_systolique', 'tension_diastolique', 'frequence_cardiaque', 'poids', 'taille',
                'examen_physique', 'resume_syndromique',
                'hypotheses_diagnostiques', 'diagnostic_principal', 'diagnostics_differentiels',
                'examens_paracliniques', 'plan_traitement', 'traitement_non_medicamenteux',
                'surveillance', 'notes_suivi', 'date_suivi',
                'systeme_principal', 'symptomes_systemiques', 'commentaires_systemiques',
            ];
            $extraCols = array_keys($extraVitaux);

            $allCols = array_merge($colsBase, $extraCols);
            $placeholders = array_map(fn($c) => ':' . $c, $allCols);

            $sql = "INSERT INTO consultations (" . implode(', ', $allCols) . ")
                    VALUES (" . implode(', ', $placeholders) . ")";

            $stmt = $this->db->prepare($sql);

            $params = [
                ':patient_id'                   => $data['patient_id']                  ?? null,
                ':medecin_id'                   => $data['medecin_id']                  ?? 1,
                ':type_consultation'            => strtolower(trim($data['type'] ?? 'externe')),
                ':date_consultation'            => $data['date_consultation']            ?? date('Y-m-d H:i:s'),
                ':motif_consultation'           => $data['motif_consultation']           ?? null,
                ':histoire_maladie'             => $data['histoire_maladie']             ?? null,
                ':automedication'               => $data['automedication']               ?? null,
                ':complement_anamnese'          => $data['complement_anamnese']          ?? null,
                ':atcd_medicaux'                => $data['atcd_medicaux']                ?? null,
                ':atcd_chirurgicaux'            => $data['atcd_chirurgicaux']            ?? null,
                ':atcd_familiaux'               => $data['atcd_familiaux']               ?? null,
                ':atcd_allergies'               => $data['atcd_allergies']               ?? null,
                ':temperature'                  => $data['temperature']                  ?? null,
                ':tension_systolique'           => $taSys,
                ':tension_diastolique'          => $taDia,
                ':frequence_cardiaque'          => $data['frequence_cardiaque']          ?? null,
                ':poids'                        => $data['poids']                        ?? null,
                ':taille'                       => $data['taille']                       ?? null,
                ':examen_physique'              => $data['examen_physique']              ?? null,
                ':resume_syndromique'           => $data['resume_syndromique']           ?? null,
                ':hypotheses_diagnostiques'     => $data['hypotheses_diagnostiques']     ?? null,
                ':diagnostic_principal'         => $data['diagnostic_principal']         ?? null,
                ':diagnostics_differentiels'    => $data['diagnostics_differentiels']    ?? null,
                ':examens_paracliniques'        => $data['examens_paracliniques']        ?? null,
                ':plan_traitement'              => $data['plan_traitement']              ?? null,
                ':traitement_non_medicamenteux' => $data['traitement_non_medicamenteux'] ?? null,
                ':surveillance'                 => $data['surveillance']                 ?? null,
                ':notes_suivi'                  => $data['notes_suivi']                  ?? null,
                ':date_suivi'                   => $data['date_suivi']                   ?? null,
                ':systeme_principal'            => $data['systeme_principal']            ?? null,
                ':symptomes_systemiques'        => $data['symptomes_systemiques']        ?? null,
                ':commentaires_systemiques'     => $data['commentaires_systemiques']     ?? null,
            ];
            foreach ($extraVitaux as $col => $val) {
                $params[':' . $col] = $val;
            }

            $result = $stmt->execute($params);

            if ($result) {
                return $this->db->lastInsertId();
            }
            $err = $stmt->errorInfo();
            error_log("Erreur création consultation : " . implode(' | ', $err));
            return false;
        } catch (Exception $e) {
            error_log("Exception création consultation: " . $e->getMessage());
            return false;
        }
    }

    public function getById($id) {
        $sql = "SELECT c.*,
                p.nom as patient_nom, p.prenom as patient_prenom, p.dossier_numero,
                u.nom as medecin_nom, u.prenom as medecin_prenom
                FROM consultations c
                JOIN patients p ON c.patient_id = p.id
                JOIN users u ON c.medecin_id = u.id
                WHERE c.id = :id";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getRecent($limit = 10) {
        $sql = "SELECT c.id, c.date_consultation, c.type_consultation, c.motif_consultation, c.diagnostic_principal,
                p.nom as patient_nom, p.prenom as patient_prenom, p.dossier_numero,
                u.nom as medecin_nom, u.prenom as medecin_prenom
                FROM consultations c
                JOIN patients p ON c.patient_id = p.id
                JOIN users u ON c.medecin_id = u.id
                ORDER BY c.date_consultation DESC
                LIMIT :limit";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function countToday() {
        $sql = "SELECT COUNT(*) as total FROM consultations
                WHERE DATE(date_consultation) = CURDATE()";
        $stmt = $this->db->query($sql);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'];
    }

    public function getStatsGraph($periode) {
        $days = $periode === '30days' ? 30 : 7;

        $sql = "SELECT DATE(date_consultation) as date, COUNT(*) as total
                FROM consultations
                WHERE date_consultation >= DATE_SUB(NOW(), INTERVAL :days DAY)
                GROUP BY DATE(date_consultation)
                ORDER BY date";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':days' => $days]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function update($id, $data) {
        $data = $this->cleanEmptyStrings($data);

        // Décomposer tension_arterielle "120/80" → systolique / diastolique
        $ta = $data['tension_arterielle'] ?? '';
        $taParts = explode('/', $ta);
        $taSys = isset($taParts[0]) && is_numeric(trim($taParts[0])) ? (int)trim($taParts[0]) : ($data['tension_systolique'] ?? null);
        $taDia = isset($taParts[1]) && is_numeric(trim($taParts[1])) ? (int)trim($taParts[1]) : ($data['tension_diastolique'] ?? null);

        // Construction dynamique des SET pour gérer les nouvelles colonnes
        $extraVitaux = $this->buildExtraVitauxFields($data);

        $baseSets = [
            'type_consultation = :type_consultation',
            'motif_consultation = :motif_consultation',
            'histoire_maladie = :histoire_maladie',
            'automedication = :automedication',
            'complement_anamnese = :complement_anamnese',
            'atcd_medicaux = :atcd_medicaux',
            'atcd_chirurgicaux = :atcd_chirurgicaux',
            'atcd_familiaux = :atcd_familiaux',
            'atcd_allergies = :atcd_allergies',
            'temperature = :temperature',
            'tension_systolique = :tension_systolique',
            'tension_diastolique = :tension_diastolique',
            'frequence_cardiaque = :frequence_cardiaque',
            'poids = :poids',
            'taille = :taille',
            'examen_physique = :examen_physique',
            'resume_syndromique = :resume_syndromique',
            'hypotheses_diagnostiques = :hypotheses_diagnostiques',
            'diagnostic_principal = :diagnostic_principal',
            'diagnostics_differentiels = :diagnostics_differentiels',
            'examens_paracliniques = :examens_paracliniques',
            'plan_traitement = :plan_traitement',
            'traitement_non_medicamenteux = :traitement_non_medicamenteux',
            'surveillance = :surveillance',
            'notes_suivi = :notes_suivi',
            'date_suivi = :date_suivi',
            'systeme_principal = :systeme_principal',
            'symptomes_systemiques = :symptomes_systemiques',
            'commentaires_systemiques = :commentaires_systemiques',
        ];
        foreach ($extraVitaux as $col => $val) {
            $baseSets[] = "$col = :$col";
        }

        $sql = "UPDATE consultations SET " . implode(', ', $baseSets) . " WHERE id = :id";

        $stmt = $this->db->prepare($sql);

        $params = [
            ':motif_consultation'           => $data['motif_consultation']           ?? null,
            ':histoire_maladie'             => $data['histoire_maladie']             ?? null,
            ':automedication'               => $data['automedication']               ?? null,
            ':complement_anamnese'          => $data['complement_anamnese']          ?? null,
            ':atcd_medicaux'                => $data['atcd_medicaux']                ?? null,
            ':atcd_chirurgicaux'            => $data['atcd_chirurgicaux']            ?? null,
            ':atcd_familiaux'               => $data['atcd_familiaux']               ?? null,
            ':atcd_allergies'               => $data['atcd_allergies']               ?? null,
            ':temperature'                  => $data['temperature']                  ?? null,
            ':tension_systolique'           => $taSys,
            ':tension_diastolique'          => $taDia,
            ':frequence_cardiaque'          => $data['frequence_cardiaque']          ?? null,
            ':poids'                        => $data['poids']                        ?? null,
            ':taille'                       => $data['taille']                       ?? null,
            ':examen_physique'              => $data['examen_physique']              ?? null,
            ':resume_syndromique'           => $data['resume_syndromique']           ?? null,
            ':hypotheses_diagnostiques'     => $data['hypotheses_diagnostiques']     ?? null,
            ':diagnostic_principal'         => $data['diagnostic_principal']         ?? null,
            ':diagnostics_differentiels'    => $data['diagnostics_differentiels']    ?? null,
            ':examens_paracliniques'        => $data['examens_paracliniques']        ?? null,
            ':plan_traitement'              => $data['plan_traitement']              ?? null,
            ':traitement_non_medicamenteux' => $data['traitement_non_medicamenteux'] ?? null,
            ':type_consultation'            => strtolower(trim($data['type'] ?? 'externe')),
            ':surveillance'                 => $data['surveillance']                 ?? null,
            ':notes_suivi'                  => $data['notes_suivi']                  ?? null,
            ':date_suivi'                   => $data['date_suivi']                   ?? null,
            ':systeme_principal'            => $data['systeme_principal']            ?? null,
            ':symptomes_systemiques'        => $data['symptomes_systemiques']        ?? null,
            ':commentaires_systemiques'     => $data['commentaires_systemiques']     ?? null,
            ':id'                           => $id,
        ];
        foreach ($extraVitaux as $col => $val) {
            $params[':' . $col] = $val;
        }

        $success = $stmt->execute($params);

        if (!$success) {
            $err = $stmt->errorInfo();
            error_log("Erreur mise à jour consultation (ID={$id}) : " . implode(' | ', $err));
            return false;
        }

        return true;
    }

    public function save($data) {
        $db = (new Database())->getConnection();
        $sql = "INSERT INTO consultations (patient_id, medecin_id, motif_consultation, examen_physique, diagnostic_principal, date_consultation)
                VALUES (:pid, :mid, :motif, :examen, :diag, NOW())";

        $stmt = $db->prepare($sql);
        $stmt->execute([
            ':pid'    => $data['patient_id'],
            ':mid'    => $data['medecin_id'],
            ':motif'  => $data['motif'] ?? $data['motif_consultation'] ?? null,
            ':examen' => $data['examen_physique'] ?? null,
            ':diag'   => $data['diagnostic'] ?? $data['diagnostic_principal'] ?? null,
        ]);

        return $db->lastInsertId();
    }

    /**
     * Nettoie les chaînes vides en NULL pour les champs DATE/optionnels
     */
    private function cleanEmptyStrings($data) {
        $cleaned = [];
        foreach ($data as $key => $value) {
            if ($value === '' || $value === '0000-00-00') {
                $cleaned[$key] = null;
            } else {
                $cleaned[$key] = $value;
            }
        }
        return $cleaned;
    }
}
?>
