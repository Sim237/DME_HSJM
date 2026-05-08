<?php
/* ============================================================================
FICHIER : app/controllers/SpecialisteController.php
CONTRÔLEUR MODULE SPÉCIALISTES
============================================================================ */

require_once __DIR__ . '/UnifiedController.php';

class SpecialisteController extends UnifiedController {

    public function __construct() {
        parent::__construct();
    }

    /**
     * Retourne la liste des spécialistes actifs (JSON)
     */
    public function getSpecialistes() {
        header('Content-Type: application/json');
        $db = (new Database())->getConnection();
        $stmt = $db->query("
            SELECT id, specialite, nom, prenom, quota_max, jours_consultation
            FROM specialistes
            WHERE actif = 1
            ORDER BY specialite
        ");
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        exit;
    }

    /**
     * Vérifie le quota disponible pour un spécialiste à une date (JSON)
     */
    public function checkQuota() {
        header('Content-Type: application/json');
        $db    = (new Database())->getConnection();
        $spId  = (int)($_GET['specialiste_id'] ?? 0);
        $date  = $_GET['date'] ?? '';

        if (!$spId || !$date) {
            echo json_encode(['error' => 'Paramètres manquants']); exit;
        }

        $sp = $db->prepare("SELECT quota_max FROM specialistes WHERE id = ? AND actif = 1");
        $sp->execute([$spId]);
        $specialiste = $sp->fetch(PDO::FETCH_ASSOC);
        if (!$specialiste) { echo json_encode(['error' => 'Spécialiste introuvable']); exit; }

        $rdv = $db->prepare("
            SELECT COUNT(*) FROM rdv_specialistes
            WHERE specialiste_id = ? AND date_rdv = ?
            AND statut NOT IN ('ANNULE','REPORTE')
        ");
        $rdv->execute([$spId, $date]);
        $pris = (int)$rdv->fetchColumn();

        echo json_encode([
            'pris'      => $pris,
            'quota_max' => (int)$specialiste['quota_max'],
            'disponible'=> max(0, (int)$specialiste['quota_max'] - $pris),
            'complet'   => $pris >= (int)$specialiste['quota_max'],
        ]);
        exit;
    }

    /**
     * Enregistre un RDV spécialiste depuis l'accueil (POST)
     */
    public function storeRdvAccueil() {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Méthode invalide']); exit;
        }

        $db         = (new Database())->getConnection();
        $patient_id = (int)($_POST['patient_id']     ?? 0);
        $sp_id      = (int)($_POST['specialiste_id'] ?? 0);
        $date_rdv   = trim($_POST['date_rdv']         ?? '');
        $heure_rdv  = trim($_POST['heure_rdv']        ?? '') ?: null;
        $motif      = trim($_POST['motif']            ?? '') ?: null;

        if (!$patient_id || !$sp_id || !$date_rdv) {
            echo json_encode(['success' => false, 'message' => 'Champs obligatoires manquants']); exit;
        }

        try {
            // Calcul du prochain ordre de passage
            $ord = $db->prepare("
                SELECT COALESCE(MAX(ordre_passage), 0) + 1
                FROM rdv_specialistes
                WHERE specialiste_id = ? AND date_rdv = ?
            ");
            $ord->execute([$sp_id, $date_rdv]);
            $ordre = (int)$ord->fetchColumn();

            $stmt = $db->prepare("
                INSERT INTO rdv_specialistes
                    (patient_id, specialiste_id, date_rdv, heure_rdv, motif,
                     statut, source, prescrit_par, ordre_passage)
                VALUES (?, ?, ?, ?, ?, 'EN_ATTENTE', 'STANDARD', ?, ?)
            ");
            $stmt->execute([
                $patient_id, $sp_id, $date_rdv, $heure_rdv, $motif,
                $_SESSION['user_id'] ?? null,
                $ordre
            ]);

            echo json_encode([
                'success' => true,
                'message' => 'RDV enregistré — ordre n°' . $ordre,
                'ordre'   => $ordre,
            ]);
        } catch (Exception $e) {
            error_log("SpecialisteController::storeRdvAccueil — " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Erreur serveur : ' . $e->getMessage()]);
        }
        exit;
    }
}
