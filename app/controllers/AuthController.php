<?php
/* ============================================================================
FICHIER : app/controllers/AuthController.php
CONTRÔLEUR D'AUTHENTIFICATION ET DE ROUTAGE PAR SERVICE
============================================================================ */

require_once __DIR__ . '/../services/Auth.php';
require_once __DIR__ . '/../services/AuditService.php';

class AuthController {
    private $auth;
    private $audit;

    public function __construct() {
        $this->auth = Auth::getInstance();
        $this->audit = new AuditService();
    }

    /**
     * ÉTAPE 1 : Connexion initiale
     */
    public function login() {
        if ($this->auth->isLoggedIn()) {
            header('Location: ' . BASE_URL . 'dashboard');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $identifiant = $_POST['identifiant'] ?? '';
            $password = $_POST['password'] ?? '';

            // Vérification des identifiants (Step 1)
            $user = $this->auth->loginStep1($identifiant, $password);

            if ($user) {
                if ($user['role'] === 'ADMIN') {
                    // L'administrateur est connecté globalement
                    $this->auth->finalizeLogin($user);
                    $this->audit->logAction('LOGIN', 'users', $user['id'], null, 'Connexion Administrateur');
                    header('Location: ' . BASE_URL . 'dashboard');
                } else {
                    // Les autres personnels doivent confirmer leur service d'affectation
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

    /**
     * ÉTAPE 2 : Vue de sélection du service
     */
    public function selectService() {
        if (!isset($_SESSION['temp_user'])) {
            header('Location: ' . BASE_URL . 'login');
            exit;
        }
        $db = (new Database())->getConnection();
        $services = $db->query("SELECT * FROM services ORDER BY nom_service ASC")->fetchAll(PDO::FETCH_ASSOC);
        require_once __DIR__ . '/../views/auth/select_service.php';
    }

    /**
 * Vérifie le service sélectionné et redirige vers le dashboard approprié
 */
public function verifyService() {
    if (!isset($_SESSION['temp_user'])) {
        header('Location: ' . BASE_URL . 'login');
        exit;
    }

    $selectedServiceId = $_POST['service_id'] ?? 0;
    $user = $_SESSION['temp_user'];
    $db = (new Database())->getConnection();

    // 1. Récupérer le nom exact du service pour la logique de redirection
    $stmt = $db->prepare("SELECT nom_service FROM services WHERE id = ?");
    $stmt->execute([$selectedServiceId]);
    $serviceRes = $stmt->fetch(PDO::FETCH_ASSOC);
    $nomService = $serviceRes['nom_service'] ?? '';

    // 2. Contrôle d'accès : L'utilisateur appartient-il à ce service ou est-il ADMIN ?
    if ($user['service_id'] == $selectedServiceId || $user['role'] === 'ADMIN') {

        // Finalisation de la session utilisateur
        $this->auth->finalizeLogin($user);

        // Stockage des informations de service en session
        $_SESSION['service_id'] = $selectedServiceId;
        $_SESSION['nom_service'] = $nomService;
        $this->audit->logAction('LOGIN', 'users', $user['id'], null, "Connexion réussie au service : $nomService");

        // --- LOGIQUE DE REDIRECTION ET ATTRIBUTION DES BUREAUX ---
        $serviceKey = strtolower($nomService);

        if (stripos($serviceKey, 'accueil') !== false) {
            // Dashboard Accueil
            header('Location: ' . BASE_URL . 'accueil');
        }
        elseif (stripos($serviceKey, 'b1') !== false) {
            // Bureau 1 : On définit l'ID de bureau à 1 pour la logique PAIR
            $_SESSION['bureau_id'] = 1;
            header('Location: ' . BASE_URL . 'parametres');
        }
        elseif (stripos($serviceKey, 'b2') !== false) {
            // Bureau 2 : On définit l'ID de bureau à 2 pour la logique IMPAIR
            $_SESSION['bureau_id'] = 2;
            header('Location: ' . BASE_URL . 'parametres');
        }
        elseif (stripos($serviceKey, 'urgences') !== false) {
            header('Location: ' . BASE_URL . 'urgences');
        }
        else {
            // Dashboard standard (Médecine, Chirurgie, etc.)
            header('Location: ' . BASE_URL . 'dashboard');
        }

        unset($_SESSION['temp_user']); // Nettoyage
        exit;

    } else {
        // Tentative d'accès non autorisé
        $this->audit->logAction('LOGIN_ATTEMPT', 'users', $user['id'], null, "REFUSÉ : Tentative d'accès au service ID $selectedServiceId");
        $error = "Accès refusé : Vous n'êtes pas affecté à ce service.";

        // Rechargement de la liste des services pour la vue
        $services = $db->query("SELECT * FROM services ORDER BY nom_service ASC")->fetchAll(PDO::FETCH_ASSOC);
        require_once __DIR__ . '/../views/auth/select_service.php';
    }
}

    /**
     * Déconnexion
     */
    public function logout() {
        $userId = $_SESSION['user_id'] ?? null;
        if ($userId) {
            $this->audit->logAction('LOGOUT', 'users', $userId, null, 'Déconnexion manuelle');
        }
        $this->auth->logout();
    }
}