-- Migration : Ajout des colonnes saturation et glycemie_capillaire dans consultations
-- Compatible MySQL 5.x et 8.x

-- Ajouter saturation uniquement si elle n'existe pas déjà
SET @dbname = DATABASE();
SET @colname1 = 'saturation';
SET @colname2 = 'glycemie_capillaire';
SET @tablename = 'consultations';

SET @sql1 = IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @colname1) = 0,
    'ALTER TABLE consultations ADD COLUMN saturation DECIMAL(5,2) DEFAULT NULL COMMENT ''Saturation en oxygène SpO2 (%)''',
    'SELECT 1 -- saturation déjà présente'
);
PREPARE stmt1 FROM @sql1;
EXECUTE stmt1;
DEALLOCATE PREPARE stmt1;

SET @sql2 = IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @colname2) = 0,
    'ALTER TABLE consultations ADD COLUMN glycemie_capillaire DECIMAL(5,2) DEFAULT NULL COMMENT ''Glycémie capillaire (g/L)''',
    'SELECT 1 -- glycemie_capillaire déjà présente'
);
PREPARE stmt2 FROM @sql2;
EXECUTE stmt2;
DEALLOCATE PREPARE stmt2;
