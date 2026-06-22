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
        // Services cliniques uniquement (pour les infirmiers)
        $servicesCliniques = $db->query(
            "SELECT id, nom_service FROM services
              WHERE categorie = 'CLINIQUE' OR id = 11
              ORDER BY nom_service ASC"
        )->fetchAll(PDO::FETCH_ASSOC);
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
                'id'         => $user_id,
                'nom'        => trim($_POST['nom']      ?? ''),
                'prenom'     => trim($_POST['prenom']   ?? ''),
                'email'      => trim($_POST['email']    ?? ''),
                'username'   => $_SESSION['username'],   // login immuable
                'telephone'  => trim($_POST['telephone'] ?? ''),
                'role'       => $_SESSION['user_role'],  // rôle immuable
                'service_id' => $_SESSION['service_id'], // service immuable
                'statut'     => 1
            ];

            // ── Changement de mot de passe ────────────────────────────────────
            $newPwd     = trim($_POST['new_password']     ?? '');
            $currentPwd = trim($_POST['current_password'] ?? '');

            if ($newPwd !== '') {
                // Vérifier le mot de passe actuel
                $userFull = $this->userModel->getById($user_id);
                if (!$userFull || !password_verify($currentPwd, $userFull['password'])) {
                    header('Location: ' . BASE_URL . 'profil?error_pwd=1');
                    exit;
                }
                if (strlen($newPwd) < 6) {
                    header('Location: ' . BASE_URL . 'profil?error=pwd_short');
                    exit;
                }
                $data['password'] = $newPwd;
            }

            if ($this->userModel->save($data)) {
                $_SESSION['user_nom']    = $data['nom'];
                $_SESSION['user_prenom'] = $data['prenom'];
                $this->audit->logAction(
                    'UPDATE', 'users', $user_id, null,
                    'Mise à jour du profil' . ($newPwd !== '' ? ' + changement mot de passe' : '')
                );
                header('Location: ' . BASE_URL . 'profil?success=1');
            } else {
                header('Location: ' . BASE_URL . 'profil?error=1');
            }
            exit;
        }
    }

    /**
     * AJAX — Retourner les infos du profil connecté (JSON)
     */
    public function profilJson(): void {
        header('Content-Type: application/json');
        $user = $this->userModel->getById($_SESSION['user_id'] ?? 0);
        if (!$user) { echo json_encode(['success' => false]); exit; }
        echo json_encode([
            'success'   => true,
            'nom'       => $user['nom']       ?? '',
            'prenom'    => $user['prenom']    ?? '',
            'email'     => $user['email']     ?? '',
            'telephone' => $user['telephone'] ?? '',
            'username'  => $user['username']  ?? '',
            'role'      => $user['role']      ?? '',
            'service'   => $_SESSION['nom_service'] ?? '',
        ]);
        exit;
    }

    /**
     * AJAX — Mettre à jour le profil (JSON)
     */
    public function updateProfilJson(): void {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Méthode invalide']); exit;
        }

        $payload    = json_decode(file_get_contents('php://input'), true) ?? [];
        $user_id    = (int)($_SESSION['user_id'] ?? 0);
        $nom        = trim($payload['nom']        ?? '');
        $prenom     = trim($payload['prenom']     ?? '');
        $email      = trim($payload['email']      ?? '');
        $telephone  = trim($payload['telephone']  ?? '');
        $newPwd     = trim($payload['new_password']     ?? '');
        $currentPwd = trim($payload['current_password'] ?? '');

        if (!$nom || !$prenom) {
            echo json_encode(['success' => false, 'message' => 'Nom et prénom obligatoires']); exit;
        }

        $data = [
            'id'         => $user_id,
            'nom'        => $nom,
            'prenom'     => $prenom,
            'email'      => $email,
            'username'   => $_SESSION['username'],
            'telephone'  => $telephone,
            'role'       => $_SESSION['user_role'],
            'service_id' => $_SESSION['service_id'] ?? null,
            'statut'     => 1,
        ];

        if ($newPwd !== '') {
            if (strlen($newPwd) < 6) {
                echo json_encode(['success' => false, 'message' => 'Le mot de passe doit faire au moins 6 caractères']); exit;
            }
            $userFull = $this->userModel->getById($user_id);
            if (!$userFull || !password_verify($currentPwd, $userFull['password'])) {
                echo json_encode(['success' => false, 'message' => 'Mot de passe actuel incorrect']); exit;
            }
            $data['password'] = $newPwd;
        }

        if ($this->userModel->save($data)) {
            $_SESSION['user_nom']    = $nom;
            $_SESSION['user_prenom'] = $prenom;
            $this->audit->logAction('UPDATE', 'users', $user_id, null,
                'Profil mis à jour via modal' . ($newPwd !== '' ? ' + MDP' : ''));
            echo json_encode(['success' => true, 'nom' => $nom, 'prenom' => $prenom]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erreur lors de la sauvegarde']);
        }
        exit;
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

    // ────────────────────────────────────────────────────────────────
    //  EXPORT / IMPORT EXCEL
    // ────────────────────────────────────────────────────────────────

    /**
     * Export Excel (.xlsx) de tous les utilisateurs.
     * Réservé aux administrateurs.
     */
    public function exportUsers() {
        $this->auth->requirePermission('utilisateurs', 'read');

        require_once __DIR__ . '/../services/ExcelService.php';
        $excel = new ExcelService();
        $db    = (new Database())->getConnection();

        // Récupérer tous les utilisateurs avec leur service
        $rows = $db->query("
            SELECT u.id, u.nom, u.prenom, u.username, u.email, u.telephone,
                   u.role, s.nom_service AS service, u.actif,
                   DATE_FORMAT(u.created_at, '%Y-%m-%d %H:%i') AS created_at,
                   CASE WHEN u.signature_path IS NOT NULL AND u.signature_path != '' THEN 'OUI' ELSE '' END AS signature,
                   CASE WHEN u.cachet_path    IS NOT NULL AND u.cachet_path    != '' THEN 'OUI' ELSE '' END AS cachet
            FROM users u
            LEFT JOIN services s ON u.service_id = s.id
            ORDER BY u.nom ASC, u.prenom ASC
        ")->fetchAll(PDO::FETCH_ASSOC);

        // Reformater pour Excel : statut en texte
        foreach ($rows as &$r) {
            $r['actif'] = (int)$r['actif'] === 1 ? 'ACTIF' : 'INACTIF';
        }
        unset($r);

        $headers = ['ID', 'Nom', 'Prenom', 'Username', 'Email', 'Telephone',
                    'Role', 'Service', 'Statut', 'Date_Creation', 'Signature', 'Cachet'];

        // Renommer les clés des lignes pour matcher les en-têtes
        $rowsExport = array_map(function($r) {
            return [
                'ID'            => $r['id'],
                'Nom'           => $r['nom'],
                'Prenom'        => $r['prenom'],
                'Username'      => $r['username'],
                'Email'         => $r['email'] ?? '',
                'Telephone'     => $r['telephone'] ?? '',
                'Role'          => $r['role'],
                'Service'       => $r['service'] ?? '',
                'Statut'        => $r['actif'],
                'Date_Creation' => $r['created_at'] ?? '',
                'Signature'     => $r['signature'] ?? '',
                'Cachet'        => $r['cachet'] ?? '',
            ];
        }, $rows);

        $filename = 'utilisateurs_dme_' . date('Y-m-d_Hi') . '.xlsx';
        $format = strtolower($_GET['format'] ?? 'xlsx');

        $this->audit->logAction('EXPORT', 'users', null, null,
            "Export Excel de " . count($rowsExport) . " utilisateurs (format: $format)");

        if ($format === 'csv') {
            $excel->downloadCsv(str_replace('.xlsx', '.csv', $filename), $headers, $rowsExport);
        } else {
            $excel->downloadXlsx($filename, $headers, $rowsExport, 'Utilisateurs');
        }
    }

    /**
     * Télécharge un modèle Excel vide pour l'import (avec en-têtes + 2 lignes d'exemple).
     */
    public function downloadTemplate() {
        $this->auth->requirePermission('utilisateurs', 'read');

        require_once __DIR__ . '/../services/ExcelService.php';
        $excel = new ExcelService();

        $headers = ['ID', 'Nom', 'Prenom', 'Username', 'Email', 'Telephone',
                    'Role', 'Service', 'Statut', 'Mot_de_passe'];

        // 2 lignes d'exemple (à supprimer par l'utilisateur avant import)
        $exemple = [
            [
                'ID' => '', 'Nom' => 'DUPONT', 'Prenom' => 'Jean',
                'Username' => 'j-dupont', 'Email' => 'j.dupont@hsjm.local',
                'Telephone' => '+237699000000', 'Role' => 'MEDECIN',
                'Service' => 'Médecine Générale', 'Statut' => 'ACTIF',
                'Mot_de_passe' => 'password123'
            ],
            [
                'ID' => '', 'Nom' => 'MARTIN', 'Prenom' => 'Marie',
                'Username' => 'm-martin', 'Email' => '',
                'Telephone' => '', 'Role' => 'INFIRMIER',
                'Service' => 'Pédiatrie', 'Statut' => 'ACTIF',
                'Mot_de_passe' => 'temp2026'
            ],
        ];

        $excel->downloadXlsx('modele_import_utilisateurs.xlsx', $headers, $exemple, 'Import Utilisateurs');
    }

    /**
     * Import Excel ou CSV d'utilisateurs.
     * Réservé aux administrateurs.
     *
     * Comportement :
     *   - Ligne avec ID renseigné → mise à jour
     *   - Ligne sans ID → création (mot de passe généré si vide)
     *   - Validation : nom, prenom, username, role, service obligatoires
     *   - Service identifié par nom (insensible à la casse) ou par ID numérique
     */
    public function importUsers() {
        $this->auth->requirePermission('utilisateurs', 'write');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . 'utilisateurs');
            exit;
        }
        if (empty($_FILES['fichier']['tmp_name']) || $_FILES['fichier']['error'] !== UPLOAD_ERR_OK) {
            $this->renderImportResult(['error' => 'Aucun fichier ou upload invalide.']);
            return;
        }

        $tmp     = $_FILES['fichier']['tmp_name'];
        $orig    = $_FILES['fichier']['name'];
        $ext     = strtolower(pathinfo($orig, PATHINFO_EXTENSION));
        if (!in_array($ext, ['xlsx', 'csv', 'txt'])) {
            $this->renderImportResult(['error' => 'Format non supporté. Utilisez .xlsx ou .csv.']);
            return;
        }

        require_once __DIR__ . '/../services/ExcelService.php';
        $excel = new ExcelService();
        try {
            $data = $excel->readFile($tmp, $orig);
        } catch (Exception $e) {
            $this->renderImportResult(['error' => 'Lecture du fichier impossible : ' . $e->getMessage()]);
            return;
        }
        $rows = $data['rows'] ?? [];
        if (empty($rows)) {
            $this->renderImportResult(['error' => 'Le fichier ne contient aucune ligne de données.']);
            return;
        }

        // Charger la liste des services pour matcher par nom
        $db = (new Database())->getConnection();
        $servicesRaw = $db->query("SELECT id, nom_service FROM services")->fetchAll(PDO::FETCH_ASSOC);
        $serviceByName = [];
        $serviceById   = [];
        foreach ($servicesRaw as $s) {
            $serviceByName[mb_strtolower(trim($s['nom_service']))] = (int)$s['id'];
            $serviceById[(int)$s['id']] = $s['nom_service'];
        }

        // Liste des rôles valides (à synchroniser avec le formulaire création utilisateur)
        $rolesValides = [
            'ADMIN','DIRECTEUR','MEDECIN','INFIRMIER','INFIRMIER_CONSULTANT',
            'INFIRMIER_ANESTHESISTE','INFIRMIER_BLOC',
            'SECRETAIRE','SECRETAIRE_SAU','SECRETAIRE_SPECIALISTE','SECRETAIRE_LABO',
            'LABORANTIN','TECHNICIEN_LABO','PHARMACIEN','MAJOR','PARAMETRES',
            'CHIRURGIEN','GENERALISTE','RADIOLOGUE','ANESTHESISTE',
        ];

        $stats = [
            'lignes'   => count($rows),
            'crees'    => 0,
            'maj'      => 0,
            'erreurs'  => 0,
            'details'  => [],     // Liste de messages d'erreur
            'creations' => [],     // Liste des créations (avec mots de passe générés)
        ];

        foreach ($rows as $idx => $row) {
            $ligne = $idx + 2; // +2 car ligne 1 = en-têtes, +1 pour passer en 1-based
            // Normaliser les clés (insensible à la casse + tirets/underscores)
            $r = $this->normalizeImportKeys($row);

            $id        = trim((string)($r['id'] ?? ''));
            $nom       = trim((string)($r['nom'] ?? ''));
            $prenom    = trim((string)($r['prenom'] ?? ''));
            $username  = trim((string)($r['username'] ?? ''));
            $email     = trim((string)($r['email'] ?? ''));
            $telephone = trim((string)($r['telephone'] ?? ''));
            $role      = strtoupper(trim((string)($r['role'] ?? '')));
            $service   = trim((string)($r['service'] ?? ''));
            $statut    = strtoupper(trim((string)($r['statut'] ?? 'ACTIF')));
            $password  = trim((string)($r['motdepasse'] ?? $r['mot_de_passe'] ?? $r['password'] ?? ''));

            // Validation des champs obligatoires
            if ($nom === '' || $prenom === '' || $username === '') {
                $stats['erreurs']++;
                $stats['details'][] = "Ligne $ligne : nom, prénom et username obligatoires.";
                continue;
            }
            if (!in_array($role, $rolesValides)) {
                $stats['erreurs']++;
                $stats['details'][] = "Ligne $ligne : rôle '$role' invalide. Valeurs acceptées : "
                                    . implode(', ', $rolesValides);
                continue;
            }

            // Identifier le service (par nom ou ID numérique)
            $serviceId = null;
            if ($service !== '') {
                if (ctype_digit($service) && isset($serviceById[(int)$service])) {
                    $serviceId = (int)$service;
                } else {
                    $key = mb_strtolower($service);
                    if (isset($serviceByName[$key])) {
                        $serviceId = $serviceByName[$key];
                    }
                }
            }
            if (!$serviceId) {
                $stats['erreurs']++;
                $stats['details'][] = "Ligne $ligne : service '$service' introuvable. "
                                    . "Vérifiez l'orthographe (services disponibles : "
                                    . implode(', ', array_slice(array_values($serviceById), 0, 8)) . "…).";
                continue;
            }

            $statutDb = ($statut === 'INACTIF' || $statut === '0') ? 0 : 1;

            try {
                if ($id !== '' && ctype_digit($id)) {
                    // ── MISE À JOUR ─────────────────────────────────────
                    $stmt = $db->prepare("SELECT id FROM users WHERE id = ?");
                    $stmt->execute([$id]);
                    if (!$stmt->fetchColumn()) {
                        $stats['erreurs']++;
                        $stats['details'][] = "Ligne $ligne : utilisateur ID=$id introuvable.";
                        continue;
                    }

                    $data = [
                        'id'         => (int)$id,
                        'nom'        => $nom,
                        'prenom'     => $prenom,
                        'username'   => $username,
                        'email'      => $email,
                        'telephone'  => $telephone,
                        'role'       => $role,
                        'service_id' => $serviceId,
                        'statut'     => $statutDb,
                    ];
                    if ($password !== '') {
                        $data['password'] = $password;
                    }

                    if ($this->userModel->save($data)) {
                        $stats['maj']++;
                        $this->audit->logAction('UPDATE', 'users', (int)$id, null,
                            "Import Excel : MAJ $nom $prenom");
                    } else {
                        $stats['erreurs']++;
                        $stats['details'][] = "Ligne $ligne : échec MAJ ID=$id.";
                    }
                } else {
                    // ── CRÉATION ────────────────────────────────────────
                    // Vérifier que username n'existe pas déjà
                    $stmt = $db->prepare("SELECT id FROM users WHERE username = ?");
                    $stmt->execute([strtolower($username)]);
                    if ($stmt->fetchColumn()) {
                        $stats['erreurs']++;
                        $stats['details'][] = "Ligne $ligne : username '$username' déjà utilisé.";
                        continue;
                    }

                    // Générer un mot de passe si vide
                    $generated = false;
                    if ($password === '') {
                        $password = $this->generatePassword();
                        $generated = true;
                    }

                    $data = [
                        'id'         => '',
                        'nom'        => $nom,
                        'prenom'     => $prenom,
                        'username'   => $username,
                        'email'      => $email,
                        'telephone'  => $telephone,
                        'role'       => $role,
                        'service_id' => $serviceId,
                        'password'   => $password,
                    ];
                    $newId = $this->userModel->save($data);
                    if ($newId) {
                        // Si le statut demandé est INACTIF, désactiver après création
                        if ($statutDb === 0) {
                            $db->prepare("UPDATE users SET actif = 0 WHERE id = ?")->execute([$newId]);
                        }
                        $stats['crees']++;
                        $stats['creations'][] = [
                            'username' => $username,
                            'nom'      => $nom . ' ' . $prenom,
                            'password' => $generated ? $password : '(saisi)',
                            'generated' => $generated,
                        ];
                        $this->audit->logAction('CREATE', 'users', (int)$newId, null,
                            "Import Excel : création $nom $prenom (rôle $role)");
                    } else {
                        $stats['erreurs']++;
                        $stats['details'][] = "Ligne $ligne : échec création $nom $prenom.";
                    }
                }
            } catch (Exception $e) {
                $stats['erreurs']++;
                $stats['details'][] = "Ligne $ligne : erreur — " . $e->getMessage();
            }
        }

        $this->renderImportResult($stats);
    }

    // ── Helpers privés pour l'import ─────────────────────────────────────────

    /** Normalise les clés d'une ligne importée (insensible à la casse, accents, espaces) */
    private function normalizeImportKeys(array $row): array {
        $out = [];
        foreach ($row as $k => $v) {
            $key = mb_strtolower((string)$k);
            // Retirer accents communs
            $key = strtr($key, [
                'é'=>'e','è'=>'e','ê'=>'e','ë'=>'e','à'=>'a','â'=>'a','ä'=>'a',
                'î'=>'i','ï'=>'i','ô'=>'o','ö'=>'o','ù'=>'u','û'=>'u','ü'=>'u','ç'=>'c'
            ]);
            // Retirer espaces, tirets, underscores
            $key = preg_replace('/[\s\-_]+/', '', $key);
            $out[$key] = $v;
        }
        return $out;
    }

    /** Génère un mot de passe aléatoire de 10 caractères lisibles */
    private function generatePassword(): string {
        // On évite les caractères ambigus (0/O, 1/l/I)
        $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz23456789';
        $pwd = '';
        $max = strlen($chars) - 1;
        for ($i = 0; $i < 10; $i++) {
            $pwd .= $chars[random_int(0, $max)];
        }
        return $pwd;
    }

    /** Affiche la page de résultat de l'import */
    private function renderImportResult(array $stats): void {
        require_once __DIR__ . '/../views/utilisateurs/import_result.php';
    }

    /* ──────────────────────────────────────────────────────────────────
     * POST utilisateurs/reset-password
     * Réinitialise le mot de passe d'un utilisateur (Admin seulement)
     * Retourne le nouveau mot de passe en clair dans la réponse JSON
     * ────────────────────────────────────────────────────────────────── */
    public function resetPassword(): void {
        header('Content-Type: application/json; charset=utf-8');
        $this->auth->requirePermission('utilisateurs', 'write');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Méthode non autorisée.']); return;
        }

        $id = (int)($_POST['id'] ?? 0);
        if (!$id) { echo json_encode(['success' => false, 'message' => 'ID invalide.']); return; }

        // Interdire la réinitialisation de son propre compte depuis cette route
        if ($id === (int)$_SESSION['user_id']) {
            echo json_encode(['success' => false, 'message' => 'Utilisez le formulaire de profil pour changer votre propre mot de passe.']);
            return;
        }

        try {
            $db   = (new Database())->getConnection();
            $stmt = $db->prepare("SELECT nom, prenom, username FROM users WHERE id = ? LIMIT 1");
            $stmt->execute([$id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$user) { echo json_encode(['success' => false, 'message' => 'Utilisateur introuvable.']); return; }

            $newPwd  = $this->generatePassword();
            $hashed  = password_hash($newPwd, PASSWORD_DEFAULT);

            $db->prepare("UPDATE users SET password = ? WHERE id = ?")->execute([$hashed, $id]);

            $this->audit->logAction('UPDATE', 'users', $id, null,
                "Réinitialisation MDP par admin pour {$user['nom']} {$user['prenom']} (login: {$user['username']})");

            echo json_encode([
                'success'   => true,
                'password'  => $newPwd,
                'username'  => $user['username'],
                'nom'       => $user['nom'] . ' ' . $user['prenom'],
            ]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /* ──────────────────────────────────────────────────────────────────
     * POST utilisateurs/force-change-password
     * Oblige un utilisateur à changer son MDP à sa prochaine connexion
     * ────────────────────────────────────────────────────────────────── */
    public function forceChangePassword(): void {
        header('Content-Type: application/json; charset=utf-8');
        $this->auth->requirePermission('utilisateurs', 'write');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Méthode non autorisée.']); return;
        }

        $id = (int)($_POST['id'] ?? 0);
        if (!$id) { echo json_encode(['success' => false, 'message' => 'ID invalide.']); return; }

        if ($id === (int)$_SESSION['user_id']) {
            echo json_encode(['success' => false, 'message' => 'Vous ne pouvez pas forcer votre propre compte.']); return;
        }

        try {
            $db = (new Database())->getConnection();

            // Créer la colonne si elle n'existe pas encore (migration automatique)
            $cols = $db->query("SHOW COLUMNS FROM users LIKE 'must_change_password'")->fetchAll();
            if (empty($cols)) {
                $db->exec("ALTER TABLE users ADD COLUMN must_change_password TINYINT(1) NOT NULL DEFAULT 0");
            }

            $stmt = $db->prepare("SELECT nom, prenom, username FROM users WHERE id = ? LIMIT 1");
            $stmt->execute([$id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$user) { echo json_encode(['success' => false, 'message' => 'Utilisateur introuvable.']); return; }

            // Toggle : si déjà activé → annuler, sinon → activer
            $stmtFlag = $db->prepare("SELECT must_change_password FROM users WHERE id = ?");
            $stmtFlag->execute([$id]);
            $current = (int)$stmtFlag->fetchColumn();
            $newVal  = $current ? 0 : 1;

            $db->prepare("UPDATE users SET must_change_password = ? WHERE id = ?")->execute([$newVal, $id]);

            $action = $newVal ? 'Forçage' : 'Annulation forçage';
            $this->audit->logAction('UPDATE', 'users', $id, null,
                "$action changement MDP par admin pour {$user['nom']} {$user['prenom']} (login: {$user['username']})");

            echo json_encode(['success' => true, 'nom' => $user['nom'] . ' ' . $user['prenom'], 'actif' => $newVal]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /* ──────────────────────────────────────────────────────────────────
     * POST utilisateurs/change-service
     * Affecte/mute un utilisateur vers un autre service (Admin seulement)
     * ────────────────────────────────────────────────────────────────── */
    public function changeService(): void {
        header('Content-Type: application/json; charset=utf-8');
        $this->auth->requirePermission('utilisateurs', 'write');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Méthode non autorisée.']); return;
        }

        $id        = (int)($_POST['id']         ?? 0);
        $serviceId = (int)($_POST['service_id'] ?? 0);
        if (!$id || !$serviceId) {
            echo json_encode(['success' => false, 'message' => 'Paramètres invalides.']); return;
        }

        try {
            $db = (new Database())->getConnection();

            $stmtU = $db->prepare("SELECT nom, prenom, role, service_id FROM users WHERE id = ? LIMIT 1");
            $stmtU->execute([$id]);
            $user = $stmtU->fetch(PDO::FETCH_ASSOC);
            if (!$user) { echo json_encode(['success' => false, 'message' => 'Utilisateur introuvable.']); return; }

            $stmtS = $db->prepare("SELECT nom_service FROM services WHERE id = ? LIMIT 1");
            $stmtS->execute([$serviceId]);
            $service = $stmtS->fetch(PDO::FETCH_ASSOC);
            if (!$service) { echo json_encode(['success' => false, 'message' => 'Service introuvable.']); return; }

            $oldSvcStmt = $db->prepare("SELECT nom_service FROM services WHERE id = ? LIMIT 1");
            $oldSvcStmt->execute([$user['service_id']]);
            $oldSvc = $oldSvcStmt->fetchColumn() ?: 'Inconnu';

            $db->prepare("UPDATE users SET service_id = ? WHERE id = ?")->execute([$serviceId, $id]);

            $this->audit->logAction('UPDATE', 'users', $id, null,
                "Mutation de service : {$user['nom']} {$user['prenom']} · {$oldSvc} → {$service['nom_service']}");

            echo json_encode([
                'success'      => true,
                'nom_service'  => $service['nom_service'],
                'message'      => "{$user['nom']} {$user['prenom']} muté vers {$service['nom_service']}",
            ]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
}