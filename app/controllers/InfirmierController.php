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
 * InfirmierController
 * Gère les consultations infirmières.
 */
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../services/AuditService.php';

class InfirmierController
{
    private PDO          $db;
    private AuditService $audit;

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        if (empty($_SESSION['user_id'])) {
            header('Location: ' . BASE_URL . 'login'); exit;
        }

        $this->db    = (new Database())->getConnection();
        $this->audit = new AuditService($this->db);

        // Migration douce : colonnes supplémentaires
        $this->migrerColonnes();
    }

    /* ─────────────────────────────────────────────────────────────────
       Migration : ajoute les colonnes manquantes si besoin
    ───────────────────────────────────────────────────────────────── */
    private function migrerColonnes(): void
    {
        // Colonnes à ajouter dans `consultations`
        $existantes = array_flip(
            $this->db->query("SHOW COLUMNS FROM consultations")->fetchAll(PDO::FETCH_COLUMN)
        );

        $aAjouter = [
            'infirmier_id'             => "INT NULL AFTER medecin_id",
            'devenir'                  => "ENUM('EN_COURS','SORTIE','HOSPITALISATION','OBSERVATION') DEFAULT 'EN_COURS'",
            'devenir_service_id'       => "INT NULL",
            'devenir_lit_id'           => "INT NULL",
            'observation_motif'        => "TEXT NULL",
            'observation_elements'     => "TEXT NULL",
            'observation_duree_heures' => "TINYINT UNSIGNED NULL",
            'observation_fin'          => "DATETIME NULL",
            'observation_decision'     => "ENUM('SORTIE','HOSPITALISATION') NULL",
            'observation_decision_date'=> "DATETIME NULL",
            'observation_decision_notes'=> "TEXT NULL",
            'spo2'                     => "DECIMAL(5,2) NULL COMMENT 'SpO2 %'",
        ];

        foreach ($aAjouter as $col => $def) {
            if (!isset($existantes[$col])) {
                try {
                    $this->db->exec("ALTER TABLE consultations ADD COLUMN `$col` $def");
                } catch (Exception $e) {
                    error_log("[InfirmierController] ALTER consultations ADD $col : " . $e->getMessage());
                }
            }
        }

        // Ajouter 'infirmiere' à l'enum type_consultation si absent
        // (en préservant TOUTES les valeurs existantes de l'enum)
        try {
            $row = $this->db->query(
                "SHOW COLUMNS FROM consultations LIKE 'type_consultation'"
            )->fetch(PDO::FETCH_ASSOC);
            if ($row && !str_contains(strtolower($row['Type'] ?? ''), 'infirmiere')) {
                // Extraire les valeurs actuelles de l'ENUM : enum('val1','val2',...)
                preg_match_all("/'([^']+)'/", $row['Type'], $m);
                $valeursActuelles = $m[1] ?? [];
                // Ajouter 'infirmiere' s'il n'est pas déjà présent
                if (!in_array('infirmiere', $valeursActuelles, true)) {
                    $valeursActuelles[] = 'infirmiere';
                }
                $enumDef = implode("','", array_map('addslashes', $valeursActuelles));
                $this->db->exec(
                    "ALTER TABLE consultations
                     MODIFY COLUMN type_consultation
                     ENUM('$enumDef') NOT NULL DEFAULT 'externe'"
                );
            }
        } catch (Exception $e) {
            error_log("[InfirmierController] ALTER ENUM type_consultation : " . $e->getMessage());
        }

        // Ajouter hors_stock dans ordonnance_medicaments si absent
        try {
            $cols = array_flip(
                $this->db->query("SHOW COLUMNS FROM ordonnance_medicaments")->fetchAll(PDO::FETCH_COLUMN)
            );
            if (!isset($cols['hors_stock'])) {
                $this->db->exec(
                    "ALTER TABLE ordonnance_medicaments ADD COLUMN `hors_stock` TINYINT(1) DEFAULT 0"
                );
            }
        } catch (Exception $e) {
            error_log("[InfirmierController] ALTER ordonnance_medicaments ADD hors_stock : " . $e->getMessage());
        }
    }

    /* ─────────────────────────────────────────────────────────────────
       GET  infirmier/consultation/{patient_id}
    ───────────────────────────────────────────────────────────────── */
    public function formulaire(int $patient_id): void
    {
        $patient = $this->getPatientComplet($patient_id);
        if (!$patient) {
            header('Location: ' . BASE_URL . 'dashboard?error=patient_introuvable'); exit;
        }

        // Derniers antécédents connus
        $stmtAtcd = $this->db->prepare(
            "SELECT atcd_medicaux, atcd_chirurgicaux, atcd_familiaux, atcd_allergies
             FROM consultations
             WHERE patient_id = ?
               AND (atcd_medicaux IS NOT NULL OR atcd_allergies IS NOT NULL)
             ORDER BY date_consultation DESC LIMIT 1"
        );
        $stmtAtcd->execute([$patient_id]);
        $atcd = $stmtAtcd->fetch(PDO::FETCH_ASSOC) ?: [];

        // Dernières constantes vitales — priorité : patient_parametres (triage IAO),
        // puis fallback sur la dernière consultation si rien n'a encore été mesuré.
        $dernieres_constantes = [];
        try {
            $stmtPP = $this->db->prepare(
                "SELECT pp.temperature,
                        pp.frequence_cardiaque,
                        pp.frequence_respiratoire,
                        pp.pression_arterielle_systolique  AS tension_systolique,
                        pp.pression_arterielle_diastolique AS tension_diastolique,
                        pp.poids,
                        pp.taille,
                        pp.saturation_oxygene              AS spo2,
                        pp.date_mesure,
                        CONCAT(u.prenom,' ',u.nom)          AS mesure_par
                 FROM patient_parametres pp
                 LEFT JOIN users u ON u.id = pp.user_id
                 WHERE pp.patient_id = ?
                 ORDER BY pp.date_mesure DESC
                 LIMIT 1"
            );
            $stmtPP->execute([$patient_id]);
            $dernieres_constantes = $stmtPP->fetch(PDO::FETCH_ASSOC) ?: [];
        } catch (Exception $e) {}

        // Fallback : consultation précédente si aucune mesure dans patient_parametres
        if (empty($dernieres_constantes) || empty($dernieres_constantes['temperature'])) {
            try {
                $stmtConst = $this->db->prepare(
                    "SELECT temperature, frequence_cardiaque, frequence_respiratoire,
                            tension_systolique, tension_diastolique, poids, taille, spo2,
                            NULL AS date_mesure, NULL AS mesure_par
                     FROM consultations
                     WHERE patient_id = ? AND temperature IS NOT NULL
                     ORDER BY date_consultation DESC LIMIT 1"
                );
                $stmtConst->execute([$patient_id]);
                $fallback = $stmtConst->fetch(PDO::FETCH_ASSOC) ?: [];
                if (!empty($fallback)) $dernieres_constantes = $fallback;
            } catch (Exception $e) {}
        }

        // Médicaments pour autocomplete (colonne `nom` dans la table)
        try {
            $medicaments = $this->db->query(
                "SELECT id, nom AS nom_commercial, '' AS dci, forme, dosage
                 FROM medicaments ORDER BY nom LIMIT 500"
            )->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $medicaments = [];
        }

        // Examens laboratoire (colonne `nom`, `disponible`)
        try {
            $examens_labo = $this->db->query(
                "SELECT id, nom AS nom_examen, categorie
                 FROM examens_laboratoire
                 WHERE disponible = 1
                 ORDER BY categorie, nom"
            )->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $examens_labo = [];
        }

        // Lits disponibles (accepte toutes les variantes de statut libre)
        try {
            $lits_disponibles = $this->db->query(
                "SELECT l.id, l.nom_lit, c.nom_chambre,
                        s.nom_service, s.id AS service_id
                 FROM lits l
                 JOIN chambres c ON l.chambre_id = c.id
                 JOIN services s ON c.service_id = s.id
                 WHERE l.statut IN ('libre','LIBRE','DISPONIBLE')
                 ORDER BY s.nom_service, c.nom_chambre, l.nom_lit"
            )->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $lits_disponibles = [];
        }

        // Services
        try {
            $services = $this->db->query(
                "SELECT id, nom_service FROM services
                 WHERE actif = 1 ORDER BY nom_service"
            )->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            // Essai sans filtre actif si la colonne n'existe pas
            try {
                $services = $this->db->query(
                    "SELECT id, nom_service FROM services ORDER BY nom_service"
                )->fetchAll(PDO::FETCH_ASSOC);
            } catch (Exception $e2) {
                $services = [];
            }
        }

        require_once __DIR__ . '/../views/infirmier/consultation_infirmiere.php';
    }

    /* ─────────────────────────────────────────────────────────────────
       GET  infirmier-consultant/consulter/{patient_id}
       Formulaire simplifié pour l'infirmier(ère) consultant(e)
    ───────────────────────────────────────────────────────────────── */
    public function formulaireConsultant(int $patient_id): void
    {
        $patient = $this->getPatientComplet($patient_id);
        if (!$patient) {
            header('Location: ' . BASE_URL . 'dashboard?error=patient_introuvable'); exit;
        }

        // Dernières constantes vitales — priorité : bureaux paramètres, fallback : consultations
        $dernieres_constantes = [];
        try {
            // 1. Chercher dans patient_parametres (bureau de triage / paramètres)
            $stmtPP = $this->db->prepare(
                "SELECT
                    pp.temperature,
                    pp.pression_arterielle_systolique  AS tension_systolique,
                    pp.pression_arterielle_diastolique AS tension_diastolique,
                    pp.frequence_cardiaque,
                    pp.saturation_oxygene              AS spo2,
                    pp.poids,
                    pp.taille,
                    pp.date_mesure,
                    CONCAT(u.prenom, ' ', u.nom)       AS mesure_par
                 FROM patient_parametres pp
                 LEFT JOIN users u ON u.id = pp.user_id
                 WHERE pp.patient_id = ?
                 ORDER BY pp.date_mesure DESC
                 LIMIT 1"
            );
            $stmtPP->execute([$patient_id]);
            $dernieres_constantes = $stmtPP->fetch(PDO::FETCH_ASSOC) ?: [];
        } catch (Exception $e) {}

        // 2. Fallback sur la dernière consultation si rien trouvé
        if (empty($dernieres_constantes) || empty($dernieres_constantes['temperature'])) {
            try {
                $stmtC = $this->db->prepare(
                    "SELECT temperature, frequence_cardiaque, frequence_respiratoire,
                            tension_systolique, tension_diastolique, poids, taille, spo2,
                            NULL AS date_mesure, NULL AS mesure_par
                     FROM consultations
                     WHERE patient_id = ? AND temperature IS NOT NULL
                     ORDER BY date_consultation DESC LIMIT 1"
                );
                $stmtC->execute([$patient_id]);
                $fallback = $stmtC->fetch(PDO::FETCH_ASSOC) ?: [];
                if (!empty($fallback)) $dernieres_constantes = $fallback;
            } catch (Exception $e) {}
        }

        // Médicaments pour autocomplete
        try {
            $medicaments = $this->db->query(
                "SELECT id, nom AS nom_commercial, forme, dosage
                 FROM medicaments ORDER BY nom LIMIT 500"
            )->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) { $medicaments = []; }

        // Examens laboratoire fréquents
        try {
            $examens_labo = $this->db->query(
                "SELECT id, nom AS nom_examen, categorie
                 FROM examens_laboratoire WHERE disponible = 1
                 ORDER BY categorie, nom"
            )->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) { $examens_labo = []; }

        // Lits disponibles (chargé en premier pour en déduire les services hospitaliers)
        try {
            $lits_disponibles = $this->db->query(
                "SELECT l.id, l.nom_lit, c.nom_chambre, s.nom_service, s.id AS service_id,
                        (SELECT COUNT(*) FROM lits l2
                         JOIN chambres c2 ON l2.chambre_id = c2.id
                         WHERE c2.service_id = s.id AND l2.statut IN ('libre','LIBRE','DISPONIBLE')
                        ) AS nb_libres_service
                 FROM lits l
                 JOIN chambres c ON l.chambre_id = c.id
                 JOIN services s ON c.service_id = s.id
                 WHERE l.statut IN ('libre','LIBRE','DISPONIBLE')
                 ORDER BY s.nom_service, c.nom_chambre, l.nom_lit"
            )->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            // Fallback sans filtre multi-statuts
            try {
                $lits_disponibles = $this->db->query(
                    "SELECT l.id, l.nom_lit, c.nom_chambre, s.nom_service, s.id AS service_id
                     FROM lits l
                     JOIN chambres c ON l.chambre_id = c.id
                     JOIN services s ON c.service_id = s.id
                     WHERE l.statut = 'libre'
                     ORDER BY s.nom_service, c.nom_chambre, l.nom_lit"
                )->fetchAll(PDO::FETCH_ASSOC);
            } catch (Exception $e2) { $lits_disponibles = []; }
        }

        // Services hospitaliers = uniquement ceux qui ont au moins un lit (chambre)
        // On les déduit de la liste des lits pour éviter d'afficher labo, pharmacie, admin…
        $serviceIdsAvecLits = array_unique(array_column($lits_disponibles, 'service_id'));
        $services = [];
        if (!empty($serviceIdsAvecLits)) {
            try {
                $in = implode(',', array_map('intval', $serviceIdsAvecLits));
                $services = $this->db->query(
                    "SELECT s.id, s.nom_service,
                            COUNT(l.id) AS nb_lits_libres
                     FROM services s
                     JOIN chambres c ON c.service_id = s.id
                     JOIN lits l ON l.chambre_id = c.id AND l.statut IN ('libre','LIBRE','DISPONIBLE')
                     WHERE s.id IN ($in)
                     GROUP BY s.id, s.nom_service
                     ORDER BY s.nom_service"
                )->fetchAll(PDO::FETCH_ASSOC);
            } catch (Exception $e) {
                // Fallback simple
                try {
                    $in = implode(',', array_map('intval', $serviceIdsAvecLits));
                    $services = $this->db->query(
                        "SELECT id, nom_service FROM services WHERE id IN ($in) ORDER BY nom_service"
                    )->fetchAll(PDO::FETCH_ASSOC);
                } catch (Exception $e2) { $services = []; }
            }
        }

        // Motif depuis triage (pré-remplissage)
        $motifPlainte = '';
        try {
            $stmtTri = $this->db->prepare(
                "SELECT motif_plainte FROM urgences_triage
                 WHERE patient_id = ? ORDER BY id DESC LIMIT 1"
            );
            $stmtTri->execute([$patient_id]);
            $motifPlainte = $stmtTri->fetchColumn() ?: '';
        } catch (Exception $e) {}

        // Médecins disponibles pour la référence
        $medecins_disponibles = [];
        try {
            $stmtMed = $this->db->query(
                "SELECT u.id, u.nom, u.prenom, u.specialite,
                        COALESCE(s.nom_service, '') AS nom_service
                 FROM users u
                 LEFT JOIN services s ON s.id = u.service_id
                 WHERE u.role IN ('medecin','admin','medecin_chef')
                   AND u.actif = 1
                 ORDER BY u.specialite, u.nom, u.prenom"
            );
            $medecins_disponibles = $stmtMed->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            // Essai sans filtre actif
            try {
                $stmtMed2 = $this->db->query(
                    "SELECT u.id, u.nom, u.prenom, u.specialite,
                            COALESCE(s.nom_service, '') AS nom_service
                     FROM users u
                     LEFT JOIN services s ON s.id = u.service_id
                     WHERE u.role IN ('medecin','admin','medecin_chef')
                     ORDER BY u.specialite, u.nom, u.prenom"
                );
                $medecins_disponibles = $stmtMed2->fetchAll(PDO::FETCH_ASSOC);
            } catch (Exception $e2) { $medecins_disponibles = []; }
        }

        // Spécialistes actifs pour RDV
        $specialistes_rdv = [];
        try {
            $specialistes_rdv = $this->db->query(
                "SELECT id, nom, prenom, specialite FROM specialistes
                  WHERE actif = 1 ORDER BY specialite, nom"
            )->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {}

        require_once __DIR__ . '/../views/infirmier_consultant/consultation.php';
    }

    /* ─────────────────────────────────────────────────────────────────
       POST  infirmier/consultation/sauvegarder
    ───────────────────────────────────────────────────────────────── */
    public function sauvegarder(): void
    {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Méthode invalide']); exit;
        }

        $patient_id   = (int)($_POST['patient_id'] ?? 0);
        $infirmier_id = (int)($_SESSION['user_id'] ?? 0);

        if (!$patient_id) {
            echo json_encode(['success' => false, 'message' => 'Patient introuvable']); exit;
        }

        try {
            $this->db->beginTransaction();

            // ── Colonnes dynamiques (sécurité si migration incomplète) ──────
            $colsConsult = array_flip(
                $this->db->query("SHOW COLUMNS FROM consultations")->fetchAll(PDO::FETCH_COLUMN)
            );

            // Construire l'INSERT dynamiquement
            $fields = [
                'patient_id'   => $patient_id,
                'medecin_id'   => $infirmier_id,
                'type_consultation' => 'infirmiere',
                'statut'       => 'terminee',
                'date_consultation' => date('Y-m-d H:i:s'),
                'service_id'   => $_SESSION['service_id'] ?? null,
                'motif_consultation' => trim($_POST['motif_consultation'] ?? ''),
                'histoire_maladie'   => trim($_POST['histoire_maladie']  ?? '') ?: null,
                'atcd_medicaux'      => trim($_POST['atcd_medicaux']     ?? '') ?: null,
                'atcd_chirurgicaux'  => trim($_POST['atcd_chirurgicaux'] ?? '') ?: null,
                'atcd_familiaux'     => trim($_POST['atcd_familiaux']    ?? '') ?: null,
                'atcd_allergies'     => trim($_POST['atcd_allergies']    ?? '') ?: null,
                'examen_physique'    => trim($_POST['examen_physique']   ?? '') ?: null,
                'temperature'        => !empty($_POST['temperature'])           ? (float)$_POST['temperature']           : null,
                'frequence_cardiaque'=> !empty($_POST['frequence_cardiaque'])   ? (int)  $_POST['frequence_cardiaque']   : null,
                'frequence_respiratoire' => !empty($_POST['frequence_respiratoire']) ? (int)$_POST['frequence_respiratoire'] : null,
                'tension_systolique' => !empty($_POST['tension_systolique'])    ? (int)  $_POST['tension_systolique']    : null,
                'tension_diastolique'=> !empty($_POST['tension_diastolique'])   ? (int)  $_POST['tension_diastolique']   : null,
                'poids'              => !empty($_POST['poids'])                 ? (float)$_POST['poids']                 : null,
                'taille'             => !empty($_POST['taille'])                ? (float)$_POST['taille']                : null,
            ];

            // Colonnes ajoutées par migration
            $devenir       = $_POST['devenir']             ?? 'EN_COURS';
            $servDest      = !empty($_POST['devenir_service_id']) ? (int)$_POST['devenir_service_id'] : null;
            $litId         = !empty($_POST['devenir_lit_id'])     ? (int)$_POST['devenir_lit_id']     : null;
            $obsDuree      = !empty($_POST['observation_duree'])  ? min(24, max(1, (int)$_POST['observation_duree'])) : null;
            // RDV Spécialiste
            $rdvSpecId     = !empty($_POST['rdv_specialiste_id'])   ? (int)$_POST['rdv_specialiste_id']        : null;
            $rdvDate       = trim($_POST['rdv_specialiste_date']     ?? '');
            $rdvHeure      = trim($_POST['rdv_specialiste_heure']    ?? '') ?: null;
            $rdvMotif      = trim($_POST['rdv_specialiste_motif']    ?? '') ?: null;

            $optionalFields = [
                'infirmier_id'             => $infirmier_id,
                'devenir'                  => $devenir,
                'devenir_service_id'       => $servDest,
                'devenir_lit_id'           => $litId,
                'observation_motif'        => trim($_POST['observation_motif']    ?? '') ?: null,
                'observation_elements'     => trim($_POST['observation_elements'] ?? '') ?: null,
                'observation_duree_heures' => $obsDuree,
                'spo2'                     => !empty($_POST['spo2']) ? (float)$_POST['spo2'] : null,
                // Diagnostic infirmier
                'diagnostic_principal'     => trim($_POST['diagnostic_principal'] ?? '') ?: null,
                'diagnostic_infirmier'     => trim($_POST['diagnostic_infirmier'] ?? '') ?: null,
            ];

            foreach ($optionalFields as $col => $val) {
                if (isset($colsConsult[$col])) {
                    $fields[$col] = $val;
                }
            }

            // Observation : calculer la date de fin
            if ($devenir === 'OBSERVATION' && $obsDuree && isset($colsConsult['observation_fin'])) {
                $fields['observation_fin'] = date('Y-m-d H:i:s', time() + $obsDuree * 3600);
            }

            // INSERT
            $cols = array_keys($fields);
            $placeholders = array_fill(0, count($cols), '?');
            $this->db->prepare(
                "INSERT INTO consultations (" . implode(', ', $cols) . ")
                 VALUES (" . implode(', ', $placeholders) . ")"
            )->execute(array_values($fields));

            $consultation_id = $this->db->lastInsertId();

            // ── Constantes vitales → patient_parametres (suivi unifié dashboard) ──
            // Permet aux médecins urgences de voir les constantes mesurées par l'infirmière
            $ppCols = array_flip(
                $this->db->query("SHOW COLUMNS FROM patient_parametres")->fetchAll(PDO::FETCH_COLUMN)
            );
            $ppFields = [
                'patient_id'  => $patient_id,
                'user_id'     => $infirmier_id,
                'date_mesure' => date('Y-m-d H:i:s'),
            ];
            $ppMap = [
                'temperature'                      => $_POST['temperature']           ?? null,
                'frequence_cardiaque'              => $_POST['frequence_cardiaque']   ?? null,
                'frequence_respiratoire'           => $_POST['frequence_respiratoire']?? null,
                'pression_arterielle_systolique'   => $_POST['tension_systolique']    ?? null,
                'pression_arterielle_diastolique'  => $_POST['tension_diastolique']   ?? null,
                'poids'                            => $_POST['poids']                 ?? null,
                'taille'                           => $_POST['taille']                ?? null,
                'saturation_oxygene'               => $_POST['spo2']                  ?? null,
                'motif_consultation'               => trim($_POST['motif_consultation'] ?? ''),
            ];
            $hasValue = false;
            foreach ($ppMap as $col => $val) {
                if (isset($ppCols[$col]) && $val !== null && $val !== '') {
                    $ppFields[$col] = $val;
                    if ($col !== 'motif_consultation') $hasValue = true;
                }
            }
            if ($hasValue) {
                $ppC  = array_keys($ppFields);
                $ppPh = array_fill(0, count($ppC), '?');
                try {
                    $this->db->prepare(
                        "INSERT INTO patient_parametres (" . implode(',', $ppC) . ")
                         VALUES (" . implode(',', $ppPh) . ")"
                    )->execute(array_values($ppFields));
                } catch (Exception $e) {
                    error_log('[InfirmierController::sauvegarder] patient_parametres INSERT : ' . $e->getMessage());
                }
            }

            // ── Ordonnances ─────────────────────────────────────────────
            $medicamentsPost = $_POST['medicaments'] ?? [];
            if (!empty($medicamentsPost)) {
                $stmtOrdo = $this->db->prepare("
                    INSERT INTO ordonnances_pharmacie
                        (consultation_id, patient_id, medecin_id, infirmier_prescripteur_id, statut, date_creation)
                    VALUES (?, ?, ?, ?, 'EN_ATTENTE', NOW())
                ");
                $stmtOrdo->execute([$consultation_id, $patient_id, $infirmier_id, $infirmier_id]);
                $ordonnance_id = $this->db->lastInsertId();

                // Colonnes disponibles dans ordonnance_medicaments
                $colsOrdo = array_flip(
                    $this->db->query("SHOW COLUMNS FROM ordonnance_medicaments")->fetchAll(PDO::FETCH_COLUMN)
                );

                foreach ($medicamentsPost as $med) {
                    if (empty($med['nom'])) continue;
                    $ligneCols = ['ordonnance_id','nom_medicament','posologie','duree','quantite'];
                    $ligneVals = [
                        $ordonnance_id,
                        trim($med['nom']),
                        trim($med['posologie'] ?? ''),
                        trim($med['duree']     ?? ''),
                        !empty($med['quantite']) ? (int)$med['quantite'] : 1,
                    ];
                    if (!empty($med['id']) && isset($colsOrdo['medicament_id'])) {
                        $ligneCols[] = 'medicament_id';
                        $ligneVals[] = (int)$med['id'];
                    }
                    if (isset($colsOrdo['hors_stock'])) {
                        $ligneCols[] = 'hors_stock';
                        $ligneVals[] = !empty($med['hors_stock']) ? 1 : 0;
                    }
                    $ph = array_fill(0, count($ligneCols), '?');
                    $this->db->prepare(
                        "INSERT INTO ordonnance_medicaments (" . implode(',', $ligneCols) . ")
                         VALUES (" . implode(',', $ph) . ")"
                    )->execute($ligneVals);
                }
            }

            // ── Demandes de bilans ───────────────────────────────────────
            $examensPost = array_filter((array)($_POST['examens'] ?? []));
            if (!empty($examensPost)) {
                $stmtD = $this->db->prepare("
                    INSERT INTO demandes_laboratoire
                        (consultation_id, patient_id, medecin_id, statut, urgence, observations, date_creation)
                    VALUES (?, ?, ?, 'EN_ATTENTE', ?, ?, NOW())
                ");
                $stmtD->execute([
                    $consultation_id, $patient_id, $infirmier_id,
                    !empty($_POST['bilans_urgence']) ? 'URGENT' : 'NORMAL',
                    trim($_POST['bilans_observations'] ?? '') ?: null,
                ]);
                $demande_id = $this->db->lastInsertId();

                $stmtE = $this->db->prepare(
                    "INSERT INTO demande_examens (demande_id, examen_id, statut, urgent)
                     VALUES (?, ?, 'EN_ATTENTE', ?)"
                );
                $urgent = !empty($_POST['bilans_urgence']) ? 1 : 0;
                foreach ($examensPost as $eid) {
                    if ($eid) $stmtE->execute([$demande_id, (int)$eid, $urgent]);
                }
            }

            // ── Mise à jour statut patient ───────────────────────────────
            switch ($devenir) {
                case 'SORTIE':
                    $this->db->prepare(
                        "UPDATE patients SET statut_parcours = 'SORTI', statut_hosp = 'AUCUN' WHERE id = ?"
                    )->execute([$patient_id]);
                    break;

                case 'HOSPITALISATION':
                    $this->db->prepare(
                        "UPDATE patients SET statut_parcours = 'HOSPITALISE',
                         statut_hosp = 'A_HOSPITALISER',
                         service_id = COALESCE(?, service_id) WHERE id = ?"
                    )->execute([$servDest, $patient_id]);

                    if ($litId) {
                        $this->assignerLit($patient_id, $litId, $consultation_id);
                    }
                    break;

                case 'OBSERVATION':
                    try {
                        $this->db->prepare(
                            "UPDATE patients SET statut_parcours = 'EN_OBSERVATION' WHERE id = ?"
                        )->execute([$patient_id]);
                    } catch (Exception $e) {}
                    break;

                case 'RDV_SPECIALISTE':
                    // Créer le RDV chez le spécialiste
                    if ($rdvSpecId && $rdvDate) {
                        try {
                            $ordre = (int)$this->db->prepare(
                                "SELECT COALESCE(MAX(ordre_passage),0)+1 FROM rdv_specialistes
                                  WHERE specialiste_id=? AND date_rdv=? AND statut NOT IN ('ANNULE','REPORTE')"
                            )->execute([$rdvSpecId, $rdvDate]) ?
                                $this->db->query(
                                    "SELECT COALESCE(MAX(ordre_passage),0)+1 FROM rdv_specialistes
                                      WHERE specialiste_id=$rdvSpecId AND date_rdv='$rdvDate'
                                        AND statut NOT IN ('ANNULE','REPORTE')"
                                )->fetchColumn() : 1;

                            $this->db->prepare("
                                INSERT INTO rdv_specialistes
                                    (patient_id, specialiste_id, date_rdv, heure_rdv, motif,
                                     statut, source, prescrit_par, ordre_passage)
                                VALUES (?, ?, ?, ?, ?, 'EN_ATTENTE', 'SECRETAIRE', ?, ?)
                            ")->execute([
                                $patient_id, $rdvSpecId, $rdvDate, $rdvHeure,
                                $rdvMotif ?: $motif_consultation,
                                $infirmier_id, $ordre
                            ]);
                        } catch (Exception $e) {
                            error_log('[sauvegarder RDV_SPECIALISTE] ' . $e->getMessage());
                        }
                    }
                    // Marquer le patient comme sorti après la consultation
                    try {
                        $this->db->prepare(
                            "UPDATE patients SET statut_parcours='SORTI', statut_hosp='AUCUN' WHERE id=?"
                        )->execute([$patient_id]);
                    } catch (Exception $e) {}
                    break;
            }

            $this->db->commit();

            try {
                $this->audit->logAction('INSERT', 'consultations', $consultation_id, null,
                    "Consultation infirmière — devenir: $devenir");
            } catch (Exception $e) {}

            echo json_encode([
                'success'         => true,
                'consultation_id' => $consultation_id,
                'devenir'         => $devenir,
                'redirect'        => BASE_URL . 'patients/dossier/' . $patient_id . '?from=consultation_infirmiere',
                'message'         => 'Consultation enregistrée avec succès.',
            ]);

        } catch (Exception $e) {
            $this->db->rollBack();
            error_log('[InfirmierController::sauvegarder] ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Erreur : ' . $e->getMessage()]);
        }
        exit;
    }

    /* ─────────────────────────────────────────────────────────────────
       POST  infirmier/consultation/decision-observation/{id}
    ───────────────────────────────────────────────────────────────── */
    public function decisionObservation(int $consultation_id): void
    {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Méthode invalide']); exit;
        }

        $decision = $_POST['decision'] ?? '';
        $notes    = trim($_POST['notes'] ?? '');
        $litId    = !empty($_POST['lit_id']) ? (int)$_POST['lit_id'] : null;

        if (!in_array($decision, ['SORTIE', 'HOSPITALISATION'])) {
            echo json_encode(['success' => false, 'message' => 'Décision invalide']); exit;
        }

        try {
            $this->db->beginTransaction();

            $c = $this->db->prepare("SELECT patient_id FROM consultations WHERE id = ?");
            $c->execute([$consultation_id]);
            $row = $c->fetch(PDO::FETCH_ASSOC);
            if (!$row) {
                echo json_encode(['success' => false, 'message' => 'Consultation introuvable']); exit;
            }
            $patient_id = $row['patient_id'];

            // MAJ consultation (colonnes optionnelles)
            $colsConsult = array_flip(
                $this->db->query("SHOW COLUMNS FROM consultations")->fetchAll(PDO::FETCH_COLUMN)
            );
            $sets = ['devenir = ?'];
            $vals = [$decision];
            if (isset($colsConsult['observation_decision']))       { $sets[] = 'observation_decision = ?';      $vals[] = $decision; }
            if (isset($colsConsult['observation_decision_date']))  { $sets[] = 'observation_decision_date = NOW()'; }
            if (isset($colsConsult['observation_decision_notes'])) { $sets[] = 'observation_decision_notes = ?'; $vals[] = $notes; }
            $vals[] = $consultation_id;

            $this->db->prepare(
                "UPDATE consultations SET " . implode(', ', $sets) . " WHERE id = ?"
            )->execute($vals);

            if ($decision === 'SORTIE') {
                $this->db->prepare(
                    "UPDATE patients SET statut_parcours = 'SORTI', statut_hosp = 'AUCUN' WHERE id = ?"
                )->execute([$patient_id]);
            } else {
                $this->db->prepare(
                    "UPDATE patients SET statut_parcours = 'HOSPITALISE', statut_hosp = 'A_HOSPITALISER' WHERE id = ?"
                )->execute([$patient_id]);
                if ($litId) $this->assignerLit($patient_id, $litId, $consultation_id);
            }

            $this->db->commit();
            echo json_encode(['success' => true, 'message' => 'Décision enregistrée.']);

        } catch (Exception $e) {
            $this->db->rollBack();
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    /* ─────────────────────────────────────────────────────────────────
       POST  infirmier/hospitaliser-depuis-consultation
       Hospitalise un patient à partir d'une consultation infirmière existante
    ───────────────────────────────────────────────────────────────── */
    public function hospitaliserDepuisConsultation(): void
    {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Méthode invalide']); exit;
        }

        $body = json_decode(file_get_contents('php://input'), true) ?? [];

        $consultation_id = (int)($body['consultation_id'] ?? 0);
        $patient_id      = (int)($body['patient_id']      ?? 0);
        $service_id      = (int)($body['service_id']      ?? 0);
        $lit_id          = !empty($body['lit_id'])     ? (int)$body['lit_id']     : null;
        $medecin_id      = !empty($body['medecin_id']) ? (int)$body['medecin_id'] : null;
        $motif           = trim($body['motif'] ?? '');

        if (!$consultation_id || !$patient_id || !$service_id) {
            echo json_encode(['success' => false, 'message' => 'Données manquantes (consultation, patient ou service).']); exit;
        }

        try {
            $this->db->beginTransaction();

            // Vérifier que la consultation appartient bien à cet infirmier
            $stmt = $this->db->prepare(
                "SELECT id, patient_id FROM consultations
                 WHERE id = ? AND patient_id = ? AND type_consultation = 'infirmiere'"
            );
            $stmt->execute([$consultation_id, $patient_id]);
            if (!$stmt->fetch()) {
                $this->db->rollBack();
                echo json_encode(['success' => false, 'message' => 'Consultation introuvable ou non autorisée.']); exit;
            }

            // Vérifier que le patient n'est pas déjà hospitalisé
            $existeHosp = $this->db->prepare(
                "SELECT id FROM hospitalisations WHERE patient_id = ? AND statut = 'en_cours' LIMIT 1"
            );
            $existeHosp->execute([$patient_id]);
            if ($existeHosp->fetch()) {
                $this->db->rollBack();
                echo json_encode(['success' => false, 'message' => 'Ce patient est déjà hospitalisé.']); exit;
            }

            // Créer l'hospitalisation
            $colsHosp = array_flip(
                $this->db->query("SHOW COLUMNS FROM hospitalisations")->fetchAll(PDO::FETCH_COLUMN)
            );
            $hFields = [
                'patient_id'       => $patient_id,
                'service_id'       => $service_id,
                'statut'           => 'en_cours',
                'date_admission'   => date('Y-m-d H:i:s'),
            ];
            if ($lit_id)     $hFields['lit_id']              = $lit_id;
            if ($medecin_id && isset($colsHosp['medecin_responsable'])) {
                $hFields['medecin_responsable'] = $medecin_id;
            }
            if ($motif && isset($colsHosp['motif_hospitalisation'])) {
                $hFields['motif_hospitalisation'] = $motif;
            }

            $hCols = array_keys($hFields);
            $hPh   = array_fill(0, count($hCols), '?');
            $this->db->prepare(
                "INSERT INTO hospitalisations (" . implode(',', $hCols) . ")
                 VALUES (" . implode(',', $hPh) . ")"
            )->execute(array_values($hFields));
            $hosp_id = $this->db->lastInsertId();

            // Marquer le lit comme occupé
            if ($lit_id) {
                $this->db->prepare(
                    "UPDATE lits SET statut = 'occupe', occupied_by_patient_id = ? WHERE id = ?"
                )->execute([$patient_id, $lit_id]);
            }

            // Mettre à jour le patient
            $this->db->prepare(
                "UPDATE patients
                 SET statut_parcours = 'HOSPITALISE',
                     statut_hosp     = 'HOSPITALISE',
                     service_id      = ?
                 WHERE id = ?"
            )->execute([$service_id, $patient_id]);

            // Mettre à jour le devenir de la consultation
            $colsConsult = array_flip(
                $this->db->query("SHOW COLUMNS FROM consultations")->fetchAll(PDO::FETCH_COLUMN)
            );
            if (isset($colsConsult['devenir'])) {
                $this->db->prepare(
                    "UPDATE consultations SET devenir = 'HOSPITALISATION'
                     WHERE id = ? AND patient_id = ?"
                )->execute([$consultation_id, $patient_id]);
            }

            $this->db->commit();

            try {
                $this->audit->logAction('INSERT', 'hospitalisations', $hosp_id, null,
                    "Hospitalisation depuis consultation infirmière #$consultation_id — service_id=$service_id");
            } catch (Exception $e) {}

            echo json_encode([
                'success' => true,
                'hosp_id' => $hosp_id,
                'message' => 'Patient hospitalisé avec succès' . ($lit_id ? ' et lit assigné.' : '.'),
            ]);

        } catch (Exception $e) {
            $this->db->rollBack();
            error_log('[InfirmierController::hospitaliserDepuisConsultation] ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Erreur : ' . $e->getMessage()]);
        }
        exit;
    }

    /* ─────────────────────────────────────────────────────────────────
       Helpers privés
    ───────────────────────────────────────────────────────────────── */
    private function getPatientComplet(int $id): ?array
    {
        $stmt = $this->db->prepare("
            SELECT p.*, s.nom_service
            FROM patients p
            LEFT JOIN services s ON p.service_id = s.id
            WHERE p.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    private function assignerLit(int $patient_id, int $lit_id, int $consultation_id): void
    {
        try {
            // Marquer le lit comme occupé
            $this->db->prepare(
                "UPDATE lits SET statut = 'occupe', occupied_by_patient_id = ? WHERE id = ?"
            )->execute([$patient_id, $lit_id]);

            // Info du lit
            $litInfo = $this->db->prepare(
                "SELECT l.*, c.service_id FROM lits l JOIN chambres c ON l.chambre_id = c.id WHERE l.id = ?"
            );
            $litInfo->execute([$lit_id]);
            $lit = $litInfo->fetch(PDO::FETCH_ASSOC);

            if ($lit) {
                // Hospitalisation (sans consultation_id ni admis_par — colonnes inexistantes)
                $colsHosp = array_flip(
                    $this->db->query("SHOW COLUMNS FROM hospitalisations")->fetchAll(PDO::FETCH_COLUMN)
                );
                $hFields = [
                    'patient_id'   => $patient_id,
                    'lit_id'       => $lit_id,
                    'service_id'   => $lit['service_id'],
                    'statut'       => 'en_cours',  /* ← correction : ACTIF n'est pas dans l'ENUM */
                    'date_admission' => date('Y-m-d H:i:s'),
                ];
                if (isset($colsHosp['medecin_responsable'])) {
                    $hFields['medecin_responsable'] = $_SESSION['user_id'] ?? null;
                }
                $hCols = array_keys($hFields);
                $hPh   = array_fill(0, count($hCols), '?');
                $this->db->prepare(
                    "INSERT INTO hospitalisations (" . implode(',', $hCols) . ")
                     VALUES (" . implode(',', $hPh) . ")"
                )->execute(array_values($hFields));

                // MAJ service patient
                $this->db->prepare(
                    "UPDATE patients SET service_id = ?, statut_hosp = 'HOSPITALISE' WHERE id = ?"
                )->execute([$lit['service_id'], $patient_id]);

                // MAJ consultation si colonne existe
                if (isset(array_flip(
                    $this->db->query("SHOW COLUMNS FROM consultations")->fetchAll(PDO::FETCH_COLUMN)
                )['devenir_lit_id'])) {
                    $this->db->prepare(
                        "UPDATE consultations SET devenir_lit_id = ? WHERE id = ?"
                    )->execute([$lit_id, $consultation_id]);
                }
            }
        } catch (Exception $e) {
            error_log('[InfirmierController::assignerLit] ' . $e->getMessage());
        }
    }
}
