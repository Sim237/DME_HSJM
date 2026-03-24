-- Table pour la banque de sang
CREATE TABLE IF NOT EXISTS `banque_sang` (
  `id` int NOT NULL AUTO_INCREMENT,
  `groupe_sanguin` enum('A','B','AB','O') NOT NULL,
  `rhesus` enum('+','-') NOT NULL,
  `quantite_ml` int NOT NULL DEFAULT 0,
  `quantite_poches` int NOT NULL DEFAULT 0,
  `date_derniere_maj` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_groupe_rhesus` (`groupe_sanguin`, `rhesus`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Données initiales banque de sang
INSERT INTO `banque_sang` (`groupe_sanguin`, `rhesus`, `quantite_ml`, `quantite_poches`) VALUES
('A', '+', 2500, 5),
('A', '-', 1000, 2),
('B', '+', 1500, 3),
('B', '-', 500, 1),
('AB', '+', 800, 2),
('AB', '-', 200, 1),
('O', '+', 3000, 6),
('O', '-', 1200, 3);

-- Trigger pour mise à jour automatique après donation
DELIMITER $$
CREATE TRIGGER after_donneur_sang_insert 
AFTER INSERT ON registre_donneurs_sang
FOR EACH ROW
BEGIN
    -- Ajouter 450ml (1 poche) à la banque
    INSERT INTO banque_sang (groupe_sanguin, rhesus, quantite_ml, quantite_poches)
    VALUES (NEW.groupe_sanguin, NEW.rhesus, 450, 1)
    ON DUPLICATE KEY UPDATE 
        quantite_ml = quantite_ml + 450,
        quantite_poches = quantite_poches + 1;
END$$
DELIMITER ;