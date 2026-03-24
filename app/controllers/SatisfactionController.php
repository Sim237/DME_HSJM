<?php
class SatisfactionController {
    private $db;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }
    
    public function enquete($patientId = null) {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $sql = "INSERT INTO satisfaction_enquetes 
                    (patient_id, consultation_id, note_globale, note_accueil, note_attente, 
                     note_medecin, commentaires, recommandation) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([
                $_POST['patient_id'],
                $_POST['consultation_id'] ?? null,
                $_POST['note_globale'],
                $_POST['note_accueil'],
                $_POST['note_attente'],
                $_POST['note_medecin'],
                $_POST['commentaires'],
                isset($_POST['recommandation']) ? 1 : 0
            ]);
            
            if ($result) {
                $_SESSION['success'] = 'Merci pour votre évaluation !';
                header('Location: ' . BASE_URL . 'satisfaction/merci');
                return;
            }
        }
        
        include __DIR__ . '/../views/satisfaction/enquete.php';
    }
    
    public function merci() {
        include __DIR__ . '/../views/satisfaction/merci.php';
    }
    
    public function dashboard() {
        $stats = $this->getStatsSatisfaction();
        $commentaires = $this->getDerniersCommentaires();
        
        include __DIR__ . '/../views/satisfaction/dashboard.php';
    }
    
    public function envoyerEnquete($patientId, $consultationId = null) {
        // Logique d'envoi automatique d'enquête par email/SMS
        $patient = $this->getPatient($patientId);
        
        if ($patient && $patient['email']) {
            $lien = BASE_URL . "satisfaction/enquete?patient=" . $patientId . "&consultation=" . $consultationId;
            
            $subject = "Votre avis nous intéresse - DME Hospital";
            $message = "
            <h2>Évaluez votre expérience</h2>
            <p>Bonjour {$patient['prenom']},</p>
            <p>Nous espérons que votre consultation s'est bien déroulée.</p>
            <p>Votre avis est important pour nous aider à améliorer nos services.</p>
            <p><a href='{$lien}' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Donner mon avis</a></p>
            <p>Merci de votre confiance.</p>
            ";
            
            // Envoi email (utiliser le service NotificationService existant)
            require_once __DIR__ . '/../services/NotificationService.php';
            $notificationService = new NotificationService();
            $notificationService->sendEmail($patient['email'], $subject, $message);
        }
    }
    
    private function getStatsSatisfaction() {
        $sql = "SELECT 
                    AVG(note_globale) as moyenne_globale,
                    AVG(note_accueil) as moyenne_accueil,
                    AVG(note_attente) as moyenne_attente,
                    AVG(note_medecin) as moyenne_medecin,
                    COUNT(*) as total_reponses,
                    SUM(CASE WHEN recommandation = 1 THEN 1 ELSE 0 END) as recommandations
                FROM satisfaction_enquetes 
                WHERE date_enquete >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
        
        $stmt = $this->db->query($sql);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    private function getDerniersCommentaires() {
        $sql = "SELECT s.*, p.nom, p.prenom 
                FROM satisfaction_enquetes s
                JOIN patients p ON s.patient_id = p.id
                WHERE s.commentaires IS NOT NULL AND s.commentaires != ''
                ORDER BY s.date_enquete DESC 
                LIMIT 10";
        
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    private function getPatient($patientId) {
        $sql = "SELECT nom, prenom, email FROM patients WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$patientId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>