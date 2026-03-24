<?php
/* ============================================================================
   FICHIER : app/controllers/BlocController.php
   CONTRÔLEUR DE GESTION DU BLOC OPÉRATOIRE
   ============================================================================ */
require_once __DIR__ . '/UnifiedController.php';
require_once __DIR__ . '/../services/AuditService.php';

class BlocController extends UnifiedController {
    private $db;

    public function __construct() {
        parent::__construct();
        $database = new Database();
        $this->db = $database->getConnection();
    }

    /**
     * Dashboard Principal du Bloc
     */
    public function index() {
        $this->auth->requirePermission('laboratoire', 'read'); // Adapté selon vos permissions

        // 1. Récupérer l'état des salles
        $salles = $this->db->query("SELECT * FROM bloc_salles ORDER BY nom_salle ASC")->fetchAll(PDO::FETCH_ASSOC);

        // 2. Récupérer les interventions programmées pour aujourd'hui
        $queryInter = "SELECT bp.*, p.nom, p.prenom, u.nom as chirurgien_nom, bs.nom_salle as salle_nom, bd.statut
                       FROM bloc_programmation bp
                       JOIN bloc_demandes bd ON bp.demande_id = bd.id
                       JOIN patients p ON bd.patient_id = p.id
                       JOIN users u ON bd.chirurgien_id = u.id
                       JOIN bloc_salles bs ON bp.salle_id = bs.id
                       WHERE bp.date_intervention = CURDATE()
                       ORDER BY bp.heure_debut ASC";
        $interventions = $this->db->query($queryInter)->fetchAll(PDO::FETCH_ASSOC);

        // 3. Récupérer la file d'attente (Les patients envoyés via "A Opérer")
        $queryQueue = "SELECT bd.*, p.nom, p.prenom, p.dossier_numero, u.nom as chirurgien_nom
                       FROM bloc_demandes bd
                       JOIN patients p ON bd.patient_id = p.id
                       JOIN users u ON bd.chirurgien_id = u.id
                       WHERE bd.statut = 'EN_ATTENTE'
                       ORDER BY bd.date_demande ASC";
        $file_attente = $this->db->query($queryQueue)->fetchAll(PDO::FETCH_ASSOC);

        require_once __DIR__ . '/../views/bloc/index.php';
    }

    /**
     * Transmet le patient au service d'anesthésie (Bouton A Opérer)
     */
    public function transmettreDemande() {
        header('Content-Type: application/json');

        $patient_id = $_POST['patient_id'] ?? null;
        $chirurgien_id = $_SESSION['user_id'] ?? null;
        $anesth_id = $_POST['anesthesiste_id'] ?? null;

        if (!$patient_id || !$chirurgien_id) {
            echo json_encode(['success' => false, 'message' => 'Session expirée ou patient inconnu']);
            return;
        }

        try {
            $this->db->beginTransaction();
            $stmt1 = $this->db->prepare("UPDATE patients SET statut = 'A_OPERER' WHERE id = ?");
            $stmt1->execute([$patient_id]);

            $sql = "INSERT INTO bloc_demandes (patient_id, chirurgien_id, anesthesiste_id, statut, date_demande)
                    VALUES (?, ?, ?, 'EN_ATTENTE', NOW())";
            $stmt2 = $this->db->prepare($sql);
            $stmt2->execute([$patient_id, $chirurgien_id, $anesth_id]);

            $this->db->commit();
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            $this->db->rollBack();
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * Finalisation de la programmation (Attribution salle/heure)
     */
    public function programmerIntervention() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $sql = "INSERT INTO bloc_programmation (demande_id, salle_id, date_intervention, heure_debut, diagnostique_op)
                        VALUES (?, ?, ?, ?, ?)";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([
                    $_POST['demande_id'],
                    $_POST['salle_id'],
                    $_POST['date'],
                    $_POST['heure'],
                    $_POST['diagnostic']
                ]);

                // Mettre à jour le statut de la demande
                $this->db->prepare("UPDATE bloc_demandes SET statut = 'PROGRAMME' WHERE id = ?")
                         ->execute([$_POST['demande_id']]);

                header('Location: ' . BASE_URL . 'bloc?success=1');
            } catch (Exception $e) {
die("Erreur de programmation : " . $e->getMessage());
            }

            header('Location: ' . BASE_URL . 'bloc?success=1');
        }
    }

    /**
     * Monitoring Cockpit - Surveillance temps réel d'une intervention
     * GET: Affiche le cockpit pour l'intervention
     * POST: Ajoute des données de monitoring via AJAX (bloc/add-monitoring)
     */
    public function monitoring($intervention_id) {
        $this->auth->requirePermission('bloc', 'read');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            header('Content-Type: application/json');

            $bpm = $_POST['bpm'] ?? null;
            $tension = $_POST['tension'] ?? null;
            $temperature = $_POST['temperature'] ?? null;
            $spo2 = $_POST['spo2'] ?? null;
            $status = $_POST['status'] ?? 'stable';
            $notes = $_POST['notes'] ?? null;

            if (!$intervention_id || !$bpm) {
                echo json_encode(['success' => false, 'message' => 'Données incomplètes']);
                return;
            }

            try {
                $sql = "INSERT INTO bloc_monitoring (intervention_id, bpm, tension, temperature, spo2, status, notes, user_id, created_at)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([
                    $intervention_id,
                    $bpm,
                    $tension,
                    $temperature ?: null,
                    $spo2 ?: null,
                    $status,
                    $notes,
                    $_SESSION['user_id'] ?? null
                ]);

                echo json_encode(['success' => true]);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
            return;
        }

        // GET: Load data for cockpit view
        $query = "SELECT bp.*, p.nom as patient_nom, p.prenom as patient_prenom, p.dossier_numero,
                         p.age, p.sexe, u.nom as chirurgien, ua.nom as anesthesiste, bs.nom_salle,
                         bd.diagnostique, bp.statut
                  FROM bloc_programmation bp
                  JOIN bloc_demandes bd ON bp.demande_id = bd.id
                  JOIN patients p ON bd.patient_id = p.id
                  JOIN bloc_salles bs ON bp.salle_id = bs.id
                  LEFT JOIN users u ON bd.chirurgien_id = u.id
                  LEFT JOIN users ua ON bd.anesthesiste_id = ua.id
                  WHERE bp.id = ? AND bp.statut IN ('EN_COURS', 'PREPARE')";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$intervention_id]);
        $intervention = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$intervention) {
            http_response_code(404);
            die('Intervention invalide ou terminée.');
        }

        $stmt = $this->db->prepare("SELECT * FROM bloc_monitoring
                                    WHERE intervention_id = ?
                                    ORDER BY id DESC LIMIT 20");
        $stmt->execute([$intervention_id]);
        $recent_vitals = $stmt->fetchAll(PDO::FETCH_ASSOC);

        require_once __DIR__ . '/../views/bloc/monitoring_live.php';
    }
}
