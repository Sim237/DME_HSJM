-- ============================================================
-- MIGRATION : Commentaires médecin sur résultats de bilans
-- ============================================================

-- Table des commentaires médecin sur les bilans (labo + imagerie)
CREATE TABLE IF NOT EXISTS commentaires_bilans (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    demande_id    INT          NOT NULL,
    type_bilan    ENUM('LABO','IMAGERIE') NOT NULL DEFAULT 'LABO',
    medecin_id    INT          NOT NULL,
    patient_id    INT          NOT NULL,
    commentaire   TEXT         NOT NULL,
    created_at    TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_demande  (demande_id, type_bilan),
    INDEX idx_patient  (patient_id),
    INDEX idx_medecin  (medecin_id)
);

-- Extension table notifications_medecin : ajouter type IMAGERIE si absent
-- (La table existe déjà via notifications_resultats.sql, on complète juste l'ENUM)
-- ALTER TABLE notifications_medecin MODIFY COLUMN type ENUM('RESULTATS_LABO','IMAGERIE','PHARMACIE','URGENCE','AUTRE') DEFAULT 'RESULTATS_LABO';
