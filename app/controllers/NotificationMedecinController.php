<?php

class NotificationMedecinController {
    private $notificationService;
    
    public function __construct() {
        require_once __DIR__ . '/../services/NotificationResultatService.php';
        $this->notificationService = new NotificationResultatService();
        if (session_status() === PHP_SESSION_NONE) session_start();
    }
    
    public function getNotifications() {
        $medecin_id = $_SESSION['user_id'] ?? 0;
        $notifications = $this->notificationService->getNotificationsMedecin($medecin_id);
        
        header('Content-Type: application/json');
        echo json_encode($notifications);
    }
    
    public function getCount() {
        $medecin_id = $_SESSION['user_id'] ?? 0;
        $notifications = $this->notificationService->getNotificationsMedecin($medecin_id, true);
        
        header('Content-Type: application/json');
        echo json_encode(['count' => count($notifications)]);
    }
    
    public function marquerLue() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $input = json_decode(file_get_contents('php://input'), true);
            $notification_id = $input['id'] ?? 0;
            
            $success = $this->notificationService->marquerLue($notification_id);
            
            header('Content-Type: application/json');
            echo json_encode(['success' => $success]);
        }
    }
    
    public function widget() {
        // Retourner le HTML du widget pour actualisation AJAX
        ob_start();
        include __DIR__ . '/../views/widgets/notifications-medecin.php';
        $html = ob_get_clean();
        
        header('Content-Type: text/html');
        echo $html;
    }
}