USE dme_hospital;

-- Table des examens de laboratoire
CREATE TABLE IF NOT EXISTS examens_laboratoire (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nom VARCHAR(100) NOT NULL,
    code VARCHAR(20) UNIQUE,
    categorie VARCHAR(50),
    valeur_min DECIMAL(10,3),
    valeur_max DECIMAL(10,3),
    unite VARCHAR(20),
    actif BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table des résultats de laboratoire
CREATE TABLE IF NOT EXISTS laboratoire_resultats (
    id INT PRIMARY KEY AUTO_INCREMENT,
    patient_id INT NOT NULL,
    examen_id INT NOT NULL,
    valeur DECIMAL(10,3) NOT NULL,
    unite VARCHAR(20),
    valeur_normale_min DECIMAL(10,3),
    valeur_normale_max DECIMAL(10,3),
    anormal BOOLEAN DEFAULT FALSE,
    date_resultat DATETIME NOT NULL,
    technicien_id INT,
    statut ENUM('en_attente', 'valide', 'rejete') DEFAULT 'valide',
    observations TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patients(id),
    FOREIGN KEY (examen_id) REFERENCES examens_laboratoire(id),
    FOREIGN KEY (technicien_id) REFERENCES users(id)
);

-- Insérer quelques examens de test
INSERT IGNORE INTO examens_laboratoire (id, nom, code, categorie, valeur_min, valeur_max, unite) VALUES
(1, 'Glycémie', 'GLY', 'Biochimie', 0.7, 1.1, 'g/L'),
(2, 'Créatinine', 'CREA', 'Biochimie', 60, 110, 'µmol/L'),
(3, 'Hémoglobine', 'HB', 'Hématologie', 12, 16, 'g/dL'),
(4, 'Leucocytes', 'GB', 'Hématologie', 4000, 10000, '/mm³'),
(5, 'Plaquettes', 'PLT', 'Hématologie', 150000, 400000, '/mm³');

-- Insérer quelques résultats de test
INSERT IGNORE INTO laboratoire_resultats (patient_id, examen_id, valeur, unite, valeur_normale_min, valeur_normale_max, anormal, date_resultat, statut) VALUES
(1, 1, 1.5, 'g/L', 0.7, 1.1, TRUE, NOW(), 'valide'),
(1, 2, 85, 'µmol/L', 60, 110, FALSE, NOW(), 'valide'),
(2, 3, 10.5, 'g/dL', 12, 16, TRUE, NOW(), 'valide'),
(2, 4, 12000, '/mm³', 4000, 10000, TRUE, NOW(), 'valide');

-- Index pour optimiser les performances
CREATE INDEX idx_resultats_patient_date ON laboratoire_resultats(patient_id, date_resultat);
CREATE INDEX idx_resultats_examen ON laboratoire_resultats(examen_id);
CREATE INDEX idx_resultats_anormal ON laboratoire_resultats(anormal, date_resultat);