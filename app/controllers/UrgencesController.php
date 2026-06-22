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
        // La secrétaire SAU a son propre cockpit — redirection immédiate
        if (($_SESSION['user_role'] ?? '') === 'SECRETAIRE_SAU') {
            header('Location: ' . BASE_URL . 'urgences/accueil-secretaire');
            exit;
        }

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

        // Sparklines + fallback vitaux depuis patient_parametres
        foreach ($admissions as &$adm) {
            $stmtV = $this->db->prepare(
                "SELECT temperature,
                        pression_arterielle_systolique AS tension_sys,
                        pression_arterielle_diastolique AS tension_dia,
                        frequence_cardiaque            AS pouls,
                        saturation_oxygene             AS spo2,
                        date_mesure
                 FROM patient_parametres
                 WHERE patient_id = ?
                 ORDER BY date_mesure DESC LIMIT 10"
            );
            $stmtV->execute([$adm['patient_id']]);
            $history = $stmtV->fetchAll(PDO::FETCH_ASSOC);
            $adm['vitals_history'] = array_reverse($history); // ASC pour les graphiques

            // ── Fallback : si pas de triage (patient venant du bureau paramètres),
            //    afficher les constantes issues de patient_parametres ─────────────
            if (!empty($history)) {
                $last = $history[0]; // plus récent (ORDER BY DESC)
                if (empty($adm['tension_sys']))  $adm['tension_sys']  = $last['tension_sys']  ?? null;
                if (empty($adm['tension_dia']))  $adm['tension_dia']  = $last['tension_dia']  ?? null;
                if (empty($adm['pouls']))        $adm['pouls']        = $last['pouls']        ?? null;
                if (empty($adm['spo2']))         $adm['spo2']         = $last['spo2']         ?? null;
                if (empty($adm['temperature']))  $adm['temperature']  = $last['temperature']  ?? null;
            }

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

        // Patients décidés pour hospitalisation :
        // - patients urgences (urgences_patients.statut = 'HOSPITALISE')
        // - patients externes hospitalisés vers urgences (patients.statut_hosp = 'A_HOSPITALISER' + service urgences)
        // Un patient est "à hospitaliser" si :
        // - son statut_hosp = 'A_HOSPITALISER'
        // - il n'a PAS encore de lit réellement assigné (lit_id IS NOT NULL dans hospitalisations)
        // NB : on exclut uniquement les hospitalisations AVEC lit pour éviter de bloquer
        //      quand seule une hospitalisation provisoire sans lit existe.
        $stmtHosp = $this->db->query(
            "SELECT ua.id, ua.patient_id,
                    CONVERT(ua.motif_admission   USING utf8mb4) AS motif_admission,
                    ua.heure_arrivee,
                    ua.niveau_triage,
                    CONVERT(p.nom            USING utf8mb4) AS nom,
                    CONVERT(p.prenom         USING utf8mb4) AS prenom,
                    CONVERT(p.dossier_numero USING utf8mb4) AS dossier_numero,
                    CONVERT('urgences'       USING utf8mb4) AS source
             FROM urgences_patients ua
             JOIN patients p ON ua.patient_id = p.id
             WHERE ua.statut = 'HOSPITALISE'
               AND p.statut_hosp = 'A_HOSPITALISER'
               AND ua.patient_id NOT IN (
                   SELECT patient_id FROM hospitalisations
                   WHERE statut = 'en_cours' AND lit_id IS NOT NULL
               )

             UNION

             SELECT NULL AS id, p.id AS patient_id,
                    CONVERT(COALESCE(pp.motif_consultation,'Hospitalisation') USING utf8mb4) AS motif_admission,
                    NOW() AS heure_arrivee,
                    '3' AS niveau_triage,
                    CONVERT(p.nom            USING utf8mb4) AS nom,
                    CONVERT(p.prenom         USING utf8mb4) AS prenom,
                    CONVERT(p.dossier_numero USING utf8mb4) AS dossier_numero,
                    CONVERT('externe'        USING utf8mb4) AS source
             FROM patients p
             LEFT JOIN patient_parametres pp ON pp.id = (SELECT MAX(id) FROM patient_parametres WHERE patient_id = p.id)
             JOIN services s ON p.service_id = s.id
             WHERE p.statut_hosp = 'A_HOSPITALISER'
               AND s.nom_service LIKE '%urgence%'
               AND p.id NOT IN (
                   SELECT patient_id FROM urgences_patients WHERE statut = 'HOSPITALISE'
               )
               AND p.id NOT IN (
                   SELECT patient_id FROM hospitalisations
                   WHERE statut = 'en_cours' AND lit_id IS NOT NULL
               )
             ORDER BY heure_arrivee DESC LIMIT 30"
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
        // (inclut la dernière évaluation de la douleur si la table existe)
        // Services cliniques pour le modal de transfert externe
        try {
            $servicesCliniques = $this->db->query(
                "SELECT id, nom_service FROM services
                  WHERE categorie = 'CLINIQUE'
                    AND nom_service NOT LIKE 'Paramètres%'
                    AND nom_service NOT LIKE 'Consultation%'
                  ORDER BY nom_service ASC"
            )->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            try {
                $servicesCliniques = $this->db->query(
                    "SELECT id, nom_service FROM services
                      WHERE nom_service IN ('Médecine Générale','Chirurgie','Urgences','Maternité','Pédiatrie','Ophtalmologie','ORL')
                      ORDER BY nom_service ASC"
                )->fetchAll(PDO::FETCH_ASSOC);
            } catch (Exception $e2) { $servicesCliniques = []; }
        }
        $urgServiceId = (int)($_SESSION['service_id'] ?? 0);

        $services_map = [];
        $patients_hospitalises_all = [];
        // Filtrer par service de l'infirmier connecté (si connu) — évite d'afficher
        // les patients des autres services (Médecine Générale, Chirurgie…) dans le cockpit Urgences
        $serviceFilter      = $urgServiceId > 0 ? "AND h.service_id = ?" : "";
        $serviceFilterParam = $urgServiceId > 0 ? [$urgServiceId]        : [];
        try {
            $stmtSrv = $this->db->prepare(
                "SELECT h.id as hosp_id, h.patient_id, h.service_id as hosp_service_id,
                        p.nom, p.prenom, p.dossier_numero,
                        COALESCE(s.nom_service, 'Service non défini') as nom_service,
                        ch.nom_chambre, l.nom_lit,
                        CASE
                            WHEN h.medecin_responsable REGEXP '^[0-9]+$'
                            THEN (SELECT CONCAT(u2.prenom, ' ', u2.nom) FROM users u2 WHERE u2.id = h.medecin_responsable LIMIT 1)
                            ELSE h.medecin_responsable
                        END as medecin_resp,
                        COUNT(DISTINCT sh.id) as total_soins,
                        SUM(UPPER(sh.statut) = 'REALISE') as soins_faits,
                        (SELECT ed.score     FROM evaluations_douleur ed WHERE ed.patient_id = h.patient_id ORDER BY ed.id DESC LIMIT 1) as douleur_score,
                        (SELECT ed.score_max FROM evaluations_douleur ed WHERE ed.patient_id = h.patient_id ORDER BY ed.id DESC LIMIT 1) as douleur_score_max,
                        (SELECT ed.echelle   FROM evaluations_douleur ed WHERE ed.patient_id = h.patient_id ORDER BY ed.id DESC LIMIT 1) as douleur_echelle,
                        (SELECT ed.severite  FROM evaluations_douleur ed WHERE ed.patient_id = h.patient_id ORDER BY ed.id DESC LIMIT 1) as douleur_severite
                 FROM hospitalisations h
                 JOIN patients p ON h.patient_id = p.id
                 LEFT JOIN services s ON h.service_id = s.id
                 LEFT JOIN lits l ON h.lit_id = l.id
                 LEFT JOIN chambres ch ON l.chambre_id = ch.id
                 LEFT JOIN soins_hospitalisation sh
                       ON sh.admission_id = h.id
                      AND UPPER(COALESCE(sh.statut,'')) NOT IN ('ANNULE','RAYE')
                 WHERE h.statut = 'en_cours'
                   $serviceFilter
                 GROUP BY h.id
                 ORDER BY s.nom_service, p.nom"
            );
            $stmtSrv->execute($serviceFilterParam);
            $rows = $stmtSrv->fetchAll(PDO::FETCH_ASSOC);
            $patients_hospitalises_all = $rows;
            foreach ($rows as $row) {
                $services_map[$row['nom_service']][] = $row;
            }
        } catch (Exception $e) {
            // Fallback sans évaluation de la douleur (table absente)
            try {
                $stmtSrv2 = $this->db->prepare(
                    "SELECT h.id as hosp_id, h.patient_id, h.service_id as hosp_service_id,
                            p.nom, p.prenom, p.dossier_numero,
                            COALESCE(s.nom_service, 'Service non défini') as nom_service,
                            ch.nom_chambre, l.nom_lit,
                            CASE
                                WHEN h.medecin_responsable REGEXP '^[0-9]+$'
                                THEN (SELECT CONCAT(u2.prenom, ' ', u2.nom) FROM users u2 WHERE u2.id = h.medecin_responsable LIMIT 1)
                                ELSE h.medecin_responsable
                            END as medecin_resp,
                            COUNT(DISTINCT sh.id) as total_soins,
                            SUM(UPPER(sh.statut) = 'REALISE') as soins_faits,
                            NULL as douleur_score, NULL as douleur_score_max,
                            NULL as douleur_echelle, NULL as douleur_severite
                     FROM hospitalisations h
                     JOIN patients p ON h.patient_id = p.id
                     LEFT JOIN services s ON h.service_id = s.id
                     LEFT JOIN lits l ON h.lit_id = l.id
                     LEFT JOIN chambres ch ON l.chambre_id = ch.id
                     LEFT JOIN soins_hospitalisation sh
                           ON sh.admission_id = h.id
                          AND UPPER(COALESCE(sh.statut,'')) NOT IN ('ANNULE','RAYE')
                     WHERE h.statut = 'en_cours'
                       $serviceFilter
                     GROUP BY h.id
                     ORDER BY s.nom_service, p.nom"
                );
                $stmtSrv2->execute($serviceFilterParam);
                $rows2 = $stmtSrv2->fetchAll(PDO::FETCH_ASSOC);
                $patients_hospitalises_all = $rows2;
                foreach ($rows2 as $row2) {
                    $services_map[$row2['nom_service']][] = $row2;
                }
            } catch (Exception $e2) {}
        }

        // Tous les lits avec chambre, service physique et service responsable du patient
        // patient_service_id / patient_service_nom permettent de détecter les lits "empruntés"
        $stmtLits = $this->db->query(
            "SELECT l.*, s.id as service_id, ch.nom_chambre, ch.type_chambre, s.nom_service,
                    p.nom as patient_nom, p.prenom as patient_prenom, p.dossier_numero,
                    h.date_admission,
                    h.service_id   AS patient_service_id,
                    s2.nom_service AS patient_service_nom
             FROM lits l
             JOIN chambres ch ON l.chambre_id = ch.id
             JOIN services s  ON ch.service_id = s.id
             LEFT JOIN patients p         ON l.occupied_by_patient_id = p.id
             LEFT JOIN hospitalisations h ON h.patient_id = p.id AND h.statut = 'en_cours'
             LEFT JOIN services s2        ON s2.id = h.service_id
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

        // ── Données supplémentaires pour le dashboard MÉDECIN uniquement ────────
        $patients_assignes     = [];
        $patients_hospitalises = [];
        $resultats_prets       = [];
        $mes_rdv               = [];

        if ($userRole === 'MEDECIN') {
            $userId    = $_SESSION['user_id']    ?? 0;
            $serviceId = $_SESSION['service_id'] ?? self::URGENCES_SERVICE_ID;

            // Salle d'Attente : patients assignés à CE médecin OU en file commune (medecin_id IS NULL)
            // Le premier médecin qui clique sur "Consulter" prend le patient via urgences/prendre-en-charge
            $stmtAss = $this->db->prepare(
                "SELECT p.*,
                        COALESCE(up.motif_admission, pp.motif_consultation, '') AS motif_plainte,
                        up.niveau_triage, up.couleur_triage,
                        pp.temperature, pp.pression_arterielle_systolique AS tension_sys,
                        pp.pression_arterielle_diastolique AS tension_dia,
                        pp.frequence_cardiaque AS pouls, pp.saturation_oxygene AS spo2
                 FROM patients p
                 LEFT JOIN patient_parametres pp
                       ON pp.id = (SELECT MAX(id) FROM patient_parametres WHERE patient_id = p.id)
                 LEFT JOIN urgences_patients up
                       ON up.id = (SELECT MAX(id) FROM urgences_patients WHERE patient_id = p.id AND statut = 'ATTENTE')
                 WHERE p.statut_parcours = 'ATTENTE_CONSULTATION'
                   AND p.actif = 1
                   AND p.service_id = ?
                   AND (p.medecin_id IS NULL OR p.medecin_id = ?)
                 ORDER BY up.niveau_triage ASC, p.numero_ordre ASC"
            );
            $stmtAss->execute([self::URGENCES_SERVICE_ID, $userId]);
            $patients_assignes = $stmtAss->fetchAll(PDO::FETCH_ASSOC);

            // Hospitalisés UNIQUEMENT dans le service du médecin connecté.
            // (avant : un OR sur medecin_responsable ramenait des patients d'autres
            //  services que le médecin avait vus aux urgences avant transfert —
            //  le médecin urgences voyait donc des patients hospitalisés en
            //  Médecine Générale, Chirurgie, etc. ce qui n'est pas souhaité.)
            $stmtH = $this->db->prepare(
                "SELECT p.*, h.date_admission, h.id AS hosp_id,
                        l.nom_lit, ch.nom_chambre, s.nom_service,
                        CASE
                            WHEN h.medecin_responsable REGEXP '^[0-9]+$'
                            THEN (SELECT CONCAT(u2.prenom,' ',u2.nom) FROM users u2 WHERE u2.id = h.medecin_responsable LIMIT 1)
                            ELSE h.medecin_responsable
                        END AS medecin_resp
                 FROM hospitalisations h
                 JOIN patients p  ON h.patient_id  = p.id
                 JOIN lits l      ON h.lit_id       = l.id
                 JOIN chambres ch ON l.chambre_id   = ch.id
                 JOIN services s  ON ch.service_id  = s.id
                 WHERE h.statut = 'en_cours'
                   AND h.service_id = ?
                 ORDER BY ch.nom_chambre, l.nom_lit"
            );
            $stmtH->execute([$serviceId]);
            $patients_hospitalises = $stmtH->fetchAll(PDO::FETCH_ASSOC);

            // Résultats labo validés (72h glissants)
            $resultats_prets = [];
            try {
                $stmtRP = $this->db->prepare("
                    SELECT prl.id, prl.demande_id, prl.nom_examen, prl.resultat,
                           prl.valeur_numerique, prl.unite,
                           prl.interpretation, prl.anormal, prl.date_resultat,
                           p.id AS patient_id, p.nom, p.prenom, p.dossier_numero
                    FROM patient_resultats_labo prl
                    JOIN patients p ON prl.patient_id = p.id
                    WHERE prl.medecin_prescripteur_id = ?
                      AND prl.date_resultat >= DATE_SUB(NOW(), INTERVAL 72 HOUR)
                    ORDER BY prl.anormal DESC, prl.date_resultat DESC
                    LIMIT 30
                ");
                $stmtRP->execute([$userId]);
                $resultats_prets = $stmtRP->fetchAll(PDO::FETCH_ASSOC);
            } catch (Exception $e) {
                // Fallback : demandes validées depuis demandes_laboratoire
                try {
                    $stmtRP2 = $this->db->prepare("
                        SELECT d.id AS demande_id, d.date_creation AS date_resultat, d.statut,
                               p.id AS patient_id, p.nom, p.prenom, p.dossier_numero,
                               GROUP_CONCAT(el.nom SEPARATOR ', ') AS nom_examen,
                               NULL AS resultat, NULL AS interpretation, 0 AS anormal
                        FROM demandes_laboratoire d
                        JOIN patients p             ON d.patient_id  = p.id
                        JOIN demande_examens de     ON de.demande_id = d.id
                        JOIN examens_laboratoire el ON de.examen_id  = el.id
                        WHERE d.medecin_id = ?
                          AND d.statut = 'VALIDES'
                          AND d.date_creation >= DATE_SUB(NOW(), INTERVAL 72 HOUR)
                        GROUP BY d.id
                        ORDER BY d.date_creation DESC
                        LIMIT 20
                    ");
                    $stmtRP2->execute([$userId]);
                    $resultats_prets = $stmtRP2->fetchAll(PDO::FETCH_ASSOC);
                } catch (Exception $e2) { /* silencieux */ }
            }

            // Bilans labo en cours (non encore validés)
            $stmtSB = $this->db->prepare("
                SELECT d.id, d.statut, d.date_creation,
                       p.nom AS patient_nom, p.prenom AS patient_prenom, p.dossier_numero,
                       GROUP_CONCAT(el.nom ORDER BY el.nom SEPARATOR ', ') AS label,
                       COUNT(de.id) AS nb_examens
                FROM demandes_laboratoire d
                JOIN patients p             ON d.patient_id  = p.id
                JOIN demande_examens de     ON de.demande_id = d.id
                JOIN examens_laboratoire el ON de.examen_id  = el.id
                WHERE d.medecin_id = ?
                  AND d.statut IN ('EN_ATTENTE','DISPATCHE','RESULTATS_SOUMIS',
                                   'PRELEVEMENTS_EFFECTUES','EN_ANALYSE')
                GROUP BY d.id
                ORDER BY d.date_creation DESC
                LIMIT 20
            ");
            $stmtSB->execute([$userId]);
            $suivi_bilans = $stmtSB->fetchAll(PDO::FETCH_ASSOC);

            // Résultats imagerie disponibles (interprétés/validés, 72h)
            $resultats_imagerie = [];
            try {
                $stmtIM = $this->db->prepare("
                    SELECT di.id, di.statut, di.date_creation,
                           COALESCE(di.type_examen, di.type_imagerie, 'Imagerie') AS type_examen,
                           COALESCE(di.partie_corps, di.partie_code, '') AS partie_corps,
                           p.nom, p.prenom, p.dossier_numero
                    FROM demandes_imagerie di
                    JOIN patients p ON di.patient_id = p.id
                    WHERE di.medecin_id = ?
                      AND di.statut IN ('interprete','valide','VALIDE','TERMINE')
                      AND di.date_creation >= DATE_SUB(NOW(), INTERVAL 72 HOUR)
                    ORDER BY di.date_creation DESC
                    LIMIT 10
                ");
                $stmtIM->execute([$userId]);
                $resultats_imagerie = $stmtIM->fetchAll(PDO::FETCH_ASSOC);
            } catch (Exception $e) { /* table absente */ }

            // Patients consultés — période configurable via GET
            $patients_consultes = [];
            $periodeConsultes   = 24; // valeur par défaut
            $periodesAutorisees = [24 => '24h', 48 => '48h', 72 => '72h',
                                   168 => '7 jours', 336 => '14 jours', 720 => '30 jours'];
            if (isset($_GET['periode_consultes']) &&
                array_key_exists((int)$_GET['periode_consultes'], $periodesAutorisees)) {
                $periodeConsultes = (int)$_GET['periode_consultes'];
            }
            try {
                // Une ligne par patient (dernière consultation uniquement) + compteur
                $stmtPC = $this->db->prepare("
                    SELECT c.id                          AS consultation_id,
                           c.patient_id,
                           c.date_consultation,
                           COALESCE(c.motif_consultation, '') AS motif,
                           p.nom, p.prenom, p.dossier_numero, p.date_naissance, p.sexe,
                           COALESCE(p.statut_hosp, '')  AS statut_hosp,
                           COALESCE(h.statut, '')        AS statut_hosp_actuel,
                           grp.nb_consultations
                    FROM consultations c
                    JOIN patients p ON c.patient_id = p.id
                    LEFT JOIN hospitalisations h
                           ON h.patient_id = p.id AND h.statut = 'en_cours'
                    JOIN (
                        SELECT patient_id,
                               MAX(id)    AS max_id,
                               COUNT(*)   AS nb_consultations
                        FROM consultations
                        WHERE medecin_id = ?
                          AND date_consultation >= DATE_SUB(NOW(), INTERVAL ? HOUR)
                        GROUP BY patient_id
                    ) grp ON grp.patient_id = c.patient_id AND grp.max_id = c.id
                    WHERE c.medecin_id = ?
                      AND c.date_consultation >= DATE_SUB(NOW(), INTERVAL ? HOUR)
                    ORDER BY c.date_consultation DESC
                    LIMIT 100
                ");
                $stmtPC->execute([$userId, $periodeConsultes, $userId, $periodeConsultes]);
                $patients_consultes = $stmtPC->fetchAll(PDO::FETCH_ASSOC);
            } catch (Exception $e) {
                error_log('[UrgencesController] patients_consultes query error: ' . $e->getMessage());
            }

            // ── Compte-rendus d'hospitalisation en attente ──────────────────────
            $patients_crh_pending = [];
            try {
                // S'assurer que la table existe (migration idempotente)
                $this->db->exec("CREATE TABLE IF NOT EXISTS `comptes_rendus_hosp` (
                    `id`               INT NOT NULL AUTO_INCREMENT,
                    `patient_id`       INT NOT NULL,
                    `hospitalisation_id` INT DEFAULT NULL,
                    `medecin_id`       INT NOT NULL,
                    `date_entree`      DATE DEFAULT NULL,
                    `diag_entree`      TEXT DEFAULT NULL,
                    `evolution`        LONGTEXT DEFAULT NULL,
                    `date_sortie`      DATE DEFAULT NULL,
                    `diag_sortie`      TEXT DEFAULT NULL,
                    `traitement_sortie` TEXT DEFAULT NULL,
                    `rendez_vous`      VARCHAR(255) DEFAULT NULL,
                    `date_signature`   DATE DEFAULT NULL,
                    `signe`            TINYINT(1) NOT NULL DEFAULT 0,
                    `signature_data`   MEDIUMTEXT DEFAULT NULL,
                    `created_at`       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (`id`),
                    KEY `idx_crh_patient` (`patient_id`),
                    KEY `idx_crh_medecin` (`medecin_id`),
                    KEY `idx_crh_hospitalisation` (`hospitalisation_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

                $stmtCrh = $this->db->prepare("
                    SELECT DISTINCT h.id            AS hosp_id,
                           h.patient_id,
                           h.date_admission,
                           h.date_sortie_effective,
                           COALESCE(h.motif_hospitalisation,'') AS motif_hospitalisation,
                           COALESCE(h.diagnostic_sortie,'')    AS diagnostic_sortie,
                           p.nom, p.prenom, p.dossier_numero, p.date_naissance, p.sexe,
                           COALESCE(s.nom_service,'')          AS nom_service
                    FROM hospitalisations h
                    JOIN patients p ON h.patient_id = p.id
                    LEFT JOIN services s ON h.service_id = s.id
                    WHERE h.statut = 'termine'
                      AND NOT EXISTS (
                          SELECT 1 FROM comptes_rendus_hosp crh
                          WHERE crh.hospitalisation_id = h.id
                      )
                      AND (
                          h.medecin_responsable = ?
                          OR h.patient_id IN (
                              SELECT patient_id FROM consultations
                              WHERE medecin_id = ?
                                AND date_consultation >= DATE_SUB(NOW(), INTERVAL 720 HOUR)
                          )
                      )
                    ORDER BY COALESCE(h.date_sortie_effective, h.date_admission) DESC
                    LIMIT 50
                ");
                $stmtCrh->execute([$userId, $userId]);
                $patients_crh_pending = $stmtCrh->fetchAll(PDO::FETCH_ASSOC);
            } catch (Exception $e) {
                error_log('[UrgencesController] patients_crh_pending query error: ' . $e->getMessage());
                $patients_crh_pending = [];
            }

            // ── Dossiers partagés ──────────────────────────────────────────────
            $dossiers_recus   = [];
            $dossiers_envoyes = [];
            $nb_partages_non_lus = 0;
            try {
                // Reçus (pour moi, encore actifs)
                $stmtDR = $this->db->prepare("
                    SELECT pd.id AS partage_id, pd.patient_id, pd.avis_medecin,
                           pd.date_partage, pd.date_expiration, pd.statut,
                           p.nom, p.prenom, p.dossier_numero,
                           p.date_naissance, p.sexe,
                           u.nom AS exp_nom, u.prenom AS exp_prenom,
                           s.nom_service
                    FROM partages_dossiers pd
                    JOIN patients p ON pd.patient_id = p.id
                    JOIN users u ON pd.expediteur_id = u.id
                    LEFT JOIN services s ON pd.service_id = s.id
                    WHERE pd.destinataire_id = ?
                      AND pd.date_expiration > NOW()
                    ORDER BY pd.statut ASC, pd.date_partage DESC
                    LIMIT 50
                ");
                $stmtDR->execute([$userId]);
                $dossiers_recus = $stmtDR->fetchAll(PDO::FETCH_ASSOC);
                $nb_partages_non_lus = count(array_filter($dossiers_recus, fn($d) => $d['statut'] === 'en_attente'));

                // Envoyés (par moi, encore actifs)
                $stmtDE = $this->db->prepare("
                    SELECT pd.id AS partage_id, pd.patient_id, pd.avis_medecin,
                           pd.date_partage, pd.date_expiration, pd.statut,
                           p.nom, p.prenom, p.dossier_numero,
                           u.nom AS dest_nom, u.prenom AS dest_prenom,
                           s.nom_service
                    FROM partages_dossiers pd
                    JOIN patients p ON pd.patient_id = p.id
                    JOIN users u ON pd.destinataire_id = u.id
                    LEFT JOIN services s ON pd.service_id = s.id
                    WHERE pd.expediteur_id = ?
                      AND pd.date_expiration > NOW()
                    ORDER BY pd.date_partage DESC
                    LIMIT 50
                ");
                $stmtDE->execute([$userId]);
                $dossiers_envoyes = $stmtDE->fetchAll(PDO::FETCH_ASSOC);
            } catch (Exception $e) {
                error_log('[UrgencesController] dossiers_partages query error: ' . $e->getMessage());
            }

            // ── Liste des médecins pour le formulaire de partage ──────────────
            $medecins_liste = [];
            try {
                $stmtML = $this->db->prepare("
                    SELECT u.id, u.nom, u.prenom, s.nom_service
                    FROM users u
                    LEFT JOIN services s ON u.service_id = s.id
                    WHERE u.role = 'MEDECIN' AND u.id != ?
                    ORDER BY u.nom, u.prenom
                ");
                $stmtML->execute([$userId]);
                $medecins_liste = $stmtML->fetchAll(PDO::FETCH_ASSOC);
            } catch (Exception $e) { /* silencieux */ }

            require_once __DIR__ . '/../views/urgences/dashboard_medecin.php';
        } else {
            // ── File d'attente consultation infirmière ──────────────────────────
            // Patients qui attendent une consultation infirmière :
            // présents aux urgences (urgences_patients ATTENTE/EN_COURS)
            // et dont le statut_parcours = 'ATTENTE_CONSULTATION' (paramètres déjà pris)
            // et sans consultation infirmière déjà en cours / terminée aujourd'hui
            $file_consultation_infirmier = [];
            try {
                $stmtFCI = $this->db->prepare("
                    SELECT p.id AS patient_id, p.nom, p.prenom, p.dossier_numero,
                           p.date_naissance, p.sexe, p.statut_parcours,
                           up.id         AS urgence_id,
                           up.heure_arrivee,
                           up.niveau_triage,
                           up.couleur_triage,
                           up.motif_admission,
                           pp.temperature,
                           pp.pression_arterielle_systolique  AS tension_sys,
                           pp.pression_arterielle_diastolique AS tension_dia,
                           pp.frequence_cardiaque             AS pouls,
                           pp.saturation_oxygene              AS spo2
                    FROM urgences_patients up
                    JOIN patients p ON p.id = up.patient_id
                    LEFT JOIN patient_parametres pp
                           ON pp.id = (SELECT MAX(id) FROM patient_parametres WHERE patient_id = p.id)
                    WHERE up.statut IN ('ATTENTE','EN_COURS')
                      AND p.statut_parcours = 'ATTENTE_CONSULTATION'
                      AND NOT EXISTS (
                          SELECT 1 FROM consultations c
                          WHERE c.patient_id    = p.id
                            AND c.type_consultation = 'infirmiere'
                            AND c.date_consultation >= CURDATE()
                      )
                    ORDER BY up.niveau_triage ASC, up.heure_arrivee ASC
                    LIMIT 100
                ");
                $stmtFCI->execute();
                $file_consultation_infirmier = $stmtFCI->fetchAll(PDO::FETCH_ASSOC);
            } catch (Exception $e) {
                error_log('[UrgencesController] file_consultation_infirmier : ' . $e->getMessage());
            }

            // ── Mes consultations infirmières (72h glissantes) ──────────────────
            $mes_consultations = [];
            $userId_inf = (int)($_SESSION['user_id'] ?? 0);
            if ($userId_inf > 0) {
                try {
                    $stmtMC = $this->db->prepare("
                        SELECT c.id AS consultation_id, c.date_consultation, c.motif_consultation, c.devenir,
                               c.infirmier_id, p.id AS patient_id, p.nom, p.prenom,
                               p.date_naissance, p.sexe, p.dossier_numero, p.statut_parcours,
                               COALESCE(h.id, NULL)         AS hosp_id,
                               COALESCE(h.statut, '')       AS hosp_statut,
                               COALESCE(l.nom_lit, '')      AS nom_lit,
                               COALESCE(ch.nom_chambre, '') AS nom_chambre
                        FROM consultations c
                        JOIN patients p ON p.id = c.patient_id
                        LEFT JOIN hospitalisations h ON h.patient_id = p.id AND h.statut = 'en_cours'
                        LEFT JOIN lits l     ON l.id  = h.lit_id
                        LEFT JOIN chambres ch ON ch.id = l.chambre_id
                        WHERE c.type_consultation = 'infirmiere'
                          AND (c.infirmier_id = ? OR c.medecin_id = ?)
                          AND c.date_consultation >= DATE_SUB(NOW(), INTERVAL 72 HOUR)
                        ORDER BY c.date_consultation DESC
                        LIMIT 100
                    ");
                    $stmtMC->execute([$userId_inf, $userId_inf]);
                    $mes_consultations = $stmtMC->fetchAll(PDO::FETCH_ASSOC);
                } catch (Exception $e) {
                    error_log('[UrgencesController] mes_consultations : ' . $e->getMessage());
                }
            }

            // ── Médecins (pour la sélection dans le modal d'hospitalisation) ────
            $medecins = [];
            try {
                $medecins = $this->db->query(
                    "SELECT id, nom, prenom FROM users WHERE role = 'MEDECIN' AND actif = 1 ORDER BY nom"
                )->fetchAll(PDO::FETCH_ASSOC);
            } catch (Exception $e) { $medecins = []; }

            // ── Soins à faire (C) — tous les soins planifiés non réalisés ────────
            $soins_a_faire = [];
            try {
                $soins_a_faire = $this->db->query("
                    SELECT sh.id, sh.type_soin, sh.description, sh.date_prevue,
                           sh.statut, sh.condition_application,
                           p.nom, p.prenom, p.dossier_numero, p.id AS patient_id,
                           s.nom_service,
                           ch.nom_chambre, l.nom_lit,
                           h.id AS hosp_id
                    FROM soins_hospitalisation sh
                    JOIN hospitalisations h  ON h.id = sh.admission_id AND h.statut = 'en_cours'
                    JOIN patients p          ON p.id = h.patient_id
                    LEFT JOIN services s     ON s.id = h.service_id
                    LEFT JOIN lits l         ON l.id = h.lit_id
                    LEFT JOIN chambres ch    ON ch.id = l.chambre_id
                    WHERE sh.statut NOT IN ('REALISE','realise','ANNULE','annule')
                      AND sh.supprime_par IS NULL
                    ORDER BY sh.date_prevue ASC NULLS LAST, sh.created_at ASC
                    LIMIT 80
                ")->fetchAll(PDO::FETCH_ASSOC);
            } catch (Exception $e) {
                // Fallback sans NULLS LAST (MySQL < 8)
                try {
                    $soins_a_faire = $this->db->query("
                        SELECT sh.id, sh.type_soin, sh.description, sh.date_prevue,
                               sh.statut, sh.condition_application,
                               p.nom, p.prenom, p.dossier_numero, p.id AS patient_id,
                               s.nom_service,
                               ch.nom_chambre, l.nom_lit,
                               h.id AS hosp_id
                        FROM soins_hospitalisation sh
                        JOIN hospitalisations h  ON h.id = sh.admission_id AND h.statut = 'en_cours'
                        JOIN patients p          ON p.id = h.patient_id
                        LEFT JOIN services s     ON s.id = h.service_id
                        LEFT JOIN lits l         ON l.id = h.lit_id
                        LEFT JOIN chambres ch    ON ch.id = l.chambre_id
                        WHERE sh.statut NOT IN ('REALISE','realise','ANNULE','annule')
                          AND sh.supprime_par IS NULL
                        ORDER BY ISNULL(sh.date_prevue), sh.date_prevue ASC, sh.created_at ASC
                        LIMIT 80
                    ")->fetchAll(PDO::FETCH_ASSOC);
                } catch (Exception $e2) { $soins_a_faire = []; }
            }

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
        $services = $this->db->query("SELECT id, nom_service AS nom FROM services ORDER BY nom_service")->fetchAll();

        require_once __DIR__ . '/../views/urgences/surveillance.php';
    }

    public function saveParametres() {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['success' => false]); exit; }

        try {
            $this->db->beginTransaction();

            $patient_id = (int)$_POST['patient_id'];
            $motif      = trim($_POST['observations'] ?? $_POST['motif'] ?? 'Prise de paramètres urgences');
            $niveau     = in_array((int)($_POST['niveau_triage'] ?? 3), [1,2,3,4,5])
                          ? (int)$_POST['niveau_triage'] : 3;
            $couleur    = ['1'=>'ROUGE','2'=>'ORANGE','3'=>'JAUNE','4'=>'VERT','5'=>'BLEU'][$niveau] ?? 'JAUNE';

            // 1. Sauvegarder les constantes
            $this->db->prepare(
                "INSERT INTO patient_parametres
                 (patient_id, user_id, temperature,
                  pression_arterielle_systolique, pression_arterielle_diastolique,
                  frequence_cardiaque, saturation_oxygene, frequence_respiratoire,
                  glycemie, diurese, sous_oxygene, debit_oxygene, observations, date_mesure)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())"
            )->execute([
                $patient_id,
                $_SESSION['user_id'] ?? null,
                $_POST['temp']          ?: null,
                $_POST['sys']           ?: null,
                $_POST['dia']           ?: null,
                $_POST['pouls']         ?: null,
                $_POST['spo2']          ?: null,
                $_POST['freq_resp']     ?: null,
                $_POST['glycemie']      ?: null,
                $_POST['diurese']       ?: null,
                !empty($_POST['sous_oxygene']) ? 1 : 0,
                $_POST['debit_oxygene'] ?: null,
                $motif,
            ]);

            // 2. Mettre le patient en ATTENTE_CONSULTATION (file médecin + file infirmier)
            $this->db->prepare(
                "UPDATE patients
                 SET statut_parcours = 'ATTENTE_CONSULTATION',
                     service_id      = ?
                 WHERE id = ?
                   AND statut_parcours NOT IN ('HOSPITALISE','EN_CONSULTATION','SORTI')"
            )->execute([self::URGENCES_SERVICE_ID, $patient_id]);

            // 3. Créer l'entrée urgences_patients si elle n'existe pas encore
            $stmtChk = $this->db->prepare(
                "SELECT id FROM urgences_patients
                 WHERE patient_id = ? AND statut IN ('ATTENTE','EN_COURS')
                 LIMIT 1"
            );
            $stmtChk->execute([$patient_id]);
            if (!$stmtChk->fetchColumn()) {
                $this->db->prepare(
                    "INSERT INTO urgences_patients
                     (patient_id, motif_admission, niveau_triage, couleur_triage, statut, heure_arrivee)
                     VALUES (?, ?, ?, ?, 'ATTENTE', NOW())"
                )->execute([$patient_id, $motif, $niveau, $couleur]);
            } else {
                // Mettre à jour le niveau de triage si l'admission existe déjà
                $this->db->prepare(
                    "UPDATE urgences_patients
                     SET niveau_triage = ?, couleur_triage = ?
                     WHERE patient_id = ? AND statut IN ('ATTENTE','EN_COURS')
                     ORDER BY id DESC LIMIT 1"
                )->execute([$niveau, $couleur, $patient_id]);
            }

            $this->db->commit();
            echo json_encode(['success' => true, 'time' => date('H:i')]);

        } catch (Exception $e) {
            $this->db->rollBack();
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
                    "UPDATE patients SET statut_parcours = 'HOSPITALISE', service_id = ?, statut_hosp = 'A_HOSPITALISER' WHERE id = ?"
                )->execute([$target_service, $patient_id]);
            } elseif ($decision === 'CONSULTER') {
                // Rediriger vers consultation externe dans un autre service
                $this->db->prepare(
                    "UPDATE patients SET statut_parcours = 'PARAMETRES', service_id = ?, medecin_id = NULL, date_mise_en_parametres = NOW() WHERE id = ?"
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
                $nomS    = strtoupper(trim($_POST['nom'] ?? 'INCONNU'));
                $prenomS = trim($_POST['prenom'] ?? 'X');

                // ── Vérification doublons (sauf si force ou patient inconnu) ──
                $forceS = !empty($_POST['force_creation']);
                if (!$forceS && $nomS !== 'INCONNU' && strlen($nomS) >= 2) {
                    require_once __DIR__ . '/../services/DoublonPatientService.php';
                    $svcS   = new DoublonPatientService($this->db);
                    $chkS   = $svcS->verifier($nomS, $prenomS, null, null);
                    if ($chkS['has_warning']) {
                        $this->db->rollBack();
                        header('Content-Type: application/json');
                        echo json_encode([
                            'success'      => false,
                            'doublon'      => true,
                            'score_max'    => $chkS['score_max'],
                            'has_critical' => $chkS['has_critical'],
                            'doublons'     => DoublonPatientService::formaterPourApi($chkS)['doublons'],
                            'message'      => 'Patient similaire trouvé. Confirmez ou utilisez un patient existant.',
                        ]);
                        exit;
                    }
                }

                $this->db->prepare(
                    "INSERT INTO patients (nom, prenom, sexe, statut_parcours, service_id, created_at)
                     VALUES (?, ?, ?, 'ATTENTE_CONSULTATION', ?, NOW())"
                )->execute([
                    $nomS,
                    $prenomS,
                    $_POST['sexe'] ?? 'M',
                    self::URGENCES_SERVICE_ID,
                ]);
                $patient_id = $this->db->lastInsertId();
            } else {
                // Mettre à jour le parcours du patient existant
                $this->db->prepare("UPDATE patients SET statut_parcours = 'ATTENTE_CONSULTATION', service_id = ? WHERE id = ?")
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

    // ─── COCKPIT SECRÉTAIRE DES URGENCES ─────────────────────────────────────

    public function accueilSecretaire() {
        // Accès : rôles autorisés explicitement + admin
        if (!$this->auth->isLoggedIn()) {
            header('Location: ' . BASE_URL . 'login'); exit;
        }
        $role = $_SESSION['user_role'] ?? '';
        $rolesAutorises = ['SECRETAIRE_SAU', 'SECRETAIRE', 'SECRETAIRE_URGENCES', 'ADMIN', 'ADMINISTRATEUR'];
        if (!in_array($role, $rolesAutorises)) {
            http_response_code(403);
            $error_cause = "Ce cockpit d'accueil est réservé à la secrétaire du Service d'Accueil des Urgences (SAU).";
            $error_details = ['Profils autorisés' => implode(', ', $rolesAutorises)];
            require __DIR__ . '/../views/errors/403.php';
            exit;
        }

        // Liste des médecins urgentistes actifs
        $medecins = $this->db->query(
            "SELECT u.id, u.nom, u.prenom, u.service_id,
                    COALESCE(s.nom_service,'Urgences') AS nom_service
             FROM users u
             LEFT JOIN services s ON u.service_id = s.id
             WHERE u.role = 'MEDECIN' AND u.actif = 1
             ORDER BY u.nom, u.prenom"
        )->fetchAll(PDO::FETCH_ASSOC);

        // File d'attente du jour (patients en attente / en cours)
        $patients_attente = $this->db->query(
            "SELECT up.id AS admission_id, up.patient_id, up.motif_admission,
                    up.niveau_triage, up.couleur_triage, up.statut, up.heure_arrivee,
                    p.nom, p.prenom, p.dossier_numero, p.sexe, p.date_naissance,
                    p.type_client,
                    u.nom AS medecin_nom, u.prenom AS medecin_prenom,
                    ut.motif_plainte, ut.tension_sys, ut.tension_dia, ut.pouls, ut.spo2, ut.temperature
             FROM urgences_patients up
             JOIN patients p ON up.patient_id = p.id
             LEFT JOIN users u ON up.medecin_id = u.id
             LEFT JOIN urgences_triage ut ON ut.id = (
                 SELECT id FROM urgences_triage WHERE admission_id = up.id ORDER BY id DESC LIMIT 1
             )
             WHERE up.statut IN ('ATTENTE','EN_COURS')
             ORDER BY up.niveau_triage ASC, up.heure_arrivee ASC"
        )->fetchAll(PDO::FETCH_ASSOC);

        // Patients du jour (tous statuts)
        $patients_jour = $this->db->query(
            "SELECT up.id AS admission_id, up.patient_id, up.motif_admission,
                    up.niveau_triage, up.couleur_triage, up.statut, up.heure_arrivee,
                    up.heure_prise_charge,
                    p.nom, p.prenom, p.dossier_numero, p.sexe, p.date_naissance,
                    p.type_client,
                    u.nom AS medecin_nom, u.prenom AS medecin_prenom
             FROM urgences_patients up
             JOIN patients p ON up.patient_id = p.id
             LEFT JOIN users u ON up.medecin_id = u.id
             WHERE DATE(up.heure_arrivee) = CURDATE()
             ORDER BY up.heure_arrivee DESC"
        )->fetchAll(PDO::FETCH_ASSOC);

        // Carte globale des lits (tous services)
        $lits_carte = [];
        try {
            $stmtL = $this->db->query(
                "SELECT l.id AS lit_id, l.nom_lit, l.statut AS lit_statut,
                        ch.id AS chambre_id, ch.nom_chambre, ch.type_chambre,
                        s.id AS service_id, s.nom_service,
                        p.id AS patient_id, p.nom AS patient_nom, p.prenom AS patient_prenom,
                        p.dossier_numero,
                        h.id AS hosp_id, h.date_admission
                 FROM lits l
                 JOIN chambres ch ON l.chambre_id = ch.id
                 JOIN services s  ON ch.service_id = s.id
                 LEFT JOIN patients p ON l.occupied_by_patient_id = p.id
                 LEFT JOIN hospitalisations h ON h.patient_id = p.id AND h.statut = 'en_cours'
                 ORDER BY s.nom_service, ch.nom_chambre, l.nom_lit"
            );
            foreach ($stmtL->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $lits_carte[$row['nom_service']][$row['nom_chambre']][] = $row;
            }
        } catch (Exception $e) {
            error_log('[UrgencesController] lits_carte error: ' . $e->getMessage());
        }

        // Stats rapides
        $nb_attente     = count(array_filter($patients_attente, fn($p) => $p['statut'] === 'ATTENTE'));
        $nb_en_cours    = count(array_filter($patients_attente, fn($p) => $p['statut'] === 'EN_COURS'));
        $nb_total_jour  = count($patients_jour);

        // Services cliniques pour la sélection transfert
        $services = [];
        try {
            $services = $this->db->query(
                "SELECT id, nom_service FROM services
                 WHERE actif = 1 OR actif IS NULL
                 ORDER BY nom_service"
            )->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $services = $this->db->query(
                "SELECT id, nom_service FROM services ORDER BY nom_service"
            )->fetchAll(PDO::FETCH_ASSOC);
        }

        require_once __DIR__ . '/../views/urgences/accueil_secretaire.php';
    }

    /**
     * Enregistre un patient aux urgences et l'assigne à un médecin — AJAX JSON
     */
    public function enregistrerPatientUrgences() {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Méthode invalide']); exit;
        }

        try {
            $this->db->beginTransaction();

            $patient_id  = !empty($_POST['patient_id']) ? (int)$_POST['patient_id'] : null;
            $medecin_id  = !empty($_POST['medecin_id']) ? (int)$_POST['medecin_id'] : null;
            $motif       = trim($_POST['motif'] ?? '');
            $niveau      = in_array((int)($_POST['niveau_triage'] ?? 3), [1,2,3,4,5])
                           ? (int)$_POST['niveau_triage'] : 3;
            $couleur     = self::COULEURS[$niveau] ?? 'JAUNE';

            // ── Créer le patient si nouveau ────────────────────────────────
            if (empty($patient_id)) {
                $nom    = strtoupper(trim($_POST['nom']    ?? ''));
                $prenom = trim($_POST['prenom'] ?? '');
                if (empty($nom)) {
                    $this->db->rollBack();
                    echo json_encode(['success' => false, 'message' => 'Le nom est obligatoire']); exit;
                }

                // ── Vérification intelligente des doublons ─────────────────
                $forceCreation = !empty($_POST['force_creation']);
                if (!$forceCreation) {
                    require_once __DIR__ . '/../services/DoublonPatientService.php';
                    $ddnChk = !empty($_POST['date_naissance']) ? $_POST['date_naissance']
                            : (!empty($_POST['age_estimatif'])
                               ? (date('Y') - (int)$_POST['age_estimatif']) . '-01-01' : null);
                    $telChk = trim($_POST['telephone'] ?? '') ?: null;

                    $svcChk = new DoublonPatientService($this->db);
                    $chkRes = $svcChk->verifier($nom, $prenom, $ddnChk, $telChk);

                    if ($chkRes['has_warning']) {
                        $this->db->rollBack();
                        echo json_encode([
                            'success'      => false,
                            'doublon'      => true,
                            'score_max'    => $chkRes['score_max'],
                            'has_critical' => $chkRes['has_critical'],
                            'doublons'     => DoublonPatientService::formaterPourApi($chkRes)['doublons'],
                            'message'      => $chkRes['has_critical']
                                ? 'Un patient identique existe déjà. Utilisez "Patient connu" ou confirmez la création.'
                                : 'Des patients similaires ont été trouvés. Vérifiez avant de créer.',
                        ]);
                        exit;
                    }
                }

                // Numéro dossier unique
                $annee2  = date('y');
                $prefix  = "HSJM$annee2";
                $stmtMax = $this->db->prepare(
                    "SELECT COALESCE(MAX(CAST(SUBSTRING(dossier_numero, 7) AS UNSIGNED)), 0)
                     FROM patients WHERE dossier_numero LIKE ? FOR UPDATE"
                );
                $stmtMax->execute(["$prefix%"]);
                $next    = (int)$stmtMax->fetchColumn() + 1;
                $dossier = $prefix . str_pad($next, 4, '0', STR_PAD_LEFT);

                $dob = !empty($_POST['date_naissance']) ? $_POST['date_naissance'] : '1900-01-01';
                if (empty($_POST['date_naissance']) && !empty($_POST['age_estimatif'])) {
                    $dob = (date('Y') - (int)$_POST['age_estimatif']) . '-01-01';
                }

                // Type client et circuit depuis le formulaire
                $allowedTypes = ['PAYANT_COMPTANT','BON_PRISE_EN_CHARGE','ASSURANCE','FAMILLE_PHP','AGENTS_PHP'];
                $typeClient   = in_array($_POST['type_client'] ?? '', $allowedTypes)
                                ? $_POST['type_client'] : 'PAYANT_COMPTANT';
                // Seul AGENTS_PHP va au bureau PHP ; FAMILLE_PHP suit la parité B1/B2
                $circuit      = ($typeClient === 'AGENTS_PHP') ? 'PHP' : 'STANDARD';
                $nomAssurance = ($typeClient === 'ASSURANCE')         ? trim($_POST['nom_assurance'] ?? '') ?: null : null;
                $numAssure    = ($typeClient === 'ASSURANCE')         ? trim($_POST['numero_assure'] ?? '') ?: null : null;
                $numeroBon    = ($typeClient === 'BON_PRISE_EN_CHARGE') ? trim($_POST['numero_bon'] ?? '') ?: null : null;
                $orgPayeur    = in_array($typeClient, ['ASSURANCE','BON_PRISE_EN_CHARGE'])
                                ? trim($_POST['organisme_payeur'] ?? '') ?: null : null;

                $this->db->prepare(
                    "INSERT INTO patients
                     (dossier_numero, nom, prenom, sexe, date_naissance, telephone,
                      type_client, circuit, nom_assurance, numero_assure, numero_bon, organisme_payeur,
                      statut_parcours, service_id, created_at)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'ATTENTE_CONSULTATION', ?, NOW())"
                )->execute([
                    $dossier,
                    $nom,
                    $prenom,
                    $_POST['sexe'] ?? 'M',
                    $dob,
                    trim($_POST['telephone'] ?? ''),
                    $typeClient,
                    $circuit,
                    $nomAssurance,
                    $numAssure,
                    $numeroBon,
                    $orgPayeur,
                    self::URGENCES_SERVICE_ID,
                ]);
                $patient_id = $this->db->lastInsertId();
            } else {
                // Mettre à jour le circuit du patient existant
                $this->db->prepare(
                    "UPDATE patients SET statut_parcours = 'ATTENTE_CONSULTATION',
                     service_id = ?, medecin_id = ? WHERE id = ?"
                )->execute([self::URGENCES_SERVICE_ID, $medecin_id, $patient_id]);
            }

            // ── Créer l'admission urgences ─────────────────────────────────
            $this->db->prepare(
                "INSERT INTO urgences_patients
                 (patient_id, medecin_id, motif_admission, niveau_triage, couleur_triage, statut, heure_arrivee)
                 VALUES (?, ?, ?, ?, ?, 'ATTENTE', NOW())"
            )->execute([$patient_id, $medecin_id, $motif, $niveau, $couleur]);
            $admission_id = $this->db->lastInsertId();

            // Mettre à jour medecin_id sur le patient
            // NULL = file commune visible par tous les médecins urgences
            $this->db->prepare(
                "UPDATE patients SET medecin_id = ? WHERE id = ?"
            )->execute([$medecin_id, $patient_id]);

            $this->db->commit();
            $this->audit->logAction('CREATE', 'urgences_patients', $admission_id, null,
                "Enregistrement secrétaire urgences — motif: $motif");

            // Récupérer les données pour la réponse
            $stmt = $this->db->prepare(
                "SELECT up.*, p.nom, p.prenom, p.dossier_numero, p.sexe, p.date_naissance,
                        u.nom AS medecin_nom, u.prenom AS medecin_prenom
                 FROM urgences_patients up
                 JOIN patients p ON up.patient_id = p.id
                 LEFT JOIN users u ON up.medecin_id = u.id
                 WHERE up.id = ?"
            );
            $stmt->execute([$admission_id]);
            $adm = $stmt->fetch(PDO::FETCH_ASSOC);

            $medecinNom = trim(($adm['medecin_prenom'] ?? '') . ' ' . ($adm['medecin_nom'] ?? ''));
            echo json_encode([
                'success'      => true,
                'admission_id' => $admission_id,
                'patient_id'   => $patient_id,
                'dossier'      => $adm['dossier_numero'] ?? '',
                'nom'          => $adm['nom'] ?? '',
                'prenom'       => $adm['prenom'] ?? '',
                'medecin_nom'  => $medecinNom,
                'message'      => $medecinNom
                    ? "Patient enregistré et envoyé à Dr $medecinNom"
                    : 'Patient ajouté en file commune — tous les médecins disponibles ont été notifiés',
            ]);

        } catch (Exception $e) {
            $this->db->rollBack();
            error_log('[UrgencesController::enregistrerPatientUrgences] ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    /**
     * Claim atomique d'un patient en file commune (premier médecin arrivé).
     * GET /urgences/prendre-en-charge/{patient_id}
     * - Si medecin_id IS NULL → on l'assigne au médecin connecté et on redirige vers la consultation
     * - Si déjà pris → message d'erreur et retour au dashboard
     */
    public function prendreEnCharge(int $patientId): void {
        $medecinId = (int)($_SESSION['user_id'] ?? 0);
        if (!$medecinId) { header('Location: ' . BASE_URL . 'login'); exit; }

        try {
            // Tentative atomique : on ne met à jour que si le patient est encore en file commune
            // ou déjà assigné à ce médecin (idempotent)
            $stmt = $this->db->prepare(
                "UPDATE patients SET medecin_id = ?
                  WHERE id = ?
                    AND statut_parcours = 'ATTENTE_CONSULTATION'
                    AND (medecin_id IS NULL OR medecin_id = ?)"
            );
            $stmt->execute([$medecinId, $patientId, $medecinId]);

            if ($stmt->rowCount() === 0) {
                // Un autre médecin a déjà pris ce patient
                header('Location: ' . BASE_URL . 'urgences?erreur=patient_deja_pris');
                exit;
            }

            // Mettre aussi à jour urgences_patients
            $this->db->prepare(
                "UPDATE urgences_patients SET medecin_id = ?
                  WHERE patient_id = ? AND statut = 'ATTENTE' AND (medecin_id IS NULL OR medecin_id = ?)"
            )->execute([$medecinId, $patientId, $medecinId]);

        } catch (Exception $e) {
            error_log('[prendreEnCharge] ' . $e->getMessage());
            header('Location: ' . BASE_URL . 'urgences?erreur=technique');
            exit;
        }

        // Rediriger vers le formulaire de consultation
        header('Location: ' . BASE_URL . 'consultation/formulaire?patient_id=' . $patientId . '&type=EXTERNE&etape=1');
        exit;
    }

    /**
     * Transfert inter-lits (secrétaire urgences) — délègue à HospitalisationController
     * POST: patient_id, service_id, lit_id, motif
     */
    public function transfererLit() {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Méthode invalide']); exit;
        }

        $patient_id = (int)($_POST['patient_id'] ?? 0);
        if (!$patient_id) {
            echo json_encode(['success' => false, 'message' => 'patient_id manquant']); exit;
        }

        try {
            // Récupérer l'hospitalisation en cours
            $stmt = $this->db->prepare(
                "SELECT h.id AS hosp_id, h.lit_id, h.service_id
                 FROM hospitalisations h
                 WHERE h.patient_id = ? AND h.statut = 'en_cours'
                 ORDER BY h.id DESC LIMIT 1"
            );
            $stmt->execute([$patient_id]);
            $hosp = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$hosp) {
                echo json_encode(['success' => false, 'message' => 'Aucune hospitalisation active pour ce patient']); exit;
            }

            $new_service_id = (int)($_POST['service_id'] ?? 0);
            $new_lit_id     = !empty($_POST['lit_id']) ? (int)$_POST['lit_id'] : null;
            $motif          = trim($_POST['motif'] ?? 'Transfert inter-lits');

            $this->db->beginTransaction();

            // 1. Libérer l'ancien lit
            if ($hosp['lit_id']) {
                $this->db->prepare(
                    "UPDATE lits SET statut = 'DISPONIBLE', occupied_by_patient_id = NULL WHERE id = ?"
                )->execute([$hosp['lit_id']]);
            }

            // 2. Clôturer l'ancienne hospitalisation
            $this->db->prepare(
                "UPDATE hospitalisations SET statut = 'transfere', date_sortie_effective = NOW() WHERE id = ?"
            )->execute([$hosp['hosp_id']]);

            // 3. Occuper le nouveau lit
            if ($new_lit_id) {
                $this->db->prepare(
                    "UPDATE lits SET statut = 'OCCUPE', occupied_by_patient_id = ? WHERE id = ?"
                )->execute([$patient_id, $new_lit_id]);
            }

            // 4. Créer la nouvelle hospitalisation
            $this->db->prepare(
                "INSERT INTO hospitalisations
                 (patient_id, service_id, lit_id, date_admission, statut, motif_admission, medecin_responsable)
                 SELECT ?, ?, ?, NOW(), 'en_cours', ?, medecin_responsable FROM hospitalisations WHERE id = ?"
            )->execute([$patient_id, $new_service_id, $new_lit_id, $motif, $hosp['hosp_id']]);
            $new_hosp_id = $this->db->lastInsertId();

            // 5. Enregistrer dans transferts_patients
            try {
                $this->db->prepare(
                    "INSERT INTO transferts_patients
                     (patient_id, hospitalisation_id, service_depart_id, service_arrivee_id,
                      lit_depart_id, lit_arrivee_id, motif, date_transfert, user_id)
                     VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), ?)"
                )->execute([
                    $patient_id, $new_hosp_id,
                    $hosp['service_id'], $new_service_id,
                    $hosp['lit_id'],     $new_lit_id,
                    $motif,
                    $_SESSION['user_id'] ?? null,
                ]);
            } catch (Exception $e2) { /* table optionnelle */ }

            // 6. Mettre à jour service_id du patient
            $this->db->prepare(
                "UPDATE patients SET service_id = ? WHERE id = ?"
            )->execute([$new_service_id, $patient_id]);

            $this->db->commit();
            $this->audit->logAction('UPDATE', 'hospitalisations', $new_hosp_id, null,
                "Transfert lit par secrétaire urgences — motif: $motif");

            echo json_encode(['success' => true, 'message' => 'Transfert effectué avec succès']);

        } catch (Exception $e) {
            $this->db->rollBack();
            error_log('[UrgencesController::transfererLit] ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    // ─── LIBÉRER LE LIT (depuis le dashboard infirmier urgences) ──────────────
    /**
     * Valider l'installation d'un patient en attente d'hospitalisation dans un lit
     * POST /urgences/valider-hospitalisation
     * Params: patient_id, lit_id, motif
     */
    public function validerHospitalisation() {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
            exit;
        }

        $patient_id = (int)($_POST['patient_id'] ?? 0);
        $lit_id     = (int)($_POST['lit_id']     ?? 0);
        $motif      = trim($_POST['motif'] ?? 'Hospitalisation depuis urgences');

        if (!$patient_id || !$lit_id) {
            echo json_encode(['success' => false, 'message' => 'Patient et lit requis.']);
            exit;
        }

        try {
            // Vérifier si une hospitalisation existe déjà pour ce patient
            $chk = $this->db->prepare(
                "SELECT id, lit_id FROM hospitalisations WHERE patient_id = ? AND statut = 'en_cours' LIMIT 1"
            );
            $chk->execute([$patient_id]);
            $existingHosp = $chk->fetch(PDO::FETCH_ASSOC);

            // Bloquer uniquement si le patient a DÉJÀ un lit assigné
            if ($existingHosp && $existingHosp['lit_id'] !== null) {
                echo json_encode(['success' => false, 'message' => 'Ce patient est déjà installé dans un lit.']);
                exit;
            }

            // Vérifier disponibilité du lit
            $stmtLit = $this->db->prepare(
                "SELECT l.id, l.statut, c.service_id
                 FROM lits l LEFT JOIN chambres c ON l.chambre_id = c.id
                 WHERE l.id = ? LIMIT 1"
            );
            $stmtLit->execute([$lit_id]);
            $lit = $stmtLit->fetch(PDO::FETCH_ASSOC);
            if (!$lit) {
                echo json_encode(['success' => false, 'message' => 'Lit introuvable.']);
                exit;
            }
            if (!in_array(strtoupper(trim($lit['statut'])), ['DISPONIBLE', 'LIBRE', 'AVAILABLE', 'FREE'])) {
                echo json_encode(['success' => false, 'message' => 'Ce lit n\'est plus disponible.']);
                exit;
            }

            $service_id = (int)($lit['service_id'] ?? self::URGENCES_SERVICE_ID);

            $this->db->beginTransaction();

            // 1. Créer OU mettre à jour l'hospitalisation
            if ($existingHosp) {
                // Hospitalisation provisoire (lit_id=NULL) → on l'assigne au lit choisi
                $this->db->prepare(
                    "UPDATE hospitalisations
                        SET lit_id = ?, service_id = ?, motif_hospitalisation = ?
                      WHERE id = ?"
                )->execute([$lit_id, $service_id, $motif, $existingHosp['id']]);
            } else {
                $this->db->prepare(
                    "INSERT INTO hospitalisations
                        (patient_id, service_id, lit_id, motif_hospitalisation, date_admission, statut)
                     VALUES (?, ?, ?, ?, NOW(), 'en_cours')"
                )->execute([$patient_id, $service_id, $lit_id, $motif]);
            }

            // 2. Occuper le lit
            $this->db->prepare(
                "UPDATE lits SET statut = 'OCCUPE', occupied_by_patient_id = ? WHERE id = ?"
            )->execute([$patient_id, $lit_id]);

            // 3. Mettre à jour le patient
            $this->db->prepare(
                "UPDATE patients SET statut = 'HOSPITALISE', statut_hosp = 'HOSPITALISE', service_id = ? WHERE id = ?"
            )->execute([$service_id, $patient_id]);

            // 4. Si le patient venait d'urgences_patients, marquer comme terminé (installé dans le service)
            $this->db->prepare(
                "UPDATE urgences_patients SET statut = 'TERMINE' WHERE patient_id = ? AND statut = 'HOSPITALISE'"
            )->execute([$patient_id]);

            $this->db->commit();
            echo json_encode(['success' => true, 'message' => 'Patient installé avec succès.']);

        } catch (Exception $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    public function libererLit() {
        header('Content-Type: application/json');
        $hosp_id = (int)($_POST['hosp_id'] ?? 0);

        if (!$hosp_id) {
            echo json_encode(['success' => false, 'message' => 'ID hospitalisation manquant.']);
            exit;
        }

        try {
            // 1. Récupérer le lit associé
            $stmt = $this->db->prepare("SELECT lit_id, patient_id FROM hospitalisations WHERE id = ?");
            $stmt->execute([$hosp_id]);
            $hosp = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$hosp) {
                echo json_encode(['success' => false, 'message' => 'Hospitalisation introuvable.']);
                exit;
            }

            $this->db->beginTransaction();

            // 2. Clôturer l'hospitalisation
            $this->db->prepare(
                "UPDATE hospitalisations SET statut = 'termine', date_sortie_effective = NOW() WHERE id = ?"
            )->execute([$hosp_id]);

            // 3. Libérer le lit
            if ($hosp['lit_id']) {
                $this->db->prepare(
                    "UPDATE lits SET statut = 'DISPONIBLE', occupied_by_patient_id = NULL WHERE id = ?"
                )->execute([$hosp['lit_id']]);
            }

            // 4. Mettre à jour le statut du patient
            if ($hosp['patient_id']) {
                $this->db->prepare(
                    "UPDATE patients SET statut_parcours = 'SORTI', statut_hosp = 'SORTIE_RECENTE' WHERE id = ?"
                )->execute([$hosp['patient_id']]);

                // 5. Clôturer l'entrée urgences_patients pour éviter toute réapparition dans "À Hospitaliser"
                $this->db->prepare(
                    "UPDATE urgences_patients
                        SET statut = 'TERMINE', heure_prise_charge = COALESCE(heure_prise_charge, NOW())
                      WHERE patient_id = ? AND statut = 'HOSPITALISE'"
                )->execute([$hosp['patient_id']]);
            }

            $this->db->commit();
            echo json_encode(['success' => true]);

        } catch (Exception $e) {
            $this->db->rollBack();
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    // ─── PARTAGE DOSSIER — AJAX ─────────────────────────────────────────────
    public function partagerDossierAjax(): void {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Méthode invalide']); exit;
        }
        $this->auth->requirePermission('patients', 'write');

        $patient_id      = (int)($_POST['patient_id']      ?? 0);
        $destinataire_id = (int)($_POST['destinataire_id'] ?? 0);
        $service_id      = (int)($_POST['service_id']      ?? 0);
        $avis            = trim($_POST['avis_medecin']      ?? '');
        $expediteur_id   = (int)($_SESSION['user_id']       ?? 0);

        if (!$patient_id || !$destinataire_id || !$expediteur_id) {
            echo json_encode(['success' => false, 'message' => 'Données manquantes']); exit;
        }

        try {
            // Récupérer le service du destinataire si non fourni
            if (!$service_id) {
                $stmtS = $this->db->prepare("SELECT service_id FROM users WHERE id = ?");
                $stmtS->execute([$destinataire_id]);
                $service_id = (int)($stmtS->fetchColumn() ?: 0);
            }

            $date_exp = date('Y-m-d H:i:s', strtotime('+24 hours'));
            $stmt = $this->db->prepare("
                INSERT INTO partages_dossiers
                    (patient_id, expediteur_id, destinataire_id, service_id, avis_medecin, date_expiration)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$patient_id, $expediteur_id, $destinataire_id, $service_id, $avis ?: null, $date_exp]);

            // Nom du patient pour la réponse
            $stmtP = $this->db->prepare("SELECT nom, prenom, dossier_numero FROM patients WHERE id = ?");
            $stmtP->execute([$patient_id]);
            $pat = $stmtP->fetch(PDO::FETCH_ASSOC);

            echo json_encode([
                'success' => true,
                'message' => 'Dossier partagé avec succès',
                'patient' => $pat,
            ]);
        } catch (Exception $e) {
            error_log('[UrgencesController] partagerDossierAjax: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Erreur lors du partage']);
        }
        exit;
    }

    // ─── MARQUER PARTAGE VU — AJAX ──────────────────────────────────────────
    public function marquerPartageVu(int $partage_id): void {
        header('Content-Type: application/json');
        $userId = (int)($_SESSION['user_id'] ?? 0);
        try {
            $stmt = $this->db->prepare("
                UPDATE partages_dossiers SET statut = 'vu'
                WHERE id = ? AND destinataire_id = ?
            ");
            $stmt->execute([$partage_id, $userId]);
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            echo json_encode(['success' => false]);
        }
        exit;
    }

    // ─── RÉADMISSION PATIENT CONNU ───────────────────────────────────────────

    /**
     * AJAX GET — Rechercher un patient déjà enregistré pour le réadmettre aux urgences
     * Route : urgences/rechercher-patient-connu?q=...
     */
    public function rechercherPatientConnu(): void {
        header('Content-Type: application/json');
        $q = trim($_GET['q'] ?? '');
        if (mb_strlen($q) < 2) {
            echo json_encode(['results' => []]);
            exit;
        }
        $like = '%' . $q . '%';
        try {
            $stmt = $this->db->prepare("
                SELECT p.id, p.nom, p.prenom, p.dossier_numero, p.sexe, p.date_naissance,
                       p.telephone, p.statut_parcours,
                       TIMESTAMPDIFF(YEAR, p.date_naissance, CURDATE()) AS age,
                       (SELECT COUNT(*) FROM consultations       WHERE patient_id = p.id) AS nb_consultations,
                       (SELECT COUNT(*) FROM hospitalisations    WHERE patient_id = p.id) AS nb_hospitalisations,
                       (SELECT date_consultation
                          FROM consultations WHERE patient_id = p.id
                          ORDER BY date_consultation DESC LIMIT 1)      AS derniere_consultation,
                       (SELECT COALESCE(date_sortie_effective, date_admission)
                          FROM hospitalisations WHERE patient_id = p.id
                          ORDER BY id DESC LIMIT 1)                     AS dernier_passage_hosp
                FROM patients p
                WHERE (p.nom LIKE ? OR p.prenom LIKE ? OR p.dossier_numero LIKE ? OR p.telephone LIKE ?)
                  AND p.statut_parcours NOT IN ('ATTENTE_CONSULTATION','EN_CONSULTATION','HOSPITALISE')
                ORDER BY p.nom, p.prenom
                LIMIT 20
            ");
            $stmt->execute([$like, $like, $like, $like]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['results' => $results]);
        } catch (Exception $e) {
            echo json_encode(['results' => [], 'error' => $e->getMessage()]);
        }
        exit;
    }

    /**
     * AJAX GET — Récupérer l'historique complet d'un patient (pour Phase 2 du modal)
     * Route : urgences/historique-patient/{id}
     */
    public function historiquePatient(int $patient_id): void {
        header('Content-Type: application/json');
        try {
            // Fiche patient
            $stmtP = $this->db->prepare("
                SELECT p.*, TIMESTAMPDIFF(YEAR, p.date_naissance, CURDATE()) AS age
                FROM patients p WHERE p.id = ?
            ");
            $stmtP->execute([$patient_id]);
            $patient = $stmtP->fetch(PDO::FETCH_ASSOC);
            if (!$patient) throw new Exception('Patient introuvable');

            // 5 dernières consultations
            $stmtC = $this->db->prepare("
                SELECT c.date_consultation,
                       COALESCE(c.motif_consultation,'—') AS motif,
                       COALESCE(c.diagnostic_principal,'—') AS diagnostic,
                       COALESCE(u.nom,'Médecin inconnu') AS medecin_nom
                FROM consultations c
                LEFT JOIN users u ON c.medecin_id = u.id
                WHERE c.patient_id = ?
                ORDER BY c.date_consultation DESC LIMIT 5
            ");
            $stmtC->execute([$patient_id]);
            $consultations = $stmtC->fetchAll(PDO::FETCH_ASSOC);

            // 3 dernières hospitalisations
            $stmtH = $this->db->prepare("
                SELECT h.date_admission,
                       h.date_sortie_effective,
                       COALESCE(s.nom_service,'Service inconnu') AS service,
                       COALESCE(h.motif_hospitalisation,'—') AS motif,
                       h.statut
                FROM hospitalisations h
                LEFT JOIN services s ON h.service_id = s.id
                WHERE h.patient_id = ?
                ORDER BY h.date_admission DESC LIMIT 3
            ");
            $stmtH->execute([$patient_id]);
            $hospitalisations = $stmtH->fetchAll(PDO::FETCH_ASSOC);

            // Antécédents importants (allergies, VIH, ATCD chroniques)
            $stmtA = $this->db->prepare("
                SELECT type, description FROM antecedents
                WHERE patient_id = ?
                ORDER BY id DESC LIMIT 6
            ");
            $stmtA->execute([$patient_id]);
            $antecedents = $stmtA->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode([
                'success'          => true,
                'patient'          => $patient,
                'consultations'    => $consultations,
                'hospitalisations' => $hospitalisations,
                'antecedents'      => $antecedents,
            ]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    /**
     * AJAX POST — Réadmettre un patient connu aux urgences
     * Route : urgences/readmettre-patient
     */
    public function readmettrePatient(): void {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
            exit;
        }
        try {
            $patient_id = (int)($_POST['patient_id'] ?? 0);
            $motif      = trim($_POST['motif']    ?? 'Consultation urgences — retour patient');
            $niveau     = in_array((int)($_POST['niveau_triage'] ?? 3), [1,2,3,4,5])
                          ? (int)$_POST['niveau_triage'] : 3;
            $couleur    = self::COULEURS[$niveau] ?? 'JAUNE';

            if (!$patient_id) throw new Exception('ID patient manquant.');

            // Vérifier qu'il n'a pas déjà une admission active
            $chk = $this->db->prepare(
                "SELECT id FROM urgences_patients
                  WHERE patient_id = ? AND statut IN ('ATTENTE','EN_COURS') LIMIT 1"
            );
            $chk->execute([$patient_id]);
            if ($chk->fetchColumn()) {
                throw new Exception('Ce patient est déjà en salle d\'attente des urgences.');
            }

            $this->db->beginTransaction();

            // 1. Remettre le patient en file urgences
            $this->db->prepare(
                "UPDATE patients
                    SET statut_parcours = 'ATTENTE_CONSULTATION',
                        service_id      = ?
                  WHERE id = ?"
            )->execute([self::URGENCES_SERVICE_ID, $patient_id]);

            // 2. Créer l'admission urgences
            $this->db->prepare(
                "INSERT INTO urgences_patients
                    (patient_id, motif_admission, niveau_triage, couleur_triage, statut, heure_arrivee)
                 VALUES (?, ?, ?, ?, 'ATTENTE', NOW())"
            )->execute([$patient_id, $motif, $niveau, $couleur]);
            $admission_id = (int)$this->db->lastInsertId();

            $this->db->commit();
            $this->audit->logAction(
                'CREATE', 'urgences_patients', $admission_id, null,
                'Réadmission patient connu — ' . $motif
            );

            // Récupérer les infos du patient pour la réponse
            $patInfo = $this->db->prepare("SELECT nom, prenom, dossier_numero FROM patients WHERE id = ?")->execute([$patient_id]);
            $pat = $this->db->query("SELECT nom, prenom, dossier_numero FROM patients WHERE id = $patient_id")->fetch(PDO::FETCH_ASSOC);

            echo json_encode([
                'success'      => true,
                'admission_id' => $admission_id,
                'patient'      => $pat,
                'message'      => 'Patient réadmis avec succès aux urgences.',
            ]);

        } catch (Exception $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }
}
