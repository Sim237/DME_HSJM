<?php
/**
 * SimCare+ — Dossier Médical Électronique (DME)
 * Copyright (c) 2024-2026 Franck Simeni. Tous droits réservés.
 * Développé pour la gestion hospitalière, et le bien être numérique des patients.
 *
 * Toute reproduction, modification ou distribution de ce logiciel,
 * en tout ou en partie, sans autorisation écrite préalable de l'auteur
 * est strictement interdite et constitue une contrefaçon.
 *
 * Protected under OAPI Agreement — Annexe VII · Berne Convention
 */


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