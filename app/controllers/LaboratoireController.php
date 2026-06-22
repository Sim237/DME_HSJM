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
FICHIER : app/controllers/LaboratoireController.php
CONTRÔLEUR DE GESTION DU LABORATOIRE (SIL)
Rôles : LABORANTIN (gestion complète) | TECHNICIEN_LABO (saisie + soumission)
============================================================================ */

require_once __DIR__ . '/UnifiedController.php';
require_once __DIR__ . '/../services/LaboratoireService.php';
require_once __DIR__ . '/../services/SignatureService.php';
require_once __DIR__ . '/../services/AuditService.php';

class LaboratoireController extends UnifiedController {
    private $laboratoireService;
    private $db;
    private $audit;

    public function __construct() {
        parent::__construct();
        $this->laboratoireService = new LaboratoireService();
        $this->db = (new Database())->getConnection();
        $this->audit = new AuditService();
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function isLaborantin(): bool {
        $r = strtoupper($_SESSION['user_role'] ?? '');
        return in_array($r, ['LABORANTIN', 'ADMINISTRATEUR', 'ADMIN']);
    }

    private function isTechnicien(): bool {
        $r = strtoupper($_SESSION['user_role'] ?? '');
        return in_array($r, ['TECHNICIEN_LABO', 'LABORANTIN', 'ADMINISTRATEUR', 'ADMIN']);
    }

    private function envoyerNotifMedecin(int $demandeId, int $medecinId, int $patientId): void {
        try {
            $this->db->prepare("
                INSERT INTO notifications_medecin (medecin_id, patient_id, type, titre, message, demande_id)
                VALUES (?, ?, 'RESULTATS_LABO',
                        'Résultats de laboratoire disponibles',
                        'Les résultats de biologie ont été validés par le laboratoire et sont disponibles dans le dossier patient.',
                        ?)
            ")->execute([$medecinId, $patientId, $demandeId]);
        } catch (Exception $e) {
            error_log("Erreur notification médecin: " . $e->getMessage());
        }
    }

    /** Examens soumis par les techniciens, en attente de validation biologiste */
    private function getExamensSoumis(): array {
        $stmt = $this->db->prepare("
            SELECT de.*,
                   el.nom AS nom_examen, el.categorie, el.unite,
                   el.valeur_normale_min, el.valeur_normale_max,
                   p.nom AS patient_nom, p.prenom AS patient_prenom, p.dossier_numero,
                   tech.nom AS tech_nom, tech.prenom AS tech_prenom,
                   d.id AS demande_id, d.medecin_id, d.patient_id AS dem_patient_id,
                   d.numero_ordre
            FROM demande_examens de
            JOIN examens_laboratoire el ON de.examen_id = el.id
            JOIN demandes_laboratoire d  ON de.demande_id = d.id
            JOIN patients p              ON d.patient_id  = p.id
            LEFT JOIN users tech         ON de.technicien_id = tech.id
            WHERE de.statut = 'SOUMIS'
            ORDER BY de.urgent DESC,
                     (d.numero_ordre IS NULL) ASC,
                     d.numero_ordre ASC,
                     de.date_soumission ASC
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ── Routes principales ────────────────────────────────────────────────────

    /**
     * Dashboard principal — LABORANTIN → cockpit complet
     *                       TECHNICIEN_LABO → redirige vers son tableau de bord
     */
    public function index(): void {
        $this->auth->requirePermission('laboratoire', 'read');
        $role = strtoupper($_SESSION['user_role'] ?? '');

        if ($role === 'TECHNICIEN_LABO') {
            $this->dashboardTechnicien(); return;
        }

        if ($role === 'SECRETAIRE_LABO') {
            $this->secretariat(); return;
        }

        $demandes       = $this->laboratoireService->getDemandesEnAttente();
        $statistiques   = $this->laboratoireService->getStatistiques();
        $examens_soumis = $this->getExamensSoumis();

        // Charger les pièces jointes pour les examens soumis (groupées par examen_id)
        $pjsParExamen = [];
        if (!empty($examens_soumis)) {
            try {
                $tableExiste = (int)$this->db->query(
                    "SELECT COUNT(*) FROM information_schema.TABLES
                     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'demande_pieces_jointes'"
                )->fetchColumn() > 0;
                if ($tableExiste) {
                    $exIds        = array_column($examens_soumis, 'id');
                    $placeholders = implode(',', array_fill(0, count($exIds), '?'));
                    $stmtPj       = $this->db->prepare("
                        SELECT id, examen_id, fichier_nom_original, fichier_chemin,
                               fichier_taille, fichier_type, uploade_at
                        FROM demande_pieces_jointes
                        WHERE examen_id IN ($placeholders)
                        ORDER BY uploade_at ASC
                    ");
                    $stmtPj->execute($exIds);
                    foreach ($stmtPj->fetchAll(PDO::FETCH_ASSOC) as $pj) {
                        $pjsParExamen[(int)$pj['examen_id']][] = $pj;
                    }
                }
            } catch (Exception $e) { /* ignorer */ }
        }

        // Charger les valeurs des sous-paramètres pour les examens soumis
        $sousValeursMap = [];
        if (!empty($examens_soumis)) {
            try {
                $spTableExiste = (int)$this->db->query(
                    "SELECT COUNT(*) FROM information_schema.TABLES
                     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'demande_examen_valeurs'"
                )->fetchColumn() > 0;
                if ($spTableExiste) {
                    $deIds  = array_column($examens_soumis, 'id');
                    $spPhs  = implode(',', array_fill(0, count($deIds), '?'));
                    $stmtSV = $this->db->prepare("
                        SELECT dev.demande_examen_id, dev.valeur_numerique,
                               esp.id AS param_id, esp.nom, esp.code, esp.unite,
                               esp.valeur_normale_min, esp.valeur_normale_max, esp.ordre
                        FROM demande_examen_valeurs dev
                        JOIN examen_sous_parametres esp ON dev.sous_parametre_id = esp.id
                        WHERE dev.demande_examen_id IN ($spPhs)
                        ORDER BY dev.demande_examen_id, esp.ordre
                    ");
                    $stmtSV->execute($deIds);
                    foreach ($stmtSV->fetchAll(PDO::FETCH_ASSOC) as $sv) {
                        $sousValeursMap[(int)$sv['demande_examen_id']][] = $sv;
                    }
                }
            } catch (Exception $e) { /* ignorer */ }
        }

        // ── Statistiques biologiste (Tab 3) ──────────────────────────────────
        $stats_bio = [];
        try {
            // Examens validés par jour — 7 derniers jours
            $stmtG1 = $this->db->query("
                SELECT DATE(de.date_validation) AS jour,
                       COUNT(*) AS nb_valides,
                       SUM(CASE WHEN de.urgent = 1 THEN 1 ELSE 0 END) AS nb_urgents
                FROM demande_examens de
                WHERE de.statut = 'VALIDE'
                  AND de.date_validation >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
                GROUP BY DATE(de.date_validation)
                ORDER BY jour
            ");
            $stats_bio['par_jour'] = $stmtG1->fetchAll(PDO::FETCH_ASSOC);

            // Répartition par catégorie (tout statut, aujourd'hui)
            $stmtC = $this->db->query("
                SELECT el.categorie,
                       COUNT(*) AS nb,
                       SUM(CASE WHEN de.statut = 'VALIDE' THEN 1 ELSE 0 END) AS nb_valides
                FROM demande_examens de
                JOIN examens_laboratoire el ON de.examen_id = el.id
                JOIN demandes_laboratoire d  ON de.demande_id = d.id
                WHERE DATE(d.date_creation) = CURDATE()
                GROUP BY el.categorie
                ORDER BY nb DESC
            ");
            $stats_bio['par_categorie'] = $stmtC->fetchAll(PDO::FETCH_ASSOC);

            // KPIs supplémentaires
            $stats_bio['valides_auj'] = (int)$this->db->query(
                "SELECT COUNT(*) FROM demande_examens
                 WHERE statut = 'VALIDE' AND DATE(date_validation) = CURDATE()"
            )->fetchColumn();
            $stats_bio['rejetes_auj'] = (int)$this->db->query(
                "SELECT COUNT(*) FROM demande_examens
                 WHERE statut = 'REJETE' AND DATE(date_validation) = CURDATE()"
            )->fetchColumn();
            $stats_bio['en_cours'] = (int)$this->db->query(
                "SELECT COUNT(*) FROM demande_examens
                 WHERE statut IN ('ASSIGNEE','PRELEVE','EN_COURS')"
            )->fetchColumn();
            $stats_bio['soumis_total'] = (int)$this->db->query(
                "SELECT COUNT(*) FROM demande_examens WHERE statut = 'SOUMIS'"
            )->fetchColumn();

            // Délai moyen de traitement (heures) — 7 jours
            $stmtDel = $this->db->query("
                SELECT ROUND(AVG(TIMESTAMPDIFF(MINUTE, d.date_creation, de.date_validation) / 60), 1) AS delai_h
                FROM demande_examens de
                JOIN demandes_laboratoire d ON de.demande_id = d.id
                WHERE de.statut = 'VALIDE'
                  AND de.date_validation >= DATE_SUB(NOW(), INTERVAL 7 DAY)
            ");
            $stats_bio['delai_moyen_7j'] = (float)($stmtDel->fetchColumn() ?? 0);

            // Taux de rejet (7 jours)
            $stmtTaux = $this->db->query("
                SELECT
                    COUNT(*) AS total,
                    SUM(CASE WHEN statut = 'REJETE' THEN 1 ELSE 0 END) AS rejetes
                FROM demande_examens
                WHERE date_validation >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                  AND statut IN ('VALIDE','REJETE')
            ");
            $tRow = $stmtTaux->fetch(PDO::FETCH_ASSOC);
            $stats_bio['taux_rejet'] = ($tRow['total'] > 0)
                ? round($tRow['rejetes'] / $tRow['total'] * 100, 1) : 0;

            // Activité récente (10 dernières validations)
            $stmtAct = $this->db->query("
                SELECT de.statut, de.date_validation,
                       el.nom AS nom_examen,
                       p.nom AS patient_nom, p.prenom AS patient_prenom,
                       u.nom AS bio_nom
                FROM demande_examens de
                JOIN examens_laboratoire el ON de.examen_id = el.id
                JOIN demandes_laboratoire d  ON de.demande_id = d.id
                JOIN patients p              ON d.patient_id = p.id
                LEFT JOIN users u            ON de.valide_par = u.id
                WHERE de.statut IN ('VALIDE','REJETE')
                ORDER BY de.date_validation DESC
                LIMIT 10
            ");
            $stats_bio['activite_recente'] = $stmtAct->fetchAll(PDO::FETCH_ASSOC);

        } catch (Exception $e) {
            error_log('[Labo::index stats] ' . $e->getMessage());
        }

        require_once __DIR__ . '/../views/laboratoire/dashboard.php';
    }

    /**
     * API : Liste des examens paramétrés
     */
    public function examensDisponibles(): void {
        header('Content-Type: application/json');
        echo json_encode($this->laboratoireService->getExamensDisponibles());
    }

    /**
     * Vue : Détails d'une demande pour prélèvement / suivi
     */
    public function traiterDemande(int $id): void {
        $this->auth->requirePermission('laboratoire', 'write');
        $demande = $this->laboratoireService->getDemandeComplete($id);
        if (!$demande) { header('Location: ' . BASE_URL . 'laboratoire?error=demande_introuvable'); exit; }
        $examens = $this->laboratoireService->getExamensParDemande($id);
        require_once __DIR__ . '/../views/laboratoire/traitement.php';
    }

    /**
     * Vue : Formulaire de saisie des résultats (laborantin — ancien workflow)
     */
    public function saisieResultats(int $demande_id): void {
        $this->auth->requirePermission('laboratoire', 'write');
        $demande = $this->laboratoireService->getDemandeComplete($demande_id);
        $examens = $this->laboratoireService->getExamensParDemande($demande_id);
        if (!$demande) { header('Location: ' . BASE_URL . 'laboratoire?error=demande_introuvable'); exit; }

        // Charger les pièces jointes — groupées par examen_id
        $piecesJointes          = []; // orphelins (examen_id = NULL) — rétro-compat
        $piecesJointesParExamen = []; // [examen_id => [pj, ...]]
        try {
            $tableExiste = (int)$this->db->query(
                "SELECT COUNT(*) FROM information_schema.TABLES
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'demande_pieces_jointes'"
            )->fetchColumn() > 0;
            if ($tableExiste) {
                $stmt = $this->db->prepare("
                    SELECT pj.*, u.nom AS uploader_nom, u.prenom AS uploader_prenom
                    FROM demande_pieces_jointes pj
                    LEFT JOIN users u ON u.id = pj.uploade_par
                    WHERE pj.demande_id = ?
                    ORDER BY pj.uploade_at DESC
                ");
                $stmt->execute([$demande_id]);
                foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $pj) {
                    $key = (int)($pj['examen_id'] ?? 0);
                    $piecesJointesParExamen[$key][] = $pj;
                }
                $piecesJointes = $piecesJointesParExamen[0] ?? [];
            }
        } catch (Exception $e) { /* ignorer */ }

        require_once __DIR__ . '/../views/laboratoire/saisie_resultats.php';
    }

    /**
     * Action : Enregistre les résultats (ancien workflow direct laborantin)
     * + Gestion des fichiers joints (PDF, images, etc.)
     */
    public function sauvegarderResultats(): void {
        $this->auth->requirePermission('laboratoire', 'write');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;

        $demande_id = (int)($_POST['demande_id'] ?? 0);
        if (!$demande_id) {
            header('Location: ' . BASE_URL . 'laboratoire?error=demande_invalide');
            exit;
        }

        // 1. Traiter les fichiers uploadés AVANT l'enregistrement des résultats
        // pour pouvoir compter les pièces jointes (utile si tous les examens sont vides
        // mais qu'un PDF a été joint).
        $piecesJointesUploadees = $this->traiterUploadsPiecesJointes($demande_id);
        $_POST['_nb_pieces_jointes'] = count($piecesJointesUploadees);

        // 2. Sauvegarder les résultats (avec validation assouplie)
        $result = $this->laboratoireService->sauvegarderResultats($_POST);

        // 3. Réponse : AJAX (brouillon) ou redirect (validation)
        $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
               || ($_SERVER['HTTP_ACCEPT'] ?? '') === 'application/json'
               || !empty($_POST['brouillon']);

        if ($isAjax) {
            header('Content-Type: application/json');
            $result['pieces_jointes_uploadees'] = $piecesJointesUploadees;
            echo json_encode($result);
            exit;
        }

        if ($result['success']) {
            $msg = urlencode($result['message'] ?? 'Résultats sauvegardés');
            // Revenir sur le même formulaire pour permettre la saisie progressive
            header('Location: ' . BASE_URL . 'laboratoire/saisie-resultats/' . $demande_id
                . '?success=' . $msg);
        } else {
            header('Location: ' . BASE_URL . 'laboratoire/saisie-resultats/' . $demande_id
                . '?error=' . urlencode($result['message'] ?? 'Erreur inconnue'));
        }
        exit;
    }

    /**
     * Traite les fichiers uploadés via $_FILES['pieces_jointes'][examen_id][].
     * Chaque examen peut avoir ses propres fichiers — structure : pieces_jointes[123][].
     * Stocke dans uploads/laboratoire/{demande_id}/ et insère dans demande_pieces_jointes.
     *
     * Rétro-compatible : si les fichiers arrivent en structure plate (pieces_jointes[]),
     * ils sont traités comme fichiers généraux (examen_id = NULL).
     *
     * @return array Liste des pièces ajoutées
     */
    private function traiterUploadsPiecesJointes(int $demande_id): array {
        $resultats = [];
        if (empty($_FILES['pieces_jointes']) || empty($_FILES['pieces_jointes']['name'])) {
            return $resultats;
        }

        // Vérifier que la table existe (compat. avant migration)
        $tableExiste = (int)$this->db->query(
            "SELECT COUNT(*) FROM information_schema.TABLES
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'demande_pieces_jointes'"
        )->fetchColumn() > 0;
        if (!$tableExiste) {
            error_log('[Labo] Table demande_pieces_jointes manquante — exécutez run_migration_labo_pieces_jointes.php');
            return $resultats;
        }

        // Préparer le dossier d'upload
        $baseDir = __DIR__ . '/../../uploads/laboratoire/' . $demande_id;
        if (!is_dir($baseDir) && !@mkdir($baseDir, 0755, true) && !is_dir($baseDir)) {
            error_log("[Labo] Impossible de créer $baseDir");
            return $resultats;
        }

        $userId      = $_SESSION['user_id'] ?? null;
        $maxFileSize = 10 * 1024 * 1024;
        $allowedExt  = ['pdf','png','jpg','jpeg','gif','webp','tif','tiff','bmp','heic','xlsx','xls','csv','doc','docx','txt'];

        $stmt = $this->db->prepare("
            INSERT INTO demande_pieces_jointes
                (demande_id, examen_id, fichier_nom_original, fichier_chemin,
                 fichier_taille, fichier_type, description, uploade_par, uploade_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");

        // Normaliser $_FILES en liste plate [[examen_id, file_info], ...]
        $filesToProcess = $this->normaliserFichiersParExamen($_FILES['pieces_jointes']);

        foreach ($filesToProcess as [$examenId, $fileInfo]) {
            if (($fileInfo['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) continue;
            if (empty($fileInfo['name'])) continue;

            $origName = $fileInfo['name'];
            $tmpPath  = $fileInfo['tmp_name'];
            $size     = (int)($fileInfo['size'] ?? 0);
            $mime     = $fileInfo['type'] ?? 'application/octet-stream';

            // Description spécifique à cet examen (ou globale en rétro-compat)
            $descArr     = $_POST['description_pieces'] ?? [];
            $descKey     = $examenId > 0 ? $examenId : 0;
            $description = trim(is_array($descArr) ? ($descArr[$descKey] ?? '') : (string)$descArr);

            $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
            if (!in_array($ext, $allowedExt, true)) {
                error_log("[Labo] Extension refusée : $ext ($origName)");
                continue;
            }
            if ($size > $maxFileSize) {
                error_log("[Labo] Fichier trop gros : $origName ($size octets)");
                continue;
            }

            // Nom de fichier sécurisé : {timestamp}_{random}.ext
            $safeBase = preg_replace('/[^a-zA-Z0-9\-_]/', '_', pathinfo($origName, PATHINFO_FILENAME));
            $safeBase = substr($safeBase, 0, 60);
            $newName  = time() . '_' . substr(bin2hex(random_bytes(4)), 0, 8) . '_' . $safeBase . '.' . $ext;
            $destPath = $baseDir . '/' . $newName;

            if (!@move_uploaded_file($tmpPath, $destPath)) {
                error_log("[Labo] move_uploaded_file échec pour $origName");
                continue;
            }

            $cheminRelatif = "uploads/laboratoire/$demande_id/$newName";
            $examenIdSave  = $examenId > 0 ? $examenId : null;

            try {
                $stmt->execute([
                    $demande_id, $examenIdSave, $origName, $cheminRelatif,
                    $size, $mime, $description ?: null, $userId,
                ]);
                $resultats[] = [
                    'id'        => (int)$this->db->lastInsertId(),
                    'nom'       => $origName,
                    'taille'    => $size,
                    'type'      => $mime,
                    'chemin'    => $cheminRelatif,
                    'examen_id' => $examenIdSave,
                ];
            } catch (\PDOException $e) {
                error_log("[Labo] Insertion pièce jointe échouée : " . $e->getMessage());
                @unlink($destPath);
            }
        }

        return $resultats;
    }

    /**
     * Normalise $_FILES['pieces_jointes'] en liste plate [[examen_id, file_info], ...].
     *
     * Gère deux structures :
     *   - Per-exam  : pieces_jointes[examen_id][] → clé = examen_id (int)
     *   - Plate     : pieces_jointes[]            → clé = 0 (général, rétro-compat)
     */
    private function normaliserFichiersParExamen(array $filesData): array {
        $result = [];
        $names  = $filesData['name'] ?? [];
        if (empty($names)) return $result;

        foreach ($names as $key => $value) {
            if (is_array($value)) {
                // Per-exam : key = examen_id (string), value = [filename, ...]
                $examenId = (int)$key;
                for ($i = 0; $i < count($value); $i++) {
                    $result[] = [$examenId, [
                        'name'     => $value[$i],
                        'tmp_name' => $filesData['tmp_name'][$key][$i] ?? '',
                        'size'     => $filesData['size'][$key][$i]     ?? 0,
                        'type'     => $filesData['type'][$key][$i]     ?? '',
                        'error'    => $filesData['error'][$key][$i]    ?? UPLOAD_ERR_NO_FILE,
                    ]];
                }
            } else {
                // Plat (rétro-compat) : key = index numérique, value = nom de fichier
                $result[] = [0, [
                    'name'     => $value,
                    'tmp_name' => $filesData['tmp_name'][$key] ?? '',
                    'size'     => $filesData['size'][$key]     ?? 0,
                    'type'     => $filesData['type'][$key]     ?? '',
                    'error'    => $filesData['error'][$key]    ?? UPLOAD_ERR_NO_FILE,
                ]];
            }
        }

        return $result;
    }

    /**
     * Téléchargement sécurisé d'une pièce jointe.
     * Route : laboratoire/piece-jointe/{id}
     */
    public function downloadPieceJointe(int $id): void {
        $this->auth->requirePermission('laboratoire', 'read');

        $stmt = $this->db->prepare("
            SELECT pj.*, d.medecin_id, d.patient_id
            FROM demande_pieces_jointes pj
            LEFT JOIN demandes_laboratoire d ON d.id = pj.demande_id
            WHERE pj.id = ?
        ");
        $stmt->execute([$id]);
        $piece = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$piece) {
            http_response_code(404);
            die('Pièce jointe introuvable.');
        }

        $cheminAbs = __DIR__ . '/../../' . $piece['fichier_chemin'];
        if (!is_file($cheminAbs)) {
            http_response_code(404);
            die('Fichier physique introuvable.');
        }

        while (ob_get_level()) ob_end_clean();
        header('Content-Type: ' . ($piece['fichier_type'] ?: 'application/octet-stream'));
        header('Content-Disposition: inline; filename="' . addslashes($piece['fichier_nom_original']) . '"');
        header('Content-Length: ' . filesize($cheminAbs));
        header('Cache-Control: private, max-age=600');
        readfile($cheminAbs);
        exit;
    }

    /**
     * Suppression d'une pièce jointe (auteur ou admin uniquement).
     * AJAX JSON. Route : laboratoire/piece-jointe-delete/{id}
     */
    public function deletePieceJointe(int $id): void {
        header('Content-Type: application/json');
        $this->auth->requirePermission('laboratoire', 'write');

        try {
            $stmt = $this->db->prepare("SELECT * FROM demande_pieces_jointes WHERE id = ?");
            $stmt->execute([$id]);
            $piece = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$piece) {
                echo json_encode(['success' => false, 'message' => 'Pièce introuvable.']);
                exit;
            }

            // Seul l'uploader ou un admin peut supprimer
            $userId   = (int)($_SESSION['user_id'] ?? 0);
            $userRole = strtoupper($_SESSION['user_role'] ?? '');
            $isAdmin  = in_array($userRole, ['ADMIN','ADMINISTRATEUR','DIRECTEUR']);
            if (!$isAdmin && (int)$piece['uploade_par'] !== $userId) {
                echo json_encode(['success' => false, 'message' => 'Vous ne pouvez supprimer que vos propres pièces.']);
                exit;
            }

            // Supprimer le fichier physique
            $abs = __DIR__ . '/../../' . $piece['fichier_chemin'];
            if (is_file($abs)) @unlink($abs);

            // Supprimer la ligne BDD
            $this->db->prepare("DELETE FROM demande_pieces_jointes WHERE id = ?")->execute([$id]);

            echo json_encode(['success' => true, 'message' => 'Pièce supprimée.']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    /**
     * Action : Mettre à jour les statuts (prélèvement fait, etc.)
     */
    public function traiterExamens(): void {
        $this->auth->requirePermission('laboratoire', 'write');
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $demande_id = (int)($_POST['demande_id'] ?? 0);
            $result = $this->laboratoireService->mettreAJourStatuts($_POST);
            if ($result['success']) {
                // Revenir sur la page traitement pour voir les changements
                header('Location: ' . BASE_URL . 'laboratoire/traitement/' . $demande_id
                    . '?success=' . urlencode('Statuts mis à jour avec succès'));
            } else {
                header('Location: ' . BASE_URL . 'laboratoire/traitement/' . $demande_id
                    . '?error=' . urlencode($result['message'] ?? 'Erreur inconnue'));
            }
            exit;
        }
    }

    /**
     * Vue : Impression des résultats pour le patient
     */
    public function imprimerResultats(int $demande_id): void {
        $this->auth->requirePermission('laboratoire', 'read');
        $demande = $this->laboratoireService->getDemandeComplete($demande_id);

        // Résultats depuis demande_examens (source principale, toujours à jour)
        $examens  = $this->laboratoireService->getExamensParDemande($demande_id);
        // Normaliser vers le format attendu par la vue
        $resultats = array_map(fn($e) => [
            'nom_examen'       => $e['nom'] ?? $e['nom_examen'] ?? '',
            'categorie'        => $e['categorie'] ?? 'AUTRE',
            'resultat'         => $e['resultat'] ?? '',
            'valeur_numerique' => $e['valeur_numerique'] ?? null,
            'unite'            => $e['unite'] ?? '',
            'valeur_normale_min' => $e['valeur_normale_min'] ?? null,
            'valeur_normale_max' => $e['valeur_normale_max'] ?? null,
            'interpretation'   => $e['interpretation'] ?? '',
            'anormal'          => (bool)($e['anormal'] ?? false),
            'urgent'           => (bool)($e['urgent'] ?? false),
        ], $examens);

        // Pièces jointes
        $piecesJointes = [];
        try {
            $tableExiste = (int)$this->db->query(
                "SELECT COUNT(*) FROM information_schema.TABLES
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'demande_pieces_jointes'"
            )->fetchColumn() > 0;
            if ($tableExiste) {
                $stmt = $this->db->prepare("
                    SELECT pj.*, u.nom AS uploader_nom, u.prenom AS uploader_prenom
                    FROM demande_pieces_jointes pj
                    LEFT JOIN users u ON u.id = pj.uploade_par
                    WHERE pj.demande_id = ?
                    ORDER BY pj.uploade_at DESC
                ");
                $stmt->execute([$demande_id]);
                $piecesJointes = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
        } catch (Exception $e) { /* ignorer */ }

        // Signature et cachet du médecin prescripteur
        $signatureMedecinSrc = null;
        $cachetMedecinSrc    = null;
        if (!empty($demande['medecin_id'])) {
            try {
                $sigService  = new SignatureService();
                $sigFull     = $sigService->getMedecinSignatureInfo((int)$demande['medecin_id']);
                $sigRaw      = $sigService->getSignature((int)$demande['medecin_id']);

                // Préférer base64 depuis la table medecin_signatures, sinon utiliser le chemin fichier
                $signatureMedecinSrc = $sigRaw['signature_image'] ?? ($sigFull['signature_src'] ?? null);
                $cachetMedecinSrc    = $sigRaw['cachet_image']    ?? ($sigFull['cachet_src']    ?? null);

                // Si c'est une URL de fichier, convertir en base64 pour l'impression
                foreach ([&$signatureMedecinSrc, &$cachetMedecinSrc] as &$src) {
                    if (!empty($src) && !str_starts_with($src, 'data:')) {
                        $urlPath   = parse_url($src, PHP_URL_PATH) ?: $src;
                        $localPath = rtrim($_SERVER['DOCUMENT_ROOT'], '/\\') . '/' . ltrim($urlPath, '/');
                        $content   = @file_get_contents($localPath);
                        if ($content) $src = 'data:image/png;base64,' . base64_encode($content);
                    }
                }
                unset($src);
            } catch (Exception $e) { /* ignorer */ }
        }

        require_once __DIR__ . '/../views/laboratoire/impression_resultats.php';
    }

    /**
     * Action : Validation individuelle d'un prélèvement (ancien code)
     */
    public function validerPrelevement(): void {
        $this->auth->requirePermission('laboratoire', 'write');
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $result = $this->laboratoireService->validerPrelevement($_POST['examen_id'], $_SESSION['user_id']);
            header('Content-Type: application/json');
            echo json_encode($result);
        }
    }

    /**
     * Action : Validation finale (ancien workflow)
     */
    public function validerResultats(): void {
        $this->auth->requirePermission('laboratoire', 'admin');
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $result = $this->laboratoireService->validerResultats($_POST['demande_id'], $_SESSION['user_id']);
            header('Content-Type: application/json');
            echo json_encode($result);
        }
    }

    /**
     * API JSON : Création d'une demande depuis la consultation
     */
    public function creerDemandeDepuisConsultation(): void {
        $this->auth->requirePermission('consultations', 'write');
        header('Content-Type: application/json');
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input || empty($input['examens'])) {
            echo json_encode(['success' => false, 'message' => 'Aucun examen sélectionné.']); return;
        }
        $patient_id      = $input['patient_id'];
        $examens         = $input['examens'];
        $medecin_id      = $_SESSION['user_id'] ?? 1;
        $consultation_id = !empty($input['consultation_id']) ? (int)$input['consultation_id'] : null;
        try {
            $this->db->beginTransaction();
            $stmtD = $this->db->prepare("INSERT INTO demandes_laboratoire (patient_id, medecin_id, consultation_id, statut, date_creation) VALUES (?, ?, ?, 'EN_ATTENTE', NOW())");
            $stmtD->execute([$patient_id, $medecin_id, $consultation_id]);
            $demande_id = $this->db->lastInsertId();
            $stmtE = $this->db->prepare("INSERT INTO demande_examens (demande_id, examen_id, urgent, a_jeun, instructions, statut) VALUES (?, ?, ?, ?, ?, 'EN_ATTENTE')");
            foreach ($examens as $ex) {
                $stmtE->execute([$demande_id, $ex['id'], ($ex['urgent'] ?? false) ? 1 : 0, ($ex['a_jeun'] ?? false) ? 1 : 0, $ex['instructions'] ?? '']);
            }
            $this->db->commit();
            echo json_encode(['success' => true, 'demande_id' => $demande_id]);
        } catch (Exception $e) {
            $this->db->rollBack();
            echo json_encode(['success' => false, 'message' => 'Erreur: ' . $e->getMessage()]);
        }
    }

    /**
     * GET laboratoire/detail-demande/{id}
     * Retourne la demande + ses examens pour le modal de mise à jour
     */
    public function detailDemande(int $demandeId): void {
        header('Content-Type: application/json');
        $userId = (int)($_SESSION['user_id'] ?? 0);
        try {
            $db = (new Database())->getConnection();
            /* Créer la colonne si elle n'existe pas */
            if (!$db->query("SHOW COLUMNS FROM demandes_laboratoire LIKE 'notes_cliniques'")->rowCount()) {
                $db->exec("ALTER TABLE demandes_laboratoire ADD COLUMN notes_cliniques TEXT NULL");
            }
            $stmt = $db->prepare("SELECT id, urgence, statut,
                COALESCE(notes_cliniques,'') as notes_cliniques
                FROM demandes_laboratoire WHERE id=? AND medecin_id=? LIMIT 1");
            $stmt->execute([$demandeId, $userId]);
            $demande = $stmt->fetch(\PDO::FETCH_ASSOC);
            if (!$demande) { echo json_encode(['success'=>false,'message'=>'Demande introuvable']); return; }

            $stmtE = $db->prepare("
                SELECT de.id as de_id, de.examen_id, de.urgent, de.a_jeun, de.instructions,
                       el.nom, el.categorie, el.type_prelevement, el.delai_rendu_heures
                FROM demande_examens de
                JOIN examens_laboratoire el ON el.id = de.examen_id
                WHERE de.demande_id = ?
                ORDER BY el.categorie, el.nom");
            $stmtE->execute([$demandeId]);
            $examens = $stmtE->fetchAll(\PDO::FETCH_ASSOC);

            echo json_encode(['success'=>true,'demande'=>$demande,'examens'=>$examens]);
        } catch (\Throwable $e) {
            echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
        }
    }

    /**
     * POST laboratoire/mettre-a-jour-demande/{id}
     * Ajoute/supprime des examens, change urgence et notes cliniques
     */
    public function mettreAJourDemande(int $demandeId): void {
        header('Content-Type: application/json');
        $userId = (int)($_SESSION['user_id'] ?? 0);
        $input  = json_decode(file_get_contents('php://input'), true) ?? [];

        try {
            $db = (new Database())->getConnection();
            /* Vérifier ownership + statut */
            $stmt = $db->prepare("SELECT id FROM demandes_laboratoire WHERE id=? AND medecin_id=? AND statut='EN_ATTENTE' LIMIT 1");
            $stmt->execute([$demandeId, $userId]);
            if (!$stmt->fetchColumn()) { echo json_encode(['success'=>false,'message'=>'Demande introuvable ou déjà traitée']); return; }

            $db->beginTransaction();

            /* Mettre à jour urgence + notes_cliniques */
            $urgence = in_array($input['urgence'] ?? '', ['URGENT','NORMAL']) ? $input['urgence'] : 'NORMAL';
            $notes   = trim($input['notes_cliniques'] ?? '');
            /* Assurer la colonne notes_cliniques */
            try {
                if (!$db->query("SHOW COLUMNS FROM demandes_laboratoire LIKE 'notes_cliniques'")->rowCount()) {
                    $db->exec("ALTER TABLE demandes_laboratoire ADD COLUMN notes_cliniques TEXT NULL");
                }
            } catch (\Throwable $e) {}
            $db->prepare("UPDATE demandes_laboratoire SET urgence=?, notes_cliniques=? WHERE id=?")
               ->execute([$urgence, $notes ?: null, $demandeId]);

            /* Supprimer des examens */
            $supprimer = $input['examens_supprimer'] ?? [];
            if (!empty($supprimer)) {
                $placeholders = implode(',', array_fill(0, count($supprimer), '?'));
                $db->prepare("DELETE FROM demande_examens WHERE id IN ($placeholders) AND demande_id=?")
                   ->execute([...$supprimer, $demandeId]);
            }

            /* Ajouter des examens */
            $ajouter = $input['examens_ajouter'] ?? [];
            if (!empty($ajouter)) {
                $stmtAdd = $db->prepare("INSERT INTO demande_examens (demande_id, examen_id, urgent, a_jeun, instructions, statut) VALUES (?,?,?,?,?,'EN_ATTENTE')");
                foreach ($ajouter as $ex) {
                    $exId = (int)($ex['id'] ?? 0);
                    if ($exId > 0) {
                        $stmtAdd->execute([$demandeId, $exId, !empty($ex['urgent'])?1:0, !empty($ex['a_jeun'])?1:0, trim($ex['instructions']??'')]);
                    }
                }
            }

            $db->commit();
            echo json_encode(['success'=>true]);
        } catch (\Throwable $e) {
            if (isset($db) && $db->inTransaction()) $db->rollBack();
            echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
        }
    }

    /**
     * POST laboratoire/supprimer-demande/{id}
     * Annule une demande EN_ATTENTE appartenant au médecin connecté
     */
    public function supprimerDemande(int $demandeId): void {
        header('Content-Type: application/json');
        $userId = (int)($_SESSION['user_id'] ?? 0);
        if (!$userId || !$demandeId) {
            echo json_encode(['success' => false, 'message' => 'Non autorisé']); return;
        }
        try {
            /* Vérifier que la demande appartient au médecin et est annulable */
            $stmt = $this->db->prepare(
                "SELECT id FROM demandes_laboratoire WHERE id = ? AND medecin_id = ? AND statut = 'EN_ATTENTE' LIMIT 1"
            );
            $stmt->execute([$demandeId, $userId]);
            if (!$stmt->fetchColumn()) {
                echo json_encode(['success' => false, 'message' => 'Demande introuvable ou déjà traitée']); return;
            }
            /* Supprimer examens liés puis la demande */
            $this->db->prepare("DELETE FROM demande_examens WHERE demande_id = ?")->execute([$demandeId]);
            $this->db->prepare("DELETE FROM demandes_laboratoire WHERE id = ?")->execute([$demandeId]);
            echo json_encode(['success' => true]);
        } catch (\PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    // ════════════════════════════════════════════════════════════════════════
    // NOUVEAU WORKFLOW DISPATCH / TECHNICIEN / VALIDATION
    // ════════════════════════════════════════════════════════════════════════

    /**
     * JSON : Liste des techniciens de laboratoire actifs
     */
    public function getTechniciensJson(): void {
        header('Content-Type: application/json');
        $stmt = $this->db->prepare("
            SELECT u.id,
                   CONCAT(u.nom, ' ', u.prenom) AS nom_complet,
                   (SELECT COUNT(*)
                    FROM demande_examens de
                    WHERE de.technicien_id = u.id
                      AND de.statut IN ('ASSIGNEE','PRELEVE','EN_COURS')) AS charge_actuelle
            FROM users u
            WHERE u.role = 'TECHNICIEN_LABO' AND u.actif = 1
            ORDER BY u.nom
        ");
        $stmt->execute();
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        exit;
    }

    /**
     * JSON GET : Examens d'une demande avec leur technicien assigné (pour modal dispatch)
     */
    public function examensDemandeJson(int $demandeId): void {
        header('Content-Type: application/json');
        $stmt = $this->db->prepare("
            SELECT de.id, de.statut, de.technicien_id,
                   el.nom AS nom_examen, el.categorie,
                   CONCAT(tech.nom,' ',tech.prenom) AS technicien_nom
            FROM demande_examens de
            JOIN examens_laboratoire el ON de.examen_id = el.id
            LEFT JOIN users tech ON de.technicien_id = tech.id
            WHERE de.demande_id = ?
            ORDER BY el.categorie, el.nom
        ");
        $stmt->execute([$demandeId]);
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        exit;
    }

    /**
     * JSON POST : Dispatcher les examens d'une demande aux techniciens
     * Body JSON : { "assignments": { "examen_id": tech_id, ... } }
     */
    public function dispatcherExamens(int $demandeId): void {
        header('Content-Type: application/json');
        if (!$this->isLaborantin()) { echo json_encode(['success' => false, 'message' => 'Accès refusé.']); exit; }

        $input       = json_decode(file_get_contents('php://input'), true);
        $assignments = $input['assignments'] ?? [];

        if (empty($assignments)) {
            echo json_encode(['success' => false, 'message' => 'Aucune assignation fournie.']); exit;
        }

        try {
            $this->db->beginTransaction();

            $stmt = $this->db->prepare("
                UPDATE demande_examens
                SET technicien_id = ?, statut = 'ASSIGNEE', date_assignation = NOW()
                WHERE id = ? AND demande_id = ?
            ");
            $nbAssigned = 0;
            foreach ($assignments as $examenId => $technicienId) {
                if ($technicienId) {
                    $stmt->execute([(int)$technicienId, (int)$examenId, $demandeId]);
                    $nbAssigned += $stmt->rowCount();
                }
            }

            if ($nbAssigned > 0) {
                $this->db->prepare("
                    UPDATE demandes_laboratoire SET statut = 'DISPATCHE' WHERE id = ?
                ")->execute([$demandeId]);
            }

            $this->db->commit();
            echo json_encode(['success' => true, 'message' => "$nbAssigned examen(s) dispatché(s) avec succès."]);
        } catch (Exception $e) {
            $this->db->rollBack();
            echo json_encode(['success' => false, 'message' => 'Erreur : ' . $e->getMessage()]);
        }
        exit;
    }

    /**
     * Dashboard technicien : examens assignés + rejetés + historique
     */
    public function dashboardTechnicien(): void {
        if (!$this->isTechnicien()) {
            http_response_code(403);
            $error_cause = "Ce tableau de bord est réservé aux techniciens de laboratoire.";
            require __DIR__ . '/../views/errors/403.php';
            exit;
        }

        $technicienId = (int)$_SESSION['user_id'];

        // Examens assignés (à traiter)
        $stmtA = $this->db->prepare("
            SELECT de.*,
                   el.nom AS nom_examen, el.categorie, el.unite,
                   el.valeur_normale_min, el.valeur_normale_max, el.type_prelevement,
                   p.nom AS patient_nom, p.prenom AS patient_prenom, p.dossier_numero,
                   p.date_naissance, p.sexe,
                   d.observations, d.id AS demande_id,
                   u.nom AS medecin_nom, u.prenom AS medecin_prenom
            FROM demande_examens de
            JOIN examens_laboratoire el   ON de.examen_id   = el.id
            JOIN demandes_laboratoire d   ON de.demande_id  = d.id
            JOIN patients p               ON d.patient_id   = p.id
            LEFT JOIN users u             ON d.medecin_id   = u.id
            WHERE de.technicien_id = ?
              AND de.statut IN ('ASSIGNEE','PRELEVE','EN_COURS')
            ORDER BY de.urgent DESC, de.date_assignation ASC
        ");
        $stmtA->execute([$technicienId]);
        $examens_assignes = $stmtA->fetchAll(PDO::FETCH_ASSOC);

        // Examens rejetés (à recommencer)
        $stmtR = $this->db->prepare("
            SELECT de.*,
                   el.nom AS nom_examen, el.categorie, el.unite,
                   el.valeur_normale_min, el.valeur_normale_max,
                   p.nom AS patient_nom, p.prenom AS patient_prenom, p.dossier_numero,
                   d.id AS demande_id,
                   val.nom AS valideur_nom, val.prenom AS valideur_prenom
            FROM demande_examens de
            JOIN examens_laboratoire el   ON de.examen_id  = el.id
            JOIN demandes_laboratoire d   ON de.demande_id = d.id
            JOIN patients p               ON d.patient_id  = p.id
            LEFT JOIN users val           ON de.valide_par = val.id
            WHERE de.technicien_id = ? AND de.statut = 'REJETE'
            ORDER BY de.date_validation DESC
        ");
        $stmtR->execute([$technicienId]);
        $examens_rejetes = $stmtR->fetchAll(PDO::FETCH_ASSOC);

        // Historique des 7 derniers jours (validés)
        $stmtH = $this->db->prepare("
            SELECT de.*,
                   el.nom AS nom_examen, el.categorie,
                   p.nom AS patient_nom, p.prenom AS patient_prenom,
                   val.nom AS valideur_nom, val.prenom AS valideur_prenom
            FROM demande_examens de
            JOIN examens_laboratoire el   ON de.examen_id  = el.id
            JOIN demandes_laboratoire d   ON de.demande_id = d.id
            JOIN patients p               ON d.patient_id  = p.id
            LEFT JOIN users val           ON de.valide_par = val.id
            WHERE de.technicien_id = ? AND de.statut = 'VALIDE'
              AND de.date_validation >= DATE_SUB(NOW(), INTERVAL 7 DAY)
            ORDER BY de.date_validation DESC
        ");
        $stmtH->execute([$technicienId]);
        $examens_valides = $stmtH->fetchAll(PDO::FETCH_ASSOC);

        // Charger les sous-paramètres pour les examens assignés et rejetés
        $sousParamsMap = [];
        try {
            $spTableExiste = (int)$this->db->query(
                "SELECT COUNT(*) FROM information_schema.TABLES
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'examen_sous_parametres'"
            )->fetchColumn() > 0;
            if ($spTableExiste) {
                $examenIds = array_unique(array_merge(
                    array_column($examens_assignes, 'examen_id'),
                    array_column($examens_rejetes,  'examen_id')
                ));
                if (!empty($examenIds)) {
                    $spPhs  = implode(',', array_fill(0, count($examenIds), '?'));
                    $stmtSP = $this->db->prepare("
                        SELECT * FROM examen_sous_parametres
                        WHERE examen_id IN ($spPhs)
                        ORDER BY examen_id, ordre
                    ");
                    $stmtSP->execute($examenIds);
                    foreach ($stmtSP->fetchAll(PDO::FETCH_ASSOC) as $sp) {
                        $sousParamsMap[(int)$sp['examen_id']][] = $sp;
                    }
                }
            }
        } catch (Exception $e) { /* ignorer */ }

        foreach ($examens_assignes as &$ex) {
            $ex['sous_params'] = $sousParamsMap[(int)$ex['examen_id']] ?? [];
        }
        unset($ex);
        foreach ($examens_rejetes as &$ex) {
            $ex['sous_params'] = $sousParamsMap[(int)$ex['examen_id']] ?? [];
        }
        unset($ex);

        require_once __DIR__ . '/../views/laboratoire/dashboard_technicien.php';
    }

    /**
     * JSON POST : Technicien soumet les résultats d'un examen pour validation
     * Body JSON : { examen_id, resultat, valeur_numerique, interpretation }
     */
    public function soumettreResultatsTechnicien(): void {
        header('Content-Type: application/json');
        if (!$this->isTechnicien()) { echo json_encode(['success' => false, 'message' => 'Accès refusé.']); exit; }

        $input          = json_decode(file_get_contents('php://input'), true);
        $examenId       = (int)($input['examen_id'] ?? 0);
        $resultat       = trim($input['resultat'] ?? '');
        $valeurNum      = (isset($input['valeur_numerique']) && $input['valeur_numerique'] !== '') ? (float)$input['valeur_numerique'] : null;
        $interpretation = trim($input['interpretation'] ?? '');
        $sousParams     = $input['sous_params'] ?? [];   // [{id, valeur_numerique}, ...]
        $isMultiParam   = !empty($sousParams);

        if (!$examenId) {
            echo json_encode(['success' => false, 'message' => 'Examen non identifié.']); exit;
        }

        // Multi-param : résultat auto-généré si vide
        if ($isMultiParam && $resultat === '') {
            try {
                $stmtP = $this->db->prepare("
                    SELECT id, nom, code, unite
                    FROM examen_sous_parametres
                    WHERE examen_id = (SELECT examen_id FROM demande_examens WHERE id = ?)
                    ORDER BY ordre
                ");
                $stmtP->execute([$examenId]);
                $paramMeta = [];
                foreach ($stmtP->fetchAll(PDO::FETCH_ASSOC) as $p) {
                    $paramMeta[(int)$p['id']] = $p;
                }
                $parts = [];
                foreach ($sousParams as $sp) {
                    $pid = (int)($sp['id'] ?? 0);
                    $val = ($sp['valeur_numerique'] !== '' && $sp['valeur_numerique'] !== null)
                           ? $sp['valeur_numerique'] : null;
                    if ($val !== null && isset($paramMeta[$pid])) {
                        $meta   = $paramMeta[$pid];
                        $parts[] = ($meta['code'] ?: $meta['nom']) . ': ' . $val
                                 . ($meta['unite'] ? ' ' . $meta['unite'] : '');
                    }
                }
                $resultat = $parts ? implode(' | ', $parts) : 'Multi-paramètres';
            } catch (Exception $e) { $resultat = 'Multi-paramètres'; }
        }

        if (!$isMultiParam && $resultat === '') {
            echo json_encode(['success' => false, 'message' => 'Résultat obligatoire.']); exit;
        }

        // Vérifier que l'examen appartient bien à ce technicien
        $stmt = $this->db->prepare("
            SELECT de.*, d.medecin_id, d.patient_id
            FROM demande_examens de
            JOIN demandes_laboratoire d ON de.demande_id = d.id
            WHERE de.id = ? AND de.technicien_id = ?
              AND de.statut IN ('ASSIGNEE','PRELEVE','EN_COURS','REJETE')
        ");
        $stmt->execute([$examenId, $_SESSION['user_id']]);
        $examen = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$examen) {
            echo json_encode(['success' => false, 'message' => 'Examen introuvable ou non assigné à vous.']); exit;
        }

        try {
            $this->db->beginTransaction();

            // Soumettre → SOUMIS
            $this->db->prepare("
                UPDATE demande_examens
                SET resultat = ?, valeur_numerique = ?, interpretation = ?,
                    statut = 'SOUMIS', date_soumission = NOW(), motif_rejet = NULL
                WHERE id = ?
            ")->execute([$resultat, $valeurNum, $interpretation, $examenId]);

            // Enregistrer les valeurs des sous-paramètres (multi-param)
            if ($isMultiParam && !empty($sousParams)) {
                $devTableExiste = (int)$this->db->query(
                    "SELECT COUNT(*) FROM information_schema.TABLES
                     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'demande_examen_valeurs'"
                )->fetchColumn() > 0;
                if ($devTableExiste) {
                    $stmtDev = $this->db->prepare("
                        INSERT INTO demande_examen_valeurs (demande_examen_id, sous_parametre_id, valeur_numerique)
                        VALUES (?, ?, ?)
                        ON DUPLICATE KEY UPDATE valeur_numerique = VALUES(valeur_numerique)
                    ");
                    foreach ($sousParams as $sp) {
                        $pid = (int)($sp['id'] ?? 0);
                        $val = ($sp['valeur_numerique'] !== '' && $sp['valeur_numerique'] !== null)
                               ? (float)$sp['valeur_numerique'] : null;
                        if ($pid) $stmtDev->execute([$examenId, $pid, $val]);
                    }
                }
            }

            // Si tous les examens de cette demande sont soumis/validés → RESULTATS_SOUMIS
            $stmtCheck = $this->db->prepare("
                SELECT COUNT(*) FROM demande_examens
                WHERE demande_id = ? AND statut NOT IN ('SOUMIS','VALIDE')
            ");
            $stmtCheck->execute([$examen['demande_id']]);
            if ((int)$stmtCheck->fetchColumn() === 0) {
                $this->db->prepare("
                    UPDATE demandes_laboratoire SET statut = 'RESULTATS_SOUMIS' WHERE id = ?
                ")->execute([$examen['demande_id']]);
            }

            $this->db->commit();

            // ── Audit : résultat de laboratoire soumis ──
            try {
                $pNom = '';
                if (!empty($examen['patient_id'])) {
                    $pRow = $this->db->prepare("SELECT nom, prenom FROM patients WHERE id = ?");
                    $pRow->execute([$examen['patient_id']]);
                    $pat  = $pRow->fetch(PDO::FETCH_ASSOC);
                    $pNom = $pat ? trim(($pat['nom'] ?? '') . ' ' . ($pat['prenom'] ?? '')) : '';
                }
                $desc = 'Résultat de laboratoire soumis'
                      . ($pNom ? " — patient : $pNom" : '')
                      . ' : ' . mb_substr($resultat, 0, 140);
                $this->audit->log('UPDATE', 'demande_examens', $examenId, $desc, null, [
                    'patient'          => $pNom,
                    'resultat'         => mb_substr($resultat, 0, 200),
                    'valeur_numerique' => $valeurNum,
                    'interpretation'   => mb_substr($interpretation, 0, 150),
                ]);
            } catch (Exception $e) { error_log('[Labo::soumettreResultats] Audit: ' . $e->getMessage()); }

            echo json_encode(['success' => true, 'message' => 'Résultats soumis. Le laborantin va valider.']);
        } catch (Exception $e) {
            $this->db->rollBack();
            echo json_encode(['success' => false, 'message' => 'Erreur : ' . $e->getMessage()]);
        }
        exit;
    }

    /**
     * JSON POST : Laborantin valide un examen soumis par un technicien
     */
    public function validerExamen(int $id): void {
        header('Content-Type: application/json');
        if (!$this->isLaborantin()) { echo json_encode(['success' => false, 'message' => 'Accès refusé.']); exit; }

        $stmt = $this->db->prepare("
            SELECT de.*, d.patient_id, d.medecin_id
            FROM demande_examens de
            JOIN demandes_laboratoire d ON de.demande_id = d.id
            WHERE de.id = ? AND de.statut = 'SOUMIS'
        ");
        $stmt->execute([$id]);
        $examen = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$examen) {
            echo json_encode(['success' => false, 'message' => 'Examen introuvable ou non soumis.']); exit;
        }

        // Lire le commentaire biologiste optionnel (JSON body)
        $input        = json_decode(file_get_contents('php://input'), true) ?? [];
        $commentaire  = trim($input['commentaire'] ?? '');

        try {
            $this->db->beginTransaction();

            // Valider l'examen + enregistrer le commentaire biologiste
            $this->db->prepare("
                UPDATE demande_examens
                SET statut = 'VALIDE', valide_par = ?, date_validation = NOW(),
                    commentaire_biologiste = ?
                WHERE id = ?
            ")->execute([$_SESSION['user_id'], $commentaire ?: null, $id]);

            // Récupérer les infos de l'examen catalogue pour les valeurs normales
            $stmtEl = $this->db->prepare("SELECT * FROM examens_laboratoire WHERE id = ?");
            $stmtEl->execute([$examen['examen_id']]);
            $examInfo = $stmtEl->fetch(PDO::FETCH_ASSOC);

            // Calculer si anormal
            $anormal = 0;
            if ($examen['valeur_numerique'] !== null
                && $examInfo['valeur_normale_min'] !== null
                && $examInfo['valeur_normale_max'] !== null) {
                $anormal = ($examen['valeur_numerique'] < $examInfo['valeur_normale_min']
                         || $examen['valeur_numerique'] > $examInfo['valeur_normale_max']) ? 1 : 0;
            }

            // Archiver dans patient_resultats_labo
            $this->db->prepare("
                INSERT INTO patient_resultats_labo
                    (patient_id, demande_id, examen_id, nom_examen, resultat,
                     valeur_numerique, unite, valeur_normale_min, valeur_normale_max,
                     interpretation, anormal, date_resultat, medecin_prescripteur_id)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?)
                ON DUPLICATE KEY UPDATE
                    resultat = VALUES(resultat),
                    valeur_numerique = VALUES(valeur_numerique),
                    anormal = VALUES(anormal),
                    date_resultat = NOW()
            ")->execute([
                $examen['patient_id'], $examen['demande_id'], $examen['examen_id'],
                $examInfo['nom'] ?? '',
                $examen['resultat'], $examen['valeur_numerique'],
                $examInfo['unite'] ?? '',
                $examInfo['valeur_normale_min'], $examInfo['valeur_normale_max'],
                $examen['interpretation'], $anormal,
                $examen['medecin_id'],
            ]);

            // Vérifier si TOUS les examens de la demande sont validés
            $stmtCheck = $this->db->prepare("
                SELECT COUNT(*) FROM demande_examens
                WHERE demande_id = ? AND statut != 'VALIDE'
            ");
            $stmtCheck->execute([$examen['demande_id']]);
            $tousValides = (int)$stmtCheck->fetchColumn() === 0;

            if ($tousValides) {
                $this->db->prepare("
                    UPDATE demandes_laboratoire
                    SET statut = 'VALIDES', date_resultats = NOW(),
                        valide_par = ?, date_validation = NOW()
                    WHERE id = ?
                ")->execute([$_SESSION['user_id'], $examen['demande_id']]);

                // Notifier le médecin
                $this->envoyerNotifMedecin($examen['demande_id'], $examen['medecin_id'], $examen['patient_id']);
                // Activer les RDV conditionnels liés à ce bilan
                require_once __DIR__ . '/SuiviExterneController.php';
                SuiviExterneController::activerRdvConditionnels($this->db, $examen['demande_id'], $examen['patient_id']);
            }

            $this->db->commit();
            echo json_encode([
                'success'      => true,
                'message'      => 'Examen validé avec succès.',
                'tous_valides' => $tousValides,
            ]);
        } catch (Exception $e) {
            $this->db->rollBack();
            echo json_encode(['success' => false, 'message' => 'Erreur : ' . $e->getMessage()]);
        }
        exit;
    }

    /**
     * JSON POST : Valider en lot tous les examens SOUMIS d'une demande
     * Body JSON : { commentaire? }
     */
    public function validerDemandeBatch(int $demandeId): void {
        header('Content-Type: application/json');
        if (!$this->isLaborantin()) {
            echo json_encode(['success' => false, 'message' => 'Accès refusé.']); exit;
        }

        $input       = json_decode(file_get_contents('php://input'), true) ?? [];
        $commentaire = trim($input['commentaire'] ?? '');

        // Récupérer tous les examens SOUMIS de la demande
        $stmt = $this->db->prepare("
            SELECT de.*, d.patient_id, d.medecin_id
            FROM demande_examens de
            JOIN demandes_laboratoire d ON de.demande_id = d.id
            WHERE de.demande_id = ? AND de.statut = 'SOUMIS'
        ");
        $stmt->execute([$demandeId]);
        $examens = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($examens)) {
            echo json_encode(['success' => false, 'message' => 'Aucun examen soumis pour cette demande.']); exit;
        }

        try {
            $this->db->beginTransaction();
            $userId   = (int)$_SESSION['user_id'];
            $nbValide = 0;

            foreach ($examens as $examen) {
                // Valider l'examen
                $this->db->prepare("
                    UPDATE demande_examens
                    SET statut = 'VALIDE', valide_par = ?, date_validation = NOW(),
                        commentaire_biologiste = ?
                    WHERE id = ?
                ")->execute([$userId, $commentaire ?: null, $examen['id']]);

                // Récupérer infos catalogue
                $stmtEl = $this->db->prepare("SELECT * FROM examens_laboratoire WHERE id = ?");
                $stmtEl->execute([$examen['examen_id']]);
                $examInfo = $stmtEl->fetch(PDO::FETCH_ASSOC) ?: [];

                // Calculer si anormal
                $anormal = 0;
                if ($examen['valeur_numerique'] !== null
                    && !empty($examInfo['valeur_normale_min'])
                    && !empty($examInfo['valeur_normale_max'])) {
                    $anormal = ($examen['valeur_numerique'] < $examInfo['valeur_normale_min']
                             || $examen['valeur_numerique'] > $examInfo['valeur_normale_max']) ? 1 : 0;
                }

                // Archiver
                $this->db->prepare("
                    INSERT INTO patient_resultats_labo
                        (patient_id, demande_id, examen_id, nom_examen, resultat,
                         valeur_numerique, unite, valeur_normale_min, valeur_normale_max,
                         interpretation, anormal, date_resultat, medecin_prescripteur_id)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?)
                    ON DUPLICATE KEY UPDATE
                        resultat = VALUES(resultat),
                        valeur_numerique = VALUES(valeur_numerique),
                        anormal = VALUES(anormal), date_resultat = NOW()
                ")->execute([
                    $examen['patient_id'], $examen['demande_id'], $examen['examen_id'],
                    $examInfo['nom'] ?? '',
                    $examen['resultat'], $examen['valeur_numerique'],
                    $examInfo['unite'] ?? '',
                    $examInfo['valeur_normale_min'] ?? null, $examInfo['valeur_normale_max'] ?? null,
                    $examen['interpretation'], $anormal,
                    $examen['medecin_id'],
                ]);

                $nbValide++;
            }

            // Mettre à jour la demande → VALIDES
            $this->db->prepare("
                UPDATE demandes_laboratoire
                SET statut = 'VALIDES', date_resultats = NOW(),
                    valide_par = ?, date_validation = NOW()
                WHERE id = ?
            ")->execute([$userId, $demandeId]);

            // Notifier le médecin (un seul envoi pour la demande)
            $firstEx = $examens[0];
            $this->envoyerNotifMedecin($demandeId, $firstEx['medecin_id'], $firstEx['patient_id']);
            // Activer les RDV conditionnels liés à ce bilan
            require_once __DIR__ . '/SuiviExterneController.php';
            SuiviExterneController::activerRdvConditionnels($this->db, $demandeId, $firstEx['patient_id']);

            $this->db->commit();
            echo json_encode([
                'success'    => true,
                'nb_valides' => $nbValide,
                'message'    => "$nbValide examen(s) validé(s). Le médecin a été notifié.",
            ]);
        } catch (Exception $e) {
            $this->db->rollBack();
            echo json_encode(['success' => false, 'message' => 'Erreur : ' . $e->getMessage()]);
        }
        exit;
    }

    /**
     * JSON POST : Laborantin rejette un examen soumis (technicien doit recommencer)
     * Body JSON : { motif }
     */
    public function rejeterExamen(int $id): void {
        header('Content-Type: application/json');
        if (!$this->isLaborantin()) { echo json_encode(['success' => false, 'message' => 'Accès refusé.']); exit; }

        $input = json_decode(file_get_contents('php://input'), true);
        $motif = trim($input['motif'] ?? 'Résultat non conforme — veuillez recommencer l\'analyse.');

        $stmt = $this->db->prepare("
            SELECT id, statut, demande_id FROM demande_examens WHERE id = ? AND statut = 'SOUMIS'
        ");
        $stmt->execute([$id]);
        $examen = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$examen) {
            echo json_encode(['success' => false, 'message' => 'Examen introuvable ou déjà traité.']); exit;
        }

        try {
            $this->db->beginTransaction();

            // Rejeter → technicien doit recommencer (retour à ASSIGNEE avec motif)
            $this->db->prepare("
                UPDATE demande_examens
                SET statut = 'REJETE', motif_rejet = ?, valide_par = ?, date_validation = NOW(),
                    resultat = NULL, valeur_numerique = NULL, interpretation = NULL, date_soumission = NULL
                WHERE id = ?
            ")->execute([$motif, $_SESSION['user_id'], $id]);

            // Remettre la demande en DISPATCHE (pas encore tous validés)
            $this->db->prepare("
                UPDATE demandes_laboratoire SET statut = 'DISPATCHE' WHERE id = ?
            ")->execute([$examen['demande_id']]);

            $this->db->commit();
            echo json_encode(['success' => true, 'message' => 'Examen rejeté. Le technicien a été notifié.']);
        } catch (Exception $e) {
            $this->db->rollBack();
            echo json_encode(['success' => false, 'message' => 'Erreur : ' . $e->getMessage()]);
        }
        exit;
    }

    // ────────────────────────────────────────────────────────────
    // NUMÉRO D'ORDRE
    // ────────────────────────────────────────────────────────────

    /**
     * POST /laboratoire/numero-ordre/{id}
     * Enregistre (ou met à jour) le numéro d'ordre d'une demande labo.
     * Format attendu : XXX-XXX (lettres et/ou chiffres)
     */
    public function numeroOrdre(int $demandeId): void {
        header('Content-Type: application/json');

        // Auth : laborantins, secrétaires labo, admins
        $role = strtoupper($_SESSION['user_role'] ?? '');
        if (!in_array($role, ['LABORANTIN', 'SECRETAIRE_LABO', 'ADMINISTRATEUR', 'ADMIN', 'MEDECIN_CHEF'])) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Accès refusé.']);
            return;
        }

        $body  = json_decode(file_get_contents('php://input'), true);
        $valeur = trim($body['numero_ordre'] ?? '');

        // Validation format
        if (!preg_match('/^[A-Z0-9]{1,6}-[A-Z0-9]{1,6}$/i', $valeur)) {
            echo json_encode(['success' => false, 'message' => 'Format invalide. Exemple : 001-001 ou LAB-042']);
            return;
        }
        $valeur = strtoupper($valeur);

        try {
            // Créer la colonne si elle n'existe pas encore
            $colExiste = $this->db->query(
                "SELECT COUNT(*) FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE()
                   AND TABLE_NAME   = 'demandes_laboratoire'
                   AND COLUMN_NAME  = 'numero_ordre'"
            )->fetchColumn();

            if (!$colExiste) {
                $this->db->exec(
                    "ALTER TABLE demandes_laboratoire
                     ADD COLUMN numero_ordre VARCHAR(20) DEFAULT NULL AFTER statut_facturation"
                );
            }

            // Vérifier que la demande existe
            $exists = $this->db->prepare(
                "SELECT id FROM demandes_laboratoire WHERE id = ? LIMIT 1"
            );
            $exists->execute([$demandeId]);
            if (!$exists->fetchColumn()) {
                echo json_encode(['success' => false, 'message' => 'Demande introuvable.']);
                return;
            }

            // Mise à jour
            $stmt = $this->db->prepare(
                "UPDATE demandes_laboratoire SET numero_ordre = ? WHERE id = ?"
            );
            $stmt->execute([$valeur, $demandeId]);

            echo json_encode([
                'success'       => true,
                'message'       => 'Numéro d\'ordre ' . $valeur . ' enregistré.',
                'numero_ordre'  => $valeur,
            ]);

        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Erreur base de données : ' . $e->getMessage()]);
        }
    }

    // ────────────────────────────────────────────────────────────
    // SECRÉTARIAT LABORATOIRE
    // ────────────────────────────────────────────────────────────

    private function isSecretaireLabo(): bool {
        $r = strtoupper($_SESSION['user_role'] ?? '');
        return in_array($r, ['SECRETAIRE_LABO', 'LABORANTIN', 'ADMINISTRATEUR', 'ADMIN']);
    }

    /**
     * Dashboard secrétariat labo — liste des demandes + historique
     * GET /laboratoire/secretariat
     */
    public function secretariat(): void {
        if (!$this->isSecretaireLabo()) {
            http_response_code(403);
            $error_cause = "Ce tableau de bord est réservé au secrétariat du laboratoire.";
            require __DIR__ . '/../views/errors/403.php';
            exit;
        }

        // ── Demandes actives (toutes sauf VALIDES) avec infos patient complètes ──
        $stmt = $this->db->prepare("
            SELECT
                d.id, d.statut, d.date_creation, d.urgence, d.observations,
                d.statut_facturation, d.date_facturation, d.mode_paiement_labo,
                p.nom, p.prenom, p.sexe, p.date_naissance, p.telephone,
                p.type_client, p.numero_assure, p.adresse, p.dossier_numero,
                u.nom   AS medecin_nom,  u.prenom   AS medecin_prenom,
                sec.nom AS sec_nom,      sec.prenom AS sec_prenom,
                COUNT(de.id)                       AS nb_examens,
                COALESCE(SUM(de.urgent), 0)        AS nb_urgents,
                GROUP_CONCAT(el.nom ORDER BY el.nom SEPARATOR ', ') AS liste_examens
            FROM demandes_laboratoire d
            JOIN patients p            ON d.patient_id  = p.id
            LEFT JOIN users u          ON d.medecin_id  = u.id
            LEFT JOIN users sec        ON d.facture_par = sec.id
            LEFT JOIN demande_examens de ON d.id = de.demande_id
            LEFT JOIN examens_laboratoire el ON de.examen_id = el.id
            WHERE d.statut NOT IN ('VALIDES')
            GROUP BY d.id
            ORDER BY
                d.statut_facturation ASC,
                d.urgence DESC,
                d.date_creation DESC
        ");
        $stmt->execute();
        $demandes_secretariat = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // ── KPIs ──────────────────────────────────────────────
        $nb_total_jour   = (int)$this->db->query(
            "SELECT COUNT(*) FROM demandes_laboratoire WHERE DATE(date_creation) = CURDATE()"
        )->fetchColumn();
        $nb_non_facture  = (int)$this->db->query(
            "SELECT COUNT(*) FROM demandes_laboratoire
             WHERE statut_facturation = 'NON_FACTURE' AND statut NOT IN ('VALIDES')"
        )->fetchColumn();
        $nb_facture_jour = (int)$this->db->query(
            "SELECT COUNT(*) FROM demandes_laboratoire
             WHERE statut_facturation = 'FACTURE' AND DATE(date_facturation) = CURDATE()"
        )->fetchColumn();

        // ── Historique (filtres GET) ───────────────────────────
        $dateDebut  = $_GET['debut']              ?? date('Y-m-d', strtotime('-30 days'));
        $dateFin    = $_GET['fin']                ?? date('Y-m-d');
        $filtFact   = $_GET['statut_facturation'] ?? '';
        $filtDossier = trim($_GET['dossier']      ?? '');

        $histSql = "
            SELECT
                d.id, d.statut, d.date_creation,
                d.statut_facturation, d.date_facturation, d.mode_paiement_labo,
                p.nom, p.prenom, p.dossier_numero, p.type_client, p.numero_assure,
                u.nom   AS medecin_nom,  u.prenom   AS medecin_prenom,
                sec.nom AS sec_nom,      sec.prenom AS sec_prenom,
                COUNT(de.id) AS nb_examens,
                GROUP_CONCAT(el.nom ORDER BY el.nom SEPARATOR ', ') AS liste_examens
            FROM demandes_laboratoire d
            JOIN patients p              ON d.patient_id  = p.id
            LEFT JOIN users u            ON d.medecin_id  = u.id
            LEFT JOIN users sec          ON d.facture_par = sec.id
            LEFT JOIN demande_examens de ON d.id = de.demande_id
            LEFT JOIN examens_laboratoire el ON de.examen_id = el.id
            WHERE DATE(d.date_creation) BETWEEN ? AND ?
        ";
        $histParams = [$dateDebut, $dateFin];
        if ($filtFact) {
            $histSql .= " AND d.statut_facturation = ?";
            $histParams[] = $filtFact;
        }
        if ($filtDossier !== '') {
            // Recherche par N° dossier exact OU par nom/prénom (LIKE)
            $histSql .= " AND (p.dossier_numero LIKE ? OR p.nom LIKE ? OR p.prenom LIKE ?)";
            $like = '%' . $filtDossier . '%';
            $histParams[] = $like;
            $histParams[] = $like;
            $histParams[] = $like;
        }
        $histSql .= " GROUP BY d.id ORDER BY d.date_creation DESC";

        $stmtH = $this->db->prepare($histSql);
        $stmtH->execute($histParams);
        $historique_facturation = $stmtH->fetchAll(PDO::FETCH_ASSOC);

        require_once __DIR__ . '/../views/laboratoire/dashboard_secretariat.php';
    }

    /**
     * JSON POST : Marquer une demande comme facturée / non facturée
     * POST /laboratoire/facturer/{id}
     * Body JSON : { action: 'FACTURE'|'NON_FACTURE', mode_paiement: string }
     */
    public function facturer(int $demandeId): void {
        header('Content-Type: application/json');
        if (!$this->isSecretaireLabo()) {
            echo json_encode(['success' => false, 'message' => 'Accès refusé.']); exit;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Méthode non autorisée.']); exit;
        }

        $input  = json_decode(file_get_contents('php://input'), true);
        $action = strtoupper(trim($input['action'] ?? 'FACTURE'));
        $mode   = trim($input['mode_paiement'] ?? '');

        // Vérifier que la demande existe
        $stmtD = $this->db->prepare("SELECT id, statut_facturation FROM demandes_laboratoire WHERE id = ? LIMIT 1");
        $stmtD->execute([$demandeId]);
        $demande = $stmtD->fetch(PDO::FETCH_ASSOC);

        if (!$demande) {
            echo json_encode(['success' => false, 'message' => 'Demande introuvable.']); exit;
        }

        try {
            if ($action === 'FACTURE') {
                $this->db->prepare("
                    UPDATE demandes_laboratoire
                    SET statut_facturation = 'FACTURE',
                        date_facturation   = NOW(),
                        facture_par        = ?,
                        mode_paiement_labo = ?
                    WHERE id = ?
                ")->execute([$_SESSION['user_id'], $mode ?: null, $demandeId]);

                echo json_encode(['success' => true, 'statut' => 'FACTURE',
                    'message' => 'Demande marquée comme facturée.']);
            } else {
                // Annuler la facturation
                $this->db->prepare("
                    UPDATE demandes_laboratoire
                    SET statut_facturation = 'NON_FACTURE',
                        date_facturation   = NULL,
                        facture_par        = NULL,
                        mode_paiement_labo = NULL
                    WHERE id = ?
                ")->execute([$demandeId]);

                echo json_encode(['success' => true, 'statut' => 'NON_FACTURE',
                    'message' => 'Facturation annulée.']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Erreur : ' . $e->getMessage()]);
        }
        exit;
    }

    /**
     * GET /laboratoire/examens-demande/{id}
     * Liste des examens d'une demande pour le modal de facturation par examen.
     * Retourne JSON : { success, demande_id, items: [{id, nom, statut, urgent, facture, montant}] }
     */
    public function getExamensDemande(int $demandeId): void {
        header('Content-Type: application/json');
        if (!$this->isSecretaireLabo()) {
            echo json_encode(['success' => false, 'message' => 'Accès refusé.']); exit;
        }

        try {
            // Détecter si la migration a été exécutée
            $hasFactureCol = (int)$this->db->query(
                "SELECT COUNT(*) FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'demande_examens'
                   AND COLUMN_NAME = 'facture'"
            )->fetchColumn() > 0;

            $factureSelect = $hasFactureCol
                ? "de.facture, de.montant_examen, de.date_facturation_examen"
                : "0 AS facture, NULL AS montant_examen, NULL AS date_facturation_examen";

            $stmt = $this->db->prepare("
                SELECT de.id, de.examen_id, de.statut, de.urgent, de.a_jeun,
                       el.nom AS nom_examen, el.categorie, el.prix AS tarif,
                       $factureSelect
                FROM demande_examens de
                LEFT JOIN examens_laboratoire el ON de.examen_id = el.id
                WHERE de.demande_id = ?
                ORDER BY el.categorie, el.nom
            ");
            $stmt->execute([$demandeId]);
            $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Stats globales
            $nbTotal = count($items);
            $nbFacture = count(array_filter($items, fn($i) => (int)($i['facture'] ?? 0) === 1));

            echo json_encode([
                'success' => true,
                'demande_id' => $demandeId,
                'items' => $items,
                'stats' => [
                    'total' => $nbTotal,
                    'facture' => $nbFacture,
                    'non_facture' => $nbTotal - $nbFacture,
                ],
                'migration_ok' => $hasFactureCol,
            ]);
        } catch (\Throwable $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    /**
     * POST /laboratoire/facturer-examens
     * Facture une liste d'examens individuels.
     * Body JSON : { demande_id, examens_ids: [int,...], mode_paiement }
     * Met à jour le statut global de la demande :
     *   - tous facturés      → FACTURE
     *   - partiellement      → PARTIELLE
     *   - aucun              → NON_FACTURE
     */
    public function facturerExamens(): void {
        header('Content-Type: application/json');
        if (!$this->isSecretaireLabo()) {
            echo json_encode(['success' => false, 'message' => 'Accès refusé.']); exit;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Méthode non autorisée.']); exit;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $demandeId = (int)($input['demande_id'] ?? 0);
        $examensIds = array_map('intval', (array)($input['examens_ids'] ?? []));
        $mode = trim($input['mode_paiement'] ?? '');

        if (!$demandeId) {
            echo json_encode(['success' => false, 'message' => 'Demande manquante.']); exit;
        }

        // Vérifier que la migration a été exécutée
        $hasFactureCol = (int)$this->db->query(
            "SELECT COUNT(*) FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'demande_examens'
               AND COLUMN_NAME = 'facture'"
        )->fetchColumn() > 0;
        if (!$hasFactureCol) {
            echo json_encode([
                'success' => false,
                'message' => 'Migration non exécutée. Lancez run_migration_facturation_par_examen.php'
            ]); exit;
        }

        try {
            $this->db->beginTransaction();

            // 1. Réinitialiser tous les examens de la demande à non facturés
            $this->db->prepare("
                UPDATE demande_examens
                SET facture = 0, date_facturation_examen = NULL,
                    facture_par_examen = NULL, montant_examen = NULL
                WHERE demande_id = ?
            ")->execute([$demandeId]);

            // 2. Marquer comme facturés ceux cochés
            if (!empty($examensIds)) {
                $placeholders = implode(',', array_fill(0, count($examensIds), '?'));
                $userId = $_SESSION['user_id'] ?? 0;
                $params = array_merge([$userId], $examensIds, [$demandeId]);

                $this->db->prepare("
                    UPDATE demande_examens de
                    LEFT JOIN examens_laboratoire el ON de.examen_id = el.id
                    SET de.facture = 1,
                        de.date_facturation_examen = NOW(),
                        de.facture_par_examen = ?,
                        de.montant_examen = el.prix
                    WHERE de.id IN ($placeholders) AND de.demande_id = ?
                ")->execute($params);
            }

            // 3. Calculer le statut global
            $stats = $this->db->prepare("
                SELECT COUNT(*) AS total, SUM(CASE WHEN facture = 1 THEN 1 ELSE 0 END) AS facture
                FROM demande_examens WHERE demande_id = ?
            ");
            $stats->execute([$demandeId]);
            $r = $stats->fetch(PDO::FETCH_ASSOC);
            $total = (int)$r['total'];
            $factures = (int)$r['facture'];

            $statutGlobal = ($factures === 0) ? 'NON_FACTURE'
                          : (($factures < $total) ? 'PARTIELLE' : 'FACTURE');

            $this->db->prepare("
                UPDATE demandes_laboratoire
                SET statut_facturation = ?,
                    date_facturation = " . ($factures > 0 ? "NOW()" : "NULL") . ",
                    facture_par = ?,
                    mode_paiement_labo = ?
                WHERE id = ?
            ")->execute([
                $statutGlobal,
                $factures > 0 ? ($_SESSION['user_id'] ?? null) : null,
                $factures > 0 ? ($mode ?: null) : null,
                $demandeId
            ]);

            $this->db->commit();

            echo json_encode([
                'success' => true,
                'statut'  => $statutGlobal,
                'total'   => $total,
                'facture' => $factures,
                'message' => $statutGlobal === 'FACTURE'
                    ? "✅ Tous les examens facturés ($factures/$total)"
                    : ($statutGlobal === 'PARTIELLE'
                        ? "⚠ Facturation partielle : $factures/$total examens facturés"
                        : "Aucun examen facturé"),
            ]);
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            echo json_encode(['success' => false, 'message' => 'Erreur : ' . $e->getMessage()]);
        }
        exit;
    }

    /**
     * Vue : Historique complet des demandes traitées
     */
    public function historique(): void {
        $this->auth->requirePermission('laboratoire', 'read');

        $dateDebut = $_GET['debut']  ?? date('Y-m-d', strtotime('-30 days'));
        $dateFin   = $_GET['fin']    ?? date('Y-m-d');
        $statut    = $_GET['statut'] ?? '';

        $sql = "
            SELECT d.*,
                   p.nom, p.prenom, p.dossier_numero,
                   u.nom AS medecin_nom, u.prenom AS medecin_prenom,
                   COUNT(de.id)                                              AS nb_examens,
                   SUM(CASE WHEN de.statut = 'VALIDE'  THEN 1 ELSE 0 END)  AS nb_valides,
                   SUM(CASE WHEN de.urgent = 1         THEN 1 ELSE 0 END)  AS nb_urgents
            FROM demandes_laboratoire d
            JOIN patients p   ON d.patient_id  = p.id
            LEFT JOIN users u ON d.medecin_id  = u.id
            LEFT JOIN demande_examens de ON d.id = de.demande_id
            WHERE DATE(d.date_creation) BETWEEN ? AND ?
        ";
        $params = [$dateDebut, $dateFin];
        if ($statut) { $sql .= " AND d.statut = ?"; $params[] = $statut; }
        $sql .= " GROUP BY d.id ORDER BY d.date_creation DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $historique = $stmt->fetchAll(PDO::FETCH_ASSOC);

        require_once __DIR__ . '/../views/laboratoire/historique.php';
    }
}
