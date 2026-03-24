<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<style>
    :root {
        --hsjm-blue: #0d6efd;
        --hsjm-gray: #f8f9fa;
        --hsjm-border: #dee2e6;
    }

    body { background-color: #f4f7f6; font-family: 'Segoe UI', system-ui, sans-serif; }

    /* Conteneur type papier A4 Paysage */
    .sheet-container {
        background: white;
        width: 100%;
        max-width: 1400px;
        margin: 20px auto;
        padding: 30px;
        border-radius: 15px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        position: relative;
        overflow: hidden;
    }

    /* Filigrane HSJM discret */
    .sheet-container::before {
        content: "HSJM";
        position: absolute;
        top: 50%; left: 50%;
        transform: translate(-50%, -50%) rotate(-30deg);
        font-size: 15rem;
        font-weight: 900;
        color: rgba(0,0,0,0.03);
        z-index: 0;
        pointer-events: none;
    }

    /* En-tête Patient Moderne */
    .patient-header-card {
        background: var(--hsjm-gray);
        border-radius: 12px;
        padding: 20px;
        margin-bottom: 25px;
        border-left: 5px solid var(--hsjm-blue);
        position: relative;
        z-index: 1;
    }

    .info-label { font-size: 0.75rem; color: #6c757d; text-transform: uppercase; font-weight: 700; }
    .info-value { font-size: 1rem; font-weight: 600; color: #212529; }

    /* Tableau Interactif */
    .table-responsive-custom {
        overflow-x: auto;
        border-radius: 8px;
        border: 1px solid var(--hsjm-border);
        background: white;
        position: relative;
        z-index: 1;
    }

    .surveillance-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
    }

    .surveillance-table th, .surveillance-table td {
        border-right: 1px solid var(--hsjm-border);
        border-bottom: 1px solid var(--hsjm-border);
        padding: 8px;
        min-width: 100px;
    }

    /* Colonne de gauche fixe */
    .label-col {
        position: sticky;
        left: 0;
        z-index: 10;
        background: #fdfdfd !important;
        min-width: 220px !important;
        font-weight: 600;
        font-size: 0.85rem;
        border-right: 2px solid var(--hsjm-blue) !important;
    }

    /* Groupes de couleurs pour les lignes */
    .row-group-treatment { background-color: #f0f7ff; }
    .row-group-vitals { background-color: #fff9f0; }
    .row-group-elimination { background-color: #f0fff4; }

    .category-divider {
        background: #e9ecef;
        font-weight: 800;
        font-size: 0.7rem;
        text-transform: uppercase;
        letter-spacing: 1px;
        color: #495057;
        text-align: center;
    }

    .form-input-table {
        border: 1px solid transparent;
        width: 100%;
        text-align: center;
        background: transparent;
        transition: all 0.2s;
        font-size: 0.9rem;
    }

    .form-input-table:hover { background: rgba(13, 110, 253, 0.05); border-bottom: 1px solid var(--hsjm-blue); }
    .form-input-table:focus { background: white; outline: none; border: 1px solid var(--hsjm-blue); border-radius: 4px; box-shadow: 0 0 5px rgba(13, 110, 253, 0.2); }

    /* Boutons */
    .btn-hsjm { border-radius: 8px; font-weight: 600; transition: all 0.3s; }
    .btn-add-col { background: #6f42c1; color: white; }
    .btn-add-col:hover { background: #59359a; color: white; transform: translateY(-2px); }

    @media print {
        .no-print { display: none !important; }
        .sheet-container { box-shadow: none; margin: 0; padding: 0; width: 100%; }
        .label-col { position: static; background: white !important; border-right: 1px solid black !important; }
        .form-input-table { border: none !important; }
    }
</style>

<div class="container-fluid pb-5">
    <!-- Barre d'outils supérieure -->
    <div class="d-flex justify-content-between align-items-center py-3 px-4 bg-white border-bottom shadow-sm no-print sticky-top mb-3">
        <div>
            <a href="<?= BASE_URL ?>patients/dossier/<?= $patient['id'] ?>" class="btn btn-outline-secondary btn-sm btn-hsjm">
                <i class="bi bi-arrow-left"></i> Retour au dossier
            </a>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-add-col btn-sm btn-hsjm" onclick="addColumn()">
                <i class="bi bi-clock-history"></i> Ajouter une Heure
            </button>
            <button class="btn btn-primary btn-sm btn-hsjm" onclick="window.print()">
                <i class="bi bi-printer"></i> Imprimer la fiche
            </button>
            <button type="submit" form="formSurveillance" class="btn btn-success btn-sm btn-hsjm px-4">
                <i class="bi bi-save"></i> Enregistrer les données
            </button>
        </div>
    </div>

    <div class="sheet-container">
        <form id="formSurveillance" action="<?= BASE_URL ?>hospitalisation/save-surveillance" method="POST">
            <input type="hidden" name="patient_id" value="<?= $patient['id'] ?>">

            <div class="text-center mb-4">
                <h4 class="fw-bold text-dark text-uppercase mb-1">Fiche de Soins et de Surveillance</h4>
                <div class="badge bg-primary">Hôpital Saint-Jean de Malte - Njombé</div>
            </div>

            <!-- SECTION PATIENT GRID -->
            <div class="patient-header-card">
                <div class="row g-3">
                    <div class="col-md-3">
                        <div class="info-label">Patient</div>
                        <div class="info-value"><?= htmlspecialchars($patient['nom'].' '.$patient['prenom']) ?></div>
                    </div>
                    <div class="col-md-2">
                        <div class="info-label">Âge / Sexe</div>
                        <div class="info-value"><?= $age ?> ans / <?= $patient['sexe'] ?></div>
                    </div>
                    <div class="col-md-3">
                        <div class="info-label">Diagnostics</div>
                        <input type="text" name="diagnostics" class="form-control form-control-sm border-0 bg-transparent fw-bold" placeholder="Saisir diagnostics...">
                    </div>
                    <div class="col-md-2">
                        <div class="info-label">Date d'Entrée</div>
                        <input type="date" name="date_entree" class="form-control form-control-sm border-0 bg-transparent fw-bold" value="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="col-md-2 text-end">
                        <div class="info-label">Séjour</div>
                        <div class="info-value"><span class="badge bg-dark">J+0</span></div>
                    </div>
                </div>
            </div>

            <!-- TABLEAU DE SURVEILLANCE -->
            <div class="table-responsive-custom">
                <table class="surveillance-table" id="tableSurveillance">
                    <thead>
                        <tr>
                            <th class="label-col">DATE / HEURE</th>
                            <th class="hour-col text-center bg-light">
                                <input type="text" name="data[date][]" class="form-input-table fw-bold" value="<?= date('d/m') ?>">
                                <input type="time" name="data[heure][]" class="form-input-table text-primary fw-bold" value="<?= date('H:i') ?>">
                            </th>
                        </tr>
                        <tr>
                            <th class="label-col">Personnel / Équipe</th>
                            <td class="text-center"><input type="text" name="data[staff][]" class="form-input-table small" placeholder="Initiales"></td>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- GROUPE : TRAITEMENTS & LIQUIDES -->
                        <tr class="category-divider"><td colspan="100%">Traitement & Apports</td></tr>
                        <tr class="row-group-treatment"><td class="label-col">Liquides / Gavage</td><td><input type="text" name="data[liquides][]" class="form-input-table"></td></tr>
                        <tr class="row-group-treatment"><td class="label-col">Transfusion</td><td><input type="text" name="data[transfu][]" class="form-input-table"></td></tr>
                        <tr class="row-group-treatment"><td class="label-col">Antibiotiques</td><td><input type="text" name="data[antibio][]" class="form-input-table"></td></tr>

                        <!-- GROUPE : CONSTANTES VITALES -->
                        <tr class="category-divider"><td colspan="100%">Surveillance Vitale</td></tr>
                        <tr class="row-group-vitals"><td class="label-col text-danger">Température (°C)</td><td><input type="text" name="data[temp][]" class="form-input-table fw-bold"></td></tr>
                        <tr class="row-group-vitals"><td class="label-col text-primary">Pouls / TA</td><td><input type="text" name="data[ta][]" class="form-input-table fw-bold"></td></tr>
                        <tr class="row-group-vitals"><td class="label-col">SaO2 (%) / O2</td><td><input type="text" name="data[sao2][]" class="form-input-table"></td></tr>
                        <tr class="row-group-vitals"><td class="label-col">Fréq. Resp / Lutte</td><td><input type="text" name="data[fr][]" class="form-input-table"></td></tr>

                        <!-- GROUPE : ÉLIMINATION & ÉTAT -->
                        <tr class="category-divider"><td colspan="100%">Élimination & État Clinique</td></tr>
                        <tr class="row-group-elimination"><td class="label-col">Diurèse / Drains</td><td><input type="text" name="data[diurese][]" class="form-input-table"></td></tr>
                        <tr class="row-group-elimination"><td class="label-col font-italic">Conscience / Douleur</td><td><input type="text" name="data[conscience][]" class="form-input-table"></td></tr>
                        <tr class="row-group-elimination"><td class="label-col">Glycémie (g/L)</td><td><input type="text" name="data[glycemie][]" class="form-input-table"></td></tr>

                        <tr class="category-divider"><td colspan="100%">Autres examens</td></tr>
                        <tr><td class="label-col">Observations / Divers</td><td><input type="text" name="data[notes][]" class="form-input-table"></td></tr>
                    </tbody>
                </table>
            </div>

            <div class="mt-4 d-flex justify-content-between align-items-center">
                <div class="small text-muted italic">HSJM v2.0 - Système de surveillance informatisé</div>
                <div class="fw-bold small">Signature Major du Service : _______________________</div>
            </div>
        </form>
    </div>
</div>

<script>
function addColumn() {
    const table = document.getElementById('tableSurveillance');
    const headerRows = table.querySelectorAll('thead tr');
    const bodyRows = table.querySelectorAll('tbody tr:not(.category-divider)');

    // 1. Ajouter les headers (Date/Heure/Staff)
    headerRows.forEach((row, index) => {
        const lastCell = row.cells[row.cells.length - 1];
        const newCell = lastCell.cloneNode(true);
        const inputs = newCell.querySelectorAll('input');

        inputs.forEach(input => {
            // Si c'est l'heure, mettre l'heure actuelle
            if(input.type === 'time') {
                const now = new Date();
                input.value = now.getHours().toString().padStart(2, '0') + ':' + now.getMinutes().toString().padStart(2, '0');
            } else {
                input.value = '';
            }
        });
        row.appendChild(newCell);
    });

    // 2. Ajouter les cellules de données dans le corps
    bodyRows.forEach(row => {
        const lastCell = row.cells[row.cells.length - 1];
        const newCell = lastCell.cloneNode(true);
        const input = newCell.querySelector('input');
        if (input) input.value = '';
        row.appendChild(newCell);
    });

    // Scroller vers la droite automatiquement
    const container = document.querySelector('.table-responsive-custom');
    container.scrollLeft = container.scrollWidth;
}
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>