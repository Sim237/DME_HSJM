USE dme_hospital;

-- Chat médical sécurisé
CREATE TABLE IF NOT EXISTS chat_medical (
    id INT PRIMARY KEY AUTO_INCREMENT,
    expediteur_id INT NOT NULL,
    destinataire_id INT NOT NULL,
    patient_id INT,
    message TEXT NOT NULL,
    type ENUM('text', 'image', 'document') DEFAULT 'text',
    fichier_path VARCHAR(255),
    lu BOOLEAN DEFAULT FALSE,
    urgent BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (expediteur_id) REFERENCES users(id),
    FOREIGN KEY (destinataire_id) REFERENCES users(id),
    FOREIGN KEY (patient_id) REFERENCES patients(id)
);

-- Gestion famille - Membres famille
CREATE TABLE IF NOT EXISTS famille_membres (
    id INT PRIMARY KEY AUTO_INCREMENT,
    patient_id INT NOT NULL,
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100) NOT NULL,
    relation ENUM('pere', 'mere', 'conjoint', 'enfant', 'frere', 'soeur', 'autre') NOT NULL,
    telephone VARCHAR(20),
    email VARCHAR(100),
    contact_urgence BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patients(id)
);

-- Gestion famille - Visites
CREATE TABLE IF NOT EXISTS famille_visites (
    id INT PRIMARY KEY AUTO_INCREMENT,
    patient_id INT NOT NULL,
    visiteur_id INT,
    nom_visiteur VARCHAR(100),
    relation VARCHAR(50),
    date_visite DATETIME NOT NULL,
    duree_minutes INT DEFAULT 30,
    autorise BOOLEAN DEFAULT TRUE,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patients(id),
    FOREIGN KEY (visiteur_id) REFERENCES famille_membres(id)
);

-- Module formation personnel
CREATE TABLE IF NOT EXISTS formations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    titre VARCHAR(200) NOT NULL,
    description TEXT,
    categorie ENUM('medical', 'technique', 'securite', 'qualite', 'autre') NOT NULL,
    duree_heures INT NOT NULL,
    obligatoire BOOLEAN DEFAULT FALSE,
    date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS formation_sessions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    formation_id INT NOT NULL,
    formateur_id INT,
    date_debut DATETIME NOT NULL,
    date_fin DATETIME NOT NULL,
    lieu VARCHAR(100),
    places_max INT DEFAULT 20,
    statut ENUM('planifie', 'en_cours', 'termine', 'annule') DEFAULT 'planifie',
    FOREIGN KEY (formation_id) REFERENCES formations(id),
    FOREIGN KEY (formateur_id) REFERENCES users(id)
);

CREATE TABLE IF NOT EXISTS formation_inscriptions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    session_id INT NOT NULL,
    user_id INT NOT NULL,
    statut ENUM('inscrit', 'present', 'absent', 'valide') DEFAULT 'inscrit',
    note DECIMAL(4,2),
    certificat_genere BOOLEAN DEFAULT FALSE,
    date_inscription TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (session_id) REFERENCES formation_sessions(id),
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Index pour performances
CREATE INDEX idx_chat_destinataire ON chat_medical(destinataire_id, lu);
CREATE INDEX idx_famille_patient ON famille_membres(patient_id);
CREATE INDEX idx_visites_patient_date ON famille_visites(patient_id, date_visite);
CREATE INDEX idx_formation_user ON formation_inscriptions(user_id);