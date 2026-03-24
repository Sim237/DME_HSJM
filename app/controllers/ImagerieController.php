<?php
/* ============================================================================
   FICHIER : app/controllers/ImagerieController.php
   CONTRÔLEUR DU SERVICE D'IMAGERIE MÉDICALE (RADIOLOGIE)
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

        // 1. Calcul des statistiques pour les widgets (Bleu, Rouge, Vert)
        $sqlStats = "SELECT
            (SELECT COUNT(*) FROM demandes_imagerie WHERE statut = 'EN_ATTENTE') as en_attente,
            (SELECT COUNT(*) FROM demandes_imagerie WHERE statut = 'termine') as a_interpreter,
            (SELECT COUNT(*) FROM demandes_imagerie WHERE statut = 'interprete' AND DATE(date_resultats) = CURDATE()) as termines";

        $stats = $this->db->query($sqlStats)->fetch(PDO::FETCH_ASSOC);

        // 2. Récupération de la liste des examens
        // On utilise AS pour renommer les colonnes au vol pour la vue index.php
        $sql = "SELECT i.*,
                       i.type_imagerie AS type_examen,
                       i.partie_code AS partie_corps,
                       p.nom, p.prenom, p.dossier_numero,
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
     * API ENDPOINT - Fournit les données du fichier DICOM au JavaScript du viewer
     */
    public function dicomData($id) {
        header('Content-Type: application/json');

        $stmt = $this->db->prepare("SELECT fichier_dicom FROM demandes_imagerie WHERE id = ?");
        $stmt->execute([$id]);
        $file = $stmt->fetchColumn();

        if ($file) {
            // CornerstoneWADOImageLoader nécessite le préfixe wadouri: pour les URLs HTTP
            $url = BASE_URL . "assets/uploads/dicom/" . $file;
            echo json_encode(['imageIds' => ["wadouri:" . $url]]);
        } else {
            echo json_encode(['imageIds' => [], 'message' => 'Aucun fichier DICOM associé']);
        }
    }

    /**
     * UPLOAD - Enregistre le fichier (DCM ou Image) et met à jour le statut à 'termine'
     */
    public function upload() {
        $this->auth->requirePermission('laboratoire', 'write');

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['dicom_file'])) {
            try {
                $imagerie_id = $_POST['imagerie_id'];
                $interpretation = htmlspecialchars($_POST['interpretation'] ?? '');
                $conclusion = htmlspecialchars($_POST['conclusion'] ?? '');
                $file = $_FILES['dicom_file'];

                // Détection du type de fichier
                $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                $isDicom = in_array($ext, ['dcm', 'dicom']);

                $upload_dir = __DIR__ . '/../../assets/uploads/dicom/';
                if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

                $filename = 'img_' . $imagerie_id . '_' . time() . '.' . $ext;
                $filepath = $upload_dir . $filename;

                if (move_uploaded_file($file['tmp_name'], $filepath)) {

                    // Mise à jour : l'examen passe de 'EN_ATTENTE' à 'termine' (Image reçue)
                    $sql = "UPDATE demandes_imagerie
                            SET fichier_dicom = :filename,
                                taille_fichier = :size,
                                interpretation = :interp,
                                conclusion = :concl,
                                statut = 'termine',
                                date_examen = NOW()
                            WHERE id = :id";

                    $stmt = $this->db->prepare($sql);
                    $stmt->execute([
                        ':filename' => $filename,
                        ':size' => $file['size'],
                        ':interp' => $interpretation,
                        ':concl' => $conclusion,
                        ':id' => $imagerie_id
                    ]);

                    // Génération de la miniature
                    if (!$isDicom) {
                        // Si c'est un JPG/PNG, on copie simplement pour la preview
                        $preview_dir = __DIR__ . '/../../assets/uploads/previews/';
                        if (!is_dir($preview_dir)) mkdir($preview_dir, 0755, true);
                        copy($filepath, $preview_dir . 'preview_' . $imagerie_id . '.jpg');

                        $this->db->prepare("UPDATE demandes_imagerie SET fichier_preview = ? WHERE id = ?")
                           ->execute(['preview_' . $imagerie_id . '.jpg', $imagerie_id]);
                    } else {
                        // Si c'est un DICOM, on génère une image noire de remplacement ou de démo
                        $this->generatePreview($imagerie_id, $filepath);
                    }

                    echo json_encode(['success' => true, 'message' => 'Examen enregistré avec succès']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Erreur lors du transfert du fichier']);
                }
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
            exit;
        }
    }

    /**
     * HELPER - Génère une miniature visuelle pour les listes
     */
    private function generatePreview($imagerie_id, $dicom_path) {
        $preview_filename = 'preview_' . $imagerie_id . '.jpg';
        $preview_dir = $_SERVER['DOCUMENT_ROOT'] . '/dme_hospital/assets/uploads/previews/';
        $preview_path = $preview_dir . $preview_filename;

        if (!is_dir($preview_dir)) mkdir($preview_dir, 0755, true);

        // On cherche une image de remplacement démo
        $demo_image = $_SERVER['DOCUMENT_ROOT'] . '/dme_hospital/public/images/dicom-demo.jpg';

        if (file_exists($demo_image)) {
            copy($demo_image, $preview_path);
        } else {
            // Créer une image noire de 150x150 avec texte
            $img = imagecreatetruecolor(150, 150);
            $text_color = imagecolorallocate($img, 255, 255, 255);
            imagestring($img, 3, 20, 70, "IMAGE DICOM", $text_color);
            imagejpeg($img, $preview_path);
            imagedestroy($img);
        }

        $sql = "UPDATE demandes_imagerie SET fichier_preview = :preview WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':preview' => $preview_filename, ':id' => $imagerie_id]);
    }

/**
     * SERVIR LE FICHIER DICOM (Crucial pour Cornerstone)
     * Cette méthode transforme le fichier disque en flux binaire HTTP
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
            header('Access-Control-Allow-Origin: *'); // Autorise le JS à lire le binaire
            readfile($path);
            exit;
        } else {
            http_response_code(404);
        }
    }

    public function viewer($id) {
        $this->auth->requirePermission('laboratoire', 'read');
        $stmt = $this->db->prepare("SELECT i.*, p.nom, p.prenom, p.date_naissance FROM demandes_imagerie i JOIN patients p ON i.patient_id = p.id WHERE i.id = ?");
        $stmt->execute([$id]);
        $examen = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$examen) header('Location: ' . BASE_URL . 'imagerie');
        require_once __DIR__ . '/../views/imagerie/viewer.php';
    }

    /**
     * SAUVEGARDE LA MINIATURE (Générée par le canvas du viewer)
     */
    public function saveThumbnail() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = $_POST['imagerie_id'];
            $data = $_POST['image_data']; // Base64 du canvas

            $img = str_replace('data:image/jpeg;base64,', '', $data);
            $img = str_replace(' ', '+', $img);
            $binary = base64_decode($img);

            $filename = 'preview_' . $id . '.jpg';
            $path = __DIR__ . '/../../assets/uploads/previews/' . $filename;

            if (file_put_contents($path, $binary)) {
                $this->db->prepare("UPDATE demandes_imagerie SET fichier_preview = ? WHERE id = ?")->execute([$filename, $id]);
                echo json_encode(['success' => true]);
            }
        }
    }

    public function saveInterpretation() {
        $this->auth->requirePermission('laboratoire', 'write');
        $sql = "UPDATE demandes_imagerie SET interpretation = ?, conclusion = ?, statut = 'interprete', date_resultats = NOW() WHERE id = ?";
        $success = $this->db->prepare($sql)->execute([$_POST['interpretation'], $_POST['conclusion'], $_POST['imagerie_id']]);
        echo json_encode(['success' => $success]);
    }
}