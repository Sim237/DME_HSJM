<?php
require_once __DIR__ . '/../services/DataService.php';
require_once __DIR__ . '/../services/Auth.php';

class UnifiedController {
    protected $dataService;
    protected $auth;

    public function __construct() {
        // Sécurité : S'assurer que la session est démarrée
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $this->dataService = DataService::getInstance();
        $this->auth = Auth::getInstance();

        // On récupère le nom de la classe actuelle
        $currentController = get_class($this);

        // Si l'utilisateur n'est pas connecté ET qu'il n'est pas déjà sur l'AuthController
        if ($currentController !== 'AuthController' && !$this->auth->isLoggedIn()) {
            $loginUrl = (defined('BASE_URL') ? BASE_URL : '/dme_hospital/') . 'login';
            header('Location: ' . $loginUrl);
            exit;
        }
    }

    protected function requireRole($allowedRoles) {
        $userRole = $this->auth->getUserRole();

        if ($userRole === 'ADMIN') return true;

        if (!in_array($userRole, $allowedRoles)) {
            http_response_code(403);
            echo '<div style="text-align:center; padding:50px;"><h1>403 - Accès refusé</h1><p>Vous n\'avez pas les droits pour accéder à cette section.</p></div>';
            exit;
        }
        return true;
    }

    protected function getPatientWithAllData($patient_id) {
        return $this->dataService->getPatientComplet($patient_id);
    }

    protected function render($view, $data = []) {
        extract($data);
        require_once __DIR__ . '/../views/' . $view . '.php';
    }
}