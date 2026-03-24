<?php
class TwoFactorService {
    private $db;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }
    
    public function generateSecret() {
        return $this->base32Encode(random_bytes(20));
    }
    
    public function enable2FA($user_id) {
        $secret = $this->generateSecret();
        $backup_codes = $this->generateBackupCodes();
        
        $sql = "INSERT INTO user_2fa (user_id, secret_key, backup_codes) 
                VALUES (:user_id, :secret, :backup_codes)
                ON DUPLICATE KEY UPDATE 
                secret_key = :secret, backup_codes = :backup_codes, is_enabled = FALSE";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':user_id' => $user_id,
            ':secret' => $secret,
            ':backup_codes' => json_encode($backup_codes)
        ]);
        
        return [
            'secret' => $secret,
            'backup_codes' => $backup_codes,
            'qr_code' => $this->generateQRCode($user_id, $secret)
        ];
    }
    
    public function verify2FA($user_id, $code) {
        $sql = "SELECT * FROM user_2fa WHERE user_id = :user_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':user_id' => $user_id]);
        $user_2fa = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user_2fa) return false;
        
        // Vérifier le code TOTP
        if ($this->verifyTOTP($user_2fa['secret_key'], $code)) {
            $this->updateLastUsed($user_id);
            return true;
        }
        
        // Vérifier les codes de sauvegarde
        $backup_codes = json_decode($user_2fa['backup_codes'], true);
        if (in_array($code, $backup_codes)) {
            // Supprimer le code utilisé
            $backup_codes = array_diff($backup_codes, [$code]);
            $this->updateBackupCodes($user_id, $backup_codes);
            return true;
        }
        
        return false;
    }
    
    public function confirm2FA($user_id, $code) {
        if ($this->verify2FA($user_id, $code)) {
            $sql = "UPDATE user_2fa SET is_enabled = TRUE WHERE user_id = :user_id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':user_id' => $user_id]);
            
            // Marquer l'utilisateur comme nécessitant 2FA
            $sql = "UPDATE users SET require_2fa = TRUE WHERE id = :user_id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':user_id' => $user_id]);
            
            return true;
        }
        return false;
    }
    
    public function disable2FA($user_id) {
        $sql = "UPDATE user_2fa SET is_enabled = FALSE WHERE user_id = :user_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':user_id' => $user_id]);
        
        $sql = "UPDATE users SET require_2fa = FALSE WHERE id = :user_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':user_id' => $user_id]);
    }
    
    public function is2FAEnabled($user_id) {
        $sql = "SELECT is_enabled FROM user_2fa WHERE user_id = :user_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':user_id' => $user_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $result['is_enabled'] : false;
    }
    
    private function verifyTOTP($secret, $code, $window = 1) {
        $time = floor(time() / 30);
        
        for ($i = -$window; $i <= $window; $i++) {
            if ($this->generateTOTP($secret, $time + $i) === $code) {
                return true;
            }
        }
        return false;
    }
    
    private function generateTOTP($secret, $time) {
        $key = $this->base32Decode($secret);
        $time = pack('N*', 0, $time);
        $hash = hash_hmac('sha1', $time, $key, true);
        $offset = ord($hash[19]) & 0xf;
        $code = (
            ((ord($hash[$offset]) & 0x7f) << 24) |
            ((ord($hash[$offset + 1]) & 0xff) << 16) |
            ((ord($hash[$offset + 2]) & 0xff) << 8) |
            (ord($hash[$offset + 3]) & 0xff)
        ) % 1000000;
        return str_pad($code, 6, '0', STR_PAD_LEFT);
    }
    
    private function generateBackupCodes() {
        $codes = [];
        for ($i = 0; $i < 10; $i++) {
            $codes[] = strtoupper(bin2hex(random_bytes(4)));
        }
        return $codes;
    }
    
    private function generateQRCode($user_id, $secret) {
        // Récupérer les infos utilisateur
        $sql = "SELECT username, email FROM users WHERE id = :user_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':user_id' => $user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $issuer = 'DME Hospital';
        $account = $user['email'] ?? $user['username'];
        
        $qr_string = "otpauth://totp/{$issuer}:{$account}?secret={$secret}&issuer={$issuer}";
        
        return "https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=" . urlencode($qr_string);
    }
    
    private function updateLastUsed($user_id) {
        $sql = "UPDATE user_2fa SET last_used_at = NOW() WHERE user_id = :user_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':user_id' => $user_id]);
    }
    
    private function updateBackupCodes($user_id, $codes) {
        $sql = "UPDATE user_2fa SET backup_codes = :codes WHERE user_id = :user_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':user_id' => $user_id, ':codes' => json_encode($codes)]);
    }
    
    private function base32Encode($data) {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $output = '';
        $v = 0;
        $vbits = 0;
        
        for ($i = 0; $i < strlen($data); $i++) {
            $v = ($v << 8) | ord($data[$i]);
            $vbits += 8;
            while ($vbits >= 5) {
                $vbits -= 5;
                $output .= $alphabet[($v >> $vbits) & 31];
            }
        }
        
        if ($vbits > 0) {
            $output .= $alphabet[($v << (5 - $vbits)) & 31];
        }
        
        return $output;
    }
    
    private function base32Decode($data) {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $output = '';
        $v = 0;
        $vbits = 0;
        
        for ($i = 0; $i < strlen($data); $i++) {
            $v = ($v << 5) | strpos($alphabet, $data[$i]);
            $vbits += 5;
            if ($vbits >= 8) {
                $vbits -= 8;
                $output .= chr(($v >> $vbits) & 255);
            }
        }
        
        return $output;
    }
}
?>