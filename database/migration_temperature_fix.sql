-- ============================================================================
-- MIGRATION : Élargissement de la colonne temperature
-- Problème   : decimal(4,1) → accepte 1 décimale max (ex: 37.5)
--              Si l'utilisateur saisit 37.25, MySQL tronque → warning 1265
-- Solution   : passer à decimal(5,2) → 2 décimales (ex: 37.25)
-- Tables concernées : patient_parametres, parametres_vitaux, consultations
-- ============================================================================

-- 1. Table principale des paramètres infirmiers
ALTER TABLE patient_parametres
  MODIFY COLUMN temperature DECIMAL(5,2) DEFAULT NULL;

-- 2. Table des paramètres vitaux (si elle existe avec decimal(4,1))
ALTER TABLE parametres_vitaux
  MODIFY COLUMN temperature DECIMAL(5,2) DEFAULT NULL;

-- 3. Table des consultations (colonne ep_temperature)
ALTER TABLE consultations
  MODIFY COLUMN ep_temperature DECIMAL(5,2) DEFAULT NULL;

-- Vérification
SELECT TABLE_NAME, COLUMN_NAME, COLUMN_TYPE
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND COLUMN_NAME LIKE '%temperature%'
ORDER BY TABLE_NAME;
