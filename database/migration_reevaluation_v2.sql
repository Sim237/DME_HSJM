-- =============================================================================
-- MIGRATION v2 : Ajout colonne traitement_non_medicamenteux
-- Exécuter dans phpMyAdmin sur la base dme_hospital
-- =============================================================================

ALTER TABLE `reevaluations_hospitalisees`
  ADD COLUMN `traitement_non_medicamenteux` TEXT NULL
  AFTER `conduite_tenir`;
