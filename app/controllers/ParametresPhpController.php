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
   FICHIER : app/controllers/ParametresPhpController.php
   BUREAU DE PARAMÈTRES PHP — Circuit dédié
   ============================================================================ */

require_once __DIR__ . '/UnifiedController.php';

class ParametresPhpController extends UnifiedController {

    public function __construct() {
        parent::__construct();
    }

    public function index() {
        $this->auth->requirePermission('parametres', 'read');

        $db = (new Database())->getConnection();
        $userId = $_SESSION['user_id'];

        // Nettoyage automatique : patients en file PARAMETRES depuis > 24h sont retirés
        require_once __DIR__ . '/../services/WorkflowResetService.php';
        (new WorkflowResetService($db))->cleanWaitingListsIfStale();

        // Bureau unique — tous les patients PHP en attente de paramètres
        // (filtrage strict par jour : on ne montre que les patients du jour)
        $patients_attente = $db->prepare("
            SELECT * FROM patients
            WHERE statut_parcours = 'PARAMETRES'
              AND circuit = 'PHP'
              AND DATE(COALESCE(date_mise_en_parametres, updated_at, created_at)) = CURDATE()
            ORDER BY numero_ordre ASC
        ");
        $patients_attente->execute();
        $patients_attente = $patients_attente->fetchAll(PDO::FETCH_ASSOC);

        $stmt_recus = $db->prepare("
            SELECT p.*, pv.date_mesure
            FROM patients p
            JOIN patient_parametres pv ON p.id = pv.patient_id
            WHERE pv.user_id = ?
              AND p.circuit = 'PHP'
              AND DATE(pv.date_mesure) = CURDATE()
            ORDER BY pv.date_mesure DESC
        ");
        $stmt_recus->execute([$userId]);
        $patients_recus = $stmt_recus->fetchAll(PDO::FETCH_ASSOC);

        $bureauLabel = "BUREAU PARAMÈTRES PHP";
        $bureauTheme = "purple";

        require_once __DIR__ . '/../views/parametres_php/dashboard.php';
    }

    public function save() {
        $db = (new Database())->getConnection();

        try {
            $db->beginTransaction();

            // ── Validation des champs obligatoires ──
            $patient_id = (int)($_POST['patient_id'] ?? 0);
            $motif      = trim($_POST['motif']   ?? '');
            $service_id = !empty($_POST['service_id']) ? (int)$_POST['service_id'] : null;
            $medecin_id = !empty($_POST['medecin_id']) ? (int)$_POST['medecin_id'] : null;

            if ($patient_id <= 0) {
                throw new Exception("patient_id manquant ou invalide (reçu: " . ($_POST['patient_id'] ?? 'vide') . ")");
            }
            if ($motif === '') {
                throw new Exception("Le motif de consultation est obligatoire");
            }
            if (!$service_id) {
                throw new Exception("Le service de destination est obligatoire");
            }
            if (!$medecin_id) {
                throw new Exception("Le médecin est obligatoire");
            }

            // ── Constantes vitales (toutes optionnelles) ──
            $temp   = $_POST['temp']   !== '' ? $_POST['temp']   : null;
            $sys    = $_POST['sys']    !== '' ? $_POST['sys']    : null;
            $dia    = $_POST['dia']    !== '' ? $_POST['dia']    : null;
            $pouls  = $_POST['pouls']  !== '' ? $_POST['pouls']  : null;
            $spo2   = $_POST['spo2']   !== '' ? $_POST['spo2']   : null;
            $poids  = $_POST['poids']  !== '' ? $_POST['poids']  : null;
            $taille = $_POST['taille'] !== '' ? $_POST['taille'] : null;

            // ── 1. Enregistrement des constantes vitales ──
            $stmtP = $db->prepare("
                INSERT INTO patient_parametres (
                    patient_id, user_id, temperature,
                    pression_arterielle_systolique, pression_arterielle_diastolique,
                    frequence_cardiaque, poids, taille, saturation_oxygene,
                    motif_consultation, date_mesure
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            $stmtP->execute([
                $patient_id, $_SESSION['user_id'],
                $temp, $sys, $dia, $pouls, $poids, $taille, $spo2, $motif
            ]);

            // ── 2. Passage en consultation (filtre uniquement sur id pour robustesse) ──
            $stmtU = $db->prepare("
                UPDATE patients
                SET statut_parcours = 'ATTENTE_CONSULTATION',
                    service_id      = ?,
                    medecin_id      = ?
                WHERE id = ?
                  AND statut_parcours IN ('PARAMETRES','ATTENTE_CONSULTATION')
            ");
            $stmtU->execute([$service_id, $medecin_id, $patient_id]);

            if ($stmtU->rowCount() === 0) {
                // Diagnostique : retrouver le vrai statut du patient
                $check = $db->prepare("SELECT id, nom, statut_parcours, circuit FROM patients WHERE id = ?");
                $check->execute([$patient_id]);
                $row = $check->fetch(PDO::FETCH_ASSOC);
                throw new Exception(
                    "UPDATE 0 lignes affectées — patient #{$patient_id} : " .
                    ($row ? "statut={$row['statut_parcours']} circuit={$row['circuit']}" : "introuvable")
                );
            }

            $db->commit();
            header('Location: ' . BASE_URL . 'parametres-php?success=1');
            exit;

        } catch (Exception $e) {
            $db->rollBack();
            $msg = $e->getMessage();
            error_log("ParametresPhpController::save : " . $msg);
            $_SESSION['php_save_error'] = $msg;
            header('Location: ' . BASE_URL . 'parametres-php?error=1');
            exit;
        }
    }
}
