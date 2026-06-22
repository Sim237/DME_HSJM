-- ============================================================================
-- MIGRATION : Rôle TECHNICIEN_LABO + Workflow Dispatch/Validation
-- Compatibilité : MySQL 5.6 / 5.7 (pas de IF NOT EXISTS sur ADD COLUMN)
-- ============================================================================
-- Workflow :
--   MEDECIN → crée demande
--   LABORANTIN → dispatche chaque examen à un TECHNICIEN_LABO
--   TECHNICIEN_LABO → saisit résultats + soumet pour validation
--   LABORANTIN → valide (→ médecin notifié) ou rejette (→ technicien recommence)
-- ============================================================================

SET FOREIGN_KEY_CHECKS = 0;

-- ── 1. Étendre le rôle à VARCHAR(50) ─────────────────────────────────────────
ALTER TABLE users
  MODIFY COLUMN role VARCHAR(50) NOT NULL DEFAULT 'ACCUEIL';

-- ── 2. Colonnes de dispatch dans demande_examens ──────────────────────────────
-- NOTE MySQL 5.x : pas de IF NOT EXISTS → exécuter colonne par colonne
--      Si une colonne existe déjà, ignorer l'erreur "Duplicate column name"

ALTER TABLE demande_examens
  ADD COLUMN technicien_id    INT NULL       COMMENT 'Technicien assigné à cet examen'    AFTER examen_id;

ALTER TABLE demande_examens
  ADD COLUMN date_assignation DATETIME NULL  COMMENT 'Date du dispatch par le laborantin' AFTER technicien_id;

ALTER TABLE demande_examens
  ADD COLUMN date_soumission  DATETIME NULL  COMMENT 'Date de soumission par le technicien';

ALTER TABLE demande_examens
  ADD COLUMN motif_rejet      TEXT NULL      COMMENT 'Motif de rejet par le laborantin';

ALTER TABLE demande_examens
  ADD COLUMN valide_par       INT NULL       COMMENT 'ID du laborantin validateur';

ALTER TABLE demande_examens
  ADD COLUMN date_validation  DATETIME NULL  COMMENT 'Date de validation/rejet par le laborantin';

-- ── 3. Étendre le statut des examens ─────────────────────────────────────────
-- EN_ATTENTE  → demande créée, examen non encore dispatché
-- ASSIGNEE    → dispatché à un technicien, en attente de saisie
-- PRELEVE     → prélèvement effectué
-- EN_COURS    → analyse en cours
-- SOUMIS      → résultats saisis et soumis, en attente validation laborantin
-- VALIDE      → validé par le laborantin → transmis au médecin
-- REJETE      → rejeté → technicien doit recommencer

ALTER TABLE demande_examens
  MODIFY COLUMN statut ENUM(
    'EN_ATTENTE','ASSIGNEE','PRELEVE','EN_COURS','SOUMIS','VALIDE','REJETE'
  ) NOT NULL DEFAULT 'EN_ATTENTE';

-- ── 4. Étendre le statut de la demande principale ────────────────────────────
ALTER TABLE demandes_laboratoire
  MODIFY COLUMN statut VARCHAR(50) NOT NULL DEFAULT 'EN_ATTENTE';

-- ── 5. Permissions du rôle TECHNICIEN_LABO ────────────────────────────────────
-- role_permissions.role est un ENUM → il faut d'abord l'étendre

ALTER TABLE role_permissions
  MODIFY COLUMN role ENUM(
    'ADMINISTRATEUR','MEDECIN','INFIRMIER','INFIRMIER_CONSULTANT','MAJOR',
    'ACCUEIL','PHARMACIEN','LABORANTIN','GESTIONNAIRE','SECRETAIRE',
    'ADMIN','PARAMETRES','DIRECTEUR','TECHNICIEN_LABO'
  ) NOT NULL;

-- permission ENUM : 'READ', 'write', 'delete', 'admin'
INSERT IGNORE INTO role_permissions (role, module, permission) VALUES
  ('TECHNICIEN_LABO', 'dashboard',   'READ'),
  ('TECHNICIEN_LABO', 'patients',    'READ'),
  ('TECHNICIEN_LABO', 'laboratoire', 'write');

SET FOREIGN_KEY_CHECKS = 1;

-- ── Vérification ──────────────────────────────────────────────────────────────
SELECT COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE, COLUMN_COMMENT
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'demande_examens'
ORDER BY ORDINAL_POSITION;

SELECT role, module, permission
FROM role_permissions
WHERE role = 'TECHNICIEN_LABO';
