<?php
require_once __DIR__ . '/UnifiedController.php';

class ImagerieController extends UnifiedController {

 public function index() {
    $this->auth->requirePermission('laboratoire', 'read');

    $database = new Database();
    $db = $database->getConnection();

    // On utilise AS pour renommer les colonnes au vol pour la vue
    $sql = "SELECT i.*,
                   i.type_imagerie AS type_examen,
                   i.partie_code AS partie_corps,
                   p.nom, p.prenom,
                   u.nom as medecin_nom, u.prenom as medecin_prenom
            FROM demandes_imagerie i
            JOIN patients p ON i.patient_id = p.id
            JOIN users u ON i.medecin_id = u.id
            ORDER BY i.date_creation DESC";

    $stmt = $db->prepare($sql);
    $stmt->execute();
    $examens = $stmt->fetchAll(PDO::FETCH_ASSOC);

    require_once __DIR__ . '/../views/imagerie/index.php';
}

    public function viewer($id) {
        $database = new Database();
        $db = $database->getConnection();

        // Ajout des alias AS type_examen et AS partie_corps pour correspondre à la vue viewer.php
        $sql = "SELECT i.*,
               i.type_imagerie AS type_examen,
               i.partie_code AS partie_corps,
               p.nom, p.prenom
        FROM demandes_imagerie i
        JOIN patients p ON i.patient_id = p.id
        WHERE i.id = :id";

        $stmt = $db->prepare($sql);
        $stmt->execute([':id' => $id]);
        $examen = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$examen) {
            header('Location: ' . BASE_URL . 'imagerie');
            return;
        }

        require_once __DIR__ . '/../views/imagerie/viewer.php';
    }

   public function upload() {
        $this->auth->requirePermission('laboratoire', 'write');

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['dicom_file'])) {
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
                $database = new Database();
                $db = $database->getConnection();

                // Mise à jour de la table demandes_imagerie
                // On enregistre le fichier, l'interprétation et la conclusion
                $sql = "UPDATE demandes_imagerie
                        SET fichier_dicom = :filename,
                            taille_fichier = :size,
                            interpretation = :interp,
                            conclusion = :concl,
                            statut = 'termine',
                            date_examen = NOW()
                        WHERE id = :id";

                $stmt = $db->prepare($sql);
                $stmt->execute([
                    ':filename' => $filename,
                    ':size' => $file['size'],
                    ':interp' => $interpretation,
                    ':concl' => $conclusion,
                    ':id' => $imagerie_id
                ]);

                // Si c'est un DICOM, on tente de générer une preview
                // Si c'est une IMAGE (jpg/png), on utilise l'image elle-même comme preview
                if (!$isDicom) {
                    $preview_dir = __DIR__ . '/../../assets/uploads/previews/';
                    if (!is_dir($preview_dir)) mkdir($preview_dir, 0755, true);
                    copy($filepath, $preview_dir . 'preview_' . $imagerie_id . '.jpg');

                    $db->prepare("UPDATE demandes_imagerie SET fichier_preview = ? WHERE id = ?")
                       ->execute(['preview_' . $imagerie_id . '.jpg', $imagerie_id]);
                } else {
                    $this->generatePreview($imagerie_id, $filepath);
                }

                echo json_encode(['success' => true, 'message' => 'Examen enregistré avec succès']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Erreur lors du transfert du fichier']);
            }
            exit;
        }
    }

    public function saveInterpretation() {
        $this->auth->requirePermission('laboratoire', 'write');

        $database = new Database();
        $db = $database->getConnection();

        // CORRECTION : Table 'demandes_imagerie'
        $sql = "UPDATE demandes_imagerie
                SET interpretation = :interpretation, conclusion = :conclusion, statut = 'interprete'
                WHERE id = :id";

        $stmt = $db->prepare($sql);
        $success = $stmt->execute([
            ':interpretation' => $_POST['interpretation'],
            ':conclusion' => $_POST['conclusion'],
            ':id' => $_POST['imagerie_id']
        ]);

        echo json_encode(['success' => $success]);
    }

    private function generatePreview($imagerie_id, $dicom_path) {
    $preview_filename = 'preview_' . $imagerie_id . '.jpg';
    // Chemin ABSOLU pour l'écriture sur le disque
    $preview_dir = $_SERVER['DOCUMENT_ROOT'] . '/dme_hospital/assets/uploads/previews/';
    $preview_path = $preview_dir . $preview_filename;

    if (!is_dir($preview_dir)) {
        mkdir($preview_dir, 0755, true);
    }

    // On cherche une image de remplacement si la démo n'existe pas
    $demo_image = $_SERVER['DOCUMENT_ROOT'] . '/dme_hospital/public/images/dicom-demo.jpg';

    if (file_exists($demo_image)) {
        copy($demo_image, $preview_path);
    } else {
        // SOLUTION DE SECOURS : Créer une image noire de 150x150 si aucune démo n'est trouvée
        $img = imagecreatetruecolor(150, 150);
        $text_color = imagecolorallocate($img, 255, 255, 255);
        imagestring($img, 3, 20, 70, "Apercu DICOM", $text_color);
        imagejpeg($img, $preview_path);
        imagedestroy($img);
    }

    $database = new Database();
    $db = $database->getConnection();
    $sql = "UPDATE demandes_imagerie SET fichier_preview = :preview WHERE id = :id";
    $stmt = $db->prepare($sql);
    $stmt->execute([':preview' => $preview_filename, ':id' => $imagerie_id]);
}
}