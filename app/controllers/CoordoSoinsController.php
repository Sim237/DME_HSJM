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
 * CoordoSoinsController.php
 * Contrôle des activités infirmières — TOUS SERVICES
 * Rôle : COORDONNATEUR_SOINS (+ ADMIN, DIRECTEUR, MEDECIN_CHEF)
 * ============================================================================ */

require_once __DIR__ . '/../../config/database.php';

class CoordoSoinsController
{
    private PDO $db;

    private const ROLES_AUTORISES = [
        'COORDONNATEUR_SOINS','ADMIN','ADMINISTRATEUR','DIRECTEUR','MEDECIN_CHEF'
    ];

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $this->db = (new Database())->getConnection();
    }

    private function guard(): void
    {
        if (defined('GARDE_ACTIVE') && GARDE_ACTIVE) return;
        $role = strtoupper($_SESSION['user_role'] ?? '');
        if (!in_array($role, self::ROLES_AUTORISES)) {
            http_response_code(403);
            die("<div style='text-align:center;padding:80px;font-family:sans-serif;'>
                 <h2>Accès refusé</h2>
                 <p>Réservé au coordonnateur des soins.</p>
                 <a href='javascript:history.back()'>Retour</a></div>");
        }
    }

    // =========================================================================
    // DASHBOARD PRINCIPAL
    // =========================================================================
    public function dashboard(): void
    {
        $this->guard();

        // ── Auto-tag soins en retard (global, tous services) ─────────────────
        try {
            $this->db->exec("
                UPDATE soins_hospitalisation sh
                JOIN hospitalisations h ON sh.admission_id = h.id
                SET sh.statut = 'RETARD'
                WHERE sh.statut = 'PLANIFIE'
                  AND sh.date_prevue < NOW()
            ");
        } catch (PDOException $e) {}

        // ── Filtres GET ───────────────────────────────────────────────────────
        $filtreService = (int)($_GET['service_id'] ?? 0);
        $filtreDate    = $_GET['date'] ?? date('Y-m-d');
        $filtreDate    = preg_match('/^\d{4}-\d{2}-\d{2}$/', $filtreDate) ? $filtreDate : date('Y-m-d');

        // ── Liste des services hospitaliers (avec chambres) uniquement ───────
        $services = $this->db->query(
            "SELECT DISTINCT s.id, s.nom_service
             FROM services s
             INNER JOIN chambres c ON c.service_id = s.id
             ORDER BY s.nom_service"
        )->fetchAll(PDO::FETCH_ASSOC);

        // ── KPI GLOBAUX ───────────────────────────────────────────────────────
        $kpi = $this->getKpiGlobaux($filtreDate);

        // ── TAB 1 : Vue d'ensemble par service ───────────────────────────────
        $vue_services = $this->getVueParService($filtreDate);

        // ── TAB 2 : Plan de soins (filtré par service+date) ──────────────────
        $soins = $this->getSoins($filtreService, $filtreDate);

        // ── TAB 3 : Activité infirmiers ───────────────────────────────────────
        $activite_inf = $this->getActiviteInfirmiers($filtreService, $filtreDate);

        // ── TAB 4 : Paramètres vitaux patients ───────────────────────────────
        $parametres = $this->getParametresPatients($filtreService);

        // ── TAB 5 : Observations infirmières ─────────────────────────────────
        $observations = $this->getObservations($filtreService, $filtreDate);

        // ── TAB 6 : Prescriptions médicaments ────────────────────────────────
        $prescriptions = $this->getPrescriptions($filtreService);

        // ── Alertes critiques ─────────────────────────────────────────────────
        $alertes = $this->getAlertes();

        // ── TAB 7 : Occupation des lits ───────────────────────────────────────
        $occupation_lits = $this->getOccupationLits();

        // ── TAB 8 : RDV Spécialistes ─────────────────────────────────────────
        $filtreRdvDate = $_GET['rdv_date'] ?? $filtreDate;
        $filtreRdvDate = preg_match('/^\d{4}-\d{2}-\d{2}$/', $filtreRdvDate) ? $filtreRdvDate : date('Y-m-d');
        $filtreRdvSpec = (int)($_GET['rdv_spec'] ?? 0);

        $rdv_specialistes  = $this->getRdvSpecialistes($filtreRdvSpec, $filtreRdvDate);
        $kpi_rdv           = $this->getKpiRdv($filtreRdvDate);
        $liste_specialistes = $this->getListeSpecialistes();

        // ── TAB 9 : Suivi du parcours patient ─────────────────────────────────
        $parc_services = $this->db->query(
            "SELECT id, nom_service FROM services ORDER BY nom_service"
        )->fetchAll(PDO::FETCH_ASSOC);

        $parc_patients = $this->db->query("
            SELECT p.id, p.nom, p.prenom, p.dossier_numero, p.sexe, p.date_naissance,
                   p.statut_parcours, p.service_id, p.medecin_id, p.circuit,
                   p.numero_ordre, p.created_at,
                   s.nom_service,
                   CONCAT(u.prenom,' ',u.nom) AS medecin_nom,
                   (SELECT DATE_FORMAT(MAX(c2.date_consultation),'%H:%i')
                    FROM consultations c2
                    WHERE c2.patient_id = p.id AND DATE(c2.date_consultation) = CURDATE()
                   ) AS heure_derniere_consult,
                   (SELECT DATE_FORMAT(MAX(dl.date_creation),'%H:%i')
                    FROM demandes_laboratoire dl
                    WHERE dl.patient_id = p.id AND DATE(dl.date_creation) = CURDATE()
                   ) AS heure_dernier_labo,
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
            ORDER BY FIELD(p.statut_parcours,
                'URGENCES','EN_CONSULTATION','ATTENTE_CONSULTATION',
                'PARAMETRES','PARAMETRES_MATERNITE','ACCUEIL',
                'HOSPITALISE','ABSENT_24H','SORTI','SORTIE_RECENTE'
            ), p.numero_ordre ASC, p.created_at ASC
        ")->fetchAll(PDO::FETCH_ASSOC);

        $parc_compteurs = [];
        foreach ($parc_patients as $pp) {
            $st = $pp['statut_parcours'] ?? 'INCONNU';
            $parc_compteurs[$st] = ($parc_compteurs[$st] ?? 0) + 1;
        }

        require_once __DIR__ . '/../views/coordo_soins/dashboard.php';
    }

    // =========================================================================
    // REQUÊTES INTERNES
    // =========================================================================

    private function getKpiGlobaux(string $date): array
    {
        $kpi = [];
        $cond = "h.statut = 'en_cours'";

        // Patients hospitalisés en cours (tous services)
        $kpi['patients_total'] = (int)$this->db->query(
            "SELECT COUNT(*) FROM hospitalisations WHERE statut = 'en_cours'"
        )->fetchColumn();

        // Soins du jour
        $s = $this->db->prepare("
            SELECT COUNT(*) FROM soins_hospitalisation sh
            JOIN hospitalisations h ON sh.admission_id = h.id
            WHERE $cond AND DATE(sh.date_prevue) = ?");
        $s->execute([$date]);
        $kpi['soins_jour'] = (int)$s->fetchColumn();

        // Soins réalisés
        $s = $this->db->prepare("
            SELECT COUNT(*) FROM soins_hospitalisation sh
            JOIN hospitalisations h ON sh.admission_id = h.id
            WHERE $cond AND sh.statut = 'REALISE' AND DATE(sh.date_prevue) = ?");
        $s->execute([$date]);
        $kpi['soins_realises'] = (int)$s->fetchColumn();

        // Soins en retard
        $s = $this->db->prepare("
            SELECT COUNT(*) FROM soins_hospitalisation sh
            JOIN hospitalisations h ON sh.admission_id = h.id
            WHERE $cond AND sh.statut = 'RETARD' AND DATE(sh.date_prevue) = ?");
        $s->execute([$date]);
        $kpi['soins_retard'] = (int)$s->fetchColumn();

        // Soins annulés
        $s = $this->db->prepare("
            SELECT COUNT(*) FROM soins_hospitalisation sh
            JOIN hospitalisations h ON sh.admission_id = h.id
            WHERE $cond AND sh.statut = 'ANNULE' AND DATE(sh.date_prevue) = ?");
        $s->execute([$date]);
        $kpi['soins_annules'] = (int)$s->fetchColumn();

        $kpi['taux'] = $kpi['soins_jour'] > 0
            ? round($kpi['soins_realises'] / $kpi['soins_jour'] * 100) : 0;

        // Infirmiers actifs aujourd'hui
        $s = $this->db->prepare("
            SELECT COUNT(DISTINCT sh.user_id_executant) FROM soins_hospitalisation sh
            JOIN hospitalisations h ON sh.admission_id = h.id
            WHERE h.statut = 'en_cours' AND sh.statut = 'REALISE'
              AND DATE(sh.date_realisee) = ?");
        $s->execute([$date]);
        $kpi['infirmiers_actifs'] = (int)$s->fetchColumn();

        // Services actifs
        $kpi['services_actifs'] = (int)$this->db->query(
            "SELECT COUNT(DISTINCT service_id) FROM hospitalisations WHERE statut = 'en_cours'"
        )->fetchColumn();

        // Observations du jour (observations_evolution + patient_parametres.observations)
        try {
            $s = $this->db->prepare("
                SELECT (
                    SELECT COUNT(*) FROM observations_evolution WHERE DATE(created_at) = ?
                ) + (
                    SELECT COUNT(*) FROM patient_parametres
                    WHERE DATE(date_mesure) = ? AND observations IS NOT NULL AND observations != ''
                ) AS total
            ");
            $s->execute([$date, $date]);
            $kpi['observations_jour'] = (int)$s->fetchColumn();
        } catch (PDOException $e) {
            // Fallback : patient_parametres seulement si observations_evolution n'existe pas
            try {
                $s = $this->db->prepare("
                    SELECT COUNT(*) FROM patient_parametres
                    WHERE DATE(date_mesure) = ? AND observations IS NOT NULL AND observations != ''
                ");
                $s->execute([$date]);
                $kpi['observations_jour'] = (int)$s->fetchColumn();
            } catch (PDOException $e2) { $kpi['observations_jour'] = 0; }
        }

        return $kpi;
    }

    private function getVueParService(string $date): array
    {
        // Seuls les services hospitaliers (= ayant des chambres/lits) sont affichés
        $rows = $this->db->prepare("
            SELECT s.id AS service_id, s.nom_service,
                   COUNT(DISTINCT h.id)                                                     AS nb_patients,
                   COUNT(DISTINCT sh.id)                                                    AS soins_total,
                   SUM(sh.statut = 'REALISE')                                              AS soins_realises,
                   SUM(sh.statut = 'RETARD')                                               AS soins_retard,
                   SUM(sh.statut = 'ANNULE')                                               AS soins_annules,
                   SUM(sh.statut = 'PLANIFIE')                                             AS soins_planifies,
                   COUNT(DISTINCT CASE WHEN sh.statut='REALISE' THEN sh.user_id_executant END) AS inf_actifs
            FROM services s
            INNER JOIN chambres c ON c.service_id = s.id
            LEFT JOIN hospitalisations h ON h.service_id = s.id AND h.statut = 'en_cours'
            LEFT JOIN soins_hospitalisation sh ON sh.admission_id = h.id AND DATE(sh.date_prevue) = ?
            GROUP BY s.id, s.nom_service
            ORDER BY s.nom_service
        ");
        $rows->execute([$date]);
        return $rows->fetchAll(PDO::FETCH_ASSOC);
    }

    private function getSoins(int $serviceId, string $date): array
    {
        $where = "DATE(sh.date_prevue) = ?";
        $params = [$date];
        if ($serviceId > 0) {
            $where .= " AND h.service_id = ?";
            $params[] = $serviceId;
        }
        $stmt = $this->db->prepare("
            SELECT sh.id, sh.type_soin, sh.description, sh.condition_application,
                   sh.date_prevue, sh.date_realisee, sh.statut,
                   sh.note_execution, sh.note_major,
                   p.id AS patient_id, p.nom AS patient_nom, p.prenom AS patient_prenom,
                   p.dossier_numero,
                   l.nom_lit, c.nom_chambre,
                   s.nom_service,
                   up.nom AS planificateur_nom, up.prenom AS planificateur_prenom,
                   ue.nom AS executant_nom, ue.prenom AS executant_prenom
            FROM soins_hospitalisation sh
            JOIN hospitalisations h ON sh.admission_id = h.id
            JOIN patients p         ON h.patient_id = p.id
            JOIN services s         ON h.service_id = s.id
            LEFT JOIN lits l        ON h.lit_id = l.id
            LEFT JOIN chambres c    ON l.chambre_id = c.id
            LEFT JOIN users up      ON sh.user_id_planificateur = up.id
            LEFT JOIN users ue      ON sh.user_id_executant = ue.id
            WHERE h.statut = 'en_cours' AND $where
            ORDER BY FIELD(sh.statut,'RETARD','PLANIFIE','REALISE','ANNULE'), sh.date_prevue ASC
            LIMIT 500
        ");
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function getActiviteInfirmiers(int $serviceId, string $date): array
    {
        // Filtre service injecté directement (int casté, pas de risque injection)
        $svcSql = $serviceId > 0 ? 'AND h.service_id = ' . (int)$serviceId : '';

        // ── Soins : planifiés OU exécutés par l'infirmier ────────────────────
        // On utilise UNION pour capturer les deux rôles sans doublon de décompte
        $stmt = $this->db->prepare("
            SELECT u.id, u.nom, u.prenom, u.role,
                   s.nom_service,
                   sh_stats.total_planifie,
                   sh_stats.realises,
                   sh_stats.retards,
                   sh_stats.annules,
                   sh_stats.en_attente,
                   sh_stats.derniere_action
            FROM users u
            JOIN services s ON u.service_id = s.id
            INNER JOIN (
                SELECT uid,
                       COUNT(*)               AS total_planifie,
                       SUM(statut='REALISE')  AS realises,
                       SUM(statut='RETARD')   AS retards,
                       SUM(statut='ANNULE')   AS annules,
                       SUM(statut='PLANIFIE') AS en_attente,
                       MAX(date_realisee)     AS derniere_action
                FROM (
                    /* soins planifiés par cet infirmier */
                    SELECT sh.user_id_planificateur AS uid, sh.statut, sh.date_realisee
                    FROM soins_hospitalisation sh
                    JOIN hospitalisations h ON sh.admission_id = h.id
                    WHERE DATE(sh.date_prevue) = ?
                      $svcSql
                      AND sh.user_id_planificateur IS NOT NULL
                    UNION ALL
                    /* soins exécutés par un infirmier différent du planificateur */
                    SELECT sh.user_id_executant AS uid, sh.statut, sh.date_realisee
                    FROM soins_hospitalisation sh
                    JOIN hospitalisations h ON sh.admission_id = h.id
                    WHERE DATE(sh.date_prevue) = ?
                      $svcSql
                      AND sh.user_id_executant IS NOT NULL
                      AND (sh.user_id_executant != sh.user_id_planificateur
                           OR sh.user_id_planificateur IS NULL)
                ) combined
                GROUP BY uid
            ) sh_stats ON sh_stats.uid = u.id
            WHERE u.role IN ('INFIRMIER','MAJOR','MAJOR_INFIRMIER','INFIRMIER_CONSULTANT')
              AND u.actif = 1
            ORDER BY s.nom_service, sh_stats.retards DESC, sh_stats.realises DESC
        ");
        $stmt->execute([$date, $date]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Initialiser le compteur d'observations
        foreach ($results as &$r) { $r['nb_observations'] = 0; }
        unset($r);
        $indexed = array_flip(array_column($results, 'id'));

        // ── Observations (table optionnelle) ─────────────────────────────────
        try {
            $oeStmt = $this->db->prepare("
                SELECT oe.user_id, COUNT(*) AS nb_obs, MAX(oe.created_at) AS derniere_obs
                FROM observations_evolution oe
                JOIN users u ON oe.user_id = u.id
                WHERE DATE(oe.created_at) = ?
                  AND u.role IN ('INFIRMIER','MAJOR','MAJOR_INFIRMIER','INFIRMIER_CONSULTANT')
                  AND u.actif = 1
                GROUP BY oe.user_id
            ");
            $oeStmt->execute([$date]);
            $oeMap = [];
            foreach ($oeStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $oeMap[(int)$row['user_id']] = $row;
            }

            // Fusionner les observations dans les résultats existants
            foreach ($results as &$r) {
                $uid = (int)$r['id'];
                if (isset($oeMap[$uid])) {
                    $r['nb_observations'] = (int)$oeMap[$uid]['nb_obs'];
                    if (!$r['derniere_action'] ||
                        $oeMap[$uid]['derniere_obs'] > $r['derniere_action']) {
                        $r['derniere_action'] = $oeMap[$uid]['derniere_obs'];
                    }
                    unset($oeMap[$uid]);
                }
            }
            unset($r);

            // Infirmiers présents UNIQUEMENT dans les observations (pas de soins ce jour)
            foreach ($oeMap as $uid => $oe) {
                $uStmt = $this->db->prepare(
                    "SELECT u.id, u.nom, u.prenom, u.role, s.nom_service
                     FROM users u
                     JOIN services s ON u.service_id = s.id
                     WHERE u.id = ? AND u.actif = 1
                       AND u.role IN ('INFIRMIER','MAJOR','MAJOR_INFIRMIER','INFIRMIER_CONSULTANT')"
                );
                $uStmt->execute([$uid]);
                $nurse = $uStmt->fetch(PDO::FETCH_ASSOC);
                if ($nurse) {
                    $nurse['total_planifie'] = 0;
                    $nurse['realises']       = 0;
                    $nurse['retards']        = 0;
                    $nurse['annules']        = 0;
                    $nurse['en_attente']     = 0;
                    $nurse['derniere_action'] = $oe['derniere_obs'];
                    $nurse['nb_observations'] = (int)$oe['nb_obs'];
                    $results[] = $nurse;
                }
            }
        } catch (PDOException $e) {
            // observations_evolution inexistante — pas critique
        }

        return $results;
    }

    private function getParametresPatients(int $serviceId): array
    {
        $where  = "h.statut = 'en_cours'";
        $params = [];
        if ($serviceId > 0) {
            $where .= " AND h.service_id = ?";
            $params[] = $serviceId;
        }
        $stmt = $this->db->prepare("
            SELECT p.id, p.nom, p.prenom, p.dossier_numero,
                   l.nom_lit, c.nom_chambre, s.nom_service,
                   /* Dernière valeur non-nulle pour chaque paramètre vital (indépendamment) */
                   (SELECT pp1.temperature FROM patient_parametres pp1
                    WHERE pp1.patient_id = p.id AND pp1.temperature IS NOT NULL
                    ORDER BY pp1.date_mesure DESC LIMIT 1) AS temperature,
                   (SELECT pp1.pression_arterielle_systolique FROM patient_parametres pp1
                    WHERE pp1.patient_id = p.id AND pp1.pression_arterielle_systolique IS NOT NULL
                    ORDER BY pp1.date_mesure DESC LIMIT 1) AS ta_sys,
                   (SELECT pp1.pression_arterielle_diastolique FROM patient_parametres pp1
                    WHERE pp1.patient_id = p.id AND pp1.pression_arterielle_diastolique IS NOT NULL
                    ORDER BY pp1.date_mesure DESC LIMIT 1) AS ta_dia,
                   (SELECT pp1.frequence_cardiaque FROM patient_parametres pp1
                    WHERE pp1.patient_id = p.id AND pp1.frequence_cardiaque IS NOT NULL
                    ORDER BY pp1.date_mesure DESC LIMIT 1) AS fc,
                   (SELECT pp1.saturation_oxygene FROM patient_parametres pp1
                    WHERE pp1.patient_id = p.id AND pp1.saturation_oxygene IS NOT NULL
                    ORDER BY pp1.date_mesure DESC LIMIT 1) AS spo2,
                   (SELECT pp1.glycemie FROM patient_parametres pp1
                    WHERE pp1.patient_id = p.id AND pp1.glycemie IS NOT NULL
                    ORDER BY pp1.date_mesure DESC LIMIT 1) AS glycemie,
                   (SELECT pp1.frequence_respiratoire FROM patient_parametres pp1
                    WHERE pp1.patient_id = p.id AND pp1.frequence_respiratoire IS NOT NULL
                    ORDER BY pp1.date_mesure DESC LIMIT 1) AS fr,
                   (SELECT pp1.poids FROM patient_parametres pp1
                    WHERE pp1.patient_id = p.id AND pp1.poids IS NOT NULL
                    ORDER BY pp1.date_mesure DESC LIMIT 1) AS poids,
                   /* Date et infirmier du dernier relevé (toutes colonnes confondues) */
                   pp_last.date_mesure,
                   u.nom  AS infirmier_nom, u.prenom AS infirmier_prenom
            FROM patients p
            JOIN hospitalisations h ON p.id = h.patient_id
            JOIN services s         ON h.service_id = s.id
            LEFT JOIN lits l        ON h.lit_id = l.id
            LEFT JOIN chambres c    ON l.chambre_id = c.id
            LEFT JOIN patient_parametres pp_last ON pp_last.id = (
                SELECT id FROM patient_parametres WHERE patient_id = p.id
                ORDER BY date_mesure DESC LIMIT 1
            )
            LEFT JOIN users u ON pp_last.user_id = u.id
            WHERE $where
            ORDER BY s.nom_service, c.nom_chambre, l.nom_lit
            LIMIT 300
        ");
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function getObservations(int $serviceId, string $date): array
    {
        // Filtre service pour chaque branche de la UNION
        $svcWhere1 = $serviceId > 0 ? " AND h.service_id = " . $serviceId  : "";
        $svcWhere2 = $serviceId > 0 ? " AND h2.service_id = " . $serviceId : "";

        try {
            // UNION : observations_evolution + patient_parametres.observations
            $stmt = $this->db->prepare("
                SELECT combined.* FROM (
                    -- Source 1 : notes d'évolution clinique
                    SELECT o.id,
                           o.contenu,
                           o.created_at,
                           p.nom  AS patient_nom, p.prenom AS patient_prenom, p.dossier_numero,
                           u.nom  AS auteur_nom,  u.prenom AS auteur_prenom,  u.role AS auteur_role,
                           s.nom_service, c.nom_chambre, l.nom_lit,
                           'evolution' AS source
                    FROM observations_evolution o
                    JOIN patients p ON o.patient_id = p.id
                    JOIN users u    ON o.user_id    = u.id
                    LEFT JOIN hospitalisations h ON h.patient_id = p.id AND h.statut = 'en_cours'
                    LEFT JOIN services s         ON h.service_id = s.id
                    LEFT JOIN lits l             ON h.lit_id     = l.id
                    LEFT JOIN chambres c         ON l.chambre_id = c.id
                    WHERE DATE(o.created_at) = ?
                      $svcWhere1

                    UNION ALL

                    -- Source 2 : observations infirmières (fiche de constantes)
                    SELECT pp.id,
                           pp.observations AS contenu,
                           pp.date_mesure  AS created_at,
                           p.nom  AS patient_nom, p.prenom AS patient_prenom, p.dossier_numero,
                           u.nom  AS auteur_nom,  u.prenom AS auteur_prenom,  u.role AS auteur_role,
                           s.nom_service, c.nom_chambre, l.nom_lit,
                           'constantes' AS source
                    FROM patient_parametres pp
                    JOIN patients p ON pp.patient_id = p.id
                    JOIN users u    ON pp.user_id    = u.id
                    LEFT JOIN hospitalisations h2 ON h2.patient_id = p.id AND h2.statut = 'en_cours'
                    LEFT JOIN services s          ON h2.service_id = s.id
                    LEFT JOIN lits l              ON h2.lit_id     = l.id
                    LEFT JOIN chambres c          ON l.chambre_id  = c.id
                    WHERE DATE(pp.date_mesure) = ?
                      AND pp.observations IS NOT NULL AND pp.observations != ''
                      $svcWhere2
                ) combined
                ORDER BY combined.created_at DESC
                LIMIT 200
            ");
            $stmt->execute([$date, $date]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            // Fallback si observations_evolution n'existe pas : patient_parametres uniquement
            try {
                $where  = "DATE(pp.date_mesure) = ?";
                $params = [$date];
                if ($serviceId > 0) {
                    $where .= " AND h.service_id = ?";
                    $params[] = $serviceId;
                }
                $stmt = $this->db->prepare("
                    SELECT pp.id,
                           pp.observations AS contenu,
                           pp.date_mesure  AS created_at,
                           p.nom  AS patient_nom, p.prenom AS patient_prenom, p.dossier_numero,
                           u.nom  AS auteur_nom,  u.prenom AS auteur_prenom,  u.role AS auteur_role,
                           s.nom_service, c.nom_chambre, l.nom_lit,
                           'constantes' AS source
                    FROM patient_parametres pp
                    JOIN patients p ON pp.patient_id = p.id
                    JOIN users u    ON pp.user_id    = u.id
                    LEFT JOIN hospitalisations h ON h.patient_id = p.id AND h.statut = 'en_cours'
                    LEFT JOIN services s         ON h.service_id = s.id
                    LEFT JOIN lits l             ON h.lit_id     = l.id
                    LEFT JOIN chambres c         ON l.chambre_id = c.id
                    WHERE $where
                      AND pp.observations IS NOT NULL AND pp.observations != ''
                    ORDER BY pp.date_mesure DESC
                    LIMIT 200
                ");
                $stmt->execute($params);
                return $stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (PDOException $e2) {
                return [];
            }
        }
    }

    private function getPrescriptions(int $serviceId): array
    {
        try {
            $where  = "ph.statut = 'active'";
            $params = [];
            if ($serviceId > 0) {
                $where .= " AND h.service_id = ?";
                $params[] = $serviceId;
            }
            $stmt = $this->db->prepare("
                SELECT ph.id, ph.posologie, ph.voie_administration, ph.frequence,
                       ph.heure_debut, ph.heure_fin, ph.statut, ph.date_prescription,
                       ph.quantite, ph.unite,
                       m.nom  AS medicament_nom, m.forme, m.dosage,
                       p.nom  AS patient_nom, p.prenom AS patient_prenom, p.dossier_numero,
                       um.nom AS medecin_nom, um.prenom AS medecin_prenom,
                       s.nom_service, c.nom_chambre, l.nom_lit
                FROM prescriptions_hospitalisation ph
                JOIN medicaments m  ON ph.medicament_id = m.id
                JOIN patients p     ON ph.patient_id = p.id
                JOIN users um       ON ph.medecin_id = um.id
                LEFT JOIN hospitalisations h ON h.patient_id = p.id AND h.statut = 'en_cours'
                LEFT JOIN services s         ON h.service_id = s.id
                LEFT JOIN lits l             ON h.lit_id = l.id
                LEFT JOIN chambres c         ON l.chambre_id = c.id
                WHERE $where
                ORDER BY s.nom_service, p.nom, ph.heure_debut
                LIMIT 400
            ");
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }

    private function getOccupationLits(): array
    {
        try {
            // ── Global ────────────────────────────────────────────────────────
            $global = $this->db->query("
                SELECT COUNT(*)              AS total,
                       SUM(statut='OCCUPE')  AS occupes,
                       SUM(statut='LIBRE')   AS libres,
                       SUM(statut='RESERVE') AS reserves,
                       SUM(statut='MAINTENANCE' OR statut='HORS_SERVICE') AS hors_service
                FROM lits
            ")->fetch(PDO::FETCH_ASSOC);

            // ── Par service hospitalier (avec chambres) ───────────────────────
            $parService = $this->db->query("
                SELECT s.id AS service_id,
                       s.nom_service,
                       COUNT(l.id)              AS total,
                       SUM(l.statut='OCCUPE')   AS occupes,
                       SUM(l.statut='LIBRE')    AS libres,
                       SUM(l.statut='RESERVE')  AS reserves
                FROM services s
                INNER JOIN chambres c ON c.service_id = s.id
                INNER JOIN lits l     ON l.chambre_id  = c.id
                GROUP BY s.id, s.nom_service
                HAVING total > 0
                ORDER BY occupes DESC, total DESC
            ")->fetchAll(PDO::FETCH_ASSOC);

            // Calculer les taux
            foreach ($parService as &$row) {
                $row['taux'] = $row['total'] > 0
                    ? round($row['occupes'] / $row['total'] * 100)
                    : 0;
            }
            unset($row);

            $global['taux'] = (int)$global['total'] > 0
                ? round((int)$global['occupes'] / (int)$global['total'] * 100)
                : 0;

            return ['global' => $global, 'par_service' => $parService];
        } catch (PDOException $e) {
            return ['global' => ['total'=>0,'occupes'=>0,'libres'=>0,'reserves'=>0,'hors_service'=>0,'taux'=>0],
                    'par_service' => []];
        }
    }

    private function getAlertes(): array
    {
        // Patients avec SpO2 < 94 % ou température > 39°C ou FC > 120
        try {
            return $this->db->query("
                SELECT p.nom, p.prenom, p.dossier_numero,
                       pp.saturation_oxygene AS spo2, pp.temperature, pp.frequence_cardiaque AS fc,
                       pp.pression_arterielle_systolique AS ta_sys,
                       c.nom_chambre, l.nom_lit, s.nom_service,
                       pp.date_mesure
                FROM patient_parametres pp
                JOIN patients p     ON pp.patient_id = p.id
                JOIN hospitalisations h ON h.patient_id = p.id AND h.statut = 'en_cours'
                JOIN services s     ON h.service_id = s.id
                LEFT JOIN lits l    ON h.lit_id = l.id
                LEFT JOIN chambres c ON l.chambre_id = c.id
                WHERE pp.id = (SELECT MAX(id) FROM patient_parametres WHERE patient_id = p.id)
                  AND (
                      pp.saturation_oxygene < 94
                   OR pp.temperature > 39
                   OR pp.frequence_cardiaque > 120
                   OR pp.pression_arterielle_systolique > 180
                   OR pp.pression_arterielle_systolique < 90
                  )
                ORDER BY pp.date_mesure DESC
                LIMIT 30
            ")->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }

    /* ──────────────────────────────────────────────────────────────────────
     * RDV chez les Spécialistes — liste paginée avec filtres
     * ────────────────────────────────────────────────────────────────────── */
    private function getRdvSpecialistes(int $filtreSpec, string $date): array
    {
        try {
            $where  = ['1=1'];
            $params = [];

            if ($date) {
                $where[]  = 'r.date_rdv = ?';
                $params[] = $date;
            }
            if ($filtreSpec) {
                $where[]  = 'r.specialiste_id = ?';
                $params[] = $filtreSpec;
            }

            $sql = "
                SELECT r.id, r.date_rdv, r.heure_rdv, r.motif, r.statut,
                       r.ordre_passage, r.notes_secretaire, r.conclusion_specialiste,
                       r.source, r.created_at,
                       CONCAT(p.nom,' ',p.prenom)               AS patient_nom,
                       p.dossier_numero,
                       p.id                                      AS patient_id,
                       CONCAT(sp.nom,' ',sp.prenom)              AS specialiste_nom,
                       sp.specialite                             AS specialiste_specialite,
                       CONCAT(presc.nom,' ',presc.prenom)        AS prescrit_par_nom
                FROM rdv_specialistes r
                JOIN patients    p     ON p.id     = r.patient_id
                JOIN specialistes sp   ON sp.id    = r.specialiste_id
                LEFT JOIN users   presc ON presc.id = r.prescrit_par
                WHERE " . implode(' AND ', $where) . "
                ORDER BY r.heure_rdv ASC, r.ordre_passage ASC
                LIMIT 200
            ";
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log('[CoordoSoins] getRdvSpecialistes: ' . $e->getMessage());
            return [];
        }
    }

    /** Charge tous les spécialistes actifs pour le filtre dropdown */
    private function getListeSpecialistes(): array
    {
        try {
            return $this->db->query(
                "SELECT id, nom, prenom, specialite FROM specialistes WHERE actif = 1 ORDER BY specialite, nom"
            )->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log('[CoordoSoins] getListeSpecialistes: ' . $e->getMessage());
            return [];
        }
    }

    /* ── KPIs RDV ──────────────────────────────────────────────────────────── */
    private function getKpiRdv(string $date): array
    {
        $kpi = ['total'=>0,'en_attente'=>0,'confirme'=>0,'en_cours'=>0,
                'termine'=>0,'annule'=>0,'non_honore'=>0];
        try {
            $rows = $this->db->prepare(
                "SELECT statut, COUNT(*) as nb FROM rdv_specialistes
                 WHERE date_rdv = ? GROUP BY statut"
            );
            $rows->execute([$date]);
            foreach ($rows->fetchAll(\PDO::FETCH_ASSOC) as $r) {
                $key = strtolower($r['statut']);
                $kpi[$key] = (int)$r['nb'];
                $kpi['total'] += (int)$r['nb'];
            }
        } catch (\PDOException $e) {}
        return $kpi;
    }

    /* ── Mise à jour statut RDV ────────────────────────────────────────────── */
    public function updateStatutRdv(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        $this->guard();

        $id     = (int)($_POST['id']     ?? 0);
        $statut = trim($_POST['statut']  ?? '');
        $notes  = trim($_POST['notes']   ?? '');

        $statutsValides = ['EN_ATTENTE','CONFIRME','EN_COURS','TERMINE','ANNULE','REPORTE','NON_HONORE'];
        if (!$id || !in_array($statut, $statutsValides)) {
            echo json_encode(['success'=>false,'message'=>'Paramètres invalides.']); return;
        }

        try {
            $this->db->prepare(
                "UPDATE rdv_specialistes SET statut = ?, notes_secretaire = COALESCE(NULLIF(?,''), notes_secretaire) WHERE id = ?"
            )->execute([$statut, $notes, $id]);
            echo json_encode(['success'=>true,'statut'=>$statut]);
        } catch (\PDOException $e) {
            echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
        }
    }

    // =========================================================================
    // ACTIONS AJAX
    // =========================================================================

    public function getServiceStats(): void
    {
        header('Content-Type: application/json');
        $this->guard();
        $sid  = (int)($_GET['service_id'] ?? 0);
        $date = $_GET['date'] ?? date('Y-m-d');
        echo json_encode($this->getVueParService($date));
    }
}
