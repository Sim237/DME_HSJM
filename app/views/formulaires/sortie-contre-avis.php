<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<style>
    :root {
        --legal-red: #d32f2f;
        --legal-blue: #1976d2;
        --paper-bg: #ffffff;
    }

    body { background-color: #f0f2f5; font-family: 'Inter', sans-serif; }

    .form-card {
        max-width: 900px;
        margin: 30px auto;
        background: var(--paper-bg);
        border-radius: 12px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        padding: 40px;
        position: relative;
    }

    .legal-header {
        text-align: center;
        border-bottom: 2px solid #333;
        padding-bottom: 20px;
        margin-bottom: 30px;
    }

    .legal-header h2 {
        font-weight: 800;
        letter-spacing: 1px;
        color: #212529;
        text-transform: uppercase;
    }

    .input-section {
        background: #f8f9fa;
        padding: 25px;
        border-radius: 15px;
        margin-bottom: 20px;
        border: 1px solid #e9ecef;
    }

    .label-custom {
        font-weight: 700;
        font-size: 0.85rem;
        color: #495057;
        text-transform: uppercase;
        margin-bottom: 8px;
        display: block;
    }

    /* Toggle pour Rayer la mention inutile */
    .mention-selector {
        display: flex;
        gap: 10px;
        background: #eee;
        padding: 5px;
        border-radius: 10px;
        margin: 15px 0;
    }

    .mention-option {
        flex: 1;
        padding: 10px;
        text-align: center;
        border-radius: 8px;
        cursor: pointer;
        transition: 0.3s;
        font-weight: 600;
    }

    .mention-option.active {
        background: var(--legal-red);
        color: white;
        box-shadow: 0 4px 10px rgba(211, 47, 47, 0.3);
    }

    /* Zone de Signature */
    .signature-pad {
        border: 2px dashed #ccc;
        border-radius: 10px;
        height: 150px;
        background: #fafafa;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: crosshair;
        position: relative;
    }

    .warning-box {
        border-left: 5px solid var(--legal-red);
        background: #fff5f5;
        padding: 15px;
        margin: 25px 0;
        font-style: italic;
        color: #b71c1c;
    }

    @media print {
        .no-print { display: none !important; }
        .form-card { box-shadow: none; width: 100%; margin: 0; padding: 0; }
        .input-section { background: none; border: none; padding: 10px 0; }
        .mention-option:not(.active) { text-decoration: line-through; opacity: 0.5; }
    }
</style>

<div class="container-fluid pb-5">
    <!-- Barre d'outils -->
    <div class="d-flex justify-content-between align-items-center py-3 px-4 bg-white border-bottom shadow-sm no-print sticky-top mb-3">
        <a href="<?= BASE_URL ?>patients/dossier/<?= $patient['id'] ?>" class="btn btn-outline-secondary rounded-pill btn-sm">
            <i class="bi bi-arrow-left"></i> Retour au dossier
        </a>
        <div class="d-flex gap-2">
            <button class="btn btn-primary rounded-pill btn-sm" onclick="window.print()"><i class="bi bi-printer"></i> Imprimer</button>
            <button type="submit" form="formSCA" class="btn btn-danger rounded-pill btn-sm px-4 shadow">
                <i class="bi bi-shield-lock"></i> Valider et Signer
            </button>
        </div>
    </div>

    <div class="form-card">
        <form id="formSCA" action="<?= BASE_URL ?>formulaire/sauvegarder/sortie-contre-avis" method="POST">
            <input type="hidden" name="patient_id" value="<?= $patient['id'] ?>">

            <div class="legal-header">
                <h2>Attestation de Sortie Contre Avis Médical</h2>
                <span class="badge bg-dark"> NJOMBÉ - HÔPITAL SAINT-JEAN DE MALTE</span>
            </div>

            <!-- SECTION SIGNATAIRE -->
            <div class="input-section">
                <h6 class="fw-bold mb-3 text-primary"><i class="bi bi-person-badge"></i> Identification du Signataire</h6>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="label-custom">Nom(s) et Prénom(s)</label>
                        <input type="text" name="signataire_nom" class="form-control" placeholder="Qui signe l'attestation ?" required>
                    </div>
                    <div class="col-md-6">
                        <label class="label-custom">Qualité de (lien avec le patient)</label>
                        <input type="text" name="lien_parente" class="form-control" placeholder="Ex: Lui-même, Père, Épouse...">
                    </div>
                    <div class="col-md-12">
                        <label class="label-custom">Adresse complète</label>
                        <input type="text" name="adresse" class="form-control" placeholder="Quartier, Ville...">
                    </div>
                    <div class="col-md-6">
                        <label class="label-custom">CNI N°</label>
                        <input type="text" name="cni_num" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="label-custom">Délivrée le</label>
                        <input type="date" name="cni_date" class="form-control">
                    </div>
                </div>
            </div>

            <!-- SECTION ACTION -->
            <div class="input-section">
                <h6 class="fw-bold mb-3 text-primary"><i class="bi bi-arrow-right-circle"></i> Décision de sortie</h6>
                <p class="small text-muted">Veuillez choisir l'option correspondant à la situation :</p>
                <input type="hidden" name="choix_action" id="choix_action" value="sortir">
                <div class="mention-selector">
                    <div class="mention-option active" id="opt1" onclick="setChoice('sortir', 'opt1')">DÉCIDE DE SORTIR</div>
                    <div class="mention-option" id="opt2" onclick="setChoice('faire_sortir', 'opt2')">DÉCIDE DE FAIRE SORTIR</div>
                </div>

                <div class="row mt-3">
                    <div class="col-md-8">
                        <label class="label-custom">Nom du Patient</label>
                        <input type="text" class="form-control fw-bold" value="<?= htmlspecialchars($patient['nom'].' '.$patient['prenom']) ?>" readonly>
                    </div>
                    <div class="col-md-4">
                        <label class="label-custom">Âge</label>
                        <input type="text" class="form-control" value="<?= $age ?> ans" readonly>
                    </div>
                </div>
            </div>

            <!-- AVIS MÉDICAL -->
            <div class="warning-box">
                <i class="bi bi-exclamation-triangle-fill"></i>
                "Sur ma propre initiative et contre l’avis du médecin traitant."
            </div>

            <!-- SIGNATURE -->
            <div class="row align-items-end">
                <div class="col-md-6">
                    <label class="label-custom">Fait à Njombé, le</label>
                    <input type="text" name="date_signature" class="form-control" value="<?= date('d/m/Y') ?>">
                </div>
                <div class="col-md-6">
                    <label class="label-custom">Signature ou empreinte digitale</label>
                    <div class="signature-pad" id="sigPad">
                        <span class="text-muted small">Cliquez ici pour signer numériquement</span>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
    function setChoice(val, id) {
        document.getElementById('choix_action').value = val;
        document.querySelectorAll('.mention-option').forEach(el => el.classList.remove('active'));
        document.getElementById(id).classList.add('active');
    }

    // Simulation de signature
    document.getElementById('sigPad').addEventListener('click', function() {
        const name = prompt("Veuillez saisir votre nom complet pour signature électronique :");
        if(name) {
            this.innerHTML = `<h4 style="font-family: 'Brush Script MT', cursive;">${name}</h4>`;
            this.style.background = "#e8f5e9";
            this.style.borderColor = "#4caf50";
        }
    });
</script>