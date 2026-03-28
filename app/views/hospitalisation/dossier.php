<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div class="container-fluid">
    <div class="row">
        <?php require_once __DIR__ . '/../layouts/sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">
                    <i class="bi bi-person-badge me-2"></i>
                    <?= htmlspecialchars($patient['nom'] . ' ' . $patient['prenom']) ?>
                    <small class="text-muted">- <?= $patient['service_nom'] ?> - Lit <?= $patient['lit_numero'] ?></small>
                </h1>
                <div class="btn-toolbar">
                    <a href="/hospitalisation" class="btn btn-outline-secondary me-2">
                        <i class="bi bi-arrow-left"></i> Retour
                    </a>
                    <button class="btn btn-danger" onclick="sortirPatient()">
                        <i class="bi bi-box-arrow-right"></i> Sortie
                    </button>
                </div>
            </div>

            <!-- Onglets -->
            <ul class="nav nav-tabs mb-4" id="dossierTabs">
                <li class="nav-item">
                    <a class="nav-link active" data-bs-toggle="tab" href="#traitements">
                        <i class="bi bi-capsule me-1"></i>Traitements
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="tab" href="#constantes">
                        <i class="bi bi-heart-pulse me-1"></i>Constantes
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="tab" href="#soins">
                        <i class="bi bi-bandaid me-1"></i>Soins
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="tab" href="#historique">
                        <i class="bi bi-clock-history me-1"></i>Historique
                    </a>
                </li>
            </ul>

            <div class="tab-content">
                <!-- TRAITEMENTS -->
                <div class="tab-pane fade show active" id="traitements">
                    <div class="row">
                        <div class="col-md-8">
                            <div class="card">
                                <div class="card-header d-flex justify-content-between">
                                    <h5>Traitements en cours</h5>
                                    <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modalNouveauTraitement">
                                        <i class="bi bi-plus"></i> Nouveau traitement
                                    </button>
                                </div>
                                <div class="card-body">
                                    <?php foreach($traitements as $traitement): ?>
                                    <div class="border rounded p-3 mb-3">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div>
                                                <h6 class="fw-bold"><?= htmlspecialchars($traitement['medicament_nom']) ?></h6>
                                                <p class="mb-1"><?= htmlspecialchars($traitement['posologie']) ?></p>
                                                <small class="text-muted">
                                                    <?= $traitement['voie_administration'] ?> -
                                                    <?= $traitement['frequence'] ?> -
                                                    <?= $traitement['heure_debut'] ?> à <?= $traitement['heure_fin'] ?>
                                                </small>
                                            </div>
                                            <div class="btn-group btn-group-sm">
                                                <button class="btn btn-success" onclick="administrer(<?= $traitement['id'] ?>)">
                                                    <i class="bi bi-check2"></i> Administrer
                                                </button>
                                                <button class="btn btn-warning" onclick="suspendre(<?= $traitement['id'] ?>)">
                                                    <i class="bi bi-pause"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-header">
                                    <h6>Dernières administrations</h6>
                                </div>
                                <div class="card-body">
                                    <div class="timeline">
                                        <!-- Charger via AJAX -->
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- CONSTANTES -->
                <div class="tab-pane fade" id="constantes">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-header">
                                    <h5>Saisir constantes</h5>
                                </div>
                                <div class="card-body">
                                    <form id="formConstantes">
                                        <input type="hidden" name="patient_id" value="<?= $patient['patient_id'] ?>">

                                        <div class="mb-3">
                                            <label class="form-label">Température (°C)</label>
                                            <input type="number" step="0.1" class="form-control" name="temperature">
                                        </div>

                                        <div class="row">
                                            <div class="col-6">
                                                <label class="form-label">TA Systolique</label>
                                                <input type="number" class="form-control" name="tension_systolique">
                                            </div>
                                            <div class="col-6">
                                                <label class="form-label">TA Diastolique</label>
                                                <input type="number" class="form-control" name="tension_diastolique">
                                            </div>
                                        </div>

                                        <div class="mb-3 mt-3">
                                            <label class="form-label">Fréquence cardiaque</label>
                                            <input type="number" class="form-control" name="frequence_cardiaque">
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">Saturation O2 (%)</label>
                                            <input type="number" class="form-control" name="saturation_o2">
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">Glycémie (g/L)</label>
                                            <input type="number" step="0.01" class="form-control" name="glycemie">
                                        </div>

                                        <button type="button" class="btn btn-primary w-100" onclick="sauvegarderConstantes()">
                                            <i class="bi bi-save"></i> Enregistrer
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-8">
                            <div class="card">
                                <div class="card-header">
                                    <h5>Évolution des constantes</h5>
                                </div>
                                <div class="card-body">
                                    <canvas id="constantesChart" height="300"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- SOINS -->
                <div class="tab-pane fade" id="soins">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between">
                            <h5>Soins planifiés</h5>
                            <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modalNouveauSoin">
                                <i class="bi bi-plus"></i> Planifier soin
                            </button>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Heure</th>
                                            <th>Type de soin</th>
                                            <th>Description</th>
                                            <th>Statut</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="soinsTableBody">
                                        <!-- Charger via AJAX -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- HISTORIQUE -->
                <div class="tab-pane fade" id="historique">
                    <div class="card">
                        <div class="card-header">
                            <h5>Historique médical</h5>
                        </div>
                        <div class="card-body">
                            <!-- Historique complet -->
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Modal Nouveau Traitement -->
<div class="modal fade" id="modalNouveauTraitement" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Nouveau Traitement</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="formNouveauTraitement">
                    <input type="hidden" name="patient_id" value="<?= $patient['patient_id'] ?>">

                    <div class="mb-3">
                        <label class="form-label">Médicament</label>
                        <select class="form-select" name="medicament_id" required>
                            <!-- Charger via AJAX -->
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Posologie</label>
                        <input type="text" class="form-control" name="posologie" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Voie d'administration</label>
                        <select class="form-select" name="voie_administration">
                            <option value="orale">Orale</option>
                            <option value="iv">Intraveineuse</option>
                            <option value="im">Intramusculaire</option>
                            <option value="sc">Sous-cutanée</option>
                        </select>
                    </div>

                    <div class="row">
                        <div class="col-6">
                            <label class="form-label">Heure début</label>
                            <input type="time" class="form-control" name="heure_debut">
                        </div>
                        <div class="col-6">
                            <label class="form-label">Heure fin</label>
                            <input type="time" class="form-control" name="heure_fin">
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-primary" onclick="sauvegarderTraitement()">Prescrire</button>
            </div>
        </div>
    </div>
</div>

<!--<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>-->
<script>
function administrer(prescriptionId) {
    const dose = prompt('Dose administrée:');
    if (dose) {
        fetch('/hospitalisation/administrer-traitement', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                prescription_id: prescriptionId,
                patient_id: <?= $patient['patient_id'] ?>,
                dose: dose
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Traitement administré');
                location.reload();
            }
        });
    }
}

function sauvegarderConstantes() {
    const form = document.getElementById('formConstantes');
    const formData = new FormData(form);
    const data = Object.fromEntries(formData);

    fetch('/hospitalisation/ajouter-constantes', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Constantes enregistrées');
            form.reset();
        }
    });
}

// Graphique des constantes
const ctx = document.getElementById('constantesChart').getContext('2d');
const constantesChart = new Chart(ctx, {
    type: 'line',
    data: {
        labels: <?= json_encode(array_map(fn($c) => date('d/m H:i', strtotime($c['date_mesure'])), array_reverse($constantes))) ?>,
        datasets: [{
            label: 'Température',
            data: <?= json_encode(array_map(fn($c) => $c['temperature'], array_reverse($constantes))) ?>,
            borderColor: 'rgb(255, 99, 132)',
            tension: 0.1
        }, {
            label: 'FC',
            data: <?= json_encode(array_map(fn($c) => $c['frequence_cardiaque'], array_reverse($constantes))) ?>,
            borderColor: 'rgb(54, 162, 235)',
            tension: 0.1
        }]
    },
    options: {
        responsive: true,
        scales: {
            y: {
                beginAtZero: false
            }
        }
    }
});
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>