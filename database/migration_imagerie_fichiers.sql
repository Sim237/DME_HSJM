-- ═══════════════════════════════════════════════════════════════
-- Migration : support multi-fichiers DICOM par examen
-- À exécuter via phpMyAdmin sur la base dme_hospital
-- ═══════════════════════════════════════════════════════════════

CREATE TABLE IF NOT EXISTS imagerie_fichiers (
    id           INT          AUTO_INCREMENT PRIMARY KEY,
    demande_id   INT          NOT NULL,
    fichier      VARCHAR(255) NOT NULL,
    nom_original VARCHAR(255) NULL,
    ordre        SMALLINT UNSIGNED DEFAULT 1,
    date_upload  DATETIME DEFAULT NOW(),
    INDEX idx_demande (demande_id),
    CONSTRAINT fk_imgf_demande
        FOREIGN KEY (demande_id)
        REFERENCES demandes_imagerie(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Migrer les fichiers déjà existants (fichier_dicom unique) vers la nouvelle table
INSERT IGNORE INTO imagerie_fichiers (demande_id, fichier, nom_original, ordre, date_upload)
SELECT id,
       fichier_dicom,
       fichier_dicom,
       1,
       COALESCE(date_resultats, date_creation, NOW())
FROM demandes_imagerie
WHERE fichier_dicom IS NOT NULL
  AND fichier_dicom <> ''
  AND id NOT IN (SELECT demande_id FROM imagerie_fichiers);
