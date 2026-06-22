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
   FICHIER : app/controllers/NeonatologyController.php
   Module NÉONATOLOGIE — Tableau de bord (unité néonatale) + examen néonatal
   Accès : service Néonatologie, service Pédiatrie (par défaut) et ADMIN
   ============================================================================ */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../models/Patient.php';
require_once __DIR__ . '/../services/AuditService.php';

class NeonatologyController {

    private $db;
    private $audit;

    public function __construct() {
        $this->db    = (new Database())->getConnection();
        $this->audit = new AuditService();

        if (empty($_SESSION['user_id'])) {
            header('Location: ' . BASE_URL . 'login');
            exit;
        }
        $this->ensureSchema();
    }

    /* ── Accès : Néonat OU Pédiatrie OU Admin OU Médecin de garde ──────────── */
    private function canAccess(): bool {
        if (defined('GARDE_ACTIVE') && GARDE_ACTIVE) return true;
        $nom  = strtolower($_SESSION['nom_service'] ?? '');
        $role = strtoupper($_SESSION['user_role'] ?? $_SESSION['role'] ?? '');
        if (in_array($role, ['ADMIN', 'ADMINISTRATEUR'])) return true;
        return stripos($nom, 'onat') !== false
            || stripos($nom, 'pédiatr') !== false
            || stripos($nom, 'pediatr') !== false
            || stripos($nom, 'ped') !== false
            || stripos($nom, 'neo') !== false;
    }

    private function guard() {
        if (!$this->canAccess()) {
            http_response_code(403);
            if (file_exists(__DIR__ . '/../views/errors/403.php')) {
                require_once __DIR__ . '/../views/errors/403.php';
            } else {
                echo "<h1>403 — Accès réservé au service Néonatologie / Pédiatrie.</h1>";
            }
            exit;
        }
    }

    /** Migration auto : crée la table si absente (idempotent). */
    private function ensureSchema() {
        try {
            $exists = (int)$this->db->query(
                "SELECT COUNT(*) FROM information_schema.TABLES
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'consultations_neonatales'"
            )->fetchColumn();
            if (!$exists) {
                $this->db->exec("
                    CREATE TABLE consultations_neonatales (
                      id INT AUTO_INCREMENT PRIMARY KEY, patient_id INT NOT NULL, medecin_id INT NULL,
                      date_consultation DATETIME DEFAULT CURRENT_TIMESTAMP,
                      age_gestationnel_sa DECIMAL(4,1) NULL, terme VARCHAR(20) NULL, mode_accouchement VARCHAR(20) NULL,
                      poids_naissance INT NULL, taille_naissance DECIMAL(4,1) NULL, perimetre_cranien DECIMAL(4,1) NULL,
                      apgar_1 INT NULL, apgar_5 INT NULL, apgar_10 INT NULL,
                      poids_actuel INT NULL, temperature DECIMAL(3,1) NULL, freq_cardiaque INT NULL,
                      freq_respiratoire INT NULL, spo2 INT NULL, glycemie DECIMAL(4,2) NULL,
                      diurese DECIMAL(4,2) NULL, etat_conscience VARCHAR(30) NULL,
                      alimentation VARCHAR(20) NULL, tolerance_alimentaire VARCHAR(255) NULL,
                      ictere TINYINT(1) DEFAULT 0, ictere_kramer INT NULL,
                      reflexe_succion TINYINT(1) DEFAULT 0, reflexe_moro TINYINT(1) DEFAULT 0, reflexe_grasping TINYINT(1) DEFAULT 0,
                      fontanelle VARCHAR(20) NULL,
                      examen_cardio TEXT, examen_respiratoire TEXT, examen_abdomen TEXT, examen_neuro TEXT, examen_peau_ombilic TEXT,
                      diagnostic TEXT, conduite_tenir TEXT, traitement TEXT, observations TEXT,
                      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                      KEY idx_patient (patient_id, id)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
                ");
            }
            // Table de suivi des gavages (alimentation entérale)
            $gavExists = (int)$this->db->query(
                "SELECT COUNT(*) FROM information_schema.TABLES
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'neonat_gavage'"
            )->fetchColumn();
            if (!$gavExists) {
                $this->db->exec("
                    CREATE TABLE neonat_gavage (
                      id INT AUTO_INCREMENT PRIMARY KEY,
                      patient_id INT NOT NULL,
                      user_id INT NULL,
                      date_gavage DATE NOT NULL,
                      heure_gavage TIME NOT NULL,
                      poids_jour DECIMAL(5,3) NULL,
                      residus VARCHAR(255) NULL,
                      lait_maternel_ml DECIMAL(6,1) NULL,
                      lait_artificiel_ml DECIMAL(6,1) NULL,
                      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                      KEY idx_patient_date (patient_id, date_gavage, heure_gavage)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
                ");
            }

            // Table des consultations post-natales (check-up de sortie de maternité)
            $pnExists = (int)$this->db->query(
                "SELECT COUNT(*) FROM information_schema.TABLES
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'neonat_post_natale'"
            )->fetchColumn();
            if (!$pnExists) {
                $this->db->exec("
                    CREATE TABLE neonat_post_natale (
                      id INT AUTO_INCREMENT PRIMARY KEY,
                      patient_id INT NOT NULL,
                      medecin_id INT NULL,
                      date_examen DATE NULL,
                      decision VARCHAR(20) NULL,
                      motif_neonatologie TEXT NULL,
                      poids_sortie INT NULL,
                      conclusion TEXT NULL,
                      date_rdv1 DATE NULL,
                      data LONGTEXT NULL,
                      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                      KEY idx_patient (patient_id, id)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
                ");
            }

            // Table des observations médicales du nouveau-né (formulaire complet 15 sections)
            $obsExists = (int)$this->db->query(
                "SELECT COUNT(*) FROM information_schema.TABLES
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'neonat_observations'"
            )->fetchColumn();
            if (!$obsExists) {
                $this->db->exec("
                    CREATE TABLE neonat_observations (
                      id INT AUTO_INCREMENT PRIMARY KEY,
                      patient_id INT NOT NULL,
                      medecin_id INT NULL,
                      date_observation DATETIME DEFAULT CURRENT_TIMESTAMP,
                      motif TEXT NULL,
                      diagnostic_principal TEXT NULL,
                      data LONGTEXT NULL,
                      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                      KEY idx_patient (patient_id, id)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
                ");
            }

            // Table fiche de soins infirmiers néonatologie
            $soinsExists = (int)$this->db->query(
                "SELECT COUNT(*) FROM information_schema.TABLES
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'neonat_soins_infirmiers'"
            )->fetchColumn();
            if (!$soinsExists) {
                $this->db->exec("
                    CREATE TABLE neonat_soins_infirmiers (
                      id INT AUTO_INCREMENT PRIMARY KEY,
                      patient_id INT NOT NULL,
                      user_id INT NULL,
                      date_soin DATE NOT NULL,
                      heure_soin TIME NOT NULL,
                      plaintes TEXT NULL,
                      besoins_specifiques TEXT NULL,
                      interventions TEXT NULL,
                      emargement VARCHAR(100) NULL,
                      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                      KEY idx_patient_date (patient_id, date_soin, heure_soin)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
                ");
            }

            // Table paramètres vitaux (température, poids, saturation, glycémie capillaire)
            $pvExists = (int)$this->db->query(
                "SELECT COUNT(*) FROM information_schema.TABLES
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'neonat_parametres'"
            )->fetchColumn();
            if (!$pvExists) {
                $this->db->exec("
                    CREATE TABLE neonat_parametres (
                      id INT AUTO_INCREMENT PRIMARY KEY,
                      patient_id INT NOT NULL,
                      user_id INT NULL,
                      date_mesure DATE NOT NULL,
                      heure_mesure TIME NOT NULL,
                      temperature DECIMAL(4,1) NULL,
                      poids INT NULL,
                      saturation DECIMAL(4,1) NULL,
                      glycemie DECIMAL(5,2) NULL,
                      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                      KEY idx_patient (patient_id, date_mesure, heure_mesure)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
                ");
            }

            // Table fiche transfusionnelle
            $trfExists = (int)$this->db->query(
                "SELECT COUNT(*) FROM information_schema.TABLES
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'neonat_transfusions'"
            )->fetchColumn();
            if (!$trfExists) {
                $this->db->exec("
                    CREATE TABLE neonat_transfusions (
                      id INT AUTO_INCREMENT PRIMARY KEY,
                      patient_id INT NOT NULL,
                      user_id INT NULL,
                      medecin_id INT NULL,
                      date_transfusion DATETIME NOT NULL,
                      groupe_patient VARCHAR(5) NULL,
                      rhesus_patient VARCHAR(3) NULL,
                      produit_sanguin VARCHAR(30) NULL,
                      volume_ml INT NULL,
                      numero_poche VARCHAR(60) NULL,
                      groupe_donneur VARCHAR(5) NULL,
                      rhesus_donneur VARCHAR(3) NULL,
                      indication TEXT NULL,
                      pre_temp DECIMAL(4,1) NULL, pre_fc INT NULL, pre_fr INT NULL, pre_spo2 INT NULL,
                      post_temp DECIMAL(4,1) NULL, post_fc INT NULL, post_fr INT NULL, post_spo2 INT NULL,
                      reactions TEXT NULL,
                      emargement VARCHAR(100) NULL,
                      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                      KEY idx_patient (patient_id, date_transfusion)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
                ");
            }

            // Table planification des soins infirmiers
            $planExists = (int)$this->db->query(
                "SELECT COUNT(*) FROM information_schema.TABLES
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'neonat_planification_soins'"
            )->fetchColumn();
            if (!$planExists) {
                $this->db->exec("
                    CREATE TABLE neonat_planification_soins (
                      id INT AUTO_INCREMENT PRIMARY KEY,
                      patient_id INT NOT NULL,
                      user_id INT NULL,
                      date_planification DATE NOT NULL,
                      priorite ENUM('HAUTE','MOYENNE','BASSE') DEFAULT 'MOYENNE',
                      diagnostic_infirmier TEXT NULL,
                      objectif TEXT NULL,
                      interventions TEXT NULL,
                      evaluation TEXT NULL,
                      echeance DATE NULL,
                      statut ENUM('EN_COURS','ATTEINT','NON_ATTEINT','REPORTE') DEFAULT 'EN_COURS',
                      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                      KEY idx_patient (patient_id, date_planification)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
                ");
            }

            // Colonnes ajoutées après création initiale (diurèse + état de conscience)
            $cols = $this->db->query("SHOW COLUMNS FROM consultations_neonatales")->fetchAll(PDO::FETCH_COLUMN);
            if (!in_array('diurese', $cols)) {
                $this->db->exec("ALTER TABLE consultations_neonatales ADD COLUMN diurese DECIMAL(4,2) NULL AFTER glycemie");
            }
            if (!in_array('etat_conscience', $cols)) {
                $this->db->exec("ALTER TABLE consultations_neonatales ADD COLUMN etat_conscience VARCHAR(30) NULL AFTER diurese");
            }
        } catch (Exception $e) { error_log('[Neonat] ensureSchema: ' . $e->getMessage()); }

        // ── Plan d'occupation néonatologie : service + salles + berceaux/couveuses ──
        if (empty($_SESSION['_migration_neonat_plan_ok'])) {
            try { $this->seedPlanOccupation(); }
            catch (\Throwable $e) { error_log('[Neonat] seedPlan: ' . $e->getMessage()); }
            $_SESSION['_migration_neonat_plan_ok'] = true;
        }
    }

    /**
     * Crée (si absents) le service Néonatologie et son plan d'occupation :
     *   Salle des berceaux A (10 berceaux) · Salle des berceaux B (4 berceaux)
     *   Salle Urgence Néonatologie (1 berceau) · Salle des couveuses (8 couveuses A–H)
     * Idempotent : ne touche à rien si les salles existent déjà.
     */
    private function seedPlanOccupation(): void {
        // 1. Service Néonatologie
        $svcId = $this->neonatServiceId();
        if (!$svcId) {
            $this->db->prepare("INSERT INTO services (nom_service, categorie) VALUES ('Néonatologie', 'CLINIQUE')")->execute();
            $svcId = (int)$this->db->lastInsertId();
        }
        if (!$svcId) return;

        // 2. Salles + lits (berceaux / couveuses)
        $plan = [
            ['Salle des berceaux A',       'COMMUNE',      array_map(fn($n) => "Berceau A$n", range(1, 10))],
            ['Salle des berceaux B',       'COMMUNE',      array_map(fn($n) => "Berceau B$n", range(1, 4))],
            ['Salle Urgence Néonatologie', 'INDIVIDUELLE', ['Berceau URG']],
            ['Salle des couveuses',        'COMMUNE',      array_map(fn($l) => "Couveuse $l", range('A', 'H'))],
        ];

        $chkChambre = $this->db->prepare("SELECT id FROM chambres WHERE service_id = ? AND nom_chambre = ? LIMIT 1");
        $insChambre = $this->db->prepare("INSERT INTO chambres (service_id, nom_chambre, type_chambre) VALUES (?,?,?)");
        $chkLit     = $this->db->prepare("SELECT COUNT(*) FROM lits WHERE chambre_id = ? AND nom_lit = ?");
        $insLit     = $this->db->prepare("INSERT INTO lits (chambre_id, service_id, nom_lit, statut) VALUES (?,?,?,'DISPONIBLE')");

        foreach ($plan as [$nomChambre, $type, $lits]) {
            $chkChambre->execute([$svcId, $nomChambre]);
            $chambreId = (int)$chkChambre->fetchColumn();
            if (!$chambreId) {
                $insChambre->execute([$svcId, $nomChambre, $type]);
                $chambreId = (int)$this->db->lastInsertId();
            }
            foreach ($lits as $nomLit) {
                $chkLit->execute([$chambreId, $nomLit]);
                if (!(int)$chkLit->fetchColumn()) {
                    $insLit->execute([$chambreId, $svcId, $nomLit]);
                }
            }
        }
    }

    /* ── Normes néonatales (utilisées par les vues et les alertes) ──────────── */
    public const NORMES = [
        'temperature'       => ['min' => 36.5, 'max' => 37.5, 'unite' => '°C',        'label' => 'Température'],
        'freq_respiratoire' => ['min' => 30,   'max' => 80,   'unite' => '/min',      'label' => 'Fréq. respiratoire'],
        'freq_cardiaque'    => ['min' => 120,  'max' => 180,  'unite' => 'bpm',       'label' => 'Fréq. cardiaque'],
        'spo2'              => ['min' => 92,   'max' => null, 'unite' => '%',         'label' => 'Saturation'],
        'diurese'           => ['min' => 0.5,  'max' => 1,    'unite' => 'cc/kg/h',   'label' => 'Diurèse'],
    ];

    /** true si la valeur est hors norme néonatale */
    public static function horsNorme(string $param, $val): bool {
        if ($val === null || $val === '' || !isset(self::NORMES[$param])) return false;
        $n = self::NORMES[$param];
        if ($n['min'] !== null && $val < $n['min']) return true;
        if ($n['max'] !== null && $val > $n['max']) return true;
        return false;
    }

    /** ID du service Néonatologie (résolu par nom). */
    private function neonatServiceId() {
        try {
            $stmt = $this->db->query("SELECT id FROM services WHERE nom_service LIKE '%onat%' ORDER BY id LIMIT 1");
            return (int)($stmt->fetchColumn() ?: 0);
        } catch (Exception $e) { return 0; }
    }

    /* ── Âge en jours/heures (lisible) ──────────────────────────────────────── */
    public static function ageNeonat($dateNaissance): string {
        if (empty($dateNaissance)) return '—';
        try {
            $n = new DateTime($dateNaissance);
            $now = new DateTime();
            $diff = $n->diff($now);
            $jours = (int)$diff->days;
            if ($jours <= 0) return $diff->h . ' h';
            if ($jours < 28) return $jours . ' j';
            if ($jours < 365) return $diff->m . ' mois ' . $diff->d . ' j';
            return $diff->y . ' an(s)';
        } catch (Exception $e) { return '—'; }
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  TABLEAU DE BORD — Unité néonatale
    // ─────────────────────────────────────────────────────────────────────────
    public function dashboard() {
        $this->guard();
        $neonatId = $this->neonatServiceId();

        // 1. Nouveau-nés hospitalisés dans le service néonatologie
        $neonates = [];
        try {
            $stmt = $this->db->prepare("
                SELECT h.id AS hosp_id, h.date_admission,
                       p.id, p.nom, p.prenom, p.dossier_numero, p.date_naissance, p.sexe,
                       l.nom_lit, c.nom_chambre,
                       (SELECT cn.poids_actuel FROM consultations_neonatales cn WHERE cn.patient_id = p.id ORDER BY cn.id DESC LIMIT 1) AS dernier_poids,
                       (SELECT cn.temperature  FROM consultations_neonatales cn WHERE cn.patient_id = p.id ORDER BY cn.id DESC LIMIT 1) AS derniere_temp,
                       (SELECT cn.spo2         FROM consultations_neonatales cn WHERE cn.patient_id = p.id ORDER BY cn.id DESC LIMIT 1) AS dernier_spo2,
                       (SELECT cn.freq_cardiaque FROM consultations_neonatales cn WHERE cn.patient_id = p.id ORDER BY cn.id DESC LIMIT 1) AS derniere_fc,
                       (SELECT cn.freq_respiratoire FROM consultations_neonatales cn WHERE cn.patient_id = p.id ORDER BY cn.id DESC LIMIT 1) AS derniere_fr,
                       (SELECT cn.diurese      FROM consultations_neonatales cn WHERE cn.patient_id = p.id ORDER BY cn.id DESC LIMIT 1) AS derniere_diurese,
                       (SELECT cn.etat_conscience FROM consultations_neonatales cn WHERE cn.patient_id = p.id ORDER BY cn.id DESC LIMIT 1) AS dernier_etat,
                       (SELECT MAX(cn.date_consultation) FROM consultations_neonatales cn WHERE cn.patient_id = p.id) AS dernier_exam
                FROM hospitalisations h
                JOIN patients p ON p.id = h.patient_id
                LEFT JOIN lits l     ON l.id = h.lit_id
                LEFT JOIN chambres c ON c.id = l.chambre_id
                WHERE h.service_id = ? AND h.statut = 'en_cours'
                ORDER BY h.date_admission DESC
            ");
            $stmt->execute([$neonatId]);
            $neonates = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) { error_log('[Neonat] board: ' . $e->getMessage()); }

        // 2. Examens néonatals récents (30 derniers jours)
        $examensRecents = [];
        try {
            $stmt = $this->db->query("
                SELECT cn.id, cn.date_consultation, cn.poids_actuel, cn.diagnostic,
                       p.nom, p.prenom, p.dossier_numero, p.date_naissance,
                       u.nom AS medecin_nom, u.prenom AS medecin_prenom
                FROM consultations_neonatales cn
                JOIN patients p ON p.id = cn.patient_id
                LEFT JOIN users u ON u.id = cn.medecin_id
                ORDER BY cn.id DESC LIMIT 15
            ");
            $examensRecents = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) { error_log('[Neonat] recents: ' . $e->getMessage()); }

        // 3. KPIs
        $kpi = ['hospitalises' => count($neonates), 'examens_jour' => 0, 'examens_total' => 0, 'alertes' => 0];
        try {
            $kpi['examens_jour']  = (int)$this->db->query("SELECT COUNT(*) FROM consultations_neonatales WHERE DATE(date_consultation)=CURDATE()")->fetchColumn();
            $kpi['examens_total'] = (int)$this->db->query("SELECT COUNT(*) FROM consultations_neonatales")->fetchColumn();
        } catch (Exception $e) {}
        // Alertes selon les normes néonatales :
        // T° 36.5–37.5 · FR 30–80 · FC 120–180 · SpO2 > 92 · Diurèse 0.5–1 cc/kg/h
        foreach ($neonates as $n) {
            if (self::horsNorme('temperature',       $n['derniere_temp'])
             || self::horsNorme('spo2',              $n['dernier_spo2'])
             || self::horsNorme('freq_cardiaque',    $n['derniere_fc'])
             || self::horsNorme('freq_respiratoire', $n['derniere_fr'])
             || self::horsNorme('diurese',           $n['derniere_diurese'])) {
                $kpi['alertes']++;
            }
        }

        // 4. Plan d'occupation : salles + berceaux/couveuses avec occupant(s) éventuel(s)
        // Les couveuses peuvent avoir plusieurs bébés (max 3) — on joint via hospitalisations
        $plan_occupation = [];
        try {
            $stmtPlan = $this->db->prepare("
                SELECT c.id AS chambre_id, c.nom_chambre, c.type_chambre,
                       l.id AS lit_id, l.nom_lit, l.statut,
                       p.id AS patient_id, p.nom AS occ_nom, p.prenom AS occ_prenom,
                       p.dossier_numero AS occ_dossier, h.id AS hosp_id
                FROM chambres c
                JOIN lits l ON l.chambre_id = c.id
                LEFT JOIN hospitalisations h ON h.lit_id = l.id AND h.statut = 'en_cours'
                LEFT JOIN patients p ON p.id = h.patient_id
                WHERE c.service_id = ?
                ORDER BY FIELD(c.nom_chambre,
                          'Salle Urgence Néonatologie', 'Salle des couveuses',
                          'Salle des berceaux A', 'Salle des berceaux B'),
                         c.nom_chambre, LENGTH(l.nom_lit), l.nom_lit
            ");
            $stmtPlan->execute([$neonatId]);
            $litMap = []; // lit_id => ['salle'=>, 'idx'=>]
            foreach ($stmtPlan->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $salle = $row['nom_chambre'];
                $litId = $row['lit_id'];
                if (!isset($litMap[$litId])) {
                    if (!isset($plan_occupation[$salle])) $plan_occupation[$salle] = [];
                    $plan_occupation[$salle][] = [
                        'lit_id'     => $litId,
                        'nom_lit'    => $row['nom_lit'],
                        'statut'     => $row['statut'],
                        'patient_id' => $row['patient_id'],
                        'occ_nom'    => $row['occ_nom'],
                        'occ_prenom' => $row['occ_prenom'],
                        'hosp_id'    => $row['hosp_id'],
                        'occupants'  => $row['patient_id'] ? [[
                            'patient_id' => $row['patient_id'],
                            'hosp_id'    => $row['hosp_id'],
                            'occ_nom'    => $row['occ_nom'],
                            'occ_prenom' => $row['occ_prenom'],
                        ]] : [],
                    ];
                    $litMap[$litId] = ['salle' => $salle, 'idx' => count($plan_occupation[$salle]) - 1];
                } elseif ($row['patient_id']) {
                    // Bébé supplémentaire dans la même couveuse
                    $s = $litMap[$litId]['salle'];
                    $i = $litMap[$litId]['idx'];
                    $plan_occupation[$s][$i]['occupants'][] = [
                        'patient_id' => $row['patient_id'],
                        'hosp_id'    => $row['hosp_id'],
                        'occ_nom'    => $row['occ_nom'],
                        'occ_prenom' => $row['occ_prenom'],
                    ];
                }
            }
        } catch (Exception $e) { error_log('[Neonat] plan: ' . $e->getMessage()); }

        // 5. Services cliniques + médecins (pour modal Créer & Hospitaliser)
        $servicesCliniques = [];
        $medecins = [];
        try {
            $servicesCliniques = $this->db->query("
                SELECT id, nom_service FROM services
                WHERE categorie = 'CLINIQUE'
                ORDER BY nom_service
            ")->fetchAll(PDO::FETCH_ASSOC);
            $medecins = $this->db->query("
                SELECT id, nom, prenom FROM users
                WHERE role IN ('MEDECIN','MEDECIN_CHEF','MEDECIN_URGENTISTE','GENERALISTE',
                               'GYNECO','CHIRURGIEN','PEDIATRE','SPECIALISTE','SAGE_FEMME')
                  AND actif = 1
                ORDER BY nom, prenom
            ")->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) { error_log('[Neonat] modal: ' . $e->getMessage()); }

        require_once __DIR__ . '/../views/neonatologie/dashboard.php';
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  LIBÉRER UN BERCEAU / COUVEUSE  (POST JSON)
    // ─────────────────────────────────────────────────────────────────────────
    public function liberer(): void {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['error' => 'POST uniquement']); return;
        }
        require_once __DIR__ . '/../services/CsrfService.php';
        if (!CsrfService::validate()) { echo json_encode(['error' => 'CSRF invalide']); return; }

        $hospId = (int)($_POST['hosp_id'] ?? 0);
        if (!$hospId) { echo json_encode(['error' => 'ID invalide']); return; }

        try {
            $stmt = $this->db->prepare("SELECT * FROM hospitalisations WHERE id=? AND statut='en_cours'");
            $stmt->execute([$hospId]);
            $hosp = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$hosp) { echo json_encode(['error' => 'Hospitalisation introuvable ou déjà clôturée']); return; }

            $litId     = (int)($hosp['lit_id']     ?? 0);
            $patientId = (int)($hosp['patient_id'] ?? 0);

            // 1. Clôturer l'hospitalisation
            $this->db->prepare("UPDATE hospitalisations SET statut='sorti', date_sortie=NOW() WHERE id=?")
                     ->execute([$hospId]);

            // 2. Mettre à jour le statut patient
            $this->db->prepare("UPDATE patients SET statut='SORTI' WHERE id=?")
                     ->execute([$patientId]);

            // 3. Libérer ou ajuster le lit
            if ($litId) {
                $stCount = $this->db->prepare("SELECT COUNT(*) FROM hospitalisations WHERE lit_id=? AND statut='en_cours'");
                $stCount->execute([$litId]);
                $remaining = (int)$stCount->fetchColumn();
                if ($remaining === 0) {
                    $this->db->prepare("UPDATE lits SET statut='DISPONIBLE', occupied_by_patient_id=NULL WHERE id=?")
                             ->execute([$litId]);
                } else {
                    // Couveuse encore partiellement occupée
                    $stNext = $this->db->prepare("SELECT patient_id FROM hospitalisations WHERE lit_id=? AND statut='en_cours' LIMIT 1");
                    $stNext->execute([$litId]);
                    $nextPid   = $stNext->fetchColumn();
                    $newStatut = ($remaining >= 3) ? 'OCCUPE' : 'DISPONIBLE';
                    $this->db->prepare("UPDATE lits SET statut=?, occupied_by_patient_id=? WHERE id=?")
                             ->execute([$newStatut, $nextPid ?: null, $litId]);
                }
            }

            echo json_encode(['ok' => true]);
        } catch (Exception $e) {
            error_log('[Neonat] liberer: ' . $e->getMessage());
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  TRANSFÉRER UN BÉBÉ VERS UN AUTRE LIT  (POST JSON)
    // ─────────────────────────────────────────────────────────────────────────
    public function transferer(): void {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['error' => 'POST uniquement']); return; }
        require_once __DIR__ . '/../services/CsrfService.php';
        if (!CsrfService::validate()) { echo json_encode(['error' => 'CSRF invalide']); return; }

        $hospId   = (int)($_POST['hosp_id']       ?? 0);
        $newLitId = (int)($_POST['nouveau_lit_id'] ?? 0) ?: null;
        if (!$hospId) { echo json_encode(['error' => 'ID hospitalisation invalide']); return; }

        try {
            $stmt = $this->db->prepare("SELECT * FROM hospitalisations WHERE id=? AND statut='en_cours'");
            $stmt->execute([$hospId]);
            $hosp = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$hosp) { echo json_encode(['error' => 'Hospitalisation introuvable']); return; }

            $oldLitId  = (int)($hosp['lit_id'] ?? 0) ?: null;
            $patientId = (int)$hosp['patient_id'];

            // 1. Mettre à jour l'hospitalisation
            $this->db->prepare("UPDATE hospitalisations SET lit_id=? WHERE id=?")
                     ->execute([$newLitId, $hospId]);

            // 2. Libérer l'ancien lit
            if ($oldLitId) {
                $stCount = $this->db->prepare("SELECT COUNT(*) FROM hospitalisations WHERE lit_id=? AND statut='en_cours'");
                $stCount->execute([$oldLitId]);
                $remaining = (int)$stCount->fetchColumn();
                if ($remaining === 0) {
                    $this->db->prepare("UPDATE lits SET statut='DISPONIBLE', occupied_by_patient_id=NULL WHERE id=?")
                             ->execute([$oldLitId]);
                } else {
                    $stNext = $this->db->prepare("SELECT patient_id FROM hospitalisations WHERE lit_id=? AND statut='en_cours' LIMIT 1");
                    $stNext->execute([$oldLitId]);
                    $nextPid   = $stNext->fetchColumn();
                    $newStatut = ($remaining >= 3) ? 'OCCUPE' : 'DISPONIBLE';
                    $this->db->prepare("UPDATE lits SET statut=?, occupied_by_patient_id=? WHERE id=?")
                             ->execute([$newStatut, $nextPid ?: null, $oldLitId]);
                }
            }

            // 3. Occuper le nouveau lit
            if ($newLitId) {
                $stCount2 = $this->db->prepare("SELECT COUNT(*) FROM hospitalisations WHERE lit_id=? AND statut='en_cours'");
                $stCount2->execute([$newLitId]);
                $nbOcc     = (int)$stCount2->fetchColumn();
                $newStatut2 = ($nbOcc >= 3) ? 'OCCUPE' : 'DISPONIBLE';
                $this->db->prepare("UPDATE lits SET statut=?, occupied_by_patient_id=? WHERE id=?")
                         ->execute([$newStatut2, $patientId, $newLitId]);
            }

            echo json_encode(['ok' => true]);
        } catch (Exception $e) {
            error_log('[Neonat] transferer: ' . $e->getMessage());
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  FORMULAIRE D'EXAMEN NÉONATAL
    // ─────────────────────────────────────────────────────────────────────────
    public function examen($patient_id) {
        $this->guard();
        $patientModel = new Patient();
        $patient = $patientModel->getById((int)$patient_id);
        if (!$patient) { die("Patient introuvable."); }

        $age = self::ageNeonat($patient['date_naissance'] ?? null);

        // Dernier examen pour pré-remplir les données de naissance
        $dernier = null;
        try {
            $stmt = $this->db->prepare("SELECT * FROM consultations_neonatales WHERE patient_id = ? ORDER BY id DESC LIMIT 1");
            $stmt->execute([(int)$patient_id]);
            $dernier = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (Exception $e) {}

        require_once __DIR__ . '/../views/neonatologie/examen.php';
    }

    public function sauvegarder() {
        $this->guard();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . 'neonatologie'); exit;
        }
        $patient_id = (int)($_POST['patient_id'] ?? 0);
        if (!$patient_id) { header('Location: ' . BASE_URL . 'neonatologie?error=patient'); exit; }

        $f = fn($k) => (isset($_POST[$k]) && $_POST[$k] !== '') ? $_POST[$k] : null;
        $cb = fn($k) => !empty($_POST[$k]) ? 1 : 0;

        try {
            $sql = "INSERT INTO consultations_neonatales
                (patient_id, medecin_id, age_gestationnel_sa, terme, mode_accouchement,
                 poids_naissance, taille_naissance, perimetre_cranien, apgar_1, apgar_5, apgar_10,
                 poids_actuel, temperature, freq_cardiaque, freq_respiratoire, spo2, glycemie,
                 diurese, etat_conscience,
                 alimentation, tolerance_alimentaire, ictere, ictere_kramer,
                 reflexe_succion, reflexe_moro, reflexe_grasping, fontanelle,
                 examen_cardio, examen_respiratoire, examen_abdomen, examen_neuro, examen_peau_ombilic,
                 diagnostic, conduite_tenir, traitement, observations)
                VALUES (?,?,?,?,?, ?,?,?,?,?,?, ?,?,?,?,?,?, ?,?, ?,?,?,?, ?,?,?,?, ?,?,?,?,?, ?,?,?,?)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $patient_id, $_SESSION['user_id'] ?? null,
                $f('age_gestationnel_sa'), $f('terme'), $f('mode_accouchement'),
                $f('poids_naissance'), $f('taille_naissance'), $f('perimetre_cranien'),
                $f('apgar_1'), $f('apgar_5'), $f('apgar_10'),
                $f('poids_actuel'), $f('temperature'), $f('freq_cardiaque'), $f('freq_respiratoire'), $f('spo2'), $f('glycemie'),
                $f('diurese'), $f('etat_conscience'),
                $f('alimentation'), $f('tolerance_alimentaire'), $cb('ictere'), $f('ictere_kramer'),
                $cb('reflexe_succion'), $cb('reflexe_moro'), $cb('reflexe_grasping'), $f('fontanelle'),
                $f('examen_cardio'), $f('examen_respiratoire'), $f('examen_abdomen'), $f('examen_neuro'), $f('examen_peau_ombilic'),
                $f('diagnostic'), $f('conduite_tenir'), $f('traitement'), $f('observations'),
            ]);
            $id = (int)$this->db->lastInsertId();

            // Audit
            try {
                $p = (new Patient())->getById($patient_id);
                $pNom = $p ? trim(($p['nom'] ?? '') . ' ' . ($p['prenom'] ?? '')) : '';
                $this->audit->log('CREATE', 'consultations_neonatales', $id,
                    'Examen néonatal enregistré' . ($pNom ? " — nouveau-né : $pNom (#$patient_id)" : '')
                    . ($f('diagnostic') ? ' · Dx : ' . mb_substr($f('diagnostic'), 0, 100) : ''),
                    null, ['patient' => $pNom, 'poids_actuel' => $f('poids_actuel'), 'diagnostic' => $f('diagnostic')]);
            } catch (Exception $e) {}

            header('Location: ' . BASE_URL . 'neonatologie/voir/' . $id);
            exit;
        } catch (Exception $e) {
            error_log('[Neonat] sauvegarder: ' . $e->getMessage());
            header('Location: ' . BASE_URL . 'neonatologie/examen/' . $patient_id . '?error=' . urlencode($e->getMessage()));
            exit;
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  CONSULTATION POST-NATALE — check-up de sortie de maternité
    // ─────────────────────────────────────────────────────────────────────────
    public function postNatale($patient_id) {
        $this->guard();
        $patientModel = new Patient();
        $patient = $patientModel->getById((int)$patient_id);
        if (!$patient) { die("Patient introuvable."); }
        $age = self::ageNeonat($patient['date_naissance'] ?? null);

        // Dernière fiche éventuelle (pour consultation / réimpression)
        $derniere_fiche = null;
        try {
            $stmt = $this->db->prepare("SELECT * FROM neonat_post_natale WHERE patient_id = ? ORDER BY id DESC LIMIT 1");
            $stmt->execute([(int)$patient_id]);
            $derniere_fiche = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (Exception $e) {}

        require_once __DIR__ . '/../views/neonatologie/post_natale.php';
    }

    public function sauvegarderPostNatale() {
        $this->guard();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . 'neonatologie'); exit;
        }
        $patient_id = (int)($_POST['patient_id'] ?? 0);
        if (!$patient_id) { header('Location: ' . BASE_URL . 'neonatologie?error=patient'); exit; }

        $f = fn($k) => (isset($_POST[$k]) && $_POST[$k] !== '') ? $_POST[$k] : null;
        $decision = in_array($_POST['decision'] ?? '', ['SORTIE', 'NEONATOLOGIE']) ? $_POST['decision'] : null;

        // Tout le contenu du formulaire (hors champs techniques) → JSON
        $data = $_POST;
        unset($data['patient_id'], $data['csrf_token'], $data['_token']);

        try {
            $this->db->prepare("
                INSERT INTO neonat_post_natale
                    (patient_id, medecin_id, date_examen, decision, motif_neonatologie,
                     poids_sortie, conclusion, date_rdv1, data)
                VALUES (?,?,?,?,?,?,?,?,?)
            ")->execute([
                $patient_id,
                $_SESSION['user_id'] ?? null,
                $f('date_examen') ?: date('Y-m-d'),
                $decision,
                $f('motif_neonatologie'),
                $f('poids_sortie'),
                $f('conclusion'),
                $f('date_rdv1'),
                json_encode($data, JSON_UNESCAPED_UNICODE),
            ]);
            $id = (int)$this->db->lastInsertId();

            $p    = (new Patient())->getById($patient_id);
            $pNom = $p ? trim(strtoupper($p['nom'] ?? '') . ' ' . ($p['prenom'] ?? '')) : '';

            // ── Application de la décision du pédiatre ──────────────────────
            if ($decision === 'SORTIE') {
                // Retour à domicile avec la mère
                $this->db->prepare(
                    "UPDATE patients SET statut_parcours = 'SORTI', statut_hosp = 'AUCUN' WHERE id = ?"
                )->execute([$patient_id]);

            } elseif ($decision === 'NEONATOLOGIE') {
                // Affectation au service néonatologie → liste "À Hospitaliser"
                $neonatId = $this->neonatServiceId();
                if ($neonatId) {
                    $this->db->prepare(
                        "UPDATE patients SET service_id = ?, statut_hosp = 'A_HOSPITALISER',
                             statut_parcours = 'HOSPITALISE' WHERE id = ?"
                    )->execute([$neonatId, $patient_id]);

                    // Notifier les infirmiers du service néonatologie
                    try {
                        require_once __DIR__ . '/../services/NotificationCenter.php';
                        (new NotificationCenter($this->db))->notifyByService($neonatId, [
                            'title'    => '👶 Nouveau-né affecté en Néonatologie',
                            'message'  => "$pNom (" . ($p['dossier_numero'] ?? '') . ") — décision du pédiatre "
                                        . 'après consultation post-natale'
                                        . ($f('motif_neonatologie') ? ' : ' . mb_substr($f('motif_neonatologie'), 0, 120) : '.')
                                        . ' Berceau / couveuse à attribuer.',
                            'priority' => 'high',
                            'link'     => 'neonatologie',
                        ], ['INFIRMIER', 'MAJOR_INFIRMIER', 'MAJOR', 'INFIRMIER_ANESTHESISTE']);
                    } catch (\Throwable $e) { error_log('[Neonat] notif post-natale: ' . $e->getMessage()); }
                }
            }

            try {
                $this->audit->log('CREATE', 'neonat_post_natale', $id,
                    "Consultation post-natale — $pNom"
                    . ($decision === 'SORTIE' ? ' · SORTIE autorisée (retour à domicile)' : '')
                    . ($decision === 'NEONATOLOGIE' ? ' · AFFECTÉ en néonatologie' : ''),
                    null, ['patient' => $pNom, 'decision' => $decision]);
            } catch (Exception $e) {}

            header('Location: ' . BASE_URL . 'neonatologie/post-natale/' . $patient_id . '?success=' . strtolower($decision ?: 'ok'));
            exit;
        } catch (Exception $e) {
            error_log('[Neonat] sauvegarderPostNatale: ' . $e->getMessage());
            header('Location: ' . BASE_URL . 'neonatologie/post-natale/' . $patient_id . '?error=save');
            exit;
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  SUIVI DES GAVAGES — formulaire + historique
    // ─────────────────────────────────────────────────────────────────────────
    public function gavage($patient_id) {
        $this->guard();
        $patientModel = new Patient();
        $patient = $patientModel->getById((int)$patient_id);
        if (!$patient) { die("Patient introuvable."); }

        $age = self::ageNeonat($patient['date_naissance'] ?? null);

        // Historique des gavages (avec l'auteur de chaque saisie)
        $gavages = [];
        try {
            $stmt = $this->db->prepare("
                SELECT g.*, u.nom AS user_nom, u.prenom AS user_prenom, u.role AS user_role
                FROM neonat_gavage g
                LEFT JOIN users u ON u.id = g.user_id
                WHERE g.patient_id = ?
                ORDER BY g.date_gavage DESC, g.heure_gavage DESC, g.id DESC
                LIMIT 200
            ");
            $stmt->execute([(int)$patient_id]);
            $gavages = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) { error_log('[Neonat] gavage list: ' . $e->getMessage()); }

        // Dernier poids connu (pré-remplissage)
        $dernier_poids = null;
        foreach ($gavages as $g) { if ($g['poids_jour'] !== null) { $dernier_poids = $g['poids_jour']; break; } }

        // Totaux du jour
        $total_jour = ['lm' => 0, 'la' => 0, 'nb' => 0];
        foreach ($gavages as $g) {
            if ($g['date_gavage'] === date('Y-m-d')) {
                $total_jour['lm'] += (float)($g['lait_maternel_ml'] ?? 0);
                $total_jour['la'] += (float)($g['lait_artificiel_ml'] ?? 0);
                $total_jour['nb']++;
            }
        }

        require_once __DIR__ . '/../views/neonatologie/gavage.php';
    }

    public function sauvegarderGavage() {
        $this->guard();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . 'neonatologie'); exit;
        }
        $patient_id = (int)($_POST['patient_id'] ?? 0);
        if (!$patient_id) { header('Location: ' . BASE_URL . 'neonatologie?error=patient'); exit; }

        $f = fn($k) => (isset($_POST[$k]) && $_POST[$k] !== '') ? $_POST[$k] : null;
        $date  = $f('date_gavage')  ?: date('Y-m-d');
        $heure = $f('heure_gavage') ?: date('H:i');

        try {
            $this->db->prepare("
                INSERT INTO neonat_gavage
                    (patient_id, user_id, date_gavage, heure_gavage, poids_jour, residus, lait_maternel_ml, lait_artificiel_ml)
                VALUES (?,?,?,?,?,?,?,?)
            ")->execute([
                $patient_id,
                $_SESSION['user_id'] ?? null,
                $date, $heure,
                $f('poids_jour'),
                $f('residus'),
                $f('lait_maternel_ml'),
                $f('lait_artificiel_ml'),
            ]);
            $id = (int)$this->db->lastInsertId();

            try {
                $p = (new Patient())->getById($patient_id);
                $pNom = $p ? trim(($p['nom'] ?? '') . ' ' . ($p['prenom'] ?? '')) : '';
                $this->audit->log('CREATE', 'neonat_gavage', $id,
                    "Gavage enregistré — $pNom · $date $heure"
                    . ' · LM : ' . ($f('lait_maternel_ml') ?: 0) . ' ml · LA : ' . ($f('lait_artificiel_ml') ?: 0) . ' ml',
                    null, ['patient' => $pNom, 'date' => $date, 'heure' => $heure]);
            } catch (Exception $e) {}

            header('Location: ' . BASE_URL . 'neonatologie/gavage/' . $patient_id . '?success=1');
            exit;
        } catch (Exception $e) {
            error_log('[Neonat] sauvegarderGavage: ' . $e->getMessage());
            header('Location: ' . BASE_URL . 'neonatologie/gavage/' . $patient_id . '?error=save');
            exit;
        }
    }

    public function voir($id) {
        $this->guard();
        try {
            $stmt = $this->db->prepare("
                SELECT cn.*, p.nom, p.prenom, p.dossier_numero, p.date_naissance, p.sexe,
                       u.nom AS medecin_nom, u.prenom AS medecin_prenom
                FROM consultations_neonatales cn
                JOIN patients p ON p.id = cn.patient_id
                LEFT JOIN users u ON u.id = cn.medecin_id
                WHERE cn.id = ?");
            $stmt->execute([(int)$id]);
            $examen = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) { $examen = null; }

        if (!$examen) { die("Examen néonatal introuvable."); }
        $age = self::ageNeonat($examen['date_naissance'] ?? null);
        require_once __DIR__ . '/../views/neonatologie/voir.php';
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  OBSERVATION MÉDICALE DU NOUVEAU-NÉ — formulaire complet 15 sections
    // ─────────────────────────────────────────────────────────────────────────
    public function observation(int $patient_id) {
        $this->guard();
        $patientModel = new Patient();
        $patient = $patientModel->getById($patient_id);
        if (!$patient) { die("Patient introuvable."); }

        $age = self::ageNeonat($patient['date_naissance'] ?? null);

        // Dernière observation enregistrée (pour pré-remplissage)
        $obs = null;
        try {
            $stmt = $this->db->prepare("SELECT * FROM neonat_observations WHERE patient_id = ? ORDER BY id DESC LIMIT 1");
            $stmt->execute([$patient_id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                $obs = $row;
                $obs['_data'] = json_decode($row['data'] ?? '{}', true) ?: [];
            }
        } catch (Exception $e) {}

        require_once __DIR__ . '/../views/neonatologie/observation_medicale.php';
    }

    public function sauvegarderObservation() {
        $this->guard();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . 'neonatologie'); exit;
        }
        $patient_id = (int)($_POST['patient_id'] ?? 0);
        if (!$patient_id) { header('Location: ' . BASE_URL . 'neonatologie?error=patient'); exit; }

        $f = fn($k) => (isset($_POST[$k]) && $_POST[$k] !== '') ? $_POST[$k] : null;

        $data = $_POST;
        unset($data['patient_id'], $data['csrf_token'], $data['_token']);

        $motif = $f('motif_1') ? trim(implode(' / ', array_filter([
            $f('motif_1'), $f('motif_2'), $f('motif_3')
        ]))) : null;
        $diagnostic = $f('diag_positif');

        try {
            $this->db->prepare("
                INSERT INTO neonat_observations
                    (patient_id, medecin_id, date_observation, motif, diagnostic_principal, data)
                VALUES (?,?,NOW(),?,?,?)
            ")->execute([
                $patient_id,
                $_SESSION['user_id'] ?? null,
                $motif,
                $diagnostic,
                json_encode($data, JSON_UNESCAPED_UNICODE),
            ]);
            $id = (int)$this->db->lastInsertId();

            try {
                $p = (new Patient())->getById($patient_id);
                $pNom = $p ? trim(strtoupper($p['nom'] ?? '') . ' ' . ($p['prenom'] ?? '')) : '';
                $this->audit->log('CREATE', 'neonat_observations', $id,
                    "Observation médicale néonatale — $pNom"
                    . ($diagnostic ? ' · Dx : ' . mb_substr($diagnostic, 0, 80) : ''),
                    null, ['patient' => $pNom, 'motif' => $motif, 'diagnostic' => $diagnostic]);
            } catch (Exception $e) {}

            header('Location: ' . BASE_URL . 'neonatologie/observation-voir/' . $id . '?success=1');
            exit;
        } catch (Exception $e) {
            error_log('[Neonat] sauvegarderObservation: ' . $e->getMessage());
            header('Location: ' . BASE_URL . 'neonatologie/observation/' . $patient_id . '?error=' . urlencode($e->getMessage()));
            exit;
        }
    }

    public function voirObservation(int $id) {
        $this->guard();
        try {
            $stmt = $this->db->prepare("
                SELECT o.*, p.nom, p.prenom, p.dossier_numero, p.date_naissance, p.sexe,
                       u.nom AS medecin_nom, u.prenom AS medecin_prenom
                FROM neonat_observations o
                JOIN patients p ON p.id = o.patient_id
                LEFT JOIN users u ON u.id = o.medecin_id
                WHERE o.id = ?");
            $stmt->execute([$id]);
            $obs = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) { $obs = null; }

        if (!$obs) { die("Observation introuvable."); }
        $obs['_data'] = json_decode($obs['data'] ?? '{}', true) ?: [];
        $age = self::ageNeonat($obs['date_naissance'] ?? null);
        $patient = ['nom' => $obs['nom'], 'prenom' => $obs['prenom'],
                    'dossier_numero' => $obs['dossier_numero'],
                    'date_naissance' => $obs['date_naissance'], 'sexe' => $obs['sexe']];
        require_once __DIR__ . '/../views/neonatologie/observation_voir.php';
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  ADMISSION NOUVEAU-NÉ (AJAX POST → JSON)
    // ─────────────────────────────────────────────────────────────────────────
    // Ajoute les colonnes parents à consultations_neonatales si absentes
    private function ensureParentColumns(): void {
        $cols = $this->db->query("SHOW COLUMNS FROM consultations_neonatales")->fetchAll(PDO::FETCH_COLUMN);
        $needed = [
            'mere_nom'           => 'VARCHAR(150)',
            'mere_prenom'        => 'VARCHAR(150)',
            'mere_age'           => 'TINYINT UNSIGNED',
            'mere_telephone'     => 'VARCHAR(25)',
            'mere_profession'    => 'VARCHAR(100)',
            'mere_situation'     => 'VARCHAR(50)',
            'mere_adresse'       => 'TEXT',
            'mere_groupe_sanguin'=> 'VARCHAR(5)',
            'mere_antecedents'   => 'TEXT',
            'pere_nom'           => 'VARCHAR(150)',
            'pere_prenom'        => 'VARCHAR(150)',
            'pere_telephone'     => 'VARCHAR(25)',
            'pere_profession'    => 'VARCHAR(100)',
            'pere_groupe_sanguin'=> 'VARCHAR(5)',
        ];
        foreach ($needed as $col => $type) {
            if (!in_array($col, $cols)) {
                $this->db->exec("ALTER TABLE consultations_neonatales ADD COLUMN $col $type NULL");
            }
        }
    }

    public function suivi(int $patient_id) {
        $this->guard();

        // Infos patient + hospitalisation
        $stmtP = $this->db->prepare("
            SELECT p.*, h.id AS admission_id, h.date_admission, h.motif_hospitalisation,
                   l.nom_lit, c.nom_chambre,
                   u.nom AS medecin_nom, u.prenom AS medecin_prenom
            FROM patients p
            LEFT JOIN hospitalisations h ON h.patient_id = p.id AND h.statut = 'en_cours'
            LEFT JOIN lits l             ON l.id = h.lit_id
            LEFT JOIN chambres c         ON c.id = l.chambre_id
            LEFT JOIN users u            ON u.id = CAST(h.medecin_responsable AS UNSIGNED)
            WHERE p.id = ? LIMIT 1");
        $stmtP->execute([$patient_id]);
        $patient = $stmtP->fetch(PDO::FETCH_ASSOC);
        if (!$patient) { header('Location: ' . BASE_URL . 'neonatologie'); exit; }

        // Tous les examens néonatals (chronologique)
        $stmtE = $this->db->prepare("
            SELECT cn.*, u.nom AS med_nom, u.prenom AS med_prenom
            FROM consultations_neonatales cn
            LEFT JOIN users u ON u.id = cn.medecin_id
            WHERE cn.patient_id = ?
            ORDER BY cn.date_consultation ASC");
        $stmtE->execute([$patient_id]);
        $examens = $stmtE->fetchAll(PDO::FETCH_ASSOC);

        // Gavages
        $stmtG = $this->db->prepare("
            SELECT g.*, u.nom AS inf_nom, u.prenom AS inf_prenom
            FROM neonat_gavage g
            LEFT JOIN users u ON u.id = g.user_id
            WHERE g.patient_id = ?
            ORDER BY g.date_gavage DESC, g.heure_gavage DESC");
        $stmtG->execute([$patient_id]);
        $gavages = $stmtG->fetchAll(PDO::FETCH_ASSOC);

        // Observations médicales
        $stmtO = $this->db->prepare("
            SELECT o.*, u.nom AS med_nom, u.prenom AS med_prenom
            FROM neonat_observations o
            LEFT JOIN users u ON u.id = o.medecin_id
            WHERE o.patient_id = ?
            ORDER BY o.date_observation DESC");
        $stmtO->execute([$patient_id]);
        $observations = $stmtO->fetchAll(PDO::FETCH_ASSOC);

        // Post-natale
        $stmtPN = $this->db->prepare("
            SELECT pn.*, u.nom AS med_nom, u.prenom AS med_prenom
            FROM neonat_post_natale pn
            LEFT JOIN users u ON u.id = pn.medecin_id
            WHERE pn.patient_id = ?
            ORDER BY pn.created_at DESC LIMIT 1");
        $stmtPN->execute([$patient_id]);
        $post_natale = $stmtPN->fetch(PDO::FETCH_ASSOC) ?: null;

        // Soins infirmiers
        $stmtSI = $this->db->prepare("
            SELECT si.*, u.nom AS inf_nom, u.prenom AS inf_prenom
            FROM neonat_soins_infirmiers si
            LEFT JOIN users u ON u.id = si.user_id
            WHERE si.patient_id = ?
            ORDER BY si.date_soin DESC, si.heure_soin DESC");
        $stmtSI->execute([$patient_id]);
        $soins_infirmiers = $stmtSI->fetchAll(PDO::FETCH_ASSOC);

        // Paramètres vitaux (température, poids, SpO2, glycémie capillaire)
        $parametres = [];
        try {
            $stmtPV = $this->db->prepare("
                SELECT pv.*, u.nom AS user_nom, u.prenom AS user_prenom
                FROM neonat_parametres pv
                LEFT JOIN users u ON u.id = pv.user_id
                WHERE pv.patient_id = ?
                ORDER BY pv.date_mesure ASC, pv.heure_mesure ASC");
            $stmtPV->execute([$patient_id]);
            $parametres = $stmtPV->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) { error_log('[Neonat] suivi parametres: ' . $e->getMessage()); }

        // Soins planifiés via soins_hospitalisation (exécution nursing)
        $soins_executions = [];
        try {
            $admId = $patient['admission_id'] ?? null;
            if ($admId) {
                $stmtSE = $this->db->prepare("
                    SELECT sh.*,
                           u_p.nom AS plan_nom, u_p.prenom AS plan_prenom,
                           u_e.nom AS exec_nom, u_e.prenom AS exec_prenom
                    FROM soins_hospitalisation sh
                    LEFT JOIN users u_p ON u_p.id = sh.user_id_planificateur
                    LEFT JOIN users u_e ON u_e.id = sh.user_id_executant
                    WHERE sh.admission_id = ?
                      AND sh.statut NOT IN ('SUPPRIME','STOPPE','REMPLACE')
                    ORDER BY sh.date_prevue ASC
                    LIMIT 100");
                $stmtSE->execute([$admId]);
                $soins_executions = $stmtSE->fetchAll(PDO::FETCH_ASSOC);
            }
        } catch (Exception $e) { error_log('[Neonat] soins_executions: ' . $e->getMessage()); }

        $flash = $_SESSION['_neo_flash'] ?? null;
        unset($_SESSION['_neo_flash']);

        require_once __DIR__ . '/../views/neonatologie/suivi.php';
    }

    public function ajouterSoin() {
        $this->guard();
        $patient_id = (int)($_POST['patient_id'] ?? 0);
        if (!$patient_id) { $_SESSION['_neo_flash'] = ['type'=>'err','msg'=>'Patient manquant.']; header('Location: ' . BASE_URL . 'neonatologie'); exit; }

        $date        = trim($_POST['date_soin']          ?? date('Y-m-d'));
        $heure       = trim($_POST['heure_soin']         ?? date('H:i'));
        $plaintes    = trim($_POST['plaintes']            ?? '');
        $besoins     = trim($_POST['besoins_specifiques'] ?? '');
        $interventions = trim($_POST['interventions']    ?? '');
        $emargement  = trim($_POST['emargement']         ?? '');
        $userId      = $_SESSION['user_id'] ?? null;

        if (!$plaintes && !$besoins && !$interventions) {
            $_SESSION['_neo_flash'] = ['type'=>'err','msg'=>'Veuillez remplir au moins un champ (plaintes, besoins ou interventions).'];
            header('Location: ' . BASE_URL . 'neonatologie/suivi/' . $patient_id . '#soins');
            exit;
        }

        try {
            $this->db->prepare("
                INSERT INTO neonat_soins_infirmiers
                    (patient_id, user_id, date_soin, heure_soin, plaintes, besoins_specifiques, interventions, emargement)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ")->execute([$patient_id, $userId, $date, $heure, $plaintes ?: null, $besoins ?: null, $interventions ?: null, $emargement ?: null]);
            $_SESSION['_neo_flash'] = ['type'=>'ok','msg'=>'Soin infirmier enregistré.'];
        } catch (Exception $e) {
            error_log('[Neonat] ajouterSoin: ' . $e->getMessage());
            $_SESSION['_neo_flash'] = ['type'=>'err','msg'=>'Erreur : ' . $e->getMessage()];
        }
        header('Location: ' . BASE_URL . 'neonatologie/suivi/' . $patient_id . '#soins');
        exit;
    }

    public function ajouterParametre() {
        $this->guard();
        $isAjax     = ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest';
        $patient_id = (int)($_POST['patient_id'] ?? 0);

        if (!$patient_id) {
            if ($isAjax) { header('Content-Type: application/json'); echo json_encode(['ok'=>false,'msg'=>'Patient manquant']); exit; }
            header('Location: ' . BASE_URL . 'neonatologie'); exit;
        }

        $date     = trim($_POST['date_mesure']  ?? date('Y-m-d'));
        $heure    = trim($_POST['heure_mesure'] ?? date('H:i'));
        $temp     = isset($_POST['temperature']) && $_POST['temperature'] !== '' ? (float)$_POST['temperature'] : null;
        $poids    = isset($_POST['poids'])       && $_POST['poids']       !== '' ? (int)$_POST['poids']         : null;
        $spo2     = isset($_POST['saturation'])  && $_POST['saturation']  !== '' ? (float)$_POST['saturation']  : null;
        $glycemie = isset($_POST['glycemie'])    && $_POST['glycemie']    !== '' ? (float)$_POST['glycemie']    : null;

        if ($temp === null && $poids === null && $spo2 === null && $glycemie === null) {
            $msg = 'Veuillez saisir au moins un paramètre.';
            if ($isAjax) { header('Content-Type: application/json'); echo json_encode(['ok'=>false,'msg'=>$msg]); exit; }
            $_SESSION['_neo_flash'] = ['type'=>'err','msg'=>$msg];
            header('Location: ' . BASE_URL . 'neonatologie/suivi/' . $patient_id . '#parametres'); exit;
        }

        try {
            $this->db->prepare("
                INSERT INTO neonat_parametres
                    (patient_id, user_id, date_mesure, heure_mesure, temperature, poids, saturation, glycemie)
                VALUES (?,?,?,?,?,?,?,?)
            ")->execute([$patient_id, $_SESSION['user_id'] ?? null, $date, $heure, $temp, $poids, $spo2, $glycemie]);

            if ($isAjax) {
                $stmt = $this->db->prepare("
                    SELECT pv.*, u.nom AS user_nom, u.prenom AS user_prenom
                    FROM neonat_parametres pv LEFT JOIN users u ON u.id = pv.user_id
                    WHERE pv.patient_id = ?
                    ORDER BY pv.date_mesure ASC, pv.heure_mesure ASC");
                $stmt->execute([$patient_id]);
                header('Content-Type: application/json');
                echo json_encode(['ok'=>true, 'parametres'=>$stmt->fetchAll(PDO::FETCH_ASSOC)]);
                exit;
            }
            $_SESSION['_neo_flash'] = ['type'=>'ok','msg'=>'Paramètres enregistrés.'];
        } catch (Exception $e) {
            error_log('[Neonat] ajouterParametre: ' . $e->getMessage());
            if ($isAjax) { header('Content-Type: application/json'); echo json_encode(['ok'=>false,'msg'=>'Erreur serveur.']); exit; }
            $_SESSION['_neo_flash'] = ['type'=>'err','msg'=>'Erreur lors de l\'enregistrement.'];
        }
        header('Location: ' . BASE_URL . 'neonatologie/suivi/' . $patient_id . '#parametres');
        exit;
    }

    /* ── Exécution soins planifiés ──────────────────────────────────────────── */
    public function marquerSoinExecute() {
        $this->guard();
        $isAjax     = ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest';
        $soin_id    = (int)($_POST['soin_id']    ?? 0);
        $patient_id = (int)($_POST['patient_id'] ?? 0);
        $note       = trim($_POST['note']        ?? '');

        if (!$soin_id) {
            if ($isAjax) { header('Content-Type: application/json'); echo json_encode(['ok'=>false,'msg'=>'ID soin manquant']); exit; }
            header('Location: ' . BASE_URL . 'neonatologie/suivi/' . $patient_id . '#soins-executes'); exit;
        }
        try {
            $this->db->prepare("
                UPDATE soins_hospitalisation SET
                    statut           = 'REALISE',
                    user_id_executant = ?,
                    date_realisee    = NOW(),
                    note_execution   = COALESCE(NULLIF(?, ''), note_execution)
                WHERE id = ?
            ")->execute([$_SESSION['user_id'] ?? null, $note, $soin_id]);

            if ($isAjax) { header('Content-Type: application/json'); echo json_encode(['ok'=>true]); exit; }
            $_SESSION['_neo_flash'] = ['type'=>'ok','msg'=>'Soin marqué comme réalisé.'];
        } catch (Exception $e) {
            error_log('[Neonat] marquerSoinExecute: ' . $e->getMessage());
            if ($isAjax) { header('Content-Type: application/json'); echo json_encode(['ok'=>false,'msg'=>'Erreur serveur.']); exit; }
            $_SESSION['_neo_flash'] = ['type'=>'err','msg'=>'Erreur.'];
        }
        header('Location: ' . BASE_URL . 'neonatologie/suivi/' . $patient_id . '#soins-executes'); exit;
    }

    /* ── Fiche transfusionnelle ─────────────────────────────────────────────── */
    public function transfusion(int $patient_id) {
        $this->guard();
        $patient = $this->loadPatientHosp($patient_id);
        if (!$patient) { header('Location: ' . BASE_URL . 'neonatologie'); exit; }

        $transfusions = [];
        try {
            $st = $this->db->prepare("
                SELECT t.*, u.nom AS inf_nom, u.prenom AS inf_prenom,
                       m.nom AS med_nom, m.prenom AS med_prenom
                FROM neonat_transfusions t
                LEFT JOIN users u ON u.id = t.user_id
                LEFT JOIN users m ON m.id = t.medecin_id
                WHERE t.patient_id = ? ORDER BY t.date_transfusion DESC");
            $st->execute([$patient_id]);
            $transfusions = $st->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) { error_log('[Neonat] transfusion list: ' . $e->getMessage()); }

        $flash = $_SESSION['_neo_flash'] ?? null;
        unset($_SESSION['_neo_flash']);
        require_once __DIR__ . '/../views/neonatologie/transfusion.php';
    }

    public function sauvegarderTransfusion() {
        $this->guard();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ' . BASE_URL . 'neonatologie'); exit; }
        $patient_id = (int)($_POST['patient_id'] ?? 0);
        if (!$patient_id) { header('Location: ' . BASE_URL . 'neonatologie'); exit; }

        $f  = fn($k) => isset($_POST[$k]) && $_POST[$k] !== '' ? trim($_POST[$k]) : null;
        $fi = fn($k) => isset($_POST[$k]) && $_POST[$k] !== '' ? (int)$_POST[$k]   : null;
        $ff = fn($k) => isset($_POST[$k]) && $_POST[$k] !== '' ? (float)$_POST[$k] : null;

        try {
            $this->db->prepare("
                INSERT INTO neonat_transfusions
                    (patient_id, user_id, medecin_id, date_transfusion,
                     groupe_patient, rhesus_patient, produit_sanguin, volume_ml,
                     numero_poche, groupe_donneur, rhesus_donneur, indication,
                     pre_temp, pre_fc, pre_fr, pre_spo2,
                     post_temp, post_fc, post_fr, post_spo2,
                     reactions, emargement)
                VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
            ")->execute([
                $patient_id, $_SESSION['user_id'] ?? null, $f('medecin_id'),
                $f('date_transfusion') ?: date('Y-m-d H:i:s'),
                $f('groupe_patient'), $f('rhesus_patient'),
                $f('produit_sanguin'), $fi('volume_ml'),
                $f('numero_poche'), $f('groupe_donneur'), $f('rhesus_donneur'),
                $f('indication'),
                $ff('pre_temp'), $fi('pre_fc'), $fi('pre_fr'), $fi('pre_spo2'),
                $ff('post_temp'), $fi('post_fc'), $fi('post_fr'), $fi('post_spo2'),
                $f('reactions'), $f('emargement'),
            ]);
            $_SESSION['_neo_flash'] = ['type'=>'ok','msg'=>'Transfusion enregistrée avec succès.'];
        } catch (Exception $e) {
            error_log('[Neonat] sauvegarderTransfusion: ' . $e->getMessage());
            $_SESSION['_neo_flash'] = ['type'=>'err','msg'=>'Erreur : ' . $e->getMessage()];
        }
        header('Location: ' . BASE_URL . 'neonatologie/transfusion/' . $patient_id);
        exit;
    }

    /* ── Planification des soins ────────────────────────────────────────────── */
    public function planification(int $patient_id) {
        $this->guard();
        $patient = $this->loadPatientHosp($patient_id);
        if (!$patient) { header('Location: ' . BASE_URL . 'neonatologie'); exit; }

        $plans = [];
        try {
            $st = $this->db->prepare("
                SELECT ps.*, u.nom AS inf_nom, u.prenom AS inf_prenom
                FROM neonat_planification_soins ps
                LEFT JOIN users u ON u.id = ps.user_id
                WHERE ps.patient_id = ?
                ORDER BY FIELD(ps.priorite,'HAUTE','MOYENNE','BASSE'), ps.date_planification DESC");
            $st->execute([$patient_id]);
            $plans = $st->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) { error_log('[Neonat] planification list: ' . $e->getMessage()); }

        $flash = $_SESSION['_neo_flash'] ?? null;
        unset($_SESSION['_neo_flash']);
        require_once __DIR__ . '/../views/neonatologie/planification.php';
    }

    public function sauvegarderPlanification() {
        $this->guard();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ' . BASE_URL . 'neonatologie'); exit; }
        $patient_id = (int)($_POST['patient_id'] ?? 0);
        if (!$patient_id) { header('Location: ' . BASE_URL . 'neonatologie'); exit; }

        $f   = fn($k) => isset($_POST[$k]) && $_POST[$k] !== '' ? trim($_POST[$k]) : null;
        $pri = in_array($_POST['priorite'] ?? '', ['HAUTE','MOYENNE','BASSE']) ? $_POST['priorite'] : 'MOYENNE';

        try {
            $this->db->prepare("
                INSERT INTO neonat_planification_soins
                    (patient_id, user_id, date_planification, priorite,
                     diagnostic_infirmier, objectif, interventions, evaluation, echeance, statut)
                VALUES (?,?,?,?,?,?,?,?,?,?)
            ")->execute([
                $patient_id, $_SESSION['user_id'] ?? null,
                $f('date_planification') ?: date('Y-m-d'),
                $pri,
                $f('diagnostic_infirmier'), $f('objectif'),
                $f('interventions'), $f('evaluation'),
                $f('echeance'), $f('statut') ?: 'EN_COURS',
            ]);
            $_SESSION['_neo_flash'] = ['type'=>'ok','msg'=>'Plan de soin enregistré.'];
        } catch (Exception $e) {
            error_log('[Neonat] sauvegarderPlanification: ' . $e->getMessage());
            $_SESSION['_neo_flash'] = ['type'=>'err','msg'=>'Erreur : ' . $e->getMessage()];
        }
        header('Location: ' . BASE_URL . 'neonatologie/planification/' . $patient_id);
        exit;
    }

    /** Charge patient + hospitalisation en cours (réutilisé par transfusion/planification). */
    private function loadPatientHosp(int $id): ?array {
        $st = $this->db->prepare("
            SELECT p.*, h.date_admission, l.nom_lit, c.nom_chambre,
                   u.nom AS medecin_nom, u.prenom AS medecin_prenom
            FROM patients p
            LEFT JOIN hospitalisations h ON h.patient_id = p.id AND h.statut = 'en_cours'
            LEFT JOIN lits l             ON l.id = h.lit_id
            LEFT JOIN chambres c         ON c.id = l.chambre_id
            LEFT JOIN users u            ON u.id = CAST(h.medecin_responsable AS UNSIGNED)
            WHERE p.id = ? LIMIT 1");
        $st->execute([$id]);
        $r = $st->fetch(PDO::FETCH_ASSOC);
        return $r ?: null;
    }

    public function admettreNouveauNe() {
        $this->guard();
        header('Content-Type: application/json; charset=utf-8');

        // ── Nouveau-né ────────────────────────────────────────────────
        $nom           = strtoupper(trim($_POST['nom']            ?? ''));
        $prenom        = trim($_POST['prenom']                    ?? '');
        $sexe          = trim($_POST['sexe']                      ?? '');
        $dateNaissance = trim($_POST['date_naissance']            ?? '');
        $typeClient    = trim($_POST['type_client']               ?? 'PAYANT_COMPTANT');
        $serviceId     = (int)($_POST['service_id']              ?? 0);
        $litId         = (int)($_POST['lit_id']                  ?? 0) ?: null;
        $medecinId     = (int)($_POST['medecin_id']              ?? 0) ?: null;
        $dateAdmission = trim($_POST['date_admission']            ?? '');
        $motif         = trim($_POST['motif']                     ?? '');
        $termeSA       = (int)($_POST['terme_sa']                ?? 0);
        $poids         = (int)($_POST['poids_naissance']         ?? 0);
        $apgar1        = (isset($_POST['apgar_1'])  && $_POST['apgar_1']  !== '') ? (int)$_POST['apgar_1']  : null;
        $apgar5        = (isset($_POST['apgar_5'])  && $_POST['apgar_5']  !== '') ? (int)$_POST['apgar_5']  : null;
        $apgar10       = (isset($_POST['apgar_10']) && $_POST['apgar_10'] !== '') ? (int)$_POST['apgar_10'] : null;
        $typeAccouch   = trim($_POST['type_accouchement']         ?? '');

        // ── Mère ─────────────────────────────────────────────────────
        $mereNom       = strtoupper(trim($_POST['mere_nom']       ?? ''));
        $merePrenom    = trim($_POST['mere_prenom']               ?? '');
        $mereAge       = (int)($_POST['mere_age']                ?? 0) ?: null;
        $mereTel       = trim($_POST['mere_telephone']            ?? '');
        $mereProf      = trim($_POST['mere_profession']           ?? '');
        $mereSit       = trim($_POST['mere_situation']            ?? '');
        $mereAdresse   = trim($_POST['mere_adresse']              ?? '');
        $mereGS        = trim($_POST['mere_groupe_sanguin']       ?? '');
        $mereAntec     = trim($_POST['mere_antecedents']          ?? '');

        // ── Père ─────────────────────────────────────────────────────
        $pereNom       = strtoupper(trim($_POST['pere_nom']       ?? ''));
        $perePrenom    = trim($_POST['pere_prenom']               ?? '');
        $pereTel       = trim($_POST['pere_telephone']            ?? '');
        $pereProf      = trim($_POST['pere_profession']           ?? '');
        $pereGS        = trim($_POST['pere_groupe_sanguin']       ?? '');

        // ── Validations ───────────────────────────────────────────────
        if (!$nom || !$sexe || !$dateNaissance || !$termeSA || !$poids || $apgar1 === null || !$typeAccouch || !$motif) {
            echo json_encode(['success' => false, 'message' => 'Champs obligatoires manquants (nouveau-né).']);
            exit;
        }
        if (!$mereNom || !$merePrenom) {
            echo json_encode(['success' => false, 'message' => 'Le nom et prénom de la mère sont requis.']);
            exit;
        }
        if (!$mereTel) {
            echo json_encode(['success' => false, 'message' => 'Le téléphone de la mère est requis.']);
            exit;
        }

        if (!$serviceId) $serviceId = $this->neonatServiceId();

        $dateNaissSQL = date('Y-m-d H:i:s', strtotime($dateNaissance));
        $dateAdmSQL   = !empty($dateAdmission) ? date('Y-m-d H:i:s', strtotime($dateAdmission)) : date('Y-m-d H:i:s');

        // Assurer que les colonnes parents existent
        try { $this->ensureParentColumns(); } catch (Exception $e) {
            error_log('[Neonat] ensureParentColumns: ' . $e->getMessage());
        }

        try {
            $this->db->beginTransaction();

            // 1. Numéro de dossier
            $annee2  = date('y');
            $prefix  = "HSJM$annee2";
            $stmtMax = $this->db->prepare(
                "SELECT COALESCE(MAX(CAST(SUBSTRING(dossier_numero, 7) AS UNSIGNED)), 0)
                 FROM patients WHERE dossier_numero LIKE ? FOR UPDATE"
            );
            $stmtMax->execute(["$prefix%"]);
            $dossierNum = $prefix . str_pad((int)$stmtMax->fetchColumn() + 1, 4, '0', STR_PAD_LEFT);

            // 2. Créer le patient — infos mère stockées dans les colonnes contact/urgence
            $mereNomComplet = trim("$mereNom $merePrenom");
            $this->db->prepare("
                INSERT INTO patients
                    (dossier_numero, nom, prenom, date_naissance, sexe,
                     type_client, service_id,
                     contact_nom, telephone_urgence,
                     adresse, groupe_sanguin,
                     profession, situation_matrimoniale,
                     antecedents_medicaux, antecedents_familiaux,
                     statut, statut_hosp, statut_parcours, circuit, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?,
                        ?, ?,
                        ?, ?,
                        ?, ?,
                        ?, ?,
                        'HOSPITALISE', 'HOSPITALISE', 'HOSPITALISE', 'STANDARD', NOW())
            ")->execute([
                $dossierNum, $nom, $prenom ?: null, $dateNaissSQL, $sexe,
                $typeClient, $serviceId,
                $mereNomComplet ?: null, $mereTel ?: null,
                $mereAdresse ?: null, $mereGS ?: null,
                $mereProf ?: null, $mereSit ?: null,
                $mereAntec ?: null,
                ($pereNom ? "Père : $pereNom $perePrenom" . ($pereTel ? " — Tél : $pereTel" : '') . ($pereProf ? " — $pereProf" : '') : null),
            ]);
            $patientId = (int)$this->db->lastInsertId();

            // 3. Hospitalisation
            $this->db->prepare("
                INSERT INTO hospitalisations
                    (patient_id, service_id, lit_id, medecin_responsable,
                     motif_hospitalisation, date_admission, statut)
                VALUES (?, ?, ?, ?, ?, ?, 'en_cours')
            ")->execute([$patientId, $serviceId, $litId, $medecinId, $motif, $dateAdmSQL]);

            // 4. Marquer le lit (tous les lits néonataux acceptent plusieurs bébés, max 3)
            if ($litId) {
                $stCount = $this->db->prepare("SELECT COUNT(*) FROM hospitalisations WHERE lit_id=? AND statut='en_cours'");
                $stCount->execute([$litId]);
                $nbOcc = (int)$stCount->fetchColumn();
                $newStatut = ($nbOcc >= 3) ? 'OCCUPE' : 'DISPONIBLE';
                $this->db->prepare("UPDATE lits SET statut=?, occupied_by_patient_id=? WHERE id=?")
                         ->execute([$newStatut, $patientId, $litId]);
            }

            // 5. Données de naissance + infos parents dans consultations_neonatales
            $this->db->prepare("
                INSERT INTO consultations_neonatales
                    (patient_id, medecin_id, date_consultation,
                     age_gestationnel_sa, mode_accouchement, poids_naissance, apgar_1, apgar_5, apgar_10,
                     mere_nom, mere_prenom, mere_age, mere_telephone,
                     mere_profession, mere_situation, mere_adresse,
                     mere_groupe_sanguin, mere_antecedents,
                     pere_nom, pere_prenom, pere_telephone,
                     pere_profession, pere_groupe_sanguin)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?,
                        ?, ?, ?, ?,
                        ?, ?, ?,
                        ?, ?,
                        ?, ?, ?,
                        ?, ?)
            ")->execute([
                $patientId, $medecinId, $dateAdmSQL,
                $termeSA, $typeAccouch, $poids, $apgar1, $apgar5, $apgar10,
                $mereNom ?: null, $merePrenom ?: null, $mereAge, $mereTel ?: null,
                $mereProf ?: null, $mereSit ?: null, $mereAdresse ?: null,
                $mereGS ?: null, $mereAntec ?: null,
                $pereNom ?: null, $perePrenom ?: null, $pereTel ?: null,
                $pereProf ?: null, $pereGS ?: null,
            ]);

            $this->db->commit();

            $this->audit->log(
                $_SESSION['user_id'],
                'NEONAT_ADMISSION',
                "Nouveau-né $nom admis en néonatologie — dossier $dossierNum — Mère : $mereNomComplet"
            );

            echo json_encode([
                'success'    => true,
                'message'    => "Nouveau-né $nom admis avec succès (dossier $dossierNum).",
                'patient_id' => $patientId,
            ]);
        } catch (Exception $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            error_log('[Neonat] admettreNouveauNe: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Erreur serveur : ' . $e->getMessage()]);
        }
        exit;
    }
}
