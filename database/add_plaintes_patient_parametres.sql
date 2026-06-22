-- Migration : Ajout de la colonne `plaintes` dans patient_parametres
-- Permet de consigner les plaintes du malade à chaque prise de constantes
-- À exécuter une seule fois sur la base de données

ALTER TABLE `patient_parametres`
  ADD COLUMN `plaintes` TEXT DEFAULT NULL COMMENT 'Plaintes exprimées par le patient lors de la prise de constantes'
  AFTER `observations`;
