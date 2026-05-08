<?php
/* ============================================================================
FICHIER : app/controllers/PatientController.php
CONTRÔLEUR DE GESTION COMPLÈTE DU DOSSIER PATIENT (DME)
============================================================================ */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../models/Patient.php';
require_once __DIR__ . '/UnifiedController.php';
require_once __DIR__ . '/../services/AuditService.php';

class PatientController extends UnifiedController {
    private $db;
    private $patientModel;
    private $audit;

    public function __construct() {
        parent::__construct();

        $database = new Database();
        $this->db = $database->getConnection();
        $this->patientModel = new Patient();
        $this->audit = new AuditService();
    }

    /**
     * Liste des patients (Filtrée par service pour le personnel)
     */
    public function index() {
        $this->auth->requirePermission('patients', 'read');

        $search = $_GET['search'] ?? null;
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = 10;
        $offset = ($page - 1) * $limit;

        // Restriction par service (sauf Admin)
        $service_id = ($_SESSION['user_role'] !== 'ADMIN') ? $_SESSION['service_id'] : null;

        if ($search) {
            $patients = $this->patientModel->search($search, $service_id);
            $total_patients = count($patients);
            $total_pages = 1;
        } else {
            $patients = $this->patientModel->getAll($limit, $offset, $service_id);
            $total_patients = $this->patientModel->count($service_id);
            $total_pages = ceil($total_patients / $limit);
        }

        include __DIR__ . '/../views/patients/liste.php';
    }

    /**
     * Formulaire nouveau patient
     */
    public function nouveau() {
        $this->auth->requirePermission('patients', 'write');

        $data = [
            'nom' => '', 'prenom' => '', 'date_naissance' => '', 'sexe' => '',
            'telephone' => '', 'email' => '', 'adresse' => '', 'groupe_sanguin' => '',
            'contact_nom' => '', 'contact_telephone' => '', 'antecedents_medicaux' => '', 'allergies' => ''
        ];

        include __DIR__ . '/../views/patients/nouveau.php';
    }

    /**
     * Enregistrement du patient + Audit + Registres
     */
    public function store() {
        $this->auth->requirePermission('patients', 'write');

        if($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'nom' => strtoupper(htmlspecialchars($_POST['nom'])),
                'prenom' => htmlspecialchars($_POST['prenom']),
                'date_naissance' => $_POST['date_naissance'],
                'sexe' => $_POST['sexe'],
                'telephone' => $_POST['telephone'] ?? null,
                'email' => $_POST['email'] ?? null,
                'adresse' => $_POST['adresse'] ?? null,
                'groupe_sanguin' => $_POST['groupe_sanguin'] ?? null,
                'contact_nom' => $_POST['contact_nom'] ?? null,
                'contact_telephone' => $_POST['contact_telephone'] ?? null,
                'antecedents_medicaux' => $_POST['antecedents_medicaux'] ?? null,
                'allergies' => $_POST['allergies'] ?? null,
                'service_id' => $_SESSION['service_id'],
                'statut' => 'EXTERNE'
            ];

            if (empty($data['nom']) || empty($data['prenom']) || empty($data['date_naissance'])) {
                $error = "Veuillez remplir tous les champs obligatoires.";
                include __DIR__ . '/../views/patients/nouveau.php';
                return;
            }

            $patient_id = $this->patientModel->create($data);

            if($patient_id) {
                $this->audit->logAction('CREATE', 'patients', $patient_id, null, "Création du dossier patient");
                if (isset($_POST['pathologies'])) {
                    $this->enregistrerPathologies($patient_id, $_POST);
                }
                header('Location: ' . BASE_URL . 'patients?success=1');
                exit();
            }
        }
    }

    /**
     * Affiche le formulaire de prise de constantes (MÉTHODE CORRIGÉE)
     */
    public function mesures($id) {
        $this->auth->requirePermission('patients', 'write');

        $patient = $this->patientModel->getById($id);

        if(!$patient) {
            header('Location: ' . BASE_URL . 'patients?error=not_found');
            exit();
        }

        // Vérification de sécurité service (sauf admin)
        if ($_SESSION['user_role'] !== 'ADMIN' && $patient['service_id'] != $_SESSION['service_id']) {
            die("Accès refusé : Ce patient appartient à un autre service.");
        }

        require_once __DIR__ . '/../views/patients/mesures.php';
    }

    /**
     * AJAX — Enregistre les constantes vitales depuis le modal du dossier patient
     */
    public function saveConstantes() {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
            return;
        }

        $patient_id = (int)($_POST['patient_id'] ?? 0);
        if (!$patient_id) {
            echo json_encode(['success' => false, 'message' => 'Patient manquant']);
            return;
        }

        $temp   = !empty($_POST['temperature'])        ? (float)$_POST['temperature']        : null;
        $sys    = !empty($_POST['tension_sys'])        ? (int)$_POST['tension_sys']           : null;
        $dia    = !empty($_POST['tension_dia'])        ? (int)$_POST['tension_dia']           : null;
        $pouls  = !empty($_POST['frequence_cardiaque']) ? (int)$_POST['frequence_cardiaque'] : null;
        $spo2   = !empty($_POST['spo2'])               ? (int)$_POST['spo2']                 : null;
        $poids  = !empty($_POST['poids'])              ? (float)$_POST['poids']              : null;
        $taille = !empty($_POST['taille'])             ? (int)$_POST['taille']               : null;

        try {
            $db = (new Database())->getConnection();
            $db->prepare("
                INSERT INTO patient_parametres (
                    patient_id, user_id, temperature,
                    pression_arterielle_systolique, pression_arterielle_diastolique,
                    frequence_cardiaque, poids, taille, saturation_oxygene, date_mesure
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ")->execute([
                $patient_id, $_SESSION['user_id'] ?? 0,
                $temp, $sys, $dia, $pouls, $poids, $taille, $spo2
            ]);

            echo json_encode([
                'success' => true,
                'data'    => [
                    'temperature' => $temp,
                    'poids'       => $poids,
                    'pouls'       => $pouls,
                    'sys'         => $sys,
                    'dia'         => $dia,
                    'spo2'        => $spo2,
                ]
            ]);
        } catch (Exception $e) {
            error_log("saveConstantes: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Erreur base de données']);
        }
    }

    /**
     * Sauvegarde des paramètres vitaux (Prise de constantes)
     */
    public function saveMesures() {
    $db = (new Database())->getConnection();
    $patient_id = $_POST['patient_id'];
    $medecin_id = $_POST['medecin_id'];
    $is_urgence = ($_POST['priorite'] === 'CRITIQUE' || $_POST['type_admission'] === 'URGENCE');

    // 1. Vérification du QUOTA (Seulement pour les consultations normales)
    if (!$is_urgence) {
        $stmt = $db->prepare("SELECT COUNT(*) FROM consultations
                              WHERE medecin_id = ?
                              AND DATE(date_consultation) = CURDATE()
                              AND type_admission = 'CONSULTATION'");
        $stmt->execute([$medecin_id]);
        $count = $stmt->fetchColumn();

        if ($count >= 20) {
            header('Location: ' . BASE_URL . 'patients/mesures/' . $patient_id . '?error=quota_atteint');
            exit();
        }
    }

    // 2. Enregistrement des paramètres vitaux et du motif
    // (Utilisez votre méthode addParametres existante en ajoutant le motif)

    // 3. Mise à jour du statut du patient et création de l'entrée en file d'attente
    $db->prepare("UPDATE patients SET statut_parcours = ?, medecin_id = ?, service_id = ? WHERE id = ?")
       ->execute([
           $is_urgence ? 'URGENCES' : 'ATTENTE_CONSULTATION',
           $medecin_id,
           $_POST['service_id'],
           $patient_id
       ]);

    header('Location: ' . BASE_URL . 'patients?success=transmis');
}

    /**
     * Affiche le dossier médical complet d'un patient
     * Inclut : Infos identité, Dernières constantes, Historique consultations et Documents
     */
    public function dossier($id) {
        // 1. Récupération des informations de base du patient
        $patient = $this->patientModel->getById($id);

        if(!$patient) {
            header('Location: ' . BASE_URL . 'patients?error=not_found');
            exit();
        }

        // 2. Sécurité : cloisonnement par service — les médecins et urgentistes peuvent
        //    accéder aux patients en cours de consultation ou d'urgence chez eux.
        if ($_SESSION['user_role'] !== 'ADMIN') {
            $canAccess = ($patient['service_id'] == $_SESSION['service_id']);

            // Médecin ayant une consultation active ou récente pour ce patient
            if (!$canAccess && in_array($_SESSION['user_role'], ['MEDECIN', 'MEDECIN_URGENCES'])) {
                $stmtConsult = $this->db->prepare(
                    "SELECT COUNT(*) FROM consultations WHERE patient_id = ? AND medecin_id = ?"
                );
                $stmtConsult->execute([$id, $_SESSION['user_id']]);
                $canAccess = ($stmtConsult->fetchColumn() > 0);
            }

            // Hospitalisation dans ce service (y compris sortis)
            if (!$canAccess) {
                $stmtAccess = $this->db->prepare(
                    "SELECT COUNT(*) FROM hospitalisations WHERE patient_id = ? AND service_id = ?"
                );
                $stmtAccess->execute([$id, $_SESSION['service_id']]);
                $canAccess = ($stmtAccess->fetchColumn() > 0);
            }

            // Patient en admission aux urgences assigné à ce médecin
            if (!$canAccess) {
                $stmtUrg = $this->db->prepare(
                    "SELECT COUNT(*) FROM urgences_patients WHERE patient_id = ? AND medecin_id = ?"
                );
                $stmtUrg->execute([$id, $_SESSION['user_id']]);
                $canAccess = ($stmtUrg->fetchColumn() > 0);
            }

            if (!$canAccess) {
                $this->audit->logAction('READ', 'patients', $id, null, "ACCÈS REFUSÉ : hors service");
                die("Accès Interdit : Ce patient n'appartient pas à votre service.");
            }
        }

        // 3. Traçabilité : Enregistrer que le dossier a été consulté
        $this->audit->logRead('patients', $id, "Consultation du dossier complet");

        // 3b. Enrichissement antécédents depuis la dernière consultation si champs vides dans patients
        if (empty($patient['allergies']) || empty($patient['antecedents_medicaux'])) {
            $stmtAtcd = $this->db->prepare("
                SELECT atcd_medicaux, atcd_chirurgicaux, atcd_familiaux, atcd_allergies
                FROM consultations
                WHERE patient_id = ?
                ORDER BY date_consultation DESC
                LIMIT 1
            ");
            $stmtAtcd->execute([$id]);
            $atcd = $stmtAtcd->fetch(PDO::FETCH_ASSOC);
            if ($atcd) {
                if (empty($patient['allergies']))            $patient['allergies']               = $atcd['atcd_allergies']   ?? '';
                if (empty($patient['antecedents_medicaux'])) $patient['antecedents_medicaux']    = $atcd['atcd_medicaux']    ?? '';
                if (empty($patient['antecedents_chirurgicaux'])) $patient['antecedents_chirurgicaux'] = $atcd['atcd_chirurgicaux'] ?? '';
                if (empty($patient['antecedents_familiaux'])) $patient['antecedents_familiaux']  = $atcd['atcd_familiaux']   ?? '';
            }
        }

        // 4. Récupération de l'historique des consultations avec le nom du médecin
        $queryConsults = "SELECT c.*, u.nom as medecin_nom, u.prenom as medecin_prenom
                          FROM consultations c
                          LEFT JOIN users u ON c.medecin_id = u.id
                          WHERE c.patient_id = :patient_id
                          ORDER BY c.date_consultation DESC";

        $stmtC = $this->db->prepare($queryConsults);
        $stmtC->bindParam(':patient_id', $id);
        $stmtC->execute();
        $consultations = $stmtC->fetchAll(PDO::FETCH_ASSOC);

        // 5. GESTION DES PARAMÈTRES VITAUX (Solution à votre problème d'affichage)
        $parametres_vitaux = [];
        $parametres = null; // Contiendra uniquement la mesure la plus récente

        if(method_exists($this->patientModel, 'getParametres')) {
            // On récupère les 10 derniers relevés pour l'onglet historique ou les graphiques
            $parametres_vitaux = $this->patientModel->getParametres($id, 10);

            // On extrait le premier résultat (le plus récent grâce au ORDER BY DESC dans le modèle)
            // C'est cette variable $parametres que vos widgets utilisent
            if (!empty($parametres_vitaux)) {
                $parametres = $parametres_vitaux[0];
            }
        }

        // 5. Récupération des derniers bilans et résultats
$queryBilans = "
    SELECT re.id, re.resultat, re.date_resultat, re.interpretation,
           dl.patient_id, dl.id as demande_id, dl.created_at as date_demande,
           dl.statut as statut_demande, el.nom as nom_examen, el.categorie,
           u.nom as medecin_prescripteur
    FROM resultats_examens re
    JOIN demande_examens de ON re.examen_id = de.id
    JOIN demandes_laboratoire dl ON de.demande_id = dl.id
    LEFT JOIN examens_laboratoire el ON de.examen_id = el.id
    LEFT JOIN users u ON dl.medecin_id = u.id
    WHERE dl.patient_id = :patient_id
    ORDER BY re.date_resultat DESC, re.id DESC";

$stmtB = $this->db->prepare($queryBilans);
$stmtB->bindParam(':patient_id', $id);
$stmtB->execute();
$bilans = $stmtB->fetchAll(PDO::FETCH_ASSOC);

        // 6. Récupération des documents numérisés (Radios, PDF, etc.)
        $queryDocs = "SELECT * FROM patient_documents
                      WHERE patient_id = :patient_id
                      ORDER BY date_upload DESC";
        $stmtD = $this->db->prepare($queryDocs);
        $stmtD->bindParam(':patient_id', $id);
        $stmtD->execute();
        $documents = $stmtD->fetchAll(PDO::FETCH_ASSOC);

        // Récupérer l'historique des soins exécutés
$stmtH = $this->db->prepare("
    SELECT sh.id, sh.type_soin as soin_description, sh.type_soin as categorie,
           sh.description, sh.date_realisee as date_execution,
           sh.statut, sh.note_execution,
           u.nom as infirmier_nom
    FROM soins_hospitalisation sh
    LEFT JOIN users u ON sh.user_id_executant = u.id
    WHERE sh.admission_id IN (SELECT id FROM hospitalisations WHERE patient_id = ?)
    AND sh.statut IN ('REALISE', 'realise')
    ORDER BY sh.date_realisee DESC
");
$stmtH->execute([$id]);
$history = $stmtH->fetchAll(PDO::FETCH_ASSOC);

        //var_dump($parametres); die();

        // 7. Comptes-rendus d'hospitalisation (Mes Documents)
        $stmtCRH = $this->db->prepare("
            SELECT crh.id, crh.date_entree, crh.date_sortie, crh.signe, crh.created_at,
                   u.nom as medecin_nom, u.prenom as medecin_prenom
            FROM comptes_rendus_hosp crh
            JOIN users u ON crh.medecin_id = u.id
            WHERE crh.patient_id = ?
            ORDER BY crh.created_at DESC
        ");
        $stmtCRH->execute([$id]);
        $comptes_rendus = $stmtCRH->fetchAll(PDO::FETCH_ASSOC);

        // 8. Prescriptions médicaments — ancien système (prescriptions) + nouveau (ordonnances_pharmacie)
        $prescriptions = [];
        try {
            // Ancien système
            $stmtPres = $this->db->prepare("
                SELECT p.id as prescription_id, p.date_prescription, p.statut as statut_prescription,
                       p.numero_ordonnance,
                       lp.posologie, lp.duree, lp.frequence, lp.quantite, lp.voie,
                       m.nom as medicament_nom, m.forme, m.dosage,
                       u.nom as medecin_nom
                FROM prescriptions p
                JOIN lignes_prescription lp ON p.id = lp.prescription_id
                JOIN medicaments m ON lp.medicament_id = m.id
                LEFT JOIN users u ON p.medecin_id = u.id
                WHERE p.patient_id = ?
                ORDER BY p.date_prescription DESC
            ");
            $stmtPres->execute([$id]);
            $prescriptions = $stmtPres->fetchAll(PDO::FETCH_ASSOC);

            // Nouveau système (ordonnances_pharmacie créées via PharmacieService)
            $stmtOP = $this->db->prepare("
                SELECT CONCAT('op_', op.id) as prescription_id,
                       op.date_creation as date_prescription,
                       op.statut as statut_prescription,
                       NULL as numero_ordonnance,
                       om.posologie, om.duree,
                       NULL as frequence, om.quantite, NULL as voie,
                       COALESCE(om.nom_medicament, m.nom, 'Médicament') as medicament_nom,
                       COALESCE(m.forme, '') as forme,
                       COALESCE(m.dosage, '') as dosage,
                       u.nom as medecin_nom
                FROM ordonnances_pharmacie op
                JOIN consultations c ON c.id = op.consultation_id
                JOIN ordonnance_medicaments om ON om.ordonnance_id = op.id
                LEFT JOIN medicaments m ON m.id = om.medicament_id
                LEFT JOIN users u ON u.id = c.medecin_id
                WHERE c.patient_id = ?
                ORDER BY op.date_creation DESC
            ");
            $stmtOP->execute([$id]);
            $nouvelles = $stmtOP->fetchAll(PDO::FETCH_ASSOC);

            // Fusion : nouvelles en premier, puis anciennes
            $prescriptions = array_merge($nouvelles, $prescriptions);
        } catch (PDOException $e) { error_log("prescriptions dossier: " . $e->getMessage()); }

        // 9. Bilans demandés (avec statut et noms d'examens via demande_examens)
        $bilans_demandes = [];
        try {
            $stmtBD = $this->db->prepare("
                SELECT dl.id, dl.statut, dl.date_creation, dl.urgence,
                       (SELECT GROUP_CONCAT(el2.nom ORDER BY el2.nom SEPARATOR ', ')
                        FROM demande_examens de2
                        JOIN examens_laboratoire el2 ON el2.id = de2.examen_id
                        WHERE de2.demande_id = dl.id) as nom_examen,
                       (SELECT el3.categorie FROM demande_examens de3
                        JOIN examens_laboratoire el3 ON el3.id = de3.examen_id
                        WHERE de3.demande_id = dl.id LIMIT 1) as categorie,
                       u.nom as medecin_nom, u.prenom as medecin_prenom,
                       prl.resultat, prl.interpretation, prl.valeur_reference,
                       prl.date_resultat,
                       NULL as valeur_numerique, NULL as unite, NULL as anormal,
                       NULL as valeur_normale_min, NULL as valeur_normale_max
                FROM demandes_laboratoire dl
                LEFT JOIN users u ON dl.medecin_id = u.id
                LEFT JOIN demande_examens dex ON dex.demande_id = dl.id
                LEFT JOIN resultats_examens prl ON prl.examen_id = dex.id
                WHERE dl.patient_id = ?
                GROUP BY dl.id, prl.id
                ORDER BY dl.date_creation DESC
            ");
            $stmtBD->execute([$id]);
            $bilans_demandes = $stmtBD->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) { error_log("bilans_demandes dossier: " . $e->getMessage()); }

        // 9b. Bilans imagerie/radiologie
        $bilans_imagerie = [];
        try {
            $stmtIM = $this->db->prepare("
                SELECT di.id, di.statut, di.date_creation, di.urgence,
                       CONCAT(UPPER(di.type_examen), ' — ', di.partie_corps) as nom_examen,
                       'Imagerie' as categorie,
                       u.nom as medecin_nom, u.prenom as medecin_prenom,
                       di.interpretation as resultat, di.conclusion as interpretation,
                       di.date_resultats as date_resultat,
                       di.fichier_preview, di.type_examen, di.partie_corps,
                       di.avec_contraste, di.description
                FROM demandes_imagerie di
                LEFT JOIN users u ON di.medecin_id = u.id
                WHERE di.patient_id = ?
                ORDER BY di.date_creation DESC
            ");
            $stmtIM->execute([$id]);
            $bilans_imagerie = $stmtIM->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) { error_log("bilans_imagerie dossier: " . $e->getMessage()); }

        // 10. Chargement de la vue avec toutes les variables préparées
        require_once __DIR__ . '/../views/patients/dossier.php';
    }

    /**
     * Page dédiée : liste de tous les patients du service avec recherche dynamique
     */
    public function mesPatients() {
        $serviceId = $_SESSION['service_id'];
        $search    = trim($_GET['q'] ?? '');
        $page      = max(1, (int)($_GET['page'] ?? 1));
        $limit     = 20;
        $offset    = ($page - 1) * $limit;

        $params = [':sid' => $serviceId, ':sid2' => $serviceId];
        $whereSearch = '';
        if ($search !== '') {
            $whereSearch = " AND (p.nom LIKE :q OR p.prenom LIKE :q OR p.dossier_numero LIKE :q)";
            $params[':q'] = "%$search%";
        }

        $sql = "
            SELECT DISTINCT p.id, p.nom, p.prenom, p.dossier_numero, p.statut, p.date_naissance,
                h.id as hosp_id, h.statut as statut_hosp, h.date_admission, h.date_sortie_effective
            FROM patients p
            LEFT JOIN hospitalisations h ON h.id = (
                SELECT MAX(h2.id) FROM hospitalisations h2 WHERE h2.patient_id = p.id
            )
            WHERE (p.service_id = :sid
               OR EXISTS (SELECT 1 FROM hospitalisations hx WHERE hx.patient_id = p.id AND hx.service_id = :sid2))
            $whereSearch
            ORDER BY p.nom ASC
            LIMIT $limit OFFSET $offset
        ";
        $stmtP = $this->db->prepare($sql);
        $stmtP->execute($params);
        $patients_liste = $stmtP->fetchAll(PDO::FETCH_ASSOC);

        // Total pour la pagination
        $sqlCount = "
            SELECT COUNT(DISTINCT p.id)
            FROM patients p
            LEFT JOIN hospitalisations h ON h.patient_id = p.id
            WHERE (p.service_id = :sid
               OR h.service_id = :sid2)
            $whereSearch
        ";
        $stmtCount = $this->db->prepare($sqlCount);
        $stmtCount->execute($params);
        $total = (int)$stmtCount->fetchColumn();
        $total_pages = ceil($total / $limit);

        // Réponse JSON pour la recherche AJAX
        if (!empty($_GET['ajax'])) {
            header('Content-Type: application/json');
            echo json_encode(['patients' => $patients_liste, 'total' => $total]);
            exit;
        }

        require_once __DIR__ . '/../views/patients/mes_patients.php';
    }

    /**
     * Upload de documents
     */
    public function uploadDocument() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['document'])) {
            $patient_id = $_POST['patient_id'];
            $categorie = htmlspecialchars($_POST['categorie'] ?? 'Autre');
            $description = htmlspecialchars($_POST['description'] ?? '');

            $file = $_FILES['document'];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $filename = 'DOC_' . $patient_id . '_' . time() . '_' . rand(100, 999) . '.' . $ext;
            $upload_dir = __DIR__ . '/../../public/uploads/documents/';

            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

            if (move_uploaded_file($file['tmp_name'], $upload_dir . $filename)) {
                $sql = "INSERT INTO patient_documents (patient_id, nom_fichier, chemin_fichier, type_mime, categorie, description, date_upload)
                        VALUES (?, ?, ?, ?, ?, ?, NOW())";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([$patient_id, $file['name'], $filename, $file['type'], $categorie, $description]);

                $this->audit->logAction('ADD', 'documents', $patient_id, null, "Upload document : $categorie");
                header("Location: " . BASE_URL . "patients/dossier/" . $patient_id . "?success=upload");
            } else {
                header("Location: " . BASE_URL . "patients/dossier/" . $patient_id . "?error=upload_failed");
            }
            exit();
        }
    }

    private function enregistrerPathologies($patient_id, $post_data) {
        require_once __DIR__ . '/../models/Registre.php';
        $registreModel = new Registre();
        foreach ($post_data['pathologies'] as $pathologie) {
            $data = [
                ':patient_id' => $patient_id,
                ':type_maladie' => $pathologie,
                ':date_diagnostic' => date('Y-m-d'),
                ':stade' => ($pathologie === 'CANCER') ? ($post_data['cancer_type'] ?? null) : null,
                ':traitement_actuel' => null,
                ':medecin_referent' => $_SESSION['user_id']
            ];
            $registreModel->addMaladieChronique($data);
        }
    }

    // Dans PatientController.php
// Dans app/controllers/PatientController.php
public function partagerDossier() {
    $this->auth->requirePermission('patients', 'write');

    // Vérification de sécurité
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: ' . BASE_URL . 'patients');
        exit;
    }

    $patient_id = $_POST['patient_id'];
    $destinataire_id = $_POST['destinataire_id'];
    $expediteur_id = $_SESSION['user_id'];
    $service_id = $_POST['service_id'];

    // Partage valable 1 heure
    $date_exp = date('Y-m-d H:i:s', strtotime('+1 hour'));

    $stmt = $this->db->prepare("
        INSERT INTO partages_dossiers
        (patient_id, expediteur_id, destinataire_id, service_id, date_expiration)
        VALUES (?, ?, ?, ?, ?)
    ");

    $success = $stmt->execute([$patient_id, $expediteur_id, $destinataire_id, $service_id, $date_exp]);

    if ($success) {
        header('Location: ' . BASE_URL . 'patients/dossier/'.$patient_id.'?success=partage');
    } else {
        header('Location: ' . BASE_URL . 'patients/dossier/'.$patient_id.'?error=echec_partage');
    }
    exit;
}
}