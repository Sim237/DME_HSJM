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
             frequence_cardiaque, saturation_oxygene, date_mesure, user_id)
            VALUES (?, ?, ?, ?, ?, ?, NOW(), ?)
        ");

        $success = $stmt->execute([
            $p['patient_id'],
            $p['temperature'],
            $p['tension_sys'],
            $p['tension_dia'],
            $p['frequence_cardiaque'],
            $p['spo2'],
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
        $admission_id = $_POST['admission_id']; // ID de l'hospitalisation
        $patient_id   = $_POST['patient_id'];
        $type_soin    = $_POST['type_soin'];
        $description  = $_POST['description'];
        $date_prevue  = $_POST['date_prevue'];

        // 2. Requête SQL corrigée : on pointe vers hospitalisations
        // Vérifiez votre table soins_hospitalisation pour voir le nom exact de la colonne
        // Si la colonne s'appelle admission_id, il faut la relier à hospitalisations.id
        $stmt = $db->prepare("
            INSERT INTO soins_hospitalisation
            (admission_id, user_id_planificateur, type_soin, description, date_prevue, statut)
            VALUES (?, ?, ?, ?, ?, 'PLANIFIE')
        ");

        $success = $stmt->execute([
            $admission_id, // Cet ID doit correspondre à un ID valide dans la table "hospitalisations"
            $_SESSION['user_id'],
            $type_soin,
            $description,
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
public function checklist($plan_id) {
    $db = (new Database())->getConnection();

    // 1. Récupérer les informations du plan et du patient
    $stmtPatient = $db->prepare("SELECT p.nom, p.prenom, sp.date_plan
        FROM soins_planification sp
        JOIN patients p ON sp.patient_id = p.id
        WHERE sp.id = ?
    ");
    $stmtPatient->execute([$plan_id]);
    $patient = $stmtPatient->fetch(PDO::FETCH_ASSOC);

    // Sécurité si le plan n'existe pas
    if (!$patient) {
        die("Erreur : Plan de soins introuvable.");
    }

    // 2. Récupérer la liste des soins
    $stmtSoins = $db->prepare("SELECT * FROM soins_details WHERE plan_id = ? ORDER BY heure ASC");
    $stmtSoins->execute([$plan_id]);
    $soins = $stmtSoins->fetchAll(PDO::FETCH_ASSOC);

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
    // Sauvegarde en base de données...
    header('Location: ' . BASE_URL . 'patients/dossier/' . $_POST['patient_id'] . '?success=1');
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
                // Tentative via la table urgences_admissions
                $stmt = $db->prepare("SELECT patient_id FROM urgences_admissions WHERE id = ?");
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
            $db->prepare("UPDATE lits SET statut = 'OCCUPE', patient_id = ? WHERE id = ?")
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

        $stmt = $db->prepare("INSERT INTO soins_planification (patient_id, medecin_id, date_plan) VALUES (?, ?, CURDATE())");
        $stmt->execute([$_POST['patient_id'], $_SESSION['user_id']]);
        $plan_id = $db->lastInsertId();

        foreach($_POST['soins'] as $categorie => $data) {
            if (isset($data['heure'])) {
                foreach($data['heure'] as $index => $heure) {
                    if(!empty($heure) && !empty($data['desc'][$index])) {
                        $stmt = $db->prepare("INSERT INTO soins_details (plan_id, categorie, soin_description, heure) VALUES (?, ?, ?, ?)");
                        $stmt->execute([$plan_id, $categorie, $data['desc'][$index], $heure]);
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
public function executerSoins($plan_id) {
    $db = (new Database())->getConnection();

    // Récupérer les soins et les infos du patient
    $stmt = $db->prepare("SELECT sd.*, p.nom, p.prenom, p.dossier_numero, sp.id as plan_id
        FROM soins_details sd
        JOIN soins_planification sp ON sd.plan_id = sp.id
        JOIN patients p ON sp.patient_id = p.id
        WHERE sd.plan_id = ?
        ORDER BY sd.heure ASC
    ");
    $stmt->execute([$plan_id]);
    $soins = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$soins) {
        header('Location: ' . BASE_URL . 'dashboard?error=plan_vide');
        exit;
    }

    require_once __DIR__ . '/../views/hospitalisation/executer_soins.php';
}

/**
 * Enregistre les soins cochés en base de données
 */
public function validerExecution() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $db = (new Database())->getConnection();
        $soins_faits = $_POST['soins_faits'] ?? []; // IDs des cases cochées
        $infirmier_id = $_SESSION['user_id'];

        try {
            $db->beginTransaction();

            if (!empty($soins_faits)) {
                foreach ($soins_faits as $id_soin) {
                    $stmt = $db->prepare("UPDATE soins_details SET execute = 1, date_execution = NOW(), infirmier_id = ? WHERE id = ?");
                    $stmt->execute([$infirmier_id, $id_soin]);
                }
            }

            $db->commit();
            header('Location: ' . BASE_URL . 'dashboard?success=soins_termines');
        } catch (Exception $e) {
            $db->rollBack();
            die("Erreur : " . $e->getMessage());
        }
    }
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

    // Initialisation du tableau $patient utilisé par la vue (pour éviter les erreurs "Undefined variable $patient")
    $patient = [
        'id' => $dossier['patient_id'] ?? $patient_id,
        'nom' => $dossier['nom'] ?? 'Inconnu',
        'prenom' => $dossier['prenom'] ?? '',
        'dossier_numero' => $dossier['dossier_numero'] ?? '---',
        'date_naissance' => $dossier['date_naissance'] ?? '',
        'sexe' => $dossier['sexe'] ?? 'M'
    ];

    // Dans HospitalisationController.php, méthode suivi()
$stmtLast = $db->prepare("
    SELECT *,
           pression_arterielle_systolique as tension_sys,
           pression_arterielle_diastolique as tension_dia
    FROM patient_parametres
    WHERE patient_id = ?
    ORDER BY date_mesure DESC LIMIT 1
");

    // 2. Récupérer les dernières constantes (pour les cartes en haut de page)
    $stmtLast = $db->prepare("SELECT * FROM patient_parametres WHERE patient_id = ? ORDER BY date_mesure DESC LIMIT 1");
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

    // 4. Récupérer les soins (via l'ID d'admission si elle existe)
    $tous_les_soins = [];
    if (!empty($dossier['admission_id'])) {
        $stmtSoins = $db->prepare("SELECT * FROM soins_hospitalisation WHERE admission_id = ? ORDER BY date_prevue ASC");
        $stmtSoins->execute([$dossier['admission_id']]);
        $tous_les_soins = $stmtSoins->fetchAll(PDO::FETCH_ASSOC);
    }
    // 5. Chargement de la vue
    require_once __DIR__ . '/../views/hospitalisation/suivi.php';
}

}