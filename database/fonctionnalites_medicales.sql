-- AGENDA MÉDICAL
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

-- TÉLÉMÉDECINE
CREATE TABLE IF NOT EXISTS telemedecine_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    consultation_id INT NULL,
    medecin_id INT NOT NULL,
    patient_id INT NOT NULL,
    room_id VARCHAR(100) UNIQUE NOT NULL,
    statut ENUM('planifiee', 'active', 'terminee', 'annulee') DEFAULT 'planifiee',
    date_debut DATETIME NOT NULL,
    date_fin DATETIME NULL,
    duree_minutes INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (medecin_id) REFERENCES users(id),
    FOREIGN KEY (patient_id) REFERENCES patients(id)
);

-- ALERTES MÉDICALES
CREATE TABLE IF NOT EXISTS interactions_medicamenteuses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    medicament_1 VARCHAR(255) NOT NULL,
    medicament_2 VARCHAR(255) NOT NULL,
    niveau_gravite ENUM('leger', 'modere', 'grave', 'contre_indique') NOT NULL,
    description TEXT NOT NULL,
    recommandation TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS allergies_patients (
    id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    type_allergie ENUM('medicament', 'alimentaire', 'environnementale', 'autre') NOT NULL,
    allergene VARCHAR(255) NOT NULL,
    gravite ENUM('legere', 'moderee', 'severe', 'anaphylaxie') NOT NULL,
    symptomes TEXT,
    date_detection DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patients(id)
);

-- IMAGERIE MÉDICALE
CREATE TABLE IF NOT EXISTS imagerie_medicale (
    id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    consultation_id INT NULL,
    type_examen ENUM('radiographie', 'scanner', 'irm', 'echographie', 'mammographie', 'autre') NOT NULL,
    partie_corps VARCHAR(100) NOT NULL,
    description TEXT,
    date_examen DATETIME NOT NULL,
    medecin_prescripteur INT NOT NULL,
    statut ENUM('programme', 'en_cours', 'termine', 'interprete', 'valide') DEFAULT 'programme',
    fichier_dicom VARCHAR(255),
    fichier_preview VARCHAR(255),
    interpretation TEXT,
    conclusion TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patients(id),
    FOREIGN KEY (medecin_prescripteur) REFERENCES users(id)
);

-- DONNÉES DE TEST
INSERT IGNORE INTO interactions_medicamenteuses (medicament_1, medicament_2, niveau_gravite, description, recommandation) VALUES
('Warfarine', 'Aspirine', 'grave', 'Risque hémorragique majeur', 'Surveillance INR renforcée'),
('Digoxine', 'Furosémide', 'modere', 'Risque de toxicité digitalique', 'Surveillance kaliémie');

INSERT IGNORE INTO imagerie_medicale (patient_id, type_examen, partie_corps, description, date_examen, medecin_prescripteur, statut) VALUES
(1, 'radiographie', 'Thorax', 'Radiographie pulmonaire', '2024-12-20 10:00:00', (SELECT id FROM users WHERE username = 'admin'), 'termine'),
(1, 'scanner', 'Crâne', 'Scanner cérébral', '2024-12-20 14:30:00', (SELECT id FROM users WHERE username = 'admin'), 'programme');