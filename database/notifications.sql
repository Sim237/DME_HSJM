-- Table des notifications
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    type ENUM('info', 'warning', 'danger', 'success') DEFAULT 'info',
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    priority ENUM('low', 'normal', 'high', 'urgent') DEFAULT 'normal',
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Notifications de test (utiliser l'ID admin existant)
-- D'abord vérifier l'ID admin : SELECT id FROM users WHERE username = 'admin';
-- Remplacer X par l'ID réel de l'admin
INSERT INTO notifications (user_id, type, title, message, priority) VALUES
((SELECT id FROM users WHERE username = 'admin'), 'danger', 'Patient critique', 'Patient en salle 12 nécessite une attention immédiate', 'urgent'),
((SELECT id FROM users WHERE username = 'admin'), 'warning', 'Stock faible', 'Paracétamol - Stock critique (5 unités restantes)', 'high'),
((SELECT id FROM users WHERE username = 'admin'), 'info', 'Nouveau patient', 'Patient Martin Jean enregistré avec succès', 'normal');