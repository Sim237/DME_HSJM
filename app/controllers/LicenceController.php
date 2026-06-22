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
   FICHIER : app/controllers/LicenceController.php
   Gestion des clés de licence — ADMIN seulement
   ============================================================================ */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../services/AuditService.php';
require_once __DIR__ . '/../services/CsrfService.php';

class LicenceController {

    private PDO $db;
    private AuditService $audit;

    public function __construct() {
        $this->db    = (new Database())->getConnection();
        $this->audit = new AuditService();
        $this->ensureSchema();
        $this->guardAdmin();
    }

    // ── Accès SUPER_ADMIN uniquement ─────────────────────────────────────────
    private function guardAdmin(): void {
        if (empty($_SESSION['logged_in']) || strtoupper($_SESSION['user_role'] ?? '') !== 'SUPER_ADMIN') {
            http_response_code(403);
            die('<h2>Accès réservé au Super Administrateur.</h2>');
        }
    }

    // ── Création auto de la table (idempotent) ────────────────────────────────
    private function ensureSchema(): void {
        try {
            $exists = (int)$this->db->query(
                "SELECT COUNT(*) FROM information_schema.TABLES
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'licences'"
            )->fetchColumn();
            if (!$exists) {
                $this->db->exec("
                    CREATE TABLE licences (
                      id          INT AUTO_INCREMENT PRIMARY KEY,
                      cle         VARCHAR(128) NOT NULL,
                      description VARCHAR(255) NULL,
                      date_debut  DATE NOT NULL,
                      date_fin    DATE NOT NULL,
                      actif       TINYINT(1) DEFAULT 1,
                      created_by  INT NULL,
                      created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                      UNIQUE KEY uk_cle (cle),
                      KEY idx_check (actif, date_fin)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
                ");
            }
        } catch (\Throwable $e) {
            error_log('[Licence] ensureSchema: ' . $e->getMessage());
        }
    }

    // ── Page principale ───────────────────────────────────────────────────────
    public function index(): void {
        $adminPage = 'licences';

        // Toutes les licences, plus récentes en premier
        $licences = [];
        try {
            $licences = $this->db->query("
                SELECT l.*, u.nom AS cree_par_nom, u.prenom AS cree_par_prenom
                FROM licences l
                LEFT JOIN users u ON u.id = l.created_by
                ORDER BY l.created_at DESC
            ")->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Throwable $e) { error_log('[Licence] index: ' . $e->getMessage()); }

        // Statut de la licence active
        $licenceActive = $this->getLicenceActive();

        require_once __DIR__ . '/../../config/config.php';
        require_once __DIR__ . '/../views/admin/partials/admin_nav.php';
        require_once __DIR__ . '/../views/admin/licences.php';
        require_once __DIR__ . '/../views/layouts/footer.php';
    }

    // ── Créer une licence ─────────────────────────────────────────────────────
    public function creer(): void {
        header('Content-Type: application/json; charset=utf-8');
        CsrfService::check();

        $cle         = trim($_POST['cle']         ?? '');
        $description = trim($_POST['description'] ?? '');
        $dateDebut   = trim($_POST['date_debut']  ?? '');
        $dateFin     = trim($_POST['date_fin']    ?? '');

        // Validations
        if (!$cle) {
            echo json_encode(['success' => false, 'message' => 'La clé de licence est obligatoire.']); exit;
        }
        if (strlen($cle) < 8) {
            echo json_encode(['success' => false, 'message' => 'La clé doit faire au moins 8 caractères.']); exit;
        }
        if (!$dateDebut || !$dateFin) {
            echo json_encode(['success' => false, 'message' => 'Les dates de début et de fin sont obligatoires.']); exit;
        }
        if ($dateFin <= $dateDebut) {
            echo json_encode(['success' => false, 'message' => 'La date de fin doit être postérieure à la date de début.']); exit;
        }

        try {
            $stmt = $this->db->prepare("
                INSERT INTO licences (cle, description, date_debut, date_fin, actif, created_by)
                VALUES (?, ?, ?, ?, 1, ?)
            ");
            $stmt->execute([$cle, $description ?: null, $dateDebut, $dateFin, $_SESSION['user_id'] ?? null]);
            $id = (int)$this->db->lastInsertId();

            // Invalider le cache de licence en session
            unset($_SESSION['_lic_ok_until']);

            $jours = (int)((strtotime($dateFin) - strtotime($dateDebut)) / 86400);
            $this->audit->log($_SESSION['user_id'] ?? 0, 'CREATE', 'licences', $id,
                "Création licence : $cle · valide du $dateDebut au $dateFin ($jours j)");

            echo json_encode([
                'success'    => true,
                'message'    => "Licence créée avec succès ($jours jours de validité).",
                'id'         => $id,
                'cle'        => $cle,
                'date_debut' => $dateDebut,
                'date_fin'   => $dateFin,
                'description'=> $description,
            ]);
        } catch (\PDOException $e) {
            if (str_contains($e->getMessage(), 'Duplicate')) {
                echo json_encode(['success' => false, 'message' => 'Cette clé existe déjà.']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Erreur base de données : ' . $e->getMessage()]);
            }
        }
        exit;
    }

    // ── Révoquer (désactiver) une licence ─────────────────────────────────────
    public function revoquer(int $id): void {
        header('Content-Type: application/json; charset=utf-8');
        CsrfService::check();

        try {
            $stmt = $this->db->prepare("UPDATE licences SET actif = 0 WHERE id = ?");
            $stmt->execute([$id]);
            unset($_SESSION['_lic_ok_until']);
            $this->audit->log($_SESSION['user_id'] ?? 0, 'UPDATE', 'licences', $id, "Révocation licence #$id");
            echo json_encode(['success' => true, 'message' => 'Licence révoquée.']);
        } catch (\Throwable $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    // ── Réactiver une licence révoquée ────────────────────────────────────────
    public function reactiver(int $id): void {
        header('Content-Type: application/json; charset=utf-8');
        CsrfService::check();

        try {
            $stmt = $this->db->prepare("UPDATE licences SET actif = 1 WHERE id = ?");
            $stmt->execute([$id]);
            unset($_SESSION['_lic_ok_until']);
            $this->audit->log($_SESSION['user_id'] ?? 0, 'UPDATE', 'licences', $id, "Réactivation licence #$id");
            echo json_encode(['success' => true, 'message' => 'Licence réactivée.']);
        } catch (\Throwable $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    // ── Supprimer définitivement ───────────────────────────────────────────────
    public function supprimer(int $id): void {
        header('Content-Type: application/json; charset=utf-8');
        CsrfService::check();

        try {
            $row = $this->db->prepare("SELECT cle FROM licences WHERE id = ?");
            $row->execute([$id]);
            $cle = $row->fetchColumn();

            $this->db->prepare("DELETE FROM licences WHERE id = ?")->execute([$id]);
            unset($_SESSION['_lic_ok_until']);
            $this->audit->log($_SESSION['user_id'] ?? 0, 'DELETE', 'licences', $id,
                "Suppression définitive licence #$id ($cle)");
            echo json_encode(['success' => true]);
        } catch (\Throwable $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    // ── Statut JSON (pour le widget admin) ────────────────────────────────────
    public function statutJson(): void {
        header('Content-Type: application/json; charset=utf-8');
        $lic = $this->getLicenceActive();
        echo json_encode($lic ?? ['valide' => false, 'message' => 'Aucune licence active.']);
        exit;
    }

    // ── Générer une clé aléatoire ─────────────────────────────────────────────
    public function genererCle(): void {
        header('Content-Type: application/json; charset=utf-8');
        $cle = strtoupper(implode('-', [
            $this->randStr(4), $this->randStr(4),
            $this->randStr(4), $this->randStr(4),
        ]));
        echo json_encode(['cle' => $cle]);
        exit;
    }

    // ── Helpers ───────────────────────────────────────────────────────────────
    private function randStr(int $len): string {
        $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $s = '';
        for ($i = 0; $i < $len; $i++) $s .= $chars[random_int(0, strlen($chars) - 1)];
        return $s;
    }

    /**
     * Retourne la licence active la plus récente, ou null.
     * Utilisé aussi par le middleware via appel statique.
     */
    public static function getLicenceActiveStatic(): ?array {
        try {
            $db = (new Database())->getConnection();
            return $db->query("
                SELECT * FROM licences
                WHERE actif = 1 AND date_debut <= CURDATE() AND date_fin >= CURDATE()
                ORDER BY date_fin DESC LIMIT 1
            ")->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (\Throwable $e) { return null; }
    }

    private function getLicenceActive(): ?array {
        return self::getLicenceActiveStatic();
    }
}
