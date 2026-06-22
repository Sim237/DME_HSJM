<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../services/CsrfService.php';
require_once __DIR__ . '/../services/GardeService.php';

class GardeController
{
    private PDO $db;

    private const ROLES_ADMIN = ['ADMIN', 'SUPER_ADMIN', 'DIRECTEUR', 'MEDECIN_CHEF'];

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $this->db = (new Database())->getConnection();
        $this->ensureSchema();
    }

    // ── Accès réservé admin ───────────────────────────────────────────────
    private function guard(): void
    {
        if (empty($_SESSION['logged_in'])) {
            header('Location: ' . BASE_URL . 'login'); exit;
        }
        $role = strtoupper($_SESSION['user_role'] ?? '');
        if (!in_array($role, self::ROLES_ADMIN)) {
            http_response_code(403);
            die("<div style='padding:60px;text-align:center;font-family:sans-serif;'>
                 <h2>403 — Accès réservé à l'administration</h2></div>");
        }
    }

    // ── Création de la table si inexistante ───────────────────────────────
    private function ensureSchema(): void
    {
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS gardes (
                id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                medecin_id   INT UNSIGNED NOT NULL,
                date_debut   DATETIME     NOT NULL,
                date_fin     DATETIME     NOT NULL,
                type_garde   ENUM('nuit','weekend','jour_ferie','remplacement','urgence')
                             NOT NULL DEFAULT 'nuit',
                statut       ENUM('planifie','en_cours','termine','annule')
                             NOT NULL DEFAULT 'planifie',
                notes        TEXT,
                created_by   INT UNSIGNED,
                created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_medecin (medecin_id),
                INDEX idx_statut_dates (statut, date_debut, date_fin)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        // Met à jour automatiquement les statuts selon les dates
        $this->syncStatuts();
    }

    /** Passe automatiquement en_cours / terminé selon date_debut/date_fin. */
    private function syncStatuts(): void
    {
        try {
            $this->db->exec("
                UPDATE gardes
                   SET statut = 'en_cours'
                 WHERE statut = 'planifie'
                   AND NOW() >= date_debut
                   AND NOW() <= date_fin
            ");
            $this->db->exec("
                UPDATE gardes
                   SET statut = 'termine'
                 WHERE statut IN ('planifie','en_cours')
                   AND NOW() > date_fin
            ");
        } catch (Exception $e) { /* silencieux */ }
    }

    // ─────────────────────────────────────────────────────────────────────
    //  DASHBOARD ADMIN GARDES
    // ─────────────────────────────────────────────────────────────────────
    public function dashboard(): void
    {
        $this->guard();

        // Gardes en cours
        $gardesEnCours = $this->db->query("
            SELECT g.*, u.nom, u.prenom, u.role AS medecin_role,
                   cb.nom AS createur_nom, cb.prenom AS createur_prenom
              FROM gardes g
              JOIN users u ON u.id = g.medecin_id
              LEFT JOIN users cb ON cb.id = g.created_by
             WHERE g.statut = 'en_cours'
             ORDER BY g.date_debut ASC
        ")->fetchAll(PDO::FETCH_ASSOC);

        // Gardes planifiées (futures)
        $gardesPlanifiees = $this->db->query("
            SELECT g.*, u.nom, u.prenom, u.role AS medecin_role,
                   cb.nom AS createur_nom, cb.prenom AS createur_prenom
              FROM gardes g
              JOIN users u ON u.id = g.medecin_id
              LEFT JOIN users cb ON cb.id = g.created_by
             WHERE g.statut = 'planifie'
             ORDER BY g.date_debut ASC
             LIMIT 50
        ")->fetchAll(PDO::FETCH_ASSOC);

        // Historique (30 derniers jours)
        $historique = $this->db->query("
            SELECT g.*, u.nom, u.prenom, u.role AS medecin_role
              FROM gardes g
              JOIN users u ON u.id = g.medecin_id
             WHERE g.statut IN ('termine','annule')
               AND g.date_debut >= DATE_SUB(NOW(), INTERVAL 30 DAY)
             ORDER BY g.date_debut DESC
             LIMIT 100
        ")->fetchAll(PDO::FETCH_ASSOC);

        // Médecins disponibles pour planifier une garde
        $medecins = $this->db->query("
            SELECT id, nom, prenom, role
              FROM users
             WHERE role IN ('MEDECIN','MEDECIN_CHEF','MEDECIN_URGENTISTE',
                            'GENERALISTE','GYNECO','CHIRURGIEN','PEDIATRE',
                            'SPECIALISTE','ANESTHESISTE')
               AND actif = 1
             ORDER BY nom, prenom
        ")->fetchAll(PDO::FETCH_ASSOC);

        require_once __DIR__ . '/../views/admin/gardes.php';
    }

    // ─────────────────────────────────────────────────────────────────────
    //  CRÉER UNE GARDE  (POST)
    // ─────────────────────────────────────────────────────────────────────
    public function creer(): void
    {
        $this->guard();
        CsrfService::validate();

        $medecinId  = (int)($_POST['medecin_id']  ?? 0);
        $dateDebut  = trim($_POST['date_debut']    ?? '');
        $dateFin    = trim($_POST['date_fin']      ?? '');
        $typeGarde  = trim($_POST['type_garde']    ?? 'nuit');
        $notes      = trim($_POST['notes']         ?? '');
        $createdBy  = (int)($_SESSION['user_id']   ?? 0);

        if (!$medecinId || !$dateDebut || !$dateFin) {
            $_SESSION['garde_flash'] = ['type' => 'error', 'msg' => 'Champs obligatoires manquants.'];
            header('Location: ' . BASE_URL . 'admin/gardes'); exit;
        }
        if (strtotime($dateFin) <= strtotime($dateDebut)) {
            $_SESSION['garde_flash'] = ['type' => 'error', 'msg' => 'La date de fin doit être après la date de début.'];
            header('Location: ' . BASE_URL . 'admin/gardes'); exit;
        }

        // Statut initial selon date
        $statut = (strtotime($dateDebut) <= time() && strtotime($dateFin) >= time())
                  ? 'en_cours' : 'planifie';

        $this->db->prepare("
            INSERT INTO gardes (medecin_id, date_debut, date_fin, type_garde, statut, notes, created_by)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ")->execute([$medecinId, $dateDebut, $dateFin, $typeGarde, $statut, $notes ?: null, $createdBy ?: null]);

        GardeService::reset();
        $_SESSION['garde_flash'] = ['type' => 'success', 'msg' => 'Garde planifiée avec succès.'];
        header('Location: ' . BASE_URL . 'admin/gardes'); exit;
    }

    // ─────────────────────────────────────────────────────────────────────
    //  TERMINER UNE GARDE IMMÉDIATEMENT  (POST JSON)
    // ─────────────────────────────────────────────────────────────────────
    public function terminer(): void
    {
        $this->guard();
        header('Content-Type: application/json');
        if (!CsrfService::validate()) { echo json_encode(['error' => 'CSRF']); return; }

        $id = (int)($_POST['garde_id'] ?? 0);
        if (!$id) { echo json_encode(['error' => 'ID invalide']); return; }

        $this->db->prepare(
            "UPDATE gardes SET statut='termine', date_fin=NOW() WHERE id=? AND statut IN ('planifie','en_cours')"
        )->execute([$id]);

        GardeService::reset();
        echo json_encode(['ok' => true]);
    }

    // ─────────────────────────────────────────────────────────────────────
    //  MODIFIER UNE GARDE  (POST JSON)
    // ─────────────────────────────────────────────────────────────────────
    public function modifier(): void
    {
        $this->guard();
        header('Content-Type: application/json');
        if (!CsrfService::validate()) { echo json_encode(['error' => 'CSRF']); return; }

        $id         = (int)($_POST['garde_id']   ?? 0);
        $medecinId  = (int)($_POST['medecin_id'] ?? 0);
        $dateDebut  = trim($_POST['date_debut']   ?? '');
        $dateFin    = trim($_POST['date_fin']     ?? '');
        $typeGarde  = trim($_POST['type_garde']   ?? '');
        $notes      = trim($_POST['notes']        ?? '');

        if (!$id || !$medecinId || !$dateDebut || !$dateFin) {
            echo json_encode(['error' => 'Champs obligatoires manquants.']); return;
        }
        if (strtotime($dateFin) <= strtotime($dateDebut)) {
            echo json_encode(['error' => 'La date de fin doit être après la date de début.']); return;
        }

        // Vérifier que la garde existe et n'est pas terminée/annulée
        $stmt = $this->db->prepare("SELECT statut FROM gardes WHERE id=?");
        $stmt->execute([$id]);
        $garde = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$garde) { echo json_encode(['error' => 'Garde introuvable.']); return; }
        if (in_array($garde['statut'], ['termine', 'annule'])) {
            echo json_encode(['error' => 'Impossible de modifier une garde terminée ou annulée.']); return;
        }

        // Recalculer le statut selon les nouvelles dates
        $now = time();
        $debut = strtotime($dateDebut);
        $fin   = strtotime($dateFin);
        if ($now > $fin) {
            $newStatut = 'termine';
        } elseif ($now >= $debut && $now <= $fin) {
            $newStatut = 'en_cours';
        } else {
            $newStatut = 'planifie';
        }

        $validTypes = ['nuit','weekend','jour_ferie','remplacement','urgence'];
        if (!in_array($typeGarde, $validTypes)) $typeGarde = 'nuit';

        $this->db->prepare("
            UPDATE gardes
               SET medecin_id=?, date_debut=?, date_fin=?, type_garde=?, statut=?, notes=?
             WHERE id=? AND statut NOT IN ('termine','annule')
        ")->execute([$medecinId, $dateDebut, $dateFin, $typeGarde, $newStatut, $notes ?: null, $id]);

        GardeService::reset();
        echo json_encode(['ok' => true]);
    }

    // ─────────────────────────────────────────────────────────────────────
    //  ANNULER UNE GARDE PLANIFIÉE  (POST JSON)
    // ─────────────────────────────────────────────────────────────────────
    public function annuler(): void
    {
        $this->guard();
        header('Content-Type: application/json');
        if (!CsrfService::validate()) { echo json_encode(['error' => 'CSRF']); return; }

        $id = (int)($_POST['garde_id'] ?? 0);
        $this->db->prepare(
            "UPDATE gardes SET statut='annule' WHERE id=? AND statut='planifie'"
        )->execute([$id]);

        GardeService::reset();
        echo json_encode(['ok' => true]);
    }
}
