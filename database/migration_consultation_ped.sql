-- ============================================================================
-- Migration : Consultation Pédiatrique (Nourrisson et Enfant)
-- ============================================================================

CREATE TABLE IF NOT EXISTS `consultations_pediatriques` (
    `id`                        INT AUTO_INCREMENT PRIMARY KEY,
    `patient_id`                INT NOT NULL,
    `medecin_id`                INT NOT NULL,
    `service_id`                INT,
    `date_consultation`         DATE NOT NULL,
    `heure_consultation`        TIME,
    `statut`                    ENUM('en_cours','termine','annule') NOT NULL DEFAULT 'en_cours',

    -- Identification complémentaire (spécifique pédiatrie)
    `age_annees`                INT DEFAULT NULL,
    `age_mois`                  INT DEFAULT NULL,
    `ethnie`                    VARCHAR(100) DEFAULT NULL,
    `religion`                  VARCHAR(100) DEFAULT NULL,
    `contact_parents`           VARCHAR(255) DEFAULT NULL,

    -- Motif & HMA
    `motif_consultation`        TEXT,
    `hma`                       TEXT,

    -- ATCDS Personnels Médicaux
    `atcds_hospitalisations`    TEXT,
    `atcds_pathologies_chroniques` JSON COMMENT 'Épilepsie, Drépanocytose, G6PD, etc.',
    `atcds_pathologies_autres`  TEXT,
    `atcds_chirurgicaux`        TEXT,

    -- Immuno-allergiques
    `atcds_transfusions`        TEXT,
    `atcds_gsrh`                TEXT,
    `atcds_electrophorese`      TEXT,
    `atcds_allergies`           TEXT,
    `atcds_vaccinations`        TEXT,
    `atcds_immunoallerg_autres` TEXT,

    -- Environnementaux
    `env_milda`                 TINYINT(1) DEFAULT 0,
    `env_deparasitage`          TINYINT(1) DEFAULT 0,
    `env_eau_boisson`           TEXT,
    `env_autres`                TEXT,

    -- Alimentation
    `alim_0_6_mois`             TEXT,
    `alim_6_24_mois`            TEXT,
    `alim_actuelle`             TEXT,
    `alim_autres`               TEXT,

    -- Développement psychomoteur (âge en mois)
    `dev_tenue_tete`            INT DEFAULT NULL,
    `dev_assis_sans_soutien`    INT DEFAULT NULL,
    `dev_ramper`                INT DEFAULT NULL,
    `dev_debout_sans_soutien`   INT DEFAULT NULL,
    `dev_marche_autonome`       INT DEFAULT NULL,
    `dev_motricite_autres`      TEXT,
    `dev_fine_regard`           INT DEFAULT NULL,
    `dev_fine_autres`           TEXT,
    `dev_social_visage_mere`    INT DEFAULT NULL,
    `dev_social_reconnait`      INT DEFAULT NULL,
    `dev_social_fuit_etranger`  INT DEFAULT NULL,
    `dev_social_autres`         TEXT,
    `dev_lang_gazouillis`       INT DEFAULT NULL,
    `dev_lang_babillage_mono`   INT DEFAULT NULL,
    `dev_lang_babillage_bi`     INT DEFAULT NULL,
    `dev_lang_phrases`          INT DEFAULT NULL,
    `dev_lang_autres`           TEXT,
    `dev_scolarise`             TEXT,

    -- Antécédents familiaux
    `fam_mere_age`              INT DEFAULT NULL,
    `fam_mere_profession`       TEXT,
    `fam_mere_pathologies`      TEXT,
    `fam_mere_autres`           TEXT,
    `fam_pere_age`              INT DEFAULT NULL,
    `fam_pere_profession`       TEXT,
    `fam_pere_pathologies`      TEXT,
    `fam_pere_autres`           TEXT,
    `fam_fratrie_rang`          VARCHAR(50) DEFAULT NULL,
    `fam_fratrie_sante`         TEXT,
    `fam_fratrie_autres`        TEXT,

    -- Enquête des systèmes (JSON d'arrays de symptômes cochés)
    `es_general`                JSON,
    `es_snc`                    JSON,
    `es_cardio`                 JSON,
    `es_respiratoire`           JSON,
    `es_digestif`               JSON,
    `es_urogenital`             JSON,
    `es_locomoteur`             JSON,

    -- Examen physique
    `ep_etat_general`           TEXT,
    `ep_conscience`             VARCHAR(50) DEFAULT NULL,
    `ep_temperature`            DECIMAL(4,1) DEFAULT NULL,
    `ep_fr`                     INT DEFAULT NULL,
    `ep_spo2`                   DECIMAL(5,2) DEFAULT NULL,
    `ep_fc`                     INT DEFAULT NULL,
    `ep_ta`                     VARCHAR(20) DEFAULT NULL,
    `ep_trc`                    VARCHAR(20) DEFAULT NULL,
    `ep_poids`                  DECIMAL(5,2) DEFAULT NULL,
    `ep_taille`                 DECIMAL(5,1) DEFAULT NULL,
    `ep_pb`                     DECIMAL(5,1) DEFAULT NULL,
    `ep_pc`                     DECIMAL(5,1) DEFAULT NULL,
    `ep_imc`                    DECIMAL(5,2) DEFAULT NULL,
    `ep_parametres_autres`      TEXT,
    -- Examen topographique (JSON par région)
    `ep_tete_cou`               JSON,
    `ep_thorax`                 JSON,
    `ep_abdomen`                JSON,
    `ep_oge`                    TEXT,
    `ep_tr`                     JSON,
    `ep_membres`                JSON,
    `ep_neuro`                  JSON,

    -- Résumé & Hypothèses
    `resume_syndromique`        TEXT,
    `diag_positif`              TEXT,
    `diag_differentiels`        TEXT,

    -- Bilan paraclinique
    `bilan_diagnostique`        JSON,
    `bilan_etiologique`         JSON,
    `bilan_retentissement`      JSON,
    `bilan_terrain`             JSON,

    -- Prise en charge
    `pec_buts`                  TEXT,
    `pec_non_pharmacologique`   TEXT,
    `pec_pharmacologique`       TEXT,
    `pec_orthopedique`          TEXT,
    `pec_chirurgical`           TEXT,

    -- Surveillance
    `surveillance`              JSON,
    `surveillance_autres`       TEXT,

    `created_at`                TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at`                TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX `idx_patient` (`patient_id`),
    INDEX `idx_medecin` (`medecin_id`),
    INDEX `idx_date` (`date_consultation`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
