<?php
class GoogleMeetService {
    private $config;
    
    public function __construct() {
        $this->config = require_once __DIR__ . '/../../config/google.php';
    }
    
    public function createMeeting($data) {
        $accessToken = $this->getAccessToken();
        
        if (!$accessToken) {
            return $this->redirectToAuth();
        }
        
        $event = [
            'summary' => $data['summary'],
            'description' => $data['description'],
            'start' => [
                'dateTime' => $data['start'],
                'timeZone' => 'Europe/Paris'
            ],
            'end' => [
                'dateTime' => date('c', strtotime($data['start'] . ' +' . $data['duration'] . ' minutes')),
                'timeZone' => 'Europe/Paris'
            ],
            'attendees' => array_map(function($email) {
                return ['email' => $email];
            }, $data['attendees']),
            'conferenceData' => [
                'createRequest' => [
                    'requestId' => uniqid(),
                    'conferenceSolutionKey' => [
                        'type' => 'hangoutsMeet'
                    ]
                ]
            ]
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://www.googleapis.com/calendar/v3/calendars/primary/events?conferenceDataVersion=1');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($event));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $accessToken,
            'Content-Type: application/json'
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200) {
            $eventData = json_decode($response, true);
            return $eventData['conferenceData']['entryPoints'][0]['uri'] ?? null;
        }
        
        return false;
    }
    
    private function getAccessToken() {
        return $_SESSION['google_access_token'] ?? null;
    }
    
    private function redirectToAuth() {
        header('Location: /auth/google');
        exit;
    }
    
    public function getAuthUrl() {
        $params = [
            'client_id' => $this->config['google']['client_id'],
            'redirect_uri' => $this->config['google']['redirect_uri'],
            'scope' => implode(' ', $this->config['google']['scopes']),
            'response_type' => 'code',
            'access_type' => 'offline'
        ];
        
        return 'https://accounts.google.com/o/oauth2/auth?' . http_build_query($params);
    }
}
?>