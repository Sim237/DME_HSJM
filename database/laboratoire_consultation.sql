-- Tables pour la connexion laboratoire-consultation

-- Table des demandes d'examens laboratoire
CREATE TABLE IF NOT EXISTS demandes_laboratoire (
    id INT AUTO_INCREMENT PRIMARY KEY,
    consultation_id INT NOT NULL,
    statut ENUM('EN_ATTENTE', 'PRELEVEMENTS_EFFECTUES', 'EN_ANALYSE', 'RESULTATS_PRETS', 'VALIDES') DEFAULT 'EN_ATTENTE',
    date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    date_prelevement TIMESTAMP NULL,
    date_resultats TIMESTAMP NULL,
    technicien_id INT NULL,
    biologiste_id INT NULL,
    notes TEXT,
    FOREIGN KEY (consultation_id) REFERENCES consultations(id),
    FOREIGN KEY (technicien_id) REFERENCES users(id),
    FOREIGN KEY (biologiste_id) REFERENCES users(id)
);

-- Table des examens de la demande
CREATE TABLE IF NOT EXISTS demande_examens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    demande_id INT NOT NULL,
    examen_id INT NOT NULL,
    urgent BOOLEAN DEFAULT FALSE,
    a_jeun BOOLEAN DEFAULT FALSE,
    instructions TEXT,
    statut ENUM('EN_ATTENTE', 'PRELEVE', 'EN_COURS', 'TERMINE') DEFAULT 'EN_ATTENTE',
    resultat TEXT,
    valeur_numerique DECIMAL(10,3) NULL,
    unite VARCHAR(50),
    valeur_normale_min DECIMAL(10,3) NULL,
    valeur_normale_max DECIMAL(10,3) NULL,
    interpretation TEXT,
    FOREIGN KEY (demande_id) REFERENCES demandes_laboratoire(id),
    FOREIGN KEY (examen_id) REFERENCES examens_laboratoire(id)
);

-- Table des examens disponibles
CREATE TABLE IF NOT EXISTS examens_laboratoire (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) UNIQUE,
    nom VARCHAR(255) NOT NULL,
    categorie ENUM('HEMATOLOGIE', 'BIOCHIMIE', 'IMMUNOLOGIE', 'MICROBIOLOGIE', 'PARASITOLOGIE', 'AUTRE') DEFAULT 'BIOCHIMIE',
    type_prelevement ENUM('SANG', 'URINE', 'SELLES', 'LCR', 'AUTRE') DEFAULT 'SANG',
    delai_rendu_heures INT DEFAULT 24,
    a_jeun_requis BOOLEAN DEFAULT FALSE,
    prix DECIMAL(10,2) DEFAULT 0,
    disponible BOOLEAN DEFAULT TRUE,
    valeur_normale_min DECIMAL(10,3) NULL,
    valeur_normale_max DECIMAL(10,3) NULL,
    unite VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insérer quelques examens de test
INSERT IGNORE INTO examens_laboratoire (code, nom, categorie, type_prelevement, delai_rendu_heures, a_jeun_requis, valeur_normale_min, valeur_normale_max, unite) VALUES
('NFS', 'Numération Formule Sanguine', 'HEMATOLOGIE', 'SANG', 2, FALSE, NULL, NULL, NULL),
('GLYC', 'Glycémie à jeun', 'BIOCHIMIE', 'SANG', 1, TRUE, 0.7, 1.1, 'g/L'),
('CREA', 'Créatininémie', 'BIOCHIMIE', 'SANG', 2, FALSE, 7, 13, 'mg/L'),
('UREE', 'Urée sanguine', 'BIOCHIMIE', 'SANG', 2, FALSE, 0.15, 0.45, 'g/L'),
('CRP', 'Protéine C Réactive', 'IMMUNOLOGIE', 'SANG', 1, FALSE, 0, 5, 'mg/L'),
('VS', 'Vitesse de Sédimentation', 'HEMATOLOGIE', 'SANG', 1, FALSE, 2, 15, 'mm'),
('ECBU', 'Examen Cytobactériologique des Urines', 'MICROBIOLOGIE', 'URINE', 48, FALSE, NULL, NULL, NULL),
('TP', 'Taux de Prothrombine', 'HEMATOLOGIE', 'SANG', 2, FALSE, 70, 100, '%'),
('ALAT', 'Alanine Aminotransférase', 'BIOCHIMIE', 'SANG', 4, FALSE, 10, 40, 'UI/L'),
('ASAT', 'Aspartate Aminotransférase', 'BIOCHIMIE', 'SANG', 4, FALSE, 10, 35, 'UI/L');