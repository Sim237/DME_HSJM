-- ============================================================================
-- MIGRATION : Refonte des circuits de RDV spécialistes
-- Avant  : STANDARD (40%), PHP (50%), PRESCRIPTION (10%)
-- Après  : PAYANT_COMPTANT (40%), FAMILLE_PHP (25%), AGENT_PHP (25%), HOSPITALISATION (10%)
-- Table  : rdv_specialistes.source
-- ============================================================================

-- Étape 1 : Assouplir le type de la colonne (VARCHAR pour permettre la migration)
ALTER TABLE rdv_specialistes
  MODIFY COLUMN source VARCHAR(30) NOT NULL DEFAULT 'PAYANT_COMPTANT';

-- Étape 2 : Migrer STANDARD → PAYANT_COMPTANT
UPDATE rdv_specialistes
   SET source = 'PAYANT_COMPTANT'
 WHERE source = 'STANDARD';

-- Étape 3 : Migrer PHP → AGENT_PHP ou FAMILLE_PHP selon le type_client du patient
UPDATE rdv_specialistes r
  JOIN patients p ON r.patient_id = p.id
   SET r.source = CASE
       WHEN p.type_client = 'AGENTS_PHP' THEN 'AGENT_PHP'
       ELSE 'FAMILLE_PHP'
   END
 WHERE r.source = 'PHP';

-- Étape 4 : Sécurité — PHP orphelins (patient supprimé) → FAMILLE_PHP par défaut
UPDATE rdv_specialistes SET source = 'FAMILLE_PHP' WHERE source = 'PHP';

-- Étape 5 : Migrer PRESCRIPTION → HOSPITALISATION
UPDATE rdv_specialistes
   SET source = 'HOSPITALISATION'
 WHERE source = 'PRESCRIPTION';

-- Vérification
SELECT source, COUNT(*) AS nb
FROM rdv_specialistes
GROUP BY source
ORDER BY nb DESC;
