<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<style>
    /* --- CONFIGURATION SUPPORT PAPIER A4 --- */
    .paper-sheet {
        background-color: white;
        width: 21cm;
        min-height: 29.7cm;
        padding: 1.5cm 2cm;
        margin: 20px auto;
        box-shadow: 0 4px 15px rgba(0,0,0,0.15);
        font-family: Arial, sans-serif;
        color: #000;
        line-height: 1.4;
        display: flex;
        flex-direction: column;
        position: relative;
    }

    /* --- STYLE DES CHAMPS --- */
    .form-dotted {
        border: none;
        border-bottom: 1px dotted #666;
        background: transparent;
        padding: 0 5px;
        outline: none;
        font-weight: bold;
        color: #0d6efd;
        font-family: inherit;
        flex-grow: 1;
    }

    .header-logo { height: 70px; margin-bottom: 10px; }

    .doc-title {
        text-align: center;
        margin: 30px 0;
        font-weight: 900;
        font-size: 1.6rem;
        text-decoration: none;
        text-transform: uppercase;
        letter-spacing: 1px;
    }

    /* --- GRILLES ALIGNÉES --- */
    .field-row { display: flex; align-items: flex-end; margin-bottom: 12px; }
    .label-main { font-weight: bold; margin-right: 10px; white-space: nowrap; min-width: 60px; }

    .grid-2 {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 30px;
        margin-bottom: 12px;
    }

    /* Section Résultats avec retrait */
    .result-section { margin-top: 20px; }
    .sub-field { margin-left: 40px; margin-bottom: 8px; display: flex; align-items: flex-end; }
    .label-sub { font-weight: normal; margin-right: 10px; white-space: nowrap; min-width: 110px; }

    .text-block {
        width: 100%;
        min-height: 200px;
        border: 1px solid #dee2e6;
        padding: 15px;
        background: #fdfdfd;
        font-size: 1rem;
        margin-top: 10px;
    }

    .signature-area {
        margin-top: auto;
        display: flex;
        justify-content: space-between;
        align-items: flex-end;
        padding: 40px 0 20px;
    }

    @media print {
        .no-print, .action-bar, header, aside { display: none !important; }
        .paper-sheet { margin: 0; box-shadow: none; width: 100%; padding: 1cm; }
        .form-dotted { color: black; border-bottom: 1px solid #000; }
        .text-block { border: 1px solid #000; }
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
                <button type="submit" form="formEchoPelv" class="btn btn-success btn-sm px-4"><i class="bi bi-save"></i> Enregistrer</button>
            </div>
        </div>
    </div>

    <div class="paper-sheet">
        <form id="formEchoPelv" action="<?= BASE_URL ?>formulaire/sauvegarder/echo-pelvienne" method="POST" style="flex-grow: 1; display: flex; flex-direction: column;">
            <input type="hidden" name="patient_id" value="<?= $patient['id'] ?>">

            <!-- EN-TÊTE -->
            <div class="d-flex align-items-center mb-4">
                <img src="<?= BASE_URL ?>public/images/logo_ordre_malte.png" class="header-logo" alt="Logo">
                <div class="ms-3">
                    <div class="fw-bold" style="font-size: 1.2rem;">ORDRE DE MALTE</div>
                    <div class="small fw-bold">HÔPITAL SAINT-JEAN DE MALTE</div>
                </div>
            </div>

            <div class="doc-title">ECHOGRAPHIE PELVIENNE</div>

            <!-- INFO PATIENT -->
            <div class="grid-2">
                <div class="field-row">
                    <span class="label-main">Nom :</span>
                    <input type="text" class="form-dotted" value="<?= htmlspecialchars($patient['nom']) ?>" readonly>
                </div>
                <div class="field-row">
                    <span class="label-main">Prénom :</span>
                    <input type="text" class="form-dotted" value="<?= htmlspecialchars($patient['prenom']) ?>" readonly>
                </div>
            </div>

            <div class="field-row">
                <span class="label-main">Gesteté - Parité :</span>
                <input type="text" name="gestite" class="form-dotted">
            </div>

            <div class="grid-2">
                <div class="field-row">
                    <span class="label-main">Age :</span>
                    <input type="text" class="form-dotted" value="<?= $age ?> ans" readonly>
                </div>
                <div class="field-row">
                    <span class="label-main">DDR :</span>
                    <input type="date" name="ddr" class="form-dotted">
                </div>
            </div>

            <div class="field-row">
                <span class="label-main">Indication :</span>
                <input type="text" name="indication" class="form-dotted">
            </div>

            <!-- RÉSULTATS -->
            <div class="result-section">
                <p class="fw-bold mb-3">RESULTAT :</p>

                <div class="field-row mb-3">
                    <span class="label-main">Vessie :</span>
                    <input type="text" name="vessie" class="form-dotted">
                </div>

                <p class="fw-bold mb-2">Utérus :</p>
                <div class="sub-field">
                    <span class="label-sub">Position :</span>
                    <input type="text" name="ut_pos" class="form-dotted">
                </div>
                <div class="sub-field">
                    <span class="label-sub">Taille :</span>
                    <input type="text" name="ut_taille" class="form-dotted">
                </div>
                <div class="sub-field">
                    <span class="label-sub">Contours :</span>
                    <input type="text" name="ut_contours" class="form-dotted">
                </div>
                <div class="sub-field">
                    <span class="label-sub">Echostructure :</span>
                    <input type="text" name="ut_echo" class="form-dotted">
                </div>
                <div class="sub-field">
                    <span class="label-sub">Endomètre :</span>
                    <input type="text" name="ut_endo" class="form-dotted">
                </div>

                <div class="field-row mt-3">
                    <span class="label-main" style="min-width: 110px;">Ovaire droit :</span>
                    <span class="label-sub" style="min-width: 50px;">Taille :</span>
                    <input type="text" name="ov_d_taille" class="form-dotted">
                </div>
                <div class="sub-field">
                    <span class="label-sub">Structure :</span>
                    <input type="text" name="ov_d_struct" class="form-dotted">
                </div>

                <div class="field-row mt-3">
                    <span class="label-main" style="min-width: 110px;">Ovaire gauche :</span>
                    <span class="label-sub" style="min-width: 50px;">Taille :</span>
                    <input type="text" name="ov_g_taille" class="form-dotted">
                </div>
                <div class="sub-field">
                    <span class="label-sub">Structure :</span>
                    <input type="text" name="ov_g_struct" class="form-dotted">
                </div>

                <div class="field-row mt-3">
                    <span class="label-main">Douglas :</span>
                    <input type="text" name="douglas" class="form-dotted">
                </div>

                <div class="mt-4">
                    <p class="fw-bold mb-1">Conclusion :</p>
                    <textarea name="conclusion" class="form-control text-block"></textarea>
                </div>
            </div>

            <!-- SIGNATURE -->
            <div class="signature-area">
                <div>
                    Date : <input type="text" class="form-dotted" style="width: 150px;" value="<?= date('d/m/Y') ?>">
                </div>
                <div style="width: 250px; text-align: center;">
                    <p class="fw-bold mb-0">Signature</p>
                    <div style="height: 60px;"></div>
                    <p class="small">Dr. <?= $_SESSION['user_nom'] ?? '' ?></p>
                </div>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>