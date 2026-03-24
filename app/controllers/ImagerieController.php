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
                       u.nom as medecin_nom, u.prenom as medecin_prenom
                FROM demandes_imagerie i
                JOIN patients p ON i.patient_id = p.id
                JOIN users u ON i.medecin_id = u.id
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
        $this->auth->requirePermission('laboratoire', 'write');
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $imagerie_id = $_POST['imagerie_id'];
                $interpretation = htmlspecialchars($_POST['interpretation'] ?? '');
                $conclusion = htmlspecialchars($_POST['conclusion'] ?? '');
                $file = $_FILES['dicom_file'];

                $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                $upload_dir = __DIR__ . '/../../assets/uploads/dicom/';
                if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

                $filename = 'img_' . $imagerie_id . '_' . time() . '.' . $ext;
                $filepath = $upload_dir . $filename;

                if (move_uploaded_file($file['tmp_name'], $filepath)) {
                    $sql = "UPDATE demandes_imagerie
                            SET fichier_dicom = :filename,
                                interpretation = :interp,
                                conclusion = :concl,
                                statut = 'termine',
                                date_examen = NOW()
                            WHERE id = :id";
                    $this->db->prepare($sql)->execute([
                        ':filename' => $filename,
                        ':interp' => $interpretation,
                        ':concl' => $conclusion,
                        ':id' => $imagerie_id
                    ]);

                    echo json_encode(['success' => true, 'message' => 'Examen enregistré']);
                }
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
            exit;
        }
    }
    /**
     * SERVIR LE FICHIER DICOM (Flux binaire sécurisé pour le viewer)
     */
    public function fetchDicom($id) {
        $this->auth->requirePermission('laboratoire', 'read');
        $stmt = $this->db->prepare("SELECT fichier_dicom FROM demandes_imagerie WHERE id = ?");
        $stmt->execute([$id]);
        $filename = $stmt->fetchColumn();

        $path = __DIR__ . '/../../assets/uploads/dicom/' . $filename;

        if ($filename && file_exists($path)) {
            header('Content-Type: application/dicom');
            header('Content-Length: ' . filesize($path));
            header('Access-Control-Allow-Origin: *');
            readfile($path);
            exit;
        } else {
            http_response_code(404);
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
}