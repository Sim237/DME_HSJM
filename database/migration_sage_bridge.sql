-- ============================================================
-- Migration : Pont SAGE direct — colonnes auto-sync
-- Exécuter une seule fois via phpMyAdmin
-- ============================================================

ALTER TABLE sage_config
    ADD COLUMN auto_sync_enabled TINYINT(1)  NOT NULL DEFAULT 0
        COMMENT 'Activer la synchronisation automatique périodique'
        AFTER pdftotext_path,
    ADD COLUMN sync_interval_min INT         NOT NULL DEFAULT 60
        COMMENT 'Intervalle en minutes entre deux synchronisations'
        AFTER auto_sync_enabled,
    ADD COLUMN last_auto_sync_at DATETIME    DEFAULT NULL
        COMMENT 'Date de la dernière synchronisation automatique'
        AFTER sync_interval_min;
