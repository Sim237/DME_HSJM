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

require_once __DIR__ . '/../layouts/header.php';

$a_hospitaliser      = $a_hospitaliser      ?? [];
$lits_service        = $lits_service        ?? [];
$plans_du_jour       = $plans_du_jour       ?? [];
$lits_global         = $lits_global         ?? [];
$patients_service    = $patients_service    ?? [];
$historique_patients = $historique_patients ?? [];

// Grouper les lits par chambre
$lits_par_chambre = [];
foreach ($lits_service as $l) {
    $nomCh = $l['nom_chambre'] ?? 'Sans chambre';
    $lits_par_chambre[$nomCh][] = $l;
}
ksort($lits_par_chambre);

// KPI rapides
$nb_patients  = count($patients_service);
$nb_lits_lib  = count(array_filter($lits_service, fn($l) => empty($l['occupied_by_patient_id'])));
$nb_lits_tot  = count($lits_service);
// Lits empruntés : physiquement dans ce service mais patient d'un autre service
$_infServiceId = (int)($_SESSION['service_id'] ?? 0);
$nb_lits_empruntes = count(array_filter($lits_service, fn($l) =>
    !empty($l['occupied_by_patient_id']) &&
    !empty($l['patient_service_id']) &&
    (int)$l['patient_service_id'] !== $_infServiceId
));
$nb_soins_tot = count($plans_du_jour);
$nb_soins_ok  = count(array_filter($plans_du_jour, fn($p) => $p['total_soins'] > 0 && $p['total_soins'] == $p['soins_faits']));
$nb_alertes   = count($a_hospitaliser);

// Compteurs soins :
//   - nb_soins_individuels_total   : tous les soins non annulés de l'admission
//   - nb_soins_individuels_attente : soins non exécutés (inclut futur) — badge onglet
//   - nb_soins_en_retard           : soins non exécutés dont la date_prevue est DÉPASSÉE → alerte
$nb_soins_individuels_attente = 0;
$nb_soins_individuels_total   = 0;
$nb_soins_en_retard           = 0;
$nb_patients_en_retard        = 0;
foreach ($plans_du_jour as $p) {
    $nb_soins_individuels_total   += (int)($p['total_soins'] ?? 0);
    $nb_soins_individuels_attente += max(0, (int)($p['total_soins'] ?? 0) - (int)($p['soins_faits'] ?? 0));
    $retard = (int)($p['soins_en_retard'] ?? 0);
    $nb_soins_en_retard += $retard;
    if ($retard > 0) $nb_patients_en_retard++;
}
?>

<style>
/* ══ RESET & BASE ═══════════════════════════════════════════════════════════ */
.sidebar { display:none !important; }
main { margin-left:0 !important; width:100% !important; min-height:100vh;
       background:#f0f4f8; font-family:'Inter',system-ui,sans-serif; }

/* ══ HEADER ═════════════════════════════════════════════════════════════════ */
.inf-header {
    background:#fff;
    border-bottom:1px solid #e8eef5;
    box-shadow:0 2px 16px rgba(37,99,235,.07);
    padding:14px 28px;
    display:flex; align-items:center; justify-content:space-between; gap:16px;
    position:sticky; top:0; z-index:1000;
}
.inf-header-brand { display:flex; align-items:center; gap:14px; }
.inf-header-logo {
    width:46px; height:46px; border-radius:12px;
    background:linear-gradient(135deg,#3b82f6,#1d4ed8);
    display:flex; align-items:center; justify-content:center;
    box-shadow:0 4px 12px rgba(37,99,235,.25); overflow:hidden;
}
.inf-header-logo img { height:36px; filter:brightness(0) invert(1); }
.inf-service-name { font-size:1.1rem; font-weight:800; color:#1e293b; line-height:1.1; }
.inf-service-sub  { font-size:.72rem; color:#64748b; font-weight:500; }
.inf-clock {
    font-family:'JetBrains Mono',monospace; font-size:1.45rem; font-weight:800;
    color:#3b82f6; background:#eff6ff; border:1.5px solid #bfdbfe;
    border-radius:12px; padding:6px 16px; letter-spacing:.04em;
}
.inf-header-actions { display:flex; gap:8px; align-items:center; }
.inf-btn {
    display:inline-flex; align-items:center; gap:6px;
    padding:8px 16px; border-radius:10px; font-size:.8rem; font-weight:700;
    border:none; cursor:pointer; text-decoration:none; transition:all .18s;
    white-space:nowrap;
}
.inf-btn-ghost  { background:#f1f5f9; color:#475569; }
.inf-btn-ghost:hover  { background:#e2e8f0; color:#1e293b; }
.inf-btn-amber  { background:#f59e0b; color:#fff; box-shadow:0 3px 10px rgba(245,158,11,.25); }
.inf-btn-amber:hover  { background:#d97706; transform:translateY(-1px); }
.inf-btn-primary{ background:#3b82f6; color:#fff; box-shadow:0 3px 10px rgba(59,130,246,.25); }
.inf-btn-primary:hover{ background:#2563eb; transform:translateY(-1px); }
.inf-btn-danger { background:#ef4444; color:#fff; }
.inf-btn-danger:hover { background:#dc2626; }
.inf-btn-round  { width:40px; height:40px; border-radius:50%; padding:0; justify-content:center; }

/* ══ ALERTE BADGE FLOTTANT ═══════════════════════════════════════════════════ */
.alerte-floating {
    position:fixed; top:80px; right:24px; z-index:990;
    background:#fff; border-radius:16px; padding:14px 18px;
    box-shadow:0 8px 32px rgba(239,68,68,.18); border:2px solid #fca5a5;
    animation:pulse-alert 2s infinite; max-width:280px;
}
@keyframes pulse-alert {
    0%,100% { box-shadow:0 8px 32px rgba(239,68,68,.18); }
    50% { box-shadow:0 8px 40px rgba(239,68,68,.35); }
}
.alerte-floating-count {
    font-size:2rem; font-weight:900; color:#ef4444; line-height:1;
}

/* ══ KPI STRIP ═══════════════════════════════════════════════════════════════ */
.kpi-strip { display:flex; gap:14px; padding:20px 28px 0; flex-wrap:wrap; }
.kpi-tile {
    flex:1; min-width:140px; background:#fff; border-radius:16px;
    padding:18px 20px; box-shadow:0 2px 12px rgba(0,0,0,.05);
    display:flex; align-items:center; gap:14px; border-left:4px solid transparent;
    transition:transform .18s, box-shadow .18s;
}
.kpi-tile:hover { transform:translateY(-2px); box-shadow:0 6px 20px rgba(0,0,0,.1); }
.kpi-tile-patients { border-color:#3b82f6; }
.kpi-tile-lits      { border-color:#10b981; }
.kpi-tile-soins     { border-color:#8b5cf6; }
.kpi-tile-alertes   { border-color:#f43f5e; }
.kpi-icon {
    width:44px; height:44px; border-radius:12px;
    display:flex; align-items:center; justify-content:center; font-size:1.25rem; flex-shrink:0;
}
.kpi-icon-blue   { background:#eff6ff; color:#3b82f6; }
.kpi-icon-green  { background:#f0fdf4; color:#10b981; }
.kpi-icon-purple { background:#f5f3ff; color:#8b5cf6; }
.kpi-icon-red    { background:#fff5f5; color:#f43f5e; }
.kpi-val  { font-size:1.9rem; font-weight:900; line-height:1; }
.kpi-lbl  { font-size:.68rem; font-weight:700; color:#94a3b8; text-transform:uppercase; letter-spacing:.7px; margin-top:2px; }

/* ══ TABS ════════════════════════════════════════════════════════════════════ */
.inf-tabs {
    display:flex; gap:4px; padding:18px 28px 0;
    border-bottom:2px solid #e8eef5; overflow-x:auto;
}
.inf-tab {
    padding:10px 20px; border:none; background:transparent;
    font-size:.84rem; font-weight:700; color:#64748b; cursor:pointer;
    border-bottom:3px solid transparent; margin-bottom:-2px;
    display:inline-flex; align-items:center; gap:7px; white-space:nowrap;
    transition:.2s;
}
.inf-tab.active { color:#3b82f6; border-bottom-color:#3b82f6; }
.inf-tab:hover:not(.active) { color:#1e293b; }
.tab-badge {
    min-width:20px; height:20px; border-radius:10px;
    font-size:.67rem; font-weight:800; display:inline-flex; align-items:center; justify-content:center;
    padding:0 6px;
}
.tab-badge-red    { background:#fef2f2; color:#ef4444; }
.tab-badge-blue   { background:#eff6ff; color:#3b82f6; }
.tab-badge-green  { background:#f0fdf4; color:#10b981; }

/* ══ BADGE CLIGNOTANT — soins à faire ══════════════════════════════════════ */
.tab-badge-alert {
    background: linear-gradient(135deg, #ef4444, #dc2626);
    color: #fff;
    font-weight: 900;
    box-shadow: 0 0 0 0 rgba(239, 68, 68, .7);
    animation: pulse-soin 1.5s infinite;
    position: relative;
}
.tab-badge-alert::before {
    content: '';
    position: absolute;
    inset: -3px;
    border-radius: 12px;
    border: 2px solid #ef4444;
    opacity: 0;
    animation: ring-soin 1.5s infinite;
    pointer-events: none;
}
@keyframes pulse-soin {
    0%   { box-shadow: 0 0 0 0    rgba(239, 68, 68, .7); transform: scale(1); }
    50%  { box-shadow: 0 0 0 8px  rgba(239, 68, 68, 0);  transform: scale(1.08); }
    100% { box-shadow: 0 0 0 0    rgba(239, 68, 68, 0);  transform: scale(1); }
}
@keyframes ring-soin {
    0%   { transform: scale(1);    opacity: .8; }
    100% { transform: scale(1.6);  opacity: 0;   }
}

/* Bandeau de notification "soins à faire" en haut du panneau */
.soins-alert-banner {
    background: linear-gradient(135deg, #fef2f2, #fee2e2);
    border-left: 4px solid #ef4444;
    border-radius: 12px;
    padding: 14px 18px;
    margin-bottom: 18px;
    display: flex; align-items: center; gap: 12px;
    box-shadow: 0 2px 10px rgba(239, 68, 68, .12);
    animation: slide-in-left .4s ease-out;
}
.soins-alert-banner .alert-icon {
    width: 42px; height: 42px; border-radius: 50%;
    background: #ef4444; color: #fff;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.3rem; flex-shrink: 0;
    animation: bell-shake 1s infinite;
}
.soins-alert-banner .alert-text {
    flex: 1;
    font-size: .9rem; color: #7f1d1d;
}
.soins-alert-banner .alert-text strong { font-size: 1.05rem; color: #991b1b; }
@keyframes slide-in-left {
    from { opacity: 0; transform: translateX(-20px); }
    to   { opacity: 1; transform: translateX(0); }
}
@keyframes bell-shake {
    0%, 100% { transform: rotate(0); }
    10%, 30% { transform: rotate(-12deg); }
    20%, 40% { transform: rotate(12deg); }
    50%      { transform: rotate(0); }
}
.inf-pane { display:none; padding:24px 28px 40px; }
.inf-pane.active { display:block; }

/* ══ PATIENT CARDS ═══════════════════════════════════════════════════════════ */
.pat-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(300px,1fr)); gap:14px; }
.pat-card {
    background:#fff; border-radius:16px; padding:18px 20px;
    box-shadow:0 2px 12px rgba(0,0,0,.05);
    display:flex; flex-direction:column; gap:12px;
    transition:transform .18s, box-shadow .18s; border:1.5px solid #f1f5f9;
}
.pat-card:hover { transform:translateY(-3px); box-shadow:0 8px 24px rgba(0,0,0,.1); border-color:#bfdbfe; }
.pat-card-head { display:flex; align-items:center; gap:12px; }
.pat-avatar {
    width:46px; height:46px; border-radius:14px;
    background:linear-gradient(135deg,#3b82f6,#1d4ed8);
    display:flex; align-items:center; justify-content:center;
    font-weight:800; font-size:1rem; color:#fff; flex-shrink:0;
}
.pat-name { font-size:.95rem; font-weight:800; color:#1e293b; line-height:1.2; }
.pat-meta { font-size:.72rem; color:#94a3b8; margin-top:2px; }
.lit-badge {
    margin-left:auto; display:inline-flex; align-items:center; gap:4px;
    background:#eff6ff; color:#3b82f6; border:1px solid #bfdbfe;
    border-radius:20px; padding:3px 10px; font-size:.7rem; font-weight:700; flex-shrink:0;
}
.pat-card-actions { display:flex; gap:6px; flex-wrap:wrap; }
.pat-action-btn {
    display:inline-flex; align-items:center; gap:5px;
    padding:6px 12px; border-radius:8px; font-size:.72rem; font-weight:700;
    border:1.5px solid transparent; text-decoration:none; transition:.15s;
}
.pat-action-btn:hover { transform:translateY(-1px); }
.btn-dossier  { background:#eff6ff; color:#3b82f6; border-color:#bfdbfe; }
.btn-suivi    { background:#ecfdf5; color:#10b981; border-color:#a7f3d0; }
.btn-si       { background:#fff5f5; color:#ef4444; border-color:#fca5a5; }
.btn-po       { background:#fef9c3; color:#92400e; border-color:#fde68a; }
.btn-chlit    { background:#f5f3ff; color:#6d28d9; border-color:#ddd6fe; cursor:pointer; }
.btn-liberer  { background:#fff1f2; color:#e11d48; border-color:#fda4af; cursor:pointer; }
.btn-liberer:hover { background:#ffe4e6; }
.btn-admettre-lit { background:#fff7ed; color:#ea580c; border-color:#fed7aa; cursor:pointer; font-weight:700; }
.btn-admettre-lit:hover { background:#ffedd5; transform:translateY(-1px); box-shadow:0 4px 10px rgba(234,88,12,.18); }

/* ══ SOINS CHECKLIST ═════════════════════════════════════════════════════════ */
.soin-row {
    background:#fff; border-radius:12px; padding:14px 18px;
    display:flex; align-items:center; gap:14px;
    box-shadow:0 1px 6px rgba(0,0,0,.05); margin-bottom:8px;
    border-left:4px solid #e2e8f0; transition:.18s;
}
.soin-row:hover { box-shadow:0 4px 16px rgba(0,0,0,.09); }
.soin-row.done { border-left-color:#10b981; opacity:.8; }
.soin-row.todo { border-left-color:#3b82f6; }
.soin-check {
    width:36px; height:36px; border-radius:10px; flex-shrink:0;
    display:flex; align-items:center; justify-content:center; font-size:1.1rem;
}
.soin-check.done { background:#f0fdf4; color:#10b981; }
.soin-check.todo { background:#eff6ff; color:#3b82f6; }
.soin-progress { height:6px; border-radius:99px; background:#e2e8f0; overflow:hidden; margin-top:5px; }
.soin-progress-fill { height:100%; border-radius:99px; background:linear-gradient(90deg,#3b82f6,#10b981); }

/* ══ LITS ════════════════════════════════════════════════════════════════════ */
.lits-overview { display:flex; flex-wrap:wrap; gap:12px; margin-bottom:24px; }
.lit-service-card {
    background:#fff; border-radius:14px; padding:14px 18px;
    box-shadow:0 2px 10px rgba(0,0,0,.06); min-width:140px;
    display:flex; flex-direction:column; align-items:center; text-align:center;
    transition:.18s;
}
.lit-service-card:hover { box-shadow:0 6px 20px rgba(37,99,235,.12); transform:translateY(-2px); }
.lit-donut-wrap { position:relative; width:80px; height:80px; margin-bottom:10px; }
.lit-donut-text { position:absolute; inset:0; display:flex; flex-direction:column;
    align-items:center; justify-content:center; }
.lit-donut-text .num { font-size:1rem; font-weight:800; line-height:1; }
.lit-donut-text .lbl { font-size:.58rem; color:#94a3b8; font-weight:600; text-transform:uppercase; }

.chambre-card {
    background:#fff; border-radius:14px; box-shadow:0 2px 10px rgba(0,0,0,.06); overflow:hidden;
    border:1.5px solid #f1f5f9; transition:.18s;
}
.chambre-card:hover { box-shadow:0 6px 20px rgba(0,0,0,.1); border-color:#bfdbfe; }
.chambre-header {
    padding:10px 16px; display:flex; align-items:center; gap:10px;
    background:#f8fafc; border-bottom:1px solid #e2e8f0;
}
.chambre-header .ch-name { font-weight:700; font-size:.88rem; color:#1e293b; }
.chambre-header .ch-type { font-size:.7rem; color:#64748b; }
.chambre-beds { display:flex; flex-wrap:wrap; gap:10px; padding:14px 16px; }

.bed-tile {
    width:110px; border-radius:10px; padding:10px 10px 8px;
    display:flex; flex-direction:column; align-items:center; gap:4px;
    transition:transform .15s; cursor:default; position:relative;
}
.bed-tile.libre    { background:#f0fdf4; border:1.5px solid #86efac; }
.bed-tile.occupe   { background:#fef2f2; border:1.5px solid #fca5a5; cursor:pointer; }
.bed-tile.occupe:hover { transform:translateY(-2px); }
.bed-tile.nettoyage   { background:#fffbeb; border:1.5px solid #fde68a; }
.bed-tile.nettoyage   .bed-num { color:#92400e; }
.bed-tile.maintenance { background:#f1f5f9; border:1.5px solid #cbd5e1; }
.bed-tile.maintenance .bed-num { color:#475569; }
/* Bouton + menu de changement de statut */
.bed-gear { position:absolute; top:4px; right:4px; width:22px; height:22px; border:none;
    background:rgba(255,255,255,.65); border-radius:6px; color:#64748b; cursor:pointer;
    display:flex; align-items:center; justify-content:center; font-size:.78rem; opacity:.55; transition:all .15s; }
.bed-gear:hover { opacity:1; background:#fff; color:#1e293b; }
#litStatutMenu { position:fixed; z-index:9500; background:#fff; border:1px solid #e2e8f0;
    border-radius:12px; box-shadow:0 10px 32px rgba(0,0,0,.18); padding:6px; display:none; min-width:190px; }
#litStatutMenu .lsm-title { font-size:.68rem; color:#94a3b8; font-weight:700; text-transform:uppercase;
    padding:6px 10px 4px; border-bottom:1px solid #f1f5f9; margin-bottom:4px; }
#litStatutMenu button { display:flex; align-items:center; gap:9px; width:100%; border:none; background:none;
    text-align:left; padding:8px 10px; border-radius:8px; font-size:.84rem; cursor:pointer; color:#1e293b; }
#litStatutMenu button:hover { background:#f1f5f9; }
#litStatutMenu button i { width:16px; text-align:center; }
/* Lit emprunté : occupé par un patient d'un autre service — visible mais non accessible */
.bed-tile.emprunte {
    background: #fffbeb;
    border: 1.5px dashed #f59e0b;
    cursor: default;
    opacity: .9;
}
.bed-tile.emprunte .bed-num  { color: #92400e; }
.bed-icon { font-size:1.5rem; }
.bed-num  { font-size:.72rem; font-weight:700; color:#475569; }
.bed-status-dot { width:8px; height:8px; border-radius:50%; margin-top:2px; }
.bed-tile.libre  .bed-status-dot { background:#22c55e; }
.bed-tile.occupe   .bed-status-dot { background:#ef4444; }
.bed-tile.emprunte .bed-status-dot { background:#f59e0b; }
.bed-patient { font-size:.62rem; font-weight:600; text-align:center; color:#475569;
               overflow:hidden; text-overflow:ellipsis; white-space:nowrap; max-width:100px; }

/* ══ INSTALLATION CARDS ══════════════════════════════════════════════════════ */
.install-card {
    background:#fff; border-radius:16px; padding:18px 22px;
    box-shadow:0 2px 12px rgba(0,0,0,.06); margin-bottom:12px;
    border-left:5px solid #f43f5e;
    animation:blink-border 1.8s infinite;
}
@keyframes blink-border {
    0%,100% { border-left-color:#f43f5e; }
    50% { border-left-color:#fca5a5; }
}
.install-card .install-head { display:flex; align-items:center; gap:14px; flex-wrap:wrap; }
.install-avatar {
    width:44px; height:44px; border-radius:12px;
    background:linear-gradient(135deg,#f43f5e,#be123c);
    display:flex; align-items:center; justify-content:center;
    font-weight:800; color:#fff; font-size:.95rem; flex-shrink:0;
}

/* ══ MODAL ADMISSION ═════════════════════════════════════════════════════════ */
.modal-content { border-radius:20px; border:none; }

/* ══ EMPTY STATE ════════════════════════════════════════════════════════════ */
.empty-state { text-align:center; padding:48px 24px; color:#94a3b8; }
.empty-state i { font-size:3rem; display:block; margin-bottom:12px; opacity:.4; }

/* ══ SECTION TITLE ═══════════════════════════════════════════════════════════ */
.section-head {
    display:flex; align-items:center; justify-content:space-between;
    margin-bottom:16px; flex-wrap:wrap; gap:8px;
}
/* ══ RECHERCHE PATIENTS ══════════════════════════════════════════════════════ */
.pat-search-wrap {
    position:relative; flex:1; max-width:320px;
}
.pat-search-wrap i {
    position:absolute; left:11px; top:50%; transform:translateY(-50%);
    color:#94a3b8; font-size:.85rem; pointer-events:none;
}
.pat-search-input {
    width:100%; padding:7px 12px 7px 32px; border-radius:10px;
    border:1.5px solid #e2e8f0; background:#f8fafc; font-size:.8rem;
    color:#1e293b; outline:none; transition:border-color .18s, box-shadow .18s;
}
.pat-search-input:focus {
    border-color:#3b82f6; box-shadow:0 0 0 3px rgba(59,130,246,.12);
    background:#fff;
}
.pat-search-input::placeholder { color:#b0bec5; }
.pat-no-result {
    display:none; text-align:center; padding:32px 0; color:#94a3b8; font-size:.85rem;
}
.pat-no-result i { font-size:2rem; display:block; margin-bottom:8px; opacity:.4; }

.section-title-txt {
    font-size:.88rem; font-weight:800; color:#1e293b; text-transform:uppercase;
    letter-spacing:.6px; display:flex; align-items:center; gap:8px;
}
</style>

<!-- ════════════════════ HEADER ════════════════════ -->
<header class="inf-header">
    <div class="inf-header-brand">
        <div class="inf-header-logo">
            <img src="<?= BASE_URL ?>public/images/logo_ordre_malte.png" alt="Logo">
        </div>
        <div>
            <div class="inf-service-name"><?= htmlspecialchars($_SESSION['nom_service'] ?? 'Unité de soins') ?></div>
            <div class="inf-service-sub">Inf.&nbsp;<strong><?= htmlspecialchars(($_SESSION['user_prenom'] ?? '').' '.($_SESSION['user_nom'] ?? '')) ?></strong> · Infirmier(e) de garde</div>
        </div>
    </div>

    <div class="inf-clock" id="infClock">00:00:00</div>

    <div class="inf-header-actions">
        <!-- Consultation infirmière rapide (recherche patient) -->
        <button type="button" class="inf-btn"
                style="background:#0d9488;color:#fff;border-color:#0d9488;"
                data-bs-toggle="modal" data-bs-target="#modalChercherPatientConsult"
                title="Effectuer une consultation infirmière">
            <i class="bi bi-clipboard2-pulse-fill"></i> Consulter
        </button>
        <button type="button" class="inf-btn inf-btn-primary"
                data-bs-toggle="modal" data-bs-target="#modalCreerHosp"
                title="Créer un nouveau patient et l'hospitaliser directement">
            <i class="bi bi-person-plus-fill"></i> Créer &amp; Hospitaliser
        </button>
        <a href="<?= BASE_URL ?>prescription/par-ordre" class="inf-btn inf-btn-amber" title="Rédiger une ordonnance par ordre">
            <i class="bi bi-pen-fill"></i> Ord. P/O
        </a>
        <button onclick="location.reload()" class="inf-btn inf-btn-ghost" title="Actualiser">
            <i class="bi bi-arrow-clockwise"></i>
        </button>
        <?php if (($_SESSION['user_role'] ?? '') === 'MAJOR'): ?>
        <a href="<?= BASE_URL ?>major/dashboard"
           class="inf-btn fw-bold"
           style="background:#f59e0b;color:#fff;border-color:#f59e0b;white-space:nowrap;"
           title="Retourner au cockpit de supervision Major">
            <i class="bi bi-speedometer2"></i> Cockpit Major
        </a>
        <?php endif; ?>
        <button onclick="ouvrirProfilModal()" class="inf-btn inf-btn-ghost inf-btn-round" title="Mon profil">
            <i class="bi bi-person-circle fs-5"></i>
        </button>
        <a href="<?= BASE_URL ?>logout" class="inf-btn inf-btn-danger inf-btn-round" title="Déconnexion">
            <i class="bi bi-power fs-5"></i>
        </a>
    </div>
</header>

<!-- ════════════════════ ALERTE FLOTTANTE ════════════════════ -->
<?php if ($nb_alertes > 0): ?>
<div class="alerte-floating d-flex align-items-center gap-12">
    <div>
        <div class="alerte-floating-count"><?= $nb_alertes ?></div>
        <div style="font-size:.72rem;font-weight:700;color:#ef4444;">À installer</div>
    </div>
    <div style="margin-left:10px;">
        <div style="font-size:.8rem;font-weight:700;color:#1e293b;">Patient(s) en attente</div>
        <button onclick="switchInfTab('installation')" class="inf-btn inf-btn-primary mt-1" style="padding:5px 12px;font-size:.72rem;">
            <i class="bi bi-hospital"></i> Voir
        </button>
    </div>
</div>
<?php endif; ?>

<!-- ════════════════════ TOAST SUCCÈS (après plan / action) ════════════════════ -->
<?php
$successParam = $_GET['success'] ?? '';
$nbHors       = (int)($_GET['nb_hors'] ?? 0);
if ($successParam === 'plan_valide' || $successParam === 'plan_valide_avec_ordo'):
?>
<div id="toast-plan" style="
    position:fixed; bottom:24px; right:24px; z-index:9999;
    background:#fff; border-radius:14px; padding:16px 20px; width:340px;
    box-shadow:0 8px 32px rgba(0,0,0,.14); border-left:4px solid #10b981;
    display:flex; gap:14px; align-items:flex-start;
    animation: slideInRight .4s ease;
">
    <div style="background:#d1fae5;border-radius:50%;width:36px;height:36px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
        <i class="bi bi-check2-circle" style="color:#059669;font-size:1.2rem;"></i>
    </div>
    <div>
        <div style="font-weight:700;color:#065f46;font-size:.9rem;">Plan de soins validé !</div>
        <div style="font-size:.78rem;color:#374151;margin-top:2px;">
            Les soins planifiés sont visibles dans la checklist.
            <?php if ($nbHors > 0): ?>
            <br><span style="color:#d97706;font-weight:600;">
                <i class="bi bi-prescription2"></i>
                <?= $nbHors ?> médicament<?= $nbHors > 1 ? 's' : '' ?> hors ordonnance transmis à la pharmacie.
            </span>
            <?php endif; ?>
        </div>
    </div>
    <button onclick="document.getElementById('toast-plan').remove()" style="
        background:none;border:none;color:#9ca3af;font-size:1.1rem;
        position:absolute;top:10px;right:12px;cursor:pointer;line-height:1;
    ">×</button>
</div>
<style>@keyframes slideInRight { from { transform:translateX(100px);opacity:0; } to { transform:translateX(0);opacity:1; } }</style>
<script>setTimeout(() => { const t = document.getElementById('toast-plan'); if(t) t.style.opacity='0'; }, 6000);</script>
<?php endif; ?>

<!-- ════════════════════ KPI STRIP ════════════════════ -->
<div class="kpi-strip">
    <div class="kpi-tile kpi-tile-patients">
        <div class="kpi-icon kpi-icon-blue"><i class="bi bi-people-fill"></i></div>
        <div>
            <div class="kpi-val" style="color:#3b82f6;"><?= $nb_patients ?></div>
            <div class="kpi-lbl">Hospitalisés</div>
        </div>
    </div>
    <div class="kpi-tile kpi-tile-lits">
        <div class="kpi-icon kpi-icon-green"><i class="bi bi-hospital"></i></div>
        <div>
            <div class="kpi-val" style="color:#10b981;"><?= $nb_lits_lib ?><span style="font-size:1rem;color:#94a3b8;">/<?= $nb_lits_tot ?></span></div>
            <div class="kpi-lbl">Lits libres</div>
        </div>
    </div>
    <div class="kpi-tile kpi-tile-soins">
        <div class="kpi-icon kpi-icon-purple"><i class="bi bi-clipboard2-check-fill"></i></div>
        <div>
            <div class="kpi-val" style="color:#8b5cf6;"><?= $nb_soins_ok ?><span style="font-size:1rem;color:#94a3b8;">/<?= $nb_soins_tot ?></span></div>
            <div class="kpi-lbl">Soins du jour</div>
        </div>
    </div>
    <div class="kpi-tile kpi-tile-alertes" style="<?= $nb_alertes ? 'border-color:#f43f5e;' : 'border-color:#94a3b8;' ?>">
        <div class="kpi-icon <?= $nb_alertes ? 'kpi-icon-red' : '' ?>" style="<?= !$nb_alertes ? 'background:#f8fafc;color:#94a3b8;' : '' ?>">
            <i class="bi bi-bell<?= $nb_alertes ? '-fill' : '' ?>"></i>
        </div>
        <div>
            <div class="kpi-val" style="color:<?= $nb_alertes ? '#f43f5e' : '#94a3b8' ?>;"><?= $nb_alertes ?></div>
            <div class="kpi-lbl">À installer</div>
        </div>
    </div>
</div>

<!-- ════════════════════ TABS ════════════════════ -->
<div class="inf-tabs">
    <button class="inf-tab active" onclick="switchInfTab('patients')" id="infTab-patients">
        <i class="bi bi-people-fill"></i> Patients
        <?php if ($nb_patients): ?><span class="tab-badge tab-badge-blue"><?= $nb_patients ?></span><?php endif; ?>
    </button>
    <button class="inf-tab" onclick="switchInfTab('soins')" id="infTab-soins">
        <i class="bi bi-clipboard2-check-fill"></i> Soins du jour
        <?php if ($nb_soins_en_retard > 0): ?>
            <!-- Badge rouge clignotant : soins EN RETARD uniquement (date_prevue dépassée) -->
            <span class="tab-badge tab-badge-alert"
                  title="<?= $nb_soins_en_retard ?> soin(s) en retard">
                <?= $nb_soins_en_retard ?>
            </span>
        <?php elseif ($nb_soins_individuels_attente > 0): ?>
            <!-- Badge bleu discret : soins à venir (pas encore en retard) -->
            <span class="tab-badge tab-badge-blue"
                  title="<?= $nb_soins_individuels_attente ?> soin(s) à venir">
                <?= $nb_soins_individuels_attente ?>
            </span>
        <?php endif; ?>
    </button>
    <button class="inf-tab" onclick="switchInfTab('lits')" id="infTab-lits">
        <i class="bi bi-hospital"></i> Lits
        <?php if ($nb_lits_lib): ?><span class="tab-badge tab-badge-green"><?= $nb_lits_lib ?> libres</span><?php endif; ?>
    </button>
    <button class="inf-tab" onclick="switchInfTab('installation')" id="infTab-installation">
        <i class="bi bi-house-door-fill"></i> Installation
        <?php if ($nb_alertes): ?><span class="tab-badge tab-badge-red"><?= $nb_alertes ?></span><?php endif; ?>
    </button>
    <button class="inf-tab" onclick="switchInfTab('historique')" id="infTab-historique">
        <i class="bi bi-clock-history"></i> Historique
        <?php if (!empty($historique_patients)): ?><span class="tab-badge tab-badge-blue"><?= count($historique_patients) ?></span><?php endif; ?>
    </button>
</div>

<!-- ════════════════════ PANE : PATIENTS ════════════════════ -->
<div class="inf-pane active" id="infPane-patients">
    <?php if (empty($patients_service)): ?>
    <div class="empty-state">
        <i class="bi bi-person-x"></i>
        Aucun patient hospitalisé dans votre service.
    </div>
    <?php else: ?>
    <div class="section-head">
        <div class="section-title-txt"><i class="bi bi-people-fill text-primary"></i>Patients hospitalisés</div>
        <!-- ── Recherche dynamique ── -->
        <div class="pat-search-wrap">
            <i class="bi bi-search"></i>
            <input type="text" id="patSearchInput" class="pat-search-input"
                   placeholder="Nom, dossier, chambre, lit…"
                   oninput="filtrerPatients(this.value)">
        </div>
        <span class="badge bg-primary rounded-pill px-3" id="patCountBadge"><?= $nb_patients ?> patient(s)</span>
    </div>
    <div id="patNoResult" class="pat-no-result">
        <i class="bi bi-person-x"></i>Aucun patient ne correspond à votre recherche.
    </div>
    <div class="pat-grid" id="patGrid">
        <?php foreach ($patients_service as $p):
            $initials  = strtoupper(substr($p['nom'] ?? 'X', 0, 1) . substr($p['prenom'] ?? 'X', 0, 1));
            $patientId = $p['id'] ?? 0;
            $agePat    = !empty($p['date_naissance']) ? (int)date_diff(date_create($p['date_naissance']), date_create())->y : null;
            $dureeSej  = !empty($p['date_admission']) ? (int)date_diff(date_create($p['date_admission']), date_create())->days : 0;
        ?>
        <?php
            $searchStr = strtolower(implode(' ', array_filter([
                $p['nom'] ?? '', $p['prenom'] ?? '',
                $p['dossier_numero'] ?? '',
                $p['nom_chambre'] ?? '', $p['nom_lit'] ?? ''
            ])));
        ?>
        <div class="pat-card" id="patCard-<?= $patientId ?>"
             data-search="<?= htmlspecialchars($searchStr) ?>">
            <div class="pat-card-head">
                <div class="pat-avatar"><?= $initials ?></div>
                <div style="flex:1; min-width:0;">
                    <div class="pat-name"><?= htmlspecialchars(strtoupper($p['nom']).' '.$p['prenom']) ?></div>
                    <div class="pat-meta">
                        <?= htmlspecialchars($p['dossier_numero'] ?? '') ?>
                        <?php if ($agePat): ?> · <?= $agePat ?> ans<?php endif; ?>
                        <?php if ($dureeSej > 0): ?> · J+<?= $dureeSej ?><?php endif; ?>
                    </div>
                </div>
                <?php if (!empty($p['nom_lit'])): ?>
                <div class="lit-badge">
                    <i class="bi bi-geo-alt"></i>
                    <?= htmlspecialchars($p['nom_chambre'] ?? '') ?>&nbsp;·&nbsp;<?= htmlspecialchars($p['nom_lit']) ?>
                </div>
                <?php endif; ?>
            </div>
            <div class="pat-card-actions">
                <a href="<?= BASE_URL ?>patients/dossier/<?= $patientId ?>" class="pat-action-btn btn-dossier">
                    <i class="bi bi-folder2-open"></i> Dossier
                </a>
                <a href="<?= BASE_URL ?>hospitalisation/suivi/<?= $patientId ?>" class="pat-action-btn btn-suivi">
                    <i class="bi bi-speedometer2"></i> Suivi
                </a>
                <a href="<?= BASE_URL ?>hospitalisation/surveillance-intensive/<?= $patientId ?>" class="pat-action-btn btn-si">
                    <i class="bi bi-clipboard2-pulse"></i> S.I.
                </a>
                <a href="<?= BASE_URL ?>prescription/par-ordre?patient_id=<?= $patientId ?>" class="pat-action-btn btn-po">
                    <i class="bi bi-pen-fill"></i> P/O
                </a>
                <?php if (empty($p['lit_id']) || empty($p['nom_lit'])): ?>
                <!-- Patient sans lit assigné → bouton Admettre sur lit -->
                <button type="button" class="pat-action-btn btn-admettre-lit"
                        onclick="ouvrirAdmettreLit(
                            <?= $patientId ?>,
                            '<?= addslashes(htmlspecialchars(strtoupper($p['nom']).' '.$p['prenom'])) ?>',
                            <?= (int)($p['hosp_service_id'] ?? $p['service_id'] ?? 0) ?>
                        )"
                        title="Attribuer un lit à ce patient">
                    <i class="bi bi-house-add-fill"></i> Admettre sur lit
                </button>
                <?php endif; ?>
                <button type="button" class="pat-action-btn btn-chlit"
                        onclick="ouvrirModalTransfertDash(
                            <?= $patientId ?>,
                            <?= (int)($p['hosp_id'] ?? 0) ?>,
                            '<?= addslashes(htmlspecialchars(strtoupper($p['nom']).' '.$p['prenom'])) ?>',
                            <?= (int)($p['hosp_service_id'] ?? $p['service_id'] ?? 0) ?>,
                            '<?= addslashes(htmlspecialchars(($p['nom_chambre'] ? $p['nom_chambre'].' · ' : '') . ($p['nom_lit'] ?? ''))) ?>'
                        )"
                        title="Transfert interne ou externe">
                    <i class="bi bi-arrow-left-right"></i> Transférer
                </button>
                <button type="button" class="pat-action-btn btn-liberer"
                        onclick="ouvrirLibererLit(
                            <?= $patientId ?>,
                            '<?= addslashes(htmlspecialchars(strtoupper($p['nom']).' '.$p['prenom'])) ?>',
                            '<?= addslashes(htmlspecialchars(($p['nom_chambre'] ? $p['nom_chambre'].' · ' : '') . ($p['nom_lit'] ?? 'Non assigné'))) ?>'
                        )"
                        title="Sortir le patient et libérer le lit">
                    <i class="bi bi-box-arrow-right"></i> Libérer
                </button>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<!-- ════════════════════ PANE : SOINS ════════════════════ -->
<div class="inf-pane" id="infPane-soins">
    <?php if ($nb_soins_en_retard > 0):
        // Construire la liste des patients en retard pour l'affichage détaillé
        $patients_retard = array_filter($plans_du_jour, fn($p) => (int)($p['soins_en_retard'] ?? 0) > 0);
        usort($patients_retard, fn($a,$b) => (int)$b['soins_en_retard'] - (int)$a['soins_en_retard']);
    ?>
    <!-- ── BANDEAU D'ALERTE CLIQUABLE ───────────────────────────────────── -->
    <div class="soins-alert-banner" onclick="toggleRetardDetail()" style="cursor:pointer;">
        <div class="alert-icon"><i class="bi bi-clock-fill"></i></div>
        <div class="alert-text" style="flex:1;">
            <strong><?= $nb_soins_en_retard ?> soin<?= $nb_soins_en_retard > 1 ? 's' : '' ?> en retard</strong>
            — <?= $nb_patients_en_retard ?> patient<?= $nb_patients_en_retard > 1 ? 's concernés' : ' concerné' ?>.
            <span style="font-size:.72rem;color:#991b1b;opacity:.75;margin-left:8px;">
                <i class="bi bi-chevron-down" id="iconRetard"></i> voir les patients
            </span>
        </div>
    </div>

    <!-- ── LISTE DÉPLIABLE DES PATIENTS EN RETARD ──────────────────────── -->
    <div id="retardDetail" style="display:none;margin-bottom:12px;
         background:#fff5f5;border:1.5px solid #fca5a5;border-radius:14px;overflow:hidden;">
        <div style="padding:10px 16px 4px;font-size:.7rem;font-weight:800;text-transform:uppercase;
                    letter-spacing:.5px;color:#991b1b;">
            <i class="bi bi-exclamation-triangle-fill me-1"></i>
            Patients avec soins en retard — triés par nombre de retards
        </div>
        <?php foreach ($patients_retard as $pr):
            $retardCount = (int)($pr['soins_en_retard'] ?? 0);
            $urgence     = $retardCount >= 5 ? '#dc2626' : ($retardCount >= 3 ? '#ea580c' : '#d97706');
        ?>
        <div style="display:flex;align-items:center;gap:12px;padding:10px 16px;
                    border-top:1px solid #fee2e2;">
            <!-- Icône urgence -->
            <div style="width:32px;height:32px;border-radius:50%;background:<?= $urgence ?>20;
                        display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <i class="bi bi-exclamation-circle-fill" style="color:<?= $urgence ?>;font-size:.9rem;"></i>
            </div>

            <!-- Infos patient -->
            <div style="flex:1;min-width:0;">
                <div style="font-weight:700;font-size:.88rem;color:#1e293b;">
                    <?= htmlspecialchars(strtoupper($pr['nom']).' '.$pr['prenom']) ?>
                </div>
                <div style="font-size:.72rem;color:#64748b;margin-top:1px;">
                    <?= $pr['soins_faits'] ?>/<?= $pr['total_soins'] ?> soins réalisés
                    · <span style="color:<?= $urgence ?>;font-weight:700;"><?= $retardCount ?> en retard</span>
                </div>
            </div>

            <!-- Badge retards + bouton -->
            <div style="display:flex;align-items:center;gap:8px;flex-shrink:0;">
                <span style="background:<?= $urgence ?>;color:white;font-size:.7rem;font-weight:800;
                             padding:3px 10px;border-radius:20px;">
                    <?= $retardCount ?> retard<?= $retardCount > 1 ? 's' : '' ?>
                </span>
                <a href="<?= BASE_URL ?>hospitalisation/executer-soins/<?= $pr['plan_id'] ?>"
                   class="inf-btn inf-btn-primary" style="font-size:.72rem;padding:4px 12px;">
                    <i class="bi bi-play-fill me-1"></i>Exécuter
                </a>
            </div>
        </div>
        <?php endforeach; ?>
        <div style="padding:8px 16px;font-size:.7rem;color:#991b1b;opacity:.8;border-top:1px solid #fee2e2;">
            <i class="bi bi-info-circle me-1"></i>
            Cliquez sur <strong>Exécuter</strong> pour chaque patient afin de valider ses soins.
        </div>
    </div>

    <script>
    function toggleRetardDetail() {
        const d = document.getElementById('retardDetail');
        const i = document.getElementById('iconRetard');
        const open = d.style.display !== 'none';
        d.style.display = open ? 'none' : 'block';
        if (i) i.className = open ? 'bi bi-chevron-down' : 'bi bi-chevron-up';
    }
    </script>
    <?php endif; ?>

    <div class="section-head">
        <div class="section-title-txt"><i class="bi bi-clipboard2-check-fill text-primary"></i>Plan de soins du jour</div>
        <?php if ($nb_soins_tot): ?>
        <span style="font-size:.8rem;color:#64748b;"><?= $nb_soins_ok ?>/<?= $nb_soins_tot ?> réalisés</span>
        <?php endif; ?>
    </div>
    <?php if (empty($plans_du_jour)): ?>
    <div class="empty-state">
        <i class="bi bi-calendar2-x"></i>
        Aucun soin planifié pour aujourd'hui.
    </div>
    <?php else: ?>
    <?php
    // Trier : patients avec retards d'abord, puis les non-terminés, puis les terminés
    usort($plans_du_jour, function($a, $b) {
        $ra = (int)($a['soins_en_retard'] ?? 0);
        $rb = (int)($b['soins_en_retard'] ?? 0);
        if ($ra !== $rb) return $rb - $ra; // retards décroissants
        $da = ($a['total_soins'] > 0 && $a['total_soins'] == $a['soins_faits']) ? 1 : 0;
        $db = ($b['total_soins'] > 0 && $b['total_soins'] == $b['soins_faits']) ? 1 : 0;
        return $da - $db; // terminés en dernier
    });
    foreach ($plans_du_jour as $soin):
        $isDone   = ($soin['total_soins'] > 0 && $soin['total_soins'] == $soin['soins_faits']);
        $retard   = (int)($soin['soins_en_retard'] ?? 0);
        $prog     = $soin['total_soins'] > 0 ? round($soin['soins_faits'] / $soin['total_soins'] * 100) : 0;
        $rowBg    = $retard >= 5 ? 'background:#fff5f5;border-left:4px solid #dc2626;'
                  : ($retard >= 1 ? 'background:#fffbeb;border-left:4px solid #f59e0b;' : '');
        $retColor = $retard >= 5 ? '#dc2626' : '#ea580c';
    ?>
    <div class="soin-row <?= $isDone ? 'done' : 'todo' ?>" style="<?= $rowBg ?>">
        <div class="soin-check <?= $isDone ? 'done' : ($retard > 0 ? '' : 'todo') ?>"
             style="<?= $retard > 0 && !$isDone ? 'color:#dc2626;' : '' ?>">
            <i class="bi bi-<?= $isDone ? 'check-circle-fill' : ($retard > 0 ? 'exclamation-circle-fill' : 'clock') ?>"></i>
        </div>
        <div style="flex:1; min-width:0;">
            <div class="fw-bold d-flex align-items-center gap-2 flex-wrap" style="font-size:.9rem;color:#1e293b;">
                <?php if (!empty($soin['nom_chambre']) || !empty($soin['nom_lit'])): ?>
                <span style="background:#eef2ff;color:#4338ca;border:1px solid #c7d2fe;font-size:.66rem;font-weight:700;
                             padding:2px 9px;border-radius:8px;white-space:nowrap;">
                    <i class="bi bi-door-open-fill me-1" style="font-size:.6rem;"></i>
                    <?= htmlspecialchars(trim(($soin['nom_chambre'] ?? '') . (!empty($soin['nom_lit']) ? ' · '.$soin['nom_lit'] : ''))) ?>
                </span>
                <?php endif; ?>
                <?= htmlspecialchars(strtoupper($soin['nom']).' '.$soin['prenom']) ?>
                <?php if ($retard > 0 && !$isDone): ?>
                <span style="background:<?= $retColor ?>;color:#fff;font-size:.65rem;font-weight:800;
                             padding:2px 8px;border-radius:20px;white-space:nowrap;">
                    <i class="bi bi-clock-fill me-1" style="font-size:.6rem;"></i>
                    <?= $retard ?> en retard
                </span>
                <?php endif; ?>
            </div>
            <div class="soin-progress mt-1">
                <div class="soin-progress-fill" style="width:<?= $prog ?>%;
                     background:<?= $isDone ? '#22c55e' : ($retard > 0 ? '#ef4444' : '#3b82f6') ?>;"></div>
            </div>
            <div style="font-size:.7rem;color:#94a3b8;margin-top:3px;">
                <?= $soin['soins_faits'] ?> / <?= $soin['total_soins'] ?> soins · <?= $prog ?>%
                <?php if ($retard > 0 && !$isDone): ?>
                <span style="color:<?= $retColor ?>;font-weight:700;margin-left:4px;">
                    · <?= $retard ?> dépassé<?= $retard > 1 ? 's' : '' ?>
                </span>
                <?php endif; ?>
            </div>
        </div>
        <div class="flex-shrink-0">
            <?php if ($isDone): ?>
            <a href="<?= BASE_URL ?>hospitalisation/executer-soins/<?= $soin['plan_id'] ?>" class="inf-btn inf-btn-ghost" style="font-size:.75rem;">
                <i class="bi bi-eye"></i> Voir
            </a>
            <?php else: ?>
            <a href="<?= BASE_URL ?>hospitalisation/executer-soins/<?= $soin['plan_id'] ?>" class="inf-btn inf-btn-primary" style="font-size:.75rem;
               <?= $retard > 0 ? 'background:#dc2626;border-color:#dc2626;' : '' ?>">
                <i class="bi bi-play-fill"></i> Exécuter
            </a>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- ════════════════════ PANE : LITS ════════════════════ -->
<div class="inf-pane" id="infPane-lits">
    <!-- Donuts overview global -->
    <div class="section-head mb-0">
        <div class="section-title-txt"><i class="bi bi-building text-primary"></i>Disponibilité globale</div>
    </div>
    <div class="lits-overview mt-3 mb-4">
        <?php
        $totAll = array_sum(array_column($lits_global, 'total'));
        $occAll = array_sum(array_column($lits_global, 'occupes'));
        $pAll   = $totAll > 0 ? round($occAll / $totAll * 100) : 0;
        $cAll   = $pAll >= 90 ? '#ef4444' : ($pAll >= 70 ? '#f59e0b' : '#22c55e');
        $R = 30; $C = round(2*M_PI*$R, 2); $D = round($pAll/100*$C, 1);
        ?>
        <div class="lit-service-card">
            <div class="lit-donut-wrap">
                <svg width="80" height="80" viewBox="0 0 80 80">
                    <circle cx="40" cy="40" r="<?= $R ?>" fill="none" stroke="#e2e8f0" stroke-width="10"/>
                    <circle cx="40" cy="40" r="<?= $R ?>" fill="none" stroke="<?= $cAll ?>" stroke-width="10"
                            stroke-dasharray="<?= $D ?> <?= $C ?>" stroke-linecap="round" transform="rotate(-90 40 40)"/>
                </svg>
                <div class="lit-donut-text">
                    <div class="num" style="color:<?= $cAll ?>"><?= $pAll ?>%</div>
                    <div class="lbl">occupé</div>
                </div>
            </div>
            <div class="fw-bold small mb-1">Hôpital</div>
            <div class="text-muted" style="font-size:.72rem"><?= $totAll - $occAll ?> libres / <?= $totAll ?></div>
        </div>
        <?php foreach ($lits_global as $srv):
            $tot = max(1,(int)$srv['total']); $occ=(int)$srv['occupes'];
            $pct = round($occ/$tot*100);
            $col = $pct>=90?'#ef4444':($pct>=70?'#f59e0b':'#22c55e');
            $r=30; $circ=round(2*M_PI*$r,2); $dash=round($pct/100*$circ,1);
            $srvName = $srv['service']??$srv['nom_service']??'?';
        ?>
        <div class="lit-service-card">
            <div class="lit-donut-wrap">
                <svg width="80" height="80" viewBox="0 0 80 80">
                    <circle cx="40" cy="40" r="<?= $r ?>" fill="none" stroke="#e2e8f0" stroke-width="10"/>
                    <circle cx="40" cy="40" r="<?= $r ?>" fill="none" stroke="<?= $col ?>" stroke-width="10"
                            stroke-dasharray="<?= $dash ?> <?= $circ ?>" stroke-linecap="round" transform="rotate(-90 40 40)"/>
                </svg>
                <div class="lit-donut-text">
                    <div class="num" style="color:<?= $col ?>"><?= $pct ?>%</div>
                    <div class="lbl">occupé</div>
                </div>
            </div>
            <div class="fw-bold small mb-1"><?= htmlspecialchars($srvName) ?></div>
            <div class="text-muted" style="font-size:.72rem"><?= $tot-$occ ?> libres / <?= $tot ?></div>
            <?php if ($tot-$occ==0): ?><span class="badge bg-danger mt-1" style="font-size:.58rem">COMPLET</span>
            <?php elseif ($tot-$occ<=2): ?><span class="badge bg-warning text-dark mt-1" style="font-size:.58rem">QUASI PLEIN</span>
            <?php else: ?><span class="badge bg-success mt-1" style="font-size:.58rem"><?= $tot-$occ ?> DISPO</span>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Chambres & lits du service -->
    <div class="section-head">
        <div class="section-title-txt">
            <i class="bi bi-building-fill text-primary"></i>
            <?= htmlspecialchars($_SESSION['nom_service'] ?? '') ?>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <span class="badge bg-primary"><?= count($lits_par_chambre) ?> ch.</span>
            <span class="badge bg-success"><?= $nb_lits_lib ?> libres</span>
            <span class="badge bg-secondary"><?= $nb_lits_tot ?> lits</span>
            <?php if ($nb_lits_empruntes > 0): ?>
            <span class="badge rounded-pill"
                  style="background:#fef3c7;color:#92400e;border:1px solid #fcd34d;"
                  title="Lits de votre service occupés par des patients d'un autre service">
                <i class="bi bi-arrow-left-right me-1"></i><?= $nb_lits_empruntes ?> emprunt<?= $nb_lits_empruntes > 1 ? 's' : '' ?>
            </span>
            <?php endif; ?>
        </div>
    </div>
    <?php if (empty($lits_par_chambre)): ?>
    <div class="empty-state"><i class="bi bi-door-closed"></i>Aucun lit configuré dans votre service.</div>
    <?php else: ?>
    <div class="row g-3">
        <?php foreach ($lits_par_chambre as $chambre => $lits_ch):
            $nbOcc = count(array_filter($lits_ch, fn($l) => !empty($l['occupied_by_patient_id'])));
            $nbTot = count($lits_ch);
        ?>
        <div class="col-12 col-sm-6 col-lg-4">
            <div class="chambre-card">
                <div class="chambre-header">
                    <i class="bi bi-door-closed-fill text-secondary"></i>
                    <div>
                        <div class="ch-name"><?= htmlspecialchars($chambre) ?></div>
                        <div class="ch-type"><?= htmlspecialchars($lits_ch[0]['type_chambre'] ?? '') ?></div>
                    </div>
                    <div class="ms-auto text-end">
                        <div style="font-size:.7rem;font-weight:700;color:<?= $nbOcc==$nbTot?'#ef4444':'#22c55e' ?>">
                            <?= $nbTot-$nbOcc ?>/<?= $nbTot ?> libres
                        </div>
                        <div style="width:50px;height:4px;background:#e2e8f0;border-radius:2px;margin-top:3px;">
                            <div style="width:<?= $nbTot>0?round($nbOcc/$nbTot*100):0 ?>%;height:100%;background:<?= $nbOcc==$nbTot?'#ef4444':'#3b82f6' ?>;border-radius:2px;"></div>
                        </div>
                    </div>
                </div>
                <div class="chambre-beds">
                    <?php foreach ($lits_ch as $l):
                        $isOcc     = !empty($l['occupied_by_patient_id']);
                        $statutLit = strtoupper($l['statut'] ?? 'DISPONIBLE');
                        // Lit "emprunté" : occupé par un patient d'un AUTRE service
                        $isEmprunte = $isOcc
                            && !empty($l['patient_service_id'])
                            && (int)$l['patient_service_id'] !== $_infServiceId;
                        $patNom = (!$isOcc || $isEmprunte) ? '' :
                            htmlspecialchars(strtoupper(substr($l['nom'] ?? '',0,1)).'. '.($l['prenom']??''));
                        if ($isOcc)                            $tileClass = $isEmprunte ? 'emprunte' : 'occupe';
                        elseif ($statutLit === 'NETTOYAGE')    $tileClass = 'nettoyage';
                        elseif ($statutLit === 'MAINTENANCE')  $tileClass = 'maintenance';
                        else                                   $tileClass = 'libre';
                        $statutLabels = ['DISPONIBLE'=>'Libre','LIBRE'=>'Libre','NETTOYAGE'=>'Nettoyage','MAINTENANCE'=>'Maintenance'];
                        $bedIcon = $isEmprunte ? '🔒' : ($isOcc ? '🛏️'
                                  : ($statutLit==='NETTOYAGE' ? '🧹' : ($statutLit==='MAINTENANCE' ? '🛠️' : '🛌')));
                        $tooltip   = $isEmprunte
                            ? 'Lit emprunté — patient du service ' . htmlspecialchars($l['patient_service_nom'] ?? '?')
                            : ($isOcc ? htmlspecialchars(($l['nom']??'').' '.($l['prenom']??'')) : ($statutLabels[$statutLit] ?? $statutLit));
                        $canEdit = !$isOcc; // lit non occupé → statut modifiable
                    ?>
                    <div class="bed-tile <?= $tileClass ?>"
                         title="<?= $tooltip ?>"
                         <?php if ($isOcc && !$isEmprunte): ?>onclick="scrollToPatient(<?= $l['occupied_by_patient_id'] ?>)"<?php endif; ?>>
                        <?php if ($canEdit): ?>
                        <button type="button" class="bed-gear" title="Changer le statut du lit"
                                onclick="event.stopPropagation(); ouvrirStatutLit(this, <?= (int)$l['id'] ?>, '<?= addslashes(htmlspecialchars(($l['nom_chambre']??'').' · '.($l['nom_lit']??''))) ?>', '<?= $statutLit ?>')">
                            <i class="bi bi-three-dots-vertical"></i>
                        </button>
                        <?php endif; ?>
                        <div class="bed-icon"><?= $bedIcon ?></div>
                        <div class="bed-num"><?= htmlspecialchars($l['nom_lit']) ?></div>
                        <div class="bed-status-dot"></div>
                        <?php if ($isEmprunte): ?>
                            <div class="bed-patient" style="font-size:.58rem;color:#d97706;font-weight:700;line-height:1.2;text-align:center;">
                                <i class="bi bi-arrow-left-right"></i><br>
                                <?= htmlspecialchars(mb_substr($l['patient_service_nom'] ?? 'Autre service', 0, 14)) ?>
                            </div>
                        <?php elseif ($isOcc): ?>
                            <div class="bed-patient"><?= $patNom ?></div>
                        <?php else: ?>
                            <div class="bed-patient" style="font-size:.6rem;font-weight:700;">
                                <?= htmlspecialchars($statutLabels[$statutLit] ?? 'Libre') ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<!-- Menu flottant : changement de statut d'un lit -->
<div id="litStatutMenu">
    <div class="lsm-title" id="lsmTitle">Statut du lit</div>
    <button onclick="setStatutLit('DISPONIBLE')"><i class="bi bi-check-circle-fill text-success"></i> Disponible</button>
    <button onclick="setStatutLit('NETTOYAGE')"><i class="bi bi-droplet-half text-warning"></i> En nettoyage</button>
    <button onclick="setStatutLit('MAINTENANCE')"><i class="bi bi-tools text-secondary"></i> En maintenance</button>
</div>
<script>
(function(){
    let curLitId = null;
    const menu = document.getElementById('litStatutMenu');
    window.ouvrirStatutLit = function(btn, litId, label, statut){
        curLitId = litId;
        document.getElementById('lsmTitle').textContent = label || 'Statut du lit';
        const r = btn.getBoundingClientRect();
        menu.style.display = 'block';
        // positionner sous le bouton, en restant dans la fenêtre
        let left = r.right - menu.offsetWidth;
        if (left < 8) left = 8;
        let top = r.bottom + 4;
        if (top + menu.offsetHeight > window.innerHeight - 8) top = r.top - menu.offsetHeight - 4;
        menu.style.left = left + 'px';
        menu.style.top  = top + 'px';
    };
    window.setStatutLit = function(statut){
        if (!curLitId) return;
        const fd = new FormData();
        fd.append('lit_id', curLitId);
        fd.append('statut', statut);
        menu.style.display = 'none';
        fetch('<?= BASE_URL ?>lits/changer-statut', { method:'POST', body: fd })
            .then(r => r.json())
            .then(res => {
                if (res.success) { location.reload(); }
                else { alert(res.message || 'Erreur lors du changement de statut.'); }
            })
            .catch(() => alert('Erreur réseau.'));
    };
    document.addEventListener('click', e => {
        if (!e.target.closest('#litStatutMenu') && !e.target.closest('.bed-gear')) menu.style.display = 'none';
    });
})();
</script>

<!-- ════════════════════ PANE : INSTALLATION ════════════════════ -->
<div class="inf-pane" id="infPane-installation">
    <div class="section-head">
        <div class="section-title-txt"><i class="bi bi-house-door-fill text-danger"></i>Patients à installer</div>
        <?php if ($nb_alertes): ?><span class="badge bg-danger rounded-pill px-3"><?= $nb_alertes ?> en attente</span><?php endif; ?>
    </div>
    <?php if (empty($a_hospitaliser)): ?>
    <div class="empty-state">
        <i class="bi bi-check2-all text-success"></i>
        Aucun patient en attente d'installation. Tout est en ordre.
    </div>
    <?php else: ?>
    <?php foreach ($a_hospitaliser as $h):
        $hNom = strtoupper($h['nom']).' '.($h['prenom']??'');
        $hInit = strtoupper(substr($h['nom']??'X',0,1).substr($h['prenom']??'X',0,1));
        $consultId = $h['consult_id'] ?? ($h['id'] ?? 0);
    ?>
    <div class="install-card <?= !empty($h['est_transfert']) ? 'border-start border-4 border-warning' : '' ?>">
        <div class="install-head">
            <div class="install-avatar" style="<?= !empty($h['est_transfert']) ? 'background:#f59e0b;' : '' ?>"><?= $hInit ?></div>
            <div style="flex:1;min-width:0;">
                <div class="fw-bold fs-6">
                    <?= htmlspecialchars($hNom) ?>
                    <?php if (!empty($h['est_transfert'])): ?>
                    <span class="badge bg-warning text-dark ms-1" style="font-size:.65rem;">
                        <i class="bi bi-arrow-left-right me-1"></i>Transfert
                    </span>
                    <?php endif; ?>
                </div>
                <div class="text-muted" style="font-size:.77rem;">
                    Dossier&nbsp;<?= htmlspecialchars($h['dossier_numero'] ?? '') ?>
                    <?php if (!empty($h['medecin_nom'])): ?> · Dr.&nbsp;<?= htmlspecialchars($h['medecin_nom']) ?><?php endif; ?>
                    <?php if (!empty($h['motif_admission'])): ?> · <?= htmlspecialchars($h['motif_admission']) ?><?php endif; ?>
                </div>
            </div>
            <button class="inf-btn inf-btn-primary fw-bold"
                    onclick="startAdmission('<?= $consultId ?>', '<?= addslashes($hNom) ?>', '<?= (int)$h['id'] ?>')">
                <i class="bi bi-hospital"></i> <?= !empty($h['est_transfert']) ? 'Recevoir' : 'Installer' ?>
            </button>
        </div>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>

    <!-- Résumé lits libres -->
    <div class="mt-4 p-4 bg-white rounded-4 border">
        <div class="section-title-txt mb-3"><i class="bi bi-hospital text-success"></i>Lits disponibles</div>
        <?php if ($nb_lits_lib === 0): ?>
        <p class="text-muted mb-0"><i class="bi bi-dash-circle me-1"></i>Aucun lit disponible dans votre service.</p>
        <?php else: ?>
        <div class="d-flex flex-wrap gap-2">
            <?php foreach ($lits_service as $l): if (!$l['occupied_by_patient_id']): ?>
            <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-3 py-2 rounded-3" style="font-size:.78rem;">
                <i class="bi bi-hospital me-1"></i><?= htmlspecialchars(($l['nom_chambre']??'').' — '.($l['nom_lit']??'')) ?>
            </span>
            <?php endif; endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- ════════════════════ PANE : HISTORIQUE ════════════════════ -->
<div class="inf-pane" id="infPane-historique">
    <div class="section-head">
        <div class="section-title-txt"><i class="bi bi-clock-history text-secondary"></i>Historique des hospitalisations</div>
        <span class="badge bg-secondary rounded-pill px-3"><?= count($historique_patients) ?> séjour(s)</span>
    </div>

    <?php if (empty($historique_patients)): ?>
    <div class="empty-state">
        <i class="bi bi-archive text-muted"></i>
        Aucune hospitalisation terminée dans ce service.
    </div>
    <?php else: ?>

    <!-- Barre de recherche -->
    <div class="mb-3" style="max-width:360px">
        <div class="input-group input-group-sm">
            <span class="input-group-text bg-white border-end-0">
                <i class="bi bi-search text-muted"></i>
            </span>
            <input type="text" id="histSearch" class="form-control border-start-0 ps-0"
                   placeholder="Rechercher par nom ou N° dossier…"
                   oninput="filtrerHistorique(this.value)">
        </div>
    </div>

    <!-- Tableau -->
    <div class="bg-white rounded-4 border overflow-hidden" style="box-shadow:0 2px 12px rgba(0,0,0,.05)">
        <table class="table table-hover mb-0 align-middle" id="histTable" style="font-size:.82rem">
            <thead style="background:#f8fafc;border-bottom:2px solid #e2e8f0">
                <tr>
                    <th class="px-3 py-3 fw-700 text-muted" style="width:36px">#</th>
                    <th class="px-3 py-3 fw-700 text-muted">Patient</th>
                    <th class="px-3 py-3 fw-700 text-muted">Admission</th>
                    <th class="px-3 py-3 fw-700 text-muted">Sortie</th>
                    <th class="px-3 py-3 fw-700 text-muted text-center">Durée</th>
                    <th class="px-3 py-3 fw-700 text-muted">Type sortie</th>
                    <th class="px-3 py-3 fw-700 text-muted">Médecin</th>
                    <th class="px-3 py-3 fw-700 text-muted text-center">Dossier</th>
                    <th class="px-3 py-3 fw-700 text-muted text-center">Action</th>
                </tr>
            </thead>
            <tbody>
            <?php
            $typeSortieLabels = [
                'guerison'    => ['label'=>'Guérison',            'bg'=>'#f0fdf4','color'=>'#15803d','icon'=>'bi-check-circle-fill'],
                'amelioration'=> ['label'=>'Amélioration',        'bg'=>'#eff6ff','color'=>'#1d4ed8','icon'=>'bi-arrow-up-circle-fill'],
                'transfert'   => ['label'=>'Transfert',           'bg'=>'#faf5ff','color'=>'#7c3aed','icon'=>'bi-arrow-left-right'],
                'deces'       => ['label'=>'Décès',               'bg'=>'#f9fafb','color'=>'#6b7280','icon'=>'bi-moon-fill'],
                'fuite'       => ['label'=>'Évasion',             'bg'=>'#fff7ed','color'=>'#c2410c','icon'=>'bi-door-open-fill'],
                'abandon'     => ['label'=>'Contre avis médical', 'bg'=>'#fffbeb','color'=>'#92400e','icon'=>'bi-x-circle-fill'],
            ];
            foreach ($historique_patients as $idx => $h):
                $nomComplet  = strtoupper($h['nom']).' '.($h['prenom'] ?? '');
                $initiales   = strtoupper(substr($h['nom']??'X',0,1).substr($h['prenom']??'X',0,1));
                $age         = !empty($h['date_naissance'])
                                ? date_diff(date_create($h['date_naissance']), date_create('today'))->y . ' ans'
                                : '—';
                $dateAdm     = !empty($h['date_admission'])        ? date('d/m/Y H:i', strtotime($h['date_admission']))        : '—';
                $dateSortie  = !empty($h['date_sortie_effective']) ? date('d/m/Y H:i', strtotime($h['date_sortie_effective'])) : '—';
                $duree       = is_numeric($h['duree_sejour']) ? (int)$h['duree_sejour'] : 0;
                $ts          = $typeSortieLabels[$h['type_sortie'] ?? ''] ?? ['label'=>'—','bg'=>'#f8fafc','color'=>'#94a3b8','icon'=>'bi-dash'];
                $litLabel    = trim(($h['nom_chambre'] ?? '') . ' · ' . ($h['nom_lit'] ?? ''), ' ·');
                // Réadmission : pas possible si décès
                $peutReadmettre = ($h['type_sortie'] ?? '') !== 'deces';
                $hospIdJs   = (int)($h['hosp_id'] ?? 0);
                $nomPatJs   = addslashes($nomComplet);
                $dateAdmJs  = addslashes($dateAdm);
                $dateSortJs = addslashes($dateSortie);
            ?>
            <tr class="hist-row" data-search="<?= htmlspecialchars(strtolower($nomComplet . ' ' . ($h['dossier_numero']??''))) ?>">
                <td class="px-3 text-muted"><?= $idx + 1 ?></td>
                <td class="px-3">
                    <div class="d-flex align-items-center gap-2">
                        <div style="width:34px;height:34px;border-radius:10px;
                                    background:linear-gradient(135deg,#64748b,#94a3b8);
                                    display:flex;align-items:center;justify-content:center;
                                    font-size:.7rem;font-weight:800;color:#fff;flex-shrink:0">
                            <?= $initiales ?>
                        </div>
                        <div>
                            <div class="fw-bold text-dark" style="font-size:.83rem"><?= htmlspecialchars($nomComplet) ?></div>
                            <div class="text-muted" style="font-size:.7rem">
                                <?= htmlspecialchars($h['dossier_numero'] ?? '—') ?>
                                <?= $litLabel ? ' · <span style="color:#7c3aed">'.htmlspecialchars($litLabel).'</span>' : '' ?>
                            </div>
                        </div>
                    </div>
                </td>
                <td class="px-3 text-muted"><?= $dateAdm ?></td>
                <td class="px-3 text-muted"><?= $dateSortie ?></td>
                <td class="px-3 text-center">
                    <span style="background:#f1f5f9;color:#475569;border-radius:20px;
                                 padding:3px 10px;font-size:.72rem;font-weight:700;white-space:nowrap">
                        <?= $duree ?> j
                    </span>
                </td>
                <td class="px-3">
                    <span style="background:<?= $ts['bg'] ?>;color:<?= $ts['color'] ?>;
                                 border-radius:20px;padding:4px 10px;
                                 font-size:.7rem;font-weight:800;white-space:nowrap;
                                 display:inline-flex;align-items:center;gap:5px">
                        <i class="bi <?= $ts['icon'] ?>"></i> <?= $ts['label'] ?>
                    </span>
                </td>
                <td class="px-3 text-muted" style="font-size:.78rem">
                    <?= !empty($h['medecin_nom']) ? 'Dr. '.htmlspecialchars($h['medecin_nom']) : '—' ?>
                </td>
                <td class="px-3 text-center">
                    <a href="<?= BASE_URL ?>patients/dossier/<?= $h['patient_id'] ?>"
                       class="btn btn-sm rounded-3"
                       style="background:#eff6ff;color:#3b82f6;border:1px solid #bfdbfe;font-size:.7rem;padding:3px 10px;font-weight:700"
                       title="Voir le dossier">
                        <i class="bi bi-folder2-open"></i>
                    </a>
                </td>
                <td class="px-3 text-center">
                    <?php if ($peutReadmettre && $hospIdJs): ?>
                    <button class="btn btn-sm rounded-3"
                            style="background:#fff7ed;color:#c2410c;border:1px solid #fed7aa;font-size:.7rem;padding:3px 10px;font-weight:700;white-space:nowrap;"
                            title="Réadmettre ce patient"
                            onclick="ouvrirModalReadmission(<?= $hospIdJs ?>, '<?= $nomPatJs ?>', '<?= $dateAdmJs ?>', '<?= $dateSortJs ?>', '<?= htmlspecialchars(addslashes($litLabel)) ?>')">
                        <i class="bi bi-arrow-counterclockwise me-1"></i>Réadmettre
                    </button>
                    <?php else: ?>
                    <span class="text-muted" style="font-size:.7rem;">—</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>


<!-- ════════════════════ MODAL RÉADMISSION ════════════════════ -->
<div class="modal fade" id="modalReadmission" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered" style="max-width:480px;">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header border-0 py-3 px-4"
                 style="background:linear-gradient(135deg,#ea580c,#f97316);color:#fff;">
                <h6 class="modal-title fw-bold mb-0">
                    <i class="bi bi-arrow-counterclockwise me-2"></i>Réadmettre le patient
                </h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body px-4 py-3">
                <!-- Infos patient -->
                <div class="rounded-3 p-3 mb-3" style="background:#fff7ed;border:1px solid #fed7aa;">
                    <div class="fw-bold text-dark mb-1" id="rdmPatientNom" style="font-size:.92rem;"></div>
                    <div class="d-flex gap-3 flex-wrap mt-1" style="font-size:.75rem;color:#92400e;">
                        <span><i class="bi bi-calendar-event me-1"></i>Admis le : <strong id="rdmDateAdm"></strong></span>
                        <span><i class="bi bi-box-arrow-right me-1"></i>Sorti le : <strong id="rdmDateSort"></strong></span>
                        <span id="rdmLitSpan"><i class="bi bi-hospital me-1"></i>Lit : <strong id="rdmLit"></strong></span>
                    </div>
                </div>
                <!-- Alerte lit -->
                <div id="rdmAlerteLit" class="alert alert-warning py-2 px-3 rounded-3 mb-3" style="font-size:.78rem;display:none;">
                    <i class="bi bi-exclamation-triangle me-1"></i>
                    Le lit d'origine est peut-être occupé. Si c'est le cas, le patient sera réadmis sans lit assigné et vous devrez lui en attribuer un.
                </div>
                <!-- Motif -->
                <div class="mb-1">
                    <label class="form-label small fw-bold text-secondary mb-1">Motif de réadmission</label>
                    <textarea id="rdmMotif" class="form-control rounded-3" rows="2"
                              placeholder="Ex : Sortie effectuée par erreur, patient toujours sous traitement…"
                              style="font-size:.82rem;resize:none;"></textarea>
                </div>
                <input type="hidden" id="rdmHospId">
            </div>
            <div class="modal-footer border-0 px-4 py-3" style="background:#f8fafc;">
                <button class="btn btn-sm btn-light rounded-3 fw-600" data-bs-dismiss="modal">
                    <i class="bi bi-x me-1"></i>Annuler
                </button>
                <button class="btn btn-sm rounded-3 fw-bold text-white" id="rdmBtnConfirmer"
                        style="background:linear-gradient(135deg,#ea580c,#f97316);border:none;padding:6px 18px;"
                        onclick="confirmerReadmission()">
                    <i class="bi bi-arrow-counterclockwise me-1"></i>Confirmer la réadmission
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ════════════════════ MODAL INSTALLATION ════════════════════ -->
<div class="modal fade" id="modalAdmit" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow-lg">
            <div class="modal-header" style="background:linear-gradient(135deg,#1d4ed8,#3b82f6);color:#fff;border:none;border-radius:20px 20px 0 0;padding:20px 24px;">
                <h5 class="modal-title fw-bold"><i class="bi bi-hospital me-2"></i>Installation du patient</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="<?= BASE_URL ?>hospitalisation/valider-installation" method="POST">
                <input type="hidden" name="admission_id" id="admitId">
                <input type="hidden" name="patient_id" id="admitPatientId">
                <div class="modal-body p-4">
                    <div class="mb-4 p-3 rounded-3" style="background:#eff6ff;border:1px solid #bfdbfe;">
                        <div class="text-muted small mb-1">Patient à installer :</div>
                        <h5 id="admitPatientName" class="fw-bold text-primary mb-0">---</h5>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Lit disponible</label>
                        <select name="lit_id" class="form-select" required>
                            <option value="">— Choisir un lit —</option>
                            <?php foreach ($lits_service as $l): if (!$l['occupied_by_patient_id']): ?>
                            <option value="<?= $l['id'] ?>">
                                <?= htmlspecialchars(($l['nom_chambre']??'').' — '.($l['nom_lit']??'')) ?>
                            </option>
                            <?php endif; endforeach; ?>
                        </select>
                    </div>
                    <div class="alert alert-info border-0 rounded-3 small py-2">
                        <i class="bi bi-info-circle-fill me-1"></i>
                        Valider l'installation créera la feuille de soins du patient.
                    </div>
                </div>
                <div class="modal-footer border-0 px-4 pb-4 pt-0">
                    <button type="button" class="btn btn-light rounded-3 px-4" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary rounded-3 px-4 fw-bold">
                        <i class="bi bi-check2 me-1"></i>Confirmer l'installation
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ════════════════════ TOAST FEEDBACK ════════════════════ -->
<div class="position-fixed bottom-0 end-0 p-3" style="z-index:9999;">
    <div id="infToast" class="toast align-items-center border-0 bg-success text-white" role="alert">
        <div class="d-flex">
            <div class="toast-body fw-semibold" id="infToastMsg">Opération réussie.</div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    </div>
</div>

<script>
// ── Horloge ──
(function tick() {
    document.getElementById('infClock').textContent = new Date().toLocaleTimeString('fr-FR');
    setTimeout(tick, 1000);
})();

// ── Tabs ──
function switchInfTab(tab) {
    document.querySelectorAll('.inf-tab').forEach(b => b.classList.remove('active'));
    document.querySelectorAll('.inf-pane').forEach(p => p.classList.remove('active'));
    document.getElementById('infTab-' + tab).classList.add('active');
    document.getElementById('infPane-' + tab).classList.add('active');
}

// ── Notification visuelle : titre de l'onglet du navigateur clignotant ──
// Alerte titre navigateur uniquement si des soins sont EN RETARD (date dépassée)
(function notifSoinsAttente() {
    const nbAttente = <?= (int)$nb_soins_en_retard ?>;
    if (nbAttente <= 0) return;

    const titreOriginal = document.title;
    const titreAlerte = '🔔 ' + nbAttente + ' soin(s) en retard';
    let isAlt = false;

    // Alterne toutes les 1.2 secondes tant que l'utilisateur n'a pas regardé l'onglet
    const interval = setInterval(() => {
        if (document.hidden) {
            isAlt = !isAlt;
            document.title = isAlt ? titreAlerte : titreOriginal;
        } else {
            document.title = titreOriginal;
        }
    }, 1200);

    // Stoppe le clignotement au bout de 5 minutes (évite la pollution prolongée)
    setTimeout(() => {
        clearInterval(interval);
        document.title = titreOriginal;
    }, 5 * 60 * 1000);

    // Restaure le titre dès que l'infirmier revient sur l'onglet
    document.addEventListener('visibilitychange', () => {
        if (!document.hidden) document.title = titreOriginal;
    });
})();


// ── Recherche dans l'historique ──
function filtrerHistorique(val) {
    const q = val.toLowerCase().trim();
    document.querySelectorAll('#histTable .hist-row').forEach(row => {
        row.style.display = (!q || row.dataset.search.includes(q)) ? '' : 'none';
    });
}

// ── Réadmission ──
function ouvrirModalReadmission(hospId, nomPatient, dateAdm, dateSort, litLabel) {
    document.getElementById('rdmHospId').value     = hospId;
    document.getElementById('rdmPatientNom').textContent = nomPatient;
    document.getElementById('rdmDateAdm').textContent    = dateAdm;
    document.getElementById('rdmDateSort').textContent   = dateSort;
    const litSpan = document.getElementById('rdmLitSpan');
    if (litLabel) {
        document.getElementById('rdmLit').textContent = litLabel;
        if (litSpan) litSpan.style.display = '';
    } else {
        if (litSpan) litSpan.style.display = 'none';
    }
    document.getElementById('rdmMotif').value = '';
    document.getElementById('rdmAlerteLit').style.display = litLabel ? 'block' : 'none';
    const btn = document.getElementById('rdmBtnConfirmer');
    btn.disabled = false;
    btn.innerHTML = '<i class="bi bi-arrow-counterclockwise me-1"></i>Confirmer la réadmission';
    new bootstrap.Modal(document.getElementById('modalReadmission')).show();
}

async function confirmerReadmission() {
    const hospId = document.getElementById('rdmHospId').value;
    const motif  = document.getElementById('rdmMotif').value.trim()
                   || 'Réadmission suite à sortie par erreur';
    const btn    = document.getElementById('rdmBtnConfirmer');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>En cours…';

    try {
        const res  = await fetch('<?= BASE_URL ?>hospitalisation/readmettre/' + hospId, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ motif })
        });
        const data = await res.json();
        bootstrap.Modal.getInstance(document.getElementById('modalReadmission'))?.hide();
        if (data.success) {
            // Toast succès
            const t = document.createElement('div');
            t.className = 'position-fixed bottom-0 end-0 m-3 alert alert-success shadow rounded-3 fw-600';
            t.style.cssText = 'z-index:9999;font-size:.82rem;min-width:280px;';
            t.innerHTML = '<i class="bi bi-check-circle-fill me-2 text-success"></i>' + data.message;
            document.body.appendChild(t);
            setTimeout(() => { t.remove(); location.reload(); }, 2500);
        } else {
            alert('Erreur : ' + (data.message || 'inconnue'));
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-arrow-counterclockwise me-1"></i>Confirmer la réadmission';
        }
    } catch (e) {
        alert('Erreur réseau. Veuillez réessayer.');
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-arrow-counterclockwise me-1"></i>Confirmer la réadmission';
    }
}

// ── Ouvrir modale admission ──
function startAdmission(id, name, patientId) {
    document.getElementById('admitId').value = id;
    document.getElementById('admitPatientId').value = patientId || '';
    document.getElementById('admitPatientName').textContent = name;
    new bootstrap.Modal(document.getElementById('modalAdmit')).show();
}

// ── Scroll vers patient (depuis lits) ──
function scrollToPatient(patientId) {
    const card = document.getElementById('patCard-' + patientId);
    if (card) {
        switchInfTab('patients');
        setTimeout(() => {
            card.scrollIntoView({ behavior:'smooth', block:'center' });
            card.style.transition = 'box-shadow .3s, border-color .3s';
            card.style.borderColor = '#3b82f6';
            card.style.boxShadow = '0 0 0 3px rgba(59,130,246,.3)';
            setTimeout(() => { card.style.borderColor = ''; card.style.boxShadow = ''; }, 1800);
        }, 100);
    }
}

// ── Auto-refresh toutes les 2 minutes — pause si un modal est ouvert ──
(function scheduleAutoRefresh() {
    setTimeout(() => {
        const anyModalOpen = document.querySelector('.modal.show');
        if (anyModalOpen) {
            // Un modal est ouvert — reporter de 60 s et rescheduler
            scheduleAutoRefresh();
        } else {
            location.reload();
        }
    }, 120000);
})();

// ── Changer de lit ────────────────────────────────────────────
const BURL = '<?= BASE_URL ?>';
let clitHospId = null, clitAncienLitId = null;

function ouvrirChangerLit(hospId, litActuelId, nomPatient, nomLit) {
    clitHospId      = hospId;
    clitAncienLitId = litActuelId;
    document.getElementById('clitPatientNom').textContent = nomPatient;
    document.getElementById('clitLitActuel').textContent  = nomLit || 'Non assigné';
    document.getElementById('clitNouveauLit').value       = '';
    const errDiv = document.getElementById('clitErreur');
    errDiv.classList.add('d-none'); errDiv.classList.remove('d-flex');
    const btn = document.getElementById('clitBtnConfirm');
    btn.disabled = false;
    btn.innerHTML = '<i class="bi bi-check2 me-1"></i>Confirmer';
    new bootstrap.Modal(document.getElementById('modalChangerLit')).show();
}

function confirmerChangerLit() {
    const nouveauLitId = document.getElementById('clitNouveauLit').value;
    const errDiv = document.getElementById('clitErreur');
    const errMsg = document.getElementById('clitErreurMsg');
    const showErr = msg => {
        errMsg.textContent = msg;
        errDiv.classList.remove('d-none'); errDiv.classList.add('d-flex');
    };
    const hideErr = () => {
        errDiv.classList.add('d-none'); errDiv.classList.remove('d-flex');
    };

    if (!nouveauLitId) { showErr('Veuillez sélectionner un lit disponible.'); return; }
    hideErr();

    const btn = document.getElementById('clitBtnConfirm');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Déplacement…';

    const fd = new FormData();
    fd.append('hosp_id',        clitHospId);
    fd.append('nouveau_lit_id', nouveauLitId);
    fd.append('ancien_lit_id',  clitAncienLitId || '');

    fetch(BURL + 'hospitalisation/changer-lit', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                bootstrap.Modal.getInstance(document.getElementById('modalChangerLit'))?.hide();
                const toast = document.createElement('div');
                toast.className = 'position-fixed bottom-0 end-0 p-3';
                toast.style.zIndex = 9999;
                toast.innerHTML = `<div class="toast show align-items-center text-white bg-success border-0 rounded-3 shadow">
                    <div class="d-flex"><div class="toast-body fw-semibold">
                        <i class="bi bi-check-circle-fill me-2"></i>${data.message}
                    </div></div></div>`;
                document.body.appendChild(toast);
                setTimeout(() => location.reload(), 1100);
            } else {
                showErr(data.message || 'Erreur.');
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-check2 me-1"></i>Confirmer';
            }
        })
        .catch(() => {
            showErr('Erreur réseau.');
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-check2 me-1"></i>Confirmer';
        });
}

// ── Transfert patient (2 étapes) ─────────────────────────────
let dashTransfertPatientId = null, dashTransfertHospId = null, dashTransfertServiceOrigId = 0;

function ouvrirModalTransfertDash(patientId, hospId, nom, serviceOrigId, litLabel) {
    dashTransfertPatientId  = patientId;
    dashTransfertHospId     = hospId;
    dashTransfertServiceOrigId = serviceOrigId;

    document.getElementById('dashTransfertNomPatient').textContent = nom;
    document.getElementById('dashTransfertLitBadge').textContent   = litLabel || '';

    // Filtrer le service actuel de la liste externe
    const sel = document.getElementById('dashExterneServiceId');
    Array.from(sel.options).forEach(o => {
        o.hidden = (o.value !== '' && parseInt(o.value) === serviceOrigId);
    });
    sel.value = '';
    sel.classList.remove('is-invalid');

    _dashShowStep('step1');
    new bootstrap.Modal(document.getElementById('modalTransfertDash')).show();
}

function dashRetourStep1() {
    _dashShowStep('step1');
    _dashResetBtn('dashBtnConfirmerInterne', '<i class="bi bi-check2 me-1"></i> Confirmer le changement de lit');
    _dashResetBtn('dashBtnConfirmerExterne', '<i class="bi bi-check2 me-1"></i> Confirmer le transfert');
}

function dashChoisirInterne() {
    _dashShowStep('interne');
    document.getElementById('dashTransfertSousTitre').textContent = 'Transfert interne — Changement de lit';
    document.getElementById('dashInterneMotif').value = '';
    const sel    = document.getElementById('dashInterneNouveauLit');
    const loader = document.getElementById('dashInterneLitsLoader');
    sel.innerHTML = '<option value="">Chargement…</option>';
    sel.disabled  = true;
    loader.classList.remove('d-none');
    fetch(BURL + 'hospitalisation/lits-disponibles?service_id=' + dashTransfertServiceOrigId)
        .then(r => r.json())
        .then(lits => {
            loader.classList.add('d-none');
            sel.innerHTML = '<option value="">-- Choisir un nouveau lit --</option>';
            if (!Array.isArray(lits) || lits.length === 0) {
                sel.innerHTML += '<option disabled>⚠ Aucun lit disponible dans ce service</option>';
            } else {
                lits.forEach(l => {
                    const lbl = l.nom_chambre ? `${l.nom_lit} (${l.nom_chambre})` : l.nom_lit;
                    sel.innerHTML += `<option value="${l.id}">${lbl.replace(/</g,'&lt;')}</option>`;
                });
            }
            sel.disabled = false;
        })
        .catch(() => { loader.classList.add('d-none'); sel.innerHTML = '<option value="">Erreur de chargement</option>'; });
}

function dashChoisirExterne() {
    _dashShowStep('externe');
    document.getElementById('dashTransfertSousTitre').textContent = 'Transfert externe — Autre service';
    document.getElementById('dashExterneMotif').value = '';
    document.getElementById('dashExterneServiceId').classList.remove('is-invalid');
}

function dashConfirmerInterne() {
    const litId = document.getElementById('dashInterneNouveauLit').value;
    if (!litId) { document.getElementById('dashInterneNouveauLit').classList.add('is-invalid'); return; }
    document.getElementById('dashInterneNouveauLit').classList.remove('is-invalid');
    const btn = document.getElementById('dashBtnConfirmerInterne');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Déplacement…';
    const fd = new FormData();
    fd.append('hosp_id', dashTransfertHospId);
    fd.append('nouveau_lit_id', litId);
    fd.append('ancien_lit_id', '');
    fd.append('motif', document.getElementById('dashInterneMotif').value);
    fetch(BURL + 'hospitalisation/changer-lit', { method:'POST', body:fd, headers:{'X-Requested-With':'XMLHttpRequest'} })
        .then(r => r.json())
        .then(d => {
            if (d.success) {
                bootstrap.Modal.getInstance(document.getElementById('modalTransfertDash'))?.hide();
                _dashToast(d.message, 'success');
                setTimeout(() => location.reload(), 1400);
            } else {
                alert('Erreur : ' + (d.message || 'Impossible de changer de lit.'));
                _dashResetBtn('dashBtnConfirmerInterne', '<i class="bi bi-check2 me-1"></i> Confirmer le changement de lit');
            }
        })
        .catch(() => { alert('Erreur réseau.'); _dashResetBtn('dashBtnConfirmerInterne', '<i class="bi bi-check2 me-1"></i> Confirmer le changement de lit'); });
}

function dashConfirmerExterne() {
    const serviceId = document.getElementById('dashExterneServiceId').value;
    if (!serviceId) { document.getElementById('dashExterneServiceId').classList.add('is-invalid'); return; }
    document.getElementById('dashExterneServiceId').classList.remove('is-invalid');
    const btn = document.getElementById('dashBtnConfirmerExterne');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Transfert…';
    const fd = new FormData();
    fd.append('service_id', serviceId);
    fd.append('motif', document.getElementById('dashExterneMotif').value);
    fetch(BURL + 'hospitalisation/transferer/' + dashTransfertPatientId, { method:'POST', body:fd, headers:{'X-Requested-With':'XMLHttpRequest'} })
        .then(r => r.json())
        .then(d => {
            if (d.success) {
                bootstrap.Modal.getInstance(document.getElementById('modalTransfertDash'))?.hide();
                _dashToast(d.message, 'success');
                setTimeout(() => location.reload(), 1400);
            } else {
                alert('Erreur : ' + (d.message || 'Transfert impossible.'));
                _dashResetBtn('dashBtnConfirmerExterne', '<i class="bi bi-check2 me-1"></i> Confirmer le transfert');
            }
        })
        .catch(() => { alert('Erreur réseau.'); _dashResetBtn('dashBtnConfirmerExterne', '<i class="bi bi-check2 me-1"></i> Confirmer le transfert'); });
}

function _dashShowStep(step) {
    const map = { step1:'dashTransfertStep1', interne:'dashTransfertStepInterne', externe:'dashTransfertStepExterne' };
    Object.entries(map).forEach(([k, id]) => document.getElementById(id).classList.toggle('d-none', k !== step));
    if (step === 'step1') document.getElementById('dashTransfertSousTitre').textContent = 'Choisissez le type de transfert';
}
function _dashResetBtn(id, html) { const b = document.getElementById(id); if(b){b.disabled=false;b.innerHTML=html;} }
function _dashToast(msg, type) {
    const wrap = document.createElement('div');
    wrap.className = 'position-fixed bottom-0 end-0 p-3'; wrap.style.zIndex = 9999;
    wrap.innerHTML = `<div class="toast show align-items-center text-white ${type==='success'?'bg-success':'bg-danger'} border-0 rounded-3 shadow">
        <div class="d-flex"><div class="toast-body fw-semibold"><i class="bi bi-check-circle-fill me-2"></i>${msg}</div></div></div>`;
    document.body.appendChild(wrap);
    setTimeout(() => wrap.remove(), 3000);
}

// ════════════════════════════════════════════════════════════════
//  ADMETTRE SUR LIT — patient sans lit assigné
// ════════════════════════════════════════════════════════════════
let admettrePatientId   = null;
let admettreServiceId   = null;
let admettreLitChoisi   = null;
let admettreMode        = 'propre'; // 'propre' | 'externe'

function ouvrirAdmettreLit(patientId, nomPatient, serviceId) {
    admettrePatientId  = patientId;
    admettreServiceId  = serviceId;
    admettreLitChoisi  = null;
    admettreMode       = 'propre';

    document.getElementById('admPatNom').textContent = nomPatient;
    document.getElementById('admBtnConfirmer').disabled = true;
    document.getElementById('admMessageBox').innerHTML = '';
    document.getElementById('admServiceBlock').classList.add('d-none');
    document.getElementById('admServiceExterne').value = '';

    // Activer le mode "Mon service" par défaut
    document.getElementById('admBtnModePropre').classList.add('active');
    document.getElementById('admBtnModeExterne').classList.remove('active');

    new bootstrap.Modal(document.getElementById('modalAdmettreLit')).show();
    _admChargerBanner('propre');
    _admChargerLits(serviceId);
}

function admChoisirMode(mode) {
    admettreMode      = mode;
    admettreLitChoisi = null;
    document.getElementById('admBtnConfirmer').disabled = true;
    document.getElementById('admMessageBox').innerHTML = '';

    if (mode === 'propre') {
        document.getElementById('admBtnModePropre').classList.add('active');
        document.getElementById('admBtnModeExterne').classList.remove('active');
        document.getElementById('admServiceBlock').classList.add('d-none');
        document.getElementById('admServiceExterne').value = '';
        _admChargerBanner('propre');
        _admChargerLits(admettreServiceId);
    } else {
        document.getElementById('admBtnModePropre').classList.remove('active');
        document.getElementById('admBtnModeExterne').classList.add('active');
        document.getElementById('admServiceBlock').classList.remove('d-none');
        _admChargerBanner('externe');
        document.getElementById('admListLits').innerHTML =
            '<div class="text-center text-muted py-3 small"><i class="bi bi-arrow-up me-1"></i>Choisissez un service d\'hébergement ci-dessus.</div>';
    }
}

function admChoisirServiceExterne(serviceId) {
    admettreLitChoisi = null;
    document.getElementById('admBtnConfirmer').disabled = true;
    document.getElementById('admMessageBox').innerHTML = '';
    if (!serviceId) {
        document.getElementById('admListLits').innerHTML =
            '<div class="text-center text-muted py-3 small"><i class="bi bi-arrow-up me-1"></i>Choisissez un service d\'hébergement ci-dessus.</div>';
        return;
    }
    _admChargerLits(serviceId);
}

function _admChargerLits(serviceId) {
    if (!serviceId) return;
    const list = document.getElementById('admListLits');
    list.innerHTML = '<div class="text-center text-muted py-3"><span class="spinner-border spinner-border-sm me-2"></span>Chargement des lits…</div>';

    fetch(BURL + 'hospitalisation/lits-disponibles?service_id=' + serviceId)
        .then(r => r.json())
        .then(lits => {
            if (!Array.isArray(lits) || lits.length === 0) {
                list.innerHTML = `<div class="alert alert-warning border-0 rounded-3 py-2 mb-0" style="font-size:.85rem;">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>Aucun lit disponible dans ce service actuellement.</div>`;
                return;
            }
            // Grouper par chambre
            const groupes = {};
            lits.forEach(l => {
                const ch = l.nom_chambre || 'Sans chambre';
                if (!groupes[ch]) groupes[ch] = [];
                groupes[ch].push(l);
            });
            let html = '<div class="d-flex flex-column gap-3">';
            Object.keys(groupes).sort().forEach(ch => {
                html += `<div><div class="fw-bold text-muted small mb-2 text-uppercase" style="letter-spacing:.5px;">
                    <i class="bi bi-door-closed-fill me-1"></i>${escapeHtmlAdm(ch)}</div>
                    <div class="d-flex flex-wrap gap-2">`;
                groupes[ch].forEach(l => {
                    html += `<button type="button" class="adm-lit-btn"
                        data-lit-id="${l.id}" data-lit-label="${escapeHtmlAdm(l.nom_lit)}"
                        onclick="selectionnerLitAdmettre(this)">
                        <i class="bi bi-hospital me-1"></i>${escapeHtmlAdm(l.nom_lit)}</button>`;
                });
                html += `</div></div>`;
            });
            html += '</div>';
            list.innerHTML = html;
        })
        .catch(() => {
            list.innerHTML = '<div class="text-danger py-3"><i class="bi bi-x-circle-fill me-1"></i>Erreur de chargement des lits.</div>';
        });
}

function _admChargerBanner(mode) {
    const banner = document.getElementById('admInfoBanner');
    if (mode === 'propre') {
        banner.style.cssText = 'background:#fff7ed;color:#9a3412;font-size:.83rem;';
        banner.innerHTML = '<i class="bi bi-house-fill me-1"></i>Le patient sera installé sur un lit de <strong>son propre service</strong>. Son dossier reste inchangé.';
    } else {
        banner.style.cssText = 'background:#eff6ff;color:#1e40af;font-size:.83rem;';
        banner.innerHTML = '<i class="bi bi-arrow-left-right me-1"></i>Le patient sera <strong>hébergé physiquement</strong> dans un autre service mais reste rattaché administrativement à son service d\'origine.';
    }
}

function selectionnerLitAdmettre(btn) {
    document.querySelectorAll('.adm-lit-btn.selected').forEach(b => b.classList.remove('selected'));
    btn.classList.add('selected');
    admettreLitChoisi = parseInt(btn.dataset.litId, 10);
    document.getElementById('admBtnConfirmer').disabled = false;
    document.getElementById('admMessageBox').innerHTML =
        `<div class="alert alert-info border-0 rounded-3 py-2 mb-0" style="font-size:.83rem;">
            <i class="bi bi-info-circle me-1"></i>Lit sélectionné : <strong>${btn.dataset.litLabel}</strong>
         </div>`;
}

function escapeHtmlAdm(s) {
    return String(s || '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[c]));
}

function confirmerAdmettreLit() {
    if (!admettrePatientId || !admettreLitChoisi) return;
    const btn = document.getElementById('admBtnConfirmer');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Admission en cours…';

    let fetchPromise;

    if (admettreMode === 'propre') {
        // Lit dans le même service → assigner-lit
        fetchPromise = fetch(BURL + 'hospitalisation/assigner-lit/' + admettrePatientId, {
            method  : 'POST',
            headers : { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            body    : JSON.stringify({ lit_id: admettreLitChoisi })
        });
    } else {
        // Hébergement dans un autre service → installer-lit-externe
        const fd = new FormData();
        fd.append('patient_id', admettrePatientId);
        fd.append('lit_id', admettreLitChoisi);
        fetchPromise = fetch(BURL + 'hospitalisation/installer-lit-externe', { method: 'POST', body: fd });
    }

    fetchPromise
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            bootstrap.Modal.getInstance(document.getElementById('modalAdmettreLit'))?.hide();
            const toast = document.createElement('div');
            toast.className = 'position-fixed bottom-0 end-0 p-3';
            toast.style.zIndex = 9999;
            const msg = data.message || (admettreMode === 'externe'
                ? `Hébergé en ${data.nom_service || 'autre service'} — ${data.nom_chambre || ''} ${data.nom_lit || ''}`
                : 'Admission confirmée');
            toast.innerHTML = `<div class="toast show align-items-center text-white bg-success border-0 rounded-3 shadow">
                <div class="d-flex"><div class="toast-body fw-semibold">
                    <i class="bi bi-check-circle-fill me-2"></i>${msg}
                </div></div></div>`;
            document.body.appendChild(toast);
            setTimeout(() => location.reload(), 1300);
        } else {
            document.getElementById('admMessageBox').innerHTML =
                `<div class="alert alert-danger border-0 rounded-3 py-2 mb-0" style="font-size:.83rem;">
                    <i class="bi bi-x-circle-fill me-1"></i>${data.message || 'Erreur'}
                 </div>`;
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-check-lg me-1"></i> Confirmer l\'admission';
        }
    })
    .catch(() => {
        document.getElementById('admMessageBox').innerHTML =
            '<div class="alert alert-danger border-0 rounded-3 py-2 mb-0" style="font-size:.83rem;"><i class="bi bi-wifi-off me-1"></i>Erreur réseau.</div>';
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-check-lg me-1"></i> Confirmer l\'admission';
    });
}

// ── Libérer un lit (sortie patient) ──────────────────────────
let liberPatientId = null;

function ouvrirLibererLit(patientId, nom, nomLit) {
    liberPatientId = patientId;
    document.getElementById('libNomPatient').textContent = nom;
    document.getElementById('libNomLit').textContent     = nomLit || 'Non assigné';
    // Réinitialiser la sélection radio sur la première option
    const radios = document.querySelectorAll('input[name="motifSortieLib"]');
    if (radios.length) radios[0].checked = true;
    const btn = document.getElementById('btnConfirmerLiberer');
    btn.disabled = false;
    btn.innerHTML = '<i class="bi bi-box-arrow-right me-1"></i> Confirmer la sortie';
    new bootstrap.Modal(document.getElementById('modalLibererLit')).show();
}

function confirmerLibererLit() {
    const motif = document.querySelector('input[name="motifSortieLib"]:checked')?.value || 'Sortie normale';
    const btn   = document.getElementById('btnConfirmerLiberer');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Sortie en cours…';

    fetch(BURL + 'hospitalisation/liberer-lit/' + liberPatientId, {
        method : 'POST',
        headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        body   : JSON.stringify({ motif_sortie: motif })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            bootstrap.Modal.getInstance(document.getElementById('modalLibererLit'))?.hide();
            const toast = document.createElement('div');
            toast.className = 'position-fixed bottom-0 end-0 p-3';
            toast.style.zIndex = 9999;
            toast.innerHTML = `<div class="toast show align-items-center text-white bg-success border-0 rounded-3 shadow">
                <div class="d-flex"><div class="toast-body fw-semibold">
                    <i class="bi bi-check-circle-fill me-2"></i>${data.message}
                </div></div></div>`;
            document.body.appendChild(toast);
            setTimeout(() => location.reload(), 1400);
        } else {
            alert('Erreur : ' + (data.message || 'Impossible de libérer le lit.'));
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-box-arrow-right me-1"></i> Confirmer la sortie';
        }
    })
    .catch(() => {
        alert('Erreur réseau.');
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-box-arrow-right me-1"></i> Confirmer la sortie';
    });
}

/* ── Recherche dynamique patients ─────────────────────────────────── */
function filtrerPatients(q) {
    const needle  = q.trim().toLowerCase();
    const grid    = document.getElementById('patGrid');
    const noRes   = document.getElementById('patNoResult');
    const badge   = document.getElementById('patCountBadge');
    if (!grid) return;
    const cards   = grid.querySelectorAll('.pat-card');
    let visible   = 0;
    cards.forEach(card => {
        const hay = (card.dataset.search || '').toLowerCase();
        const show = needle === '' || hay.includes(needle);
        card.style.display = show ? '' : 'none';
        if (show) visible++;
    });
    // badge & message vide
    badge.textContent = visible + ' patient' + (visible > 1 ? 's' : '') + (needle ? ' trouvé(s)' : '');
    noRes.style.display = (visible === 0 && needle !== '') ? 'block' : 'none';
    // highlight léger de la recherche active
    const input = document.getElementById('patSearchInput');
    if (input) input.style.borderColor = needle ? '#3b82f6' : '';
}
</script>

<!-- ═══════════════════════════════════════════════════════════════
     MODAL — TRANSFERT PATIENT (2 étapes)
═══════════════════════════════════════════════════════════════ -->
<div class="modal fade" id="modalTransfertDash" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow rounded-4" style="overflow:hidden">

            <div class="modal-header border-0 px-4 pt-4 pb-2"
                 style="background:linear-gradient(135deg,#f59e0b,#d97706)">
                <div>
                    <h5 class="fw-bold mb-0 text-white">
                        <i class="bi bi-arrow-left-right me-2"></i>Transfert de patient
                    </h5>
                    <small class="text-white opacity-75" id="dashTransfertSousTitre">Choisissez le type de transfert</small>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body px-4 pb-4">

                <!-- Bandeau patient -->
                <div class="alert alert-warning border-0 rounded-3 small mb-3 py-2 d-flex align-items-center gap-2">
                    <i class="bi bi-person-fill fs-5"></i>
                    <strong id="dashTransfertNomPatient">—</strong>
                    <span id="dashTransfertLitBadge" class="ms-auto badge bg-secondary"></span>
                </div>

                <!-- ── ÉTAPE 1 : Choix ── -->
                <div id="dashTransfertStep1">
                    <p class="text-muted small mb-3 fw-semibold">
                        <i class="bi bi-signpost-2 me-1"></i>Sélectionnez le type de transfert :
                    </p>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="card border-2 border-primary rounded-4 p-4 text-center h-100"
                                 role="button" style="cursor:pointer;transition:all .15s"
                                 onmouseover="this.style.background='#eff6ff'" onmouseout="this.style.background=''"
                                 onclick="dashChoisirInterne()">
                                <div class="mb-2" style="font-size:2.2rem">🛏️</div>
                                <h6 class="fw-bold text-primary mb-1">Transfert interne</h6>
                                <p class="text-muted small mb-0">Changer de lit dans le <strong>même service</strong></p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card border-2 border-danger rounded-4 p-4 text-center h-100"
                                 role="button" style="cursor:pointer;transition:all .15s"
                                 onmouseover="this.style.background='#fff5f5'" onmouseout="this.style.background=''"
                                 onclick="dashChoisirExterne()">
                                <div class="mb-2" style="font-size:2.2rem">🏥</div>
                                <h6 class="fw-bold text-danger mb-1">Transfert externe</h6>
                                <p class="text-muted small mb-0">Envoyer vers un <strong>autre service</strong></p>
                            </div>
                        </div>
                    </div>
                    <div class="text-end mt-3">
                        <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Annuler</button>
                    </div>
                </div>

                <!-- ── ÉTAPE 2A : Interne ── -->
                <div id="dashTransfertStepInterne" class="d-none">
                    <div class="alert alert-primary border-0 rounded-3 small py-2 mb-3">
                        <i class="bi bi-info-circle me-1"></i>
                        Le patient reste dans le <strong>même service</strong>, seul son lit change.
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-uppercase text-muted">
                            Nouveau lit <span class="text-danger">*</span>
                        </label>
                        <select id="dashInterneNouveauLit" class="form-select border-2 rounded-3" disabled>
                            <option value="">Chargement…</option>
                        </select>
                        <div id="dashInterneLitsLoader" class="form-text text-muted d-none">
                            <span class="spinner-border spinner-border-sm me-1"></span> Chargement…
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-uppercase text-muted">Motif</label>
                        <textarea id="dashInterneMotif" class="form-control border-2 rounded-3" rows="2"
                                  placeholder="Raison du changement de lit (optionnel)…"></textarea>
                    </div>
                    <div class="d-flex gap-2 mt-4">
                        <button type="button" class="btn btn-light rounded-pill px-3" onclick="dashRetourStep1()">
                            <i class="bi bi-arrow-left me-1"></i> Retour
                        </button>
                        <button type="button" id="dashBtnConfirmerInterne"
                                class="btn btn-primary rounded-pill px-4 flex-grow-1 fw-bold"
                                onclick="dashConfirmerInterne()">
                            <i class="bi bi-check2 me-1"></i> Confirmer le changement de lit
                        </button>
                    </div>
                </div>

                <!-- ── ÉTAPE 2B : Externe ── -->
                <div id="dashTransfertStepExterne" class="d-none">
                    <div class="alert alert-info border-0 rounded-3 small py-2 mb-3">
                        <i class="bi bi-info-circle me-1"></i>
                        L'infirmière du service de destination se chargera d'assigner un lit.
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-uppercase text-muted">
                            Service de destination <span class="text-danger">*</span>
                        </label>
                        <select id="dashExterneServiceId" class="form-select border-2 rounded-3">
                            <option value="">-- Choisir un service --</option>
                            <?php foreach(($servicesCliniques ?? []) as $sc): ?>
                            <option value="<?= (int)$sc['id'] ?>"><?= htmlspecialchars($sc['nom_service']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-uppercase text-muted">Motif du transfert</label>
                        <textarea id="dashExterneMotif" class="form-control border-2 rounded-3" rows="2"
                                  placeholder="Raison du transfert (optionnel)…"></textarea>
                    </div>
                    <div class="d-flex gap-2 mt-4">
                        <button type="button" class="btn btn-light rounded-pill px-3" onclick="dashRetourStep1()">
                            <i class="bi bi-arrow-left me-1"></i> Retour
                        </button>
                        <button type="button" id="dashBtnConfirmerExterne"
                                class="btn btn-danger rounded-pill px-4 flex-grow-1 fw-bold"
                                onclick="dashConfirmerExterne()">
                            <i class="bi bi-check2 me-1"></i> Confirmer le transfert
                        </button>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════════════
     MODAL — ADMETTRE SUR UN LIT (assignation lit)
═══════════════════════════════════════════════════════════════ -->
<style>
.adm-lit-btn {
    background:#fff7ed; color:#9a3412; border:1.5px solid #fed7aa;
    border-radius:10px; padding:9px 16px; font-size:.85rem; font-weight:700;
    cursor:pointer; transition:all .15s; display:inline-flex; align-items:center;
}
.adm-lit-btn:hover { background:#ffedd5; transform:translateY(-1px); box-shadow:0 4px 10px rgba(234,88,12,.18); }
.adm-lit-btn.selected {
    background:#ea580c; color:#fff; border-color:#ea580c;
    box-shadow:0 4px 14px rgba(234,88,12,.4);
}
.adm-lit-btn.selected:hover { background:#c2410c; }
</style>
<style>
.adm-mode-btn {
    flex:1; padding:8px 12px; border:2px solid #e2e8f0; border-radius:10px;
    background:#f8fafc; color:#64748b; font-size:.82rem; font-weight:700;
    cursor:pointer; transition:.15s; text-align:center;
}
.adm-mode-btn.active { border-color:#ea580c; background:#fff7ed; color:#ea580c; }
.adm-mode-btn:hover:not(.active) { background:#f1f5f9; border-color:#cbd5e1; color:#1e293b; }
</style>
<div class="modal fade" id="modalAdmettreLit" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width:580px">
        <div class="modal-content border-0 shadow-lg" style="border-radius:16px;overflow:hidden">

            <!-- En-tête orange -->
            <div class="modal-header border-0 p-4"
                 style="background:linear-gradient(135deg,#ea580c,#f97316)">
                <div>
                    <h5 class="modal-title fw-bold text-white mb-0">
                        <i class="bi bi-house-add-fill me-2"></i>Admettre sur un lit
                    </h5>
                    <div class="text-white opacity-75 small mt-1" id="admPatNom">—</div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <!-- Body -->
            <div class="modal-body p-4">

                <!-- Sélecteur de mode -->
                <div class="d-flex gap-2 mb-3">
                    <button id="admBtnModePropre" class="adm-mode-btn active"
                            onclick="admChoisirMode('propre')">
                        <i class="bi bi-house-fill me-1"></i> Mon service
                    </button>
                    <button id="admBtnModeExterne" class="adm-mode-btn"
                            onclick="admChoisirMode('externe')">
                        <i class="bi bi-arrow-left-right me-1"></i> Autre service (hébergement)
                    </button>
                </div>

                <!-- Bandeau d'info dynamique -->
                <div id="admInfoBanner" class="alert border-0 rounded-3 py-2 mb-3"
                     style="background:#fff7ed;color:#9a3412;font-size:.83rem;">
                    <i class="bi bi-house-fill me-1"></i>Le patient sera installé sur un lit de <strong>son propre service</strong>.
                </div>

                <!-- Sélecteur de service externe (masqué par défaut) -->
                <div id="admServiceBlock" class="mb-3 d-none">
                    <label class="form-label fw-bold small text-uppercase"
                           style="letter-spacing:.5px;color:#64748b;">
                        <i class="bi bi-grid-3x3-gap-fill me-1"></i>Service d'hébergement
                    </label>
                    <select id="admServiceExterne" class="form-select border-2 rounded-3"
                            onchange="admChoisirServiceExterne(this.value)">
                        <option value="">— Choisir un service —</option>
                        <?php foreach(($servicesCliniques ?? []) as $sc): ?>
                        <?php if ((int)$sc['id'] !== (int)($_SESSION['service_id'] ?? 0)): ?>
                        <option value="<?= (int)$sc['id'] ?>"><?= htmlspecialchars($sc['nom_service']) ?></option>
                        <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Liste des lits -->
                <div id="admListLits" style="max-height:320px;overflow-y:auto;">
                    <!-- chargement dynamique -->
                </div>

                <div id="admMessageBox" class="mt-3"></div>
            </div>

            <!-- Footer -->
            <div class="modal-footer border-0 px-4 pb-4 pt-0">
                <button type="button" class="btn btn-light rounded-pill px-4"
                        data-bs-dismiss="modal">Annuler</button>
                <button type="button" id="admBtnConfirmer"
                        class="btn rounded-pill px-4 fw-semibold text-white"
                        style="background:linear-gradient(135deg,#ea580c,#f97316);border:none;"
                        onclick="confirmerAdmettreLit()" disabled>
                    <i class="bi bi-check-lg me-1"></i> Confirmer l'admission
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════════════
     MODAL — LIBÉRER LE LIT (SORTIE PATIENT)
═══════════════════════════════════════════════════════════════ -->
<div class="modal fade" id="modalLibererLit" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width:460px">
        <div class="modal-content border-0 shadow-lg" style="border-radius:16px;overflow:hidden">

            <!-- En-tête rouge -->
            <div class="modal-header border-0 p-4"
                 style="background:linear-gradient(135deg,#be123c,#e11d48)">
                <div>
                    <h5 class="modal-title fw-bold text-white mb-0">
                        <i class="bi bi-box-arrow-right me-2"></i>Libérer le lit
                    </h5>
                    <div class="text-white opacity-75 small mt-1" id="libNomPatient">—</div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body p-4">

                <!-- Lit actuel -->
                <div class="d-flex align-items-center gap-3 bg-light rounded-3 p-3 mb-4">
                    <div style="width:42px;height:42px;border-radius:10px;background:#ffe4e6;
                                display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <i class="bi bi-hospital text-danger fs-5"></i>
                    </div>
                    <div>
                        <div class="text-muted" style="font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px;">
                            Lit occupé actuellement
                        </div>
                        <div class="fw-bold" id="libNomLit">—</div>
                    </div>
                </div>

                <!-- Motif de sortie -->
                <div class="mb-3">
                    <label class="form-label fw-semibold small text-muted text-uppercase mb-2"
                           style="letter-spacing:.4px;">
                        Motif de sortie
                    </label>
                    <div class="d-grid gap-2">
                        <?php
                        $motifsSortie = [
                            ['val' => 'Sortie normale',                  'icon' => 'bi-check-circle',       'color' => '#10b981'],
                            ['val' => 'Sortie contre avis médical',      'icon' => 'bi-exclamation-triangle','color' => '#f59e0b'],
                            ['val' => 'Transfert vers autre établissement','icon'=> 'bi-arrow-up-right-circle','color'=> '#3b82f6'],
                            ['val' => 'Évasion de l\'hôpital',           'icon' => 'bi-door-open',          'color' => '#ef4444'],
                            ['val' => 'Décès',                           'icon' => 'bi-heartbreak',         'color' => '#6b7280'],
                        ];
                        foreach ($motifsSortie as $i => $m): ?>
                        <label class="d-flex align-items-center gap-3 p-3 border rounded-3"
                               style="cursor:pointer;transition:.15s;"
                               onmouseover="this.style.background='#f8fafc'"
                               onmouseout="this.style.background=''">
                            <input class="form-check-input mt-0 flex-shrink-0" type="radio"
                                   name="motifSortieLib" value="<?= htmlspecialchars($m['val']) ?>"
                                   <?= $i === 0 ? 'checked' : '' ?>>
                            <i class="bi <?= $m['icon'] ?>" style="color:<?= $m['color'] ?>;font-size:1.1rem;"></i>
                            <span class="fw-semibold" style="font-size:.88rem;"><?= htmlspecialchars($m['val']) ?></span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Avertissement -->
                <div class="alert alert-warning border-0 rounded-3 py-2 px-3 small d-flex gap-2 align-items-start mb-4">
                    <i class="bi bi-exclamation-triangle-fill text-warning flex-shrink-0 mt-1"></i>
                    <span>Cette action <strong>clôture l'hospitalisation</strong> et rend le lit disponible. Elle est irréversible.</span>
                </div>

                <!-- Boutons -->
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-light rounded-pill px-4 fw-semibold"
                            data-bs-dismiss="modal">Annuler</button>
                    <button type="button" id="btnConfirmerLiberer"
                            class="btn rounded-pill px-4 flex-grow-1 fw-bold text-white"
                            style="background:#e11d48;"
                            onclick="confirmerLibererLit()">
                        <i class="bi bi-box-arrow-right me-1"></i> Confirmer la sortie
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════════════
     MODAL — CHANGER DE LIT
═══════════════════════════════════════════════════════════════ -->
<div class="modal fade" id="modalChangerLit" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width:440px">
        <div class="modal-content border-0 shadow-lg" style="border-radius:16px;overflow:hidden">

            <!-- En-tête -->
            <div class="modal-header border-0 px-4 pt-4 pb-3"
                 style="background:linear-gradient(135deg,#4f46e5,#7c3aed)">
                <div>
                    <h5 class="modal-title fw-bold text-white mb-0">
                        <i class="bi bi-arrow-left-right me-2"></i>Changer de lit
                    </h5>
                    <div class="text-white opacity-75" style="font-size:.78rem;margin-top:3px">
                        Déplacement intra-service
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body px-4 py-3">

                <!-- Info patient + lit actuel -->
                <div class="rounded-3 px-3 py-2 mb-3 d-flex align-items-center gap-3"
                     style="background:#f5f3ff;border:1px solid #ddd6fe">
                    <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
                         style="width:38px;height:38px;background:#6d28d9">
                        <i class="bi bi-person-fill text-white"></i>
                    </div>
                    <div>
                        <div class="fw-bold" style="font-size:.88rem;color:#3730a3" id="clitPatientNom">—</div>
                        <div style="font-size:.75rem;color:#7c3aed">
                            Lit actuel :&nbsp;<strong id="clitLitActuel">—</strong>
                        </div>
                    </div>
                </div>

                <!-- Sélecteur nouveau lit -->
                <div class="mb-3">
                    <label class="form-label fw-semibold small text-muted mb-1">
                        <i class="bi bi-hospital me-1"></i>Nouveau lit
                        <span class="text-danger">*</span>
                    </label>
                    <select id="clitNouveauLit" class="form-select border-2" style="border-radius:10px">
                        <option value="">— Sélectionner un lit disponible —</option>
                        <?php foreach(($lits_service ?? []) as $l):
                            if (strtoupper($l['statut'] ?? '') !== 'DISPONIBLE') continue; ?>
                        <option value="<?= (int)$l['id'] ?>">
                            <?= htmlspecialchars(($l['nom_chambre'] ?? '') . ' — ' . ($l['nom_lit'] ?? '')) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <?php $nbDispo = count(array_filter($lits_service ?? [], fn($l) => strtoupper($l['statut'] ?? '') === 'DISPONIBLE')); ?>
                    <div class="form-text" style="font-size:.72rem">
                        <?= $nbDispo ?> lit<?= $nbDispo > 1 ? 's' : '' ?> disponible<?= $nbDispo > 1 ? 's' : '' ?> dans votre service
                    </div>
                </div>

                <!-- Message d'erreur -->
                <div id="clitErreur" class="d-none d-flex align-items-center gap-2 rounded-3 px-3 py-2 mb-2"
                     style="background:#fff5f5;border:1px solid #fca5a5">
                    <i class="bi bi-exclamation-triangle-fill text-danger flex-shrink-0"></i>
                    <span id="clitErreurMsg" class="text-danger fw-semibold" style="font-size:.82rem"></span>
                </div>

            </div>

            <!-- Pied de page -->
            <div class="modal-footer border-0 px-4 pb-4 pt-0 gap-2">
                <button type="button" class="btn btn-light rounded-pill px-4 flex-grow-1"
                        data-bs-dismiss="modal">Annuler</button>
                <button type="button" id="clitBtnConfirm"
                        class="btn fw-bold rounded-pill px-4 flex-grow-1"
                        style="background:linear-gradient(135deg,#4f46e5,#7c3aed);color:#fff"
                        onclick="confirmerChangerLit()">
                    <i class="bi bi-check2 me-1"></i>Confirmer
                </button>
            </div>

        </div>
    </div>
</div>

<!-- ══════════════════════════════════════════════════════════════════
     MODAL — CRÉER PATIENT + HOSPITALISER DIRECTEMENT
     (Pour patients hospitalisés avant le lancement du logiciel)
══════════════════════════════════════════════════════════════════ -->
<div class="modal fade" id="modalCreerHosp" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius:18px;overflow:hidden;max-height:90vh;display:flex;flex-direction:column">

            <!-- En-tête -->
            <div class="modal-header border-0 p-4"
                 style="background:linear-gradient(135deg,#065f46,#059669)">
                <div>
                    <h5 class="modal-title fw-bold text-white mb-0">
                        <i class="bi bi-person-plus-fill me-2"></i>Créer un patient &amp; l'hospitaliser
                    </h5>
                    <div class="text-white opacity-75 small mt-1">
                        Pour les patients hospitalisés <strong>avant le lancement du logiciel</strong> — crée le dossier et l'installe directement sur un lit
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <form id="formCreerHosp" novalidate
                  style="flex:1 1 auto;min-height:0;overflow:hidden;display:flex;flex-direction:column">
            <div class="modal-body p-0" style="overflow-y:auto;flex:1 1 auto">

                <!-- Alerte contexte -->
                <div class="px-4 pt-3 pb-2">
                    <div class="alert alert-warning border-0 rounded-3 py-2 px-3 mb-0 d-flex align-items-center gap-2" style="font-size:.82rem">
                        <i class="bi bi-info-circle-fill text-warning fs-5 flex-shrink-0"></i>
                        <div>
                            <strong>Attention :</strong> Si ce patient est <u>déjà enregistré</u> dans le système,
                            utilisez plutôt la liste des patients existants pour éviter les doublons.
                        </div>
                    </div>
                </div>

                <!-- ── SECTION A : ÉTAT CIVIL ───────────────────────────── -->
                <div class="px-4 pt-3 pb-3" style="border-bottom:1px solid #e2e8f0">
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <div class="rounded-circle d-flex align-items-center justify-content-center"
                             style="width:28px;height:28px;background:#065f46;flex-shrink:0">
                            <span class="text-white fw-bold" style="font-size:.7rem">A</span>
                        </div>
                        <span class="fw-bold text-uppercase" style="font-size:.72rem;letter-spacing:.6px;color:#065f46">
                            État civil du patient
                        </span>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small text-muted mb-1">
                                Nom <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="nom" id="chNom"
                                   class="form-control border-2 text-uppercase" style="border-radius:10px"
                                   placeholder="DUPONT" autocomplete="off">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small text-muted mb-1">
                                Prénom <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="prenom" id="chPrenom"
                                   class="form-control border-2" style="border-radius:10px"
                                   placeholder="Jean" autocomplete="off">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small text-muted mb-1">
                                Sexe <span class="text-danger">*</span>
                            </label>
                            <select name="sexe" class="form-select border-2" style="border-radius:10px">
                                <option value="">— Choisir —</option>
                                <option value="M">Masculin</option>
                                <option value="F">Féminin</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small text-muted mb-1 d-flex align-items-center justify-content-between">
                                <span>Date de naissance</span>
                                <!-- Toggle Date / Âge -->
                                <span class="btn-group btn-group-sm" role="group" style="font-size:.7rem;">
                                    <button type="button" id="btnModeDob" class="btn btn-success btn-sm active px-2 py-0"
                                            onclick="setDobMode('date')" style="font-size:.68rem;border-radius:6px 0 0 6px;">
                                        📅 Date
                                    </button>
                                    <button type="button" id="btnModeAge" class="btn btn-outline-success btn-sm px-2 py-0"
                                            onclick="setDobMode('age')" style="font-size:.68rem;border-radius:0 6px 6px 0;">
                                        🔢 Âge
                                    </button>
                                </span>
                            </label>

                            <!-- Mode Date -->
                            <div id="dobModeDate">
                                <input type="date" name="date_naissance" id="chDateNaissance"
                                       class="form-control border-2" style="border-radius:10px"
                                       max="<?= date('Y-m-d') ?>">
                            </div>

                            <!-- Mode Âge (en années) -->
                            <div id="dobModeAge" class="d-none">
                                <div class="input-group">
                                    <input type="number" id="chAge" min="0" max="130"
                                           class="form-control border-2" style="border-radius:10px 0 0 10px"
                                           placeholder="ex : 45"
                                           oninput="convertAgeToDate(this.value)">
                                    <span class="input-group-text border-2"
                                          style="background:#f8fafc;border-radius:0 10px 10px 0;font-size:.8rem;color:#64748b;">
                                        ans
                                    </span>
                                </div>
                                <div id="dobAgePreview" class="mt-1 text-muted" style="font-size:.75rem;min-height:1.2em;"></div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small text-muted mb-1">
                                Téléphone
                                <span class="text-muted fw-normal">(optionnel)</span>
                            </label>
                            <input type="tel" name="telephone"
                                   class="form-control border-2" style="border-radius:10px"
                                   placeholder="6X XX XX XX XX">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small text-muted mb-1">
                                Groupe sanguin
                                <span class="text-muted fw-normal">(optionnel)</span>
                            </label>
                            <select name="groupe_sanguin" class="form-select border-2" style="border-radius:10px">
                                <option value="">— Non renseigné —</option>
                                <option value="A+">A+</option><option value="A-">A-</option>
                                <option value="B+">B+</option><option value="B-">B-</option>
                                <option value="AB+">AB+</option><option value="AB-">AB-</option>
                                <option value="O+">O+</option><option value="O-">O-</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small text-muted mb-1">
                                Type de prise en charge
                            </label>
                            <select name="type_client" class="form-select border-2" style="border-radius:10px">
                                <option value="PAYANT_COMPTANT" selected>Payant comptant</option>
                                <option value="ASSURANCE">Assurance</option>
                                <option value="BON_PRISE_EN_CHARGE">Bon de prise en charge</option>
                                <option value="FAMILLE_PHP">Famille PHP</option>
                                <option value="AGENTS_PHP">Agent PHP</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- ── SECTION B : SERVICE + LIT ───────────────────────── -->
                <div class="px-4 pt-3 pb-3" style="border-bottom:1px solid #e2e8f0">
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <div class="rounded-circle d-flex align-items-center justify-content-center"
                             style="width:28px;height:28px;background:#065f46;flex-shrink:0">
                            <span class="text-white fw-bold" style="font-size:.7rem">B</span>
                        </div>
                        <span class="fw-bold text-uppercase" style="font-size:.72rem;letter-spacing:.6px;color:#065f46">
                            Affectation service &amp; lit
                        </span>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small text-muted mb-1">
                                <i class="bi bi-building me-1"></i>Service <span class="text-danger">*</span>
                            </label>
                            <select id="chServiceId" name="service_id"
                                    class="form-select border-2" style="border-radius:10px"
                                    onchange="chChargerLits(this.value)">
                                <option value="">— Choisir un service —</option>
                                <?php foreach(($servicesCliniques ?? []) as $s): ?>
                                <option value="<?= (int)$s['id'] ?>"
                                    <?= (isset($_SESSION['service_id']) && (int)$s['id'] === (int)$_SESSION['service_id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($s['nom_service']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small text-muted mb-1">
                                <i class="bi bi-hospital me-1"></i>Lit
                                <span class="text-muted fw-normal">(optionnel)</span>
                            </label>
                            <select id="chLitId" name="lit_id"
                                    class="form-select border-2" style="border-radius:10px" disabled>
                                <option value="">— Choisir un service d'abord —</option>
                            </select>
                            <div id="chLitsLoader" class="form-text d-none">
                                <span class="spinner-border spinner-border-sm me-1 text-success"></span>
                                Chargement des lits disponibles…
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ── SECTION C : MÉDECIN + DATES + MOTIF ────────────── -->
                <div class="px-4 pt-3 pb-4">
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <div class="rounded-circle d-flex align-items-center justify-content-center"
                             style="width:28px;height:28px;background:#065f46;flex-shrink:0">
                            <span class="text-white fw-bold" style="font-size:.7rem">C</span>
                        </div>
                        <span class="fw-bold text-uppercase" style="font-size:.72rem;letter-spacing:.6px;color:#065f46">
                            Informations cliniques &amp; dates
                        </span>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small text-muted mb-1">
                                <i class="bi bi-person-badge me-1"></i>Médecin responsable
                            </label>
                            <select name="medecin_id" class="form-select border-2" style="border-radius:10px">
                                <option value="">— Sélectionner —</option>
                                <?php foreach(($medecins ?? []) as $m): ?>
                                <option value="<?= (int)$m['id'] ?>">
                                    Dr <?= htmlspecialchars($m['nom'] . ' ' . $m['prenom']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small text-muted mb-1">
                                <i class="bi bi-calendar-check me-1"></i>Date d'admission
                                <span class="text-danger">*</span>
                                <span class="badge bg-warning text-dark ms-1" style="font-size:.6rem">antidatable</span>
                            </label>
                            <input type="datetime-local" id="chDateAdmission" name="date_admission"
                                   class="form-control border-2" style="border-radius:10px"
                                   value="<?= date('Y-m-d\TH:i') ?>"
                                   max="<?= date('Y-m-d\TH:i') ?>">
                            <div class="form-text text-muted" style="font-size:.7rem">
                                <i class="bi bi-info-circle me-1"></i>Saisir la vraie date si le patient est déjà hospitalisé
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small text-muted mb-1">
                                <i class="bi bi-calendar-x me-1"></i>Sortie prévue
                                <span class="text-muted fw-normal">(optionnel)</span>
                            </label>
                            <input type="date" name="date_sortie_prevue"
                                   class="form-control border-2" style="border-radius:10px">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small text-muted mb-1">
                                <i class="bi bi-clipboard2-pulse me-1"></i>Motif d'hospitalisation
                                <span class="text-danger">*</span>
                            </label>
                            <textarea name="motif" class="form-control border-2" rows="3"
                                      style="border-radius:10px;resize:vertical"
                                      placeholder="Décrivez le motif principal d'admission…"></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small text-muted mb-1">
                                <i class="bi bi-file-medical me-1"></i>Diagnostic d'entrée
                                <span class="text-muted fw-normal">(optionnel)</span>
                            </label>
                            <textarea name="diagnostic" class="form-control border-2" rows="3"
                                      style="border-radius:10px;resize:vertical"
                                      placeholder="Diagnostic provisoire à l'entrée…"></textarea>
                        </div>
                    </div>
                </div>

            </div><!-- /.modal-body -->

            <!-- Pied de page -->
            <div class="modal-footer border-0 px-4 pb-4 pt-0 d-flex justify-content-between align-items-center"
                 style="background:#f8fafc">
                <div id="chErreur" class="text-danger small fw-semibold d-none">
                    <i class="bi bi-exclamation-triangle-fill me-1"></i>
                    <span id="chErreurMsg"></span>
                </div>
                <div class="ms-auto d-flex gap-2">
                    <button type="button" class="btn btn-light rounded-pill px-4"
                            data-bs-dismiss="modal">Annuler</button>
                    <button type="button" id="chBtnSubmit"
                            class="btn btn-success btn-lg rounded-pill fw-bold shadow px-5"
                            onclick="sauvegarderCreerHosp()">
                        <i class="bi bi-person-plus-fill me-1"></i>CRÉER &amp; HOSPITALISER
                    </button>
                </div>
            </div>
            </form>

        </div>
    </div>
</div>

<script>
// ── Utilitaires (modal créer & hospitaliser) ──────────────────
function escHtmlInf(s) { return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }

const BURL_INF = '<?= BASE_URL ?>';

// ── Charger les lits disponibles (AJAX) ──────────────────────
function chChargerLits(serviceId) {
    const select = document.getElementById('chLitId');
    const loader = document.getElementById('chLitsLoader');
    if (!serviceId) {
        select.innerHTML = '<option value="">— Choisir un service d\'abord —</option>';
        select.disabled  = true;
        return;
    }
    loader.classList.remove('d-none');
    select.disabled  = true;
    select.innerHTML = '';
    fetch(BURL_INF + 'hospitalisation/lits-disponibles?service_id=' + serviceId)
        .then(r => r.json())
        .then(lits => {
            loader.classList.add('d-none');
            select.innerHTML = '<option value="">— Aucun lit (non assigné) —</option>';
            if (!Array.isArray(lits) || lits.length === 0) {
                select.innerHTML += '<option value="" disabled>⚠ Aucun lit disponible dans ce service</option>';
            } else {
                lits.forEach(l => {
                    const label = l.nom_chambre ? `${l.nom_lit}  (${l.nom_chambre})` : l.nom_lit;
                    select.innerHTML += `<option value="${l.id}">${escHtmlInf(label)}</option>`;
                });
            }
            select.disabled = false;
        })
        .catch(() => {
            loader.classList.add('d-none');
            select.innerHTML = '<option value="">Erreur de chargement</option>';
        });
}

// ── Soumettre le formulaire (AJAX) ────────────────────────────
function sauvegarderCreerHosp() {
    const form   = document.getElementById('formCreerHosp');
    const errDiv = document.getElementById('chErreur');
    const errMsg = document.getElementById('chErreurMsg');
    const btn    = document.getElementById('chBtnSubmit');

    const showErr = msg => {
        errMsg.textContent = msg;
        errDiv.classList.remove('d-none');
        errDiv.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    };

    const nom       = form.querySelector('[name="nom"]').value.trim();
    const prenom    = form.querySelector('[name="prenom"]').value.trim();
    const sexe      = form.querySelector('[name="sexe"]').value;
    const serviceId = document.getElementById('chServiceId').value;
    const motif     = form.querySelector('[name="motif"]').value.trim();

    if (!nom)       { showErr('Le nom du patient est requis.');           return; }
    if (!prenom)    { showErr('Le prénom du patient est requis.');        return; }
    if (!sexe)      { showErr('Le sexe du patient est requis.');          return; }
    if (!serviceId) { showErr('Veuillez sélectionner un service.');       return; }
    if (!motif)     { showErr("Le motif d'hospitalisation est requis."); return; }

    errDiv.classList.add('d-none');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Enregistrement…';

    fetch(BURL_INF + 'hospitalisation/creer-et-admettre', {
        method: 'POST',
        body: new FormData(form)
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            bootstrap.Modal.getInstance(document.getElementById('modalCreerHosp')).hide();
            const toast = document.createElement('div');
            toast.className = 'position-fixed bottom-0 end-0 p-3';
            toast.style.zIndex = 9999;
            toast.innerHTML = `
                <div class="toast show align-items-center text-white bg-success border-0 rounded-3 shadow" style="min-width:320px">
                    <div class="d-flex">
                        <div class="toast-body fw-semibold">
                            <i class="bi bi-check-circle-fill me-2"></i>${escHtmlInf(data.message)}
                        </div>
                        <button type="button" class="btn-close btn-close-white me-2 m-auto"
                                onclick="this.closest('.toast').remove()"></button>
                    </div>
                </div>`;
            document.body.appendChild(toast);
            setTimeout(() => location.reload(), 1500);
        } else {
            showErr(data.message || 'Erreur lors de l\'enregistrement.');
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-person-plus-fill me-1"></i>CRÉER &amp; HOSPITALISER';
        }
    })
    .catch(() => {
        showErr('Erreur réseau. Veuillez réessayer.');
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-person-plus-fill me-1"></i>CRÉER &amp; HOSPITALISER';
    });
}

// ── Bascule mode Date / Âge ───────────────────────────────────
function setDobMode(mode) {
    const modeDate = document.getElementById('dobModeDate');
    const modeAge  = document.getElementById('dobModeAge');
    const btnDate  = document.getElementById('btnModeDob');
    const btnAge   = document.getElementById('btnModeAge');

    if (mode === 'age') {
        modeDate.classList.add('d-none');
        modeAge.classList.remove('d-none');
        btnDate.classList.replace('btn-success',         'btn-outline-success');
        btnAge .classList.replace('btn-outline-success', 'btn-success');
        // Vider la date native pour ne pas bloquer la validation
        const inp = document.getElementById('chDateNaissance');
        if (inp) inp.removeAttribute('name');
        document.getElementById('chAge').focus();
    } else {
        modeDate.classList.remove('d-none');
        modeAge.classList.add('d-none');
        btnDate.classList.replace('btn-outline-success', 'btn-success');
        btnAge .classList.replace('btn-success',         'btn-outline-success');
        const inp = document.getElementById('chDateNaissance');
        if (inp) inp.setAttribute('name', 'date_naissance');
        document.getElementById('dobAgePreview').textContent = '';
    }
}

// Conversion âge → date de naissance estimée (1er janvier de l'année)
function convertAgeToDate(ageVal) {
    const preview = document.getElementById('dobAgePreview');
    const age = parseInt(ageVal, 10);

    if (isNaN(age) || age < 0 || age > 130) {
        preview.textContent = '';
        return;
    }

    const anneeNaissance = new Date().getFullYear() - age;
    const dobEstimee = anneeNaissance + '-01-01'; // 1er janvier par défaut

    // Injecter dans un champ caché géré dynamiquement
    let hidden = document.getElementById('chDateNaissanceFromAge');
    if (!hidden) {
        hidden = document.createElement('input');
        hidden.type = 'hidden';
        hidden.id   = 'chDateNaissanceFromAge';
        hidden.name = 'date_naissance';
        document.getElementById('formCreerHosp').appendChild(hidden);
    }
    hidden.value = dobEstimee;

    preview.innerHTML = `<i class="bi bi-calendar-check me-1 text-success"></i>
        Date estimée : <strong>${String(anneeNaissance).padStart(4,'0')}-01-01</strong>
        <span class="text-muted">(1ᵉʳ janv. ${anneeNaissance})</span>`;
}

// ── Réinitialiser à l'ouverture du modal ─────────────────────
document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById('modalCreerHosp');
    if (!modal) return;
    modal.addEventListener('show.bs.modal', function () {
        document.getElementById('formCreerHosp').reset();
        document.getElementById('chDateAdmission').value = new Date().toISOString().slice(0, 16);
        const litSel = document.getElementById('chLitId');
        litSel.innerHTML = '<option value="">— Choisir un service d\'abord —</option>';
        litSel.disabled  = true;
        document.getElementById('chErreur').classList.add('d-none');
        const btn = document.getElementById('chBtnSubmit');
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-person-plus-fill me-1"></i>CRÉER &amp; HOSPITALISER';

        // Réinitialiser le mode Date / Âge → retour en mode Date
        setDobMode('date');
        document.getElementById('chDateNaissance').value = '';
        document.getElementById('chAge').value = '';
        document.getElementById('dobAgePreview').textContent = '';
        const hiddenAge = document.getElementById('chDateNaissanceFromAge');
        if (hiddenAge) hiddenAge.remove();

        // Pré-charger les lits si le service de l'infirmier est déjà sélectionné
        const srvSel = document.getElementById('chServiceId');
        if (srvSel && srvSel.value) chChargerLits(srvSel.value);
    });
});
</script>

<!-- ═══════════════════════════════════════════════════════════════
     MODAL — CHERCHER PATIENT POUR CONSULTATION INFIRMIÈRE
════════════════════════════════════════════════════════════════ -->
<div class="modal fade" id="modalChercherPatientConsult" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered" style="max-width:520px;">
        <div class="modal-content border-0 shadow-lg" style="border-radius:16px;overflow:hidden;">
            <div class="modal-header border-0 pb-0"
                 style="background:linear-gradient(135deg,#0d9488,#0f766e);border-radius:16px 16px 0 0;">
                <div>
                    <h5 class="modal-title text-white fw-bold mb-0">
                        <i class="bi bi-clipboard2-pulse-fill me-2"></i>Consultation Infirmière
                    </h5>
                    <p class="text-white opacity-75 small mb-0">Recherchez le patient à consulter</p>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-3">
                <div class="position-relative mb-3">
                    <i class="bi bi-search position-absolute"
                       style="left:14px;top:50%;transform:translateY(-50%);color:#94a3b8;pointer-events:none;"></i>
                    <input type="text" id="rechercheConsultInf" class="form-control ps-4"
                           placeholder="Nom, prénom ou n° dossier…" autocomplete="off"
                           style="border-radius:10px;">
                </div>
                <div id="resultatsConsultInf" style="max-height:340px;overflow-y:auto;">
                    <div class="text-center py-4 text-muted small">
                        <i class="bi bi-person-lines-fill d-block" style="font-size:2rem;opacity:.3;"></i>
                        Saisissez au moins 2 caractères
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    let _timer = null;

    document.getElementById('modalChercherPatientConsult').addEventListener('shown.bs.modal', function() {
        document.getElementById('rechercheConsultInf').focus();
        document.getElementById('rechercheConsultInf').value = '';
        document.getElementById('resultatsConsultInf').innerHTML =
            '<div class="text-center py-4 text-muted small"><i class="bi bi-person-lines-fill d-block" style="font-size:2rem;opacity:.3;"></i>Saisissez au moins 2 caractères</div>';
    });

    document.getElementById('rechercheConsultInf').addEventListener('input', function() {
        clearTimeout(_timer);
        const q = this.value.trim();
        const zone = document.getElementById('resultatsConsultInf');

        if (q.length < 2) {
            zone.innerHTML = '<div class="text-center py-4 text-muted small"><i class="bi bi-person-lines-fill d-block" style="font-size:2rem;opacity:.3;"></i>Saisissez au moins 2 caractères</div>';
            return;
        }
        zone.innerHTML = '<div class="text-center py-3 text-muted small"><span class="spinner-border spinner-border-sm me-2"></span>Recherche…</div>';

        _timer = setTimeout(() => {
            fetch('<?= BASE_URL ?>patients/recherche?q=' + encodeURIComponent(q), {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(r => r.json())
            .then(data => {
                if (!data.success || !data.patients.length) {
                    zone.innerHTML = '<div class="text-center py-4 text-muted small"><i class="bi bi-person-x d-block" style="font-size:2rem;opacity:.3;"></i>Aucun patient trouvé</div>';
                    return;
                }
                let html = '';
                data.patients.forEach(p => {
                    const ini = (p.nom.charAt(0) + (p.prenom?.charAt(0)||'')).toUpperCase();
                    html += `<a href="<?= BASE_URL ?>infirmier/consultation/${p.id}"
                                style="display:flex;align-items:center;gap:12px;padding:10px 12px;border-radius:10px;
                                       text-decoration:none;color:#1e293b;transition:background .15s;"
                                onmouseover="this.style.background='#f0fdfa'" onmouseout="this.style.background=''">
                        <div style="width:38px;height:38px;border-radius:10px;
                                    background:linear-gradient(135deg,#0d9488,#14b8a6);
                                    display:flex;align-items:center;justify-content:center;
                                    color:#fff;font-size:.75rem;font-weight:800;flex-shrink:0;">${ini}</div>
                        <div style="flex:1;min-width:0;">
                            <div style="font-weight:700;font-size:.88rem;">${p.nom} ${p.prenom||''}</div>
                            <div style="font-size:.72rem;color:#94a3b8;">${p.dossier_numero} · ${p.date_naissance||'?'}</div>
                        </div>
                        <i class="bi bi-arrow-right-circle-fill" style="color:#0d9488;font-size:1.2rem;"></i>
                    </a>`;
                });
                zone.innerHTML = html;
            })
            .catch(() => {
                zone.innerHTML = '<p class="text-danger text-center small p-3">Erreur réseau.</p>';
            });
        }, 280);
    });
})();
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
