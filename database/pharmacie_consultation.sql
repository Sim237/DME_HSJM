-- Tables pour la connexion pharmacie-consultation

-- Table des ordonnances pharmacie
CREATE TABLE IF NOT EXISTS ordonnances_pharmacie (
    id INT AUTO_INCREMENT PRIMARY KEY,
    consultation_id INT NOT NULL,
    statut ENUM('EN_ATTENTE', 'EN_COURS', 'TERMINEE', 'PARTIELLEMENT_SERVIE') DEFAULT 'EN_ATTENTE',
    date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    date_traitement TIMESTAMP NULL,
    pharmacien_id INT NULL,
    notes TEXT,
    FOREIGN KEY (consultation_id) REFERENCES consultations(id),
    FOREIGN KEY (pharmacien_id) REFERENCES users(id)
);

-- Table des médicaments de l'ordonnance avec vérification stock
CREATE TABLE IF NOT EXISTS ordonnance_medicaments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ordonnance_id INT NOT NULL,
    medicament_id INT NOT NULL,
    quantite INT NOT NULL,
    posologie VARCHAR(255),
    duree VARCHAR(100),
    disponible BOOLEAN DEFAULT FALSE,
    quantite_servie INT DEFAULT 0,
    message_stock TEXT,
    FOREIGN KEY (ordonnance_id) REFERENCES ordonnances_pharmacie(id),
    FOREIGN KEY (medicament_id) REFERENCES medicaments(id)
);

-- Table des médicaments (si elle n'existe pas)
CREATE TABLE IF NOT EXISTS medicaments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) UNIQUE,
    nom VARCHAR(255) NOT NULL,
    forme VARCHAR(100),
    dosage VARCHAR(100),
    quantite INT DEFAULT 0,
    unite VARCHAR(50) DEFAULT 'unités',
    seuil_alerte INT DEFAULT 10,
    prix_unitaire DECIMAL(10,2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insérer quelques médicaments de test
INSERT IGNORE INTO medicaments (code, nom, forme, dosage, quantite, unite, seuil_alerte) VALUES
('PARA500', 'Paracétamol', 'Comprimé', '500mg', 100, 'comprimés', 20),
('AMOX1G', 'Amoxicilline', 'Gélule', '1g', 50, 'gélules', 15),
('IBU400', 'Ibuprofène', 'Comprimé', '400mg', 75, 'comprimés', 25),
('SPAS', 'Spasfon', 'Comprimé', '80mg', 30, 'comprimés', 10),
('DOLIP1000', 'Doliprane', 'Comprimé', '1000mg', 80, 'comprimés', 20);