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

/* ============================================================================
FICHIER : app/services/Auth.php
SERVICE D'AUTHENTIFICATION ET GESTION DES ACCÈS PAR SERVICE ET PERMISSIONS
============================================================================ */

class Auth {
    private static $instance = null;
    private $db;

    private function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * ÉTAPE 1 : Vérification des identifiants
     * Vérifie les identifiants et retourne l'utilisateur sans ouvrir de session
     */
    public function loginStep1($identifiant, $password) {
        $stmt = $this->db->prepare("
            SELECT u.*, s.nom_service
            FROM users u
            LEFT JOIN services s ON u.service_id = s.id
            WHERE (u.username = :id_user OR u.email = :id_email) AND u.actif = 1
        ");
        $stmt->execute([':id_user' => $identifiant, ':id_email' => $identifiant]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            return $user;
        }
        return false;
    }

    /**
     * ÉTAPE 2 : Finalisation de la connexion
     * Initialise les variables de session après validation du service ou admin
     */
    public function finalizeLogin($user) {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }

        // Sécurité : Régénérer l'ID de session
        session_regenerate_id(true);

        $_SESSION['logged_in']           = true;
        $_SESSION['user_id']             = $user['id'];
        $_SESSION['username']            = $user['username'];
        $_SESSION['user_nom']            = $user['nom'];
        $_SESSION['user_prenom']         = $user['prenom'];
        $_SESSION['user_role']           = strtoupper($user['role']);
        $_SESSION['service_id']          = $user['service_id'];
        $_SESSION['nom_service']         = $user['nom_service'] ?? 'Administration';
        $_SESSION['specialite']          = strtolower(trim($user['specialite'] ?? ''));
        // Forçage changement MDP (positionné par l'admin)
        $_SESSION['must_change_password'] = !empty($user['must_change_password']);

        return true;
    }

    /**
     * Déconnexion complète
     */
    public function logout() {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION = array();
        session_destroy();
        header('Location: ' . BASE_URL . 'login');
        exit;
    }

    /**
     * Vérifie si l'utilisateur est connecté
     */
    public function isLoggedIn() {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
    }

    /**
     * Récupère le rôle en session
     */
    public function getUserRole() {
        return $this->isLoggedIn() ? $_SESSION['user_role'] : null;
    }

    /**
     * GESTION DES PERMISSIONS (Version Mise à jour)
     * Vérifie si le rôle a accès à un module.
     * Inclus une logique hiérarchique : admin > write > read
     */
    public function hasPermission($module, $permission = 'read') {
        if (!$this->isLoggedIn()) return false;

        $role = $_SESSION['user_role'];
        $permission = strtolower($permission);

        // 1. BYPASS TOTAL POUR L'ADMIN ET LE SUPER_ADMIN :
        if (in_array($role, ['ADMIN', 'ADMINISTRATEUR', 'SUPER_ADMIN'])) {
            return true;
        }

        // 1b. BYPASS POUR LES RÔLES NON ENCORE DANS L'ENUM role_permissions
        // Ces rôles ont des permissions codées en dur ici en attendant la migration SQL
        $hardcodedPermissions = [
            'SECRETAIRE_LABO' => [
                'dashboard'   => ['read'],
                'patients'    => ['read'],
                'laboratoire' => ['read', 'write'],
            ],
            'SECRETAIRE_SAU' => [
                'dashboard' => ['read'],
                'urgences'  => ['read', 'write'],
                'patients'  => ['read'],
            ],
            'SECRETAIRE_SPECIALISTE' => [
                'dashboard'   => ['read'],
                'specialiste' => ['read', 'write'],
                'patients'    => ['read'],
            ],
        ];

        if (isset($hardcodedPermissions[$role])) {
            $allowed = $hardcodedPermissions[$role][$module] ?? [];
            if (in_array($permission, $allowed) || in_array('write', $allowed)) {
                return true;
            }
            // Si la permission demandée est 'read' et que 'write' est accordé → OK
            if ($permission === 'read' && in_array('write', $allowed)) {
                return true;
            }
            // Module non listé dans les droits de ce rôle → refus direct (évite la query ENUM)
            return false;
        }

        // 2. LOGIQUE HIÉRARCHIQUE DES PERMISSIONS :
        // On vérifie si l'utilisateur possède :
        // - La permission exacte demandée
        // - OU la permission 'admin' sur ce module
        // - OU la permission 'write' alors qu'il demande une lecture 'read'
        $sql = "SELECT COUNT(*) as count FROM role_permissions
                WHERE role = :role AND module = :module AND
                (
                    permission = :permission
                    OR permission = 'admin'
                    OR (permission = 'write' AND :req_p = 'read')
                )";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':role'       => $role,
            ':module'     => $module,
            ':permission' => $permission,
            ':req_p'      => $permission
        ]);

        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'] > 0;
    }

    /**
     * Bloque l'accès et affiche une erreur 403 si la permission est manquante
     */
    public function requirePermission($module, $permission = 'read') {
        if (!$this->hasPermission($module, $permission)) {
            http_response_code(403);
            $error_cause = "Votre profil ne dispose pas de la permission « " . $permission
                . " » sur le module « " . $module . " ». "
                . "Cette autorisation est gérée par l'administrateur dans Permissions & Accès.";
            $error_details = [
                'Module'             => $module,
                'Permission requise' => strtoupper($permission),
            ];
            require __DIR__ . '/../views/errors/403.php';
            exit;
        }
    }

    // --- HELPERS DE RÔLES (Raccourcis) ---

    public function isAdmin() {
        $role = $this->getUserRole();
        return ($role === 'ADMIN' || $role === 'ADMINISTRATEUR');
    }

    public function isMedecin() {
        return $this->getUserRole() === 'MEDECIN';
    }

    public function isInfirmier() {
        return $this->getUserRole() === 'INFIRMIER';
    }

    public function isMajor() {
        return $this->getUserRole() === 'MAJOR';
    }

    public function isClinician() {
        return in_array($this->getUserRole(), ['MEDECIN', 'INFIRMIER', 'MAJOR', 'INFIRMIER_CONSULTANT']);
    }

    public function isSecretaire() {
        $role = $this->getUserRole();
        return ($role === 'SECRETAIRE' || $role === 'ACCUEIL');
    }

    public function isLaborantin() {
        return $this->getUserRole() === 'LABORANTIN';
    }

    public function isTechnicienLabo() {
        return in_array($this->getUserRole(), ['TECHNICIEN_LABO', 'LABORANTIN', 'ADMIN', 'ADMINISTRATEUR']);
    }

    public function isPharmacien() {
        return $this->getUserRole() === 'PHARMACIEN';
    }
}