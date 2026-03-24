<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<style>
    /* Format Papier A4 */
    .paper-sheet {
        background-color: white;
        width: 21cm;
        min-height: 29.7cm;
        padding: 1.5cm 2cm;
        margin: 20px auto;
        box-shadow: 0 4px 15px rgba(0,0,0,0.15);
        font-family: "Times New Roman", Times, serif;
        color: #000;
        line-height: 1.5;
        display: flex;
        flex-direction: column;
        position: relative;
    }

    /* Style des champs de saisie type "Ligne papier" */
    .field-group {
        display: flex;
        align-items: flex-end; /* Aligne le texte sur la ligne pointillée */
        margin-bottom: 10px;
    }

    .field-label {
        white-space: nowrap;
        font-weight: normal;
        margin-right: 5px;
    }

    .form-dotted {
        border: none;
        border-bottom: 1px dotted #000;
        background: transparent;
        padding: 0 5px;
        outline: none;
        font-weight: bold;
        color: #0d6efd; /* Bleu pour la saisie écran */
        flex-grow: 1;
        font-family: "Times New Roman", Times, serif;
    }

    /* Grille pour aligner Nom/Prénom et Sexe/Âge */
    .patient-grid {
        display: grid;
        grid-template-columns: 1fr 1fr; /* 2 colonnes égales */
        column-gap: 40px;
        margin-left: 20px;
    }

    /* En-tête */
    .hospital-header {
        text-align: left;
        line-height: 1.2;
        margin-bottom: 30px;
    }
    .hospital-header h4 { font-weight: bold; margin-bottom: 2px; text-transform: uppercase; }

    .doc-title {
        text-align: center;
        margin: 30px 0;
    }
    .doc-title h2 { font-weight: 900; font-size: 1.5rem; text-transform: uppercase; letter-spacing: 1px; }

    .patient-section { margin-top: 20px; }
    .patient-section h5 { font-weight: bold; text-decoration: underline; margin-bottom: 15px; }

    .text-area-box {
        width: 100%;
        min-height: 120px;
        border: 1px solid #dee2e6;
        padding: 10px;
        background: #fdfdfd;
        font-family: inherit;
        font-size: 1.1rem;
        margin-top: 5px;
    }

    /* Signature alignée à droite sans déborder */
    .signature-area {
        margin-top: 50px;
        margin-left: auto;
        text-align: center;
        width: 300px;
        padding-bottom: 40px;
    }

    /* Pied de page */
    .footer-note {
        margin-top: auto;
        font-size: 0.75rem;
        line-height: 1.3;
        text-align: center;
        font-style: italic;
        border-top: 1px solid #000;
        padding-top: 10px;
    }

    @media print {
        .no-print, .action-bar, header, aside { display: none !important; }
        .paper-sheet { margin: 0; box-shadow: none; width: 100%; padding: 1cm; }
        .form-dotted { color: black; border-bottom: 1px solid #000; }
        .text-area-box { border: 1px solid #000; }
        body { background: white; }
    }
</style>

<div class="container-fluid bg-light pb-5">

    <!-- BARRE D'ACTIONS -->
    <div class="action-bar py-3 px-4 bg-white border-bottom shadow-sm no-print sticky-top mb-3">
        <div class="d-flex justify-content-between align-items-center">
            <a href="<?= BASE_URL ?>patients/dossier/<?= $patient['id'] ?>" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left"></i> Retour au dossier
            </a>
            <div class="d-flex gap-2">
                <button class="btn btn-primary btn-sm" onclick="window.print()"><i class="bi bi-printer"></i> Imprimer</button>
                <button type="submit" form="formHisto" class="btn btn-success btn-sm px-4"><i class="bi bi-save"></i> Enregistrer</button>
            </div>
        </div>
    </div>

    <div class="paper-sheet">
        <form id="formHisto" action="<?= BASE_URL ?>formulaire/sauvegarder/histo-pathologie" method="POST" style="flex-grow: 1; display: flex; flex-direction: column;">
            <input type="hidden" name="patient_id" value="<?= $patient['id'] ?>">

            <!-- EN-TÊTE -->
            <div class="hospital-header">
                <h4>HÔPITAL SAINT-JEAN DE MALTE</h4>
                <p class="mb-1">BP.: 56 NJOMBE - CAMEROUN</p>
                <p>Tél.: (237) 697 09 29 92 / 233 21 10 22</p>
            </div>

            <div class="doc-title">
                <h2>DEMANDE D'EXAMEN HISTOPATHOLOGIE</h2>
            </div>

            <!-- SECTION DOCTEUR -->
            <div class="field-group">
                <span class="field-label">Effectué par le Docteur :</span>
                <input type="text" name="medecin_nom" class="form-dotted" value="Dr. <?= $_SESSION['user_nom'] ?? 'Simeni' ?>">
            </div>
            <div class="field-group">
                <span class="field-label">Formation sanitaire :</span>
                <input type="text" name="etablissement" class="form-dotted" value="Hôpital Saint-Jean de Malte">
            </div>
            <div class="row g-0">
                <div class="col-8">
                    <div class="field-group">
                        <span class="field-label">Adresse :</span>
                        <input type="text" name="adresse_hopital" class="form-dotted" value="Njombé">
                    </div>
                </div>
                <div class="col-4">
                    <div class="field-group" style="padding-left: 15px;">
                        <span class="field-label">N° Fax :</span>
                        <input type="text" name="fax" class="form-dotted">
                    </div>
                </div>
            </div>

            <!-- SECTION MALADE (GRILLE ALIGNÉE) -->
            <div class="patient-section">
                <h5>MALADE :</h5>

                <div class="patient-grid">
                    <div class="field-group">
                        <span class="field-label">- Nom :</span>
                        <input type="text" class="form-dotted" value="<?= htmlspecialchars($patient['nom']) ?>" readonly>
                    </div>
                    <div class="field-group">
                        <span class="field-label">Prénom :</span>
                        <input type="text" class="form-dotted" value="<?= htmlspecialchars($patient['prenom']) ?>" readonly>
                    </div>
                    <div class="field-group">
                        <span class="field-label">- Sexe :</span>
                        <input type="text" class="form-dotted" value="<?= $patient['sexe'] == 'M' ? 'Masculin' : 'Féminin' ?>" readonly>
                    </div>
                    <div class="field-group">
                        <span class="field-label">Âge :</span>
                        <input type="text" class="form-dotted" value="<?= $age ?> ans" readonly>
                    </div>
                </div>

                <div style="margin-left: 20px; margin-top: 10px;">
                    <div class="field-group">
                        <span class="field-label">- Adresse :</span>
                        <input type="text" name="patient_adresse" class="form-dotted" placeholder="Lieu de résidence...">
                    </div>
                    <div class="field-group">
                        <span class="field-label">- Organe prélevé :</span>
                        <input type="text" name="organe" class="form-dotted" placeholder="Préciser l'organe ou le tissu...">
                    </div>
                    <div class="field-group">
                        <span class="field-label">- Date du prélèvement :</span>
                        <input type="text" name="date_prelevement" class="form-dotted" style="max-width: 250px;" value="<?= date('d/m/Y') ?>">
                    </div>
                </div>
            </div>

            <!-- RENSEIGNEMENTS MÉDICAUX -->
            <div class="mt-4">
                <h6 class="fw-bold text-uppercase">Renseignements cliniques et paracliniques :</h6>
                <textarea name="infos_cliniques" class="form-control text-area-box" placeholder="Symptômes, antécédents, résultats d'imagerie..."></textarea>
            </div>

            <div class="mt-4">
                <h6 class="fw-bold text-uppercase">Description des lésions :</h6>
                <small class="text-muted">(schéma éventuel)</small>
                <textarea name="description_lesions" class="form-control text-area-box" placeholder="Taille, aspect, consistance..."></textarea>
            </div>

            <!-- ZONE SIGNATURE -->
            <div class="signature-area">
                <p class="fw-bold mb-5">Le Médecin prescripteur</p>
                <p class="fw-bold text-decoration-underline">Dr. <?= $_SESSION['user_nom'] ?? 'Simeni' ?></p>
            </div>

            <!-- MENTIONS LÉGALES BAS DE PAGE -->
            <div class="footer-note">
                L’Hôpital Saint-Jean de Malte est une œuvre humanitaire fonctionnant tous l’égide des Œuvres Hospitalière Françaises de l'Ordre de Malte, en partenariat avec le Ministère de la Santé Publique et avec les Plantations SPNP/SBM/PHP.
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>