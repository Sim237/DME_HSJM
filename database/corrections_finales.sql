-- ============================================================================
-- CORRECTIONS DES ERREURS RESTANTES
-- ============================================================================

SET FOREIGN_KEY_CHECKS = 0;

-- ─────────────────────────────────────────────────────────────────────────────
-- ERREUR 1 : medecin_id NOT NULL → ON DELETE SET NULL impossible
-- Solution : rendre la colonne nullable d'abord, puis ajouter la FK
-- Concerne : demandes_laboratoire, demandes_imagerie,
--            consultations_pediatriques, prescriptions
-- ─────────────────────────────────────────────────────────────────────────────

ALTER TABLE `demandes_laboratoire`
  MODIFY COLUMN `medecin_id` INT DEFAULT NULL;
ALTER TABLE `demandes_laboratoire`
  ADD CONSTRAINT `fk_demlabo_medecin`
  FOREIGN KEY (`medecin_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

ALTER TABLE `demandes_imagerie`
  MODIFY COLUMN `medecin_id` INT DEFAULT NULL;
ALTER TABLE `demandes_imagerie`
  ADD CONSTRAINT `fk_demimag_medecin`
  FOREIGN KEY (`medecin_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

ALTER TABLE `consultations_pediatriques`
  MODIFY COLUMN `medecin_id` INT DEFAULT NULL;
ALTER TABLE `consultations_pediatriques`
  ADD CONSTRAINT `fk_pedcons_medecin`
  FOREIGN KEY (`medecin_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

ALTER TABLE `prescriptions`
  MODIFY COLUMN `medecin_id` INT DEFAULT NULL;
ALTER TABLE `prescriptions`
  ADD CONSTRAINT `fk_presc_medecin`
  FOREIGN KEY (`medecin_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

-- ─────────────────────────────────────────────────────────────────────────────
-- ERREUR 2 : parametres_vitaux.user_id → données orphelines (user_id inexistant)
-- Solution : nettoyer les orphelins, puis ajouter la FK
-- ─────────────────────────────────────────────────────────────────────────────

-- Voir quels user_id sont orphelins
SELECT DISTINCT user_id FROM `parametres_vitaux`
WHERE user_id IS NOT NULL
  AND user_id NOT IN (SELECT id FROM `users`);

-- Mettre à NULL les user_id orphelins
UPDATE `parametres_vitaux`
  SET `user_id` = NULL
  WHERE `user_id` IS NOT NULL
    AND `user_id` NOT IN (SELECT id FROM `users`);

-- Maintenant ajouter la FK
ALTER TABLE `parametres_vitaux`
  ADD CONSTRAINT `fk_params_user`
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

-- ─────────────────────────────────────────────────────────────────────────────
-- ERREUR 3 : soins_details a une FK vers soins_planification (table supprimée)
-- Solution : supprimer la FK cassée, puis ajouter fk_soinsdet_infirmier
-- ─────────────────────────────────────────────────────────────────────────────

-- Supprimer la FK cassée (plan_id → soins_planification qui n'existe plus)
ALTER TABLE `soins_details` DROP FOREIGN KEY `soins_details_ibfk_1`;

-- Optionnel : rendre plan_id nullable puisque la table parent n'existe plus
ALTER TABLE `soins_details` MODIFY COLUMN `plan_id` INT DEFAULT NULL;

-- Ajouter la FK infirmier
ALTER TABLE `soins_details`
  ADD CONSTRAINT `fk_soinsdet_infirmier`
  FOREIGN KEY (`infirmier_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

-- ─────────────────────────────────────────────────────────────────────────────
-- ERREUR 4 : demandes_laboratoire — updated_at existe déjà
-- Solution : ajouter seulement created_at
-- ─────────────────────────────────────────────────────────────────────────────

ALTER TABLE `demandes_laboratoire`
  ADD COLUMN `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP;

-- ─────────────────────────────────────────────────────────────────────────────
-- ERREUR 5 : Index dupliqués (idx_patients_circuit, idx_hosp_statut)
-- Solution : ne créer que les index vraiment manquants
-- ─────────────────────────────────────────────────────────────────────────────

-- patients : idx_patients_circuit existait déjà → ajouter seulement nom_prenom + statut
ALTER TABLE `patients`
  ADD INDEX `idx_patients_nom_prenom` (`nom`, `prenom`);
ALTER TABLE `patients`
  ADD INDEX `idx_patients_statut` (`statut`);

-- hospitalisations : idx_hosp_statut existait → ajouter seulement idx_hosp_dates
ALTER TABLE `hospitalisations`
  ADD INDEX `idx_hosp_dates` (`date_admission`, `date_sortie_effective`);

-- ─────────────────────────────────────────────────────────────────────────────
-- COLLATIONS manquantes (résultat du SELECT de la section 5)
-- ─────────────────────────────────────────────────────────────────────────────

ALTER TABLE `comptes_rendus_hosp`
  CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci;

ALTER TABLE `surveillance_intensive_obs`
  CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci;

-- ─────────────────────────────────────────────────────────────────────────────
-- VÉRIFICATION FINALE
-- ─────────────────────────────────────────────────────────────────────────────

-- Lister toutes les FK actives
SELECT
  TABLE_NAME,
  CONSTRAINT_NAME,
  REFERENCED_TABLE_NAME
FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
WHERE TABLE_SCHEMA = 'dme_hospital'
  AND REFERENCED_TABLE_NAME IS NOT NULL
ORDER BY TABLE_NAME;

-- Vérifier qu'il ne reste plus de collations unicode_ci
SELECT TABLE_NAME, TABLE_COLLATION
FROM INFORMATION_SCHEMA.TABLES
WHERE TABLE_SCHEMA = 'dme_hospital'
  AND TABLE_COLLATION != 'utf8mb4_0900_ai_ci'
  AND TABLE_TYPE = 'BASE TABLE';

SET FOREIGN_KEY_CHECKS = 1;
SELECT 'Corrections terminées.' AS resultat;
