<?php
/* ============================================================================
   FICHIER : UserController.php
   ============================================================================ */
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/UnifiedController.php';
require_once __DIR__ . '/../services/AuditService.php';

class UserController extends UnifiedController {
    private $userModel;
    private $audit;

    public function __construct() {
        parent::__construct();
        $this->userModel = new User();
        $this->audit = new AuditService();
    }

    /**
     * Liste des utilisateurs (Admin seulement)
     */
    public function index() {
        $this->auth->requirePermission('utilisateurs', 'read');
        $users = $this->userModel->getAll();
        $db = (new Database())->getConnection();
        $services = $db->query("SELECT * FROM services ORDER BY nom_service ASC")->fetchAll(PDO::FETCH_ASSOC);
        require_once __DIR__ . '/../views/utilisateurs/index.php';
    }

    /**
     * Affiche le profil de l'utilisateur connecté (Celle qui manquait !)
     */
    public function profil() {
        // On récupère l'ID de l'utilisateur en session
        $user_id = $_SESSION['user_id'];

        // On va chercher ses informations complètes en base de données
        $user = $this->userModel->getById($user_id);

        if (!$user) {
            header('Location: ' . BASE_URL . 'dashboard?error=user_not_found');
            exit;
        }

        require_once __DIR__ . '/../views/utilisateurs/profil.php';
    }

    /**
     * Action pour que l'utilisateur mette à jour son propre profil
     */
    public function updateProfil() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $user_id = $_SESSION['user_id'];

            $data = [
                'id' => $user_id,
                'nom' => $_POST['nom'],
                'prenom' => $_POST['prenom'],
                'email' => $_POST['email'],
                'username' => $_SESSION['username'], // On ne change pas le login ici pour la sécurité
                'telephone' => $_POST['telephone'],
                'role' => $_SESSION['user_role'], // Garde le rôle actuel
                'service_id' => $_SESSION['service_id'], // Garde le service actuel
                'statut' => 1
            ];

            // Changement de mot de passe si rempli
            if (!empty($_POST['new_password'])) {
                $data['password'] = $_POST['new_password'];
            }

            if ($this->userModel->save($data)) {
                $this->audit->logAction('UPDATE', 'users', $user_id, null, 'Mise à jour du profil par l\'utilisateur');
                header('Location: ' . BASE_URL . 'profil?success=1');
            } else {
                header('Location: ' . BASE_URL . 'profil?error=1');
            }
            exit;
        }
    }

    /**
     * Sauvegarde admin (Utilisée par la modale dans index.php)
     */
    public function save() {
        $this->auth->requirePermission('utilisateurs', 'write');
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            ob_start();
            try {
                $id = $_POST['id'] ?? '';
                $data = [
                    'id' => $id,
                    'nom' => $_POST['nom'],
                    'prenom' => $_POST['prenom'],
                    'username' => $_POST['username'],
                    'email' => $_POST['email'],
                    'telephone' => $_POST['telephone'] ?? '',
                    'role' => $_POST['role'],
                    'service_id' => $_POST['service_id'],
                    'statut' => $_POST['statut'] ?? 1
                ];
                if (!empty($_POST['password'])) $data['password'] = $_POST['password'];

                $userId = $this->userModel->save($data);
                ob_clean();
                echo json_encode(['success' => (bool)$userId]);
            } catch (Exception $e) {
                ob_clean();
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
            exit;
        }
    }
}