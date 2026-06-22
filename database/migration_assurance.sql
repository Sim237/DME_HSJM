-- Migration : ajout champs assurance sur la table patients
ALTER TABLE patients
    ADD COLUMN IF NOT EXISTS nom_assurance VARCHAR(150) NULL DEFAULT NULL AFTER type_client,
    ADD COLUMN IF NOT EXISTS numero_assure VARCHAR(100) NULL DEFAULT NULL AFTER nom_assurance;
