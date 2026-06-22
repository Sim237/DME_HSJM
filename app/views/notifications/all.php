<?php
/**
 * SimCare+ — Dossier Médical Électronique (DME)
 * Copyright (c) 2024-2026 Franck Simeni. Tous droits réservés.
 * Développé pour la gestion hospitalière, et le bien être numérique des patients.
 *
 * Toute reproduction, modification ou distribution de ce logiciel,
 * en tout ou en partie, sans autorisation écrite préalable de l'auteur
 * est strictement interdite et constitue une contrefaçon.
 *
 * Protected under OAPI Agreement — Annexe VII · Berne Convention
 */
 require_once __DIR__ . '/../layouts/header.php'; ?>

<style>
.nc-page { min-height:100vh; background:#f8fafc; padding:24px 28px; }
.nc-page-card {
    max-width: 900px; margin: 0 auto;
    background:#fff; border-radius:14px;
    box-shadow:0 2px 14px rgba(0,0,0,.06);
    overflow:hidden;
}
.nc-page-head {
    background:linear-gradient(135deg,#3b82f6,#1d4ed8);
    color:#fff; padding:22px 28px;
    display:flex; align-items:center; justify-content:space-between;
}
.nc-page-title { font-size:1.2rem; font-weight:800; margin:0; }
.nc-page-list { padding:8px 0; max-height:70vh; overflow-y:auto; }
.nc-page-item {
    padding:14px 22px; border-bottom:1px solid #f1f5f9;
    display:flex; gap:14px; transition:background .15s;
    cursor: pointer;
}
.nc-page-item:hover { background:#f8fafc; }
.nc-page-item.is-read { opacity:.6; }
.nc-page-item .ic {
    width:42px; height:42px; border-radius:50%;
    background:#eff6ff; color:#3b82f6;
    display:flex; align-items:center; justify-content:center;
    flex-shrink:0; font-size:1.1rem;
}
.nc-page-item .ic.cat-CONSULTATION { background:#eff6ff; color:#3b82f6; }
.nc-page-item .ic.cat-SOIN         { background:#fef2f2; color:#ef4444; }
.nc-page-item .ic.cat-BILAN        { background:#f0fdf4; color:#16a34a; }
.nc-page-item .ic.cat-LABO         { background:#fefce8; color:#ca8a04; }
.nc-page-item .ic.cat-IMAGERIE     { background:#f0f9ff; color:#0284c7; }
.nc-page-item .ic.cat-PHARMACIE    { background:#fdf4ff; color:#a21caf; }
.nc-page-item .ic.cat-RDV          { background:#fdf2f8; color:#db2777; }
.nc-page-item .ic.cat-HOSPIT       { background:#eef2ff; color:#4f46e5; }
.nc-page-item .ic.cat-URGENCE      { background:#fee2e2; color:#dc2626; }

.nc-page-body { flex:1; min-width:0; }
.nc-page-title2 { font-weight:700; color:#0f172a; font-size:.92rem; }
.nc-page-msg { font-size:.85rem; color:#475569; margin-top:3px; line-height:1.5; }
.nc-page-time { font-size:.72rem; color:#94a3b8; margin-top:6px; }
.nc-pill {
    display:inline-block; font-size:.65rem; font-weight:800;
    background:#f1f5f9; color:#475569;
    padding:2px 8px; border-radius:10px; margin-left:6px;
    text-transform: uppercase; letter-spacing:.4px;
}
.nc-pill.urgent { background:#fee2e2; color:#dc2626; }
.nc-pill.high { background:#fef3c7; color:#92400e; }
.nc-empty-large {
    text-align:center; padding:60px 20px; color:#94a3b8;
}
.nc-empty-large .bi { font-size:3rem; opacity:.35; display:block; margin-bottom:14px; }
</style>

<div class="nc-page">
    <div class="nc-page-card">
        <div class="nc-page-head">
            <div>
                <h2 class="nc-page-title">
                    <i class="bi bi-bell-fill me-2"></i>Toutes les notifications
                </h2>
                <small style="opacity:.85;"><?= count($notifications) ?> notification<?= count($notifications) > 1 ? 's' : '' ?></small>
            </div>
            <div class="d-flex gap-2">
                <a href="<?= BASE_URL ?>dashboard"
                   class="btn btn-sm btn-light rounded-pill px-3 fw-semibold">
                    <i class="bi bi-arrow-left me-1"></i>Retour
                </a>
            </div>
        </div>

        <div class="nc-page-list">
            <?php if (empty($notifications)): ?>
            <div class="nc-empty-large">
                <i class="bi bi-bell-slash"></i>
                Aucune notification dans votre historique
            </div>
            <?php else: foreach ($notifications as $n):
                $cat = strtoupper($n['category'] ?? 'INFO');
                $ic = $n['icon'] ?? '';
                if (!$ic) {
                    $ic = match ($cat) {
                        'CONSULTATION' => 'bi-clipboard2-pulse', 'SOIN' => 'bi-heart-pulse',
                        'BILAN' => 'bi-file-earmark-medical',  'LABO' => 'bi-flask',
                        'IMAGERIE' => 'bi-camera-video',        'PHARMACIE' => 'bi-capsule',
                        'RDV' => 'bi-calendar-check',           'HOSPIT' => 'bi-hospital',
                        'URGENCE' => 'bi-exclamation-triangle-fill', default => 'bi-bell',
                    };
                }
                $linkHref = '';
                if (!empty($n['link'])) {
                    $linkHref = (str_starts_with($n['link'], 'http') || str_starts_with($n['link'], '/'))
                                ? $n['link'] : BASE_URL . ltrim($n['link'], '/');
                }
            ?>
            <div class="nc-page-item <?= $n['is_read'] ? 'is-read' : '' ?>"
                 <?php if ($linkHref): ?>onclick="window.location.href='<?= htmlspecialchars($linkHref) ?>'"<?php endif; ?>>
                <div class="ic cat-<?= htmlspecialchars($cat) ?>"><i class="bi <?= htmlspecialchars($ic) ?>"></i></div>
                <div class="nc-page-body">
                    <div class="nc-page-title2">
                        <?= htmlspecialchars($n['title']) ?>
                        <?php if (!empty($n['priority']) && in_array($n['priority'], ['urgent','high'])): ?>
                            <span class="nc-pill <?= htmlspecialchars($n['priority']) ?>"><?= htmlspecialchars($n['priority']) ?></span>
                        <?php endif; ?>
                        <?php if (!$n['is_read']): ?>
                            <span class="nc-pill" style="background:#dbeafe;color:#1e40af;">Nouveau</span>
                        <?php endif; ?>
                    </div>
                    <div class="nc-page-msg"><?= nl2br(htmlspecialchars($n['message'])) ?></div>
                    <div class="nc-page-time">
                        <i class="bi bi-clock me-1"></i><?= date('d/m/Y à H:i', strtotime($n['created_at'])) ?>
                        <?php if (!empty($n['category'])): ?>
                            <span class="nc-pill"><?= htmlspecialchars($n['category']) ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
