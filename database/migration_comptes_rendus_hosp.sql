-- ============================================================================
-- MIGRATION : Table des comptes-rendus d'hospitalisation (CRH)
-- ============================================================================

CREATE TABLE IF NOT EXISTS `comptes_rendus_hosp` (
  `id`               INT NOT NULL AUTO_INCREMENT,
  `patient_id`       INT NOT NULL,
  `hospitalisation_id` INT DEFAULT NULL,
  `medecin_id`       INT NOT NULL,
  `date_entree`      DATE DEFAULT NULL,
  `diag_entree`      TEXT DEFAULT NULL,
  `evolution`        LONGTEXT DEFAULT NULL,
  `date_sortie`      DATE DEFAULT NULL,
  `diag_sortie`      TEXT DEFAULT NULL,
  `traitement_sortie` TEXT DEFAULT NULL,
  `rendez_vous`      VARCHAR(255) DEFAULT NULL,
  `date_signature`   DATE DEFAULT NULL,
  `signe`            TINYINT(1) NOT NULL DEFAULT 0,
  `signature_data`   MEDIUMTEXT DEFAULT NULL,
  `created_at`       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_crh_patient` (`patient_id`),
  KEY `idx_crh_medecin` (`medecin_id`),
  KEY `idx_crh_hospitalisation` (`hospitalisation_id`),
  CONSTRAINT `fk_crh_patient`  FOREIGN KEY (`patient_id`)       REFERENCES `patients`(`id`)        ON DELETE CASCADE,
  CONSTRAINT `fk_crh_medecin`  FOREIGN KEY (`medecin_id`)       REFERENCES `users`(`id`),
  CONSTRAINT `fk_crh_hosp`     FOREIGN KEY (`hospitalisation_id`) REFERENCES `hospitalisations`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
