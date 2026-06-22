-- Migration : ajouter le statut NON_HONORE pour les RDV spécialistes
-- (Patient absent au rendez-vous — différent de ANNULE qui est sur initiative du patient/secrétaire)
-- Compatible MySQL 5.7+ / 8.0 / MariaDB

ALTER TABLE rdv_specialistes
  MODIFY COLUMN statut ENUM(
    'EN_ATTENTE',
    'CONFIRME',
    'EN_COURS',
    'TERMINE',
    'ANNULE',
    'REPORTE',
    'NON_HONORE'
  ) DEFAULT 'EN_ATTENTE';
