-- Tables et données de test pour les registres DME Hospital

-- Registre des donneurs de sang
CREATE TABLE IF NOT EXISTS `registre_donneurs_sang` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nom` varchar(100) NOT NULL,
  `prenom` varchar(100) NOT NULL,
  `date_naissance` date NOT NULL,
  `sexe` enum('M','F') NOT NULL,
  `groupe_sanguin` enum('A','B','AB','O') NOT NULL,
  `rhesus` enum('+','-') NOT NULL,
  `telephone` varchar(20) NOT NULL,
  `email` varchar(150) DEFAULT NULL,
  `adresse` text,
  `date_inscription` datetime DEFAULT CURRENT_TIMESTAMP,
  `statut` enum('ACTIF','SUSPENDU','INACTIF') DEFAULT 'ACTIF',
  `derniere_donation` date DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Données de test donneurs de sang
INSERT INTO `registre_donneurs_sang` (`nom`, `prenom`, `date_naissance`, `sexe`, `groupe_sanguin`, `rhesus`, `telephone`, `email`, `adresse`, `derniere_donation`) VALUES
('MARTIN', 'Jean', '1985-03-15', 'M', 'O', '+', '699123456', 'jean.martin@email.com', 'Yaoundé', '2024-10-15'),
('DUPONT', 'Marie', '1990-07-22', 'F', 'A', '+', '677234567', 'marie.dupont@email.com', 'Douala', '2024-11-01'),
('BERNARD', 'Paul', '1988-12-10', 'M', 'B', '-', '655345678', 'paul.bernard@email.com', 'Bafoussam', NULL),
('LAMBERT', 'Sophie', '1992-05-18', 'F', 'AB', '+', '698456789', 'sophie.lambert@email.com', 'Garoua', '2024-09-20'),
('ROUSSEAU', 'Pierre', '1987-09-03', 'M', 'O', '-', '676567890', 'pierre.rousseau@email.com', 'Bamenda', '2024-11-10');

-- Registre des donneurs CSH
CREATE TABLE IF NOT EXISTS `registre_donneurs_csh` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nom` varchar(100) NOT NULL,
  `prenom` varchar(100) NOT NULL,
  `date_naissance` date NOT NULL,
  `sexe` enum('M','F') NOT NULL,
  `hla_typing` text,
  `telephone` varchar(20) NOT NULL,
  `email` varchar(150) DEFAULT NULL,
  `adresse` text,
  `date_inscription` datetime DEFAULT CURRENT_TIMESTAMP,
  `statut` enum('ACTIF','SUSPENDU','INACTIF') DEFAULT 'ACTIF',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Données de test donneurs CSH
INSERT INTO `registre_donneurs_csh` (`nom`, `prenom`, `date_naissance`, `sexe`, `hla_typing`, `telephone`, `email`, `adresse`) VALUES
('DURAND', 'Michel', '1983-04-12', 'M', 'HLA-A*02:01, HLA-B*07:02, HLA-C*07:02', '699111222', 'michel.durand@email.com', 'Yaoundé'),
('MOREAU', 'Claire', '1991-08-25', 'F', 'HLA-A*01:01, HLA-B*08:01, HLA-C*07:01', '677333444', 'claire.moreau@email.com', 'Douala'),
('LEROY', 'Antoine', '1986-11-30', 'M', 'HLA-A*03:01, HLA-B*35:01, HLA-C*04:01', '655555666', 'antoine.leroy@email.com', 'Bafoussam');

-- Registre des receveurs CSH
CREATE TABLE IF NOT EXISTS `registre_receveurs_csh` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nom` varchar(100) NOT NULL,
  `prenom` varchar(100) NOT NULL,
  `date_naissance` date NOT NULL,
  `sexe` enum('M','F') NOT NULL,
  `hla_typing` text,
  `pathologie` varchar(255) NOT NULL,
  `urgence` enum('FAIBLE','MOYENNE','HAUTE','CRITIQUE') DEFAULT 'MOYENNE',
  `telephone` varchar(20) NOT NULL,
  `email` varchar(150) DEFAULT NULL,
  `date_inscription` datetime DEFAULT CURRENT_TIMESTAMP,
  `statut` enum('EN_ATTENTE','GREFFE_REALISEE','SUSPENDU') DEFAULT 'EN_ATTENTE',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Données de test receveurs CSH
INSERT INTO `registre_receveurs_csh` (`nom`, `prenom`, `date_naissance`, `sexe`, `hla_typing`, `pathologie`, `urgence`, `telephone`, `email`) VALUES
('GARCIA', 'Luis', '1995-02-14', 'M', 'HLA-A*02:01, HLA-B*07:02, HLA-C*07:02', 'Leucémie aiguë lymphoblastique', 'HAUTE', '698777888', 'luis.garcia@email.com'),
('PETIT', 'Emma', '1989-06-08', 'F', 'HLA-A*01:01, HLA-B*08:01, HLA-C*07:01', 'Aplasie médullaire', 'CRITIQUE', '677999000', 'emma.petit@email.com');

-- Registre des maladies chroniques
CREATE TABLE IF NOT EXISTS `registre_maladies_chroniques` (
  `id` int NOT NULL AUTO_INCREMENT,
  `patient_id` int NOT NULL,
  `type_maladie` enum('DIABETE','HYPERTENSION','MALADIE_RENALE','INSUFFISANCE_CARDIAQUE','INSUFFISANCE_HEPATIQUE','BPCO','CANCER','VIH','HEPATITE_B','HEPATITE_C','TUBERCULOSE','DREPANOCYTOSE','AUTRE') NOT NULL,
  `date_diagnostic` date NOT NULL,
  `stade` varchar(50) DEFAULT NULL,
  `traitement_actuel` text,
  `medecin_referent` varchar(200) DEFAULT NULL,
  `date_inscription` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `patient_id` (`patient_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Données de test maladies chroniques (en supposant des patient_id existants)
INSERT INTO `registre_maladies_chroniques` (`patient_id`, `type_maladie`, `date_diagnostic`, `stade`, `traitement_actuel`, `medecin_referent`) VALUES
(1, 'DIABETE', '2020-03-15', 'Type 2', 'Metformine 850mg 2x/jour', 'Dr. MARTIN Paul'),
(2, 'HYPERTENSION', '2019-08-22', 'Grade 2', 'Amlodipine 5mg/jour', 'Dr. BERNARD Sophie'),
(3, 'CANCER', '2023-01-10', 'Stade II', 'Chimiothérapie FOLFOX', 'Dr. DURAND Michel'),
(1, 'HYPERTENSION', '2021-05-18', 'Grade 1', 'Lisinopril 10mg/jour', 'Dr. MARTIN Paul'),
(4, 'DIABETE', '2022-09-03', 'Type 1', 'Insuline rapide + lente', 'Dr. ROUSSEAU Claire');

-- Registre des maladies rares
CREATE TABLE IF NOT EXISTS `registre_maladies_rares` (
  `id` int NOT NULL AUTO_INCREMENT,
  `patient_id` int NOT NULL,
  `nom_maladie` varchar(255) NOT NULL,
  `code_orpha` varchar(20) DEFAULT NULL,
  `date_diagnostic` date NOT NULL,
  `symptomes` text,
  `traitement` text,
  `medecin_referent` varchar(200) DEFAULT NULL,
  `date_inscription` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `patient_id` (`patient_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Données de test maladies rares (utiliser des patient_id existants)
INSERT INTO `registre_maladies_rares` (`patient_id`, `nom_maladie`, `code_orpha`, `date_diagnostic`, `symptomes`, `traitement`, `medecin_referent`) VALUES
(1, 'Maladie de Huntington', 'ORPHA399', '2023-06-15', 'Mouvements involontaires, troubles cognitifs', 'Tétrabenazine, kinésithérapie', 'Dr. LAMBERT Neurologie'),
(2, 'Syndrome de Marfan', 'ORPHA558', '2022-11-20', 'Grande taille, arachnodactylie, prolapsus mitral', 'Surveillance cardiaque, restriction activité', 'Dr. MOREAU Cardiologie');