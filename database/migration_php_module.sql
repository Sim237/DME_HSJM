-- ============================================================================
-- MIGRATION : Module PHP + Type de client
-- Ajoute type_client et circuit aux patients
-- ============================================================================

-- 1. Ajout du champ type_client
ALTER TABLE patients
    ADD COLUMN IF NOT EXISTS type_client
    ENUM('BON_PRISE_EN_CHARGE','PAYANT_COMPTANT','ASSURANCE','FAMILLE_PHP','AGENTS_PHP')
    DEFAULT 'PAYANT_COMPTANT'
    AFTER statut;

-- 2. Ajout du champ circuit (Standard vs PHP)
ALTER TABLE patients
    ADD COLUMN IF NOT EXISTS circuit
    ENUM('STANDARD','PHP')
    DEFAULT 'STANDARD'
    AFTER type_client;

-- Pour les bases qui ne supportent pas IF NOT EXISTS dans ALTER :
-- (exécuter manuellement si la commande ci-dessus échoue)
-- ALTER TABLE patients ADD COLUMN type_client ENUM('BON_PRISE_EN_CHARGE','PAYANT_COMPTANT','ASSURANCE','FAMILLE_PHP','AGENTS_PHP') DEFAULT 'PAYANT_COMPTANT';
-- ALTER TABLE patients ADD COLUMN circuit ENUM('STANDARD','PHP') DEFAULT 'STANDARD';

-- 3. Mise à jour des patients existants (rétro-compat)
UPDATE patients
SET circuit = 'PHP',
    type_client = 'FAMILLE_PHP'
WHERE type_client IN ('FAMILLE_PHP','AGENTS_PHP')
  AND circuit IS NULL;

UPDATE patients
SET circuit = 'STANDARD'
WHERE circuit IS NULL;

-- 4. Index pour les requêtes du directeur
CREATE INDEX IF NOT EXISTS idx_patients_type_client ON patients(type_client);
CREATE INDEX IF NOT EXISTS idx_patients_circuit ON patients(circuit);
