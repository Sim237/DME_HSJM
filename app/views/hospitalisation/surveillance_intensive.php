<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<!-- Bibliothèques additionnelles pour le dynamisme -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>

<style>
    :root {
        --primary-blue: #0d6efd;
        --danger-red: #dc3545;
        --warning-orange: #fd7e14;
        --success-green: #198754;
        --dark-bg: #212529;
    }

    body { background-color: #f4f7f9; font-family: 'Inter', sans-serif; }

    .paper-sheet {
        background: white;
        width: 100%;
        max-width: 1500px;
        margin: 0 auto;
        padding: 40px;
        border-radius: 0;
        box-shadow: 0 15px 50px rgba(0,0,0,0.1);
        min-height: 29.7cm;
    }

    /* En-tête ultra-moderne */
    .hospital-brand-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 3px solid var(--dark-bg);
        padding-bottom: 20px;
        margin-bottom: 30px;
    }

    .title-box {
        text-align: center;
        border: 4px double var(--dark-bg);
        padding: 10px 40px;
        background: #fff;
    }

    .title-box h1 { font-weight: 900; margin: 0; letter-spacing: 3px; font-size: 1.8rem; }

    /* Dashboard Patient */
    .patient-dashboard {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        background: var(--dark-bg);
        color: white;
        padding: 20px;
        border-radius: 12px;
        margin-bottom: 30px;
    }

    .stat-item { border-left: 3px solid var(--primary-blue); padding-left: 15px; }
    .stat-label { font-size: 0.7rem; color: #adb5bd; text-transform: uppercase; font-weight: 700; }
    .stat-value { font-size: 1.1rem; font-weight: 600; display: block; }

    /* Tableau Interactif */
    .table-container {
        overflow-x: auto;
        border-radius: 8px;
        box-shadow: 0 0 0 1px #dee2e6;
    }

    .si-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.85rem;
    }

    .si-table th {
        background: #f8f9fa;
        color: var(--dark-bg);
        padding: 12px 8px;
        border: 1px solid #dee2e6;
        text-align: center;
        white-space: nowrap;
    }

    .si-table td { border: 1px solid #dee2e6; padding: 0; position: relative; }

    /* Style des inputs de saisie */
    .si-input {
        border: none;
        width: 100%;
        height: 45px;
        text-align: center;
        font-weight: 600;
        background: transparent;
        transition: all 0.2s;
    }

    .si-input:focus {
        background: #e7f1ff;
        outline: none;
        box-shadow: inset 0 0 0 2px var(--primary-blue);
    }

    /* Alertes de seuils critiques */
    .vitals-critical { background-color: #ffe5e5 !important; color: #b71c1c !important; animation: pulse-red 2s infinite; }
    .vitals-warning { background-color: #fff4e5 !important; color: #e65100 !important; }

    @keyframes pulse-red {
        0% { background-color: #ffe5e5; }
        50% { background-color: #ffcccc; }
        100% { background-color: #ffe5e5; }
    }

    /* Boutons Flottants */
    .action-fab {
        position: fixed;
        bottom: 30px;
        right: 30px;
        display: flex;
        flex-direction: column;
        gap: 10px;
        z-index: 1000;
    }

    .btn-circle {
        width: 55px;
        height: 55px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        transition: transform 0.2s;
    }

    .btn-circle:hover { transform: scale(1.1); }

    @media print {
        .no-print, .action-fab, .btn-circle { display: none !important; }
        .paper-sheet { box-shadow: none; padding: 0; margin: 0; width: 100%; }
        .patient-dashboard { background: white !important; color: black !important; border: 1px solid black; }
        .stat-value { color: black !important; }
    }
</style>

<div class="container-fluid pb-5">

    <!-- BARRE D'ACTIONS TOP -->
    <div class="d-flex justify-content-between align-items-center py-3 px-4 bg-white border-bottom shadow-sm no-print sticky-top mb-3">
        <a href="<?= BASE_URL ?>patients/dossier/<?= $patient['id'] ?>" class="btn btn-outline-dark btn-sm rounded-pill">
            <i class="bi bi-arrow-left"></i> Retour Dossier
        </a>
        <div class="d-flex gap-2">
          <button type="button" class="btn btn-dark btn-sm rounded-pill px-3" onclick="addRow()">
        <i class="bi bi-plus-circle me-1"></i> Ajouter une ligne
    </button>
            <button class="btn btn-primary btn-sm rounded-pill px-3" onclick="window.print()">
                <i class="bi bi-printer"></i> Version Papier
            </button>
            <button type="submit" form="formSI" class="btn btn-success btn-sm rounded-pill px-4">
                <i class="bi bi-cloud-upload"></i> Enregistrer tout
            </button>
        </div>
    </div>

    <div class="paper-sheet animate__animated animate__fadeIn">
        <form id="formSI" action="<?= BASE_URL ?>hospitalisation/save-si" method="POST">
            <input type="hidden" name="patient_id" value="<?= $patient['id'] ?>">

            <!-- HEADER -->
            <div class="hospital-brand-header">
                <div class="d-flex align-items-center">
                    <img src="<?= BASE_URL ?>public/images/logo_ordre_malte.png" style="height: 60px;" alt="Logo">
                    <div class="ms-3">
                        <div class="fw-bold text-primary">ORDRE DE MALTE</div>
                        <div class="small fw-bold">HÔPITAL SAINT-JEAN DE MALTE</div>
                    </div>
                </div>

                <div class="title-box">
                    <h1>FICHE DE SURVEILLANCE INTENSIVE</h1>
                </div>

                <div class="text-end small fw-bold">
                    NJOMBE - CAMEROUN<br>
                    SERVICES D'URGENCES / REANIMATION
                </div>
            </div>

            <!-- DASHBOARD PATIENT -->
            <div class="patient-dashboard shadow-sm">
                <div class="stat-item">
                    <span class="stat-label">Patient</span>
                    <span class="stat-value text-uppercase"><?= htmlspecialchars($patient['nom'].' '.$patient['prenom']) ?></span>
                </div>
                <div class="stat-item">
                    <span class="stat-label">Âge / Sexe</span>
                    <span class="stat-value"><?= $age ?> Ans / <?= $patient['sexe'] ?></span>
                </div>
                <div class="stat-item">
                    <span class="stat-label">ID Dossier</span>
                    <span class="stat-value"><?= $patient['dossier_numero'] ?></span>
                </div>
                <div class="stat-item">
                    <span class="stat-label">Diagnostic</span>
                    <input type="text" name="diag" class="bg-transparent border-0 text-white fw-bold p-0 w-100" placeholder="Cliquer pour saisir...">
                </div>
            </div>

            <!-- TABLEAU DYNAMIQUE -->
            <div class="table-container">
                <table class="si-table" id="siTable">
                    <thead>
                        <tr>
                            <th style="width: 80px;">DATE</th>
                            <th style="width: 70px;">HEURE</th>
                            <th style="width: 80px; background: #eef2ff;">T.A (S/D)</th>
                            <th style="width: 70px;">POULS</th>
                            <th style="width: 60px;">T°</th>
                            <th style="width: 60px;">RESP.</th>
                            <th style="width: 80px;">DIURÈSE</th>
                            <th style="width: 100px;">CONSCIENCE</th>
                            <th style="width: 90px;">ASPIRATION</th>
                            <th style="width: 100px;">SOINS / MEDS</th>
                            <th>OBSERVATIONS CLINIQUES</th>
                            <th style="width: 80px;">STAFF</th>
                            <th class="no-print" style="width: 40px;">Action</th>
                        </tr>
                    </thead>
                    <tbody id="siBody">
                        <!-- Ligne de saisie initiale -->
                        <tr class="si-row">
                            <td><input type="text" name="obs[date][]" class="si-input" value="<?= date('d/m') ?>"></td>
                            <td><input type="time" name="obs[heure][]" class="si-input text-primary" value="<?= date('H:i') ?>"></td>
                            <td><input type="text" name="obs[ta][]" class="si-input" placeholder="120/80" onchange="validateVitals(this, 'ta')"></td>
                            <td><input type="number" name="obs[pouls][]" class="si-input" onchange="validateVitals(this, 'pouls')"></td>
                            <td><input type="text" name="obs[temp][]" class="si-input" onchange="validateVitals(this, 'temp')"></td>
                            <td><input type="number" name="obs[resp][]" class="si-input"></td>
                            <td><input type="text" name="obs[diurese][]" class="si-input"></td>
                            <td>
                                <select name="obs[conscience][]" class="si-input">
                                    <option value="A">A (Alerte)</option>
                                    <option value="V">V (Verbal)</option>
                                    <option value="P">P (Pain)</option>
                                    <option value="U">U (Unresponsive)</option>
                                </select>
                            </td>
                            <td><input type="text" name="obs[asp][]" class="si-input"></td>
                            <td><input type="text" name="obs[soins][]" class="si-input text-start px-2"></td>
                            <td><input type="text" name="obs[obs][]" class="si-input text-start px-2"></td>
                            <td><input type="text" name="obs[staff][]" class="si-input small text-uppercase" value="<?= $_SESSION['user_initiales'] ?? '' ?>"></td>
                            <td class="no-print text-center">
                                <button type="button" class="btn btn-link btn-sm text-danger" onclick="removeRow(this)"><i class="bi bi-trash3-fill">Retirer</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Footer info -->
            <div class="mt-4 d-flex justify-content-between align-items-center">
                <div class="text-muted small italic">Système informatisé de surveillance - Hôpital Njombé</div>
                <div class="text-end">
                    <p class="mb-0 fw-bold border-top border-dark px-5">Signature Praticien</p>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- BOUTONS FLOTTANTS (FAB) -->
<div class="action-fab no-print">
    <button type="button" class="btn btn-dark btn-circle" onclick="addRow()" title="Ajouter une observation (Alt+N)">
        <i class="bi bi-plus-lg fs-4"></i>
    </button>
</div>

<script>
/**
 * Ajoute une nouvelle ligne de surveillance
 */
function addRow() {
    const body = document.getElementById('siBody');
    const rows = body.getElementsByClassName('si-row');
    const newRow = rows[0].cloneNode(true);

    // Reset les inputs
    const inputs = newRow.querySelectorAll('input');
    inputs.forEach(input => {
        input.classList.remove('vitals-critical', 'vitals-warning');
        if (input.name.includes('[heure]')) {
            const now = new Date();
            input.value = now.getHours().toString().padStart(2, '0') + ':' + now.getMinutes().toString().padStart(2, '0');
        } else if (!input.name.includes('[date]') && !input.name.includes('[staff]')) {
            input.value = '';
        }
    });

    body.appendChild(newRow);
    newRow.classList.add('animate__animated', 'animate__fadeInDown');
    newRow.querySelector('input[name="obs[ta][]"]').focus();
}

/**
 * Supprime une ligne
 */
function removeRow(btn) {
    const body = document.getElementById('siBody');
    if (body.rows.length > 1) {
        btn.closest('tr').remove();
    }
}

/**
 * Validation intelligente des constantes vitales
 */
function validateVitals(input, type) {
    const val = parseFloat(input.value);
    input.classList.remove('vitals-critical', 'vitals-warning');

    if (type === 'temp') {
        if (val >= 38.5 || val <= 35.5) input.classList.add('vitals-critical');
        else if (val >= 37.8) input.classList.add('vitals-warning');
    }

    if (type === 'pouls') {
        if (val >= 110 || val <= 50) input.classList.add('vitals-critical');
        else if (val >= 95) input.classList.add('vitals-warning');
    }

    if (type === 'ta') {
        // Détection simple d'hypertension (si systolique > 160)
        const sys = parseInt(input.value.split('/')[0]);
        if (sys >= 160 || sys <= 90) input.classList.add('vitals-critical');
    }
}

// Raccourcis clavier
document.addEventListener('keydown', function(e) {
    if (e.altKey && e.key === 'n') addRow();
});

// Auto-focus sur le premier champ vide
window.onload = () => {
    document.querySelector('input[name="obs[ta][]"]').focus();
}
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>