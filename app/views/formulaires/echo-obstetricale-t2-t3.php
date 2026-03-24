<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<style>
    /* --- CONFIGURATION SUPPORT PAPIER A4 --- */
    .paper-sheet {
        background-color: white;
        width: 21cm;
        min-height: 29.7cm;
        padding: 1.2cm 1.5cm;
        margin: 20px auto;
        box-shadow: 0 4px 15px rgba(0,0,0,0.15);
        font-family: Arial, sans-serif;
        color: #000;
        line-height: 1.3;
        display: flex;
        flex-direction: column;
        position: relative;
        overflow: hidden; /* Empêche tout dépassement visuel */
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
        font-size: 0.95rem;
        width: 100%;
        min-width: 0; /* CRUCIAL pour éviter que l'input ne pousse la grille */
    }

    .header-logo { height: 60px; margin-bottom: 5px; }

    .doc-title {
        text-align: center;
        margin: 10px 0;
        line-height: 1.2;
    }
    .doc-title h2 { font-weight: 900; font-size: 1.3rem; text-transform: uppercase; margin-bottom: 0; }
    .doc-title p { font-weight: bold; font-size: 1.1rem; margin-top: 0; }

    /* --- SYSTÈME DE GRILLE STABLE --- */
    .grid-3 {
        display: grid;
        grid-template-columns: repeat(3, 1fr); /* 3 colonnes égales */
        gap: 20px;
        width: 100%;
        margin-bottom: 8px;
    }

    .grid-2 {
        display: grid;
        grid-template-columns: 1fr 1fr; /* 2 colonnes égales */
        gap: 20px;
        width: 100%;
        margin-bottom: 8px;
    }

    .field-group {
        display: flex;
        align-items: baseline;
        width: 100%;
        min-width: 0;
    }

    .label-main { font-weight: bold; margin-right: 8px; white-space: nowrap; font-size: 0.95rem; }

    .section-title { font-weight: bold; text-decoration: underline; margin-top: 15px; margin-bottom: 10px; display: block; text-transform: uppercase; font-size: 0.95rem; }
    .sub-section { margin-left: 15px; }

    .text-block {
        width: 100%;
        min-height: 90px;
        border: 1px solid #dee2e6;
        padding: 10px;
        background: #fdfdfd;
        font-size: 0.95rem;
        margin-top: 5px;
    }

    .signature-area {
        margin-top: auto;
        text-align: center;
        width: 250px;
        align-self: flex-end;
        padding: 15px 0;
    }

    @media print {
        .no-print, .action-bar, header, aside { display: none !important; }
        .paper-sheet { margin: 0; box-shadow: none; width: 100%; padding: 0.5cm 1cm; }
        .form-dotted { color: black; border-bottom: 1px solid #000; }
        .text-block { border: 1px solid #000; }
        body { background: white; }
    }
</style>

<div class="container-fluid bg-light pb-5">
    <div class="action-bar py-3 px-4 bg-white border-bottom shadow-sm no-print sticky-top mb-3">
        <div class="d-flex justify-content-between align-items-center">
            <a href="<?= BASE_URL ?>patients/dossier/<?= $patient['id'] ?>" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left"></i> Retour
            </a>
            <div class="d-flex gap-2">
                <button class="btn btn-primary btn-sm" onclick="window.print()"><i class="bi bi-printer"></i> Imprimer</button>
                <button type="submit" form="formEchoT2" class="btn btn-success btn-sm px-4"><i class="bi bi-save"></i> Enregistrer</button>
            </div>
        </div>
    </div>

    <div class="paper-sheet">
        <form id="formEchoT2" action="<?= BASE_URL ?>formulaire/sauvegarder/echo-pelvienne-t2" method="POST" style="flex-grow: 1; display: flex; flex-direction: column;">
            <input type="hidden" name="patient_id" value="<?= $patient['id'] ?>">

            <div class="d-flex align-items-center mb-2">
                <img src="<?= BASE_URL ?>public/images/logo_ordre_malte.png" class="header-logo" alt="Logo">
                <div class="ms-3">
                    <div class="fw-bold" style="font-size: 0.9rem;">ORDRE DE MALTE</div>
                    <div class="small fw-bold">HÔPITAL SAINT-JEAN DE MALTE</div>
                </div>
            </div>

            <div class="doc-title">
                <h2>ECHOGRAPHIE PELVIENNE</h2>
                <p>(2<sup>eme</sup> - 3<sup>eme</sup> TRIMESTRE)</p>
            </div>

            <!-- IDENTIFICATION -->
            <div class="grid-2">
                <div class="field-group"><span class="label-main">Nom :</span><input type="text" class="form-dotted" value="<?= htmlspecialchars($patient['nom']) ?>" readonly></div>
                <div class="field-group"><span class="label-main">Prénom :</span><input type="text" class="form-dotted" value="<?= htmlspecialchars($patient['prenom']) ?>" readonly></div>
            </div>

            <div class="field-group mb-2">
                <span class="label-main">Gesteté - Parité :</span>
                <input type="text" name="gestite" class="form-dotted">
            </div>

            <div class="grid-3">
                <div class="field-group"><span class="label-main">Age :</span><input type="text" class="form-dotted" value="<?= $age ?> ans" readonly></div>
                <div class="field-group"><span class="label-main">DDR :</span><input type="date" name="ddr" class="form-dotted"></div>
                <div class="field-group"><span class="label-main">DPA :</span><input type="date" name="dpa" class="form-dotted"></div>
            </div>

            <div class="field-group mb-2">
                <span class="label-main">Indication :</span>
                <input type="text" name="indication" class="form-dotted">
            </div>

            <div class="field-group mb-2">
                <span class="label-main">Médecin traitant :</span>
                <input type="text" name="medecin" class="form-dotted" value="Dr. <?= $_SESSION['user_nom'] ?? '' ?>">
            </div>

            <div class="mt-2">
                <span class="fw-bold">RESULTAT :</span>

                <div class="sub-section">
                    <span class="section-title">Fœtus :</span>
                    <div class="grid-2">
                        <div class="field-group"><span class="label-main">Nombre :</span><input type="text" name="f_nombre" class="form-dotted"></div>
                        <div class="field-group"><span class="label-main">Présentation :</span><input type="text" name="f_pres" class="form-dotted"></div>
                    </div>

                    <div class="grid-3">
                        <div class="field-group"><span class="label-main">Vitalité : MAF</span><input type="text" name="v_maf" class="form-dotted"></div>
                        <div class="field-group"><span class="label-main">BDCF</span><input type="text" name="v_bdcf" class="form-dotted"></div>
                        <div class="field-group"><span class="label-main">Tonus fœtal</span><input type="text" name="v_tonus" class="form-dotted"></div>
                    </div>

                    <div class="grid-2">
                        <div class="field-group"><span class="label-main">Activité cardiaque</span><input type="text" name="v_cardio" class="form-dotted"></div>
                        <div class="field-group"><span class="label-main">Respiration</span><input type="text" name="v_resp" class="form-dotted"></div>
                    </div>
                    <div class="grid-2">
                        <div class="field-group"><span class="label-main">Déglutition</span><input type="text" name="v_deglu" class="form-dotted"></div>
                        <div class="field-group"><span class="label-main">Manning</span><input type="text" name="v_manning" class="form-dotted"></div>
                    </div>

                    <span class="section-title">Biométrie :</span>
                    <div class="grid-3">
                        <div class="field-group"><span class="label-main">BIP</span><input type="text" name="b_bip" class="form-dotted"></div>
                        <div class="field-group"><span class="label-main">LF</span><input type="text" name="b_lf" class="form-dotted"></div>
                        <div class="field-group"><span class="label-main">CA</span><input type="text" name="b_ca" class="form-dotted"></div>
                    </div>
                    <div class="field-group"><span class="label-main">Estimation du poids du fœtus :</span><input type="text" name="est_poids" class="form-dotted"></div>

                    <span class="section-title">Morphologie :</span>
                    <div class="grid-2">
                        <div class="field-group"><span class="label-main">SNC</span><input type="text" name="m_snc" class="form-dotted"></div>
                        <div class="field-group"><span class="label-main">Colonne vertébrale</span><input type="text" name="m_colonne" class="form-dotted"></div>
                    </div>
                    <div class="grid-2">
                        <div class="field-group"><span class="label-main">Cœur 4 cavités</span><input type="text" name="m_coeur" class="form-dotted"></div>
                        <div class="field-group"><span class="label-main">Vessie</span><input type="text" name="m_vessie" class="form-dotted"></div>
                    </div>
                    <div class="grid-2">
                        <div class="field-group"><span class="label-main">Thorax</span><input type="text" name="m_thorax" class="form-dotted"></div>
                        <div class="field-group"><span class="label-main">Abdomen</span><input type="text" name="m_abdomen" class="form-dotted"></div>
                    </div>
                    <div class="grid-2">
                        <div class="field-group"><span class="label-main">Reins</span><input type="text" name="m_reins" class="form-dotted"></div>
                        <div class="field-group"><span class="label-main">4 membres</span><input type="text" name="m_membres" class="form-dotted"></div>
                    </div>

                    <div class="grid-2 mt-2">
                        <div class="field-group"><span class="label-main">Liquide amniotique : GCV</span><input type="text" name="liq_gcv" class="form-dotted"></div>
                        <div class="field-group"><span class="label-main">Index de Phelam</span><input type="text" name="liq_phelam" class="form-dotted"></div>
                    </div>

                    <div class="grid-2">
                        <div class="field-group"><span class="label-main">Placenta : Insertion</span><input type="text" name="plac_ins" class="form-dotted"></div>
                        <div class="field-group"><span class="label-main">Rapport avec le col</span><input type="text" name="plac_col" class="form-dotted"></div>
                    </div>

                    <div class="field-group mb-1">
                        <span class="label-main">Cordon ombilical : État du cordon</span>
                        <input type="text" name="cord_etat" class="form-dotted">
                    </div>
                    <div class="field-group sub-section">
                        <span class="label-main">Nombre de vaisseaux</span>
                        <input type="text" name="cord_vaisseaux" class="form-dotted">
                    </div>

                    <span class="section-title">Doppler :</span>
                    <div class="field-group sub-section"><span class="label-main">Artère utérine</span><input type="text" name="dop_ut" class="form-dotted"></div>
                    <div class="field-group sub-section"><span class="label-main">Artère ombilicale</span><input type="text" name="dop_omb" class="form-dotted"></div>
                    <div class="field-group sub-section"><span class="label-main">Artère cérébrale moyenne</span><input type="text" name="dop_cer" class="form-dotted"></div>

                    <span class="section-title">Utérus :</span>
                    <div class="grid-2 sub-section">
                        <div class="field-group"><span class="label-main">Corps utérin</span><input type="text" name="ut_corps" class="form-dotted"></div>
                        <div class="field-group"><span class="label-main">Endomètre</span><input type="text" name="ut_endo" class="form-dotted"></div>
                    </div>
                    <div class="field-group sub-section"><span class="label-main">Aspect du col</span><input type="text" name="ut_col" class="form-dotted"></div>
                </div>

                <div class="mt-2">
                    <span class="fw-bold">Conclusion :</span>
                    <textarea name="conclusion" class="form-control text-block"></textarea>
                </div>
            </div>

            <div class="signature-area">
                <p class="fw-bold mb-4">Signature</p>
                <p class="small fw-bold">Dr. <?= $_SESSION['user_nom'] ?? '' ?></p>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>