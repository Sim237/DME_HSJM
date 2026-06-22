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

/* ============================================================================
   FICHIER : app/views/urgences/accueil_secretaire.php
   COCKPIT SECRÉTAIRE D'ACCUEIL — SERVICE DES URGENCES (SAU)
============================================================================ */
$pageTitle = 'Accueil Urgences — Secrétariat';
require_once __DIR__ . '/../layouts/header.php';
?>
<style>
/* ═══════════════════════════════════════════════════════════
   RESET & BASE
═══════════════════════════════════════════════════════════ */
body { background: #f1f5f9 !important; }

/* ═══════════════════════════════════════════════════════════
   TOPBAR SAU
═══════════════════════════════════════════════════════════ */
.sau-topbar {
    background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #312e81 100%);
    padding: 0 28px;
    height: 58px;
    display: flex; align-items: center; justify-content: space-between;
    position: sticky; top: 0; z-index: 1060;
    box-shadow: 0 2px 16px rgba(0,0,0,.4);
}
.sau-brand { display:flex; align-items:center; gap:12px; }
.sau-brand .icon-wrap {
    width: 38px; height: 38px; border-radius: 10px;
    background: linear-gradient(135deg,#6d28d9,#4f46e5);
    display:flex; align-items:center; justify-content:center;
    font-size: 1.1rem; color:#fff;
    box-shadow: 0 4px 12px rgba(109,40,217,.4);
}
.sau-brand-text { color:#f8fafc; font-weight:800; font-size:.95rem; line-height:1.2; }
.sau-brand-text span { display:block; font-size:.65rem; font-weight:500; color:#94a3b8; }

.sau-stats { display:flex; gap:6px; }
.sau-stat {
    display:flex; align-items:center; gap:7px;
    background: rgba(255,255,255,.07);
    border: 1px solid rgba(255,255,255,.1);
    border-radius: 30px; padding: 5px 14px;
    font-size: .75rem; font-weight: 700; color: #e2e8f0;
    cursor: default;
}
.sau-stat .dot { width:8px; height:8px; border-radius:50%; flex-shrink:0; }
.sau-stat .dot.amber { background:#f59e0b; box-shadow:0 0 6px #f59e0b; }
.sau-stat .dot.blue  { background:#3b82f6; box-shadow:0 0 6px #3b82f6; }
.sau-stat .dot.green { background:#22c55e; box-shadow:0 0 6px #22c55e; }

.sau-user { display:flex; align-items:center; gap:10px; }
.sau-clock { font-family:monospace; font-size:1.2rem; font-weight:700; color:#818cf8; letter-spacing:.5px; }
.sau-avatar {
    width:34px; height:34px; border-radius:50%;
    background: linear-gradient(135deg,#6d28d9,#4f46e5);
    display:flex; align-items:center; justify-content:center;
    font-size:.85rem; font-weight:800; color:#fff;
}
.sau-logout {
    width:34px; height:34px; border-radius:8px;
    background: rgba(255,255,255,.08); border:1px solid rgba(255,255,255,.15);
    display:flex; align-items:center; justify-content:center;
    color:#94a3b8; text-decoration:none; transition:all .2s;
}
.sau-logout:hover { background:rgba(239,68,68,.2); border-color:#ef4444; color:#fca5a5; }

/* ═══════════════════════════════════════════════════════════
   NAVIGATION ONGLETS
═══════════════════════════════════════════════════════════ */
.sau-tabs {
    background: #fff;
    border-bottom: 1px solid #e2e8f0;
    padding: 0 24px;
    display: flex; gap: 4px;
    position: sticky; top: 58px; z-index: 1050;
    box-shadow: 0 1px 4px rgba(0,0,0,.06);
}
.sau-tab {
    display: inline-flex; align-items: center; gap: 8px;
    padding: 14px 20px;
    font-size: .82rem; font-weight: 700; color: #64748b;
    border: none; background: transparent;
    border-bottom: 3px solid transparent;
    cursor: pointer; transition: all .2s; white-space: nowrap;
}
.sau-tab:hover { color: #4f46e5; background: #f5f3ff; }
.sau-tab.active { color: #4f46e5; border-bottom-color: #4f46e5; }
.sau-tab .tbadge {
    background: #ef4444; color: #fff;
    border-radius: 20px; padding: 1px 7px;
    font-size: .65rem; font-weight: 800;
    min-width: 20px; text-align: center;
}
.sau-tab .tbadge.amber  { background: #f59e0b; color: #1e293b; }
.sau-tab .tbadge.indigo { background: #6366f1; }

/* ═══════════════════════════════════════════════════════════
   ZONES CONTENU
═══════════════════════════════════════════════════════════ */
.sau-pane { display:none; padding: 24px 28px; min-height: calc(100vh - 120px); }
.sau-pane.active { display: block; }

/* ═══════════════════════════════════════════════════════════
   ONGLET 1 — LAYOUT 2 COLONNES
═══════════════════════════════════════════════════════════ */
.enreg-layout { display: grid; grid-template-columns: 480px 1fr; gap: 20px; }
@media (max-width:1100px) { .enreg-layout { grid-template-columns:1fr; } }

/* ── Panneau formulaire ── */
.form-panel {
    background: #fff; border-radius: 16px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 4px 24px rgba(0,0,0,.05);
    overflow: hidden;
}
.form-panel-head {
    background: linear-gradient(135deg, #4f46e5, #6d28d9);
    padding: 18px 22px;
    display: flex; align-items: center; gap: 12px;
    color: #fff;
}
.form-panel-head .ph-icon {
    width: 40px; height: 40px; border-radius: 12px;
    background: rgba(255,255,255,.2);
    display: flex; align-items: center; justify-content: center;
    font-size: 1.1rem;
}
.form-panel-head h2 { margin: 0; font-size: 1rem; font-weight: 800; }
.form-panel-head p  { margin: 0; font-size: .72rem; color: rgba(255,255,255,.7); }
.form-panel-body { padding: 22px; }

/* ── Recherche ── */
.search-wrap { position: relative; margin-bottom: 18px; }
.search-wrap input {
    width: 100%; padding: 10px 14px 10px 40px;
    border: 2px solid #e2e8f0; border-radius: 12px;
    font-size: .85rem; color: #1e293b;
    background: #f8fafc; transition: all .2s;
}
.search-wrap input:focus { border-color: #6366f1; background: #fff; outline: none; box-shadow: 0 0 0 4px rgba(99,102,241,.1); }
.search-wrap .si { position:absolute; left:13px; top:50%; transform:translateY(-50%); color:#94a3b8; font-size:.9rem; }
.search-results {
    position: absolute; width: 100%; background: #fff;
    border: 1.5px solid #e0e7ff; border-radius: 12px;
    z-index: 400; box-shadow: 0 8px 30px rgba(0,0,0,.12);
    max-height: 220px; overflow-y: auto; top: calc(100% + 4px);
}
.sr-item {
    padding: 10px 14px; cursor: pointer;
    border-bottom: 1px solid #f1f5f9;
    display: flex; align-items: center; gap: 10px;
    font-size: .82rem; transition: background .15s;
}
.sr-item:hover { background: #f5f3ff; }
.sr-item:last-child { border-bottom: none; }
.sr-avatar {
    width: 34px; height: 34px; border-radius: 50%; flex-shrink: 0;
    background: linear-gradient(135deg,#6d28d9,#4f46e5);
    color: #fff; display:flex; align-items:center; justify-content:center;
    font-size: .8rem; font-weight: 800;
}

/* ── Séparateur ── */
.divider-or { display:flex; align-items:center; gap:10px; margin:16px 0; }
.divider-or hr { flex:1; border:none; border-top:1px solid #e2e8f0; margin:0; }
.divider-or span { font-size:.72rem; color:#94a3b8; font-weight:600; white-space:nowrap; text-transform:uppercase; letter-spacing:.5px; }

/* ── Inputs ── */
.field-label { font-size:.72rem; font-weight:700; color:#374151; text-transform:uppercase; letter-spacing:.5px; margin-bottom:5px; display:block; }
.field-input {
    width: 100%; padding: 9px 12px;
    border: 1.5px solid #e2e8f0; border-radius: 10px;
    font-size: .85rem; color: #1e293b; background: #fff;
    transition: all .2s;
}
.field-input:focus { border-color: #6366f1; outline:none; box-shadow: 0 0 0 3px rgba(99,102,241,.1); }
.field-input.uppercase { text-transform: uppercase; }
select.field-input { cursor: pointer; }
.fields-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px; }
.field-full { margin-bottom: 12px; }

/* ── Bannière patient existant ── */
.existing-banner {
    background: linear-gradient(135deg,#ecfdf5,#d1fae5);
    border: 1.5px solid #6ee7b7; border-radius: 12px;
    padding: 10px 14px; display:flex; align-items:center; gap:10px;
    font-size: .82rem; color: #065f46; margin-bottom: 14px;
}
.existing-banner .eb-icon { font-size:1.2rem; }
.existing-banner a { color:#dc2626; font-weight:700; margin-left:auto; text-decoration:none; font-size:.78rem; }

/* ── Section triage ── */
.triage-section { margin-bottom: 18px; }
.triage-section .section-label {
    font-size: .72rem; font-weight: 700; color: #374151;
    text-transform: uppercase; letter-spacing: .5px; margin-bottom: 10px;
    display: flex; align-items: center; gap: 6px;
}
.triage-cards { display: grid; grid-template-columns: repeat(5,1fr); gap: 6px; }
.triage-card {
    border: 2px solid transparent; border-radius: 12px;
    padding: 10px 4px; text-align: center; cursor: pointer;
    transition: all .2s; position:relative;
}
.triage-card input[type=radio] { position:absolute; opacity:0; width:0; height:0; }
.triage-card .tc-num { font-size:1.3rem; font-weight:900; line-height:1; }
.triage-card .tc-label { font-size:.62rem; font-weight:700; margin-top:3px; text-transform:uppercase; letter-spacing:.3px; }
.triage-card.tr1 { background:#fff1f2; border-color:#fecdd3; color:#be123c; }
.triage-card.tr2 { background:#fff7ed; border-color:#fed7aa; color:#c2410c; }
.triage-card.tr3 { background:#fefce8; border-color:#fef08a; color:#a16207; }
.triage-card.tr4 { background:#f0fdf4; border-color:#bbf7d0; color:#166534; }
.triage-card.tr5 { background:#eff6ff; border-color:#bfdbfe; color:#1d4ed8; }
.triage-card.tr1:hover, .triage-card.tr1.selected { background:#fff1f2; border-color:#ef4444; box-shadow:0 4px 12px rgba(239,68,68,.25); transform:translateY(-2px); }
.triage-card.tr2:hover, .triage-card.tr2.selected { background:#fff7ed; border-color:#f97316; box-shadow:0 4px 12px rgba(249,115,22,.25); transform:translateY(-2px); }
.triage-card.tr3:hover, .triage-card.tr3.selected { background:#fefce8; border-color:#eab308; box-shadow:0 4px 12px rgba(234,179,8,.25); transform:translateY(-2px); }
.triage-card.tr4:hover, .triage-card.tr4.selected { background:#f0fdf4; border-color:#22c55e; box-shadow:0 4px 12px rgba(34,197,94,.25); transform:translateY(-2px); }
.triage-card.tr5:hover, .triage-card.tr5.selected { background:#eff6ff; border-color:#3b82f6; box-shadow:0 4px 12px rgba(59,130,246,.25); transform:translateY(-2px); }

/* ── Sélection médecin ── */
.medecin-section { margin-bottom: 20px; }
.medecin-strip {
    display: flex; gap: 8px; overflow-x: auto; padding-bottom: 6px;
    scrollbar-width: thin; scrollbar-color: #e0e7ff transparent;
}
.medecin-strip::-webkit-scrollbar { height: 4px; }
.medecin-strip::-webkit-scrollbar-track { background: transparent; }
.medecin-strip::-webkit-scrollbar-thumb { background: #c7d2fe; border-radius: 4px; }
.med-chip {
    display: flex; flex-direction: column; align-items: center;
    gap: 6px; padding: 12px 14px; border-radius: 14px;
    border: 2px solid #e2e8f0; background: #fff;
    cursor: pointer; transition: all .2s; flex-shrink: 0;
    min-width: 90px;
}
.med-chip:hover { border-color: #a5b4fc; background: #f5f3ff; }
.med-chip.selected { border-color: #4f46e5; background: #eef2ff; box-shadow: 0 4px 14px rgba(79,70,229,.2); }
.med-chip .med-av {
    width: 40px; height: 40px; border-radius: 12px;
    background: linear-gradient(135deg,#6d28d9,#4f46e5);
    display:flex; align-items:center; justify-content:center;
    font-size: .9rem; color: #fff; font-weight: 800;
    transition: transform .2s;
}
.med-chip.selected .med-av { background: linear-gradient(135deg,#4f46e5,#6366f1); transform: scale(1.1); }
.med-chip .med-nm { font-size: .72rem; font-weight: 700; color: #1e293b; text-align:center; white-space:nowrap; }
.med-chip .med-sv { font-size: .62rem; color: #94a3b8; text-align:center; white-space:nowrap; }

/* ── Bouton submit ── */
.btn-submit {
    width: 100%; padding: 13px;
    background: linear-gradient(135deg,#4f46e5,#7c3aed);
    color: #fff; border: none; border-radius: 12px;
    font-size: .9rem; font-weight: 800; letter-spacing: .3px;
    cursor: pointer; transition: all .25s;
    display: flex; align-items: center; justify-content: center; gap: 8px;
    box-shadow: 0 4px 16px rgba(79,70,229,.3);
}
.btn-submit:hover:not(:disabled) { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(79,70,229,.4); }
.btn-submit:disabled { opacity: .45; cursor: not-allowed; transform: none; box-shadow: none; }

/* ── Panneau file d'attente ── */
.queue-panel { display:flex; flex-direction:column; gap:0; }
.queue-header {
    background: #fff; border-radius: 14px 14px 0 0;
    padding: 16px 20px; border: 1px solid #e2e8f0; border-bottom: none;
    display: flex; align-items: center; gap: 12px;
}
.queue-header h3 { margin:0; font-size:.9rem; font-weight:800; color:#1e293b; }
.queue-count {
    background: #f59e0b; color: #1e293b;
    border-radius: 20px; padding: 2px 10px;
    font-size: .72rem; font-weight: 800;
}
.queue-body {
    background: #fff; border-radius: 0 0 14px 14px;
    border: 1px solid #e2e8f0; border-top: none;
    padding: 0; overflow: hidden;
    flex: 1; min-height: 300px;
}
.queue-empty {
    display:flex; flex-direction:column; align-items:center; justify-content:center;
    padding: 60px 20px; color: #94a3b8;
}
.queue-empty .qe-icon { font-size: 2.5rem; color: #22c55e; margin-bottom: 12px; }
.queue-empty p { margin:0; font-size:.85rem; font-weight:600; }

/* ── Carte patient en file ── */
.qcard {
    display: flex; align-items: center; gap: 14px;
    padding: 14px 20px;
    border-bottom: 1px solid #f1f5f9;
    transition: background .15s;
    position: relative;
}
.qcard::before {
    content:''; position:absolute; left:0; top:0; bottom:0;
    width: 4px; border-radius: 0;
}
.qcard.p1::before { background:#ef4444; }
.qcard.p2::before { background:#f97316; }
.qcard.p3::before { background:#eab308; }
.qcard.p4::before { background:#22c55e; }
.qcard.p5::before { background:#3b82f6; }
.qcard:hover { background: #fafaf9; }
.qcard:last-child { border-bottom: none; }
.qcard-av {
    width: 42px; height: 42px; border-radius: 50%; flex-shrink:0;
    display:flex; align-items:center; justify-content:center;
    font-weight: 800; font-size: .95rem;
}
.qcard-av.M { background:#dbeafe; color:#1d4ed8; }
.qcard-av.F { background:#fce7f3; color:#be185d; }
.qcard-info { flex:1; min-width:0; }
.qcard-name { font-weight:700; font-size:.88rem; color:#1e293b; }
.qcard-meta { font-size:.73rem; color:#64748b; margin-top:2px; }
.qcard-tags { display:flex; gap:5px; flex-wrap:wrap; margin-top:5px; }
.qtag { display:inline-flex; align-items:center; gap:3px; padding:2px 8px; border-radius:20px; font-size:.67rem; font-weight:700; }
.qtag.p1{background:#fee2e2;color:#991b1b;}
.qtag.p2{background:#ffedd5;color:#9a3412;}
.qtag.p3{background:#fef9c3;color:#854d0e;}
.qtag.p4{background:#dcfce7;color:#166534;}
.qtag.p5{background:#dbeafe;color:#1e40af;}
.qtag.att{background:#fef3c7;color:#92400e;}
.qtag.enc{background:#e0f2fe;color:#0369a1;}
.qcard-actions { display:flex; gap:6px; flex-shrink:0; }
.qcard-actions .qa {
    width:32px; height:32px; border-radius:8px;
    display:flex; align-items:center; justify-content:center;
    border: 1.5px solid #e2e8f0; background:#fff;
    color:#64748b; text-decoration:none; font-size:.85rem;
    transition:all .2s; cursor:pointer;
}
.qcard-actions .qa:hover { border-color:#4f46e5; color:#4f46e5; background:#f5f3ff; }

/* ═══════════════════════════════════════════════════════════
   ONGLET 2 — GESTION DES LITS
═══════════════════════════════════════════════════════════ */
.lits-toolbar {
    display:flex; align-items:center; justify-content:space-between;
    margin-bottom:20px; gap:12px; flex-wrap:wrap;
}
.lits-toolbar h2 { margin:0; font-size:1rem; font-weight:800; color:#1e293b; }
.legend { display:flex; gap:14px; flex-wrap:wrap; }
.legend-item { display:flex; align-items:center; gap:7px; font-size:.75rem; font-weight:600; color:#374151; }
.legend-dot { width:12px; height:12px; border-radius:3px; }

.service-block { background:#fff; border-radius:14px; border:1px solid #e2e8f0; overflow:hidden; margin-bottom:16px; box-shadow:0 2px 8px rgba(0,0,0,.04); }
.service-block-head {
    padding:12px 18px; background:#f8fafc;
    border-bottom:1px solid #e2e8f0;
    display:flex; align-items:center; gap:10px;
}
.service-block-head .sname { font-weight:800; font-size:.88rem; color:#1e293b; flex:1; }
.service-block-body { padding:18px; }
.chambre-group { margin-bottom:20px; }
.chambre-group:last-child { margin-bottom:0; }
.chambre-label {
    font-size:.72rem; font-weight:700; color:#64748b;
    text-transform:uppercase; letter-spacing:.6px; margin-bottom:10px;
    display:flex; align-items:center; gap:7px;
    padding-bottom:7px; border-bottom:1px dashed #e2e8f0;
}

/* ── Grille de cartes de lits ── */
.lits-cards-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
    gap: 12px;
}

.lit-card {
    border-radius: 14px;
    border: 2px solid #e2e8f0;
    overflow: hidden;
    transition: all .2s;
    background: #fff;
    box-shadow: 0 2px 8px rgba(0,0,0,.04);
}
.lit-card:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(0,0,0,.08); }

/* Bande colorée en haut selon statut */
.lit-card .lc-top {
    height: 6px;
}
.lit-card.libre  .lc-top  { background: linear-gradient(90deg,#22c55e,#4ade80); }
.lit-card.occupe .lc-top  { background: linear-gradient(90deg,#ef4444,#f87171); }
.lit-card.reserve .lc-top { background: linear-gradient(90deg,#f59e0b,#fbbf24); }

.lit-card .lc-body { padding: 14px 14px 12px; }

/* Ligne numéro + icône statut */
.lc-head {
    display: flex; align-items: center; justify-content: space-between;
    margin-bottom: 10px;
}
.lc-nom {
    font-size: .88rem; font-weight: 800; color: #1e293b;
}
.lc-statut-dot {
    width: 10px; height: 10px; border-radius: 50%; flex-shrink:0;
}
.lit-card.libre  .lc-statut-dot { background:#22c55e; box-shadow:0 0 6px rgba(34,197,94,.5); }
.lit-card.occupe .lc-statut-dot { background:#ef4444; box-shadow:0 0 6px rgba(239,68,68,.5); animation: pulse-dot 2s infinite; }
.lit-card.reserve .lc-statut-dot { background:#f59e0b; box-shadow:0 0 6px rgba(245,158,11,.4); }
@keyframes pulse-dot { 0%,100%{opacity:1} 50%{opacity:.4} }

/* Zone patient */
.lc-patient {
    background: #f8fafc; border-radius: 10px; padding: 10px 12px;
    margin-bottom: 10px;
}
.lc-patient .lcp-name {
    font-size: .82rem; font-weight: 700; color: #1e293b;
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.lc-patient .lcp-since {
    font-size: .7rem; color: #94a3b8; margin-top: 2px;
    display: flex; align-items: center; gap: 4px;
}

/* Zone vide */
.lc-empty {
    text-align: center; padding: 12px 0;
    color: #22c55e; font-size: .78rem; font-weight: 600;
}
.lc-empty i { font-size: 1.4rem; display:block; margin-bottom:4px; color:#86efac; }

/* Boutons action */
.lc-actions {
    display: flex; gap: 6px;
}
.lc-btn {
    flex: 1; padding: 7px 0; border-radius: 8px; border: 1.5px solid;
    font-size: .72rem; font-weight: 700; cursor: pointer;
    display: flex; align-items: center; justify-content: center; gap: 4px;
    transition: all .2s; background: #fff;
}
.lc-btn.transfer { border-color:#c7d2fe; color:#4f46e5; }
.lc-btn.transfer:hover { background:#eef2ff; border-color:#818cf8; }
.lc-btn.release  { border-color:#fecaca; color:#ef4444; }
.lc-btn.release:hover  { background:#fff1f2; border-color:#f87171; }

/* ═══════════════════════════════════════════════════════════
   ONGLET 3 — JOURNÉE
═══════════════════════════════════════════════════════════ */
.journee-header {
    background:#fff; border-radius:14px; border:1px solid #e2e8f0;
    padding:14px 20px; display:flex; align-items:center; gap:12px;
    margin-bottom:16px; box-shadow:0 2px 8px rgba(0,0,0,.04);
}
.journee-header h2 { margin:0; font-size:.95rem; font-weight:800; color:#1e293b; flex:1; }
.search-j {
    padding:8px 14px; border:1.5px solid #e2e8f0; border-radius:10px;
    font-size:.82rem; color:#1e293b; width:220px; transition:all .2s;
}
.search-j:focus { border-color:#6366f1; outline:none; box-shadow:0 0 0 3px rgba(99,102,241,.1); }

.table-sau { width:100%; border-collapse:collapse; }
.table-sau thead th {
    background:#f8fafc; font-size:.7rem; font-weight:700; color:#64748b;
    text-transform:uppercase; letter-spacing:.5px;
    padding:10px 14px; border:none; border-bottom:2px solid #e2e8f0;
    white-space:nowrap;
}
.table-sau tbody td { font-size:.82rem; color:#374151; padding:12px 14px; border-bottom:1px solid #f1f5f9; vertical-align:middle; }
.table-sau tbody tr:hover td { background:#f5f3ff; }
.table-sau-wrap { background:#fff; border-radius:14px; border:1px solid #e2e8f0; overflow:hidden; box-shadow:0 2px 8px rgba(0,0,0,.04); }

/* Statut badges */
.sbadge { display:inline-flex; align-items:center; gap:5px; padding:3px 10px; border-radius:20px; font-size:.7rem; font-weight:700; }
.sb-att  { background:#fef3c7; color:#92400e; }
.sb-enc  { background:#e0f2fe; color:#0369a1; }
.sb-ter  { background:#dcfce7; color:#166534; }
.sb-hos  { background:#ede9fe; color:#4c1d95; }

/* Triage badge */
.tbadge-sm { display:inline-flex; padding:2px 8px; border-radius:8px; font-size:.68rem; font-weight:800; color:#fff; }
.tb-1{background:#ef4444}.tb-2{background:#f97316}.tb-3{background:#eab308;color:#1e293b}.tb-4{background:#22c55e}.tb-5{background:#3b82f6}

/* ═══════════════════════════════════════════════════════════
   MODALS
═══════════════════════════════════════════════════════════ */
.modal-sau .modal-content { border-radius:18px; border:none; box-shadow:0 24px 60px rgba(0,0,0,.18); overflow:hidden; }
.modal-sau .modal-header { padding:18px 24px; border-bottom:1px solid #f1f5f9; }
.modal-sau .modal-body { padding:24px; }
.modal-sau .modal-footer { padding:16px 24px; border-top:1px solid #f1f5f9; }
.modal-field { margin-bottom:16px; }
.modal-field label { font-size:.75rem; font-weight:700; color:#374151; text-transform:uppercase; letter-spacing:.5px; display:block; margin-bottom:6px; }
.modal-field select, .modal-field textarea {
    width:100%; padding:9px 12px; border:1.5px solid #e2e8f0; border-radius:10px;
    font-size:.85rem; color:#1e293b; transition:all .2s;
}
.modal-field select:focus, .modal-field textarea:focus { border-color:#6366f1; outline:none; box-shadow:0 0 0 3px rgba(99,102,241,.1); }

/* Toast */
.toast-container { position:fixed; bottom:24px; right:24px; z-index:9999; }
</style>

<?php
$trLabels = [1=>'P1 Vital',2=>'P2 Urgent',3=>'P3 Stable',4=>'P4 Mineur',5=>'P5 Surveill.'];
$initials = strtoupper(substr($_SESSION['user_prenom'] ?? 'S', 0, 1) . substr($_SESSION['user_nom'] ?? 'A', 0, 1));
?>

<!-- ═══════════════════════════════════════════
     TOPBAR
═══════════════════════════════════════════ -->
<div class="sau-topbar">
    <div class="sau-brand">
        <div class="icon-wrap"><i class="bi bi-hospital-fill"></i></div>
        <div class="sau-brand-text">
            Accueil des Urgences
            <span>Secrétariat SAU</span>
        </div>
    </div>

    <div class="sau-stats">
        <div class="sau-stat">
            <div class="dot amber"></div>
            <span><?= $nb_attente ?> en attente</span>
        </div>
        <div class="sau-stat">
            <div class="dot blue"></div>
            <span><?= $nb_en_cours ?> en cours</span>
        </div>
        <div class="sau-stat">
            <div class="dot green"></div>
            <span><?= $nb_total_jour ?> aujourd'hui</span>
        </div>
    </div>

    <div class="sau-user">
        <div class="sau-clock" id="navClock">--:--:--</div>
        <div class="sau-avatar"><?= $initials ?></div>
        <div style="color:#e2e8f0;font-size:.78rem;line-height:1.3;">
            <div style="font-weight:700;"><?= htmlspecialchars(($_SESSION['user_prenom']??'').' '.($_SESSION['user_nom']??'')) ?></div>
            <div style="color:#94a3b8;font-size:.68rem;">Secrétaire SAU</div>
        </div>
        <a href="<?= BASE_URL ?>logout" class="sau-logout" title="Déconnexion">
            <i class="bi bi-box-arrow-right"></i>
        </a>
    </div>
</div>

<!-- ═══════════════════════════════════════════
     ONGLETS
═══════════════════════════════════════════ -->
<div class="sau-tabs">
    <button class="sau-tab active" data-pane="pane-enreg">
        <i class="bi bi-person-plus-fill"></i>
        Enregistrement
        <?php if($nb_attente > 0): ?>
        <span class="tbadge amber"><?= $nb_attente ?></span>
        <?php endif; ?>
    </button>
    <button class="sau-tab" data-pane="pane-lits">
        <i class="bi bi-grid-3x3-gap-fill"></i>
        Gestion des Lits
    </button>
    <button class="sau-tab" data-pane="pane-journee">
        <i class="bi bi-clipboard2-pulse"></i>
        <?= date('d/m/Y') ?>
        <span class="tbadge indigo"><?= $nb_total_jour ?></span>
    </button>
</div>

<!-- ═══════════════════════════════════════════
     PANE 1 — ENREGISTREMENT
═══════════════════════════════════════════ -->
<div class="sau-pane active" id="pane-enreg">
    <div class="enreg-layout">

        <!-- ── Formulaire ── -->
        <div class="form-panel">
            <div class="form-panel-head">
                <div class="ph-icon"><i class="bi bi-person-plus-fill"></i></div>
                <div>
                    <h2>Enregistrer un patient</h2>
                    <p>Recherchez un dossier existant ou créez-en un nouveau</p>
                </div>
            </div>
            <div class="form-panel-body">

                <!-- Recherche patient -->
                <div class="search-wrap">
                    <i class="bi bi-search si"></i>
                    <input type="text" id="searchPatientInput" placeholder="Nom, prénom ou N° dossier…" autocomplete="off">
                    <div id="searchResultsBox" class="search-results d-none"></div>
                </div>

                <div class="divider-or">
                    <hr><span>ou nouveau patient</span><hr>
                </div>

                <form id="formEnregPatient" novalidate>
                    <input type="hidden" id="fieldPatientId" name="patient_id" value="">

                    <!-- Bannière patient existant -->
                    <div id="existingPatientBanner" class="existing-banner d-none">
                        <i class="bi bi-person-check-fill eb-icon"></i>
                        <div>
                            <strong id="existingPatientName"></strong>
                            <div style="font-size:.72rem;color:#047857;margin-top:1px;">Patient déjà en dossier — informations pré-remplies</div>
                        </div>
                        <a href="#" id="clearPatient">✕ Annuler</a>
                    </div>

                    <div id="newPatientFields">
                        <div class="fields-grid">
                            <div>
                                <label class="field-label">Nom *</label>
                                <input type="text" name="nom" id="fieldNom" class="field-input uppercase" placeholder="NOM">
                            </div>
                            <div>
                                <label class="field-label">Prénom</label>
                                <input type="text" name="prenom" class="field-input" placeholder="Prénom">
                            </div>
                            <div>
                                <label class="field-label">Sexe</label>
                                <select name="sexe" class="field-input">
                                    <option value="M">Masculin</option>
                                    <option value="F">Féminin</option>
                                </select>
                            </div>
                            <div>
                                <label class="field-label">Âge</label>
                                <input type="text" name="age_estimatif" class="field-input" placeholder="ex : 34">
                            </div>
                        </div>
                        <div class="field-full">
                            <label class="field-label">Téléphone</label>
                            <input type="text" name="telephone" class="field-input" placeholder="6XXXXXXXX">
                        </div>
                    </div>

                    <!-- Motif -->
                    <div class="field-full">
                        <label class="field-label"><i class="bi bi-chat-right-text me-1"></i>Motif d'admission <span style="font-weight:400;opacity:.6;">(optionnel)</span></label>
                        <textarea name="motif" class="field-input" rows="2" placeholder="Décrivez le motif de venue aux urgences…" style="resize:vertical;"></textarea>
                    </div>

                    <!-- Triage -->
                    <div class="triage-section">
                        <div class="section-label">
                            <i class="bi bi-heart-pulse-fill" style="color:#ef4444;"></i>
                            Niveau de priorité
                        </div>
                        <div class="triage-cards">
                            <?php
                            $triageData = [
                                1 => ['class'=>'tr1','num'=>'P1','lbl'=>'Vital',  'ico'=>'🔴'],
                                2 => ['class'=>'tr2','num'=>'P2','lbl'=>'Urgent', 'ico'=>'🟠'],
                                3 => ['class'=>'tr3','num'=>'P3','lbl'=>'Stable', 'ico'=>'🟡'],
                                4 => ['class'=>'tr4','num'=>'P4','lbl'=>'Mineur', 'ico'=>'🟢'],
                                5 => ['class'=>'tr5','num'=>'P5','lbl'=>'Surveill','ico'=>'🔵'],
                            ];
                            foreach($triageData as $val => $td):
                            ?>
                            <label class="triage-card <?= $td['class'] ?><?= $val===3?' selected':'' ?>" id="tc<?= $val ?>">
                                <input type="radio" name="niveau_triage" value="<?= $val ?>"<?= $val===3?' checked':'' ?> class="triage-radio">
                                <div class="tc-num"><?= $td['ico'] ?></div>
                                <div style="font-size:.72rem;font-weight:900;margin-top:2px;"><?= $td['num'] ?></div>
                                <div class="tc-label"><?= $td['lbl'] ?></div>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Médecin (optionnel) -->
                    <div class="medecin-section">
                        <div class="section-label">
                            <i class="bi bi-person-badge-fill" style="color:#4f46e5;"></i>
                            Assigner à un médecin
                            <span style="font-weight:400;opacity:.6;font-size:.75rem;margin-left:6px;">(optionnel)</span>
                        </div>
                        <!-- Bandeau file commune (visible quand aucun médecin sélectionné) -->
                        <div id="bandeauFileCommunne" style="display:flex;align-items:center;gap:8px;padding:8px 12px;background:#eff6ff;border:1px solid #bfdbfe;border-radius:8px;font-size:.78rem;color:#1d4ed8;margin-bottom:8px;">
                            <i class="bi bi-broadcast-pin fs-5"></i>
                            <span>Le patient sera visible par <strong>tous les médecins disponibles</strong>. Le premier qui clique sur "Consulter" le prend en charge.</span>
                        </div>
                        <input type="hidden" name="medecin_id" id="fieldMedecinId">
                        <div class="medecin-strip" id="medecinsGrid">
                            <?php foreach($medecins as $med):
                                $av = strtoupper(substr($med['prenom'],0,1).substr($med['nom'],0,1));
                            ?>
                            <div class="med-chip" data-id="<?= $med['id'] ?>"
                                 onclick="selectMedecin(<?= $med['id'] ?>, '<?= htmlspecialchars(addslashes($med['prenom'].' '.$med['nom'])) ?>')">
                                <div class="med-av"><?= $av ?></div>
                                <div class="med-nm">Dr <?= htmlspecialchars($med['nom']) ?></div>
                                <div class="med-sv"><?= htmlspecialchars($med['nom_service']) ?></div>
                            </div>
                            <?php endforeach; ?>
                            <?php if(empty($medecins)): ?>
                            <div class="text-muted" style="font-size:.8rem;padding:10px;">Aucun médecin disponible</div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Panneau doublons (injecté dynamiquement) -->
                    <div id="doublonWarningPanel" style="display:none;margin-bottom:12px;
                         background:#fffbeb;border:1.5px solid #fde68a;border-radius:12px;padding:12px 14px;">
                        <div style="display:flex;align-items:center;gap:8px;margin-bottom:8px;">
                            <i class="bi bi-exclamation-triangle-fill" style="color:#f59e0b;font-size:1rem;"></i>
                            <span style="font-weight:800;font-size:.82rem;color:#92400e;" id="doublonWarningTitle">
                                Patients similaires trouvés
                            </span>
                        </div>
                        <div id="doublonWarningList" style="display:flex;flex-direction:column;gap:6px;margin-bottom:10px;"></div>
                        <div style="display:flex;gap:8px;flex-wrap:wrap;">
                            <button type="button" onclick="forcerCreation()"
                                    style="background:#f59e0b;color:#fff;border:none;border-radius:8px;
                                           padding:7px 16px;font-size:.82rem;font-weight:700;cursor:pointer;">
                                <i class="bi bi-person-plus-fill me-1"></i> Créer quand même
                            </button>
                            <button type="button" onclick="cacherDoublons()"
                                    style="background:#f1f5f9;color:#475569;border:1.5px solid #e2e8f0;
                                           border-radius:8px;padding:7px 14px;font-size:.82rem;font-weight:600;cursor:pointer;">
                                Annuler
                            </button>
                        </div>
                    </div>

                    <button type="button" class="btn-submit" id="btnEnreg" onclick="enregistrerPatient()" disabled>
                        <i class="bi bi-send-fill"></i>
                        Enregistrer le patient
                    </button>
                </form>
            </div>
        </div>

        <!-- ── File d'attente ── -->
        <div class="queue-panel">
            <div class="queue-header">
                <i class="bi bi-hourglass-split" style="color:#f59e0b;font-size:1.1rem;"></i>
                <h3>File d'attente</h3>
                <span class="queue-count"><?= count($patients_attente) ?></span>
                <button onclick="location.reload()" style="margin-left:auto;background:#f8fafc;border:1.5px solid #e2e8f0;border-radius:8px;padding:5px 12px;font-size:.75rem;font-weight:700;color:#64748b;cursor:pointer;display:flex;align-items:center;gap:5px;">
                    <i class="bi bi-arrow-clockwise"></i> Actualiser
                </button>
            </div>
            <div class="queue-body">
                <?php if(empty($patients_attente)): ?>
                <div class="queue-empty">
                    <i class="bi bi-check-circle-fill qe-icon"></i>
                    <p>Aucun patient en attente</p>
                    <small style="color:#cbd5e1;margin-top:4px;">La file est vide pour le moment</small>
                </div>
                <?php else: ?>
                <?php foreach($patients_attente as $p):
                    $age   = ($p['date_naissance'] && $p['date_naissance'] !== '1900-01-01')
                             ? (int)((time() - strtotime($p['date_naissance'])) / 31557600) : null;
                    $heure = date('H:i', strtotime($p['heure_arrivee']));
                    $tr    = (int)($p['niveau_triage'] ?? 3);
                    $pClass= 'p'.$tr;
                ?>
                <div class="qcard <?= $pClass ?>" id="row-adm-<?= $p['admission_id'] ?>">
                    <div class="qcard-av <?= $p['sexe']==='F'?'F':'M' ?>">
                        <?= strtoupper(mb_substr($p['prenom']?:$p['nom'],0,1)) ?>
                    </div>
                    <div class="qcard-info">
                        <div class="qcard-name">
                            <?= htmlspecialchars($p['nom'].' '.($p['prenom']??'')) ?>
                            <span style="font-size:.7rem;color:#94a3b8;font-weight:400;margin-left:4px;">#<?= htmlspecialchars($p['dossier_numero']??'—') ?></span>
                        </div>
                        <div class="qcard-meta">
                            <?= $age?"$age ans · ":'' ?><?= $p['sexe']==='F'?'F':'M' ?> · <?= $heure ?>
                            <?php if($p['medecin_nom']): ?>
                             · <i class="bi bi-person-fill"></i> Dr <?= htmlspecialchars($p['medecin_nom']) ?>
                            <?php endif; ?>
                        </div>
                        <div class="qcard-tags">
                            <span class="qtag <?= $pClass ?>"><?= $trLabels[$tr]??'P3' ?></span>
                            <?php if($p['statut']==='EN_COURS'): ?>
                            <span class="qtag enc"><i class="bi bi-circle-fill" style="font-size:.35rem;"></i>En consultation</span>
                            <?php else: ?>
                            <span class="qtag att"><i class="bi bi-circle-fill" style="font-size:.35rem;"></i>En attente</span>
                            <?php endif; ?>
                            <?php if($m = ($p['motif_plainte']?:$p['motif_admission'])): ?>
                            <span style="background:#f1f5f9;color:#64748b;padding:2px 7px;border-radius:6px;font-size:.65rem;">
                                <?= htmlspecialchars(mb_strimwidth($m,0,30,'…')) ?>
                            </span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="qcard-actions">
                        <a href="<?= BASE_URL ?>urgences/triage/<?= $p['admission_id'] ?>" class="qa" title="Triage">
                            <i class="bi bi-clipboard-heart-fill"></i>
                        </a>
                        <a href="<?= BASE_URL ?>dossier/<?= $p['patient_id'] ?>" class="qa" title="Dossier">
                            <i class="bi bi-folder2-open"></i>
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

    </div>
</div>

<!-- ═══════════════════════════════════════════
     PANE 2 — GESTION DES LITS
═══════════════════════════════════════════ -->
<div class="sau-pane" id="pane-lits">
    <div class="lits-toolbar">
        <div>
            <h2><i class="bi bi-grid-3x3-gap-fill me-2" style="color:#4f46e5;"></i>Carte des lits — Tous services</h2>
        </div>
        <div class="d-flex align-items-center gap-3 flex-wrap">
            <div class="legend">
                <div class="legend-item"><div class="legend-dot" style="background:#22c55e;"></div>Disponible</div>
                <div class="legend-item"><div class="legend-dot" style="background:#ef4444;"></div>Occupé</div>
                <div class="legend-item"><div class="legend-dot" style="background:#f59e0b;"></div>Réservé</div>
            </div>
            <button onclick="location.reload()" style="background:#fff;border:1.5px solid #e2e8f0;border-radius:10px;padding:7px 14px;font-size:.78rem;font-weight:700;color:#64748b;cursor:pointer;display:flex;align-items:center;gap:6px;">
                <i class="bi bi-arrow-clockwise"></i> Actualiser
            </button>
        </div>
    </div>

    <?php if(empty($lits_carte)): ?>
    <div class="text-center py-5" style="color:#94a3b8;">
        <i class="bi bi-exclamation-triangle-fill" style="font-size:2rem;"></i>
        <p class="mt-2">Aucun lit en base de données</p>
    </div>
    <?php else: ?>
    <?php foreach($lits_carte as $nomSvc => $chambres):
        $totalLits = array_sum(array_map('count',$chambres));
        $libres = 0;
        foreach($chambres as $cLits) foreach($cLits as $l)
            if(in_array(strtolower($l['lit_statut']??''),['disponible','libre'])) $libres++;
        $occupes = $totalLits - $libres;
    ?>
    <div class="service-block">
        <div class="service-block-head">
            <i class="bi bi-building-fill" style="color:#4f46e5;font-size:1rem;"></i>
            <span class="sname"><?= htmlspecialchars($nomSvc) ?></span>
            <span class="badge" style="background:#dcfce7;color:#166534;font-size:.68rem;"><?= $libres ?> libre<?= $libres>1?'s':'' ?></span>
            <span class="badge" style="background:#fee2e2;color:#991b1b;font-size:.68rem;"><?= $occupes ?> occupé<?= $occupes>1?'s':'' ?></span>
            <span class="badge bg-secondary" style="font-size:.68rem;"><?= $totalLits ?> total</span>
        </div>
        <div class="service-block-body">
        <?php foreach($chambres as $nomCh => $lits): ?>
        <div class="chambre-group">
            <div class="chambre-label">
                <i class="bi bi-door-closed-fill"></i>
                Chambre <?= htmlspecialchars($nomCh) ?>
                <span style="margin-left:auto;font-size:.68rem;color:#cbd5e1;">
                    <?= count($lits) ?> lit<?= count($lits)>1?'s':'' ?>
                </span>
            </div>
            <div class="lits-cards-grid">
                <?php foreach($lits as $lit):
                    $st     = strtolower($lit['lit_statut'] ?? 'disponible');
                    $classe = in_array($st, ['disponible','libre']) ? 'libre'
                            : ($st==='occupe'||$st==='occupé' ? 'occupe' : 'reserve');
                    $statutLabel = ['libre'=>'Disponible','occupe'=>'Occupé','reserve'=>'Réservé'][$classe];
                    $depuis = $lit['date_admission']
                              ? date('d/m H:i', strtotime($lit['date_admission']))
                              : null;
                    $patNomComplet = trim($lit['patient_nom'].' '.($lit['patient_prenom']??''));
                ?>
                <div class="lit-card <?= $classe ?>">
                    <div class="lc-top"></div>
                    <div class="lc-body">
                        <div class="lc-head">
                            <div class="lc-nom">
                                <i class="bi bi-hospital me-1" style="font-size:.75rem;color:#94a3b8;"></i>
                                <?= htmlspecialchars($lit['nom_lit']) ?>
                            </div>
                            <div class="lc-statut-dot" title="<?= $statutLabel ?>"></div>
                        </div>

                        <?php if($lit['patient_nom']): ?>
                        <div class="lc-patient">
                            <div class="lcp-name" title="<?= htmlspecialchars($patNomComplet) ?>">
                                <i class="bi bi-person-fill" style="color:#64748b;font-size:.75rem;"></i>
                                <?= htmlspecialchars(mb_strimwidth($patNomComplet, 0, 22, '…')) ?>
                            </div>
                            <?php if($depuis): ?>
                            <div class="lcp-since">
                                <i class="bi bi-clock"></i> depuis <?= $depuis ?>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php if($lit['hosp_id']): ?>
                        <div class="lc-actions">
                            <button class="lc-btn transfer"
                                    onclick="ouvrirTransfert(<?= $lit['patient_id'] ?>, '<?= htmlspecialchars(addslashes($patNomComplet)) ?>', <?= $lit['hosp_id'] ?>)">
                                <i class="bi bi-arrow-left-right"></i> Transférer
                            </button>
                            <button class="lc-btn release"
                                    onclick="confirmerLiberation(<?= $lit['hosp_id'] ?>, '<?= htmlspecialchars(addslashes($patNomComplet)) ?>')">
                                <i class="bi bi-door-open"></i> Libérer
                            </button>
                        </div>
                        <?php endif; ?>

                        <?php else: ?>
                        <div class="lc-empty">
                            <i class="bi bi-check-circle-fill"></i>
                            Disponible
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endforeach; ?>
        </div>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- ═══════════════════════════════════════════
     PANE 3 — JOURNÉE
═══════════════════════════════════════════ -->
<div class="sau-pane" id="pane-journee">
    <div class="journee-header">
        <i class="bi bi-clipboard2-pulse-fill" style="color:#4f46e5;font-size:1.1rem;"></i>
        <h2>Patients enregistrés — <?= date('d/m/Y') ?></h2>
        <span class="badge" style="background:#ede9fe;color:#4c1d95;font-size:.75rem;font-weight:800;"><?= count($patients_jour) ?> patients</span>
        <div style="margin-left:auto;display:flex;gap:8px;align-items:center;">
            <input type="text" class="search-j" id="searchJournee" placeholder="🔍 Rechercher…" oninput="filtrerJournee(this.value)">
            <button onclick="location.reload()" style="background:#f8fafc;border:1.5px solid #e2e8f0;border-radius:10px;padding:7px 14px;font-size:.78rem;font-weight:700;color:#64748b;cursor:pointer;">
                <i class="bi bi-arrow-clockwise"></i>
            </button>
        </div>
    </div>

    <?php if(empty($patients_jour)): ?>
    <div class="text-center py-5" style="color:#94a3b8;">
        <i class="bi bi-person-x-fill" style="font-size:2rem;"></i>
        <p class="mt-2">Aucun patient enregistré aujourd'hui</p>
    </div>
    <?php else: ?>
    <div class="table-sau-wrap">
        <table class="table-sau" id="tableJournee">
            <thead>
                <tr>
                    <th>Heure</th>
                    <th>Patient</th>
                    <th>Triage</th>
                    <th>Motif</th>
                    <th>Médecin</th>
                    <th>Statut</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach($patients_jour as $p):
                $heure = date('H:i', strtotime($p['heure_arrivee']));
                $age   = ($p['date_naissance'] && $p['date_naissance']!=='1900-01-01')
                         ? (int)((time()-strtotime($p['date_naissance']))/31557600).'a' : '—';
                $tr    = (int)($p['niveau_triage']??3);
                $stMap = ['ATTENTE'=>'sb-att','EN_COURS'=>'sb-enc','TERMINE'=>'sb-ter','HOSPITALISE'=>'sb-hos'];
                $stLbl = ['ATTENTE'=>'Attente','EN_COURS'=>'En cours','TERMINE'=>'Terminé','HOSPITALISE'=>'Hospitalisé'];
            ?>
            <tr data-search="<?= strtolower($p['nom'].' '.$p['prenom'].' '.$p['dossier_numero']) ?>">
                <td><strong><?= $heure ?></strong></td>
                <td>
                    <div style="font-weight:700;"><?= htmlspecialchars($p['nom'].' '.($p['prenom']??'')) ?></div>
                    <small style="color:#94a3b8;"><?= htmlspecialchars($p['dossier_numero']??'') ?> · <?= $age ?></small>
                </td>
                <td><span class="tbadge-sm tb-<?= $tr ?>"><?= $trLabels[$tr]??'P3' ?></span></td>
                <td style="max-width:160px;" title="<?= htmlspecialchars($p['motif_admission']??'') ?>">
                    <span style="color:#64748b;"><?= htmlspecialchars(mb_strimwidth($p['motif_admission']??'—',0,38,'…')) ?></span>
                </td>
                <td>
                    <?php if($p['medecin_nom']): ?>
                    <span style="font-size:.8rem;color:#4f46e5;font-weight:600;">Dr <?= htmlspecialchars($p['medecin_nom']) ?></span>
                    <?php else: ?><span style="color:#cbd5e1;">—</span><?php endif; ?>
                </td>
                <td>
                    <span class="sbadge <?= $stMap[$p['statut']]??'sb-att' ?>">
                        <?= $stLbl[$p['statut']]??$p['statut'] ?>
                    </span>
                </td>
                <td>
                    <div style="display:flex;gap:5px;">
                        <a href="<?= BASE_URL ?>dossier/<?= $p['patient_id'] ?>" class="qa" style="width:30px;height:30px;border-radius:7px;border:1.5px solid #e2e8f0;display:flex;align-items:center;justify-content:center;color:#64748b;text-decoration:none;font-size:.8rem;" title="Dossier">
                            <i class="bi bi-folder2-open"></i>
                        </a>
                        <?php if(in_array($p['statut'],['ATTENTE','EN_COURS'])): ?>
                        <a href="<?= BASE_URL ?>urgences/triage/<?= $p['admission_id'] ?>" class="qa" style="width:30px;height:30px;border-radius:7px;border:1.5px solid #e0e7ff;background:#eef2ff;display:flex;align-items:center;justify-content:center;color:#4f46e5;text-decoration:none;font-size:.8rem;" title="Triage">
                            <i class="bi bi-clipboard-heart-fill"></i>
                        </a>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<!-- ═══════════════════════════════════════════
     MODAL TRANSFERT
═══════════════════════════════════════════ -->
<div class="modal fade modal-sau" id="modalTransfert" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header" style="background:linear-gradient(135deg,#4f46e5,#7c3aed);">
                <h5 class="modal-title text-white fw-bold"><i class="bi bi-arrow-left-right me-2"></i>Transfert de lit</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div style="background:#eff6ff;border:1.5px solid #bfdbfe;border-radius:10px;padding:10px 14px;margin-bottom:18px;font-size:.82rem;color:#1d4ed8;">
                    <i class="bi bi-person-fill me-1"></i>
                    <strong id="transfertPatientNom"></strong>
                </div>
                <input type="hidden" id="transfertPatientId">
                <input type="hidden" id="transfertHospId">

                <div class="modal-field">
                    <label>Service de destination *</label>
                    <select id="transfertServiceId" onchange="chargerLitsTransfert(this.value)">
                        <option value="">— Choisir un service —</option>
                        <?php foreach($services as $srv): ?>
                        <option value="<?= $srv['id'] ?>"><?= htmlspecialchars($srv['nom_service']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="modal-field" id="transfertLitBlock" style="display:none;">
                    <label>Lit disponible</label>
                    <select id="transfertLitId">
                        <option value="">— Choisir un lit —</option>
                    </select>
                    <div id="transfertNoLit" style="display:none;font-size:.75rem;color:#ef4444;margin-top:5px;">
                        <i class="bi bi-exclamation-triangle me-1"></i>Aucun lit disponible dans ce service
                    </div>
                </div>

                <div class="modal-field">
                    <label>Motif du transfert *</label>
                    <textarea id="transfertMotif" rows="2" placeholder="Justifiez le transfert…"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn text-white fw-bold" style="background:linear-gradient(135deg,#4f46e5,#7c3aed);border-radius:10px;" onclick="validerTransfert()">
                    <i class="bi bi-arrow-left-right me-1"></i>Confirmer
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ═══════════════════════════════════════════
     MODAL LIBÉRATION
═══════════════════════════════════════════ -->
<div class="modal fade modal-sau" id="modalLiberation" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white" style="border-radius:18px 18px 0 0;">
                <h5 class="modal-title fw-bold"><i class="bi bi-door-open me-2"></i>Libérer le lit</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center p-4">
                <div style="font-size:2.5rem;margin-bottom:10px;">🏥</div>
                <p style="font-size:.88rem;color:#64748b;">Confirmer la libération du lit de</p>
                <p class="fw-bold" id="liberationPatientNom" style="font-size:1rem;color:#1e293b;"></p>
                <p style="font-size:.75rem;color:#94a3b8;">L'hospitalisation sera clôturée.</p>
            </div>
            <div class="modal-footer border-0 justify-content-center gap-2">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-danger fw-bold" onclick="validerLiberation()" id="btnConfirmLiberation">
                    <i class="bi bi-check-circle me-1"></i>Confirmer
                </button>
            </div>
            <input type="hidden" id="liberationHospId">
        </div>
    </div>
</div>

<!-- Toasts -->
<div class="toast-container">
    <div id="toastSuccess" class="toast align-items-center text-bg-success border-0" role="alert">
        <div class="d-flex">
            <div class="toast-body fw-bold"><i class="bi bi-check-circle-fill me-2"></i><span id="toastSuccessMsg"></span></div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    </div>
    <div id="toastError" class="toast align-items-center text-bg-danger border-0" role="alert">
        <div class="d-flex">
            <div class="toast-body fw-bold"><i class="bi bi-exclamation-triangle-fill me-2"></i><span id="toastErrorMsg"></span></div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    </div>
</div>

<script>
/* ── Horloge ── */
(function tick(){
    document.getElementById('navClock').textContent =
        new Date().toLocaleTimeString('fr-FR',{hour:'2-digit',minute:'2-digit',second:'2-digit'});
    setTimeout(tick,1000);
})();

/* ── Onglets ── */
document.querySelectorAll('.sau-tab').forEach(btn => {
    btn.addEventListener('click', function(){
        document.querySelectorAll('.sau-tab').forEach(b => b.classList.remove('active'));
        document.querySelectorAll('.sau-pane').forEach(p => p.classList.remove('active'));
        this.classList.add('active');
        const pane = document.getElementById(this.dataset.pane);
        if(pane) pane.classList.add('active');
    });
});

/* ── Toasts ── */
function showSuccess(msg){ document.getElementById('toastSuccessMsg').textContent=msg; new bootstrap.Toast(document.getElementById('toastSuccess'),{delay:4000}).show(); }
function showError(msg){   document.getElementById('toastErrorMsg').textContent=msg;   new bootstrap.Toast(document.getElementById('toastError'),{delay:5000}).show(); }

/* ── Recherche patient ── */
let st=null;
document.getElementById('searchPatientInput').addEventListener('input',function(){
    clearTimeout(st);
    const v=this.value.trim();
    if(v.length<2){ document.getElementById('searchResultsBox').classList.add('d-none'); return; }
    st=setTimeout(()=>rechercherPatient(v),280);
});
function rechercherPatient(q){
    fetch(BASE_URL+'accueil/search-doublon?nom='+encodeURIComponent(q))
        .then(r=>r.json()).then(list=>{
            const box=document.getElementById('searchResultsBox');
            if(!list||!list.length){ box.classList.add('d-none'); return; }
            box.innerHTML=list.map(p=>`
                <div class="sr-item" onclick="selectionnerPatient(${p.id},'${esc(p.nom)} ${esc(p.prenom||'')}','${esc(p.dossier_numero||'')}')">
                    <div class="sr-avatar">${(p.nom||'?').charAt(0).toUpperCase()}</div>
                    <div>
                        <strong>${esc(p.nom)} ${esc(p.prenom||'')}</strong>
                        <small class="text-muted ms-1">${esc(p.dossier_numero||'')}</small><br>
                        <small class="text-muted">${p.nb_consultations||0} consult.</small>
                    </div>
                </div>`).join('');
            box.classList.remove('d-none');
        }).catch(()=>{});
}
function selectionnerPatient(id,nom,dossier){
    document.getElementById('fieldPatientId').value=id;
    document.getElementById('existingPatientName').textContent=nom+' ('+dossier+')';
    document.getElementById('existingPatientBanner').classList.remove('d-none');
    const nf=document.getElementById('newPatientFields');
    nf.style.opacity='.35'; nf.style.pointerEvents='none';
    document.getElementById('searchResultsBox').classList.add('d-none');
    document.getElementById('searchPatientInput').value='';
    updateBtn();
}
document.getElementById('clearPatient').addEventListener('click',function(e){
    e.preventDefault();
    document.getElementById('fieldPatientId').value='';
    document.getElementById('existingPatientBanner').classList.add('d-none');
    const nf=document.getElementById('newPatientFields');
    nf.style.opacity='1'; nf.style.pointerEvents='auto';
    updateBtn();
});
document.addEventListener('click',e=>{
    const box=document.getElementById('searchResultsBox');
    if(!box.contains(e.target)&&e.target!==document.getElementById('searchPatientInput'))
        box.classList.add('d-none');
});

/* ── Triage cliquable ── */
document.querySelectorAll('.triage-card').forEach(card=>{
    card.addEventListener('click',function(){
        document.querySelectorAll('.triage-card').forEach(c=>c.classList.remove('selected'));
        this.classList.add('selected');
        const radio=this.querySelector('input[type=radio]');
        if(radio) radio.checked=true;
    });
});

/* ── Médecin ── */
function selectMedecin(id, nom) {
    const current = document.getElementById('fieldMedecinId').value;
    // Clic sur le médecin déjà sélectionné = désélectionner
    if (current == id) {
        document.getElementById('fieldMedecinId').value = '';
        document.querySelectorAll('.med-chip').forEach(c => c.classList.remove('selected'));
        document.getElementById('bandeauFileCommunne').style.display = 'flex';
    } else {
        document.getElementById('fieldMedecinId').value = id;
        document.querySelectorAll('.med-chip').forEach(c => c.classList.remove('selected'));
        document.querySelector(`.med-chip[data-id="${id}"]`)?.classList.add('selected');
        document.getElementById('bandeauFileCommunne').style.display = 'none';
    }
    updateBtn();
}

/* ── État bouton — actif dès qu'un nom ou un patient existant est présent ── */
function updateBtn() {
    const ok = document.getElementById('fieldPatientId').value.trim() !== '' ||
               document.getElementById('fieldNom').value.trim() !== '';
    document.getElementById('btnEnreg').disabled = !ok;
}
document.getElementById('fieldNom').addEventListener('input', updateBtn);

/* ── Enregistrement ── */
function enregistrerPatient() {
    const btn  = document.getElementById('btnEnreg');
    const form = document.getElementById('formEnregPatient');
    const pid  = document.getElementById('fieldPatientId').value;
    const nom  = document.getElementById('fieldNom').value.trim();
    if (!pid && !nom) { showError('Veuillez saisir le nom du patient.'); return; }

    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Enregistrement…';

    fetch(BASE_URL + 'urgences/enregistrer-patient', { method: 'POST', body: new FormData(form) })
        .then(r => r.json()).then(data => {
            if (data.success) {
                const dest = data.medecin_nom?.trim()
                    ? 'Patient ajouté dans la file de Dr ' + data.medecin_nom
                    : 'Patient ajouté dans la file commune — tous les médecins disponibles ont été notifiés';
                showSuccess(data.nom + ' ' + data.prenom + ' — ' + dest);
                form.reset();
                document.getElementById('fieldPatientId').value = '';
                document.getElementById('existingPatientBanner').classList.add('d-none');
                const nf = document.getElementById('newPatientFields');
                nf.style.opacity = '1'; nf.style.pointerEvents = 'auto';
                document.querySelectorAll('.med-chip').forEach(c => c.classList.remove('selected'));
                document.querySelectorAll('.triage-card').forEach(c => c.classList.remove('selected'));
                document.querySelector('.triage-card.tr3')?.classList.add('selected');
                document.getElementById('bandeauFileCommunne').style.display = 'flex';
                updateBtn();
                setTimeout(() => location.reload(), 2500);
            } else if (data.doublon) {
                afficherDoublons(data.doublons || [], data.message);
            } else {
                showError(data.message || 'Erreur lors de l\'enregistrement.');
            }
        })
        .catch(() => showError('Erreur réseau.'))
        .finally(() => {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-send-fill"></i> Enregistrer le patient';
            updateBtn();
        });
}

/* ── Gestion doublons ── */
function afficherDoublons(doublons, msg) {
    const panel = document.getElementById('doublonWarningPanel');
    const title = document.getElementById('doublonWarningTitle');
    const list  = document.getElementById('doublonWarningList');

    title.textContent = msg || 'Patients similaires trouvés — vérifiez avant de créer';

    list.innerHTML = doublons.map(d => {
        const color  = d.niveau_color || '#f59e0b';
        const label  = d.niveau_label || d.niveau || '';
        const age    = d.age ? ' · ' + d.age + ' ans' : '';
        const dossier = d.dossier_numero ? ' · ' + d.dossier_numero : '';
        return `<div style="display:flex;align-items:center;gap:8px;background:#fff;
                            border:1px solid #e2e8f0;border-radius:8px;padding:7px 10px;">
                    <span style="width:8px;height:8px;border-radius:50%;background:${color};
                                 flex-shrink:0;display:inline-block;"></span>
                    <span style="font-size:.82rem;font-weight:700;color:#1e293b;">
                        ${d.nom || ''} ${d.prenom || ''}
                    </span>
                    <span style="font-size:.78rem;color:#64748b;">${dossier}${age}</span>
                    <span style="margin-left:auto;font-size:.72rem;font-weight:700;
                                 color:${color};text-transform:uppercase;">${label}</span>
                </div>`;
    }).join('');

    panel.style.display = 'block';
    panel.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

function cacherDoublons() {
    document.getElementById('doublonWarningPanel').style.display = 'none';
}

function forcerCreation() {
    cacherDoublons();
    const btn  = document.getElementById('btnEnreg');
    const form = document.getElementById('formEnregPatient');

    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Création en cours…';

    const fd = new FormData(form);
    fd.append('force_creation', '1');

    fetch(BASE_URL + 'urgences/enregistrer-patient', { method: 'POST', body: fd })
        .then(r => r.json()).then(data => {
            if (data.success) {
                const dest = data.medecin_nom?.trim()
                    ? 'Patient ajouté dans la file de Dr ' + data.medecin_nom
                    : 'Patient ajouté dans la file commune — tous les médecins disponibles ont été notifiés';
                showSuccess(data.nom + ' ' + data.prenom + ' — ' + dest);
                form.reset();
                document.getElementById('fieldPatientId').value = '';
                document.getElementById('existingPatientBanner').classList.add('d-none');
                const nf = document.getElementById('newPatientFields');
                nf.style.opacity = '1'; nf.style.pointerEvents = 'auto';
                document.querySelectorAll('.med-chip').forEach(c => c.classList.remove('selected'));
                document.querySelectorAll('.triage-card').forEach(c => c.classList.remove('selected'));
                document.querySelector('.triage-card.tr3')?.classList.add('selected');
                document.getElementById('bandeauFileCommunne').style.display = 'flex';
                updateBtn();
                setTimeout(() => location.reload(), 2500);
            } else {
                showError(data.message || 'Erreur lors de l\'enregistrement.');
            }
        })
        .catch(() => showError('Erreur réseau.'))
        .finally(() => {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-send-fill"></i> Enregistrer le patient';
            updateBtn();
        });
}

/* ── Filtre journée ── */
function filtrerJournee(q){
    document.querySelectorAll('#tableJournee tbody tr').forEach(tr=>{
        tr.style.display=(tr.dataset.search||'').includes(q.toLowerCase())?'':'none';
    });
}

/* ── Transfert lit ── */
function ouvrirTransfert(pid,nom,hospId){
    document.getElementById('transfertPatientId').value=pid;
    document.getElementById('transfertHospId').value=hospId;
    document.getElementById('transfertPatientNom').textContent=nom;
    document.getElementById('transfertServiceId').value='';
    document.getElementById('transfertLitId').innerHTML='<option value="">— Choisir un lit —</option>';
    document.getElementById('transfertLitBlock').style.display='none';
    document.getElementById('transfertMotif').value='';
    new bootstrap.Modal(document.getElementById('modalTransfert')).show();
}
function chargerLitsTransfert(serviceId){
    const lb=document.getElementById('transfertLitBlock'),ls=document.getElementById('transfertLitId'),nl=document.getElementById('transfertNoLit');
    if(!serviceId){lb.style.display='none';return;}
    ls.innerHTML='<option value="">Chargement…</option>'; lb.style.display='block'; nl.style.display='none';
    fetch(BASE_URL+'hospitalisation/lits-disponibles?service_id='+serviceId)
        .then(r=>r.json()).then(lits=>{
            if(!lits||!lits.length){ ls.innerHTML='<option value="">Aucun lit disponible</option>'; nl.style.display='block'; }
            else { ls.innerHTML='<option value="">— Choisir un lit —</option>'+lits.map(l=>`<option value="${l.id}">${esc(l.nom_chambre||'')} - ${esc(l.nom_lit)}</option>`).join(''); }
        }).catch(()=>{ ls.innerHTML='<option value="">Erreur</option>'; });
}
function validerTransfert(){
    const sid=document.getElementById('transfertServiceId').value;
    const motif=document.getElementById('transfertMotif').value.trim();
    if(!sid){showError('Veuillez choisir un service.');return;}
    if(!motif){showError('Veuillez indiquer le motif.');return;}
    const fd=new FormData();
    fd.append('patient_id',document.getElementById('transfertPatientId').value);
    fd.append('service_id',sid);
    const lid=document.getElementById('transfertLitId').value;
    if(lid) fd.append('lit_id',lid);
    fd.append('motif',motif);
    bootstrap.Modal.getInstance(document.getElementById('modalTransfert')).hide();
    fetch(BASE_URL+'urgences/transferer-lit',{method:'POST',body:fd})
        .then(r=>r.json()).then(d=>{ if(d.success){showSuccess('Transfert effectué.');setTimeout(()=>location.reload(),1800);} else showError(d.message||'Échec.'); })
        .catch(()=>showError('Erreur réseau.'));
}

/* ── Libération lit ── */
function confirmerLiberation(hospId,nom){
    document.getElementById('liberationHospId').value=hospId;
    document.getElementById('liberationPatientNom').textContent=nom;
    new bootstrap.Modal(document.getElementById('modalLiberation')).show();
}
function validerLiberation(){
    const btn=document.getElementById('btnConfirmLiberation');
    btn.disabled=true; btn.innerHTML='<span class="spinner-border spinner-border-sm me-1"></span>…';
    const fd=new FormData(); fd.append('hosp_id',document.getElementById('liberationHospId').value);
    bootstrap.Modal.getInstance(document.getElementById('modalLiberation')).hide();
    fetch(BASE_URL+'urgences/liberer-lit',{method:'POST',body:fd})
        .then(r=>r.json()).then(d=>{ if(d.success){showSuccess('Lit libéré.');setTimeout(()=>location.reload(),1800);} else showError(d.message||'Échec.'); })
        .catch(()=>showError('Erreur réseau.'))
        .finally(()=>{ btn.disabled=false; btn.innerHTML='<i class="bi bi-check-circle me-1"></i>Confirmer'; });
}

/* ── Helpers ── */
function esc(s){ return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }

/* ══════════════════════════════════════════════════
   ACTUALISATION INTELLIGENTE
   - Intervalle : 3 minutes
   - Pause automatique si l'utilisateur est en train
     de saisir (formulaire non vide)
   - Indicateur visuel dans la barre du haut
══════════════════════════════════════════════════ */
(function() {
    const INTERVAL = 3 * 60 * 1000; // 3 min
    let timer = null;
    let lastActivity = Date.now();
    let countdown = INTERVAL / 1000;
    let countdownTimer = null;

    // Champs du formulaire d'enregistrement
    const formFields = document.querySelectorAll('#formEnregPatient input, #formEnregPatient textarea, #formEnregPatient select');

    function formHasContent() {
        return !!(
            document.getElementById('fieldPatientId').value ||
            document.getElementById('fieldNom').value.trim() ||
            document.querySelector('[name="motif"]')?.value.trim()
        );
    }

    function resetActivity() {
        lastActivity = Date.now();
    }

    // Créer l'indicateur dans la topbar
    const indicator = document.createElement('div');
    indicator.id = 'refreshIndicator';
    indicator.style.cssText = 'display:flex;align-items:center;gap:5px;font-size:.65rem;color:#64748b;background:rgba(255,255,255,.07);border:1px solid rgba(255,255,255,.1);border-radius:20px;padding:3px 10px;';
    indicator.innerHTML = '<i class="bi bi-arrow-clockwise" id="riIcon"></i><span id="riText">3:00</span>';
    const userDiv = document.querySelector('.sau-user');
    if (userDiv) userDiv.insertBefore(indicator, userDiv.firstChild);

    function updateIndicator(secs, paused) {
        const el = document.getElementById('riText');
        const ic = document.getElementById('riIcon');
        if (!el) return;
        if (paused) {
            indicator.style.color = '#f59e0b';
            el.textContent = 'En pause';
            ic.className = 'bi bi-pause-circle-fill';
        } else {
            indicator.style.color = '#94a3b8';
            const m = Math.floor(secs / 60);
            const s = secs % 60;
            el.textContent = m + ':' + String(s).padStart(2,'0');
            ic.className = 'bi bi-arrow-clockwise';
        }
    }

    function scheduleRefresh() {
        clearTimeout(timer);
        clearInterval(countdownTimer);
        countdown = INTERVAL / 1000;

        countdownTimer = setInterval(() => {
            if (formHasContent()) {
                updateIndicator(0, true);
                return; // pause sans décrémenter
            }
            countdown--;
            updateIndicator(countdown, false);
            if (countdown <= 0) {
                clearInterval(countdownTimer);
            }
        }, 1000);

        timer = setTimeout(() => {
            if (formHasContent()) {
                // Reprogrammer dans 30s tant que le formulaire est rempli
                scheduleRefresh();
                return;
            }
            location.reload();
        }, INTERVAL);
    }

    // Écouter la saisie pour réinitialiser l'activité
    formFields.forEach(el => el.addEventListener('input', resetActivity));
    document.addEventListener('keydown', resetActivity);

    scheduleRefresh();
})();
</script>
<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
