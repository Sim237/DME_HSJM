<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<style>
    .paper-sheet {
        background-color: white;
        width: 21cm;
        min-height: 29.7cm;
        padding: 2cm;
        margin: 20px auto;
        box-shadow: 0 0 10px rgba(0,0,0,0.1);
        font-family: "Times New Roman", Times, serif;
        color: #000;
        line-height: 1.6;
    }
    .form-dotted {
        border: none;
        border-bottom: 1px dotted #000;
        background: transparent;
        padding: 0 5px;
        outline: none;
    }
    .header-hospital {
        text-align: center;
        text-transform: uppercase;
        margin-bottom: 30px;
    }
    .header-hospital h5 { font-weight: bold; margin-bottom: 0; }
    .form-title {
        text-align: center;
        text-decoration: underline;
        font-weight: bold;
        font-size: 1.4rem;
        margin: 40px 0;
    }
    .section-content { margin-bottom: 20px; font-size: 1.1rem; }
    .checkbox-group { margin: 15px 0 15px 40px; }
    .signature-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        margin-top: 50px;
        gap: 50px;
    }
    .signature-box { height: 100px; }

    @media print {
        body { background: none; padding: 0; }
        .no-print, .sidebar, .header-main { display: none !important; }
        .paper-sheet { margin: 0; box-shadow: none; width: 100%; }
        .form-dotted { border-bottom: none; }
    }
</style>

<div class="container-fluid bg-light pb-5">
    <!-- Barre d'action fixe -->
    <div class="d-flex justify-content-between align-items-center py-3 px-4 bg-white border-bottom shadow-sm no-print">
        <div>
            <a href="<?= BASE_URL ?>patients/dossier/<?= $patient['id'] ?>" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Retour au dossier
            </a>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-primary" onclick="window.print()">
                <i class="bi bi-printer"></i> Imprimer
            </button>
            <button type="submit" form="formLigature" class="btn btn-success">
                <i class="bi bi-save"></i> Enregistrer le formulaire
            </button>
        </div>
    </div>

    <div class="paper-sheet">
        <div class="header-hospital">
            <h5>Hôpital Saint-Jean de Malte</h5>
            <div>Ordre de Malte France</div>
            <small>B.P : 56 Njombé Tel. / Fax (237): 343 30 41</small>
        </div>

        <div class="form-title">AUTORISATION DE LIGATURE DES TROMPES</div>

        <form id="formLigature" action="<?= BASE_URL ?>formulaire/sauvegarder/ligature-trompes" method="POST">
            <input type="hidden" name="patient_id" value="<?= $patient['id'] ?>">

            <div class="section-content">
                Je soussigné : <input type="text" name="signataire_nom" class="form-dotted" style="width: 70%;" placeholder="Nom et prénom" required>
            </div>

            <div class="section-content">
                CNI N° : <input type="text" name="cni_num" class="form-dotted" style="width: 30%;">
                délivrée le <input type="date" name="cni_date" class="form-dotted">
                à <input type="text" name="cni_lieu" class="form-dotted" style="width: 20%;">
            </div>

            <div class="section-content mt-4">
                Autorise le Médecin à pratiquer une stérilisation définitive par ligature des trompes à :
            </div>

            <div class="checkbox-group">
                <div class="form-check mb-2">
                    <input class="form-check-input" type="radio" name="beneficiaire" id="moiMeme" value="moi-meme" checked>
                    <label class="form-check-label" for="moiMeme">(1) Moi-même</label>
                </div>

                <div class="form-check mb-2">
                    <input class="form-check-input" type="radio" name="beneficiaire" id="epouse" value="epouse">
                    <label class="form-check-label" for="epouse">(1) Mon épouse nommée :</label>
                    <input type="text" name="epouse_nom" class="form-dotted" style="width: 50%;" placeholder="<?= htmlspecialchars($patient['nom'].' '.$patient['prenom']) ?>">
                </div>
            </div>

            <div class="section-content" style="margin-left: 40px;">
                CNI N° : <input type="text" name="epouse_cni" class="form-dotted" style="width: 25%;">
                délivrée le <input type="date" name="epouse_cni_date" class="form-dotted">
                à <input type="text" name="epouse_cni_lieu" class="form-dotted" style="width: 20%;">
            </div>

            <div class="section-content mt-3">
                Avec (son) plein accord
            </div>

            <div class="mt-4">
                <strong><u>Signatures :</u></strong>
            </div>

            <div class="signature-grid">
                <div>
                    <p>Époux</p>
                    <div class="signature-box border-bottom border-dark"></div>
                </div>
                <div>
                    <p>Autre (préciser S.V.P) <input type="text" name="autre_preciser" class="form-dotted" style="width: 50%;"></p>
                    <div class="signature-box border-bottom border-dark"></div>
                </div>
            </div>

            <div class="text-end mt-5">
                Njombé, le <input type="text" name="date_signature" class="form-dotted" value="<?= date('d/m/Y') ?>">
            </div>

            <div class="mt-5 pt-5 small">
                <em>(1) cocher la case correspondante</em>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>