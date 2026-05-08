<?php
/* ============================================================================
   FICHIER : app/services/SignatureService.php
   Service de gestion des signatures électroniques et cachets médicaux
   ============================================================================ */

class SignatureService {
    private $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    // ── UPLOAD & REDIMENSIONNEMENT ────────────────────────────────────────────

    /**
     * Traite un fichier uploadé ($_FILES entry), le redimensionne avec GD
     * et le sauvegarde dans assets/uploads/signatures/.
     * Synchronise aussi medecin_signatures (base64).
     *
     * @param array  $file     Entrée $_FILES (ex: $_FILES['signature'])
     * @param string $type     'signature' ou 'cachet'
     * @param int    $userId   ID de l'utilisateur
     * @return string|null     Chemin relatif (ex: assets/uploads/signatures/user_3_signature.png)
     */
    public function uploadAndResize(array $file, string $type, int $userId): ?string
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || empty($file['size'])) {
            return null;
        }

        // Vérification du type MIME
        $allowed = ['image/png', 'image/jpeg', 'image/jpg', 'image/gif', 'image/webp'];
        $mime    = mime_content_type($file['tmp_name']);
        if (!in_array($mime, $allowed, true)) {
            return null;
        }

        // Dimensions cibles
        [$targetW, $targetH] = ($type === 'cachet') ? [220, 220] : [420, 160];

        // Charger l'image source
        $src = @imagecreatefromstring(file_get_contents($file['tmp_name']));
        if (!$src) return null;

        $srcW = imagesx($src);
        $srcH = imagesy($src);

        // Conserver le ratio sans déformation
        $ratio = min($targetW / $srcW, $targetH / $srcH);
        $newW  = (int)round($srcW * $ratio);
        $newH  = (int)round($srcH * $ratio);
        $offX  = (int)round(($targetW - $newW) / 2);
        $offY  = (int)round(($targetH - $newH) / 2);

        // Canvas final avec fond transparent
        $dst = imagecreatetruecolor($targetW, $targetH);
        imagealphablending($dst, false);
        imagesavealpha($dst, true);
        $transparent = imagecolorallocatealpha($dst, 255, 255, 255, 127);
        imagefilledrectangle($dst, 0, 0, $targetW, $targetH, $transparent);
        imagealphablending($dst, true);

        imagecopyresampled($dst, $src, $offX, $offY, 0, 0, $newW, $newH, $srcW, $srcH);

        // Dossier de stockage
        $dir = UPLOAD_PATH . 'signatures/';
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $filename = "user_{$userId}_{$type}.png";
        $fullPath = $dir . $filename;
        imagepng($dst, $fullPath, 3); // compression légère

        imagedestroy($src);
        imagedestroy($dst);

        $relativePath = 'assets/uploads/signatures/' . $filename;

        // Synchroniser medecin_signatures (base64)
        $b64 = 'data:image/png;base64,' . base64_encode(file_get_contents($fullPath));
        $col = ($type === 'cachet') ? 'cachet_image' : 'signature_image';
        $this->db->prepare("
            INSERT INTO medecin_signatures (medecin_id, {$col}, is_active)
            VALUES (?, ?, 1)
            ON DUPLICATE KEY UPDATE {$col} = VALUES({$col}), updated_at = NOW()
        ")->execute([$userId, $b64]);

        return $relativePath;
    }

    /**
     * Redimensionne une image base64 et retourne une nouvelle base64
     * (pour les signatures dessinées sur canvas)
     */
    public function resizeBase64(string $base64Image, int $width = 420, int $height = 160): string
    {
        $parts   = explode(',', $base64Image);
        $content = base64_decode(end($parts));
        $src     = @imagecreatefromstring($content);
        if (!$src) return $base64Image;

        $dst = imagecreatetruecolor($width, $height);
        imagealphablending($dst, false);
        imagesavealpha($dst, true);
        $t = imagecolorallocatealpha($dst, 255, 255, 255, 127);
        imagefilledrectangle($dst, 0, 0, $width, $height, $t);
        imagealphablending($dst, true);

        imagecopyresampled($dst, $src, 0, 0, 0, 0, $width, $height, imagesx($src), imagesy($src));

        ob_start();
        imagepng($dst);
        $data = ob_get_clean();

        imagedestroy($src);
        imagedestroy($dst);

        return 'data:image/png;base64,' . base64_encode($data);
    }

    // ── SAUVEGARDE PROFIL (depuis la page profil/signature) ──────────────────

    /**
     * Enregistre une signature base64 + cachet dans medecin_signatures
     */
    public function saveSignature(int $medecin_id, string $signature_data, ?string $cachet_data = null,
                                   ?string $numero_ordre = null, ?string $specialite = null): bool
    {
        $signature_data = $this->resizeBase64($signature_data, 420, 160);
        if ($cachet_data) {
            $cachet_data = $this->resizeBase64($cachet_data, 220, 220);
        }

        $stmt = $this->db->prepare("
            INSERT INTO medecin_signatures (medecin_id, signature_image, cachet_image, numero_ordre, specialite)
            VALUES (?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                signature_image = VALUES(signature_image),
                cachet_image    = COALESCE(VALUES(cachet_image), cachet_image),
                numero_ordre    = COALESCE(VALUES(numero_ordre), numero_ordre),
                specialite      = COALESCE(VALUES(specialite), specialite),
                updated_at      = NOW()
        ");

        return $stmt->execute([$medecin_id, $signature_data, $cachet_data, $numero_ordre, $specialite]);
    }

    // ── LECTURE ───────────────────────────────────────────────────────────────

    /**
     * Récupère le profil de signature d'un médecin (medecin_signatures)
     */
    public function getSignature(int $medecin_id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM medecin_signatures WHERE medecin_id = ? AND is_active = 1 ORDER BY id DESC LIMIT 1");
        $stmt->execute([$medecin_id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Récupère les infos de signature d'un médecin (merge users + medecin_signatures)
     * Retourne: signature_src, cachet_src, numero_ordre, specialite
     */
    public function getMedecinSignatureInfo(int $medecin_id): array
    {
        // Fichiers depuis users
        $stmt = $this->db->prepare("SELECT signature_path, cachet_path, specialite FROM users WHERE id = ?");
        $stmt->execute([$medecin_id]);
        $userRow = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

        // Base64 + numero_ordre depuis medecin_signatures
        $sigRow = $this->getSignature($medecin_id) ?: [];

        // Résolution: préférer le fichier physique (plus léger), fallback sur base64
        $sig_src = '';
        if (!empty($userRow['signature_path'])) {
            $sig_src = BASE_URL . $userRow['signature_path'];
        } elseif (!empty($sigRow['signature_image'])) {
            $sig_src = $sigRow['signature_image'];
        }

        $cac_src = '';
        if (!empty($userRow['cachet_path'])) {
            $cac_src = BASE_URL . $userRow['cachet_path'];
        } elseif (!empty($sigRow['cachet_image'])) {
            $cac_src = $sigRow['cachet_image'];
        }

        return [
            'signature_src'  => $sig_src,
            'cachet_src'     => $cac_src,
            'numero_ordre'   => $sigRow['numero_ordre'] ?? null,
            'specialite'     => $sigRow['specialite'] ?? $userRow['specialite'] ?? null,
            'has_signature'  => !empty($sig_src),
            'has_cachet'     => !empty($cac_src),
        ];
    }

    // ── SIGNATURE DE DOCUMENT ─────────────────────────────────────────────────

    /**
     * Enregistre la signature d'un document dans documents_signes
     * Retourne le hash généré
     */
    public function signDocument(string $document_type, int $document_id, int $medecin_id): string
    {
        $hash = hash('sha256', $document_type . $document_id . $medecin_id . microtime(true));

        // Supprimer toute entrée existante (re-signature)
        $this->db->prepare("DELETE FROM documents_signes WHERE document_type = ? AND document_id = ?")
                 ->execute([$document_type, $document_id]);

        $this->db->prepare("
            INSERT INTO documents_signes (document_type, document_id, medecin_id, signature_hash, ip_address)
            VALUES (?, ?, ?, ?, ?)
        ")->execute([$document_type, $document_id, $medecin_id, $hash, $_SERVER['REMOTE_ADDR'] ?? null]);

        return $hash;
    }

    /**
     * Vérifie si un document est signé et retourne les infos de signature
     */
    public function isDocumentSigned(string $document_type, int $document_id): ?array
    {
        $stmt = $this->db->prepare("
            SELECT ds.*, u.nom, u.prenom, u.specialite
            FROM documents_signes ds
            JOIN users u ON ds.medecin_id = u.id
            WHERE ds.document_type = ? AND ds.document_id = ?
            ORDER BY ds.signed_at DESC LIMIT 1
        ");
        $stmt->execute([$document_type, $document_id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Vérifie l'authenticité d'un document via son hash
     */
    public function verifyHash(string $document_type, int $document_id, string $hash): bool
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) FROM documents_signes
            WHERE document_type = ? AND document_id = ? AND signature_hash = ?
        ");
        $stmt->execute([$document_type, $document_id, $hash]);
        return (int)$stmt->fetchColumn() > 0;
    }
}
