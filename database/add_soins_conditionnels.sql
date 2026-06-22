-- ============================================================
-- Soins conditionnels : planification selon paramètres vitaux
-- ============================================================

ALTER TABLE soins_hospitalisation
  ADD COLUMN IF NOT EXISTS parametre_surveille    VARCHAR(50)   DEFAULT NULL
      COMMENT 'Ex: temperature, pouls, saturation_o2, tension_sys, glycemie',
  ADD COLUMN IF NOT EXISTS valeur_cible           DECIMAL(7,2)  DEFAULT NULL
      COMMENT 'Valeur seuil du paramètre',
  ADD COLUMN IF NOT EXISTS operateur_condition    VARCHAR(5)    DEFAULT NULL
      COMMENT 'Opérateur de comparaison : <=, >=, =, <, >',
  ADD COLUMN IF NOT EXISTS action_si_atteint      VARCHAR(20)   DEFAULT NULL
      COMMENT 'STOPPER ou CHANGER',
  ADD COLUMN IF NOT EXISTS nouveau_traitement     TEXT          DEFAULT NULL
      COMMENT 'Description du nouveau traitement si action = CHANGER',
  ADD COLUMN IF NOT EXISTS frequence_surveillance_h INT         DEFAULT NULL
      COMMENT 'Fréquence de mesure du paramètre (heures)',
  ADD COLUMN IF NOT EXISTS condition_atteinte     TINYINT(1)    DEFAULT 0,
  ADD COLUMN IF NOT EXISTS date_condition_atteinte DATETIME     DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS note_execution         TEXT          DEFAULT NULL;

-- Ajouter STOPPE à l'enum statut si pas déjà présent
-- (MySQL ne supporte pas IF NOT EXISTS sur MODIFY COLUMN, on force la valeur complète)
ALTER TABLE soins_hospitalisation
  MODIFY COLUMN statut
    ENUM('PLANIFIE','REALISE','ANNULE','RETARD','SUPPRIME','STOPPE','REMPLACE')
    DEFAULT 'PLANIFIE';
