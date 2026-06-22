<?php
/* ============================================================================
   FICHIER : app/controllers/SystemConfigController.php
   Configuration système globale de SimCare+ HSJM
   ============================================================================ */
require_once __DIR__ . '/UnifiedController.php';
require_once __DIR__ . '/../services/AuditService.php';

class SystemConfigController extends UnifiedController {

    private $audit;

    public function __construct() {
        parent::__construct();
        $this->audit = new AuditService();
    }

    /* ────────────────────────────────────────────────────────────
     * GET  admin/config
     * Affiche la page de configuration système
     * ──────────────────────────────────────────────────────────── */
    public function index(): void {
        $this->auth->requirePermission('utilisateurs', 'write'); // réservé admin

        $db = (new Database())->getConnection();
        $this->ensureTable($db);

        $rows = $db->query("
            SELECT * FROM system_config
            ORDER BY groupe ASC, libelle ASC
        ")->fetchAll(PDO::FETCH_ASSOC);

        // Regrouper par groupe
        $configs = [];
        foreach ($rows as $row) {
            $configs[$row['groupe']][] = $row;
        }

        // Libellés des groupes
        $groupeLabels = [
            'etablissement' => ['label' => 'Établissement',    'icon' => 'bi-hospital-fill',      'color' => '#1e40af'],
            'securite'      => ['label' => 'Sécurité',         'icon' => 'bi-shield-fill-check',  'color' => '#dc2626'],
            'interface'     => ['label' => 'Interface',        'icon' => 'bi-palette-fill',       'color' => '#7c3aed'],
            'facturation'   => ['label' => 'Facturation',      'icon' => 'bi-receipt-cutoff',     'color' => '#059669'],
            'systeme'       => ['label' => 'Système',          'icon' => 'bi-gear-fill',          'color' => '#d97706'],
        ];

        $adminPage = 'config';
        require_once __DIR__ . '/../views/admin/partials/admin_nav.php';
        require_once __DIR__ . '/../views/admin/config_systeme.php';
    }

    /* ────────────────────────────────────────────────────────────
     * POST admin/config/save
     * Sauvegarde un ou plusieurs paramètres
     * ──────────────────────────────────────────────────────────── */
    public function save(): void {
        header('Content-Type: application/json; charset=utf-8');
        $this->auth->requirePermission('utilisateurs', 'write');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Méthode non autorisée.']); return;
        }

        $db = (new Database())->getConnection();
        $this->ensureTable($db);

        $cle    = trim($_POST['cle']    ?? '');
        $valeur = trim($_POST['valeur'] ?? '');

        if (!$cle) {
            echo json_encode(['success' => false, 'message' => 'Clé manquante.']); return;
        }

        try {
            // Vérifier que la clé existe
            $check = $db->prepare("SELECT id, libelle, valeur FROM system_config WHERE cle = ? LIMIT 1");
            $check->execute([$cle]);
            $existing = $check->fetch(PDO::FETCH_ASSOC);
            if (!$existing) {
                echo json_encode(['success' => false, 'message' => "Paramètre '$cle' inconnu."]); return;
            }

            $db->prepare("UPDATE system_config SET valeur = ?, updated_by = ? WHERE cle = ?")
               ->execute([$valeur, $_SESSION['user_id'] ?? 0, $cle]);

            $this->audit->logAction('UPDATE', 'system_config', null, null,
                "Config modifiée · {$existing['libelle']} : «{$existing['valeur']}» → «{$valeur}»");

            echo json_encode(['success' => true, 'libelle' => $existing['libelle']]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /* ────────────────────────────────────────────────────────────
     * POST admin/config/save-groupe
     * Sauvegarde tous les paramètres d'un groupe en une requête
     * ──────────────────────────────────────────────────────────── */
    public function saveGroupe(): void {
        header('Content-Type: application/json; charset=utf-8');
        $this->auth->requirePermission('utilisateurs', 'write');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Méthode non autorisée.']); return;
        }

        $db = (new Database())->getConnection();
        $this->ensureTable($db);

        $params = $_POST['params'] ?? [];
        if (!is_array($params) || empty($params)) {
            echo json_encode(['success' => false, 'message' => 'Aucune donnée reçue.']); return;
        }

        try {
            $db->beginTransaction();
            $nb = 0;
            $stmt = $db->prepare("UPDATE system_config SET valeur = ?, updated_by = ? WHERE cle = ?");
            foreach ($params as $cle => $valeur) {
                $cle    = trim((string)$cle);
                $valeur = trim((string)$valeur);
                if (!$cle) continue;
                $stmt->execute([$valeur, $_SESSION['user_id'] ?? 0, $cle]);
                $nb++;
            }
            $db->commit();

            $this->audit->logAction('UPDATE', 'system_config', null, null,
                "Sauvegarde groupée : $nb paramètre(s) modifié(s)");

            echo json_encode(['success' => true, 'nb' => $nb]);
        } catch (PDOException $e) {
            $db->rollBack();
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /* ────────────────────────────────────────────────────────────
     * Utilitaire : helper statique pour lire un paramètre
     * Utilisation : SystemConfigController::get($db, 'hopital_nom', 'HSJM')
     * ──────────────────────────────────────────────────────────── */
    public static function get(\PDO $db, string $cle, string $defaut = ''): string {
        try {
            $stmt = $db->prepare("SELECT valeur FROM system_config WHERE cle = ? LIMIT 1");
            $stmt->execute([$cle]);
            $val = $stmt->fetchColumn();
            return ($val !== false && $val !== null) ? (string)$val : $defaut;
        } catch (\Throwable $e) {
            return $defaut;
        }
    }

    /* ────────────────────────────────────────────────────────────
     * S'assurer que la table existe (idempotent)
     * ──────────────────────────────────────────────────────────── */
    private function ensureTable(\PDO $db): void {
        try {
            $db->query("SELECT 1 FROM system_config LIMIT 1");
        } catch (\PDOException $e) {
            $db->exec("
                CREATE TABLE IF NOT EXISTS system_config (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    cle VARCHAR(100) NOT NULL UNIQUE,
                    valeur TEXT,
                    type_valeur ENUM('string','text','integer','boolean','color','email','url','image') DEFAULT 'string',
                    groupe VARCHAR(50) DEFAULT 'general',
                    libelle VARCHAR(200),
                    description TEXT,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    updated_by INT,
                    INDEX idx_groupe (groupe)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");
        }
    }
}
