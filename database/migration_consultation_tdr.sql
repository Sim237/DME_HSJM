-- Migration : ajout d'un champ TDR (Tests de Diagnostic Rapide)
-- réalisés sur place par l'infirmier consultant ou le médecin
-- (paludisme, glycémie, COVID, dengue, urinaire, etc.)
--
-- Format : JSON contenant un tableau d'objets, ex:
-- [
--   {"type":"PALUDISME","resultat":"NEGATIF","valeur":"","heure":"08:30","note":""},
--   {"type":"GLYCEMIE","resultat":"VALEUR","valeur":"1.45 g/L","heure":"08:35","note":"À jeun"}
-- ]

ALTER TABLE consultations
  ADD COLUMN tests_tdr TEXT NULL
    COMMENT 'JSON des Tests Diagnostic Rapide réalisés sur place'
    AFTER examens_paracliniques;
