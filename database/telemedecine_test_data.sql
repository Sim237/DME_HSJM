USE dme_hospital;

-- Insérer quelques consultations de test
INSERT IGNORE INTO telemedecine_consultations (id, patient_id, medecin_id, type, statut, date_consultation, motif) VALUES
(1, 1, 9, 'video', 'planifie', '2024-01-15 14:30:00', 'Suivi post-opératoire'),
(2, 2, 9, 'video', 'en_cours', '2024-01-15 15:00:00', 'Consultation de contrôle'),
(3, 1, 9, 'audio', 'planifie', '2024-01-15 16:00:00', 'Résultats d\'examens');

-- Insérer quelques données de surveillance
INSERT IGNORE INTO telemedecine_surveillance (patient_id, medecin_id, type_donnee, valeur, unite, date_mesure, alerte) VALUES
(1, 9, 'tension', 160.00, 'mmHg', NOW(), TRUE),
(1, 9, 'glycemie', 0.9, 'g/L', NOW(), FALSE),
(2, 9, 'temperature', 38.5, '°C', NOW(), TRUE);