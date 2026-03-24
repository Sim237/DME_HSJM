-- Script d'initialisation complète pour le système laboratoire-consultation

-- 1. Tables laboratoire-consultation
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

-- 2. Tables notifications
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

-- 3. Tables pour les kits de médicaments
CREATE TABLE IF NOT EXISTS prescription_kits (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(255) NOT NULL,
    description TEXT,
    specialite VARCHAR(100),
    actif BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS prescription_kit_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kit_id INT NOT NULL,
    medicament_id INT NOT NULL,
    posologie VARCHAR(255),
    duree VARCHAR(100),
    quantite INT DEFAULT 1,
    FOREIGN KEY (kit_id) REFERENCES prescription_kits(id),
    FOREIGN KEY (medicament_id) REFERENCES medicaments(id)
);

-- 4. Données de test kits
INSERT IGNORE INTO prescription_kits (nom, description, specialite) VALUES
('Kit Infection Respiratoire', 'Traitement standard infection des voies respiratoires', 'Médecine Générale'),
('Kit Gastro-entérite', 'Traitement symptomatique gastro-entérite', 'Médecine Générale'),
('Kit Douleur/Fièvre', 'Antalgiques et antipyrétiques de base', 'Médecine Générale'),
('Kit Hypertension', 'Traitement de base hypertension artérielle', 'Cardiologie'),
('Kit Diabète Type 2', 'Traitement initial diabète type 2', 'Endocrinologie');

-- Insérer items des kits (en supposant que les médicaments existent)
INSERT IGNORE INTO prescription_kit_items (kit_id, medicament_id, posologie, duree, quantite) VALUES
-- Kit Infection Respiratoire (ID 1)
(1, 2, '1g matin et soir', '7 jours', 14),  -- Amoxicilline
(1, 1, '500mg si fièvre', '5 jours', 10),   -- Paracétamol
-- Kit Gastro-entérite (ID 2) 
(2, 4, '1 cp 3 fois/jour', '3 jours', 9),    -- Spasfon
(2, 1, '500mg si douleur', '3 jours', 6),   -- Paracétamol
-- Kit Douleur/Fièvre (ID 3)
(3, 1, '500mg toutes les 6h', '5 jours', 20), -- Paracétamol
(3, 3, '400mg matin et soir', '3 jours', 6);  -- Ibuprofène

-- 5. Données de test examens
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