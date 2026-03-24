<?php

class SystemMonitoringService {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    // MÉTRIQUES TEMPS RÉEL
    public function collectMetrics() {
        $metrics = [
            'CPU' => $this->getCpuUsage(),
            'MEMORY' => $this->getMemoryUsage(),
            'DISK' => $this->getDiskUsage(),
            'DATABASE' => $this->getDatabaseMetrics(),
            'USERS' => $this->getActiveUsers()
        ];
        
        foreach ($metrics as $type => $data) {
            $this->saveMetric($type, $data['value'], $data['unit']);
        }
        
        $this->checkThresholds($metrics);
    }
    
    private function getCpuUsage() {
        $load = sys_getloadavg();
        return ['value' => round($load[0] * 100, 2), 'unit' => '%'];
    }
    
    private function getMemoryUsage() {
        $memory = memory_get_usage(true);
        $peak = memory_get_peak_usage(true);
        return ['value' => round($memory / 1024 / 1024, 2), 'unit' => 'MB'];
    }
    
    private function getDiskUsage() {
        $total = disk_total_space('.');
        $free = disk_free_space('.');
        $used = ($total - $free) / $total * 100;
        return ['value' => round($used, 2), 'unit' => '%'];
    }
    
    private function getDatabaseMetrics() {
        $stmt = $this->db->query("SHOW STATUS LIKE 'Threads_connected'");
        $connections = $stmt->fetch()['Value'];
        return ['value' => $connections, 'unit' => 'connections'];
    }
    
    private function getActiveUsers() {
        $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM users WHERE last_login > DATE_SUB(NOW(), INTERVAL 1 HOUR)");
        $stmt->execute();
        return ['value' => $stmt->fetch()['count'], 'unit' => 'users'];
    }
    
    private function saveMetric($type, $value, $unit) {
        $stmt = $this->db->prepare("INSERT INTO system_metrics (metric_type, value, unit) VALUES (?, ?, ?)");
        $stmt->execute([$type, $value, $unit]);
    }
    
    // ALERTES SYSTÈME
    private function checkThresholds($metrics) {
        if ($metrics['CPU']['value'] > 80) {
            $this->createAlert('PERFORMANCE', 'HIGH', 'CPU Élevé', "Utilisation CPU: {$metrics['CPU']['value']}%");
        }
        
        if ($metrics['MEMORY']['value'] > 1000) {
            $this->createAlert('PERFORMANCE', 'MEDIUM', 'Mémoire Élevée', "Utilisation mémoire: {$metrics['MEMORY']['value']} MB");
        }
        
        if ($metrics['DISK']['value'] > 90) {
            $this->createAlert('PERFORMANCE', 'CRITICAL', 'Disque Plein', "Utilisation disque: {$metrics['DISK']['value']}%");
        }
    }
    
    public function createAlert($type, $severity, $title, $message) {
        $stmt = $this->db->prepare("INSERT INTO system_alerts (type, severity, title, message) VALUES (?, ?, ?, ?)");
        $stmt->execute([$type, $severity, $title, $message]);
        
        if ($severity === 'CRITICAL') {
            $this->sendCriticalAlert($title, $message);
        }
    }
    
    private function sendCriticalAlert($title, $message) {
        // Notification immédiate pour alertes critiques
        $notificationService = new NotificationService();
        $notificationService->sendEmail('admin@hospital.com', "ALERTE CRITIQUE: $title", $message);
    }
    
    // LOGS CENTRALISÉS
    public function log($level, $module, $message, $context = null, $userId = null) {
        $stmt = $this->db->prepare("INSERT INTO system_logs (level, module, message, context, user_id, ip_address) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $level,
            $module,
            $message,
            $context ? json_encode($context) : null,
            $userId,
            $_SERVER['REMOTE_ADDR'] ?? null
        ]);
    }
    
    // SURVEILLANCE PERFORMANCES
    public function logPerformance($endpoint, $responseTime, $statusCode, $memoryUsage) {
        $stmt = $this->db->prepare("INSERT INTO performance_monitoring (endpoint, response_time, status_code, memory_usage) VALUES (?, ?, ?, ?)");
        $stmt->execute([$endpoint, $responseTime, $statusCode, $memoryUsage]);
    }
    
    // RAPPORTS AUTOMATIQUES
    public function generateScheduledReports() {
        $stmt = $this->db->prepare("SELECT * FROM automated_reports WHERE is_active = 1 AND next_generation <= NOW()");
        $stmt->execute();
        
        while ($report = $stmt->fetch()) {
            $this->generateReport($report);
            $this->updateNextGeneration($report);
        }
    }
    
    private function generateReport($report) {
        $data = $this->getReportData($report['template']);
        $content = $this->formatReport($report['template'], $data);
        
        $recipients = json_decode($report['recipients'], true);
        foreach ($recipients as $email) {
            $notificationService = new NotificationService();
            $notificationService->sendEmail($email, $report['name'], $content);
        }
        
        $this->db->prepare("UPDATE automated_reports SET last_generated = NOW() WHERE id = ?")->execute([$report['id']]);
    }
    
    private function getReportData($template) {
        switch ($template) {
            case 'daily_system':
                return $this->getDailySystemData();
            case 'weekly_summary':
                return $this->getWeeklySummaryData();
            case 'monthly_complete':
                return $this->getMonthlyCompleteData();
        }
    }
    
    private function getDailySystemData() {
        return [
            'errors' => $this->getErrorCount('24 HOUR'),
            'performance' => $this->getAverageResponseTime('24 HOUR'),
            'users' => $this->getActiveUsersCount('24 HOUR'),
            'alerts' => $this->getAlertsCount('24 HOUR')
        ];
    }
    
    private function getWeeklySummaryData() {
        return [
            'errors' => $this->getErrorCount('7 DAY'),
            'performance' => $this->getAverageResponseTime('7 DAY'),
            'users' => $this->getActiveUsersCount('7 DAY'),
            'alerts' => $this->getAlertsCount('7 DAY'),
            'trends' => $this->getWeeklyTrends()
        ];
    }
    
    private function getMonthlyCompleteData() {
        return [
            'errors' => $this->getErrorCount('30 DAY'),
            'performance' => $this->getAverageResponseTime('30 DAY'),
            'users' => $this->getActiveUsersCount('30 DAY'),
            'alerts' => $this->getAlertsCount('30 DAY'),
            'trends' => $this->getMonthlyTrends(),
            'recommendations' => $this->getSystemRecommendations()
        ];
    }
    
    private function getErrorCount($period) {
        $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM system_logs WHERE level IN ('ERROR', 'CRITICAL') AND timestamp > DATE_SUB(NOW(), INTERVAL $period)");
        $stmt->execute();
        return $stmt->fetch()['count'];
    }
    
    private function getAverageResponseTime($period) {
        $stmt = $this->db->prepare("SELECT AVG(response_time) as avg_time FROM performance_monitoring WHERE timestamp > DATE_SUB(NOW(), INTERVAL $period)");
        $stmt->execute();
        return round($stmt->fetch()['avg_time'], 2);
    }
    
    private function getActiveUsersCount($period) {
        $stmt = $this->db->prepare("SELECT COUNT(DISTINCT user_id) as count FROM system_logs WHERE user_id IS NOT NULL AND timestamp > DATE_SUB(NOW(), INTERVAL $period)");
        $stmt->execute();
        return $stmt->fetch()['count'];
    }
    
    private function getAlertsCount($period) {
        $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM system_alerts WHERE created_at > DATE_SUB(NOW(), INTERVAL $period)");
        $stmt->execute();
        return $stmt->fetch()['count'];
    }
    
    private function formatReport($template, $data) {
        $html = "<h2>Rapport Système - " . date('d/m/Y H:i') . "</h2>";
        $html .= "<p><strong>Erreurs:</strong> {$data['errors']}</p>";
        $html .= "<p><strong>Temps de réponse moyen:</strong> {$data['performance']} ms</p>";
        $html .= "<p><strong>Utilisateurs actifs:</strong> {$data['users']}</p>";
        $html .= "<p><strong>Alertes:</strong> {$data['alerts']}</p>";
        
        return $html;
    }
    
    private function updateNextGeneration($report) {
        $interval = match($report['type']) {
            'DAILY' => '1 DAY',
            'WEEKLY' => '7 DAY',
            'MONTHLY' => '1 MONTH'
        };
        
        $this->db->prepare("UPDATE automated_reports SET next_generation = DATE_ADD(NOW(), INTERVAL $interval) WHERE id = ?")->execute([$report['id']]);
    }
}