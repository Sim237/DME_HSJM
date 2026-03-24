-- Table imagerie médicale
CREATE TABLE IF NOT EXISTS imagerie_medicale (
    id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    consultation_id INT NULL,
    type_examen ENUM('radiographie', 'scanner', 'irm', 'echographie', 'mammographie', 'autre') NOT NULL,
    partie_corps VARCHAR(100) NOT NULL,
    description TEXT,
    date_examen DATETIME NOT NULL,
    medecin_prescripteur INT NOT NULL,
    technicien_id INT NULL,
    statut ENUM('programme', 'en_cours', 'termine', 'interprete', 'valide') DEFAULT 'programme',
    urgence BOOLEAN DEFAULT FALSE,
    fichier_dicom VARCHAR(255),
    fichier_preview VARCHAR(255),
    taille_fichier INT,
    nombre_images INT DEFAULT 1,
    interpretation TEXT,
    conclusion TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patients(id),
    FOREIGN KEY (consultation_id) REFERENCES consultations(id),
    FOREIGN KEY (medecin_prescripteur) REFERENCES users(id),
    FOREIGN KEY (technicien_id) REFERENCES users(id)
);

-- Table métadonnées DICOM
CREATE TABLE IF NOT EXISTS dicom_metadata (
    id INT AUTO_INCREMENT PRIMARY KEY,
    imagerie_id INT NOT NULL,
    study_uid VARCHAR(255),
    series_uid VARCHAR(255),
    instance_uid VARCHAR(255),
    modality VARCHAR(10),
    study_date DATE,
    study_time TIME,
    patient_name VARCHAR(255),
    patient_id_dicom VARCHAR(100),
    institution_name VARCHAR(255),
    manufacturer VARCHAR(100),
    model_name VARCHAR(100),
    slice_thickness DECIMAL(10,3),
    pixel_spacing VARCHAR(50),
    window_center INT,
    window_width INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (imagerie_id) REFERENCES imagerie_medicale(id)
);

-- Données de test
INSERT INTO imagerie_medicale (patient_id, type_examen, partie_corps, description, date_examen, medecin_prescripteur, statut, fichier_preview) VALUES
(1, 'radiographie', 'Thorax', 'Radiographie pulmonaire de contrôle', '2024-12-20 10:00:00', (SELECT id FROM users WHERE username = 'admin'), 'termine', 'chest_xray_preview.jpg'),
(2, 'scanner', 'Crâne', 'Scanner cérébral sans injection', '2024-12-20 14:30:00', (SELECT id FROM users WHERE username = 'admin'), 'interprete', 'brain_ct_preview.jpg'),
(1, 'irm', 'Genou droit', 'IRM genou suite traumatisme', '2024-12-21 09:15:00', (SELECT id FROM users WHERE username = 'admin'), 'programme', null);