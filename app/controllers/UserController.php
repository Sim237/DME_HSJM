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
     * Suppression d'un utilisateur (Admin seulement)
     */
    public function delete() {
        header('Content-Type: application/json');
        $this->auth->requirePermission('utilisateurs', 'delete');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['success' => false]); exit; }

        $id = (int)($_POST['id'] ?? 0);
        if (!$id) { echo json_encode(['success' => false, 'message' => 'ID invalide']); exit; }

        // Interdit de se supprimer soi-même
        if ($id === (int)$_SESSION['user_id']) {
            echo json_encode(['success' => false, 'message' => 'Vous ne pouvez pas supprimer votre propre compte.']);
            exit;
        }

        try {
            $db = (new Database())->getConnection();
            // Vérifier que l'utilisateur existe
            $stmt = $db->prepare("SELECT nom, prenom FROM users WHERE id = ?");
            $stmt->execute([$id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$user) { echo json_encode(['success' => false, 'message' => 'Utilisateur introuvable.']); exit; }

            // Soft-delete : marquer comme inactif plutôt que supprimer
            $db->prepare("UPDATE users SET actif = 0 WHERE id = ?")->execute([$id]);
            $this->audit->logAction('DELETE', 'users', $id, null, "Désactivation: {$user['nom']} {$user['prenom']}");

            echo json_encode(['success' => true]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    /**
     * Réactivation d'un utilisateur désactivé
     */
    public function reactivate() {
        header('Content-Type: application/json');
        $this->auth->requirePermission('utilisateurs', 'write');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['success' => false]); exit; }

        $id = (int)($_POST['id'] ?? 0);
        if (!$id) { echo json_encode(['success' => false, 'message' => 'ID invalide']); exit; }

        try {
            $db = (new Database())->getConnection();
            $stmt = $db->prepare("SELECT nom, prenom FROM users WHERE id = ?");
            $stmt->execute([$id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$user) { echo json_encode(['success' => false, 'message' => 'Utilisateur introuvable.']); exit; }

            $db->prepare("UPDATE users SET actif = 1 WHERE id = ?")->execute([$id]);
            $this->audit->logAction('UPDATE', 'users', $id, null, "Réactivation: {$user['nom']} {$user['prenom']}");

            echo json_encode(['success' => true]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    /**
     * Suppression définitive d'un utilisateur (Admin seulement)
     */
    public function destroy() {
        header('Content-Type: application/json');
        $this->auth->requirePermission('utilisateurs', 'delete');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['success' => false]); exit; }

        $id = (int)($_POST['id'] ?? 0);
        if (!$id) { echo json_encode(['success' => false, 'message' => 'ID invalide']); exit; }

        if ($id === (int)$_SESSION['user_id']) {
            echo json_encode(['success' => false, 'message' => 'Impossible de supprimer votre propre compte.']);
            exit;
        }

        try {
            $db = (new Database())->getConnection();
            $stmt = $db->prepare("SELECT nom, prenom FROM users WHERE id = ?");
            $stmt->execute([$id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$user) { echo json_encode(['success' => false, 'message' => 'Utilisateur introuvable.']); exit; }

            // Désactiver les FK pour cette session uniquement, puis supprimer
            $db->exec("SET FOREIGN_KEY_CHECKS = 0");
            $db->prepare("DELETE FROM users WHERE id = ?")->execute([$id]);
            $db->exec("SET FOREIGN_KEY_CHECKS = 1");

            $this->audit->logAction('DELETE', 'users', $id, null, "Suppression définitive: {$user['nom']} {$user['prenom']}");
            echo json_encode(['success' => true]);
        } catch (PDOException $e) {
            $db->exec("SET FOREIGN_KEY_CHECKS = 1");
            echo json_encode(['success' => false, 'message' => 'Erreur : ' . $e->getMessage()]);
        }
        exit;
    }

    /**
     * Sauvegarde admin (Utilisée par la modale dans index.php)
     * Gère l'upload + redimensionnement des fichiers signature/cachet
     */
    public function save() {
        $this->auth->requirePermission('utilisateurs', 'write');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;

        ob_start();
        try {
            require_once __DIR__ . '/../services/SignatureService.php';
            $sigService = new SignatureService();

            $id   = $_POST['id'] ?? '';
            $role = $_POST['role'] ?? '';

            $data = [
                'id'         => $id,
                'nom'        => $_POST['nom'],
                'prenom'     => $_POST['prenom'],
                'username'   => $_POST['username'],
                'email'      => $_POST['email'] ?? '',
                'telephone'  => $_POST['telephone'] ?? '',
                'role'       => $role,
                'service_id' => $_POST['service_id'],
                'statut'     => $_POST['statut'] ?? 1,
            ];
            if (!empty($_POST['password'])) {
                $data['password'] = $_POST['password'];
            }

            // 1. Sauvegarde principale (sans les fichiers)
            $userId = $this->userModel->save($data);

            if (!$userId) {
                ob_clean();
                echo json_encode(['success' => false, 'message' => 'Erreur lors de la sauvegarde']);
                exit;
            }

            // ID réel : pour une création, c'est le lastInsertId ; pour un update, c'est l'id existant
            $realId = $id ? (int)$id : (int)$userId;

            // 2. Traitement des fichiers uploadés (tous rôles — un médecin peut aussi être admin)
            $db = (new Database())->getConnection();

            foreach (['signature', 'cachet'] as $type) {
                if (!empty($_FILES[$type]['size']) && $_FILES[$type]['error'] === UPLOAD_ERR_OK) {
                    $path = $sigService->uploadAndResize($_FILES[$type], $type, $realId);
                    if ($path) {
                        $col = $type . '_path';
                        $db->prepare("UPDATE users SET {$col} = ? WHERE id = ?")
                           ->execute([$path, $realId]);
                    }
                }
            }

            ob_clean();
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            ob_clean();
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }
}