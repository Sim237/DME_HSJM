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
   FICHIER : app/controllers/PmiController.php
   Module PMI — Protection Maternelle et Infantile
   3 axes : Consultations prénatales · Suivi des tout-petits · Aide à la famille
   Accès : service PMI, Maternité, Pédiatrie et ADMIN
   ============================================================================ */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../models/Patient.php';
require_once __DIR__ . '/../services/AuditService.php';

class PmiController {

    private $db;
    private $audit;

    public function __construct() {
        $this->db    = (new Database())->getConnection();
        $this->audit = new AuditService();
        if (empty($_SESSION['user_id'])) { header('Location: ' . BASE_URL . 'login'); exit; }
        $this->ensureSchema();
    }

    private function canAccess(): bool {
        if (defined('GARDE_ACTIVE') && GARDE_ACTIVE) return true;
        $nom  = strtolower($_SESSION['nom_service'] ?? '');
        $role = strtoupper($_SESSION['user_role'] ?? $_SESSION['role'] ?? '');
        if (in_array($role, ['ADMIN','ADMINISTRATEUR'])) return true;
        return stripos($nom, 'pmi') !== false
            || stripos($nom, 'maternelle') !== false
            || stripos($nom, 'maternit') !== false
            || stripos($nom, 'pédiatr') !== false
            || stripos($nom, 'pediatr') !== false
            || $role === 'SAGE_FEMME';
    }

    private function guard() {
        if (!$this->canAccess()) {
            http_response_code(403);
            $error_cause = "Ce module est réservé au personnel des services PMI, Maternité et Pédiatrie.";
            require __DIR__ . '/../views/errors/403.php';
            exit;
        }
    }

    private function ensureSchema() {
        try {
            $exists = fn($t) => (int)$this->db->query(
                "SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='".$t."'"
            )->fetchColumn() > 0;

            if (!$exists('pmi_cpn')) {
                $this->db->exec("CREATE TABLE pmi_cpn (id INT AUTO_INCREMENT PRIMARY KEY, patient_id INT NOT NULL, soignant_id INT NULL,
                    date_consultation DATETIME DEFAULT CURRENT_TIMESTAMP, numero_cpn INT NULL, terme_sa DECIMAL(4,1) NULL, gestite INT NULL, parite INT NULL,
                    ddr DATE NULL, dpa DATE NULL, poids DECIMAL(5,1) NULL, ta_systolique INT NULL, ta_diastolique INT NULL,
                    hauteur_uterine DECIMAL(4,1) NULL, bruits_coeur_foetal INT NULL, mouvements_actifs TINYINT(1) DEFAULT 1, presentation VARCHAR(30) NULL,
                    oedemes TINYINT(1) DEFAULT 0, albuminurie VARCHAR(20) NULL, glycosurie VARCHAR(20) NULL, vat VARCHAR(20) NULL,
                    fer_folates TINYINT(1) DEFAULT 0, tpi_palu TINYINT(1) DEFAULT 0, examens_prescrits TEXT, traitement TEXT, prochain_rdv DATE NULL, observations TEXT,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, KEY idx_p (patient_id, id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            }
            if (!$exists('pmi_suivi_enfant')) {
                $this->db->exec("CREATE TABLE pmi_suivi_enfant (id INT AUTO_INCREMENT PRIMARY KEY, patient_id INT NOT NULL, soignant_id INT NULL,
                    date_consultation DATETIME DEFAULT CURRENT_TIMESTAMP, age_mois INT NULL, poids DECIMAL(5,2) NULL, taille DECIMAL(4,1) NULL, perimetre_cranien DECIMAL(4,1) NULL,
                    etat_nutritionnel VARCHAR(30) NULL, allaitement VARCHAR(30) NULL, alimentation TEXT,
                    dev_tient_tete TINYINT(1) DEFAULT 0, dev_assis TINYINT(1) DEFAULT 0, dev_marche TINYINT(1) DEFAULT 0, dev_parle TINYINT(1) DEFAULT 0, dev_proprete TINYINT(1) DEFAULT 0, dev_observations TEXT,
                    vaccins_jour TINYINT(1) DEFAULT 1, vaccins_administres TEXT, prochain_vaccin DATE NULL, examen_clinique TEXT, conduite TEXT, prochain_rdv DATE NULL, observations TEXT,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, KEY idx_p (patient_id, id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            }
            if (!$exists('pmi_atelier')) {
                $this->db->exec("CREATE TABLE pmi_atelier (id INT AUTO_INCREMENT PRIMARY KEY, animateur_id INT NULL, patient_id INT NULL,
                    date_atelier DATETIME DEFAULT CURRENT_TIMESTAMP, type_atelier VARCHAR(30) NOT NULL, theme VARCHAR(200) NULL,
                    participant_nom VARCHAR(150) NULL, nb_participants INT DEFAULT 1, contenu TEXT, conseils TEXT, observations TEXT,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, KEY idx_type (type_atelier, id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            }
        } catch (Exception $e) { error_log('[PMI] ensureSchema: ' . $e->getMessage()); }
    }

    public static function ageEnfant($dateNaissance): string {
        if (empty($dateNaissance)) return '—';
        try {
            $d = (new DateTime($dateNaissance))->diff(new DateTime());
            if ($d->y >= 1) return $d->y . ' an' . ($d->y>1?'s':'') . ($d->m? ' '.$d->m.' m':'');
            if ($d->m >= 1) return $d->m . ' mois';
            return max(0,(int)$d->days) . ' j';
        } catch (Exception $e) { return '—'; }
    }
    public static function ageMois($dateNaissance): int {
        if (empty($dateNaissance)) return 0;
        try { $d=(new DateTime($dateNaissance))->diff(new DateTime()); return $d->y*12+$d->m; } catch (Exception $e) { return 0; }
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  TABLEAU DE BORD
    // ─────────────────────────────────────────────────────────────────────────
    public function dashboard() {
        $this->guard();
        $kpi = ['cpn_jour'=>0,'enfants_jour'=>0,'ateliers_jour'=>0,'cpn_total'=>0,'enfants_total'=>0,'ateliers_total'=>0,'rdv_semaine'=>0];
        try {
            $kpi['cpn_jour']      = (int)$this->db->query("SELECT COUNT(*) FROM pmi_cpn WHERE DATE(date_consultation)=CURDATE()")->fetchColumn();
            $kpi['enfants_jour']  = (int)$this->db->query("SELECT COUNT(*) FROM pmi_suivi_enfant WHERE DATE(date_consultation)=CURDATE()")->fetchColumn();
            $kpi['ateliers_jour'] = (int)$this->db->query("SELECT COUNT(*) FROM pmi_atelier WHERE DATE(date_atelier)=CURDATE()")->fetchColumn();
            $kpi['cpn_total']     = (int)$this->db->query("SELECT COUNT(*) FROM pmi_cpn")->fetchColumn();
            $kpi['enfants_total'] = (int)$this->db->query("SELECT COUNT(*) FROM pmi_suivi_enfant")->fetchColumn();
            $kpi['ateliers_total']= (int)$this->db->query("SELECT COUNT(*) FROM pmi_atelier")->fetchColumn();
            $kpi['rdv_semaine']   = (int)$this->db->query("SELECT (SELECT COUNT(*) FROM pmi_cpn WHERE prochain_rdv BETWEEN CURDATE() AND DATE_ADD(CURDATE(),INTERVAL 7 DAY)) + (SELECT COUNT(*) FROM pmi_suivi_enfant WHERE prochain_rdv BETWEEN CURDATE() AND DATE_ADD(CURDATE(),INTERVAL 7 DAY))")->fetchColumn();
        } catch (Exception $e) {}

        $recentsCpn = $recentsEnfant = $recentsAtelier = [];
        try {
            $recentsCpn = $this->db->query("SELECT c.id,c.date_consultation,c.terme_sa,c.numero_cpn,c.prochain_rdv,p.nom,p.prenom,p.dossier_numero FROM pmi_cpn c JOIN patients p ON p.id=c.patient_id ORDER BY c.id DESC LIMIT 8")->fetchAll(PDO::FETCH_ASSOC);
            $recentsEnfant = $this->db->query("SELECT s.id,s.date_consultation,s.age_mois,s.poids,s.vaccins_jour,p.nom,p.prenom,p.dossier_numero,p.date_naissance FROM pmi_suivi_enfant s JOIN patients p ON p.id=s.patient_id ORDER BY s.id DESC LIMIT 8")->fetchAll(PDO::FETCH_ASSOC);
            $recentsAtelier = $this->db->query("SELECT a.*, u.nom AS anim_nom, u.prenom AS anim_prenom FROM pmi_atelier a LEFT JOIN users u ON u.id=a.animateur_id ORDER BY a.id DESC LIMIT 8")->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {}

        require_once __DIR__ . '/../views/pmi/dashboard.php';
    }

    /* ── Helpers form ───────────────────────────────────────────────────────── */
    private function loadPatient($id) {
        $p = (new Patient())->getById((int)$id);
        if (!$p) { die("Patient introuvable."); }
        return $p;
    }
    private function postVal($k) { return (isset($_POST[$k]) && $_POST[$k] !== '') ? $_POST[$k] : null; }
    private function postCb($k)  { return !empty($_POST[$k]) ? 1 : 0; }

    // ── 1. CONSULTATION PRÉNATALE ───────────────────────────────────────────
    public function cpnForm($patient_id) {
        $this->guard();
        $patient = $this->loadPatient($patient_id);
        require_once __DIR__ . '/../views/pmi/cpn_form.php';
    }
    public function cpnSave() {
        $this->guard();
        $pid = (int)($_POST['patient_id'] ?? 0);
        if (!$pid) { header('Location: '.BASE_URL.'pmi'); exit; }
        $f = fn($k)=>$this->postVal($k); $cb = fn($k)=>$this->postCb($k);
        try {
            $stmt = $this->db->prepare("INSERT INTO pmi_cpn
                (patient_id,soignant_id,numero_cpn,terme_sa,gestite,parite,ddr,dpa,poids,ta_systolique,ta_diastolique,
                 hauteur_uterine,bruits_coeur_foetal,mouvements_actifs,presentation,oedemes,albuminurie,glycosurie,
                 vat,fer_folates,tpi_palu,examens_prescrits,traitement,prochain_rdv,observations)
                VALUES (?,?,?,?,?,?,?,?,?,?,?, ?,?,?,?,?,?,?, ?,?,?,?,?,?,?)");
            $stmt->execute([$pid,$_SESSION['user_id']??null,$f('numero_cpn'),$f('terme_sa'),$f('gestite'),$f('parite'),
                $f('ddr'),$f('dpa'),$f('poids'),$f('ta_systolique'),$f('ta_diastolique'),
                $f('hauteur_uterine'),$f('bruits_coeur_foetal'),$cb('mouvements_actifs'),$f('presentation'),$cb('oedemes'),
                $f('albuminurie'),$f('glycosurie'),$f('vat'),$cb('fer_folates'),$cb('tpi_palu'),
                $f('examens_prescrits'),$f('traitement'),$f('prochain_rdv'),$f('observations')]);
            $id = (int)$this->db->lastInsertId();
            $this->logPatient('pmi_cpn',$id,$pid,'Consultation prénatale (CPN'.($f('numero_cpn')?' n°'.$f('numero_cpn'):'').') enregistrée');
            header('Location: '.BASE_URL.'pmi/cpn/voir/'.$id); exit;
        } catch (Exception $e) { error_log('[PMI cpn] '.$e->getMessage()); header('Location: '.BASE_URL.'pmi/cpn/'.$pid.'?error=1'); exit; }
    }
    public function cpnVoir($id) {
        $this->guard();
        $stmt = $this->db->prepare("SELECT c.*,p.nom,p.prenom,p.dossier_numero,p.date_naissance,u.nom AS s_nom,u.prenom AS s_prenom FROM pmi_cpn c JOIN patients p ON p.id=c.patient_id LEFT JOIN users u ON u.id=c.soignant_id WHERE c.id=?");
        $stmt->execute([(int)$id]); $examen = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$examen) die("CPN introuvable.");
        $type='cpn'; require_once __DIR__ . '/../views/pmi/voir.php';
    }

    // ── 2. SUIVI ENFANT ─────────────────────────────────────────────────────
    public function enfantForm($patient_id) {
        $this->guard();
        $patient = $this->loadPatient($patient_id);
        $ageMois = self::ageMois($patient['date_naissance'] ?? null);
        require_once __DIR__ . '/../views/pmi/enfant_form.php';
    }
    public function enfantSave() {
        $this->guard();
        $pid = (int)($_POST['patient_id'] ?? 0);
        if (!$pid) { header('Location: '.BASE_URL.'pmi'); exit; }
        $f = fn($k)=>$this->postVal($k); $cb = fn($k)=>$this->postCb($k);
        try {
            $stmt = $this->db->prepare("INSERT INTO pmi_suivi_enfant
                (patient_id,soignant_id,age_mois,poids,taille,perimetre_cranien,etat_nutritionnel,allaitement,alimentation,
                 dev_tient_tete,dev_assis,dev_marche,dev_parle,dev_proprete,dev_observations,
                 vaccins_jour,vaccins_administres,prochain_vaccin,examen_clinique,conduite,prochain_rdv,observations)
                VALUES (?,?,?,?,?,?,?,?,?, ?,?,?,?,?,?, ?,?,?,?,?,?,?)");
            $stmt->execute([$pid,$_SESSION['user_id']??null,$f('age_mois'),$f('poids'),$f('taille'),$f('perimetre_cranien'),
                $f('etat_nutritionnel'),$f('allaitement'),$f('alimentation'),
                $cb('dev_tient_tete'),$cb('dev_assis'),$cb('dev_marche'),$cb('dev_parle'),$cb('dev_proprete'),$f('dev_observations'),
                $cb('vaccins_jour'),$f('vaccins_administres'),$f('prochain_vaccin'),$f('examen_clinique'),$f('conduite'),$f('prochain_rdv'),$f('observations')]);
            $id = (int)$this->db->lastInsertId();
            $this->logPatient('pmi_suivi_enfant',$id,$pid,'Suivi enfant enregistré'.($f('poids')?' — poids '.$f('poids').' kg':''));
            header('Location: '.BASE_URL.'pmi/enfant/voir/'.$id); exit;
        } catch (Exception $e) { error_log('[PMI enfant] '.$e->getMessage()); header('Location: '.BASE_URL.'pmi/enfant/'.$pid.'?error=1'); exit; }
    }
    public function enfantVoir($id) {
        $this->guard();
        $stmt = $this->db->prepare("SELECT s.*,p.nom,p.prenom,p.dossier_numero,p.date_naissance,p.sexe,u.nom AS s_nom,u.prenom AS s_prenom FROM pmi_suivi_enfant s JOIN patients p ON p.id=s.patient_id LEFT JOIN users u ON u.id=s.soignant_id WHERE s.id=?");
        $stmt->execute([(int)$id]); $examen = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$examen) die("Suivi introuvable.");
        $type='enfant'; require_once __DIR__ . '/../views/pmi/voir.php';
    }

    // ── 3. AIDE À LA FAMILLE (atelier) ──────────────────────────────────────
    public function atelierForm() {
        $this->guard();
        require_once __DIR__ . '/../views/pmi/atelier_form.php';
    }
    public function atelierSave() {
        $this->guard();
        $f = fn($k)=>$this->postVal($k);
        $type = $this->postVal('type_atelier') ?: 'AUTRE';
        try {
            $stmt = $this->db->prepare("INSERT INTO pmi_atelier
                (animateur_id,patient_id,type_atelier,theme,participant_nom,nb_participants,contenu,conseils,observations)
                VALUES (?,?,?,?,?,?,?,?,?)");
            $stmt->execute([$_SESSION['user_id']??null,$f('patient_id'),$type,$f('theme'),$f('participant_nom'),
                $f('nb_participants') ?: 1,$f('contenu'),$f('conseils'),$f('observations')]);
            $id = (int)$this->db->lastInsertId();
            $this->audit->log('CREATE','pmi_atelier',$id,'Atelier / entretien famille : '.$type.($f('theme')?' — '.mb_substr($f('theme'),0,80):''),null,['type'=>$type,'theme'=>$f('theme')]);
            header('Location: '.BASE_URL.'pmi/atelier/voir/'.$id); exit;
        } catch (Exception $e) { error_log('[PMI atelier] '.$e->getMessage()); header('Location: '.BASE_URL.'pmi/atelier?error=1'); exit; }
    }
    public function atelierVoir($id) {
        $this->guard();
        $stmt = $this->db->prepare("SELECT a.*,u.nom AS s_nom,u.prenom AS s_prenom,p.nom,p.prenom,p.dossier_numero FROM pmi_atelier a LEFT JOIN users u ON u.id=a.animateur_id LEFT JOIN patients p ON p.id=a.patient_id WHERE a.id=?");
        $stmt->execute([(int)$id]); $examen = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$examen) die("Atelier introuvable.");
        $type='atelier'; require_once __DIR__ . '/../views/pmi/voir.php';
    }

    /* ── Audit avec nom patient ─────────────────────────────────────────────── */
    private function logPatient($table,$id,$patientId,$desc) {
        try {
            $p = (new Patient())->getById($patientId);
            $pNom = $p ? trim(($p['nom']??'').' '.($p['prenom']??'')) : '';
            $this->audit->log('CREATE',$table,$id, $desc.($pNom?" — $pNom (#$patientId)":''), null, ['patient'=>$pNom]);
        } catch (Exception $e) {}
    }
}
