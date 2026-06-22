-- Migration : table des transferts inter-services
-- À exécuter une seule fois sur la base de données

CREATE TABLE IF NOT EXISTS `transferts_patients` (
  `id`                     INT NOT NULL AUTO_INCREMENT,
  `patient_id`             INT NOT NULL,
  `service_origine_id`     INT DEFAULT NULL,
  `service_destination_id` INT NOT NULL,
  `lit_destination_id`     INT DEFAULT NULL,
  `hospitalisation_id`     INT DEFAULT NULL,
  `motif`                  TEXT,
  `infirmier_id`           INT DEFAULT NULL,
  `medecin_id`             INT DEFAULT NULL,
  `date_transfert`         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_transfert_patient` (`patient_id`),
  KEY `idx_transfert_date`    (`date_transfert`),
  CONSTRAINT `fk_transfert_patient`  FOREIGN KEY (`patient_id`)             REFERENCES `patients`        (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_transfert_serv_orig` FOREIGN KEY (`service_origine_id`)    REFERENCES `services`        (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_transfert_serv_dest` FOREIGN KEY (`service_destination_id`) REFERENCES `services`       (`id`),
  CONSTRAINT `fk_transfert_lit`       FOREIGN KEY (`lit_destination_id`)    REFERENCES `lits`            (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_transfert_hosp`      FOREIGN KEY (`hospitalisation_id`)    REFERENCES `hospitalisations`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
