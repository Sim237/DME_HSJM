-- Migration : ajout de nouveaux paramètres vitaux à la consultation
-- (fréquence respiratoire, TA bras gauche/droit, pouls, indicateur "TA non prenable")

ALTER TABLE consultations
  ADD COLUMN frequence_respiratoire INT NULL
    COMMENT 'Fréquence respiratoire (cycles/min)'
    AFTER frequence_cardiaque,
  ADD COLUMN ta_bras_gauche_systolique INT NULL
    COMMENT 'TA bras gauche systolique (mmHg)'
    AFTER frequence_respiratoire,
  ADD COLUMN ta_bras_gauche_diastolique INT NULL
    COMMENT 'TA bras gauche diastolique (mmHg)'
    AFTER ta_bras_gauche_systolique,
  ADD COLUMN ta_bras_droit_systolique INT NULL
    COMMENT 'TA bras droit systolique (mmHg)'
    AFTER ta_bras_gauche_diastolique,
  ADD COLUMN ta_bras_droit_diastolique INT NULL
    COMMENT 'TA bras droit diastolique (mmHg)'
    AFTER ta_bras_droit_systolique,
  ADD COLUMN pouls INT NULL
    COMMENT 'Pouls (bpm) - distinct de la frequence_cardiaque'
    AFTER ta_bras_droit_diastolique,
  ADD COLUMN tension_non_prenable TINYINT(1) DEFAULT 0
    COMMENT 'TA non prenable (TA non mesurable, patient agité, etc.)'
    AFTER pouls,
  ADD COLUMN motif_tension_non_prenable VARCHAR(255) NULL
    COMMENT 'Motif facultatif si TA non prenable'
    AFTER tension_non_prenable;
