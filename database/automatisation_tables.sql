USE dme_hospital;

-- Table pour les notifications automatiques
CREATE TABLE IF NOT EXISTS notifications_automatiques (
    id INT PRIMARY KEY AUTO_INCREMENT,
    patient_id INT NOT NULL,
    type ENUM('RAPPEL_TRAITEMENT', 'RAPPEL_CONSTANTES', 'ALERTE_CRITIQUE') NOT NULL,
    message TEXT NOT NULL,
    date_creation DATETIME NOT NULL,
    date_lecture DATETIME NULL,
    statut ENUM('active', 'lue', 'archivee') DEFAULT 'active',
    FOREIGN KEY (patient_id) REFERENCES patients(id)
);

-- Table pour les scores de gravité
CREATE TABLE IF NOT EXISTS scores_gravite (
    id INT PRIMARY KEY AUTO_INCREMENT,
    patient_id INT NOT NULL,
    type_score ENUM('NEWS', 'GLASGOW', 'CHARLSON', 'APACHE') NOT NULL,
    valeur INT NOT NULL,
    date_calcul DATETIME NOT NULL,
    calculateur_id INT,
    FOREIGN KEY (patient_id) REFERENCES patients(id),
    FOREIGN KEY (calculateur_id) REFERENCES users(id)
);

-- Table pour les alertes prédictives
CREATE TABLE IF NOT EXISTS alertes_predictives (
    id INT PRIMARY KEY AUTO_INCREMENT,
    patient_id INT NOT NULL,
    type ENUM('PREDICTIVE', 'VARIATION_BRUTALE', 'CHUTE_TENSION', 'DETERIORATION') NOT NULL,
    niveau ENUM('INFO', 'ATTENTION', 'URGENT', 'CRITIQUE') NOT NULL,
    message TEXT NOT NULL,
    probabilite DECIMAL(5,2),
    action TEXT,
    date_creation DATETIME NOT NULL,
    date_resolution DATETIME NULL,
    statut ENUM('active', 'resolue', 'ignoree') DEFAULT 'active',
    FOREIGN KEY (patient_id) REFERENCES patients(id)
);

-- Table pour le planning infirmier
CREATE TABLE IF NOT EXISTS planning_infirmier (
    id INT PRIMARY KEY AUTO_INCREMENT,
    service_id INT NOT NULL,
    infirmier_id INT NOT NULL,
    patient_id INT NOT NULL,
    date_planning DATE NOT NULL,
    heure_debut TIME NOT NULL,
    heure_fin TIME NOT NULL,
    type_activite ENUM('TRAITEMENT', 'SOIN', 'CONSTANTES', 'SURVEILLANCE') NOT NULL,
    description TEXT,
    statut ENUM('planifie', 'en_cours', 'termine', 'reporte') DEFAULT 'planifie',
    duree_estimee INT, -- en minutes
    duree_reelle INT, -- en minutes
    FOREIGN KEY (service_id) REFERENCES services(id),
    FOREIGN KEY (infirmier_id) REFERENCES users(id),
    FOREIGN KEY (patient_id) REFERENCES patients(id)
);

-- Ajouter colonne durée estimée aux soins planifiés
ALTER TABLE soins_planifies 
ADD COLUMN duree_estimee INT DEFAULT 30 COMMENT 'Durée estimée en minutes';

-- Index pour optimiser les performances
CREATE INDEX idx_notifications_patient_statut ON notifications_automatiques(patient_id, statut);
CREATE INDEX idx_scores_patient_type ON scores_gravite(patient_id, type_score);
CREATE INDEX idx_alertes_patient_statut ON alertes_predictives(patient_id, statut);
CREATE INDEX idx_planning_service_date ON planning_infirmier(service_id, date_planning);