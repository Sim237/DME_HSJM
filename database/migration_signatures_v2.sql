-- ============================================================================
-- MIGRATION : Module Signature Électronique v2
-- À appliquer : mysql -u root -proot dme_hospital < database/migration_signatures_v2.sql
-- ============================================================================

-- 1. Table medecin_signatures (si elle n'existe pas déjà)
CREATE TABLE IF NOT EXISTS `medecin_signatures` (
    `id`              INT          NOT NULL AUTO_INCREMENT,
    `medecin_id`      INT          NOT NULL,
    `signature_image` LONGTEXT     DEFAULT NULL COMMENT 'Base64 PNG de la signature',
    `cachet_image`    LONGTEXT     DEFAULT NULL COMMENT 'Base64 PNG du cachet',
    `numero_ordre`    VARCHAR(100) DEFAULT NULL COMMENT 'N° Ordre professionnel',
    `specialite`      VARCHAR(100) DEFAULT NULL,
    `is_active`       TINYINT(1)   NOT NULL DEFAULT 1,
    `created_at`      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_medecin` (`medecin_id`),
    CONSTRAINT `fk_sig_user` FOREIGN KEY (`medecin_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Table documents_signes
CREATE TABLE IF NOT EXISTS `documents_signes` (
    `id`             INT          NOT NULL AUTO_INCREMENT,
    `document_type`  ENUM('ORDONNANCE','CERTIFICAT','RAPPORT','COMPTE_RENDU','AUTRE') NOT NULL,
    `document_id`    INT          NOT NULL,
    `medecin_id`     INT          NOT NULL,
    `signature_hash` VARCHAR(64)  NOT NULL COMMENT 'SHA256 du document signé',
    `signed_at`      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `ip_address`     VARCHAR(45)  DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_doc` (`document_type`, `document_id`),
    KEY `idx_medecin` (`medecin_id`),
    CONSTRAINT `fk_ds_user` FOREIGN KEY (`medecin_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Colonnes supplémentaires sur ordonnances_pharmacie (ajout conditionnel via procédure)
DROP PROCEDURE IF EXISTS add_col_if_missing;
DELIMITER //
CREATE PROCEDURE add_col_if_missing()
BEGIN
    IF NOT EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'ordonnances_pharmacie' AND COLUMN_NAME = 'signature_hash') THEN
        ALTER TABLE `ordonnances_pharmacie` ADD COLUMN `signature_hash` VARCHAR(64) DEFAULT NULL COMMENT 'SHA256 à la signature';
    END IF;
    IF NOT EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'ordonnances_pharmacie' AND COLUMN_NAME = 'signed_at') THEN
        ALTER TABLE `ordonnances_pharmacie` ADD COLUMN `signed_at` DATETIME DEFAULT NULL COMMENT 'Horodatage de signature';
    END IF;
    IF NOT EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'ordonnances_pharmacie' AND COLUMN_NAME = 'signe_par') THEN
        ALTER TABLE `ordonnances_pharmacie` ADD COLUMN `signe_par` INT DEFAULT NULL COMMENT 'FK user_id du signataire';
    END IF;
    IF NOT EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'ordonnances_pharmacie' AND INDEX_NAME = 'idx_statut_medecin') THEN
        ALTER TABLE `ordonnances_pharmacie` ADD INDEX `idx_statut_medecin` (`statut`, `medecin_id`);
    END IF;
END //
DELIMITER ;
CALL add_col_if_missing();
DROP PROCEDURE IF EXISTS add_col_if_missing;

-- 4. Dossier de stockage physique (rappel — à créer sur le serveur)
-- mkdir -p assets/uploads/signatures && chmod 755 assets/uploads/signatures
