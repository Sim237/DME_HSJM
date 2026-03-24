<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div class="container-fluid">
    <div class="row">
        <?php require_once __DIR__ . '/../layouts/sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2"><i class="bi bi-activity me-2"></i>Laboratoire - Temps Réel</h1>
                <div class="btn-toolbar">
                    <span class="badge bg-success me-2" id="statusConnection">🟢 Connecté</span>
                    <button class="btn btn-primary btn-sm" onclick="ajouterResultat()">
                        <i class="bi bi-plus"></i> Nouveau Résultat
                    </button>
                </div>
            </div>

            <!-- Statistiques temps réel -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card bg-primary text-white">
                        <div class="card-body text-center">
                            <h3 id="resultatsAujourdhui">-</h3>
                            <p class="mb-0">Résultats Aujourd'hui</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-warning text-white">
                        <div class="card-body text-center">
                            <h3 id="anormauxAujourdhui">-</h3>
                            <p class="mb-0">Anormaux</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-info text-white">
                        <div class="card-body text-center">
                            <h3 id="enAttente">-</h3>
                            <p class="mb-0">En Attente</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-danger text-white">
                        <div class="card-body text-center">
                            <h3 id="alertesCritiques">-</h3>
                            <p class="mb-0">Alertes Critiques</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Onglets -->
            <ul class="nav nav-tabs mb-4">
                <li class="nav-item">
                    <a class="nav-link active" data-bs-toggle="tab" href="#resultats">
                        <i class="bi bi-list-ul"></i> Résultats Récents
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="tab" href="#alertes">
                        <i class="bi bi-exclamation-triangle"></i> Alertes
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="tab" href="#patient">
                        <i class="bi bi-person"></i> Par Patient
                    </a>
                </li>
            </ul>

            <div class="tab-content">
                <!-- RÉSULTATS RÉCENTS -->
                <div class="tab-pane fade show active" id="resultats">
                    <div class="card">
                        <div class="card-header">
                            <h5>Résultats en Temps Réel</h5>
                            <small class="text-muted">Mise à jour automatique toutes les 5 secondes</small>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Patient</th>
                                            <th>Examen</th>
                                            <th>Résultat</th>
                                            <th>Statut</th>
                                            <th>Tendance</th>
                                            <th>Date</th>
                                        </tr>
                                    </thead>
                                    <tbody id="resultatsTableBody">
                                        <!-- Chargé via API -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ALERTES -->
                <div class="tab-pane fade" id="alertes">
                    <div class="card">
                        <div class="card-header">
                            <h5>Alertes Critiques</h5>
                        </div>
                        <div class="card-body">
                            <div id="alertesContainer">
                                <!-- Chargé via API -->
                            </div>
                        </div>
                    </div>
                </div>

                <!-- PAR PATIENT -->
                <div class="tab-pane fade" id="patient">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-header">
                                    <h6>Rechercher Patient</h6>
                                </div>
                                <div class="card-body">
                                    <form id="formRecherchePatient">
                                        <div class="mb-3">
                                            <label class="form-label">ID Patient</label>
                                            <input type="number" class="form-control" name="patient_id" required>
                                        </div>
                                        <button type="button" class="btn btn-primary" onclick="rechercherPatient()">
                                            Rechercher
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-8">
                            <div class="card">
                                <div class="card-header">
                                    <h6>Résultats Patient</h6>
                                </div>
                                <div class="card-body">
                                    <div id="resultatsPatient">
                                        <p class="text-muted">Sélectionnez un patient</p>
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

<!-- Modal Nouveau Résultat -->
<div class="modal fade" id="modalNouveauResultat" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Nouveau Résultat</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="formNouveauResultat">
                    <div class="mb-3">
                        <label class="form-label">Patient ID</label>
                        <input type="number" class="form-control" name="patient_id" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Examen ID</label>
                        <input type="number" class="form-control" name="examen_id" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Valeur</label>
                        <input type="text" class="form-control" name="valeur" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Unité</label>
                        <input type="text" class="form-control" name="unite" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Observations</label>
                        <textarea class="form-control" name="observations" rows="2"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-primary" onclick="sauvegarderResultat()">Enregistrer</button>
            </div>
        </div>
    </div>
</div>

<script>
let eventSource = null;

function initTempsReel() {
    // Connexion Server-Sent Events
    eventSource = new EventSource('/dme_hospital/api/laboratoire/stream');
    
    eventSource.onmessage = function(event) {
        const data = JSON.parse(event.data);
        updateInterface(data);
    };
    
    eventSource.onerror = function() {
        document.getElementById('statusConnection').innerHTML = '🔴 Déconnecté';
        document.getElementById('statusConnection').className = 'badge bg-danger me-2';
    };
    
    eventSource.onopen = function() {
        document.getElementById('statusConnection').innerHTML = '🟢 Connecté';
        document.getElementById('statusConnection').className = 'badge bg-success me-2';
    };
}

function updateInterface(data) {
    // Mettre à jour statistiques
    updateStatistiques();
    
    // Mettre à jour tableau résultats
    updateTableauResultats(data.resultats);
    
    // Mettre à jour alertes
    updateAlertes(data.alertes);
}

function updateStatistiques() {
    fetch('/dme_hospital/api/laboratoire/statistiques')
        .then(response => response.json())
        .then(data => {
            document.getElementById('resultatsAujourdhui').textContent = data.resultats_aujourd_hui || 0;
            document.getElementById('anormauxAujourdhui').textContent = data.anormaux_aujourd_hui || 0;
            document.getElementById('enAttente').textContent = data.en_attente || 0;
        });
}

function updateTableauResultats(resultats) {
    const tbody = document.getElementById('resultatsTableBody');
    
    if (!resultats || resultats.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center">Aucun résultat</td></tr>';
        return;
    }
    
    tbody.innerHTML = resultats.map(r => `
        <tr class="${r.critique === 'CRITIQUE' ? 'table-danger' : (r.anormal ? 'table-warning' : '')}">
            <td><strong>${r.nom} ${r.prenom}</strong></td>
            <td>${r.examen_nom}</td>
            <td>
                ${r.valeur} ${r.unite}
                ${r.anormal ? '<span class="badge bg-warning ms-1">Anormal</span>' : ''}
            </td>
            <td>
                <span class="badge bg-${getStatutColor(r.critique)}">${r.critique}</span>
            </td>
            <td>
                <span class="badge bg-${getTendanceColor(r.tendance)}">${getTendanceIcon(r.tendance)} ${r.tendance}</span>
            </td>
            <td>${new Date(r.date_resultat).toLocaleString()}</td>
        </tr>
    `).join('');
}

function updateAlertes(alertes) {
    const container = document.getElementById('alertesContainer');
    document.getElementById('alertesCritiques').textContent = alertes?.length || 0;
    
    if (!alertes || alertes.length === 0) {
        container.innerHTML = '<div class="alert alert-success">Aucune alerte critique</div>';
        return;
    }
    
    container.innerHTML = alertes.map(a => `
        <div class="alert alert-${getAlerteColor(a.niveau_alerte)} d-flex justify-content-between">
            <div>
                <strong>${a.nom} ${a.prenom}</strong> - ${a.examen}<br>
                <small>Valeur: ${a.valeur} ${a.unite} (Normal: ${a.valeur_normale_min}-${a.valeur_normale_max})</small>
            </div>
            <small>${new Date(a.date_resultat).toLocaleString()}</small>
        </div>
    `).join('');
}

function ajouterResultat() {
    new bootstrap.Modal(document.getElementById('modalNouveauResultat')).show();
}

function sauvegarderResultat() {
    const form = document.getElementById('formNouveauResultat');
    const formData = new FormData(form);
    const data = Object.fromEntries(formData);
    
    fetch('/dme_hospital/api/laboratoire/resultats', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            bootstrap.Modal.getInstance(document.getElementById('modalNouveauResultat')).hide();
            form.reset();
            if (data.anormal) {
                alert('⚠️ Résultat anormal détecté - Notification envoyée');
            }
        } else {
            alert('Erreur: ' + data.error);
        }
    });
}

function rechercherPatient() {
    const patientId = document.querySelector('[name="patient_id"]').value;
    
    fetch(`/dme_hospital/api/laboratoire/patient/${patientId}`)
        .then(response => response.json())
        .then(data => {
            const container = document.getElementById('resultatsPatient');
            
            if (data.error) {
                container.innerHTML = `<div class="alert alert-danger">${data.error}</div>`;
                return;
            }
            
            let html = `<h6>Résultats récents (${data.resultats_recents.length})</h6>`;
            html += '<div class="table-responsive"><table class="table table-sm">';
            html += '<thead><tr><th>Examen</th><th>Résultat</th><th>Date</th></tr></thead><tbody>';
            
            data.resultats_recents.forEach(r => {
                html += `<tr class="${r.anormal ? 'table-warning' : ''}">
                    <td>${r.examen_nom}</td>
                    <td>${r.valeur} ${r.unite}</td>
                    <td>${new Date(r.date_resultat).toLocaleString()}</td>
                </tr>`;
            });
            
            html += '</tbody></table></div>';
            container.innerHTML = html;
        });
}

function getStatutColor(critique) {
    switch(critique) {
        case 'CRITIQUE': return 'danger';
        case 'ANORMAL': return 'warning';
        default: return 'success';
    }
}

function getTendanceColor(tendance) {
    switch(tendance) {
        case 'HAUSSE': return 'danger';
        case 'BAISSE': return 'warning';
        default: return 'secondary';
    }
}

function getTendanceIcon(tendance) {
    switch(tendance) {
        case 'HAUSSE': return '↗️';
        case 'BAISSE': return '↘️';
        default: return '➡️';
    }
}

function getAlerteColor(niveau) {
    switch(niveau) {
        case 'CRITIQUE': return 'danger';
        case 'ANORMAL': return 'warning';
        default: return 'info';
    }
}

// Initialisation
document.addEventListener('DOMContentLoaded', function() {
    initTempsReel();
    updateStatistiques();
});

// Nettoyage à la fermeture
window.addEventListener('beforeunload', function() {
    if (eventSource) {
        eventSource.close();
    }
});
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>