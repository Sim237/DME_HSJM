-- Table pour les signatures et cachets électroniques
CREATE TABLE IF NOT EXISTS medecin_signatures (
    id INT AUTO_INCREMENT PRIMARY KEY,
    medecin_id INT NOT NULL,
    signature_image LONGTEXT NOT NULL,
    cachet_image LONGTEXT,
    numero_ordre VARCHAR(100),
    specialite VARCHAR(100),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (medecin_id) REFERENCES users(id)
);

-- Table pour tracer les documents signés
CREATE TABLE IF NOT EXISTS documents_signes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    document_type ENUM('ORDONNANCE', 'CERTIFICAT', 'RAPPORT', 'AUTRE') NOT NULL,
    document_id INT NOT NULL,
    medecin_id INT NOT NULL,
    signature_hash VARCHAR(255) NOT NULL,
    signed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ip_address VARCHAR(45),
    FOREIGN KEY (medecin_id) REFERENCES users(id)
);
