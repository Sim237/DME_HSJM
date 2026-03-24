<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<style>
    :root {
        --medical-blue: #0d6efd;
        --soft-bg: #f0f4f8;
        --card-shadow: 0 10px 30px rgba(0,0,0,0.08);
    }

    body { background-color: var(--soft-bg); font-family: 'Inter', sans-serif; }

    .form-container {
        max-width: 900px;
        margin: 40px auto;
        background: white;
        padding: 40px;
        border-radius: 20px;
        box-shadow: var(--card-shadow);
    }

    .hospital-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 2px solid #eee;
        padding-bottom: 20px;
        margin-bottom: 30px;
    }

    .form-title {
        color: var(--medical-blue);
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 1px;
        text-align: center;
        margin-bottom: 40px;
    }

    /* Groupes de champs stylisés */
    .input-group-custom {
        background: #f8f9fa;
        padding: 20px;
        border-radius: 15px;
        margin-bottom: 20px;
        transition: all 0.3s ease;
        border: 1px solid transparent;
    }

    .input-group-custom:hover {
        background: #fff;
        border-color: var(--medical-blue);
        box-shadow: 0 5px 15px rgba(13, 110, 253, 0.1);
    }

    .label-title {
        font-weight: 700;
        color: #495057;
        display: block;
        margin-bottom: 10px;
    }

    /* APGAR interactif */
    .apgar-selector {
        display: flex;
        gap: 5px;
        flex-wrap: wrap;
    }

    .apgar-btn {
        width: 40px;
        height: 40px;
        border-radius: 8px;
        border: 2px solid #dee2e6;
        background: white;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        font-weight: bold;
        transition: 0.2s;
    }

    .apgar-btn:hover { border-color: var(--medical-blue); }
    .apgar-btn.active {
        background: var(--medical-blue);
        color: white;
        border-color: var(--medical-blue);
        transform: scale(1.1);
    }

    /* Badge de sexe */
    .gender-selector {
        display: flex;
        gap: 15px;
    }

    .gender-option {
        flex: 1;
        text-align: center;
        padding: 15px;
        border: 2px solid #dee2e6;
        border-radius: 12px;
        cursor: pointer;
        transition: 0.3s;
    }

    .gender-option input { display: none; }
    .gender-option.active-m { background: #e3f2fd; border-color: #2196f3; color: #1976d2; }
    .gender-option.active-f { background: #fce4ec; border-color: #f06292; color: #c2185b; }

    /* Animation */
    .fade-in { animation: fadeIn 0.5s ease-in; }
    @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

    @media print {
        .no-print { display: none !important; }
        .form-container { box-shadow: none; margin: 0; padding: 20px; }
    }
</style>

<div class="container-fluid pb-5">
    <div class="d-flex justify-content-between align-items-center py-3 px-4 bg-white border-bottom shadow-sm no-print sticky-top mb-3">
        <a href="<?= BASE_URL ?>patients/dossier/<?= $patient['id'] ?>" class="btn btn-outline-secondary rounded-pill btn-sm">
            <i class="bi bi-arrow-left"></i> Retour Dossier Mère
        </a>
        <div class="d-flex gap-2">
            <button class="btn btn-primary rounded-pill btn-sm" onclick="window.print()"><i class="bi bi-printer"></i> Imprimer</button>
            <button type="submit" form="formNN" class="btn btn-success rounded-pill btn-sm px-4 shadow"><i class="bi bi-check-lg"></i> Enregistrer les paramètres</button>
        </div>
    </div>

    <div class="form-container fade-in">
        <form id="formNN" action="<?= BASE_URL ?>formulaire/sauvegarder/parametres-nouveau-ne" method="POST">
            <input type="hidden" name="patient_mere_id" value="<?= $patient['id'] ?>">

            <div class="hospital-header">
                <div>
                    <img src="<?= BASE_URL ?>public/images/logo_ordre_malte.png" style="height: 50px;" alt="">
                    <p class="mb-0 small fw-bold mt-2">HÔPITAL SAINT JEAN DE MALTE - NJOMBÉ</p>
                </div>
                <div class="text-end">
                    <span class="badge bg-light text-dark border">Césarienne</span>
                </div>
            </div>

            <h3 class="form-title">Paramètres du Nouveau-Né</h3>

            <div class="row">
                <!-- Identité Mère -->
                <div class="col-md-12 mb-3">
                    <div class="input-group-custom" style="border-left: 4px solid #6c757d;">
                        <span class="label-title"><i class="bi bi-person-heart me-2"></i>Nom de la mère</span>
                        <h5 class="mb-0 text-primary fw-bold"><?= htmlspecialchars($patient['nom'].' '.$patient['prenom']) ?></h5>
                    </div>
                </div>

                <!-- Date et Heure -->
                <div class="col-md-6 mb-3">
                    <div class="input-group-custom">
                        <span class="label-title"><i class="bi bi-calendar-event me-2"></i>Date/heure de naissance</span>
                        <input type="datetime-local" name="date_heure" class="form-control border-0 bg-white" required value="<?= date('Y-m-d\TH:i') ?>">
                    </div>
                </div>

                <!-- Sexe -->
                <div class="col-md-6 mb-3">
                    <div class="input-group-custom">
                        <span class="label-title"><i class="bi bi-gender-ambiguous me-2"></i>Sexe du Nouveau-né</span>
                        <div class="gender-selector">
                            <label class="gender-option" id="labelM">
                                <input type="radio" name="sexe" value="M" onchange="updateGenderUI('M')"> <i class="bi bi-gender-male"></i> Masculin
                            </label>
                            <label class="gender-option" id="labelF">
                                <input type="radio" name="sexe" value="F" onchange="updateGenderUI('F')"> <i class="bi bi-gender-female"></i> Féminin
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Mesures Vitales -->
                <div class="col-md-4 mb-3">
                    <div class="input-group-custom text-center">
                        <span class="label-title">Poids (g)</span>
                        <input type="number" name="poids" class="form-control text-center fs-4 fw-bold border-0 bg-white" placeholder="0000">
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="input-group-custom text-center">
                        <span class="label-title">Taille (cm)</span>
                        <input type="number" name="taille" step="0.1" class="form-control text-center fs-4 fw-bold border-0 bg-white" placeholder="00">
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="input-group-custom text-center">
                        <span class="label-title text-danger">APGAR Score</span>
                        <input type="hidden" name="apgar" id="apgar_val" value="">
                        <div class="apgar-selector justify-content-center">
                            <?php for($i=0; $i<=10; $i++): ?>
                                <div class="apgar-btn" onclick="setApgar(<?= $i ?>)"><?= $i ?></div>
                            <?php endfor; ?>
                        </div>
                    </div>
                </div>

                <!-- Périmètres -->
                <div class="col-md-4 mb-3">
                    <div class="input-group-custom">
                        <span class="label-title text-center">P. Crânien (PC)</span>
                        <div class="input-group">
                            <input type="number" step="0.1" name="pc" class="form-control text-center border-0 bg-white">
                            <span class="input-group-text bg-white border-0">cm</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="input-group-custom">
                        <span class="label-title text-center">P. Thoracique (PT)</span>
                        <div class="input-group">
                            <input type="number" step="0.1" name="pt" class="form-control text-center border-0 bg-white">
                            <span class="input-group-text bg-white border-0">cm</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="input-group-custom">
                        <span class="label-title text-center">P. Brachial (PB)</span>
                        <div class="input-group">
                            <input type="number" step="0.1" name="pb" class="form-control text-center border-0 bg-white">
                            <span class="input-group-text bg-white border-0">cm</span>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
    // Dynamisme APGAR
    function setApgar(val) {
        document.getElementById('apgar_val').value = val;
        document.querySelectorAll('.apgar-btn').forEach(btn => {
            btn.classList.remove('active');
            if(btn.innerText == val) btn.classList.add('active');
        });
    }

    // Dynamisme Sexe
    function updateGenderUI(sexe) {
        const m = document.getElementById('labelM');
        const f = document.getElementById('labelF');
        m.classList.remove('active-m');
        f.classList.remove('active-f');

        if(sexe === 'M') m.classList.add('active-m');
        else f.classList.add('active-f');
    }
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>