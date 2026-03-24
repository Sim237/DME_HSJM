<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<style>
    .paper-sheet {
        background-color: white;
        width: 21cm;
        min-height: 29.7cm;
        padding: 2cm;
        margin: 20px auto;
        box-shadow: 0 4px 15px rgba(0,0,0,0.15);
        font-family: "Times New Roman", Times, serif;
        color: #000;
        line-height: 2.2; /* Espacement plus large pour le style officiel */
        position: relative;
    }

    .form-dotted {
        border: none;
        border-bottom: 1px dotted #000;
        background: transparent;
        padding: 0 5px;
        outline: none;
        font-family: "Times New Roman", Times, serif;
        font-size: 1.2rem;
        color: #0d6efd; /* Bleu pour la saisie à l'écran */
    }

    .header-hospital {
        text-align: center;
        margin-bottom: 60px;
        line-height: 1.2;
    }
    .header-hospital h4 { font-weight: bold; margin-bottom: 5px; text-transform: uppercase; }
    .header-hospital p { margin-bottom: 0; font-size: 0.9rem; }

    .doc-title {
        text-align: center;
        font-weight: bold;
        font-size: 1.6rem;
        margin: 80px 0 60px;
        text-transform: uppercase;
        letter-spacing: 2px;
    }

    .content-body {
        font-size: 1.3rem;
        text-align: justify;
    }

    .signature-area {
        margin-top: 100px;
        float: right;
        text-align: center;
        width: 250px;
    }

    @media print {
        .no-print, .sidebar, header, .action-bar { display: none !important; }
        .paper-sheet { margin: 0; box-shadow: none; width: 100%; padding: 1.5cm; }
        .form-dotted { color: black; border-bottom: 1px solid #000; }
        body { background: white; }
    }
</style>

<div class="container-fluid bg-light pb-5">
    <!-- BARRE D'ACTIONS -->
    <div class="d-flex justify-content-between align-items-center py-3 px-4 bg-white border-bottom shadow-sm no-print action-bar">
        <a href="<?= BASE_URL ?>patients/dossier/<?= $patient['id'] ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Retour
        </a>
        <div class="d-flex gap-2">
            <button class="btn btn-primary" onclick="window.print()"><i class="bi bi-printer"></i> Imprimer</button>
            <button type="submit" form="formGrossesse" class="btn btn-success px-4"><i class="bi bi-save"></i> Enregistrer</button>
        </div>
    </div>

    <div class="paper-sheet">
        <form id="formGrossesse" action="<?= BASE_URL ?>formulaire/sauvegarder/certificat-grossesse" method="POST">
            <input type="hidden" name="patient_id" value="<?= $patient['id'] ?>">

            <!-- EN-TÊTE -->
            <div class="header-hospital">
                <h4>HOPITAL SAINT JEAN DE MALTE</h4>
                <h4>ORDRE DE MALTE</h4>
                <p>B.P. 56 Njombé - République du Cameroun</p>
                <p>Tél. 99 17 58 01</p>
            </div>

            <div class="doc-title">CERTIFICAT DE GROSSESSE</div>

            <!-- CORPS DU DOCUMENT -->
            <div class="content-body">
                <p>
                    Je soussigné, <input type="text" name="praticien_nom" class="form-dotted" style="width: 78%;" value="Dr. <?= $_SESSION['user_nom'] ?? '' ?>" required>
                </p>
                <p>
                    certifie avoir fait subir le <input type="text" name="date_examen" class="form-dotted" style="width: 63%;" value="<?= date('d/m/Y') ?>">
                </p>
                <p>
                    à Madame <input type="text" name="patient_nom" class="form-dotted" style="width: 80%;" value="<?= htmlspecialchars($patient['nom'].' '.$patient['prenom']) ?>" readonly>
                </p>
                <p>
                    l’examen général et obstétrical prévu par la loi.
                </p>
                <p>
                    Je certifie qu’elle est enceinte de <input type="text" name="duree_grossesse" class="form-dotted" style="width: 50%;" placeholder="ex: 24 semaines" required> et qu’elle
                </p>
                <p>
                    accouchera vers le <input type="text" name="date_accouchement_prevue" class="form-dotted" style="width: 65%;" placeholder="jj/mm/aaaa">
                </p>

                <p class="mt-4">
                    En foi de quoi le présent certificat lui est délivré pour servir et valoir ce que de droit.
                </p>
            </div>

            <!-- DATE ET SIGNATURE -->
            <div class="mt-5">
                <p>Nyombé, le <input type="text" name="date_certificat" class="form-dotted" style="width: 180px;" value="<?= date('d/m/Y') ?>"></p>
            </div>

            <div class="signature-area">
                <p class="fw-bold">Le Praticien</p>
                <div style="height: 80px;"></div>
                <p class="fw-bold">Dr. <?= $_SESSION['user_nom'] ?? '' ?></p>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>