# Guide de déploiement DME Hospital — Ubuntu Server

## ✅ Modifications déjà appliquées au code
Le fichier `app/services/SageSyncService.php` a été rendu **cross-platform** :
- `findPythonBinary()` détecte automatiquement Windows OU Linux
- `findPdftotextBinary()` détecte automatiquement Windows OU Linux
- `getPythonSearchTrace()` affiche les chemins Linux corrects sur Ubuntu
- `extractTextViaPython()` utilise `escapeshellarg()` sur Unix

## 📋 Procédure pour copier vers le serveur Ubuntu

### 1. Copier `SageSyncService.php` vers le serveur
Depuis Windows (PowerShell ou WinSCP) :
```bash
scp C:\xampp\htdocs\dme_hospital\app\services\SageSyncService.php utilisateur@serveur:/var/www/html/dme/app/services/
```

### 2. Vérifier les prérequis sur Ubuntu
```bash
# Vérifier Python 3 et pdfplumber
python3 --version
python3 -c "import pdfplumber; print(pdfplumber.__version__)"

# Vérifier les extensions PHP
php -m | grep -E 'pdo_odbc|odbc|pdo_mysql'
```

Si `pdo_odbc` manque :
```bash
sudo apt update
sudo apt install php-odbc unixodbc unixodbc-dev
sudo systemctl restart apache2
```

### 3. Optionnel — installer poppler-utils (pdftotext)
Pour avoir un moteur d'extraction PDF supplémentaire :
```bash
sudo apt install poppler-utils
which pdftotext   # → /usr/bin/pdftotext
```

### 4. Vérifier les permissions
Apache sur Ubuntu tourne généralement sous `www-data`. Le dossier projet doit être accessible :
```bash
sudo chown -R www-data:www-data /var/www/html/dme
sudo chmod -R 755 /var/www/html/dme
# Pour les dossiers d'écriture (uploads, logs) :
sudo chmod -R 775 /var/www/html/dme/uploads /var/www/html/dme/logs
```

### 5. Tester depuis le navigateur
- Dashboard Sage : `http://IP_SERVEUR/dme/pharmacie/sage`
- Onglet **Import PDF** → diagnostic Python doit montrer ✅ pour `/usr/bin/python3`

## 🔍 Diagnostic en cas de problème

Le script `run_migration_python_path.php` fonctionne aussi sur Ubuntu pour ajouter la colonne :
```
http://IP_SERVEUR/dme/run_migration_python_path.php
```

Si Python ne s'exécute pas malgré une présence physique :
- Vérifier que `www-data` a le droit d'exécuter Python : `sudo -u www-data python3 --version`
- Vérifier que `exec()` n'est pas désactivé dans `php.ini` : `disable_functions=` ne doit pas contenir `exec`

## 🌐 Test connexion Sage depuis Ubuntu
Le serveur Sage est sur Windows (`SAGE25001\SAGE100`). Depuis Ubuntu, utilisez :
- Le pilote `FreeTDS` ou le **Microsoft ODBC Driver for SQL Server**

Installation Microsoft ODBC Driver 18 sur Ubuntu :
```bash
curl https://packages.microsoft.com/keys/microsoft.asc | sudo apt-key add -
curl https://packages.microsoft.com/config/ubuntu/$(lsb_release -rs)/prod.list | \
    sudo tee /etc/apt/sources.list.d/mssql-release.list
sudo apt update
sudo ACCEPT_EULA=Y apt install msodbcsql18 unixodbc-dev
```

Vérifier la connectivité réseau :
```bash
ping -c 2 192.168.1.70
nc -zv 192.168.1.70 1433     # Port SQL Server par défaut
nc -zv 192.168.1.70 1434     # Port SQL Browser
```
