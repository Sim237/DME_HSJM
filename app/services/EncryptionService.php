<?php
class EncryptionService {
    private $db;
    private $encryption_key;
    private $cipher = 'AES-256-CBC';
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->encryption_key = $this->getOrCreateEncryptionKey();
    }
    
    public function encryptSensitiveData($table_name, $record_id, $field_name, $data) {
        if (empty($data)) return $data;
        
        $encrypted = $this->encrypt($data);
        
        // Stocker les données chiffrées
        $sql = "INSERT INTO encrypted_data (table_name, record_id, field_name, encrypted_value)
                VALUES (:table, :record_id, :field, :encrypted)
                ON DUPLICATE KEY UPDATE 
                encrypted_value = :encrypted, updated_at = NOW()";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':table' => $table_name,
            ':record_id' => $record_id,
            ':field' => $field_name,
            ':encrypted' => $encrypted
        ]);
        
        // Retourner un hash pour la base de données principale
        return hash('sha256', $data);
    }
    
    public function decryptSensitiveData($table_name, $record_id, $field_name) {
        $sql = "SELECT encrypted_value FROM encrypted_data 
                WHERE table_name = :table AND record_id = :record_id AND field_name = :field";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':table' => $table_name,
            ':record_id' => $record_id,
            ':field' => $field_name
        ]);
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result) {
            return $this->decrypt($result['encrypted_value']);
        }
        
        return null;
    }
    
    public function encryptPatientData($patient_data) {
        $sensitive_fields = ['nom', 'prenom', 'telephone', 'email', 'adresse'];
        $encrypted_data = $patient_data;
        
        foreach ($sensitive_fields as $field) {
            if (isset($patient_data[$field])) {
                $encrypted_data[$field . '_hash'] = hash('sha256', $patient_data[$field]);
                // Les données réelles sont stockées chiffrées séparément
            }
        }
        
        return $encrypted_data;
    }
    
    public function hashPassword($password) {
        // Utiliser Argon2ID pour les mots de passe (plus sécurisé que bcrypt)
        return password_hash($password, PASSWORD_ARGON2ID, [
            'memory_cost' => 65536, // 64 MB
            'time_cost' => 4,       // 4 iterations
            'threads' => 3          // 3 threads
        ]);
    }
    
    public function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }
    
    public function generateSecureToken($length = 32) {
        return bin2hex(random_bytes($length));
    }
    
    public function anonymizeData($table_name, $record_id) {
        // Anonymiser les données pour la conformité RGPD
        $anonymized_data = [
            'nom' => 'ANONYME_' . substr(hash('sha256', $record_id), 0, 8),
            'prenom' => 'ANONYME',
            'email' => 'anonyme_' . $record_id . '@anonyme.local',
            'telephone' => '00000000',
            'adresse' => 'ADRESSE ANONYMISÉE'
        ];
        
        // Supprimer les données chiffrées
        $sql = "DELETE FROM encrypted_data 
                WHERE table_name = :table AND record_id = :record_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':table' => $table_name, ':record_id' => $record_id]);
        
        return $anonymized_data;
    }
    
    public function auditDataAccess($table_name, $record_id, $field_name, $action = 'READ') {
        $audit = new AuditService();
        $audit->logAction(
            'DATA_ACCESS_' . $action,
            $table_name,
            $record_id,
            null,
            ['field' => $field_name, 'encrypted' => true]
        );
    }
    
    public function checkDataIntegrity() {
        $issues = [];
        
        // Vérifier l'intégrité des données chiffrées
        $sql = "SELECT * FROM encrypted_data LIMIT 100";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $encrypted_records = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($encrypted_records as $record) {
            try {
                $decrypted = $this->decrypt($record['encrypted_value']);
                if ($decrypted === false) {
                    $issues[] = [
                        'type' => 'DECRYPTION_FAILED',
                        'table' => $record['table_name'],
                        'record_id' => $record['record_id'],
                        'field' => $record['field_name']
                    ];
                }
            } catch (Exception $e) {
                $issues[] = [
                    'type' => 'ENCRYPTION_ERROR',
                    'table' => $record['table_name'],
                    'record_id' => $record['record_id'],
                    'error' => $e->getMessage()
                ];
            }
        }
        
        return $issues;
    }
    
    private function encrypt($data) {
        $iv = random_bytes(openssl_cipher_iv_length($this->cipher));
        $encrypted = openssl_encrypt($data, $this->cipher, $this->encryption_key, 0, $iv);
        return base64_encode($iv . $encrypted);
    }
    
    private function decrypt($encrypted_data) {
        $data = base64_decode($encrypted_data);
        $iv_length = openssl_cipher_iv_length($this->cipher);
        $iv = substr($data, 0, $iv_length);
        $encrypted = substr($data, $iv_length);
        return openssl_decrypt($encrypted, $this->cipher, $this->encryption_key, 0, $iv);
    }
    
    private function getOrCreateEncryptionKey() {
        $key_file = __DIR__ . '/../../config/encryption.key';
        
        if (file_exists($key_file)) {
            return file_get_contents($key_file);
        }
        
        // Générer une nouvelle clé
        $key = random_bytes(32); // 256 bits
        
        // Créer le dossier si nécessaire
        $key_dir = dirname($key_file);
        if (!is_dir($key_dir)) {
            mkdir($key_dir, 0700, true);
        }
        
        file_put_contents($key_file, $key);
        chmod($key_file, 0600); // Lecture seule pour le propriétaire
        
        return $key;
    }
    
    public function rotateEncryptionKey() {
        // Rotation de la clé de chiffrement (procédure complexe)
        $old_key = $this->encryption_key;
        $new_key = random_bytes(32);
        
        // Rechiffrer toutes les données avec la nouvelle clé
        $sql = "SELECT * FROM encrypted_data";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $this->db->beginTransaction();
        
        try {
            foreach ($records as $record) {
                // Déchiffrer avec l'ancienne clé
                $decrypted = $this->decrypt($record['encrypted_value']);
                
                // Rechiffrer avec la nouvelle clé
                $this->encryption_key = $new_key;
                $re_encrypted = $this->encrypt($decrypted);
                
                // Mettre à jour en base
                $update_sql = "UPDATE encrypted_data 
                              SET encrypted_value = :encrypted, updated_at = NOW()
                              WHERE id = :id";
                $update_stmt = $this->db->prepare($update_sql);
                $update_stmt->execute([
                    ':encrypted' => $re_encrypted,
                    ':id' => $record['id']
                ]);
            }
            
            // Sauvegarder la nouvelle clé
            $key_file = __DIR__ . '/../../config/encryption.key';
            file_put_contents($key_file, $new_key);
            
            $this->db->commit();
            
            // Logger la rotation
            $audit = new AuditService();
            $audit->logAction('ENCRYPTION_KEY_ROTATION', 'system', null, null, [
                'records_updated' => count($records)
            ]);
            
            return true;
            
        } catch (Exception $e) {
            $this->db->rollback();
            $this->encryption_key = $old_key; // Restaurer l'ancienne clé
            throw $e;
        }
    }
}
?>