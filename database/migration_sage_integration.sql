-- ============================================================
-- MIGRATION : Intégration SAGE 100 Gestion Commerciale
-- DME Hospital — Pont SAGE ↔ Stock/Prix pharmacie
-- ============================================================

-- 1. Configuration de la connexion SAGE
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS sage_config (
    id              INT PRIMARY KEY AUTO_INCREMENT,
    -- Type de source
    type_source     ENUM('CSV_FILE','ODBC_DSN','ODBC_MDB','SQLSERVER')
                    NOT NULL DEFAULT 'CSV_FILE'
                    COMMENT 'Méthode de connexion à SAGE',
    -- CSV (méthode principale — aucun prérequis PHP)
    csv_watch_path  VARCHAR(500) DEFAULT NULL
                    COMMENT 'Dossier surveillé pour les exports SAGE (ex: C:\\SAGE\\exports)',
    csv_delimiter   VARCHAR(5)  DEFAULT ';'
                    COMMENT 'Séparateur de champs dans le CSV',
    csv_encoding    VARCHAR(20) DEFAULT 'UTF-8'
                    COMMENT 'Encodage du fichier SAGE (souvent windows-1252)',
    -- ODBC (si extension activée plus tard)
    odbc_dsn        VARCHAR(100) DEFAULT NULL
                    COMMENT 'Nom du DSN ODBC Windows configuré pour SAGE',
    odbc_mdb_path   VARCHAR(500) DEFAULT NULL
                    COMMENT 'Chemin complet vers GESCOM.MDB',
    -- SQL Server (SAGE avec backend SQL)
    sql_host        VARCHAR(100) DEFAULT NULL,
    sql_instance    VARCHAR(100) DEFAULT NULL,
    sql_db          VARCHAR(100) DEFAULT 'GESCOM',
    -- Credentials SAGE (souvent vide)
    sage_user       VARCHAR(100) DEFAULT '',
    sage_password   VARCHAR(255) DEFAULT '',
    -- Dépôt SAGE correspondant à la pharmacie
    depot_no        INT         DEFAULT 1
                    COMMENT 'Numéro de dépôt SAGE (DE_No) = pharmacie',
    depot_intitule  VARCHAR(100) DEFAULT 'PHARMACIE',
    -- État de la dernière sync
    last_sync_at    DATETIME    DEFAULT NULL,
    last_sync_ok    TINYINT(1)  DEFAULT 0,
    last_sync_msg   TEXT        DEFAULT NULL,
    last_csv_file   VARCHAR(500) DEFAULT NULL
                    COMMENT 'Dernier fichier CSV importé',
    -- Stats
    articles_sage   INT         DEFAULT 0
                    COMMENT 'Nb articles dans sage_medicaments_map',
    articles_mappes INT         DEFAULT 0
                    COMMENT 'Nb articles mappés vers medicaments DME',
    actif           TINYINT(1)  DEFAULT 1,
    created_at      DATETIME    DEFAULT NOW(),
    updated_at      DATETIME    DEFAULT NOW() ON UPDATE NOW()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Configuration de la connexion SAGE 100';

-- Insérer la configuration par défaut
INSERT IGNORE INTO sage_config (id, type_source) VALUES (1, 'CSV_FILE');

-- 2. Correspondance articles SAGE ↔ médicaments DME
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS sage_medicaments_map (
    id              INT PRIMARY KEY AUTO_INCREMENT,
    -- Côté SAGE
    ar_ref          VARCHAR(100) NOT NULL UNIQUE
                    COMMENT 'Référence article SAGE (AR_Ref = clé primaire SAGE)',
    ar_design       VARCHAR(500) DEFAULT NULL
                    COMMENT 'Désignation article SAGE (AR_Design)',
    fa_intitule     VARCHAR(200) DEFAULT NULL
                    COMMENT 'Famille article (FA_Intitule)',
    ar_prix_ven     DECIMAL(12,4) DEFAULT 0.0000
                    COMMENT 'Prix de vente SAGE HT (AR_PrixVen)',
    ar_prix_ttc     DECIMAL(12,4) DEFAULT 0.0000
                    COMMENT 'Prix TTC (calculé ou extrait)',
    ar_unite        VARCHAR(50)  DEFAULT NULL
                    COMMENT 'Unité de vente SAGE (AR_UniteVen)',
    as_qte_sto      DECIMAL(14,4) DEFAULT 0.0000
                    COMMENT 'Quantité en stock dépôt pharmacie (AS_QteSto)',
    -- Côté DME
    medicament_id   INT          DEFAULT NULL
                    COMMENT 'FK vers medicaments.id — NULL si non mappé',
    -- Statut du mapping
    statut_map      ENUM('NON_MAPPE','AUTO','MANUEL','IGNORE')
                    DEFAULT 'NON_MAPPE'
                    COMMENT 'Comment ce lien a été établi',
    confiance_auto  TINYINT      DEFAULT 0
                    COMMENT 'Score de confiance du mapping auto (0-100)',
    -- Contrôle
    exclure_sync    TINYINT(1)   DEFAULT 0
                    COMMENT '1 = exclure cet article de la sync stock',
    last_sync_at    DATETIME     DEFAULT NULL,
    created_at      DATETIME     DEFAULT NOW(),
    updated_at      DATETIME     DEFAULT NOW() ON UPDATE NOW(),
    FOREIGN KEY (medicament_id) REFERENCES medicaments(id) ON DELETE SET NULL,
    INDEX idx_medicament_id (medicament_id),
    INDEX idx_statut_map    (statut_map)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
  COMMENT='Table de correspondance articles SAGE <-> médicaments DME';

-- 3. Historique des synchronisations
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS sage_sync_logs (
    id              INT PRIMARY KEY AUTO_INCREMENT,
    type_operation  ENUM('IMPORT_CSV','SYNC_ODBC','SYNC_STOCK','SYNC_PRIX','AUTO_MAP')
                    NOT NULL,
    source_fichier  VARCHAR(500) DEFAULT NULL
                    COMMENT 'Nom du fichier CSV importé',
    articles_lus    INT         DEFAULT 0,
    articles_crees  INT         DEFAULT 0,
    articles_maj    INT         DEFAULT 0,
    meds_stock_maj  INT         DEFAULT 0
                    COMMENT 'Médicaments DME dont le stock a été mis à jour',
    meds_prix_maj   INT         DEFAULT 0
                    COMMENT 'Médicaments DME dont le prix a été mis à jour',
    erreurs         INT         DEFAULT 0,
    statut          ENUM('OK','ERREUR','PARTIEL') DEFAULT 'OK',
    message         TEXT        DEFAULT NULL,
    details_json    JSON        DEFAULT NULL
                    COMMENT 'Détails par article (erreurs, skips)',
    duree_ms        INT         DEFAULT 0
                    COMMENT 'Durée de l\'opération en ms',
    declencheur     VARCHAR(50) DEFAULT 'MANUEL'
                    COMMENT 'MANUEL, CRON, UPLOAD',
    user_id         INT         DEFAULT NULL,
    created_at      DATETIME    DEFAULT NOW(),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Journal des synchronisations SAGE';

-- 4. Ajouter champ ar_ref dans medicaments (si besoin)
--    pour stocker la référence SAGE directement dans DME
-- --------------------------------------------------------
ALTER TABLE medicaments
    ADD COLUMN IF NOT EXISTS ar_ref_sage VARCHAR(100) DEFAULT NULL
        COMMENT 'Référence article SAGE (AR_Ref) — lien direct' AFTER code,
    ADD COLUMN IF NOT EXISTS prix_sage   DECIMAL(12,4) DEFAULT NULL
        COMMENT 'Dernier prix synchronisé depuis SAGE' AFTER prix_unitaire,
    ADD COLUMN IF NOT EXISTS stock_sage_at DATETIME DEFAULT NULL
        COMMENT 'Horodatage de la dernière sync stock depuis SAGE' AFTER stock_sage_at;

-- Index pour recherche rapide par référence SAGE
-- (MySQL 5.x ne supporte pas IF NOT EXISTS sur CREATE INDEX — ignorer l'erreur si l'index existe déjà)
CREATE INDEX idx_ar_ref_sage ON medicaments(ar_ref_sage);
