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
        $userRole = $_SESSION['user_role'] ?? '';
        $userId = $_SESSION['user_id'] ?? 0;
        $serviceId = $_SESSION['service_id'] ?? 0;
        $db = (new Database())->getConnection();

        // ============================================================
        // 1. LOGIQUE POUR L'ADMINISTRATEUR (KPI TEMPS RÉEL)
        // ============================================================
        if ($userRole === 'ADMIN') {
            $stats = [];
            $stats['total_patients'] = $db->query("SELECT COUNT(*) FROM patients")->fetchColumn() ?: 0;
            $stats['hosp_actuelles'] = $db->query("SELECT COUNT(*) FROM admissions WHERE statut = 'EN_COURS'")->fetchColumn() ?: 0;

            $sqlCA = "SELECT SUM(montant_ttc) FROM factures
                      WHERE statut = 'payee'
                      AND MONTH(date_facture) = MONTH(CURRENT_DATE())
                      AND YEAR(date_facture) = YEAR(CURRENT_DATE())";
            $stats['ca_du_mois'] = $db->query($sqlCA)->fetchColumn() ?: 0;
            $stats['alertes_stock'] = $db->query("SELECT COUNT(*) FROM medicaments WHERE quantite <= seuil_alerte")->fetchColumn() ?: 0;

            $system_status = [
                'CPU' => ['value' => (function_exists('sys_getloadavg') ? round(sys_getloadavg()[0] * 10, 1) : rand(5,15)), 'unit' => '%'],
                'MEMORY' => ['value' => round(memory_get_usage(true) / 1024 / 1024, 1), 'unit' => 'MB']
            ];

            $stmtLogs = $db->query("
                SELECT al.*, u.nom, u.prenom
                FROM audit_logs al
                LEFT JOIN users u ON al.user_id = u.id
                ORDER BY al.created_at DESC LIMIT 6
            ");
            $recent_logs = $stmtLogs->fetchAll(PDO::FETCH_ASSOC);

            require_once __DIR__ . '/../views/admin/dashboard.php';
            return;
        }

        // ============================================================
        // 2. ROUTAGE AUTOMATIQUE PAR SERVICE
        // ============================================================
        $stmtS = $db->prepare("SELECT nom_service FROM services WHERE id = ?");
        $stmtS->execute([$serviceId]);
        $service = $stmtS->fetch(PDO::FETCH_ASSOC);
        $nomService = $service['nom_service'] ?? 'Général';
        $serviceKey = strtolower(trim($nomService));

        if (stripos($serviceKey, 'urgences') !== false) {
            header('Location: ' . BASE_URL . 'urgences');
            exit;
        }

        if ($userRole === 'SECRETAIRE' || stripos($serviceKey, 'accueil') !== false) {
            header('Location: ' . BASE_URL . 'accueil');
            exit;
        }

        if (stripos($serviceKey, 'paramètres') !== false) {
            header('Location: ' . BASE_URL . 'parametres');
            exit;
        }

        // ============================================================
        // 3. LOGIQUE POUR LES SERVICES STANDARDS
        // ============================================================
        if ($userRole === 'INFIRMIER') {
            $this->loadNurseWardData($db, $serviceId, $userId);
        } elseif ($userRole === 'MEDECIN') {
            $this->loadDoctorWardData($db, $serviceId, $userId);
        } else {
            $stats = ['nom_service' => $nomService];
            require_once __DIR__ . '/../views/dashboard/dashboard_service.php';
        }
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
}