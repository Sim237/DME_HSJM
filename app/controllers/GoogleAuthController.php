<?php
class GoogleAuthController {
    private $config;
    
    public function __construct() {
        $this->config = require_once __DIR__ . '/../../config/google.php';
    }
    
    public function redirect() {
        $params = [
            'client_id' => $this->config['google']['client_id'],
            'redirect_uri' => $this->config['google']['redirect_uri'],
            'scope' => implode(' ', $this->config['google']['scopes']),
            'response_type' => 'code',
            'access_type' => 'offline',
            'prompt' => 'consent'
        ];
        
        $url = 'https://accounts.google.com/o/oauth2/auth?' . http_build_query($params);
        header('Location: ' . $url);
        exit;
    }
    
    public function callback() {
        if (!isset($_GET['code'])) {
            $_SESSION['error'] = 'Autorisation Google refusée';
            header('Location: /telemedicine');
            exit;
        }
        
        $tokenData = $this->exchangeCodeForToken($_GET['code']);
        
        if ($tokenData) {
            $_SESSION['google_access_token'] = $tokenData['access_token'];
            $_SESSION['google_refresh_token'] = $tokenData['refresh_token'] ?? null;
            $_SESSION['success'] = 'Connexion Google réussie';
        } else {
            $_SESSION['error'] = 'Erreur lors de l\'authentification Google';
        }
        
        header('Location: /telemedicine');
        exit;
    }
    
    private function exchangeCodeForToken($code) {
        $data = [
            'client_id' => $this->config['google']['client_id'],
            'client_secret' => $this->config['google']['client_secret'],
            'redirect_uri' => $this->config['google']['redirect_uri'],
            'grant_type' => 'authorization_code',
            'code' => $code
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://oauth2.googleapis.com/token');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200) {
            return json_decode($response, true);
        }
        
        return false;
    }
}
?>