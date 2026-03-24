<?php

require_once __DIR__ . '/../services/TelemedecinService.php';

class TelemedecinController {
    private $telemedecinService;
    
    public function __construct() {
        $this->telemedecinService = new TelemedecinService();
    }
    
    public function index() {
        $consultations = $this->telemedecinService->getConsultationsJour($_SESSION['user_id'] ?? null);
        $alertes = $this->telemedecinService->getAlertes($_SESSION['user_id'] ?? null);
        
        include __DIR__ . '/../views/telemedecine/dashboard.php';
    }
    
    public function planifier() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $result = $this->telemedecinService->planifierConsultation(
                $_POST['patient_id'],
                $_SESSION['user_id'],
                $_POST['type'],
                $_POST['date_consultation'],
                $_POST['motif']
            );
            
            if ($result) {
                header('Location: ' . BASE_URL . 'telemedecine?success=consultation_planifiee');
                exit;
            }
        }
        
        include __DIR__ . '/../views/telemedecine/planifier.php';
    }
    
    public function consultation($id) {
        // Démarrer la consultation
        $this->telemedecinService->demarrerConsultation($id);
        
        include __DIR__ . '/../views/telemedecine/consultation.php';
    }
    
    public function surveillance() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $result = $this->telemedecinService->ajouterSurveillance(
                $_POST['patient_id'],
                $_SESSION['user_id'],
                $_POST['type_donnee'],
                $_POST['valeur'],
                $_POST['unite'],
                $_POST['date_mesure']
            );
            
            echo json_encode(['success' => $result]);
            return;
        }
        
        include __DIR__ . '/../views/telemedecine/surveillance.php';
    }
    
    public function terminer() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $result = $this->telemedecinService->terminerConsultation(
                $_POST['consultation_id'],
                $_POST['diagnostic'],
                $_POST['prescription'] ?? null
            );
            
            echo json_encode(['success' => $result]);
        }
    }
}
?>