<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<style>
    /* --- CONFIGURATION SUPPORT PAPIER A4 --- */
    .paper-sheet {
        background-color: white;
        width: 21cm;
        min-height: 29.7cm;
        padding: 2cm;
        margin: 20px auto;
        box-shadow: 0 4px 15px rgba(0,0,0,0.15);
        font-family: Arial, sans-serif;
        color: #000;
        line-height: 1.6;
        display: flex;
        flex-direction: column;
        position: relative;
    }

    /* --- STYLE DES CHAMPS --- */
    .form-dotted {
        border: none;
        border-bottom: 1px solid #000;
        background: transparent;
        padding: 0 5px;
        outline: none;
        font-weight: bold;
        color: #0d6efd; /* Bleu pour la saisie écran */
        font-family: inherit;
        width: 100%;
        min-width: 0;
    }

    .doc-title {
        text-align: center;
        margin-bottom: 40px;
    }
    .doc-title h2 { font-weight: 900; font-size: 1.8rem; text-transform: uppercase; }

    .hospital-info {
        text-align: left;
        margin-bottom: 30px;
        font-weight: bold;
        line-height: 1.2;
    }

    .section-header {
        text-align: center;
        text-decoration: underline;
        font-weight: bold;
        margin: 30px 0 20px;
        text-transform: uppercase;
    }

    /* --- GRILLES D'ALIGNEMENT --- */
    .field-row {
        display: flex;
        align-items: flex-end;
        margin-bottom: 15px;
        width: 100%;
    }
    .label-main { font-weight: bold; margin-right: 10px; white-space: nowrap; }

    .grid-2 {
        display: grid;
        grid-template-columns: 1.5fr 1fr;
        gap: 30px;
        width: 100%;
    }

    .text-block {
        width: 100%;
        min-height: 350px;
        border: 1px solid #dee2e6;
        padding: 15px;
        background: #fdfdfd;
        font-size: 1rem;
        margin-top: 5px;
    }

    /* --- SIGNATURES --- */
    .signature-container {
        margin-top: 50px;
        display: flex;
        flex-direction: column;
        gap: 40px;
    }

    .sig-block {
        width: fit-content;
    }
    .sig-label { font-weight: bold; text-transform: uppercase; display: block; margin-bottom: 10px; }
    .sig-box { width: 300px; height: 60px; background: #eff6ff; border-radius: 4px; border: 1px dashed #abc; }

    .date-time-block {
        align-self: flex-end;
        text-align: right;
    }

    @media print {
        .no-print, .action-bar, header, aside { display: none !important; }
        .paper-sheet { margin: 0; box-shadow: none; width: 100%; padding: 1cm; }
        .form-dotted { color: black; border-bottom: 1px solid #000; }
        .text-block { border: 1px solid #000; background: none; }
        .sig-box { background: none; border: none; }
        body { background: white; }
    }
</style>

<div class="container-fluid bg-light pb-5">
    <!-- BARRE D'ACTIONS -->
    <div class="action-bar py-3 px-4 bg-white border-bottom shadow-sm no-print sticky-top mb-3">
        <div class="d-flex justify-content-between align-items-center">
            <a href="<?= BASE_URL ?>patients/dossier/<?= $patient['id'] ?>" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left"></i> Retour
            </a>
            <div class="d-flex gap-2">
                <button class="btn btn-primary btn-sm" onclick="window.print()"><i class="bi bi-printer"></i> Imprimer</button>
                <button type="submit" form="formReference" class="btn btn-success btn-sm px-4"><i class="bi bi-save"></i> Enregistrer la fiche</button>
            </div>
        </div>
    </div>

    <div class="paper-sheet">
        <form id="formReference" action="<?= BASE_URL ?>formulaire/sauvegarder/fiche-reference" method="POST" style="flex-grow: 1; display: flex; flex-direction: column;">
            <input type="hidden" name="patient_id" value="<?= $patient['id'] ?>">

            <div class="doc-title">
                <h2>FICHE DE REFERENCE</h2>
            </div>

            <div class="hospital-info">
                HÔPITAL SAINT JEAN DE MALTE DE NJOMBE<br>
                TEL : 697092992
            </div>

            <div class="section-header">IDENTIFICATION DU MALADE</div>

            <!-- IDENTIFICATION -->
            <div class="field-row">
                <span class="label-main">NOM ET PRENOM :</span>
                <input type="text" class="form-dotted" value="<?= htmlspecialchars($patient['nom'].' '.$patient['prenom']) ?>" readonly>
            </div>

            <div class="grid-2">
                <div class="field-row">
                    <span class="label-main">TEL :</span>
                    <input type="text" name="tel_patient" class="form-dotted" value="<?= htmlspecialchars($patient['telephone']) ?>">
                </div>
                <div class="field-row">
                    <span class="label-main">AGE :</span>
                    <input type="text" class="form-dotted" value="<?= $age ?> ans" readonly>
                </div>
            </div>

            <div class="field-row">
                <span class="label-main">RESIDENCE :</span>
                <input type="text" name="residence" class="form-dotted">
            </div>

            <div class="grid-2">
                <div class="field-row">
                    <span class="label-main">PERSONNE A PREVENIR :</span>
                    <input type="text" name="contact_urgence" class="form-dotted">
                </div>
                <div class="field-row">
                    <span class="label-main">TEL :</span>
                    <input type="text" name="tel_urgence" class="form-dotted">
                </div>
            </div>

            <!-- MOTIF -->
            <div class="mt-4">
                <span class="label-main">MOTIF DE REFERENCE :</span>
                <textarea name="motif" class="form-control text-block" placeholder="Description clinique, examens effectués, soins administrés et raison du transfert..."></textarea>
            </div>

            <div class="field-row mt-4">
                <span class="label-main">LIEU DE LA REFERENCE :</span>
                <input type="text" name="lieu_reference" class="form-dotted" placeholder="Nom de la formation sanitaire d'accueil">
            </div>

            <!-- SIGNATURES -->
            <div class="signature-container">
                <div class="sig-block">
                    <span class="sig-label">Nom et Signature du référent</span>
                    <div class="sig-box d-flex align-items-center justify-content-center text-muted small">
                        Dr. <?= $_SESSION['user_nom'] ?? 'Nom du Médecin' ?>
                    </div>
                </div>

                <div class="date-time-block">
                    <span class="sig-label">Date et Heure</span>
                    <div class="d-flex gap-2">
                        <input type="date" name="date_ref" class="form-dotted" value="<?= date('Y-m-d') ?>" style="width: 150px;">
                        <input type="time" name="heure_ref" class="form-dotted" value="<?= date('H:i') ?>" style="width: 100px;">
                    </div>
                </div>
            </div>

        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?> 