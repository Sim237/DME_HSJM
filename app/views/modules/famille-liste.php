<?php include __DIR__ . '/../layouts/header.php'; ?>
<?php include __DIR__ . '/../layouts/sidebar.php'; ?>

<div class="main-content">
    <!-- Dashboard Header -->
    <div class="dashboard-header">
        <div class="dashboard-title-section">
            <h2><i class="fas fa-heart"></i> Gestion Famille</h2>
            <p>Gérez les familles et contacts des patients hospitalisés</p>
        </div>
        <div class="dashboard-user-section">
            <div class="user-status-badge">
                En ligne
            </div>
            <div class="user-info-display">
                <i class="fas fa-user-circle"></i>
                <span><?= $_SESSION['user_prenom'] ?? 'Utilisateur' ?> <?= $_SESSION['user_nom'] ?? '' ?></span>
            </div>
        </div>
    </div>
    
    <div class="container-fluid p-4">
        <!-- Search and Filters -->
        <div class="card mb-4">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="search-container">
                            <i class="fas fa-search search-icon"></i>
                            <input type="text" 
                                   class="form-control search-input" 
                                   id="searchPatient"
                                   placeholder="Rechercher un patient...">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <select class="form-select" id="filterService">
                            <option value="">Tous les services</option>
                            <option value="medecine">Médecine générale</option>
                            <option value="chirurgie">Chirurgie</option>
                            <option value="pediatrie">Pédiatrie</option>
                            <option value="maternite">Maternité</option>
                            <option value="rea">Réanimation</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select class="form-select" id="filterStatut">
                            <option value="">Tous les statuts</option>
                            <option value="actif">Hospitalisés</option>
                            <option value="visite">Visites programmées</option>
                            <option value="urgent">Contacts urgents</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="stats-grid mb-4">
            <div class="stat-card-modern variant-primary">
                <div class="stat-number">15</div>
                <div class="stat-label">Patients hospitalisés</div>
                <div class="stat-detail">
                    <i class="fas fa-bed"></i>
                    Avec famille enregistrée
                </div>
                <i class="fas fa-hospital-user stat-icon"></i>
            </div>
            
            <div class="stat-card-modern variant-success">
                <div class="stat-number">42</div>
                <div class="stat-label">Contacts famille</div>
                <div class="stat-detail">
                    <i class="fas fa-users"></i>
                    Membres enregistrés
                </div>
                <i class="fas fa-address-book stat-icon"></i>
            </div>
            
            <div class="stat-card-modern variant-info">
                <div class="stat-number">8</div>
                <div class="stat-label">Visites aujourd'hui</div>
                <div class="stat-detail">
                    <i class="fas fa-calendar-check"></i>
                    Planifiées
                </div>
                <i class="fas fa-calendar-alt stat-icon"></i>
            </div>
            
            <div class="stat-card-modern variant-warning">
                <div class="stat-number">3</div>
                <div class="stat-label">Autorisations</div>
                <div class="stat-detail">
                    <i class="fas fa-clock"></i>
                    En attente
                </div>
                <i class="fas fa-user-check stat-icon"></i>
            </div>
        </div>

        <!-- Patients List -->
        <div class="activity-card">
            <div class="activity-header">
                <h5>
                    <i class="fas fa-list"></i>
                    Liste des Patients
                </h5>
            </div>
            <div class="activity-table-container">
                <table class="activity-table" id="patientsTable">
                    <thead>
                        <tr>
                            <th>Patient</th>
                            <th>Service</th>
                            <th>Chambre</th>
                            <th>Contacts Famille</th>
                            <th>Visites</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Sample Data - À remplacer par données dynamiques -->
                        <tr>
                            <td data-label="Patient">
                                <div class="patient-name">DUPONT Marie</div>
                                <small class="text-muted">N° 2026-00123</small>
                            </td>
                            <td data-label="Service">
                                <span class="badge badge-primary">Médecine</span>
                            </td>
                            <td data-label="Chambre">201-A</td>
                            <td data-label="Contacts Famille">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="fas fa-users text-primary"></i>
                                    <span class="fw-bold">3</span>
                                    <small class="text-muted">membres</small>
                                </div>
                            </td>
                            <td data-label="Visites">
                                <span class="status-badge status-planned">1 prévue</span>
                            </td>
                            <td data-label="Actions">
                                <div class="d-flex gap-2">
                                    <a href="<?= BASE_URL ?>modules/famille/1" 
                                       class="btn btn-sm btn-primary">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <button class="btn btn-sm btn-success" 
                                            onclick="planifierVisite(1)">
                                        <i class="fas fa-calendar-plus"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        
                        <tr>
                            <td data-label="Patient">
                                <div class="patient-name">BERNARD Paul</div>
                                <small class="text-muted">N° 2026-00124</small>
                            </td>
                            <td data-label="Service">
                                <span class="badge badge-warning">Chirurgie</span>
                            </td>
                            <td data-label="Chambre">305-B</td>
                            <td data-label="Contacts Famille">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="fas fa-users text-primary"></i>
                                    <span class="fw-bold">2</span>
                                    <small class="text-muted">membres</small>
                                </div>
                            </td>
                            <td data-label="Visites">
                                <span class="status-badge status-completed">2 aujourd'hui</span>
                            </td>
                            <td data-label="Actions">
                                <div class="d-flex gap-2">
                                    <a href="<?= BASE_URL ?>modules/famille/2" 
                                       class="btn btn-sm btn-primary">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <button class="btn btn-sm btn-success" 
                                            onclick="planifierVisite(2)">
                                        <i class="fas fa-calendar-plus"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        
                        <tr>
                            <td data-label="Patient">
                                <div class="patient-name">LAMBERT Sophie</div>
                                <small class="text-muted">N° 2026-00125</small>
                            </td>
                            <td data-label="Service">
                                <span class="badge badge-info">Pédiatrie</span>
                            </td>
                            <td data-label="Chambre">102-C</td>
                            <td data-label="Contacts Famille">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="fas fa-users text-primary"></i>
                                    <span class="fw-bold">4</span>
                                    <small class="text-muted">membres</small>
                                    <i class="fas fa-exclamation-triangle text-danger" 
                                       title="Contact urgence"></i>
                                </div>
                            </td>
                            <td data-label="Visites">
                                <span class="status-badge status-pending">Aucune</span>
                            </td>
                            <td data-label="Actions">
                                <div class="d-flex gap-2">
                                    <a href="<?= BASE_URL ?>modules/famille/3" 
                                       class="btn btn-sm btn-primary">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <button class="btn btn-sm btn-success" 
                                            onclick="planifierVisite(3)">
                                        <i class="fas fa-calendar-plus"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        
                        <tr>
                            <td data-label="Patient">
                                <div class="patient-name">MARTIN Jean</div>
                                <small class="text-muted">N° 2026-00126</small>
                            </td>
                            <td data-label="Service">
                                <span class="badge badge-danger">Réanimation</span>
                            </td>
                            <td data-label="Chambre">Réa-05</td>
                            <td data-label="Contacts Famille">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="fas fa-users text-primary"></i>
                                    <span class="fw-bold">5</span>
                                    <small class="text-muted">membres</small>
                                    <i class="fas fa-exclamation-triangle text-danger" 
                                       title="Contact urgence"></i>
                                </div>
                            </td>
                            <td data-label="Visites">
                                <span class="status-badge status-urgent">Restreintes</span>
                            </td>
                            <td data-label="Actions">
                                <div class="d-flex gap-2">
                                    <a href="<?= BASE_URL ?>modules/famille/4" 
                                       class="btn btn-sm btn-primary">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <button class="btn btn-sm btn-warning" 
                                            onclick="contactUrgence(4)">
                                        <i class="fas fa-phone"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        
                        <tr>
                            <td data-label="Patient">
                                <div class="patient-name">ROUSSEAU Claire</div>
                                <small class="text-muted">N° 2026-00127</small>
                            </td>
                            <td data-label="Service">
                                <span class="badge badge-success">Maternité</span>
                            </td>
                            <td data-label="Chambre">401-D</td>
                            <td data-label="Contacts Famille">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="fas fa-users text-primary"></i>
                                    <span class="fw-bold">2</span>
                                    <small class="text-muted">membres</small>
                                </div>
                            </td>
                            <td data-label="Visites">
                                <span class="status-badge status-in-progress">En cours</span>
                            </td>
                            <td data-label="Actions">
                                <div class="d-flex gap-2">
                                    <a href="<?= BASE_URL ?>modules/famille/5" 
                                       class="btn btn-sm btn-primary">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <button class="btn btn-sm btn-success" 
                                            onclick="planifierVisite(5)">
                                        <i class="fas fa-calendar-plus"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Planifier Visite -->
<div class="modal fade" id="planifierVisiteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-calendar-plus"></i>
                    Planifier une Visite
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="<?= BASE_URL ?>modules/visite/planifier">
                <div class="modal-body">
                    <input type="hidden" name="patient_id" id="visite_patient_id">
                    
                    <div class="form-group mb-3">
                        <label class="form-label">Nom du visiteur</label>
                        <input type="text" class="form-control" name="nom_visiteur" required>
                    </div>
                    
                    <div class="form-group mb-3">
                        <label class="form-label">Relation avec le patient</label>
                        <select class="form-select" name="relation" required>
                            <option value="">-- Sélectionner --</option>
                            <option value="conjoint">Conjoint(e)</option>
                            <option value="parent">Parent</option>
                            <option value="enfant">Enfant</option>
                            <option value="frere_soeur">Frère/Sœur</option>
                            <option value="ami">Ami(e)</option>
                            <option value="autre">Autre</option>
                        </select>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label class="form-label">Date</label>
                                <input type="date" class="form-control" name="date_visite" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label class="form-label">Heure</label>
                                <input type="time" class="form-control" name="heure_visite" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group mb-3">
                        <label class="form-label">Durée (minutes)</label>
                        <select class="form-select" name="duree_minutes">
                            <option value="15">15 minutes</option>
                            <option value="30" selected>30 minutes</option>
                            <option value="45">45 minutes</option>
                            <option value="60">1 heure</option>
                        </select>
                    </div>
                    
                    <div class="form-group mb-3">
                        <label class="form-label">Téléphone (optionnel)</label>
                        <input type="tel" class="form-control" name="telephone">
                    </div>
                    
                    <div class="form-group mb-3">
                        <label class="form-label">Notes</label>
                        <textarea class="form-control" name="notes" rows="3" 
                                  placeholder="Informations complémentaires..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        Annuler
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-check"></i>
                        Planifier
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Search functionality
document.getElementById('searchPatient')?.addEventListener('input', function(e) {
    const searchTerm = e.target.value.toLowerCase();
    const rows = document.querySelectorAll('#patientsTable tbody tr');
    
    rows.forEach(row => {
        const patientName = row.querySelector('.patient-name')?.textContent.toLowerCase() || '';
        const patientNum = row.querySelector('small')?.textContent.toLowerCase() || '';
        
        if (patientName.includes(searchTerm) || patientNum.includes(searchTerm)) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
});

// Planifier visite
function planifierVisite(patientId) {
    document.getElementById('visite_patient_id').value = patientId;
    const modal = new bootstrap.Modal(document.getElementById('planifierVisiteModal'));
    modal.show();
}

// Contact urgence
function contactUrgence(patientId) {
    if (confirm('Voulez-vous contacter la famille en urgence ?')) {
        // Redirection vers la page famille avec mode urgence
        window.location.href = '<?= BASE_URL ?>modules/famille/' + patientId + '?urgence=1';
    }
}

// Filters
document.getElementById('filterService')?.addEventListener('change', filterTable);
document.getElementById('filterStatut')?.addEventListener('change', filterTable);

function filterTable() {
    const serviceFilter = document.getElementById('filterService').value.toLowerCase();
    const statutFilter = document.getElementById('filterStatut').value.toLowerCase();
    const rows = document.querySelectorAll('#patientsTable tbody tr');
    
    rows.forEach(row => {
        const service = row.querySelector('.badge')?.textContent.toLowerCase() || '';
        const statut = row.querySelector('.status-badge')?.textContent.toLowerCase() || '';
        
        let showRow = true;
        
        if (serviceFilter && !service.includes(serviceFilter)) {
            showRow = false;
        }
        
        if (statutFilter) {
            if (statutFilter === 'actif' && !row.querySelector('.patient-name')) {
                showRow = false;
            } else if (statutFilter === 'visite' && !statut.includes('prévue') && !statut.includes('cours')) {
                showRow = false;
            } else if (statutFilter === 'urgent' && !row.querySelector('.fa-exclamation-triangle')) {
                showRow = false;
            }
        }
        
        row.style.display = showRow ? '' : 'none';
    });
}
</script>

<?php include __DIR__ . '/../layouts/footer.php'; ?>
