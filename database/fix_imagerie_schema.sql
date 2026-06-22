-- Fix Imagerie Schema for 'type_examen' column error
-- Safe migration: Adds columns if missing, creates table if absent
-- Compatible with existing data

USE dme_hospital;

-- =====================================================
-- 1. SAFE ADD COLUMNS using Stored Procedure (MySQL compatible)
-- =====================================================
DELIMITER $$

CREATE PROCEDURE SafeAddImagerieColumns()
BEGIN
    DECLARE col_exists INT DEFAULT 0;

    -- Check and add type_examen
    SELECT COUNT(*) INTO col_exists
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'demandes_imagerie'
    AND COLUMN_NAME = 'type_examen';
    IF col_exists = 0 THEN
        ALTER TABLE demandes_imagerie
        ADD COLUMN type_examen ENUM('radiographie','scanner','irm','echographie','mammographie','autre') NOT NULL DEFAULT 'autre';
    END IF;

    -- Check and add partie_corps
    SELECT COUNT(*) INTO col_exists
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'demandes_imagerie'
    AND COLUMN_NAME = 'partie_corps';
    IF col_exists = 0 THEN
        ALTER TABLE demandes_imagerie
        ADD COLUMN partie_corps VARCHAR(100) NOT NULL DEFAULT '';
    END IF;

    -- Check and add description
    SELECT COUNT(*) INTO col_exists
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'demandes_imagerie'
    AND COLUMN_NAME = 'description';
    IF col_exists = 0 THEN
        ALTER TABLE demandes_imagerie
        ADD COLUMN description TEXT NULL;
    END IF;

    -- Check and add urgence
    SELECT COUNT(*) INTO col_exists
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'demandes_imagerie'
    AND COLUMN_NAME = 'urgence';
    IF col_exists = 0 THEN
        ALTER TABLE demandes_imagerie
        ADD COLUMN urgence ENUM('NORMAL','URGENT','TRES_URGENT') DEFAULT 'NORMAL';
    END IF;

    -- Check and add avec_contraste
    SELECT COUNT(*) INTO col_exists
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'demandes_imagerie'
    AND COLUMN_NAME = 'avec_contraste';
    IF col_exists = 0 THEN
        ALTER TABLE demandes_imagerie
        ADD COLUMN avec_contraste BOOLEAN DEFAULT FALSE;
    END IF;

    -- Check and add statut
    SELECT COUNT(*) INTO col_exists
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'demandes_imagerie'
    AND COLUMN_NAME = 'statut';
    IF col_exists = 0 THEN
        ALTER TABLE demandes_imagerie
        ADD COLUMN statut ENUM('EN_ATTENTE','en_cours','termine','interprete') DEFAULT 'EN_ATTENTE';
    END IF;

END$$

DELIMITER ;

-- Execute the procedure
CALL SafeAddImagerieColumns();

-- Cleanup
DROP PROCEDURE IF EXISTS SafeAddImagerieColumns;

-- =====================================================
-- 2. CREATE TABLE IF NOT EXISTS (Full schema backup)
-- =====================================================
CREATE TABLE IF NOT EXISTS demandes_imagerie (
    id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    consultation_id INT NULL,
    medecin_id INT NULL,
    type_examen ENUM('radiographie','scanner','irm','echographie','mammographie','autre') NOT NULL DEFAULT 'autre',
    partie_corps VARCHAR(100) NOT NULL,
    description TEXT NULL,
    urgence ENUM('NORMAL','URGENT','TRES_URGENT') DEFAULT 'NORMAL',
    avec_contraste BOOLEAN DEFAULT FALSE,
    statut ENUM('EN_ATTENTE','en_cours','termine','interprete') DEFAULT 'EN_ATTENTE',
    date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_patient_statut (patient_id, statut),
    INDEX idx_urgence (urgence),
    FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =====================================================
-- 3. VERIFY (Comment out after first run)
-- =====================================================
-- SELECT 'Migration completed successfully' as status;
-- DESCRIBE demandes_imagerie;
-- SHOW CREATE TABLE demandes_imagerie;

-- Success message (visible in mysql output)
SELECT '✅ Imagerie schema fixed! Column type_examen added/verified.' as Migration_Status;

