<?php
require_once __DIR__ . '/UnifiedController.php';
require_once __DIR__ . '/../services/AuditService.php';
require_once __DIR__ . '/../services/TwoFactorService.php';
require_once __DIR__ . '/../services/BackupService.php';
require_once __DIR__ . '/../services/EncryptionService.php';

class SecurityController extends UnifiedController {
    
    public function index() {
        $this->auth->requirePermission('parametres', 'admin');
        require_once __DIR__ . '/../views/security/index.php';
    }
    
    public function auditLogs() {
        $this->auth->requirePermission('parametres', 'admin');
        
        $auditService = new AuditService();
        $filters = [
            'user_id' => $_GET['user_id'] ?? null,
            'table_name' => $_GET['table_name'] ?? null,
            'action' => $_GET['action'] ?? null,
            'date_from' => $_GET['date_from'] ?? null,
            'date_to' => $_GET['date_to'] ?? null
        ];
        
        $logs = $auditService->getAuditLogs($filters);
        
        header('Content-Type: application/json');
        echo json_encode($logs);
    }
    
    public function enable2FA() {
        $user_id = $_SESSION['user_id'];
        $twoFactorService = new TwoFactorService();
        
        $setup = $twoFactorService->enable2FA($user_id);
        
        header('Content-Type: application/json');
        echo json_encode($setup);
    }
    
    public function verify2FA() {
        $user_id = $_SESSION['user_id'];
        $code = $_POST['code'] ?? '';
        
        $twoFactorService = new TwoFactorService();
        $verified = $twoFactorService->confirm2FA($user_id, $code);
        
        header('Content-Type: application/json');
        echo json_encode(['success' => $verified]);
    }
    
    public function disable2FA() {
        $user_id = $_SESSION['user_id'];
        $twoFactorService = new TwoFactorService();
        $twoFactorService->disable2FA($user_id);
        
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
    }
    
    public function createBackup() {
        $this->auth->requirePermission('parametres', 'admin');
        
        $type = $_POST['type'] ?? 'full';
        $backupService = new BackupService();
        
        if ($type === 'incremental') {
            $result = $backupService->createIncrementalBackup();
        } else {
            $result = $backupService->createFullBackup();
        }
        
        header('Content-Type: application/json');
        echo json_encode($result);
    }
    
    public function backupHistory() {
        $this->auth->requirePermission('parametres', 'admin');
        
        $backupService = new BackupService();
        $history = $backupService->getBackupHistory();
        
        header('Content-Type: application/json');
        echo json_encode($history);
    }
    
    public function securityDashboard() {
        $this->auth->requirePermission('parametres', 'admin');
        
        $auditService = new AuditService();
        $backupService = new BackupService();
        $encryptionService = new EncryptionService();
        
        $data = [
            'login_attempts' => $auditService->getLoginAttempts(24),
            'backup_stats' => $backupService->getBackupStats(),
            'encryption_integrity' => $encryptionService->checkDataIntegrity(),
            'suspicious_activities' => $this->getSuspiciousActivities()
        ];
        
        header('Content-Type: application/json');
        echo json_encode($data);
    }
    
    public function anonymizePatient() {
        $this->auth->requirePermission('parametres', 'admin');
        
        $patient_id = $_POST['patient_id'] ?? null;
        if (!$patient_id) {
            http_response_code(400);
            echo json_encode(['error' => 'Patient ID requis']);
            return;
        }
        
        $encryptionService = new EncryptionService();
        $anonymized_data = $encryptionService->anonymizeData('patients', $patient_id);
        
        // Mettre à jour le patient avec les données anonymisées
        $database = new Database();
        $db = $database->getConnection();
        
        $sql = "UPDATE patients SET 
                nom = :nom, prenom = :prenom, email = :email, 
                telephone = :telephone, adresse = :adresse
                WHERE id = :id";
        
        $stmt = $db->prepare($sql);
        $stmt->execute(array_merge($anonymized_data, ['id' => $patient_id]));
        
        // Logger l'anonymisation
        $auditService = new AuditService();
        $auditService->logAction('ANONYMIZE_PATIENT', 'patients', $patient_id, null, $anonymized_data);
        
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
    }
    
    public function exportAuditLogs() {
        $this->auth->requirePermission('parametres', 'admin');
        
        $auditService = new AuditService();
        $logs = $auditService->getAuditLogs();
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="audit_logs_' . date('Y-m-d') . '.csv"');
        
        echo "Date,Utilisateur,Action,Table,ID,IP,Details\n";
        
        foreach ($logs as $log) {
            echo implode(',', [
                $log['created_at'],
                $log['nom'] . ' ' . $log['prenom'],
                $log['action'],
                $log['table_name'],
                $log['record_id'] ?? '',
                $log['ip_address'],
                str_replace(["\n", "\r", ","], [" ", " ", ";"], $log['new_values'] ?? '')
            ]) . "\n";
        }
    }
    
    private function getSuspiciousActivities() {
        $database = new Database();
        $db = $database->getConnection();
        
        // Activités suspectes des dernières 24h
        $sql = "SELECT 
                    u.nom, u.prenom, a.action, a.ip_address, a.created_at,
                    COUNT(*) as frequency
                FROM audit_logs a
                LEFT JOIN users u ON a.user_id = u.id
                WHERE a.created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
                AND a.action IN ('DELETE', 'BULK_DELETE', 'LOGIN_FAILED', 'ADMIN_ACCESS')
                GROUP BY a.user_id, a.action, a.ip_address
                HAVING frequency > 5
                ORDER BY frequency DESC, a.created_at DESC
                LIMIT 20";
        
        $stmt = $db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>