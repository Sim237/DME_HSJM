-- DME Hospital Enhancements v2: MySQL 5.7+ Compatible (No IF NOT EXISTS)
-- Run: mysql -u root -p"" dme_hospital < database/migrations_hospital_enhancements_v2.sql
-- Ignore "duplicate column" errors if already run

USE dme_hospital;

-- 1. consultations table
ALTER TABLE consultations
ADD COLUMN wait_hospital_until TIMESTAMP NULL COMMENT '1h TTL for doctor hospitaliser btn',
ADD INDEX idx_wait_hospital (wait_hospital_until);

-- 2. urgences_admissions table
ALTER TABLE urgences_admissions
ADD COLUMN a_hospitaliser TINYINT(1) DEFAULT 0 COMMENT 'Flashing queue for nurse',
ADD INDEX idx_a_hospitaliser (a_hospitaliser);

-- 3. notifications_medecin table
ALTER TABLE notifications_medecin
CHANGE COLUMN type type VARCHAR(50) DEFAULT 'INFO' COMMENT 'INFO|RESULTATS_LABO|HOSPITALISER|URGENT';

-- 4. lits table (bed management)
ALTER TABLE lits
ADD COLUMN service_id INT NULL,
ADD COLUMN occupied_by_patient_id INT NULL,
ADD COLUMN occupied_since TIMESTAMP NULL,
ADD INDEX idx_lits_service (service_id),
ADD INDEX idx_lits_occupied (occupied_by_patient_id);

-- 5. hospitalisations table
ALTER TABLE hospitalisations
ADD COLUMN from_urgences TINYINT(1) DEFAULT 0 COMMENT 'Quick admission from urgences';

-- Verify (run separately)
-- DESCRIBE consultations;
-- DESCRIBE lits;
-- SHOW INDEX FROM consultations WHERE Key_name = 'idx_wait_hospital';
