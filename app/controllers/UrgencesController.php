<?php
/* ============================================================================
FICHIER : app/controllers/UrgencesController.php
CONTRÔLEUR DE GESTION DU SERVICE D'ACCUEIL ET DES URGENCES (SAU)
============================================================================ */

require_once __DIR__ . '/UnifiedController.php';
require_once __DIR__ . '/../services/AuditService.php';
require_once __DIR__ . '/../models/Patient.php';

class UrgencesController extends UnifiedController {
    private $db;
    private $patientModel;
    private $audit;

    // Service ID urgences (ajuster si différent dans votre base)
    const URGENCES_SERVICE_ID = 3;

    // Mapping niveau_triage (1-5) → couleur + label
    const COULEURS = ['1'=>'ROUGE','2'=>'ORANGE','3'=>'JAUNE','4'=>'VERT','5'=>'BLEU'];
    const LABELS   = ['1'=>'P1-VITAL','2'=>'P2-URGENT','3'=>'P3-STABLE','4'=>'P4-MINEUR','5'=>'P5-SURVEILLANCE'];

    public function __construct() {
        parent::__construct();
        $this->db = (new Database())->getConnection();
        $this->patientModel = new Patient();
        $this->audit = new AuditService();
    }

    // ─── DASHBOARD PRINCIPAL ─────────────────────────────────────────────────

    public function index() {
        $this->auth->requirePermission('urgences', 'read');

        $userRole = $_SESSION['user_role'] ?? '';

        // Récupération de tous les patients actifs aux urgences (ATTENTE ou EN_COURS)
        $sql = "SELECT ua.*,
                       p.nom, p.prenom, p.dossier_numero, p.sexe, p.date_naissance,
                       u.nom AS medecin_nom,
                       ut.niveau_gravite, ut.motif_plainte, ut.score_glasgow,
                       ut.tension_sys, ut.tension_dia, ut.pouls, ut.spo2, ut.temperature,
                       (SELECT COUNT(*) FROM demandes_laboratoire dl
                        WHERE dl.patient_id = p.id AND dl.statut = 'TERMINE') AS nb_bilans_dispo
                FROM urgences_patients ua
                JOIN patients p ON ua.patient_id = p.id
                LEFT JOIN users u ON ua.medecin_id = u.id
                LEFT JOIN urgences_triage ut ON ut.id = (
                    SELECT id FROM urgences_triage WHERE admission_id = ua.id ORDER BY id DESC LIMIT 1
                )
                WHERE ua.statut IN ('ATTENTE','EN_COURS')
                ORDER BY ua.niveau_triage ASC, ua.heure_arrivee ASC";

        $admissions = $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);

        // Sparklines (historique constantes pour graphiques flip-card)
        foreach ($admissions as &$adm) {
            $stmtV = $this->db->prepare(
                "SELECT temperature, tension_sys, pouls, date_mesure
                 FROM patient_parametres
                 WHERE patient_id = ?
                 ORDER BY date_mesure ASC LIMIT 10"
            );
            $stmtV->execute([$adm['patient_id']]);
            $adm['vitals_history'] = $stmtV->fetchAll(PDO::FETCH_ASSOC);

            // Calculer le label P1/P2/P3 à partir de niveau_triage
            $n = (int)($adm['niveau_triage'] ?? 3);
            $adm['niveau_label'] = self::LABELS[$n] ?? 'P3-STABLE';
            $adm['couleur']      = self::COULEURS[$n] ?? 'JAUNE';
        }
        unset($adm);

        // Stats de pilotage
        $stats = ['P1' => 0, 'P2' => 0, 'P3' => 0, 'waiting_med' => 0, 'total' => count($admissions)];
        foreach ($admissions as $a) {
            $n = (int)($a['niveau_triage'] ?? 3);
            if ($n == 1) $stats['P1']++;
            elseif ($n == 2) $stats['P2']++;
            else $stats['P3']++;
            if ($a['statut'] === 'ATTENTE') $stats['waiting_med']++;
        }

        // Patients décidés pour hospitalisation (statut HOSPITALISE)
        $stmtHosp = $this->db->query(
            "SELECT ua.*, p.nom, p.prenom FROM urgences_patients ua
             JOIN patients p ON ua.patient_id = p.id
             WHERE ua.statut = 'HOSPITALISE' ORDER BY ua.heure_arrivee DESC LIMIT 20"
        );
        $a_hospitaliser = $stmtHosp ? $stmtHosp->fetchAll(PDO::FETCH_ASSOC) : [];

        // Disponibilité des lits par service (lits → chambres → services)
        $stmtLitsGlobal = $this->db->query(
            "SELECT s.nom_service, COUNT(l.id) as total,
                    SUM(l.statut IN ('DISPONIBLE','LIBRE')) as libres,
                    SUM(l.statut = 'OCCUPE') as occupes
             FROM lits l
             JOIN chambres ch ON l.chambre_id = ch.id
             JOIN services s ON ch.service_id = s.id
             GROUP BY s.id, s.nom_service
             ORDER BY s.nom_service"
        );
        $lits_global = $stmtLitsGlobal ? $stmtLitsGlobal->fetchAll(PDO::FETCH_ASSOC) : [];

        // Services & Soins : patients hospitalisés groupés par service avec suivi soins du jour
        $stmtSrv = $this->db->query(
            "SELECT h.id as hosp_id, h.patient_id, p.nom, p.prenom, p.dossier_numero,
                    COALESCE(s.nom_service, 'Service non défini') as nom_service,
                    ch.nom_chambre, l.nom_lit,
                    CASE
                        WHEN h.medecin_responsable REGEXP '^[0-9]+$'
                        THEN (SELECT CONCAT(u2.prenom, ' ', u2.nom) FROM users u2 WHERE u2.id = h.medecin_responsable LIMIT 1)
                        ELSE h.medecin_responsable
                    END as medecin_resp,
                    COUNT(DISTINCT sh.id) as total_soins,
                    SUM(sh.statut = 'REALISE') as soins_faits
             FROM hospitalisations h
             JOIN patients p ON h.patient_id = p.id
             LEFT JOIN services s ON h.service_id = s.id
             LEFT JOIN lits l ON h.lit_id = l.id
             LEFT JOIN chambres ch ON l.chambre_id = ch.id
             LEFT JOIN soins_hospitalisation sh ON sh.admission_id = h.id AND DATE(sh.date_prevue) = CURDATE()
             WHERE h.statut = 'en_cours'
             GROUP BY h.id
             ORDER BY s.nom_service, p.nom"
        );
        $services_map = [];
        $patients_hospitalises_all = [];
        if ($stmtSrv) {
            $rows = $stmtSrv->fetchAll(PDO::FETCH_ASSOC);
            $patients_hospitalises_all = $rows;
            foreach ($rows as $row) {
                $services_map[$row['nom_service']][] = $row;
            }
        }

        // Tous les lits avec chambre, service et patient occupant
        $stmtLits = $this->db->query(
            "SELECT l.*, ch.nom_chambre, ch.type_chambre, s.nom_service,
                    p.nom as patient_nom, p.prenom as patient_prenom, p.dossier_numero,
                    h.date_admission
             FROM lits l
             JOIN chambres ch ON l.chambre_id = ch.id
             JOIN services s ON ch.service_id = s.id
             LEFT JOIN patients p ON l.occupied_by_patient_id = p.id
             LEFT JOIN hospitalisations h ON h.patient_id = p.id AND h.statut = 'en_cours'
             ORDER BY s.nom_service, ch.nom_chambre, l.nom_lit"
        );
        $lits_disponibles = [];
        $lits_carte = []; // [service][chambre] = [lits...]
        if ($stmtLits) {
            foreach ($stmtLits->fetchAll(PDO::FETCH_ASSOC) as $l) {
                if ($l['statut'] !== 'OCCUPE') $lits_disponibles[] = $l;
                $lits_carte[$l['nom_service']][$l['nom_chambre']][] = $l;
            }
        }

        if ($userRole === 'MEDECIN') {
            require_once __DIR__ . '/../views/urgences/dashboard_medecin.php';
        } else {
            require_once __DIR__ . '/../views/urgences/index.php';
        }
    }

    // ─── TRIAGE IAO ──────────────────────────────────────────────────────────

    public function triage($admission_id) {
        $this->auth->requirePermission('urgences', 'write');

        $stmt = $this->db->prepare(
            "SELECT ua.*, p.nom, p.prenom, p.date_naissance, p.sexe, p.dossier_numero
             FROM urgences_patients ua
             JOIN patients p ON ua.patient_id = p.id
             WHERE ua.id = ?"
        );
        $stmt->execute([$admission_id]);
        $adm = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$adm) {
            header('Location: ' . BASE_URL . 'urgences?error=not_found');
            exit;
        }

        // Données de triage existantes si retriage
        $triageExist = $this->db->prepare("SELECT * FROM urgences_triage WHERE admission_id = ? ORDER BY id DESC LIMIT 1");
        $triageExist->execute([$admission_id]);
        $triageData = $triageExist->fetch(PDO::FETCH_ASSOC) ?: [];

        $medecins = $this->db->query("SELECT id, nom, prenom FROM users WHERE role = 'MEDECIN' AND actif = 1")->fetchAll();

        require_once __DIR__ . '/../views/urgences/triage.php';
    }

    public function saveTriage() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;

        try {
            $this->db->beginTransaction();

            $admission_id    = (int)$_POST['admission_id'];
            $niveau_priorite = $_POST['niveau_priorite'] ?? 'P3-STABLE'; // P1-VITAL, P2-URGENT, P3-STABLE
            $medecin_id      = !empty($_POST['medecin_id']) ? (int)$_POST['medecin_id'] : null;

            // Convertir P1/P2/P3 → numérique et couleur
            $niveauMap = ['P1-VITAL' => '1', 'P2-URGENT' => '2', 'P3-STABLE' => '3', 'P4-MINEUR' => '4', 'P5-SURVEILLANCE' => '5'];
            $niveauNum  = $niveauMap[$niveau_priorite] ?? '3';
            $couleur    = self::COULEURS[$niveauNum] ?? 'JAUNE';

            // Niveau gravite pour urgences_triage (ENUM : 1-ROUGE / 2-ORANGE / 3-VERT)
            $graviteMap = ['1' => '1-ROUGE', '2' => '2-ORANGE', '3' => '3-VERT', '4' => '3-VERT', '5' => '3-VERT'];
            $niveau_gravite = $graviteMap[$niveauNum] ?? '3-VERT';

            // 1. Supprimer triage précédent pour INSERT propre
            $this->db->prepare("DELETE FROM urgences_triage WHERE admission_id = ?")
                     ->execute([$admission_id]);

            // 2. Insérer le triage
            $patient_id = $this->db->query("SELECT patient_id FROM urgences_patients WHERE id = $admission_id")->fetchColumn();
            $this->db->prepare(
                "INSERT INTO urgences_triage
                 (admission_id, patient_id, infirmier_id, score_glasgow, tension_sys, tension_dia, pouls, spo2, temperature, motif_plainte, niveau_gravite)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
            )->execute([
                $admission_id,
                $patient_id,
                $_SESSION['user_id'] ?? null,
                $_POST['gcs_total']  ?? null,
                $_POST['sys']        ?? null,
                $_POST['dia']        ?? null,
                $_POST['pouls']      ?? null,
                $_POST['spo2']       ?? null,
                $_POST['temp']       ?? null,
                $_POST['motif']      ?? null,
                $niveau_gravite,
            ]);

            // 3. Sauvegarder aussi dans patient_parametres (suivi unifié)
            if ($patient_id) {
                $this->db->prepare(
                    "INSERT INTO patient_parametres (patient_id, temperature, tension_sys, tension_dia, pouls, spo2, date_mesure)
                     VALUES (?, ?, ?, ?, ?, ?, NOW())"
                )->execute([
                    $patient_id,
                    $_POST['temp']  ?? null,
                    $_POST['sys']   ?? null,
                    $_POST['dia']   ?? null,
                    $_POST['pouls'] ?? null,
                    $_POST['spo2']  ?? null,
                ]);
            }

            // 4. Mise à jour admission
            $this->db->prepare(
                "UPDATE urgences_patients SET niveau_triage = ?, couleur_triage = ?, statut = 'EN_COURS', medecin_id = ?
                 WHERE id = ?"
            )->execute([$niveauNum, $couleur, $medecin_id, $admission_id]);

            $this->db->commit();
            $this->audit->logAction('UPDATE', 'urgences_patients', $admission_id, null, "Triage : $niveau_priorite");
            header('Location: ' . BASE_URL . 'urgences?success=tri_valide');

        } catch (Exception $e) {
            $this->db->rollBack();
            die("Erreur de triage : " . $e->getMessage());
        }
    }

    // ─── SURVEILLANCE / SUIVI CONSTANTES ─────────────────────────────────────

    public function surveillance($admission_id) {
        $this->auth->requirePermission('urgences', 'read');

        $stmt = $this->db->prepare(
            "SELECT ua.*, p.nom, p.prenom, p.date_naissance, p.sexe, p.dossier_numero,
                    ut.motif_plainte, ut.score_glasgow, ut.tension_sys, ut.tension_dia,
                    ut.pouls, ut.spo2, ut.temperature, ut.niveau_gravite
             FROM urgences_patients ua
             JOIN patients p ON ua.patient_id = p.id
             LEFT JOIN urgences_triage ut ON ut.id = (
                 SELECT id FROM urgences_triage WHERE admission_id = ua.id ORDER BY id DESC LIMIT 1
             )
             WHERE ua.id = ?"
        );
        $stmt->execute([$admission_id]);
        $adm = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$adm) { header('Location: ' . BASE_URL . 'urgences'); exit; }

        // Historique des constantes (patient_parametres)
        $stmtH = $this->db->prepare(
            "SELECT * FROM patient_parametres WHERE patient_id = ? ORDER BY date_mesure DESC LIMIT 20"
        );
        $stmtH->execute([$adm['patient_id']]);
        $historique = $stmtH->fetchAll(PDO::FETCH_ASSOC);

        // Bilans demandés (laboratoire)
        $stmtB = $this->db->prepare(
            "SELECT dl.id,
                    COALESCE(
                        (SELECT GROUP_CONCAT(el.nom SEPARATOR ', ')
                         FROM demande_examens de JOIN examens_laboratoire el ON el.id = de.examen_id
                         WHERE de.demande_id = dl.id),
                        'Bilan laboratoire'
                    ) AS examens_noms,
                    dl.statut, dl.date_creation,
                    u.nom AS demandeur_nom
             FROM demandes_laboratoire dl
             LEFT JOIN users u ON dl.medecin_id = u.id
             WHERE dl.patient_id = ?
             ORDER BY dl.date_creation DESC LIMIT 10"
        );
        $stmtB->execute([$adm['patient_id']]);
        $bilans = $stmtB->fetchAll(PDO::FETCH_ASSOC);

        // Liste des médecins pour re-attribution
        $medecins = $this->db->query("SELECT id, nom, prenom FROM users WHERE role = 'MEDECIN' AND actif = 1")->fetchAll();

        // Services pour transfert
        $services = $this->db->query("SELECT id, nom FROM services ORDER BY nom")->fetchAll();

        require_once __DIR__ . '/../views/urgences/surveillance.php';
    }

    public function saveParametres() {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['success' => false]); exit; }

        try {
            $patient_id = (int)$_POST['patient_id'];
            $this->db->prepare(
                "INSERT INTO patient_parametres
                 (patient_id, temperature, pression_arterielle_systolique, pression_arterielle_diastolique,
                  frequence_cardiaque, saturation_oxygene, frequence_respiratoire,
                  glycemie, diurese, sous_oxygene, debit_oxygene, observations, date_mesure)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())"
            )->execute([
                $patient_id,
                $_POST['temp']       ?: null,
                $_POST['sys']        ?: null,
                $_POST['dia']        ?: null,
                $_POST['pouls']      ?: null,
                $_POST['spo2']       ?: null,
                $_POST['freq_resp']  ?: null,
                $_POST['glycemie']   ?: null,
                $_POST['diurese']    ?: null,
                !empty($_POST['sous_oxygene']) ? 1 : 0,
                $_POST['debit_oxygene'] ?: null,
                $_POST['observations']  ?: null,
            ]);

            echo json_encode(['success' => true, 'time' => date('H:i')]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    // ─── TRANSFERT / SORTIE ───────────────────────────────────────────────────

    public function transferer() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;

        $admission_id  = (int)$_POST['admission_id'];
        $decision      = $_POST['decision'];            // HOSPITALISATION | SORTIE | CONSULTER
        $target_service = !empty($_POST['service_id']) ? (int)$_POST['service_id'] : null;
        $notes         = trim($_POST['notes'] ?? '');

        try {
            $this->db->beginTransaction();

            $newStatut = match($decision) {
                'HOSPITALISATION' => 'HOSPITALISE',
                default           => 'TERMINE',
            };

            $this->db->prepare("UPDATE urgences_patients SET statut = ?, heure_prise_charge = NOW() WHERE id = ?")
                     ->execute([$newStatut, $admission_id]);

            $patient_id = $this->db->query("SELECT patient_id FROM urgences_patients WHERE id = $admission_id")->fetchColumn();

            if ($decision === 'HOSPITALISATION') {
                $this->db->prepare(
                    "UPDATE patients SET statut_parcours = 'ATTENTE_HOSPITALISATION', service_id = ?, statut_hosp = 'A_HOSPITALISER' WHERE id = ?"
                )->execute([$target_service, $patient_id]);
            } elseif ($decision === 'CONSULTER') {
                // Rediriger vers consultation externe dans un autre service
                $this->db->prepare(
                    "UPDATE patients SET statut_parcours = 'PARAMETRES', date_mise_en_parametres = NOW(), service_id = ?, medecin_id = NULL WHERE id = ?"
                )->execute([$target_service, $patient_id]);
            } else {
                $this->db->prepare("UPDATE patients SET statut_parcours = 'SORTI', statut_hosp = 'AUCUN' WHERE id = ?")
                         ->execute([$patient_id]);
            }

            $this->db->commit();
            $this->audit->logAction('UPDATE', 'urgences_patients', $admission_id, null, "Orientation : $decision");
            header('Location: ' . BASE_URL . 'urgences?success=transfert');

        } catch (Exception $e) {
            $this->db->rollBack();
            die("Erreur transfert : " . $e->getMessage());
        }
    }

    // ─── ADMISSION MASSIVE (AFFLUX / PLAN BLANC) ──────────────────────────────

    public function nouvelleAdmission() {
        $this->auth->requirePermission('urgences', 'write');
        require_once __DIR__ . '/../views/urgences/admission_rapide.php';
    }

    public function saveMassive() {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;

        $patients_data = json_decode($_POST['patients'] ?? '[]', true);

        try {
            $this->db->beginTransaction();

            foreach ($patients_data as $p) {
                if (empty($p['nom']) && empty($p['is_inconnu'])) continue;

                $nom    = ($p['is_inconnu'] ?? false) ? 'X-INCONNU-' . strtoupper(substr(uniqid(), -5)) : strtoupper($p['nom']);
                $prenom = $p['prenom'] ?? 'X';
                $age    = (int)($p['age_approx'] ?? 30);
                $dob    = (date('Y') - $age) . '-01-01';

                $this->db->prepare(
                    "INSERT INTO patients (nom, prenom, sexe, date_naissance, statut_parcours, service_id, created_at)
                     VALUES (?, ?, ?, ?, 'URGENCES', ?, NOW())"
                )->execute([$nom, $prenom, $p['sexe'] ?? 'M', $dob, self::URGENCES_SERVICE_ID]);
                $patient_id = $this->db->lastInsertId();

                // P1→1, P2→2, P3→3
                $niveauStr = $p['priorite'] ?? 'P3';
                $niveauNum = (string)(int)substr($niveauStr, 1, 1);
                $couleur   = self::COULEURS[$niveauNum] ?? 'JAUNE';

                $this->db->prepare(
                    "INSERT INTO urgences_patients (patient_id, motif_admission, niveau_triage, couleur_triage, statut, heure_arrivee)
                     VALUES (?, ?, ?, ?, 'ATTENTE', NOW())"
                )->execute([$patient_id, $p['mode'] ?? 'Admission urgence', $niveauNum, $couleur]);
            }

            $this->db->commit();
            $this->audit->logAction('CREATE', 'urgences_massive', 0, null, 'Admission massive effectuée');
            echo json_encode(['success' => true, 'count' => count($patients_data)]);

        } catch (Exception $e) {
            $this->db->rollBack();
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    // ─── ADMISSION UNIQUE RAPIDE ──────────────────────────────────────────────

    public function saveSingle() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;

        try {
            $this->db->beginTransaction();

            $patient_id = !empty($_POST['patient_id']) ? (int)$_POST['patient_id'] : null;

            if (empty($patient_id)) {
                $this->db->prepare(
                    "INSERT INTO patients (nom, prenom, sexe, statut_parcours, service_id, created_at)
                     VALUES (?, ?, ?, 'URGENCES', ?, NOW())"
                )->execute([
                    strtoupper(trim($_POST['nom'] ?? 'INCONNU')),
                    trim($_POST['prenom'] ?? 'X'),
                    $_POST['sexe'] ?? 'M',
                    self::URGENCES_SERVICE_ID,
                ]);
                $patient_id = $this->db->lastInsertId();
            } else {
                // Mettre à jour le parcours du patient existant
                $this->db->prepare("UPDATE patients SET statut_parcours = 'URGENCES', service_id = ? WHERE id = ?")
                         ->execute([self::URGENCES_SERVICE_ID, $patient_id]);
            }

            $this->db->prepare(
                "INSERT INTO urgences_patients (patient_id, motif_admission, niveau_triage, couleur_triage, statut, heure_arrivee)
                 VALUES (?, ?, '3', 'JAUNE', 'ATTENTE', NOW())"
            )->execute([
                $patient_id,
                trim($_POST['motif'] ?? $_POST['mode_arrivee'] ?? 'Admission urgence'),
            ]);

            $this->db->commit();
            $this->audit->logAction('CREATE', 'urgences_patients', $this->db->lastInsertId(), null, 'Admission unique rapide');
            header('Location: ' . BASE_URL . 'urgences?success=admission_ok');

        } catch (Exception $e) {
            $this->db->rollBack();
            die("Erreur d'admission : " . $e->getMessage());
        }
    }
}
