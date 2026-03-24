-- DME Hospital Enhancements: DB Schema Updates for Doctor/Nurse/Urgences
-- Run: mysql -u root -p"" dme_hospital < database/migrations_hospital_enhancements.sql

USE dme_hospital;

-- 1. consultations: Hospitaliser 1h TTL flag
ALTER TABLE consultations
ADD COLUMN IF NOT EXISTS wait_hospital_until TIMESTAMP NULL COMMENT '1h TTL for doctor hospitaliser btn',
ADD INDEX idx_wait_hospital (wait_hospital_until);

-- 2. urgences_admissions: Nurse queue flag
ALTER TABLE urgences_admissions
ADD COLUMN IF NOT EXISTS a_hospitaliser TINYINT(1) DEFAULT 0 COMMENT 'Flashing queue for nurse',
ADD INDEX idx_a_hospitaliser (a_hospitaliser);

-- 3. notifications_medecin: Extend types for HOSPITALISER
ALTER TABLE notifications_medecin
MODIFY COLUMN type VARCHAR(50) DEFAULT 'INFO' COMMENT 'INFO|RESULTATS_LABO|HOSPITALISER|URGENT';

-- 4. lits: Service/lits management for nurse
ALTER TABLE lits
ADD COLUMN IF NOT EXISTS service_id INT NULL COMMENT 'Service foreign key',
ADD COLUMN IF NOT EXISTS occupied_by_patient_id INT NULL COMMENT 'Current patient',
ADD COLUMN IF NOT EXISTS occupied_since TIMESTAMP NULL COMMENT 'Admission time to lit',
ADD INDEX idx_service (service_id),
ADD INDEX idx_occupied_patient (occupied_by_patient_id),
ADD FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE SET NULL;

-- 5. hospitalisations: Track urgences origin
ALTER TABLE hospitalisations
ADD COLUMN IF NOT EXISTS from_urgences TINYINT(1) DEFAULT 0 COMMENT 'Quick admission from urgences';

-- 6. Verify changes
-- SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME IN ('consultations','urgences_admissions','lits') AND COLUMN_NAME LIKE '%hospital%';
