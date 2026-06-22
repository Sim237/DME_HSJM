<?php
$adminPage    = $adminPage ?? '';
$userName     = trim(($_SESSION['user_prenom'] ?? '') . ' ' . ($_SESSION['user_nom'] ?? ''));
$userInit     = strtoupper(substr($_SESSION['user_prenom'] ?? 'A', 0, 1) . substr($_SESSION['user_nom'] ?? '', 0, 1));
$_navRole     = strtoupper($_SESSION['user_role'] ?? '');
$_isSuperAdmin = ($_navRole === 'SUPER_ADMIN');

// Badge patients actifs (silencieux si table absente)
$nb_actifs = 0;
try {
    $db_nav = (new Database())->getConnection();
    $nb_actifs = (int)$db_nav->query("SELECT COUNT(*) FROM patients WHERE actif=1 AND statut_parcours IN ('ACCUEIL','PARAMETRES','PARAMETRES_MATERNITE','ATTENTE_CONSULTATION','EN_CONSULTATION','URGENCES')")->fetchColumn();
} catch(\Throwable $e) {}

// Groupes de navigation
$navGroups = [
    [
        'label' => 'Tableau de bord',
        'icon'  => 'bi-speedometer2',
        'pages' => ['dashboard'],
        'items' => [
            ['href' => 'dashboard',          'icon' => 'bi-house-fill',         'label' => 'Vue d\'ensemble',    'page' => 'dashboard',      'desc' => 'KPI globaux et alertes'],
            ['href' => 'utilisateurs',        'icon' => 'bi-people-fill',        'label' => 'Utilisateurs',       'page' => 'utilisateurs',   'desc' => 'Gestion des comptes'],
            ['href' => 'directeur/patients',  'icon' => 'bi-bar-chart-fill',     'label' => 'Statistiques',       'page' => 'dir_patients',   'desc' => 'Rapports et analyses'],
        ],
    ],
    [
        'label' => 'Patients',
        'icon'  => 'bi-person-vcard-fill',
        'pages' => ['patients','nouveau_patient','suivi_parcours'],
        'badge' => $nb_actifs > 0 ? $nb_actifs : null,
        'items' => [
            ['href' => 'admin/patients',        'icon' => 'bi-folder2-open',       'label' => 'Dossiers',           'page' => 'patients',         'desc' => 'Tous les dossiers patients'],
            ['href' => 'patients/nouveau',       'icon' => 'bi-person-plus-fill',   'label' => 'Nouveau patient',    'page' => 'nouveau_patient',  'desc' => 'Créer un dossier'],
            ['href' => 'admin/suivi-parcours',   'icon' => 'bi-map-fill',           'label' => 'Suivi Parcours',     'page' => 'suivi_parcours',   'desc' => 'Localisation en temps réel', 'badge' => $nb_actifs > 0 ? $nb_actifs : null],
        ],
    ],
    [
        'label' => 'Infrastructure',
        'icon'  => 'bi-building-fill',
        'pages' => ['services','lits','bloc'],
        'items' => [
            ['href' => 'admin/services', 'icon' => 'bi-building-fill',      'label' => 'Services',           'page' => 'services', 'desc' => 'Gestion des services cliniques'],
            ['href' => 'admin/lits',     'icon' => 'bi-grid-3x3-gap-fill', 'label' => 'Chambres & Lits',    'page' => 'lits',     'desc' => 'Plan d\'occupation'],
            ['href' => 'admin/bloc',     'icon' => 'bi-scissors',           'label' => 'Bloc opératoire',    'page' => 'bloc',     'desc' => 'Salles, interventions et SSPI'],
        ],
    ],
    [
        'label' => 'Gardes',
        'icon'  => 'bi-shield-lock-fill',
        'pages' => ['gardes'],
        'items' => [
            ['href' => 'admin/gardes', 'icon' => 'bi-moon-stars-fill', 'label' => 'Gardes médicales', 'page' => 'gardes', 'desc' => 'Planifier et gérer les gardes'],
        ],
    ],
    [
        'label' => 'Médico-technique',
        'icon'  => 'bi-capsule-pill',
        'pages' => ['pharmacie','laboratoire','imagerie'],
        'items' => [
            ['href' => 'admin/pharmacie',     'icon' => 'bi-capsule-pill',  'label' => 'Pharmacie & Stock', 'page' => 'pharmacie',    'desc' => 'Médicaments et inventaire'],
            ['href' => 'admin/laboratoire',   'icon' => 'bi-flask-fill',    'label' => 'Laboratoire',       'page' => 'laboratoire',  'desc' => 'Examens et résultats'],
            ['href' => 'admin/imagerie',      'icon' => 'bi-radioactive',   'label' => 'Imagerie Médicale', 'page' => 'imagerie',     'desc' => 'Catalogue et demandes d\'examens'],
        ],
    ],
    [
        'label' => 'Système',
        'icon'  => 'bi-gear-fill',
        'pages' => ['permissions','config','logs','export_sql','licences'],
        'items' => array_filter([
            ['href' => 'admin/permissions', 'icon' => 'bi-shield-fill-check', 'label' => 'Permissions',     'page' => 'permissions', 'desc' => 'Rôles et accès'],
            ['href' => 'admin/config',      'icon' => 'bi-sliders',           'label' => 'Configuration',   'page' => 'config',      'desc' => 'Paramètres système'],
            ['href' => 'admin/logs',        'icon' => 'bi-journal-code',      'label' => 'Journaux Audit',  'page' => 'logs',        'desc' => 'Traçabilité des actions'],
            // Export SQL et Licences : SUPER_ADMIN uniquement
            $_isSuperAdmin ? ['href' => 'admin/export-sql', 'icon' => 'bi-database-down', 'label' => 'Export SQL',  'page' => 'export_sql', 'desc' => 'Télécharger la base de données'] : null,
            $_isSuperAdmin ? ['href' => 'admin/licences',   'icon' => 'bi-key-fill',      'label' => 'Licences',    'page' => 'licences',   'desc' => 'Clés d\'activation'] : null,
        ]),
    ],
];
?>
<style>
/* ══════════════════════════════════════════════════════════════
   RESET SIDEBAR — le header prend la place
══════════════════════════════════════════════════════════════ */
.sidebar, .admin-sidenav { display:none !important; }
main, .main-admin, .admin-main { margin-left:0 !important; }
.admin-shell { display:block !important; }
*, *::before, *::after { box-sizing:border-box; }
body { background:#f0f2f8; font-family:'Segoe UI',system-ui,sans-serif; margin:0; padding-top:64px; }

/* ── Sous-barre de page (remplace l'ancien topbar) ── */
.admin-topbar {
    position:sticky; top:64px; z-index:900;
    background:rgba(255,255,255,.95);
    backdrop-filter:blur(10px);
    border-bottom:1px solid #e2e8f0;
    padding:0 28px;
    height:56px;
    display:flex !important;
    align-items:center;
    justify-content:space-between;
    gap:12px;
    box-shadow:0 2px 8px rgba(0,0,0,.05);
}
.admin-topbar-title {
    font-size:1rem; font-weight:800; color:#0f172a;
    display:flex; flex-direction:column; gap:1px;
}
.admin-topbar-title small {
    font-size:.68rem; font-weight:500; color:#94a3b8; display:block;
}
.adm-topbar-actions {
    display:flex; align-items:center; gap:8px; flex-wrap:wrap;
}
.adm-topbtn {
    height:34px; padding:0 14px; border-radius:9px;
    font-size:.78rem; font-weight:700; cursor:pointer;
    border:none; display:inline-flex; align-items:center; gap:6px;
    text-decoration:none; transition:all .15s; white-space:nowrap;
}
.adm-topbtn-ghost {
    background:#f1f5f9; color:#475569; border:1px solid #e2e8f0;
}
.adm-topbtn-ghost:hover { background:#e2e8f0; color:#0f172a; transform:translateY(-1px); }
.adm-topbtn-primary {
    background:linear-gradient(135deg,#3b82f6,#6366f1); color:#fff;
    box-shadow:0 3px 10px rgba(99,102,241,.3);
}
.adm-topbtn-primary:hover { transform:translateY(-1px); box-shadow:0 5px 14px rgba(99,102,241,.4); color:#fff; }
.adm-topbtn-danger {
    background:rgba(239,68,68,.1); color:#dc2626; border:1px solid #fecaca;
}
.adm-topbtn-danger:hover { background:rgba(239,68,68,.18); transform:translateY(-1px); }

/* Cacher l'horloge dans le topbar (déjà dans le header principal) */
.admin-topbar .adm-clock-pill { display:none; }

/* ══════════════════════════════════════════════════════════════
   RESPONSIVE
══════════════════════════════════════════════════════════════ */

/* Espace intermédiaire (≤ 1280px) — libère de la place pour le profil */
@media (max-width:1280px) {
    .adm-quick-btn span { display:none; }
    .adm-quick-btn { padding:7px 10px; min-width:34px; justify-content:center; }
    #admGlobalSearch { width:150px !important; }
}

/* Tablette (≤ 1024px) */
@media (max-width:1024px) {
    .adm-nav-btn { padding:8px 10px; font-size:.78rem; }
    #admGlobalSearch { width:130px !important; }
}

/* Mobile (≤ 768px) */
@media (max-width:768px) {
    /* Header */
    .adm-header { padding:0 10px; flex-wrap:nowrap; height:56px; }
    body { padding-top:56px; }
    .adm-hdr-logo-text { display:none; }
    .adm-hdr-logo img { height:28px; }
    .adm-hdr-sep { display:none; }

    /* Nav : scroll horizontal, texte masqué */
    .adm-nav {
        gap:0; overflow-x:auto; scrollbar-width:none;
        flex-shrink:1; min-width:0;
    }
    .adm-nav::-webkit-scrollbar { display:none; }
    .adm-nav-btn {
        padding:6px 9px; font-size:.72rem; gap:4px;
        border-radius:8px;
    }
    /* Masquer le texte des groupes, garder icône + badge */
    .adm-nav-btn > .nav-txt { display:none; }
    .adm-nav-caret { display:none; }

    /* Dropdowns : plein largeur depuis le bord gauche */
    .adm-dropdown {
        left:auto; right:0; min-width:240px;
        position:fixed; top:56px;
    }

    /* Droite */
    #admGlobalSearch { display:none; }
    .adm-clock { display:none; }
    .adm-profile-info { display:none; }
    .adm-profile-btn { padding:5px; border-radius:9px; }

    /* Sous-barre page */
    .admin-topbar {
        top:56px; padding:8px 12px;
        height:auto; flex-wrap:wrap; gap:6px;
    }
    .admin-topbar-title { font-size:.88rem; }

    /* Contenu */
    .admin-page-content { padding:14px 10px; }

    /* Tables : scroll horizontal */
    .adm-card .table-responsive,
    .adm-card > div { overflow-x:auto; }

    /* Stats : 2 colonnes */
    .row.g-3 > [class*="col-md-3"] { flex:0 0 50%; max-width:50%; }
    .row.g-3 > [class*="col-md-6"] { flex:0 0 100%; max-width:100%; }
}

/* Très petit (≤ 480px) */
@media (max-width:480px) {
    .row.g-3 > [class*="col-md-3"] { flex:0 0 100%; max-width:100%; }
    .adm-nav-btn { padding:6px 7px; }
    .adm-header { padding:0 8px; }
    .adm-avatar-sm { width:30px; height:30px; font-size:.7rem; border-radius:7px; }
}

/* ══════════════════════════════════════════════════════════════
   HEADER PRINCIPAL
══════════════════════════════════════════════════════════════ */
.adm-header {
    position:fixed; top:0; left:0; right:0; height:64px; z-index:1000;
    background:linear-gradient(135deg,#0a0f1e 0%,#0d1b3e 60%,#0f2057 100%);
    display:flex; align-items:center; padding:0 20px; gap:0;
    box-shadow:0 4px 24px rgba(0,0,0,.35);
    border-bottom:1px solid rgba(255,255,255,.06);
}

/* Logo */
.adm-hdr-logo {
    display:flex; align-items:center; gap:10px; text-decoration:none;
    margin-right:24px; flex-shrink:0;
}
.adm-hdr-logo img { height:36px; }
.adm-hdr-logo-text {
    font-size:.72rem; font-weight:800; color:#e2e8f0; line-height:1.2;
    letter-spacing:.5px;
}
.adm-hdr-logo-text span { display:block; color:#38bdf8; font-size:.62rem; font-weight:600; }

/* Séparateur vertical */
.adm-hdr-sep {
    width:1px; height:32px; background:rgba(255,255,255,.1); margin:0 16px; flex-shrink:0;
}

/* ── Nav principale ── */
.adm-nav { display:flex; align-items:center; gap:2px; flex:1; }

.adm-nav-group { position:relative; }

.adm-nav-btn {
    display:flex; align-items:center; gap:7px;
    padding:8px 13px; border-radius:10px; cursor:pointer;
    font-size:.82rem; font-weight:600; color:#94a3b8;
    border:none; background:transparent;
    transition:.15s; white-space:nowrap; position:relative;
    user-select:none;
}
.adm-nav-btn:hover { background:rgba(255,255,255,.08); color:#e2e8f0; }
.adm-nav-btn.active {
    background:linear-gradient(135deg,rgba(59,130,246,.25),rgba(99,102,241,.2));
    color:#bfdbfe;
    box-shadow:inset 0 0 0 1px rgba(99,102,241,.3);
}
.adm-nav-btn .adm-nav-caret {
    font-size:.6rem; transition:transform .2s; margin-left:2px;
}
.adm-nav-group:hover .adm-nav-btn .adm-nav-caret { transform:rotate(180deg); }

.adm-nav-dot {
    width:6px; height:6px; border-radius:50%;
    background:#f97316; position:absolute; top:6px; right:6px;
    animation:pulse-dot 1.5s ease-in-out infinite;
}
@keyframes pulse-dot {
    0%,100%{opacity:1;transform:scale(1);}
    50%{opacity:.5;transform:scale(.7);}
}

/* ── Dropdown ── */
.adm-dropdown {
    position:absolute; top:calc(100% + 8px); left:0;
    background:#ffffff; border-radius:16px; min-width:260px;
    box-shadow:0 20px 60px rgba(0,0,0,.2), 0 0 0 1px rgba(0,0,0,.06);
    padding:8px; opacity:0; visibility:hidden; pointer-events:none;
    transform:translateY(-6px) scale(.98);
    transition:opacity .18s ease, transform .18s ease, visibility .18s;
    z-index:2000;
}
.adm-nav-group:hover .adm-dropdown,
.adm-nav-group:focus-within .adm-dropdown {
    opacity:1; visibility:visible; pointer-events:auto;
    transform:translateY(0) scale(1);
}

/* Arrow tip */
.adm-dropdown::before {
    content:''; position:absolute; top:-6px; left:20px;
    width:12px; height:12px; background:#fff;
    transform:rotate(45deg); border-radius:2px;
    box-shadow:-2px -2px 4px rgba(0,0,0,.06);
}

.adm-drop-item {
    display:flex; align-items:center; gap:12px; padding:10px 12px;
    border-radius:10px; text-decoration:none; color:#334155;
    transition:.12s; position:relative;
}
.adm-drop-item:hover { background:#f0f4ff; color:#1e293b; }
.adm-drop-item.active { background:linear-gradient(135deg,#eff6ff,#eef2ff); color:#1e40af; }

.adm-drop-icon {
    width:36px; height:36px; border-radius:10px; flex-shrink:0;
    display:flex; align-items:center; justify-content:center;
    font-size:1rem; background:#f1f5f9; color:#64748b; transition:.12s;
}
.adm-drop-item:hover .adm-drop-icon { background:#dbeafe; color:#2563eb; }
.adm-drop-item.active .adm-drop-icon { background:#dbeafe; color:#1d4ed8; }

.adm-drop-text { flex:1; }
.adm-drop-label { font-size:.84rem; font-weight:700; line-height:1.2; }
.adm-drop-desc  { font-size:.7rem; color:#94a3b8; margin-top:1px; }
.adm-drop-item.active .adm-drop-desc { color:#93c5fd; }

.adm-drop-badge {
    background:#f97316; color:#fff; font-size:.62rem; font-weight:800;
    padding:2px 6px; border-radius:20px; flex-shrink:0;
}

.adm-drop-divider { height:1px; background:#f1f5f9; margin:4px 0; }

/* ── Droite du header ── */
.adm-hdr-right {
    display:flex; align-items:center; gap:10px; margin-left:auto; flex-shrink:0;
}

/* Horloge */
.adm-clock {
    background:rgba(255,255,255,.06); border:1px solid rgba(255,255,255,.1);
    border-radius:10px; padding:6px 12px;
    font-family:'Courier New',monospace; font-size:.78rem; font-weight:800;
    color:#38bdf8; letter-spacing:2px; display:flex; align-items:center; gap:6px;
}
.adm-clock-dot { width:6px; height:6px; border-radius:50%; background:#22c55e; animation:pulse-dot 1.5s ease-in-out infinite; }

/* Bouton rapide */
.adm-quick-btn {
    display:flex; align-items:center; gap:6px; padding:7px 13px;
    border-radius:10px; font-size:.78rem; font-weight:700; cursor:pointer;
    border:none; text-decoration:none; transition:.15s; white-space:nowrap;
}
.adm-quick-btn-primary { background:linear-gradient(135deg,#3b82f6,#6366f1); color:#fff; box-shadow:0 4px 12px rgba(99,102,241,.35); }
.adm-quick-btn-primary:hover { transform:translateY(-1px); box-shadow:0 6px 18px rgba(99,102,241,.45); color:#fff; }
.adm-quick-btn-ghost { background:rgba(255,255,255,.07); color:#94a3b8; border:1px solid rgba(255,255,255,.1); }
.adm-quick-btn-ghost:hover { background:rgba(255,255,255,.12); color:#e2e8f0; }

/* Profil */
.adm-profile-btn {
    display:flex; align-items:center; gap:8px; padding:4px 10px 4px 4px;
    border-radius:12px; cursor:pointer; border:none;
    background:rgba(255,255,255,.09);
    border:1.5px solid rgba(99,102,241,.45);
    transition:.15s; position:relative; flex-shrink:0;
}
.adm-profile-btn:hover {
    background:rgba(255,255,255,.15);
    border-color:rgba(99,102,241,.75);
    box-shadow:0 0 0 3px rgba(99,102,241,.2);
}
.adm-avatar-sm {
    width:34px; height:34px; border-radius:9px; flex-shrink:0;
    background:linear-gradient(135deg,#6366f1,#3b82f6);
    display:flex; align-items:center; justify-content:center;
    font-size:.8rem; font-weight:900; color:#fff; letter-spacing:.5px;
    box-shadow:0 3px 10px rgba(99,102,241,.45);
}
.adm-profile-info { text-align:left; }
.adm-profile-name-sm { font-size:.75rem; font-weight:700; color:#e2e8f0; line-height:1.1; white-space:nowrap; }
.adm-profile-role-sm { font-size:.62rem; color:#94a3b8; white-space:nowrap; }

/* Dropdown profil */
.adm-profile-group { position:relative; }
.adm-profile-dropdown {
    position:absolute; top:calc(100% + 10px); right:0;
    background:#fff; border-radius:16px; min-width:230px;
    box-shadow:0 24px 64px rgba(0,0,0,.2), 0 0 0 1px rgba(0,0,0,.06);
    padding:8px; opacity:0; visibility:hidden; pointer-events:none;
    transform:translateY(-8px) scale(.97); transition:.18s ease; z-index:3000;
}
/* Flèche */
.adm-profile-dropdown::before {
    content:''; position:absolute; top:-6px; right:18px;
    width:12px; height:12px; background:#fff;
    transform:rotate(45deg); border-radius:2px;
    box-shadow:-2px -2px 4px rgba(0,0,0,.05);
}
.adm-profile-group:hover .adm-profile-dropdown {
    opacity:1; visibility:visible; pointer-events:auto;
    transform:translateY(0) scale(1);
}
/* Carte identité en haut du dropdown */
.adm-prof-id-card {
    display:flex; align-items:center; gap:12px;
    padding:12px 14px; background:linear-gradient(135deg,#eff6ff,#eef2ff);
    border-radius:12px; margin-bottom:6px;
}
.adm-prof-id-avatar {
    width:42px; height:42px; border-radius:11px; flex-shrink:0;
    background:linear-gradient(135deg,#6366f1,#3b82f6);
    display:flex; align-items:center; justify-content:center;
    font-size:.88rem; font-weight:900; color:#fff;
    box-shadow:0 4px 10px rgba(99,102,241,.35);
}
.adm-prof-id-name { font-size:.84rem; font-weight:800; color:#1e293b; }
.adm-prof-id-role {
    display:inline-block; margin-top:2px;
    font-size:.62rem; font-weight:700; background:#c7d2fe; color:#3730a3;
    border-radius:20px; padding:1px 8px; letter-spacing:.3px;
}
.adm-prof-item {
    display:flex; align-items:center; gap:10px; padding:9px 12px;
    border-radius:9px; text-decoration:none; color:#334155; font-size:.82rem; font-weight:600;
    transition:.12s; cursor:pointer; border:none; background:none; width:100%;
}
.adm-prof-item:hover { background:#f0f4ff; color:#1e293b; }
.adm-prof-item i { font-size:.9rem; color:#64748b; }
.adm-prof-item:hover i { color:#3b82f6; }
.adm-prof-item.danger { color:#dc2626; }
.adm-prof-item.danger:hover { background:#fef2f2; }
.adm-prof-item.danger i { color:#dc2626; }
.adm-prof-divider { height:1px; background:#f1f5f9; margin:4px 0; }
.adm-prof-header {
    padding:10px 12px 6px;
    font-size:.72rem; font-weight:800; text-transform:uppercase;
    letter-spacing:.8px; color:#94a3b8;
}

/* ══ PAGE CONTENT ══ */
.admin-page-content { padding:28px 28px; max-width:1600px; margin:0 auto; }

/* Shared utilities (kept from old nav) */
.adm-stat {
    background:#fff; border:1px solid #e8edf5; border-radius:16px;
    padding:1.1rem 1.4rem; position:relative; overflow:hidden;
    transition:transform .2s, box-shadow .2s;
    box-shadow:0 1px 6px rgba(0,0,0,.06);
}
.adm-stat::before {
    content:''; position:absolute; left:0; top:0; bottom:0; width:4px;
    border-radius:16px 0 0 16px; background:var(--adm-accent,#3b82f6);
}
.adm-stat:hover { transform:translateY(-2px); box-shadow:0 8px 24px rgba(0,0,0,.1); }
.adm-stat-val  { font-size:1.9rem; font-weight:800; line-height:1; color:#0f172a; }
.adm-stat-lbl  { font-size:.67rem; font-weight:700; text-transform:uppercase; letter-spacing:1px; color:#94a3b8; margin-top:4px; }
.adm-stat-ico  { position:absolute; right:14px; top:50%; transform:translateY(-50%); font-size:2rem; opacity:.07; color:#0f172a; }

.adm-card { background:#fff; border:1px solid #e8edf5; border-radius:16px; box-shadow:0 1px 6px rgba(0,0,0,.06); overflow:hidden; }
.adm-card-header { padding:.9rem 1.4rem; border-bottom:1px solid #f1f5f9; display:flex; align-items:center; justify-content:space-between; font-size:.82rem; font-weight:700; color:#0f172a; }
.adm-card-body { padding:1.2rem 1.4rem; }

.adm-table { width:100%; border-collapse:collapse; }
.adm-table thead tr { background:#f8fafc; border-bottom:2px solid #e8edf5; }
.adm-table thead th { font-size:.69rem; font-weight:700; text-transform:uppercase; letter-spacing:.8px; color:#94a3b8; padding:.8rem 1rem; white-space:nowrap; }
.adm-table tbody tr { border-bottom:1px solid #f1f5f9; transition:background .12s; }
.adm-table tbody tr:hover { background:#f8fafc; }
.adm-table tbody td { padding:.75rem 1rem; font-size:.84rem; color:#334155; vertical-align:middle; }
.adm-table tbody tr:last-child { border-bottom:none; }

.adm-action-btn {
    width:32px; height:32px; border-radius:8px; border:1px solid #e2e8f0;
    background:#f8fafc; display:inline-flex; align-items:center; justify-content:center;
    font-size:.85rem; cursor:pointer; transition:all .15s; color:#64748b; text-decoration:none;
}
.adm-action-btn:hover { transform:translateY(-1px); box-shadow:0 3px 8px rgba(0,0,0,.12); color:#0f172a; background:#fff; }

.adm-alert-info    { background:#eff6ff; border:1px solid #bfdbfe; border-radius:12px; padding:.85rem 1.2rem; font-size:.83rem; color:#1e40af; }
.adm-alert-warning { background:#fffbeb; border:1px solid #fde68a; border-radius:12px; padding:.85rem 1.2rem; font-size:.83rem; color:#92400e; }
.adm-alert-danger  { background:#fef2f2; border:1px solid #fecaca; border-radius:12px; padding:.85rem 1.2rem; font-size:.83rem; color:#991b1b; }
.adm-alert-success { background:#f0fdf4; border:1px solid #bbf7d0; border-radius:12px; padding:.85rem 1.2rem; font-size:.83rem; color:#166534; }

.adm-section-title {
    font-size:.75rem; font-weight:800; text-transform:uppercase; letter-spacing:1.2px;
    color:#94a3b8; margin-bottom:.8rem; display:flex; align-items:center; gap:8px;
}
.adm-section-title::after { content:''; flex:1; height:1px; background:#f1f5f9; }

/* Breadcrumb */
.adm-breadcrumb {
    display:flex; align-items:center; gap:6px;
    font-size:.75rem; color:#94a3b8; font-weight:600; margin-bottom:20px;
}
.adm-breadcrumb a { color:#6366f1; text-decoration:none; }
.adm-breadcrumb a:hover { text-decoration:underline; }
.adm-breadcrumb .sep { color:#cbd5e1; }
</style>

<!-- ═══════════════════════════════════════════════════════
     HEADER ADMIN
═══════════════════════════════════════════════════════ -->
<header class="adm-header">

    <!-- Logo -->
    <a href="<?= BASE_URL ?>dashboard" class="adm-hdr-logo">
        <img src="<?= BASE_URL ?>public/images/simcare_plus_logo.svg" alt="SimCare+">
        <div class="adm-hdr-logo-text">
            SimCare+
            <span>Administration</span>
        </div>
    </a>

    <div class="adm-hdr-sep"></div>

    <!-- Navigation groupes + dropdowns -->
    <nav class="adm-nav">
        <?php foreach($navGroups as $group):
            $isActive = in_array($adminPage, $group['pages']);
        ?>
        <div class="adm-nav-group">
            <button class="adm-nav-btn <?= $isActive ? 'active' : '' ?>">
                <i class="bi <?= $group['icon'] ?>"></i>
                <span class="nav-txt"><?= $group['label'] ?></span>
                <?php if (!empty($group['badge'])): ?>
                <span style="background:#f97316;color:#fff;font-size:.6rem;font-weight:800;
                             padding:1px 6px;border-radius:20px;margin-left:2px;">
                    <?= $group['badge'] ?>
                </span>
                <?php endif; ?>
                <i class="bi bi-chevron-down adm-nav-caret"></i>
                <?php if ($isActive): ?><span class="adm-nav-dot"></span><?php endif; ?>
            </button>

            <div class="adm-dropdown">
                <?php foreach($group['items'] as $idx => $item):
                    $isItemActive = ($adminPage === $item['page']);
                ?>
                <?php if ($idx > 0): ?><div class="adm-drop-divider"></div><?php endif; ?>
                <a href="<?= BASE_URL ?><?= $item['href'] ?>"
                   class="adm-drop-item <?= $isItemActive ? 'active' : '' ?>">
                    <div class="adm-drop-icon"><i class="bi <?= $item['icon'] ?>"></i></div>
                    <div class="adm-drop-text">
                        <div class="adm-drop-label"><?= $item['label'] ?></div>
                        <div class="adm-drop-desc"><?= $item['desc'] ?></div>
                    </div>
                    <?php if (!empty($item['badge'])): ?>
                    <span class="adm-drop-badge"><?= $item['badge'] ?></span>
                    <?php endif; ?>
                    <?php if ($isItemActive): ?>
                    <i class="bi bi-check2" style="color:#2563eb;font-size:.85rem;flex-shrink:0;"></i>
                    <?php endif; ?>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </nav>

    <!-- Droite -->
    <div class="adm-hdr-right">

        <!-- Recherche rapide -->
        <div style="position:relative;">
            <input type="search" id="admGlobalSearch"
                   placeholder="Rechercher un patient…"
                   style="background:rgba(255,255,255,.07);border:1px solid rgba(255,255,255,.12);
                          border-radius:10px;padding:7px 12px 7px 34px;font-size:.78rem;
                          color:#e2e8f0;outline:none;width:200px;transition:.2s;"
                   onfocus="this.style.width='260px';this.style.borderColor='rgba(99,102,241,.6)'"
                   onblur="this.style.width='200px';this.style.borderColor='rgba(255,255,255,.12)'"
                   oninput="admSearch(this.value)">
            <i class="bi bi-search" style="position:absolute;left:10px;top:50%;transform:translateY(-50%);
               color:#64748b;font-size:.78rem;pointer-events:none;"></i>
            <div id="admSearchResults" style="display:none;position:absolute;top:calc(100%+6px);right:0;
                 background:#fff;border-radius:12px;min-width:300px;max-height:320px;overflow-y:auto;
                 box-shadow:0 20px 60px rgba(0,0,0,.15);padding:6px;z-index:3000;"></div>
        </div>

        <!-- Horloge -->
        <div class="adm-clock">
            <span class="adm-clock-dot"></span>
            <span class="adm-clock-time">00:00:00</span>
        </div>

        <!-- Nouveau patient (raccourci) -->
        <a href="<?= BASE_URL ?>patients/nouveau" class="adm-quick-btn adm-quick-btn-primary">
            <i class="bi bi-person-plus-fill"></i>
            <span>Nouveau patient</span>
        </a>

        <!-- Profil dropdown -->
        <div class="adm-profile-group">
            <button class="adm-profile-btn" title="<?= htmlspecialchars($userName) ?>">
                <div class="adm-avatar-sm"><?= $userInit ?></div>
                <div class="adm-profile-info">
                    <div class="adm-profile-name-sm"><?= htmlspecialchars($userName) ?></div>
                    <div class="adm-profile-role-sm"><?= $_isSuperAdmin ? 'Super Administrateur' : 'Administrateur' ?></div>
                </div>
                <i class="bi bi-chevron-down" style="color:#94a3b8;font-size:.6rem;margin-left:2px;"></i>
            </button>

            <div class="adm-profile-dropdown">
                <!-- Carte identité -->
                <div class="adm-prof-id-card">
                    <div class="adm-prof-id-avatar"><?= $userInit ?></div>
                    <div>
                        <div class="adm-prof-id-name"><?= htmlspecialchars($userName) ?></div>
                        <span class="adm-prof-id-role">Administrateur</span>
                    </div>
                </div>
                <button type="button" class="adm-prof-item" style="width:100%;text-align:left;background:none;border:none;cursor:pointer;" onclick="ouvrirModalProfil()">
                    <i class="bi bi-person-circle"></i> Mon profil
                </button>
                <a href="<?= BASE_URL ?>admin/config" class="adm-prof-item">
                    <i class="bi bi-gear-fill"></i> Configuration
                </a>
                <div class="adm-prof-divider"></div>
                <a href="<?= BASE_URL ?>dashboard" class="adm-prof-item">
                    <i class="bi bi-arrow-left-circle-fill"></i> Vue médecin
                </a>
                <div class="adm-prof-divider"></div>
                <a href="<?= BASE_URL ?>logout" class="adm-prof-item danger">
                    <i class="bi bi-power"></i> Déconnexion
                </a>
            </div>
        </div>

    </div>
</header>

<script>
/* ── Horloge ── */
(function(){
    function tick(){
        const t = new Date().toLocaleTimeString('fr-FR',{hour:'2-digit',minute:'2-digit',second:'2-digit'});
        document.querySelectorAll('.adm-clock-time').forEach(el => el.textContent = t);
    }
    tick(); setInterval(tick,1000);
})();

/* ── Recherche rapide patients (AJAX) ── */
let admSearchTimer;
function admSearch(q) {
    const box = document.getElementById('admSearchResults');
    clearTimeout(admSearchTimer);
    if (q.length < 2) { box.style.display='none'; return; }
    admSearchTimer = setTimeout(() => {
        fetch('<?= BASE_URL ?>patients/recherche?q=' + encodeURIComponent(q))
        .then(r=>r.json()).then(data=>{
            const list = data.patients || data;
            if (!list.length) { box.style.display='none'; return; }
            box.innerHTML = list.slice(0,8).map(p=>`
                <a href="<?= BASE_URL ?>patients/dossier/${p.id}"
                   style="display:flex;align-items:center;gap:10px;padding:8px 10px;
                          border-radius:9px;text-decoration:none;color:#1e293b;transition:.1s;"
                   onmouseover="this.style.background='#f0f4ff'"
                   onmouseout="this.style.background='none'">
                    <div style="width:32px;height:32px;border-radius:8px;background:#eff6ff;
                                color:#2563eb;display:flex;align-items:center;justify-content:center;
                                font-size:.82rem;font-weight:800;flex-shrink:0;">
                        ${(p.prenom||'').charAt(0)}${(p.nom||'').charAt(0)}
                    </div>
                    <div>
                        <div style="font-size:.82rem;font-weight:700;">${p.nom} ${p.prenom}</div>
                        <div style="font-size:.7rem;color:#94a3b8;">${p.dossier_numero||''} · ${p.nom_service||p.service||''}</div>
                    </div>
                </a>`).join('');
            box.style.display='block';
        }).catch(()=>{});
    }, 280);
}
document.addEventListener('click', e => {
    if (!e.target.closest('#admGlobalSearch') && !e.target.closest('#admSearchResults'))
        document.getElementById('admSearchResults').style.display='none';
});
</script>

<!-- ══════════════════════════════════════════════════════════════
     MODAL PROFIL UTILISATEUR
     ══════════════════════════════════════════════════════════════ -->
<div id="modalProfil" style="display:none;position:fixed;inset:0;z-index:9999;background:rgba(15,23,42,.55);backdrop-filter:blur(4px);align-items:center;justify-content:center;">
<div style="background:#fff;border-radius:20px;width:100%;max-width:500px;margin:16px;box-shadow:0 24px 60px rgba(0,0,0,.22);overflow:hidden;animation:profSlideIn .22s ease;">

    <!-- En-tête -->
    <div style="background:linear-gradient(135deg,#1d4ed8,#3b82f6);padding:20px 24px;display:flex;align-items:center;justify-content:space-between;">
        <div style="display:flex;align-items:center;gap:12px;">
            <div id="mpAvatar" style="width:44px;height:44px;border-radius:13px;background:rgba(255,255,255,.22);display:flex;align-items:center;justify-content:center;font-size:1.1rem;font-weight:900;color:#fff;flex-shrink:0;">—</div>
            <div>
                <div id="mpNomComplet" style="font-size:.95rem;font-weight:800;color:#fff;">Chargement…</div>
                <div id="mpRole" style="font-size:.72rem;color:rgba(255,255,255,.75);"></div>
            </div>
        </div>
        <button onclick="fermerModalProfil()" style="background:rgba(255,255,255,.18);border:none;border-radius:9px;width:32px;height:32px;cursor:pointer;color:#fff;font-size:1rem;display:flex;align-items:center;justify-content:center;">&times;</button>
    </div>

    <!-- Onglets -->
    <div style="display:flex;border-bottom:2px solid #e2e8f0;">
        <button id="mpTab1" onclick="mpSwitchTab(1)" style="flex:1;padding:11px;border:none;background:#eff6ff;color:#1d4ed8;font-weight:700;font-size:.82rem;cursor:pointer;border-bottom:2px solid #1d4ed8;margin-bottom:-2px;">
            <i class="bi bi-person-fill"></i> Informations
        </button>
        <button id="mpTab2" onclick="mpSwitchTab(2)" style="flex:1;padding:11px;border:none;background:#fff;color:#64748b;font-weight:600;font-size:.82rem;cursor:pointer;">
            <i class="bi bi-shield-lock-fill"></i> Mot de passe
        </button>
    </div>

    <!-- Corps : Informations -->
    <div id="mpPanel1" style="padding:22px 24px;">
        <div id="mpAlertInfo" style="display:none;padding:9px 14px;border-radius:10px;font-size:.82rem;font-weight:600;margin-bottom:14px;"></div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
            <div>
                <label style="font-size:.68rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.4px;display:block;margin-bottom:4px;">Nom</label>
                <input id="mpNom" type="text" style="width:100%;padding:9px 12px;border:1.5px solid #e2e8f0;border-radius:10px;font-size:.88rem;color:#1e293b;outline:none;box-sizing:border-box;" onfocus="this.style.borderColor='#3b82f6'" onblur="this.style.borderColor='#e2e8f0'">
            </div>
            <div>
                <label style="font-size:.68rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.4px;display:block;margin-bottom:4px;">Prénom</label>
                <input id="mpPrenom" type="text" style="width:100%;padding:9px 12px;border:1.5px solid #e2e8f0;border-radius:10px;font-size:.88rem;color:#1e293b;outline:none;box-sizing:border-box;" onfocus="this.style.borderColor='#3b82f6'" onblur="this.style.borderColor='#e2e8f0'">
            </div>
        </div>
        <div style="margin-top:12px;">
            <label style="font-size:.68rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.4px;display:block;margin-bottom:4px;">Email</label>
            <input id="mpEmail" type="email" style="width:100%;padding:9px 12px;border:1.5px solid #e2e8f0;border-radius:10px;font-size:.88rem;color:#1e293b;outline:none;box-sizing:border-box;" onfocus="this.style.borderColor='#3b82f6'" onblur="this.style.borderColor='#e2e8f0'">
        </div>
        <div style="margin-top:12px;">
            <label style="font-size:.68rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.4px;display:block;margin-bottom:4px;">Téléphone</label>
            <input id="mpTel" type="tel" style="width:100%;padding:9px 12px;border:1.5px solid #e2e8f0;border-radius:10px;font-size:.88rem;color:#1e293b;outline:none;box-sizing:border-box;" onfocus="this.style.borderColor='#3b82f6'" onblur="this.style.borderColor='#e2e8f0'">
        </div>
        <div style="margin-top:12px;display:grid;grid-template-columns:1fr 1fr;gap:12px;">
            <div>
                <label style="font-size:.68rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.4px;display:block;margin-bottom:4px;">Identifiant</label>
                <input id="mpUsername" type="text" disabled style="width:100%;padding:9px 12px;border:1.5px solid #e2e8f0;border-radius:10px;font-size:.88rem;color:#94a3b8;background:#f8fafc;box-sizing:border-box;">
            </div>
            <div>
                <label style="font-size:.68rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.4px;display:block;margin-bottom:4px;">Service</label>
                <input id="mpService" type="text" disabled style="width:100%;padding:9px 12px;border:1.5px solid #e2e8f0;border-radius:10px;font-size:.88rem;color:#94a3b8;background:#f8fafc;box-sizing:border-box;">
            </div>
        </div>
        <div style="margin-top:20px;display:flex;gap:10px;justify-content:flex-end;">
            <button onclick="fermerModalProfil()" style="padding:9px 20px;border:1.5px solid #e2e8f0;border-radius:10px;background:#fff;color:#64748b;font-weight:600;font-size:.82rem;cursor:pointer;">Annuler</button>
            <button onclick="mpSauvegarderInfo()" id="mpBtnInfo" style="padding:9px 22px;border:none;border-radius:10px;background:#1d4ed8;color:#fff;font-weight:700;font-size:.82rem;cursor:pointer;display:flex;align-items:center;gap:6px;">
                <i class="bi bi-floppy-fill"></i> Enregistrer
            </button>
        </div>
    </div>

    <!-- Corps : Mot de passe -->
    <div id="mpPanel2" style="display:none;padding:22px 24px;">
        <div id="mpAlertPwd" style="display:none;padding:9px 14px;border-radius:10px;font-size:.82rem;font-weight:600;margin-bottom:14px;"></div>
        <div>
            <label style="font-size:.68rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.4px;display:block;margin-bottom:4px;">Mot de passe actuel *</label>
            <div style="position:relative;">
                <input id="mpPwdActuel" type="password" autocomplete="current-password" style="width:100%;padding:9px 40px 9px 12px;border:1.5px solid #e2e8f0;border-radius:10px;font-size:.88rem;color:#1e293b;outline:none;box-sizing:border-box;" onfocus="this.style.borderColor='#3b82f6'" onblur="this.style.borderColor='#e2e8f0'" placeholder="Votre mot de passe actuel">
                <button type="button" onclick="mpTogglePwd('mpPwdActuel',this)" style="position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:#94a3b8;font-size:.9rem;padding:2px;"><i class="bi bi-eye"></i></button>
            </div>
        </div>
        <div style="margin-top:12px;">
            <label style="font-size:.68rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.4px;display:block;margin-bottom:4px;">Nouveau mot de passe *</label>
            <div style="position:relative;">
                <input id="mpPwdNew" type="password" autocomplete="new-password" style="width:100%;padding:9px 40px 9px 12px;border:1.5px solid #e2e8f0;border-radius:10px;font-size:.88rem;color:#1e293b;outline:none;box-sizing:border-box;" onfocus="this.style.borderColor='#3b82f6'" onblur="this.style.borderColor='#e2e8f0'" placeholder="6 caractères minimum" oninput="mpForceBar(this.value)">
                <button type="button" onclick="mpTogglePwd('mpPwdNew',this)" style="position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:#94a3b8;font-size:.9rem;padding:2px;"><i class="bi bi-eye"></i></button>
            </div>
            <!-- Barre de force -->
            <div style="margin-top:6px;height:5px;border-radius:4px;background:#e2e8f0;overflow:hidden;">
                <div id="mpForceBar" style="height:100%;width:0;border-radius:4px;transition:width .25s,background .25s;"></div>
            </div>
            <div id="mpForceLabel" style="font-size:.68rem;color:#94a3b8;margin-top:3px;"></div>
        </div>
        <div style="margin-top:12px;">
            <label style="font-size:.68rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.4px;display:block;margin-bottom:4px;">Confirmer le nouveau mot de passe *</label>
            <div style="position:relative;">
                <input id="mpPwdConfirm" type="password" autocomplete="new-password" style="width:100%;padding:9px 40px 9px 12px;border:1.5px solid #e2e8f0;border-radius:10px;font-size:.88rem;color:#1e293b;outline:none;box-sizing:border-box;" onfocus="this.style.borderColor='#3b82f6'" onblur="this.style.borderColor='#e2e8f0'" placeholder="Répétez le nouveau mot de passe">
                <button type="button" onclick="mpTogglePwd('mpPwdConfirm',this)" style="position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:#94a3b8;font-size:.9rem;padding:2px;"><i class="bi bi-eye"></i></button>
            </div>
        </div>
        <div style="margin-top:20px;display:flex;gap:10px;justify-content:flex-end;">
            <button onclick="fermerModalProfil()" style="padding:9px 20px;border:1.5px solid #e2e8f0;border-radius:10px;background:#fff;color:#64748b;font-weight:600;font-size:.82rem;cursor:pointer;">Annuler</button>
            <button onclick="mpSauvegarderPwd()" id="mpBtnPwd" style="padding:9px 22px;border:none;border-radius:10px;background:#1d4ed8;color:#fff;font-weight:700;font-size:.82rem;cursor:pointer;display:flex;align-items:center;gap:6px;">
                <i class="bi bi-shield-lock-fill"></i> Changer le mot de passe
            </button>
        </div>
    </div>

</div>
</div>

<style>
@keyframes profSlideIn { from{opacity:0;transform:translateY(-14px)} to{opacity:1;transform:translateY(0)} }
.adm-prof-item { font-family:inherit; }
</style>

<script>
const MP_BASE = '<?= BASE_URL ?>';

function ouvrirModalProfil() {
    const m = document.getElementById('modalProfil');
    m.style.display = 'flex';
    mpSwitchTab(1);
    mpCharger();
    // Fermer le dropdown
    document.querySelector('.adm-profile-dropdown') && (document.querySelector('.adm-profile-group').blur());
}

function fermerModalProfil() {
    document.getElementById('modalProfil').style.display = 'none';
    ['mpPwdActuel','mpPwdNew','mpPwdConfirm'].forEach(id => { const el=document.getElementById(id); if(el) el.value=''; });
    ['mpAlertInfo','mpAlertPwd'].forEach(id => { const el=document.getElementById(id); if(el) el.style.display='none'; });
    document.getElementById('mpForceBar').style.width = '0';
    document.getElementById('mpForceLabel').textContent = '';
}

// Fermer en cliquant l'arrière-plan
document.getElementById('modalProfil').addEventListener('click', function(e) {
    if (e.target === this) fermerModalProfil();
});

function mpSwitchTab(n) {
    const t1=document.getElementById('mpTab1'), t2=document.getElementById('mpTab2');
    const p1=document.getElementById('mpPanel1'), p2=document.getElementById('mpPanel2');
    const activeStyle  = 'flex:1;padding:11px;border:none;background:#eff6ff;color:#1d4ed8;font-weight:700;font-size:.82rem;cursor:pointer;border-bottom:2px solid #1d4ed8;margin-bottom:-2px;';
    const inactiveStyle= 'flex:1;padding:11px;border:none;background:#fff;color:#64748b;font-weight:600;font-size:.82rem;cursor:pointer;';
    if (n===1) { t1.style.cssText=activeStyle; t2.style.cssText=inactiveStyle; p1.style.display='block'; p2.style.display='none'; }
    else       { t2.style.cssText=activeStyle; t1.style.cssText=inactiveStyle; p2.style.display='block'; p1.style.display='none'; }
}

function mpCharger() {
    fetch(MP_BASE + 'profil/json')
        .then(r => r.json())
        .then(data => {
            if (!data.success) return;
            document.getElementById('mpNom').value      = data.nom     || '';
            document.getElementById('mpPrenom').value   = data.prenom  || '';
            document.getElementById('mpEmail').value    = data.email   || '';
            document.getElementById('mpTel').value      = data.telephone || '';
            document.getElementById('mpUsername').value = data.username || '';
            document.getElementById('mpService').value  = data.service  || '';
            const initiales = ((data.nom||'').charAt(0) + (data.prenom||'').charAt(0)).toUpperCase();
            document.getElementById('mpAvatar').textContent    = initiales || '?';
            document.getElementById('mpNomComplet').textContent= (data.nom||'') + ' ' + (data.prenom||'');
            document.getElementById('mpRole').textContent      = data.role || '';
        })
        .catch(() => mpShowAlert('mpAlertInfo', 'Erreur de chargement des données.', false));
}

function mpShowAlert(id, msg, ok) {
    const el = document.getElementById(id);
    el.style.display = 'block';
    el.style.background = ok ? '#f0fdf4' : '#fef2f2';
    el.style.color      = ok ? '#166534' : '#991b1b';
    el.style.border     = ok ? '1px solid #86efac' : '1px solid #fca5a5';
    el.innerHTML = (ok ? '<i class="bi bi-check-circle-fill"></i> ' : '<i class="bi bi-exclamation-circle-fill"></i> ') + msg;
    if (ok) setTimeout(() => { el.style.display='none'; }, 3500);
}

function mpSetLoading(btnId, loading) {
    const btn = document.getElementById(btnId);
    btn.disabled = loading;
    btn.style.opacity = loading ? '.6' : '1';
}

function mpSauvegarderInfo() {
    const nom    = document.getElementById('mpNom').value.trim();
    const prenom = document.getElementById('mpPrenom').value.trim();
    if (!nom || !prenom) { mpShowAlert('mpAlertInfo','Nom et prénom obligatoires.',false); return; }

    mpSetLoading('mpBtnInfo', true);
    fetch(MP_BASE + 'profil/update-json', {
        method: 'POST',
        headers: {'Content-Type':'application/json'},
        body: JSON.stringify({
            nom,
            prenom,
            email:     document.getElementById('mpEmail').value.trim(),
            telephone: document.getElementById('mpTel').value.trim(),
        })
    })
    .then(r => r.json())
    .then(data => {
        mpSetLoading('mpBtnInfo', false);
        if (data.success) {
            mpShowAlert('mpAlertInfo', 'Profil mis à jour avec succès.', true);
            document.getElementById('mpNomComplet').textContent = (data.nom||'') + ' ' + (data.prenom||'');
            const initiales = ((data.nom||'').charAt(0)+(data.prenom||'').charAt(0)).toUpperCase();
            document.getElementById('mpAvatar').textContent = initiales;
        } else {
            mpShowAlert('mpAlertInfo', data.message || 'Erreur lors de la sauvegarde.', false);
        }
    })
    .catch(() => { mpSetLoading('mpBtnInfo',false); mpShowAlert('mpAlertInfo','Erreur réseau.',false); });
}

function mpSauvegarderPwd() {
    const actuel  = document.getElementById('mpPwdActuel').value;
    const nouveau = document.getElementById('mpPwdNew').value;
    const confirm = document.getElementById('mpPwdConfirm').value;

    if (!actuel)  { mpShowAlert('mpAlertPwd','Veuillez saisir votre mot de passe actuel.',false); return; }
    if (!nouveau) { mpShowAlert('mpAlertPwd','Veuillez saisir un nouveau mot de passe.',false); return; }
    if (nouveau.length < 6) { mpShowAlert('mpAlertPwd','Le mot de passe doit faire au moins 6 caractères.',false); return; }
    if (nouveau !== confirm) { mpShowAlert('mpAlertPwd','Les deux mots de passe ne correspondent pas.',false); return; }

    // Charger d'abord nom/prénom pour le payload obligatoire
    const nom    = document.getElementById('mpNom').value.trim();
    const prenom = document.getElementById('mpPrenom').value.trim();

    mpSetLoading('mpBtnPwd', true);
    fetch(MP_BASE + 'profil/update-json', {
        method: 'POST',
        headers: {'Content-Type':'application/json'},
        body: JSON.stringify({
            nom:              nom || '—',
            prenom:           prenom || '—',
            current_password: actuel,
            new_password:     nouveau,
        })
    })
    .then(r => r.json())
    .then(data => {
        mpSetLoading('mpBtnPwd', false);
        if (data.success) {
            mpShowAlert('mpAlertPwd','Mot de passe modifié avec succès !', true);
            ['mpPwdActuel','mpPwdNew','mpPwdConfirm'].forEach(id => document.getElementById(id).value='');
            document.getElementById('mpForceBar').style.width='0';
            document.getElementById('mpForceLabel').textContent='';
        } else {
            mpShowAlert('mpAlertPwd', data.message || 'Erreur lors du changement.', false);
        }
    })
    .catch(() => { mpSetLoading('mpBtnPwd',false); mpShowAlert('mpAlertPwd','Erreur réseau.',false); });
}

function mpTogglePwd(inputId, btn) {
    const inp = document.getElementById(inputId);
    const show = inp.type === 'password';
    inp.type = show ? 'text' : 'password';
    btn.innerHTML = show ? '<i class="bi bi-eye-slash"></i>' : '<i class="bi bi-eye"></i>';
}

function mpForceBar(pwd) {
    let score = 0;
    if (pwd.length >= 6)  score++;
    if (pwd.length >= 10) score++;
    if (/[A-Z]/.test(pwd) && /[a-z]/.test(pwd)) score++;
    if (/[0-9]/.test(pwd)) score++;
    if (/[^A-Za-z0-9]/.test(pwd)) score++;
    const levels = [
        {w:'0%',   bg:'#e2e8f0', lbl:''},
        {w:'25%',  bg:'#ef4444', lbl:'Très faible'},
        {w:'50%',  bg:'#f97316', lbl:'Faible'},
        {w:'70%',  bg:'#eab308', lbl:'Moyen'},
        {w:'88%',  bg:'#22c55e', lbl:'Fort'},
        {w:'100%', bg:'#16a34a', lbl:'Très fort'},
    ];
    const l = levels[score] || levels[0];
    document.getElementById('mpForceBar').style.width      = l.w;
    document.getElementById('mpForceBar').style.background = l.bg;
    document.getElementById('mpForceLabel').textContent    = l.lbl;
    document.getElementById('mpForceLabel').style.color    = l.bg;
}
</script>
