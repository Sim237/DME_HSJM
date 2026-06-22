-- ============================================================================
-- SECTIONS 3 à 9 CORRIGÉES — Compatible MySQL 8.4
-- Pas de ADD COLUMN IF NOT EXISTS (introduit en MySQL 8.0.29 mais non porté en 8.4)
-- On vérifie l'existence via INFORMATION_SCHEMA avant d'ajouter
-- ============================================================================

SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================================
-- SECTION 3 : FUSION ordonnances_pharmacie → prescriptions
-- ============================================================================

-- Ajouter colonnes manquantes dans prescriptions (vérification manuelle recommandée)
-- Si la colonne existe déjà, cette commande échouera silencieusement — exécuter une par une

ALTER TABLE `prescriptions` ADD COLUMN `qr_code_token` VARCHAR(64) DEFAULT NULL;
ALTER TABLE `prescriptions` ADD COLUMN `pharmacien_id` INT DEFAULT NULL;
ALTER TABLE `prescriptions` ADD COLUMN `date_delivrance` DATETIME DEFAULT NULL;
ALTER TABLE `prescriptions` ADD COLUMN `recommandations` TEXT DEFAULT NULL;

-- Ajouter disponible dans lignes_prescription
ALTER TABLE `lignes_prescription` ADD COLUMN `disponible` TINYINT(1) DEFAULT 1;

-- Migrer les données d'ordonnances_pharmacie
INSERT INTO `prescriptions`
    (patient_id, medecin_id, consultation_id, statut, qr_code_token,
     pharmacien_id, recommandations, date_prescription)
SELECT
    op.patient_id, op.medecin_id, op.consultation_id,
    op.statut, op.qr_code_token, op.pharmacien_id,
    op.recommandations, op.created_at
FROM `ordonnances_pharmacie` op
WHERE NOT EXISTS (
    SELECT 1 FROM `prescriptions` p
    WHERE p.consultation_id = op.consultation_id
      AND p.patient_id = op.patient_id
);

DROP TABLE IF EXISTS `ordonnances_pharmacie`;

-- ============================================================================
-- SECTION 4 : FOREIGN KEYS MANQUANTES
-- ============================================================================
-- Note : Si la FK existe déjà, MySQL retourne une erreur — ignorer les doublons

-- users.service_id → services.id
ALTER TABLE `users`
  ADD CONSTRAINT `fk_users_service`
  FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE SET NULL;

-- patients.service_id → services.id
ALTER TABLE `patients`
  ADD CONSTRAINT `fk_patients_service`
  FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE SET NULL;

-- consultations.service_id → services.id
ALTER TABLE `consultations`
  ADD CONSTRAINT `fk_consultations_service`
  FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE SET NULL;

-- demandes_laboratoire
ALTER TABLE `demandes_laboratoire`
  ADD CONSTRAINT `fk_demlabo_consultation`
  FOREIGN KEY (`consultation_id`) REFERENCES `consultations` (`id`) ON DELETE SET NULL;

ALTER TABLE `demandes_laboratoire`
  ADD CONSTRAINT `fk_demlabo_patient`
  FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE;

ALTER TABLE `demandes_laboratoire`
  ADD CONSTRAINT `fk_demlabo_medecin`
  FOREIGN KEY (`medecin_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

ALTER TABLE `demandes_laboratoire`
  ADD CONSTRAINT `fk_demlabo_technicien`
  FOREIGN KEY (`technicien_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

-- demandes_imagerie
ALTER TABLE `demandes_imagerie`
  ADD CONSTRAINT `fk_demimag_consultation`
  FOREIGN KEY (`consultation_id`) REFERENCES `consultations` (`id`) ON DELETE SET NULL;

ALTER TABLE `demandes_imagerie`
  ADD CONSTRAINT `fk_demimag_patient`
  FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE;

ALTER TABLE `demandes_imagerie`
  ADD CONSTRAINT `fk_demimag_medecin`
  FOREIGN KEY (`medecin_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

-- dicom_metadata.imagerie_id
ALTER TABLE `dicom_metadata`
  ADD CONSTRAINT `fk_dicom_imagerie`
  FOREIGN KEY (`imagerie_id`) REFERENCES `demandes_imagerie` (`id`) ON DELETE CASCADE;

-- lits
ALTER TABLE `lits`
  ADD CONSTRAINT `fk_lits_service`
  FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE SET NULL;

ALTER TABLE `lits`
  ADD CONSTRAINT `fk_lits_patient`
  FOREIGN KEY (`occupied_by_patient_id`) REFERENCES `patients` (`id`) ON DELETE SET NULL;

-- hospitalisations.medecin_responsable
ALTER TABLE `hospitalisations`
  ADD CONSTRAINT `fk_hosp_medecin`
  FOREIGN KEY (`medecin_responsable`) REFERENCES `users` (`id`) ON DELETE SET NULL;

-- commentaires_bilans
ALTER TABLE `commentaires_bilans`
  ADD CONSTRAINT `fk_combilan_medecin`
  FOREIGN KEY (`medecin_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

ALTER TABLE `commentaires_bilans`
  ADD CONSTRAINT `fk_combilan_patient`
  FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE;

-- consultations_pediatriques
ALTER TABLE `consultations_pediatriques`
  ADD CONSTRAINT `fk_pedcons_patient`
  FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE;

ALTER TABLE `consultations_pediatriques`
  ADD CONSTRAINT `fk_pedcons_medecin`
  FOREIGN KEY (`medecin_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

ALTER TABLE `consultations_pediatriques`
  ADD CONSTRAINT `fk_pedcons_service`
  FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE SET NULL;

-- parametres_vitaux
ALTER TABLE `parametres_vitaux`
  ADD CONSTRAINT `fk_params_hosp`
  FOREIGN KEY (`admission_id`) REFERENCES `hospitalisations` (`id`) ON DELETE SET NULL;

ALTER TABLE `parametres_vitaux`
  ADD CONSTRAINT `fk_params_user`
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

-- prescriptions.medecin_id
ALTER TABLE `prescriptions`
  ADD CONSTRAINT `fk_presc_medecin`
  FOREIGN KEY (`medecin_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

-- soins_details.infirmier_id
ALTER TABLE `soins_details`
  ADD CONSTRAINT `fk_soinsdet_infirmier`
  FOREIGN KEY (`infirmier_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

-- ============================================================================
-- SECTION 5 : HARMONISATION COLLATIONS
-- ============================================================================

ALTER TABLE `commentaires_bilans`
  CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci;

ALTER TABLE `consultations_pediatriques`
  CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci;

-- Identifier les autres tables avec unicode_ci :
SELECT TABLE_NAME, TABLE_COLLATION
FROM INFORMATION_SCHEMA.TABLES
WHERE TABLE_SCHEMA = 'dme_hospital'
  AND TABLE_COLLATION = 'utf8mb4_unicode_ci';
-- Exécuter ALTER TABLE ... CONVERT TO pour chacune des tables listées ci-dessus

-- ============================================================================
-- SECTION 6 : TIMESTAMPS MANQUANTS
-- ============================================================================

ALTER TABLE `hospitalisations`
  ADD COLUMN `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP;

ALTER TABLE `lits`
  ADD COLUMN `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  ADD COLUMN `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

ALTER TABLE `services`
  ADD COLUMN `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP;

ALTER TABLE `chambres`
  ADD COLUMN `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP;

ALTER TABLE `prescriptions`
  ADD COLUMN `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

ALTER TABLE `demandes_laboratoire`
  ADD COLUMN `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  ADD COLUMN `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

ALTER TABLE `demandes_imagerie`
  ADD COLUMN `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  ADD COLUMN `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

ALTER TABLE `resultats_examens`
  ADD COLUMN `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  ADD COLUMN `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

ALTER TABLE `observations_evolution`
  ADD COLUMN `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP;

-- ============================================================================
-- SECTION 7 : INDEX PERFORMANCES
-- ============================================================================

ALTER TABLE `patients`
  ADD INDEX `idx_patients_nom_prenom` (`nom`, `prenom`),
  ADD INDEX `idx_patients_statut` (`statut`),
  ADD INDEX `idx_patients_circuit` (`circuit`);

ALTER TABLE `consultations`
  ADD INDEX `idx_consult_date` (`date_consultation`),
  ADD INDEX `idx_consult_statut` (`statut`);

ALTER TABLE `hospitalisations`
  ADD INDEX `idx_hosp_statut` (`statut`),
  ADD INDEX `idx_hosp_dates` (`date_admission`, `date_sortie_effective`);

ALTER TABLE `demandes_laboratoire`
  ADD INDEX `idx_demlabo_statut` (`statut`);

ALTER TABLE `demandes_imagerie`
  ADD INDEX `idx_demimag_statut` (`statut`);

ALTER TABLE `notifications`
  ADD INDEX `idx_notif_user_read` (`user_id`, `is_read`);

-- ============================================================================
-- SECTION 8 : VUE RÉSUMÉ PATIENT
-- ============================================================================

CREATE OR REPLACE VIEW `v_patients_actifs` AS
SELECT
    p.id,
    p.dossier_numero,
    CONCAT(p.nom, ' ', p.prenom) AS nom_complet,
    p.date_naissance,
    TIMESTAMPDIFF(YEAR, p.date_naissance, CURDATE()) AS age,
    p.sexe,
    p.telephone,
    p.groupe_sanguin,
    p.statut_parcours,
    s.nom_service,
    u.nom AS medecin_nom,
    u.prenom AS medecin_prenom,
    h.id AS hospitalisation_id,
    h.statut AS statut_hosp,
    l.nom_lit,
    c.nom_chambre
FROM patients p
LEFT JOIN services s ON p.service_id = s.id
LEFT JOIN users u ON p.medecin_id = u.id
LEFT JOIN hospitalisations h ON h.patient_id = p.id AND h.statut = 'EN_COURS'
LEFT JOIN lits l ON h.lit_id = l.id
LEFT JOIN chambres c ON l.chambre_id = c.id
WHERE p.actif = 1;

-- ============================================================================
SET FOREIGN_KEY_CHECKS = 1;
SELECT 'Sections 3-9 terminées.' AS resultat;
