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
<script src="<?= BASE_URL ?>public/js/chart.umd.js"></script>

<style>
/* ══════════════════════════════════════════════════════
   VARIABLES DE THÈME (modifiées par JS)
══════════════════════════════════════════════════════ */
:root {
    --clk:        #00ff87;
    --clk-glow:   rgba(0,255,135,0.4);
    --acc:        #10b981;
    --acc-dim:    rgba(16,185,129,0.12);
    --acc-border: rgba(16,185,129,0.35);
    --bg-body:    #0f172a;
    --bg-card:    #1e293b;
    --bg-card2:   #263044;
    --border:     rgba(255,255,255,0.06);
    --txt-main:   #f1f5f9;
    --txt-muted:  #94a3b8;
    --txt-dim:    #64748b;
}

/* ══════════════════════════════════════════════════════ LAYOUT */
.sidebar { display:none !important; }
main { margin-left:0 !important; width:100% !important; }
body { background:var(--bg-body); font-family:'Inter',system-ui,sans-serif; color:var(--txt-main); }

/* ══════════════════════════════════════════════════════ TOPBAR */
.dir-topbar {
    background:linear-gradient(135deg,#0f172a 0%,#1e293b 100%);
    border-bottom:1px solid var(--border);
    padding:12px 28px;
    position:sticky; top:0; z-index:1000;
}
#dir-clock {
    font-family:'Courier New',monospace; font-size:1.45rem; font-weight:900;
    color:var(--clk); text-shadow:0 0 18px var(--clk-glow); letter-spacing:2px;
    transition:color .4s,text-shadow .4s;
}

/* ══════════════════════════════════════════════════════ THEME SWITCHER */
.theme-btn {
    background:rgba(255,255,255,.08); border:1px solid rgba(255,255,255,.15);
    color:#fff; border-radius:50px; padding:5px 14px; font-size:.78rem;
    font-weight:700; cursor:pointer; transition:.2s;
    display:flex; align-items:center; gap:6px;
}
.theme-btn:hover { background:rgba(255,255,255,.14); }
.theme-panel {
    position:absolute; top:calc(100% + 10px); right:0;
    background:#1e293b; border:1px solid rgba(255,255,255,.1);
    border-radius:16px; padding:16px 18px; min-width:260px;
    box-shadow:0 20px 50px rgba(0,0,0,.5); z-index:2000;
    display:none;
}
.theme-panel.show { display:block; animation:fadeUp .2s ease; }
@keyframes fadeUp { from{opacity:0;transform:translateY(-6px)} to{opacity:1;transform:none} }
.theme-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:10px; margin-top:10px; }
.theme-swatch {
    width:100%; aspect-ratio:1; border-radius:12px; cursor:pointer;
    border:2px solid transparent; transition:.2s; position:relative;
    display:flex; align-items:center; justify-content:center;
}
.theme-swatch:hover { transform:scale(1.12); }
.theme-swatch.active::after {
    content:'✓'; font-size:.9rem; font-weight:900; color:#fff;
    text-shadow:0 1px 4px rgba(0,0,0,.6);
}
.theme-swatch.active { border-color:rgba(255,255,255,.6); }
.theme-swatch-label { font-size:.65rem; color:#64748b; text-align:center; margin-top:4px; }

/* ══════════════════════════════════════════════════════ SECTIONS */
.dir-content { padding:24px 28px 40px; }
.section-label {
    font-size:.67rem; font-weight:800; text-transform:uppercase; letter-spacing:2px;
    color:var(--acc); margin-bottom:14px; display:flex; align-items:center; gap:8px;
    transition:color .4s;
}
.section-label::after { content:''; flex:1; height:1px; background:var(--border); }

/* ══════════════════════════════════════════════════════ KPI CARDS */
.kpi-card {
    background:var(--bg-card); border:1px solid var(--border);
    border-radius:16px; padding:18px 20px;
    position:relative; overflow:hidden;
    transition:transform .2s,box-shadow .2s,border-color .2s;
    height:100%;
}
.kpi-card:hover { transform:translateY(-3px); box-shadow:0 14px 36px rgba(0,0,0,.35); border-color:rgba(255,255,255,.12); }
.kpi-card::before { content:''; position:absolute; top:0; left:0; right:0; height:3px; }
.kc-blue::before   { background:linear-gradient(90deg,#3b82f6,#06b6d4); }
.kc-green::before  { background:linear-gradient(90deg,#10b981,#34d399); }
.kc-orange::before { background:linear-gradient(90deg,#f59e0b,#fbbf24); }
.kc-red::before    { background:linear-gradient(90deg,#ef4444,#f97316); }
.kc-purple::before { background:linear-gradient(90deg,#8b5cf6,#a78bfa); }
.kc-teal::before   { background:linear-gradient(90deg,#14b8a6,#06b6d4); }
.kc-pink::before   { background:linear-gradient(90deg,#f43f5e,#fb7185); }
.kc-indigo::before { background:linear-gradient(90deg,#6366f1,#818cf8); }
.kc-amber::before  { background:linear-gradient(90deg,#d97706,#fbbf24); }
.kc-cyan::before   { background:linear-gradient(90deg,#0891b2,#38bdf8); }
.kc-accent::before { background:linear-gradient(90deg,var(--acc),var(--clk)); }

.kpi-value { font-size:2.1rem; font-weight:900; color:var(--txt-main); line-height:1; margin:6px 0 3px; }
.kpi-label { font-size:.75rem; color:var(--txt-muted); font-weight:500; }
.kpi-sub   { font-size:.7rem; color:var(--txt-dim); margin-top:5px; }
.kpi-icon  { position:absolute; right:16px; top:50%; transform:translateY(-50%); font-size:2.6rem; opacity:.07; color:#fff; pointer-events:none; }
.kpi-badge {
    display:inline-flex; align-items:center; gap:3px;
    font-size:.68rem; font-weight:700; padding:2px 8px; border-radius:20px; margin-top:6px;
}
.badge-up   { background:rgba(16,185,129,.15); color:#10b981; }
.badge-down { background:rgba(239,68,68,.15); color:#ef4444; }
.badge-warn { background:rgba(245,158,11,.15); color:#f59e0b; }
.badge-info { background:rgba(59,130,246,.15); color:#60a5fa; }
.badge-neu  { background:rgba(100,116,139,.15); color:#94a3b8; }

.gauge-bar  { height:8px; border-radius:10px; background:rgba(255,255,255,.06); margin-top:8px; overflow:hidden; }
.gauge-fill { height:100%; border-radius:10px; transition:width 1.2s cubic-bezier(.16,1,.3,1); }

/* ══════════════════════════════════════════════════════ ALERT CARDS */
.alert-card {
    background:linear-gradient(135deg,rgba(239,68,68,.1),rgba(249,115,22,.08));
    border:1px solid rgba(239,68,68,.25); border-radius:14px;
    padding:14px 16px; display:flex; align-items:center; gap:12px;
}
.alert-card-p1 { border-color:rgba(239,68,68,.45); background:linear-gradient(135deg,rgba(239,68,68,.18),rgba(239,68,68,.08)); }
.alert-card-p2 { border-color:rgba(245,158,11,.35); background:linear-gradient(135deg,rgba(245,158,11,.15),rgba(245,158,11,.06)); }

.pulse-dot {
    width:10px; height:10px; border-radius:50%; flex-shrink:0;
    animation:pulseDot 1.4s infinite;
}
.pd-red  { background:#ef4444; box-shadow:0 0 0 0 rgba(239,68,68,.6); }
.pd-amber{ background:#f59e0b; box-shadow:0 0 0 0 rgba(245,158,11,.6); }
@keyframes pulseDot {
    0%   { box-shadow:0 0 0 0 currentColor; }
    70%  { box-shadow:0 0 0 7px transparent; }
    100% { box-shadow:0 0 0 0 transparent; }
}
.pd-red  { animation:pulseDotR 1.4s infinite; }
.pd-amber{ animation:pulseDotA 1.4s infinite; }
@keyframes pulseDotR { 0%{box-shadow:0 0 0 0 rgba(239,68,68,.7)} 70%{box-shadow:0 0 0 7px transparent} 100%{box-shadow:0 0 0 0 transparent} }
@keyframes pulseDotA { 0%{box-shadow:0 0 0 0 rgba(245,158,11,.7)} 70%{box-shadow:0 0 0 7px transparent} 100%{box-shadow:0 0 0 0 transparent} }

/* ══════════════════════════════════════════════════════ CHART CARDS */
.chart-card {
    background:var(--bg-card); border:1px solid var(--border);
    border-radius:16px; padding:18px 22px; height:100%;
}
.chart-card-title {
    font-size:.82rem; font-weight:700; color:#cbd5e1;
    margin-bottom:14px; display:flex; align-items:center; gap:8px;
}
.dot { width:8px; height:8px; border-radius:50%; flex-shrink:0; }

/* ══════════════════════════════════════════════════════ TABLES */
.dir-table { width:100%; border-collapse:collapse; font-size:.8rem; }
.dir-table th { padding:9px 12px; text-align:left; font-size:.67rem; font-weight:700; text-transform:uppercase; letter-spacing:1px; color:var(--txt-dim); border-bottom:1px solid var(--border); }
.dir-table td { padding:9px 12px; color:#cbd5e1; border-bottom:1px solid rgba(255,255,255,.03); vertical-align:middle; }
.dir-table tbody tr:hover { background:rgba(255,255,255,.025); }
.badge-pill { font-size:.67rem; padding:2px 8px; border-radius:20px; font-weight:700; }

/* ══════════════════════════════════════════════════════ PROGRESS */
.prog-bar { flex:1; height:6px; border-radius:6px; background:rgba(255,255,255,.06); overflow:hidden; min-width:50px; }
.prog-fill { height:100%; border-radius:6px; transition:width 1s ease; }

/* ══════════════════════════════════════════════════════ SCROLLBAR */
::-webkit-scrollbar { width:5px; height:5px; }
::-webkit-scrollbar-track { background:var(--bg-body); }
::-webkit-scrollbar-thumb { background:#334155; border-radius:3px; }

/* ══════════════════════════════════════════════════════ DIVIDER */
.stat-divider { border:none; border-top:1px solid var(--border); margin:6px 0; }

/* ══════════════════════════════════════════════════════ MINI STAT ROW */
.mini-stats { display:flex; gap:0; }
.mini-stat  { flex:1; text-align:center; padding:10px 6px; position:relative; }
.mini-stat + .mini-stat::before { content:''; position:absolute; left:0; top:20%; bottom:20%; width:1px; background:var(--border); }
.mini-stat-val   { font-size:1.2rem; font-weight:900; color:var(--txt-main); line-height:1; }
.mini-stat-label { font-size:.65rem; color:var(--txt-dim); margin-top:3px; }

/* ══════════════════════════════════════════════════════ REVENUS */
.rev-card {
    background:linear-gradient(135deg,#1a2744 0%,#1e2d4a 100%);
    border:1px solid rgba(59,130,246,.2); border-radius:16px;
    padding:18px 20px; position:relative; overflow:hidden; height:100%;
}
.rev-card::before { content:''; position:absolute; top:0; left:0; right:0; height:3px; background:linear-gradient(90deg,#3b82f6,#06b6d4); }

/* ══════════════════════════════════════════════════════ RESPONSIVE */
@media(max-width:768px) {
    .dir-content { padding:14px 14px 30px; }
    .dir-topbar { padding:10px 14px; }
    .kpi-value { font-size:1.7rem; }
}

/* ══════════════════════════════════════════════════════ CLICK ANIMATION */
.kpi-card { cursor:pointer; user-select:none; }
.kpi-ripple {
    position:absolute; border-radius:50%;
    background:rgba(255,255,255,.2); width:10px; height:10px;
    transform:scale(0); opacity:1; pointer-events:none; z-index:10;
    animation:kpiRipple .55s cubic-bezier(.4,0,.2,1) forwards;
}
@keyframes kpiRipple { to { transform:scale(36); opacity:0; } }
.kpi-card.kpi-pop { animation:kpiPop .28s cubic-bezier(.16,1,.3,1); }
@keyframes kpiPop {
    0%  { transform:scale(1) translateY(0); }
    40% { transform:scale(.95) translateY(2px); filter:brightness(1.1); }
    100%{ transform:scale(1) translateY(-3px); }
}

/* ══════════════════════════════════════════════════════ KPI DRAWER MODAL */
.kpi-overlay {
    position:fixed; inset:0; z-index:9500;
    background:rgba(0,0,0,.75); backdrop-filter:blur(6px);
    display:none; align-items:flex-end; justify-content:center;
}
.kpi-overlay.show { display:flex; animation:kpiOvIn .2s ease; }
@keyframes kpiOvIn { from{opacity:0} to{opacity:1} }
.kpi-drawer {
    background:#1e293b; border:1px solid rgba(255,255,255,.09);
    border-bottom:none; border-radius:28px 28px 0 0;
    width:100%; max-width:960px; max-height:82vh; overflow-y:auto;
    animation:kpiDrUp .32s cubic-bezier(.16,1,.3,1);
}
@keyframes kpiDrUp { from{transform:translateY(100%)} to{transform:translateY(0)} }
.kpi-dr-handle { width:40px; height:4px; background:rgba(255,255,255,.14); border-radius:4px; margin:12px auto 0; }
.kpi-dr-head {
    padding:14px 26px 12px; border-bottom:1px solid rgba(255,255,255,.07);
    display:flex; align-items:center; justify-content:space-between;
    position:sticky; top:0; z-index:3; background:#1e293b;
}
.kpi-dr-title { font-size:1rem; font-weight:800; color:#f1f5f9; display:flex; align-items:center; gap:10px; }
.kpi-dr-title .kpi-dr-icon { font-size:1.4rem; }
.kpi-dr-close {
    background:rgba(255,255,255,.07); border:1px solid rgba(255,255,255,.1);
    color:#94a3b8; border-radius:50%; width:30px; height:30px;
    display:flex; align-items:center; justify-content:center;
    cursor:pointer; font-size:.85rem; transition:.15s; flex-shrink:0;
}
.kpi-dr-close:hover { background:rgba(239,68,68,.2); color:#f87171; border-color:rgba(239,68,68,.3); }
.kpi-dr-body { padding:20px 26px 32px; }

/* detail grid & components */
.kd-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(150px,1fr)); gap:12px; margin-bottom:18px; }
.kd-stat { background:rgba(255,255,255,.04); border:1px solid rgba(255,255,255,.07); border-radius:14px; padding:14px 15px; position:relative; overflow:hidden; }
.kd-stat::before { content:''; position:absolute; top:0; left:0; right:0; height:2px; border-radius:2px; }
.kd-sv { font-size:1.65rem; font-weight:900; color:#f1f5f9; line-height:1; }
.kd-sl { font-size:.7rem; color:#94a3b8; margin-top:4px; }
.kd-ss { font-size:.67rem; color:#64748b; margin-top:2px; }
.kd-sep { border:none; border-top:1px solid rgba(255,255,255,.06); margin:14px 0; }
.kd-sec { font-size:.68rem; font-weight:700; text-transform:uppercase; letter-spacing:1.5px; color:#64748b; margin-bottom:8px; }
.kd-table { width:100%; border-collapse:collapse; font-size:.79rem; }
.kd-table th { color:#64748b; font-size:.67rem; font-weight:700; text-transform:uppercase; letter-spacing:.7px; padding:5px 8px; text-align:left; border-bottom:1px solid rgba(255,255,255,.06); }
.kd-table td { padding:7px 8px; color:#cbd5e1; border-bottom:1px solid rgba(255,255,255,.03); vertical-align:middle; }
.kd-table tbody tr:hover td { background:rgba(255,255,255,.025); }
.kd-bar { height:5px; border-radius:5px; background:rgba(255,255,255,.06); overflow:hidden; margin-top:4px; }
.kd-bar-f { height:100%; border-radius:5px; }
.kd-link { display:inline-flex; align-items:center; gap:6px; padding:7px 18px; border-radius:20px; font-size:.78rem; font-weight:700; text-decoration:none; transition:.2s; margin-top:10px; }
.kd-link:hover { filter:brightness(1.15); }
.kd-alert-row { display:flex; align-items:center; gap:10px; padding:8px 10px; border-radius:10px; margin-bottom:5px; }
.kd-badge { display:inline-block; padding:2px 8px; border-radius:20px; font-size:.67rem; font-weight:700; }

/* ── KPI loading spinner ── */
.kpi-spin {
    width:22px; height:22px; border-radius:50%;
    border:2px solid rgba(255,255,255,.12);
    border-top-color:var(--clk,#60a5fa);
    animation:kpiSpin .7s linear infinite; flex-shrink:0;
}
@keyframes kpiSpin { to{transform:rotate(360deg)} }
</style>

<!-- ══════════════════════════════════════════════════════ TOPBAR ══ -->
<nav class="dir-topbar d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div class="d-flex align-items-center gap-3">
        <div class="bg-white rounded-circle p-2 shadow-sm" style="flex-shrink:0;">
            <img src="<?= BASE_URL ?>public/images/logo_ordre_malte.png" style="height:34px;" alt="HSJM">
        </div>
        <div>
            <div class="text-white fw-bold" style="font-size:1.05rem;">Hôpital Saint-Jean de Malte</div>
            <div style="font-size:.7rem;color:#64748b;">
                Direction Générale &nbsp;|&nbsp;
                <span style="color:#94a3b8;"><?= htmlspecialchars($_SESSION['user_nom'] ?? 'Directeur') ?></span>
                &nbsp;|&nbsp;
                <span style="color:var(--acc);"><?= date('l d F Y') ?></span>
            </div>
        </div>
    </div>

    <div id="dir-clock">00:00:00</div>

    <div class="d-flex gap-2 align-items-center flex-wrap">
        <!-- Analyse Patients -->
        <a href="<?= BASE_URL ?>directeur/patients" class="btn btn-sm rounded-pill px-3 fw-bold"
           style="background:var(--acc-dim);color:var(--clk);border:1px solid var(--acc-border);">
            <i class="bi bi-people-fill me-1"></i>Analyse Patients
        </a>

        <!-- Export -->
        <div class="dropdown">
            <button class="btn btn-sm rounded-pill px-3 fw-bold dropdown-toggle"
                    style="background:rgba(16,185,129,.15);color:#6ee7b7;border:1px solid rgba(16,185,129,.3);"
                    data-bs-toggle="dropdown">
                <i class="bi bi-download me-1"></i>Exporter
            </button>
            <ul class="dropdown-menu dropdown-menu-end" style="background:#1e293b;border:1px solid rgba(255,255,255,.08);min-width:210px;">
                <li><h6 class="dropdown-header" style="color:#64748b;font-size:.68rem;">📄 Impression PDF</h6></li>
                <li><a class="dropdown-item" style="color:#cbd5e1;font-size:.8rem;" href="<?= BASE_URL ?>dashboard/export?format=pdf" target="_blank"><i class="bi bi-file-earmark-pdf me-2" style="color:#ef4444;"></i>Rapport complet</a></li>
                <li><hr class="dropdown-divider" style="border-color:rgba(255,255,255,.07);"></li>
                <li><h6 class="dropdown-header" style="color:#64748b;font-size:.68rem;">📊 Excel / CSV</h6></li>
                <li><a class="dropdown-item" style="color:#cbd5e1;font-size:.8rem;" href="<?= BASE_URL ?>dashboard/export?format=csv&section=global"><i class="bi bi-file-earmark-spreadsheet me-2" style="color:#10b981;"></i>Synthèse globale</a></li>
                <li><a class="dropdown-item" style="color:#cbd5e1;font-size:.8rem;" href="<?= BASE_URL ?>dashboard/export?format=csv&section=rdv"><i class="bi bi-calendar-check me-2" style="color:#8b5cf6;"></i>RDV Spécialistes</a></li>
                <li><a class="dropdown-item" style="color:#cbd5e1;font-size:.8rem;" href="<?= BASE_URL ?>dashboard/export?format=csv&section=hospitalisations"><i class="bi bi-hospital me-2" style="color:#f59e0b;"></i>Hospitalisations</a></li>
                <li><a class="dropdown-item" style="color:#cbd5e1;font-size:.8rem;" href="<?= BASE_URL ?>dashboard/export?format=csv&section=pharmacie"><i class="bi bi-capsule me-2" style="color:#06b6d4;"></i>Stocks Pharmacie</a></li>
                <li><a class="dropdown-item" style="color:#cbd5e1;font-size:.8rem;" href="<?= BASE_URL ?>dashboard/export?format=csv&section=services"><i class="bi bi-grid-3x3-gap me-2" style="color:#f97316;"></i>Activité par Service</a></li>
            </ul>
        </div>

        <!-- 🎨 SÉLECTEUR DE THÈME -->
        <div class="position-relative" id="themeWrapper">
            <button class="theme-btn" id="themeToggleBtn" onclick="toggleThemePanel()">
                <i class="bi bi-palette-fill" style="color:var(--clk);font-size:.9rem;"></i>
                <span>Thème</span>
            </button>
            <div class="theme-panel" id="themePanel">
                <div style="font-size:.72rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:1px;">
                    <i class="bi bi-palette me-1"></i>Choisir un thème
                </div>
                <div class="theme-grid" id="themeGrid"></div>
                <hr style="border-color:rgba(255,255,255,.06);margin:12px 0 8px;">
                <div style="font-size:.68rem;color:#475569;text-align:center;">
                    Sauvegardé automatiquement dans votre navigateur
                </div>
            </div>
        </div>

        <button class="btn btn-sm btn-outline-light rounded-pill px-3 fw-bold" onclick="location.reload()" title="Actualiser">
            <i class="bi bi-arrow-clockwise"></i>
        </button>
        <button onclick="ouvrirProfilModal()" class="btn btn-sm rounded-pill px-3 fw-bold"
                style="background:rgba(255,255,255,.1);color:#fff;border:1px solid rgba(255,255,255,.2);">
            <i class="bi bi-person-circle me-1"></i>Profil
        </button>
        <a href="<?= BASE_URL ?>logout" class="btn btn-sm btn-danger rounded-pill px-3 fw-bold">
            <i class="bi bi-power"></i>
        </a>
    </div>
</nav>

<!-- ══════════════════════════════════════════════════════ CONTENU ══ -->
<div class="dir-content">

<?php
// ── Alertes urgences critiques ─────────────────────────────────────────────
$hasAlerts = !empty($urgences_critiques);
if ($hasAlerts):
?>
<!-- ═══════════ BANDEAU ALERTES URGENCES P1/P2 ═══════════ -->
<div class="mb-4" style="background:rgba(239,68,68,.07);border:1px solid rgba(239,68,68,.25);border-radius:16px;padding:14px 20px;">
    <div class="d-flex align-items-center gap-2 mb-3">
        <div class="pulse-dot pd-red"></div>
        <span style="font-size:.78rem;font-weight:800;color:#f87171;text-transform:uppercase;letter-spacing:1.5px;">
            ⚠ Urgences Critiques — <?= count($urgences_critiques) ?> patient(s) en attente P1/P2
        </span>
    </div>
    <div class="row g-2">
        <?php foreach ($urgences_critiques as $urg):
            $isP1 = $urg['niveau_triage'] === 'P1';
            $urgColor = $isP1 ? '#ef4444' : '#f59e0b';
            $urgBg    = $isP1 ? 'rgba(239,68,68,.15)' : 'rgba(245,158,11,.12)';
            $urgBord  = $isP1 ? 'rgba(239,68,68,.35)' : 'rgba(245,158,11,.3)';
            $attente  = round((time() - strtotime($urg['created_at'])) / 60);
        ?>
        <div class="col-md-4 col-sm-6">
            <div style="background:<?= $urgBg ?>;border:1px solid <?= $urgBord ?>;border-radius:10px;padding:10px 14px;display:flex;align-items:center;gap:10px;">
                <div class="pulse-dot <?= $isP1?'pd-red':'pd-amber' ?>"></div>
                <div style="flex:1;min-width:0;">
                    <div style="font-weight:700;color:#f1f5f9;font-size:.82rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                        <?= htmlspecialchars($urg['prenom'].' '.strtoupper($urg['nom'])) ?>
                    </div>
                    <div style="font-size:.7rem;color:#94a3b8;"><?= htmlspecialchars($urg['motif_admission'] ?? '—') ?></div>
                </div>
                <div style="text-align:right;flex-shrink:0;">
                    <span style="background:<?= $urgBg ?>;color:<?= $urgColor ?>;border:1px solid <?= $urgBord ?>;border-radius:6px;padding:2px 7px;font-size:.7rem;font-weight:900;"><?= $urg['niveau_triage'] ?></span>
                    <div style="font-size:.65rem;color:#64748b;margin-top:2px;"><?= $attente ?>min</div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>


<!-- ═══════════ SECTION A — KPI PRINCIPAUX ═══════════ -->
<p class="section-label"><i class="bi bi-speedometer2"></i> Activité Clinique — Temps Réel</p>
<div class="row g-3 mb-4">

    <!-- Total Patients -->
    <div class="col-xl-3 col-lg-4 col-md-6">
        <div class="kpi-card kc-blue" data-kpi="patients" onclick="openKpiModal(this)"  title="Voir détails">
            <div class="kpi-label">Total Patients</div>
            <div class="kpi-value"><?= number_format($kpi['total_patients']) ?></div>
            <div>
                <span class="kpi-badge badge-up"><i class="bi bi-arrow-up-short"></i>+<?= $kpi['patients_ce_mois'] ?> ce mois</span>
            </div>
            <div class="kpi-sub">Dossiers actifs enregistrés</div>
            <i class="bi bi-people-fill kpi-icon"></i>
        </div>
    </div>

    <!-- Consultations -->
    <div class="col-xl-3 col-lg-4 col-md-6">
        <div class="kpi-card kc-green" data-kpi="consultations" onclick="openKpiModal(this)" title="Voir détails">
            <div class="kpi-label">Consultations ce mois</div>
            <div class="kpi-value"><?= number_format($kpi['consultations_mois']) ?></div>
            <div>
                <?php $avgDay = $kpi['consultations_mois'] > 0 ? round($kpi['consultations_mois'] / max(1, (int)date('j')), 1) : 0; ?>
                <span class="kpi-badge badge-info"><i class="bi bi-graph-up me-1"></i>~<?= $avgDay ?>/jour</span>
            </div>
            <div class="kpi-sub"><?= date('F Y') ?></div>
            <i class="bi bi-clipboard2-pulse kpi-icon"></i>
        </div>
    </div>

    <!-- Hospitalisés Actifs -->
    <div class="col-xl-3 col-lg-4 col-md-6">
        <div class="kpi-card kc-orange" data-kpi="hospitalisations" onclick="openKpiModal(this)" title="Voir détails">
            <div class="kpi-label">Hospitalisés Actifs</div>
            <div class="kpi-value"><?= $kpi['hospitalisations_actives'] ?></div>
            <div>
                <span class="kpi-badge badge-neu"><i class="bi bi-box-arrow-right me-1"></i><?= $kpi['patients_sortis_mois'] ?> sortis ce mois</span>
            </div>
            <div class="kpi-sub">DMS : <strong style="color:#fbbf24;"><?= $kpi['dms'] ?> jours</strong></div>
            <i class="bi bi-hospital kpi-icon"></i>
        </div>
    </div>

    <!-- Occupation Lits -->
    <div class="col-xl-3 col-lg-4 col-md-6">
        <?php
            $tOcc   = (float)$kpi['taux_occupation'];
            $occCls = $tOcc > 80 ? 'kc-red' : ($tOcc > 50 ? 'kc-orange' : 'kc-green');
            $occClr = $tOcc > 80 ? '#ef4444' : ($tOcc > 50 ? '#f59e0b' : '#10b981');
        ?>
        <div class="kpi-card <?= $occCls ?>" data-kpi="lits" onclick="openKpiModal(this)" title="Voir détails">
            <div class="kpi-label">Taux Occupation Lits</div>
            <div class="kpi-value" style="color:<?= $occClr ?>;"><?= $tOcc ?>%</div>
            <div class="gauge-bar">
                <div class="gauge-fill" style="width:<?= $tOcc ?>%;background:<?= $occClr ?>;"></div>
            </div>
            <div class="kpi-sub mt-1"><?= $kpi['lits_occupes'] ?> / <?= $kpi['total_lits'] ?> lits occupés</div>
            <i class="bi bi-grid-3x3-gap kpi-icon"></i>
        </div>
    </div>

    <!-- Urgences -->
    <div class="col-xl-3 col-lg-4 col-md-6">
        <?php $urgCls = $kpi['urgences_en_cours'] > 10 ? 'kc-red' : ($kpi['urgences_en_cours'] > 5 ? 'kc-orange' : 'kc-teal'); ?>
        <div class="kpi-card <?= $urgCls ?>" data-kpi="urgences" onclick="openKpiModal(this)" title="Voir détails">
            <div class="kpi-label">Urgences en cours</div>
            <div class="kpi-value"><?= $kpi['urgences_en_cours'] ?></div>
            <div>
                <?php if (!empty($urgences_critiques)): ?>
                <span class="kpi-badge badge-down"><i class="bi bi-exclamation-triangle-fill me-1"></i><?= count($urgences_critiques) ?> P1/P2</span>
                <?php else: ?>
                <span class="kpi-badge badge-up"><i class="bi bi-check-circle me-1"></i>Aucun critique</span>
                <?php endif; ?>
            </div>
            <div class="kpi-sub"><?= $kpi['urgences_aujourd_hui'] ?> admis aujourd'hui</div>
            <i class="bi bi-heart-pulse kpi-icon"></i>
        </div>
    </div>

    <!-- Examens Labo -->
    <div class="col-xl-3 col-lg-4 col-md-6">
        <div class="kpi-card kc-cyan" data-kpi="labo" onclick="openKpiModal(this)" title="Voir détails">
            <div class="kpi-label">Examens Labo ce mois</div>
            <div class="kpi-value"><?= number_format($kpi['examens_labo_mois']) ?></div>
            <div>
                <?php $avgLabo = $kpi['examens_labo_mois'] > 0 ? round($kpi['examens_labo_mois'] / max(1,(int)date('j')), 1) : 0; ?>
                <span class="kpi-badge badge-info"><i class="bi bi-graph-up me-1"></i>~<?= $avgLabo ?>/jour</span>
            </div>
            <div class="kpi-sub"><?= date('F Y') ?></div>
            <i class="bi bi-flask kpi-icon"></i>
        </div>
    </div>

    <!-- Chirurgies -->
    <div class="col-xl-3 col-lg-4 col-md-6">
        <div class="kpi-card kc-purple" data-kpi="chirurgies" onclick="openKpiModal(this)" title="Voir détails">
            <div class="kpi-label">Chirurgies ce mois</div>
            <div class="kpi-value"><?= $kpi['chirurgies_mois'] ?></div>
            <div><span class="kpi-badge badge-neu"><i class="bi bi-calendar3 me-1"></i><?= date('F Y') ?></span></div>
            <div class="kpi-sub">Interventions au bloc opératoire</div>
            <i class="bi bi-scissors kpi-icon"></i>
        </div>
    </div>

    <!-- Temps d'attente / Personnel -->
    <div class="col-xl-3 col-lg-4 col-md-6">
        <div class="kpi-card kc-indigo" data-kpi="attente" onclick="openKpiModal(this)" title="Voir détails">
            <div class="kpi-label">Temps Attente Moy.</div>
            <div class="kpi-value">
                <?php
                    $att = $kpi['temps_attente_moy'];
                    echo $att > 60 ? round($att/60,1).'h' : $att.'min';
                ?>
            </div>
            <div>
                <?php $attCls = $att > 120 ? 'badge-down' : ($att > 45 ? 'badge-warn' : 'badge-up'); ?>
                <span class="kpi-badge <?= $attCls ?>">
                    <i class="bi bi-<?= $att>120?'exclamation-circle':($att>45?'dash-circle':'check-circle') ?> me-1"></i>
                    <?= $att > 120 ? 'Attente longue' : ($att > 45 ? 'Attente modérée' : 'Bonne fluidité') ?>
                </span>
            </div>
            <div class="kpi-sub">30 derniers jours</div>
            <i class="bi bi-stopwatch kpi-icon"></i>
        </div>
    </div>

</div>


<!-- ═══════════ SECTION B — RDV + FINANCE + RH ═══════════ -->
<p class="section-label"><i class="bi bi-calendar2-check"></i> RDV Spécialistes &amp; Ressources</p>
<div class="row g-3 mb-4">

    <!-- RDV Aujourd'hui -->
    <div class="col-xl-2 col-lg-4 col-md-6">
        <div class="kpi-card kc-purple" data-kpi="rdv-today" onclick="openKpiModal(this)" title="Voir détails">
            <div class="kpi-label">RDV aujourd'hui</div>
            <div class="kpi-value"><?= $kpi['rdv_aujourd_hui'] ?></div>
            <div class="kpi-sub"><?= date('d/m/Y') ?></div>
            <i class="bi bi-calendar-day kpi-icon"></i>
        </div>
    </div>

    <!-- RDV Mois -->
    <div class="col-xl-2 col-lg-4 col-md-6">
        <div class="kpi-card kc-blue" data-kpi="rdv-mois" onclick="openKpiModal(this)" title="Voir détails">
            <div class="kpi-label">RDV ce mois</div>
            <div class="kpi-value"><?= $kpi['rdv_mois'] ?></div>
            <div class="kpi-sub"><?= date('F Y') ?></div>
            <i class="bi bi-calendar-month kpi-icon"></i>
        </div>
    </div>

    <!-- RDV Honorés -->
    <div class="col-xl-2 col-lg-4 col-md-6">
        <div class="kpi-card kc-green" data-kpi="rdv-honores" onclick="openKpiModal(this)" title="Voir détails">
            <div class="kpi-label">RDV honorés</div>
            <div class="kpi-value"><?= $kpi['rdv_honores_mois'] ?></div>
            <div class="kpi-sub"><?= $kpi['rdv_mois'] > 0 ? $kpi['rdv_taux_honore'].'% du total' : 'Aucun RDV' ?></div>
            <i class="bi bi-check2-circle kpi-icon"></i>
        </div>
    </div>

    <!-- Taux Honoration -->
    <div class="col-xl-2 col-lg-4 col-md-6">
        <?php $tH = $kpi['rdv_taux_honore']; $hC = $tH>=70?'#10b981':($tH>=40?'#f59e0b':'#ef4444'); $hCls = $tH>=70?'kc-green':($tH>=40?'kc-orange':'kc-red'); ?>
        <div class="kpi-card <?= $hCls ?>" data-kpi="rdv-taux" onclick="openKpiModal(this)" title="Voir détails">
            <div class="kpi-label">Taux d'honoration</div>
            <div class="kpi-value" style="color:<?= $hC ?>;"><?= $tH ?>%</div>
            <div class="gauge-bar">
                <div class="gauge-fill" style="width:<?= $tH ?>%;background:<?= $hC ?>;"></div>
            </div>
            <div class="kpi-sub mt-1"><?= $kpi['rdv_honores_mois'] ?>/<?= $kpi['rdv_mois'] ?> RDV</div>
            <i class="bi bi-bar-chart kpi-icon"></i>
        </div>
    </div>

    <!-- Alertes Pharmacie -->
    <div class="col-xl-2 col-lg-4 col-md-6">
        <?php $pharCls = $kpi['medicaments_rupture']>0?'kc-red':($kpi['medicaments_alerte']>0?'kc-orange':'kc-teal'); ?>
        <div class="kpi-card <?= $pharCls ?>" data-kpi="pharmacie" onclick="openKpiModal(this)" title="Voir détails">
            <div class="kpi-label">Alertes Pharmacie</div>
            <div class="kpi-value"><?= $kpi['medicaments_rupture'] + $kpi['medicaments_alerte'] ?></div>
            <div>
                <?php if ($kpi['medicaments_rupture'] > 0): ?>
                <span class="kpi-badge badge-down"><i class="bi bi-exclamation-octagon me-1"></i><?= $kpi['medicaments_rupture'] ?> rupture(s)</span>
                <?php endif; ?>
            </div>
            <div class="kpi-sub"><?= $kpi['medicaments_alerte'] ?> en alerte</div>
            <i class="bi bi-capsule kpi-icon"></i>
        </div>
    </div>

    <!-- Personnel Actif -->
    <div class="col-xl-2 col-lg-4 col-md-6">
        <div class="kpi-card kc-accent" data-kpi="personnel" onclick="openKpiModal(this)" title="Voir détails">
            <div class="kpi-label">Personnel Actif</div>
            <div class="kpi-value" style="color:var(--clk);"><?= $kpi['personnel_actif'] ?></div>
            <div>
                <?php
                $actifs7j = array_sum(array_column($activite_personnel, 'nb_actifs'));
                ?>
                <span class="kpi-badge badge-up"><i class="bi bi-wifi me-1"></i><?= $actifs7j ?> connectés (7j)</span>
            </div>
            <div class="kpi-sub">Utilisateurs actifs du système</div>
            <i class="bi bi-person-badge kpi-icon"></i>
        </div>
    </div>

</div>


<!-- ═══════════ SECTION C — GRAPHIQUES PRINCIPAUX ═══════════ -->
<p class="section-label"><i class="bi bi-graph-up-arrow"></i> Évolution &amp; Répartition</p>
<div class="row g-3 mb-4">

    <!-- Occupation lits par service -->
    <div class="col-xl-4 col-lg-5">
        <div class="chart-card">
            <div class="chart-card-title">
                <span class="dot" style="background:#3b82f6;"></span>
                Occupation des Lits par Service
            </div>
            <?php if (empty($lits_par_service)): ?>
            <div class="text-center py-4" style="color:#64748b;">Aucune donnée</div>
            <?php else: ?>
            <div>
                <?php foreach ($lits_par_service as $svc):
                    $pct   = (float)($svc['taux'] ?? 0);
                    $color = $pct>80?'#ef4444':($pct>50?'#f59e0b':'#10b981');
                ?>
                <div class="mb-3">
                    <div class="d-flex justify-content-between mb-1">
                        <span style="font-size:.78rem;color:#cbd5e1;font-weight:600;"><?= htmlspecialchars($svc['nom_service']) ?></span>
                        <span style="font-size:.72rem;color:#94a3b8;"><?= $svc['occupes'] ?>/<?= $svc['total'] ?> — <strong style="color:<?= $color ?>;"><?= $pct ?>%</strong></span>
                    </div>
                    <div class="gauge-bar">
                        <div class="gauge-fill" style="width:<?= $pct ?>%;background:<?= $color ?>;"></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Patients enregistrés 12 mois -->
    <div class="col-xl-8 col-lg-7">
        <div class="chart-card">
            <div class="chart-card-title">
                <span class="dot" style="background:#10b981;"></span>
                Patients Enregistrés — 12 Derniers Mois
                <span class="ms-auto" style="font-size:.7rem;color:#64748b;">Total : <?= array_sum(array_column($patients_par_mois,'nb')) ?></span>
            </div>
            <canvas id="chartPatientsMois" height="160"></canvas>
        </div>
    </div>

</div>

<!-- Consultations vs Hosp + Top Pathologies -->
<div class="row g-3 mb-4">
    <div class="col-xl-6">
        <div class="chart-card">
            <div class="chart-card-title">
                <span class="dot" style="background:#06b6d4;"></span>
                Consultations vs Hospitalisations — 6 Mois
            </div>
            <canvas id="chartConsultHosp" height="200"></canvas>
        </div>
    </div>
    <div class="col-xl-6">
        <div class="chart-card">
            <div class="chart-card-title">
                <span class="dot" style="background:#a78bfa;"></span>
                Top 10 Pathologies Diagnostiquées
            </div>
            <?php if (empty($top_pathologies)): ?>
            <div class="text-center py-4" style="color:#64748b;">Aucun diagnostic enregistré</div>
            <?php else: ?>
            <canvas id="chartPathologies" height="200"></canvas>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Labo évolution + Distribution horaire -->
<div class="row g-3 mb-4">

    <div class="col-xl-6">
        <div class="chart-card">
            <div class="chart-card-title">
                <span class="dot" style="background:#38bdf8;"></span>
                Demandes Laboratoire — 6 Mois
                <?php if (empty($labo_par_mois)): ?>
                <span style="font-size:.7rem;color:#64748b;margin-left:auto;">Aucune donnée</span>
                <?php endif; ?>
            </div>
            <?php if (!empty($labo_par_mois)): ?>
            <canvas id="chartLabo" height="160"></canvas>
            <?php else: ?>
            <div class="text-center py-4" style="color:#64748b;">Aucun examen de laboratoire enregistré</div>
            <?php endif; ?>
        </div>
    </div>

    <div class="col-xl-6">
        <div class="chart-card">
            <div class="chart-card-title">
                <span class="dot" style="background:#f59e0b;"></span>
                Distribution Horaire des Consultations (30j)
            </div>
            <canvas id="chartHeures" height="160"></canvas>
        </div>
    </div>

</div>


<!-- ═══════════ SECTION D — MÉDICAMENTS + ACTIVITÉ PERSONNEL ═══════════ -->
<p class="section-label"><i class="bi bi-capsule"></i> Pharmacie &amp; Ressources Humaines</p>
<div class="row g-3 mb-4">

    <!-- Top médicaments -->
    <div class="col-xl-5">
        <div class="chart-card">
            <div class="chart-card-title">
                <span class="dot" style="background:#f59e0b;"></span>
                Médicaments les Plus Prescrits
            </div>
            <?php if (empty($top_medicaments)): ?>
            <div class="text-center py-4" style="color:#64748b;">Aucune ordonnance</div>
            <?php else:
            $maxMed = max(array_column($top_medicaments,'total_demande'));
            $medColors = ['#f59e0b','#fb923c','#f87171','#a78bfa','#34d399','#38bdf8','#818cf8','#fb7185','#4ade80','#facc15'];
            foreach ($top_medicaments as $idx => $med):
                $pct = $maxMed > 0 ? round($med['total_demande']/$maxMed*100) : 0;
                $c = $medColors[$idx%count($medColors)];
            ?>
            <div class="mb-2">
                <div class="d-flex justify-content-between mb-1">
                    <span style="font-size:.78rem;color:#e2e8f0;font-weight:600;max-width:65%;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="<?= htmlspecialchars($med['nom']) ?>"><?= htmlspecialchars($med['nom']) ?></span>
                    <span style="font-size:.72rem;color:#94a3b8;"><?= $med['total_demande'] ?> unités</span>
                </div>
                <div class="gauge-bar">
                    <div class="gauge-fill" style="width:<?= $pct ?>%;background:<?= $c ?>;"></div>
                </div>
            </div>
            <?php endforeach; endif; ?>
        </div>
    </div>

    <!-- Activité personnel par rôle + Revenu mois -->
    <div class="col-xl-7">
        <div class="row g-3 h-100">

            <?php if ($revenus_mois > 0): ?>
            <!-- Revenus -->
            <div class="col-12">
                <div class="rev-card">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div style="font-size:.75rem;color:#93c5fd;font-weight:600;text-transform:uppercase;letter-spacing:1px;">Revenus ce mois</div>
                            <div style="font-size:2.2rem;font-weight:900;color:#fff;line-height:1.1;margin-top:4px;">
                                <?= number_format($revenus_mois, 0, ',', ' ') ?> FCFA
                            </div>
                            <div style="font-size:.72rem;color:#60a5fa;margin-top:4px;"><?= date('F Y') ?> — Factures payées</div>
                        </div>
                        <i class="bi bi-cash-coin" style="font-size:3rem;opacity:.15;color:#3b82f6;"></i>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Activité par rôle -->
            <div class="col-12">
                <div class="chart-card" style="height:auto;">
                    <div class="chart-card-title">
                        <span class="dot" style="background:#8b5cf6;"></span>
                        Connexions Personnel — 7 Derniers Jours
                    </div>
                    <?php if (empty($activite_personnel)): ?>
                    <div style="color:#64748b;font-size:.8rem;text-align:center;padding:16px;">Aucune connexion enregistrée</div>
                    <?php else:
                    $maxAct = max(array_column($activite_personnel,'nb_actifs'));
                    $roleLabels = ['ADMIN'=>'Admin','MEDECIN'=>'Médecin','MEDECIN_CHEF'=>'Chef Méd.','INFIRMIER'=>'Infirmier','SPECIALISTE'=>'Spécialiste','PHARMACIEN'=>'Pharmacien','LABORANTIN'=>'Laborantin','SECRETAIRE'=>'Secrétaire','DIRECTEUR'=>'Directeur'];
                    $roleColors = ['ADMIN'=>'#ef4444','MEDECIN'=>'#3b82f6','MEDECIN_CHEF'=>'#1d4ed8','INFIRMIER'=>'#10b981','SPECIALISTE'=>'#06b6d4','PHARMACIEN'=>'#f59e0b','LABORANTIN'=>'#8b5cf6','SECRETAIRE'=>'#f97316','DIRECTEUR'=>'#a78bfa'];
                    foreach ($activite_personnel as $ap):
                        $pct = $maxAct > 0 ? round($ap['nb_actifs']/$maxAct*100) : 0;
                        $c   = $roleColors[$ap['role']] ?? '#94a3b8';
                        $lbl = $roleLabels[$ap['role']] ?? $ap['role'];
                    ?>
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <span style="font-size:.75rem;color:#cbd5e1;font-weight:600;min-width:80px;"><?= $lbl ?></span>
                        <div class="prog-bar">
                            <div class="prog-fill" style="width:<?= $pct ?>%;background:<?= $c ?>;"></div>
                        </div>
                        <span style="font-size:.75rem;font-weight:800;color:<?= $c ?>;min-width:20px;"><?= $ap['nb_actifs'] ?></span>
                    </div>
                    <?php endforeach; endif; ?>
                </div>
            </div>

        </div>
    </div>

</div>


<!-- ═══════════ SECTION E — RDV SPÉCIALISTES ═══════════ -->
<p class="section-label"><i class="bi bi-calendar2-week"></i> Rendez-vous Spécialistes</p>
<div class="row g-3 mb-4">

    <!-- Tableau spécialistes -->
    <div class="col-xl-7">
        <div class="chart-card">
            <div class="chart-card-title d-flex justify-content-between align-items-center w-100">
                <span><span class="dot" style="background:#8b5cf6;"></span> Par Spécialiste — <?= date('F Y') ?></span>
                <a href="<?= BASE_URL ?>dashboard/export?format=csv&section=rdv" style="font-size:.7rem;color:#8b5cf6;text-decoration:none;font-weight:700;"><i class="bi bi-download me-1"></i>CSV</a>
            </div>
            <?php if (empty($rdv_par_specialiste)): ?>
            <div class="text-center py-4" style="color:#64748b;">Aucun spécialiste configuré</div>
            <?php else: ?>
            <div style="overflow-x:auto;">
                <table class="dir-table">
                    <thead>
                        <tr>
                            <th>Spécialiste</th>
                            <th style="text-align:center;">Total</th>
                            <th style="text-align:center;">Honorés</th>
                            <th style="text-align:center;">Annulés</th>
                            <th style="text-align:center;">En attente</th>
                            <th>Taux</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rdv_par_specialiste as $sp):
                            $tx  = $sp['total']>0 ? round($sp['honores']/$sp['total']*100,1) : 0;
                            $hc  = $tx>=70?'#10b981':($tx>=40?'#f59e0b':'#ef4444');
                        ?>
                        <tr>
                            <td>
                                <div style="font-weight:700;color:#f1f5f9;font-size:.82rem;"><?= htmlspecialchars($sp['specialite']) ?></div>
                                <div style="font-size:.7rem;color:#475569;"><?= htmlspecialchars($sp['prenom'].' '.$sp['nom']) ?></div>
                            </td>
                            <td style="text-align:center;font-weight:800;color:#a5b4fc;"><?= $sp['total'] ?></td>
                            <td style="text-align:center;color:#6ee7b7;font-weight:700;"><?= $sp['honores'] ?></td>
                            <td style="text-align:center;color:#f87171;"><?= $sp['annules'] ?></td>
                            <td style="text-align:center;color:#fbbf24;"><?= $sp['en_attente'] ?></td>
                            <td>
                                <div style="display:flex;align-items:center;gap:6px;">
                                    <div style="flex:1;height:4px;border-radius:4px;background:rgba(255,255,255,.06);overflow:hidden;min-width:44px;">
                                        <div style="height:100%;border-radius:4px;width:<?= $tx ?>%;background:<?= $hc ?>;"></div>
                                    </div>
                                    <span style="font-size:.73rem;font-weight:800;color:<?= $hc ?>;"><?= $tx ?>%</span>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Évolution RDV + Répartition statuts -->
    <div class="col-xl-5">
        <div class="chart-card h-100">
            <div class="chart-card-title">
                <span class="dot" style="background:#a78bfa;"></span>
                Évolution des RDV — 6 Mois
            </div>
            <?php if (empty($rdv_evolution_mois)): ?>
            <div class="text-center py-4" style="color:#64748b;">Aucun historique</div>
            <?php else: ?>
            <canvas id="chartRdvEvolution" height="200"></canvas>
            <?php endif; ?>
            <?php if (!empty($rdv_par_statut)): ?>
            <div style="margin-top:14px;padding-top:12px;border-top:1px solid rgba(255,255,255,.06);">
                <div style="font-size:.68rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.8px;margin-bottom:8px;">Répartition ce mois</div>
                <?php
                $statColors = ['EN_ATTENTE'=>'#fbbf24','CONFIRME'=>'#60a5fa','EN_COURS'=>'#34d399','TERMINE'=>'#6ee7b7','ANNULE'=>'#f87171','REPORTE'=>'#94a3b8'];
                $rdvTotal = array_sum(array_column($rdv_par_statut,'nb'));
                foreach ($rdv_par_statut as $rs):
                    $pctS = $rdvTotal > 0 ? round($rs['nb']/$rdvTotal*100) : 0;
                    $sc = $statColors[$rs['statut']] ?? '#94a3b8';
                ?>
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:5px;">
                    <span style="font-size:.73rem;color:#cbd5e1;font-weight:600;min-width:90px;"><?= $rs['statut'] ?></span>
                    <div style="display:flex;align-items:center;gap:6px;flex:1;">
                        <div style="flex:1;height:4px;border-radius:4px;background:rgba(255,255,255,.06);overflow:hidden;">
                            <div style="height:100%;border-radius:4px;width:<?= $pctS ?>%;background:<?= $sc ?>;"></div>
                        </div>
                        <span style="font-size:.73rem;font-weight:800;color:<?= $sc ?>;min-width:22px;text-align:right;"><?= $rs['nb'] ?></span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

</div>


<!-- ═══════════ SECTION F — STOCKS + RÉSUMÉ SERVICES ═══════════ -->
<p class="section-label"><i class="bi bi-grid-3x3-gap-fill"></i> Stocks Pharmacie &amp; Résumé Services</p>
<div class="row g-3 mb-4">

    <!-- Stocks -->
    <div class="col-xl-5">
        <div class="chart-card">
            <div class="chart-card-title">
                <span class="dot" style="background:#ef4444;"></span>
                État des Stocks — Pharmacie
                <?php if ($kpi['medicaments_rupture']>0): ?>
                <span class="ms-2" style="background:rgba(239,68,68,.2);color:#ef4444;font-size:.65rem;padding:2px 8px;border-radius:20px;font-weight:800;"><?= $kpi['medicaments_rupture'] ?> RUPTURE(S)</span>
                <?php endif; ?>
            </div>
            <div style="max-height:340px;overflow-y:auto;">
                <table class="dir-table">
                    <thead><tr><th>Médicament</th><th>Stock</th><th>Seuil</th><th>Statut</th></tr></thead>
                    <tbody>
                        <?php foreach ($stocks_alerte as $m):
                            $niv   = $m['niveau'];
                            $color = match($niv) { 'rupture'=>'#ef4444','critique'=>'#f97316','alerte'=>'#f59e0b',default=>'#10b981' };
                            $label = match($niv) { 'rupture'=>'RUPTURE','critique'=>'CRITIQUE','alerte'=>'ALERTE',default=>'OK' };
                            $maxQ  = max($m['seuil_alerte']*3,1);
                            $pctS  = min(round($m['quantite']/$maxQ*100),100);
                        ?>
                        <tr>
                            <td>
                                <div style="font-weight:600;color:#e2e8f0;font-size:.78rem;"><?= htmlspecialchars($m['nom']) ?></div>
                                <div style="font-size:.66rem;color:#64748b;"><?= htmlspecialchars($m['forme']??'') ?> <?= htmlspecialchars($m['dosage']??'') ?></div>
                            </td>
                            <td>
                                <div style="display:flex;align-items:center;gap:6px;">
                                    <div style="flex:1;height:5px;border-radius:5px;background:rgba(255,255,255,.06);overflow:hidden;min-width:40px;">
                                        <div style="height:100%;border-radius:5px;width:<?= $pctS ?>%;background:<?= $color ?>;"></div>
                                    </div>
                                    <span style="color:<?= $color ?>;font-weight:700;font-size:.75rem;min-width:24px;text-align:right;"><?= $m['quantite'] ?></span>
                                </div>
                            </td>
                            <td style="color:#64748b;font-size:.75rem;"><?= $m['seuil_alerte'] ?></td>
                            <td><span class="badge-pill" style="background:<?= $color ?>22;color:<?= $color ?>;"><?= $label ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($stocks_alerte)): ?>
                        <tr><td colspan="4" class="text-center" style="color:#64748b;padding:24px;">Tous les stocks sont normaux ✓</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Résumé par service -->
    <div class="col-xl-7">
        <div class="chart-card">
            <div class="chart-card-title">
                <span class="dot" style="background:#8b5cf6;"></span>
                Résumé par Service — Activité du Mois
            </div>
            <div style="overflow-x:auto;">
                <table class="dir-table">
                    <thead>
                        <tr>
                            <th>Service</th>
                            <th style="text-align:center;">Patients</th>
                            <th style="text-align:center;">Consult.</th>
                            <th style="text-align:center;">Hosp. actives</th>
                            <th>Occupation lits</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($resume_services as $s):
                            $occ = ($s['total_lits']>0) ? round(($s['lits_occupes']/$s['total_lits'])*100) : 0;
                            $oc  = $occ>80?'#ef4444':($occ>50?'#f59e0b':'#10b981');
                        ?>
                        <tr>
                            <td style="font-weight:700;color:#e2e8f0;"><?= htmlspecialchars($s['nom_service']) ?></td>
                            <td style="text-align:center;font-weight:700;color:#38bdf8;"><?= $s['nb_patients'] ?></td>
                            <td style="text-align:center;color:#10b981;font-weight:600;"><?= $s['nb_consult'] ?></td>
                            <td style="text-align:center;">
                                <?php if ($s['nb_hosp_actives']>0): ?>
                                <span class="badge-pill" style="background:rgba(249,115,22,.2);color:#fb923c;"><?= $s['nb_hosp_actives'] ?></span>
                                <?php else: ?><span style="color:#475569;">—</span><?php endif; ?>
                            </td>
                            <td>
                                <?php if ($s['total_lits']>0): ?>
                                <div style="display:flex;align-items:center;gap:6px;">
                                    <div class="prog-bar" style="min-width:60px;">
                                        <div class="prog-fill" style="width:<?= $occ ?>%;background:<?= $oc ?>;"></div>
                                    </div>
                                    <span style="color:<?= $oc ?>;font-size:.73rem;font-weight:700;"><?= $occ ?>%</span>
                                </div>
                                <?php else: ?><span style="color:#475569;font-size:.72rem;">N/A</span><?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>


<!-- ════════════════════════════════════════════════════════
     KPI DETAIL DRAWER MODAL
════════════════════════════════════════════════════════ -->
<div class="kpi-overlay" id="kpiOverlay" onclick="kpiOverlayClick(event)">
    <div class="kpi-drawer" id="kpiDrawer">
        <div class="kpi-dr-handle"></div>
        <div class="kpi-dr-head">
            <div class="kpi-dr-title">
                <span class="kpi-dr-icon" id="kpiDrIcon">📊</span>
                <span id="kpiDrTitle">Détails</span>
            </div>
            <button class="kpi-dr-close" onclick="closeKpiModal()" title="Fermer (Échap)">✕</button>
        </div>
        <div class="kpi-dr-body" id="kpiDrBody"></div>
    </div>
</div>

<?php
/* ══════════════════════════════════════════════════════
   PRÉ-CALCUL DES DONNÉES DE DÉTAIL POUR CHAQUE KPI
══════════════════════════════════════════════════════ */

/* helpers */
$fmtNum = fn($n) => number_format((int)$n, 0, ',', ' ');
$avgDay = fn($n) => $n > 0 ? round($n / max(1,(int)date('j')),1) : 0;

/* occupation lits — table HTML */
$litsTable = '';
if (!empty($lits_par_service)) {
    $litsTable .= '<p class="kd-sec">Occupation par service</p>';
    $litsTable .= '<table class="kd-table"><thead><tr><th>Service</th><th>Lits occ.</th><th>Total</th><th style="min-width:100px;">Taux</th></tr></thead><tbody>';
    foreach ($lits_par_service as $ls) {
        $p = (float)($ls['taux'] ?? 0);
        $c = $p>80?'#ef4444':($p>50?'#f59e0b':'#10b981');
        $litsTable .= '<tr><td style="font-weight:600;">' . htmlspecialchars($ls['nom_service']) . '</td>'
            . '<td style="text-align:center;color:#38bdf8;">' . $ls['occupes'] . '</td>'
            . '<td style="text-align:center;color:#64748b;">' . $ls['total'] . '</td>'
            . '<td><div style="display:flex;align-items:center;gap:7px;">'
            . '<div class="kd-bar" style="flex:1;"><div class="kd-bar-f" style="width:' . $p . '%;background:' . $c . ';"></div></div>'
            . '<span style="color:' . $c . ';font-size:.72rem;font-weight:700;">' . $p . '%</span></div></td></tr>';
    }
    $litsTable .= '</tbody></table>';
}

/* pharmacie alerts — table HTML */
$pharTable = '';
if (!empty($stocks_alerte)) {
    $pharTable .= '<table class="kd-table"><thead><tr><th>Médicament</th><th>Stock actuel</th><th>Seuil alerte</th><th>Niveau</th></tr></thead><tbody>';
    foreach (array_slice($stocks_alerte, 0, 20) as $med) {
        $niv = $med['niveau'] ?? 'alerte';
        $nivColor = $niv==='rupture' ? '#ef4444' : ($niv==='critique'?'#f97316':'#f59e0b');
        $nivBg    = $niv==='rupture' ? 'rgba(239,68,68,.15)' : ($niv==='critique'?'rgba(249,115,22,.15)':'rgba(245,158,11,.15)');
        $nivLbl   = $niv==='rupture' ? 'Rupture' : ($niv==='critique'?'Critique':'Alerte');
        $pharTable .= '<tr>'
            . '<td style="font-weight:600;">' . htmlspecialchars($med['nom'] ?? '—') . '</td>'
            . '<td style="text-align:center;color:' . $nivColor . ';font-weight:700;">' . ($med['stock_actuel'] ?? '—') . '</td>'
            . '<td style="text-align:center;color:#64748b;">' . ($med['stock_min'] ?? ($med['seuil_alerte'] ?? '—')) . '</td>'
            . '<td><span class="kd-badge" style="background:' . $nivBg . ';color:' . $nivColor . ';">' . $nivLbl . '</span></td></tr>';
    }
    $pharTable .= '</tbody></table>';
} else {
    $pharTable = '<div style="color:#64748b;text-align:center;padding:20px;">Aucune alerte pharmacie</div>';
}

/* personnel par rôle */
$persoTable = '';
if (!empty($activite_personnel)) {
    $persoTable .= '<p class="kd-sec">Activité par rôle (7 derniers jours)</p>';
    $persoTable .= '<table class="kd-table"><thead><tr><th>Rôle</th><th>Actifs</th></tr></thead><tbody>';
    foreach ($activite_personnel as $p) {
        $persoTable .= '<tr><td>' . htmlspecialchars($p['role'] ?? '—') . '</td>'
            . '<td style="font-weight:700;color:var(--clk);">' . ($p['nb_actifs'] ?? 0) . '</td></tr>';
    }
    $persoTable .= '</tbody></table>';
}

/* urgences critiques list */
$urgList = '';
if (!empty($urgences_critiques)) {
    foreach ($urgences_critiques as $urg) {
        $isP1 = $urg['niveau_triage'] === 'P1';
        $uc = $isP1?'#ef4444':'#f59e0b'; $ub = $isP1?'rgba(239,68,68,.1)':'rgba(245,158,11,.1)';
        $att = round((time() - strtotime($urg['created_at'])) / 60);
        $urgList .= '<div class="kd-alert-row" style="background:' . $ub . ';border:1px solid ' . $uc . '33;">'
            . '<span class="kd-badge" style="background:' . $ub . ';color:' . $uc . ';">' . htmlspecialchars($urg['niveau_triage']) . '</span>'
            . '<div style="flex:1;"><div style="font-weight:700;font-size:.82rem;">' . htmlspecialchars(($urg['prenom']??'').' '.strtoupper($urg['nom']??'')) . '</div>'
            . '<div style="font-size:.7rem;color:#94a3b8;">' . htmlspecialchars($urg['motif_admission']??'—') . '</div></div>'
            . '<span style="color:#64748b;font-size:.7rem;">' . $att . 'min</span></div>';
    }
} else {
    $urgList = '<div style="color:#10b981;text-align:center;padding:16px;"><i class="bi bi-check-circle-fill me-2"></i>Aucune urgence critique en cours</div>';
}

$avgConsult  = $avgDay($kpi['consultations_mois']);
$avgLaboDay  = $avgDay($kpi['examens_labo_mois']);
$attFormatted = $kpi['temps_attente_moy'] > 60 ? round($kpi['temps_attente_moy']/60,1).'h' : $kpi['temps_attente_moy'].'min';
$attClass = $kpi['temps_attente_moy']>120?'🔴 Attente longue':($kpi['temps_attente_moy']>45?'🟡 Modérée':'🟢 Bonne fluidité');
$attColor = $kpi['temps_attente_moy']>120?'#ef4444':($kpi['temps_attente_moy']>45?'#f59e0b':'#10b981');

$kpiDetails = [

'patients' => ['icon'=>'👥','title'=>'Total Patients — Détails', 'html'=> '
<div class="kd-grid">
  <div class="kd-stat"><div style="position:absolute;top:0;left:0;right:0;height:2px;background:linear-gradient(90deg,#3b82f6,#06b6d4);border-radius:2px;"></div>
    <div class="kd-sv">' . $fmtNum($kpi['total_patients']) . '</div><div class="kd-sl">Dossiers actifs</div><div class="kd-ss">Base totale</div></div>
  <div class="kd-stat"><div style="position:absolute;top:0;left:0;right:0;height:2px;background:linear-gradient(90deg,#10b981,#34d399);border-radius:2px;"></div>
    <div class="kd-sv" style="color:#10b981;">+' . $fmtNum($kpi['patients_ce_mois']) . '</div><div class="kd-sl">Nouveaux ce mois</div><div class="kd-ss">' . date('F Y') . '</div></div>
  <div class="kd-stat"><div style="position:absolute;top:0;left:0;right:0;height:2px;background:linear-gradient(90deg,#8b5cf6,#a78bfa);border-radius:2px;"></div>
    <div class="kd-sv" style="color:#a78bfa;">' . $avgDay($kpi['patients_ce_mois']) . '</div><div class="kd-sl">Admissions/jour</div><div class="kd-ss">Mois en cours</div></div>
</div>
<a href="' . BASE_URL . 'patients" class="kd-link" style="background:rgba(59,130,246,.15);color:#60a5fa;border:1px solid rgba(59,130,246,.3);">
  <i class="bi bi-people-fill"></i> Voir tous les patients
</a>'],

'consultations' => ['icon'=>'🩺','title'=>'Consultations — Détails', 'html'=> '
<div class="kd-grid">
  <div class="kd-stat"><div style="position:absolute;top:0;left:0;right:0;height:2px;background:linear-gradient(90deg,#10b981,#34d399);border-radius:2px;"></div>
    <div class="kd-sv">' . $fmtNum($kpi['consultations_mois']) . '</div><div class="kd-sl">Consultations ce mois</div><div class="kd-ss">' . date('F Y') . '</div></div>
  <div class="kd-stat"><div style="position:absolute;top:0;left:0;right:0;height:2px;background:linear-gradient(90deg,#06b6d4,#38bdf8);border-radius:2px;"></div>
    <div class="kd-sv" style="color:#38bdf8;">~' . $avgConsult . '</div><div class="kd-sl">Par jour (moy.)</div><div class="kd-ss">Jours écoulés ce mois</div></div>
</div>
<a href="' . BASE_URL . 'consultation" class="kd-link" style="background:rgba(16,185,129,.15);color:#6ee7b7;border:1px solid rgba(16,185,129,.3);">
  <i class="bi bi-clipboard2-pulse"></i> Voir les consultations
</a>'],

'hospitalisations' => ['icon'=>'🏥','title'=>'Hospitalisés Actifs — Détails', 'html'=> '
<div class="kd-grid">
  <div class="kd-stat"><div style="position:absolute;top:0;left:0;right:0;height:2px;background:linear-gradient(90deg,#f59e0b,#fbbf24);border-radius:2px;"></div>
    <div class="kd-sv">' . $kpi['hospitalisations_actives'] . '</div><div class="kd-sl">Hospitalisés actifs</div><div class="kd-ss">En cours</div></div>
  <div class="kd-stat"><div style="position:absolute;top:0;left:0;right:0;height:2px;background:linear-gradient(90deg,#3b82f6,#06b6d4);border-radius:2px;"></div>
    <div class="kd-sv" style="color:#fbbf24;">' . $kpi['dms'] . 'j</div><div class="kd-sl">Durée Moy. Séjour</div><div class="kd-ss">DMS</div></div>
  <div class="kd-stat"><div style="position:absolute;top:0;left:0;right:0;height:2px;background:linear-gradient(90deg,#10b981,#34d399);border-radius:2px;"></div>
    <div class="kd-sv" style="color:#34d399;">' . $kpi['patients_sortis_mois'] . '</div><div class="kd-sl">Sortis ce mois</div><div class="kd-ss">' . date('F Y') . '</div></div>
</div>
' . $litsTable . '
<a href="' . BASE_URL . 'hospitalisation" class="kd-link" style="background:rgba(245,158,11,.15);color:#fbbf24;border:1px solid rgba(245,158,11,.3);">
  <i class="bi bi-hospital"></i> Voir les hospitalisations
</a>'],

'lits' => ['icon'=>'🛏','title'=>'Occupation des Lits — Détails', 'html'=> '
<div class="kd-grid">
  <div class="kd-stat"><div style="position:absolute;top:0;left:0;right:0;height:2px;background:linear-gradient(90deg,#3b82f6,#06b6d4);border-radius:2px;"></div>
    <div class="kd-sv" style="color:' . $occClr . ';">' . $tOcc . '%</div><div class="kd-sl">Taux d\'occupation</div></div>
  <div class="kd-stat"><div style="position:absolute;top:0;left:0;right:0;height:2px;background:linear-gradient(90deg,#ef4444,#f97316);border-radius:2px;"></div>
    <div class="kd-sv" style="color:#f87171;">' . $kpi['lits_occupes'] . '</div><div class="kd-sl">Lits occupés</div></div>
  <div class="kd-stat"><div style="position:absolute;top:0;left:0;right:0;height:2px;background:linear-gradient(90deg,#10b981,#34d399);border-radius:2px;"></div>
    <div class="kd-sv" style="color:#34d399;">' . ($kpi['total_lits'] - $kpi['lits_occupes']) . '</div><div class="kd-sl">Lits disponibles</div></div>
  <div class="kd-stat"><div style="position:absolute;top:0;left:0;right:0;height:2px;background:linear-gradient(90deg,#8b5cf6,#a78bfa);border-radius:2px;"></div>
    <div class="kd-sv">' . $kpi['total_lits'] . '</div><div class="kd-sl">Capacité totale</div></div>
</div>
' . $litsTable],

'urgences' => ['icon'=>'🚨','title'=>'Urgences en Cours — Détails', 'html'=> '
<div class="kd-grid">
  <div class="kd-stat"><div style="position:absolute;top:0;left:0;right:0;height:2px;background:linear-gradient(90deg,#ef4444,#f97316);border-radius:2px;"></div>
    <div class="kd-sv">' . $kpi['urgences_en_cours'] . '</div><div class="kd-sl">En cours (ATTENTE / EN_COURS)</div></div>
  <div class="kd-stat"><div style="position:absolute;top:0;left:0;right:0;height:2px;background:linear-gradient(90deg,#f59e0b,#fbbf24);border-radius:2px;"></div>
    <div class="kd-sv" style="color:#fbbf24;">' . count($urgences_critiques) . '</div><div class="kd-sl">Critiques P1 / P2</div></div>
  <div class="kd-stat"><div style="position:absolute;top:0;left:0;right:0;height:2px;background:linear-gradient(90deg,#3b82f6,#06b6d4);border-radius:2px;"></div>
    <div class="kd-sv" style="color:#60a5fa;">' . $kpi['urgences_aujourd_hui'] . '</div><div class="kd-sl">Admis aujourd\'hui</div></div>
</div>
<hr class="kd-sep">
<p class="kd-sec">Patients critiques P1 / P2</p>
' . $urgList . '
<a href="' . BASE_URL . 'urgences" class="kd-link" style="background:rgba(239,68,68,.15);color:#f87171;border:1px solid rgba(239,68,68,.3);">
  <i class="bi bi-heart-pulse-fill"></i> Voir les urgences
</a>'],

'labo' => ['icon'=>'🧪','title'=>'Examens Laboratoire — Détails', 'html'=> '
<div class="kd-grid">
  <div class="kd-stat"><div style="position:absolute;top:0;left:0;right:0;height:2px;background:linear-gradient(90deg,#0891b2,#38bdf8);border-radius:2px;"></div>
    <div class="kd-sv">' . $fmtNum($kpi['examens_labo_mois']) . '</div><div class="kd-sl">Examens ce mois</div><div class="kd-ss">' . date('F Y') . '</div></div>
  <div class="kd-stat"><div style="position:absolute;top:0;left:0;right:0;height:2px;background:linear-gradient(90deg,#10b981,#34d399);border-radius:2px;"></div>
    <div class="kd-sv" style="color:#34d399;">~' . $avgLaboDay . '</div><div class="kd-sl">Examens/jour (moy.)</div></div>
</div>
<a href="' . BASE_URL . 'laboratoire" class="kd-link" style="background:rgba(8,145,178,.15);color:#38bdf8;border:1px solid rgba(8,145,178,.3);">
  <i class="bi bi-flask"></i> Voir le laboratoire
</a>'],

'chirurgies' => ['icon'=>'🔪','title'=>'Chirurgies — Détails', 'html'=> '
<div class="kd-grid">
  <div class="kd-stat"><div style="position:absolute;top:0;left:0;right:0;height:2px;background:linear-gradient(90deg,#8b5cf6,#a78bfa);border-radius:2px;"></div>
    <div class="kd-sv">' . $kpi['chirurgies_mois'] . '</div><div class="kd-sl">Interventions ce mois</div><div class="kd-ss">' . date('F Y') . '</div></div>
</div>
<div style="color:#64748b;font-size:.8rem;margin-top:8px;">Bloc opératoire — données enregistrées dans le module chirurgie.</div>'],

'attente' => ['icon'=>'⏱','title'=>'Temps d\'Attente — Détails', 'html'=> '
<div class="kd-grid">
  <div class="kd-stat"><div style="position:absolute;top:0;left:0;right:0;height:2px;background:linear-gradient(90deg,#6366f1,#818cf8);border-radius:2px;"></div>
    <div class="kd-sv" style="color:' . $attColor . ';">' . $attFormatted . '</div><div class="kd-sl">Temps moyen (30j)</div></div>
  <div class="kd-stat"><div style="position:absolute;top:0;left:0;right:0;height:2px;background:' . $attColor . ';"></div>
    <div class="kd-sv" style="font-size:1.1rem;">' . $attClass . '</div><div class="kd-sl">Classification</div></div>
</div>
<div style="color:#64748b;font-size:.8rem;margin-top:8px;">
  Seuils : <span style="color:#10b981;">≤ 45 min = Bonne fluidité</span> · <span style="color:#f59e0b;">45–120 min = Modérée</span> · <span style="color:#ef4444;">> 120 min = Attente longue</span>
</div>'],

'rdv-today' => ['icon'=>'📅','title'=>'RDV Aujourd\'hui — Détails', 'html'=> '
<div class="kd-grid">
  <div class="kd-stat"><div style="position:absolute;top:0;left:0;right:0;height:2px;background:linear-gradient(90deg,#8b5cf6,#a78bfa);border-radius:2px;"></div>
    <div class="kd-sv">' . $kpi['rdv_aujourd_hui'] . '</div><div class="kd-sl">RDV ce jour</div><div class="kd-ss">' . date('d/m/Y') . '</div></div>
  <div class="kd-stat"><div style="position:absolute;top:0;left:0;right:0;height:2px;background:linear-gradient(90deg,#3b82f6,#06b6d4);border-radius:2px;"></div>
    <div class="kd-sv">' . $kpi['rdv_mois'] . '</div><div class="kd-sl">Total ce mois</div><div class="kd-ss">' . date('F Y') . '</div></div>
</div>'],

'rdv-mois' => ['icon'=>'📆','title'=>'RDV Ce Mois — Détails', 'html'=> '
<div class="kd-grid">
  <div class="kd-stat"><div style="position:absolute;top:0;left:0;right:0;height:2px;background:linear-gradient(90deg,#3b82f6,#06b6d4);border-radius:2px;"></div>
    <div class="kd-sv">' . $kpi['rdv_mois'] . '</div><div class="kd-sl">Total RDV</div><div class="kd-ss">' . date('F Y') . '</div></div>
  <div class="kd-stat"><div style="position:absolute;top:0;left:0;right:0;height:2px;background:linear-gradient(90deg,#10b981,#34d399);border-radius:2px;"></div>
    <div class="kd-sv" style="color:#34d399;">' . $kpi['rdv_honores_mois'] . '</div><div class="kd-sl">Honorés</div></div>
  <div class="kd-stat"><div style="position:absolute;top:0;left:0;right:0;height:2px;background:linear-gradient(90deg,#ef4444,#f97316);border-radius:2px;"></div>
    <div class="kd-sv" style="color:#f87171;">' . max(0,$kpi['rdv_mois']-$kpi['rdv_honores_mois']) . '</div><div class="kd-sl">Non honorés</div></div>
</div>'],

'rdv-honores' => ['icon'=>'✅','title'=>'RDV Honorés — Détails', 'html'=> '
<div class="kd-grid">
  <div class="kd-stat"><div style="position:absolute;top:0;left:0;right:0;height:2px;background:linear-gradient(90deg,#10b981,#34d399);border-radius:2px;"></div>
    <div class="kd-sv" style="color:#34d399;">' . $kpi['rdv_honores_mois'] . '</div><div class="kd-sl">Honorés ce mois</div></div>
  <div class="kd-stat"><div style="position:absolute;top:0;left:0;right:0;height:2px;background:' . $hC . ';"></div>
    <div class="kd-sv" style="color:' . $hC . ';">' . $tH . '%</div><div class="kd-sl">Taux d\'honoration</div></div>
  <div class="kd-stat"><div style="position:absolute;top:0;left:0;right:0;height:2px;background:linear-gradient(90deg,#3b82f6,#06b6d4);border-radius:2px;"></div>
    <div class="kd-sv">' . $kpi['rdv_mois'] . '</div><div class="kd-sl">Total prévus</div></div>
</div>'],

'rdv-taux' => ['icon'=>'📊','title'=>'Taux d\'Honoration RDV — Détails', 'html'=> '
<div class="kd-grid">
  <div class="kd-stat"><div style="position:absolute;top:0;left:0;right:0;height:2px;background:' . $hC . ';"></div>
    <div class="kd-sv" style="color:' . $hC . ';">' . $tH . '%</div><div class="kd-sl">Taux d\'honoration</div></div>
  <div class="kd-stat"><div style="position:absolute;top:0;left:0;right:0;height:2px;background:linear-gradient(90deg,#10b981,#34d399);border-radius:2px;"></div>
    <div class="kd-sv" style="color:#34d399;">' . $kpi['rdv_honores_mois'] . '</div><div class="kd-sl">Honorés</div></div>
  <div class="kd-stat"><div style="position:absolute;top:0;left:0;right:0;height:2px;background:linear-gradient(90deg,#ef4444,#f97316);border-radius:2px;"></div>
    <div class="kd-sv" style="color:#f87171;">' . max(0,$kpi['rdv_mois']-$kpi['rdv_honores_mois']) . '</div><div class="kd-sl">Non honorés</div></div>
</div>
<div style="margin-top:12px;">
  <div class="kd-bar" style="height:10px;">
    <div class="kd-bar-f" style="width:' . $tH . '%;background:' . $hC . ';height:10px;"></div>
  </div>
</div>'],

'pharmacie' => ['icon'=>'💊','title'=>'Alertes Pharmacie — Détails', 'html'=> '
<div class="kd-grid">
  <div class="kd-stat"><div style="position:absolute;top:0;left:0;right:0;height:2px;background:linear-gradient(90deg,#ef4444,#f97316);border-radius:2px;"></div>
    <div class="kd-sv" style="color:#f87171;">' . $kpi['medicaments_rupture'] . '</div><div class="kd-sl">En rupture de stock</div></div>
  <div class="kd-stat"><div style="position:absolute;top:0;left:0;right:0;height:2px;background:linear-gradient(90deg,#f59e0b,#fbbf24);border-radius:2px;"></div>
    <div class="kd-sv" style="color:#fbbf24;">' . $kpi['medicaments_alerte'] . '</div><div class="kd-sl">En alerte / critique</div></div>
</div>
<hr class="kd-sep">
<p class="kd-sec">Liste des médicaments concernés</p>
' . $pharTable . '
<a href="' . BASE_URL . 'pharmacie" class="kd-link" style="background:rgba(6,182,212,.15);color:#38bdf8;border:1px solid rgba(6,182,212,.3);">
  <i class="bi bi-capsule"></i> Gérer la pharmacie
</a>'],

'personnel' => ['icon'=>'👤','title'=>'Personnel Actif — Détails', 'html'=> '
<div class="kd-grid">
  <div class="kd-stat"><div style="position:absolute;top:0;left:0;right:0;height:2px;background:linear-gradient(90deg,var(--acc),var(--clk));border-radius:2px;"></div>
    <div class="kd-sv" style="color:var(--clk);">' . $kpi['personnel_actif'] . '</div><div class="kd-sl">Utilisateurs actifs</div></div>
  <div class="kd-stat"><div style="position:absolute;top:0;left:0;right:0;height:2px;background:linear-gradient(90deg,#10b981,#34d399);border-radius:2px;"></div>
    <div class="kd-sv" style="color:#34d399;">' . array_sum(array_column($activite_personnel,'nb_actifs')) . '</div><div class="kd-sl">Connectés (7j)</div></div>
</div>
' . $persoTable],

];
?>
<script>
/* ══════════════════════════════════════════════════════
   KPI DETAIL — AJAX-based rich rendering
══════════════════════════════════════════════════════ */
/* Icon + title metadata per KPI key */
const KPI_META = {};
<?php foreach($kpiDetails as $k=>$v): ?>
KPI_META[<?= json_encode($k) ?>] = {icon:<?= json_encode($v['icon']) ?>, title:<?= json_encode($v['title']) ?>};
<?php endforeach; ?>

/* Chart.js instance registry — destroy before re-creating */
const _charts = {};
function destroyChart(id){ if(_charts[id]){ try{_charts[id].destroy();}catch(e){} delete _charts[id]; } }

/* ── Ripple + Pop animation ───────────────────────────────── */
function addRipple(card, e) {
    const r = document.createElement('div');
    r.className = 'kpi-ripple';
    const rect = card.getBoundingClientRect();
    r.style.left = (e.clientX - rect.left - 5) + 'px';
    r.style.top  = (e.clientY - rect.top  - 5) + 'px';
    card.appendChild(r);
    setTimeout(() => r.remove(), 600);
}

/* ── Open modal → fetch AJAX ────────────────────────────── */
function openKpiModal(card) {
    addRipple(card, window._kpiLastEvent || {clientX:0,clientY:0});
    card.classList.remove('kpi-pop');
    void card.offsetWidth;
    card.classList.add('kpi-pop');
    setTimeout(() => card.classList.remove('kpi-pop'), 350);

    const key  = card.dataset.kpi;
    const meta = KPI_META[key] || {icon:'📊', title:'Détails'};

    document.getElementById('kpiDrIcon').textContent  = meta.icon;
    document.getElementById('kpiDrTitle').textContent = meta.title;
    document.getElementById('kpiDrBody').innerHTML =
        `<div style="display:flex;align-items:center;justify-content:center;padding:48px;gap:14px;color:#64748b;">
            <div class="kpi-spin"></div>
            <span style="font-size:.85rem;">Chargement des données…</span>
        </div>`;
    document.getElementById('kpiOverlay').classList.add('show');
    document.body.style.overflow = 'hidden';

    Object.keys(_charts).forEach(id => destroyChart(id));

    fetch(BASE_URL + 'dashboard/kpi-details/' + key, {credentials:'same-origin'})
        .then(r => r.json())
        .then(data => {
            if (data.error) {
                document.getElementById('kpiDrBody').innerHTML =
                    `<div style="color:#f87171;padding:24px;text-align:center;">${data.error}</div>`;
                return;
            }
            document.getElementById('kpiDrBody').innerHTML = renderKpi(key, data);
            buildCharts(key, data);
        })
        .catch(err => {
            document.getElementById('kpiDrBody').innerHTML =
                `<div style="color:#f87171;padding:24px;text-align:center;">Erreur réseau : ${err.message}</div>`;
        });
}

/* ── HTML renderers ─────────────────────────────────────── */
function renderKpi(key, d) {
    const fmt  = n => Number(n||0).toLocaleString('fr-FR');
    const stat = (val, lbl, color='#60a5fa', sub='') =>
        `<div class="kd-stat"><div class="kd-sv" style="color:${color};">${val}</div><div class="kd-sl">${lbl}</div>${sub?`<div class="kd-ss">${sub}</div>`:''}</div>`;
    const mini = (id, title) =>
        `<div style="margin-top:14px;"><p class="kd-sec">${title}</p><div style="height:110px;position:relative;"><canvas id="${id}"></canvas></div></div>`;
    const sec  = (title, body) =>
        `<div style="margin-top:14px;"><p class="kd-sec">${title}</p>${body}</div>`;
    const tbl  = (headers, rows) => {
        const h = headers.map(h=>`<th>${h}</th>`).join('');
        const r = rows.map(row=>`<tr>${row.map(c=>`<td>${c}</td>`).join('')}</tr>`).join('');
        return `<table class="kd-table"><thead><tr>${h}</tr></thead><tbody>${r||`<tr><td colspan="${headers.length}" style="text-align:center;color:#64748b;padding:12px;">Aucune donnée</td></tr>`}</tbody></table>`;
    };
    const badge = (txt, color) => `<span class="kd-badge" style="background:${color}22;color:${color};">${txt}</span>`;
    const bar   = (pct, color, h=10) =>
        `<div class="kd-bar" style="height:${h}px;"><div class="kd-bar-f" style="width:${Math.min(100,pct)}%;background:${color};height:${h}px;"></div></div>`;

    switch(key) {

        /* ── PATIENTS ─────────────────────────────────────── */
        case 'patients': {
            const rows = (d.derniers||[]).map(p=>[
                `<strong>${p.prenom} ${(p.nom||'').toUpperCase()}</strong>`,
                `<span style="color:#64748b;font-size:.73rem;">${p.dossier_numero||'—'}</span>`,
                badge(p.sexe==='M'?'♂':p.sexe==='F'?'♀':'?', p.sexe==='M'?'#3b82f6':'#f43f5e'),
                p.date_insc||'—'
            ]);
            return `
            <div class="kd-grid">
                ${stat(fmt(d.total),'Total actifs','#60a5fa','Base complète')}
                ${stat(fmt(d.ce_mois),'Nouveaux ce mois','#10b981',new Date().toLocaleDateString('fr-FR',{month:'long',year:'numeric'}))}
                ${stat(fmt(d.mois_dernier),'Mois dernier','#8b5cf6','')}
            </div>
            ${mini('chPtTrend','Inscriptions — 12 derniers mois')}
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-top:14px;">
                <div><p class="kd-sec">Répartition par sexe</p><div style="height:130px;"><canvas id="chPtSex"></canvas></div></div>
                <div><p class="kd-sec">Tranches d'âge</p><div style="height:130px;"><canvas id="chPtAge"></canvas></div></div>
            </div>
            ${sec('8 derniers patients inscrits', tbl(['Nom','Dossier','Sexe','Date'], rows))}
            <a href="${BASE_URL}patients" class="kd-link" style="background:rgba(59,130,246,.15);color:#60a5fa;border:1px solid rgba(59,130,246,.3);">
                <i class="bi bi-people-fill"></i> Voir tous les patients
            </a>`;
        }

        /* ── CONSULTATIONS ──────────────────────────────── */
        case 'consultations': {
            const medRows  = (d.top_medecins||[]).map(m=>[
                `<strong>${m.prenom} ${(m.nom||'').toUpperCase()}</strong>`,
                `<span style="color:#64748b;">${m.specialite||'—'}</span>`,
                `<span style="color:#10b981;font-weight:700;">${m.nb}</span>`
            ]);
            const typeRows = (d.par_type||[]).map(t=>[t.type_consultation||'—', fmt(t.nb)]);
            return `
            <div class="kd-grid">
                ${stat(fmt(d.total_mois),'Consultations ce mois','#10b981','')}
                ${stat(fmt(d.mois_dernier),'Mois dernier','#06b6d4','')}
            </div>
            ${mini('chCons30','Tendance — 30 derniers jours')}
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-top:14px;">
                <div>${sec('Par type', tbl(['Type','Nb'], typeRows))}</div>
                <div><p class="kd-sec">Distribution types</p><div style="height:120px;"><canvas id="chConsType"></canvas></div></div>
            </div>
            ${sec('Top médecins ce mois', tbl(['Médecin','Spécialité','Nb'], medRows))}
            <a href="${BASE_URL}consultation" class="kd-link" style="background:rgba(16,185,129,.15);color:#6ee7b7;border:1px solid rgba(16,185,129,.3);">
                <i class="bi bi-clipboard2-pulse"></i> Voir les consultations
            </a>`;
        }

        /* ── HOSPITALISATIONS ───────────────────────────── */
        case 'hospitalisations': {
            const svcRows = (d.par_service||[]).map(s=>[
                s.nom_service,
                `<span style="color:#38bdf8;font-weight:700;">${s.nb_actives}</span>`,
                `<span style="color:#f59e0b;">${Math.round(s.dms_service||0)}j</span>`
            ]);
            const recRows = (d.recents||[]).map(p=>[
                `<strong>${p.prenom} ${(p.nom||'').toUpperCase()}</strong>`,
                `<span style="color:#64748b;font-size:.73rem;">${p.dossier_numero||'—'}</span>`,
                p.nom_service||'—',
                `<span style="color:#f59e0b;">${p.jours}j</span>`
            ]);
            return `
            <div class="kd-grid">
                ${stat(d.actives,'Hospitalisés actifs','#f59e0b','En cours')}
                ${stat(d.ce_mois,'Admissions ce mois','#3b82f6','')}
                ${stat(d.sorties_mois,'Sorties ce mois','#10b981','')}
                ${stat((d.dms||0)+'j','DMS globale','#a78bfa','')}
            </div>
            ${mini('chHosTrend','Admissions — 6 derniers mois')}
            ${sec('Par service', tbl(['Service','Actifs','DMS moy.'], svcRows))}
            ${sec('10 patients récemment admis', tbl(['Patient','Dossier','Service','Durée'], recRows))}
            <a href="${BASE_URL}hospitalisation" class="kd-link" style="background:rgba(245,158,11,.15);color:#fbbf24;border:1px solid rgba(245,158,11,.3);">
                <i class="bi bi-hospital"></i> Voir les hospitalisations
            </a>`;
        }

        /* ── LITS ───────────────────────────────────────── */
        case 'lits': {
            const tot = d.total||1;
            const pct = Math.round((d.occupes||0)/tot*100);
            const c   = pct>80?'#ef4444':pct>50?'#f59e0b':'#10b981';
            const svcRows = (d.par_service||[]).map(s=>{
                const p = parseFloat(s.taux||0);
                const sc = p>80?'#ef4444':p>50?'#f59e0b':'#10b981';
                return [
                    `<strong>${s.nom_service}</strong>`,
                    `<span style="color:#38bdf8;">${s.occupes}/${s.total}</span>`,
                    `<div style="display:flex;align-items:center;gap:6px;">${bar(p,sc,6)}<span style="color:${sc};font-size:.7rem;font-weight:700;">${p}%</span></div>`
                ];
            });
            return `
            <div class="kd-grid">
                ${stat(pct+'%','Taux d\'occupation',c,'')}
                ${stat(d.occupes||0,'Lits occupés','#f87171','')}
                ${stat(d.libres||0,'Lits disponibles','#34d399','')}
                ${stat(d.maintenance||0,'Maintenance','#f59e0b','')}
                ${stat(tot,'Capacité totale','#60a5fa','')}
            </div>
            <div style="margin-top:10px;">${bar(pct,c,10)}</div>
            ${mini('chLitsServ','Taux d\'occupation par service (%)')}
            ${sec('Détail par service', tbl(['Service','Occ./Total','Taux'], svcRows))}`;
        }

        /* ── URGENCES ───────────────────────────────────── */
        case 'urgences': {
            const triage = {P1:'#ef4444',P2:'#f97316',P3:'#f59e0b',P4:'#10b981',P5:'#3b82f6'};
            const critRows = (d.critiques||[]).map(p=>[
                `<strong>${p.prenom||''} ${(p.nom||'').toUpperCase()}</strong>`,
                badge(p.niveau_triage||'?', triage[p.niveau_triage]||'#64748b'),
                `<span style="color:#64748b;font-size:.73rem;">${(p.motif_admission||'—').substring(0,40)}</span>`,
                `<span style="color:#f87171;">${p.attente_min||0}min</span>`
            ]);
            return `
            <div class="kd-grid">
                ${stat(d.en_cours||0,'En cours','#ef4444','ATTENTE + EN_COURS')}
                ${stat(d.aujourd_hui||0,'Admissions auj.','#f59e0b','')}
                ${stat(d.total_mois||0,'Total ce mois','#60a5fa','')}
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-top:14px;">
                <div><p class="kd-sec">Tendance 7 jours</p><div style="height:110px;"><canvas id="chUrg7j"></canvas></div></div>
                <div><p class="kd-sec">Distribution triage actif</p><div style="height:110px;"><canvas id="chUrgTriage"></canvas></div></div>
            </div>
            ${critRows.length>0 ? sec('Patients critiques P1/P2',tbl(['Patient','Triage','Motif','Attente'],critRows))
              : '<div style="color:#10b981;text-align:center;padding:18px;font-size:.83rem;"><i class="bi bi-check-circle-fill me-2"></i>Aucune urgence critique en cours</div>'}
            <a href="${BASE_URL}urgences" class="kd-link" style="background:rgba(239,68,68,.15);color:#f87171;border:1px solid rgba(239,68,68,.3);">
                <i class="bi bi-heart-pulse-fill"></i> Voir les urgences
            </a>`;
        }

        /* ── LABO ───────────────────────────────────────── */
        case 'labo': {
            const catRows = (d.par_categorie||[]).map(c=>[
                c.categorie||'—',
                `<span style="color:#38bdf8;font-weight:700;">${c.nb}</span>`
            ]);
            const topRows = (d.top_examens||[]).map(e=>[
                e.nom||'—',
                `<span style="color:#10b981;">${e.nb}</span>`
            ]);
            return `
            <div class="kd-grid">
                ${stat(fmt(d.total_mois),'Demandes ce mois','#0891b2','')}
                ${stat(fmt(d.mois_dernier),'Mois dernier','#06b6d4','')}
                ${stat(d.en_attente||0,'En attente/cours','#f59e0b','')}
                ${stat(d.urgent||0,'Urgents','#ef4444','')}
            </div>
            ${mini('chLabo30','Tendance — 30 derniers jours')}
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-top:14px;">
                <div><p class="kd-sec">Par catégorie</p><div style="height:120px;"><canvas id="chLaboCat"></canvas></div></div>
                <div>${sec('Top 8 examens (30j)', tbl(['Examen','Nb'], topRows))}</div>
            </div>
            ${sec('Répartition par catégorie', tbl(['Catégorie','Demandes'], catRows))}
            <a href="${BASE_URL}laboratoire" class="kd-link" style="background:rgba(8,145,178,.15);color:#38bdf8;border:1px solid rgba(8,145,178,.3);">
                <i class="bi bi-flask"></i> Voir le laboratoire
            </a>`;
        }

        /* ── ATTENTE ────────────────────────────────────── */
        case 'attente': {
            const m   = d.moy_minutes||0;
            const c   = m>120?'#ef4444':m>45?'#f59e0b':'#10b981';
            const lbl = m>120?'🔴 Longue':m>45?'🟡 Modérée':'🟢 Bonne fluidité';
            const mf  = m>=60 ? `${Math.floor(m/60)}h${String(m%60).padStart(2,'0')}` : m+'min';
            const distRows = (d.distribution||[]).map(t=>[t.tranche||'—',`<span style="color:#818cf8;">${t.nb}</span>`]);
            return `
            <div class="kd-grid">
                ${stat(mf,'Temps moyen (30j)',c,'')}
                ${stat(lbl,'Classification',c,'')}
            </div>
            ${mini('chAtt30','Temps moyen quotidien (min) — 30j')}
            ${mini('chAttDist','Distribution des temps d\'attente')}
            ${sec('Distribution', tbl(['Tranche d\'attente','Nb consultations'], distRows))}
            <div style="color:#64748b;font-size:.73rem;margin-top:10px;padding:8px;background:rgba(255,255,255,.02);border-radius:8px;border:1px solid rgba(255,255,255,.05);">
                Seuils : <span style="color:#10b981;">≤45min = Bonne fluidité</span> &nbsp;·&nbsp;
                <span style="color:#f59e0b;">45–120min = Modérée</span> &nbsp;·&nbsp;
                <span style="color:#ef4444;">&gt;120min = Longue</span>
            </div>`;
        }

        /* ── RDV ────────────────────────────────────────── */
        case 'rdv-today': case 'rdv-mois': case 'rdv-honores': case 'rdv-taux': {
            const th = d.total_mois>0 ? Math.round((d.honores_mois||0)/d.total_mois*100) : 0;
            const hc = th>=80?'#10b981':th>=50?'#f59e0b':'#ef4444';
            const medRows = (d.par_medecin||[]).map(m=>[
                `<strong>${m.prenom||''} ${(m.nom||'').toUpperCase()}</strong>`,
                `<span style="color:#64748b;">${m.specialite||'—'}</span>`,
                `<span style="color:#3b82f6;">${m.total}</span>`,
                `<span style="color:#10b981;">${m.honores||0}</span>`,
                `<span style="color:${m.total>0&&(m.honores||0)/m.total>=.8?'#10b981':'#f59e0b'};">${m.total>0?Math.round((m.honores||0)/m.total*100):0}%</span>`
            ]);
            return `
            <div class="kd-grid">
                ${stat(d.aujourd_hui||0,'RDV aujourd\'hui','#8b5cf6',new Date().toLocaleDateString('fr-FR'))}
                ${stat(d.total_mois||0,'Total ce mois','#3b82f6','')}
                ${stat(d.honores_mois||0,'Honorés','#10b981','')}
                ${stat(d.annules_mois||0,'Annulés','#ef4444','')}
            </div>
            <div style="margin-top:10px;">
                <p class="kd-sec">Taux d'honoration : <span style="color:${hc};font-weight:700;">${th}%</span></p>
                ${bar(th,hc,10)}
            </div>
            ${mini('chRdv30','Tendance RDV — 30 derniers jours')}
            ${mini('chRdvMed','Volume par médecin')}
            ${sec('Détail par médecin', tbl(['Médecin','Spécialité','Total','Honorés','Taux'], medRows))}`;
        }

        /* ── PHARMACIE ──────────────────────────────────── */
        case 'pharmacie': {
            const niv   = {rupture:'#ef4444',critique:'#f97316',alerte:'#f59e0b'};
            const lbl   = {rupture:'Rupture',critique:'Critique',alerte:'Alerte'};
            const rows  = (d.stocks||[]).map(m=>[
                `<strong>${m.nom||'—'}</strong>`,
                `<span style="color:${niv[m.niveau]||'#f59e0b'};font-weight:700;">${m.stock_actuel}</span>`,
                `<span style="color:#64748b;">${m.seuil||0}</span>`,
                badge(lbl[m.niveau]||'Alerte', niv[m.niveau]||'#f59e0b')
            ]);
            const topRows = (d.top_prescrits||[]).map(m=>[m.nom||'—',`<span style="color:#38bdf8;">${m.nb}</span>`]);
            return `
            <div class="kd-grid">
                ${stat(d.ruptures||0,'En rupture','#ef4444','')}
                ${stat(d.critiques||0,'Stock critique','#f97316','')}
                ${stat(d.alertes||0,'En alerte','#f59e0b','')}
            </div>
            ${rows.length>0 ? sec('Médicaments en alerte/rupture', tbl(['Médicament','Stock','Seuil','Niveau'], rows))
              : '<div style="color:#10b981;text-align:center;padding:18px;font-size:.83rem;"><i class="bi bi-check-circle-fill me-2"></i>Aucune alerte stock</div>'}
            ${topRows.length>0 ? sec('Top 8 médicaments prescrits (30j)', tbl(['Médicament','Prescriptions'], topRows)) : ''}
            <a href="${BASE_URL}pharmacie" class="kd-link" style="background:rgba(6,182,212,.15);color:#38bdf8;border:1px solid rgba(6,182,212,.3);">
                <i class="bi bi-capsule"></i> Gérer la pharmacie
            </a>`;
        }

        /* ── PERSONNEL ──────────────────────────────────── */
        case 'personnel': {
            const roleRows  = (d.par_role||[]).map(r=>[r.role||'—',`<span style="color:var(--clk);font-weight:700;">${r.nb}</span>`]);
            const loginRows = (d.derniere_connexion||[]).map(u=>[
                `<strong>${u.prenom||''} ${(u.nom||'').toUpperCase()}</strong>`,
                badge(u.role||'—','#3b82f6'),
                `<span style="color:#94a3b8;font-size:.73rem;">${u.last_fmt||'—'}</span>`
            ]);
            const totalActifs = (d.actifs_7j||[]).reduce((s,r)=>s+(r.nb_actifs||0),0);
            return `
            <div class="kd-grid">
                ${stat(d.total_actif||0,'Utilisateurs actifs','var(--clk)','')}
                ${stat(totalActifs,'Connectés (7j)','#10b981','')}
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-top:14px;">
                <div><p class="kd-sec">Répartition par rôle</p><div style="height:160px;"><canvas id="chPersoRole"></canvas></div></div>
                <div>${sec('Effectif par rôle', tbl(['Rôle','Nb'], roleRows))}</div>
            </div>
            ${loginRows.length>0 ? sec('Dernières connexions (7j)', tbl(['Utilisateur','Rôle','Dernière connexion'], loginRows)) : ''}`;
        }

        /* ── CHIRURGIES ─────────────────────────────────── */
        case 'chirurgies': {
            const typeRows = (d.par_type||[]).map(t=>[t.type_intervention||'—',`<span style="color:#a78bfa;">${t.nb}</span>`]);
            return `
            <div class="kd-grid">
                ${stat(d.ce_mois||0,'Interventions ce mois','#8b5cf6','')}
                ${stat(d.mois_dernier||0,'Mois dernier','#a78bfa','')}
            </div>
            ${mini('chChir6m','Tendance — 6 derniers mois')}
            ${typeRows.length>0 ? `
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-top:14px;">
                <div><p class="kd-sec">Par type</p><div style="height:120px;"><canvas id="chChirType"></canvas></div></div>
                <div>${sec('Types d\'intervention', tbl(['Type','Nb'], typeRows))}</div>
            </div>` : ''}`;
        }

        default:
            return `<div style="color:#64748b;padding:24px;text-align:center;">Type de KPI inconnu : ${key}</div>`;
    }
}

/* ── Build Chart.js instances after HTML is inserted ──── */
function buildCharts(key, d) {
    const cfgBase = {
        responsive:true, maintainAspectRatio:false,
        plugins:{legend:{display:false}, tooltip:{mode:'index',intersect:false,
            backgroundColor:'rgba(15,23,42,.92)',titleColor:'#f1f5f9',bodyColor:'#cbd5e1',borderWidth:0}},
        scales:{
            x:{grid:{color:'rgba(255,255,255,.04)'},ticks:{color:'#64748b',font:{size:9},maxTicksLimit:8}},
            y:{grid:{color:'rgba(255,255,255,.06)'},ticks:{color:'#64748b',font:{size:9}},beginAtZero:true}
        }
    };
    const clamp = n => Math.max(0, Number(n)||0);

    function line(id, labels, vals, color='#3b82f6') {
        const el=document.getElementById(id); if(!el)return;
        destroyChart(id);
        _charts[id] = new Chart(el,{type:'line',data:{labels,datasets:[{
            data:vals.map(clamp),borderColor:color,backgroundColor:color+'1a',
            fill:true,tension:.4,pointRadius:2,borderWidth:2,pointHoverRadius:4
        }]},options:{...cfgBase}});
    }
    function bar(id, labels, vals, color='#3b82f6') {
        const el=document.getElementById(id); if(!el)return;
        destroyChart(id);
        _charts[id] = new Chart(el,{type:'bar',data:{labels,datasets:[{
            data:vals.map(clamp),backgroundColor:color+'44',borderColor:color,borderWidth:1.5,borderRadius:3
        }]},options:{...cfgBase}});
    }
    function donut(id, labels, vals, colors) {
        const el=document.getElementById(id); if(!el)return;
        destroyChart(id);
        _charts[id] = new Chart(el,{type:'doughnut',data:{labels,datasets:[{
            data:vals.map(clamp),backgroundColor:colors,borderWidth:0,hoverOffset:4
        }]},options:{responsive:true,maintainAspectRatio:false,
            plugins:{legend:{display:true,position:'right',labels:{color:'#94a3b8',font:{size:10},boxWidth:10,padding:6}}}}});
    }

    const PALETTE = ['#3b82f6','#10b981','#f59e0b','#ef4444','#8b5cf6','#06b6d4','#f43f5e','#84cc16','#fb923c','#a78bfa'];

    switch(key) {
        case 'patients':
            if(d.tendance)      line('chPtTrend', d.tendance.map(r=>r.label), d.tendance.map(r=>r.nb));
            if(d.par_sexe){
                const s=d.par_sexe;
                donut('chPtSex',['Hommes','Femmes','Autre'],[clamp(s.M||s.m||0),clamp(s.F||s.f||0),clamp(s.A||s.a||0)],['#3b82f6','#f43f5e','#8b5cf6']);
            }
            if(d.par_age)       bar('chPtAge', d.par_age.map(r=>r.tranche.replace(/\s*\(.*\)$/,'')), d.par_age.map(r=>r.nb),'#8b5cf6');
            break;
        case 'consultations':
            if(d.tendance_30j)  line('chCons30',d.tendance_30j.map(r=>r.label),d.tendance_30j.map(r=>r.nb),'#10b981');
            if(d.par_type)      bar('chConsType',d.par_type.map(r=>r.type_consultation||'—'),d.par_type.map(r=>r.nb),'#06b6d4');
            break;
        case 'hospitalisations':
            if(d.tendance)      line('chHosTrend',d.tendance.map(r=>r.label),d.tendance.map(r=>r.nb),'#f59e0b');
            break;
        case 'lits':
            if(d.par_service)   bar('chLitsServ',d.par_service.map(r=>r.nom_service),d.par_service.map(r=>parseFloat(r.taux||0)),'#ef4444');
            break;
        case 'urgences':
            if(d.tendance_7j)   line('chUrg7j',d.tendance_7j.map(r=>r.label),d.tendance_7j.map(r=>r.nb),'#ef4444');
            if(d.par_triage){
                const colors=d.par_triage.map(r=>({P1:'#ef4444',P2:'#f97316',P3:'#f59e0b',P4:'#10b981',P5:'#3b82f6'}[r.niveau_triage]||'#64748b'));
                bar('chUrgTriage',d.par_triage.map(r=>r.niveau_triage),d.par_triage.map(r=>r.nb));
                // override colors manually
                if(_charts['chUrgTriage']) _charts['chUrgTriage'].data.datasets[0].backgroundColor=colors.map(c=>c+'44');
                if(_charts['chUrgTriage']) _charts['chUrgTriage'].data.datasets[0].borderColor=colors;
                if(_charts['chUrgTriage']) _charts['chUrgTriage'].update();
            }
            break;
        case 'labo':
            if(d.tendance_30j)  line('chLabo30',d.tendance_30j.map(r=>r.label),d.tendance_30j.map(r=>r.nb),'#0891b2');
            if(d.par_categorie) bar('chLaboCat',d.par_categorie.map(r=>r.categorie||'—'),d.par_categorie.map(r=>r.nb),'#06b6d4');
            break;
        case 'attente':
            if(d.tendance_30j)  line('chAtt30',d.tendance_30j.map(r=>r.label),d.tendance_30j.map(r=>r.moy_min),'#6366f1');
            if(d.distribution)  bar('chAttDist',d.distribution.map(r=>r.tranche),d.distribution.map(r=>r.nb),'#818cf8');
            break;
        case 'rdv-today': case 'rdv-mois': case 'rdv-honores': case 'rdv-taux':
            if(d.tendance_30j)  line('chRdv30',d.tendance_30j.map(r=>r.label),d.tendance_30j.map(r=>r.nb),'#8b5cf6');
            if(d.par_medecin)   bar('chRdvMed',d.par_medecin.map(r=>(r.prenom||'').charAt(0)+'. '+(r.nom||'').toUpperCase()),d.par_medecin.map(r=>r.total),'#a78bfa');
            break;
        case 'pharmacie':
            break;
        case 'personnel':
            if(d.par_role)      donut('chPersoRole',d.par_role.map(r=>r.role),d.par_role.map(r=>r.nb),PALETTE);
            break;
        case 'chirurgies':
            if(d.tendance)      line('chChir6m',d.tendance.map(r=>r.label),d.tendance.map(r=>r.nb),'#8b5cf6');
            if(d.par_type)      bar('chChirType',d.par_type.map(r=>r.type_intervention||'—'),d.par_type.map(r=>r.nb),'#c084fc');
            break;
    }
}

/* ── Close modal ─────────────────────────────────────── */
function closeKpiModal() {
    document.getElementById('kpiOverlay').classList.remove('show');
    document.body.style.overflow = '';
    Object.keys(_charts).forEach(id => destroyChart(id));
}

function kpiOverlayClick(e) {
    if (e.target === document.getElementById('kpiOverlay')) closeKpiModal();
}

document.addEventListener('mousemove', e => window._kpiLastEvent = e);
document.addEventListener('click',     e => window._kpiLastEvent = e);
document.addEventListener('keydown',   e => { if (e.key === 'Escape') closeKpiModal(); });
</script>

<!-- ═══════════ FOOTER ═══════════ -->
<div class="text-center mt-2 mb-2" style="color:#334155;font-size:.68rem;">
    Hôpital Saint-Jean de Malte — Njombé, Cameroun &nbsp;·&nbsp;
    Mise à jour : <?= date('d/m/Y à H:i:s') ?> &nbsp;·&nbsp; DME v2026
    &nbsp;·&nbsp; <span style="color:var(--acc);">Thème : <span id="footerThemeName">—</span></span>
</div>

</div><!-- /dir-content -->


<!-- ══════════════════════════════════════════════════════ JAVASCRIPT ══ -->
<script>
/* ═══════════════════════════════════════════════
   THÈMES
═══════════════════════════════════════════════ */
const THEMES = [
    { id:'emerald', name:'Émeraude',  clk:'#00ff87', glow:'rgba(0,255,135,.4)',   acc:'#10b981', dim:'rgba(16,185,129,.12)', border:'rgba(16,185,129,.3)',  bg:'linear-gradient(135deg,#0a1a12,#1e293b)' },
    { id:'sapphire',name:'Saphir',    clk:'#60a5fa', glow:'rgba(96,165,250,.4)',  acc:'#3b82f6', dim:'rgba(59,130,246,.12)', border:'rgba(59,130,246,.3)',   bg:'linear-gradient(135deg,#0d1a2d,#1e293b)' },
    { id:'amethyst',name:'Améthyste', clk:'#c084fc', glow:'rgba(192,132,252,.4)', acc:'#8b5cf6', dim:'rgba(139,92,246,.12)', border:'rgba(139,92,246,.3)',   bg:'linear-gradient(135deg,#130d2d,#1e293b)' },
    { id:'coral',   name:'Corail',    clk:'#fb923c', glow:'rgba(251,146,60,.4)',  acc:'#f97316', dim:'rgba(249,115,22,.12)', border:'rgba(249,115,22,.3)',   bg:'linear-gradient(135deg,#1f0d05,#1e293b)' },
    { id:'rose',    name:'Rose',      clk:'#fb7185', glow:'rgba(251,113,133,.4)', acc:'#f43f5e', dim:'rgba(244,63,94,.12)',  border:'rgba(244,63,94,.3)',    bg:'linear-gradient(135deg,#1f0a10,#1e293b)' },
    { id:'arctic',  name:'Arctique',  clk:'#67e8f9', glow:'rgba(103,232,249,.4)', acc:'#06b6d4', dim:'rgba(6,182,212,.12)',  border:'rgba(6,182,212,.3)',    bg:'linear-gradient(135deg,#051820,#1e293b)' },
    { id:'gold',    name:'Or',        clk:'#fbbf24', glow:'rgba(251,191,36,.4)',  acc:'#d97706', dim:'rgba(217,119,6,.12)',  border:'rgba(217,119,6,.3)',    bg:'linear-gradient(135deg,#1a1205,#1e293b)' },
    { id:'silver',  name:'Argent',    clk:'#cbd5e1', glow:'rgba(203,213,225,.3)', acc:'#94a3b8', dim:'rgba(148,163,184,.1)', border:'rgba(148,163,184,.2)',  bg:'linear-gradient(135deg,#0f172a,#1e293b)' },
];

let currentTheme = localStorage.getItem('dir_theme') || 'emerald';

function applyTheme(id, save = true) {
    const t = THEMES.find(x => x.id === id) || THEMES[0];
    const root = document.documentElement;
    root.style.setProperty('--clk', t.clk);
    root.style.setProperty('--clk-glow', t.glow);
    root.style.setProperty('--acc', t.acc);
    root.style.setProperty('--acc-dim', t.dim);
    root.style.setProperty('--acc-border', t.border);
    currentTheme = t.id;
    if (save) localStorage.setItem('dir_theme', t.id);
    // Mettre à jour le swatch actif
    document.querySelectorAll('.theme-swatch').forEach(s => {
        s.classList.toggle('active', s.dataset.id === t.id);
    });
    // Footer
    const fn = document.getElementById('footerThemeName');
    if (fn) fn.textContent = t.name;
}

function buildThemeGrid() {
    const grid = document.getElementById('themeGrid');
    if (!grid) return;
    grid.innerHTML = '';
    THEMES.forEach(t => {
        const wrap = document.createElement('div');
        wrap.style.cssText = 'display:flex;flex-direction:column;align-items:center;gap:4px;';
        const sw = document.createElement('div');
        sw.className = 'theme-swatch' + (t.id === currentTheme ? ' active' : '');
        sw.dataset.id = t.id;
        sw.style.cssText = `background:radial-gradient(circle at 35% 35%, ${t.clk}, ${t.acc});`;
        sw.title = t.name;
        sw.onclick = () => { applyTheme(t.id); };
        const lbl = document.createElement('div');
        lbl.className = 'theme-swatch-label';
        lbl.textContent = t.name;
        wrap.appendChild(sw);
        wrap.appendChild(lbl);
        grid.appendChild(wrap);
    });
}

function toggleThemePanel() {
    const p = document.getElementById('themePanel');
    p.classList.toggle('show');
}

// Fermer en cliquant en dehors
document.addEventListener('click', e => {
    const w = document.getElementById('themeWrapper');
    if (w && !w.contains(e.target)) {
        document.getElementById('themePanel')?.classList.remove('show');
    }
});

// Init
buildThemeGrid();
applyTheme(currentTheme, false);

/* ═══════════════════════════════════════════════
   HORLOGE
═══════════════════════════════════════════════ */
function updateClock() {
    document.getElementById('dir-clock').textContent = new Date().toLocaleTimeString('fr-FR');
}
setInterval(updateClock, 1000);
updateClock();

/* ═══════════════════════════════════════════════
   DONNÉES PHP → JS
═══════════════════════════════════════════════ */
const patientsMoisData = <?= json_encode($patients_par_mois) ?>;
const consultHospData  = <?= json_encode($consult_vs_hosp) ?>;
const pathologiesData  = <?= json_encode($top_pathologies) ?>;
const heuresData       = <?= json_encode($admissions_par_heure) ?>;
const rdvEvolutionData = <?= json_encode($rdv_evolution_mois ?? []) ?>;
const laboData         = <?= json_encode($labo_par_mois) ?>;

const PALETTE = ['#3b82f6','#10b981','#f59e0b','#ef4444','#8b5cf6','#06b6d4','#f97316','#34d399','#a78bfa','#fb923c'];
const CHART_GRID = 'rgba(255,255,255,0.04)';
const CHART_TICK = '#64748b';

Chart.defaults.font.family = "'Inter', system-ui, sans-serif";
Chart.defaults.font.size   = 11;
Chart.defaults.color       = CHART_TICK;

/* ── Patients par mois ──────────────────────────────────────── */
if (patientsMoisData.length > 0) {
    new Chart(document.getElementById('chartPatientsMois'), {
        type:'line',
        data:{
            labels: patientsMoisData.map(d=>d.label),
            datasets:[{
                label:'Nouveaux patients',
                data: patientsMoisData.map(d=>d.nb),
                borderColor:'#10b981', backgroundColor:'rgba(16,185,129,0.08)',
                fill:true, tension:0.4, pointRadius:4,
                pointBackgroundColor:'#10b981', pointBorderColor:'#0f172a', pointBorderWidth:2
            }]
        },
        options:{
            responsive:true,
            plugins:{ legend:{display:false}, tooltip:{mode:'index'} },
            scales:{
                x:{ grid:{color:CHART_GRID}, ticks:{color:CHART_TICK} },
                y:{ grid:{color:CHART_GRID}, ticks:{color:CHART_TICK,precision:0}, beginAtZero:true }
            }
        }
    });
}

/* ── Consultations vs Hospitalisations ──────────────────────── */
if (consultHospData.length > 0) {
    new Chart(document.getElementById('chartConsultHosp'), {
        type:'bar',
        data:{
            labels: consultHospData.map(d=>d.label),
            datasets:[
                { label:'Consultations',     data:consultHospData.map(d=>d.nb_consult), backgroundColor:'rgba(6,182,212,.7)',  borderColor:'#06b6d4', borderWidth:1, borderRadius:5 },
                { label:'Hospitalisations',  data:consultHospData.map(d=>d.nb_hosp),    backgroundColor:'rgba(249,115,22,.7)', borderColor:'#f97316', borderWidth:1, borderRadius:5 }
            ]
        },
        options:{
            responsive:true,
            plugins:{ legend:{labels:{color:'#94a3b8',font:{size:11}}} },
            scales:{ x:{grid:{color:CHART_GRID},ticks:{color:CHART_TICK}}, y:{grid:{color:CHART_GRID},ticks:{color:CHART_TICK,precision:0}} }
        }
    });
}

/* ── Top Pathologies ────────────────────────────────────────── */
if (pathologiesData.length > 0) {
    new Chart(document.getElementById('chartPathologies'), {
        type:'bar',
        data:{
            labels: pathologiesData.map(d=>d.pathologie.length>28?d.pathologie.slice(0,26)+'…':d.pathologie),
            datasets:[{ label:'Cas', data:pathologiesData.map(d=>d.nb), backgroundColor:PALETTE.map(c=>c+'cc'), borderColor:PALETTE, borderWidth:1, borderRadius:5 }]
        },
        options:{
            indexAxis:'y', responsive:true,
            plugins:{ legend:{display:false} },
            scales:{ x:{grid:{color:CHART_GRID},ticks:{color:CHART_TICK,precision:0}}, y:{grid:{display:false},ticks:{color:'#cbd5e1',font:{size:11}}} }
        }
    });
}

/* ── Distribution Horaire ───────────────────────────────────── */
(function(){
    const hMap={};
    heuresData.forEach(d=>{ hMap[parseInt(d.heure)]=parseInt(d.nb); });
    const l24 = Array.from({length:24},(_,i)=>i+'h');
    const d24 = Array.from({length:24},(_,i)=>hMap[i]||0);
    const mx  = Math.max(...d24,1);
    new Chart(document.getElementById('chartHeures'),{
        type:'bar',
        data:{
            labels:l24,
            datasets:[{
                label:'Consultations', data:d24,
                backgroundColor: d24.map(v=>`rgba(56,189,248,${(.2+v/mx*.8).toFixed(2)})`),
                borderColor:'rgba(56,189,248,.4)', borderWidth:1, borderRadius:4
            }]
        },
        options:{
            responsive:true,
            plugins:{ legend:{display:false}, tooltip:{callbacks:{title:ctx=>ctx[0].label+':00 — '+ctx[0].label+':59'}} },
            scales:{ x:{grid:{color:CHART_GRID},ticks:{color:CHART_TICK,font:{size:10}}}, y:{grid:{color:CHART_GRID},ticks:{color:CHART_TICK,precision:0}} }
        }
    });
})();

/* ── Labo par mois ──────────────────────────────────────────── */
if (laboData.length > 0) {
    new Chart(document.getElementById('chartLabo'),{
        type:'bar',
        data:{
            labels: laboData.map(d=>d.label),
            datasets:[
                { label:'Demandes totales', data:laboData.map(d=>d.nb),        backgroundColor:'rgba(56,189,248,.6)',  borderColor:'#38bdf8', borderWidth:1, borderRadius:5 },
                { label:'Rendus',           data:laboData.map(d=>d.nb_rendus), backgroundColor:'rgba(16,185,129,.6)', borderColor:'#10b981', borderWidth:1, borderRadius:5 }
            ]
        },
        options:{
            responsive:true,
            plugins:{ legend:{labels:{color:'#94a3b8',font:{size:10},boxWidth:10}} },
            scales:{ x:{grid:{color:CHART_GRID},ticks:{color:CHART_TICK}}, y:{grid:{color:CHART_GRID},ticks:{color:CHART_TICK,precision:0}} }
        }
    });
}

/* ── Évolution RDV ──────────────────────────────────────────── */
if (rdvEvolutionData.length > 0) {
    new Chart(document.getElementById('chartRdvEvolution'),{
        type:'bar',
        data:{
            labels: rdvEvolutionData.map(d=>d.label),
            datasets:[
                { label:'Total',    data:rdvEvolutionData.map(d=>d.total),   backgroundColor:'rgba(139,92,246,.6)',  borderColor:'#8b5cf6', borderWidth:1, borderRadius:4 },
                { label:'Honorés',  data:rdvEvolutionData.map(d=>d.honores), backgroundColor:'rgba(16,185,129,.6)', borderColor:'#10b981', borderWidth:1, borderRadius:4 },
                { label:'Annulés',  data:rdvEvolutionData.map(d=>d.annules), backgroundColor:'rgba(239,68,68,.5)',  borderColor:'#ef4444', borderWidth:1, borderRadius:4 }
            ]
        },
        options:{
            responsive:true,
            plugins:{ legend:{labels:{color:'#94a3b8',font:{size:10},boxWidth:10}} },
            scales:{ x:{grid:{color:CHART_GRID},ticks:{color:CHART_TICK,font:{size:10}}}, y:{grid:{color:CHART_GRID},ticks:{color:CHART_TICK,precision:0}} }
        }
    });
}

/* ── Auto-refresh ───────────────────────────────────────────── */
setTimeout(()=>location.reload(), 300000); // 5 min
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
