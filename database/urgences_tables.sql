-- Urgences Tables Schema
-- Fixes missing urgences_triage table with gcs_total column
-- Run: mysql -u root -p dme_hospital < database/urgences_tables.sql

USE dme_hospital;

-- Table: urgences_admissions (if not exists, inferred from code)
CREATE TABLE IF NOT EXISTS `urgences_admissions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `patient_id` int(11) NOT NULL,
  `mode_arrivee` varchar(50) DEFAULT NULL,
  `niveau_priorite` varchar(10) DEFAULT NULL,
  `statut_actuel` varchar(50) DEFAULT 'EN_ATTENTE_TRI',
  `infirmier_id` int(11) DEFAULT NULL,
  `medecin_id` int(11) DEFAULT NULL,
  `box_id` int(11) DEFAULT NULL,
  `date_entree` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `date_sortie` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `patient_id` (`patient_id`),
  KEY `infirmier_id` (`infirmier_id`),
  KEY `medecin_id` (`medecin_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table: urgences_triage (MAIN FIX - missing gcs_total)
CREATE TABLE IF NOT EXISTS `urgences_triage` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `admission_id` int(11) NOT NULL,
  `gcs_total` int(11) DEFAULT 15 COMMENT 'Glasgow Coma Scale Total (3-15)',
  `tension_sys` int(11) DEFAULT NULL,
  `tension_dia` int(11) DEFAULT NULL,
  `pouls` int(11) DEFAULT NULL,
  `spo2` decimal(5,2) DEFAULT NULL,
  `temp` decimal(4,1) DEFAULT NULL,
  `motif_plainte` text,
  `niveau_gravite` varchar(20) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `admission_id` (`admission_id`),
  KEY `gcs_total` (`gcs_total`),
  CONSTRAINT `urgences_triage_ibfk_1` FOREIGN KEY (`admission_id`) REFERENCES `urgences_admissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert sample data for testing (optional)
INSERT IGNORE INTO `urgences_admissions` (`patient_id`, `mode_arrivee`, `niveau_priorite`) VALUES (1, 'AMBULANCE', 'P2');

-- Verify tables/columns
-- SHOW TABLES LIKE 'urgences_%';
-- DESCRIBE urgences_triage;
-- SELECT * FROM urgences_triage LIMIT 5;
