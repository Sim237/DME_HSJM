<?php
/* ============================================================================
FICHIER : app/controllers/ConsultationController.php
============================================================================ */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../models/Consultation.php';
require_once __DIR__ . '/../models/Patient.php';

class ConsultationController {
    private $db;
    private $consultationModel;
    private $patientModel;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->consultationModel = new Consultation();
        $this->patientModel = new Patient();

        // Démarrage de session si pas déjà fait (pour stocker les étapes temporaires)
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * Page d'accueil des consultations (Redirige vers la sélection)
     */
    public function index() {
        $this->selection();
    }

    /**
     * Étape 1 : Sélection du patient
     */
    public function selection() {
        // Vue : selection_patient.php
        require_once __DIR__ . '/../views/consultations/selection_patient.php';
    }

    /**
     * Méthode AJAX pour la barre de recherche
     */
    public function searchPatients() {
        $query = $_GET['q'] ?? '';
        header('Content-Type: application/json');

        if (strlen($query) < 2) {
            echo json_encode([]);
            return;
        }

        $patients = $this->patientModel->search($query);
        echo json_encode($patients);
    }

    /**
     * Affichage du dossier patient avant consultation
     */
    public function dossierPatient($id) {
        $patient = $this->patientModel->getById($id);

        if (!$patient) {
            header('Location: ' . BASE_URL . 'consultation?error=patient_not_found');
            exit;
        }

        // On charge aussi les paramètres vitaux récents si possible
        if(method_exists($this->patientModel, 'getParametres')) {
            $patient['parametres'] = $this->patientModel->getParametres($id, 1)[0] ?? null;
        }

        // On charge les antécédents (simulé ici, à adapter selon votre modèle)
        $patient['antecedents'] = []; // À remplir via le modèle si existant

        // Vue : dossier_patient.php
        require_once __DIR__ . '/../views/consultations/dossier_patient.php';
    }

    /**
     * Traitement du démarrage depuis le dossier patient (POST)
     */
    public function commencerConsultation() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $patient_id = $_POST['patient_id'];
            $type = strtoupper($_POST['type_consultation']); // INTERNE ou EXTERNE

            // Redirection vers le formulaire étape 1
            $url = BASE_URL . "consultation/formulaire?patient_id=$patient_id&type=$type&etape=1";
            header("Location: $url");
            exit;
        }
    }

    /**
     * Étape intermédiaire : Choix du type (si on ne passe pas par le dossier)
     */
    public function choixType() {
        $patient_id = $_GET['patient_id'] ?? null;
        if (!$patient_id) {
            header('Location: ' . BASE_URL . 'consultation');
            exit;
        }

        $patient = $this->patientModel->getById($patient_id);

        // Vue : type_consultation.php
        require_once __DIR__ . '/../views/consultations/type_consultation.php';
    }

   /**
 * Gère l'affichage des 7 étapes du formulaire de consultation
 * URL : consultation/formulaire?patient_id=X&type=EXTERNE&etape=Y
 */
public function formulaire() {
    // 1. Récupération et sécurisation des paramètres URL
    $patient_id = isset($_GET['patient_id']) ? (int)$_GET['patient_id'] : null;
    $etape = isset($_GET['etape']) ? (int)$_GET['etape'] : 1;
    $type = isset($_GET['type']) ? strtoupper($_GET['type']) : 'EXTERNE';

    // 2. Vérification de la présence du patient
    if (!$patient_id) {
        header('Location: ' . BASE_URL . 'consultation?error=patient_manquant');
        exit;
    }

    // 3. Récupération des informations du patient via le modèle
    $patient = $this->patientModel->getById($patient_id);
    if (!$patient) {
        header('Location: ' . BASE_URL . 'consultation?error=patient_introuvable');
        exit;
    }

    // 4. RÉCUPÉRATION DES DERNIÈRES CONSTANTES (Logique de liaison Paramètres -> Médecin)
    // On va chercher le tout dernier relevé dans la table patient_parametres
    $queryVitals = "SELECT * FROM patient_parametres
                    WHERE patient_id = :pid
                    ORDER BY date_mesure DESC LIMIT 1";
    $stmtV = $this->db->prepare($queryVitals);
    $stmtV->execute([':pid' => $patient_id]);
    $last_vitals = $stmtV->fetch(PDO::FETCH_ASSOC);

    // 5. GESTION DES DONNÉES TEMPORAIRES (Brouillon de session)
    // On récupère ce que le médecin a déjà saisi s'il navigue entre les étapes
    if (session_status() === PHP_SESSION_NONE) { session_start(); }
    $consultation_data = $_SESSION['consultation_temp'] ?? [];

    // 6. ROUTAGE DYNAMIQUE DES VUES (Switch 7 étapes)
    $view_directory = __DIR__ . '/../views/consultations/formulaire/';
    $view_file = '';

    switch ($etape) {
        case 1:
            $view_file = 'etape1_anamnese.php';
            break;
        case 2:
            $view_file = 'etape2_examen.php';
            break;
        case 3:
            $view_file = 'etape3_hypotheses.php';
            break;
        case 4:
            // Pour l'étape 4, on peut avoir besoin de l'historique des examens
            $stmtEx = $this->db->prepare("SELECT * FROM examens WHERE patient_id = ? ORDER BY date_demande DESC LIMIT 5");
            $stmtEx->execute([$patient_id]);
            $historique_examens = $stmtEx->fetchAll(PDO::FETCH_ASSOC);
            $view_file = 'etape4_bilans.php';
            break;
        case 5:
            $view_file = 'etape5_traitement.php';
            break;
        case 6:
            $view_file = 'etape6_surveillance.php';
            break;
        case 7:
            $view_file = 'etape7_suivi.php';
            break;
        default:
            $view_file = 'etape1_anamnese.php';
            $etape = 1;
    }

    // 7. Chargement de la vue
    // Toutes les variables définies ici ($patient, $last_vitals, $consultation_data, etc.)
    // seront accessibles directement dans le fichier PHP inclus.
    if (file_exists($view_directory . $view_file)) {
        require_once $view_directory . $view_file;
    } else {
        die("Erreur critique : Le fichier de vue " . htmlspecialchars($view_file) . " est introuvable.");
    }
}

    /**
     * Sauvegarde d'une étape et passage à la suivante
     */
    public function sauvegarderEtape() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $etape_actuelle = $_POST['etape_actuelle'] ?? 1;
        $patient_id = $_POST['patient_id'] ?? null;
        $type = $_POST['type'] ?? 'EXTERNE';

        if (empty($patient_id)) {
            die("Erreur : L'identifiant du patient a été perdu lors de la sauvegarde. Veuillez recommencer l'étape.");
        }

        if (!isset($_SESSION['consultation_temp'])) {
            $_SESSION['consultation_temp'] = [];
        }

        // Fusionner les nouvelles données dans la session
        $_SESSION['consultation_temp'] = array_merge($_SESSION['consultation_temp'], $_POST);

        // Préparer les données communes
        $data = $_SESSION['consultation_temp'];
        $data['medecin_id'] = $_SESSION['user_id'] ?? 1;
        $data['date_consultation'] = $data['date_consultation'] ?? date('Y-m-d H:i:s');

        // --- ÉTAPE 4 : BILANS (LABORATOIRE) ---
        if ($etape_actuelle == 4 && !empty($_POST['examens'])) {
            require_once __DIR__ . '/../services/LaboratoireService.php';
            $laboratoireService = new LaboratoireService();

            // ANTI-DOUBLON : On crée ou on met à jour
            if (!isset($_SESSION['consultation_temp']['consultation_id'])) {
                $consultation_id = $this->consultationModel->create($data);
                $_SESSION['consultation_temp']['consultation_id'] = $consultation_id; // ON SAUVEGARDE L'ID
            } else {
                $consultation_id = $_SESSION['consultation_temp']['consultation_id'];
                $this->consultationModel->update($consultation_id, $data);
            }

            if ($consultation_id) {
                $laboratoireService->creerDemandeExamens($consultation_id, $_POST['examens']);
            }
        }

        // --- ÉTAPE 5 : TRAITEMENT (PHARMACIE) ---
        if ($etape_actuelle == 5 && !empty($_POST['medicaments'])) {
            require_once __DIR__ . '/../services/PharmacieService.php';
            $pharmacieService = new PharmacieService();

            // ANTI-DOUBLON : On utilise l'ID existant (de l'étape 4) ou on crée
            if (!isset($_SESSION['consultation_temp']['consultation_id'])) {
                $consultation_id = $this->consultationModel->create($data);
                $_SESSION['consultation_temp']['consultation_id'] = $consultation_id;
            } else {
                $consultation_id = $_SESSION['consultation_temp']['consultation_id'];
                $this->consultationModel->update($consultation_id, $data);
            }

            if ($consultation_id) {
                $pharmacieService->creerOrdonnancePharmacie($consultation_id, $_POST['medicaments']);
            }
        }

        // --- ÉTAPE 7 : FINALISATION ---
        if ($etape_actuelle == 7) {
            return $this->finaliserConsultation();
        }

        // Navigation vers l'étape suivante
        $next_etape = $etape_actuelle + 1;
        $url = BASE_URL . "consultation/formulaire?patient_id=$patient_id&type=$type&etape=$next_etape";
        header("Location: $url");
        exit;
    }
}

    /**
     * Enregistrement final en base de données
     */
   private function finaliserConsultation() {
    if (!isset($_SESSION['consultation_temp'])) {
        header('Location: ' . BASE_URL . 'dashboard');
        exit;
    }

    $data = $_SESSION['consultation_temp'];
    $db = $this->db;

    // --- LOGIQUE ANTI-DOUBLON ---
    // On vérifie si un ID de consultation existe déjà en session (créé à l'étape 4 ou 5)
    $consultation_id = $data['consultation_id'] ?? null;

    if ($consultation_id) {
        // Si l'ID existe, on MET À JOUR la ligne existante au lieu d'en créer une nouvelle
        $this->consultationModel->update($consultation_id, $data);
    } else {
        // Sinon, on crée la consultation (cas où le médecin n'a demandé ni labo ni pharmacie)
        $consultation_id = $this->consultationModel->create($data);
    }

    if ($consultation_id) {
        // 1. On sort le patient de la file d'attente
        // S'il y a un RDV, il va à l'ACCUEIL, sinon il est considéré comme SORTI
        $nouveauStatut = !empty($data['date_suivi']) ? 'ACCUEIL' : 'SORTI';

        $stmtUpdate = $db->prepare("UPDATE patients SET statut_parcours = ?, statut_hosp = 'AUCUN' WHERE id = ?");
        $stmtUpdate->execute([$nouveauStatut, $data['patient_id']]);

        // 2. LOGIQUE RENDEZ-VOUS
        if (!empty($data['date_suivi'])) {
            $stmtRDV = $db->prepare("INSERT INTO patient_rdv (patient_id, medecin_id, date_rdv, motif, statut)
                                     VALUES (?, ?, ?, ?, 'CONFIRME')");
            $stmtRDV->execute([
                $data['patient_id'],
                $_SESSION['user_id'],
                $data['date_suivi'],
                $data['motif_suivi'] ?? 'Suivi médical'
            ]);
        }

        // 3. Règle de l'heure (Hospitaliser) et Date de clôture
        $this->db->prepare("UPDATE consultations SET
                            wait_hospital_until = DATE_ADD(NOW(), INTERVAL 1 HOUR),
                            date_cloture = NOW(),
                            statut = 'terminee'
                            WHERE id = ?")
                 ->execute([$consultation_id]);

        // 4. Nettoyage et redirection
        unset($_SESSION['consultation_temp']);
        header('Location: ' . BASE_URL . 'dashboard?success=consult_saved');
        exit;
    }
}

    /**
     * Affichage du récapitulatif final
     */
    public function recapitulatif($id) {
        $consultation = $this->consultationModel->getById($id);

        if (!$consultation) {
            die("Consultation introuvable");
        }

        $patient = $this->patientModel->getById($consultation['patient_id']);

        // Vue : recapitulatif.php
        require_once __DIR__ . '/../views/consultations/recapitulatif.php';
    }

    /**
     * Générer l'ordonnance pour impression/PDF
     */
    public function imprimerOrdonnance($prescription_id) {
        require_once __DIR__ . '/../models/Prescription.php';
        $prescriptionModel = new Prescription();

        // 1. Récupérer les infos
        $ordonnance = $prescriptionModel->getById($prescription_id);
        $medicaments = $prescriptionModel->getMedicaments($prescription_id);
        $hopital = $prescriptionModel->getHopitalSettings();

        if (!$ordonnance) {
            die("Ordonnance introuvable");
        }

        // 2. Charger la vue d'impression
        require_once __DIR__ . '/../views/consultations/print/ordonnance.php';
    }

    // Recherche CIM-10 (AJAX)
    public function searchCim10() {
        $term = $_GET['q'] ?? '';
        if (strlen($term) < 2) { echo json_encode([]); return; }

        $stmt = $this->db->prepare("SELECT * FROM cim10 WHERE code LIKE :term OR description LIKE :term LIMIT 20");
        $stmt->execute([':term' => "%$term%"]);
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public function getKits() {
        header('Content-Type: application/json');
        try {
            $stmt = $this->db->prepare("SELECT * FROM prescription_kits WHERE actif = 1 ORDER BY nom");
            $stmt->execute();
            $kits = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode($kits);
        } catch (Exception $e) {
            echo json_encode([]);
        }
    }

    public function getKitDetails($id) {
        header('Content-Type: application/json');
        try {
            $stmt = $this->db->prepare("
                SELECT pki.*, m.nom as nom_medicament, m.forme, m.dosage
                FROM prescription_kit_items pki
                JOIN medicaments m ON pki.medicament_id = m.id
                WHERE pki.kit_id = ?
            ");
            $stmt->execute([$id]);
            $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode($items);
        } catch (Exception $e) {
            echo json_encode([]);
        }
    }

    /**
     * Enregistrer une décision d'hospitalisation
     */
    public function decisionHospitalisation() {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $consultation_id = $input['consultation_id'] ?? null;
        $decision = $input['decision'] ?? null;
        $justification = $input['justification'] ?? '';
        $medecin_id = $_SESSION['user_id'] ?? 1;

        if (!$consultation_id || !$decision) {
            echo json_encode(['success' => false, 'message' => 'Données manquantes']);
            return;
        }

        require_once __DIR__ . '/../services/HospitalisationService.php';
        $success = HospitalisationService::enregistrerDecisionHospitalisation(
            $consultation_id,
            $decision,
            $medecin_id,
            $justification
        );

        if ($success) {
            echo json_encode(['success' => true, 'message' => 'Décision enregistrée']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erreur lors de l\'enregistrement']);
        }
    }

    // app/controllers/ConsultationController.php

public function sauvegarder() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // 1. Récupérer les données de la session (accumulées durant les 7 étapes)
        $data = $_SESSION['consultation_form'] ?? [];
        $patient_id = $_POST['patient_id'];
        $user_id = $_SESSION['user_id'];

        // 2. Préparer l'objet pour la base de données
        $consultationData = [
            'patient_id' => $patient_id,
            'medecin_id' => $user_id,
            'motif' => $data['step1']['motif'] ?? '',
            'examen_physique' => $data['step2']['examen_physique'] ?? '',
            'diagnostic' => $data['step3']['diagnostic'] ?? '',
            'traitement' => $data['step5']['traitement'] ?? '',
            // Ajoutez tous les champs nécessaires ici
        ];

        // 3. Appel au modèle pour l'insertion
        require_once 'app/models/Consultation.php';
        $consultModel = new Consultation();
        $consult_id = $consultModel->save($consultationData);

        if ($consult_id) {
            // 4. Nettoyer la session après enregistrement
            unset($_SESSION['consultation_form']);

            // 5. Rediriger vers le dossier patient avec succès
            header('Location: ' . BASE_URL . 'patients/dossier/' . $patient_id . '?success=consult_saved');
        } else {
            header('Location: ' . BASE_URL . 'consultation/formulaire?error=save_failed');
        }
        exit;
    }
}

/**
     * Initialise une nouvelle consultation et redirige vers l'étape 1
     */
    public function ouvrir($id) {
        // 1. On s'assure qu'on nettoie les anciens brouillons en session
        if (session_status() === PHP_SESSION_NONE) { session_start(); }
        unset($_SESSION['consultation_temp']);

        // 2. On redirige vers la route 'consultation/formulaire' que vous avez déjà
        // en passant l'ID du patient, le type (externe par défaut ici) et l'étape 1
        header('Location: ' . BASE_URL . 'consultation/formulaire?patient_id=' . $id . '&type=EXTERNE&etape=1');
        exit;
    }

    public function cloturer($id) {
    // 1. Récupérer les infos de la consultation pour avoir l'ID du patient
    $consultation = $this->consultationModel->getById($id);

    if ($consultation) {
        $patient_id = $consultation['patient_id'];

        // 2. Sortir le patient de la file d'attente (statut_parcours)
        // On le passe en 'SORTI' pour qu'il ne soit plus dans 'ATTENTE_CONSULTATION'
        $stmtP = $this->db->prepare("UPDATE patients SET statut_parcours = 'SORTI' WHERE id = ?");
        $stmtP->execute([$patient_id]);

        // 3. Activer le verrou de 1h pour le bouton "Hospitaliser" sur le Dashboard
        // (La logique que nous avons mise en place précédemment)
        $stmtH = $this->db->prepare("UPDATE consultations SET wait_hospital_until = DATE_ADD(NOW(), INTERVAL 1 HOUR) WHERE id = ?");
        $stmtH->execute([$id]);

        // 4. Marquer la date de clôture effective
        $stmtC = $this->db->prepare("UPDATE consultations SET date_cloture = NOW() WHERE id = ?");
        $stmtC->execute([$id]);
    }

    // 5. Redirection vers le dashboard médecin avec un message de succès
    header('Location: ' . BASE_URL . 'dashboard?success=consultation_terminee');
    exit;
}
}
?>