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
   FICHIER : app/controllers/SpecialisteController.php
   MODULE  : Gestion des rendez-vous médecins spécialistes
   Rôles   : SECRETAIRE_SPECIALISTE | COORDONNATEUR_SOINS (écriture+supervision) | ACCUEIL (lecture) | ADMIN (tout)
   ============================================================================ */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../services/Auth.php';
require_once __DIR__ . '/../services/AuditService.php';

class SpecialisteController {

    private PDO $db;
    private Auth $auth;
    private AuditService $audit;

    // Libellés des spécialités pour l'affichage
    const LABELS = [
        'DENTISTE'           => 'Dentiste',
        'OPHTALMOLOGUE'      => 'Ophtalmologue',
        'UROLOGUE'           => 'Urologue',
        'GASTRO_ENTEROLOGUE' => 'Gastro-entérologue',
        'CARDIOLOGUE'        => 'Cardiologue',
        'CHIRURGIEN_PEDIATRE'=> 'Chirurgien Pédiatre',
        'ORL'                => 'ORL',
        'PSYCHOLOGUE'        => 'Psychologue',
    ];

    // Libellés des circuits (source) pour l'affichage
    const SOURCE_LABELS = [
        'PAYANT_COMPTANT' => 'Payant Comptant',
        'FAMILLE_PHP'     => 'Famille PHP',
        'AGENT_PHP'       => 'Agent PHP',
        'HOSPITALISATION' => 'Hospitalisation',
        'SECRETAIRE'      => 'Secrétariat',
    ];

    public function __construct() {
        $this->db    = (new Database())->getConnection();
        $this->auth  = Auth::getInstance();
        $this->audit = new AuditService();

        if (!$this->auth->isLoggedIn()) {
            header('Location: ' . BASE_URL . 'login'); exit;
        }
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function isSecretaire(): bool {
        $r = strtoupper($_SESSION['user_role'] ?? '');
        return in_array($r, ['SECRETAIRE_SPECIALISTE', 'COORDONNATEUR_SOINS', 'ADMIN', 'ADMINISTRATEUR', 'DIRECTEUR']);
    }

    private function isAccueil(): bool {
        $r = strtoupper($_SESSION['user_role'] ?? '');
        return in_array($r, ['ACCUEIL', 'SECRETAIRE_SPECIALISTE', 'COORDONNATEUR_SOINS', 'ADMIN', 'ADMINISTRATEUR']);
    }

    private function canPrescribe(): bool {
        $r = strtoupper($_SESSION['user_role'] ?? '');
        return in_array($r, ['MEDECIN','CHIRURGIEN','INFIRMIER_CONSULTANT','ADMIN','ADMINISTRATEUR','GENERALISTE']);
    }

    /** Retourne le nombre de RDV non-annulés pour un spécialiste à une date */
    private function compterRdv(int $specialisteId, string $date): int {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) FROM rdv_specialistes
            WHERE specialiste_id = ? AND date_rdv = ? AND statut NOT IN ('ANNULE','REPORTE')
        ");
        $stmt->execute([$specialisteId, $date]);
        return (int)$stmt->fetchColumn();
    }

    /** Retourne le prochain numéro d'ordre disponible */
    private function prochainOrdre(int $specialisteId, string $date): int {
        $stmt = $this->db->prepare("
            SELECT COALESCE(MAX(ordre_passage), 0) + 1 FROM rdv_specialistes
            WHERE specialiste_id = ? AND date_rdv = ? AND statut NOT IN ('ANNULE','REPORTE')
        ");
        $stmt->execute([$specialisteId, $date]);
        return (int)$stmt->fetchColumn();
    }

    /** Charge tous les spécialistes actifs */
    private function getSpecialistes(): array {
        return $this->db->query("SELECT * FROM specialistes WHERE actif = 1 ORDER BY specialite")
                        ->fetchAll(PDO::FETCH_ASSOC);
    }

    // ── Dashboard secrétaire ──────────────────────────────────────────────────

    public function secretariat(): void {
        if (!$this->isSecretaire()) {
            http_response_code(403);
            $error_cause = "Cette page est réservée à la secrétaire des spécialistes.";
            require __DIR__ . '/../views/errors/403.php';
            exit;
        }

        $date          = $_GET['date']          ?? date('Y-m-d');
        $specialisteId = (int)($_GET['spec_id'] ?? 0);
        $specialistes  = $this->getSpecialistes();

        // Si aucun spécialiste sélectionné, prendre le premier
        if (!$specialisteId && !empty($specialistes)) {
            $specialisteId = (int)$specialistes[0]['id'];
        }

        // Spécialiste actif
        $specialiste = null;
        foreach ($specialistes as $s) {
            if ((int)$s['id'] === $specialisteId) { $specialiste = $s; break; }
        }

        // KPIs : comptage par spécialiste pour la date choisie
        $kpis = [];
        foreach ($specialistes as $s) {
            $kpis[$s['id']] = [
                'count'     => $this->compterRdv((int)$s['id'], $date),
                'quota_min' => (int)$s['quota_min'],
                'quota_max' => (int)$s['quota_max'],
            ];
        }

        // Liste des RDVs actifs pour le spécialiste + date sélectionnés (hors TERMINE)
        $rdvs = [];
        if ($specialiste) {
            $stmt = $this->db->prepare("
                SELECT r.*, p.nom, p.prenom, p.dossier_numero, p.telephone,
                       u.nom AS prescripteur_nom, u.prenom AS prescripteur_prenom
                FROM rdv_specialistes r
                JOIN patients p ON r.patient_id = p.id
                LEFT JOIN users u ON r.prescrit_par = u.id
                WHERE r.specialiste_id = ? AND r.date_rdv = ?
                  AND r.statut NOT IN ('TERMINE')
                ORDER BY CASE WHEN r.statut IN ('ANNULE','REPORTE') THEN 1 ELSE 0 END,
                         COALESCE(r.ordre_passage, 9999), r.created_at
            ");
            $stmt->execute([$specialisteId, $date]);
            $rdvs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        // Liste des patients vus (TERMINE) pour ce spécialiste ce jour
        $rdvs_termines = [];
        if ($specialiste) {
            $stmt2 = $this->db->prepare("
                SELECT r.*, p.nom, p.prenom, p.dossier_numero, p.telephone,
                       p.type_client, p.circuit,
                       s.specialite
                FROM rdv_specialistes r
                JOIN patients p ON r.patient_id = p.id
                JOIN specialistes s ON r.specialiste_id = s.id
                WHERE r.specialiste_id = ? AND r.date_rdv = ? AND r.statut = 'TERMINE'
                ORDER BY r.updated_at DESC
            ");
            $stmt2->execute([$specialisteId, $date]);
            $rdvs_termines = $stmt2->fetchAll(PDO::FETCH_ASSOC);
        }

        require_once __DIR__ . '/../views/specialiste/secretariat.php';
    }

    // ── Créer un RDV ─────────────────────────────────────────────────────────

    public function storeRdv(): void {
        header('Content-Type: application/json');
        if (!$this->auth->isLoggedIn()) {
            echo json_encode(['success' => false, 'message' => 'Non connecté.']); exit;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Méthode invalide.']); exit;
        }

        $patientId     = (int)($_POST['patient_id']     ?? 0);
        $specialisteId = (int)($_POST['specialiste_id'] ?? 0);
        $dateRdv       = trim($_POST['date_rdv']        ?? '');
        $heureRdv      = trim($_POST['heure_rdv']       ?? '') ?: null;
        $motif         = trim($_POST['motif']           ?? '');
        $notes         = trim($_POST['notes_secretaire'] ?? '');

        if (!$patientId || !$specialisteId || !$dateRdv) {
            echo json_encode(['success' => false, 'message' => 'Champs obligatoires manquants.']); exit;
        }

        // Déterminer la source selon le rôle et le type de patient
        $role = strtoupper($_SESSION['user_role'] ?? '');
        if (in_array($role, ['SECRETAIRE_SPECIALISTE', 'COORDONNATEUR_SOINS'])) {
            $source = 'SECRETAIRE';                        // secrétaire / coordonnateur : pas de quota
        } elseif ($this->canPrescribe()) {
            $source = 'HOSPITALISATION';                   // médecin/prescripteur → Hospitalisation (10%)
        } else {
            $source = $this->determineSource($patientId);  // PAYANT_COMPTANT / FAMILLE_PHP / AGENT_PHP
        }

        // Charger le spécialiste
        $spec = $this->db->prepare("SELECT * FROM specialistes WHERE id = ?");
        $spec->execute([$specialisteId]);
        $specRow = $spec->fetch(PDO::FETCH_ASSOC);
        if (!$specRow) {
            echo json_encode(['success' => false, 'message' => 'Spécialiste introuvable.']); exit;
        }

        // Vérification quota par circuit (la secrétaire peut toujours ajouter)
        if ($source !== 'SECRETAIRE') {
            $quotas   = $this->calcQuotas((int)$specRow['quota_max']);
            $pris     = $this->compterParSource($specialisteId, $dateRdv);
            $qKey     = $this->quotaKey($source);
            $qCircuit = $quotas[$qKey] ?? 0;
            $nCircuit = $pris[$source] ?? 0;

            if ($qCircuit > 0 && $nCircuit >= $qCircuit) {
                $label = self::SOURCE_LABELS[$source] ?? $source;
                echo json_encode([
                    'success'       => false,
                    'quota_atteint' => true,
                    'message'       => "Quota {$label} atteint ({$nCircuit}/{$qCircuit}). Contactez la secrétaire.",
                ]); exit;
            }
        }

        $ordre = $this->prochainOrdre($specialisteId, $dateRdv);

        $stmt = $this->db->prepare("
            INSERT INTO rdv_specialistes
                (patient_id, specialiste_id, date_rdv, heure_rdv, motif, statut, source,
                 prescrit_par, ordre_passage, notes_secretaire)
            VALUES (?, ?, ?, ?, ?, 'EN_ATTENTE', ?, ?, ?, ?)
        ");
        $stmt->execute([
            $patientId, $specialisteId, $dateRdv, $heureRdv, $motif,
            $source,
            $this->canPrescribe() || $this->isSecretaire() ? $_SESSION['user_id'] : null,
            $ordre, $notes ?: null,
        ]);

        $rdvId = $this->db->lastInsertId();
        $this->audit->logAction('CREATE', 'rdv_specialistes', $rdvId, null,
            "RDV créé pour patient #$patientId chez spécialiste #$specialisteId le $dateRdv");

        echo json_encode([
            'success' => true,
            'message' => 'Rendez-vous créé avec succès.',
            'rdv_id'  => $rdvId,
            'ordre'   => $ordre,
        ]);
        exit;
    }

    // ── Modifier un RDV ───────────────────────────────────────────────────────

    public function updateRdv(int $id): void {
        header('Content-Type: application/json');
        if (!$this->isSecretaire()) {
            echo json_encode(['success' => false, 'message' => 'Accès refusé.']); exit;
        }

        $dateRdv  = trim($_POST['date_rdv']  ?? '');
        $heureRdv = trim($_POST['heure_rdv'] ?? '') ?: null;
        $motif    = trim($_POST['motif']     ?? '');
        $notes    = trim($_POST['notes_secretaire'] ?? '');
        $statut   = $_POST['statut'] ?? 'EN_ATTENTE';

        $stmt = $this->db->prepare("
            UPDATE rdv_specialistes
            SET date_rdv = ?, heure_rdv = ?, motif = ?, notes_secretaire = ?, statut = ?
            WHERE id = ?
        ");
        $stmt->execute([$dateRdv, $heureRdv, $motif, $notes ?: null, $statut, $id]);

        $this->audit->logAction('UPDATE', 'rdv_specialistes', $id, null, "RDV #$id modifié");
        echo json_encode(['success' => true, 'message' => 'Rendez-vous mis à jour.']);
        exit;
    }

    // ── Annuler un RDV ───────────────────────────────────────────────────────

    public function annulerRdv(int $id): void {
        header('Content-Type: application/json');
        if (!$this->isSecretaire()) {
            echo json_encode(['success' => false, 'message' => 'Accès refusé.']); exit;
        }

        $this->db->prepare("UPDATE rdv_specialistes SET statut = 'ANNULE' WHERE id = ?")
                 ->execute([$id]);
        $this->audit->logAction('UPDATE', 'rdv_specialistes', $id, null, "RDV #$id annulé");
        echo json_encode(['success' => true, 'message' => 'Rendez-vous annulé.']);
        exit;
    }

    // ── Marquer un RDV comme NON HONORÉ (patient absent) ─────────────────────
    /**
     * Différent de ANNULE :
     *   - ANNULE     = rendez-vous annulé à l'avance (patient prévient ou la secrétaire annule)
     *   - NON_HONORE = patient ne s'est pas présenté à son rendez-vous (no-show)
     *
     * Le motif optionnel est stocké dans notes_secretaire (préfixé "[NON HONORÉ]").
     */
    public function marquerNonHonore(int $id): void {
        header('Content-Type: application/json');
        if (!$this->isSecretaire()) {
            echo json_encode(['success' => false, 'message' => 'Accès refusé.']); exit;
        }

        // Récupérer le RDV pour vérifier qu'il est dans un statut "non honorable"
        $stmt = $this->db->prepare("SELECT statut, notes_secretaire FROM rdv_specialistes WHERE id = ?");
        $stmt->execute([$id]);
        $rdv = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$rdv) {
            echo json_encode(['success' => false, 'message' => 'RDV introuvable.']); exit;
        }
        if (in_array($rdv['statut'], ['TERMINE', 'EN_COURS'])) {
            echo json_encode(['success' => false, 'message' => "Impossible de marquer non-honoré un RDV déjà {$rdv['statut']}."]); exit;
        }

        $motif = trim($_POST['motif'] ?? '');
        $userName = trim(($_SESSION['user_nom'] ?? '') . ' ' . ($_SESSION['user_prenom'] ?? ''));
        $stamp = date('d/m/Y H:i');
        $tag = "[NON HONORÉ — $stamp" . ($userName !== '' ? " par $userName" : '') . ']';
        if ($motif !== '') $tag .= ' ' . $motif;

        $newNotes = $rdv['notes_secretaire']
            ? trim($rdv['notes_secretaire']) . "\n" . $tag
            : $tag;

        $this->db->prepare("
            UPDATE rdv_specialistes
            SET statut = 'NON_HONORE', notes_secretaire = ?
            WHERE id = ?
        ")->execute([$newNotes, $id]);

        $this->audit->logAction('UPDATE', 'rdv_specialistes', $id, null,
            "RDV #$id marqué non honoré" . ($motif ? " — motif : $motif" : ''));

        echo json_encode([
            'success' => true,
            'message' => 'Rendez-vous marqué comme non honoré.',
        ]);
        exit;
    }

    // ── Marquer en cours ─────────────────────────────────────────────────────

    public function marquerEnCours(int $id): void {
        header('Content-Type: application/json');
        if (!$this->isSecretaire()) {
            echo json_encode(['success' => false, 'message' => 'Accès refusé.']); exit;
        }
        $this->db->prepare("UPDATE rdv_specialistes SET statut = 'EN_COURS' WHERE id = ?")
                 ->execute([$id]);
        echo json_encode(['success' => true, 'message' => 'Patient appelé.']);
        exit;
    }

    // ── Marquer terminé ──────────────────────────────────────────────────────

    public function marquerTermine(int $id): void {
        header('Content-Type: application/json');
        if (!$this->isSecretaire()) {
            echo json_encode(['success' => false, 'message' => 'Accès refusé.']); exit;
        }
        $conclusion = trim($_POST['conclusion'] ?? '');
        $this->db->prepare("
            UPDATE rdv_specialistes
               SET statut = 'TERMINE',
                   conclusion_specialiste = ?
             WHERE id = ?
        ")->execute([$conclusion ?: null, $id]);
        $this->audit->logAction('UPDATE', 'rdv_specialistes', $id, null, "Consultation terminée — RDV #$id");
        echo json_encode(['success' => true, 'message' => 'Consultation terminée.']);
        exit;
    }

    // ── Réordonner les passages ───────────────────────────────────────────────

    public function reordonner(): void {
        header('Content-Type: application/json');
        if (!$this->isSecretaire()) {
            echo json_encode(['success' => false, 'message' => 'Accès refusé.']); exit;
        }

        // Reçoit un tableau JSON d'IDs dans le bon ordre
        $data = json_decode(file_get_contents('php://input'), true);
        $ids  = $data['ids'] ?? [];

        if (empty($ids)) {
            echo json_encode(['success' => false, 'message' => 'Aucun ID fourni.']); exit;
        }

        $stmt = $this->db->prepare("UPDATE rdv_specialistes SET ordre_passage = ? WHERE id = ?");
        foreach ($ids as $ordre => $rdvId) {
            $stmt->execute([$ordre + 1, (int)$rdvId]);
        }

        echo json_encode(['success' => true, 'message' => 'Ordre mis à jour.']);
        exit;
    }

    // ── Déplacer d'un cran (bouton ↑/↓) ──────────────────────────────────────

    public function deplacerRdv(int $id): void {
        header('Content-Type: application/json');
        if (!$this->isSecretaire()) {
            echo json_encode(['success' => false, 'message' => 'Accès refusé.']); exit;
        }

        $direction = $_POST['direction'] ?? 'up'; // 'up' ou 'down'

        $stmt = $this->db->prepare("SELECT * FROM rdv_specialistes WHERE id = ?");
        $stmt->execute([$id]);
        $rdv = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$rdv) { echo json_encode(['success' => false, 'message' => 'RDV introuvable.']); exit; }

        $currentOrdre = (int)$rdv['ordre_passage'];
        $targetOrdre  = $direction === 'up' ? $currentOrdre - 1 : $currentOrdre + 1;

        // Trouver le RDV voisin
        $stmtVoisin = $this->db->prepare("
            SELECT id FROM rdv_specialistes
            WHERE specialiste_id = ? AND date_rdv = ? AND ordre_passage = ?
              AND statut NOT IN ('ANNULE','REPORTE')
        ");
        $stmtVoisin->execute([$rdv['specialiste_id'], $rdv['date_rdv'], $targetOrdre]);
        $voisin = $stmtVoisin->fetchColumn();

        if ($voisin) {
            // Échanger les ordres
            $this->db->prepare("UPDATE rdv_specialistes SET ordre_passage = ? WHERE id = ?")
                     ->execute([$currentOrdre, $voisin]);
        }
        $this->db->prepare("UPDATE rdv_specialistes SET ordre_passage = ? WHERE id = ?")
                 ->execute([$targetOrdre, $id]);

        echo json_encode(['success' => true]);
        exit;
    }

    // ── Helpers quota par circuit ─────────────────────────────────────────────

    /**
     * Calcule les sous-quotas par circuit depuis la DB (colonnes quota_pct_* sur specialistes)
     * ou utilise les pourcentages par défaut si les colonnes sont absentes.
     *   PAYANT_COMPTANT = quota_pct_payant     % (défaut 40%)
     *   FAMILLE_PHP     = quota_pct_famille_php% (défaut 25%)
     *   AGENT_PHP       = quota_pct_agent_php  % (défaut 25%)
     *   HOSPITALISATION = reste (~10%)
     */
    private function calcQuotas(int $quotaMax, ?int $specialisteId = null): array {
        $pctPayant  = 40;
        $pctFamille = 25;
        $pctAgent   = 25;

        if ($specialisteId) {
            try {
                $s = $this->db->prepare(
                    "SELECT quota_pct_payant, quota_pct_famille_php, quota_pct_agent_php
                     FROM specialistes WHERE id = ?"
                );
                $s->execute([$specialisteId]);
                $row = $s->fetch(\PDO::FETCH_ASSOC);
                if ($row) {
                    $pctPayant  = (int)($row['quota_pct_payant']     ?? 40);
                    $pctFamille = (int)($row['quota_pct_famille_php'] ?? 25);
                    $pctAgent   = (int)($row['quota_pct_agent_php']   ?? 25);
                }
            } catch (\PDOException $e) {
                // Colonnes pas encore migrées → valeurs par défaut
            }
        }

        $payant_comptant = (int)floor($quotaMax * $pctPayant  / 100);
        $famille_php     = (int)floor($quotaMax * $pctFamille / 100);
        $agent_php       = (int)floor($quotaMax * $pctAgent   / 100);
        $hospitalisation = max(0, $quotaMax - $payant_comptant - $famille_php - $agent_php);

        return compact('payant_comptant', 'famille_php', 'agent_php', 'hospitalisation');
    }

    /** Compte les RDV par source pour un spécialiste à une date */
    private function compterParSource(int $specialisteId, string $date): array {
        $stmt = $this->db->prepare("
            SELECT source, COUNT(*) AS nb
            FROM rdv_specialistes
            WHERE specialiste_id = ? AND date_rdv = ?
              AND statut NOT IN ('ANNULE','REPORTE')
            GROUP BY source
        ");
        $stmt->execute([$specialisteId, $date]);
        $result = [
            'PAYANT_COMPTANT' => 0,
            'FAMILLE_PHP'     => 0,
            'AGENT_PHP'       => 0,
            'HOSPITALISATION' => 0,
            'SECRETAIRE'      => 0,
        ];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            if (array_key_exists($row['source'], $result)) {
                $result[$row['source']] = (int)$row['nb'];
            }
        }
        return $result;
    }

    /**
     * Détermine la source (circuit) d'un RDV à partir du type_client du patient.
     * Agent PHP  → AGENT_PHP
     * Famille PHP → FAMILLE_PHP
     * Autres     → PAYANT_COMPTANT
     */
    private function determineSource(int $patientId): string {
        $stmt = $this->db->prepare("SELECT type_client, circuit FROM patients WHERE id = ?");
        $stmt->execute([$patientId]);
        $p = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$p) return 'PAYANT_COMPTANT';
        if ($p['type_client'] === 'AGENTS_PHP')  return 'AGENT_PHP';
        if ($p['type_client'] === 'FAMILLE_PHP')  return 'FAMILLE_PHP';
        if ($p['circuit'] === 'PHP')              return 'FAMILLE_PHP'; // fallback PHP sans type précis
        return 'PAYANT_COMPTANT';
    }

    /**
     * Clé de tableau calcQuotas() correspondant à une valeur de source.
     * PAYANT_COMPTANT → 'payant_comptant', etc.
     */
    private function quotaKey(string $source): string {
        return strtolower($source); // les clés de calcQuotas() sont déjà en snake_case minuscule
    }

    // ── Vérifier quota (AJAX) ─────────────────────────────────────────────────

    public function checkQuota(): void {
        header('Content-Type: application/json');
        if (!$this->auth->isLoggedIn()) {
            echo json_encode(['success' => false]); exit;
        }

        $specialisteId = (int)($_GET['spec_id']       ?? $_GET['specialiste_id'] ?? 0);
        $date          = trim($_GET['date']             ?? '');
        $circuit       = strtoupper(trim($_GET['circuit'] ?? 'STANDARD'));

        if (!$specialisteId || !$date) {
            echo json_encode(['success' => false, 'message' => 'Paramètres manquants.']); exit;
        }

        $spec = $this->db->prepare("SELECT * FROM specialistes WHERE id = ?");
        $spec->execute([$specialisteId]);
        $specRow = $spec->fetch(PDO::FETCH_ASSOC);
        if (!$specRow) {
            echo json_encode(['success' => false, 'message' => 'Spécialiste introuvable.']); exit;
        }

        $quotas  = $this->calcQuotas((int)$specRow['quota_max']);
        $pris    = $this->compterParSource($specialisteId, $date);
        $total   = $this->compterRdv($specialisteId, $date);

        // Quota applicable selon le circuit demandé
        // Accepte : PAYANT_COMPTANT | FAMILLE_PHP | AGENT_PHP | HOSPITALISATION
        $validSources = ['PAYANT_COMPTANT', 'FAMILLE_PHP', 'AGENT_PHP', 'HOSPITALISATION'];
        if (!in_array($circuit, $validSources)) $circuit = 'PAYANT_COMPTANT';
        $qKey    = $this->quotaKey($circuit);
        $qSource = $quotas[$qKey] ?? 0;
        $nSource = $pris[$circuit] ?? 0;

        echo json_encode([
            'success'    => true,
            'quota_max'  => (int)$specRow['quota_max'],
            'total_pris' => $total,
            'jours'      => $specRow['jours_consultation'],
            // Détail PAYANT COMPTANT (40%)
            'quota_payant_comptant'  => $quotas['payant_comptant'],
            'pris_payant_comptant'   => $pris['PAYANT_COMPTANT'],
            'dispo_payant_comptant'  => max(0, $quotas['payant_comptant'] - $pris['PAYANT_COMPTANT']),
            // Détail FAMILLE PHP (25%)
            'quota_famille_php'      => $quotas['famille_php'],
            'pris_famille_php'       => $pris['FAMILLE_PHP'],
            'dispo_famille_php'      => max(0, $quotas['famille_php'] - $pris['FAMILLE_PHP']),
            // Détail AGENT PHP (25%)
            'quota_agent_php'        => $quotas['agent_php'],
            'pris_agent_php'         => $pris['AGENT_PHP'],
            'dispo_agent_php'        => max(0, $quotas['agent_php'] - $pris['AGENT_PHP']),
            // Détail HOSPITALISATION (10%)
            'quota_hospitalisation'  => $quotas['hospitalisation'],
            'pris_hospitalisation'   => $pris['HOSPITALISATION'],
            'dispo_hospitalisation'  => max(0, $quotas['hospitalisation'] - $pris['HOSPITALISATION']),
            // Pour le circuit demandé
            'circuit'       => $circuit,
            'quota_circuit' => $qSource,
            'pris_circuit'  => $nSource,
            'dispo_circuit' => max(0, $qSource - $nSource),
            'complet'       => $nSource >= $qSource,
        ]);
        exit;
    }

    /**
     * Quotas du jour pour tous les spécialistes — utilisé par le panneau accueil (JSON)
     */
    public function quotasDuJour(): void {
        header('Content-Type: application/json');
        $date  = trim($_GET['date'] ?? date('Y-m-d'));
        $specs = $this->db->query("SELECT * FROM specialistes WHERE actif=1 ORDER BY specialite")
                          ->fetchAll(PDO::FETCH_ASSOC);
        $result = [];
        foreach ($specs as $sp) {
            $quotas = $this->calcQuotas((int)$sp['quota_max']);
            $pris   = $this->compterParSource((int)$sp['id'], $date);
            $result[] = [
                'id'         => $sp['id'],
                'specialite' => self::LABELS[$sp['specialite']] ?? $sp['specialite'],
                'quota_max'  => (int)$sp['quota_max'],
                'jours'      => $sp['jours_consultation'],
                // Payant Comptant (40%)
                'q_payant'   => $quotas['payant_comptant'],
                'p_payant'   => $pris['PAYANT_COMPTANT'],
                'd_payant'   => max(0, $quotas['payant_comptant'] - $pris['PAYANT_COMPTANT']),
                // Famille PHP (25%)
                'q_fam'      => $quotas['famille_php'],
                'p_fam'      => $pris['FAMILLE_PHP'],
                'd_fam'      => max(0, $quotas['famille_php'] - $pris['FAMILLE_PHP']),
                // Agent PHP (25%)
                'q_agent'    => $quotas['agent_php'],
                'p_agent'    => $pris['AGENT_PHP'],
                'd_agent'    => max(0, $quotas['agent_php'] - $pris['AGENT_PHP']),
                // Hospitalisation (10%)
                'q_hosp'     => $quotas['hospitalisation'],
                'p_hosp'     => $pris['HOSPITALISATION'],
                'd_hosp'     => max(0, $quotas['hospitalisation'] - $pris['HOSPITALISATION']),
            ];
        }
        echo json_encode($result);
        exit;
    }

    // ── Vue accueil (lecture) ─────────────────────────────────────────────────

    public function accueilView(): void {
        if (!$this->isAccueil()) {
            http_response_code(403); die("Accès refusé.");
        }

        $date          = $_GET['date']    ?? date('Y-m-d');
        $specialisteId = (int)($_GET['spec_id'] ?? 0);
        $specialistes  = $this->getSpecialistes();

        $rdvs = [];
        if ($specialisteId) {
            $stmt = $this->db->prepare("
                SELECT r.*, p.nom, p.prenom, p.dossier_numero, p.telephone
                FROM rdv_specialistes r
                JOIN patients p ON r.patient_id = p.id
                WHERE r.specialiste_id = ? AND r.date_rdv = ?
                ORDER BY COALESCE(r.ordre_passage, 9999), r.created_at
            ");
            $stmt->execute([$specialisteId, $date]);
            $rdvs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            // Tous les spécialistes du jour
            $stmt = $this->db->prepare("
                SELECT r.*, p.nom, p.prenom, p.dossier_numero,
                       s.specialite, s.quota_max
                FROM rdv_specialistes r
                JOIN patients p ON r.patient_id = p.id
                JOIN specialistes s ON r.specialiste_id = s.id
                WHERE r.date_rdv = ?
                ORDER BY s.specialite, COALESCE(r.ordre_passage, 9999)
            ");
            $stmt->execute([$date]);
            $rdvs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        require_once __DIR__ . '/../views/specialiste/accueil_rdv.php';
    }

    // ── Orienter patient vers spécialiste (depuis mesures) ────────────────────

    public function orienterPatient(int $rdvId = 0): void {
        header('Content-Type: application/json');
        if (!$this->auth->isLoggedIn()) {
            echo json_encode(['success' => false, 'message' => 'Non connecté.']); exit;
        }

        // Accept rdv_id from URL segment or POST body
        if (!$rdvId) $rdvId = (int)($_POST['rdv_id'] ?? 0);
        $patientId = (int)($_POST['patient_id'] ?? 0);

        if (!$rdvId || !$patientId) {
            echo json_encode(['success' => false, 'message' => 'Paramètres manquants.']); exit;
        }

        // Charger le RDV
        $stmt = $this->db->prepare("SELECT r.*, s.quota_max FROM rdv_specialistes r JOIN specialistes s ON r.specialiste_id = s.id WHERE r.id = ?");
        $stmt->execute([$rdvId]);
        $rdv = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$rdv || $rdv['patient_id'] != $patientId) {
            echo json_encode(['success' => false, 'message' => 'RDV introuvable.']); exit;
        }

        // Vérifier quota (sauf si rôle secrétaire ou admin)
        if (!$this->isSecretaire()) {
            $count = $this->compterRdv((int)$rdv['specialiste_id'], $rdv['date_rdv']);
            if ($count >= $rdv['quota_max']) {
                echo json_encode([
                    'success'       => false,
                    'quota_atteint' => true,
                    'message'       => "Quota atteint ({$count}/{$rdv['quota_max']}). Contactez la secrétaire des spécialistes.",
                ]); exit;
            }
        }

        $this->db->beginTransaction();
        try {
            // Mettre à jour le statut du RDV
            $this->db->prepare("UPDATE rdv_specialistes SET statut = 'CONFIRME' WHERE id = ?")
                     ->execute([$rdvId]);
            // Mettre à jour le parcours du patient
            $this->db->prepare("UPDATE patients SET statut_parcours = 'ATTENTE_SPECIALISTE' WHERE id = ?")
                     ->execute([$patientId]);
            $this->db->commit();

            $this->audit->logAction('UPDATE', 'patients', $patientId, null,
                "Patient orienté vers spécialiste (RDV #$rdvId)");

            echo json_encode(['success' => true, 'message' => 'Patient orienté vers le spécialiste.']);
        } catch (Exception $e) {
            $this->db->rollBack();
            echo json_encode(['success' => false, 'message' => 'Erreur : ' . $e->getMessage()]);
        }
        exit;
    }

    // ── Recherche patients (AJAX autocomplete) ───────────────────────────────

    public function searchPatients(): void {
        header('Content-Type: application/json');
        if (!$this->auth->isLoggedIn()) { echo json_encode([]); exit; }

        $q    = trim($_GET['q'] ?? '');
        if (strlen($q) < 2) { echo json_encode([]); exit; }

        $stmt = $this->db->prepare("
            SELECT id, nom, prenom, dossier_numero, telephone
            FROM patients
            WHERE (nom LIKE ? OR prenom LIKE ? OR dossier_numero LIKE ?)
              AND actif = 1
            LIMIT 10
        ");
        $like = "%$q%";
        $stmt->execute([$like, $like, $like]);
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        exit;
    }

    /** Liste publique des spécialistes actifs (JSON) — utilisée par l'accueil */
    public function getSpecialistesJson(): void {
        header('Content-Type: application/json');
        $stmt = $this->db->query("
            SELECT id, specialite, nom, prenom, quota_max, jours_consultation
            FROM specialistes WHERE actif = 1 ORDER BY specialite
        ");
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        exit;
    }

    // ── Historique des RDV spécialistes ──────────────────────────────────────
    /**
     * Page historique pour la secrétaire des spécialistes.
     * Filtres : période (date_debut / date_fin), spécialiste, statut, recherche patient.
     * Pagination : 50 RDV par page.
     */
    public function historique(): void {
        if (!$this->isSecretaire()) {
            http_response_code(403);
            $error_cause = "Cette page est réservée à la secrétaire des spécialistes.";
            require __DIR__ . '/../views/errors/403.php';
            exit;
        }

        // ── Filtres ──────────────────────────────────────────────────────
        $today = date('Y-m-d');
        $dateDebut     = trim($_GET['date_debut']   ?? date('Y-m-d', strtotime('-30 days')));
        $dateFin       = trim($_GET['date_fin']     ?? $today);
        $specialisteId = (int)($_GET['spec_id']     ?? 0);
        $typeClient    = trim($_GET['type_client']    ?? '');
        $statut        = strtoupper(trim($_GET['statut'] ?? ''));
        $q             = trim($_GET['q']            ?? '');
        $page          = max(1, (int)($_GET['page'] ?? 1));
        $perPage       = 50;
        $offset        = ($page - 1) * $perPage;

        // ── Construction WHERE dynamique ─────────────────────────────────
        $where  = ["r.date_rdv BETWEEN ? AND ?"];
        $params = [$dateDebut, $dateFin];

        if ($specialisteId > 0) {
            $where[]  = "r.specialiste_id = ?";
            $params[] = $specialisteId;
        }

        $allowedTypeClients = ['PAYANT_COMPTANT','ASSURANCE','BON_PRISE_EN_CHARGE','AGENTS_PHP','FAMILLE_PHP'];
        if ($typeClient !== '' && in_array($typeClient, $allowedTypeClients, true)) {
            $where[]  = "p.type_client = ?";
            $params[] = $typeClient;
        }

        $statutsValides = ['EN_ATTENTE','CONFIRME','EN_COURS','TERMINE','ANNULE','REPORTE','NON_HONORE'];
        if ($statut !== '' && in_array($statut, $statutsValides)) {
            $where[]  = "r.statut = ?";
            $params[] = $statut;
        }

        if ($q !== '') {
            $where[]  = "(p.nom LIKE ? OR p.prenom LIKE ? OR p.dossier_numero LIKE ? OR p.telephone LIKE ?)";
            $like = "%$q%";
            array_push($params, $like, $like, $like, $like);
        }

        $whereSql = implode(' AND ', $where);

        // ── Requête principale (paginée) ─────────────────────────────────
        $sql = "
            SELECT r.*,
                   p.nom, p.prenom, p.dossier_numero, p.telephone,
                   p.type_client, p.circuit,
                   s.specialite, s.nom AS spec_nom, s.prenom AS spec_prenom,
                   u.nom AS prescripteur_nom, u.prenom AS prescripteur_prenom
            FROM rdv_specialistes r
            JOIN patients p ON r.patient_id = p.id
            JOIN specialistes s ON r.specialiste_id = s.id
            LEFT JOIN users u ON r.prescrit_par = u.id
            WHERE $whereSql
            ORDER BY r.date_rdv DESC, COALESCE(r.heure_rdv, '23:59:59'), r.created_at DESC
            LIMIT $perPage OFFSET $offset
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $rdvs = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // ── Comptage total pour pagination ───────────────────────────────
        $countSql = "
            SELECT COUNT(*)
            FROM rdv_specialistes r
            JOIN patients p ON r.patient_id = p.id
            JOIN specialistes s ON r.specialiste_id = s.id
            WHERE $whereSql
        ";
        $stmtCount = $this->db->prepare($countSql);
        $stmtCount->execute($params);
        $total = (int)$stmtCount->fetchColumn();
        $nbPages = max(1, (int)ceil($total / $perPage));

        // ── Statistiques par statut sur la période filtrée ──────────────
        $statsSql = "
            SELECT r.statut, COUNT(*) AS nb
            FROM rdv_specialistes r
            JOIN patients p ON r.patient_id = p.id
            WHERE $whereSql
            GROUP BY r.statut
        ";
        $stmtStats = $this->db->prepare($statsSql);
        $stmtStats->execute($params);
        $statsByStatut = [
            'EN_ATTENTE' => 0, 'CONFIRME' => 0, 'EN_COURS' => 0,
            'TERMINE' => 0, 'ANNULE' => 0, 'REPORTE' => 0, 'NON_HONORE' => 0
        ];
        foreach ($stmtStats->fetchAll(PDO::FETCH_ASSOC) as $row) {
            if (array_key_exists($row['statut'], $statsByStatut)) {
                $statsByStatut[$row['statut']] = (int)$row['nb'];
            }
        }

        $specialistes = $this->getSpecialistes();

        require_once __DIR__ . '/../views/specialiste/historique.php';
    }

    /** Enregistre un RDV spécialiste depuis l'accueil (POST JSON) */
    public function storeRdvAccueil(): void {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Méthode invalide']); exit;
        }

        $patient_id = (int)($_POST['patient_id']     ?? 0);
        $sp_id      = (int)($_POST['specialiste_id'] ?? 0);
        $date_rdv   = trim($_POST['date_rdv']         ?? '');
        $heure_rdv  = trim($_POST['heure_rdv']        ?? '') ?: null;
        $motif      = trim($_POST['motif']            ?? '') ?: null;

        if (!$patient_id || !$sp_id || !$date_rdv) {
            echo json_encode(['success' => false, 'message' => 'Champs obligatoires manquants']); exit;
        }

        // Déterminer la source selon le type_client du patient
        $source = $this->determineSource($patient_id);

        try {
            $ord = $this->db->prepare("
                SELECT COALESCE(MAX(ordre_passage), 0) + 1
                FROM rdv_specialistes
                WHERE specialiste_id = ? AND date_rdv = ? AND statut NOT IN ('ANNULE','REPORTE')
            ");
            $ord->execute([$sp_id, $date_rdv]);
            $ordre = (int)$ord->fetchColumn();

            $stmt = $this->db->prepare("
                INSERT INTO rdv_specialistes
                    (patient_id, specialiste_id, date_rdv, heure_rdv, motif,
                     statut, source, prescrit_par, ordre_passage)
                VALUES (?, ?, ?, ?, ?, 'EN_ATTENTE', ?, ?, ?)
            ");
            $stmt->execute([
                $patient_id, $sp_id, $date_rdv, $heure_rdv, $motif,
                $source,
                $_SESSION['user_id'] ?? null,
                $ordre
            ]);

            echo json_encode([
                'success' => true,
                'message' => 'RDV enregistré — ordre n°' . $ordre . ' (' . (self::SOURCE_LABELS[$source] ?? $source) . ')',
                'ordre'   => $ordre,
                'source'  => $source,
            ]);
        } catch (Exception $e) {
            error_log("storeRdvAccueil: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Erreur serveur : ' . $e->getMessage()]);
        }
        exit;
    }
}
