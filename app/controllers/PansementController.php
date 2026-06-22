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

/**
 * PansementController
 * Gère les fiches de suivi des pansements (identification + entrées de suivi).
 */
require_once __DIR__ . '/../../config/database.php';

class PansementController
{
    private PDO $db;

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        if (empty($_SESSION['user_id'])) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Non authentifié.']);
            exit;
        }

        $this->db = (new Database())->getConnection();
        $this->migrerTables();
    }

    /* ─────────────────────────────────────────────────────────────────
       Migration automatique des tables
    ───────────────────────────────────────────────────────────────── */
    private function migrerTables(): void
    {
        // Table : fiches_pansement (identification de la plaie)
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS `fiches_pansement` (
                `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `patient_id`   INT NOT NULL,
                `hosp_id`      INT NULL,
                `type_plaie`   TEXT NULL COMMENT 'JSON array',
                `terrain`      TEXT NULL COMMENT 'JSON array',
                `localisation` TEXT NULL,
                `age_plaie`    VARCHAR(100) NULL,
                `user_id`      INT NOT NULL,
                `created_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uq_fiche_patient` (`patient_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // Table : suivi_pansement (entrées de suivi)
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS `suivi_pansement` (
                `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `fiche_id`     INT UNSIGNED NULL,
                `patient_id`   INT NOT NULL,
                `hosp_id`      INT NULL,
                `date_soin`    DATE NOT NULL,
                `evolution`    TEXT NULL COMMENT 'JSON array',
                `longueur`     DECIMAL(6,2) NULL,
                `largeur`      DECIMAL(6,2) NULL,
                `exsudat`      VARCHAR(10)  NULL,
                `douleur`      VARCHAR(10)  NULL,
                `actions`      TEXT NULL COMMENT 'JSON array',
                `signes`       TEXT NULL COMMENT 'JSON array',
                `observations` TEXT NULL,
                `photos`       TEXT NULL COMMENT 'JSON array of relative paths',
                `user_id`      INT NOT NULL,
                `created_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                KEY `idx_suivi_patient` (`patient_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // Migration : ajouter la colonne photos si absente (tables existantes)
        try {
            $this->db->exec("ALTER TABLE `suivi_pansement` ADD COLUMN `photos` TEXT NULL COMMENT 'JSON array of relative paths' AFTER `observations`");
        } catch (\PDOException $e) {
            // Colonne déjà présente — ignoré
        }
    }

    /* ─────────────────────────────────────────────────────────────────
       Helper : sortie JSON
    ───────────────────────────────────────────────────────────────── */
    private function json(array $data, int $code = 200): void
    {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    /* ─────────────────────────────────────────────────────────────────
       POST pansement/sauvegarder-identification
       Crée ou met à jour la fiche d'identification de la plaie
    ───────────────────────────────────────────────────────────────── */
    public function sauvegarderIdentification(): void
    {
        $patient_id  = (int)($_POST['patient_id']  ?? 0);
        $hosp_id     = $_POST['hosp_id']     ? (int)$_POST['hosp_id']     : null;
        $type_plaie  = $_POST['type_plaie']  ?? '[]';
        $terrain     = $_POST['terrain']     ?? '[]';
        $localisation = trim($_POST['localisation'] ?? '');
        $age_plaie   = trim($_POST['age_plaie']  ?? '');
        $user_id     = (int)$_SESSION['user_id'];

        if (!$patient_id) {
            $this->json(['success' => false, 'message' => 'patient_id manquant.'], 422);
        }

        // Valider que les JSON sont bien formés
        if (json_decode($type_plaie) === null) $type_plaie = '[]';
        if (json_decode($terrain)    === null) $terrain    = '[]';

        // UPSERT : une seule fiche par patient
        $stmt = $this->db->prepare("
            INSERT INTO fiches_pansement
                (patient_id, hosp_id, type_plaie, terrain, localisation, age_plaie, user_id)
            VALUES
                (:pid, :hid, :tp, :ter, :loc, :age, :uid)
            ON DUPLICATE KEY UPDATE
                hosp_id      = VALUES(hosp_id),
                type_plaie   = VALUES(type_plaie),
                terrain      = VALUES(terrain),
                localisation = VALUES(localisation),
                age_plaie    = VALUES(age_plaie),
                user_id      = VALUES(user_id),
                updated_at   = NOW()
        ");
        $stmt->execute([
            ':pid' => $patient_id,
            ':hid' => $hosp_id,
            ':tp'  => $type_plaie,
            ':ter' => $terrain,
            ':loc' => $localisation,
            ':age' => $age_plaie,
            ':uid' => $user_id,
        ]);

        $this->json(['success' => true, 'message' => 'Identification enregistrée avec succès.']);
    }

    /* ─────────────────────────────────────────────────────────────────
       POST pansement/ajouter-entree
       Ajoute une nouvelle entrée dans l'historique de suivi
    ───────────────────────────────────────────────────────────────── */
    public function ajouterEntree(): void
    {
        $patient_id  = (int)($_POST['patient_id'] ?? 0);
        $hosp_id     = $_POST['hosp_id']    ? (int)$_POST['hosp_id'] : null;
        $date_soin   = trim($_POST['date_soin']   ?? '');
        $evolution   = $_POST['evolution']  ?? '[]';
        $longueur    = $_POST['longueur']   !== '' ? (float)$_POST['longueur']  : null;
        $largeur     = $_POST['largeur']    !== '' ? (float)$_POST['largeur']   : null;
        $exsudat     = trim($_POST['exsudat']     ?? '');
        $douleur     = trim($_POST['douleur']     ?? '');
        $actions     = $_POST['actions']    ?? '[]';
        $signes      = $_POST['signes']     ?? '[]';
        $observations = trim($_POST['observations'] ?? '');
        $user_id     = (int)$_SESSION['user_id'];

        if (!$patient_id || !$date_soin) {
            $this->json(['success' => false, 'message' => 'Champs obligatoires manquants.'], 422);
        }

        // Valider le format de date
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_soin)) {
            $this->json(['success' => false, 'message' => 'Format de date invalide.'], 422);
        }

        // Valider les JSON
        if (json_decode($evolution) === null) $evolution = '[]';
        if (json_decode($actions)   === null) $actions   = '[]';
        if (json_decode($signes)    === null) $signes    = '[]';

        // Récupérer l'id de la fiche si elle existe
        $stmtF = $this->db->prepare("SELECT id FROM fiches_pansement WHERE patient_id = ? LIMIT 1");
        $stmtF->execute([$patient_id]);
        $fiche_id = $stmtF->fetchColumn() ?: null;

        // Photos : chemins déjà uploadés transmis en JSON
        $photos = trim($_POST['photos'] ?? '[]');
        if (json_decode($photos) === null) $photos = '[]';

        $stmt = $this->db->prepare("
            INSERT INTO suivi_pansement
                (fiche_id, patient_id, hosp_id, date_soin, evolution, longueur, largeur,
                 exsudat, douleur, actions, signes, observations, photos, user_id)
            VALUES
                (:fid, :pid, :hid, :ds, :evo, :lon, :lar, :exs, :dou, :act, :sig, :obs, :pho, :uid)
        ");
        $stmt->execute([
            ':fid' => $fiche_id,
            ':pid' => $patient_id,
            ':hid' => $hosp_id,
            ':ds'  => $date_soin,
            ':evo' => $evolution,
            ':lon' => $longueur,
            ':lar' => $largeur,
            ':exs' => $exsudat ?: null,
            ':dou' => $douleur ?: null,
            ':act' => $actions,
            ':sig' => $signes,
            ':obs' => $observations ?: null,
            ':pho' => $photos !== '[]' ? $photos : null,
            ':uid' => $user_id,
        ]);

        $this->json(['success' => true, 'message' => 'Entrée enregistrée avec succès.']);
    }

    /* ─────────────────────────────────────────────────────────────────
       GET pansement/historique?patient_id=X
       Retourne les entrées de suivi JSON
    ───────────────────────────────────────────────────────────────── */
    public function historique(): void
    {
        $patient_id = (int)($_GET['patient_id'] ?? 0);
        if (!$patient_id) {
            $this->json(['success' => false, 'entrees' => [], 'message' => 'patient_id manquant.']);
        }

        $stmt = $this->db->prepare("
            SELECT sp.id, sp.date_soin, sp.evolution, sp.longueur, sp.largeur,
                   sp.exsudat, sp.douleur, sp.actions, sp.signes, sp.observations, sp.photos,
                   u.prenom AS user_prenom, u.nom AS user_nom
            FROM suivi_pansement sp
            LEFT JOIN users u ON u.id = sp.user_id
            WHERE sp.patient_id = ?
            ORDER BY sp.date_soin DESC, sp.id DESC
        ");
        $stmt->execute([$patient_id]);
        $entrees = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $this->json(['success' => true, 'entrees' => $entrees]);
    }

    /* ─────────────────────────────────────────────────────────────────
       GET pansement/get-identification?patient_id=X
       Retourne la fiche d'identification JSON
    ───────────────────────────────────────────────────────────────── */
    public function getIdentification(): void
    {
        $patient_id = (int)($_GET['patient_id'] ?? 0);
        if (!$patient_id) {
            $this->json(['success' => false, 'fiche' => null, 'message' => 'patient_id manquant.']);
        }

        $stmt = $this->db->prepare("
            SELECT fp.id, fp.patient_id, fp.hosp_id, fp.type_plaie, fp.terrain,
                   fp.localisation, fp.age_plaie, fp.updated_at
            FROM fiches_pansement fp
            WHERE fp.patient_id = ?
            LIMIT 1
        ");
        $stmt->execute([$patient_id]);
        $fiche = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;

        $this->json(['success' => true, 'fiche' => $fiche]);
    }

    /* ─────────────────────────────────────────────────────────────────
       POST pansement/modifier-entree
       Met à jour une entrée de suivi existante
    ───────────────────────────────────────────────────────────────── */
    public function modifierEntree(): void
    {
        $id          = (int)($_POST['id'] ?? 0);
        $user_id     = (int)$_SESSION['user_id'];
        $role        = $_SESSION['user_role'] ?? '';

        if (!$id) $this->json(['success' => false, 'message' => 'ID manquant.'], 422);

        $stmt = $this->db->prepare("SELECT user_id FROM suivi_pansement WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) $this->json(['success' => false, 'message' => 'Entrée introuvable.'], 404);

        $rolesPriv = ['ADMIN', 'MEDECIN_CHEF', 'DIRECTEUR'];
        if ((int)$row['user_id'] !== $user_id && !in_array($role, $rolesPriv)) {
            $this->json(['success' => false, 'message' => 'Modification non autorisée.'], 403);
        }

        $date_soin    = trim($_POST['date_soin']    ?? '');
        $evolution    = $_POST['evolution']   ?? '[]';
        $longueur     = $_POST['longueur']    !== '' ? (float)$_POST['longueur']  : null;
        $largeur      = $_POST['largeur']     !== '' ? (float)$_POST['largeur']   : null;
        $exsudat      = trim($_POST['exsudat']      ?? '');
        $douleur      = trim($_POST['douleur']      ?? '');
        $actions      = $_POST['actions']     ?? '[]';
        $signes       = $_POST['signes']      ?? '[]';
        $observations = trim($_POST['observations'] ?? '');

        if (!$date_soin || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_soin)) {
            $this->json(['success' => false, 'message' => 'Date invalide.'], 422);
        }
        if (json_decode($evolution) === null) $evolution = '[]';
        if (json_decode($actions)   === null) $actions   = '[]';
        if (json_decode($signes)    === null) $signes    = '[]';

        $upd = $this->db->prepare("
            UPDATE suivi_pansement SET
                date_soin=:ds, evolution=:evo, longueur=:lon, largeur=:lar,
                exsudat=:exs, douleur=:dou, actions=:act, signes=:sig, observations=:obs
            WHERE id=:id
        ");
        $upd->execute([
            ':ds'  => $date_soin,
            ':evo' => $evolution,
            ':lon' => $longueur,
            ':lar' => $largeur,
            ':exs' => $exsudat ?: null,
            ':dou' => $douleur ?: null,
            ':act' => $actions,
            ':sig' => $signes,
            ':obs' => $observations ?: null,
            ':id'  => $id,
        ]);

        $this->json(['success' => true, 'message' => 'Entrée mise à jour.']);
    }

    /* ─────────────────────────────────────────────────────────────────
       POST pansement/ajouter-photo-entree
       Upload une photo et l'attache à une entrée existante
    ───────────────────────────────────────────────────────────────── */
    public function ajouterPhotoEntree(): void
    {
        ob_clean();
        $entree_id = (int)($_POST['entree_id'] ?? 0);
        if (!$entree_id) $this->json(['success' => false, 'message' => 'entree_id manquant.'], 422);

        if (empty($_FILES['photo']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
            $this->json(['success' => false, 'message' => 'Aucun fichier reçu.'], 422);
        }

        $file   = $_FILES['photo'];
        $mimeOk = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $finfo  = finfo_open(FILEINFO_MIME_TYPE);
        $mime   = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mime, $mimeOk, true)) {
            $this->json(['success' => false, 'message' => 'Format non accepté.'], 422);
        }
        if ($file['size'] > 8 * 1024 * 1024) {
            $this->json(['success' => false, 'message' => 'Fichier trop volumineux (max 8 Mo).'], 422);
        }

        $ext = match($mime) {
            'image/jpeg' => 'jpg', 'image/png' => 'png',
            'image/gif'  => 'gif', 'image/webp' => 'webp', default => 'jpg',
        };
        $dir = __DIR__ . '/../../storage/pansement_photos/';
        if (!is_dir($dir)) { mkdir($dir, 0750, true); file_put_contents($dir . '.htaccess', "Options -Indexes\n"); }
        $name = date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        if (!move_uploaded_file($file['tmp_name'], $dir . $name)) {
            $this->json(['success' => false, 'message' => 'Erreur enregistrement.'], 500);
        }
        $path = 'storage/pansement_photos/' . $name;

        // Vérifier l'entrée et récupérer les photos actuelles
        $stmt = $this->db->prepare("SELECT photos FROM suivi_pansement WHERE id = ?");
        $stmt->execute([$entree_id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) { @unlink($dir . $name); $this->json(['success' => false, 'message' => 'Entrée introuvable.'], 404); }

        $photos = json_decode($row['photos'] ?? '[]', true) ?: [];
        $photos[] = $path;
        $this->db->prepare("UPDATE suivi_pansement SET photos=? WHERE id=?")->execute([json_encode($photos), $entree_id]);

        $this->json(['success' => true, 'path' => $path, 'message' => 'Photo ajoutée.']);
    }

    /* ─────────────────────────────────────────────────────────────────
       POST pansement/upload-photo
       Upload une photo et retourne son chemin relatif
    ───────────────────────────────────────────────────────────────── */
    public function uploadPhoto(): void
    {
        ob_clean();
        if (empty($_FILES['photo']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
            $this->json(['success' => false, 'message' => 'Aucun fichier reçu.'], 422);
        }

        $file     = $_FILES['photo'];
        $mimeOk   = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $finfo    = finfo_open(FILEINFO_MIME_TYPE);
        $mime     = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mime, $mimeOk, true)) {
            $this->json(['success' => false, 'message' => 'Format non accepté (JPG, PNG, GIF, WEBP uniquement).'], 422);
        }

        if ($file['size'] > 8 * 1024 * 1024) {
            $this->json(['success' => false, 'message' => 'Fichier trop volumineux (max 8 Mo).'], 422);
        }

        $ext  = match($mime) {
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/gif'  => 'gif',
            'image/webp' => 'webp',
            default      => 'jpg',
        };
        $dir  = __DIR__ . '/../../storage/pansement_photos/';
        if (!is_dir($dir)) {
            mkdir($dir, 0750, true);
            file_put_contents($dir . '.htaccess', "Options -Indexes\n");
        }
        $name = date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        if (!move_uploaded_file($file['tmp_name'], $dir . $name)) {
            $this->json(['success' => false, 'message' => 'Erreur lors de l\'enregistrement.'], 500);
        }

        $this->json(['success' => true, 'path' => 'storage/pansement_photos/' . $name]);
    }

    /* ─────────────────────────────────────────────────────────────────
       POST pansement/supprimer-photo
       Supprime une photo d'une entrée (chemin relatif passé en POST)
    ───────────────────────────────────────────────────────────────── */
    public function supprimerPhoto(): void
    {
        $entree_id = (int)($_POST['entree_id'] ?? 0);
        $path      = trim($_POST['path'] ?? '');
        $user_id   = (int)$_SESSION['user_id'];
        $role      = $_SESSION['user_role'] ?? '';

        if (!$entree_id || !$path) {
            $this->json(['success' => false, 'message' => 'Paramètres manquants.'], 422);
        }

        // Valider le chemin pour éviter tout path traversal
        if (!preg_match('#^storage/pansement_photos/[a-zA-Z0-9_\-\.]+\.(jpg|jpeg|png|gif|webp)$#i', $path)) {
            $this->json(['success' => false, 'message' => 'Chemin invalide.'], 422);
        }

        $stmt = $this->db->prepare("SELECT user_id, photos FROM suivi_pansement WHERE id = ?");
        $stmt->execute([$entree_id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            $this->json(['success' => false, 'message' => 'Entrée introuvable.'], 404);
        }

        $rolesPriv = ['ADMIN', 'MEDECIN_CHEF', 'DIRECTEUR'];
        if ((int)$row['user_id'] !== $user_id && !in_array($role, $rolesPriv)) {
            $this->json(['success' => false, 'message' => 'Non autorisé.'], 403);
        }

        $photos = json_decode($row['photos'] ?? '[]', true) ?: [];
        $photos = array_values(array_filter($photos, fn($p) => $p !== $path));

        $upd = $this->db->prepare("UPDATE suivi_pansement SET photos = ? WHERE id = ?");
        $upd->execute([$photos ? json_encode($photos) : null, $entree_id]);

        // Supprimer le fichier physique s'il n'est plus référencé ailleurs
        $refCheck = $this->db->prepare("SELECT COUNT(*) FROM suivi_pansement WHERE photos LIKE ?");
        $refCheck->execute(['%' . $path . '%']);
        if ((int)$refCheck->fetchColumn() === 0) {
            $fullPath = __DIR__ . '/../../' . $path;
            if (file_exists($fullPath)) @unlink($fullPath);
        }

        $this->json(['success' => true, 'message' => 'Photo supprimée.']);
    }

    /* ─────────────────────────────────────────────────────────────────
       POST pansement/supprimer-entree
       Supprime une entrée de suivi (uniquement par l'auteur ou un admin)
    ───────────────────────────────────────────────────────────────── */
    public function supprimerEntree(): void
    {
        $id      = (int)($_POST['id'] ?? 0);
        $user_id = (int)$_SESSION['user_id'];
        $role    = $_SESSION['user_role'] ?? '';

        if (!$id) {
            $this->json(['success' => false, 'message' => 'ID manquant.'], 422);
        }

        // Vérifier l'existence de l'entrée
        $stmt = $this->db->prepare("SELECT user_id, photos FROM suivi_pansement WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            $this->json(['success' => false, 'message' => 'Entrée introuvable.'], 404);
        }

        // Autorisation : auteur de l'entrée ou rôle admin/médecin_chef
        $rolesPrivileges = ['ADMIN', 'MEDECIN_CHEF', 'DIRECTEUR'];
        if ((int)$row['user_id'] !== $user_id && !in_array($role, $rolesPrivileges)) {
            $this->json(['success' => false, 'message' => 'Suppression non autorisée.'], 403);
        }

        // Supprimer les photos associées
        $photos = json_decode($row['photos'] ?? '[]', true) ?: [];
        foreach ($photos as $p) {
            if (preg_match('#^storage/pansement_photos/[a-zA-Z0-9_\-\.]+\.(jpg|jpeg|png|gif|webp)$#i', $p)) {
                $full = __DIR__ . '/../../' . $p;
                if (file_exists($full)) @unlink($full);
            }
        }

        // On doit re-fetcher photos depuis row — row n'avait que user_id, re-lire
        $del = $this->db->prepare("DELETE FROM suivi_pansement WHERE id = ?");
        $del->execute([$id]);

        $this->json(['success' => true, 'message' => 'Entrée supprimée.']);
    }
}
