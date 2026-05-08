-- ============================================================================
-- Migration : Table surveillance_intensive_obs
-- Stockage des observations de la fiche de surveillance intensive
-- ============================================================================

CREATE TABLE IF NOT EXISTS `surveillance_intensive_obs` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `patient_id` INT NOT NULL,
  `date_obs` VARCHAR(20) DEFAULT NULL,
  `heure_obs` VARCHAR(10) DEFAULT NULL,
  `ta` VARCHAR(20) DEFAULT NULL,
  `pouls` SMALLINT DEFAULT NULL,
  `temperature` DECIMAL(4,1) DEFAULT NULL,
  `respiration` SMALLINT DEFAULT NULL,
  `diurese` VARCHAR(50) DEFAULT NULL,
  `conscience` VARCHAR(20) DEFAULT NULL,
  `aspiration` VARCHAR(100) DEFAULT NULL,
  `soins` TEXT DEFAULT NULL,
  `observations` TEXT DEFAULT NULL,
  `staff` VARCHAR(50) DEFAULT NULL,
  `diag` TEXT DEFAULT NULL,
  `infirmier_id` INT DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_si_patient` (`patient_id`),
  CONSTRAINT `fk_si_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_si_infirmier` FOREIGN KEY (`infirmier_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
