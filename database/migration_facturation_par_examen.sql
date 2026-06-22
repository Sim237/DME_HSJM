-- Migration : facturation par examen individuel (au lieu de la demande entière)
-- Permet la facturation partielle quand le patient ne paye qu'une partie

ALTER TABLE demande_examens
  ADD COLUMN facture TINYINT(1) DEFAULT 0
    COMMENT '0=non facturé, 1=facturé'
    AFTER statut,
  ADD COLUMN date_facturation_examen DATETIME NULL DEFAULT NULL
    AFTER facture,
  ADD COLUMN facture_par_examen INT NULL DEFAULT NULL
    COMMENT 'ID utilisateur (secretaire labo) qui a facturé'
    AFTER date_facturation_examen,
  ADD COLUMN montant_examen DECIMAL(10,2) NULL DEFAULT NULL
    COMMENT 'Montant facturé pour cet examen'
    AFTER facture_par_examen;

CREATE INDEX idx_demande_examens_facture ON demande_examens(facture);

-- Étendre l'enum statut_facturation pour gérer le cas PARTIELLE
ALTER TABLE demandes_laboratoire
  MODIFY COLUMN statut_facturation
    ENUM('NON_FACTURE','PARTIELLE','FACTURE','ANNULE')
    DEFAULT 'NON_FACTURE';
