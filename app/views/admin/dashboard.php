<?php
require_once __DIR__ . '/../layouts/header.php';
$adminPage = 'dashboard';
require_once __DIR__ . '/partials/admin_nav.php';
?>

        <!-- TOPBAR -->
        <div class="admin-topbar">
            <div class="admin-topbar-title">
                Dashboard Administrateur
                <small>Vue d'ensemble du système hospitalier</small>
            </div>
            <div class="d-flex align-items-center gap-3">
                <div id="liveClock" class="fw-bold" style="font-size:1.1rem; color:#e2e8f0; font-variant-numeric: tabular-nums;">00:00:00</div>
                <a href="<?= BASE_URL ?>logout" class="btn btn-sm btn-outline-danger rounded-pill px-3">
                    <i class="bi bi-box-arrow-right me-1"></i>Déconnexion
                </a>
            </div>
        </div>

        <div class="admin-page-content">

            <!-- KPI GRID -->
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm rounded-4 h-100">
                        <div class="card-body p-4">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div style="width:46px;height:46px;border-radius:14px;background:rgba(59,130,246,.12);display:flex;align-items:center;justify-content:center;font-size:1.4rem;color:#3b82f6;">
                                    <i class="bi bi-people-fill"></i>
                                </div>
                                <span class="badge bg-success bg-opacity-10 text-success fw-bold" style="font-size:.65rem;">LIVE</span>
                            </div>
                            <div class="fs-3 fw-bold mb-0"><?= $stats['total_patients'] ?? 0 ?></div>
                            <div class="text-muted small fw-semibold text-uppercase" style="letter-spacing:.05em;font-size:.7rem;">Patients enregistrés</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm rounded-4 h-100">
                        <div class="card-body p-4">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div style="width:46px;height:46px;border-radius:14px;background:rgba(14,165,233,.12);display:flex;align-items:center;justify-content:center;font-size:1.4rem;color:#0ea5e9;">
                                    <i class="bi bi-hospital"></i>
                                </div>
                            </div>
                            <div class="fs-3 fw-bold mb-0"><?= $stats['hosp_actuelles'] ?? 0 ?></div>
                            <div class="text-muted small fw-semibold text-uppercase" style="letter-spacing:.05em;font-size:.7rem;">Hospitalisations en cours</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm rounded-4 h-100">
                        <div class="card-body p-4">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div style="width:46px;height:46px;border-radius:14px;background:rgba(16,185,129,.12);display:flex;align-items:center;justify-content:center;font-size:1.4rem;color:#10b981;">
                                    <i class="bi bi-cash-stack"></i>
                                </div>
                            </div>
                            <div class="fs-3 fw-bold mb-0"><?= number_format($stats['ca_du_mois'] ?? 0, 0, ',', ' ') ?> <span class="fs-6 fw-normal text-muted">FCFA</span></div>
                            <div class="text-muted small fw-semibold text-uppercase" style="letter-spacing:.05em;font-size:.7rem;">Chiffre d'affaires (mois)</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm rounded-4 h-100">
                        <div class="card-body p-4">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div style="width:46px;height:46px;border-radius:14px;background:rgba(239,68,68,.12);display:flex;align-items:center;justify-content:center;font-size:1.4rem;color:#ef4444;">
                                    <i class="bi bi-exclamation-triangle-fill"></i>
                                </div>
                            </div>
                            <div class="fs-3 fw-bold mb-0"><?= $stats['alertes_stock'] ?? 0 ?></div>
                            <div class="text-muted small fw-semibold text-uppercase" style="letter-spacing:.05em;font-size:.7rem;">Alertes stock pharmacie</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-4">
                <!-- MONITORING SERVEUR -->
                <div class="col-lg-4">
                    <div class="card border-0 shadow-sm rounded-4 h-100" style="background:#1e293b; color:#e2e8f0;">
                        <div class="card-body p-4">
                            <h6 class="fw-bold mb-4" style="color:#94a3b8; text-transform:uppercase; font-size:.72rem; letter-spacing:.1em;">
                                <i class="bi bi-cpu me-2"></i>État du Serveur
                            </h6>
                            <div class="mb-4">
                                <div class="d-flex justify-content-between small fw-bold mb-2">
                                    <span>Charge CPU</span>
                                    <span><?= $system_status['CPU']['value'] ?? 0 ?>%</span>
                                </div>
                                <div style="height:8px;background:rgba(255,255,255,.1);border-radius:10px;overflow:hidden;">
                                    <div style="height:100%;width:<?= $system_status['CPU']['value'] ?? 0 ?>%;background:#10b981;border-radius:10px;transition:width 1s;"></div>
                                </div>
                            </div>
                            <div class="mb-4">
                                <div class="d-flex justify-content-between small fw-bold mb-2">
                                    <span>Mémoire RAM</span>
                                    <span><?= $system_status['MEMORY']['value'] ?? 0 ?> MB</span>
                                </div>
                                <div style="height:8px;background:rgba(255,255,255,.1);border-radius:10px;overflow:hidden;">
                                    <div style="height:100%;width:25%;background:#0ea5e9;border-radius:10px;"></div>
                                </div>
                            </div>
                            <div class="p-3 rounded-4 text-center mt-4" style="background:rgba(255,255,255,.06);">
                                <div class="small text-uppercase fw-bold mb-1" style="font-size:.6rem;opacity:.5;">Statut Base de Données</div>
                                <span class="text-success fw-bold"><i class="bi bi-check-circle-fill me-1"></i>CONNECTÉ</span>
                            </div>
                            <!-- Quick links -->
                            <div class="mt-4 d-flex flex-column gap-2">
                                <a href="<?= BASE_URL ?>admin/services" class="btn btn-sm rounded-3 text-start fw-bold" style="background:rgba(255,255,255,.06);color:#cbd5e1;font-size:.78rem;">
                                    <i class="bi bi-building me-2 text-info"></i>Gérer les Services
                                </a>
                                <a href="<?= BASE_URL ?>admin/lits" class="btn btn-sm rounded-3 text-start fw-bold" style="background:rgba(255,255,255,.06);color:#cbd5e1;font-size:.78rem;">
                                    <i class="bi bi-hospital me-2 text-success"></i>Chambres &amp; Lits
                                </a>
                                <a href="<?= BASE_URL ?>admin/permissions" class="btn btn-sm rounded-3 text-start fw-bold" style="background:rgba(255,255,255,.06);color:#cbd5e1;font-size:.78rem;">
                                    <i class="bi bi-shield-lock me-2 text-warning"></i>Permissions &amp; Rôles
                                </a>
                                <a href="<?= BASE_URL ?>utilisateurs" class="btn btn-sm rounded-3 text-start fw-bold" style="background:rgba(255,255,255,.06);color:#cbd5e1;font-size:.78rem;">
                                    <i class="bi bi-people me-2 text-primary"></i>Utilisateurs
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ACTIVITÉ RÉCENTE -->
                <div class="col-lg-8">
                    <div class="card border-0 shadow-sm rounded-4 h-100">
                        <div class="card-body p-4">
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <h6 class="fw-bold mb-0">Activités Récentes <span class="text-muted fw-normal">(Audit Trail)</span></h6>
                                <a href="<?= BASE_URL ?>admin/logs" class="btn btn-sm btn-light rounded-pill px-3" style="font-size:.78rem;">
                                    Voir tout <i class="bi bi-arrow-right ms-1"></i>
                                </a>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle" style="font-size:.83rem;">
                                    <thead>
                                        <tr class="small text-muted" style="font-size:.7rem; text-transform:uppercase; letter-spacing:.05em;">
                                            <th class="border-0 pb-3">Heure</th>
                                            <th class="border-0 pb-3">Utilisateur</th>
                                            <th class="border-0 pb-3">Action</th>
                                            <th class="border-0 pb-3">Module</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($recent_logs)): ?>
                                            <tr>
                                                <td colspan="4" class="text-center py-5 text-muted">
                                                    <i class="bi bi-inbox d-block fs-2 mb-2 opacity-25"></i>
                                                    Aucune activité enregistrée.
                                                </td>
                                            </tr>
                                        <?php else: foreach ($recent_logs as $log): ?>
                                            <tr>
                                                <td><span class="fw-bold text-dark"><?= date('H:i', strtotime($log['created_at'])) ?></span></td>
                                                <td><strong><?= htmlspecialchars($log['prenom'] . ' ' . $log['nom']) ?></strong></td>
                                                <td>
                                                    <?php
                                                    $actionColors = ['INSERT'=>'success','UPDATE'=>'primary','DELETE'=>'danger','READ'=>'secondary'];
                                                    $ac = $actionColors[$log['action']] ?? 'secondary';
                                                    ?>
                                                    <span class="badge bg-<?= $ac ?>-subtle text-<?= $ac ?> border border-<?= $ac ?>-subtle"><?= $log['action'] ?></span>
                                                </td>
                                                <td><small class="text-muted"><?= htmlspecialchars($log['table_name'] ?? '') ?></small></td>
                                            </tr>
                                        <?php endforeach; endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div><!-- /admin-page-content -->
    </div><!-- /admin-main -->
</div><!-- /admin-shell -->

<script>
function updateClock() {
    document.getElementById('liveClock').innerText = new Date().toLocaleTimeString('fr-FR');
}
setInterval(updateClock, 1000);
updateClock();
// Refresh auto toutes les 2 minutes
setTimeout(() => location.reload(), 120000);
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
