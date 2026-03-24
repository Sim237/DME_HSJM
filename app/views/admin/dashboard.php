<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

<style>
    :root { --admin-primary: #0f172a; --bg-soft: #f8fafc; --shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.04); }
    body { background-color: var(--bg-soft); font-family: 'Plus Jakarta Sans', sans-serif; color: #1e293b; }
    .sidebar { width: 280px; height: 100vh; position: fixed; left: 0; top: 0; }
    .main-admin { margin-left: 280px; min-height: 100vh; }
    .admin-header { background: white; padding: 30px 50px; border-bottom: 1px solid #e2e8f0; margin-bottom: 30px; }
    .kpi-card { background: white; border-radius: 24px; padding: 25px; border: 1px solid #f1f5f9; box-shadow: var(--shadow); transition: transform 0.3s; height: 100%; }
    .kpi-card:hover { transform: translateY(-5px); }
    .icon-box { width: 50px; height: 50px; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; }
    .monitor-card { background: #1e293b; color: white; border-radius: 24px; padding: 25px; }
    .cpu-bar { height: 8px; background: rgba(255,255,255,0.1); border-radius: 10px; overflow: hidden; margin-top: 10px; }
    .cpu-progress { height: 100%; background: #10b981; transition: width 1s ease; }
    .data-table-card { background: white; border-radius: 24px; padding: 25px; box-shadow: var(--shadow); border: 1px solid #f1f5f9; }
</style>

<div class="d-flex">
    <!-- Sidebar -->
    <?php require_once __DIR__ . '/../layouts/sidebar.php'; ?>

    <!-- Main -->
    <div class="main-admin flex-grow-1">
        <div class="admin-header d-flex justify-content-between align-items-center">
            <div>
                <h2 class="fw-800 mb-0">Dashboard <span class="text-primary">Administrateur</span></h2>
                <p class="text-muted mb-0">Données hospitalières en temps réel</p>
            </div>
            <div class="d-flex align-items-center gap-4">
                <div id="liveClock" class="fw-bold fs-5 text-dark">00:00:00</div>
                <a href="<?= BASE_URL ?>logout" class="btn btn-outline-danger rounded-pill px-4 fw-bold">Déconnexion</a>
            </div>
        </div>

        <div class="container-fluid px-5 pb-5">
            <!-- KPI GRID -->
            <div class="row g-4 mb-5">
                <div class="col-md-3">
                    <div class="kpi-card">
                        <div class="d-flex justify-content-between mb-3">
                            <div class="icon-box bg-primary bg-opacity-10 text-primary"><i class="bi bi-people-fill"></i></div>
                            <span class="badge bg-success-subtle text-success">+Live</span>
                        </div>
                        <h3 class="fw-800 mb-0"><?= $stats['total_patients'] ?? 0 ?></h3>
                        <small class="text-muted fw-bold text-uppercase">Patients enregistrés</small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="kpi-card">
                        <div class="d-flex justify-content-between mb-3">
                            <div class="icon-box bg-info bg-opacity-10 text-info"><i class="bi bi-hospital"></i></div>
                        </div>
                        <h3 class="fw-800 mb-0"><?= $stats['hosp_actuelles'] ?? 0 ?></h3>
                        <small class="text-muted fw-bold text-uppercase">Hospitalisations en cours</small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="kpi-card">
                        <div class="d-flex justify-content-between mb-3">
                            <div class="icon-box bg-success bg-opacity-10 text-success"><i class="bi bi-cash-stack"></i></div>
                        </div>
                        <h3 class="fw-800 mb-0"><?= number_format($stats['ca_du_mois'] ?? 0, 0, ',', ' ') ?> <small class="fs-6">FCFA</small></h3>
                        <small class="text-muted fw-bold text-uppercase">Chiffre d'Affaires (Mois)</small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="kpi-card">
                        <div class="d-flex justify-content-between mb-3">
                            <div class="icon-box bg-danger bg-opacity-10 text-danger"><i class="bi bi-exclamation-triangle"></i></div>
                        </div>
                        <h3 class="fw-800 mb-0"><?= $stats['alertes_stock'] ?? 0 ?></h3>
                        <small class="text-muted fw-bold text-uppercase">Alertes Stock Pharmacie</small>
                    </div>
                </div>
            </div>

            <div class="row g-4">
                <!-- MONITORING -->
                <div class="col-lg-4">
                    <div class="monitor-card h-100">
                        <h5 class="fw-bold mb-4 text-white-50"><i class="bi bi-cpu me-2"></i>État du Serveur</h5>
                        <div class="mb-4">
                            <div class="d-flex justify-content-between small fw-bold mb-1"><span>Charge CPU</span><span><?= $system_status['CPU']['value'] ?? 0 ?>%</span></div>
                            <div class="cpu-bar"><div class="cpu-progress" style="width: <?= $system_status['CPU']['value'] ?? 0 ?>%"></div></div>
                        </div>
                        <div class="mb-4">
                            <div class="d-flex justify-content-between small fw-bold mb-1"><span>Mémoire RAM</span><span><?= $system_status['MEMORY']['value'] ?? 0 ?> MB</span></div>
                            <div class="cpu-bar"><div class="cpu-progress bg-info" style="width: 25%"></div></div>
                        </div>
                        <div class="p-3 rounded-4 bg-white bg-opacity-10 mt-4 text-center">
                            <small class="d-block opacity-50 text-uppercase fw-bold" style="font-size: 0.6rem;">Statut Base de Données</small>
                            <span class="text-success fw-bold"><i class="bi bi-check-circle-fill"></i> CONNECTÉ</span>
                        </div>
                    </div>
                </div>

                <!-- ACTIVITÉ LOGS -->
                <div class="col-lg-8">
                    <div class="data-table-card">
                        <h5 class="fw-bold mb-4">Activités Récentes (Audit Trail)</h5>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr class="small text-muted"><th>HEURE</th><th>UTILISATEUR</th><th>ACTION</th><th>MODULE</th></tr>
                                </thead>
                                <tbody>
                                    <?php if(empty($recent_logs)): ?>
                                        <tr><td colspan="4" class="text-center py-4">Aucune activité enregistrée.</td></tr>
                                    <?php else: foreach($recent_logs as $log): ?>
                                        <tr>
                                            <td><small class="fw-bold"><?= date('H:i', strtotime($log['created_at'])) ?></small></td>
                                            <td><strong><?= htmlspecialchars($log['prenom'] . ' ' . $log['nom']) ?></strong></td>
                                            <td><span class="badge bg-light text-dark border"><?= $log['action'] ?></span></td>
                                            <td><small class="text-muted"><?= $log['table_name'] ?></small></td>
                                        </tr>
                                    <?php endforeach; endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function updateClock() { document.getElementById('liveClock').innerText = new Date().toLocaleTimeString('fr-FR'); }
    setInterval(updateClock, 1000); updateClock();
    // Auto-refresh pour le temps réel (toutes les 2 minutes)
    setTimeout(() => { location.reload(); }, 120000);
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>