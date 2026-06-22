-- ============================================================
-- Migration : Domaine Windows pour authentification SQL Server SAGE
-- Exécuter une seule fois via phpMyAdmin
-- ============================================================

ALTER TABLE sage_config
    ADD COLUMN sage_domain VARCHAR(100) DEFAULT 'HSJM'
        COMMENT 'Domaine Windows pour les credentials SAGE (ex: HSJM)';

UPDATE sage_config SET sage_domain = 'HSJM' WHERE id = 1;
