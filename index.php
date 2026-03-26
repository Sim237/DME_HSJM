<?php
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

// Routeur Principal
switch(true) {

    /* ============================================================
       1. AUTHENTIFICATION & SESSIONS
       ============================================================ */
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

    case (preg_match('/patients\/dossier\/(\d+)/', $request, $matches)):
        require_once 'app/controllers/PatientController.php';
        (new PatientController())->dossier($matches[1]);
        break;

    case (preg_match('/patients\/mesures\/(\d+)/', $request, $matches)):
        require_once 'app/controllers/PatientController.php';
        (new PatientController())->mesures($matches[1]);
        break;

    case ($request == 'patients/save-mesures'):
        require_once 'app/controllers/PatientController.php';
        (new PatientController())->saveMesures();
        break;

    case ($request == 'patients/upload-document'):
        require_once 'app/controllers/PatientController.php';
        (new PatientController())->uploadDocument();
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

    case ($request == 'consultation/formulaire'):
        require_once 'app/controllers/ConsultationController.php';
        (new ConsultationController())->formulaire();
        break;

    case ($request == 'consultation/sauvegarder'):
        require_once 'app/controllers/ConsultationController.php';
        (new ConsultationController())->sauvegarderEtape();
        break;

    case (preg_match('/consultation\/recapitulatif\/(\d+)/', $request, $matches)):
        require_once 'app/controllers/ConsultationController.php';
        (new ConsultationController())->recapitulatif($matches[1]);
        break;

    case ($request == 'consultation/search-cim10'):
        require_once 'app/controllers/ConsultationController.php';
        (new ConsultationController())->searchCim10();
        break;

    // Dans votre fichier index.php, ajoutez ce cas :
case (preg_match('/consultation\/cloturer\/(\d+)/', $request, $matches)):
    require_once 'app/controllers/ConsultationController.php';
    (new ConsultationController())->cloturer($matches[1]);
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

    case ($request == 'lits/confirmer-admission'):
        require_once 'app/controllers/LitController.php';
        (new LitController())->confirmerAdmission();
        break;

    case ($request == 'lits/decharger'):
        require_once 'app/controllers/LitController.php';
        (new LitController())->dechargerPatient();
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

    case ($request == 'laboratoire/sauvegarder-resultats'):
        require_once 'app/controllers/LaboratoireController.php';
        (new LaboratoireController())->sauvegarderResultats();
        break;

    /* ============================================================
       SECTION PHARMACIE - RECHERCHE DYNAMIQUE
       ============================================================ */
    case ($request == 'pharmacie/search-medicaments'):
        require_once 'app/controllers/PharmacieController.php';
        (new PharmacieController())->searchMedicaments();
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

    case ($request == 'profil'):
        require_once 'app/controllers/UserController.php';
        (new UserController())->profil();
        break;

    case ($request == 'update-profil'):
        require_once 'app/controllers/UserController.php';
        (new UserController())->updateProfil();
        break;

    case ($request == 'utilisateurs'):
        require_once 'app/controllers/UserController.php';
        (new UserController())->index();
        break;

    // MODULE ACCUEIL
case ($request == 'accueil'):
    require_once 'app/controllers/AccueilController.php';
    (new AccueilController())->index();
    break;

case ($request == 'accueil/enregistrer-patient'):
    require_once 'app/controllers/AccueilController.php';
    (new AccueilController())->enregistrerPatient();
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

/* ============================================================
       PERSONNEL ET UTILISATEURS
       ============================================================ */
    case ($request == 'utilisateurs'):
        require_once 'app/controllers/UserController.php';
        (new UserController())->index();
        break;

    case ($request == 'utilisateurs/save'):
        require_once 'app/controllers/UserController.php';
        (new UserController())->save();
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

case ($request == 'bilan/save'):
    require_once 'app/controllers/BilanController.php';
    (new BilanController())->save();
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