<?php
require_once __DIR__ . '/../models/Patient.php';

class PortailPatientController {
    private $db;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }
    
    public function login() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = $_POST['email'];
            $password = $_POST['password'];
            
            $sql = "SELECT pa.*, p.nom, p.prenom FROM patient_accounts pa 
                    JOIN patients p ON pa.patient_id = p.id 
                    WHERE pa.email = ? AND pa.is_active = 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$email]);
            $account = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($account && password_verify($password, $account['password_hash'])) {
                $_SESSION['patient_id'] = $account['patient_id'];
                $_SESSION['patient_name'] = $account['nom'] . ' ' . $account['prenom'];
                
                // Mise à jour dernière connexion
                $this->updateLastLogin($account['id']);
                
                header('Location: ' . BASE_URL . 'portail/dashboard');
            } else {
                $_SESSION['error'] = 'Email ou mot de passe incorrect';
                header('Location: ' . BASE_URL . 'portail/login');
            }
        } else {
            include __DIR__ . '/../views/portail/login.php';
        }
    }
    
    public function dashboard() {
        $this->checkAuth();
        $patientId = $_SESSION['patient_id'];
        
        $rdvs = $this->getProchainRdv($patientId);
        $traitements = $this->getTraitementsActifs($patientId);
        $rappels = $this->getRappelsDuJour($patientId);
        
        include __DIR__ . '/../views/portail/dashboard.php';
    }
    
    public function rdv() {
        $this->checkAuth();
        $patientId = $_SESSION['patient_id'];
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $sql = "INSERT INTO patient_rdv (patient_id, medecin_id, date_rdv, motif, notes_patient) 
                    VALUES (?, ?, ?, ?, ?)";
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([
                $patientId,
                $_POST['medecin_id'],
                $_POST['date_rdv'],
                $_POST['motif'],
                $_POST['notes_patient']
            ]);
            
            if ($result) {
                $_SESSION['success'] = 'Demande de RDV envoyée';
            }
        }
        
        $medecins = $this->getMedecins();
        $rdvs = $this->getRdvPatient($patientId);
        
        include __DIR__ . '/../views/portail/rdv.php';
    }
    
    public function dossier() {
        $this->checkAuth();
        $patientId = $_SESSION['patient_id'];
        
        $patientModel = new Patient();
        $patient = $patientModel->getById($patientId);
        $consultations = $patientModel->getConsultations($patientId, 10);
        $examens = $patientModel->getExamens($patientId, 10);
        
        include __DIR__ . '/../views/portail/dossier.php';
    }
    
    private function checkAuth() {
        if (!isset($_SESSION['patient_id'])) {
            header('Location: ' . BASE_URL . 'portail/login');
            exit;
        }
    }
    
    private function updateLastLogin($accountId) {
        $sql = "UPDATE patient_accounts SET last_login = NOW() WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$accountId]);
    }
    
    private function getProchainRdv($patientId) {
        $sql = "SELECT r.*, u.nom as medecin_nom, u.prenom as medecin_prenom
                FROM patient_rdv r
                JOIN users u ON r.medecin_id = u.id
                WHERE r.patient_id = ? AND r.date_rdv > NOW() AND r.statut != 'ANNULE'
                ORDER BY r.date_rdv LIMIT 3";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$patientId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    private function getTraitementsActifs($patientId) {
        $sql = "SELECT * FROM patient_traitements 
                WHERE patient_id = ? AND actif = 1 AND (date_fin IS NULL OR date_fin >= CURDATE())
                ORDER BY created_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$patientId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    private function getRappelsDuJour($patientId) {
        $sql = "SELECT * FROM patient_rappels 
                WHERE patient_id = ? AND DATE(date_rappel) = CURDATE()
                ORDER BY date_rappel";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$patientId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    private function getMedecins() {
        $sql = "SELECT id, nom, prenom, specialite FROM users WHERE role = 'MEDECIN' ORDER BY nom";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    private function getRdvPatient($patientId) {
        $sql = "SELECT r.*, u.nom as medecin_nom, u.prenom as medecin_prenom
                FROM patient_rdv r
                JOIN users u ON r.medecin_id = u.id
                WHERE r.patient_id = ?
                ORDER BY r.date_rdv DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$patientId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>