-- ============================================================
-- Migration : chemin pdftotext configurable dans sage_config
-- Exécuter une seule fois via phpMyAdmin
-- ============================================================

ALTER TABLE sage_config
    ADD COLUMN pdftotext_path VARCHAR(500) DEFAULT NULL
        COMMENT 'Chemin complet vers pdftotext.exe (Poppler). Vide = détection automatique dans le PATH et emplacements standards.'
    AFTER last_csv_file;
