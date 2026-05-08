<?php
require_once __DIR__ . '/../layouts/header.php';

// Sécurisation des variables
$total_refs     = $total_refs     ?? 0;
$total_alerte   = $total_alerte   ?? 0;
$processed_today = $processed_today ?? 0;
$pending_count  = $pending_count  ?? 0;
$pending_orders = $pending_orders ?? [];
$low_stock      = $low_stock      ?? [];
?>

<style>
    /* ── LAYOUT FULL WIDTH ── */
    .sidebar { display: none !important; }
    main, .col-md-10, .ms-sm-auto {
        margin-left: 0 !important;
        width: 100% !important;
        flex: 0 0 100% !important;
        max-width: 100% !important;
        padding: 0 !important;
    }
    body { background: #0f172a; font-family: 'Segoe UI', system-ui, sans-serif; }

    /* ── TOPBAR ── */
    .ph-topbar {
        background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
        border-bottom: 1px solid rgba(255,255,255,0.06);
        padding: 14px 28px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        position: sticky;
        top: 0;
        z-index: 100;
    }
    .ph-logo-text { font-size: 1.25rem; font-weight: 800; color: white; letter-spacing: -0.5px; }
    .ph-logo-text span { color: #f59e0b; }
    .ph-subtitle { font-size: 0.72rem; color: #64748b; font-weight: 600; text-transform: uppercase; letter-spacing: 1px; margin-top: 2px; }

    #clock {
        font-family: 'Courier New', monospace;
        font-size: 1.9rem;
        font-weight: 900;
        color: #34d399;
        letter-spacing: 3px;
        text-shadow: 0 0 20px rgba(52,211,153,.45);
    }

    .ph-btn {
        display: inline-flex; align-items: center; gap: 7px;
        padding: 8px 18px; border-radius: 10px; font-weight: 700;
        font-size: 0.78rem; text-decoration: none; border: none; cursor: pointer;
        transition: all .2s ease;
    }
    .ph-btn-outline { background: rgba(255,255,255,.05); color: #cbd5e1; border: 1px solid rgba(255,255,255,.1); }
    .ph-btn-outline:hover { background: rgba(255,255,255,.1); color: white; }
    .ph-btn-danger  { background: rgba(239,68,68,.15); color: #f87171; border: 1px solid rgba(239,68,68,.2); }
    .ph-btn-danger:hover  { background: rgba(239,68,68,.25); color: white; }

    /* ── CONTENU PRINCIPAL ── */
    .ph-main { padding: 24px 28px; }

    /* ── KPI CARDS ── */
    .kpi-grid { display: grid; grid-template-columns: repeat(4,1fr); gap: 16px; margin-bottom: 24px; }
    .kpi-card {
        background: #1e293b;
        border-radius: 16px;
        padding: 22px 24px;
        border: 1px solid rgba(255,255,255,.06);
        position: relative;
        overflow: hidden;
    }
    .kpi-card::before {
        content: '';
        position: absolute; top: 0; left: 0; right: 0; height: 3px;
        border-radius: 16px 16px 0 0;
    }
    .kpi-card.kpi-blue::before  { background: #3b82f6; }
    .kpi-card.kpi-red::before   { background: #ef4444; }
    .kpi-card.kpi-green::before { background: #10b981; }
    .kpi-card.kpi-amber::before { background: #f59e0b; }

    .kpi-value { font-size: 2.4rem; font-weight: 900; line-height: 1; margin-bottom: 4px; }
    .kpi-blue  .kpi-value { color: #93c5fd; }
    .kpi-red   .kpi-value { color: #fca5a5; }
    .kpi-green .kpi-value { color: #6ee7b7; }
    .kpi-amber .kpi-value { color: #fcd34d; }

    .kpi-label { font-size: 0.75rem; color: #64748b; font-weight: 700; text-transform: uppercase; letter-spacing: .8px; }
    .kpi-icon {
        position: absolute; right: 20px; top: 50%; transform: translateY(-50%);
        font-size: 2.5rem; opacity: .07;
    }

    /* ── GRID PRINCIPAL ── */
    .ph-grid { display: grid; grid-template-columns: 1fr 320px; gap: 20px; }

    /* ── PANEL ── */
    .ph-panel {
        background: #1e293b;
        border-radius: 20px;
        border: 1px solid rgba(255,255,255,.06);
        overflow: hidden;
    }
    .ph-panel-header {
        padding: 16px 22px;
        border-bottom: 1px solid rgba(255,255,255,.06);
        display: flex; align-items: center; justify-content: space-between;
    }
    .ph-panel-title { font-size: .85rem; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: .8px; }

    .badge-live {
        background: rgba(59,130,246,.2); color: #60a5fa;
        padding: 4px 12px; border-radius: 20px;
        font-size: .72rem; font-weight: 800; letter-spacing: 1px;
        border: 1px solid rgba(59,130,246,.3);
        animation: pulse-live 2s infinite;
    }
    @keyframes pulse-live { 0%,100%{opacity:1} 50%{opacity:.6} }

    /* ── TABLE ── */
    .ph-table { width: 100%; border-collapse: collapse; }
    .ph-table thead th {
        background: rgba(0,0,0,.2);
        color: #475569; font-size: .7rem; font-weight: 700;
        text-transform: uppercase; letter-spacing: 1px;
        padding: 12px 20px; border-bottom: 1px solid rgba(255,255,255,.05);
    }
    .ph-table tbody tr { border-bottom: 1px solid rgba(255,255,255,.04); transition: background .15s; }
    .ph-table tbody tr:hover { background: rgba(255,255,255,.03); }
    .ph-table tbody td { padding: 14px 20px; color: #cbd5e1; }

    .patient-name { font-weight: 700; color: #f1f5f9; font-size: .92rem; }
    .patient-ref  { font-size: .75rem; color: #475569; font-weight: 600; }
    .medecin-name { color: #60a5fa; font-weight: 600; font-size: .85rem; }

    .timer-normal { background: rgba(255,255,255,.06); color: #94a3b8; padding: 4px 12px; border-radius: 20px; font-size: .78rem; font-weight: 700; }
    .timer-alert  { background: rgba(239,68,68,.15); color: #f87171; padding: 4px 12px; border-radius: 20px; font-size: .78rem; font-weight: 700; animation: blink-alert 1.5s infinite; }
    @keyframes blink-alert { 50%{opacity:.5} }

    .btn-preparer {
        background: linear-gradient(135deg,#2563eb,#1d4ed8);
        color: white; padding: 7px 18px; border-radius: 10px;
        font-size: .78rem; font-weight: 700; border: none;
        text-decoration: none; display: inline-block;
        box-shadow: 0 4px 12px rgba(37,99,235,.3);
        transition: all .2s;
    }
    .btn-preparer:hover { transform: translateY(-1px); box-shadow: 0 6px 16px rgba(37,99,235,.4); color: white; }

    .empty-state { padding: 50px 20px; text-align: center; color: #475569; }
    .empty-state i { font-size: 2.5rem; opacity: .3; margin-bottom: 12px; display: block; }
    .empty-state span { font-size: .85rem; font-weight: 600; }

    /* ── PANEL ALERTES ── */
    .alert-item {
        padding: 14px 20px;
        border-bottom: 1px solid rgba(255,255,255,.04);
        display: flex; align-items: center; justify-content: space-between;
        transition: background .15s;
    }
    .alert-item:hover { background: rgba(255,255,255,.03); }
    .alert-item:last-child { border-bottom: none; }
    .alert-med-name  { font-size: .88rem; font-weight: 700; color: #f1f5f9; }
    .alert-med-info  { font-size: .74rem; color: #64748b; margin-top: 2px; }
    .badge-rupture   { background: rgba(239,68,68,.2); color: #f87171; padding: 4px 10px; border-radius: 20px; font-size: .8rem; font-weight: 800; border: 1px solid rgba(239,68,68,.3); }
    .badge-ok        { background: rgba(16,185,129,.1); color: #6ee7b7; padding: 4px 10px; border-radius: 20px; font-size: .8rem; font-weight: 800; }

    .ph-panel-footer { padding: 14px 20px; border-top: 1px solid rgba(255,255,255,.06); text-align: center; }
    .ph-panel-footer a { color: #60a5fa; font-size: .8rem; font-weight: 700; text-decoration: none; }
    .ph-panel-footer a:hover { color: #93c5fd; }
</style>

<main>

    <!-- TOPBAR -->
    <div class="ph-topbar">
        <div>
            <div class="ph-logo-text"><i class="bi bi-capsule me-2"></i>PHARMACIE <span>CENTRALE</span></div>
            <div class="ph-subtitle">Unité Logistique &bull; HSJM</div>
        </div>

        <div id="clock">00:00:00</div>

        <div class="d-flex gap-2">
            <button class="ph-btn ph-btn-outline" onclick="location.href='<?= BASE_URL ?>pharmacie/sage-sync'">
                <i class="bi bi-cloud-check-fill"></i> SYNCHRO SAGE
            </button>
            <a href="<?= BASE_URL ?>pharmacie/stock" class="ph-btn ph-btn-outline">
                <i class="bi bi-box-seam-fill"></i> INVENTAIRE
            </a>
            <a href="<?= BASE_URL ?>profil" class="ph-btn ph-btn-outline">
                <i class="bi bi-person-circle"></i> PROFIL
            </a>
            <a href="<?= BASE_URL ?>logout" class="ph-btn ph-btn-danger">
                <i class="bi bi-power"></i>
            </a>
        </div>
    </div>

    <div class="ph-main">

        <!-- KPI -->
        <div class="kpi-grid">
            <div class="kpi-card kpi-blue">
                <div class="kpi-value"><?= $total_refs ?></div>
                <div class="kpi-label">Références</div>
                <i class="bi bi-archive kpi-icon"></i>
            </div>
            <div class="kpi-card kpi-red">
                <div class="kpi-value"><?= $total_alerte ?></div>
                <div class="kpi-label">En Alerte Stock</div>
                <i class="bi bi-exclamation-triangle kpi-icon"></i>
            </div>
            <div class="kpi-card kpi-green">
                <div class="kpi-value"><?= $processed_today ?></div>
                <div class="kpi-label">Traitées Aujourd'hui</div>
                <i class="bi bi-check2-circle kpi-icon"></i>
            </div>
            <div class="kpi-card kpi-amber">
                <div class="kpi-value"><?= $pending_count ?></div>
                <div class="kpi-label">En Attente</div>
                <i class="bi bi-hourglass-split kpi-icon"></i>
            </div>
        </div>

        <!-- GRID PRINCIPAL -->
        <div class="ph-grid">

            <!-- FLUX ORDONNANCES -->
            <div class="ph-panel">
                <div class="ph-panel-header">
                    <span class="ph-panel-title"><i class="bi bi-megaphone me-2"></i>Flux e-Prescriptions</span>
                    <span class="badge-live">● LIVE</span>
                </div>
                <table class="ph-table">
                    <thead>
                        <tr>
                            <th>Patient</th>
                            <th>Prescripteur</th>
                            <th>Attente</th>
                            <th style="text-align:right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($pending_orders)): ?>
                            <tr>
                                <td colspan="4">
                                    <div class="empty-state">
                                        <i class="bi bi-inbox"></i>
                                        <span>Aucune ordonnance en attente de traitement.</span>
                                    </div>
                                </td>
                            </tr>
                        <?php else: foreach ($pending_orders as $ord): ?>
                            <tr>
                                <td>
                                    <div class="patient-name"><?= strtoupper($ord['nom'] ?? 'Inconnu') ?> <?= htmlspecialchars($ord['prenom'] ?? '') ?></div>
                                    <div class="patient-ref"><?= htmlspecialchars($ord['dossier_numero'] ?? '') ?></div>
                                </td>
                                <td><span class="medecin-name">Dr. <?= htmlspecialchars($ord['medecin_nom'] ?? '') ?></span></td>
                                <td>
                                    <?php $wait = (int)($ord['minutes_attente'] ?? 0); ?>
                                    <?php if ($wait >= 15): ?>
                                        <span class="timer-alert"><i class="bi bi-alarm-fill me-1"></i><?= $wait ?> min</span>
                                    <?php else: ?>
                                        <span class="timer-normal"><?= $wait ?> min</span>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align:right">
                                    <a href="<?= BASE_URL ?>pharmacie/traitement/<?= (int)$ord['id'] ?>" class="btn-preparer">
                                        <i class="bi bi-capsule me-1"></i>PRÉPARER
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- ALERTES STOCK -->
            <div class="ph-panel">
                <div class="ph-panel-header">
                    <span class="ph-panel-title"><i class="bi bi-exclamation-triangle-fill me-2 text-danger" style="color:#f87171!important"></i>Ruptures &amp; Alertes</span>
                </div>
                <?php if (empty($low_stock)): ?>
                    <div class="empty-state">
                        <i class="bi bi-check-circle-fill" style="color:#10b981;opacity:.5"></i>
                        <span>Stock optimal.</span>
                    </div>
                <?php else: foreach ($low_stock as $m): ?>
                    <div class="alert-item">
                        <div>
                            <div class="alert-med-name"><?= htmlspecialchars($m['nom'] ?? 'Produit') ?></div>
                            <div class="alert-med-info"><?= htmlspecialchars($m['dosage'] ?? '') ?> &bull; <?= htmlspecialchars($m['forme'] ?? '') ?></div>
                        </div>
                        <?php $qty = (int)($m['quantite'] ?? 0); ?>
                        <span class="<?= $qty === 0 ? 'badge-rupture' : 'badge-rupture' ?>"><?= $qty ?></span>
                    </div>
                <?php endforeach; endif; ?>
                <div class="ph-panel-footer">
                    <a href="<?= BASE_URL ?>pharmacie/stock"><i class="bi bi-box-seam me-1"></i>Voir l'inventaire complet</a>
                </div>
            </div>

        </div><!-- /ph-grid -->
    </div><!-- /ph-main -->
</main>

<script>
(function startClock() {
    function update() {
        const n = new Date();
        const t = [n.getHours(), n.getMinutes(), n.getSeconds()]
            .map(v => String(v).padStart(2,'0')).join(':');
        const el = document.getElementById('clock');
        if (el) el.textContent = t;
    }
    update();
    setInterval(update, 1000);
})();

// Auto-refresh toutes les 2 min
setTimeout(() => location.reload(), 120000);
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
