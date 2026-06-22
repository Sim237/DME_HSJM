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
 * FicheChirurgieController — Fiches cliniques anesthésie (HSJM)
 * Formulaires : Consultation Pré-Anesthésique · Phase Peropératoire · Surveillance Post-op
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../services/CsrfService.php';
require_once __DIR__ . '/../services/AuditService.php';

class FicheChirurgieController {

    private PDO $db;
    private AuditService $audit;

    private const ROLES_AUTORISES = ['ANESTHESISTE','INFIRMIER_ANESTHESISTE','INFIRMIER','MAJOR_INFIRMIER','INFIRMIER_CONSULTANT','MEDECIN','CHIRURGIEN','ADMIN','SUPER_ADMIN'];

    public function __construct() {
        $this->db    = (new Database())->getConnection();
        $this->audit = new AuditService();
        $this->guard();
        $this->ensureSchema();
    }

    private function guard(): void {
        if (empty($_SESSION['logged_in'])) {
            header('Location: ' . BASE_URL . 'login'); exit;
        }
        if (!in_array($_SESSION['user_role'] ?? '', self::ROLES_AUTORISES)) {
            http_response_code(403);
            die('<h2>Accès non autorisé.</h2>');
        }
    }

    private function ensureSchema(): void {
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS fiches_anesthesie (
                id               INT AUTO_INCREMENT PRIMARY KEY,
                patient_id       INT NOT NULL,
                type             ENUM('pre_anesthesique','per_operatoire','surveillance_post_op') NOT NULL,
                data             LONGTEXT NULL,
                anesthesiste_id  INT NULL,
                statut           ENUM('brouillon','finalise') DEFAULT 'brouillon',
                created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                KEY idx_patient  (patient_id, type),
                KEY idx_anesth   (anesthesiste_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function getPatient(int $id): ?array {
        $s = $this->db->prepare("
            SELECT p.*, s.nom_service
            FROM patients p
            LEFT JOIN services s ON s.id = p.service_id
            WHERE p.id = ?
        ");
        $s->execute([$id]);
        return $s->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    private function getFiche(int $id): ?array {
        $s = $this->db->prepare("
            SELECT f.*, p.nom, p.prenom, p.dossier_numero, p.date_naissance, p.sexe,
                   u.nom AS anesth_nom, u.prenom AS anesth_prenom
            FROM fiches_anesthesie f
            JOIN patients p ON p.id = f.patient_id
            LEFT JOIN users u ON u.id = f.anesthesiste_id
            WHERE f.id = ?
        ");
        $s->execute([$id]);
        return $s->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    private function getPatients(): array {
        return $this->db->query("
            SELECT p.id, p.nom, p.prenom, p.dossier_numero, s.nom_service
            FROM patients p
            LEFT JOIN services s ON s.id = p.service_id
            WHERE p.actif = 1
            ORDER BY p.nom, p.prenom
        ")->fetchAll(PDO::FETCH_ASSOC);
    }

    private function sauvegarder(string $type): void {
        header('Content-Type: application/json; charset=utf-8');
        CsrfService::check();

        $patient_id = (int)($_POST['patient_id'] ?? 0);
        $fiche_id   = (int)($_POST['fiche_id']   ?? 0);
        $statut     = ($_POST['statut'] ?? '') === 'finalise' ? 'finalise' : 'brouillon';

        if (!$patient_id) {
            echo json_encode(['success' => false, 'message' => 'Patient requis.']); exit;
        }

        $exclude = ['_csrf_token','patient_id','fiche_id','statut'];
        $data = [];
        foreach ($_POST as $k => $v) {
            if (!in_array($k, $exclude)) $data[$k] = $v;
        }
        $json = json_encode($data, JSON_UNESCAPED_UNICODE);

        try {
            if ($fiche_id) {
                $this->db->prepare("
                    UPDATE fiches_anesthesie SET data=?, statut=?, updated_at=NOW() WHERE id=? AND type=?
                ")->execute([$json, $statut, $fiche_id, $type]);
            } else {
                $this->db->prepare("
                    INSERT INTO fiches_anesthesie (patient_id, type, data, anesthesiste_id, statut)
                    VALUES (?, ?, ?, ?, ?)
                ")->execute([$patient_id, $type, $json, $_SESSION['user_id'] ?? null, $statut]);
                $fiche_id = (int)$this->db->lastInsertId();
            }
            $this->audit->log($_SESSION['user_id'] ?? 0, 'UPSERT', 'fiches_anesthesie', $fiche_id,
                "Fiche $type patient #$patient_id");
            echo json_encode(['success' => true, 'fiche_id' => $fiche_id,
                'message' => $statut === 'finalise' ? 'Fiche finalisée.' : 'Brouillon sauvegardé.']);
        } catch (\Throwable $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    // ── FICHE 7 — Consultation Pré-Anesthésique ───────────────────────────────

    public function preAnesthesique(int $patient_id = 0): void {
        $patients  = $this->getPatients();
        $patient   = $patient_id ? $this->getPatient($patient_id) : null;
        $fiche     = null;
        $fiche_id  = (int)($_GET['fiche_id'] ?? 0);

        if ($patient_id && !$fiche_id) {
            $r = $this->db->prepare("SELECT * FROM fiches_anesthesie WHERE patient_id=? AND type='pre_anesthesique' ORDER BY id DESC LIMIT 1");
            $r->execute([$patient_id]);
            $fiche = $r->fetch(PDO::FETCH_ASSOC) ?: null;
        } elseif ($fiche_id) {
            $fiche = $this->getFiche($fiche_id);
            if ($fiche) {
                $patient = $this->getPatient((int)$fiche['patient_id']);
                $patient_id = (int)$fiche['patient_id'];
            }
        }

        $data = $fiche ? json_decode($fiche['data'] ?? '{}', true) : [];
        require_once __DIR__ . '/../views/anesthesie/fiche_pre_anesthesique.php';
    }

    public function sauvegarderPreAnesthesique(): void {
        $this->sauvegarder('pre_anesthesique');
    }

    // ── FICHE 5 — Phase Peropératoire ─────────────────────────────────────────

    public function perOperatoire(int $patient_id = 0): void {
        $patients  = $this->getPatients();
        $patient   = $patient_id ? $this->getPatient($patient_id) : null;
        $fiche     = null;
        $fiche_id  = (int)($_GET['fiche_id'] ?? 0);

        if ($patient_id && !$fiche_id) {
            $r = $this->db->prepare("SELECT * FROM fiches_anesthesie WHERE patient_id=? AND type='per_operatoire' ORDER BY id DESC LIMIT 1");
            $r->execute([$patient_id]);
            $fiche = $r->fetch(PDO::FETCH_ASSOC) ?: null;
        } elseif ($fiche_id) {
            $fiche = $this->getFiche($fiche_id);
            if ($fiche) {
                $patient = $this->getPatient((int)$fiche['patient_id']);
                $patient_id = (int)$fiche['patient_id'];
            }
        }

        $data = $fiche ? json_decode($fiche['data'] ?? '{}', true) : [];
        require_once __DIR__ . '/../views/anesthesie/fiche_per_operatoire.php';
    }

    public function sauvegarderPerOperatoire(): void {
        $this->sauvegarder('per_operatoire');
    }

    // ── FICHE 1 — Soins et Surveillance Post-op ───────────────────────────────

    public function surveillancePostOp(int $patient_id = 0): void {
        $patients  = $this->getPatients();
        $patient   = $patient_id ? $this->getPatient($patient_id) : null;
        $fiche     = null;
        $fiche_id  = (int)($_GET['fiche_id'] ?? 0);

        if ($patient_id && !$fiche_id) {
            $r = $this->db->prepare("SELECT * FROM fiches_anesthesie WHERE patient_id=? AND type='surveillance_post_op' ORDER BY id DESC LIMIT 1");
            $r->execute([$patient_id]);
            $fiche = $r->fetch(PDO::FETCH_ASSOC) ?: null;
        } elseif ($fiche_id) {
            $fiche = $this->getFiche($fiche_id);
            if ($fiche) {
                $patient = $this->getPatient((int)$fiche['patient_id']);
                $patient_id = (int)$fiche['patient_id'];
            }
        }

        $data = $fiche ? json_decode($fiche['data'] ?? '{}', true) : [];
        require_once __DIR__ . '/../views/anesthesie/fiche_surveillance_post_op.php';
    }

    public function sauvegarderSurveillancePostOp(): void {
        $this->sauvegarder('surveillance_post_op');
    }

    // ── API JSON : infos patient + intervention programmée ────────────────────

    public function patientJson(int $patient_id): void {
        header('Content-Type: application/json; charset=utf-8');
        $p = $this->getPatient($patient_id);
        if (!$p) { echo json_encode(['success' => false, 'message' => 'Patient introuvable']); exit; }

        // Âge calculé
        $age = null;
        if (!empty($p['date_naissance'])) {
            $age = (int)(new \DateTime())->diff(new \DateTime($p['date_naissance']))->y;
        }

        // Intervention programmée la plus proche
        $inter = null;
        try {
            $s = $this->db->prepare("
                SELECT bi.type_intervention, bi.date_prevue, bi.duree_prevue,
                       u.nom AS chirurgien_nom, u.prenom AS chirurgien_prenom,
                       bs.nom_salle
                FROM bloc_interventions bi
                LEFT JOIN users u      ON u.id  = bi.chirurgien_id
                LEFT JOIN bloc_salles bs ON bs.id = bi.salle_id
                WHERE bi.patient_id = ? AND bi.statut IN ('PROGRAMMEE','EN_COURS')
                ORDER BY bi.date_prevue ASC LIMIT 1
            ");
            $s->execute([$patient_id]);
            $inter = $s->fetch(\PDO::FETCH_ASSOC) ?: null;
        } catch (\Throwable $e) {}

        // Demande bloc si pas d'intervention directe
        if (!$inter) {
            try {
                $s = $this->db->prepare("
                    SELECT bd.acte_prevu AS type_intervention, bd.date_souhaitee AS date_prevue,
                           u.nom AS chirurgien_nom, u.prenom AS chirurgien_prenom, NULL AS nom_salle
                    FROM bloc_demandes bd
                    LEFT JOIN users u ON u.id = bd.chirurgien_id
                    WHERE bd.patient_id = ? AND bd.statut IN ('EN_ATTENTE','VALIDEE')
                    ORDER BY bd.created_at ASC LIMIT 1
                ");
                $s->execute([$patient_id]);
                $inter = $s->fetch(\PDO::FETCH_ASSOC) ?: null;
            } catch (\Throwable $e) {}
        }

        echo json_encode([
            'success'      => true,
            'patient'      => [
                'nom'                    => $p['nom'],
                'prenom'                 => $p['prenom'],
                'dossier_numero'         => $p['dossier_numero'],
                'age'                    => $age,
                'sexe'                   => $p['sexe'],
                'date_naissance'         => $p['date_naissance'],
                'telephone'              => $p['telephone'] ?? '',
                'profession'             => $p['profession'] ?? '',
                'adresse'                => $p['adresse'] ?? '',
                'groupe_sanguin'         => $p['groupe_sanguin'] ?? '',
                'allergies'              => $p['allergies'] ?? '',
                'antecedents_medicaux'   => $p['antecedents_medicaux'] ?? '',
                'antecedents_chirurgicaux' => $p['antecedents_chirurgicaux'] ?? '',
                'antecedents_familiaux'  => $p['antecedents_familiaux'] ?? '',
                'nom_service'            => $p['nom_service'] ?? '',
            ],
            'intervention' => $inter,
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // ── Check-list Ouverture Salle d'Anesthésie ───────────────────────────────

    public function ouvertureSalleAnesthesie(): void {
        $this->db->exec("CREATE TABLE IF NOT EXISTS anesthesie_ouverture_salle (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT,
            date_fiche DATE,
            nom_salle VARCHAR(100),
            personnel VARCHAR(255),
            checklist_json JSON,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX(user_id),
            INDEX(date_fiche)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $salles = $this->db->query(
            "SELECT id, nom_salle FROM bloc_salles ORDER BY nom_salle"
        )->fetchAll(PDO::FETCH_ASSOC);

        require_once __DIR__ . '/../views/anesthesie/ouverture_salle_anesthesie.php';
    }

    public function sauvegarderOuvertureSalleAnesthesie(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: '.BASE_URL.'anesthesie/ouverture-salle'); exit; }
        if (!CsrfService::validate()) { header('Location: '.BASE_URL.'anesthesie/ouverture-salle?error=csrf'); exit; }

        $this->db->exec("CREATE TABLE IF NOT EXISTS anesthesie_ouverture_salle (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT,
            date_fiche DATE,
            nom_salle VARCHAR(100),
            personnel VARCHAR(255),
            checklist_json JSON,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX(user_id),
            INDEX(date_fiche)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $sections = [
            // I - Ouverture de la salle
            'fiche_anesthesie','hygiene_salle','table_operatoire','scialytique','oxygene_mural',
            // II - Accueil + Installation
            'identite_patient','vpo','jeun_preop','preparation_faite',
            'dossier_medical','prothese','brassard_pa',
            // III - Monitorage
            'ecg_electrodes','spo2_capteur','thermometre','catheters',
            // IV - VVP
            'perfuseurs_robinet','transfuseur','solutes_remplissage',
            // V - Voies aériennes
            'ventilation_colonne','aspiration_aspirateur','sng','intubation_kit',
            // VI - Drogues anesthésiques
            'premed_midazolam','ag_hypnotiques','ag_morphiniques','ag_curares',
            'ag_halogenes','alr_kit','antidotes','reanimation_drogues',
            'atb','anti_hta','diuretique',
            // VII - Autres
            'anti_oedemateux','nvpo_dexa','ulcere_stress',
        ];

        $checklist = [];
        foreach ($sections as $k) {
            $checklist[$k] = [
                'statut' => $_POST[$k.'_statut'] ?? null,
                'obs'    => trim($_POST[$k.'_obs'] ?? ''),
            ];
        }

        $this->db->prepare(
            "INSERT INTO anesthesie_ouverture_salle
             (user_id, date_fiche, nom_salle, personnel, checklist_json)
             VALUES (?,?,?,?,?)"
        )->execute([
            $_SESSION['user_id'] ?? null,
            $_POST['date_fiche']  ?? date('Y-m-d'),
            trim($_POST['nom_salle']  ?? ''),
            trim($_POST['personnel']  ?? ''),
            json_encode($checklist, JSON_UNESCAPED_UNICODE),
        ]);

        header('Location: '.BASE_URL.'anesthesie/ouverture-salle?ok=1'); exit;
    }

    // ── Liste des fiches d'un patient ─────────────────────────────────────────

    public function fichesDuPatient(int $patient_id): void {
        header('Content-Type: application/json; charset=utf-8');
        $rows = $this->db->prepare("
            SELECT f.id, f.type, f.statut, f.created_at, f.updated_at,
                   u.nom AS anesth_nom, u.prenom AS anesth_prenom
            FROM fiches_anesthesie f
            LEFT JOIN users u ON u.id = f.anesthesiste_id
            WHERE f.patient_id = ?
            ORDER BY f.created_at DESC
        ");
        $rows->execute([$patient_id]);
        echo json_encode(['success' => true, 'fiches' => $rows->fetchAll(PDO::FETCH_ASSOC)]);
        exit;
    }
}
