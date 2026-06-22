# Rapport d'Analyse de la Base de Données — dme_hospital
**Date :** 2026-04-01 | **Tables :** 134 | **Moteur :** InnoDB / MySQL 8.4

---

## 1. SYNTHÈSE EXÉCUTIVE

| Problème | Nombre |
|---|---|
| Tables dupliquées / redondantes | 35 tables (26%) |
| Colonnes `*_id` sans FOREIGN KEY | 76 |
| Tables totalement vides (jamais utilisées) | 86 |
| Colonnes redondantes dans une même table | ~15 |
| Collations mixtes | 2 (128 × `0900_ai_ci` + 6 × `unicode_ci`) |
| Tables sans `created_at` | 77 |

---

## 2. DOUBLONS ET TABLES REDONDANTES

### 2.1 Hospitalisations / Admissions (4 tables pour la même chose)
| Table | Problème |
|---|---|
| `admissions` | Doublon de `hospitalisations` — mêmes colonnes (patient_id, lit_id, date_admission, statut) |
| `hospitalisations` | **Table principale à garder** — plus complète (service_id, diagnostic, type_sortie) |
| `decisions_hospitalisation` | Workflow de décision depuis une consultation — à fusionner dans `hospitalisations.statut` |
| `urgences_admissions` | Spécifique urgences — justifiée, mais redondante avec `urgences_patients` |

**→ Supprimer : `admissions`** (jamais utilisée en prod) | **Garder et enrichir : `hospitalisations`**

### 2.2 Prescriptions / Médicaments (6 tables pour la même chose)
| Table | Rôle réel | Verdict |
|---|---|---|
| `prescriptions` | En-tête ordonnance consultation | **Garder (principale)** |
| `lignes_prescription` | Lignes de l'ordonnance | **Garder** |
| `prescription_medicaments` | Doublon exact de `lignes_prescription` | **Supprimer** |
| `ordonnances_pharmacie` | Doublon de `prescriptions` côté pharmacie | **Fusionner dans `prescriptions`** |
| `ordonnance_medicaments` | Doublon de `lignes_prescription` avec champ `disponible` | **Supprimer** (ajouter `disponible` à `lignes_prescription`) |
| `prescriptions_hospitalisation` | Prescription en hospit — structure différente (sans `consultation_id`) | **Garder mais renommer `prescriptions_hospitalisation`** |

### 2.3 Soins infirmiers (6 tables pour la même chose)
```
soins_hospitalisation  ← principale (admission_id, statut, dates)
soins_planification    ← plan global (sans FK propres)
soins_planifies        ← doublon de soins_hospitalisation
soins_administres      ← doublon de soins_hospitalisation
soins_details          ← enfant de soins_planification (OK)
soins_execution        ← doublon de soins_details.execute
```
**→ Garder : `soins_hospitalisation` + `soins_details`**
**→ Supprimer : `soins_planification`, `soins_planifies`, `soins_administres`, `soins_execution`**

### 2.4 Laboratoire (7 tables)
| Table | Verdict |
|---|---|
| `demandes_laboratoire` | **Garder** (demande) |
| `demande_examens` | **Garder** (lignes d'une demande) |
| `examens_laboratoire` | **Garder** (catalogue des examens) |
| `lab_catalogue` | Doublon de `examens_laboratoire` | **Supprimer** |
| `lab_categories` / `lab_parametres` | Référentiels liés à `lab_catalogue` → migrer vers `examens_laboratoire` |
| `examens` | Doublon de `demandes_laboratoire` | **Supprimer** |
| `examen_details` | Doublon de `demande_examens` | **Supprimer** |
| `examens_paracliniques` | Doublon de `demandes_laboratoire` (champ libre) | **Supprimer** |
| `resultats_examens` | **Garder** |
| `laboratoire_resultats` | Doublon de `resultats_examens` | **Supprimer** |
| `patient_resultats_labo` | Doublon de `resultats_examens` | **Supprimer** |

### 2.5 Notifications (4 tables)
```
notifications          ← générique (user_id)       → GARDER
notifications_medecin  ← doublon orienté médecin   → SUPPRIMER (merge dans notifications)
notifications_automatiques ← doublon patient       → SUPPRIMER
patient_notifications  ← doublon patient           → SUPPRIMER
```

### 2.6 RDV / Agenda (3 tables)
```
rendez_vous    ← structure simple           → SUPPRIMER (doublon)
patient_rdv    ← doublon côté patient       → SUPPRIMER
agenda_medical ← la plus complète (type, date_debut/fin, couleur) → GARDER
```

### 2.7 Banque de Sang (6 tables — 2 systèmes parallèles)
```
blood_donors    ← système anglophone
blood_stock     ← système anglophone
blood_movements ← système anglophone
registre_donneurs_sang  ← système francophone (doublon de blood_donors)
registre_donneurs_csh   ← CSH différent des donneurs sang (justifié)
registre_receveurs_csh  ← Receveurs (justifié)
```
**→ Fusionner `blood_donors` + `registre_donneurs_sang`**
**→ Supprimer `blood_donors`** (renommer `registre_donneurs_sang` en y ajoutant `code_donneur`)

### 2.8 Planning (2 tables)
```
planning_infirmier  ← spécifique soins      → GARDER
planning_personnel  ← général (gardes)      → GARDER (rôles différents)
```

### 2.9 Télémédecine (4 tables — justifiées)
```
telemedecine_consultations → GARDER (master)
telemedecine_sessions      → FUSIONNER dans consultations (room_id, date_debut/fin)
telemedecine_documents     → GARDER
telemedecine_surveillance  → GARDER (données IoT)
```

---

## 3. COLONNES REDONDANTES

### Table `patients`
| Colonne | Problème |
|---|---|
| `antecedents_medicaux` + table `antecedents` | Double stockage — choisir l'un |
| `allergies` + `allergie_connue` + table `allergies_patients` | Triple stockage |
| `statut` + `statut_parcours` + `statut_hosp` | 3 champs statut — confus |
| `date_creation` + `created_at` | Doublon exact |
| `service_id` | Sans FK vers `services` |

### Table `consultations`
| Colonne | Problème |
|---|---|
| `temperature` + `tension_arterielle` + `frequence_cardiaque` + `poids` + `taille` | Dupliqués dans `parametres_vitaux` |
| `tension_systolique` + `tension_diastolique` + `tension_arterielle` | Triple stockage de la TA |
| `atcd_medicaux` + `atcd_chirurgicaux` + `atcd_familiaux` + `atcd_allergies` + `antecedents_snapshot` | 5 colonnes d'antécédents (snapshot justifié, les 4 autres non) |
| `type` + `type_consultation` | Doublon |
| `date_creation` + `date_modification` + `updated_at` | 3 colonnes de timestamp |

### Table `lits`
| Colonne | Problème |
|---|---|
| `patient_id` + `occupied_by_patient_id` | Exactement la même chose — supprimer `patient_id` |
| `service_id` | Sans FK (chambre → service suffit via `chambres`) |

### Table `users`
| Colonne | Problème |
|---|---|
| `date_creation` + `created_at` | Doublon |
| `statut` + `actif` | Redondant (ENUM vs TINYINT) |

### Table `parametres_vitaux`
| Colonne | Problème |
|---|---|
| `tension_sys` + `tension_dia` + `pression_arterielle_systolique` + `pression_arterielle_diastolique` | Quadruple TA ! |
| `admission_id` | Sans FK |

---

## 4. FOREIGN KEYS MANQUANTES (critiques)

Les colonnes suivantes n'ont aucune contrainte d'intégrité :

```sql
-- Critiques (risque de données orphelines)
users.service_id                    → REFERENCES services(id)
patients.service_id                 → REFERENCES services(id)
consultations.service_id            → REFERENCES services(id)
demandes_laboratoire.consultation_id → REFERENCES consultations(id)
demandes_laboratoire.patient_id     → REFERENCES patients(id)
demandes_laboratoire.medecin_id     → REFERENCES users(id)
demandes_imagerie.patient_id        → REFERENCES patients(id)
demandes_imagerie.medecin_id        → REFERENCES users(id)
ordonnances_pharmacie.patient_id    → REFERENCES patients(id)
lits.service_id                     → REFERENCES services(id)
admissions.medecin_id               → REFERENCES users(id)
commentaires_bilans.*               → Aucune FK (table nouvelle)
consultations_pediatriques.*        → Aucune FK (table nouvelle)
```

---

## 5. INCOHÉRENCES DE NOMMAGE

| Problème | Exemples |
|---|---|
| Collation mixte | 6 tables en `utf8mb4_unicode_ci` vs 128 en `utf8mb4_0900_ai_ci` |
| Préfixe incohérent | `blood_*` (anglais) vs `registre_*` (français) |
| Suffixe inconsistant | `soins_hospitalisation` vs `soins_planifies` vs `soins_administres` |
| Timestamp incohérent | `date_creation`, `created_at`, `date_ajout` — pas de standard |
| FK nommage | `user_id_planificateur` au lieu de `planificateur_id` |

---

## 6. TABLES INUTILISÉES (86 tables vides — candidates à l'archivage)

Tables créées mais **jamais utilisées** en production :
- Toutes les tables `bloc_*` sauf `bloc_salles`
- `encrypted_data`, `performance_monitoring`, `system_metrics`, `system_alerts`
- `maternite_consultations`, `maternite_grossesses`
- `patient_accounts`, `patient_traitements`, `patient_rappels`
- `formation_*`, `satisfaction_enquetes`
- `reanimation_monitoring`, `reanimation_patients`
- `scores_gravite`, `alertes_predictives`

---

## 7. PLAN DE RÉORGANISATION RECOMMANDÉ

### Tables finales cibles (de 134 → ~85)

**CONSERVER (53)**
```
patients, users, services, lits, chambres
consultations, hospitalisations, agenda_medical
prescriptions, lignes_prescription, prescriptions_hospitalisation
demandes_laboratoire, demande_examens, examens_laboratoire, resultats_examens
demandes_imagerie, imagerie_medicale, dicom_metadata
medicaments, dispensations, interactions_medicamenteuses
soins_hospitalisation, soins_details, planning_infirmier, planning_personnel
observations_evolution, parametres_vitaux, surveillance_intensive_obs
comptes_rendus_hosp, documents_signes, patient_documents
notifications, audit_logs, role_permissions, settings
antecedents, allergies_patients
bloc_demandes, bloc_interventions, bloc_programmation, bloc_anesthesie, bloc_cro, bloc_sspi, bloc_salles, bloc_monitoring
registre_donneurs_sang, registre_donneurs_csh, registre_receveurs_csh, blood_stock, blood_movements
telemedecine_consultations, telemedecine_documents, telemedecine_surveillance
urgences_patients, urgences_triage
commentaires_bilans, consultations_pediatriques
medecin_signatures, cim10, tarifs, config_sequence
```

**SUPPRIMER (34)**
```
admissions                    → remplacé par hospitalisations
ordonnances_pharmacie         → fusionner dans prescriptions
ordonnance_medicaments        → fusionner dans lignes_prescription
prescription_medicaments      → doublon de lignes_prescription
soins_planification           → doublon de soins_hospitalisation
soins_planifies               → doublon de soins_hospitalisation
soins_administres             → doublon de soins_hospitalisation
soins_execution               → doublon de soins_details
lab_catalogue                 → doublon de examens_laboratoire
lab_categories                → à migrer dans examens_laboratoire
lab_parametres                → à migrer dans examens_laboratoire
examens                       → doublon de demandes_laboratoire
examen_details                → doublon de demande_examens
examens_paracliniques         → doublon de demandes_laboratoire
laboratoire_resultats         → doublon de resultats_examens
patient_resultats_labo        → doublon de resultats_examens
rendez_vous                   → doublon de agenda_medical
patient_rdv                   → doublon de agenda_medical
notifications_automatiques    → fusionner dans notifications
notifications_medecin         → fusionner dans notifications
patient_notifications         → fusionner dans notifications
blood_donors                  → fusionner dans registre_donneurs_sang
decisions_hospitalisation     → champ statut dans consultations
patient_traitements           → redondant avec prescriptions
patient_parametres            → doublon de parametres_vitaux
patient_accounts              → non utilisé
patient_rappels               → fusionner dans agenda_medical
telemedecine_sessions         → fusionner dans telemedecine_consultations
encrypted_data                → non utilisé
performance_monitoring        → non utilisé
system_metrics                → non utilisé
urgences_admissions           → doublon de urgences_patients
urgences_stats_rapides        → non utilisé
maternite_consultations       → non utilisé (module absent)
maternite_grossesses          → non utilisé (module absent)
```

**ARCHIVER (optionnel, garder désactivées)**
```
bloc_catalogue_actes, bloc_materiel_sterile, bloc_tracabilite_materiel
formation_*, satisfaction_enquetes, scores_gravite, alertes_predictives
reanimation_*, famille_*, kiosque_*, automated_reports
backup_logs, system_logs, system_alerts, login_attempts, user_sessions
```
