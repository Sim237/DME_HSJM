USE dme_hospital;

-- Table pour les décisions d'hospitalisation
CREATE TABLE IF NOT EXISTS decisions_hospitalisation (
    id INT PRIMARY KEY AUTO_INCREMENT,
    consultation_id INT NOT NULL,
    medecin_id INT NOT NULL,
    decision ENUM('hospitalisation_urgente', 'hospitalisation_programmee', 'surveillance_renforcee', 'suivi_ambulatoire') NOT NULL,
    justification TEXT,
    date_decision DATETIME NOT NULL,
    statut ENUM('en_attente', 'executee', 'annulee') DEFAULT 'en_attente',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (consultation_id) REFERENCES consultations(id) ON DELETE CASCADE,
    FOREIGN KEY (medecin_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_consultation_decision (consultation_id)
);

-- Ajouter colonnes manquantes à la table consultations
ALTER TABLE consultations 
ADD COLUMN taille DECIMAL(5,2) COMMENT 'Taille en cm';

ALTER TABLE consultations 
ADD COLUMN tension_systolique INT COMMENT 'Tension systolique en mmHg';

ALTER TABLE consultations 
ADD COLUMN tension_diastolique INT COMMENT 'Tension diastolique en mmHg';