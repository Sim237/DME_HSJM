-- MÉTRIQUES SYSTÈME
CREATE TABLE IF NOT EXISTS system_metrics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    metric_type ENUM('CPU', 'MEMORY', 'DISK', 'NETWORK', 'DATABASE', 'USERS') NOT NULL,
    value DECIMAL(10,2) NOT NULL,
    unit VARCHAR(20) NOT NULL,
    timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_type_time (metric_type, timestamp)
);

-- LOGS CENTRALISÉS
CREATE TABLE IF NOT EXISTS system_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    level ENUM('DEBUG', 'INFO', 'WARNING', 'ERROR', 'CRITICAL') NOT NULL,
    module VARCHAR(100) NOT NULL,
    message TEXT NOT NULL,
    context JSON,
    user_id INT NULL,
    ip_address VARCHAR(45),
    timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_level_time (level, timestamp),
    INDEX idx_module_time (module, timestamp),
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- ALERTES SYSTÈME
CREATE TABLE IF NOT EXISTS system_alerts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    type ENUM('PERFORMANCE', 'ERROR', 'SECURITY', 'MAINTENANCE') NOT NULL,
    severity ENUM('LOW', 'MEDIUM', 'HIGH', 'CRITICAL') NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    status ENUM('ACTIVE', 'ACKNOWLEDGED', 'RESOLVED') DEFAULT 'ACTIVE',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    acknowledged_at DATETIME NULL,
    acknowledged_by INT NULL,
    resolved_at DATETIME NULL,
    FOREIGN KEY (acknowledged_by) REFERENCES users(id)
);

-- RAPPORTS AUTOMATIQUES
CREATE TABLE IF NOT EXISTS automated_reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    type ENUM('DAILY', 'WEEKLY', 'MONTHLY') NOT NULL,
    recipients JSON NOT NULL,
    last_generated DATETIME NULL,
    next_generation DATETIME NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    template TEXT NOT NULL
);

-- SURVEILLANCE PERFORMANCES
CREATE TABLE IF NOT EXISTS performance_monitoring (
    id INT AUTO_INCREMENT PRIMARY KEY,
    endpoint VARCHAR(255) NOT NULL,
    response_time INT NOT NULL,
    status_code INT NOT NULL,
    memory_usage DECIMAL(10,2),
    timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_endpoint_time (endpoint, timestamp)
);

-- Données initiales
INSERT IGNORE INTO automated_reports (name, type, recipients, next_generation, template) VALUES 
('Rapport Quotidien Système', 'DAILY', '["admin@hospital.com"]', NOW(), 'daily_system'),
('Synthèse Hebdomadaire', 'WEEKLY', '["admin@hospital.com", "direction@hospital.com"]', NOW(), 'weekly_summary'),
('Rapport Mensuel Complet', 'MONTHLY', '["admin@hospital.com", "direction@hospital.com"]', NOW(), 'monthly_complete');