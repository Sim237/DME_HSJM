-- Migration : ajouter le statut de délivrance par ligne d'ordonnance
-- Permet au pharmacien de marquer chaque médicament comme délivré ou non délivré
-- (utile quand un médicament n'est pas en stock ou que le patient renonce à un)

ALTER TABLE ordonnance_medicaments
  ADD COLUMN delivre TINYINT(1) DEFAULT NULL
    COMMENT 'NULL = non traité, 1 = délivré au patient, 0 = non délivré'
  AFTER disponible;
