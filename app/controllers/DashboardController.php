<?php
/**
 * SimCare+ — Dossier Médical Électronique (DME)
 * Copyright (c) 2024-2026 Franck Simeni. Tous droits réservés.
 * Développé pour la gestion hospitalière, et le bien être numérique des patients.
 *
 * Toute reproduction, modification ou distribution de ce logiciel,
 * en tout ou en partie, sans autorisation écrite préalable de l'auteur
 * est strictement interdite et constitue une contrefaçon.
 *
 * Protected under OAPI Agreement — Annexe VII · Berne Convention
 */

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
    if (in_array($userRole, ['ADMIN', 'SUPER_ADMIN'])) {
        require_once __DIR__ . '/AdminDashboardController.php';
        (new AdminDashboardController())->dashboard();
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
    // 3. ROUTAGE PAR RÔLE — PRIORITAIRE sur le service
    //    Les rôles purement infirmiers ont leur propre vue filtrée par
    //    service, indépendamment du nom du service (urgences inclus).
    // ============================================================

    // INFIRMIER / MAJOR_INFIRMIER du service Urgences → cockpit urgences dédié
    if (in_array($userRole, ['INFIRMIER', 'MAJOR_INFIRMIER']) && stripos($serviceKey, 'urgences') !== false) {
        header('Location: ' . BASE_URL . 'urgences');
        exit;
    }

    // INFIRMIER / MAJOR_INFIRMIER / INFIRMIER_CONSULTANT du service PMI → dashboard PMI dédié
    if (in_array($userRole, ['INFIRMIER', 'MAJOR_INFIRMIER', 'INFIRMIER_CONSULTANT'])
        && (stripos($serviceKey, 'pmi') !== false || stripos($serviceKey, 'maternelle et infantile') !== false)) {
        header('Location: ' . BASE_URL . 'pmi');
        exit;
    }

    // INFIRMIER / MAJOR_INFIRMIER du Bloc Opératoire → dashboard bloc dédié
    if (in_array($userRole, ['INFIRMIER', 'MAJOR_INFIRMIER', 'INFIRMIER_CONSULTANT'])
        && stripos($serviceKey, 'bloc') !== false) {
        header('Location: ' . BASE_URL . 'bloc');
        exit;
    }

    // INFIRMIER ANESTHÉSISTE (IADE) → dashboard SSPI (surveillance post-interventionnelle)
    if ($userRole === 'INFIRMIER_ANESTHESISTE') {
        header('Location: ' . BASE_URL . 'bloc/sspi-dashboard');
        exit;
    }

    // INFIRMIER BLOC OPÉRATOIRE (IBODE) → dashboard bloc, quel que soit le service
    if ($userRole === 'INFIRMIER_BLOC') {
        header('Location: ' . BASE_URL . 'bloc');
        exit;
    }

    // INFIRMIER du service Anesthésie → cockpit anesthésie
    if (in_array($userRole, ['INFIRMIER', 'MAJOR_INFIRMIER', 'INFIRMIER_CONSULTANT'])
        && stripos($serviceKey, 'anesth') !== false) {
        $this->loadAnesthesieData($db, $serviceId, $userId);
        return;
    }

    // INFIRMIER / MAJOR_INFIRMIER du service Néonatologie → dashboard néonat dédié
    if (in_array($userRole, ['INFIRMIER', 'MAJOR_INFIRMIER', 'INFIRMIER_CONSULTANT'])
        && (stripos($serviceKey, 'neonat') !== false || stripos($serviceKey, 'néonat') !== false)) {
        header('Location: ' . BASE_URL . 'neonatologie');
        exit;
    }

    // INFIRMIER / MAJOR_INFIRMIER autres services → dashboard infirmier filtré par service
    if (in_array($userRole, ['INFIRMIER', 'MAJOR_INFIRMIER'])) {
        $this->loadNurseWardData($db, $serviceId, $userId);
        return;
    }

    // INFIRMIER_CONSULTANT → interface consultation infirmière dédiée
    if ($userRole === 'INFIRMIER_CONSULTANT') {
        $this->loadNurseConsultantData($db, $serviceId, $userId, $nomService);
        return;
    }

    // ============================================================
    // 4. ROUTAGE PAR SERVICE SPÉCIFIQUE (COCKPITS)
    // ============================================================

    // A. SECRÉTAIRE SAU — priorité absolue, quel que soit le service
    if ($userRole === 'SECRETAIRE_SAU') {
        header('Location: ' . BASE_URL . 'urgences/accueil-secretaire');
        exit;
    }

    // A2. SERVICE DES URGENCES (médecins, secretaires, etc.)
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
    // Rediriger uniquement si c'est le service PERMANENT de l'utilisateur
    // (pas un changement temporaire via "Changer service")
    $isServiceTemporaire = !empty($_SESSION['service_id_origine']);
    if (!$isServiceTemporaire
        && stripos($serviceKey, 'consultation') !== false
        && stripos($serviceKey, 'php') !== false
    ) {
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

    // E2. SERVICE PMI — Protection Maternelle et Infantile
    if (stripos($serviceKey, 'pmi') !== false || stripos($serviceKey, 'maternelle et infantile') !== false) {
        header('Location: ' . BASE_URL . 'pmi');
        exit;
    }

    // ============================================================
    // 5. ROUTAGE CLINIQUE STANDARD (Médecine, Chirurgie, Pédia...)
    // ============================================================

    // F. DIRECTION GÉNÉRALE
    if ($userRole === 'DIRECTEUR') {
        $this->loadDirectorData($db);
        return;
    }

    // Logique pour le MAJOR (Infirmier Major / Chef d'unité) — supervision service
    if ($userRole === 'MAJOR') {
        // Mode "vue infirmier" : le major accède à l'interface infirmier standard de son service
        if (($_GET['vue'] ?? '') === 'infirmier') {
            $this->loadNurseWardData($db, $serviceId, $userId);
            return;
        }
        require_once __DIR__ . '/MajorController.php';
        (new MajorController())->dashboard();
        return;
    }

    // SECRETAIRE SPÉCIALISTE → tableau de bord du secrétariat des spécialistes
    if ($userRole === 'SECRETAIRE_SPECIALISTE') {
        header('Location: ' . BASE_URL . 'specialiste/secretariat');
        exit;
    }

    // SECRETAIRE LABO → tableau de bord du secrétariat du laboratoire
    if ($userRole === 'SECRETAIRE_LABO') {
        header('Location: ' . BASE_URL . 'laboratoire/secretariat');
        exit;
    }

    // COORDONNATEUR DES SOINS → cockpit de supervision infirmière multi-services
    if ($userRole === 'COORDONNATEUR_SOINS') {
        header('Location: ' . BASE_URL . 'coordo-soins/dashboard');
        exit;
    }

    // RADIOLOGUE → interface imagerie médicale
    if ($userRole === 'RADIOLOGUE') {
        $this->loadImagingDashboardData($db, $userId);
        return;
    }

    // ANESTHESISTE → cockpit anesthésie dédié
    if ($userRole === 'ANESTHESISTE') {
        $this->loadAnesthesieData($db, $serviceId, $userId);
        return;
    }

    // Logique pour les MÉDECINS de service (tous les rôles prescripteurs)
    $rolesCliniciens = ['MEDECIN','GENERALISTE','GYNECO','CHIRURGIEN','SAGE_FEMME','PEDIATRE'];
    if (in_array($userRole, $rolesCliniciens)) {
        $this->loadDoctorWardData($db, $serviceId, $userId, $nomService);
        return;
    }

    // ============================================================
    // 5. BUREAU PARAMÈTRES MATERNITÉ
    //    Tout utilisateur non encore routé avec service Maternité
    //    (infirmiers paramètres, aides-soignantes…) → bureau maternité
    // ============================================================
    if (stripos($serviceKey, 'maternit') !== false) {
        header('Location: ' . BASE_URL . 'parametres-maternite');
        exit;
    }

    // ============================================================
    // 6. FALLBACK (VUE PAR DÉFAUT)
    // ============================================================
    // Si aucun routage spécifique n'est trouvé
    $stats = ['nom_service' => $nomService];
    require_once __DIR__ . '/../views/dashboard/dashboard_service.php';
}

    private function loadAnesthesieData($db, $serviceId, $userId): void {
        $patient              = null;
        $age                  = 'N/A';
        $demandes_entrantes   = [];
        $programme_operatoire = [];

        try {
            // Patient actuellement en cours au bloc (assigné à cet anesthésiste en priorité)
            $stmt = $db->prepare("
                SELECT p.*, bs.nom_salle,
                       TIMESTAMPDIFF(YEAR, p.date_naissance, CURDATE()) AS age_calcule,
                       bp.diagnostique_op AS type_intervention
                FROM bloc_programmation bp
                JOIN bloc_demandes bd  ON bd.id = bp.demande_id
                JOIN patients p        ON p.id  = bd.patient_id
                LEFT JOIN bloc_salles bs ON bs.id = bp.salle_id
                WHERE bp.statut = 'EN_COURS'
                ORDER BY (bd.anesthesiste_id = ?) DESC, bp.date_intervention DESC
                LIMIT 1
            ");
            $stmt->execute([$userId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                $patient = $row;
                $age     = $row['age_calcule'] ?? 'N/A';
            }

            // Programme opératoire : interventions PROGRAMME ou EN_COURS aujourd'hui + 3 jours
            // Les interventions assignées à cet anesthésiste apparaissent en premier
            $stmt2 = $db->prepare("
                SELECT
                    bp.id,
                    CONCAT(bp.date_intervention, ' ', COALESCE(bp.heure_debut, '00:00:00')) AS date_prevue,
                    bp.diagnostique_op   AS type_intervention,
                    COALESCE(bp.statut, 'PROGRAMME')                                       AS statut,
                    NULL                                                                    AS duree_prevue,
                    p.id   AS patient_id,
                    p.nom, p.prenom, p.dossier_numero,
                    TIMESTAMPDIFF(YEAR, p.date_naissance, CURDATE())                       AS age_patient,
                    uc.nom AS chirurgien_nom, uc.prenom AS chirurgien_prenom,
                    ua.nom AS anesthesiste_nom, ua.prenom AS anesthesiste_prenom,
                    bs.nom_salle,
                    (bd.anesthesiste_id = ?)                                               AS is_mine
                FROM bloc_programmation bp
                JOIN bloc_demandes bd       ON bd.id  = bp.demande_id
                JOIN patients p             ON p.id   = bd.patient_id
                LEFT JOIN users uc          ON uc.id  = bd.chirurgien_id
                LEFT JOIN users ua          ON ua.id  = bd.anesthesiste_id
                LEFT JOIN bloc_salles bs    ON bs.id  = bp.salle_id
                WHERE bp.statut IN ('PROGRAMME','EN_COURS')
                  AND bp.date_intervention BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 3 DAY)
                ORDER BY is_mine DESC, bp.date_intervention ASC, bp.heure_debut ASC
                LIMIT 20
            ");
            $stmt2->execute([$userId]);
            $programme_operatoire = $stmt2->fetchAll(PDO::FETCH_ASSOC);

            // Demandes en attente de consultation pré-anesthésique
            $stmt3 = $db->prepare("
                SELECT bd.id, bd.created_at AS date_demande,
                       p.id AS patient_id, p.nom, p.prenom, p.dossier_numero,
                       uc.nom AS chirurgien_nom,
                       ua.nom AS anesthesiste_nom, ua.prenom AS anesthesiste_prenom,
                       (bd.anesthesiste_id = ?) AS is_mine
                FROM bloc_demandes bd
                JOIN patients p    ON p.id  = bd.patient_id
                LEFT JOIN users uc ON uc.id = bd.chirurgien_id
                LEFT JOIN users ua ON ua.id = bd.anesthesiste_id
                WHERE bd.statut IN ('EN_ATTENTE','VALIDEE')
                ORDER BY is_mine DESC, bd.created_at ASC
                LIMIT 15
            ");
            $stmt3->execute([$userId]);
            $demandes_entrantes = $stmt3->fetchAll(PDO::FETCH_ASSOC);

        } catch (\Throwable $e) {
            // tables absentes → valeurs vides déjà initialisées
        }

        require_once __DIR__ . '/../views/dashboard/dashboard_anesthesie.php';
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
    $stmtP = $db->prepare("SELECT p.*, h.id as hosp_id, h.lit_id, h.statut, h.service_id as hosp_service_id, l.nom_lit, c.nom_chambre
        FROM patients p
        JOIN hospitalisations h ON p.id = h.patient_id
        LEFT JOIN lits l ON h.lit_id = l.id
        LEFT JOIN chambres c ON l.chambre_id = c.id
        WHERE h.service_id = ? AND h.statut = 'en_cours'");
    $stmtP->execute([$serviceId]);
    $patients_service = $stmtP->fetchAll(PDO::FETCH_ASSOC);

    // B. État des lits du service (pour la grille de disponibilité)
    //    On inclut le service RESPONSABLE du patient (h.service_id) pour détecter
    //    les lits "empruntés" (patient d'un autre service installé physiquement ici)
    $stmtL = $db->prepare("
        SELECT l.*, c.nom_chambre, c.type_chambre,
               p.nom, p.prenom, p.id AS patient_id_reel,
               h.service_id    AS patient_service_id,
               s2.nom_service  AS patient_service_nom
        FROM lits l
        JOIN chambres c   ON l.chambre_id = c.id
        LEFT JOIN patients p        ON l.occupied_by_patient_id = p.id
        LEFT JOIN hospitalisations h ON h.patient_id = p.id AND h.statut = 'en_cours'
        LEFT JOIN services s2        ON s2.id = h.service_id
        WHERE c.service_id = ?
        ORDER BY c.nom_chambre ASC, l.nom_lit ASC");
    $stmtL->execute([$serviceId]);
    $lits_service = $stmtL->fetchAll(PDO::FETCH_ASSOC);

    // C. Plan de soins par patient hospitalisé
    //
    // Compte TOUS les soins actifs (non annulés) de l'hospitalisation en cours,
    // peu importe la date prévue — l'infirmier voit l'état réel du plan de
    // soins du patient (ex: 3/8 soins faits sur l'admission complète).
    //
    // Bugs corrigés (versions précédentes) :
    //   1. Le filtre était sur sh.date_realisee → NULL pour les soins non
    //      encore faits → ils n'étaient jamais comptés. ✓ corrigé
    //   2. Les statuts en BDD sont en MAJUSCULES ('PLANIFIE','REALISE',
    //      'ANNULE') mais la requête cherchait 'realise' en minuscules. ✓ corrigé
    //   3. Filtre date_prevue = CURDATE() était trop strict — un soin planifié
    //      hier mais toujours à faire affichait 0/0. ✓ retiré
    //   4. Soins ANNULES inclus dans total_soins. ✓ exclus
    $stmtS = $db->prepare("
        SELECT
            h.id  AS plan_id,
            p.id  AS patient_id,
            p.nom, p.prenom,
            c.nom_chambre, l.nom_lit,
            COUNT(sh.id) AS total_soins,
            SUM(CASE WHEN UPPER(sh.statut) = 'REALISE' THEN 1 ELSE 0 END) AS soins_faits,
            -- Soins en retard : planifiés (non exécutés) dont l'heure prévue est dépassée
            SUM(CASE
                WHEN UPPER(sh.statut) NOT IN ('REALISE','ANNULE','RAYE','SUPPRIME')
                 AND sh.date_prevue IS NOT NULL
                 AND sh.date_prevue <= NOW()
                THEN 1 ELSE 0
            END) AS soins_en_retard
        FROM hospitalisations h
        JOIN patients p ON h.patient_id = p.id
        LEFT JOIN lits l     ON l.id = h.lit_id
        LEFT JOIN chambres c ON c.id = l.chambre_id
        LEFT JOIN soins_hospitalisation sh
            ON sh.admission_id = h.id
           AND UPPER(COALESCE(sh.statut, '')) NOT IN ('ANNULE', 'RAYE', 'SUPPRIME')
        WHERE h.service_id = ?
          AND h.statut = 'en_cours'
        GROUP BY h.id, p.id, p.nom, p.prenom, c.nom_chambre, l.nom_lit
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

    // E. Alertes : À Hospitaliser (admissions standard + patients transférés en attente d'installation)
    // Exclusion automatique : tout patient déjà sur un lit (lits.occupied_by_patient_id)
    // ou dont l'hospitalisation active a déjà un lit_id non nul.
    // Remplacement correlated subquery → LEFT JOIN + GROUP BY MAX(id)
    // + REGEXP supprimé : medecin_responsable traité comme INT directement
    $sqlHosp = "SELECT p.*,
                       c.id AS consult_id,
                       COALESCE(u.nom, t.nom) AS medecin_nom,
                       IF(h.id IS NOT NULL, 1, 0) AS est_transfert
                FROM patients p
                LEFT JOIN (
                    SELECT patient_id, MAX(id) AS last_id
                    FROM consultations
                    GROUP BY patient_id
                ) lc ON lc.patient_id = p.id
                LEFT JOIN consultations c ON c.id = lc.last_id
                LEFT JOIN users u ON c.medecin_id = u.id
                LEFT JOIN hospitalisations h
                       ON h.patient_id = p.id AND h.statut = 'en_cours'
                LEFT JOIN users t ON t.id = CAST(h.medecin_responsable AS UNSIGNED)
                WHERE p.statut_hosp = 'A_HOSPITALISER'
                  AND p.service_id = ?
                  AND NOT EXISTS (
                    SELECT 1 FROM lits l WHERE l.occupied_by_patient_id = p.id
                  )
                  AND (h.id IS NULL OR h.lit_id IS NULL)";

    // Auto-correctif : remettre statut_hosp = 'HOSPITALISE' pour tout patient de ce service
    // qui a déjà un lit assigné mais dont le statut n'a pas été mis à jour.
    try {
        $db->prepare(
            "UPDATE patients p
             INNER JOIN lits l ON l.occupied_by_patient_id = p.id
             SET p.statut_hosp = 'HOSPITALISE', p.statut = 'HOSPITALISE'
             WHERE p.service_id = ?
               AND p.statut_hosp = 'A_HOSPITALISER'"
        )->execute([$serviceId]);
    } catch (Exception $e) { /* non bloquant */ }

    $stmtA = $db->prepare($sqlHosp);
    $stmtA->execute([$serviceId]);
    $a_hospitaliser = $stmtA->fetchAll(PDO::FETCH_ASSOC);

    // F. Services cliniques (pour le modal "Créer & Hospitaliser")
    // Utilise la colonne categorie='CLINIQUE' et exclut les pseudo-services de paramètres/consultations
    try {
        $servicesCliniques = $db->query(
            "SELECT id, nom_service FROM services
             WHERE categorie = 'CLINIQUE'
               AND nom_service NOT LIKE 'Paramètres%'
               AND nom_service NOT LIKE 'Consultation%'
             ORDER BY nom_service ASC"
        )->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // Fallback : liste statique si la colonne categorie n'existe pas
        try {
            $servicesCliniques = $db->query(
                "SELECT id, nom_service FROM services
                 WHERE nom_service IN ('Médecine Générale','Chirurgie','Urgences','Maternité','Pédiatrie','Ophtalmologie','ORL','Neurologie')
                 ORDER BY nom_service ASC"
            )->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e2) {
            $servicesCliniques = [];
        }
    }

    // G. Médecins (pour le modal "Créer & Hospitaliser")
    try {
        $medecins = $db->query(
            "SELECT id, nom, prenom FROM users
             WHERE role IN ('MEDECIN','MEDECIN_CHEF')
               AND actif = 1
             ORDER BY nom ASC"
        )->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $medecins = [];
    }

    // H. Historique des hospitalisations terminées dans le service
    try {
        $stmtHist = $db->prepare("
            SELECT
                p.id          AS patient_id,
                p.nom, p.prenom,
                p.dossier_numero,
                p.date_naissance,
                h.id          AS hosp_id,
                h.date_admission,
                h.date_sortie_effective,
                h.type_sortie,
                h.motif_hospitalisation,
                h.diagnostic_sortie,
                DATEDIFF(
                    COALESCE(h.date_sortie_effective, NOW()),
                    h.date_admission
                ) AS duree_sejour,
                COALESCE(
                    NULLIF(TRIM(CONCAT(u.nom, ' ', COALESCE(u.prenom,''))), ''),
                    (SELECT NULLIF(TRIM(CONCAT(u2.nom,' ',COALESCE(u2.prenom,''))), '')
                       FROM consultations c2
                       JOIN users u2 ON c2.medecin_id = u2.id
                      WHERE c2.patient_id = p.id
                      ORDER BY c2.id DESC LIMIT 1)
                ) AS medecin_nom,
                l.nom_lit,
                c.nom_chambre
            FROM hospitalisations h
            JOIN patients p  ON h.patient_id = p.id
            LEFT JOIN users u ON h.medecin_responsable = u.id
            LEFT JOIN lits l  ON h.lit_id = l.id
            LEFT JOIN chambres c ON l.chambre_id = c.id
            WHERE h.service_id = ?
              AND h.statut = 'termine'
            ORDER BY h.date_sortie_effective DESC
            LIMIT 100
        ");
        $stmtHist->execute([$serviceId]);
        $historique_patients = $stmtHist->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $historique_patients = [];
    }

    // I. Consultations réalisées par cet infirmier (72 dernières heures)
    $mes_consultations = [];
    try {
        $stmtMC = $db->prepare("
            SELECT
                c.id            AS consultation_id,
                c.date_consultation,
                c.motif_consultation,
                c.devenir,
                c.infirmier_id,
                p.id            AS patient_id,
                p.nom, p.prenom, p.date_naissance, p.sexe,
                p.dossier_numero,
                p.statut_parcours,
                COALESCE(h.id, NULL)        AS hosp_id,
                COALESCE(h.statut, '')      AS hosp_statut,
                COALESCE(l.nom_lit, '')     AS nom_lit,
                COALESCE(ch.nom_chambre,'') AS nom_chambre
            FROM consultations c
            JOIN patients p ON p.id = c.patient_id
            LEFT JOIN hospitalisations h
                   ON h.patient_id = p.id AND h.statut = 'en_cours'
            LEFT JOIN lits l   ON l.id = h.lit_id
            LEFT JOIN chambres ch ON ch.id = l.chambre_id
            WHERE c.type_consultation = 'infirmiere'
              AND (c.infirmier_id = ? OR c.medecin_id = ?)
              AND c.date_consultation >= DATE_SUB(NOW(), INTERVAL 72 HOUR)
            ORDER BY c.date_consultation DESC
            LIMIT 100
        ");
        $stmtMC->execute([$userId, $userId]);
        $mes_consultations = $stmtMC->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('[DashboardController] mes_consultations : ' . $e->getMessage());
    }

    // Gestion du rafraîchissement partiel via AJAX
    if (isset($_GET['ajax_soins'])) {
        require_once __DIR__ . '/../views/dashboard/partials/soins_list.php';
        exit;
    }

    // Chargement de la vue principale
    require_once __DIR__ . '/../views/dashboard/dashboard_infirmier.php';
}

    private function loadDoctorWardData($db, $serviceId, $userId, $nomService = '') {
    $isPediatrie        = stripos($nomService, 'pédiatr') !== false || stripos($nomService, 'pediatr') !== false;
    $isNeonat           = stripos($nomService, 'onat') !== false; // néonatologie / neonatologie
    $isMaterniteService = stripos($nomService, 'maternit') !== false;
    // Accès Bloc Opératoire : chirurgiens, anesthésistes, médecine générale
    $isBlocAcces        = stripos($nomService, 'chirurgie') !== false
                       || stripos($nomService, 'anesthés') !== false
                       || stripos($nomService, 'anesthes') !== false
                       || stripos($nomService, 'bloc') !== false;
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
        // Patients assignés spécifiquement à CE médecin OU ceux du service sans médecin désigné
        $stmtA = $db->prepare("
                              SELECT p.*,
                                     COALESCE(pp.motif_consultation, ut.motif_plainte, 'Consultation') as motif_plainte,
                                     ut.niveau_gravite
                              FROM patients p
                              LEFT JOIN (SELECT patient_id, MAX(id) AS max_id FROM patient_parametres GROUP BY patient_id) pp_idx ON pp_idx.patient_id = p.id
                              LEFT JOIN patient_parametres pp ON pp.id = pp_idx.max_id
                              LEFT JOIN (SELECT patient_id, MAX(id) AS max_id FROM urgences_triage GROUP BY patient_id) ut_idx ON ut_idx.patient_id = p.id
                              LEFT JOIN urgences_triage ut ON ut.id = ut_idx.max_id
                              WHERE p.statut_parcours IN ('ATTENTE_CONSULTATION', 'PARAMETRES')
                              AND p.actif = 1
                              AND COALESCE(p.date_mise_en_parametres, p.updated_at, p.created_at) >= CURDATE()
                              AND COALESCE(p.date_mise_en_parametres, p.updated_at, p.created_at) < CURDATE() + INTERVAL 1 DAY
                              AND (
                                  p.medecin_id = ?
                                  OR (p.service_id = ? AND (p.medecin_id IS NULL OR p.medecin_id = 0))
                              )
                              ORDER BY p.numero_ordre ASC");
        $stmtA->execute([$userId, $serviceId]);
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
                             AND DATE(c.date_consultation) = CURDATE()
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
                    COALESCE(cx_last.last_date, h.date_admission, p.created_at) as derniere_activite
                FROM patients p
                LEFT JOIN (SELECT patient_id, MAX(id) AS max_id FROM hospitalisations GROUP BY patient_id) h_idx
                    ON h_idx.patient_id = p.id
                LEFT JOIN hospitalisations h ON h.id = h_idx.max_id
                LEFT JOIN hospitalisations hx ON hx.patient_id = p.id AND hx.service_id = :sid2
                LEFT JOIN (
                    SELECT patient_id, MAX(date_consultation) AS last_date
                    FROM consultations
                    WHERE medecin_id = :uid
                      AND date_consultation >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                    GROUP BY patient_id
                ) cx_last ON cx_last.patient_id = p.id
                WHERE p.service_id = :sid OR hx.id IS NOT NULL OR cx_last.patient_id IS NOT NULL
                ORDER BY derniere_activite DESC
                LIMIT 6
            ");
            $stmtMP->execute([':sid' => $serviceId, ':sid2' => $serviceId, ':uid' => $userId]);
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

        // 10. STATISTIQUES DU MOIS COURANT (pour le panneau "Mon activité")
        $stats_mois = [
            'consultations'      => 0,
            'consultations_jour' => 0,
            'ordonnances'        => 0,
            'bilans_labo'        => 0,
            'bilans_imagerie'    => 0,
            'reevaluations'      => 0,
            'hospitalisations'   => 0,
            'jours_actifs'       => 0,
            'semaine'            => [],   // nb consults/j sur 7j glissants
        ];
        try {
            $debutMois = date('Y-m-01');
            $finMois   = date('Y-m-01', strtotime('+1 month'));

            // Consultations mois + aujourd'hui + jours actifs — 1 seule requête avec range
            $s = $db->prepare("
                SELECT
                    SUM(date_consultation >= ? AND date_consultation < ?)                                          AS consultations,
                    SUM(date_consultation >= CURDATE() AND date_consultation < CURDATE() + INTERVAL 1 DAY)        AS consultations_jour,
                    COUNT(DISTINCT DATE(date_consultation))                                                        AS jours_actifs
                FROM consultations WHERE medecin_id = ? AND date_consultation >= ? AND date_consultation < ?
            ");
            $s->execute([$debutMois, $finMois, $userId, $debutMois, $finMois]);
            $r = $s->fetch(PDO::FETCH_ASSOC);
            $stats_mois['consultations']      = (int)($r['consultations']      ?? 0);
            $stats_mois['consultations_jour'] = (int)($r['consultations_jour'] ?? 0);
            $stats_mois['jours_actifs']       = (int)($r['jours_actifs']       ?? 0);

            // Ordonnances ce mois
            $s = $db->prepare("SELECT COUNT(*) FROM ordonnances_pharmacie WHERE medecin_id=? AND date_creation >= ? AND date_creation < ?");
            $s->execute([$userId, $debutMois, $finMois]);
            $stats_mois['ordonnances'] = (int)$s->fetchColumn();

            // Bilans labo demandés ce mois
            $s = $db->prepare("SELECT COUNT(*) FROM demandes_laboratoire WHERE medecin_id=? AND date_creation >= ? AND date_creation < ?");
            $s->execute([$userId, $debutMois, $finMois]);
            $stats_mois['bilans_labo'] = (int)$s->fetchColumn();

            // Bilans imagerie demandés ce mois
            try {
                $s = $db->prepare("SELECT COUNT(*) FROM demandes_imagerie WHERE medecin_id=? AND date_creation >= ? AND date_creation < ?");
                $s->execute([$userId, $debutMois, $finMois]);
                $stats_mois['bilans_imagerie'] = (int)$s->fetchColumn();
            } catch (PDOException $e) {}

            // Réévaluations ce mois
            try {
                $s = $db->prepare("SELECT COUNT(*) FROM reevaluations_hospitalisees WHERE medecin_id=? AND date_reevaluation >= ? AND date_reevaluation < ?");
                $s->execute([$userId, $debutMois, $finMois]);
                $stats_mois['reevaluations'] = (int)$s->fetchColumn();
            } catch (PDOException $e) {}

            // Hospitalisations initiées ce mois
            try {
                $s = $db->prepare("SELECT COUNT(*) FROM hospitalisations WHERE medecin_responsable=? AND date_admission >= ? AND date_admission < ?");
                $s->execute([$userId, $debutMois, $finMois]);
                $stats_mois['hospitalisations'] = (int)$s->fetchColumn();
            } catch (PDOException $e) {}

            // Activité sur 7 jours glissants (pour mini-courbe)
            $s = $db->prepare("
                SELECT DATE(date_consultation) as jour, COUNT(*) as nb
                FROM consultations
                WHERE medecin_id = ?
                  AND date_consultation >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
                GROUP BY DATE(date_consultation)
                ORDER BY jour ASC
            ");
            $s->execute([$userId]);
            $semaineRaw = $s->fetchAll(PDO::FETCH_KEY_PAIR);
            for ($i = 6; $i >= 0; $i--) {
                $d = date('Y-m-d', strtotime("-$i day"));
                $stats_mois['semaine'][] = ['date' => $d, 'nb' => (int)($semaineRaw[$d] ?? 0)];
            }
        } catch (PDOException $e) { error_log('stats_mois: ' . $e->getMessage()); }

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


/* Requête séparée LABO + IMAGERIE pour éviter les problèmes de colonnes manquantes */
$suivi_bilans = [];
try {
    $sqlLabo = "
        SELECT 'Labo' as type,
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
        WHERE dl.medecin_id = ? AND dl.statut NOT IN ('VALIDES','interprete','termine')
        ORDER BY dl.date_creation DESC LIMIT 20";
    $stmtLabo = $db->prepare($sqlLabo);
    $stmtLabo->execute([$userId]);
    $suivi_bilans = $stmtLabo->fetchAll(PDO::FETCH_ASSOC);
} catch (\Throwable $eLabo) {
    error_log('[dashboard bilans labo] ' . $eLabo->getMessage());
}
try {
    $sqlImg = "
        SELECT 'Radio' as type,
            COALESCE(di.type_imagerie, 'Imagerie') as label,
            di.statut, di.date_creation, di.id as record_id,
            di.patient_id, p.nom as patient_nom, p.prenom as patient_prenom, p.dossier_numero,
            NULL as anormal, 0 as nb_commentaires, NULL as urgence
        FROM demandes_imagerie di
        JOIN patients p ON p.id = di.patient_id
        WHERE di.medecin_id = ? AND di.statut NOT IN ('interprete','VALIDES','termine')
        ORDER BY di.date_creation DESC LIMIT 20";
    $stmtImg = $db->prepare($sqlImg);
    $stmtImg->execute([$userId]);
    $imgRows = $stmtImg->fetchAll(PDO::FETCH_ASSOC);
    $suivi_bilans = array_merge($suivi_bilans, $imgRows);
    usort($suivi_bilans, fn($a,$b) => strcmp($b['date_creation'], $a['date_creation']));
    $suivi_bilans = array_slice($suivi_bilans, 0, 20);
} catch (\Throwable $eImg) {
    error_log('[dashboard bilans imagerie] ' . $eImg->getMessage());
}

// --- DOSSIERS REÇUS (Pour moi) ---
$stmtReçus = $db->prepare("
    SELECT p.nom, p.prenom, pd.patient_id, pd.id as partage_id, pd.avis_medecin,
           u.nom as expediteur_nom, pd.date_partage, pd.date_expiration
    FROM partages_dossiers pd
    JOIN patients p ON pd.patient_id = p.id
    JOIN users u ON pd.expediteur_id = u.id
    WHERE pd.destinataire_id = ? AND pd.date_expiration > NOW()
    ORDER BY pd.date_partage DESC
");
$stmtReçus->execute([$userId]);
$dossiers_reçus = $stmtReçus->fetchAll(PDO::FETCH_ASSOC);

// --- DOSSIERS ENVOYÉS (Par moi) ---
$stmtEnvoyés = $db->prepare("
    SELECT p.nom, p.prenom, pd.patient_id, pd.id as partage_id, pd.avis_medecin,
           u.nom as destinataire_nom, pd.date_partage, pd.date_expiration
    FROM partages_dossiers pd
    JOIN patients p ON pd.patient_id = p.id
    JOIN users u ON pd.destinataire_id = u.id
    WHERE pd.expediteur_id = ? AND pd.date_expiration > NOW()
    ORDER BY pd.date_partage DESC
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

    private function loadNurseConsultantData($db, $serviceId, $userId, $nomService = '') {
        $patients_assignes     = [];
        $resultats_prets       = [];
        $suivi_bilans          = [];
        $patients_consultes    = [];
        $dossiers_reçus        = [];
        $dossiers_envoyés      = [];
        $mes_taches            = [];
        $nb_consultations_jour = 0;

        try {
            // 1. PATIENTS EN SALLE D'ATTENTE (assignés à cet infirmier consultant ou au service)
            $stmtA = $db->prepare("
                SELECT p.*,
                       COALESCE(pp.motif_consultation, ut.motif_plainte, 'Consultation') as motif_plainte,
                       ut.niveau_gravite
                FROM patients p
                LEFT JOIN (SELECT patient_id, MAX(id) AS max_id FROM patient_parametres GROUP BY patient_id) pp_idx ON pp_idx.patient_id = p.id
                LEFT JOIN patient_parametres pp ON pp.id = pp_idx.max_id
                LEFT JOIN (SELECT patient_id, MAX(id) AS max_id FROM urgences_triage GROUP BY patient_id) ut_idx ON ut_idx.patient_id = p.id
                LEFT JOIN urgences_triage ut ON ut.id = ut_idx.max_id
                WHERE p.statut_parcours IN ('ATTENTE_CONSULTATION', 'PARAMETRES')
                AND p.actif = 1
                AND COALESCE(p.date_mise_en_parametres, p.updated_at, p.created_at) >= CURDATE()
                AND COALESCE(p.date_mise_en_parametres, p.updated_at, p.created_at) < CURDATE() + INTERVAL 1 DAY
                AND (
                    p.medecin_id = ?
                    OR (p.service_id = ? AND (p.medecin_id IS NULL OR p.medecin_id = 0))
                    OR COALESCE(p.consultation_infirmiere, 0) = 1
                )
                ORDER BY p.numero_ordre ASC");
            $stmtA->execute([$userId, $serviceId]);
            $patients_assignes = $stmtA->fetchAll(PDO::FETCH_ASSOC);

            // 2. RÉSULTATS DE LABORATOIRE DISPONIBLES
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

            // 3. SUIVI DES BILANS (LABO + RADIO EN COURS)
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

            // 4. CONSULTATIONS EFFECTUÉES RÉCEMMENT (24h)
            $stmtH = $db->prepare("
                SELECT c.id as consult_id, c.date_consultation,
                       p.id as patient_id, p.nom, p.prenom, p.dossier_numero
                FROM consultations c
                JOIN patients p ON c.patient_id = p.id
                WHERE c.medecin_id = ?
                AND DATE(c.date_consultation) = CURDATE()
                ORDER BY c.date_consultation DESC LIMIT 10");
            $stmtH->execute([$userId]);
            $patients_consultes = $stmtH->fetchAll(PDO::FETCH_ASSOC);

            // 5. NOMBRE DE CONSULTATIONS AUJOURD'HUI
            $stmtNb = $db->prepare("
                SELECT COUNT(*) FROM consultations
                WHERE medecin_id = ? AND DATE(date_consultation) = CURDATE()");
            $stmtNb->execute([$userId]);
            $nb_consultations_jour = (int)$stmtNb->fetchColumn();

            // 6. DOSSIERS PARTAGÉS REÇUS
            $stmtReçus = $db->prepare("
                SELECT p.nom, p.prenom, pd.patient_id, pd.id as partage_id, pd.avis_medecin,
                       u.nom as expediteur_nom, pd.date_partage, pd.date_expiration
                FROM partages_dossiers pd
                JOIN patients p ON pd.patient_id = p.id
                JOIN users u ON pd.expediteur_id = u.id
                WHERE pd.destinataire_id = ? AND pd.date_expiration > NOW()
                ORDER BY pd.date_partage DESC");
            $stmtReçus->execute([$userId]);
            $dossiers_reçus = $stmtReçus->fetchAll(PDO::FETCH_ASSOC);

            // 7. DOSSIERS PARTAGÉS ENVOYÉS
            $stmtEnvoyés = $db->prepare("
                SELECT p.nom, p.prenom, pd.patient_id, pd.id as partage_id, pd.avis_medecin,
                       u.nom as destinataire_nom, pd.date_partage, pd.date_expiration
                FROM partages_dossiers pd
                JOIN patients p ON pd.patient_id = p.id
                JOIN users u ON pd.destinataire_id = u.id
                WHERE pd.expediteur_id = ? AND pd.date_expiration > NOW()
                ORDER BY pd.date_partage DESC");
            $stmtEnvoyés->execute([$userId]);
            $dossiers_envoyés = $stmtEnvoyés->fetchAll(PDO::FETCH_ASSOC);

            // 8. NOTES / TO-DO PERSONNELS
            $stmtT = $db->prepare("SELECT * FROM user_todo WHERE user_id = ? ORDER BY is_done ASC, created_at DESC LIMIT 10");
            $stmtT->execute([$userId]);
            $mes_taches = $stmtT->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("Erreur Dashboard Infirmier Consultant : " . $e->getMessage());
        }

        require_once __DIR__ . '/../views/dashboard/dashboard_infirmier_consultant.php';
    }

    private function loadDirectorData($db) {

        // ── 1. KPIs GLOBAUX ──────────────────────────────────────────────────
        $kpi = [];
        $_dm = date('Y-m-01'); $_fm = date('Y-m-01', strtotime('+1 month'));
        $_kpiStmt = $db->prepare("
            SELECT
                (SELECT COUNT(*) FROM patients WHERE actif = 1)                                        AS total_patients,
                (SELECT COUNT(*) FROM patients WHERE created_at >= ? AND created_at < ?)               AS patients_ce_mois,
                (SELECT COUNT(*) FROM consultations WHERE date_consultation >= ? AND date_consultation < ?) AS consultations_mois,
                (SELECT COUNT(*) FROM hospitalisations WHERE statut = 'en_cours')                      AS hospitalisations_actives,
                (SELECT COUNT(*) FROM lits)                                                             AS total_lits,
                (SELECT COUNT(*) FROM lits WHERE statut = 'OCCUPE')                                    AS lits_occupes
        ");
        $_kpiStmt->execute([$_dm, $_fm, $_dm, $_fm]);
        $_kd = $_kpiStmt->fetch(PDO::FETCH_ASSOC);
        $kpi['total_patients']           = (int)$_kd['total_patients'];
        $kpi['patients_ce_mois']         = (int)$_kd['patients_ce_mois'];
        $kpi['consultations_mois']       = (int)$_kd['consultations_mois'];
        $kpi['hospitalisations_actives'] = (int)$_kd['hospitalisations_actives'];
        $kpi['total_lits']               = (int)$_kd['total_lits'];
        $kpi['lits_occupes']             = (int)$_kd['lits_occupes'];
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

        // ── 11. STATISTIQUES RDV SPÉCIALISTES ────────────────────────────────
        $kpi['rdv_aujourd_hui']  = 0;
        $kpi['rdv_mois']         = 0;
        $kpi['rdv_honores_mois'] = 0;
        $kpi['rdv_taux_honore']  = 0;
        $rdv_par_specialiste     = [];
        $rdv_evolution_mois      = [];
        $rdv_par_statut          = [];
        try {
            $kpi['rdv_aujourd_hui'] = (int)$db->query(
                "SELECT COUNT(*) FROM rdv_specialistes WHERE date_rdv = CURDATE()"
            )->fetchColumn();

            $kpi['rdv_mois'] = (int)$db->query("
                SELECT COUNT(*) FROM rdv_specialistes
                WHERE MONTH(date_rdv) = MONTH(CURDATE()) AND YEAR(date_rdv) = YEAR(CURDATE())
            ")->fetchColumn();

            $kpi['rdv_honores_mois'] = (int)$db->query("
                SELECT COUNT(*) FROM rdv_specialistes
                WHERE statut = 'TERMINE'
                  AND MONTH(date_rdv) = MONTH(CURDATE()) AND YEAR(date_rdv) = YEAR(CURDATE())
            ")->fetchColumn();

            $kpi['rdv_taux_honore'] = $kpi['rdv_mois'] > 0
                ? round($kpi['rdv_honores_mois'] / $kpi['rdv_mois'] * 100, 1)
                : 0;

            // RDV par spécialiste (ce mois)
            $rdv_par_specialiste = $db->query("
                SELECT sp.specialite, sp.nom, sp.prenom,
                       COUNT(rs.id) as total,
                       SUM(CASE WHEN rs.statut = 'TERMINE'  THEN 1 ELSE 0 END) as honores,
                       SUM(CASE WHEN rs.statut = 'ANNULE'   THEN 1 ELSE 0 END) as annules,
                       SUM(CASE WHEN rs.statut NOT IN ('TERMINE','ANNULE','REPORTE') THEN 1 ELSE 0 END) as en_attente
                FROM specialistes sp
                LEFT JOIN rdv_specialistes rs
                       ON rs.specialiste_id = sp.id
                      AND MONTH(rs.date_rdv) = MONTH(CURDATE())
                      AND YEAR(rs.date_rdv)  = YEAR(CURDATE())
                GROUP BY sp.id
                ORDER BY total DESC
            ")->fetchAll(PDO::FETCH_ASSOC);

            // Évolution mensuelle des RDV (6 derniers mois)
            $rdv_evolution_mois = $db->query("
                SELECT DATE_FORMAT(date_rdv, '%Y-%m') as mois,
                       DATE_FORMAT(date_rdv, '%b %Y') as label,
                       COUNT(*) as total,
                       SUM(CASE WHEN statut = 'TERMINE' THEN 1 ELSE 0 END) as honores,
                       SUM(CASE WHEN statut = 'ANNULE'  THEN 1 ELSE 0 END) as annules
                FROM rdv_specialistes
                WHERE date_rdv >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
                GROUP BY mois, label
                ORDER BY mois ASC
            ")->fetchAll(PDO::FETCH_ASSOC);

            // Répartition par statut
            $rdv_par_statut = $db->query("
                SELECT statut, COUNT(*) as nb
                FROM rdv_specialistes
                WHERE MONTH(date_rdv) = MONTH(CURDATE()) AND YEAR(date_rdv) = YEAR(CURDATE())
                GROUP BY statut
                ORDER BY nb DESC
            ")->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("RDV stats error: " . $e->getMessage());
        }

        // ── 12. URGENCES ──────────────────────────────────────────────────────
        $kpi['urgences_en_cours']   = 0;
        $kpi['urgences_aujourd_hui']= 0;
        $urgences_critiques = [];
        try {
            $kpi['urgences_en_cours']    = (int)$db->query("SELECT COUNT(*) FROM urgences_patients WHERE statut IN ('ATTENTE','EN_COURS')")->fetchColumn();
            $kpi['urgences_aujourd_hui'] = (int)$db->query("SELECT COUNT(*) FROM urgences_patients WHERE DATE(created_at) = CURDATE()")->fetchColumn();
            $urgences_critiques = $db->query("
                SELECT up.niveau_triage, up.motif_admission, up.created_at,
                       p.nom, p.prenom, p.dossier_numero
                FROM urgences_patients up
                JOIN patients p ON up.patient_id = p.id
                WHERE up.statut IN ('ATTENTE','EN_COURS')
                  AND up.niveau_triage IN ('P1','P2')
                ORDER BY up.niveau_triage ASC, up.created_at ASC
                LIMIT 6
            ")->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {}

        // ── 13. DURÉE MOYENNE DE SÉJOUR ──────────────────────────────────────
        $kpi['dms'] = 0;
        try {
            $dms = $db->query("
                SELECT AVG(DATEDIFF(COALESCE(date_sortie, NOW()), date_admission))
                FROM hospitalisations
                WHERE date_admission >= DATE_SUB(CURDATE(), INTERVAL 90 DAY)
            ")->fetchColumn();
            $kpi['dms'] = $dms ? round($dms, 1) : 0;
        } catch (PDOException $e) {}

        // ── 14. PATIENTS SORTIS CE MOIS ───────────────────────────────────────
        $kpi['patients_sortis_mois'] = 0;
        try {
            $kpi['patients_sortis_mois'] = (int)$db->query("
                SELECT COUNT(*) FROM hospitalisations
                WHERE statut = 'sorti'
                  AND MONTH(date_sortie) = MONTH(CURDATE())
                  AND YEAR(date_sortie)  = YEAR(CURDATE())
            ")->fetchColumn();
        } catch (PDOException $e) {}

        // ── 15. EXAMENS LABO CE MOIS ─────────────────────────────────────────
        $kpi['examens_labo_mois'] = 0;
        try {
            $kpi['examens_labo_mois'] = (int)$db->query("
                SELECT COUNT(*) FROM demandes_laboratoire
                WHERE MONTH(date_creation) = MONTH(CURDATE())
                  AND YEAR(date_creation)  = YEAR(CURDATE())
            ")->fetchColumn();
        } catch (PDOException $e) {}

        // ── 16. CHIRURGIES CE MOIS ────────────────────────────────────────────
        $kpi['chirurgies_mois'] = 0;
        try {
            $kpi['chirurgies_mois'] = (int)$db->query("
                SELECT COUNT(*) FROM interventions
                WHERE MONTH(date_intervention) = MONTH(CURDATE())
                  AND YEAR(date_intervention)  = YEAR(CURDATE())
            ")->fetchColumn();
        } catch (PDOException $e) {}

        // ── 17. PERSONNEL ACTIF ───────────────────────────────────────────────
        $kpi['personnel_actif'] = (int)$db->query("SELECT COUNT(*) FROM users WHERE actif = 1")->fetchColumn();

        // ── 18. EVOLUTION LABO 6 MOIS ─────────────────────────────────────────
        $labo_par_mois = [];
        try {
            $labo_par_mois = $db->query("
                SELECT DATE_FORMAT(date_creation,'%Y-%m') as mois,
                       DATE_FORMAT(date_creation,'%b %Y') as label,
                       COUNT(*) as nb,
                       SUM(statut = 'valide' OR statut = 'interprete') as nb_rendus
                FROM demandes_laboratoire
                WHERE date_creation >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
                GROUP BY mois, label
                ORDER BY mois ASC
            ")->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {}

        // ── 19. ACTIVITÉ PERSONNEL (connexions 7j) ────────────────────────────
        $activite_personnel = [];
        try {
            $activite_personnel = $db->query("
                SELECT u.role, COUNT(DISTINCT a.user_id) as nb_actifs
                FROM audit_logs a
                JOIN users u ON a.user_id = u.id
                WHERE a.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                  AND a.action = 'LOGIN'
                GROUP BY u.role
                ORDER BY nb_actifs DESC
            ")->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {}

        require_once __DIR__ . '/../views/dashboard/dashboard_directeur.php';
    }

    /**
     * Export des statistiques : CSV (Excel) ou page impression (PDF)
     */
    public function exportStats(): void {
        if (($_SESSION['user_role'] ?? '') !== 'DIRECTEUR') {
            http_response_code(403);
            $error_cause = "L'export des statistiques est réservé au profil DIRECTEUR.";
            require __DIR__ . '/../views/errors/403.php';
            exit;
        }
        $db     = (new Database())->getConnection();
        $format = $_GET['format'] ?? 'csv';
        $section= $_GET['section'] ?? 'global';

        if ($format === 'csv') {
            $this->exportCsv($db, $section);
        } else {
            // Format PDF = page impression optimisée
            $this->exportPrintPage($db);
        }
    }

    private function exportCsv($db, string $section): void {
        $now = date('Y-m-d_H-i');
        $filename = "HSJM_Stats_{$section}_{$now}.csv";
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-cache');
        // BOM UTF-8 pour Excel
        echo "\xEF\xBB\xBF";
        $out = fopen('php://output', 'w');

        switch ($section) {
            case 'rdv':
                fputcsv($out, ['Spécialiste','Spécialité','Total RDV (mois)','Honorés','Annulés','En attente'], ';');
                $rows = $db->query("
                    SELECT sp.nom, sp.prenom, sp.specialite,
                           COUNT(rs.id) as total,
                           SUM(CASE WHEN rs.statut='TERMINE' THEN 1 ELSE 0 END) as honores,
                           SUM(CASE WHEN rs.statut='ANNULE'  THEN 1 ELSE 0 END) as annules,
                           SUM(CASE WHEN rs.statut NOT IN ('TERMINE','ANNULE','REPORTE') THEN 1 ELSE 0 END) as en_attente
                    FROM specialistes sp
                    LEFT JOIN rdv_specialistes rs ON rs.specialiste_id = sp.id
                       AND MONTH(rs.date_rdv)=MONTH(CURDATE()) AND YEAR(rs.date_rdv)=YEAR(CURDATE())
                    GROUP BY sp.id ORDER BY total DESC
                ")->fetchAll(PDO::FETCH_ASSOC);
                foreach ($rows as $r) {
                    fputcsv($out, [$r['nom'].' '.$r['prenom'], $r['specialite'], $r['total'], $r['honores'], $r['annules'], $r['en_attente']], ';');
                }
                break;

            case 'hospitalisations':
                fputcsv($out, ['Service','Total Lits','Lits Occupés','Taux Occupation (%)'], ';');
                $rows = $db->query("
                    SELECT s.nom_service,
                           COUNT(l.id) as total,
                           SUM(CASE WHEN l.statut='OCCUPE' THEN 1 ELSE 0 END) as occupes,
                           ROUND(SUM(CASE WHEN l.statut='OCCUPE' THEN 1 ELSE 0 END)/COUNT(l.id)*100,1) as taux
                    FROM services s
                    LEFT JOIN chambres c ON s.id = c.service_id
                    LEFT JOIN lits l ON c.id = l.chambre_id
                    GROUP BY s.id HAVING total > 0 ORDER BY taux DESC
                ")->fetchAll(PDO::FETCH_ASSOC);
                foreach ($rows as $r) {
                    fputcsv($out, [$r['nom_service'], $r['total'], $r['occupes'], $r['taux']], ';');
                }
                break;

            case 'pharmacie':
                fputcsv($out, ['Médicament','Forme','Dosage','Stock actuel','Seuil alerte','Niveau'], ';');
                $rows = $db->query("
                    SELECT nom, forme, dosage, quantite, seuil_alerte,
                           CASE WHEN quantite=0 THEN 'Rupture'
                                WHEN quantite<=seuil_alerte THEN 'Critique'
                                WHEN quantite<=seuil_alerte*2 THEN 'Alerte'
                                ELSE 'Normal' END as niveau
                    FROM medicaments ORDER BY niveau ASC, quantite ASC
                ")->fetchAll(PDO::FETCH_ASSOC);
                foreach ($rows as $r) {
                    fputcsv($out, [$r['nom'], $r['forme'], $r['dosage'], $r['quantite'], $r['seuil_alerte'], $r['niveau']], ';');
                }
                break;

            case 'services':
                fputcsv($out, ['Service','Patients (actifs)','Consultations (30j)','Hospitalisations actives'], ';');
                $rows = $db->query("
                    SELECT s.nom_service,
                           COUNT(DISTINCT p.id) as nb_patients,
                           COUNT(DISTINCT c.id) as nb_consult,
                           COUNT(DISTINCT h.id) as nb_hosp
                    FROM services s
                    LEFT JOIN patients p ON p.service_id=s.id AND p.actif=1
                    LEFT JOIN consultations c ON c.service_id=s.id AND c.date_consultation>=DATE_SUB(CURDATE(),INTERVAL 30 DAY)
                    LEFT JOIN hospitalisations h ON h.service_id=s.id AND h.statut='en_cours'
                    GROUP BY s.id ORDER BY nb_patients DESC
                ")->fetchAll(PDO::FETCH_ASSOC);
                foreach ($rows as $r) {
                    fputcsv($out, [$r['nom_service'], $r['nb_patients'], $r['nb_consult'], $r['nb_hosp']], ';');
                }
                break;

            default: // global
                // KPIs globaux
                fputcsv($out, ['=== RAPPORT GLOBAL — HSJM — ' . date('d/m/Y H:i') . ' ==='], ';');
                fputcsv($out, [], ';');
                fputcsv($out, ['INDICATEUR','VALEUR'], ';');
                fputcsv($out, ['Total Patients', $db->query("SELECT COUNT(*) FROM patients WHERE actif=1")->fetchColumn()], ';');
                fputcsv($out, ['Patients ce mois', $db->query("SELECT COUNT(*) FROM patients WHERE MONTH(created_at)=MONTH(CURDATE()) AND YEAR(created_at)=YEAR(CURDATE())")->fetchColumn()], ';');
                fputcsv($out, ['Consultations ce mois', $db->query("SELECT COUNT(*) FROM consultations WHERE MONTH(date_consultation)=MONTH(CURDATE()) AND YEAR(date_consultation)=YEAR(CURDATE())")->fetchColumn()], ';');
                fputcsv($out, ['Hospitalisations actives', $db->query("SELECT COUNT(*) FROM hospitalisations WHERE statut='en_cours'")->fetchColumn()], ';');
                fputcsv($out, ['Lits occupés', $db->query("SELECT COUNT(*) FROM lits WHERE statut='OCCUPE'")->fetchColumn()], ';');
                fputcsv($out, ['Total lits', $db->query("SELECT COUNT(*) FROM lits")->fetchColumn()], ';');
                fputcsv($out, ['RDV spécialistes aujourd\'hui', $db->query("SELECT COUNT(*) FROM rdv_specialistes WHERE date_rdv=CURDATE()")->fetchColumn()], ';');
                fputcsv($out, ['RDV spécialistes ce mois', $db->query("SELECT COUNT(*) FROM rdv_specialistes WHERE MONTH(date_rdv)=MONTH(CURDATE()) AND YEAR(date_rdv)=YEAR(CURDATE())")->fetchColumn()], ';');
                fputcsv($out, ['Médicaments en rupture', $db->query("SELECT COUNT(*) FROM medicaments WHERE quantite=0")->fetchColumn()], ';');
        }

        fclose($out);
        exit;
    }

    private function exportPrintPage($db): void {
        // Collecte toutes les données pour la page imprimable
        $date_rapport = date('d/m/Y à H:i');
        $kpi = [];
        $kpi['total_patients']      = $db->query("SELECT COUNT(*) FROM patients WHERE actif=1")->fetchColumn();
        $kpi['patients_ce_mois']    = $db->query("SELECT COUNT(*) FROM patients WHERE MONTH(created_at)=MONTH(CURDATE()) AND YEAR(created_at)=YEAR(CURDATE())")->fetchColumn();
        $kpi['consultations_mois']  = $db->query("SELECT COUNT(*) FROM consultations WHERE MONTH(date_consultation)=MONTH(CURDATE()) AND YEAR(date_consultation)=YEAR(CURDATE())")->fetchColumn();
        $kpi['hospitalisations_actives'] = $db->query("SELECT COUNT(*) FROM hospitalisations WHERE statut='en_cours'")->fetchColumn();
        $kpi['lits_occupes']        = $db->query("SELECT COUNT(*) FROM lits WHERE statut='OCCUPE'")->fetchColumn();
        $kpi['total_lits']          = $db->query("SELECT COUNT(*) FROM lits")->fetchColumn();
        $kpi['taux_occupation']     = $kpi['total_lits'] > 0 ? round($kpi['lits_occupes']/$kpi['total_lits']*100,1) : 0;
        $kpi['rdv_aujourd_hui']     = $db->query("SELECT COUNT(*) FROM rdv_specialistes WHERE date_rdv=CURDATE()")->fetchColumn();
        $kpi['rdv_mois']            = $db->query("SELECT COUNT(*) FROM rdv_specialistes WHERE MONTH(date_rdv)=MONTH(CURDATE()) AND YEAR(date_rdv)=YEAR(CURDATE())")->fetchColumn();

        $lits_par_service = $db->query("
            SELECT s.nom_service, COUNT(l.id) as total,
                   SUM(CASE WHEN l.statut='OCCUPE' THEN 1 ELSE 0 END) as occupes,
                   ROUND(SUM(CASE WHEN l.statut='OCCUPE' THEN 1 ELSE 0 END)/COUNT(l.id)*100,1) as taux
            FROM services s
            LEFT JOIN chambres c ON s.id=c.service_id
            LEFT JOIN lits l ON c.id=l.chambre_id
            GROUP BY s.id HAVING total > 0 ORDER BY taux DESC
        ")->fetchAll(PDO::FETCH_ASSOC);

        $rdv_par_specialiste = $db->query("
            SELECT sp.specialite, sp.nom, sp.prenom,
                   COUNT(rs.id) as total,
                   SUM(CASE WHEN rs.statut='TERMINE' THEN 1 ELSE 0 END) as honores,
                   SUM(CASE WHEN rs.statut='ANNULE'  THEN 1 ELSE 0 END) as annules
            FROM specialistes sp
            LEFT JOIN rdv_specialistes rs ON rs.specialiste_id=sp.id
               AND MONTH(rs.date_rdv)=MONTH(CURDATE()) AND YEAR(rs.date_rdv)=YEAR(CURDATE())
            GROUP BY sp.id ORDER BY total DESC
        ")->fetchAll(PDO::FETCH_ASSOC);

        $stocks_critiques = $db->query("
            SELECT nom, forme, quantite, seuil_alerte
            FROM medicaments WHERE quantite <= seuil_alerte ORDER BY quantite ASC LIMIT 20
        ")->fetchAll(PDO::FETCH_ASSOC);

        require_once __DIR__ . '/../views/dashboard/dashboard_export_print.php';
        exit;
    }

    /* ================================================================
       ENDPOINT AJAX — Détails enrichis par KPI
       Route : GET /dashboard/kpi-details/{type}
    ================================================================ */
    public function kpiDetails(string $type): void {
        header('Content-Type: application/json; charset=utf-8');
        $this->auth->requirePermission('dashboard', 'read');
        $db = (new Database())->getConnection();
        $out = [];

        try {
            switch ($type) {

                /* ──── PATIENTS ──────────────────────────────────────────── */
                case 'patients':
                    $out['total']      = (int)$db->query("SELECT COUNT(*) FROM patients WHERE actif=1")->fetchColumn();
                    $out['ce_mois']    = (int)$db->query("SELECT COUNT(*) FROM patients WHERE MONTH(created_at)=MONTH(CURDATE()) AND YEAR(created_at)=YEAR(CURDATE())")->fetchColumn();
                    $out['mois_dernier']= (int)$db->query("SELECT COUNT(*) FROM patients WHERE MONTH(created_at)=MONTH(DATE_SUB(CURDATE(),INTERVAL 1 MONTH)) AND YEAR(created_at)=YEAR(DATE_SUB(CURDATE(),INTERVAL 1 MONTH))")->fetchColumn();

                    // Répartition par sexe
                    $sex = $db->query("SELECT sexe, COUNT(*) as nb FROM patients WHERE actif=1 GROUP BY sexe")->fetchAll(\PDO::FETCH_ASSOC);
                    $out['par_sexe'] = array_column($sex, 'nb', 'sexe');

                    // Tranches d'âge
                    $ages = $db->query("
                        SELECT CASE
                            WHEN TIMESTAMPDIFF(YEAR,date_naissance,CURDATE()) < 1  THEN 'Nourrissons (<1an)'
                            WHEN TIMESTAMPDIFF(YEAR,date_naissance,CURDATE()) < 15 THEN 'Enfants (1-14)'
                            WHEN TIMESTAMPDIFF(YEAR,date_naissance,CURDATE()) < 30 THEN 'Jeunes (15-29)'
                            WHEN TIMESTAMPDIFF(YEAR,date_naissance,CURDATE()) < 50 THEN 'Adultes (30-49)'
                            WHEN TIMESTAMPDIFF(YEAR,date_naissance,CURDATE()) < 70 THEN 'Séniors (50-69)'
                            ELSE 'Âgés (70+)'
                        END as tranche, COUNT(*) as nb
                        FROM patients WHERE actif=1 AND date_naissance IS NOT NULL
                        GROUP BY tranche ORDER BY MIN(date_naissance) DESC
                    ")->fetchAll(\PDO::FETCH_ASSOC);
                    $out['par_age'] = $ages;

                    // Tendance 12 mois
                    $trend = $db->query("
                        SELECT DATE_FORMAT(created_at,'%b %Y') as label,
                               DATE_FORMAT(created_at,'%Y-%m') as mois,
                               COUNT(*) as nb
                        FROM patients
                        WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
                        GROUP BY mois, label ORDER BY mois ASC
                    ")->fetchAll(\PDO::FETCH_ASSOC);
                    $out['tendance'] = $trend;

                    // Derniers inscrits
                    $out['derniers'] = $db->query("
                        SELECT nom, prenom, dossier_numero, sexe,
                               DATE_FORMAT(created_at,'%d/%m/%Y') as date_insc
                        FROM patients WHERE actif=1
                        ORDER BY created_at DESC LIMIT 8
                    ")->fetchAll(\PDO::FETCH_ASSOC);
                    break;

                /* ──── CONSULTATIONS ─────────────────────────────────────── */
                case 'consultations':
                    $out['total_mois'] = (int)$db->query("SELECT COUNT(*) FROM consultations WHERE MONTH(date_consultation)=MONTH(CURDATE()) AND YEAR(date_consultation)=YEAR(CURDATE())")->fetchColumn();
                    $out['mois_dernier']=(int)$db->query("SELECT COUNT(*) FROM consultations WHERE MONTH(date_consultation)=MONTH(DATE_SUB(CURDATE(),INTERVAL 1 MONTH)) AND YEAR(date_consultation)=YEAR(DATE_SUB(CURDATE(),INTERVAL 1 MONTH))")->fetchColumn();

                    // Par type
                    $types = $db->query("SELECT type_consultation, COUNT(*) as nb FROM consultations WHERE MONTH(date_consultation)=MONTH(CURDATE()) AND YEAR(date_consultation)=YEAR(CURDATE()) GROUP BY type_consultation")->fetchAll(\PDO::FETCH_ASSOC);
                    $out['par_type'] = $types;

                    // Par statut
                    $statuts = $db->query("SELECT statut, COUNT(*) as nb FROM consultations WHERE MONTH(date_consultation)=MONTH(CURDATE()) AND YEAR(date_consultation)=YEAR(CURDATE()) GROUP BY statut ORDER BY nb DESC")->fetchAll(\PDO::FETCH_ASSOC);
                    $out['par_statut'] = $statuts;

                    // Top 5 médecins
                    $top = $db->query("
                        SELECT u.nom, u.prenom, u.specialite, COUNT(c.id) as nb
                        FROM consultations c JOIN users u ON c.medecin_id=u.id
                        WHERE MONTH(c.date_consultation)=MONTH(CURDATE()) AND YEAR(c.date_consultation)=YEAR(CURDATE())
                        GROUP BY u.id ORDER BY nb DESC LIMIT 7
                    ")->fetchAll(\PDO::FETCH_ASSOC);
                    $out['top_medecins'] = $top;

                    // Tendance 30j
                    $trend30 = $db->query("
                        SELECT DATE_FORMAT(date_consultation,'%d/%m') as label,
                               DATE(date_consultation) as jour,
                               COUNT(*) as nb
                        FROM consultations
                        WHERE date_consultation >= DATE_SUB(CURDATE(),INTERVAL 30 DAY)
                        GROUP BY jour, label ORDER BY jour ASC
                    ")->fetchAll(\PDO::FETCH_ASSOC);
                    $out['tendance_30j'] = $trend30;
                    break;

                /* ──── HOSPITALISATIONS ──────────────────────────────────── */
                case 'hospitalisations':
                    $out['actives']    = (int)$db->query("SELECT COUNT(*) FROM hospitalisations WHERE statut='en_cours'")->fetchColumn();
                    $out['ce_mois']    = (int)$db->query("SELECT COUNT(*) FROM hospitalisations WHERE MONTH(date_admission)=MONTH(CURDATE()) AND YEAR(date_admission)=YEAR(CURDATE())")->fetchColumn();
                    $out['sorties_mois']=(int)$db->query("SELECT COUNT(*) FROM hospitalisations WHERE statut='termine' AND MONTH(date_sortie_effective)=MONTH(CURDATE()) AND YEAR(date_sortie_effective)=YEAR(CURDATE())")->fetchColumn();

                    // DMS réel
                    $dms = $db->query("SELECT AVG(DATEDIFF(IFNULL(date_sortie_effective,CURDATE()),date_admission)) FROM hospitalisations WHERE date_admission IS NOT NULL")->fetchColumn();
                    $out['dms'] = $dms ? round($dms, 1) : 0;

                    // Par service
                    $byService = $db->query("
                        SELECT s.nom_service, COUNT(h.id) as nb_actives,
                               AVG(DATEDIFF(CURDATE(),h.date_admission)) as dms_service
                        FROM hospitalisations h
                        JOIN services s ON h.service_id=s.id
                        WHERE h.statut='en_cours'
                        GROUP BY s.id, s.nom_service ORDER BY nb_actives DESC
                    ")->fetchAll(\PDO::FETCH_ASSOC);
                    $out['par_service'] = $byService;

                    // Patients récemment admis (10)
                    $recents = $db->query("
                        SELECT p.nom, p.prenom, p.dossier_numero,
                               s.nom_service, h.date_admission,
                               DATE_FORMAT(h.date_admission,'%d/%m/%Y') as date_fmt,
                               DATEDIFF(CURDATE(),h.date_admission) as jours
                        FROM hospitalisations h
                        JOIN patients p ON h.patient_id=p.id
                        JOIN services s ON h.service_id=s.id
                        WHERE h.statut='en_cours'
                        ORDER BY h.date_admission DESC LIMIT 10
                    ")->fetchAll(\PDO::FETCH_ASSOC);
                    $out['recents'] = $recents;

                    // Tendance 6 mois
                    $trend = $db->query("
                        SELECT DATE_FORMAT(date_admission,'%b %Y') as label,
                               DATE_FORMAT(date_admission,'%Y-%m') as mois,
                               COUNT(*) as nb
                        FROM hospitalisations
                        WHERE date_admission >= DATE_SUB(CURDATE(),INTERVAL 6 MONTH)
                        GROUP BY mois, label ORDER BY mois ASC
                    ")->fetchAll(\PDO::FETCH_ASSOC);
                    $out['tendance'] = $trend;
                    break;

                /* ──── LITS ──────────────────────────────────────────────── */
                case 'lits':
                    $out['total']     = (int)$db->query("SELECT COUNT(*) FROM lits")->fetchColumn();
                    $out['occupes']   = (int)$db->query("SELECT COUNT(*) FROM lits WHERE statut='OCCUPE'")->fetchColumn();
                    $out['libres']    = (int)$db->query("SELECT COUNT(*) FROM lits WHERE statut='DISPONIBLE'")->fetchColumn();
                    $out['maintenance']=(int)$db->query("SELECT COUNT(*) FROM lits WHERE statut NOT IN ('OCCUPE','DISPONIBLE')")->fetchColumn();
                    $out['par_service'] = $db->query("
                        SELECT s.nom_service,
                               COUNT(l.id) as total,
                               SUM(CASE WHEN l.statut='OCCUPE' THEN 1 ELSE 0 END) as occupes,
                               SUM(CASE WHEN l.statut='DISPONIBLE' THEN 1 ELSE 0 END) as libres,
                               ROUND(SUM(CASE WHEN l.statut='OCCUPE' THEN 1 ELSE 0 END)/COUNT(l.id)*100,1) as taux
                        FROM services s
                        LEFT JOIN chambres ch ON s.id=ch.service_id
                        LEFT JOIN lits l ON ch.id=l.chambre_id
                        GROUP BY s.id, s.nom_service HAVING total>0 ORDER BY taux DESC
                    ")->fetchAll(\PDO::FETCH_ASSOC);
                    break;

                /* ──── URGENCES ──────────────────────────────────────────── */
                case 'urgences':
                    $out['en_cours']   = 0; $out['aujourd_hui'] = 0;
                    $out['par_triage'] = []; $out['tendance_7j'] = [];
                    try {
                        $out['en_cours']    = (int)$db->query("SELECT COUNT(*) FROM urgences_patients WHERE statut IN ('ATTENTE','EN_COURS')")->fetchColumn();
                        $out['aujourd_hui'] = (int)$db->query("SELECT COUNT(*) FROM urgences_patients WHERE DATE(heure_arrivee)=CURDATE()")->fetchColumn();
                        $out['total_mois']  = (int)$db->query("SELECT COUNT(*) FROM urgences_patients WHERE MONTH(heure_arrivee)=MONTH(CURDATE()) AND YEAR(heure_arrivee)=YEAR(CURDATE())")->fetchColumn();

                        // niveau_triage est enum('1','2','3','4','5') — on affiche P1…P5
                        $raw = $db->query("
                            SELECT niveau_triage, COUNT(*) as nb
                            FROM urgences_patients
                            WHERE statut IN ('ATTENTE','EN_COURS')
                            GROUP BY niveau_triage ORDER BY niveau_triage ASC
                        ")->fetchAll(\PDO::FETCH_ASSOC);
                        $out['par_triage'] = array_map(fn($r)=>['niveau_triage'=>'P'.$r['niveau_triage'],'nb'=>$r['nb']], $raw);

                        $out['tendance_7j'] = $db->query("
                            SELECT DATE_FORMAT(heure_arrivee,'%d/%m') as label, DATE(heure_arrivee) as jour, COUNT(*) as nb
                            FROM urgences_patients
                            WHERE heure_arrivee >= DATE_SUB(CURDATE(),INTERVAL 7 DAY)
                            GROUP BY jour,label ORDER BY jour ASC
                        ")->fetchAll(\PDO::FETCH_ASSOC);

                        $out['critiques'] = $db->query("
                            SELECT p.nom, p.prenom, CONCAT('P',up.niveau_triage) as niveau_triage,
                                   up.motif_admission, up.heure_arrivee as created_at,
                                   TIMESTAMPDIFF(MINUTE,up.heure_arrivee,NOW()) as attente_min
                            FROM urgences_patients up
                            JOIN patients p ON up.patient_id=p.id
                            WHERE up.statut IN ('ATTENTE','EN_COURS') AND up.niveau_triage IN ('1','2')
                            ORDER BY up.niveau_triage ASC, up.heure_arrivee ASC LIMIT 10
                        ")->fetchAll(\PDO::FETCH_ASSOC);
                    } catch(\Throwable $e) {}
                    break;

                /* ──── LABO ──────────────────────────────────────────────── */
                case 'labo':
                    $out['total_mois'] = 0; $out['par_categorie'] = []; $out['en_attente'] = 0;
                    try {
                        $out['total_mois'] = (int)$db->query("SELECT COUNT(*) FROM demandes_laboratoire WHERE MONTH(date_creation)=MONTH(CURDATE()) AND YEAR(date_creation)=YEAR(CURDATE())")->fetchColumn();
                        $out['mois_dernier']=(int)$db->query("SELECT COUNT(*) FROM demandes_laboratoire WHERE MONTH(date_creation)=MONTH(DATE_SUB(CURDATE(),INTERVAL 1 MONTH)) AND YEAR(date_creation)=YEAR(DATE_SUB(CURDATE(),INTERVAL 1 MONTH))")->fetchColumn();
                        $out['en_attente'] = (int)$db->query("SELECT COUNT(*) FROM demandes_laboratoire WHERE statut IN ('EN_ATTENTE','EN_COURS','ASSIGNEE','PRELEVE')")->fetchColumn();
                        $out['urgent']     = (int)$db->query("SELECT COUNT(*) FROM demandes_laboratoire WHERE statut IN ('EN_ATTENTE','EN_COURS') AND urgence='URGENT'")->fetchColumn();

                        $out['par_categorie'] = $db->query("
                            SELECT el.categorie, COUNT(de.id) as nb
                            FROM demande_examens de
                            JOIN examens_laboratoire el ON de.examen_id=el.id
                            JOIN demandes_laboratoire dl ON de.demande_id=dl.id
                            WHERE MONTH(dl.date_creation)=MONTH(CURDATE()) AND YEAR(dl.date_creation)=YEAR(CURDATE())
                            GROUP BY el.categorie ORDER BY nb DESC LIMIT 10
                        ")->fetchAll(\PDO::FETCH_ASSOC);

                        $out['top_examens'] = $db->query("
                            SELECT el.nom, COUNT(de.id) as nb
                            FROM demande_examens de
                            JOIN examens_laboratoire el ON de.examen_id=el.id
                            JOIN demandes_laboratoire dl ON de.demande_id=dl.id
                            WHERE dl.date_creation >= DATE_SUB(CURDATE(),INTERVAL 30 DAY)
                            GROUP BY el.id, el.nom ORDER BY nb DESC LIMIT 8
                        ")->fetchAll(\PDO::FETCH_ASSOC);

                        $out['tendance_30j'] = $db->query("
                            SELECT DATE_FORMAT(date_creation,'%d/%m') as label,
                                   DATE(date_creation) as jour, COUNT(*) as nb
                            FROM demandes_laboratoire
                            WHERE date_creation >= DATE_SUB(CURDATE(),INTERVAL 30 DAY)
                            GROUP BY jour, label ORDER BY jour ASC
                        ")->fetchAll(\PDO::FETCH_ASSOC);
                    } catch(\Throwable $e) {}
                    break;

                /* ──── ATTENTE ───────────────────────────────────────────── */
                case 'attente':
                    try {
                        $moy = $db->query("
                            SELECT AVG(TIMESTAMPDIFF(MINUTE,p.created_at,c.date_consultation)) as moy
                            FROM consultations c JOIN patients p ON c.patient_id=p.id
                            WHERE c.date_consultation > DATE_SUB(NOW(),INTERVAL 30 DAY)
                              AND c.date_consultation > p.created_at
                        ")->fetchColumn();
                        $out['moy_minutes'] = $moy ? round($moy) : 0;

                        $out['distribution'] = $db->query("
                            SELECT CASE
                                WHEN TIMESTAMPDIFF(MINUTE,p.created_at,c.date_consultation) < 30  THEN '< 30 min'
                                WHEN TIMESTAMPDIFF(MINUTE,p.created_at,c.date_consultation) < 60  THEN '30-60 min'
                                WHEN TIMESTAMPDIFF(MINUTE,p.created_at,c.date_consultation) < 120 THEN '1-2h'
                                WHEN TIMESTAMPDIFF(MINUTE,p.created_at,c.date_consultation) < 240 THEN '2-4h'
                                ELSE '> 4h'
                            END as tranche, COUNT(*) as nb
                            FROM consultations c JOIN patients p ON c.patient_id=p.id
                            WHERE c.date_consultation > DATE_SUB(NOW(),INTERVAL 30 DAY)
                              AND c.date_consultation > p.created_at
                            GROUP BY tranche
                        ")->fetchAll(\PDO::FETCH_ASSOC);

                        $out['tendance_30j'] = $db->query("
                            SELECT DATE_FORMAT(c.date_consultation,'%d/%m') as label,
                                   DATE(c.date_consultation) as jour,
                                   ROUND(AVG(TIMESTAMPDIFF(MINUTE,p.created_at,c.date_consultation))) as moy_min
                            FROM consultations c JOIN patients p ON c.patient_id=p.id
                            WHERE c.date_consultation > DATE_SUB(NOW(),INTERVAL 30 DAY)
                              AND c.date_consultation > p.created_at
                            GROUP BY jour, label ORDER BY jour ASC
                        ")->fetchAll(\PDO::FETCH_ASSOC);
                    } catch(\Throwable $e) { $out['moy_minutes'] = 0; $out['distribution'] = []; }
                    break;

                /* ──── PHARMACIE ─────────────────────────────────────────── */
                case 'pharmacie':
                    try {
                        // stocks_pharmacie n'existe pas — on utilise medicaments.quantite directement
                        $out['stocks'] = $db->query("
                            SELECT nom,
                                   quantite  AS stock_actuel,
                                   seuil_alerte AS seuil,
                                   CASE
                                       WHEN quantite = 0                          THEN 'rupture'
                                       WHEN quantite <= seuil_alerte * 0.5        THEN 'critique'
                                       ELSE 'alerte'
                                   END AS niveau
                            FROM medicaments
                            WHERE quantite <= seuil_alerte
                            ORDER BY quantite ASC LIMIT 30
                        ")->fetchAll(\PDO::FETCH_ASSOC);
                        $out['ruptures']  = count(array_filter($out['stocks'], fn($m)=>$m['niveau']==='rupture'));
                        $out['critiques'] = count(array_filter($out['stocks'], fn($m)=>$m['niveau']==='critique'));
                        $out['alertes']   = count(array_filter($out['stocks'], fn($m)=>$m['niveau']==='alerte'));

                        $out['top_prescrits'] = $db->query("
                            SELECT om.nom_medicament AS nom, COUNT(om.id) AS nb
                            FROM ordonnance_medicaments om
                            JOIN ordonnances_pharmacie op ON om.ordonnance_id = op.id
                            WHERE op.date_creation >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                            GROUP BY om.nom_medicament ORDER BY nb DESC LIMIT 8
                        ")->fetchAll(\PDO::FETCH_ASSOC);
                    } catch(\Throwable $e) { $out['stocks']=[]; $out['ruptures']=0; }
                    break;

                /* ──── PERSONNEL ─────────────────────────────────────────── */
                case 'personnel':
                    $out['total_actif'] = (int)$db->query("SELECT COUNT(*) FROM users WHERE actif=1")->fetchColumn();
                    $out['par_role']    = $db->query("
                        SELECT role, COUNT(*) as nb FROM users WHERE actif=1 GROUP BY role ORDER BY nb DESC
                    ")->fetchAll(\PDO::FETCH_ASSOC);
                    try {
                        // user_sessions utilise last_activity (pas created_at)
                        $out['actifs_7j'] = $db->query("
                            SELECT u.role, COUNT(DISTINCT us.user_id) as nb_actifs
                            FROM user_sessions us JOIN users u ON us.user_id=u.id
                            WHERE us.last_activity >= DATE_SUB(NOW(),INTERVAL 7 DAY)
                            GROUP BY u.role ORDER BY nb_actifs DESC
                        ")->fetchAll(\PDO::FETCH_ASSOC);
                        $out['derniere_connexion'] = $db->query("
                            SELECT u.nom, u.prenom, u.role,
                                   MAX(us.last_activity) as last_login,
                                   DATE_FORMAT(MAX(us.last_activity),'%d/%m %H:%i') as last_fmt
                            FROM users u JOIN user_sessions us ON u.id=us.user_id
                            WHERE u.actif=1 AND us.last_activity >= DATE_SUB(NOW(),INTERVAL 7 DAY)
                            GROUP BY u.id, u.nom, u.prenom, u.role
                            ORDER BY last_login DESC LIMIT 10
                        ")->fetchAll(\PDO::FETCH_ASSOC);
                    } catch(\Throwable $e) { $out['actifs_7j']=[]; $out['derniere_connexion']=[]; }
                    break;

                /* ──── RDV ───────────────────────────────────────────────── */
                case 'rdv-today': case 'rdv-mois': case 'rdv-honores': case 'rdv-taux':
                    try {
                        $out['aujourd_hui']  = (int)$db->query("SELECT COUNT(*) FROM rdv_specialistes WHERE date_rdv=CURDATE()")->fetchColumn();
                        $out['total_mois']   = (int)$db->query("SELECT COUNT(*) FROM rdv_specialistes WHERE MONTH(date_rdv)=MONTH(CURDATE()) AND YEAR(date_rdv)=YEAR(CURDATE())")->fetchColumn();
                        $out['honores_mois'] = (int)$db->query("SELECT COUNT(*) FROM rdv_specialistes WHERE statut IN ('confirme','CONFIRME','termine','TERMINE') AND MONTH(date_rdv)=MONTH(CURDATE()) AND YEAR(date_rdv)=YEAR(CURDATE())")->fetchColumn();
                        $out['annules_mois'] = (int)$db->query("SELECT COUNT(*) FROM rdv_specialistes WHERE statut IN ('annule','ANNULE') AND MONTH(date_rdv)=MONTH(CURDATE()) AND YEAR(date_rdv)=YEAR(CURDATE())")->fetchColumn();
                        $out['taux_honore']  = $out['total_mois'] > 0 ? round($out['honores_mois']/$out['total_mois']*100,1) : 0;

                        // clé specialiste_id (pas medecin_id) dans rdv_specialistes
                        $out['par_medecin'] = $db->query("
                            SELECT u.nom, u.prenom, u.specialite, COUNT(r.id) as total,
                                   SUM(CASE WHEN r.statut IN ('confirme','CONFIRME','termine','TERMINE') THEN 1 ELSE 0 END) as honores
                            FROM rdv_specialistes r JOIN users u ON r.specialiste_id=u.id
                            WHERE MONTH(r.date_rdv)=MONTH(CURDATE()) AND YEAR(r.date_rdv)=YEAR(CURDATE())
                            GROUP BY u.id ORDER BY total DESC LIMIT 8
                        ")->fetchAll(\PDO::FETCH_ASSOC);

                        $out['tendance_30j'] = $db->query("
                            SELECT DATE_FORMAT(date_rdv,'%d/%m') as label, DATE(date_rdv) as jour, COUNT(*) as nb
                            FROM rdv_specialistes
                            WHERE date_rdv >= DATE_SUB(CURDATE(),INTERVAL 30 DAY)
                            GROUP BY jour, label ORDER BY jour ASC
                        ")->fetchAll(\PDO::FETCH_ASSOC);
                    } catch(\Throwable $e) {}
                    break;

                /* ──── CHIRURGIES ────────────────────────────────────────── */
                case 'chirurgies':
                    try {
                        // Table réelle : bloc_interventions, date : date_prevue
                        $out['ce_mois']    = (int)$db->query("SELECT COUNT(*) FROM bloc_interventions WHERE MONTH(date_prevue)=MONTH(CURDATE()) AND YEAR(date_prevue)=YEAR(CURDATE())")->fetchColumn();
                        $out['mois_dernier']=(int)$db->query("SELECT COUNT(*) FROM bloc_interventions WHERE MONTH(date_prevue)=MONTH(DATE_SUB(CURDATE(),INTERVAL 1 MONTH)) AND YEAR(date_prevue)=YEAR(DATE_SUB(CURDATE(),INTERVAL 1 MONTH))")->fetchColumn();
                        $out['tendance'] = $db->query("
                            SELECT DATE_FORMAT(date_prevue,'%b %Y') as label,
                                   DATE_FORMAT(date_prevue,'%Y-%m') as mois, COUNT(*) as nb
                            FROM bloc_interventions
                            WHERE date_prevue >= DATE_SUB(CURDATE(),INTERVAL 6 MONTH)
                            GROUP BY mois, label ORDER BY mois ASC
                        ")->fetchAll(\PDO::FETCH_ASSOC);
                        $out['par_type'] = $db->query("
                            SELECT type_intervention, COUNT(*) as nb
                            FROM bloc_interventions
                            WHERE MONTH(date_prevue)=MONTH(CURDATE()) AND YEAR(date_prevue)=YEAR(CURDATE())
                            GROUP BY type_intervention ORDER BY nb DESC LIMIT 8
                        ")->fetchAll(\PDO::FETCH_ASSOC);
                    } catch(\Throwable $e) { $out['ce_mois']=0; }
                    break;

                default:
                    $out['error'] = 'Type inconnu';
            }
        } catch (\Throwable $e) {
            $out['error'] = $e->getMessage();
        }

        echo json_encode($out);
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
        $stmt = $db->prepare("SELECT patient_id, medecin_id FROM consultations WHERE id = ?");
        $stmt->execute([$_POST['consult_id']]);
        $p = $stmt->fetch();
        if ($p) {
            // Trouver le service urgences
            $urgencesServiceId = null;
            try {
                $urgencesServiceId = $db->query("SELECT id FROM services WHERE LOWER(nom_service) LIKE '%urgence%' LIMIT 1")->fetchColumn() ?: null;
            } catch (Exception $e) {}

            // Mettre à jour le patient vers urgences
            $db->prepare("UPDATE patients SET statut_hosp = 'A_HOSPITALISER', statut_parcours = 'HOSPITALISE'"
                . ($urgencesServiceId ? ", service_id = $urgencesServiceId" : "")
                . " WHERE id = ?")->execute([$p['patient_id']]);

            // Créer une entrée urgences_patients pour l'infirmier urgences
            if ($urgencesServiceId) {
                $chk = $db->prepare("SELECT id FROM urgences_patients WHERE patient_id = ? AND statut NOT IN ('TERMINE','SORTI') LIMIT 1");
                $chk->execute([$p['patient_id']]);
                $existing = $chk->fetch();
                if ($existing) {
                    $db->prepare("UPDATE urgences_patients SET statut = 'HOSPITALISE', heure_prise_charge = NOW() WHERE id = ?")
                       ->execute([$existing['id']]);
                } else {
                    $db->prepare("INSERT INTO urgences_patients (patient_id, motif_admission, niveau_triage, heure_arrivee, medecin_id, statut) VALUES (?, 'Hospitalisation depuis consultation', '3', NOW(), ?, 'HOSPITALISE')")
                       ->execute([$p['patient_id'], $p['medecin_id'] ?? null]);
                }
            }

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

    /**
     * API AJAX : récupère les résultats d'une demande de bilan labo
     * GET : id = demandes_laboratoire.id
     */
    public function resultatsBilan() {
        header('Content-Type: application/json');
        $demandeId = (int)($_GET['id'] ?? 0);
        if (!$demandeId) {
            echo json_encode(['success' => false, 'message' => 'ID manquant']); return;
        }

        try {
            $db = (new Database())->getConnection();

            // Résultats des examens liés à cette demande
            // Les résultats sont sur demande_examens (valeur_numerique, interpretation)
            // et optionnellement dans resultats_examens (résultat texte complémentaire)
            $stmt = $db->prepare("
                SELECT de.id,
                       COALESCE(de.resultat, re.resultat) as resultat,
                       de.valeur_numerique, de.unite,
                       COALESCE(de.valeur_normale_min, el.valeur_normale_min) as valeur_normale_min,
                       COALESCE(de.valeur_normale_max, el.valeur_normale_max) as valeur_normale_max,
                       COALESCE(de.interpretation, re.interpretation) as interpretation,
                       CASE
                           WHEN de.valeur_numerique IS NOT NULL
                                AND de.valeur_normale_min IS NOT NULL
                                AND de.valeur_normale_max IS NOT NULL
                                AND (de.valeur_numerique < de.valeur_normale_min OR de.valeur_numerique > de.valeur_normale_max)
                           THEN 1 ELSE 0
                       END as anormal,
                       re.date_resultat,
                       el.nom as nom_examen, el.categorie,
                       dl.statut, dl.patient_id,
                       p.nom as patient_nom, p.prenom as patient_prenom, p.dossier_numero,
                       u.nom as medecin_nom, u.prenom as medecin_prenom
                FROM demande_examens de
                JOIN demandes_laboratoire dl ON de.demande_id = dl.id
                LEFT JOIN examens_laboratoire el ON de.examen_id = el.id
                LEFT JOIN resultats_examens re ON re.examen_id = de.id
                JOIN patients p ON dl.patient_id = p.id
                JOIN users u ON dl.medecin_id = u.id
                WHERE dl.id = ?
                ORDER BY el.categorie, el.nom
            ");
            $stmt->execute([$demandeId]);
            $examens = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Commentaires médecin sur ce bilan
            $commentaires = [];
            try {
                $stmtC = $db->prepare("
                    SELECT bc.commentaire, bc.created_at,
                           u.nom as medecin_nom, u.prenom as medecin_prenom
                    FROM bilan_commentaires bc
                    JOIN users u ON bc.medecin_id = u.id
                    WHERE bc.demande_id = ?
                    ORDER BY bc.created_at DESC
                ");
                $stmtC->execute([$demandeId]);
                $commentaires = $stmtC->fetchAll(PDO::FETCH_ASSOC);
            } catch (PDOException $e) {
                // Table bilan_commentaires peut ne pas exister
            }

            echo json_encode([
                'success'      => true,
                'examens'      => $examens,
                'commentaires' => $commentaires,
            ]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * API JSON — données de suivi infirmier pour le modal du dashboard médecin
     * GET dashboard/suivi-infirmier/{patient_id}
     */
    /**
     * API JSON POST — Enregistrer une conduite à tenir (CAT) rapide
     * Crée une entrée minimale dans reevaluations_hospitalisees
     * POST dashboard/save-cat
     */

    /**
     * API JSON GET — File d'attente filtrée par date
     * GET dashboard/attente-par-date?date=YYYY-MM-DD&service_id=X
     */
    public function attenteParDate(): void
    {
        ob_clean(); // Annuler toute sortie parasite (warnings PHP, etc.)
        header('Content-Type: application/json; charset=utf-8');
        $db = (new Database())->getConnection();

        $date      = $_GET['date']       ?? date('Y-m-d');
        $serviceId = (int)($_GET['service_id'] ?? 0);
        $userId    = (int)($_SESSION['user_id'] ?? 0);

        // Validation de la date
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            $date = date('Y-m-d');
        }
        $isToday = ($date === date('Y-m-d'));

        try {
            if ($isToday) {
                // Aujourd'hui : file d'attente active (patients non encore consultés)
                $stmt = $db->prepare("
                    SELECT p.id, p.nom, p.prenom, p.dossier_numero, p.sexe,
                           p.date_naissance, p.medecin_id, p.femme_enceinte,
                           p.numero_ordre, p.statut_parcours,
                           COALESCE(p.parametres_requis, 0) AS parametres_requis,
                           COALESCE(pp.motif_consultation, ut.motif_plainte, 'Consultation') AS motif_plainte,
                           ut.niveau_gravite,
                           NULL AS heure_consultation, NULL AS consultation_id
                    FROM patients p
                    LEFT JOIN patient_parametres pp
                           ON pp.id = (SELECT MAX(id) FROM patient_parametres WHERE patient_id = p.id)
                    LEFT JOIN urgences_triage ut
                           ON ut.id = (SELECT MAX(id) FROM urgences_triage WHERE patient_id = p.id)
                    WHERE p.statut_parcours IN ('ATTENTE_CONSULTATION', 'PARAMETRES')
                      AND p.actif = 1
                      AND DATE(COALESCE(p.date_mise_en_parametres, p.updated_at, p.created_at)) = CURDATE()
                      AND (
                          p.medecin_id = ?
                          OR (p.service_id = ? AND (p.medecin_id IS NULL OR p.medecin_id = 0))
                      )
                    ORDER BY p.numero_ordre ASC
                ");
                $stmt->execute([$userId, $serviceId]);
            } else {
                // Date passée : patients ayant eu une consultation ce jour-là
                $stmt = $db->prepare("
                    SELECT p.id, p.nom, p.prenom, p.dossier_numero, p.sexe,
                           p.date_naissance, p.medecin_id, p.femme_enceinte,
                           NULL AS numero_ordre, c.statut AS statut_parcours,
                           COALESCE(c.motif_consultation, 'Consultation') AS motif_plainte,
                           NULL AS niveau_gravite,
                           TIME(c.date_consultation) AS heure_consultation,
                           c.id AS consultation_id
                    FROM consultations c
                    JOIN patients p ON c.patient_id = p.id
                    WHERE DATE(c.date_consultation) = ?
                      AND (
                          c.medecin_id = ?
                          OR c.service_id = ?
                      )
                    ORDER BY c.date_consultation ASC
                ");
                $stmt->execute([$date, $userId, $serviceId]);
            }

            $patients = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode([
                'success'  => true,
                'date'     => $date,
                'is_today' => $isToday,
                'count'    => count($patients),
                'patients' => $patients,
            ]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function saveCat(): void
    {
        header('Content-Type: application/json');
        if (session_status() === PHP_SESSION_NONE) session_start();

        // Vérif rôle médecin
        $role = strtoupper($_SESSION['user_role'] ?? '');
        $roles = ['MEDECIN','MEDECIN_CHEF','MEDECIN_URGENTISTE','DIRECTEUR','ADMIN','ADMINISTRATEUR'];
        if (!in_array($role, $roles)) {
            echo json_encode(['success' => false, 'message' => 'Accès réservé aux médecins.']); return;
        }

        $patient_id    = (int)($_POST['patient_id']      ?? 0);
        $cat           = trim($_POST['conduite_tenir']   ?? '');
        $evolution     = trim($_POST['evolution_globale'] ?? 'STATUO_QUO');
        $diagnostic    = trim($_POST['diagnostic_jour']   ?? '');
        $note_evol     = trim($_POST['note_evolution']    ?? '');
        $medecin_id    = (int)($_SESSION['user_id']       ?? 0);

        $evols_valides = ['FAVORABLE','AMELIORATION_PARTIELLE','STATUO_QUO','NON_FAVORABLE','AGGRAVATION','CRITIQUE'];
        if (!$patient_id || empty($cat)) {
            echo json_encode(['success' => false, 'message' => 'Patient et conduite à tenir requis.']); return;
        }
        if (!in_array($evolution, $evols_valides)) $evolution = 'STATUO_QUO';

        try {
            $db = (new Database())->getConnection();

            // Trouver l'hospitalisation en cours
            $stmtH = $db->prepare("
                SELECT id FROM hospitalisations
                WHERE patient_id = ? AND statut = 'en_cours'
                ORDER BY id DESC LIMIT 1
            ");
            $stmtH->execute([$patient_id]);
            $hosp = $stmtH->fetchColumn();
            if (!$hosp) {
                echo json_encode(['success' => false, 'message' => 'Aucune hospitalisation active pour ce patient.']); return;
            }

            $stmtIns = $db->prepare("
                INSERT INTO reevaluations_hospitalisees
                    (hospitalisation_id, patient_id, medecin_id,
                     date_reevaluation, heure_reevaluation,
                     evolution_globale, note_evolution,
                     diagnostic_jour, conduite_tenir)
                VALUES (?, ?, ?, CURDATE(), CURTIME(), ?, ?, ?, ?)
            ");
            $stmtIns->execute([
                $hosp, $patient_id, $medecin_id,
                $evolution,
                $note_evol ?: null,
                $diagnostic ?: null,
                $cat,
            ]);

            echo json_encode([
                'success' => true,
                'message' => 'Conduite à tenir enregistrée avec succès.',
                'id'      => $db->lastInsertId(),
            ]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * API JSON — données de suivi infirmier pour le modal du dashboard médecin
     * GET dashboard/suivi-infirmier/{patient_id}
     */
    public function suiviInfirmierJson(int $patient_id) {
        header('Content-Type: application/json');
        $db = (new Database())->getConnection();

        try {
            // ── 1. Infos patient ──
            $pat = $db->prepare("SELECT id, nom, prenom, date_naissance, sexe, dossier_numero FROM patients WHERE id = ? LIMIT 1");
            $pat->execute([$patient_id]);
            $patient = $pat->fetch(PDO::FETCH_ASSOC);
            if (!$patient) { echo json_encode(['success' => false, 'message' => 'Patient introuvable']); return; }

            // ── 2. Derniers paramètres vitaux (dernière mesure) ──
            $stmtLast = $db->prepare("
                SELECT pp.*,
                       u.nom AS inf_nom, u.prenom AS inf_prenom, u.role AS inf_role
                FROM patient_parametres pp
                LEFT JOIN users u ON pp.user_id = u.id
                WHERE pp.patient_id = ?
                ORDER BY pp.date_mesure DESC
                LIMIT 1
            ");
            $stmtLast->execute([$patient_id]);
            $dernieres = $stmtLast->fetch(PDO::FETCH_ASSOC) ?: [];

            // ── 3. Historique pour les courbes (20 dernières mesures) ──
            $stmtHist = $db->prepare("
                SELECT date_mesure, temperature,
                       pression_arterielle_systolique AS ta_sys,
                       pression_arterielle_diastolique AS ta_dia,
                       frequence_cardiaque, saturation_oxygene,
                       glycemie, frequence_respiratoire, diurese
                FROM patient_parametres
                WHERE patient_id = ?
                ORDER BY date_mesure DESC
                LIMIT 20
            ");
            $stmtHist->execute([$patient_id]);
            $historique = array_reverse($stmtHist->fetchAll(PDO::FETCH_ASSOC));

            // ── 4. Observations infirmières (non vides, 10 dernières) ──
            $stmtObs = $db->prepare("
                SELECT pp.observations, pp.date_mesure,
                       u.nom AS inf_nom, u.prenom AS inf_prenom, u.role AS inf_role
                FROM patient_parametres pp
                LEFT JOIN users u ON pp.user_id = u.id
                WHERE pp.patient_id = ?
                  AND pp.observations IS NOT NULL
                  AND pp.observations <> ''
                ORDER BY pp.date_mesure DESC
                LIMIT 10
            ");
            $stmtObs->execute([$patient_id]);
            $observations = $stmtObs->fetchAll(PDO::FETCH_ASSOC);

            // ── 5. Plaintes du patient (non vides, 10 dernières) ──
            $plaintes = [];
            try {
                $stmtPlaintes = $db->prepare("
                    SELECT pp.plaintes, pp.date_mesure,
                           u.nom AS inf_nom, u.prenom AS inf_prenom, u.role AS inf_role
                    FROM patient_parametres pp
                    LEFT JOIN users u ON pp.user_id = u.id
                    WHERE pp.patient_id = ?
                      AND pp.plaintes IS NOT NULL
                      AND pp.plaintes <> ''
                    ORDER BY pp.date_mesure DESC
                    LIMIT 10
                ");
                $stmtPlaintes->execute([$patient_id]);
                $plaintes = $stmtPlaintes->fetchAll(PDO::FETCH_ASSOC);
            } catch (Exception $e) { /* colonne plaintes absente — migration non encore exécutée */ }

            // ── 6. Soins planifiés et exécutés (50 derniers) ──
            $stmtSoins = $db->prepare("
                SELECT sh.id, sh.type_soin, sh.description, sh.condition_application,
                       sh.date_prevue, sh.date_realisee, sh.statut,
                       sh.note_execution, sh.note_major,
                       up.nom AS planif_nom,  up.prenom AS planif_prenom,
                       ue.nom AS exec_nom,    ue.prenom AS exec_prenom
                FROM soins_hospitalisation sh
                JOIN hospitalisations h ON sh.admission_id = h.id
                LEFT JOIN users up ON sh.user_id_planificateur = up.id
                LEFT JOIN users ue ON sh.user_id_executant     = ue.id
                WHERE h.patient_id = ?
                ORDER BY sh.date_prevue DESC
                LIMIT 50
            ");
            $stmtSoins->execute([$patient_id]);
            $soins = $stmtSoins->fetchAll(PDO::FETCH_ASSOC);

            // ── 7. Réévaluations médicales (10 dernières) ──
            $stmtRev = $db->prepare("
                SELECT r.id, r.date_reevaluation, r.heure_reevaluation,
                       r.evolution_globale, r.note_evolution,
                       r.diagnostic_jour,  r.code_cim10,
                       r.conduite_tenir,   r.traitement_non_medicamenteux,
                       r.plaintes_jour,
                       u.nom AS medecin_nom, u.prenom AS medecin_prenom, u.role AS medecin_role
                FROM reevaluations_hospitalisees r
                JOIN users u ON r.medecin_id = u.id
                WHERE r.patient_id = ?
                ORDER BY r.date_reevaluation DESC, r.heure_reevaluation DESC
                LIMIT 10
            ");
            $stmtRev->execute([$patient_id]);
            $reevaluations = $stmtRev->fetchAll(PDO::FETCH_ASSOC);

            // Bilans et médicaments liés à chaque réévaluation
            foreach ($reevaluations as &$rev) {
                $rid = (int)$rev['id'];

                $stmtBilans = $db->prepare("
                    SELECT type, intitule, urgence, note
                    FROM reevaluation_bilans
                    WHERE reevaluation_id = ?
                    ORDER BY type, id
                ");
                $stmtBilans->execute([$rid]);
                $rev['bilans'] = $stmtBilans->fetchAll(PDO::FETCH_ASSOC);

                $stmtMeds = $db->prepare("
                    SELECT nom_medicament, posologie, voie_administration, frequence, duree, notes
                    FROM reevaluation_medicaments
                    WHERE reevaluation_id = ?
                    ORDER BY id
                ");
                $stmtMeds->execute([$rid]);
                $rev['medicaments'] = $stmtMeds->fetchAll(PDO::FETCH_ASSOC);
            }
            unset($rev);

            // ── 8. Évaluations de la douleur (20 dernières) ──
            $douleur = [];
            try {
                $stmtDoul = $db->prepare("
                    SELECT ed.id, ed.date_evaluation, ed.profil, ed.echelle,
                           ed.score, ed.score_max, ed.severite,
                           ed.localisation, ed.caracteristiques,
                           ed.action_prise, ed.note_infirmier, ed.contexte,
                           u.nom AS inf_nom, u.prenom AS inf_prenom
                    FROM evaluations_douleur ed
                    LEFT JOIN users u ON ed.infirmier_id = u.id
                    WHERE ed.patient_id = ?
                    ORDER BY ed.date_evaluation DESC
                    LIMIT 20
                ");
                $stmtDoul->execute([$patient_id]);
                $douleur = $stmtDoul->fetchAll(PDO::FETCH_ASSOC);
            } catch (Exception $e) { /* table absente — non bloquant */ }

            echo json_encode([
                'success'        => true,
                'patient'        => $patient,
                'dernieres'      => $dernieres,
                'historique'     => $historique,
                'observations'   => $observations,
                'plaintes'       => $plaintes,
                'soins'          => $soins,
                'reevaluations'  => $reevaluations,
                'douleur'        => $douleur,
            ]);

        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /* ================================================================
       REGISTRE DES CONSULTATIONS — endpoint AJAX + export
       GET  dashboard/registre-consultations   → JSON
       GET  dashboard/registre-export-csv      → téléchargement CSV
       GET  dashboard/registre-export-pdf      → page HTML imprimable
       ================================================================ */
    private function _buildRegistreQuery(PDO $db, int $userId, array $params): array {
        $dateFrom = $params['from']   ?? date('Y-m-01');
        $dateTo   = $params['to']     ?? date('Y-m-d');
        $search   = trim($params['search'] ?? '');

        // Validation des dates
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFrom)) $dateFrom = date('Y-m-01');
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateTo))   $dateTo   = date('Y-m-d');

        $whereSearch = '';
        $bindExtra = [];
        if ($search !== '') {
            $whereSearch = " AND (p.nom LIKE ? OR p.prenom LIKE ? OR p.dossier_numero LIKE ?)";
            $s = "%$search%";
            $bindExtra = [$s, $s, $s];
        }

        $sql = "
            SELECT
                c.id                        AS consultation_id,
                c.date_consultation,
                c.motif_consultation,
                c.diagnostic_principal,
                c.plan_traitement,
                c.traitement_non_medicamenteux,
                p.id                        AS patient_id,
                p.nom, p.prenom,
                p.sexe,
                p.date_naissance,
                p.ville,
                p.telephone,
                p.type_client,
                p.dossier_numero,
                /* Bilans labo (agrégés) */
                (SELECT GROUP_CONCAT(el.nom ORDER BY el.nom SEPARATOR ', ')
                 FROM demandes_laboratoire dl
                 JOIN demande_examens de2  ON de2.demande_id = dl.id
                 JOIN examens_laboratoire el ON el.id = de2.examen_id
                 WHERE dl.consultation_id = c.id
                ) AS bilans_labo,
                /* Médicaments ordonnance liée */
                (SELECT GROUP_CONCAT(
                    CONCAT(om.nom_medicament, ' (', COALESCE(om.posologie,'?'), ')')
                    ORDER BY om.id SEPARATOR ' | ')
                 FROM ordonnances_pharmacie op2
                 JOIN ordonnance_medicaments om ON om.ordonnance_id = op2.id
                 WHERE op2.consultation_id = c.id
                 LIMIT 1
                ) AS traitements
            FROM consultations c
            JOIN patients p ON c.patient_id = p.id
            WHERE c.medecin_id = ?
              AND DATE(c.date_consultation) BETWEEN ? AND ?
              $whereSearch
            ORDER BY c.date_consultation DESC
            LIMIT 500
        ";
        $binds = array_merge([$userId, $dateFrom, $dateTo], $bindExtra);

        $stmt = $db->prepare($sql);
        $stmt->execute($binds);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Enrichissement : âge, statut VIH, type_client lisible
        $typeLabels = [
            'BON_PRISE_EN_CHARGE' => 'Bon PEC',
            'PAYANT_COMPTANT'     => 'Payant comptant',
            'ASSURANCE'           => 'Assurance',
            'FAMILLE_PHP'         => 'Famille PHP',
            'AGENTS_PHP'          => 'Agents PHP',
        ];

        // Statut VIH : chercher dans antecedents.description (type médical)
        $vihCache = [];
        try {
            $stmtV = $db->prepare("
                SELECT patient_id, description FROM antecedents
                WHERE type = 'medical'
                  AND (description LIKE '%VIH%' OR description LIKE '%HIV%'
                       OR description LIKE '%séropositif%' OR description LIKE '%seroposit%')
            ");
            $stmtV->execute();
            foreach ($stmtV->fetchAll(PDO::FETCH_ASSOC) as $r) {
                $vihCache[$r['patient_id']] = true;
            }
        } catch (Exception $e) {}

        foreach ($rows as &$row) {
            $dn = $row['date_naissance'];
            $row['age'] = $dn ? (int)(new DateTime())->diff(new DateTime($dn))->y : null;
            $row['statut_vih'] = isset($vihCache[$row['patient_id']]) ? 'Positif' : 'Non précisé';
            $row['type_client_label'] = $typeLabels[$row['type_client'] ?? ''] ?? ($row['type_client'] ?? '—');
            // Fusion traitements texte + médicaments ordonnance
            $traitTxt = trim($row['traitement_non_medicamenteux'] ?? '');
            $meds     = trim($row['traitements'] ?? '');
            $row['traitements_complets'] = implode(' | ', array_filter([$traitTxt, $meds])) ?: '—';
        }
        unset($row);

        return ['rows' => $rows, 'from' => $dateFrom, 'to' => $dateTo];
    }

    public function registreConsultations(): void {
        header('Content-Type: application/json');
        $this->auth->requirePermission('dashboard', 'read');
        $db = (new Database())->getConnection();
        $userId = (int)($_SESSION['user_id'] ?? 0);
        try {
            $result = $this->_buildRegistreQuery($db, $userId, $_GET);
            echo json_encode(['success' => true] + $result);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    public function registreExportCsv(): void {
        $this->auth->requirePermission('dashboard', 'read');
        $db = (new Database())->getConnection();
        $userId = (int)($_SESSION['user_id'] ?? 0);
        $result = $this->_buildRegistreQuery($db, $userId, $_GET);

        $filename = 'registre_consultations_' . $result['from'] . '_au_' . $result['to'] . '.csv';
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        // BOM UTF-8 pour Excel
        echo "\xEF\xBB\xBF";

        $out = fopen('php://output', 'w');
        fputcsv($out, [
            'N°','Date','Nom','Prénom','Sexe','Âge','Statut VIH',
            'Type Client','Domicile (Ville)','Contact','N° Dossier',
            'Motif de Consultation','Diagnostic','Bilans','Traitements'
        ], ';');

        foreach ($result['rows'] as $i => $row) {
            fputcsv($out, [
                $i + 1,
                $row['date_consultation'] ? date('d/m/Y H:i', strtotime($row['date_consultation'])) : '',
                strtoupper($row['nom'] ?? ''),
                $row['prenom'] ?? '',
                $row['sexe'] === 'M' ? 'Masculin' : ($row['sexe'] === 'F' ? 'Féminin' : ''),
                $row['age'] !== null ? $row['age'] . ' ans' : '',
                $row['statut_vih'],
                $row['type_client_label'],
                $row['ville'] ?? '',
                $row['telephone'] ?? '',
                $row['dossier_numero'] ?? '',
                $row['motif_consultation'] ?? '',
                $row['diagnostic_principal'] ?? '',
                $row['bilans_labo'] ?? '',
                $row['traitements_complets'],
            ], ';');
        }
        fclose($out);
        exit;
    }

    public function registreExportPdf(): void {
        $this->auth->requirePermission('dashboard', 'read');
        $db = (new Database())->getConnection();
        $userId = (int)($_SESSION['user_id'] ?? 0);
        $result = $this->_buildRegistreQuery($db, $userId, $_GET);

        $doctorNom = $_SESSION['nom_complet'] ?? ($_SESSION['user_nom'] ?? 'Dr.');
        $rows = $result['rows'];
        require_once __DIR__ . '/../views/dashboard/registre_pdf.php';
        exit;
    }

    /* ──────────────────────────────────────────────────────────────────
     * POST dashboard/changer-service
     * Permet au médecin de basculer temporairement vers un service autorisé
     * (Urgences, Consultations externes, Consultations externes PHP)
     * ────────────────────────────────────────────────────────────────── */
    public function changerService(): void {
        ob_clean();
        header('Content-Type: application/json; charset=utf-8');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Méthode non autorisée.']);
            return;
        }

        $serviceId = (int)($_POST['service_id'] ?? 0);
        if ($serviceId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Service invalide.']);
            return;
        }

        $db = (new Database())->getConnection();

        // Uniquement Urgences ou Consultations externes (avec ou sans PHP)
        $stmt = $db->prepare("
            SELECT id, nom_service FROM services
            WHERE id = ?
              AND (
                  LOWER(nom_service) LIKE '%urgence%'
                  OR (LOWER(nom_service) LIKE '%consult%' AND LOWER(nom_service) LIKE '%ext%')
              )
            LIMIT 1
        ");
        $stmt->execute([$serviceId]);
        $service = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$service) {
            echo json_encode(['success' => false, 'message' => 'Ce service n\'est pas autorisé pour le changement temporaire.']);
            return;
        }

        // Mémoriser le service d'origine au premier changement
        if (empty($_SESSION['service_id_origine'])) {
            $_SESSION['service_id_origine']  = $_SESSION['service_id']  ?? 0;
            $_SESSION['nom_service_origine'] = $_SESSION['nom_service'] ?? '';
        }

        $_SESSION['service_id']  = (int)$service['id'];
        $_SESSION['nom_service'] = $service['nom_service'];

        echo json_encode([
            'success'     => true,
            'service_id'  => (int)$service['id'],
            'nom_service' => $service['nom_service'],
        ]);
    }

    /* ──────────────────────────────────────────────────────────────────
     * POST dashboard/retourner-service-origine
     * Remet le médecin dans son service d'origine
     * ────────────────────────────────────────────────────────────────── */
    public function retournerServiceOrigine(): void {
        ob_clean();
        header('Content-Type: application/json; charset=utf-8');

        if (empty($_SESSION['service_id_origine'])) {
            echo json_encode(['success' => false, 'message' => 'Vous êtes déjà dans votre service d\'origine.']);
            return;
        }

        $_SESSION['service_id']  = (int)$_SESSION['service_id_origine'];
        $_SESSION['nom_service'] = $_SESSION['nom_service_origine'] ?? '';
        unset($_SESSION['service_id_origine'], $_SESSION['nom_service_origine']);

        echo json_encode(['success' => true, 'nom_service' => $_SESSION['nom_service']]);
    }

}