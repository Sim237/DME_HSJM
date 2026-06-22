-- ============================================================
-- MIGRATION : Champs supplémentaires pour Agents PHP
-- Date  : 2026-04-16
-- ============================================================
USE dme_hospital;

ALTER TABLE patients
  ADD COLUMN matricule_agent    VARCHAR(50)  NULL AFTER numero_assure,
  ADD COLUMN infirmerie_origine VARCHAR(100) NULL AFTER matricule_agent;

-- Vérification
SELECT 'Colonnes matricule_agent et infirmerie_origine ajoutées' AS info;
DESCRIBE patients;
