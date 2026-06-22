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
FICHIER : index.php
POINT D'ENTRÉE PRINCIPAL - DME HOSPITAL (Version Intégrale 2026)
============================================================================ */

session_start();
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'app/middleware/CompressionMiddleware.php';

// Activer la compression si configurée
if (defined('COMPRESSION_ENABLED') && COMPRESSION_ENABLED) {
    CompressionMiddleware::enable();
}

// Nettoyage de l'URL pour le routage
$request = $_SERVER['REQUEST_URI'];
$request = str_replace('/dme_hospital/', '', $request);
$request = strtok($request, '?');
$request = trim($request, '/');

// ── Guard : forcer le changement de mot de passe avant toute navigation ──
$_routesLibres = ['login', 'logout', 'changer-mot-de-passe', 'select-service', 'verify-service', 'forgot-password', 'reset-password'];
if (!empty($_SESSION['logged_in'])
    && !empty($_SESSION['must_change_password'])
    && !in_array($request, $_routesLibres)) {
    header('Location: ' . BASE_URL . 'changer-mot-de-passe');
    exit;
}

// ── Guard : vérification de licence (non-ADMIN uniquement) ──────────────────
if (!empty($_SESSION['logged_in'])
    && !in_array(strtoupper($_SESSION['user_role'] ?? ''), ['ADMIN', 'SUPER_ADMIN'])) {

    $now = time();
    $licOk = isset($_SESSION['_lic_ok_until']) && $_SESSION['_lic_ok_until'] > $now;

    if (!$licOk) {
        // Routes exemptées de la vérification de licence
        $_routesLicence = [
            'login', 'logout', 'changer-mot-de-passe', 'select-service', 'verify-service',
            'licence-expiree',
        ];
        $exempted = in_array($request, $_routesLicence)
            || str_starts_with($request, 'admin/licences');

        if (!$exempted) {
            require_once 'app/controllers/LicenceController.php';
            $lic = LicenceController::getLicenceActiveStatic();
            if ($lic) {
                // Valider le cache 1h
                $_SESSION['_lic_ok_until'] = $now + 3600;
            } else {
                header('Location: ' . BASE_URL . 'licence-expiree');
                exit;
            }
        }
    }
}

// ── Garde médicale : définit GARDE_ACTIVE pour les controllers ──────────────
if (!empty($_SESSION['logged_in']) && !empty($_SESSION['user_id'])) {
    require_once 'app/services/GardeService.php';
    define('GARDE_ACTIVE', GardeService::isOnDuty((int)$_SESSION['user_id']));
} else {
    define('GARDE_ACTIVE', false);
}

// Routeur Principal
switch(true) {

    /* ============================================================
       1. AUTHENTIFICATION & SESSIONS
       ============================================================ */
    case ($request == 'licence-expiree'):
        require_once 'app/views/licence_expiree.php';
        break;

    case ($request == 'login'):
        require_once 'app/controllers/AuthController.php';
        (new AuthController())->login();
        break;

    case ($request == 'logout'):
        require_once 'app/controllers/AuthController.php';
        (new AuthController())->logout();
        break;

    case ($request == 'select-service'):
        require_once 'app/controllers/AuthController.php';
        (new AuthController())->selectService();
        break;

    case ($request == 'verify-service'):
        require_once 'app/controllers/AuthController.php';
        (new AuthController())->verifyService();
        break;

    case ($request == 'forgot-password'):
        require_once 'app/controllers/AuthController.php';
        (new AuthController())->forgotPassword();
        break;

    case ($request == 'reset-password'):
        require_once 'app/controllers/AuthController.php';
        (new AuthController())->resetPassword();
        break;

    /* ============================================================
       2. DASHBOARDS & ACTIONS AJAX DASHBOARD
       ============================================================ */
    case ($request == '' || $request == '/' || $request == 'dashboard'):
        require_once 'app/controllers/DashboardController.php';
        (new DashboardController())->index();
        break;

    case ($request == 'dashboard/add-task'):
        require_once 'app/controllers/DashboardController.php';
        (new DashboardController())->addTask();
        break;

    case ($request == 'dashboard/toggle-task'):
        require_once 'app/controllers/DashboardController.php';
        (new DashboardController())->toggleTask();
        break;

    case ($request == 'dashboard/delete-task'):
        require_once 'app/controllers/DashboardController.php';
        (new DashboardController())->deleteTask();
        break;

    case ($request == 'dashboard/hospitaliser'): // Déclenche l'alerte pour l'infirmier
        require_once 'app/controllers/DashboardController.php';
        (new DashboardController())->hospitaliserConsult();
        break;

    case ($request == 'dashboard/evolution-data'): // API pour les graphiques Chart.js
        require_once 'app/controllers/DashboardController.php';
        (new DashboardController())->getEvolutionData();
        break;

    case (preg_match('#^dashboard/suivi-infirmier/(\d+)$#', $request, $matches) === 1): // API suivi infirmier pour modal médecin
        require_once 'app/controllers/DashboardController.php';
        (new DashboardController())->suiviInfirmierJson((int)$matches[1]);
        break;

    case ($request === 'dashboard/save-cat'): // API POST — CAT rapide depuis modal médecin
        require_once 'app/controllers/DashboardController.php';
        (new DashboardController())->saveCat();
        break;

    case ($request === 'dashboard/attente-par-date'): // API GET — file d'attente filtrée par date
        require_once 'app/controllers/DashboardController.php';
        (new DashboardController())->attenteParDate();
        break;

    case ($request === 'dashboard/changer-service'): // POST — changement temporaire de service
        require_once 'app/controllers/DashboardController.php';
        (new DashboardController())->changerService();
        break;

    case ($request === 'dashboard/retourner-service-origine'): // POST — retour au service d'origine
        require_once 'app/controllers/DashboardController.php';
        (new DashboardController())->retournerServiceOrigine();
        break;

    case ($request == 'dashboard/kpi-directeur'): // API temps réel pour le dashboard Directeur
        require_once 'app/controllers/DashboardController.php';
        (new DashboardController())->kpiDirecteur();
        break;

    case (preg_match('#^dashboard/kpi-details/([\w-]+)$#', $request, $matches) === 1): // AJAX détails enrichis KPI
        require_once 'app/controllers/DashboardController.php';
        (new DashboardController())->kpiDetails($matches[1]);
        break;

    case ($request == 'dashboard/export'): // Export CSV ou impression PDF
        require_once 'app/controllers/DashboardController.php';
        (new DashboardController())->exportStats();
        break;

    case ($request == 'dashboard/registre-consultations'):
        require_once 'app/controllers/DashboardController.php';
        (new DashboardController())->registreConsultations();
        break;

    case ($request == 'dashboard/registre-export-csv'):
        require_once 'app/controllers/DashboardController.php';
        (new DashboardController())->registreExportCsv();
        break;

    case ($request == 'dashboard/registre-export-pdf'):
        require_once 'app/controllers/DashboardController.php';
        (new DashboardController())->registreExportPdf();
        break;

    case ($request == 'medecin/resultats-bilan'): // API AJAX résultats bilan labo
        require_once 'app/controllers/DashboardController.php';
        (new DashboardController())->resultatsBilan();
        break;

    /* ============================================================
       SUIVI PATIENTS EXTERNES
       ============================================================ */
    case ($request == 'suivi-externe'):
        require_once 'app/controllers/SuiviExterneController.php';
        (new SuiviExterneController())->dashboard();
        break;

    case ($request == 'suivi-externe/confirmer-diagnostic'):
        require_once 'app/controllers/SuiviExterneController.php';
        (new SuiviExterneController())->confirmerDiagnostic();
        break;

    case ($request == 'suivi-externe/reviser-diagnostic'):
        require_once 'app/controllers/SuiviExterneController.php';
        (new SuiviExterneController())->reviserDiagnostic();
        break;

    case ($request == 'suivi-externe/planifier-rdv-bilan'):
        require_once 'app/controllers/SuiviExterneController.php';
        (new SuiviExterneController())->planifierRdvBilan();
        break;

    /* ============================================================
       3. GESTION DES PATIENTS (DME)
       ============================================================ */
    case ($request == 'patients'):
        require_once 'app/controllers/PatientController.php';
        (new PatientController())->index();
        break;

    case ($request == 'patients/nouveau'):
        require_once 'app/controllers/PatientController.php';
        (new PatientController())->nouveau();
        break;

    case ($request == 'patients/store'):
        require_once 'app/controllers/PatientController.php';
        (new PatientController())->store();
        break;

    case ($request === 'patients/verifier-doublon'):
        require_once 'app/controllers/PatientController.php';
        (new PatientController())->verifierDoublon();
        break;

    case (preg_match('/patients\/dossier\/(\d+)/', $request, $matches)):
        require_once 'app/controllers/PatientController.php';
        (new PatientController())->dossier($matches[1]);
        break;

    case (preg_match('#^patients/imprimer-recap/(\d+)$#', $request, $matches)):
        require_once 'app/controllers/PatientController.php';
        (new PatientController())->printRecap((int)$matches[1]);
        break;

    case (preg_match('#^patients/update-info/(\d+)$#', $request, $matches)):
        require_once 'app/controllers/PatientController.php';
        (new PatientController())->updatePersonalInfo($matches[1]);
        break;

    case ($request === 'patients/creer-rapide'):
        require_once 'app/controllers/PatientController.php';
        (new PatientController())->creerRapide();
        break;

    case ($request === 'patients/recherche'):
        require_once 'app/controllers/PatientController.php';
        (new PatientController())->rechercherPatient();
        break;

    /* ============================================================
       MODULE INFIRMIER — Consultations infirmières
       ============================================================ */
    case (preg_match('#^infirmier/consultation/(\d+)$#', $request, $matches) === 1):
        require_once 'app/controllers/InfirmierController.php';
        (new InfirmierController())->formulaire((int)$matches[1]);
        break;

    /* ── INFIRMIER CONSULTANT — Formulaire simplifié ── */
    case (preg_match('#^infirmier-consultant/consulter/(\d+)$#', $request, $matches) === 1):
        require_once 'app/controllers/InfirmierController.php';
        (new InfirmierController())->formulaireConsultant((int)$matches[1]);
        break;

    case ($request === 'infirmier-consultant/consulter/sauvegarder'):
        require_once 'app/controllers/InfirmierController.php';
        (new InfirmierController())->sauvegarder();
        break;

    case ($request === 'infirmier/consultation/sauvegarder'):
        require_once 'app/controllers/InfirmierController.php';
        (new InfirmierController())->sauvegarder();
        break;

    case (preg_match('#^infirmier/consultation/decision-observation/(\d+)$#', $request, $matches) === 1):
        require_once 'app/controllers/InfirmierController.php';
        (new InfirmierController())->decisionObservation((int)$matches[1]);
        break;

    case ($request === 'infirmier/hospitaliser-depuis-consultation'):
        require_once 'app/controllers/InfirmierController.php';
        (new InfirmierController())->hospitaliserDepuisConsultation();
        break;

    /* ============================================================
       MODULE MAJOR — Supervision de service
       ============================================================ */
    case ($request == 'major'):
    case ($request == 'major/dashboard'):
        require_once 'app/controllers/MajorController.php';
        (new MajorController())->dashboard();
        break;

    case ($request == 'major/marquer-retard'):
        require_once 'app/controllers/MajorController.php';
        (new MajorController())->marquerRetard();
        break;

    case ($request == 'major/reassigner'):
        require_once 'app/controllers/MajorController.php';
        (new MajorController())->reassignerSoin();
        break;

    case ($request == 'major/annoter'):
        require_once 'app/controllers/MajorController.php';
        (new MajorController())->annoterSoin();
        break;

    case (preg_match('/patients\/mesures\/(\d+)/', $request, $matches)):
        require_once 'app/controllers/PatientController.php';
        (new PatientController())->mesures($matches[1]);
        break;

    case ($request == 'patients/save-constantes'):
        require_once 'app/controllers/PatientController.php';
        (new PatientController())->saveConstantes();
        break;

    case ($request == 'patients/save-mesures'):
        require_once 'app/controllers/PatientController.php';
        (new PatientController())->saveMesures();
        break;

    case ($request == 'patients/upload-document'):
        require_once 'app/controllers/PatientController.php';
        (new PatientController())->uploadDocument();
        break;

    case ($request == 'patients/mes-patients'):
        require_once 'app/controllers/PatientController.php';
        (new PatientController())->mesPatients();
        break;

    /* ============================================================
       4. MODULE URGENCES (SAU) - COCKPIT DÉDIÉ
       ============================================================ */
    case ($request == 'urgences'):
        require_once 'app/controllers/UrgencesController.php';
        (new UrgencesController())->index();
        break;

    case ($request == 'urgences/nouvelle-admission'):
        require_once 'app/controllers/UrgencesController.php';
        (new UrgencesController())->nouvelleAdmission();
        break;

    case ($request == 'urgences/save-massive'):
        require_once 'app/controllers/UrgencesController.php';
        (new UrgencesController())->saveMassive();
        break;

    case ($request == 'urgences/save-single'):
        require_once 'app/controllers/UrgencesController.php';
        (new UrgencesController())->saveSingle();
        break;

    case (preg_match('/urgences\/triage\/(\d+)/', $request, $matches)):
        require_once 'app/controllers/UrgencesController.php';
        (new UrgencesController())->triage($matches[1]);
        break;

    case ($request == 'urgences/save-triage'):
        require_once 'app/controllers/UrgencesController.php';
        (new UrgencesController())->saveTriage();
        break;

    case ($request == 'urgences/transferer'):
        require_once 'app/controllers/UrgencesController.php';
        (new UrgencesController())->transferer();
        break;

    case ($request == 'urgences/valider-hospitalisation'):
        require_once 'app/controllers/UrgencesController.php';
        (new UrgencesController())->validerHospitalisation();
        break;

    case ($request == 'urgences/liberer-lit'):
        require_once 'app/controllers/UrgencesController.php';
        (new UrgencesController())->libererLit();
        break;

    case ($request == 'urgences/accueil-secretaire'):
        require_once 'app/controllers/UrgencesController.php';
        (new UrgencesController())->accueilSecretaire();
        break;

    case ($request == 'urgences/enregistrer-patient'):
        require_once 'app/controllers/UrgencesController.php';
        (new UrgencesController())->enregistrerPatientUrgences();
        break;

    case (preg_match('#^urgences/prendre-en-charge/(\d+)$#', $request, $matches) === 1):
        require_once 'app/controllers/UrgencesController.php';
        (new UrgencesController())->prendreEnCharge((int)$matches[1]);
        break;

    case ($request == 'urgences/transferer-lit'):
        require_once 'app/controllers/UrgencesController.php';
        (new UrgencesController())->transfererLit();
        break;

    case ($request == 'urgences/partager-dossier'):
        require_once 'app/controllers/UrgencesController.php';
        (new UrgencesController())->partagerDossierAjax();
        break;

    case (preg_match('#^urgences/partage-vu/(\d+)$#', $request, $matches) === 1):
        require_once 'app/controllers/UrgencesController.php';
        (new UrgencesController())->marquerPartageVu((int)$matches[1]);
        break;

    case ($request == 'urgences/rechercher-patient-connu'):
        require_once 'app/controllers/UrgencesController.php';
        (new UrgencesController())->rechercherPatientConnu();
        break;

    case (preg_match('#^urgences/historique-patient/(\d+)$#', $request, $matches) === 1):
        require_once 'app/controllers/UrgencesController.php';
        (new UrgencesController())->historiquePatient((int)$matches[1]);
        break;

    case ($request == 'urgences/readmettre-patient'):
        require_once 'app/controllers/UrgencesController.php';
        (new UrgencesController())->readmettrePatient();
        break;

    /* ============================================================
       5. CONSULTATIONS (WORKFLOW 7 ÉTAPES)
       ============================================================ */
    case ($request == 'consultation'):
        require_once 'app/controllers/ConsultationController.php';
        (new ConsultationController())->selection();
        break;

    case ($request == 'consultation/search-patients'):
        require_once 'app/controllers/ConsultationController.php';
        (new ConsultationController())->searchPatients();
        break;

    case ($request == 'consultation/commencer'):
        require_once 'app/controllers/ConsultationController.php';
        (new ConsultationController())->commencerConsultation();
        break;

    /* ============================================================
       CONSULTATIONS PÉDIATRIQUES
       ============================================================ */
    case (preg_match('/consultation-ped\/formulaire\/(\d+)/', $request, $matches)):
        require_once 'app/controllers/PediatricConsultationController.php';
        (new PediatricConsultationController())->formulaire((int)$matches[1]);
        break;

    case ($request == 'consultation-ped/sauvegarder-etape'):
        require_once 'app/controllers/PediatricConsultationController.php';
        (new PediatricConsultationController())->sauvegarderEtape();
        break;

    case (preg_match('/consultation-ped\/voir\/(\d+)/', $request, $matches)):
        require_once 'app/controllers/PediatricConsultationController.php';
        (new PediatricConsultationController())->voir((int)$matches[1]);
        break;

    case (preg_match('/consultation-ped\/liste\/(\d+)/', $request, $matches)):
        require_once 'app/controllers/PediatricConsultationController.php';
        (new PediatricConsultationController())->liste((int)$matches[1]);
        break;

    /* ── NÉONATOLOGIE ─────────────────────────────────────────────── */
    case ($request == 'neonatologie'):
        require_once 'app/controllers/NeonatologyController.php';
        (new NeonatologyController())->dashboard();
        break;

    case (preg_match('/^neonatologie\/examen\/(\d+)$/', $request, $matches) === 1):
        require_once 'app/controllers/NeonatologyController.php';
        (new NeonatologyController())->examen((int)$matches[1]);
        break;

    case ($request == 'neonatologie/sauvegarder'):
        require_once 'app/controllers/NeonatologyController.php';
        (new NeonatologyController())->sauvegarder();
        break;

    case (preg_match('/^neonatologie\/voir\/(\d+)$/', $request, $matches) === 1):
        require_once 'app/controllers/NeonatologyController.php';
        (new NeonatologyController())->voir((int)$matches[1]);
        break;

    case (preg_match('/^neonatologie\/suivi\/(\d+)$/', $request, $matches) === 1):
        require_once 'app/controllers/NeonatologyController.php';
        (new NeonatologyController())->suivi((int)$matches[1]);
        break;

    case ($request == 'neonatologie/soins-ajouter'):
        require_once 'app/controllers/NeonatologyController.php';
        (new NeonatologyController())->ajouterSoin();
        break;

    case ($request == 'neonatologie/parametres-ajouter'):
        require_once 'app/controllers/NeonatologyController.php';
        (new NeonatologyController())->ajouterParametre();
        break;

    case (preg_match('/^neonatologie\/transfusion\/(\d+)$/', $request, $matches) === 1):
        require_once 'app/controllers/NeonatologyController.php';
        (new NeonatologyController())->transfusion((int)$matches[1]);
        break;

    case ($request == 'neonatologie/transfusion-sauvegarder'):
        require_once 'app/controllers/NeonatologyController.php';
        (new NeonatologyController())->sauvegarderTransfusion();
        break;

    case (preg_match('/^neonatologie\/planification\/(\d+)$/', $request, $matches) === 1):
        require_once 'app/controllers/NeonatologyController.php';
        (new NeonatologyController())->planification((int)$matches[1]);
        break;

    case ($request == 'neonatologie/planification-sauvegarder'):
        require_once 'app/controllers/NeonatologyController.php';
        (new NeonatologyController())->sauvegarderPlanification();
        break;

    case ($request == 'neonatologie/soin-executer'):
        require_once 'app/controllers/NeonatologyController.php';
        (new NeonatologyController())->marquerSoinExecute();
        break;

    case (preg_match('/^neonatologie\/gavage\/(\d+)$/', $request, $matches) === 1):
        require_once 'app/controllers/NeonatologyController.php';
        (new NeonatologyController())->gavage((int)$matches[1]);
        break;

    case ($request == 'neonatologie/gavage-sauvegarder'):
        require_once 'app/controllers/NeonatologyController.php';
        (new NeonatologyController())->sauvegarderGavage();
        break;

    case (preg_match('/^neonatologie\/post-natale\/(\d+)$/', $request, $matches) === 1):
        require_once 'app/controllers/NeonatologyController.php';
        (new NeonatologyController())->postNatale((int)$matches[1]);
        break;

    case ($request == 'neonatologie/post-natale-sauvegarder'):
        require_once 'app/controllers/NeonatologyController.php';
        (new NeonatologyController())->sauvegarderPostNatale();
        break;

    case ($request == 'neonatologie/admettre-nouveau-ne'):
        require_once 'app/controllers/NeonatologyController.php';
        (new NeonatologyController())->admettreNouveauNe();
        break;

    case (preg_match('/^neonatologie\/observation\/(\d+)$/', $request, $matches) === 1):
        require_once 'app/controllers/NeonatologyController.php';
        (new NeonatologyController())->observation((int)$matches[1]);
        break;

    case ($request == 'neonatologie/observation-sauvegarder'):
        require_once 'app/controllers/NeonatologyController.php';
        (new NeonatologyController())->sauvegarderObservation();
        break;

    case (preg_match('/^neonatologie\/observation-voir\/(\d+)$/', $request, $matches) === 1):
        require_once 'app/controllers/NeonatologyController.php';
        (new NeonatologyController())->voirObservation((int)$matches[1]);
        break;

    case ($request == 'neonatologie/liberer'):
        require_once 'app/controllers/NeonatologyController.php';
        (new NeonatologyController())->liberer();
        break;

    case ($request == 'neonatologie/transferer'):
        require_once 'app/controllers/NeonatologyController.php';
        (new NeonatologyController())->transferer();
        break;

    /* ── PMI — Protection Maternelle et Infantile ─────────────────── */
    case ($request == 'pmi'):
        require_once 'app/controllers/PmiController.php';
        (new PmiController())->dashboard();
        break;
    case (preg_match('/^pmi\/cpn\/voir\/(\d+)$/', $request, $matches) === 1):
        require_once 'app/controllers/PmiController.php';
        (new PmiController())->cpnVoir((int)$matches[1]);
        break;
    case (preg_match('/^pmi\/cpn\/(\d+)$/', $request, $matches) === 1):
        require_once 'app/controllers/PmiController.php';
        (new PmiController())->cpnForm((int)$matches[1]);
        break;
    case ($request == 'pmi/cpn-sauvegarder'):
        require_once 'app/controllers/PmiController.php';
        (new PmiController())->cpnSave();
        break;
    case (preg_match('/^pmi\/enfant\/voir\/(\d+)$/', $request, $matches) === 1):
        require_once 'app/controllers/PmiController.php';
        (new PmiController())->enfantVoir((int)$matches[1]);
        break;
    case (preg_match('/^pmi\/enfant\/(\d+)$/', $request, $matches) === 1):
        require_once 'app/controllers/PmiController.php';
        (new PmiController())->enfantForm((int)$matches[1]);
        break;
    case ($request == 'pmi/enfant-sauvegarder'):
        require_once 'app/controllers/PmiController.php';
        (new PmiController())->enfantSave();
        break;
    case (preg_match('/^pmi\/atelier\/voir\/(\d+)$/', $request, $matches) === 1):
        require_once 'app/controllers/PmiController.php';
        (new PmiController())->atelierVoir((int)$matches[1]);
        break;
    case ($request == 'pmi/atelier'):
        require_once 'app/controllers/PmiController.php';
        (new PmiController())->atelierForm();
        break;
    case ($request == 'pmi/atelier-sauvegarder'):
        require_once 'app/controllers/PmiController.php';
        (new PmiController())->atelierSave();
        break;

    case ($request == 'consultation/formulaire'):
        require_once 'app/controllers/ConsultationController.php';
        (new ConsultationController())->formulaire();
        break;

    case ($request == 'consultation/autosave'):
        require_once 'app/controllers/ConsultationController.php';
        (new ConsultationController())->autosave();
        break;

    case ($request == 'consultation/sauvegarder'):
        require_once 'app/controllers/ConsultationController.php';
        (new ConsultationController())->sauvegarderEtape();
        break;

    case (preg_match('/consultation\/recapitulatif\/(\d+)/', $request, $matches)):
        require_once 'app/controllers/ConsultationController.php';
        (new ConsultationController())->recapitulatif($matches[1]);
        break;

    case (preg_match('/consultation\/modifier\/(\d+)/', $request, $matches)):
        require_once 'app/controllers/ConsultationController.php';
        (new ConsultationController())->modifier($matches[1]);
        break;

    case ($request == 'consultation/search-cim10'):
        require_once 'app/controllers/ConsultationController.php';
        (new ConsultationController())->searchCim10();
        break;

    case (preg_match('/consultation\/cloturer\/(\d+)/', $request, $matches)):
        require_once 'app/controllers/ConsultationController.php';
        (new ConsultationController())->cloturer($matches[1]);
        break;

    case (preg_match('/^consultation\/supprimer\/(\d+)$/', $request, $matches)):
        require_once 'app/controllers/ConsultationController.php';
        (new ConsultationController())->supprimer((int)$matches[1]);
        break;

    case ($request == 'consultation/decision-hospitalisation'):
        require_once 'app/controllers/ConsultationController.php';
        (new ConsultationController())->decisionHospitalisation();
        break;

    case ($request == 'consultation/transferer-patient'):
        require_once 'app/controllers/ConsultationController.php';
        (new ConsultationController())->transfererPatient();
        break;

    case (preg_match('/consultation\/imprimer-ordonnance\/(\d+)/', $request, $matches)):
        require_once 'app/controllers/ConsultationController.php';
        (new ConsultationController())->imprimerOrdonnancePharmacien($matches[1]);
        break;

    case ($request == 'consultation/confirmer-diagnostic'):
        require_once 'app/controllers/DashboardController.php';
        (new DashboardController())->confirmerDiagnostic();
        break;

    /* ============================================================
       6. HOSPITALISATION & SOINS (INFIRMIER / MÉDECIN)
       ============================================================ */
    case ($request == 'hospitalisation'):
        require_once 'app/controllers/HospitalisationController.php';
        (new HospitalisationController())->index();
        break;

    case (preg_match('#^hospitalisation/transferer/(\d+)$#', $request, $matches) === 1):
        require_once 'app/controllers/HospitalisationController.php';
        (new HospitalisationController())->transferer((int)$matches[1]);
        break;

    case ($request == 'hospitalisation/lits-disponibles'):
        require_once 'app/controllers/HospitalisationController.php';
        (new HospitalisationController())->litsDisponibles();
        break;

    case (preg_match('#^hospitalisation/assigner-lit/(\d+)$#', $request, $matches) === 1):
        require_once 'app/controllers/HospitalisationController.php';
        (new HospitalisationController())->assignerLit((int)$matches[1]);
        break;

    case (preg_match('#^hospitalisation/get-soin/(\d+)$#', $request, $matches) === 1):
        require_once 'app/controllers/HospitalisationController.php';
        (new HospitalisationController())->getSoin((int)$matches[1]);
        break;

    case (preg_match('#^hospitalisation/modifier-soin/(\d+)$#', $request, $matches) === 1):
        require_once 'app/controllers/HospitalisationController.php';
        (new HospitalisationController())->modifierSoin((int)$matches[1]);
        break;

    // Note : la route supprimer-soin existe déjà plus bas (ligne ~401)

    case ($request == 'hospitalisation/admettre'):
        require_once 'app/controllers/HospitalisationController.php';
        (new HospitalisationController())->admettre();
        break;

    case ($request == 'hospitalisation/creer-et-admettre'):
        require_once 'app/controllers/HospitalisationController.php';
        (new HospitalisationController())->creerEtAdmettre();
        break;

    case ($request == 'hospitalisation/changer-lit'):
        require_once 'app/controllers/HospitalisationController.php';
        (new HospitalisationController())->changerLit();
        break;

    case (preg_match('#^hospitalisation/liberer-lit/(\d+)$#', $request, $matches) === 1):
        require_once 'app/controllers/HospitalisationController.php';
        (new HospitalisationController())->libererLit((int)$matches[1]);
        break;

    case (preg_match('#^hospitalisation/readmettre/(\d+)$#', $request, $matches) === 1):
        require_once 'app/controllers/HospitalisationController.php';
        (new HospitalisationController())->readmettre((int)$matches[1]);
        break;

    case (preg_match('/hospitalisation\/planifier-soins\/(\d+)/', $request, $matches)):
        require_once 'app/controllers/HospitalisationController.php';
        (new HospitalisationController())->planifierSoins($matches[1]);
        break;

    case ($request == 'hospitalisation/save-plan'):
        require_once 'app/controllers/HospitalisationController.php';
        (new HospitalisationController())->savePlan();
        break;

    case (preg_match('/hospitalisation\/checklist\/(\d+)/', $request, $matches)):
        require_once 'app/controllers/HospitalisationController.php';
        (new HospitalisationController())->checklist($matches[1]);
        break;

    case ($request == 'hospitalisation/valider-soin'):
        require_once 'app/controllers/HospitalisationController.php';
        (new HospitalisationController())->validerSoinItem();
        break;

    case (preg_match('#^hospitalisation/supprimer-soin/(\d+)$#', $request, $matches) === 1):
        require_once 'app/controllers/HospitalisationController.php';
        (new HospitalisationController())->supprimerSoin((int)$matches[1]);
        break;

    case (preg_match('/hospitalisation\/observations-evolution\/(\d+)/', $request, $matches)):
        require_once 'app/controllers/HospitalisationController.php';
        (new HospitalisationController())->observationsEvolution($matches[1]);
        break;

    case ($request == 'hospitalisation/save-observation'):
        require_once 'app/controllers/HospitalisationController.php';
        (new HospitalisationController())->saveObservation();
        break;

    case (preg_match('/hospitalisation\/surveillance\/(\d+)/', $request, $matches)):
        require_once 'app/controllers/HospitalisationController.php';
        (new HospitalisationController())->surveillance($matches[1]);
        break;

    case (preg_match('/hospitalisation\/surveillance-intensive\/(\d+)/', $request, $matches)):
        require_once 'app/controllers/HospitalisationController.php';
        (new HospitalisationController())->surveillanceIntensive($matches[1]);
        break;

    case ($request == 'hospitalisation/save-si'):
        require_once 'app/controllers/HospitalisationController.php';
        (new HospitalisationController())->saveSI();
        break;

    case (preg_match('/hospitalisation\/fiche-transfusionnelle\/(\d+)/', $request, $matches)):
        require_once 'app/controllers/HospitalisationController.php';
        (new HospitalisationController())->ficheTransfusionnelle($matches[1]);
        break;

    case ($request == 'hospitalisation/save-transfusion'):
        require_once 'app/controllers/HospitalisationController.php';
        (new HospitalisationController())->saveTransfusion();
        break;

    // ── RÉÉVALUATION MÉDICALE HOSPITALISÉ (RONDE / VISITE AU CHEVET) ──
    case (preg_match('#^hospitalisation/reevaluation/(\d+)$#', $request, $matches) === 1):
        require_once 'app/controllers/HospitalisationController.php';
        (new HospitalisationController())->reevaluation((int)$matches[1]);
        break;

    case (preg_match('#^hospitalisation/update-reevaluation/(\d+)$#', $request, $matches) === 1):
        require_once 'app/controllers/HospitalisationController.php';
        (new HospitalisationController())->updateReevaluation((int)$matches[1]);
        break;

    case ($request == 'hospitalisation/save-reevaluation'):
        require_once 'app/controllers/HospitalisationController.php';
        (new HospitalisationController())->saveReevaluation();
        break;

    case ($request == 'hospitalisation/creer-bilans-reeval'):
        require_once 'app/controllers/HospitalisationController.php';
        (new HospitalisationController())->creerBilansReeval();
        break;

    case ($request == 'hospitalisation/search-medicament-reeval'):
        require_once 'app/controllers/HospitalisationController.php';
        (new HospitalisationController())->searchMedicamentReeval();
        break;

    /* ============================================================
       PANSEMENTS — Fiche de suivi des pansements
       ============================================================ */
    case ($request === 'pansement/sauvegarder-identification'):
        require_once 'app/controllers/PansementController.php';
        (new PansementController())->sauvegarderIdentification();
        break;

    case ($request === 'pansement/ajouter-entree'):
        require_once 'app/controllers/PansementController.php';
        (new PansementController())->ajouterEntree();
        break;

    case ($request === 'pansement/historique'):
        require_once 'app/controllers/PansementController.php';
        (new PansementController())->historique();
        break;

    case ($request === 'pansement/get-identification'):
        require_once 'app/controllers/PansementController.php';
        (new PansementController())->getIdentification();
        break;

    case ($request === 'pansement/supprimer-entree'):
        require_once 'app/controllers/PansementController.php';
        (new PansementController())->supprimerEntree();
        break;

    case ($request === 'pansement/modifier-entree'):
        require_once 'app/controllers/PansementController.php';
        (new PansementController())->modifierEntree();
        break;

    case ($request === 'pansement/ajouter-photo-entree'):
        require_once 'app/controllers/PansementController.php';
        (new PansementController())->ajouterPhotoEntree();
        break;

    case ($request === 'pansement/upload-photo'):
        require_once 'app/controllers/PansementController.php';
        (new PansementController())->uploadPhoto();
        break;

    case ($request === 'pansement/supprimer-photo'):
        require_once 'app/controllers/PansementController.php';
        (new PansementController())->supprimerPhoto();
        break;

    /* ============================================================
       7. LITS & BANQUE DE SANG
       ============================================================ */
    case ($request == 'lits'):
        require_once 'app/controllers/LitController.php';
        (new LitController())->gestion();
        break;

    case ($request == 'lits/get-patients-admissibles'):
        require_once 'app/controllers/LitController.php';
        (new LitController())->getPatientsAdmissibles();
        break;

    case ($request == 'lits/disponibles'):
        require_once 'app/controllers/LitController.php';
        (new LitController())->getLitsDisponibles();
        break;

    case ($request == 'lits/get-lit-patient'):
        require_once 'app/controllers/LitController.php';
        (new LitController())->getLitPatient();
        break;

    case ($request == 'lits/confirmer-admission'):
        require_once 'app/controllers/LitController.php';
        (new LitController())->confirmerAdmission();
        break;

    case ($request == 'lits/decharger'):
        require_once 'app/controllers/LitController.php';
        (new LitController())->dechargerPatient();
        break;

    case ($request == 'lits/changer-statut'):
        require_once 'app/controllers/LitController.php';
        (new LitController())->changerStatut();
        break;

    case (preg_match('/lits\/billet-sortie\/(\d+)/', $request, $matches)):
        require_once 'app/controllers/LitController.php';
        (new LitController())->billetSortie($matches[1]);
        break;

    case ($request == 'banque-sang'):
        require_once 'app/controllers/BloodBankController.php';
        (new BloodBankController())->index();
        break;

    case ($request == 'banque-sang/check-stock'):
        require_once 'app/controllers/BloodBankController.php';
        (new BloodBankController())->checkStock();
        break;

    case ($request == 'banque-sang/indisponible'):
        require_once 'app/controllers/BloodBankController.php';
        (new BloodBankController())->markUnavailable();
        break;

    case ($request == 'banque-sang/delivrer'):
        require_once 'app/controllers/BloodBankController.php';
        (new BloodBankController())->deliverRequest();
        break;

    /* ============================================================
       PRESCRIPTIONS / ORDONNANCES
       ============================================================ */
    case ($request == 'prescription'):
        require_once 'app/controllers/PrescriptionController.php';
        (new PrescriptionController())->index();
        break;

    case ($request == 'prescription/create'):
        require_once 'app/controllers/PrescriptionController.php';
        (new PrescriptionController())->create();
        break;

    case ($request == 'prescription/save'):
        require_once 'app/controllers/PrescriptionController.php';
        (new PrescriptionController())->save();
        break;

    case (preg_match('#^prescription/edit/(\d+)$#', $request, $matches) === 1):
        require_once 'app/controllers/PrescriptionController.php';
        (new PrescriptionController())->edit($matches[1]);
        break;

    case (preg_match('#^prescription/update/(\d+)$#', $request, $matches) === 1):
        require_once 'app/controllers/PrescriptionController.php';
        (new PrescriptionController())->updatePrescription($matches[1]);
        break;

    case ($request == 'prescription/print'):
        require_once 'app/controllers/PrescriptionController.php';
        (new PrescriptionController())->print();
        break;

    case ($request == 'prescription/signer-et-envoyer'):
        require_once 'app/controllers/PrescriptionController.php';
        (new PrescriptionController())->signerEtEnvoyer();
        break;

    case ($request == 'prescription/archives'):
        require_once 'app/controllers/PrescriptionController.php';
        (new PrescriptionController())->archives();
        break;

    case ($request == 'prescription/verify'):
        require_once 'app/controllers/PrescriptionController.php';
        require_once 'app/services/SignatureService.php';
        header('Content-Type: application/json');
        $id   = (int)($_POST['id']   ?? 0);
        $hash = trim($_POST['hash']  ?? '');
        $valid = $id && $hash ? (new SignatureService())->verifyHash('ORDONNANCE', $id, $hash) : false;
        echo json_encode(['valid' => $valid]);
        break;

    case ($request == 'prescription/check-stock'):
        require_once 'app/controllers/PrescriptionController.php';
        (new PrescriptionController())->checkStock();
        break;

    case ($request == 'prescription/history'):
        require_once 'app/controllers/PrescriptionController.php';
        (new PrescriptionController())->history();
        break;

    case ($request == 'prescription/par-ordre'):
        require_once 'app/controllers/PrescriptionController.php';
        (new PrescriptionController())->parOrdreCreate();
        break;

    case ($request == 'prescription/par-ordre-save'):
        require_once 'app/controllers/PrescriptionController.php';
        (new PrescriptionController())->parOrdreSave();
        break;

    /* ============================================================
       8. PHARMACIE & LABORATOIRE
       ============================================================ */
        // Route pour le Dashboard (doit être exacte)
    case ($request == 'pharmacie'):
        require_once 'app/controllers/PharmacieController.php';
        (new PharmacieController())->index();
        break;

    // Route DYNAMIQUE pour le traitement (Regex corrigée avec délimiteurs #)
    case (preg_match('#^pharmacie/traitement/([0-9]+)$#', $request, $matches)):
        require_once 'app/controllers/PharmacieController.php';
        (new PharmacieController())->traitement($matches[1]);
        break;

    case ($request == 'pharmacie/stock'):
        require_once 'app/controllers/PharmacieController.php';
        (new PharmacieController())->stock();
        break;

    case ($request == 'pharmacie/delivrer'):
        require_once 'app/controllers/PharmacieController.php';
        (new PharmacieController())->delivrer();
        break;

    case ($request == 'pharmacie/declarer-rupture'):
        require_once 'app/controllers/PharmacieController.php';
        (new PharmacieController())->declarerRupture();
        break;

    case (preg_match('#^pharmacie/modifier-ligne/(\d+)$#', $request, $matches) === 1):
        require_once 'app/controllers/PharmacieController.php';
        (new PharmacieController())->modifierLigne((int)$matches[1]);
        break;

    // ── Intégration SAGE 100 ─────────────────────────────────────
    case ($request === 'pharmacie/sage'):
        require_once 'app/controllers/SageSyncController.php';
        (new SageSyncController())->dashboard();
        break;
    case ($request === 'pharmacie/sage/config'):
        require_once 'app/controllers/SageSyncController.php';
        (new SageSyncController())->configPage();
        break;
    case ($request === 'pharmacie/sage/save-config'):
        require_once 'app/controllers/SageSyncController.php';
        (new SageSyncController())->saveConfig();
        break;
    case ($request === 'pharmacie/sage/test-connexion'):
        require_once 'app/controllers/SageSyncController.php';
        (new SageSyncController())->testConnection();
        break;
    case ($request === 'pharmacie/sage/import-csv'):
        require_once 'app/controllers/SageSyncController.php';
        (new SageSyncController())->importCSV();
        break;
    case ($request === 'pharmacie/sage/sync-now'):
        require_once 'app/controllers/SageSyncController.php';
        (new SageSyncController())->syncNow();
        break;
    case ($request === 'pharmacie/sage/apply-stock'):
        require_once 'app/controllers/SageSyncController.php';
        (new SageSyncController())->applyStock();
        break;
    case ($request === 'pharmacie/sage/apply-prix'):
        require_once 'app/controllers/SageSyncController.php';
        (new SageSyncController())->applyPrix();
        break;
    case ($request === 'pharmacie/sage/import-texte'):
        require_once 'app/controllers/SageSyncController.php';
        (new SageSyncController())->importText();
        break;
    case ($request === 'pharmacie/sage/auto-map'):
        require_once 'app/controllers/SageSyncController.php';
        (new SageSyncController())->autoMap();
        break;

    case ($request === 'pharmacie/sage/creer-meds-manquants'):
        require_once 'app/controllers/SageSyncController.php';
        (new SageSyncController())->creerMedicamentsManquants();
        break;

    case ($request === 'pharmacie/sage/reset-counters'):
        require_once 'app/controllers/SageSyncController.php';
        (new SageSyncController())->resetCounters();
        break;
    case ($request === 'pharmacie/sage/set-mapping'):
        require_once 'app/controllers/SageSyncController.php';
        (new SageSyncController())->setMapping();
        break;
    case ($request === 'pharmacie/sage/mapping'):
        require_once 'app/controllers/SageSyncController.php';
        (new SageSyncController())->mappingPage();
        break;
    case (preg_match('#^pharmacie/sage/prix/(\d+)$#', $request, $matches) === 1):
        require_once 'app/controllers/SageSyncController.php';
        (new SageSyncController())->getPrix((int)$matches[1]);
        break;
    // ── Pont SAGE direct ─────────────────────────────────────────
    case ($request === 'pharmacie/sage/bridge'):
        require_once 'app/controllers/SageSyncController.php';
        (new SageSyncController())->bridgePage();
        break;
    case ($request === 'pharmacie/sage/diagnose'):
        require_once 'app/controllers/SageSyncController.php';
        (new SageSyncController())->diagnoseBridge();
        break;
    case ($request === 'pharmacie/sage/list-dsns'):
        require_once 'app/controllers/SageSyncController.php';
        (new SageSyncController())->listDsns();
        break;
    case ($request === 'pharmacie/sage/depots'):
        require_once 'app/controllers/SageSyncController.php';
        (new SageSyncController())->getDepots();
        break;
    case ($request === 'pharmacie/sage/live-stock'):
        require_once 'app/controllers/SageSyncController.php';
        (new SageSyncController())->liveStock();
        break;
    case ($request === 'pharmacie/sage/search'):
        require_once 'app/controllers/SageSyncController.php';
        (new SageSyncController())->searchSage();
        break;
    case ($request === 'pharmacie/sage/save-auto-sync'):
        require_once 'app/controllers/SageSyncController.php';
        (new SageSyncController())->saveAutoSync();
        break;
    case ($request === 'pharmacie/sage/verify-credentials'):
        require_once 'app/controllers/SageSyncController.php';
        (new SageSyncController())->verifyCredentials();
        break;
    case ($request === 'pharmacie/sage/clear-session'):
        require_once 'app/controllers/SageSyncController.php';
        (new SageSyncController())->clearSageSession();
        break;
    case ($request === 'pharmacie/sage/auth-status'):
        require_once 'app/controllers/SageSyncController.php';
        (new SageSyncController())->getSageAuthStatus();
        break;

    // ── Stocks intelligents / Prédiction ruptures ────────────────
    case ($request === 'pharmacie/stocks'):
        require_once 'app/controllers/PharmacieStocksController.php';
        (new PharmacieStocksController())->dashboard();
        break;
    case ($request === 'pharmacie/stocks/analyse'):
        require_once 'app/controllers/PharmacieStocksController.php';
        (new PharmacieStocksController())->runAnalyse();
        break;
    case ($request === 'pharmacie/stocks/acquitter'):
        require_once 'app/controllers/PharmacieStocksController.php';
        (new PharmacieStocksController())->acquitterAlerte();
        break;
    case ($request === 'pharmacie/stocks/export-commande'):
        require_once 'app/controllers/PharmacieStocksController.php';
        (new PharmacieStocksController())->exportCommande();
        break;
    case ($request === 'pharmacie/stocks/stats'):
        require_once 'app/controllers/PharmacieStocksController.php';
        (new PharmacieStocksController())->getStatsJson();
        break;

    /* ============================================================
       SECTION NOTIFICATIONS UNIFIÉES
       ============================================================ */
    case ($request === 'notifications/poll'):
        require_once 'app/controllers/NotificationCenterController.php';
        (new NotificationCenterController())->poll();
        break;

    case (preg_match('#^notifications/mark-read/(\d+)$#', $request, $matches) === 1):
        require_once 'app/controllers/NotificationCenterController.php';
        (new NotificationCenterController())->markRead((int)$matches[1]);
        break;

    case ($request === 'notifications/mark-all-read'):
        require_once 'app/controllers/NotificationCenterController.php';
        (new NotificationCenterController())->markAllRead();
        break;

    case ($request === 'notifications/all'):
        require_once 'app/controllers/NotificationCenterController.php';
        (new NotificationCenterController())->listAll();
        break;

    case ($request === 'pharmacie/sage/test-python'):
        require_once 'app/controllers/SageSyncController.php';
        (new SageSyncController())->testPython();
        break;

    case ($request === 'pharmacie/sage/save-python-path'):
        require_once 'app/controllers/SageSyncController.php';
        (new SageSyncController())->savePythonPath();
        break;

    /* ============================================================
       SECTION LABORATOIRE
       ============================================================ */
    // Route pour la liste (Dashboard)
    case ($request == 'laboratoire'):
        require_once 'app/controllers/LaboratoireController.php';
        (new LaboratoireController())->index();
        break;

    // API : liste des examens disponibles pour le formulaire de consultation
    case ($request == 'laboratoire/examens-disponibles'):
        require_once 'app/controllers/LaboratoireController.php';
        (new LaboratoireController())->examensDisponibles();
        break;

    // Route pour traiter une demande spécifique (Regex corrigée)
    case (preg_match('#^laboratoire/traitement/([0-9]+)$#', $request, $matches)):
        require_once 'app/controllers/LaboratoireController.php';
        (new LaboratoireController())->traiterDemande($matches[1]);
        break;

    // Route pour la saisie des résultats
    case (preg_match('#^laboratoire/saisie-resultats/([0-9]+)$#', $request, $matches)):
        require_once 'app/controllers/LaboratoireController.php';
        (new LaboratoireController())->saisieResultats($matches[1]);
        break;

    case (preg_match('#^laboratoire/imprimer-resultats/([0-9]+)$#', $request, $matches)):
        require_once 'app/controllers/LaboratoireController.php';
        (new LaboratoireController())->imprimerResultats((int)$matches[1]);
        break;

    case ($request == 'laboratoire/sauvegarder-resultats'):
        require_once 'app/controllers/LaboratoireController.php';
        (new LaboratoireController())->sauvegarderResultats();
        break;

    case (preg_match('#^laboratoire/piece-jointe/([0-9]+)$#', $request, $matches)):
        require_once 'app/controllers/LaboratoireController.php';
        (new LaboratoireController())->downloadPieceJointe((int)$matches[1]);
        break;

    case (preg_match('#^laboratoire/piece-jointe-delete/([0-9]+)$#', $request, $matches)):
        require_once 'app/controllers/LaboratoireController.php';
        (new LaboratoireController())->deletePieceJointe((int)$matches[1]);
        break;

    case ($request == 'laboratoire/traiter-examens'):
        require_once 'app/controllers/LaboratoireController.php';
        (new LaboratoireController())->traiterExamens();
        break;

    // ── Workflow TECHNICIEN_LABO ──────────────────────────────────────────────

    case ($request == 'laboratoire/historique'):
        require_once 'app/controllers/LaboratoireController.php';
        (new LaboratoireController())->historique();
        break;

    // ── Secrétariat laboratoire ───────────────────────────────
    case ($request == 'laboratoire/secretariat'):
        require_once 'app/controllers/LaboratoireController.php';
        (new LaboratoireController())->secretariat();
        break;

    case (preg_match('#^laboratoire/facturer/([0-9]+)$#', $request, $matches)):
        require_once 'app/controllers/LaboratoireController.php';
        (new LaboratoireController())->facturer((int)$matches[1]);
        break;

    case (preg_match('#^laboratoire/examens-demande/([0-9]+)$#', $request, $matches)):
        require_once 'app/controllers/LaboratoireController.php';
        (new LaboratoireController())->getExamensDemande((int)$matches[1]);
        break;

    case ($request == 'laboratoire/facturer-examens'):
        require_once 'app/controllers/LaboratoireController.php';
        (new LaboratoireController())->facturerExamens();
        break;

    case ($request == 'laboratoire/get-techniciens'):
        require_once 'app/controllers/LaboratoireController.php';
        (new LaboratoireController())->getTechniciensJson();
        break;

    case (preg_match('#^laboratoire/examens-dispatch/([0-9]+)$#', $request, $matches)):
        require_once 'app/controllers/LaboratoireController.php';
        (new LaboratoireController())->examensDemandeJson((int)$matches[1]);
        break;

    case (preg_match('#^laboratoire/dispatcher/([0-9]+)$#', $request, $matches)):
        require_once 'app/controllers/LaboratoireController.php';
        (new LaboratoireController())->dispatcherExamens((int)$matches[1]);
        break;

    case ($request == 'laboratoire/soumettre-resultats-technicien'):
        require_once 'app/controllers/LaboratoireController.php';
        (new LaboratoireController())->soumettreResultatsTechnicien();
        break;

    case (preg_match('#^laboratoire/valider-examen/([0-9]+)$#', $request, $matches)):
        require_once 'app/controllers/LaboratoireController.php';
        (new LaboratoireController())->validerExamen((int)$matches[1]);
        break;

    case (preg_match('#^laboratoire/rejeter-examen/([0-9]+)$#', $request, $matches)):
        require_once 'app/controllers/LaboratoireController.php';
        (new LaboratoireController())->rejeterExamen((int)$matches[1]);
        break;

    case (preg_match('#^laboratoire/valider-demande/([0-9]+)$#', $request, $matches)):
        require_once 'app/controllers/LaboratoireController.php';
        (new LaboratoireController())->validerDemandeBatch((int)$matches[1]);
        break;

    case (preg_match('#^laboratoire/numero-ordre/([0-9]+)$#', $request, $matches)):
        require_once 'app/controllers/LaboratoireController.php';
        (new LaboratoireController())->numeroOrdre((int)$matches[1]);
        break;

    /* ============================================================
       SECTION ANESTHÉSIE — FICHES CHIRURGIE
       ============================================================ */
    case ($request === 'anesthesie/pre-anesthesique'):
        require_once 'app/controllers/FicheChirurgieController.php';
        (new FicheChirurgieController())->preAnesthesique();
        break;

    case (preg_match('#^anesthesie/pre-anesthesique/([0-9]+)$#', $request, $matches)):
        require_once 'app/controllers/FicheChirurgieController.php';
        (new FicheChirurgieController())->preAnesthesique((int)$matches[1]);
        break;

    case ($request === 'anesthesie/sauvegarder-pre-anesthesique'):
        require_once 'app/controllers/FicheChirurgieController.php';
        (new FicheChirurgieController())->sauvegarderPreAnesthesique();
        break;

    case ($request === 'anesthesie/per-operatoire'):
        require_once 'app/controllers/FicheChirurgieController.php';
        (new FicheChirurgieController())->perOperatoire();
        break;

    case (preg_match('#^anesthesie/per-operatoire/([0-9]+)$#', $request, $matches)):
        require_once 'app/controllers/FicheChirurgieController.php';
        (new FicheChirurgieController())->perOperatoire((int)$matches[1]);
        break;

    case ($request === 'anesthesie/sauvegarder-per-operatoire'):
        require_once 'app/controllers/FicheChirurgieController.php';
        (new FicheChirurgieController())->sauvegarderPerOperatoire();
        break;

    case ($request === 'anesthesie/surveillance-post-op'):
        require_once 'app/controllers/FicheChirurgieController.php';
        (new FicheChirurgieController())->surveillancePostOp();
        break;

    case (preg_match('#^anesthesie/surveillance-post-op/([0-9]+)$#', $request, $matches)):
        require_once 'app/controllers/FicheChirurgieController.php';
        (new FicheChirurgieController())->surveillancePostOp((int)$matches[1]);
        break;

    case ($request === 'anesthesie/sauvegarder-surveillance-post-op'):
        require_once 'app/controllers/FicheChirurgieController.php';
        (new FicheChirurgieController())->sauvegarderSurveillancePostOp();
        break;

    case ($request === 'anesthesie/ouverture-salle'):
        require_once 'app/controllers/FicheChirurgieController.php';
        (new FicheChirurgieController())->ouvertureSalleAnesthesie();
        break;

    case ($request === 'anesthesie/ouverture-salle/sauvegarder'):
        require_once 'app/controllers/FicheChirurgieController.php';
        (new FicheChirurgieController())->sauvegarderOuvertureSalleAnesthesie();
        break;

    case (preg_match('#^anesthesie/fiches-patient/([0-9]+)$#', $request, $matches)):
        require_once 'app/controllers/FicheChirurgieController.php';
        (new FicheChirurgieController())->fichesDuPatient((int)$matches[1]);
        break;

    case (preg_match('#^anesthesie/patient-json/([0-9]+)$#', $request, $matches)):
        require_once 'app/controllers/FicheChirurgieController.php';
        (new FicheChirurgieController())->patientJson((int)$matches[1]);
        break;

    /* ============================================================
       SECTION PHARMACIE - RECHERCHE DYNAMIQUE
       ============================================================ */
    case ($request == 'pharmacie/search-medicaments'):
        require_once 'app/controllers/PharmacieController.php';
        (new PharmacieController())->searchMedicaments();
        break;

    case ($request == 'pharmacie/import-stock'):
        require_once 'app/controllers/PharmacieController.php';
        (new PharmacieController())->importStock();
        break;

    case ($request == 'pharmacie/download-template'):
        require_once 'app/controllers/PharmacieController.php';
        (new PharmacieController())->downloadTemplate();
        break;

    case ($request == 'pharmacie/approvisionnement'):
        require_once 'app/controllers/PharmacieController.php';
        (new PharmacieController())->approvisionnement();
        break;

    /* ============================================================
       9. BLOC OPÉRATOIRE & CHIRURGIE
       ============================================================ */
    case ($request == 'bloc'):
        require_once 'app/controllers/BlocController.php';
        (new BlocController())->index();
        break;

    case ($request == 'bloc/transmettre-demande'):
        require_once 'app/controllers/BlocController.php';
        (new BlocController())->transmettreDemande();
        break;

    case (preg_match('/bloc\/monitoring\/(\d+)/', $request, $matches)):
        require_once 'app/controllers/BlocController.php';
        (new BlocController())->monitoring($matches[1]);
        break;

    case ($request == 'bloc/confirmer-programmation'):
        require_once 'app/controllers/BlocController.php';
        (new BlocController())->programmerIntervention();
        break;

    case ($request == 'bloc/programmer-direct'):
        require_once 'app/controllers/BlocController.php';
        (new BlocController())->programmerDirect();
        break;

    case (preg_match('#^bloc/demarrer/(\d+)$#', $request, $matches) === 1):
        require_once 'app/controllers/BlocController.php';
        (new BlocController())->demarrer((int)$matches[1]);
        break;

    case (preg_match('#^bloc/ouverture-salle/(\d+)$#', $request, $matches) === 1):
        require_once 'app/controllers/BlocController.php';
        (new BlocController())->ouvertureSalle((int)$matches[1]);
        break;

    case ($request === 'bloc/ouverture-salle/sauvegarder'):
        require_once 'app/controllers/BlocController.php';
        (new BlocController())->sauvegarderOuverture();
        break;

    case ($request === 'bloc/modifier'):
        require_once 'app/controllers/BlocController.php';
        (new BlocController())->modifierIntervention();
        break;

    case ($request === 'bloc/annuler'):
        require_once 'app/controllers/BlocController.php';
        (new BlocController())->annulerIntervention();
        break;

    case ($request == 'bloc/cro'):
        require_once 'app/controllers/BlocController.php';
        (new BlocController())->sauvegarderCRO();
        break;

    case ($request == 'bloc/sspi-dashboard' || $request == 'sspi'):
        require_once 'app/controllers/BlocController.php';
        (new BlocController())->sspiDashboard();
        break;

    case (preg_match('#^bloc/sspi/(\d+)$#', $request, $matches) === 1):
        require_once 'app/controllers/BlocController.php';
        (new BlocController())->sspi((int)$matches[1]);
        break;

    case ($request == 'bloc/sspi-sauvegarder'):
        require_once 'app/controllers/BlocController.php';
        (new BlocController())->sauvegarderSSPI();
        break;

    case ($request == 'bloc/changer-statut-salle'):
        require_once 'app/controllers/BlocController.php';
        (new BlocController())->changerStatutSalle();
        break;

    case ($request == 'admin/bloc'):
        require_once 'app/controllers/BlocController.php';
        (new BlocController())->adminBloc();
        break;

    case ($request == 'admin/bloc/sauvegarder-salle'):
        require_once 'app/controllers/BlocController.php';
        (new BlocController())->sauvegarderSalle();
        break;

    case ($request == 'admin/bloc/supprimer-salle'):
        require_once 'app/controllers/BlocController.php';
        (new BlocController())->supprimerSalle();
        break;

    /* ============================================================
       10. COMMUNICATION & FORMULAIRES & PROFIL
       ============================================================ */
    case ($request == 'telemedecine'):
        require_once 'app/controllers/TelemedecinController.php';
        (new TelemedecinController())->index();
        break;

    case ($request == 'modules/chat'):
        require_once 'app/controllers/ModulesController.php';
        (new ModulesController())->chat();
        break;

    case (preg_match('/formulaire\/creer\/([a-z0-9-]+)\/(\d+)/', $request, $matches)):
        require_once 'app/controllers/FormulaireController.php';
        (new FormulaireController())->creer($matches[1], $matches[2]);
        break;

    case ($request == 'formulaire/sauvegarder-generique'):
        require_once 'app/controllers/FormulaireController.php';
        (new FormulaireController())->sauvegarderGenerique();
        break;

    case (preg_match('#^formulaire/imprimer/([a-z0-9-]+)/(\d+)$#', $request, $matches)):
        require_once 'app/controllers/FormulaireController.php';
        (new FormulaireController())->imprimer($matches[1], (int)$matches[2]);
        break;

    case (preg_match('#^formulaire/recap-gyneco/(\d+)$#', $request, $matches)):
        require_once 'app/controllers/FormulaireController.php';
        (new FormulaireController())->recapGyneco((int)$matches[1]);
        break;

    case ($request == 'consultation-gyneco/sauvegarder-etape'):
        require_once 'app/controllers/ConsultationGynecologiqueController.php';
        (new ConsultationGynecologiqueController())->sauvegarderEtape();
        break;

    case ($request == 'consultation-gyneco/charger'):
        require_once 'app/controllers/ConsultationGynecologiqueController.php';
        (new ConsultationGynecologiqueController())->charger();
        break;

    case ($request == 'consultation-gyneco/finaliser'):
        require_once 'app/controllers/ConsultationGynecologiqueController.php';
        (new ConsultationGynecologiqueController())->finaliser();
        break;

    case (preg_match('#^pharmacie/signer-ordonnance/(\d+)$#', $request, $matches)):
        require_once 'app/controllers/ConsultationGynecologiqueController.php';
        (new ConsultationGynecologiqueController())->signerOrdonnance((int)$matches[1]);
        break;

    case ($request == 'formulaire/soumettre-medecin'):
        require_once 'app/controllers/FormulaireController.php';
        (new FormulaireController())->soumettreMedecin();
        break;

    case ($request == 'hospitalisation/formulaires-a-signer'):
        require_once 'app/controllers/FormulaireController.php';
        (new FormulaireController())->formulairesASigner();
        break;

    case ($request == 'formulaire/action-signer'):
        require_once 'app/controllers/FormulaireController.php';
        (new FormulaireController())->actionSigner();
        break;

    // Aperçu + signature électronique médecin
    case (preg_match('#^formulaire/apercu-signer/(\d+)$#', $request, $matches) === 1):
        require_once 'app/controllers/FormulaireController.php';
        (new FormulaireController())->apercuSigner((int)$matches[1]);
        break;

    // Détail d'une demande (GET — pour modal mise à jour)
    case (preg_match('#^laboratoire/detail-demande/(\d+)$#', $request, $matches) === 1):
        require_once 'app/controllers/LaboratoireController.php';
        (new LaboratoireController())->detailDemande((int)$matches[1]);
        break;

    // Mettre à jour une demande (POST)
    case (preg_match('#^laboratoire/mettre-a-jour-demande/(\d+)$#', $request, $matches) === 1):
        require_once 'app/controllers/LaboratoireController.php';
        (new LaboratoireController())->mettreAJourDemande((int)$matches[1]);
        break;

    // Supprimer une demande de bilan laboratoire (EN_ATTENTE uniquement)
    case (preg_match('#^laboratoire/supprimer-demande/(\d+)$#', $request, $matches) === 1):
        require_once 'app/controllers/LaboratoireController.php';
        (new LaboratoireController())->supprimerDemande((int)$matches[1]);
        break;

    // Supprimer un brouillon (AJAX)
    case ($request == 'formulaire/supprimer-brouillon'):
        require_once 'app/controllers/FormulaireController.php';
        (new FormulaireController())->supprimerBrouillon();
        break;

    // Upload AJAX document depuis formulaire gynéco (ou autres)
    case ($request == 'formulaire/upload-document-ajax'):
        require_once 'app/controllers/FormulaireController.php';
        (new FormulaireController())->uploadDocumentAjax();
        break;

    // Liste JSON documents d'un patient (pour onglet Documents)
    case (preg_match('#^formulaire/documents-patient/(\d+)$#', $request, $matches) === 1):
        require_once 'app/controllers/FormulaireController.php';
        (new FormulaireController())->getDocumentsPatient((int)$matches[1]);
        break;

    // CRH : formulaire depuis l'ID d'hospitalisation
    case (preg_match('/formulaire\/crh\/(\d+)/', $request, $matches)):
        require_once 'app/controllers/FormulaireController.php';
        (new FormulaireController())->crh($matches[1]);
        break;

    // CRH : sauvegarde
    case ($request == 'formulaire/sauvegarder-crh'):
        require_once 'app/controllers/FormulaireController.php';
        (new FormulaireController())->sauvegarderCRH();
        break;

    // Bulletin examens : sauvegarde
    case ($request == 'formulaire/sauvegarder/bulletin-examens'):
        require_once 'app/controllers/FormulaireController.php';
        (new FormulaireController())->sauvegarderBulletinExamens();
        break;

    // Bulletin examens : créer formulaire
    case (preg_match('/formulaire\/creer\/bulletin-examens\/(\d+)/', $request, $matches)):
        require_once 'app/controllers/FormulaireController.php';
        (new FormulaireController())->creerBulletinExamens($matches[1]);
        break;

    // CRH : consultation / impression
    case (preg_match('/formulaire\/voir-crh\/(\d+)/', $request, $matches)):
        require_once 'app/controllers/FormulaireController.php';
        (new FormulaireController())->voirCRH($matches[1]);
        break;

    // Bulletin examens : consultation / impression
    case (preg_match('/formulaire\/voir-bulletin-examens\/(\d+)/', $request, $matches)):
        require_once 'app/controllers/FormulaireController.php';
        (new FormulaireController())->voirBulletinExamens($matches[1]);
        break;

    case (preg_match('/formulaire\/signer-bulletin\/(\d+)/', $request, $matches)):
        require_once 'app/controllers/FormulaireController.php';
        (new FormulaireController())->signerBulletin((int)$matches[1]);
        break;

    case ($request == 'profil'):
        require_once 'app/controllers/UserController.php';
        (new UserController())->profil();
        break;

    case ($request == 'update-profil'):
        require_once 'app/controllers/UserController.php';
        (new UserController())->updateProfil();
        break;

    case ($request == 'profil/json'):
        require_once 'app/controllers/UserController.php';
        (new UserController())->profilJson();
        break;

    case ($request == 'profil/update-json'):
        require_once 'app/controllers/UserController.php';
        (new UserController())->updateProfilJson();
        break;

    case ($request == 'utilisateurs'):
        require_once 'app/controllers/UserController.php';
        (new UserController())->index();
        break;

    case ($request == 'utilisateurs/save'):
        require_once 'app/controllers/UserController.php';
        (new UserController())->save();
        break;

    case ($request == 'utilisateurs/delete'):
        require_once 'app/controllers/UserController.php';
        (new UserController())->delete();
        break;

    case ($request == 'utilisateurs/reactivate'):
        require_once 'app/controllers/UserController.php';
        (new UserController())->reactivate();
        break;

    case ($request == 'utilisateurs/destroy'):
        require_once 'app/controllers/UserController.php';
        (new UserController())->destroy();
        break;

    case ($request == 'utilisateurs/export'):
        require_once 'app/controllers/UserController.php';
        (new UserController())->exportUsers();
        break;

    case ($request == 'utilisateurs/import'):
        require_once 'app/controllers/UserController.php';
        (new UserController())->importUsers();
        break;

    case ($request == 'utilisateurs/modele-excel'):
        require_once 'app/controllers/UserController.php';
        (new UserController())->downloadTemplate();
        break;

    case ($request == 'utilisateurs/reset-password'):
        require_once 'app/controllers/UserController.php';
        (new UserController())->resetPassword();
        break;

    case ($request == 'utilisateurs/force-change-password'):
        require_once 'app/controllers/UserController.php';
        (new UserController())->forceChangePassword();
        break;

    case ($request == 'changer-mot-de-passe'):
        require_once 'app/controllers/AuthController.php';
        (new AuthController())->changePassword();
        break;

    case ($request == 'utilisateurs/change-service'):
        require_once 'app/controllers/UserController.php';
        (new UserController())->changeService();
        break;

    // MODULE ACCUEIL
case ($request == 'accueil'):
    require_once 'app/controllers/AccueilController.php';
    (new AccueilController())->index();
    break;

case ($request == 'accueil/search-doublon'):
    require_once 'app/controllers/AccueilController.php';
    (new AccueilController())->searchDoublon();
    break;

case ($request == 'accueil/check-doublon-final'):
    require_once 'app/controllers/AccueilController.php';
    (new AccueilController())->checkDoublonFinal();
    break;

case ($request == 'accueil/enregistrer-patient'):
    require_once 'app/controllers/AccueilController.php';
    (new AccueilController())->enregistrerPatient();
    break;

case (preg_match('/accueil\/get-patient\/(\d+)/', $request, $matches)):
    require_once 'app/controllers/AccueilController.php';
    (new AccueilController())->getPatient($matches[1]);
    break;

case ($request == 'accueil/nouvelle-visite'):
    require_once 'app/controllers/AccueilController.php';
    (new AccueilController())->nouvelleVisite();
    break;

case ($request == 'accueil/modifier-patient'):
    require_once 'app/controllers/AccueilController.php';
    (new AccueilController())->modifierPatient();
    break;

case (preg_match('/accueil\/commencer-visite\/(\d+)/', $request, $matches)):
    require_once 'app/controllers/AccueilController.php';
    (new AccueilController())->commencerVisite($matches[1]);
    break;

// MODULE ACCUEIL PHP — SUPPRIMÉ : les patients PHP passent désormais par l'accueil standard
// Redirection de sécurité pour les anciens liens éventuels
case ($request == 'accueil-php'):
case ($request == 'accueil-php/enregistrer-patient'):
case (preg_match('/accueil-php\/commencer-visite\/(\d+)/', $request, $matches)):
    header('Location: ' . BASE_URL . 'accueil');
    exit;

// MODULE PARAMÈTRES PHP
case ($request == 'parametres-php'):
    require_once 'app/controllers/ParametresPhpController.php';
    (new ParametresPhpController())->index();
    break;

case ($request == 'parametres-php/save'):
    require_once 'app/controllers/ParametresPhpController.php';
    (new ParametresPhpController())->save();
    break;

// MODULE CONSULTATION EXTERNE PHP
case ($request == 'consultation-ext-php'):
    require_once 'app/controllers/ConsultationExtPhpController.php';
    (new ConsultationExtPhpController())->index();
    break;

// DIRECTEUR — ANALYSE PATIENTS
case ($request == 'directeur/patients'):
    require_once 'app/controllers/DashboardController.php';
    (new DashboardController())->directeurPatients();
    break;

// MODULE PARAMÈTRES (Commun pour B1 et B2, la distinction se fait par la session)
case ($request == 'parametres'):
    require_once 'app/controllers/ParametresController.php';
    (new ParametresController())->index();
    break;

case ($request == 'parametres/save'):
    require_once 'app/controllers/ParametresController.php';
    (new ParametresController())->save();
    break;

// MODULE PARAMÈTRES MATERNITÉ
case ($request == 'parametres-maternite'):
    require_once 'app/controllers/ParametresMaterniteController.php';
    (new ParametresMaterniteController())->index();
    break;

case ($request == 'parametres-maternite/save'):
    require_once 'app/controllers/ParametresMaterniteController.php';
    (new ParametresMaterniteController())->save();
    break;

// AJAX — Prise en charge exclusive d'une patiente maternité (first-claim)
case ($request == 'consultation/prendre-patient'):
    header('Content-Type: application/json');
    $patient_id = (int)($_POST['patient_id'] ?? 0);
    $medecin_id = (int)($_SESSION['user_id'] ?? 0);
    if (!$patient_id || !$medecin_id) {
        echo json_encode(['success' => false, 'message' => 'Données invalides']); break;
    }
    try {
        $db = (new Database())->getConnection();
        $db->beginTransaction();
        // Verrouillage exclusif : on ne prend que si encore non assigné
        $stmtCk = $db->prepare(
            "SELECT id FROM patients
              WHERE id = ? AND statut_parcours = 'ATTENTE_CONSULTATION'
                AND (medecin_id IS NULL OR medecin_id = 0) FOR UPDATE"
        );
        $stmtCk->execute([$patient_id]);
        if (!$stmtCk->fetch()) {
            $db->rollBack();
            echo json_encode(['success' => false, 'message' => 'Cette patiente a déjà été prise en charge par un autre médecin.']);
            break;
        }
        $db->prepare("UPDATE patients SET medecin_id = ? WHERE id = ?")->execute([$medecin_id, $patient_id]);
        $db->commit();
        // Redirection vers la consultation gynéco
        echo json_encode([
            'success'  => true,
            'redirect' => BASE_URL . 'formulaire/creer/consultation-gyneco/' . $patient_id
        ]);
    } catch (\Exception $e) {
        if (isset($db)) try { $db->rollBack(); } catch(\Throwable $t) {}
        echo json_encode(['success' => false, 'message' => 'Erreur: ' . $e->getMessage()]);
    }
    break;

    /* ============================================================
       ADMINISTRATION (Services, Chambres, Lits, Permissions)
       ============================================================ */
    case ($request == 'admin/suivi-parcours'):
        require_once 'app/controllers/AdminDashboardController.php';
        (new AdminDashboardController())->suiviParcours();
        break;

    case ($request == 'admin/reorienter-patient'):
        require_once 'app/controllers/AdminDashboardController.php';
        (new AdminDashboardController())->reorienterPatient();
        break;

    case ($request == 'admin/services'):
        require_once 'app/controllers/AdminController.php';
        (new AdminController())->services();
        break;

    case ($request == 'admin/save-service'):
        require_once 'app/controllers/AdminController.php';
        (new AdminController())->saveService();
        break;

    case (preg_match('/admin\/delete-service\/(\d+)/', $request, $matches)):
        require_once 'app/controllers/AdminController.php';
        (new AdminController())->deleteService($matches[1]);
        break;

    case ($request == 'admin/gardes'):
        require_once 'app/controllers/GardeController.php';
        (new GardeController())->dashboard();
        break;

    case ($request == 'admin/gardes/creer'):
        require_once 'app/controllers/GardeController.php';
        (new GardeController())->creer();
        break;

    case ($request == 'admin/gardes/terminer'):
        require_once 'app/controllers/GardeController.php';
        (new GardeController())->terminer();
        break;

    case ($request == 'admin/gardes/annuler'):
        require_once 'app/controllers/GardeController.php';
        (new GardeController())->annuler();
        break;

    case ($request == 'admin/gardes/modifier'):
        require_once 'app/controllers/GardeController.php';
        (new GardeController())->modifier();
        break;

    case ($request == 'admin/lits'):
        require_once 'app/controllers/AdminController.php';
        (new AdminController())->lits();
        break;

    case ($request == 'admin/save-chambre'):
        require_once 'app/controllers/AdminController.php';
        (new AdminController())->saveChambre();
        break;

    case (preg_match('/admin\/delete-chambre\/(\d+)/', $request, $matches)):
        require_once 'app/controllers/AdminController.php';
        (new AdminController())->deleteChambre($matches[1]);
        break;

    case ($request == 'admin/save-lit'):
        require_once 'app/controllers/AdminController.php';
        (new AdminController())->saveLit();
        break;

    case (preg_match('/admin\/delete-lit\/(\d+)/', $request, $matches)):
        require_once 'app/controllers/AdminController.php';
        (new AdminController())->deleteLit($matches[1]);
        break;

    case ($request == 'admin/permissions'):
        require_once 'app/controllers/AdminController.php';
        (new AdminController())->permissions();
        break;

    case ($request == 'admin/save-permission'):
        require_once 'app/controllers/AdminController.php';
        (new AdminController())->savePermission();
        break;

    case ($request == 'admin/logs'):
        require_once 'app/controllers/AdminController.php';
        (new AdminController())->logs();
        break;

    /* ── Licences ──────────────────────────────────────────────────── */
    case ($request == 'admin/licences'):
        require_once 'app/controllers/LicenceController.php';
        (new LicenceController())->index();
        break;

    case ($request == 'admin/licences/creer'):
        require_once 'app/controllers/LicenceController.php';
        (new LicenceController())->creer();
        break;

    case ($request == 'admin/licences/generer-cle'):
        require_once 'app/controllers/LicenceController.php';
        (new LicenceController())->genererCle();
        break;

    case (preg_match('#^admin/licences/revoquer/(\d+)$#', $request, $matches) === 1):
        require_once 'app/controllers/LicenceController.php';
        (new LicenceController())->revoquer((int)$matches[1]);
        break;

    case (preg_match('#^admin/licences/reactiver/(\d+)$#', $request, $matches) === 1):
        require_once 'app/controllers/LicenceController.php';
        (new LicenceController())->reactiver((int)$matches[1]);
        break;

    case (preg_match('#^admin/licences/supprimer/(\d+)$#', $request, $matches) === 1):
        require_once 'app/controllers/LicenceController.php';
        (new LicenceController())->supprimer((int)$matches[1]);
        break;

    case ($request == 'admin/restaurer-sql'):
        require_once 'app/controllers/AdminDashboardController.php';
        (new AdminDashboardController())->restaurerSQL();
        break;

    case ($request == 'admin/export-sql'):
        require_once 'app/controllers/AdminDashboardController.php';
        (new AdminDashboardController())->exportSQL();
        break;

    case ($request == 'admin/backup/config'):
        require_once 'app/controllers/AdminDashboardController.php';
        (new AdminDashboardController())->getPlanificationSauvegarde();
        break;

    case ($request == 'admin/backup/planifier'):
        require_once 'app/controllers/AdminDashboardController.php';
        (new AdminDashboardController())->sauvegarderPlanification();
        break;

    case ($request == 'admin/backup/executer'):
        require_once 'app/controllers/AdminDashboardController.php';
        (new AdminDashboardController())->executerSauvegarde();
        break;

    case ($request == 'admin/backup/telecharger'):
        require_once 'app/controllers/AdminDashboardController.php';
        (new AdminDashboardController())->telechargerSauvegarde();
        break;

    case ($request == 'admin/backup/supprimer'):
        require_once 'app/controllers/AdminDashboardController.php';
        (new AdminDashboardController())->supprimerSauvegarde();
        break;

    case ($request == 'admin/dashboard/live-kpi'):
        require_once 'app/controllers/AdminDashboardController.php';
        (new AdminDashboardController())->liveKpi();
        break;

    /* ── Configuration système ───────────────────────────────── */
    case ($request == 'admin/config'):
        require_once 'app/controllers/SystemConfigController.php';
        (new SystemConfigController())->index();
        break;

    case ($request == 'admin/config/save'):
        require_once 'app/controllers/SystemConfigController.php';
        (new SystemConfigController())->save();
        break;

    case ($request == 'admin/config/save-groupe'):
        require_once 'app/controllers/SystemConfigController.php';
        (new SystemConfigController())->saveGroupe();
        break;

    /* ── Gestion administrative des patients (ADMIN) ─────────── */
    case ($request == 'admin/patients'):
        require_once 'app/controllers/PatientController.php';
        (new PatientController())->adminList();
        break;

    case (preg_match('#^admin/patients/update/(\d+)$#', $request, $matches) === 1):
        require_once 'app/controllers/PatientController.php';
        (new PatientController())->update($matches[1]);
        break;

    case (preg_match('#^admin/patients/toggle-actif/(\d+)$#', $request, $matches) === 1):
        require_once 'app/controllers/PatientController.php';
        (new PatientController())->toggleActif($matches[1]);
        break;

    case ($request === 'admin/patients/doublons'):
        require_once 'app/controllers/PatientController.php';
        (new PatientController())->getDoublons();
        break;

    case (preg_match('#^admin/patients/supprimer/(\d+)$#', $request, $matches) === 1):
        require_once 'app/controllers/PatientController.php';
        (new PatientController())->deletePatient($matches[1]);
        break;

    case (preg_match('#^admin/patients/delete/(\d+)$#', $request, $matches) === 1):
        require_once 'app/controllers/PatientController.php';
        (new PatientController())->deletePatient($matches[1]);
        break;

    case (preg_match('#^admin/patients/delete-document/(\d+)$#', $request, $matches) === 1):
        require_once 'app/controllers/PatientController.php';
        (new PatientController())->deleteDocument($matches[1]);
        break;

    case (preg_match('#^admin/patients/delete-crh/(\d+)$#', $request, $matches) === 1):
        require_once 'app/controllers/PatientController.php';
        (new PatientController())->deleteCRH($matches[1]);
        break;

    /* ============================================================
       MODULE SPÉCIALISTES
       ============================================================ */
    case ($request == 'specialiste/secretariat'):
        require_once 'app/controllers/SpecialisteController.php';
        (new SpecialisteController())->secretariat();
        break;

    case ($request == 'specialiste/store-rdv'):
        require_once 'app/controllers/SpecialisteController.php';
        (new SpecialisteController())->storeRdv();
        break;

    case (preg_match('#^specialiste/update-rdv/(\d+)$#', $request, $matches) === 1):
        require_once 'app/controllers/SpecialisteController.php';
        (new SpecialisteController())->updateRdv($matches[1]);
        break;

    case (preg_match('#^specialiste/annuler-rdv/(\d+)$#', $request, $matches) === 1):
        require_once 'app/controllers/SpecialisteController.php';
        (new SpecialisteController())->annulerRdv($matches[1]);
        break;

    case (preg_match('#^specialiste/non-honore/(\d+)$#', $request, $matches) === 1):
        require_once 'app/controllers/SpecialisteController.php';
        (new SpecialisteController())->marquerNonHonore((int)$matches[1]);
        break;

    case ($request === 'specialiste/historique'):
        require_once 'app/controllers/SpecialisteController.php';
        (new SpecialisteController())->historique();
        break;

    case (preg_match('#^specialiste/en-cours/(\d+)$#', $request, $matches) === 1):
        require_once 'app/controllers/SpecialisteController.php';
        (new SpecialisteController())->marquerEnCours($matches[1]);
        break;

    case (preg_match('#^specialiste/terminer/(\d+)$#', $request, $matches) === 1):
        require_once 'app/controllers/SpecialisteController.php';
        (new SpecialisteController())->marquerTermine($matches[1]);
        break;

    case (preg_match('#^specialiste/deplacer-rdv/(\d+)$#', $request, $matches) === 1):
        require_once 'app/controllers/SpecialisteController.php';
        (new SpecialisteController())->deplacerRdv($matches[1]);
        break;

    case (preg_match('#^specialiste/orienter/(\d+)$#', $request, $matches) === 1):
        require_once 'app/controllers/SpecialisteController.php';
        (new SpecialisteController())->orienterPatient((int)$matches[1]);
        break;

    case ($request == 'specialiste/reordonner'):
        require_once 'app/controllers/SpecialisteController.php';
        (new SpecialisteController())->reordonner();
        break;

    case ($request == 'specialiste/check-quota'):
        require_once 'app/controllers/SpecialisteController.php';
        (new SpecialisteController())->checkQuota();
        break;

    case ($request == 'specialiste/get-specialistes'):
        require_once 'app/controllers/SpecialisteController.php';
        (new SpecialisteController())->getSpecialistesJson();
        break;

    case ($request == 'specialiste/store-rdv-accueil'):
        require_once 'app/controllers/SpecialisteController.php';
        (new SpecialisteController())->storeRdvAccueil();
        break;

    case ($request == 'specialiste/accueil'):
        require_once 'app/controllers/SpecialisteController.php';
        (new SpecialisteController())->accueilView();
        break;

    case ($request == 'specialiste/search-patients'):
        require_once 'app/controllers/SpecialisteController.php';
        (new SpecialisteController())->searchPatients();
        break;

    case ($request == 'specialiste/quotas-du-jour'):
        require_once 'app/controllers/SpecialisteController.php';
        (new SpecialisteController())->quotasDuJour();
        break;

    case ($request == 'admin/pharmacie'):
        require_once 'app/controllers/AdminController.php';
        (new AdminController())->pharmacie();
        break;

    case ($request == 'admin/save-medicament'):
        require_once 'app/controllers/AdminController.php';
        (new AdminController())->saveMedicament();
        break;

    case ($request == 'admin/delete-medicament'):
        require_once 'app/controllers/AdminController.php';
        (new AdminController())->deleteMedicament();
        break;

    case ($request == 'admin/laboratoire'):
        require_once 'app/controllers/AdminController.php';
        (new AdminController())->laboratoire();
        break;

    case ($request == 'admin/imagerie'):
        require_once 'app/controllers/AdminController.php';
        (new AdminController())->imagerie();
        break;

    case ($request == 'admin/save-examen-imagerie'):
        require_once 'app/controllers/AdminController.php';
        (new AdminController())->saveExamenImagerie();
        break;

    case (preg_match('/admin\/delete-examen-imagerie\/(\d+)/', $request, $matches)):
        require_once 'app/controllers/AdminController.php';
        (new AdminController())->deleteExamenImagerie($matches[1]);
        break;

    case ($request == 'admin/save-examen'):
        require_once 'app/controllers/AdminController.php';
        (new AdminController())->saveExamen();
        break;

    case (preg_match('/admin\/delete-examen\/(\d+)/', $request, $matches)):
        require_once 'app/controllers/AdminController.php';
        (new AdminController())->deleteExamen($matches[1]);
        break;

    case ($request == 'admin/save-categorie'):
        require_once 'app/controllers/AdminController.php';
        (new AdminController())->saveCategorie();
        break;

    case (preg_match('/admin\/delete-categorie\/(\d+)/', $request, $matches)):
        require_once 'app/controllers/AdminController.php';
        (new AdminController())->deleteCategorie($matches[1]);
        break;

    case ($request == 'profil'):
        require_once 'app/controllers/UserController.php';
        (new UserController())->profil();
        break;

    /* ============================================================
       RECHERCHEZ LA SECTION CONSULTATIONS ET AJOUTEZ CECI
       ============================================================ */
    case (preg_match('/consultation\/ouvrir\/(\d+)/', $request, $matches)):
        require_once 'app/controllers/ConsultationController.php';
        (new ConsultationController())->ouvrir($matches[1]);
        break;

    // Dans le switch de index.php, section Laboratoire
case ($request == 'laboratoire/creer-demande-consultation'):
    require_once 'app/controllers/LaboratoireController.php';
    (new LaboratoireController())->creerDemandeDepuisConsultation();
    break;

// Dans index.php, section HOSPITALISATION
case ($request == 'hospitalisation/valider-installation'):
    require_once 'app/controllers/HospitalisationController.php';
    (new HospitalisationController())->validerInstallation();
    break;

// Dans index.php
case (preg_match('/hospitalisation\/executer-soins\/(\d+)/', $request, $matches)):
    require_once 'app/controllers/HospitalisationController.php';
    (new HospitalisationController())->executerSoins($matches[1]);
    break;

case ($request == 'hospitalisation/valider-execution'):
    require_once 'app/controllers/HospitalisationController.php';
    (new HospitalisationController())->validerExecution();
    break;

case ($request == 'hospitalisation/save-douleur'):
    require_once 'app/controllers/HospitalisationController.php';
    (new HospitalisationController())->sauvegarderEvaluationDouleur();
    break;

case ($request == 'hospitalisation/cocher-soin'):
    require_once 'app/controllers/HospitalisationController.php';
    (new HospitalisationController())->cocherSoin();
    break;

case ($request == 'hospitalisation/commenter-soin'):
    require_once 'app/controllers/HospitalisationController.php';
    (new HospitalisationController())->sauvegarderCommentaireSoin();
    break;

case ($request == 'hospitalisation/rayer-soin'):
    require_once 'app/controllers/HospitalisationController.php';
    (new HospitalisationController())->rayerSoin();
    break;

case ($request == 'hospitalisation/stopper-soin'):
    require_once 'app/controllers/HospitalisationController.php';
    (new HospitalisationController())->stopperSoin();
    break;


    // --- ROUTES POUR L'AGENDA MÉDICAL ---
case ($request == 'agenda'):
    require_once 'app/controllers/AgendaController.php';
    (new AgendaController())->index();
    break;

case ($request == 'agenda/events'):
    require_once 'app/controllers/AgendaController.php';
    (new AgendaController())->getEvents();
    break;

case ($request == 'agenda/save'):
    require_once 'app/controllers/AgendaController.php';
    (new AgendaController())->save();
    break;

case (preg_match('/agenda\/delete\/(\d+)/', $request, $matches)):
    require_once 'app/controllers/AgendaController.php';
    (new AgendaController())->delete($matches[1]);
    break;

// Dans index.php, cherche la section imagerie
case (preg_match('/imagerie\/delete\/(\d+)/', $request, $matches)):
    require_once 'app/controllers/ImagerieController.php';
    (new ImagerieController())->delete($matches[1]);
    break;

case ($request == 'imagerie'):
    require_once 'app/controllers/ImagerieController.php';
    (new ImagerieController())->index();
    break;

case ($request == 'imagerie'):
    require_once 'app/controllers/ImagerieController.php';
    (new ImagerieController())->index(); // Votre vue index
    break;

case ($request == 'imagerie/upload'):
    require_once 'app/controllers/ImagerieController.php';
    (new ImagerieController())->upload();
    break;

case (preg_match('/imagerie\/viewer\/(\d+)/', $request, $matches)):
    require_once 'app/controllers/ImagerieController.php';
    (new ImagerieController())->viewer($matches[1]);
    break;

case (preg_match('/imagerie\/dicom-data\/(\d+)/', $request, $matches)):
    require_once 'app/controllers/ImagerieController.php';
    (new ImagerieController())->dicomData($matches[1]);
    break;

case ($request == 'imagerie/save-interpretation'):
    require_once 'app/controllers/ImagerieController.php';
    (new ImagerieController())->saveInterpretation();
    break;

case (preg_match('/imagerie\/fetchDicom\/(\d+)/', $request, $matches)):
    require_once 'app/controllers/ImagerieController.php';
    (new ImagerieController())->fetchDicom($matches[1]);
    break;

case ($request == 'imagerie/saveThumbnail'):
    require_once 'app/controllers/ImagerieController.php';
    // Créez une méthode simple qui décode le base64 du canvas et l'enregistre en .jpg
    (new ImagerieController())->saveThumbnail();
    break;

case ($request == 'imagerie/creer-demande-consultation'):
    require_once 'app/controllers/ImagerieController.php';
    (new ImagerieController())->creerDemandeConsultation();
    break;

// Liste des fichiers d'un examen (JSON)
case (preg_match('/^imagerie\/fichiers\/(\d+)$/', $request, $matches) === 1):
    require_once 'app/controllers/ImagerieController.php';
    (new ImagerieController())->getFileList($matches[1]);
    break;

// Servir un fichier individuel par son ID dans imagerie_fichiers
case (preg_match('/^imagerie\/fichier\/(\d+)$/', $request, $matches) === 1):
    require_once 'app/controllers/ImagerieController.php';
    (new ImagerieController())->fetchDicomFile($matches[1]);
    break;

case ($request == 'bilan/save'):
    require_once 'app/controllers/BilanController.php';
    (new BilanController())->save();
    break;

case ($request == 'api/get-users'):
    require_once 'app/controllers/ApiController.php';
    (new ApiController())->getUsersByService();
    break;

    // Dans index.php, cherchez la section patients ou ajoutez ceci :
case ($request == 'patients/update-allergies'):
    require_once 'app/controllers/PatientController.php';
    (new PatientController())->updateAllergies();
    break;

case ($request == 'patients/partager-dossier'):
    require_once 'app/controllers/PatientController.php';
    (new PatientController())->partagerDossier();
    break;

case ($request == 'patients/save-avis-partage'):
    require_once 'app/controllers/PatientController.php';
    (new PatientController())->saveAvisPartage();
    break;

case (preg_match('/hospitalisation\/suivi\/(\d+)/', $request, $matches)):
    require_once 'app/controllers/HospitalisationController.php';
    (new HospitalisationController())->suivi($matches[1]);
    break;

case (preg_match('#^hospitalisation/suivi-rapide/(\d+)$#', $request, $matches) === 1):
    require_once 'app/controllers/HospitalisationController.php';
    (new HospitalisationController())->suiviRapide((int)$matches[1]);
    break;

case (preg_match('#^hospitalisation/reevaluations-rapide/(\d+)$#', $request, $matches) === 1):
    require_once 'app/controllers/HospitalisationController.php';
    (new HospitalisationController())->reevaluationsRapide((int)$matches[1]);
    break;

case ($request == 'hospitalisation/add-soin'):
    require_once 'app/controllers/HospitalisationController.php';
    (new HospitalisationController())->planifierSoin();
    break;

case (preg_match('#^hospitalisation/verifier-condition/(\d+)$#', $request, $matches) === 1):
    require_once 'app/controllers/HospitalisationController.php';
    (new HospitalisationController())->verifierConditionSoin((int)$matches[1]);
    break;

case ($request == 'hospitalisation/appliquer-action-condition'):
    require_once 'app/controllers/HospitalisationController.php';
    (new HospitalisationController())->appliquerActionCondition();
    break;

case ($request == 'hospitalisation/add-constantes'):
    require_once 'app/controllers/HospitalisationController.php';
    (new HospitalisationController())->ajouterConstantes();
    break;

case ($request == 'hospitalisation/update-constantes'):
    require_once 'app/controllers/HospitalisationController.php';
    (new HospitalisationController())->updateConstantes();
    break;

case ($request == 'hospitalisation/installer-lit-externe'):
    require_once 'app/controllers/HospitalisationController.php';
    (new HospitalisationController())->installerLitExterne();
    break;

case ($request == 'hospitalisation/get-lits-disponibles'):
    require_once 'app/controllers/HospitalisationController.php';
    (new HospitalisationController())->getLitsDisponibles();
    break;

case ($request == 'hospitalisation/prescrire-medicament'):
    require_once 'app/controllers/HospitalisationController.php';
    (new HospitalisationController())->prescrireMedicament();
    break;

    /* ============================================================
       MODULE COORDONNATEUR DES SOINS
       ============================================================ */
case ($request == 'coordo-soins'):
case ($request == 'coordo-soins/dashboard'):
    require_once 'app/controllers/CoordoSoinsController.php';
    (new CoordoSoinsController())->dashboard();
    break;

case ($request == 'coordo-soins/service-stats'):
    require_once 'app/controllers/CoordoSoinsController.php';
    (new CoordoSoinsController())->getServiceStats();
    break;

case ($request == 'coordo-soins/update-statut-rdv'):
    require_once 'app/controllers/CoordoSoinsController.php';
    (new CoordoSoinsController())->updateStatutRdv();
    break;

    /* ============================================================
       MODULE MESSAGERIE MÉDICALE (CHAT)
       ============================================================ */
    case ($request === 'chat/heartbeat'):
        require_once 'app/controllers/ChatController.php';
        (new ChatController())->heartbeat();
        break;

    case ($request === 'chat/doctors'):
        require_once 'app/controllers/ChatController.php';
        (new ChatController())->doctors();
        break;

    case ($request === 'chat/start'):
        require_once 'app/controllers/ChatController.php';
        (new ChatController())->startConv();
        break;

    case ($request === 'chat/conversations'):
        require_once 'app/controllers/ChatController.php';
        (new ChatController())->conversations();
        break;

    case ($request === 'chat/messages'):
        require_once 'app/controllers/ChatController.php';
        (new ChatController())->messages();
        break;

    case ($request === 'chat/send'):
        require_once 'app/controllers/ChatController.php';
        (new ChatController())->send();
        break;

    case ($request === 'chat/upload'):
        require_once 'app/controllers/ChatController.php';
        (new ChatController())->upload();
        break;

    case ($request === 'chat/file'):
        require_once 'app/controllers/ChatController.php';
        (new ChatController())->file();
        break;

    case ($request === 'chat/bilans'):
        require_once 'app/controllers/ChatController.php';
        (new ChatController())->bilans();
        break;

    case ($request === 'chat/bilan-statut'):
        require_once 'app/controllers/ChatController.php';
        (new ChatController())->bilanStatut();
        break;

    case ($request === 'chat/ordonnances'):
        require_once 'app/controllers/ChatController.php';
        (new ChatController())->ordonnances();
        break;

    case ($request === 'chat/imageries'):
        require_once 'app/controllers/ChatController.php';
        (new ChatController())->imageries();
        break;

    case ($request === 'chat/patients-recents'):
        require_once 'app/controllers/ChatController.php';
        (new ChatController())->patientsRecents();
        break;

    case ($request === 'chat/delete'):
        require_once 'app/controllers/ChatController.php';
        (new ChatController())->deleteMsg();
        break;

    case ($request === 'chat/edit'):
        require_once 'app/controllers/ChatController.php';
        (new ChatController())->editMsg();
        break;

    case ($request === 'chat/mark-read'):
        require_once 'app/controllers/ChatController.php';
        (new ChatController())->markRead();
        break;

    case ($request === 'chat/poll'):
        require_once 'app/controllers/ChatController.php';
        (new ChatController())->poll();
        break;

    case ($request === 'chat/call-signal'):
        require_once 'app/controllers/ChatController.php';
        (new ChatController())->callSignal();
        break;

    /* ============================================================
       DEFAUT : 404
       ============================================================ */
    default:
        http_response_code(404);
        echo '<div style="text-align:center; margin-top:50px; font-family:sans-serif;">';
        echo '<h1 style="font-size:100px; color:#ccc;">404</h1>';
        echo '<h3>Page non trouvée</h3>';
        echo '<p>L\'adresse demandée n\'existe pas : <b>' . htmlspecialchars($request) . '</b></p>';
        echo '<a href="'.BASE_URL.'">Retour au Dashboard</a>';
        echo '</div>';
        break;
}