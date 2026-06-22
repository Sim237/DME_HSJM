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
FICHIER : app/controllers/AccueilController.php
CONTRÔLEUR DU MODULE ACCUEIL (RECEPTION)
============================================================================ */

// IMPORTANT : Charger le contrôleur parent
require_once __DIR__ . '/UnifiedController.php';

class AccueilController extends UnifiedController {

    public function __construct() {
        parent::__construct();
    }

    /**
     * Dashboard de l'accueil
     */
   public function index() {
    $db = (new Database())->getConnection();

    // Nettoyage automatique : patients restés en file PARAMETRES depuis > 24h sont retirés
    require_once __DIR__ . '/../services/WorkflowResetService.php';
    (new WorkflowResetService($db))->cleanWaitingListsIfStale();

    // RDV spécialistes du jour (table rdv_specialistes, pas agenda_medical)
    $sql = "SELECT p.id            AS id,
                   p.nom, p.prenom, p.dossier_numero,
                   rs.motif,
                   CONCAT(rs.date_rdv, ' ', COALESCE(rs.heure_rdv, '00:00:00')) AS date_rdv,
                   rs.ordre_passage,
                   sp.specialite
            FROM rdv_specialistes rs
            JOIN patients p      ON rs.patient_id     = p.id
            JOIN specialistes sp ON rs.specialiste_id  = sp.id
            WHERE rs.date_rdv = CURDATE()
              AND rs.statut NOT IN ('ANNULE', 'REPORTE', 'TERMINE')
            ORDER BY rs.heure_rdv ASC, rs.ordre_passage ASC";

    $stmt = $db->query($sql);
    $rdvs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    require_once __DIR__ . '/../views/accueil/dashboard.php';
}
    /**
     * Enregistre un patient et démarre sa visite
     */
    public function savePatient() {
        // Cette méthode devrait normalement appeler PatientModel pour le store
        // Mais pour suivre votre logique de redirection immédiate :

        $db = (new Database())->getConnection();

        // Simulation de la récupération de l'ID après insertion (à adapter selon votre formulaire)
        $patient_id = $_POST['patient_id'] ?? null;

        if ($patient_id) {
            $this->commencerVisite($patient_id);
        } else {
            header('Location: ' . BASE_URL . 'accueil?error=missing_id');
        }
    }

    /**
     * Logique de début de visite avec génération du numéro d'ordre (Pair/Impair)
     */
    /**
     * Vérifie (une seule fois par session) que les colonnes requises existent.
     * Les migrations DDL sont désormais dans migrations/003_app_migrations.sql.
     */
    private function migrateMaterniteColumns(\PDO $db): void {
        // Guard : une seule vérification par session pour éviter les ALTER à chaque requête
        if (!empty($_SESSION['_migration_maternite_ok'])) return;

        $missing = [];
        $cols = $db->query(
            "SELECT COLUMN_NAME FROM information_schema.COLUMNS
              WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'patients'
                AND COLUMN_NAME IN ('femme_enceinte','parametres_requis')"
        )->fetchAll(\PDO::FETCH_COLUMN);

        if (!in_array('femme_enceinte', $cols)) {
            try { $db->exec("ALTER TABLE patients ADD COLUMN femme_enceinte TINYINT(1) NOT NULL DEFAULT 0 AFTER sexe"); }
            catch (\Throwable $e) {}
        }
        if (!in_array('parametres_requis', $cols)) {
            try { $db->exec("ALTER TABLE patients ADD COLUMN parametres_requis TINYINT(1) NOT NULL DEFAULT 0 AFTER statut_parcours"); }
            catch (\Throwable $e) {}
        }

        // ENUM extension — uniquement si la valeur n'existe pas encore
        try {
            $colType = $db->query(
                "SELECT COLUMN_TYPE FROM information_schema.COLUMNS
                  WHERE TABLE_SCHEMA = DATABASE()
                    AND TABLE_NAME   = 'patients'
                    AND COLUMN_NAME  = 'statut_parcours'"
            )->fetchColumn();
            if ($colType && stripos($colType, 'enum') === 0
                         && strpos($colType, 'PARAMETRES_MATERNITE') === false) {
                $newType = str_replace("'PARAMETRES'", "'PARAMETRES','PARAMETRES_MATERNITE'", $colType);
                $db->exec("ALTER TABLE patients MODIFY COLUMN statut_parcours $newType");
            }
        } catch (\Throwable $e) {}

        $_SESSION['_migration_maternite_ok'] = true;
    }

    /**
     * Auto-migration : garantit que les colonnes de facturation / circuit existent
     * sur la table patients. Évite les "Unknown column" en cas de dérive de schéma
     * (ex. après une restauration depuis une base de récupération incomplète).
     */
    private function ensurePatientBillingColumns($db) {
        if (!empty($_SESSION['_migration_billing_ok'])) return;
        try {
            $existing = $db->query(
                "SELECT COLUMN_NAME FROM information_schema.COLUMNS
                  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'patients'"
            )->fetchAll(\PDO::FETCH_COLUMN);

            $needed = [
                'type_client'            => "VARCHAR(30) NULL",
                'nom_assurance'          => "VARCHAR(150) NULL",
                'numero_assure'          => "VARCHAR(100) NULL",
                'numero_compte_sage'     => "VARCHAR(100) NULL",
                'numero_bon'             => "VARCHAR(100) NULL",
                'organisme_payeur'       => "VARCHAR(150) NULL",
                'matricule_agent'        => "VARCHAR(100) NULL",
                'infirmerie_origine'     => "VARCHAR(150) NULL",
                'circuit'                => "VARCHAR(20) NULL DEFAULT 'STANDARD'",
                'situation_matrimoniale' => "VARCHAR(30) NULL",
                'contact_nom'            => "VARCHAR(150) NULL",
                'contact_telephone'      => "VARCHAR(30) NULL",
            ];
            foreach ($needed as $col => $ddl) {
                if (!in_array($col, $existing)) {
                    try { $db->exec("ALTER TABLE patients ADD COLUMN `$col` $ddl"); }
                    catch (\Throwable $e) { error_log("[ensurePatientBillingColumns] $col: " . $e->getMessage()); }
                }
            }
        } catch (\Throwable $e) {
            error_log('[ensurePatientBillingColumns] ' . $e->getMessage());
        }
        $_SESSION['_migration_billing_ok'] = true;
    }

    public function enregistrerPatient() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $db = (new Database())->getConnection();
        $this->migrateMaterniteColumns($db);
        $this->ensurePatientBillingColumns($db);

        try {
            $db->beginTransaction();

            // --- 1. GÉNÉRATION DU NUMÉRO DE DOSSIER UNIQUE ---
            $annee2 = date('y'); // 2 chiffres : 26
            $prefix = "HSJM$annee2";
            // MAX() au lieu de COUNT() : résistant aux suppressions + FOR UPDATE contre les doublons concurrents
            $stmtMax = $db->prepare(
                "SELECT COALESCE(MAX(CAST(SUBSTRING(dossier_numero, 7) AS UNSIGNED)), 0)
                 FROM patients WHERE dossier_numero LIKE ? FOR UPDATE"
            );
            $stmtMax->execute(["$prefix%"]);
            $next_id        = (int)$stmtMax->fetchColumn() + 1;
            $dossier_numero = $prefix . str_pad($next_id, 4, '0', STR_PAD_LEFT);

            // --- 2. INSERTION DU PATIENT ---
            $allowed_types = ['BON_PRISE_EN_CHARGE','PAYANT_COMPTANT','ASSURANCE','FAMILLE_PHP','AGENTS_PHP'];
            $type_client = in_array($_POST['type_client'] ?? '', $allowed_types)
                           ? $_POST['type_client'] : 'PAYANT_COMPTANT';

            // ── Validation des champs obligatoires selon le circuit ────────
            $erreurs_circuit = [];
            if ($type_client === 'ASSURANCE') {
                if (empty(trim($_POST['nom_assurance'] ?? ''))) {
                    $erreurs_circuit[] = 'Le nom de l\'assurance est obligatoire pour un patient assuré.';
                }
                if (empty(trim($_POST['numero_assure'] ?? ''))) {
                    $erreurs_circuit[] = 'Le numéro d\'assuré est obligatoire pour un patient assuré.';
                }
            }
            if ($type_client === 'BON_PRISE_EN_CHARGE') {
                if (empty(trim($_POST['numero_bon'] ?? ''))) {
                    $erreurs_circuit[] = 'Le numéro du bon de prise en charge est obligatoire.';
                }
                if (empty(trim($_POST['organisme_payeur'] ?? ''))) {
                    $erreurs_circuit[] = 'L\'organisme émetteur du bon est obligatoire.';
                }
            }
            if ($type_client === 'AGENTS_PHP') {
                if (empty(trim($_POST['matricule_agent'] ?? ''))) {
                    $erreurs_circuit[] = 'Le matricule agent est obligatoire pour un agent PHP.';
                }
            }
            if (!empty($erreurs_circuit)) {
                $db->rollBack();
                // Réponse JSON pour l'appel AJAX du modal
                if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) || !empty($_POST['_ajax'])) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'erreurs' => $erreurs_circuit,
                                      'message' => implode(' | ', $erreurs_circuit)]);
                } else {
                    $error_circuit = implode('<br>', $erreurs_circuit);
                    $_SESSION['flash_error'] = $error_circuit;
                    header('Location: ' . BASE_URL . 'accueil?error=circuit');
                }
                exit;
            }

            // ── Routage circuit ───────────────────────────────────────────
            // AGENTS_PHP  → bureau Paramètres PHP dédié          (circuit = 'PHP')
            // FAMILLE_PHP, PAYANT_COMPTANT, ASSURANCE, BON_PRISE_EN_CHARGE
            //             → bureaux B1/B2 par parité du ticket   (circuit = 'STANDARD')
            //               impair → B1, pair → B2
            $circuit = ($type_client === 'AGENTS_PHP') ? 'PHP' : 'STANDARD';

            // Femme enceinte → bureau paramètres maternité (radio OUI=1 / NON=0)
            $femme_enceinte = (($_POST['sexe'] ?? '') === 'F' && ($_POST['femme_enceinte'] ?? '') === '1') ? 1 : 0;

            // Champs spécifiques par type_client
            $nom_assurance      = in_array($type_client, ['ASSURANCE'])
                                  ? trim($_POST['nom_assurance'] ?? '') : null;
            $numero_assure      = in_array($type_client, ['ASSURANCE'])
                                  ? trim($_POST['numero_assure'] ?? '') : null;
            $numero_bon         = ($type_client === 'BON_PRISE_EN_CHARGE')
                                  ? trim($_POST['numero_bon'] ?? '') : null;
            $organisme_payeur   = in_array($type_client, ['BON_PRISE_EN_CHARGE', 'ASSURANCE'])
                                  ? trim($_POST['organisme_payeur'] ?? $_POST['nom_assurance'] ?? '') : null;
            $matricule_agent    = ($type_client === 'AGENTS_PHP')
                                  ? trim($_POST['matricule_agent'] ?? '') : null;
            $infirmerie_origine = ($type_client === 'AGENTS_PHP')
                                  ? trim($_POST['infirmerie_origine'] ?? '') : null;

            // Date de naissance : priorité au champ date, fallback âge estimatif
            if (empty($_POST['date_naissance']) && !empty($_POST['age_estimatif'])) {
                $_POST['date_naissance'] = (date('Y') - (int)$_POST['age_estimatif']) . '-01-01';
            }
            $date_naissance = !empty($_POST['date_naissance']) ? $_POST['date_naissance'] : '1900-01-01';

            $numero_compte_sage = trim($_POST['numero_compte_sage'] ?? '') ?: null;

            $sqlP = "INSERT INTO patients (
                        dossier_numero, nom, prenom, date_naissance, sexe, femme_enceinte,
                        telephone, adresse, profession, situation_matrimoniale,
                        groupe_sanguin, contact_nom, contact_telephone,
                        type_client, nom_assurance, numero_assure, numero_compte_sage,
                        numero_bon, organisme_payeur,
                        matricule_agent, infirmerie_origine,
                        circuit, statut_parcours, created_at
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'ACCUEIL', NOW())";

            $stmtP = $db->prepare($sqlP);
            $stmtP->execute([
                $dossier_numero,
                strtoupper(trim($_POST['nom'] ?? '')),
                trim($_POST['prenom'] ?? ''),
                $date_naissance,
                $_POST['sexe'] ?? 'M',
                $femme_enceinte,
                $_POST['telephone'] ?? null,
                $_POST['adresse'] ?? null,
                $_POST['profession'] ?? null,
                $_POST['situation_matrimoniale'] ?? 'celibataire',
                (!empty($_POST['groupe_sanguin']) ? $_POST['groupe_sanguin'] : null),
                $_POST['contact_nom'] ?? null,
                $_POST['contact_telephone'] ?? null,
                $type_client,
                $nom_assurance,
                $numero_assure,
                $numero_compte_sage,
                $numero_bon,
                $organisme_payeur,
                $matricule_agent,
                $infirmerie_origine,
                $circuit,
            ]);

            $patient_id = $db->lastInsertId();

            // --- 3. GÉNÉRATION DU NUMÉRO D'ORDRE (TICKET) ---
            require_once __DIR__ . '/../services/WorkflowResetService.php';
            $num_ordre = (new WorkflowResetService($db))->getNextOrderNumber();

            // --- 4. ROUTAGE AUTOMATIQUE PÉDIATRIQUE (0–15 ans) ---
            // Si le patient est un enfant (hors femme enceinte), on l'envoie directement
            // dans la file de TOUS les médecins pédiatres du service pédiatrie,
            // sans passer par le bureau paramètres.
            $pediatrie_routing    = false;
            $pediatric_service_id = null;

            if (!$femme_enceinte && $date_naissance !== '1900-01-01') {
                try {
                    $age_calc = (int)date_diff(date_create($date_naissance), date_create('now'))->y;
                    if ($age_calc >= 0 && $age_calc <= 15) {
                        // Chercher le service pédiatrie (insensible aux accents)
                        $stmtPed = $db->query("
                            SELECT id FROM services
                            WHERE LOWER(nom_service) LIKE '%p%diatr%'
                               OR LOWER(nom_service) LIKE '%pediatr%'
                               OR LOWER(nom_service) LIKE '%pédiatr%'
                            LIMIT 1
                        ");
                        $pedRow = $stmtPed->fetchColumn();
                        if ($pedRow) {
                            $pediatric_service_id = (int)$pedRow;
                            $pediatrie_routing    = true;
                        }
                    }
                } catch (Exception $e) {
                    error_log('[AccueilController] Pediatrie routing error: ' . $e->getMessage());
                }
            }

            // ─── 5. SERVICE DE DESTINATION (sélectionné par la secrétaire) ───────────
            $service_id_destination = (int)($_POST['service_id_destination'] ?? 0);
            $is_php = in_array($type_client, ['FAMILLE_PHP', 'AGENTS_PHP']);

            // Priorité de routage :
            //  1. Femme enceinte          → PARAMETRES_MATERNITE
            //  2. PHP (famille / agents)  → PARAMETRES (bureau dédié)
            //  3. Service sélectionné     → ATTENTE_CONSULTATION direct + parametres_requis=1
            //     (inclut les enfants dont le service pédiatrie est auto-sélectionné en JS)
            //  4. Pédiatrie auto-fallback → PARAMETRES + service_id=ped + parametres_requis=1
            //     (si la secrétaire n'a pas sélectionné le service mais que l'enfant est détecté)
            //  5. Défaut                  → PARAMETRES standard

            if ($femme_enceinte) {
                $stmtUpdate = $db->prepare("
                    UPDATE patients
                    SET numero_ordre = ?, statut_parcours = 'PARAMETRES_MATERNITE',
                        date_mise_en_parametres = NOW()
                    WHERE id = ?
                ");
                $stmtUpdate->execute([$num_ordre, $patient_id]);

            } elseif ($is_php) {
                $stmtUpdate = $db->prepare("
                    UPDATE patients
                    SET numero_ordre = ?, statut_parcours = 'PARAMETRES',
                        date_mise_en_parametres = NOW()
                    WHERE id = ?
                ");
                $stmtUpdate->execute([$num_ordre, $patient_id]);

            } elseif ($service_id_destination > 0) {
                // Routing direct : le patient entre dans la file du service choisi
                // parametres_requis=1 → badge "⏳ Paramètres à prendre" chez le médecin
                $stmtUpdate = $db->prepare("
                    UPDATE patients
                    SET numero_ordre = ?,
                        statut_parcours = 'ATTENTE_CONSULTATION',
                        service_id      = ?,
                        medecin_id      = NULL,
                        parametres_requis = 1,
                        date_mise_en_parametres = NOW()
                    WHERE id = ?
                ");
                $stmtUpdate->execute([$num_ordre, $service_id_destination, $patient_id]);

            } elseif ($pediatrie_routing && $pediatric_service_id) {
                // Fallback pédiatrique : form n'a pas envoyé de service_id
                // → PARAMETRES mais visible par tous les pédiatres
                $stmtUpdate = $db->prepare("
                    UPDATE patients
                    SET numero_ordre = ?,
                        statut_parcours = 'PARAMETRES',
                        service_id      = ?,
                        medecin_id      = NULL,
                        parametres_requis = 1,
                        date_mise_en_parametres = NOW()
                    WHERE id = ?
                ");
                $stmtUpdate->execute([$num_ordre, $pediatric_service_id, $patient_id]);

            } else {
                $stmtUpdate = $db->prepare("
                    UPDATE patients
                    SET numero_ordre = ?, statut_parcours = 'PARAMETRES',
                        date_mise_en_parametres = NOW()
                    WHERE id = ?
                ");
                $stmtUpdate->execute([$num_ordre, $patient_id]);
            }

            $db->commit();

            // Redirection avec indicateur pédiatrie si applicable
            $redir = BASE_URL . 'accueil?success=patient_cree&ticket=' . $num_ordre
                   . '&dossier=' . $dossier_numero;
            if ($pediatrie_routing) {
                $redir .= '&pediatrie=1';
            }
            header('Location: ' . $redir);
            exit;

        } catch (Exception $e) {
            if ($db->inTransaction()) $db->rollBack();
            error_log('[AccueilController::enregistrerPatient] ' . $e->getMessage());
            // Violation UNIQUE sur dossier_numero (race condition) → message spécifique
            if ((int)$e->getCode() === 23000 || strpos($e->getMessage(), 'Duplicate') !== false) {
                $_SESSION['flash_error'] = "Numéro de dossier en conflit. Veuillez resoumettre le formulaire.";
                header('Location: ' . BASE_URL . 'accueil?error=doublon_dossier');
            } else {
                $_SESSION['flash_error'] = "Erreur lors de l'enregistrement : "
                    . htmlspecialchars($e->getMessage());
                header('Location: ' . BASE_URL . 'accueil?error=enregistrement');
            }
            exit;
        }
    }
}

    /**
     * Vérification finale doublon avant soumission du formulaire d'enregistrement.
     * Critère renforcé : nom + prénom exacts (insensible à la casse)
     *   + correspondance sur date_naissance OU téléphone.
     * Retourne { found: bool, patients: [...] }
     */
    public function checkDoublonFinal(): void {
        header('Content-Type: application/json');
        $db  = (new Database())->getConnection();
        $nom = strtoupper(trim($_GET['nom']            ?? ''));
        $pre = trim($_GET['prenom']                    ?? '');
        $ddn = trim($_GET['date_naissance']            ?? '');
        $tel = trim($_GET['telephone']                 ?? '');

        if (strlen($nom) < 2) {
            echo json_encode(['found' => false, 'patients' => []]);
            exit;
        }

        // Construire la condition : nom+prénom exacts ET (date_naissance OU téléphone correspond)
        $whereParts = ["UPPER(TRIM(p.nom)) = ? AND UPPER(TRIM(p.prenom)) = ?"];
        $params     = [$nom, strtoupper(trim($pre))];

        $dateOrTel = [];
        if ($ddn) { $dateOrTel[] = "p.date_naissance = ?"; $params[] = $ddn; }
        if ($tel)  { $dateOrTel[] = "p.telephone LIKE ?";  $params[] = '%' . $tel . '%'; }

        if (!empty($dateOrTel)) {
            $whereParts[] = '(' . implode(' OR ', $dateOrTel) . ')';
        }

        $sql = "SELECT p.id, p.dossier_numero, p.nom, p.prenom,
                       p.date_naissance, p.telephone, p.statut_parcours,
                       (SELECT COUNT(*) FROM consultations WHERE patient_id = p.id) AS nb_consultations
                FROM patients p
                WHERE " . implode(' AND ', $whereParts) . "
                ORDER BY p.created_at DESC
                LIMIT 5";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $patients = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(['found' => count($patients) > 0, 'patients' => $patients]);
        exit;
    }

    /**
     * Recherche doublon en temps réel (AJAX JSON)
     * Cherche par nom + prénom, ou téléphone
     */
    public function searchDoublon() {
        header('Content-Type: application/json');
        $db  = (new Database())->getConnection();
        $nom = trim($_GET['nom'] ?? '');
        $pre = trim($_GET['prenom'] ?? '');
        $tel = trim($_GET['telephone'] ?? '');

        if (strlen($nom) < 2 && strlen($tel) < 6) {
            echo json_encode([]); exit;
        }

        $params = [];
        $where  = ['p.actif = 1'];

        if ($nom && $pre) {
            $where[]  = "(p.nom LIKE ? AND p.prenom LIKE ?)";
            $params[] = $nom . '%';
            $params[] = $pre . '%';
        } elseif ($nom) {
            $where[]  = "p.nom LIKE ?";
            $params[] = $nom . '%';
        }
        if ($tel) {
            $where[]  = "p.telephone LIKE ?";
            $params[] = '%' . $tel . '%';
        }

        $sql = "SELECT p.id, p.dossier_numero, p.nom, p.prenom, p.date_naissance, p.sexe,
                       p.telephone, p.type_client, p.statut_parcours,
                       (SELECT COUNT(*) FROM consultations WHERE patient_id = p.id) AS nb_consultations
                FROM patients p
                WHERE " . implode(' AND ', $where) . "
                ORDER BY p.created_at DESC
                LIMIT 5";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        exit;
    }

    /**
     * Retourne les données d'un patient existant (AJAX JSON)
     */
    public function getPatient($id) {
        header('Content-Type: application/json');
        $db = (new Database())->getConnection();
        $stmt = $db->prepare("
            SELECT p.*,
                   (SELECT MAX(created_at) FROM patients WHERE id = p.id) as date_creation,
                   (SELECT COUNT(*) FROM consultations WHERE patient_id = p.id) as nb_consultations
            FROM patients p
            WHERE p.id = ? AND p.actif = 1
        ");
        $stmt->execute([(int)$id]);
        $patient = $stmt->fetch(PDO::FETCH_ASSOC);
        echo json_encode($patient ?: null);
        exit;
    }

    /**
     * Met à jour les infos d'un ancien patient et démarre une nouvelle visite
     */
    public function nouvelleVisite() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . 'accueil');
            exit;
        }
        $db = (new Database())->getConnection();
        $this->migrateMaterniteColumns($db);
        try {
            $db->beginTransaction();

            $id          = (int)$_POST['patient_id'];
            $type_client = $_POST['type_client'] ?? 'PAYANT_COMPTANT';
            $allowed     = ['BON_PRISE_EN_CHARGE','PAYANT_COMPTANT','ASSURANCE','FAMILLE_PHP','AGENTS_PHP'];
            if (!in_array($type_client, $allowed)) $type_client = 'PAYANT_COMPTANT';

            $nom_assurance      = ($type_client === 'ASSURANCE')          ? trim($_POST['nom_assurance']      ?? '') ?: null : null;
            $numero_assure      = ($type_client === 'ASSURANCE')          ? trim($_POST['numero_assure']      ?? '') ?: null : null;
            $numero_bon         = ($type_client === 'BON_PRISE_EN_CHARGE') ? trim($_POST['numero_bon']         ?? '') ?: null : null;
            $organisme_payeur   = in_array($type_client, ['BON_PRISE_EN_CHARGE','ASSURANCE'])
                                  ? trim($_POST['organisme_payeur'] ?? $_POST['nom_assurance'] ?? '') ?: null : null;
            $matricule_agent    = ($type_client === 'AGENTS_PHP')         ? trim($_POST['matricule_agent']    ?? '') ?: null : null;
            $infirmerie_origine = ($type_client === 'AGENTS_PHP')         ? trim($_POST['infirmerie_origine'] ?? '') ?: null : null;
            // Seul AGENTS_PHP va au bureau PHP ; FAMILLE_PHP suit la parité B1/B2
            $circuit_nv         = ($type_client === 'AGENTS_PHP') ? 'PHP' : 'STANDARD';

            // Mise à jour des informations du patient
            $db->prepare("UPDATE patients SET
                nom                  = ?,
                prenom               = ?,
                telephone            = ?,
                adresse              = ?,
                profession           = ?,
                groupe_sanguin       = ?,
                situation_matrimoniale = ?,
                contact_nom          = ?,
                contact_telephone    = ?,
                type_client          = ?,
                circuit              = ?,
                nom_assurance        = ?,
                numero_assure        = ?,
                numero_bon           = ?,
                organisme_payeur     = ?,
                matricule_agent      = ?,
                infirmerie_origine   = ?
            WHERE id = ?")->execute([
                strtoupper(trim($_POST['nom'])),
                trim($_POST['prenom']),
                trim($_POST['telephone'] ?? ''),
                trim($_POST['adresse']   ?? ''),
                trim($_POST['profession'] ?? ''),
                ($_POST['groupe_sanguin'] ?: null),
                $_POST['situation_matrimoniale'] ?? 'celibataire',
                trim($_POST['contact_nom']       ?? ''),
                trim($_POST['contact_telephone'] ?? ''),
                $type_client,
                $circuit_nv,
                $nom_assurance,
                $numero_assure,
                $numero_bon,
                $organisme_payeur,
                $matricule_agent,
                $infirmerie_origine,
                $id,
            ]);

            // Génération du numéro d'ordre (ticket) — reset auto à 1 chaque jour
            require_once __DIR__ . '/../services/WorkflowResetService.php';
            $num = (new WorkflowResetService($db))->getNextOrderNumber();

            // Récupérer femme_enceinte et date_naissance existants
            $stmtFE = $db->prepare("SELECT sexe, femme_enceinte, date_naissance FROM patients WHERE id = ?");
            $stmtFE->execute([$id]);
            $row = $stmtFE->fetch(\PDO::FETCH_ASSOC);
            $femme_enceinte_nv = (!empty($row['femme_enceinte']) && $row['sexe'] === 'F') ? 1 : 0;
            $is_php_nv = in_array($type_client, ['FAMILLE_PHP', 'AGENTS_PHP']);
            $service_id_dest_nv = (int)($_POST['service_id_destination'] ?? 0);

            if ($femme_enceinte_nv) {
                // Maternité — inchangé
                $db->prepare("UPDATE patients SET
                    statut_parcours = 'PARAMETRES_MATERNITE', numero_ordre = ?,
                    date_mise_en_parametres = NOW(), medecin_id = NULL, service_id = NULL,
                    parametres_requis = 0
                WHERE id = ?")->execute([$num, $id]);

            } elseif ($is_php_nv) {
                // PHP — bureau paramètres dédié
                $db->prepare("UPDATE patients SET
                    statut_parcours = 'PARAMETRES', numero_ordre = ?,
                    date_mise_en_parametres = NOW(), medecin_id = NULL, service_id = NULL,
                    parametres_requis = 0
                WHERE id = ?")->execute([$num, $id]);

            } elseif ($service_id_dest_nv > 0) {
                // Service sélectionné → routing direct
                $db->prepare("UPDATE patients SET
                    statut_parcours = 'ATTENTE_CONSULTATION', numero_ordre = ?,
                    date_mise_en_parametres = NOW(), medecin_id = NULL,
                    service_id = ?, parametres_requis = 1
                WHERE id = ?")->execute([$num, $service_id_dest_nv, $id]);

            } else {
                // Standard → paramètres
                $db->prepare("UPDATE patients SET
                    statut_parcours = 'PARAMETRES', numero_ordre = ?,
                    date_mise_en_parametres = NOW(), medecin_id = NULL, service_id = NULL,
                    parametres_requis = 0
                WHERE id = ?")->execute([$num, $id]);
            }

            $dossier = $db->prepare("SELECT dossier_numero FROM patients WHERE id = ?");
            $dossier->execute([$id]);
            $dossier_numero = $dossier->fetchColumn();

            $db->commit();
            header('Location: ' . BASE_URL . 'accueil?success=visite_demarree&ticket=' . $num . '&dossier=' . urlencode($dossier_numero));
            exit;

        } catch (Exception $e) {
            $db->rollBack();
            error_log("Erreur nouvelleVisite : " . $e->getMessage());
            header('Location: ' . BASE_URL . 'accueil?error=visite');
            exit;
        }
    }

    public function commencerVisite($id) {
        $db = (new Database())->getConnection();
        $this->executeVisite($id, $db);
        header('Location: ' . BASE_URL . 'accueil?success=visite_demarree');
        exit;
    }

    /**
     * Modifie toutes les informations d'un patient existant (AJAX JSON)
     * POST accueil/modifier-patient
     */
    public function modifierPatient(): void {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']); exit;
        }

        $db = (new Database())->getConnection();
        $this->migrateMaterniteColumns($db);

        try {
            $id = (int)($_POST['patient_id'] ?? 0);
            if (!$id) throw new \Exception('ID patient manquant');

            // Vérifier que le patient existe
            $stmtChk = $db->prepare("SELECT id, sexe FROM patients WHERE id = ? AND actif = 1");
            $stmtChk->execute([$id]);
            $patientRow = $stmtChk->fetch(\PDO::FETCH_ASSOC);
            if (!$patientRow) throw new \Exception('Patient introuvable');

            // Sanitize & valider
            $allowed_types = ['BON_PRISE_EN_CHARGE','PAYANT_COMPTANT','ASSURANCE','FAMILLE_PHP','AGENTS_PHP'];
            $type_client   = in_array($_POST['type_client'] ?? '', $allowed_types)
                             ? $_POST['type_client'] : 'PAYANT_COMPTANT';

            $sexe               = in_array($_POST['sexe'] ?? '', ['M','F']) ? $_POST['sexe'] : $patientRow['sexe'];
            $femme_enceinte     = ($sexe === 'F' && ($_POST['femme_enceinte'] ?? '') === '1') ? 1 : 0;

            $nom                = strtoupper(trim($_POST['nom']  ?? ''));
            $prenom             = trim($_POST['prenom']          ?? '');
            if (!$nom || !$prenom) throw new \Exception('Nom et prénom obligatoires');

            $date_naissance     = !empty($_POST['date_naissance']) ? $_POST['date_naissance'] : null;
            $groupe_sanguin     = $_POST['groupe_sanguin']   ?: null;
            $situation_mat      = $_POST['situation_matrimoniale'] ?? 'celibataire';
            $telephone          = trim($_POST['telephone']    ?? '');
            $email              = trim($_POST['email']        ?? '') ?: null;
            $telephone_urgence  = trim($_POST['telephone_urgence'] ?? '') ?: null;
            $profession         = trim($_POST['profession']   ?? '');
            $adresse            = trim($_POST['adresse']      ?? '');
            $ville              = trim($_POST['ville']        ?? '') ?: null;
            $contact_nom        = trim($_POST['contact_nom']  ?? '');
            $contact_telephone  = trim($_POST['contact_telephone'] ?? '');

            $nom_assurance      = ($type_client === 'ASSURANCE')           ? trim($_POST['nom_assurance']      ?? '') ?: null : null;
            $numero_assure      = ($type_client === 'ASSURANCE')           ? trim($_POST['numero_assure']      ?? '') ?: null : null;
            $numero_bon         = ($type_client === 'BON_PRISE_EN_CHARGE') ? trim($_POST['numero_bon']         ?? '') ?: null : null;
            $organisme_payeur   = in_array($type_client, ['BON_PRISE_EN_CHARGE','ASSURANCE'])
                                  ? trim($_POST['organisme_payeur'] ?? '') ?: null : null;
            $matricule_agent    = ($type_client === 'AGENTS_PHP')          ? trim($_POST['matricule_agent']    ?? '') ?: null : null;
            $infirmerie_origine = ($type_client === 'AGENTS_PHP')          ? trim($_POST['infirmerie_origine'] ?? '') ?: null : null;
            $numero_compte_sage = trim($_POST['numero_compte_sage'] ?? '') ?: null;
            // Seul AGENTS_PHP va au bureau PHP ; FAMILLE_PHP suit la parité B1/B2
            $circuit_edit       = ($type_client === 'AGENTS_PHP') ? 'PHP' : 'STANDARD';

            $db->prepare("UPDATE patients SET
                nom                    = ?,
                prenom                 = ?,
                date_naissance         = ?,
                sexe                   = ?,
                femme_enceinte         = ?,
                groupe_sanguin         = ?,
                situation_matrimoniale = ?,
                telephone              = ?,
                email                  = ?,
                telephone_urgence      = ?,
                profession             = ?,
                adresse                = ?,
                ville                  = ?,
                contact_nom            = ?,
                contact_telephone      = ?,
                type_client            = ?,
                circuit                = ?,
                nom_assurance          = ?,
                numero_assure          = ?,
                numero_bon             = ?,
                organisme_payeur       = ?,
                matricule_agent        = ?,
                infirmerie_origine     = ?,
                numero_compte_sage     = ?,
                date_modification      = NOW(),
                updated_at             = NOW()
            WHERE id = ?")
            ->execute([
                $nom, $prenom, $date_naissance, $sexe, $femme_enceinte,
                $groupe_sanguin, $situation_mat,
                $telephone, $email, $telephone_urgence,
                $profession, $adresse, $ville,
                $contact_nom, $contact_telephone,
                $type_client, $circuit_edit,
                $nom_assurance, $numero_assure,
                $numero_bon, $organisme_payeur,
                $matricule_agent, $infirmerie_origine, $numero_compte_sage,
                $id,
            ]);

            echo json_encode([
                'success' => true,
                'message' => 'Dossier mis à jour avec succès',
                'patient' => [
                    'id'     => $id,
                    'nom'    => $nom,
                    'prenom' => $prenom,
                ],
            ]);

        } catch (\Exception $e) {
            error_log("AccueilController::modifierPatient : " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    private function executeVisite($patient_id, $db) {
        // Génère le numéro d'ordre (reset auto à 1 chaque nouveau jour)
        require_once __DIR__ . '/../services/WorkflowResetService.php';
        $num = (new WorkflowResetService($db))->getNextOrderNumber();

        // Envoie aux paramètres avec date du jour
        $stmt = $db->prepare("UPDATE patients
            SET statut_parcours = 'PARAMETRES', numero_ordre = ?, date_mise_en_parametres = NOW()
            WHERE id = ?");
        $stmt->execute([$num, $patient_id]);
    }
}