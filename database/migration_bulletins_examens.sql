-- Migration: Création de la table bulletins_examens
-- Date: 2024
-- Description: Table pour stocker les bulletins d'examens médicaux

CREATE TABLE IF NOT EXISTS `bulletins_examens` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `consultation_id` INT(11) NOT NULL,
    `patient_id` INT(11) NOT NULL,
    `medecin_id` INT(11) NOT NULL,
    `type` ENUM('laboratoire', 'imagerie') NOT NULL,
    `radio` BOOLEAN DEFAULT FALSE,
    `echo` BOOLEAN DEFAULT FALSE,
    `labo` BOOLEAN DEFAULT FALSE,
    `profession` VARCHAR(100) DEFAULT NULL,
    `service` VARCHAR(100) DEFAULT 'Consultation Extr.',
    `chambre` VARCHAR(50) DEFAULT NULL,
    `lit` VARCHAR(50) DEFAULT NULL,
    `renseignements` TEXT,
    `resultats` TEXT,
    `examens` TEXT NOT NULL, -- JSON array des examens demandés
    `date_creation` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `date_signature` DATETIME NULL,
    `signe` BOOLEAN DEFAULT FALSE,
    `signature_data` TEXT, -- Données de signature numérique si applicable
    PRIMARY KEY (`id`),
    KEY `consultation_id` (`consultation_id`),
    KEY `patient_id` (`patient_id`),
    KEY `medecin_id` (`medecin_id`),
    CONSTRAINT `fk_bulletins_examens_consultation` FOREIGN KEY (`consultation_id`) REFERENCES `consultations` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_bulletins_examens_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_bulletins_examens_medecin` FOREIGN KEY (`medecin_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Index pour améliorer les performances
CREATE INDEX idx_bulletins_examens_type ON bulletins_examens(type);
CREATE INDEX idx_bulletins_examens_date_creation ON bulletins_examens(date_creation);
CREATE INDEX idx_bulletins_examens_signe ON bulletins_examens(signe);