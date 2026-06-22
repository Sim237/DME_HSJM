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
$dossier  = $dossier  ?? [];
$dernieres = $dernieres_constantes ?? [];

// Helpers d'alerte vitaux
$temp      = (float)($dernieres['temperature'] ?? 0);
$spo2      = (float)($dernieres['saturation_oxygene'] ?? 0);
$fc        = (int)($dernieres['frequence_cardiaque'] ?? 0);
$sys       = (int)($dernieres['pression_arterielle_systolique'] ?? 0);
$dia       = (int)($dernieres['pression_arterielle_diastolique'] ?? 0);
$fr        = (int)($dernieres['frequence_respiratoire'] ?? 0);
$glycemie  = (float)($dernieres['glycemie'] ?? 0);
$diurese   = (int)($dernieres['diurese'] ?? 0);
$sousO2    = (int)($dernieres['sous_oxygene'] ?? 0);
$debitO2   = (float)($dernieres['debit_oxygene'] ?? 0);

function vitalAlert(string $type, $val): string {
    if (!$val) return '';
    return match($type) {
        'temp'     => $val >= 38.5 ? 'critical' : ($val >= 38 ? 'warning' : ''),
        'spo2'     => $val < 90 ? 'critical' : ($val < 94 ? 'warning' : ''),
        'fc'       => $val > 120 || $val < 40 ? 'critical' : ($val > 100 ? 'warning' : ''),
        'sys'      => $val > 180 || $val < 80 ? 'critical' : ($val > 140 ? 'warning' : ''),
        'fr'       => $val > 30 || $val < 8  ? 'critical' : ($val > 20 ? 'warning' : ''),
        'glycemie' => $val < 0.60 || $val > 2.00 ? 'critical' : ($val < 0.70 || $val > 1.40 ? 'warning' : ''),
        'diurese'  => $val < 100 ? 'critical' : ($val < 400 ? 'warning' : ''),
        default    => ''
    };
}
?>

<script src="<?= BASE_URL ?>public/js/chart.umd.js"></script>

<style>
/* ══ BASE ══ */
body { background: #f1f5f9; }

/* ══ HEADER PATIENT ══ */
.suivi-header {
    background: linear-gradient(135deg, #0f172a 0%, #1e3a5f 55%, #1e40af 100%);
    padding: 22px 32px;
    display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 14px;
}
.suivi-header .patient-name { font-size: 1.25rem; font-weight: 800; color: #fff; margin: 0; }
.suivi-header .patient-sub  { font-size: .8rem; color: rgba(255,255,255,.55); margin-top: 3px; }
.suivi-header .header-badges { display: flex; gap: 8px; flex-wrap: wrap; align-items: center; }
.h-badge {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 6px 14px; border-radius: 20px; font-size: .75rem; font-weight: 700;
    backdrop-filter: blur(4px);
}
.h-badge.service { background: rgba(34,197,94,.2); color: #86efac; border: 1px solid rgba(34,197,94,.35); }
.h-badge.lit     { background: rgba(148,163,184,.15); color: #e2e8f0; border: 1px solid rgba(148,163,184,.3); }

/* ══ ACTION BAR ══ */
.action-bar {
    background: #fff;
    border-bottom: 1px solid #e2e8f0;
    padding: 12px 28px;
    display: flex; align-items: center; gap: 8px; flex-wrap: wrap;
    position: sticky; top: 0; z-index: 900;
    box-shadow: 0 2px 12px rgba(0,0,0,.05);
}
.ab-sep { width: 1px; height: 28px; background: #e2e8f0; flex-shrink: 0; }
.ab-btn {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 8px 16px; border-radius: 20px; font-size: .8rem; font-weight: 700;
    border: none; cursor: pointer; text-decoration: none; transition: all .18s;
    white-space: nowrap;
}
.ab-btn.ghost   { background: #f1f5f9; color: #475569; }
.ab-btn.ghost:hover { background: #e2e8f0; color: #1e293b; }
.ab-btn.primary { background: #4f46e5; color: #fff; box-shadow: 0 2px 8px rgba(79,70,229,.3); }
.ab-btn.primary:hover { background: #4338ca; color: #fff; }
.ab-btn.teal    { background: #0e7490; color: #fff; box-shadow: 0 2px 8px rgba(14,116,144,.25); }
.ab-btn.teal:hover { background: #0c647f; color: #fff; }
.ab-btn.dark    { background: #1e293b; color: #fff; }
.ab-btn.dark:hover { background: #0f172a; color: #fff; }
.ab-btn.red     { background: #ef4444; color: #fff; box-shadow: 0 2px 8px rgba(239,68,68,.3); }
.ab-btn.red:hover { background: #dc2626; color: #fff; }
.ab-btn.amber   { background: #f59e0b; color: #fff; box-shadow: 0 2px 8px rgba(245,158,11,.3); }
.ab-btn.amber:hover { background: #d97706; color: #fff; }
.ab-btn.blue    { background: #1d4ed8; color: #fff; box-shadow: 0 2px 8px rgba(29,78,216,.3); }
.ab-btn.blue:hover  { background: #1e40af; color: #fff; }

/* ══ MODAL PANSEMENT ══════════════════════════════════════════ */
.pan-section-title {
    font-size:.72rem; font-weight:800; text-transform:uppercase; letter-spacing:.7px;
    color:#7c3aed; margin-bottom:10px; display:flex; align-items:center; gap:6px;
}
.pan-check-grid {
    display:grid; grid-template-columns:repeat(auto-fill,minmax(160px,1fr)); gap:6px;
}
.pan-check-item {
    display:flex; align-items:center; gap:7px;
    padding:7px 11px; border-radius:9px; border:1.5px solid #e2e8f0;
    background:#f8fafc; font-size:.82rem; color:#334155;
    cursor:pointer; transition:all .12s;
}
.pan-check-item:hover { border-color:#c4b5fd; background:#f5f3ff; }
.pan-check-item input { accent-color:#7c3aed; width:14px; height:14px; flex-shrink:0; }
.pan-check-item.checked { background:#f5f3ff; border-color:#7c3aed; color:#4c1d95; font-weight:700; }
/* Couleurs évolution plaie */
.evo-necrose    { border-color:#374151!important; background:#1f2937!important; color:#f9fafb!important; }
.evo-fibrine    { border-color:#d97706!important; background:#fef3c7!important; color:#78350f!important; }
.evo-bourgeon   { border-color:#dc2626!important; background:#fee2e2!important; color:#7f1d1d!important; }
.evo-epidem     { border-color:#db2777!important; background:#fce7f3!important; color:#831843!important; }
.evo-infection  { border-color:#16a34a!important; background:#dcfce7!important; color:#14532d!important; }
.evo-necrose.checked    { background:#374151!important; color:#fff!important; }
.evo-fibrine.checked    { background:#f59e0b!important; color:#fff!important; }
.evo-bourgeon.checked   { background:#ef4444!important; color:#fff!important; }
.evo-epidem.checked     { background:#ec4899!important; color:#fff!important; }
.evo-infection.checked  { background:#22c55e!important; color:#fff!important; }

.pan-radio-group { display:flex; flex-wrap:wrap; gap:6px; }
.pan-radio-item {
    display:flex; align-items:center; gap:6px;
    padding:6px 12px; border-radius:20px; border:1.5px solid #e2e8f0;
    background:#f8fafc; font-size:.8rem; cursor:pointer; transition:all .12s;
}
.pan-radio-item input { accent-color:#7c3aed; flex-shrink:0; }
.pan-radio-item:hover { border-color:#c4b5fd; }

.pan-input {
    width:100%; padding:8px 11px; border:1.5px solid #e2e8f0; border-radius:9px;
    font-size:.83rem; color:#1e293b; background:#f8fafc; outline:none;
    transition:border-color .15s;
}
.pan-input:focus { border-color:#7c3aed; background:#fff; }

/* Tableau historique */
.pan-hist-table { width:100%; border-collapse:collapse; font-size:.77rem; }
.pan-hist-table th {
    background:#f5f3ff; color:#4c1d95; font-weight:800; font-size:.68rem;
    text-transform:uppercase; letter-spacing:.5px; padding:8px 10px;
    border-bottom:2px solid #c4b5fd; white-space:nowrap;
}
.pan-hist-table td { padding:8px 10px; border-bottom:1px solid #f1f5f9; vertical-align:middle; }
.pan-hist-table tr:hover td { background:#f5f3ff; }
.evo-tag {
    display:inline-block; padding:2px 7px; border-radius:20px;
    font-size:.67rem; font-weight:700; margin:1px;
}
.tag-necrose  { background:#374151; color:#fff; }
.tag-fibrine  { background:#f59e0b; color:#fff; }
.tag-bourgeon { background:#ef4444; color:#fff; }
.tag-epidem   { background:#ec4899; color:#fff; }
.tag-infection{ background:#22c55e; color:#fff; }
.exp-badge { display:inline-block; padding:2px 8px; border-radius:20px;
    font-size:.7rem; font-weight:700; background:#e0e7ff; color:#3730a3; }

/* ══ VITAUX ══ */
.vitals-row { display: grid; grid-template-columns: repeat(auto-fill, minmax(210px,1fr)); gap: 16px; }
.vital-card {
    background: #fff; border-radius: 18px; padding: 20px 22px;
    box-shadow: 0 2px 16px rgba(0,0,0,.06);
    display: flex; align-items: center; gap: 16px;
    position: relative; overflow: hidden; transition: transform .18s;
    cursor: pointer;
}
.vital-card:hover { transform: translateY(-3px); box-shadow: 0 8px 28px rgba(0,0,0,.1); }
.vital-card::before {
    content: ''; position: absolute; top: 0; left: 0; right: 0; height: 4px;
    border-radius: 18px 18px 0 0;
}
.vital-card.red::before    { background: linear-gradient(90deg,#f87171,#ef4444); }
.vital-card.blue::before   { background: linear-gradient(90deg,#60a5fa,#3b82f6); }
.vital-card.purple::before { background: linear-gradient(90deg,#c084fc,#a855f7); }
.vital-card.green::before  { background: linear-gradient(90deg,#4ade80,#22c55e); }
.vital-card.teal::before   { background: linear-gradient(90deg,#2dd4bf,#0d9488); }
.vital-card.amber::before  { background: linear-gradient(90deg,#fcd34d,#f59e0b); }
.vital-card.sky::before    { background: linear-gradient(90deg,#7dd3fc,#0ea5e9); }
.vital-card.indigo::before { background: linear-gradient(90deg,#818cf8,#4f46e5); }
.vital-card.add::before    { background: linear-gradient(90deg,#94a3b8,#64748b); }
.vital-icon {
    width: 52px; height: 52px; border-radius: 14px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.5rem; flex-shrink: 0;
}
.vital-card.red    .vital-icon { background: #fff5f5;    color: #ef4444; }
.vital-card.blue   .vital-icon { background: #eff6ff;    color: #3b82f6; }
.vital-card.purple .vital-icon { background: #faf5ff;    color: #a855f7; }
.vital-card.green  .vital-icon { background: #f0fdf4;    color: #22c55e; }
.vital-card.teal   .vital-icon { background: #f0fdfa;    color: #0d9488; }
.vital-card.amber  .vital-icon { background: #fffbeb;    color: #f59e0b; }
.vital-card.sky    .vital-icon { background: #f0f9ff;    color: #0ea5e9; }
.vital-card.indigo .vital-icon { background: #eef2ff;    color: #4f46e5; }
.vital-card.add    .vital-icon { background: #f8fafc;    color: #64748b; border: 2px dashed #cbd5e1; }
.vital-label { font-size: .7rem; font-weight: 700; text-transform: uppercase; color: #94a3b8; letter-spacing: .05em; }
.vital-value { font-size: 1.65rem; font-weight: 900; line-height: 1.1; color: #0f172a; }
.vital-unit  { font-size: .72rem; font-weight: 600; color: #94a3b8; }
.vital-card.warning { box-shadow: 0 0 0 2px #f59e0b, 0 4px 20px rgba(245,158,11,.15); }
.vital-card.critical { box-shadow: 0 0 0 2px #ef4444, 0 4px 20px rgba(239,68,68,.2);
    animation: pulse-red 2s infinite; }
@keyframes pulse-red {
    0%,100% { box-shadow: 0 0 0 2px #ef4444, 0 4px 20px rgba(239,68,68,.2); }
    50%      { box-shadow: 0 0 0 5px rgba(239,68,68,.4), 0 4px 20px rgba(239,68,68,.3); }
}

/* ══ CHARTS ══ */
.chart-card {
    background: #fff; border-radius: 18px; padding: 22px 24px;
    box-shadow: 0 2px 16px rgba(0,0,0,.06);
}
.chart-card-title {
    font-size: .8rem; font-weight: 800; text-transform: uppercase; letter-spacing: .06em;
    display: flex; align-items: center; gap: 8px; margin-bottom: 18px;
}
.chart-card-title .dot { width: 10px; height: 10px; border-radius: 50%; flex-shrink: 0; }

/* ══ SOINS TIMELINE ══ */
.soins-card {
    background: #fff; border-radius: 18px; padding: 24px;
    box-shadow: 0 2px 16px rgba(0,0,0,.06);
}
.soins-header {
    display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px;
}
.soins-title { font-size: .85rem; font-weight: 800; text-transform: uppercase; color: #1e293b; letter-spacing: .06em; }
.soin-item {
    display: flex; align-items: center; gap: 16px;
    padding: 14px 16px; border-radius: 14px; margin-bottom: 8px;
    border: 1.5px solid #f1f5f9;
    transition: all .15s;
}
.soin-item:hover { border-color: #e2e8f0; background: #fafbfd; }
.soin-item.done  { background: #f0fdf4; border-color: #bbf7d0; }
.soin-item.todo  { background: #fffbeb; border-color: #fde68a; }
.soin-time {
    min-width: 52px; text-align: center; flex-shrink: 0;
}
.soin-time .st-h { font-size: .9rem; font-weight: 800; color: #1e293b; }
.soin-time .st-d { font-size: .67rem; color: #94a3b8; }
.soin-type-badge {
    padding: 4px 11px; border-radius: 20px; font-size: .68rem; font-weight: 800;
    white-space: nowrap; flex-shrink: 0;
}
.soin-desc { flex-grow: 1; font-size: .83rem; color: #334155; font-weight: 500; }
.soin-status { flex-shrink: 0; text-align: right; }
.status-done { display: inline-flex; align-items: center; gap: 4px; background: #f0fdf4; color: #15803d; border: 1px solid #86efac; border-radius: 20px; padding: 4px 10px; font-size: .7rem; font-weight: 800; }
.status-todo { display: inline-flex; align-items: center; gap: 4px; background: #fffbeb; color: #92400e; border: 1px solid #fcd34d; border-radius: 20px; padding: 4px 10px; font-size: .7rem; font-weight: 800; }
.soin-valider-btn {
    margin-top: 6px; display: inline-flex; align-items: center; gap: 4px;
    background: #16a34a; color: #fff; border: none; border-radius: 20px;
    padding: 4px 12px; font-size: .72rem; font-weight: 700; cursor: pointer; transition: .15s;
}
.soin-valider-btn:hover { background: #15803d; }
.empty-soins { text-align: center; padding: 40px 20px; color: #94a3b8; }
.empty-soins i { font-size: 2.5rem; display: block; margin-bottom: 10px; }
/* ── Accordéon planification par date ── */
.soin-date-group { margin-bottom: 8px; }
.soin-planif-toggle {
    width: 100%; display: flex; align-items: center; gap: 12px;
    padding: 11px 16px; border-radius: 12px; border: 1.5px solid #e2e8f0;
    background: #f8fafc; cursor: pointer; transition: all .18s;
    text-align: left; outline: none;
}
.soin-planif-toggle:hover { background: #f1f5f9; border-color: #cbd5e1; }
.soin-planif-toggle.is-today {
    background: linear-gradient(135deg,#ede9fe,#f5f3ff);
    border-color: #c4b5fd;
}
.soin-planif-toggle.is-today:hover { background: linear-gradient(135deg,#ddd6fe,#ede9fe); }
.spl-icon {
    width: 32px; height: 32px; border-radius: 9px; flex-shrink: 0;
    display: flex; align-items: center; justify-content: center;
    background: #e2e8f0; font-size: .9rem;
}
.soin-planif-toggle.is-today .spl-icon { background: #ddd6fe; }
.spl-label {
    flex-grow: 1; font-size: .82rem; font-weight: 700; color: #1e293b;
}
.soin-planif-toggle.is-today .spl-label { color: #5b21b6; }
.spl-pills { display: flex; align-items: center; gap: 6px; flex-shrink: 0; }
.spl-pill {
    font-size: .67rem; font-weight: 700; padding: 3px 9px;
    border-radius: 20px; white-space: nowrap;
}
.spl-pill.todo-pill  { background: #fffbeb; color: #92400e; border: 1px solid #fde68a; }
.spl-pill.done-pill  { background: #f0fdf4; color: #15803d; border: 1px solid #86efac; }
.spl-pill.today-pill { background: #ede9fe; color: #7c3aed; border: 1px solid #c4b5fd; }
.spl-chevron {
    font-size: .75rem; color: #94a3b8; transition: transform .22s; flex-shrink: 0;
}
.soin-planif-toggle[aria-expanded="true"] .spl-chevron { transform: rotate(180deg); }
/* Contenu déroulé */
.soin-planif-body { padding-top: 6px; }
/* ── Bouton supprimer ── */
.soin-suppr-btn {
    background: none; border: none; color: #fca5a5;
    font-size: .88rem; padding: 5px 7px; border-radius: 8px;
    cursor: pointer; transition: .15s; flex-shrink: 0; line-height: 1;
}
.soin-suppr-btn:hover { background: #fee2e2; color: #dc2626; }

/* ══ SI SECTION ══ */
.si-section { background: #fff; border-radius: 18px; overflow: hidden; box-shadow: 0 2px 16px rgba(0,0,0,.06); }
.si-header { background: linear-gradient(135deg,#7f1d1d,#dc2626); color: #fff; padding: 16px 24px; display: flex; align-items: center; justify-content: space-between; }

/* ══ PAGE LAYOUT ══ */
.suivi-body { max-width: 1400px; margin: 0 auto; padding: 26px 28px; }
@media(max-width:768px){ .suivi-body { padding: 16px; } }

/* ══ FORMULAIRES HISTORY SECTION ══ */
.forms-history-section {
    background: #fff;
    border-radius: 18px;
    overflow: hidden;
    box-shadow: 0 2px 16px rgba(0,0,0,.06);
}
.forms-history-header {
    background: linear-gradient(135deg, #1e3a5f, #0d9488);
    color: #fff;
    padding: 16px 24px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    user-select: none;
}
.badge-fh-count {
    display: inline-flex; align-items: center; justify-content: center;
    background: rgba(255,255,255,.25); color: #fff;
    border-radius: 20px; font-size: .72rem; font-weight: 800;
    padding: 1px 9px; margin-left: 8px; vertical-align: middle;
}
.fh-chevron { color: rgba(255,255,255,.8); }

/* Filtre bar */
.fh-filter-bar {
    display: flex; gap: 8px; flex-wrap: wrap;
    padding: 14px 20px 10px;
    border-bottom: 1px solid #f1f5f9;
}
.fh-filter {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 5px 14px; border-radius: 20px; border: 1.5px solid #e2e8f0;
    background: white; color: #64748b; font-size: .78rem; font-weight: 600;
    cursor: pointer; transition: .15s;
}
.fh-filter span {
    background: #f1f5f9; color: #475569; border-radius: 20px;
    padding: 0 6px; font-size: .72rem; font-weight: 700;
}
.fh-filter.active, .fh-filter:hover {
    background: #0d9488; border-color: #0d9488; color: #fff;
}
.fh-filter.active span, .fh-filter:hover span {
    background: rgba(255,255,255,.25); color: #fff;
}

/* Liste */
.fh-list { padding: 8px 12px 12px; display: flex; flex-direction: column; gap: 6px; }
.fh-item {
    display: flex; align-items: center; gap: 12px;
    padding: 11px 14px; border-radius: 12px;
    border: 1.5px solid #f1f5f9; background: #fafbfc;
    transition: .15s;
}
.fh-item:hover { background: #f0fdfa; border-color: #0d9488; }

/* Icône du formulaire */
.fh-form-icon {
    width: 38px; height: 38px; border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1rem; flex-shrink: 0;
}

/* Infos formulaire */
.fh-form-info { flex: 1; min-width: 0; }
.fh-form-title { font-weight: 700; font-size: .88rem; color: #1e293b; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.fh-form-meta  { font-size: .73rem; color: #94a3b8; margin-top: 2px; }

/* Badges de statut */
.fh-badge {
    flex-shrink: 0; display: inline-flex; align-items: center;
    padding: 3px 10px; border-radius: 20px; font-size: .72rem; font-weight: 700;
}
.fh-badge-signed  { background: #dcfce7; color: #16a34a; }
.fh-badge-sent    { background: #fef9c3; color: #b45309; }
.fh-badge-draft   { background: #f1f5f9; color: #64748b; }

/* Bouton Voir */
.fh-btn-voir {
    flex-shrink: 0; display: inline-flex; align-items: center; gap: 5px;
    padding: 7px 16px; border-radius: 8px;
    background: #0d9488; color: #fff;
    font-size: .78rem; font-weight: 700; text-decoration: none;
    transition: .15s;
}
.fh-btn-voir:hover { background: #0f766e; color: #fff; }

/* État vide */
.fh-empty {
    text-align: center;
    padding: 40px 20px;
    color: #94a3b8;
}

/* ══ RÉÉVALUATIONS MÉDICALES ══ */
.rev-group {
    border-radius: 12px;
    overflow: hidden;
    border: 1.5px solid #e2e8f0;
    margin-bottom: 10px;
}
.rev-group-header {
    width: 100%; display: flex; align-items: center; justify-content: space-between;
    padding: 12px 16px; border: none; cursor: pointer; text-align: left;
    transition: filter .15s;
}
.rev-group-header:hover { filter: brightness(.97); }
.rev-group-body {
    overflow: hidden;
    transition: max-height .35s ease, padding .3s ease;
    background: #fff;
}
.rev-label {
    font-size: .7rem; font-weight: 800; color: #64748b;
    text-transform: uppercase; letter-spacing: .6px; margin-bottom: 6px;
    display: flex; align-items: center; gap: 4px;
}
.rev-content {
    border-radius: 0 8px 8px 0;
    padding: 10px 14px;
    font-size: .82rem;
    color: #334155;
    line-height: 1.65;
}
.rev-med-item {
    background: #faf5ff; border: 1px solid #ddd6fe; border-radius: 8px;
    padding: 9px 13px; display: flex; align-items: flex-start; gap: 10px; margin-bottom: 6px;
}
.rev-bilan-item {
    border-radius: 7px; padding: 7px 12px;
    display: flex; align-items: center; gap: 9px; margin-bottom: 5px;
}
</style>

<!-- ══ HEADER ══ -->
<div class="suivi-header">
    <div>
        <p class="patient-name">
            <i class="bi bi-heart-pulse-fill me-2" style="color:#60a5fa"></i>
            Suivi Hospitalisation
        </p>
        <p class="patient-sub">
            <span class="fw-bold" style="color:#e2e8f0"><?= htmlspecialchars(strtoupper($dossier['nom'] ?? '').' '.($dossier['prenom'] ?? '')) ?></span>
            &nbsp;|&nbsp; Dossier <?= htmlspecialchars($dossier['dossier_numero'] ?? '') ?>
        </p>
    </div>
    <div class="header-badges">
        <span class="h-badge service"><i class="bi bi-building-fill"></i> <?= htmlspecialchars($dossier['service_nom'] ?? 'Service non défini') ?></span>
        <?php
        $serviceHebergNom = $dossier['service_hebergement_nom'] ?? null;
        $chambreHeberg    = $dossier['chambre_hebergement'] ?? null;
        $litNumero        = $dossier['lit_numero'] ?? '--';
        $isExterne        = !empty($serviceHebergNom);
        ?>
        <?php if ($isExterne): ?>
            <span class="h-badge" style="background:rgba(251,191,36,.2);color:#fbbf24;border:1px solid rgba(251,191,36,.4);">
                <i class="bi bi-geo-alt-fill"></i>
                Hébergé : <?= htmlspecialchars($serviceHebergNom) ?>
                <?php if ($chambreHeberg): ?> · Ch. <?= htmlspecialchars($chambreHeberg) ?><?php endif; ?>
                · LIT <?= htmlspecialchars($litNumero) ?>
            </span>
        <?php else: ?>
            <span class="h-badge lit"><i class="bi bi-bed-fill"></i>
                <?php if ($chambreHeberg): ?>Ch. <?= htmlspecialchars($chambreHeberg) ?> · <?php endif; ?>
                LIT <?= htmlspecialchars($litNumero) ?>
            </span>
        <?php endif; ?>
    </div>
</div>

<!-- ══ ACTION BAR ══ -->
<div class="action-bar">
    <a href="<?= BASE_URL ?>dashboard" class="ab-btn ghost">
        <i class="bi bi-house-door"></i> Tableau de bord
    </a>
    <div class="ab-sep"></div>
    <button class="ab-btn primary" data-bs-toggle="modal" data-bs-target="#modalAddConstante">
        <i class="bi bi-plus-circle-fill"></i> Ajouter Constantes
    </button>
    <?php if (in_array($_SESSION['user_role'] ?? '', ['INFIRMIER','MAJOR_INFIRMIER','MAJOR','ADMIN'])): ?>
    <button class="ab-btn" style="background:#0891b2;color:#fff;box-shadow:0 2px 8px rgba(8,145,178,.3);"
            onclick="ouvrirModalHebergement()">
        <i class="bi bi-arrow-left-right"></i> Changer de Lit
    </button>
    <?php endif; ?>
    <button class="ab-btn" style="background:#7c3aed;color:#fff;box-shadow:0 2px 8px rgba(124,58,237,.3);"
            data-bs-toggle="modal" data-bs-target="#modalFichePansement">
        <i class="bi bi-bandaid-fill"></i> Fiche Pansement
    </button>
    <a href="<?= BASE_URL ?>hospitalisation/reevaluation/<?= $patient['id'] ?>" class="ab-btn blue">
        <i class="bi bi-clipboard2-pulse-fill"></i> Réévaluation
    </a>
    <div class="ab-sep"></div>
    <a href="<?= BASE_URL ?>hospitalisation/surveillance-intensive/<?= $patient['id'] ?>" class="ab-btn red">
        <i class="bi bi-clipboard2-pulse-fill"></i> Fiche S.I.
    </a>
    <a href="<?= BASE_URL ?>hospitalisation/fiche-transfusionnelle/<?= $patient['id'] ?>" class="ab-btn amber">
        <i class="bi bi-droplet-half"></i> Fiche Transfusionnelle
    </a>
    <button type="button" class="ab-btn" style="background:#0d9488;color:#fff;"
            data-bs-toggle="modal" data-bs-target="#modalMesFormulaires">
        <i class="bi bi-file-earmark-plus"></i> Mes Formulaires
    </button>
</div>

<!-- ══ MODAL MES FORMULAIRES ══ -->
<?php
require_once __DIR__ . '/../../controllers/FormulaireController.php';
$catalogueFormulaires = FormulaireController::catalogue();
$hospIdFormulaires    = $dossier['id'] ?? 0;
?>
<div class="modal fade" id="modalMesFormulaires" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" style="max-width:580px;">
        <div class="modal-content border-0 shadow-lg" style="border-radius:18px;overflow:hidden;">
            <!-- En-tête -->
            <div class="modal-header border-0 p-4"
                 style="background:linear-gradient(135deg,#0f172a,#0d9488);">
                <div>
                    <h5 class="modal-title fw-bold text-white mb-0">
                        <i class="bi bi-file-earmark-plus me-2"></i>Mes Formulaires
                    </h5>
                    <small style="color:rgba(255,255,255,.65);">
                        Sélectionnez un formulaire à remplir pour
                        <strong class="text-white"><?= htmlspecialchars(($patient['nom'] ?? '').' '.($patient['prenom'] ?? '')) ?></strong>
                    </small>
                </div>
                <button type="button" class="btn-close btn-close-white ms-3" data-bs-dismiss="modal"></button>
            </div>

            <!-- Corps -->
            <div class="modal-body p-3">
                <div class="row g-2">
                    <?php foreach ($catalogueFormulaires as $form): ?>
                    <div class="col-6">
                        <a href="<?= BASE_URL ?>formulaire/creer/<?= $form['slug'] ?>/<?= $patient['id'] ?>?hosp_id=<?= $hospIdFormulaires ?>"
                           target="_blank"
                           class="d-flex align-items-center gap-3 p-3 rounded-3 text-decoration-none"
                           style="background:#f8fafc;border:1.5px solid #e2e8f0;transition:.15s;"
                           onmouseover="this.style.background='#f0fdfa';this.style.borderColor='#0d9488'"
                           onmouseout="this.style.background='#f8fafc';this.style.borderColor='#e2e8f0'">
                            <div class="rounded-2 d-flex align-items-center justify-content-center flex-shrink-0"
                                 style="width:36px;height:36px;background:<?= $form['color'] ?>18;">
                                <i class="bi <?= $form['icon'] ?>" style="color:<?= $form['color'] ?>;font-size:1rem;"></i>
                            </div>
                            <span class="fw-semibold text-dark" style="font-size:.8rem;line-height:1.3;">
                                <?= htmlspecialchars($form['titre']) ?>
                            </span>
                        </a>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Info workflow -->
                <div class="alert border-0 rounded-3 mt-3 mb-0 py-2 px-3 small d-flex gap-2"
                     style="background:#f0fdfa;border-left:3px solid #0d9488 !important;border-left-style:solid !important;">
                    <i class="bi bi-info-circle-fill flex-shrink-0 mt-1" style="color:#0d9488"></i>
                    <span class="text-muted">Après avoir rempli le formulaire, cliquez sur
                        <strong style="color:#0d9488">« Soumettre au médecin »</strong>
                        pour le transmettre au médecin du service pour signature.
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ══ TOAST CONTAINER ══ -->
<div id="suivi-toast-area" style="position:fixed;top:80px;right:22px;z-index:9999;display:flex;flex-direction:column;gap:10px;min-width:280px"></div>

<?php if (!empty($_GET['success']) && str_starts_with($_GET['success'], 'plan_valide')): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const area = document.getElementById('suivi-toast-area');
    if (!area) return;
    const t = document.createElement('div');
    t.style.cssText = 'background:#0d9488;color:#fff;padding:13px 18px;border-radius:14px;box-shadow:0 6px 20px rgba(13,148,136,.3);display:flex;align-items:center;gap:10px;font-weight:700;font-size:.85rem;animation:slideIn .3s ease;';
    t.innerHTML = '<i class="bi bi-check-circle-fill fs-5"></i> Planification enregistrée avec succès !';
    area.appendChild(t);
    setTimeout(function(){ t.style.opacity='0'; t.style.transition='opacity .5s'; setTimeout(function(){ t.remove(); }, 500); }, 4000);
});
</script>
<?php endif; ?>

<!-- ══ CORPS DE PAGE ══ -->
<div class="suivi-body">

    <!-- ── VITAUX ── -->
    <div class="vitals-row mb-4" id="vitals-row">

        <!-- Température -->
        <?php $alertTemp = vitalAlert('temp', $temp); ?>
        <div class="vital-card red <?= $alertTemp ?>" data-bs-toggle="modal" data-bs-target="#modalAddConstante" title="Cliquer pour ajouter">
            <div class="vital-icon"><i class="bi bi-thermometer-half"></i></div>
            <div>
                <div class="vital-label">Température</div>
                <div class="vital-value">
                    <?= $temp > 0 ? number_format($temp, 1) : '--' ?>
                    <span class="vital-unit">°C</span>
                </div>
                <?php if($alertTemp === 'critical'): ?>
                <div style="font-size:.65rem;color:#ef4444;font-weight:700;margin-top:2px"><i class="bi bi-exclamation-triangle-fill"></i> Critique</div>
                <?php elseif($alertTemp === 'warning'): ?>
                <div style="font-size:.65rem;color:#f59e0b;font-weight:700;margin-top:2px"><i class="bi bi-exclamation-circle-fill"></i> Élevée</div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Tension -->
        <?php $alertTA = vitalAlert('sys', $sys); ?>
        <div class="vital-card blue <?= $alertTA ?>" data-bs-toggle="modal" data-bs-target="#modalAddConstante" title="Cliquer pour ajouter">
            <div class="vital-icon"><i class="bi bi-activity"></i></div>
            <div>
                <div class="vital-label">Tension Artérielle</div>
                <div class="vital-value" style="font-size:1.35rem">
                    <?= ($sys > 0)
                        ? $sys.'/'.($dernieres['pression_arterielle_diastolique'] ?? '--')
                        : '--/--' ?>
                    <span class="vital-unit">mmHg</span>
                </div>
                <?php if($alertTA === 'critical'): ?>
                <div style="font-size:.65rem;color:#ef4444;font-weight:700;margin-top:2px"><i class="bi bi-exclamation-triangle-fill"></i> Critique</div>
                <?php elseif($alertTA === 'warning'): ?>
                <div style="font-size:.65rem;color:#f59e0b;font-weight:700;margin-top:2px"><i class="bi bi-exclamation-circle-fill"></i> Élevée</div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Fréquence Cardiaque -->
        <?php $alertFC = vitalAlert('fc', $fc); ?>
        <div class="vital-card purple <?= $alertFC ?>" data-bs-toggle="modal" data-bs-target="#modalAddConstante" title="Cliquer pour ajouter">
            <div class="vital-icon"><i class="bi bi-heart-fill"></i></div>
            <div>
                <div class="vital-label">Fréquence Cardiaque</div>
                <div class="vital-value">
                    <?= $fc > 0 ? $fc : '--' ?>
                    <span class="vital-unit">bpm</span>
                </div>
                <?php if($alertFC === 'critical'): ?>
                <div style="font-size:.65rem;color:#ef4444;font-weight:700;margin-top:2px"><i class="bi bi-exclamation-triangle-fill"></i> Critique</div>
                <?php elseif($alertFC === 'warning'): ?>
                <div style="font-size:.65rem;color:#f59e0b;font-weight:700;margin-top:2px"><i class="bi bi-exclamation-circle-fill"></i> Tachycardie</div>
                <?php endif; ?>
            </div>
        </div>

        <!-- SpO2 -->
        <?php $alertSpo2 = vitalAlert('spo2', $spo2); ?>
        <div class="vital-card green <?= $alertSpo2 ?>" data-bs-toggle="modal" data-bs-target="#modalAddConstante" title="Cliquer pour ajouter">
            <div class="vital-icon"><i class="bi bi-lungs-fill"></i></div>
            <div>
                <div class="vital-label">Saturation O₂</div>
                <div class="vital-value">
                    <?= $spo2 > 0 ? $spo2 : '--' ?>
                    <span class="vital-unit">%</span>
                </div>
                <?php if($alertSpo2 === 'critical'): ?>
                <div style="font-size:.65rem;color:#ef4444;font-weight:700;margin-top:2px"><i class="bi bi-exclamation-triangle-fill"></i> Hypoxie</div>
                <?php elseif($alertSpo2 === 'warning'): ?>
                <div style="font-size:.65rem;color:#f59e0b;font-weight:700;margin-top:2px"><i class="bi bi-exclamation-circle-fill"></i> Basse</div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Fréquence Respiratoire — visible si valeur présente -->
        <?php if ($fr > 0): $alertFR = vitalAlert('fr', $fr); ?>
        <div class="vital-card teal <?= $alertFR ?>" data-bs-toggle="modal" data-bs-target="#modalAddConstante" title="Cliquer pour ajouter">
            <div class="vital-icon"><i class="bi bi-wind"></i></div>
            <div>
                <div class="vital-label">Fréquence Resp.</div>
                <div class="vital-value"><?= $fr ?><span class="vital-unit">/min</span></div>
                <?php if($alertFR === 'critical'): ?>
                <div style="font-size:.65rem;color:#ef4444;font-weight:700;margin-top:2px"><i class="bi bi-exclamation-triangle-fill"></i> Critique</div>
                <?php elseif($alertFR === 'warning'): ?>
                <div style="font-size:.65rem;color:#f59e0b;font-weight:700;margin-top:2px"><i class="bi bi-exclamation-circle-fill"></i> Tachypnée</div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Glycémie capillaire — visible si valeur présente -->
        <?php if ($glycemie > 0): $alertGly = vitalAlert('glycemie', $glycemie); ?>
        <div class="vital-card amber <?= $alertGly ?>" data-bs-toggle="modal" data-bs-target="#modalAddConstante" title="Cliquer pour ajouter">
            <div class="vital-icon"><i class="bi bi-droplet-fill"></i></div>
            <div>
                <div class="vital-label">Glycémie Capillaire</div>
                <div class="vital-value"><?= number_format($glycemie, 2) ?><span class="vital-unit">g/L</span></div>
                <?php if($alertGly === 'critical'): ?>
                <div style="font-size:.65rem;color:#ef4444;font-weight:700;margin-top:2px"><i class="bi bi-exclamation-triangle-fill"></i>
                    <?= $glycemie < 0.60 ? 'Hypoglycémie' : 'Hyperglycémie' ?>
                </div>
                <?php elseif($alertGly === 'warning'): ?>
                <div style="font-size:.65rem;color:#f59e0b;font-weight:700;margin-top:2px"><i class="bi bi-exclamation-circle-fill"></i>
                    <?= $glycemie < 0.70 ? 'Limite basse' : 'Limite haute' ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Diurèse — visible si valeur présente -->
        <?php if ($diurese > 0): $alertDiu = vitalAlert('diurese', $diurese); ?>
        <div class="vital-card sky <?= $alertDiu ?>" data-bs-toggle="modal" data-bs-target="#modalAddConstante" title="Cliquer pour ajouter">
            <div class="vital-icon"><i class="bi bi-water"></i></div>
            <div>
                <div class="vital-label">Diurèse</div>
                <div class="vital-value" style="font-size:1.35rem"><?= number_format($diurese) ?><span class="vital-unit">mL/24h</span></div>
                <?php if($alertDiu === 'critical'): ?>
                <div style="font-size:.65rem;color:#ef4444;font-weight:700;margin-top:2px"><i class="bi bi-exclamation-triangle-fill"></i> Anurie</div>
                <?php elseif($alertDiu === 'warning'): ?>
                <div style="font-size:.65rem;color:#f59e0b;font-weight:700;margin-top:2px"><i class="bi bi-exclamation-circle-fill"></i> Oligurie</div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Oxygénothérapie — visible si patient sous O2 -->
        <?php if ($sousO2): ?>
        <div class="vital-card indigo" data-bs-toggle="modal" data-bs-target="#modalAddConstante" title="Cliquer pour ajouter">
            <div class="vital-icon"><i class="bi bi-mask"></i></div>
            <div>
                <div class="vital-label">Oxygénothérapie</div>
                <?php if($debitO2 > 0): ?>
                <div class="vital-value" style="font-size:1.35rem"><?= number_format($debitO2, 1) ?><span class="vital-unit">L/min</span></div>
                <?php else: ?>
                <div style="font-size:.82rem;font-weight:700;color:#4f46e5;margin-top:4px">Sous O₂</div>
                <?php endif; ?>
                <div style="font-size:.65rem;color:#6366f1;font-weight:700;margin-top:2px"><i class="bi bi-wind"></i> Oxygène actif</div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Nouvelle prise -->
        <div class="vital-card add" data-bs-toggle="modal" data-bs-target="#modalAddConstante" title="Enregistrer une prise">
            <div class="vital-icon"><i class="bi bi-plus-lg"></i></div>
            <div>
                <div class="vital-label">Nouvelle prise</div>
                <div style="font-size:.8rem;color:#64748b;margin-top:4px">Constantes &amp; paramètres</div>
            </div>
        </div>
    </div>

    <?php if($temp == 0 && $spo2 == 0 && $fc == 0 && $sys == 0 && $fr == 0 && $glycemie == 0 && $diurese == 0 && !$sousO2): ?>
    <!-- Bannière "aucune mesure" — disparaît dès qu'on ajoute des constantes -->
    <div id="no-vitals-banner" style="background:linear-gradient(135deg,#fffbeb,#fef3c7);border:1.5px dashed #f59e0b;border-radius:14px;padding:18px 24px;display:flex;align-items:center;gap:16px;margin-bottom:20px">
        <div style="width:44px;height:44px;background:#fef9c3;border-radius:12px;display:flex;align-items:center;justify-content:center;flex-shrink:0">
            <i class="bi bi-exclamation-triangle-fill" style="font-size:1.3rem;color:#d97706"></i>
        </div>
        <div style="flex:1">
            <div style="font-size:.85rem;font-weight:800;color:#92400e">Aucune constante enregistrée pour ce patient</div>
            <div style="font-size:.75rem;color:#78350f;margin-top:3px">Cliquez sur <strong>Ajouter Constantes</strong> pour saisir la première prise de paramètres vitaux.</div>
        </div>
        <button class="btn btn-sm fw-bold rounded-pill px-3" style="background:#f59e0b;color:#fff;border:none;flex-shrink:0"
                data-bs-toggle="modal" data-bs-target="#modalAddConstante">
            <i class="bi bi-plus-circle-fill me-1"></i> Saisir maintenant
        </button>
    </div>
    <?php endif; ?>

    <!-- ── GRAPHIQUES ── -->
    <div class="row g-4 mb-4">
        <div class="col-md-6">
            <div class="chart-card">
                <div class="chart-card-title" style="color:#ef4444">
                    <span class="dot" style="background:#ef4444"></span>
                    Évolution Température
                </div>
                <canvas id="chartTemp" height="170"></canvas>
            </div>
        </div>
        <div class="col-md-6">
            <div class="chart-card">
                <div class="chart-card-title" style="color:#3b82f6">
                    <span class="dot" style="background:#3b82f6"></span>
                    Évolution Tension Artérielle
                </div>
                <canvas id="chartTension" height="170"></canvas>
            </div>
        </div>
    </div>

    <!-- ── PLANNING DES SOINS ── -->
    <div class="soins-card mb-4">
        <div class="soins-header">
            <div class="soins-title"><i class="bi bi-journal-medical me-2" style="color:#4f46e5"></i>Planning des Soins</div>
            <div class="d-flex gap-2 flex-wrap align-items-center">
                <button type="button"
                        class="ab-btn"
                        style="background:#0d9488;color:#fff;font-size:.75rem;padding:7px 16px;"
                        data-bs-toggle="modal" data-bs-target="#modalUpdatePlanif">
                    <i class="bi bi-pencil-square"></i> Mettre à jour
                </button>
                <a href="<?= BASE_URL ?>hospitalisation/planifier-soins/<?= htmlspecialchars($dossier['patient_id'] ?? $patient['id']) ?>"
                   class="ab-btn primary" style="font-size:.75rem;padding:7px 16px">
                    <i class="bi bi-calendar-plus-fill"></i> Planifier un Soin
                </a>
                <button type="button" class="ab-btn"
                        style="font-size:.75rem;padding:7px 16px;background:linear-gradient(135deg,#7c3aed,#6d28d9);color:#fff;border:none"
                        data-bs-toggle="modal" data-bs-target="#modalAddSoin"
                        title="Planifier un soin conditionné par l'évolution d'un paramètre vital">
                    <i class="bi bi-graph-up-arrow me-1"></i> Soin conditionnel
                </button>
            </div>
        </div>

        <?php if(empty($tous_les_soins)): ?>
        <div class="empty-soins">
            <i class="bi bi-calendar2-check text-muted"></i>
            <p class="fw-bold">Aucun soin planifié</p>
            <p class="small text-muted">Utilisez le bouton "Planifier un Soin" pour ajouter des soins.</p>
        </div>
        <?php else:
        /* ── Regrouper par date ── */
        $soinsByDate = [];
        foreach ($tous_les_soins as $s) {
            $soinsByDate[date('Y-m-d', strtotime($s['date_prevue']))][] = $s;
        }
        $joursFr = ['Sunday'=>'Dimanche','Monday'=>'Lundi','Tuesday'=>'Mardi',
                    'Wednesday'=>'Mercredi','Thursday'=>'Jeudi','Friday'=>'Vendredi','Saturday'=>'Samedi'];
        $moisFr  = ['January'=>'Janvier','February'=>'Février','March'=>'Mars','April'=>'Avril',
                    'May'=>'Mai','June'=>'Juin','July'=>'Juillet','August'=>'Août',
                    'September'=>'Septembre','October'=>'Octobre','November'=>'Novembre','December'=>'Décembre'];
        $typeColors = ['IV'=>'#0e7490','IM'=>'#7c3aed','SC'=>'#0369a1','PO'=>'#15803d',
                       'PER_OS'=>'#15803d','SURVEILLANCE'=>'#475569','Pansement'=>'#be123c',
                       'Injection'=>'#7c3aed','Perfusion'=>'#0e7490','Prise de sang'=>'#dc2626'];
        $today    = date('Y-m-d');
        $tomorrow = date('Y-m-d', strtotime('+1 day'));
        $groupIdx = 0;
        ?>
        <?php foreach ($soinsByDate as $dateKey => $soinsJour):
            $dt         = new DateTime($dateKey);
            $isToday    = $dateKey === $today;
            $isTomorrow = $dateKey === $tomorrow;
            $collapseId = 'planifGroup-' . str_replace('-', '', $dateKey);
            $dateLabel  = date('d/m/Y', strtotime($dateKey));
            $doneCnt    = count(array_filter($soinsJour, fn($s) => $s['statut'] === 'REALISE'));
            $suppCnt    = count(array_filter($soinsJour, fn($s) => $s['statut'] === 'SUPPRIME'));
            $todoCnt    = count($soinsJour) - $doneCnt - $suppCnt;
            $totalCnt   = count($soinsJour);
            // Ouvert par défaut : aujourd'hui + premier groupe si aucun "aujourd'hui" n'existe
            $isOpen = $isToday || ($groupIdx === 0 && !array_key_exists($today, $soinsByDate));
            $groupIdx++;
        ?>
        <div class="soin-date-group">

            <!-- ── Ligne accordéon cliquable ── -->
            <button class="soin-planif-toggle <?= $isToday ? 'is-today' : '' ?>"
                    type="button"
                    data-bs-toggle="collapse"
                    data-bs-target="#<?= $collapseId ?>"
                    aria-expanded="<?= $isOpen ? 'true' : 'false' ?>"
                    aria-controls="<?= $collapseId ?>">

                <!-- Icône calendrier -->
                <div class="spl-icon">
                    <i class="bi bi-calendar3" style="color:<?= $isToday ? '#7c3aed' : '#64748b' ?>"></i>
                </div>

                <!-- Libellé date -->
                <span class="spl-label">
                    Planification du <?= $dateLabel ?>
                    <?php if ($isToday): ?>&nbsp;<span style="font-size:.67rem;font-weight:600;opacity:.7">(Aujourd'hui)</span><?php endif; ?>
                    <?php if ($isTomorrow): ?>&nbsp;<span style="font-size:.67rem;font-weight:600;opacity:.7">(Demain)</span><?php endif; ?>
                </span>

                <!-- Badges compteurs -->
                <div class="spl-pills">
                    <?php if ($isToday): ?>
                        <span class="spl-pill today-pill"><i class="bi bi-clock me-1"></i>Aujourd'hui</span>
                    <?php endif; ?>
                    <?php if ($todoCnt > 0): ?>
                        <span class="spl-pill todo-pill"><?= $todoCnt ?> à faire</span>
                    <?php endif; ?>
                    <?php if ($doneCnt > 0): ?>
                        <span class="spl-pill done-pill"><i class="bi bi-check2 me-1"></i><?= $doneCnt ?> fait<?= $doneCnt > 1 ? 's' : '' ?></span>
                    <?php endif; ?>
                </div>

                <!-- Chevron -->
                <i class="bi bi-chevron-down spl-chevron"></i>
            </button>

            <!-- ── Contenu déroulable ── -->
            <div class="collapse <?= $isOpen ? 'show' : '' ?>" id="<?= $collapseId ?>">
                <div class="soin-planif-body">
                    <?php foreach ($soinsJour as $soin):
                        $isDone      = $soin['statut'] === 'REALISE';
                        $isSupprime  = $soin['statut'] === 'SUPPRIME';
                        $itemClass   = $isDone ? 'done' : ($isSupprime ? 'supprime' : 'todo');
                        $tc          = $typeColors[$soin['type_soin']] ?? '#475569';
                        $descEsc     = addslashes(htmlspecialchars($soin['description'], ENT_QUOTES));
                    ?>
                    <div class="soin-item <?= $itemClass ?>"
                         id="soin-item-<?= $soin['id'] ?>"
                         <?= $isSupprime ? 'style="opacity:.48;background:#f8f9fa;border-color:#e2e8f0;pointer-events:none;"' : '' ?>>
                        <!-- Heure -->
                        <div class="soin-time">
                            <div class="st-h" <?= $isSupprime ? 'style="text-decoration:line-through;color:#9ca3af"' : '' ?>>
                                <?= date('H:i', strtotime($soin['date_prevue'])) ?>
                            </div>
                        </div>
                        <!-- Séparateur vertical coloré -->
                        <div style="width:3px;height:38px;border-radius:2px;background:<?= $isSupprime ? '#e5e7eb' : ($isDone ? '#86efac' : '#fcd34d') ?>;flex-shrink:0"></div>
                        <!-- Badge type -->
                        <span class="soin-type-badge" style="background:<?= $isSupprime ? '#f3f4f6' : $tc.'22' ?>;color:<?= $isSupprime ? '#9ca3af' : $tc ?>;border:1px solid <?= $isSupprime ? '#e5e7eb' : $tc.'44' ?>">
                            <?= htmlspecialchars($soin['type_soin']) ?>
                        </span>
                        <!-- Description -->
                        <div class="soin-desc" <?= $isSupprime ? 'style="text-decoration:line-through;color:#9ca3af"' : '' ?>>
                            <?= htmlspecialchars($soin['description']) ?>
                            <?php
                            /* ── Badge condition paramétrique ── */
                            $hasCondition = !empty($soin['parametre_surveille']);
                            $isStoppeSoin = in_array($soin['statut'] ?? '', ['STOPPE','REMPLACE']);
                            $condAtteinte = !empty($soin['condition_atteinte']);
                            $labelParamBadge = [
                                'temperature'=>'T°','pouls'=>'FC','tension_sys'=>'TAS',
                                'tension_dia'=>'TAD','saturation_o2'=>'SpO2',
                                'frequence_respiratoire'=>'FR','glycemie'=>'Gly',
                            ];
                            if ($hasCondition):
                                $opSymbole = ['<'=>'<','>'=>'>','<='=>'≤','>='=>'≥','='=>'='][$soin['operateur_condition']] ?? '?';
                                $freqLabel = $soin['frequence_surveillance_h'] ? 'mesure /' . $soin['frequence_surveillance_h'] . 'h' : '';
                                $lbl = $labelParamBadge[$soin['parametre_surveille']] ?? $soin['parametre_surveille'];
                            ?>
                            <div class="mt-1" style="display:flex;flex-wrap:wrap;gap:4px;align-items:center">
                                <span style="background:<?= $condAtteinte?'#dcfce7':'#f3e8ff' ?>;color:<?= $condAtteinte?'#15803d':'#7c3aed' ?>;
                                      border:1px solid <?= $condAtteinte?'#86efac':'#d8b4fe' ?>;border-radius:20px;
                                      padding:1px 8px;font-size:.68rem;font-weight:700;display:inline-flex;align-items:center;gap:4px">
                                    <i class="bi <?= $condAtteinte ? 'bi-check-circle-fill' : 'bi-graph-up-arrow' ?>"></i>
                                    <?= $lbl ?> <?= $opSymbole ?> <?= htmlspecialchars($soin['valeur_cible']) ?>
                                    <?php if ($condAtteinte): ?> ✓ Atteint<?php endif; ?>
                                </span>
                                <?php if ($freqLabel): ?>
                                <span style="background:#eff6ff;color:#1d4ed8;border:1px solid #bfdbfe;border-radius:20px;
                                      padding:1px 8px;font-size:.65rem;font-weight:600">
                                    <i class="bi bi-clock me-1"></i><?= htmlspecialchars($freqLabel) ?>
                                </span>
                                <?php endif; ?>
                                <?php if (!empty($soin['action_si_atteint'])): ?>
                                <span style="background:#fffbeb;color:#b45309;border:1px solid #fde68a;border-radius:20px;
                                      padding:1px 8px;font-size:.65rem;font-weight:600">
                                    <?= $soin['action_si_atteint'] === 'STOPPER' ? '🛑 Stop si atteint' : '🔄 Changer si atteint' ?>
                                </span>
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>
                            <?php if ($isSupprime || $isStoppeSoin): ?>
                            <div style="font-size:.72rem;color:<?= $isStoppeSoin?'#7c3aed':'#ef4444' ?>;margin-top:3px;font-style:italic;">
                                <?php if ($isStoppeSoin): ?>
                                <i class="bi bi-stop-circle-fill me-1"></i>
                                <?= $soin['statut'] === 'STOPPE' ? 'Stoppé' : 'Remplacé' ?> — condition atteinte
                                <?php else: ?>
                                <i class="bi bi-trash3-fill me-1"></i>
                                Supprimé par <?= htmlspecialchars(($soin['supprime_par_nom'] ?? '?').' '.($soin['supprime_par_prenom'] ?? '')) ?>
                                <?php if(!empty($soin['date_suppression'])): ?> le <?= date('d/m/Y à H:i', strtotime($soin['date_suppression'])) ?><?php endif; ?>
                                <?php if(!empty($soin['motif_suppression'])): ?> — <?= htmlspecialchars($soin['motif_suppression']) ?><?php endif; ?>
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                        <!-- Statut + action -->
                        <div class="soin-status">
                            <?php if ($isSupprime || $isStoppeSoin): ?>
                                <span style="font-size:.68rem;background:<?= $isStoppeSoin?'#7c3aed':'#dc3545' ?>;color:#fff;padding:2px 8px;border-radius:20px;font-weight:700;">
                                    <?= $soin['statut'] === 'STOPPE' ? 'STOPPÉ' : ($soin['statut'] === 'REMPLACE' ? 'REMPLACÉ' : 'SUPPRIMÉ') ?>
                                </span>
                            <?php elseif ($isDone): ?>
                                <div class="status-done"><i class="bi bi-check-circle-fill"></i> FAIT</div>
                                <div style="font-size:.63rem;color:#94a3b8;margin-top:4px;text-align:right">
                                    le <?= date('d/m H:i', strtotime($soin['date_realisee'])) ?>
                                </div>
                            <?php else: ?>
                                <div class="status-todo"><i class="bi bi-clock-fill"></i> À FAIRE</div>
                            <?php endif; ?>
                        </div>
                        <!-- Supprimer (masqué si déjà supprimé ou réalisé) -->
                        <?php if (!$isSupprime && !$isDone): ?>
                        <button class="soin-suppr-btn"
                                onclick="ouvrirModalSuppression(<?= $soin['id'] ?>, '<?= $descEsc ?>')"
                                title="Supprimer ce soin">
                            <i class="bi bi-trash3"></i>
                        </button>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

        </div><!-- /.soin-date-group -->
        <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- ── SURVEILLANCE INTENSIVE (si données) ── -->
    <?php if(!empty($si_data)): ?>
    <div class="si-section mb-4">
        <div class="si-header">
            <div class="d-flex align-items-center gap-3">
                <i class="bi bi-clipboard2-pulse-fill fs-4"></i>
                <div>
                    <div style="font-weight:800;font-size:.95rem">Surveillance Intensive</div>
                    <div style="font-size:.72rem;opacity:.7"><?= count($si_data) ?> observation(s) enregistrée(s)</div>
                </div>
            </div>
            <a href="<?= BASE_URL ?>hospitalisation/surveillance-intensive/<?= $patient['id'] ?>"
               class="ab-btn" style="background:rgba(255,255,255,.15);color:#fff;font-size:.75rem;padding:7px 14px;border:1px solid rgba(255,255,255,.25)">
                <i class="bi bi-plus-circle-fill"></i> Nouvelle observation
            </a>
        </div>
        <div style="padding:24px">
            <!-- Graphiques SI -->
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <div class="chart-card" style="padding:16px">
                        <div class="chart-card-title" style="color:#ef4444;font-size:.7rem">
                            <span class="dot" style="background:#ef4444"></span> Température (°C)
                        </div>
                        <canvas id="chartSITemp" height="160"></canvas>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="chart-card" style="padding:16px">
                        <div class="chart-card-title" style="color:#6366f1;font-size:.7rem">
                            <span class="dot" style="background:#6366f1"></span> Pouls (bpm)
                        </div>
                        <canvas id="chartSIPouls" height="160"></canvas>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="chart-card" style="padding:16px">
                        <div class="chart-card-title" style="color:#0ea5e9;font-size:.7rem">
                            <span class="dot" style="background:#0ea5e9"></span> Tension Artérielle
                        </div>
                        <canvas id="chartSITA" height="160"></canvas>
                    </div>
                </div>
            </div>
            <!-- Tableau SI -->
            <div class="table-responsive">
                <table class="table table-sm align-middle" style="font-size:.8rem">
                    <thead>
                        <tr style="background:#f8fafc;border-bottom:2px solid #e2e8f0">
                            <th class="fw-bold" style="color:#64748b">Date / Heure</th>
                            <th class="fw-bold" style="color:#64748b">TA</th>
                            <th class="fw-bold" style="color:#64748b">Pouls</th>
                            <th class="fw-bold" style="color:#64748b">T°</th>
                            <th class="fw-bold" style="color:#64748b">Resp.</th>
                            <th class="fw-bold" style="color:#64748b">Diurèse</th>
                            <th class="fw-bold" style="color:#64748b">Conscience</th>
                            <th class="fw-bold" style="color:#64748b">Observations</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($si_data as $obs): ?>
                        <tr style="border-bottom:1px solid #f1f5f9">
                            <td>
                                <div class="fw-bold"><?= htmlspecialchars($obs['date_obs'] ?? '') ?></div>
                                <div style="color:#94a3b8;font-size:.7rem"><?= htmlspecialchars($obs['heure_obs'] ?? '') ?></div>
                            </td>
                            <td><span class="badge bg-primary-subtle text-primary border"><?= htmlspecialchars($obs['ta'] ?? '--') ?></span></td>
                            <td>
                                <?php $p = $obs['pouls'] ?? null; ?>
                                <span class="fw-bold <?= ($p && ($p > 110 || $p < 50)) ? 'text-danger' : ($p && $p > 95 ? 'text-warning' : '') ?>">
                                    <?= $p ?? '--' ?>
                                </span>
                            </td>
                            <td>
                                <?php $t = $obs['temperature'] ?? null; ?>
                                <span class="fw-bold <?= ($t && ($t >= 38.5 || $t <= 35.5)) ? 'text-danger' : ($t && $t >= 37.8 ? 'text-warning' : '') ?>">
                                    <?= $t ? $t.'°' : '--' ?>
                                </span>
                            </td>
                            <td><?= htmlspecialchars($obs['respiration'] ?? '--') ?></td>
                            <td><?= htmlspecialchars($obs['diurese'] ?? '--') ?></td>
                            <td>
                                <?php $c = $obs['conscience'] ?? ''; $cBadge = ['A'=>'success','V'=>'warning','P'=>'orange','U'=>'danger']; ?>
                                <span class="badge bg-<?= $cBadge[$c] ?? 'secondary' ?>"><?= htmlspecialchars($c ?: '--') ?></span>
                            </td>
                            <td style="max-width:180px;color:#475569"><?= htmlspecialchars($obs['observations'] ?? '') ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <script>
    (function() {
        const siData   = <?= json_encode(array_reverse($si_data)) ?>;
        const siLabels = siData.map(d => (d.date_obs||'') + ' ' + (d.heure_obs||''));
        const mkChart  = (id, datasets, yOpts) => new Chart(document.getElementById(id), {
            type: 'line',
            data: { labels: siLabels, datasets },
            options: { responsive: true, plugins: { legend: { display: datasets.length > 1 } },
                       scales: { y: yOpts, x: { ticks: { font: { size: 9 } } } } }
        });
        mkChart('chartSITemp',  [{ label:'T° (°C)', data: siData.map(d=>d.temperature), borderColor:'#ef4444', backgroundColor:'rgba(239,68,68,.08)', fill:true, tension:.35, pointRadius:3 }], { min:35, max:42 });
        mkChart('chartSIPouls', [{ label:'Pouls', data: siData.map(d=>d.pouls), borderColor:'#6366f1', backgroundColor:'rgba(99,102,241,.08)', fill:true, tension:.35, pointRadius:3 }], {});
        const taSys = siData.map(d => d.ta ? parseInt(d.ta.toString().split('/')[0]) : null);
        const taDia = siData.map(d => d.ta ? parseInt(d.ta.toString().split('/')[1]) : null);
        mkChart('chartSITA', [
            { label:'Systolique',  data:taSys, borderColor:'#0ea5e9', tension:.35, pointRadius:3 },
            { label:'Diastolique', data:taDia, borderColor:'#3b82f6', tension:.35, pointRadius:3 }
        ], {});
    })();
    </script>
    <?php endif; ?>

    <!-- ══ ÉVALUATIONS DE LA DOULEUR ════════════════════════════════════ -->
    <?php
    $evaluations_douleur = $evaluations_douleur ?? [];
    $nbDoul = count($evaluations_douleur);
    $sevCfgDoul = [
        'ABSENT'  => ['label'=>'Absent',  'bg'=>'#f0fdf4','color'=>'#166534','border'=>'#86efac','dot'=>'#22c55e'],
        'LEGERE'  => ['label'=>'Légère',  'bg'=>'#fefce8','color'=>'#854d0e','border'=>'#fde047','dot'=>'#eab308'],
        'MODEREE' => ['label'=>'Modérée', 'bg'=>'#fff7ed','color'=>'#9a3412','border'=>'#fb923c','dot'=>'#f97316'],
        'INTENSE' => ['label'=>'Intense', 'bg'=>'#fef2f2','color'=>'#991b1b','border'=>'#fca5a5','dot'=>'#ef4444'],
    ];
    $contexteLabel = ['AVANT_SOIN'=>'Avant soin','PENDANT_SOIN'=>'Pendant soin','APRES_SOIN'=>'Après soin'];
    ?>
    <div class="soins-card mb-4">
        <div class="soins-header" style="background:linear-gradient(135deg,#fff7ed,#ffedd5);border-bottom:1px solid #fed7aa;">
            <div class="soins-title">
                <i class="bi bi-heart-pulse-fill me-2" style="color:#f97316;"></i>
                Évaluations de la Douleur
                <?php if ($nbDoul > 0): ?>
                <span style="background:#f97316;color:#fff;font-size:.65rem;font-weight:800;padding:1px 8px;border-radius:20px;margin-left:8px;"><?= $nbDoul ?></span>
                <?php endif; ?>
            </div>
        </div>

        <?php if (empty($evaluations_douleur)): ?>
        <div class="empty-soins">
            <i class="bi bi-emoji-smile" style="font-size:2rem;color:#fed7aa;"></i>
            <p class="fw-bold mt-2">Aucune évaluation de la douleur enregistrée</p>
            <p class="small text-muted">Les évaluations effectuées par l'infirmier lors des soins apparaîtront ici.</p>
        </div>
        <?php else: ?>
        <div style="padding:16px;display:flex;flex-direction:column;gap:8px;">
            <?php foreach ($evaluations_douleur as $ev):
                $sev   = $sevCfgDoul[$ev['severite']] ?? $sevCfgDoul['LEGERE'];
                $dt    = !empty($ev['date_evaluation']) ? new DateTime($ev['date_evaluation']) : null;
                $dtStr = $dt ? $dt->format('d/m/Y H:i') : '';
                $inf   = trim(($ev['inf_prenom']??'').' '.($ev['inf_nom']??''));
                $pct   = $ev['score_max'] > 0 ? round(($ev['score'] / $ev['score_max']) * 100) : 0;
            ?>
            <div style="border:1.5px solid <?= $sev['border'] ?>;border-radius:12px;background:<?= $sev['bg'] ?>;overflow:hidden;">
                <div style="display:flex;align-items:center;gap:14px;padding:12px 16px;">
                    <!-- Score -->
                    <div style="min-width:54px;text-align:center;flex-shrink:0;">
                        <div style="font-size:1.5rem;font-weight:900;color:<?= $sev['color'] ?>;line-height:1;">
                            <?= htmlspecialchars($ev['score']) ?>
                        </div>
                        <div style="font-size:.62rem;color:<?= $sev['color'] ?>;opacity:.7;">/ <?= htmlspecialchars($ev['score_max']) ?></div>
                    </div>
                    <!-- Détail -->
                    <div style="flex:1;min-width:0;">
                        <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;margin-bottom:5px;">
                            <span style="background:<?= $sev['border'] ?>;color:<?= $sev['color'] ?>;font-size:.68rem;font-weight:800;padding:2px 9px;border-radius:20px;">
                                <?= strtoupper($sev['label']) ?>
                            </span>
                            <span style="font-size:.75rem;font-weight:700;color:#1e293b;"><?= htmlspecialchars($ev['echelle']) ?></span>
                            <?php if (!empty($ev['contexte'])): ?>
                            <span style="font-size:.65rem;color:#64748b;background:#f1f5f9;padding:1px 7px;border-radius:20px;">
                                <?= htmlspecialchars($contexteLabel[$ev['contexte']] ?? $ev['contexte']) ?>
                            </span>
                            <?php endif; ?>
                        </div>
                        <!-- Barre -->
                        <div style="height:6px;background:#e2e8f0;border-radius:20px;overflow:hidden;">
                            <div style="width:<?= $pct ?>%;height:100%;background:<?= $sev['dot'] ?>;border-radius:20px;"></div>
                        </div>
                        <?php if (!empty($ev['localisation'])): ?>
                        <div style="font-size:.72rem;color:#475569;margin-top:4px;">
                            <i class="bi bi-geo-alt-fill me-1" style="color:<?= $sev['dot'] ?>;"></i><?= htmlspecialchars($ev['localisation']) ?>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($ev['caracteristiques'])): ?>
                        <div style="font-size:.7rem;color:#64748b;margin-top:2px;">
                            <i class="bi bi-tag me-1"></i><?= htmlspecialchars($ev['caracteristiques']) ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    <!-- Date + infirmier -->
                    <div style="text-align:right;min-width:90px;flex-shrink:0;">
                        <div style="font-size:.72rem;font-weight:700;color:#1e293b;"><?= $dtStr ?></div>
                        <?php if ($inf): ?>
                        <div style="font-size:.65rem;color:#64748b;margin-top:2px;"><?= htmlspecialchars($inf) ?></div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php if (!empty($ev['action_prise']) || !empty($ev['note_infirmier'])): ?>
                <div style="padding:8px 16px 10px;border-top:1px solid <?= $sev['border'] ?>55;background:rgba(255,255,255,.5);">
                    <?php if (!empty($ev['action_prise'])): ?>
                    <div style="font-size:.75rem;color:#374151;margin-bottom:3px;">
                        <i class="bi bi-lightning-fill me-1" style="color:#f59e0b;"></i>
                        <strong>Action :</strong> <?= htmlspecialchars($ev['action_prise']) ?>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($ev['note_infirmier'])): ?>
                    <div style="font-size:.75rem;color:#374151;">
                        <i class="bi bi-pencil me-1" style="color:#6366f1;"></i><?= htmlspecialchars($ev['note_infirmier']) ?>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- ══ RÉÉVALUATIONS MÉDICALES ══════════════════════════════════════ -->
    <?php
    $reevaluations_medecin = $reevaluations_medecin ?? [];
    $nbReev = count($reevaluations_medecin);
    $evolCfg = [
        'FAVORABLE'              => ['label'=>'Favorable',             'bg'=>'#dcfce7','color'=>'#166534','border'=>'#16a34a','icon'=>'bi-arrow-up-circle-fill'],
        'AMELIORATION_PARTIELLE' => ['label'=>'Amélioration partielle','bg'=>'#d1fae5','color'=>'#0f766e','border'=>'#14b8a6','icon'=>'bi-arrow-up-right-circle-fill'],
        'STATUO_QUO'             => ['label'=>'Statuo quo',            'bg'=>'#f1f5f9','color'=>'#475569','border'=>'#94a3b8','icon'=>'bi-dash-circle-fill'],
        'NON_FAVORABLE'          => ['label'=>'Non favorable',         'bg'=>'#fef3c7','color'=>'#b45309','border'=>'#f59e0b','icon'=>'bi-arrow-down-right-circle-fill'],
        'AGGRAVATION'            => ['label'=>'Aggravation',           'bg'=>'#fee2e2','color'=>'#b91c1c','border'=>'#ef4444','icon'=>'bi-arrow-down-circle-fill'],
        'CRITIQUE'               => ['label'=>'Critique',              'bg'=>'#fce7f3','color'=>'#9f1239','border'=>'#f43f5e','icon'=>'bi-exclamation-octagon-fill'],
    ];
    ?>
    <div class="soins-card mb-4" id="sectionReevalMedecin">
        <div class="soins-header" style="flex-wrap:wrap;gap:10px;">
            <div class="soins-title">
                <i class="bi bi-file-medical-fill me-2" style="color:#10b981;"></i>
                Suivi Médical &amp; Réévaluations du Médecin
            </div>
            <div style="display:flex;align-items:center;gap:8px;">
                <?php if ($nbReev > 0): ?>
                <span style="background:#d1fae5;color:#065f46;border:1px solid #6ee7b7;
                             border-radius:20px;padding:4px 12px;font-size:.72rem;font-weight:800;">
                    <i class="bi bi-clipboard2-check me-1"></i>
                    <?= $nbReev ?> réévaluation<?= $nbReev > 1 ? 's' : '' ?>
                </span>
                <?php endif; ?>
            </div>
        </div>

        <?php if (empty($reevaluations_medecin)): ?>
        <div class="empty-soins">
            <i class="bi bi-file-medical" style="font-size:2rem;color:#cbd5e1;"></i>
            <p class="fw-bold mt-2">Aucune réévaluation médicale enregistrée</p>
            <p class="small text-muted">Les réévaluations effectuées par le médecin apparaîtront ici.</p>
        </div>
        <?php else: ?>
        <div style="display:flex;flex-direction:column;gap:0;padding:0 4px 4px;">
        <?php foreach ($reevaluations_medecin as $idx => $rev):
            $ec       = $evolCfg[$rev['evolution_globale'] ?? ''] ?? $evolCfg['STATUO_QUO'];
            $medecin  = trim(($rev['medecin_prenom'] ?? '') . ' ' . ($rev['medecin_nom'] ?? ''));
            $dateRev  = $rev['date_reevaluation'] ? date('d/m/Y', strtotime($rev['date_reevaluation'])) : '—';
            $heureRev = substr($rev['heure_reevaluation'] ?? '', 0, 5);
            $isFirst  = $idx === 0;
            $grpId    = 'revGrp_' . $idx;
            $hasMeds  = !empty($rev['medicaments']);
            $hasBilans= !empty($rev['bilans']);
        ?>
        <div class="rev-group" style="border-color:<?= $ec['border'] ?>50;">
            <!-- En-tête cliquable -->
            <button type="button" class="rev-group-header" onclick="toggleRevGrp('<?= $grpId ?>')"
                    style="background:<?= $ec['bg'] ?>;">
                <div style="display:flex;align-items:center;gap:10px;">
                    <div style="width:38px;height:38px;border-radius:10px;background:<?= $ec['color'] ?>20;
                                display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <i class="bi <?= $ec['icon'] ?>" style="color:<?= $ec['color'] ?>;font-size:1.1rem;"></i>
                    </div>
                    <div>
                        <div style="font-size:.87rem;font-weight:800;color:#0f172a;display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
                            <?= $dateRev ?>
                            <span style="font-size:.73rem;color:#64748b;font-weight:500;"><?= $heureRev ?></span>
                            <?php if ($isFirst): ?>
                            <span style="background:#1e40af;color:#fff;font-size:.57rem;padding:1px 7px;border-radius:5px;font-weight:700;">DERNIÈRE</span>
                            <?php endif; ?>
                        </div>
                        <div style="font-size:.71rem;color:#475569;margin-top:1px;">
                            <i class="bi bi-person-badge-fill me-1" style="color:<?= $ec['color'] ?>;"></i>
                            Dr. <?= htmlspecialchars($medecin) ?>
                        </div>
                    </div>
                </div>
                <div style="display:flex;align-items:center;gap:8px;">
                    <span style="background:<?= $ec['color'] ?>;color:#fff;font-size:.62rem;font-weight:700;
                                 padding:3px 10px;border-radius:20px;white-space:nowrap;">
                        <i class="bi <?= $ec['icon'] ?> me-1"></i><?= $ec['label'] ?>
                    </span>
                    <?php if ($hasMeds): ?>
                    <span style="background:#ede9fe;color:#7c3aed;font-size:.6rem;font-weight:700;padding:2px 7px;border-radius:6px;">
                        <i class="bi bi-capsule me-1"></i><?= count($rev['medicaments']) ?> méd.
                    </span>
                    <?php endif; ?>
                    <?php if ($hasBilans): ?>
                    <span style="background:#e0f2fe;color:#0369a1;font-size:.6rem;font-weight:700;padding:2px 7px;border-radius:6px;">
                        <i class="bi bi-microscope me-1"></i><?= count($rev['bilans']) ?> bilan<?= count($rev['bilans'])>1?'s':'' ?>
                    </span>
                    <?php endif; ?>
                    <i id="<?= $grpId ?>_chev" class="bi bi-chevron-<?= $isFirst ? 'up' : 'down' ?>"
                       style="color:<?= $ec['color'] ?>;font-size:.8rem;flex-shrink:0;"></i>
                </div>
            </button>

            <!-- Corps déroulant -->
            <div id="<?= $grpId ?>" class="rev-group-body"
                 style="max-height:<?= $isFirst ? '3000px' : '0' ?>;padding:<?= $isFirst ? '16px' : '0' ?> 16px;">

                <?php if (!empty($rev['diagnostic_jour'])): ?>
                <div style="margin-bottom:14px;">
                    <div class="rev-label"><i class="bi bi-file-earmark-medical-fill" style="color:#3b82f6;"></i>Diagnostic du jour</div>
                    <div class="rev-content" style="background:#eff6ff;border-left:3px solid #3b82f6;font-weight:600;color:#1e3a5f;">
                        <?= htmlspecialchars($rev['diagnostic_jour']) ?>
                        <?php if (!empty($rev['code_cim10'])): ?>
                        <span style="background:#dbeafe;color:#1d4ed8;font-size:.65rem;font-weight:700;
                                     padding:1px 7px;border-radius:6px;margin-left:8px;"><?= htmlspecialchars($rev['code_cim10']) ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>

                <?php if (!empty($rev['note_evolution'])): ?>
                <div style="margin-bottom:14px;">
                    <div class="rev-label"><i class="bi bi-activity" style="color:<?= $ec['color'] ?>;"></i>Note d'évolution</div>
                    <div class="rev-content" style="background:<?= $ec['bg'] ?>;border-left:3px solid <?= $ec['border'] ?>;white-space:pre-line;">
                        <?= htmlspecialchars($rev['note_evolution']) ?>
                    </div>
                </div>
                <?php endif; ?>

                <?php if (!empty($rev['conduite_tenir'])): ?>
                <div style="margin-bottom:14px;">
                    <div class="rev-label"><i class="bi bi-list-check" style="color:#0d9488;"></i>Conduite à tenir (CAT)</div>
                    <div class="rev-content" style="background:#f0fdfa;border-left:3px solid #0d9488;color:#134e4a;white-space:pre-line;">
                        <?= htmlspecialchars($rev['conduite_tenir']) ?>
                    </div>
                </div>
                <?php endif; ?>

                <?php if ($hasMeds): ?>
                <div style="margin-bottom:14px;">
                    <div class="rev-label">
                        <i class="bi bi-capsule-pill" style="color:#8b5cf6;"></i>Médicaments prescrits
                        <span style="background:#ede9fe;color:#7c3aed;font-size:.6rem;padding:1px 6px;border-radius:6px;font-weight:700;"><?= count($rev['medicaments']) ?></span>
                    </div>
                    <?php foreach ($rev['medicaments'] as $med): ?>
                    <div class="rev-med-item">
                        <div style="flex-shrink:0;width:30px;height:30px;border-radius:8px;background:#ede9fe;
                                    display:flex;align-items:center;justify-content:center;margin-top:1px;">
                            <i class="bi bi-capsule" style="color:#7c3aed;font-size:.85rem;"></i>
                        </div>
                        <div style="flex:1;min-width:0;">
                            <div style="font-size:.85rem;font-weight:700;color:#3b0764;"><?= htmlspecialchars($med['nom_medicament']) ?></div>
                            <div style="font-size:.73rem;color:#6d28d9;margin-top:3px;display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
                                <?php if (!empty($med['posologie'])): ?><span><?= htmlspecialchars($med['posologie']) ?></span><?php endif; ?>
                                <?php if (!empty($med['voie_administration'])): ?><span style="background:#ede9fe;padding:1px 6px;border-radius:5px;font-weight:700;"><?= htmlspecialchars($med['voie_administration']) ?></span><?php endif; ?>
                                <?php if (!empty($med['frequence'])): ?><span><?= htmlspecialchars($med['frequence']) ?></span><?php endif; ?>
                                <?php if (!empty($med['duree'])): ?><span style="color:#94a3b8;">· <?= htmlspecialchars($med['duree']) ?></span><?php endif; ?>
                            </div>
                            <?php if (!empty($med['notes'])): ?><div style="font-size:.7rem;color:#94a3b8;margin-top:2px;font-style:italic;"><?= htmlspecialchars($med['notes']) ?></div><?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <?php if ($hasBilans): ?>
                <div style="margin-bottom:14px;">
                    <div class="rev-label">
                        <i class="bi bi-microscope" style="color:#0ea5e9;"></i>Bilans demandés
                        <span style="background:#e0f2fe;color:#0369a1;font-size:.6rem;padding:1px 6px;border-radius:6px;font-weight:700;"><?= count($rev['bilans']) ?></span>
                    </div>
                    <?php foreach ($rev['bilans'] as $bilan):
                        $isLabo = ($bilan['type'] === 'LABO');
                        $bBg    = $isLabo ? '#f0f9ff' : '#fdf4ff';
                        $bBorder= $isLabo ? '#bae6fd' : '#e9d5ff';
                        $bColor = $isLabo ? '#0369a1' : '#7e22ce';
                        $bIcon  = $isLabo ? 'bi-flask-fill' : 'bi-image';
                        $bLabel = $isLabo ? 'Labo' : 'Imagerie';
                    ?>
                    <div class="rev-bilan-item" style="background:<?= $bBg ?>;border:1px solid <?= $bBorder ?>;">
                        <i class="bi <?= $bIcon ?>" style="color:<?= $bColor ?>;font-size:.9rem;flex-shrink:0;"></i>
                        <div style="flex:1;">
                            <span style="font-size:.82rem;font-weight:600;color:#1e293b;"><?= htmlspecialchars($bilan['intitule']) ?></span>
                            <?php if (!empty($bilan['urgence'])): ?>
                            <span style="background:#fee2e2;color:#b91c1c;font-size:.6rem;font-weight:700;padding:1px 6px;border-radius:5px;margin-left:6px;">URGENT</span>
                            <?php endif; ?>
                        </div>
                        <span style="background:<?= $bBg ?>;color:<?= $bColor ?>;font-size:.6rem;font-weight:700;
                                     padding:1px 6px;border-radius:5px;border:1px solid <?= $bBorder ?>;"><?= $bLabel ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <?php if (!empty($rev['traitement_non_medicamenteux'])): ?>
                <div>
                    <div class="rev-label"><i class="bi bi-heart-pulse" style="color:#f97316;"></i>Traitement non médicamenteux</div>
                    <div class="rev-content" style="background:#fff7ed;border-left:3px solid #f97316;color:#7c2d12;white-space:pre-line;">
                        <?= htmlspecialchars($rev['traitement_non_medicamenteux']) ?>
                    </div>
                </div>
                <?php endif; ?>

            </div><!-- /corps -->
        </div><!-- /groupe -->
        <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- ══ OBSERVATIONS INFIRMIÈRES ══════════════════════════════════════ -->
    <?php
    $historique_parametres = $historique_parametres ?? [];
    // Séparer les fiches avec observation et celles sans
    $fiches_avec_obs      = array_filter($historique_parametres, fn($p) => !empty(trim($p['observations'] ?? '')));
    $fiches_avec_plaintes = array_filter($historique_parametres, fn($p) => !empty(trim($p['plaintes'] ?? '')));
    $toutes_fiches        = $historique_parametres;
    $nbObs      = count($fiches_avec_obs);
    $nbPlaintes = count($fiches_avec_plaintes);
    $nbFiches   = count($toutes_fiches);
    ?>
    <!-- ══ FICHES TRANSFUSIONNELLES ══════════════════════════════════ -->
    <?php
    $transfusions = $transfusions ?? [];
    $nbTrans = count($transfusions);
    ?>
    <div class="soins-card mb-4" id="sectionTransfusions">
        <div class="soins-header" style="flex-wrap:wrap;gap:10px;
             background:linear-gradient(135deg,#fff1f2,#ffe4e6);border-bottom:1px solid #fecdd3;">
            <div class="soins-title">
                <i class="bi bi-droplet-half me-2" style="color:#e11d48;"></i>
                Fiches Transfusionnelles
                <?php if ($nbTrans > 0): ?>
                <span style="margin-left:8px;background:#e11d48;color:#fff;
                             font-size:.7rem;font-weight:800;padding:2px 8px;
                             border-radius:20px;"><?= $nbTrans ?></span>
                <?php endif; ?>
            </div>
            <a href="<?= BASE_URL ?>hospitalisation/fiche-transfusionnelle/<?= $patient['id'] ?>"
               class="btn btn-sm fw-bold rounded-pill px-3"
               style="background:#e11d48;color:#fff;border:none;font-size:.78rem;">
                <i class="bi bi-plus-circle me-1"></i> Nouvelle fiche
            </a>
        </div>

        <?php if ($nbTrans === 0): ?>
        <div class="empty-soins">
            <i class="bi bi-droplet-half" style="font-size:2rem;color:#fecdd3;"></i>
            <p class="fw-bold">Aucune fiche transfusionnelle enregistrée</p>
            <p class="small text-muted">Cliquez sur <strong>Nouvelle fiche</strong> pour en créer une.</p>
        </div>
        <?php else: ?>
        <div style="padding:16px;display:flex;flex-direction:column;gap:12px;">
        <?php foreach ($transfusions as $tr):
            $nbPoches = count($tr['poches'] ?? []);
            $dateLabel = $tr['created_at'] ? date('d/m/Y à H:i', strtotime($tr['created_at'])) : '';
            $auteur = trim(($tr['auteur_prenom'] ?? '') . ' ' . ($tr['auteur_nom'] ?? ''));
        ?>
        <div style="border:1px solid #fecdd3;border-radius:12px;overflow:hidden;">
            <!-- Entête fiche -->
            <div style="background:linear-gradient(90deg,#fff1f2,#fff);
                        padding:10px 14px;display:flex;align-items:center;
                        justify-content:space-between;flex-wrap:wrap;gap:8px;
                        border-bottom:1px solid #fecdd3;">
                <div style="display:flex;align-items:center;gap:10px;">
                    <span style="background:#e11d48;color:#fff;border-radius:50%;
                                 width:32px;height:32px;display:flex;align-items:center;
                                 justify-content:center;font-size:.9rem;flex-shrink:0;">
                        <i class="bi bi-droplet-fill"></i>
                    </span>
                    <div>
                        <div style="font-size:.83rem;font-weight:800;color:#1e293b;">
                            <?= htmlspecialchars($tr['indication'] ?: 'Transfusion') ?>
                            <?php if ($tr['groupe_sanguin']): ?>
                            <span style="background:#fee2e2;color:#b91c1c;padding:1px 7px;
                                         border-radius:20px;font-size:.72rem;margin-left:6px;font-weight:700;">
                                <?= htmlspecialchars($tr['groupe_sanguin']) ?>
                            </span>
                            <?php endif; ?>
                        </div>
                        <div style="font-size:.72rem;color:#64748b;margin-top:1px;">
                            <?= $dateLabel ?>
                            <?php if ($auteur): ?> · Par <?= htmlspecialchars($auteur) ?><?php endif; ?>
                            · <strong><?= $nbPoches ?> poche<?= $nbPoches > 1 ? 's' : '' ?></strong>
                        </div>
                    </div>
                </div>
                <div style="display:flex;gap:6px;align-items:center;flex-wrap:wrap;">
                    <?php if ($tr['consentement'] === 'Oui'): ?>
                    <span style="background:#dcfce7;color:#166534;padding:2px 8px;
                                 border-radius:20px;font-size:.7rem;font-weight:700;">
                        <i class="bi bi-check-circle-fill me-1"></i>Consentement OK
                    </span>
                    <?php endif; ?>
                    <?php if ($tr['taux_hb']): ?>
                    <span style="background:#fef3c7;color:#92400e;padding:2px 8px;
                                 border-radius:20px;font-size:.7rem;font-weight:700;">
                        Hb <?= htmlspecialchars($tr['taux_hb']) ?> g/dL
                    </span>
                    <?php endif; ?>
                    <button type="button"
                            onclick="toggleTrans(this)"
                            style="background:#f1f5f9;border:1px solid #e2e8f0;color:#64748b;
                                   border-radius:8px;padding:4px 10px;font-size:.75rem;
                                   font-weight:600;cursor:pointer;">
                        <i class="bi bi-chevron-down"></i> Détails
                    </button>
                </div>
            </div>
            <!-- Corps (replié par défaut) -->
            <div class="trans-detail" style="display:none;padding:12px 14px;">
                <?php if ($tr['diagnostic']): ?>
                <div style="margin-bottom:10px;font-size:.8rem;">
                    <span style="font-weight:700;color:#475569;">Diagnostic :</span>
                    <?= htmlspecialchars($tr['diagnostic']) ?>
                </div>
                <?php endif; ?>
                <!-- Bilan pré-transfusionnel -->
                <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:12px;">
                    <?php foreach ([
                        ['Groupe vérifié', $tr['groupe_verifie']],
                        ['Rhésus',         $tr['rhesus']],
                        ['RAI',            $tr['rai']],
                        ['N° Compat.',     $tr['num_compat']],
                        ['Prescripteur',   $tr['medecin_prescripteur']],
                    ] as [$lbl, $val]):
                        if (!$val) continue; ?>
                    <span style="background:#f8fafc;border:1px solid #e2e8f0;padding:3px 10px;
                                 border-radius:8px;font-size:.75rem;color:#475569;">
                        <strong><?= $lbl ?> :</strong> <?= htmlspecialchars($val) ?>
                    </span>
                    <?php endforeach; ?>
                </div>
                <!-- Tableau des poches -->
                <?php if ($nbPoches > 0): ?>
                <div style="overflow-x:auto;border-radius:8px;border:1px solid #fecdd3;">
                    <table style="width:100%;border-collapse:collapse;font-size:.75rem;">
                        <thead>
                            <tr style="background:#e11d48;color:#fff;">
                                <th style="padding:7px 8px;white-space:nowrap;">Date</th>
                                <th style="padding:7px 8px;">Début</th>
                                <th style="padding:7px 8px;">Fin</th>
                                <th style="padding:7px 8px;">Produit</th>
                                <th style="padding:7px 8px;">N° Poche</th>
                                <th style="padding:7px 8px;">Groupe</th>
                                <th style="padding:7px 8px;">Vol.</th>
                                <th style="padding:7px 8px;">T° avant</th>
                                <th style="padding:7px 8px;">TA avant</th>
                                <th style="padding:7px 8px;">Pouls</th>
                                <th style="padding:7px 8px;">T° après</th>
                                <th style="padding:7px 8px;">Réaction</th>
                                <th style="padding:7px 8px;">Infirmier</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($tr['poches'] as $idx => $p):
                            $rowBg = ($idx % 2 === 0) ? '#fff' : '#fff8f8';
                            $hasRx = !empty($p['reaction']) && $p['reaction'] !== 'Aucune';
                        ?>
                        <tr style="background:<?= $rowBg ?>;<?= $hasRx ? 'color:#b91c1c;font-weight:700;' : '' ?>">
                            <td style="padding:6px 8px;border-bottom:1px solid #fecdd3;"><?= htmlspecialchars($p['date_trans'] ?? '') ?></td>
                            <td style="padding:6px 8px;border-bottom:1px solid #fecdd3;"><?= htmlspecialchars($p['heure_debut'] ?? '') ?></td>
                            <td style="padding:6px 8px;border-bottom:1px solid #fecdd3;"><?= htmlspecialchars($p['heure_fin'] ?? '') ?></td>
                            <td style="padding:6px 8px;border-bottom:1px solid #fecdd3;"><?= htmlspecialchars($p['type_produit'] ?? '') ?></td>
                            <td style="padding:6px 8px;border-bottom:1px solid #fecdd3;"><?= htmlspecialchars($p['num_poche'] ?? '') ?></td>
                            <td style="padding:6px 8px;border-bottom:1px solid #fecdd3;text-align:center;">
                                <strong><?= htmlspecialchars($p['groupe'] ?? '') ?></strong>
                            </td>
                            <td style="padding:6px 8px;border-bottom:1px solid #fecdd3;text-align:center;"><?= $p['volume'] ? $p['volume'].' mL' : '' ?></td>
                            <td style="padding:6px 8px;border-bottom:1px solid #fecdd3;text-align:center;"><?= $p['temp_avant'] ? $p['temp_avant'].'°' : '' ?></td>
                            <td style="padding:6px 8px;border-bottom:1px solid #fecdd3;text-align:center;"><?= htmlspecialchars($p['ta_avant'] ?? '') ?></td>
                            <td style="padding:6px 8px;border-bottom:1px solid #fecdd3;text-align:center;"><?= $p['pouls'] ?: '' ?></td>
                            <td style="padding:6px 8px;border-bottom:1px solid #fecdd3;text-align:center;"><?= $p['temp_apres'] ? $p['temp_apres'].'°' : '' ?></td>
                            <td style="padding:6px 8px;border-bottom:1px solid #fecdd3;">
                                <?php if ($hasRx): ?>
                                <span style="background:#fee2e2;padding:1px 6px;border-radius:10px;">
                                    <?= htmlspecialchars($p['reaction']) ?>
                                </span>
                                <?php else: ?>
                                <span style="color:#86efac;">Aucune</span>
                                <?php endif; ?>
                            </td>
                            <td style="padding:6px 8px;border-bottom:1px solid #fecdd3;"><?= htmlspecialchars($p['infirmier'] ?? '') ?></td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
                <!-- Observations post-transfusionnelles -->
                <?php if (!empty(trim($tr['observations_finales'] ?? ''))): ?>
                <div style="margin-top:10px;background:#fff8f8;border-left:3px solid #e11d48;
                            padding:8px 12px;border-radius:0 8px 8px 0;font-size:.8rem;color:#1e293b;">
                    <i class="bi bi-journal-text me-1 text-danger"></i>
                    <strong>Observations :</strong> <?= nl2br(htmlspecialchars($tr['observations_finales'])) ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
    <!-- ── FIN FICHES TRANSFUSIONNELLES ── -->

    <div class="soins-card mb-4" id="sectionObsInf">
        <div class="soins-header" style="flex-wrap:wrap;gap:10px;">
            <div class="soins-title">
                <i class="bi bi-chat-dots-fill me-2" style="color:#0d9488"></i>
                Fiches de Paramètres &amp; Observations Infirmières
            </div>
            <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                <?php if($nbFiches > 0): ?>
                <span style="background:#f0fdfa;color:#0d9488;border:1px solid #99f6e4;border-radius:20px;padding:4px 12px;font-size:.72rem;font-weight:800;">
                    <?= $nbFiches ?> fiche<?= $nbFiches > 1 ? 's' : '' ?>
                </span>
                <?php endif; ?>
                <?php if($nbPlaintes > 0): ?>
                <span style="background:#fef3c7;color:#92400e;border:1px solid #fde68a;border-radius:20px;padding:4px 12px;font-size:.72rem;font-weight:800;">
                    <i class="bi bi-emoji-frown-fill me-1" style="color:#f59e0b;"></i><?= $nbPlaintes ?> plainte<?= $nbPlaintes > 1 ? 's' : '' ?>
                </span>
                <?php endif; ?>
                <?php if($nbObs > 0): ?>
                <span style="background:#ede9fe;color:#7c3aed;border:1px solid #c4b5fd;border-radius:20px;padding:4px 12px;font-size:.72rem;font-weight:800;">
                    <i class="bi bi-chat-text-fill me-1"></i><?= $nbObs ?> observation<?= $nbObs > 1 ? 's' : '' ?>
                </span>
                <?php endif; ?>
                <!-- Basculer vue : observations seules vs toutes les fiches -->
                <?php if($nbFiches > 0): ?>
                <button type="button" onclick="toggleVueFiches(this)"
                        data-mode="obs"
                        style="background:#f8fafc;color:#475569;border:1.5px solid #e2e8f0;border-radius:20px;padding:5px 14px;font-size:.75rem;font-weight:700;cursor:pointer;">
                    <i class="bi bi-funnel-fill me-1"></i>Avec observations seulement
                </button>
                <?php endif; ?>
            </div>
        </div>

        <?php if (empty($toutes_fiches)): ?>
        <div class="empty-soins">
            <i class="bi bi-clipboard2-pulse text-muted"></i>
            <p class="fw-bold">Aucune fiche de paramètres enregistrée</p>
            <p class="small text-muted">Utilisez <strong>Ajouter Constantes</strong> pour saisir la première fiche.</p>
        </div>
        <?php else:
        // ── Grouper les fiches par date (yyyy-mm-dd) ──────────────────
        $fichesByDate = [];
        foreach ($toutes_fiches as $fiche) {
            $dk = $fiche['date_mesure'] ? substr($fiche['date_mesure'], 0, 10) : 'sans_date';
            $fichesByDate[$dk][] = $fiche;
        }
        krsort($fichesByDate); // plus récent en premier
        $todayKey = date('Y-m-d');
        $jours = ['Dimanche','Lundi','Mardi','Mercredi','Jeudi','Vendredi','Samedi'];
        $mois  = ['jan.','fév.','mars','avr.','mai','juin','juil.','août','sept.','oct.','nov.','déc.'];
        $fgIdx = 0;
        ?>
        <div id="listeFichesParametres">
        <?php foreach ($fichesByDate as $dateKey => $fiches):
            $isToday  = ($dateKey === $todayKey);
            $isPast   = ($dateKey < $todayKey);
            $openDef  = $isToday; // ouvert si aujourd'hui
            $fgId     = 'ficheGrp_' . $fgIdx++;

            // Compteurs du groupe
            $grpNb    = count($fiches);
            $grpObs   = count(array_filter($fiches, fn($f) => !empty(trim($f['observations'] ?? ''))));
            $grpPl    = count(array_filter($fiches, fn($f) => !empty(trim($f['plaintes'] ?? ''))));

            // Libellé de la date
            if ($dateKey === 'sans_date') {
                $dateLabel = 'Date inconnue';
            } else {
                $d = new DateTime($dateKey);
                $dateLabel = $jours[(int)$d->format('w')] . ' ' . (int)$d->format('j') . ' ' . $mois[(int)$d->format('n')-1] . ' ' . $d->format('Y');
            }

            // Couleur du header selon la date
            $hBg     = $isToday ? '#f0fdf4' : ($isPast ? '#f8fafc' : '#eff6ff');
            $hBorder = $isToday ? '#16a34a' : ($isPast ? '#94a3b8' : '#3b82f6');
            $hColor  = $isToday ? '#166534' : ($isPast ? '#475569' : '#1d4ed8');
        ?>
        <div class="fiche-date-group" data-date="<?= $dateKey ?>"
             style="margin-bottom:10px;border:1.5px solid <?= $hBorder ?>40;border-radius:12px;overflow:hidden;">

            <!-- En-tête de date cliquable -->
            <button type="button" onclick="toggleFicheGrp('<?= $fgId ?>')"
                    style="width:100%;display:flex;align-items:center;justify-content:space-between;
                           padding:10px 14px;border:none;cursor:pointer;text-align:left;
                           background:<?= $hBg ?>;transition:filter .15s;">
                <div style="display:flex;align-items:center;gap:10px;">
                    <div style="width:8px;height:8px;border-radius:50%;background:<?= $hBorder ?>;flex-shrink:0;"></div>
                    <span style="font-size:.8rem;font-weight:800;color:<?= $hColor ?>;letter-spacing:.2px;">
                        <?= $dateLabel ?>
                        <?php if ($isToday): ?>
                        <span style="background:#16a34a;color:#fff;font-size:.57rem;padding:1px 6px;border-radius:5px;margin-left:6px;font-weight:700;">AUJOURD'HUI</span>
                        <?php endif; ?>
                    </span>
                </div>
                <div style="display:flex;align-items:center;gap:6px;">
                    <span style="font-size:.68rem;font-weight:700;color:#64748b;background:#fff;
                                 padding:2px 8px;border-radius:6px;border:1px solid #e2e8f0;">
                        <?= $grpNb ?> fiche<?= $grpNb > 1 ? 's' : '' ?>
                    </span>
                    <?php if ($grpPl > 0): ?>
                    <span style="background:#fef3c7;color:#92400e;font-size:.6rem;font-weight:700;padding:2px 7px;border-radius:6px;border:1px solid #fde68a;">
                        <i class="bi bi-emoji-frown-fill me-1" style="color:#f59e0b;"></i><?= $grpPl ?>
                    </span>
                    <?php endif; ?>
                    <?php if ($grpObs > 0): ?>
                    <span style="background:#ede9fe;color:#7c3aed;font-size:.6rem;font-weight:700;padding:2px 7px;border-radius:6px;border:1px solid #c4b5fd;">
                        <i class="bi bi-chat-text-fill me-1"></i><?= $grpObs ?>
                    </span>
                    <?php endif; ?>
                    <i id="<?= $fgId ?>_chev" class="bi bi-chevron-<?= $openDef ? 'up' : 'down' ?>"
                       style="color:<?= $hColor ?>;font-size:.78rem;flex-shrink:0;"></i>
                </div>
            </button>

            <!-- Corps du groupe -->
            <div id="<?= $fgId ?>"
                 style="max-height:<?= $openDef ? '6000px' : '0' ?>;overflow:hidden;
                        transition:max-height .35s ease,padding .3s ease;background:#fff;
                        padding:<?= $openDef ? '10px' : '0' ?> 10px;">
            <div style="display:flex;flex-direction:column;gap:8px;">

            <?php foreach ($fiches as $fiche):
                $hasObs      = !empty(trim($fiche['observations'] ?? ''));
                $hasPlaintes = !empty(trim($fiche['plaintes'] ?? ''));
                $auteur    = trim(($fiche['infirmier_prenom'] ?? '') . ' ' . ($fiche['infirmier_nom'] ?? ''));
                $role      = strtoupper($fiche['infirmier_role'] ?? '');
                $roleLabel = match($role) {
                    'INFIRMIER','INFIRMIERE' => 'Inf.',
                    'MEDECIN'               => 'Dr',
                    'ADMIN'                 => 'Admin',
                    default                 => ''
                };
                $dateMesure = $fiche['date_mesure'] ?? '';
                $heureAff   = $dateMesure ? date('H:i', strtotime($dateMesure)) : '--';

                $temp_f = (float)($fiche['temperature'] ?? 0);
                $fc_f   = (int)($fiche['frequence_cardiaque'] ?? 0);
                $spo2_f = (int)($fiche['saturation_oxygene'] ?? 0);
                $sys_f  = (int)($fiche['pression_arterielle_systolique'] ?? 0);
                $dia_f  = (int)($fiche['pression_arterielle_diastolique'] ?? 0);
                $fr_f   = (int)($fiche['frequence_respiratoire'] ?? 0);
                $gly_f  = (float)($fiche['glycemie'] ?? 0);
                $diu_f  = (int)($fiche['diurese'] ?? 0);
                $o2_f   = (int)($fiche['sous_oxygene'] ?? 0);
                $dO2_f  = (float)($fiche['debit_oxygene'] ?? 0);
                $cardBorder = $hasPlaintes ? '#fde68a' : ($hasObs ? '#c4b5fd' : '#e2e8f0');
                $cardBg     = $hasPlaintes ? '#fffef5' : ($hasObs ? '#fdf8ff' : '#fafbfc');
            ?>
            <div class="fiche-param-item <?= $hasObs ? 'has-obs' : '' ?> <?= $hasPlaintes ? 'has-plaintes' : '' ?>"
                 style="border-radius:12px;border:1.5px solid <?= $cardBorder ?>;
                        background:<?= $cardBg ?>;padding:12px 14px;transition:.15s;">

                <!-- En-tête fiche -->
                <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px;margin-bottom:8px;">
                    <div style="display:flex;align-items:center;gap:8px;">
                        <div style="width:32px;height:32px;border-radius:9px;flex-shrink:0;
                                    background:<?= $hasObs ? 'linear-gradient(135deg,#7c3aed,#a855f7)' : 'linear-gradient(135deg,#0d9488,#14b8a6)' ?>;
                                    display:flex;align-items:center;justify-content:center;color:#fff;font-weight:800;font-size:.75rem;">
                            <?= mb_strtoupper(mb_substr($fiche['infirmier_prenom'] ?? 'I', 0, 1) . mb_substr($fiche['infirmier_nom'] ?? '', 0, 1)) ?>
                        </div>
                        <div>
                            <div style="font-weight:700;font-size:.83rem;color:#1e293b;">
                                <?= $roleLabel ? '<span style="font-size:.68rem;color:#64748b;margin-right:3px;">'.$roleLabel.'</span>' : '' ?>
                                <?= htmlspecialchars($auteur ?: 'Utilisateur') ?>
                            </div>
                            <div style="font-size:.7rem;color:#94a3b8;">
                                <i class="bi bi-clock me-1"></i><?= $heureAff ?>
                            </div>
                        </div>
                    </div>
                    <div style="display:flex;gap:5px;flex-wrap:wrap;align-items:center;">
                        <?php if ($hasPlaintes): ?>
                        <span style="background:#fef3c7;color:#92400e;border:1px solid #fde68a;border-radius:20px;padding:2px 9px;font-size:.65rem;font-weight:800;">
                            <i class="bi bi-emoji-frown-fill me-1" style="color:#f59e0b;"></i>Plaintes
                        </span>
                        <?php endif; ?>
                        <?php if ($hasObs): ?>
                        <span style="background:#ede9fe;color:#7c3aed;border:1px solid #c4b5fd;border-radius:20px;padding:2px 9px;font-size:.65rem;font-weight:800;">
                            <i class="bi bi-chat-text-fill me-1"></i>Observation
                        </span>
                        <?php endif; ?>
                        <?php
                        $peutEditerFiche = (($_SESSION['user_id'] ?? 0) == ($fiche['user_id'] ?? -1))
                                        || in_array(strtoupper($_SESSION['user_role'] ?? ''), ['ADMIN','MEDECIN','MAJOR_INFIRMIER','MEDECIN_CHEF']);
                        if ($peutEditerFiche):
                            $ficheEditData = htmlspecialchars(json_encode([
                                'id'                    => (int)($fiche['id'] ?? 0),
                                'temperature'           => $fiche['temperature'] ?? '',
                                'frequence_cardiaque'   => $fiche['frequence_cardiaque'] ?? '',
                                'spo2'                  => $fiche['saturation_oxygene'] ?? '',
                                'tension_sys'           => $fiche['pression_arterielle_systolique'] ?? '',
                                'tension_dia'           => $fiche['pression_arterielle_diastolique'] ?? '',
                                'frequence_respiratoire'=> $fiche['frequence_respiratoire'] ?? '',
                                'glycemie'              => $fiche['glycemie'] ?? '',
                                'diurese'               => $fiche['diurese'] ?? '',
                                'sous_oxygene'          => $fiche['sous_oxygene'] ?? 0,
                                'debit_oxygene'         => $fiche['debit_oxygene'] ?? '',
                                'observations'          => $fiche['observations'] ?? '',
                                'plaintes'              => $fiche['plaintes'] ?? '',
                            ]), ENT_QUOTES);
                        ?>
                        <button type="button"
                                onclick="ouvrirEditFiche(<?= $ficheEditData ?>)"
                                title="Modifier cette fiche"
                                style="background:#f1f5f9;color:#64748b;border:1.5px solid #e2e8f0;border-radius:8px;
                                       padding:3px 9px;font-size:.72rem;cursor:pointer;transition:.15s;line-height:1.4;"
                                onmouseover="this.style.background='#ede9fe';this.style.color='#7c3aed';this.style.borderColor='#c4b5fd'"
                                onmouseout="this.style.background='#f1f5f9';this.style.color='#64748b';this.style.borderColor='#e2e8f0'">
                            <i class="bi bi-pencil-fill"></i>
                        </button>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Chips constantes -->
                <?php
                $chips = [];
                if ($temp_f > 0) $chips[] = ['🌡️', number_format($temp_f,1).'°C',    $temp_f>=38.5?'#ef4444':($temp_f>=38?'#f59e0b':'#0d9488')];
                if ($sys_f > 0)  $chips[] = ['💉', $sys_f.'/'.($dia_f?:'--').' mmHg', $sys_f>180||$sys_f<80?'#ef4444':($sys_f>140?'#f59e0b':'#3b82f6')];
                if ($fc_f > 0)   $chips[] = ['❤️', $fc_f.' bpm',                       $fc_f>120||$fc_f<40?'#ef4444':($fc_f>100?'#f59e0b':'#a855f7')];
                if ($spo2_f > 0) $chips[] = ['🫁', $spo2_f.' %',                       $spo2_f<90?'#ef4444':($spo2_f<94?'#f59e0b':'#22c55e')];
                if ($fr_f > 0)   $chips[] = ['💨', $fr_f.' /min',                      $fr_f>30||$fr_f<8?'#ef4444':($fr_f>20?'#f59e0b':'#0d9488')];
                if ($gly_f > 0)  $chips[] = ['🩸', number_format($gly_f,2).' g/L',    $gly_f<0.60||$gly_f>2.00?'#ef4444':($gly_f<0.70||$gly_f>1.40?'#f59e0b':'#f59e0b')];
                if ($diu_f > 0)  $chips[] = ['💧', number_format($diu_f).' mL/24h',   $diu_f<100?'#ef4444':($diu_f<400?'#f59e0b':'#0ea5e9')];
                if ($o2_f)       $chips[] = ['😷', $dO2_f>0 ? $dO2_f.' L/min O₂' : 'Sous O₂', '#4f46e5'];
                ?>
                <?php if (!empty($chips)): ?>
                <div style="display:flex;flex-wrap:wrap;gap:5px;margin-bottom:<?= ($hasObs||$hasPlaintes) ? '8px' : '0' ?>;">
                    <?php foreach($chips as [$ico, $val, $col]): ?>
                    <span style="display:inline-flex;align-items:center;gap:4px;padding:3px 9px;border-radius:20px;
                                 background:<?= $col ?>18;border:1px solid <?= $col ?>44;font-size:.7rem;font-weight:700;color:<?= $col ?>;">
                        <?= $ico ?> <?= htmlspecialchars($val) ?>
                    </span>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <!-- Plaintes -->
                <?php if ($hasPlaintes): ?>
                <div style="background:#fffbeb;border-radius:9px;border:1px solid #fde68a;
                            padding:9px 13px;font-size:.82rem;color:#78350f;line-height:1.55;
                            white-space:pre-wrap;margin-bottom:<?= $hasObs ? '7px' : '0' ?>;">
                    <div style="display:flex;align-items:center;gap:5px;margin-bottom:4px;">
                        <i class="bi bi-emoji-frown-fill" style="color:#f59e0b;font-size:.85rem;"></i>
                        <span style="font-size:.65rem;font-weight:800;text-transform:uppercase;letter-spacing:.06em;color:#d97706;">Plaintes du malade</span>
                    </div>
                    <?= htmlspecialchars(trim($fiche['plaintes'])) ?>
                </div>
                <?php endif; ?>

                <!-- Observation -->
                <?php if ($hasObs): ?>
                <div style="background:#fff;border-radius:9px;border:1px solid #e9d5ff;
                            padding:9px 13px;font-size:.82rem;color:#3b0764;line-height:1.55;white-space:pre-wrap;">
                    <div style="display:flex;align-items:center;gap:5px;margin-bottom:4px;">
                        <i class="bi bi-chat-text-fill" style="color:#a78bfa;font-size:.85rem;"></i>
                        <span style="font-size:.65rem;font-weight:800;text-transform:uppercase;letter-spacing:.06em;color:#7c3aed;">Observation infirmière</span>
                    </div>
                    <?= htmlspecialchars(trim($fiche['observations'])) ?>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>

            </div>
            </div><!-- /corps groupe -->
        </div><!-- /fiche-date-group -->
        <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <script>
    /* ── Accordéon groupes de fiches (global) ── */
    function toggleFicheGrp(id) {
        const body = document.getElementById(id);
        const chev = document.getElementById(id + '_chev');
        if (!body) return;
        const isOpen = body.style.maxHeight !== '0px' && body.style.maxHeight !== '';
        if (isOpen) {
            body.style.maxHeight = '0';
            body.style.padding   = '0 10px';
            if (chev) chev.classList.replace('bi-chevron-up', 'bi-chevron-down');
        } else {
            body.style.maxHeight = '6000px';
            body.style.padding   = '10px';
            if (chev) chev.classList.replace('bi-chevron-down', 'bi-chevron-up');
        }
    }

    /* ── Filtre Obs/Plaintes — ouvre les groupes concernés ── */
    function toggleVueFiches(btn) {
        const mode  = btn.dataset.mode;
        const items = document.querySelectorAll('#listeFichesParametres .fiche-param-item');
        const filterOn = (mode === 'obs');

        items.forEach(el => {
            const show = !filterOn || el.classList.contains('has-obs') || el.classList.contains('has-plaintes');
            el.style.display = show ? '' : 'none';
        });

        // Masquer les groupes où toutes les fiches sont cachées
        document.querySelectorAll('#listeFichesParametres .fiche-date-group').forEach(grp => {
            const visible = [...grp.querySelectorAll('.fiche-param-item')].some(el => el.style.display !== 'none');
            grp.style.display = visible ? '' : 'none';
            // Ouvrir le corps du groupe si filtrage actif
            if (filterOn && visible) {
                const bodyId = grp.querySelector('[id^="ficheGrp_"]')?.id;
                if (bodyId) {
                    const b = document.getElementById(bodyId);
                    const c = document.getElementById(bodyId + '_chev');
                    if (b) { b.style.maxHeight = '6000px'; b.style.padding = '10px'; }
                    if (c) c.classList.replace('bi-chevron-down', 'bi-chevron-up');
                }
            }
        });

        btn.dataset.mode = filterOn ? 'all' : 'obs';
        btn.innerHTML = filterOn
            ? '<i class="bi bi-grid-fill me-1"></i>Toutes les fiches'
            : '<i class="bi bi-funnel-fill me-1"></i>Avec observations seulement';
    }

    /* ════════════════════════════════════════════════════════════════
       MODIFICATION FICHE PARAMÈTRES
       ════════════════════════════════════════════════════════════════ */
    function _esc(str) {
        return String(str ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    /* ── Transfusions : toggle détails ── */
    function toggleTrans(btn) {
        const detail = btn.closest('[style*="border:1px solid #fecdd3"]').querySelector('.trans-detail');
        const icon   = btn.querySelector('i');
        if (detail.style.display === 'none') {
            detail.style.display = 'block';
            icon.className = 'bi bi-chevron-up';
            btn.innerHTML  = '<i class="bi bi-chevron-up"></i> Masquer';
        } else {
            detail.style.display = 'none';
            icon.className = 'bi bi-chevron-down';
            btn.innerHTML  = '<i class="bi bi-chevron-down"></i> Détails';
        }
    }

    function ouvrirEditFiche(data) {
        const sousO2 = data.sous_oxygene == 1;
        document.getElementById('editFicheBody').innerHTML = `
            <input type="hidden" id="ef_id" value="${parseInt(data.id)}">
            <div id="editFicheErr" class="alert alert-danger d-none rounded-3 py-2 px-3 mb-3"
                 style="font-size:.82rem"></div>

            <p class="fw-bold text-muted mb-3"
               style="font-size:.72rem;letter-spacing:.08em;text-transform:uppercase;
                      border-bottom:1px solid #f1f5f9;padding-bottom:8px">
                <i class="bi bi-activity me-1 text-primary"></i> Signes Vitaux
            </p>
            <div class="row g-3 mb-4">
                <div class="col-6 col-md-4">
                    <label class="form-label fw-semibold small">Température (°C)</label>
                    <input type="text" inputmode="decimal" class="form-control rounded-3"
                           id="ef_temperature" value="${_esc(data.temperature)}" placeholder="37.0">
                </div>
                <div class="col-6 col-md-4">
                    <label class="form-label fw-semibold small">Pouls (bpm)</label>
                    <input type="number" class="form-control rounded-3"
                           id="ef_frequence_cardiaque" value="${_esc(data.frequence_cardiaque)}" placeholder="80">
                </div>
                <div class="col-6 col-md-4">
                    <label class="form-label fw-semibold small">SpO2 (%)</label>
                    <input type="number" min="50" max="100" class="form-control rounded-3"
                           id="ef_spo2" value="${_esc(data.spo2)}" placeholder="98">
                </div>
                <div class="col-6 col-md-4">
                    <label class="form-label fw-semibold small">TA Systolique (mmHg)</label>
                    <input type="number" class="form-control rounded-3"
                           id="ef_tension_sys" value="${_esc(data.tension_sys)}" placeholder="120">
                </div>
                <div class="col-6 col-md-4">
                    <label class="form-label fw-semibold small">TA Diastolique (mmHg)</label>
                    <input type="number" class="form-control rounded-3"
                           id="ef_tension_dia" value="${_esc(data.tension_dia)}" placeholder="80">
                </div>
                <div class="col-6 col-md-4">
                    <label class="form-label fw-semibold small">Fréquence Resp. (/min)</label>
                    <input type="number" class="form-control rounded-3"
                           id="ef_frequence_respiratoire" value="${_esc(data.frequence_respiratoire)}" placeholder="16">
                </div>
            </div>

            <p class="fw-bold text-muted mb-3"
               style="font-size:.72rem;letter-spacing:.08em;text-transform:uppercase;
                      border-bottom:1px solid #f1f5f9;padding-bottom:8px">
                <i class="bi bi-droplet-fill me-1 text-warning"></i> Biologie au lit
            </p>
            <div class="row g-3 mb-4">
                <div class="col-6 col-md-4">
                    <label class="form-label fw-semibold small">Glycémie capillaire (g/L)</label>
                    <input type="text" inputmode="decimal" class="form-control rounded-3"
                           id="ef_glycemie" value="${_esc(data.glycemie)}" placeholder="0.90">
                </div>
                <div class="col-6 col-md-4">
                    <label class="form-label fw-semibold small">Diurèse (mL/24h)</label>
                    <input type="number" class="form-control rounded-3"
                           id="ef_diurese" value="${_esc(data.diurese)}" placeholder="1500">
                </div>
            </div>

            <p class="fw-bold text-muted mb-3"
               style="font-size:.72rem;letter-spacing:.08em;text-transform:uppercase;
                      border-bottom:1px solid #f1f5f9;padding-bottom:8px">
                <i class="bi bi-wind me-1 text-info"></i> Oxygénothérapie
            </p>
            <div class="row g-3 mb-4 align-items-center">
                <div class="col-auto">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="ef_sous_oxygene"
                               ${sousO2 ? 'checked' : ''}
                               onchange="document.getElementById('ef_debit_wrap').style.display=this.checked?'block':'none'">
                        <label class="form-check-label fw-semibold small" for="ef_sous_oxygene">
                            Patient sous oxygène
                        </label>
                    </div>
                </div>
                <div class="col-6 col-md-3" id="ef_debit_wrap"
                     style="display:${sousO2 ? 'block' : 'none'}">
                    <label class="form-label fw-semibold small">Débit O₂ (L/min)</label>
                    <input type="number" step="0.5" min="0" max="15" class="form-control rounded-3"
                           id="ef_debit_oxygene" value="${_esc(data.debit_oxygene)}" placeholder="2">
                </div>
            </div>

            <p class="fw-bold mb-2"
               style="font-size:.72rem;letter-spacing:.08em;text-transform:uppercase;
                      border-bottom:2px solid #fde68a;padding-bottom:8px;color:#92400e;">
                <i class="bi bi-emoji-frown-fill me-1" style="color:#f59e0b;"></i> Plaintes du malade
            </p>
            <div class="mb-4" style="position:relative;">
                <textarea class="form-control rounded-3" id="ef_plaintes" rows="3"
                          style="border:1.5px solid #fde68a;background:#fffbeb;resize:vertical;font-size:.88rem;"
                          placeholder="Ex : Céphalées, douleurs abdominales, nausées…">${_esc(data.plaintes)}</textarea>
                <div style="position:absolute;bottom:8px;right:10px;font-size:.65rem;color:#d97706;
                            font-weight:700;pointer-events:none;">
                    <i class="bi bi-person-exclamation"></i> Exprimées par le patient
                </div>
            </div>

            <p class="fw-bold text-muted mb-2"
               style="font-size:.72rem;letter-spacing:.08em;text-transform:uppercase;
                      border-bottom:1px solid #f1f5f9;padding-bottom:8px">
                <i class="bi bi-chat-text me-1 text-secondary"></i> Observations infirmières
            </p>
            <textarea class="form-control rounded-3" id="ef_observations" rows="3"
                      placeholder="État général, comportement, remarques cliniques…">${_esc(data.observations)}</textarea>
        `;

        const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('modalEditFiche'));
        const btn   = document.getElementById('btnSaveEditFiche');
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-save-fill me-1"></i> Enregistrer les modifications';
        modal.show();
    }

    async function sauvegarderEditFiche() {
        const btn = document.getElementById('btnSaveEditFiche');
        const err = document.getElementById('editFicheErr');
        const id  = parseInt(document.getElementById('ef_id')?.value || '0');
        if (!id) return;

        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Enregistrement…';
        err.classList.add('d-none');

        const payload = {
            id,
            temperature:            document.getElementById('ef_temperature')?.value            || null,
            frequence_cardiaque:    document.getElementById('ef_frequence_cardiaque')?.value    || null,
            spo2:                   document.getElementById('ef_spo2')?.value                   || null,
            tension_sys:            document.getElementById('ef_tension_sys')?.value            || null,
            tension_dia:            document.getElementById('ef_tension_dia')?.value            || null,
            frequence_respiratoire: document.getElementById('ef_frequence_respiratoire')?.value || null,
            glycemie:               document.getElementById('ef_glycemie')?.value               || null,
            diurese:                document.getElementById('ef_diurese')?.value                || null,
            sous_oxygene:           document.getElementById('ef_sous_oxygene')?.checked ? 1 : 0,
            debit_oxygene:          document.getElementById('ef_debit_oxygene')?.value          || null,
            observations:           document.getElementById('ef_observations')?.value           || '',
            plaintes:               document.getElementById('ef_plaintes')?.value               || '',
        };

        try {
            const resp = await fetch(BASE_URL + 'hospitalisation/update-constantes', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            const result = await resp.json();
            if (result.success) {
                bootstrap.Modal.getInstance(document.getElementById('modalEditFiche'))?.hide();
                location.reload();
            } else {
                err.textContent = result.message || 'Erreur lors de la modification.';
                err.classList.remove('d-none');
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-save-fill me-1"></i> Enregistrer les modifications';
            }
        } catch (e) {
            err.textContent = 'Erreur réseau. Veuillez réessayer.';
            err.classList.remove('d-none');
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-save-fill me-1"></i> Enregistrer les modifications';
        }
    }
    </script>

    <!-- ══ FORMULAIRES REMPLIS ══════════════════════════════════════════ -->
    <?php
    /* Construire un index slug → {icon, color} depuis le catalogue */
    $fcIdx = [];
    foreach ($catalogueFormulaires as $_f) {
        $fcIdx[$_f['slug']] = $_f;
    }

    /* Compter par statut */
    $nbForms   = count($formulaires_remplis);
    $nbSoumis  = count(array_filter($formulaires_remplis, fn($f) => $f['statut'] === 'SOUMIS'));
    $nbSigne   = count(array_filter($formulaires_remplis, fn($f) => $f['statut'] === 'SIGNE'));
    $nbBrouil  = count(array_filter($formulaires_remplis, fn($f) => $f['statut'] === 'BROUILLON'));
    ?>
    <div class="forms-history-section mt-4" id="formsHistorySection">

        <!-- En-tête cliquable -->
        <div class="forms-history-header" onclick="toggleFormsHistory()" style="cursor:pointer;">
            <div class="d-flex align-items-center gap-3">
                <div style="width:40px;height:40px;background:rgba(255,255,255,.15);border-radius:10px;
                            display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <i class="bi bi-folder2-open fs-5"></i>
                </div>
                <div>
                    <div style="font-weight:800;font-size:.95rem;">
                        Formulaires remplis
                        <span class="badge-fh-count"><?= $nbForms ?></span>
                    </div>
                    <div style="font-size:.72rem;opacity:.75;margin-top:1px;">
                        <?php if($nbForms > 0): ?>
                            <?= $nbSigne  ? "<span style='color:#86efac;font-weight:700;'>$nbSigne signés</span> · " : '' ?>
                            <?= $nbSoumis ? "<span style='color:#fcd34d;font-weight:700;'>$nbSoumis soumis</span> · " : '' ?>
                            <?= $nbBrouil ? "<span style='color:#cbd5e1;'>$nbBrouil brouillons</span>" : '' ?>
                        <?php else: ?>
                            Aucun formulaire enregistré pour ce patient
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="d-flex align-items-center gap-2">
                <button type="button"
                        class="ab-btn"
                        style="background:rgba(255,255,255,.15);color:#fff;font-size:.75rem;
                               padding:7px 14px;border:1px solid rgba(255,255,255,.25);"
                        onclick="event.stopPropagation();
                                 document.querySelector('[data-bs-target=\'#modalMesFormulaires\']').click();">
                    <i class="bi bi-plus-circle-fill"></i> Nouveau
                </button>
                <i class="bi bi-chevron-down fh-chevron" id="fhChevron"
                   style="font-size:1.1rem;transition:transform .25s;"></i>
            </div>
        </div>

        <!-- Corps (liste) -->
        <div id="formsHistoryBody" style="display:<?= $nbForms ? 'block' : 'none' ?>;">
            <?php if(empty($formulaires_remplis)): ?>
            <div class="fh-empty">
                <i class="bi bi-folder2-open" style="font-size:2.5rem;color:#cbd5e1;"></i>
                <p class="mt-2 text-muted fw-semibold mb-1">Aucun formulaire rempli</p>
                <p class="text-muted small mb-0">Cliquez sur <strong>Mes Formulaires</strong> dans la barre d'actions pour commencer.</p>
            </div>
            <?php else: ?>

            <!-- Filtres rapides -->
            <div class="fh-filter-bar">
                <button class="fh-filter active" onclick="filterForms('all',this)">
                    Tous <span><?= $nbForms ?></span>
                </button>
                <?php if($nbSigne): ?>
                <button class="fh-filter" onclick="filterForms('SIGNE',this)">
                    <i class="bi bi-patch-check-fill" style="color:#22c55e;"></i> Signés <span><?= $nbSigne ?></span>
                </button>
                <?php endif; ?>
                <?php if($nbSoumis): ?>
                <button class="fh-filter" onclick="filterForms('SOUMIS',this)">
                    <i class="bi bi-send-fill" style="color:#f59e0b;"></i> Soumis <span><?= $nbSoumis ?></span>
                </button>
                <?php endif; ?>
                <?php if($nbBrouil): ?>
                <button class="fh-filter" onclick="filterForms('BROUILLON',this)">
                    <i class="bi bi-pencil-fill" style="color:#94a3b8;"></i> Brouillons <span><?= $nbBrouil ?></span>
                </button>
                <?php endif; ?>
            </div>

            <!-- Liste -->
            <div class="fh-list" id="fhList">
                <?php foreach($formulaires_remplis as $form):
                    $meta   = $fcIdx[$form['type_formulaire']] ?? ['icon'=>'bi-file-earmark','color'=>'#64748b'];
                    $dMod   = $form['date_modification'] ?? $form['date_creation'];
                    $dLabel = $dMod ? date('d/m/Y à H:i', strtotime($dMod)) : date('d/m/Y', strtotime($form['date_creation']));
                    $auteur = trim($form['user_prenom'] . ' ' . $form['user_nom']);
                    $statut = $form['statut'];
                    $statutLabel = match($statut) {
                        'SIGNE'    => ['label'=>'Signé',    'cls'=>'fh-badge-signed',  'icon'=>'bi-patch-check-fill'],
                        'SOUMIS'   => ['label'=>'Soumis',   'cls'=>'fh-badge-sent',    'icon'=>'bi-send-fill'],
                        default    => ['label'=>'Brouillon','cls'=>'fh-badge-draft',   'icon'=>'bi-pencil-fill'],
                    };
                ?>
                <div class="fh-item" data-statut="<?= $statut ?>">
                    <!-- Icône du formulaire -->
                    <div class="fh-form-icon" style="background:<?= $meta['color'] ?>18;color:<?= $meta['color'] ?>;">
                        <i class="bi <?= $meta['icon'] ?>"></i>
                    </div>

                    <!-- Titre + meta -->
                    <div class="fh-form-info">
                        <div class="fh-form-title"><?= htmlspecialchars($form['titre']) ?></div>
                        <div class="fh-form-meta">
                            <i class="bi bi-clock me-1"></i><?= $dLabel ?>
                            &nbsp;·&nbsp;
                            <i class="bi bi-person me-1"></i><?= htmlspecialchars($auteur) ?>
                        </div>
                    </div>

                    <!-- Badge statut -->
                    <span class="fh-badge <?= $statutLabel['cls'] ?>">
                        <i class="bi <?= $statutLabel['icon'] ?> me-1"></i><?= $statutLabel['label'] ?>
                    </span>

                    <!-- Boutons action -->
                    <div class="d-flex gap-2 align-items-center">
                        <?php if ($statut === 'BROUILLON'): ?>
                        <button type="button"
                                class="btn btn-sm btn-outline-danger rounded-pill"
                                style="font-size:.78rem;padding:4px 10px;"
                                onclick="supprimerBrouillon(<?= (int)$form['id'] ?>, this)"
                                title="Supprimer ce brouillon">
                            <i class="bi bi-trash3-fill me-1"></i>Supprimer
                        </button>
                        <?php endif; ?>
                        <a href="<?= BASE_URL ?>formulaire/imprimer/<?= urlencode($form['type_formulaire']) ?>/<?= (int)$form['id'] ?>"
                           target="_blank"
                           class="fh-btn-voir"
                           title="Voir / Imprimer ce formulaire">
                            <i class="bi bi-eye-fill me-1"></i>Voir
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <?php endif; ?>
        </div>
    </div>

</div><!-- /suivi-body -->

<!-- ══ MODAL HÉBERGEMENT EXTERNE ══ -->
<div class="modal fade" id="modalHebergement" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered" style="max-width:480px">
        <div class="modal-content border-0 shadow-lg" style="border-radius:18px;overflow:hidden">
            <div class="modal-header border-0 text-white" style="background:linear-gradient(135deg,#0c4a6e,#0891b2);padding:18px 24px">
                <div class="d-flex align-items-center gap-3">
                    <div style="width:38px;height:38px;background:rgba(255,255,255,.15);border-radius:10px;display:flex;align-items:center;justify-content:center">
                        <i class="bi bi-arrow-left-right fs-5"></i>
                    </div>
                    <div>
                        <h6 class="modal-title fw-bold mb-0">Héberger dans un autre service</h6>
                        <small style="opacity:.75">Le patient reste rattaché à <strong><?= htmlspecialchars($dossier['service_nom'] ?? '') ?></strong></small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white ms-auto" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">

                <!-- Info badge -->
                <div class="d-flex align-items-start gap-2 mb-4 p-3 rounded-3" style="background:#f0f9ff;border:1px solid #bae6fd">
                    <i class="bi bi-info-circle-fill text-info mt-1"></i>
                    <div style="font-size:.82rem;color:#0c4a6e">
                        L'infirmier du service d'hébergement verra le lit comme <strong>occupé</strong> mais n'aura pas accès au dossier du patient.
                    </div>
                </div>

                <!-- Localisation actuelle -->
                <div class="mb-3 p-3 rounded-3" style="background:#f8fafc;border:1px solid #e2e8f0;font-size:.83rem">
                    <div class="fw-semibold text-muted mb-1" style="font-size:.7rem;text-transform:uppercase;letter-spacing:.06em">Localisation actuelle</div>
                    <span id="hbergLocActuelle" class="fw-bold text-dark">—</span>
                </div>

                <!-- Sélecteur service -->
                <div class="mb-3">
                    <label class="form-label fw-semibold" style="font-size:.83rem">Service d'hébergement</label>
                    <select id="hbergService" class="form-select" onchange="hbergChargerLits(this.value)">
                        <option value="">— Choisir un service —</option>
                        <?php foreach ($tous_services as $svc): ?>
                        <option value="<?= (int)$svc['id'] ?>"
                            <?= ((int)$svc['id'] === (int)($dossier['service_appartenance_id'] ?? 0)) ? 'style="font-weight:700"' : '' ?>>
                            <?= htmlspecialchars($svc['nom_service']) ?>
                            <?= ((int)$svc['id'] === (int)($dossier['service_appartenance_id'] ?? 0)) ? ' (votre service)' : '' ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Sélecteur lit (chargé dynamiquement) -->
                <div class="mb-3" id="hbergLitWrap" style="display:none">
                    <label class="form-label fw-semibold" style="font-size:.83rem">Lit disponible</label>
                    <select id="hbergLit" class="form-select">
                        <option value="">— Choisir un lit —</option>
                    </select>
                </div>

                <!-- Message d'erreur / succès -->
                <div id="hbergMsg" class="d-none rounded-3 p-2 px-3 mb-2" style="font-size:.82rem"></div>
            </div>
            <div class="modal-footer border-0 pt-0 px-4 pb-4">
                <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn text-white rounded-pill px-4 fw-bold" id="hbergBtn"
                        style="background:#0891b2;" onclick="hbergSauvegarder()" disabled>
                    <i class="bi bi-check2-circle me-1"></i> Installer
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ══ MODAL MODIFIER FICHE PARAMÈTRES ══ -->
<div class="modal fade" id="modalEditFiche" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius:18px;overflow:hidden">
            <div class="modal-header text-white border-0"
                 style="background:linear-gradient(135deg,#5b21b6,#7c3aed);padding:20px 24px">
                <div class="d-flex align-items-center gap-3">
                    <div style="width:42px;height:42px;background:rgba(255,255,255,.15);border-radius:10px;
                                display:flex;align-items:center;justify-content:center">
                        <i class="bi bi-pencil-fill fs-5"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold mb-0">Modifier la Fiche</h5>
                        <div style="font-size:.75rem;opacity:.8;margin-top:2px;">
                            Paramètres &amp; Observations infirmières
                        </div>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white ms-auto" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body p-4" id="editFicheBody">
                <!-- Rempli dynamiquement par ouvrirEditFiche() -->
            </div>

            <div class="modal-footer bg-light border-0 px-4 pb-4 pt-2">
                <button type="button" class="btn btn-outline-secondary rounded-pill px-4"
                        data-bs-dismiss="modal">Annuler</button>
                <button type="button" id="btnSaveEditFiche"
                        onclick="sauvegarderEditFiche()"
                        class="btn text-white rounded-pill px-4 fw-bold shadow-sm"
                        style="background:linear-gradient(135deg,#5b21b6,#7c3aed);">
                    <i class="bi bi-save-fill me-1"></i> Enregistrer les modifications
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ══ MODAL CONSTANTES ══ -->
<div class="modal fade" id="modalAddConstante" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius:18px;overflow:hidden">
            <div class="modal-header text-white border-0" style="background:linear-gradient(135deg,#1e3a5f,#1e40af);padding:20px 24px">
                <div class="d-flex align-items-center gap-3">
                    <div style="width:42px;height:42px;background:rgba(255,255,255,.15);border-radius:10px;display:flex;align-items:center;justify-content:center">
                        <i class="bi bi-heart-pulse-fill fs-5"></i>
                    </div>
                    <h5 class="modal-title fw-bold mb-0">Fiche de Paramètres</h5>
                </div>
                <button type="button" class="btn-close btn-close-white ms-auto" data-bs-dismiss="modal"></button>
            </div>
            <form id="formConstantes" novalidate>
                <input type="hidden" name="admission_id" value="<?= htmlspecialchars($dossier['id'] ?? '0') ?>">
                <input type="hidden" name="patient_id"   value="<?= htmlspecialchars($dossier['patient_id'] ?? $patient['id'] ?? '0') ?>">
                <div class="modal-body p-4">

                    <!-- Alerte d'erreur AJAX -->
                    <div id="constErreur" class="alert alert-danger d-none rounded-3 py-2 px-3 mb-3" style="font-size:.82rem"></div>

                    <p class="fw-bold text-muted mb-3" style="font-size:.72rem;letter-spacing:.08em;text-transform:uppercase;border-bottom:1px solid #f1f5f9;padding-bottom:8px">
                        <i class="bi bi-activity me-1 text-primary"></i> Signes Vitaux
                    </p>
                    <div class="row g-3 mb-4">
                        <div class="col-6 col-md-4"><label class="form-label fw-semibold small">Température (°C)</label>
                            <input type="text" inputmode="decimal" pattern="[0-9]+([.,][0-9]*)?" class="form-control rounded-3" name="temperature" placeholder="37.0" autocomplete="off"></div>
                        <div class="col-6 col-md-4"><label class="form-label fw-semibold small">Pouls (bpm)</label>
                            <input type="number" min="20" max="300" class="form-control rounded-3" name="frequence_cardiaque" placeholder="80"></div>
                        <div class="col-6 col-md-4"><label class="form-label fw-semibold small">SpO2 (%)</label>
                            <input type="number" min="50" max="100" class="form-control rounded-3" name="spo2" placeholder="98"></div>
                        <div class="col-6 col-md-4"><label class="form-label fw-semibold small">TA Systolique (mmHg)</label>
                            <input type="number" min="50" max="300" class="form-control rounded-3" name="tension_sys" placeholder="120"></div>
                        <div class="col-6 col-md-4"><label class="form-label fw-semibold small">TA Diastolique (mmHg)</label>
                            <input type="number" min="30" max="200" class="form-control rounded-3" name="tension_dia" placeholder="80"></div>
                        <div class="col-6 col-md-4"><label class="form-label fw-semibold small">Fréquence Resp. (/min)</label>
                            <input type="number" min="5" max="60" class="form-control rounded-3" name="frequence_respiratoire" placeholder="16"></div>
                    </div>

                    <p class="fw-bold text-muted mb-3" style="font-size:.72rem;letter-spacing:.08em;text-transform:uppercase;border-bottom:1px solid #f1f5f9;padding-bottom:8px">
                        <i class="bi bi-droplet-fill me-1 text-warning"></i> Biologie au lit
                    </p>
                    <div class="row g-3 mb-4">
                        <div class="col-6 col-md-4"><label class="form-label fw-semibold small">Glycémie capillaire (g/L)</label>
                            <input type="text" inputmode="decimal" pattern="[0-9]+([.,][0-9]*)?" class="form-control rounded-3" name="glycemie" placeholder="0.90" autocomplete="off"></div>
                        <div class="col-6 col-md-4"><label class="form-label fw-semibold small">Diurèse (mL/24h)</label>
                            <input type="number" min="0" max="10000" class="form-control rounded-3" name="diurese" placeholder="1500"></div>
                    </div>

                    <p class="fw-bold text-muted mb-3" style="font-size:.72rem;letter-spacing:.08em;text-transform:uppercase;border-bottom:1px solid #f1f5f9;padding-bottom:8px">
                        <i class="bi bi-wind me-1 text-info"></i> Oxygénothérapie
                    </p>
                    <div class="row g-3 mb-4 align-items-center">
                        <div class="col-auto">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="toggleOxygene" name="sous_oxygene" value="1" onchange="toggleDebitO2(this)">
                                <label class="form-check-label fw-semibold small" for="toggleOxygene">Patient sous oxygène</label>
                            </div>
                        </div>
                        <div class="col-6 col-md-3" id="champDebitO2" style="display:none">
                            <label class="form-label fw-semibold small">Débit O₂ (L/min)</label>
                            <input type="number" step="0.5" min="0" max="15" class="form-control rounded-3" name="debit_oxygene" id="debit_oxygene" placeholder="2">
                        </div>
                    </div>

                    <!-- ══ PLAINTES DU MALADE ══ -->
                    <p class="fw-bold mb-2" style="font-size:.72rem;letter-spacing:.08em;text-transform:uppercase;border-bottom:2px solid #fde68a;padding-bottom:8px;color:#92400e;">
                        <i class="bi bi-emoji-frown-fill me-1" style="color:#f59e0b;"></i> Plaintes du malade
                    </p>
                    <div class="mb-4" style="position:relative;">
                        <textarea class="form-control rounded-3"
                                  name="plaintes"
                                  id="panPlaintes"
                                  rows="3"
                                  placeholder="Ex : Céphalées, douleurs abdominales, nausées, dyspnée…"
                                  style="border:1.5px solid #fde68a;background:#fffbeb;resize:vertical;font-size:.88rem;"></textarea>
                        <div style="position:absolute;bottom:8px;right:10px;font-size:.65rem;color:#d97706;font-weight:700;pointer-events:none;">
                            <i class="bi bi-person-exclamation"></i> Exprimées par le patient
                        </div>
                    </div>

                    <!-- ══ OBSERVATIONS INFIRMIÈRES ══ -->
                    <p class="fw-bold text-muted mb-2" style="font-size:.72rem;letter-spacing:.08em;text-transform:uppercase;border-bottom:1px solid #f1f5f9;padding-bottom:8px">
                        <i class="bi bi-chat-text me-1 text-secondary"></i> Observations infirmières
                    </p>
                    <textarea class="form-control rounded-3" name="observations" id="panObservations" rows="3" placeholder="État général, comportement, remarques cliniques…"></textarea>
                </div>
                <div class="modal-footer bg-light border-0 px-4 pb-4 pt-2">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">Annuler</button>
                    <button type="button" id="btnSaveConstantes" class="btn btn-primary rounded-pill px-4 fw-bold shadow-sm" onclick="saveConstantes()">
                        <i class="bi bi-save-fill me-1"></i> Enregistrer la fiche
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ══ MODAL PLANIFIER SOIN ══ -->
<div class="modal fade" id="modalAddSoin" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered" style="max-width:500px">
        <div class="modal-content border-0 shadow-lg" style="border-radius:18px;overflow:hidden">
            <div class="modal-header text-white border-0" style="background:linear-gradient(135deg,#0c4a6e,#0e7490);padding:20px 24px">
                <div class="d-flex align-items-center gap-3">
                    <div style="width:42px;height:42px;background:rgba(255,255,255,.15);border-radius:10px;display:flex;align-items:center;justify-content:center">
                        <i class="bi bi-calendar-plus-fill fs-5"></i>
                    </div>
                    <h5 class="modal-title fw-bold mb-0">Planifier un Soin</h5>
                </div>
                <button type="button" class="btn-close btn-close-white ms-auto" data-bs-dismiss="modal"></button>
            </div>
            <form action="<?= BASE_URL ?>hospitalisation/add-soin" method="POST" id="formPlanifierSoin">
                <input type="hidden" name="admission_id" value="<?= $dossier['id'] ?>">
                <input type="hidden" name="patient_id"   value="<?= $patient['id'] ?>">
                <div class="modal-body p-4">

                    <!-- Ligne 1 : Type + Date -->
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Type de Soin</label>
                            <select class="form-select rounded-3" name="type_soin" required>
                                <option value="IV">IV — Injection intraveineuse</option>
                                <option value="IM">IM — Injection intramusculaire</option>
                                <option value="SC">SC — Injection sous-cutanée</option>
                                <option value="PO">PO — Voie orale</option>
                                <option value="Pansement">Pansement</option>
                                <option value="Perfusion">Perfusion</option>
                                <option value="Prise de sang">Prise de sang</option>
                                <option value="Surveillance">Surveillance</option>
                                <option value="Autre">Autre</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Date et Heure Prévue</label>
                            <input type="datetime-local" class="form-control rounded-3" name="date_prevue"
                                   value="<?= date('Y-m-d\TH:i') ?>" required>
                        </div>
                    </div>

                    <!-- Description -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Description / Instructions</label>
                        <textarea class="form-control rounded-3" name="description" rows="2" required
                                  placeholder="Ex: Paracétamol 1g, Artésunate 120mg IV..."></textarea>
                    </div>

                    <!-- ══ SECTION CONDITION PARAMÉTRIQUE ══ -->
                    <div class="rounded-3 p-3 mb-1"
                         style="background:#faf5ff;border:1.5px solid #e9d5ff;">
                        <!-- Toggle -->
                        <div class="form-check form-switch mb-0">
                            <input class="form-check-input" type="checkbox" role="switch"
                                   id="toggleConditionParam" name="avec_condition" value="1"
                                   onchange="toggleConditionSection(this.checked)">
                            <label class="form-check-label fw-bold small" for="toggleConditionParam"
                                   style="color:#7c3aed">
                                <i class="bi bi-graph-up-arrow me-1"></i>
                                Conditionner ce soin à l'évolution d'un paramètre vital
                            </label>
                        </div>

                        <!-- Section déroulante (masquée par défaut) -->
                        <div id="sectionConditionParam" style="display:none;margin-top:14px">

                            <!-- Paramètre + Opérateur + Valeur cible -->
                            <div class="row g-2 mb-2">
                                <div class="col-md-5">
                                    <label class="form-label fw-semibold small mb-1">Paramètre à surveiller</label>
                                    <select class="form-select form-select-sm rounded-3" name="parametre_surveille" id="selParametre">
                                        <option value="">— Choisir —</option>
                                        <option value="temperature">🌡️ Température (°C)</option>
                                        <option value="pouls">❤️ Fréquence cardiaque (bpm)</option>
                                        <option value="tension_sys">🩺 TA Systolique (mmHg)</option>
                                        <option value="tension_dia">🩺 TA Diastolique (mmHg)</option>
                                        <option value="saturation_o2">💨 SpO2 (%)</option>
                                        <option value="frequence_respiratoire">🌬️ Fréquence respiratoire (/min)</option>
                                        <option value="glycemie">🩸 Glycémie (g/L)</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-semibold small mb-1">Condition</label>
                                    <select class="form-select form-select-sm rounded-3" name="operateur_condition">
                                        <option value="<=">≤ inférieur ou égal</option>
                                        <option value=">=">≥ supérieur ou égal</option>
                                        <option value="<">&lt; strictement inférieur</option>
                                        <option value=">">&gt; strictement supérieur</option>
                                        <option value="=">=  égal à</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold small mb-1">Valeur cible</label>
                                    <div class="input-group input-group-sm">
                                        <input type="number" step="0.1" class="form-control rounded-3"
                                               name="valeur_cible" id="inputValeurCible"
                                               placeholder="Ex: 38.5">
                                        <span class="input-group-text" id="uniteParam" style="font-size:.72rem">—</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Fréquence de surveillance -->
                            <div class="row g-2 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold small mb-1">
                                        <i class="bi bi-clock-history me-1 text-info"></i>
                                        Fréquence de mesure du paramètre
                                    </label>
                                    <select class="form-select form-select-sm rounded-3" name="frequence_surveillance_h">
                                        <option value="">— Non spécifiée —</option>
                                        <option value="1">Toutes les 1h</option>
                                        <option value="2">Toutes les 2h</option>
                                        <option value="3">Toutes les 3h</option>
                                        <option value="4" selected>Toutes les 4h</option>
                                        <option value="6">Toutes les 6h</option>
                                        <option value="8">Toutes les 8h</option>
                                        <option value="12">Toutes les 12h</option>
                                        <option value="24">1×/jour</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold small mb-1">
                                        <i class="bi bi-arrow-right-circle me-1 text-warning"></i>
                                        Action si valeur atteinte
                                    </label>
                                    <select class="form-select form-select-sm rounded-3" name="action_si_atteint"
                                            id="selActionAtteint" onchange="toggleNouveauTraitement(this.value)">
                                        <option value="STOPPER">🛑 Stopper ce soin</option>
                                        <option value="CHANGER">🔄 Changer le traitement</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Nouveau traitement (si CHANGER) -->
                            <div id="blocNouveauTraitement" style="display:none">
                                <label class="form-label fw-semibold small mb-1">
                                    <i class="bi bi-pencil-square me-1 text-primary"></i>
                                    Nouveau traitement à appliquer
                                </label>
                                <textarea class="form-control form-control-sm rounded-3"
                                          name="nouveau_traitement" rows="2"
                                          placeholder="Ex: Paracétamol 500mg PO / 6h au lieu de 1g…"></textarea>
                            </div>

                            <!-- Résumé de la règle -->
                            <div id="resumeRegle" class="mt-2 rounded-2 px-3 py-2"
                                 style="background:#f3e8ff;font-size:.78rem;color:#6d28d9;display:none">
                                <i class="bi bi-info-circle-fill me-1"></i>
                                <span id="resumeRegleTexte"></span>
                            </div>
                        </div>
                    </div>

                    <!-- Note libre (optionnel) -->
                    <div class="mt-3">
                        <label class="form-label fw-semibold small text-muted">Note / Condition textuelle <span class="text-muted fw-normal">(optionnel)</span></label>
                        <input type="text" class="form-control rounded-3 form-control-sm" name="condition_application"
                               placeholder="Ex: à donner si douleur EVA > 6, si agitation…">
                    </div>
                </div>
                <div class="modal-footer bg-light border-0 px-4 pb-4 pt-2">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn fw-bold rounded-pill px-4 text-white shadow-sm" style="background:#0e7490">
                        <i class="bi bi-calendar-check-fill me-1"></i> Planifier
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ══ MODAL ACTION CONDITION ATTEINTE ══ -->
<div class="modal fade" id="modalConditionAtteinte" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius:18px;overflow:hidden">
            <div class="modal-header border-0 px-4 pt-4 pb-2"
                 style="background:linear-gradient(135deg,#7c3aed,#4f46e5)">
                <div>
                    <h5 class="modal-title fw-bold text-white mb-0">
                        <i class="bi bi-graph-up-arrow me-2"></i>Condition paramétrique atteinte !
                    </h5>
                    <small class="text-white opacity-80" id="mca_resume"></small>
                </div>
            </div>
            <div class="modal-body px-4 pt-3 pb-2">
                <input type="hidden" id="mca_soin_id">
                <input type="hidden" id="mca_action_defaut">
                <input type="hidden" id="mca_nouveau_traitement">

                <!-- Valeur actuelle vs cible -->
                <div class="d-flex gap-3 mb-3">
                    <div class="flex-fill text-center rounded-3 py-2"
                         style="background:#fdf4ff;border:1.5px solid #e9d5ff">
                        <div class="fw-bold fs-4 text-purple" style="color:#7c3aed" id="mca_valeur_actuelle">—</div>
                        <div class="small text-muted" id="mca_label_param">Valeur actuelle</div>
                    </div>
                    <div class="d-flex align-items-center text-muted fs-4 fw-bold">
                        <span id="mca_operateur">—</span>
                    </div>
                    <div class="flex-fill text-center rounded-3 py-2"
                         style="background:#f0fdf4;border:1.5px solid #bbf7d0">
                        <div class="fw-bold fs-4 text-success" id="mca_valeur_cible">—</div>
                        <div class="small text-muted">Valeur cible</div>
                    </div>
                </div>

                <!-- Action prédéfinie -->
                <div class="alert rounded-3 mb-3" id="mca_alert_action"
                     style="background:#fef9c3;border-left:4px solid #facc15;font-size:.85rem">
                    <i class="bi bi-lightbulb-fill me-1 text-warning"></i>
                    <span id="mca_texte_action"></span>
                </div>

                <!-- Nouveau traitement (si CHANGER) -->
                <div id="mca_bloc_nouveau_trait" style="display:none">
                    <label class="form-label fw-semibold small">Nouveau traitement</label>
                    <textarea id="mca_nouveau_trait_input" class="form-control rounded-3" rows="2"
                              placeholder="Confirmez ou modifiez le nouveau traitement…"></textarea>
                </div>

                <!-- Note -->
                <div class="mt-2">
                    <label class="form-label fw-semibold small text-muted">Observation (optionnel)</label>
                    <input type="text" id="mca_note" class="form-control form-control-sm rounded-3"
                           placeholder="Ex: T° redescendue après antipyrétique…">
                </div>
            </div>
            <div class="modal-footer border-0 px-4 pb-4 gap-2 flex-column flex-sm-row">
                <button type="button" class="btn btn-outline-secondary rounded-pill px-4 flex-fill"
                        onclick="mca_continuerSoin()">
                    <i class="bi bi-arrow-clockwise me-1"></i> Continuer le soin (valeur non confirmée)
                </button>
                <button type="button" class="btn fw-bold rounded-pill px-4 flex-fill text-white"
                        style="background:linear-gradient(135deg,#7c3aed,#4f46e5)"
                        onclick="mca_confirmerAction()">
                    <i class="bi bi-check-circle-fill me-1"></i> Appliquer l'action
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ══ SCRIPTS ══ -->
<script>
/* ── Helpers ─────────────────────────────────────────── */
function toggleDebitO2(cb) {
    document.getElementById('champDebitO2').style.display = cb.checked ? 'block' : 'none';
    if (!cb.checked) document.getElementById('debit_oxygene').value = '';
}

/* ── Validation directe (sans condition) ── */
function validerSoin(id) {
    if (!confirm('Confirmer la réalisation de ce soin ?')) return;
    const note = prompt('Observation éventuelle (facultatif) :') || '';
    const fd = new FormData();
    fd.append('soin_id', id);
    fd.append('note', note);
    fetch('<?= BASE_URL ?>hospitalisation/valider-soin', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(d => { if (d.success) location.reload(); });
}

/* ── Validation avec vérification de condition paramétrique ── */
function validerSoinAvecCondition(id, avecCondition) {
    if (!avecCondition) {
        validerSoin(id);
        return;
    }
    // Vérifier la condition via AJAX
    fetch('<?= BASE_URL ?>hospitalisation/verifier-condition/' + id, {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => r.json())
    .then(data => {
        if (!data.success) { alert('Erreur : ' + (data.message||'Inconnue')); return; }

        if (!data.avec_condition) {
            // Pas de condition → validation normale
            validerSoin(id);
            return;
        }

        if (!data.atteinte) {
            // Condition PAS atteinte → inviter à continuer
            const dateM = data.date_mesure
                ? ' (mesuré le ' + new Date(data.date_mesure).toLocaleString('fr-FR',{day:'2-digit',month:'2-digit',hour:'2-digit',minute:'2-digit'}) + ')'
                : ' (aucune mesure récente)';
            const valStr = data.valeur_actuelle !== null
                ? `Valeur actuelle : ${data.valeur_actuelle}${dateM}`
                : `Aucune mesure disponible pour ce paramètre${dateM}`;
            const msg = `⚡ Condition non atteinte\n\n`
                + `Condition : ${data.label_param} ${data.operateur} ${data.valeur_cible}\n`
                + valStr + `\n\nContinuer le soin quand même ?`;
            if (confirm(msg)) validerSoin(id);
            return;
        }

        // Condition ATTEINTE → ouvrir le modal d'action
        document.getElementById('mca_soin_id').value       = id;
        document.getElementById('mca_action_defaut').value  = data.action || 'STOPPER';
        document.getElementById('mca_nouveau_traitement').value = data.nouveau_traitement || '';
        document.getElementById('mca_valeur_actuelle').textContent  = data.valeur_actuelle ?? '—';
        document.getElementById('mca_label_param').textContent      = data.label_param;
        document.getElementById('mca_valeur_cible').textContent     = data.valeur_cible ?? '—';
        document.getElementById('mca_operateur').textContent        = data.operateur ?? '—';
        document.getElementById('mca_note').value = '';
        const dateStr = data.date_mesure
            ? new Date(data.date_mesure).toLocaleString('fr-FR',{day:'2-digit',month:'2-digit',hour:'2-digit',minute:'2-digit'})
            : '?';
        document.getElementById('mca_resume').textContent =
            `${data.label_param} ${data.operateur} ${data.valeur_cible} — mesure du ${dateStr}`;
        const action = data.action || 'STOPPER';
        const txtAction = action === 'STOPPER'
            ? '🛑 Ce soin doit être <strong>stoppé</strong> car la condition est atteinte.'
            : '🔄 Le traitement doit être <strong>changé</strong> car la condition est atteinte.';
        document.getElementById('mca_texte_action').innerHTML = txtAction;
        const blocTrait = document.getElementById('mca_bloc_nouveau_trait');
        if (action === 'CHANGER') {
            blocTrait.style.display = '';
            document.getElementById('mca_nouveau_trait_input').value = data.nouveau_traitement || '';
        } else {
            blocTrait.style.display = 'none';
        }
        new bootstrap.Modal(document.getElementById('modalConditionAtteinte')).show();
    })
    .catch(err => {
        console.error(err);
        validerSoin(id); // fallback
    });
}

function mca_continuerSoin() {
    // L'infirmier décide de continuer malgré la condition atteinte
    const id = document.getElementById('mca_soin_id').value;
    bootstrap.Modal.getInstance(document.getElementById('modalConditionAtteinte')).hide();
    validerSoin(id);
}

function mca_confirmerAction() {
    const soinId  = document.getElementById('mca_soin_id').value;
    const action  = document.getElementById('mca_action_defaut').value;
    const note    = document.getElementById('mca_note').value;
    const nouveau = document.getElementById('mca_nouveau_trait_input').value;
    const fd = new FormData();
    fd.append('soin_id',           soinId);
    fd.append('action',            action);
    fd.append('note',              note);
    fd.append('nouveau_traitement',nouveau);
    fetch('<?= BASE_URL ?>hospitalisation/appliquer-action-condition', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(d => {
            bootstrap.Modal.getInstance(document.getElementById('modalConditionAtteinte')).hide();
            if (d.success) {
                location.reload();
            } else {
                alert('Erreur : ' + (d.message || 'Inconnue'));
            }
        });
}

/* ── Helpers modal "Planifier un Soin" ── */
const _unites = {
    temperature:'°C', pouls:'bpm', tension_sys:'mmHg',
    tension_dia:'mmHg', saturation_o2:'%', frequence_respiratoire:'/min', glycemie:'g/L'
};
function toggleConditionSection(show) {
    document.getElementById('sectionConditionParam').style.display = show ? '' : 'none';
    if (!show) {
        document.getElementById('resumeRegle').style.display = 'none';
    }
}
function toggleNouveauTraitement(val) {
    document.getElementById('blocNouveauTraitement').style.display = (val === 'CHANGER') ? '' : 'none';
    majResumeRegle();
}
document.getElementById('selParametre')?.addEventListener('change', function() {
    document.getElementById('uniteParam').textContent = _unites[this.value] || '—';
    majResumeRegle();
});
document.querySelectorAll('#sectionConditionParam select, #inputValeurCible').forEach(el => {
    el.addEventListener('change', majResumeRegle);
    el.addEventListener('input',  majResumeRegle);
});
function majResumeRegle() {
    const param  = document.getElementById('selParametre')?.value;
    const op     = document.querySelector('[name="operateur_condition"]')?.value;
    const val    = document.getElementById('inputValeurCible')?.value;
    const action = document.getElementById('selActionAtteint')?.value;
    const freq   = document.querySelector('[name="frequence_surveillance_h"]')?.value;
    const resume = document.getElementById('resumeRegle');
    if (!param || !val) { resume.style.display='none'; return; }
    const unite  = _unites[param] || '';
    const labels = { temperature:'T°', pouls:'FC', tension_sys:'TAS', tension_dia:'TAD',
                     saturation_o2:'SpO2', frequence_respiratoire:'FR', glycemie:'Glycémie' };
    const actTxt = action === 'STOPPER' ? 'stopper ce soin' : 'changer le traitement';
    const freqTxt = freq ? ` — mesure toutes les ${freq}h` : '';
    document.getElementById('resumeRegleTexte').textContent =
        `Si ${labels[param]||param} ${op} ${val} ${unite}${freqTxt} → ${actTxt}`;
    resume.style.display = '';
}

// Suppression logique avec motif
let _suprSoinId = null;
function ouvrirModalSuppression(id, desc) {
    _suprSoinId = id;
    document.getElementById('suivi_supprimerSoinDesc').textContent = desc || '(sans description)';
    document.getElementById('suivi_motifSuppressionInput').value = '';
    document.getElementById('suivi_errMotifSuppression').style.display = 'none';
    new bootstrap.Modal(document.getElementById('suivi_modalMotifSuppression')).show();
}

function suivi_confirmerSuppression() {
    const motif = document.getElementById('suivi_motifSuppressionInput').value.trim();
    if (!motif) {
        document.getElementById('suivi_errMotifSuppression').style.display = 'block';
        return;
    }
    const btn = document.getElementById('suivi_btnConfirmerSuppression');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Suppression…';

    fetch('<?= BASE_URL ?>hospitalisation/supprimer-soin/' + _suprSoinId, {
        method : 'POST',
        headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        body   : JSON.stringify({ motif: motif })
    })
    .then(r => r.json())
    .then(d => {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-trash3 me-1"></i> Confirmer la suppression';
        if (d.success) {
            bootstrap.Modal.getInstance(document.getElementById('suivi_modalMotifSuppression'))?.hide();
            // Griser la ligne sans la retirer
            const item = document.getElementById('soin-item-' + _suprSoinId);
            if (item) {
                item.style.opacity = '0.48';
                item.style.background = '#f8f9fa';
                item.style.pointerEvents = 'none';
                const descEl = item.querySelector('.soin-desc');
                if (descEl) {
                    descEl.style.textDecoration = 'line-through';
                    descEl.style.color = '#9ca3af';
                    descEl.insertAdjacentHTML('beforeend',
                        '<div style="text-decoration:none;font-size:.72rem;color:#ef4444;margin-top:3px;font-style:italic;">' +
                        '<i class="bi bi-trash3-fill me-1"></i>Supprimé — ' + motif + '</div>');
                }
                const statusEl = item.querySelector('.soin-status');
                if (statusEl) statusEl.innerHTML = '<span style="font-size:.68rem;background:#dc3545;color:#fff;padding:2px 8px;border-radius:20px;font-weight:700;">SUPPRIMÉ</span>';
                const supprBtn = item.querySelector('.soin-suppr-btn');
                if (supprBtn) supprBtn.remove();
            }
            showToast('Soin marqué comme supprimé.', 'success');
        } else {
            showToast(d.message || 'Erreur lors de la suppression.', 'error');
        }
    })
    .catch(() => {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-trash3 me-1"></i> Confirmer la suppression';
        showToast('Erreur réseau.', 'error');
    });
}

/* ── Toast ──────────────────────────────────────────── */
function showToast(msg, type = 'success') {
    const area = document.getElementById('suivi-toast-area');
    const t = document.createElement('div');
    const isOk = type === 'success';
    t.style.cssText = `background:${isOk ? '#f0fdf4' : '#fef2f2'};border:1.5px solid ${isOk ? '#86efac' : '#fca5a5'};
        border-radius:14px;padding:14px 18px;display:flex;align-items:center;gap:12px;
        box-shadow:0 4px 20px rgba(0,0,0,.1);animation:slideIn .25s ease;max-width:340px`;
    t.innerHTML = `
        <div style="width:34px;height:34px;border-radius:10px;background:${isOk ? '#dcfce7' : '#fee2e2'};
             display:flex;align-items:center;justify-content:center;flex-shrink:0">
            <i class="bi bi-${isOk ? 'check-circle-fill' : 'x-circle-fill'}"
               style="color:${isOk ? '#16a34a' : '#dc2626'};font-size:1rem"></i>
        </div>
        <div style="flex:1;font-size:.82rem;font-weight:600;color:${isOk ? '#15803d' : '#b91c1c'}">${msg}</div>
        <button onclick="this.closest('div').remove()" style="background:none;border:none;cursor:pointer;
            color:#94a3b8;font-size:1rem;padding:0">×</button>`;
    area.appendChild(t);
    setTimeout(() => t.style.animation = 'fadeOut .35s ease forwards', 4500);
    setTimeout(() => t.remove(), 4900);
}

/* ── AJAX : Enregistrer les constantes ───────────────── */
async function saveConstantes() {
    const form    = document.getElementById('formConstantes');
    const btn     = document.getElementById('btnSaveConstantes');
    const errDiv  = document.getElementById('constErreur');
    const pid     = form.querySelector('[name="patient_id"]').value;

    // Validation minimale : au moins un champ vital rempli
    const fields  = ['temperature','frequence_cardiaque','spo2','tension_sys','tension_dia'];
    const hasData = fields.some(n => { const f = form.querySelector('[name="'+n+'"]'); return f && f.value.trim() !== ''; });
    if (!hasData) {
        errDiv.textContent = 'Veuillez remplir au moins un paramètre vital.';
        errDiv.classList.remove('d-none');
        return;
    }
    errDiv.classList.add('d-none');

    // Construire le payload JSON (normalise virgule→point pour les décimales en locale française)
    const payload = { patient_id: pid };
    new FormData(form).forEach((v, k) => {
        if (v === '') return;
        // Remplacer la virgule par un point pour les champs décimaux (ex: "37,5" → "37.5")
        if (['temperature','glycemie','debit_oxygene'].includes(k)) {
            v = String(v).replace(',', '.');
        }
        if (v !== '') payload[k] = v;
    });

    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Enregistrement…';

    try {
        const resp = await fetch('<?= BASE_URL ?>hospitalisation/add-constantes', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        const data = await resp.json();

        if (data.success) {
            bootstrap.Modal.getInstance(document.getElementById('modalAddConstante')).hide();
            form.reset();
            document.getElementById('champDebitO2').style.display = 'none';
            showToast('Constantes enregistrées avec succès ✓');

            // Masquer la bannière "aucune mesure"
            const banner = document.getElementById('no-vitals-banner');
            if (banner) banner.style.display = 'none';

            // Rafraîchir les cartes vitales depuis le serveur
            refreshVitals();
        } else {
            errDiv.textContent = data.message || 'Erreur lors de l\'enregistrement.';
            errDiv.classList.remove('d-none');
        }
    } catch (err) {
        errDiv.textContent = 'Erreur réseau : ' + err.message;
        errDiv.classList.remove('d-none');
    }

    btn.disabled = false;
    btn.innerHTML = '<i class="bi bi-save-fill me-1"></i> Enregistrer la fiche';
}

/* ── Rafraîchissement des cartes vitales ─────────────── */
function refreshVitals() {
    fetch(window.location.href + (window.location.href.includes('?') ? '&' : '?') + '_ajax_refresh=' + Date.now())
        .then(r => r.text())
        .then(html => {
            const doc     = new DOMParser().parseFromString(html, 'text/html');
            const newRow  = doc.getElementById('vitals-row');
            const curRow  = document.getElementById('vitals-row');
            if (newRow && curRow) curRow.innerHTML = newRow.innerHTML;
        })
        .catch(() => {/* rafraîchissement différé — on ignore */});
}

// Rafraîchissement silencieux toutes les 90s
setInterval(refreshVitals, 90000);

/* ── Toast depuis URL param (?success=constantes_ajoutees) ── */
(function () {
    const p = new URLSearchParams(window.location.search);
    if (p.get('success') === 'constantes_ajoutees') showToast('Constantes enregistrées avec succès ✓');
    if (p.get('error')) showToast(decodeURIComponent(p.get('error')), 'error');
})();

/* ── Animation toast ─────────────────────────────────── */
const __style = document.createElement('style');
__style.textContent = `
    @keyframes slideIn { from { opacity:0; transform:translateX(40px); } to { opacity:1; transform:translateX(0); } }
    @keyframes fadeOut { from { opacity:1; } to { opacity:0; transform:translateX(40px); } }
`;
document.head.appendChild(__style);

/* ── Graphiques principaux ──────────────────────────── */
(function() {
    const hist = <?= json_encode($constantes ?? []) ?>;
    if (!hist.length) return; // Pas de données — pas de graphique

    const labels  = hist.map(d => {
        const dt = new Date(d.date_mesure);
        return dt.toLocaleDateString('fr-FR', { day:'2-digit', month:'2-digit' }) + ' ' +
               dt.toLocaleTimeString('fr-FR', { hour:'2-digit', minute:'2-digit' });
    });
    const defOpts = (yLabel) => ({
        responsive: true,
        plugins: { legend: { display: false } },
        scales: {
            x: { ticks: { font: { size: 9 }, maxTicksLimit: 8 }, grid: { color: '#f1f5f9' } },
            y: { ticks: { font: { size: 9 } }, grid: { color: '#f1f5f9' }, title: { display: !!yLabel, text: yLabel, font: { size: 9 } } }
        }
    });

    // Température
    new Chart(document.getElementById('chartTemp'), {
        type: 'line',
        data: { labels, datasets: [{
            label: 'T° (°C)',
            data: hist.map(d => d.temperature),
            borderColor: '#ef4444', backgroundColor: 'rgba(239,68,68,.08)',
            tension: 0.4, fill: true, pointRadius: 4, pointHoverRadius: 6,
            pointBackgroundColor: '#ef4444'
        }]},
        options: { ...defOpts('°C'), scales: { ...defOpts('°C').scales, y: { ...defOpts('°C').scales.y, min: 35, max: 42 } } }
    });

    // Tension
    new Chart(document.getElementById('chartTension'), {
        type: 'line',
        data: { labels, datasets: [
            { label: 'Systolique',  data: hist.map(d => d.tension_sys), borderColor: '#0ea5e9', backgroundColor: 'rgba(14,165,233,.06)', tension: 0.4, fill: true,  pointRadius: 4, pointBackgroundColor: '#0ea5e9' },
            { label: 'Diastolique', data: hist.map(d => d.tension_dia), borderColor: '#3b82f6', tension: 0.4, pointRadius: 4, pointBackgroundColor: '#3b82f6' }
        ]},
        options: { ...defOpts('mmHg'), plugins: { legend: { display: true, labels: { font: { size: 10 } } } } }
    });
})();

/* ── Formulaires history : toggle + filtre ───────────────────── */
(function () {
    let _fhOpen = <?= $nbForms > 0 ? 'true' : 'false' ?>;

    window.toggleFormsHistory = function () {
        _fhOpen = !_fhOpen;
        document.getElementById('formsHistoryBody').style.display = _fhOpen ? 'block' : 'none';
        document.getElementById('fhChevron').style.transform = _fhOpen ? 'rotate(180deg)' : 'rotate(0)';
    };

    /* Initialiser l'état du chevron */
    if (_fhOpen) document.getElementById('fhChevron').style.transform = 'rotate(180deg)';

    window.filterForms = function (statut, btn) {
        /* Activer le bon bouton */
        document.querySelectorAll('.fh-filter').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        /* Filtrer les items */
        document.querySelectorAll('#fhList .fh-item').forEach(item => {
            const show = statut === 'all' || item.dataset.statut === statut;
            item.style.display = show ? 'flex' : 'none';
        });
    };
})();

/* ── Suppression d'un brouillon ──────────────────────────────── */
window.supprimerBrouillon = function(formulaire_id, btn) {
    if (!confirm('Supprimer ce brouillon définitivement ?')) return;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';

    fetch('<?= BASE_URL ?>formulaire/supprimer-brouillon', {
        method : 'POST',
        headers: { 'Content-Type': 'application/json' },
        body   : JSON.stringify({ formulaire_id })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            /* Retirer la ligne de la liste */
            const item = btn.closest('.fh-item');
            if (item) {
                item.style.transition = 'opacity .3s';
                item.style.opacity = '0';
                setTimeout(() => item.remove(), 300);
            }
        } else {
            alert('Erreur : ' + (data.message || 'Suppression impossible'));
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-trash3-fill me-1"></i>Supprimer';
        }
    })
    .catch(() => {
        alert('Erreur réseau');
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-trash3-fill me-1"></i>Supprimer';
    });
};

/* ── Accordéon réévaluations médicales (global) ─────────────── */
function toggleRevGrp(id) {
    const body = document.getElementById(id);
    const chev = document.getElementById(id + '_chev');
    if (!body) return;
    const isOpen = body.style.maxHeight !== '0px' && body.style.maxHeight !== '';
    if (isOpen) {
        body.style.maxHeight = '0';
        body.style.padding   = '0 16px';
        if (chev) { chev.classList.replace('bi-chevron-up', 'bi-chevron-down'); }
    } else {
        body.style.maxHeight = '3000px';
        body.style.padding   = '16px';
        if (chev) { chev.classList.replace('bi-chevron-down', 'bi-chevron-up'); }
    }
}

/* ══ HÉBERGEMENT EXTERNE ══════════════════════════════════════ */
const HBERG_PATIENT_ID = <?= (int)($patient['id'] ?? 0) ?>;
const HBERG_BASE       = '<?= BASE_URL ?>';

function ouvrirModalHebergement() {
    // Afficher la localisation actuelle
    const locEl = document.getElementById('hbergLocActuelle');
    <?php
    $locActuelle = '';
    if (!empty($dossier['service_hebergement_nom'])) {
        $locActuelle = $dossier['service_hebergement_nom'];
        if (!empty($dossier['chambre_hebergement'])) $locActuelle .= ' · Ch. ' . $dossier['chambre_hebergement'];
        if (!empty($dossier['lit_numero'])) $locActuelle .= ' · LIT ' . $dossier['lit_numero'];
    } elseif (!empty($dossier['lit_numero'])) {
        $locActuelle = ($dossier['service_nom'] ?? '') . ' · ';
        if (!empty($dossier['chambre_hebergement'])) $locActuelle .= 'Ch. ' . $dossier['chambre_hebergement'] . ' · ';
        $locActuelle .= 'LIT ' . $dossier['lit_numero'];
    } else {
        $locActuelle = 'Pas encore installé dans un lit';
    }
    ?>
    if (locEl) locEl.textContent = <?= json_encode($locActuelle) ?>;

    // Reset
    document.getElementById('hbergService').value = '';
    document.getElementById('hbergLitWrap').style.display = 'none';
    document.getElementById('hbergLit').innerHTML = '<option value="">— Choisir un lit —</option>';
    document.getElementById('hbergBtn').disabled = true;
    const msg = document.getElementById('hbergMsg');
    msg.className = 'd-none'; msg.textContent = '';

    new bootstrap.Modal(document.getElementById('modalHebergement')).show();
}

async function hbergChargerLits(serviceId) {
    const litWrap = document.getElementById('hbergLitWrap');
    const litSel  = document.getElementById('hbergLit');
    const btn     = document.getElementById('hbergBtn');

    litWrap.style.display = 'none';
    litSel.innerHTML = '<option value="">Chargement…</option>';
    btn.disabled = true;

    if (!serviceId) return;

    try {
        const resp = await fetch(HBERG_BASE + 'hospitalisation/get-lits-disponibles?service_id=' + serviceId);
        const lits = await resp.json();
        if (!lits.length) {
            litSel.innerHTML = '<option value="">Aucun lit disponible dans ce service</option>';
            litWrap.style.display = '';
            return;
        }
        let html = '<option value="">— Choisir un lit —</option>';
        let lastChambre = '';
        lits.forEach(l => {
            if (l.nom_chambre !== lastChambre) {
                if (lastChambre) html += '</optgroup>';
                html += `<optgroup label="Chambre ${l.nom_chambre}">`;
                lastChambre = l.nom_chambre;
            }
            html += `<option value="${l.id}">${l.nom_lit}</option>`;
        });
        if (lastChambre) html += '</optgroup>';
        litSel.innerHTML = html;
        litWrap.style.display = '';
        litSel.onchange = () => { btn.disabled = !litSel.value; };
    } catch(e) {
        litSel.innerHTML = '<option value="">Erreur de chargement</option>';
        litWrap.style.display = '';
    }
}

async function hbergSauvegarder() {
    const litId = document.getElementById('hbergLit').value;
    const btn   = document.getElementById('hbergBtn');
    const msg   = document.getElementById('hbergMsg');
    if (!litId) return;

    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Installation…';

    try {
        const fd = new FormData();
        fd.append('patient_id', HBERG_PATIENT_ID);
        fd.append('lit_id',     litId);
        const resp = await fetch(HBERG_BASE + 'hospitalisation/installer-lit-externe', { method: 'POST', body: fd });
        const data = await resp.json();

        if (data.success) {
            msg.className = 'rounded-3 p-2 px-3 mb-2';
            msg.style.cssText = 'background:#f0fdf4;color:#15803d;border:1px solid #bbf7d0;font-size:.82rem;';
            msg.textContent = '✓ ' + data.message + ' — ' + data.nom_service + ' · ' + data.nom_chambre + ' · LIT ' + data.nom_lit;
            msg.classList.remove('d-none');
            setTimeout(() => location.reload(), 1500);
        } else {
            msg.className = 'rounded-3 p-2 px-3 mb-2';
            msg.style.cssText = 'background:#fef2f2;color:#dc2626;border:1px solid #fecdd3;font-size:.82rem;';
            msg.textContent = '✗ ' + data.message;
            msg.classList.remove('d-none');
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-check2-circle me-1"></i> Installer';
        }
    } catch(e) {
        msg.textContent = 'Erreur réseau.'; msg.classList.remove('d-none');
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-check2-circle me-1"></i> Installer';
    }
}
</script>

<!-- ═══════════════════════════════════════════════════════════════
     MODAL — MOTIF DE SUPPRESSION D'UN SOIN (suivi)
═══════════════════════════════════════════════════════════════ -->
<div class="modal fade" id="suivi_modalMotifSuppression" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width:480px;">
        <div class="modal-content border-0 shadow-lg" style="border-radius:16px;overflow:hidden;">
            <div class="modal-header border-0 p-4" style="background:linear-gradient(135deg,#7f1d1d,#dc2626);">
                <h5 class="modal-title fw-bold text-white mb-0">
                    <i class="bi bi-trash3-fill me-2"></i>Supprimer ce soin
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="rounded-3 p-3 mb-4" style="background:#fff5f5;border:1px solid #fecaca;">
                    <div class="small text-muted mb-1 fw-semibold text-uppercase" style="font-size:.7rem;letter-spacing:.6px;">Soin concerné</div>
                    <div class="fw-bold text-danger" id="suivi_supprimerSoinDesc">—</div>
                </div>
                <label class="form-label fw-semibold small mb-1">
                    Motif de suppression <span class="text-danger">*</span>
                </label>
                <textarea id="suivi_motifSuppressionInput"
                          class="form-control rounded-3"
                          rows="3"
                          placeholder="Ex : Erreur de saisie, soin non nécessaire, doublon…"
                          style="resize:vertical;font-size:.92rem;"
                          oninput="document.getElementById('suivi_errMotifSuppression').style.display='none'"></textarea>
                <div id="suivi_errMotifSuppression" class="text-danger small mt-1" style="display:none;">
                    <i class="bi bi-exclamation-circle me-1"></i>Le motif est obligatoire.
                </div>
                <div class="alert alert-warning border-0 rounded-3 py-2 px-3 small d-flex gap-2 align-items-start mt-3 mb-0">
                    <i class="bi bi-info-circle-fill text-warning flex-shrink-0 mt-1"></i>
                    <span>Le soin sera <strong>grisé</strong> avec votre nom, la date et le motif. Il restera visible à titre de traçabilité.</span>
                </div>
            </div>
            <div class="modal-footer border-0 px-4 pb-4 pt-0 gap-2">
                <button class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">Annuler</button>
                <button id="suivi_btnConfirmerSuppression"
                        class="btn text-white fw-bold rounded-pill px-4"
                        style="background:#dc2626;"
                        onclick="suivi_confirmerSuppression()">
                    <i class="bi bi-trash3 me-1"></i> Confirmer la suppression
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ══════════════════════════════════════════════════════════════
     MODAL — METTRE À JOUR LA PLANIFICATION
     Permet d'ajouter des soins/médicaments avec une date de début libre
══════════════════════════════════════════════════════════════ -->
<div class="modal fade" id="modalUpdatePlanif" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg" style="border-radius:20px;">

            <!-- En-tête -->
            <div class="modal-header border-0 p-0" style="flex-shrink:0;">
                <div class="w-100 px-4 py-3 d-flex align-items-center gap-3"
                     style="background:linear-gradient(135deg,#0f172a,#0d9488);border-radius:20px 20px 0 0;">
                    <div style="width:46px;height:46px;border-radius:13px;background:rgba(255,255,255,.15);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <i class="bi bi-pencil-square text-white fs-4"></i>
                    </div>
                    <div>
                        <h5 class="fw-bold text-white mb-0">Mettre à jour la planification</h5>
                        <small style="color:rgba(255,255,255,.6);">
                            Ajouter des soins pour aujourd'hui ou des dates futures —
                            <strong class="text-white"><?= htmlspecialchars(($patient['prenom'] ?? '').' '.($patient['nom'] ?? '')) ?></strong>
                        </small>
                    </div>
                    <button type="button" class="btn-close btn-close-white ms-auto" data-bs-dismiss="modal"></button>
                </div>
            </div>

            <!-- Formulaire -->
            <form id="formUpdatePlanif"
                  action="<?= BASE_URL ?>hospitalisation/save-plan"
                  method="POST"
                  style="display:flex;flex-direction:column;flex:1 1 auto;overflow:hidden;min-height:0;">
                <input type="hidden" name="patient_id" value="<?= (int)($patient['id'] ?? $dossier['patient_id'] ?? 0) ?>">
                <input type="hidden" name="redirect_to" value="hospitalisation/suivi/<?= (int)($patient['id'] ?? $dossier['patient_id'] ?? 0) ?>">

                <div class="modal-body p-4" style="overflow-y:auto;flex:1 1 auto;">

                    <!-- Légende voies -->
                    <div class="d-flex flex-wrap gap-2 mb-3">
                        <?php foreach([
                            'IV'          => ['#0e7490','Intraveineux'],
                            'PO'          => ['#15803d','Per Os'],
                            'IM'          => ['#7c3aed','Intramusculaire'],
                            'SC'          => ['#0369a1','Sous-cutané'],
                            'Pansement'   => ['#be123c','Pansement'],
                            'SURVEILLANCE'=> ['#475569','Surveillance'],
                        ] as $code => [$color, $label]): ?>
                        <span style="font-size:.68rem;font-weight:700;padding:2px 9px;border-radius:20px;background:<?= $color ?>18;color:<?= $color ?>;border:1px solid <?= $color ?>44;">
                            <?= $code ?> — <?= $label ?>
                        </span>
                        <?php endforeach; ?>
                    </div>

                    <!-- Zone des lignes -->
                    <div id="upPlanifRows" class="d-flex flex-column gap-2"></div>

                    <!-- Bouton ajouter une ligne -->
                    <button type="button"
                            class="btn btn-sm mt-3 w-100 fw-bold"
                            style="border:2px dashed #0d9488;color:#0d9488;background:transparent;border-radius:12px;padding:10px;"
                            onclick="upPlanifAddRow()">
                        <i class="bi bi-plus-circle me-2"></i>Ajouter une ligne de soin / médicament
                    </button>
                </div>

                <div class="modal-footer border-0 px-4 pb-4 pt-0 gap-2">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">Annuler</button>
                    <button type="button"
                            class="btn fw-bold rounded-pill px-5 shadow-sm"
                            style="background:linear-gradient(135deg,#0d9488,#0f766e);color:#fff;"
                            onclick="upPlanifSubmit()">
                        <i class="bi bi-check2-circle me-2"></i>Enregistrer la planification
                    </button>
                </div>
            </form>

        </div>
    </div>
</div>

<style>
/* ── Modal Update Planif ── */
.uprow {
    display: grid;
    grid-template-columns: 130px 72px 90px 1fr 68px 36px;
    gap: 6px; align-items: center;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 9px 12px;
    transition: border-color .15s;
}
.uprow:hover { border-color: #0d9488; }
.uprow-label {
    font-size: .62rem; font-weight: 700; color: #94a3b8;
    text-transform: uppercase; letter-spacing: .4px;
    display: block; margin-bottom: 2px;
}
.uprow input, .uprow select {
    font-size: .82rem; font-weight: 600; border-radius: 8px;
    border: 1px solid #e2e8f0; padding: 5px 8px;
    width: 100%; background: #fff; color: #1e293b;
}
.uprow input:focus, .uprow select:focus {
    outline: none; border-color: #0d9488; box-shadow: 0 0 0 2px rgba(13,148,136,.15);
}
.uprow-del {
    width: 32px; height: 32px; border: none; border-radius: 8px;
    background: #fff1f2; color: #dc2626; cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    font-size: .85rem; transition: all .15s; flex-shrink: 0;
}
.uprow-del:hover { background: #fecaca; }
@media (max-width: 640px) {
    .uprow { grid-template-columns: 1fr 1fr; }
}
</style>

<script>
(function () {
    let _upKey = 0;

    /* ── Ajouter une ligne ── */
    window.upPlanifAddRow = function(defaults) {
        defaults = defaults || {};
        const key = ++_upKey;
        const today = new Date().toISOString().split('T')[0];

        const voies = [
            ['IV','IV — Intraveineux','#0e7490'],
            ['PO','PO — Per Os','#15803d'],
            ['IM','IM — Intramusculaire','#7c3aed'],
            ['SC','SC — Sous-cutané','#0369a1'],
            ['Pansement','Pansement','#be123c'],
            ['SURVEILLANCE','Surveillance','#475569'],
        ];

        const durees = [1,2,3,4,5,6,7,10,14];

        let voieOptions = voies.map(([v, lbl]) =>
            `<option value="${v}" ${(defaults.type||'IV') === v ? 'selected':''}>` + lbl + `</option>`
        ).join('');

        let dureeOptions = durees.map(d =>
            `<option value="${d}" ${parseInt(defaults.duree||1) === d ? 'selected':''}>` +
            (d === 1 ? '1 jour' : d + ' jours') + `</option>`
        ).join('');

        const row = document.createElement('div');
        row.className = 'uprow';
        row.dataset.key = key;
        row.innerHTML = `
            <div>
                <span class="uprow-label">Date début</span>
                <input type="date" class="up-date" min="${today}"
                       value="${defaults.date || today}">
            </div>
            <div>
                <span class="uprow-label">Heure</span>
                <input type="time" class="up-heure"
                       value="${defaults.heure || '08:00'}">
            </div>
            <div>
                <span class="uprow-label">Voie</span>
                <select class="up-type">${voieOptions}</select>
            </div>
            <div>
                <span class="uprow-label">Médicament / Description</span>
                <input type="text" class="up-desc" placeholder="ex: Ceftriaxone 2g/24h…"
                       value="${defaults.desc || ''}">
            </div>
            <div>
                <span class="uprow-label">Durée</span>
                <select class="up-duree">${dureeOptions}</select>
            </div>
            <button type="button" class="uprow-del" onclick="upPlanifDelRow(${key})" title="Supprimer">
                <i class="bi bi-trash3-fill"></i>
            </button>`;

        document.getElementById('upPlanifRows').appendChild(row);
        row.querySelector('.up-desc').focus();
    };

    /* ── Supprimer une ligne ── */
    window.upPlanifDelRow = function(key) {
        const row = document.querySelector('[data-key="' + key + '"]');
        if (row) row.remove();
    };

    /* ── Soumettre (construit les champs hidden avant POST) ── */
    window.upPlanifSubmit = function() {
        const form = document.getElementById('formUpdatePlanif');

        // Nettoyer anciens champs dynamiques
        form.querySelectorAll('.up-dynamic').forEach(e => e.remove());

        const rows = document.querySelectorAll('#upPlanifRows .uprow');
        if (rows.length === 0) {
            alert('Ajoutez au moins une ligne avant d\'enregistrer.');
            return;
        }

        const catIdx = {};
        let valid = 0;

        rows.forEach(function(row) {
            const date  = row.querySelector('.up-date').value.trim();
            const heure = row.querySelector('.up-heure').value.trim();
            const type  = row.querySelector('.up-type').value;
            const desc  = row.querySelector('.up-desc').value.trim();
            const duree = row.querySelector('.up-duree').value;

            if (!date || !heure || !desc) return; // ignorer lignes incomplètes

            if (!catIdx[type]) catIdx[type] = 0;
            const idx = catIdx[type]++;

            const addHidden = function(name, val) {
                const inp = document.createElement('input');
                inp.type = 'hidden';
                inp.name = name;
                inp.value = val;
                inp.className = 'up-dynamic';
                form.appendChild(inp);
            };

            addHidden('soins[' + type + '][heure]['  + idx + ']', heure);
            addHidden('soins[' + type + '][desc]['   + idx + ']', desc);
            addHidden('soins[' + type + '][duree]['  + idx + ']', duree);
            addHidden('soins[' + type + '][date_debut][' + idx + ']', date);
            valid++;
        });

        if (valid === 0) {
            alert('Veuillez renseigner au moins une ligne complète (date + heure + description).');
            return;
        }

        form.submit();
    };

    /* ── Réinitialiser le modal à l'ouverture ── */
    document.addEventListener('DOMContentLoaded', function() {
        const modal = document.getElementById('modalUpdatePlanif');
        if (!modal) return;
        modal.addEventListener('show.bs.modal', function() {
            document.getElementById('upPlanifRows').innerHTML = '';
            _upKey = 0;
            upPlanifAddRow(); // une ligne par défaut
        });
    });
})();
</script>

<!-- ══════════════════════════════════════════════════════════
     MODAL — FICHE DE SUIVI DES PANSEMENTS
══════════════════════════════════════════════════════════ -->
<div class="modal fade" id="modalFichePansement" tabindex="-1" aria-hidden="true">
<div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
<div class="modal-content border-0 shadow-lg" style="border-radius:18px;overflow:hidden;">

    <!-- Header -->
    <div class="modal-header border-0 text-white"
         style="background:linear-gradient(135deg,#4c1d95,#7c3aed);padding:18px 24px;">
        <div>
            <h5 class="modal-title fw-bold mb-0">
                <i class="bi bi-bandaid-fill me-2"></i>Fiche de Suivi des Pansements
            </h5>
            <small style="opacity:.75;">
                <?= htmlspecialchars(strtoupper($patient['nom'] ?? '').' '.($patient['prenom'] ?? '')) ?>
                &nbsp;·&nbsp;<?= htmlspecialchars($patient['dossier_numero'] ?? '') ?>
                &nbsp;·&nbsp;<?= htmlspecialchars($_SESSION['nom_service'] ?? '') ?>
            </small>
        </div>
        <button type="button" class="btn-close btn-close-white ms-auto" data-bs-dismiss="modal"></button>
    </div>

    <div class="modal-body p-0">

        <!-- Tabs -->
        <ul class="nav nav-tabs px-4 pt-3" id="panTabs" style="border-bottom:1px solid #e2e8f0;">
            <li class="nav-item">
                <button class="nav-link active fw-bold" data-bs-toggle="tab" data-bs-target="#panTabIdent"
                        style="font-size:.83rem;">
                    <i class="bi bi-person-vcard me-1"></i>Identification de la plaie
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link fw-bold" data-bs-toggle="tab" data-bs-target="#panTabSuivi"
                        id="panTabSuiviBtn" style="font-size:.83rem;">
                    <i class="bi bi-table me-1"></i>Suivi &amp; Nouvelle entrée
                    <span class="badge rounded-pill ms-1" id="panNbEntrees"
                          style="background:#7c3aed;color:#fff;font-size:.65rem;">…</span>
                </button>
            </li>
        </ul>

        <div class="tab-content p-4">

            <!-- ─── TAB 1 : Identification ─── -->
            <div class="tab-pane fade show active" id="panTabIdent">
                <div class="row g-4">
                    <!-- Type de plaie -->
                    <div class="col-md-4">
                        <div class="pan-section-title"><i class="bi bi-tag"></i>Type de plaie</div>
                        <div class="pan-check-grid" style="grid-template-columns:1fr;">
                            <?php foreach(['Ulcère','Abcès','Escarre','Brûlure','Plaie traumatique','Plaie opératoire','Autre'] as $tp): ?>
                            <label class="pan-check-item" onclick="panToggle(this)">
                                <input type="checkbox" name="type_plaie[]" value="<?= htmlspecialchars($tp) ?>">
                                <?= htmlspecialchars($tp) ?>
                            </label>
                            <?php endforeach; ?>
                        </div>
                        <div class="mt-2">
                            <label style="font-size:.73rem;color:#64748b;">Âge de la plaie</label>
                            <input type="text" id="panAgePlaie" class="pan-input mt-1"
                                   placeholder="Ex : 3 semaines, 5 jours…">
                        </div>
                    </div>
                    <!-- Terrain -->
                    <div class="col-md-4">
                        <div class="pan-section-title"><i class="bi bi-person-heart"></i>Terrain</div>
                        <div class="pan-check-grid" style="grid-template-columns:1fr;">
                            <?php foreach(['Diabète','Obésité','LAV +','Dénutrition','Cancer','Insuffisance veineuse','AOMI','Dépigmentation volontaire','Pied diabétique'] as $tr): ?>
                            <label class="pan-check-item" onclick="panToggle(this)">
                                <input type="checkbox" name="terrain[]" value="<?= htmlspecialchars($tr) ?>">
                                <?= htmlspecialchars($tr) ?>
                            </label>
                            <?php endforeach; ?>
                            <div>
                                <input type="text" id="panTerrainAutre" class="pan-input mt-1"
                                       placeholder="Autre terrain…">
                            </div>
                        </div>
                    </div>
                    <!-- Localisation -->
                    <div class="col-md-4">
                        <div class="pan-section-title"><i class="bi bi-geo-alt"></i>Localisation de la plaie</div>
                        <!-- Schéma corporel SVG simplifié -->
                        <div style="text-align:center; margin-bottom:10px;">
                            <svg viewBox="0 0 120 200" width="110" style="opacity:.55;">
                                <!-- tête --><ellipse cx="60" cy="20" rx="16" ry="18" fill="none" stroke="#7c3aed" stroke-width="1.5"/>
                                <!-- cou --><rect x="53" y="36" width="14" height="10" fill="none" stroke="#7c3aed" stroke-width="1.5"/>
                                <!-- tronc --><rect x="32" y="46" width="56" height="60" rx="8" fill="none" stroke="#7c3aed" stroke-width="1.5"/>
                                <!-- bras G --><rect x="10" y="46" width="20" height="55" rx="8" fill="none" stroke="#7c3aed" stroke-width="1.5"/>
                                <!-- bras D --><rect x="90" y="46" width="20" height="55" rx="8" fill="none" stroke="#7c3aed" stroke-width="1.5"/>
                                <!-- jambe G --><rect x="33" y="108" width="22" height="70" rx="8" fill="none" stroke="#7c3aed" stroke-width="1.5"/>
                                <!-- jambe D --><rect x="65" y="108" width="22" height="70" rx="8" fill="none" stroke="#7c3aed" stroke-width="1.5"/>
                            </svg>
                        </div>
                        <textarea id="panLocalisation" class="pan-input" rows="3"
                                  placeholder="Décrivez la localisation : ex. face interne cheville gauche, talon droit, sacrum…"></textarea>
                    </div>
                </div>

                <div class="d-flex justify-content-end mt-3">
                    <button type="button" class="btn fw-bold rounded-pill px-4"
                            style="background:#7c3aed;color:#fff;font-size:.85rem;"
                            onclick="panSauvegarderIdentification()">
                        <i class="bi bi-floppy-fill me-1"></i>Enregistrer l'identification
                    </button>
                </div>
                <div id="panIdentMsg" style="display:none;" class="alert alert-success rounded-3 mt-2 py-2 small"></div>
            </div>

            <!-- ─── TAB 2 : Suivi + Nouvelle entrée ─── -->
            <div class="tab-pane fade" id="panTabSuivi">

                <!-- ── Récapitulatif de l'identification ────────────────── -->
                <div id="panFicheResume" style="display:none;background:#fff;border:1.5px solid #ddd6fe;border-radius:14px;padding:14px 18px;margin-bottom:18px;">
                    <div class="d-flex align-items-center justify-content-between mb-2 flex-wrap gap-2">
                        <span class="fw-bold" style="font-size:.85rem;color:#4c1d95;">
                            <i class="bi bi-clipboard2-pulse-fill me-1"></i>Identification de la plaie
                        </span>
                        <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill py-0 px-3"
                                style="font-size:.72rem;"
                                onclick="document.querySelector('[data-bs-target=\'#panTabIdent\']').click()">
                            <i class="bi bi-pencil-square me-1"></i>Modifier
                        </button>
                    </div>
                    <div class="row g-2" style="font-size:.78rem;">
                        <div class="col-sm-6">
                            <span class="text-muted d-block mb-1" style="font-size:.7rem;text-transform:uppercase;letter-spacing:.04em;">Type(s) de plaie</span>
                            <div id="panResumeTypes" class="d-flex flex-wrap gap-1">—</div>
                        </div>
                        <div class="col-sm-6">
                            <span class="text-muted d-block mb-1" style="font-size:.7rem;text-transform:uppercase;letter-spacing:.04em;">Terrain / Comorbidités</span>
                            <div id="panResumeTerrain" class="d-flex flex-wrap gap-1">—</div>
                        </div>
                        <div class="col-sm-8 mt-1">
                            <span class="text-muted d-block mb-1" style="font-size:.7rem;text-transform:uppercase;letter-spacing:.04em;">Localisation</span>
                            <span id="panResumeLocalisation" class="text-dark">—</span>
                        </div>
                        <div class="col-sm-4 mt-1">
                            <span class="text-muted d-block mb-1" style="font-size:.7rem;text-transform:uppercase;letter-spacing:.04em;">Âge de la plaie</span>
                            <span id="panResumeAge" class="text-dark">—</span>
                        </div>
                        <div class="col-12 mt-1">
                            <span class="text-muted" id="panResumeDate" style="font-size:.68rem;"></span>
                        </div>
                    </div>
                </div>
                <!-- Si aucune identification -->
                <div id="panFicheResumeVide" style="display:none;background:#faf5ff;border:1.5px dashed #c4b5fd;border-radius:14px;padding:12px 18px;margin-bottom:18px;font-size:.8rem;color:#7c3aed;">
                    <i class="bi bi-info-circle me-1"></i>
                    Aucune identification enregistrée.
                    <button type="button" class="btn btn-sm fw-bold ms-2 rounded-pill py-0 px-3"
                            style="font-size:.72rem;background:#7c3aed;color:#fff;"
                            onclick="document.querySelector('[data-bs-target=\'#panTabIdent\']').click()">
                        <i class="bi bi-plus-circle me-1"></i>Créer la fiche
                    </button>
                </div>

                <!-- Formulaire nouvelle entrée -->
                <div style="background:#f5f3ff;border:1.5px solid #c4b5fd;border-radius:14px;padding:18px 20px;margin-bottom:24px;">
                    <div class="fw-bold mb-3" style="font-size:.9rem;color:#4c1d95;">
                        <i class="bi bi-plus-circle-fill me-1"></i>Nouvelle entrée de suivi
                    </div>
                    <div class="row g-3">
                        <!-- Date -->
                        <div class="col-md-2">
                            <label class="pan-section-title mb-1"><i class="bi bi-calendar3"></i>Date</label>
                            <input type="date" id="panDate" class="pan-input"
                                   value="<?= date('Y-m-d') ?>" max="<?= date('Y-m-d') ?>">
                        </div>
                        <!-- Évolution de la plaie -->
                        <div class="col-12">
                            <div class="pan-section-title"><i class="bi bi-bandaid"></i>Évolution de la plaie</div>
                            <div class="d-flex flex-wrap gap-2">
                                <label class="pan-check-item evo-necrose" onclick="panToggle(this)" title="Tissu nécrotique noir/marron">
                                    <input type="checkbox" name="evo[]" value="Nécrose">⬛ Nécrose
                                </label>
                                <label class="pan-check-item evo-fibrine" onclick="panToggle(this)" title="Dépôt fibrineux jaunâtre">
                                    <input type="checkbox" name="evo[]" value="Fibrine">🟡 Fibrine
                                </label>
                                <label class="pan-check-item evo-bourgeon" onclick="panToggle(this)" title="Tissu de granulation rouge">
                                    <input type="checkbox" name="evo[]" value="Bourgeonnement">🔴 Bourgeonnement
                                </label>
                                <label class="pan-check-item evo-epidem" onclick="panToggle(this)" title="Nouvelle épithélialisation">
                                    <input type="checkbox" name="evo[]" value="Épidémisation">🩷 Épidémisation
                                </label>
                                <label class="pan-check-item evo-infection" onclick="panToggle(this)" title="Signes d'infection locale">
                                    <input type="checkbox" name="evo[]" value="Infection">🟢 Infection
                                </label>
                            </div>
                        </div>
                        <!-- Surface -->
                        <div class="col-md-3">
                            <div class="pan-section-title"><i class="bi bi-arrows-fullscreen"></i>Surface (cm²)</div>
                            <div class="d-flex align-items-center gap-2">
                                <input type="number" step="0.1" min="0" id="panLongueur" class="pan-input"
                                       placeholder="Long." style="max-width:80px;">
                                <span style="font-weight:700;color:#7c3aed;">×</span>
                                <input type="number" step="0.1" min="0" id="panLargeur" class="pan-input"
                                       placeholder="Larg." style="max-width:80px;">
                                <span id="panSurface" style="font-size:.8rem;color:#64748b;white-space:nowrap;">= — cm²</span>
                            </div>
                        </div>
                        <!-- Exsudat -->
                        <div class="col-md-3">
                            <div class="pan-section-title"><i class="bi bi-droplet-half"></i>Exsudat</div>
                            <div class="pan-radio-group">
                                <?php foreach(['0'=>'Aucun (0)','+'=>'Faible (+)','++'=>'Modéré (++)','+++'=>'Important (+++)'] as $v=>$l): ?>
                                <label class="pan-radio-item">
                                    <input type="radio" name="panExsudat" value="<?= $v ?>"> <?= $l ?>
                                </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <!-- Douleur -->
                        <div class="col-md-3">
                            <div class="pan-section-title"><i class="bi bi-emoji-frown"></i>Douleur</div>
                            <div class="pan-radio-group">
                                <?php foreach(['0'=>'Aucune (0)','+'=>'Faible (+)','++'=>'Modérée (++)','+++'=>'Intense (+++)'] as $v=>$l): ?>
                                <label class="pan-radio-item">
                                    <input type="radio" name="panDouleur" value="<?= $v ?>"> <?= $l ?>
                                </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <!-- Actions menées -->
                        <div class="col-12">
                            <div class="pan-section-title"><i class="bi bi-tools"></i>Action(s) menée(s)</div>
                            <div class="pan-check-grid">
                                <?php foreach(['Sucre','Sérum salé','Bétadine','Eau oxygénée','Aloe Vera','Eosine aqueuse','Nécrosectomie','Lavage pulsatile'] as $act): ?>
                                <label class="pan-check-item" onclick="panToggle(this)">
                                    <input type="checkbox" name="actions[]" value="<?= htmlspecialchars($act) ?>">
                                    <?= htmlspecialchars($act) ?>
                                </label>
                                <?php endforeach; ?>
                                <div>
                                    <input type="text" id="panActionAutre" class="pan-input"
                                           placeholder="Autre action…">
                                </div>
                            </div>
                        </div>
                        <!-- Signes associés -->
                        <div class="col-md-3">
                            <div class="pan-section-title"><i class="bi bi-exclamation-triangle"></i>Signes associés</div>
                            <div class="d-flex gap-2">
                                <label class="pan-check-item" onclick="panToggle(this)" style="flex:1;">
                                    <input type="checkbox" name="signes[]" value="Prurit">Prurit
                                </label>
                                <label class="pan-check-item" onclick="panToggle(this)" style="flex:1;">
                                    <input type="checkbox" name="signes[]" value="Œdème">Œdème
                                </label>
                            </div>
                        </div>
                        <!-- Observations -->
                        <div class="col-md-9">
                            <div class="pan-section-title"><i class="bi bi-chat-text"></i>Observations</div>
                            <textarea id="panObsPansement" class="pan-input" rows="2"
                                      placeholder="Observations cliniques, évolution générale…"></textarea>
                        </div>
                    </div>
                    <!-- Photos du soin -->
                    <div class="mt-3">
                        <div class="pan-section-title"><i class="bi bi-camera"></i>Photos du soin</div>
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <label class="btn btn-sm btn-outline-secondary rounded-pill" style="cursor:pointer;">
                                <i class="bi bi-plus-circle me-1"></i>Ajouter des photos
                                <input type="file" id="panPhotoInput" accept="image/*" multiple style="display:none;"
                                       onchange="panAjouterPhotos(this)">
                            </label>
                            <span class="text-muted" style="font-size:.72rem;">JPG, PNG, WEBP · max 8 Mo par photo</span>
                        </div>
                        <div id="panPhotoPreviewGrid" class="d-flex flex-wrap gap-2 mt-2"></div>
                        <input type="hidden" id="panPhotosPaths" value="[]">
                    </div>

                    <div class="d-flex justify-content-end mt-3 gap-2">
                        <button type="button" id="panBtnAnnulerEdit"
                                class="btn btn-outline-secondary rounded-pill d-none"
                                onclick="panResetForm()">
                            <i class="bi bi-x-circle me-1"></i>Annuler
                        </button>
                        <button type="button" class="btn btn-light rounded-pill" onclick="panResetForm()">
                            <i class="bi bi-arrow-counterclockwise me-1"></i>Réinitialiser
                        </button>
                        <button type="button" id="panBtnEnregistrer" class="btn fw-bold rounded-pill px-4"
                                style="background:#7c3aed;color:#fff;"
                                onclick="panEnregistrerEntree()">
                            <i class="bi bi-floppy-fill me-1"></i>Enregistrer l'entrée
                        </button>
                    </div>
                    <div id="panEntreeMsg" style="display:none;" class="alert rounded-3 mt-2 py-2 small"></div>
                </div>

                <!-- Tableau historique -->
                <div class="pan-section-title"><i class="bi bi-clock-history"></i>Historique des entrées</div>
                <div id="panHistWrapper">
                    <div class="text-center py-4 text-muted" id="panHistSpinner">
                        <div class="spinner-border spinner-border-sm me-2" style="color:#7c3aed;"></div>
                        Chargement…
                    </div>
                </div>

            </div><!-- /tab-pane suivi -->
        </div><!-- /tab-content -->
    </div><!-- /modal-body -->
</div>
</div>
</div>

<script>
// ══ FICHE PANSEMENT ═══════════════════════════════════════════════
(function() {
const PAN_PATIENT_ID = <?= (int)($patient['id'] ?? 0) ?>;
const PAN_HOSP_ID    = <?= (int)($dossier['id'] ?? 0) ?>;
const BASE           = '<?= BASE_URL ?>';
let _panEntreesCache = {};   // cache entrées historique
let _panEditId = null;       // null = création, entier = édition

// Toggle checkbox styling
window.panToggle = function(label) {
    const cb = label.querySelector('input[type=checkbox]');
    label.classList.toggle('checked', cb.checked);
};

// Calcul surface
['panLongueur','panLargeur'].forEach(id => {
    document.getElementById(id)?.addEventListener('input', () => {
        const l = parseFloat(document.getElementById('panLongueur').value) || 0;
        const w = parseFloat(document.getElementById('panLargeur').value)  || 0;
        document.getElementById('panSurface').textContent =
            (l && w) ? '= ' + (l * w).toFixed(1) + ' cm²' : '= — cm²';
    });
});

// Collecter les checkboxes cochées d'un groupe
function collectChecked(name) {
    return [...document.querySelectorAll(`#modalFichePansement input[name="${name}"]:checked`)]
        .map(i => i.value);
}
function collectRadio(name) {
    const el = document.querySelector(`#modalFichePansement input[name="${name}"]:checked`);
    return el ? el.value : '';
}

// ─── Sauvegarder l'identification ─────────────────────────────
window.panSauvegarderIdentification = async function() {
    const types     = collectChecked('type_plaie[]');
    const terrains  = collectChecked('terrain[]');
    const terrAutre = document.getElementById('panTerrainAutre').value.trim();
    if (terrAutre) terrains.push(terrAutre);
    const data = {
        patient_id:   PAN_PATIENT_ID,
        hosp_id:      PAN_HOSP_ID,
        type_plaie:   JSON.stringify(types),
        terrain:      JSON.stringify(terrains),
        localisation: document.getElementById('panLocalisation').value.trim(),
        age_plaie:    document.getElementById('panAgePlaie').value.trim(),
    };
    const fd = new FormData();
    Object.entries(data).forEach(([k,v]) => fd.append(k, v));
    const res = await fetch(BASE + 'pansement/sauvegarder-identification', { method:'POST', body:fd });
    const json = await res.json();
    const msg = document.getElementById('panIdentMsg');
    msg.className = json.success ? 'alert alert-success rounded-3 mt-2 py-2 small'
                                 : 'alert alert-danger rounded-3 mt-2 py-2 small';
    msg.textContent = json.message || (json.success ? 'Identification enregistrée.' : 'Erreur.');
    msg.style.display = 'block';
    setTimeout(() => { msg.style.display='none'; }, 3000);
};

// ─── Enregistrer une entrée de suivi ──────────────────────────
window.panEnregistrerEntree = async function() {
    const evos    = collectChecked('evo[]');
    const actions = collectChecked('actions[]');
    const actAutre = document.getElementById('panActionAutre').value.trim();
    if (actAutre) actions.push(actAutre);
    const signes = collectChecked('signes[]');
    const data = {
        patient_id:   PAN_PATIENT_ID,
        hosp_id:      PAN_HOSP_ID,
        date_soin:    document.getElementById('panDate').value,
        evolution:    JSON.stringify(evos),
        longueur:     document.getElementById('panLongueur').value || '',
        largeur:      document.getElementById('panLargeur').value  || '',
        exsudat:      collectRadio('panExsudat'),
        douleur:      collectRadio('panDouleur'),
        actions:      JSON.stringify(actions),
        signes:       JSON.stringify(signes),
        observations: document.getElementById('panObsPansement').value.trim(),
        photos:       document.getElementById('panPhotosPaths').value || '[]',
    };
    if (!data.date_soin) {
        showPanMsg('panEntreeMsg', false, 'Veuillez saisir une date.'); return;
    }
    const fd = new FormData();
    Object.entries(data).forEach(([k,v]) => fd.append(k, v));

    let url = BASE + 'pansement/ajouter-entree';
    if (_panEditId) {
        url = BASE + 'pansement/modifier-entree';
        fd.append('id', _panEditId);
    }

    const res  = await fetch(url, { method:'POST', body:fd });
    const json = await res.json();
    showPanMsg('panEntreeMsg', json.success, json.message || (json.success ? (_panEditId ? 'Entrée mise à jour.' : 'Entrée enregistrée.') : 'Erreur.'));
    if (json.success) {
        panResetForm();
        chargerHistorique();
    }
};

function showPanMsg(id, ok, txt) {
    const el = document.getElementById(id);
    el.className = ok ? 'alert alert-success rounded-3 mt-2 py-2 small'
                      : 'alert alert-danger rounded-3 mt-2 py-2 small';
    el.textContent = txt;
    el.style.display = 'block';
    if (ok) setTimeout(() => el.style.display='none', 3000);
}

// ─── Réinitialiser le formulaire ──────────────────────────────
window.panResetForm = function() {
    document.querySelectorAll('#panTabSuivi input[type=checkbox]').forEach(cb => {
        cb.checked = false;
        cb.closest('label')?.classList.remove('checked');
    });
    document.querySelectorAll('#panTabSuivi input[type=radio]').forEach(r => r.checked = false);
    document.getElementById('panLongueur').value = '';
    document.getElementById('panLargeur').value  = '';
    document.getElementById('panSurface').textContent = '= — cm²';
    document.getElementById('panObsPansement').value = '';
    document.getElementById('panActionAutre').value  = '';
    document.getElementById('panDate').value = new Date().toISOString().slice(0,10);
    // Réinitialiser les photos
    _panPhotosEnCours = [];
    document.getElementById('panPhotosPaths').value = '[]';
    document.getElementById('panPhotoPreviewGrid').innerHTML = '';
    // Réinitialiser le mode édition
    _panEditId = null;
    const btn = document.getElementById('panBtnEnregistrer');
    if (btn) { btn.innerHTML = '<i class="bi bi-floppy-fill me-1"></i>Enregistrer l\'entrée'; btn.style.background='#7c3aed'; }
    document.getElementById('panBtnAnnulerEdit')?.classList.add('d-none');
};

// ─── Charger l'historique ─────────────────────────────────────
async function chargerHistorique() {
    const wrap = document.getElementById('panHistWrapper');
    wrap.innerHTML = '<div class="text-center py-4 text-muted"><div class="spinner-border spinner-border-sm me-2" style="color:#7c3aed;"></div>Chargement…</div>';
    try {
        const res  = await fetch(BASE + 'pansement/historique?patient_id=' + PAN_PATIENT_ID);
        const json = await res.json();
        document.getElementById('panNbEntrees').textContent = json.entrees?.length || 0;
        if (!json.entrees?.length) {
            wrap.innerHTML = '<p class="text-muted text-center py-3 small"><i class="bi bi-inbox d-block fs-3 mb-1 opacity-25"></i>Aucune entrée enregistrée.</p>';
            return;
        }
        const evoClasses = { 'Nécrose':'tag-necrose','Fibrine':'tag-fibrine','Bourgeonnement':'tag-bourgeon','Épidémisation':'tag-epidem','Infection':'tag-infection' };
        let html = `<div class="table-responsive"><table class="pan-hist-table">
        <thead><tr>
            <th>Date</th><th>Évolution</th><th>Surface</th>
            <th>Exsudat</th><th>Douleur</th><th>Actions</th>
            <th>Signes</th><th>Observations</th><th>Photos</th><th></th>
        </tr></thead><tbody>`;
        // Stocker toutes les entrées pour pré-remplissage édition
        _panEntreesCache = {};
        json.entrees.forEach(e => { _panEntreesCache[e.id] = e; });

        json.entrees.forEach(e => {
            const evos    = JSON.parse(e.evolution || '[]');
            const actions = JSON.parse(e.actions   || '[]');
            const signes  = JSON.parse(e.signes    || '[]');
            const evoHtml = evos.map(v => `<span class="evo-tag ${evoClasses[v]||'exp-badge'}">${v}</span>`).join(' ');
            const surf    = (e.longueur && e.largeur) ? `${e.longueur}×${e.largeur} = ${(e.longueur*e.largeur).toFixed(1)} cm²` : '—';
            const actHtml = actions.map(a => `<span class="exp-badge">${a}</span>`).join(' ');
            const sigHtml = signes.map(s  => `<span class="badge bg-warning text-dark" style="font-size:.67rem;">${s}</span>`).join(' ');
            const photos = JSON.parse(e.photos || '[]');
            const photoHtml = photos.length
                ? photos.map((p,i) => `<img src="${BASE}${p}" class="pan-thumb"
                      style="width:40px;height:40px;object-fit:cover;border-radius:6px;cursor:pointer;border:2px solid #e2e8f0;"
                      onclick="panOuvrirLightbox(${e.id},${i})" title="Agrandir">`).join('')
                : '<span class="text-muted" style="font-size:.72rem;">—</span>';
            html += `<tr data-entree-id="${e.id}">
                <td><strong>${new Date(e.date_soin).toLocaleDateString('fr-FR')}</strong></td>
                <td>${evoHtml||'—'}</td>
                <td style="white-space:nowrap;">${surf}</td>
                <td><span class="exp-badge">${e.exsudat||'—'}</span></td>
                <td><span class="exp-badge">${e.douleur||'—'}</span></td>
                <td>${actHtml||'—'}</td>
                <td>${sigHtml||'—'}</td>
                <td style="max-width:180px;">${e.observations ? `<span title="${e.observations.replace(/"/g,"'")}">` + e.observations.substring(0,50) + (e.observations.length>50?'…':'') + '</span>' : '—'}</td>
                <td><div class="d-flex gap-1 flex-wrap align-items-center" id="panPhotoCell_${e.id}">${photoHtml}</div></td>
                <td style="white-space:nowrap;">
                    <button class="btn btn-sm btn-outline-primary rounded-pill py-0 px-2 me-1" style="font-size:.7rem;"
                        onclick="panChargerEdition(${e.id})" title="Modifier cette entrée">
                        <i class="bi bi-pencil-fill"></i>
                    </button>
                    <label class="btn btn-sm btn-outline-success rounded-pill py-0 px-2 me-1" style="font-size:.7rem;cursor:pointer;" title="Ajouter une photo">
                        <i class="bi bi-camera-fill"></i>
                        <input type="file" accept="image/*" style="display:none;"
                               onchange="panAjouterPhotoExistante(this,${e.id})">
                    </label>
                    <button class="btn btn-sm btn-outline-danger rounded-pill py-0 px-2" style="font-size:.7rem;"
                        onclick="panSupprimerEntree(${e.id})"><i class="bi bi-trash"></i></button>
                </td>
            </tr>`;
        });
        html += '</tbody></table></div>';
        wrap.innerHTML = html;
    } catch(err) {
        wrap.innerHTML = '<p class="text-danger text-center small">Erreur de chargement.</p>';
    }
}

// ─── Supprimer une entrée ─────────────────────────────────────
window.panSupprimerEntree = async function(id) {
    if (!confirm('Supprimer cette entrée de suivi ?')) return;
    const fd = new FormData(); fd.append('id', id);
    const res  = await fetch(BASE + 'pansement/supprimer-entree', { method:'POST', body:fd });
    const json = await res.json();
    if (json.success) chargerHistorique();
    else alert('Erreur : ' + (json.message || ''));
};

// ─── Upload photos (avant enregistrement de l'entrée) ─────────
let _panPhotosEnCours = []; // chemins déjà uploadés sur le serveur

window.panAjouterPhotos = async function(input) {
    const files = [...input.files];
    input.value = '';
    for (const file of files) {
        if (!file.type.startsWith('image/')) { alert('Fichier non image ignoré : ' + file.name); continue; }
        if (file.size > 8 * 1024 * 1024) { alert('Fichier trop volumineux (max 8 Mo) : ' + file.name); continue; }

        // Prévisualisation locale immédiate avec loader
        const idx     = _panPhotosEnCours.length;
        const tmpId   = 'panThumb_tmp_' + idx + '_' + Date.now();
        const preview = document.getElementById('panPhotoPreviewGrid');
        const div     = document.createElement('div');
        div.id        = tmpId;
        div.style     = 'position:relative;width:64px;height:64px;';
        div.innerHTML = `<div style="width:64px;height:64px;background:#f1f5f9;border-radius:8px;display:flex;align-items:center;justify-content:center;">
            <div class="spinner-border spinner-border-sm text-secondary"></div></div>`;
        preview.appendChild(div);

        const fd = new FormData();
        fd.append('photo', file);
        try {
            const res  = await fetch(BASE + 'pansement/upload-photo', { method: 'POST', body: fd });
            const json = await res.json();
            if (!json.success) { div.remove(); alert('Erreur upload : ' + (json.message || '')); continue; }

            _panPhotosEnCours.push(json.path);
            const safeIdx = _panPhotosEnCours.length - 1;
            div.innerHTML = `
                <img src="${BASE}${json.path}" style="width:64px;height:64px;object-fit:cover;border-radius:8px;border:2px solid #7c3aed;">
                <button type="button" onclick="panRetirerPhotoLocale(${safeIdx},'${tmpId}')"
                    style="position:absolute;top:-6px;right:-6px;background:#ef4444;color:#fff;border:none;border-radius:50%;width:20px;height:20px;font-size:12px;line-height:1;cursor:pointer;display:flex;align-items:center;justify-content:center;">✕</button>`;
        } catch(e) {
            div.remove();
            alert('Erreur réseau lors de l\'upload.');
        }
        _panPhotosEnCours = _panPhotosEnCours.filter(Boolean);
        document.getElementById('panPhotosPaths').value = JSON.stringify(_panPhotosEnCours);
    }
};

window.panRetirerPhotoLocale = function(idx, divId) {
    _panPhotosEnCours[idx] = null;
    _panPhotosEnCours = _panPhotosEnCours.filter(Boolean);
    document.getElementById('panPhotosPaths').value = JSON.stringify(_panPhotosEnCours);
    document.getElementById(divId)?.remove();
};

// ─── Charger une entrée dans le formulaire (mode édition) ─────
window.panChargerEdition = function(id) {
    const e = _panEntreesCache[id];
    if (!e) return;

    // Aller sur l'onglet Suivi et scroller vers le formulaire
    document.getElementById('panTabSuiviBtn')?.click();
    setTimeout(() => document.getElementById('panDate')?.scrollIntoView({ behavior:'smooth', block:'center' }), 150);

    // Date
    document.getElementById('panDate').value = e.date_soin || '';

    // Remettre à zéro puis cocher les valeurs
    document.querySelectorAll('#panTabSuivi input[type=checkbox]').forEach(cb => {
        cb.checked = false; cb.closest('label')?.classList.remove('checked');
    });
    document.querySelectorAll('#panTabSuivi input[type=radio]').forEach(r => r.checked = false);

    const evos    = JSON.parse(e.evolution || '[]');
    const actions = JSON.parse(e.actions   || '[]');
    const signes  = JSON.parse(e.signes    || '[]');

    evos.forEach(v => {
        const cb = document.querySelector(`#panTabSuivi input[name="evo[]"][value="${v}"]`);
        if (cb) { cb.checked = true; cb.closest('label')?.classList.add('checked'); }
    });
    actions.forEach(v => {
        const cb = document.querySelector(`#panTabSuivi input[name="actions[]"][value="${v}"]`);
        if (cb) { cb.checked = true; cb.closest('label')?.classList.add('checked'); }
        else document.getElementById('panActionAutre').value = v;
    });
    signes.forEach(v => {
        const cb = document.querySelector(`#panTabSuivi input[name="signes[]"][value="${v}"]`);
        if (cb) { cb.checked = true; cb.closest('label')?.classList.add('checked'); }
    });

    // Surface
    document.getElementById('panLongueur').value = e.longueur || '';
    document.getElementById('panLargeur').value  = e.largeur  || '';
    const l = parseFloat(e.longueur) || 0, w = parseFloat(e.largeur) || 0;
    document.getElementById('panSurface').textContent = (l && w) ? '= ' + (l*w).toFixed(1) + ' cm²' : '= — cm²';

    // Exsudat / Douleur (radio)
    if (e.exsudat) { const r = document.querySelector(`#panTabSuivi input[name="panExsudat"][value="${e.exsudat}"]`); if(r){r.checked=true;} }
    if (e.douleur) { const r = document.querySelector(`#panTabSuivi input[name="panDouleur"][value="${e.douleur}"]`);  if(r){r.checked=true;} }

    // Observations
    document.getElementById('panObsPansement').value = e.observations || '';

    // Réinitialiser photos locales (l'édition ne modifie pas les photos)
    _panPhotosEnCours = [];
    document.getElementById('panPhotosPaths').value = '[]';
    document.getElementById('panPhotoPreviewGrid').innerHTML = '';

    // Passer en mode édition
    _panEditId = id;
    const btn = document.getElementById('panBtnEnregistrer');
    if (btn) { btn.innerHTML = '<i class="bi bi-pencil-fill me-1"></i>Mettre à jour'; btn.style.background='#0d9488'; }
    document.getElementById('panBtnAnnulerEdit')?.classList.remove('d-none');
};

// ─── Ajouter une photo à une entrée existante ─────────────────
window.panAjouterPhotoExistante = async function(input, entreeId) {
    const file = input.files[0];
    input.value = '';
    if (!file) return;
    if (!file.type.startsWith('image/')) { alert('Fichier non image.'); return; }
    if (file.size > 8 * 1024 * 1024) { alert('Fichier trop volumineux (max 8 Mo).'); return; }

    const cell = document.getElementById('panPhotoCell_' + entreeId);
    // Ajouter un spinner dans la cellule
    const tmpSpan = document.createElement('span');
    tmpSpan.innerHTML = '<div class="spinner-border spinner-border-sm text-success" style="width:20px;height:20px;"></div>';
    cell?.appendChild(tmpSpan);

    try {
        const fd = new FormData();
        fd.append('photo', file);
        fd.append('entree_id', entreeId);
        const res  = await fetch(BASE + 'pansement/ajouter-photo-entree', { method: 'POST', body: fd });
        const json = await res.json();
        tmpSpan.remove();
        if (!json.success) { alert('Erreur : ' + (json.message || '')); return; }

        // Ajouter la miniature dans la cellule
        if (cell) {
            // Retirer le "—" s'il existe
            cell.querySelectorAll('span.text-muted').forEach(s => s.remove());
            const newIdx = (cell.querySelectorAll('img.pan-thumb').length);
            const img = document.createElement('img');
            img.src = BASE + json.path;
            img.className = 'pan-thumb';
            img.style.cssText = 'width:40px;height:40px;object-fit:cover;border-radius:6px;cursor:pointer;border:2px solid #e2e8f0;';
            img.title = 'Agrandir';
            img.onclick = () => panOuvrirLightbox(entreeId, newIdx);
            cell.insertBefore(img, cell.lastElementChild); // avant le label caméra
            // Mettre à jour le cache
            if (_panEntreesCache[entreeId]) {
                const ph = JSON.parse(_panEntreesCache[entreeId].photos || '[]');
                ph.push(json.path);
                _panEntreesCache[entreeId].photos = JSON.stringify(ph);
            }
        }
    } catch(err) {
        tmpSpan.remove();
        alert('Erreur réseau.');
    }
};

// ─── Lightbox pour visualiser les photos de l'historique ──────
let _lbPhotos = [], _lbIdx = 0;

window.panOuvrirLightbox = function(entreeId, photoIdx) {
    // Récupérer les photos de la ligne depuis le DOM déjà rendu
    const imgs = [...document.querySelectorAll(`#panHistWrapper img.pan-thumb`)].filter(img => {
        const tr = img.closest('tr');
        const btn = tr?.querySelector('button[onclick*="panSupprimerEntree(' + entreeId + ')"]');
        return !!btn;
    });
    _lbPhotos = imgs.map(i => i.src);
    _lbIdx    = photoIdx;
    panLbAfficher();
    document.getElementById('panLightbox').style.display = 'flex';
};

function panLbAfficher() {
    document.getElementById('panLbImg').src = _lbPhotos[_lbIdx] || '';
    document.getElementById('panLbCounter').textContent = (_lbIdx + 1) + ' / ' + _lbPhotos.length;
    document.getElementById('panLbPrev').style.display = _lbPhotos.length > 1 ? '' : 'none';
    document.getElementById('panLbNext').style.display = _lbPhotos.length > 1 ? '' : 'none';
}
window.panLbPrev = function() { _lbIdx = (_lbIdx - 1 + _lbPhotos.length) % _lbPhotos.length; panLbAfficher(); };
window.panLbNext = function() { _lbIdx = (_lbIdx + 1) % _lbPhotos.length; panLbAfficher(); };
window.panLbFermer = function() { document.getElementById('panLightbox').style.display = 'none'; };

// Fermer la lightbox avec Échap
document.addEventListener('keydown', e => {
    if (e.key === 'Escape') panLbFermer();
    if (e.key === 'ArrowLeft') { if (document.getElementById('panLightbox').style.display !== 'none') panLbPrev(); }
    if (e.key === 'ArrowRight') { if (document.getElementById('panLightbox').style.display !== 'none') panLbNext(); }
});

// ─── Charger l'identification existante ───────────────────────
async function chargerIdentification() {
    try {
        const res  = await fetch(BASE + 'pansement/get-identification?patient_id=' + PAN_PATIENT_ID);
        const json = await res.json();

        // ── Résumé dans l'onglet Suivi ──────────────────────────────
        const resumeCard  = document.getElementById('panFicheResume');
        const resumeVide  = document.getElementById('panFicheResumeVide');

        if (!json.fiche) {
            resumeCard.style.display = 'none';
            resumeVide.style.display = 'block';
            return;
        }

        const f = json.fiche;

        // Afficher le résumé
        resumeVide.style.display = 'none';
        resumeCard.style.display = 'block';

        // Types de plaie
        const types = JSON.parse(f.type_plaie || '[]');
        document.getElementById('panResumeTypes').innerHTML = types.length
            ? types.map(t => `<span style="background:#ede9fe;color:#4c1d95;padding:2px 9px;border-radius:20px;font-size:.72rem;font-weight:600;">${t}</span>`).join('')
            : '<span class="text-muted">—</span>';

        // Terrain
        const terrains = JSON.parse(f.terrain || '[]');
        document.getElementById('panResumeTerrain').innerHTML = terrains.length
            ? terrains.map(t => `<span style="background:#f0fdf4;color:#166534;padding:2px 9px;border-radius:20px;font-size:.72rem;font-weight:600;border:1px solid #bbf7d0;">${t}</span>`).join('')
            : '<span class="text-muted">—</span>';

        // Localisation & âge
        document.getElementById('panResumeLocalisation').textContent = f.localisation || '—';
        document.getElementById('panResumeAge').textContent          = f.age_plaie    || '—';

        // Date de mise à jour
        if (f.updated_at) {
            const d = new Date(f.updated_at);
            document.getElementById('panResumeDate').textContent =
                'Mis à jour le ' + d.toLocaleDateString('fr-FR') + ' à ' + d.toLocaleTimeString('fr-FR', {hour:'2-digit',minute:'2-digit'});
        }

        // ── Pré-remplir le formulaire Identification ────────────────
        // Pré-cocher type_plaie
        types.forEach(v => {
            const cb = document.querySelector(`#panTabIdent input[value="${v}"]`);
            if (cb) { cb.checked = true; cb.closest('label')?.classList.add('checked'); }
        });
        // Pré-cocher terrain
        terrains.forEach(v => {
            const cb = document.querySelector(`#panTabIdent input[name="terrain[]"][value="${v}"]`);
            if (cb) { cb.checked = true; cb.closest('label')?.classList.add('checked'); }
            else document.getElementById('panTerrainAutre').value = v;
        });
        if (f.localisation) document.getElementById('panLocalisation').value = f.localisation;
        if (f.age_plaie)    document.getElementById('panAgePlaie').value    = f.age_plaie;

    } catch(e) {}
}

// ─── Rafraîchir le résumé après sauvegarde identification ─────
const _origSauvegarder = window.panSauvegarderIdentification;
window.panSauvegarderIdentification = async function() {
    await _origSauvegarder();
    chargerIdentification();
};

// ─── Ouvrir le modal → charger les données ────────────────────
document.getElementById('modalFichePansement').addEventListener('show.bs.modal', () => {
    chargerIdentification();
    chargerHistorique();
});
// Recharger historique quand on clique sur l'onglet Suivi
document.getElementById('panTabSuiviBtn').addEventListener('click', chargerHistorique);

})();
</script>

<!-- ══ LIGHTBOX PANSEMENT PHOTOS ════════════════════════════════ -->
<div id="panLightbox" onclick="if(event.target===this)panLbFermer()"
     style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.88);z-index:9999;
            align-items:center;justify-content:center;flex-direction:column;gap:12px;">
    <button onclick="panLbFermer()"
            style="position:absolute;top:18px;right:22px;background:none;border:none;color:#fff;font-size:2rem;line-height:1;cursor:pointer;"
            title="Fermer">✕</button>
    <div style="position:relative;max-width:90vw;max-height:80vh;">
        <button onclick="panLbPrev()" id="panLbPrev"
                style="position:absolute;left:-48px;top:50%;transform:translateY(-50%);background:rgba(255,255,255,.15);
                       border:none;color:#fff;font-size:1.8rem;border-radius:50%;width:40px;height:40px;cursor:pointer;">‹</button>
        <img id="panLbImg" src="" alt="Photo pansement"
             style="max-width:90vw;max-height:78vh;border-radius:10px;object-fit:contain;display:block;box-shadow:0 8px 40px rgba(0,0,0,.6);">
        <button onclick="panLbNext()" id="panLbNext"
                style="position:absolute;right:-48px;top:50%;transform:translateY(-50%);background:rgba(255,255,255,.15);
                       border:none;color:#fff;font-size:1.8rem;border-radius:50%;width:40px;height:40px;cursor:pointer;">›</button>
    </div>
    <span id="panLbCounter" style="color:#e2e8f0;font-size:.85rem;"></span>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
