<?php
/* ============================================================================
   FICHIER : app/controllers/AdminDashboardController.php
   CONTRÔLEUR D'ADMINISTRATION - GESTION DES KPI ET MONITORING
   ============================================================================ */

require_once __DIR__ . '/UnifiedController.php';

class AdminDashboardController extends UnifiedController {

    public function __construct() {
        parent::__construct();
    }

    /**
     * Dashboard Principal de l'Administrateur (enrichi)
     */
    public function dashboard() {
        $this->requireRole(['ADMIN']);
        $db = (new Database())->getConnection();

        $stats = [
            'total_patients'    => 0,
            'patients_ce_mois'  => 0,
            'hosp_actuelles'    => 0,
            'consultations_aujourd_hui' => 0,
            'consultations_ce_mois'     => 0,
            'ca_du_mois'        => 0,
            'alertes_stock'     => 0,
            'total_users'       => 0,
            'users_actifs'      => 0,
            'lits_occupes'      => 0,
            'lits_total'        => 0,
            'urgences_en_cours' => 0,
            'bilans_en_attente' => 0,
            'patients_attente'  => 0,
        ];

        try {
            $stats['total_patients']   = (int)$db->query("SELECT COUNT(*) FROM patients WHERE actif=1")->fetchColumn();
            $stats['patients_ce_mois'] = (int)$db->query("SELECT COUNT(*) FROM patients WHERE MONTH(created_at)=MONTH(CURDATE()) AND YEAR(created_at)=YEAR(CURDATE()) AND actif=1")->fetchColumn();
            $stats['hosp_actuelles']   = (int)$db->query("SELECT COUNT(*) FROM hospitalisations WHERE statut='en_cours'")->fetchColumn();
            $stats['consultations_aujourd_hui'] = (int)$db->query("SELECT COUNT(*) FROM consultations WHERE DATE(date_consultation)=CURDATE()")->fetchColumn();
            $stats['consultations_ce_mois']     = (int)$db->query("SELECT COUNT(*) FROM consultations WHERE MONTH(date_consultation)=MONTH(CURDATE()) AND YEAR(date_consultation)=YEAR(CURDATE())")->fetchColumn();
            $stats['total_users']  = (int)$db->query("SELECT COUNT(*) FROM users")->fetchColumn();
            $stats['users_actifs'] = (int)$db->query("SELECT COUNT(*) FROM users WHERE actif=1")->fetchColumn();
            $stats['lits_total']   = (int)$db->query("SELECT COUNT(*) FROM lits WHERE actif=1")->fetchColumn();
            $stats['lits_occupes'] = (int)$db->query("SELECT COUNT(*) FROM hospitalisations WHERE statut='en_cours'")->fetchColumn();
            $stats['patients_attente'] = (int)$db->query("SELECT COUNT(*) FROM patients WHERE statut_parcours IN ('PARAMETRES','ATTENTE_CONSULTATION') AND actif=1")->fetchColumn();
        } catch (Exception $e) { error_log("KPI erreur: ".$e->getMessage()); }

        try { $stats['alertes_stock']    = (int)$db->query("SELECT COUNT(*) FROM medicaments WHERE quantite <= seuil_alerte")->fetchColumn(); } catch (Exception $e) {}
        try { $stats['urgences_en_cours']= (int)$db->query("SELECT COUNT(*) FROM urgences_patients WHERE statut IN ('ATTENTE','EN_COURS')")->fetchColumn(); } catch (Exception $e) {}
        try { $stats['bilans_en_attente']= (int)$db->query("SELECT COUNT(*) FROM demandes_laboratoire WHERE statut='en_attente'")->fetchColumn(); } catch (Exception $e) {}
        try { $stats['interventions_jour']= (int)$db->query("SELECT COUNT(*) FROM bloc_programmation WHERE date_intervention = CURDATE()")->fetchColumn(); } catch (Exception $e) {}
        try { $stats['sspi_en_cours']    = (int)$db->query("SELECT COUNT(*) FROM bloc_programmation WHERE statut = 'EN_SSPI'")->fetchColumn(); } catch (Exception $e) {}
        try {
            $stats['ca_du_mois'] = (float)($db->query("SELECT SUM(montant_ttc) FROM factures WHERE statut='payee' AND MONTH(date_facture)=MONTH(CURDATE()) AND YEAR(date_facture)=YEAR(CURDATE())")->fetchColumn() ?: 0);
        } catch (Exception $e) {}

        // ── Activité par service (top 8) ──────────────────────────────────────
        $activite_services = [];
        try {
            $activite_services = $db->query("
                SELECT s.nom_service, COUNT(c.id) AS nb_consultations
                FROM services s
                LEFT JOIN consultations c ON c.service_id = s.id
                    AND MONTH(c.date_consultation) = MONTH(CURDATE())
                    AND YEAR(c.date_consultation) = YEAR(CURDATE())
                WHERE s.nom_service NOT LIKE '%Accueil%'
                  AND s.nom_service NOT LIKE '%Param%'
                  AND s.nom_service NOT LIKE '%Administ%'
                GROUP BY s.id, s.nom_service
                ORDER BY nb_consultations DESC
                LIMIT 8
            ")->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {}

        // ── Distribution des rôles utilisateurs ─────────────────────────────
        $repartition_roles = [];
        try {
            $repartition_roles = $db->query("
                SELECT role, COUNT(*) AS nb FROM users WHERE actif=1 GROUP BY role ORDER BY nb DESC
            ")->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {}

        // ── Derniers logs d'audit ─────────────────────────────────────────────
        $recent_logs = [];
        try {
            $recent_logs = $db->query("
                SELECT al.action, al.table_name, al.description, al.created_at,
                       CONCAT(u.prenom,' ',u.nom) AS utilisateur
                FROM audit_logs al
                LEFT JOIN users u ON al.user_id = u.id
                ORDER BY al.created_at DESC LIMIT 10
            ")->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {}

        // ── Évolution consultations 7 derniers jours ─────────────────────────
        $evol_7j = [];
        try {
            $evol_7j = $db->query("
                SELECT DATE_FORMAT(date_consultation,'%d/%m') AS jour,
                       COUNT(*) AS nb
                FROM consultations
                WHERE date_consultation >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
                GROUP BY DATE(date_consultation)
                ORDER BY DATE(date_consultation) ASC
            ")->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {}

        $system_status  = $this->getSystemStatus();

        $data = [
            'stats'              => $stats,
            'activite_services'  => $activite_services,
            'repartition_roles'  => $repartition_roles,
            'recent_logs'        => $recent_logs,
            'evol_7j'            => $evol_7j,
            'system_status'      => $system_status,
            'performance_summary'=> $this->getPerformanceSummary(),
        ];

        $this->render('admin/dashboard', $data);
    }

    /**
     * GET admin/dashboard/live-kpi  — endpoint AJAX pour rafraîchissement temps réel
     */
    public function liveKpi(): void {
        header('Content-Type: application/json; charset=utf-8');
        $this->requireRole(['ADMIN']);
        $db = (new Database())->getConnection();

        $kpi = [];
        try {
            $kpi['patients_attente']  = (int)$db->query("SELECT COUNT(*) FROM patients WHERE statut_parcours IN ('PARAMETRES','ATTENTE_CONSULTATION') AND actif=1")->fetchColumn();
            $kpi['hosp_actuelles']    = (int)$db->query("SELECT COUNT(*) FROM hospitalisations WHERE statut='en_cours'")->fetchColumn();
            $kpi['urgences_en_cours'] = (int)$db->query("SELECT COUNT(*) FROM urgences_patients WHERE statut IN ('ATTENTE','EN_COURS')")->fetchColumn();
            $kpi['consultations_aujourd_hui'] = (int)$db->query("SELECT COUNT(*) FROM consultations WHERE DATE(date_consultation)=CURDATE()")->fetchColumn();
            $kpi['bilans_en_attente'] = (int)$db->query("SELECT COUNT(*) FROM demandes_laboratoire WHERE statut='en_attente'")->fetchColumn();
            $kpi['alertes_stock']     = (int)$db->query("SELECT COUNT(*) FROM medicaments WHERE quantite <= seuil_alerte")->fetchColumn();
        } catch (Exception $e) {}

        echo json_encode(['success' => true, 'kpi' => $kpi, 'ts' => date('H:i:s')]);
    }

    /**
     * Méthode privée pour simuler ou récupérer l'état du serveur
     */
    private function getSystemStatus() {
        return [
            'CPU' => [
                'value' => (function_exists('sys_getloadavg')) ? round(sys_getloadavg()[0] * 10, 1) : rand(2, 8),
                'unit'  => '%'
            ],
            'MEMORY' => [
                'value' => round(memory_get_usage(true) / 1024 / 1024, 2),
                'unit'  => 'MB'
            ]
        ];
    }

    private function getPerformanceSummary() {
        return [
            'avg_response'   => 145,
            'total_requests' => 2500,
            'error_count'    => 0
        ];
    }

    // Garder les autres méthodes privées si vous en aviez...

    /* ================================================================
       SUIVI EN TEMPS RÉEL DU PARCOURS PATIENT (Admin)
       ================================================================ */
    public function suiviParcours(): void {
        $this->requireRole(['ADMIN', 'COORDONNATEUR_SOINS']);
        $db = (new Database())->getConnection();

        // Tous les services cliniques
        $services = $db->query("SELECT id, nom_service FROM services ORDER BY nom_service")->fetchAll(PDO::FETCH_ASSOC);

        // Tous les médecins actifs
        $medecins = $db->query("
            SELECT id, nom, prenom, specialite
            FROM users
            WHERE actif = 1
              AND UPPER(role) IN ('MEDECIN','CHIRURGIEN','GENERALISTE','MEDECIN_URGENCES',
                                  'MEDECIN_CHEF','PEDIATRE','GYNECOLOGUE','DERMATOLOGUE',
                                  'OPHTALMOLOGUE','CARDIOLOGUE','RADIOLOGUE','NEUROLOGUE')
            ORDER BY nom, prenom
        ")->fetchAll(PDO::FETCH_ASSOC);

        // Patients actifs du jour (créés aujourd'hui OU encore en parcours actif)
        $patients = $db->query("
            SELECT p.id, p.nom, p.prenom, p.dossier_numero, p.sexe, p.date_naissance,
                   p.statut_parcours, p.statut, p.statut_hosp,
                   p.service_id, p.medecin_id,
                   p.circuit,
                   p.numero_ordre, p.created_at,
                   s.nom_service,
                   CONCAT(u.prenom,' ',u.nom) AS medecin_nom,
                   -- Dernière consultation
                   (SELECT DATE_FORMAT(MAX(c2.date_consultation),'%H:%i')
                    FROM consultations c2
                    WHERE c2.patient_id = p.id
                      AND DATE(c2.date_consultation) = CURDATE()
                   ) AS heure_derniere_consult,
                   -- Dernier bilan labo
                   (SELECT DATE_FORMAT(MAX(dl.date_creation),'%H:%i')
                    FROM demandes_laboratoire dl
                    WHERE dl.patient_id = p.id
                      AND DATE(dl.date_creation) = CURDATE()
                   ) AS heure_dernier_labo,
                   -- Hospitalisé : nom lit
                   l.nom_lit, ch.nom_chambre,
                   TIMESTAMPDIFF(MINUTE, p.created_at, NOW()) AS minutes_depuis_creation
            FROM patients p
            LEFT JOIN services s  ON s.id = p.service_id
            LEFT JOIN users u     ON u.id = p.medecin_id
            LEFT JOIN hospitalisations h ON h.patient_id = p.id AND h.statut = 'en_cours'
            LEFT JOIN lits l      ON l.id = h.lit_id
            LEFT JOIN chambres ch ON ch.id = l.chambre_id
            WHERE p.actif = 1
              AND (
                  DATE(p.created_at) = CURDATE()
                  OR p.statut_parcours IN (
                      'ACCUEIL','PARAMETRES','PARAMETRES_MATERNITE',
                      'ATTENTE_CONSULTATION','EN_CONSULTATION',
                      'URGENCES','HOSPITALISE'
                  )
              )
            ORDER BY
              FIELD(p.statut_parcours,
                'URGENCES','EN_CONSULTATION','ATTENTE_CONSULTATION',
                'PARAMETRES','PARAMETRES_MATERNITE','ACCUEIL',
                'HOSPITALISE','ABSENT_24H','SORTI','SORTIE_RECENTE'
              ),
              p.numero_ordre ASC, p.created_at ASC
        ")->fetchAll(PDO::FETCH_ASSOC);

        // Compteurs par statut
        $compteurs = [];
        foreach ($patients as $p) {
            $st = $p['statut_parcours'] ?? 'INCONNU';
            $compteurs[$st] = ($compteurs[$st] ?? 0) + 1;
        }

        require_once __DIR__ . '/../views/admin/suivi_parcours.php';
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  EXPORT SQL — téléchargement de la base de données complète
    // ─────────────────────────────────────────────────────────────────────────
    public function exportSQL() {
        $this->requireSuperAdmin();

        $dbName   = defined('DB_NAME') ? DB_NAME : 'dme_hospital';
        $filename = 'dme_hsjm_' . date('Y-m-d_His') . '.sql';

        // Désactiver le timeout PHP et le buffering pour le streaming
        @set_time_limit(300);
        while (ob_get_level()) ob_end_clean();

        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-store, no-cache, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');

        $db       = (new Database())->getConnection();
        $userName = trim(($_SESSION['user_prenom'] ?? '') . ' ' . ($_SESSION['user_nom'] ?? 'Admin'));

        // ── En-tête du fichier SQL ────────────────────────────────────────────
        echo "-- ===========================================================\n";
        echo "-- SimCare+ DME HSJM — Sauvegarde de la base de données\n";
        echo "-- Base de données : $dbName\n";
        echo "-- Date d'export   : " . date('Y-m-d H:i:s') . "\n";
        echo "-- Exporté par     : $userName\n";
        echo "-- ===========================================================\n\n";
        echo "SET FOREIGN_KEY_CHECKS = 0;\n";
        echo "SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';\n";
        echo "SET NAMES utf8mb4;\n";
        echo "SET CHARACTER SET utf8mb4;\n\n";
        flush();

        // ── Parcourir les tables ──────────────────────────────────────────────
        $tables = $db->query(
            "SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'"
        )->fetchAll(PDO::FETCH_COLUMN);

        foreach ($tables as $table) {
            echo "\n-- -----------------------------------------------------------\n";
            echo "-- Table : `$table`\n";
            echo "-- -----------------------------------------------------------\n\n";

            // CREATE TABLE
            $createRow = $db->query("SHOW CREATE TABLE `$table`")->fetch(PDO::FETCH_NUM);
            echo "DROP TABLE IF EXISTS `$table`;\n";
            echo $createRow[1] . ";\n";

            // Données
            $count = (int)$db->query("SELECT COUNT(*) FROM `$table`")->fetchColumn();
            if ($count === 0) { flush(); continue; }

            $colsMeta = $db->query("SHOW COLUMNS FROM `$table`")->fetchAll(PDO::FETCH_ASSOC);
            $colList  = implode(', ', array_map(fn($c) => '`' . $c['Field'] . '`', $colsMeta));

            $offset = 0;
            $batch  = 500;
            while ($offset < $count) {
                $rows = $db->query(
                    "SELECT * FROM `$table` LIMIT $batch OFFSET $offset"
                )->fetchAll(PDO::FETCH_NUM);
                if (!$rows) break;

                $valueBlocks = [];
                foreach ($rows as $row) {
                    $vals = array_map(function ($v) use ($db) {
                        if ($v === null) return 'NULL';
                        return $db->quote($v); // retourne 'valeur' avec guillemets
                    }, $row);
                    $valueBlocks[] = '(' . implode(', ', $vals) . ')';
                }
                echo "\nINSERT INTO `$table` ($colList) VALUES\n"
                   . implode(",\n", $valueBlocks) . ";\n";

                $offset += $batch;
                flush();
            }
        }

        // ── Vues SQL ─────────────────────────────────────────────────────────
        $views = $db->query(
            "SHOW FULL TABLES WHERE Table_type = 'VIEW'"
        )->fetchAll(PDO::FETCH_COLUMN);

        if ($views) {
            echo "\n\n-- ===========================================================\n";
            echo "-- VUES\n";
            echo "-- ===========================================================\n";
            foreach ($views as $view) {
                $createRow = $db->query("SHOW CREATE VIEW `$view`")->fetch(PDO::FETCH_NUM);
                echo "\nDROP VIEW IF EXISTS `$view`;\n";
                echo $createRow[1] . ";\n";
            }
            flush();
        }

        echo "\n\nSET FOREIGN_KEY_CHECKS = 1;\n";
        echo "\n-- Fin de la sauvegarde — " . date('Y-m-d H:i:s') . "\n";

        // Audit
        try {
            require_once __DIR__ . '/../services/AuditService.php';
            (new AuditService())->log(
                $_SESSION['user_id'] ?? 0, 'EXPORT', 'database', 0,
                "Export SQL de la base $dbName par $userName"
            );
        } catch (\Throwable $e) {}

        exit;
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  RESTAURATION SQL — import d'un fichier .sql (ADMIN seulement)
    // ─────────────────────────────────────────────────────────────────────────
    public function restaurerSQL(): void {
        header('Content-Type: application/json; charset=utf-8');
        $this->requireSuperAdmin();

        // Fichier présent ?
        if (empty($_FILES['sql_file']) || $_FILES['sql_file']['error'] !== UPLOAD_ERR_OK) {
            $codes = [1=>'trop grand (ini)',2=>'trop grand (form)',3=>'partiel',4=>'absent',6=>'tmp absent',7=>'écriture impossible'];
            $err   = $codes[$_FILES['sql_file']['error'] ?? 4] ?? 'inconnu';
            echo json_encode(['success' => false, 'message' => "Erreur d'upload : $err."]);
            exit;
        }

        $file    = $_FILES['sql_file'];
        $maxSize = 100 * 1024 * 1024; // 100 Mo

        // Taille
        if ($file['size'] > $maxSize) {
            echo json_encode(['success' => false, 'message' => 'Fichier trop volumineux (max 100 Mo).']);
            exit;
        }

        // Extension .sql obligatoire
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if ($ext !== 'sql') {
            echo json_encode(['success' => false, 'message' => 'Seuls les fichiers .sql sont acceptés.']);
            exit;
        }

        // Lecture du contenu
        $sql = file_get_contents($file['tmp_name']);
        if ($sql === false || trim($sql) === '') {
            echo json_encode(['success' => false, 'message' => 'Fichier vide ou illisible.']);
            exit;
        }

        // Suppression du BOM UTF-8 éventuel
        $sql = ltrim($sql, "\xEF\xBB\xBF");

        // Découpage en instructions individuelles (respecte les chaînes et commentaires)
        $statements = $this->splitSqlStatements($sql);

        if (empty($statements)) {
            echo json_encode(['success' => false, 'message' => 'Aucune instruction SQL valide trouvée.']);
            exit;
        }

        $db = (new Database())->getConnection();

        // Désactiver les contraintes le temps de la restauration
        $db->exec('SET FOREIGN_KEY_CHECKS = 0');
        $db->exec('SET SQL_MODE = ""');
        $db->exec('SET NAMES utf8mb4');

        $executed = 0;
        $errors   = [];

        $db->beginTransaction();
        try {
            foreach ($statements as $stmt) {
                if (trim($stmt) === '') continue;
                $db->exec($stmt);
                $executed++;
            }
            $db->commit();
        } catch (\Throwable $e) {
            $db->rollBack();
            $db->exec('SET FOREIGN_KEY_CHECKS = 1');
            echo json_encode([
                'success' => false,
                'message' => 'Erreur SQL lors de la restauration : ' . $e->getMessage(),
                'executed_before_error' => $executed,
            ]);
            exit;
        }

        $db->exec('SET FOREIGN_KEY_CHECKS = 1');

        // Audit
        try {
            require_once __DIR__ . '/../services/AuditService.php';
            $userName = trim(($_SESSION['user_prenom'] ?? '') . ' ' . ($_SESSION['user_nom'] ?? 'Admin'));
            (new AuditService())->log(
                $_SESSION['user_id'] ?? 0,
                'RESTAURATION',
                'database',
                0,
                "Restauration SQL par $userName — fichier : {$file['name']} — $executed instructions exécutées"
            );
        } catch (\Throwable $e) {}

        echo json_encode([
            'success'   => true,
            'message'   => "Restauration réussie : $executed instruction(s) exécutée(s).",
            'executed'  => $executed,
            'filename'  => htmlspecialchars($file['name']),
        ]);
        exit;
    }

    /**
     * Découpe un dump SQL en instructions individuelles.
     * Gère : commentaires -- et /* *\/, chaînes 'quoted' et `backtick`.
     */
    private function splitSqlStatements(string $sql): array {
        $statements = [];
        $current    = '';
        $len        = strlen($sql);
        $inString   = false;
        $strChar    = '';
        $inLine     = false;
        $inBlock    = false;

        for ($i = 0; $i < $len; $i++) {
            $c    = $sql[$i];
            $next = $sql[$i + 1] ?? '';

            // Fin de commentaire ligne
            if ($inLine) {
                if ($c === "\n") $inLine = false;
                continue;
            }

            // Fin de commentaire bloc
            if ($inBlock) {
                if ($c === '*' && $next === '/') { $inBlock = false; $i++; }
                continue;
            }

            // Début commentaire ligne (hors chaîne)
            if (!$inString && $c === '-' && $next === '-') {
                $inLine = true; $i++;
                continue;
            }

            // Début commentaire bloc (hors chaîne)
            if (!$inString && $c === '/' && $next === '*') {
                $inBlock = true; $i++;
                continue;
            }

            // Entrée/sortie chaîne
            if (!$inString && ($c === "'" || $c === '"' || $c === '`')) {
                $inString = true;
                $strChar  = $c;
                $current .= $c;
                continue;
            }
            if ($inString) {
                $current .= $c;
                // Échappement backslash
                if ($c === '\\') { $current .= $next; $i++; continue; }
                // Fin de la chaîne (double quote = guillemet doublé dans SQL)
                if ($c === $strChar) {
                    if ($next === $strChar) { $current .= $next; $i++; } // ''
                    else { $inString = false; }
                }
                continue;
            }

            // Délimiteur d'instruction
            if ($c === ';') {
                $stmt = trim($current);
                if ($stmt !== '') $statements[] = $stmt;
                $current = '';
                continue;
            }

            $current .= $c;
        }

        $last = trim($current);
        if ($last !== '') $statements[] = $last;

        return $statements;
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  SAUVEGARDES AUTOMATIQUES
    // ─────────────────────────────────────────────────────────────────────────

    private function getBackupDir(): string {
        $dir = dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'backups';
        if (!is_dir($dir)) {
            mkdir($dir, 0770, true);
            file_put_contents($dir . DIRECTORY_SEPARATOR . '.htaccess', "Deny from all\n");
        }
        return $dir;
    }

    private function initBackupTable(PDO $db): void {
        $db->exec("CREATE TABLE IF NOT EXISTS backup_planification (
            id           INT PRIMARY KEY DEFAULT 1,
            actif        TINYINT(1) NOT NULL DEFAULT 1,
            frequence    ENUM('QUOTIDIEN','HEBDOMADAIRE','MENSUEL') NOT NULL DEFAULT 'QUOTIDIEN',
            heure        TINYINT NOT NULL DEFAULT 2,
            jour_semaine TINYINT NOT NULL DEFAULT 1,
            jour_mois    TINYINT NOT NULL DEFAULT 1,
            retention    INT NOT NULL DEFAULT 7,
            derniere_sauvegarde DATETIME NULL,
            updated_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $db->exec("INSERT IGNORE INTO backup_planification (id) VALUES (1)");
    }

    private function formatSize(int $bytes): string {
        if ($bytes >= 1073741824) return round($bytes / 1073741824, 1) . ' Go';
        if ($bytes >= 1048576)    return round($bytes / 1048576, 1) . ' Mo';
        if ($bytes >= 1024)       return round($bytes / 1024, 1) . ' Ko';
        return $bytes . ' o';
    }

    private function appliquerRetention(string $dir, int $max): void {
        $files = glob($dir . '/dme_backup_*.sql*') ?: [];
        usort($files, fn($a, $b) => filemtime($b) - filemtime($a));
        foreach (array_slice($files, $max) as $old) {
            @unlink($old);
        }
    }

    public function runBackup(bool $auto = false): array {
        $dir  = $this->getBackupDir();
        $date = date('Y-m-d_His');
        $file = $dir . '/dme_backup_' . $date . '.sql';

        $cnf = tempnam(sys_get_temp_dir(), 'dme_cnf_');
        file_put_contents($cnf, "[client]\nhost=" . DB_HOST . "\nuser=" . DB_USER . "\npassword=" . DB_PASS . "\n");
        chmod($cnf, 0600);

        $cmd = sprintf(
            'mysqldump --defaults-extra-file=%s --single-transaction --routines --triggers %s > %s 2>&1',
            escapeshellarg($cnf),
            escapeshellarg(DB_NAME),
            escapeshellarg($file)
        );
        exec($cmd, $output, $code);
        unlink($cnf);

        if ($code !== 0 || !file_exists($file) || filesize($file) < 100) {
            if (file_exists($file)) unlink($file);
            return ['success' => false, 'message' => 'Échec mysqldump : ' . implode(' ', $output)];
        }

        $gzFile = $file . '.gz';
        exec('gzip -f ' . escapeshellarg($file), $gzOut, $gzCode);
        $finalFile = ($gzCode === 0 && file_exists($gzFile)) ? $gzFile : $file;

        try {
            $db = (new Database())->getConnection();
            $this->initBackupTable($db);
            $db->prepare("UPDATE backup_planification SET derniere_sauvegarde=NOW() WHERE id=1")->execute();
            $plan = $db->query("SELECT retention FROM backup_planification WHERE id=1")->fetch(PDO::FETCH_ASSOC);
            $this->appliquerRetention($dir, (int)($plan['retention'] ?? 7));
        } catch (\Throwable $e) {}

        try {
            require_once __DIR__ . '/../services/AuditService.php';
            $who = $auto ? 'Tâche automatique' : trim(($_SESSION['user_prenom'] ?? '') . ' ' . ($_SESSION['user_nom'] ?? 'Admin'));
            (new AuditService())->log(
                $_SESSION['user_id'] ?? 0, 'SAUVEGARDE', 'database', 0,
                "Sauvegarde " . ($auto ? 'automatique' : 'manuelle') . " — " . basename($finalFile) . " — " . $this->formatSize(filesize($finalFile))
            );
        } catch (\Throwable $e) {}

        return [
            'success' => true,
            'message' => 'Sauvegarde créée avec succès',
            'fichier' => basename($finalFile),
            'taille'  => $this->formatSize(filesize($finalFile)),
        ];
    }

    public function getPlanificationSauvegarde(): void {
        ob_start(); header('Content-Type: application/json; charset=utf-8'); ob_clean();
        $this->requireSuperAdmin();
        $db = (new Database())->getConnection();
        $this->initBackupTable($db);
        $plan = $db->query("SELECT * FROM backup_planification WHERE id=1")->fetch(PDO::FETCH_ASSOC);
        $backups = [];
        $dir = $this->getBackupDir();
        foreach (glob($dir . '/dme_backup_*.sql*') ?: [] as $f) {
            $backups[] = ['nom' => basename($f), 'taille' => $this->formatSize(filesize($f)),
                          'date' => date('d/m/Y H:i', filemtime($f)), 'ts' => filemtime($f)];
        }
        usort($backups, fn($a, $b) => $b['ts'] - $a['ts']);
        echo json_encode(['success' => true, 'plan' => $plan, 'backups' => $backups]);
        exit;
    }

    public function sauvegarderPlanification(): void {
        ob_start(); header('Content-Type: application/json; charset=utf-8'); ob_clean();
        $this->requireSuperAdmin();
        $actif     = isset($_POST['actif']) ? (int)(bool)$_POST['actif'] : 0;
        $freq      = in_array($_POST['frequence'] ?? '', ['QUOTIDIEN','HEBDOMADAIRE','MENSUEL']) ? $_POST['frequence'] : 'QUOTIDIEN';
        $heure     = max(0, min(23, (int)($_POST['heure'] ?? 2)));
        $jourSem   = max(1, min(7,  (int)($_POST['jour_semaine'] ?? 1)));
        $jourMois  = max(1, min(28, (int)($_POST['jour_mois'] ?? 1)));
        $retention = max(1, min(30, (int)($_POST['retention'] ?? 7)));
        $db = (new Database())->getConnection();
        $this->initBackupTable($db);
        $db->prepare("UPDATE backup_planification SET actif=?,frequence=?,heure=?,jour_semaine=?,jour_mois=?,retention=? WHERE id=1")
           ->execute([$actif, $freq, $heure, $jourSem, $jourMois, $retention]);
        echo json_encode(['success' => true, 'message' => 'Planification sauvegardée']);
        exit;
    }

    public function executerSauvegarde(): void {
        ob_start(); header('Content-Type: application/json; charset=utf-8'); ob_clean();
        $this->requireSuperAdmin();
        echo json_encode($this->runBackup(false));
        exit;
    }

    public function telechargerSauvegarde(): void {
        $this->requireSuperAdmin();
        $nom = basename($_GET['nom'] ?? '');
        if (!preg_match('/^dme_backup_[\d_]+\.sql(\.gz)?$/', $nom)) { http_response_code(400); exit; }
        $path = $this->getBackupDir() . '/' . $nom;
        if (!file_exists($path)) { http_response_code(404); exit; }
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $nom . '"');
        header('Content-Length: ' . filesize($path));
        readfile($path);
        exit;
    }

    public function supprimerSauvegarde(): void {
        ob_start(); header('Content-Type: application/json; charset=utf-8'); ob_clean();
        $this->requireSuperAdmin();
        $nom = basename($_POST['nom'] ?? '');
        if (!preg_match('/^dme_backup_[\d_]+\.sql(\.gz)?$/', $nom)) {
            echo json_encode(['success' => false, 'message' => 'Nom invalide']); exit;
        }
        $path = $this->getBackupDir() . '/' . $nom;
        if (!file_exists($path)) { echo json_encode(['success' => false, 'message' => 'Fichier introuvable']); exit; }
        unlink($path);
        echo json_encode(['success' => true]);
        exit;
    }

    /* ----------------------------------------------------------------
       AJAX POST — Réorienter un patient
       Body JSON : { patient_id, statut_parcours, service_id, medecin_id }
    ---------------------------------------------------------------- */
    public function reorienterPatient(): void {
        header('Content-Type: application/json');
        $this->requireSuperAdmin();

        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $patient_id     = (int)($input['patient_id']     ?? 0);
        $statut         = strtoupper(trim($input['statut_parcours'] ?? ''));
        $service_id     = !empty($input['service_id'])  ? (int)$input['service_id']  : null;
        $medecin_id     = !empty($input['medecin_id'])  ? (int)$input['medecin_id']  : null;
        $note           = trim($input['note'] ?? '');

        $statutsOk = ['ACCUEIL','PARAMETRES','PARAMETRES_MATERNITE',
                      'ATTENTE_CONSULTATION','EN_CONSULTATION',
                      'URGENCES','HOSPITALISE','ABSENT_24H','SORTI'];

        if (!$patient_id || !in_array($statut, $statutsOk)) {
            echo json_encode(['success' => false, 'message' => 'Données invalides']); exit;
        }

        try {
            $db = (new Database())->getConnection();

            // Vérifier que le patient existe
            $p = $db->prepare("SELECT id, nom, prenom, statut_parcours FROM patients WHERE id = ?");
            $p->execute([$patient_id]);
            $patient = $p->fetch(PDO::FETCH_ASSOC);
            if (!$patient) { echo json_encode(['success' => false, 'message' => 'Patient introuvable']); exit; }

            $db->prepare("
                UPDATE patients
                SET statut_parcours = ?,
                    service_id      = COALESCE(?, service_id),
                    medecin_id      = ?
                WHERE id = ?
            ")->execute([$statut, $service_id, $medecin_id, $patient_id]);

            // Traçabilité dans patient_parcours_logs si la table existe
            try {
                $db->exec("CREATE TABLE IF NOT EXISTS patient_parcours_logs (
                    id           INT AUTO_INCREMENT PRIMARY KEY,
                    patient_id   INT NOT NULL,
                    admin_id     INT DEFAULT NULL,
                    ancien_statut VARCHAR(50),
                    nouveau_statut VARCHAR(50),
                    service_id   INT DEFAULT NULL,
                    medecin_id   INT DEFAULT NULL,
                    note         TEXT,
                    created_at   DATETIME DEFAULT NOW(),
                    INDEX idx_p (patient_id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

                $db->prepare("INSERT INTO patient_parcours_logs
                    (patient_id, admin_id, ancien_statut, nouveau_statut, service_id, medecin_id, note)
                    VALUES (?,?,?,?,?,?,?)")
                ->execute([
                    $patient_id,
                    $_SESSION['user_id'] ?? null,
                    $patient['statut_parcours'],
                    $statut,
                    $service_id,
                    $medecin_id,
                    $note ?: null,
                ]);
            } catch (\Throwable $e) { /* non bloquant */ }

            echo json_encode([
                'success'  => true,
                'message'  => 'Parcours mis à jour',
                'patient'  => $patient['nom'] . ' ' . $patient['prenom'],
                'nouveau'  => $statut,
            ]);
        } catch (\Throwable $e) {
            error_log('[reorienterPatient] ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }
}