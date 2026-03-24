-- Table agenda médical
CREATE TABLE IF NOT EXISTS agenda_medical (
    id INT AUTO_INCREMENT PRIMARY KEY,
    medecin_id INT NOT NULL,
    patient_id INT NULL,
    type_rdv ENUM('consultation', 'intervention', 'suivi', 'urgence') NOT NULL,
    titre VARCHAR(255) NOT NULL,
    description TEXT,
    date_debut DATETIME NOT NULL,
    date_fin DATETIME NOT NULL,
    statut ENUM('planifie', 'confirme', 'en_cours', 'termine', 'annule') DEFAULT 'planifie',
    salle VARCHAR(50),
    couleur VARCHAR(7) DEFAULT '#007bff',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (medecin_id) REFERENCES users(id),
    FOREIGN KEY (patient_id) REFERENCES patients(id)
);

-- Données de test agenda
INSERT INTO agenda_medical (medecin_id, patient_id, type_rdv, titre, description, date_debut, date_fin, salle) VALUES
((SELECT id FROM users WHERE username = 'admin'), 1, 'consultation', 'Consultation cardiologie', 'Contrôle post-opératoire', '2024-12-20 09:00:00', '2024-12-20 09:30:00', 'Salle 1'),
((SELECT id FROM users WHERE username = 'admin'), 2, 'intervention', 'Intervention chirurgicale', 'Appendicectomie programmée', '2024-12-20 14:00:00', '2024-12-20 16:00:00', 'Bloc 1'),
((SELECT id FROM users WHERE username = 'admin'), NULL, 'consultation', 'Consultation libre', 'Créneau disponible', '2024-12-20 10:00:00', '2024-12-20 10:30:00', 'Salle 2');