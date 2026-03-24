<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<style>
    /* --- CONFIGURATION DU SUPPORT PAPIER A4 --- */
    .paper-sheet {
        background-color: white;
        width: 21cm;
        min-height: 29.7cm;
        padding: 1.5cm 1.8cm;
        margin: 20px auto;
        box-shadow: 0 4px 15px rgba(0,0,0,0.15);
        font-family: Arial, sans-serif;
        color: #000;
        line-height: 1.4;
        display: flex;
        flex-direction: column;
        position: relative;
    }

    /* --- STYLE DES CHAMPS DE SAISIE --- */
    .form-dotted {
        border: none;
        border-bottom: 1px dotted #666;
        background: transparent;
        padding: 0 5px;
        outline: none;
        font-weight: bold;
        color: #0d6efd; /* Bleu pour la saisie écran */
        font-family: inherit;
    }

    /* --- EN-TÊTE ET BLOC PATIENT --- */
    .header-top {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 30px;
    }

    .hospital-brand {
        display: flex;
        flex-direction: column;
    }
    .header-logo { height: 65px; margin-bottom: 5px; }

    .patient-id-box {
        border: 1.5px solid #000;
        width: 320px;
        padding: 12px;
        background-color: #fff;
    }

    /* Grille pour aligner parfaitement les labels et les deux-points */
    .id-row {
        display: grid;
        grid-template-columns: 75px 10px 1fr;
        align-items: baseline;
        margin-bottom: 6px;
    }
    .id-label { font-size: 0.9rem; font-weight: bold; }
    .id-colon { font-weight: bold; }
    .id-value { font-weight: bold; color: #0d6efd; padding-left: 8px; border-bottom: 1px dotted #ccc; }

    /* --- TITRES --- */
    .doc-title-section {
        text-align: center;
        margin: 20px 0;
        clear: both;
    }
    .doc-title-section h5 { font-weight: bold; margin-bottom: 5px; text-transform: uppercase; }
    .doc-title-section h4 { font-weight: 900; text-decoration: underline; text-transform: uppercase; }

    /* --- GRILLES DE CONTENU --- */
    .field-row { display: flex; align-items: flex-end; margin-bottom: 15px; }
    .section-label { font-weight: bold; margin-right: 10px; white-space: nowrap; }

    /* Grille pour LCC, BIP, LF (3 colonnes égales) */
    .measurements-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 30px;
        margin: 25px 0;
        width: 100%;
    }
    .m-item { display: flex; align-items: flex-end; min-width: 0; }
    .m-label { font-weight: bold; margin-right: 8px; white-space: nowrap; }
    .m-item .form-dotted { flex: 1; min-width: 0; }

    .text-block {
        width: 100%;
        min-height: 120px;
        border: 1px solid #dee2e6;
        padding: 10px;
        background: #fdfdfd;
        font-size: 1rem;
        margin-top: 5px;
    }

    /* --- SIGNATURE --- */
    .signature-area {
        margin-top: auto;
        margin-left: auto;
        text-align: center;
        width: 250px;
        padding: 40px 0 20px;
    }

    /* --- MENTIONS LÉGALES --- */
    .footer-note {
        margin-top: 20px;
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
        .form-dotted, .id-value { color: black; border-bottom: 1px solid #000; }
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
                <button type="submit" form="formEcho" class="btn btn-success btn-sm px-4"><i class="bi bi-save"></i> Enregistrer</button>
            </div>
        </div>
    </div>

    <div class="paper-sheet">
        <form id="formEcho" action="<?= BASE_URL ?>formulaire/sauvegarder/echo-obstetricale" method="POST" style="flex-grow: 1; display: flex; flex-direction: column;">
            <input type="hidden" name="patient_id" value="<?= $patient['id'] ?>">

            <!-- EN-TÊTE CORRIGÉ -->
            <div class="header-top">
                <div class="hospital-brand">
                    <img src="<?= BASE_URL ?>public/images/logo_ordre_malte.png" class="header-logo" alt="Logo">
                    <div class="small fw-bold">ORDRE DE MALTE</div>
                    <div class="small">HÔPITAL SAINT-JEAN DE MALTE</div>
                </div>

                <div class="patient-id-box">
                    <div class="id-row">
                        <span class="id-label">Date</span><span class="id-colon">:</span>
                        <span class="id-value"><?= date('d/m/Y') ?></span>
                    </div>
                    <div class="id-row">
                        <span class="id-label">Nom</span><span class="id-colon">:</span>
                        <span class="id-value"><?= htmlspecialchars($patient['nom']) ?></span>
                    </div>
                    <div class="id-row">
                        <span class="id-label">Prénom</span><span class="id-colon">:</span>
                        <span class="id-value"><?= htmlspecialchars($patient['prenom']) ?></span>
                    </div>
                    <div class="id-row">
                        <span class="id-label">Âge</span><span class="id-colon">:</span>
                        <span class="id-value"><?= $age ?> ans</span>
                    </div>
                </div>
            </div>

            <div class="doc-title-section">
                <h5>SERVICE DE GYNECOLOGIE OBSTETRIQUE</h5>
                <h4>ECHOGRAPHIE OBSTETRICALE - 1<sup>er</sup> TRIMESTRE</h4>
            </div>

            <!-- INFOS CLINIQUES -->
            <div class="mt-2">
                <div class="field-row">
                    <span class="section-label">Gestetité - parité :</span>
                    <input type="text" name="gestite" class="form-dotted flex-grow-1">
                </div>
                <div class="row g-3">
                    <div class="col-6 d-flex align-items-end">
                        <span class="section-label">Date des dernières règles :</span>
                        <input type="date" name="ddr" class="form-dotted flex-grow-1">
                    </div>
                    <div class="col-6 d-flex align-items-end">
                        <span class="section-label">Date probable d'accouchement :</span>
                        <input type="date" name="dpa" class="form-dotted flex-grow-1">
                    </div>
                </div>
                <div class="field-row mt-2">
                    <span class="section-label">Indications :</span>
                    <input type="text" name="indications" class="form-dotted flex-grow-1">
                </div>
                <div class="field-row">
                    <span class="section-label">Médecin traitant :</span>
                    <input type="text" name="medecin" class="form-dotted flex-grow-1" value="Dr. <?= $_SESSION['user_nom'] ?? 'Simeni' ?>">
                </div>
            </div>

            <!-- SECTION RÉSULTATS -->
            <div class="mt-4">
                <h5 class="fw-bold text-decoration-underline mb-3">Résultat 1<sup>er</sup> Trimestre</h5>

                <div class="field-row">
                    <span class="section-label">Sac ovulaire :</span>
                    <input type="text" name="sac" class="form-dotted flex-grow-1">
                </div>
                <div class="field-row">
                    <span class="section-label">Couronne trophoblastique :</span>
                    <input type="text" name="couronne" class="form-dotted flex-grow-1">
                </div>
                <div class="field-row">
                    <span class="section-label">Embryon :</span>
                    <input type="text" name="embryon" class="form-dotted flex-grow-1">
                </div>

                <!-- GRILLE DES MESURES (3 COLONNES ALIGNÉES) -->
                <div class="measurements-grid">
                    <div class="m-item">
                        <span class="m-label">LCC :</span>
                        <input type="text" name="lcc" class="form-dotted" placeholder="mm">
                    </div>
                    <div class="m-item">
                        <span class="m-label">BIP :</span>
                        <input type="text" name="bip" class="form-dotted" placeholder="mm">
                    </div>
                    <div class="m-item">
                        <span class="m-label">LF :</span>
                        <input type="text" name="lf" class="form-dotted" placeholder="mm">
                    </div>
                </div>

                <div class="mt-2">
                    <label class="section-label">Annexes :</label>
                    <textarea name="annexes" class="form-control text-block" rows="3"></textarea>
                </div>

                <div class="mt-4">
                    <label class="section-label text-decoration-underline">CONCLUSION</label>
                    <textarea name="conclusion" class="form-control text-block" style="min-height: 150px;"></textarea>
                </div>
            </div>

            <!-- SIGNATURE -->
            <div class="signature-area">
                <p class="fw-bold mb-5">Signature</p>
                <p class="small fw-bold border-top border-dark pt-1">Dr. <?= $_SESSION['user_nom'] ?? 'Simeni' ?></p>
            </div>

            <!-- PIED DE PAGE -->
            <div class="footer-note">
                L’Hôpital Saint-Jean de Malte est une œuvre humanitaire fonctionnant tous l’égide des Œuvres Hospitalière Françaises de l'Ordre de Malte, en partenariat avec le Ministère de la Santé Publique et avec les Plantations SPNP/SBM/PHP.
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>