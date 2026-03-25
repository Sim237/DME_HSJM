# README-UPDATE — Journal des modifications DME Hospital (HSJM)

> Ce fichier trace toutes les modifications apportées au projet DME Hospital.
> Il doit être mis à jour **avant chaque commit git**, avec les fichiers exacts modifiés,
> les numéros de lignes et les diffs pour faciliter les débugages.

---

## Informations du projet

| Champ | Valeur |
|-------|--------|
| Nom du projet | DME Hospital — HSJM |
| Version initiale | 1.0.0 |
| Branche principale | `main` |
| Dépôt GitHub | https://github.com/Sim237/DME_HSJM.git |
| URL locale (dev) | `http://localhost:8080/` |
| Base de données | `dme_hospital` |
| Timezone | Africa/Douala (UTC+1) |
| Langue | Français |

---

## Environnement de développement

### Prérequis
- **MAMP PRO** (macOS) → [https://www.mamp.info](https://www.mamp.info)
- PHP 8.3.30 (via MAMP : `/Applications/MAMP/bin/php/php8.3.30/bin/php`)
- MySQL 8.0 (via MAMP : port 3306)
- Git

### Configuration MAMP (standardisée pour tous les développeurs)
- **Apache Port** : `8080`
- **MySQL Port** : `3306`
- **Document Root** : pointer **directement** sur `/chemin/vers/DME_HSJM`
  _(pas sur le dossier parent — c'est la source de nombreux bugs de routing)_
- **PHP Version** : 8.3.30

### Accès après démarrage MAMP
| Ressource | URL |
|---|---|
| Application | `http://localhost:8080/` |
| phpMyAdmin | `http://localhost:8080/phpMyAdmin/` |

### Identifiants de connexion (DEV uniquement — à changer en production)
| Utilisateur | Rôle | Mot de passe |
|-------------|------|--------------|
| `admin` | ADMIN | `admin123` |
| `dr.house` | MEDECIN | `123456` |
| `dr.grey` | MEDECIN | `123456` |
| `inf.ratched` | INFIRMIER | `123456` |
| `ph.white` | PHARMACIEN | `123456` |
| `lab.dexter` | LABORANTIN | `123456` |
| `sec.pam` | PARAMETRES | `123456` |

> ⚠️ **Ces mots de passe DOIVENT être changés avant toute mise en production.**

---

## État actuel des fichiers de configuration

| Fichier | État actuel | Note |
|---------|-------------|------|
| `config/config.php` | ✅ Charge depuis `.env` via `loadEnv()` | Ne pas hardcoder ici |
| `config/database.php` | ⚠️ **Hardcodé** (`password = "root"`) | Revenu en arrière après conflit avec collègue — à corriger |
| `.env` | ✅ Présent localement, jamais commité | Chaque dev a le sien |
| `.env.example` | ✅ Commité sur git | Template à copier |
| `.htaccess` | ✅ `RewriteBase /` | Ne pas changer sauf déplacement du projet |
| `.gitignore` | ✅ Protège `.env`, uploads, logs | Complet |
| `.claude/launch.json` | ✅ Configurations serveurs dev | Voir section dédiée |

---

## Journal des modifications

---

### [2026-03-24] — Modification du préfixe de numéro de dossier patient

| Champ | Détail |
|-------|--------|
| **Date** | 2026-03-24 |
| **Auteur** | Claude (assistant IA) |
| **Type** | Modification fonctionnelle |
| **Impact** | Nouveaux patients uniquement — anciens conservent leur format |
| **Commit** | `2c8c718` |

**Fichier modifié :**
- `app/models/Patient.php` — **ligne 255**

**Diff exact :**
```php
// AVANT
return 'P-' . $annee . '-' . str_pad($numero, 5, '0', STR_PAD_LEFT);

// APRÈS
return 'HSJM-' . $annee . '-' . str_pad($numero, 5, '0', STR_PAD_LEFT);
```

**Format du numéro de dossier :**
```
Ancien : P-2026-00001
Nouveau : HSJM-2026-00001
```

**Points d'attention pour le débugage :**
- Les patients déjà en base conservent l'ancien format `P-XXXX-XXXXX` — aucune migration n'a été faite.
- Si un doublon survient : la séquence utilise `COUNT(*) + 1` (ligne ~250) — risque si des patients sont supprimés. À remplacer par `MAX(id) + 1`.
- Le champ `numero_dossier` en base doit être `VARCHAR(20)` minimum pour accueillir `HSJM-XXXX-XXXXX`.

---

### [2026-03-24] — Correction erreur 500 Apache (boucle de redirection infinie)

| Champ | Détail |
|-------|--------|
| **Date** | 2026-03-24 |
| **Auteur** | Claude (assistant IA) |
| **Type** | Correction de bug critique |
| **Impact** | L'application ne démarrait pas du tout |
| **Commit** | `b0c5ea5` |

**Erreur Apache dans les logs :**
```
AH00124: Request exceeded the limit of 10 internal redirects due to probable configuration error.
```

**Cause racine :**
Le DocumentRoot MAMP pointe directement sur `/Users/test/Movies/DME_HSJM`.
Le `.htaccess` avait `RewriteBase /dme_hospital/` → Apache cherchait un sous-dossier inexistant → boucle infinie → HTTP 500.

**Fichiers modifiés :**

`① .htaccess` — **ligne 9**
```apache
# AVANT (commit collègue)
RewriteBase /dme_hospital/

# APRÈS
RewriteBase /
```

`② config/config.php` — **ligne 5** _(avant la refonte .env)_
```php
// AVANT
define('BASE_URL', 'http://localhost:8080/dme_hospital/');

// APRÈS
define('BASE_URL', 'http://localhost:8080/');
```

**Règle de débugage :**
Si cette erreur 500 réapparaît → vérifier que `RewriteBase` dans `.htaccess` correspond au chemin entre le DocumentRoot MAMP et la racine du projet.

---

### [2026-03-24] — Correction erreur MySQL "Access denied" (page blanche après login)

| Champ | Détail |
|-------|--------|
| **Date** | 2026-03-24 |
| **Auteur** | Claude (assistant IA) |
| **Type** | Correction de bug critique |
| **Impact** | Connexion impossible — page blanche après soumission du login |
| **Commit** | `2c8c718` puis refactorisé dans `0495072` |

**Erreur affichée :**
```
SQLSTATE[HY000] [1045] Access denied for user 'root'@'localhost' (using password: YES)
```

**Cause :**
Le fichier `config/database.php` contenait le mot de passe MySQL du collègue (`Franck@2903`). Sur la machine locale, MAMP utilise `root` par défaut.

**Fichiers modifiés :**

`① config/database.php` — **ligne 6**
```php
// AVANT
private $password = "Franck@2903";

// APRÈS
private $password = "root";
```

`② config/config.php` — **ligne 19** _(avant la refonte .env)_
```php
// AVANT
define('DB_PASS', 'Franck@2903');

// APRÈS
define('DB_PASS', 'root');
```

**⚠️ État actuel de database.php :**
`database.php` est **toujours hardcodé** avec `password = "root"` (revenu en arrière après conflit git avec le collègue). Voir l'entrée `.env` ci-dessous pour l'historique complet.

---

### [2026-03-24] — Correction chevauchement sidebar / contenu principal au redimensionnement

| Champ | Détail |
|-------|--------|
| **Date** | 2026-03-24 |
| **Auteur** | Claude (assistant IA) |
| **Type** | Correction de bug CSS/UX |
| **Impact** | La sidebar chevauchait le contenu principal en dessous de 992px de largeur |
| **Commit** | `b0c5ea5` |

**Fichiers modifiés :**

`① public/css/style.css` — section `@media (max-width: 992px)` (~ligne 1037-1045)
```css
/* BUG 1 — AVANT : mettait --sidebar-width à 100% → margin-left = 100% sur le contenu */
@media (max-width: 992px) {
    :root { --sidebar-width: 100%; }   /* ← SUPPRIMÉ */
}

/* BUG 2 — AVANT : ciblait <main> mais pas .main-content */
main { margin-left: 0; }

/* APRÈS : cible les deux avec !important */
main, .main-content { margin-left: 0 !important; }
```

`② public/css/responsive.css` — section tablette
```css
/* Mise à jour du breakpoint sidebar pour aligner sur 992px */
```

`③ app/views/layouts/topbar.php` — **ligne 5** (bouton hamburger)
```html
<!-- AVANT : bouton disparaissait dès 768px alors que la sidebar se ferme à 992px -->
<button class="d-md-none" ...>

<!-- APRÈS : bouton visible jusqu'à 992px -->
<button class="d-lg-none" id="sidebarToggleBtn" ...>
```

**Fonctionnalités ajoutées dans topbar.php :**
- Overlay sombre cliquable pour fermer la sidebar en mobile/tablette
- Blocage du scroll body quand la sidebar est ouverte
- Fermeture automatique de la sidebar au redimensionnement ≥ 992px

---

### [2026-03-24] — Mise en place du système .env (suppression des credentials hardcodés)

| Champ | Détail |
|-------|--------|
| **Date** | 2026-03-24 |
| **Auteur** | Claude (assistant IA) |
| **Type** | Refactoring sécurité |
| **Impact** | Chaque développeur gère ses propres credentials sans casser l'autre |
| **Commit** | `0495072` |

**Problème résolu :**
Les credentials MySQL et l'URL étaient hardcodés dans des fichiers PHP commités sur git. Chaque `git pull` cassait l'application de l'autre développeur.

**Fichiers créés :**

`① .env.example` _(commité sur git — template)_
```env
DB_HOST=localhost
DB_NAME=dme_hospital
DB_USER=root
DB_PASS=          ← chaque dev met le sien
BASE_URL=http://localhost:8080/
APP_ENV=development
APP_DEBUG=true
```

`② .env` _(jamais commité — local à chaque dev)_
```env
DB_PASS=root              ← machine locale
BASE_URL=http://localhost:8080/
```

`③ .gitignore` _(créé)_
- Protège : `.env`, `assets/uploads/*`, `*.log`, `node_modules/`, `.DS_Store`

**Fichier modifié :**

`④ config/config.php` — **entièrement réécrit** pour charger depuis `.env`
```php
// Fonction loadEnv() ajoutée — charge .env depuis la racine
loadEnv(__DIR__ . '/../.env');

// Constantes définies depuis .env (jamais hardcodées)
define('BASE_URL', getenv('BASE_URL') ?: 'http://localhost:8080/');
define('DB_HOST',  getenv('DB_HOST')  ?: 'localhost');
define('DB_PASS',  getenv('DB_PASS')  ?: '');
// ...
```

**⚠️ Problème subsistant — `config/database.php` :**
Ce fichier devait aussi lire depuis les constantes définies par `config.php`, mais il a été **réécrasé par un conflit git** avec le collègue et est revenu à une version hardcodée :
```php
// ÉTAT ACTUEL (hardcodé — à corriger)
private $password = "root";
```
**→ À faire : faire lire `database.php` depuis les constantes `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`.**

**Workflow correct pour chaque développeur :**
```bash
git pull origin main
cp .env.example .env        # seulement si .env absent
# éditer .env avec ses propres valeurs
# Ne JAMAIS : git add .env
```

| Développeur | `DB_PASS` dans `.env` | `BASE_URL` dans `.env` |
|---|---|---|
| Dev 1 (toi) | `root` | `http://localhost:8080/` |
| Dev 2 (collègue) | `Franck@2903` | `http://localhost:8080/` |
| Production | mot de passe serveur | `https://domaine/` |

---

### [2026-03-24] — Fix conflit git : credentials collègue cassaient l'application locale

| Champ | Détail |
|-------|--------|
| **Date** | 2026-03-24 |
| **Auteur** | Claude (assistant IA) |
| **Type** | Correction de conflit git |
| **Impact** | Critique — application inaccessible après `git pull` |
| **Commit** | `b0c5ea5` |

**Ce qui s'est passé :**
Le collègue a résolu son problème de connexion MySQL en hardcodant ses propres credentials dans `config/config.php` et a pushé. Lors du `git pull` suivant, l'application locale est tombée.

**Fichiers impactés par le commit du collègue :**
- `config/config.php` — `BASE_URL` repassée à `/dme_hospital/` et `DB_PASS` en dur
- `.htaccess` — `RewriteBase /dme_hospital/` réintroduit
- `.gitignore` — `config/config.php` et `config/database.php` ajoutés par erreur

**Corrections apportées :**

`① .gitignore` — suppression des lignes erronées
```
# SUPPRIMÉ (ces fichiers DOIVENT être trackés sur git)
config/config.php
config/database.php
```

`② config/config.php` — restauration du chargement via `.env`
```php
// Restauré : BASE_URL vient du .env, pas hardcodé
define('BASE_URL', getenv('BASE_URL') ?: 'http://localhost:8080/');
```

`③ .htaccess` — ligne 9 restaurée
```apache
RewriteBase /   ← restauré
```

**Instructions communiquées au collègue après ce fix :**
1. `git pull origin main`
2. Éditer son `.env` : `BASE_URL=http://localhost:8080/` et `DB_PASS=Franck@2903`
3. Configurer son MAMP DocumentRoot directement sur le dossier DME_HSJM

---

### [2026-03-24] — Dashboard Laboratoire cockpit + redirection post-login

| Champ | Détail |
|-------|--------|
| **Date** | 2026-03-24 |
| **Auteur** | Claude (assistant IA) |
| **Type** | Nouvelle fonctionnalité + Correction |
| **Impact** | UX — le laborantin accède directement à son cockpit dès la connexion |
| **Commit** | `9eca682` |

**Problèmes résolus :**
1. `lab.dexter` (LABORANTIN) atterrissait sur `/dashboard` générique (avec sidebar) au lieu du cockpit `/laboratoire`
2. La vue `laboratoire/dashboard.php` affichait la sidebar au lieu de la masquer comme la pharmacie

**Fichiers modifiés :**

`① app/controllers/AuthController.php` — méthode `verifyService()` — **lignes 114–123**
```php
// AVANT — tout tombait dans le else
else {
    header('Location: ' . BASE_URL . 'dashboard');
}

// APRÈS — cas dédiés ajoutés avant le else
elseif (stripos($serviceKey, 'laboratoire') !== false || $user['role'] === 'LABORANTIN') {
    header('Location: ' . BASE_URL . 'laboratoire');  // ← cockpit dédié
}
elseif (stripos($serviceKey, 'pharmacie') !== false || $user['role'] === 'PHARMACIEN') {
    header('Location: ' . BASE_URL . 'pharmacie');    // ← cohérence pharmacie
}
else {
    header('Location: ' . BASE_URL . 'dashboard');
}
```

`② app/views/laboratoire/dashboard.php` — **entièrement réécrit**

_Avant :_ vue standard avec sidebar incluse (`require_once sidebar.php`) dans un `container-fluid`

_Après :_ cockpit fullscreen — même pattern que `pharmacie/dashboard.php`
```css
/* Lignes 14-16 — masquage sidebar */
.sidebar        { display: none !important; }
main, .main-content { margin-left: 0 !important; width: 100% !important; }
```

**Éléments du cockpit laboratoire :**
- Header : `LABORATOIRE CENTRAL • HSJM` (bleu foncé gradient `#0f4c75` → `#1b6ca8`)
- Horloge néon cyan (`#00e5ff`) — temps réel via JS
- 4 KPI cards : Demandes du jour / Urgents / Délai moyen / Taux Qualité
- Barre de filtres : statut, priorité, recherche patient
- Table des demandes avec badges statut colorés et délai critique clignotant (rouge)
- Boutons par ligne : Traiter / Saisir résultats / Valider / Imprimer
- Auto-refresh toutes les 30 secondes

**Logique de redirection — double sécurité :**
| Condition | Méthode |
|---|---|
| Nom service contient "laboratoire" | `stripos($serviceKey, 'laboratoire')` |
| Rôle utilisateur = LABORANTIN | `$user['role'] === 'LABORANTIN'` |
> Les deux conditions sont évaluées avec `||` — l'une suffit pour rediriger.

---

### [2026-03-25] — Création du fichier de lancement des serveurs dev

| Champ | Détail |
|-------|--------|
| **Date** | 2026-03-25 |
| **Auteur** | Claude (assistant IA) |
| **Type** | Configuration outillage |
| **Impact** | Permet de démarrer les serveurs depuis Claude Code sans ouvrir MAMP |
| **Commit** | à commiter |

**Fichier créé :**

`① .claude/launch.json`
```json
{
  "version": "0.0.1",
  "configurations": [
    {
      "name": "PHP Built-in Server",
      "runtimeExecutable": "/Applications/MAMP/bin/php/php8.3.30/bin/php",
      "runtimeArgs": ["-S", "localhost:8080", "-t", "/Users/test/Movies/DME_HSJM"],
      "port": 8080
    },
    {
      "name": "MAMP Apache",
      "runtimeExecutable": "/Applications/MAMP/bin/startApache.sh",
      "runtimeArgs": [],
      "port": 8080
    },
    {
      "name": "MAMP MySQL",
      "runtimeExecutable": "/Applications/MAMP/bin/startMysql.sh",
      "runtimeArgs": [],
      "port": 3306
    }
  ]
}
```

**Usage :**
- **MAMP Apache** + **MAMP MySQL** = configuration recommandée (supporte `.htaccess` et routing)
- **PHP Built-in Server** = alternative légère mais **ne supporte pas `.htaccess`** → routing cassé

---

## ⚠️ Points critiques à corriger (backlog)

| # | Fichier | Description | Priorité | Statut |
|---|---------|-------------|----------|--------|
| 1 | `config/database.php` | Hardcodé `password = "root"` — doit lire depuis les constantes `.env` comme `config.php` | 🔴 Haute | En attente |
| 2 | `app/models/Patient.php` ~ligne 250 | Générateur de numéro utilise `COUNT(*)+1` → risque doublon — remplacer par `MAX(id)+1` | 🔴 Haute | En attente |
| 3 | Base de données | Migrer les anciens numéros `P-XXXX-XXXXX` vers `HSJM-XXXX-XXXXX` si nécessaire | 🟡 Moyenne | En attente |
| 4 | `app/controllers/AuthController.php` | Vérifier et corriger les redirections des rôles INFIRMIER, MEDECIN, GESTIONNAIRE | 🟡 Moyenne | En attente |
| 5 | Tous les mots de passe users | Changer avant mise en production (`admin123`, `123456`) | 🔴 Haute | En attente |

---

## Template pour nouvelle entrée

```markdown
### [YYYY-MM-DD] — Titre court et descriptif

| Champ | Détail |
|-------|--------|
| **Date** | YYYY-MM-DD |
| **Auteur** | Prénom / Claude |
| **Type** | Ajout / Modification / Correction / Suppression / Refactoring |
| **Impact** | Description de l'impact utilisateur ou technique |
| **Commit** | hash git |

**Fichiers modifiés :**

`① chemin/fichier.php` — **ligne(s) X–Y**
```language
// AVANT
ancien code

// APRÈS
nouveau code
```

**Points d'attention / Débugage :**
- ...
```
