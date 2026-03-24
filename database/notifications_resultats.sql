-- Table des notifications pour les médecins
CREATE TABLE IF NOT EXISTS notifications_medecin (
    id INT AUTO_INCREMENT PRIMARY KEY,
    medecin_id INT NOT NULL,
    patient_id INT NOT NULL,
    type ENUM('RESULTATS_LABO', 'PHARMACIE', 'URGENCE', 'AUTRE') DEFAULT 'RESULTATS_LABO',
    titre VARCHAR(255) NOT NULL,
    message TEXT,
    demande_id INT NULL,
    lu BOOLEAN DEFAULT FALSE,
    date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (medecin_id) REFERENCES users(id),
    FOREIGN KEY (patient_id) REFERENCES patients(id),
    FOREIGN KEY (demande_id) REFERENCES demandes_laboratoire(id)
);

-- Table historique résultats pour dossier patient
CREATE TABLE IF NOT EXISTS patient_resultats_labo (
    id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    demande_id INT NOT NULL,
    examen_id INT NOT NULL,
    nom_examen VARCHAR(255),
    resultat TEXT,
    valeur_numerique DECIMAL(10,3) NULL,
    unite VARCHAR(50),
    valeur_normale_min DECIMAL(10,3) NULL,
    valeur_normale_max DECIMAL(10,3) NULL,
    interpretation TEXT,
    anormal BOOLEAN DEFAULT FALSE,
    date_resultat TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    medecin_prescripteur_id INT,
    FOREIGN KEY (patient_id) REFERENCES patients(id),
    FOREIGN KEY (demande_id) REFERENCES demandes_laboratoire(id),
    FOREIGN KEY (examen_id) REFERENCES examens_laboratoire(id),
    FOREIGN KEY (medecin_prescripteur_id) REFERENCES users(id)
);