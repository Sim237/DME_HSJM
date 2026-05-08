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

    case ($request == 'dashboard/kpi-directeur'): // API temps réel pour le dashboard Directeur
        require_once 'app/controllers/DashboardController.php';
        (new DashboardController())->kpiDirecteur();
        break;

    case ($request == 'medecin/resultats-bilan'): // API AJAX résultats bilan labo
        require_once 'app/controllers/DashboardController.php';
        (new DashboardController())->resultatsBilan();
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

    case (preg_match('/consultation\/cloturer\/(\d+)/', $request, $matches)):
        require_once 'app/controllers/ConsultationController.php';
        (new ConsultationController())->cloturer($matches[1]);
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

    // CRH : consultation / impression
    case (preg_match('/formulaire\/voir-crh\/(\d+)/', $request, $matches)):
        require_once 'app/controllers/FormulaireController.php';
        (new FormulaireController())->voirCRH($matches[1]);
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

    case ($request == 'utilisateurs/save'):
        require_once 'app/controllers/UserController.php';
        (new UserController())->save();
        break;

    case ($request == 'utilisateurs/delete'):
        require_once 'app/controllers/UserController.php';
        (new UserController())->delete();
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

case (preg_match('/accueil\/get-patient\/(\d+)/', $request, $matches)):
    require_once 'app/controllers/AccueilController.php';
    (new AccueilController())->getPatient($matches[1]);
    break;

case ($request == 'accueil/nouvelle-visite'):
    require_once 'app/controllers/AccueilController.php';
    (new AccueilController())->nouvelleVisite();
    break;

case (preg_match('/accueil\/commencer-visite\/(\d+)/', $request, $matches)):
    require_once 'app/controllers/AccueilController.php';
    (new AccueilController())->commencerVisite($matches[1]);
    break;

// MODULE SPÉCIALISTES
case ($request == 'specialiste/get-specialistes'):
    require_once 'app/controllers/SpecialisteController.php';
    (new SpecialisteController())->getSpecialistes();
    break;

case ($request == 'specialiste/check-quota'):
    require_once 'app/controllers/SpecialisteController.php';
    (new SpecialisteController())->checkQuota();
    break;

case ($request == 'specialiste/store-rdv-accueil'):
    require_once 'app/controllers/SpecialisteController.php';
    (new SpecialisteController())->storeRdvAccueil();
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

    /* ============================================================
       ADMINISTRATION (Services, Chambres, Lits, Permissions)
       ============================================================ */
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

case ($request == 'hospitalisation/cocher-soin'):
    require_once 'app/controllers/HospitalisationController.php';
    (new HospitalisationController())->cocherSoin();
    break;

case ($request == 'hospitalisation/rayer-soin'):
    require_once 'app/controllers/HospitalisationController.php';
    (new HospitalisationController())->rayerSoin();
    break;


    // --- ROUTES MAJOR (INFIRMIER MAJOR) ---
case ($request == 'major/dashboard'):
    require_once 'app/controllers/MajorController.php';
    (new MajorController())->dashboard();
    break;

case ($request == 'hospitalisation/update-patient'):
    require_once 'app/controllers/HospitalisationController.php';
    (new HospitalisationController())->updatePatient();
    break;

case ($request == 'major/marquer-retard'):
    require_once 'app/controllers/MajorController.php';
    (new MajorController())->marquerRetard();
    break;

case ($request == 'major/reassigner-soin'):
    require_once 'app/controllers/MajorController.php';
    (new MajorController())->reassignerSoin();
    break;

case ($request == 'major/annoter-soin'):
    require_once 'app/controllers/MajorController.php';
    (new MajorController())->annoterSoin();
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

case ($request == 'bilan/save'):
    require_once 'app/controllers/BilanController.php';
    (new BilanController())->save();
    break;

case ($request == 'api/get-users'):
    require_once 'app/controllers/ApiController.php';
    (new ApiController())->getUsersByService();
    break;

    // Dans index.php, cherchez la section patients ou ajoutez ceci :
case ($request == 'patients/partager-dossier'):
    require_once 'app/controllers/PatientController.php';
    (new PatientController())->partagerDossier();
    break;

case (preg_match('/hospitalisation\/suivi\/(\d+)/', $request, $matches)):
    require_once 'app/controllers/HospitalisationController.php';
    (new HospitalisationController())->suivi($matches[1]);
    break;

case ($request == 'hospitalisation/add-soin'):
    require_once 'app/controllers/HospitalisationController.php';
    (new HospitalisationController())->planifierSoin();
    break;

case ($request == 'hospitalisation/add-constantes'):
    require_once 'app/controllers/HospitalisationController.php';
    (new HospitalisationController())->ajouterConstantes();
    break;

case ($request == 'hospitalisation/prescrire-medicament'):
    require_once 'app/controllers/HospitalisationController.php';
    (new HospitalisationController())->prescrireMedicament();
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