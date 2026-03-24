<?php include __DIR__ . '/../layouts/header.php'; ?>

<div class="main-content">
    <div class="container-fluid p-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="mb-1"><i class="fas fa-heartbeat text-danger me-2"></i>Surveillance à Distance</h2>
                <p class="text-muted mb-0">Suivi des paramètres vitaux des patients</p>
            </div>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addDataModal">
                <i class="fas fa-plus me-2"></i>Ajouter Données
            </button>
        </div>

        <!-- Tableau de bord surveillance -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <i class="fas fa-users fa-2x text-primary mb-2"></i>
                        <h4 class="text-primary">24</h4>
                        <p class="mb-0">Patients Surveillés</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <i class="fas fa-exclamation-triangle fa-2x text-warning mb-2"></i>
                        <h4 class="text-warning">3</h4>
                        <p class="mb-0">Alertes Actives</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <i class="fas fa-chart-line fa-2x text-success mb-2"></i>
                        <h4 class="text-success">156</h4>
                        <p class="mb-0">Mesures Aujourd'hui</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <i class="fas fa-clock fa-2x text-info mb-2"></i>
                        <h4 class="text-info">98%</h4>
                        <p class="mb-0">Conformité</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filtres -->
        <div class="card mb-4">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <select class="form-select" id="filter-patient">
                            <option value="">Tous les patients</option>
                            <option value="1">DUPONT Marie</option>
                            <option value="2">MARTIN Jean</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select class="form-select" id="filter-type">
                            <option value="">Tous les paramètres</option>
                            <option value="tension">Tension artérielle</option>
                            <option value="glycemie">Glycémie</option>
                            <option value="temperature">Température</option>
                            <option value="poids">Poids</option>
                            <option value="frequence_cardiaque">Fréquence cardiaque</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <input type="date" class="form-control" id="filter-date" value="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="col-md-3">
                        <button class="btn btn-primary w-100" onclick="filtrerDonnees()">
                            <i class="fas fa-search me-2"></i>Filtrer
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Données de surveillance -->
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-chart-area me-2"></i>Données de Surveillance</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover" id="surveillance-table">
                        <thead>
                            <tr>
                                <th>Patient</th>
                                <th>Paramètre</th>
                                <th>Valeur</th>
                                <th>Unité</th>
                                <th>Date/Heure</th>
                                <th>Statut</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Données exemple -->
                            <tr>
                                <td><strong>DUPONT Marie</strong></td>
                                <td><i class="fas fa-heartbeat text-danger me-1"></i>Tension</td>
                                <td class="fw-bold text-danger">160/95</td>
                                <td>mmHg</td>
                                <td><?= date('d/m/Y H:i') ?></td>
                                <td><span class="badge bg-danger">Alerte</span></td>
                                <td>
                                    <button class="btn btn-sm btn-primary" onclick="voirDetails(1)">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="btn btn-sm btn-warning" onclick="contacterPatient(1)">
                                        <i class="fas fa-phone"></i>
                                    </button>
                                </td>
                            </tr>
                            <tr>
                                <td><strong>MARTIN Jean</strong></td>
                                <td><i class="fas fa-tint text-primary me-1"></i>Glycémie</td>
                                <td class="fw-bold text-success">0.9</td>
                                <td>g/L</td>
                                <td><?= date('d/m/Y H:i', strtotime('-1 hour')) ?></td>
                                <td><span class="badge bg-success">Normal</span></td>
                                <td>
                                    <button class="btn btn-sm btn-primary" onclick="voirDetails(2)">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </td>
                            </tr>
                            <tr>
                                <td><strong>BERNARD Sophie</strong></td>
                                <td><i class="fas fa-thermometer-half text-warning me-1"></i>Température</td>
                                <td class="fw-bold text-warning">38.5</td>
                                <td>°C</td>
                                <td><?= date('d/m/Y H:i', strtotime('-30 minutes')) ?></td>
                                <td><span class="badge bg-warning">Attention</span></td>
                                <td>
                                    <button class="btn btn-sm btn-primary" onclick="voirDetails(3)">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="btn btn-sm btn-warning" onclick="contacterPatient(3)">
                                        <i class="fas fa-phone"></i>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal ajout données -->
<div class="modal fade" id="addDataModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Ajouter Données de Surveillance</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="add-data-form">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Patient</label>
                        <select name="patient_id" class="form-select" required>
                            <option value="">Sélectionner un patient</option>
                            <option value="1">DUPONT Marie</option>
                            <option value="2">MARTIN Jean</option>
                            <option value="3">BERNARD Sophie</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Type de donnée</label>
                        <select name="type_donnee" class="form-select" required onchange="updateUnite(this)">
                            <option value="">Sélectionner</option>
                            <option value="tension">Tension artérielle</option>
                            <option value="glycemie">Glycémie</option>
                            <option value="temperature">Température</option>
                            <option value="poids">Poids</option>
                            <option value="frequence_cardiaque">Fréquence cardiaque</option>
                            <option value="saturation">Saturation O2</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Valeur</label>
                        <input type="number" name="valeur" class="form-control" step="0.01" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Unité</label>
                        <input type="text" name="unite" class="form-control" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Date et heure</label>
                        <input type="datetime-local" name="date_mesure" class="form-control" 
                               value="<?= date('Y-m-d\TH:i') ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Notes (optionnel)</label>
                        <textarea name="notes" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Mettre à jour l'unité selon le type
function updateUnite(select) {
    const unites = {
        'tension': 'mmHg',
        'glycemie': 'g/L',
        'temperature': '°C',
        'poids': 'kg',
        'frequence_cardiaque': 'bpm',
        'saturation': '%'
    };
    
    document.querySelector('input[name="unite"]').value = unites[select.value] || '';
}

// Soumettre formulaire
document.getElementById('add-data-form').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    fetch('<?= BASE_URL ?>telemedecine/surveillance', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            $('#addDataModal').modal('hide');
            location.reload();
        } else {
            alert('Erreur lors de l\'enregistrement');
        }
    });
});

// Filtrer données
function filtrerDonnees() {
    // Logique de filtrage
    console.log('Filtrage des données...');
}

// Voir détails
function voirDetails(id) {
    // Ouvrir modal avec graphiques
    console.log('Voir détails patient:', id);
}

// Contacter patient
function contacterPatient(id) {
    // Démarrer consultation d'urgence
    if (confirm('Démarrer une consultation d\'urgence avec ce patient ?')) {
        window.location.href = '<?= BASE_URL ?>telemedecine/consultation/urgence/' + id;
    }
}

// Actualisation automatique toutes les 30 secondes
setInterval(function() {
    // Recharger les données de surveillance
    location.reload();
}, 30000);
</script>

<?php include __DIR__ . '/../layouts/sidebar.php'; ?>
<?php include __DIR__ . '/../layouts/footer.php'; ?>