-- AUDIT TRAIL
CREATE TABLE IF NOT EXISTS audit_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    action VARCHAR(100) NOT NULL,
    table_name VARCHAR(50) NOT NULL,
    record_id INT NULL,
    old_values JSON NULL,
    new_values JSON NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    INDEX idx_user_date (user_id, created_at),
    INDEX idx_table_record (table_name, record_id)
);

-- DOUBLE AUTHENTIFICATION
CREATE TABLE IF NOT EXISTS user_2fa (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    secret_key VARCHAR(32) NOT NULL,
    is_enabled BOOLEAN DEFAULT FALSE,
    backup_codes JSON NULL,
    last_used_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    UNIQUE KEY unique_user_2fa (user_id)
);

-- SESSIONS SÉCURISÉES
CREATE TABLE IF NOT EXISTS user_sessions (
    id VARCHAR(128) PRIMARY KEY,
    user_id INT NOT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (user_id) REFERENCES users(id),
    INDEX idx_user_active (user_id, is_active),
    INDEX idx_expires (expires_at)
);

-- TENTATIVES DE CONNEXION
CREATE TABLE IF NOT EXISTS login_attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(255),
    ip_address VARCHAR(45),
    success BOOLEAN DEFAULT FALSE,
    failure_reason VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_ip_date (ip_address, created_at),
    INDEX idx_username_date (username, created_at)
);

-- SAUVEGARDES
CREATE TABLE IF NOT EXISTS backup_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    backup_type ENUM('full', 'incremental', 'differential') NOT NULL,
    file_path VARCHAR(500),
    file_size BIGINT,
    status ENUM('started', 'completed', 'failed') NOT NULL,
    start_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    end_time TIMESTAMP NULL,
    error_message TEXT NULL,
    INDEX idx_date_status (start_time, status)
);

-- CHIFFREMENT DES DONNÉES SENSIBLES
CREATE TABLE IF NOT EXISTS encrypted_data (
    id INT AUTO_INCREMENT PRIMARY KEY,
    table_name VARCHAR(50) NOT NULL,
    record_id INT NOT NULL,
    field_name VARCHAR(50) NOT NULL,
    encrypted_value TEXT NOT NULL,
    encryption_method VARCHAR(20) DEFAULT 'AES-256',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_field (table_name, record_id, field_name)
);

-- Ajouter colonnes de sécurité aux utilisateurs
ALTER TABLE users 
ADD COLUMN IF NOT EXISTS failed_login_attempts INT DEFAULT 0,
ADD COLUMN IF NOT EXISTS locked_until TIMESTAMP NULL,
ADD COLUMN IF NOT EXISTS password_changed_at TIMESTAMP NULL,
ADD COLUMN IF NOT EXISTS require_2fa BOOLEAN DEFAULT FALSE;