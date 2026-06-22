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

<!-- IMPORTATION DES ICONES ET CSS ADDITIONNELS -->
<link rel="stylesheet" href="<?= BASE_URL ?>public/css/bootstrap-icons.css">

<style>
/* ════════════════════════════════════════════════════════
   DASHBOARD MÉDECIN — Design System v2
   Palette : bleu nuit #1e40af · teal #0d9488 · slate #f8fafc
   ════════════════════════════════════════════════════════ */
:root {
    --c-blue:    #1e40af;
    --c-blue-lt: #eff6ff;
    --c-teal:    #0d9488;
    --c-teal-lt: #f0fdfa;
    --c-green:   #16a34a;
    --c-amber:   #d97706;
    --c-red:     #dc2626;
    --c-purple:  #7c3aed;
    --c-slate:   #64748b;
    --c-bg:      #f1f5f9;
    --radius:    16px;
    --shadow:    0 2px 16px rgba(15,23,42,.07);
    --shadow-md: 0 6px 30px rgba(15,23,42,.10);
}

/* ── Reset ── */
*, *::before, *::after { box-sizing: border-box; }
body { background: var(--c-bg); font-family: 'Segoe UI', system-ui, sans-serif; color: #1e293b; margin: 0; }
.sidebar { display: none !important; }
#wrapper, .main-wrapper { margin: 0 !important; padding: 0 !important; width: 100% !important; }
main { margin-left: 0 !important; width: 100% !important; }

/* ── Topbar ── */
.top-nav {
    background: #fff;
    padding: 0 28px;
    height: 60px;
    border-bottom: 1px solid #e2e8f0;
    box-shadow: 0 1px 8px rgba(30,64,175,.06);
    display: flex; justify-content: space-between; align-items: center;
    position: sticky; top: 0; z-index: 1000;
}
.top-nav-left { display: flex; align-items: center; gap: 12px; }
.top-nav-avatar {
    width: 40px; height: 40px; border-radius: 12px;
    background: linear-gradient(135deg, #dbeafe, #bfdbfe);
    display: flex; align-items: center; justify-content: center; flex-shrink: 0;
}
.top-nav-service {
    font-size: .7rem; font-weight: 700; text-transform: uppercase; letter-spacing: .7px;
    color: var(--c-blue); background: var(--c-blue-lt); border: 1px solid #bfdbfe;
    padding: 4px 12px; border-radius: 20px;
}
.top-nav-doctor { font-size: .9rem; font-weight: 700; color: #0f172a; line-height: 1.2; }
.top-nav-sub    { font-size: .72rem; color: #94a3b8; }
.top-nav-clock  {
    font-family: 'SF Mono', 'Fira Code', monospace;
    font-size: 1rem; font-weight: 700; color: #1e293b;
    background: #f8fafc; border: 1px solid #e2e8f0;
    padding: 6px 14px; border-radius: 10px; letter-spacing: 1px;
}
.top-nav-btn {
    width: 36px; height: 36px; border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    border: 1px solid #e2e8f0; background: #f8fafc;
    color: #64748b; text-decoration: none; cursor: pointer;
    transition: all .15s ease;
}
.top-nav-btn:hover { background: #f1f5f9; border-color: #94a3b8; color: #0f172a; }
.top-nav-btn.danger:hover { background: #fff1f2; border-color: #fca5a5; color: var(--c-red); }
.top-nav-icon-btn {
    width: 34px; height: 34px; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    background: none; border: none; cursor: pointer;
    font-size: 1rem; transition: background .15s;
}
.top-nav-icon-btn:hover { background: rgba(0,0,0,.07); }

/* ── Layout ── */
.dashboard-content { padding: 24px 28px; max-width: 1700px; margin: 0 auto; }

/* ── Cards ── */
.med-card {
    background: #fff; border-radius: var(--radius);
    box-shadow: var(--shadow); margin-bottom: 20px;
    overflow: hidden; border: 1px solid #f1f5f9;
    transition: box-shadow .2s ease;
}
.med-card:hover { box-shadow: var(--shadow-md); }

/* ── Section header ── */
.card-header-custom {
    padding: 16px 22px; border-bottom: 1px solid #f1f5f9;
    display: flex; justify-content: space-between; align-items: center;
    background: #fff;
}
.section-title {
    font-size: .85rem; font-weight: 700; color: #0f172a;
    display: flex; align-items: center; gap: 8px; margin: 0;
}
.section-title .s-icon {
    width: 32px; height: 32px; border-radius: 8px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1rem; flex-shrink: 0;
}

/* ── Stat Widgets — nouvelle version ── */
.stat-widget {
    padding: 0; display: flex; flex-direction: column;
    border-radius: var(--radius); overflow: hidden;
    box-shadow: var(--shadow); border: none; margin-bottom: 20px;
    background: #fff; position: relative;
}
.stat-widget-body { padding: 20px 22px 16px; display: flex; align-items: flex-start; gap: 14px; }
.stat-widget-icon {
    width: 52px; height: 52px; border-radius: 14px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.4rem; flex-shrink: 0;
}
.stat-widget-num { font-size: 2.2rem; font-weight: 900; line-height: 1; color: #0f172a; }
.stat-widget-lbl { font-size: .7rem; font-weight: 700; text-transform: uppercase; letter-spacing: .7px; color: #94a3b8; margin-top: 2px; }
.stat-widget-bar { height: 4px; }

/* ── Patient avatar ── */
.pat-avatar {
    width: 38px; height: 38px; border-radius: 10px; flex-shrink: 0;
    display: flex; align-items: center; justify-content: center;
    font-size: .85rem; font-weight: 800; color: #fff; letter-spacing: .5px;
}

/* ── Table Styling ── */
.table-custom { width: 100%; border-collapse: collapse; }
.table-custom thead th {
    background: #f8fafc; color: #64748b;
    font-size: .68rem; text-transform: uppercase; letter-spacing: 1px;
    padding: 12px 18px; border: none; font-weight: 700; white-space: nowrap;
}
.table-custom tbody tr { border-bottom: 1px solid #f1f5f9; transition: background .12s; }
.table-custom tbody tr:last-child { border-bottom: none; }
.table-custom tbody tr:hover { background: #f8fafc; }
.table-custom td { padding: 13px 18px; vertical-align: middle; }

/* ── Patient card (file d'attente) ── */
.pat-wait-card {
    display: flex; align-items: center; gap: 14px;
    padding: 13px 18px; border-bottom: 1px solid #f8fafc;
    transition: background .12s;
}
.pat-wait-card:last-child { border-bottom: none; }
.pat-wait-card:hover { background: #f8fafc; }

/* ── Status chips ── */
.chip {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 4px 11px; border-radius: 20px;
    font-size: .68rem; font-weight: 700;
}
.chip-blue   { background: #dbeafe; color: #1d4ed8; }
.chip-green  { background: #dcfce7; color: #16a34a; }
.chip-amber  { background: #fef3c7; color: #92400e; }
.chip-red    { background: #fee2e2; color: #991b1b; }
.chip-slate  { background: #f1f5f9; color: #475569; }
.chip-teal   { background: #ccfbf1; color: #0f766e; }

/* Legacy aliases */
.status-badge { display: inline-flex; align-items: center; gap: 5px; padding: 4px 11px; border-radius: 20px; font-size: .68rem; font-weight: 700; }
.status-ready    { background: #dcfce7; color: #16a34a; }
.status-waiting  { background: #dbeafe; color: #1d4ed8; }
.status-confirmed { background: #dcfce7; color: #16a34a; }
.status-pending-rdv { background: #fff7ed; color: #9a3412; }

/* ── Action Buttons ── */
.act-btn {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 6px 14px; border-radius: 8px; font-size: .75rem; font-weight: 700;
    border: none; cursor: pointer; text-decoration: none; transition: all .15s;
    white-space: nowrap;
}
.act-btn-primary { background: var(--c-blue-lt); color: var(--c-blue); }
.act-btn-primary:hover { background: #dbeafe; color: var(--c-blue); }
.act-btn-success { background: #dcfce7; color: var(--c-green); }
.act-btn-success:hover { background: #bbf7d0; }
.act-btn-danger  { background: #fee2e2; color: var(--c-red); }
.act-btn-danger:hover  { background: #fecaca; }
.act-btn-slate   { background: #f1f5f9; color: #475569; }

/* ── Priority badges ── */
.prio-p1 { background: #fee2e2; color: #991b1b; border-radius: 6px; padding: 3px 10px; font-size: .68rem; font-weight: 800; }
.prio-p2 { background: #fef3c7; color: #92400e; border-radius: 6px; padding: 3px 10px; font-size: .68rem; font-weight: 800; }
.prio-p3 { background: #dcfce7; color: #166534; border-radius: 6px; padding: 3px 10px; font-size: .68rem; font-weight: 800; }

/* ── Sidebar cards (colonne droite) ── */
.side-card {
    background: #fff; border-radius: var(--radius); box-shadow: var(--shadow);
    overflow: hidden; margin-bottom: 18px; border: 1px solid #f1f5f9;
}
.side-card-head {
    padding: 13px 18px; border-bottom: 1px solid #f1f5f9;
    display: flex; justify-content: space-between; align-items: center;
}
.side-card-title { font-size: .82rem; font-weight: 700; color: #0f172a; margin: 0; display: flex; align-items: center; gap: 7px; }

/* ── Consult card (sidebar) ── */
.consult-item {
    display: flex; align-items: center; gap: 11px;
    padding: 11px 16px; border-bottom: 1px solid #f8fafc; transition: background .12s;
}
.consult-item:last-child { border-bottom: none; }
.consult-item:hover { background: #f8fafc; }

/* ── Todo list ── */
.todo-item { display: flex; align-items: center; gap: 10px; padding: 8px 14px; border-radius: 10px; transition: background .12s; }
.todo-item:hover { background: #f8fafc; }
.todo-item .form-check-input { width: 16px; height: 16px; border-radius: 4px; cursor: pointer; border-color: #cbd5e1; }
.todo-item .form-check-input:checked { background-color: var(--c-green); border-color: var(--c-green); }

/* ── Bilan table (inline styles kept) ── */
.bilan-card { background: white; border-radius: var(--radius); border: 1px solid #f1f5f9; overflow: hidden; margin-bottom: 20px; box-shadow: var(--shadow); }
.bilan-header { padding: 16px 22px; border-bottom: 1px solid #f1f5f9; display: flex; align-items: center; justify-content: space-between; }
.bilan-table { width: 100%; border-collapse: collapse; }
.bilan-table thead th { background: #f8fafc; color: #64748b; font-size: .68rem; font-weight: 700; text-transform: uppercase; letter-spacing: .8px; padding: 11px 16px; border-bottom: 1px solid #e2e8f0; }
.bilan-table tbody tr { border-bottom: 1px solid #f1f5f9; transition: background .12s; }
.bilan-table tbody tr:last-child { border-bottom: none; }
.bilan-table tbody tr:hover { background: #fafbff; }
.bilan-table tbody td { padding: 12px 16px; font-size: .85rem; }
.bilan-table tbody tr.nouveau { background: #fffbeb; border-left: 3px solid #f59e0b; }

.badge-labo  { background: #e0f2fe; color: #0369a1; font-size: .7rem; font-weight: 700; padding: 3px 10px; border-radius: 20px; }
.badge-radio { background: #f3e8ff; color: #7c3aed; font-size: .7rem; font-weight: 700; padding: 3px 10px; border-radius: 20px; }

.statut-chip { display: inline-flex; align-items: center; gap: 5px; padding: 4px 10px; border-radius: 20px; font-size: .72rem; font-weight: 700; }
.chip-attente   { background:#fef3c7; color:#92400e; }
.chip-analyse   { background:#dbeafe; color:#1d4ed8; }
.chip-pret      { background:#dcfce7; color:#166534; }
.chip-interprete { background:#d1fae5; color:#065f46; }
.chip-default   { background:#f1f5f9; color:#475569; }

.alerte-dot { width:8px; height:8px; background:#ef4444; border-radius:50%; display:inline-block; animation: pulse-alert .8s infinite; }
@keyframes pulse-alert { 0%,100%{transform:scale(1)} 50%{transform:scale(1.4)} }

.btn-bilan { display:inline-flex; align-items:center; gap:5px; padding:5px 12px; border-radius:8px; font-size:.72rem; font-weight:700; border:none; cursor:pointer; transition:all .15s; text-decoration:none; }
.btn-b-voir    { background:#e0f2fe; color:#0369a1; } .btn-b-voir:hover    { background:#bae6fd; }
.btn-b-comment { background:#f0fdf4; color:#166534; } .btn-b-comment:hover { background:#bbf7d0; }
.btn-b-rdv     { background:#fef3c7; color:#92400e; } .btn-b-rdv:hover     { background:#fde68a; }
.btn-b-attente { background:#f1f5f9; color:#94a3b8; cursor:default; }
.patient-chip { font-size:.8rem; font-weight:700; color:#334155; }
.patient-ref  { font-size:.7rem; color:#94a3b8; }

/* Modal bilans */
.modal-bilan .modal-content { border-radius:20px; border:0; box-shadow:0 25px 50px rgba(0,0,0,.15); }
.modal-bilan .modal-header  { border-bottom:1px solid #f1f5f9; padding:18px 24px; }
.modal-bilan .modal-footer  { border-top:1px solid #f1f5f9; padding:14px 24px; }
.result-row { background:#f8fafc; border-radius:10px; padding:12px 16px; margin-bottom:8px; }
.result-val { font-size:1.4rem; font-weight:900; }
.result-val.anormal { color:#dc2626; } .result-val.normal  { color:#16a34a; }
.commentaire-item { background:#f8fafc; border-radius:10px; padding:10px 14px; margin-bottom:8px; border-left:3px solid #3b82f6; }
.commentaire-item .c-meta { font-size:.7rem; color:#94a3b8; margin-bottom:4px; }
.commentaire-item .c-text { font-size:.83rem; color:#334155; }

/* ── Dossiers partagés ── */
.dossier-item { display:flex; align-items:center; gap:12px; padding:11px 16px; border-bottom:1px solid #f8fafc; transition:background .12s; }
.dossier-item:last-child { border-bottom:none; }
.dossier-item:hover { background:#f8fafc; }

/* ── Animations ── */
.pulse-urgent { animation: pulse-red 2s infinite; }
@keyframes pulse-red { 0%,100%{opacity:1} 50%{opacity:.45} }
.btn-hosp-pulse { animation: pulse-orange 2s infinite; background: var(--c-amber) !important; color:#fff; border:none; font-weight:700; }
@keyframes pulse-orange { 0%{box-shadow:0 0 0 0 rgba(217,119,6,.4)} 70%{box-shadow:0 0 0 10px rgba(217,119,6,0)} 100%{box-shadow:0 0 0 0 rgba(217,119,6,0)} }
.crh-badge { animation: pulse-crh 2s infinite; }
@keyframes pulse-crh { 0%{box-shadow:0 0 0 0 rgba(220,38,38,.4)} 70%{box-shadow:0 0 0 8px rgba(220,38,38,0)} 100%{box-shadow:0 0 0 0 rgba(220,38,38,0)} }

/* ── Empty states ── */
.empty-state { text-align:center; padding: 36px 20px; color:#94a3b8; }
.empty-state i { font-size:2.5rem; opacity:.35; display:block; margin-bottom:10px; }
.empty-state p { font-size:.82rem; margin:0; }
</style>

<?php
/* ── Services autorisés pour le changement temporaire ── */
$_servicesAutorisesChgt = [];
try {
    $_stmtSrvChgt = $db->query("
        SELECT id, nom_service FROM services
        WHERE LOWER(nom_service) LIKE '%urgence%'
           OR (LOWER(nom_service) LIKE '%consult%' AND LOWER(nom_service) LIKE '%ext%')
        ORDER BY nom_service ASC
    ");
    $_servicesAutorisesChgt = $_stmtSrvChgt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $_e) { /* non bloquant */ }

$_estServiceTemporaire  = !empty($_SESSION['service_id_origine']);
$_nomServiceOrigine     = $_SESSION['nom_service_origine'] ?? '';
?>

<!-- TOPBAR -->
<nav class="top-nav no-print">
    <div class="top-nav-left">
        <!-- Logo -->
        <img src="<?= BASE_URL ?>public/images/logo_ordre_malte.png"
             style="height:38px;opacity:.9;" alt="HSJM">

        <!-- Séparateur vertical -->
        <div style="width:1px;height:32px;background:#e2e8f0;"></div>

        <!-- Avatar + infos médecin -->
        <div class="top-nav-avatar">
            <i class="bi bi-heart-pulse-fill" style="color:#3b82f6;font-size:1.1rem;"></i>
        </div>
        <div>
            <div class="top-nav-doctor">
                <?php
                $roleActuel = $_SESSION['user_role'] ?? '';
                $prefixe = match(true) {
                    $roleActuel === 'MEDECIN'              => 'Dr.',
                    str_contains($roleActuel, 'INFIRMIER') => 'Inf.',
                    $roleActuel === 'MAJOR'                => 'Major',
                    default                                => ''
                };
                echo htmlspecialchars($prefixe . ' ' . ($_SESSION['user_prenom'] ?? '') . ' ' . ($_SESSION['user_nom'] ?? ''));
                ?>
            </div>
            <div class="top-nav-sub">Hôpital Saint-Jean de Malte &mdash; Njombé</div>
        </div>

        <!-- Badge service (cliquable pour changer) -->
        <span id="currentServiceBadge" class="top-nav-service"
              onclick="ouvrirChangerService()" title="Changer de service"
              style="cursor:pointer;transition:.2s;<?= $_estServiceTemporaire ? 'background:#fff7ed;border-color:#fed7aa;color:#c2410c;' : '' ?>">
            <i class="bi bi-building-fill me-1" style="font-size:.75rem;opacity:.7"></i>
            <span id="currentServiceLabel"><?= htmlspecialchars($_SESSION['nom_service'] ?? 'Médecine') ?></span>
            <?php if ($_estServiceTemporaire): ?>
            <span id="tempServiceIndicator" title="Service temporaire — cliquez pour revenir à votre service d'origine"
                  style="display:inline-flex;align-items:center;gap:3px;margin-left:5px;background:#fef3c7;color:#92400e;border-radius:6px;padding:1px 5px;font-size:.65rem;font-weight:700;">
                <i class="bi bi-arrow-left-right"></i> TEMP
            </span>
            <?php else: ?>
            <span id="tempServiceIndicator" style="display:none;align-items:center;gap:3px;margin-left:5px;background:#fef3c7;color:#92400e;border-radius:6px;padding:1px 5px;font-size:.65rem;font-weight:700;">
                <i class="bi bi-arrow-left-right"></i> TEMP
            </span>
            <?php endif; ?>
        </span>
    </div>

    <div class="d-flex align-items-center gap-2">
        <div id="liveClock" class="top-nav-clock">00:00:00</div>

        <!-- Groupe icônes secondaires -->
        <div style="display:flex;align-items:center;gap:2px;background:#f8fafc;
                    border:1px solid #e2e8f0;border-radius:50px;padding:4px 6px;">
            <!-- Ordonnance -->
            <button onclick="ouvrirOrdonnancePatient()" title="Ordonnance" class="top-nav-icon-btn"
                    style="color:#7c3aed;">
                <i class="bi bi-prescription2"></i>
            </button>
            <!-- Registre -->
            <button onclick="ouvrirRegistre()" title="Registre des consultations" class="top-nav-icon-btn"
                    style="color:#16a34a;">
                <i class="bi bi-journal-text"></i>
            </button>
            <!-- Externes -->
            <a href="<?= BASE_URL ?>suivi-externe" title="Patients externes" class="top-nav-icon-btn"
               style="color:#1d4ed8;text-decoration:none;">
                <i class="bi bi-person-lines-fill"></i>
            </a>
            <!-- Rechercher -->
            <button onclick="ouvrirRecherchePatient()" title="Rechercher un patient" class="top-nav-icon-btn"
                    style="color:#374151;">
                <i class="bi bi-search"></i>
            </button>
            <?php if (!empty($isPediatrie) || !empty($isNeonat)): ?>
            <!-- Néonatologie -->
            <a href="<?= BASE_URL ?>neonatologie" title="Néonatologie" class="top-nav-icon-btn"
               style="color:#0891b2;text-decoration:none;">
                <i class="bi bi-heart-pulse-fill"></i>
            </a>
            <?php endif; ?>
            <?php if (!empty($isBlocAcces)): ?>
            <!-- Bloc Opératoire -->
            <a href="<?= BASE_URL ?>bloc" title="Bloc Opératoire" class="top-nav-icon-btn"
               style="color:#334155;text-decoration:none;">
                <i class="bi bi-scissors"></i>
            </a>
            <?php endif; ?>
        </div>

        <!-- Changer de service -->
        <button id="btnChangerService" onclick="ouvrirChangerService()" title="Changer temporairement de service" class="top-nav-btn"
                style="width:auto;padding:0 12px;gap:6px;background:<?= $_estServiceTemporaire ? '#fff7ed' : '#fff' ?>;border-color:<?= $_estServiceTemporaire ? '#fed7aa' : '#e2e8f0' ?>;color:<?= $_estServiceTemporaire ? '#c2410c' : '#374151' ?>;">
            <i class="bi bi-shuffle" style="font-size:.95rem;"></i>
            <span style="font-size:.78rem;font-weight:600;"><?= $_estServiceTemporaire ? 'Chg. service' : 'Changer service' ?></span>
        </button>

        <!-- Nouveau patient — CTA principal -->
        <button onclick="ouvrirNouveauPatient()" title="Créer un nouveau patient" class="top-nav-btn"
                style="width:auto;padding:0 14px;gap:6px;background:#1e40af;border-color:#1e40af;color:#fff;font-weight:700;">
            <i class="bi bi-person-plus-fill" style="font-size:.95rem;"></i>
            <span style="font-size:.78rem;">Nouveau patient</span>
        </button>

        <button onclick="ouvrirProfilModal()" title="Mon profil" class="top-nav-btn">
            <i class="bi bi-person-circle" style="font-size:1.1rem;"></i>
        </button>

        <a href="<?= BASE_URL ?>logout" title="Déconnexion" class="top-nav-btn danger">
            <i class="bi bi-power" style="font-size:1.1rem;"></i>
        </a>
    </div>
</nav>

<div class="dashboard-content">

    <!-- 1. WIDGETS DE PILOTAGE -->
    <div class="row g-3 mb-4">
        <?php
        $nbAttente = is_array($patients_assignes) ? count($patients_assignes) : 0;
        $nbBilans  = is_array($resultats_prets)   ? count($resultats_prets)   : 0;
        $nbTaches  = is_array($mes_taches) ? count(array_filter($mes_taches, fn($t) => !$t['is_done'])) : 0;
        $statCards = [
            ['label'=>'En attente',   'val'=>$nbAttente, 'icon'=>'bi-people-fill',    'color'=>'#1e40af','bg'=>'#eff6ff','bar'=>'#3b82f6'],
            ['label'=>'Bilans prêts', 'val'=>$nbBilans,  'icon'=>'bi-flask-fill',     'color'=>'#16a34a','bg'=>'#f0fdf4','bar'=>'#22c55e'],
            ['label'=>'Télémédecine', 'val'=>2,           'icon'=>'bi-camera-video-fill','color'=>'#dc2626','bg'=>'#fff1f2','bar'=>'#ef4444'],
            ['label'=>'À faire',      'val'=>$nbTaches,  'icon'=>'bi-check2-square',  'color'=>'#d97706','bg'=>'#fffbeb','bar'=>'#f59e0b'],
        ];
        foreach($statCards as $sc): ?>
        <div class="col-6 col-md-3">
            <div class="stat-widget">
                <div class="stat-widget-body">
                    <div class="stat-widget-icon" style="background:<?= $sc['bg'] ?>;color:<?= $sc['color'] ?>">
                        <i class="bi <?= $sc['icon'] ?>"></i>
                    </div>
                    <div>
                        <div class="stat-widget-num"><?= $sc['val'] ?></div>
                        <div class="stat-widget-lbl"><?= $sc['label'] ?></div>
                    </div>
                </div>
                <div class="stat-widget-bar" style="background:<?= $sc['bar'] ?>"></div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <div class="row">
        <!-- COLONNE GAUCHE (8/12) -->
        <div class="col-lg-8">

            <!-- 2. MON ACTIVITÉ DU MOIS -->
            <?php
            $sm         = $stats_mois ?? [];
            $moisLabel  = mb_strtoupper(date('F'), 'UTF-8') . ' ' . date('Y');
            $nbConsult  = (int)($sm['consultations']      ?? 0);
            $nbJour     = (int)($sm['consultations_jour'] ?? 0);
            $nbOrdos    = (int)($sm['ordonnances']        ?? 0);
            $nbLabo     = (int)($sm['bilans_labo']        ?? 0);
            $nbImag     = (int)($sm['bilans_imagerie']    ?? 0);
            $nbReeval   = (int)($sm['reevaluations']      ?? 0);
            $nbHosp     = (int)($sm['hospitalisations']   ?? 0);
            $nbJoursAct = (int)($sm['jours_actifs']       ?? 0);
            $semaine    = $sm['semaine'] ?? [];
            $maxSem     = max(1, max(array_column($semaine ?: [[0]], 'nb') ?: [1]));
            $pct        = min(100, $nbJoursAct > 0 ? round($nbJoursAct / 22 * 100) : 0);
            ?>
            <style>
            .act-month-card{background:#fff;border-radius:var(--radius);box-shadow:var(--shadow);margin-bottom:20px;overflow:hidden;border:1px solid #f1f5f9;}
            .act-month-header{background:linear-gradient(135deg,#1e40af 0%,#1d4ed8 60%,#2563eb 100%);padding:16px 22px;display:flex;justify-content:space-between;align-items:center;}
            .act-stat-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:1px;background:#f1f5f9;}
            .act-stat-item{background:#fff;padding:16px 18px;display:flex;align-items:center;gap:12px;transition:background .12s;}
            .act-stat-item:hover{background:#f8fafc;}
            .act-stat-icon{width:42px;height:42px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.1rem;flex-shrink:0;}
            .act-stat-num{font-size:1.6rem;font-weight:900;line-height:1;color:#0f172a;}
            .act-stat-lbl{font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.6px;color:#94a3b8;margin-top:2px;}
            .act-stat-sub{font-size:.7rem;color:#64748b;margin-top:2px;font-weight:600;}
            .sparkbar-wrap{display:flex;align-items:flex-end;gap:3px;height:36px;padding:8px 22px 0;}
            .sparkbar{flex:1;background:#dbeafe;border-radius:3px 3px 0 0;min-height:3px;position:relative;cursor:pointer;}
            .sparkbar:hover{background:#3b82f6;}
            .sparkbar.today{background:#1e40af;}
            .sparkbar .sbtip{position:absolute;bottom:calc(100% + 3px);left:50%;transform:translateX(-50%);background:#1e293b;color:#fff;font-size:.6rem;padding:2px 5px;border-radius:4px;white-space:nowrap;display:none;z-index:10;}
            .sparkbar:hover .sbtip{display:block;}
            .act-footer{padding:10px 22px;background:#f8fafc;border-top:1px solid #f1f5f9;display:flex;gap:24px;flex-wrap:wrap;}
            </style>

            <div class="act-month-card">
                <!-- En-tête gradient -->
                <div class="act-month-header">
                    <div class="d-flex align-items-center gap-3">
                        <div style="width:40px;height:40px;border-radius:12px;background:rgba(255,255,255,.15);display:flex;align-items:center;justify-content:center;">
                            <i class="bi bi-bar-chart-fill" style="color:#93c5fd;font-size:1.2rem;"></i>
                        </div>
                        <div>
                            <div class="fw-bold text-white" style="font-size:.95rem;">Mon Activité du Mois</div>
                            <div style="font-size:.72rem;color:#93c5fd;font-weight:600;"><?= $moisLabel ?></div>
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-3">
                        <!-- Barre jours actifs -->
                        <div style="text-align:right;">
                            <div style="font-size:.65rem;color:#93c5fd;font-weight:700;text-transform:uppercase;letter-spacing:.5px;margin-bottom:4px;">Jours actifs</div>
                            <div class="d-flex align-items-center gap-2">
                                <div style="width:90px;height:6px;background:rgba(255,255,255,.2);border-radius:3px;overflow:hidden;">
                                    <div style="height:100%;width:<?= $pct ?>%;background:#34d399;border-radius:3px;"></div>
                                </div>
                                <span style="color:#fff;font-weight:800;font-size:.82rem;"><?= $nbJoursAct ?>j</span>
                            </div>
                        </div>
                        <!-- Badge aujourd'hui -->
                        <?php if ($nbJour > 0): ?>
                        <div style="background:rgba(255,255,255,.15);border:1px solid rgba(255,255,255,.25);border-radius:10px;padding:7px 14px;text-align:center;">
                            <div style="font-size:1.5rem;font-weight:900;color:#fff;line-height:1;"><?= $nbJour ?></div>
                            <div style="font-size:.6rem;color:#93c5fd;font-weight:700;text-transform:uppercase;letter-spacing:.5px;">Auj.</div>
                        </div>
                        <?php else: ?>
                        <div style="background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.15);border-radius:10px;padding:7px 14px;text-align:center;">
                            <div style="font-size:1.5rem;font-weight:900;color:rgba(255,255,255,.4);line-height:1;">0</div>
                            <div style="font-size:.6rem;color:#93c5fd;font-weight:700;text-transform:uppercase;letter-spacing:.5px;">Auj.</div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Sparkline 7 jours -->
                <?php if (!empty($semaine)): ?>
                <div style="padding:8px 22px 0;background:#fafbff;">
                    <div style="font-size:.63rem;color:#94a3b8;font-weight:700;text-transform:uppercase;letter-spacing:.5px;margin-bottom:4px;">7 derniers jours</div>
                    <div class="sparkbar-wrap" style="padding:0;background:transparent;">
                        <?php foreach ($semaine as $day):
                            $isToday = ($day['date'] === date('Y-m-d'));
                            $h = $maxSem > 0 ? max(3, round($day['nb'] / $maxSem * 36)) : 3;
                            $dLabel = date('d/m', strtotime($day['date']));
                            $jLabel = ['Sun'=>'Dim','Mon'=>'Lun','Tue'=>'Mar','Wed'=>'Mer','Thu'=>'Jeu','Fri'=>'Ven','Sat'=>'Sam'][date('D', strtotime($day['date']))] ?? '';
                        ?>
                        <div class="d-flex flex-column align-items-center" style="flex:1;gap:2px;">
                            <div class="sparkbar <?= $isToday ? 'today' : '' ?>" style="width:100%;height:<?= $h ?>px;">
                                <div class="sbtip"><?= $dLabel ?> : <?= $day['nb'] ?></div>
                            </div>
                            <div style="font-size:.6rem;color:<?= $isToday ? '#1e40af' : '#94a3b8' ?>;font-weight:<?= $isToday ? '800' : '600' ?>;"><?= $jLabel ?></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div style="height:1px;background:#f1f5f9;margin-top:10px;"></div>
                <?php endif; ?>

                <!-- Grille des 6 indicateurs -->
                <div class="act-stat-grid">

                    <div class="act-stat-item">
                        <div class="act-stat-icon" style="background:#eff6ff;color:#1e40af;"><i class="bi bi-stethoscope"></i></div>
                        <div>
                            <div class="act-stat-num"><?= $nbConsult ?></div>
                            <div class="act-stat-lbl">Consultations</div>
                            <?php if ($nbJour > 0): ?>
                            <div class="act-stat-sub" style="color:#16a34a;">+<?= $nbJour ?> aujourd'hui</div>
                            <?php else: ?>
                            <div class="act-stat-sub">ce mois</div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="act-stat-item">
                        <div class="act-stat-icon" style="background:#faf5ff;color:#7c3aed;"><i class="bi bi-prescription2"></i></div>
                        <div>
                            <div class="act-stat-num"><?= $nbOrdos ?></div>
                            <div class="act-stat-lbl">Ordonnances</div>
                            <div class="act-stat-sub"><?= $nbConsult > 0 ? round($nbOrdos / $nbConsult * 100) . '% des consult.' : 'prescrites' ?></div>
                        </div>
                    </div>

                    <div class="act-stat-item">
                        <div class="act-stat-icon" style="background:#f0fdf4;color:#16a34a;"><i class="bi bi-flask-fill"></i></div>
                        <div>
                            <div class="act-stat-num"><?= $nbLabo ?></div>
                            <div class="act-stat-lbl">Bilans Labo</div>
                            <div class="act-stat-sub"><?= $nbConsult > 0 ? round($nbLabo / $nbConsult * 100) . '% des consult.' : 'demandés' ?></div>
                        </div>
                    </div>

                    <div class="act-stat-item">
                        <div class="act-stat-icon" style="background:#fffbeb;color:#d97706;"><i class="bi bi-radioactive"></i></div>
                        <div>
                            <div class="act-stat-num"><?= $nbImag ?></div>
                            <div class="act-stat-lbl">Imagerie</div>
                            <div class="act-stat-sub">Radio / Écho</div>
                        </div>
                    </div>

                    <div class="act-stat-item">
                        <div class="act-stat-icon" style="background:#ecfdf5;color:#0d9488;"><i class="bi bi-clipboard2-pulse-fill"></i></div>
                        <div>
                            <div class="act-stat-num"><?= $nbReeval ?></div>
                            <div class="act-stat-lbl">Réévaluations</div>
                            <div class="act-stat-sub">Patients hospitalisés</div>
                        </div>
                    </div>

                    <div class="act-stat-item">
                        <div class="act-stat-icon" style="background:#fff1f2;color:#dc2626;"><i class="bi bi-hospital-fill"></i></div>
                        <div>
                            <div class="act-stat-num"><?= $nbHosp ?></div>
                            <div class="act-stat-lbl">Hospitalisations</div>
                            <div class="act-stat-sub">Admissions ce mois</div>
                        </div>
                    </div>

                </div><!-- /.act-stat-grid -->

                <!-- Pied récapitulatif -->
                <?php if ($nbConsult > 0 || $nbOrdos > 0): ?>
                <div class="act-footer">
                    <?php if ($nbConsult > 0 && $nbJoursAct > 0): ?>
                    <div style="font-size:.72rem;color:#64748b;font-weight:600;">
                        <i class="bi bi-graph-up-arrow me-1 text-primary"></i>
                        Moy. <?= round($nbConsult / $nbJoursAct, 1) ?> consultation<?= $nbConsult / $nbJoursAct > 1 ? 's' : '' ?> / jour actif
                    </div>
                    <?php endif; ?>
                    <?php if (($nbLabo + $nbImag) > 0): ?>
                    <div style="font-size:.72rem;color:#64748b;font-weight:600;">
                        <i class="bi bi-activity me-1 text-success"></i>
                        <?= $nbLabo + $nbImag ?> bilan<?= ($nbLabo + $nbImag) > 1 ? 's' : '' ?> demandé<?= ($nbLabo + $nbImag) > 1 ? 's' : '' ?> au total
                    </div>
                    <?php endif; ?>
                    <?php if ($nbReeval > 0): ?>
                    <div style="font-size:.72rem;color:#64748b;font-weight:600;">
                        <i class="bi bi-clipboard2-pulse me-1 text-teal"></i>
                        <?= $nbReeval ?> réévaluation<?= $nbReeval > 1 ? 's' : '' ?> effectuée<?= $nbReeval > 1 ? 's' : '' ?>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- 3. FILE D'ATTENTE -->
            <div class="med-card" id="cardAttenteSection">
                <div class="card-header-custom" style="flex-wrap:wrap;gap:8px;">
                    <h5 class="section-title" style="flex:1;min-width:0;">
                        <span class="s-icon" style="background:#eff6ff;color:#1e40af"><i class="bi bi-person-lines-fill"></i></span>
                        Patients en salle d'attente
                    </h5>
                    <div style="display:flex;align-items:center;gap:8px;">
                        <!-- Filtre date -->
                        <div style="display:flex;align-items:center;gap:6px;background:#f8fafc;border:1.5px solid #e2e8f0;
                                    border-radius:10px;padding:4px 10px;">
                            <i class="bi bi-calendar3" style="color:#1e40af;font-size:.8rem;"></i>
                            <input type="date"
                                   id="attenteFiltrDate"
                                   value="<?= date('Y-m-d') ?>"
                                   max="<?= date('Y-m-d') ?>"
                                   style="border:none;background:transparent;font-size:.78rem;font-weight:600;
                                          color:#1e293b;outline:none;cursor:pointer;width:120px;"
                                   onchange="attenteChargerDate(this.value)">
                        </div>
                        <span id="attenteCountBadge" class="chip <?= (is_array($patients_assignes) && count($patients_assignes) > 0) ? 'chip-blue' : 'chip-slate' ?>">
                            <?php $nbWait = is_array($patients_assignes) ? count($patients_assignes) : 0; ?>
                            <?= $nbWait ?> patient<?= $nbWait != 1 ? 's' : '' ?>
                        </span>
                    </div>
                </div>

                <!-- Liste des patients — rendue côté PHP pour aujourd'hui, mise à jour par JS pour les autres dates -->
                <div id="attenteListeContainer">
                <?php if(!empty($patients_assignes)): foreach($patients_assignes as $p):
                    $ini2  = strtoupper(substr($p['nom'],0,1).substr($p['prenom']??'',0,1));
                    $prio  = $p['niveau_gravite'] ?? 'P3';
                    $prioCls = str_contains($prio,'P1') ? 'prio-p1' : (str_contains($prio,'P2') ? 'prio-p2' : 'prio-p3');
                    $avatarGrad = str_contains($prio,'P1') ? 'linear-gradient(135deg,#dc2626,#f87171)' : (str_contains($prio,'P2') ? 'linear-gradient(135deg,#d97706,#fbbf24)' : 'linear-gradient(135deg,#1e40af,#60a5fa)');
                ?>
                <div class="pat-wait-card">
                    <div class="pat-avatar" style="background:<?= $avatarGrad ?>"><?= $ini2 ?></div>
                    <div class="flex-grow-1">
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <span class="fw-bold" style="font-size:.85rem"><?= strtoupper($p['nom']) ?> <?= $p['prenom'] ?></span>
                            <span class="<?= $prioCls ?>"><?= $prio ?></span>
                        </div>
                        <div style="font-size:.73rem;color:#64748b;margin-top:2px">
                            <?= $p['dossier_numero'] ?> · <?= htmlspecialchars(mb_substr($p['motif_plainte'] ?? 'Consultation',0,50)) ?>
                        </div>
                    </div>
                    <?php
                    $patFemmeEnceinte = !empty($p['femme_enceinte']);
                    $needsClaim = $patFemmeEnceinte && (empty($p['medecin_id']) || (int)$p['medecin_id'] === 0);
                    $useGyneco  = ($patFemmeEnceinte && !$needsClaim) || !empty($isMaterniteService);
                    ?>
                    <?php if ($needsClaim): ?>
                    <span class="chip me-2" style="background:#fce7f3;color:#db2777;"><i class="bi bi-heart-pulse-fill me-1"></i>Maternité</span>
                    <button class="act-btn" style="background:#fce7f3;color:#9d174d;font-weight:700;"
                            onclick="prendrePatienteMat(<?= (int)$p['id'] ?>, '<?= addslashes($p['nom'].' '.$p['prenom']) ?>', this)">
                        <i class="bi bi-person-heart-fill me-1"></i>Prendre en charge
                    </button>
                    <?php elseif ($useGyneco): ?>
                    <span class="chip me-2" style="background:#fce7f3;color:#db2777;"><i class="bi bi-heart-pulse-fill me-1"></i>Attente</span>
                    <a href="<?= BASE_URL ?>formulaire/creer/consultation-gyneco/<?= (int)$p['id'] ?>"
                       class="act-btn" style="background:#fce7f3;color:#9d174d;font-weight:700;">
                        <i class="bi bi-stethoscope me-1"></i>Consulter
                    </a>
                    <?php elseif (!empty($isPediatrie)): ?>
                    <?php if (!empty($p['parametres_requis'])): ?>
                    <span class="chip me-2" style="background:#fef9c3;color:#854d0e;" title="Constantes vitales à prendre">
                        <i class="bi bi-thermometer-half me-1"></i>⏳ Paramètres à prendre
                    </span>
                    <?php elseif (($p['statut_parcours'] ?? '') === 'PARAMETRES'): ?>
                    <span class="chip me-2" style="background:#fef9c3;color:#854d0e;">
                        <i class="bi bi-thermometer-half me-1"></i>En paramètres
                    </span>
                    <?php else: ?>
                    <span class="chip chip-blue me-2"><i class="bi bi-clock me-1"></i>Attente</span>
                    <?php endif; ?>
                    <a href="<?= BASE_URL ?>consultation-ped/formulaire/<?= (int)$p['id'] ?>" class="act-btn act-btn-primary">
                        <i class="bi bi-stethoscope"></i> Consulter
                    </a>
                    <?php else: ?>
                    <?php if (!empty($p['parametres_requis'])): ?>
                    <span class="chip me-2" style="background:#fef9c3;color:#854d0e;" title="Constantes vitales à prendre">
                        <i class="bi bi-thermometer-half me-1"></i>⏳ Paramètres à prendre
                    </span>
                    <?php else: ?>
                    <span class="chip chip-blue me-2"><i class="bi bi-clock me-1"></i> Attente</span>
                    <?php endif; ?>
                    <a href="<?= BASE_URL ?>consultation/formulaire?patient_id=<?= $p['id'] ?>&type=EXTERNE&etape=1" class="act-btn act-btn-primary">
                        <i class="bi bi-stethoscope"></i> Consulter
                    </a>
                    <?php endif; ?>
                </div>
                <?php endforeach; else: ?>
                <div class="empty-state">
                    <i class="bi bi-check2-circle text-success"></i>
                    <p>Aucun patient en attente aujourd'hui.</p>
                </div>
                <?php endif; ?>
                </div><!-- /attenteListeContainer -->
            </div>

            <script>
            (function() {
                const BASE        = '<?= BASE_URL ?>';
                const SERVICE_ID  = <?= (int)($serviceId ?? 0) ?>;
                const TODAY       = '<?= date('Y-m-d') ?>';
                const isPediatrie = <?= !empty($isPediatrie) ? 'true' : 'false' ?>;
                const isMat       = <?= !empty($isMaterniteService) ? 'true' : 'false' ?>;

                window.attenteChargerDate = function(date) {
                    const container = document.getElementById('attenteListeContainer');
                    const badge     = document.getElementById('attenteCountBadge');

                    // Spinner
                    container.innerHTML = `<div class="text-center py-4 text-muted" style="font-size:.85rem;">
                        <span class="spinner-border spinner-border-sm me-2"></span>Chargement…</div>`;

                    fetch(`${BASE}dashboard/attente-par-date?date=${date}&service_id=${SERVICE_ID}`)
                        .then(r => {
                            if (!r.ok) throw new Error('HTTP ' + r.status);
                            return r.json();
                        })
                        .then(data => {
                            if (!data.success) {
                                container.innerHTML = `<div class="empty-state"><i class="bi bi-exclamation-triangle text-warning"></i><p style="color:#dc2626;font-size:.8rem;">${data.message || 'Erreur de chargement.'}</p></div>`;
                                return;
                            }

                            const nb = data.count || 0;
                            badge.textContent = nb + ' patient' + (nb !== 1 ? 's' : '');
                            badge.className   = 'chip ' + (nb > 0 ? 'chip-blue' : 'chip-slate');

                            if (nb === 0) {
                                const msg = data.is_today
                                    ? 'Aucun patient en attente aujourd\'hui.'
                                    : 'Aucune consultation enregistrée ce jour.';
                                container.innerHTML = `<div class="empty-state">
                                    <i class="bi bi-${data.is_today ? 'check2-circle text-success' : 'calendar-x text-muted'}"></i>
                                    <p>${msg}</p></div>`;
                                return;
                            }

                            let html = '';
                            data.patients.forEach(p => {
                                const ini  = (p.nom?.[0] || 'X').toUpperCase() + (p.prenom?.[0] || '').toUpperCase();
                                const prio = p.niveau_gravite || 'P3';
                                const prioCls = prio.includes('P1') ? 'prio-p1' : (prio.includes('P2') ? 'prio-p2' : 'prio-p3');
                                const grad = prio.includes('P1')
                                    ? 'linear-gradient(135deg,#dc2626,#f87171)'
                                    : (prio.includes('P2') ? 'linear-gradient(135deg,#d97706,#fbbf24)' : 'linear-gradient(135deg,#1e40af,#60a5fa)');
                                const motif  = (p.motif_plainte || 'Consultation').substring(0, 50);
                                const heure  = p.heure_consultation ? ` · ${p.heure_consultation.substring(0,5)}` : '';
                                const isPreg = p.femme_enceinte == 1;
                                const needClaim = isPreg && (!p.medecin_id || p.medecin_id == 0);
                                const useGyn = (isPreg && !needClaim) || isMat;

                                let actionHtml = '';
                                if (!data.is_today) {
                                    // Date passée : bouton dossier uniquement
                                    actionHtml = `<a href="${BASE}patients/dossier/${p.id}" class="act-btn act-btn-primary">
                                        <i class="bi bi-folder2-open"></i> Dossier</a>`;
                                } else if (needClaim) {
                                    actionHtml = `<span class="chip me-2" style="background:#fce7f3;color:#db2777;"><i class="bi bi-heart-pulse-fill me-1"></i>Maternité</span>
                                        <button class="act-btn" style="background:#fce7f3;color:#9d174d;font-weight:700;"
                                            onclick="prendrePatienteMat(${p.id}, '${(p.nom+' '+p.prenom).replace(/'/g,"\\'")}', this)">
                                            <i class="bi bi-person-heart-fill me-1"></i>Prendre en charge</button>`;
                                } else if (useGyn) {
                                    actionHtml = `<span class="chip me-2" style="background:#fce7f3;color:#db2777;"><i class="bi bi-heart-pulse-fill me-1"></i>Attente</span>
                                        <a href="${BASE}formulaire/creer/consultation-gyneco/${p.id}" class="act-btn" style="background:#fce7f3;color:#9d174d;font-weight:700;">
                                            <i class="bi bi-stethoscope me-1"></i>Consulter</a>`;
                                } else if (isPediatrie) {
                                    const pedBadge = p.parametres_requis == 1
                                        ? `<span class="chip me-2" style="background:#fef9c3;color:#854d0e;" title="Constantes vitales à prendre"><i class="bi bi-thermometer-half me-1"></i>⏳ Paramètres à prendre</span>`
                                        : `<span class="chip chip-blue me-2"><i class="bi bi-clock"></i> Attente</span>`;
                                    actionHtml = `${pedBadge}
                                        <a href="${BASE}consultation-ped/formulaire/${p.id}" class="act-btn act-btn-primary">
                                            <i class="bi bi-stethoscope"></i> Consulter</a>`;
                                } else {
                                    const stdBadge = p.parametres_requis == 1
                                        ? `<span class="chip me-2" style="background:#fef9c3;color:#854d0e;" title="Constantes vitales à prendre"><i class="bi bi-thermometer-half me-1"></i>⏳ Paramètres à prendre</span>`
                                        : `<span class="chip chip-blue me-2"><i class="bi bi-clock"></i> Attente</span>`;
                                    actionHtml = `${stdBadge}
                                        <a href="${BASE}consultation/formulaire?patient_id=${p.id}&type=EXTERNE&etape=1" class="act-btn act-btn-primary">
                                            <i class="bi bi-stethoscope"></i> Consulter</a>`;
                                }

                                html += `<div class="pat-wait-card">
                                    <div class="pat-avatar" style="background:${grad}">${ini}</div>
                                    <div class="flex-grow-1">
                                        <div class="d-flex align-items-center gap-2 flex-wrap">
                                            <span class="fw-bold" style="font-size:.85rem">${p.nom.toUpperCase()} ${p.prenom || ''}</span>
                                            ${data.is_today ? `<span class="${prioCls}">${prio}</span>` : ''}
                                        </div>
                                        <div style="font-size:.73rem;color:#64748b;margin-top:2px">
                                            ${p.dossier_numero}${heure} · ${motif}
                                        </div>
                                    </div>
                                    ${actionHtml}
                                </div>`;
                            });
                            container.innerHTML = html;
                        })
                        .catch(err => {
                            container.innerHTML = `<div class="empty-state"><i class="bi bi-wifi-off text-muted"></i><p>Erreur réseau : ${err.message}</p></div>`;
                        });
                };

                // Chargement initial automatique
                document.addEventListener('DOMContentLoaded', function() {
                    attenteChargerDate(TODAY);
                });
            })();
            </script>

            <!-- ================= SECTION MES PATIENTS DU SERVICE ================= -->
<div class="med-card">
    <div class="card-header-custom">
        <h5 class="section-title">
            <span class="s-icon" style="background:#eff6ff;color:#1e40af"><i class="bi bi-people-fill"></i></span>
            Mes Patients du Service
        </h5>
        <a href="<?= BASE_URL ?>patients/mes-patients" class="act-btn act-btn-slate" style="font-size:.72rem">
            <i class="bi bi-arrow-right"></i> Voir plus
        </a>
    </div>
    <div class="table-responsive">
        <table class="table-custom w-100">
            <thead><tr><th>Patient</th><th>Statut</th><th>Dernière hospit.</th><th style="text-align:right">Actions</th></tr></thead>
            <tbody>
            <?php if(!empty($mes_patients_service)): foreach($mes_patients_service as $mp):
                $sortieDate    = $mp['date_sortie_effective'] ? date('d/m/Y', strtotime($mp['date_sortie_effective'])) : null;
                $isHospEnCours = ($mp['statut_hosp'] === 'en_cours');
                $isSorti       = ($mp['statut'] === 'SORTIE' || $mp['statut_hosp'] === 'termine');
                $ini3 = strtoupper(substr($mp['nom'],0,1).substr($mp['prenom']??'',0,1));
                $avatarBg = $isHospEnCours ? 'linear-gradient(135deg,#1e40af,#60a5fa)' : ($isSorti ? 'linear-gradient(135deg,#64748b,#94a3b8)' : 'linear-gradient(135deg,#16a34a,#4ade80)');
            ?>
                <tr>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <div class="pat-avatar" style="background:<?= $avatarBg ?>"><?= $ini3 ?></div>
                            <div>
                                <div class="fw-bold" style="font-size:.84rem"><?= strtoupper($mp['nom']) ?> <?= $mp['prenom'] ?></div>
                                <div style="font-size:.7rem;color:#94a3b8"><?= $mp['dossier_numero'] ?></div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <?php if($isHospEnCours): ?>
                            <span class="chip chip-blue"><i class="bi bi-hospital"></i> Hospitalisé</span>
                        <?php elseif($isSorti): ?>
                            <span class="chip chip-slate"><i class="bi bi-box-arrow-right"></i> Sorti</span>
                        <?php else: ?>
                            <span class="chip chip-green"><i class="bi bi-person-check"></i> Externe</span>
                        <?php endif; ?>
                    </td>
                    <td style="font-size:.78rem;color:#64748b">
                        <?php if($sortieDate): ?>
                            <i class="bi bi-box-arrow-right me-1"></i>Sorti le <?= $sortieDate ?>
                        <?php elseif($isHospEnCours): ?>
                            <span style="color:#16a34a;font-weight:700"><i class="bi bi-activity me-1"></i>En cours</span>
                        <?php else: ?>—<?php endif; ?>
                    </td>
                    <td style="text-align:right">
                        <div class="d-flex gap-1 justify-content-end">
                            <a href="<?= BASE_URL ?>patients/dossier/<?= $mp['id'] ?>" class="act-btn act-btn-primary"><i class="bi bi-folder2-open"></i> Dossier</a>
                            <?php if ($mp['hosp_id'] && $isSorti): ?>
                            <a href="<?= BASE_URL ?>formulaire/crh/<?= $mp['hosp_id'] ?>" class="act-btn act-btn-danger"><i class="bi bi-pencil-square"></i> CRH</a>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
            <?php endforeach; else: ?>
                <tr><td colspan="4"><div class="empty-state"><i class="bi bi-person-x"></i><p>Aucun patient dans votre service.</p></div></td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ================= SECTION PATIENTS HOSPITALISÉS ================= -->
<div class="med-card">
    <div class="card-header-custom">
        <h5 class="section-title">
            <span class="s-icon" style="background:#eff6ff;color:#1e40af"><i class="bi bi-hospital-fill"></i></span>
            Patients Hospitalisés du Service
        </h5>
        <?php $nbHosp2 = count($patients_hospitalises ?? []); ?>
        <span class="chip <?= $nbHosp2 > 0 ? 'chip-blue' : 'chip-slate' ?>"><?= $nbHosp2 ?> patient<?= $nbHosp2 != 1 ? 's' : '' ?></span>
    </div>
    <div class="table-responsive">
        <table class="table-custom w-100">
            <thead><tr><th>Chambre / Lit</th><th>Patient</th><th>Date d'entrée</th><th style="text-align:right">Actions</th></tr></thead>
            <tbody>
            <?php if(!empty($patients_hospitalises)): foreach($patients_hospitalises as $hosp):
                $ini4 = strtoupper(substr($hosp['nom'],0,1).substr($hosp['prenom']??'',0,1));
            ?>
                <tr>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <div style="width:36px;height:36px;border-radius:8px;background:#eff6ff;color:#1e40af;display:flex;align-items:center;justify-content:center;flex-shrink:0">
                                <i class="bi bi-door-closed-fill"></i>
                            </div>
                            <div>
                                <div class="fw-bold" style="font-size:.83rem">Ch. <?= htmlspecialchars($hosp['nom_chambre']) ?></div>
                                <div style="font-size:.7rem;color:#94a3b8">Lit : <?= htmlspecialchars($hosp['nom_lit']) ?></div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <div class="pat-avatar" style="background:linear-gradient(135deg,#1e40af,#60a5fa)"><?= $ini4 ?></div>
                            <div>
                                <div class="fw-bold" style="font-size:.83rem"><?= strtoupper($hosp['nom']) ?> <?= $hosp['prenom'] ?></div>
                                <div style="font-size:.7rem;color:#94a3b8"><?= $hosp['dossier_numero'] ?></div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <div style="font-size:.78rem;color:#64748b">Admis le</div>
                        <div class="fw-bold" style="font-size:.82rem"><?= date('d/m/Y', strtotime($hosp['date_admission'])) ?></div>
                    </td>
                    <td style="text-align:right">
                        <div class="d-flex gap-1 justify-content-end flex-wrap">
                            <button class="act-btn"
                                    style="background:#0d9488;color:#fff;"
                                    onclick="ouvrirSuiviInfirmier(<?= $hosp['id'] ?>, '<?= htmlspecialchars(addslashes(strtoupper($hosp['nom']).' '.$hosp['prenom']), ENT_QUOTES) ?>')">
                                <i class="bi bi-activity"></i> Voir suivi
                            </button>
                            <a href="<?= BASE_URL ?>patients/dossier/<?= $hosp['id'] ?>" class="act-btn act-btn-primary"><i class="bi bi-folder2-open"></i> Dossier</a>
                            <a href="<?= BASE_URL ?>hospitalisation/reevaluation/<?= $hosp['id'] ?>" class="act-btn act-btn-success"><i class="bi bi-clipboard2-pulse-fill"></i> Réévaluer</a>
                        </div>
                    </td>
                </tr>
            <?php endforeach; else: ?>
                <tr><td colspan="4"><div class="empty-state"><i class="bi bi-bed"></i><p>Aucun patient hospitalisé dans votre service actuellement.</p></div></td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ================= SECTION CRH À RÉDIGER ================= -->
<?php if (!empty($crh_en_attente)): ?>
<div class="med-card" style="border-left:4px solid #dc2626">
    <div class="card-header-custom" style="background:#fff5f5">
        <h5 class="section-title" style="color:#dc2626">
            <span class="s-icon" style="background:#fee2e2;color:#dc2626"><i class="bi bi-file-earmark-medical-fill"></i></span>
            Comptes-rendus d'hospitalisation à rédiger
        </h5>
        <span class="chip chip-red crh-badge"><?= count($crh_en_attente) ?> en attente</span>
    </div>
    <div class="table-responsive">
        <table class="table table-custom align-middle">
            <thead>
                <tr>
                    <th>Patient</th>
                    <th>Admis le</th>
                    <th>Sorti le</th>
                    <th>Motif</th>
                    <th class="text-end">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($crh_en_attente as $crh): ?>
                    <tr>
                        <td>
                            <strong><?= strtoupper($crh['nom']) ?> <?= $crh['prenom'] ?></strong><br>
                            <small class="text-muted"><?= $crh['dossier_numero'] ?></small>
                        </td>
                        <td><small><?= date('d/m/Y', strtotime($crh['date_admission'])) ?></small></td>
                        <td><small class="text-danger fw-bold"><?= date('d/m/Y', strtotime($crh['date_sortie_effective'])) ?></small></td>
                        <td><small class="text-muted"><?= htmlspecialchars(substr($crh['motif_hospitalisation'] ?? '—', 0, 50)) ?></small></td>
                        <td class="text-end">
                            <a href="<?= BASE_URL ?>formulaire/crh/<?= $crh['hosp_id'] ?>"
                               class="btn btn-sm btn-danger rounded-pill px-3 fw-bold">
                                <i class="bi bi-pencil-square me-1"></i> Rédiger CRH
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<!-- ══ SUIVI DES BILANS DEMANDÉS ══ -->
<style>
    .bilan-card { background: white; border-radius: 16px; border: 1px solid #e2e8f0; overflow: hidden; margin-bottom: 24px; box-shadow: 0 2px 8px rgba(0,0,0,.04); }
    .bilan-header { padding: 16px 20px; border-bottom: 1px solid #f1f5f9; display: flex; align-items: center; justify-content: space-between; }
    .bilan-table { width: 100%; border-collapse: collapse; }
    .bilan-table thead th { background: #f8fafc; color: #64748b; font-size: .7rem; font-weight: 700; text-transform: uppercase; letter-spacing: .8px; padding: 10px 16px; border-bottom: 1px solid #e2e8f0; }
    .bilan-table tbody tr { border-bottom: 1px solid #f1f5f9; transition: background .15s; }
    .bilan-table tbody tr:hover { background: #fafbff; }
    .bilan-table tbody td { padding: 12px 16px; font-size: .88rem; }
    .bilan-table tbody tr.nouveau { background: #fffbeb; border-left: 3px solid #f59e0b; }

    .badge-labo  { background: #e0f2fe; color: #0369a1; font-size: .72rem; font-weight: 700; padding: 3px 10px; border-radius: 20px; }
    .badge-radio { background: #f3e8ff; color: #7c3aed; font-size: .72rem; font-weight: 700; padding: 3px 10px; border-radius: 20px; }

    .statut-chip { display: inline-flex; align-items: center; gap: 5px; padding: 4px 10px; border-radius: 20px; font-size: .75rem; font-weight: 700; }
    .chip-attente  { background:#fef3c7; color:#92400e; }
    .chip-analyse  { background:#dbeafe; color:#1d4ed8; }
    .chip-pret     { background:#dcfce7; color:#166534; }
    .chip-interprete { background:#d1fae5; color:#065f46; }
    .chip-default  { background:#f1f5f9; color:#475569; }

    .alerte-dot { width:8px; height:8px; background:#ef4444; border-radius:50%; display:inline-block; animation: pulse-alert .8s infinite; }
    @keyframes pulse-alert { 0%,100%{transform:scale(1)} 50%{transform:scale(1.4)} }

    .btn-bilan { display:inline-flex; align-items:center; gap:5px; padding:5px 12px; border-radius:8px; font-size:.75rem; font-weight:700; border:none; cursor:pointer; transition:all .15s; text-decoration:none; }
    .btn-b-voir    { background:#e0f2fe; color:#0369a1; } .btn-b-voir:hover    { background:#bae6fd; color:#0369a1; }
    .btn-b-comment { background:#f0fdf4; color:#166534; } .btn-b-comment:hover { background:#bbf7d0; color:#166534; }
    .btn-b-rdv     { background:#fef3c7; color:#92400e; } .btn-b-rdv:hover     { background:#fde68a; color:#92400e; }
    .btn-b-attente { background:#f1f5f9; color:#94a3b8; cursor:default; }

    .patient-chip { font-size:.8rem; font-weight:700; color:#334155; }
    .patient-ref  { font-size:.72rem; color:#94a3b8; }

    /* Modals */
    .modal-bilan .modal-content { border-radius:20px; border:0; box-shadow:0 25px 50px rgba(0,0,0,.15); }
    .modal-bilan .modal-header  { border-bottom:1px solid #f1f5f9; padding:20px 24px; }
    .modal-bilan .modal-footer  { border-top:1px solid #f1f5f9; padding:16px 24px; }
    .result-row { background:#f8fafc; border-radius:10px; padding:12px 16px; margin-bottom:8px; }
    .result-val { font-size:1.4rem; font-weight:900; }
    .result-val.anormal { color:#dc2626; }
    .result-val.normal  { color:#16a34a; }
    .commentaire-item { background:#f8fafc; border-radius:10px; padding:10px 14px; margin-bottom:8px; border-left:3px solid #3b82f6; }
    .commentaire-item .c-meta { font-size:.72rem; color:#94a3b8; margin-bottom:4px; }
    .commentaire-item .c-text { font-size:.85rem; color:#334155; }
</style>

<div class="bilan-card">
    <div class="bilan-header">
<h5 class="section-title">
            <span class="s-icon" style="background:#fef3c7;color:#d97706"><i class="bi bi-clipboard2-pulse-fill"></i></span>
            Suivi des Bilans Demandés
            <?php if (!empty($nouveaux_resultats) && $nouveaux_resultats > 0): ?>
                <span class="chip chip-red ms-2"><?= $nouveaux_resultats ?> nouveau<?= $nouveaux_resultats > 1 ? 'x' : '' ?></span>
            <?php endif; ?>
        </h5>
        <span class="chip chip-slate"><?= count($suivi_bilans ?? []) ?> en cours</span>
    </div>

    <div class="table-responsive">
        <table class="bilan-table">
            <thead>
                <tr>
                    <th>Patient</th>
                    <th>Type</th>
                    <th>Examen / Zone</th>
                    <th>Statut</th>
                    <th>Résultat</th>
                    <th style="text-align:right">Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php if (!empty($suivi_bilans)): foreach ($suivi_bilans as $b):
                $isDisponible = in_array($b['statut'], ['RESULTATS_PRETS','VALIDES','interprete','termine']);
                $isNouveau    = $isDisponible && (int)($b['nb_commentaires'] ?? 0) === 0;
                $isAnormal    = !empty($b['anormal']);
                $patId = (int)($b['patient_id'] ?? 0);
                $patNom = strtoupper($b['patient_nom'] ?? '?') . ' ' . ($b['patient_prenom'] ?? '');
                $patRef = htmlspecialchars($b['dossier_numero'] ?? '');

                // Chip statut
                $chips = [
                    'EN_ATTENTE'             => ['chip-attente',   'bi-clock-history',      'En attente'],
                    'PRELEVEMENTS_EFFECTUES' => ['chip-analyse',   'bi-droplet-half',        'Prélevé'],
                    'EN_ANALYSE'             => ['chip-analyse',   'bi-gear-wide-connected', 'En analyse'],
                    'RESULTATS_PRETS'        => ['chip-pret',      'bi-check-circle-fill',   'Résultat prêt'],
                    'VALIDES'                => ['chip-pret',      'bi-patch-check-fill',    'Validé'],
                    'en_cours'               => ['chip-analyse',   'bi-gear-wide-connected', 'En cours'],
                    'termine'                => ['chip-interprete','bi-check-circle-fill',   'Terminé'],
                    'interprete'             => ['chip-interprete','bi-star-fill',           'Interprété'],
                ];
                [$cCls, $cIcon, $cTxt] = $chips[$b['statut']] ?? ['chip-default','bi-dash','Inconnu'];
            ?>
                <tr class="<?= $isNouveau ? 'nouveau' : '' ?>">
                    <td>
                        <?php if ($isNouveau): ?><span class="alerte-dot me-1"></span><?php endif; ?>
                        <span class="patient-chip"><?= htmlspecialchars($patNom) ?></span><br>
                        <span class="patient-ref"><?= $patRef ?></span>
                    </td>
                    <td>
                        <?php if ($b['type'] === 'Labo'): ?>
                            <span class="badge-labo"><i class="bi bi-droplet me-1"></i>Labo</span>
                        <?php else: ?>
                            <span class="badge-radio"><i class="bi bi-radioactive me-1"></i>Radio</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <strong><?= htmlspecialchars($b['label']) ?></strong>
                        <?php if ((int)($b['nb_commentaires'] ?? 0) > 0): ?>
                            <br><small style="color:#64748b"><i class="bi bi-chat-text me-1"></i><?= $b['nb_commentaires'] ?> commentaire(s)</small>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="statut-chip <?= $cCls ?>">
                            <i class="bi <?= $cIcon ?>"></i> <?= $cTxt ?>
                        </span>
                    </td>
                    <td>
                        <?php if ($isDisponible && $b['type'] === 'Labo' && !empty($b['valeur_numerique'])): ?>
                            <span class="fw-bold <?= $isAnormal ? 'text-danger' : 'text-success' ?>">
                                <?= $b['valeur_numerique'] ?> <?= htmlspecialchars($b['unite'] ?? '') ?>
                            </span>
                            <?php if ($isAnormal): ?><i class="bi bi-exclamation-triangle-fill text-danger ms-1"></i><?php endif; ?>
                        <?php elseif ($isDisponible): ?>
                            <span style="color:#16a34a;font-size:.78rem;font-weight:700"><i class="bi bi-check2 me-1"></i>Disponible</span>
                        <?php else: ?>
                            <span style="color:#94a3b8;font-size:.78rem">—</span>
                        <?php endif; ?>
                    </td>
                    <td style="text-align:right">
                        <div class="d-flex gap-1 justify-content-end">
                        <?php if ($isDisponible): ?>
                            <?php if ($b['type'] === 'Labo'): ?>
                                <button class="btn-bilan btn-b-voir"
                                        onclick="voirResultatsLabo(<?= (int)$b['record_id'] ?>)">
                                    <i class="bi bi-eye"></i> Voir
                                </button>
                            <?php else: ?>
                                <a href="<?= BASE_URL ?>imagerie/viewer/<?= (int)$b['record_id'] ?>?from=medecin"
                                   class="btn-bilan btn-b-voir">
                                    <i class="bi bi-image"></i> Voir
                                </a>
                            <?php endif; ?>
                            <button class="btn-bilan btn-b-comment"
                                    onclick="ouvrirCommenter(<?= (int)$b['record_id'] ?>, '<?= $b['type'] === 'Labo' ? 'LABO' : 'IMAGERIE' ?>', <?= $patId ?>, '<?= htmlspecialchars(addslashes($patNom), ENT_QUOTES) ?>', '<?= htmlspecialchars(addslashes($b['label']), ENT_QUOTES) ?>')">
                                <i class="bi bi-chat-text"></i> Commenter
                            </button>
                            <button class="btn-bilan btn-b-rdv"
                                    onclick="ouvrirRdv(<?= $patId ?>, '<?= htmlspecialchars(addslashes($patNom), ENT_QUOTES) ?>', '<?= htmlspecialchars(addslashes($b['label']), ENT_QUOTES) ?>')">
                                <i class="bi bi-calendar-plus"></i> RDV
                            </button>
                        <?php else: ?>
                            <span class="btn-bilan btn-b-attente"><i class="bi bi-hourglass-split"></i> En attente</span>
                            <?php if ($b['type'] === 'Labo' && $b['statut'] === 'EN_ATTENTE'): ?>
                            <button class="btn-bilan"
                                    style="background:#eff6ff;color:#1d4ed8;border:1px solid #bfdbfe;"
                                    onclick="ouvrirMajDemande(<?= (int)$b['record_id'] ?>, '<?= htmlspecialchars(addslashes($b['label']), ENT_QUOTES) ?>')"
                                    title="Mettre à jour la demande">
                                <i class="bi bi-pencil-square"></i> Mettre à jour
                            </button>
                            <button class="btn-bilan"
                                    style="background:#fff1f2;color:#be123c;border:1px solid #fecdd3;"
                                    onclick="supprimerDemandeBilan(<?= (int)$b['record_id'] ?>, this)"
                                    title="Annuler cette demande">
                                <i class="bi bi-trash3"></i>
                            </button>
                            <?php endif; ?>
                        <?php endif; ?>
                        </div>
                    </td>
                </tr>
            <?php endforeach; else: ?>
                <tr><td colspan="6" class="text-center py-5 text-muted small">
                    <i class="bi bi-clipboard2 d-block fs-2 mb-2 opacity-25"></i>Aucun bilan en cours.
                </td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ══ MODAL : MISE À JOUR DEMANDE LABO ══ -->
<div class="modal fade" id="modalMajDemande" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg" style="border-radius:16px;overflow:hidden;">
            <div class="modal-header text-white" style="background:linear-gradient(135deg,#1d4ed8,#3b82f6);padding:16px 22px;">
                <div class="d-flex align-items-center gap-3">
                    <div style="width:36px;height:36px;background:rgba(255,255,255,.15);border-radius:10px;display:flex;align-items:center;justify-content:center;">
                        <i class="bi bi-pencil-square fs-5"></i>
                    </div>
                    <h6 class="modal-title fw-bold mb-0" id="majModalTitle">Mettre à jour la demande</h6>
                </div>
                <button type="button" class="btn-close btn-close-white ms-auto" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4" id="majModalBody">
                <div class="text-center py-4"><div class="spinner-border text-primary"></div></div>
            </div>
            <div class="modal-footer border-0" style="background:#f8fafc;padding:14px 22px;">
                <button type="button" class="btn btn-secondary rounded-pill" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-primary rounded-pill fw-bold" id="btnMajSauvegarder" onclick="sauvegarderMajDemande()">
                    <i class="bi bi-save2-fill me-1"></i>Enregistrer les modifications
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ══ MODAL : RÉSULTATS LABO ══ -->
<div class="modal fade modal-bilan" id="modalResultatsLabo" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold"><i class="bi bi-flask me-2 text-primary"></i>Résultats du Bilan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4" id="resultatsLaboBody">
                <div class="text-center py-4"><div class="spinner-border text-primary"></div></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light rounded-pill" data-bs-dismiss="modal">Fermer</button>
                <button class="btn btn-success rounded-pill" id="btnCommentDepuisResultat" onclick="commenterDepuisResultat()">
                    <i class="bi bi-chat-text me-1"></i>Ajouter un commentaire
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ══ MODAL : COMMENTER BILAN ══ -->
<div class="modal fade modal-bilan" id="modalCommenter" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold"><i class="bi bi-chat-text me-2 text-success"></i>Commenter le Résultat</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <input type="hidden" id="c_demande_id">
                <input type="hidden" id="c_type_bilan">
                <input type="hidden" id="c_patient_id">
                <div class="alert alert-light border rounded-3 mb-3 small" id="c_bilan_info"></div>
                <label class="form-label small fw-bold text-muted text-uppercase">Commentaire / Interprétation clinique</label>
                <textarea id="c_commentaire" class="form-control" rows="5"
                          placeholder="Entrez votre analyse des résultats, conduite à tenir, modifications thérapeutiques..."></textarea>
                <div id="commentaires_existants" class="mt-3"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light rounded-pill" data-bs-dismiss="modal">Annuler</button>
                <button class="btn btn-success rounded-pill fw-bold" onclick="enregistrerCommentaire()">
                    <i class="bi bi-check2 me-1"></i>Enregistrer
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ══ MODAL : PROGRAMMER RDV ══ -->
<div class="modal fade modal-bilan" id="modalRdvBilan" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold"><i class="bi bi-calendar-plus me-2 text-warning"></i>Programmer un RDV</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <input type="hidden" id="rdv_patient_id">
                <div class="alert alert-warning border-0 rounded-3 mb-3 small" id="rdv_info"></div>
                <div class="mb-3">
                    <label class="form-label small fw-bold text-muted text-uppercase">Titre du RDV</label>
                    <input type="text" id="rdv_titre" class="form-control" value="Présentation résultats bilans">
                </div>
                <div class="row g-3 mb-3">
                    <div class="col-6">
                        <label class="form-label small fw-bold text-muted text-uppercase">Date et heure</label>
                        <input type="datetime-local" id="rdv_date_debut" class="form-control" required>
                    </div>
                    <div class="col-6">
                        <label class="form-label small fw-bold text-muted text-uppercase">Fin (optionnel)</label>
                        <input type="datetime-local" id="rdv_date_fin" class="form-control">
                    </div>
                </div>
                <div class="mb-2">
                    <label class="form-label small fw-bold text-muted text-uppercase">Notes</label>
                    <textarea id="rdv_notes" class="form-control" rows="2" placeholder="Examens à apporter, instructions..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light rounded-pill" data-bs-dismiss="modal">Annuler</button>
                <button class="btn btn-warning rounded-pill fw-bold text-dark" onclick="enregistrerRdv()">
                    <i class="bi bi-calendar-check me-1"></i>Confirmer le RDV
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// ── DONNÉES contexte commentaire depuis modal résultats ──
let _currentBilanCtx = {};

/* ── Mise à jour demande labo ──────────────────────────────── */
let _majDemandeId   = null;
let _majExamensDispos = [];
let _majExamensAjouter = [];

async function ouvrirMajDemande(demandeId, label) {
    _majDemandeId    = demandeId;
    _majExamensAjouter = [];

    document.getElementById('majModalTitle').textContent = 'Mettre à jour : ' + label;
    document.getElementById('majModalBody').innerHTML =
        '<div class="text-center py-4"><div class="spinner-border text-primary"></div></div>';
    new bootstrap.Modal(document.getElementById('modalMajDemande')).show();

    /* Charger les données */
    const [r1, r2] = await Promise.all([
        fetch('<?= BASE_URL ?>laboratoire/detail-demande/' + demandeId).then(r => r.json()),
        _majExamensDispos.length ? Promise.resolve(_majExamensDispos)
            : fetch('<?= BASE_URL ?>laboratoire/examens-disponibles').then(r => r.json())
    ]);
    if (!r1.success) { document.getElementById('majModalBody').innerHTML = '<p class="text-danger">'+r1.message+'</p>'; return; }
    if (Array.isArray(r2)) _majExamensDispos = r2;

    const d = r1.demande;
    const examens = r1.examens;

    /* Construire les catégories */
    const cats = [...new Set(_majExamensDispos.map(e => e.categorie).filter(Boolean))].sort();
    const catOptions = cats.map(c => `<option value="${c}">${c}</option>`).join('');

    document.getElementById('majModalBody').innerHTML = `
    <div class="row g-3">

      <!-- Urgence + Notes -->
      <div class="col-md-5">
        <label class="form-label fw-semibold small text-uppercase text-muted">Urgence</label>
        <div class="d-flex gap-2">
          <div class="form-check form-check-inline">
            <input class="form-check-input" type="radio" name="majUrgence" id="majNormal" value="NORMAL" ${d.urgence!=='URGENT'?'checked':''}>
            <label class="form-check-label" for="majNormal">Normal</label>
          </div>
          <div class="form-check form-check-inline">
            <input class="form-check-input" type="radio" name="majUrgence" id="majUrgent" value="URGENT" ${d.urgence==='URGENT'?'checked':''}>
            <label class="form-check-label text-danger fw-bold" for="majUrgent"><i class="bi bi-exclamation-triangle-fill"></i> URGENT</label>
          </div>
        </div>
      </div>
      <div class="col-md-7">
        <label class="form-label fw-semibold small text-uppercase text-muted">Notes cliniques / Instructions</label>
        <textarea id="majNotes" class="form-control" rows="2" placeholder="Notes pour le laborantin…">${escHtml(d.notes_cliniques)}</textarea>
      </div>

      <!-- Examens actuels -->
      <div class="col-12">
        <label class="form-label fw-semibold small text-uppercase text-muted">Examens actuels</label>
        <div id="majListeActuelle">
          ${examens.length === 0
            ? '<p class="text-muted small text-center py-2">Aucun examen lié (demande sans détail).</p>'
            : examens.map(ex => `
            <div class="d-flex align-items-center justify-content-between gap-2 px-2 py-1 border rounded mb-1 bg-white" id="majExRow_${ex.de_id}">
              <div>
                <span class="fw-semibold" style="font-size:.84rem">${escHtml(ex.nom)}</span>
                <span class="badge bg-secondary ms-1" style="font-size:.62rem">${escHtml(ex.categorie||'')}</span>
                ${ex.urgent=='1'?'<span class="badge bg-danger ms-1" style="font-size:.6rem">URGENT</span>':''}
                ${ex.a_jeun=='1'?'<span class="badge bg-warning text-dark ms-1" style="font-size:.6rem">À jeun</span>':''}
              </div>
              <button type="button" class="btn btn-sm btn-outline-danger rounded-pill" style="font-size:.72rem;padding:2px 8px;"
                      onclick="majRetirerExamen(${ex.de_id}, this)">
                <i class="bi bi-x-lg"></i>
              </button>
            </div>`).join('')
          }
        </div>
      </div>

      <!-- Ajouter des examens -->
      <div class="col-12">
        <label class="form-label fw-semibold small text-uppercase text-muted">Ajouter des examens</label>
        <div class="row g-2 mb-2">
          <div class="col-md-5">
            <select class="form-select form-select-sm" id="majCat" onchange="majFiltrerExamens()">
              <option value="">Toutes les catégories</option>${catOptions}
            </select>
          </div>
          <div class="col-md-7">
            <input type="text" class="form-control form-control-sm" id="majSearch"
                   placeholder="Rechercher un examen…" oninput="majFiltrerExamens()">
          </div>
        </div>
        <div id="majExamensList" style="max-height:200px;overflow-y:auto;border:1.5px solid #dee2e6;border-radius:.5rem;padding:4px;">
          <p class="text-muted small text-center py-3 mb-0"><i class="bi bi-hourglass-split me-1"></i>Chargement…</p>
        </div>
        <div class="d-flex justify-content-between mt-1">
          <small id="majSelCount" class="text-success fw-semibold"></small>
        </div>
      </div>

      <!-- Options ajouter -->
      <div class="col-md-4">
        <div class="form-check">
          <input class="form-check-input" type="checkbox" id="majUrgentAdd">
          <label class="form-check-label text-danger fw-bold" for="majUrgentAdd"><i class="bi bi-exclamation-triangle-fill"></i> URGENT</label>
        </div>
      </div>
      <div class="col-md-4">
        <div class="form-check">
          <input class="form-check-input" type="checkbox" id="majAJeun">
          <label class="form-check-label" for="majAJeun">À jeun requis</label>
        </div>
      </div>
      <div class="col-md-12">
        <button type="button" class="btn btn-info btn-sm text-white" onclick="majAjouterSelectionnes()">
          <i class="bi bi-plus-lg me-1"></i>Ajouter les examens sélectionnés
        </button>
      </div>

    </div>`;

    majFiltrerExamens();
}

function escHtml(s) { return (s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }

let _majSupprimer = [];

function majRetirerExamen(deId, btn) {
    const row = document.getElementById('majExRow_' + deId);
    if (row) { row.style.opacity = '.4'; row.style.textDecoration = 'line-through'; }
    if (!_majSupprimer.includes(deId)) _majSupprimer.push(deId);
    btn.disabled = true;
}

function majFiltrerExamens() {
    const cat    = document.getElementById('majCat')?.value || '';
    const search = (document.getElementById('majSearch')?.value || '').toLowerCase().trim();
    let filtered = cat ? _majExamensDispos.filter(e => e.categorie === cat) : _majExamensDispos;
    if (search) filtered = filtered.filter(e => e.nom.toLowerCase().includes(search));

    const list = document.getElementById('majExamensList');
    if (!list) return;

    /* Mémoriser les IDs cochés avant re-rendu */
    const checkedIds = new Set([...document.querySelectorAll('.maj-chk:checked')].map(cb => cb.value));

    if (!filtered.length) { list.innerHTML = '<p class="text-muted small text-center py-3 mb-0">Aucun examen trouvé</p>'; return; }

    list.innerHTML = filtered.slice(0, 80).map(ex => {
        const ds        = JSON.stringify(ex).replace(/"/g, '&quot;');
        const isChecked = checkedIds.has(String(ex.id));
        const rowBg     = isChecked ? 'background:#eff6ff;' : '';
        return `<div class="d-flex align-items-center gap-2 px-2 py-1" style="border-bottom:1px solid #f1f5f9;cursor:pointer;border-radius:6px;${rowBg}" onclick="this.querySelector('input').click()">
            <input class="form-check-input maj-chk flex-shrink-0" type="checkbox" id="majEx_${ex.id}" value="${ex.id}" data-examen="${ds}"
                   ${isChecked ? 'checked' : ''}
                   onclick="event.stopPropagation();majMajCompteur()" style="cursor:pointer">
            <label class="mb-0 w-100" for="majEx_${ex.id}" style="cursor:pointer;pointer-events:none;font-size:.83rem">
                <span class="fw-semibold">${escHtml(ex.nom)}</span>
                <span class="badge bg-secondary ms-1" style="font-size:.6rem">${escHtml(ex.categorie||'')}</span>
            </label>
        </div>`;
    }).join('');
    majMajCompteur();
}

function majMajCompteur() {
    const n = document.querySelectorAll('.maj-chk:checked').length;
    const lbl = document.getElementById('majSelCount');
    if (lbl) lbl.textContent = n > 0 ? `✔ ${n} sélectionné${n>1?'s':''}` : '';
}

function majAjouterSelectionnes() {
    const checked = document.querySelectorAll('.maj-chk:checked');
    if (!checked.length) { alert('Sélectionnez au moins un examen.'); return; }
    const isUrgent = document.getElementById('majUrgentAdd')?.checked;
    const isAJeun  = document.getElementById('majAJeun')?.checked;

    checked.forEach(cb => {
        const ex = JSON.parse(cb.dataset.examen.replace(/&quot;/g, '"'));
        if (!_majExamensAjouter.find(x => x.id == ex.id)) {
            _majExamensAjouter.push({ id: ex.id, nom: ex.nom, urgent: isUrgent, a_jeun: isAJeun, instructions: '' });
        }
        /* Marquer visuellement */
        const row = cb.closest('div[onclick]');
        if (row) { row.style.background = '#f0fdf4'; row.style.opacity = '.6'; cb.disabled = true; }
    });

    /* Ajouter à la liste actuelle */
    const liste = document.getElementById('majListeActuelle');
    _majExamensAjouter.forEach(ex => {
        if (!document.getElementById('majNewEx_' + ex.id)) {
            const div = document.createElement('div');
            div.id = 'majNewEx_' + ex.id;
            div.className = 'd-flex align-items-center justify-content-between gap-2 px-2 py-1 border rounded mb-1';
            div.style.background = '#f0fdf4';
            div.innerHTML = `<div><span class="fw-semibold" style="font-size:.84rem">${escHtml(ex.nom)}</span>
                <span class="badge bg-success ms-1" style="font-size:.62rem">Nouveau</span>
                ${ex.urgent?'<span class="badge bg-danger ms-1" style="font-size:.6rem">URGENT</span>':''}
                ${ex.a_jeun?'<span class="badge bg-warning text-dark ms-1" style="font-size:.6rem">À jeun</span>':''}</div>
                <button type="button" class="btn btn-sm btn-outline-danger rounded-pill" style="font-size:.72rem;padding:2px 8px;"
                    onclick="majAnnulerNouvel(${ex.id},this)"><i class="bi bi-x-lg"></i></button>`;
            liste.appendChild(div);
        }
    });
    majMajCompteur();
}

function majAnnulerNouvel(exId, btn) {
    _majExamensAjouter = _majExamensAjouter.filter(x => x.id != exId);
    document.getElementById('majNewEx_' + exId)?.remove();
}

async function sauvegarderMajDemande() {
    if (!_majDemandeId) return;
    const btn = document.getElementById('btnMajSauvegarder');
    btn.disabled = true; btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" style="width:14px;height:14px;"></span>Enregistrement…';

    const payload = {
        urgence           : document.querySelector('input[name="majUrgence"]:checked')?.value || 'NORMAL',
        notes_cliniques   : document.getElementById('majNotes')?.value || '',
        examens_supprimer : [..._majSupprimer],
        examens_ajouter   : _majExamensAjouter
    };

    const r = await fetch('<?= BASE_URL ?>laboratoire/mettre-a-jour-demande/' + _majDemandeId, {
        method: 'POST', headers: {'Content-Type':'application/json'}, body: JSON.stringify(payload)
    }).then(r => r.json()).catch(() => ({success:false,message:'Erreur réseau'}));

    if (r.success) {
        bootstrap.Modal.getInstance(document.getElementById('modalMajDemande'))?.hide();
        /* Recharger le label dans la ligne */
        location.reload();
    } else {
        alert('Erreur : ' + (r.message || 'inconnue'));
        btn.disabled = false; btn.innerHTML = '<i class="bi bi-save2-fill me-1"></i>Enregistrer';
    }
    _majSupprimer = [];
}

function supprimerDemandeBilan(demandeId, btn) {
    if (!confirm('Annuler cette demande de bilan ? Cette action est irréversible.')) return;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm" style="width:12px;height:12px;"></span>';
    fetch('<?= BASE_URL ?>laboratoire/supprimer-demande/' + demandeId, { method: 'POST' })
        .then(r => r.json())
        .then(d => {
            if (d.success) {
                const row = btn.closest('tr');
                if (row) { row.style.opacity = '0'; row.style.transition = 'opacity .3s'; setTimeout(() => row.remove(), 300); }
            } else {
                alert('Erreur : ' + (d.message || 'Impossible de supprimer'));
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-trash3"></i>';
            }
        })
        .catch(() => { alert('Erreur réseau'); btn.disabled = false; btn.innerHTML = '<i class="bi bi-trash3"></i>'; });
}

function voirResultatsLabo(demandeId) {
    document.getElementById('resultatsLaboBody').innerHTML =
        '<div class="text-center py-4"><div class="spinner-border text-primary"></div></div>';
    new bootstrap.Modal(document.getElementById('modalResultatsLabo')).show();

    fetch('<?= BASE_URL ?>medecin/resultats-bilan?id=' + demandeId)
        .then(r => r.json())
        .then(data => {
            if (!data.success) { document.getElementById('resultatsLaboBody').innerHTML = '<p class="text-danger">Erreur chargement.</p>'; return; }

            let html = '';
            if (data.examens.length === 0) {
                html = '<p class="text-muted text-center py-3">Aucun résultat enregistré pour cette demande.</p>';
            } else {
                const meta = data.examens[0];
                html += `<div class="d-flex align-items-center gap-3 mb-4 p-3 bg-light rounded-3">
                    <i class="bi bi-person-circle fs-4 text-primary"></i>
                    <div><div class="fw-bold">${meta.patient_nom || ''} ${meta.patient_prenom || ''}</div>
                    <small class="text-muted">Dr. ${meta.medecin_nom || ''} • Statut : ${meta.statut || ''}</small></div></div>`;

                data.examens.forEach(ex => {
                    const anormal = ex.anormal == 1;
                    const valCls  = anormal ? 'anormal' : 'normal';
                    html += `<div class="result-row">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <div class="fw-bold">${ex.nom_examen || '—'}</div>
                                <small class="text-muted">${ex.categorie || ''}</small>
                            </div>
                            <span class="badge rounded-pill ${anormal ? 'bg-danger' : 'bg-success'}">${anormal ? 'ANORMAL' : 'NORMAL'}</span>
                        </div>`;
                    if (ex.valeur_numerique !== null) {
                        html += `<div class="mt-2 result-val ${valCls}">${ex.valeur_numerique} <small style="font-size:.7em">${ex.unite||''}</small></div>
                            <small class="text-muted">Norme : ${ex.valeur_normale_min||'?'}–${ex.valeur_normale_max||'?'} ${ex.unite||''}</small>`;
                    } else if (ex.resultat) {
                        html += `<p class="mt-2 mb-0 small">${ex.resultat}</p>`;
                    }
                    if (ex.interpretation) html += `<p class="mt-1 mb-0 small text-primary"><i class="bi bi-chat-quote me-1"></i>${ex.interpretation}</p>`;
                    html += '</div>';
                });
            }

            // Commentaires existants
            if (data.commentaires && data.commentaires.length > 0) {
                html += '<h6 class="fw-bold mt-4 mb-2 text-muted text-uppercase" style="font-size:.72rem;letter-spacing:.8px">Commentaires médecin</h6>';
                data.commentaires.forEach(c => {
                    html += `<div class="commentaire-item">
                        <div class="c-meta">Dr. ${c.medecin_nom} — ${new Date(c.created_at).toLocaleDateString('fr-FR')}</div>
                        <div class="c-text">${c.commentaire}</div></div>`;
                });
            }

            document.getElementById('resultatsLaboBody').innerHTML = html;
            // Stocker contexte pour commentaire depuis modal résultats
            _currentBilanCtx = { demandeId: demandeId, type: 'LABO' };
        });
}

function commenterDepuisResultat() {
    bootstrap.Modal.getInstance(document.getElementById('modalResultatsLabo'))?.hide();
    // On déclenche avec le contexte stocké
    // Besoin du patient_id – récupéré dans les données fetchées
    fetch('<?= BASE_URL ?>medecin/resultats-bilan?id=' + _currentBilanCtx.demandeId)
        .then(r => r.json()).then(data => {
            if (data.examens && data.examens[0]) {
                // On n'a pas directement patient_id dans cette réponse → chercher dans le DOM
                ouvrirCommenter(_currentBilanCtx.demandeId, 'LABO', 0,
                    (data.examens[0].patient_nom || '') + ' ' + (data.examens[0].patient_prenom || ''),
                    data.examens[0].nom_examen || 'Bilan');
            }
        });
}

function ouvrirCommenter(demandeId, typeBilan, patientId, patNom, examNom) {
    document.getElementById('c_demande_id').value = demandeId;
    document.getElementById('c_type_bilan').value = typeBilan;
    document.getElementById('c_patient_id').value = patientId;
    document.getElementById('c_commentaire').value = '';
    document.getElementById('c_bilan_info').innerHTML =
        `<strong>${patNom}</strong> — <em>${examNom}</em>`;

    // Charger les commentaires existants
    fetch('<?= BASE_URL ?>medecin/resultats-bilan?id=' + demandeId)
        .then(r => r.json()).then(data => {
            let html = '';
            if (data.commentaires && data.commentaires.length > 0) {
                html = '<h6 class="small text-muted fw-bold text-uppercase mb-2" style="letter-spacing:.7px">Commentaires précédents</h6>';
                data.commentaires.forEach(c => {
                    html += `<div class="commentaire-item">
                        <div class="c-meta">Dr. ${c.medecin_nom} — ${new Date(c.created_at).toLocaleDateString('fr-FR')}</div>
                        <div class="c-text">${c.commentaire}</div></div>`;
                });
            }
            document.getElementById('commentaires_existants').innerHTML = html;
        });

    new bootstrap.Modal(document.getElementById('modalCommenter')).show();
}

function enregistrerCommentaire() {
    const fd = new FormData();
    fd.append('demande_id',  document.getElementById('c_demande_id').value);
    fd.append('type_bilan',  document.getElementById('c_type_bilan').value);
    fd.append('patient_id',  document.getElementById('c_patient_id').value);
    fd.append('commentaire', document.getElementById('c_commentaire').value.trim());

    if (!fd.get('commentaire')) { alert('Veuillez saisir un commentaire.'); return; }

    fetch('<?= BASE_URL ?>medecin/commenter-bilan', { method:'POST', body:fd })
        .then(r => r.json()).then(data => {
            if (data.success) {
                bootstrap.Modal.getInstance(document.getElementById('modalCommenter'))?.hide();
                // Toast + reload léger
                const toast = document.createElement('div');
                toast.style.cssText = 'position:fixed;bottom:20px;right:20px;background:#16a34a;color:white;padding:12px 20px;border-radius:12px;font-weight:700;z-index:9999;box-shadow:0 8px 24px rgba(0,0,0,.2)';
                toast.innerHTML = '<i class="bi bi-check2-circle me-2"></i>Commentaire enregistré.';
                document.body.appendChild(toast);
                setTimeout(() => { toast.remove(); location.reload(); }, 2000);
            } else {
                alert('Erreur : ' + (data.message || 'Inconnue'));
            }
        });
}

function ouvrirRdv(patientId, patNom, examNom) {
    document.getElementById('rdv_patient_id').value = patientId;
    document.getElementById('rdv_info').innerHTML =
        `<i class="bi bi-person-fill me-2"></i><strong>${patNom}</strong> — ${examNom}`;
    document.getElementById('rdv_titre').value = `Présentation résultats — ${examNom}`;
    // Pré-remplir date = demain à 9h
    const d = new Date(); d.setDate(d.getDate()+1); d.setHours(9,0,0);
    document.getElementById('rdv_date_debut').value = d.toISOString().slice(0,16);
    new bootstrap.Modal(document.getElementById('modalRdvBilan')).show();
}

function enregistrerRdv() {
    const fd = new FormData();
    fd.append('patient_id',  document.getElementById('rdv_patient_id').value);
    fd.append('titre',       document.getElementById('rdv_titre').value);
    fd.append('date_debut',  document.getElementById('rdv_date_debut').value);
    fd.append('date_fin',    document.getElementById('rdv_date_fin').value);
    fd.append('notes',       document.getElementById('rdv_notes').value);

    if (!fd.get('date_debut')) { alert('Veuillez choisir une date.'); return; }

    fetch('<?= BASE_URL ?>medecin/programmer-rdv-bilan', { method:'POST', body:fd })
        .then(r => r.json()).then(data => {
            if (data.success) {
                bootstrap.Modal.getInstance(document.getElementById('modalRdvBilan'))?.hide();
                const toast = document.createElement('div');
                toast.style.cssText = 'position:fixed;bottom:20px;right:20px;background:#d97706;color:white;padding:12px 20px;border-radius:12px;font-weight:700;z-index:9999;box-shadow:0 8px 24px rgba(0,0,0,.2)';
                toast.innerHTML = '<i class="bi bi-calendar-check me-2"></i>RDV programmé avec succès !';
                document.body.appendChild(toast);
                setTimeout(() => toast.remove(), 3000);
            } else {
                alert('Erreur : ' + (data.message || 'Inconnue'));
            }
        });
}
</script>

<!-- SECTION : DOSSIERS PARTAGÉS (Dashboard Médecin) -->
<div class="med-card">
    <div class="card-header-custom" style="background:linear-gradient(135deg,#0d9488,#0f766e)">
        <h5 class="section-title text-white mb-0">
            <span class="s-icon" style="background:rgba(255,255,255,.15);color:#fff"><i class="bi bi-share-fill"></i></span>
            Dossiers Partagés
        </h5>
    </div>
    <div class="card-body p-3">
        <!-- Navigation des onglets -->
        <ul class="nav nav-pills nav-justified mb-3" id="partageTab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active rounded-pill fw-bold" data-bs-toggle="pill" data-bs-target="#reçus" type="button">
                    <i class="bi bi-inbox me-2"></i>Reçus
                    <span class="badge bg-light text-primary ms-2"><?= is_array($dossiers_reçus) ? count($dossiers_reçus) : 0 ?></span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link rounded-pill fw-bold" data-bs-toggle="pill" data-bs-target="#envoyés" type="button">
                    <i class="bi bi-send me-2"></i>Envoyés
                    <span class="badge bg-light text-primary ms-2"><?= is_array($dossiers_envoyés) ? count($dossiers_envoyés) : 0 ?></span>
                </button>
            </li>
        </ul>

        <!-- Contenu des onglets -->
        <div class="tab-content">
            <!-- DOSSIERS REÇUS -->
            <div class="tab-pane fade show active" id="reçus" role="tabpanel">
                <?php if(!empty($dossiers_reçus) && is_array($dossiers_reçus)): foreach($dossiers_reçus as $r): ?>
                    <div class="alert alert-light border mb-2 d-flex justify-content-between align-items-center gap-2">
                        <div class="flex-grow-1">
                            <strong class="text-primary"><?= htmlspecialchars($r['nom'].' '.$r['prenom']) ?></strong>
                            <?php if (!empty($r['avis_medecin'])): ?>
                                <span class="badge bg-success ms-1" style="font-size:.65rem"><i class="bi bi-check-lg me-1"></i>Avis donné</span>
                            <?php else: ?>
                                <span class="badge ms-1" style="background:#0d9488;font-size:.65rem">Avis à donner</span>
                            <?php endif; ?>
                            <br>
                            <small class="text-muted"><i class="bi bi-person-fill me-1"></i>Envoyé par Dr. <?= htmlspecialchars($r['expediteur_nom']) ?></small>
                            <small class="text-muted ms-2"><i class="bi bi-clock me-1"></i>Expire <?= date('d/m H:i', strtotime($r['date_expiration'])) ?></small>
                        </div>
                        <a href="<?= BASE_URL ?>patients/dossier/<?= $r['patient_id'] ?>#tab-avis-partage"
                           class="btn btn-sm text-white rounded-pill px-3 flex-shrink-0"
                           style="background:#0d9488">
                            <i class="bi bi-pencil-square me-1"></i><?= empty($r['avis_medecin']) ? 'Donner avis' : 'Voir dossier' ?>
                        </a>
                    </div>
                <?php endforeach; else: ?>
                    <div class="text-center py-4 text-muted small italic">Aucun dossier reçu.</div>
                <?php endif; ?>
            </div>

            <!-- DOSSIERS ENVOYÉS -->
            <div class="tab-pane fade" id="envoyés" role="tabpanel">
                <?php if(!empty($dossiers_envoyés) && is_array($dossiers_envoyés)): foreach($dossiers_envoyés as $e): ?>
                    <div class="alert alert-light border mb-2 d-flex justify-content-between align-items-center gap-2">
                        <div class="flex-grow-1">
                            <strong class="text-dark"><?= htmlspecialchars($e['nom'].' '.$e['prenom']) ?></strong>
                            <?php if (!empty($e['avis_medecin'])): ?>
                                <span class="badge bg-success ms-1" style="font-size:.65rem"><i class="bi bi-check-lg me-1"></i>Avis reçu</span>
                            <?php endif; ?>
                            <br>
                            <small class="text-muted"><i class="bi bi-send-check me-1"></i>Partagé à Dr. <?= htmlspecialchars($e['destinataire_nom']) ?></small>
                            <small class="text-muted ms-2"><i class="bi bi-clock me-1"></i>Expire <?= date('d/m H:i', strtotime($e['date_expiration'])) ?></small>
                        </div>
                        <?php if (!empty($e['avis_medecin'])): ?>
                        <a href="<?= BASE_URL ?>patients/dossier/<?= $e['patient_id'] ?>"
                           class="btn btn-sm btn-outline-success rounded-pill px-3 flex-shrink-0">
                            <i class="bi bi-eye me-1"></i>Voir l'avis
                        </a>
                        <?php endif; ?>
                    </div>
                <?php endforeach; else: ?>
                    <div class="text-center py-4 text-muted small italic">Aucun dossier envoyé.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

            <!-- ================= FORMULAIRES À SIGNER ================= -->
<?php
// Compter + récupérer les formulaires infirmiers en attente
$nbFormulairesASigner  = 0;
$apercuFormulaires     = [];
$sqlFiltreFS = "statut IN ('SOUMIS','VU')
              AND (
                  medecin_id = ?
                  OR medecin_id IS NULL
                  OR service_id = ?
                  OR service_id IS NULL
                  OR service_id = 0
              )";
try {
    $chkTable = $db->query("SHOW TABLES LIKE 'formulaires_soumis'")->rowCount();
    if ($chkTable) {
        // Compteur
        $stmtFS = $db->prepare("SELECT COUNT(*) FROM formulaires_soumis WHERE $sqlFiltreFS");
        $stmtFS->execute([$userId, $serviceId ?? 0]);
        $nbFormulairesASigner = (int)$stmtFS->fetchColumn();

        // Aperçu des 4 derniers
        if ($nbFormulairesASigner > 0) {
            $stmtAp = $db->prepare("
                SELECT fs.id, fs.type_formulaire, fs.titre, fs.statut, fs.date_soumission,
                       p.nom AS pat_nom, p.prenom AS pat_prenom,
                       u.nom AS inf_nom, u.prenom AS inf_prenom
                FROM formulaires_soumis fs
                JOIN patients p ON fs.patient_id = p.id
                JOIN users   u  ON fs.infirmier_id = u.id
                WHERE $sqlFiltreFS
                ORDER BY fs.date_soumission DESC
                LIMIT 4
            ");
            $stmtAp->execute([$userId, $serviceId ?? 0]);
            $apercuFormulaires = $stmtAp->fetchAll(PDO::FETCH_ASSOC);
        }
    }
} catch (\Exception $e) {}
?>
<?php if ($nbFormulairesASigner > 0): ?>
<div class="med-card" style="border-left:4px solid #0d9488;">
    <div class="card-header-custom" style="background:linear-gradient(135deg,#f0fdfa,#ccfbf1)">
        <h5 class="section-title">
            <span class="s-icon" style="background:#0d9488;color:#fff"><i class="bi bi-file-earmark-check-fill"></i></span>
            Formulaires à signer
            <span class="badge ms-2 rounded-pill text-white" style="background:#0d9488;font-size:.7rem;min-width:22px"><?= $nbFormulairesASigner ?></span>
        </h5>
        <a href="<?= BASE_URL ?>hospitalisation/formulaires-a-signer" class="act-btn text-white fw-bold"
           style="background:#0d9488;font-size:.72rem">
            <i class="bi bi-pen me-1"></i>Voir tout
        </a>
    </div>
    <!-- Aperçu des derniers formulaires -->
    <div style="padding:4px 8px 8px;">
        <?php foreach ($apercuFormulaires as $af):
            $isNew = $af['statut'] === 'SOUMIS';
            $diff  = time() - strtotime($af['date_soumission']);
            $quand = $diff < 3600
                ? round($diff/60) . ' min'
                : ($diff < 86400 ? round($diff/3600) . 'h' : date('d/m', strtotime($af['date_soumission'])));
        ?>
        <div style="display:flex;align-items:center;gap:10px;padding:8px 10px;border-radius:10px;
                    margin-bottom:4px;background:<?= $isNew ? '#f0fdf4' : '#f8fafc' ?>;
                    border:1px solid <?= $isNew ? '#bbf7d0' : '#e2e8f0' ?>;">
            <!-- Icône -->
            <div style="width:36px;height:36px;border-radius:9px;flex-shrink:0;
                        display:flex;align-items:center;justify-content:center;font-size:1rem;
                        background:<?= $isNew ? '#dcfce7' : '#f1f5f9' ?>;
                        color:<?= $isNew ? '#16a34a' : '#64748b' ?>;">
                <i class="bi bi-file-earmark-<?= $isNew ? 'text-fill' : 'check-fill' ?>"></i>
            </div>
            <!-- Infos -->
            <div style="flex:1;min-width:0;">
                <div style="font-size:.82rem;font-weight:700;color:#1e293b;
                            white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                    <?= htmlspecialchars($af['titre']) ?>
                    <?php if ($isNew): ?>
                    <span style="background:#dbeafe;color:#1d4ed8;font-size:.6rem;
                                 padding:1px 6px;border-radius:8px;font-weight:700;
                                 margin-left:4px;vertical-align:middle;">NOUVEAU</span>
                    <?php endif; ?>
                </div>
                <div style="font-size:.72rem;color:#64748b;margin-top:1px;">
                    <i class="bi bi-person-fill me-1"></i><?= htmlspecialchars($af['pat_nom'] . ' ' . $af['pat_prenom']) ?>
                    <span style="margin:0 5px;opacity:.4">·</span>
                    <i class="bi bi-nurse me-1"></i>Inf. <?= htmlspecialchars($af['inf_nom'] . ' ' . $af['inf_prenom']) ?>
                </div>
            </div>
            <!-- Temps + lien -->
            <div style="text-align:right;flex-shrink:0;">
                <div style="font-size:.68rem;color:#94a3b8;margin-bottom:4px;"><?= $quand ?></div>
                <a href="<?= BASE_URL ?>hospitalisation/formulaires-a-signer"
                   style="font-size:.7rem;font-weight:700;color:#0d9488;text-decoration:none;">
                    Signer <i class="bi bi-arrow-right"></i>
                </a>
            </div>
        </div>
        <?php endforeach; ?>

        <?php if ($nbFormulairesASigner > 4): ?>
        <div style="text-align:center;padding:6px 0 2px;">
            <a href="<?= BASE_URL ?>hospitalisation/formulaires-a-signer"
               style="font-size:.75rem;color:#0d9488;font-weight:700;text-decoration:none;">
                <i class="bi bi-plus-circle me-1"></i>
                Voir <?= $nbFormulairesASigner - 4 ?> autre<?= $nbFormulairesASigner - 4 > 1 ? 's' : '' ?> formulaire<?= $nbFormulairesASigner - 4 > 1 ? 's' : '' ?>
            </a>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

            <!-- ================= SECTION MES RENDEZ-VOUS ================= -->
<div class="med-card">
    <div class="card-header-custom">
        <h5 class="section-title">
            <span class="s-icon" style="background:#f0fdf4;color:#16a34a"><i class="bi bi-calendar2-check-fill"></i></span>
            Mes Rendez-vous à venir
        </h5>
        <a href="<?= BASE_URL ?>agenda" class="act-btn act-btn-primary" style="font-size:.72rem"><i class="bi bi-calendar3"></i> Voir l'agenda</a>
    </div>
    <div class="table-responsive">
        <table class="table table-custom align-middle">
            <thead>
                <tr>
                    <th>Date & Heure</th>
                    <th>Patient</th>
                    <th>Motif</th>
                    <th class="text-end">Statut</th>
                </tr>
            </thead>
            <tbody>
                <?php if(!empty($mes_rdv)): foreach($mes_rdv as $rdv): ?>
                    <tr>
                        <td>
                            <div class="fw-bold text-dark">
                                <i class="bi bi-clock text-primary me-2"></i>
                                <?= date('d/m', strtotime($rdv['date_rdv'])) ?> à <?= date('H:i', strtotime($rdv['date_rdv'])) ?>
                            </div>
                        </td>
                        <td>
                            <strong><?= strtoupper($rdv['nom']) ?> <?= $rdv['prenom'] ?></strong><br>
                            <small class="text-muted"><?= $rdv['dossier_numero'] ?></small>
                        </td>
                        <td><small><?= htmlspecialchars($rdv['motif']) ?></small></td>
                        <td class="text-end">
                            <?php
                                $statusClass = ($rdv['statut'] == 'CONFIRME') ? 'bg-success' : 'bg-warning text-dark';
                            ?>
                            <span class="badge <?= $statusClass ?> rounded-pill px-3">
                                <?= $rdv['statut'] ?>
                            </span>
                        </td>
                    </tr>
                <?php endforeach; else: ?>
                    <tr>
                        <td colspan="4" class="text-center py-4 text-muted small italic">
                            <i class="bi bi-calendar-x d-block mb-2 fs-3 opacity-50"></i>
                            Aucun rendez-vous programmé pour le moment.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
        </div>

        <!-- COLONNE DROITE (4/12) -->
        <div class="col-lg-4">

            <!-- 4. CONSULTATIONS RÉCENTES -->
            <div class="side-card">
                <div class="side-card-head" style="background:linear-gradient(135deg,#0f172a,#1e293b)">
                    <span class="side-card-title text-white">
                        <i class="bi bi-clipboard2-pulse-fill" style="color:#60a5fa"></i>
                        Consultations Récentes
                    </span>
                    <span class="chip" style="background:rgba(255,255,255,.12);color:#94a3b8;font-size:.63rem">Aujourd'hui</span>
                </div>
                <?php if(!empty($patients_consultes)): foreach($patients_consultes as $hc):
                    $iniC = strtoupper(substr($hc['nom'],0,1).substr($hc['prenom']??'',0,1));
                ?>
                <div class="consult-item">
                    <div class="pat-avatar" style="background:linear-gradient(135deg,#0d9488,#2dd4bf);font-size:.7rem"><?= $iniC ?></div>
                    <div class="flex-grow-1">
                        <div class="fw-bold" style="font-size:.82rem"><?= htmlspecialchars($hc['nom'].' '.$hc['prenom']) ?></div>
                        <div style="font-size:.7rem;color:#94a3b8"><i class="bi bi-clock me-1"></i><?= date('H:i', strtotime($hc['date_consultation'])) ?></div>
                    </div>
                    <div class="d-flex gap-1 flex-shrink-0">
                        <?php if($hc['can_hospitaliser'] && $hc['statut_hosp'] == 'AUCUN'): ?>
                            <button class="act-btn btn-hosp-pulse" style="font-size:.68rem;padding:5px 10px" onclick="hospitaliser(<?= $hc['consult_id'] ?>)">
                                <i class="bi bi-house-heart"></i> Hosp.
                            </button>
                        <?php elseif($hc['statut_hosp'] != 'AUCUN'): ?>
                            <span class="chip chip-green" style="font-size:.63rem"><i class="bi bi-check-circle-fill"></i> Transmis</span>
                        <?php endif; ?>
                        <a href="<?= BASE_URL ?>patients/dossier/<?= $hc['patient_id'] ?>"
                           class="act-btn act-btn-slate" style="font-size:.68rem;padding:5px 8px">
                            <i class="bi bi-folder2-open"></i>
                        </a>
                    </div>
                </div>
                <?php endforeach; else: ?>
                <div class="empty-state" style="padding:28px 16px">
                    <i class="bi bi-clipboard2" style="font-size:2rem"></i>
                    <p>Aucune consultation récente.</p>
                </div>
                <?php endif; ?>
            </div>

            <!-- 5. TO-DO LIST PERSO -->
            <div class="side-card">
                <div class="side-card-head">
                    <span class="side-card-title">
                        <i class="bi bi-check2-square" style="color:#d97706"></i>
                        Mes Rappels / Notes
                    </span>
                    <button class="act-btn act-btn-primary" style="width:30px;height:30px;padding:0;justify-content:center;border-radius:8px"
                            onclick="document.getElementById('todoIn').focus()">
                        <i class="bi bi-plus fw-bold"></i>
                    </button>
                </div>
                <div class="p-3">
                    <div class="d-flex gap-2 mb-3">
                        <input type="text" id="todoIn"
                               class="form-control form-control-sm"
                               style="border-radius:10px;border:1px solid #e2e8f0;font-size:.82rem;background:#f8fafc"
                               placeholder="Ajouter une note..."
                               onkeydown="if(event.key==='Enter') addTask()">
                        <button class="act-btn act-btn-primary" style="white-space:nowrap" onclick="addTask()">+ Ajouter</button>
                    </div>
                    <div id="todoList">
                        <?php if(!empty($mes_taches)): foreach($mes_taches as $t): ?>
                        <div class="todo-item">
                            <input class="form-check-input" type="checkbox" <?= $t['is_done'] ? 'checked' : '' ?> onchange="toggleTask(<?= $t['id'] ?>)">
                            <label class="flex-grow-1 mb-0" style="font-size:.82rem;cursor:pointer;<?= $t['is_done'] ? 'text-decoration:line-through;color:#94a3b8' : 'color:#334155' ?>">
                                <?= htmlspecialchars($t['label']) ?>
                            </label>
                            <i class="bi bi-trash3" style="color:#cbd5e1;cursor:pointer;font-size:.85rem" onclick="deleteTask(<?= $t['id'] ?>)"></i>
                        </div>
                        <?php endforeach; else: ?>
                        <div class="empty-state" style="padding:20px 0">
                            <i class="bi bi-journal-text" style="font-size:1.8rem"></i>
                            <p>Aucune note pour le moment.</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- MODAL : VALIDATION BILAN LABO -->
<div class="modal fade" id="modalResultat" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header bg-primary text-white border-0 p-4">
                <h5 class="modal-title fw-bold">Validation du Résultat Médical</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="<?= BASE_URL ?>consultation/confirmer-diagnostic" method="POST">
                <input type="hidden" name="resultat_id" id="val-res-id">
                <div class="modal-body p-4">
                    <div class="p-4 bg-light rounded-4 mb-4 border">
                        <div class="row">
                            <div class="col-md-6"><small class="text-muted d-block fw-bold">Patient</small><p id="val-res-patient" class="fw-bold mb-0">---</p></div>
                            <div class="col-md-6 text-end"><small class="text-muted d-block fw-bold">Examen</small><p id="val-res-examen" class="fw-bold mb-0 text-primary">---</p></div>
                        </div>
                        <hr class="my-3">
                        <small class="text-muted d-block fw-bold mb-2">Valeur technique :</small>
                        <div id="val-res-data" class="fs-4 fw-bold text-dark bg-white p-3 rounded border border-primary border-opacity-25">---</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Conclusion Médicale / Action Thérapeutique</label>
                        <textarea name="diagnostic_complement" class="form-control rounded-4 shadow-sm" rows="5" placeholder="En fonction de ce résultat, quel est votre diagnostic ou changement de traitement ?" required></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light border-0 p-4">
                    <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Fermer</button>
                    <button type="submit" class="btn btn-success rounded-pill px-5 shadow-sm fw-bold">Valider & Intégrer au Dossier</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Horloge
    setInterval(() => { document.getElementById('liveClock').innerText = new Date().toLocaleTimeString('fr-FR'); }, 1000);

    // Fonction d'ouverture modale Labo (Bootstrap 5)
    function openResultat(id, patient, examen, resultat) {
        document.getElementById('val-res-id').value = id;
        document.getElementById('val-res-patient').innerText = patient;
        document.getElementById('val-res-examen').innerText = examen;
        document.getElementById('val-res-data').innerText = resultat;

        var myModal = new bootstrap.Modal(document.getElementById('modalResultat'));
        myModal.show();
    }

    // Action Hospitaliser
    function hospitaliser(consultId) {
        if(!confirm('Confirmer la demande d\'hospitalisation immédiate ?')) return;
        fetch('<?= BASE_URL ?>dashboard/hospitaliser', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'consult_id=' + consultId
        }).then(res => res.json()).then(data => {
            if(data.success) { alert('Demande transmise aux infirmiers.'); location.reload(); }
            else { alert('Erreur : ' + data.message); }
        });
    }

    // Tâches AJAX
    function addTask() {
        const label = document.getElementById('todoIn').value;
        if(!label) return;
        fetch('<?= BASE_URL ?>dashboard/add-task', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'label=' + encodeURIComponent(label)
        }).then(() => location.reload());
    }

    function toggleTask(id) {
        fetch('<?= BASE_URL ?>dashboard/toggle-task', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'id=' + id
        });
    }

    function deleteTask(id) {
        if(!confirm('Supprimer ?')) return;
        fetch('<?= BASE_URL ?>dashboard/delete-task', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'id=' + id
        }).then(() => location.reload());
    }

    /* ── Prise en charge exclusive d'une patiente maternité ──────────── */
    function prendrePatienteMat(patientId, nom, btn) {
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>En cours…';

        fetch('<?= BASE_URL ?>consultation/prendre-patient', {
            method : 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body   : 'patient_id=' + patientId
        })
        .then(r => r.json())
        .then(d => {
            if (d.success) {
                // Rediriger vers la consultation gynéco
                window.location.href = d.redirect;
            } else {
                // Une autre médecin a déjà pris la patiente
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-person-heart-fill me-1"></i>Prendre en charge';

                // Afficher un message et retirer la carte de la file
                const card = btn.closest('[id^="pat-"]') || btn.closest('.pat-row') || btn.closest('tr') || btn.parentElement;
                card.style.opacity = '.4';
                card.style.pointerEvents = 'none';

                // Toast d'information
                const toast = document.createElement('div');
                toast.style.cssText = 'position:fixed;bottom:20px;right:20px;z-index:9999;background:#fce7f3;color:#9d174d;border:1.5px solid #f9a8d4;border-radius:12px;padding:14px 20px;font-weight:700;font-size:.88rem;box-shadow:0 4px 20px rgba(0,0,0,.15);animation:slideIn .3s ease;';
                toast.innerHTML = '<i class="bi bi-info-circle-fill me-2"></i>' + (d.message || 'Patiente déjà prise en charge.');
                document.body.appendChild(toast);
                setTimeout(() => { toast.style.opacity = '0'; setTimeout(() => toast.remove(), 400); }, 4000);
            }
        })
        .catch(() => {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-person-heart-fill me-1"></i>Prendre en charge';
            alert('Erreur réseau.');
        });
    }
</script>

<!-- ═══════════════════════════════════════════════════════════════
     MODAL — CHANGER DE SERVICE TEMPORAIREMENT
════════════════════════════════════════════════════════════════════ -->
<div class="modal fade" id="modalChangerService" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width:480px">
        <div class="modal-content border-0 shadow-lg" style="border-radius:20px;overflow:hidden">

            <!-- En-tête -->
            <div class="modal-header border-0 py-3 px-4"
                 style="background:linear-gradient(135deg,#1e40af 0%,#3b82f6 100%)">
                <div class="d-flex align-items-center gap-3 w-100">
                    <div style="width:42px;height:42px;background:rgba(255,255,255,.2);border-radius:12px;
                                display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <i class="bi bi-shuffle text-white" style="font-size:1.1rem"></i>
                    </div>
                    <div>
                        <h5 class="fw-bold mb-0 text-white" style="font-size:.95rem">Changer de service</h5>
                        <small style="color:rgba(255,255,255,.7);font-size:.72rem">Basculement temporaire — votre service d'origine est conservé</small>
                    </div>
                    <button type="button" class="btn-close btn-close-white ms-auto" data-bs-dismiss="modal"></button>
                </div>
            </div>

            <div class="modal-body px-4 pt-4 pb-2">

                <!-- Alerte service temporaire actif -->
                <?php if ($_estServiceTemporaire): ?>
                <div class="alert border-0 rounded-3 mb-3 d-flex align-items-center gap-2 py-2 px-3"
                     style="background:#fff7ed;color:#c2410c;font-size:.82rem;">
                    <i class="bi bi-arrow-left-right fw-bold"></i>
                    <div>
                        <strong>Service temporaire actif :</strong>
                        <span id="nomServiceTemporaireAlerte"><?= htmlspecialchars($_SESSION['nom_service'] ?? '') ?></span><br>
                        <small style="opacity:.75">Service d'origine : <strong><?= htmlspecialchars($_nomServiceOrigine) ?></strong></small>
                    </div>
                </div>
                <div id="alertServiceTemporaire" style="display:none"></div>
                <?php else: ?>
                <div id="alertServiceTemporaire" class="alert border-0 rounded-3 mb-3 d-flex align-items-center gap-2 py-2 px-3"
                     style="display:none!important;background:#fff7ed;color:#c2410c;font-size:.82rem;">
                    <i class="bi bi-arrow-left-right fw-bold"></i>
                    <div>
                        <strong>Service temporaire actif :</strong>
                        <span id="nomServiceTemporaireAlerte"></span>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Info service actuel -->
                <div class="mb-3 p-3 rounded-3" style="background:#f8fafc;border:1px solid #e2e8f0;">
                    <div style="font-size:.7rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.5px;margin-bottom:4px">Service actuel</div>
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-building-fill text-primary" style="font-size:.9rem"></i>
                        <span id="serviceActuelLabel" style="font-weight:700;color:#0f172a;font-size:.87rem">
                            <?= htmlspecialchars($_SESSION['nom_service'] ?? '') ?>
                        </span>
                    </div>
                </div>

                <!-- Services disponibles -->
                <div style="font-size:.7rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.5px;margin-bottom:8px">
                    <i class="bi bi-list-ul me-1"></i>Choisir un service de destination
                </div>

                <?php if (empty($_servicesAutorisesChgt)): ?>
                <div class="text-center py-3 text-muted" style="font-size:.82rem">
                    <i class="bi bi-exclamation-triangle me-1"></i>Aucun service disponible pour le basculement.
                </div>
                <?php else: ?>
                <div class="d-flex flex-column gap-2 mb-3" id="listeServicesChgt">
                    <?php foreach ($_servicesAutorisesChgt as $_svc): ?>
                    <?php
                        $_isUrgence = stripos($_svc['nom_service'], 'urgence') !== false;
                        $_isCurrent = (int)$_svc['id'] === (int)($_SESSION['service_id'] ?? 0);
                        $_icon  = $_isUrgence ? 'bi-hospital-fill' : 'bi-person-badge-fill';
                        $_color = $_isUrgence ? '#dc2626' : '#1e40af';
                        $_bg    = $_isUrgence ? '#fef2f2' : '#eff6ff';
                        $_border= $_isUrgence ? '#fca5a5' : '#bfdbfe';
                    ?>
                    <button onclick="changerVersService(<?= (int)$_svc['id'] ?>, '<?= addslashes(htmlspecialchars($_svc['nom_service'])) ?>')"
                            class="btn text-start d-flex align-items-center gap-3 rounded-3 service-chgt-btn"
                            style="background:<?= $_bg ?>;border:1.5px solid <?= $_isCurrent ? $_color : $_border ?>;padding:10px 14px;transition:.15s;<?= $_isCurrent ? 'opacity:.5;cursor:not-allowed;' : '' ?>"
                            <?= $_isCurrent ? 'disabled title="Vous êtes déjà dans ce service"' : '' ?>>
                        <div style="width:36px;height:36px;background:<?= $_color ?>22;border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <i class="bi <?= $_icon ?>" style="color:<?= $_color ?>;font-size:.95rem"></i>
                        </div>
                        <div class="flex-grow-1">
                            <div style="font-weight:700;color:#0f172a;font-size:.85rem"><?= htmlspecialchars($_svc['nom_service']) ?></div>
                            <?php if ($_isUrgence): ?>
                            <div style="font-size:.7rem;color:#94a3b8">File d'attente urgences — tous les cas</div>
                            <?php else: ?>
                            <div style="font-size:.7rem;color:#94a3b8">Consultations programmées du service</div>
                            <?php endif; ?>
                        </div>
                        <?php if ($_isCurrent): ?>
                        <i class="bi bi-check-circle-fill" style="color:<?= $_color ?>;font-size:1rem;flex-shrink:0"></i>
                        <?php else: ?>
                        <i class="bi bi-arrow-right" style="color:#94a3b8;font-size:.9rem;flex-shrink:0"></i>
                        <?php endif; ?>
                    </button>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <!-- Erreur -->
                <div id="changerServiceErr" class="alert alert-danger rounded-3 py-2 px-3 d-none" style="font-size:.8rem"></div>
            </div>

            <div class="modal-footer border-0 px-4 pb-4 pt-2 d-flex gap-2">
                <!-- Bouton retour service d'origine -->
                <button id="btnRetourServiceOrigine" type="button"
                        onclick="retournerServiceOrigine()"
                        class="btn btn-outline-warning rounded-pill fw-bold flex-grow-1"
                        style="font-size:.82rem;<?= !$_estServiceTemporaire ? 'display:none!important;' : '' ?>">
                    <i class="bi bi-house-fill me-1"></i>
                    Retour : <strong><?= htmlspecialchars($_nomServiceOrigine ?: 'Mon service') ?></strong>
                </button>
                <button type="button" class="btn btn-outline-secondary rounded-pill <?= $_estServiceTemporaire ? '' : 'flex-grow-1' ?>"
                        data-bs-dismiss="modal" style="font-size:.82rem">Fermer</button>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    let _modalChangerService = null;

    function getModalCS() {
        if (!_modalChangerService) {
            _modalChangerService = new bootstrap.Modal(document.getElementById('modalChangerService'));
        }
        return _modalChangerService;
    }

    window.ouvrirChangerService = function() {
        document.getElementById('changerServiceErr').classList.add('d-none');
        getModalCS().show();
    };

    window.changerVersService = function(serviceId, nomService) {
        const err = document.getElementById('changerServiceErr');
        err.classList.add('d-none');

        // Désactiver tous les boutons pendant la requête
        document.querySelectorAll('.service-chgt-btn').forEach(b => b.disabled = true);

        fetch(BASE_URL + 'dashboard/changer-service', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'service_id=' + encodeURIComponent(serviceId)
        })
        .then(r => r.json())
        .then(data => {
            if (!data.success) {
                err.textContent = data.message || 'Erreur lors du changement de service.';
                err.classList.remove('d-none');
                document.querySelectorAll('.service-chgt-btn').forEach(b => b.disabled = false);
                return;
            }
            // Mettre à jour l'affichage du badge service
            const lbl = document.getElementById('currentServiceLabel');
            if (lbl) lbl.textContent = data.nom_service;

            const badge = document.getElementById('currentServiceBadge');
            if (badge) {
                badge.style.background = '#fff7ed';
                badge.style.borderColor = '#fed7aa';
                badge.style.color = '#c2410c';
            }

            // Afficher l'indicateur TEMP
            const tempInd = document.getElementById('tempServiceIndicator');
            if (tempInd) { tempInd.style.display = 'inline-flex'; }

            // Afficher le bouton retour
            const btnRetour = document.getElementById('btnRetourServiceOrigine');
            if (btnRetour) { btnRetour.style.removeProperty('display'); }

            // Fermer le modal et laisser le routage /dashboard décider du bon cockpit
            getModalCS().hide();
            setTimeout(() => { location.href = BASE_URL + 'dashboard'; }, 300);
        })
        .catch(() => {
            err.textContent = 'Erreur réseau. Veuillez réessayer.';
            err.classList.remove('d-none');
            document.querySelectorAll('.service-chgt-btn').forEach(b => b.disabled = false);
        });
    };

    window.retournerServiceOrigine = function() {
        const err = document.getElementById('changerServiceErr');
        err.classList.add('d-none');

        fetch(BASE_URL + 'dashboard/retourner-service-origine', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: ''
        })
        .then(r => r.json())
        .then(data => {
            if (!data.success) {
                err.textContent = data.message || 'Erreur.';
                err.classList.remove('d-none');
                return;
            }
            getModalCS().hide();
            setTimeout(() => { location.href = BASE_URL + 'dashboard'; }, 300);
        })
        .catch(() => {
            err.textContent = 'Erreur réseau.';
            err.classList.remove('d-none');
        });
    };
})();
</script>

<!-- ═══════════════════════════════════════════════════════════════
     MODAL — CHOISIR PATIENT POUR ORDONNANCE
════════════════════════════════════════════════════════════════════ -->
<div class="modal fade" id="modalOrdonnancePatient" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered" style="max-width:560px;">
        <div class="modal-content border-0 shadow-lg" style="border-radius:18px;overflow:hidden;">
            <!-- Header -->
            <div class="modal-header border-0 pb-2"
                 style="background:linear-gradient(135deg,#4c1d95,#7c3aed);border-radius:18px 18px 0 0;padding:20px 24px 16px;">
                <div>
                    <h5 class="modal-title text-white fw-bold mb-0" style="font-size:1rem;">
                        <i class="bi bi-prescription2 me-2"></i>Nouvelle ordonnance
                    </h5>
                    <p class="mb-0 mt-1" style="font-size:.75rem;color:rgba(255,255,255,.7);">
                        Choisissez le patient pour qui rédiger l'ordonnance
                    </p>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body p-4">
                <!-- Barre de recherche -->
                <div class="position-relative mb-1">
                    <i class="bi bi-search position-absolute"
                       style="left:13px;top:50%;transform:translateY(-50%);color:#7c3aed;font-size:.9rem;pointer-events:none;"></i>
                    <input type="text" id="ordoSearchInput" class="form-control ps-4 fw-semibold"
                           placeholder="Nom, prénom ou numéro de dossier…"
                           autocomplete="off"
                           style="border-radius:10px;border:2px solid #ddd6fe;font-size:.85rem;">
                </div>
                <p class="text-muted mb-2" style="font-size:.7rem;padding-left:4px;">
                    <i class="bi bi-info-circle me-1"></i>
                    Les patients d'un autre service sont affichés mais non sélectionnables.
                </p>

                <!-- Résultats -->
                <div id="ordoResultats" style="max-height:380px;overflow-y:auto;border-radius:10px;">
                    <div class="text-center py-5 text-muted" style="font-size:.82rem;">
                        <i class="bi bi-person-lines-fill d-block mb-2" style="font-size:2.2rem;opacity:.2;"></i>
                        Commencez à saisir pour chercher
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════════════
     MODAL — NOUVEAU PATIENT (création rapide depuis dashboard médecin)
════════════════════════════════════════════════════════════════════ -->
<div class="modal fade" id="modalNouveauPatient" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg" style="border-radius:16px;overflow:clip;">
            <div class="modal-header border-0 pb-0"
                 style="background:linear-gradient(135deg,#1e40af,#3b82f6);border-radius:16px 16px 0 0;">
                <div>
                    <h5 class="modal-title text-white fw-bold mb-0">
                        <i class="bi bi-person-plus-fill me-2"></i>Nouveau Patient
                    </h5>
                    <p class="text-white opacity-75 small mb-0">Enregistrement rapide — le dossier sera ouvert immédiatement</p>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
                <div class="modal-body px-4 py-3">
                <form id="formNouveauPatientRapide">

                    <!-- ── Identité ─────────────────────────────────────── -->
                    <h6 class="fw-bold text-primary mb-2 pb-1 border-bottom">
                        <i class="bi bi-person-badge me-2"></i>Identité
                    </h6>
                    <div class="row g-2 mb-3">
                        <div class="col-md-5">
                            <label class="form-label small fw-semibold">Nom <span class="text-danger">*</span></label>
                            <input type="text" class="form-control text-uppercase" name="nom" id="npNom"
                                   placeholder="NOM DE FAMILLE" required autocomplete="off">
                        </div>
                        <div class="col-md-5">
                            <label class="form-label small fw-semibold">Prénom <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="prenom" id="npPrenom"
                                   placeholder="Prénom" required autocomplete="off">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small fw-semibold">Sexe <span class="text-danger">*</span></label>
                            <select class="form-select" name="sexe" id="npSexe" required>
                                <option value="M">M</option>
                                <option value="F">F</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Date de naissance</label>
                            <input type="date" class="form-control" name="date_naissance" id="npDdn">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold">Âge estimatif</label>
                            <div class="input-group">
                                <input type="number" class="form-control" name="age_estimatif" id="npAge"
                                       placeholder="—" min="0" max="120">
                                <span class="input-group-text small">ans</span>
                            </div>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label small fw-semibold">Lieu de naissance</label>
                            <input type="text" class="form-control" name="lieu_naissance" id="npLieuNaissance"
                                   placeholder="Ville / Région">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Nationalité</label>
                            <input type="text" class="form-control" name="nationalite" id="npNationalite"
                                   placeholder="Ex : Camerounaise" value="Camerounaise">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Situation matrimoniale</label>
                            <select class="form-select" name="situation_matrimoniale" id="npSituation">
                                <option value="">— Non renseigné —</option>
                                <option value="CELIBATAIRE">Célibataire</option>
                                <option value="MARIE">Marié(e)</option>
                                <option value="DIVORCE">Divorcé(e)</option>
                                <option value="VEUF">Veuf / Veuve</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Profession</label>
                            <input type="text" class="form-control" name="profession" id="npProfession"
                                   placeholder="Ex : Enseignant">
                        </div>
                    </div>

                    <!-- ── Coordonnées ──────────────────────────────────── -->
                    <h6 class="fw-bold text-primary mb-2 pb-1 border-bottom">
                        <i class="bi bi-telephone me-2"></i>Coordonnées
                    </h6>
                    <div class="row g-2 mb-3">
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Téléphone</label>
                            <input type="text" class="form-control" name="telephone" id="npTel"
                                   placeholder="Ex : 699 00 00 00">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Téléphone 2</label>
                            <input type="text" class="form-control" name="telephone2" id="npTel2"
                                   placeholder="Optionnel">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Email</label>
                            <input type="email" class="form-control" name="email" id="npEmail"
                                   placeholder="patient@example.com">
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Adresse</label>
                            <input type="text" class="form-control" name="adresse" id="npAdresse"
                                   placeholder="Quartier, Ville">
                        </div>
                    </div>

                    <!-- ── Informations médicales ───────────────────────── -->
                    <h6 class="fw-bold text-primary mb-2 pb-1 border-bottom">
                        <i class="bi bi-heart-pulse me-2"></i>Informations médicales
                    </h6>
                    <div class="row g-2 mb-3">
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Groupe sanguin</label>
                            <select class="form-select" name="groupe_sanguin" id="npGroupeSanguin">
                                <option value="">— Inconnu —</option>
                                <option value="A+">A+</option>
                                <option value="A-">A-</option>
                                <option value="B+">B+</option>
                                <option value="B-">B-</option>
                                <option value="AB+">AB+</option>
                                <option value="AB-">AB-</option>
                                <option value="O+">O+</option>
                                <option value="O-">O-</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Prise en charge</label>
                            <select class="form-select" name="type_client" id="npTypeClient">
                                <option value="PAYANT_COMPTANT">Payant comptant</option>
                                <option value="BON_PRISE_EN_CHARGE">Bon de prise en charge</option>
                                <option value="ASSURANCE">Assurance</option>
                                <option value="FAMILLE_PHP">Famille PHP</option>
                                <option value="AGENTS_PHP">Agents PHP</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Allergies connues</label>
                            <input type="text" class="form-control" name="allergies" id="npAllergies"
                                   placeholder="Ex : Pénicilline, Aspirine">
                        </div>
                    </div>

                    <!-- ── Contact d'urgence ────────────────────────────── -->
                    <h6 class="fw-bold text-primary mb-2 pb-1 border-bottom">
                        <i class="bi bi-person-lines-fill me-2"></i>Contact d'urgence
                    </h6>
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Nom du contact</label>
                            <input type="text" class="form-control" name="contact_nom" id="npContactNom"
                                   placeholder="Nom &amp; Prénom">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold">Lien</label>
                            <select class="form-select" name="contact_lien" id="npContactLien">
                                <option value="">—</option>
                                <option value="Conjoint(e)">Conjoint(e)</option>
                                <option value="Père">Père</option>
                                <option value="Mère">Mère</option>
                                <option value="Frère / Sœur">Frère / Sœur</option>
                                <option value="Enfant">Enfant</option>
                                <option value="Tuteur">Tuteur</option>
                                <option value="Ami(e)">Ami(e)</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold">Téléphone</label>
                            <input type="text" class="form-control" name="contact_telephone" id="npContactTel"
                                   placeholder="699 00 00 00">
                        </div>
                    </div>

                    <div class="alert alert-info border-0 rounded-3 py-2 small mb-0">
                        <i class="bi bi-info-circle-fill me-2"></i>
                        Le dossier sera créé et ouvert immédiatement. Les champs non renseignés pourront être complétés depuis le dossier.
                    </div>
                </form>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-4"
                            data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" form="formNouveauPatientRapide" id="btnCreerPatientRapide"
                            class="btn btn-primary rounded-pill px-5 fw-semibold">
                        <i class="bi bi-person-plus-fill me-2"></i>Créer &amp; Ouvrir le dossier
                    </button>
                </div>
        </div>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════════════
     MODAL — RECHERCHER PATIENT
════════════════════════════════════════════════════════════════════ -->
<div class="modal fade" id="modalRecherchePatient" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered" style="max-width:540px;">
        <div class="modal-content border-0 shadow-lg" style="border-radius:16px;overflow:hidden;">
            <div class="modal-header border-0 pb-0"
                 style="background:linear-gradient(135deg,#0f172a,#1e293b);border-radius:16px 16px 0 0;">
                <div>
                    <h5 class="modal-title text-white fw-bold mb-0">
                        <i class="bi bi-search me-2"></i>Rechercher un patient
                    </h5>
                    <p class="text-white opacity-75 small mb-0">Nom, prénom ou numéro de dossier</p>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-3">
                <div class="position-relative mb-3">
                    <i class="bi bi-search position-absolute"
                       style="left:14px;top:50%;transform:translateY(-50%);color:#94a3b8;font-size:.9rem;pointer-events:none;"></i>
                    <input type="text" id="rechercheInput" class="form-control ps-4"
                           placeholder="Tapez au moins 2 caractères…"
                           autocomplete="off" style="border-radius:10px;">
                </div>
                <div id="rechercheResultats" style="max-height:360px;overflow-y:auto;">
                    <div class="text-center py-4 text-muted small">
                        <i class="bi bi-person-lines-fill d-block" style="font-size:2rem;opacity:.3;"></i>
                        Commencez à saisir pour chercher
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    let _modalNP, _modalRP;

    // Rôle du médecin connecté (utilisé pour le bouton "Consulter" dans la recherche)
    const ROLE_MEDECIN = '<?= htmlspecialchars($_SESSION['user_role'] ?? '', ENT_QUOTES) ?>';
    const PEUT_CONSULTER_PARAMS = !['PEDIATRE','GYNECO'].includes(ROLE_MEDECIN);

    document.addEventListener('DOMContentLoaded', function() {
        _modalNP = new bootstrap.Modal(document.getElementById('modalNouveauPatient'));
        _modalRP = new bootstrap.Modal(document.getElementById('modalRecherchePatient'));

        // ── Formulaire création rapide ─────────────────────────────────
        document.getElementById('formNouveauPatientRapide').addEventListener('submit', function(e) {
            e.preventDefault();
            soumettreCréationPatient(this, document.getElementById('btnCreerPatientRapide'), _modalNP, 'formNouveauPatientRapide');
        });

        // ── Recherche en temps réel ────────────────────────────────────
        let _searchTimer = null;
        document.getElementById('rechercheInput').addEventListener('input', function() {
            clearTimeout(_searchTimer);
            const q = this.value.trim();
            const zone = document.getElementById('rechercheResultats');

            if (q.length < 2) {
                zone.innerHTML = '<div class="text-center py-4 text-muted small"><i class="bi bi-person-lines-fill d-block" style="font-size:2rem;opacity:.3;"></i>Commencez à saisir pour chercher</div>';
                return;
            }

            zone.innerHTML = '<div class="text-center py-4 text-muted small"><span class="spinner-border spinner-border-sm me-2"></span>Recherche…</div>';

            _searchTimer = setTimeout(() => {
                fetch('<?= BASE_URL ?>patients/recherche?q=' + encodeURIComponent(q), {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                })
                .then(r => r.json())
                .then(data => {
                    if (!data.success || !data.patients.length) {
                        zone.innerHTML = '<div class="text-center py-4 text-muted small"><i class="bi bi-person-x d-block" style="font-size:2rem;opacity:.3;"></i>Aucun patient trouvé</div>';
                        return;
                    }
                    const statutColors = {
                        'ACCUEIL': '#64748b', 'PARAMETRES': '#d97706', 'ATTENTE_CONSULTATION': '#1e40af',
                        'EN_CONSULTATION': '#16a34a', 'HOSPITALISE': '#7c3aed', 'SORTI': '#94a3b8'
                    };
                    const statutLabels = {
                        'ACCUEIL': 'Accueil', 'PARAMETRES': 'Paramètres', 'ATTENTE_CONSULTATION': 'En attente',
                        'EN_CONSULTATION': 'En consultation', 'HOSPITALISE': 'Hospitalisé', 'SORTI': 'Sorti'
                    };
                    let html = '';
                    data.patients.forEach(p => {
                        const ini = (p.nom.charAt(0) + (p.prenom.charAt(0) || '')).toUpperCase();
                        const color = statutColors[p.statut_parcours] || '#64748b';
                        const label = statutLabels[p.statut_parcours] || p.statut_parcours;
                        const showConsulterBtn = PEUT_CONSULTER_PARAMS && p.statut_parcours === 'PARAMETRES';
                        const consulterUrl = `<?= BASE_URL ?>consultation/formulaire?patient_id=${p.id}&type=EXTERNE&etape=1`;

                        html += `<div style="display:flex;align-items:center;gap:12px;padding:10px 12px;border-radius:10px;transition:background .15s;"
                                     onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background=''">
                            <a href="<?= BASE_URL ?>patients/dossier/${p.id}" onclick="_modalRP.hide()"
                               class="text-decoration-none" style="display:flex;align-items:center;gap:12px;flex:1;min-width:0;color:#1e293b;">
                                <div style="width:38px;height:38px;border-radius:10px;background:linear-gradient(135deg,#1e40af,#3b82f6);
                                            display:flex;align-items:center;justify-content:center;
                                            color:#fff;font-size:.75rem;font-weight:700;flex-shrink:0;">${ini}</div>
                                <div style="flex:1;min-width:0;">
                                    <div style="font-weight:700;font-size:.85rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                                        ${escH(p.nom)} ${escH(p.prenom)}
                                    </div>
                                    <div style="font-size:.72rem;color:#94a3b8;">
                                        ${escH(p.dossier_numero)} · ${p.date_naissance || '?'}
                                        ${p.telephone ? ' · ' + escH(p.telephone) : ''}
                                    </div>
                                </div>
                                <span style="font-size:.68rem;font-weight:700;padding:2px 8px;border-radius:20px;
                                             background:${color}18;color:${color};white-space:nowrap;">${label}</span>
                            </a>
                            ${showConsulterBtn ? `
                            <a href="${consulterUrl}" onclick="_modalRP.hide()"
                               style="flex-shrink:0;background:#0891b2;color:#fff;font-size:.72rem;font-weight:700;
                                      padding:5px 11px;border-radius:20px;text-decoration:none;white-space:nowrap;
                                      display:flex;align-items:center;gap:4px;transition:background .15s;"
                               onmouseover="this.style.background='#0e7490'" onmouseout="this.style.background='#0891b2'"
                               title="Commencer la consultation">
                                <i class="bi bi-stethoscope"></i> Consulter
                            </a>` : ''}
                        </div>`;
                    });
                    zone.innerHTML = html;
                })
                .catch(() => {
                    zone.innerHTML = '<p class="text-danger text-center small p-3">Erreur réseau.</p>';
                });
            }, 300);
        });

        // Vider la recherche à l'ouverture
        document.getElementById('modalRecherchePatient').addEventListener('shown.bs.modal', function() {
            document.getElementById('rechercheInput').value = '';
            document.getElementById('rechercheResultats').innerHTML = '<div class="text-center py-4 text-muted small"><i class="bi bi-person-lines-fill d-block" style="font-size:2rem;opacity:.3;"></i>Commencez à saisir pour chercher</div>';
            document.getElementById('rechercheInput').focus();
        });

        // Réinitialiser le form à la fermeture
        document.getElementById('modalNouveauPatient').addEventListener('hidden.bs.modal', function() {
            document.getElementById('formNouveauPatientRapide').reset();
            const old = document.querySelector('#formNouveauPatientRapide .doublon-alert');
            if (old) old.remove();
            const btn = document.getElementById('btnCreerPatientRapide');
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-person-plus-fill me-2"></i>Créer & Ouvrir le dossier';
        });
    });

    window.ouvrirNouveauPatient   = function() { _modalNP.show(); };
    window.ouvrirRecherchePatient = function() { _modalRP.show(); };

    // ── Création avec gestion doublons ────────────────────────────
    window.soumettreCréationPatient = function(form, btn, modal, formId) {
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Vérification…';

        const fd = new FormData(form);
        // Supprimer flag force si présent (il sera ajouté explicitement si confirmé)
        fd.delete('force');

        fetch('<?= BASE_URL ?>patients/creer-rapide', {
            method: 'POST', body: fd,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                modal.hide();
                window.location.href = data.redirect;
                return;
            }
            if (data.duplicate_warning) {
                // Afficher les doublons dans le formulaire
                afficherDoublons(data.doublons, form, btn, modal);
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-person-plus-fill me-2"></i>Créer & Ouvrir le dossier';
                return;
            }
            alert('❌ ' + (data.message || 'Erreur lors de la création.'));
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-person-plus-fill me-2"></i>Créer & Ouvrir le dossier';
        })
        .catch(() => {
            alert('Erreur réseau.');
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-person-plus-fill me-2"></i>Créer & Ouvrir le dossier';
        });
    };

    window.afficherDoublons = function(doublons, form, btn, modal) {
        // Enlever ancienne alerte si présente
        const old = form.querySelector('.doublon-alert');
        if (old) old.remove();

        let html = `<div class="doublon-alert" style="
            background:#fff7ed;border:1.5px solid #f97316;border-radius:12px;
            padding:14px 16px;margin-bottom:12px;font-size:.82rem;">
            <div style="font-weight:700;color:#c2410c;margin-bottom:8px;">
                <i class="bi bi-exclamation-triangle-fill me-1"></i>
                Doublon détecté — ${doublons.length} patient(s) avec ce nom existent déjà
            </div>
            <div style="display:flex;flex-direction:column;gap:6px;margin-bottom:10px;">`;

        doublons.forEach(p => {
            const ddn = p.date_naissance && p.date_naissance !== '1900-01-01'
                ? new Date(p.date_naissance).toLocaleDateString('fr-FR') : '—';
            html += `<div style="display:flex;align-items:center;justify-content:space-between;
                              background:#fff;border-radius:8px;padding:8px 12px;border:1px solid #fed7aa;">
                <span style="font-weight:700;">${escH(p.nom)} ${escH(p.prenom)}</span>
                <span style="color:#64748b;font-size:.75rem;">${escH(p.dossier_numero)} · ${ddn}</span>
                <a href="<?= BASE_URL ?>patients/dossier/${p.id}"
                   onclick="modal.hide();"
                   style="background:#f97316;color:#fff;padding:3px 10px;border-radius:20px;
                          font-size:.72rem;font-weight:700;text-decoration:none;"
                   target="_blank">Ouvrir</a>
            </div>`;
        });

        html += `</div>
            <div style="display:flex;gap:8px;flex-wrap:wrap;">
                <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3"
                        onclick="this.closest('.doublon-alert').remove();">
                    <i class="bi bi-arrow-left me-1"></i>Modifier le formulaire
                </button>
                <button type="button" class="btn btn-sm rounded-pill px-3 fw-bold"
                        style="background:#f97316;color:#fff;"
                        onclick="forcerCreation(this.closest('form'), '${btn.id}', modal)">
                    <i class="bi bi-person-plus-fill me-1"></i>Créer quand même (nouveau dossier)
                </button>
            </div>
        </div>`;

        form.insertAdjacentHTML('afterbegin', html);
    };

    window.forcerCreation = function(form, btnId, modal) {
        const fd = new FormData(form);
        fd.append('force', '1');
        const btn = document.getElementById(btnId);
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Création…';

        fetch('<?= BASE_URL ?>patients/creer-rapide', {
            method: 'POST', body: fd,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                modal.hide();
                window.location.href = data.redirect;
            } else {
                alert('❌ ' + (data.message || 'Erreur.'));
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-person-plus-fill me-2"></i>Créer & Ouvrir le dossier';
            }
        })
        .catch(() => { alert('Erreur réseau.'); btn.disabled = false; });
    };

    // ── Modal Ordonnance ──────────────────────────────────────────
    const SESSION_SERVICE_ID = <?= (int)($_SESSION['service_id'] ?? 0) ?>;
    let _modalOrdo;
    document.addEventListener('DOMContentLoaded', function() {
        _modalOrdo = new bootstrap.Modal(document.getElementById('modalOrdonnancePatient'));

        // Vider à l'ouverture
        document.getElementById('modalOrdonnancePatient').addEventListener('shown.bs.modal', function() {
            const inp = document.getElementById('ordoSearchInput');
            inp.value = '';
            document.getElementById('ordoResultats').innerHTML =
                '<div class="text-center py-5 text-muted" style="font-size:.82rem;"><i class="bi bi-person-lines-fill d-block mb-2" style="font-size:2.2rem;opacity:.2;"></i>Commencez à saisir pour chercher</div>';
            inp.focus();
        });

        let _ordoTimer = null;
        document.getElementById('ordoSearchInput').addEventListener('input', function() {
            clearTimeout(_ordoTimer);
            const q = this.value.trim();
            const zone = document.getElementById('ordoResultats');
            if (q.length < 2) {
                zone.innerHTML = '<div class="text-center py-5 text-muted" style="font-size:.82rem;"><i class="bi bi-person-lines-fill d-block mb-2" style="font-size:2.2rem;opacity:.2;"></i>Commencez à saisir pour chercher</div>';
                return;
            }
            zone.innerHTML = '<div class="text-center py-4 text-muted small"><span class="spinner-border spinner-border-sm me-2" style="color:#7c3aed;"></span>Recherche…</div>';
            _ordoTimer = setTimeout(() => {
                fetch('<?= BASE_URL ?>patients/recherche?q=' + encodeURIComponent(q), { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(r => r.json())
                .then(data => {
                    if (!data.success || !data.patients.length) {
                        zone.innerHTML = '<div class="text-center py-4 text-muted small"><i class="bi bi-person-x d-block mb-1" style="font-size:2rem;opacity:.3;"></i>Aucun patient trouvé</div>';
                        return;
                    }
                    let html = '';
                    data.patients.forEach(p => {
                        const ini = (p.nom.charAt(0) + (p.prenom.charAt(0) || '')).toUpperCase();
                        const meme = !SESSION_SERVICE_ID || parseInt(p.service_id) === SESSION_SERVICE_ID;
                        const serviceLabel = p.nom_service ? escH(p.nom_service) : 'Service inconnu';
                        if (meme) {
                            html += `<a href="<?= BASE_URL ?>prescription/create?patient_id=${p.id}"
                                style="display:flex;align-items:center;gap:12px;padding:11px 14px;border-radius:10px;
                                       cursor:pointer;text-decoration:none;color:#1e293b;border:1.5px solid transparent;
                                       transition:all .15s;margin-bottom:4px;background:#fff;"
                                onmouseover="this.style.background='#faf5ff';this.style.borderColor='#c4b5fd';"
                                onmouseout="this.style.background='#fff';this.style.borderColor='transparent';">
                                <div style="width:40px;height:40px;border-radius:10px;flex-shrink:0;
                                            background:linear-gradient(135deg,#4c1d95,#7c3aed);
                                            display:flex;align-items:center;justify-content:center;
                                            color:#fff;font-size:.78rem;font-weight:800;">${ini}</div>
                                <div style="flex:1;min-width:0;">
                                    <div style="font-weight:700;font-size:.88rem;">${escH(p.nom)} ${escH(p.prenom)}</div>
                                    <div style="font-size:.72rem;color:#94a3b8;">${escH(p.dossier_numero)} · ${p.date_naissance || '?'}</div>
                                </div>
                                <div style="text-align:right;flex-shrink:0;">
                                    <span style="font-size:.68rem;font-weight:700;padding:2px 9px;border-radius:20px;
                                                 background:#f0fdf4;color:#16a34a;border:1px solid #bbf7d0;">
                                        <i class="bi bi-check-circle-fill me-1"></i>Mon service
                                    </span>
                                </div>
                            </a>`;
                        } else {
                            html += `<div style="display:flex;align-items:center;gap:12px;padding:11px 14px;border-radius:10px;
                                          opacity:.45;cursor:not-allowed;margin-bottom:4px;background:#f8fafc;
                                          border:1.5px solid #e2e8f0;" title="Patient rattaché à : ${serviceLabel} — non accessible">
                                <div style="width:40px;height:40px;border-radius:10px;flex-shrink:0;
                                            background:#e2e8f0;
                                            display:flex;align-items:center;justify-content:center;
                                            color:#94a3b8;font-size:.78rem;font-weight:800;">${ini}</div>
                                <div style="flex:1;min-width:0;">
                                    <div style="font-weight:700;font-size:.88rem;color:#64748b;">${escH(p.nom)} ${escH(p.prenom)}</div>
                                    <div style="font-size:.72rem;color:#94a3b8;">${escH(p.dossier_numero)} · ${p.date_naissance || '?'}</div>
                                </div>
                                <div style="text-align:right;flex-shrink:0;">
                                    <span style="font-size:.66rem;font-weight:700;padding:2px 9px;border-radius:20px;
                                                 background:#f1f5f9;color:#94a3b8;border:1px solid #e2e8f0;">
                                        <i class="bi bi-lock-fill me-1"></i>${serviceLabel}
                                    </span>
                                </div>
                            </div>`;
                        }
                    });
                    zone.innerHTML = html;
                })
                .catch(() => { zone.innerHTML = '<p class="text-danger text-center small p-3">Erreur réseau.</p>'; });
            }, 300);
        });
    });
    window.ouvrirOrdonnancePatient = function() { _modalOrdo.show(); };

    function escH(s) {
        if (!s) return '';
        return String(s).replace(/[&<>"']/g, c =>
            ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[c]));
    }
})();
</script>

<!-- ══════════════════════════════════════════════════════
     MODAL SUIVI INFIRMIER — Vue rapide pour le médecin
══════════════════════════════════════════════════════ -->
<div class="modal fade" id="modalSuiviInfirmier" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg" style="border-radius:18px;overflow:hidden;">

            <!-- Header -->
            <div class="modal-header border-0 p-0">
                <div class="w-100 px-4 py-3 d-flex align-items-center gap-3"
                     style="background:linear-gradient(135deg,#0f172a,#0d9488);">
                    <div style="width:46px;height:46px;border-radius:13px;background:rgba(255,255,255,.15);
                                display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <i class="bi bi-activity text-white fs-4"></i>
                    </div>
                    <div>
                        <h5 class="fw-bold text-white mb-0" id="siModalTitle">Suivi Infirmier</h5>
                        <small id="siModalSub" style="color:rgba(255,255,255,.6);">Paramètres vitaux &amp; observations infirmières</small>
                    </div>
                    <button type="button" class="btn-close btn-close-white ms-auto" data-bs-dismiss="modal"></button>
                </div>
            </div>

            <!-- Body -->
            <div class="modal-body p-4" id="siModalBody">
                <div class="text-center py-5">
                    <div class="spinner-border text-teal" style="color:#0d9488;width:2.5rem;height:2.5rem;"></div>
                    <div class="mt-3 text-muted fw-semibold" style="font-size:.9rem;">Chargement des données infirmières…</div>
                </div>
            </div>

            <!-- Panneau CAT rapide (caché par défaut) -->
            <div id="siCatPanel" style="display:none;padding:18px 24px 10px;
                 background:linear-gradient(135deg,#f0fdfa,#ecfdf5);
                 border-top:2px solid #99f6e4;">
                <div style="font-size:.87rem;font-weight:800;color:#0f766e;margin-bottom:12px;display:flex;align-items:center;gap:8px;">
                    <div style="width:30px;height:30px;border-radius:8px;background:#0d9488;display:flex;align-items:center;justify-content:center;">
                        <i class="bi bi-list-check text-white" style="font-size:.9rem;"></i>
                    </div>
                    Ajouter une Conduite à tenir
                </div>

                <div class="row g-2 mb-2">
                    <div class="col-md-5">
                        <label style="font-size:.68rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.5px;display:block;margin-bottom:4px;">
                            État du patient
                        </label>
                        <select id="siCatEvol" class="form-select form-select-sm" style="border-radius:8px;border:1.5px solid #99f6e4;font-size:.82rem;">
                            <option value="STATUO_QUO">— Statuo quo</option>
                            <option value="FAVORABLE">✅ Favorable</option>
                            <option value="AMELIORATION_PARTIELLE">📈 Amélioration partielle</option>
                            <option value="NON_FAVORABLE">📉 Non favorable</option>
                            <option value="AGGRAVATION">⚠️ Aggravation</option>
                            <option value="CRITIQUE">🚨 Critique</option>
                        </select>
                    </div>
                    <div class="col-md-7">
                        <label style="font-size:.68rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.5px;display:block;margin-bottom:4px;">
                            Diagnostic du jour <span style="color:#94a3b8;font-weight:400;">(optionnel)</span>
                        </label>
                        <input type="text" id="siCatDiag" class="form-control form-control-sm"
                               style="border-radius:8px;border:1.5px solid #99f6e4;font-size:.82rem;"
                               placeholder="Ex : Pneumopathie infectieuse, J18...">
                    </div>
                </div>

                <div class="mb-2">
                    <label style="font-size:.68rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.5px;display:block;margin-bottom:4px;">
                        Conduite à tenir <span style="color:#ef4444;">*</span>
                    </label>
                    <textarea id="siCatText" rows="4" class="form-control"
                              style="border-radius:8px;border:1.5px solid #99f6e4;font-size:.83rem;resize:vertical;"
                              placeholder="• Poursuivre antibiothérapie&#10;• Surveillance horaire de la diurèse&#10;• Repos strict au lit&#10;• Contrôle biologique demain matin..."></textarea>
                </div>

                <div class="mb-3">
                    <label style="font-size:.68rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.5px;display:block;margin-bottom:4px;">
                        Note d'évolution <span style="color:#94a3b8;font-weight:400;">(optionnel)</span>
                    </label>
                    <textarea id="siCatNoteEvol" rows="2" class="form-control"
                              style="border-radius:8px;border:1.5px solid #99f6e4;font-size:.82rem;resize:vertical;"
                              placeholder="Observations cliniques..."></textarea>
                </div>

                <div id="siCatMsg" style="display:none;margin-bottom:10px;"></div>

                <div style="display:flex;justify-content:flex-end;gap:8px;">
                    <button type="button" onclick="siToggleCatPanel()"
                            class="btn btn-sm btn-outline-secondary rounded-pill px-3">
                        Annuler
                    </button>
                    <button type="button" onclick="siSaveCat()" id="siCatSaveBtn"
                            class="btn btn-sm rounded-pill px-4 fw-bold"
                            style="background:#0d9488;color:#fff;box-shadow:0 2px 8px rgba(13,148,136,.3);">
                        <i class="bi bi-check-lg me-1"></i>Sauvegarder
                    </button>
                </div>
            </div>

            <!-- Footer -->
            <div class="modal-footer border-0 px-4 pb-4 pt-2" style="gap:8px;justify-content:space-between;">
                <button class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">Fermer</button>
                <div style="display:flex;gap:8px;align-items:center;">
                    <button type="button" id="siCatToggleBtn" onclick="siToggleCatPanel()"
                            class="btn rounded-pill px-4 fw-bold"
                            style="background:#0d9488;color:#fff;box-shadow:0 2px 8px rgba(13,148,136,.25);">
                        <i class="bi bi-list-check me-1"></i>Ajouter Conduite à tenir
                    </button>
                    <a id="siLinkDossier" href="#" class="btn rounded-pill px-4 fw-bold"
                       style="background:#1e40af;color:#fff;">
                        <i class="bi bi-folder2-open me-1"></i>Ouvrir le dossier complet
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="<?= BASE_URL ?>public/js/chart.umd.js"></script>
<script>
(function() {
    const BASE = '<?= BASE_URL ?>';
    let _siChartTemp = null;
    let _siChartTA   = null;

    window.ouvrirSuiviInfirmier = function(patientId, nomComplet) {
        // Titre + lien dossier
        document.getElementById('siModalTitle').textContent = 'Suivi Infirmier — ' + nomComplet;
        document.getElementById('siLinkDossier').href = BASE + 'patients/dossier/' + patientId;

        // Afficher le spinner
        document.getElementById('siModalBody').innerHTML = `
            <div class="text-center py-5">
                <div class="spinner-border" style="color:#0d9488;width:2.5rem;height:2.5rem;"></div>
                <div class="mt-3 text-muted fw-semibold" style="font-size:.9rem;">Chargement des données infirmières…</div>
            </div>`;

        // Ouvrir le modal
        new bootstrap.Modal(document.getElementById('modalSuiviInfirmier')).show();

        // Détruire anciens graphiques
        if (_siChartTemp) { _siChartTemp.destroy(); _siChartTemp = null; }
        if (_siChartTA)   { _siChartTA.destroy();   _siChartTA   = null; }

        // Fetch données
        fetch(BASE + 'dashboard/suivi-infirmier/' + patientId)
            .then(r => r.json())
            .then(renderSuivi)
            .catch(() => {
                document.getElementById('siModalBody').innerHTML =
                    '<div class="alert alert-danger m-4">Erreur lors du chargement des données.</div>';
            });
    };

    function v(val, def) { return (val !== null && val !== undefined && val !== '' && parseFloat(val) !== 0) ? val : def; }

    function alertClass(type, val) {
        val = parseFloat(val);
        if (!val) return '';
        const rules = {
            temp:    [[38.5, Infinity, 'danger'], [38, Infinity, 'warning']],
            spo2:    [[-Infinity, 90, 'danger'], [-Infinity, 94, 'warning']],
            fc:      [[120, Infinity, 'danger'], [-Infinity, 40, 'danger'], [100, Infinity, 'warning']],
            ta_sys:  [[180, Infinity, 'danger'], [-Infinity, 80, 'danger'], [140, Infinity, 'warning']],
        };
        for (const [low, high, cls] of (rules[type] || [])) {
            if (val >= low && val <= high) return cls;
        }
        return '';
    }

    function alertBadge(type, val) {
        const cls = alertClass(type, val);
        if (cls === 'danger')  return '<span class="badge ms-1" style="background:#ef4444;font-size:.6rem;">⚠ Critique</span>';
        if (cls === 'warning') return '<span class="badge ms-1" style="background:#f59e0b;font-size:.6rem;">Élevé</span>';
        return '';
    }

    function renderSuivi(data) {
        if (!data.success) {
            document.getElementById('siModalBody').innerHTML =
                '<div class="alert alert-danger m-4">' + escH(data.message) + '</div>';
            return;
        }

        const d             = data.dernieres;
        const obs           = data.observations;
        const hist          = data.historique;
        const plaintes      = data.plaintes      || [];
        const soins         = data.soins         || [];
        const reevaluations = data.reevaluations || [];
        const douleur       = data.douleur       || [];

        /* ── Vitaux ── */
        const vitals = [
            { label:'Température',      val: v(d.temperature,'—'),                      unit:'°C',   icon:'bi-thermometer-half',   color:'#ef4444', type:'temp'  },
            { label:'Tension artérielle',val: d.pression_arterielle_systolique ? d.pression_arterielle_systolique+'/'+d.pression_arterielle_diastolique : '—', unit:'mmHg', icon:'bi-activity',          color:'#3b82f6', type:'ta_sys'},
            { label:'Fréquence cardiaque',val:v(d.frequence_cardiaque,'—'),              unit:'bpm',  icon:'bi-heart-fill',          color:'#a855f7', type:'fc'   },
            { label:'SpO₂',             val: v(d.saturation_oxygene,'—'),                unit:'%',    icon:'bi-lungs-fill',          color:'#22c55e', type:'spo2' },
            { label:'Fréq. respiratoire',val:v(d.frequence_respiratoire,'—'),            unit:'c/min',icon:'bi-wind',                color:'#0d9488', type:''     },
            { label:'Glycémie capillaire',val:v(d.glycemie,'—'),                         unit:'g/L',  icon:'bi-droplet-fill',        color:'#f59e0b', type:''     },
            { label:'Diurèse',           val: v(d.diurese,'—'),                          unit:'mL',   icon:'bi-water',               color:'#0ea5e9', type:''     },
        ];
        if (parseInt(d.sous_oxygene)) {
            vitals.push({ label:'Oxygénothérapie', val:v(d.debit_oxygene,'?'), unit:'L/min', icon:'bi-mask', color:'#6366f1', type:'' });
        }

        let infLabel = '';
        if (d.inf_nom) {
            infLabel = `<small style="color:#64748b;font-size:.72rem;">
                <i class="bi bi-person-fill me-1"></i>
                Saisi par : <strong>${escH((d.inf_prenom||'')+' '+(d.inf_nom||''))}</strong>
                &nbsp;•&nbsp; ${d.date_mesure ? new Date(d.date_mesure).toLocaleString('fr-FR',{day:'2-digit',month:'2-digit',year:'numeric',hour:'2-digit',minute:'2-digit'}) : ''}
            </small>`;
        }

        let vitalsHtml = `
        <div class="mb-4">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <h6 class="fw-bold mb-0" style="color:#1e293b;font-size:.9rem;">
                    <i class="bi bi-heart-pulse-fill me-2" style="color:#ef4444;"></i>Derniers Paramètres Vitaux
                </h6>
                ${infLabel}
            </div>`;

        if (!d || !d.patient_id) {
            vitalsHtml += `<div class="alert alert-light border rounded-3 text-muted" style="font-size:.85rem;">
                <i class="bi bi-info-circle me-2"></i>Aucun paramètre enregistré par l'infirmier pour ce patient.
            </div>`;
        } else {
            vitalsHtml += '<div class="row g-2">';
            vitals.forEach(vt => {
                const badge = alertBadge(vt.type, vt.val);
                const borderStyle = alertClass(vt.type, vt.val) === 'danger' ? 'border:2px solid #ef4444;' :
                                    alertClass(vt.type, vt.val) === 'warning' ? 'border:2px solid #f59e0b;' : '';
                vitalsHtml += `
                <div class="col-6 col-md-3">
                    <div style="background:#fff;border-radius:14px;padding:13px 14px;border:1px solid #e2e8f0;${borderStyle}box-shadow:0 2px 8px rgba(0,0,0,.04);">
                        <div style="display:flex;align-items:center;gap:8px;margin-bottom:6px;">
                            <div style="width:32px;height:32px;border-radius:9px;background:${vt.color}18;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                <i class="bi ${vt.icon}" style="color:${vt.color};font-size:.9rem;"></i>
                            </div>
                            <span style="font-size:.68rem;font-weight:700;color:#64748b;text-transform:uppercase;">${escH(vt.label)}</span>
                        </div>
                        <div style="font-size:1.25rem;font-weight:800;color:#1e293b;line-height:1;">
                            ${escH(String(vt.val))}
                            <span style="font-size:.72rem;font-weight:500;color:#94a3b8;">${vt.unit}</span>
                        </div>
                        ${badge}
                    </div>
                </div>`;
            });
            vitalsHtml += '</div>';
        }
        vitalsHtml += '</div>';

        /* ── Courbes ── */
        const chartsHtml = hist.length >= 2 ? `
        <div class="row g-3 mb-4">
            <div class="col-md-6">
                <div style="background:#fff;border-radius:14px;padding:16px;border:1px solid #e2e8f0;box-shadow:0 2px 8px rgba(0,0,0,.04);">
                    <div class="fw-bold mb-2" style="font-size:.8rem;color:#ef4444;display:flex;align-items:center;gap:6px;">
                        <span style="width:10px;height:10px;border-radius:50%;background:#ef4444;display:inline-block;"></span>
                        Courbe Température (°C)
                    </div>
                    <canvas id="siChartTemp" height="130"></canvas>
                </div>
            </div>
            <div class="col-md-6">
                <div style="background:#fff;border-radius:14px;padding:16px;border:1px solid #e2e8f0;box-shadow:0 2px 8px rgba(0,0,0,.04);">
                    <div class="fw-bold mb-2" style="font-size:.8rem;color:#3b82f6;display:flex;align-items:center;gap:6px;">
                        <span style="width:10px;height:10px;border-radius:50%;background:#3b82f6;display:inline-block;"></span>
                        Courbe Tension Artérielle (mmHg)
                    </div>
                    <canvas id="siChartTA" height="130"></canvas>
                </div>
            </div>
        </div>` : '';

        /* ── Observations (contenu du panneau) ── */
        let obsPaneHtml = '';
        if (obs.length === 0) {
            obsPaneHtml = `<div class="text-center py-4 text-muted" style="background:#f8fafc;border-radius:12px;font-size:.85rem;">
                <i class="bi bi-chat-square d-block fs-3 mb-2 opacity-30"></i>
                Aucune observation infirmière enregistrée.
            </div>`;
        } else {
            obs.forEach(o => {
                const dt = o.date_mesure
                    ? new Date(o.date_mesure).toLocaleString('fr-FR',{day:'2-digit',month:'2-digit',year:'numeric',hour:'2-digit',minute:'2-digit'})
                    : '—';
                const auteur = ((o.inf_prenom||'')+' '+(o.inf_nom||'')).trim() || 'Infirmier';
                obsPaneHtml += `
                <div style="background:#faf5ff;border-left:3px solid #7c3aed;border-radius:0 12px 12px 0;padding:12px 16px;margin-bottom:10px;">
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px;">
                        <div style="font-size:.72rem;font-weight:700;color:#7c3aed;">
                            <i class="bi bi-person-fill me-1"></i>${escH(auteur)}
                        </div>
                        <div style="font-size:.7rem;color:#94a3b8;">${dt}</div>
                    </div>
                    <div style="font-size:.84rem;color:#334155;line-height:1.6;">${escH(o.observations)}</div>
                </div>`;
            });
        }

        /* ── Plaintes (contenu du panneau) ── */
        let plaintesPaneHtml = '';
        if (plaintes.length === 0) {
            plaintesPaneHtml = `<div class="text-center py-4 text-muted" style="background:#fffbeb;border-radius:12px;font-size:.85rem;border:1px dashed #fde68a;">
                <i class="bi bi-emoji-smile d-block fs-3 mb-2 opacity-30"></i>
                Aucune plainte enregistrée pour ce patient.
            </div>`;
        } else {
            plaintes.forEach(p => {
                const dt = p.date_mesure
                    ? new Date(p.date_mesure).toLocaleString('fr-FR',{day:'2-digit',month:'2-digit',year:'numeric',hour:'2-digit',minute:'2-digit'})
                    : '—';
                const auteur = ((p.inf_prenom||'')+' '+(p.inf_nom||'')).trim() || 'Infirmier';
                plaintesPaneHtml += `
                <div style="background:#fffbeb;border-left:3px solid #f59e0b;border-radius:0 12px 12px 0;padding:12px 16px;margin-bottom:10px;">
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px;">
                        <div style="font-size:.72rem;font-weight:700;color:#b45309;">
                            <i class="bi bi-person-fill me-1"></i>${escH(auteur)}
                            <span style="background:#fef3c7;color:#92400e;font-size:.6rem;padding:1px 6px;border-radius:6px;margin-left:6px;font-weight:700;">Saisi par l'infirmier</span>
                        </div>
                        <div style="font-size:.7rem;color:#94a3b8;">${dt}</div>
                    </div>
                    <div style="font-size:.84rem;color:#78350f;line-height:1.6;font-style:italic;">"${escH(p.plaintes)}"</div>
                </div>`;
            });
        }

        /* ── Panneau Soins (groupes déroulants par date) ── */
        let soinsPaneHtml = '';
        if (soins.length === 0) {
            soinsPaneHtml = `<div class="text-center py-4 text-muted" style="background:#f8fafc;border-radius:12px;font-size:.85rem;">
                <i class="bi bi-clipboard2-pulse d-block fs-3 mb-2 opacity-30"></i>
                Aucun soin enregistré pour ce patient.
            </div>`;
        } else {
            const statutCfg = {
                REALISE:  { bg:'#dcfce7', color:'#166534', border:'#86efac', icon:'bi-check-circle-fill',  label:'Réalisé'  },
                PLANIFIE: { bg:'#dbeafe', color:'#1d4ed8', border:'#93c5fd', icon:'bi-clock-fill',         label:'Planifié' },
                RETARD:   { bg:'#fee2e2', color:'#991b1b', border:'#fca5a5', icon:'bi-exclamation-triangle-fill', label:'Retard' },
                ANNULE:   { bg:'#f1f5f9', color:'#64748b', border:'#cbd5e1', icon:'bi-x-circle-fill',      label:'Annulé'   },
                SUPPRIME: { bg:'#f1f5f9', color:'#94a3b8', border:'#e2e8f0', icon:'bi-trash-fill',         label:'Supprimé' },
            };

            // Grouper par date_prevue (jour)
            const groups = {};
            soins.forEach(s => {
                const dateKey = s.date_prevue ? s.date_prevue.slice(0,10) : 'sans_date';
                if (!groups[dateKey]) groups[dateKey] = [];
                groups[dateKey].push(s);
            });

            const today = new Date().toISOString().slice(0,10);
            const fmtDate = str => {
                if (!str || str === 'sans_date') return 'Date inconnue';
                const d = new Date(str + 'T00:00:00');
                const days   = ['Dimanche','Lundi','Mardi','Mercredi','Jeudi','Vendredi','Samedi'];
                const months = ['JANVIER','FÉVRIER','MARS','AVRIL','MAI','JUIN','JUILLET','AOÛT','SEPTEMBRE','OCTOBRE','NOVEMBRE','DÉCEMBRE'];
                return days[d.getDay()] + ' ' + d.getDate() + ' ' + months[d.getMonth()] + ' ' + d.getFullYear();
            };

            let grpIdx = 0;
            Object.keys(groups).sort((a,b) => b.localeCompare(a)).forEach(dateKey => {
                const items      = groups[dateKey];
                const nbRealises = items.filter(s => s.statut === 'REALISE').length;
                const nbRetard   = items.filter(s => s.statut === 'RETARD').length;
                const nbTotal    = items.length;
                const isToday    = dateKey === today;
                const isPast     = dateKey < today;
                // Aujourd'hui → ouvert ; passé/futur → fermé
                const openByDefault = isToday;
                const grpId      = 'siSoinGrp_' + grpIdx++;

                // Couleur de la ligne de séparation selon la date
                const headerBg    = isToday ? '#f0fdf4' : (isPast ? '#f8fafc' : '#eff6ff');
                const headerBorder= isToday ? '#16a34a' : (isPast ? '#94a3b8' : '#3b82f6');
                const headerColor = isToday ? '#166534' : (isPast ? '#475569' : '#1d4ed8');

                // Mini-barres de statut dans le header
                const miniBar = `
                    <span style="display:inline-flex;align-items:center;gap:6px;font-size:.68rem;font-weight:700;">
                        <span style="background:#dcfce7;color:#166534;padding:2px 7px;border-radius:6px;">${nbRealises} ✓</span>
                        ${nbRetard > 0 ? `<span style="background:#fee2e2;color:#991b1b;padding:2px 7px;border-radius:6px;">${nbRetard} retard</span>` : ''}
                        <span style="color:#94a3b8;">${nbTotal} soin${nbTotal>1?'s':''}</span>
                    </span>`;

                soinsPaneHtml += `
                <div style="margin-bottom:10px;border:1px solid ${headerBorder}40;border-radius:12px;overflow:hidden;">
                    <!-- En-tête cliquable -->
                    <button onclick="siToggleSoinGroup('${grpId}')"
                            style="width:100%;display:flex;align-items:center;justify-content:space-between;
                                   padding:11px 16px;border:none;cursor:pointer;text-align:left;
                                   background:${headerBg};border-bottom:2px solid ${headerBorder}40;
                                   transition:background .15s;">
                        <div style="display:flex;align-items:center;gap:10px;">
                            <div style="width:8px;height:8px;border-radius:50%;background:${headerBorder};flex-shrink:0;"></div>
                            <span style="font-size:.8rem;font-weight:800;color:${headerColor};letter-spacing:.3px;">
                                ${fmtDate(dateKey)}
                                ${isToday ? '<span style="background:#16a34a;color:#fff;font-size:.58rem;padding:1px 6px;border-radius:5px;margin-left:6px;font-weight:700;">AUJOURD\'HUI</span>' : ''}
                            </span>
                        </div>
                        <div style="display:flex;align-items:center;gap:10px;">
                            ${miniBar}
                            <i id="${grpId}_chev" class="bi bi-chevron-${openByDefault?'up':'down'}"
                               style="color:${headerColor};font-size:.75rem;transition:transform .2s;"></i>
                        </div>
                    </button>
                    <!-- Corps déroulant -->
                    <div id="${grpId}" style="padding:${openByDefault?'12px':'0'} 12px;max-height:${openByDefault?'2000px':'0'};
                                              overflow:hidden;transition:max-height .3s ease,padding .3s ease;background:#fff;">`;

                // ── Grouper les soins du jour par médicament (type_soin + description) ──
                const medicGroups = {};
                items.forEach(s => {
                    const key = (s.type_soin || 'Soin') + '||' + (s.description || '');
                    if (!medicGroups[key]) medicGroups[key] = [];
                    medicGroups[key].push(s);
                });

                Object.values(medicGroups).forEach(passes => {
                    // Trier les passages par heure prévue croissante
                    passes.sort((a, b) => (a.date_prevue||'').localeCompare(b.date_prevue||''));

                    const first    = passes[0];
                    const planif   = first.planif_nom ? escH((first.planif_prenom||'')+' '+first.planif_nom) : '—';
                    const nbTotal  = passes.length;
                    const nbRealise= passes.filter(p => p.statut==='REALISE').length;
                    const nbRetard = passes.filter(p => p.statut==='RETARD').length;

                    // Statut dominant de la carte
                    let domStatut = first.statut || 'PLANIFIE';
                    if (nbRetard > 0)              domStatut = 'RETARD';
                    else if (nbRealise === nbTotal) domStatut = 'REALISE';
                    const cfg = statutCfg[domStatut] || statutCfg.PLANIFIE;

                    // Chips d'heures (une par passage, colorée selon son statut)
                    const timeChips = passes.map(p => {
                        const pCfg = statutCfg[p.statut] || statutCfg.PLANIFIE;
                        const hP   = p.date_prevue   ? new Date(p.date_prevue).toLocaleTimeString('fr-FR',{hour:'2-digit',minute:'2-digit'}) : '—';
                        const hR   = p.date_realisee ? new Date(p.date_realisee).toLocaleTimeString('fr-FR',{hour:'2-digit',minute:'2-digit'}) : null;
                        return `<span title="${pCfg.label}${hR ? ' · Réalisé : '+hR : ''}"
                                     style="display:inline-flex;align-items:center;gap:3px;
                                            background:${pCfg.bg};border:1px solid ${pCfg.border};
                                            color:${pCfg.color};border-radius:7px;padding:3px 9px;
                                            font-size:.71rem;font-weight:700;white-space:nowrap;">
                            <i class="bi ${pCfg.icon}" style="font-size:.6rem;"></i>
                            ${hP}
                            ${hR ? `<i class="bi bi-check2" style="font-size:.65rem;color:#16a34a;"></i>` : ''}
                        </span>`;
                    }).join('');

                    // Notes (dédupliquées)
                    const notes     = [...new Set(passes.map(p=>p.note_execution).filter(Boolean))];
                    const notesMajor= [...new Set(passes.map(p=>p.note_major).filter(Boolean))];
                    const conds     = [...new Set(passes.map(p=>p.condition_application).filter(Boolean))];

                    // Exécutants distincts
                    const execs = [...new Set(passes.filter(p=>p.exec_nom).map(p=>escH((p.exec_prenom||'')+' '+p.exec_nom)))];

                    // Badge résumé (ex: "2/3 réalisés")
                    const resumeBadge = nbTotal > 1
                        ? `<span style="font-size:.6rem;font-weight:700;background:#e0f2fe;color:#0369a1;
                                        padding:1px 7px;border-radius:6px;margin-left:6px;">
                               ${nbRealise}/${nbTotal} réalisés
                           </span>`
                        : '';

                    soinsPaneHtml += `
                    <div style="background:#fff;border:1px solid ${cfg.border};border-left:4px solid ${cfg.color};
                                border-radius:0 10px 10px 0;padding:11px 14px;margin-bottom:8px;
                                display:flex;gap:12px;align-items:flex-start;">
                        <div style="flex-shrink:0;width:34px;height:34px;border-radius:9px;
                                    background:${cfg.bg};display:flex;align-items:center;justify-content:center;margin-top:2px;">
                            <i class="bi ${cfg.icon}" style="color:${cfg.color};font-size:.95rem;"></i>
                        </div>
                        <div style="flex:1;min-width:0;">
                            <!-- Ligne titre -->
                            <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;flex-wrap:wrap;margin-bottom:6px;">
                                <div style="display:flex;align-items:center;flex-wrap:wrap;gap:6px;">
                                    <span style="font-size:.83rem;font-weight:700;color:#0f172a;">${escH(first.type_soin||'Soin')}</span>
                                    <span style="background:${cfg.bg};color:${cfg.color};font-size:.6rem;font-weight:700;
                                                 padding:1px 7px;border-radius:8px;">${cfg.label}</span>
                                    ${resumeBadge}
                                </div>
                                ${nbTotal === 1
                                    ? `<div style="font-size:.7rem;color:#94a3b8;white-space:nowrap;">
                                           <i class="bi bi-clock me-1"></i>${passes[0].date_prevue ? new Date(passes[0].date_prevue).toLocaleTimeString('fr-FR',{hour:'2-digit',minute:'2-digit'}) : '—'}
                                       </div>`
                                    : ''}
                            </div>
                            <!-- Description du médicament -->
                            ${first.description ? `<div style="font-size:.82rem;color:#1e293b;font-weight:600;margin-bottom:6px;">${escH(first.description)}</div>` : ''}
                            <!-- Chips heures (si passages multiples) -->
                            ${nbTotal > 1
                                ? `<div style="display:flex;flex-wrap:wrap;gap:6px;margin-bottom:6px;">${timeChips}</div>`
                                : ''}
                            <!-- Conditions -->
                            ${conds.length ? `<div style="font-size:.72rem;color:#7c3aed;margin-bottom:4px;"><i class="bi bi-info-circle me-1"></i>${conds.map(escH).join(' · ')}</div>` : ''}
                            <!-- Planifié / exécutants -->
                            <div style="display:flex;gap:14px;flex-wrap:wrap;font-size:.68rem;color:#94a3b8;">
                                <span><i class="bi bi-person me-1"></i>Planifié par : <strong style="color:#475569;">${planif}</strong></span>
                                ${execs.length ? `<span><i class="bi bi-person-check me-1"></i>Exécuté par : <strong style="color:#166534;">${execs.join(', ')}</strong></span>` : ''}
                            </div>
                            <!-- Notes exécution -->
                            ${notes.map(n=>`
                            <div style="background:#f0fdf4;border-radius:6px;padding:6px 10px;margin-top:7px;font-size:.76rem;color:#166534;line-height:1.5;">
                                <i class="bi bi-journal-check me-1"></i><strong>Note :</strong> ${escH(n)}
                            </div>`).join('')}
                            <!-- Notes major -->
                            ${notesMajor.map(n=>`
                            <div style="background:#fef3c7;border-radius:6px;padding:6px 10px;margin-top:5px;font-size:.76rem;color:#92400e;line-height:1.5;">
                                <i class="bi bi-star-fill me-1"></i><strong>Major :</strong> ${escH(n)}
                            </div>`).join('')}
                        </div>
                    </div>`;
                });

                soinsPaneHtml += `</div></div>`; // fin corps + fin groupe
            });
        }

        /* ── Panneau Réévaluations médicales ── */
        const evolCfg = {
            FAVORABLE:            { bg:'#dcfce7', color:'#166534', border:'#16a34a', icon:'bi-arrow-up-circle-fill',    label:'Favorable'            },
            AMELIORATION_PARTIELLE:{ bg:'#d1fae5', color:'#0f766e', border:'#14b8a6', icon:'bi-arrow-up-right-circle-fill', label:'Amélioration partielle'},
            STATUO_QUO:           { bg:'#f1f5f9', color:'#475569', border:'#94a3b8', icon:'bi-dash-circle-fill',        label:'Statuo quo'           },
            NON_FAVORABLE:        { bg:'#fef3c7', color:'#b45309', border:'#f59e0b', icon:'bi-arrow-down-right-circle-fill', label:'Non favorable'    },
            AGGRAVATION:          { bg:'#fee2e2', color:'#b91c1c', border:'#ef4444', icon:'bi-arrow-down-circle-fill',  label:'Aggravation'          },
            CRITIQUE:             { bg:'#fce7f3', color:'#9f1239', border:'#f43f5e', icon:'bi-exclamation-octagon-fill',label:'Critique'             },
        };

        let revPaneHtml = '';
        if (reevaluations.length === 0) {
            revPaneHtml = `<div class="text-center py-4 text-muted" style="background:#f8fafc;border-radius:12px;font-size:.85rem;">
                <i class="bi bi-file-medical d-block fs-3 mb-2 opacity-30"></i>
                Aucune réévaluation médicale enregistrée pour ce patient.
            </div>`;
        } else {
            const months = ['janv.','févr.','mars','avr.','mai','juin','juil.','août','sept.','oct.','nov.','déc.'];
            const fmtRevDate = str => {
                if (!str) return '—';
                const d = new Date(str + 'T00:00:00');
                const days = ['Dim.','Lun.','Mar.','Mer.','Jeu.','Ven.','Sam.'];
                return days[d.getDay()] + ' ' + d.getDate() + ' ' + months[d.getMonth()] + ' ' + d.getFullYear();
            };

            reevaluations.forEach((rev, idx) => {
                const ec        = evolCfg[rev.evolution_globale] || evolCfg.STATUO_QUO;
                const grpId     = 'siRevGrp_' + idx;
                const openDef   = idx === 0; // première réévaluation ouverte par défaut
                const heure     = rev.heure_reevaluation ? rev.heure_reevaluation.slice(0,5) : '';
                const medecin   = escH(((rev.medecin_prenom||'')+' '+(rev.medecin_nom||'')).trim());
                const hasBilans = rev.bilans && rev.bilans.length > 0;
                const hasMeds   = rev.medicaments && rev.medicaments.length > 0;

                revPaneHtml += `
                <div style="margin-bottom:10px;border:1.5px solid ${ec.border}50;border-radius:12px;overflow:hidden;">
                    <!-- En-tête cliquable -->
                    <button onclick="siToggleRevGroup('${grpId}')"
                            style="width:100%;display:flex;align-items:center;justify-content:space-between;
                                   padding:12px 16px;border:none;cursor:pointer;text-align:left;
                                   background:${ec.bg};transition:filter .15s;">
                        <div style="display:flex;align-items:center;gap:10px;">
                            <div style="width:36px;height:36px;border-radius:9px;background:${ec.color}20;
                                        display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                <i class="bi ${ec.icon}" style="color:${ec.color};font-size:1rem;"></i>
                            </div>
                            <div>
                                <div style="font-size:.84rem;font-weight:800;color:#0f172a;">
                                    ${fmtRevDate(rev.date_reevaluation)}
                                    <span style="font-size:.72rem;color:#64748b;font-weight:500;margin-left:6px;">${heure}</span>
                                </div>
                                <div style="font-size:.7rem;color:#475569;margin-top:1px;">
                                    <i class="bi bi-person-badge-fill me-1" style="color:${ec.color};"></i>Dr. ${medecin}
                                </div>
                            </div>
                        </div>
                        <div style="display:flex;align-items:center;gap:8px;">
                            <span style="background:${ec.color};color:#fff;font-size:.62rem;font-weight:700;
                                         padding:3px 10px;border-radius:20px;white-space:nowrap;">
                                <i class="bi ${ec.icon} me-1"></i>${ec.label}
                            </span>
                            <i id="${grpId}_chev" class="bi bi-chevron-${openDef?'up':'down'}"
                               style="color:${ec.color};font-size:.8rem;flex-shrink:0;"></i>
                        </div>
                    </button>

                    <!-- Corps déroulant -->
                    <div id="${grpId}" style="padding:${openDef?'16px':'0'} 16px;max-height:${openDef?'3000px':'0'};
                                              overflow:hidden;transition:max-height .35s ease,padding .3s ease;background:#fff;">

                        ${rev.diagnostic_jour ? `
                        <div style="margin-bottom:14px;">
                            <div style="font-size:.7rem;font-weight:800;color:#64748b;text-transform:uppercase;
                                        letter-spacing:.6px;margin-bottom:6px;">
                                <i class="bi bi-file-earmark-medical-fill me-1" style="color:#3b82f6;"></i>Diagnostic du jour
                            </div>
                            <div style="background:#eff6ff;border-left:3px solid #3b82f6;border-radius:0 8px 8px 0;
                                        padding:10px 14px;font-size:.84rem;font-weight:600;color:#1e3a5f;">
                                ${escH(rev.diagnostic_jour)}
                                ${rev.code_cim10 ? `<span style="background:#dbeafe;color:#1d4ed8;font-size:.65rem;font-weight:700;
                                    padding:1px 7px;border-radius:6px;margin-left:8px;">${escH(rev.code_cim10)}</span>` : ''}
                            </div>
                        </div>` : ''}

                        ${rev.note_evolution ? `
                        <div style="margin-bottom:14px;">
                            <div style="font-size:.7rem;font-weight:800;color:#64748b;text-transform:uppercase;
                                        letter-spacing:.6px;margin-bottom:6px;">
                                <i class="bi bi-activity me-1" style="color:${ec.color};"></i>Note d'évolution
                            </div>
                            <div style="background:${ec.bg};border-left:3px solid ${ec.border};border-radius:0 8px 8px 0;
                                        padding:10px 14px;font-size:.82rem;color:#334155;line-height:1.6;white-space:pre-line;">
                                ${escH(rev.note_evolution)}
                            </div>
                        </div>` : ''}

                        ${rev.conduite_tenir ? `
                        <div style="margin-bottom:14px;">
                            <div style="font-size:.7rem;font-weight:800;color:#64748b;text-transform:uppercase;
                                        letter-spacing:.6px;margin-bottom:6px;">
                                <i class="bi bi-list-check me-1" style="color:#0d9488;"></i>Conduite à tenir (CAT)
                            </div>
                            <div style="background:#f0fdfa;border-left:3px solid #0d9488;border-radius:0 8px 8px 0;
                                        padding:10px 14px;font-size:.82rem;color:#134e4a;line-height:1.7;white-space:pre-line;">
                                ${escH(rev.conduite_tenir)}
                            </div>
                        </div>` : ''}

                        ${hasMeds ? `
                        <div style="margin-bottom:14px;">
                            <div style="font-size:.7rem;font-weight:800;color:#64748b;text-transform:uppercase;
                                        letter-spacing:.6px;margin-bottom:6px;">
                                <i class="bi bi-capsule-pill me-1" style="color:#8b5cf6;"></i>Médicaments prescrits
                                <span style="background:#ede9fe;color:#7c3aed;font-size:.6rem;padding:1px 6px;border-radius:6px;margin-left:4px;font-weight:700;">${rev.medicaments.length}</span>
                            </div>
                            <div style="display:flex;flex-direction:column;gap:6px;">
                                ${rev.medicaments.map(m => `
                                <div style="background:#faf5ff;border:1px solid #ddd6fe;border-radius:8px;
                                            padding:9px 13px;display:flex;align-items:flex-start;gap:10px;">
                                    <div style="flex-shrink:0;width:28px;height:28px;border-radius:7px;background:#ede9fe;
                                                display:flex;align-items:center;justify-content:center;margin-top:1px;">
                                        <i class="bi bi-capsule" style="color:#7c3aed;font-size:.8rem;"></i>
                                    </div>
                                    <div style="flex:1;min-width:0;">
                                        <div style="font-size:.83rem;font-weight:700;color:#3b0764;">${escH(m.nom_medicament)}</div>
                                        <div style="font-size:.72rem;color:#6d28d9;margin-top:2px;display:flex;gap:8px;flex-wrap:wrap;">
                                            ${m.posologie ? `<span><i class="bi bi-dot"></i>${escH(m.posologie)}</span>` : ''}
                                            ${m.voie_administration ? `<span style="background:#ede9fe;padding:1px 6px;border-radius:5px;">${escH(m.voie_administration)}</span>` : ''}
                                            ${m.frequence ? `<span>${escH(m.frequence)}</span>` : ''}
                                            ${m.duree ? `<span style="color:#94a3b8;">· ${escH(m.duree)}</span>` : ''}
                                        </div>
                                        ${m.notes ? `<div style="font-size:.7rem;color:#94a3b8;margin-top:2px;font-style:italic;">${escH(m.notes)}</div>` : ''}
                                    </div>
                                </div>`).join('')}
                            </div>
                        </div>` : ''}

                        ${hasBilans ? `
                        <div style="margin-bottom:14px;">
                            <div style="font-size:.7rem;font-weight:800;color:#64748b;text-transform:uppercase;
                                        letter-spacing:.6px;margin-bottom:6px;">
                                <i class="bi bi-microscope me-1" style="color:#0ea5e9;"></i>Bilans demandés
                                <span style="background:#e0f2fe;color:#0369a1;font-size:.6rem;padding:1px 6px;border-radius:6px;margin-left:4px;font-weight:700;">${rev.bilans.length}</span>
                            </div>
                            <div style="display:flex;flex-direction:column;gap:5px;">
                                ${rev.bilans.map(b => {
                                    const isLabo = b.type === 'LABO';
                                    const bBg    = isLabo ? '#f0f9ff' : '#fdf4ff';
                                    const bBorder= isLabo ? '#bae6fd' : '#e9d5ff';
                                    const bColor = isLabo ? '#0369a1' : '#7e22ce';
                                    const bIcon  = isLabo ? 'bi-flask-fill' : 'bi-image';
                                    const bLabel = isLabo ? 'Labo' : 'Imagerie';
                                    return `
                                    <div style="background:${bBg};border:1px solid ${bBorder};border-radius:7px;
                                                padding:7px 12px;display:flex;align-items:center;gap:9px;">
                                        <i class="bi ${bIcon}" style="color:${bColor};font-size:.85rem;flex-shrink:0;"></i>
                                        <div style="flex:1;">
                                            <span style="font-size:.8rem;font-weight:600;color:#1e293b;">${escH(b.intitule)}</span>
                                            ${b.urgence=='1'||b.urgence===true ? `<span style="background:#fee2e2;color:#b91c1c;font-size:.6rem;font-weight:700;padding:1px 6px;border-radius:5px;margin-left:6px;">URGENT</span>` : ''}
                                        </div>
                                        <span style="background:${bBg};color:${bColor};font-size:.6rem;font-weight:700;
                                                     padding:1px 6px;border-radius:5px;border:1px solid ${bBorder};">${bLabel}</span>
                                    </div>`;
                                }).join('')}
                            </div>
                        </div>` : ''}

                        ${rev.traitement_non_medicamenteux ? `
                        <div style="margin-bottom:4px;">
                            <div style="font-size:.7rem;font-weight:800;color:#64748b;text-transform:uppercase;
                                        letter-spacing:.6px;margin-bottom:6px;">
                                <i class="bi bi-heart-pulse me-1" style="color:#f97316;"></i>Traitement non médicamenteux
                            </div>
                            <div style="background:#fff7ed;border-left:3px solid #f97316;border-radius:0 8px 8px 0;
                                        padding:10px 14px;font-size:.81rem;color:#7c2d12;line-height:1.7;white-space:pre-line;">
                                ${escH(rev.traitement_non_medicamenteux)}
                            </div>
                        </div>` : ''}

                    </div><!-- /corps -->
                </div>`; // /groupe
            });
        }

        /* ── Évaluations douleur — panneau ── */
        const sevCfgDoul = {
            ABSENT:   { bg:'#f0fdf4', color:'#166534', border:'#86efac', label:'Absent',   dot:'#22c55e' },
            LEGERE:   { bg:'#fefce8', color:'#854d0e', border:'#fde047', label:'Légère',   dot:'#eab308' },
            MODEREE:  { bg:'#fff7ed', color:'#9a3412', border:'#fb923c', label:'Modérée',  dot:'#f97316' },
            INTENSE:  { bg:'#fef2f2', color:'#991b1b', border:'#fca5a5', label:'Intense',  dot:'#ef4444' },
        };
        const contexteLabel = { AVANT_SOIN:'Avant soin', PENDANT_SOIN:'Pendant soin', APRES_SOIN:'Après soin' };

        let douleurPaneHtml = '';
        if (douleur.length === 0) {
            douleurPaneHtml = `<div class="text-center py-4 text-muted"
                style="background:#fff7ed;border-radius:12px;font-size:.85rem;border:1px dashed #fdba74;">
                <i class="bi bi-emoji-smile d-block fs-3 mb-2 opacity-40"></i>
                Aucune évaluation de la douleur enregistrée pour ce patient.
            </div>`;
        } else {
            douleur.forEach(ev => {
                const sev   = sevCfgDoul[ev.severite] || sevCfgDoul['LEGERE'];
                const dt    = ev.date_evaluation ? new Date(ev.date_evaluation) : null;
                const dtStr = dt ? dt.toLocaleDateString('fr-FR',{day:'2-digit',month:'short',year:'numeric'})
                                   + ' ' + dt.toLocaleTimeString('fr-FR',{hour:'2-digit',minute:'2-digit'}) : '';
                const inf   = escH(((ev.inf_prenom||'')+' '+(ev.inf_nom||'')).trim());
                const pct   = ev.score_max > 0 ? Math.round((ev.score / ev.score_max) * 100) : 0;
                const barColor = sev.dot;

                douleurPaneHtml += `
                <div style="margin-bottom:10px;border:1.5px solid ${sev.border};border-radius:12px;
                            overflow:hidden;background:${sev.bg};">
                    <div style="display:flex;align-items:center;gap:12px;padding:12px 16px;">
                        <!-- Score visuel -->
                        <div style="min-width:52px;text-align:center;">
                            <div style="font-size:1.4rem;font-weight:900;color:${sev.color};line-height:1;">
                                ${parseFloat(ev.score)}
                            </div>
                            <div style="font-size:.65rem;color:${sev.color};opacity:.7;">/ ${parseFloat(ev.score_max)}</div>
                        </div>
                        <!-- Barre + infos -->
                        <div style="flex:1;min-width:0;">
                            <div style="display:flex;align-items:center;gap:8px;margin-bottom:5px;">
                                <span style="background:${sev.border};color:${sev.color};font-size:.68rem;font-weight:800;
                                             padding:2px 9px;border-radius:20px;">${sev.label.toUpperCase()}</span>
                                <span style="font-size:.73rem;font-weight:700;color:#1e293b;">${escH(ev.echelle)}</span>
                                ${ev.contexte ? `<span style="font-size:.65rem;color:#64748b;background:#f1f5f9;
                                    padding:1px 7px;border-radius:20px;">${contexteLabel[ev.contexte]||ev.contexte}</span>` : ''}
                            </div>
                            <!-- Barre de progression -->
                            <div style="height:6px;background:#e2e8f0;border-radius:20px;overflow:hidden;">
                                <div style="width:${pct}%;height:100%;background:${barColor};border-radius:20px;transition:width .5s;"></div>
                            </div>
                            ${ev.localisation ? `<div style="font-size:.72rem;color:#475569;margin-top:5px;">
                                <i class="bi bi-geo-alt-fill me-1" style="color:${barColor};"></i>${escH(ev.localisation)}</div>` : ''}
                            ${ev.caracteristiques ? `<div style="font-size:.71rem;color:#64748b;margin-top:2px;">
                                <i class="bi bi-tag me-1"></i>${escH(ev.caracteristiques)}</div>` : ''}
                        </div>
                        <!-- Date + infirmier -->
                        <div style="text-align:right;min-width:90px;">
                            <div style="font-size:.7rem;font-weight:700;color:#1e293b;">${dtStr}</div>
                            ${inf ? `<div style="font-size:.65rem;color:#64748b;margin-top:2px;">${inf}</div>` : ''}
                        </div>
                    </div>
                    ${(ev.action_prise || ev.note_infirmier) ? `
                    <div style="padding:8px 16px 10px;border-top:1px solid ${sev.border}40;background:rgba(255,255,255,.5);">
                        ${ev.action_prise ? `<div style="font-size:.75rem;color:#374151;margin-bottom:3px;">
                            <i class="bi bi-lightning-fill me-1" style="color:#f59e0b;"></i>
                            <strong>Action :</strong> ${escH(ev.action_prise)}</div>` : ''}
                        ${ev.note_infirmier ? `<div style="font-size:.75rem;color:#374151;">
                            <i class="bi bi-pencil me-1" style="color:#6366f1;"></i>
                            ${escH(ev.note_infirmier)}</div>` : ''}
                    </div>` : ''}
                </div>`;
            });
        }

        /* ── Section onglets Obs / Plaintes / Soins / Douleur / Médical ── */
        const badgeObs = obs.length > 0
            ? `<span style="background:#ede9fe;color:#7c3aed;font-size:.62rem;font-weight:700;padding:1px 7px;border-radius:10px;margin-left:5px;">${obs.length}</span>` : '';
        const badgePlaintes = plaintes.length > 0
            ? `<span style="background:#fef3c7;color:#92400e;font-size:.62rem;font-weight:700;padding:1px 7px;border-radius:10px;margin-left:5px;">${plaintes.length}</span>` : '';
        const nbSoinsRealises  = soins.filter(s => s.statut === 'REALISE').length;
        const nbSoinsPlanifies = soins.filter(s => s.statut === 'PLANIFIE' || s.statut === 'RETARD').length;
        const badgeSoins = soins.length > 0
            ? `<span style="background:#dbeafe;color:#1d4ed8;font-size:.62rem;font-weight:700;padding:1px 7px;border-radius:10px;margin-left:5px;">${nbSoinsRealises}✓ ${nbSoinsPlanifies}⏳</span>` : '';
        const badgeRev = reevaluations.length > 0
            ? `<span style="background:#d1fae5;color:#065f46;font-size:.62rem;font-weight:700;padding:1px 7px;border-radius:10px;margin-left:5px;">${reevaluations.length}</span>` : '';
        // Badge douleur : nombre d'évaluations avec couleur de la dernière sévérité
        const lastSev = douleur.length > 0 ? (douleur[0].severite || '') : '';
        const doulBadgeBg = {ABSENT:'#dcfce7',LEGERE:'#fef9c3',MODEREE:'#ffedd5',INTENSE:'#fee2e2'}[lastSev] || '#fef3c7';
        const doulBadgeColor = {ABSENT:'#166534',LEGERE:'#854d0e',MODEREE:'#9a3412',INTENSE:'#991b1b'}[lastSev] || '#92400e';
        const badgeDouleur = douleur.length > 0
            ? `<span style="background:${doulBadgeBg};color:${doulBadgeColor};font-size:.62rem;font-weight:700;padding:1px 7px;border-radius:10px;margin-left:5px;">${douleur.length}</span>` : '';

        const tabsHtml = `
        <div>
            <!-- Nav onglets -->
            <div style="display:flex;gap:2px;border-bottom:2px solid #e2e8f0;margin-bottom:16px;overflow-x:auto;flex-wrap:nowrap;">
                <button id="siTabBtnObs" onclick="siSwitchTab('obs')"
                        style="padding:9px 14px;border:none;background:transparent;cursor:pointer;white-space:nowrap;
                               font-weight:700;font-size:.79rem;border-radius:8px 8px 0 0;
                               color:#7c3aed;border-bottom:3px solid #7c3aed;margin-bottom:-2px;transition:.15s;">
                    <i class="bi bi-chat-square-text-fill me-1" style="color:#7c3aed;"></i>Observations${badgeObs}
                </button>
                <button id="siTabBtnPlaintes" onclick="siSwitchTab('plaintes')"
                        style="padding:9px 14px;border:none;background:transparent;cursor:pointer;white-space:nowrap;
                               font-weight:700;font-size:.79rem;border-radius:8px 8px 0 0;
                               color:#64748b;border-bottom:3px solid transparent;margin-bottom:-2px;transition:.15s;">
                    <i class="bi bi-exclamation-circle-fill me-1" style="color:#f59e0b;"></i>Plaintes${badgePlaintes}
                </button>
                <button id="siTabBtnSoins" onclick="siSwitchTab('soins')"
                        style="padding:9px 14px;border:none;background:transparent;cursor:pointer;white-space:nowrap;
                               font-weight:700;font-size:.79rem;border-radius:8px 8px 0 0;
                               color:#64748b;border-bottom:3px solid transparent;margin-bottom:-2px;transition:.15s;">
                    <i class="bi bi-clipboard2-pulse-fill me-1" style="color:#0ea5e9;"></i>Soins${badgeSoins}
                </button>
                <button id="siTabBtnDouleur" onclick="siSwitchTab('douleur')"
                        style="padding:9px 14px;border:none;background:transparent;cursor:pointer;white-space:nowrap;
                               font-weight:700;font-size:.79rem;border-radius:8px 8px 0 0;
                               color:#64748b;border-bottom:3px solid transparent;margin-bottom:-2px;transition:.15s;">
                    <i class="bi bi-heart-pulse-fill me-1" style="color:#f97316;"></i>Douleur${badgeDouleur}
                </button>
                <button id="siTabBtnRev" onclick="siSwitchTab('rev')"
                        style="padding:9px 14px;border:none;background:transparent;cursor:pointer;white-space:nowrap;
                               font-weight:700;font-size:.79rem;border-radius:8px 8px 0 0;
                               color:#64748b;border-bottom:3px solid transparent;margin-bottom:-2px;transition:.15s;">
                    <i class="bi bi-file-medical-fill me-1" style="color:#10b981;"></i>Suivi Médical${badgeRev}
                </button>
            </div>
            <!-- Panneaux -->
            <div id="siPaneObs">${obsPaneHtml}</div>
            <div id="siPanePlaintes" style="display:none;">${plaintesPaneHtml}</div>
            <div id="siPaneSoins"    style="display:none;">${soinsPaneHtml}</div>
            <div id="siPaneDouleur"  style="display:none;">${douleurPaneHtml}</div>
            <div id="siPaneRev"      style="display:none;">${revPaneHtml}</div>
        </div>`;

        // Injecter tout dans le modal
        document.getElementById('siModalBody').innerHTML = vitalsHtml + chartsHtml + tabsHtml;

        // Dessiner les courbes si données suffisantes
        if (hist.length >= 2) {
            const labels = hist.map(h => {
                const d = new Date(h.date_mesure);
                return d.toLocaleDateString('fr-FR',{day:'2-digit',month:'2-digit'}) + ' ' + d.toLocaleTimeString('fr-FR',{hour:'2-digit',minute:'2-digit'});
            });
            const temps  = hist.map(h => parseFloat(h.temperature)  || null);
            const taSys  = hist.map(h => parseFloat(h.ta_sys)        || null);
            const taDia  = hist.map(h => parseFloat(h.ta_dia)        || null);

            const chartOpts = {
                responsive: true,
                plugins: { legend: { display: true, labels: { font: { size: 11 } } } },
                scales: {
                    x: { ticks: { font: { size: 9 }, maxRotation: 35 }, grid: { color: '#f1f5f9' } },
                    y: { ticks: { font: { size: 10 } }, grid: { color: '#f1f5f9' } }
                }
            };

            _siChartTemp = new Chart(document.getElementById('siChartTemp'), {
                type: 'line',
                data: {
                    labels,
                    datasets: [{
                        label: 'Température (°C)',
                        data: temps,
                        borderColor: '#ef4444', backgroundColor: 'rgba(239,68,68,.08)',
                        borderWidth: 2, pointRadius: 3, tension: 0.3, fill: true,
                        spanGaps: true,
                    }]
                },
                options: { ...chartOpts,
                    plugins: { ...chartOpts.plugins,
                        annotation: { annotations: [{
                            type: 'line', yMin: 37.5, yMax: 37.5,
                            borderColor: '#f59e0b', borderWidth: 1, borderDash: [4,4],
                        }]}
                    }
                }
            });

            _siChartTA = new Chart(document.getElementById('siChartTA'), {
                type: 'line',
                data: {
                    labels,
                    datasets: [
                        {
                            label: 'Systolique (mmHg)',
                            data: taSys,
                            borderColor: '#3b82f6', backgroundColor: 'rgba(59,130,246,.08)',
                            borderWidth: 2, pointRadius: 3, tension: 0.3, fill: false,
                            spanGaps: true,
                        },
                        {
                            label: 'Diastolique (mmHg)',
                            data: taDia,
                            borderColor: '#93c5fd', backgroundColor: 'rgba(147,197,253,.08)',
                            borderWidth: 2, pointRadius: 3, tension: 0.3, fill: false,
                            borderDash: [4,3],
                            spanGaps: true,
                        }
                    ]
                },
                options: chartOpts
            });
        }
    }

    /* ── Panneau CAT rapide ── */
    let _siCurrentPatientId = null;

    // Stocker le patient courant à l'ouverture du modal
    const _origOuvrirSuivi = window.ouvrirSuiviInfirmier;
    window.ouvrirSuiviInfirmier = function(patientId, nomComplet) {
        _siCurrentPatientId = patientId;
        // Réinitialiser le panneau CAT
        const panel = document.getElementById('siCatPanel');
        if (panel) panel.style.display = 'none';
        const btn = document.getElementById('siCatToggleBtn');
        if (btn) { btn.innerHTML = '<i class="bi bi-list-check me-1"></i>Ajouter Conduite à tenir'; btn.style.background = '#0d9488'; }
        _origOuvrirSuivi(patientId, nomComplet);
    };

    window.siToggleCatPanel = function() {
        const panel = document.getElementById('siCatPanel');
        const btn   = document.getElementById('siCatToggleBtn');
        if (!panel) return;
        const isOpen = panel.style.display !== 'none';
        panel.style.display = isOpen ? 'none' : 'block';
        if (btn) {
            if (isOpen) {
                btn.innerHTML = '<i class="bi bi-list-check me-1"></i>Ajouter Conduite à tenir';
                btn.style.background = '#0d9488';
            } else {
                btn.innerHTML = '<i class="bi bi-chevron-up me-1"></i>Masquer le formulaire';
                btn.style.background = '#475569';
                // Scroll vers le panneau
                panel.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }
        }
        // Réinitialiser le message
        const msg = document.getElementById('siCatMsg');
        if (msg) msg.style.display = 'none';
    };

    window.siSaveCat = function() {
        const cat      = (document.getElementById('siCatText')?.value     || '').trim();
        const evol     = document.getElementById('siCatEvol')?.value      || 'STATUO_QUO';
        const diag     = (document.getElementById('siCatDiag')?.value     || '').trim();
        const noteEvol = (document.getElementById('siCatNoteEvol')?.value || '').trim();
        const msg      = document.getElementById('siCatMsg');
        const btn      = document.getElementById('siCatSaveBtn');

        if (!cat) {
            msg.style.display = 'block';
            msg.innerHTML = '<div style="background:#fee2e2;color:#b91c1c;border-radius:8px;padding:8px 12px;font-size:.8rem;font-weight:600;"><i class="bi bi-exclamation-circle me-1"></i>La conduite à tenir est obligatoire.</div>';
            return;
        }

        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Enregistrement…';

        const body = new FormData();
        body.append('patient_id',       _siCurrentPatientId);
        body.append('conduite_tenir',   cat);
        body.append('evolution_globale',evol);
        body.append('diagnostic_jour',  diag);
        body.append('note_evolution',   noteEvol);

        fetch(BASE + 'dashboard/save-cat', { method: 'POST', body })
            .then(r => r.json())
            .then(res => {
                msg.style.display = 'block';
                if (res.success) {
                    msg.innerHTML = '<div style="background:#dcfce7;color:#166534;border-radius:8px;padding:8px 12px;font-size:.8rem;font-weight:600;"><i class="bi bi-check-circle-fill me-1"></i>Conduite à tenir enregistrée. L\'infirmier y a maintenant accès.</div>';
                    // Vider les champs
                    document.getElementById('siCatText').value     = '';
                    document.getElementById('siCatDiag').value     = '';
                    document.getElementById('siCatNoteEvol').value = '';
                    document.getElementById('siCatEvol').value     = 'STATUO_QUO';
                    // Recharger les données du modal pour mettre à jour l'onglet Suivi Médical
                    setTimeout(() => {
                        fetch(BASE + 'dashboard/suivi-infirmier/' + _siCurrentPatientId)
                            .then(r => r.json())
                            .then(data => {
                                if (data.success) renderSuivi(data);
                                // Basculer automatiquement sur l'onglet Suivi Médical
                                setTimeout(() => siSwitchTab('rev'), 300);
                            })
                            .catch(() => {});
                    }, 800);
                } else {
                    msg.innerHTML = '<div style="background:#fee2e2;color:#b91c1c;border-radius:8px;padding:8px 12px;font-size:.8rem;font-weight:600;"><i class="bi bi-x-circle me-1"></i>' + escH(res.message || 'Erreur lors de l\'enregistrement.') + '</div>';
                }
            })
            .catch(() => {
                msg.style.display = 'block';
                msg.innerHTML = '<div style="background:#fee2e2;color:#b91c1c;border-radius:8px;padding:8px 12px;font-size:.8rem;">Erreur réseau. Veuillez réessayer.</div>';
            })
            .finally(() => {
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-check-lg me-1"></i>Sauvegarder';
            });
    };

    /* ── Toggle groupe soins (accordéon) ── */
    window.siToggleSoinGroup = function(id) {
        const body = document.getElementById(id);
        const chev = document.getElementById(id + '_chev');
        if (!body) return;
        const isOpen = body.style.maxHeight !== '0px' && body.style.maxHeight !== '';
        if (isOpen) {
            body.style.maxHeight = '0';
            body.style.padding   = '0 12px';
            if (chev) { chev.classList.remove('bi-chevron-up'); chev.classList.add('bi-chevron-down'); }
        } else {
            body.style.maxHeight = '2000px';
            body.style.padding   = '12px';
            if (chev) { chev.classList.remove('bi-chevron-down'); chev.classList.add('bi-chevron-up'); }
        }
    };

    /* ── Toggle groupe réévaluation (accordéon) ── */
    window.siToggleRevGroup = function(id) {
        const body = document.getElementById(id);
        const chev = document.getElementById(id + '_chev');
        if (!body) return;
        const isOpen = body.style.maxHeight !== '0px' && body.style.maxHeight !== '';
        if (isOpen) {
            body.style.maxHeight = '0';
            body.style.padding   = '0 16px';
            if (chev) { chev.classList.remove('bi-chevron-up'); chev.classList.add('bi-chevron-down'); }
        } else {
            body.style.maxHeight = '3000px';
            body.style.padding   = '16px';
            if (chev) { chev.classList.remove('bi-chevron-down'); chev.classList.add('bi-chevron-up'); }
        }
    };

    /* ── Commutateur onglets Obs / Plaintes / Soins / Suivi Médical ── */
    window.siSwitchTab = function(tab) {
        const panes = {
            obs:      document.getElementById('siPaneObs'),
            plaintes: document.getElementById('siPanePlaintes'),
            soins:    document.getElementById('siPaneSoins'),
            douleur:  document.getElementById('siPaneDouleur'),
            rev:      document.getElementById('siPaneRev'),
        };
        const btns = {
            obs:      document.getElementById('siTabBtnObs'),
            plaintes: document.getElementById('siTabBtnPlaintes'),
            soins:    document.getElementById('siTabBtnSoins'),
            douleur:  document.getElementById('siTabBtnDouleur'),
            rev:      document.getElementById('siTabBtnRev'),
        };
        const activeStyle = {
            obs:      { color: '#7c3aed', border: '3px solid #7c3aed' },
            plaintes: { color: '#b45309', border: '3px solid #f59e0b' },
            soins:    { color: '#0369a1', border: '3px solid #0ea5e9' },
            douleur:  { color: '#c2410c', border: '3px solid #f97316' },
            rev:      { color: '#065f46', border: '3px solid #10b981' },
        };

        Object.keys(panes).forEach(key => {
            if (!panes[key] || !btns[key]) return;
            if (key === tab) {
                panes[key].style.display     = 'block';
                btns[key].style.color        = activeStyle[key].color;
                btns[key].style.borderBottom = activeStyle[key].border;
            } else {
                panes[key].style.display     = 'none';
                btns[key].style.color        = '#64748b';
                btns[key].style.borderBottom = '3px solid transparent';
            }
        });
    };

    function escH(s) {
        if (!s) return '';
        return String(s).replace(/[&<>"']/g, c =>
            ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[c]));
    }

})();
</script>

<!-- ════════════════════════════════════════════════════════
     MODAL REGISTRE DES CONSULTATIONS
     ════════════════════════════════════════════════════════ -->
<style>
.reg-table { width:100%; border-collapse:collapse; font-size:.78rem; }
.reg-table th { background:#1e40af; color:#fff; padding:9px 10px; font-size:.7rem; text-transform:uppercase; letter-spacing:.5px; font-weight:800; white-space:nowrap; position:sticky; top:0; z-index:2; }
.reg-table td { padding:8px 10px; border-bottom:1px solid #f1f5f9; vertical-align:top; line-height:1.4; }
.reg-table tr:nth-child(even) td { background:#f8fafc; }
.reg-table tr:hover td { background:#eff6ff; transition:.1s; }
.reg-badge { display:inline-block; border-radius:6px; padding:1px 7px; font-size:.68rem; font-weight:700; }
.reg-badge-m  { background:#dbeafe; color:#1e40af; }
.reg-badge-f  { background:#fce7f3; color:#be185d; }
.reg-badge-vih-pos { background:#fee2e2; color:#b91c1c; }
.reg-badge-vih-np  { background:#f1f5f9; color:#64748b; }
.reg-type { background:#f5f3ff; color:#5b21b6; border-radius:5px; padding:1px 6px; font-size:.67rem; font-weight:600; white-space:nowrap; }
.reg-empty { text-align:center; padding:50px 20px; color:#94a3b8; }
.reg-empty i { font-size:2.2rem; display:block; margin-bottom:10px; }
</style>

<div class="modal fade" id="modalRegistre" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-fullscreen modal-dialog-scrollable">
        <div class="modal-content border-0" style="border-radius:0">

            <!-- ── Header ── -->
            <div class="modal-header border-0 px-4 py-3"
                 style="background:linear-gradient(135deg,#1e40af,#1d4ed8);flex-shrink:0">
                <div class="d-flex align-items-center gap-3">
                    <div style="width:38px;height:38px;border-radius:10px;background:rgba(255,255,255,.2);
                                display:flex;align-items:center;justify-content:center">
                        <i class="bi bi-journal-text text-white fs-5"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold text-white mb-0">Registre des Consultations</h5>
                        <small class="text-white opacity-75" id="regSubtitle">Chargement…</small>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <!-- Export CSV -->
                    <button onclick="exportRegistre('csv')" id="btnExportCsv"
                            class="btn btn-sm fw-bold rounded-pill"
                            style="background:rgba(255,255,255,.15);color:#fff;border:1px solid rgba(255,255,255,.35);font-size:.72rem">
                        <i class="bi bi-file-earmark-spreadsheet-fill me-1"></i>Excel / CSV
                    </button>
                    <!-- Export PDF -->
                    <button onclick="exportRegistre('pdf')" id="btnExportPdf"
                            class="btn btn-sm fw-bold rounded-pill"
                            style="background:rgba(239,68,68,.8);color:#fff;border:none;font-size:.72rem">
                        <i class="bi bi-file-earmark-pdf-fill me-1"></i>PDF
                    </button>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
            </div>

            <!-- ── Barre filtres ── -->
            <div class="px-4 py-3 border-bottom" style="background:#f8fafc;flex-shrink:0">
                <div class="d-flex align-items-end gap-3 flex-wrap">
                    <!-- Période -->
                    <div>
                        <label class="form-label fw-semibold mb-1" style="font-size:.72rem;color:#374151;text-transform:uppercase;letter-spacing:.5px">
                            <i class="bi bi-calendar3 me-1"></i>Du
                        </label>
                        <input type="date" id="regDateFrom" class="form-control form-control-sm rounded-2"
                               style="font-size:.82rem;min-width:140px"
                               value="<?= date('Y-m-01') ?>">
                    </div>
                    <div>
                        <label class="form-label fw-semibold mb-1" style="font-size:.72rem;color:#374151;text-transform:uppercase;letter-spacing:.5px">Au</label>
                        <input type="date" id="regDateTo" class="form-control form-control-sm rounded-2"
                               style="font-size:.82rem;min-width:140px"
                               value="<?= date('Y-m-d') ?>">
                    </div>
                    <!-- Recherche -->
                    <div style="flex:1;min-width:200px">
                        <label class="form-label fw-semibold mb-1" style="font-size:.72rem;color:#374151;text-transform:uppercase;letter-spacing:.5px">
                            <i class="bi bi-search me-1"></i>Rechercher
                        </label>
                        <input type="text" id="regSearch" class="form-control form-control-sm rounded-2"
                               style="font-size:.82rem" placeholder="Nom, prénom, N° dossier…"
                               oninput="regSearchDebounce()">
                    </div>
                    <!-- Appliquer -->
                    <button onclick="chargerRegistre()" class="btn btn-primary btn-sm rounded-pill fw-bold px-4"
                            style="font-size:.78rem">
                        <i class="bi bi-funnel-fill me-1"></i>Filtrer
                    </button>
                    <!-- Réinitialiser -->
                    <button onclick="resetFiltresRegistre()" class="btn btn-outline-secondary btn-sm rounded-pill"
                            style="font-size:.78rem">
                        <i class="bi bi-x-circle me-1"></i>Réinitialiser
                    </button>
                </div>

                <!-- Stats rapides -->
                <div id="regStats" class="d-flex gap-3 flex-wrap mt-3 d-none">
                    <span class="badge rounded-pill fw-semibold" style="background:#dbeafe;color:#1e40af;font-size:.72rem">
                        <i class="bi bi-person-check me-1"></i><span id="regTotal">0</span> consultations
                    </span>
                    <span class="badge rounded-pill fw-semibold" style="background:#dbeafe;color:#1e40af;font-size:.72rem">
                        ♂ <span id="regMale">0</span> Hommes
                    </span>
                    <span class="badge rounded-pill fw-semibold" style="background:#fce7f3;color:#be185d;font-size:.72rem">
                        ♀ <span id="regFemale">0</span> Femmes
                    </span>
                    <span class="badge rounded-pill fw-semibold" style="background:#f5f3ff;color:#5b21b6;font-size:.72rem">
                        Payant : <span id="regPayant">0</span>
                    </span>
                    <span class="badge rounded-pill fw-semibold" style="background:#f0fdf4;color:#16a34a;font-size:.72rem">
                        Assurance : <span id="regAssurance">0</span>
                    </span>
                    <span class="badge rounded-pill fw-semibold" style="background:#fff7ed;color:#c2410c;font-size:.72rem">
                        BPC : <span id="regBpc">0</span>
                    </span>
                </div>
            </div>

            <!-- ── Corps : tableau ── -->
            <div class="modal-body p-0" style="overflow-x:auto">

                <!-- Loader -->
                <div id="regLoader" class="text-center py-5 d-none">
                    <div class="spinner-border text-primary" role="status"></div>
                    <p class="text-muted mt-2 small">Chargement du registre…</p>
                </div>

                <!-- Tableau -->
                <div id="regTableWrap">
                    <div class="reg-empty">
                        <i class="bi bi-journal-text" style="color:#1e40af;opacity:.25"></i>
                        <p>Cliquez sur <strong>Filtrer</strong> pour charger le registre.</p>
                    </div>
                </div>

            </div><!-- /modal-body -->

        </div>
    </div>
</div>

<script>
// ── REGISTRE DES CONSULTATIONS ─────────────────────────────────────────────
(function() {
const BASE = '<?= BASE_URL ?>';
let _regTimer = null;

window.ouvrirRegistre = function() {
    new bootstrap.Modal(document.getElementById('modalRegistre')).show();
    chargerRegistre();
};

window.chargerRegistre = function() {
    const from   = document.getElementById('regDateFrom').value;
    const to     = document.getElementById('regDateTo').value;
    const search = document.getElementById('regSearch').value.trim();

    document.getElementById('regLoader').classList.remove('d-none');
    document.getElementById('regTableWrap').innerHTML = '';
    document.getElementById('regStats').classList.add('d-none');
    document.getElementById('regSubtitle').textContent = 'Chargement…';

    const params = new URLSearchParams({ from, to, search });
    fetch(BASE + 'dashboard/registre-consultations?' + params, {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => r.json())
    .then(data => {
        document.getElementById('regLoader').classList.add('d-none');
        if (!data.success) {
            document.getElementById('regTableWrap').innerHTML = `<div class="reg-empty"><i class="bi bi-exclamation-triangle text-danger"></i><p>${data.message || 'Erreur de chargement.'}</p></div>`;
            return;
        }
        _renderRegistre(data.rows, from, to);
        _updateStats(data.rows);
        document.getElementById('regSubtitle').textContent =
            `${data.rows.length} consultation(s) · Du ${_fmtDate(from)} au ${_fmtDate(to)}`;
    })
    .catch(err => {
        document.getElementById('regLoader').classList.add('d-none');
        document.getElementById('regTableWrap').innerHTML = `<div class="reg-empty"><i class="bi bi-exclamation-triangle text-danger"></i><p>Erreur réseau : ${err.message}</p></div>`;
    });
};

window.regSearchDebounce = function() {
    clearTimeout(_regTimer);
    _regTimer = setTimeout(() => chargerRegistre(), 450);
};

window.resetFiltresRegistre = function() {
    document.getElementById('regDateFrom').value = new Date().toISOString().slice(0, 7) + '-01';
    document.getElementById('regDateTo').value   = new Date().toISOString().slice(0, 10);
    document.getElementById('regSearch').value   = '';
    chargerRegistre();
};

window.exportRegistre = function(type) {
    const from   = document.getElementById('regDateFrom').value;
    const to     = document.getElementById('regDateTo').value;
    const search = document.getElementById('regSearch').value.trim();
    const params = new URLSearchParams({ from, to, search });
    if (type === 'csv') {
        window.location.href = BASE + 'dashboard/registre-export-csv?' + params;
    } else {
        window.open(BASE + 'dashboard/registre-export-pdf?' + params + '&autoprint=0', '_blank');
    }
};

function _fmtDate(d) {
    if (!d) return '';
    const p = d.split('-');
    return p[2] + '/' + p[1] + '/' + p[0];
}

function _typeLabel(t) {
    const map = {
        'BON_PRISE_EN_CHARGE': 'Bon PEC',
        'PAYANT_COMPTANT': 'Payant comptant',
        'ASSURANCE': 'Assurance',
        'FAMILLE_PHP': 'Famille PHP',
        'AGENTS_PHP': 'Agents PHP',
    };
    return map[t] || t || '—';
}

function _updateStats(rows) {
    const el = document.getElementById('regStats');
    document.getElementById('regTotal').textContent   = rows.length;
    document.getElementById('regMale').textContent    = rows.filter(r => r.sexe === 'M').length;
    document.getElementById('regFemale').textContent  = rows.filter(r => r.sexe === 'F').length;
    document.getElementById('regPayant').textContent  = rows.filter(r => r.type_client === 'PAYANT_COMPTANT').length;
    document.getElementById('regAssurance').textContent = rows.filter(r => r.type_client === 'ASSURANCE').length;
    document.getElementById('regBpc').textContent     = rows.filter(r => r.type_client === 'BON_PRISE_EN_CHARGE').length;
    el.classList.remove('d-none');
}

function _renderRegistre(rows, from, to) {
    const wrap = document.getElementById('regTableWrap');
    if (!rows || rows.length === 0) {
        wrap.innerHTML = `<div class="reg-empty"><i class="bi bi-journal-x" style="color:#1e40af;opacity:.3"></i><p>Aucune consultation pour cette période.</p></div>`;
        return;
    }

    const esc = s => s ? String(s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[c])) : '';
    const trunc = (s, n=60) => s ? (s.length > n ? s.slice(0, n) + '…' : s) : '—';

    let html = `<table class="reg-table">
        <thead>
            <tr>
                <th style="width:35px">N°</th>
                <th style="width:85px">Date</th>
                <th style="width:110px">Nom</th>
                <th style="width:95px">Prénom</th>
                <th style="width:45px">Sexe</th>
                <th style="width:40px">Âge</th>
                <th style="width:50px">VIH</th>
                <th style="width:100px">Type client</th>
                <th style="width:80px">Domicile</th>
                <th style="width:90px">Contact</th>
                <th style="min-width:120px">Motif</th>
                <th style="min-width:130px">Diagnostic</th>
                <th style="min-width:120px">Bilans</th>
                <th style="min-width:140px">Traitements</th>
                <th style="width:40px"></th>
            </tr>
        </thead>
        <tbody>`;

    rows.forEach((r, i) => {
        const dt = r.date_consultation
            ? new Date(r.date_consultation).toLocaleDateString('fr-FR', {day:'2-digit',month:'2-digit',year:'numeric'})
            : '—';
        const sexeBadge = r.sexe === 'M'
            ? '<span class="reg-badge reg-badge-m">M</span>'
            : r.sexe === 'F'
                ? '<span class="reg-badge reg-badge-f">F</span>'
                : '—';
        const vihBadge = r.statut_vih === 'Positif'
            ? '<span class="reg-badge reg-badge-vih-pos"><i class="bi bi-plus-circle-fill me-1"></i>VIH+</span>'
            : '<span class="reg-badge reg-badge-vih-np">—</span>';

        html += `<tr>
            <td style="color:#94a3b8;font-weight:700;text-align:center">${i+1}</td>
            <td style="white-space:nowrap">${esc(dt)}</td>
            <td style="font-weight:700">${esc((r.nom||'').toUpperCase())}</td>
            <td>${esc(r.prenom||'')}</td>
            <td style="text-align:center">${sexeBadge}</td>
            <td style="text-align:center">${r.age !== null ? r.age+'<small style="color:#94a3b8">a</small>' : '—'}</td>
            <td style="text-align:center">${vihBadge}</td>
            <td><span class="reg-type">${esc(_typeLabel(r.type_client))}</span></td>
            <td style="color:#475569">${esc(r.ville||'—')}</td>
            <td style="color:#475569">${esc(r.telephone||'—')}</td>
            <td style="color:#374151">${esc(trunc(r.motif_consultation,70))}</td>
            <td style="font-weight:600;color:#0f172a">${esc(trunc(r.diagnostic_principal,80))}</td>
            <td style="color:#0891b2;font-size:.72rem">${esc(trunc(r.bilans_labo||'—',90))}</td>
            <td style="color:#7c3aed;font-size:.72rem">${esc(trunc(r.traitements_complets||'—',100))}</td>
            <td style="text-align:center">
                <a href="${BASE}patients/dossier/${r.patient_id}" target="_blank"
                   class="btn btn-outline-primary btn-sm rounded-2 p-0" style="width:26px;height:26px;display:inline-flex;align-items:center;justify-content:center"
                   title="Ouvrir le dossier">
                    <i class="bi bi-folder2-open" style="font-size:.7rem"></i>
                </a>
            </td>
        </tr>`;
    });

    html += '</tbody></table>';
    wrap.innerHTML = html;
}

})();
</script>

<?php if (($_GET['success'] ?? '') === 'patient_cree'): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const t = document.createElement('div');
    t.style.cssText = 'position:fixed;bottom:24px;right:24px;background:#16a34a;color:#fff;'
        + 'padding:13px 20px;border-radius:14px;font-weight:700;font-size:.88rem;'
        + 'z-index:9999;box-shadow:0 8px 28px rgba(0,0,0,.18);display:flex;align-items:center;gap:8px;';
    t.innerHTML = '<i class="bi bi-person-check-fill" style="font-size:1.1rem;"></i>'
        + 'Patient créé et ajouté à votre file d\'attente.';
    document.body.appendChild(t);
    setTimeout(() => {
        t.style.transition = 'opacity .5s';
        t.style.opacity = '0';
        setTimeout(() => t.remove(), 500);
    }, 4000);
});
</script>
<?php endif; ?>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>