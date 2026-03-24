# README-UPDATE — Journal des modifications DME Hospital (HSJM)

> Ce fichier trace toutes les modifications apportées au projet DME Hospital.
> Il doit être mis à jour à chaque intervention, avant tout commit git.

---

## Informations du projet

| Champ | Valeur |
|-------|--------|
| Nom du projet | DME Hospital — HSJM |
| Version initiale | 1.0.0 |
| Branche principale | `main` |
| URL locale (dev) | `http://localhost:8080/dme_hospital/` |
| Base de données | `dme_hospital` |
| Timezone | Africa/Douala (UTC+1) |
| Langue | Français |

---

## Environnement de développement recommandé

### Prérequis
- **MAMP** (recommandé sur macOS) → [https://www.mamp.info](https://www.mamp.info)
- PHP 8.0+
- MySQL 8.0+ / MariaDB 10.5+
- Git

### Configuration MAMP
- Apache Port : `8080`
- MySQL Port : `3306`
- Document Root : pointer vers `/Users/test/Movies/` (ou dossier parent de DME_HSJM)
- PHP Version : 8.0 minimum

---

## Journal des modifications

---

### [2026-03-24] — Modification du préfixe de numéro de dossier patient

| Champ | Détail |
|-------|--------|
| **Date** | 2026-03-24 |
| **Auteur** | Claude (assistant IA) |
| **Type** | Modification |
| **Impact** | Fonctionnel — Nouveaux patients uniquement |

**Fichier modifié :**
- `app/models/Patient.php` — ligne 255

**Ancien comportement :**
```
Préfixe : P
Format   : P-{ANNÉE}-{XXXXX}
Exemple  : P-2026-00001
```

**Nouveau comportement :**
```
Préfixe : HSJM
Format   : HSJM-{ANNÉE}-{XXXXX}
Exemple  : HSJM-2026-00001
```

**Diff :**
```php
// AVANT
return 'P-' . $annee . '-' . str_pad($numero, 5, '0', STR_PAD_LEFT);

// APRÈS
return 'HSJM-' . $annee . '-' . str_pad($numero, 5, '0', STR_PAD_LEFT);
```

**Notes / Points d'attention :**
- Les patients déjà enregistrés en base conservent leur ancien format `P-XXXX-XXXXX`.
- Si une migration des anciens numéros est souhaitée, prévoir un script SQL `UPDATE`.
- Le champ `numero_dossier` dans la table `patients` doit avoir une taille suffisante (VARCHAR >= 20).
- Risque de doublon identifié : la séquence utilise `COUNT(*) + 1`, à améliorer ultérieurement avec `MAX()` ou une séquence dédiée.

---

---

### [2026-03-24] — Correction erreur 500 au démarrage (boucle de redirection Apache)

| Champ | Détail |
|-------|--------|
| **Date** | 2026-03-24 |
| **Auteur** | Claude (assistant IA) |
| **Type** | Correction de bug |
| **Impact** | Critique — l'application ne démarrait pas du tout |

**Erreur Apache :**
```
AH00124: Request exceeded the limit of 10 internal redirects due to probable configuration error.
```

**Cause racine :**
Le DocumentRoot MAMP PRO pointe directement sur `/Users/test/Movies/DME_HSJM`.
Le `.htaccess` avait `RewriteBase /dme_hospital/` (sous-dossier inexistant).
Apache entrait dans une boucle infinie de redirections → erreur 500.

**Fichiers modifiés :**
- `.htaccess` — ligne 9
- `config/config.php` — ligne 5

**Diff `.htaccess` :**
```apache
# AVANT
RewriteBase /dme_hospital/

# APRÈS
RewriteBase /
```

**Diff `config/config.php` :**
```php
// AVANT
define('BASE_URL', 'http://localhost:8080/dme_hospital/');

// APRÈS
define('BASE_URL', 'http://localhost:8080/');
```

**URL d'accès correcte :**
```
http://localhost:8080/
```
(et non http://localhost:8080/dme_hospital/)

**Notes :**
- Si le projet est un jour déplacé dans un sous-dossier, ces deux valeurs devront être remises à jour.
- Configuration MAMP PRO vérifiée : `DocumentRoot "/Users/test/Movies/DME_HSJM"` ✅

---

### [2026-03-24] — Correction erreur "Access denied" MySQL au login

| Champ | Détail |
|-------|--------|
| **Date** | 2026-03-24 |
| **Auteur** | Claude (assistant IA) |
| **Type** | Correction de bug |
| **Impact** | Critique — connexion impossible, page blanche après login |

**Erreur :**
```
SQLSTATE[HY000] [1045] Access denied for user 'root'@'localhost' (using password: YES)
```

**Cause :**
Le projet était configuré avec le mot de passe MySQL du développeur (`Franck@2903`).
Sur la machine locale, MAMP utilise le mot de passe par défaut `root`.

**Fichiers modifiés :**
- `config/database.php` — ligne 6
- `config/config.php` — ligne 19

**Diff :**
```php
// AVANT (dans database.php et config.php)
"Franck@2903"

// APRÈS
"root"  // mot de passe MAMP local par défaut
```

**⚠️ Important pour la mise en production :**
Avant tout déploiement, remettre les vraies credentials du serveur de production.
Ne jamais commiter les mots de passe en clair → prévoir un fichier `.env` (amélioration future).

**État base de données :**
- Base `dme_hospital` ✅ présente et complète
- 120 tables ✅ toutes importées

---

---

### [2026-03-24] — Diagnostic et correction des identifiants de connexion

| Champ | Détail |
|-------|--------|
| **Date** | 2026-03-24 |
| **Auteur** | Claude (assistant IA) |
| **Type** | Correction de bug + Investigation |
| **Impact** | Critique — impossible de se connecter |

**Problème :**
Les utilisateurs existent en base mais les mots de passe sont inconnus.
`ph.white` et `lab.dexter` avaient des **hashes bcrypt corrompus/tronqués** (probablement lors de l'import SQL).

**Mots de passe découverts (environnement DEV uniquement) :**

| Utilisateur | Rôle | Mot de passe | Email |
|-------------|------|--------------|-------|
| `admin` | ADMIN | `admin123` | admin@hospital.com |
| `dr.house` | MEDECIN | `123456` | house@hopital.com |
| `dr.grey` | MEDECIN | `123456` | grey@hopital.com |
| `inf.ratched` | INFIRMIER | `123456` | — |
| `ph.white` | PHARMACIEN | `123456` *(réinitialisé)* | white@hopital.com |
| `lab.dexter` | LABORANTIN | `123456` *(réinitialisé)* | dexter@hopital.com |
| `sec.pam` | PARAMETRES | `123456` | pam@hopital.com |

**Action effectuée en base :**
```sql
UPDATE users SET password = '[hash_bcrypt_123456]'
WHERE username IN ('ph.white', 'lab.dexter', 'testinfirmier', 't.param');
```

**⚠️ IMPORTANT — Avant mise en production :**
Tous ces mots de passe DOIVENT être changés. Ne jamais déployer avec `admin123` ou `123456`.

---

## Améliorations identifiées / À faire

| # | Description | Priorité | Statut |
|---|-------------|----------|--------|
| 1 | Corriger le générateur de numéro de dossier (COUNT → MAX) pour éviter les doublons | Haute | En attente |
| 2 | Migrer les anciens numéros `P-XXXX` vers `HSJM-XXXX` si nécessaire | Moyenne | En attente |

---

## Template pour nouvelle entrée

```
### [YYYY-MM-DD] — Titre de la modification

| Champ | Détail |
|-------|--------|
| **Date** | YYYY-MM-DD |
| **Auteur** | Nom |
| **Type** | Ajout / Modification / Correction / Suppression |
| **Impact** | Description de l'impact |

**Fichiers modifiés :**
- `chemin/fichier.php` — ligne X

**Description :**
...

**Notes / Points d'attention :**
...
```
