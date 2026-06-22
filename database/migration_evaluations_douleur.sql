-- ============================================================================
-- MIGRATION : Table evaluations_douleur
-- Évaluations obligatoires de la douleur (échelles HAS 2022) par l'infirmier
-- à chaque séance d'exécution des soins
-- ============================================================================

CREATE TABLE IF NOT EXISTS evaluations_douleur (
    id                  INT             NOT NULL AUTO_INCREMENT PRIMARY KEY,
    patient_id          INT             NOT NULL,
    admission_id        INT             NULL,
    infirmier_id        INT             NOT NULL,

    -- Profil du patient
    profil              ENUM('ADULTE_COMM','ADULTE_NON_COMM','PEDIATRIQUE') NOT NULL,

    -- Échelle utilisée (HAS 2022)
    echelle             VARCHAR(20)     NOT NULL
                        COMMENT 'EVN, EN, EVA, EVS, ALGOPLUS, DOLOPLUS2, BPS_NI, EVENDOL, FLACC, FPS_R',

    -- Score calculé / saisi
    score               DECIMAL(5,1)    NOT NULL,
    score_max           DECIMAL(5,1)    NOT NULL,

    -- Niveau de sévérité calculé
    severite            ENUM('ABSENT','LEGERE','MODEREE','INTENSE')  NOT NULL,

    -- Détail des items (JSON) — pour ALGOPLUS, FLACC, EVENDOL
    items_json          TEXT            NULL,

    -- Contexte clinique
    localisation        VARCHAR(200)    NULL,
    caracteristiques    VARCHAR(200)    NULL COMMENT 'Ex: brûlure, élancement, oppression',
    action_prise        TEXT            NULL COMMENT 'Antalgique donné, repositionnement…',
    note_infirmier      TEXT            NULL,

    -- Contexte temporel
    contexte            ENUM('AVANT_SOIN','PENDANT_SOIN','APRES_SOIN') NOT NULL DEFAULT 'AVANT_SOIN',
    date_evaluation     DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,

    KEY idx_patient    (patient_id),
    KEY idx_admission  (admission_id),
    KEY idx_infirmier  (infirmier_id),
    KEY idx_date       (date_evaluation),

    CONSTRAINT fk_ed_patient   FOREIGN KEY (patient_id)  REFERENCES patients(id)    ON DELETE CASCADE,
    CONSTRAINT fk_ed_infirmier FOREIGN KEY (infirmier_id) REFERENCES users(id)       ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Évaluations de la douleur obligatoires — échelles HAS 2022';

-- Vérification
DESCRIBE evaluations_douleur;
