-- =================================================================
-- Migration 002 : Contraintes de sécurité & index critiques
-- À exécuter une seule fois sur la base de production
-- =================================================================

-- 1. UNIQUE sur dossier_numero (filet contre les race conditions)
--    Si deux INSERTs concurrents produisent le même numéro,
--    le second échoue avec une erreur que le controller gère.
ALTER TABLE patients
    ADD UNIQUE KEY uk_dossier_numero (dossier_numero);

-- 2. Index sur colonnes filtrées fréquemment (performance)
ALTER TABLE patients
    ADD INDEX idx_statut_hosp      (statut_hosp),
    ADD INDEX idx_statut_parcours  (statut_parcours),
    ADD INDEX idx_type_client      (type_client),
    ADD INDEX idx_circuit          (circuit);

ALTER TABLE hospitalisations
    ADD INDEX idx_hosp_statut      (statut),
    ADD INDEX idx_hosp_patient     (patient_id),
    ADD INDEX idx_hosp_service     (service_id);

ALTER TABLE demandes_laboratoire
    ADD INDEX idx_dl_statut        (statut),
    ADD INDEX idx_dl_patient       (patient_id);

ALTER TABLE lits
    ADD INDEX idx_lits_statut      (statut);

ALTER TABLE services
    ADD INDEX idx_services_nom     (nom_service(64));

-- =================================================================
