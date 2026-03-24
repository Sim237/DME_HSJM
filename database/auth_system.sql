-- Système d'authentification avec rôles
-- Mise à jour de la table users existante

-- Ajout des colonnes manquantes si elles n'existent pas
ALTER TABLE users 
ADD COLUMN IF NOT EXISTS last_login DATETIME NULL,
ADD COLUMN IF NOT EXISTS created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

-- Mise à jour des rôles disponibles
ALTER TABLE users MODIFY COLUMN role ENUM(
    'ADMINISTRATEUR',
    'MEDECIN', 
    'INFIRMIER',
    'ACCUEIL',
    'PHARMACIEN',
    'LABORANTIN',
    'GESTIONNAIRE'
) NOT NULL DEFAULT 'ACCUEIL';

-- Table des permissions par rôle
CREATE TABLE IF NOT EXISTS role_permissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    role ENUM('ADMINISTRATEUR','MEDECIN','INFIRMIER','ACCUEIL','PHARMACIEN','LABORANTIN','GESTIONNAIRE') NOT NULL,
    module VARCHAR(50) NOT NULL,
    permission ENUM('READ','write','delete','admin') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_role_module_permission (role, module, permission)
);

-- Permissions par défaut
INSERT IGNORE INTO role_permissions (role, module, permission) VALUES
-- ADMINISTRATEUR (accès total)
('ADMINISTRATEUR', 'dashboard', 'admin'),
('ADMINISTRATEUR', 'patients', 'admin'),
('ADMINISTRATEUR', 'consultations', 'admin'),
('ADMINISTRATEUR', 'hospitalisation', 'admin'),
('ADMINISTRATEUR', 'pharmacie', 'admin'),
('ADMINISTRATEUR', 'laboratoire', 'admin'),
('ADMINISTRATEUR', 'utilisateurs', 'admin'),
('ADMINISTRATEUR', 'parametres', 'admin'),
('ADMINISTRATEUR', 'registres', 'admin'),

-- MEDECIN
('MEDECIN', 'dashboard', 'read'),
('MEDECIN', 'patients', 'write'),
('MEDECIN', 'consultations', 'write'),
('MEDECIN', 'hospitalisation', 'write'),
('MEDECIN', 'pharmacie', 'read'),
('MEDECIN', 'laboratoire', 'write'),
('MEDECIN', 'registres', 'read'),

-- INFIRMIER
('INFIRMIER', 'dashboard', 'read'),
('INFIRMIER', 'patients', 'read'),
('INFIRMIER', 'consultations', 'read'),
('INFIRMIER', 'hospitalisation', 'write'),
('INFIRMIER', 'pharmacie', 'read'),
('INFIRMIER', 'laboratoire', 'read'),

-- ACCUEIL
('ACCUEIL', 'dashboard', 'read'),
('ACCUEIL', 'patients', 'write'),
('ACCUEIL', 'consultations', 'read'),

-- PHARMACIEN
('PHARMACIEN', 'dashboard', 'read'),
('PHARMACIEN', 'patients', 'read'),
('PHARMACIEN', 'pharmacie', 'write'),

-- LABORANTIN
('LABORANTIN', 'dashboard', 'read'),
('LABORANTIN', 'patients', 'read'),
('LABORANTIN', 'laboratoire', 'write'),

-- GESTIONNAIRE
('GESTIONNAIRE', 'dashboard', 'read'),
('GESTIONNAIRE', 'patients', 'read'),
('GESTIONNAIRE', 'registres', 'write'),
('GESTIONNAIRE', 'parametres', 'read');

-- Utilisateur admin par défaut
INSERT IGNORE INTO users (nom, prenom, username, email, role, password, statut) VALUES
('Admin', 'Système', 'admin', 'admin@dme-hospital.com', 'ADMINISTRATEUR', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1);
-- Mot de passe: password