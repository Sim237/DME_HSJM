-- Migration : table des pièces jointes pour les résultats de laboratoire
-- Permet au technicien d'attacher des fichiers (PDF, images) aux résultats
-- transmis au médecin (ex: scan d'un résultat imprimé depuis l'analyseur).

CREATE TABLE IF NOT EXISTS demande_pieces_jointes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    demande_id INT NOT NULL,
    examen_id INT NULL COMMENT 'NULL = piece globale à la demande, sinon ID de demande_examens',
    fichier_nom_original VARCHAR(255) NOT NULL COMMENT 'Nom du fichier choisi par l'utilisateur',
    fichier_chemin VARCHAR(500) NOT NULL COMMENT 'Chemin relatif sur disque (uploads/laboratoire/...)',
    fichier_taille INT DEFAULT NULL COMMENT 'Taille en octets',
    fichier_type VARCHAR(100) DEFAULT NULL COMMENT 'MIME type (image/png, application/pdf, ...)',
    description TEXT DEFAULT NULL,
    uploade_par INT DEFAULT NULL,
    uploade_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    KEY idx_dpj_demande (demande_id),
    KEY idx_dpj_examen  (examen_id),
    KEY idx_dpj_user    (uploade_par),
    CONSTRAINT fk_dpj_demande FOREIGN KEY (demande_id)  REFERENCES demandes_laboratoire(id) ON DELETE CASCADE,
    CONSTRAINT fk_dpj_examen  FOREIGN KEY (examen_id)   REFERENCES demande_examens(id)     ON DELETE SET NULL,
    CONSTRAINT fk_dpj_user    FOREIGN KEY (uploade_par) REFERENCES users(id)               ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
