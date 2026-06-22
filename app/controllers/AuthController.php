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
 * FICHIER : app/controllers/AuthController.php
 * CONTRÔLEUR D'AUTHENTIFICATION ET DE ROUTAGE PAR SERVICE
 * ============================================================================ */

require_once __DIR__ . '/../services/Auth.php';
require_once __DIR__ . '/../services/AuditService.php';
require_once __DIR__ . '/../services/CsrfService.php';

class AuthController {
    private $auth;
    private $audit;

    // Cookie "Se souvenir de moi"
    private const REMEMBER_COOKIE   = 'smc_remember';
    private const REMEMBER_DURATION = 2592000; // 30 jours

    public function __construct() {
        $this->auth  = Auth::getInstance();
        $this->audit = new AuditService();
    }

    /* =========================================================================
     * HELPERS — Se souvenir de moi (cookie HMAC-SHA256, sans stockage DB)
     * ======================================================================= */

    private function rememberSecret(): string {
        return hash('sha256', (defined('DB_PASS') ? DB_PASS : '') . 'smc_cookie_salt_2026');
    }

    private function setRememberCookie(int $userId): void {
        $exp = time() + self::REMEMBER_DURATION;
        $sig = hash_hmac('sha256', $userId . ':' . $exp, $this->rememberSecret());
        $val = base64_encode($userId . ':' . $exp . ':' . $sig);
        setcookie(self::REMEMBER_COOKIE, $val, [
            'expires'  => $exp,
            'path'     => '/',
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }

    private function clearRememberCookie(): void {
        setcookie(self::REMEMBER_COOKIE, '', [
            'expires'  => time() - 3600,
            'path'     => '/',
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }

    private function checkRememberCookie(): ?array {
        if (empty($_COOKIE[self::REMEMBER_COOKIE])) return null;
        $raw = base64_decode($_COOKIE[self::REMEMBER_COOKIE], true);
        if (!$raw) return null;
        $parts = explode(':', $raw, 3);
        if (count($parts) !== 3) return null;
        [$uid, $exp, $sig] = $parts;
        if (time() > (int)$exp) return null;
        $expected = hash_hmac('sha256', $uid . ':' . $exp, $this->rememberSecret());
        if (!hash_equals($expected, $sig)) return null;

        $db   = (new Database())->getConnection();
        $stmt = $db->prepare(
            "SELECT u.*, s.nom_service FROM users u
             LEFT JOIN services s ON u.service_id = s.id
             WHERE u.id = ? AND u.actif = 1"
        );
        $stmt->execute([(int)$uid]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /* -------------------------------------------------------------------------
     * ÉTAPE 1 : Connexion initiale
     * ---------------------------------------------------------------------- */
    public function login() {
        // Auto-login via cookie remember-me
        if (!$this->auth->isLoggedIn()) {
            $remembered = $this->checkRememberCookie();
            if ($remembered) {
                $this->auth->finalizeLogin($remembered);
                if (in_array(strtoupper($remembered['role']), ['ADMIN', 'SUPER_ADMIN'])) {
                    header('Location: ' . BASE_URL . 'dashboard');
                } else {
                    $_SESSION['temp_user'] = $remembered;
                    header('Location: ' . BASE_URL . 'select-service');
                }
                exit;
            }
        }

        if ($this->auth->isLoggedIn()) {
            header('Location: ' . BASE_URL . 'dashboard');
            exit;
        }

        $error      = null;
        $locked_for = 0;

        // ── Rate limiting par IP ─────────────────────────────────────────────
        $ip          = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $rlKey       = 'rl_login_' . md5($ip);
        $rlMaxTries  = 5;
        $rlBanSecs   = 900; // 15 minutes

        $rlData = $_SESSION[$rlKey] ?? ['attempts' => 0, 'first_at' => time(), 'banned_until' => 0];

        if ($rlData['banned_until'] > time()) {
            $locked_for = $rlData['banned_until'] - time();
            $error = "Trop de tentatives. Réessayez dans " . ceil($locked_for / 60) . " minute(s).";
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$locked_for) {
            if (!CsrfService::validate()) {
                $error = "Session expirée. Veuillez recharger la page.";
                require_once __DIR__ . '/../views/auth/login.php';
                exit;
            }

            $identifiant = trim($_POST['identifiant'] ?? '');
            $password    = $_POST['password'] ?? '';

            $user = $this->auth->loginStep1($identifiant, $password);

            if ($user) {
                unset($_SESSION[$rlKey]);

                // Cookie "Se souvenir de moi"
                if (!empty($_POST['remember_me'])) {
                    $this->setRememberCookie((int)$user['id']);
                }

                if (in_array($user['role'], ['ADMIN', 'SUPER_ADMIN'])) {
                    $this->auth->finalizeLogin($user);
                    $_SESSION['show_welcome'] = true;
                    $label = $user['role'] === 'SUPER_ADMIN' ? 'Super Administrateur' : 'Administrateur';
                    $this->audit->logAction('LOGIN', 'users', $user['id'], null, "Connexion $label");
                    header('Location: ' . BASE_URL . 'dashboard');
                } else {
                    $_SESSION['temp_user'] = $user;
                    header('Location: ' . BASE_URL . 'select-service');
                }
                exit;
            } else {
                $rlData['attempts']++;
                if ($rlData['attempts'] >= $rlMaxTries) {
                    $rlData['banned_until'] = time() + $rlBanSecs;
                    $locked_for = $rlBanSecs;
                    $error = "Trop de tentatives. Compte bloqué 15 minutes.";
                    $this->audit->logAction('LOGIN_FAIL', 'users', 0, null,
                        "Brute-force détecté depuis IP $ip — blocage 15 min");
                } else {
                    $remaining = $rlMaxTries - $rlData['attempts'];
                    $error = "Identifiants incorrects. $remaining tentative(s) restante(s) avant blocage.";
                }
                $_SESSION[$rlKey] = $rlData;
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

        $db = (new Database())->getConnection();

        if (!class_exists('GardeService')) {
            require_once __DIR__ . '/../services/GardeService.php';
        }
        $gardeActive = GardeService::isOnDuty((int)($_SESSION['temp_user']['id'] ?? 0));
        $gardeInfos  = $gardeActive ? GardeService::getActiveGarde((int)($_SESSION['temp_user']['id'] ?? 0)) : null;

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

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && !CsrfService::validate()) {
            header('Location: ' . BASE_URL . 'select-service?error=session_expired');
            exit;
        }

        $selectedServiceId = (int) ($_POST['service_id'] ?? 0);
        $user              = $_SESSION['temp_user'];
        $db                = (new Database())->getConnection();

        $stmt = $db->prepare("SELECT nom_service FROM services WHERE id = ?");
        $stmt->execute([$selectedServiceId]);
        $serviceRes = $stmt->fetch(PDO::FETCH_ASSOC);
        $nomService = $serviceRes['nom_service'] ?? '';

        // Médecin de garde : accès à tous les services pendant la garde
        if (!class_exists('GardeService')) {
            require_once __DIR__ . '/../services/GardeService.php';
        }
        $isOnGarde = GardeService::isOnDuty((int)($user['id'] ?? 0));

        if ($user['service_id'] == $selectedServiceId
            || in_array($user['role'], ['ADMIN', 'SUPER_ADMIN'])
            || $isOnGarde) {

            $this->auth->finalizeLogin($user);
            $_SESSION['service_id']   = $selectedServiceId;
            $_SESSION['nom_service']  = $nomService;
            $_SESSION['show_welcome'] = true;
            if ($isOnGarde) {
                $_SESSION['garde_active']      = true;
                $_SESSION['_garde_checked_at'] = time();
                $_SESSION['_garde_uid']        = (int)($user['id'] ?? 0);
            }

            $this->audit->logAction(
                'LOGIN', 'users', $user['id'], null,
                "Connexion réussie au service : $nomService"
            );

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
     * Mot de passe oublié — Génération du code de réinitialisation
     * ---------------------------------------------------------------------- */
    public function forgotPassword(): void {
        $error   = null;
        $success = null;
        $token   = null;

        $db = (new Database())->getConnection();

        // Créer la table si elle n'existe pas encore
        $db->exec("CREATE TABLE IF NOT EXISTS password_resets (
            id         INT AUTO_INCREMENT PRIMARY KEY,
            user_id    INT NOT NULL,
            token      VARCHAR(8) NOT NULL,
            expires_at DATETIME NOT NULL,
            used       TINYINT(1) DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_token (token)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!CsrfService::validate()) {
                $error = "Session expirée. Veuillez recharger la page.";
            } else {
                $identifiant = trim($_POST['identifiant'] ?? '');
                if (empty($identifiant)) {
                    $error = "Veuillez saisir votre identifiant ou email.";
                } else {
                    $stmt = $db->prepare(
                        "SELECT id, nom, prenom FROM users
                         WHERE (username = ? OR email = ?) AND actif = 1"
                    );
                    $stmt->execute([$identifiant, $identifiant]);
                    $user = $stmt->fetch(PDO::FETCH_ASSOC);

                    if ($user) {
                        // Invalider les anciens tokens non utilisés
                        $db->prepare("UPDATE password_resets SET used = 1 WHERE user_id = ? AND used = 0")
                           ->execute([$user['id']]);

                        // Code alphanumérique à 8 caractères (plus sûr que 6 chiffres)
                        $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
                        $token = '';
                        for ($i = 0; $i < 8; $i++) {
                            $token .= $chars[random_int(0, strlen($chars) - 1)];
                        }

                        $expires = date('Y-m-d H:i:s', time() + 3600);
                        $db->prepare("INSERT INTO password_resets (user_id, token, expires_at) VALUES (?, ?, ?)")
                           ->execute([$user['id'], $token, $expires]);

                        $success = "Code généré pour {$user['prenom']} {$user['nom']}.";
                        $this->audit->logAction(
                            'PASSWORD_RESET_REQUEST', 'users', $user['id'], null,
                            'Demande de réinitialisation de mot de passe'
                        );
                    } else {
                        // Ne pas révéler si le compte existe ou non
                        $success = "Si un compte correspond à cet identifiant, les instructions s'affichent ci-dessous.";
                    }
                }
            }
        }

        require_once __DIR__ . '/../views/auth/forgot_password.php';
    }

    /* -------------------------------------------------------------------------
     * Réinitialisation du mot de passe avec le code reçu
     * ---------------------------------------------------------------------- */
    public function resetPassword(): void {
        $error   = null;
        $success = null;
        $db      = (new Database())->getConnection();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!CsrfService::validate()) {
                $error = "Session expirée. Veuillez recharger la page.";
            } else {
                $code    = strtoupper(trim($_POST['code'] ?? ''));
                $newPwd  = $_POST['new_password'] ?? '';
                $confirm = $_POST['confirm_password'] ?? '';

                if (empty($code)) {
                    $error = "Veuillez saisir votre code de réinitialisation.";
                } elseif (strlen($newPwd) < 8) {
                    $error = "Le mot de passe doit contenir au moins 8 caractères.";
                } elseif ($newPwd !== $confirm) {
                    $error = "Les deux mots de passe ne correspondent pas.";
                } else {
                    $stmt = $db->prepare(
                        "SELECT user_id FROM password_resets
                         WHERE token = ? AND used = 0 AND expires_at > NOW()"
                    );
                    $stmt->execute([$code]);
                    $reset = $stmt->fetch(PDO::FETCH_ASSOC);

                    if ($reset) {
                        $hashed = password_hash($newPwd, PASSWORD_DEFAULT);
                        $db->prepare("UPDATE users SET password = ?, must_change_password = 0 WHERE id = ?")
                           ->execute([$hashed, $reset['user_id']]);
                        $db->prepare("UPDATE password_resets SET used = 1 WHERE token = ?")
                           ->execute([$code]);

                        $this->audit->logAction(
                            'PASSWORD_RESET', 'users', $reset['user_id'], null,
                            'Mot de passe réinitialisé via code'
                        );
                        $success = true;
                    } else {
                        $error = "Code invalide ou expiré (validité : 1 heure). Faites une nouvelle demande.";
                    }
                }
            }
        }

        require_once __DIR__ . '/../views/auth/reset_password.php';
    }

    /* -------------------------------------------------------------------------
     * Changement de mot de passe forcé
     * ---------------------------------------------------------------------- */
    public function changePassword() {
        if (!$this->auth->isLoggedIn()) {
            header('Location: ' . BASE_URL . 'login'); exit;
        }

        $error   = null;
        $success = false;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!CsrfService::validate()) {
                $error = "Session expirée. Veuillez recharger la page.";
            } else {
                $newPwd     = $_POST['new_password']     ?? '';
                $confirmPwd = $_POST['confirm_password'] ?? '';

                if (strlen($newPwd) < 8) {
                    $error = "Le mot de passe doit contenir au moins 8 caractères.";
                } elseif ($newPwd !== $confirmPwd) {
                    $error = "Les deux mots de passe ne correspondent pas.";
                } else {
                    try {
                        $db     = (new Database())->getConnection();
                        $hashed = password_hash($newPwd, PASSWORD_DEFAULT);
                        $db->prepare("UPDATE users SET password = ?, must_change_password = 0 WHERE id = ?")
                           ->execute([$hashed, $_SESSION['user_id']]);

                        $_SESSION['must_change_password'] = false;

                        $this->audit->logAction('UPDATE', 'users', $_SESSION['user_id'], null,
                            'Changement de mot de passe forcé effectué');

                        header('Location: ' . BASE_URL . 'dashboard');
                        exit;
                    } catch (PDOException $e) {
                        $error = "Erreur serveur : " . $e->getMessage();
                    }
                }
            }
        }

        require_once __DIR__ . '/../views/auth/change_password.php';
    }

    /* -------------------------------------------------------------------------
     * Déconnexion
     * ---------------------------------------------------------------------- */
    public function logout() {
        $userId = $_SESSION['user_id'] ?? null;

        if ($userId) {
            $this->audit->logAction('LOGOUT', 'users', $userId, null, 'Déconnexion manuelle');
        }

        $this->clearRememberCookie();
        $this->auth->logout();

        header('Location: ' . BASE_URL . 'login');
        exit;
    }
}
