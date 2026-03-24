<?php
/* ============================================================================
FICHIER : app/controllers/LaboratoireController.php
CONTRÔLEUR DE GESTION DU LABORATOIRE (SIL)
============================================================================ */

require_once __DIR__ . '/UnifiedController.php';
require_once __DIR__ . '/../services/LaboratoireService.php';

class LaboratoireController extends UnifiedController {
    private $laboratoireService;
    private $db;

    public function __construct() {
        parent::__construct();
        $this->laboratoireService = new LaboratoireService();
        $this->db = (new Database())->getConnection();
    }

    /**
     * Dashboard principal du laboratoire
     */
    public function index() {
        $this->auth->requirePermission('laboratoire', 'read');

        $demandes = $this->laboratoireService->getDemandesEnAttente();
        $statistiques = $this->laboratoireService->getStatistiques();

        require_once __DIR__ . '/../views/laboratoire/dashboard.php';
    }

    /**
     * API : Liste des examens paramétrés (pour les listes déroulantes)
     */
    public function examensDisponibles() {
        header('Content-Type: application/json');
        echo json_encode($this->laboratoireService->getExamensDisponibles());
    }

    /**
     * Vue : Détails d'une demande pour prélèvement ou mise à jour statut
     */
    public function traiterDemande($id) {
        $this->auth->requirePermission('laboratoire', 'write');

        $demande = $this->laboratoireService->getDemandeComplete($id);

        if (!$demande) {
            header('Location: ' . BASE_URL . 'laboratoire?error=demande_introuvable');
            exit;
        }

        $examens = $this->laboratoireService->getExamensParDemande($id);
        require_once __DIR__ . '/../views/laboratoire/traitement.php';
    }

    /**
     * Vue : Formulaire de saisie des valeurs techniques
     */
    public function saisieResultats($demande_id) {
        $this->auth->requirePermission('laboratoire', 'write');

        $demande = $this->laboratoireService->getDemandeComplete($demande_id);
        $examens = $this->laboratoireService->getExamensParDemande($demande_id);

        if (!$demande) {
            header('Location: ' . BASE_URL . 'laboratoire?error=demande_introuvable');
            exit;
        }

        require_once __DIR__ . '/../views/laboratoire/saisie_resultats.php';
    }

    /**
     * Action : Enregistre les résultats techniques saisis
     */
    public function sauvegarderResultats() {
        $this->auth->requirePermission('laboratoire', 'write');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $result = $this->laboratoireService->sauvegarderResultats($_POST);

            if ($result['success']) {
                header('Location: ' . BASE_URL . 'laboratoire?success=resultats_sauvegardes');
            } else {
                $error_msg = urlencode($result['message'] ?? 'Erreur inconnue');
                header('Location: ' . BASE_URL . 'laboratoire/saisie-resultats/' . $_POST['demande_id'] . '?error=' . $error_msg);
            }
            exit;
        }
    }

    /**
     * Action : Validation finale par le biologiste
     */
    public function validerResultats() {
        $this->auth->requirePermission('laboratoire', 'admin');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $result = $this->laboratoireService->validerResultats($_POST['demande_id'], $_SESSION['user_id']);
            header('Content-Type: application/json');
            echo json_encode($result);
        }
    }

    /**
     * Action : Mettre à jour les statuts (ex: Prélèvement fait)
     */
    public function traiterExamens() {
        $this->auth->requirePermission('laboratoire', 'write');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $result = $this->laboratoireService->mettreAJourStatuts($_POST);

            if ($result['success']) {
                header('Location: ' . BASE_URL . 'laboratoire?success=statuts_mis_a_jour');
            } else {
                header('Location: ' . BASE_URL . 'laboratoire/traitement/' . $_POST['demande_id'] . '?error=' . urlencode($result['message']));
            }
            exit;
        }
    }

    /**
     * Vue : Impression des résultats pour le patient
     */
    public function imprimerResultats($demande_id) {
        $this->auth->requirePermission('laboratoire', 'read');

        $demande = $this->laboratoireService->getDemandeComplete($demande_id);
        $resultats = $this->laboratoireService->getResultatsParDemande($demande_id);
        require_once __DIR__ . '/../views/laboratoire/impression_resultats.php';
    }

    /**
     * API JSON : Création d'une demande groupée depuis le formulaire de consultation
     */
    public function creerDemandeDepuisConsultation() {
        $this->auth->requirePermission('consultations', 'write');
        header('Content-Type: application/json');

        // 1. Lire les données JSON envoyées par fetch()
        $input = json_decode(file_get_contents('php://input'), true);

        if (!$input || empty($input['examens'])) {
            echo json_encode(['success' => false, 'message' => 'Aucun examen sélectionné.']);
            return;
        }

        $patient_id = $input['patient_id'];
        $examens = $input['examens'];
        $medecin_id = $_SESSION['user_id'] ?? 1;

        try {
            $this->db->beginTransaction();

            // 2. Créer l'entrée principale (Demande)
            $stmtD = $this->db->prepare("INSERT INTO demandes_laboratoire (patient_id, medecin_id, statut, date_creation) VALUES (?, ?, 'EN_ATTENTE', NOW())");
            $stmtD->execute([$patient_id, $medecin_id]);
            $demande_id = $this->db->lastInsertId();

            // 3. Insérer chaque examen lié du catalogue
            $stmtE = $this->db->prepare("INSERT INTO demande_examens (demande_id, examen_id, urgent, a_jeun, instructions, statut) VALUES (?, ?, ?, ?, ?, 'EN_ATTENTE')");

            foreach ($examens as $ex) {
                $stmtE->execute([
                    $demande_id,
                    $ex['id'],
                    ($ex['urgent'] ?? false) ? 1 : 0,
                    ($ex['a_jeun'] ?? false) ? 1 : 0,
                    $ex['instructions'] ?? ''
                ]);
            }

            $this->db->commit();
            echo json_encode(['success' => true, 'demande_id' => $demande_id]);

        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Erreur Labo depuis Consult : " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Erreur technique: ' . $e->getMessage()]);
        }
    }

    /**
     * Action : Validation individuelle d'un prélèvement
     */
    public function validerPrelevement() {
        $this->auth->requirePermission('laboratoire', 'write');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $result = $this->laboratoireService->validerPrelevement(
                $_POST['examen_id'],
                $_SESSION['user_id']
            );
            header('Content-Type: application/json');
            echo json_encode($result);
        }
    }
}