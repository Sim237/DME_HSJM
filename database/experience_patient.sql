-- PORTAIL PATIENT
CREATE TABLE IF NOT EXISTS patient_accounts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    activation_token VARCHAR(100),
    is_active BOOLEAN DEFAULT FALSE,
    last_login DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patients(id)
);

CREATE TABLE IF NOT EXISTS patient_rdv (
    id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    medecin_id INT NOT NULL,
    date_rdv DATETIME NOT NULL,
    motif TEXT,
    statut ENUM('DEMANDE', 'CONFIRME', 'ANNULE', 'TERMINE') DEFAULT 'DEMANDE',
    notes_patient TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patients(id),
    FOREIGN KEY (medecin_id) REFERENCES users(id)
);

-- APPLICATION MOBILE
CREATE TABLE IF NOT EXISTS patient_traitements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    medicament VARCHAR(255) NOT NULL,
    dosage VARCHAR(100),
    frequence VARCHAR(100),
    date_debut DATE,
    date_fin DATE,
    instructions TEXT,
    actif BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patients(id)
);

CREATE TABLE IF NOT EXISTS patient_rappels (
    id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    type_rappel ENUM('MEDICAMENT', 'RDV', 'EXAMEN') NOT NULL,
    titre VARCHAR(255) NOT NULL,
    message TEXT,
    date_rappel DATETIME NOT NULL,
    envoye BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patients(id)
);

-- KIOSQUE D'ACCUEIL
CREATE TABLE IF NOT EXISTS kiosque_checkins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    rdv_id INT NULL,
    heure_checkin DATETIME DEFAULT CURRENT_TIMESTAMP,
    kiosque_id VARCHAR(50),
    statut ENUM('CHECKIN', 'ATTENTE', 'APPELE') DEFAULT 'CHECKIN',
    FOREIGN KEY (patient_id) REFERENCES patients(id),
    FOREIGN KEY (rdv_id) REFERENCES patient_rdv(id)
);

-- SATISFACTION
CREATE TABLE IF NOT EXISTS satisfaction_enquetes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    consultation_id INT NULL,
    note_globale INT CHECK (note_globale BETWEEN 1 AND 5),
    note_accueil INT CHECK (note_accueil BETWEEN 1 AND 5),
    note_attente INT CHECK (note_attente BETWEEN 1 AND 5),
    note_medecin INT CHECK (note_medecin BETWEEN 1 AND 5),
    commentaires TEXT,
    recommandation BOOLEAN,
    date_enquete DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patients(id)
);

-- Données de test
INSERT IGNORE INTO patient_accounts (patient_id, email, password_hash, is_active) VALUES 
(1, 'patient@test.com', '$2y$10$example', TRUE);

INSERT IGNORE INTO patient_traitements (patient_id, medicament, dosage, frequence, date_debut, date_fin) VALUES 
(1, 'Paracétamol', '1g', '3 fois par jour', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 7 DAY));

INSERT IGNORE INTO satisfaction_enquetes (patient_id, note_globale, note_accueil, note_attente, note_medecin, recommandation) VALUES 
(1, 5, 4, 3, 5, TRUE);