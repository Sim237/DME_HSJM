-- MySQL dump 10.13  Distrib 8.4.6, for Win64 (x86_64)
--
-- Host: localhost    Database: dme_hospital
-- ------------------------------------------------------
-- Server version	8.4.6

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `admissions`
--

DROP TABLE IF EXISTS `admissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `admissions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `patient_id` int NOT NULL,
  `lit_id` int DEFAULT NULL,
  `date_admission` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `date_sortie` datetime DEFAULT NULL,
  `statut` enum('EN_COURS','SORTIE','TRANSFERE') DEFAULT 'EN_COURS',
  `motif_admission` text,
  `medecin_id` int DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `patient_id` (`patient_id`),
  KEY `lit_id` (`lit_id`),
  CONSTRAINT `admissions_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `admissions_ibfk_2` FOREIGN KEY (`lit_id`) REFERENCES `lits` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admissions`
--

LOCK TABLES `admissions` WRITE;
/*!40000 ALTER TABLE `admissions` DISABLE KEYS */;
INSERT INTO `admissions` VALUES (1,2,3,'2025-11-25 11:17:45',NULL,'EN_COURS','Crise de paludisme sévère avec vomissements',2,'2025-11-27 11:17:45');
/*!40000 ALTER TABLE `admissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `antecedents`
--

DROP TABLE IF EXISTS `antecedents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `antecedents` (
  `id` int NOT NULL AUTO_INCREMENT,
  `patient_id` int NOT NULL,
  `type` enum('medical','chirurgical','familial','gyneco_obstetrique','allergique') NOT NULL,
  `description` text NOT NULL,
  `date_survenue` date DEFAULT NULL,
  `date_enregistrement` datetime DEFAULT CURRENT_TIMESTAMP,
  `enregistre_par` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `patient_id` (`patient_id`),
  CONSTRAINT `fk_antecedents_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `antecedents`
--

LOCK TABLES `antecedents` WRITE;
/*!40000 ALTER TABLE `antecedents` DISABLE KEYS */;
/*!40000 ALTER TABLE `antecedents` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cim10`
--

DROP TABLE IF EXISTS `cim10`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cim10` (
  `code` varchar(10) NOT NULL,
  `description` text NOT NULL,
  PRIMARY KEY (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cim10`
--

LOCK TABLES `cim10` WRITE;
/*!40000 ALTER TABLE `cim10` DISABLE KEYS */;
INSERT INTO `cim10` VALUES ('A01.0','Fièvre typhoïde'),('B50','Paludisme à Plasmodium falciparum'),('B51','Paludisme à Plasmodium vivax'),('B54','Paludisme, sans précision'),('E11','Diabète sucré de type 2'),('I10','Hypertension essentielle (primitive)'),('J00','Rhinopharyngite aiguë [rhume banal]'),('J18.9','Pneumopathie, sans précision'),('K29.7','Gastrite, sans précision'),('R50.9','Fièvre, sans précision');
/*!40000 ALTER TABLE `cim10` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `consultations`
--

DROP TABLE IF EXISTS `consultations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `consultations` (
  `id` int NOT NULL AUTO_INCREMENT,
  `patient_id` int NOT NULL,
  `medecin_id` int NOT NULL,
  `type_consultation` enum('interne','externe') NOT NULL,
  `date_consultation` datetime DEFAULT CURRENT_TIMESTAMP,
  `statut` enum('en_cours','terminee','validee','annulee') DEFAULT 'en_cours',
  `motif_consultation` text,
  `automedication` text,
  `histoire_maladie` text,
  `complement_anamnese` text,
  `temperature` decimal(4,1) DEFAULT NULL,
  `tension_arterielle` varchar(20) DEFAULT NULL,
  `frequence_cardiaque` int DEFAULT NULL,
  `examen_physique` text,
  `resume_syndromique` text,
  `hypotheses_diagnostiques` text,
  `diagnostic_principal` text,
  `diagnostics_differentiels` text,
  `plan_traitement` text,
  `traitement_non_medicamenteux` text,
  `surveillance` text,
  `notes_suivi` text,
  `date_prochain_rdv` date DEFAULT NULL,
  `date_creation` datetime DEFAULT CURRENT_TIMESTAMP,
  `date_modification` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `type` varchar(50) DEFAULT 'GENERALE',
  `poids` decimal(5,2) DEFAULT NULL,
  `taille` decimal(5,2) DEFAULT NULL,
  `examens_paracliniques` text,
  `date_suivi` date DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `patient_id` (`patient_id`),
  KEY `medecin_id` (`medecin_id`),
  KEY `idx_date_consultation` (`date_consultation`),
  CONSTRAINT `fk_consultation_medecin` FOREIGN KEY (`medecin_id`) REFERENCES `users` (`id`),
  CONSTRAINT `fk_consultation_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `consultations`
--

LOCK TABLES `consultations` WRITE;
/*!40000 ALTER TABLE `consultations` DISABLE KEYS */;
INSERT INTO `consultations` VALUES (1,1,2,'interne','2025-11-27 10:15:01','en_cours','Douleurs abdo',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Gastrite',NULL,NULL,NULL,NULL,NULL,NULL,'2025-11-27 11:15:01','2025-11-27 11:15:01','EXTERNE',NULL,NULL,NULL,NULL);
/*!40000 ALTER TABLE `consultations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `dispensations`
--

DROP TABLE IF EXISTS `dispensations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `dispensations` (
  `id` int NOT NULL AUTO_INCREMENT,
  `prescription_id` int NOT NULL,
  `ligne_prescription_id` int NOT NULL,
  `quantite_dispensee` int NOT NULL,
  `date_dispensation` datetime DEFAULT CURRENT_TIMESTAMP,
  `dispense_par` int NOT NULL,
  `observation` text,
  PRIMARY KEY (`id`),
  KEY `prescription_id` (`prescription_id`),
  KEY `ligne_prescription_id` (`ligne_prescription_id`),
  CONSTRAINT `fk_dispensation_ligne` FOREIGN KEY (`ligne_prescription_id`) REFERENCES `lignes_prescription` (`id`),
  CONSTRAINT `fk_dispensation_prescription` FOREIGN KEY (`prescription_id`) REFERENCES `prescriptions` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `dispensations`
--

LOCK TABLES `dispensations` WRITE;
/*!40000 ALTER TABLE `dispensations` DISABLE KEYS */;
/*!40000 ALTER TABLE `dispensations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `examen_details`
--

DROP TABLE IF EXISTS `examen_details`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `examen_details` (
  `id` int NOT NULL AUTO_INCREMENT,
  `examen_id` int NOT NULL,
  `nom_examen` varchar(255) DEFAULT NULL,
  `code_examen` varchar(50) DEFAULT NULL,
  `resultat` text,
  `valeur_normale` varchar(100) DEFAULT NULL,
  `unite` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `examen_id` (`examen_id`),
  CONSTRAINT `examen_details_ibfk_1` FOREIGN KEY (`examen_id`) REFERENCES `examens` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `examen_details`
--

LOCK TABLES `examen_details` WRITE;
/*!40000 ALTER TABLE `examen_details` DISABLE KEYS */;
INSERT INTO `examen_details` VALUES (1,1,'NFS','BIO-001',NULL,NULL,NULL),(2,1,'Groupe Sanguin','BIO-002',NULL,NULL,NULL);
/*!40000 ALTER TABLE `examen_details` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `examens`
--

DROP TABLE IF EXISTS `examens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `examens` (
  `id` int NOT NULL AUTO_INCREMENT,
  `consultation_id` int DEFAULT NULL,
  `patient_id` int NOT NULL,
  `medecin_id` int NOT NULL,
  `technicien_id` int DEFAULT NULL,
  `type_examen` varchar(100) DEFAULT NULL,
  `urgence` tinyint(1) DEFAULT '0',
  `observations` text,
  `observations_labo` text,
  `date_demande` datetime DEFAULT CURRENT_TIMESTAMP,
  `date_resultat` datetime DEFAULT NULL,
  `statut` enum('EN_ATTENTE','EN_COURS','TERMINE','ANNULE') DEFAULT 'EN_ATTENTE',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `etat_prelevement` enum('NON_FAIT','FAIT') DEFAULT 'NON_FAIT',
  `date_prelevement` datetime DEFAULT NULL,
  `validation_biologiste` tinyint(1) DEFAULT '0',
  `commentaire_biologiste` text,
  PRIMARY KEY (`id`),
  KEY `patient_id` (`patient_id`),
  KEY `consultation_id` (`consultation_id`),
  CONSTRAINT `examens_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `examens_ibfk_2` FOREIGN KEY (`consultation_id`) REFERENCES `consultations` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `examens`
--

LOCK TABLES `examens` WRITE;
/*!40000 ALTER TABLE `examens` DISABLE KEYS */;
INSERT INTO `examens` VALUES (1,NULL,3,3,NULL,'BIOLOGIE',1,'Bilan pré-op',NULL,'2025-11-27 11:15:01',NULL,'EN_ATTENTE','2025-11-27 11:15:01','NON_FAIT',NULL,0,NULL);
/*!40000 ALTER TABLE `examens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `examens_paracliniques`
--

DROP TABLE IF EXISTS `examens_paracliniques`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `examens_paracliniques` (
  `id` int NOT NULL AUTO_INCREMENT,
  `consultation_id` int NOT NULL,
  `patient_id` int NOT NULL,
  `type_examen` enum('biologie','imagerie','autre') NOT NULL,
  `nom_examen` varchar(255) NOT NULL,
  `indication` text,
  `urgence` tinyint(1) DEFAULT '0',
  `statut` enum('demande','en_cours','termine','resultat_disponible') DEFAULT 'demande',
  `date_demande` datetime DEFAULT CURRENT_TIMESTAMP,
  `date_realisation` datetime DEFAULT NULL,
  `demande_par` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `consultation_id` (`consultation_id`),
  KEY `patient_id` (`patient_id`),
  CONSTRAINT `fk_examen_consultation` FOREIGN KEY (`consultation_id`) REFERENCES `consultations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_examen_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `examens_paracliniques`
--

LOCK TABLES `examens_paracliniques` WRITE;
/*!40000 ALTER TABLE `examens_paracliniques` DISABLE KEYS */;
/*!40000 ALTER TABLE `examens_paracliniques` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `hospitalisations`
--

DROP TABLE IF EXISTS `hospitalisations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `hospitalisations` (
  `id` int NOT NULL AUTO_INCREMENT,
  `patient_id` int NOT NULL,
  `lit_id` int NOT NULL,
  `service_id` int NOT NULL,
  `medecin_responsable` int NOT NULL,
  `motif_hospitalisation` text NOT NULL,
  `diagnostic_entree` text,
  `date_admission` datetime DEFAULT CURRENT_TIMESTAMP,
  `date_sortie_prevue` date DEFAULT NULL,
  `date_sortie_effective` datetime DEFAULT NULL,
  `type_sortie` enum('guerison','amelioration','transfert','deces','fuite','abandon') DEFAULT NULL,
  `diagnostic_sortie` text,
  `statut` enum('en_cours','termine') DEFAULT 'en_cours',
  PRIMARY KEY (`id`),
  KEY `patient_id` (`patient_id`),
  KEY `lit_id` (`lit_id`),
  KEY `service_id` (`service_id`),
  CONSTRAINT `fk_hospit_lit` FOREIGN KEY (`lit_id`) REFERENCES `lits` (`id`),
  CONSTRAINT `fk_hospit_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`),
  CONSTRAINT `fk_hospit_service` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `hospitalisations`
--

LOCK TABLES `hospitalisations` WRITE;
/*!40000 ALTER TABLE `hospitalisations` DISABLE KEYS */;
/*!40000 ALTER TABLE `hospitalisations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `lab_catalogue`
--

DROP TABLE IF EXISTS `lab_catalogue`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `lab_catalogue` (
  `id` int NOT NULL AUTO_INCREMENT,
  `categorie_id` int NOT NULL,
  `nom` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `prix` decimal(10,2) DEFAULT '0.00',
  `type_tube` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `delai_attente` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `categorie_id` (`categorie_id`),
  CONSTRAINT `lab_catalogue_ibfk_1` FOREIGN KEY (`categorie_id`) REFERENCES `lab_categories` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lab_catalogue`
--

LOCK TABLES `lab_catalogue` WRITE;
/*!40000 ALTER TABLE `lab_catalogue` DISABLE KEYS */;
INSERT INTO `lab_catalogue` VALUES (1,1,'NFS (Hémogramme)','HEM-01',0.00,'Violet (EDTA)',NULL),(2,2,'Glycémie à jeun','BIO-01',0.00,'Gris (Fluorure)',NULL);
/*!40000 ALTER TABLE `lab_catalogue` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `lab_categories`
--

DROP TABLE IF EXISTS `lab_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `lab_categories` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nom` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `couleur` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'primary',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lab_categories`
--

LOCK TABLES `lab_categories` WRITE;
/*!40000 ALTER TABLE `lab_categories` DISABLE KEYS */;
INSERT INTO `lab_categories` VALUES (1,'Hématologie','danger'),(2,'Biochimie','success'),(3,'Sérologie','warning'),(4,'Parasitologie','info');
/*!40000 ALTER TABLE `lab_categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `lab_parametres`
--

DROP TABLE IF EXISTS `lab_parametres`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `lab_parametres` (
  `id` int NOT NULL AUTO_INCREMENT,
  `examen_id` int NOT NULL,
  `nom` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `unite` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `valeur_min` decimal(10,2) DEFAULT NULL,
  `valeur_max` decimal(10,2) DEFAULT NULL,
  `valeur_min_h` decimal(10,2) DEFAULT NULL,
  `valeur_max_h` decimal(10,2) DEFAULT NULL,
  `ordre_affichage` int DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `examen_id` (`examen_id`),
  CONSTRAINT `lab_parametres_ibfk_1` FOREIGN KEY (`examen_id`) REFERENCES `lab_catalogue` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lab_parametres`
--

LOCK TABLES `lab_parametres` WRITE;
/*!40000 ALTER TABLE `lab_parametres` DISABLE KEYS */;
INSERT INTO `lab_parametres` VALUES (1,1,'Hémoglobine','g/dL',12.00,16.00,13.00,18.00,1),(2,1,'Hématies','T/L',4.00,5.20,4.50,5.80,2),(3,1,'Leucocytes (G.B)','G/L',4.00,10.00,4.00,10.00,3),(4,1,'Plaquettes','G/L',150.00,400.00,150.00,400.00,4),(5,2,'Glucose','g/L',0.70,1.10,NULL,NULL,1);
/*!40000 ALTER TABLE `lab_parametres` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `lignes_prescription`
--

DROP TABLE IF EXISTS `lignes_prescription`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `lignes_prescription` (
  `id` int NOT NULL AUTO_INCREMENT,
  `prescription_id` int NOT NULL,
  `medicament_id` int NOT NULL,
  `posologie` varchar(255) NOT NULL,
  `voie` varchar(50) DEFAULT NULL,
  `frequence` varchar(100) NOT NULL,
  `duree` varchar(100) NOT NULL,
  `quantite` int NOT NULL,
  `instruction_speciale` text,
  PRIMARY KEY (`id`),
  KEY `prescription_id` (`prescription_id`),
  KEY `medicament_id` (`medicament_id`),
  CONSTRAINT `fk_ligne_medicament` FOREIGN KEY (`medicament_id`) REFERENCES `medicaments` (`id`),
  CONSTRAINT `fk_ligne_prescription` FOREIGN KEY (`prescription_id`) REFERENCES `prescriptions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lignes_prescription`
--

LOCK TABLES `lignes_prescription` WRITE;
/*!40000 ALTER TABLE `lignes_prescription` DISABLE KEYS */;
/*!40000 ALTER TABLE `lignes_prescription` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `lits`
--

DROP TABLE IF EXISTS `lits`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `lits` (
  `id` int NOT NULL AUTO_INCREMENT,
  `numero` varchar(20) NOT NULL,
  `chambre` varchar(20) DEFAULT NULL,
  `service_id` int DEFAULT NULL,
  `statut` enum('DISPONIBLE','OCCUPE','NETTOYAGE','MAINTENANCE') DEFAULT 'DISPONIBLE',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `service_id` (`service_id`),
  CONSTRAINT `lits_ibfk_1` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lits`
--

LOCK TABLES `lits` WRITE;
/*!40000 ALTER TABLE `lits` DISABLE KEYS */;
INSERT INTO `lits` VALUES (1,'L-URG-01','Box 1',1,'DISPONIBLE','2025-11-27 11:17:45'),(2,'L-URG-02','Box 2',1,'DISPONIBLE','2025-11-27 11:17:45'),(3,'L-MED-101','101',2,'OCCUPE','2025-11-27 11:17:45'),(4,'L-MED-102','101',2,'DISPONIBLE','2025-11-27 11:17:45'),(5,'L-CHIR-201','201',3,'DISPONIBLE','2025-11-27 11:17:45');
/*!40000 ALTER TABLE `lits` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `medicaments`
--

DROP TABLE IF EXISTS `medicaments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `medicaments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `code` varchar(20) NOT NULL,
  `nom` varchar(100) NOT NULL,
  `forme` varchar(50) DEFAULT NULL,
  `dosage` varchar(50) DEFAULT NULL,
  `quantite` int DEFAULT '0',
  `unite` varchar(20) DEFAULT NULL,
  `seuil_alerte` int DEFAULT '10',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `medicaments`
--

LOCK TABLES `medicaments` WRITE;
/*!40000 ALTER TABLE `medicaments` DISABLE KEYS */;
INSERT INTO `medicaments` VALUES (1,'MED001','Paracétamol','Comprimé','500mg',500,'boîtes',50,'2025-11-27 11:15:01','2025-11-27 11:15:01'),(2,'MED002','Paracétamol','Perfusion','1g/100ml',45,'poches',20,'2025-11-27 11:15:01','2025-11-27 11:15:01'),(3,'MED003','Amoxicilline','Gélule','1g',12,'boîtes',15,'2025-11-27 11:15:01','2025-11-27 11:15:01'),(4,'MED004','Spasfon','Injectable','40mg',100,'ampoules',20,'2025-11-27 11:15:01','2025-11-27 11:15:01'),(5,'MED005','Sérum Physiologique','Perfusion','500ml',200,'poches',50,'2025-11-27 11:15:01','2025-11-27 11:15:01'),(6,'MED006','Tramadol','Gélule','50mg',0,'boîtes',10,'2025-11-27 11:15:01','2025-11-27 11:15:01');
/*!40000 ALTER TABLE `medicaments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `parametres_vitaux`
--

DROP TABLE IF EXISTS `parametres_vitaux`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `parametres_vitaux` (
  `id` int NOT NULL AUTO_INCREMENT,
  `patient_id` int NOT NULL,
  `temperature` decimal(4,1) DEFAULT NULL,
  `pression_arterielle_systolique` int DEFAULT NULL,
  `pression_arterielle_diastolique` int DEFAULT NULL,
  `frequence_cardiaque` int DEFAULT NULL,
  `frequence_respiratoire` int DEFAULT NULL,
  `saturation_oxygene` decimal(5,2) DEFAULT NULL,
  `poids` decimal(5,2) DEFAULT NULL,
  `taille` decimal(5,2) DEFAULT NULL,
  `imc` decimal(5,2) DEFAULT NULL,
  `glycemie` decimal(5,2) DEFAULT NULL,
  `observation` text,
  `date_mesure` datetime DEFAULT CURRENT_TIMESTAMP,
  `mesure_par` int DEFAULT NULL,
  `admission_id` int DEFAULT NULL,
  `user_id` int DEFAULT NULL,
  `tension_sys` int DEFAULT NULL,
  `tension_dia` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `patient_id` (`patient_id`),
  KEY `idx_date_mesure` (`date_mesure`),
  CONSTRAINT `fk_parametres_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `parametres_vitaux`
--

LOCK TABLES `parametres_vitaux` WRITE;
/*!40000 ALTER TABLE `parametres_vitaux` DISABLE KEYS */;
INSERT INTO `parametres_vitaux` VALUES (1,2,39.5,NULL,NULL,95,NULL,96.00,NULL,NULL,NULL,NULL,NULL,'2025-11-25 19:15:01',NULL,1,4,110,70),(2,2,38.8,NULL,NULL,90,NULL,97.00,NULL,NULL,NULL,NULL,NULL,'2025-11-26 05:15:01',NULL,1,4,115,75),(3,2,38.2,NULL,NULL,85,NULL,98.00,NULL,NULL,NULL,NULL,NULL,'2025-11-26 15:15:01',NULL,1,4,118,78),(4,2,37.5,NULL,NULL,80,NULL,98.00,NULL,NULL,NULL,NULL,NULL,'2025-11-27 01:15:01',NULL,1,4,120,80),(5,2,37.0,NULL,NULL,72,NULL,99.00,NULL,NULL,NULL,NULL,NULL,'2025-11-27 11:15:01',NULL,1,4,120,80),(6,2,39.5,NULL,NULL,95,NULL,96.00,NULL,NULL,NULL,NULL,NULL,'2025-11-25 19:17:45',NULL,1,4,110,70),(7,2,38.8,NULL,NULL,90,NULL,97.00,NULL,NULL,NULL,NULL,NULL,'2025-11-26 05:17:45',NULL,1,4,115,75),(8,2,38.2,NULL,NULL,85,NULL,98.00,NULL,NULL,NULL,NULL,NULL,'2025-11-26 15:17:45',NULL,1,4,118,78),(9,2,37.5,NULL,NULL,80,NULL,98.00,NULL,NULL,NULL,NULL,NULL,'2025-11-27 01:17:45',NULL,1,4,120,80),(10,2,37.0,NULL,NULL,72,NULL,99.00,NULL,NULL,NULL,NULL,NULL,'2025-11-27 11:17:45',NULL,1,4,120,80),(11,2,39.0,120,80,80,NULL,98.00,NULL,NULL,NULL,NULL,'RAS','2025-11-27 13:54:19',NULL,1,1,NULL,NULL);
/*!40000 ALTER TABLE `parametres_vitaux` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `patient_documents`
--

DROP TABLE IF EXISTS `patient_documents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `patient_documents` (
  `id` int NOT NULL AUTO_INCREMENT,
  `patient_id` int NOT NULL,
  `nom_fichier` varchar(255) NOT NULL,
  `chemin_fichier` varchar(255) NOT NULL,
  `type_mime` varchar(50) DEFAULT NULL,
  `categorie` varchar(50) DEFAULT 'AUTRE',
  `description` text,
  `date_upload` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `patient_id` (`patient_id`),
  CONSTRAINT `patient_documents_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `patient_documents`
--

LOCK TABLES `patient_documents` WRITE;
/*!40000 ALTER TABLE `patient_documents` DISABLE KEYS */;
/*!40000 ALTER TABLE `patient_documents` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `patients`
--

DROP TABLE IF EXISTS `patients`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `patients` (
  `id` int NOT NULL AUTO_INCREMENT,
  `dossier_numero` varchar(50) NOT NULL,
  `nom` varchar(100) NOT NULL,
  `prenom` varchar(100) NOT NULL,
  `date_naissance` date NOT NULL,
  `sexe` enum('M','F') NOT NULL,
  `telephone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `telephone_urgence` varchar(20) DEFAULT NULL,
  `adresse` text,
  `ville` varchar(100) DEFAULT NULL,
  `profession` varchar(100) DEFAULT NULL,
  `situation_matrimoniale` enum('celibataire','marie','divorce','veuf') DEFAULT NULL,
  `groupe_sanguin` enum('A+','A-','B+','B-','AB+','AB-','O+','O-') DEFAULT NULL,
  `contact_nom` varchar(100) DEFAULT NULL,
  `contact_telephone` varchar(20) DEFAULT NULL,
  `antecedents_medicaux` text,
  `antecedents_chirurgicaux` text,
  `antecedents_familiaux` text,
  `statut` varchar(20) DEFAULT 'EXTERNE',
  `allergies` text,
  `allergie_connue` text,
  `photo` varchar(255) DEFAULT NULL,
  `date_creation` datetime DEFAULT CURRENT_TIMESTAMP,
  `date_modification` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `actif` tinyint(1) DEFAULT '1',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `numero_dossier` (`dossier_numero`),
  KEY `idx_nom_prenom` (`nom`,`prenom`),
  KEY `idx_numero_dossier` (`dossier_numero`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `patients`
--

LOCK TABLES `patients` WRITE;
/*!40000 ALTER TABLE `patients` DISABLE KEYS */;
INSERT INTO `patients` VALUES (1,'P-2023-00001','MEBARA','Jean','1985-05-20','M','699111111',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'EXTERNE',NULL,NULL,NULL,'2025-11-27 11:15:01','2025-11-27 11:15:01',1,'2025-11-27 11:15:01'),(2,'P-2023-00002','CURIE','Marie','1990-11-07','F','699222222',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'HOSPITALISE',NULL,NULL,NULL,'2025-11-27 11:15:01','2025-11-27 11:15:01',1,'2025-11-27 11:15:01'),(3,'P-2023-00003','MBAPPÉ','Kylian','1998-12-20','M','699333333',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'EXTERNE',NULL,NULL,NULL,'2025-11-27 11:15:01','2025-11-27 11:15:01',1,'2025-11-27 11:15:01');
/*!40000 ALTER TABLE `patients` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `prescription_kit_items`
--

DROP TABLE IF EXISTS `prescription_kit_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `prescription_kit_items` (
  `id` int NOT NULL AUTO_INCREMENT,
  `kit_id` int NOT NULL,
  `medicament_id` int NOT NULL,
  `posologie` varchar(100) DEFAULT NULL,
  `duree` varchar(50) DEFAULT NULL,
  `quantite` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `kit_id` (`kit_id`),
  KEY `medicament_id` (`medicament_id`),
  CONSTRAINT `prescription_kit_items_ibfk_1` FOREIGN KEY (`kit_id`) REFERENCES `prescription_kits` (`id`) ON DELETE CASCADE,
  CONSTRAINT `prescription_kit_items_ibfk_2` FOREIGN KEY (`medicament_id`) REFERENCES `medicaments` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `prescription_kit_items`
--

LOCK TABLES `prescription_kit_items` WRITE;
/*!40000 ALTER TABLE `prescription_kit_items` DISABLE KEYS */;
INSERT INTO `prescription_kit_items` VALUES (1,1,1,'1g toutes les 6h si fièvre','3 jours',12),(2,1,2,'1 comprimé toutes les 8h','3 jours',9);
/*!40000 ALTER TABLE `prescription_kit_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `prescription_kits`
--

DROP TABLE IF EXISTS `prescription_kits`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `prescription_kits` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nom` varchar(100) NOT NULL,
  `description` text,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `prescription_kits`
--

LOCK TABLES `prescription_kits` WRITE;
/*!40000 ALTER TABLE `prescription_kits` DISABLE KEYS */;
INSERT INTO `prescription_kits` VALUES (1,'Kit Paludisme Simple','Traitement standard adulte','2025-11-27 15:08:18');
/*!40000 ALTER TABLE `prescription_kits` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `prescription_medicaments`
--

DROP TABLE IF EXISTS `prescription_medicaments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `prescription_medicaments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `prescription_id` int NOT NULL,
  `medicament_id` int DEFAULT NULL,
  `nom_medicament` varchar(100) DEFAULT NULL,
  `posologie` varchar(100) DEFAULT NULL,
  `duree` varchar(50) DEFAULT NULL,
  `quantite_prescrite` int DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `prescription_id` (`prescription_id`),
  KEY `medicament_id` (`medicament_id`),
  CONSTRAINT `prescription_medicaments_ibfk_1` FOREIGN KEY (`prescription_id`) REFERENCES `prescriptions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `prescription_medicaments_ibfk_2` FOREIGN KEY (`medicament_id`) REFERENCES `medicaments` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `prescription_medicaments`
--

LOCK TABLES `prescription_medicaments` WRITE;
/*!40000 ALTER TABLE `prescription_medicaments` DISABLE KEYS */;
INSERT INTO `prescription_medicaments` VALUES (1,1,4,'Spasfon','2 cp si douleur',NULL,20),(2,1,1,'Paracétamol','1 cp 3x/j',NULL,10);
/*!40000 ALTER TABLE `prescription_medicaments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `prescriptions`
--

DROP TABLE IF EXISTS `prescriptions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `prescriptions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `consultation_id` int DEFAULT NULL,
  `patient_id` int NOT NULL,
  `medecin_id` int NOT NULL,
  `numero_ordonnance` varchar(50) DEFAULT NULL,
  `date_prescription` datetime DEFAULT CURRENT_TIMESTAMP,
  `statut` enum('EN_ATTENTE','PARTIEL','SERVIE','ANNULEE') DEFAULT 'EN_ATTENTE',
  `observations` text,
  PRIMARY KEY (`id`),
  UNIQUE KEY `numero_ordonnance` (`numero_ordonnance`),
  KEY `consultation_id` (`consultation_id`),
  KEY `patient_id` (`patient_id`),
  CONSTRAINT `fk_prescription_consultation` FOREIGN KEY (`consultation_id`) REFERENCES `consultations` (`id`),
  CONSTRAINT `fk_prescription_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `prescriptions`
--

LOCK TABLES `prescriptions` WRITE;
/*!40000 ALTER TABLE `prescriptions` DISABLE KEYS */;
INSERT INTO `prescriptions` VALUES (1,1,1,2,NULL,'2025-11-27 11:15:01','EN_ATTENTE',NULL);
/*!40000 ALTER TABLE `prescriptions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `rendez_vous`
--

DROP TABLE IF EXISTS `rendez_vous`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `rendez_vous` (
  `id` int NOT NULL AUTO_INCREMENT,
  `patient_id` int NOT NULL,
  `medecin_id` int NOT NULL,
  `consultation_id` int DEFAULT NULL,
  `date_rdv` datetime NOT NULL,
  `motif` text,
  `statut` enum('planifie','confirme','realise','annule','reporte') DEFAULT 'planifie',
  `observation` text,
  `date_creation` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `patient_id` (`patient_id`),
  KEY `medecin_id` (`medecin_id`),
  KEY `idx_date_rdv` (`date_rdv`),
  CONSTRAINT `fk_rdv_medecin` FOREIGN KEY (`medecin_id`) REFERENCES `users` (`id`),
  CONSTRAINT `fk_rdv_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `rendez_vous`
--

LOCK TABLES `rendez_vous` WRITE;
/*!40000 ALTER TABLE `rendez_vous` DISABLE KEYS */;
/*!40000 ALTER TABLE `rendez_vous` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `resultats_examens`
--

DROP TABLE IF EXISTS `resultats_examens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `resultats_examens` (
  `id` int NOT NULL AUTO_INCREMENT,
  `examen_id` int NOT NULL,
  `resultat` text NOT NULL,
  `valeur_reference` text,
  `interpretation` text,
  `fichier_joint` varchar(255) DEFAULT NULL,
  `date_resultat` datetime DEFAULT CURRENT_TIMESTAMP,
  `saisi_par` int DEFAULT NULL,
  `valide_par` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `examen_id` (`examen_id`),
  CONSTRAINT `fk_resultat_examen` FOREIGN KEY (`examen_id`) REFERENCES `examens_paracliniques` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `resultats_examens`
--

LOCK TABLES `resultats_examens` WRITE;
/*!40000 ALTER TABLE `resultats_examens` DISABLE KEYS */;
/*!40000 ALTER TABLE `resultats_examens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `services`
--

DROP TABLE IF EXISTS `services`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `services` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nom` varchar(100) NOT NULL,
  `code` varchar(20) NOT NULL,
  `type` enum('clinique','paraclinique','administratif') NOT NULL,
  `responsable_id` int DEFAULT NULL,
  `telephone` varchar(20) DEFAULT NULL,
  `localisation` varchar(255) DEFAULT NULL,
  `actif` tinyint(1) DEFAULT '1',
  `description` text,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `services`
--

LOCK TABLES `services` WRITE;
/*!40000 ALTER TABLE `services` DISABLE KEYS */;
INSERT INTO `services` VALUES (1,'Urgences','URG','clinique',NULL,NULL,NULL,1,'Accueil urgences'),(2,'Médecine Générale','MED','clinique',NULL,NULL,NULL,1,'Hospitalisation'),(3,'Chirurgie','CHIR','clinique',NULL,NULL,NULL,1,'Bloc opératoire'),(4,'Réanimation','REA','clinique',NULL,NULL,NULL,1,'Soins intensifs');
/*!40000 ALTER TABLE `services` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `settings`
--

DROP TABLE IF EXISTS `settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `settings` (
  `id` int NOT NULL DEFAULT '1',
  `nom_hopital` varchar(100) DEFAULT 'DME Hospital',
  `adresse` text,
  `telephone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `devise` varchar(10) DEFAULT 'FCFA',
  `prefixe_dossier` varchar(5) DEFAULT 'P-',
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `settings`
--

LOCK TABLES `settings` WRITE;
/*!40000 ALTER TABLE `settings` DISABLE KEYS */;
INSERT INTO `settings` VALUES (1,'DME Hospital',NULL,'+237 000 000 000','contact@dme-hospital.com','FCFA','P-','2025-11-27 08:54:13');
/*!40000 ALTER TABLE `settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `soins_administres`
--

DROP TABLE IF EXISTS `soins_administres`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `soins_administres` (
  `id` int NOT NULL AUTO_INCREMENT,
  `patient_id` int NOT NULL,
  `hospitalisation_id` int DEFAULT NULL,
  `consultation_id` int DEFAULT NULL,
  `type_soin` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `date_prevue` datetime NOT NULL,
  `date_administration` datetime DEFAULT NULL,
  `statut` enum('planifie','administre','annule') DEFAULT 'planifie',
  `administre_par` int DEFAULT NULL,
  `observation` text,
  PRIMARY KEY (`id`),
  KEY `patient_id` (`patient_id`),
  KEY `hospitalisation_id` (`hospitalisation_id`),
  CONSTRAINT `fk_soin_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `soins_administres`
--

LOCK TABLES `soins_administres` WRITE;
/*!40000 ALTER TABLE `soins_administres` DISABLE KEYS */;
/*!40000 ALTER TABLE `soins_administres` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `soins_hospitalisation`
--

DROP TABLE IF EXISTS `soins_hospitalisation`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `soins_hospitalisation` (
  `id` int NOT NULL AUTO_INCREMENT,
  `admission_id` int NOT NULL,
  `user_id_planificateur` int NOT NULL,
  `user_id_executant` int DEFAULT NULL,
  `type_soin` varchar(100) DEFAULT NULL,
  `description` text,
  `date_prevue` datetime NOT NULL,
  `date_realisee` datetime DEFAULT NULL,
  `statut` enum('PLANIFIE','REALISE','ANNULE','RETARD') DEFAULT 'PLANIFIE',
  `note_execution` text,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `admission_id` (`admission_id`),
  CONSTRAINT `soins_hospitalisation_ibfk_1` FOREIGN KEY (`admission_id`) REFERENCES `admissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `soins_hospitalisation`
--

LOCK TABLES `soins_hospitalisation` WRITE;
/*!40000 ALTER TABLE `soins_hospitalisation` DISABLE KEYS */;
INSERT INTO `soins_hospitalisation` VALUES (1,1,2,NULL,'Perfusion','Sérum glucosé 5% + Quinine','2025-11-27 09:17:59',NULL,'REALISE',NULL,'2025-11-27 11:17:59'),(2,1,2,1,'Injection','Antipyrétique si Temp > 38.5','2025-11-27 11:17:59','2025-11-27 13:14:22','REALISE','','2025-11-27 11:17:59'),(3,1,2,NULL,'Prise de sang','Contrôle Goutte Épaisse','2025-11-27 15:17:59',NULL,'PLANIFIE',NULL,'2025-11-27 11:17:59');
/*!40000 ALTER TABLE `soins_hospitalisation` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `stock_pharmacie`
--

DROP TABLE IF EXISTS `stock_pharmacie`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `stock_pharmacie` (
  `id` int NOT NULL AUTO_INCREMENT,
  `medicament_id` int NOT NULL,
  `quantite_disponible` int NOT NULL DEFAULT '0',
  `quantite_alerte` int DEFAULT '10',
  `date_peremption` date DEFAULT NULL,
  `numero_lot` varchar(50) DEFAULT NULL,
  `prix_unitaire` decimal(10,2) DEFAULT NULL,
  `derniere_maj` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `medicament_id` (`medicament_id`),
  CONSTRAINT `fk_stock_medicament` FOREIGN KEY (`medicament_id`) REFERENCES `medicaments` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `stock_pharmacie`
--

LOCK TABLES `stock_pharmacie` WRITE;
/*!40000 ALTER TABLE `stock_pharmacie` DISABLE KEYS */;
/*!40000 ALTER TABLE `stock_pharmacie` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `transferts_lits`
--

DROP TABLE IF EXISTS `transferts_lits`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `transferts_lits` (
  `id` int NOT NULL AUTO_INCREMENT,
  `hospitalisation_id` int NOT NULL,
  `lit_origine_id` int NOT NULL,
  `lit_destination_id` int NOT NULL,
  `motif_transfert` text NOT NULL,
  `date_transfert` datetime DEFAULT CURRENT_TIMESTAMP,
  `effectue_par` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `hospitalisation_id` (`hospitalisation_id`),
  CONSTRAINT `fk_transfert_hospit` FOREIGN KEY (`hospitalisation_id`) REFERENCES `hospitalisations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `transferts_lits`
--

LOCK TABLES `transferts_lits` WRITE;
/*!40000 ALTER TABLE `transferts_lits` DISABLE KEYS */;
/*!40000 ALTER TABLE `transferts_lits` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `nom` varchar(100) NOT NULL,
  `prenom` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `role` enum('ADMIN','MEDECIN','INFIRMIER','SECRETAIRE','LABORANTIN','PHARMACIEN') NOT NULL,
  `service_id` int DEFAULT NULL,
  `specialite` varchar(100) DEFAULT NULL,
  `telephone` varchar(20) DEFAULT NULL,
  `actif` tinyint(1) DEFAULT '1',
  `date_creation` datetime DEFAULT CURRENT_TIMESTAMP,
  `statut` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'admin','$2y$10$I0j.j.j.j.j.j.j.j.j.j.j.j.j.j.j.j.j.j.j.j.j.j.j.j.','Admin','Système','admin@hopital.com','ADMIN',NULL,NULL,'699000000',1,'2025-11-27 11:15:01',1),(2,'dr.house','$2y$10$I0j.j.j.j.j.j.j.j.j.j.j.j.j.j.j.j.j.j.j.j.j.j.j.j.','Dr. House','Gregory','house@hopital.com','MEDECIN',NULL,'Diagnosticien','699000001',1,'2025-11-27 11:15:01',1),(3,'dr.grey','$2y$10$I0j.j.j.j.j.j.j.j.j.j.j.j.j.j.j.j.j.j.j.j.j.j.j.j.','Dr. Grey','Meredith','grey@hopital.com','MEDECIN',NULL,'Chirurgie Générale','699000002',1,'2025-11-27 11:15:01',1),(4,'inf.ratched','$2y$10$I0j.j.j.j.j.j.j.j.j.j.j.j.j.j.j.j.j.j.j.j.j.j.j.j.','Inf. Ratched','Mildred','ratched@hopital.com','INFIRMIER',NULL,NULL,'699000003',1,'2025-11-27 11:15:01',1),(5,'ph.white','$2y$10$I0j.j.j.j.j.j.j.j.j.j.j.j.j.j.j.j.j.j.j.j.j.j.j.j.','Ph. White','Walter','white@hopital.com','PHARMACIEN',NULL,NULL,'699000004',1,'2025-11-27 11:15:01',1),(6,'lab.dexter','$2y$10$I0j.j.j.j.j.j.j.j.j.j.j.j.j.j.j.j.j.j.j.j.j.j.j.j.','Lab. Dexter','Morgan','dexter@hopital.com','LABORANTIN',NULL,NULL,'699000005',1,'2025-11-27 11:15:01',1),(7,'sec.pam','$2y$10$I0j.j.j.j.j.j.j.j.j.j.j.j.j.j.j.j.j.j.j.j.j.j.j.j.','Sec. Pam','Beesly','pam@hopital.com','SECRETAIRE',NULL,NULL,'699000006',1,'2025-11-27 11:15:01',1);
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-01-12 13:38:59
