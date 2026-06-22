-- ============================================================
-- MIGRATION : Modification des lignes d'ordonnance par le pharmacien
-- Date  : 2026-04-16
-- ============================================================
USE dme_hospital;

ALTER TABLE ordonnance_medicaments
  ADD COLUMN ligne_annulee      TINYINT(1) DEFAULT 0 AFTER disponible,
  ADD COLUMN remplace_ligne_id  INT NULL              AFTER ligne_annulee,
  ADD COLUMN note_pharmacien    TEXT NULL             AFTER remplace_ligne_id;

-- Vérification
SELECT 'Colonnes ligne_annulee, remplace_ligne_id, note_pharmacien ajoutées' AS info;
DESCRIBE ordonnance_medicaments;
