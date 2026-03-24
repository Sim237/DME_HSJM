<?php
$title = "Réanimation - Monitoring";
include __DIR__ . '/../layouts/header.php';
?>

<div class="main-content">
    <div class="module-header reanimation">
        <div class="header-content">
            <div class="header-icon">
                <i class="fas fa-heartbeat"></i>
            </div>
            <div class="header-text">
                <h1>Réanimation</h1>
                <p>Monitoring temps réel des patients critiques</p>
            </div>
        </div>
        <div class="header-actions">
            <a href="<?php echo BASE_URL; ?>" class="back-btn">
                <i class="fas fa-arrow-left"></i> Retour au tableau de bord
            </a>
            <div class="status-indicators">
                <span class="status-item critique">
                    <i class="fas fa-exclamation-triangle"></i>
                    <?= count(array_filter($patients, fn($p) => $p['statut'] == 'CRITIQUE')) ?> Critiques
                </span>
                <span class="status-item stable">
                    <i class="fas fa-check-circle"></i>
                    <?= count(array_filter($patients, fn($p) => $p['statut'] == 'STABLE')) ?> Stables
                </span>
            </div>
            <button class="btn btn-light" onclick="toggleAutoRefresh()">
                <i class="fas fa-sync-alt" id="refreshIcon"></i> Auto-Refresh
            </button>
        </div>
    </div>

    <div class="row g-4">
        <?php if (empty($patients)): ?>
            <div class="col-12">
                <div class="empty-state-rea">
                    <i class="fas fa-bed"></i>
                    <h4>Aucun patient en réanimation</h4>
                    <p>Tous les lits sont libres</p>
                </div>
            </div>
        <?php else: ?>
            <?php foreach ($patients as $patient): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card patient-monitor <?= strtolower($patient['statut']) ?>">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <div class="patient-identity">
                                <h6 class="mb-0"><?= $patient['nom'] . ' ' . $patient['prenom'] ?></h6>
                                <small class="text-muted">Lit <?= $patient['lit_id'] ?></small>
                            </div>
                            <div class="status-badges">
                                <span class="badge bg-<?= $patient['statut'] == 'CRITIQUE' ? 'danger' : ($patient['statut'] == 'STABLE' ? 'success' : 'warning') ?>">
                                    <?= $patient['statut'] ?>
                                </span>
                                <span class="badge bg-secondary">
                                    <?= date_diff(date_create($patient['date_admission']), date_create('now'))->days ?>j
                                </span>
                            </div>
                        </div>
                    <div class="card-body">
                        <div class="vital-signs" id="vitals-<?= $patient['id'] ?>">
                            <div class="vital-item">
                                <div class="vital-icon heart">
                                    <i class="fas fa-heartbeat"></i>
                                </div>
                                <div class="vital-data">
                                    <span class="vital-value" id="fc-<?= $patient['id'] ?>">--</span>
                                    <span class="vital-unit">bpm</span>
                                </div>
                            </div>
                            
                            <div class="vital-item">
                                <div class="vital-icon pressure">
                                    <i class="fas fa-tachometer-alt"></i>
                                </div>
                                <div class="vital-data">
                                    <span class="vital-value" id="ta-<?= $patient['id'] ?>">--/--</span>
                                    <span class="vital-unit">mmHg</span>
                                </div>
                            </div>
                            
                            <div class="vital-item">
                                <div class="vital-icon oxygen">
                                    <i class="fas fa-lungs"></i>
                                </div>
                                <div class="vital-data">
                                    <span class="vital-value" id="spo2-<?= $patient['id'] ?>">--</span>
                                    <span class="vital-unit">%</span>
                                </div>
                            </div>
                            
                            <div class="vital-item">
                                <div class="vital-icon temp">
                                    <i class="fas fa-thermometer-half"></i>
                                </div>
                                <div class="vital-data">
                                    <span class="vital-value" id="temp-<?= $patient['id'] ?>">--</span>
                                    <span class="vital-unit">°C</span>
                                </div>
                            </div>
                            
                            <div class="vital-item">
                                <div class="vital-icon glasgow">
                                    <i class="fas fa-brain"></i>
                                </div>
                                <div class="vital-data">
                                    <span class="vital-value" id="glasgow-<?= $patient['id'] ?>">--</span>
                                    <span class="vital-unit">Glasgow</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mt-3">
                            <button class="btn btn-sm btn-primary w-100" onclick="ouvrirMonitoring(<?= $patient['id'] ?>)">
                                <i class="fas fa-chart-line"></i> Monitoring Détaillé
                            </button>
                        </div>
                        
                        <div class="alerts mt-2" id="alerts-<?= $patient['id'] ?>">
                            <!-- Alertes dynamiques -->
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Modal Saisie Données -->
<div class="modal fade" id="saisieModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5>Saisie des Constantes</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="saisieForm">
                <div class="modal-body">
                    <input type="hidden" name="patient_rea_id" id="patientReaId">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Fréquence Cardiaque</label>
                            <input type="number" name="frequence_cardiaque" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Tension Systolique</label>
                            <input type="number" name="tension_sys" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Tension Diastolique</label>
                            <input type="number" name="tension_dia" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Saturation O2</label>
                            <input type="number" name="saturation_o2" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Température</label>
                            <input type="number" step="0.1" name="temperature" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Fréq. Respiratoire</label>
                            <input type="number" name="frequence_respiratoire" class="form-control">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Score de Glasgow</label>
                            <input type="number" min="3" max="15" name="glasgow" class="form-control">
                        </div>
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

<style>
.module-header.reanimation {
    background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
    color: white;
    padding: 2rem;
    border-radius: 15px;
    margin-bottom: 2rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.header-content {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.header-icon {
    background: rgba(255,255,255,0.2);
    padding: 1rem;
    border-radius: 50%;
    font-size: 2rem;
}

.header-actions {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.status-indicators {
    display: flex;
    gap: 1rem;
}

.status-item {
    background: rgba(255,255,255,0.15);
    padding: 0.5rem 1rem;
    border-radius: 20px;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.9rem;
    backdrop-filter: blur(10px);
}

.status-item.critique {
    background: rgba(220, 53, 69, 0.3);
    border: 1px solid rgba(220, 53, 69, 0.5);
}

.status-item.stable {
    background: rgba(40, 167, 69, 0.3);
    border: 1px solid rgba(40, 167, 69, 0.5);
}

.patient-monitor {
    border: 2px solid #ddd;
    transition: all 0.3s;
}

.patient-monitor:hover {
    border-color: #007bff;
    box-shadow: 0 4px 15px rgba(0,123,255,0.2);
}

.vital-signs {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
}

.vital-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px;
    background: #f8f9fa;
    border-radius: 8px;
}

.vital-icon {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
}

.vital-icon.heart { background: #e74c3c; }
.vital-icon.pressure { background: #3498db; }
.vital-icon.oxygen { background: #2ecc71; }
.vital-icon.temp { background: #f39c12; }
.vital-icon.glasgow { background: #9b59b6; }

.vital-data {
    display: flex;
    flex-direction: column;
}

.vital-value {
    font-size: 1.2rem;
    font-weight: bold;
    color: #2c3e50;
}

.vital-unit {
    font-size: 0.8rem;
    color: #7f8c8d;
}

.alert-critique {
    background: #f8d7da;
    color: #721c24;
    padding: 5px 10px;
    border-radius: 4px;
    font-size: 0.8rem;
    margin-top: 5px;
}

.back-btn {
    background: rgba(255,255,255,0.2);
    color: white;
    padding: 0.75rem 1.5rem;
    border-radius: 25px;
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    transition: all 0.3s;
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255,255,255,0.3);
}

.back-btn:hover {
    background: rgba(255,255,255,0.3);
    transform: translateY(-2px);
    color: white;
}

.empty-state-rea {
    text-align: center;
    padding: 4rem 2rem;
    color: #6c757d;
    background: white;
    border-radius: 15px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.empty-state-rea i {
    font-size: 4rem;
    margin-bottom: 1rem;
    opacity: 0.3;
}

.empty-state-rea h4 {
    margin-bottom: 0.5rem;
}

.patient-monitor.critique {
    border-left: 4px solid #dc3545;
    box-shadow: 0 4px 15px rgba(220, 53, 69, 0.2);
}

.patient-monitor.stable {
    border-left: 4px solid #28a745;
    box-shadow: 0 4px 15px rgba(40, 167, 69, 0.2);
}

.patient-identity h6 {
    color: #2c3e50;
    font-weight: bold;
}

.status-badges {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.status-badges .badge {
    font-size: 0.7rem;
}
</style>

<script>
let autoRefresh = false;
let refreshInterval;

function toggleAutoRefresh() {
    autoRefresh = !autoRefresh;
    const icon = document.getElementById('refreshIcon');
    
    if (autoRefresh) {
        icon.classList.add('fa-spin');
        refreshInterval = setInterval(refreshAllData, 5000);
    } else {
        icon.classList.remove('fa-spin');
        clearInterval(refreshInterval);
    }
}

function refreshAllData() {
    <?php foreach ($patients as $patient): ?>
        refreshPatientData(<?= $patient['id'] ?>);
    <?php endforeach; ?>
}

function refreshPatientData(patientId) {
    fetch(`<?= BASE_URL ?>reanimation/donnees-temps-reel/${patientId}`)
    .then(response => response.json())
    .then(data => {
        if (data) {
            document.getElementById(`fc-${patientId}`).textContent = data.frequence_cardiaque || '--';
            document.getElementById(`ta-${patientId}`).textContent = 
                (data.tension_sys && data.tension_dia) ? `${data.tension_sys}/${data.tension_dia}` : '--/--';
            document.getElementById(`spo2-${patientId}`).textContent = data.saturation_o2 || '--';
            document.getElementById(`temp-${patientId}`).textContent = data.temperature || '--';
            document.getElementById(`glasgow-${patientId}`).textContent = data.glasgow || '--';
            
            // Vérifier les alertes
            checkAlerts(patientId, data);
        }
    });
}

function checkAlerts(patientId, data) {
    const alertsDiv = document.getElementById(`alerts-${patientId}`);
    let alerts = [];
    
    if (data.frequence_cardiaque < 50 || data.frequence_cardiaque > 120) {
        alerts.push('FC anormale');
    }
    if (data.saturation_o2 < 90) {
        alerts.push('SpO2 faible');
    }
    if (data.glasgow < 8) {
        alerts.push('Glasgow critique');
    }
    
    alertsDiv.innerHTML = alerts.map(alert => 
        `<div class="alert-critique">${alert}</div>`
    ).join('');
}

function ouvrirMonitoring(patientId) {
    window.open(`<?= BASE_URL ?>reanimation/monitoring/${patientId}`, '_blank');
}

// Démarrer le refresh automatique au chargement
document.addEventListener('DOMContentLoaded', function() {
    refreshAllData();
});
</script>

<?php include __DIR__ . '/../layouts/footer.php'; ?>