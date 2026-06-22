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
        if (!in_array($_SESSION['user_role'] ?? '', ['ADMIN', 'SUPER_ADMIN'])) {
            header('Location: ' . BASE_URL . 'dashboard?error=acces_refuse');
            exit;
        }
    }

    // ── Accès réservé au SUPER_ADMIN (pas ADMIN) ─────────────────────────────
    private function requireSuperAdmin(): void {
        if (($_SESSION['user_role'] ?? '') !== 'SUPER_ADMIN') {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Action réservée au Super Administrateur.']);
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
        $this->requireSuperAdmin();
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
        $this->requireSuperAdmin();
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
            LEFT JOIN patients p ON l.occupied_by_patient_id = p.id
            ORDER BY s.nom_service ASC, c.nom_chambre ASC, l.nom_lit ASC
        ")->fetchAll(PDO::FETCH_ASSOC);

        require_once __DIR__ . '/../views/admin/gestion_lits.php';
    }

    public function saveChambre() {
        header('Content-Type: application/json');
        $this->requireSuperAdmin();
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
        $this->requireSuperAdmin();
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
        $this->requireSuperAdmin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['success' => false]); exit; }

        $id         = (int)($_POST['id'] ?? 0);
        $chambre_id = (int)($_POST['chambre_id'] ?? 0);
        $nom        = trim($_POST['nom_lit'] ?? '');
        $statut     = $_POST['statut'] ?? 'DISPONIBLE';

        if (!$chambre_id || empty($nom)) { echo json_encode(['success' => false, 'message' => 'Données incomplètes']); exit; }

        try {
            if ($id) {
                $statutUpper = strtoupper($statut);

                if (in_array($statutUpper, ['DISPONIBLE', 'LIBRE', 'NETTOYAGE', 'MAINTENANCE'])) {
                    // Lit libéré : vider le patient occupant ET ses champs associés
                    $this->db->prepare(
                        "UPDATE lits SET chambre_id=?, nom_lit=?, statut=?,
                         occupied_by_patient_id=NULL, occupied_since=NULL WHERE id=?"
                    )->execute([$chambre_id, $nom, $statutUpper, $id]);

                    // Clôturer l'hospitalisation en cours pour ce lit
                    $this->db->prepare("
                        UPDATE hospitalisations
                        SET statut = 'termine',
                            date_sortie_effective = COALESCE(date_sortie_effective, NOW())
                        WHERE lit_id = ? AND statut = 'en_cours'
                    ")->execute([$id]);

                } else {
                    // Mise à jour simple sans toucher à l'occupation
                    $this->db->prepare("UPDATE lits SET chambre_id=?, nom_lit=?, statut=? WHERE id=?")
                             ->execute([$chambre_id, $nom, $statut, $id]);
                }
            } else {
                $this->db->prepare("INSERT INTO lits (chambre_id, nom_lit, statut) VALUES (?,?,?)")
                         ->execute([$chambre_id, $nom, $statut]);
                $id = $this->db->lastInsertId();
            }
            $this->audit->logAction($id ? 'UPDATE' : 'INSERT', 'lits', $id, null, "Lit: $nom — Statut: $statut");
            echo json_encode(['success' => true, 'id' => $id]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    public function deleteLit($id) {
        header('Content-Type: application/json');
        $this->requireSuperAdmin();
        try {
            // Vérifie que le lit n'est pas occupé
            $lit = $this->db->prepare("SELECT statut, nom_lit FROM lits WHERE id = ?");
            $lit->execute([$id]);
            $row = $lit->fetch(PDO::FETCH_ASSOC);
            if ($row && $row['statut'] === 'OCCUPE') {
                echo json_encode(['success' => false, 'message' => 'Impossible : le lit ' . $row['nom_lit'] . ' est actuellement occupé.']);
                exit;
            }
            // Supprimer l'historique des mouvements liés à ce lit (FK)
            $this->db->prepare("DELETE FROM mouvements_lits WHERE lit_id = ?")->execute([$id]);
            $this->db->prepare("DELETE FROM lits WHERE id = ?")->execute([$id]);
            echo json_encode(['success' => true]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    // ── RÔLES & PERMISSIONS ───────────────────────────────────────────────────

    public function permissions() {
        $isSuperAdmin = ($_SESSION['user_role'] ?? '') === 'SUPER_ADMIN';
        $roles = $isSuperAdmin
            ? ['SUPER_ADMIN', 'ADMIN', 'MEDECIN', 'INFIRMIER', 'SECRETAIRE', 'LABORANTIN', 'PHARMACIEN', 'PARAMETRES', 'DIRECTEUR']
            : ['ADMIN', 'MEDECIN', 'INFIRMIER', 'SECRETAIRE', 'LABORANTIN', 'PHARMACIEN', 'PARAMETRES', 'DIRECTEUR'];

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

    // ── Familles de médicaments (référentiel partagé) ─────────────────────────
    public static function getFamillesMedicaments(): array {
        return [
            'Antibiotiques', 'Antiparasitaires', 'Antipaludéens', 'Antalgiques',
            'Anti-inflammatoires', 'Antihypertenseurs', 'Antidiabétiques',
            'Antiviraux', 'Antifongiques', 'Gastro-entérologie', 'Cardiologie',
            'Neurologie / Psychiatrie', 'Dermatologie', 'Ophtalmologie',
            'Gynécologie / Obstétrique', 'Pédiatrie', 'Vitamines / Suppléments',
            'Solutés / Perfusion', 'Autres',
        ];
    }

    private function ensureFamilleColumn(): void {
        $col = $this->db->query(
            "SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'medicaments' AND COLUMN_NAME = 'famille'"
        )->fetchColumn();
        if (!$col) {
            $this->db->exec("ALTER TABLE medicaments ADD COLUMN famille VARCHAR(80) DEFAULT NULL AFTER dosage");
        }
    }

    public function pharmacie() {
        $this->ensureFamilleColumn();
        $familles    = self::getFamillesMedicaments();
        $medicaments = $this->db->query("
            SELECT id, code, nom AS designation, nom, forme, dosage, famille, quantite, unite, seuil_alerte, prix_unitaire
            FROM medicaments
            ORDER BY famille ASC, nom ASC
        ")->fetchAll(PDO::FETCH_ASSOC);
        require_once __DIR__ . '/../views/admin/gestion_pharmacie.php';
    }

    public function saveMedicament() {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['success' => false]); exit; }

        $id    = (int)($_POST['id'] ?? 0);
        $nom   = trim($_POST['nom'] ?? '');
        if (empty($nom)) { echo json_encode(['success' => false, 'message' => 'Le nom est requis']); exit; }

        $code    = trim($_POST['code']           ?? '') ?: null;
        $forme   = trim($_POST['forme']          ?? '') ?: null;
        $dosage  = trim($_POST['dosage']         ?? '') ?: null;
        $famille = trim($_POST['famille']        ?? '') ?: null;
        $qte     = max(0, (int)($_POST['quantite']     ?? 0));
        $unite   = trim($_POST['unite']          ?? '') ?: null;
        $seuil   = max(0, (int)($_POST['seuil_alerte'] ?? 10));
        $prix    = max(0, (float)($_POST['prix_unitaire'] ?? 0));

        try {
            if ($id) {
                $stmt = $this->db->prepare("UPDATE medicaments SET code=?, nom=?, forme=?, dosage=?, famille=?, quantite=?, unite=?, seuil_alerte=?, prix_unitaire=? WHERE id=?");
                $stmt->execute([$code, $nom, $forme, $dosage, $famille, $qte, $unite, $seuil, $prix, $id]);
                $this->audit->logAction('UPDATE', 'medicaments', $id, null, "Médicament: $nom");
            } else {
                $stmt = $this->db->prepare("INSERT INTO medicaments (code, nom, forme, dosage, famille, quantite, unite, seuil_alerte, prix_unitaire) VALUES (?,?,?,?,?,?,?,?,?)");
                $stmt->execute([$code, $nom, $forme, $dosage, $famille, $qte, $unite, $seuil, $prix]);
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
                nom     VARCHAR(100) NOT NULL UNIQUE,
                couleur VARCHAR(20) NOT NULL DEFAULT 'secondary',
                icone   VARCHAR(50) NOT NULL DEFAULT 'grid-fill'
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        // Migrer categorie de ENUM → VARCHAR(100) si nécessaire
        $col = $this->db->query("
            SELECT COLUMN_TYPE FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME   = 'examens_laboratoire'
              AND COLUMN_NAME  = 'categorie'
        ")->fetchColumn();
        if ($col && stripos($col, 'enum') === 0) {
            $this->db->exec("
                ALTER TABLE examens_laboratoire
                MODIFY COLUMN categorie VARCHAR(100) NOT NULL DEFAULT 'BIOCHIMIE'
            ");
        }

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

    // ── IMAGERIE MÉDICALE ────────────────────────────────────────────────────

    /** Auto-création du catalogue des examens d'imagerie + seed initial */
    private function ensureExamensImagerieTable() {
        $this->db->exec("CREATE TABLE IF NOT EXISTS examens_imagerie (
            id INT AUTO_INCREMENT PRIMARY KEY,
            code VARCHAR(30) NULL,
            nom VARCHAR(150) NOT NULL,
            type_examen ENUM('radiographie','scanner','irm','echographie','mammographie','autre') NOT NULL DEFAULT 'radiographie',
            partie_corps VARCHAR(100) NULL,
            prix DECIMAL(10,2) NOT NULL DEFAULT 0,
            avec_contraste TINYINT(1) NOT NULL DEFAULT 0,
            delai_rendu_heures INT NOT NULL DEFAULT 24,
            disponible TINYINT(1) NOT NULL DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        // Seed initial si le catalogue est vide
        $count = (int)$this->db->query("SELECT COUNT(*) FROM examens_imagerie")->fetchColumn();
        if ($count === 0) {
            $seed = $this->db->prepare(
                "INSERT INTO examens_imagerie (code, nom, type_examen, partie_corps, prix, avec_contraste, delai_rendu_heures) VALUES (?,?,?,?,?,?,?)"
            );
            $defauts = [
                ['RX-THOR',  'Radiographie thorax',        'radiographie', 'Thorax',   15000, 0, 2],
                ['RX-MEMB',  'Radiographie membre',        'radiographie', 'Membre',   12000, 0, 2],
                ['RX-ASP',   'Abdomen sans préparation',   'radiographie', 'Abdomen',  15000, 0, 2],
                ['ECHO-ABD', 'Échographie abdominale',     'echographie',  'Abdomen',  20000, 0, 4],
                ['ECHO-PELV','Échographie pelvienne',      'echographie',  'Pelvis',   20000, 0, 4],
                ['ECHO-OBS', 'Échographie obstétricale',   'echographie',  'Pelvis',   25000, 0, 4],
                ['SCAN-CRA', 'Scanner crâne',              'scanner',      'Crâne',    75000, 1, 24],
                ['SCAN-ABD', 'Scanner abdomino-pelvien',   'scanner',      'Abdomen',  90000, 1, 24],
                ['IRM-CER',  'IRM cérébrale',              'irm',          'Crâne',   120000, 1, 48],
                ['MAMMO',    'Mammographie bilatérale',    'mammographie', 'Seins',    30000, 0, 24],
            ];
            foreach ($defauts as $d) $seed->execute($d);
        }
    }

    public function imagerie() {
        $this->ensureExamensImagerieTable();

        $examens_imagerie = $this->db->query(
            "SELECT * FROM examens_imagerie ORDER BY type_examen ASC, nom ASC"
        )->fetchAll(PDO::FETCH_ASSOC);

        // KPIs des demandes
        $stats_imagerie = ['jour' => 0, 'attente' => 0, 'termine_jour' => 0];
        try {
            $stats_imagerie['jour'] = (int)$this->db->query(
                "SELECT COUNT(*) FROM demandes_imagerie WHERE DATE(date_creation) = CURDATE()"
            )->fetchColumn();
            $stats_imagerie['attente'] = (int)$this->db->query(
                "SELECT COUNT(*) FROM demandes_imagerie WHERE UPPER(statut) IN ('EN_ATTENTE','PROGRAMME','EN_COURS')"
            )->fetchColumn();
            $stats_imagerie['termine_jour'] = (int)$this->db->query(
                "SELECT COUNT(*) FROM demandes_imagerie
                  WHERE UPPER(statut) IN ('TERMINE','VALIDE','INTERPRETE')
                    AND DATE(COALESCE(date_resultats, updated_at, date_creation)) = CURDATE()"
            )->fetchColumn();
        } catch (Exception $e) {}

        // Dernières demandes (suivi temps réel pour l'admin)
        $demandes_recentes = [];
        try {
            $demandes_recentes = $this->db->query("
                SELECT di.id, di.type_imagerie, di.type_examen, di.partie_corps, di.statut,
                       di.urgence, di.date_creation,
                       p.nom AS patient_nom, p.prenom AS patient_prenom, p.dossier_numero,
                       u.nom AS medecin_nom, u.prenom AS medecin_prenom
                FROM demandes_imagerie di
                LEFT JOIN patients p ON p.id = di.patient_id
                LEFT JOIN users u    ON u.id = di.medecin_id
                ORDER BY di.date_creation DESC
                LIMIT 20
            ")->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {}

        require_once __DIR__ . '/../views/admin/gestion_imagerie.php';
    }

    public function saveExamenImagerie() {
        header('Content-Type: application/json');
        $this->requireSuperAdmin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['success' => false]); exit; }
        $this->ensureExamensImagerieTable();

        $id  = (int)($_POST['id'] ?? 0);
        $nom = trim($_POST['nom'] ?? '');
        if (empty($nom)) { echo json_encode(['success' => false, 'message' => 'Le nom est requis']); exit; }

        $code      = trim($_POST['code'] ?? '') ?: null;
        $type      = $_POST['type_examen'] ?? 'radiographie';
        $partie    = trim($_POST['partie_corps'] ?? '') ?: null;
        $prix      = max(0, (float)($_POST['prix'] ?? 0));
        $contraste = isset($_POST['avec_contraste']) ? 1 : 0;
        $delai     = max(1, (int)($_POST['delai_rendu_heures'] ?? 24));
        $dispo     = isset($_POST['disponible']) ? 1 : 0;

        $typesValides = ['radiographie','scanner','irm','echographie','mammographie','autre'];
        if (!in_array($type, $typesValides)) $type = 'autre';

        try {
            if ($id) {
                $this->db->prepare("UPDATE examens_imagerie
                    SET code=?, nom=?, type_examen=?, partie_corps=?, prix=?, avec_contraste=?, delai_rendu_heures=?, disponible=?
                    WHERE id=?")
                    ->execute([$code, $nom, $type, $partie, $prix, $contraste, $delai, $dispo, $id]);
                $this->audit->logAction('UPDATE', 'examens_imagerie', $id, null, "Examen imagerie: $nom");
            } else {
                $this->db->prepare("INSERT INTO examens_imagerie
                    (code, nom, type_examen, partie_corps, prix, avec_contraste, delai_rendu_heures, disponible)
                    VALUES (?,?,?,?,?,?,?,?)")
                    ->execute([$code, $nom, $type, $partie, $prix, $contraste, $delai, $dispo]);
                $id = $this->db->lastInsertId();
                $this->audit->logAction('INSERT', 'examens_imagerie', $id, null, "Nouvel examen imagerie: $nom");
            }
            echo json_encode(['success' => true, 'id' => $id]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    public function deleteExamenImagerie($id) {
        header('Content-Type: application/json');
        $this->requireSuperAdmin();
        try {
            $this->db->prepare("DELETE FROM examens_imagerie WHERE id = ?")->execute([(int)$id]);
            $this->audit->logAction('DELETE', 'examens_imagerie', (int)$id, null, "Suppression examen imagerie #$id");
            echo json_encode(['success' => true]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    public function saveCategorie() {
        header('Content-Type: application/json');
        $this->requireSuperAdmin();
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
        $this->requireSuperAdmin();
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
        $this->requireSuperAdmin();
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
        $this->requireSuperAdmin();
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
        $db = $this->db;

        // ── Filtres ──────────────────────────────────────────────────────────
        $date_to       = $_GET['date_to']   ?? date('Y-m-d');
        $date_from     = $_GET['date_from'] ?? date('Y-m-d', strtotime('-30 days'));
        $action_filter = $_GET['action']    ?? 'ALL';
        $table_filter  = $_GET['table']     ?? 'ALL';
        $user_filter   = $_GET['user_id']   ?? '';
        $ip_filter     = trim($_GET['ip']     ?? '');
        $search        = trim($_GET['search'] ?? '');
        $per_page      = 50;
        $page          = max(1, (int)($_GET['page'] ?? 1));
        $offset        = ($page - 1) * $per_page;

        // ── WHERE pour le journal paginé ─────────────────────────────────────
        $where  = "a.created_at BETWEEN ? AND DATE_ADD(?, INTERVAL 1 DAY)";
        $params = [$date_from, $date_to];
        if ($action_filter !== 'ALL') { $where .= ' AND a.action = ?';        $params[] = $action_filter; }
        if ($table_filter  !== 'ALL') { $where .= ' AND a.table_name = ?';    $params[] = $table_filter; }
        if ($user_filter   !== '')    { $where .= ' AND a.user_id = ?';        $params[] = (int)$user_filter; }
        if ($ip_filter     !== '')    { $where .= ' AND a.ip_address LIKE ?';  $params[] = "%$ip_filter%"; }
        if ($search        !== '') {
            $where   .= " AND (a.action LIKE ? OR a.table_name LIKE ? OR CONCAT(u.prenom,' ',u.nom) LIKE ?)";
            $params[] = "%$search%"; $params[] = "%$search%"; $params[] = "%$search%";
        }

        $stmtCount = $db->prepare("SELECT COUNT(*) FROM audit_logs a LEFT JOIN users u ON a.user_id = u.id WHERE $where");
        $stmtCount->execute($params);
        $total_rows  = (int)$stmtCount->fetchColumn();
        $total_pages = max(1, ceil($total_rows / $per_page));

        $stmtLogs = $db->prepare("
            SELECT a.*, u.nom, u.prenom, u.username,
                   pt.nom AS pat_nom, pt.prenom AS pat_prenom, pt.dossier_numero AS pat_dossier
            FROM audit_logs a
            LEFT JOIN users u ON a.user_id = u.id
            LEFT JOIN patients pt ON (a.table_name = 'patients' AND a.record_id = pt.id)
            WHERE $where
            ORDER BY a.created_at DESC
            LIMIT $per_page OFFSET $offset
        ");
        $stmtLogs->execute($params);
        $logs = $stmtLogs->fetchAll(PDO::FETCH_ASSOC);

        // ── KPI ──────────────────────────────────────────────────────────────
        $kpi = [];
        $kpi['total']         = (int)$db->query("SELECT COUNT(*) FROM audit_logs")->fetchColumn();
        $kpi['today']         = (int)$db->query("SELECT COUNT(*) FROM audit_logs WHERE DATE(created_at)=CURDATE()")->fetchColumn();
        $kpi['week']          = (int)$db->query("SELECT COUNT(*) FROM audit_logs WHERE created_at >= DATE_SUB(NOW(),INTERVAL 7 DAY)")->fetchColumn();
        $kpi['users_today']   = (int)$db->query("SELECT COUNT(DISTINCT user_id) FROM audit_logs WHERE DATE(created_at)=CURDATE()")->fetchColumn();
        $kpi['users_week']    = (int)$db->query("SELECT COUNT(DISTINCT user_id) FROM audit_logs WHERE created_at >= DATE_SUB(NOW(),INTERVAL 7 DAY)")->fetchColumn();
        try {
            $kpi['login_failures'] = (int)$db->query("SELECT COUNT(*) FROM login_attempts WHERE success=0 AND created_at >= DATE_SUB(NOW(),INTERVAL 7 DAY)")->fetchColumn();
        } catch (PDOException $e) { $kpi['login_failures'] = 0; }
        $kpi['access_denied'] = (int)$db->query("SELECT COUNT(*) FROM audit_logs WHERE action='ACCESS_DENIED' AND created_at >= DATE_SUB(NOW(),INTERVAL 7 DAY)")->fetchColumn();

        // ── Activité par jour (30 jours) ─────────────────────────────────────
        $rows = $db->query("
            SELECT DATE(created_at) as jour, COUNT(*) as nb
            FROM audit_logs WHERE created_at >= DATE_SUB(CURDATE(),INTERVAL 30 DAY)
            GROUP BY jour ORDER BY jour ASC
        ")->fetchAll(PDO::FETCH_ASSOC);
        $activityByDay = [];
        foreach ($rows as $r) $activityByDay[$r['jour']] = (int)$r['nb'];

        // ── Activité par heure ────────────────────────────────────────────────
        $rows = $db->query("
            SELECT HOUR(created_at) as h, COUNT(*) as nb
            FROM audit_logs WHERE created_at >= DATE_SUB(NOW(),INTERVAL 30 DAY)
            GROUP BY h
        ")->fetchAll(PDO::FETCH_ASSOC);
        $hourData = array_fill(0, 24, 0);
        foreach ($rows as $r) $hourData[(int)$r['h']] = (int)$r['nb'];

        // ── Répartition des actions ───────────────────────────────────────────
        $rows = $db->query("SELECT action, COUNT(*) as nb FROM audit_logs GROUP BY action ORDER BY nb DESC")->fetchAll(PDO::FETCH_ASSOC);
        $actionBreakdown = [];
        foreach ($rows as $r) $actionBreakdown[$r['action']] = (int)$r['nb'];

        // ── Top modules ──────────────────────────────────────────────────────
        $topModules = $db->query("
            SELECT table_name, COUNT(*) as nb FROM audit_logs
            WHERE table_name IS NOT NULL AND table_name != ''
            GROUP BY table_name ORDER BY nb DESC LIMIT 10
        ")->fetchAll(PDO::FETCH_ASSOC);

        // ── Statistiques utilisateurs ─────────────────────────────────────────
        $totalActions = max(1, $kpi['total']);
        $rows = $db->query("
            SELECT a.user_id,
                   u.nom, u.prenom, u.username, u.role,
                   COUNT(*)                           AS nb_actions,
                   COUNT(DISTINCT DATE(a.created_at)) AS jours_actifs,
                   MAX(a.created_at)                  AS derniere_action,
                   SUM(a.action='LOGIN')              AS nb_logins
            FROM audit_logs a
            LEFT JOIN users u ON a.user_id = u.id
            WHERE a.user_id IS NOT NULL
            GROUP BY a.user_id ORDER BY nb_actions DESC LIMIT 30
        ")->fetchAll(PDO::FETCH_ASSOC);

        // Nombre de consultations par médecin (tous rôles médicaux)
        $consultParMedecin = [];
        try {
            $cRows = $db->query("
                SELECT medecin_id,
                       COUNT(*)                                              AS nb_total,
                       SUM(MONTH(date_consultation)=MONTH(CURDATE())
                           AND YEAR(date_consultation)=YEAR(CURDATE()))     AS nb_mois,
                       SUM(DATE(date_consultation)=CURDATE())               AS nb_jour,
                       MAX(date_consultation)                                AS derniere_consult
                FROM consultations
                WHERE medecin_id IS NOT NULL
                GROUP BY medecin_id
            ")->fetchAll(PDO::FETCH_ASSOC);
            foreach ($cRows as $c) {
                $consultParMedecin[(int)$c['medecin_id']] = $c;
            }
        } catch (PDOException $e) {}

        $userStats = [];
        foreach ($rows as $r) {
            $r['taux']   = round($r['nb_actions'] / $totalActions * 100, 2);
            $uid         = (int)$r['user_id'];
            $r['consult'] = $consultParMedecin[$uid] ?? null;
            $userStats[]  = $r;
        }

        // ── Tentatives de connexion ───────────────────────────────────────────
        $loginAttempts = [];
        $loginByDay    = [];
        $topIps        = [];
        try {
            $loginAttempts = $db->query("
                SELECT la.* FROM login_attempts la ORDER BY la.created_at DESC LIMIT 100
            ")->fetchAll(PDO::FETCH_ASSOC);

            $rows = $db->query("
                SELECT DATE(created_at) as jour, COUNT(*) as nb
                FROM login_attempts WHERE success=0 AND created_at >= DATE_SUB(CURDATE(),INTERVAL 30 DAY)
                GROUP BY jour ORDER BY jour ASC
            ")->fetchAll(PDO::FETCH_ASSOC);
            foreach ($rows as $r) $loginByDay[$r['jour']] = (int)$r['nb'];

            $topIps = $db->query("
                SELECT ip_address,
                       COUNT(*)          AS nb_total,
                       SUM(success=0)    AS nb_echecs,
                       MAX(created_at)   AS derniere_tentative
                FROM login_attempts
                GROUP BY ip_address ORDER BY nb_total DESC LIMIT 15
            ")->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) { /* table absente */ }

        // ── Heatmap (7 jours de la semaine × 24 heures) ───────────────────────
        $heatmap = array_fill(0, 7, array_fill(0, 24, 0));
        $rows = $db->query("
            SELECT DAYOFWEEK(created_at)-1 AS dow, HOUR(created_at) AS h, COUNT(*) AS nb
            FROM audit_logs WHERE created_at >= DATE_SUB(NOW(),INTERVAL 90 DAY)
            GROUP BY dow, h
        ")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as $r) $heatmap[(int)$r['dow']][(int)$r['h']] = (int)$r['nb'];

        // ── Listes pour les filtres ───────────────────────────────────────────
        $actions = $db->query("SELECT DISTINCT action FROM audit_logs ORDER BY action")->fetchAll(PDO::FETCH_COLUMN);
        $tables  = $db->query("SELECT DISTINCT table_name FROM audit_logs WHERE table_name IS NOT NULL ORDER BY table_name")->fetchAll(PDO::FETCH_COLUMN);
        $users   = $db->query("SELECT id, CONCAT(prenom,' ',nom) as full_name FROM users ORDER BY nom")->fetchAll(PDO::FETCH_ASSOC);

        $filters = [
            'action'    => $action_filter,
            'table'     => $table_filter,
            'user_id'   => $user_filter,
            'ip'        => $ip_filter,
            'search'    => $search,
            'date_from' => $date_from,
            'date_to'   => $date_to,
        ];

        require_once __DIR__ . '/../views/admin/logs.php';
    }
}
