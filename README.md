# 🏥 DME HOSPITAL - Dossier Médical Électronique

![Version](https://img.shields.io/badge/version-2.0.0-blue.svg)
![PHP](https://img.shields.io/badge/PHP-8.0+-purple.svg)
![MySQL](https://img.shields.io/badge/MySQL-8.0+-orange.svg)
![License](https://img.shields.io/badge/license-MIT-green.svg)

## 📋 Table des Matières

- [Description](#description)
- [Fonctionnalités](#fonctionnalités)
- [Prérequis](#prérequis)
- [Installation](#installation)
- [Configuration](#configuration)
- [Structure du Projet](#structure-du-projet)
- [Modules](#modules)
- [Utilisation](#utilisation)
- [API](#api)
- [Sécurité](#sécurité)
- [Dépannage](#dépannage)
- [Contribution](#contribution)
- [Licence](#licence)

---

## 📖 Description

**DME Hospital** est un système complet de gestion hospitalière développé en PHP avec architecture MVC. Il permet la gestion complète des dossiers médicaux électroniques, consultations, hospitalisations, laboratoire, pharmacie et bien plus.

### 🎯 Objectifs

- Digitaliser la gestion des dossiers patients
- Optimiser le workflow médical
- Améliorer la traçabilité des soins
- Faciliter la communication inter-services
- Sécuriser les données médicales

---

## ✨ Fonctionnalités

### 👥 Gestion Patients
- ✅ Dossiers médicaux électroniques complets
- ✅ Historique des consultations
- ✅ Antécédents médicaux et allergies
- ✅ Paramètres vitaux et constantes
- ✅ Documents numérisés
- ✅ Impression cartes patients avec QR code

### 🩺 Consultations
- ✅ Formulaire multi-étapes (7 étapes)
- ✅ Anamnèse structurée
- ✅ Examen physique détaillé
- ✅ Diagnostic avec CIM-10
- ✅ Prescription électronique
- ✅ Aide à la décision (hospitalisation)
- ✅ Signatures électroniques

### 🏨 Hospitalisation
- ✅ Gestion des lits et admissions
- ✅ Suivi clinique temps réel
- ✅ Constantes vitales
- ✅ Planning des soins
- ✅ Scores de gravité automatiques
- ✅ Alertes prédictives

### 🔬 Laboratoire
- ✅ Demandes d'examens intégrées
- ✅ Saisie et validation des résultats
- ✅ Détection automatique d'anomalies
- ✅ API temps réel (SSE)
- ✅ Notifications médecins
- ✅ Historique complet

### 💊 Pharmacie
- ✅ Gestion des stocks
- ✅ Ordonnances électroniques
- ✅ Délivrance avec traçabilité
- ✅ Alertes interactions médicamenteuses
- ✅ Vérification disponibilité
- ✅ Statistiques de consommation

### 🖼️ Imagerie Médicale
- ✅ Viewer DICOM intégré
- ✅ Upload et stockage sécurisé
- ✅ Interprétations radiologiques
- ✅ Comparaison d'images
- ✅ Annotations

### 📹 Télémédecine
- ✅ Consultations vidéo (Jitsi Meet)
- ✅ Surveillance à distance
- ✅ Partage de documents
- ✅ Chat en temps réel
- ✅ Alertes automatiques

### 📊 Registres Spécialisés
- ✅ Donneurs de sang
- ✅ Banques de sang
- ✅ Donneurs/Receveurs CSH
- ✅ Maladies chroniques (diabète, HTA, cancers)
- ✅ Compatibilité HLA

### 🔐 Sécurité
- ✅ Authentification multi-facteurs (2FA)
- ✅ Encryption des données sensibles
- ✅ Audit logs complets
- ✅ Backups automatiques
- ✅ Gestion des permissions
- ✅ Sessions sécurisées

### 📱 Modules Additionnels
- ✅ Chat médical sécurisé
- ✅ Gestion famille et visites
- ✅ Formation du personnel
- ✅ Agenda médical
- ✅ Statistiques avancées
- ✅ Facturation
- ✅ Kiosque patient
- ✅ Satisfaction patient

---

## 🔧 Prérequis

### Logiciels Requis

```
- PHP >= 8.0
- MySQL >= 8.0 ou MariaDB >= 10.5
- Apache >= 2.4 ou Nginx
- Composer (optionnel)
```

### Extensions PHP Requises

```
- pdo_mysql
- mbstring
- openssl
- json
- gd
- curl
- zip
- xml
```

### Recommandations

```
- Redis (pour le cache)
- Node.js (pour certaines fonctionnalités)
- SSL/TLS (pour la production)
```

---

## 📥 Installation

### 1. Cloner le Projet

```bash
cd C:\xampp\htdocs
git clone https://github.com/votre-repo/dme_hospital.git
cd dme_hospital
```

### 2. Configuration Base de Données

```bash
# Créer la base de données
mysql -u root -p
CREATE DATABASE dme_hospital CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
EXIT;

# Importer le schéma principal
mysql -u root -p dme_hospital < database/dme_hospital.sql

# Importer les modules (dans l'ordre)
mysql -u root -p dme_hospital < database/auth_system.sql
mysql -u root -p dme_hospital < database/hospitalisation_tables.sql
mysql -u root -p dme_hospital < database/laboratoire_api_tables.sql
mysql -u root -p dme_hospital < database/pharmacie_consultation.sql
mysql -u root -p dme_hospital < database/imagerie_medicale.sql
mysql -u root -p dme_hospital < database/telemedecine_tables.sql
mysql -u root -p dme_hospital < database/chat_famille_formation_tables.sql
mysql -u root -p dme_hospital < database/registres_tables.sql
mysql -u root -p dme_hospital < database/securite.sql
```

### 3. Configuration Application

```bash
# Copier le fichier de configuration
cp config/config.example.php config/config.php

# Éditer les paramètres
nano config/config.php
```

### 4. Permissions

```bash
# Linux/Mac
chmod -R 755 public/
chmod -R 777 uploads/
chmod -R 777 backups/

# Windows (via PowerShell en admin)
icacls uploads /grant Everyone:F /T
icacls backups /grant Everyone:F /T
```

### 5. Créer l'Administrateur

```bash
# Exécuter le script de création admin
php reset_admin.php
```

**Identifiants par défaut :**
- **Login:** admin@hospital.com
- **Mot de passe:** Admin123

⚠️ **IMPORTANT:** Changez ces identifiants immédiatement après la première connexion !

---

## ⚙️ Configuration

### config/config.php

```php
<?php
// Base de données
define('DB_HOST', 'localhost');
define('DB_NAME', 'dme_hospital');
define('DB_USER', 'root');
define('DB_PASS', '');

// Application
define('BASE_URL', '/dme_hospital/');
define('APP_NAME', 'DME Hospital');
define('APP_ENV', 'development'); // production, development

// Sécurité
define('ENCRYPTION_KEY', 'votre-clé-32-caractères-ici');
define('SESSION_LIFETIME', 3600); // 1 heure

// Cache
define('CACHE_ENABLED', true);
define('CACHE_DRIVER', 'redis'); // redis, file

// Compression
define('COMPRESSION_ENABLED', false);

// Uploads
define('MAX_UPLOAD_SIZE', 10485760); // 10MB
define('ALLOWED_EXTENSIONS', 'jpg,jpeg,png,pdf,doc,docx');
?>
```

### config/database.php

```php
<?php
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch(PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}
?>
```

---

## 📁 Structure du Projet

```
dme_hospital/
├── app/
│   ├── controllers/          # Contrôleurs MVC
│   │   ├── AuthController.php
│   │   ├── PatientController.php
│   │   ├── ConsultationController.php
│   │   ├── LaboratoireController.php
│   │   └── ...
│   ├── models/              # Modèles de données
│   │   ├── Patient.php
│   │   ├── Consultation.php
│   │   └── ...
│   ├── services/            # Services métier
│   │   ├── Auth.php
│   │   ├── DataService.php
│   │   ├── NotificationService.php
│   │   └── ...
│   ├── middleware/          # Middlewares
│   │   └── CompressionMiddleware.php
│   └── views/               # Vues (HTML/PHP)
│       ├── layouts/
│       ├── patients/
│       ├── consultations/
│       └── ...
├── config/                  # Configuration
│   ├── config.php
│   ├── database.php
│   └── routes.php
├── database/                # Scripts SQL
│   ├── dme_hospital.sql
│   └── ...
├── public/                  # Fichiers publics
│   ├── css/
│   ├── js/
│   └── images/
├── uploads/                 # Fichiers uploadés
├── backups/                 # Sauvegardes
├── docs/                    # Documentation
├── index.php               # Point d'entrée
└── README.md               # Ce fichier
```

---

## 🎯 Modules

### 1. Gestion Patients

**Fichiers principaux:**
- `app/controllers/PatientController.php`
- `app/models/Patient.php`
- `app/views/patients/`

**Routes:**
```
GET  /patients              - Liste des patients
GET  /patients/nouveau      - Formulaire nouveau patient
POST /patients/store        - Enregistrer patient
GET  /patients/dossier/{id} - Dossier patient
```

### 2. Consultations

**Fichiers principaux:**
- `app/controllers/ConsultationController.php`
- `app/views/consultations/formulaire/`

**Routes:**
```
GET  /consultation                    - Sélection patient
POST /consultation/commencer          - Démarrer consultation
GET  /consultation/formulaire         - Formulaire (7 étapes)
POST /consultation/sauvegarder        - Sauvegarder étape
GET  /consultation/recapitulatif/{id} - Récapitulatif
```

### 3. Laboratoire

**Fichiers principaux:**
- `app/controllers/LaboratoireController.php`
- `app/services/LaboratoireAPI.php`

**Routes:**
```
GET  /laboratoire                     - Dashboard
GET  /laboratoire/saisie/{id}         - Saisie résultats
POST /laboratoire/valider-resultats   - Valider
GET  /api/laboratoire/stream          - SSE temps réel
```

### 4. Télémédecine

**Fichiers principaux:**
- `app/controllers/TelemedecinController.php`
- `app/services/TelemedecinService.php`

**Routes:**
```
GET  /telemedecine                    - Dashboard
GET  /telemedecine/planifier          - Planifier consultation
GET  /telemedecine/consultation/{id}  - Salle vidéo
GET  /telemedecine/surveillance       - Surveillance
```

---

## 🚀 Utilisation

### Démarrage Rapide

1. **Démarrer XAMPP**
```bash
# Démarrer Apache et MySQL
```

2. **Accéder à l'application**
```
http://localhost/dme_hospital
```

3. **Se connecter**
```
Email: admin@hospital.com
Mot de passe: Admin123
```

### Workflow Typique

#### 1. Créer un Patient

```
Menu > Patients > Nouveau Patient
Remplir le formulaire
Enregistrer
```

#### 2. Consultation

```
Dossier Patient > Nouvelle Consultation
Suivre les 7 étapes:
  1. Anamnèse
  2. Examen physique
  3. Hypothèses diagnostiques
  4. Examens paracliniques
  5. Traitement
  6. Surveillance
  7. Suivi
Valider et imprimer ordonnance
```

#### 3. Demande Laboratoire

```
Dossier Patient > Demander Bilans
Sélectionner examens
Envoyer au laboratoire
```

#### 4. Résultats Laboratoire

```
Menu > Laboratoire
Sélectionner demande
Saisir résultats
Valider
→ Notification automatique au médecin
```

---

## 🔌 API

### API Laboratoire Temps Réel

**Endpoint SSE:**
```
GET /api/laboratoire/stream
```

**Exemple JavaScript:**
```javascript
const eventSource = new EventSource('/dme_hospital/api/laboratoire/stream');

eventSource.addEventListener('nouveau_resultat', (e) => {
    const data = JSON.parse(e.data);
    console.log('Nouveau résultat:', data);
});

eventSource.addEventListener('alerte_critique', (e) => {
    const data = JSON.parse(e.data);
    alert('Alerte: ' + data.message);
});
```

### API REST

**Obtenir résultats patient:**
```
GET /api/laboratoire/patient/{id}
Response: {
    "success": true,
    "resultats": [...]
}
```

**Ajouter résultat:**
```
POST /api/laboratoire/ajouter
Body: {
    "patient_id": 1,
    "examen_id": 5,
    "valeur": 1.2,
    "unite": "g/L"
}
```

---

## 🔒 Sécurité

### Bonnes Pratiques Implémentées

✅ **Authentification**
- Hachage bcrypt des mots de passe
- Sessions sécurisées
- 2FA disponible
- Timeout automatique

✅ **Autorisation**
- Système de rôles (Admin, Médecin, Infirmier, etc.)
- Permissions granulaires
- Vérification à chaque requête

✅ **Protection Données**
- Encryption AES-256 des données sensibles
- Préparation des requêtes SQL (PDO)
- Validation des entrées
- Sanitization des sorties

✅ **Audit**
- Logs de toutes les actions
- Traçabilité complète
- Alertes de sécurité

### Configuration SSL (Production)

```apache
# .htaccess
RewriteEngine On
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
```

---

## 🐛 Dépannage

### Problème: Page blanche

**Solution:**
```bash
# Activer l'affichage des erreurs
# Dans config/config.php
ini_set('display_errors', 1);
error_reporting(E_ALL);

# Vérifier les logs
tail -f /xampp/apache/logs/error.log
```

### Problème: Erreur de connexion base de données

**Solution:**
```bash
# Vérifier MySQL
mysql -u root -p

# Vérifier les credentials dans config/database.php
# Recréer la base si nécessaire
```

### Problème: Upload de fichiers échoue

**Solution:**
```bash
# Vérifier php.ini
upload_max_filesize = 10M
post_max_size = 10M

# Vérifier permissions dossier uploads/
chmod 777 uploads/
```

### Problème: Sessions perdues

**Solution:**
```php
// Dans config/config.php
ini_set('session.gc_maxlifetime', 3600);
session_set_cookie_params(3600);
```

---

## 🤝 Contribution

### Comment Contribuer

1. **Fork le projet**
2. **Créer une branche** (`git checkout -b feature/AmazingFeature`)
3. **Commit** (`git commit -m 'Add AmazingFeature'`)
4. **Push** (`git push origin feature/AmazingFeature`)
5. **Pull Request**

### Standards de Code

- **PSR-12** pour PHP
- **Commentaires** en français
- **Tests** pour les nouvelles fonctionnalités
- **Documentation** à jour

---

## 📝 Changelog

### Version 2.0.0 (2024-01-15)
- ✨ Ajout module télémédecine
- ✨ API laboratoire temps réel
- ✨ Système de registres spécialisés
- 🔒 Amélioration sécurité (2FA, encryption)
- 🐛 Corrections bugs multiples

### Version 1.5.0 (2023-12-01)
- ✨ Module imagerie médicale
- ✨ Signatures électroniques
- ✨ Chat médical sécurisé

### Version 1.0.0 (2023-10-01)
- 🎉 Version initiale
- ✅ Gestion patients
- ✅ Consultations
- ✅ Laboratoire
- ✅ Pharmacie

---

## 📄 Licence

Ce projet est sous licence **MIT**. Voir le fichier `LICENSE` pour plus de détails.

```
MIT License

Copyright (c) 2024 DME Hospital

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction...
```

---

## 👥 Auteurs

- **Équipe DME Hospital** - *Développement initial*

---

## 🙏 Remerciements

- Bootstrap pour l'interface
- Jitsi Meet pour la télémédecine
- Chart.js pour les graphiques
- Font Awesome pour les icônes
- Communauté open source

---

## 📞 Support

- **Email:** support@dmehospital.com
- **Documentation:** https://docs.dmehospital.com
- **Issues:** https://github.com/votre-repo/dme_hospital/issues

---

## 🔗 Liens Utiles

- [Documentation Complète](docs/)
- [Guide d'Installation](docs/INSTALLATION.md)
- [Guide Utilisateur](docs/USER_GUIDE.md)
- [API Documentation](docs/API.md)
- [FAQ](docs/FAQ.md)

---

**Fait avec ❤️ pour améliorer les soins de santé**
