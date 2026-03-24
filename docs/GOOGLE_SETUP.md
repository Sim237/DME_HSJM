# Configuration Google Cloud Console pour Télémédecine

## 1. Créer un projet Google Cloud Console

1. Allez sur https://console.cloud.google.com/
2. Cliquez sur "Nouveau projet"
3. Nom du projet: "DME Hospital Telemedicine"
4. Cliquez sur "Créer"

## 2. Activer l'API Google Calendar

1. Dans le menu de gauche, allez à "APIs et services" > "Bibliothèque"
2. Recherchez "Google Calendar API"
3. Cliquez sur "Google Calendar API"
4. Cliquez sur "Activer"

## 3. Configurer OAuth2

### Écran de consentement OAuth
1. Allez à "APIs et services" > "Écran de consentement OAuth"
2. Choisissez "Externe" si vous n'avez pas Google Workspace
3. Remplissez les informations requises:
   - Nom de l'application: "DME Hospital"
   - Email d'assistance utilisateur: votre email
   - Domaines autorisés: localhost (pour les tests)

### Créer les identifiants OAuth2
1. Allez à "APIs et services" > "Identifiants"
2. Cliquez sur "Créer des identifiants" > "ID client OAuth"
3. Type d'application: "Application Web"
4. Nom: "DME Hospital Web Client"
5. URI de redirection autorisés:
   - http://localhost/dme_hospital/auth/google/callback
   - http://127.0.0.1/dme_hospital/auth/google/callback

### Récupérer les clés
Après création, vous obtiendrez:
- Client ID: 123456789-abcdef.apps.googleusercontent.com
- Client Secret: GOCSPX-abcdef123456

## 4. Configuration dans l'application

Remplacez dans GoogleMeetService.php:
- YOUR_GOOGLE_CLIENT_ID par votre Client ID
- YOUR_GOOGLE_CLIENT_SECRET par votre Client Secret

## 5. Test de l'intégration

1. Accédez à /telemedicine
2. Sélectionnez un patient avec email
3. Planifiez une réunion
4. Le système vous redirigera vers Google pour autorisation