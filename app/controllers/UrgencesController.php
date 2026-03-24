<?php
/* ============================================================================
FICHIER : app/controllers/UrgencesController.php
CONTRÔLEUR DE GESTION DU SERVICE D'ACCUEIL ET DES URGENCES (SAU) - VERSION COMPLÈTE
============================================================================ */

require_once __DIR__ . '/UnifiedController.php';
require_once __DIR__ . '/../services/AuditService.php';
require_once __DIR__ . '/../models/Patient.php';

class UrgencesController extends UnifiedController {
    private $db;
    private $patientModel;
    private $audit;

    public function __construct() {
        parent::__construct();
        $this->db = (new Database())->getConnection();
        $this->patientModel = new Patient();
        $this->audit = new AuditService();
    }

    /**
     * DASHBOARD PRINCIPAL (Big Board / Cockpit)
     * Gère l'affichage spécifique pour le Médecin et l'Infirmier des Urgences
     */
    public function index() {
        // 1. Vérification des permissions
        $this->auth->requirePermission('urgences', 'read');

        $userRole = $_SESSION['user_role'] ?? '';
        $serviceId = $_SESSION['service_id'] ?? 0;

        // 2. Requête Haute Densité pour le Cockpit
        // On récupère les patients non sortis du service Urgences
        // On inclut les dernières constantes et le compte des bilans labo non lus
        $sql = "SELECT ua.*, p.nom, p.prenom, p.dossier_numero, p.sexe, p.date_naissance,
                       u.nom as medecin_nom,
                       ut.niveau_gravite, ut.motif_plainte, ut.score_glasgow,
                       ut.tension_sys, ut.tension_dia, ut.pouls, ut.spo2, ut.temperature,
                       (SELECT COUNT(*) FROM patient_resultats_labo WHERE patient_id = p.id AND statut_validation = 'NON_LU') as nb_bilans_dispo
                FROM urgences_admissions ua
                JOIN patients p ON ua.patient_id = p.id
                LEFT JOIN users u ON ua.medecin_id = u.id
                LEFT JOIN urgences_triage ut ON ua.id = ut.admission_id
                WHERE ua.date_sortie IS NULL
                AND p.service_id = ?
                ORDER BY ua.niveau_priorite ASC, ua.date_entree ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$serviceId]);
        $admissions = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // 3. Récupération des Sparklines (Historique rapide des constantes pour les graphiques)
        foreach ($admissions as &$adm) {
            $stmtV = $this->db->prepare("SELECT temperature, tension_sys, pouls, date_mesure
                                        FROM patient_parametres
                                        WHERE patient_id = ?
                                        ORDER BY date_mesure ASC LIMIT 10");
            $stmtV->execute([$adm['patient_id']]);
            $adm['vitals_history'] = $stmtV->fetchAll(PDO::FETCH_ASSOC);
        }

        // 4. Statistiques de pilotage pour les compteurs du dashboard
        $stats = ['P1' => 0, 'P2' => 0, 'P3' => 0, 'waiting_med' => 0];
        foreach($admissions as $a) {
            if(strpos($a['niveau_priorite'] ?? '', 'P1') !== false) $stats['P1']++;
            if(strpos($a['niveau_priorite'] ?? '', 'P2') !== false) $stats['P2']++;
            if(strpos($a['niveau_priorite'] ?? '', 'P3') !== false) $stats['P3']++;
            if($a['statut_actuel'] == 'ATTENTE_MEDECIN') $stats['waiting_med']++;
        }

        // 5. Routage vers la vue Cockpit (Sans Sidebar géré par CSS/Layout)
        if ($userRole === 'MEDECIN') {
            require_once __DIR__ . '/../views/urgences/dashboard_medecin.php';
        } else {
            require_once __DIR__ . '/../views/urgences/index.php';
        }
    }

    /**
     * VUE ADMISSION MASSIVE (Plan Blanc / Catastrophe)
     */
    public function nouvelleAdmission() {
        $this->auth->requirePermission('urgences', 'write');
        require_once __DIR__ . '/../views/urgences/admission_rapide.php';
    }

    /**
     * TRAITEMENT DE L'ADMISSION MASSIVE (JSON via AJAX)
     */
    public function saveMassive() {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;

        $patients_data = json_decode($_POST['patients'], true);
        $serviceId = $_SESSION['service_id'];

        try {
            $this->db->beginTransaction();

            foreach ($patients_data as $p) {
                if (empty($p['nom']) && empty($p['is_inconnu'])) continue;

                // 1. Création Patient (Anonyme ou Nommé)
                $nom = ($p['is_inconnu'] ?? false) ? "X-INCONNU-" . strtoupper(substr(uniqid(), -5)) : strtoupper($p['nom']);
                $prenom = $p['prenom'] ?? "X";
                $age = (int)($p['age_approx'] ?? 30);
                $dob = (date('Y') - $age) . "-01-01";

                $stmtP = $this->db->prepare("INSERT INTO patients (nom, prenom, sexe, date_naissance, statut_parcours, service_id, created_at) VALUES (?, ?, ?, ?, 'URGENCES', ?, NOW())");
                $stmtP->execute([$nom, $prenom, $p['sexe'], $dob, $serviceId]);
                $patient_id = $this->db->lastInsertId();

                // 2. Création Admission Urgence
                $stmtA = $this->db->prepare("INSERT INTO urgences_admissions (patient_id, mode_arrivee, niveau_priorite, statut_actuel, infirmier_id, date_entree) VALUES (?, ?, ?, 'EN_ATTENTE_TRI', ?, NOW())");
                $stmtA->execute([$patient_id, $p['mode'] ?? 'SEUL', $p['priorite'] ?? 'P3-STABLE', $_SESSION['user_id']]);
            }

            $this->db->commit();
            $this->audit->logAction('CREATE', 'urgences_massive', 0, null, "Admission massive effectuée");
            echo json_encode(['success' => true]);

        } catch (Exception $e) {
            $this->db->rollBack();
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    /**
     * INTERFACE DE TRIAGE IAO
     */
    public function triage($admission_id) {
        $this->auth->requirePermission('urgences', 'write');

        $stmt = $this->db->prepare("SELECT ua.*, p.nom, p.prenom, p.date_naissance, p.sexe
                                    FROM urgences_admissions ua
                                    JOIN patients p ON ua.patient_id = p.id
                                    WHERE ua.id = ?");
        $stmt->execute([$admission_id]);
        $adm = $stmt->fetch(PDO::FETCH_ASSOC);

        $medecins = $this->db->query("SELECT id, nom FROM users WHERE role = 'MEDECIN' AND statut = 1")->fetchAll();

        require_once __DIR__ . '/../views/urgences/triage.php';
    }

    /**
     * SAUVEGARDE DU TRIAGE ET AFFECTATION MÉDECIN
     */
    public function saveTriage() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $this->db->beginTransaction();

                // 1. Enregistrement des données de triage
                $sql = "INSERT INTO urgences_triage (admission_id, score_glasgow, tension_sys, tension_dia, pouls, spo2, temperature, motif_plainte, niveau_gravite)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $this->db->prepare($sql)->execute([
                    $_POST['admission_id'], $_POST['gcs_total'], $_POST['sys'], $_POST['dia'],
                    $_POST['pouls'], $_POST['spo2'], $_POST['temp'], $_POST['motif'], $_POST['niveau_priorite']
                ]);

                // 2. Update admission : Passage en attente médecin et assignation
                $stmt = $this->db->prepare("UPDATE urgences_admissions SET
                    niveau_priorite = ?,
                    statut_actuel = 'ATTENTE_MEDECIN',
                    medecin_id = ?
                    WHERE id = ?");
                $stmt->execute([$_POST['niveau_priorite'], $_POST['medecin_id'], $_POST['admission_id']]);

                $this->db->commit();
                $this->audit->logAction('UPDATE', 'urgences_admissions', $_POST['admission_id'], null, "Triage terminé : " . $_POST['niveau_priorite']);
                header('Location: ' . BASE_URL . 'urgences?success=tri_valide');
            } catch (Exception $e) {
                $this->db->rollBack();
                die("Erreur de triage : " . $e->getMessage());
            }
        }
    }

    /**
     * TRANSFERT / SORTIE DES URGENCES
     */
    public function transferer() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $admission_id = $_POST['admission_id'];
            $target_service = $_POST['service_id'] ?? null;
            $decision = $_POST['decision']; // HOSPITALISATION ou SORTIE

            try {
                $this->db->beginTransaction();

                // 1. Clôture de l'épisode d'urgence
                $this->db->prepare("UPDATE urgences_admissions SET statut_actuel = 'TRANSFERE', date_sortie = NOW() WHERE id = ?")
                         ->execute([$admission_id]);

                // 2. Mise à jour du parcours patient
                if ($decision === 'HOSPITALISATION') {
                    $sqlP = "UPDATE patients SET statut_parcours = 'ATTENTE_HOSPITALISATION', service_id = ?, statut_hosp = 'A_HOSPITALISER'
                             WHERE id = (SELECT patient_id FROM urgences_admissions WHERE id = ?)";
                    $this->db->prepare($sqlP)->execute([$target_service, $admission_id]);
                } else {
                    $this->db->prepare("UPDATE patients SET statut_parcours = 'SORTI', statut_hosp = 'AUCUN' WHERE id = (SELECT patient_id FROM urgences_admissions WHERE id = ?)")
                             ->execute([$admission_id]);
                }

                $this->db->commit();
                $this->audit->logAction('UPDATE', 'patients', $admission_id, null, "Orientation post-urgence : $decision");
                header('Location: ' . BASE_URL . 'urgences?success=transfert');
            } catch (Exception $e) {
                $this->db->rollBack();
                die("Erreur transfert : " . $e->getMessage());
            }
        }
    }

    /**
     * ADMISSION UNIQUE RAPIDE (Via Modale Dashboard)
     */
    public function saveSingle() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $serviceId = $_SESSION['service_id'];
            try {
                $this->db->beginTransaction();

                $patient_id = $_POST['patient_id'] ?? null;

                // Création si nouveau patient
                if (empty($patient_id)) {
                    $stmtP = $this->db->prepare("INSERT INTO patients (nom, prenom, sexe, statut_parcours, service_id, created_at) VALUES (?, 'X', 'M', 'URGENCES', ?, NOW())");
                    $stmtP->execute([strtoupper($_POST['nom']), $serviceId]);
                    $patient_id = $this->db->lastInsertId();
                }

                // Création admission
                $stmtA = $this->db->prepare("INSERT INTO urgences_admissions (patient_id, mode_arrivee, box_id, infirmier_id, statut_actuel, date_entree) VALUES (?, ?, ?, ?, 'EN_ATTENTE_TRI', NOW())");
                $stmtA->execute([
                    $patient_id,
                    $_POST['mode_arrivee'] ?? 'SEUL',
                    $_POST['box'] ?? null,
                    $_SESSION['user_id']
                ]);

                $this->db->commit();
                $this->audit->logAction('CREATE', 'urgences_admissions', $this->db->lastInsertId(), null, "Admission unique rapide");
                header('Location: ' . BASE_URL . 'urgences?success=admission_ok');
            } catch (Exception $e) {
                $this->db->rollBack();
                die("Erreur d'admission : " . $e->getMessage());
            }
        }
    }
}