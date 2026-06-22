-- ============================================================
-- MIGRATION : Ajout colonne conclusion_specialiste
-- Table : rdv_specialistes
-- Date  : 2026-04-14
-- ============================================================

USE dme_hospital;

ALTER TABLE rdv_specialistes ADD COLUMN conclusion_specialiste TEXT NULL AFTER notes_secretaire;

-- Vérification
SELECT 'Colonne conclusion_specialiste ajoutée' AS info;
DESCRIBE rdv_specialistes;
