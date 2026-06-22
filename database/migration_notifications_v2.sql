-- Migration : extension de la table notifications pour support unifié
-- (catégorie, lien direct vers l'action, métadonnées JSON, scope par service/role)

ALTER TABLE notifications
  ADD COLUMN category VARCHAR(50) NULL DEFAULT NULL
    COMMENT 'CONSULTATION, BILAN, SOIN, PHARMACIE, LABO, IMAGERIE, RDV, etc.'
    AFTER type,
  ADD COLUMN link VARCHAR(500) NULL DEFAULT NULL
    COMMENT 'URL relative ou absolue pour aller directement à l action'
    AFTER message,
  ADD COLUMN meta TEXT NULL DEFAULT NULL
    COMMENT 'JSON avec des infos contextuelles (patient_id, demande_id, etc.)'
    AFTER link,
  ADD COLUMN icon VARCHAR(50) NULL DEFAULT NULL
    COMMENT 'Classe icone Bootstrap Icons (bi-bell, bi-flask, etc.)'
    AFTER meta;

CREATE INDEX idx_notif_category ON notifications(category);
CREATE INDEX idx_notif_created ON notifications(created_at DESC);
