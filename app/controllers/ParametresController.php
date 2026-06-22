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
FICHIER : app/controllers/ParametresController.php
CONTRÔLEUR DES BUREAUX DE PARAMÈTRES (VITAL SIGNS)
============================================================================ */

require_once __DIR__ . '/UnifiedController.php';

class ParametresController extends UnifiedController {

    public function __construct() {
        parent::__construct();
    }

    /**
     * Dashboard de prise des paramètres — bureau unique
     */
    public function index() {
        // 1. Vérification des permissions
        $this->auth->requirePermission('parametres', 'read');

        $db = (new Database())->getConnection();
        $userId  = $_SESSION['user_id'];
        $bureauId = $_SESSION['bureau_id'] ?? 1; // 1 = B1, 2 = B2

        // 1bis. Nettoyage automatique : tout patient resté en file > 24h est retiré
        require_once __DIR__ . '/../services/WorkflowResetService.php';
        (new WorkflowResetService($db))->cleanWaitingListsIfStale();

        // 2. Logique de parité :
        //    B1 (bureau_id = 1) → numéros IMPAIRS  (1, 3, 5, ...)
        //    B2 (bureau_id = 2) → numéros PAIRS    (2, 4, 6, ...)
        $pariteCondition = ($bureauId == 2)
            ? "numero_ordre % 2 = 0"   // B2 → pairs
            : "numero_ordre % 2 = 1";  // B1 → impairs (défaut)

        // 3. Récupération des patients en attente :
        //    - Groupe A : patients en PARAMETRES (parcours classique, distribués par parité pair/impair)
        //    - Groupe B : patients en ATTENTE_CONSULTATION avec parametres_requis=1
        //      (routés directement vers un service depuis l'accueil → constantes pas encore prises)
        //      → visibles dans LES DEUX bureaux (pas de filtre parité)
        //    Filtrage par jour (date_mise_en_parametres du jour)
        $stmtA = $db->prepare("
            SELECT * FROM patients
            WHERE DATE(COALESCE(date_mise_en_parametres, updated_at, created_at)) = CURDATE()
              AND (
                    -- Parcours classique : parité B1/B2
                    (   statut_parcours = 'PARAMETRES'
                        AND (circuit = 'STANDARD' OR circuit IS NULL)
                        AND $pariteCondition
                    )
                    OR
                    -- Routage direct service : parametres pas encore pris
                    (   statut_parcours = 'ATTENTE_CONSULTATION'
                        AND COALESCE(parametres_requis, 0) = 1
                    )
              )
            ORDER BY numero_ordre ASC
        ");
        $stmtA->execute();
        $patients_attente = $stmtA->fetchAll(PDO::FETCH_ASSOC);

        // 4. Récupération des patients déjà traités aujourd'hui par cet infirmier
        $stmtR = $db->prepare("SELECT p.*, pv.date_mesure
                                FROM patients p
                                JOIN patient_parametres pv ON p.id = pv.patient_id
                                WHERE pv.user_id = ?
                                AND DATE(pv.date_mesure) = CURDATE()
                                ORDER BY pv.date_mesure DESC");
        $stmtR->execute([$userId]);
        $patients_reçus = $stmtR->fetchAll(PDO::FETCH_ASSOC);

        // 5. Données pour la vue
        $bureauLabel = ($bureauId == 2) ? "BUREAU PARAMÈTRES B2" : "BUREAU PARAMÈTRES B1";
        $bureauTheme = ($bureauId == 2) ? "success" : "primary";

        require_once __DIR__ . '/../views/parametres/dashboard.php';
    }

    public function save() {
    $db = (new Database())->getConnection();

    try {
        $db->beginTransaction();

        // 1. Récupération sécurisée des données
        //    Les champs numériques vides → NULL (évite SQLSTATE 01000 / warning 1265)
        $patient_id           = (int)$_POST['patient_id'];
        $service_id           = (int)$_POST['service_id'];
        $consultation_infirmiere = ($_POST['medecin_id'] === 'INFIRMIERE');
        $medecin_id           = $consultation_infirmiere ? 0 : (int)$_POST['medecin_id'];
        $motif                = trim($_POST['motif'] ?? '');

        // Migration douce : ajouter consultation_infirmiere si absent
        try {
            $cols = array_column($db->query("DESCRIBE patients")->fetchAll(PDO::FETCH_ASSOC), 'Field');
            if (!in_array('consultation_infirmiere', $cols)) {
                $db->exec("ALTER TABLE patients ADD COLUMN consultation_infirmiere TINYINT(1) NOT NULL DEFAULT 0 AFTER statut_parcours");
            }
        } catch (Exception $e) { error_log('[ParametresController] migration: ' . $e->getMessage()); }

        // Helper : chaîne vide ou absente → null ; sinon cast numérique
        $num = fn($k, $type = 'float') => (isset($_POST[$k]) && $_POST[$k] !== '')
            ? ($type === 'int' ? (int)$_POST[$k] : (float)str_replace(',', '.', $_POST[$k]))
            : null;

        // Arrondir temp à 1 décimale (compatible decimal(4,1) ET decimal(5,2) après migration)
        // Les autres floats restent à 2 décimales (poids, spo2 — colonnes float ou decimal(5,2))
        $round1 = fn($v) => $v !== null ? round($v, 1) : null;
        $round2 = fn($v) => $v !== null ? round($v, 2) : null;

        $temp   = $round1($num('temp'));           // ex: 37.5  — decimal(4,1) safe
        $sys    = $num('sys',   'int');            // int — ex: 120
        $dia    = $num('dia',   'int');            // int — ex: 80
        $pouls  = $num('pouls', 'int');            // int — ex: 72
        $spo2   = $round2($num('spo2'));           // ex: 98.50
        $poids  = $round2($num('poids'));          // ex: 70.50
        $taille = $round1($num('taille'));         // ex: 175.0

        // 2. Enregistrement des paramètres vitaux dans l'historique
        $sqlV = "INSERT INTO patient_parametres (
                    patient_id, user_id, temperature,
                    pression_arterielle_systolique, pression_arterielle_diastolique,
                    frequence_cardiaque, poids, taille, saturation_oxygene,
                    motif_consultation, date_mesure
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";

        $stmtV = $db->prepare($sqlV);
        $stmtV->execute([
            $patient_id, $_SESSION['user_id'], $temp,
            $sys, $dia, $pouls, $poids, $taille, $spo2, $motif
        ]);

        // 3a. Détection auto-routing pédiatrique :
        //     Si le patient est un enfant (0–15 ans) et qu'il a déjà un service_id
        //     pointant vers la pédiatrie (défini à l'accueil), on préserve ce routing
        //     → ATTENTE_CONSULTATION avec service_id pédiatrie + medecin_id = NULL.
        //     Cela permet à TOUS les pédiatres de le voir, quel que soit le choix
        //     manuel de l'infirmier dans le formulaire.
        try {
            $stmtPat = $db->prepare("SELECT date_naissance, service_id FROM patients WHERE id = ? LIMIT 1");
            $stmtPat->execute([$patient_id]);
            $patData = $stmtPat->fetch(PDO::FETCH_ASSOC);
            if ($patData && !empty($patData['date_naissance']) && $patData['date_naissance'] !== '1900-01-01'
                && !empty($patData['service_id'])) {
                $age_p = (int)date_diff(date_create($patData['date_naissance']), date_create('now'))->y;
                if ($age_p >= 0 && $age_p <= 15) {
                    // Vérifier que le service pré-assigné est bien pédiatrie
                    $stmtSrvPed = $db->prepare("SELECT id FROM services WHERE id = ?
                        AND (LOWER(nom_service) LIKE '%p%diatr%'
                          OR LOWER(nom_service) LIKE '%pediatr%'
                          OR LOWER(nom_service) LIKE '%pédiatr%') LIMIT 1");
                    $stmtSrvPed->execute([$patData['service_id']]);
                    if ($stmtSrvPed->fetchColumn()) {
                        // Forcer le routing pédiatrique
                        $service_id = (int)$patData['service_id'];
                        $medecin_id = 0; // NULL en DB → tous les pédiatres verront le patient
                    }
                }
            }
        } catch (Exception $e) { /* non bloquant — routing manuel en fallback */ }

        // 3b. Mise à jour de la fiche patient (Statut, Service et Médecin affecté)
        //     parametres_requis = 0 : les vitaux viennent d'être pris
        $sqlP = "UPDATE patients SET
                    statut_parcours = 'ATTENTE_CONSULTATION',
                    service_id = ?,
                    medecin_id = ?,
                    consultation_infirmiere = ?,
                    parametres_requis = 0
                 WHERE id = ?";

        $stmtP = $db->prepare($sqlP);
        $stmtP->execute([$service_id, $medecin_id ?: null, $consultation_infirmiere ? 1 : 0, $patient_id]);

        // ── Si le service cible est les urgences, créer une entrée urgences_patients
        // pour que le patient apparaisse dans le cockpit urgences du médecin ────────
        if ($service_id) {
            $stmtSrv = $db->prepare("SELECT nom_service FROM services WHERE id = ? LIMIT 1");
            $stmtSrv->execute([$service_id]);
            $srvNom = strtolower($stmtSrv->fetchColumn() ?: '');

            if (str_contains($srvNom, 'urgence')) {
                // Vérifier si déjà une entrée active dans urgences_patients
                $stmtChk = $db->prepare("SELECT id FROM urgences_patients WHERE patient_id = ? AND statut NOT IN ('TERMINE','SORTI','HOSPITALISE') LIMIT 1");
                $stmtChk->execute([$patient_id]);
                if (!$stmtChk->fetch()) {
                    $db->prepare(
                        "INSERT INTO urgences_patients (patient_id, motif_admission, niveau_triage, heure_arrivee, medecin_id, statut)
                         VALUES (?, ?, '3', NOW(), ?, 'ATTENTE')"
                    )->execute([$patient_id, $motif ?: 'Consultation — envoyé depuis paramètres', $medecin_id]);
                }
            }
        }

        $db->commit();

        // Redirection selon le choix de l'infirmier
        $redirect_after = $_POST['redirect_after'] ?? 'salle_attente';
        if ($redirect_after === 'consultation_directe') {
            header('Location: ' . BASE_URL . 'infirmier/consultation/' . $patient_id);
        } else {
            header('Location: ' . BASE_URL . 'parametres?success=1');
        }
        exit;

    } catch (Exception $e) {
        $db->rollBack();
        error_log("Erreur ParametresController::save : " . $e->getMessage());
        die("Erreur technique lors de la sauvegarde : " . $e->getMessage());
    }
}
}