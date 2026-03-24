<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div class="container-fluid">
    <div class="row">
        <?php require_once __DIR__ . '/../layouts/sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2"><i class="bi bi-robot me-2"></i>Automatisation Hospitalière</h1>
                <div class="btn-toolbar">
                    <button class="btn btn-primary" onclick="executerTachesAuto()">
                        <i class="bi bi-play-circle"></i> Exécuter Tâches
                    </button>
                </div>
            </div>

            <!-- Statut Automatisation -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card bg-success text-white">
                        <div class="card-body text-center">
                            <h3 id="rappelsActifs">-</h3>
                            <p class="mb-0">Rappels Actifs</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-info text-white">
                        <div class="card-body text-center">
                            <h3 id="scoresCalcules">-</h3>
                            <p class="mb-0">Scores Calculés</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-warning text-white">
                        <div class="card-body text-center">
                            <h3 id="alertesPredictives">-</h3>
                            <p class="mb-0">Alertes Prédictives</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-danger text-white">
                        <div class="card-body text-center">
                            <h3 id="planningsGeneres">-</h3>
                            <p class="mb-0">Plannings Générés</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Onglets -->
            <ul class="nav nav-tabs mb-4">
                <li class="nav-item">
                    <a class="nav-link active" data-bs-toggle="tab" href="#rappels">
                        <i class="bi bi-bell"></i> Rappels Automatiques
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="tab" href="#scores">
                        <i class="bi bi-calculator"></i> Scores de Gravité
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="tab" href="#planning">
                        <i class="bi bi-calendar3"></i> Planning Optimisé
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="tab" href="#ia">
                        <i class="bi bi-cpu"></i> IA Prédictive
                    </a>
                </li>
            </ul>

            <div class="tab-content">
                <!-- RAPPELS -->
                <div class="tab-pane fade show active" id="rappels">
                    <div class="card">
                        <div class="card-header">
                            <h5>Rappels Automatiques en Temps Réel</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <h6>Traitements à Administrer</h6>
                                    <div id="rappelsTraitements" class="list-group">
                                        <!-- Chargé via AJAX -->
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <h6>Constantes à Prendre</h6>
                                    <div id="rappelsConstantes" class="list-group">
                                        <!-- Chargé via AJAX -->
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- SCORES -->
                <div class="tab-pane fade" id="scores">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-header">
                                    <h6>Calculateur de Scores</h6>
                                </div>
                                <div class="card-body">
                                    <form id="formScore">
                                        <div class="mb-3">
                                            <label class="form-label">Patient ID</label>
                                            <input type="number" class="form-control" name="patient_id" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Type de Score</label>
                                            <select class="form-select" name="type_score">
                                                <option value="NEWS">NEWS (Early Warning Score)</option>
                                                <option value="GLASGOW">Glasgow Coma Scale</option>
                                                <option value="CHARLSON">Charlson Comorbidity</option>
                                            </select>
                                        </div>
                                        <button type="button" class="btn btn-primary" onclick="calculerScore()">
                                            Calculer Score
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-8">
                            <div class="card">
                                <div class="card-header">
                                    <h6>Résultat du Score</h6>
                                </div>
                                <div class="card-body">
                                    <div id="resultatScore">
                                        <p class="text-muted">Sélectionnez un patient et calculez un score</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- PLANNING -->
                <div class="tab-pane fade" id="planning">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between">
                            <h5>Planning Infirmier Optimisé</h5>
                            <button class="btn btn-success btn-sm" onclick="genererPlanning()">
                                <i class="bi bi-gear"></i> Générer Planning
                            </button>
                        </div>
                        <div class="card-body">
                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <label class="form-label">Service</label>
                                    <select id="serviceSelect" class="form-select">
                                        <option value="1">Médecine Interne</option>
                                        <option value="2">Chirurgie</option>
                                        <option value="3">Pédiatrie</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Date</label>
                                    <input type="date" id="dateSelect" class="form-control" value="<?= date('Y-m-d') ?>">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Équilibrage</label>
                                    <span id="equilibrageScore" class="badge bg-secondary">-</span>
                                </div>
                            </div>
                            <div id="planningResult">
                                <!-- Planning généré -->
                            </div>
                        </div>
                    </div>
                </div>

                <!-- IA PRÉDICTIVE -->
                <div class="tab-pane fade" id="ia">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h6>Analyse Prédictive Patient</h6>
                                </div>
                                <div class="card-body">
                                    <form id="formAnalyse">
                                        <div class="mb-3">
                                            <label class="form-label">Patient ID</label>
                                            <input type="number" class="form-control" name="patient_id" required>
                                        </div>
                                        <button type="button" class="btn btn-primary" onclick="analyserPatient()">
                                            <i class="bi bi-cpu"></i> Analyser
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h6>Alertes Prédictives</h6>
                                </div>
                                <div class="card-body">
                                    <div id="alertesIA">
                                        <p class="text-muted">Aucune analyse en cours</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
function executerTachesAuto() {
    fetch('/dme_hospital/automatisation/executer')
        .then(response => response.json())
        .then(data => {
            document.getElementById('rappelsActifs').textContent = 
                (data.rappels_traitements?.length || 0) + (data.rappels_constantes?.length || 0);
            document.getElementById('scoresCalcules').textContent = data.scores_calcules || 0;
            document.getElementById('alertesPredictives').textContent = data.alertes_predictives || 0;
        })
        .catch(error => console.error('Erreur:', error));
}

function calculerScore() {
    const form = document.getElementById('formScore');
    const formData = new FormData(form);
    const params = new URLSearchParams(formData);
    
    fetch(`/dme_hospital/automatisation/calculer-score?${params}`)
        .then(response => response.json())
        .then(data => {
            const html = `
                <div class="alert alert-${getScoreColor(data.score)} mb-3">
                    <h4>Score: ${data.score}</h4>
                    <p><strong>Niveau:</strong> ${data.niveau}</p>
                    <p><strong>Action:</strong> ${data.action}</p>
                </div>
            `;
            document.getElementById('resultatScore').innerHTML = html;
        })
        .catch(error => {
            console.error('Erreur:', error);
            document.getElementById('resultatScore').innerHTML = '<div class="alert alert-danger">Erreur lors du calcul</div>';
        });
}

function genererPlanning() {
    const service = document.getElementById('serviceSelect').value;
    const date = document.getElementById('dateSelect').value;
    
    fetch(`/dme_hospital/automatisation/planning?service_id=${service}&date=${date}&infirmieres=Inf1,Inf2,Inf3`)
        .then(response => response.json())
        .then(data => {
            document.getElementById('equilibrageScore').textContent = data.equilibrage?.equilibre || '-';
            
            let html = '<div class="row">';
            Object.entries(data.repartition || {}).forEach(([infirmiere, patients]) => {
                html += `
                    <div class="col-md-4">
                        <h6>${infirmiere}</h6>
                        <div class="list-group">
                            ${patients.map(p => `<div class="list-group-item">Patient ${p}</div>`).join('')}
                        </div>
                    </div>
                `;
            });
            html += '</div>';
            document.getElementById('planningResult').innerHTML = html;
        })
        .catch(error => {
            console.error('Erreur:', error);
            document.getElementById('planningResult').innerHTML = '<div class="alert alert-danger">Erreur lors de la génération</div>';
        });
}

function analyserPatient() {
    const form = document.getElementById('formAnalyse');
    const patientId = form.patient_id.value;
    
    fetch(`/dme_hospital/automatisation/analyser?patient_id=${patientId}`)
        .then(response => response.json())
        .then(data => {
            let html = '';
            
            if (data.tendances?.length) {
                html += '<h6>Tendances Détectées:</h6>';
                data.tendances.forEach(t => {
                    html += `<div class="alert alert-${t.niveau.toLowerCase()}">${t.message} (${t.probabilite}%)</div>`;
                });
            }
            
            if (data.duree_sejour) {
                html += `<h6>Durée Séjour Estimée:</h6>`;
                html += `<p>${data.duree_sejour.duree_estimee} jours</p>`;
            }
            
            document.getElementById('alertesIA').innerHTML = html || '<p>Aucune alerte</p>';
        })
        .catch(error => {
            console.error('Erreur:', error);
            document.getElementById('alertesIA').innerHTML = '<div class="alert alert-danger">Erreur lors de l\'analyse</div>';
        });
}

function getScoreColor(score) {
    if (score >= 7) return 'danger';
    if (score >= 5) return 'warning';
    if (score >= 3) return 'info';
    return 'success';
}

setInterval(executerTachesAuto, 30000);
document.addEventListener('DOMContentLoaded', executerTachesAuto);
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>