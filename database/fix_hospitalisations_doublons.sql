-- ============================================================
-- Fix : supprimer les hospitalisations provisoires en doublon
-- À exécuter UNE SEULE FOIS dans MySQL
-- ============================================================

-- 1. Supprimer les hospitalisations provisoires (lit_id IS NULL)
--    quand le même patient a DÉJÀ une hospitalisation avec un lit assigné
DELETE FROM hospitalisations
WHERE statut = 'en_cours'
  AND lit_id IS NULL
  AND patient_id IN (
      SELECT patient_id FROM (
          SELECT patient_id FROM hospitalisations
          WHERE statut = 'en_cours' AND lit_id IS NOT NULL
      ) AS tmp
  );

-- 2. Vérification : patients avec encore plusieurs hospitalisations actives
-- SELECT patient_id, COUNT(*) as nb
-- FROM hospitalisations WHERE statut = 'en_cours'
-- GROUP BY patient_id HAVING nb > 1;
