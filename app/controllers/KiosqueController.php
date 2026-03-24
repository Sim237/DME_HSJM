<?php
class KiosqueController {
    private $db;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }
    
    public function index() {
        include __DIR__ . '/../views/kiosque/index.php';
    }
    
    public function checkin() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $patientId = $this->findPatient($_POST);
            
            if ($patientId) {
                $rdvId = $this->findRdvDuJour($patientId);
                
                $sql = "INSERT INTO kiosque_checkins (patient_id, rdv_id, kiosque_id) 
                        VALUES (?, ?, ?)";
                $stmt = $this->db->prepare($sql);
                $result = $stmt->execute([
                    $patientId,
                    $rdvId,
                    'KIOSQUE_01'
                ]);
                
                if ($result) {
                    $numeroFile = $this->getNumeroFile($patientId);
                    echo json_encode([
                        'success' => true,
                        'numero' => $numeroFile,
                        'message' => 'Check-in réussi'
                    ]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Erreur technique']);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Patient non trouvé']);
            }
        }
    }
    
    public function fileAttente() {
        $sql = "SELECT k.*, p.nom, p.prenom, r.date_rdv
                FROM kiosque_checkins k
                JOIN patients p ON k.patient_id = p.id
                LEFT JOIN patient_rdv r ON k.rdv_id = r.id
                WHERE DATE(k.heure_checkin) = CURDATE() AND k.statut != 'APPELE'
                ORDER BY k.heure_checkin";
        $stmt = $this->db->query($sql);
        $patients = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        include __DIR__ . '/../views/kiosque/file-attente.php';
    }
    
    public function appelPatient() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $checkinId = $_POST['checkin_id'];
            
            $sql = "UPDATE kiosque_checkins SET statut = 'APPELE' WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([$checkinId]);
            
            echo json_encode(['success' => $result]);
        }
    }
    
    private function findPatient($data) {
        $sql = "SELECT id FROM patients WHERE ";
        $params = [];
        
        if (!empty($data['nom']) && !empty($data['prenom'])) {
            $sql .= "nom LIKE ? AND prenom LIKE ?";
            $params = ['%' . $data['nom'] . '%', '%' . $data['prenom'] . '%'];
        } elseif (!empty($data['dossier_numero'])) {
            $sql .= "dossier_numero = ?";
            $params = [$data['dossier_numero']];
        } else {
            return null;
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $patient = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $patient ? $patient['id'] : null;
    }
    
    private function findRdvDuJour($patientId) {
        $sql = "SELECT id FROM patient_rdv 
                WHERE patient_id = ? AND DATE(date_rdv) = CURDATE() AND statut = 'CONFIRME'
                LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$patientId]);
        $rdv = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $rdv ? $rdv['id'] : null;
    }
    
    private function getNumeroFile($patientId) {
        $sql = "SELECT COUNT(*) as position FROM kiosque_checkins 
                WHERE DATE(heure_checkin) = CURDATE() AND statut != 'APPELE'
                AND heure_checkin <= (SELECT heure_checkin FROM kiosque_checkins 
                                     WHERE patient_id = ? AND DATE(heure_checkin) = CURDATE() 
                                     ORDER BY heure_checkin DESC LIMIT 1)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$patientId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result['position'];
    }
}
?>