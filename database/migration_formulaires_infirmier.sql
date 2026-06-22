-- Migration : formulaires soumis par l'infirmier pour signature médecin
CREATE TABLE IF NOT EXISTS `formulaires_soumis` (
  `id`              INT NOT NULL AUTO_INCREMENT,
  `patient_id`      INT NOT NULL,
  `hosp_id`         INT DEFAULT NULL,
  `infirmier_id`    INT NOT NULL,
  `medecin_id`      INT DEFAULT NULL,
  `service_id`      INT DEFAULT NULL,
  `type_formulaire` VARCHAR(60) NOT NULL,
  `titre`           VARCHAR(120) NOT NULL,
  `statut`          ENUM('SOUMIS','VU','SIGNE','REFUSE') DEFAULT 'SOUMIS',
  `date_soumission` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `date_action`     DATETIME DEFAULT NULL,
  `note_medecin`    TEXT DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `patient_id`   (`patient_id`),
  KEY `infirmier_id` (`infirmier_id`),
  KEY `medecin_id`   (`medecin_id`),
  KEY `service_id`   (`service_id`),
  CONSTRAINT `fk_fs_patient`   FOREIGN KEY (`patient_id`)   REFERENCES `patients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_fs_infirmier` FOREIGN KEY (`infirmier_id`) REFERENCES `users`    (`id`),
  CONSTRAINT `fk_fs_medecin`   FOREIGN KEY (`medecin_id`)   REFERENCES `users`    (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
