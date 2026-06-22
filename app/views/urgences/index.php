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
/* ══ COCKPIT INFIRMIER — REFONTE ══════════════════════════════════════════════
   Palette triage constante cross-thème
══════════════════════════════════════════════════════════════════════════════ */
:root{
  --p1:#e11d48;--p1-soft:#fff1f3;
  --p2:#ea580c;--p2-soft:#fff4ec;
  --p3:#ca8a04;--p3-soft:#fefce8;
  --p4:#16a34a;--p4-soft:#f0fdf4;
  --p5:#2563eb;--p5-soft:#eff6ff;
  --radius:14px;
}
[data-theme="light"]{
  --bg:#eef1f6;--surface:#ffffff;--surface-2:#f7f9fc;--surface-3:#eef2f7;
  --header:#ffffff;--header-ink:#0f172a;
  --ink:#0f172a;--ink-2:#475569;--ink-3:#94a3b8;
  --line:#e2e8f0;--line-2:#eef2f7;
  --primary:#1d4ed8;--primary-ink:#ffffff;
  --shadow:0 1px 2px rgba(15,23,42,.04),0 8px 24px rgba(15,23,42,.06);
  --shadow-sm:0 1px 2px rgba(15,23,42,.05);
}
[data-theme="dark"]{
  --bg:#080c16;--surface:#111726;--surface-2:#0d1220;--surface-3:#1a2236;
  --header:#0c1120;--header-ink:#e9eef7;
  --ink:#eef2fb;--ink-2:#9aa7bd;--ink-3:#5d6b85;
  --line:#212a40;--line-2:#1a2236;
  --primary:#3b82f6;--primary-ink:#ffffff;
  --shadow:0 1px 2px rgba(0,0,0,.4),0 10px 30px rgba(0,0,0,.45);
  --shadow-sm:0 1px 2px rgba(0,0,0,.4);
}
*{box-sizing:border-box;}
body{
  font-family:'Segoe UI',system-ui,sans-serif;
  background:var(--bg);color:var(--ink);
  -webkit-font-smoothing:antialiased;
  transition:background .3s,color .3s;
  margin:0;
}

/* ── TOOLBAR DIRECTION/THÈME/VUE ──────────────────────────────────────────── */
.ck-toolbar{
  position:sticky;top:0;z-index:60;
  display:flex;align-items:center;gap:12px;flex-wrap:wrap;
  padding:7px 16px;background:#fff;color:#334155;
  border-bottom:1px solid #e2e8f0;font-size:12px;
  box-shadow:0 1px 4px rgba(15,23,42,.06);
}
[data-theme="dark"] .ck-toolbar{background:#0d1220;color:#9aa7bd;border-bottom-color:#212a40;box-shadow:none;}
.ck-toolbar .ck-tb-title{font-weight:700;color:#1e293b;display:flex;align-items:center;gap:7px;}
[data-theme="dark"] .ck-toolbar .ck-tb-title{color:#e2e8f0;}
.ck-toolbar .ck-tb-title i{color:#2563eb;}
.ck-toolbar .spacer{flex:1;}
.ck-toolbar .grp{display:flex;align-items:center;gap:6px;}
.ck-toolbar .grp span{color:#94a3b8;font-size:10.5px;text-transform:uppercase;letter-spacing:.07em;font-weight:600;}
.ck-seg{display:inline-flex;background:#f1f5f9;border:1px solid #e2e8f0;border-radius:8px;padding:2px;gap:2px;}
[data-theme="dark"] .ck-seg{background:#1a2236;border-color:#2a3550;}
.ck-seg button{
  font-family:inherit;font-size:11.5px;font-weight:600;cursor:pointer;
  border:none;background:transparent;color:#64748b;padding:4px 10px;border-radius:5px;transition:all .15s;white-space:nowrap;
}
.ck-seg button:hover{color:#1e293b;background:#e2e8f0;}
[data-theme="dark"] .ck-seg button:hover{color:#e2e8f0;background:#232c45;}
.ck-seg button.on{background:#2563eb;color:#fff;box-shadow:0 1px 4px rgba(37,99,235,.35);}
.ck-seg.dirs button.on{background:#059669;box-shadow:0 1px 4px rgba(5,150,105,.35);}

/* ── HEADER ──────────────────────────────────────────────────────────────── */
.ck-hdr{
  position:sticky;top:37px;z-index:40;
  background:var(--header);border-bottom:1px solid var(--line);
  padding:10px 20px;display:flex;align-items:center;gap:10px;
  box-shadow:var(--shadow-sm);transition:background .3s,border-color .3s;
}
[data-dir="cockpit"] .ck-hdr{background:linear-gradient(100deg,var(--surface) 0%,var(--surface-2) 100%);}

/* Brand */
.ck-brand{display:flex;align-items:center;gap:11px;flex-shrink:0;}
.ck-logo{
  width:40px;height:40px;border-radius:10px;flex-shrink:0;
  background:linear-gradient(135deg,#dc2626,#2563eb);
  display:flex;align-items:center;justify-content:center;color:#fff;font-size:18px;
  box-shadow:0 3px 10px rgba(37,99,235,.25);
}
.ck-brand h1{margin:0;font-size:14.5px;font-weight:800;letter-spacing:-.02em;color:var(--ink);white-space:nowrap;}
.ck-brand .ck-sub{font-size:11px;color:var(--ink-3);font-weight:600;margin-top:1px;white-space:nowrap;}

/* KPIs — centrés automatiquement via margin auto dans le HTML */
.ck-kpis{display:flex;gap:6px;flex-wrap:nowrap;}
.ck-kpi{
  display:flex;align-items:center;gap:6px;padding:5px 10px;border-radius:9px;
  background:var(--surface-2);border:1px solid var(--line);white-space:nowrap;
  cursor:default;
}
.ck-kpi .dot{width:7px;height:7px;border-radius:50%;flex-shrink:0;}
.ck-kpi .v{font-weight:800;font-size:13.5px;color:var(--ink);font-family:monospace;}
.ck-kpi .l{font-size:10px;color:var(--ink-2);font-weight:600;text-transform:uppercase;letter-spacing:.04em;}
.ck-kpi.crit{background:var(--p1-soft);border-color:color-mix(in srgb,var(--p1) 25%,transparent);}
[data-theme="dark"] .ck-kpi.crit{background:color-mix(in srgb,var(--p1) 16%,var(--surface));}
.ck-kpi.crit .v{color:var(--p1);}

/* Horloge */
.ck-clock{
  font-family:monospace;font-weight:700;font-size:17px;color:var(--ink);
  letter-spacing:.04em;padding:0 6px;flex-shrink:0;
  border-left:1px solid var(--line);padding-left:14px;margin-left:4px;
}
[data-dir="cockpit"] .ck-clock{color:#10b981;}

/* Boutons header */
.ck-hbtn{
  display:inline-flex;align-items:center;gap:6px;font-family:inherit;cursor:pointer;
  font-size:12.5px;font-weight:700;padding:8px 13px;border-radius:9px;white-space:nowrap;
  border:1px solid var(--line);background:var(--surface-2);color:var(--ink);
  transition:all .15s;text-decoration:none;
}
.ck-hbtn:hover{border-color:var(--primary);color:var(--primary);}
.ck-hbtn.primary{background:var(--primary);color:var(--primary-ink);border-color:transparent;
  box-shadow:0 3px 10px rgba(37,99,235,.3);text-decoration:none;}
.ck-hbtn.primary:hover{filter:brightness(1.08);color:#fff;}
.ck-icon-btn{
  width:36px;height:36px;border-radius:9px;border:1px solid var(--line);background:var(--surface-2);
  display:inline-flex;align-items:center;justify-content:center;cursor:pointer;
  color:var(--ink-2);font-size:15px;transition:all .15s;text-decoration:none;flex-shrink:0;
}
.ck-icon-btn:hover{color:var(--primary);border-color:var(--primary);}
.ck-icon-btn.power:hover{color:var(--p1);border-color:var(--p1);}

@media(max-width:900px){
  .ck-kpi .l{display:none;}
  .ck-brand .ck-sub{display:none;}
}

/* ── SUBBAR : SEARCH + TABS ──────────────────────────────────────────────── */
.ck-subbar{
  position:sticky;top:103px;z-index:30;background:var(--bg);
  padding:10px 18px 0;transition:background .3s;
}
.ck-searchrow{display:flex;align-items:center;gap:12px;margin-bottom:10px;position:relative;}
.ck-search{
  flex:1;display:flex;align-items:center;gap:9px;background:var(--surface);border:1px solid var(--line);
  border-radius:10px;padding:9px 14px;box-shadow:var(--shadow-sm);
}
.ck-search i{color:var(--ink-3);font-size:15px;}
.ck-search input{flex:1;border:none;background:transparent;font-family:inherit;font-size:13.5px;color:var(--ink);outline:none;}
.ck-search input::placeholder{color:var(--ink-3);}
.ck-tabs{display:flex;gap:3px;overflow-x:auto;scrollbar-width:none;padding-bottom:10px;border-bottom:1px solid var(--line);}
.ck-tabs::-webkit-scrollbar{display:none;}
.ck-tab{
  display:inline-flex;align-items:center;gap:7px;padding:8px 13px;border-radius:9px;cursor:pointer;
  font-size:13px;font-weight:600;color:var(--ink-2);white-space:nowrap;border:1px solid transparent;transition:all .15s;
  background:transparent;font-family:inherit;
}
.ck-tab:hover{background:var(--surface-2);color:var(--ink);}
.ck-tab.on{background:var(--primary);color:#fff;}
.ck-tab .cnt{
  font-size:10.5px;font-weight:800;background:var(--surface-3);color:var(--ink-2);
  border-radius:20px;padding:1px 7px;min-width:18px;text-align:center;
}
.ck-tab.on .cnt{background:rgba(255,255,255,.22);color:#fff;}
.ck-tab .cnt.warn{background:var(--p2);color:#fff;}

/* ── ACTION ROW ──────────────────────────────────────────────────────────── */
.ck-actionrow{display:flex;align-items:center;gap:9px;padding:12px 18px 4px;}
.ck-actionrow .rh{font-size:12.5px;color:var(--ink-2);font-weight:600;}
.ck-actionrow .rh b{color:var(--ink);}
.ck-actionrow .spacer{flex:1;}
.ck-chiplink{display:inline-flex;align-items:center;gap:6px;font-size:12.5px;font-weight:700;color:var(--p1);cursor:pointer;padding:7px 11px;border-radius:8px;border:none;background:transparent;font-family:inherit;}
.ck-chiplink:hover{background:var(--p1-soft);}

/* ── URGENCES CARD GRID ─────────────────────────────────────────────────── */
.ck-board{padding:8px 18px 0;}
.ck-grid{display:grid;gap:13px;grid-template-columns:repeat(auto-fill,minmax(285px,1fr));}
[data-dir="dense"] .ck-grid{gap:9px;grid-template-columns:repeat(auto-fill,minmax(230px,1fr));}

.ck-card{
  position:relative;background:var(--surface);border:1px solid var(--line);
  border-radius:var(--radius);padding:13px 14px 12px 17px;box-shadow:var(--shadow);
  overflow:hidden;transition:transform .12s,box-shadow .2s,background .3s;
}
.ck-card:hover{transform:translateY(-2px);box-shadow:0 6px 20px rgba(15,23,42,.12);}
[data-dir="dense"] .ck-card{padding:10px 11px 10px 14px;border-radius:11px;}
.ck-card::before{content:'';position:absolute;left:0;top:0;bottom:0;width:5px;background:var(--tri);}
[data-dir="cockpit"] .ck-card{border-color:color-mix(in srgb,var(--tri) 35%,var(--line));}
[data-dir="cockpit"] .ck-card::before{width:6px;box-shadow:0 0 14px var(--tri);}

.ck-card .c-top{display:flex;align-items:flex-start;justify-content:space-between;gap:7px;margin-bottom:8px;}
.ck-tri-chip{
  display:inline-flex;align-items:center;gap:5px;font-size:10.5px;font-weight:800;letter-spacing:.03em;
  color:#fff;background:var(--tri);padding:3px 8px;border-radius:6px;text-transform:uppercase;
}
.ck-c-time{text-align:right;font-size:10.5px;color:var(--ink-3);font-weight:600;line-height:1.3;}
.ck-c-time .wait{display:block;color:var(--ink-2);font-weight:700;}
.ck-c-time .wait.long{color:var(--p2);}
.ck-c-name{font-size:15px;font-weight:800;color:var(--ink);letter-spacing:-.01em;line-height:1.2;}
[data-dir="dense"] .ck-c-name{font-size:13px;}
.ck-c-demo{font-size:11.5px;color:var(--ink-3);font-weight:600;margin-top:2px;}
.ck-c-motif{
  font-size:12px;font-weight:700;color:var(--tri);margin-top:7px;
  display:flex;align-items:center;gap:5px;text-transform:uppercase;letter-spacing:.02em;
}
[data-theme="dark"] .ck-c-motif{color:color-mix(in srgb,var(--tri) 75%,#fff);}
.ck-vitals{
  display:flex;align-items:stretch;gap:1px;margin-top:10px;background:var(--line-2);
  border:1px solid var(--line);border-radius:9px;overflow:hidden;
}
.ck-vit{flex:1;background:var(--surface-2);padding:5px 3px;text-align:center;min-width:0;}
.ck-vit .vl{font-size:8.5px;color:var(--ink-3);font-weight:700;text-transform:uppercase;letter-spacing:.04em;}
.ck-vit .vv{font-size:13px;font-weight:800;color:var(--ink);font-family:monospace;margin-top:1px;}
.ck-vit .vv.muted{color:var(--ink-3);font-weight:600;}
.ck-vit .vv.warn{color:var(--p2);}
.ck-vit .vv.bad{color:var(--p1);}
.ck-news{
  flex-shrink:0;display:flex;flex-direction:column;align-items:center;justify-content:center;
  padding:5px 11px;background:var(--news-bg,var(--surface-3));color:var(--news-ink,var(--ink));
}
.ck-news .nl{font-size:8px;font-weight:800;letter-spacing:.06em;opacity:.85;}
.ck-news .nv{font-size:17px;font-weight:800;font-family:monospace;line-height:1;}
.ck-c-foot{display:flex;gap:7px;margin-top:11px;}
[data-dir="dense"] .ck-c-foot{margin-top:8px;}
.ck-cbtn{
  flex:1;display:inline-flex;align-items:center;justify-content:center;gap:6px;cursor:pointer;
  font-family:inherit;font-size:12.5px;font-weight:700;padding:8px;border-radius:8px;transition:all .15s;text-decoration:none;
}
.ck-cbtn.ghost{background:var(--surface-2);border:1px solid var(--line);color:var(--ink-2);flex:0 0 auto;padding:8px 12px;}
.ck-cbtn.ghost:hover{color:var(--primary);border-color:var(--primary);}
.ck-cbtn.main{background:var(--primary);color:var(--primary-ink);border:1px solid transparent;}
.ck-cbtn.main:hover{filter:brightness(1.08);}
[data-dir="dense"] .ck-cbtn{font-size:11.5px;padding:7px;}

/* ── LIST VIEW ──────────────────────────────────────────────────────────── */
.ck-listwrap{padding:8px 18px 0;}
.ck-ltable{width:100%;border-collapse:separate;border-spacing:0;background:var(--surface);border:1px solid var(--line);border-radius:var(--radius);overflow:hidden;box-shadow:var(--shadow);}
.ck-ltable thead th{
  background:var(--surface-2);text-align:left;font-size:10.5px;font-weight:700;color:var(--ink-2);
  text-transform:uppercase;letter-spacing:.05em;padding:10px 13px;border-bottom:1px solid var(--line);white-space:nowrap;
}
.ck-ltable tbody td{padding:10px 13px;border-bottom:1px solid var(--line-2);font-size:13px;vertical-align:middle;}
.ck-ltable tbody tr:last-child td{border-bottom:none;}
.ck-ltable tbody tr:hover{background:var(--surface-2);}
.ck-ltable .lt-tri{width:5px;padding:0!important;background:var(--tri);}
.lt-name{font-weight:800;color:var(--ink);}
.lt-demo{font-size:11px;color:var(--ink-3);font-weight:600;}
.lt-motif{font-weight:700;color:var(--tri);font-size:12px;text-transform:uppercase;letter-spacing:.02em;}
[data-theme="dark"] .lt-motif{color:color-mix(in srgb,var(--tri) 78%,#fff);}
.lt-tag{display:inline-block;font-size:10px;font-weight:800;color:#fff;background:var(--tri);padding:2px 7px;border-radius:5px;text-transform:uppercase;}
.lt-news{display:inline-flex;flex-direction:column;align-items:center;padding:2px 9px;border-radius:7px;font-family:monospace;}
.lt-news .nv{font-size:14px;font-weight:800;line-height:1;}
.lt-news .nl{font-size:7.5px;font-weight:700;letter-spacing:.05em;}
.lt-vit{font-family:monospace;font-weight:700;color:var(--ink);}
.lt-vit .muted{color:var(--ink-3);font-weight:500;}
.lt-vit .warn{color:var(--p2);}
.lt-vit .bad{color:var(--p1);}
.lt-actions{display:flex;gap:6px;justify-content:flex-end;}

/* body data-view switch */
body[data-view="list"] .ck-board{display:none;}
body[data-view="cards"] .ck-listwrap{display:none;}

/* ── PANNEAUX ONGLETS ────────────────────────────────────────────────────── */
/* Désactiver le système Bootstrap tab — switching géré en JS custom */
.tab-pane { display: none !important; }
.tab-pane.active { display: block !important; }
.tab-content-area { padding: 22px; min-height: calc(100vh - 140px); }

/* ══ STYLES PANNEAUX 2-7 (inchangés) ════════════════════════════════════ */
.hosp-alert-bar { background: linear-gradient(135deg, #fff7ed, #fffbeb); border: 2px solid #f59e0b; border-radius: 14px; padding: 14px 18px; margin-bottom: 18px; display: flex; align-items: center; gap: 12px; animation: pulse-amber 2s infinite; }
@keyframes pulse-amber { 0%,100%{box-shadow:0 0 0 #f59e0b} 50%{box-shadow:0 0 16px rgba(245,158,11,.45)} }
.hosp-card { background: var(--surface,#fff); border-radius: 12px; padding: 16px; border: 1px solid var(--line,#e2e8f0); box-shadow: 0 2px 8px rgba(0,0,0,.05); margin-bottom: 12px; display: flex; align-items: center; gap: 14px; transition: all .2s; }
.hosp-card:hover { box-shadow: 0 8px 24px rgba(0,0,0,.1); transform: translateY(-2px); }
.hosp-avatar { width: 48px; height: 48px; border-radius: 50%; background: linear-gradient(135deg, #f59e0b, #d97706); color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 1.05rem; flex-shrink: 0; }
.urgence-tag { background: #fef3c7; color: #92400e; border: 1px solid #fcd34d; border-radius: 8px; padding: 1px 7px; font-size: 0.68rem; font-weight: 700; }
.ext-tag    { background: #eff6ff; color: #1e40af; border: 1px solid #bfdbfe; border-radius: 8px; padding: 1px 7px; font-size: 0.68rem; font-weight: 700; }
.service-section { margin-bottom: 32px; }
.service-header { background: linear-gradient(135deg, #1e293b, #334155); color: #fff; border-radius: 14px 14px 0 0; padding: 13px 20px; display: flex; align-items: center; justify-content: space-between; }
.service-cards-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 14px; padding: 16px; background: var(--surface-2,#f8fafc); border: 1px solid var(--line,#e2e8f0); border-top: none; border-radius: 0 0 14px 14px; }
.pat-card { background: var(--surface,#fff); border-radius: 14px; border: 1px solid var(--line,#e2e8f0); box-shadow: 0 2px 10px rgba(0,0,0,.05); overflow: hidden; transition: all .2s ease; }
.pat-card:hover { transform: translateY(-3px); box-shadow: 0 8px 24px rgba(0,0,0,.1); border-color: #93c5fd; }
.pat-card-top { padding: 14px 16px 10px; display: flex; align-items: center; gap: 12px; }
.inf-avatar { width: 44px; height: 44px; border-radius: 50%; background: linear-gradient(135deg, #1e40af, #3b82f6); color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 0.9rem; flex-shrink: 0; }
.pat-card-info { flex-grow: 1; min-width: 0; }
.pat-card-name { font-weight: 800; font-size: .9rem; color: var(--ink,#1e293b); line-height: 1.2; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.pat-card-meta { font-size: .72rem; color: var(--ink-2,#64748b); margin-top: 2px; }
.pat-card-progress { padding: 0 16px 10px; }
.progress-bar-wrap { height: 6px; border-radius: 4px; background: var(--line,#e2e8f0); overflow: hidden; }
.progress-fill { height: 100%; border-radius: 4px; transition: width .3s; }
.progress-fill.done { background: #22c55e; } .progress-fill.partial { background: #f59e0b; } .progress-fill.low { background: #ef4444; }
.pat-card-actions { padding: 10px 12px 12px; border-top: 1px solid var(--line-2,#f1f5f9); display: flex; gap: 7px; flex-wrap: wrap; align-items: center; }
.act-btn { display: inline-flex; align-items: center; gap: 5px; padding: 7px 13px; border-radius: 10px; font-size: .78rem; font-weight: 700; text-decoration: none; border: none; cursor: pointer; transition: all .15s; white-space: nowrap; }
.act-btn-soins     { background: #16a34a; color: #fff; } .act-btn-soins:hover { background: #15803d; color: #fff; }
.act-btn-plan      { background: #eff6ff; color: #1e40af; border: 1.5px solid #bfdbfe; } .act-btn-plan:hover { background: #dbeafe; color: #1e40af; }
.act-btn-constantes{ background: #ecfdf5; color: #065f46; border: 1.5px solid #6ee7b7; } .act-btn-constantes:hover { background: #d1fae5; color: #065f46; }
.act-btn-prescription { background: #fdf4ff; color: #7e22ce; border: 1.5px solid #d8b4fe; } .act-btn-prescription:hover { background: #f3e8ff; color: #6b21a8; }
.act-btn-suivi     { background: #fffbeb; color: #92400e; border: 1.5px solid #fcd34d; } .act-btn-suivi:hover { background: #fef3c7; color: #92400e; }
.act-btn-more      { background: var(--surface-2,#f1f5f9); color: var(--ink-2,#475569); margin-left: auto; padding: 7px 10px; border-radius: 10px; } .act-btn-more:hover { background: var(--line,#e2e8f0); }
.act-btn-liberer   { background: #fff1f2; color: #be123c; border: 1.5px solid #fda4af; border-radius: 10px; padding: 7px 13px; } .act-btn-liberer:hover { background: #ffe4e6; color: #9f1239; }
.act-btn-transferer { background: #f5f3ff; color: #5b21b6; border: 1.5px solid #c4b5fd; border-radius: 10px; padding: 7px 13px; } .act-btn-transferer:hover { background: #ede9fe; color: #4c1d95; }
.douleur-strip { padding: 2px 16px 9px; display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
.douleur-badge { display: inline-flex; align-items: center; gap: 4px; padding: 3px 10px; border-radius: 20px; font-size: .7rem; font-weight: 700; white-space: nowrap; }
.douleur-absent{background:#f0fdf4;color:#15803d;border:1px solid #86efac;} .douleur-leger{background:#f0fdf4;color:#15803d;border:1px solid #86efac;}
.douleur-modere{background:#fffbeb;color:#92400e;border:1px solid #fcd34d;} .douleur-intense{background:#fff1f2;color:#be123c;border:1px solid #fda4af;}
.douleur-severe{background:#7f1d1d;color:#fff;border:1px solid #991b1b;} .douleur-default{background:var(--surface-2,#f1f5f9);color:var(--ink-2,#475569);border:1px solid var(--line,#cbd5e1);}
.lits-overview { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 14px; }
.lit-service-card { background: var(--surface,#fff); border-radius: 12px; padding: 18px; border: 1px solid var(--line,#e2e8f0); box-shadow: 0 2px 8px rgba(0,0,0,.04); text-align: center; }
.lit-donut-wrap { position: relative; width: 80px; height: 80px; margin: 0 auto 10px; }
.lit-donut-wrap svg { transform: rotate(-90deg); }
.lit-donut-text { position: absolute; top: 50%; left: 50%; transform: translate(-50%,-50%); }
.lit-donut-text .num { font-size: 1.2rem; font-weight: 800; line-height: 1; }
.lit-donut-text .lbl { font-size: 0.52rem; color: var(--ink-2,#64748b); text-transform: uppercase; }
.modal-assign .modal-header { background: linear-gradient(135deg, #0f172a, #1e40af); color: #fff; }
.lit-select-item { display: flex; align-items: center; gap: 10px; padding: 9px 12px; border: 1px solid var(--line,#e2e8f0); border-radius: 9px; margin-bottom: 7px; cursor: pointer; transition: .15s; }
.lit-select-item:hover, .lit-select-item.selected { border-color: #3b82f6; background: #eff6ff; }
.empty-state { text-align: center; padding: 60px 20px; color: var(--ink-3,#94a3b8); }
.empty-state i { font-size: 3rem; margin-bottom: 12px; display: block; }
.dropdown-menu { border: none !important; box-shadow: 0 8px 24px rgba(0,0,0,.12) !important; }
.dropdown-item:hover { background: var(--surface-2,#f1f5f9); }
.dropdown-header { font-size: .65rem !important; letter-spacing: .5px; text-transform: uppercase; }
.provenance-arrow { font-size: .7rem; color: var(--ink-3,#94a3b8); }
@keyframes slideIn  { from { transform: translateY(20px); opacity:0; } to { transform:translateY(0);opacity:1; } }
@keyframes timerBlink { 0%,100% { opacity:1; } 50% { opacity:.4; } }
.wait-timer { display:inline-block; border-radius:8px; padding:1px 6px; font-size:.72rem; font-weight:800; }
.wait-timer.timer-blink { animation: timerBlink 1.2s ease-in-out infinite; }

/* search dropdown */
.csr-item{display:flex;align-items:center;gap:10px;padding:9px 10px;border-radius:10px;cursor:pointer;transition:background .12s;border:1.5px solid transparent;text-decoration:none;color:inherit;}
.csr-item:hover,.csr-item.csr-active{background:#f0f9ff;border-color:#bae6fd;text-decoration:none;color:inherit;}
.csr-avatar{width:38px;height:38px;border-radius:50%;flex-shrink:0;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:.82rem;color:#fff;}
.csr-name{font-weight:700;font-size:.87rem;color:var(--ink,#1e293b);line-height:1.2;}
.csr-meta{font-size:.72rem;color:var(--ink-2,#64748b);}
.csr-badge{font-size:.65rem;font-weight:700;padding:2px 7px;border-radius:8px;}
.csr-badge.urgences{background:#fee2e2;color:#b91c1c;} .csr-badge.attente{background:#fef3c7;color:#92400e;}
.csr-badge.hospitalise{background:#dcfce7;color:#166534;} .csr-badge.consultation{background:#eff6ff;color:#1e40af;}
.csr-badge.default{background:var(--surface-2,#f1f5f9);color:var(--ink-2,#475569);}
.csr-actions{display:flex;gap:5px;margin-left:auto;flex-shrink:0;}
.csr-btn{padding:3px 9px;border-radius:20px;font-size:.7rem;font-weight:700;border:1.5px solid;cursor:pointer;white-space:nowrap;text-decoration:none;display:inline-flex;align-items:center;gap:3px;transition:all .12s;}
.csr-btn-dossier{border-color:#3b82f6;color:#3b82f6;background:#eff6ff;} .csr-btn-dossier:hover{background:#3b82f6;color:#fff;}
.csr-btn-consult{border-color:#0d9488;color:#0d9488;background:#f0fdfa;} .csr-btn-consult:hover{background:#0d9488;color:#fff;}
.csr-btn-param{border-color:#f59e0b;color:#b45309;background:#fffbeb;} .csr-btn-param:hover{background:#f59e0b;color:#fff;}
</style>

<!-- ══ TOOLBAR DIRECTION / THÈME / VUE ══ -->
<div class="ck-toolbar">
  <span class="ck-tb-title"><i class="bi bi-easel2-fill"></i> Cockpit Infirmier</span>
  <div class="spacer"></div>
  <div class="grp"><span>Direction</span>
    <div class="ck-seg dirs" id="ckSegDir">
      <button data-dir="sobre" class="on">A · Sobre</button>
      <button data-dir="cockpit">B · Cockpit</button>
      <button data-dir="dense">C · Dense</button>
    </div>
  </div>
  <div class="grp"><span>Thème</span>
    <div class="ck-seg" id="ckSegTheme">
      <button data-theme="light" class="on">Clair</button>
      <button data-theme="dark">Sombre</button>
    </div>
  </div>
  <div class="grp"><span>Vue</span>
    <div class="ck-seg" id="ckSegView">
      <button data-view="cards" class="on">Cartes</button>
      <button data-view="list">Liste</button>
    </div>
  </div>
</div>

<!-- ══ HEADER PRINCIPAL ══ -->
<div id="ckApp" data-dir="sobre">
<header class="ck-hdr">

  <!-- Bloc gauche : marque -->
  <div class="ck-brand">
    <div class="ck-logo"><i class="bi bi-heart-pulse-fill"></i></div>
    <div>
      <h1>Cockpit Infirmier</h1>
      <div class="ck-sub">
        <?= htmlspecialchars($_SESSION['nom_complet'] ?? ($_SESSION['user_nom'] ?? 'Infirmier')) ?>
        &nbsp;·&nbsp; Urgences
      </div>
    </div>
  </div>

  <!-- KPIs — centrés -->
  <div class="ck-kpis" style="margin:0 auto;">
    <div class="ck-kpi crit" title="Patients P1 — vitaux">
      <span class="dot" style="background:var(--p1)"></span>
      <span class="v"><?= $stats['P1'] ?></span>
      <span class="l">P1 vital</span>
    </div>
    <div class="ck-kpi" title="En attente de médecin">
      <span class="dot" style="background:var(--p2)"></span>
      <span class="v"><?= $stats['waiting_med'] ?></span>
      <span class="l">En attente</span>
    </div>
    <div class="ck-kpi" title="À hospitaliser">
      <span class="dot" style="background:var(--p4)"></span>
      <span class="v"><?= count($a_hospitaliser) ?></span>
      <span class="l">À hosp.</span>
    </div>
    <div class="ck-kpi" title="Lits disponibles">
      <span class="dot" style="background:var(--p5)"></span>
      <span class="v"><?= count($lits_disponibles ?? []) ?></span>
      <span class="l">Lits libres</span>
    </div>
  </div>

  <!-- Bloc droit : actions rapides -->
  <div style="display:flex;align-items:center;gap:8px;flex-shrink:0;">
    <div class="ck-clock" id="cockpit-clock">--:--:--</div>

    <!-- Créer patient — accessible depuis tous les onglets -->
    <button class="ck-hbtn"
            style="background:linear-gradient(135deg,#059669,#10b981);color:#fff;border-color:transparent;box-shadow:0 2px 8px rgba(5,150,105,.3);"
            onclick="new bootstrap.Modal(document.getElementById('modalNouveauPatientUrgences')).show()"
            title="Créer un nouveau dossier patient">
      <i class="bi bi-person-plus-fill"></i> Créer patient
    </button>

    <a href="<?= BASE_URL ?>prescription/par-ordre" class="ck-icon-btn" title="Prescription par ordre">
      <i class="bi bi-clipboard2-pulse"></i>
    </a>
    <button onclick="ouvrirProfilModal()" class="ck-icon-btn" title="Mon profil">
      <i class="bi bi-person-circle"></i>
    </button>
    <a href="<?= BASE_URL ?>logout" class="ck-icon-btn power" title="Déconnecter">
      <i class="bi bi-power"></i>
    </a>
  </div>

</header>

<!-- SEARCH HTML → déplacé dans .ck-subbar ci-dessous (IDs conservés pour le JS) -->


<script>
(function() {
    let _cTimer = null, _cFocusIdx = -1, _cResults = [];

    function age(dob) {
        if (!dob) return '';
        const d = new Date(dob), n = new Date();
        let a = n.getFullYear() - d.getFullYear();
        if (n.getMonth() - d.getMonth() < 0 || (n.getMonth() === d.getMonth() && n.getDate() < d.getDate())) a--;
        return a + ' ans';
    }

    function statusBadge(p) {
        const s = (p.statut_parcours || '').toUpperCase();
        if (s === 'URGENCES' || s === 'ATTENTE_CONSULTATION') return '<span class="csr-badge urgences">Urgences</span>';
        if (s === 'HOSPITALISE') return '<span class="csr-badge hospitalise">Hospitalisé</span>';
        if (s === 'EN_CONSULTATION') return '<span class="csr-badge consultation">En consultation</span>';
        return '<span class="csr-badge default">' + (p.statut_parcours || 'Actif') + '</span>';
    }

    function renderResults(patients) {
        _cResults = patients;
        _cFocusIdx = -1;
        const drop = document.getElementById('cockpitSearchDrop');
        const hint = document.getElementById('cockpitSearchHint');
        if (!patients.length) {
            drop.innerHTML = '<div style="text-align:center;padding:22px 10px;color:#94a3b8;font-size:.85rem;">'
                + '<i class="bi bi-person-x d-block" style="font-size:2rem;margin-bottom:8px;opacity:.4"></i>'
                + 'Aucun patient trouvé</div>';
            drop.style.display = 'block';
            hint.style.display = 'none';
            return;
        }
        let html = '';
        patients.forEach(function(p, i) {
            const initiales = ((p.nom||'').charAt(0) + (p.prenom||'').charAt(0)).toUpperCase();
            const sexColor = p.sexe === 'F' ? 'linear-gradient(135deg,#ec4899,#db2777)' : 'linear-gradient(135deg,#3b82f6,#1d4ed8)';
            const urlDossier  = '<?= BASE_URL ?>patients/dossier/' + p.id;
            const urlConsult  = '<?= BASE_URL ?>infirmier/consultation/' + p.id;
            const urlParam    = '<?= BASE_URL ?>patients/parametres/' + p.id;
            html += '<div class="csr-item" data-idx="' + i + '" data-url="' + urlDossier + '" '
                + 'onmouseenter="cockpitSearchFocus(' + i + ')" onclick="location.href=\'' + urlDossier + '\'">'
                + '<div class="csr-avatar" style="background:' + sexColor + '">' + initiales + '</div>'
                + '<div style="flex:1;min-width:0">'
                +   '<div class="csr-name">' + ((p.prenom||'')+' '+(p.nom||'').toUpperCase()).trim() + '</div>'
                +   '<div class="csr-meta">'
                +     (age(p.date_naissance) ? age(p.date_naissance) + ' &nbsp;·&nbsp; ' : '')
                +     (p.sexe === 'M' ? 'Masc.' : (p.sexe === 'F' ? 'Fém.' : ''))
                +     (p.dossier_numero ? ' &nbsp;·&nbsp; <strong>' + p.dossier_numero + '</strong>' : '')
                +   '</div>'
                + '</div>'
                + statusBadge(p)
                + '<div class="csr-actions" onclick="event.stopPropagation()">'
                +   '<a href="' + urlDossier + '" class="csr-btn csr-btn-dossier"><i class="bi bi-folder2-open"></i> Dossier</a>'
                +   '<a href="' + urlConsult + '" class="csr-btn csr-btn-consult"><i class="bi bi-stethoscope"></i> Consulter</a>'
                +   '<a href="' + urlParam   + '" class="csr-btn csr-btn-param"><i class="bi bi-activity"></i> Paramètres</a>'
                + '</div>'
                + '</div>';
        });
        drop.innerHTML = html;
        drop.style.display = 'block';
        hint.style.display = patients.length ? 'flex' : 'none';
    }

    window.cockpitSearchRun = function(q) {
        clearTimeout(_cTimer);
        const drop = document.getElementById('cockpitSearchDrop');
        const hint = document.getElementById('cockpitSearchHint');
        if (!q || q.trim().length < 2) {
            drop.style.display = 'none';
            hint.style.display = 'none';
            return;
        }
        drop.style.display = 'block';
        drop.innerHTML = '<div style="text-align:center;padding:18px;color:#64748b;font-size:.85rem;">'
            + '<span class="spinner-border spinner-border-sm me-2" style="color:#3b82f6"></span>Recherche…</div>';
        _cTimer = setTimeout(function() {
            fetch('<?= BASE_URL ?>patients/recherche?q=' + encodeURIComponent(q.trim()))
                .then(r => r.json())
                .then(function(data) {
                    const list = Array.isArray(data) ? data : (data && Array.isArray(data.patients) ? data.patients : []);
                    renderResults(list);
                })
                .catch(function() {
                    drop.innerHTML = '<div style="text-align:center;padding:18px;color:#ef4444;font-size:.85rem;">'
                        + '<i class="bi bi-wifi-off me-2"></i>Erreur réseau</div>';
                });
        }, 280);
    };

    window.cockpitSearchHide = function() {
        const inp  = document.getElementById('globalPatientSearch');
        if (document.activeElement === inp) return;
        document.getElementById('cockpitSearchDrop').style.display = 'none';
        document.getElementById('cockpitSearchHint').style.display = 'none';
        _cFocusIdx = -1;
    };

    window.cockpitSearchFocus = function(idx) {
        _cFocusIdx = idx;
        document.querySelectorAll('#cockpitSearchDrop .csr-item').forEach(function(el, i) {
            el.classList.toggle('csr-active', i === idx);
        });
    };

    window.cockpitSearchKey = function(e) {
        const items = document.querySelectorAll('#cockpitSearchDrop .csr-item');
        if (!items.length) return;
        if (e.key === 'ArrowDown') {
            e.preventDefault();
            cockpitSearchFocus(Math.min(_cFocusIdx + 1, items.length - 1));
            items[_cFocusIdx]?.scrollIntoView({ block:'nearest' });
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            cockpitSearchFocus(Math.max(_cFocusIdx - 1, 0));
            items[_cFocusIdx]?.scrollIntoView({ block:'nearest' });
        } else if (e.key === 'Enter') {
            e.preventDefault();
            const idx = _cFocusIdx >= 0 ? _cFocusIdx : 0;
            const url = items[idx]?.dataset?.url;
            if (url) location.href = url;
        } else if (e.key === 'Escape') {
            document.getElementById('cockpitSearchDrop').style.display = 'none';
            document.getElementById('cockpitSearchHint').style.display = 'none';
            document.getElementById('globalPatientSearch').blur();
        }
    };

    // Raccourci clavier : / ou Ctrl+K focusse la barre
    document.addEventListener('keydown', function(e) {
        const inp = document.getElementById('globalPatientSearch');
        if (!inp) return;
        if ((e.key === '/' || (e.ctrlKey && e.key === 'k')) &&
            document.activeElement?.tagName !== 'INPUT' &&
            document.activeElement?.tagName !== 'TEXTAREA') {
            e.preventDefault();
            inp.focus();
            inp.select();
        }
    });
})();
</script>

<!-- ══ SUBBAR : RECHERCHE + ONGLETS ══ -->
<div class="ck-subbar">
  <div class="ck-searchrow">
    <div class="ck-search" style="position:relative;">
      <i class="bi bi-search"></i>
      <input type="text" id="globalPatientSearch"
             placeholder="Rechercher un patient — nom, prénom, numéro de dossier…"
             autocomplete="off"
             onfocus="this.parentElement.style.borderColor='var(--primary)'"
             onblur="setTimeout(()=>{this.parentElement.style.borderColor='var(--line)';cockpitSearchHide()},200)"
             oninput="cockpitSearchRun(this.value)"
             onkeydown="cockpitSearchKey(event)">
      <div id="cockpitSearchDrop"
           style="display:none;position:absolute;top:calc(100% + 6px);left:0;right:0;
                  background:var(--surface);border:1.5px solid var(--line);border-radius:14px;
                  box-shadow:var(--shadow);z-index:2000;max-height:420px;overflow-y:auto;padding:6px;">
      </div>
      <span id="cockpitSearchHint" style="display:none;font-size:.75rem;color:var(--ink-3);white-space:nowrap;">
        <kbd style="background:var(--surface-2);border:1px solid var(--line);border-radius:4px;padding:1px 5px;font-size:.68rem">↑↓</kbd>
        <kbd style="background:var(--surface-2);border:1px solid var(--line);border-radius:4px;padding:1px 5px;font-size:.68rem">Entrée</kbd>
        <kbd style="background:var(--surface-2);border:1px solid var(--line);border-radius:4px;padding:1px 5px;font-size:.68rem">Échap</kbd>
      </span>
    </div>
    <span style="font-size:.75rem;color:var(--ink-3);white-space:nowrap;">
      <b style="color:var(--ink)"><?= $stats['total'] ?></b> urgence<?= $stats['total']>1?'s':'' ?>
      &nbsp;·&nbsp;
      <b style="color:var(--ink)"><?= count($patients_hospitalises_all) ?></b> hospitalisé<?= count($patients_hospitalises_all)>1?'s':'' ?>
    </span>
  </div>
  <nav class="ck-tabs" id="ckTabs">
    <button class="ck-tab on" id="tabBtnUrgences" onclick="ckTabChange('tabUrgences')">
      <i class="bi bi-lightning-charge-fill"></i> Urgences
      <?php if($stats['P1']>0): ?><span class="cnt warn"><?= $stats['P1'] ?></span><?php elseif($stats['total']>0): ?><span class="cnt"><?= $stats['total'] ?></span><?php endif; ?>
    </button>
    <button class="ck-tab" onclick="ckTabChange('tabHospitaliser')">
      <i class="bi bi-house-heart-fill"></i> À hospitaliser
      <?php if(count($a_hospitaliser)>0): ?><span class="cnt warn"><?= count($a_hospitaliser) ?></span><?php endif; ?>
    </button>
    <button class="ck-tab" onclick="ckTabChange('tabServices')">
      <i class="bi bi-grid-1x2"></i> Services &amp; soins
      <?php if(count($patients_hospitalises_all)>0): ?><span class="cnt"><?= count($patients_hospitalises_all) ?></span><?php endif; ?>
    </button>
    <button class="ck-tab" onclick="ckTabChange('tabLits')">
      <i class="bi bi-hospital"></i> Lits
    </button>
    <button class="ck-tab" id="tabBtnFileConsult" onclick="ckTabChange('tabFileConsult')">
      <i class="bi bi-activity"></i> À consulter
      <?php $nb_fci = count($file_consultation_infirmier ?? []); if($nb_fci > 0): ?><span class="cnt"><?= $nb_fci ?></span><?php endif; ?>
    </button>
    <button class="ck-tab" id="tabBtnMesConsult" onclick="ckTabChange('tabMesConsult')">
      <i class="bi bi-journal-medical"></i> Mes consultations
      <?php $nb_mc = count($mes_consultations ?? []); if($nb_mc > 0): ?><span class="cnt"><?= $nb_mc ?></span><?php endif; ?>
    </button>
    <button class="ck-tab" id="tabBtnSoins" onclick="ckTabChange('tabSoins')">
      <i class="bi bi-check2-square"></i> Soins à faire
      <?php
      $nb_soins = count($soins_a_faire ?? []);
      $nb_soins_retard = 0; $now_ts = time();
      foreach (($soins_a_faire ?? []) as $s) { if (!empty($s['date_prevue']) && strtotime($s['date_prevue']) < $now_ts) $nb_soins_retard++; }
      if ($nb_soins > 0): ?><span class="cnt <?= $nb_soins_retard>0?'warn':'' ?>"><?= $nb_soins ?></span><?php endif; ?>
    </button>
  </nav>
</div>

<!-- ══ ACTION ROW (onglet Urgences uniquement) ══ -->
<div class="ck-actionrow" id="ckActionRow">
  <div class="rh"><b id="ckVisCount"><?= $stats['total'] ?></b> patient<?= $stats['total']>1?'s':'' ?> · triés par priorité puis temps d'attente</div>
  <div class="spacer"></div>
  <button class="ck-chiplink" onclick="location.href='<?= BASE_URL ?>urgences/nouvelle-admission'"><i class="bi bi-exclamation-octagon-fill"></i> Aflux massif</button>
  <button class="ck-hbtn" onclick="new bootstrap.Modal(document.getElementById('modalFastAdmission')).show()"><i class="bi bi-box-arrow-in-down"></i> Admission rapide</button>
  <a href="<?= BASE_URL ?>prescription/par-ordre" class="ck-hbtn"><i class="bi bi-clipboard2-pulse"></i> Prescriptions</a>
  <button class="ck-hbtn primary" onclick="new bootstrap.Modal(document.getElementById('modalChercherPatientConsult')).show()"><i class="bi bi-stethoscope"></i> Consulter</button>
</div>

<div class="tab-content">

<!-- ══════════════ ONGLET 1 : URGENCES ══════════════ -->
<div class="tab-pane fade show active" id="tabUrgences">
<?php
// ── Palettes triage ──────────────────────────────────────────────────────────
$triLabels = ['1'=>'P1 · Vital','2'=>'P2 · Urgent','3'=>'P3 · Semi-urgent','4'=>'P4 · Standard','5'=>'P5 · Non urgent'];
$triVars   = ['1'=>'--p1','2'=>'--p2','3'=>'--p3','4'=>'--p4','5'=>'--p5'];
?>
<?php if(empty($admissions)): ?>
<div class="empty-state" style="padding:60px 20px;text-align:center;color:var(--ink-3)">
  <i class="bi bi-patch-check-fill" style="font-size:3rem;color:#22c55e;display:block;margin-bottom:12px"></i>
  <h5 style="color:#22c55e;font-weight:700">Aucune urgence active</h5>
  <p style="color:var(--ink-2)">Tous les patients urgences ont été pris en charge.</p>
</div>
<?php else: ?>

<!-- GRILLE CARTES -->
<div class="ck-board">
<div class="ck-grid">
<?php foreach($admissions as $adm):
  $niv    = (string)($adm['niveau_triage'] ?? '3');
  $triVar = $triVars[$niv] ?? '--p3';
  $triLbl = $triLabels[$niv] ?? 'P3 · Semi-urgent';
  $age    = !empty($adm['date_naissance']) ? date_diff(date_create($adm['date_naissance']), date_create('now'))->y : '?';
  $heureArr = !empty($adm['heure_arrivee']) ? date('H:i', strtotime($adm['heure_arrivee'])) : '--:--';
  $tsArrivee = !empty($adm['heure_arrivee']) ? strtotime($adm['heure_arrivee']) : 0;
  $motif  = htmlspecialchars($adm['motif_plainte'] ?? $adm['motif_admission'] ?? 'En attente de triage…');

  // NEWS2
  $n2=0; $poulsV=(int)($adm['pouls']??0); $spo2V=(float)($adm['spo2']??0);
  $sysV=(int)($adm['tension_sys']??0); $tempV=(float)($adm['temperature']??0);
  if($poulsV>0){if($poulsV<=40||$poulsV>=131)$n2+=3;elseif($poulsV>=111)$n2+=2;elseif($poulsV<=50||($poulsV>=91&&$poulsV<=110))$n2+=1;}
  if($spo2V>0){if($spo2V<=91)$n2+=3;elseif($spo2V<=93)$n2+=2;elseif($spo2V<=95)$n2+=1;}
  if($sysV>0){if($sysV<=90||$sysV>=220)$n2+=3;elseif($sysV<=100)$n2+=2;elseif($sysV<=110)$n2+=1;}
  if($tempV>0){if($tempV<=35.0)$n2+=3;elseif($tempV<=36.0)$n2+=1;elseif($tempV>=39.1)$n2+=2;elseif($tempV>=38.1)$n2+=1;}
  // NEWS2 couleurs (inline pour compatibilité dark/light via CSS vars)
  $newsBg  = $n2>=7?'var(--p1-soft)':($n2>=5?'var(--p2-soft)':($n2>=1?'var(--p5-soft)':'var(--p4-soft)'));
  $newsInk = $n2>=7?'var(--p1)':($n2>=5?'var(--p2)':($n2>=1?'var(--p5)':'var(--p4)'));
  // Vitaux: classes d'alerte
  $plsCls = $poulsV==0?'muted':($poulsV>=120||$poulsV<=45?'bad':($poulsV>=100||$poulsV<=50?'warn':''));
  $spoCls = $spo2V==0?'muted':($spo2V<90?'bad':($spo2V<94?'warn':''));
  $taSys  = ($adm['tension_sys']&&$adm['tension_dia'])?$adm['tension_sys'].'/'.$adm['tension_dia']:null;
?>
<div class="ck-card" style="--tri:var(<?= $triVar ?>);--news-bg:<?= $newsBg ?>;--news-ink:<?= $newsInk ?>"
     data-ts-arrivee="<?= $tsArrivee ?>">
  <div class="c-top">
    <span class="ck-tri-chip"><?= $triLbl ?></span>
    <div class="ck-c-time">
      <?= $heureArr ?>
      <span class="wait" id="ck-wait-<?= $adm['id'] ?>">--</span>
    </div>
  </div>
  <div class="ck-c-name"><?= htmlspecialchars(strtoupper($adm['nom']).' '.$adm['prenom']) ?></div>
  <div class="ck-c-demo"><?= $age ?> ans · <?= $adm['sexe']=='M'?'M':'F' ?><?= !empty($adm['medecin_nom'])?' · Dr '.htmlspecialchars($adm['medecin_nom']):'' ?></div>
  <div class="ck-c-motif"><i class="bi bi-record-circle-fill" style="font-size:7px"></i> <?= $motif ?></div>
  <div class="ck-vitals">
    <div class="ck-vit"><div class="vl">Pouls</div><div class="vv <?= $plsCls ?>"><?= $poulsV?:'—' ?></div></div>
    <div class="ck-vit"><div class="vl">SpO₂</div><div class="vv <?= $spoCls ?>"><?= $spo2V?$spo2V.'%':'—' ?></div></div>
    <div class="ck-vit"><div class="vl">TA</div><div class="vv <?= $taSys?'':'muted' ?>"><?= $taSys?:'—' ?></div></div>
    <div class="ck-vit"><div class="vl">Temp</div><div class="vv <?= $tempV&&$tempV>=38.5?'warn':'' ?>"><?= $tempV?$tempV.'°':'—' ?></div></div>
    <div class="ck-news"><span class="nl">NEWS2</span><span class="nv"><?= $n2 ?></span></div>
  </div>
  <?php if(!empty($adm['nb_bilans_dispo'])): ?>
  <div style="margin-top:8px"><span style="font-size:.72rem;font-weight:700;color:var(--p4);background:var(--p4-soft);border:1px solid var(--p4);border-radius:6px;padding:2px 8px"><i class="bi bi-flask-fill"></i> <?= $adm['nb_bilans_dispo'] ?> résultat(s) dispo</span></div>
  <?php endif; ?>
  <div class="ck-c-foot">
    <a href="<?= BASE_URL ?>urgences/triage/<?= $adm['id'] ?>" class="ck-cbtn ghost"><i class="bi bi-clipboard2-pulse"></i></a>
    <a href="<?= BASE_URL ?>patients/dossier/<?= $adm['patient_id'] ?>" class="ck-cbtn main"><i class="bi bi-folder2-open"></i> Dossier</a>
  </div>
</div>
<?php endforeach; ?>
</div><!-- /ck-grid -->
</div><!-- /ck-board -->

<!-- LISTE (vue alternative) -->
<div class="ck-listwrap">
<table class="ck-ltable">
  <thead><tr>
    <th class="lt-tri"></th>
    <th>Niv.</th><th>Patient</th><th>Motif</th><th>NEWS2</th>
    <th>Pouls</th><th>SpO₂</th><th>TA</th><th>Attente</th><th style="text-align:right">Actions</th>
  </tr></thead>
  <tbody>
  <?php foreach($admissions as $adm):
    $niv    = (string)($adm['niveau_triage'] ?? '3');
    $triVar = $triVars[$niv] ?? '--p3';
    $triLbl = 'P'.$niv;
    $age    = !empty($adm['date_naissance']) ? date_diff(date_create($adm['date_naissance']), date_create('now'))->y : '?';
    $poulsV=(int)($adm['pouls']??0);$spo2V=(float)($adm['spo2']??0);
    $sysV=(int)($adm['tension_sys']??0);$tempV=(float)($adm['temperature']??0);
    $n2=0;
    if($poulsV>0){if($poulsV<=40||$poulsV>=131)$n2+=3;elseif($poulsV>=111)$n2+=2;elseif($poulsV<=50||($poulsV>=91&&$poulsV<=110))$n2+=1;}
    if($spo2V>0){if($spo2V<=91)$n2+=3;elseif($spo2V<=93)$n2+=2;elseif($spo2V<=95)$n2+=1;}
    if($sysV>0){if($sysV<=90||$sysV>=220)$n2+=3;elseif($sysV<=100)$n2+=2;elseif($sysV<=110)$n2+=1;}
    if($tempV>0){if($tempV<=35.0)$n2+=3;elseif($tempV<=36.0)$n2+=1;elseif($tempV>=39.1)$n2+=2;elseif($tempV>=38.1)$n2+=1;}
    $newsBg  = $n2>=7?'var(--p1-soft)':($n2>=5?'var(--p2-soft)':($n2>=1?'var(--p5-soft)':'var(--p4-soft)'));
    $newsInk = $n2>=7?'var(--p1)':($n2>=5?'var(--p2)':($n2>=1?'var(--p5)':'var(--p4)'));
    $plsCls  = $poulsV==0?'muted':($poulsV>=120||$poulsV<=45?'bad':($poulsV>=100||$poulsV<=50?'warn':''));
    $spoCls  = $spo2V==0?'muted':($spo2V<90?'bad':($spo2V<94?'warn':''));
    $taSys   = ($adm['tension_sys']&&$adm['tension_dia'])?$adm['tension_sys'].'/'.$adm['tension_dia']:null;
    $tsArrivee = !empty($adm['heure_arrivee']) ? strtotime($adm['heure_arrivee']) : 0;
  ?>
  <tr style="--tri:var(<?= $triVar ?>)">
    <td class="lt-tri"></td>
    <td><span class="lt-tag"><?= $triLbl ?></span></td>
    <td><div class="lt-name"><?= htmlspecialchars(strtoupper($adm['nom']).' '.$adm['prenom']) ?></div><div class="lt-demo"><?= $age ?> ans · <?= $adm['sexe']=='M'?'M':'F' ?></div></td>
    <td><span class="lt-motif"><?= htmlspecialchars(mb_substr($adm['motif_plainte']??$adm['motif_admission']??'—',0,40)) ?></span></td>
    <td><div class="lt-news" style="background:<?= $newsBg ?>;color:<?= $newsInk ?>"><span class="nv"><?= $n2 ?></span><span class="nl">NEWS2</span></div></td>
    <td class="lt-vit"><span class="<?= $plsCls ?>"><?= $poulsV?:'<span class="muted">—</span>' ?></span></td>
    <td class="lt-vit"><span class="<?= $spoCls ?>"><?= $spo2V?$spo2V.'%':'<span class="muted">—</span>' ?></span></td>
    <td class="lt-vit"><?= $taSys?:'<span class="muted">—</span>' ?></td>
    <td class="lt-vit"><span id="ck-wait-l-<?= $adm['id'] ?>" data-ts="<?= $tsArrivee ?>">--</span></td>
    <td><div class="lt-actions">
      <a href="<?= BASE_URL ?>urgences/triage/<?= $adm['id'] ?>" class="ck-cbtn ghost" style="padding:6px 11px;font-size:12px"><i class="bi bi-clipboard2-pulse"></i></a>
      <a href="<?= BASE_URL ?>patients/dossier/<?= $adm['patient_id'] ?>" class="ck-cbtn main" style="padding:6px 13px;font-size:12px"><i class="bi bi-folder2-open"></i> Dossier</a>
    </div></td>
  </tr>
  <?php endforeach; ?>
  </tbody>
</table>
</div><!-- /ck-listwrap -->

<?php endif; ?>
</div><!-- /tabUrgences -->

<!-- ══════════════ ONGLET 2 : À HOSPITALISER ══════════════ -->
<div class="tab-pane fade tab-content-area" id="tabHospitaliser">
    <?php if(!empty($a_hospitaliser)): ?>
    <div class="hosp-alert-bar">
        <i class="bi bi-exclamation-triangle-fill text-warning fs-4"></i>
        <div>
            <strong><?= count($a_hospitaliser) ?> patient(s) en attente d'installation</strong>
            <div class="small text-muted">Venant des consultations externes et des urgences — à orienter vers un lit.</div>
        </div>
    </div>
    <?php endif; ?>

    <?php if(empty($a_hospitaliser)): ?>
    <div class="empty-state">
        <i class="bi bi-check-circle-fill text-success"></i>
        <h5 class="text-success fw-bold">Aucun patient en attente d'hospitalisation</h5>
        <p class="text-muted">Tous les patients ont été installés.</p>
    </div>
    <?php else: foreach($a_hospitaliser as $h):
        $initials   = strtoupper(substr($h['nom'],0,1).substr($h['prenom']??'',0,1));
        $patId      = (int)$h['patient_id'];
        $isUrgences = (($h['source'] ?? '') === 'urgences');
    ?>
    <div class="hosp-card">
        <div class="hosp-avatar"><?= $initials ?></div>
        <div class="flex-grow-1" style="min-width:0">
            <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                <strong><?= htmlspecialchars(strtoupper($h['nom']).' '.($h['prenom']??'')) ?></strong>
                <span class="text-muted small">#<?= htmlspecialchars($h['dossier_numero']??'') ?></span>
                <?php if($isUrgences): ?>
                <span class="urgence-tag"><i class="bi bi-lightning-charge-fill"></i> URGENCES</span>
                <?php else: ?>
                <span class="ext-tag"><i class="bi bi-clipboard2-pulse"></i> CONSULTATION</span>
                <?php endif; ?>
            </div>
            <div class="small text-muted">
                Provenance : <strong><?= htmlspecialchars($h['nom_service_provenance'] ?? ($isUrgences ? 'Urgences' : 'Consultation')) ?></strong>
                <?php if(!empty($h['nom_service_destination'])): ?>
                &nbsp;→&nbsp;<span class="badge bg-primary-subtle text-primary border" style="font-size:.65rem"><?= htmlspecialchars($h['nom_service_destination']) ?></span>
                <?php endif; ?>
                <?php if(!empty($h['medecin_nom'])): ?>&nbsp;•&nbsp;Dr <?= htmlspecialchars($h['medecin_nom']) ?><?php endif; ?>
            </div>
            <?php if(!empty($h['diagnostic_principal'])): ?>
            <div class="small text-secondary mt-1" style="font-size:.75rem"><i class="bi bi-clipboard-pulse"></i> <?= htmlspecialchars(mb_substr($h['diagnostic_principal'],0,90)) ?><?= mb_strlen($h['diagnostic_principal'])>90?'…':'' ?></div>
            <?php endif; ?>
        </div>
        <div class="d-flex gap-2 flex-shrink-0 align-items-center">
            <button class="btn btn-success btn-sm rounded-pill px-3 shadow-sm fw-bold"
                    onclick="openAssignBed(<?= $patId ?>, '<?= addslashes(strtoupper($h['nom']).' '.($h['prenom']??'')) ?>')">
                <i class="bi bi-bed-fill"></i> Installer
            </button>
            <a href="<?= BASE_URL ?>patients/dossier/<?= $patId ?>" class="btn btn-outline-primary btn-sm rounded-pill px-3">
                <i class="bi bi-folder2-open"></i> Dossier
            </a>
        </div>
    </div>
    <?php endforeach; endif; ?>
</div>

<!-- ══════════════ ONGLET 3 : SERVICES & SOINS ══════════════ -->
<div class="tab-pane fade tab-content-area" id="tabServices">
    <?php if(empty($services_map)): ?>
    <div class="empty-state">
        <i class="bi bi-building"></i>
        <h5>Aucun patient hospitalisé actuellement</h5>
    </div>
    <?php else: foreach($services_map as $nomService => $patients):
        $soinsTotal = array_sum(array_column($patients,'total_soins'));
        $soinsFaits = array_sum(array_column($patients,'soins_faits'));
        $pctGlobal  = $soinsTotal>0 ? round($soinsFaits/$soinsTotal*100) : 0;
        $badgeClass = $pctGlobal==100?'success':($pctGlobal>=50?'warning':'danger');
    ?>
    <div class="service-section">
        <div class="service-header">
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-building-fill-check fs-5"></i>
                <strong style="font-size:.95rem"><?= htmlspecialchars($nomService) ?></strong>
                <span class="badge bg-white text-dark" style="font-size:.72rem"><?= count($patients) ?> patient<?= count($patients)>1?'s':'' ?></span>
            </div>
            <div class="d-flex align-items-center gap-2">
                <div style="width:90px;height:6px;border-radius:4px;background:rgba(255,255,255,.2);overflow:hidden">
                    <div style="width:<?= $pctGlobal ?>%;height:100%;background:<?= $pctGlobal==100?'#4ade80':($pctGlobal>=50?'#fbbf24':'#f87171') ?>;border-radius:4px"></div>
                </div>
                <span class="badge bg-<?= $badgeClass ?>" style="font-size:.72rem"><?= $soinsFaits ?>/<?= $soinsTotal ?> (<?= $pctGlobal ?>%)</span>
            </div>
        </div>
        <div class="service-cards-grid">
            <?php foreach($patients as $pat):
                $ini = strtoupper(substr($pat['nom'],0,1).substr($pat['prenom'],0,1));
                $pct = ($pat['total_soins']>0) ? round($pat['soins_faits']/$pat['total_soins']*100) : 0;
                $fillClass = $pct==100?'done':($pct>=50?'partial':'low');
                $litLabel = (!empty($pat['nom_chambre'])) ? htmlspecialchars($pat['nom_chambre'].' — '.($pat['nom_lit']??'')) : 'Lit non assigné';
            ?>
            <div class="pat-card">
                <!-- TOP : avatar + infos -->
                <div class="pat-card-top">
                    <div class="inf-avatar"><?= $ini ?></div>
                    <div class="pat-card-info">
                        <div class="pat-card-name"><?= htmlspecialchars(strtoupper($pat['nom']).' '.$pat['prenom']) ?></div>
                        <div class="pat-card-meta d-flex align-items-center gap-2 flex-wrap mt-1">
                            <span class="badge bg-light text-dark border" style="font-size:.66rem">
                                <i class="bi bi-door-closed me-1"></i><?= $litLabel ?>
                            </span>
                            <?php if(!empty($pat['medecin_resp'])): ?>
                            <span style="font-size:.72rem;color:#64748b"><i class="bi bi-person-badge me-1"></i>Dr <?= htmlspecialchars($pat['medecin_resp']) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <!-- Badge soins -->
                    <div class="flex-shrink-0 text-end">
                        <div class="fw-800" style="font-size:.78rem;color:<?= $pct==100?'#16a34a':($pct>=50?'#d97706':'#dc2626') ?>"><?= $pct ?>%</div>
                        <div style="font-size:.65rem;color:#94a3b8"><?= $pat['soins_faits']??0 ?>/<?= $pat['total_soins']??0 ?> soins</div>
                    </div>
                </div>
                <!-- BARRE PROGRESSION -->
                <div class="pat-card-progress">
                    <div class="progress-bar-wrap">
                        <div class="progress-fill <?= $fillClass ?>" style="width:<?= $pct ?>%"></div>
                    </div>
                </div>
                <!-- ÉVALUATION DOULEUR -->
                <?php
                $dScore    = $pat['douleur_score']    ?? null;
                $dScoreMax = $pat['douleur_score_max'] ?? null;
                $dEchelle  = $pat['douleur_echelle']  ?? null;
                $dSeverite = strtolower($pat['douleur_severite'] ?? '');
                $dClass    = match(true) {
                    $dSeverite === 'absent'  => 'douleur-absent',
                    str_contains($dSeverite,'leg')  => 'douleur-leger',
                    str_contains($dSeverite,'mod')  => 'douleur-modere',
                    str_contains($dSeverite,'int')  => 'douleur-intense',
                    str_contains($dSeverite,'s') && str_contains($dSeverite,'v') => 'douleur-severe',
                    $dScore !== null         => 'douleur-default',
                    default                  => ''
                };
                // Déduire aussi depuis le score si severite absente
                if ($dClass === 'douleur-default' && $dScore !== null) {
                    $s = (int)$dScore;
                    if ($s == 0)       $dClass = 'douleur-absent';
                    elseif ($s <= 3)   $dClass = 'douleur-leger';
                    elseif ($s <= 6)   $dClass = 'douleur-modere';
                    elseif ($s <= 8)   $dClass = 'douleur-intense';
                    else               $dClass = 'douleur-severe';
                }
                ?>
                <?php if($dScore !== null): ?>
                <div class="douleur-strip">
                    <i class="bi bi-bandaid-fill text-secondary" style="font-size:.72rem"></i>
                    <span class="douleur-badge <?= $dClass ?>">
                        <i class="bi bi-circle-fill" style="font-size:.45rem"></i>
                        <?= $dScore ?><?= $dScoreMax ? '/'.$dScoreMax : '' ?>
                        <?= $dEchelle ? '&nbsp;–&nbsp;'.$dEchelle : '' ?>
                        <?= $dSeverite ? '&nbsp;['.strtoupper($pat['douleur_severite']).']' : '' ?>
                    </span>
                    <span style="font-size:.65rem;color:#94a3b8">Infirmier</span>
                </div>
                <?php else: ?>
                <div class="douleur-strip">
                    <i class="bi bi-bandaid text-muted" style="font-size:.72rem"></i>
                    <span style="font-size:.68rem;color:#cbd5e1;font-style:italic">Douleur non évaluée</span>
                </div>
                <?php endif; ?>
                <!-- BOUTONS ACTIONS -->
                <div class="pat-card-actions">
                    <a href="<?= BASE_URL ?>hospitalisation/executer-soins/<?= $pat['hosp_id'] ?>" class="act-btn act-btn-soins">
                        <i class="bi bi-clipboard2-check-fill"></i> Soins du jour
                    </a>
                    <a href="<?= BASE_URL ?>hospitalisation/planifier-soins/<?= $pat['patient_id'] ?>" class="act-btn act-btn-plan">
                        <i class="bi bi-calendar2-plus"></i> Planifier
                    </a>
                    <a href="<?= BASE_URL ?>prescription/par-ordre?patient_id=<?= $pat['patient_id'] ?>" class="act-btn act-btn-prescription"
                       title="Prescription par ordre">
                        <i class="bi bi-prescription2"></i> Prescription
                    </a>
                    <a href="<?= BASE_URL ?>hospitalisation/suivi/<?= $pat['patient_id'] ?>" class="act-btn act-btn-suivi">
                        <i class="bi bi-heart-pulse"></i> Suivi
                    </a>
                    <a href="<?= BASE_URL ?>patients/dossier/<?= $pat['patient_id'] ?>" class="act-btn"
                       style="background:#f0f9ff;color:#0369a1;border:1.5px solid #bae6fd;"
                       onmouseover="this.style.background='#e0f2fe'" onmouseout="this.style.background='#f0f9ff'"
                       title="Dossier médical">
                        <i class="bi bi-folder2-open"></i> Dossier
                    </a>
                    <!-- Transférer (2 étapes : interne / externe) -->
                    <button type="button" class="act-btn act-btn-transferer"
                            onclick="urgOuvrirTransfert(
                                <?= (int)$pat['patient_id'] ?>,
                                <?= (int)($pat['hosp_id'] ?? 0) ?>,
                                '<?= addslashes(strtoupper($pat['nom']).' '.$pat['prenom']) ?>',
                                <?= (int)($pat['hosp_service_id'] ?? 0) ?>,
                                '<?= addslashes($litLabel) ?>'
                            )"
                            title="Transférer ce patient">
                        <i class="bi bi-arrow-left-right"></i> Transférer
                    </button>
                    <!-- Libérer le lit -->
                    <button type="button" class="act-btn act-btn-liberer"
                            onclick="confirmerLiberationLit(<?= $pat['hosp_id'] ?>, '<?= addslashes(strtoupper($pat['nom']).' '.$pat['prenom']) ?>', '<?= addslashes($litLabel) ?>')"
                            title="Libérer le lit">
                        <i class="bi bi-door-open-fill"></i> Libérer le lit
                    </button>
                    <!-- Dropdown Plus -->
                    <div class="dropdown">
                        <button type="button" class="act-btn act-btn-more dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false" title="Plus d'actions">
                            <i class="bi bi-three-dots-vertical"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow border-0 rounded-3" style="min-width:210px;font-size:.82rem">
                            <li>
                                <a class="dropdown-item py-2" href="<?= BASE_URL ?>patients/dossier/<?= $pat['patient_id'] ?>">
                                    <i class="bi bi-folder2-open me-2 text-primary"></i>Dossier patient
                                </a>
                            </li>
                            <li><hr class="dropdown-divider my-1"></li>
                            <li><span class="dropdown-header" style="font-size:.68rem">FORMULAIRES</span></li>
                            <li>
                                <a class="dropdown-item py-2" href="<?= BASE_URL ?>formulaire/creer/bulletin-examens/<?= $pat['patient_id'] ?>">
                                    <i class="bi bi-file-earmark-text me-2 text-secondary"></i>Bulletin d'examens
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item py-2" href="<?= BASE_URL ?>formulaire/creer/certificat-hospitalisation/<?= $pat['patient_id'] ?>">
                                    <i class="bi bi-file-earmark-medical me-2 text-secondary"></i>Cert. d'hospitalisation
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item py-2" href="<?= BASE_URL ?>formulaire/creer/compte-rendu-hosp/<?= $pat['patient_id'] ?>">
                                    <i class="bi bi-journal-medical me-2 text-secondary"></i>Compte-rendu hosp.
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item py-2" href="<?= BASE_URL ?>formulaire/creer/consentement/<?= $pat['patient_id'] ?>">
                                    <i class="bi bi-pen me-2 text-secondary"></i>Formulaire consentement
                                </a>
                            </li>
                            <li><hr class="dropdown-divider my-1"></li>
                            <li>
                                <a class="dropdown-item py-2 text-danger fw-bold" href="<?= BASE_URL ?>hospitalisation/sortie/<?= $pat['hosp_id'] ?>">
                                    <i class="bi bi-box-arrow-right me-2"></i>Préparer sortie
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endforeach; endif; ?>
</div>

<!-- ══════════════ ONGLET 4 : LITS ══════════════ -->
<div class="tab-pane fade tab-content-area" id="tabLits">

<style>
/* ── Résumé par service ── */
.lits-overview { display:flex; flex-wrap:wrap; gap:14px; margin-bottom:32px; }
.lit-service-card {
    background:#fff; border-radius:14px; padding:16px 20px;
    box-shadow:0 2px 10px rgba(0,0,0,.07); min-width:160px;
    display:flex; flex-direction:column; align-items:center; text-align:center;
    cursor:pointer; transition:box-shadow .18s, transform .18s;
    border:2px solid transparent;
}
.lit-service-card:hover { box-shadow:0 6px 20px rgba(37,99,235,.13); transform:translateY(-2px); }
.lit-service-card.active-srv { border-color:#2563eb; }
.lit-donut-wrap { position:relative; width:80px; height:80px; margin-bottom:10px; }
.lit-donut-text { position:absolute; inset:0; display:flex; flex-direction:column;
    align-items:center; justify-content:center; }
.lit-donut-text .num { font-size:1rem; font-weight:800; line-height:1; }
.lit-donut-text .lbl { font-size:.58rem; color:#94a3b8; font-weight:600; text-transform:uppercase; }

/* ── Carte de chambre ── */
.chambre-card {
    background:#fff; border-radius:14px; box-shadow:0 2px 10px rgba(0,0,0,.06);
    overflow:hidden; margin-bottom:20px;
}
.chambre-header {
    padding:10px 16px; display:flex; align-items:center; gap:10px;
    background:#f8fafc; border-bottom:1px solid #e2e8f0;
}
.chambre-header .ch-name { font-weight:700; font-size:.88rem; }
.chambre-header .ch-type { font-size:.7rem; color:#64748b; }
.chambre-beds { display:flex; flex-wrap:wrap; gap:10px; padding:14px 16px; }

/* ── Tuile de lit ── */
.bed-tile {
    width:110px; border-radius:10px; padding:10px 10px 8px;
    display:flex; flex-direction:column; align-items:center; gap:4px;
    transition:transform .15s; position:relative;
}
.bed-tile:hover { transform:scale(1.04); }
.bed-gear {
    position:absolute; top:4px; right:4px; width:20px; height:20px;
    border:none; border-radius:4px; background:rgba(0,0,0,.07);
    color:#64748b; font-size:.65rem; display:flex; align-items:center;
    justify-content:center; cursor:pointer; opacity:0; transition:opacity .15s;
    padding:0; line-height:1;
}
.bed-tile:hover .bed-gear { opacity:1; }
.bed-gear:hover { background:#e2e8f0 !important; color:#1e293b; }
/* Menu flottant statut lit */
#urgLitStatutMenu {
    display:none; position:fixed; z-index:9999;
    background:#fff; border:1.5px solid #e2e8f0; border-radius:12px;
    box-shadow:0 8px 28px rgba(0,0,0,.14); padding:8px; min-width:180px;
}
#urgLitStatutMenu .lsm-title {
    font-size:.7rem; font-weight:800; text-transform:uppercase;
    letter-spacing:.05em; color:#94a3b8; padding:4px 8px 8px;
    border-bottom:1px solid #f1f5f9; margin-bottom:6px;
}
#urgLitStatutMenu button {
    display:flex; align-items:center; gap:8px; width:100%;
    padding:8px 12px; border:none; border-radius:8px; background:none;
    font-size:.82rem; font-weight:600; color:#374151; cursor:pointer; text-align:left;
}
#urgLitStatutMenu button:hover { background:#f8fafc; }
.bed-tile.libre  { background:#f0fdf4; border:1.5px solid #86efac; }
.bed-tile.occupe { background:#fef2f2; border:1.5px solid #fca5a5; }
.bed-tile.maintenance { background:#fefce8; border:1.5px solid #fde68a; }
.bed-tile.emprunte  { background:#fffbeb; border:1.5px dashed #f59e0b; cursor:default; }
.bed-tile.emprunte:hover { transform:none; }
.bed-icon { font-size:1.6rem; line-height:1; }
.bed-num  { font-size:.72rem; font-weight:700; color:#374151; }
.bed-status-dot {
    width:8px; height:8px; border-radius:50%; margin-top:2px;
}
.bed-tile.libre  .bed-status-dot { background:#22c55e; }
.bed-tile.occupe .bed-status-dot { background:#ef4444; }
.bed-tile.maintenance .bed-status-dot { background:#eab308; }
.bed-tile.emprunte  .bed-status-dot { background:#f59e0b; }
.bed-patient { font-size:.6rem; color:#64748b; text-align:center;
    max-width:100px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }

/* ── Filtre service ── */
.srv-section { margin-bottom:36px; }
.srv-section-title {
    display:flex; align-items:center; gap:10px; margin-bottom:14px;
    padding-bottom:8px; border-bottom:2px solid #e2e8f0;
}
.srv-section-title h6 { margin:0; font-weight:800; font-size:.9rem; }
</style>

    <!-- KPI par service (cliquables pour filtrer) -->
    <div class="lits-overview">
        <div class="lit-service-card active-srv" onclick="filtrerService('__all__', this)">
            <div class="lit-donut-wrap">
                <?php
                $totAll = array_sum(array_column($lits_global,'total'));
                $libAll = array_sum(array_column($lits_global,'libres'));
                $occAll = $totAll - $libAll;
                $pAll   = $totAll > 0 ? round($occAll/$totAll*100) : 0;
                $cAll   = $pAll>=90?'#ef4444':($pAll>=70?'#f59e0b':'#22c55e');
                $R=30; $C=round(2*M_PI*$R,2); $D=round($pAll/100*$C,1);
                ?>
                <svg width="80" height="80" viewBox="0 0 80 80">
                    <circle cx="40" cy="40" r="<?=$R?>" fill="none" stroke="#e2e8f0" stroke-width="10"/>
                    <circle cx="40" cy="40" r="<?=$R?>" fill="none" stroke="<?=$cAll?>" stroke-width="10"
                            stroke-dasharray="<?=$D?> <?=$C?>" stroke-linecap="round"
                            transform="rotate(-90 40 40)"/>
                </svg>
                <div class="lit-donut-text">
                    <div class="num" style="color:<?=$cAll?>"><?=$pAll?>%</div>
                    <div class="lbl">occupé</div>
                </div>
            </div>
            <div class="fw-bold small mb-1">Tout l'hôpital</div>
            <div class="text-muted" style="font-size:.72rem"><?=$libAll?> libres / <?=$totAll?> total</div>
        </div>

        <?php foreach($lits_global as $srv):
            $total  = max(1,(int)$srv['total']);
            $libres = (int)$srv['libres'];
            $occupes= $total - $libres;
            $pctOcc = round($occupes/$total*100);
            $color  = $pctOcc>=90?'#ef4444':($pctOcc>=70?'#f59e0b':'#22c55e');
            $r=30; $circ=round(2*M_PI*$r,2); $dash=round($pctOcc/100*$circ,1);
            $srvSlug = 'srv-'.preg_replace('/[^a-z0-9]/','-',strtolower($srv['nom_service']));
        ?>
        <div class="lit-service-card" onclick="filtrerService('<?=$srvSlug?>', this)" data-srv="<?=$srvSlug?>">
            <div class="lit-donut-wrap">
                <svg width="80" height="80" viewBox="0 0 80 80">
                    <circle cx="40" cy="40" r="<?=$r?>" fill="none" stroke="#e2e8f0" stroke-width="10"/>
                    <circle cx="40" cy="40" r="<?=$r?>" fill="none" stroke="<?=$color?>" stroke-width="10"
                            stroke-dasharray="<?=$dash?> <?=$circ?>" stroke-linecap="round"
                            transform="rotate(-90 40 40)"/>
                </svg>
                <div class="lit-donut-text">
                    <div class="num" style="color:<?=$color?>"><?=$pctOcc?>%</div>
                    <div class="lbl">occupé</div>
                </div>
            </div>
            <div class="fw-bold small mb-1"><?=htmlspecialchars($srv['nom_service'])?></div>
            <div class="text-muted" style="font-size:.72rem"><?=$libres?> libres / <?=$total?> total</div>
            <?php if($libres==0): ?>
                <span class="badge bg-danger mt-1" style="font-size:.6rem">COMPLET</span>
            <?php elseif($libres<=2): ?>
                <span class="badge bg-warning text-dark mt-1" style="font-size:.6rem">QUASI PLEIN</span>
            <?php else: ?>
                <span class="badge bg-success mt-1" style="font-size:.6rem"><?=$libres?> DISPO</span>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Légende -->
    <div class="d-flex gap-4 mb-4" style="font-size:.78rem;">
        <span><span style="display:inline-block;width:12px;height:12px;background:#22c55e;border-radius:3px;margin-right:5px;"></span>Disponible</span>
        <span><span style="display:inline-block;width:12px;height:12px;background:#ef4444;border-radius:3px;margin-right:5px;"></span>Occupé</span>
        <span><span style="display:inline-block;width:12px;height:12px;background:#eab308;border-radius:3px;margin-right:5px;"></span>Maintenance</span>
        <span><span style="display:inline-block;width:12px;height:12px;background:#fffbeb;border:1.5px dashed #f59e0b;border-radius:3px;margin-right:5px;"></span>Emprunté (autre service)</span>
    </div>

    <!-- Carte dynamique : services → chambres → lits -->
    <?php foreach($lits_carte as $nomService => $chambres):
        $srvSlug = 'srv-'.preg_replace('/[^a-z0-9]/','-',strtolower($nomService));
    ?>
    <div class="srv-section" data-section="<?=$srvSlug?>">
        <div class="srv-section-title">
            <i class="bi bi-building-fill text-primary fs-5"></i>
            <h6><?=htmlspecialchars($nomService)?></h6>
            <?php
            $nbLits  = array_sum(array_map('count', $chambres));
            $nbLibres= 0;
            foreach($chambres as $chLits) foreach($chLits as $lt) if($lt['statut']!=='OCCUPE') $nbLibres++;
            ?>
            <span class="badge bg-primary ms-1"><?=count($chambres)?> ch.</span>
            <span class="badge bg-success ms-1"><?=$nbLibres?> libres</span>
            <span class="badge bg-secondary ms-1"><?=$nbLits?> lits</span>
        </div>

        <div class="row g-3">
        <?php foreach($chambres as $nomChambre => $lits):
            $nbOcc = count(array_filter($lits, fn($l)=>$l['statut']==='OCCUPE'));
            $nbTot = count($lits);
            $firstType = $lits[0]['type_chambre'] ?? '';
        ?>
        <div class="col-12 col-sm-6 col-lg-4 col-xl-3">
            <div class="chambre-card">
                <div class="chambre-header">
                    <i class="bi bi-door-closed-fill text-secondary"></i>
                    <div>
                        <div class="ch-name"><?=htmlspecialchars($nomChambre)?></div>
                        <?php if($firstType): ?>
                        <div class="ch-type"><?=htmlspecialchars($firstType)?></div>
                        <?php endif; ?>
                    </div>
                    <div class="ms-auto text-end">
                        <div style="font-size:.7rem;font-weight:700;color:<?=$nbOcc==$nbTot?'#ef4444':'#22c55e'?>">
                            <?=$nbTot-$nbOcc?>/<?=$nbTot?> libres
                        </div>
                        <!-- mini barre -->
                        <div style="width:50px;height:4px;background:#e2e8f0;border-radius:2px;margin-top:3px;">
                            <div style="width:<?=round($nbOcc/$nbTot*100)?>%;height:100%;background:<?=$nbOcc==$nbTot?'#ef4444':'#3b82f6'?>;border-radius:2px;"></div>
                        </div>
                    </div>
                </div>
                <div class="chambre-beds">
                <?php foreach($lits as $lt):
                    $isOcc      = $lt['statut'] === 'OCCUPE';
                    $isMaint    = !$isOcc && $lt['statut'] !== 'DISPONIBLE' && $lt['statut'] !== 'LIBRE';
                    // Lit emprunté : occupé par un patient dont le service responsable ≠ service physique du lit
                    $isEmprunte = $isOcc
                        && !empty($lt['patient_service_id'])
                        && !empty($lt['service_id'])
                        && (int)$lt['patient_service_id'] !== (int)$lt['service_id'];
                    if ($isEmprunte) {
                        $cls   = 'emprunte';
                        $icon  = '🔒';
                        $title = 'Occupé — patient du service : '.($lt['patient_service_nom'] ?? '—');
                    } else {
                        $cls   = $isOcc ? 'occupe' : ($isMaint ? 'maintenance' : 'libre');
                        $icon  = $isOcc ? '🛏️' : '🛌';
                        $title = $isOcc ? ($lt['patient_nom'].' '.($lt['patient_prenom']??'').' — '.($lt['dossier_numero']??'')) : 'Disponible';
                    }
                    $patNom = (!$isEmprunte && $isOcc && !empty($lt['patient_nom']))
                        ? htmlspecialchars(strtoupper(substr($lt['patient_nom'],0,1)).'. '.($lt['patient_prenom']??''))
                        : '';
                ?>
                <div class="bed-tile <?=$cls?>" title="<?=htmlspecialchars($title)?>">
                    <?php if (!$isOcc && !$isEmprunte): ?>
                    <button type="button" class="bed-gear"
                            title="Changer le statut du lit"
                            onclick="event.stopPropagation(); urgOuvrirStatutLit(this, <?= (int)($lt['id'] ?? 0) ?>, '<?= addslashes(htmlspecialchars($nomChambre.' · '.($lt['nom_lit']??''))) ?>')">
                        <i class="bi bi-gear-fill"></i>
                    </button>
                    <?php endif; ?>
                    <div class="bed-icon"><?=$icon?></div>
                    <div class="bed-num"><?=htmlspecialchars($lt['nom_lit'])?></div>
                    <div class="bed-status-dot"></div>
                    <?php if($isEmprunte): ?>
                    <div class="bed-patient" style="color:#d97706;font-size:.58rem;text-align:center;line-height:1.2;">
                        <?=htmlspecialchars($lt['patient_service_nom'] ?? '—')?>
                    </div>
                    <?php elseif($patNom): ?>
                    <div class="bed-patient"><?=$patNom?></div>
                    <?php else: ?>
                    <div class="bed-patient" style="color:#86efac;font-size:.6rem">Libre</div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
        </div>
    </div>
    <?php endforeach; ?>

</div>

<!-- Menu flottant : changement de statut lit (urgences) -->
<div id="urgLitStatutMenu">
    <div class="lsm-title" id="urgLsmTitle">Statut du lit</div>
    <button onclick="urgSetStatutLit('DISPONIBLE')">
        <i class="bi bi-check-circle-fill text-success"></i> Disponible
    </button>
    <button onclick="urgSetStatutLit('NETTOYAGE')">
        <i class="bi bi-droplet-half text-warning"></i> En nettoyage
    </button>
    <button onclick="urgSetStatutLit('MAINTENANCE')">
        <i class="bi bi-tools text-secondary"></i> En maintenance
    </button>
</div>
<script>
(function(){
    let _urgLitId = null;
    const menu = document.getElementById('urgLitStatutMenu');

    window.urgOuvrirStatutLit = function(btn, litId, label) {
        _urgLitId = litId;
        document.getElementById('urgLsmTitle').textContent = label || 'Statut du lit';
        const r = btn.getBoundingClientRect();
        menu.style.display = 'block';
        let left = r.right - menu.offsetWidth;
        if (left < 8) left = 8;
        let top = r.bottom + 6;
        if (top + menu.offsetHeight > window.innerHeight - 8) top = r.top - menu.offsetHeight - 6;
        menu.style.left = left + 'px';
        menu.style.top  = top  + 'px';
    };

    window.urgSetStatutLit = function(statut) {
        if (!_urgLitId) return;
        const fd = new FormData();
        fd.append('lit_id', _urgLitId);
        fd.append('statut', statut);
        menu.style.display = 'none';
        fetch('<?= BASE_URL ?>lits/changer-statut', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(res => {
                if (res.success) { location.reload(); }
                else { alert(res.message || 'Erreur lors du changement de statut.'); }
            })
            .catch(() => alert('Erreur réseau.'));
    };

    document.addEventListener('click', e => {
        if (!e.target.closest('#urgLitStatutMenu') && !e.target.closest('.bed-gear')) {
            menu.style.display = 'none';
        }
    });
})();
</script>

<script>
function filtrerService(slug, el) {
    // Activer carte KPI
    document.querySelectorAll('.lit-service-card').forEach(c => c.classList.remove('active-srv'));
    el.classList.add('active-srv');
    // Afficher/masquer sections
    document.querySelectorAll('.srv-section').forEach(s => {
        s.style.display = (slug === '__all__' || s.dataset.section === slug) ? '' : 'none';
    });
}
</script>

<!-- ══════════════ ONGLET 5 : FILE D'ATTENTE CONSULTATION INFIRMIÈRE ══════════════ -->
<div class="tab-pane fade tab-content-area" id="tabFileConsult">
<?php $file_consultation_infirmier = $file_consultation_infirmier ?? []; ?>

<style>
/* ── File consultation infirmière ── */
.fci-grid { display:grid; grid-template-columns:repeat(auto-fill, minmax(300px,1fr)); gap:16px; }
.fci-card {
    background:#fff; border-radius:14px; border:1px solid #e2e8f0;
    box-shadow:0 2px 10px rgba(0,0,0,.05); overflow:hidden; transition:all .2s;
}
.fci-card:hover { transform:translateY(-3px); box-shadow:0 8px 24px rgba(0,0,0,.10); border-color:#0d9488; }
.fci-card-top {
    padding:14px 16px 10px; display:flex; align-items:center; gap:12px;
    border-left:6px solid #0d9488;
}
.fci-avatar {
    width:44px; height:44px; border-radius:50%; flex-shrink:0;
    background:linear-gradient(135deg,#0d9488,#0f766e);
    color:#fff; display:flex; align-items:center; justify-content:center;
    font-weight:800; font-size:.9rem;
}
.fci-card-actions { padding:10px 16px 14px; display:flex; gap:8px; align-items:center; border-top:1px solid #f1f5f9; }
.fci-vitals { display:flex; flex-wrap:wrap; gap:6px; padding:6px 16px 0; }
.fci-vital-chip {
    display:inline-flex; align-items:center; gap:4px;
    background:#f8fafc; border:1px solid #e2e8f0;
    border-radius:8px; padding:2px 8px; font-size:.72rem; font-weight:700; color:#475569;
}
.fci-vital-chip.alert { background:#fff1f2; border-color:#fecdd3; color:#be123c; }
.triage-dot { width:10px; height:10px; border-radius:50%; display:inline-block; flex-shrink:0; }
.fci-attente { font-size:.72rem; color:#64748b; }
</style>

<?php if(empty($file_consultation_infirmier)): ?>
<div class="text-center py-5 text-muted">
    <i class="bi bi-clipboard2-check d-block" style="font-size:3.5rem;opacity:.3;color:#0d9488"></i>
    <h6 class="mt-3 fw-bold" style="color:#0d9488">File vide — aucun patient en attente de consultation</h6>
    <p class="small">Les patients dont les paramètres ont été pris apparaîtront ici.</p>
    <button type="button" class="btn btn-sm rounded-pill fw-bold mt-1"
            style="background:linear-gradient(135deg,#0d9488,#0f766e);color:#fff;border:none;"
            onclick="new bootstrap.Modal(document.getElementById('modalChercherPatientConsult')).show()">
        <i class="bi bi-search me-1"></i> Rechercher un patient
    </button>
</div>
<?php else: ?>

<div class="d-flex align-items-center gap-3 mb-4 flex-wrap">
    <div style="background:#f0fdfa;border:1px solid #99f6e4;border-radius:12px;padding:10px 18px;display:flex;align-items:center;gap:10px;">
        <i class="bi bi-clipboard2-pulse fs-4" style="color:#0d9488"></i>
        <div>
            <div style="font-size:1.6rem;font-weight:900;line-height:1;color:#0d9488"><?= count($file_consultation_infirmier) ?></div>
            <div style="font-size:.72rem;text-transform:uppercase;color:#0f766e;font-weight:700">patient<?= count($file_consultation_infirmier)>1?'s':'' ?> en attente</div>
        </div>
    </div>
    <p class="text-muted small mb-0">
        <i class="bi bi-info-circle me-1"></i>
        Ces patients ont eu leurs paramètres enregistrés et attendent votre consultation infirmière.
    </p>
</div>

<div class="fci-grid">
<?php
$triageColors = ['1'=>'#ef4444','2'=>'#f97316','3'=>'#eab308','4'=>'#22c55e','5'=>'#3b82f6'];
$triageLabels = ['1'=>'P1','2'=>'P2','3'=>'P3','4'=>'P4','5'=>'P5'];
foreach($file_consultation_infirmier as $fc):
    $initiales = strtoupper(substr($fc['nom'],0,1).substr($fc['prenom']??'',0,1));
    $age = '';
    if(!empty($fc['date_naissance'])) {
        $diff = (new DateTime())->diff(new DateTime($fc['date_naissance']));
        $age  = $diff->y . ' ans';
    }
    $n = (int)($fc['niveau_triage'] ?? 3);
    $triCls   = $triageColors[$n] ?? '#eab308';
    $triLabel = $triageLabels[$n] ?? 'P3';
    // Durée d'attente
    $attenteMin = '';
    if(!empty($fc['heure_arrivee'])) {
        $mins = (int)round((time() - strtotime($fc['heure_arrivee'])) / 60);
        $attenteMin = $mins >= 60
            ? floor($mins/60).'h'.str_pad($mins%60,2,'0',STR_PAD_LEFT)
            : $mins.'min';
    }
    // Alertes vitaux
    $spo2Alert  = !empty($fc['spo2'])      && (float)$fc['spo2']      < 94;
    $poulsAlert = !empty($fc['pouls'])     && ((float)$fc['pouls'] > 100 || (float)$fc['pouls'] < 50);
    $tempAlert  = !empty($fc['temperature']) && ((float)$fc['temperature'] > 38.5 || (float)$fc['temperature'] < 36);
    $taAlert    = !empty($fc['tension_sys']) && ((float)$fc['tension_sys'] > 160 || (float)$fc['tension_sys'] < 90);
?>
<div class="fci-card">
    <div class="fci-card-top">
        <div class="fci-avatar"><?= $initiales ?></div>
        <div class="flex-grow-1 min-width-0">
            <div class="fw-bold" style="font-size:.92rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
                <?= htmlspecialchars(strtoupper($fc['nom']).' '.($fc['prenom']??'')) ?>
            </div>
            <div style="font-size:.73rem;color:#64748b">
                <?= $age ? $age.' • ' : '' ?><?= $fc['sexe']==='M'?'Masc.':($fc['sexe']==='F'?'Fém.':'') ?>
                &nbsp;·&nbsp;<span style="font-weight:600"><?= htmlspecialchars($fc['dossier_numero']??'') ?></span>
            </div>
            <div class="mt-1 d-flex align-items-center gap-2 flex-wrap">
                <span class="triage-dot" style="background:<?= $triCls ?>"></span>
                <span style="font-size:.7rem;font-weight:800;color:<?= $triCls ?>"><?= $triLabel ?></span>
                <?php if($attenteMin): ?>
                <span class="fci-attente"><i class="bi bi-clock me-1"></i><?= $attenteMin ?></span>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php if(!empty($fc['motif_admission'])): ?>
    <div style="padding:4px 16px 6px;font-size:.78rem;color:#475569;font-style:italic;">
        « <?= htmlspecialchars(substr($fc['motif_admission'],0,80)) ?> »
    </div>
    <?php endif; ?>

    <!-- Vitaux -->
    <?php if($fc['tension_sys'] || $fc['pouls'] || $fc['spo2'] || $fc['temperature']): ?>
    <div class="fci-vitals">
        <?php if($fc['tension_sys']): ?><span class="fci-vital-chip <?= $taAlert?'alert':'' ?>">
            <i class="bi bi-activity"></i> <?= $fc['tension_sys'].'/'.$fc['tension_dia'] ?> mmHg</span><?php endif; ?>
        <?php if($fc['pouls']): ?><span class="fci-vital-chip <?= $poulsAlert?'alert':'' ?>">
            <i class="bi bi-heart-pulse"></i> <?= $fc['pouls'] ?> bpm</span><?php endif; ?>
        <?php if($fc['spo2']): ?><span class="fci-vital-chip <?= $spo2Alert?'alert':'' ?>">
            <i class="bi bi-lungs"></i> <?= $fc['spo2'] ?>%</span><?php endif; ?>
        <?php if($fc['temperature']): ?><span class="fci-vital-chip <?= $tempAlert?'alert':'' ?>">
            <i class="bi bi-thermometer-half"></i> <?= $fc['temperature'] ?>°C</span><?php endif; ?>
    </div>
    <?php endif; ?>

    <div class="fci-card-actions">
        <a href="<?= BASE_URL ?>infirmier/consultation/<?= (int)$fc['patient_id'] ?>"
           class="btn btn-sm fw-bold flex-grow-1 rounded-pill"
           style="background:linear-gradient(135deg,#0d9488,#0f766e);color:#fff;border:none;">
            <i class="bi bi-stethoscope me-1"></i> Consulter
        </a>
        <a href="<?= BASE_URL ?>patients/dossier/<?= (int)$fc['patient_id'] ?>"
           class="btn btn-sm btn-outline-secondary rounded-pill" title="Voir le dossier">
            <i class="bi bi-folder2-open"></i>
        </a>
    </div>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>

</div><!-- /tabFileConsult -->

<!-- ══════════════ ONGLET 6 : MES CONSULTATIONS ══════════════ -->
<div class="tab-pane fade tab-content-area" id="tabMesConsult">
<?php
$mes_consultations = $mes_consultations ?? [];
$nb_total   = count($mes_consultations);
$nb_hosp    = count(array_filter($mes_consultations, fn($c) => !empty($c['hosp_id'])));
$nb_orient  = count(array_filter($mes_consultations, fn($c) => ($c['devenir'] ?? '') === 'RETOUR_DOMICILE'));
$nb_encours = count(array_filter($mes_consultations, fn($c) => empty($c['hosp_id']) && ($c['devenir'] ?? '') !== 'RETOUR_DOMICILE'));
?>

<style>
.mc-kpi-row   { display:flex; flex-wrap:wrap; gap:14px; margin-bottom:24px; }
.mc-kpi       { flex:1 1 140px; background:#fff; border-radius:14px; padding:16px 20px;
                box-shadow:0 2px 10px rgba(0,0,0,.06); display:flex; flex-direction:column; }
.mc-kpi .kpi-val { font-size:2rem; font-weight:900; line-height:1; }
.mc-kpi .kpi-lbl { font-size:.72rem; color:#64748b; text-transform:uppercase; margin-top:4px; font-weight:700; }
.mc-table     { background:#fff; border-radius:14px; box-shadow:0 2px 10px rgba(0,0,0,.06); overflow:hidden; }
.mc-table table { margin:0; }
.mc-table th  { background:#f8fafc; font-size:.73rem; text-transform:uppercase; color:#64748b; font-weight:800; border-bottom:2px solid #e2e8f0; }
.mc-table td  { vertical-align:middle; font-size:.84rem; }
.devenir-badge { display:inline-flex; align-items:center; gap:4px; padding:2px 9px; border-radius:10px; font-size:.7rem; font-weight:800; }
.devenir-encours    { background:#eff6ff; color:#1e40af; }
.devenir-hosp       { background:#f0fdf4; color:#166534; }
.devenir-domicile   { background:#f1f5f9; color:#475569; }
.devenir-autre      { background:#fef9c3; color:#713f12; }
</style>

<!-- KPIs -->
<div class="mc-kpi-row">
    <div class="mc-kpi">
        <div class="kpi-val text-primary"><?= $nb_total ?></div>
        <div class="kpi-lbl">Consultations (72h)</div>
    </div>
    <div class="mc-kpi">
        <div class="kpi-val text-warning"><?= $nb_encours ?></div>
        <div class="kpi-lbl">En cours / à orienter</div>
    </div>
    <div class="mc-kpi">
        <div class="kpi-val text-success"><?= $nb_hosp ?></div>
        <div class="kpi-lbl">Hospitalisés</div>
    </div>
    <div class="mc-kpi">
        <div class="kpi-val text-secondary"><?= $nb_orient ?></div>
        <div class="kpi-lbl">Retour à domicile</div>
    </div>
</div>

<!-- Recherche -->
<div class="mb-3 d-flex gap-2 align-items-center">
    <div class="input-group" style="max-width:360px">
        <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
        <input type="text" id="mcSearchInput" class="form-control border-start-0 ps-0"
               placeholder="Rechercher un patient…" oninput="filtrerMesConsultUrgences()">
    </div>
    <span class="text-muted small"><?= $nb_total ?> résultat<?= $nb_total>1?'s':'' ?></span>
</div>

<?php if(empty($mes_consultations)): ?>
<div class="text-center py-5 text-muted">
    <i class="bi bi-clipboard2-x d-block" style="font-size:3rem;opacity:.35"></i>
    <h6 class="mt-3">Aucune consultation infirmière dans les 72 dernières heures</h6>
    <p class="small">Les consultations que vous effectuez apparaîtront ici.</p>
</div>
<?php else: ?>
<div class="mc-table">
<table class="table table-hover mb-0" id="mcTable">
    <thead>
        <tr>
            <th>Patient</th>
            <th>Date</th>
            <th>Motif</th>
            <th>Devenir</th>
            <th>Lit assigné</th>
            <th class="text-center">Actions</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach($mes_consultations as $mc):
        $age = '';
        if(!empty($mc['date_naissance'])) {
            $diff = (new DateTime())->diff(new DateTime($mc['date_naissance']));
            $age  = $diff->y . ' ans';
        }
        $devenir      = $mc['devenir'] ?? '';
        $devenirClass = 'devenir-encours';
        $devenirLabel = 'En cours';
        if ($devenir === 'HOSPITALISATION' || !empty($mc['hosp_id'])) {
            $devenirClass = 'devenir-hosp';
            $devenirLabel = 'Hospitalisé';
        } elseif ($devenir === 'RETOUR_DOMICILE') {
            $devenirClass = 'devenir-domicile';
            $devenirLabel = 'Retour domicile';
        } elseif (!empty($devenir) && $devenir !== 'EN_COURS') {
            $devenirClass = 'devenir-autre';
            $devenirLabel = htmlspecialchars($devenir);
        }
        $litInfo = trim(($mc['nom_chambre'] ? $mc['nom_chambre'] . ' / ' : '') . ($mc['nom_lit'] ?? ''));
    ?>
    <tr class="mc-row" data-search="<?= strtolower(htmlspecialchars($mc['nom'].' '.$mc['prenom'].' '.$mc['dossier_numero'])) ?>">
        <td>
            <div class="fw-bold" style="font-size:.88rem"><?= htmlspecialchars($mc['nom'].' '.($mc['prenom']??'')) ?></div>
            <div class="text-muted" style="font-size:.73rem"><?= $age ? $age.' • ' : '' ?><?= $mc['sexe']==='M'?'Masculin':($mc['sexe']==='F'?'Féminin':'') ?></div>
            <div class="text-muted" style="font-size:.73rem"><?= htmlspecialchars($mc['dossier_numero'] ?? '') ?></div>
        </td>
        <td class="text-muted" style="font-size:.8rem"><?= date('d/m H:i', strtotime($mc['date_consultation'])) ?></td>
        <td style="max-width:200px;font-size:.82rem"><?= nl2br(htmlspecialchars(substr($mc['motif_consultation'] ?? '—', 0, 100))) ?></td>
        <td><span class="devenir-badge <?= $devenirClass ?>"><?= $devenirLabel ?></span></td>
        <td class="text-muted" style="font-size:.8rem"><?= $litInfo ?: '—' ?></td>
        <td class="text-center">
            <div class="d-flex gap-1 justify-content-center flex-wrap">
                <a href="<?= BASE_URL ?>patients/dossier/<?= (int)$mc['patient_id'] ?>"
                   class="btn btn-xs btn-outline-primary rounded-pill" style="font-size:.72rem;padding:2px 9px"
                   title="Ouvrir le dossier">
                    <i class="bi bi-folder2-open"></i> Dossier
                </a>
                <?php if(empty($mc['hosp_id'])): ?>
                <button type="button"
                        class="btn btn-xs btn-success rounded-pill" style="font-size:.72rem;padding:2px 9px"
                        onclick="mcOuvrirModalHosp(<?= (int)$mc['patient_id'] ?>, <?= (int)$mc['consultation_id'] ?>, '<?= htmlspecialchars(addslashes($mc['nom'].' '.($mc['prenom']??'')), ENT_QUOTES) ?>')"
                        title="Hospitaliser ce patient">
                    <i class="bi bi-hospital"></i> Hospitaliser
                </button>
                <?php else: ?>
                <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill" style="font-size:.7rem;padding:3px 8px">
                    <i class="bi bi-check2-circle"></i> Hospitalisé
                </span>
                <?php endif; ?>
            </div>
        </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
<?php endif; ?>

<script>
function filtrerMesConsultUrgences() {
    const q = (document.getElementById('mcSearchInput')?.value || '').toLowerCase().trim();
    document.querySelectorAll('#mcTable .mc-row').forEach(function(row) {
        row.style.display = (!q || row.dataset.search.includes(q)) ? '' : 'none';
    });
}
</script>

</div><!-- /tabMesConsult -->

<!-- ══════════════ ONGLET C : SOINS À FAIRE ══════════════ -->
<div class="tab-pane fade tab-content-area" id="tabSoins">
<style>
/* ── Soins à faire ── */
.soins-timeline { display:flex; flex-direction:column; gap:0; }
.soin-row {
    display:flex; align-items:center; gap:14px;
    padding:12px 16px; border-bottom:1px solid #f1f5f9;
    background:#fff; transition:background .12s;
}
.soin-row:first-child { border-radius:14px 14px 0 0; }
.soin-row:last-child  { border-radius:0 0 14px 14px; border-bottom:none; }
.soin-row:hover { background:#f8fafc; }
.soin-row.retard   { border-left:3px solid #ef4444; }
.soin-row.urgent   { border-left:3px solid #f59e0b; }
.soin-row.planifie { border-left:3px solid #3b82f6; }
.soin-row.non_date { border-left:3px solid #94a3b8; }
.soin-time-badge {
    min-width:64px; text-align:center; flex-shrink:0;
    border-radius:10px; padding:5px 6px;
}
.soin-time-badge .th  { font-size:.6rem; font-weight:700; text-transform:uppercase; letter-spacing:.3px; }
.soin-time-badge .tv  { font-size:.95rem; font-weight:900; line-height:1.1; }
.soin-type-icon {
    width:38px; height:38px; border-radius:10px;
    display:flex; align-items:center; justify-content:center;
    font-size:1rem; flex-shrink:0;
}
.soin-info-main { font-size:.84rem; font-weight:700; color:#0f172a; }
.soin-info-sub  { font-size:.72rem; color:#64748b; margin-top:1px; }
.soin-patient   { font-size:.74rem; font-weight:600; }
.soin-btn-done  {
    margin-left:auto; flex-shrink:0;
    background:#f0fdf4; color:#16a34a; border:1.5px solid #86efac;
    border-radius:8px; padding:5px 12px; font-size:.72rem; font-weight:700;
    cursor:pointer; transition:.15s; white-space:nowrap;
}
.soin-btn-done:hover { background:#16a34a; color:#fff; }
.soins-section-header {
    display:flex; align-items:center; gap:8px;
    padding:10px 16px; background:#f8fafc;
    border-bottom:1px solid #e2e8f0;
    font-size:.72rem; font-weight:800; text-transform:uppercase; letter-spacing:.6px;
    border-radius:14px 14px 0 0; margin-top:16px;
}
.soins-kpi-bar {
    display:grid; grid-template-columns:repeat(4,1fr); gap:12px;
    margin-bottom:20px;
}
.soins-kpi {
    background:#fff; border-radius:14px; padding:14px 16px;
    box-shadow:0 2px 10px rgba(0,0,0,.05); text-align:center;
}
.soins-kpi .val { font-size:1.8rem; font-weight:900; line-height:1; }
.soins-kpi .lbl { font-size:.65rem; font-weight:700; text-transform:uppercase;
                  letter-spacing:.5px; color:#94a3b8; margin-top:3px; }
</style>

<?php
$soins_a_faire = $soins_a_faire ?? [];
$now_ts = time();

// Grouper : retard, <2h, >2h, sans date
$groupes = ['retard'=>[], 'bientot'=>[], 'planifie'=>[], 'non_date'=>[]];
foreach ($soins_a_faire as $s) {
    if (empty($s['date_prevue'])) {
        $groupes['non_date'][] = $s;
    } else {
        $ts = strtotime($s['date_prevue']);
        if ($ts < $now_ts) $groupes['retard'][] = $s;
        elseif ($ts <= $now_ts + 7200) $groupes['bientot'][] = $s;
        else $groupes['planifie'][] = $s;
    }
}

$typeCfg = [
    'MEDICAMENT'   => ['icon'=>'bi-capsule-pill', 'bg'=>'#f5f3ff','color'=>'#7c3aed'],
    'PANSEMENT'    => ['icon'=>'bi-bandaid-fill', 'bg'=>'#fef2f2','color'=>'#dc2626'],
    'INJECTION'    => ['icon'=>'bi-syringe',      'bg'=>'#eff6ff','color'=>'#2563eb'],
    'PERFUSION'    => ['icon'=>'bi-droplet-fill',  'bg'=>'#f0f9ff','color'=>'#0284c7'],
    'SURVEILLANCE' => ['icon'=>'bi-eye-fill',      'bg'=>'#f0fdf4','color'=>'#16a34a'],
    'BILAN'        => ['icon'=>'bi-flask-fill',    'bg'=>'#fefce8','color'=>'#ca8a04'],
    'KINE'         => ['icon'=>'bi-person-arms-up','bg'=>'#fdf4ff','color'=>'#a21caf'],
    'AUTRE'        => ['icon'=>'bi-clipboard2-check','bg'=>'#f8fafc','color'=>'#64748b'],
];

// KPIs
$nbRetard   = count($groupes['retard']);
$nbBientot  = count($groupes['bientot']);
$nbPlanifie = count($groupes['planifie']);
$nbNonDate  = count($groupes['non_date']);
$nbTotal    = count($soins_a_faire);
?>

<?php if ($nbTotal === 0): ?>
<div class="empty-state" style="padding:60px 20px;text-align:center;">
    <i class="bi bi-clipboard2-check-fill text-success" style="font-size:3rem;display:block;margin-bottom:12px"></i>
    <h5 class="text-success">Aucun soin planifié en attente</h5>
    <p class="text-muted">Tous les soins ont été réalisés ou aucun soin n'est planifié.</p>
</div>
<?php else: ?>

<!-- KPIs soins -->
<div class="soins-kpi-bar">
    <div class="soins-kpi">
        <div class="val" style="color:<?= $nbRetard>0?'#dc2626':'#0f172a' ?>"><?= $nbRetard ?></div>
        <div class="lbl" style="color:<?= $nbRetard>0?'#dc2626':'#94a3b8' ?>">En retard</div>
    </div>
    <div class="soins-kpi">
        <div class="val" style="color:#d97706"><?= $nbBientot ?></div>
        <div class="lbl">Dans les 2h</div>
    </div>
    <div class="soins-kpi">
        <div class="val" style="color:#3b82f6"><?= $nbPlanifie ?></div>
        <div class="lbl">Planifiés</div>
    </div>
    <div class="soins-kpi">
        <div class="val" style="color:#94a3b8"><?= $nbNonDate ?></div>
        <div class="lbl">Non datés</div>
    </div>
</div>

<!-- Filtre par patient -->
<div class="mb-3 d-flex align-items-center gap-3">
    <input type="text" id="soinsSearchInput" placeholder="Filtrer par patient ou type de soin…"
           oninput="filtrerSoins(this.value)"
           style="flex:1;max-width:360px;border:1.5px solid #e2e8f0;border-radius:10px;padding:7px 12px;font-size:.83rem;">
    <label style="display:flex;align-items:center;gap:6px;font-size:.8rem;font-weight:600;cursor:pointer;">
        <input type="checkbox" id="chkRetardOnly" onchange="filtrerSoins(document.getElementById('soinsSearchInput').value)">
        Retards uniquement
    </label>
</div>

<div id="soinsContainer">
<?php
$groupesCfg = [
    'retard'   => ['label'=>'⚠ En retard',         'color'=>'#dc2626','bg'=>'#fef2f2'],
    'bientot'  => ['label'=>'⏱ Dans les 2 heures',  'color'=>'#d97706','bg'=>'#fffbeb'],
    'planifie' => ['label'=>'📅 Planifiés',          'color'=>'#3b82f6','bg'=>'#eff6ff'],
    'non_date' => ['label'=>'📋 Sans date définie',  'color'=>'#64748b','bg'=>'#f8fafc'],
];
foreach ($groupesCfg as $gKey => $gCfg):
    if (empty($groupes[$gKey])) continue;
?>
<div class="soin-group" data-groupe="<?= $gKey ?>">
    <div class="soins-section-header" style="background:<?= $gCfg['bg'] ?>;color:<?= $gCfg['color'] ?>">
        <i class="bi bi-circle-fill" style="font-size:.5rem"></i>
        <?= $gCfg['label'] ?> — <strong><?= count($groupes[$gKey]) ?> soin(s)</strong>
    </div>
    <div class="soins-timeline">
    <?php foreach ($groupes[$gKey] as $s):
        $ts_s    = !empty($s['date_prevue']) ? strtotime($s['date_prevue']) : 0;
        $typeKey = strtoupper($s['type_soin'] ?? 'AUTRE');
        $tc      = $typeCfg[$typeKey] ?? $typeCfg['AUTRE'];
        $lit     = trim(($s['nom_chambre']??'').(!empty($s['nom_lit'])?' / '.$s['nom_lit']:''));
        $isRetard = ($gKey === 'retard');
    ?>
    <div class="soin-row <?= $gKey ?>" data-soin-id="<?= (int)$s['id'] ?>"
         data-search="<?= strtolower(htmlspecialchars($s['nom'].' '.$s['prenom'].' '.$s['type_soin'].' '.($s['description']??''))) ?>">

        <!-- Heure -->
        <div class="soin-time-badge"
             style="background:<?= $ts_s ? ($isRetard?'#fef2f2':'#fff7ed') : '#f8fafc' ?>;
                    color:<?= $ts_s ? ($isRetard?'#dc2626':'#d97706') : '#94a3b8' ?>">
            <?php if ($ts_s): ?>
            <div class="th"><?= $isRetard ? 'Dû à' : 'Prévu' ?></div>
            <div class="tv"><?= date('H:i', $ts_s) ?></div>
            <?php else: ?>
            <div class="th">Heure</div>
            <div class="tv">--</div>
            <?php endif; ?>
        </div>

        <!-- Icône type -->
        <div class="soin-type-icon" style="background:<?= $tc['bg'] ?>;color:<?= $tc['color'] ?>">
            <i class="bi <?= $tc['icon'] ?>"></i>
        </div>

        <!-- Info -->
        <div style="flex:1;min-width:0">
            <div class="soin-info-main"><?= htmlspecialchars(ucfirst(strtolower($s['type_soin']??'Soin'))) ?></div>
            <?php if (!empty($s['description'])): ?>
            <div class="soin-info-sub"><?= htmlspecialchars(mb_substr($s['description'],0,70)) ?><?= mb_strlen($s['description'])>70?'…':'' ?></div>
            <?php endif; ?>
            <div class="soin-patient mt-1">
                <a href="<?= BASE_URL ?>patients/dossier/<?= (int)$s['patient_id'] ?>"
                   style="color:#1e40af;text-decoration:none;font-weight:700">
                    <?= htmlspecialchars(strtoupper($s['nom']).' '.$s['prenom']) ?>
                </a>
                <span style="color:#94a3b8"> · <?= htmlspecialchars($s['dossier_numero']??'') ?></span>
                <?php if ($lit): ?>
                <span style="color:#64748b"> · <i class="bi bi-hospital" style="font-size:.7rem"></i> <?= htmlspecialchars($lit) ?></span>
                <?php endif; ?>
                <?php if (!empty($s['nom_service'])): ?>
                <span style="color:#94a3b8"> · <?= htmlspecialchars($s['nom_service']) ?></span>
                <?php endif; ?>
            </div>
        </div>

        <!-- Bouton marquer fait -->
        <button class="soin-btn-done" onclick="marquerSoinFait(<?= (int)$s['id'] ?>, this)"
                title="Marquer ce soin comme réalisé">
            <i class="bi bi-check-lg me-1"></i>Fait
        </button>
    </div>
    <?php endforeach; ?>
    </div>
</div>
<?php endforeach; ?>
</div><!-- /soinsContainer -->

<?php endif; ?>

<script>
function filtrerSoins(q) {
    q = (q||'').toLowerCase();
    const retardOnly = document.getElementById('chkRetardOnly')?.checked;
    document.querySelectorAll('.soin-row').forEach(row => {
        const matchQ = !q || (row.dataset.search||'').includes(q);
        const matchR = !retardOnly || row.classList.contains('retard');
        row.style.display = (matchQ && matchR) ? '' : 'none';
    });
    // Masquer les sections vides
    document.querySelectorAll('.soin-group').forEach(g => {
        const visible = [...g.querySelectorAll('.soin-row')].some(r => r.style.display !== 'none');
        g.style.display = visible ? '' : 'none';
    });
}

function marquerSoinFait(soinId, btn) {
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>';
    fetch('<?= BASE_URL ?>hospitalisation/marquer-soin-fait', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
        body: 'soin_id=' + soinId
    })
    .then(r => r.json())
    .then(d => {
        if (d.success) {
            const row = btn.closest('.soin-row');
            row.style.transition = 'opacity .3s, transform .3s';
            row.style.opacity = '0'; row.style.transform = 'translateX(30px)';
            setTimeout(() => {
                row.remove();
                // Màj compteurs
                const retardBadge = document.querySelector('#tabBtnSoins .tab-badge');
                if (retardBadge) { let n = parseInt(retardBadge.textContent)-1; retardBadge.textContent = n; if(!n) retardBadge.remove(); }
            }, 320);
        } else {
            btn.disabled = false; btn.innerHTML = '<i class="bi bi-check-lg me-1"></i>Fait';
            alert(d.message || 'Erreur');
        }
    })
    .catch(() => { btn.disabled = false; btn.innerHTML = '<i class="bi bi-check-lg me-1"></i>Fait'; });
}
</script>

</div><!-- /tabSoins -->

</div><!-- /tab-content -->
</div><!-- /ckApp -->

<script>
// ── Direction / Thème / Vue ──────────────────────────────────────────────────
(function(){
  const app = document.getElementById('ckApp');

  function bindSeg(id, attr, target, after){
    document.getElementById(id)?.addEventListener('click', function(e){
      const b = e.target.closest('button'); if(!b) return;
      this.querySelectorAll('button').forEach(x=>x.classList.remove('on'));
      b.classList.add('on');
      const val = b.dataset[attr];
      target.setAttribute('data-'+attr, val);
      try{ localStorage.setItem('ck_'+attr, val); }catch(_){}
      if(after) after(val);
    });
  }

  bindSeg('ckSegDir',   'dir',   app);
  bindSeg('ckSegTheme', 'theme', document.body);
  bindSeg('ckSegView',  'view',  document.body);

  // Restaurer préférences
  try{
    const d=localStorage.getItem('ck_dir'), t=localStorage.getItem('ck_theme'), v=localStorage.getItem('ck_view');
    if(d){ app.setAttribute('data-dir',d); document.querySelector('#ckSegDir [data-dir="'+d+'"]')?.classList.add('on'); document.querySelector('#ckSegDir .on:not([data-dir="'+d+'"])')?.classList.remove('on'); }
    if(t){ document.body.setAttribute('data-theme',t); document.querySelectorAll('#ckSegTheme button').forEach(x=>x.classList.toggle('on',x.dataset.theme===t)); }
    if(v){ document.body.setAttribute('data-view',v); document.querySelectorAll('#ckSegView button').forEach(x=>x.classList.toggle('on',x.dataset.view===v)); }
  }catch(_){}
})();

// ── Tab switching ────────────────────────────────────────────────────────────
var _ckTabIds = ['tabUrgences','tabHospitaliser','tabServices','tabLits','tabFileConsult','tabMesConsult','tabSoins'];

function ckTabChange(tabId){
  // Masquer tous les panneaux
  _ckTabIds.forEach(function(id){
    var p = document.getElementById(id);
    if(p){ p.style.display='none'; p.classList.remove('show','active'); }
  });
  // Afficher le panneau cible
  var target = document.getElementById(tabId);
  if(target){ target.style.display='block'; target.classList.add('show','active'); }
  // Mettre à jour boutons
  document.querySelectorAll('#ckTabs .ck-tab').forEach(function(btn, i){
    btn.classList.toggle('on', _ckTabIds[i]===tabId);
  });
  // Action row visible uniquement sur Urgences
  var ar = document.getElementById('ckActionRow');
  if(ar) ar.style.display = tabId==='tabUrgences' ? 'flex' : 'none';
  // Mémoriser onglet actif
  try{ localStorage.setItem('ck_tab', tabId); }catch(_){}
}

// Restaurer dernier onglet actif
(function(){
  try{
    var last = localStorage.getItem('ck_tab');
    if(last && _ckTabIds.includes(last) && last !== 'tabUrgences') ckTabChange(last);
  }catch(_){}
})();

// ── Timers d'attente (cartes + liste) ────────────────────────────────────────
(function(){
  function fmtWait(ts){
    const sec = Math.floor(Date.now()/1000) - ts;
    if(sec < 0) return '--';
    const h = Math.floor(sec/3600), m = Math.floor((sec%3600)/60);
    return (h?h+'h':'') + String(m).padStart(2,'0')+'min';
  }
  function updateAll(){
    document.querySelectorAll('[id^="ck-wait-"]').forEach(function(el){
      const card = el.closest('[data-ts-arrivee]');
      const listTs = el.dataset.ts;
      const ts = card ? parseInt(card.dataset.tsArrivee) : parseInt(listTs||0);
      if(!ts) return;
      const sec = Math.floor(Date.now()/1000) - ts;
      const txt = fmtWait(ts);
      el.textContent = txt;
      if(el.classList.contains('wait')) el.classList.toggle('long', sec > 3600);
    });
  }
  updateAll();
  setInterval(updateAll, 30000);
})();

// ── Horloge ──────────────────────────────────────────────────────────────────
setInterval(function(){
  const d=new Date();
  const t=[d.getHours(),d.getMinutes(),d.getSeconds()].map(x=>String(x).padStart(2,'0')).join(':');
  const el=document.getElementById('cockpit-clock'); if(el) el.textContent=t;
},1000);
</script>

<!-- ══ MODAL NOUVEAU PATIENT (INFIRMIER URGENCES) ══ -->
<div class="modal fade" id="modalNouveauPatientUrgences" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-0 text-white rounded-top-4" style="background:linear-gradient(135deg,#1e40af,#2563eb);">
                <div>
                    <h5 class="modal-title fw-bold mb-0">
                        <i class="bi bi-person-plus-fill me-2"></i>Nouveau dossier patient
                    </h5>
                    <small class="opacity-75" style="font-size:.8rem;">Urgences · Enregistrement rapide</small>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="<?= BASE_URL ?>patients/store" method="POST">
                <input type="hidden" name="context" value="urgences">
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <!-- Identité -->
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-uppercase text-secondary mb-1">NOM <span class="text-danger">*</span></label>
                            <input type="text" name="nom" class="form-control" required
                                   placeholder="Nom de famille" style="text-transform:uppercase;border-radius:10px;border:1.5px solid #e2e8f0;padding:10px 14px;font-weight:600;"
                                   oninput="this.value=this.value.toUpperCase()">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-uppercase text-secondary mb-1">PRÉNOM <span class="text-danger">*</span></label>
                            <input type="text" name="prenom" class="form-control" required placeholder="Prénom"
                                   style="border-radius:10px;border:1.5px solid #e2e8f0;padding:10px 14px;font-weight:600;">
                        </div>
                        <div class="col-md-4">
                            <div class="d-flex align-items-center justify-content-between mb-1">
                                <label class="form-label small fw-bold text-uppercase text-secondary mb-0">DATE DE NAISSANCE <span class="text-danger">*</span></label>
                                <div class="btn-group btn-group-sm" role="group" style="font-size:.7rem;">
                                    <button type="button" id="urgNpBtnDate" class="btn btn-primary py-0 px-2"
                                            style="font-size:.68rem;border-radius:6px 0 0 6px;" onclick="urgNpSetDobMode('date')">Date</button>
                                    <button type="button" id="urgNpBtnAge" class="btn btn-outline-secondary py-0 px-2"
                                            style="font-size:.68rem;border-radius:0 6px 6px 0;" onclick="urgNpSetDobMode('age')">Âge</button>
                                </div>
                            </div>
                            <input type="date" name="date_naissance" id="urgNpInputDdn" class="form-control"
                                   style="border-radius:10px;border:1.5px solid #e2e8f0;padding:10px 14px;" required>
                            <div class="input-group d-none" id="urgNpFieldAge">
                                <input type="number" id="urgNpInputAge" class="form-control"
                                       placeholder="Âge estimé" min="0" max="130"
                                       style="border-radius:10px 0 0 10px;border:1.5px solid #e2e8f0;padding:10px 14px;"
                                       oninput="urgNpUpdateEstimate()">
                                <span class="input-group-text" style="border-radius:0 10px 10px 0;border:1.5px solid #e2e8f0;border-left:none;background:#f8fafc;font-weight:700;">ans</span>
                            </div>
                            <small class="text-muted d-none" id="urgNpDobEstimate" style="font-size:.72rem;"></small>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-uppercase text-secondary mb-1">SEXE <span class="text-danger">*</span></label>
                            <select name="sexe" class="form-select" required
                                    style="border-radius:10px;border:1.5px solid #e2e8f0;padding:10px 14px;font-weight:600;">
                                <option value="">— Sélectionner —</option>
                                <option value="M">Masculin</option>
                                <option value="F">Féminin</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-uppercase text-secondary mb-1">Groupe sanguin</label>
                            <select name="groupe_sanguin" class="form-select"
                                    style="border-radius:10px;border:1.5px solid #e2e8f0;padding:10px 14px;font-weight:600;">
                                <option value="">— Inconnu —</option>
                                <?php foreach (['A+','A-','B+','B-','AB+','AB-','O+','O-'] as $g): ?>
                                <option value="<?= $g ?>"><?= $g ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-uppercase text-secondary mb-1">Téléphone</label>
                            <input type="tel" name="telephone" class="form-control" placeholder="+237 6XX XXX XXX"
                                   style="border-radius:10px;border:1.5px solid #e2e8f0;padding:10px 14px;">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-uppercase text-secondary mb-1">Contact d'urgence</label>
                            <input type="text" name="contact_nom" class="form-control" placeholder="Nom du contact"
                                   style="border-radius:10px;border:1.5px solid #e2e8f0;padding:10px 14px;">
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold text-uppercase text-secondary mb-1">Allergies connues</label>
                            <input type="text" name="allergies" class="form-control" placeholder="Ex: Pénicilline, AINS..."
                                   style="border-radius:10px;border:1.5px solid #e2e8f0;padding:10px 14px;">
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-2 mt-3 p-3 rounded-3" style="background:#eff6ff;border:1px solid #bfdbfe;">
                        <i class="bi bi-info-circle-fill text-primary"></i>
                        <small style="font-size:.82rem;color:#1e40af;">Après création, vous serez redirigé(e) vers le formulaire de <strong>prise de paramètres</strong>.</small>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0 px-4 pb-4 gap-2">
                    <button type="button" class="btn btn-light rounded-pill px-4 fw-semibold" data-bs-dismiss="modal"
                            style="border:1.5px solid #e2e8f0;">Annuler</button>
                    <button type="submit" class="btn rounded-pill px-4 fw-bold flex-grow-1"
                            style="background:linear-gradient(135deg,#2563eb,#1d4ed8);color:#fff;border:none;padding:10px 20px;">
                        <i class="bi bi-person-plus-fill me-1"></i>Créer et prendre les paramètres
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
/* ── Bascule Date de naissance / Âge estimé (modal nouveau patient urgences) ── */
function urgNpSetDobMode(mode) {
    const isDate   = (mode === 'date');
    const inputDdn = document.getElementById('urgNpInputDdn');
    const fieldAge = document.getElementById('urgNpFieldAge');
    const inputAge = document.getElementById('urgNpInputAge');
    const estimate = document.getElementById('urgNpDobEstimate');
    const btnDate  = document.getElementById('urgNpBtnDate');
    const btnAge   = document.getElementById('urgNpBtnAge');

    inputDdn.classList.toggle('d-none', !isDate);
    fieldAge.classList.toggle('d-none', isDate);
    estimate.classList.toggle('d-none', isDate);

    btnDate.className = 'btn py-0 px-2 ' + (isDate  ? 'btn-primary' : 'btn-outline-secondary');
    btnAge.className  = 'btn py-0 px-2 ' + (!isDate ? 'btn-primary' : 'btn-outline-secondary');
    btnDate.style.fontSize = btnAge.style.fontSize = '.68rem';

    if (isDate) {
        // Mode date : champ date requis, âge ignoré
        inputDdn.required = true;
        inputAge.required = false;
        inputAge.name     = '';
        inputAge.value    = '';
        estimate.textContent = '';
    } else {
        // Mode âge : la date est calculée côté serveur depuis age_estimatif
        inputDdn.required = false;
        inputDdn.value    = '';
        inputAge.required = true;
        inputAge.name     = 'age_estimatif';
        inputAge.focus();
    }
}

function urgNpUpdateEstimate() {
    const age      = parseInt(document.getElementById('urgNpInputAge').value, 10);
    const estimate = document.getElementById('urgNpDobEstimate');
    if (!isNaN(age) && age >= 0 && age <= 130) {
        estimate.textContent = 'Année de naissance estimée : ' + (new Date().getFullYear() - age);
    } else {
        estimate.textContent = '';
    }
}

/* Réinitialiser en mode date à chaque ouverture du modal */
document.getElementById('modalNouveauPatientUrgences')
    ?.addEventListener('show.bs.modal', () => urgNpSetDobMode('date'));
</script>

<!-- ══ MODAL ADMISSION RAPIDE ══ -->
<?php include __DIR__ . '/modal_admission.php'; ?>

<!-- ══ MODAL ASSIGNER LIT ══ -->
<div class="modal fade modal-assign" id="modalAssignBed" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius:14px;overflow:hidden">
            <div class="modal-header py-3">
                <h5 class="modal-title fw-bold text-white"><i class="bi bi-bed-fill me-2"></i>Installer le patient</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="alert alert-warning py-2 px-3 mb-3 fw-bold rounded-3" id="assignPatientName" style="font-size:.85rem"></div>
                <input type="hidden" id="assignPatientId">

                <div class="mb-3">
                    <label class="form-label fw-bold small text-uppercase text-muted">Motif d'hospitalisation</label>
                    <input type="text" class="form-control rounded-3" id="assignMotif" placeholder="Ex: Surveillance post-opératoire...">
                </div>

                <label class="form-label fw-bold small text-uppercase text-muted mb-1">Choisir un lit</label>
                <input type="text" class="form-control form-control-sm rounded-3 mb-2" id="litSearch" placeholder="🔍 Filtrer par service, chambre...">
                <div id="litsList" style="max-height:300px;overflow-y:auto">
                    <?php foreach($lits_disponibles as $l): ?>
                    <div class="lit-select-item" data-lit-id="<?= $l['id'] ?>" data-search="<?= strtolower($l['nom_service'].' '.$l['nom_chambre'].' '.$l['nom_lit']) ?>">
                        <input type="radio" name="lit_radio" value="<?= $l['id'] ?>" id="lit<?= $l['id'] ?>">
                        <label for="lit<?= $l['id'] ?>" class="flex-grow-1 mb-0" style="cursor:pointer">
                            <strong class="small"><?= htmlspecialchars($l['nom_service']) ?></strong>
                            <span class="text-muted small"> &mdash; <?= htmlspecialchars($l['nom_chambre']) ?> &mdash; <?= htmlspecialchars($l['nom_lit']) ?></span>
                        </label>
                        <span class="badge bg-success-subtle text-success border" style="font-size:.62rem">LIBRE</span>
                    </div>
                    <?php endforeach; ?>
                    <?php if(empty($lits_disponibles)): ?>
                    <div class="text-center text-danger py-5"><i class="bi bi-exclamation-circle fs-3 d-block mb-2"></i>Aucun lit disponible actuellement</div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary rounded-pill" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-success rounded-pill px-4 shadow" id="btnValiderInstall" onclick="validerInstallation()">
                    <i class="bi bi-check-circle-fill me-1"></i> Valider l'installation
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ══ MODAL ORIENTATION URGENCE ══ -->
<div class="modal fade" id="modalOrienter" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header bg-dark text-white" style="border-radius:14px 14px 0 0">
                <h5 class="modal-title fw-bold"><i class="bi bi-signpost-split me-2"></i>Orienter le patient</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="<?= BASE_URL ?>urgences/transferer" method="POST">
                <input type="hidden" name="admission_id" id="orientAdmissionId">
                <input type="hidden" name="patient_id" id="orientPatientId">
                <div class="modal-body p-4">
                    <label class="fw-bold small text-muted text-uppercase mb-2 d-block">Décision médicale</label>
                    <div class="d-flex gap-3 mb-3">
                        <label class="flex-grow-1 p-3 border rounded-3 text-center orient-choice" style="cursor:pointer">
                            <input type="radio" name="decision" value="HOSPITALISATION" class="d-none">
                            <i class="bi bi-house-heart-fill d-block fs-3 text-primary mb-1"></i>
                            <span class="small fw-bold">Hospitaliser</span>
                        </label>
                        <label class="flex-grow-1 p-3 border rounded-3 text-center orient-choice" style="cursor:pointer">
                            <input type="radio" name="decision" value="SORTIE" class="d-none">
                            <i class="bi bi-door-open-fill d-block fs-3 text-success mb-1"></i>
                            <span class="small fw-bold">Sortie</span>
                        </label>
                    </div>
                    <div id="serviceDestBlock" style="display:none">
                        <label class="fw-bold small text-muted text-uppercase mb-1">Service de destination</label>
                        <select name="service_id" class="form-select rounded-3">
                            <option value="">-- Sélectionner --</option>
                            <option value="1">Médecine Générale</option>
                            <option value="2">Chirurgie</option>
                            <option value="4">Maternité</option>
                            <option value="5">Pédiatrie</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary rounded-pill" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-dark rounded-pill px-4"><i class="bi bi-check2 me-1"></i>Confirmer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="<?= BASE_URL ?>public/js/chart.umd.min.js"></script>
<script>
// ── Horloge ──
(function() {
    const el = document.getElementById('cockpit-clock');
    function tick() { if(el) el.textContent = new Date().toLocaleTimeString('fr-FR'); }
    tick(); setInterval(tick, 1000);
})();

// ── A : Minuteries d'attente + alertes progressives ──────────────────────────
(function() {
    function updateTimers() {
        const nowMs = Date.now();
        document.querySelectorAll('.flip-card[data-ts-arrivee]').forEach(card => {
            const ts = parseInt(card.dataset.tsArrivee || 0);
            if (!ts) return;
            const diffMin = Math.floor((nowMs / 1000 - ts) / 60);
            const timerEl = card.querySelector('.wait-timer');
            if (!timerEl) return;

            let h = Math.floor(diffMin / 60), m = diffMin % 60;
            const label = h > 0 ? `${h}h${String(m).padStart(2,'0')}` : `${m}min`;

            // Couleur progressive
            let bg, color, anim = '';
            if (diffMin < 30)       { bg='#d1fae5'; color='#065f46'; }
            else if (diffMin < 60)  { bg='#fef3c7'; color='#92400e'; }
            else if (diffMin < 120) { bg='#fee2e2'; color='#991b1b'; }
            else                    { bg='#dc2626'; color='#fff'; anim='timer-blink'; }

            timerEl.textContent = label;
            timerEl.style.background = bg;
            timerEl.style.color = color;
            timerEl.className = 'wait-timer ' + anim;

            // Bordure de la carte selon ancienneté
            const inner = card.querySelector('.card-front');
            if (inner) {
                if (diffMin >= 120) inner.style.boxShadow = '0 0 0 2px #dc2626, 0 0 12px #dc262640';
                else if (diffMin >= 60) inner.style.boxShadow = '0 0 0 2px #f59e0b, 0 0 10px #f59e0b30';
                else inner.style.boxShadow = '';
            }
        });
    }
    updateTimers();
    setInterval(updateTimers, 30000); // toutes les 30s
})();

// ── Sparklines ──
document.addEventListener('DOMContentLoaded', function() {
    <?php foreach($admissions as $adm): ?>
    renderSparkline('hr-<?= $adm['id'] ?>', <?= json_encode(array_values(array_filter(array_column($adm['vitals_history']??[], 'pouls')))) ?>, '#3b82f6');
    renderSparkline('bp-<?= $adm['id'] ?>', <?= json_encode(array_values(array_filter(array_column($adm['vitals_history']??[], 'tension_sys')))) ?>, '#ef4444');
    <?php endforeach; ?>
});

function renderSparkline(id, data, color) {
    const ctx = document.getElementById(id);
    if(!ctx || !data || !data.length) return;
    new Chart(ctx, {
        type: 'line',
        data: { labels: data.map((_,i)=>i), datasets: [{ data, borderColor: color, borderWidth: 2, pointRadius: 0, fill: true, backgroundColor: color+'15', tension: 0.4 }] },
        options: { responsive:true, maintainAspectRatio:false, plugins:{legend:{display:false}}, scales:{x:{display:false},y:{display:false}} }
    });
}

// ── Alerte onglet P1 ──
<?php if($stats['P1']>0): ?>
document.querySelector('[data-bs-target="#tabUrgences"]').style.color='#ef4444';
<?php endif; ?>

// ── Modale orientation ──
function openOrientation(admId, patId) {
    document.getElementById('orientAdmissionId').value = admId;
    document.getElementById('orientPatientId').value = patId;
    document.querySelectorAll('.orient-choice').forEach(l => l.classList.remove('border-primary','bg-primary-subtle'));
    document.getElementById('serviceDestBlock').style.display = 'none';
    new bootstrap.Modal(document.getElementById('modalOrienter')).show();
}
document.querySelectorAll('.orient-choice input').forEach(r => {
    r.closest('label').addEventListener('click', function() {
        document.querySelectorAll('.orient-choice').forEach(l => l.classList.remove('border-primary','bg-primary-subtle'));
        this.classList.add('border-primary','bg-primary-subtle');
        document.getElementById('serviceDestBlock').style.display = r.value==='HOSPITALISATION' ? 'block' : 'none';
    });
});

// ── Modale assignation lit ──
function openAssignBed(patientId, patientName) {
    document.getElementById('assignPatientId').value = patientId;
    document.getElementById('assignPatientName').textContent = '👤 ' + patientName;
    document.getElementById('assignMotif').value = '';
    document.querySelectorAll('[name="lit_radio"]').forEach(r => r.checked = false);
    document.querySelectorAll('.lit-select-item').forEach(i => i.classList.remove('selected'));
    document.getElementById('litSearch').value = '';
    filterLits('');
    new bootstrap.Modal(document.getElementById('modalAssignBed')).show();
}
document.getElementById('litSearch').addEventListener('input', function() { filterLits(this.value.toLowerCase()); });
function filterLits(q) {
    document.querySelectorAll('.lit-select-item').forEach(item => {
        item.style.display = (!q || item.dataset.search.includes(q)) ? '' : 'none';
    });
}
document.querySelectorAll('.lit-select-item').forEach(item => {
    item.addEventListener('click', function() {
        document.querySelectorAll('.lit-select-item').forEach(i => i.classList.remove('selected'));
        this.classList.add('selected');
        this.querySelector('input[type="radio"]').checked = true;
    });
});

function validerInstallation() {
    const patientId = document.getElementById('assignPatientId').value;
    const motif = document.getElementById('assignMotif').value;
    const litRadio = document.querySelector('[name="lit_radio"]:checked');
    if(!litRadio) { alert('Veuillez sélectionner un lit disponible.'); return; }

    const btn = document.getElementById('btnValiderInstall');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Traitement...';

    const fd = new FormData();
    fd.append('patient_id', patientId);
    fd.append('lit_id', litRadio.value);
    fd.append('motif', motif || 'Hospitalisation validée par infirmier urgences');

    fetch('<?= BASE_URL ?>urgences/valider-hospitalisation', { method:'POST', body:fd })
        .then(r => r.json())
        .then(data => {
            if(data.success) {
                bootstrap.Modal.getInstance(document.getElementById('modalAssignBed')).hide();
                showToast('✅ Patient installé avec succès !', 'success');
                setTimeout(() => window._cockpitReload ? window._cockpitReload() : location.reload(), 1800);
            } else {
                showToast('❌ ' + (data.message || 'Erreur'), 'danger');
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-check-circle-fill me-1"></i> Valider l\'installation';
            }
        });
}

function showToast(msg, type) {
    const t = document.createElement('div');
    t.className = `position-fixed bottom-0 end-0 m-4 alert alert-${type} shadow-lg rounded-3 px-4 py-3`;
    t.style.cssText = 'z-index:9999;min-width:260px;animation:slideIn .3s ease';
    t.innerHTML = msg;
    document.body.appendChild(t);
    setTimeout(() => t.remove(), 3500);
}

// ── Libérer le lit ──
function confirmerLiberationLit(hospId, patientNom, litLabel) {
    document.getElementById('liberHospId').value = hospId;
    document.getElementById('liberPatientNom').textContent = patientNom;
    document.getElementById('liberLitLabel').textContent = litLabel;
    new bootstrap.Modal(document.getElementById('modalLiberLit')).show();
}

function libererLit() {
    const hospId = document.getElementById('liberHospId').value;
    const btn = document.getElementById('btnConfirmerLiberer');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Libération...';

    const fd = new FormData();
    fd.append('hosp_id', hospId);

    fetch('<?= BASE_URL ?>urgences/liberer-lit', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            bootstrap.Modal.getInstance(document.getElementById('modalLiberLit')).hide();
            if (data.success) {
                showToast('🛏️ Lit libéré avec succès !', 'success');
                setTimeout(() => window._cockpitReload ? window._cockpitReload() : location.reload(), 1600);
            } else {
                showToast('❌ ' + (data.message || 'Erreur'), 'danger');
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-door-open-fill me-1"></i>Confirmer la libération';
            }
        });
}

// ── Modal Transfert lit ──
function ouvrirTransfert(patientId, patientNom, currentLit) {
    document.getElementById('transfertPatientId').value  = patientId;
    document.getElementById('transfertPatientNom').textContent = '👤 ' + patientNom;
    document.getElementById('transfertLitActuel').textContent  = 'Lit actuel : ' + currentLit;
    document.getElementById('transfertMotif').value      = '';
    document.getElementById('transfertLitSearch').value  = '';
    document.getElementById('transfertServiceSelect').value = '';
    // Décocher tout
    document.querySelectorAll('[name="transfert_lit_radio"]').forEach(r => r.checked = false);
    document.querySelectorAll('.transfert-lit-item').forEach(i => i.classList.remove('selected'));
    filtrerLitsTransfert();
    new bootstrap.Modal(document.getElementById('modalTransfererLit')).show();
}

function filtrerLitsTransfert() {
    const serviceEl = document.getElementById('transfertServiceSelect');
    const searchEl  = document.getElementById('transfertLitSearch');
    if (!serviceEl || !searchEl) return; // éléments absents (modal non rendue)
    const serviceId = serviceEl.value;
    const search    = searchEl.value.toLowerCase();
    let visible = 0;
    document.querySelectorAll('.transfert-lit-item').forEach(item => {
        const matchSrv = !serviceId || item.dataset.serviceId === serviceId;
        const matchTxt = !search  || item.dataset.search.includes(search);
        const show = matchSrv && matchTxt;
        item.style.display = show ? '' : 'none';
        if (show) visible++;
    });
    const badge = document.getElementById('transfertNbLits');
    if (badge) badge.textContent = visible + ' lit' + (visible > 1 ? 's' : '');
}

function filtrerLitsTransfertSearch(val) {
    filtrerLitsTransfert();
}

// Sélection visuelle des lits du modal transfert
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.transfert-lit-item').forEach(item => {
        item.addEventListener('click', function() {
            document.querySelectorAll('.transfert-lit-item').forEach(i => i.classList.remove('selected'));
            this.classList.add('selected');
            this.querySelector('input[type="radio"]').checked = true;
        });
    });
    // Init compteur
    filtrerLitsTransfert();
});

function validerTransfert() {
    const patientId = document.getElementById('transfertPatientId').value;
    const serviceId = document.getElementById('transfertServiceSelect').value;
    const litRadio  = document.querySelector('[name="transfert_lit_radio"]:checked');
    const motif     = document.getElementById('transfertMotif').value.trim() || 'Transfert inter-lits';

    if (!litRadio) {
        showToast('⚠️ Veuillez sélectionner un lit de destination.', 'warning');
        return;
    }

    const btn = document.getElementById('btnValiderTransfert');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Transfert en cours...';

    const fd = new FormData();
    fd.append('patient_id', patientId);
    fd.append('service_id', serviceId || litRadio.closest('.transfert-lit-item').dataset.serviceId);
    fd.append('lit_id',     litRadio.value);
    fd.append('motif',      motif);

    fetch('<?= BASE_URL ?>urgences/transferer-lit', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                bootstrap.Modal.getInstance(document.getElementById('modalTransfererLit')).hide();
                showToast('✅ Transfert effectué avec succès !', 'success');
                setTimeout(() => window._cockpitReload ? window._cockpitReload() : location.reload(), 1800);
            } else {
                showToast('❌ ' + (data.message || 'Erreur lors du transfert'), 'danger');
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-check-circle-fill me-1"></i> Confirmer le transfert';
            }
        })
        .catch(() => {
            showToast('❌ Erreur réseau', 'danger');
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-check-circle-fill me-1"></i> Confirmer le transfert';
        });
}

// ── Modal Transfert 2 étapes (Urgences) ──
let _urgPatId = null, _urgHospId = null, _urgServiceOrigId = null;

function urgOuvrirTransfert(patientId, hospId, nom, serviceOrigId, litLabel) {
    _urgPatId         = patientId;
    _urgHospId        = hospId;
    _urgServiceOrigId = serviceOrigId;

    document.getElementById('urgTransfertNomPatient').textContent = nom;
    document.getElementById('urgTransfertLitBadge').textContent   = litLabel;
    document.getElementById('urgTransfertSousTitre').textContent  = 'Choisissez le type de transfert';

    // Masquer le service courant dans la liste externe
    document.querySelectorAll('#urgExterneServiceId option').forEach(opt => {
        if (opt.value === '') return;
        opt.style.display = (parseInt(opt.value) === serviceOrigId) ? 'none' : '';
    });

    // Réinitialiser les champs
    const selLit = document.getElementById('urgInterneNouveauLit');
    selLit.innerHTML = '<option value="">Chargement…</option>';
    selLit.disabled = true;
    document.getElementById('urgInterneMotif').value    = '';
    document.getElementById('urgExterneServiceId').value = '';
    document.getElementById('urgExterneMotif').value    = '';
    _urgResetBtn('urgBtnConfirmerInterne', '<i class="bi bi-check2 me-1"></i> Confirmer le changement de lit');
    _urgResetBtn('urgBtnConfirmerExterne', '<i class="bi bi-check2 me-1"></i> Confirmer le transfert');

    _urgShowStep('step1');
    new bootstrap.Modal(document.getElementById('modalTransfererLit')).show();
}

function urgRetourStep1() {
    document.getElementById('urgTransfertSousTitre').textContent = 'Choisissez le type de transfert';
    _urgShowStep('step1');
}

function urgChoisirInterne() {
    document.getElementById('urgTransfertSousTitre').textContent = 'Transfert interne — Changer de lit';
    _urgShowStep('interne');

    const sel    = document.getElementById('urgInterneNouveauLit');
    const loader = document.getElementById('urgInterneLitsLoader');
    sel.innerHTML = '<option value="">Chargement…</option>';
    sel.disabled  = true;
    loader.classList.remove('d-none');

    fetch(`<?= BASE_URL ?>hospitalisation/lits-disponibles?service_id=${_urgServiceOrigId}`, {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => r.json())
    .then(data => {
        loader.classList.add('d-none');
        const lits = Array.isArray(data) ? data : [];
        if (lits.length === 0) {
            sel.innerHTML = '<option value="">Aucun lit disponible dans ce service</option>';
        } else {
            sel.innerHTML = '<option value="">-- Choisir un lit --</option>';
            lits.forEach(l => {
                const opt = document.createElement('option');
                opt.value = l.id;
                opt.textContent = (l.nom_chambre ? l.nom_chambre + ' — ' : '') + l.nom_lit;
                sel.appendChild(opt);
            });
            sel.disabled = false;
        }
    })
    .catch(() => {
        loader.classList.add('d-none');
        sel.innerHTML = '<option value="">Erreur de chargement</option>';
    });
}

function urgChoisirExterne() {
    document.getElementById('urgTransfertSousTitre').textContent = 'Transfert externe — Autre service';
    _urgShowStep('externe');
}

function urgConfirmerInterne() {
    const litId = document.getElementById('urgInterneNouveauLit').value;
    const motif = document.getElementById('urgInterneMotif').value.trim() || 'Changement de lit intra-service';
    if (!litId) {
        showToast('⚠️ Veuillez sélectionner un lit de destination.', 'warning');
        return;
    }
    _urgResetBtn('urgBtnConfirmerInterne', '<span class="spinner-border spinner-border-sm me-1"></span>Transfert…', true);

    const fd = new FormData();
    fd.append('hosp_id',       _urgHospId);
    fd.append('nouveau_lit_id', litId);
    fd.append('motif',          motif);

    fetch('<?= BASE_URL ?>hospitalisation/changer-lit', {
        method: 'POST', body: fd,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            bootstrap.Modal.getInstance(document.getElementById('modalTransfererLit')).hide();
            showToast('✅ Changement de lit effectué !', 'success');
            setTimeout(() => window._cockpitReload ? window._cockpitReload() : location.reload(), 1800);
        } else {
            showToast('❌ ' + (data.message || 'Erreur'), 'danger');
            _urgResetBtn('urgBtnConfirmerInterne', '<i class="bi bi-check2 me-1"></i> Confirmer le changement de lit');
        }
    })
    .catch(() => {
        showToast('❌ Erreur réseau', 'danger');
        _urgResetBtn('urgBtnConfirmerInterne', '<i class="bi bi-check2 me-1"></i> Confirmer le changement de lit');
    });
}

function urgConfirmerExterne() {
    const serviceId = document.getElementById('urgExterneServiceId').value;
    const motif     = document.getElementById('urgExterneMotif').value.trim() || 'Transfert vers autre service';
    if (!serviceId) {
        showToast('⚠️ Veuillez sélectionner un service de destination.', 'warning');
        return;
    }
    _urgResetBtn('urgBtnConfirmerExterne', '<span class="spinner-border spinner-border-sm me-1"></span>Transfert…', true);

    const fd = new FormData();
    fd.append('service_id', serviceId);
    fd.append('motif',      motif);

    fetch(`<?= BASE_URL ?>hospitalisation/transferer/${_urgPatId}`, {
        method: 'POST', body: fd,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            bootstrap.Modal.getInstance(document.getElementById('modalTransfererLit')).hide();
            showToast('✅ Patient transféré avec succès !', 'success');
            setTimeout(() => window._cockpitReload ? window._cockpitReload() : location.reload(), 1800);
        } else {
            showToast('❌ ' + (data.message || 'Erreur'), 'danger');
            _urgResetBtn('urgBtnConfirmerExterne', '<i class="bi bi-check2 me-1"></i> Confirmer le transfert');
        }
    })
    .catch(() => {
        showToast('❌ Erreur réseau', 'danger');
        _urgResetBtn('urgBtnConfirmerExterne', '<i class="bi bi-check2 me-1"></i> Confirmer le transfert');
    });
}

function _urgShowStep(step) {
    const map = {
        step1:   'urgTransfertStep1',
        interne: 'urgTransfertStepInterne',
        externe: 'urgTransfertStepExterne'
    };
    Object.entries(map).forEach(([k, id]) => {
        const el = document.getElementById(id);
        if (el) el.classList.toggle('d-none', k !== step);
    });
}

function _urgResetBtn(id, html, disable = false) {
    const btn = document.getElementById(id);
    if (!btn) return;
    btn.disabled  = disable;
    btn.innerHTML = html;
}

// ── Persistance de l'onglet actif après location.reload() ──
(function() {
    const STORAGE_KEY = 'urgCockpitActiveTab';

    // Restaurer l'onglet au chargement
    const savedTab = sessionStorage.getItem(STORAGE_KEY);
    if (savedTab) {
        sessionStorage.removeItem(STORAGE_KEY);
        const tabBtn = document.querySelector(`[data-bs-target="${savedTab}"]`);
        if (tabBtn) {
            // Désactiver l'onglet "Urgences" par défaut et activer le bon
            document.querySelectorAll('#cockpitTabs .nav-link').forEach(b => b.classList.remove('active'));
            document.querySelectorAll('.tab-pane').forEach(p => { p.classList.remove('show','active'); });
            tabBtn.classList.add('active');
            const pane = document.querySelector(savedTab);
            if (pane) pane.classList.add('show','active');
        }
    } else {
        // Par défaut : activer le premier onglet
        document.getElementById('tabBtnUrgences')?.classList.add('active');
        document.getElementById('tabUrgences')?.classList.add('show','active');
    }

    // Sauvegarder l'onglet courant avant tout reload
    function saveTabAndReload() {
        const active = document.querySelector('#cockpitTabs .nav-link.active');
        if (active) sessionStorage.setItem(STORAGE_KEY, active.getAttribute('data-bs-target'));
        location.reload();
    }
    // Exposer globalement pour les setTimeout existants
    window._cockpitReload = saveTabAndReload;
})();

// ── Actualisation intelligente (3 min, pause si saisie active) ──
(function() {
    const INTERVAL = 3 * 60 * 1000;
    let timer = null;

    function anyModalOpen() {
        return document.querySelectorAll('.modal.show').length > 0;
    }

    function anyInputActive() {
        const active = document.activeElement;
        return active && (active.tagName === 'INPUT' || active.tagName === 'TEXTAREA' || active.tagName === 'SELECT');
    }

    function scheduleRefresh() {
        clearTimeout(timer);
        timer = setTimeout(() => {
            if (anyModalOpen() || anyInputActive()) {
                timer = setTimeout(scheduleRefresh, 30000);
                return;
            }
            window._cockpitReload ? window._cockpitReload() : location.reload();
        }, INTERVAL);
    }

    ['keydown','mousedown','touchstart'].forEach(ev =>
        document.addEventListener(ev, () => scheduleRefresh(), { passive: true })
    );

    scheduleRefresh();
})();
</script>

<!-- ══ MODAL TRANSFERT 2 ÉTAPES ══ -->
<div class="modal fade" id="modalTransfererLit" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4" style="overflow:hidden">

            <div class="modal-header border-0 px-4 pt-4 pb-2"
                 style="background:linear-gradient(135deg,#f59e0b,#d97706)">
                <div>
                    <h5 class="fw-bold mb-0 text-white">
                        <i class="bi bi-arrow-left-right me-2"></i>Transfert de patient
                    </h5>
                    <small class="text-white opacity-75" id="urgTransfertSousTitre">Choisissez le type de transfert</small>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body px-4 pb-4">

                <!-- Bandeau patient -->
                <div class="alert alert-warning border-0 rounded-3 small mb-3 py-2 d-flex align-items-center gap-2">
                    <i class="bi bi-person-fill fs-5"></i>
                    <strong id="urgTransfertNomPatient">—</strong>
                    <span id="urgTransfertLitBadge" class="ms-auto badge bg-secondary"></span>
                </div>

                <!-- ── ÉTAPE 1 ── -->
                <div id="urgTransfertStep1">
                    <p class="text-muted small mb-3 fw-semibold">
                        <i class="bi bi-signpost-2 me-1"></i>Sélectionnez le type de transfert :
                    </p>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="card border-2 border-primary rounded-4 p-4 text-center h-100"
                                 role="button" style="cursor:pointer;transition:all .15s"
                                 onmouseover="this.style.background='#eff6ff'" onmouseout="this.style.background=''"
                                 onclick="urgChoisirInterne()">
                                <div class="mb-2" style="font-size:2.2rem">🛏️</div>
                                <h6 class="fw-bold text-primary mb-1">Transfert interne</h6>
                                <p class="text-muted small mb-0">Changer de lit dans le <strong>même service</strong></p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card border-2 border-danger rounded-4 p-4 text-center h-100"
                                 role="button" style="cursor:pointer;transition:all .15s"
                                 onmouseover="this.style.background='#fff5f5'" onmouseout="this.style.background=''"
                                 onclick="urgChoisirExterne()">
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
                <div id="urgTransfertStepInterne" class="d-none">
                    <div class="alert alert-primary border-0 rounded-3 small py-2 mb-3">
                        <i class="bi bi-info-circle me-1"></i>
                        Le patient reste dans le <strong>même service</strong>, seul son lit change.
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-uppercase text-muted">
                            Nouveau lit <span class="text-danger">*</span>
                        </label>
                        <select id="urgInterneNouveauLit" class="form-select border-2 rounded-3" disabled>
                            <option value="">Chargement…</option>
                        </select>
                        <div id="urgInterneLitsLoader" class="form-text text-muted d-none">
                            <span class="spinner-border spinner-border-sm me-1"></span> Chargement…
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-uppercase text-muted">Motif</label>
                        <textarea id="urgInterneMotif" class="form-control border-2 rounded-3" rows="2"
                                  placeholder="Raison du changement de lit (optionnel)…"></textarea>
                    </div>
                    <div class="d-flex gap-2 mt-4">
                        <button type="button" class="btn btn-light rounded-pill px-3" onclick="urgRetourStep1()">
                            <i class="bi bi-arrow-left me-1"></i> Retour
                        </button>
                        <button type="button" id="urgBtnConfirmerInterne"
                                class="btn btn-primary rounded-pill px-4 flex-grow-1 fw-bold"
                                onclick="urgConfirmerInterne()">
                            <i class="bi bi-check2 me-1"></i> Confirmer le changement de lit
                        </button>
                    </div>
                </div>

                <!-- ── ÉTAPE 2B : Externe ── -->
                <div id="urgTransfertStepExterne" class="d-none">
                    <div class="alert alert-info border-0 rounded-3 small py-2 mb-3">
                        <i class="bi bi-info-circle me-1"></i>
                        L'infirmière du service de destination se chargera d'assigner un lit.
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-uppercase text-muted">
                            Service de destination <span class="text-danger">*</span>
                        </label>
                        <select id="urgExterneServiceId" class="form-select border-2 rounded-3">
                            <option value="">-- Choisir un service --</option>
                            <?php foreach(($servicesCliniques ?? []) as $sc): ?>
                            <option value="<?= (int)$sc['id'] ?>"><?= htmlspecialchars($sc['nom_service']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-uppercase text-muted">Motif du transfert</label>
                        <textarea id="urgExterneMotif" class="form-control border-2 rounded-3" rows="2"
                                  placeholder="Raison du transfert (optionnel)…"></textarea>
                    </div>
                    <div class="d-flex gap-2 mt-4">
                        <button type="button" class="btn btn-light rounded-pill px-3" onclick="urgRetourStep1()">
                            <i class="bi bi-arrow-left me-1"></i> Retour
                        </button>
                        <button type="button" id="urgBtnConfirmerExterne"
                                class="btn btn-danger rounded-pill px-4 flex-grow-1 fw-bold"
                                onclick="urgConfirmerExterne()">
                            <i class="bi bi-check2 me-1"></i> Confirmer le transfert
                        </button>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<!-- ══ MODAL LIBÉRER LE LIT ══ -->
<div class="modal fade" id="modalLiberLit" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width:440px">
        <div class="modal-content border-0 shadow-lg" style="border-radius:18px;overflow:hidden">
            <div class="modal-header border-0 pb-0 pt-4 px-4">
                <div class="d-flex align-items-center gap-3 w-100">
                    <div style="width:50px;height:50px;background:#fff1f2;border-radius:14px;display:flex;align-items:center;justify-content:center;flex-shrink:0">
                        <i class="bi bi-door-open-fill text-danger fs-4"></i>
                    </div>
                    <div>
                        <h5 class="fw-bold mb-0 text-dark">Libérer le lit</h5>
                        <small class="text-muted">Cette action est irréversible</small>
                    </div>
                    <button type="button" class="btn-close ms-auto" data-bs-dismiss="modal"></button>
                </div>
            </div>
            <div class="modal-body px-4 pt-3 pb-2">
                <input type="hidden" id="liberHospId">
                <div class="alert alert-warning rounded-3 py-2 px-3 mb-3" style="font-size:.85rem">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    Vous allez libérer le lit de <strong id="liberPatientNom"></strong>
                </div>
                <div class="d-flex align-items-center gap-2 p-3 bg-light rounded-3" style="font-size:.85rem">
                    <i class="bi bi-bed-fill text-secondary fs-5"></i>
                    <div>
                        <div class="text-muted" style="font-size:.7rem;text-transform:uppercase;font-weight:700">Lit concerné</div>
                        <strong id="liberLitLabel" class="text-dark"></strong>
                    </div>
                </div>
                <p class="text-muted mt-3 mb-0" style="font-size:.82rem">
                    <i class="bi bi-info-circle me-1"></i>
                    L'hospitalisation sera clôturée, le patient passera en statut "Sorti" et le lit sera immédiatement remis à disposition.
                </p>
            </div>
            <div class="modal-footer border-0 px-4 pb-4 pt-2 d-flex gap-2">
                <button type="button" class="btn btn-outline-secondary rounded-pill flex-grow-1" data-bs-dismiss="modal">Annuler</button>
                <button type="button" id="btnConfirmerLiberer" class="btn btn-danger rounded-pill flex-grow-1 fw-bold shadow-sm" onclick="libererLit()">
                    <i class="bi bi-door-open-fill me-1"></i>Confirmer la libération
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ══════════════════════════════════════════════════════════
     MODAL — RECHERCHER UN PATIENT POUR CONSULTATION INFIRMIÈRE
══════════════════════════════════════════════════════════ -->
<div class="modal fade" id="modalChercherPatientConsult" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header border-0 pb-0" style="background:linear-gradient(135deg,#0f172a,#0d9488);">
                <div class="d-flex align-items-center gap-3 w-100 py-2">
                    <div style="width:46px;height:46px;background:rgba(255,255,255,.15);border-radius:12px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <i class="bi bi-stethoscope text-white fs-4"></i>
                    </div>
                    <div>
                        <h5 class="fw-bold mb-0 text-white">Consultation Infirmière</h5>
                        <small style="color:rgba(255,255,255,.65);">Rechercher le patient à consulter</small>
                    </div>
                    <button type="button" class="btn-close btn-close-white ms-auto" data-bs-dismiss="modal"></button>
                </div>
            </div>
            <div class="modal-body p-4">
                <!-- Champ de recherche -->
                <div class="input-group mb-3 shadow-sm" style="border-radius:12px;overflow:hidden;">
                    <span class="input-group-text bg-white border-end-0" style="border-radius:12px 0 0 12px;">
                        <i class="bi bi-search text-muted"></i>
                    </span>
                    <input type="text" id="consultSearchInput"
                           class="form-control border-start-0 ps-0"
                           placeholder="Nom, prénom ou numéro de dossier…"
                           autocomplete="off"
                           style="border-radius:0 12px 12px 0;font-size:.9rem;"
                           oninput="rechercherPatientConsult(this.value)">
                </div>

                <!-- Résultats -->
                <div id="consultSearchResults" style="max-height:340px;overflow-y:auto;">
                    <div class="text-center text-muted py-4" style="font-size:.85rem;">
                        <i class="bi bi-person-search d-block fs-2 mb-2 opacity-40"></i>
                        Tapez au moins 2 caractères pour rechercher
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.consult-result-item {
    display: flex; align-items: center; gap: 12px;
    padding: 10px 12px; border-radius: 10px;
    border: 1px solid #e2e8f0; margin-bottom: 7px;
    cursor: pointer; transition: all .15s;
    text-decoration: none; color: inherit;
}
.consult-result-item:hover {
    border-color: #0d9488; background: #f0fdfa;
    transform: translateY(-1px); box-shadow: 0 4px 14px rgba(13,148,136,.12);
    text-decoration: none; color: inherit;
}
.consult-avatar-mini {
    width: 40px; height: 40px; border-radius: 50%; flex-shrink: 0;
    background: linear-gradient(135deg, #0d9488, #0f766e);
    color: #fff; display: flex; align-items: center;
    justify-content: center; font-weight: 800; font-size: .85rem;
}
.consult-result-name  { font-weight: 700; font-size: .88rem; color: #1e293b; line-height: 1.2; }
.consult-result-meta  { font-size: .72rem; color: #64748b; }
.consult-result-dossier { font-size: .7rem; background: #f1f5f9; color: #475569; padding: 1px 7px; border-radius: 6px; font-weight: 600; }
</style>

<script>
(function() {
    let _consultTimer = null;

    window.rechercherPatientConsult = function(q) {
        clearTimeout(_consultTimer);
        const box = document.getElementById('consultSearchResults');

        if (q.trim().length < 2) {
            box.innerHTML = '<div class="text-center text-muted py-4" style="font-size:.85rem;"><i class="bi bi-person-search d-block fs-2 mb-2 opacity-40"></i>Tapez au moins 2 caractères pour rechercher</div>';
            return;
        }

        box.innerHTML = '<div class="text-center py-3"><div class="spinner-border spinner-border-sm text-teal" style="color:#0d9488"></div> Recherche…</div>';

        _consultTimer = setTimeout(function() {
            fetch('<?= BASE_URL ?>patients/recherche?q=' + encodeURIComponent(q.trim()))
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    const patients = Array.isArray(data) ? data : (data && Array.isArray(data.patients) ? data.patients : []);
                    if (patients.length === 0) {
                        box.innerHTML = '<div class="text-center text-muted py-4" style="font-size:.85rem;"><i class="bi bi-person-x d-block fs-2 mb-2 opacity-40"></i>Aucun patient trouvé</div>';
                        return;
                    }
                    let html = '';
                    patients.forEach(function(p) {
                        const initiales = ((p.nom||'').charAt(0) + (p.prenom||'').charAt(0)).toUpperCase();
                        const url = '<?= BASE_URL ?>infirmier/consultation/' + p.id;
                        html += '<a href="' + url + '" class="consult-result-item">' +
                            '<div class="consult-avatar-mini">' + initiales + '</div>' +
                            '<div class="flex-grow-1 min-width-0">' +
                                '<div class="consult-result-name">' + (p.prenom||'') + ' ' + (p.nom||'').toUpperCase() + '</div>' +
                                '<div class="consult-result-meta">' +
                                    (p.date_naissance ? calculerAge(p.date_naissance) + ' ans • ' : '') +
                                    (p.sexe === 'M' ? 'Masculin' : (p.sexe === 'F' ? 'Féminin' : '')) +
                                '</div>' +
                            '</div>' +
                            '<span class="consult-result-dossier">' + (p.dossier_numero || '#'+p.id) + '</span>' +
                            '<i class="bi bi-arrow-right-circle-fill text-muted" style="opacity:.4"></i>' +
                        '</a>';
                    });
                    box.innerHTML = html;
                })
                .catch(function() {
                    box.innerHTML = '<div class="text-center text-danger py-3" style="font-size:.85rem;"><i class="bi bi-wifi-off me-2"></i>Erreur réseau, veuillez réessayer</div>';
                });
        }, 320);
    };

    function calculerAge(dateNaissance) {
        const naissance = new Date(dateNaissance);
        const maintenant = new Date();
        let age = maintenant.getFullYear() - naissance.getFullYear();
        const m = maintenant.getMonth() - naissance.getMonth();
        if (m < 0 || (m === 0 && maintenant.getDate() < naissance.getDate())) age--;
        return age;
    }

    // Vider la recherche à chaque ouverture du modal
    document.addEventListener('DOMContentLoaded', function() {
        const modal = document.getElementById('modalChercherPatientConsult');
        if (modal) {
            modal.addEventListener('shown.bs.modal', function() {
                const inp = document.getElementById('consultSearchInput');
                if (inp) { inp.value = ''; inp.focus(); }
                const box = document.getElementById('consultSearchResults');
                if (box) box.innerHTML = '<div class="text-center text-muted py-4" style="font-size:.85rem;"><i class="bi bi-person-search d-block fs-2 mb-2 opacity-40"></i>Tapez au moins 2 caractères pour rechercher</div>';
            });
        }
    });
})();
</script>

<!-- ══ MODAL HOSPITALISATION DEPUIS CONSULTATION INFIRMIÈRE ══ -->
<div class="modal fade" id="modalMcHospitaliser" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4" style="overflow:hidden">
            <div class="modal-header border-0 px-4 pt-4 pb-2"
                 style="background:linear-gradient(135deg,#059669,#10b981)">
                <div>
                    <h5 class="fw-bold mb-0 text-white">
                        <i class="bi bi-hospital me-2"></i>Hospitaliser le patient
                    </h5>
                    <small class="text-white opacity-80" id="mcHospNomPatient"></small>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body px-4 pb-4 pt-3">
                <input type="hidden" id="mcHospPatientId" value="">
                <input type="hidden" id="mcHospConsultId" value="">

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-uppercase text-muted">
                            Service <span class="text-danger">*</span>
                        </label>
                        <select id="mcHospServiceId" class="form-select border-2 rounded-3" onchange="mcChargerLits()">
                            <option value="">— Choisir un service —</option>
                            <?php foreach(($servicesCliniques ?? []) as $sc): ?>
                            <option value="<?= (int)$sc['id'] ?>"><?= htmlspecialchars($sc['nom_service']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-uppercase text-muted">
                            Lit <span class="text-danger">*</span>
                        </label>
                        <select id="mcHospLitId" class="form-select border-2 rounded-3" disabled>
                            <option value="">— Sélectionner un service d'abord —</option>
                        </select>
                        <div id="mcLitsLoader" class="form-text text-muted d-none">
                            <span class="spinner-border spinner-border-sm me-1"></span> Chargement des lits…
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-uppercase text-muted">Médecin responsable</label>
                        <select id="mcHospMedecinId" class="form-select border-2 rounded-3">
                            <option value="">— Optionnel —</option>
                            <?php foreach(($medecins ?? []) as $med): ?>
                            <option value="<?= (int)$med['id'] ?>"><?= htmlspecialchars($med['prenom'].' '.$med['nom']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-uppercase text-muted">Motif d'hospitalisation</label>
                        <input type="text" id="mcHospMotif" class="form-control border-2 rounded-3"
                               placeholder="Motif (optionnel)…">
                    </div>
                </div>

                <div id="mcHospAlert" class="alert d-none mt-3 mb-0 small rounded-3"></div>
            </div>
            <div class="modal-footer border-0 px-4 pb-4 pt-0 gap-2">
                <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Annuler</button>
                <button type="button" id="mcHospBtnConfirmer"
                        class="btn btn-success rounded-pill px-5 fw-bold"
                        onclick="mcConfirmerHospitalisation()">
                    <i class="bi bi-check2-circle me-1"></i> Confirmer l'hospitalisation
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// ── Mes Consultations : modal hospitalisation ────────────────────────────────
function mcOuvrirModalHosp(patientId, consultId, nomPatient) {
    document.getElementById('mcHospPatientId').value = patientId;
    document.getElementById('mcHospConsultId').value = consultId;
    document.getElementById('mcHospNomPatient').textContent = nomPatient;
    document.getElementById('mcHospServiceId').value = '';
    document.getElementById('mcHospLitId').innerHTML = '<option value="">— Sélectionner un service d\'abord —</option>';
    document.getElementById('mcHospLitId').disabled = true;
    document.getElementById('mcHospMedecinId').value = '';
    document.getElementById('mcHospMotif').value = '';
    const al = document.getElementById('mcHospAlert');
    al.className = 'alert d-none mt-3 mb-0 small rounded-3';
    al.textContent = '';
    new bootstrap.Modal(document.getElementById('modalMcHospitaliser')).show();
}

function mcChargerLits() {
    const serviceId = document.getElementById('mcHospServiceId').value;
    const sel       = document.getElementById('mcHospLitId');
    const loader    = document.getElementById('mcLitsLoader');
    if (!serviceId) {
        sel.innerHTML = '<option value="">— Sélectionner un service d\'abord —</option>';
        sel.disabled = true;
        return;
    }
    sel.innerHTML = '<option value="">Chargement…</option>';
    sel.disabled = true;
    loader.classList.remove('d-none');
    fetch('<?= BASE_URL ?>hospitalisation/lits-disponibles?service_id=' + serviceId)
        .then(r => r.json())
        .then(function(data) {
            loader.classList.add('d-none');
            const lits = Array.isArray(data) ? data : (data.lits || []);
            if (!lits.length) {
                sel.innerHTML = '<option value="">Aucun lit disponible dans ce service</option>';
                return;
            }
            sel.innerHTML = '<option value="">— Choisir un lit —</option>';
            lits.forEach(function(l) {
                const opt = document.createElement('option');
                opt.value = l.id;
                opt.textContent = (l.nom_chambre ? l.nom_chambre + ' / ' : '') + l.nom_lit;
                sel.appendChild(opt);
            });
            sel.disabled = false;
        })
        .catch(function() {
            loader.classList.add('d-none');
            sel.innerHTML = '<option value="">Erreur de chargement</option>';
        });
}

function mcConfirmerHospitalisation() {
    const patientId  = document.getElementById('mcHospPatientId').value;
    const consultId  = document.getElementById('mcHospConsultId').value;
    const litId      = document.getElementById('mcHospLitId').value;
    const serviceId  = document.getElementById('mcHospServiceId').value;
    const medecinId  = document.getElementById('mcHospMedecinId').value;
    const motif      = document.getElementById('mcHospMotif').value;
    const btn        = document.getElementById('mcHospBtnConfirmer');
    const al         = document.getElementById('mcHospAlert');

    if (!litId) {
        al.className = 'alert alert-warning mt-3 mb-0 small rounded-3 d-block';
        al.textContent = 'Veuillez sélectionner un lit avant de confirmer.';
        return;
    }
    al.className = 'alert d-none mt-3 mb-0 small rounded-3';
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Traitement…';

    fetch('<?= BASE_URL ?>infirmier/hospitaliser-depuis-consultation', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            consultation_id: parseInt(consultId),
            patient_id:      parseInt(patientId),
            lit_id:          parseInt(litId),
            service_id:      parseInt(serviceId),
            medecin_id:      medecinId ? parseInt(medecinId) : null,
            motif:           motif
        })
    })
    .then(r => r.json())
    .then(function(data) {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-check2-circle me-1"></i> Confirmer l\'hospitalisation';
        if (data.success) {
            bootstrap.Modal.getInstance(document.getElementById('modalMcHospitaliser'))?.hide();
            if (window._cockpitReload) window._cockpitReload(); else location.reload();
        } else {
            al.className = 'alert alert-danger mt-3 mb-0 small rounded-3 d-block';
            al.textContent = data.message || 'Une erreur est survenue.';
        }
    })
    .catch(function() {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-check2-circle me-1"></i> Confirmer l\'hospitalisation';
        al.className = 'alert alert-danger mt-3 mb-0 small rounded-3 d-block';
        al.textContent = 'Erreur réseau, veuillez réessayer.';
    });
}
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
