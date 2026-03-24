-- BLOC OPÉRATOIRE
CREATE TABLE IF NOT EXISTS bloc_salles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(50) NOT NULL,
    type_salle ENUM('CHIRURGIE', 'AMBULATOIRE', 'URGENCE') DEFAULT 'CHIRURGIE',
    statut ENUM('LIBRE', 'OCCUPEE', 'NETTOYAGE', 'MAINTENANCE') DEFAULT 'LIBRE',
    equipements JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS bloc_interventions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    chirurgien_id INT NOT NULL,
    salle_id INT NOT NULL,
    type_intervention VARCHAR(100) NOT NULL,
    date_prevue DATETIME NOT NULL,
    duree_prevue INT DEFAULT 60,
    statut ENUM('PROGRAMMEE', 'EN_COURS', 'TERMINEE', 'ANNULEE') DEFAULT 'PROGRAMMEE',
    materiel_requis JSON,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patients(id),
    FOREIGN KEY (chirurgien_id) REFERENCES users(id),
    FOREIGN KEY (salle_id) REFERENCES bloc_salles(id)
);

-- MATERNITÉ
CREATE TABLE IF NOT EXISTS maternite_grossesses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    patiente_id INT NOT NULL,
    date_derniere_regles DATE,
    date_prevue_accouchement DATE,
    nombre_grossesses INT DEFAULT 1,
    nombre_accouchements INT DEFAULT 0,
    groupe_sanguin_conjoint VARCHAR(10),
    statut ENUM('EN_COURS', 'ACCOUCHEE', 'INTERROMPUE') DEFAULT 'EN_COURS',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patiente_id) REFERENCES patients(id)
);

CREATE TABLE IF NOT EXISTS maternite_consultations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    grossesse_id INT NOT NULL,
    semaine_amenorrhee INT,
    poids DECIMAL(5,2),
    tension_sys INT,
    tension_dia INT,
    hauteur_uterine INT,
    bcf INT,
    observations TEXT,
    date_consultation DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (grossesse_id) REFERENCES maternite_grossesses(id)
);

-- URGENCES
CREATE TABLE IF NOT EXISTS urgences_patients (
    id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    motif_admission TEXT NOT NULL,
    niveau_triage ENUM('1', '2', '3', '4', '5') NOT NULL,
    couleur_triage ENUM('ROUGE', 'ORANGE', 'JAUNE', 'VERT', 'BLEU') NOT NULL,
    heure_arrivee DATETIME DEFAULT CURRENT_TIMESTAMP,
    heure_prise_charge DATETIME NULL,
    medecin_id INT NULL,
    statut ENUM('ATTENTE', 'EN_COURS', 'TERMINE', 'HOSPITALISE') DEFAULT 'ATTENTE',
    FOREIGN KEY (patient_id) REFERENCES patients(id),
    FOREIGN KEY (medecin_id) REFERENCES users(id)
);

-- RÉANIMATION
CREATE TABLE IF NOT EXISTS reanimation_patients (
    id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    lit_id INT NOT NULL,
    date_admission DATETIME DEFAULT CURRENT_TIMESTAMP,
    diagnostic_admission TEXT,
    score_glasgow INT,
    statut ENUM('STABLE', 'CRITIQUE', 'AMELIORATION', 'SORTIE') DEFAULT 'STABLE',
    FOREIGN KEY (patient_id) REFERENCES patients(id)
);

CREATE TABLE IF NOT EXISTS reanimation_monitoring (
    id INT AUTO_INCREMENT PRIMARY KEY,
    patient_rea_id INT NOT NULL,
    timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
    frequence_cardiaque INT,
    tension_sys INT,
    tension_dia INT,
    saturation_o2 INT,
    temperature DECIMAL(4,2),
    frequence_respiratoire INT,
    glasgow INT,
    FOREIGN KEY (patient_rea_id) REFERENCES reanimation_patients(id)
);

-- Insertion de données de test
INSERT IGNORE INTO bloc_salles (nom, type_salle, equipements) VALUES 
('Salle 1', 'CHIRURGIE', '["Bistouri électrique", "Monitoring", "Respirateur"]'),
('Salle 2', 'AMBULATOIRE', '["Monitoring", "Défibrillateur"]');

INSERT IGNORE INTO urgences_patients (patient_id, motif_admission, niveau_triage, couleur_triage) VALUES 
(1, 'Douleur thoracique', '2', 'ORANGE'),
(1, 'Fracture bras', '3', 'JAUNE');