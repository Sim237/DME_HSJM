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
        line-height: 2;
        position: relative; /* Référentiel pour le footer */
        padding-bottom: 5cm; /* Zone de sécurité augmentée en bas */
        display: flex;
        flex-direction: column;
    }

    .form-dotted {
        border: none;
        border-bottom: 1px dotted #000;
        background: transparent;
        padding: 0 5px;
        outline: none;
        font-family: "Times New Roman", Times, serif;
        font-size: 1.1rem;
        color: #0d6efd;
    }
    .header-hospital {
        text-align: center;
        margin-bottom: 50px;
        line-height: 1.2;
    }
    .header-hospital h4 { font-weight: bold; margin-bottom: 5px; }
    .header-hospital p { margin-bottom: 0; font-size: 0.9rem; }

    .doc-title {
        text-align: center;
        font-weight: bold;
        font-size: 1.6rem;
        margin: 60px 0;
        text-transform: uppercase;
        letter-spacing: 2px;
    }

    .content-body {
        font-size: 1.2rem;
        margin-bottom: 40px;
    }

    .signature-area {
        margin-top: 50px;
        margin-left: auto; /* Aligne à droite sans utiliser float */
        text-align: center;
        width: 250px;
        line-height: 1.2;
        /* On s'assure que la signature ne descend pas trop bas */
        margin-bottom: 30px;
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
        border-top: 1px solid #000;
        padding-top: 10px;
    }

    @media print {
        .no-print, .sidebar, header { display: none !important; }
        .paper-sheet { margin: 0; box-shadow: none; width: 100%; padding: 1.5cm; }
        .form-dotted { color: black; }
        body { background: white; }
    }
</style>

<div class="container-fluid bg-light pb-5">
    <!-- BARRE D'ACTIONS -->
    <div class="d-flex justify-content-between align-items-center py-3 px-4 bg-white border-bottom shadow-sm no-print">
        <a href="<?= BASE_URL ?>patients/dossier/<?= $patient['id'] ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Retour
        </a>
        <div class="d-flex gap-2">
            <button class="btn btn-primary" onclick="window.print()"><i class="bi bi-printer"></i> Imprimer</button>
            <button type="submit" form="formAccouchement" class="btn btn-success px-4"><i class="bi bi-save"></i> Enregistrer</button>
        </div>
    </div>

    <div class="paper-sheet">
        <form id="formAccouchement" action="<?= BASE_URL ?>formulaire/sauvegarder/certificat-accouchement" method="POST">
            <input type="hidden" name="patient_id" value="<?= $patient['id'] ?>">

            <!-- EN-TÊTE -->
            <div class="header-hospital">
                <h4>HOPITAL SAINT JEAN DE MALTE</h4>
                <h4 class="mb-3">ORDRE DE MALTE</h4>
                <p>RP. 56 Njombé-République du Cameroun</p>
                <p>Tél. 99.17 58 01</p>
            </div>

            <div class="doc-title">CERTIFICAT D’ACCOUCHEMENT</div>

            <!-- CORPS DU DOCUMENT -->
            <div class="content-body">
                <p>
                    Je soussigné <input type="text" name="praticien_nom" class="form-dotted" style="width: 70%;" value="Dr. <?= $_SESSION['user_nom'] ?? '' ?>" required>
                </p>
                <p>
                    Certifie que <input type="text" name="patient_nom" class="form-dotted" style="width: 75%;" value="<?= htmlspecialchars($patient['nom'].' '.$patient['prenom']) ?>" readonly>
                </p>
                <p>
                    Age <input type="text" name="age" class="form-dotted" style="width: 80px;" value="<?= $age ?>"> ans
                    &nbsp; Profession <input type="text" name="profession" class="form-dotted" style="width: 200px;">
                    &nbsp; Domicile <input type="text" name="domicile" class="form-dotted" style="width: 180px;">
                </p>
                <p>
                    A accouché à la Maternité de l’Hôpital St Jean de Malte le <input type="date" name="date_accouchement" class="form-dotted" required>
                </p>
                <p>
                    D’un enfant &nbsp;
                    <select name="etat_enfant" class="form-dotted">
                        <option value="vivant">(vivant)</option>
                        <option value="mort_ne">(mort né)</option>
                    </select>
                    &nbsp; de sexe <select name="sexe_enfant" class="form-dotted">
                        <option value="M">Masculin</option>
                        <option value="F">Féminin</option>
                    </select>
                </p>

                <p class="mt-4">
                    En foi de quoi ce certificat est délivré pour servir et valoir ce que de droit.
                </p>
            </div>

             <!-- BLOC SIGNATURE : Aligné proprement -->
        <div style="margin-top: auto;"> <!-- Pousse le bloc vers le bas mais avant le footer -->
            <p>Njombe, le <input type="text" name="date_certificat" class="form-dotted" style="width: 180px;" value="<?= date('d/m/Y') ?>"></p>

            <div class="signature-area">
                <p class="mb-5">Cachet et Signature</p>
                <p class="fw-bold" style="text-decoration: underline;">
                    Dr. <?= $_SESSION['user_nom'] ?? 'Simeni' ?>
                </p>
            </div>
        </div>

        <!-- PIED DE PAGE : Toujours scellé au bas de la feuille -->
        <div class="footer-note">
            L’Hôpital Saint-Jean de Malte est une œuvre humanitaire fonctionnant sous l’égide des Œuvres Hospitalières Françaises de l’Ordre de Malte, en partenariat avec le Ministère de la Santé Publique et avec les Plantations SPNP/SBM/PHP.
        </div>
    </form>
</div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>