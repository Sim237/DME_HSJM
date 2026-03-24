<?php
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
            WHERE (u.username = :id OR u.email = :id) AND u.statut = 1
        ");
        $stmt->execute([':id' => $identifiant]);
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

        $_SESSION['logged_in']    = true;
        $_SESSION['user_id']      = $user['id'];
        $_SESSION['username']     = $user['username'];
        $_SESSION['user_nom']     = $user['nom'];
        $_SESSION['user_prenom']  = $user['prenom'];
        $_SESSION['user_role']    = strtoupper($user['role']); // On force en majuscules pour la cohérence
        $_SESSION['service_id']   = $user['service_id'];
        $_SESSION['nom_service']  = $user['nom_service'] ?? 'Administration';

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

        // 1. BYPASS TOTAL POUR L'ADMIN :
        // Gère les deux variantes possibles dans la base
        if ($role === 'ADMIN' || $role === 'ADMINISTRATEUR') {
            return true;
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
            require_once __DIR__ . '/../views/errors/403.php'; // Charge une vue 403 propre si elle existe
            // Si pas de vue, on affiche un message propre :
            die("<div style='text-align:center; padding:50px; font-family:sans-serif;'>
                    <h1 style='font-size:4rem; color:#dc3545;'>403 - Accès Refusé</h1>
                    <p style='font-size:1.2rem; color:#6c757d;'>Vous n'avez pas les droits nécessaires pour accéder au module : <b>" . htmlspecialchars($module) . "</b></p>
                    <a href='".BASE_URL."' style='color:#0d6efd; text-decoration:none; font-weight:bold;'>Retour au tableau de bord</a>
                 </div>");
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

    public function isSecretaire() {
        $role = $this->getUserRole();
        return ($role === 'SECRETAIRE' || $role === 'ACCUEIL');
    }

    public function isLaborantin() {
        return $this->getUserRole() === 'LABORANTIN';
    }

    public function isPharmacien() {
        return $this->getUserRole() === 'PHARMACIEN';
    }
}