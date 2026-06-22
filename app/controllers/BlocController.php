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
   FICHIER : app/controllers/BlocController.php
   CONTRÔLEUR DE GESTION DU BLOC OPÉRATOIRE
   ============================================================================ */
require_once __DIR__ . '/UnifiedController.php';
require_once __DIR__ . '/../services/AuditService.php';

class BlocController extends UnifiedController {
    private $db;
    private $audit;

    public function __construct() {
        parent::__construct();
        $database = new Database();
        $this->db = $database->getConnection();
        $this->audit = new AuditService();
        $this->ensureSchema();
    }

    /**
     * Auto-migration : colonnes de cycle de vie sur bloc_programmation.
     * statut PROGRAMME → EN_COURS → EN_SSPI → TERMINE / ANNULE + horodatages réels.
     */
    private function ensureSchema() {
        try {
            $cols = $this->db->query("SHOW COLUMNS FROM bloc_programmation")->fetchAll(PDO::FETCH_COLUMN);
            if (!in_array('statut', $cols)) {
                $this->db->exec("ALTER TABLE bloc_programmation ADD COLUMN statut VARCHAR(20) NOT NULL DEFAULT 'PROGRAMME'");
            }
            if (!in_array('heure_debut_reel', $cols)) {
                $this->db->exec("ALTER TABLE bloc_programmation ADD COLUMN heure_debut_reel DATETIME NULL");
            }
            if (!in_array('heure_fin_reel', $cols)) {
                $this->db->exec("ALTER TABLE bloc_programmation ADD COLUMN heure_fin_reel DATETIME NULL");
            }
        } catch (\Throwable $e) { error_log('[Bloc] ensureSchema: ' . $e->getMessage()); }

        // ── Rôles infirmiers du bloc : IADE (anesthésiste) + IBODE (bloc opératoire) ──
        // Étendre l'ENUM role_permissions et seeder leurs permissions (1x / session)
        if (empty($_SESSION['_migration_roles_bloc_ok'])) {
            try {
                $colType = $this->db->query(
                    "SELECT COLUMN_TYPE FROM information_schema.COLUMNS
                      WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'role_permissions' AND COLUMN_NAME = 'role'"
                )->fetchColumn();
                if ($colType && stripos($colType, 'enum') === 0) {
                    $newType = $colType;
                    foreach (['INFIRMIER_ANESTHESISTE', 'INFIRMIER_BLOC'] as $r) {
                        if (strpos($newType, "'$r'") === false) {
                            $newType = preg_replace('/\)$/', ",'$r')", $newType);
                        }
                    }
                    if ($newType !== $colType) {
                        $this->db->exec("ALTER TABLE role_permissions MODIFY COLUMN role $newType");
                    }
                }
                // Seed des permissions nécessaires au module bloc / SSPI
                $seed = $this->db->prepare(
                    "INSERT INTO role_permissions (role, module, permission)
                     SELECT ?, ?, ? FROM DUAL
                     WHERE NOT EXISTS (SELECT 1 FROM role_permissions WHERE role=? AND module=? AND permission=?)"
                );
                foreach (['INFIRMIER_ANESTHESISTE', 'INFIRMIER_BLOC'] as $r) {
                    foreach ([['laboratoire','READ'], ['laboratoire','write'], ['bloc','READ'], ['bloc','write'], ['patients','READ']] as [$mod, $perm]) {
                        $seed->execute([$r, $mod, $perm, $r, $mod, $perm]);
                    }
                }
            } catch (\Throwable $e) { error_log('[Bloc] migration roles bloc: ' . $e->getMessage()); }
            $_SESSION['_migration_roles_bloc_ok'] = true;
        }
    }

    /** Helper : libellé + couleur d'un statut d'intervention */
    public static function statutInfo($s): array {
        $map = [
            'PROGRAMME' => ['Programmée', '#2563eb', 'bi-calendar-check'],
            'EN_COURS'  => ['En cours',   '#dc2626', 'bi-activity'],
            'EN_SSPI'   => ['En réveil',  '#d97706', 'bi-heart-pulse'],
            'TERMINE'   => ['Terminée',   '#16a34a', 'bi-check-circle'],
            'ANNULE'    => ['Annulée',    '#94a3b8', 'bi-x-circle'],
        ];
        return $map[strtoupper($s ?? '')] ?? ['Programmée', '#2563eb', 'bi-calendar-check'];
    }

    /**
     * Dashboard Principal du Bloc
     */
    public function index() {
        $this->auth->requirePermission('laboratoire', 'read');

        // 1. État des salles
        $salles = $this->db->query("SELECT * FROM bloc_salles ORDER BY nom_salle ASC")->fetchAll(PDO::FETCH_ASSOC);

        // 2. Programme du jour (programmations du jour, tous statuts sauf terminé/annulé pour le suivi)
        $queryInter = "SELECT bp.*, p.id AS patient_id, p.nom, p.prenom, p.dossier_numero,
                              bd.chirurgien_id, bd.anesthesiste_id,
                              u.nom AS chirurgien_nom, bs.nom_salle AS salle_nom,
                              bp.diagnostique_op, COALESCE(bp.statut,'PROGRAMME') AS statut
                       FROM bloc_programmation bp
                       JOIN bloc_demandes bd ON bp.demande_id = bd.id
                       JOIN patients p       ON bd.patient_id = p.id
                       JOIN users u          ON bd.chirurgien_id = u.id
                       JOIN bloc_salles bs   ON bp.salle_id = bs.id
                       WHERE bp.date_intervention = CURDATE()
                       ORDER BY FIELD(COALESCE(bp.statut,'PROGRAMME'),'EN_COURS','EN_SSPI','PROGRAMME','TERMINE','ANNULE'), bp.heure_debut ASC";
        $interventions = $this->db->query($queryInter)->fetchAll(PDO::FETCH_ASSOC);

        // 3. File d'attente (patients « À opérer »)
        $queryQueue = "SELECT bd.*, p.nom, p.prenom, p.dossier_numero, u.nom AS chirurgien_nom
                       FROM bloc_demandes bd
                       JOIN patients p ON bd.patient_id = p.id
                       JOIN users u    ON bd.chirurgien_id = u.id
                       WHERE bd.statut = 'EN_ATTENTE'
                       ORDER BY bd.date_demande ASC";
        $file_attente = $this->db->query($queryQueue)->fetchAll(PDO::FETCH_ASSOC);

        // 4. KPIs
        $kpi = [
            'salles_libres' => 0, 'salles_total' => count($salles),
            'prog_jour' => 0, 'en_cours' => 0, 'en_sspi' => 0, 'termine_jour' => 0, 'attente' => count($file_attente),
        ];
        foreach ($salles as $s) {
            $st = strtoupper($s['statut']);
            if ($st === 'DISPONIBLE' || $st === 'LIBRE') $kpi['salles_libres']++;
        }
        foreach ($interventions as $i) {
            $st = strtoupper($i['statut']);
            if ($st === 'PROGRAMME') $kpi['prog_jour']++;
            elseif ($st === 'EN_COURS') $kpi['en_cours']++;
            elseif ($st === 'EN_SSPI') $kpi['en_sspi']++;
            elseif ($st === 'TERMINE') $kpi['termine_jour']++;
        }

        // Intervenants pour le modal "Nouvelle Intervention"
        $medecins  = $this->db->query(
            "SELECT id, nom, prenom FROM users WHERE role='MEDECIN' AND actif=1 ORDER BY nom, prenom"
        )->fetchAll(PDO::FETCH_ASSOC);
        $infirmiers = $this->db->query(
            "SELECT id, nom, prenom, role FROM users
             WHERE role IN ('INFIRMIER','MAJOR_INFIRMIER','INFIRMIER_CONSULTANT') AND actif=1
             ORDER BY nom, prenom"
        )->fetchAll(PDO::FETCH_ASSOC);

        // Infirmiers spécifiquement du Bloc Opératoire (11) et Anesthésie (16)
        $infirmiers_bloc = $this->db->query(
            "SELECT u.id, u.nom, u.prenom, s.nom_service
             FROM users u JOIN services s ON s.id=u.service_id
             WHERE u.service_id IN (11, 16) AND u.actif=1
             ORDER BY u.nom, u.prenom"
        )->fetchAll(PDO::FETCH_ASSOC);

        // Fiches d'ouverture de salle remplies aujourd'hui
        $ouvertures = [];
        try {
            $ouvertures = $this->db->query(
                "SELECT bos.*,
                        p.nom AS patient_nom, p.prenom AS patient_prenom, p.dossier_numero,
                        u.nom AS user_nom, u.prenom AS user_prenom,
                        bs.nom_salle, bp.diagnostique_op,
                        bp.heure_debut, COALESCE(bp.statut,'PROGRAMME') AS inter_statut
                 FROM bloc_ouverture_salle bos
                 JOIN bloc_programmation bp ON bp.id = bos.programmation_id
                 JOIN bloc_demandes bd      ON bd.id = bp.demande_id
                 JOIN patients p            ON p.id  = bd.patient_id
                 LEFT JOIN users u          ON u.id  = bos.user_id
                 LEFT JOIN bloc_salles bs   ON bs.id = bp.salle_id
                 WHERE DATE(bos.created_at) = CURDATE()
                 ORDER BY bos.created_at DESC"
            )->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Throwable $e) { /* table pas encore créée */ }

        require_once __DIR__ . '/../views/bloc/index.php';
    }

    // ─── DÉMARRER une intervention ───────────────────────────────────────────
    public function demarrer($prog_id) {
        $this->auth->requirePermission('laboratoire', 'read');
        // Redirige vers le checklist d'ouverture de salle avant de démarrer
        header('Location: '.BASE_URL.'bloc/ouverture-salle/'.(int)$prog_id); exit;
    }

    // ─── FICHE D'OUVERTURE DE SALLE — Affichage ──────────────────────────────
    public function ouvertureSalle($prog_id) {
        $this->auth->requirePermission('laboratoire', 'read');
        $prog_id = (int)$prog_id;

        $stmt = $this->db->prepare(
            "SELECT bp.*, bs.nom_salle AS salle_nom,
                    bd.id AS demande_id, bd.patient_id,
                    p.nom, p.prenom, p.dossier_numero
             FROM bloc_programmation bp
             JOIN bloc_demandes bd ON bd.id = bp.demande_id
             JOIN patients p       ON p.id  = bd.patient_id
             JOIN bloc_salles bs   ON bs.id = bp.salle_id
             WHERE bp.id = ? AND bp.statut = 'PROGRAMME'"
        );
        $stmt->execute([$prog_id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) { header('Location: '.BASE_URL.'bloc?error=introuvable'); exit; }

        $programmation = $row;
        $patient       = ['nom' => $row['nom'], 'prenom' => $row['prenom'],
                          'dossier_numero' => $row['dossier_numero'],
                          'salle_nom' => $row['salle_nom']];

        $salles = $this->db->query(
            "SELECT id, nom_salle FROM bloc_salles WHERE statut != 'OCCUPEE' OR id = {$row['salle_id']} ORDER BY nom_salle"
        )->fetchAll(PDO::FETCH_ASSOC);

        require_once __DIR__ . '/../views/bloc/ouverture_salle.php';
    }

    // ─── FICHE D'OUVERTURE DE SALLE — Sauvegarde + démarrage ─────────────────
    public function sauvegarderOuverture() {
        $this->auth->requirePermission('laboratoire', 'read');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: '.BASE_URL.'bloc'); exit; }
        require_once __DIR__ . '/../services/CsrfService.php';
        if (!CsrfService::validate()) { header('Location: '.BASE_URL.'bloc?error=csrf'); exit; }

        $progId = (int)($_POST['prog_id'] ?? 0);
        if (!$progId) { header('Location: '.BASE_URL.'bloc'); exit; }

        try {
            // Vérifier que l'intervention est encore PROGRAMME
            $stmt = $this->db->prepare(
                "SELECT bp.*, bd.id AS demande_id FROM bloc_programmation bp
                 JOIN bloc_demandes bd ON bd.id=bp.demande_id WHERE bp.id=? AND bp.statut='PROGRAMME'"
            );
            $stmt->execute([$progId]);
            $prog = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$prog) { header('Location: '.BASE_URL.'bloc?error=introuvable'); exit; }

            // Construire checklist JSON
            $checkItems = [
                'eclairage_ambiance','eclairage_scialytique',
                'table_mouvements','table_accessoires',
                'bistouri_generateur','bistouri_plaque','bistouri_pedale','bistouri_cordon',
                'asp_generateur','asp_bocaux','asp_accessoires',
                'air_splits',
                'mat_ampli','mat_colone_video','mat_negatoscope',
            ];
            $checklist = [];
            foreach ($checkItems as $k) {
                $checklist[$k] = [
                    'presence'       => $_POST[$k.'_presence']       ?? null,
                    'fonctionnalite' => $_POST[$k.'_fonctionnalite'] ?? null,
                    'proprete'       => $_POST[$k.'_proprete']       ?? null,
                    'obs'            => trim($_POST[$k.'_obs']        ?? ''),
                ];
            }

            // Créer table si absente
            $this->db->exec("CREATE TABLE IF NOT EXISTS bloc_ouverture_salle (
                id INT AUTO_INCREMENT PRIMARY KEY,
                programmation_id INT NOT NULL,
                user_id INT,
                date_fiche DATE,
                heure_ouverture TIME,
                salle_identite VARCHAR(100),
                heure_groupe_on TIME,
                heure_groupe_off TIME,
                transmis_arret VARCHAR(255),
                signature_responsable VARCHAR(150),
                checklist_json JSON,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX(programmation_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

            // Enregistrer la fiche
            $this->db->prepare(
                "INSERT INTO bloc_ouverture_salle
                 (programmation_id, user_id, date_fiche, heure_ouverture, salle_identite,
                  heure_groupe_on, heure_groupe_off, transmis_arret, signature_responsable, checklist_json)
                 VALUES (?,?,?,?,?,?,?,?,?,?)"
            )->execute([
                $progId,
                $_SESSION['user_id'] ?? null,
                $_POST['date_fiche']           ?? date('Y-m-d'),
                $_POST['heure_ouverture']      ?: null,
                trim($_POST['salle_identite']  ?? ''),
                $_POST['heure_groupe_on']      ?: null,
                $_POST['heure_groupe_off']     ?: null,
                trim($_POST['transmis_arret']  ?? ''),
                trim($_POST['signature_responsable'] ?? ''),
                json_encode($checklist, JSON_UNESCAPED_UNICODE),
            ]);

            // Changement de salle si sélectionnée différemment
            $newSalleId = (int)($_POST['salle_id'] ?? $prog['salle_id']);
            if ($newSalleId && $newSalleId !== (int)$prog['salle_id']) {
                $this->db->prepare("UPDATE bloc_programmation SET salle_id=? WHERE id=?")->execute([$newSalleId, $progId]);
                // Libérer l'ancienne salle si elle était réservée
                $this->db->prepare("UPDATE bloc_salles SET statut='LIBRE' WHERE id=? AND statut='RESERVEE'")->execute([$prog['salle_id']]);
                $prog['salle_id'] = $newSalleId;
            }

            // Démarrer l'intervention
            $this->db->prepare("UPDATE bloc_programmation SET statut='EN_COURS', heure_debut_reel=NOW() WHERE id=?")->execute([$progId]);
            $this->db->prepare("UPDATE bloc_demandes SET statut='EN_COURS' WHERE id=?")->execute([$prog['demande_id']]);
            $this->db->prepare("UPDATE bloc_salles SET statut='OCCUPEE' WHERE id=?")->execute([$prog['salle_id']]);

            try { $this->audit->log('UPDATE','bloc_programmation',$progId,'Intervention démarrée après ouverture de salle',null,['statut'=>'EN_COURS']); } catch (\Throwable $e) {}

            header('Location: '.BASE_URL.'bloc/monitoring/'.$progId); exit;

        } catch (\Throwable $e) {
            error_log('[Bloc] sauvegarderOuverture: '.$e->getMessage());
            header('Location: '.BASE_URL.'bloc?error=ouverture'); exit;
        }
    }

    // ─── ENREGISTRER le compte-rendu opératoire (CRO) + envoyer en SSPI ───────
    public function sauvegarderCRO() {
        $this->auth->requirePermission('laboratoire', 'write');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: '.BASE_URL.'bloc'); exit; }
        $prog_id = (int)($_POST['programmation_id'] ?? 0);
        if (!$prog_id) { header('Location: '.BASE_URL.'bloc'); exit; }
        $f = fn($k) => (isset($_POST[$k]) && $_POST[$k] !== '') ? $_POST[$k] : null;
        try {
            $this->db->prepare("INSERT INTO bloc_cro
                (programmation_id, type_intervention, compte_rendu, complications, pertes_sanguines, drainage, heure_fin)
                VALUES (?,?,?,?,?,?,?)")
                ->execute([$prog_id, $f('type_intervention'), $f('compte_rendu'), $f('complications'),
                           $f('pertes_sanguines'), $f('drainage'), $f('heure_fin') ?: date('H:i:s')]);

            // Intervention terminée chirurgicalement → patient en salle de réveil (SSPI)
            $prog = $this->db->prepare("SELECT bp.salle_id, bd.id AS demande_id, bd.patient_id FROM bloc_programmation bp JOIN bloc_demandes bd ON bd.id=bp.demande_id WHERE bp.id=?");
            $prog->execute([$prog_id]); $row = $prog->fetch(PDO::FETCH_ASSOC);
            $this->db->prepare("UPDATE bloc_programmation SET statut='EN_SSPI', heure_fin_reel=NOW() WHERE id=?")->execute([$prog_id]);
            if ($row) {
                // Libérer la salle pour nettoyage
                $this->db->prepare("UPDATE bloc_salles SET statut='NETTOYAGE' WHERE id=?")->execute([$row['salle_id']]);
            }
            try { $this->audit->log('CREATE','bloc_cro',$prog_id,'Compte-rendu opératoire enregistré · passage en SSPI',null,['type'=>$f('type_intervention')]); } catch (\Throwable $e) {}

            // ── Communication bloc → SSPI : notifier les infirmiers SSPI ──
            try {
                $pInfo = $this->db->prepare("SELECT p.nom, p.prenom, p.dossier_numero FROM patients p WHERE p.id = ?");
                $pInfo->execute([(int)($row['patient_id'] ?? 0)]);
                $pat = $pInfo->fetch(PDO::FETCH_ASSOC) ?: [];
                $pNom = trim(strtoupper($pat['nom'] ?? '') . ' ' . ($pat['prenom'] ?? ''));

                require_once __DIR__ . '/../services/NotificationCenter.php';
                $nc = new NotificationCenter($this->db);
                $notif = [
                    'title'    => '🛏️ Patient arrivé en SSPI',
                    'message'  => "$pNom (" . ($pat['dossier_numero'] ?? '') . ") sort du bloc — "
                                . ($f('type_intervention') ?: 'intervention') . ". Surveillance post-interventionnelle à débuter.",
                    'priority' => 'high',
                    'link'     => 'bloc/sspi/' . $prog_id,
                ];
                $nc->notifyByRole('INFIRMIER_ANESTHESISTE', $notif);
                $nc->notifyByRole('INFIRMIER_BLOC', $notif);
            } catch (\Throwable $e) { error_log('[Bloc] notif SSPI: ' . $e->getMessage()); }

            header('Location: '.BASE_URL.'bloc/sspi/'.$prog_id); exit;
        } catch (\Throwable $e) {
            error_log('[Bloc] CRO: '.$e->getMessage());
            header('Location: '.BASE_URL.'bloc/monitoring/'.$prog_id.'?error=cro'); exit;
        }
    }

    // ─── DASHBOARD SSPI — tous les patients en surveillance post-interventionnelle ───
    public function sspiDashboard() {
        $this->auth->requirePermission('laboratoire', 'read');

        $patients_sspi = $this->db->query("
            SELECT bp.id AS prog_id, bp.heure_fin_reel, bp.diagnostique_op,
                   p.id AS patient_id, p.nom, p.prenom, p.dossier_numero, p.date_naissance, p.sexe,
                   bs.nom_salle, u.nom AS chirurgien_nom,
                   (SELECT type_intervention FROM bloc_cro  WHERE programmation_id = bp.id ORDER BY id DESC LIMIT 1) AS type_intervention,
                   (SELECT total_aldrete     FROM bloc_sspi WHERE programmation_id = bp.id ORDER BY id DESC LIMIT 1) AS dernier_aldrete,
                   (SELECT COUNT(*)          FROM bloc_sspi WHERE programmation_id = bp.id)                          AS nb_releves,
                   TIMESTAMPDIFF(MINUTE, bp.heure_fin_reel, NOW()) AS minutes_sspi
            FROM bloc_programmation bp
            JOIN bloc_demandes bd ON bd.id = bp.demande_id
            JOIN patients p       ON p.id = bd.patient_id
            JOIN bloc_salles bs   ON bs.id = bp.salle_id
            LEFT JOIN users u     ON u.id = bd.chirurgien_id
            WHERE bp.statut = 'EN_SSPI'
            ORDER BY bp.heure_fin_reel ASC
        ")->fetchAll(PDO::FETCH_ASSOC);

        // Sorties SSPI du jour (historique)
        $sorties_jour = $this->db->query("
            SELECT bp.id AS prog_id, p.nom, p.prenom, p.dossier_numero,
                   (SELECT total_aldrete FROM bloc_sspi WHERE programmation_id = bp.id AND autorisation_sortie = 1 ORDER BY id DESC LIMIT 1) AS aldrete_sortie,
                   (SELECT MAX(date_saisie) FROM bloc_sspi WHERE programmation_id = bp.id) AS heure_sortie
            FROM bloc_programmation bp
            JOIN bloc_demandes bd ON bd.id = bp.demande_id
            JOIN patients p       ON p.id = bd.patient_id
            WHERE bp.statut = 'TERMINE' AND DATE(bp.date_intervention) = CURDATE()
            ORDER BY heure_sortie DESC
        ")->fetchAll(PDO::FETCH_ASSOC);

        require_once __DIR__ . '/../views/bloc/sspi_dashboard.php';
    }

    // ─── SALLE DE RÉVEIL (SSPI) — score d'Aldrete ─────────────────────────────
    public function sspi($prog_id) {
        $this->auth->requirePermission('laboratoire', 'read');
        $stmt = $this->db->prepare("SELECT bp.*, p.id AS patient_id, p.nom AS patient_nom, p.prenom AS patient_prenom, p.dossier_numero,
                   bs.nom_salle, u.nom AS chirurgien, bp.diagnostique_op,
                   (SELECT type_intervention FROM bloc_cro WHERE programmation_id=bp.id ORDER BY id DESC LIMIT 1) AS type_intervention
            FROM bloc_programmation bp
            JOIN bloc_demandes bd ON bd.id=bp.demande_id
            JOIN patients p ON p.id=bd.patient_id
            JOIN bloc_salles bs ON bs.id=bp.salle_id
            LEFT JOIN users u ON u.id=bd.chirurgien_id
            WHERE bp.id=?");
        $stmt->execute([(int)$prog_id]);
        $intervention = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$intervention) { die('Intervention introuvable.'); }
        // Relevés SSPI existants
        $sspi = $this->db->prepare("SELECT * FROM bloc_sspi WHERE programmation_id=? ORDER BY id DESC");
        $sspi->execute([(int)$prog_id]);
        $sspiList = $sspi->fetchAll(PDO::FETCH_ASSOC);

        // Services cliniques pour le transfert à la sortie de SSPI
        $servicesCliniques = [];
        try {
            $servicesCliniques = $this->db->query(
                "SELECT id, nom_service FROM services
                  WHERE categorie = 'CLINIQUE'
                    AND nom_service NOT LIKE 'Param%'
                    AND nom_service NOT LIKE 'Consultation%'
                  ORDER BY nom_service ASC"
            )->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {}

        // Relevés de paramètres vitaux (monitoring per-opératoire / SSPI)
        $monitoringList = [];
        try {
            $mStmt = $this->db->prepare(
                "SELECT * FROM bloc_monitoring WHERE programmation_id=? ORDER BY `heure_relevé` ASC"
            );
            $mStmt->execute([(int)$prog_id]);
            $monitoringList = $mStmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {}

        require_once __DIR__ . '/../views/bloc/sspi.php';
    }

    public function sauvegarderSSPI() {
        $this->auth->requirePermission('laboratoire', 'write');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: '.BASE_URL.'bloc'); exit; }
        $prog_id = (int)($_POST['programmation_id'] ?? 0);
        if (!$prog_id) { header('Location: '.BASE_URL.'bloc'); exit; }
        $i = fn($k) => (int)($_POST[$k] ?? 0);
        $total = $i('score_motricite')+$i('score_respiration')+$i('score_circulation')+$i('score_conscience')+$i('score_saturation');
        $autorise = !empty($_POST['autorisation_sortie']) ? 1 : 0;
        try {
            $this->db->prepare("INSERT INTO bloc_sspi
                (programmation_id, score_motricite, score_respiration, score_circulation, score_conscience, score_saturation, total_aldrete, autorisation_sortie)
                VALUES (?,?,?,?,?,?,?,?)")
                ->execute([$prog_id, $i('score_motricite'), $i('score_respiration'), $i('score_circulation'),
                           $i('score_conscience'), $i('score_saturation'), $total, $autorise]);

            $orientationLog = '';
            if ($autorise) {
                // Sortie SSPI autorisée → intervention terminée + salle remise disponible
                $prog = $this->db->prepare("SELECT bp.salle_id, bd.id AS demande_id, bd.patient_id FROM bloc_programmation bp JOIN bloc_demandes bd ON bd.id=bp.demande_id WHERE bp.id=?");
                $prog->execute([$prog_id]); $row = $prog->fetch(PDO::FETCH_ASSOC);
                $this->db->prepare("UPDATE bloc_programmation SET statut='TERMINE' WHERE id=?")->execute([$prog_id]);
                if ($row) {
                    $this->db->prepare("UPDATE bloc_demandes SET statut='TERMINE' WHERE id=?")->execute([$row['demande_id']]);
                    $this->db->prepare("UPDATE bloc_salles SET statut='DISPONIBLE' WHERE id=?")->execute([$row['salle_id']]);
                }

                // ── Orientation du patient à la sortie de SSPI ──────────────
                $orientation     = $_POST['orientation_sortie'] ?? '';
                $service_dest_id = (int)($_POST['service_dest_id'] ?? 0);
                $patient_id      = (int)($row['patient_id'] ?? 0);

                if ($patient_id && $orientation === 'TRANSFERT' && $service_dest_id) {
                    // Transfert vers un service : le patient apparaît dans la liste
                    // "À Hospitaliser" de l'infirmier du service de destination
                    $this->db->prepare(
                        "UPDATE patients SET service_id = ?, statut_hosp = 'A_HOSPITALISER',
                             statut_parcours = 'HOSPITALISE' WHERE id = ?"
                    )->execute([$service_dest_id, $patient_id]);

                    $svcNom = '';
                    try {
                        $s = $this->db->prepare("SELECT nom_service FROM services WHERE id = ?");
                        $s->execute([$service_dest_id]);
                        $svcNom = $s->fetchColumn() ?: '';
                    } catch (\Throwable $e) {}
                    $orientationLog = " · transféré vers $svcNom";

                    // Notifier les infirmiers du service de destination
                    try {
                        $pInfo = $this->db->prepare("SELECT nom, prenom, dossier_numero FROM patients WHERE id = ?");
                        $pInfo->execute([$patient_id]);
                        $pat = $pInfo->fetch(PDO::FETCH_ASSOC) ?: [];
                        require_once __DIR__ . '/../services/NotificationCenter.php';
                        (new NotificationCenter($this->db))->notifyByService($service_dest_id, [
                            'title'    => '🛏️ Patient sortant de SSPI à installer',
                            'message'  => trim(strtoupper($pat['nom'] ?? '') . ' ' . ($pat['prenom'] ?? ''))
                                        . ' (' . ($pat['dossier_numero'] ?? '') . ") — score d'Aldrete $total/10. "
                                        . 'Transfert depuis la salle de réveil, lit à attribuer.',
                            'priority' => 'high',
                            'link'     => 'dashboard',
                        ], ['INFIRMIER', 'MAJOR_INFIRMIER', 'MAJOR']);
                    } catch (\Throwable $e) { error_log('[Bloc] notif transfert SSPI: ' . $e->getMessage()); }

                } elseif ($patient_id && $orientation === 'DOMICILE') {
                    // Retour à domicile (chirurgie ambulatoire)
                    $this->db->prepare(
                        "UPDATE patients SET statut_parcours = 'SORTI', statut_hosp = 'AUCUN' WHERE id = ?"
                    )->execute([$patient_id]);
                    $orientationLog = ' · retour à domicile';
                }
            }
            try { $this->audit->log('CREATE','bloc_sspi',$prog_id,"Score d'Aldrete : $total/10".($autorise?' — sortie autorisée'.$orientationLog:''),null,['total_aldrete'=>$total,'sortie'=>$autorise]); } catch (\Throwable $e) {}

            header('Location: '.BASE_URL.($autorise ? 'bloc/sspi-dashboard?success=sortie' : 'bloc/sspi/'.$prog_id)); exit;
        } catch (\Throwable $e) {
            error_log('[Bloc] SSPI: '.$e->getMessage());
            header('Location: '.BASE_URL.'bloc/sspi/'.$prog_id.'?error=sspi'); exit;
        }
    }

    // ─── Changer le statut d'une salle ───────────────────────────────────────
    public function changerStatutSalle() {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['success'=>false]); exit; }
        $salle_id = (int)($_POST['salle_id'] ?? 0);
        $statut   = strtoupper(trim($_POST['statut'] ?? ''));
        if (!$salle_id || !in_array($statut, ['DISPONIBLE','NETTOYAGE','MAINTENANCE'])) {
            echo json_encode(['success'=>false,'message'=>'Données invalides']); exit;
        }
        try {
            $this->db->prepare("UPDATE bloc_salles SET statut=? WHERE id=?")->execute([$statut, $salle_id]);
            try { $this->audit->log('UPDATE','bloc_salles',$salle_id,"Salle → $statut",null,['statut'=>$statut]); } catch (\Throwable $e) {}
            echo json_encode(['success'=>true,'statut'=>$statut]);
        } catch (\Throwable $e) { echo json_encode(['success'=>false,'message'=>$e->getMessage()]); }
        exit;
    }

    /**
     * Transmet le patient au service d'anesthésie (Bouton A Opérer)
     */
    public function transmettreDemande() {
        header('Content-Type: application/json');

        $patient_id = $_POST['patient_id'] ?? null;
        $chirurgien_id = $_SESSION['user_id'] ?? null;
        $anesth_id = $_POST['anesthesiste_id'] ?? null;

        if (!$patient_id || !$chirurgien_id) {
            echo json_encode(['success' => false, 'message' => 'Session expirée ou patient inconnu']);
            return;
        }

        try {
            $this->db->beginTransaction();
            $stmt1 = $this->db->prepare("UPDATE patients SET statut = 'A_OPERER' WHERE id = ?");
            $stmt1->execute([$patient_id]);

            $sql = "INSERT INTO bloc_demandes (patient_id, chirurgien_id, anesthesiste_id, statut, date_demande)
                    VALUES (?, ?, ?, 'EN_ATTENTE', NOW())";
            $stmt2 = $this->db->prepare($sql);
            $stmt2->execute([$patient_id, $chirurgien_id, $anesth_id]);
            $demandeId = (int)$this->db->lastInsertId();

            $this->db->commit();

            // ── Audit : patient transmis au bloc opératoire ──
            try {
                $pRow = $this->db->prepare("SELECT nom, prenom FROM patients WHERE id = ?");
                $pRow->execute([$patient_id]);
                $pat  = $pRow->fetch(PDO::FETCH_ASSOC);
                $pNom = $pat ? trim(($pat['nom'] ?? '') . ' ' . ($pat['prenom'] ?? '')) : '';
                $this->audit->log('CREATE', 'interventions', $demandeId,
                    'Patient transmis au bloc opératoire' . ($pNom ? " — $pNom (#$patient_id)" : ''),
                    null, ['patient' => $pNom, 'patient_id' => (int)$patient_id, 'statut' => 'EN_ATTENTE']);
            } catch (Exception $e) { error_log('[Bloc::transmettre] Audit: ' . $e->getMessage()); }

            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            $this->db->rollBack();
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * Finalisation de la programmation (Attribution salle/heure)
     */
    public function programmerIntervention() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $sql = "INSERT INTO bloc_programmation (demande_id, salle_id, date_intervention, heure_debut, diagnostique_op)
                        VALUES (?, ?, ?, ?, ?)";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([
                    $_POST['demande_id'],
                    $_POST['salle_id'],
                    $_POST['date'],
                    $_POST['heure'],
                    $_POST['diagnostic']
                ]);

                // Mettre à jour le statut de la demande
                $this->db->prepare("UPDATE bloc_demandes SET statut = 'PROGRAMME' WHERE id = ?")
                         ->execute([$_POST['demande_id']]);

                // ── Audit : intervention programmée ──
                try {
                    $info = $this->db->prepare(
                        "SELECT p.nom, p.prenom, bs.nom_salle
                         FROM bloc_demandes bd
                         JOIN patients p ON p.id = bd.patient_id
                         LEFT JOIN bloc_salles bs ON bs.id = ?
                         WHERE bd.id = ?");
                    $info->execute([$_POST['salle_id'], $_POST['demande_id']]);
                    $i = $info->fetch(PDO::FETCH_ASSOC);
                    $pNom  = $i ? trim(($i['nom'] ?? '') . ' ' . ($i['prenom'] ?? '')) : '';
                    $salle = $i['nom_salle'] ?? '';
                    $desc  = 'Intervention programmée'
                           . ($pNom ? " — $pNom" : '')
                           . ' le ' . ($_POST['date'] ?? '?') . ' à ' . ($_POST['heure'] ?? '?')
                           . ($salle ? " (salle : $salle)" : '');
                    $this->audit->log('CREATE', 'interventions', (int)$_POST['demande_id'], $desc, null, [
                        'patient'    => $pNom,
                        'date'       => $_POST['date'] ?? null,
                        'heure'      => $_POST['heure'] ?? null,
                        'salle'      => $salle,
                        'diagnostic' => mb_substr($_POST['diagnostic'] ?? '', 0, 150),
                    ]);
                } catch (Exception $e) { error_log('[Bloc::programmer] Audit: ' . $e->getMessage()); }

                header('Location: ' . BASE_URL . 'bloc?success=1');
            } catch (Exception $e) {
die("Erreur de programmation : " . $e->getMessage());
            }

            header('Location: ' . BASE_URL . 'bloc?success=1');
        }
    }

    /**
     * Programmer directement une intervention depuis le dashboard bloc
     * (crée la demande + la programmation en une seule transaction)
     */
    public function programmerDirect() {
        header('Content-Type: application/json');
        try {
            require_once __DIR__ . '/../services/CsrfService.php';
            CsrfService::check();

            $patientId   = (int)($_POST['patient_id']       ?? 0);
            $salleId     = (int)($_POST['salle_id']         ?? 0);
            $date        = trim($_POST['date']              ?? '');
            $heure       = trim($_POST['heure']             ?? '');
            $diagnostic  = trim($_POST['diagnostic']        ?? '');
            // Chirurgien : préférer la sélection du formulaire, sinon user connecté
            $chirId      = !empty($_POST['chirurgien_id'])    ? (int)$_POST['chirurgien_id']    : (int)($_SESSION['user_id'] ?? 0);
            $anesthId    = !empty($_POST['anesthesiste_id'])  ? (int)$_POST['anesthesiste_id']  : null;
            $infCircId   = !empty($_POST['inf_circulant_id']) ? (int)$_POST['inf_circulant_id'] : null;
            $instrId     = !empty($_POST['instrumentiste_id'])? (int)$_POST['instrumentiste_id']: null;

            // Construire equipe_json avec les noms réels
            $equipe = [];
            $fetchNom = function(int $id, string $role) {
                $s = $this->db->prepare("SELECT nom, prenom FROM users WHERE id = ?");
                $s->execute([$id]);
                $r = $s->fetch(PDO::FETCH_ASSOC);
                return $r ? ['id'=>$id,'role'=>$role,'nom'=>trim(($r['prenom']??'').' '.($r['nom']??''))] : null;
            };
            if ($chirId)   { $n = $fetchNom($chirId,   'Chirurgien');            if ($n) $equipe[] = $n; }
            if ($anesthId) { $n = $fetchNom($anesthId, 'Anesthésiste');          if ($n) $equipe[] = $n; }
            if ($infCircId){ $n = $fetchNom($infCircId,'Infirmier circulant');   if ($n) $equipe[] = $n; }
            if ($instrId)  { $n = $fetchNom($instrId,  'Instrumentiste');        if ($n) $equipe[] = $n; }
            $equipeJson = json_encode($equipe, JSON_UNESCAPED_UNICODE);

            if (!$patientId || !$salleId || !$date || !$heure || !$diagnostic) {
                echo json_encode(['success' => false, 'message' => 'Tous les champs obligatoires sont requis.']);
                return;
            }

            $this->db->beginTransaction();

            // 1. Créer la demande
            $this->db->prepare(
                "INSERT INTO bloc_demandes (patient_id, chirurgien_id, anesthesiste_id, statut, date_demande)
                 VALUES (?, ?, ?, 'PROGRAMME', NOW())"
            )->execute([$patientId, $chirId, $anesthId]);
            $demandeId = (int)$this->db->lastInsertId();

            // 2. Créer la programmation avec équipe
            $this->db->prepare(
                "INSERT INTO bloc_programmation (demande_id, salle_id, date_intervention, heure_debut, diagnostique_op, equipe_json)
                 VALUES (?, ?, ?, ?, ?, ?)"
            )->execute([$demandeId, $salleId, $date, $heure, $diagnostic, $equipeJson]);
            $progId = (int)$this->db->lastInsertId();

            // 3. Mettre à jour le statut patient
            $this->db->prepare("UPDATE patients SET statut = 'A_OPERER' WHERE id = ?")->execute([$patientId]);

            $this->db->commit();

            // Audit
            try {
                $pat = $this->db->prepare("SELECT nom, prenom FROM patients WHERE id = ?");
                $pat->execute([$patientId]);
                $p = $pat->fetch(PDO::FETCH_ASSOC);
                $pNom = $p ? trim(($p['nom'] ?? '') . ' ' . ($p['prenom'] ?? '')) : '';
                $sal = $this->db->prepare("SELECT nom_salle FROM bloc_salles WHERE id = ?");
                $sal->execute([$salleId]);
                $s = $sal->fetchColumn() ?: '';
                $this->audit->log('CREATE', 'interventions', $progId,
                    "Intervention programmée directement depuis le bloc — $pNom · $date $heure" . ($s ? " · $s" : ''),
                    null, ['patient' => $pNom, 'date' => $date, 'heure' => $heure, 'salle' => $s]);
            } catch (\Throwable $e) { error_log('[Bloc::programmerDirect] Audit: ' . $e->getMessage()); }

            echo json_encode(['success' => true, 'prog_id' => $progId]);

        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * Monitoring Cockpit - Surveillance temps réel d'une intervention
     * GET: Affiche le cockpit pour l'intervention
     * POST: Ajoute des données de monitoring via AJAX (bloc/add-monitoring)
     */
    public function monitoring($intervention_id) {
        $this->auth->requirePermission('bloc', 'read');
        $intervention_id = (int)$intervention_id;

        // POST : enregistrement d'un relevé de constantes per-opératoire
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            header('Content-Type: application/json');
            $val = fn($k) => (isset($_POST[$k]) && $_POST[$k] !== '') ? $_POST[$k] : null;
            $bpm = $val('bpm');
            if (!$intervention_id || $bpm === null) {
                echo json_encode(['success' => false, 'message' => 'FC obligatoire']); return;
            }
            try {
                $this->db->prepare("INSERT INTO bloc_monitoring
                    (programmation_id, bpm, spo2, ta_sys, ta_dia, fr, temp)
                    VALUES (?,?,?,?,?,?,?)")
                    ->execute([$intervention_id, (int)$bpm, $val('spo2'), $val('ta_sys'), $val('ta_dia'), $val('fr'), $val('temp')]);

                // Détection de valeurs critiques → notification
                $alertes = [];
                $spo2v  = $val('spo2')   !== null ? (float)$val('spo2')   : null;
                $tasys  = $val('ta_sys') !== null ? (float)$val('ta_sys') : null;
                $tadia  = $val('ta_dia') !== null ? (float)$val('ta_dia') : null;
                $frv    = $val('fr')     !== null ? (float)$val('fr')     : null;
                $tempv  = $val('temp')   !== null ? (float)$val('temp')   : null;
                $bpmv   = (int)$bpm;

                if ($bpmv <= 45 || $bpmv >= 120)  $alertes[] = "FC critique : {$bpmv} bpm";
                if ($spo2v  !== null && $spo2v  <= 90) $alertes[] = "SpO₂ critique : {$spo2v}%";
                if ($tasys  !== null && ($tasys  <= 80 || $tasys  >= 180)) $alertes[] = "TA Sys critique : {$tasys} mmHg";
                if ($tadia  !== null && ($tadia  <= 50 || $tadia  >= 110)) $alertes[] = "TA Dia critique : {$tadia} mmHg";
                if ($frv    !== null && ($frv    <= 8  || $frv    >= 30))  $alertes[] = "FR critique : {$frv} c/min";
                if ($tempv  !== null && ($tempv  <= 35 || $tempv  >= 38.5))$alertes[] = "Température critique : {$tempv}°C";

                if (!empty($alertes)) {
                    try {
                        $prog = $this->db->prepare(
                            "SELECT p.nom, p.prenom, p.dossier_numero, bd.anesthesiste_id
                             FROM bloc_programmation bp
                             JOIN bloc_demandes bd ON bd.id = bp.demande_id
                             JOIN patients p ON p.id = bd.patient_id
                             WHERE bp.id = ? LIMIT 1"
                        );
                        $prog->execute([$intervention_id]);
                        $pr = $prog->fetch(\PDO::FETCH_ASSOC);
                        if ($pr) {
                            $nomPat  = strtoupper($pr['nom']).' '.$pr['prenom'];
                            $details = implode(' | ', $alertes);
                            $msg     = "🚨 SSPI [{$pr['dossier_numero']}] {$nomPat} — {$details}";
                            require_once __DIR__ . '/../../services/NotificationCenter.php';
                            NotificationCenter::notifyByRole($this->db, ['MEDECIN','INFIRMIER_ANESTHESISTE'],
                                $msg, 'bloc/sspi/'.$intervention_id, 'ALERTE');
                        }
                    } catch (\Throwable $e2) { error_log('[SSPI alert] '.$e2->getMessage()); }
                }

                echo json_encode(['success' => true, 'alertes' => $alertes]);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
            return;
        }

        // GET : cockpit de l'intervention en cours
        $query = "SELECT bp.*, p.nom AS patient_nom, p.prenom AS patient_prenom, p.dossier_numero,
                         p.date_naissance, p.sexe, u.nom AS chirurgien, ua.nom AS anesthesiste,
                         bs.nom_salle, bp.diagnostique_op, COALESCE(bp.statut,'PROGRAMME') AS statut
                  FROM bloc_programmation bp
                  JOIN bloc_demandes bd ON bp.demande_id = bd.id
                  JOIN patients p       ON bd.patient_id = p.id
                  JOIN bloc_salles bs   ON bp.salle_id = bs.id
                  LEFT JOIN users u     ON bd.chirurgien_id = u.id
                  LEFT JOIN users ua    ON bd.anesthesiste_id = ua.id
                  WHERE bp.id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$intervention_id]);
        $intervention = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$intervention) { http_response_code(404); die('Intervention introuvable.'); }

        $stmt = $this->db->prepare("SELECT * FROM bloc_monitoring WHERE programmation_id = ? ORDER BY id DESC LIMIT 30");
        $stmt->execute([$intervention_id]);
        $recent_vitals = $stmt->fetchAll(PDO::FETCH_ASSOC);

        require_once __DIR__ . '/../views/bloc/monitoring_live.php';
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  ADMIN — Gestion du bloc opératoire
    // ─────────────────────────────────────────────────────────────────────────
    public function adminBloc() {
        $this->requireRole(['ADMIN']);

        $salles = [];
        try {
            $salles = $this->db->query("
                SELECT bs.*,
                       COUNT(bp.id)                                                            AS total_interventions,
                       SUM(bp.date_intervention = CURDATE())                                   AS interventions_jour
                FROM bloc_salles bs
                LEFT JOIN bloc_programmation bp ON bp.salle_id = bs.id
                GROUP BY bs.id
                ORDER BY bs.nom_salle
            ")->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Throwable $e) { error_log('[AdminBloc] salles: ' . $e->getMessage()); }

        $stats = [
            'total'              => count($salles),
            'disponibles'        => count(array_filter($salles, fn($s) => strtoupper($s['statut'] ?? '') === 'DISPONIBLE')),
            'occupees'           => count(array_filter($salles, fn($s) => strtoupper($s['statut'] ?? '') === 'OCCUPEE')),
            'maintenance'        => count(array_filter($salles, fn($s) => in_array(strtoupper($s['statut'] ?? ''), ['MAINTENANCE','NETTOYAGE']))),
            'interventions_jour' => 0,
            'en_attente'         => 0,
        ];
        try { $stats['interventions_jour'] = (int)$this->db->query("SELECT COUNT(*) FROM bloc_programmation WHERE date_intervention = CURDATE()")->fetchColumn(); } catch (\Throwable $e) {}
        try { $stats['en_attente']         = (int)$this->db->query("SELECT COUNT(*) FROM bloc_demandes WHERE statut = 'EN_ATTENTE'")->fetchColumn(); } catch (\Throwable $e) {}

        require_once __DIR__ . '/../views/admin/gestion_bloc.php';
    }

    public function sauvegarderSalle() {
        header('Content-Type: application/json; charset=utf-8');
        $this->requireSuperAdmin();

        $id         = (int)($_POST['id']         ?? 0);
        $nom        = trim($_POST['nom_salle']   ?? '');
        $type       = trim($_POST['type_salle']  ?? 'Chirurgie générale');
        $statut     = strtoupper(trim($_POST['statut'] ?? 'DISPONIBLE'));

        if (!$nom) { echo json_encode(['success'=>false,'message'=>'Le nom de la salle est requis.']); exit; }
        if (!in_array($statut, ['DISPONIBLE','NETTOYAGE','MAINTENANCE'])) $statut = 'DISPONIBLE';

        try {
            if ($id) {
                $this->db->prepare("UPDATE bloc_salles SET nom_salle=?, type_salle=?, statut=? WHERE id=?")
                         ->execute([$nom, $type, $statut, $id]);
                $msg = "Salle « $nom » modifiée avec succès.";
            } else {
                $this->db->prepare("INSERT INTO bloc_salles (nom_salle, type_salle, statut) VALUES (?,?,?)")
                         ->execute([$nom, $type, $statut]);
                $id  = (int)$this->db->lastInsertId();
                $msg = "Salle « $nom » créée avec succès.";
            }
            try { $this->audit->log($_SESSION['user_id'] ?? 0, 'UPDATE', 'bloc_salles', $id, $msg); } catch (\Throwable $e) {}
            echo json_encode(['success'=>true,'message'=>$msg,'id'=>$id]);
        } catch (\Throwable $e) {
            echo json_encode(['success'=>false,'message'=>'Erreur : ' . $e->getMessage()]);
        }
        exit;
    }

    // ─── MODIFIER une intervention programmée (AJAX POST) ────────────────────
    public function modifierIntervention() {
        ob_start();
        header('Content-Type: application/json; charset=utf-8');
        require_once __DIR__ . '/../services/CsrfService.php';

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            ob_clean(); echo json_encode(['ok'=>false,'error'=>'POST requis']); exit;
        }
        if (!CsrfService::validate()) {
            http_response_code(403);
            ob_clean(); echo json_encode(['ok'=>false,'error'=>'CSRF invalide']); exit;
        }

        $id        = (int)($_POST['id'] ?? 0);
        $salle     = (int)($_POST['salle_id'] ?? 0);
        $date      = trim($_POST['date_intervention'] ?? '');
        $heure     = trim($_POST['heure_debut'] ?? '');
        $diag      = trim($_POST['diagnostique_op'] ?? '');
        $chirId    = !empty($_POST['chirurgien_id'])    ? (int)$_POST['chirurgien_id']    : null;
        $anesthId  = !empty($_POST['anesthesiste_id'])  ? (int)$_POST['anesthesiste_id']  : null;
        $infCircId = !empty($_POST['inf_circulant_id']) ? (int)$_POST['inf_circulant_id'] : null;
        $instrId   = !empty($_POST['instrumentiste_id'])? (int)$_POST['instrumentiste_id']: null;

        if (!$id || !$salle || !$date || !$heure) {
            ob_clean(); echo json_encode(['ok'=>false,'error'=>'Champs requis manquants']); exit;
        }

        try {
            // Récupérer demande_id
            $row = $this->db->prepare("SELECT demande_id FROM bloc_programmation WHERE id=?");
            $row->execute([$id]);
            $prog = $row->fetch(PDO::FETCH_ASSOC);

            // Construire equipe_json
            $fetchNom = function(?int $uid, string $role) {
                if (!$uid) return null;
                $s = $this->db->prepare("SELECT nom, prenom FROM users WHERE id=?");
                $s->execute([$uid]);
                $r = $s->fetch(PDO::FETCH_ASSOC);
                return $r ? ['id'=>$uid,'role'=>$role,'nom'=>trim(($r['prenom']??'').' '.($r['nom']??''))] : null;
            };
            $equipe = array_filter([
                $fetchNom($chirId,    'Chirurgien'),
                $fetchNom($anesthId,  'Anesthésiste'),
                $fetchNom($infCircId, 'Infirmier circulant'),
                $fetchNom($instrId,   'Instrumentiste'),
            ]);
            $equipeJson = json_encode(array_values($equipe), JSON_UNESCAPED_UNICODE);

            $this->db->prepare(
                "UPDATE bloc_programmation
                 SET salle_id=?, date_intervention=?, heure_debut=?, diagnostique_op=?, equipe_json=?
                 WHERE id=? AND statut='PROGRAMME'"
            )->execute([$salle, $date, $heure, $diag, $equipeJson, $id]);

            if ($prog && $chirId) {
                $this->db->prepare(
                    "UPDATE bloc_demandes SET chirurgien_id=?, anesthesiste_id=? WHERE id=?"
                )->execute([$chirId, $anesthId, $prog['demande_id']]);
            }

            ob_clean();
            echo json_encode(['ok'=>true]);
        } catch (Exception $e) {
            http_response_code(500);
            ob_clean();
            echo json_encode(['ok'=>false,'error'=>$e->getMessage()]);
        }
        exit;
    }

    // ─── ANNULER une intervention programmée (AJAX POST) ─────────────────────
    public function annulerIntervention() {
        ob_start();
        header('Content-Type: application/json; charset=utf-8');
        require_once __DIR__ . '/../services/CsrfService.php';

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            ob_clean(); echo json_encode(['ok'=>false,'error'=>'POST requis']); exit;
        }
        if (!CsrfService::validate()) {
            http_response_code(403);
            ob_clean(); echo json_encode(['ok'=>false,'error'=>'CSRF invalide']); exit;
        }

        $id = (int)($_POST['id'] ?? 0);
        if (!$id) {
            ob_clean(); echo json_encode(['ok'=>false,'error'=>'ID invalide']); exit;
        }

        try {
            $stmt = $this->db->prepare("SELECT demande_id FROM bloc_programmation WHERE id=? AND statut='PROGRAMME'");
            $stmt->execute([$id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$row) {
                ob_clean(); echo json_encode(['ok'=>false,'error'=>'Intervention introuvable ou déjà démarrée']); exit;
            }

            $this->db->prepare("UPDATE bloc_programmation SET statut='ANNULE' WHERE id=?")->execute([$id]);
            $this->db->prepare("UPDATE bloc_demandes SET statut='EN_ATTENTE' WHERE id=?")->execute([$row['demande_id']]);

            ob_clean();
            echo json_encode(['ok'=>true]);
        } catch (Exception $e) {
            http_response_code(500);
            ob_clean();
            echo json_encode(['ok'=>false,'error'=>$e->getMessage()]);
        }
        exit;
    }

    public function supprimerSalle() {
        header('Content-Type: application/json; charset=utf-8');
        $this->requireSuperAdmin();

        $id = (int)($_POST['id'] ?? 0);
        if (!$id) { echo json_encode(['success'=>false,'message'=>'ID manquant.']); exit; }

        try {
            $stmt = $this->db->prepare(
                "SELECT COUNT(*) FROM bloc_programmation
                 WHERE salle_id = ? AND statut IN ('EN_COURS','PROGRAMME') AND date_intervention >= CURDATE()"
            );
            $stmt->execute([$id]);
            $actives = (int)$stmt->fetchColumn();
            if ($actives > 0) {
                echo json_encode(['success'=>false,'message'=>"Impossible : $actives intervention(s) planifiée(s) sur cette salle."]);
                exit;
            }
            $nomStmt = $this->db->prepare("SELECT nom_salle FROM bloc_salles WHERE id=?");
            $nomStmt->execute([$id]);
            $nom = $nomStmt->fetchColumn();

            $this->db->prepare("DELETE FROM bloc_salles WHERE id=?")->execute([$id]);
            try { $this->audit->log($_SESSION['user_id'] ?? 0, 'DELETE', 'bloc_salles', $id, "Salle #$id ($nom) supprimée"); } catch (\Throwable $e) {}
            echo json_encode(['success'=>true,'message'=>"Salle « $nom » supprimée."]);
        } catch (\Throwable $e) {
            echo json_encode(['success'=>false,'message'=>'Erreur : ' . $e->getMessage()]);
        }
        exit;
    }
}
