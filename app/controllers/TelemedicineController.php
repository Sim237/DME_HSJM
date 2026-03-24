<?php
require_once __DIR__ . '/../models/Patient.php';
require_once __DIR__ . '/../services/MockGoogleMeetService.php'; // Mode test

class TelemedicineController {
    private $patientModel;
    private $googleMeetService;
    
    public function __construct() {
        $this->patientModel = new Patient();
        $this->googleMeetService = new MockGoogleMeetService(); // Mode test
    }
    
    public function index() {
        $patients = $this->patientModel->getAll();
        include __DIR__ . '/../views/telemedicine/index.php';
    }
    
    public function createMeeting() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $patientId = $_POST['patient_id'];
            $date = $_POST['date'];
            $time = $_POST['time'];
            $duration = $_POST['duration'] ?? 30;
            
            $patient = $this->patientModel->getById($patientId);
            
            if (!$patient) {
                $_SESSION['error'] = 'Patient non trouvé';
                header('Location: ' . BASE_URL . 'telemedicine');
                return;
            }
            
            $meetingData = [
                'summary' => 'Consultation télémédecine - ' . $patient['nom'] . ' ' . $patient['prenom'],
                'description' => 'Consultation médicale à distance',
                'start' => $date . 'T' . $time . ':00',
                'duration' => $duration,
                'attendees' => [$patient['email']]
            ];
            
            $meetingUrl = $this->googleMeetService->createMeeting($meetingData);
            
            if ($meetingUrl) {
                $_SESSION['success'] = 'Réunion Google Meet créée avec succès';
                header('Location: ' . BASE_URL . 'telemedicine?meeting_url=' . urlencode($meetingUrl));
            } else {
                $_SESSION['error'] = 'Erreur lors de la création de la réunion';
                header('Location: ' . BASE_URL . 'telemedicine');
            }
        }
    }
}
?>