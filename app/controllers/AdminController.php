<?php
/* ============================================================================
   FICHIER : app/controllers/AdminController.php
   Gestion administrative : Services, Chambres, Lits, Permissions
   ============================================================================ */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../services/AuditService.php';

class AdminController {
    private $db;
    private $audit;

    public function __construct() {
        $this->db    = (new Database())->getConnection();
        $this->audit = new AuditService();

        if (!isset($_SESSION['user_id'])) {
            header('Location: ' . BASE_URL . 'login');
            exit;
        }
        if (!in_array($_SESSION['user_role'] ?? '', ['ADMIN'])) {
            header('Location: ' . BASE_URL . 'dashboard?error=acces_refuse');
            exit;
        }
    }

    // ── SERVICES ─────────────────────────────────────────────────────────────

    public function services() {
        $services = $this->db->query("
            SELECT s.*,
                   COUNT(DISTINCT c.id)   as nb_chambres,
                   COUNT(DISTINCT l.id)   as nb_lits,
                   COUNT(DISTINCT p.id)   as nb_patients,
                   SUM(CASE WHEN l.statut='OCCUPE' THEN 1 ELSE 0 END) as lits_occupes
            FROM services s
            LEFT JOIN chambres c ON c.service_id = s.id
            LEFT JOIN lits l     ON l.chambre_id = c.id
            LEFT JOIN patients p ON p.service_id = s.id AND p.actif = 1
            GROUP BY s.id, s.nom_service
            ORDER BY s.nom_service ASC
        ")->fetchAll(PDO::FETCH_ASSOC);

        require_once __DIR__ . '/../views/admin/gestion_services.php';
    }

    public function saveService() {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['success' => false]); exit; }

        $id  = (int)($_POST['id'] ?? 0);
        $nom = trim($_POST['nom_service'] ?? '');

        if (empty($nom)) { echo json_encode(['success' => false, 'message' => 'Nom requis']); exit; }

        try {
            if ($id) {
                $stmt = $this->db->prepare("UPDATE services SET nom_service = ? WHERE id = ?");
                $stmt->execute([$nom, $id]);
                $action = 'UPDATE';
            } else {
                $stmt = $this->db->prepare("INSERT INTO services (nom_service) VALUES (?)");
                $stmt->execute([$nom]);
                $id = $this->db->lastInsertId();
                $action = 'INSERT';
            }
            $this->audit->logAction($action, 'services', $id, null, "Service: $nom");
            echo json_encode(['success' => true, 'id' => $id]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    public function deleteService($id) {
        header('Content-Type: application/json');
        try {
            // Vérifie qu'il n'y a pas de chambres attachées
            $nb = $this->db->prepare("SELECT COUNT(*) FROM chambres WHERE service_id = ?");
            $nb->execute([$id]);
            if ($nb->fetchColumn() > 0) {
                echo json_encode(['success' => false, 'message' => 'Impossible : des chambres sont liées à ce service.']);
                exit;
            }
            $this->db->prepare("DELETE FROM services WHERE id = ?")->execute([$id]);
            $this->audit->logAction('DELETE', 'services', $id, null, "Suppression service #$id");
            echo json_encode(['success' => true]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    // ── CHAMBRES & LITS ───────────────────────────────────────────────────────

    public function lits() {
        $services = $this->db->query("SELECT * FROM services ORDER BY nom_service ASC")->fetchAll(PDO::FETCH_ASSOC);

        $chambres = $this->db->query("
            SELECT c.*,
                   s.nom_service,
                   COUNT(l.id) as nb_lits,
                   SUM(CASE WHEN l.statut='OCCUPE' THEN 1 ELSE 0 END) as lits_occupes
            FROM chambres c
            JOIN services s ON c.service_id = s.id
            LEFT JOIN lits l ON l.chambre_id = c.id
            GROUP BY c.id
            ORDER BY s.nom_service ASC, c.nom_chambre ASC
        ")->fetchAll(PDO::FETCH_ASSOC);

        // Récupérer les lits avec info patient si occupé
        $lits = $this->db->query("
            SELECT l.*, c.nom_chambre, s.nom_service, s.id as service_id,
                   p.nom as patient_nom, p.prenom as patient_prenom, p.dossier_numero
            FROM lits l
            JOIN chambres c ON l.chambre_id = c.id
            JOIN services s ON c.service_id = s.id
            LEFT JOIN patients p ON l.patient_id = p.id
            ORDER BY s.nom_service ASC, c.nom_chambre ASC, l.nom_lit ASC
        ")->fetchAll(PDO::FETCH_ASSOC);

        require_once __DIR__ . '/../views/admin/gestion_lits.php';
    }

    public function saveChambre() {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['success' => false]); exit; }

        $id         = (int)($_POST['id'] ?? 0);
        $service_id = (int)($_POST['service_id'] ?? 0);
        $nom        = trim($_POST['nom_chambre'] ?? '');
        $type       = $_POST['type_chambre'] ?? 'COMMUNE';

        if (!$service_id || empty($nom)) { echo json_encode(['success' => false, 'message' => 'Données incomplètes']); exit; }

        try {
            if ($id) {
                $stmt = $this->db->prepare("UPDATE chambres SET service_id=?, nom_chambre=?, type_chambre=? WHERE id=?");
                $stmt->execute([$service_id, $nom, $type, $id]);
            } else {
                $stmt = $this->db->prepare("INSERT INTO chambres (service_id, nom_chambre, type_chambre) VALUES (?,?,?)");
                $stmt->execute([$service_id, $nom, $type]);
                $id = $this->db->lastInsertId();
            }
            $this->audit->logAction($id ? 'UPDATE' : 'INSERT', 'chambres', $id, null, "Chambre: $nom");
            echo json_encode(['success' => true, 'id' => $id]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    public function deleteChambre($id) {
        header('Content-Type: application/json');
        try {
            $nb = $this->db->prepare("SELECT COUNT(*) FROM lits WHERE chambre_id = ?");
            $nb->execute([$id]);
            if ($nb->fetchColumn() > 0) {
                echo json_encode(['success' => false, 'message' => 'Impossible : des lits sont associés à cette chambre.']);
                exit;
            }
            $this->db->prepare("DELETE FROM chambres WHERE id = ?")->execute([$id]);
            echo json_encode(['success' => true]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    public function saveLit() {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['success' => false]); exit; }

        $id         = (int)($_POST['id'] ?? 0);
        $chambre_id = (int)($_POST['chambre_id'] ?? 0);
        $nom        = trim($_POST['nom_lit'] ?? '');
        $statut     = $_POST['statut'] ?? 'DISPONIBLE';

        if (!$chambre_id || empty($nom)) { echo json_encode(['success' => false, 'message' => 'Données incomplètes']); exit; }

        try {
            if ($id) {
                $stmt = $this->db->prepare("UPDATE lits SET chambre_id=?, nom_lit=?, statut=? WHERE id=?");
                $stmt->execute([$chambre_id, $nom, $statut, $id]);
            } else {
                $stmt = $this->db->prepare("INSERT INTO lits (chambre_id, nom_lit, statut) VALUES (?,?,?)");
                $stmt->execute([$chambre_id, $nom, $statut]);
                $id = $this->db->lastInsertId();
            }
            $this->audit->logAction($id ? 'UPDATE' : 'INSERT', 'lits', $id, null, "Lit: $nom");
            echo json_encode(['success' => true, 'id' => $id]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    public function deleteLit($id) {
        header('Content-Type: application/json');
        try {
            // Vérifie que le lit n'est pas occupé
            $lit = $this->db->prepare("SELECT statut, nom_lit FROM lits WHERE id = ?");
            $lit->execute([$id]);
            $row = $lit->fetch(PDO::FETCH_ASSOC);
            if ($row && $row['statut'] === 'OCCUPE') {
                echo json_encode(['success' => false, 'message' => 'Impossible : le lit ' . $row['nom_lit'] . ' est actuellement occupé.']);
                exit;
            }
            $this->db->prepare("DELETE FROM lits WHERE id = ?")->execute([$id]);
            echo json_encode(['success' => true]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    // ── RÔLES & PERMISSIONS ───────────────────────────────────────────────────

    public function permissions() {
        $roles = ['ADMIN', 'MEDECIN', 'INFIRMIER', 'SECRETAIRE', 'LABORANTIN', 'PHARMACIEN', 'PARAMETRES', 'DIRECTEUR'];

        $modules = [
            'patients'        => 'Dossiers Patients',
            'consultations'   => 'Consultations',
            'hospitalisations'=> 'Hospitalisations',
            'laboratoire'     => 'Laboratoire',
            'pharmacie'       => 'Pharmacie',
            'imagerie'        => 'Imagerie Médicale',
            'bloc'            => 'Bloc Opératoire',
            'urgences'        => 'Urgences',
            'utilisateurs'    => 'Gestion Utilisateurs',
            'statistiques'    => 'Statistiques',
            'facturation'     => 'Facturation',
        ];

        // Charger les permissions existantes → tableau [role][module][permission] = true/false
        $perms_raw = $this->db->query("SELECT * FROM role_permissions")->fetchAll(PDO::FETCH_ASSOC);
        $matrix = [];
        foreach ($perms_raw as $p) {
            $matrix[$p['role']][$p['module']] = $p['permission'];
        }

        require_once __DIR__ . '/../views/admin/gestion_permissions.php';
    }

    public function savePermission() {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['success' => false]); exit; }

        $role       = $_POST['role']       ?? '';
        $module     = $_POST['module']     ?? '';
        $permission = $_POST['permission'] ?? '';  // READ, write, delete, admin, or '' (remove)

        if (empty($role) || empty($module)) { echo json_encode(['success' => false, 'message' => 'Données incomplètes']); exit; }

        try {
            if (empty($permission)) {
                $this->db->prepare("DELETE FROM role_permissions WHERE role = ? AND module = ?")
                         ->execute([$role, $module]);
            } else {
                // UPSERT
                $stmt = $this->db->prepare("
                    INSERT INTO role_permissions (role, module, permission) VALUES (?, ?, ?)
                    ON DUPLICATE KEY UPDATE permission = VALUES(permission)
                ");
                $stmt->execute([$role, $module, $permission]);
            }
            $this->audit->logAction('UPDATE', 'role_permissions', 0, null, "Perm: $role/$module → $permission");
            echo json_encode(['success' => true]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    // ── PHARMACIE ────────────────────────────────────────────────────────────

    public function pharmacie() {
        $medicaments = $this->db->query("
            SELECT id, code, nom AS designation, nom, forme, dosage, quantite, unite, seuil_alerte, prix_unitaire
            FROM medicaments
            ORDER BY nom ASC
        ")->fetchAll(PDO::FETCH_ASSOC);
        require_once __DIR__ . '/../views/admin/gestion_pharmacie.php';
    }

    public function saveMedicament() {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['success' => false]); exit; }

        $id    = (int)($_POST['id'] ?? 0);
        $nom   = trim($_POST['nom'] ?? '');
        if (empty($nom)) { echo json_encode(['success' => false, 'message' => 'Le nom est requis']); exit; }

        $code   = trim($_POST['code']          ?? '') ?: null;
        $forme  = trim($_POST['forme']         ?? '') ?: null;
        $dosage = trim($_POST['dosage']        ?? '') ?: null;
        $qte    = max(0, (int)($_POST['quantite']    ?? 0));
        $unite  = trim($_POST['unite']         ?? '') ?: null;
        $seuil  = max(0, (int)($_POST['seuil_alerte'] ?? 10));
        $prix   = max(0, (float)($_POST['prix_unitaire'] ?? 0));

        try {
            if ($id) {
                $stmt = $this->db->prepare("UPDATE medicaments SET code=?, nom=?, forme=?, dosage=?, quantite=?, unite=?, seuil_alerte=?, prix_unitaire=? WHERE id=?");
                $stmt->execute([$code, $nom, $forme, $dosage, $qte, $unite, $seuil, $prix, $id]);
                $this->audit->logAction('UPDATE', 'medicaments', $id, null, "Médicament: $nom");
            } else {
                $stmt = $this->db->prepare("INSERT INTO medicaments (code, nom, forme, dosage, quantite, unite, seuil_alerte, prix_unitaire) VALUES (?,?,?,?,?,?,?,?)");
                $stmt->execute([$code, $nom, $forme, $dosage, $qte, $unite, $seuil, $prix]);
                $id = $this->db->lastInsertId();
                $this->audit->logAction('INSERT', 'medicaments', $id, null, "Nouveau médicament: $nom");
            }
            echo json_encode(['success' => true, 'id' => $id]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    public function deleteMedicament() {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['success' => false]); exit; }

        $id = (int)($_POST['id'] ?? 0);
        if (!$id) { echo json_encode(['success' => false, 'message' => 'ID invalide']); exit; }

        try {
            $stmt = $this->db->prepare("SELECT nom FROM medicaments WHERE id = ?");
            $stmt->execute([$id]);
            $med = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$med) { echo json_encode(['success' => false, 'message' => 'Médicament introuvable']); exit; }

            $this->db->prepare("DELETE FROM medicaments WHERE id = ?")->execute([$id]);
            $this->audit->logAction('DELETE', 'medicaments', $id, null, "Suppression médicament: {$med['nom']}");
            echo json_encode(['success' => true]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    // ── LABORATOIRE ──────────────────────────────────────────────────────────

    private function ensureCategoriesTable() {
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS categories_examens (
                id      INT AUTO_INCREMENT PRIMARY KEY,
                nom     VARCHAR(50) NOT NULL UNIQUE,
                couleur VARCHAR(20) NOT NULL DEFAULT 'secondary',
                icone   VARCHAR(50) NOT NULL DEFAULT 'grid-fill'
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
        $count = $this->db->query("SELECT COUNT(*) FROM categories_examens")->fetchColumn();
        if ($count == 0) {
            $defaults = [
                ['HEMATOLOGIE',   'danger',    'droplet-fill'],
                ['BIOCHIMIE',     'primary',   'flask-fill'],
                ['IMMUNOLOGIE',   'info',      'shield-fill-check'],
                ['MICROBIOLOGIE', 'warning',   'virus'],
                ['PARASITOLOGIE', 'success',   'bug-fill'],
                ['AUTRE',         'secondary', 'grid-fill'],
            ];
            $stmt = $this->db->prepare("INSERT IGNORE INTO categories_examens (nom, couleur, icone) VALUES (?,?,?)");
            foreach ($defaults as $d) $stmt->execute($d);
        }
    }

    public function laboratoire() {
        $this->ensureCategoriesTable();
        $categories = $this->db->query("SELECT * FROM categories_examens ORDER BY nom ASC")->fetchAll(PDO::FETCH_ASSOC);
        $examens = $this->db->query("
            SELECT id, code, nom, categorie, type_prelevement, delai_rendu_heures,
                   a_jeun_requis, prix, disponible, valeur_normale_min, valeur_normale_max, unite
            FROM examens_laboratoire
            ORDER BY categorie ASC, nom ASC
        ")->fetchAll(PDO::FETCH_ASSOC);
        require_once __DIR__ . '/../views/admin/gestion_laboratoire.php';
    }

    public function saveCategorie() {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['success' => false]); exit; }

        $this->ensureCategoriesTable();
        $nom     = strtoupper(trim($_POST['nom']     ?? ''));
        $couleur = trim($_POST['couleur'] ?? 'secondary');
        $icone   = trim($_POST['icone']   ?? 'grid-fill');

        if (empty($nom)) { echo json_encode(['success' => false, 'message' => 'Nom requis']); exit; }
        if (!preg_match('/^[A-Z0-9\-_ ]+$/', $nom)) {
            echo json_encode(['success' => false, 'message' => 'Nom invalide (lettres majuscules, chiffres, tirets)']); exit;
        }
        $allowed = ['primary','secondary','success','danger','warning','info','dark'];
        if (!in_array($couleur, $allowed)) $couleur = 'secondary';

        try {
            $id = (int)($_POST['id'] ?? 0);
            if ($id) {
                $this->db->prepare("UPDATE categories_examens SET nom=?, couleur=?, icone=? WHERE id=?")->execute([$nom, $couleur, $icone, $id]);
            } else {
                $this->db->prepare("INSERT INTO categories_examens (nom, couleur, icone) VALUES (?,?,?)")->execute([$nom, $couleur, $icone]);
                $id = $this->db->lastInsertId();
            }
            $this->audit->logAction('INSERT', 'categories_examens', $id, null, "Catégorie: $nom");
            echo json_encode(['success' => true]);
        } catch (PDOException $e) {
            $dup = strpos($e->getMessage(), '1062') !== false;
            echo json_encode(['success' => false, 'message' => $dup ? "La catégorie « $nom » existe déjà." : $e->getMessage()]);
        }
        exit;
    }

    public function deleteCategorie($id) {
        header('Content-Type: application/json');
        $id = (int)$id;
        try {
            $cat = $this->db->prepare("SELECT nom FROM categories_examens WHERE id = ?");
            $cat->execute([$id]);
            $row = $cat->fetch(PDO::FETCH_ASSOC);
            if (!$row) { echo json_encode(['success' => false, 'message' => 'Introuvable']); exit; }

            $nb = $this->db->prepare("SELECT COUNT(*) FROM examens_laboratoire WHERE categorie = ?");
            $nb->execute([$row['nom']]);
            if ($nb->fetchColumn() > 0) {
                echo json_encode(['success' => false, 'message' => "Impossible : des examens utilisent cette catégorie."]); exit;
            }
            $this->db->prepare("DELETE FROM categories_examens WHERE id = ?")->execute([$id]);
            $this->audit->logAction('DELETE', 'categories_examens', $id, null, "Suppression catégorie: {$row['nom']}");
            echo json_encode(['success' => true]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    public function saveExamen() {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['success' => false]); exit; }

        $id  = (int)($_POST['id'] ?? 0);
        $nom = trim($_POST['nom'] ?? '');
        if (empty($nom)) { echo json_encode(['success' => false, 'message' => 'Le nom est requis']); exit; }

        $code       = trim($_POST['code']        ?? '') ?: null;
        $categorie  = $_POST['categorie']        ?? 'BIOCHIMIE';
        $prelevement= $_POST['type_prelevement'] ?? 'SANG';
        $delai      = max(1, (int)($_POST['delai_rendu_heures'] ?? 24));
        $jeun       = isset($_POST['a_jeun_requis']) ? 1 : 0;
        $prix       = max(0, (float)($_POST['prix'] ?? 0));
        $disponible = isset($_POST['disponible']) ? 1 : 0;
        $vmin       = strlen($_POST['valeur_normale_min'] ?? '') ? (float)$_POST['valeur_normale_min'] : null;
        $vmax       = strlen($_POST['valeur_normale_max'] ?? '') ? (float)$_POST['valeur_normale_max'] : null;
        $unite      = trim($_POST['unite'] ?? '') ?: null;

        // Valider la catégorie dynamiquement depuis la table
        try {
            $this->ensureCategoriesTable();
            $cats = $this->db->query("SELECT nom FROM categories_examens")->fetchAll(PDO::FETCH_COLUMN);
        } catch (Exception $e) {
            $cats = ['HEMATOLOGIE','BIOCHIMIE','IMMUNOLOGIE','MICROBIOLOGIE','PARASITOLOGIE','AUTRE'];
        }
        $prelevements= ['SANG','URINE','SELLES','LCR','AUTRE'];
        if (!in_array($categorie, $cats))           $categorie   = 'AUTRE';
        if (!in_array($prelevement, $prelevements)) $prelevement = 'AUTRE';

        try {
            if ($id) {
                $stmt = $this->db->prepare("UPDATE examens_laboratoire SET code=?, nom=?, categorie=?, type_prelevement=?, delai_rendu_heures=?, a_jeun_requis=?, prix=?, disponible=?, valeur_normale_min=?, valeur_normale_max=?, unite=? WHERE id=?");
                $stmt->execute([$code, $nom, $categorie, $prelevement, $delai, $jeun, $prix, $disponible, $vmin, $vmax, $unite, $id]);
                $this->audit->logAction('UPDATE', 'examens_laboratoire', $id, null, "Examen: $nom");
            } else {
                $stmt = $this->db->prepare("INSERT INTO examens_laboratoire (code, nom, categorie, type_prelevement, delai_rendu_heures, a_jeun_requis, prix, disponible, valeur_normale_min, valeur_normale_max, unite) VALUES (?,?,?,?,?,?,?,?,?,?,?)");
                $stmt->execute([$code, $nom, $categorie, $prelevement, $delai, $jeun, $prix, $disponible, $vmin, $vmax, $unite]);
                $id = $this->db->lastInsertId();
                $this->audit->logAction('INSERT', 'examens_laboratoire', $id, null, "Nouvel examen: $nom");
            }
            echo json_encode(['success' => true, 'id' => $id]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    public function deleteExamen($id) {
        header('Content-Type: application/json');
        try {
            $nb = $this->db->prepare("SELECT COUNT(*) FROM demandes_laboratoire WHERE examen_id = ?");
            $nb->execute([$id]);
            if ($nb->fetchColumn() > 0) {
                echo json_encode(['success' => false, 'message' => 'Impossible : des demandes sont liées à cet examen.']);
                exit;
            }
            $this->db->prepare("DELETE FROM examens_laboratoire WHERE id = ?")->execute([$id]);
            $this->audit->logAction('DELETE', 'examens_laboratoire', $id, null, "Suppression examen #$id");
            echo json_encode(['success' => true]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    // ── JOURNAUX D'AUDIT ─────────────────────────────────────────────────────

    public function logs() {
        $action_filter = $_GET['action'] ?? 'ALL';
        $table_filter  = $_GET['table'] ?? 'ALL';
        $user_filter   = $_GET['user_id'] ?? '';
        $per_page      = 50;
        $page          = max(1, (int)($_GET['page'] ?? 1));
        $offset        = ($page - 1) * $per_page;

        $where  = '1=1';
        $params = [];
        if ($action_filter !== 'ALL') { $where .= ' AND a.action = ?'; $params[] = $action_filter; }
        if ($table_filter  !== 'ALL') { $where .= ' AND a.table_name = ?'; $params[] = $table_filter; }
        if ($user_filter !== '')      { $where .= ' AND a.user_id = ?'; $params[] = (int)$user_filter; }

        $total = $this->db->prepare("SELECT COUNT(*) FROM audit_logs a WHERE $where");
        $total->execute($params);
        $total_rows = (int)$total->fetchColumn();
        $total_pages = max(1, ceil($total_rows / $per_page));

        $stmt = $this->db->prepare("
            SELECT a.*, u.nom, u.prenom, u.username
            FROM audit_logs a
            LEFT JOIN users u ON a.user_id = u.id
            WHERE $where
            ORDER BY a.created_at DESC
            LIMIT $per_page OFFSET $offset
        ");
        $stmt->execute($params);
        $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Pour les filtres : actions et tables distinctes
        $actions = $this->db->query("SELECT DISTINCT action FROM audit_logs ORDER BY action")->fetchAll(PDO::FETCH_COLUMN);
        $tables  = $this->db->query("SELECT DISTINCT table_name FROM audit_logs ORDER BY table_name")->fetchAll(PDO::FETCH_COLUMN);
        $users   = $this->db->query("SELECT id, CONCAT(prenom,' ',nom) as full_name FROM users ORDER BY nom")->fetchAll(PDO::FETCH_ASSOC);

        $filters = [
            'action'  => $action_filter,
            'table'   => $table_filter,
            'user_id' => $user_filter,
        ];

        require_once __DIR__ . '/../views/admin/logs.php';
    }
}
