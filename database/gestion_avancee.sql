-- FACTURATION
CREATE TABLE IF NOT EXISTS tarifs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(20) UNIQUE NOT NULL,
    libelle VARCHAR(255) NOT NULL,
    prix DECIMAL(10,2) NOT NULL,
    type_acte ENUM('consultation', 'examen', 'intervention', 'medicament', 'hospitalisation') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS factures (
    id INT AUTO_INCREMENT PRIMARY KEY,
    numero_facture VARCHAR(50) UNIQUE NOT NULL,
    patient_id INT NOT NULL,
    consultation_id INT NULL,
    date_facture DATE NOT NULL,
    montant_ht DECIMAL(10,2) NOT NULL,
    taux_tva DECIMAL(5,2) DEFAULT 0,
    montant_ttc DECIMAL(10,2) NOT NULL,
    statut ENUM('brouillon', 'emise', 'payee', 'annulee') DEFAULT 'brouillon',
    mode_paiement ENUM('especes', 'carte', 'cheque', 'virement', 'assurance') NULL,
    date_paiement DATE NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patients(id),
    FOREIGN KEY (consultation_id) REFERENCES consultations(id)
);

CREATE TABLE IF NOT EXISTS facture_lignes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    facture_id INT NOT NULL,
    tarif_id INT NOT NULL,
    quantite INT DEFAULT 1,
    prix_unitaire DECIMAL(10,2) NOT NULL,
    montant DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (facture_id) REFERENCES factures(id),
    FOREIGN KEY (tarif_id) REFERENCES tarifs(id)
);

-- GESTION STOCK
CREATE TABLE IF NOT EXISTS stock_medicaments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(255) NOT NULL,
    code_barre VARCHAR(50),
    stock_actuel INT NOT NULL DEFAULT 0,
    stock_minimum INT NOT NULL DEFAULT 10,
    stock_maximum INT NOT NULL DEFAULT 100,
    prix_achat DECIMAL(10,2),
    prix_vente DECIMAL(10,2),
    date_expiration DATE,
    fournisseur VARCHAR(255),
    emplacement VARCHAR(100),
    statut ENUM('actif', 'perime', 'rupture') DEFAULT 'actif',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS mouvements_stock (
    id INT AUTO_INCREMENT PRIMARY KEY,
    medicament_id INT NOT NULL,
    type_mouvement ENUM('entree', 'sortie', 'ajustement') NOT NULL,
    quantite INT NOT NULL,
    motif VARCHAR(255),
    user_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (medicament_id) REFERENCES stock_medicaments(id),
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- PLANNING PERSONNEL
CREATE TABLE IF NOT EXISTS planning_personnel (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    type_planning ENUM('garde', 'conge', 'formation', 'maladie') NOT NULL,
    date_debut DATE NOT NULL,
    date_fin DATE NOT NULL,
    heure_debut TIME NULL,
    heure_fin TIME NULL,
    statut ENUM('planifie', 'confirme', 'annule') DEFAULT 'planifie',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- DONNÉES DE TEST
INSERT IGNORE INTO tarifs (code, libelle, prix, type_acte) VALUES
('CONS_GEN', 'Consultation générale', 25000, 'consultation'),
('CONS_SPEC', 'Consultation spécialisée', 35000, 'consultation'),
('RADIO_THOR', 'Radiographie thorax', 15000, 'examen'),
('SCAN_CRANE', 'Scanner crâne', 75000, 'examen'),
('HOSPIT_J', 'Hospitalisation par jour', 50000, 'hospitalisation');

INSERT IGNORE INTO stock_medicaments (nom, stock_actuel, stock_minimum, prix_vente) VALUES
('Paracétamol 500mg', 150, 20, 500),
('Amoxicilline 1g', 80, 15, 1200),
('Aspirine 100mg', 200, 25, 300),
('Doliprane 1000mg', 45, 30, 800);

INSERT IGNORE INTO planning_personnel (user_id, type_planning, date_debut, date_fin, heure_debut, heure_fin) VALUES
((SELECT id FROM users WHERE username = 'admin'), 'garde', '2024-12-21', '2024-12-21', '08:00:00', '20:00:00'),
((SELECT id FROM users WHERE username = 'admin'), 'conge', '2024-12-25', '2024-12-27', NULL, NULL);