-- Migration : suppression logique des soins avec motif
-- À exécuter une seule fois sur la base de données

-- 1. Ajouter la valeur SUPPRIME à l'ENUM statut
ALTER TABLE soins_hospitalisation
    MODIFY COLUMN statut ENUM('PLANIFIE','REALISE','ANNULE','RETARD','SUPPRIME') DEFAULT 'PLANIFIE';

-- 2. Ajouter les colonnes de traçabilité de suppression
ALTER TABLE soins_hospitalisation
    ADD COLUMN motif_suppression TEXT DEFAULT NULL AFTER note_major,
    ADD COLUMN supprime_par INT DEFAULT NULL AFTER motif_suppression,
    ADD COLUMN date_suppression DATETIME DEFAULT NULL AFTER supprime_par;
