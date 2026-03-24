<?php
require_once __DIR__ . '/UnifiedController.php';

class TelemedecineController extends UnifiedController {
    
    public function index() {
        $this->auth->requirePermission('consultations', 'read');
        
        $database = new Database();
        $db = $database->getConnection();
        
        $sql = "SELECT t.*, p.nom, p.prenom, u.nom as medecin_nom, u.prenom as medecin_prenom
                FROM telemedecine_sessions t
                JOIN patients p ON t.patient_id = p.id
                JOIN users u ON t.medecin_id = u.id
                WHERE t.statut IN ('planifiee', 'active')
                ORDER BY t.date_debut ASC";
        
        $stmt = $db->prepare($sql);
        $stmt->execute();
        $sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        require_once __DIR__ . '/../views/telemedecine/index.php';
    }
    
    public function createSession() {
        $this->auth->requirePermission('consultations', 'write');
        
        $room_id = 'room_' . uniqid();
        $data = [
            'consultation_id' => $_POST['consultation_id'],
            'medecin_id' => $_SESSION['user_id'],
            'patient_id' => $_POST['patient_id'],
            'room_id' => $room_id,
            'date_debut' => $_POST['date_debut']
        ];
        
        $database = new Database();
        $db = $database->getConnection();
        
        $sql = "INSERT INTO telemedecine_sessions (consultation_id, medecin_id, patient_id, room_id, date_debut)
                VALUES (:consultation_id, :medecin_id, :patient_id, :room_id, :date_debut)";
        
        $stmt = $db->prepare($sql);
        $success = $stmt->execute($data);
        
        if ($success) {
            $session_id = $db->lastInsertId();
            echo json_encode(['success' => true, 'session_id' => $session_id, 'room_id' => $room_id]);
        } else {
            echo json_encode(['success' => false]);
        }
    }
    
    public function joinRoom($session_id) {
        $database = new Database();
        $db = $database->getConnection();
        
        $sql = "SELECT * FROM telemedecine_sessions WHERE id = :id";
        $stmt = $db->prepare($sql);
        $stmt->execute([':id' => $session_id]);
        $session = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$session) {
            header('Location: ' . BASE_URL . 'telemedecine');
            return;
        }
        
        // Vérifier les permissions
        $user_id = $_SESSION['user_id'];
        if ($session['medecin_id'] != $user_id && $session['patient_id'] != $user_id && !$this->auth->isAdmin()) {
            header('Location: ' . BASE_URL . 'telemedecine');
            return;
        }
        
        // Marquer la session comme active
        if ($session['statut'] === 'planifiee') {
            $sql = "UPDATE telemedecine_sessions SET statut = 'active', date_debut = NOW() WHERE id = :id";
            $stmt = $db->prepare($sql);
            $stmt->execute([':id' => $session_id]);
        }
        
        require_once __DIR__ . '/../views/telemedecine/room.php';
    }
    
    public function endSession($session_id) {
        $database = new Database();
        $db = $database->getConnection();
        
        $sql = "UPDATE telemedecine_sessions 
                SET statut = 'terminee', date_fin = NOW(), 
                    duree_minutes = TIMESTAMPDIFF(MINUTE, date_debut, NOW())
                WHERE id = :id";
        
        $stmt = $db->prepare($sql);
        $success = $stmt->execute([':id' => $session_id]);
        
        echo json_encode(['success' => $success]);
    }
}
?>