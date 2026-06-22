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

require_once __DIR__ . '/../services/DataService.php';
require_once __DIR__ . '/../services/Auth.php';
require_once __DIR__ . '/../services/CsrfService.php';

class UnifiedController {
    protected $dataService;
    protected $auth;

    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $this->dataService = DataService::getInstance();
        $this->auth = Auth::getInstance();

        $currentController = get_class($this);

        if ($currentController !== 'AuthController' && !$this->auth->isLoggedIn()) {
            $loginUrl = (defined('BASE_URL') ? BASE_URL : '/dme_hospital/') . 'login';
            header('Location: ' . $loginUrl);
            exit;
        }

        // Validation CSRF sur toutes les requêtes POST authentifiées
        // AuthController gère son propre token (login form)
        if ($_SERVER['REQUEST_METHOD'] === 'POST'
            && $currentController !== 'AuthController'
            && !CsrfService::validate()
        ) {
            CsrfService::check(); // répond 403 et exit
        }
    }

    protected function requireRole($allowedRoles) {
        $userRole = $this->auth->getUserRole();

        // SUPER_ADMIN et ADMIN bypassent tous les contrôles de rôle ordinaires
        if (in_array($userRole, ['ADMIN', 'SUPER_ADMIN'])) return true;

        if (!in_array($userRole, $allowedRoles)) {
            http_response_code(403);
            $error_cause = "Cette section est réservée aux profils : "
                . implode(', ', $allowedRoles) . ". "
                . "Votre profil actuel ne figure pas dans cette liste.";
            $error_details = [
                'Profils autorisés' => implode(', ', $allowedRoles),
            ];
            require __DIR__ . '/../views/errors/403.php';
            exit;
        }
        return true;
    }

    /**
     * Réserve l'action au SUPER_ADMIN exclusivement.
     * ADMIN obtient 403 même s'il bypasse requireRole().
     */
    protected function requireSuperAdmin(): void {
        $userRole = $this->auth->getUserRole();
        if ($userRole !== 'SUPER_ADMIN') {
            $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
                   || str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')
                   || ($_SERVER['HTTP_CONTENT_TYPE'] ?? '') === 'application/json';
            http_response_code(403);
            if ($isAjax || str_starts_with($_SERVER['REQUEST_URI'] ?? '', '/') ) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['success' => false, 'message' => 'Action réservée au Super Administrateur.']);
            } else {
                $error_cause   = 'Cette action est réservée au Super Administrateur.';
                $error_details = [];
                require __DIR__ . '/../views/errors/403.php';
            }
            exit;
        }
    }

    protected function getPatientWithAllData($patient_id) {
        return $this->dataService->getPatientComplet($patient_id);
    }

    protected function render($view, $data = []) {
        // EXTR_SKIP : ne pas écraser les variables déjà définies ($this, $db, etc.)
        // Cela évite la pollution de scope si une clé data correspond à une variable système.
        extract($data, EXTR_SKIP);
        require_once __DIR__ . '/../views/' . $view . '.php';
    }
}