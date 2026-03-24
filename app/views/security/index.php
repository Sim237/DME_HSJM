<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div class="container-fluid">
    <div class="row">
        <?php require_once __DIR__ . '/../layouts/sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2"><i class="bi bi-shield-check"></i> Sécurité & Conformité</h1>
                <div class="btn-group">
                    <button class="btn btn-warning" onclick="createBackup()">
                        <i class="bi bi-hdd"></i> Sauvegarde
                    </button>
                    <button class="btn btn-info" onclick="exportAudit()">
                        <i class="bi bi-download"></i> Export Audit
                    </button>
                </div>
            </div>

            <!-- Alertes Sécurité -->
            <div id="securityAlerts"></div>

            <!-- KPIs Sécurité -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card bg-success text-white">
                        <div class="card-body">
                            <h4 id="successfulLogins">0</h4>
                            <p class="mb-0">Connexions Réussies</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-danger text-white">
                        <div class="card-body">
                            <h4 id="failedLogins">0</h4>
                            <p class="mb-0">Tentatives Échouées</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-info text-white">
                        <div class="card-body">
                            <h4 id="lastBackup">-</h4>
                            <p class="mb-0">Dernière Sauvegarde</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-warning text-white">
                        <div class="card-body">
                            <h4 id="suspiciousActivities">0</h4>
                            <p class="mb-0">Activités Suspectes</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Onglets -->
            <ul class="nav nav-tabs" id="securityTabs">
                <li class="nav-item">
                    <a class="nav-link active" data-bs-toggle="tab" href="#auditTab">
                        <i class="bi bi-list-check"></i> Audit Trail
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="tab" href="#backupTab">
                        <i class="bi bi-hdd-stack"></i> Sauvegardes
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="tab" href="#encryptionTab">
                        <i class="bi bi-lock"></i> Chiffrement
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="tab" href="#twoFactorTab">
                        <i class="bi bi-shield-lock"></i> 2FA
                    </a>
                </li>
            </ul>

            <div class="tab-content mt-3">
                <!-- Audit Trail -->
                <div class="tab-pane fade show active" id="auditTab">
                    <div class="card">
                        <div class="card-header">
                            <h5>Journal d'Audit</h5>
                            <div class="row">
                                <div class="col-md-2">
                                    <select class="form-select form-select-sm" id="filterAction">
                                        <option value="">Toutes actions</option>
                                        <option value="CREATE">Création</option>
                                        <option value="UPDATE">Modification</option>
                                        <option value="DELETE">Suppression</option>
                                        <option value="LOGIN">Connexion</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <input type="date" class="form-control form-control-sm" id="filterDate">
                                </div>
                                <div class="col-md-2">
                                    <button class="btn btn-primary btn-sm" onclick="loadAuditLogs()">Filtrer</button>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped table-sm">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Utilisateur</th>
                                            <th>Action</th>
                                            <th>Table</th>
                                            <th>IP</th>
                                            <th>Détails</th>
                                        </tr>
                                    </thead>
                                    <tbody id="auditTableBody"></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sauvegardes -->
                <div class="tab-pane fade" id="backupTab">
                    <div class="card">
                        <div class="card-header">
                            <h5>Gestion des Sauvegardes</h5>
                            <div class="btn-group">
                                <button class="btn btn-primary btn-sm" onclick="createBackup('full')">
                                    Sauvegarde Complète
                                </button>
                                <button class="btn btn-secondary btn-sm" onclick="createBackup('incremental')">
                                    Sauvegarde Incrémentale
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Type</th>
                                            <th>Taille</th>
                                            <th>Statut</th>
                                            <th>Durée</th>
                                        </tr>
                                    </thead>
                                    <tbody id="backupTableBody"></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Chiffrement -->
                <div class="tab-pane fade" id="encryptionTab">
                    <div class="card">
                        <div class="card-header">
                            <h5>Gestion du Chiffrement</h5>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle"></i>
                                Les données sensibles sont automatiquement chiffrées avec AES-256-CBC
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <h6>Intégrité des Données</h6>
                                    <div id="encryptionStatus">Vérification en cours...</div>
                                </div>
                                <div class="col-md-6">
                                    <h6>Actions</h6>
                                    <button class="btn btn-warning" onclick="rotateEncryptionKey()">
                                        Rotation de Clé
                                    </button>
                                    <button class="btn btn-info" onclick="checkIntegrity()">
                                        Vérifier Intégrité
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Double Authentification -->
                <div class="tab-pane fade" id="twoFactorTab">
                    <div class="card">
                        <div class="card-header">
                            <h5>Double Authentification (2FA)</h5>
                        </div>
                        <div class="card-body">
                            <div id="twoFactorStatus">
                                <div class="alert alert-warning">
                                    <i class="bi bi-shield-exclamation"></i>
                                    La double authentification n'est pas activée
                                </div>
                                <button class="btn btn-success" onclick="enable2FA()">
                                    Activer 2FA
                                </button>
                            </div>
                            
                            <div id="twoFactorSetup" style="display: none;">
                                <div class="row">
                                    <div class="col-md-6">
                                        <h6>1. Scanner le QR Code</h6>
                                        <img id="qrCode" src="" alt="QR Code" class="img-fluid">
                                    </div>
                                    <div class="col-md-6">
                                        <h6>2. Entrer le code de vérification</h6>
                                        <input type="text" class="form-control" id="verificationCode" placeholder="000000">
                                        <button class="btn btn-primary mt-2" onclick="verify2FA()">
                                            Vérifier
                                        </button>
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
document.addEventListener('DOMContentLoaded', function() {
    loadSecurityDashboard();
    loadAuditLogs();
    loadBackupHistory();
});

function loadSecurityDashboard() {
    fetch(`${BASE_URL}security/dashboard`)
        .then(response => response.json())
        .then(data => {
            // Mettre à jour les KPIs
            const successful = data.login_attempts.filter(a => a.success).length;
            const failed = data.login_attempts.filter(a => !a.success).length;
            
            document.getElementById('successfulLogins').textContent = successful;
            document.getElementById('failedLogins').textContent = failed;
            document.getElementById('suspiciousActivities').textContent = data.suspicious_activities.length;
            
            if (data.backup_stats.last_backup) {
                document.getElementById('lastBackup').textContent = 
                    new Date(data.backup_stats.last_backup).toLocaleDateString();
            }
            
            // Afficher les alertes
            displaySecurityAlerts(data);
        });
}

function loadAuditLogs() {
    const filters = {
        action: document.getElementById('filterAction')?.value || '',
        date_from: document.getElementById('filterDate')?.value || ''
    };
    
    const params = new URLSearchParams(filters);
    
    fetch(`${BASE_URL}security/audit-logs?${params}`)
        .then(response => response.json())
        .then(logs => {
            const tbody = document.getElementById('auditTableBody');
            tbody.innerHTML = logs.map(log => `
                <tr>
                    <td>${new Date(log.created_at).toLocaleString()}</td>
                    <td>${log.nom || 'Système'} ${log.prenom || ''}</td>
                    <td><span class="badge bg-primary">${log.action}</span></td>
                    <td>${log.table_name}</td>
                    <td>${log.ip_address}</td>
                    <td><small>${log.new_values ? JSON.stringify(JSON.parse(log.new_values)).substring(0, 50) + '...' : '-'}</small></td>
                </tr>
            `).join('');
        });
}

function loadBackupHistory() {
    fetch(`${BASE_URL}security/backup-history`)
        .then(response => response.json())
        .then(backups => {
            const tbody = document.getElementById('backupTableBody');
            tbody.innerHTML = backups.map(backup => `
                <tr>
                    <td>${new Date(backup.start_time).toLocaleString()}</td>
                    <td><span class="badge bg-info">${backup.backup_type}</span></td>
                    <td>${backup.file_size ? (backup.file_size / 1024 / 1024).toFixed(2) + ' MB' : '-'}</td>
                    <td><span class="badge bg-${backup.status === 'completed' ? 'success' : 'danger'}">${backup.status}</span></td>
                    <td>${backup.end_time ? Math.round((new Date(backup.end_time) - new Date(backup.start_time)) / 1000) + 's' : '-'}</td>
                </tr>
            `).join('');
        });
}

function createBackup(type = 'full') {
    const formData = new FormData();
    formData.append('type', type);
    
    fetch(`${BASE_URL}security/create-backup`, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Sauvegarde créée avec succès');
            loadBackupHistory();
        } else {
            alert('Erreur: ' + data.error);
        }
    });
}

function enable2FA() {
    fetch(`${BASE_URL}security/enable-2fa`, {
        method: 'POST'
    })
    .then(response => response.json())
    .then(data => {
        document.getElementById('qrCode').src = data.qr_code;
        document.getElementById('twoFactorSetup').style.display = 'block';
        document.getElementById('twoFactorStatus').style.display = 'none';
    });
}

function verify2FA() {
    const code = document.getElementById('verificationCode').value;
    const formData = new FormData();
    formData.append('code', code);
    
    fetch(`${BASE_URL}security/verify-2fa`, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('2FA activé avec succès');
            location.reload();
        } else {
            alert('Code incorrect');
        }
    });
}

function exportAudit() {
    window.open(`${BASE_URL}security/export-audit`, '_blank');
}

function displaySecurityAlerts(data) {
    const container = document.getElementById('securityAlerts');
    let alerts = '';
    
    if (data.suspicious_activities.length > 0) {
        alerts += `<div class="alert alert-danger">
            <i class="bi bi-exclamation-triangle"></i>
            ${data.suspicious_activities.length} activité(s) suspecte(s) détectée(s)
        </div>`;
    }
    
    if (data.encryption_integrity.length > 0) {
        alerts += `<div class="alert alert-warning">
            <i class="bi bi-shield-exclamation"></i>
            ${data.encryption_integrity.length} problème(s) d'intégrité détecté(s)
        </div>`;
    }
    
    container.innerHTML = alerts;
}
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>