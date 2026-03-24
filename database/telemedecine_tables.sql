USE dme_hospital;

-- Consultations télémédecine
CREATE TABLE IF NOT EXISTS telemedecine_consultations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    patient_id INT NOT NULL,
    medecin_id INT NOT NULL,
    type ENUM('video', 'audio', 'chat') DEFAULT 'video',
    statut ENUM('planifie', 'en_cours', 'termine', 'annule') DEFAULT 'planifie',
    date_consultation DATETIME NOT NULL,
    duree_minutes INT DEFAULT 30,
    lien_reunion VARCHAR(255),
    room_id VARCHAR(100),
    motif TEXT,
    diagnostic TEXT,
    prescription TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patients(id),
    FOREIGN KEY (medecin_id) REFERENCES users(id)
);

-- Documents partagés télémédecine
CREATE TABLE IF NOT EXISTS telemedecine_documents (
    id INT PRIMARY KEY AUTO_INCREMENT,
    consultation_id INT NOT NULL,
    nom_fichier VARCHAR(255) NOT NULL,
    chemin_fichier VARCHAR(500) NOT NULL,
    type_fichier ENUM('image', 'pdf', 'video', 'audio', 'autre') NOT NULL,
    taille_ko INT,
    partage_par INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (consultation_id) REFERENCES telemedecine_consultations(id),
    FOREIGN KEY (partage_par) REFERENCES users(id)
);

-- Surveillance à distance
CREATE TABLE IF NOT EXISTS telemedecine_surveillance (
    id INT PRIMARY KEY AUTO_INCREMENT,
    patient_id INT NOT NULL,
    medecin_id INT NOT NULL,
    type_donnee ENUM('tension', 'glycemie', 'temperature', 'poids', 'frequence_cardiaque', 'saturation') NOT NULL,
    valeur DECIMAL(8,2) NOT NULL,
    unite VARCHAR(20),
    date_mesure DATETIME NOT NULL,
    alerte BOOLEAN DEFAULT FALSE,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patients(id),
    FOREIGN KEY (medecin_id) REFERENCES users(id)
);

-- Index pour performances
CREATE INDEX idx_teleconsult_patient_date ON telemedecine_consultations(patient_id, date_consultation);
CREATE INDEX idx_teleconsult_medecin ON telemedecine_consultations(medecin_id, statut);
CREATE INDEX idx_surveillance_patient ON telemedecine_surveillance(patient_id, date_mesure);
CREATE INDEX idx_surveillance_alerte ON telemedecine_surveillance(alerte, date_mesure);