<?php
/* ============================================================================
   FICHIER : app/controllers/ImagerieController.php
   CONTRÔLEUR DU SERVICE D'IMAGERIE MÉDICALE (RADIOLOGIE) - VERSION FINALE
   ============================================================================ */

require_once __DIR__ . '/UnifiedController.php';

class ImagerieController extends UnifiedController {

    private $db;

    public function __construct() {
        parent::__construct();
        $this->db = (new Database())->getConnection();
    }

    /**
     * DASHBOARD PRINCIPAL - Liste des examens et Statistiques
     */
   public function index() {
        $this->auth->requirePermission('laboratoire', 'read');

        // 1. Statistiques KPI
        $sqlStats = "SELECT
            (SELECT COUNT(*) FROM demandes_imagerie WHERE statut = 'EN_ATTENTE') as en_attente,
            (SELECT COUNT(*) FROM demandes_imagerie WHERE statut = 'termine') as a_interpreter,
            (SELECT COUNT(*) FROM demandes_imagerie WHERE statut = 'interprete' AND DATE(date_resultats) = CURDATE()) as termines";
        $stats = $this->db->query($sqlStats)->fetch(PDO::FETCH_ASSOC);

        // 2. Liste des examens (Utilisation des noms de colonnes réels de la table)
        $sql = "SELECT i.*, p.nom, p.prenom, p.dossier_numero,
               COALESCE(u.nom, 'Médecin inconnu') as medecin_nom
        FROM demandes_imagerie i
        JOIN patients p ON i.patient_id = p.id
        LEFT JOIN users u ON i.medecin_id = u.id -- On utilise LEFT JOIN ici
        WHERE i.statut != 'interprete' OR DATE(i.date_resultats) = CURDATE()
        ORDER BY (i.urgence = 'URGENT') DESC, i.date_creation DESC";


        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $examens = $stmt->fetchAll(PDO::FETCH_ASSOC);

        require_once __DIR__ . '/../views/imagerie/index.php';
    }

    /**
     * UPLOAD - Traitement du fichier et de l'interprétation initiale
     */
   public function upload() {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
            exit;
        }

        try {
            $id = $_POST['imagerie_id'];
            $file = $_FILES['dicom_file'] ?? null;

            if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
                throw new Exception("Erreur de transfert : " . ($file ? $file['error'] : 'Fichier manquant'));
            }

            // 1. Définition des dossiers
            $upload_dir = __DIR__ . '/../../assets/uploads/dicom/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

            // 2. Nettoyage du nom de fichier
            $ext = pathinfo($file['name'], PATHINFO_EXTENSION) ?: 'dcm';
            $new_name = 'IMG_' . $id . '_' . time() . '.' . $ext;
            $destination = $upload_dir . $new_name;

            // 3. Déplacement du fichier
            if (move_uploaded_file($file['tmp_name'], $destination)) {

                // 4. Mise à jour de la base de données UNIQUEMENT si le fichier est sur le disque
                $sql = "UPDATE demandes_imagerie SET
                        fichier_dicom = ?,
                        statut = 'termine',
                        interpretation = ?,
                        conclusion = ?,
                        date_examen = NOW()
                        WHERE id = ?";

                $stmt = $this->db->prepare($sql);
                $stmt->execute([
                    $new_name,
                    $_POST['interpretation'] ?? '',
                    $_POST['conclusion'] ?? '',
                    $id
                ]);

                echo json_encode(['success' => true, 'message' => 'Fichier uploadé avec succès']);
            } else {
                throw new Exception("Impossible d'écrire le fichier sur le disque. Vérifiez les permissions du dossier assets/uploads/dicom/");
            }

        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }
    /**
     * SERVIR LE FICHIER DICOM (Flux binaire sécurisé pour le viewer)
     */
    public function fetchDicom($id) {
    // On vide tout tampon pour éviter d'envoyer du texte parasite
    if (ob_get_length()) ob_clean();

    $stmt = $this->db->prepare("SELECT fichier_dicom FROM demandes_imagerie WHERE id = ?");
    $stmt->execute([$id]);
    $filename = $stmt->fetchColumn();

    // Chemin correspondant à votre capture d'écran (assets/uploads/dicom/)
    $path = $_SERVER['DOCUMENT_ROOT'] . '/dme_hospital/assets/uploads/dicom/' . $filename;

    if ($filename && file_exists($path)) {
        header('Content-Type: application/dicom');
        header('Content-Length: ' . filesize($path));
        header('Access-Control-Allow-Origin: *');
        header('Cache-Control: no-cache');
        readfile($path);
        exit;
    } else {
        http_response_code(404);
        echo "Fichier introuvable. Chemin tenté : " . $path;
        exit;
    }
}
    /**
     * VISUALISEUR - Charge la page du viewer CornerstoneJS
     */
    public function viewer($id) {
        $this->auth->requirePermission('laboratoire', 'read');
        $stmt = $this->db->prepare("SELECT i.*, p.nom, p.prenom, p.date_naissance FROM demandes_imagerie i JOIN patients p ON i.patient_id = p.id WHERE i.id = ?");
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
        $this->auth->requirePermission('laboratoire', 'write');
        header('Content-Type: application/json');

        $sql = "UPDATE demandes_imagerie SET interpretation = ?, conclusion = ?, statut = 'interprete', date_resultats = NOW() WHERE id = ?";
        $success = $this->db->prepare($sql)->execute([$_POST['interpretation'], $_POST['conclusion'], $_POST['imagerie_id']]);

        echo json_encode(['success' => $success]);
        exit;
    }

    /**
     * HELPER - Génère la miniature (Thumbnail)
     */
    private function generatePreview($imagerie_id, $file_path, $is_standard_image = false) {
        $preview_filename = 'preview_' . $imagerie_id . '.jpg';
        $preview_dir = __DIR__ . '/../../assets/uploads/previews/';
        if (!is_dir($preview_dir)) mkdir($preview_dir, 0777, true);
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
}