-- ============================================================================
-- SCRIPT DE NETTOYAGE ET RÉORGANISATION - dme_hospital
-- ⚠️  FAIRE UN BACKUP COMPLET AVANT D'EXÉCUTER
-- Exécuter section par section dans phpMyAdmin
-- ============================================================================

SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================================
-- SECTION 1 : SUPPRESSION DES TABLES DUPLIQUÉES (sans données utiles)
-- ============================================================================

-- 1.1 Doublons Admissions → gardé par hospitalisations
DROP TABLE IF EXISTS `admissions`;

-- 1.2 Doublons Prescriptions / Ordonnances
-- ordonnance_medicaments → remplacé par lignes_prescription
DROP TABLE IF EXISTS `ordonnance_medicaments`;
-- prescription_medicaments → doublon de lignes_prescription
DROP TABLE IF EXISTS `prescription_medicaments`;

-- 1.3 Doublons Soins (garder soins_hospitalisation + soins_details)
DROP TABLE IF EXISTS `soins_planification`;
DROP TABLE IF EXISTS `soins_planifies`;
DROP TABLE IF EXISTS `soins_administres`;
DROP TABLE IF EXISTS `soins_execution`;

-- 1.4 Doublons Laboratoire (garder demandes_laboratoire, demande_examens, examens_laboratoire, resultats_examens)
DROP TABLE IF EXISTS `examens`;
DROP TABLE IF EXISTS `examen_details`;
DROP TABLE IF EXISTS `examens_paracliniques`;
DROP TABLE IF EXISTS `laboratoire_resultats`;
DROP TABLE IF EXISTS `patient_resultats_labo`;

-- 1.5 lab_catalogue → doublon de examens_laboratoire (migrer les IDs si besoin)
-- Les données de lab_catalogue seront migrées dans examens_laboratoire
DROP TABLE IF EXISTS `lab_categories`;
DROP TABLE IF EXISTS `lab_parametres`;
DROP TABLE IF EXISTS `lab_catalogue`;

-- 1.6 Doublons RDV (garder agenda_medical)
DROP TABLE IF EXISTS `rendez_vous`;
DROP TABLE IF EXISTS `patient_rdv`;

-- 1.7 Doublons Notifications (garder notifications)
DROP TABLE IF EXISTS `notifications_automatiques`;
DROP TABLE IF EXISTS `notifications_medecin`;
DROP TABLE IF EXISTS `patient_notifications`;

-- 1.8 Doublons Blood (garder registre_donneurs_sang enrichi)
DROP TABLE IF EXISTS `blood_donors`;

-- 1.9 Doublons Urgences (garder urgences_patients + urgences_triage)
DROP TABLE IF EXISTS `urgences_admissions`;
DROP TABLE IF EXISTS `urgences_stats_rapides`;

-- 1.10 Doublons Télémédecine
DROP TABLE IF EXISTS `telemedecine_sessions`;

-- 1.11 Jamais utilisées
DROP TABLE IF EXISTS `encrypted_data`;
DROP TABLE IF EXISTS `performance_monitoring`;
DROP TABLE IF EXISTS `system_metrics`;
DROP TABLE IF EXISTS `patient_traitements`;
DROP TABLE IF EXISTS `patient_accounts`;
DROP TABLE IF EXISTS `patient_rappels`;
DROP TABLE IF EXISTS `decisions_hospitalisation`;
DROP TABLE IF EXISTS `maternite_consultations`;
DROP TABLE IF EXISTS `maternite_grossesses`;
DROP TABLE IF EXISTS `urgences_stats_rapides`;

-- ============================================================================
-- SECTION 2 : CORRECTION DES COLONNES REDONDANTES
-- ============================================================================

-- 2.1 TABLE lits : supprimer la colonne patient_id (doublon de occupied_by_patient_id)
-- Vérifier d'abord que occupied_by_patient_id contient les mêmes données
-- UPDATE lits SET occupied_by_patient_id = patient_id WHERE occupied_by_patient_id IS NULL AND patient_id IS NOT NULL;
ALTER TABLE `lits` DROP COLUMN IF EXISTS `patient_id`;

-- 2.2 TABLE users : supprimer date_creation (doublon de created_at) et statut (doublon de actif)
-- D'abord synchroniser : actif = (statut = 'ACTIF' ou statut IS NULL)
UPDATE `users` SET `actif` = CASE WHEN `statut` IN ('ACTIF', 'actif', '') OR `statut` IS NULL THEN 1 ELSE 0 END
WHERE `actif` IS NULL OR `actif` != CASE WHEN `statut` IN ('ACTIF', 'actif', '') OR `statut` IS NULL THEN 1 ELSE 0 END;
-- Copier date_creation dans created_at si created_at est NULL
UPDATE `users` SET `created_at` = `date_creation` WHERE `created_at` IS NULL AND `date_creation` IS NOT NULL;
ALTER TABLE `users` DROP COLUMN IF EXISTS `date_creation`;
ALTER TABLE `users` DROP COLUMN IF EXISTS `statut`;

-- 2.3 TABLE patients : nettoyer les doublons de timestamp
UPDATE `patients` SET `created_at` = `date_creation` WHERE `created_at` IS NULL AND `date_creation` IS NOT NULL;
ALTER TABLE `patients` DROP COLUMN IF EXISTS `date_creation`;
-- Garder date_modification pour la compatibilité applicative → renommer en updated_at
-- (updated_at existe déjà → vérifier)
-- ALTER TABLE `patients` DROP COLUMN IF EXISTS `date_modification`;

-- 2.4 TABLE consultations : nettoyer les doublons de timestamp et champs TA
-- tension_arterielle (varchar) est rendu obsolète par tension_systolique + tension_diastolique
ALTER TABLE `consultations` DROP COLUMN IF EXISTS `tension_arterielle`;
-- Supprimer les doublons timestamp
ALTER TABLE `consultations` DROP COLUMN IF EXISTS `date_modification`;
-- type et type_consultation : garder type_consultation, supprimer type
ALTER TABLE `consultations` DROP COLUMN IF EXISTS `type`;

-- 2.5 TABLE parametres_vitaux : supprimer les 2 aliases de tension
ALTER TABLE `parametres_vitaux` DROP COLUMN IF EXISTS `tension_sys`;
ALTER TABLE `parametres_vitaux` DROP COLUMN IF EXISTS `tension_dia`;

-- ============================================================================
-- SECTION 3 : FUSION ordonnances_pharmacie → prescriptions
-- ============================================================================
-- Ajouter les colonnes manquantes dans prescriptions
ALTER TABLE `prescriptions`
  ADD COLUMN IF NOT EXISTS `qr_code_token` VARCHAR(64) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `pharmacien_id` INT DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `date_delivrance` DATETIME DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `recommandations` TEXT DEFAULT NULL;

-- Migrer les données d'ordonnances_pharmacie vers prescriptions
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

-- Ajouter colonne disponible dans lignes_prescription (depuis ordonnance_medicaments)
ALTER TABLE `lignes_prescription`
  ADD COLUMN IF NOT EXISTS `disponible` TINYINT(1) DEFAULT 1;

-- Supprimer ordonnances_pharmacie après migration
DROP TABLE IF EXISTS `ordonnances_pharmacie`;

-- ============================================================================
-- SECTION 4 : AJOUTER LES FOREIGN KEYS MANQUANTES (critiques)
-- ============================================================================

-- 4.1 users.service_id → services.id
ALTER TABLE `users`
  ADD CONSTRAINT `fk_users_service`
  FOREIGN KEY IF NOT EXISTS (`service_id`) REFERENCES `services` (`id`) ON DELETE SET NULL;

-- 4.2 patients.service_id → services.id
ALTER TABLE `patients`
  ADD CONSTRAINT `fk_patients_service`
  FOREIGN KEY IF NOT EXISTS (`service_id`) REFERENCES `services` (`id`) ON DELETE SET NULL;

-- 4.3 consultations.service_id → services.id
ALTER TABLE `consultations`
  ADD CONSTRAINT `fk_consultations_service`
  FOREIGN KEY IF NOT EXISTS (`service_id`) REFERENCES `services` (`id`) ON DELETE SET NULL;

-- 4.4 demandes_laboratoire → (clés manquantes)
ALTER TABLE `demandes_laboratoire`
  ADD CONSTRAINT `fk_demlabo_consultation`
  FOREIGN KEY IF NOT EXISTS (`consultation_id`) REFERENCES `consultations` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_demlabo_patient`
  FOREIGN KEY IF NOT EXISTS (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_demlabo_medecin`
  FOREIGN KEY IF NOT EXISTS (`medecin_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_demlabo_technicien`
  FOREIGN KEY IF NOT EXISTS (`technicien_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

-- 4.5 demandes_imagerie → (clés manquantes)
ALTER TABLE `demandes_imagerie`
  ADD CONSTRAINT `fk_demimag_consultation`
  FOREIGN KEY IF NOT EXISTS (`consultation_id`) REFERENCES `consultations` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_demimag_patient`
  FOREIGN KEY IF NOT EXISTS (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_demimag_medecin`
  FOREIGN KEY IF NOT EXISTS (`medecin_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

-- 4.6 dicom_metadata.imagerie_id → demandes_imagerie.id
ALTER TABLE `dicom_metadata`
  ADD CONSTRAINT `fk_dicom_imagerie`
  FOREIGN KEY IF NOT EXISTS (`imagerie_id`) REFERENCES `demandes_imagerie` (`id`) ON DELETE CASCADE;

-- 4.7 lits.service_id → services.id
ALTER TABLE `lits`
  ADD CONSTRAINT `fk_lits_service`
  FOREIGN KEY IF NOT EXISTS (`service_id`) REFERENCES `services` (`id`) ON DELETE SET NULL;

-- 4.8 lits.occupied_by_patient_id → patients.id
ALTER TABLE `lits`
  ADD CONSTRAINT `fk_lits_patient`
  FOREIGN KEY IF NOT EXISTS (`occupied_by_patient_id`) REFERENCES `patients` (`id`) ON DELETE SET NULL;

-- 4.9 admissions.medecin_id (maintenant dans hospitalisations)
-- hospitalisations.medecin_responsable est INT → ajouter FK
ALTER TABLE `hospitalisations`
  ADD CONSTRAINT `fk_hosp_medecin`
  FOREIGN KEY IF NOT EXISTS (`medecin_responsable`) REFERENCES `users` (`id`) ON DELETE SET NULL;

-- 4.10 commentaires_bilans (nouvelle table — ajouter les FK)
ALTER TABLE `commentaires_bilans`
  ADD CONSTRAINT `fk_combilan_medecin`
  FOREIGN KEY IF NOT EXISTS (`medecin_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_combilan_patient`
  FOREIGN KEY IF NOT EXISTS (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE;

-- 4.11 consultations_pediatriques (nouvelle table — ajouter les FK)
ALTER TABLE `consultations_pediatriques`
  ADD CONSTRAINT `fk_pedcons_patient`
  FOREIGN KEY IF NOT EXISTS (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_pedcons_medecin`
  FOREIGN KEY IF NOT EXISTS (`medecin_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_pedcons_service`
  FOREIGN KEY IF NOT EXISTS (`service_id`) REFERENCES `services` (`id`) ON DELETE SET NULL;

-- 4.12 parametres_vitaux.admission_id → hospitalisations.id
ALTER TABLE `parametres_vitaux`
  ADD CONSTRAINT `fk_params_hosp`
  FOREIGN KEY IF NOT EXISTS (`admission_id`) REFERENCES `hospitalisations` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_params_user`
  FOREIGN KEY IF NOT EXISTS (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

-- 4.13 prescriptions.medecin_id → users.id
ALTER TABLE `prescriptions`
  ADD CONSTRAINT `fk_presc_medecin`
  FOREIGN KEY IF NOT EXISTS (`medecin_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

-- 4.14 soins_details.infirmier_id → users.id
ALTER TABLE `soins_details`
  ADD CONSTRAINT `fk_soinsdet_infirmier`
  FOREIGN KEY IF NOT EXISTS (`infirmier_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

-- ============================================================================
-- SECTION 5 : HARMONISATION DES COLLATIONS
-- ============================================================================
-- Les 6 tables en utf8mb4_unicode_ci → passer en utf8mb4_0900_ai_ci

ALTER TABLE `commentaires_bilans`
  CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci;

ALTER TABLE `consultations_pediatriques`
  CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci;

-- (Les 4 autres tables en unicode_ci à identifier et convertir de même)

-- ============================================================================
-- SECTION 6 : STANDARDISER LES TIMESTAMPS MANQUANTS
-- ============================================================================

ALTER TABLE `hospitalisations`
  ADD COLUMN IF NOT EXISTS `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP;

ALTER TABLE `lits`
  ADD COLUMN IF NOT EXISTS `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  ADD COLUMN IF NOT EXISTS `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

ALTER TABLE `services`
  ADD COLUMN IF NOT EXISTS `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP;

ALTER TABLE `chambres`
  ADD COLUMN IF NOT EXISTS `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP;

ALTER TABLE `prescriptions`
  ADD COLUMN IF NOT EXISTS `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

ALTER TABLE `demandes_laboratoire`
  ADD COLUMN IF NOT EXISTS `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  ADD COLUMN IF NOT EXISTS `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

ALTER TABLE `demandes_imagerie`
  ADD COLUMN IF NOT EXISTS `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  ADD COLUMN IF NOT EXISTS `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

ALTER TABLE `resultats_examens`
  ADD COLUMN IF NOT EXISTS `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  ADD COLUMN IF NOT EXISTS `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

ALTER TABLE `observations_evolution`
  ADD COLUMN IF NOT EXISTS `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP;

-- ============================================================================
-- SECTION 7 : NETTOYAGE TABLE patients (colonnes redondantes)
-- ============================================================================

-- Supprimer allergie_connue (doublon de allergies)
-- D'abord fusionner: si allergie_connue est renseigné et allergies est vide → copier
UPDATE `patients`
  SET `allergies` = `allergie_connue`
  WHERE (`allergies` IS NULL OR `allergies` = '')
    AND `allergie_connue` IS NOT NULL
    AND `allergie_connue` != '';

ALTER TABLE `patients` DROP COLUMN IF EXISTS `allergie_connue`;

-- Supprimer numero_ordre (redondant avec dossier_numero)
-- ALTER TABLE `patients` DROP COLUMN IF EXISTS `numero_ordre`;
-- ⚠️ Commenter la ligne ci-dessus si numero_ordre est utilisé différemment de dossier_numero

-- ============================================================================
-- SECTION 8 : INDEX MANQUANTS POUR LES PERFORMANCES
-- ============================================================================

-- Index sur les colonnes de recherche fréquentes

-- patients : recherche par nom/prénom
ALTER TABLE `patients`
  ADD INDEX IF NOT EXISTS `idx_patients_nom_prenom` (`nom`, `prenom`),
  ADD INDEX IF NOT EXISTS `idx_patients_statut` (`statut`),
  ADD INDEX IF NOT EXISTS `idx_patients_circuit` (`circuit`);

-- consultations : recherche par date et médecin
ALTER TABLE `consultations`
  ADD INDEX IF NOT EXISTS `idx_consult_date` (`date_consultation`),
  ADD INDEX IF NOT EXISTS `idx_consult_statut` (`statut`);

-- hospitalisations : recherche par statut
ALTER TABLE `hospitalisations`
  ADD INDEX IF NOT EXISTS `idx_hosp_statut` (`statut`),
  ADD INDEX IF NOT EXISTS `idx_hosp_dates` (`date_admission`, `date_sortie_effective`);

-- demandes_laboratoire : recherche par statut
ALTER TABLE `demandes_laboratoire`
  ADD INDEX IF NOT EXISTS `idx_demlabo_statut` (`statut`),
  ADD INDEX IF NOT EXISTS `idx_demlabo_date` (`created_at`);

-- demandes_imagerie : idem
ALTER TABLE `demandes_imagerie`
  ADD INDEX IF NOT EXISTS `idx_demimag_statut` (`statut`);

-- notifications : recherche par utilisateur non lu
ALTER TABLE `notifications`
  ADD INDEX IF NOT EXISTS `idx_notif_user_read` (`user_id`, `is_read`);

-- ============================================================================
-- SECTION 9 : VUE PRATIQUE — Résumé d'un patient
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
-- FIN DU SCRIPT
-- ============================================================================

SET FOREIGN_KEY_CHECKS = 1;

SELECT 'Nettoyage terminé. Vérifier les logs ci-dessus.' AS resultat;
