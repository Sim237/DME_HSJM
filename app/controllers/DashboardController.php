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

private $db; // Déclaré ici
    private $patientModel;

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
    if (stripos($serviceKey, 'accueil php') !== false || stripos($serviceKey, 'accueil-php') !== false) {
        header('Location: ' . BASE_URL . 'accueil-php');
        exit;
    }
    if ($userRole === 'SECRETAIRE' || stripos($serviceKey, 'accueil') !== false) {
        header('Location: ' . BASE_URL . 'accueil');
        exit;
    }

    // C. BUREAUX DES PARAMÈTRES (TRI)
    if (stripos($serviceKey, 'paramètres php') !== false || stripos($serviceKey, 'parametres php') !== false) {
        header('Location: ' . BASE_URL . 'parametres-php');
        exit;
    }
    if (stripos($serviceKey, 'paramètres') !== false) {
        header('Location: ' . BASE_URL . 'parametres');
        exit;
    }

    // C2. CONSULTATION EXTERNE PHP
    if (stripos($serviceKey, 'consultation') !== false && stripos($serviceKey, 'php') !== false) {
        header('Location: ' . BASE_URL . 'consultation-ext-php');
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

    // F. DIRECTION GÉNÉRALE
    if ($userRole === 'DIRECTEUR') {
        $this->loadDirectorData($db);
        return;
    }

    // Logique pour le MAJOR (Infirmier Major — supervision de service)
    if ($userRole === 'MAJOR') {
        require_once __DIR__ . '/MajorController.php';
        (new MajorController())->dashboard();
        return;
    }

    // Logique pour les INFIRMIERS de service (Gestion lits, soins, admissions)
    if ($userRole === 'INFIRMIER') {
        $this->loadNurseWardData($db, $serviceId, $userId);
        return;
    }

    // Logique pour les MÉDECINS de service (Consultations, hospitalisés, résultats)
    if ($userRole === 'MEDECIN') {
        $this->loadDoctorWardData($db, $serviceId, $userId, $nomService);
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
        $processed_today = $db->query("SELECT COUNT(*) FROM prescriptions WHERE statut = 'TRAITEE' AND DATE(date_delivrance) = CURDATE()")->fetchColumn() ?: 0;

        // D. Ordonnances en attente
        $pending_count = $db->query("SELECT COUNT(*) FROM prescriptions WHERE statut = 'SIGNEE'")->fetchColumn() ?: 0;

        // E. Liste des ordonnances (Jointure réelle)
        $stmtOrders = $db->prepare("SELECT o.*, p.nom, p.prenom, p.dossier_numero, u.nom as medecin_nom,
                                   TIMESTAMPDIFF(MINUTE, o.date_prescription, NOW()) as minutes_attente
                                   FROM prescriptions o
                                   JOIN patients p ON o.patient_id = p.id
                                   JOIN users u ON o.medecin_id = u.id
                                   WHERE o.statut = 'SIGNEE'
                                   ORDER BY o.date_prescription ASC");
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
        LEFT JOIN patients p ON l.occupied_by_patient_id = p.id
        WHERE c.service_id = ?
        ORDER BY c.nom_chambre ASC, l.nom_lit ASC");
    $stmtL->execute([$serviceId]);
    $lits_service = $stmtL->fetchAll(PDO::FETCH_ASSOC);

    // C. Planning des soins AGRÉGÉ (Checklist du jour sans doublons)
    $stmtS = $db->prepare("
        SELECT
            h.id as plan_id,
            p.nom, p.prenom, p.id as patient_id,
            COUNT(sh.id) as total_soins,
            SUM(CASE WHEN sh.statut = 'realise' THEN 1 ELSE 0 END) as soins_faits
        FROM hospitalisations h
        JOIN patients p ON h.patient_id = p.id
        LEFT JOIN soins_hospitalisation sh ON h.id = sh.admission_id AND DATE(sh.date_realisee) = CURDATE()
        WHERE h.service_id = ?
        AND h.statut = 'en_cours'
        GROUP BY h.id, p.nom, p.prenom, p.id
        ORDER BY h.id DESC
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

    private function loadDoctorWardData($db, $serviceId, $userId, $nomService = '') {
    $isPediatrie = stripos($nomService, 'pédiatr') !== false || stripos($nomService, 'pediatr') !== false;
    // 1. INITIALISATION DES VARIABLES (Évite les erreurs "undefined variable" ou "count() on null")
    $patients_assignes = [];
    $patients_hospitalises = [];
    $resultats_prets = [];
    $mes_rdv = [];
    $patients_consultes = [];
    $mes_taches = [];
    $dossiers_reçus = [];   // <--- AJOUTER CECI
    $dossiers_envoyés = []; // <--- AJOUTER CECI

    try {
        // 2. RÉCUPÉRATION DES PATIENTS EN SALLE D'ATTENTE (Consultations externes / Urgences)
        // On ne récupère que ceux du service du médecin qui attendent une consultation
        $stmtA = $db->prepare("
                              SELECT p.*,
                                     COALESCE(pp.motif_consultation, ut.motif_plainte, 'Consultation') as motif_plainte,
                                     ut.niveau_gravite
                              FROM patients p
                              LEFT JOIN patient_parametres pp ON pp.id = (SELECT MAX(id) FROM patient_parametres WHERE patient_id = p.id)
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
        $stmtR = $db->prepare("
            SELECT re.id, re.resultat, re.date_resultat, re.interpretation,
                   dl.patient_id, dl.statut as statut_demande,
                   el.nom as nom_examen, el.categorie,
                   p.nom, p.prenom
            FROM resultats_examens re
            JOIN demande_examens de ON re.examen_id = de.id
            JOIN demandes_laboratoire dl ON de.demande_id = dl.id
            LEFT JOIN examens_laboratoire el ON de.examen_id = el.id
            JOIN patients p ON dl.patient_id = p.id
            WHERE dl.medecin_id = ?
            AND dl.statut = 'termine'
            ORDER BY re.date_resultat DESC LIMIT 20");
        $stmtR->execute([$userId]);
        $resultats_prets = $stmtR->fetchAll(PDO::FETCH_ASSOC);

        // 5. RÉCUPÉRATION DES RENDEZ-VOUS DU JOUR
        $stmtRDV = $db->prepare("SELECT r.*, p.nom, p.prenom, p.dossier_numero
                                FROM agenda_medical r
                                JOIN patients p ON r.patient_id = p.id
                                WHERE r.medecin_id = ?
                                AND DATE(r.date_debut) = CURDATE()
                                AND r.statut != 'ANNULE'
                                ORDER BY r.date_debut ASC");
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

        // 8. MES PATIENTS DU SERVICE (service + hospitalisations + consultations récentes par ce médecin)
        $mes_patients_service = [];
        try {
            $stmtMP = $db->prepare("
                SELECT DISTINCT p.id, p.nom, p.prenom, p.dossier_numero, p.statut,
                    h.id as hosp_id, h.statut as statut_hosp,
                    h.date_sortie_effective,
                    COALESCE(
                        (SELECT MAX(c2.date_consultation) FROM consultations c2 WHERE c2.patient_id = p.id AND c2.medecin_id = :uid),
                        h.date_admission,
                        p.created_at
                    ) as derniere_activite
                FROM patients p
                LEFT JOIN hospitalisations h ON h.id = (
                    SELECT MAX(h2.id) FROM hospitalisations h2 WHERE h2.patient_id = p.id
                )
                WHERE p.service_id = :sid
                   OR EXISTS (SELECT 1 FROM hospitalisations hx WHERE hx.patient_id = p.id AND hx.service_id = :sid2)
                   OR EXISTS (SELECT 1 FROM consultations cx WHERE cx.patient_id = p.id AND cx.medecin_id = :uid2
                              AND cx.date_consultation >= DATE_SUB(NOW(), INTERVAL 30 DAY))
                ORDER BY derniere_activite DESC
                LIMIT 6
            ");
            $stmtMP->execute([':sid' => $serviceId, ':sid2' => $serviceId, ':uid' => $userId, ':uid2' => $userId]);
            $mes_patients_service = $stmtMP->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) { error_log("mes_patients: " . $e->getMessage()); }

        // 9. PATIENTS LIBÉRÉS SANS COMPTE-RENDU D'HOSPITALISATION (CRH à rédiger)
        $crh_en_attente = [];
        $stmtCRH = $db->prepare("
            SELECT p.id, p.nom, p.prenom, p.dossier_numero,
                   h.id as hosp_id, h.date_admission, h.date_sortie_effective, h.motif_hospitalisation
            FROM hospitalisations h
            JOIN patients p ON h.patient_id = p.id
            LEFT JOIN comptes_rendus_hosp crh ON h.id = crh.hospitalisation_id
            WHERE h.medecin_responsable = ?
              AND h.statut = 'termine'
              AND crh.id IS NULL
            ORDER BY h.date_sortie_effective DESC
            LIMIT 10
        ");
        $stmtCRH->execute([$userId]);
        $crh_en_attente = $stmtCRH->fetchAll(PDO::FETCH_ASSOC);

       // 2. RÉCUPÉRATION DU SUIVI DES BILANS (Labo + Radio)
        $sqlSuivi = "
    (SELECT 'Labo' as type,
            COALESCE(
                (SELECT GROUP_CONCAT(el2.nom SEPARATOR ', ')
                 FROM demande_examens de2
                 JOIN examens_laboratoire el2 ON el2.id = de2.examen_id
                 WHERE de2.demande_id = dl.id LIMIT 1),
                'Bilan laboratoire'
            ) as label,
            dl.statut, dl.date_creation, dl.id as record_id,
            dl.patient_id, p.nom as patient_nom, p.prenom as patient_prenom, p.dossier_numero,
            NULL as anormal, 0 as nb_commentaires, dl.urgence
     FROM demandes_laboratoire dl
     JOIN patients p ON p.id = dl.patient_id
     WHERE dl.medecin_id = ? AND dl.statut NOT IN ('VALIDES','interprete','termine'))
    UNION
    (SELECT 'Radio' as type, COALESCE(di.partie_code, di.type_imagerie, 'Imagerie') as label,
            di.statut, di.date_creation, di.id as record_id,
            di.patient_id, p.nom as patient_nom, p.prenom as patient_prenom, p.dossier_numero,
            NULL as anormal, 0 as nb_commentaires, di.urgence
     FROM demandes_imagerie di
     JOIN patients p ON p.id = di.patient_id
     WHERE di.medecin_id = ? AND di.statut NOT IN ('interprete','VALIDES','termine'))
    ORDER BY date_creation DESC LIMIT 20";

$stmtW = $db->prepare($sqlSuivi);
$stmtW->execute([$userId, $userId]);
$suivi_bilans = $stmtW->fetchAll(PDO::FETCH_ASSOC);

// --- DOSSIERS REÇUS (Pour moi) ---
$stmtReçus = $db->prepare("
    SELECT p.nom, p.prenom, pd.id as partage_id, pd.avis_medecin,
           u.nom as expediteur_nom, pd.date_partage
    FROM partages_dossiers pd
    JOIN patients p ON pd.patient_id = p.id
    JOIN users u ON pd.expediteur_id = u.id
    WHERE pd.destinataire_id = ? AND pd.date_expiration > NOW()
");
$stmtReçus->execute([$userId]);
$dossiers_reçus = $stmtReçus->fetchAll(PDO::FETCH_ASSOC);

// --- DOSSIERS ENVOYÉS (Par moi) ---
$stmtEnvoyés = $db->prepare("
    SELECT p.nom, p.prenom, pd.id as partage_id, pd.avis_medecin,
           u.nom as destinataire_nom, pd.date_partage
    FROM partages_dossiers pd
    JOIN patients p ON pd.patient_id = p.id
    JOIN users u ON pd.destinataire_id = u.id
    WHERE pd.expediteur_id = ? AND pd.date_expiration > NOW()
");
$stmtEnvoyés->execute([$userId]);
$dossiers_envoyés = $stmtEnvoyés->fetchAll(PDO::FETCH_ASSOC);


    } catch (PDOException $e) {
        // Log de l'erreur en cas de problème SQL
        error_log("Erreur Dashboard Medecin : " . $e->getMessage());
    }

    // 8. APPEL DE LA VUE AVEC TOUTES LES DONNÉES PRÉPARÉES
    require_once __DIR__ . '/../views/dashboard/dashboard_medecin.php';
}

    private function loadDirectorData($db) {

        // ── 1. KPIs GLOBAUX ──────────────────────────────────────────────────
        $kpi = [];
        $kpi['total_patients']      = $db->query("SELECT COUNT(*) FROM patients WHERE actif = 1")->fetchColumn();
        $kpi['patients_ce_mois']    = $db->query("SELECT COUNT(*) FROM patients WHERE MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())")->fetchColumn();
        $kpi['consultations_mois']  = $db->query("SELECT COUNT(*) FROM consultations WHERE MONTH(date_consultation) = MONTH(CURDATE()) AND YEAR(date_consultation) = YEAR(CURDATE())")->fetchColumn();
        $kpi['hospitalisations_actives'] = $db->query("SELECT COUNT(*) FROM hospitalisations WHERE statut = 'en_cours'")->fetchColumn();
        $kpi['total_lits']          = $db->query("SELECT COUNT(*) FROM lits")->fetchColumn();
        $kpi['lits_occupes']        = $db->query("SELECT COUNT(*) FROM lits WHERE statut = 'OCCUPE'")->fetchColumn();
        $kpi['taux_occupation']     = $kpi['total_lits'] > 0 ? round(($kpi['lits_occupes'] / $kpi['total_lits']) * 100, 1) : 0;
        $kpi['temps_attente_moy']   = 0;
        try {
            $ta = $db->query("
                SELECT AVG(TIMESTAMPDIFF(MINUTE, p.created_at, c.date_consultation)) as moy
                FROM consultations c
                JOIN patients p ON c.patient_id = p.id
                WHERE c.date_consultation > DATE_SUB(NOW(), INTERVAL 30 DAY)
                  AND c.date_consultation > p.created_at
            ")->fetchColumn();
            $kpi['temps_attente_moy'] = $ta ? round($ta) : 0;
        } catch (PDOException $e) {}

        // ── 2. OCCUPATION DES LITS PAR SERVICE ───────────────────────────────
        $lits_par_service = $db->query("
            SELECT s.nom_service,
                   COUNT(l.id) as total,
                   SUM(CASE WHEN l.statut = 'OCCUPE' THEN 1 ELSE 0 END) as occupes,
                   ROUND(SUM(CASE WHEN l.statut = 'OCCUPE' THEN 1 ELSE 0 END) / COUNT(l.id) * 100, 1) as taux
            FROM services s
            LEFT JOIN chambres c ON s.id = c.service_id
            LEFT JOIN lits l ON c.id = l.chambre_id
            GROUP BY s.id, s.nom_service
            HAVING total > 0
            ORDER BY taux DESC
        ")->fetchAll(PDO::FETCH_ASSOC);

        // ── 3. PATIENTS PAR MOIS (12 DERNIERS MOIS) ──────────────────────────
        $patients_par_mois = $db->query("
            SELECT DATE_FORMAT(created_at, '%Y-%m') as mois,
                   DATE_FORMAT(created_at, '%b %Y') as label,
                   COUNT(*) as nb
            FROM patients
            WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
            GROUP BY mois, label
            ORDER BY mois ASC
        ")->fetchAll(PDO::FETCH_ASSOC);

        // ── 4. CONSULTATIONS VS HOSPITALISATIONS PAR MOIS (6 MOIS) ──────────
        $consult_vs_hosp = $db->query("
            SELECT m.mois, m.label,
                   COALESCE(c.nb_consult, 0) as nb_consult,
                   COALESCE(h.nb_hosp, 0)    as nb_hosp
            FROM (
                SELECT DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL n MONTH), '%Y-%m') as mois,
                       DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL n MONTH), '%b %Y') as label
                FROM (SELECT 0 n UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5) nums
            ) m
            LEFT JOIN (
                SELECT DATE_FORMAT(date_consultation, '%Y-%m') as mois, COUNT(*) as nb_consult
                FROM consultations
                GROUP BY mois
            ) c ON c.mois = m.mois
            LEFT JOIN (
                SELECT DATE_FORMAT(date_admission, '%Y-%m') as mois, COUNT(*) as nb_hosp
                FROM hospitalisations
                GROUP BY mois
            ) h ON h.mois = m.mois
            ORDER BY m.mois ASC
        ")->fetchAll(PDO::FETCH_ASSOC);

        // ── 5. TOP 10 PATHOLOGIES ─────────────────────────────────────────────
        $top_pathologies = $db->query("
            SELECT diagnostic_principal as pathologie, COUNT(*) as nb
            FROM consultations
            WHERE diagnostic_principal IS NOT NULL AND diagnostic_principal != ''
            GROUP BY diagnostic_principal
            ORDER BY nb DESC
            LIMIT 10
        ")->fetchAll(PDO::FETCH_ASSOC);

        // ── 6. MÉDICAMENTS LES PLUS DEMANDÉS ─────────────────────────────────
        $top_medicaments = $db->query("
            SELECT m.nom as nom, SUM(lp.quantite) as total_demande
            FROM lignes_prescription lp
            JOIN medicaments m ON lp.medicament_id = m.id
            GROUP BY m.id, m.nom
            ORDER BY total_demande DESC
            LIMIT 10
        ")->fetchAll(PDO::FETCH_ASSOC);

        // ── 7. STOCKS PHARMACIE ───────────────────────────────────────────────
        $stocks_alerte = $db->query("
            SELECT nom, forme, dosage, quantite, seuil_alerte,
                   CASE WHEN quantite = 0 THEN 'rupture'
                        WHEN quantite <= seuil_alerte THEN 'critique'
                        WHEN quantite <= seuil_alerte * 2 THEN 'alerte'
                        ELSE 'ok' END as niveau
            FROM medicaments
            ORDER BY (quantite / GREATEST(seuil_alerte, 1)) ASC
            LIMIT 15
        ")->fetchAll(PDO::FETCH_ASSOC);

        $kpi['medicaments_rupture'] = count(array_filter($stocks_alerte, fn($m) => $m['niveau'] === 'rupture'));
        $kpi['medicaments_alerte']  = count(array_filter($stocks_alerte, fn($m) => $m['niveau'] === 'critique' || $m['niveau'] === 'alerte'));

        // ── 8. RÉSUMÉ PAR SERVICE ─────────────────────────────────────────────
        $resume_services = $db->query("
            SELECT s.nom_service,
                   COUNT(DISTINCT p.id)  as nb_patients,
                   COUNT(DISTINCT c.id)  as nb_consult,
                   COUNT(DISTINCT h.id)  as nb_hosp_actives,
                   COUNT(DISTINCT l.id)  as total_lits,
                   SUM(CASE WHEN l.statut = 'OCCUPE' THEN 1 ELSE 0 END) as lits_occupes
            FROM services s
            LEFT JOIN patients p   ON p.service_id = s.id AND p.actif = 1
            LEFT JOIN consultations c ON c.service_id = s.id AND c.date_consultation >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
            LEFT JOIN hospitalisations h ON h.service_id = s.id AND h.statut = 'en_cours'
            LEFT JOIN chambres ch  ON ch.service_id = s.id
            LEFT JOIN lits l       ON l.chambre_id = ch.id
            GROUP BY s.id, s.nom_service
            ORDER BY nb_patients DESC
        ")->fetchAll(PDO::FETCH_ASSOC);

        // ── 9. ADMISSIONS PAR HEURE (DISTRIBUTION) ───────────────────────────
        $admissions_par_heure = $db->query("
            SELECT HOUR(date_consultation) as heure, COUNT(*) as nb
            FROM consultations
            WHERE date_consultation >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
            GROUP BY heure
            ORDER BY heure ASC
        ")->fetchAll(PDO::FETCH_ASSOC);

        // ── 10. INDICATEURS FINANCIERS SIMPLES (si table factures existe) ─────
        $revenus_mois = 0;
        try {
            $revenus_mois = $db->query("
                SELECT COALESCE(SUM(montant_ttc), 0)
                FROM factures
                WHERE MONTH(created_at) = MONTH(CURDATE())
                  AND YEAR(created_at) = YEAR(CURDATE())
                  AND statut = 'payee'
            ")->fetchColumn();
        } catch (PDOException $e) {}

        require_once __DIR__ . '/../views/dashboard/dashboard_directeur.php';
    }

    public function kpiDirecteur() {
        header('Content-Type: application/json');
        $db = (new Database())->getConnection();
        echo json_encode([
            'total_patients'           => (int)$db->query("SELECT COUNT(*) FROM patients WHERE actif = 1")->fetchColumn(),
            'hospitalisations_actives' => (int)$db->query("SELECT COUNT(*) FROM hospitalisations WHERE statut = 'en_cours'")->fetchColumn(),
            'lits_occupes'             => (int)$db->query("SELECT COUNT(*) FROM lits WHERE statut = 'OCCUPE'")->fetchColumn(),
            'total_lits'               => (int)$db->query("SELECT COUNT(*) FROM lits")->fetchColumn(),
            'consultations_mois'       => (int)$db->query("SELECT COUNT(*) FROM consultations WHERE MONTH(date_consultation)=MONTH(CURDATE()) AND YEAR(date_consultation)=YEAR(CURDATE())")->fetchColumn(),
            'timestamp'                => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Vue analytique patients pour le Directeur — filtre multi-critères
     */
    public function directeurPatients() {
        if (($_SESSION['user_role'] ?? '') !== 'DIRECTEUR') {
            header('Location: ' . BASE_URL . 'dashboard');
            exit;
        }

        $db = (new Database())->getConnection();

        // Filtres GET
        $type_client = $_GET['type_client'] ?? '';
        $circuit     = $_GET['circuit']     ?? '';
        $service_id  = (int)($_GET['service_id'] ?? 0);
        $pathologie  = trim($_GET['pathologie'] ?? '');
        $date_debut  = $_GET['date_debut'] ?? date('Y-m-01');
        $date_fin    = $_GET['date_fin']   ?? date('Y-m-d');
        $sexe        = $_GET['sexe']       ?? '';
        $per_page    = 50;
        $page        = max(1, (int)($_GET['page'] ?? 1));
        $offset      = ($page - 1) * $per_page;

        // Construction du WHERE dynamique
        $where  = ['p.actif = 1', 'DATE(p.created_at) BETWEEN ? AND ?'];
        $params = [$date_debut, $date_fin];

        if ($type_client !== '') { $where[] = 'p.type_client = ?'; $params[] = $type_client; }
        if ($circuit     !== '') { $where[] = 'p.circuit = ?';     $params[] = $circuit; }
        if ($service_id > 0)     { $where[] = 'p.service_id = ?';  $params[] = $service_id; }
        if ($sexe !== '')        { $where[] = 'p.sexe = ?';        $params[] = $sexe; }
        if ($pathologie !== '') {
            $where[] = 'c.diagnostic_principal LIKE ?';
            $params[] = "%$pathologie%";
        }

        $joinConsult = $pathologie !== ''
            ? 'LEFT JOIN consultations c ON c.patient_id = p.id'
            : 'LEFT JOIN consultations c ON c.patient_id = p.id AND c.id = (SELECT MIN(id) FROM consultations WHERE patient_id = p.id)';
        $whereStr = 'WHERE ' . implode(' AND ', $where);

        // Total
        $stmtCount = $db->prepare("SELECT COUNT(DISTINCT p.id) FROM patients p $joinConsult $whereStr");
        $stmtCount->execute($params);
        $total_rows  = (int)$stmtCount->fetchColumn();
        $total_pages = max(1, ceil($total_rows / $per_page));

        // Données paginées
        $stmtData = $db->prepare("
            SELECT DISTINCT p.id, p.dossier_numero, p.nom, p.prenom, p.sexe, p.date_naissance,
                   p.type_client, p.circuit, p.statut_parcours, p.created_at,
                   s.nom_service,
                   (SELECT diagnostic_principal FROM consultations WHERE patient_id=p.id ORDER BY id ASC LIMIT 1) as pathologie_principale
            FROM patients p
            LEFT JOIN services s ON p.service_id = s.id
            $joinConsult
            $whereStr
            ORDER BY p.created_at DESC
            LIMIT $per_page OFFSET $offset
        ");
        $stmtData->execute($params);
        $patients = $stmtData->fetchAll(PDO::FETCH_ASSOC);

        // Données pour filtres
        $services = $db->query("SELECT id, nom_service FROM services ORDER BY nom_service ASC")->fetchAll(PDO::FETCH_ASSOC);

        // Répartition par type_client
        $repartition = $db->query("
            SELECT type_client, COUNT(*) as nb
            FROM patients
            WHERE actif=1
            GROUP BY type_client
            ORDER BY nb DESC
        ")->fetchAll(PDO::FETCH_ASSOC);

        $filters = compact('type_client','circuit','service_id','pathologie','date_debut','date_fin','sexe');

        require_once __DIR__ . '/../views/dashboard/directeur_patients.php';
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