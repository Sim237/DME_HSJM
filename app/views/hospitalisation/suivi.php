<?php
require_once __DIR__ . '/../layouts/header.php';
$dossier = $dossier ?? [];
$dernieres = $dernieres_constantes ?? [];
?>


<!-- Inclusion de Chart.js pour les graphiques -->
<!--<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>-->

<script src="<?= BASE_URL ?>public/js/chart.umd.js"></script>

<style>
/* Style pour les valeurs anormales */
.vitals-warning {
    background-color: #fffbeb !important;
    border-color: #f59e0b !important;
    animation: pulse-orange 2s infinite;
}

.vitals-critical {
    background-color: #fee2e2 !important;
    border-color: #ef4444 !important;
    animation: pulse-red 2s infinite;
}

@keyframes pulse-red {
    0% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.7); }
    70% { box-shadow: 0 0 0 10px rgba(239, 68, 68, 0); }
    100% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0); }
}

@keyframes pulse-orange {
    0% { box-shadow: 0 0 0 0 rgba(245, 158, 11, 0.7); }
    70% { box-shadow: 0 0 0 10px rgba(245, 158, 11, 0); }
    100% { box-shadow: 0 0 0 0 rgba(245, 158, 11, 0); }
}

</style>

<div class="container-fluid">
    <div class="row">
        <?php require_once __DIR__ . '/../layouts/sidebar.php'; ?>

        <main class="col-md-10 ms-sm-auto px-md-4 mb-5">

            <!-- En-tête Patient -->
            <div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom">
                <div>
                    <h1 class="h2"><i class="bi bi-heart-pulse"></i> Suivi Hospitalisation</h1>
                    <h5 class="text-primary">
                        <?= htmlspecialchars($dossier['nom'] . ' ' . $dossier['prenom']) ?>
                        <span class="text-muted text-small">| Dossier <?= htmlspecialchars($dossier['dossier_numero']) ?></span>
                    </h5>
                </div>
                <div class="text-end">
                    <span class="badge bg-success fs-6 mb-1">
    <?= htmlspecialchars($dossier['service_nom'] ?? 'Service non défini') ?>
                    </span><br>
                    <span class="badge bg-secondary">Lit <?= htmlspecialchars($dossier['lit_numero']) ?></span>
                </div>
            </div>

            <!-- BARRE DE STATUT (Dernières constantes) -->
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <div class="card bg-dark text-white h-100">
                        <div class="card-body d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-subtitle mb-2 text-muted">Température</h6>
                                <h2 class="card-title mb-0"><?= $dernieres['temperature'] ?? '--' ?>°C</h2>
                            </div>
                            <i class="bi bi-thermometer-half fs-1 text-danger"></i>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-dark text-white h-100">
                        <div class="card-body d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-subtitle mb-2 text-muted">Tension Artérielle</h6>
                                <!-- Dans app/views/hospitalisation/suivi.php -->
<h2 class="card-title mb-0">
    <?= (isset($dernieres['pression_arterielle_systolique']) && $dernieres['pression_arterielle_systolique'] > 0)
        ? $dernieres['pression_arterielle_systolique'] . '/' . $dernieres['pression_arterielle_diastolique']
        : '--/--' ?>
</h2>
                            </div>
                            <i class="bi bi-activity fs-1 text-info"></i>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-dark text-white h-100">
                        <div class="card-body d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-subtitle mb-2 text-muted">Fréquence Cardiaque</h6>
                                <h2 class="card-title mb-0"><?= $dernieres['frequence_cardiaque'] ?? '--' ?> <small class="fs-6">bpm</small></h2>
                            </div>
                            <i class="bi bi-heart-fill fs-1 text-danger"></i>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-primary h-100" style="border-style: dashed;">
                        <div class="card-body d-flex align-items-center justify-content-center cursor-pointer"
                             data-bs-toggle="modal" data-bs-target="#modalAddConstante" style="cursor: pointer;">
                            <div class="text-center text-primary">
                                <i class="bi bi-plus-circle fs-2"></i><br>
                                <span>Nouvelle Prise</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mb-4 no-print">
    <div class="col-12">
        <div class="card p-3 shadow-sm border-0 bg-light">
            <div class="d-flex gap-2">
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalAddConstante"><i class="bi bi-plus-circle"></i> Ajouter Constantes</button>
                <button class="btn btn-info text-white" data-bs-toggle="modal" data-bs-target="#modalAddSoin"><i class="bi bi-calendar-plus"></i> Ajouter Soin</button>
                <!-- Dans suivi.php -->
<a href="<?= BASE_URL ?>hospitalisation/observations-evolution/<?= htmlspecialchars($patient['id']) ?>"
   class="btn btn-dark rounded-pill px-4">
   <i class="bi bi-pencil-square"></i> Note d'évolution
</a>
            </div>
        </div>
    </div>
</div>

            <!-- GRAPHIQUES -->
            <div class="row g-3 mb-4">
                <!-- Graphe Température -->
                <div class="col-md-6">
                    <div class="card shadow-sm">
                        <div class="card-header bg-white d-flex justify-content-between">
                            <h6 class="mb-0 fw-bold text-danger"><i class="bi bi-thermometer-high"></i> Évolution Température</h6>
                        </div>
                        <div class="card-body">
                            <canvas id="chartTemp" height="200"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Graphe Tension -->
                <div class="col-md-6">
                    <div class="card shadow-sm">
                        <div class="card-header bg-white">
                            <h6 class="mb-0 fw-bold text-info"><i class="bi bi-activity"></i> Évolution Tension Artérielle</h6>
                        </div>
                        <div class="card-body">
                            <canvas id="chartTension" height="200"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- GESTION DES SOINS -->
            <div class="row">
                <div class="col-md-12">
                    <div class="card shadow-sm">
                        <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                            <h5 class="mb-0"><i class="bi bi-journal-medical"></i> Planning des Soins</h5>
                            <!-- Bouton dans la vue suivi.php -->
<a href="<?= BASE_URL ?>hospitalisation/planifier-soins/<?= htmlspecialchars($dossier['patient_id'] ?? $patient['id']) ?>"
   class="btn btn-primary btn-sm">
    <i class="bi bi-calendar-plus"></i> Planifier un Soin
</a>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Heure</th>
                                            <th>Type de Soin</th>
                                            <th>Description</th>
                                            <th>Statut</th>
                                            <th class="text-end">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if(empty($soins_du_jour) && empty($tous_les_soins)): ?>
                                            <tr><td colspan="5" class="text-center py-4 text-muted">Aucun soin planifié</td></tr>
                                        <?php else: ?>
                                            <?php foreach($tous_les_soins as $soin): ?>
                                            <tr class="<?= $soin['statut'] == 'REALISE' ? 'table-success' : '' ?>">
                                                <td>
                                                    <strong><?= date('H:i', strtotime($soin['date_prevue'])) ?></strong><br>
                                                    <small class="text-muted"><?= date('d/m', strtotime($soin['date_prevue'])) ?></small>
                                                </td>
                                                <td>
                                                    <span class="badge bg-info text-dark"><?= htmlspecialchars($soin['type_soin']) ?></span>
                                                </td>
                                                <td><?= htmlspecialchars($soin['description']) ?></td>
                                                <td>
                                                    <?php if($soin['statut'] == 'PLANIFIE'): ?>
                                                        <span class="badge bg-warning text-dark">À FAIRE</span>
                                                    <?php elseif($soin['statut'] == 'REALISE'): ?>
                                                        <span class="badge bg-success">FAIT</span>
                                                        <small class="d-block text-muted" style="font-size: 0.7em">le <?= date('d/m H:i', strtotime($soin['date_realisee'])) ?></small>
                                                    <?php else: ?>
                                                        <span class="badge bg-secondary"><?= $soin['statut'] ?></span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-end">
                                                    <?php if($soin['statut'] == 'PLANIFIE'): ?>
                                                    <button class="btn btn-sm btn-success" onclick="validerSoin(<?= $soin['id'] ?>)">
                                                        <i class="bi bi-check-lg"></i> Valider
                                                    </button>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </main>
    </div>
</div>

<!-- Modal Ajout Constantes -->
<div class="modal fade" id="modalAddConstante" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="<?= BASE_URL ?>hospitalisation/add-constantes" method="POST">
               <input type="hidden" name="admission_id" value="<?= htmlspecialchars($dossier['id'] ?? '0') ?>">
                <input type="hidden" name="patient_id" value="<?= htmlspecialchars($dossier['patient_id'] ?? '0') ?>">

                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">Nouvelle Prise de Constantes</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Température (°C)</label>
                            <input type="number" step="0.1" class="form-control" name="temperature" placeholder="37.0" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Pouls (bpm)</label>
                            <input type="number" class="form-control" name="frequence_cardiaque" placeholder="80">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Tension Systolique</label>
                            <input type="number" class="form-control" name="tension_sys" placeholder="120">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Tension Diastolique</label>
                            <input type="number" class="form-control" name="tension_dia" placeholder="80">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">SpO2 (%)</label>
                            <input type="number" class="form-control" name="spo2" placeholder="98">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Observations</label>
                            <textarea class="form-control" name="observations" rows="2"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Planification Soin -->
<div class="modal fade" id="modalAddSoin" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="<?= BASE_URL ?>hospitalisation/add-soin" method="POST">
                <input type="hidden" name="admission_id" value="<?= $dossier['id'] ?>">
                <input type="hidden" name="patient_id" value="">

                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title">Planifier un Soin</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Type de Soin</label>
                        <select class="form-select" name="type_soin" required>
                            <option value="Injection">Injection</option>
                            <option value="Perfusion">Perfusion</option>
                            <option value="Pansement">Pansement</option>
                            <option value="Prise de sang">Prise de sang</option>
                            <option value="Administration Médicament">Administration Médicament</option>
                            <option value="Surveillance">Surveillance</option>
                            <option value="Autre">Autre</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Date et Heure Prévue</label>
                        <input type="datetime-local" class="form-control" name="date_prevue" value="<?= date('Y-m-d\TH:i') ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description / Instructions</label>
                        <textarea class="form-control" name="description" rows="3" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-info text-white">Planifier</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- SCRIPT POUR LA  SURVEILLANCE AUTOMATIQUE -->

<script>
function surveillerConstantes() {
    // Récupérer les dernières valeurs depuis les cartes HTML
    const tempVal = parseFloat(document.querySelector('.vital-box.temp .vital-value').innerText);
    const spo2Val = parseFloat(document.querySelector('.vital-box.spo2 .vital-value')?.innerText || 100);
    const fcVal = parseInt(document.querySelector('.vital-box.pouls .vital-value').innerText);

    // Température > 38.5 = Critique, > 38 = Warning
    const tempBox = document.querySelector('.vital-box.temp');
    if (tempVal > 38.5) tempBox.classList.add('vitals-critical');
    else if (tempVal > 38) tempBox.classList.add('vitals-warning');

    // SpO2 < 94 = Warning, < 90 = Critique
    const spo2Box = document.querySelector('.vital-box.spo2');
    if (spo2Val < 90) spo2Box.classList.add('vitals-critical');
    else if (spo2Val < 94) spo2Box.classList.add('vitals-warning');

    // Pouls > 120 = Critique, > 100 = Warning
    const poulsBox = document.querySelector('.vital-box.pouls');
    if (fcVal > 120) poulsBox.classList.add('vitals-critical');
    else if (fcVal > 100) poulsBox.classList.add('vitals-warning');
}

// Lancer la surveillance dès le chargement
document.addEventListener('DOMContentLoaded', surveillerConstantes);
</script>

<script>
function preparerModalSoin(patientId, admissionId) {
    // Remplir les champs cachés du formulaire de la modale
    document.querySelector('#modalAddSoin input[name="patient_id"]').value = patientId;
    document.querySelector('#modalAddSoin input[name="admission_id"]').value = admissionId;

    // Ouvrir la modale manuellement
    const myModal = new bootstrap.Modal(document.getElementById('modalAddSoin'));
    myModal.show();
}
</script>

<!-- SCRIPT POUR LES GRAPHIQUES CHART.JS -->
<script>
// Préparation des données PHP pour JS
const historyData = <?= json_encode($constantes) ?>;

// Extraction des labels (Dates) et données
const labels = historyData.map(d => {
    const date = new Date(d.date_mesure);
    return date.toLocaleDateString('fr-FR', {hour: '2-digit', minute:'2-digit'});
});

const dataTemp = historyData.map(d => d.temperature);
const dataSys = historyData.map(d => d.tension_sys);
const dataDia = historyData.map(d => d.tension_dia);

// --- GRAPHIQUE TEMPERATURE ---
new Chart(document.getElementById('chartTemp'), {
    type: 'line',
    data: {
        labels: labels,
        datasets: [{
            label: 'Température (°C)',
            data: dataTemp,
            borderColor: '#dc3545', // Rouge
            backgroundColor: 'rgba(220, 53, 69, 0.1)',
            tension: 0.4,
            fill: true,
            pointRadius: 4
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: { y: { min: 35, max: 42 } }
    }
});

// --- GRAPHIQUE TENSION ---
new Chart(document.getElementById('chartTension'), {
    type: 'line',
    data: {
        labels: labels,
        datasets: [
            {
                label: 'Systolique',
                data: dataSys,
                borderColor: '#0dcaf0', // Cyan
                tension: 0.4
            },
            {
                label: 'Diastolique',
                data: dataDia,
                borderColor: '#0d6efd', // Bleu
                tension: 0.4
            }
        ]
    },
    options: {
        responsive: true
    }
});

// Fonction Validation Soin
function validerSoin(id) {
    if(confirm("Confirmer la réalisation de ce soin ?")) {
        const note = prompt("Observation éventuelle (facultatif) :");

        const formData = new FormData();
        formData.append('soin_id', id);
        formData.append('note', note);

        fetch('<?= BASE_URL ?>hospitalisation/valider-soin', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if(data.success) location.reload();
        });
    }
}

setInterval(() => {
    fetch(window.location.href)
        .then(response => response.text())
        .then(html => {
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            // Mise à jour silencieuse des cartes de constantes
            document.querySelector('.row.g-3').innerHTML = doc.querySelector('.row.g-3').innerHTML;
        });
}, 60000);
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>