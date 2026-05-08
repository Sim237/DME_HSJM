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
            // Historique des examens du patient (dégradé si tables absentes)
            $historique_examens = [];
            try {
                $stmtEx = $this->db->prepare(
                    "SELECT e.id, e.type_examen, e.statut, e.date_demande,
                            GROUP_CONCAT(ed.nom_examen SEPARATOR ', ') AS noms
                     FROM examens e
                     LEFT JOIN examen_details ed ON ed.examen_id = e.id
                     WHERE e.patient_id = ?
                     GROUP BY e.id
                     ORDER BY e.date_demande DESC LIMIT 5"
                );
                $stmtEx->execute([$patient_id]);
                $historique_examens = $stmtEx->fetchAll(PDO::FETCH_ASSOC);
            } catch (Exception $e) { /* table absente — on continue */ }
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
        if ($etape_actuelle == 5) {
            require_once __DIR__ . '/../services/PharmacieService.php';
            $pharmacieService = new PharmacieService();

            // ANTI-DOUBLON : On utilise l'ID existant ou on crée la consultation
            if (!isset($_SESSION['consultation_temp']['consultation_id'])) {
                $consultation_id = $this->consultationModel->create($data);
                if ($consultation_id) {
                    $_SESSION['consultation_temp']['consultation_id'] = $consultation_id;
                }
            } else {
                $consultation_id = $_SESSION['consultation_temp']['consultation_id'];
                $this->consultationModel->update($consultation_id, $data);
            }

            // Créer l'ordonnance seulement s'il y a des médicaments ET une consultation valide
            if (!empty($_POST['medicaments']) && !empty($consultation_id)) {
                // Normaliser les médicaments (compatibilité nouvelles clés voie/instructions)
                $medicamentsNormalises = [];
                foreach ($_POST['medicaments'] as $m) {
                    $medicamentsNormalises[] = [
                        'medicament_id' => $m['medicament_id'] ?? null,
                        'nom_medicament' => $m['nom_medicament'] ?? $m['nom'] ?? 'Médicament',
                        'posologie'      => $m['posologie']      ?? '',
                        'duree'          => $m['duree']          ?? '',
                        'quantite'       => $m['quantite']       ?? 1,
                        'voie'           => $m['voie']           ?? 'orale',
                        'instructions'   => $m['instructions']   ?? '',
                    ];
                }

                $ordonnance_id = $pharmacieService->creerOrdonnancePharmacie(
                    $consultation_id,
                    $medicamentsNormalises
                );

                if ($ordonnance_id) {
                    $_SESSION['consultation_temp']['ordonnance_id'] = $ordonnance_id;
                } else {
                    error_log('[Etape5] Échec création ordonnance pour consultation_id=' . $consultation_id);
                }
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
     * Enregistrement final en base de données — version robuste
     */
   private function finaliserConsultation() {
    try {
        // ── 0. Vérification de base ──────────────────────────────
        if (!isset($_SESSION['consultation_temp'])) {
            header('Location: ' . BASE_URL . 'dashboard');
            exit;
        }

        $data       = $_SESSION['consultation_temp'];
        $patient_id = $data['patient_id'] ?? $_POST['patient_id'] ?? null;

        if (!$patient_id) {
            error_log('[finaliserConsultation] patient_id manquant');
            header('Location: ' . BASE_URL . 'dashboard?error=patient_id_manquant');
            exit;
        }

        // ── 1. Fusionner les données POST de l'étape 7 ──────────
        $data = array_merge($data, [
            'notes_suivi'  => $_POST['notes_suivi']  ?? $data['notes_suivi']  ?? null,
            'date_suivi'   => $_POST['date_suivi']   ?? $data['date_suivi']   ?? null,
            'motif_suivi'  => $_POST['motif_suivi']  ?? $data['motif_suivi']  ?? null,
            'patient_id'   => $patient_id,
            'medecin_id'   => $_SESSION['user_id'] ?? $data['medecin_id'] ?? 1,
        ]);

        // ── 2. Créer ou mettre à jour la consultation ────────────
        $consultation_id = $data['consultation_id'] ?? null;

        if ($consultation_id) {
            $this->consultationModel->update($consultation_id, $data);
        } else {
            $consultation_id = $this->consultationModel->create($data);
        }

        if (!$consultation_id) {
            error_log('[finaliserConsultation] Échec create/update consultation');
            header('Location: ' . BASE_URL . 'consultation/formulaire?patient_id=' . $patient_id . '&type=' . ($data['type'] ?? 'EXTERNE') . '&etape=7&error=save_failed');
            exit;
        }

        // ── 3. Mise à jour statut patient (colonnes optionnelles) ─
        try {
            // Vérifie si la colonne statut_parcours existe avant de l'utiliser
            $chk = $this->db->query("SHOW COLUMNS FROM patients LIKE 'statut_parcours'");
            if ($chk->rowCount() > 0) {
                $nouveauStatut = !empty($data['date_suivi']) ? 'ACCUEIL' : 'SORTI';
                $this->db->prepare("UPDATE patients SET statut_parcours = ? WHERE id = ?")
                         ->execute([$nouveauStatut, $patient_id]);
            }

            $chk2 = $this->db->query("SHOW COLUMNS FROM patients LIKE 'statut_hosp'");
            if ($chk2->rowCount() > 0) {
                $this->db->prepare("UPDATE patients SET statut_hosp = 'AUCUN' WHERE id = ?")
                         ->execute([$patient_id]);
            }
        } catch (Exception $e) {
            error_log('[finaliserConsultation] Mise à jour statut patient ignorée : ' . $e->getMessage());
        }

        // ── 4. Clôture consultation (colonnes optionnelles) ───────
        try {
            $cols = $this->db->query("SHOW COLUMNS FROM consultations")->fetchAll(PDO::FETCH_COLUMN);
            $sets = ['statut = ?'];
            $vals = ['terminee'];

            if (in_array('date_cloture', $cols)) {
                $sets[] = 'date_cloture = NOW()';
            }
            if (in_array('wait_hospital_until', $cols)) {
                $sets[] = 'wait_hospital_until = DATE_ADD(NOW(), INTERVAL 1 HOUR)';
            }

            $this->db->prepare('UPDATE consultations SET ' . implode(', ', $sets) . ' WHERE id = ?')
                     ->execute(array_merge($vals, [$consultation_id]));
        } catch (Exception $e) {
            error_log('[finaliserConsultation] Clôture consultation ignorée : ' . $e->getMessage());
        }

        // ── 5. Rendez-vous de suivi ───────────
        if (!empty($data['date_suivi'])) {
            try {
                $this->db->prepare(
                    "INSERT INTO agenda_medical (patient_id, medecin_id, date_debut, date_fin, motif, statut, type_rdv)
                     VALUES (?, ?, ?, DATE_ADD(?, INTERVAL 30 MINUTE), ?, 'CONFIRME', 'SUIVI')"
                )->execute([
                    $patient_id,
                    $_SESSION['user_id'] ?? 1,
                    $data['date_suivi'],
                    $data['date_suivi'],
                    $data['motif_suivi'] ?? 'Suivi médical'
                ]);
            } catch (Exception $e) {
                error_log('[finaliserConsultation] RDV ignoré : ' . $e->getMessage());
            }
        }

        // ── 6. Nettoyage session et redirection vers récapitulatif ───────────────────
        unset($_SESSION['consultation_temp']);
        header('Location: ' . BASE_URL . 'consultation/recapitulatif/' . $consultation_id);
        exit;

    } catch (Exception $e) {
        error_log('[finaliserConsultation] Exception fatale : ' . $e->getMessage());
        // Jamais de page blanche — toujours rediriger
        $pid = $_POST['patient_id'] ?? $_SESSION['consultation_temp']['patient_id'] ?? '';
        header('Location: ' . BASE_URL . ($pid ? 'patients/dossier/' . $pid : 'dashboard') . '?error=' . urlencode('Erreur finalisation : ' . $e->getMessage()));
        exit;
    }
}

    /**
     * Affichage du récapitulatif final
     */
    public function recapitulatif($id) {
        // ── Migration silencieuse : s'assurer que notes_suivi existe ────────
        try {
            $this->db->exec("ALTER TABLE consultations ADD COLUMN IF NOT EXISTS notes_suivi TEXT NULL");
        } catch (Exception $e) { /* silencieux */ }

        $consultation = $this->consultationModel->getById($id);

        if (!$consultation) {
            die("Consultation introuvable");
        }

        $patient = $this->patientModel->getById($consultation['patient_id']);

        // ── Antécédents patient ─────────────────────────────────────────────
        $antecedents = [];
        try {
            $stmtA = $this->db->prepare(
                "SELECT type, description, date_survenue
                 FROM antecedents WHERE patient_id = ?
                 ORDER BY type, date_survenue DESC"
            );
            $stmtA->execute([$consultation['patient_id']]);
            foreach ($stmtA->fetchAll(PDO::FETCH_ASSOC) as $r) {
                $antecedents[$r['type']][] = $r;
            }
        } catch (Exception $e) {}

        // ── Examens / Bilans demandés ────────────────────────────────────────
        $examens_list = [];
        try {
            $stmtE = $this->db->prepare(
                "SELECT e.id, e.type_examen, e.urgence, e.statut, e.date_demande,
                        GROUP_CONCAT(ed.nom_examen ORDER BY ed.nom_examen SEPARATOR ', ') AS noms_detail
                 FROM examens e
                 LEFT JOIN examen_details ed ON ed.examen_id = e.id
                 WHERE e.consultation_id = ?
                 GROUP BY e.id
                 ORDER BY e.date_demande ASC"
            );
            $stmtE->execute([$id]);
            $examens_list = $stmtE->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) { error_log('[recapitulatif] examens: '.$e->getMessage()); }

        // ── Ordonnance + médicaments prescrits (table ordonnances_pharmacie) ──
        $prescription = null;
        $medicaments_prescrits = [];
        try {
            $stmtP = $this->db->prepare(
                "SELECT op.*, u.nom AS prescripteur_nom, u.prenom AS prescripteur_prenom
                 FROM ordonnances_pharmacie op
                 LEFT JOIN consultations c ON c.id = op.consultation_id
                 LEFT JOIN users u ON u.id = c.medecin_id
                 WHERE op.consultation_id = ?
                 ORDER BY op.id DESC LIMIT 1"
            );
            $stmtP->execute([$id]);
            $prescription = $stmtP->fetch(PDO::FETCH_ASSOC);

            if ($prescription) {
                // La table ordonnance_medicaments peut avoir une colonne nom_medicament (ajoutée par PharmacieService)
                $colsOm = $this->db->query("SHOW COLUMNS FROM ordonnance_medicaments")->fetchAll(PDO::FETCH_COLUMN);
                $hasNomCol = in_array('nom_medicament', $colsOm);

                $nomSelect = $hasNomCol
                    ? "COALESCE(om.nom_medicament, m.nom, 'Médicament') AS nom_medicament"
                    : "COALESCE(m.nom, 'Médicament') AS nom_medicament";

                $stmtL = $this->db->prepare(
                    "SELECT om.*, $nomSelect, m.forme, m.dosage, m.unite
                     FROM ordonnance_medicaments om
                     LEFT JOIN medicaments m ON m.id = om.medicament_id
                     WHERE om.ordonnance_id = ?
                     ORDER BY om.id ASC"
                );
                $stmtL->execute([$prescription['id']]);
                $medicaments_prescrits = $stmtL->fetchAll(PDO::FETCH_ASSOC);
            }
        } catch (Exception $e) { error_log('[recapitulatif] ordonnance: '.$e->getMessage()); }

        // Services disponibles (pour modal transfer/hospitalisation)
        $services = [];
        try {
            $services = $this->db->query("SELECT id, nom FROM services ORDER BY nom")->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {}

        // Lits disponibles groupés par service
        $lits_dispo = [];
        try {
            $stmtL = $this->db->query(
                "SELECT l.id, l.nom_lit, l.service_id, s.nom as service_nom
                 FROM lits l JOIN services s ON l.service_id = s.id
                 WHERE l.statut = 'DISPONIBLE' AND l.occupied_by_patient_id IS NULL
                 ORDER BY s.nom, l.nom_lit"
            );
            foreach ($stmtL->fetchAll(PDO::FETCH_ASSOC) as $l) {
                $lits_dispo[$l['service_id']][] = $l;
            }
        } catch (Exception $e) {}

        // Critères d'hospitalisation IA
        $criteres_hosp = [];
        try {
            require_once __DIR__ . '/../services/HospitalisationService.php';
            $age = !empty($patient['date_naissance'])
                ? date_diff(date_create($patient['date_naissance']), date_create('today'))->y : null;
            $criteres_hosp = HospitalisationService::analyserCriteresHospitalisation($consultation, $age);
        } catch (Exception $e) {}

        // ── Vue ─────────────────────────────────────────────────────────────
        require_once __DIR__ . '/../views/consultations/recapitulatif.php';
    }

    /**
     * Impression ordonnance pharmacien (système ordonnances_pharmacie)
     */
    public function imprimerOrdonnancePharmacien($ordonnance_id) {
        try {
            $stmtO = $this->db->prepare(
                "SELECT op.*, c.patient_id, c.medecin_id,
                        p.nom AS pat_nom, p.prenom AS pat_prenom, p.date_naissance, p.sexe, p.dossier_numero,
                        u.nom AS med_nom, u.prenom AS med_prenom
                 FROM ordonnances_pharmacie op
                 JOIN consultations c ON c.id = op.consultation_id
                 JOIN patients p ON p.id = c.patient_id
                 JOIN users u ON u.id = c.medecin_id
                 WHERE op.id = ?"
            );
            $stmtO->execute([$ordonnance_id]);
            $ordonnance = $stmtO->fetch(PDO::FETCH_ASSOC);

            if (!$ordonnance) { die("Ordonnance introuvable"); }

            $colsOm = $this->db->query("SHOW COLUMNS FROM ordonnance_medicaments")->fetchAll(PDO::FETCH_COLUMN);
            $hasNomCol = in_array('nom_medicament', $colsOm);
            $nomSelect = $hasNomCol
                ? "COALESCE(om.nom_medicament, m.nom, 'Médicament') AS nom_medicament"
                : "COALESCE(m.nom, 'Médicament') AS nom_medicament";

            $stmtM = $this->db->prepare(
                "SELECT om.*, $nomSelect, m.forme, m.dosage
                 FROM ordonnance_medicaments om
                 LEFT JOIN medicaments m ON m.id = om.medicament_id
                 WHERE om.ordonnance_id = ?
                 ORDER BY om.id ASC"
            );
            $stmtM->execute([$ordonnance_id]);
            $medicaments = $stmtM->fetchAll(PDO::FETCH_ASSOC);

            require_once __DIR__ . '/../views/consultations/print/ordonnance_pharmacien.php';
        } catch (Exception $e) {
            die("Erreur : " . $e->getMessage());
        }
    }

    /**
     * Générer l'ordonnance pour impression/PDF (ancien système prescriptions)
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

        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $consultation_id = $input['consultation_id'] ?? null;
        $decision        = $input['decision']         ?? null;
        $justification   = $input['justification']    ?? '';
        $service_dest_id = $input['service_id']       ?? null;
        $lit_id          = $input['lit_id']            ?? null;
        $medecin_id      = $_SESSION['user_id']        ?? 1;

        if (!$consultation_id || !$decision) {
            echo json_encode(['success' => false, 'message' => 'Données manquantes']);
            return;
        }

        $consultation = $this->consultationModel->getById($consultation_id);
        if (!$consultation) {
            echo json_encode(['success' => false, 'message' => 'Consultation introuvable']);
            return;
        }
        $patient_id = $consultation['patient_id'];

        require_once __DIR__ . '/../services/HospitalisationService.php';
        HospitalisationService::enregistrerDecisionHospitalisation($consultation_id, $decision, $medecin_id, $justification);

        $redirect_url = BASE_URL . 'dashboard';

        if ($decision === 'HOSPITALISATION') {
            $medecin_service_id = $_SESSION['service_id'] ?? null;
            $medecin_service_nom = '';
            try {
                $row = $this->db->prepare("SELECT nom FROM services WHERE id = ?");
                $row->execute([$medecin_service_id]);
                $medecin_service_nom = strtolower($row->fetchColumn() ?: '');
            } catch (Exception $e) {}

            $is_consultation_externe = str_contains($medecin_service_nom, 'extern') ||
                                       str_contains($medecin_service_nom, 'consult');

            if (!$service_dest_id) {
                if ($is_consultation_externe) {
                    try {
                        $rowU = $this->db->query("SELECT id FROM services WHERE LOWER(nom) LIKE '%urgence%' LIMIT 1");
                        $service_dest_id = $rowU->fetchColumn() ?: null;
                    } catch (Exception $e) {}
                } else {
                    $service_dest_id = $medecin_service_id;
                }
            }

            try {
                $this->db->prepare(
                    "UPDATE patients SET statut_parcours = 'ATTENTE_HOSPITALISATION',
                     statut_hosp = 'A_HOSPITALISER', service_id = ? WHERE id = ?"
                )->execute([$service_dest_id, $patient_id]);
            } catch (Exception $e) {}

            if ($is_consultation_externe && $service_dest_id) {
                try {
                    $stmtChk = $this->db->prepare("SELECT id FROM urgences_patients WHERE patient_id = ? AND statut NOT IN ('TERMINE','SORTI') LIMIT 1");
                    $stmtChk->execute([$patient_id]);
                    if (!$stmtChk->fetch()) {
                        $this->db->prepare(
                            "INSERT INTO urgences_patients (patient_id, motif_admission, niveau_triage, heure_arrivee, medecin_id, statut)
                             VALUES (?, ?, '3', NOW(), ?, 'EN_ATTENTE')"
                        )->execute([$patient_id, $justification ?: 'Transfert consultation externe', $medecin_id]);
                    }
                } catch (Exception $e) { error_log("DecisionHosp urgences_patients: " . $e->getMessage()); }
                $redirect_url = BASE_URL . 'urgences';
            }

            if ($lit_id && $service_dest_id) {
                try { HospitalisationService::assignLitNurse($patient_id, $service_dest_id, $lit_id, $medecin_id); }
                catch (Exception $e) {}
            }

        } elseif ($decision === 'SORTIE') {
            try {
                $this->db->prepare(
                    "UPDATE patients SET statut_parcours = 'SORTI', statut_hosp = 'AUCUN' WHERE id = ?"
                )->execute([$patient_id]);
            } catch (Exception $e) {}
            $redirect_url = BASE_URL . 'patients/dossier/' . $patient_id;
        }

        echo json_encode(['success' => true, 'message' => 'Décision enregistrée', 'redirect' => $redirect_url]);
    }

    /**
     * Transfert d'un patient vers un autre service avec sélection de lit
     */
    public function transfererPatient() {
        header('Content-Type: application/json');

        $patient_id      = $_POST['patient_id']  ?? null;
        $service_dest_id = $_POST['service_id']  ?? null;
        $lit_id          = $_POST['lit_id']       ?? null;
        $motif_transfert = $_POST['motif']        ?? 'Transfert inter-service';
        $infirmier_id    = $_SESSION['user_id']   ?? 1;

        if (!$patient_id || !$service_dest_id || !$lit_id) {
            echo json_encode(['success' => false, 'message' => 'Champs obligatoires manquants (patient, service, lit)']);
            return;
        }

        try {
            $this->db->beginTransaction();

            // Clôturer hospitalisation active si elle existe
            $stmtH = $this->db->prepare(
                "SELECT id, lit_id FROM hospitalisations WHERE patient_id = ? AND statut = 'active' LIMIT 1"
            );
            $stmtH->execute([$patient_id]);
            $hosp_actuelle = $stmtH->fetch(PDO::FETCH_ASSOC);

            if ($hosp_actuelle) {
                $this->db->prepare(
                    "UPDATE hospitalisations SET statut = 'transfere', date_sortie = NOW(), motif_sortie = ? WHERE id = ?"
                )->execute([$motif_transfert, $hosp_actuelle['id']]);
                if ($hosp_actuelle['lit_id']) {
                    $this->db->prepare(
                        "UPDATE lits SET statut = 'DISPONIBLE', occupied_by_patient_id = NULL, occupied_since = NULL WHERE id = ?"
                    )->execute([$hosp_actuelle['lit_id']]);
                }
            }

            require_once __DIR__ . '/../services/HospitalisationService.php';
            $hosp_id = HospitalisationService::assignLitNurse($patient_id, $service_dest_id, $lit_id, $infirmier_id);

            try {
                $this->db->prepare(
                    "INSERT INTO transferts_patients (patient_id, service_origine_id, service_destination_id,
                     lit_destination_id, hospitalisation_id, motif, infirmier_id, date_transfert)
                     VALUES (?, ?, ?, ?, ?, ?, ?, NOW())"
                )->execute([
                    $patient_id, $_SESSION['service_id'] ?? null, $service_dest_id,
                    $lit_id, $hosp_id ?: null, $motif_transfert, $infirmier_id
                ]);
            } catch (Exception $e) { error_log("transferts_patients insert ignoré: " . $e->getMessage()); }

            $this->db->prepare(
                "UPDATE patients SET service_id = ?, statut_parcours = 'HOSPITALISE' WHERE id = ?"
            )->execute([$service_dest_id, $patient_id]);

            $this->db->commit();

            $stmtInfo = $this->db->prepare(
                "SELECT l.nom_lit, s.nom as service_nom FROM lits l JOIN services s ON l.service_id = s.id WHERE l.id = ?"
            );
            $stmtInfo->execute([$lit_id]);
            $info = $stmtInfo->fetch(PDO::FETCH_ASSOC);

            echo json_encode([
                'success' => true,
                'message' => 'Patient transféré vers ' . ($info['service_nom'] ?? 'le service') . ' — Lit ' . ($info['nom_lit'] ?? ''),
                'redirect' => BASE_URL . 'hospitalisation/dossier/' . $patient_id
            ]);

        } catch (Exception $e) {
            $this->db->rollBack();
            echo json_encode(['success' => false, 'message' => 'Erreur lors du transfert : ' . $e->getMessage()]);
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