-- ============================================================================
-- MIGRATION : Table patient_resultats_labo
-- Archive des résultats validés par le laborantin, consultable par le médecin
-- ============================================================================

CREATE TABLE IF NOT EXISTS patient_resultats_labo (
    id                      INT             NOT NULL AUTO_INCREMENT PRIMARY KEY,
    patient_id              INT             NOT NULL,
    demande_id              INT             NOT NULL,
    examen_id               INT             NULL,
    nom_examen              VARCHAR(200)    NOT NULL,
    resultat                TEXT            NULL        COMMENT 'Résultat textuel',
    valeur_numerique        DECIMAL(10,3)   NULL        COMMENT 'Valeur chiffrée',
    unite                   VARCHAR(50)     NULL        COMMENT 'Unité de mesure',
    valeur_normale_min      DECIMAL(10,3)   NULL,
    valeur_normale_max      DECIMAL(10,3)   NULL,
    interpretation          TEXT            NULL,
    anormal                 TINYINT(1)      NOT NULL DEFAULT 0,
    date_resultat           DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    medecin_prescripteur_id INT             NULL,
    created_at              TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,

    KEY idx_patient   (patient_id),
    KEY idx_demande   (demande_id),
    KEY idx_medecin   (medecin_prescripteur_id),
    KEY idx_date      (date_resultat),

    CONSTRAINT fk_prl_patient  FOREIGN KEY (patient_id)  REFERENCES patients(id) ON DELETE CASCADE,
    CONSTRAINT fk_prl_demande  FOREIGN KEY (demande_id)  REFERENCES demandes_laboratoire(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Archive des résultats de laboratoire validés';

-- ── Vérification ──────────────────────────────────────────────────────────────
DESCRIBE patient_resultats_labo;
