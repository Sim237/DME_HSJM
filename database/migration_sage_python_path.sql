-- Migration : ajout du chemin Python configurable pour l'extraction PDF Sage
-- À exécuter via phpMyAdmin sur la base dme_hospital
-- (Compatible MySQL 5.x / 8.0 / MariaDB)

ALTER TABLE sage_config
  ADD COLUMN python_path VARCHAR(500) NULL DEFAULT NULL
  COMMENT 'Chemin manuel vers python.exe (override la detection automatique)'
  AFTER pdftotext_path;
