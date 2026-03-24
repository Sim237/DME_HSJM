<?php
class BackupService {
    private $db;
    private $backup_dir;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->backup_dir = __DIR__ . '/../../backups/';
        
        if (!is_dir($this->backup_dir)) {
            mkdir($this->backup_dir, 0755, true);
        }
    }
    
    public function createFullBackup() {
        $backup_id = $this->logBackupStart('full');
        
        try {
            $filename = 'full_backup_' . date('Y-m-d_H-i-s') . '.sql';
            $filepath = $this->backup_dir . $filename;
            
            // Commande mysqldump
            $command = sprintf(
                'mysqldump --host=%s --user=%s --password=%s --single-transaction --routines --triggers %s > %s',
                DB_HOST,
                DB_USER,
                DB_PASS,
                DB_NAME,
                escapeshellarg($filepath)
            );
            
            exec($command, $output, $return_code);
            
            if ($return_code === 0 && file_exists($filepath)) {
                $file_size = filesize($filepath);
                $this->logBackupComplete($backup_id, $filepath, $file_size);
                
                // Compresser le fichier
                $this->compressBackup($filepath);
                
                // Nettoyer les anciennes sauvegardes
                $this->cleanOldBackups();
                
                return [
                    'success' => true,
                    'filename' => $filename,
                    'size' => $file_size
                ];
            } else {
                throw new Exception('Échec de la sauvegarde MySQL');
            }
            
        } catch (Exception $e) {
            $this->logBackupError($backup_id, $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    public function createIncrementalBackup() {
        $backup_id = $this->logBackupStart('incremental');
        
        try {
            $last_backup = $this->getLastBackupTime();
            $filename = 'incremental_backup_' . date('Y-m-d_H-i-s') . '.sql';
            $filepath = $this->backup_dir . $filename;
            
            // Sauvegarder seulement les données modifiées
            $tables = $this->getModifiedTables($last_backup);
            
            if (empty($tables)) {
                $this->logBackupComplete($backup_id, null, 0);
                return ['success' => true, 'message' => 'Aucune modification détectée'];
            }
            
            $sql_content = $this->generateIncrementalSQL($tables, $last_backup);
            file_put_contents($filepath, $sql_content);
            
            $file_size = filesize($filepath);
            $this->logBackupComplete($backup_id, $filepath, $file_size);
            
            return [
                'success' => true,
                'filename' => $filename,
                'size' => $file_size,
                'tables' => count($tables)
            ];
            
        } catch (Exception $e) {
            $this->logBackupError($backup_id, $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    public function scheduleAutomaticBackups() {
        // Créer un fichier cron job
        $cron_content = "#!/bin/bash\n";
        $cron_content .= "# Sauvegarde quotidienne complète à 2h du matin\n";
        $cron_content .= "0 2 * * * php " . __DIR__ . "/../../scripts/backup_cron.php full\n";
        $cron_content .= "# Sauvegarde incrémentale toutes les 4 heures\n";
        $cron_content .= "0 */4 * * * php " . __DIR__ . "/../../scripts/backup_cron.php incremental\n";
        
        $cron_file = $this->backup_dir . 'backup_cron.sh';
        file_put_contents($cron_file, $cron_content);
        chmod($cron_file, 0755);
        
        return $cron_file;
    }
    
    public function restoreBackup($backup_file) {
        if (!file_exists($backup_file)) {
            throw new Exception('Fichier de sauvegarde introuvable');
        }
        
        // Décompresser si nécessaire
        if (pathinfo($backup_file, PATHINFO_EXTENSION) === 'gz') {
            $backup_file = $this->decompressBackup($backup_file);
        }
        
        $command = sprintf(
            'mysql --host=%s --user=%s --password=%s %s < %s',
            DB_HOST,
            DB_USER,
            DB_PASS,
            DB_NAME,
            escapeshellarg($backup_file)
        );
        
        exec($command, $output, $return_code);
        
        if ($return_code !== 0) {
            throw new Exception('Échec de la restauration');
        }
        
        // Logger la restauration
        $audit = new AuditService();
        $audit->logAction('RESTORE_BACKUP', 'system', null, null, ['file' => basename($backup_file)]);
        
        return true;
    }
    
    public function getBackupHistory($limit = 50) {
        $sql = "SELECT * FROM backup_logs ORDER BY start_time DESC LIMIT :limit";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getBackupStats() {
        $sql = "SELECT 
                    COUNT(*) as total_backups,
                    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as successful,
                    SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed,
                    SUM(file_size) as total_size,
                    MAX(start_time) as last_backup
                FROM backup_logs 
                WHERE start_time >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    private function logBackupStart($type) {
        $sql = "INSERT INTO backup_logs (backup_type, status) VALUES (:type, 'started')";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':type' => $type]);
        return $this->db->lastInsertId();
    }
    
    private function logBackupComplete($backup_id, $filepath, $file_size) {
        $sql = "UPDATE backup_logs 
                SET status = 'completed', file_path = :filepath, file_size = :size, end_time = NOW()
                WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':id' => $backup_id,
            ':filepath' => $filepath,
            ':size' => $file_size
        ]);
    }
    
    private function logBackupError($backup_id, $error) {
        $sql = "UPDATE backup_logs 
                SET status = 'failed', error_message = :error, end_time = NOW()
                WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $backup_id, ':error' => $error]);
    }
    
    private function compressBackup($filepath) {
        if (function_exists('gzencode')) {
            $data = file_get_contents($filepath);
            $compressed = gzencode($data, 9);
            file_put_contents($filepath . '.gz', $compressed);
            unlink($filepath); // Supprimer le fichier non compressé
        }
    }
    
    private function decompressBackup($filepath) {
        $decompressed_file = str_replace('.gz', '', $filepath);
        $data = file_get_contents($filepath);
        $decompressed = gzdecode($data);
        file_put_contents($decompressed_file, $decompressed);
        return $decompressed_file;
    }
    
    private function cleanOldBackups($keep_days = 30) {
        $files = glob($this->backup_dir . '*');
        $cutoff = time() - ($keep_days * 24 * 60 * 60);
        
        foreach ($files as $file) {
            if (filemtime($file) < $cutoff) {
                unlink($file);
            }
        }
    }
    
    private function getLastBackupTime() {
        $sql = "SELECT MAX(start_time) as last_backup FROM backup_logs WHERE status = 'completed'";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['last_backup'] ?? '1970-01-01 00:00:00';
    }
    
    private function getModifiedTables($since) {
        // Tables avec colonnes de timestamp
        $tables_with_timestamps = [
            'patients' => 'created_at',
            'consultations' => 'created_at',
            'users' => 'created_at',
            'factures' => 'created_at'
        ];
        
        $modified_tables = [];
        
        foreach ($tables_with_timestamps as $table => $timestamp_col) {
            $sql = "SELECT COUNT(*) as count FROM $table WHERE $timestamp_col > :since";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':since' => $since]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result['count'] > 0) {
                $modified_tables[] = $table;
            }
        }
        
        return $modified_tables;
    }
    
    private function generateIncrementalSQL($tables, $since) {
        $sql_content = "-- Sauvegarde incrémentale du " . date('Y-m-d H:i:s') . "\n";
        $sql_content .= "-- Données modifiées depuis : $since\n\n";
        
        foreach ($tables as $table) {
            $sql_content .= "-- Table: $table\n";
            // Ici, vous pourriez implémenter une logique plus sophistiquée
            // pour extraire seulement les enregistrements modifiés
        }
        
        return $sql_content;
    }
}
?>