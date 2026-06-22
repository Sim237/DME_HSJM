-- =================================================================
-- Migration 003 : Colonnes et tables créées dynamiquement dans le code
-- À exécuter UNE SEULE FOIS sur la base de production
-- =================================================================

-- ── AccueilController ─────────────────────────────────────────────

ALTER TABLE patients
    ADD COLUMN IF NOT EXISTS femme_enceinte   TINYINT(1) NOT NULL DEFAULT 0  AFTER sexe,
    ADD COLUMN IF NOT EXISTS parametres_requis TINYINT(1) NOT NULL DEFAULT 0 AFTER statut_parcours;

-- Extension ENUM statut_parcours avec PARAMETRES_MATERNITE
-- (uniquement si la valeur n'existe pas déjà)
SET @col := (
    SELECT COLUMN_TYPE FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'patients'
      AND COLUMN_NAME  = 'statut_parcours'
);
SET @needsMig := IF(@col IS NOT NULL AND @col NOT LIKE '%PARAMETRES_MATERNITE%', 1, 0);
-- Note : MySQL ne supporte pas IF conditionnel pour ALTER, l'appliquer manuellement si besoin :
-- ALTER TABLE patients MODIFY COLUMN statut_parcours ENUM('ACCUEIL','PARAMETRES','PARAMETRES_MATERNITE','ATTENTE_CONSULTATION','EN_CONSULTATION','HOSPITALISE','SORTI');

-- ── ChatController ────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS `chat_conversations` (
    id        INT AUTO_INCREMENT PRIMARY KEY,
    titre     VARCHAR(255),
    type      ENUM('DIRECT','GROUPE') DEFAULT 'DIRECT',
    cree_par  INT,
    created_at DATETIME DEFAULT NOW()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `chat_participants` (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    conversation_id INT NOT NULL,
    user_id         INT NOT NULL,
    joined_at       DATETIME DEFAULT NOW(),
    UNIQUE KEY uk_conv_user (conversation_id, user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `chat_messages` (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    conversation_id INT NOT NULL,
    sender_id       INT NOT NULL,
    message         TEXT,
    type            ENUM('TEXT','IMAGE','FILE') DEFAULT 'TEXT',
    created_at      DATETIME DEFAULT NOW(),
    INDEX idx_conv (conversation_id),
    INDEX idx_sender (sender_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `user_presence` (
    user_id   INT PRIMARY KEY,
    last_seen DATETIME DEFAULT NOW(),
    statut    ENUM('online','offline','busy') DEFAULT 'offline'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── FormulaireController ──────────────────────────────────────────

CREATE TABLE IF NOT EXISTS `formulaires_data` (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    patient_id     INT,
    consultation_id INT,
    formulaire_type VARCHAR(100),
    data_json      LONGTEXT,
    created_at     DATETIME DEFAULT NOW(),
    updated_at     DATETIME ON UPDATE NOW()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `formulaires_soumis` (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    patient_id      INT,
    consultation_id INT,
    type_formulaire VARCHAR(100),
    data_json       LONGTEXT,
    soumis_par      INT,
    created_at      DATETIME DEFAULT NOW()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `patient_documents` (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    patient_id   INT NOT NULL,
    nom_fichier  VARCHAR(255),
    chemin_fichier VARCHAR(255),
    type_mime    VARCHAR(100),
    categorie    VARCHAR(100),
    description  TEXT,
    date_upload  DATETIME DEFAULT NOW(),
    INDEX idx_patient (patient_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =================================================================
