-- ============================================================================
-- MIGRATION : Rôle SECRETAIRE_LABO dans role_permissions
-- À exécuter dans phpMyAdmin sur la base dme_hospital
-- ============================================================================

USE dme_hospital;

-- 1. Étendre l'ENUM du champ role pour inclure les nouveaux rôles secrétariat
ALTER TABLE role_permissions
  MODIFY COLUMN role ENUM(
    'ADMINISTRATEUR','MEDECIN','INFIRMIER','INFIRMIER_CONSULTANT','MAJOR',
    'ACCUEIL','PHARMACIEN','LABORANTIN','GESTIONNAIRE','SECRETAIRE',
    'ADMIN','PARAMETRES','DIRECTEUR','TECHNICIEN_LABO',
    'SECRETAIRE_LABO','SECRETAIRE_SAU','SECRETAIRE_SPECIALISTE'
  ) NOT NULL;

-- 2. Permissions du Secrétaire de Laboratoire
INSERT IGNORE INTO role_permissions (role, module, permission) VALUES
  ('SECRETAIRE_LABO', 'dashboard',   'read'),
  ('SECRETAIRE_LABO', 'patients',    'read'),
  ('SECRETAIRE_LABO', 'laboratoire', 'read'),
  ('SECRETAIRE_LABO', 'laboratoire', 'write');

-- 3. Permissions du Secrétaire SAU (si manquantes)
INSERT IGNORE INTO role_permissions (role, module, permission) VALUES
  ('SECRETAIRE_SAU', 'dashboard', 'read'),
  ('SECRETAIRE_SAU', 'urgences',  'read'),
  ('SECRETAIRE_SAU', 'urgences',  'write'),
  ('SECRETAIRE_SAU', 'patients',  'read');

-- 4. Permissions du Secrétaire Spécialistes (si manquantes)
INSERT IGNORE INTO role_permissions (role, module, permission) VALUES
  ('SECRETAIRE_SPECIALISTE', 'dashboard',   'read'),
  ('SECRETAIRE_SPECIALISTE', 'specialiste', 'read'),
  ('SECRETAIRE_SPECIALISTE', 'specialiste', 'write'),
  ('SECRETAIRE_SPECIALISTE', 'patients',    'read');

-- Vérification
SELECT role, module, permission FROM role_permissions
WHERE role IN ('SECRETAIRE_LABO','SECRETAIRE_SAU','SECRETAIRE_SPECIALISTE')
ORDER BY role, module;
