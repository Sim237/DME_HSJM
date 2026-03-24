# Documentation: Système de Gestion des Ordonnances et Bilans

## Vue d'ensemble

Cette implémentation ajoute deux nouvelles fonctionnalités principales au système DME Hospital :

1. **Ajouter une Ordonnance** - Permet au médecin de créer une prescription médicale depuis le dossier du patient
2. **Demander un Bilan** - Permet au médecin de demander un bilan de laboratoire ou d'imagerie

## Architecture et Flux de Données

### 1. Ordonnances (Prescriptions)

#### Flux:
```
Dossier Patient
    ↓
Bouton "Ajouter une Ordonnance"
    ↓
Formulaire de Prescription (create.php)
    ↓
Sélection des médicaments + Posologie
    ↓
POST → prescription/save
    ↓
PrescriptionController.save()
    ↓
INSERT prescriptions + prescription_medicaments
    ↓
Notification à la Pharmacie
    ↓
Redirection vers Page d'Impression
```

#### Fichiers concernés:
- **Vue**: [app/views/prescriptions/create.php](app/views/prescriptions/create.php)
  - Formulaire multi-étapes pour sélectionner médicaments
  - Gestion dynamique de la liste avec JS
  - Recherche en temps réel des médicaments

- **Contrôleur**: [app/controllers/PrescriptionController.php](app/controllers/PrescriptionController.php)
  - `create()` - Affiche le formulaire avec liste des médicaments
  - `save()` - Sauvegarde la prescription et notifie la pharmacie
  - `print()` - Génère l'ordonnance imprimable

- **Modèle**: Déjà existant `Prescription.php`
  - Table: `prescriptions`
  - Table détails: `prescription_medicaments`

#### Tables impliquées:
```sql
prescriptions:
  - id
  - patient_id
  - medecin_id
  - consultation_id (nullable)
  - date_prescription
  - statut (EN_ATTENTE, EN_COURS, TERMINE, ANNULE)

prescription_medicaments:
  - id
  - prescription_id
  - medicament_id
  - nom_medicament
  - posologie
  - duree
  - quantite_prescrite
```

---

### 2. Bilans (Laboratoire et Imagerie)

#### Flux général:

```
Dossier Patient
    ↓
Bouton "Demander un Bilan"
    ↓
Modal Demande de Bilan
    ├── Sélection Type: Laboratoire ou Imagerie
    ├── Remplissage formulaire specific
    └── POST → bilan/save
        ↓
        BilanController.save()
        ├── Type = Laboratoire
        │   ├── INSERT examens
        │   ├── INSERT examen_details
        │   └── Notification au Laboratoire
        │
        └── Type = Imagerie
            ├── INSERT imagerie_medicale
            └── Notification à l'Imagerie
        ↓
Redirection vers Dossier Patient avec confirmation
```

#### Sous-flux 2.1: Bilan de Laboratoire

**Point d'Entrée**: Modal dans dossier_patient.php
```
Sélectionner Examen:
  - Hémogramme (NFS)
  - Bilan Métabolique
  - Bilan Lipidique
  - Fonction Hépatique
  - Fonction Rénale
  - Groupe Sanguin
  - Bilan de Coagulation
  - Autres

Sélectionner Urgence:
  - Normal
  - Urgent
  - Très Urgent

Ajouter Observations/Indications Cliniques
```

**Processing**:
- Utilise le modèle `Laboratoire.creerDemande()`
- Crée un enregistrement dans la table `examens`
- Crée les lignes de détails dans `examen_details`
- **Destination**: Liqui laboratoire peut voir ces demandes dans le tableau de bord

#### Sous-flux 2.2: Bilan d'Imagerie

**Point d'Entrée**: Modal dans dossier_patient.php
```
Sélectionner Type d'Imagerie:
  - Radiographie
  - Échographie
  - Scanner (TDM)
  - IRM
  - Mammographie
  - Autre

Préciser Zone/Organe à examiner:
  (champs texte libre avec suggestions rapides)
  - Thorax
  - Abdomen
  - Bassin
  - Crâne
  - Colonne Vertébrale

Sélectionner Urgence:
  - Normal
  - Urgent
  - Très Urgent

Ajouter Indications Cliniques
```

**Processing**:
- Insert dans la table `imagerie_medicale`
- Statut initial: `programme`
- Urgence stockée comme booléen (0=normal, 1=urgent/très urgent)
- **Destination**: L'imagerie peut voir ces demandes dans son dashboard

#### Fichiers concernés:

- **Vue Principale**: [app/views/consultations/dossier_patient.php](app/views/consultations/dossier_patient.php)
  - Modal Bootstrap pour demander un bilan
  - Toggle JS pour basculer Laboratoire ↔ Imagerie
  - Formulaires pré-remplis avec patient_id

- **Contrôleur**: [app/controllers/BilanController.php](app/controllers/BilanController.php)
  - `create()` - Affiche le formulaire multi-étapes (optionnel)
  - `save()` - Analyse le type et redirige vers saveBilanLaboratoire() ou saveBilanImagerie()
  - `saveBilanLaboratoire()` - Crée la demande via Laboratoire.creerDemande()
  - `saveBilanImagerie()` - INSERT dans imagerie_medicale
  - `notifyLaboratoire()` - Log et notifie
  - `notifyImagerie()` - Log et notifie

- **Vue optionnelle**: [app/views/bilans/create.php](app/views/bilans/create.php)
  - Formulaires plus détaillés avec pages séparées
  - Utilisable comme alternative au modal

#### Tables impliquées:

```sql
examens (Laboratoire):
  - id
  - patient_id
  - medecin_id
  - consultation_id (nullable)
  - type_examen (VARCHAR)
  - urgence (BOOLEAN: 0 ou 1)
  - observations (TEXT)
  - statut (EN_ATTENTE, EN_COURS, TERMINE, ANNULE)
  - date_demande
  - etat_prelevement (NON_FAIT, FAIT)

examen_details:
  - id
  - examen_id
  - nom_examen
  - resultat
  - valeur_normale
  - unite

imagerie_medicale (Imagerie):
  - id
  - patient_id
  - type_examen (radiographie, scanner, irm, echographie, mammographie, autre)
  - partie_corps (VARCHAR du champ à examiner)
  - description (TEXT avec indications)
  - medecin_prescripteur
  - statut (programme, en_cours, termine, interprete, valide)
  - urgence (BOOLEAN)
  - date_examen
```

---

## Routes et Points d'Entrée HTTP

### Prescriptions:
```
GET  /dme_hospital/prescription/create?patient_id=XX
     → Affiche le formulaire de création d'ordonnance

POST /dme_hospital/prescription/save
     → Sauvegarde l'ordonnance (requiert patient_id, medicaments JSON)

GET  /dme_hospital/prescription/print?id=XX
     → Affiche/imprime l'ordonnance créée
```

### Bilans:
```
GET  /dme_hospital/bilan/create
     → Affiche le formulaire de création (optionnel, page séparée)

POST /dme_hospital/bilan/save
     → Valide et sauvegarde le bilan
     → Redirige avec success=bilan_created

GET  /dme_hospital/bilan/detail/XX
     → Affiche les détails d'un bilan (non implémenté)
```

---

## Intégration avec les Services

### Pharmacie
```
Détection: Nouvelle prescription créée
Action:
  - notifyPharmacy() enregistre un log
  - TODO: Ajouter en queue de traitement visible par PharmacieController.ordonnances()
Affichage: PharmacieController.ordonnances() → views/pharmacie/ordonnances.php
Traitement: Pharmaciste marque comme servie/livrée
```

### Laboratoire
```
Détection: Nouveau bilan de laboratoire créé via bilan/save
Action:
  - Laboratoire.crierDemande() insère dans examens + examen_details
  - notifyLaboratoire() enregistre un log
  - Vérifié: LaboratoireController.index() récupère les demandes EN_ATTENTE
Affichage: LaboratoireController.index() → views/laboratoire/dashboard.php
Traitement:
  1. Effectuer le prélèvement
  2. Saisir les résultats (saisieResultats)
  3. Valider (validerResultats)
  4. Imprimer (imprimerResultats)
```

### Imagerie
```
Détection: Nouveau bilan d'imagerie créé via bilan/save
Action:
  - INSERT dans imagerie_medicale (statut='programme')
  - notifyImagerie() enregistre un log
  - Vérifié: ImagerieController.index() affiche tous les examens
Affichage: ImagerieController.index() → views/imagerie/index.php
Traitement:
  1. Effectuer l'examen
  2. Upload du fichier DICOM (upload method)
  3. Interpréter (saveInterpretation)
  4. Valider
```

---

## Flux de Retour des Résultats

### Laboratoire
```
LaboratoireController.imprimerResultats()
    ↓
Génère un Document avec les résultats
    ↓
Les résultats doivent être ajoutés manuellement au Dossier du Patient
    ↓
Médecin interprète dans consultation
```

### Imagerie
```
ImagerieController.saveInterpretation()
    ↓
Stocke interpretation + conclusion dans imagerie_medicale
    ↓
Les images/interprétation doivent être visibles au médecin
    ↓
Médecin interprète dans consultation
```

---

## Sécurité et Permissions

### À implémenter:
```php
// Dans les contrôleurs, ajouter les vérifications:
- Vérifier que l'utilisateur est connecté
- Vérifier que seul un médecin peut créer ordonnances/bilans
- Vérifier que le médecin a les permissions appropriées
- Vérifier que le patient existe

Exemple:
if ($_SESSION['user_role'] !== 'medecin') {
    header('HTTP/1.0 403 Forbidden');
    exit;
}
```

---

## Messages de Feedback et Erreurs

### Succès:
```
?success=bilan_created         → "Bilan demandé avec succès"
?success=bilan_imagerie_created → "Demande d'imagerie créée"
?success=ordonnance_created     → "Ordonnance créée et envoyée à la pharmacie"
```

### Erreurs:
```
?error=no_examen             → "Aucun examen sélectionné"
?error=no_imagerie_details   → "Type d'imagerie ou zone non spécifiée"
?error=invalid_data          → "Données invalides"
?error=save_failed           → "Erreur lors de la sauvegarde"
?error=exception             → "Erreur système"
?error=patient_not_found     → "Patient introuvable"
```

---

## Données de Test

### Patient test:
```
ID: 1
Nom: MEBARA Jean
Dossier: P-2023-00001
Contact: 699111111
```

### Médicaments disponibles:
```
1. Paracétamol 500mg (stock: 500 boîtes)
2. Paracétamol Perfusion 1g/100ml (stock: 45 poches)
3. Amoxicilline 1g (stock: 12 boîtes)
4. Spasfon 40mg Injectable (stock: 100 ampoules)
5. Sérum Physiologique 500ml (stock: 200 poches)
6. Tramadol 50mg (stock: 0 boîtes - OUT OF STOCK)
```

---

## Étapes Suivantes et TODO

1. **Notifications avancées**:
   - [ ] Envoyer des emails au laboratoire/imagerie
   - [ ] Créer des notifications système dans le dashboard
   - [ ] Implémenter une queue de messages (Redis/RabbitMQ)

2. **Workflow complet**:
   - [ ] Tableau de bord "À faire" pour chaque service
   - [ ] Marquer comme "En cours" / "Terminé"
   - [ ] Feedback automatique au dossier du patient

3. **Interface utilisateur**:
   - [ ] Afficher les demandes en attente dans les onglets du dossier
   - [ ] Afficher l'historique des ordonnances/bilans
   - [ ] Timeline visuelle des demandes

4. **Intégration**:
   - [ ] Connecter les résultats de laboratoire au dossier
   - [ ] Ajouter commentaires des techniciens/radiologues
   - [ ] Validation par le médecin avant archivage

5. **Rapports**:
   - [ ] Statistiques de demandes par type
   - [ ] Temps moyen de traitement
   - [ ] KPI du laboratoire et imagerie

---

## Fichiers Modifiés/Créés

### Créés:
- `app/controllers/BilanController.php` - Nouveau contrôleur
- `app/views/prescriptions/create.php` - Formulaire ordonnance
- `app/views/bilans/create.php` - Formulaire bilans (optionnel)

### Modifiés:
- `index.php` - Ajout des routes prescription et bilan
- `app/controllers/PrescriptionController.php` - Amélioration des méthodes
- `app/views/consultations/dossier_patient.php` - Ajout des boutons et modal

### Existants (utilisés):
- `app/models/Prescription.php`
- `app/models/Laboratoire.php`
- `app/models/Medicament.php`
- `app/views/pharmacie/ordonnances.php`
- `app/views/laboratoire/dashboard.php`
- `app/views/imagerie/index.php`

---

## Version et Historique

- **v1.0** (2026-02-19): Implémentation initiale
  - Boutons dans dossier patient
  - Modal pour bilans
  - Formulaires de saisie
  - Routage vers services appropriés
