-- ============================================================================
-- MIGRATION : Robustesse de la base de données DME-HSJM
-- Ajout de colonnes manquantes, index, contraintes et support fichiers labo
-- À exécuter UNE SEULE FOIS : mysql -u root -p dme_hospital < migration_robustesse.sql
-- ============================================================================

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';

-- ── 1. TABLE patients : colonnes de sécurité ──────────────────────────────
ALTER TABLE patients
    MODIFY COLUMN nom             VARCHAR(100) NOT NULL,
    MODIFY COLUMN prenom          VARCHAR(100) NOT NULL,
    MODIFY COLUMN date_naissance  DATE         NOT NULL,
    MODIFY COLUMN sexe            ENUM('M','F') NOT NULL;

-- Ajouter colonnes manquantes si absentes
ALTER TABLE patients
    ADD COLUMN IF NOT EXISTS dossier_numero VARCHAR(20) UNIQUE
        GENERATED ALWAYS AS (CONCAT('P-', LPAD(id, 7, '0'))) STORED,
    ADD COLUMN IF NOT EXISTS antecedents_medicaux      TEXT     NULL,
    ADD COLUMN IF NOT EXISTS antecedents_chirurgicaux  TEXT     NULL,
    ADD COLUMN IF NOT EXISTS antecedents_familiaux     TEXT     NULL,
    ADD COLUMN IF NOT EXISTS allergies                 TEXT     NULL,
    ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

-- Index utiles sur patients
CREATE INDEX IF NOT EXISTS idx_patients_nom_prenom   ON patients(nom, prenom);
CREATE INDEX IF NOT EXISTS idx_patients_dossier      ON patients(dossier_numero);
CREATE INDEX IF NOT EXISTS idx_patients_statut       ON patients(statut);

-- ── 2. TABLE consultations : colonnes manquantes ──────────────────────────
ALTER TABLE consultations
    ADD COLUMN IF NOT EXISTS antecedents_snapshot      TEXT     NULL COMMENT 'Snapshot antécédents au moment consultation',
    ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

CREATE INDEX IF NOT EXISTS idx_consultations_patient   ON consultations(patient_id);
CREATE INDEX IF NOT EXISTS idx_consultations_date      ON consultations(date_consultation);
CREATE INDEX IF NOT EXISTS idx_consultations_medecin   ON consultations(medecin_id);

-- ── 3. TABLE demandes_laboratoire : colonnes et index ─────────────────────
ALTER TABLE demandes_laboratoire
    ADD COLUMN IF NOT EXISTS notes_technicien  TEXT     NULL,
    ADD COLUMN IF NOT EXISTS valide_par        INT      NULL,
    ADD COLUMN IF NOT EXISTS date_validation   DATETIME NULL,
    ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

CREATE INDEX IF NOT EXISTS idx_dl_patient   ON demandes_laboratoire(patient_id);
CREATE INDEX IF NOT EXISTS idx_dl_statut    ON demandes_laboratoire(statut);
CREATE INDEX IF NOT EXISTS idx_dl_date      ON demandes_laboratoire(date_creation);
CREATE INDEX IF NOT EXISTS idx_dl_medecin   ON demandes_laboratoire(medecin_id);

-- ── 4. TABLE demande_examens : support fichiers complémentaires ────────────
ALTER TABLE demande_examens
    ADD COLUMN IF NOT EXISTS fichier_resultat  VARCHAR(255) NULL COMMENT 'Chemin fichier PDF/image complémentaire',
    ADD COLUMN IF NOT EXISTS fichier_nom       VARCHAR(255) NULL COMMENT 'Nom original du fichier uploadé',
    ADD COLUMN IF NOT EXISTS fichier_type      VARCHAR(50)  NULL COMMENT 'MIME type du fichier',
    ADD COLUMN IF NOT EXISTS fichier_taille    INT          NULL COMMENT 'Taille en octets',
    ADD COLUMN IF NOT EXISTS date_upload       DATETIME     NULL,
    ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

CREATE INDEX IF NOT EXISTS idx_de_demande   ON demande_examens(demande_id);
CREATE INDEX IF NOT EXISTS idx_de_statut    ON demande_examens(statut);
CREATE INDEX IF NOT EXISTS idx_de_urgent    ON demande_examens(urgent);

-- ── 5. TABLE patient_resultats_labo : robustesse ──────────────────────────
ALTER TABLE patient_resultats_labo
    ADD COLUMN IF NOT EXISTS fichier_complementaire VARCHAR(255) NULL,
    ADD COLUMN IF NOT EXISTS valide_biologiste TINYINT(1) DEFAULT 0,
    ADD COLUMN IF NOT EXISTS commentaire_biologiste TEXT NULL,
    ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

CREATE INDEX IF NOT EXISTS idx_prl_patient  ON patient_resultats_labo(patient_id);
CREATE INDEX IF NOT EXISTS idx_prl_date     ON patient_resultats_labo(date_resultat);
CREATE INDEX IF NOT EXISTS idx_prl_anormal  ON patient_resultats_labo(anormal);

-- ── 6. TABLE imagerie_medicale / demandes_imagerie : robustesse ────────────
-- Gestion du cas où l'une ou l'autre existe
ALTER TABLE imagerie_medicale
    ADD COLUMN IF NOT EXISTS avec_contraste     TINYINT(1)   DEFAULT 0,
    ADD COLUMN IF NOT EXISTS cote               VARCHAR(20)  NULL,
    ADD COLUMN IF NOT EXISTS indication         TEXT         NULL,
    ADD COLUMN IF NOT EXISTS date_resultats     DATETIME     NULL,
    ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

CREATE INDEX IF NOT EXISTS idx_im_patient   ON imagerie_medicale(patient_id);
CREATE INDEX IF NOT EXISTS idx_im_statut    ON imagerie_medicale(statut);
CREATE INDEX IF NOT EXISTS idx_im_date      ON imagerie_medicale(created_at);

-- ── 7. TABLE antecedents : index ──────────────────────────────────────────
CREATE INDEX IF NOT EXISTS idx_ant_patient  ON antecedents(patient_id);
CREATE INDEX IF NOT EXISTS idx_ant_type     ON antecedents(type);

-- ── 8. TABLE hospitalisations : colonnes manquantes ───────────────────────
ALTER TABLE hospitalisations
    ADD COLUMN IF NOT EXISTS date_sortie_prevue DATE NULL,
    ADD COLUMN IF NOT EXISTS motif_hospitalisation TEXT NULL,
    ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

CREATE INDEX IF NOT EXISTS idx_hosp_patient ON hospitalisations(patient_id);
CREATE INDEX IF NOT EXISTS idx_hosp_statut  ON hospitalisations(statut);
CREATE INDEX IF NOT EXISTS idx_hosp_date    ON hospitalisations(date_admission);

-- ── 9. Dossier uploads labo (info pour l'admin) ───────────────────────────
-- Note : créer le dossier assets/uploads/labo_resultats/ avec chmod 755

-- ── 10. Vues utiles ───────────────────────────────────────────────────────
CREATE OR REPLACE VIEW v_demandes_labo_actives AS
SELECT
    dl.id,
    dl.patient_id,
    dl.medecin_id,
    dl.statut,
    dl.date_creation,
    p.nom, p.prenom, p.dossier_numero,
    u.nom AS medecin_nom, u.prenom AS medecin_prenom,
    COUNT(de.id)                    AS nb_examens,
    SUM(de.urgent)                  AS nb_urgents,
    MIN(e.delai_rendu_heures)       AS delai_min,
    t.nom AS technicien_nom
FROM demandes_laboratoire dl
JOIN patients             p  ON dl.patient_id = p.id
JOIN users                u  ON dl.medecin_id = u.id
LEFT JOIN demande_examens de ON de.demande_id = dl.id
LEFT JOIN examens_catalogue e ON de.examen_id = e.id
LEFT JOIN users           t  ON dl.technicien_id = t.id
WHERE dl.statut NOT IN ('VALIDE', 'ARCHIVE')
GROUP BY dl.id, dl.patient_id, dl.medecin_id, dl.statut, dl.date_creation,
         p.nom, p.prenom, p.dossier_numero,
         u.nom, u.prenom, t.nom;

SET FOREIGN_KEY_CHECKS = 1;

-- ── Confirmation ──────────────────────────────────────────────────────────
SELECT 'Migration robustesse OK ✅' AS resultat,
       NOW()                        AS executee_le;
