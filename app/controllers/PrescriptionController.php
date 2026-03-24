<?php
require_once __DIR__ . '/../models/Prescription.php';
require_once __DIR__ . '/../models/Medicament.php';
require_once __DIR__ . '/../models/Patient.php';

class PrescriptionController {
    private $prescriptionModel;
    private $medicamentModel;
    private $patientModel;

    public function __construct() {
        $this->prescriptionModel = new Prescription();
        $this->medicamentModel = new Medicament();
        $this->patientModel = new Patient();

        // Démarrage de session si pas déjà fait
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * Liste toutes les prescriptions
     */
    public function index() {
        $prescriptions = $this->prescriptionModel->getAll();
        require_once __DIR__ . '/../views/prescriptions/ordonnance.php';
    }

    /**
     * Affiche le formulaire de création d'ordonnance
     */
    public function create() {
        $patient_id = $_GET['patient_id'] ?? null;

        if (!$patient_id) {
            header('Location: ' . BASE_URL . 'patients');
            exit;
        }

        $patient = $this->patientModel->getById($patient_id);
        $medicaments = $this->medicamentModel->getAll();

        if (!$patient) {
            header('Location: ' . BASE_URL . 'patients?error=patient_not_found');
            exit;
        }

        require_once __DIR__ . '/../views/prescriptions/create.php';
    }

    /**
     * Sauvegarde la prescription en base de données
     */
    public function save() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . 'patients');
            exit;
        }

        $patient_id = $_POST['patient_id'] ?? null;
        $medicaments = json_decode($_POST['medicaments'] ?? '[]', true);

        // On récupère les notes/recommandations
        $recommandations = $_POST['notes'] ?? $_POST['recommandations'] ?? '';

        if (!$patient_id || empty($medicaments)) {
            header('Location: ' . BASE_URL . 'prescription/create?patient_id=' . $patient_id . '&error=invalid_data');
            exit;
        }

        $data = [
            'patient_id' => $patient_id,
            'medecin_id' => $_SESSION['user_id'] ?? 1,
            'consultation_id' => null,
            'date_prescription' => date('Y-m-d H:i:s'),
            'medicaments' => $medicaments,
            'recommandations' => $recommandations // Nom de colonne standard
        ];

        $prescription_id = $this->prescriptionModel->create($data);

        if ($prescription_id) {
            header('Location: ' . BASE_URL . 'prescription/print?id=' . $prescription_id . '&success=1');
            exit;
        } else {
            header('Location: ' . BASE_URL . 'prescription/create?patient_id=' . $patient_id . '&error=save_failed');
            exit;
        }
    }

    /**
     * Préparation des données pour la vue d'impression/signature
     */
    public function print() {
        $id = $_GET['id'] ?? null;
        if (!$id) {
            header('Location: ' . BASE_URL . 'patients');
            exit;
        }

        // On récupère les détails de l'ordonnance
        $prescription = $this->prescriptionModel->getById($id);
        $medicaments = $this->prescriptionModel->getMedicaments($id);

        if (!$prescription) {
            header('Location: ' . BASE_URL . 'patients?error=prescription_not_found');
            exit;
        }

        // --- CORRECTION DU WARNING : RECOMMANDATIONS ---
        // On s'assure que la clé existe pour la vue impression.php
        if (!isset($prescription['recommandations'])) {
            $prescription['recommandations'] = $prescription['notes'] ?? '';
        }

        // --- INJECTION DES DONNÉES DE SIGNATURE ---
        // On va chercher les chemins de signature/cachet du médecin qui a créé l'ordonnance
        $db = (new Database())->getConnection();
        $stmt = $db->prepare("SELECT signature_path, cachet_path FROM users WHERE id = ?");
        $stmt->execute([$prescription['medecin_id']]);
        $doctorFiles = $stmt->fetch(PDO::FETCH_ASSOC);

        $prescription['signature_path'] = $doctorFiles['signature_path'] ?? null;
        $prescription['cachet_path'] = $doctorFiles['cachet_path'] ?? null;

        // Normalisation Patient (pour compatibilité vue)
        if (isset($prescription['nom']) && !isset($prescription['patient_nom'])) {
            $prescription['patient_nom'] = $prescription['nom'];
        }
        if (isset($prescription['prenom']) && !isset($prescription['patient_prenom'])) {
            $prescription['patient_prenom'] = $prescription['prenom'];
        }

        // Normalisation des médicaments pour la vue
        $normalizedMedicaments = [];
        foreach ($medicaments as $m) {
            $m['medicament_nom'] = $m['nom_medicament'] ?? $m['nom'] ?? 'Médicament inconnu';
            $m['forme'] = $m['forme'] ?? '';
            $m['dosage'] = $m['dosage'] ?? '';
            $m['posologie'] = $m['posologie'] ?? '';
            $m['duree'] = $m['duree'] ?? '';
            $normalizedMedicaments[] = $m;
        }
        $medicaments = $normalizedMedicaments;

        require_once __DIR__ . '/../views/prescriptions/impression.php';
    }

    /**
     * API AJAX pour signer numériquement l'ordonnance et l'envoyer à la pharmacie
     */
    public function signerEtEnvoyer() {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
            return;
        }

        $id = $_POST['id'] ?? null;

        if ($id) {
            $db = (new Database())->getConnection();

            // On change le statut à 'SIGNEE'
            $stmt = $db->prepare("UPDATE ordonnances_pharmacie SET statut = 'SIGNEE' WHERE id = ?");
            if ($stmt->execute([$id])) {
                // Optionnel : Envoyer une notification ici
                echo json_encode(['success' => true]);
                return;
            }
        }
        echo json_encode(['success' => false, 'message' => 'Erreur lors de la signature électronique']);
    }

    /**
     * Vérification du stock via AJAX
     */
    public function checkStock() {
        header('Content-Type: application/json');
        $medicament_id = $_POST['medicament_id'] ?? null;
        if ($medicament_id) {
            $stock = $this->medicamentModel->getStock($medicament_id);
            echo json_encode(['disponible' => $stock > 0, 'quantite' => $stock]);
        }
    }

    /**
     * Historique des prescriptions d'un patient
     */
    public function history() {
        header('Content-Type: application/json');
        $patient_id = $_GET['patient_id'] ?? null;
        if ($patient_id) {
            $prescriptions = $this->prescriptionModel->getByPatient($patient_id);
            echo json_encode($prescriptions);
        }
    }
}