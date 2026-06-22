-- ============================================================
-- Migration : Connexion Pervasive PSQL (SAGE 100 GC .gcm)
-- Exécuter une seule fois via phpMyAdmin
-- ============================================================

ALTER TABLE sage_config
    ADD COLUMN sage_domain  VARCHAR(100) DEFAULT 'HSJM'
        COMMENT 'Domaine Windows SAGE (ex: HSJM)',
    ADD COLUMN psql_server  VARCHAR(200) DEFAULT 'SAGE25001'
        COMMENT 'Nom ou IP du serveur Pervasive PSQL (ex: SAGE25001)',
    ADD COLUMN psql_db      VARCHAR(100) DEFAULT 'HSJM'
        COMMENT 'Nom de la base Pervasive (nom du .gcm sans extension)';

-- Mettre à jour les valeurs par défaut
UPDATE sage_config SET
    sage_domain = 'HSJM',
    psql_server = 'SAGE25001',
    psql_db     = 'HSJM'
WHERE id = 1;
