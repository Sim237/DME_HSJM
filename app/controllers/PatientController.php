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

        // Restriction par service (sauf Admin / Super Admin)
        $isAdmin    = in_array(strtoupper($_SESSION['user_role'] ?? ''), ['ADMIN', 'SUPER_ADMIN'], true);
        $service_id = $isAdmin ? null : ($_SESSION['service_id'] ?? null);

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
        $role = strtoupper($_SESSION['user_role'] ?? '');
        if ($role === 'INFIRMIER') {
            if (!$this->auth->isLoggedIn()) { header('Location: ' . BASE_URL . 'login'); exit; }
        } else {
            $this->auth->requirePermission('patients', 'write');
        }

        $data = [
            'nom' => '', 'prenom' => '', 'date_naissance' => '', 'sexe' => '',
            'telephone' => '', 'email' => '', 'adresse' => '', 'groupe_sanguin' => '',
            'contact_nom' => '', 'contact_telephone' => '', 'antecedents_medicaux' => '', 'allergies' => ''
        ];

        include __DIR__ . '/../views/patients/nouveau.php';
    }

    /**
     * AJAX — Vérification intelligente des doublons (endpoint centralisé)
     * GET/POST patients/verifier-doublon?nom=&prenom=&date_naissance=&telephone=&exclude_id=
     */
    public function verifierDoublon(): void
    {
        header('Content-Type: application/json');
        require_once __DIR__ . '/../services/DoublonPatientService.php';

        $nom     = trim($_REQUEST['nom']            ?? '');
        $prenom  = trim($_REQUEST['prenom']         ?? '');
        $ddn     = trim($_REQUEST['date_naissance'] ?? '') ?: null;
        $tel     = trim($_REQUEST['telephone']      ?? '') ?: null;
        $exclId  = !empty($_REQUEST['exclude_id']) ? (int)$_REQUEST['exclude_id'] : null;

        if (strlen($nom) < 2) {
            echo json_encode(['doublons' => [], 'score_max' => 0,
                              'has_critical' => false, 'has_warning' => false]);
            exit;
        }

        $svc    = new DoublonPatientService($this->db ?? (new Database())->getConnection());
        $result = $svc->verifier($nom, $prenom, $ddn, $tel, $exclId);
        echo json_encode(DoublonPatientService::formaterPourApi($result));
        exit;
    }

    /**
     * Enregistrement du patient + Audit + Registres
     */
    public function store() {
        $role = strtoupper($_SESSION['user_role'] ?? '');
        if ($role === 'INFIRMIER') {
            if (!$this->auth->isLoggedIn()) { header('Location: ' . BASE_URL . 'login'); exit; }
        } else {
            $this->auth->requirePermission('patients', 'write');
        }

        if($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Date de naissance inconnue → calculée depuis l'âge estimé
            if (empty($_POST['date_naissance']) && !empty($_POST['age_estimatif'])) {
                $_POST['date_naissance'] = (date('Y') - (int)$_POST['age_estimatif']) . '-01-01';
            }

            $data = [
                'nom' => strtoupper(htmlspecialchars($_POST['nom'])),
                'prenom' => htmlspecialchars($_POST['prenom']),
                'date_naissance' => $_POST['date_naissance'],
                'sexe' => $_POST['sexe'],
                'telephone' => $_POST['telephone'] ?? null,
                'email' => $_POST['email'] ?? null,
                'adresse' => $_POST['adresse'] ?? null,
                'groupe_sanguin' => ($_POST['groupe_sanguin'] ?: null),
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

            // ── Vérification intelligente des doublons ─────────────────────
            // Ignorée pour le contexte urgences (prise en charge rapide, pas de temps pour dialog)
            if (empty($_POST['confirmer_doublon']) && ($_POST['context'] ?? '') !== 'urgences') {
                require_once __DIR__ . '/../services/DoublonPatientService.php';
                $svc    = new DoublonPatientService($this->db);
                $result = $svc->verifier($data['nom'], $data['prenom'],
                                         $data['date_naissance'], $data['telephone']);
                if ($result['has_warning']) {
                    $doublons_trouves = DoublonPatientService::formaterPourApi($result)['doublons'];
                    $doublon_score_max = $result['score_max'];
                    include __DIR__ . '/../views/patients/nouveau.php';
                    return;
                }
            }

            $patient_id = $this->patientModel->create($data);

            if($patient_id) {
                $this->audit->logAction('CREATE', 'patients', $patient_id, null, "Création du dossier patient");
                if (isset($_POST['pathologies'])) {
                    $this->enregistrerPathologies($patient_id, $_POST);
                }

                // ── Si c'est un médecin/praticien qui crée, placer directement
                //    dans sa file d'attente (statut_parcours + medecin_id + service_id)
                $rolesPraticiens = ['MEDECIN','CHIRURGIEN','GENERALISTE',
                                    'MEDECIN_URGENCES','MEDECIN_CHEF','PEDIATRE',
                                    'GYNECOLOGUE','DERMATOLOGUE','OPHTALMOLOGUE',
                                    'CARDIOLOGUE','RADIOLOGUE','NEUROLOGUE'];
                if (in_array($role, $rolesPraticiens)) {
                    $createur_id = (int)($_SESSION['user_id']      ?? 0);
                    $service_id  = (int)($_SESSION['service_id']   ?? 0) ?: null;
                    try {
                        $this->db->prepare("
                            UPDATE patients
                            SET statut_parcours = 'ATTENTE_CONSULTATION',
                                medecin_id      = ?,
                                service_id      = ?,
                                actif           = 1
                            WHERE id = ?
                        ")->execute([$createur_id, $service_id, $patient_id]);
                    } catch (\Throwable $e) {
                        error_log('[store] assignation file attente : ' . $e->getMessage());
                    }
                    // Rediriger vers le dashboard du médecin pour voir la file
                    header('Location: ' . BASE_URL . 'dashboard?success=patient_cree');
                    exit();
                }

                // Contexte urgences : aller directement à la prise de paramètres
                if (($_POST['context'] ?? '') === 'urgences') {
                    header('Location: ' . BASE_URL . 'patients/mesures/' . $patient_id . '?context=urgences');
                } else {
                    header('Location: ' . BASE_URL . 'patients?success=1');
                }
                exit();
            }
        }
    }

    /**
     * Affiche le formulaire de prise de constantes (MÉTHODE CORRIGÉE)
     */
    public function mesures($id) {
        // INFIRMIER doit pouvoir prendre les constantes → permission read suffit
        $role = strtoupper($_SESSION['user_role'] ?? '');
        if ($role === 'INFIRMIER') {
            if (!$this->auth->isLoggedIn()) {
                header('Location: ' . BASE_URL . 'login'); exit();
            }
        } else {
            $this->auth->requirePermission('patients', 'write');
        }

        $patient = $this->patientModel->getById($id);

        if(!$patient) {
            header('Location: ' . BASE_URL . 'patients?error=not_found');
            exit();
        }

        // Vérification de sécurité service (sauf admin et infirmier urgences qui gère tous les services)
        if (!in_array($role, ['ADMIN', 'ADMINISTRATEUR', 'INFIRMIER']) && $patient['service_id'] != $_SESSION['service_id']) {
            die("Accès refusé : Ce patient appartient à un autre service.");
        }

        // RDV spécialiste du jour pour ce patient
        $rdvAujourdhui = null;
        try {
            $stmtRdv = $this->db->prepare("
                SELECT r.*, s.specialite, s.nom AS spec_nom, s.prenom AS spec_prenom,
                       s.quota_max
                FROM rdv_specialistes r
                JOIN specialistes s ON s.id = r.specialiste_id
                WHERE r.patient_id = ?
                  AND r.date_rdv = CURDATE()
                  AND r.statut NOT IN ('ANNULE','REPORTE','TERMINE')
                ORDER BY r.id DESC
                LIMIT 1
            ");
            $stmtRdv->execute([$id]);
            $rdvAujourdhui = $stmtRdv->fetch(PDO::FETCH_ASSOC) ?: null;

            // Compte quota actuel si RDV trouvé
            if ($rdvAujourdhui) {
                $stmtQ = $this->db->prepare("
                    SELECT COUNT(*) FROM rdv_specialistes
                    WHERE specialiste_id = ? AND date_rdv = CURDATE()
                      AND statut NOT IN ('ANNULE','REPORTE')
                ");
                $stmtQ->execute([$rdvAujourdhui['specialiste_id']]);
                $rdvAujourdhui['quota_actuel'] = (int)$stmtQ->fetchColumn();
            }
        } catch (Exception $e) {
            // Table rdv_specialistes pas encore créée ou autre erreur
            $rdvAujourdhui = null;
        }

        $context = $_GET['context'] ?? '';

        require_once __DIR__ . '/../views/patients/mesures.php';
    }

    /**
     * AJAX — Enregistre les constantes depuis le modal du dossier patient
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

        $temp   = isset($_POST['temperature'])        && $_POST['temperature'] !== ''        ? (float)$_POST['temperature']        : null;
        $sys    = isset($_POST['tension_sys'])        && $_POST['tension_sys'] !== ''        ? (int)$_POST['tension_sys']           : null;
        $dia    = isset($_POST['tension_dia'])        && $_POST['tension_dia'] !== ''        ? (int)$_POST['tension_dia']           : null;
        $pouls  = isset($_POST['frequence_cardiaque']) && $_POST['frequence_cardiaque'] !== '' ? (int)$_POST['frequence_cardiaque'] : null;
        $spo2   = isset($_POST['spo2'])               && $_POST['spo2'] !== ''               ? (int)$_POST['spo2']                 : null;
        $poids  = isset($_POST['poids'])              && $_POST['poids'] !== ''              ? (float)$_POST['poids']              : null;
        $taille = isset($_POST['taille'])             && $_POST['taille'] !== ''             ? (int)$_POST['taille']               : null;

        try {
            $db = (new Database())->getConnection();
            $db->prepare("
                INSERT INTO patient_parametres (
                    patient_id, user_id,
                    temperature,
                    pression_arterielle_systolique, pression_arterielle_diastolique,
                    frequence_cardiaque, poids, taille, saturation_oxygene,
                    date_mesure
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ")->execute([
                $patient_id,
                $_SESSION['user_id'] ?? 0,
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
        $db         = (new Database())->getConnection();
        $patient_id = (int)($_POST['patient_id'] ?? 0);
        $context    = $_POST['context'] ?? '';

        if (!$patient_id) {
            header('Location: ' . BASE_URL . 'patients?error=patient_manquant');
            exit();
        }

        try {
            $db->beginTransaction();

            // ── 1. Enregistrement des constantes vitales ──────────────────────
            $temp    = isset($_POST['temperature'])        && $_POST['temperature']        !== '' ? (float)$_POST['temperature']        : null;
            $sys     = isset($_POST['tension_sys'])        && $_POST['tension_sys']        !== '' ? (int)$_POST['tension_sys']           : null;
            $dia     = isset($_POST['tension_dia'])        && $_POST['tension_dia']        !== '' ? (int)$_POST['tension_dia']           : null;
            $pouls   = isset($_POST['frequence_cardiaque']) && $_POST['frequence_cardiaque'] !== '' ? (int)$_POST['frequence_cardiaque'] : null;
            $spo2    = isset($_POST['spo2'])               && $_POST['spo2']               !== '' ? (int)$_POST['spo2']                 : null;
            $poids   = isset($_POST['poids'])              && $_POST['poids']              !== '' ? (float)$_POST['poids']              : null;
            $taille  = isset($_POST['taille'])             && $_POST['taille']             !== '' ? (int)$_POST['taille']               : null;

            $db->prepare("
                INSERT INTO patient_parametres
                    (patient_id, user_id,
                     temperature,
                     pression_arterielle_systolique, pression_arterielle_diastolique,
                     frequence_cardiaque, poids, taille, saturation_oxygene,
                     date_mesure)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ")->execute([
                $patient_id,
                $_SESSION['user_id'] ?? 0,
                $temp, $sys, $dia, $pouls, $poids, $taille, $spo2
            ]);

            // ── 2. Contexte URGENCES : mise en file commune des médecins urgences ──
            if ($context === 'urgences') {

                // Niveau de triage par défaut P3-STABLE si non précisé
                $niveau  = in_array((int)($_POST['niveau_triage'] ?? 3), [1,2,3,4,5])
                           ? (int)$_POST['niveau_triage'] : 3;
                $couleur = ['1'=>'ROUGE','2'=>'ORANGE','3'=>'JAUNE','4'=>'VERT','5'=>'BLEU'][$niveau] ?? 'JAUNE';
                $motif   = trim($_POST['motif_admission'] ?? 'Enregistrement infirmier urgences');

                // Vérifier s'il n'y a pas déjà une admission active pour ce patient
                $stmtCheck = $db->prepare(
                    "SELECT id FROM urgences_patients
                      WHERE patient_id = ? AND statut IN ('ATTENTE','EN_COURS')
                      LIMIT 1"
                );
                $stmtCheck->execute([$patient_id]);
                $existante = $stmtCheck->fetchColumn();

                if (!$existante) {
                    // Créer l'entrée en file commune (medecin_id = NULL → visible par tous les médecins urgences)
                    $db->prepare(
                        "INSERT INTO urgences_patients
                            (patient_id, medecin_id, motif_admission, niveau_triage, couleur_triage, statut, heure_arrivee)
                         VALUES (?, NULL, ?, ?, ?, 'ATTENTE', NOW())"
                    )->execute([$patient_id, $motif, $niveau, $couleur]);
                }

                // Mettre le patient en file d'attente urgences
                // Résoudre l'ID du service urgences dynamiquement
                $urgServiceId = 3; // fallback (UrgencesController::URGENCES_SERVICE_ID)
                $stmtUrgSrv = $db->query("SELECT id FROM services WHERE nom_service LIKE '%urgence%' LIMIT 1");
                if ($stmtUrgSrv) {
                    $found = $stmtUrgSrv->fetchColumn();
                    if ($found) $urgServiceId = (int)$found;
                }

                // statut_parcours = ATTENTE_CONSULTATION :
                // — visibilité dans la salle d'attente du médecin urgences
                // — visibilité dans la file consultation de l'infirmier urgences
                $db->prepare(
                    "UPDATE patients
                        SET statut_parcours = 'ATTENTE_CONSULTATION',
                            service_id      = ?,
                            medecin_id      = NULL
                      WHERE id = ?"
                )->execute([$urgServiceId, $patient_id]);

                $db->commit();

                $redirect_after = $_POST['redirect_after'] ?? 'salle_attente';
                if ($redirect_after === 'consultation_directe') {
                    header('Location: ' . BASE_URL . 'infirmier/consultation/' . $patient_id);
                } else {
                    header('Location: ' . BASE_URL . 'urgences?success=patient_cree');
                }

            } else {
                // ── 3. Contexte standard (hors urgences) ──────────────────────
                $medecin_id = !empty($_POST['medecin_id']) ? (int)$_POST['medecin_id'] : null;
                $service_id = !empty($_POST['service_id']) ? (int)$_POST['service_id'] : ($_SESSION['service_id'] ?? null);
                $is_urgence = (($_POST['priorite'] ?? '') === 'CRITIQUE' || ($_POST['type_admission'] ?? '') === 'URGENCE');

                if (!$is_urgence) {
                    $stmt = $db->prepare("SELECT COUNT(*) FROM consultations
                                          WHERE medecin_id = ?
                                          AND DATE(date_consultation) = CURDATE()
                                          AND type_admission = 'CONSULTATION'");
                    $stmt->execute([$medecin_id]);
                    if ($stmt->fetchColumn() >= 20) {
                        $db->rollBack();
                        header('Location: ' . BASE_URL . 'patients/mesures/' . $patient_id . '?error=quota_atteint');
                        exit();
                    }
                }

                $db->prepare("UPDATE patients SET statut_parcours = ?, medecin_id = ?, service_id = ? WHERE id = ?")
                   ->execute([
                       $is_urgence ? 'URGENCES' : 'ATTENTE_CONSULTATION',
                       $medecin_id,
                       $service_id,
                       $patient_id
                   ]);

                $db->commit();
                header('Location: ' . BASE_URL . 'patients?success=transmis');
            }

        } catch (Exception $e) {
            $db->rollBack();
            error_log("saveMesures erreur: " . $e->getMessage());
            header('Location: ' . BASE_URL . 'patients/mesures/' . $patient_id . '?context=' . urlencode($context) . '&error=db');
        }
        exit();
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

        // 2. Sécurité : cloisonnement par service — les médecins, urgentistes et
        //    infirmiers consultants peuvent accéder aux patients en cours de
        //    consultation ou d'urgence chez eux.
        $roleAdmin = in_array(strtoupper($_SESSION['user_role'] ?? ''), ['ADMIN', 'SUPER_ADMIN'], true);
        if (!$roleAdmin) {
            $canAccess = ($patient['service_id'] == $_SESSION['service_id']);

            // Praticiens ayant une consultation active ou récente pour ce patient
            // (médecins, urgentistes, infirmier(es) consultant(es), généralistes…)
            $rolesPraticien = [
                'MEDECIN', 'MEDECIN_URGENCES', 'INFIRMIER_CONSULTANT',
                'GENERALISTE', 'CHIRURGIEN', 'ANESTHESISTE', 'RADIOLOGUE'
            ];
            if (!$canAccess && in_array($_SESSION['user_role'], $rolesPraticien, true)) {
                $stmtConsult = $this->db->prepare(
                    "SELECT COUNT(*) FROM consultations WHERE patient_id = ? AND medecin_id = ?"
                );
                $stmtConsult->execute([$id, $_SESSION['user_id']]);
                $canAccess = ($stmtConsult->fetchColumn() > 0);

                // Bonus : autoriser aussi l'INFIRMIER_CONSULTANT à voir les patients
                // qu'il a en file d'attente (statut_parcours = 'ATTENTE_CONSULTATION')
                // de son service, même sans consultation déjà créée
                if (!$canAccess && $_SESSION['user_role'] === 'INFIRMIER_CONSULTANT') {
                    $canAccess = in_array($patient['statut_parcours'] ?? '',
                                          ['ATTENTE_CONSULTATION', 'EN_CONSULTATION'], true);
                }
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

            // Infirmier urgences : accès à tout patient ayant un passage aux urgences (actif ou récent)
            if (!$canAccess && $_SESSION['user_role'] === 'INFIRMIER') {
                $stmtUrg2 = $this->db->prepare(
                    "SELECT COUNT(*) FROM urgences_patients WHERE patient_id = ? AND statut IN ('ATTENTE','EN_COURS','HOSPITALISE')"
                );
                $stmtUrg2->execute([$id]);
                $canAccess = ($stmtUrg2->fetchColumn() > 0);
            }

            // Patient en SSPI ou au bloc : infirmiers anesthésie et anesthésistes ont accès complet
            // (le passage en bloc ne crée pas de consultation, donc la vérification classique échoue)
            if (!$canAccess && in_array($_SESSION['user_role'], [
                    'ANESTHESISTE', 'INFIRMIER_ANESTHESISTE',
                    'INFIRMIER', 'MAJOR_INFIRMIER', 'INFIRMIER_CONSULTANT'
                ], true)) {
                $stmtSspi = $this->db->prepare(
                    "SELECT COUNT(*) FROM bloc_programmation bp
                     JOIN bloc_demandes bd ON bd.id = bp.demande_id
                     WHERE bd.patient_id = ? AND bp.statut IN ('EN_COURS','EN_SSPI')"
                );
                $stmtSspi->execute([$id]);
                $canAccess = ($stmtSspi->fetchColumn() > 0);
            }

            // Anesthésiste : accès à tout patient qu'il a déjà pris en charge au bloc
            if (!$canAccess && $_SESSION['user_role'] === 'ANESTHESISTE') {
                $stmtBloc = $this->db->prepare(
                    "SELECT COUNT(*) FROM bloc_demandes
                     WHERE patient_id = ? AND anesthesiste_id = ?"
                );
                $stmtBloc->execute([$id, $_SESSION['user_id']]);
                $canAccess = ($stmtBloc->fetchColumn() > 0);
            }

            // Dossier partagé : un médecin destinataire d'un partage actif peut consulter
            if (!$canAccess) {
                $stmtPartage = $this->db->prepare(
                    "SELECT COUNT(*) FROM partages_dossiers
                     WHERE patient_id = ? AND destinataire_id = ? AND date_expiration > NOW()"
                );
                $stmtPartage->execute([$id, $_SESSION['user_id']]);
                $canAccess = ($stmtPartage->fetchColumn() > 0);
            }

            if (!$canAccess) {
                $this->audit->logAction('READ', 'patients', $id, null, "ACCÈS REFUSÉ : hors service");
                http_response_code(403);

                // Service de rattachement actuel du dossier (pour orienter l'utilisateur)
                $servicePatientNom = null;
                try {
                    if (!empty($patient['service_id'])) {
                        $stmtSvcPat = $this->db->prepare("SELECT nom_service FROM services WHERE id = ?");
                        $stmtSvcPat->execute([(int)$patient['service_id']]);
                        $servicePatientNom = $stmtSvcPat->fetchColumn() ?: null;
                    }
                } catch (\Throwable $eSvc) {}

                $error_cause = "Ce dossier patient n'est pas rattaché à votre service"
                    . ($servicePatientNom ? " : il appartient actuellement au service « " . $servicePatientNom . " »." : ".")
                    . " Vous ne pouvez consulter que les dossiers des patients pris en charge dans votre service, "
                    . "qui vous sont assignés, ou qui ont été partagés avec vous.";
                $error_details = [
                    'Dossier demandé'        => ($patient['dossier_numero'] ?? ('Patient #' . $id)),
                    'Service du dossier'     => $servicePatientNom,
                ];
                require __DIR__ . '/../views/errors/403.php';
                exit;
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

        // 4. Historique des consultations — paginé (20 par page)
        $consultPage   = max(1, (int)($_GET['consult_page'] ?? 1));
        $consultLimit  = 20;
        $consultOffset = ($consultPage - 1) * $consultLimit;

        $stmtCTotal = $this->db->prepare(
            "SELECT COUNT(*) FROM consultations WHERE patient_id = ?"
        );
        $stmtCTotal->execute([$id]);
        $totalConsultations = (int)$stmtCTotal->fetchColumn();
        $totalConsultPages  = max(1, (int)ceil($totalConsultations / $consultLimit));

        // Total global incluant les consultations pédiatriques (pour le compteur d'en-tête)
        $totalConsultationsPed = 0;
        try {
            $stmtPedC = $this->db->prepare("SELECT COUNT(*) FROM consultations_pediatriques WHERE patient_id = ?");
            $stmtPedC->execute([$id]);
            $totalConsultationsPed = (int)$stmtPedC->fetchColumn();
        } catch (\Throwable $e) { $totalConsultationsPed = 0; }
        $nbConsultationsGlobal = $totalConsultations + $totalConsultationsPed;

        $stmtC = $this->db->prepare(
            "SELECT c.*, u.nom AS medecin_nom, u.prenom AS medecin_prenom
             FROM consultations c
             LEFT JOIN users u ON c.medecin_id = u.id
             WHERE c.patient_id = ?
             ORDER BY c.date_consultation DESC
             LIMIT $consultLimit OFFSET $consultOffset"
        );
        $stmtC->execute([$id]);
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
    SELECT sh.id,
           sh.description  AS soin_description,
           sh.type_soin    AS categorie,
           COALESCE(sh.date_realisee, sh.date_prevue) AS date_execution,
           sh.statut, sh.note_execution,
           u.nom    AS infirmier_nom,
           u.prenom AS infirmier_prenom
    FROM soins_hospitalisation sh
    LEFT JOIN users u ON sh.user_id_executant = u.id
    WHERE sh.admission_id IN (SELECT id FROM hospitalisations WHERE patient_id = ?)
    AND sh.statut IN ('REALISE', 'realise')
    ORDER BY COALESCE(sh.date_realisee, sh.date_prevue) DESC
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

            // Nouveau système (ordonnances_pharmacie) — inclut les ordonnances par ordre (consultation_id NULL)
            $stmtOP = $this->db->prepare("
                SELECT CONCAT('op_', op.id) AS prescription_id,
                       op.date_creation     AS date_prescription,
                       op.statut            AS statut_prescription,
                       op.type_ordonnance,
                       NULL                 AS numero_ordonnance,
                       om.posologie, om.duree,
                       NULL AS frequence, om.quantite, NULL AS voie,
                       COALESCE(om.nom_medicament, m.nom, 'Médicament') AS medicament_nom,
                       COALESCE(m.forme, '')  AS forme,
                       COALESCE(m.dosage, '') AS dosage,
                       u_med.nom    AS medecin_nom,
                       u_med.prenom AS medecin_prenom,
                       u_inf.nom    AS infirmier_nom,
                       u_inf.prenom AS infirmier_prenom
                FROM ordonnances_pharmacie op
                JOIN ordonnance_medicaments om ON om.ordonnance_id = op.id
                LEFT JOIN medicaments m          ON m.id  = om.medicament_id
                LEFT JOIN users u_med            ON u_med.id = op.medecin_id
                LEFT JOIN users u_inf            ON u_inf.id = op.infirmier_prescripteur_id
                WHERE op.patient_id = ?
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
                       di.fichier_preview, di.fichier_dicom, di.type_examen, di.partie_corps,
                       di.avec_contraste, di.description
                FROM demandes_imagerie di
                LEFT JOIN users u ON di.medecin_id = u.id
                WHERE di.patient_id = ?
                ORDER BY di.date_creation DESC
            ");
            $stmtIM->execute([$id]);
            $bilans_imagerie = $stmtIM->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) { error_log("bilans_imagerie dossier: " . $e->getMessage()); }

        // 9c. Hospitalisation en cours / dernière + historique complet
        $hospitalisation      = null;
        $historique_hospit    = [];
        try {
            $stmtHosp = $this->db->prepare("
                SELECT h.*, s.nom_service, ch.nom_chambre, l.nom_lit,
                       u.nom AS medecin_resp_nom, u.prenom AS medecin_resp_prenom
                FROM hospitalisations h
                LEFT JOIN services s  ON h.service_id = s.id
                LEFT JOIN lits l      ON h.lit_id = l.id
                LEFT JOIN chambres ch ON l.chambre_id = ch.id
                LEFT JOIN users u     ON h.medecin_responsable = u.id
                WHERE h.patient_id = ?
                ORDER BY h.date_admission DESC
            ");
            $stmtHosp->execute([$id]);
            $allHosp = $stmtHosp->fetchAll(PDO::FETCH_ASSOC);
            if (!empty($allHosp)) {
                $hospitalisation   = $allHosp[0]; // la plus récente
                $historique_hospit = $allHosp;    // tout l'historique
            }
        } catch (\Throwable $e) { error_log("hospit dossier: ".$e->getMessage()); }

        // 10. Partage actif pour ce patient (si le visiteur est destinataire)
        $partage_info = null;
        try {
            $stmtPI = $this->db->prepare("
                SELECT pd.id, pd.avis_medecin, pd.date_partage, pd.date_expiration, pd.statut,
                       u.nom as expediteur_nom, u.prenom as expediteur_prenom,
                       u.specialite as expediteur_specialite
                FROM partages_dossiers pd
                JOIN users u ON pd.expediteur_id = u.id
                WHERE pd.patient_id = ? AND pd.destinataire_id = ? AND pd.date_expiration > NOW()
                ORDER BY pd.date_partage DESC
                LIMIT 1
            ");
            $stmtPI->execute([$id, $_SESSION['user_id']]);
            $partage_info = $stmtPI->fetch(PDO::FETCH_ASSOC) ?: null;

            // Marquer comme vu si c'est la première visite
            if ($partage_info && $partage_info['statut'] === 'en_attente') {
                $this->db->prepare("UPDATE partages_dossiers SET statut = 'vu' WHERE id = ?")
                         ->execute([$partage_info['id']]);
            }
        } catch (PDOException $e) { error_log("partage_info dossier: " . $e->getMessage()); }

        // 11. Chargement de la vue avec toutes les variables préparées
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
     * Upload de documents (sécurisé : validation extension + MIME réel)
     */
    public function uploadDocument() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_FILES['document'])) return;

        $patient_id  = (int)($_POST['patient_id'] ?? 0);
        $categorie   = htmlspecialchars($_POST['categorie']   ?? 'Autre', ENT_QUOTES, 'UTF-8');
        $description = htmlspecialchars($_POST['description'] ?? '',       ENT_QUOTES, 'UTF-8');

        if (!$patient_id) {
            header("Location: " . BASE_URL . "patients?error=invalid_patient");
            exit;
        }

        $file = $_FILES['document'];

        // 1. Vérification erreur upload PHP
        if ($file['error'] !== UPLOAD_ERR_OK) {
            header("Location: " . BASE_URL . "patients/dossier/$patient_id?error=upload_error");
            exit;
        }

        // 2. Whitelist extensions autorisées
        $allowedExt = ['pdf','jpg','jpeg','png','gif','bmp','webp','doc','docx','xls','xlsx','csv','txt'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowedExt, true)) {
            header("Location: " . BASE_URL . "patients/dossier/$patient_id?error=ext_non_autorisee");
            exit;
        }

        // 3. Vérification MIME réel via finfo (pas $_FILES['type'] spoofable)
        $allowedMime = [
            'application/pdf','image/jpeg','image/png','image/gif','image/bmp','image/webp',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'text/plain','text/csv',
        ];
        $finfo    = new finfo(FILEINFO_MIME_TYPE);
        $realMime = $finfo->file($file['tmp_name']);
        if (!in_array($realMime, $allowedMime, true)) {
            header("Location: " . BASE_URL . "patients/dossier/$patient_id?error=mime_non_autorise");
            exit;
        }

        // 4. Nom de fichier aléatoire (UUID-like) pour éviter la traversée de chemin
        $filename   = 'DOC_' . $patient_id . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
        $upload_dir = __DIR__ . '/../../public/uploads/documents/';

        if (!is_dir($upload_dir)) mkdir($upload_dir, 0750, true);

        // 5. Déplacement sécurisé
        if (move_uploaded_file($file['tmp_name'], $upload_dir . $filename)) {
            $sql = "INSERT INTO patient_documents
                        (patient_id, nom_fichier, chemin_fichier, type_mime, categorie, description, date_upload)
                    VALUES (?, ?, ?, ?, ?, ?, NOW())";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$patient_id, basename($file['name']), $filename, $realMime, $categorie, $description]);

            $this->audit->logAction('ADD', 'documents', $patient_id, null, "Upload document : $categorie");
            header("Location: " . BASE_URL . "patients/dossier/$patient_id?success=upload");
        } else {
            header("Location: " . BASE_URL . "patients/dossier/$patient_id?error=upload_failed");
        }
        exit;
    }

    /* =========================================================
       SUPPRESSION (ADMIN UNIQUEMENT)
       ========================================================= */

    /**
     * Supprime un document uploadé (patient_documents) + fichier physique
     */
    public function deleteDocument($id) {
        header('Content-Type: application/json');
        if (!$this->auth->isAdmin() && !in_array(strtoupper($_SESSION['user_role'] ?? ''), ['ADMIN', 'SUPER_ADMIN'], true)) {
            echo json_encode(['success' => false, 'message' => 'Accès réservé à l\'administrateur.']); exit;
        }

        $id   = (int)$id;
        $stmt = $this->db->prepare("SELECT * FROM patient_documents WHERE id = ?");
        $stmt->execute([$id]);
        $doc  = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$doc) {
            echo json_encode(['success' => false, 'message' => 'Document introuvable.']); exit;
        }

        // Supprimer le fichier physique
        $filePath = __DIR__ . '/../../public/uploads/documents/' . $doc['chemin_fichier'];
        if (file_exists($filePath)) @unlink($filePath);

        // Supprimer l'entrée en base
        $this->db->prepare("DELETE FROM patient_documents WHERE id = ?")->execute([$id]);
        $this->audit->logAction('DELETE', 'documents', $id, null, "Suppression document : " . $doc['nom_fichier']);

        echo json_encode(['success' => true, 'message' => 'Document supprimé.']);
        exit;
    }

    /**
     * Supprime un compte-rendu d'hospitalisation (CRH) et sa signature
     */
    public function deleteCRH($id) {
        header('Content-Type: application/json');
        if (!$this->auth->isAdmin() && !in_array(strtoupper($_SESSION['user_role'] ?? ''), ['ADMIN', 'SUPER_ADMIN'], true)) {
            echo json_encode(['success' => false, 'message' => 'Accès réservé à l\'administrateur.']); exit;
        }

        $id   = (int)$id;
        $stmt = $this->db->prepare("SELECT id, patient_id FROM comptes_rendus_hosp WHERE id = ?");
        $stmt->execute([$id]);
        $crh  = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$crh) {
            echo json_encode(['success' => false, 'message' => 'Compte-rendu introuvable.']); exit;
        }

        $this->db->beginTransaction();
        try {
            // Supprimer la signature liée
            $this->db->prepare("DELETE FROM documents_signes WHERE document_type = 'RAPPORT' AND document_id = ?")
                     ->execute([$id]);
            // Supprimer le CRH
            $this->db->prepare("DELETE FROM comptes_rendus_hosp WHERE id = ?")->execute([$id]);
            $this->db->commit();

            $this->audit->logAction('DELETE', 'comptes_rendus_hosp', $id, null, "Suppression CRH #$id");
            echo json_encode(['success' => true, 'message' => 'Compte-rendu supprimé.']);
        } catch (Exception $e) {
            $this->db->rollBack();
            echo json_encode(['success' => false, 'message' => 'Erreur lors de la suppression.']);
        }
        exit;
    }

    /**
     * Supprime définitivement un patient et toutes ses données
     */
    public function deletePatient($id) {
        header('Content-Type: application/json');
        if (!$this->auth->isAdmin() && !in_array(strtoupper($_SESSION['user_role'] ?? ''), ['ADMIN', 'SUPER_ADMIN'], true)) {
            echo json_encode(['success' => false, 'message' => 'Accès réservé à l\'administrateur.']); exit;
        }

        $id   = (int)$id;
        $stmt = $this->db->prepare("SELECT nom, prenom FROM patients WHERE id = ?");
        $stmt->execute([$id]);
        $patient = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$patient) {
            echo json_encode(['success' => false, 'message' => 'Patient introuvable.']); exit;
        }

        $this->db->beginTransaction();
        try {
            // 1. Supprimer les fichiers physiques uploadés
            $docs = $this->db->prepare("SELECT chemin_fichier FROM patient_documents WHERE patient_id = ?");
            $docs->execute([$id]);
            foreach ($docs->fetchAll(PDO::FETCH_COLUMN) as $fichier) {
                $path = __DIR__ . '/../../public/uploads/documents/' . $fichier;
                if (file_exists($path)) @unlink($path);
            }

            // 2. Supprimer les signatures des CRH liés
            $crhIds = $this->db->prepare("SELECT id FROM comptes_rendus_hosp WHERE patient_id = ?");
            $crhIds->execute([$id]);
            foreach ($crhIds->fetchAll(PDO::FETCH_COLUMN) as $crhId) {
                $this->db->prepare("DELETE FROM documents_signes WHERE document_type = 'RAPPORT' AND document_id = ?")
                         ->execute([$crhId]);
            }

            // 3. Supprimer les signatures des ordonnances liées
            $ordoIds = $this->db->prepare("SELECT id FROM ordonnances_pharmacie WHERE patient_id = ?");
            $ordoIds->execute([$id]);
            foreach ($ordoIds->fetchAll(PDO::FETCH_COLUMN) as $ordoId) {
                $this->db->prepare("DELETE FROM documents_signes WHERE document_type = 'ORDONNANCE' AND document_id = ?")
                         ->execute([$ordoId]);
            }

            // 4. Supprimer toutes les données liées (ordre : enfants avant parents)
            $tables = [
                'patient_documents',
                'comptes_rendus_hosp',
                'ordonnances_pharmacie',
                'prescriptions',
                'registre_maladies_chroniques',
                'parametres_vitaux',
                'consultations_pediatriques',
                'consultations',
                'hospitalisations',
            ];
            foreach ($tables as $table) {
                $this->db->prepare("DELETE FROM $table WHERE patient_id = ?")->execute([$id]);
            }

            // 5. Supprimer le patient
            $this->db->prepare("DELETE FROM patients WHERE id = ?")->execute([$id]);
            $this->db->commit();

            $nom = $patient['nom'] . ' ' . $patient['prenom'];
            $this->audit->logAction('DELETE', 'patients', $id, null, "Suppression définitive du patient : $nom");
            echo json_encode(['success' => true, 'message' => "Patient $nom supprimé définitivement."]);

        } catch (Exception $e) {
            $this->db->rollBack();
            error_log('[PatientController] ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Une erreur est survenue. Veuillez réessayer.']);
        }
        exit;
    }

    /* =========================================================
       ADMINISTRATION DES PATIENTS (ADMIN UNIQUEMENT)
       ========================================================= */

    /**
     * Liste complète de tous les patients pour l'administrateur
     */
    public function adminList() {
        $isAdmin = $this->auth->isAdmin() || in_array(strtoupper($_SESSION['user_role'] ?? ''), ['ADMIN', 'SUPER_ADMIN'], true);
        if (!$isAdmin) {
            http_response_code(403);
            $error_cause = "La liste complète des dossiers patients est réservée à l'administrateur.";
            require __DIR__ . '/../views/errors/403.php';
            exit;
        }

        $search     = trim($_GET['search'] ?? '');
        $filtre     = $_GET['filtre'] ?? 'tous';    // tous | actif | inactif | hospitalise
        $service_id = !empty($_GET['service_id']) ? (int)$_GET['service_id'] : null;
        $page       = max(1, (int)($_GET['page'] ?? 1));
        $limit      = 20;
        $offset     = ($page - 1) * $limit;

        // Construction de la requête dynamique
        $where  = ['1=1'];
        $params = [];

        if ($search !== '') {
            $where[]  = "(p.nom LIKE ? OR p.prenom LIKE ? OR p.dossier_numero LIKE ? OR p.telephone LIKE ?)";
            $params   = array_merge($params, ["%$search%", "%$search%", "%$search%", "%$search%"]);
        }
        if ($filtre === 'actif')      { $where[] = "p.actif = 1"; }
        if ($filtre === 'inactif')    { $where[] = "p.actif = 0"; }
        if ($filtre === 'hospitalise'){ $where[] = "p.statut = 'HOSPITALISE'"; }
        if ($service_id)              { $where[] = "p.service_id = ?"; $params[] = $service_id; }

        $whereStr = implode(' AND ', $where);

        $total = $this->db->prepare("SELECT COUNT(*) FROM patients p WHERE $whereStr");
        $total->execute($params);
        $total_patients = (int)$total->fetchColumn();
        $total_pages    = max(1, ceil($total_patients / $limit));

        $stmt = $this->db->prepare("
            SELECT p.id, p.dossier_numero, p.nom, p.prenom, p.date_naissance, p.sexe,
                   p.telephone, p.email, p.ville, p.adresse, p.statut, p.actif,
                   p.profession, p.situation_matrimoniale,
                   p.contact_nom, p.contact_telephone, p.telephone_urgence,
                   p.type_client, p.nom_assurance, p.numero_assure,
                   p.created_at, s.nom_service
            FROM patients p
            LEFT JOIN services s ON p.service_id = s.id
            WHERE $whereStr
            ORDER BY p.created_at DESC
            LIMIT $limit OFFSET $offset
        ");
        $stmt->execute($params);
        $patients = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Liste des services pour le filtre
        $services = $this->db->query("SELECT id, nom_service FROM services ORDER BY nom_service")->fetchAll(PDO::FETCH_ASSOC);

        $adminPage = 'patients';
        require_once __DIR__ . '/../views/admin/patients.php';
    }

    /**
     * Met à jour les données administratives d'un patient (ADMIN)
     */
    /**
     * Met à jour les informations PERSONNELLES (non médicales) d'un patient.
     * Accessible aux médecins, infirmiers et admins.
     *
     * Champs modifiables : nom, prénom, date_naissance, sexe, téléphone, email,
     * adresse, ville, profession, situation matrimoniale, contacts d'urgence,
     * groupe sanguin, type client, infos assurance.
     *
     * Champs NON modifiables ici : dossier_numero (immutable), statut_parcours,
     * statut_hosp, service_id, medecin_id, et toutes les données médicales.
     *
     * Route : POST /patients/update-info/{id}
     */
    public function updatePersonalInfo($id) {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Méthode invalide']); exit;
        }

        $userRole = strtoupper($_SESSION['user_role'] ?? '');
        $autorise = in_array($userRole, [
            'MEDECIN','CHIRURGIEN','GENERALISTE',
            'INFIRMIER','INFIRMIER_CONSULTANT','MAJOR_INFIRMIER','MAJOR',
            'ADMIN','ADMINISTRATEUR','DIRECTEUR','SECRETAIRE'
        ]);
        if (!$autorise) {
            echo json_encode(['success' => false, 'message' => 'Accès refusé.']); exit;
        }

        $id = (int)$id;
        if (!$id) {
            echo json_encode(['success' => false, 'message' => 'ID patient invalide.']); exit;
        }

        try {
            // Vérifier que le patient existe
            $stmt = $this->db->prepare("SELECT id, nom, prenom FROM patients WHERE id = ?");
            $stmt->execute([$id]);
            $existant = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$existant) {
                echo json_encode(['success' => false, 'message' => 'Patient introuvable.']); exit;
            }

            // Détection dynamique des colonnes existantes (sécurité multi-schémas)
            $cols = $this->db->query("SHOW COLUMNS FROM patients")->fetchAll(PDO::FETCH_COLUMN);

            // Liste des champs admis avec normalisation
            $champs = [
                'nom'                    => fn($v) => strtoupper(trim($v)),
                'prenom'                 => fn($v) => ucfirst(strtolower(trim($v))),
                'date_naissance'         => fn($v) => $v ?: null,
                'sexe'                   => fn($v) => in_array($v, ['M','F']) ? $v : null,
                'telephone'              => fn($v) => trim($v) ?: null,
                'email'                  => fn($v) => trim($v) ?: null,
                'adresse'                => fn($v) => trim($v) ?: null,
                'ville'                  => fn($v) => trim($v) ?: null,
                'profession'             => fn($v) => trim($v) ?: null,
                'situation_matrimoniale' => fn($v) => trim($v) ?: null,
                'contact_nom'            => fn($v) => trim($v) ?: null,
                'contact_telephone'      => fn($v) => trim($v) ?: null,
                'telephone_urgence'      => fn($v) => trim($v) ?: null,
                'groupe_sanguin'         => fn($v) => trim($v) ?: null,
                'type_client'            => fn($v) => trim($v) ?: null,
                'nom_assurance'          => fn($v) => trim($v) ?: null,
                'numero_assure'          => fn($v) => trim($v) ?: null,
                'matricule_agent'        => fn($v) => trim($v) ?: null,
                'infirmerie_origine'     => fn($v) => trim($v) ?: null,
                'numero_compte_sage'     => fn($v) => trim($v) ?: null,
            ];

            $sets = [];
            $vals = [];
            $changes = [];
            foreach ($champs as $champ => $normalize) {
                if (!in_array($champ, $cols, true)) continue;
                if (!array_key_exists($champ, $_POST)) continue;
                $valNorm = $normalize($_POST[$champ] ?? '');
                $sets[] = "$champ = ?";
                $vals[] = $valNorm;
                $changes[] = $champ;
            }

            if (empty($sets)) {
                echo json_encode(['success' => false, 'message' => 'Aucune donnée à mettre à jour.']); exit;
            }

            // ── Synchroniser circuit si type_client est modifié ────────────
            if (array_key_exists('type_client', $_POST) && in_array('circuit', $cols, true)) {
                $newTypeClient = strtoupper(trim($_POST['type_client'] ?? ''));
                // Seul AGENTS_PHP va au bureau PHP ; FAMILLE_PHP suit la parité B1/B2
                $newCircuit    = ($newTypeClient === 'AGENTS_PHP') ? 'PHP' : 'STANDARD';
                $sets[]        = 'circuit = ?';
                $vals[]        = $newCircuit;
                $changes[]     = 'circuit';

                // Effacer les champs incompatibles avec le nouveau circuit
                if (!in_array($newTypeClient, ['ASSURANCE'])) {
                    if (in_array('nom_assurance', $cols, true) && !isset($_POST['nom_assurance'])) {
                        $sets[] = 'nom_assurance = NULL'; $sets[] = 'numero_assure = NULL';
                    }
                }
                if ($newTypeClient !== 'BON_PRISE_EN_CHARGE' && in_array('numero_bon', $cols, true)) {
                    if (!isset($_POST['numero_bon'])) {
                        $sets[] = 'numero_bon = NULL';
                    }
                }
                if ($newTypeClient !== 'AGENTS_PHP') {
                    if (in_array('matricule_agent', $cols, true) && !isset($_POST['matricule_agent'])) {
                        $sets[] = 'matricule_agent = NULL'; $sets[] = 'infirmerie_origine = NULL';
                    }
                }
            }

            // Ajouter la date de modification si la colonne existe
            if (in_array('date_modification', $cols, true)) {
                $sets[] = "date_modification = NOW()";
            }
            if (in_array('updated_at', $cols, true)) {
                $sets[] = "updated_at = NOW()";
            }

            $vals[] = $id;
            $sql = "UPDATE patients SET " . implode(', ', $sets) . " WHERE id = ?";
            $this->db->prepare($sql)->execute($vals);

            // Audit
            try {
                $this->audit->logAction('UPDATE', 'patients', $id, null,
                    "MAJ infos personnelles ({$existant['nom']} {$existant['prenom']}) "
                    . "par " . ($_SESSION['user_role'] ?? '?') . " — champs: " . implode(', ', $changes));
            } catch (\Throwable $e) { /* ignorer */ }

            echo json_encode([
                'success'         => true,
                'message'         => '✅ Informations du patient mises à jour.',
                'champs_modifies' => $changes,
            ]);
        } catch (\Throwable $e) {
            error_log('[PatientController] ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Une erreur est survenue. Veuillez réessayer.']);
        }
        exit;
    }

    public function update($id) {
        if (!$this->auth->isAdmin()) {
            http_response_code(403);
            $error_cause = "La modification administrative d'un dossier patient est réservée à l'administrateur.";
            require __DIR__ . '/../views/errors/403.php';
            exit;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . 'admin/patients'); exit;
        }

        $id = (int)$id;

        // Champs purement administratifs (pas de données médicales)
        $stmt = $this->db->prepare("
            UPDATE patients SET
                nom                   = ?,
                prenom                = ?,
                date_naissance        = ?,
                sexe                  = ?,
                telephone             = ?,
                email                 = ?,
                adresse               = ?,
                ville                 = ?,
                profession            = ?,
                situation_matrimoniale= ?,
                contact_nom           = ?,
                contact_telephone     = ?,
                telephone_urgence     = ?,
                type_client           = ?,
                nom_assurance         = ?,
                numero_assure         = ?,
                date_modification     = NOW()
            WHERE id = ?
        ");
        $stmt->execute([
            strtoupper(trim($_POST['nom']   ?? '')),
            ucfirst(strtolower(trim($_POST['prenom'] ?? ''))),
            $_POST['date_naissance'] ?? null,
            $_POST['sexe']           ?? 'M',
            $_POST['telephone']      ?? null,
            $_POST['email']          ?? null,
            $_POST['adresse']        ?? null,
            $_POST['ville']          ?? null,
            $_POST['profession']     ?? null,
            $_POST['situation_matrimoniale'] ?? null,
            $_POST['contact_nom']    ?? null,
            $_POST['contact_telephone'] ?? null,
            $_POST['telephone_urgence'] ?? null,
            $_POST['type_client']    ?? null,
            $_POST['nom_assurance']  ?? null,
            $_POST['numero_assure']  ?? null,
            $id,
        ]);

        $this->audit->logAction('UPDATE', 'patients', $id, null, "Mise à jour administrative par l'admin");

        // Réponse JSON (appel AJAX) ou redirection
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => 'Patient mis à jour.']);
            exit;
        }
        header('Location: ' . BASE_URL . 'admin/patients?success=updated'); exit;
    }

    /**
     * Crée rapidement un patient depuis le dashboard médecin (sans circuit accueil).
     * Route : POST patients/creer-rapide
     * Retourne JSON { success, patient_id, dossier, redirect }
     */
    public function creerRapide(): void {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Méthode invalide.']); exit;
        }

        // Médecins, infirmiers, admins, secrétaires peuvent créer
        $role = strtoupper($_SESSION['user_role'] ?? '');
        $autorise = in_array($role, [
            'MEDECIN','CHIRURGIEN','GENERALISTE',
            'INFIRMIER','INFIRMIER_CONSULTANT','MAJOR_INFIRMIER','MAJOR',
            'ADMIN','ADMINISTRATEUR','DIRECTEUR','SECRETAIRE',
        ]);
        if (!$autorise) {
            echo json_encode(['success' => false, 'message' => 'Accès refusé.']); exit;
        }

        $nom    = strtoupper(trim($_POST['nom']    ?? ''));
        $prenom = ucfirst(strtolower(trim($_POST['prenom'] ?? '')));
        $sexe   = in_array($_POST['sexe'] ?? 'M', ['M','F']) ? $_POST['sexe'] : 'M';
        $force  = !empty($_POST['force']); // bypass duplicate warning

        if (!$nom || !$prenom) {
            echo json_encode(['success' => false, 'message' => 'Le nom et le prénom sont obligatoires.']); exit;
        }

        // Date de naissance
        $ddn = !empty($_POST['date_naissance']) ? $_POST['date_naissance'] : null;
        if (!$ddn && !empty($_POST['age_estimatif'])) {
            $ddn = (date('Y') - (int)$_POST['age_estimatif']) . '-01-01';
        }
        if (!$ddn) $ddn = '1900-01-01';

        $allowed_types = ['BON_PRISE_EN_CHARGE','PAYANT_COMPTANT','ASSURANCE','FAMILLE_PHP','AGENTS_PHP'];
        $type_client = in_array($_POST['type_client'] ?? '', $allowed_types)
                       ? $_POST['type_client'] : 'PAYANT_COMPTANT';

        // ── Vérification des doublons ──────────────────────────────────────
        if (!$force) {
            $ddnProvided = ($ddn && $ddn !== '1900-01-01');
            if ($ddnProvided) {
                $stmtDup = $this->db->prepare("
                    SELECT id, dossier_numero, nom, prenom, date_naissance, sexe, telephone, statut
                    FROM patients
                    WHERE actif = 1
                      AND UPPER(TRIM(nom))    = UPPER(TRIM(?))
                      AND UPPER(TRIM(prenom)) = UPPER(TRIM(?))
                      AND date_naissance      = ?
                    LIMIT 5
                ");
                $stmtDup->execute([$nom, $prenom, $ddn]);
            } else {
                $stmtDup = $this->db->prepare("
                    SELECT id, dossier_numero, nom, prenom, date_naissance, sexe, telephone, statut
                    FROM patients
                    WHERE actif = 1
                      AND UPPER(TRIM(nom))    = UPPER(TRIM(?))
                      AND UPPER(TRIM(prenom)) = UPPER(TRIM(?))
                    LIMIT 5
                ");
                $stmtDup->execute([$nom, $prenom]);
            }
            $doublons = $stmtDup->fetchAll(PDO::FETCH_ASSOC);

            if (!empty($doublons)) {
                echo json_encode([
                    'success'           => false,
                    'duplicate_warning' => true,
                    'message'           => count($doublons) . ' patient(s) avec le même nom existent déjà.',
                    'doublons'          => $doublons,
                ]);
                exit;
            }
        }

        try {
            $this->db->beginTransaction();

            // Numéro de dossier unique
            $annee2  = date('y');
            $prefix  = "HSJM$annee2";
            $stmtMax = $this->db->prepare(
                "SELECT COALESCE(MAX(CAST(SUBSTRING(dossier_numero, 7) AS UNSIGNED)), 0)
                 FROM patients WHERE dossier_numero LIKE ? FOR UPDATE"
            );
            $stmtMax->execute(["$prefix%"]);
            $next    = (int)$stmtMax->fetchColumn() + 1;
            $dossier = $prefix . str_pad($next, 4, '0', STR_PAD_LEFT);

            // Récupérer le service et le médecin créateur pour l'assignation
            $createur_id = (int)($_SESSION['user_id'] ?? 0);
            $service_id  = (int)($_SESSION['service_id'] ?? 0) ?: null;

            // N'assigner le médecin que si c'est un praticien (pas un admin/secrétaire)
            $rolesPraticiens = ['MEDECIN','CHIRURGIEN','GENERALISTE','MEDECIN_URGENCES','MEDECIN_CHEF'];
            $estPraticien = in_array($role, $rolesPraticiens);
            $medecin_id_assigné = $estPraticien ? $createur_id : null;

            $stmt = $this->db->prepare("
                INSERT INTO patients
                    (dossier_numero, nom, prenom, date_naissance, sexe,
                     telephone, telephone2, email, adresse,
                     groupe_sanguin, type_client,
                     allergies, contact_nom, contact_telephone,
                     nationalite, situation_matrimoniale, profession, lieu_naissance,
                     service_id, medecin_id,
                     statut_parcours, circuit, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'ATTENTE_CONSULTATION', 'STANDARD', NOW())
            ");
            $contactLien = trim($_POST['contact_lien'] ?? '');
            $contactNom  = trim($_POST['contact_nom']  ?? '');
            $contactNomFull = $contactLien && $contactNom
                ? "$contactNom ($contactLien)"
                : $contactNom;
            $stmt->execute([
                $dossier, $nom, $prenom, $ddn, $sexe,
                trim($_POST['telephone']           ?? '') ?: null,
                trim($_POST['telephone2']          ?? '') ?: null,
                trim($_POST['email']               ?? '') ?: null,
                trim($_POST['adresse']             ?? '') ?: null,
                trim($_POST['groupe_sanguin']      ?? '') ?: null,
                $type_client,
                trim($_POST['allergies']           ?? '') ?: null,
                $contactNomFull                          ?: null,
                trim($_POST['contact_telephone']   ?? '') ?: null,
                trim($_POST['nationalite']         ?? '') ?: null,
                trim($_POST['situation_matrimoniale'] ?? '') ?: null,
                trim($_POST['profession']          ?? '') ?: null,
                trim($_POST['lieu_naissance']      ?? '') ?: null,
                $service_id,
                $medecin_id_assigné,
            ]);
            $patient_id = $this->db->lastInsertId();

            $this->db->commit();

            $this->audit->logAction('INSERT', 'patients', $patient_id, null,
                "Création rapide — file d'attente médecin #$createur_id");

            echo json_encode([
                'success'    => true,
                'message'    => "Patient $dossier créé et ajouté à la file d'attente.",
                'patient_id' => $patient_id,
                'dossier'    => $dossier,
                'redirect'   => BASE_URL . 'patients/dossier/' . $patient_id,
            ]);
        } catch (Exception $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            error_log('[PatientController] ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Une erreur est survenue. Veuillez réessayer.']);
        }
        exit;
    }

    /**
     * Recherche de patients (nom / prénom / dossier) — AJAX JSON
     * Route : GET patients/recherche?q=...
     * Retourne les 10 premiers résultats.
     */
    public function rechercherPatient(): void {
        header('Content-Type: application/json');
        $q = trim($_GET['q'] ?? '');
        if (strlen($q) < 2) {
            echo json_encode(['success' => true, 'patients' => []]); exit;
        }

        $like = "%$q%";
        $stmt = $this->db->prepare("
            SELECT p.id, p.dossier_numero, p.nom, p.prenom, p.date_naissance, p.sexe,
                   p.telephone, p.statut_parcours, p.statut, p.service_id,
                   s.nom_service
            FROM patients p
            LEFT JOIN services s ON s.id = p.service_id
            WHERE p.actif = 1
              AND (p.nom LIKE ? OR p.prenom LIKE ? OR p.dossier_numero LIKE ?
                   OR CONCAT(p.nom, ' ', p.prenom) LIKE ? OR CONCAT(p.prenom, ' ', p.nom) LIKE ?)
            ORDER BY p.nom, p.prenom
            LIMIT 15
        ");
        $stmt->execute([$like, $like, $like, $like, $like]);
        $patients = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(['success' => true, 'patients' => $patients]);
        exit;
    }

    /**
     * AJAX GET : Retourne les groupes de patients en doublon (même nom + prénom)
     * Route : admin/patients/doublons
     */
    public function getDoublons(): void {
        header('Content-Type: application/json');
        if (!$this->auth->isAdmin() && !in_array(strtoupper($_SESSION['user_role'] ?? ''), ['ADMIN', 'SUPER_ADMIN'], true)) {
            echo json_encode(['success' => false, 'message' => 'Accès réservé à l\'administrateur.']); exit;
        }

        try {
            // Trouver les groupes (nom+prenom identiques) ayant plus d'un dossier
            $stmt = $this->db->query("
                SELECT UPPER(TRIM(nom)) AS nom_key, UPPER(TRIM(prenom)) AS prenom_key,
                       COUNT(*) AS nb
                FROM patients
                GROUP BY UPPER(TRIM(nom)), UPPER(TRIM(prenom))
                HAVING COUNT(*) > 1
                ORDER BY nb DESC, nom_key
                LIMIT 100
            ");
            $groupKeys = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($groupKeys)) {
                echo json_encode(['success' => true, 'groups' => []]);
                exit;
            }

            $groups = [];
            foreach ($groupKeys as $gk) {
                // Récupérer tous les patients de ce groupe avec leurs stats
                $stmtP = $this->db->prepare("
                    SELECT p.id, p.dossier_numero, p.nom, p.prenom,
                           p.date_naissance, p.telephone, p.statut, p.actif,
                           DATE_FORMAT(p.created_at, '%d/%m/%Y %H:%i') AS created_at,
                           (SELECT COUNT(*) FROM consultations c WHERE c.patient_id = p.id) AS nb_consultations
                    FROM patients p
                    WHERE UPPER(TRIM(p.nom)) = ? AND UPPER(TRIM(p.prenom)) = ?
                    ORDER BY p.created_at ASC
                ");
                $stmtP->execute([$gk['nom_key'], $gk['prenom_key']]);
                $patients = $stmtP->fetchAll(PDO::FETCH_ASSOC);

                $groups[] = [
                    'nom'      => $patients[0]['nom']    ?? $gk['nom_key'],
                    'prenom'   => $patients[0]['prenom'] ?? $gk['prenom_key'],
                    'patients' => $patients,
                ];
            }

            echo json_encode(['success' => true, 'groups' => $groups]);
        } catch (\Throwable $e) {
            error_log('[PatientController] ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Une erreur est survenue. Veuillez réessayer.']);
        }
        exit;
    }

    /**
     * Active ou désactive un patient (ADMIN)
     */
    public function toggleActif($id) {
        if (!$this->auth->isAdmin()) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Accès refusé.']); exit;
        }

        $id   = (int)$id;
        $stmt = $this->db->prepare("SELECT actif, nom, prenom FROM patients WHERE id = ?");
        $stmt->execute([$id]);
        $patient = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$patient) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Patient introuvable.']); exit;
        }

        $newActif = $patient['actif'] ? 0 : 1;
        $this->db->prepare("UPDATE patients SET actif = ? WHERE id = ?")->execute([$newActif, $id]);
        $this->audit->logAction('UPDATE', 'patients', $id, null,
            ($newActif ? 'Réactivation' : 'Désactivation') . " du patient par l'admin");

        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'actif'   => $newActif,
            'message' => $newActif ? 'Patient réactivé.' : 'Patient désactivé.'
        ]);
        exit;
    }

    /* ──────────────────────────────────────────────────────────────────
     * POST patients/update-allergies
     * Met à jour les allergies + antécédents depuis le dossier patient
     * ────────────────────────────────────────────────────────────────── */
    public function updateAllergies(): void {
        header('Content-Type: application/json; charset=utf-8');
        $this->auth->requirePermission('patients', 'write');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Méthode non autorisée.']); return;
        }

        $patientId = (int)($_POST['patient_id'] ?? 0);
        if (!$patientId) { echo json_encode(['success' => false, 'message' => 'ID manquant.']); return; }

        $champ  = trim($_POST['champ']  ?? '');
        $valeur = trim($_POST['valeur'] ?? '');

        $champsAutorises = ['allergies','antecedents_medicaux','antecedents_chirurgicaux','antecedents_familiaux'];
        if (!in_array($champ, $champsAutorises)) {
            echo json_encode(['success' => false, 'message' => 'Champ non autorisé.']); return;
        }

        try {
            $this->db->prepare("UPDATE patients SET `$champ` = ? WHERE id = ?")
                     ->execute([$valeur, $patientId]);
            $this->audit->logAction('UPDATE', 'patients', $patientId, null,
                "Mise à jour $champ depuis le dossier");
            echo json_encode(['success' => true, 'champ' => $champ, 'valeur' => $valeur]);
        } catch (\PDOException $e) {
            error_log('[PatientController] ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Une erreur est survenue. Veuillez réessayer.']);
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

    // Partage valable 24 heures
    $date_exp = date('Y-m-d H:i:s', strtotime('+24 hours'));

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

/**
 * Enregistrer l'avis du médecin destinataire sur un dossier partagé
 */
public function saveAvisPartage() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: ' . BASE_URL . 'patients');
        exit;
    }

    $partage_id = (int)($_POST['partage_id'] ?? 0);
    $patient_id = (int)($_POST['patient_id'] ?? 0);
    $avis       = trim($_POST['avis_medecin'] ?? '');

    if (!$partage_id || !$patient_id) {
        header('Location: ' . BASE_URL . 'patients/dossier/'.$patient_id.'?error=avis_invalide');
        exit;
    }

    // Sécurité : seul le destinataire peut enregistrer l'avis, partage encore actif
    $stmtCheck = $this->db->prepare("
        SELECT id FROM partages_dossiers
        WHERE id = ? AND destinataire_id = ? AND patient_id = ? AND date_expiration > NOW()
    ");
    $stmtCheck->execute([$partage_id, $_SESSION['user_id'], $patient_id]);
    if (!$stmtCheck->fetch()) {
        header('Location: ' . BASE_URL . 'patients/dossier/'.$patient_id.'?error=avis_interdit');
        exit;
    }

    $stmt = $this->db->prepare("
        UPDATE partages_dossiers SET avis_medecin = ? WHERE id = ?
    ");
    $ok = $stmt->execute([$avis, $partage_id]);

    if ($ok) {
        header('Location: ' . BASE_URL . 'patients/dossier/'.$patient_id.'?success=avis_sauvegarde#tab-avis-partage');
    } else {
        header('Location: ' . BASE_URL . 'patients/dossier/'.$patient_id.'?error=avis_echec');
    }
    exit;
}

/* ============================================================
   IMPRESSION RÉCAPITULATIF COMPLET DOSSIER PATIENT
   Route : GET /patients/imprimer-recap/{id}
   ============================================================ */
public function printRecap(int $patientId): void {
    $this->auth->requirePermission('patients', 'read');

    $patient = $this->patientModel->getById($patientId);
    if (!$patient) {
        header('Location: ' . BASE_URL . 'patients?error=not_found'); exit;
    }

    $db = $this->db;

    // ── 1. Médecin traitant ───────────────────────────────────────────────
    $medecin = null;
    if (!empty($patient['medecin_id'])) {
        $s = $db->prepare("SELECT nom, prenom, specialite FROM users WHERE id = ? LIMIT 1");
        $s->execute([$patient['medecin_id']]);
        $medecin = $s->fetch(PDO::FETCH_ASSOC);
    }

    // ── 2. Hospitalisation en cours ───────────────────────────────────────
    $hospitalisation = null;
    try {
        $s = $db->prepare("
            SELECT h.*, s.nom_service, ch.nom_chambre, l.nom_lit,
                   u.nom AS medecin_resp_nom, u.prenom AS medecin_resp_prenom
            FROM hospitalisations h
            LEFT JOIN services s ON h.service_id = s.id
            LEFT JOIN lits l ON h.lit_id = l.id
            LEFT JOIN chambres ch ON l.chambre_id = ch.id
            LEFT JOIN users u ON h.medecin_responsable = u.id
            WHERE h.patient_id = ? ORDER BY h.date_admission DESC LIMIT 1
        ");
        $s->execute([$patientId]);
        $hospitalisation = $s->fetch(PDO::FETCH_ASSOC);
    } catch (\Throwable $e) {}

    // ── 3. Antécédents ────────────────────────────────────────────────────
    $antecedents = [];
    try {
        $s = $db->prepare("SELECT type, description, date_survenue FROM antecedents WHERE patient_id = ? ORDER BY type, date_survenue DESC");
        $s->execute([$patientId]);
        foreach ($s->fetchAll(PDO::FETCH_ASSOC) as $r) {
            $antecedents[$r['type']][] = $r;
        }
    } catch (\Throwable $e) {}

    // ── 4. Constantes vitales (10 dernières) ─────────────────────────────
    $constantes = [];
    try {
        $s = $db->prepare("
            SELECT pp.*, u.nom AS inf_nom, u.prenom AS inf_prenom
            FROM patient_parametres pp
            LEFT JOIN users u ON pp.user_id = u.id
            WHERE pp.patient_id = ?
            ORDER BY pp.date_mesure DESC LIMIT 10
        ");
        $s->execute([$patientId]);
        $constantes = array_reverse($s->fetchAll(PDO::FETCH_ASSOC));
    } catch (\Throwable $e) {}

    // ── 5. Consultations (toutes) ─────────────────────────────────────────
    $consultations = [];
    try {
        $s = $db->prepare("
            SELECT c.*, u.nom AS med_nom, u.prenom AS med_prenom, u.specialite
            FROM consultations c
            LEFT JOIN users u ON c.medecin_id = u.id
            WHERE c.patient_id = ?
            ORDER BY c.date_consultation DESC
        ");
        $s->execute([$patientId]);
        $consultations = $s->fetchAll(PDO::FETCH_ASSOC);
    } catch (\Throwable $e) {}

    // ── 6. Bilans laboratoire + résultats par examen ──────────────────────
    $bilans_labo = [];
    try {
        $s = $db->prepare("
            SELECT dl.id AS demande_id, dl.date_creation, dl.statut AS statut_demande,
                   dl.urgence,
                   de.id AS exam_id, de.statut AS statut_exam,
                   de.resultat, de.valeur_numerique, de.unite,
                   de.valeur_normale_min, de.valeur_normale_max,
                   de.interpretation,
                   el.nom AS nom_examen, el.categorie,
                   u.nom AS med_nom, u.prenom AS med_prenom,
                   dl.consultation_id
            FROM demandes_laboratoire dl
            JOIN demande_examens de ON de.demande_id = dl.id
            JOIN examens_laboratoire el ON de.examen_id = el.id
            LEFT JOIN users u ON dl.medecin_id = u.id
            WHERE dl.patient_id = ?
            ORDER BY dl.date_creation DESC, el.categorie, el.nom
        ");
        $s->execute([$patientId]);
        foreach ($s->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $bilans_labo[$row['demande_id']]['info'] = [
                'date'        => $row['date_creation'],
                'statut'      => $row['statut_demande'],
                'urgence'     => $row['urgence'],
                'medecin'     => trim(($row['med_prenom'] ?? '') . ' ' . ($row['med_nom'] ?? '')),
                'consult_id'  => $row['consultation_id'],
            ];
            $bilans_labo[$row['demande_id']]['examens'][] = $row;
        }
    } catch (\Throwable $e) {}

    // ── 7. Bilans imagerie ────────────────────────────────────────────────
    $bilans_imagerie = [];
    try {
        $s = $db->prepare("
            SELECT di.*, u.nom AS med_nom, u.prenom AS med_prenom
            FROM demandes_imagerie di
            LEFT JOIN users u ON di.medecin_id = u.id
            WHERE di.patient_id = ?
            ORDER BY di.date_creation DESC
        ");
        $s->execute([$patientId]);
        $bilans_imagerie = $s->fetchAll(PDO::FETCH_ASSOC);
    } catch (\Throwable $e) {}

    // ── 8. Soins effectués (hospitalisés) ────────────────────────────────
    $soins_realises = [];
    try {
        if ($hospitalisation) {
            $s = $db->prepare("
                SELECT sh.*, up.nom AS planif_nom, up.prenom AS planif_prenom,
                       ue.nom AS exec_nom, ue.prenom AS exec_prenom
                FROM soins_hospitalisation sh
                LEFT JOIN users up ON sh.user_id_planificateur = up.id
                LEFT JOIN users ue ON sh.user_id_executant = ue.id
                WHERE sh.admission_id = ?
                ORDER BY sh.date_prevue ASC
            ");
            $s->execute([$hospitalisation['id']]);
            $soins_realises = $s->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch (\Throwable $e) {}

    // ── 9. Soins details (plan de soins infirmier) ────────────────────────
    $soins_details = [];
    try {
        $s = $db->prepare("
            SELECT sd.*, u.nom AS inf_nom, u.prenom AS inf_prenom,
                   ps.date_plan
            FROM soins_details sd
            LEFT JOIN plan_soins ps ON sd.plan_id = ps.id
            LEFT JOIN users u ON sd.infirmier_id = u.id
            WHERE ps.patient_id = ?
            ORDER BY ps.date_plan DESC, sd.heure ASC
        ");
        $s->execute([$patientId]);
        $soins_details = $s->fetchAll(PDO::FETCH_ASSOC);
    } catch (\Throwable $e) {}

    // ── 10. Observations infirmières ─────────────────────────────────────
    $observations = [];
    try {
        $s = $db->prepare("
            SELECT oe.*, u.nom AS user_nom, u.prenom AS user_prenom, u.role
            FROM observations_evolution oe
            LEFT JOIN users u ON oe.user_id = u.id
            WHERE oe.patient_id = ?
            ORDER BY oe.date_obs DESC
        ");
        $s->execute([$patientId]);
        $observations = $s->fetchAll(PDO::FETCH_ASSOC);
    } catch (\Throwable $e) {}

    // ── 11. Réévaluations hospitalisées ──────────────────────────────────
    $reevaluations = [];
    try {
        $s = $db->prepare("
            SELECT r.*, u.nom AS med_nom, u.prenom AS med_prenom, u.specialite AS med_specialite
            FROM reevaluations_hospitalisees r
            LEFT JOIN users u ON r.medecin_id = u.id
            WHERE r.patient_id = ?
            ORDER BY r.date_reevaluation ASC, r.heure_reevaluation ASC
        ");
        $s->execute([$patientId]);
        $reevaluations = $s->fetchAll(PDO::FETCH_ASSOC);
    } catch (\Throwable $e) {}

    // ── 12. Ordonnances + médicaments ────────────────────────────────────
    $ordonnances = [];
    try {
        $s = $db->prepare("
            SELECT op.*, u.nom AS med_nom, u.prenom AS med_prenom
            FROM ordonnances_pharmacie op
            LEFT JOIN users u ON op.medecin_id = u.id
            WHERE op.patient_id = ?
            ORDER BY op.date_creation DESC
        ");
        $s->execute([$patientId]);
        $ords = $s->fetchAll(PDO::FETCH_ASSOC);
        foreach ($ords as $ord) {
            try {
                $sm = $db->prepare("
                    SELECT om.*, COALESCE(om.nom_medicament, m.nom, 'Médicament') AS nom_med,
                           m.forme, m.dosage AS dosage_ref
                    FROM ordonnance_medicaments om
                    LEFT JOIN medicaments m ON om.medicament_id = m.id
                    WHERE om.ordonnance_id = ?
                ");
                $sm->execute([$ord['id']]);
                $ord['medicaments'] = $sm->fetchAll(PDO::FETCH_ASSOC);
            } catch (\Throwable $e) { $ord['medicaments'] = []; }
            $ordonnances[] = $ord;
        }
    } catch (\Throwable $e) {}

    // ── 12. Médecin imprimant ─────────────────────────────────────────────
    $imprimente_par = null;
    try {
        $s = $db->prepare("SELECT nom, prenom, specialite, role FROM users WHERE id = ? LIMIT 1");
        $s->execute([$_SESSION['user_id'] ?? 0]);
        $imprimente_par = $s->fetch(PDO::FETCH_ASSOC);
    } catch (\Throwable $e) {}

    require_once __DIR__ . '/../views/patients/print_recap.php';
}
}

