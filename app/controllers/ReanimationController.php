<?php
class ReanimationController {
    private $db;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }
    
    public function index() {
        $patients = $this->getPatientsReanimation();
        include __DIR__ . '/../views/reanimation/index.php';
    }
    
    public function monitoring($patientId) {
        $patient = $this->getPatientReanimation($patientId);
        $donnees = $this->getDonneesMonitoring($patientId);
        include __DIR__ . '/../views/reanimation/monitoring.php';
    }
    
    public function ajouterDonnees() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $sql = "INSERT INTO reanimation_monitoring 
                    (patient_rea_id, frequence_cardiaque, tension_sys, tension_dia, 
                     saturation_o2, temperature, frequence_respiratoire, glasgow) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([
                $_POST['patient_rea_id'],
                $_POST['frequence_cardiaque'],
                $_POST['tension_sys'],
                $_POST['tension_dia'],
                $_POST['saturation_o2'],
                $_POST['temperature'],
                $_POST['frequence_respiratoire'],
                $_POST['glasgow']
            ]);
            
            // Vérification des alertes
            $alertes = $this->verifierAlertes($_POST);
            
            echo json_encode(['success' => $result, 'alertes' => $alertes]);
        }
    }
    
    public function getDonneesTempsReel($patientId) {
        $sql = "SELECT * FROM reanimation_monitoring 
                WHERE patient_rea_id = ? 
                ORDER BY timestamp DESC LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$patientId]);
        $donnees = $stmt->fetch(PDO::FETCH_ASSOC);
        
        header('Content-Type: application/json');
        echo json_encode($donnees);
    }
    
    private function verifierAlertes($donnees) {
        $alertes = [];
        
        if ($donnees['frequence_cardiaque'] < 50 || $donnees['frequence_cardiaque'] > 120) {
            $alertes[] = ['type' => 'CRITIQUE', 'message' => 'Fréquence cardiaque anormale'];
        }
        
        if ($donnees['tension_sys'] < 90 || $donnees['tension_sys'] > 180) {
            $alertes[] = ['type' => 'CRITIQUE', 'message' => 'Tension artérielle critique'];
        }
        
        if ($donnees['saturation_o2'] < 90) {
            $alertes[] = ['type' => 'URGENTE', 'message' => 'Saturation en oxygène faible'];
        }
        
        if ($donnees['glasgow'] < 8) {
            $alertes[] = ['type' => 'CRITIQUE', 'message' => 'Score de Glasgow critique'];
        }
        
        return $alertes;
    }
    
    private function getPatientsReanimation() {
        $sql = "SELECT r.*, p.nom, p.prenom, p.date_naissance
                FROM reanimation_patients r
                JOIN patients p ON r.patient_id = p.id
                WHERE r.statut != 'SORTIE'
                ORDER BY r.date_admission DESC";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    private function getPatientReanimation($patientId) {
        $sql = "SELECT r.*, p.nom, p.prenom
                FROM reanimation_patients r
                JOIN patients p ON r.patient_id = p.id
                WHERE r.id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$patientId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    private function getDonneesMonitoring($patientId, $heures = 24) {
        $sql = "SELECT * FROM reanimation_monitoring 
                WHERE patient_rea_id = ? 
                AND timestamp >= DATE_SUB(NOW(), INTERVAL ? HOUR)
                ORDER BY timestamp DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$patientId, $heures]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>