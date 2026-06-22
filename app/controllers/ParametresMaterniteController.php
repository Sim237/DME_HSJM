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
 * FICHIER : app/controllers/ParametresMaterniteController.php
 * Bureau de prise de paramètres — Circuit Maternité
 * ============================================================================ */

require_once __DIR__ . '/UnifiedController.php';

class ParametresMaterniteController extends UnifiedController {

    public function __construct() {
        parent::__construct();
    }

    /* ──────────────────────────────────────────────────────────────────
     * GET  parametres-maternite
     * Dashboard : file d'attente maternité du jour
     * ────────────────────────────────────────────────────────────────── */
    public function index(): void {
        $db     = (new Database())->getConnection();
        $userId = $_SESSION['user_id'] ?? 0;

        // Nettoyage des files > 24h
        require_once __DIR__ . '/../services/WorkflowResetService.php';
        (new WorkflowResetService($db))->cleanWaitingListsIfStale();

        // Patients en attente côté maternité (aujourd'hui)
        $stmt = $db->prepare("
            SELECT * FROM patients
             WHERE statut_parcours = 'PARAMETRES_MATERNITE'
               AND DATE(COALESCE(date_mise_en_parametres, updated_at, created_at)) = CURDATE()
             ORDER BY numero_ordre ASC
        ");
        $stmt->execute();
        $patients_attente = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // Patients déjà traités aujourd'hui par cet infirmier
        $stmtR = $db->prepare("
            SELECT p.*, pv.date_mesure
              FROM patients p
              JOIN patient_parametres pv ON p.id = pv.patient_id
             WHERE pv.user_id = ?
               AND DATE(pv.date_mesure) = CURDATE()
             ORDER BY pv.date_mesure DESC
        ");
        $stmtR->execute([$userId]);
        $patients_reçus = $stmtR->fetchAll(\PDO::FETCH_ASSOC);

        require_once __DIR__ . '/../views/parametres/dashboard_maternite.php';
    }

    /* ──────────────────────────────────────────────────────────────────
     * POST parametres-maternite/save
     * Enregistre TA / Temp / SpO2 / Poids et envoie en consultation
     * ────────────────────────────────────────────────────────────────── */
    public function save(): void {
        $db = (new Database())->getConnection();

        try {
            $db->beginTransaction();

            $patient_id = (int)($_POST['patient_id'] ?? 0);
            if (!$patient_id) throw new \Exception('patient_id manquant');

            $num = fn($k, $type = 'float') =>
                (isset($_POST[$k]) && $_POST[$k] !== '')
                    ? ($type === 'int' ? (int)$_POST[$k] : (float)str_replace(',', '.', $_POST[$k]))
                    : null;

            $temp  = ($v = $num('temp'))  !== null ? round($v, 1) : null;
            $sys   = $num('sys',  'int');
            $dia   = $num('dia',  'int');
            $spo2  = ($v = $num('spo2'))  !== null ? round($v, 2) : null;
            $poids = ($v = $num('poids')) !== null ? round($v, 2) : null;
            $motif = trim($_POST['motif'] ?? '');

            // 1. Enregistrer les paramètres vitaux
            $db->prepare("
                INSERT INTO patient_parametres
                    (patient_id, user_id, temperature,
                     pression_arterielle_systolique, pression_arterielle_diastolique,
                     poids, saturation_oxygene, motif_consultation, date_mesure)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ")->execute([
                $patient_id, $_SESSION['user_id'] ?? 0,
                $temp, $sys, $dia, $poids, $spo2, $motif
            ]);

            // 2. Trouver (ou créer) le service Maternité
            $materniteId = $this->getOrCreateMaterniteService($db);

            // 3. Envoyer en file d'attente consultation
            //    medecin_id = NULL → tous les médecins du service maternité voient la patiente
            $db->prepare("
                UPDATE patients
                   SET statut_parcours = 'ATTENTE_CONSULTATION',
                       service_id      = ?,
                       medecin_id      = NULL
                 WHERE id = ?
            ")->execute([$materniteId, $patient_id]);

            // 4. Notifier tous les médecins/gynécos/sages-femmes du service
            $stmtP = $db->prepare("SELECT nom, prenom FROM patients WHERE id = ?");
            $stmtP->execute([$patient_id]);
            $pat    = $stmtP->fetch(\PDO::FETCH_ASSOC) ?: [];
            $nomPat = trim(($pat['nom'] ?? '') . ' ' . ($pat['prenom'] ?? ''));

            $stmtMeds = $db->prepare("
                SELECT id FROM users
                 WHERE service_id = ?
                   AND role IN ('MEDECIN','GYNECO','SAGE_FEMME','GENERALISTE','CHIRURGIEN')
                   AND actif = 1
            ");
            $stmtMeds->execute([$materniteId]);
            $notifStmt = $db->prepare("
                INSERT INTO notifications
                    (user_id, type, category, title, message, link, icon, priority)
                VALUES (?, 'info', 'CONSULTATION', ?, ?, ?, 'bi-person-heart-fill', 'normal')
            ");
            foreach ($stmtMeds->fetchAll(\PDO::FETCH_COLUMN) as $mid) {
                $notifStmt->execute([
                    $mid,
                    "Patiente maternité en attente",
                    "La patiente $nomPat est prête pour la consultation — maternité.",
                    "dashboard"
                ]);
            }

            $db->commit();
            header('Location: ' . BASE_URL . 'parametres-maternite?success=1');
            exit;

        } catch (\Exception $e) {
            $db->rollBack();
            error_log("ParametresMaterniteController::save : " . $e->getMessage());
            die("Erreur technique : " . htmlspecialchars($e->getMessage()));
        }
    }

    /* ──────────────────────────────────────────────────────────────────
     * Helper : trouve ou crée le service Maternité
     * ────────────────────────────────────────────────────────────────── */
    private function getOrCreateMaterniteService(\PDO $db): int {
        $stmt = $db->query(
            "SELECT id FROM services WHERE nom_service LIKE '%aternit%' LIMIT 1"
        );
        $id = $stmt->fetchColumn();
        if (!$id) {
            $db->exec("INSERT INTO services (nom_service, categorie) VALUES ('Maternité', 'CLINIQUE')");
            $id = (int)$db->lastInsertId();
        }
        return (int)$id;
    }
}
