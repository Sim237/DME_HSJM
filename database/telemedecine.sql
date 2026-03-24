-- Table télémédecine
CREATE TABLE IF NOT EXISTS telemedecine_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    consultation_id INT NOT NULL,
    medecin_id INT NOT NULL,
    patient_id INT NOT NULL,
    room_id VARCHAR(100) UNIQUE NOT NULL,
    statut ENUM('planifiee', 'active', 'terminee', 'annulee') DEFAULT 'planifiee',
    date_debut DATETIME NOT NULL,
    date_fin DATETIME NULL,
    duree_minutes INT DEFAULT 0,
    qualite_video ENUM('basse', 'moyenne', 'haute') DEFAULT 'moyenne',
    notes_session TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (consultation_id) REFERENCES consultations(id),
    FOREIGN KEY (medecin_id) REFERENCES users(id),
    FOREIGN KEY (patient_id) REFERENCES patients(id)
);