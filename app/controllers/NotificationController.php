<?php
require_once __DIR__ . '/../services/NotificationService.php';
require_once __DIR__ . '/../models/Patient.php';

class NotificationController {
    private $notificationService;
    private $patientModel;
    
    public function __construct() {
        $this->notificationService = new NotificationService();
        $this->patientModel = new Patient();
    }
    
    public function sendReminder() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $patientId = $_POST['patient_id'];
            $type = $_POST['type'];
            
            $patient = $this->patientModel->getById($patientId);
            
            if (!$patient) {
                echo json_encode(['success' => false, 'message' => 'Patient non trouvé']);
                return;
            }
            
            switch ($type) {
                case 'appointment':
                    $appointment = [
                        'date_rdv' => $_POST['date_rdv'],
                        'type' => 'consultation'
                    ];
                    $this->notificationService->sendAppointmentReminder($patient, $appointment);
                    break;
                    
                case 'results':
                    $this->notificationService->sendResultsNotification($patient, []);
                    break;
            }
            
            echo json_encode(['success' => true, 'message' => 'Notification envoyée']);
        }
    }
    
    public function scheduleReminders() {
        $this->notificationService->scheduleReminders();
        echo json_encode(['success' => true, 'message' => 'Rappels programmés']);
    }
    
    public function testSMS() {
        if (isset($_GET['phone']) && isset($_GET['message'])) {
            $result = $this->notificationService->sendSMS($_GET['phone'], $_GET['message']);
            echo json_encode(['success' => $result]);
        }
    }
    
    public function testEmail() {
        if (isset($_GET['email']) && isset($_GET['subject']) && isset($_GET['message'])) {
            $result = $this->notificationService->sendEmail($_GET['email'], $_GET['subject'], $_GET['message']);
            echo json_encode(['success' => $result]);
        }
    }
}
?>