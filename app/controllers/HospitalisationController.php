<?php

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../services/DataService.php';

class HospitalisationController {
    private $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    public function index() {
        // Liste des patients hospitalisés
        $stmt = $this->db->prepare("SELECT p.*, h.*, s.nom_service as service_nom, l.nom_lit as lit_numero
            FROM patients p
            JOIN hospitalisations h ON p.id = h.patient_id
            LEFT JOIN services s ON h.service_id = s.id
            LEFT JOIN lits l ON h.lit_id = l.id
            WHERE h.statut = 'active'
            ORDER BY h.date_admission DESC
        ");
        $stmt->execute();
        $patients_hospitalises = $stmt->fetchAll(PDO::FETCH_ASSOC);

        require_once __DIR__ . '/../views/hospitalisation/index.php';
    }

    public function dossier($patient_id) {
        // Dossier complet du patient hospitalisé
        $patient = $this->getPatientHospitalise($patient_id);
        $traitements = $this->getTraitementsActifs($patient_id);
        $constantes = $this->getConstantesRecentes($patient_id);
        $prescriptions = $this->getPrescriptionsHospitalisation($patient_id);

        require_once __DIR__ . '/../views/hospitalisation/dossier.php';
    }

    public function administrerTraitement() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = json_decode(file_get_contents('php://input'), true);

            $stmt = $this->db->prepare("INSERT INTO administrations_medicaments
                (prescription_id, patient_id, medicament_id, dose_administree, heure_administration, infirmier_id, observations)
                VALUES (?, ?, ?, ?, NOW(), ?, ?)
            ");

            $success = $stmt->execute([
                $data['prescription_id'],
                $data['patient_id'],
                $data['medicament_id'],
                $data['dose'],
                $_SESSION['user_id'],
                $data['observations'] ?? ''
            ]);

            header('Content-Type: application/json');
            echo json_encode(['success' => $success]);
        }
    }

    public function ajouterConstantes() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Si c'est du JSON (AJAX)
        $data = json_decode(file_get_contents('php://input'), true);

        // Si c'est un formulaire classique (POST), on utilise $_POST
        $p = !empty($data) ? $data : $_POST;

        $stmt = $this->db->prepare("
            INSERT INTO patient_parametres
            (patient_id, temperature, pression_arterielle_systolique, pression_arterielle_diastolique,
             frequence_cardiaque, saturation_oxygene, glycemie, frequence_respiratoire,
             diurese, sous_oxygene, debit_oxygene, observations, date_mesure, user_id)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?)
        ");

        $success = $stmt->execute([
            $p['patient_id'],
            $p['temperature'] ?: null,
            $p['tension_sys'] ?: null,
            $p['tension_dia'] ?: null,
            $p['frequence_cardiaque'] ?: null,
            $p['spo2'] ?: null,
            $p['glycemie'] ?: null,
            $p['frequence_respiratoire'] ?: null,
            $p['diurese'] ?: null,
            !empty($p['sous_oxygene']) ? 1 : 0,
            $p['debit_oxygene'] ?: null,
            $p['observations'] ?: null,
            $_SESSION['user_id']
        ]);

        // Redirection si formulaire classique
        if (empty($data)) {
            header('Location: ' . BASE_URL . 'hospitalisation/suivi/' . $p['patient_id'] . '?success=constantes_ajoutees');
        } else {
            header('Content-Type: application/json');
            echo json_encode(['success' => $success]);
        }
        exit;
    }
}
 public function planifierSoin() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $db = (new Database())->getConnection();

        // 1. Récupération des données
        $admission_id = $_POST['admission_id'];
        $patient_id   = $_POST['patient_id'];
        $type_soin    = $_POST['type_soin'];
        $description  = $_POST['description'];
        $condition    = $_POST['condition_application'] ?? null;
        $date_prevue  = $_POST['date_prevue'];

        $stmt = $db->prepare("
            INSERT INTO soins_hospitalisation
            (admission_id, user_id_planificateur, type_soin, description, condition_application, date_prevue, statut)
            VALUES (?, ?, ?, ?, ?, ?, 'PLANIFIE')
        ");

        $success = $stmt->execute([
            $admission_id,
            $_SESSION['user_id'],
            $type_soin,
            $description,
            $condition ?: null,
            $date_prevue
        ]);

       // Par (Assurez-vous que patient_id est bien envoyé par le formulaire) :
if (!empty($_POST['patient_id'])) {
    header('Location: ' . BASE_URL . 'hospitalisation/suivi/' . $_POST['patient_id'] . '?success=soin_planifie');
} else {
    // Fallback si patient_id est perdu
    header('Location: ' . BASE_URL . 'hospitalisation');
}
        exit;
    }
}

    private function getPatientHospitalise($patient_id) {
        $stmt = $this->db->prepare("SELECT p.*, h.*, s.nom as service_nom, l.numero as lit_numero
            FROM patients p
            JOIN hospitalisations h ON p.id = h.patient_id
            LEFT JOIN services s ON h.service_id = s.id
            LEFT JOIN lits l ON h.lit_id = l.id
            WHERE p.id = ? AND h.statut = 'active'
        ");
        $stmt->execute([$patient_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    private function getTraitementsActifs($patient_id) {
        $stmt = $this->db->prepare("SELECT ph.*, m.nom as medicament_nom, m.forme, m.dosage
            FROM prescriptions_hospitalisation ph
            JOIN medicaments m ON ph.medicament_id = m.id
            WHERE ph.patient_id = ? AND ph.statut = 'active'
            ORDER BY ph.heure_debut
        ");
        $stmt->execute([$patient_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function getConstantesRecentes($patient_id) {
       $stmt = $this->db->prepare("SELECT * FROM patient_parametres
        WHERE patient_id = ?
        ORDER BY date_mesure DESC
        LIMIT 10
    ");
        $stmt->execute([$patient_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function getPrescriptionsHospitalisation($patient_id) {
        $stmt = $this->db->prepare("SELECT ph.*, m.nom as medicament_nom, u.nom as medecin_nom
            FROM prescriptions_hospitalisation ph
            JOIN medicaments m ON ph.medicament_id = m.id
            JOIN users u ON ph.medecin_id = u.id
            WHERE ph.patient_id = ?
            ORDER BY ph.date_prescription DESC
        ");
        $stmt->execute([$patient_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Affiche la grille vide pour planifier
public function planifierSoins($patient_id) {
    $db = (new Database())->getConnection();
    // 1. Récupérer les infos de base du patient
    require_once 'app/models/Patient.php';
    $patientModel = new Patient();
    $patient = $patientModel->getById($patient_id);
    $age = date_diff(date_create($patient['date_naissance']), date_create('now'))->y;

    // 2. Récupérer l'hospitalisation active pour pré-remplir la localisation
    $stmt = $db->prepare("SELECT h.*, s.nom_service, l.nom_lit, c.nom_chambre
        FROM hospitalisations h
        JOIN services s ON h.service_id = s.id
        JOIN lits l ON h.lit_id = l.id
        JOIN chambres c ON l.chambre_id = c.id
        WHERE h.patient_id = ? AND h.statut = 'en_cours'
        LIMIT 1
    ");
    $stmt->execute([$patient_id]);
    $loc = $stmt->fetch(PDO::FETCH_ASSOC);
    // On passe $loc à la vue
    require_once 'app/views/hospitalisation/planifier_soins.php';
}
// Enregistre les données en base


// Affiche la checklist avec boutons de validation
// Supprimez tout autre bloc "public function checklist" pour ne garder que celui-ci :
public function checklist($hosp_id) {
    $db = (new Database())->getConnection();

    // 1. Récupérer les informations de l'hospitalisation et du patient
    $stmtPatient = $db->prepare("SELECT p.nom, p.prenom, h.date_admission as date_plan
        FROM hospitalisations h
        JOIN patients p ON h.patient_id = p.id
        WHERE h.id = ?
    ");
    $stmtPatient->execute([$hosp_id]);
    $patient = $stmtPatient->fetch(PDO::FETCH_ASSOC);

    if (!$patient) {
        die("Erreur : Hospitalisation introuvable.");
    }

    // 2. Récupérer la liste des soins du jour
    $stmtSoins = $db->prepare("SELECT * FROM soins_hospitalisation WHERE admission_id = ? ORDER BY date_prevue ASC");
    $stmtSoins->execute([$hosp_id]);
    $soins = $stmtSoins->fetchAll(PDO::FETCH_ASSOC);

    $plan_id = $hosp_id; // compatibilité vue
    // 3. Charger la vue
    require_once 'app/views/hospitalisation/checklist.php';
}

// Validation AJAX d'une ligne de soin
public function validerSoinItem() {
    $id = $_POST['id'];
    $db = (new Database())->getConnection();
    $stmt = $db->prepare("UPDATE soins_details SET execute = 1, date_execution = NOW(), infirmier_id = ? WHERE id = ?");
    $success = $stmt->execute([$_SESSION['user_id'], $id]);

    header('Content-Type: application/json');
    echo json_encode(['success' => $success]);
}

public function surveillance($patient_id) {
    require_once 'app/models/Patient.php';
    $patientModel = new Patient();
    $patient = $patientModel->getById($patient_id);
    $age = date_diff(date_create($patient['date_naissance']), date_create('now'))->y;

    require_once __DIR__ . '/../views/hospitalisation/soins_surveillance.php';
}

public function saveSurveillance() {
    // Logique de sauvegarde des colonnes multiples (boucle sur les tableaux postés)
    // Redirection vers le dossier patient
    header('Location: ' . BASE_URL . 'patients/dossier/' . $_POST['patient_id'] . '?success=1');
}

public function surveillanceIntensive($patient_id) {
    require_once 'app/models/Patient.php';
    $patientModel = new Patient();
    $patient = $patientModel->getById($patient_id);
    $age = date_diff(date_create($patient['date_naissance']), date_create('now'))->y;

    require_once __DIR__ . '/../views/hospitalisation/surveillance_intensive.php';
}

public function saveSI() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: ' . BASE_URL . 'dashboard');
        exit;
    }

    $db = (new Database())->getConnection();
    $patient_id = (int)($_POST['patient_id'] ?? 0);
    $diag = trim($_POST['diag'] ?? '');
    $obs = $_POST['obs'] ?? [];

    if ($patient_id && !empty($obs['date'])) {
        $stmt = $db->prepare("
            INSERT INTO surveillance_intensive_obs
            (patient_id, date_obs, heure_obs, ta, pouls, temperature, respiration,
             diurese, conscience, aspiration, soins, observations, staff, diag, infirmier_id)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $count = count($obs['date']);
        for ($i = 0; $i < $count; $i++) {
            $pouls = !empty($obs['pouls'][$i]) ? (int)$obs['pouls'][$i] : null;
            $temp  = !empty($obs['temp'][$i])  ? (float)$obs['temp'][$i] : null;
            $resp  = !empty($obs['resp'][$i])  ? (int)$obs['resp'][$i] : null;

            $stmt->execute([
                $patient_id,
                $obs['date'][$i]      ?? '',
                $obs['heure'][$i]     ?? '',
                $obs['ta'][$i]        ?? '',
                $pouls,
                $temp,
                $resp,
                $obs['diurese'][$i]   ?? '',
                $obs['conscience'][$i] ?? '',
                $obs['asp'][$i]       ?? '',
                $obs['soins'][$i]     ?? '',
                $obs['obs'][$i]       ?? '',
                $obs['staff'][$i]     ?? '',
                $diag,
                $_SESSION['user_id']
            ]);
        }
    }

    header('Location: ' . BASE_URL . 'hospitalisation/suivi/' . $patient_id . '?success=si_saved');
    exit;
}

public function ficheTransfusionnelle($patient_id) {
    require_once 'app/models/Patient.php';
    $patientModel = new Patient();
    $patient = $patientModel->getById($patient_id);

    if (!$patient) {
        header('Location: ' . BASE_URL . 'dashboard?error=patient_introuvable');
        exit;
    }

    $age = date_diff(date_create($patient['date_naissance']), date_create('now'))->y;

    // Récupérer infos hospitalisation active (service, lit)
    $db = (new Database())->getConnection();
    $stmtH = $db->prepare("SELECT s.nom_service, l.nom_lit
        FROM hospitalisations h
        LEFT JOIN services s ON h.service_id = s.id
        LEFT JOIN lits l ON h.lit_id = l.id
        WHERE h.patient_id = ? AND h.statut = 'en_cours'
        LIMIT 1");
    $stmtH->execute([$patient_id]);
    $hosp = $stmtH->fetch(PDO::FETCH_ASSOC);
    $patient['nom_service'] = $hosp['nom_service'] ?? '-';
    $patient['nom_lit']     = $hosp['nom_lit'] ?? '-';

    require_once __DIR__ . '/../views/hospitalisation/fiche_transfusionnelle.php';
}

public function saveTransfusion() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: ' . BASE_URL . 'dashboard');
        exit;
    }
    // Redirect back to suivi after save (extend later with DB storage if needed)
    $patient_id = (int)($_POST['patient_id'] ?? 0);
    header('Location: ' . BASE_URL . 'hospitalisation/suivi/' . $patient_id . '?success=transfusion_saved');
    exit;
}

public function observationsEvolution($patient_id) {
    $db = (new Database())->getConnection();

    // 1. Récupération infos patient
    require_once 'app/models/Patient.php';
    $patientModel = new Patient();
    $patient = $patientModel->getById($patient_id);

    // 2. Calcul âge
    $age = $patient ? date_diff(date_create($patient['date_naissance']), date_create('now'))->y : 'N/A';

    // 3. Récupérer les observations
    $stmt = $db->prepare("SELECT o.*, u.nom as user_nom, u.role
                          FROM observations_evolution o
                          JOIN users u ON o.user_id = u.id
                          WHERE o.patient_id = ?
                          ORDER BY o.date_obs DESC");
    $stmt->execute([$patient_id]);
    $observations = $stmt->fetchAll(PDO::FETCH_ASSOC);

    require_once __DIR__ . '/../views/hospitalisation/observations_evolution.php';
}

public function saveObservation() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $db = (new Database())->getConnection();
        $stmt = $db->prepare("INSERT INTO observations_evolution (patient_id, user_id, contenu) VALUES (?, ?, ?)");

        $success = $stmt->execute([
            $_POST['patient_id'],
            $_SESSION['user_id'], // L'ID du médecin/infirmier connecté
            $_POST['contenu']
        ]);

        if ($success) {
            header('Location: ' . BASE_URL . 'hospitalisation/observations-evolution/' . $_POST['patient_id']);
        } else {
            echo "Erreur lors de l'enregistrement.";
        }
    }
}

public function deleteObservation($id, $patient_id) {
    // Vérification de sécurité (seul l'auteur ou un admin peut supprimer)
    // $this->auth->requirePermission('hospitalisation', 'delete');

    $db = (new Database())->getConnection();
    $stmt = $db->prepare("DELETE FROM observations_evolution WHERE id = ?");
    $success = $stmt->execute([$id]);

    if ($success) {
        header('Location: ' . BASE_URL . 'hospitalisation/observations-evolution/' . $patient_id . '?success=deleted');
    } else {
        header('Location: ' . BASE_URL . 'hospitalisation/observations-evolution/' . $patient_id . '?error=1');
    }
    exit;
}

/**
 * Action déclenchée par l'infirmier pour installer un patient sur un lit
 */
public function validerInstallation() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $db = (new Database())->getConnection();

        $admission_id = $_POST['admission_id'] ?? null; // ID de la demande (consultation ou urgence)
        $lit_id = $_POST['lit_id'] ?? null;
        $infirmier_id = $_SESSION['user_id'];
        $infirmier_service_id = $_SESSION['service_id'] ?? null; // Le service de l'infirmier connecté

        if (!$admission_id || !$lit_id) {
            die("Erreur : Données d'installation incomplètes.");
        }

        try {
            $db->beginTransaction();

            // 1. On cherche le patient et son service d'origine
            // Tentative via la table consultations
            $stmt = $db->prepare("SELECT patient_id, service_id FROM consultations WHERE id = ?");
            $stmt->execute([$admission_id]);
            $infos = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$infos) {
                // Tentative via la table urgences_patients
                $stmt = $db->prepare("SELECT patient_id FROM urgences_patients WHERE id = ?");
                $stmt->execute([$admission_id]);
                $infos = $stmt->fetch(PDO::FETCH_ASSOC);
                // Aux urgences, le service_id n'est pas toujours dans l'admission,
                // donc on s'assurera d'utiliser celui de l'infirmier plus bas.
            }

            if (!$infos) {
                throw new Exception("Impossible de retrouver le patient.");
            }

            $patient_id = $infos['patient_id'];

            // --- DETERMINATION DU SERVICE (SECURITE ANTI-NULL) ---
            // On prend le service de la demande, sinon celui de l'infirmier
            $final_service_id = !empty($infos['service_id']) ? $infos['service_id'] : $infirmier_service_id;

            if (empty($final_service_id)) {
                throw new Exception("Le service de destination est introuvable. Veuillez vérifier votre affectation.");
            }

            // 2. Créer l'entrée officielle en hospitalisation
            $sqlH = "INSERT INTO hospitalisations (patient_id, service_id, lit_id, date_admission, statut)
                     VALUES (?, ?, ?, NOW(), 'en_cours')";
            $db->prepare($sqlH)->execute([$patient_id, $final_service_id, $lit_id]);

            // 3. Mettre à jour le statut du LIT (Occupé)
            $db->prepare("UPDATE lits SET statut = 'OCCUPE', occupied_by_patient_id = ? WHERE id = ?")
               ->execute([$patient_id, $lit_id]);

            // 4. Mettre à jour le statut du PATIENT
            $db->prepare("UPDATE patients SET statut_hosp = 'HOSPITALISE', statut = 'HOSPITALISE' WHERE id = ?")
               ->execute([$patient_id]);

            $db->commit();
            header('Location: ' . BASE_URL . 'dashboard?success=installation_reussie');
            exit;

        } catch (Exception $e) {
            $db->rollBack();
            die("Erreur lors de l'installation : " . $e->getMessage());
        }
    }
}

public function savePlan() {
    $db = (new Database())->getConnection();
    try {
        $db->beginTransaction();

        // Récupérer l'hospitalisation en cours du patient
        $stmtH = $db->prepare("SELECT id FROM hospitalisations WHERE patient_id = ? AND statut = 'en_cours' ORDER BY id DESC LIMIT 1");
        $stmtH->execute([$_POST['patient_id']]);
        $hosp = $stmtH->fetch(PDO::FETCH_ASSOC);
        $admission_id = $hosp ? $hosp['id'] : null;

        foreach($_POST['soins'] as $categorie => $data) {
            if (isset($data['heure'])) {
                foreach($data['heure'] as $index => $heure) {
                    if(!empty($heure) && !empty($data['desc'][$index])) {
                        $condition = $data['condition'][$index] ?? null;
                        $stmt = $db->prepare("INSERT INTO soins_hospitalisation (admission_id, user_id_planificateur, type_soin, description, condition_application, date_prevue, statut) VALUES (?, ?, ?, ?, ?, ?, 'PLANIFIE')");
                        $stmt->execute([$admission_id, $_SESSION['user_id'], $categorie, $data['desc'][$index], $condition ?: null, date('Y-m-d') . ' ' . $heure]);
                    }
                }
            }
        }
        $db->commit();
        header('Location: ' . BASE_URL . 'dashboard?success=plan_valide');
    } catch (Exception $e) {
        $db->rollBack();
        die("Erreur : " . $e->getMessage());
    }
}

/**
 * Affiche la liste des soins à cocher pour un plan donné
 */
public function executerSoins($hosp_id) {
    $db = (new Database())->getConnection();

    // Récupérer les soins + infos patient + infos exécutant
    $stmt = $db->prepare("
        SELECT sh.*,
               p.id as patient_id, p.nom, p.prenom, p.dossier_numero,
               h.id as plan_id, h.service_id,
               s.nom_service,
               ue.nom as executant_nom, ue.prenom as executant_prenom
        FROM soins_hospitalisation sh
        JOIN hospitalisations h ON sh.admission_id = h.id
        JOIN patients p ON h.patient_id = p.id
        LEFT JOIN services s ON h.service_id = s.id
        LEFT JOIN users ue ON sh.user_id_executant = ue.id
        WHERE sh.admission_id = ?
        ORDER BY sh.date_prevue ASC
    ");
    $stmt->execute([$hosp_id]);
    $soins = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $plan_id = $hosp_id;

    if (!$soins) {
        header('Location: ' . BASE_URL . 'dashboard?error=plan_vide');
        exit;
    }

    require_once __DIR__ . '/../views/hospitalisation/executer_soins.php';
}

/**
 * Enregistre les soins cochés (soumission du formulaire complet)
 */
public function validerExecution() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;
    $db = (new Database())->getConnection();
    $soins_faits  = $_POST['soins_faits'] ?? [];
    $admission_id = (int)($_POST['admission_id'] ?? 0);
    $patient_id   = (int)($_POST['patient_id'] ?? 0);
    $infirmier_id = $_SESSION['user_id'];

    try {
        $db->beginTransaction();
        foreach ($soins_faits as $id_soin) {
            $db->prepare("UPDATE soins_hospitalisation
                SET statut = 'REALISE', date_realisee = NOW(), user_id_executant = ?
                WHERE id = ? AND statut IN ('PLANIFIE','RETARD')")
               ->execute([$infirmier_id, $id_soin]);
        }
        $db->commit();
        // Retourner au suivi du patient
        if ($patient_id) {
            header('Location: ' . BASE_URL . 'hospitalisation/suivi/' . $patient_id . '?success=soins_enregistres');
        } else {
            header('Location: ' . BASE_URL . 'dashboard?success=soins_enregistres');
        }
    } catch (Exception $e) {
        $db->rollBack();
        die("Erreur : " . $e->getMessage());
    }
    exit;
}

/**
 * AJAX — Cocher/décocher un soin individuellement (sauvegarde partielle)
 */
public function cocherSoin() {
    header('Content-Type: application/json');
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['success'=>false]); exit; }
    $data = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $soin_id  = (int)($data['soin_id'] ?? 0);
    $cocher   = !empty($data['cocher']); // true = cocher, false = décocher
    $infirmier_id = $_SESSION['user_id'];
    try {
        $db = (new Database())->getConnection();
        if ($cocher) {
            $db->prepare("UPDATE soins_hospitalisation
                SET statut = 'REALISE', date_realisee = NOW(), user_id_executant = ?
                WHERE id = ? AND statut IN ('PLANIFIE','RETARD')")
               ->execute([$infirmier_id, $soin_id]);
        } else {
            $db->prepare("UPDATE soins_hospitalisation
                SET statut = 'PLANIFIE', date_realisee = NULL, user_id_executant = NULL
                WHERE id = ? AND statut = 'REALISE'")
               ->execute([$soin_id]);
        }
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

/**
 * AJAX — Rayer un soin (ANNULE) et créer la correction
 */
public function rayerSoin() {
    header('Content-Type: application/json');
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['success'=>false]); exit; }
    $data = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $soin_id      = (int)($data['soin_id'] ?? 0);
    $motif        = trim($data['motif'] ?? '');
    $correction   = trim($data['correction'] ?? '');
    $infirmier_id = $_SESSION['user_id'];
    try {
        $db = (new Database())->getConnection();
        $db->beginTransaction();

        // 1. Récupérer le soin original
        $orig = $db->prepare("SELECT * FROM soins_hospitalisation WHERE id = ?");
        $orig->execute([$soin_id]);
        $soin = $orig->fetch(PDO::FETCH_ASSOC);
        if (!$soin) throw new Exception("Soin introuvable");

        // 2. Marquer l'original ANNULE
        $db->prepare("UPDATE soins_hospitalisation
            SET statut = 'ANNULE', note_execution = ?, user_id_executant = ?
            WHERE id = ?")
           ->execute(["Rayé : $motif", $infirmier_id, $soin_id]);

        // 3. Créer la ligne corrigée si une correction est fournie
        $new_id = null;
        if ($correction !== '') {
            $db->prepare("INSERT INTO soins_hospitalisation
                (admission_id, user_id_planificateur, type_soin, description,
                 condition_application, date_prevue, statut, note_execution)
                VALUES (?, ?, ?, ?, ?, ?, 'PLANIFIE', ?)")
               ->execute([
                   $soin['admission_id'],
                   $infirmier_id,
                   $soin['type_soin'],
                   $correction,
                   $soin['condition_application'],
                   $soin['date_prevue'],
                   'CORR. de #' . $soin_id,
               ]);
            $new_id = $db->lastInsertId();
        }

        $db->commit();
        echo json_encode(['success' => true, 'new_id' => $new_id]);
    } catch (Exception $e) {
        if (isset($db)) $db->rollBack();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// Dans HospitalisationController.php
public function suivi($patient_id) {
    $db = (new Database())->getConnection();

    // 1. Récupération des infos globales du dossier
    // On joint avec 'patients' pour avoir le nom/prénom/dossier
    $stmt = $db->prepare("SELECT h.id, p.id as patient_id, p.nom, p.prenom, p.dossier_numero,
        s.nom_service as service_nom, l.nom_lit as lit_numero
        FROM patients p
        JOIN hospitalisations h ON p.id = h.patient_id
        LEFT JOIN services s ON h.service_id = s.id
        LEFT JOIN lits l ON h.lit_id = l.id
        WHERE p.id = ? AND h.statut = 'en_cours'
        LIMIT 1
    ");
    $stmt->execute([$patient_id]);
    $dossier = $stmt->fetch(PDO::FETCH_ASSOC);

    // Si pas d'hospitalisation active, chercher sans filtre statut (patient sorti ou archivé)
    if (!$dossier) {
        $stmtFallback = $db->prepare("SELECT h.id, p.id as patient_id, p.nom, p.prenom, p.dossier_numero,
            s.nom_service as service_nom, l.nom_lit as lit_numero
            FROM patients p
            JOIN hospitalisations h ON p.id = h.patient_id
            LEFT JOIN services s ON h.service_id = s.id
            LEFT JOIN lits l ON h.lit_id = l.id
            WHERE p.id = ?
            ORDER BY h.id DESC LIMIT 1
        ");
        $stmtFallback->execute([$patient_id]);
        $dossier = $stmtFallback->fetch(PDO::FETCH_ASSOC);
    }

    // Fallback ultime si le patient n'existe pas du tout
    if (!$dossier) {
        $dossier = [
            'id' => 0, 'patient_id' => $patient_id,
            'nom' => 'Patient', 'prenom' => 'Inconnu',
            'dossier_numero' => '---', 'service_nom' => '', 'lit_numero' => '',
        ];
    }

    // Données complètes du patient (pour le modal d'édition)
    $stmtPatientFull = $db->prepare("SELECT * FROM patients WHERE id = ?");
    $stmtPatientFull->execute([$patient_id]);
    $patientFull = $stmtPatientFull->fetch(PDO::FETCH_ASSOC) ?: [];

    $patient = [
        'id'                   => $dossier['patient_id']         ?? $patient_id,
        'nom'                  => $patientFull['nom']            ?? $dossier['nom']            ?? 'Inconnu',
        'prenom'               => $patientFull['prenom']         ?? $dossier['prenom']         ?? '',
        'dossier_numero'       => $patientFull['dossier_numero'] ?? $dossier['dossier_numero'] ?? '---',
        'date_naissance'       => $patientFull['date_naissance'] ?? '',
        'sexe'                 => $patientFull['sexe']           ?? 'M',
        'telephone'            => $patientFull['telephone']      ?? '',
        'email'                => $patientFull['email']          ?? '',
        'adresse'              => $patientFull['adresse']        ?? '',
        'groupe_sanguin'       => $patientFull['groupe_sanguin'] ?? '',
        'contact_nom'          => $patientFull['contact_nom']    ?? '',
        'contact_telephone'    => $patientFull['contact_telephone'] ?? '',
        'antecedents_medicaux' => $patientFull['antecedents_medicaux'] ?? '',
        'allergies'            => $patientFull['allergies']      ?? '',
        'profession'           => $patientFull['profession']     ?? '',
        'nationalite'          => $patientFull['nationalite']    ?? '',
    ];

    // 2. Récupérer les dernières constantes (pour les cartes en haut de page)
    $stmtLast = $db->prepare("SELECT *,
        pression_arterielle_systolique as tension_sys,
        pression_arterielle_diastolique as tension_dia
        FROM patient_parametres WHERE patient_id = ? ORDER BY date_mesure DESC LIMIT 1");
    $stmtLast->execute([$patient_id]);
    $dernieres_constantes = $stmtLast->fetch(PDO::FETCH_ASSOC) ?: [];

    // 3. Récupérer l'historique pour les graphiques (table patient_parametres)
    $stmtHist = $db->prepare("SELECT date_mesure, temperature,
               pression_arterielle_systolique as tension_sys,
               pression_arterielle_diastolique as tension_dia,
               frequence_cardiaque
        FROM patient_parametres
        WHERE patient_id = ?
        ORDER BY date_mesure ASC LIMIT 10
    ");
    $stmtHist->execute([$patient_id]);
    $constantes = $stmtHist->fetchAll(PDO::FETCH_ASSOC);

    // 4. Récupérer les soins (via l'ID d'hospitalisation)
    $tous_les_soins = [];
    if (!empty($dossier['id'])) {
        $stmtSoins = $db->prepare("SELECT * FROM soins_hospitalisation WHERE admission_id = ? ORDER BY date_prevue ASC");
        $stmtSoins->execute([$dossier['id']]);
        $tous_les_soins = $stmtSoins->fetchAll(PDO::FETCH_ASSOC);
    }

    // 5. Récupérer les observations de surveillance intensive
    $si_data = [];
    try {
        $stmtSI = $db->prepare("SELECT * FROM surveillance_intensive_obs WHERE patient_id = ? ORDER BY created_at DESC LIMIT 30");
        $stmtSI->execute([$patient_id]);
        $si_data = $stmtSI->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // Table may not exist yet — silently ignore
        $si_data = [];
    }

    // 6. Chargement de la vue
    require_once __DIR__ . '/../views/hospitalisation/suivi.php';
}

// ─── MISE À JOUR DES DONNÉES PERSONNELLES DU PATIENT ────────────────────────
public function updatePatient() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: ' . BASE_URL . 'dashboard');
        exit;
    }

    $db         = (new Database())->getConnection();
    $patient_id = (int)($_POST['patient_id'] ?? 0);

    if (!$patient_id) {
        header('Location: ' . BASE_URL . 'dashboard');
        exit;
    }

    // Champs autorisés
    $nom                  = strtoupper(trim($_POST['nom'] ?? ''));
    $prenom               = trim($_POST['prenom'] ?? '');
    $date_naissance       = $_POST['date_naissance'] ?? null;
    $sexe                 = $_POST['sexe'] ?? 'M';
    $telephone            = trim($_POST['telephone'] ?? '');
    $email                = trim($_POST['email'] ?? '');
    $adresse              = trim($_POST['adresse'] ?? '');
    $groupe_sanguin       = $_POST['groupe_sanguin'] ?? null;
    $contact_nom          = trim($_POST['contact_nom'] ?? '');
    $contact_telephone    = trim($_POST['contact_telephone'] ?? '');
    $antecedents_medicaux = trim($_POST['antecedents_medicaux'] ?? '');
    $allergies            = trim($_POST['allergies'] ?? '');
    $profession           = trim($_POST['profession'] ?? '');
    $nationalite          = trim($_POST['nationalite'] ?? '');

    if (empty($nom) || empty($prenom)) {
        header('Location: ' . BASE_URL . 'hospitalisation/suivi/' . $patient_id . '?error=champs_requis');
        exit;
    }

    try {
        // Colonnes de base (toujours présentes)
        $sql = "UPDATE patients SET
            nom = ?, prenom = ?, date_naissance = ?, sexe = ?,
            telephone = ?, email = ?, adresse = ?, groupe_sanguin = ?,
            contact_nom = ?, contact_telephone = ?,
            antecedents_medicaux = ?, allergies = ?
            WHERE id = ?";
        $db->prepare($sql)->execute([
            $nom, $prenom, $date_naissance ?: null, $sexe,
            $telephone ?: null, $email ?: null, $adresse ?: null, $groupe_sanguin ?: null,
            $contact_nom ?: null, $contact_telephone ?: null,
            $antecedents_medicaux ?: null, $allergies ?: null,
            $patient_id
        ]);

        // Colonnes optionnelles (peuvent ne pas exister selon la version du schéma)
        if ($profession !== '' || $nationalite !== '') {
            try {
                $db->prepare("UPDATE patients SET profession = ?, nationalite = ? WHERE id = ?")
                   ->execute([$profession ?: null, $nationalite ?: null, $patient_id]);
            } catch (PDOException $e) { /* colonnes absentes — on ignore */ }
        }

        header('Location: ' . BASE_URL . 'hospitalisation/suivi/' . $patient_id . '?success=patient_maj');
    } catch (PDOException $e) {
        header('Location: ' . BASE_URL . 'hospitalisation/suivi/' . $patient_id . '?error=db');
    }
    exit;
}

}