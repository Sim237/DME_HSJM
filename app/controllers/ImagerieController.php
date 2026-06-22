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
   FICHIER : app/controllers/ImagerieController.php
   CONTRÔLEUR DU SERVICE D'IMAGERIE MÉDICALE (RADIOLOGIE) - VERSION FINALE
   ============================================================================ */

require_once __DIR__ . '/UnifiedController.php';
require_once __DIR__ . '/../services/AuditService.php';

class ImagerieController extends UnifiedController {

    private $db;
    private $audit;

    public function __construct() {
        parent::__construct();
        $this->db = (new Database())->getConnection();
        $this->audit = new AuditService();
    }

    /**
     * DASHBOARD PRINCIPAL - Liste des examens et Statistiques
     */
   public function index() {
        $this->auth->requirePermission('laboratoire', 'read');

        // 1. Statistiques KPI
        try {
            $sqlStats = "SELECT
                (SELECT COUNT(*) FROM demandes_imagerie WHERE statut = 'EN_ATTENTE') as en_attente,
                (SELECT COUNT(*) FROM demandes_imagerie WHERE statut = 'EN_ATTENTE' AND urgence = 'URGENT') as urgents,
                (SELECT COUNT(*) FROM demandes_imagerie WHERE statut IN ('termine','interprete') AND DATE(COALESCE(date_resultats, date_creation)) = CURDATE()) as termines_jour";
            $stats = $this->db->query($sqlStats)->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $stats = ['en_attente' => 0, 'urgents' => 0, 'termines_jour' => 0];
        }

        // 2. Examens EN_ATTENTE — base commune
        $sqlAttente = "SELECT i.*,
               COALESCE(i.type_examen,   i.type_imagerie,   'autre')               AS type_examen,
               COALESCE(i.partie_corps,  i.partie_code,     i.description,
                        'Zone non précisée')                                         AS partie_corps,
               p.nom, p.prenom, p.dossier_numero, p.id AS patient_id,
               COALESCE(u.nom, 'Médecin inconnu') AS medecin_nom
        FROM demandes_imagerie i
        JOIN patients p ON i.patient_id = p.id
        LEFT JOIN users u ON i.medecin_id = u.id
        WHERE i.statut = 'EN_ATTENTE'
        ORDER BY (i.urgence = 'URGENT' OR i.urgence = 1) DESC, i.date_creation ASC";

        $stmt = $this->db->prepare($sqlAttente);
        $stmt->execute();
        $examens_attente = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // 3. Grouper par patient_id selon la modalité
        $radio_groupes  = [];
        $echo_groupes   = [];
        $autres_attente = [];

        foreach ($examens_attente as $ex) {
            $type = strtolower(trim($ex['type_examen']));
            $pid  = (int)$ex['patient_id'];

            if (str_contains($type, 'radio') || $type === 'rx' || str_contains($type, 'radiograph')) {
                if (!isset($radio_groupes[$pid])) {
                    $radio_groupes[$pid] = ['patient' => $ex, 'examens' => []];
                }
                $radio_groupes[$pid]['examens'][] = $ex;
            } elseif (str_contains($type, 'echo') || str_contains($type, 'échograph') || str_contains($type, 'echograph')) {
                if (!isset($echo_groupes[$pid])) {
                    $echo_groupes[$pid] = ['patient' => $ex, 'examens' => []];
                }
                $echo_groupes[$pid]['examens'][] = $ex;
            } else {
                if (!isset($autres_attente[$pid])) {
                    $autres_attente[$pid] = ['patient' => $ex, 'examens' => []];
                }
                $autres_attente[$pid]['examens'][] = $ex;
            }
        }

        // 4. Examens Terminés (statut = 'termine' ou 'interprete')
        $sqlTerminees = "SELECT i.*,
               COALESCE(i.type_examen,   i.type_imagerie,   'autre')               AS type_examen,
               COALESCE(i.partie_corps,  i.partie_code,     i.description,
                        'Zone non précisée')                                         AS partie_corps,
               p.nom, p.prenom, p.dossier_numero,
               COALESCE(u.nom, 'Médecin inconnu') AS medecin_nom
        FROM demandes_imagerie i
        JOIN patients p ON i.patient_id = p.id
        LEFT JOIN users u ON i.medecin_id = u.id
        WHERE i.statut IN ('termine', 'interprete')
        ORDER BY COALESCE(i.date_resultats, i.date_creation) DESC
        LIMIT 100";

        $stmt2 = $this->db->prepare($sqlTerminees);
        $stmt2->execute();
        $examens_termines = $stmt2->fetchAll(PDO::FETCH_ASSOC);

        // Compat rétro (modal_upload, etc.)
        $examens = $examens_attente;

        require_once __DIR__ . '/../views/imagerie/index.php';
    }

    /**
     * UPLOAD - Traitement du fichier et de l'interprétation initiale
     */
    /**
     * UPLOAD — Supporte 1 ou N fichiers DICOM/image
     * Modes : 'new' (premier dépôt), 'add' (ajout aux existants), 'replace' (tout remplacer)
     */
    public function upload() {
        ob_start();
        $json = ['success' => false, 'message' => 'Erreur inconnue'];

        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') throw new Exception('Méthode non autorisée');

            // Détecter dépassement post_max_size
            if (empty($_POST) && empty($_FILES) && (int)($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
                $mb = round((int)$_SERVER['CONTENT_LENGTH'] / 1048576, 1);
                throw new Exception("Fichier(s) trop volumineux ({$mb} Mo). Limite post_max_size="
                    . ini_get('post_max_size') . ", upload_max_filesize=" . ini_get('upload_max_filesize'));
            }

            $id   = (int)($_POST['imagerie_id'] ?? 0);
            $mode = $_POST['mode'] ?? 'new'; // new | add | replace

            if (!$id) throw new Exception("Identifiant de l'examen manquant.");

            // ── Normaliser $_FILES en tableau uniforme [{name, tmp_name, error, size}, …] ──
            $raw   = $_FILES['dicom_file'] ?? null;
            $files = [];
            if ($raw) {
                if (is_array($raw['name'])) {
                    // input name="dicom_file[]" multiple
                    foreach ($raw['name'] as $i => $name) {
                        $files[] = [
                            'name'     => $name,
                            'tmp_name' => $raw['tmp_name'][$i],
                            'error'    => $raw['error'][$i],
                            'size'     => $raw['size'][$i],
                        ];
                    }
                } else {
                    // input name="dicom_file" (legacy)
                    $files[] = $raw;
                }
            }
            $files = array_filter($files, fn($f) => $f['error'] === UPLOAD_ERR_OK);

            if (empty($files)) {
                // Aucun fichier reçu — peut-être juste une mise à jour de l'interprétation
                if ($mode === 'add') throw new Exception("Aucun fichier sélectionné.");
                // En mode new/replace sans fichier c'est une erreur
                if ($mode !== 'new' && $mode !== 'replace') throw new Exception("Aucun fichier sélectionné.");
                // new/replace sans fichier = on accepte (mise à jour interprétation seule)
            }

            $uploadDir = __DIR__ . '/../../assets/uploads/dicom/';
            if (!is_dir($uploadDir) && !mkdir($uploadDir, 0750, true)) {
                throw new Exception("Impossible de créer le dossier d'upload.");
            }

            // ── En mode replace : supprimer tous les anciens fichiers ──────────
            if ($mode === 'replace') {
                // Supprimer depuis imagerie_fichiers
                $stmtOld = $this->db->prepare(
                    "SELECT fichier FROM imagerie_fichiers WHERE demande_id = ?"
                );
                $stmtOld->execute([$id]);
                foreach ($stmtOld->fetchAll(PDO::FETCH_COLUMN) as $f) {
                    @unlink($uploadDir . $f);
                }
                $this->db->prepare("DELETE FROM imagerie_fichiers WHERE demande_id = ?")->execute([$id]);

                // Supprimer aussi l'ancien fichier_dicom + preview
                $stmtMain = $this->db->prepare(
                    "SELECT fichier_dicom, fichier_preview FROM demandes_imagerie WHERE id = ?"
                );
                $stmtMain->execute([$id]);
                $main = $stmtMain->fetch(PDO::FETCH_ASSOC);
                if ($main) {
                    @unlink($uploadDir . ($main['fichier_dicom'] ?? ''));
                    @unlink(__DIR__ . '/../../assets/uploads/previews/' . ($main['fichier_preview'] ?? ''));
                }
                $this->db->prepare(
                    "UPDATE demandes_imagerie SET fichier_dicom=NULL, fichier_preview=NULL WHERE id=?"
                )->execute([$id]);
            }

            // ── Déterminer le prochain numéro d'ordre ────────────────────────
            $stmtOrdre = $this->db->prepare(
                "SELECT COALESCE(MAX(ordre),0) FROM imagerie_fichiers WHERE demande_id = ?"
            );
            $stmtOrdre->execute([$id]);
            $nextOrdre = (int)$stmtOrdre->fetchColumn() + 1;

            // ── Sauvegarder chaque fichier ───────────────────────────────────
            $firstFileName = null;
            $stmtInsert    = $this->db->prepare(
                "INSERT INTO imagerie_fichiers (demande_id, fichier, nom_original, ordre)
                 VALUES (?, ?, ?, ?)"
            );

            foreach ($files as $f) {
                $ext      = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION) ?: 'dcm');
                $newName  = 'IMG_' . $id . '_' . time() . '_' . $nextOrdre . '.' . $ext;
                $dest     = $uploadDir . $newName;

                if (!move_uploaded_file($f['tmp_name'], $dest)) {
                    throw new Exception("Impossible d'écrire « {$f['name']} ». Vérifiez les permissions.");
                }

                $stmtInsert->execute([$id, $newName, basename($f['name']), $nextOrdre]);

                if ($firstFileName === null) $firstFileName = $newName;
                $nextOrdre++;
                usleep(1000); // évite collision timestamp dans le nom
            }

            // ── Mise à jour de la demande principale ──────────────────────────
            $interp         = trim($_POST['interpretation'] ?? '');
            $concl          = trim($_POST['conclusion']     ?? '');
            $setFile        = ($firstFileName && ($mode === 'new' || $mode === 'replace'));
            $marquerTermine = ((int)($_POST['marquer_termine'] ?? 1)) === 1;
            $nouveauStatut  = $marquerTermine ? 'termine' : 'EN_ATTENTE';

            if ($setFile) {
                $this->db->prepare("
                    UPDATE demandes_imagerie
                    SET fichier_dicom   = ?,
                        fichier_preview = NULL,
                        statut          = ?,
                        interpretation  = ?,
                        conclusion      = ?,
                        date_resultats  = " . ($marquerTermine ? 'NOW()' : 'date_resultats') . "
                    WHERE id = ?
                ")->execute([$firstFileName, $nouveauStatut, $interp, $concl, $id]);
            } else {
                // mode add : juste statut + interprétation si fournie
                $this->db->prepare("
                    UPDATE demandes_imagerie
                    SET statut = ?,
                        interpretation = CASE WHEN ? <> '' THEN ? ELSE interpretation END,
                        conclusion     = CASE WHEN ? <> '' THEN ? ELSE conclusion     END,
                        date_resultats = " . ($marquerTermine ? 'COALESCE(date_resultats, NOW())' : 'date_resultats') . "
                    WHERE id = ?
                ")->execute([$nouveauStatut, $interp, $interp, $concl, $concl, $id]);
            }

            // Mettre à jour les autres examens du groupe — uniquement si terminé
            $rawGroupIds = trim($_POST['imagerie_ids_groupe'] ?? '');
            if ($marquerTermine && $rawGroupIds !== '') {
                $groupIds = array_filter(array_map('intval', explode(',', $rawGroupIds)));
                foreach ($groupIds as $gid) {
                    if ($gid !== $id) {
                        $this->db->prepare(
                            "UPDATE demandes_imagerie
                             SET statut = 'termine',
                                 date_resultats = COALESCE(date_resultats, NOW())
                             WHERE id = ?"
                        )->execute([$gid]);
                    }
                }
            }

            $nbFiles = count($files);
            $json = [
                'success' => true,
                'message' => $nbFiles
                    ? "$nbFiles fichier(s) enregistré(s) avec succès."
                    : 'Interprétation mise à jour.',
                'nb_files' => $nbFiles,
            ];

        } catch (Exception $e) {
            $json = ['success' => false, 'message' => $e->getMessage()];
        }

        ob_end_clean();
        header('Content-Type: application/json');
        echo json_encode($json);
        exit;
    }

    /**
     * LISTE DES FICHIERS — Retourne le JSON des fichiers d'un examen
     */
    public function getFileList($examId) {
        ob_start();
        try {
            $examId = (int)$examId;

            // Fichiers dans imagerie_fichiers
            $stmt = $this->db->prepare("
                SELECT id, fichier, nom_original, ordre, date_upload
                FROM imagerie_fichiers
                WHERE demande_id = ?
                ORDER BY ordre ASC, id ASC
            ");
            $stmt->execute([$examId]);
            $files = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Fallback rétro-compat : si la table est vide, utiliser fichier_dicom
            if (empty($files)) {
                $s = $this->db->prepare(
                    "SELECT fichier_dicom FROM demandes_imagerie WHERE id = ?"
                );
                $s->execute([$examId]);
                $main = $s->fetchColumn();
                if ($main) {
                    $files = [[
                        'id'           => 0,
                        'fichier'      => $main,
                        'nom_original' => $main,
                        'ordre'        => 1,
                        'date_upload'  => null,
                    ]];
                }
            }

            ob_end_clean();
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'files' => $files, 'count' => count($files)]);
        } catch (Exception $e) {
            ob_end_clean();
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => $e->getMessage(), 'files' => []]);
        }
        exit;
    }

    /**
     * SERVIR UN FICHIER par son ID dans imagerie_fichiers
     */
    public function fetchDicomFile($fileId) {
        ob_start();
        $fileId   = (int)$fileId;
        $filename = null;

        if ($fileId > 0) {
            $stmt = $this->db->prepare(
                "SELECT fichier FROM imagerie_fichiers WHERE id = ?"
            );
            $stmt->execute([$fileId]);
            $filename = $stmt->fetchColumn();
        }

        $uploadDir = __DIR__ . '/../../assets/uploads/dicom/';
        $path      = $uploadDir . $filename;
        $realPath  = $filename ? realpath($path) : false;

        if ($realPath && file_exists($realPath) && filesize($realPath) > 0) {
            ob_end_clean();
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: inline; filename="' . basename($filename) . '"');
            header('Content-Length: ' . filesize($realPath));
            header('Cache-Control: no-cache, no-store, must-revalidate');
            header('Pragma: no-cache');
            header('Access-Control-Allow-Origin: *');
            readfile($realPath);
        } else {
            ob_end_clean();
            http_response_code(404);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Fichier introuvable', 'file_id' => $fileId]);
        }
        exit;
    }
    /**
     * SERVIR LE FICHIER DICOM (Flux binaire sécurisé pour le viewer)
     */
    public function fetchDicom($id) {
        // ob_start() capture tout output parasite (warnings PHP)
        // qui corromprait le flux binaire DICOM
        ob_start();

        $stmt = $this->db->prepare("SELECT fichier_dicom FROM demandes_imagerie WHERE id = ?");
        $stmt->execute([$id]);
        $filename = $stmt->fetchColumn();

        // Chemin absolu via __DIR__ — indépendant de DOCUMENT_ROOT
        $path = __DIR__ . '/../../assets/uploads/dicom/' . $filename;
        $realPath = realpath($path);

        if ($filename && $realPath && file_exists($realPath) && filesize($realPath) > 0) {
            ob_end_clean(); // Vider TOUT avant d'envoyer le binaire
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: inline; filename="' . basename($filename) . '"');
            header('Content-Length: ' . filesize($realPath));
            header('Access-Control-Allow-Origin: *');
            header('Cache-Control: no-cache, no-store, must-revalidate');
            header('Pragma: no-cache');
            header('X-Content-Type-Options: nosniff');
            readfile($realPath);
        } else {
            ob_end_clean();
            http_response_code(404);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Fichier introuvable ou vide.']);
        }
        exit;
    }
    /**
     * VISUALISEUR - Charge la page du viewer CornerstoneJS
     */
    public function viewer($id) {
        $this->auth->requirePermission('laboratoire', 'read');
        $stmt = $this->db->prepare("SELECT i.*, p.nom, p.prenom, p.date_naissance, p.sexe, p.dossier_numero FROM demandes_imagerie i JOIN patients p ON i.patient_id = p.id WHERE i.id = ?");
        $stmt->execute([$id]);
        $examen = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$examen) {
            header('Location: ' . BASE_URL . 'imagerie');
            exit;
        }
        require_once __DIR__ . '/../views/imagerie/viewer.php';
    }

    /**
     * INTERPRÉTATION - Enregistre le rapport final du Radiologue
     */
    public function saveInterpretation() {
        // Médecins, radiologues, laborantins et admins peuvent interpréter
        if (!$this->auth->isLoggedIn()) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Non connecté.']);
            exit;
        }
        header('Content-Type: application/json');

        $id             = (int)($_POST['imagerie_id'] ?? 0);
        $interpretation = trim($_POST['interpretation'] ?? '');
        $conclusion     = trim($_POST['conclusion'] ?? '');

        if (!$id || $interpretation === '') {
            echo json_encode(['success' => false, 'message' => 'Données manquantes.']);
            exit;
        }

        $sql = "UPDATE demandes_imagerie
                SET interpretation = ?, conclusion = ?,
                    statut = 'interprete', date_resultats = NOW(),
                    radiologue_id = COALESCE(radiologue_id, ?)
                WHERE id = ?";
        $success = $this->db->prepare($sql)->execute([
            $interpretation, $conclusion,
            $_SESSION['user_id'] ?? null, $id
        ]);

        // Notifier le médecin prescripteur que l'imagerie est interprétée
        if ($success) {
            try {
                $stmtInfo = $this->db->prepare(
                    "SELECT di.patient_id, di.medecin_id,
                            p.nom, p.prenom, p.dossier_numero
                     FROM demandes_imagerie di
                     JOIN patients p ON p.id = di.patient_id
                     WHERE di.id = ?"
                );
                $stmtInfo->execute([$id]);
                $info = $stmtInfo->fetch(PDO::FETCH_ASSOC);

                if ($info && $info['medecin_id']) {
                    $titre   = "Imagerie interprétée — {$info['nom']} {$info['prenom']}";
                    $message = "Le compte-rendu d'imagerie pour le dossier {$info['dossier_numero']} est disponible.";
                    $this->db->prepare(
                        "INSERT INTO notifications_medecin
                            (medecin_id, patient_id, type, titre, message, created_at)
                         VALUES (?, ?, 'RESULTATS_IMAGERIE', ?, ?, NOW())"
                    )->execute([$info['medecin_id'], $info['patient_id'], $titre, $message]);
                }
            } catch (Exception $e) {
                error_log('[ImagerieController::saveInterpretation] Notif échouée : ' . $e->getMessage());
            }

            // ── Audit : compte-rendu d'imagerie rédigé ──
            try {
                $pNom = isset($info) && $info ? trim(($info['nom'] ?? '') . ' ' . ($info['prenom'] ?? '')) : '';
                $desc = 'Compte-rendu d\'imagerie rédigé'
                      . ($pNom ? " — patient : $pNom" : '')
                      . ($conclusion ? ' · Conclusion : ' . mb_substr($conclusion, 0, 120) : '');
                $this->audit->log('UPDATE', 'demandes_imagerie', $id, $desc, null, [
                    'patient'        => $pNom,
                    'interpretation' => mb_substr($interpretation, 0, 200),
                    'conclusion'     => mb_substr($conclusion, 0, 200),
                ]);
            } catch (Exception $e) { error_log('[Imagerie::saveInterpretation] Audit: ' . $e->getMessage()); }
        }

        echo json_encode(['success' => (bool)$success]);
        exit;
    }

    /**
     * HELPER - Génère la miniature (Thumbnail)
     */
    private function generatePreview($imagerie_id, $file_path, $is_standard_image = false) {
        $preview_filename = 'preview_' . $imagerie_id . '.jpg';
        $preview_dir = __DIR__ . '/../../assets/uploads/previews/';
        if (!is_dir($preview_dir)) mkdir($preview_dir, 0750, true);
        $preview_path = $preview_dir . $preview_filename;

        if ($is_standard_image) {
            copy($file_path, $preview_path);
        } else {
            // Image par défaut si DICOM (le viewer en générera une plus précise à l'ouverture)
            $demo_image = __DIR__ . '/../../public/images/dicom-demo.jpg';
            if (file_exists($demo_image)) {
                copy($demo_image, $preview_path);
            } elseif (function_exists('imagecreatetruecolor')) {
                $img = imagecreatetruecolor(150, 150);
                imagejpeg($img, $preview_path);
                imagedestroy($img);
            }
        }

        if (file_exists($preview_path)) {
            $this->db->prepare("UPDATE demandes_imagerie SET fichier_preview = ? WHERE id = ?")
                     ->execute([$preview_filename, $imagerie_id]);
        }
    }

    public function delete($id) {
    $this->auth->requirePermission('laboratoire', 'write');

    // On vide tout tampon de sortie pour éviter les Warnings PHP dans le JSON
    if (ob_get_length()) ob_clean();
    header('Content-Type: application/json');

    try {
        $stmt = $this->db->prepare("SELECT fichier_dicom, fichier_preview FROM demandes_imagerie WHERE id = ?");
        $stmt->execute([$id]);
        $examen = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$examen) {
            echo json_encode(['success' => false, 'message' => "Examen introuvable"]);
            exit;
        }

        // Chemins des fichiers
        $dirDicom = __DIR__ . '/../../assets/uploads/dicom/';
        $dirPreview = __DIR__ . '/../../assets/uploads/previews/';

        // Suppression physique sécurisée (on vérifie si le champ n'est pas vide ET si le fichier existe)
        if (!empty($examen['fichier_dicom']) && file_exists($dirDicom . $examen['fichier_dicom'])) {
            unlink($dirDicom . $examen['fichier_dicom']);
        }

        if (!empty($examen['fichier_preview']) && file_exists($dirPreview . $examen['fichier_preview'])) {
            unlink($dirPreview . $examen['fichier_preview']);
        }

        // Suppression en base de données
        $delete = $this->db->prepare("DELETE FROM demandes_imagerie WHERE id = ?");
        $success = $delete->execute([$id]);

        echo json_encode(['success' => $success]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

/**
 * Créer des demandes d'imagerie depuis la consultation (étape 4)
 * Route : POST imagerie/creer-demande-consultation
 */
public function creerDemandeConsultation() {
    header('Content-Type: application/json');

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
        exit;
    }

    try {
        $data = json_decode(file_get_contents('php://input'), true);

        if (empty($data['patient_id']) || empty($data['demandes'])) {
            echo json_encode(['success' => false, 'message' => 'Données incomplètes']);
            exit;
        }

        $patientId     = (int) $data['patient_id'];
        $consultId     = !empty($data['consultation_id']) ? (int)$data['consultation_id'] : null;
        $medecinId     = !empty($data['medecin_id'])     ? (int)$data['medecin_id']     : ($_SESSION['user_id'] ?? null);
        $demandes      = $data['demandes'];
        $count         = 0;

        // Utilise imagerie_medicale (table existante) ou demandes_imagerie selon ce qui existe
        // On tente d'abord demandes_imagerie, sinon imagerie_medicale
        $tableCheck = $this->db->query("SHOW TABLES LIKE 'demandes_imagerie'")->rowCount();
        $table      = $tableCheck > 0 ? 'demandes_imagerie' : 'imagerie_medicale';

        // Colonnes adaptées à chaque table
        if ($table === 'demandes_imagerie') {
            $stmt = $this->db->prepare("
                INSERT INTO demandes_imagerie
                    (patient_id, consultation_id, medecin_id, type_examen, partie_corps,
                     description, urgence, statut, avec_contraste, date_creation)
                VALUES (?, ?, ?, ?, ?, ?, ?, 'EN_ATTENTE', ?, NOW())
            ");
        } else {
            $stmt = $this->db->prepare("
                INSERT INTO imagerie_medicale
                    (patient_id, consultation_id, medecin_prescripteur, type_examen, partie_corps,
                     description, urgence, statut, date_examen)
                VALUES (?, ?, ?, ?, ?, ?, ?, 'programme', NOW())
            ");
        }

        foreach ($demandes as $d) {
            $typeExamen  = $d['modalite']     ?? 'autre';
            $partieCorps = $d['partie_corps'] ?? '';
            $urgence     = ($d['urgence'] === 'URGENT' || $d['urgence'] === 'TRES_URGENT') ? 1 : 0;
            $description = trim(($d['indication'] ?? '') . ($d['instructions'] ? ' | ' . $d['instructions'] : ''));
            $contraste   = !empty($d['avec_contraste']) ? 1 : 0;

            if ($table === 'demandes_imagerie') {
                $stmt->execute([$patientId, $consultId, $medecinId, $typeExamen, $partieCorps, $description, $urgence ? 'URGENT' : 'NORMAL', $contraste]);
            } else {
                $stmt->execute([$patientId, $consultId, $medecinId, $typeExamen, $partieCorps, $description, $urgence]);
            }
            $count++;
        }

        // ── Audit : demande(s) d'imagerie créée(s) ──
        try {
            $pRow = $this->db->prepare("SELECT nom, prenom FROM patients WHERE id = ?");
            $pRow->execute([$patientId]);
            $pat  = $pRow->fetch(PDO::FETCH_ASSOC);
            $pNom = $pat ? trim(($pat['nom'] ?? '') . ' ' . ($pat['prenom'] ?? '')) : '';
            $types = array_map(fn($d) => ($d['modalite'] ?? 'autre') . ' ' . ($d['partie_corps'] ?? ''), $demandes);
            $desc = "$count examen(s) d'imagerie demandé(s)"
                  . ($pNom ? " pour $pNom (#$patientId)" : '')
                  . ' : ' . mb_substr(implode(', ', $types), 0, 180);
            $this->audit->log('CREATE', 'demandes_imagerie', $patientId, $desc, null, [
                'patient'     => $pNom,
                'nb_examens'  => $count,
                'examens'     => $types,
                'consultation'=> $consultId,
            ]);
        } catch (Exception $e) { error_log('[Imagerie::creerDemande] Audit: ' . $e->getMessage()); }

        echo json_encode(['success' => true, 'count' => $count, 'message' => "$count demande(s) créée(s) avec succès"]);

    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}
}
