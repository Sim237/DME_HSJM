USE dme_hospital;

-- Table des hospitalisations
CREATE TABLE IF NOT EXISTS hospitalisations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    patient_id INT NOT NULL,
    service_id INT,
    lit_id INT,
    date_admission DATETIME NOT NULL,
    date_sortie DATETIME NULL,
    motif_admission TEXT,
    diagnostic_admission TEXT,
    statut ENUM('active', 'sortie', 'transfert', 'deces') DEFAULT 'active',
    medecin_referent_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patients(id),
    FOREIGN KEY (medecin_referent_id) REFERENCES users(id)
);

-- Table des services hospitaliers
CREATE TABLE IF NOT EXISTS services (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nom VARCHAR(100) NOT NULL,
    chef_service_id INT,
    capacite INT DEFAULT 0,
    specialite VARCHAR(100),
    actif BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (chef_service_id) REFERENCES users(id)
);

-- Table des constantes vitales
CREATE TABLE IF NOT EXISTS constantes_vitales (
    id INT PRIMARY KEY AUTO_INCREMENT,
    patient_id INT NOT NULL,
    temperature DECIMAL(4,2),
    tension_systolique INT,
    tension_diastolique INT,
    frequence_cardiaque INT,
    frequence_respiratoire INT,
    saturation_o2 INT,
    glycemie DECIMAL(5,2),
    poids DECIMAL(5,2),
    taille DECIMAL(5,2),
    date_mesure DATETIME NOT NULL,
    infirmier_id INT,
    observations TEXT,
    FOREIGN KEY (patient_id) REFERENCES patients(id),
    FOREIGN KEY (infirmier_id) REFERENCES users(id)
);

-- Table des prescriptions hospitalisation
CREATE TABLE IF NOT EXISTS prescriptions_hospitalisation (
    id INT PRIMARY KEY AUTO_INCREMENT,
    patient_id INT NOT NULL,
    medicament_id INT NOT NULL,
    medecin_id INT NOT NULL,
    posologie TEXT NOT NULL,
    voie_administration VARCHAR(50),
    frequence VARCHAR(100),
    duree_traitement INT,
    heure_debut TIME,
    heure_fin TIME,
    date_prescription DATETIME DEFAULT CURRENT_TIMESTAMP,
    statut ENUM('active', 'suspendu', 'arrete', 'termine') DEFAULT 'active',
    instructions_speciales TEXT,
    FOREIGN KEY (patient_id) REFERENCES patients(id),
    FOREIGN KEY (medicament_id) REFERENCES medicaments(id),
    FOREIGN KEY (medecin_id) REFERENCES users(id)
);

-- Table des administrations de médicaments
CREATE TABLE IF NOT EXISTS administrations_medicaments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    prescription_id INT NOT NULL,
    patient_id INT NOT NULL,
    medicament_id INT NOT NULL,
    dose_administree VARCHAR(100),
    heure_administration DATETIME NOT NULL,
    infirmier_id INT NOT NULL,
    observations TEXT,
    effets_secondaires TEXT,
    FOREIGN KEY (prescription_id) REFERENCES prescriptions_hospitalisation(id),
    FOREIGN KEY (patient_id) REFERENCES patients(id),
    FOREIGN KEY (medicament_id) REFERENCES medicaments(id),
    FOREIGN KEY (infirmier_id) REFERENCES users(id)
);

-- Table des soins planifiés
CREATE TABLE IF NOT EXISTS soins_planifies (
    id INT PRIMARY KEY AUTO_INCREMENT,
    patient_id INT NOT NULL,
    type_soin VARCHAR(100) NOT NULL,
    description TEXT,
    heure_prevue DATETIME NOT NULL,
    heure_realisation DATETIME NULL,
    statut ENUM('planifie', 'en_cours', 'realise', 'annule') DEFAULT 'planifie',
    prescripteur_id INT,
    executant_id INT,
    observations TEXT,
    FOREIGN KEY (patient_id) REFERENCES patients(id),
    FOREIGN KEY (prescripteur_id) REFERENCES users(id),
    FOREIGN KEY (executant_id) REFERENCES users(id)
);

-- Insérer quelques services par défaut
INSERT IGNORE INTO services (id, nom, specialite) VALUES
(1, 'Médecine Interne', 'Médecine générale'),
(2, 'Chirurgie', 'Chirurgie générale'),
(3, 'Pédiatrie', 'Pédiatrie'),
(4, 'Cardiologie', 'Cardiologie'),
(5, 'Urgences', 'Médecine d\'urgence');