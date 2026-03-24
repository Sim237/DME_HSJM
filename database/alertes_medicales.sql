-- Tables pour alertes médicales
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

CREATE TABLE IF NOT EXISTS alertes_medicales (
    id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    type_alerte ENUM('interaction', 'allergie', 'contre_indication', 'dosage') NOT NULL,
    niveau_urgence ENUM('info', 'attention', 'danger', 'critique') NOT NULL,
    titre VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    medicament_concerne VARCHAR(255),
    statut ENUM('active', 'ignoree', 'resolue') DEFAULT 'active',
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patients(id),
    FOREIGN KEY (created_by) REFERENCES users(id)
);

-- Données de test interactions
INSERT INTO interactions_medicamenteuses (medicament_1, medicament_2, niveau_gravite, description, recommandation) VALUES
('Warfarine', 'Aspirine', 'grave', 'Risque hémorragique majeur', 'Surveillance INR renforcée, ajustement posologique'),
('Digoxine', 'Furosémide', 'modere', 'Risque de toxicité digitalique par hypokaliémie', 'Surveillance kaliémie et ECG'),
('Métformine', 'Produit de contraste iodé', 'contre_indique', 'Risque d\'acidose lactique', 'Arrêt 48h avant et après injection');

-- Données de test allergies
INSERT INTO allergies_patients (patient_id, type_allergie, allergene, gravite, symptomes, date_detection) VALUES
(1, 'medicament', 'Pénicilline', 'severe', 'Urticaire généralisé, œdème de Quincke', '2023-01-15'),
(2, 'medicament', 'Aspirine', 'moderee', 'Éruption cutanée, démangeaisons', '2022-06-10'),
(1, 'alimentaire', 'Arachides', 'anaphylaxie', 'Choc anaphylactique', '2020-03-20');