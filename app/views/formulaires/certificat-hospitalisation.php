<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<style>
    .paper-sheet {
        background-color: white;
        width: 21cm;
        min-height: 29.7cm;
        padding: 1.5cm 2cm;
        margin: 20px auto;
        box-shadow: 0 4px 15px rgba(0,0,0,0.15);
        font-family: "Times New Roman", Times, serif;
        color: #000;
        line-height: 1.8;
        position: relative;
        display: flex;
        flex-direction: column;
        padding-bottom: 4cm; /* Espace pour le pied de page */
    }

    .form-dotted {
        border: none;
        border-bottom: 1px dotted #000;
        background: transparent;
        padding: 0 5px;
        outline: none;
        font-family: "Times New Roman", Times, serif;
        font-size: 1.15rem;
        color: #0d6efd; /* Bleu pour la saisie */
    }

    .header-hospital {
        text-align: center;
        margin-bottom: 40px;
        line-height: 1.3;
    }
    .header-hospital h3 { font-weight: bold; margin-bottom: 0; text-transform: uppercase; }
    .header-hospital p { margin-bottom: 0; font-size: 0.95rem; font-weight: 500; }

    .doc-title {
        text-align: center;
        font-weight: bold;
        font-size: 1.6rem;
        margin: 50px 0;
        text-transform: uppercase;
        letter-spacing: 1px;
    }

    .content-body {
        font-size: 1.2rem;
        margin-top: 20px;
    }

    .signature-area {
        margin-top: 50px;
        margin-left: auto;
        text-align: center;
        width: 280px;
        line-height: 1.2;
    }

    .footer-note {
        position: absolute;
        bottom: 1cm;
        left: 1.5cm;
        right: 1.5cm;
        font-size: 0.75rem;
        line-height: 1.3;
        text-align: center;
        font-style: italic;
        border-top: 0.5px solid #000;
        padding-top: 10px;
    }

    @media print {
        .no-print, .sidebar, header, .action-bar { display: none !important; }
        .paper-sheet { margin: 0; box-shadow: none; width: 100%; padding: 1cm 1.5cm; }
        .form-dotted { color: black; border-bottom: 1px solid #000; }
        body { background: white; }
    }
</style>

<div class="container-fluid bg-light pb-5">
    <!-- BARRE D'ACTIONS -->
    <div class="d-flex justify-content-between align-items-center py-3 px-4 bg-white border-bottom shadow-sm no-print action-bar">
        <a href="<?= BASE_URL ?>patients/dossier/<?= $patient['id'] ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Retour au dossier
        </a>
        <div class="d-flex gap-2">
            <button class="btn btn-primary" onclick="window.print()"><i class="bi bi-printer"></i> Imprimer</button>
            <button type="submit" form="formHospitalisation" class="btn btn-success px-4"><i class="bi bi-save"></i> Enregistrer</button>
        </div>
    </div>

    <div class="paper-sheet">
        <form id="formHospitalisation" action="<?= BASE_URL ?>formulaire/sauvegarder/certificat-hospitalisation" method="POST">
            <input type="hidden" name="patient_id" value="<?= $patient['id'] ?>">

            <!-- EN-TÊTE OFFICIEL -->
            <div class="header-hospital">
                <h3>HOPITAL ST-JEAN DE MALTE</h3>
                <p>Hôpital agréé au statut d’établissement d’Utilité Publique</p>
                <p>ORDRE DE MALTE FRANCE ,</p>
                <p>Association Humanitaire à but non lucratif</p>
                <p>B.P. 56 Njombé - République du Cameroun</p>
                <p>Tél. / Fax (237) 343 -30-41</p>
            </div>

            <div class="doc-title">CERTIFICAT D’HOSPITALISATION</div>

            <!-- CORPS DU DOCUMENT -->
            <div class="content-body">
                <p>
                    Je soussigné <input type="text" name="praticien_nom" class="form-dotted" style="width: 75%;" value="Dr. <?= $_SESSION['user_nom'] ?? '' ?>" required>
                </p>
                <p>
                    Certifie que <input type="text" name="patient_nom" class="form-dotted" style="width: 75%;" value="<?= htmlspecialchars($patient['nom'].' '.$patient['prenom']) ?>" readonly>
                </p>
                <p>
                    Âge <input type="text" name="age" class="form-dotted" style="width: 80px;" value="<?= $age ?>"> ans
                    &nbsp;&nbsp; Profession <input type="text" name="profession" class="form-dotted" style="width: 50%;">
                </p>
                <p>
                    Est hospitalisé (e) <input type="text" name="periode_hosp" class="form-dotted" style="width: 70%;" placeholder="du ... au ... (ou depuis le ...)">
                </p>

                <p class="mt-5 text-center" style="font-size: 1.1rem;">
                    En foi de quoi ce certificat est délivré pour servir et valoir ce que de droit.
                </p>
            </div>

            <!-- DATE ET SIGNATURE -->
            <div style="margin-top: auto; padding-bottom: 1cm;">
                <p>Nyombé, le <input type="text" name="date_certificat" class="form-dotted" style="width: 200px;" value="<?= date('d/m/Y') ?>"></p>

                <div class="signature-area">
                    <p class="mb-5">Cachet et Signature</p>
                    <div style="height: 40px;"></div>
                    <p class="fw-bold">Dr. <?= $_SESSION['user_nom'] ?? '' ?></p>
                </div>
            </div>

            <!-- PIED DE PAGE -->
            <div class="footer-note">
                L’Hôpital Saint-Jean de Malte est une œuvre humanitaire fonctionnant tous l’égide des Œuvres Hospitalière Françaises de l'Ordre de Malte, en partenariat avec le Ministère de la Santé Publique et avec les Plantations SPNP/SBM/PHP.
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>