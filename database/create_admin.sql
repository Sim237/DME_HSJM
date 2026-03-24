-- Créer l'utilisateur admin manuellement
DELETE FROM users WHERE username = 'admin';

-- Vérifier les rôles disponibles
SHOW COLUMNS FROM users LIKE 'role';

-- Essayer avec différents rôles possibles
INSERT INTO users (nom, prenom, username, email, role, password, statut) VALUES
('Admin', 'Système', 'admin', 'admin@dme-hospital.com', 'ADMIN', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1);

-- Si ADMIN ne marche pas, essayer MEDECIN
-- INSERT INTO users (nom, prenom, username, email, role, password, statut) VALUES
-- ('Admin', 'Système', 'admin', 'admin@dme-hospital.com', 'MEDECIN', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1);

-- Vérification
SELECT id, nom, prenom, username, email, role FROM users WHERE username = 'admin';