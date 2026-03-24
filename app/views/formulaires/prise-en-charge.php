<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<style>
    .paper-sheet {
        background-color: white;
        width: 21cm;
        min-height: 29.7cm; /* Format A4 complet */
        padding: 1.5cm 2cm;
        margin: 20px auto;
        box-shadow: 0 4px 15px rgba(0,0,0,0.15);
        font-family: Arial, sans-serif;
        color: #000;
        line-height: 1.6;
    }

    /* Style des champs soulignés */
    .form-dotted {
        border: none;
        border-bottom: 1px solid #000;
        background: transparent;
        padding: 0 5px;
        outline: none;
        font-weight: bold;
        color: #0d6efd;
        width: 100%; /* Prend toute la place dispo dans sa colonne */
    }

    input[type="date"].form-dotted {
        cursor: pointer;
    }

    /* En-tête */
    .header-table { width: 100%; margin-bottom: 30px; }
    .logo-img { height: 75px; }
    .hosp-info { text-align: right; font-size: 0.85rem; line-height: 1.3; font-weight: bold; }

    .doc-title {
        text-align: center;
        margin: 20px 0 40px;
    }
    .doc-title h2 { font-weight: 900; font-size: 1.6rem; text-transform: uppercase; border-bottom: none; }

    /* Alignement des lignes */
    .field-label { white-space: nowrap; font-weight: normal; margin-right: 8px; }
    .form-group-row { display: flex; align-items: baseline; margin-bottom: 18px; }

    .diagnosis-box {
        margin-top: 10px;
        width: 100%;
        min-height: 120px;
        border: 1px solid #eee;
        padding: 10px;
        background: #fdfdfd;
        font-family: inherit;
    }

    @media print {
        .no-print, .action-bar { display: none !important; }
        .paper-sheet { margin: 0; box-shadow: none; width: 100%; padding: 1cm; }
        .form-dotted { color: black; border-bottom: 1px solid #000; }
        .diagnosis-box { border: 1px solid #000; }
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
                <button class="btn btn-primary" onclick="window.print()"><i class="bi bi-printer"></i> Imprimer</button>
                <button type="submit" form="formPEC" class="btn btn-success px-4"><i class="bi bi-save"></i> Enregistrer</button>
            </div>
        </div>
    </div>

    <div class="paper-sheet">
        <form id="formPEC" action="<?= BASE_URL ?>formulaire/sauvegarder/prise-en-charge" method="POST">
            <input type="hidden" name="patient_id" value="<?= $patient['id'] ?>">

            <!-- EN-TÊTE -->
            <table class="header-table">
                <tr>
                    <td>
                        <div class="d-flex align-items-center">
                            <img src="<?= BASE_URL ?>public/images/logo_ordre_malte.png" class="logo-img" alt="Logo">
                            <div class="ms-3">
                                <div class="fw-bold" style="font-size: 1.2rem;">ORDRE DE MALTE</div>
                                <div class="small fw-bold">HÔPITAL SAINT-JEAN DE MALTE</div>
                            </div>
                        </div>
                    </td>
                    <td class="hosp-info">
                        HÔPITAL SAINT-JEAN DE MALTE<br>
                        BP.: 56 NJOMBE - CAMEROUN<br>
                        Tél.: (237) 697 09 29 92 / 233 21 10 22
                    </td>
                </tr>
            </table>

            <div class="doc-title">
                <h2>DEMANDE DE BON DE PRISE EN CHARGE</h2>
            </div>

            <!-- SECTION 1 : L'ASSURÉ -->
            <div class="form-group-row">
                <span class="field-label">Nom de l’assuré :</span>
                <input type="text" name="nom_assure" class="form-dotted">
            </div>

            <!-- LIGNE ENTREPRISE / SECTEUR / MATRICULE (Fixée) -->
            <div class="row g-3 mb-3">
                <div class="col-5 d-flex align-items-baseline">
                    <span class="field-label">Entreprise :</span>
                    <input type="text" name="entreprise" class="form-dotted">
                </div>
                <div class="col-4 d-flex align-items-baseline">
                    <span class="field-label">Secteur :</span>
                    <input type="text" name="secteur" class="form-dotted">
                </div>
                <div class="col-3 d-flex align-items-baseline">
                    <span class="field-label">Matricule :</span>
                    <input type="text" name="matricule" class="form-dotted">
                </div>
            </div>

            <!-- SECTION 2 : LE MALADE -->
            <div class="form-group-row">
                <span class="field-label">Nom du malade :</span>
                <input type="text" class="form-dotted" value="<?= htmlspecialchars($patient['nom'].' '.$patient['prenom']) ?>" readonly>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-6 d-flex align-items-baseline">
                    <span class="field-label">Date de naissance :</span>
                    <input type="text" class="form-dotted" value="<?= date('d/m/Y', strtotime($patient['date_naissance'])) ?>" readonly>
                </div>
                <div class="col-6 d-flex align-items-center justify-content-end">
                    <span class="field-label">Lien de filiation :</span>
                    <div class="form-check form-check-inline mb-0 ms-2">
                        <input class="form-check-input border-dark" type="checkbox" name="fil_salarie" id="salarie">
                        <label class="form-check-label small fw-bold" for="salarie">Salarié</label>
                    </div>
                    <div class="form-check form-check-inline mb-0">
                        <input class="form-check-input border-dark" type="checkbox" name="fil_conjoint" id="conjoint">
                        <label class="form-check-label small fw-bold" for="conjoint">Conjoint</label>
                    </div>
                </div>
            </div>

            <!-- SECTION 3 : PRESTATIONS ET DATES -->
            <div class="row g-4 mt-2">
                <div class="col-7">
                    <div class="fw-bold text-uppercase" style="font-size: 0.9rem;">Consultation externe et soins externes</div>
                </div>
                <div class="col-5 d-flex align-items-baseline">
                    <span class="field-label">Date :</span>
                    <input type="date" name="date_soins_externe" class="form-dotted">
                </div>

                <div class="col-7">
                    <div class="fw-bold text-uppercase" style="font-size: 0.9rem;">Hospitalisation</div>
                </div>
                <div class="col-5 d-flex align-items-baseline">
                    <span class="field-label">Date :</span>
                    <input type="date" name="date_hosp" class="form-dotted">
                </div>
            </div>

            <!-- SECTION 4 : DIAGNOSTIC -->
            <div class="mt-5">
                <label class="fw-bold">Diagnostic d'entrée :</label>
                <textarea name="diagnostic" class="form-control diagnosis-box" placeholder="Observations médicales..."></textarea>
            </div>

            <!-- SECTION 5 : SIGNATURE ET DATE -->
            <div class="d-flex justify-content-between align-items-end" style="margin-top: 60px;">
                <div class="d-flex align-items-baseline" style="width: 50%;">
                    <span class="field-label">à Njombé, le</span>
                    <input type="date" name="date_signature" class="form-dotted" value="<?= date('Y-m-d') ?>">
                </div>
                <div class="text-center" style="width: 250px;">
                    <div class="mb-5 fw-bold" style="font-size: 0.9rem;">Signature & Cachet</div>
                    <div style="border-top: 1px solid #eee; width: 100%;"></div>
                </div>
            </div>

            <div class="mt-5 pt-5 text-center text-muted small">
                Document généré par le système DME - Hôpital Saint-Jean de Malte
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>