-- ============================================================================
-- SECTION 2 CORRIGÉE : Suppression colonnes redondantes
-- Compatible MySQL 8.4 (pas de DROP COLUMN IF EXISTS)
-- ============================================================================

SET FOREIGN_KEY_CHECKS = 0;

-- ─────────────────────────────────────────────────────────────────────────────
-- 2.1 TABLE lits : supprimer patient_id (doublon de occupied_by_patient_id)
-- ─────────────────────────────────────────────────────────────────────────────
-- Synchroniser d'abord
UPDATE `lits`
  SET `occupied_by_patient_id` = `patient_id`
  WHERE `occupied_by_patient_id` IS NULL AND `patient_id` IS NOT NULL;

ALTER TABLE `lits` DROP COLUMN `patient_id`;

-- ─────────────────────────────────────────────────────────────────────────────
-- 2.2 TABLE users : supprimer date_creation (doublon) et statut (doublon actif)
-- ─────────────────────────────────────────────────────────────────────────────
-- Synchroniser created_at
UPDATE `users`
  SET `created_at` = `date_creation`
  WHERE `created_at` IS NULL AND `date_creation` IS NOT NULL;

-- Synchroniser actif depuis statut
UPDATE `users`
  SET `actif` = IF(`statut` = 'ACTIF' OR `statut` IS NULL OR `statut` = '', 1, 0);

ALTER TABLE `users` DROP COLUMN `date_creation`;
ALTER TABLE `users` DROP COLUMN `statut`;

-- ─────────────────────────────────────────────────────────────────────────────
-- 2.3 TABLE patients : supprimer date_creation et allergie_connue
-- ─────────────────────────────────────────────────────────────────────────────
UPDATE `patients`
  SET `created_at` = `date_creation`
  WHERE `created_at` IS NULL AND `date_creation` IS NOT NULL;

ALTER TABLE `patients` DROP COLUMN `date_creation`;

-- Fusionner allergie_connue dans allergies
UPDATE `patients`
  SET `allergies` = `allergie_connue`
  WHERE (`allergies` IS NULL OR `allergies` = '')
    AND `allergie_connue` IS NOT NULL
    AND `allergie_connue` != '';

ALTER TABLE `patients` DROP COLUMN `allergie_connue`;

-- ─────────────────────────────────────────────────────────────────────────────
-- 2.4 TABLE consultations : colonnes dupliquées
-- ─────────────────────────────────────────────────────────────────────────────
-- Sauvegarder tension_arterielle dans les champs sys/dia si vides
UPDATE `consultations`
  SET `tension_systolique` = SUBSTRING_INDEX(`tension_arterielle`, '/', 1) + 0,
      `tension_diastolique` = SUBSTRING_INDEX(`tension_arterielle`, '/', -1) + 0
  WHERE (`tension_systolique` IS NULL OR `tension_systolique` = 0)
    AND `tension_arterielle` LIKE '%/%';

ALTER TABLE `consultations` DROP COLUMN `tension_arterielle`;

-- Synchroniser updated_at depuis date_modification
UPDATE `consultations`
  SET `updated_at` = `date_modification`
  WHERE `updated_at` IS NULL AND `date_modification` IS NOT NULL;

ALTER TABLE `consultations` DROP COLUMN `date_modification`;

-- Synchroniser type_consultation depuis type
UPDATE `consultations`
  SET `type_consultation` = `type`
  WHERE (`type_consultation` IS NULL OR `type_consultation` = '')
    AND `type` IS NOT NULL AND `type` != '';

ALTER TABLE `consultations` DROP COLUMN `type`;

-- ─────────────────────────────────────────────────────────────────────────────
-- 2.5 TABLE parametres_vitaux : supprimer les alias dupliqués de tension
-- ─────────────────────────────────────────────────────────────────────────────
-- Synchroniser vers les colonnes principales
UPDATE `parametres_vitaux`
  SET `pression_arterielle_systolique` = `tension_sys`
  WHERE (`pression_arterielle_systolique` IS NULL OR `pression_arterielle_systolique` = 0)
    AND `tension_sys` IS NOT NULL AND `tension_sys` > 0;

UPDATE `parametres_vitaux`
  SET `pression_arterielle_diastolique` = `tension_dia`
  WHERE (`pression_arterielle_diastolique` IS NULL OR `pression_arterielle_diastolique` = 0)
    AND `tension_dia` IS NOT NULL AND `tension_dia` > 0;

ALTER TABLE `parametres_vitaux` DROP COLUMN `tension_sys`;
ALTER TABLE `parametres_vitaux` DROP COLUMN `tension_dia`;

SET FOREIGN_KEY_CHECKS = 1;

SELECT 'Section 2 terminée.' AS resultat;
