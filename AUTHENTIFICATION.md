# Système d'Authentification DME Hospital

## Installation

### 1. Exécuter le script SQL
```sql
-- Exécuter le fichier database/auth_system.sql dans votre base de données
```

### 2. Comptes par défaut
- **Utilisateur**: admin
- **Mot de passe**: password
- **Rôle**: ADMINISTRATEUR

### 3. Rôles disponibles

#### ADMINISTRATEUR
- Accès complet à tous les modules
- Gestion des utilisateurs et paramètres

#### MEDECIN
- Patients (lecture/écriture)
- Consultations (lecture/écriture)
- Hospitalisation (lecture/écriture)
- Laboratoire (lecture/écriture)
- Pharmacie (lecture)
- Registres (lecture)

#### INFIRMIER
- Patients (lecture)
- Consultations (lecture)
- Hospitalisation (lecture/écriture)
- Pharmacie (lecture)
- Laboratoire (lecture)

#### ACCUEIL
- Patients (lecture/écriture)
- Consultations (lecture)

#### PHARMACIEN
- Patients (lecture)
- Pharmacie (lecture/écriture)

#### LABORANTIN
- Patients (lecture)
- Laboratoire (lecture/écriture)

#### GESTIONNAIRE
- Patients (lecture)
- Registres (lecture/écriture)
- Paramètres (lecture)

## Utilisation

### Connexion
- Accédez à `/login`
- Utilisez email ou nom d'utilisateur + mot de passe

### Gestion des permissions
Les permissions sont automatiquement vérifiées :
- Dans les contrôleurs avec `$this->auth->requirePermission()`
- Dans les vues avec `hasModuleAccess()`

### Sécurité
- Mots de passe hachés avec `password_hash()`
- Sessions sécurisées
- Vérification des permissions à chaque action
- Protection contre l'auto-suppression

## Personnalisation

### Ajouter un nouveau rôle
1. Modifier l'ENUM dans la table `users`
2. Ajouter les permissions dans `role_permissions`
3. Mettre à jour la page de connexion

### Modifier les permissions
Modifier directement la table `role_permissions` ou créer une interface d'administration.