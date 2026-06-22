-- =============================================================================
-- MIGRATION : Réévaluation Médicale des Patients Hospitalisés (Ronde Médicale)
-- Exécuter dans phpMyAdmin sur la base dme_hospital
-- =============================================================================

-- 1. Table principale des réévaluations journalières
CREATE TABLE IF NOT EXISTS `reevaluations_hospitalisees` (
  `id`                     INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `hospitalisation_id`     INT NOT NULL,
  `patient_id`             INT NOT NULL,
  `medecin_id`             INT NOT NULL,
  `date_reevaluation`      DATE NOT NULL,
  `heure_reevaluation`     TIME NOT NULL,

  -- 1. Plaintes du jour
  `plaintes_jour`          TEXT NULL,

  -- 2. Histoire de la maladie (optionnel)
  `histoire_maladie`       TEXT NULL,

  -- 3. Examen physique par systèmes
  `examen_general`         TEXT NULL,
  `examen_cardiovasculaire` TEXT NULL,
  `examen_respiratoire`    TEXT NULL,
  `examen_digestif`        TEXT NULL,
  `examen_neurologique`    TEXT NULL,
  `examen_osteoarticulaire` TEXT NULL,
  `examen_autres`          TEXT NULL,

  -- 4. Évolution globale de l'état du patient
  `evolution_globale`      ENUM(
    'FAVORABLE',
    'AMELIORATION_PARTIELLE',
    'STATUO_QUO',
    'NON_FAVORABLE',
    'AGGRAVATION',
    'CRITIQUE'
  ) NOT NULL DEFAULT 'STATUO_QUO',
  `note_evolution`         TEXT NULL,

  -- 5. Diagnostic du jour
  `diagnostic_jour`        VARCHAR(500) NULL,
  `code_cim10`             VARCHAR(20) NULL,

  -- 6. Conduite à tenir
  `conduite_tenir`         TEXT NULL,

  -- Lien vers l'ordonnance formelle générée (nullable)
  `prescription_id`        INT NULL,

  `created_at`             TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

  INDEX `idx_patient`  (`patient_id`),
  INDEX `idx_hosp`     (`hospitalisation_id`),
  INDEX `idx_date`     (`date_reevaluation`),
  INDEX `idx_medecin`  (`medecin_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Réévaluations médicales quotidiennes des patients hospitalisés';


-- 2. Médicaments prescrits lors de la réévaluation
CREATE TABLE IF NOT EXISTS `reevaluation_medicaments` (
  `id`                 INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `reevaluation_id`    INT UNSIGNED NOT NULL,
  `medicament_id`      INT NULL COMMENT 'FK vers medicaments si trouvé par autocomplete',
  `nom_medicament`     VARCHAR(200) NOT NULL,
  `posologie`          VARCHAR(200) NULL,
  `voie_administration` VARCHAR(50) NULL,
  `frequence`          VARCHAR(100) NULL,
  `duree`              VARCHAR(50) NULL,
  `quantite`           INT DEFAULT 1,
  `notes`              TEXT NULL,

  FOREIGN KEY (`reevaluation_id`) REFERENCES `reevaluations_hospitalisees`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- 3. Bilans demandés lors de la réévaluation
CREATE TABLE IF NOT EXISTS `reevaluation_bilans` (
  `id`                 INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `reevaluation_id`    INT UNSIGNED NOT NULL,
  `type`               ENUM('LABO','IMAGERIE') NOT NULL DEFAULT 'LABO',
  `intitule`           VARCHAR(300) NOT NULL,
  `urgence`            TINYINT(1) DEFAULT 0,
  `note`               TEXT NULL,
  `demande_id`         INT NULL COMMENT 'ID dans demandes_laboratoire ou demandes_imagerie',

  FOREIGN KEY (`reevaluation_id`) REFERENCES `reevaluations_hospitalisees`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
