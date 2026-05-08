<?php
/* ============================================================================
 * MajorController.php — Supervision de service (Infirmier Major)
 * ============================================================================ */

require_once __DIR__ . '/../../config/database.php';

class MajorController {

    private $db;

    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $database = new Database();
        $this->db = $database->getConnection();
    }

    // ─── DASHBOARD PRINCIPAL ─────────────────────────────────────────────────

    public function dashboard() {
        $serviceId = $_SESSION['service_id'] ?? 0;
        $userId    = $_SESSION['user_id']    ?? 0;

        // ── Auto-tag soins en retard ─────────────────────────────────────────
        $this->db->prepare("
            UPDATE soins_hospitalisation sh
            JOIN hospitalisations h ON sh.admission_id = h.id
            SET sh.statut = 'RETARD'
            WHERE h.service_id = ?
              AND sh.statut = 'PLANIFIE'
              AND sh.date_prevue < NOW()
        ")->execute([$serviceId]);

        // ── KPI ──────────────────────────────────────────────────────────────
        $kpi = [];

        $s = $this->db->prepare("SELECT COUNT(*) FROM hospitalisations WHERE service_id = ? AND statut = 'en_cours'");
        $s->execute([$serviceId]); $kpi['patients'] = (int)$s->fetchColumn();

        $s = $this->db->prepare("SELECT COUNT(*) FROM soins_hospitalisation sh JOIN hospitalisations h ON sh.admission_id = h.id WHERE h.service_id = ? AND DATE(sh.date_prevue) = CURDATE()");
        $s->execute([$serviceId]); $kpi['soins_jour'] = (int)$s->fetchColumn();

        $s = $this->db->prepare("SELECT COUNT(*) FROM soins_hospitalisation sh JOIN hospitalisations h ON sh.admission_id = h.id WHERE h.service_id = ? AND sh.statut IN ('RETARD') AND DATE(sh.date_prevue) = CURDATE()");
        $s->execute([$serviceId]); $kpi['retards'] = (int)$s->fetchColumn();

        $s = $this->db->prepare("SELECT COUNT(*) FROM soins_hospitalisation sh JOIN hospitalisations h ON sh.admission_id = h.id WHERE h.service_id = ? AND sh.statut = 'REALISE' AND DATE(sh.date_prevue) = CURDATE()");
        $s->execute([$serviceId]); $kpi['realises'] = (int)$s->fetchColumn();

        $kpi['taux'] = $kpi['soins_jour'] > 0 ? round($kpi['realises'] / $kpi['soins_jour'] * 100) : 0;

        $s = $this->db->prepare("SELECT COUNT(*) FROM soins_hospitalisation sh JOIN hospitalisations h ON sh.admission_id = h.id WHERE h.service_id = ? AND sh.statut = 'ANNULE' AND DATE(sh.date_prevue) = CURDATE()");
        $s->execute([$serviceId]); $kpi['annules'] = (int)$s->fetchColumn();

        // ── Nom du service ────────────────────────────────────────────────────
        $s = $this->db->prepare("SELECT nom_service FROM services WHERE id = ?");
        $s->execute([$serviceId]);
        $nomService = $s->fetchColumn() ?: 'Service';

        // ── TAB 1 : Plan du jour (soins groupés par tranche horaire) ─────────
        $stmt = $this->db->prepare("
            SELECT sh.id, sh.type_soin, sh.description, sh.condition_application,
                   sh.date_prevue, sh.date_realisee, sh.statut,
                   sh.note_execution, sh.note_major, sh.user_id_reassigne,
                   p.id AS patient_id, p.nom AS patient_nom, p.prenom AS patient_prenom,
                   p.dossier_numero,
                   l.nom_lit, c.nom_chambre,
                   up.nom AS planificateur_nom, up.prenom AS planificateur_prenom,
                   ue.nom AS executant_nom, ue.prenom AS executant_prenom,
                   ur.nom AS reassigne_nom, ur.prenom AS reassigne_prenom
            FROM soins_hospitalisation sh
            JOIN hospitalisations h ON sh.admission_id = h.id
            JOIN patients p ON h.patient_id = p.id
            LEFT JOIN lits l ON h.lit_id = l.id
            LEFT JOIN chambres c ON l.chambre_id = c.id
            LEFT JOIN users up ON sh.user_id_planificateur = up.id
            LEFT JOIN users ue ON sh.user_id_executant = ue.id
            LEFT JOIN users ur ON sh.user_id_reassigne = ur.id
            WHERE h.service_id = ? AND DATE(sh.date_prevue) = CURDATE()
            ORDER BY sh.date_prevue ASC
        ");
        $stmt->execute([$serviceId]);
        $soins_jour_raw = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $soins_par_tranche = [
            'matin'     => ['label'=>'Matin (06h–12h)',       'icon'=>'bi-brightness-high',    'color'=>'#f59e0b', 'soins'=>[]],
            'apres_midi'=> ['label'=>'Après-midi (12h–18h)',  'icon'=>'bi-sun',                'color'=>'#3b82f6', 'soins'=>[]],
            'nuit'      => ['label'=>'Soir / Nuit (18h–06h)', 'icon'=>'bi-moon-stars',         'color'=>'#6366f1', 'soins'=>[]],
        ];
        foreach ($soins_jour_raw as $s) {
            $h = (int)date('H', strtotime($s['date_prevue']));
            if      ($h >= 6  && $h < 12) $soins_par_tranche['matin']['soins'][]      = $s;
            elseif  ($h >= 12 && $h < 18) $soins_par_tranche['apres_midi']['soins'][] = $s;
            else                           $soins_par_tranche['nuit']['soins'][]        = $s;
        }

        // ── TAB 2 : Retards & Annulations ────────────────────────────────────
        $stmt = $this->db->prepare("
            SELECT sh.*, p.nom AS patient_nom, p.prenom AS patient_prenom,
                   p.dossier_numero, l.nom_lit, c.nom_chambre,
                   up.nom AS planificateur_nom, up.prenom AS planificateur_prenom,
                   ue.nom AS executant_nom
            FROM soins_hospitalisation sh
            JOIN hospitalisations h ON sh.admission_id = h.id
            JOIN patients p ON h.patient_id = p.id
            LEFT JOIN lits l ON h.lit_id = l.id
            LEFT JOIN chambres c ON l.chambre_id = c.id
            LEFT JOIN users up ON sh.user_id_planificateur = up.id
            LEFT JOIN users ue ON sh.user_id_executant = ue.id
            WHERE h.service_id = ?
              AND sh.statut IN ('RETARD','ANNULE')
            ORDER BY FIELD(sh.statut,'RETARD','ANNULE'), sh.date_prevue ASC
            LIMIT 100
        ");
        $stmt->execute([$serviceId]);
        $soins_alertes = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // ── TAB 3 : Activité par infirmier ────────────────────────────────────
        $stmt = $this->db->prepare("
            SELECT u.id AS infirmier_id, u.nom, u.prenom, u.role,
                   COUNT(sh.id)                                      AS total,
                   SUM(sh.statut = 'REALISE')                        AS realises,
                   SUM(sh.statut = 'RETARD')                         AS retards,
                   SUM(sh.statut = 'ANNULE')                         AS annules,
                   SUM(sh.statut = 'PLANIFIE')                       AS en_attente
            FROM users u
            JOIN soins_hospitalisation sh ON sh.user_id_planificateur = u.id
            JOIN hospitalisations h ON sh.admission_id = h.id
            WHERE h.service_id = ?
              AND u.role IN ('INFIRMIER','MAJOR')
              AND DATE(sh.date_prevue) = CURDATE()
            GROUP BY u.id, u.nom, u.prenom, u.role
            ORDER BY retards DESC, realises DESC
        ");
        $stmt->execute([$serviceId]);
        $activite_infirmiers = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // ── TAB 4 : Patients du service ───────────────────────────────────────
        $stmt = $this->db->prepare("
            SELECT p.id, p.nom, p.prenom, p.dossier_numero, p.date_naissance,
                   l.nom_lit, c.nom_chambre, h.date_admission, h.id AS hosp_id,
                   h.medecin_responsable,
                   pp.temperature,
                   pp.pression_arterielle_systolique AS ta_sys,
                   pp.pression_arterielle_diastolique AS ta_dia,
                   pp.frequence_cardiaque AS fc,
                   pp.saturation_oxygene AS spo2,
                   pp.glycemie,
                   pp.frequence_respiratoire AS fr,
                   pp.date_mesure AS date_derniere_mesure,
                   upm.nom AS mesureur_nom, upm.prenom AS mesureur_prenom,
                   (SELECT COUNT(*) FROM soins_hospitalisation s2
                    JOIN hospitalisations h2 ON s2.admission_id = h2.id
                    WHERE h2.patient_id = p.id AND s2.statut IN ('PLANIFIE','RETARD')
                      AND DATE(s2.date_prevue) = CURDATE()) AS soins_restants,
                   (SELECT COUNT(*) FROM soins_hospitalisation s3
                    JOIN hospitalisations h3 ON s3.admission_id = h3.id
                    WHERE h3.patient_id = p.id AND s3.statut = 'REALISE'
                      AND DATE(s3.date_prevue) = CURDATE()) AS soins_faits
            FROM patients p
            JOIN hospitalisations h ON p.id = h.patient_id
            LEFT JOIN lits l ON h.lit_id = l.id
            LEFT JOIN chambres c ON l.chambre_id = c.id
            LEFT JOIN patient_parametres pp ON pp.id = (
                SELECT id FROM patient_parametres WHERE patient_id = p.id
                ORDER BY date_mesure DESC LIMIT 1
            )
            LEFT JOIN users upm ON pp.user_id = upm.id
            WHERE h.service_id = ? AND h.statut = 'en_cours'
            ORDER BY c.nom_chambre, l.nom_lit
        ");
        $stmt->execute([$serviceId]);
        $patients_service = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // ── Équipe du service (pour réassignation) ────────────────────────────
        $stmt = $this->db->prepare("SELECT id, nom, prenom, role FROM users WHERE service_id = ? AND role IN ('INFIRMIER','MAJOR') AND actif = 1 ORDER BY nom");
        $stmt->execute([$serviceId]);
        $equipe = $stmt->fetchAll(PDO::FETCH_ASSOC);

        require_once __DIR__ . '/../views/major/dashboard.php';
    }

    // ─── ACTIONS AJAX ─────────────────────────────────────────────────────────

    public function marquerRetard() {
        header('Content-Type: application/json');
        $data    = json_decode(file_get_contents('php://input'), true) ?: $_POST;
        $soin_id = (int)($data['soin_id'] ?? 0);
        $note    = trim($data['note'] ?? '');
        try {
            $this->db->prepare("UPDATE soins_hospitalisation SET statut='RETARD', note_major=? WHERE id = ? AND statut IN ('PLANIFIE','RETARD')")
                     ->execute([$note ?: null, $soin_id]);
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    public function reassignerSoin() {
        header('Content-Type: application/json');
        $data         = json_decode(file_get_contents('php://input'), true) ?: $_POST;
        $soin_id      = (int)($data['soin_id'] ?? 0);
        $infirmier_id = (int)($data['infirmier_id'] ?? 0);
        $note         = trim($data['note'] ?? '');
        try {
            $this->db->prepare("UPDATE soins_hospitalisation SET user_id_reassigne=?, date_reassignation=NOW(), note_major=? WHERE id=?")
                     ->execute([$infirmier_id, $note ?: null, $soin_id]);
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    public function annoterSoin() {
        header('Content-Type: application/json');
        $data    = json_decode(file_get_contents('php://input'), true) ?: $_POST;
        $soin_id = (int)($data['soin_id'] ?? 0);
        $note    = trim($data['note'] ?? '');
        try {
            $this->db->prepare("UPDATE soins_hospitalisation SET note_major=? WHERE id=?")->execute([$note, $soin_id]);
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }
}
