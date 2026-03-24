-- Tables pour les registres DME Hospital

-- Registre des donneurs de sang
CREATE TABLE `registre_donneurs_sang` (
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

-- Registre des donneurs de cellules souches hématopoïétiques
CREATE TABLE `registre_donneurs_csh` (
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

-- Registre des receveurs de cellules souches hématopoïétiques
CREATE TABLE `registre_receveurs_csh` (
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

-- Registre des maladies chroniques
CREATE TABLE `registre_maladies_chroniques` (
  `id` int NOT NULL AUTO_INCREMENT,
  `patient_id` int NOT NULL,
  `type_maladie` enum('DIABETE','HYPERTENSION','MALADIE_RENALE','INSUFFISANCE_CARDIAQUE','INSUFFISANCE_HEPATIQUE','BPCO','CANCER','VIH','HEPATITE_B','HEPATITE_C','TUBERCULOSE','DREPANOCYTOSE','AUTRE') NOT NULL,
  `date_diagnostic` date NOT NULL,
  `stade` varchar(50) DEFAULT NULL,
  `traitement_actuel` text,
  `medecin_referent` varchar(200) DEFAULT NULL,
  `date_inscription` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `patient_id` (`patient_id`),
  CONSTRAINT `fk_registre_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Registre des maladies rares
CREATE TABLE `registre_maladies_rares` (
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
  KEY `patient_id` (`patient_id`),
  CONSTRAINT `fk_registre_rare_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;