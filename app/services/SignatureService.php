<?php

class SignatureService {
    private $db;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }
    
    public function resizeSignature($base64Image, $width = 400, $height = 150) {
        // Extraire les données de l'image
        $imageData = explode(',', $base64Image);
        $imageContent = base64_decode(end($imageData));
        
        // Créer une image depuis la chaîne
        $sourceImage = imagecreatefromstring($imageContent);
        if (!$sourceImage) return $base64Image;
        
        // Créer une nouvelle image redimensionnée
        $resizedImage = imagecreatetruecolor($width, $height);
        
        // Fond blanc
        $white = imagecolorallocate($resizedImage, 255, 255, 255);
        imagefill($resizedImage, 0, 0, $white);
        
        // Redimensionner
        imagecopyresampled(
            $resizedImage, $sourceImage,
            0, 0, 0, 0,
            $width, $height,
            imagesx($sourceImage), imagesy($sourceImage)
        );
        
        // Convertir en base64
        ob_start();
        imagepng($resizedImage);
        $imageData = ob_get_clean();
        
        imagedestroy($sourceImage);
        imagedestroy($resizedImage);
        
        return 'data:image/png;base64,' . base64_encode($imageData);
    }
    
    public function saveSignature($medecin_id, $signature_data, $cachet_data = null, $numero_ordre = null, $specialite = null) {
        // Redimensionner la signature
        $signature_data = $this->resizeSignature($signature_data, 400, 150);
        
        // Redimensionner le cachet si présent
        if ($cachet_data) {
            $cachet_data = $this->resizeSignature($cachet_data, 200, 200);
        }
        
        $stmt = $this->db->prepare("
            INSERT INTO medecin_signatures (medecin_id, signature_image, cachet_image, numero_ordre, specialite) 
            VALUES (?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
                signature_image = VALUES(signature_image),
                cachet_image = COALESCE(VALUES(cachet_image), cachet_image),
                numero_ordre = COALESCE(VALUES(numero_ordre), numero_ordre),
                specialite = COALESCE(VALUES(specialite), specialite),
                updated_at = CURRENT_TIMESTAMP
        ");
        
        return $stmt->execute([$medecin_id, $signature_data, $cachet_data, $numero_ordre, $specialite]);
    }
    
    public function getSignature($medecin_id) {
        $stmt = $this->db->prepare("SELECT * FROM medecin_signatures WHERE medecin_id = ? AND is_active = 1");
        $stmt->execute([$medecin_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function signDocument($document_type, $document_id, $medecin_id) {
        $hash = hash('sha256', $document_type . $document_id . $medecin_id . time());
        
        $stmt = $this->db->prepare("
            INSERT INTO documents_signes (document_type, document_id, medecin_id, signature_hash, ip_address) 
            VALUES (?, ?, ?, ?, ?)
        ");
        
        return $stmt->execute([
            $document_type,
            $document_id,
            $medecin_id,
            $hash,
            $_SERVER['REMOTE_ADDR'] ?? null
        ]);
    }
    
    public function isDocumentSigned($document_type, $document_id) {
        $stmt = $this->db->prepare("
            SELECT * FROM documents_signes 
            WHERE document_type = ? AND document_id = ?
        ");
        $stmt->execute([$document_type, $document_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
