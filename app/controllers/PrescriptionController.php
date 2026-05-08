<?php
/* ============================================================================
   FICHIER : app/controllers/PrescriptionController.php
   ============================================================================ */
require_once __DIR__ . '/../models/Prescription.php';
require_once __DIR__ . '/../models/Medicament.php';
require_once __DIR__ . '/../models/Patient.php';
require_once __DIR__ . '/../services/SignatureService.php';

class PrescriptionController {
    private $prescriptionModel;
    private $medicamentModel;
    private $patientModel;
    private $sigService;

    public function __construct() {
        $this->prescriptionModel = new Prescription();
        $this->medicamentModel   = new Medicament();
        $this->patientModel      = new Patient();
        $this->sigService        = new SignatureService();

        if (session_status() === PHP_SESSION_NONE) session_start();
    }

    /** Liste toutes les prescriptions */
    public function index() {
        $prescriptions = $this->prescriptionModel->getAll();
        require_once __DIR__ . '/../views/prescriptions/ordonnance.php';
    }

    /** Formulaire de création d'ordonnance */
    public function create() {
        $patient_id = $_GET['patient_id'] ?? null;
        if (!$patient_id) { header('Location: ' . BASE_URL . 'patients'); exit; }

        $patient    = $this->patientModel->getById($patient_id);
        $medicaments = $this->medicamentModel->getAll();

        if (!$patient) { header('Location: ' . BASE_URL . 'patients?error=patient_not_found'); exit; }

        require_once __DIR__ . '/../views/prescriptions/create.php';
    }

    /** Sauvegarde une nouvelle prescription */
    public function save() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . 'patients'); exit;
        }

        $patient_id      = $_POST['patient_id'] ?? null;
        $medicaments     = json_decode($_POST['medicaments'] ?? '[]', true);
        $recommandations = $_POST['notes'] ?? $_POST['recommandations'] ?? '';

        if (!$patient_id || empty($medicaments)) {
            header('Location: ' . BASE_URL . 'prescription/create?patient_id=' . $patient_id . '&error=invalid_data');
            exit;
        }

        $prescription_id = $this->prescriptionModel->create([
            'patient_id'      => $patient_id,
            'medecin_id'      => $_SESSION['user_id'] ?? 1,
            'consultation_id' => null,
            'medicaments'     => $medicaments,
            'recommandations' => $recommandations,
        ]);

        if ($prescription_id) {
            header('Location: ' . BASE_URL . 'prescription/print?id=' . $prescription_id . '&success=1');
        } else {
            header('Location: ' . BASE_URL . 'prescription/create?patient_id=' . $patient_id . '&error=save_failed');
        }
        exit;
    }

    /**
     * Vue impression / signature de l'ordonnance
     * Charge signature, cachet, numero_ordre et specialite du médecin
     */
    public function print() {
        $id = $_GET['id'] ?? null;
        if (!$id) { header('Location: ' . BASE_URL . 'patients'); exit; }

        $prescription = $this->prescriptionModel->getById($id);
        $medicaments  = $this->prescriptionModel->getMedicaments($id);
        $hopital      = $this->prescriptionModel->getHopitalSettings();

        if (!$prescription) {
            header('Location: ' . BASE_URL . 'patients?error=prescription_not_found'); exit;
        }

        // Normaliser recommandations
        if (!isset($prescription['recommandations'])) {
            $prescription['recommandations'] = $prescription['notes'] ?? '';
        }

        // Charger signature + cachet + infos médecin
        $sigInfo = $this->sigService->getMedecinSignatureInfo((int)$prescription['medecin_id']);
        $prescription = array_merge($prescription, $sigInfo);

        // Vérifier si déjà signé (pour afficher infos d'archive)
        $signatureDoc = $this->sigService->isDocumentSigned('ORDONNANCE', (int)$id);

        // Normaliser champs patient
        if (isset($prescription['nom'])    && !isset($prescription['patient_nom']))    $prescription['patient_nom']    = $prescription['nom'];
        if (isset($prescription['prenom']) && !isset($prescription['patient_prenom'])) $prescription['patient_prenom'] = $prescription['prenom'];

        // Normaliser médicaments
        $medicaments = array_map(function($m) {
            $m['medicament_nom'] = $m['nom_medicament'] ?? $m['nom'] ?? 'Médicament inconnu';
            $m['forme']          = $m['forme']    ?? '';
            $m['dosage']         = $m['dosage']   ?? '';
            $m['posologie']      = $m['posologie'] ?? '';
            $m['duree']          = $m['duree']    ?? '';
            return $m;
        }, $medicaments);

        require_once __DIR__ . '/../views/prescriptions/impression.php';
    }

    /**
     * API AJAX : signe l'ordonnance, l'enregistre dans documents_signes
     * et met à jour le statut + hash sur prescriptions
     */
    public function signerEtEnvoyer() {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']); return;
        }

        $id         = (int)($_POST['id'] ?? 0);
        $medecin_id = (int)($_SESSION['user_id'] ?? 0);

        if (!$id || !$medecin_id) {
            echo json_encode(['success' => false, 'message' => 'Données invalides']); return;
        }

        try {
            $db = (new Database())->getConnection();

            $stmt = $db->prepare("SELECT id, statut, medecin_id FROM prescriptions WHERE id = ?");
            $stmt->execute([$id]);
            $ord = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$ord) {
                echo json_encode(['success' => false, 'message' => 'Ordonnance introuvable']); return;
            }
            if ($ord['statut'] === 'SIGNEE') {
                echo json_encode(['success' => false, 'message' => 'Déjà signée']); return;
            }

            // Signer via le service (crée l'entrée dans documents_signes)
            $hash = $this->sigService->signDocument('ORDONNANCE', $id, $medecin_id);

            // Mettre à jour l'ordonnance avec le hash et le statut SIGNEE
            $db->prepare("
                UPDATE prescriptions
                SET statut = 'SIGNEE', signature_hash = ?, signed_at = NOW(), signe_par = ?
                WHERE id = ?
            ")->execute([$hash, $medecin_id, $id]);

            echo json_encode([
                'success'     => true,
                'hash_short'  => strtoupper(substr($hash, 0, 12)),
                'signed_at'   => date('d/m/Y H:i'),
            ]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * Archive des documents signés
     */
    public function archives() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: ' . BASE_URL . 'login'); exit;
        }

        $db         = (new Database())->getConnection();
        $medecin_id = (int)$_SESSION['user_id'];
        $role       = $_SESSION['user_role'] ?? '';

        $type_filtre = $_GET['type']    ?? 'ORDONNANCE';
        $date_filtre = $_GET['date']    ?? '';
        $patient_q   = $_GET['patient'] ?? '';

        $where  = "o.statut = 'SIGNEE'";
        $params = [];

        // Cloisonnement : les médecins ne voient que leurs ordonnances
        if (!in_array($role, ['ADMIN', 'DIRECTEUR'])) {
            $where .= " AND o.medecin_id = ?";
            $params[] = $medecin_id;
        }
        if ($date_filtre) {
            $where .= " AND DATE(COALESCE(o.signed_at, o.date_creation)) = ?";
            $params[] = $date_filtre;
        }
        if ($patient_q) {
            $where .= " AND (p.nom LIKE ? OR p.prenom LIKE ? OR p.dossier_numero LIKE ?)";
            $params[] = "%$patient_q%";
            $params[] = "%$patient_q%";
            $params[] = "%$patient_q%";
        }

        $stmt = $db->prepare("
            SELECT
                o.id, o.date_prescription as date_creation, o.signed_at, o.signature_hash, o.statut,
                p.nom  AS patient_nom, p.prenom AS patient_prenom, p.dossier_numero,
                u.nom  AS medecin_nom, u.prenom AS medecin_prenom, u.specialite,
                COUNT(lp.id) AS nb_medicaments
            FROM prescriptions o
            JOIN patients p   ON o.patient_id  = p.id
            JOIN users u      ON o.medecin_id  = u.id
            LEFT JOIN lignes_prescription lp ON lp.prescription_id = o.id
            WHERE $where
            GROUP BY o.id
            ORDER BY COALESCE(o.signed_at, o.date_prescription) DESC
            LIMIT 200
        ");
        $stmt->execute($params);
        $documents = $stmt->fetchAll(PDO::FETCH_ASSOC);

        require_once __DIR__ . '/../views/prescriptions/archives.php';
    }

    /** Vérification du stock AJAX */
    public function checkStock() {
        header('Content-Type: application/json');
        $medicament_id = $_POST['medicament_id'] ?? null;
        if ($medicament_id) {
            $stock = $this->medicamentModel->getStock($medicament_id);
            echo json_encode(['disponible' => $stock > 0, 'quantite' => $stock]);
        }
    }

    /** Historique des prescriptions d'un patient (AJAX) */
    public function history() {
        header('Content-Type: application/json');
        $patient_id = $_GET['patient_id'] ?? null;
        if ($patient_id) {
            echo json_encode($this->prescriptionModel->getByPatient($patient_id));
        }
    }
}
