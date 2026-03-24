<?php
class MockGoogleMeetService {
    public function createMeeting($data) {
        // Simulation d'une réunion Google Meet pour les tests
        $meetingId = 'meet-' . uniqid();
        $meetingUrl = "https://meet.google.com/{$meetingId}";
        
        // Log de la réunion simulée
        error_log("Réunion simulée créée: " . json_encode([
            'url' => $meetingUrl,
            'patient' => $data['summary'],
            'date' => $data['start'],
            'duration' => $data['duration']
        ]));
        
        return $meetingUrl;
    }
    
    public function getAuthUrl() {
        return '#'; // Pas d'authentification nécessaire en mode test
    }
}
?>