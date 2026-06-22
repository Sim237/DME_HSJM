-- Stockage unifié des formulaires infirmiers remplis
CREATE TABLE IF NOT EXISTS `formulaires_data` (
  `id`              INT NOT NULL AUTO_INCREMENT,
  `patient_id`      INT NOT NULL,
  `hosp_id`         INT DEFAULT NULL,
  `user_id`         INT NOT NULL,
  `service_id`      INT DEFAULT NULL,
  `type_formulaire` VARCHAR(60) NOT NULL,
  `titre`           VARCHAR(120) NOT NULL,
  `data`            JSON NOT NULL COMMENT 'Champs du formulaire en JSON',
  `statut`          ENUM('BROUILLON','SOUMIS','SIGNE') DEFAULT 'BROUILLON',
  `date_creation`   DATETIME DEFAULT CURRENT_TIMESTAMP,
  `date_modification` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `patient_id`      (`patient_id`),
  KEY `user_id`         (`user_id`),
  KEY `type_formulaire` (`type_formulaire`),
  CONSTRAINT `fk_fd_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_fd_user`    FOREIGN KEY (`user_id`)    REFERENCES `users`    (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
