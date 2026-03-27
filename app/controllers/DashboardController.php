<?php
/* ============================================================================
FICHIER : app/controllers/DashboardController.php
CONTRÔLEUR CENTRAL : ROUTAGE DYNAMIQUE ET CALCUL DES KPI TEMPS RÉEL
============================================================================ */

require_once __DIR__ . '/../models/Patient.php';
require_once __DIR__ . '/../services/DataService.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/UnifiedController.php';
require_once __DIR__ . '/../services/AuditService.php';

class DashboardController extends UnifiedController {

    public function __construct() {
        parent::__construct();
        $this->patientModel = new Patient();
    }

   public function index() {
    // 1. Récupération des informations de session
    $userRole = $_SESSION['user_role'] ?? '';
    $userId = $_SESSION['user_id'] ?? 0;
    $serviceId = $_SESSION['service_id'] ?? 0;

    // Connexion à la base de données
    $db = (new Database())->getConnection();

    // ============================================================
    // 1. LOGIQUE POUR L'ADMINISTRATEUR (Vue Globale)
    // ============================================================
    if ($userRole === 'ADMIN') {
        $dashboardData = $this->dataService->getDashboardData();
        $stats = $dashboardData['patients'];
        require_once __DIR__ . '/../views/admin/dashboard.php';
        return;
    }

    // ============================================================
    // 2. RÉCUPÉRATION ET NORMALISATION DU SERVICE
    // ============================================================
    $stmtS = $db->prepare("SELECT nom_service FROM services WHERE id = ?");
    $stmtS->execute([$serviceId]);
    $service = $stmtS->fetch(PDO::FETCH_ASSOC);
    $nomService = $service['nom_service'] ?? 'Général';

    // Nettoyage de la chaîne pour faciliter les comparaisons (minuscules, sans espaces inutiles)
    $serviceKey = strtolower(trim($nomService));

    // ============================================================
    // 3. ROUTAGE PAR SERVICE SPÉCIFIQUE (COCKPITS)
    // ============================================================

    // A. SERVICE DES URGENCES
    if (stripos($serviceKey, 'urgences') !== false) {
        header('Location: ' . BASE_URL . 'urgences');
        exit;
    }

    // B. SERVICE D'ACCUEIL / RÉCEPTION
    if ($userRole === 'SECRETAIRE' || stripos($serviceKey, 'accueil') !== false) {
        header('Location: ' . BASE_URL . 'accueil');
        exit;
    }

    // C. BUREAUX DES PARAMÈTRES (TRI)
    if (stripos($serviceKey, 'paramètres') !== false) {
        header('Location: ' . BASE_URL . 'parametres');
        exit;
    }

    // D. SERVICE PHARMACIE
    if ($userRole === 'PHARMACIEN' || stripos($serviceKey, 'pharmacie') !== false) {
        $this->loadPharmacistDashboardData($db, $userId);
        return;
    }

    // E. SERVICE IMAGERIE MÉDICALE
    if (stripos($serviceKey, 'imagerie') !== false) {
        $this->loadImagingDashboardData($db, $userId);
        return;
    }

    // ============================================================
    // 4. ROUTAGE CLINIQUE STANDARD (Médecine, Chirurgie, Pédia...)
    // ============================================================

    // Logique pour les INFIRMIERS de service (Gestion lits, soins, admissions)
    if ($userRole === 'INFIRMIER') {
        $this->loadNurseWardData($db, $serviceId, $userId);
        return;
    }

    // Logique pour les MÉDECINS de service (Consultations, hospitalisés, résultats)
    if ($userRole === 'MEDECIN') {
        $this->loadDoctorWardData($db, $serviceId, $userId);
        return;
    }

    // ============================================================
    // 5. FALLBACK (VUE PAR DÉFAUT)
    // ============================================================
    // Si aucun routage spécifique n'est trouvé
    $stats = ['nom_service' => $nomService];
    require_once __DIR__ . '/../views/dashboard/dashboard_service.php';
}

   private function loadPharmacistDashboardData($db, $userId) {
    // 1. INITIALISATION DES VARIABLES (Pour éviter les "Undefined variable")
    $total_refs = 0;
    $total_alerte = 0;
    $processed_today = 0;
    $pending_count = 0;
    $pending_orders = [];
    $low_stock = [];

    try {
        // A. Nombre de références totales
        $total_refs = $db->query("SELECT COUNT(*) FROM medicaments")->fetchColumn() ?: 0;

        // B. Nombre de produits en alerte (Correction du nom de la variable)
        $total_alerte = $db->query("SELECT COUNT(*) FROM medicaments WHERE quantite <= seuil_alerte")->fetchColumn() ?: 0;

        // C. Ordonnances traitées aujourd'hui
        $processed_today = $db->query("SELECT COUNT(*) FROM ordonnances_pharmacie WHERE statut = 'TRAITEE' AND DATE(date_traitement) = CURDATE()")->fetchColumn() ?: 0;

        // D. Ordonnances en attente
        $pending_count = $db->query("SELECT COUNT(*) FROM ordonnances_pharmacie WHERE statut = 'SIGNEE'")->fetchColumn() ?: 0;

        // E. Liste des ordonnances (Jointure réelle)
        $stmtOrders = $db->prepare("SELECT o.*, p.nom, p.prenom, p.dossier_numero, u.nom as medecin_nom,
                                   TIMESTAMPDIFF(MINUTE, o.date_creation, NOW()) as minutes_attente
                                   FROM ordonnances_pharmacie o
                                   JOIN patients p ON o.patient_id = p.id
                                   JOIN users u ON o.medecin_id = u.id
                                   WHERE o.statut = 'SIGNEE'
                                   ORDER BY o.date_creation ASC");
        $stmtOrders->execute();
        $pending_orders = $stmtOrders->fetchAll(PDO::FETCH_ASSOC);

        // F. Liste des alertes de stock (On force les noms des colonnes 'nom' et 'quantite')
        $stmtStock = $db->query("SELECT nom, forme, dosage, quantite FROM medicaments WHERE quantite <= seuil_alerte ORDER BY quantite ASC LIMIT 5");
        $low_stock = $stmtStock->fetchAll(PDO::FETCH_ASSOC);

    } catch (Exception $e) {
        error_log("Erreur Pharmacie : " . $e->getMessage());
    }

    // Chargement de la vue (les variables ci-dessus sont automatiquement extraites)
    require_once __DIR__ . '/../views/pharmacie/dashboard.php';
}

   private function loadNurseWardData($db, $serviceId, $userId) {
    // A. Patients déjà hospitalisés (statut 'en_cours') dans le service de l'infirmier
    $stmtP = $db->prepare("SELECT p.*, h.id as hosp_id, h.statut, l.nom_lit, c.nom_chambre
        FROM patients p
        JOIN hospitalisations h ON p.id = h.patient_id
        LEFT JOIN lits l ON h.lit_id = l.id
        LEFT JOIN chambres c ON l.chambre_id = c.id
        WHERE h.service_id = ? AND h.statut = 'en_cours'");
    $stmtP->execute([$serviceId]);
    $patients_service = $stmtP->fetchAll(PDO::FETCH_ASSOC);

    // B. État des lits du service (pour la grille de disponibilité)
    $stmtL = $db->prepare("SELECT l.*, c.nom_chambre, p.nom, p.prenom, p.id as patient_id_reel
        FROM lits l
        JOIN chambres c ON l.chambre_id = c.id
        LEFT JOIN patients p ON l.patient_id = p.id
        WHERE c.service_id = ?
        ORDER BY c.nom_chambre ASC, l.nom_lit ASC");
    $stmtL->execute([$serviceId]);
    $lits_service = $stmtL->fetchAll(PDO::FETCH_ASSOC);

    // C. Planning des soins AGRÉGÉ (Checklist du jour sans doublons)
    // On calcule le nombre total de soins par patient VS le nombre de soins déjà cochés
    $stmtS = $db->prepare("
        SELECT
            sp.id as plan_id,
            p.nom, p.prenom, p.id as patient_id,
            COUNT(sd.id) as total_soins,
            SUM(CASE WHEN sd.execute = 1 THEN 1 ELSE 0 END) as soins_faits
        FROM soins_planification sp
        JOIN patients p ON sp.patient_id = p.id
        LEFT JOIN soins_details sd ON sp.id = sd.plan_id
        WHERE p.service_id = ?
        AND sp.date_plan = CURDATE()
        GROUP BY sp.id, p.nom, p.prenom, p.id
        ORDER BY sp.id DESC
    ");
    $stmtS->execute([$serviceId]);
    $plans_du_jour = $stmtS->fetchAll(PDO::FETCH_ASSOC);

    // D. Disponibilité globale des lits dans tout l'hôpital (pour info transferts)
    $sqlGlobal = "SELECT s.nom_service as service, COUNT(l.id) as total,
              SUM(CASE WHEN l.statut = 'OCCUPE' THEN 1 ELSE 0 END) as occupes
              FROM services s
              LEFT JOIN chambres c ON s.id = c.service_id
              LEFT JOIN lits l ON c.id = l.chambre_id
              WHERE s.nom_service IN ('Chirurgie', 'Pédiatrie', 'Maternité', 'Urgences', 'Médecine Générale')
              GROUP BY s.id, s.nom_service
              ORDER BY FIELD(s.nom_service, 'Urgences', 'Médecine Générale', 'Chirurgie', 'Maternité', 'Pédiatrie')";

$lits_global = $db->query($sqlGlobal)->fetchAll(PDO::FETCH_ASSOC);

    // E. Alertes : À Hospitaliser (Patients envoyés par les médecins mais non encore installés)
    $sqlHosp = "SELECT p.*, c.id as consult_id, u.nom as medecin_nom
                FROM patients p
                INNER JOIN consultations c ON p.id = c.patient_id
                INNER JOIN users u ON c.medecin_id = u.id
                WHERE p.statut_hosp = 'A_HOSPITALISER'
                AND p.service_id = ?
                AND c.id = (SELECT MAX(id) FROM consultations WHERE patient_id = p.id)";

    $stmtA = $db->prepare($sqlHosp);
    $stmtA->execute([$serviceId]);
    $a_hospitaliser = $stmtA->fetchAll(PDO::FETCH_ASSOC);

    // Gestion du rafraîchissement partiel via AJAX
    if (isset($_GET['ajax_soins'])) {
        require_once __DIR__ . '/../views/dashboard/partials/soins_list.php';
        exit;
    }

    // Chargement de la vue principale
    require_once __DIR__ . '/../views/dashboard/dashboard_infirmier.php';
}

    private function loadDoctorWardData($db, $serviceId, $userId) {
    // 1. INITIALISATION DES VARIABLES (Évite les erreurs "undefined variable" ou "count() on null")
    $patients_assignes = [];
    $patients_hospitalises = [];
    $resultats_prets = [];
    $mes_rdv = [];
    $patients_consultes = [];
    $mes_taches = [];

    try {
        // 2. RÉCUPÉRATION DES PATIENTS EN SALLE D'ATTENTE (Consultations externes / Urgences)
        // On ne récupère que ceux du service du médecin qui attendent une consultation
        $stmtA = $db->prepare("SELECT p.*, ut.motif_plainte, ut.niveau_gravite
                              FROM patients p
                              LEFT JOIN urgences_triage ut ON ut.id = (SELECT MAX(id) FROM urgences_triage WHERE patient_id = p.id)
                              WHERE p.service_id = ?
                              AND p.statut_parcours = 'ATTENTE_CONSULTATION'
                              AND p.actif = 1
                              ORDER BY p.numero_ordre ASC");
        $stmtA->execute([$serviceId]);
        $patients_assignes = $stmtA->fetchAll(PDO::FETCH_ASSOC);

        // 3. RÉCUPÉRATION DES PATIENTS HOSPITALISÉS (CLOISONNEMENT STRICT PAR SERVICE)
        // Le médecin ne voit que les patients installés sur un lit dans SON service
        $stmtHosp = $db->prepare("SELECT p.*, h.date_admission, l.nom_lit, c.nom_chambre
                                 FROM patients p
                                 JOIN hospitalisations h ON p.id = h.patient_id
                                 JOIN lits l ON h.lit_id = l.id
                                 JOIN chambres c ON l.chambre_id = c.id
                                 WHERE h.service_id = ?
                                 AND h.statut = 'en_cours'
                                 ORDER BY c.nom_chambre ASC, l.nom_lit ASC");
        $stmtHosp->execute([$serviceId]);
        $patients_hospitalises = $stmtHosp->fetchAll(PDO::FETCH_ASSOC);

        // 4. RÉCUPÉRATION DES RÉSULTATS DE LABORATOIRE (Non lus)
        // Résultats des examens prescrits par ce médecin spécifique
        $stmtR = $db->prepare("SELECT prl.*, p.nom, p.prenom
                              FROM patient_resultats_labo prl
                              JOIN patients p ON prl.patient_id = p.id
                              WHERE prl.medecin_prescripteur_id = ?
                              AND prl.statut_validation = 'NON_LU'
                              ORDER BY prl.date_resultat DESC");
        $stmtR->execute([$userId]);
        $resultats_prets = $stmtR->fetchAll(PDO::FETCH_ASSOC);

        // 5. RÉCUPÉRATION DES RENDEZ-VOUS DU JOUR
        $stmtRDV = $db->prepare("SELECT r.*, p.nom, p.prenom, p.dossier_numero
                                FROM patient_rdv r
                                JOIN patients p ON r.patient_id = p.id
                                WHERE r.medecin_id = ?
                                AND DATE(r.date_rdv) = CURDATE()
                                AND r.statut != 'ANNULE'
                                ORDER BY r.date_rdv ASC");
        $stmtRDV->execute([$userId]);
        $mes_rdv = $stmtRDV->fetchAll(PDO::FETCH_ASSOC);

        // 6. HISTORIQUE DES CONSULTATIONS RÉCENTES (Règle de l'heure pour Hospitaliser)
        // Utilisé pour le bouton "Hosp." qui clignote pendant 60 min après la fin du soin
        $stmtH = $db->prepare("SELECT c.id as consult_id, c.date_consultation, p.id as patient_id,
                                    p.nom, p.prenom, p.dossier_numero, p.statut_hosp,
                                    CASE WHEN c.wait_hospital_until > NOW() THEN 1 ELSE 0 END as can_hospitaliser
                             FROM consultations c
                             JOIN patients p ON c.patient_id = p.id
                             WHERE c.medecin_id = ?
                             AND c.date_consultation > DATE_SUB(NOW(), INTERVAL 24 HOUR)
                             ORDER BY c.date_consultation DESC LIMIT 10");
        $stmtH->execute([$userId]);
        $patients_consultes = $stmtH->fetchAll(PDO::FETCH_ASSOC);

        // 7. TO-DO LIST (Tâches personnelles du médecin)
        $stmtT = $db->prepare("SELECT * FROM user_todo WHERE user_id = ? ORDER BY is_done ASC, created_at DESC LIMIT 10");
        $stmtT->execute([$userId]);
        $mes_taches = $stmtT->fetchAll(PDO::FETCH_ASSOC);

       // 2. RÉCUPÉRATION DU SUIVI DES BILANS (Labo + Radio)
        // Cette requête récupère les demandes qui n'ont pas encore été validées/lues
    $sqlSuivi = "
    (SELECT 'Labo' as type,
            CONCAT(\`Nb examens: \`, COUNT(de.id), \` (\`, GROUP_CONCAT(DISTINCT el.nom SEPARATOR ', ') \`) \`) as label,
            MAX(dl.statut) as statut,
            MAX(dl.date_creation) as date_creation,
            dl.id as record_id,
            MAX(dl.patient_id) as patient_id
     FROM demandes_laboratoire dl
     JOIN demande_examens de ON dl.id = de.demande_id
     JOIN examens_laboratoire el ON de.examen_id = el.id
     WHERE dl.medecin_id = ? AND dl.statut != 'VALIDES'
     GROUP BY dl.id)
    UNION
    (SELECT 'Radio' as type, di.partie_code as label, di.statut, di.date_creation, di.id as record_id, di.patient_id
     FROM demandes_imagerie di
     WHERE di.medecin_id = ? AND di.statut != 'interprete')
    ORDER BY date_creation DESC LIMIT 10";

$stmtW = $db->prepare($sqlSuivi);
$stmtW->execute([$userId, $userId]);
$suivi_bilans = $stmtW->fetchAll(PDO::FETCH_ASSOC);


    } catch (PDOException $e) {
        // Log de l'erreur en cas de problème SQL
        error_log("Erreur Dashboard Medecin : " . $e->getMessage());
    }

    // 8. APPEL DE LA VUE AVEC TOUTES LES DONNÉES PRÉPARÉES
    require_once __DIR__ . '/../views/dashboard/dashboard_medecin.php';
}

    public function addTask() {
        header('Content-Type: application/json');
        $db = (new Database())->getConnection();
        $stmt = $db->prepare("INSERT INTO user_todo (user_id, label) VALUES (?, ?)");
        $success = $stmt->execute([$_SESSION['user_id'], htmlspecialchars($_POST['label'])]);
        echo json_encode(['success' => $success, 'id' => $db->lastInsertId(), 'label' => $_POST['label']]);
    }

    public function hospitaliserConsult() {
        header('Content-Type: application/json');
        $db = (new Database())->getConnection();
        $stmt = $db->prepare("SELECT patient_id FROM consultations WHERE id = ?");
        $stmt->execute([$_POST['consult_id']]);
        $p = $stmt->fetch();
        if ($p) {
            $db->prepare("UPDATE patients SET statut_hosp = 'A_HOSPITALISER' WHERE id = ?")->execute([$p['patient_id']]);
            echo json_encode(['success' => true]);
        }
    }

    private function loadImagingDashboardData($db, $userId) {
    // 1. Statistiques du jour
    $stats = [
        'en_attente' => $db->query("SELECT COUNT(*) FROM demandes_imagerie WHERE statut = 'EN_ATTENTE'")->fetchColumn(),
        'a_interpreter' => $db->query("SELECT COUNT(*) FROM demandes_imagerie WHERE statut = 'termine'")->fetchColumn(),
        'termines' => $db->query("SELECT COUNT(*) FROM demandes_imagerie WHERE statut = 'interprete' AND DATE(date_resultats) = CURDATE()")->fetchColumn()
    ];

    // 2. Liste des examens (pour votre index.php)
    $stmt = $db->prepare("
        SELECT i.*, p.nom, p.prenom, p.dossier_numero, u.nom as medecin_nom
        FROM demandes_imagerie i
        JOIN patients p ON i.patient_id = p.id
        JOIN users u ON i.medecin_id = u.id
        ORDER BY (i.urgence = 'URGENT') DESC, i.date_creation DESC
    ");
    $stmt->execute();
    $examens = $stmt->fetchAll(PDO::FETCH_ASSOC);

    require_once __DIR__ . '/../views/imagerie/index.php';
}

}