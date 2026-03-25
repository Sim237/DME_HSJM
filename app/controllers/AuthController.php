<?php
/* ============================================================================
 * FICHIER : app/controllers/AuthController.php
 * CONTRÔLEUR D'AUTHENTIFICATION ET DE ROUTAGE PAR SERVICE
 * ============================================================================ */

require_once __DIR__ . '/../services/Auth.php';
require_once __DIR__ . '/../services/AuditService.php';

class AuthController {
    private $auth;
    private $audit;

    public function __construct() {
        $this->auth  = Auth::getInstance();
        $this->audit = new AuditService();
    }

    /* -------------------------------------------------------------------------
     * ÉTAPE 1 : Connexion initiale
     * ---------------------------------------------------------------------- */
    public function login() {
        if ($this->auth->isLoggedIn()) {
            header('Location: ' . BASE_URL . 'dashboard');
            exit;
        }

        $error = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $identifiant = trim($_POST['identifiant'] ?? '');
            $password    = $_POST['password'] ?? '';

            $user = $this->auth->loginStep1($identifiant, $password);

            if ($user) {
                if ($user['role'] === 'ADMIN') {
                    $this->auth->finalizeLogin($user);
                    $this->audit->logAction('LOGIN', 'users', $user['id'], null, 'Connexion Administrateur');
                    header('Location: ' . BASE_URL . 'dashboard');
                } else {
                    $_SESSION['temp_user'] = $user;
                    header('Location: ' . BASE_URL . 'select-service');
                }
                exit;
            } else {
                $error = "Identifiants incorrects ou compte inactif.";
            }
        }

        require_once __DIR__ . '/../views/auth/login.php';
    }

    /* -------------------------------------------------------------------------
     * ÉTAPE 2 : Vue de sélection du service
     * ---------------------------------------------------------------------- */
    public function selectService() {
        if (!isset($_SESSION['temp_user'])) {
            header('Location: ' . BASE_URL . 'login');
            exit;
        }

        $db       = (new Database())->getConnection();
        $services = $db->query("SELECT * FROM services ORDER BY nom_service ASC")
                       ->fetchAll(PDO::FETCH_ASSOC);

        require_once __DIR__ . '/../views/auth/select_service.php';
    }

    /* -------------------------------------------------------------------------
     * ÉTAPE 3 : Vérification du service et redirection vers le bon dashboard
     * ---------------------------------------------------------------------- */
    public function verifyService() {
        if (!isset($_SESSION['temp_user'])) {
            header('Location: ' . BASE_URL . 'login');
            exit;
        }

        $selectedServiceId = (int) ($_POST['service_id'] ?? 0);
        $user              = $_SESSION['temp_user'];
        $db                = (new Database())->getConnection();

        // Récupérer le nom du service sélectionné
        $stmt = $db->prepare("SELECT nom_service FROM services WHERE id = ?");
        $stmt->execute([$selectedServiceId]);
        $serviceRes = $stmt->fetch(PDO::FETCH_ASSOC);
        $nomService = $serviceRes['nom_service'] ?? '';

        // Contrôle d'accès
        if ($user['service_id'] == $selectedServiceId || $user['role'] === 'ADMIN') {

            // Finalisation de la session
            $this->auth->finalizeLogin($user);
            $_SESSION['service_id']  = $selectedServiceId;
            $_SESSION['nom_service'] = $nomService;

            $this->audit->logAction(
                'LOGIN', 'users', $user['id'], null,
                "Connexion réussie au service : $nomService"
            );

            // Redirection selon le service
            $serviceKey = strtolower($nomService);

            if (stripos($serviceKey, 'accueil') !== false) {
                header('Location: ' . BASE_URL . 'accueil');

            } elseif (stripos($serviceKey, 'b1') !== false) {
                $_SESSION['bureau_id'] = 1;
                header('Location: ' . BASE_URL . 'parametres');

            } elseif (stripos($serviceKey, 'b2') !== false) {
                $_SESSION['bureau_id'] = 2;
                header('Location: ' . BASE_URL . 'parametres');

            } elseif (stripos($serviceKey, 'urgences') !== false) {
                header('Location: ' . BASE_URL . 'urgences');

            } elseif (stripos($serviceKey, 'laboratoire') !== false || $user['role'] === 'LABORANTIN') {
                header('Location: ' . BASE_URL . 'laboratoire');

            } elseif (stripos($serviceKey, 'pharmacie') !== false || $user['role'] === 'PHARMACIEN') {
                header('Location: ' . BASE_URL . 'pharmacie');

            } elseif (stripos($serviceKey, 'imagerie') !== false) {
                header('Location: ' . BASE_URL . 'imagerie');

            } else {
                header('Location: ' . BASE_URL . 'dashboard');
            }

            unset($_SESSION['temp_user']);
            exit;

        } else {
            // Accès refusé
            $this->audit->logAction(
                'LOGIN_ATTEMPT', 'users', $user['id'], null,
                "REFUSÉ : Tentative d'accès au service ID $selectedServiceId"
            );

            $error    = "Accès refusé : Vous n'êtes pas affecté à ce service.";
            $services = $db->query("SELECT * FROM services ORDER BY nom_service ASC")
                           ->fetchAll(PDO::FETCH_ASSOC);

            require_once __DIR__ . '/../views/auth/select_service.php';
        }
    }

    /* -------------------------------------------------------------------------
     * Déconnexion
     * ---------------------------------------------------------------------- */
    public function logout() {
        $userId = $_SESSION['user_id'] ?? null;

        if ($userId) {
            $this->audit->logAction('LOGOUT', 'users', $userId, null, 'Déconnexion manuelle');
        }

        $this->auth->logout();

        // ✅ Redirection vers la page de connexion après déconnexion
        header('Location: ' . BASE_URL . 'login');
        exit;
    }
}