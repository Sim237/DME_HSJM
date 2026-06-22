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
FICHIER : app/controllers/ConsultationController.php
============================================================================ */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../models/Consultation.php';
require_once __DIR__ . '/../models/Patient.php';
require_once __DIR__ . '/../services/AuditService.php';

class ConsultationController {
    private $db;
    private $consultationModel;
    private $patientModel;
    private $audit;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->consultationModel = new Consultation();
        $this->patientModel = new Patient();
        $this->audit = new AuditService();

        // Démarrage de session si pas déjà fait (pour stocker les étapes temporaires)
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    // ── Schéma brouillons ─────────────────────────────────────────────────────
    private function ensureSchema(): void {
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS consultations_brouillons (
                id          INT AUTO_INCREMENT PRIMARY KEY,
                patient_id  INT NOT NULL,
                medecin_id  INT NOT NULL,
                etape       TINYINT DEFAULT 1,
                data        LONGTEXT NULL,
                saved_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY uk_med_pat (medecin_id, patient_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
    }

    // ── Endpoint AJAX autosave ────────────────────────────────────────────────
    public function autosave(): void {
        header('Content-Type: application/json; charset=utf-8');

        if (empty($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'session_expired' => true,
                              'message' => 'Session expirée — données conservées en base.']);
            exit;
        }

        $patient_id = (int)($_POST['patient_id'] ?? 0);
        $medecin_id = (int)$_SESSION['user_id'];
        $etape      = (int)($_POST['etape_actuelle'] ?? 1);

        if (!$patient_id) {
            echo json_encode(['success' => false, 'message' => 'patient_id manquant.']);
            exit;
        }

        // Fusionner en session (maintient la cohérence si la session est encore active)
        if (!isset($_SESSION['consultation_temp'])) $_SESSION['consultation_temp'] = [];
        $_SESSION['consultation_temp'] = array_merge($_SESSION['consultation_temp'], $_POST);

        $json = json_encode($_SESSION['consultation_temp'], JSON_UNESCAPED_UNICODE);

        try {
            $this->ensureSchema();
            $this->db->prepare("
                INSERT INTO consultations_brouillons (patient_id, medecin_id, etape, data, saved_at)
                VALUES (?, ?, ?, ?, NOW())
                ON DUPLICATE KEY UPDATE etape = VALUES(etape), data = VALUES(data), saved_at = NOW()
            ")->execute([$patient_id, $medecin_id, $etape, $json]);

            echo json_encode([
                'success'  => true,
                'saved_at' => date('H:i:s'),
                'etape'    => $etape,
            ]);
        } catch (\Throwable $e) {
            error_log('[autosave] ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Erreur base de données.']);
        }
        exit;
    }

    // ── Supprimer le brouillon DB d'un médecin/patient ───────────────────────
    private function supprimerBrouillon(int $patient_id): void {
        try {
            $this->ensureSchema();
            $this->db->prepare("
                DELETE FROM consultations_brouillons
                WHERE patient_id = ? AND medecin_id = ?
            ")->execute([$patient_id, (int)($_SESSION['user_id'] ?? 0)]);
        } catch (\Throwable $e) {
            error_log('[supprimerBrouillon] ' . $e->getMessage());
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
            $patient_id = (int)($_POST['patient_id'] ?? 0);
            $type = strtoupper(trim($_POST['type_consultation'] ?? 'EXTERNE'));
            if (!in_array($type, ['INTERNE', 'EXTERNE'])) $type = 'EXTERNE';

            if (!$patient_id) {
                header('Location: ' . BASE_URL . 'consultation?error=patient_manquant');
                exit;
            }

            // Mettre le patient en statut EN_CONSULTATION pour qu'il apparaisse
            // dans le tableau de bord du médecin et verrouiller la file
            try {
                $this->db->prepare(
                    "UPDATE patients
                     SET statut_parcours = 'EN_CONSULTATION',
                         medecin_id      = ?
                     WHERE id = ? AND statut_parcours NOT IN ('HOSPITALISE')"
                )->execute([$_SESSION['user_id'] ?? null, $patient_id]);

                // Pour les patients hospitalisés, on garde leur statut mais on note le médecin
                $this->db->prepare(
                    "UPDATE patients SET medecin_id = ?
                     WHERE id = ? AND statut_parcours = 'HOSPITALISE'"
                )->execute([$_SESSION['user_id'] ?? null, $patient_id]);
            } catch (\Throwable $e) {
                error_log('[commencerConsultation] ' . $e->getMessage());
            }

            // Vider le brouillon de session précédent
            if (session_status() === PHP_SESSION_NONE) session_start();
            unset($_SESSION['consultation_temp']);

            // Redirection vers le formulaire selon la spécialité du médecin
            $specialite = strtolower(trim($_SESSION['specialite'] ?? ''));
            // Fallback : si la session est ancienne (avant le fix), requêter la BD
            if ($specialite === '' && !empty($_SESSION['user_id'])) {
                try {
                    $stmtSpec = $this->db->prepare("SELECT specialite FROM users WHERE id = ? LIMIT 1");
                    $stmtSpec->execute([$_SESSION['user_id']]);
                    $specialite = strtolower(trim($stmtSpec->fetchColumn() ?: ''));
                    $_SESSION['specialite'] = $specialite; // mettre en cache pour la suite
                } catch (\Throwable $e) { /* ignore */ }
            }

            // Correspondance spécialité → formulaire spécialisé
            $formsSpecialises = [
                'gynec'   => 'consultation-gyneco',    // gynécologie (ASCII)
                'gynéc'   => 'consultation-gyneco',    // gynécologie (accentué : é = multibyte)
                'obstet'  => 'consultation-gyneco',    // obstétrique
                'materni' => 'consultation-gyneco',    // maternité
            ];

            $formulaireSlug = null;
            foreach ($formsSpecialises as $motCle => $slug) {
                if (str_contains($specialite, $motCle)) {
                    $formulaireSlug = $slug;
                    break;
                }
            }

            // Fallback service (médecin généraliste affecté au service de maternité)
            if ($formulaireSlug === null) {
                $nomService = strtolower(trim($_SESSION['nom_service'] ?? ''));
                if (str_contains($nomService, 'matern') || str_contains($nomService, 'gynec') || str_contains($nomService, 'gynéc')) {
                    $formulaireSlug = 'consultation-gyneco';
                }
            }

            // Fallback rôle (si spécialité non renseignée en BD)
            if ($formulaireSlug === null && in_array(strtoupper($_SESSION['user_role'] ?? ''), [
                'GYNECOLOGIE', 'GYNECO_OBS', 'SAGE_FEMME', 'GYNECO'
            ])) {
                $formulaireSlug = 'consultation-gyneco';
            }

            if ($formulaireSlug !== null) {
                // Formulaire spécialisé (gynéco, etc.)
                $url = BASE_URL . "formulaire/creer/{$formulaireSlug}/{$patient_id}";
            } else {
                // Formulaire standard 7 étapes
                $url = BASE_URL . "consultation/formulaire?patient_id={$patient_id}&type={$type}&etape=1";
            }

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

    // 5. GESTION DES DONNÉES TEMPORAIRES (Brouillon de session + brouillon DB)
    // On récupère ce que le médecin a déjà saisi s'il navigue entre les étapes
    if (session_status() === PHP_SESSION_NONE) { session_start(); }
    $consultation_data  = $_SESSION['consultation_temp'] ?? [];
    $brouillon_restored = null; // horodatage de restauration, affiché dans la vue

    // Si la session est vide (expirée ou nouvelle page), tenter la restauration depuis DB
    if (empty($consultation_data) && $patient_id && !empty($_SESSION['user_id'])) {
        try {
            $this->ensureSchema();
            $stmtBr = $this->db->prepare(
                "SELECT data, etape, saved_at
                 FROM consultations_brouillons
                 WHERE patient_id = ? AND medecin_id = ?
                 LIMIT 1"
            );
            $stmtBr->execute([$patient_id, (int)$_SESSION['user_id']]);
            $br = $stmtBr->fetch(PDO::FETCH_ASSOC);
            if ($br && !empty($br['data'])) {
                $restored = json_decode($br['data'], true);
                if ($restored) {
                    $_SESSION['consultation_temp'] = $restored;
                    $consultation_data   = $restored;
                    $brouillon_restored  = $br['saved_at'];
                    // Reprendre à l'étape où le médecin s'était arrêté
                    if ((int)($br['etape'] ?? 0) > $etape) {
                        $etape = (int)$br['etape'];
                    }
                }
            }
        } catch (\Throwable $e) {
            error_log('[formulaire] restauration brouillon : ' . $e->getMessage());
        }
    }

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
                // Tenter via la table examens (patient_id direct)
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
            } catch (Exception $e) { /* table absente ou structure différente — on continue */ }
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

        // --- ÉTAPE 2 : EXAMEN PHYSIQUE — Sauvegarde des paramètres vitaux ---
        // Si le médecin saisit les constantes, on les persiste dans patient_parametres
        // et on efface le flag parametres_requis pour retirer le badge "⏳ Paramètres à prendre".
        if ($etape_actuelle == 2) {
            $num = fn($k, $type = 'float') => (isset($_POST[$k]) && $_POST[$k] !== '')
                ? ($type === 'int' ? (int)$_POST[$k] : (float)str_replace(',', '.', $_POST[$k]))
                : null;

            $temp   = $num('temperature');
            $fc     = $num('frequence_cardiaque', 'int');
            $poids  = $num('poids');
            $taille = $num('taille');
            $spo2   = $num('spo2') ?? $num('saturation_oxygene') ?? $num('saturation');
            $fr     = $num('frequence_respiratoire', 'int');
            // Glycémie capillaire (champ du formulaire = glycemie_capillaire)
            $glyc      = $num('glycemie_capillaire') ?? $num('glycemie');
            $glycType  = in_array($_POST['glycemie_type'] ?? '', ['a_jeun', 'aleatoire'])
                         ? $_POST['glycemie_type'] : null;

            // Tension artérielle format "120/80" → sys / dia
            $sys = null; $dia = null;
            if (!empty($_POST['tension_arterielle'])) {
                $parts = explode('/', trim($_POST['tension_arterielle']));
                if (count($parts) === 2) { $sys = (int)$parts[0]; $dia = (int)$parts[1]; }
            }
            // Fallback sur les champs séparés
            if ($sys === null) { $sys = $num('pression_arterielle_systolique', 'int'); }
            if ($dia === null) { $dia = $num('pression_arterielle_diastolique', 'int'); }

            // Sauvegarder uniquement si au moins une valeur est renseignée
            $hasVitals = ($temp !== null || $fc !== null || $sys !== null || $poids !== null);
            if ($hasVitals) {
                try {
                    $this->db->prepare("
                        INSERT INTO patient_parametres
                            (patient_id, user_id, temperature,
                             pression_arterielle_systolique, pression_arterielle_diastolique,
                             frequence_cardiaque, poids, taille, saturation_oxygene,
                             frequence_respiratoire, glycemie, glycemie_type,
                             motif_consultation, date_mesure)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
                    ")->execute([
                        $patient_id,
                        $_SESSION['user_id'] ?? null,
                        $temp, $sys, $dia, $fc, $poids, $taille, $spo2,
                        $fr, $glyc, $glycType,
                        $_SESSION['consultation_temp']['motif_consultation'] ?? null,
                    ]);
                    // Effacer le badge "⏳ Paramètres à prendre"
                    $this->db->prepare("UPDATE patients SET parametres_requis = 0 WHERE id = ?")
                             ->execute([$patient_id]);
                } catch (\Throwable $e) {
                    error_log('[ConsultationController] Sauvegarde vitaux étape 2 : ' . $e->getMessage());
                }
            }
        }

        // --- ÉTAPE 4 : BILANS ---
        if ($etape_actuelle == 4) {
            require_once __DIR__ . '/../services/LaboratoireService.php';
            $laboratoireService = new LaboratoireService();

            // ANTI-DOUBLON : On crée ou on met à jour
            if (empty($_SESSION['consultation_temp']['consultation_id'])) {
                $consultation_id = $this->consultationModel->create($data);
                if ($consultation_id) {
                    $_SESSION['consultation_temp']['consultation_id'] = $consultation_id;
                }
            } else {
                $consultation_id = $_SESSION['consultation_temp']['consultation_id'];
                $this->consultationModel->update($consultation_id, $data);
            }

            if ($consultation_id) {
                // Examens envoyés via le formulaire (non-AJAX)
                if (!empty($_POST['examens'])) {
                    $laboratoireService->creerDemandeExamens($consultation_id, $_POST['examens']);
                }

                // Lier rétroactivement les demandes AJAX (labo + imagerie) créées sans consultation_id
                try {
                    $this->db->prepare(
                        "UPDATE demandes_laboratoire
                         SET consultation_id = ?
                         WHERE patient_id = ? AND consultation_id IS NULL
                           AND date_creation > DATE_SUB(NOW(), INTERVAL 4 HOUR)"
                    )->execute([$consultation_id, $patient_id]);

                    $this->db->prepare(
                        "UPDATE demandes_imagerie
                         SET consultation_id = ?
                         WHERE patient_id = ? AND consultation_id IS NULL
                           AND date_creation > DATE_SUB(NOW(), INTERVAL 4 HOUR)"
                    )->execute([$consultation_id, $patient_id]);
                } catch (Exception $e) {
                    error_log('[Etape4] Liaison demandes AJAX: ' . $e->getMessage());
                }

                // Auto-créer ou mettre à jour le bulletin laboratoire
                $this->autoCreerBulletinLabo($consultation_id, $patient_id);
            }
        }

        // --- ÉTAPE 5 : TRAITEMENT (PHARMACIE) ---
        if ($etape_actuelle == 5) {
            require_once __DIR__ . '/../services/PharmacieService.php';
            $pharmacieService = new PharmacieService();

            // ANTI-DOUBLON : On utilise l'ID existant ou on crée la consultation
            if (empty($_SESSION['consultation_temp']['consultation_id'])) {
                $consultation_id = $this->consultationModel->create($data);
                if ($consultation_id) {
                    $_SESSION['consultation_temp']['consultation_id'] = $consultation_id;
                }
            } else {
                $consultation_id = $_SESSION['consultation_temp']['consultation_id'];
                $this->consultationModel->update($consultation_id, $data);
            }

            // Créer l'ordonnance seulement s'il y a des médicaments ET une consultation valide
            // Éviter les doublons si une ordonnance existe déjà pour cette consultation
            $ordonnanceExistante = false;
            if (!empty($consultation_id)) {
                try {
                    $stmtOrdCheck = $this->db->prepare(
                        "SELECT id FROM ordonnances_pharmacie WHERE consultation_id = ? ORDER BY id DESC LIMIT 1"
                    );
                    $stmtOrdCheck->execute([$consultation_id]);
                    $existing = $stmtOrdCheck->fetch(PDO::FETCH_ASSOC);
                    if ($existing) {
                        $ordonnanceExistante = true;
                        $_SESSION['consultation_temp']['ordonnance_id'] = $existing['id'];
                    }
                } catch (Exception $e) {}
            }
            if (!empty($_POST['medicaments']) && !empty($consultation_id) && !$ordonnanceExistante) {
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
            // Fusionner avec les données DB existantes pour ne pas écraser avec du vide
            $existing = $this->consultationModel->getById($consultation_id);
            if ($existing) {
                foreach ($existing as $col => $val) {
                    if (($data[$col] ?? null) === null || $data[$col] === '') {
                        $data[$col] = $val;
                    }
                }
            }
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
                // Si un suivi est planifié → SORTI mais avec RDV (pas re-boucler sur ACCUEIL)
                // Le patient reviendra via un nouveau passage à l'accueil le jour du RDV.
                $nouveauStatut = 'SORTI';
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
                    "INSERT INTO agenda_medical (patient_id, medecin_id, date_debut, date_fin, titre, statut, type_rdv)
                     VALUES (?, ?, ?, DATE_ADD(?, INTERVAL 30 MINUTE), ?, 'confirme', 'suivi')"
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

        // ── 6. Audit : traçabilité de la consultation finalisée ───
        try {
            $pNom   = '';
            $pInfo  = $this->patientModel->getById($patient_id);
            if ($pInfo) $pNom = trim(($pInfo['nom'] ?? '') . ' ' . ($pInfo['prenom'] ?? ''));
            $diag   = $data['diagnostic_principal'] ?? '';
            $motif  = $data['motif_consultation'] ?? '';
            $desc   = 'Consultation finalisée';
            if ($pNom)  $desc .= ' — patient : ' . $pNom . ' (#' . $patient_id . ')';
            if ($motif) $desc .= ' · Motif : ' . mb_substr($motif, 0, 80);
            if ($diag)  $desc .= ' · Diagnostic : ' . mb_substr($diag, 0, 120);

            $this->audit->log('UPDATE', 'consultations', $consultation_id, $desc, null, [
                'patient_id'           => $patient_id,
                'patient'              => $pNom,
                'type'                 => $data['type'] ?? 'EXTERNE',
                'motif'                => $motif,
                'diagnostic_principal' => $diag,
                'plan_traitement'      => mb_substr($data['plan_traitement'] ?? '', 0, 200),
                'suivi_prevu'          => $data['date_suivi'] ?? null,
            ]);
        } catch (Exception $e) {
            error_log('[finaliserConsultation] Audit ignoré : ' . $e->getMessage());
        }

        // ── 7. Nettoyage session + brouillon DB, puis redirection ────────────
        unset($_SESSION['consultation_temp']);
        $this->supprimerBrouillon((int)$patient_id);
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
        $consultation = $this->consultationModel->getById($id);

        if (!$consultation) {
            die("Consultation introuvable");
        }

        $patient = $this->patientModel->getById($consultation['patient_id']);
        $recap_patient_id = (int)$consultation['patient_id'];

        // Lier rétroactivement les demandes labo sans consultation_id (créées le même jour)
        // IMPORTANT : doit s'exécuter AVANT la requête $examens_list pour que les demandes
        // récemment liées apparaissent directement dans la liste.
        try {
            $this->db->prepare(
                "UPDATE demandes_laboratoire SET consultation_id = ?
                 WHERE patient_id = ? AND consultation_id IS NULL
                   AND DATE(date_creation) = (SELECT DATE(date_consultation) FROM consultations WHERE id = ? LIMIT 1)"
            )->execute([$id, $recap_patient_id, $id]);
        } catch (Exception $e) { error_log('[Recap] Liaison rétro labo: ' . $e->getMessage()); }

        // Antécédents
        $antecedents = [];
        try {
            $stmtA = $this->db->prepare(
                "SELECT type, description, date_survenue FROM antecedents
                 WHERE patient_id = ? ORDER BY type, date_survenue DESC"
            );
            $stmtA->execute([$consultation['patient_id']]);
            foreach ($stmtA->fetchAll(PDO::FETCH_ASSOC) as $r) {
                $antecedents[$r['type']][] = $r;
            }
        } catch (Exception $e) {}

        // Examens / bilans liés à la consultation
        $examens_list = [];
        try {
            // Bilans de laboratoire — jointure correcte via demande_examens
            $stmtLab = $this->db->prepare(
                "SELECT dl.id, 'laboratoire' as type_examen, dl.statut, dl.date_creation as date_demande,
                        GROUP_CONCAT(el.nom ORDER BY el.nom SEPARATOR ', ') as noms_examens
                 FROM demandes_laboratoire dl
                 JOIN demande_examens de ON de.demande_id = dl.id
                 JOIN examens_laboratoire el ON de.examen_id = el.id
                 WHERE dl.consultation_id = ?
                 GROUP BY dl.id
                 ORDER BY dl.date_creation DESC"
            );
            $stmtLab->execute([$id]);
            $labo_list = $stmtLab->fetchAll(PDO::FETCH_ASSOC);
            
            // Bilans d'imagerie (COALESCE : couvre BilanController et ImagerieController)
            $stmtImg = $this->db->prepare(
                "SELECT di.id, 'imagerie' as type_examen, di.statut, di.date_creation as date_demande,
                        CONCAT(
                            COALESCE(NULLIF(di.type_examen,'autre'), di.type_imagerie, 'Imagerie'),
                            ' - ',
                            COALESCE(NULLIF(di.partie_corps,''), di.partie_code, 'Zone non précisée')
                        ) as noms_examens
                 FROM demandes_imagerie di
                 WHERE di.consultation_id = ?
                 ORDER BY di.date_creation DESC"
            );
            $stmtImg->execute([$id]);
            $imaging_list = $stmtImg->fetchAll(PDO::FETCH_ASSOC);
            
            // Fusionner les deux listes
            $examens_list = array_merge($labo_list, $imaging_list);
            
        } catch (Exception $e) { 
            error_log('Erreur examens: ' . $e->getMessage());
        }

        // Ordonnance pharmacie
        $prescription = null;
        $medicaments_prescrits = [];
        try {
            $stmtOrd = $this->db->prepare(
                "SELECT op.* FROM ordonnances_pharmacie op WHERE op.consultation_id = ? ORDER BY op.id DESC LIMIT 1"
            );
            $stmtOrd->execute([$id]);
            $prescription = $stmtOrd->fetch(PDO::FETCH_ASSOC);

            // Fallback : chercher par patient + date si aucune ordonnance liée à cette consultation
            if (!$prescription && $recap_patient_id) {
                $stmtOrdFb = $this->db->prepare(
                    "SELECT op.* FROM ordonnances_pharmacie op
                     WHERE op.patient_id = ?
                       AND DATE(op.date_creation) = DATE((SELECT date_consultation FROM consultations WHERE id = ? LIMIT 1))
                     ORDER BY op.id DESC LIMIT 1"
                );
                $stmtOrdFb->execute([$recap_patient_id, $id]);
                $prescription = $stmtOrdFb->fetch(PDO::FETCH_ASSOC);
                // Lier rétroactivement si trouvée sans consultation_id
                if ($prescription && empty($prescription['consultation_id'])) {
                    try {
                        $this->db->prepare(
                            "UPDATE ordonnances_pharmacie SET consultation_id = ? WHERE id = ? AND consultation_id IS NULL"
                        )->execute([$id, $prescription['id']]);
                    } catch (Exception $e) {}
                }
            }

            if ($prescription) {
                $colCheck = $this->db->query("SHOW COLUMNS FROM ordonnance_medicaments LIKE 'nom_medicament'")->rowCount();
                $nomCol = $colCheck > 0 ? 'om.nom_medicament' : "COALESCE(m.nom, 'Médicament') as nom_medicament";
                $stmtMed = $this->db->prepare(
                    "SELECT om.*, $nomCol, m.forme, m.dosage as dosage_ref
                     FROM ordonnance_medicaments om
                     LEFT JOIN medicaments m ON om.medicament_id = m.id
                     WHERE om.ordonnance_id = ?"
                );
                $stmtMed->execute([$prescription['id']]);
                $medicaments_prescrits = $stmtMed->fetchAll(PDO::FETCH_ASSOC);
            }
        } catch (Exception $e) {}

        // Services disponibles (pour modal transfer/hospitalisation)
        $services = [];
        try {
            $services = $this->db->query("SELECT id, nom_service AS nom FROM services ORDER BY nom_service")->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {}

        // Services cliniques uniquement (pour sélecteur destination hospitalisation médecin)
        $servicesCliniques = [];
        try {
            $servicesCliniques = $this->db->query(
                "SELECT id, nom_service FROM services
                  WHERE id IN (1, 2, 3, 4, 5, 6, 7, 15, 20)
                  ORDER BY nom_service ASC"
            )->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {}

        // Bulletins d'examens pour cette consultation
        $bulletins_labo      = [];
        $bulletins_imagerie  = [];
        try {
            $stmtBul = $this->db->prepare(
                "SELECT * FROM bulletins_examens WHERE consultation_id = ? ORDER BY date_creation DESC"
            );
            $stmtBul->execute([$id]);
            foreach ($stmtBul->fetchAll(PDO::FETCH_ASSOC) as $b) {
                if ($b['type'] === 'laboratoire') $bulletins_labo[]     = $b;
                else                              $bulletins_imagerie[] = $b;
            }
        } catch (Exception $e) { /* table absente ou vide — ignorer */ }

        // Auto-créer le bulletin si des demandes labo existent mais pas encore de bulletin
        if (empty($bulletins_labo)) {
            require_once __DIR__ . '/../services/LaboratoireService.php';
            $this->autoCreerBulletinLabo((int)$id, $recap_patient_id);
            // Recharger après création éventuelle
            try {
                $stmtBul2 = $this->db->prepare(
                    "SELECT * FROM bulletins_examens WHERE consultation_id = ? AND type = 'laboratoire' ORDER BY date_creation DESC"
                );
                $stmtBul2->execute([$id]);
                $bulletins_labo = $stmtBul2->fetchAll(PDO::FETCH_ASSOC);
            } catch (Exception $e) {}
        }

        // Lits disponibles par service (pour modal transfer)
        $lits_dispo = [];
        try {
            $stmtL = $this->db->query(
                "SELECT l.id, l.nom_lit, l.service_id, s.nom_service as service_nom
                 FROM lits l JOIN services s ON l.service_id = s.id
                 WHERE l.statut = 'DISPONIBLE' AND l.occupied_by_patient_id IS NULL
                 ORDER BY s.nom_service, l.nom_lit"
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
                ? date_diff(date_create($patient['date_naissance']), date_create('today'))->y
                : null;
            $criteres_hosp = HospitalisationService::analyserCriteresHospitalisation($consultation, $age);
        } catch (Exception $e) {}

        // Vue : recapitulatif.php
        require_once __DIR__ . '/../views/consultations/recapitulatif.php';
    }

    /**
     * Pré-charge une consultation existante en session pour la modifier
     * Route : consultation/modifier/{id}
     */
    public function modifier($id) {
        $id = (int)$id;
        $consultation = $this->consultationModel->getById($id);

        if (!$consultation) {
            header('Location: ' . BASE_URL . 'dashboard?error=consultation_introuvable');
            exit;
        }

        // Sécurité : seul le médecin auteur ou un admin peut modifier
        $userRole = $_SESSION['user_role'] ?? '';
        $userId   = (int)($_SESSION['user_id'] ?? 0);
        $isAdmin  = in_array($userRole, ['ADMIN', 'ADMINISTRATEUR', 'DIRECTEUR']);
        if (!$isAdmin && (int)$consultation['medecin_id'] !== $userId) {
            header('Location: ' . BASE_URL . 'consultation/recapitulatif/' . $id . '?error=not_authorized');
            exit;
        }

        // Reconstruire tension_arterielle "sys/dia" depuis les deux colonnes séparées
        $ta = '';
        if (!empty($consultation['tension_systolique']) && !empty($consultation['tension_diastolique'])) {
            $ta = $consultation['tension_systolique'] . '/' . $consultation['tension_diastolique'];
        }

        // Reconstruire TA bras gauche et droit au format "sys/dia"
        $ta_gauche = '';
        if (!empty($consultation['ta_bras_gauche_systolique']) && !empty($consultation['ta_bras_gauche_diastolique'])) {
            $ta_gauche = $consultation['ta_bras_gauche_systolique'] . '/' . $consultation['ta_bras_gauche_diastolique'];
        }
        $ta_droit = '';
        if (!empty($consultation['ta_bras_droit_systolique']) && !empty($consultation['ta_bras_droit_diastolique'])) {
            $ta_droit = $consultation['ta_bras_droit_systolique'] . '/' . $consultation['ta_bras_droit_diastolique'];
        }

        // Type de consultation : la DB stocke en minuscules, la session attend MAJUSCULES
        $typeConsultation = strtoupper($consultation['type_consultation'] ?? 'EXTERNE');

        // Remplir la session exactement comme le ferait le formulaire étape par étape
        $_SESSION['consultation_temp'] = [
            // Identifiants
            'consultation_id'               => $id,
            'patient_id'                    => $consultation['patient_id'],
            'medecin_id'                    => $consultation['medecin_id'],
            'type'                          => $typeConsultation,
            'date_consultation'             => $consultation['date_consultation'],
            // Flag mode édition (affiché dans le formulaire)
            '_edit_mode'                    => true,

            // Étape 1 — Anamnèse
            'motif_consultation'            => $consultation['motif_consultation']           ?? '',
            'histoire_maladie'              => $consultation['histoire_maladie']             ?? '',
            'automedication'                => $consultation['automedication']               ?? '',
            'complement_anamnese'           => $consultation['complement_anamnese']          ?? '',
            'atcd_medicaux'                 => $consultation['atcd_medicaux']                ?? '',
            'atcd_chirurgicaux'             => $consultation['atcd_chirurgicaux']            ?? '',
            'atcd_familiaux'                => $consultation['atcd_familiaux']               ?? '',
            'atcd_allergies'                => $consultation['atcd_allergies']               ?? '',
            'atcd_toxicologiques'           => $consultation['atcd_toxicologiques']          ?? '',
            'atcd_medicamenteux'            => $consultation['atcd_medicamenteux']           ?? '',

            // Étape 1 — Enquête systémique
            'systeme_principal'             => $consultation['systeme_principal']            ?? '',
            'symptomes_systemiques'         => $consultation['symptomes_systemiques']        ?? '',
            'commentaires_systemiques'      => $consultation['commentaires_systemiques']     ?? '',

            // Étape 2 — Examen physique / constantes
            'tension_arterielle'            => $ta,
            'tension_systolique'            => $consultation['tension_systolique']           ?? '',
            'tension_diastolique'           => $consultation['tension_diastolique']          ?? '',
            'temperature'                   => $consultation['temperature']                  ?? '',
            'frequence_cardiaque'           => $consultation['frequence_cardiaque']          ?? '',
            'pouls'                         => $consultation['pouls']                        ?? '',
            'poids'                         => $consultation['poids']                        ?? '',
            'taille'                        => $consultation['taille']                       ?? '',
            'saturation'                    => $consultation['saturation']                   ?? '',
            'frequence_respiratoire'        => $consultation['frequence_respiratoire']       ?? '',
            'glycemie_capillaire'           => $consultation['glycemie_capillaire']          ?? '',
            'glycemie_type'                 => $consultation['glycemie_type']                ?? '',
            'ta_bras_gauche'                => $ta_gauche,
            'ta_bras_gauche_systolique'     => $consultation['ta_bras_gauche_systolique']    ?? '',
            'ta_bras_gauche_diastolique'    => $consultation['ta_bras_gauche_diastolique']   ?? '',
            'ta_bras_droit'                 => $ta_droit,
            'ta_bras_droit_systolique'      => $consultation['ta_bras_droit_systolique']     ?? '',
            'ta_bras_droit_diastolique'     => $consultation['ta_bras_droit_diastolique']    ?? '',
            'tension_non_prenable'          => $consultation['tension_non_prenable']         ?? 0,
            'motif_tension_non_prenable'    => $consultation['motif_tension_non_prenable']   ?? '',
            'commentaires_parametres'       => $consultation['commentaires_parametres']      ?? '',
            'examen_physique'               => $consultation['examen_physique']              ?? '',
            'resume_syndromique'            => $consultation['resume_syndromique']           ?? '',

            // Étape 3 — Hypothèses diagnostiques
            'hypotheses_diagnostiques'      => $consultation['hypotheses_diagnostiques']     ?? '',
            'diagnostic_principal'          => $consultation['diagnostic_principal']         ?? '',
            'diagnostics_differentiels'     => $consultation['diagnostics_differentiels']    ?? '',

            // Étape 4 — Bilans / examens
            'examens_paracliniques'         => $consultation['examens_paracliniques']        ?? '',

            // Étape 5 — Traitement
            'plan_traitement'               => $consultation['plan_traitement']              ?? '',
            'traitement_non_medicamenteux'  => $consultation['traitement_non_medicamenteux'] ?? '',

            // Étape 6 — Surveillance
            'surveillance'                  => $consultation['surveillance']                 ?? '',

            // Étape 7 — Suivi
            'notes_suivi'                   => $consultation['notes_suivi']                  ?? '',
            'date_suivi'                    => $consultation['date_suivi']                   ?? '',
        ];

        // Récupérer l'ordonnance pharmacie existante (pour ne pas la perdre)
        try {
            $stmtOrd = $this->db->prepare(
                "SELECT id FROM ordonnances_pharmacie WHERE consultation_id = ? ORDER BY id DESC LIMIT 1"
            );
            $stmtOrd->execute([$id]);
            $ord = $stmtOrd->fetch(PDO::FETCH_ASSOC);
            if ($ord) {
                $_SESSION['consultation_temp']['ordonnance_id'] = $ord['id'];
            }
        } catch (Exception $e) {}

        // ── Consultation gynécologique → formulaire spécialisé ──────────────
        if (strtolower($typeConsultation) === 'gyneco') {
            $formulaireData = [];
            $formulaireId   = 0;
            try {
                // Chercher le formulaires_data lié via consultation_id stocké en JSON
                $stmtFD = $this->db->prepare("
                    SELECT id, data FROM formulaires_data
                    WHERE patient_id = ?
                      AND type_formulaire = 'consultation-gyneco'
                      AND JSON_EXTRACT(data, '$.consultation_id') = ?
                    LIMIT 1
                ");
                $stmtFD->execute([$consultation['patient_id'], $id]);
                $fdRow = $stmtFD->fetch(PDO::FETCH_ASSOC);

                if (!$fdRow) {
                    // Fallback : le plus récent pour ce patient + médecin auteur
                    $stmtFD2 = $this->db->prepare("
                        SELECT id, data FROM formulaires_data
                        WHERE patient_id = ? AND user_id = ?
                          AND type_formulaire = 'consultation-gyneco'
                        ORDER BY date_creation DESC LIMIT 1
                    ");
                    $stmtFD2->execute([$consultation['patient_id'], (int)($consultation['medecin_id'] ?? 0)]);
                    $fdRow = $stmtFD2->fetch(PDO::FETCH_ASSOC) ?: null;
                }

                if ($fdRow) {
                    $formulaireId   = (int)$fdRow['id'];
                    $formulaireData = json_decode($fdRow['data'], true) ?? [];
                }
            } catch (\Throwable $eFD) {
                error_log('[modifier] Chargement formulaire gynéco: ' . $eFD->getMessage());
            }

            // Injecter dans la session gynéco avec marqueurs d'édition
            $formulaireData['_edit_consultation_id'] = $id;
            $formulaireData['_edit_formulaire_id']   = $formulaireId;
            $_SESSION['consultation_gyneco_temp']    = $formulaireData;

            header('Location: ' . BASE_URL . 'formulaire/creer/consultation-gyneco/' . $consultation['patient_id']);
            exit;
        }

        // ── Formulaire standard 7 étapes ─────────────────────────────────────
        header('Location: ' . BASE_URL . 'consultation/formulaire?patient_id=' . $consultation['patient_id']
               . '&type=' . urlencode($typeConsultation) . '&etape=1');
        exit;
    }

    /**
     * Impression/signature de l'ordonnance par le médecin
     * Redirige vers PrescriptionController::print() qui gère signature + impression
     */
    public function imprimerOrdonnance($ordonnance_id) {
        header('Location: ' . BASE_URL . 'prescription/print?id=' . (int)$ordonnance_id);
        exit;
    }

    /**
     * Vue ordonnance côté pharmacien (lecture + vérification signature)
     * Route : consultation/imprimer-ordonnance/{id}
     */
    public function imprimerOrdonnancePharmacien($ordonnance_id) {
        require_once __DIR__ . '/../models/Prescription.php';
        require_once __DIR__ . '/../services/SignatureService.php';

        $prescriptionModel = new Prescription();
        $sigService        = new SignatureService();

        $prescription = $prescriptionModel->getById((int)$ordonnance_id);
        $medicaments  = $prescriptionModel->getMedicaments((int)$ordonnance_id);
        $hopital      = $prescriptionModel->getHopitalSettings();

        if (!$prescription) {
            header('Location: ' . BASE_URL . 'pharmacie?error=ordonnance_introuvable');
            exit;
        }

        if (!isset($prescription['recommandations'])) {
            $prescription['recommandations'] = $prescription['notes'] ?? '';
        }

        $sigInfo      = $sigService->getMedecinSignatureInfo((int)$prescription['medecin_id']);
        $prescription = array_merge($prescription, $sigInfo);
        $signatureDoc = $sigService->isDocumentSigned('ORDONNANCE', (int)$ordonnance_id);

        if (isset($prescription['nom'])    && !isset($prescription['patient_nom']))    $prescription['patient_nom']    = $prescription['nom'];
        if (isset($prescription['prenom']) && !isset($prescription['patient_prenom'])) $prescription['patient_prenom'] = $prescription['prenom'];

        $medicaments = array_map(function($m) {
            $m['medicament_nom'] = $m['nom_medicament'] ?? $m['nom'] ?? 'Médicament inconnu';
            $m['forme']     = $m['forme']     ?? '';
            $m['dosage']    = $m['dosage']    ?? '';
            $m['posologie'] = $m['posologie'] ?? '';
            $m['duree']     = $m['duree']     ?? '';
            return $m;
        }, $medicaments);

        require_once __DIR__ . '/../views/prescriptions/impression.php';
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
        if (ob_get_level()) ob_end_clean();
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
            return;
        }

        // Wrapper global pour retourner toute erreur PHP en JSON lisible
        try {

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

        // Enregistrement décision
        require_once __DIR__ . '/../services/HospitalisationService.php';
        HospitalisationService::enregistrerDecisionHospitalisation($consultation_id, $decision, $medecin_id, $justification);

        $redirect_url = BASE_URL . 'dashboard';

        $decisionsHosp = ['HOSPITALISATION', 'hospitalisation_urgente', 'hospitalisation_programmee', 'hospitalisation_recommandee'];
        if (in_array($decision, $decisionsHosp)) {
            // Déterminer le service de destination :
            // Si le médecin est en "consultations externes", on envoie aux urgences
            $medecin_service_id = $_SESSION['service_id'] ?? null;
            $medecin_service_nom = '';
            try {
                $row = $this->db->prepare("SELECT nom_service FROM services WHERE id = ?");
                $row->execute([$medecin_service_id]);
                $medecin_service_nom = strtolower($row->fetchColumn() ?: '');
            } catch (Exception $e) {}

            $is_consultation_externe = str_contains($medecin_service_nom, 'extern') ||
                                       str_contains($medecin_service_nom, 'consult');

            // Si pas de service destination fourni et médecin externe → chercher urgences
            if (!$service_dest_id) {
                if ($is_consultation_externe) {
                    try {
                        $rowU = $this->db->query(
                            "SELECT id FROM services WHERE LOWER(nom_service) LIKE '%urgence%' LIMIT 1"
                        );
                        $service_dest_id = $rowU->fetchColumn() ?: null;
                    } catch (Exception $e) {}
                } else {
                    $service_dest_id = $medecin_service_id;
                }
            }

            // Mettre à jour le statut patient
            try {
                $this->db->prepare(
                    "UPDATE patients SET statut_parcours = 'HOSPITALISE',
                     statut_hosp = 'A_HOSPITALISER', service_id = ? WHERE id = ?"
                )->execute([$service_dest_id, $patient_id]);
            } catch (Exception $e) { error_log("DecisionHosp update patient: " . $e->getMessage()); }

            // Si la destination est urgences, créer/mettre à jour urgences_patients
            // pour que la demande apparaisse sur le cockpit de l'infirmier urgences
            if ($service_dest_id) {
                try {
                    $srvNomRow = $this->db->prepare("SELECT nom_service FROM services WHERE id = ? LIMIT 1");
                    $srvNomRow->execute([$service_dest_id]);
                    $srvNom = strtolower($srvNomRow->fetchColumn() ?: '');
                    if (str_contains($srvNom, 'urgence')) {
                        // Vérifier si déjà dans urgences_patients
                        $existChk = $this->db->prepare("SELECT id FROM urgences_patients WHERE patient_id = ? AND statut NOT IN ('TERMINE','SORTI') LIMIT 1");
                        $existChk->execute([$patient_id]);
                        $existing = $existChk->fetch();
                        if ($existing) {
                            $this->db->prepare("UPDATE urgences_patients SET statut = 'HOSPITALISE', heure_prise_charge = NOW() WHERE id = ?")
                                     ->execute([$existing['id']]);
                        } else {
                            $this->db->prepare(
                                "INSERT INTO urgences_patients (patient_id, motif_admission, niveau_triage, heure_arrivee, medecin_id, statut)
                                 VALUES (?, ?, '3', NOW(), ?, 'HOSPITALISE')"
                            )->execute([$patient_id, $justification ?: 'Hospitalisation depuis consultation', $medecin_id]);
                        }
                    }
                } catch (Exception $e) { error_log("DecisionHosp urgences_patients: " . $e->getMessage()); }
            }

            // Si médecin externe → créer entrée urgences pour apparaître sur le tableau infirmier
            if ($is_consultation_externe && $service_dest_id) {
                try {
                    // Vérifier si déjà en urgences
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

            // Si un lit a été choisi par le médecin → créer l'hospitalisation directement
            // Sinon → le patient passe en statut A_HOSPITALISER et l'infirmier l'installe
            if ($lit_id && $service_dest_id) {
                try {
                    HospitalisationService::assignLitNurse($patient_id, $service_dest_id, $lit_id, $medecin_id);
                } catch (Exception $e) { error_log("DecisionHosp assignLit: " . $e->getMessage()); }
            }
            // Pas de lit choisi : aucune hospitalisation provisoire créée.
            // Le patient apparaît dans la liste "À Hospitaliser" de l'infirmier.

        } elseif ($decision === 'SORTIE') {
            try {
                $this->db->prepare(
                    "UPDATE patients SET statut_parcours = 'SORTI', statut_hosp = 'AUCUN' WHERE id = ?"
                )->execute([$patient_id]);
            } catch (Exception $e) {}
            $redirect_url = BASE_URL . 'patients/dossier/' . $patient_id;
        }

        echo json_encode(['success' => true, 'message' => 'Décision enregistrée', 'redirect' => $redirect_url]);

        } catch (\Throwable $e) {
            error_log('decisionHospitalisation ERROR: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
            echo json_encode([
                'success' => false,
                'message' => 'Erreur serveur : ' . $e->getMessage(),
                'file'    => basename($e->getFile()) . ':' . $e->getLine(),
            ]);
        }
    }

    /**
     * Transfert d'un patient vers un autre service avec sélection de lit
     */
    public function transfererPatient() {
        header('Content-Type: application/json');

        $patient_id      = $_POST['patient_id']     ?? null;
        $service_dest_id = $_POST['service_id']     ?? null;
        $lit_id          = $_POST['lit_id']         ?? null;
        $motif_transfert = $_POST['motif']          ?? 'Transfert inter-service';
        $infirmier_id    = $_SESSION['user_id']     ?? 1;

        if (!$patient_id || !$service_dest_id || !$lit_id) {
            echo json_encode(['success' => false, 'message' => 'Champs obligatoires manquants (patient, service, lit)']);
            return;
        }

        try {
            $this->db->beginTransaction();

            // 1. Clôturer l'hospitalisation active si elle existe
            $stmtH = $this->db->prepare(
                "SELECT id, lit_id FROM hospitalisations WHERE patient_id = ? AND statut = 'active' LIMIT 1"
            );
            $stmtH->execute([$patient_id]);
            $hosp_actuelle = $stmtH->fetch(PDO::FETCH_ASSOC);

            if ($hosp_actuelle) {
                $this->db->prepare(
                    "UPDATE hospitalisations SET statut = 'transfere', date_sortie = NOW(),
                     motif_sortie = ? WHERE id = ?"
                )->execute([$motif_transfert, $hosp_actuelle['id']]);

                // Libérer l'ancien lit
                if ($hosp_actuelle['lit_id']) {
                    $this->db->prepare(
                        "UPDATE lits SET statut = 'DISPONIBLE', occupied_by_patient_id = NULL, occupied_since = NULL WHERE id = ?"
                    )->execute([$hosp_actuelle['lit_id']]);
                }
            }

            // 2. Créer nouvelle hospitalisation dans le service destination
            require_once __DIR__ . '/../services/HospitalisationService.php';
            $hosp_id = HospitalisationService::assignLitNurse($patient_id, $service_dest_id, $lit_id, $infirmier_id);

            // 3. Enregistrer l'événement de transfert
            try {
                $this->db->prepare(
                    "INSERT INTO transferts_patients (patient_id, service_origine_id, service_destination_id,
                     lit_destination_id, hospitalisation_id, motif, infirmier_id, date_transfert)
                     VALUES (?, ?, ?, ?, ?, ?, ?, NOW())"
                )->execute([
                    $patient_id,
                    $_SESSION['service_id'] ?? null,
                    $service_dest_id,
                    $lit_id,
                    $hosp_id ?: null,
                    $motif_transfert,
                    $infirmier_id
                ]);
            } catch (Exception $e) {
                // Table transferts_patients peut ne pas exister, on ignore silencieusement
                error_log("transferts_patients insert ignoré: " . $e->getMessage());
            }

            // 4. Mise à jour service patient
            $this->db->prepare(
                "UPDATE patients SET service_id = ?, statut_parcours = 'HOSPITALISE' WHERE id = ?"
            )->execute([$service_dest_id, $patient_id]);

            $this->db->commit();

            // Récupérer le nom du lit/service pour la réponse
            $stmtInfo = $this->db->prepare(
                "SELECT l.nom_lit, s.nom_service as service_nom FROM lits l JOIN services s ON l.service_id = s.id WHERE l.id = ?"
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
            error_log("transfererPatient error: " . $e->getMessage());
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

    // 5. Redirection vers le dashboard (le tableau de bord du rôle de l'utilisateur)
    // Le dashboard adapte automatiquement la vue selon le rôle (infirmier consultant,
    // médecin, etc.) — c'est l'endroit logique pour reprendre son flux de travail.
    header('Location: ' . BASE_URL . 'dashboard?success=consultation_terminee');
    exit;
}

    /**
     * POST consultation/supprimer/{id}
     * Supprime une consultation et ses données liées (ordonnances, demandes labo/imagerie, formulaires).
     * Réservé au médecin créateur ou à un administrateur.
     */
    public function supprimer(int $id): void {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Méthode non autorisée.']);
            return;
        }

        $userId   = (int)($_SESSION['user_id']   ?? 0);
        $userRole = strtoupper($_SESSION['user_role'] ?? '');
        $isAdmin  = in_array($userRole, ['ADMIN', 'ADMINISTRATEUR', 'DIRECTEUR']);

        try {
            // Vérifier que la consultation existe
            $stmt = $this->db->prepare("SELECT id, patient_id, medecin_id FROM consultations WHERE id = ? LIMIT 1");
            $stmt->execute([$id]);
            $consultation = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$consultation) {
                echo json_encode(['success' => false, 'message' => 'Consultation introuvable.']);
                return;
            }

            // Seul le médecin créateur ou un admin peut supprimer
            if (!$isAdmin && (int)$consultation['medecin_id'] !== $userId) {
                echo json_encode(['success' => false, 'message' => 'Vous ne pouvez supprimer que vos propres consultations.']);
                return;
            }

            $patientId = (int)$consultation['patient_id'];

            $this->db->beginTransaction();

            // 1. Ordonnances pharmacie liées
            try {
                $this->db->prepare("DELETE FROM ordonnances_pharmacie WHERE consultation_id = ?")->execute([$id]);
            } catch (\Throwable $e) { /* table peut ne pas exister */ }

            // 2. Lignes d'ordonnance
            try {
                $this->db->prepare("DELETE FROM ordonnance_lignes WHERE consultation_id = ?")->execute([$id]);
            } catch (\Throwable $e) {}

            // 3. Demandes de laboratoire (et examens liés)
            try {
                $stmtD = $this->db->prepare("SELECT id FROM demandes_laboratoire WHERE consultation_id = ?");
                $stmtD->execute([$id]);
                $demandes = $stmtD->fetchAll(PDO::FETCH_COLUMN);
                foreach ($demandes as $dId) {
                    $this->db->prepare("DELETE FROM demande_examens WHERE demande_id = ?")->execute([$dId]);
                    try { $this->db->prepare("DELETE FROM demande_pieces_jointes WHERE demande_id = ?")->execute([$dId]); } catch (\Throwable $e) {}
                    try { $this->db->prepare("DELETE FROM patient_resultats_labo WHERE demande_id = ?")->execute([$dId]); } catch (\Throwable $e) {}
                }
                $this->db->prepare("DELETE FROM demandes_laboratoire WHERE consultation_id = ?")->execute([$id]);
            } catch (\Throwable $e) {}

            // 4. Demandes d'imagerie
            try {
                $this->db->prepare("DELETE FROM demandes_imagerie WHERE consultation_id = ?")->execute([$id]);
            } catch (\Throwable $e) {}

            // 5. Formulaires data
            try {
                $this->db->prepare("DELETE FROM formulaires_data WHERE data->>'$.consultation_id' = ?")->execute([$id]);
            } catch (\Throwable $e) {}

            // 6. Bulletins d'examens
            try {
                $this->db->prepare("DELETE FROM bulletins_examens WHERE consultation_id = ?")->execute([$id]);
            } catch (\Throwable $e) {}

            // 7. Constantes / mesures liées
            try {
                $this->db->prepare("DELETE FROM constantes_vitales WHERE consultation_id = ?")->execute([$id]);
            } catch (\Throwable $e) {}

            // 8. Notes de suivi
            try {
                $this->db->prepare("DELETE FROM notes_suivi WHERE consultation_id = ?")->execute([$id]);
            } catch (\Throwable $e) {}

            // 9. Supprimer la consultation elle-même
            $this->db->prepare("DELETE FROM consultations WHERE id = ?")->execute([$id]);

            $this->db->commit();

            echo json_encode([
                'success'    => true,
                'message'    => 'Consultation supprimée avec succès.',
                'patient_id' => $patientId,
            ]);

        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            error_log('[ConsultationSupprimer] ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Erreur : ' . $e->getMessage()]);
        }
    }

    private function autoCreerBulletinLabo(int $consultation_id, int $patient_id): void {
        try {
            // Stratégie 1 : via demande_examens (jointure standard)
            $stmt = $this->db->prepare("
                SELECT dl.id, GROUP_CONCAT(el.nom ORDER BY el.nom SEPARATOR '|||') as noms
                FROM demandes_laboratoire dl
                JOIN demande_examens de ON de.demande_id = dl.id
                JOIN examens_laboratoire el ON el.id = de.examen_id
                WHERE dl.consultation_id = ?
                GROUP BY dl.id
                ORDER BY dl.date_creation ASC
            ");
            $stmt->execute([$consultation_id]);
            $demandes = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Stratégie 2 : via dl.examen_id direct (fallback)
            if (empty($demandes)) {
                $stmt2b = $this->db->prepare("
                    SELECT dl.id, el.nom as noms
                    FROM demandes_laboratoire dl
                    JOIN examens_laboratoire el ON el.id = dl.examen_id
                    WHERE dl.consultation_id = ?
                    ORDER BY dl.date_creation ASC
                ");
                $stmt2b->execute([$consultation_id]);
                $demandes = $stmt2b->fetchAll(PDO::FETCH_ASSOC);
            }

            if (empty($demandes)) return;

            $allNoms = [];
            foreach ($demandes as $d) {
                foreach (explode('|||', $d['noms']) as $n) $allNoms[] = $n;
            }
            $allNoms = array_unique($allNoms);
            $premiereDemande = $demandes[0];
            $medecin_id = $_SESSION['user_id'] ?? 1;

            $stmt2 = $this->db->prepare("SELECT id FROM bulletins_examens WHERE consultation_id = ? AND type = 'laboratoire' LIMIT 1");
            $stmt2->execute([$consultation_id]);
            $existing = $stmt2->fetch();

            if ($existing) {
                $this->db->prepare("UPDATE bulletins_examens SET examens = ?, demande_id = ? WHERE id = ?")
                    ->execute([json_encode(array_values($allNoms)), $premiereDemande['id'], $existing['id']]);
            } else {
                $this->db->prepare("INSERT INTO bulletins_examens (consultation_id, patient_id, medecin_id, type, labo, examens, demande_id, statut) VALUES (?, ?, ?, 'laboratoire', 1, ?, ?, 'BROUILLON')")
                    ->execute([$consultation_id, $patient_id, $medecin_id, json_encode(array_values($allNoms)), $premiereDemande['id']]);
            }
        } catch (Exception $e) {
            error_log('[AutoBulletin] ' . $e->getMessage());
        }
    }
}
?>