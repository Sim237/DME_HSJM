<?php
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

        // Le bureau PHP utilise la même logique pair/impair mais ne prend que les patients PHP
        $bureauId    = $_SESSION['bureau_id'] ?? 1;
        $filtreLogic = ($bureauId == 1) ? "(numero_ordre % 2 = 0)" : "(numero_ordre % 2 <> 0)";

        // Nettoyage automatique : patients PHP restés en PARAMETRES depuis un jour précédent → SORTI
        $db->prepare("
            UPDATE patients
            SET statut_parcours = 'SORTI'
            WHERE statut_parcours = 'PARAMETRES'
              AND circuit = 'PHP'
              AND date_mise_en_parametres IS NOT NULL
              AND DATE(date_mise_en_parametres) < CURDATE()
        ")->execute();

        $patients_attente = $db->prepare("
            SELECT * FROM patients
            WHERE statut_parcours = 'PARAMETRES'
              AND circuit = 'PHP'
              AND DATE(date_mise_en_parametres) = CURDATE()
              AND $filtreLogic
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

        $bureauLabel = ($bureauId == 1) ? "BUREAU PHP 1 (Pairs)" : "BUREAU PHP 2 (Impairs)";
        $bureauTheme = "purple";

        require_once __DIR__ . '/../views/parametres_php/dashboard.php';
    }

    public function save() {
        $db = (new Database())->getConnection();

        try {
            $db->beginTransaction();

            $patient_id = (int)$_POST['patient_id'];
            $temp       = $_POST['temp']    ?? null;
            $sys        = $_POST['sys']     ?? null;
            $dia        = $_POST['dia']     ?? null;
            $pouls      = $_POST['pouls']   ?? null;
            $spo2       = $_POST['spo2']    ?? null;
            $poids      = $_POST['poids']   ?? null;
            $taille     = $_POST['taille']  ?? null;
            $motif      = $_POST['motif']   ?? '';
            $service_id = (int)$_POST['service_id'];
            $medecin_id = (int)$_POST['medecin_id'];

            // Enregistrement des constantes vitales
            $db->prepare("
                INSERT INTO patient_parametres (
                    patient_id, user_id, temperature,
                    pression_arterielle_systolique, pression_arterielle_diastolique,
                    frequence_cardiaque, poids, taille, saturation_oxygene,
                    motif_consultation, date_mesure
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ")->execute([
                $patient_id, $_SESSION['user_id'], $temp,
                $sys, $dia, $pouls, $poids, $taille, $spo2, $motif
            ]);

            // Mise à jour du patient PHP vers la consultation externe PHP
            $db->prepare("
                UPDATE patients
                SET statut_parcours = 'ATTENTE_CONSULTATION',
                    service_id = ?,
                    medecin_id = ?
                WHERE id = ? AND circuit = 'PHP'
            ")->execute([$service_id, $medecin_id, $patient_id]);

            $db->commit();
            header('Location: ' . BASE_URL . 'parametres-php?success=1');
            exit;

        } catch (Exception $e) {
            $db->rollBack();
            error_log("ParametresPhpController::save : " . $e->getMessage());
            header('Location: ' . BASE_URL . 'parametres-php?error=1');
            exit;
        }
    }
}
