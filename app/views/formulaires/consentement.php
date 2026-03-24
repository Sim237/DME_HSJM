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
        line-height: 1.4;
        position: relative;
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
        font-weight: bold;
        color: #0d6efd;
    }

    .header-logo { height: 60px; margin-bottom: 10px; }

    .doc-title {
        text-align: center;
        font-weight: bold;
        font-size: 1.3rem;
        margin: 20px 0;
        text-decoration: underline;
    }

    .notice-box {
        font-size: 0.85rem;
        border: 1px solid #000;
        padding: 10px;
        margin-bottom: 20px;
        font-style: italic;
        background: #f9f9f9;
    }

    .section-clause {
        margin-bottom: 12px;
        display: flex;
        align-items: flex-start;
        gap: 15px;
        font-size: 0.95rem;
        text-align: justify;
    }

    .consent-frame {
        border: 3px solid #000;
        padding: 15px;
        text-align: center;
        font-weight: bold;
        font-size: 1.1rem;
        margin: 30px 0;
        text-transform: uppercase;
    }

    .signature-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 50px;
        margin-top: 30px;
    }

    @media print {
        .no-print, .action-bar { display: none !important; }
        .paper-sheet { margin: 0; box-shadow: none; width: 100%; padding: 1cm 1.5cm; }
        .form-dotted { color: black; border-bottom: 1px solid #000; }
        body { background: white; }
        .notice-box { background: none; }
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
            <button type="submit" form="formConsentement" class="btn btn-success px-4"><i class="bi bi-save"></i> Enregistrer le Consentement</button>
        </div>
    </div>

    <div class="paper-sheet">
        <form id="formConsentement" action="<?= BASE_URL ?>formulaire/sauvegarder/consentement" method="POST">
            <input type="hidden" name="patient_id" value="<?= $patient['id'] ?>">

            <!-- EN-TÊTE -->
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <img src="<?= BASE_URL ?>public/images/logo_ordre_malte.png" class="header-logo" alt="Logo">
                    <div class="small fw-bold">ORDRE DE MALTE</div>
                </div>
                <div class="text-end small">
                    <strong>HÔPITAL SAINT-JEAN DE MALTE</strong><br>
                    Njombé, Cameroun
                </div>
            </div>

            <div class="doc-title">FORMULAIRE DE CONSENTEMENT ÉCLAIRÉ</div>

            <div class="notice-box">
                Ce document est rempli par le praticien avec le concours du patient lors du premier entretien et devra être joint dans le dossier médical. Il est remis à l'autre partie sur demande.
            </div>

            <!-- IDENTITÉ -->
            <div class="mb-4">
                Je soussigné(e), Madame/Monsieur <input type="text" class="form-dotted" style="width: 60%;" value="<?= htmlspecialchars($patient['nom'].' '.$patient['prenom']) ?>" readonly><br>
                Né(e) le : <input type="text" class="form-dotted" style="width: 150px;" value="<?= date('d/m/Y', strtotime($patient['date_naissance'])) ?>" readonly>
                à <input type="text" name="lieu_naissance" class="form-dotted" style="width: 250px;"><br>
                certifie avoir pu m'entretenir avec le Docteur <input type="text" name="medecin_nom" class="form-dotted" style="width: 300px;" value="<?= $_SESSION['user_nom'] ?? '' ?>">
                en date du <input type="text" name="date_entretien" class="form-dotted" style="width: 120px;" value="<?= date('d/m/Y') ?>">.
            </div>

            <!-- OPTIONS D'HOSPITALISATION -->
            <div class="mb-4">
                <div class="form-check mb-2">
                    <input class="form-check-input border-dark" type="checkbox" name="hosp_prevue">
                    <label class="form-check-label">Je dois être hospitalisé(e) à partir du <input type="text" name="date_hosp" class="form-dotted" style="width: 100px;"> pour subir une intervention chirurgicale prévue le <input type="text" name="date_interv" class="form-dotted" style="width: 100px;">.</label>
                </div>
                <div class="form-check mb-2">
                    <input class="form-check-input border-dark" type="checkbox" name="interv_seule">
                    <label class="form-check-label">Je dois subir une intervention chirurgicale prévue le <input type="text" name="date_interv_seule" class="form-dotted" style="width: 100px;">.</label>
                </div>
            </div>

            <!-- CLAUSES D'INFORMATION -->
            <div class="section-clause">
                <input type="checkbox" class="form-check-input border-dark mt-1" checked>
                <div>Le médecin m'a donné des informations précises sur mes problèmes de santé/les problèmes de santé de mon enfant (stagiaire).</div>
            </div>

            <div class="section-clause">
                <input type="checkbox" class="form-check-input border-dark mt-1" checked>
                <div>Il m'a expliqué, de façon simple et intelligible, l'évolution possible si l'on ne recourt pas à cette intervention, ainsi que les autres types de traitement(s), s'ils existent, avec leurs avantages et leurs inconvénients.</div>
            </div>

            <div class="section-clause">
                <input type="checkbox" class="form-check-input border-dark mt-1" checked>
                <div>Il m'a clairement indiqué la nature (technique opératoire) et le but de l'intervention qui m'est proposée, l'éventuel contrôle après cet acte, les précautions à prendre, les douleurs qui peuvent en résulter, les suites opératoires habituelles et les risques et complications possibles de cette chirurgie, non seulement dans les suites opératoires mais aussi à terme.</div>
            </div>

            <div class="section-clause">
                <input type="checkbox" class="form-check-input border-dark mt-1" checked>
                <div>Il a également été convenu du fait qu'au cours de l'intervention, une découverte ou un événement imprévu pourrait conduire le médecin à élargir l'intervention en réalisant des actes complémentaires différents de ceux prévus initialement.</div>
            </div>

            <div class="section-clause">
                <input type="checkbox" class="form-check-input border-dark mt-1" checked>
                <div>J'ai été informé(e) d'une estimation du coût financier lié à ce type de traitement/d'intervention et, en fonction de mes exigences personnelles : <input type="text" name="cout_estim" class="form-dotted" style="width: 150px;"> FCFA.</div>
            </div>

            <div class="section-clause">
                <input type="checkbox" class="form-check-input border-dark mt-1" checked>
                <div>J'ai eu la possibilité de poser des questions au Docteur <input type="text" class="form-dotted" style="width: 200px;" value="<?= $_SESSION['user_nom'] ?? '' ?>"> qui a répondu de façon complète et satisfaisante.</div>
            </div>

            <!-- CADRE DE CONSENTEMENT -->
            <div class="consent-frame">
                POUR QUE SOIT RÉALISÉE L'INTERVENTION PRÉVUE<br>
                DANS LES CONDITIONS CI-DESSUS
            </div>

            <div class="text-center small italic">
                Fait à Njombé, le <input type="text" name="date_signature" class="form-dotted" style="width: 150px;" value="<?= date('d/m/Y') ?>">
            </div>

            <!-- SIGNATURES -->
            <div class="signature-grid">
                <div class="text-center">
                    <p class="fw-bold">Signature du patient</p>
                    <div style="height: 80px; border: 1px dashed #ccc;" class="mb-2"></div>
                </div>
                <div class="text-center">
                    <p class="fw-bold">Signature du Praticien</p>
                    <div style="height: 80px; border: 1px dashed #ccc;" class="mb-2"></div>
                    <p class="small">Dr. <?= $_SESSION['user_nom'] ?? '' ?></p>
                </div>
            </div>

            <!-- PIED DE PAGE -->
            <div class="mt-auto pt-4 text-center border-top border-dark" style="font-size: 0.65rem; font-style: italic;">
                L'Hôpital Saint-Jean de Malte est une œuvre humanitaire fonctionnant sous l'égide des Œuvres Hospitalières Françaises de l'Ordre de Malte, en partenariat avec le Ministère de la Santé Publique et avec les Plantations SPNP/SBM/PHP.
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>