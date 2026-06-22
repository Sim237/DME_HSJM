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

$demandes       = $demandes       ?? [];
$statistiques   = $statistiques   ?? [];
$examens_soumis = $examens_soumis ?? [];
$pjsParExamen   = $pjsParExamen   ?? [];
$sousValeursMap = $sousValeursMap ?? [];
$stats_bio      = $stats_bio      ?? [];

$nb_demandes   = array_sum(array_column($statistiques['demandes_jour'] ?? [], 'nb'));
$nb_urgents    = $statistiques['urgents']      ?? 0;
$delai_moyen   = $statistiques['delai_moyen']  ?? 0;
$taux_qualite  = $statistiques['taux_qualite'] ?? 98.5;
$nb_a_valider  = count($examens_soumis);
$nb_valides_auj = $stats_bio['valides_auj']   ?? 0;
$nb_rejetes_auj = $stats_bio['rejetes_auj']   ?? 0;
$nb_en_cours    = $stats_bio['en_cours']       ?? 0;
$taux_rejet     = $stats_bio['taux_rejet']     ?? 0;
$delai_7j       = $stats_bio['delai_moyen_7j'] ?? $delai_moyen;

/** Icône pièce jointe */
$iconePj = function(string $mime, string $nom = ''): array {
    $mime = strtolower($mime); $ext = strtolower(pathinfo($nom, PATHINFO_EXTENSION));
    if (str_contains($mime, 'pdf') || $ext === 'pdf')  return ['bi-file-earmark-pdf-fill',   '#dc2626'];
    if (str_contains($mime, 'image') || in_array($ext, ['png','jpg','jpeg','gif','webp','tif','tiff','bmp','heic']))
                                                         return ['bi-file-earmark-image-fill', '#2563eb'];
    if (str_contains($mime, 'sheet') || in_array($ext, ['xlsx','xls','csv']))
                                                         return ['bi-file-earmark-excel-fill', '#16a34a'];
    return ['bi-file-earmark-fill', '#64748b'];
};
$tailleH = function(?int $o): string {
    if ($o === null) return '—';
    if ($o < 1024)   return "$o o";
    if ($o < 1048576) return round($o/1024, 1).' Ko';
    return round($o/1048576, 1).' Mo';
};

/** Délai écoulé depuis une date en format humain compact */
$elapsedLabel = function(string $dt): string {
    $secs = time() - strtotime($dt);
    if ($secs < 3600)  return round($secs/60).'min';
    if ($secs < 86400) return round($secs/3600, 1).'h';
    return round($secs/86400).'j';
};
?>

<style>
/* ══ COCKPIT GLOBAL ══ */
.sidebar { display:none !important; }
main, .main-content { margin-left:0 !important; width:100% !important; }
body { background:#f0f4f8; font-family:'Plus Jakarta Sans',sans-serif; color:#1e293b; }

/* ══ HEADER ══ */
.cockpit-header {
    background:linear-gradient(135deg,#0f4c75 0%,#1b6ca8 100%);
    color:#fff; padding:12px 30px;
    box-shadow:0 4px 12px rgba(0,0,0,.15);
    position:sticky; top:0; z-index:200;
}
#lab-clock { font-family:'Courier New',monospace; font-size:2.2rem; font-weight:bold;
             color:#00e5ff; text-shadow:0 0 15px rgba(0,229,255,.6); letter-spacing:3px; }
.btn-lh { font-weight:700; border-radius:50px; padding:8px 20px; font-size:.82rem;
          display:inline-flex; align-items:center; gap:7px; transition:all .25s; border:none; }
.btn-lh-hist { background:rgba(255,255,255,.15); color:#fff; border:1px solid rgba(255,255,255,.3); }
.btn-lh-hist:hover  { background:rgba(255,255,255,.25); color:#fff; transform:translateY(-2px); }
.btn-lh-secr { background:#1565c0; color:#fff; border:1px solid rgba(255,255,255,.2); }
.btn-lh-secr:hover  { background:#0d47a1; color:#fff; transform:translateY(-2px); }
.btn-lh-prof { background:#fff; color:#0f4c75; }
.btn-lh-prof:hover  { transform:translateY(-2px); }

/* ══ KPI ══ */
.kpi-wrap { display:flex; gap:14px; padding:18px 28px 6px; flex-wrap:wrap; }
.kpi-card { flex:1; min-width:140px; background:#fff; border-radius:18px; padding:16px 18px;
            box-shadow:0 4px 12px rgba(0,0,0,.06); display:flex; align-items:center; gap:12px;
            border-bottom:5px solid #e2e8f0; transition:transform .2s; cursor:default; }
.kpi-card:hover { transform:translateY(-3px); }
.kpi-demandes { border-color:#6366f1; }
.kpi-urgent   { border-color:#ef4444; }
.kpi-valider  { border-color:#f59e0b; cursor:pointer; }
.kpi-encours  { border-color:#3b82f6; }
.kpi-valides  { border-color:#10b981; }
.kpi-delai    { border-color:#8b5cf6; }
.kpi-rejet    { border-color:#f43f5e; }
.kpi-icon { width:50px; height:50px; border-radius:14px; display:flex; align-items:center;
            justify-content:center; font-size:1.4rem; flex-shrink:0; }
.kpi-demandes .kpi-icon { background:#eef2ff; color:#6366f1; }
.kpi-urgent   .kpi-icon { background:#fef2f2; color:#ef4444; }
.kpi-valider  .kpi-icon { background:#fffbeb; color:#f59e0b; }
.kpi-encours  .kpi-icon { background:#eff6ff; color:#3b82f6; }
.kpi-valides  .kpi-icon { background:#ecfdf5; color:#10b981; }
.kpi-delai    .kpi-icon { background:#f5f3ff; color:#8b5cf6; }
.kpi-rejet    .kpi-icon { background:#fff1f2; color:#f43f5e; }
.kpi-label { font-size:.72rem; color:#64748b; font-weight:600; text-transform:uppercase; letter-spacing:.5px; }
.kpi-value { font-size:1.7rem; font-weight:800; line-height:1.1; color:#0f172a; }
.kpi-sub   { font-size:.68rem; color:#94a3b8; margin-top:1px; }

/* ══ CORPS ══ */
.lab-body { padding:10px 28px 30px; }

/* ══ TABS ══ */
.lab-tabs { border-bottom:2px solid #e2e8f0; margin-bottom:14px; display:flex; gap:4px; }
.lab-tab-btn { padding:10px 22px; border:none; background:transparent; border-radius:10px 10px 0 0;
               font-weight:700; font-size:.875rem; color:#64748b; cursor:pointer; transition:.2s;
               border-bottom:3px solid transparent; margin-bottom:-2px; }
.lab-tab-btn.active { color:#1b6ca8; border-bottom-color:#1b6ca8; background:rgba(27,108,168,.06); }
.lab-tab-btn:hover:not(.active) { background:#f8fafc; color:#0f172a; }
.lab-tab-pane { display:none; }
.lab-tab-pane.active { display:block; }

/* ══ FILTRES ══ */
.filter-bar { background:#fff; border-radius:16px; padding:12px 16px; margin-bottom:12px;
              box-shadow:0 2px 8px rgba(0,0,0,.05); display:flex; gap:10px; flex-wrap:wrap; align-items:center; }
.filter-bar .form-select,
.filter-bar .form-control { border-radius:10px; border:1.5px solid #e2e8f0; font-size:.875rem; padding:7px 12px; }
.filter-bar .form-select:focus,
.filter-bar .form-control:focus { border-color:#1b6ca8; box-shadow:0 0 0 3px rgba(27,108,168,.12); }

/* ══ TABLE DEMANDES ══ */
.table-card { background:#fff; border-radius:18px; box-shadow:0 4px 16px rgba(0,0,0,.07); overflow:hidden; }
.table-card-header { padding:12px 18px; border-bottom:1px solid #f1f5f9;
                     display:flex; justify-content:space-between; align-items:center; }
.table-card-header h5 { font-weight:800; font-size:.95rem; color:#0f172a; margin:0; }
.lab-table th { background:#f8fafc; color:#1b6ca8; padding:10px 12px; font-size:.69rem;
                text-transform:uppercase; letter-spacing:.7px; font-weight:700; border-bottom:2px solid #e2e8f0; }
.lab-table td { padding:11px 12px; vertical-align:middle; border-bottom:1px solid #f8fafc; }
.lab-table tbody tr:hover { background:#f8fafc; }
.lab-table tbody tr.row-urgent { border-left:4px solid #ef4444; }
.lab-table tbody tr.row-normal { border-left:4px solid #10b981; }

/* ══ BADGES STATUT ══ */
.bs { padding:4px 10px; border-radius:20px; font-size:.7rem; font-weight:700; display:inline-block; }
.bs-attente          { background:#fef9c3; color:#854d0e; }
.bs-dispatche        { background:#dbeafe; color:#1e40af; }
.bs-soumis           { background:#fef3c7; color:#92400e; }
.bs-valides          { background:#dcfce7; color:#166534; }
.bs-prelev           { background:#e0f2fe; color:#075985; }
.bs-analyse          { background:#dbeafe; color:#1e40af; }

/* ══ BOUTONS ══ */
.btn-act { border-radius:8px; padding:5px 12px; font-size:.77rem; font-weight:700;
           border:none; transition:.2s; display:inline-flex; align-items:center; gap:4px; cursor:pointer; }
.btn-dispatcher { background:#6366f1; color:#fff; }
.btn-dispatcher:hover { background:#4f46e5; }
.btn-traiter    { background:#0f4c75; color:#fff; text-decoration:none; }
.btn-traiter:hover { background:#0a3554; color:#fff; }
.btn-valider-ex { background:#10b981; color:#fff; }
.btn-valider-ex:hover { background:#059669; }
.btn-valider-all { background:linear-gradient(135deg,#10b981,#059669); color:#fff; font-size:.82rem; padding:7px 16px; }
.btn-valider-all:hover { background:linear-gradient(135deg,#059669,#047857); }
.btn-rejeter-ex { background:#ef4444; color:#fff; }
.btn-rejeter-ex:hover { background:#dc2626; }
.btn-refaire-ex { background:#f59e0b; color:#fff; }
.btn-refaire-ex:hover { background:#d97706; }
.btn-imprimer   { border:1.5px solid #94a3b8; color:#64748b; border-radius:8px; padding:5px 10px;
                  background:transparent; font-size:.77rem; transition:.2s; text-decoration:none; }
.btn-imprimer:hover { background:#94a3b8; color:#fff; }
.btn-num-ordre  { background:#0e7490; color:#fff; border-radius:8px; padding:5px 10px;
                  font-size:.77rem; font-weight:700; border:none; transition:.2s;
                  display:inline-flex; align-items:center; gap:.3rem; cursor:pointer; }
.btn-num-ordre:hover { background:#0c6276; }
.btn-num-ordre.has-ordre { background:#166534; }

/* ══ DÉLAI ══ */
.delai-ok   { color:#10b981; font-weight:700; }
.delai-warn { color:#f59e0b; font-weight:700; }
.delai-ko   { color:#ef4444; font-weight:800; animation:blink-d 1.8s infinite; }
@keyframes blink-d { 50% { opacity:.4; } }
.elapsed-badge { display:inline-flex; align-items:center; gap:3px; font-size:.7rem; padding:2px 7px;
                 border-radius:10px; font-weight:700; }
.elapsed-ok   { background:#ecfdf5; color:#166534; }
.elapsed-warn { background:#fffbeb; color:#92400e; }
.elapsed-ko   { background:#fef2f2; color:#991b1b; }

/* ══ VALIDATION CARDS ══ */
.val-card { background:#fff; border-radius:16px; box-shadow:0 3px 12px rgba(0,0,0,.07);
            border-left:5px solid #f59e0b; margin-bottom:14px; overflow:hidden; }
.val-card.urgent-card { border-left-color:#ef4444; }
.val-card-header { padding:12px 18px; background:#fffbeb; display:flex;
                   justify-content:space-between; align-items:center; flex-wrap:wrap; gap:8px; }
.val-card.urgent-card .val-card-header { background:#fef2f2; }
.val-card-body { padding:14px 18px; }
.val-result-box { background:#f8fafc; border-radius:10px; padding:10px 14px;
                  margin-bottom:8px; border:1px solid #e2e8f0; }
.val-result-box .rl { font-size:.7rem; text-transform:uppercase; font-weight:700;
                      color:#94a3b8; letter-spacing:.5px; }
.val-result-box .rv { font-size:.95rem; font-weight:700; color:#0f172a; }
.normal-range    { font-size:.73rem; color:#64748b; }
.anormal-ind  { color:#ef4444; font-weight:800; }
.normal-ind   { color:#10b981; font-weight:700; }
.val-card.removing { opacity:0; max-height:0 !important; padding:0 !important;
                     margin:0 !important; overflow:hidden; transition:all .35s ease; }

/* ══ DEMANDE GROUP ══ */
.demande-group { background:#fff; border-radius:18px; box-shadow:0 3px 14px rgba(0,0,0,.07);
                 margin-bottom:16px; overflow:hidden; border:1.5px solid #e2e8f0; }
.demande-group.has-urgent { border-color:#fca5a5; }
.demande-group-header { padding:12px 18px; background:linear-gradient(135deg,#f8fafc,#f1f5f9);
                        display:flex; justify-content:space-between; align-items:center;
                        gap:10px; cursor:pointer; user-select:none; transition:background .15s; flex-wrap:wrap; }
.demande-group-header:hover { background:linear-gradient(135deg,#f1f5f9,#e2e8f0); }
.demande-group.has-urgent .demande-group-header { background:linear-gradient(135deg,#fef2f2,#fee2e2); }
.dg-patient-name { font-weight:800; font-size:.93rem; color:#0f172a; }
.dg-dossier { font-size:.73rem; color:#64748b; }
.dg-count-badge { background:#f59e0b; color:#fff; border-radius:20px; padding:3px 10px;
                  font-size:.72rem; font-weight:700; }
.demande-group-body { padding:14px 14px 4px; }
.demande-group-body.collapsed { display:none; }
.dg-chevron { transition:transform .2s; color:#64748b; }

/* ══ Commentaire biologiste ══ */
.bio-comment-wrap { margin-bottom:10px; }
.bio-comment-label { font-size:.7rem; text-transform:uppercase; font-weight:700;
                     color:#7c3aed; letter-spacing:.5px; margin-bottom:4px;
                     display:flex; align-items:center; gap:5px; }
.bio-comment-ta { width:100%; border:1.5px solid #ddd6fe; border-radius:10px; padding:8px 12px;
                  font-size:.83rem; color:#1e293b; resize:vertical; min-height:55px; max-height:110px;
                  transition:border-color .2s; font-family:inherit; background:#faf5ff; }
.bio-comment-ta:focus { outline:none; border-color:#8b5cf6; background:#fff;
                        box-shadow:0 0 0 3px rgba(139,92,246,.1); }

/* ══ Pièces jointes ══ */
.val-pj-section { margin-bottom:.8rem; padding:.6rem .85rem;
                  background:#f0f9ff; border:1px solid #bae6fd; border-radius:10px; }
.val-pj-label   { font-size:.7rem; font-weight:700; text-transform:uppercase; letter-spacing:.5px;
                  color:#0369a1; margin-bottom:.3rem; display:flex; align-items:center; gap:.3rem; }
.val-pj-item    { display:flex; align-items:center; gap:.5rem; padding:.28rem .6rem;
                  background:#fff; border:1px solid #e0f2fe; border-radius:7px;
                  margin-top:.25rem; font-size:.79rem; font-weight:600; color:#0f172a;
                  text-decoration:none; transition:border-color .15s; }
.val-pj-item:hover { border-color:#7dd3fc; background:#f0f9ff; color:#0369a1; }
.val-pj-item-name { flex:1; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.val-pj-item-size { font-size:.68rem; color:#64748b; white-space:nowrap; flex-shrink:0; }

/* ══ Multi-param ══ */
.mp-grid { display:flex; flex-wrap:wrap; gap:10px; margin-bottom:10px; }
.mp-card { background:#f8fafc; border:1.5px solid #e2e8f0; border-radius:12px;
           padding:10px 14px; min-width:110px; flex:1 0 110px; }
.mp-card.mp-n { background:#f0fdf4; border-color:#86efac; }
.mp-card.mp-a { background:#fff1f2; border-color:#fca5a5; }
.mp-name { font-size:.67rem; font-weight:700; text-transform:uppercase;
           color:#94a3b8; letter-spacing:.5px; margin-bottom:4px; }
.mp-num  { font-size:1rem; font-weight:800; color:#0f172a; display:flex; align-items:baseline; gap:4px; }
.mp-range{ font-size:.64rem; color:#94a3b8; margin-top:2px; }
.mp-card.mp-n .mp-num { color:#15803d; }
.mp-card.mp-a .mp-num { color:#dc2626; }

/* ══ TOAST ══ */
.toast-lab { position:fixed; bottom:24px; right:24px; z-index:9999; min-width:300px;
             border-radius:12px; box-shadow:0 8px 24px rgba(0,0,0,.15); font-weight:600; }

/* ══ MODAL DISPATCH ══ */
.disp-row { display:flex; align-items:center; gap:12px; padding:10px 0; border-bottom:1px solid #f1f5f9; }
.disp-row:last-child { border-bottom:none; }
.disp-name { font-weight:700; font-size:.875rem; flex:1; }
.disp-cat  { font-size:.74rem; color:#64748b; }
.disp-sel  { width:200px; flex-shrink:0; border-radius:8px; border:1.5px solid #e2e8f0; font-size:.82rem; padding:6px 10px; }

/* ══ STATS TAB ══ */
.stats-grid { display:grid; grid-template-columns:1fr 1fr; gap:18px; }
@media(max-width:900px) { .stats-grid { grid-template-columns:1fr; } }
.stat-panel { background:#fff; border-radius:18px; box-shadow:0 4px 14px rgba(0,0,0,.07); padding:20px 24px; }
.stat-panel-title { font-weight:800; font-size:.9rem; color:#0f172a; margin-bottom:16px;
                    display:flex; align-items:center; gap:8px; }
.activite-row { display:flex; align-items:center; gap:10px; padding:8px 0; border-bottom:1px solid #f1f5f9; font-size:.84rem; }
.activite-row:last-child { border-bottom:none; }
.activite-icon { width:30px; height:30px; border-radius:8px; display:flex; align-items:center;
                 justify-content:center; font-size:.9rem; flex-shrink:0; }
.ai-valide { background:#dcfce7; color:#166534; }
.ai-rejete { background:#fef2f2; color:#dc2626; }
.activite-info { flex:1; }
.activite-nom  { font-weight:700; color:#0f172a; }
.activite-sub  { font-size:.72rem; color:#64748b; }
.activite-time { font-size:.72rem; color:#94a3b8; white-space:nowrap; }

/* ══ Empty state ══ */
.empty-state { padding:50px 20px; text-align:center; color:#94a3b8; }
.empty-state i { font-size:3rem; opacity:.3; display:block; margin-bottom:10px; }

/* ══ N° Ordre ══ */
.ordre-chip { display:inline-flex; align-items:center; gap:.3rem; background:#ecfdf5;
              color:#166534; border:1px solid #86efac; border-radius:6px;
              padding:2px 8px; font-size:.71rem; font-weight:700; font-family:monospace; }
</style>

<main>

<!-- ══ HEADER COCKPIT ══ -->
<div class="cockpit-header d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div class="d-flex align-items-center gap-3">
        <div class="bg-white p-2 rounded-circle shadow-sm">
            <img src="<?= BASE_URL ?>public/images/logo_ordre_malte.png" style="height:34px;" alt="Logo">
        </div>
        <div>
            <h4 class="mb-0 fw-bold">LABORATOIRE <span style="color:#00e5ff;">CENTRAL</span></h4>
            <small class="opacity-75 fw-semibold">Biologiste — Supervision &amp; Validation • HSJM</small>
        </div>
    </div>
    <div id="lab-clock">00:00:00</div>
    <div class="d-flex gap-2 align-items-center flex-wrap">
        <a href="<?= BASE_URL ?>laboratoire/secretariat" class="btn btn-lh btn-lh-secr">
            <i class="bi bi-receipt"></i> Secrétariat
        </a>
        <a href="<?= BASE_URL ?>laboratoire/historique" class="btn btn-lh btn-lh-hist">
            <i class="bi bi-clock-history"></i> Historique
        </a>
        <a href="<?= BASE_URL ?>profil" class="btn btn-lh btn-lh-prof shadow-sm">
            <i class="bi bi-person-circle"></i> Profil
        </a>
        <a href="<?= BASE_URL ?>logout" class="btn btn-danger rounded-circle p-2 shadow-sm">
            <i class="bi bi-power"></i>
        </a>
    </div>
</div>

<!-- ══ KPI STRIP ══ -->
<div class="kpi-wrap">
    <div class="kpi-card kpi-demandes">
        <div class="kpi-icon"><i class="bi bi-clipboard-data"></i></div>
        <div>
            <div class="kpi-label">Demandes du jour</div>
            <div class="kpi-value"><?= $nb_demandes ?></div>
        </div>
    </div>
    <div class="kpi-card kpi-urgent">
        <div class="kpi-icon"><i class="bi bi-exclamation-triangle-fill"></i></div>
        <div>
            <div class="kpi-label">Urgents actifs</div>
            <div class="kpi-value" style="color:#ef4444;"><?= $nb_urgents ?></div>
        </div>
    </div>
    <div class="kpi-card kpi-valider" onclick="switchTab('valider')">
        <div class="kpi-icon"><i class="bi bi-patch-check"></i></div>
        <div>
            <div class="kpi-label">À valider</div>
            <div class="kpi-value" style="color:#f59e0b;" id="kpi-a-valider"><?= $nb_a_valider ?></div>
            <div class="kpi-sub">Cliquer pour traiter</div>
        </div>
    </div>
    <div class="kpi-card kpi-encours">
        <div class="kpi-icon"><i class="bi bi-hourglass-split"></i></div>
        <div>
            <div class="kpi-label">En cours techniciens</div>
            <div class="kpi-value" style="color:#3b82f6;"><?= $nb_en_cours ?></div>
        </div>
    </div>
    <div class="kpi-card kpi-valides">
        <div class="kpi-icon"><i class="bi bi-check2-all"></i></div>
        <div>
            <div class="kpi-label">Validés aujourd'hui</div>
            <div class="kpi-value" style="color:#10b981;"><?= $nb_valides_auj ?></div>
        </div>
    </div>
    <div class="kpi-card kpi-delai">
        <div class="kpi-icon"><i class="bi bi-clock"></i></div>
        <div>
            <div class="kpi-label">Délai moy. (7j)</div>
            <div class="kpi-value" style="color:#8b5cf6;"><?= $delai_7j ?>h</div>
        </div>
    </div>
    <div class="kpi-card kpi-rejet">
        <div class="kpi-icon"><i class="bi bi-x-circle"></i></div>
        <div>
            <div class="kpi-label">Taux de rejet (7j)</div>
            <div class="kpi-value" style="color:#f43f5e;"><?= $taux_rejet ?>%</div>
            <?php if ($nb_rejetes_auj > 0): ?>
            <div class="kpi-sub"><?= $nb_rejetes_auj ?> rejet(s) aujourd'hui</div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- ══ CORPS ══ -->
<div class="lab-body">

    <!-- TABS -->
    <div class="lab-tabs">
        <button class="lab-tab-btn active" id="tab-demandes-btn" onclick="switchTab('demandes')">
            <i class="bi bi-list-ul me-1"></i>File de travail
            <span class="badge bg-primary-subtle text-primary ms-1" style="font-size:.68rem;"><?= count($demandes) ?></span>
        </button>
        <button class="lab-tab-btn" id="tab-valider-btn" onclick="switchTab('valider')">
            <i class="bi bi-patch-check me-1"></i>Validation biologiste
            <?php if ($nb_a_valider > 0): ?>
            <span class="badge bg-warning text-dark ms-1" style="font-size:.68rem;" id="badge-tab-valider"><?= $nb_a_valider ?></span>
            <?php endif; ?>
        </button>
        <button class="lab-tab-btn" id="tab-stats-btn" onclick="switchTab('stats')">
            <i class="bi bi-bar-chart-line me-1"></i>Statistiques &amp; Activité
        </button>
    </div>

    <!-- ══════════════════════════════════════
         TAB 1 : FILE DE TRAVAIL (DEMANDES)
    ══════════════════════════════════════ -->
    <div class="lab-tab-pane active" id="tab-demandes">

        <div class="filter-bar">
            <select class="form-select" style="max-width:200px;" id="filtreStatut" onchange="filtrerDemandes()">
                <option value="">Tous les statuts</option>
                <option value="EN_ATTENTE">En attente</option>
                <option value="DISPATCHE">Dispatché</option>
                <option value="RESULTATS_SOUMIS">Résultats soumis</option>
                <option value="VALIDES">Validés</option>
                <option value="PRELEVEMENTS_EFFECTUES">Prélèvements effectués</option>
                <option value="EN_ANALYSE">En analyse</option>
            </select>
            <select class="form-select" style="max-width:180px;" id="filtrePriorite" onchange="filtrerDemandes()">
                <option value="">Toutes priorités</option>
                <option value="urgent">Urgents uniquement</option>
                <option value="normal">Normal uniquement</option>
            </select>
            <input type="text" class="form-control" style="max-width:220px;"
                   id="recherchePatient" placeholder="🔍 Rechercher patient…"
                   oninput="filtrerDemandes()">
            <div class="ms-auto d-flex gap-2">
                <span id="liveIndicator" class="d-flex align-items-center gap-1 text-muted" style="font-size:.75rem;">
                    <span style="width:8px;height:8px;border-radius:50%;background:#10b981;animation:pulse-green 2s infinite;display:inline-block;"></span>
                    Live
                </span>
                <button class="btn btn-sm btn-outline-secondary" onclick="location.reload()">
                    <i class="bi bi-arrow-clockwise"></i>
                </button>
            </div>
        </div>

        <div class="table-card">
            <div class="table-card-header">
                <h5><i class="bi bi-flask text-primary me-2"></i>Demandes actives
                    <span class="badge bg-primary ms-1" style="font-size:.7rem;"><?= count($demandes) ?></span>
                </h5>
            </div>
            <div class="table-responsive">
                <table class="table lab-table align-middle mb-0" id="tableDemandes">
                    <thead>
                        <tr>
                            <th>Priorité</th>
                            <th>Patient</th>
                            <th>Examens</th>
                            <th>Médecin</th>
                            <th>Statut</th>
                            <th>Paiement</th>
                            <th>Délai</th>
                            <th style="min-width:220px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (!empty($demandes)): foreach ($demandes as $d):
                        $isUrgent = ($d['nb_urgents'] ?? 0) > 0;
                        $heures   = round((time() - strtotime($d['date_creation'])) / 3600, 1);
                        $delaiMax = $d['delai_min'] ?? 24;
                        $delaiClass = $heures <= $delaiMax * .6 ? 'delai-ok' : ($heures <= $delaiMax ? 'delai-warn' : 'delai-ko');
                        $statutMap = [
                            'EN_ATTENTE'             => ['En Attente',             'bs-attente'],
                            'DISPATCHE'              => ['Dispatché',              'bs-dispatche'],
                            'RESULTATS_SOUMIS'       => ['Résultats soumis',       'bs-soumis'],
                            'VALIDES'                => ['Validés',                'bs-valides'],
                            'PRELEVEMENTS_EFFECTUES' => ['Prélèvements effectués', 'bs-prelev'],
                            'EN_ANALYSE'             => ['En Analyse',             'bs-analyse'],
                        ];
                        [$sLabel, $sClass] = $statutMap[$d['statut']] ?? [$d['statut'], 'bg-secondary text-white'];
                        $currentOrdre = trim($d['numero_ordre'] ?? '');
                    ?>
                    <tr class="demande-row <?= $isUrgent ? 'row-urgent' : 'row-normal' ?>"
                        data-statut="<?= htmlspecialchars($d['statut']) ?>"
                        data-priorite="<?= $isUrgent ? 'urgent' : 'normal' ?>"
                        data-patient="<?= strtolower(htmlspecialchars($d['nom'].' '.$d['prenom'])) ?>"
                        id="row-dem-<?= (int)$d['id'] ?>">

                        <td>
                            <?php if ($isUrgent): ?>
                                <span class="badge bg-danger"><i class="bi bi-exclamation-triangle me-1"></i>URGENT</span>
                            <?php else: ?>
                                <span class="badge bg-success-subtle text-success">Normal</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="fw-bold"><?= htmlspecialchars($d['nom'].' '.$d['prenom']) ?></div>
                            <small class="text-muted"><?= htmlspecialchars($d['dossier_numero']) ?></small>
                        </td>
                        <td>
                            <span class="badge bg-info-subtle text-info fw-bold"><?= $d['nb_examens'] ?> examen(s)</span>
                            <?php if ($isUrgent): ?>
                            <br><small class="text-danger fw-bold"><?= $d['nb_urgents'] ?> urgent(s)</small>
                            <?php endif; ?>
                        </td>
                        <td><span class="fw-semibold">Dr. <?= htmlspecialchars($d['medecin_nom'].' '.$d['medecin_prenom']) ?></span></td>
                        <td><span class="bs <?= $sClass ?>"><?= $sLabel ?></span></td>
                        <td>
                            <?php if (($d['statut_facturation'] ?? 'NON_FACTURE') === 'FACTURE'): ?>
                            <span class="badge" style="background:#dcfce7;color:#166534;font-size:.72rem;border-radius:12px;padding:3px 8px;">
                                <i class="bi bi-check-circle-fill me-1"></i>Facturé
                            </span>
                            <?php else: ?>
                            <span class="badge" style="background:#fef9c3;color:#854d0e;font-size:.72rem;border-radius:12px;padding:3px 8px;">
                                <i class="bi bi-clock me-1"></i>Non facturé
                            </span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="<?= $delaiClass ?>"><?= $heures ?>h</span>
                        </td>
                        <td>
                            <div class="d-flex gap-1 flex-wrap align-items-center">
                                <?php if (in_array($d['statut'], ['EN_ATTENTE','DISPATCHE','PRELEVEMENTS_EFFECTUES','EN_ANALYSE'])): ?>
                                <button class="btn-act btn-dispatcher"
                                        onclick="ouvrirDispatch(<?= $d['id'] ?>, '<?= htmlspecialchars(addslashes($d['nom'].' '.$d['prenom'])) ?>')">
                                    <i class="bi bi-people"></i>Dispatcher
                                </button>
                                <?php endif; ?>
                                <a href="<?= BASE_URL ?>laboratoire/traitement/<?= $d['id'] ?>" class="btn-act btn-traiter">
                                    <i class="bi bi-eye"></i>Voir
                                </a>
                                <button class="btn-num-ordre <?= $currentOrdre ? 'has-ordre' : '' ?>"
                                        onclick="ouvrirModalOrdre(<?= (int)$d['id'] ?>, '<?= htmlspecialchars(addslashes($d['nom'].' '.$d['prenom'])) ?>', '<?= htmlspecialchars(addslashes($currentOrdre)) ?>')">
                                    <i class="bi bi-hash"></i>
                                    <span id="ordre-label-<?= (int)$d['id'] ?>"><?= $currentOrdre ?: 'N° Ordre' ?></span>
                                </button>
                                <a href="<?= BASE_URL ?>laboratoire/imprimer/<?= $d['id'] ?>" target="_blank" class="btn-imprimer">
                                    <i class="bi bi-printer"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; else: ?>
                    <tr><td colspan="8">
                        <div class="empty-state">
                            <i class="bi bi-clipboard-check"></i>
                            <p class="fw-semibold mb-1">Aucune demande active</p>
                            <small>Les nouvelles demandes apparaîtront ici automatiquement.</small>
                        </div>
                    </td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div><!-- /.tab-demandes -->

    <!-- ══════════════════════════════════════
         TAB 2 : VALIDATION BIOLOGISTE
    ══════════════════════════════════════ -->
    <div class="lab-tab-pane" id="tab-valider">

        <?php if (empty($examens_soumis)): ?>
        <div class="empty-state" style="background:#fff;border-radius:18px;box-shadow:0 4px 16px rgba(0,0,0,.07);">
            <i class="bi bi-patch-check"></i>
            <p class="fw-semibold mb-1">Aucun examen en attente de validation</p>
            <small>Les résultats soumis par les techniciens apparaîtront ici.</small>
        </div>

        <?php else:
        /* Grouper par demande_id */
        $groupes = [];
        foreach ($examens_soumis as $ex) {
            $did = (int)$ex['demande_id'];
            if (!isset($groupes[$did])) {
                $groupes[$did] = [
                    'demande_id'     => $did,
                    'patient_nom'    => $ex['patient_nom'],
                    'patient_prenom' => $ex['patient_prenom'],
                    'dossier_numero' => $ex['dossier_numero'],
                    'numero_ordre'   => $ex['numero_ordre'],
                    'dem_patient_id' => $ex['dem_patient_id'],
                    'urgent'         => false,
                    'date_soumission_min' => $ex['date_soumission'],
                    'examens'        => [],
                ];
            }
            if (!empty($ex['urgent'])) $groupes[$did]['urgent'] = true;
            if (!empty($ex['date_soumission']) && $ex['date_soumission'] < $groupes[$did]['date_soumission_min'])
                $groupes[$did]['date_soumission_min'] = $ex['date_soumission'];
            $groupes[$did]['examens'][] = $ex;
        }
        ?>

        <div class="mb-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <span class="fw-bold text-muted small text-uppercase" style="letter-spacing:.5px;">
                <i class="bi bi-hourglass-split me-1 text-warning"></i>
                <?= $nb_a_valider ?> examen(s) — <?= count($groupes) ?> demande(s) en attente de validation
            </span>
            <button class="btn btn-sm btn-outline-secondary" onclick="location.reload()">
                <i class="bi bi-arrow-clockwise me-1"></i>Actualiser
            </button>
        </div>

        <?php foreach ($groupes as $gid => $groupe):
            $nbEx      = count($groupe['examens']);
            $bodyId    = 'dg-'.$gid;
            $elSec     = !empty($groupe['date_soumission_min'])
                         ? (time() - strtotime($groupe['date_soumission_min'])) : 0;
            $elH       = round($elSec / 3600, 1);
            $elClass   = $elH < 1 ? 'elapsed-ok' : ($elH < 3 ? 'elapsed-warn' : 'elapsed-ko');
        ?>
        <div class="demande-group <?= $groupe['urgent'] ? 'has-urgent' : '' ?>" id="group-<?= $gid ?>">

            <!-- ── En-tête accordéon ── -->
            <div class="demande-group-header" onclick="toggleGroup('<?= $bodyId ?>')">
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <?php if (!empty($groupe['numero_ordre'])): ?>
                    <span style="background:#1d4ed8;color:#fff;border-radius:20px;padding:3px 12px;font-size:.73rem;font-weight:800;">
                        <i class="bi bi-hash" style="font-size:.7rem;"></i><?= htmlspecialchars($groupe['numero_ordre']) ?>
                    </span>
                    <?php endif; ?>
                    <span class="dg-patient-name"><?= htmlspecialchars($groupe['patient_nom'].' '.$groupe['patient_prenom']) ?></span>
                    <span class="dg-dossier"><i class="bi bi-file-earmark-medical me-1"></i><?= htmlspecialchars($groupe['dossier_numero']) ?></span>
                    <?php if ($groupe['urgent']): ?>
                    <span class="badge bg-danger" style="font-size:.67rem;"><i class="bi bi-exclamation-triangle me-1"></i>URGENT</span>
                    <?php endif; ?>
                    <span class="elapsed-badge <?= $elClass ?>">
                        <i class="bi bi-clock me-1"></i>Soumis il y a <?= $elH < 1 ? round($elSec/60).'min' : $elH.'h' ?>
                    </span>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <!-- Bouton Valider tout -->
                    <button class="btn-act btn-valider-all"
                            onclick="event.stopPropagation(); validerDemandeBatch(<?= $gid ?>, this)"
                            title="Valider tous les examens de cette demande">
                        <i class="bi bi-check2-all"></i> Valider tout (<?= $nbEx ?>)
                    </button>
                    <span class="dg-count-badge" id="count-<?= $gid ?>"><?= $nbEx ?> examen<?= $nbEx > 1 ? 's' : '' ?></span>
                    <i class="bi bi-chevron-down dg-chevron" id="chevron-<?= $bodyId ?>"></i>
                </div>
            </div>

            <!-- ── Corps accordéon ── -->
            <div class="demande-group-body" id="<?= $bodyId ?>">
                <?php foreach ($groupe['examens'] as $ex):
                    $urgent = !empty($ex['urgent']);
                    $anormal = false;
                    if ($ex['valeur_numerique'] !== null
                        && $ex['valeur_normale_min'] !== null && $ex['valeur_normale_max'] !== null) {
                        $anormal = ($ex['valeur_numerique'] < $ex['valeur_normale_min']
                                 || $ex['valeur_numerique'] > $ex['valeur_normale_max']);
                    }
                    $spVals  = $sousValeursMap[$ex['id']] ?? [];
                    $pjsEx   = $pjsParExamen[$ex['id']] ?? [];
                ?>
                <div class="val-card <?= $urgent ? 'urgent-card' : '' ?>"
                     id="val-card-<?= $ex['id'] ?>"
                     data-group="<?= $gid ?>">

                    <div class="val-card-header">
                        <div>
                            <span class="fw-bold fs-6"><?= htmlspecialchars($ex['nom_examen']) ?></span>
                            <span class="badge bg-secondary-subtle text-secondary ms-2" style="font-size:.7rem;"><?= htmlspecialchars($ex['categorie']) ?></span>
                            <?php if ($urgent): ?><span class="badge bg-danger ms-1" style="font-size:.7rem;"><i class="bi bi-exclamation-triangle me-1"></i>URGENT</span><?php endif; ?>
                            <?php if ($anormal): ?><span class="badge bg-danger-subtle text-danger ms-1" style="font-size:.7rem;">⚠ Valeur anormale</span><?php endif; ?>
                        </div>
                        <div class="text-end">
                            <small class="text-muted">Technicien : <?= htmlspecialchars(trim(($ex['tech_nom'] ?? '').' '.($ex['tech_prenom'] ?? ''))) ?></small>
                            <small class="text-muted d-block">Soumis le <?= date('d/m/Y H:i', strtotime($ex['date_soumission'])) ?></small>
                        </div>
                    </div>

                    <div class="val-card-body">
                        <?php if (!empty($spVals)): ?>
                        <!-- Multi-paramètres -->
                        <div class="mp-grid">
                            <?php foreach ($spVals as $sv):
                                $spA = ($sv['valeur_numerique'] !== null
                                        && $sv['valeur_normale_min'] !== null && $sv['valeur_normale_max'] !== null
                                        && ($sv['valeur_numerique'] < $sv['valeur_normale_min']
                                         || $sv['valeur_numerique'] > $sv['valeur_normale_max']));
                            ?>
                            <div class="mp-card <?= $spA ? 'mp-a' : ($sv['valeur_numerique'] !== null ? 'mp-n' : '') ?>">
                                <div class="mp-name"><?= htmlspecialchars($sv['nom']) ?></div>
                                <div class="mp-num">
                                    <?= $sv['valeur_numerique'] !== null ? htmlspecialchars($sv['valeur_numerique']) : '—' ?>
                                    <?php if (!empty($sv['unite'])): ?><span style="font-size:.68rem;font-weight:400;color:#64748b;"><?= htmlspecialchars($sv['unite']) ?></span><?php endif; ?>
                                    <?php if ($sv['valeur_numerique'] !== null): ?><span style="font-size:.73rem;"><?= $spA ? '⚠' : '✓' ?></span><?php endif; ?>
                                </div>
                                <?php if ($sv['valeur_normale_min'] !== null): ?><div class="mp-range">N: <?= $sv['valeur_normale_min'] ?>–<?= $sv['valeur_normale_max'] ?></div><?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php if (!empty($ex['interpretation'])): ?>
                        <div class="val-result-box mb-3"><div class="rl">Interprétation</div><div class="rv" style="font-size:.875rem;"><?= nl2br(htmlspecialchars($ex['interpretation'])) ?></div></div>
                        <?php endif; ?>

                        <?php else: ?>
                        <!-- Résultat standard -->
                        <div class="row g-2 mb-3">
                            <div class="col-md-5">
                                <div class="val-result-box">
                                    <div class="rl">Résultat</div>
                                    <div class="rv"><?= nl2br(htmlspecialchars($ex['resultat'] ?? '—')) ?></div>
                                </div>
                            </div>
                            <?php if ($ex['valeur_numerique'] !== null): ?>
                            <div class="col-md-3">
                                <div class="val-result-box">
                                    <div class="rl">Valeur numérique</div>
                                    <div class="rv <?= $anormal ? 'anormal-ind' : 'normal-ind' ?>">
                                        <?= htmlspecialchars($ex['valeur_numerique']) ?> <?= htmlspecialchars($ex['unite'] ?? '') ?>
                                        <?= $anormal ? ' ⚠' : ' ✓' ?>
                                    </div>
                                    <?php if ($ex['valeur_normale_min'] !== null): ?>
                                    <div class="normal-range">Norme : <?= $ex['valeur_normale_min'] ?>–<?= $ex['valeur_normale_max'] ?> <?= htmlspecialchars($ex['unite'] ?? '') ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endif; ?>
                            <?php if (!empty($ex['interpretation'])): ?>
                            <div class="col-md-4">
                                <div class="val-result-box">
                                    <div class="rl">Interprétation</div>
                                    <div class="rv" style="font-size:.875rem;"><?= nl2br(htmlspecialchars($ex['interpretation'])) ?></div>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>

                        <?php if (!empty($pjsEx)): ?>
                        <div class="val-pj-section">
                            <div class="val-pj-label"><i class="bi bi-paperclip"></i><?= count($pjsEx) ?> fichier(s) joint(s)</div>
                            <?php foreach ($pjsEx as $pj):
                                [$pjIcon, $pjColor] = $iconePj($pj['fichier_type'] ?? '', $pj['fichier_nom_original'] ?? '');
                            ?>
                            <a href="<?= BASE_URL ?>laboratoire/piece-jointe/<?= (int)$pj['id'] ?>" target="_blank" class="val-pj-item">
                                <i class="bi <?= $pjIcon ?>" style="color:<?= $pjColor ?>;font-size:.93rem;flex-shrink:0;"></i>
                                <span class="val-pj-item-name"><?= htmlspecialchars($pj['fichier_nom_original']) ?></span>
                                <span class="val-pj-item-size"><?= $tailleH((int)($pj['fichier_taille'] ?? 0)) ?></span>
                                <i class="bi bi-box-arrow-up-right" style="font-size:.66rem;color:#94a3b8;flex-shrink:0;"></i>
                            </a>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>

                        <!-- Commentaire biologiste -->
                        <div class="bio-comment-wrap">
                            <label class="bio-comment-label" for="comment-<?= $ex['id'] ?>">
                                <i class="bi bi-chat-text"></i>Commentaire biologiste (optionnel)
                            </label>
                            <textarea class="bio-comment-ta" id="comment-<?= $ex['id'] ?>" rows="2"
                                      placeholder="Interprétation clinique, recommandations, remarques…"></textarea>
                        </div>

                        <div class="d-flex gap-2 flex-wrap">
                            <button class="btn-act btn-valider-ex" onclick="validerExamen(<?= $ex['id'] ?>, <?= $gid ?>, this)">
                                <i class="bi bi-check-circle"></i>Valider
                            </button>
                            <button class="btn-act btn-rejeter-ex" onclick="ouvrirRejet(<?= $ex['id'] ?>, '<?= htmlspecialchars(addslashes($ex['nom_examen'])) ?>')">
                                <i class="bi bi-x-circle"></i>Rejeter
                            </button>
                            <button class="btn-act btn-refaire-ex" onclick="ouvrirRefaire(<?= $ex['id'] ?>, '<?= htmlspecialchars(addslashes($ex['nom_examen'])) ?>')">
                                <i class="bi bi-arrow-repeat"></i>Refaire
                            </button>
                        </div>
                    </div>
                </div><!-- /.val-card -->
                <?php endforeach; ?>
            </div><!-- /.demande-group-body -->
        </div><!-- /.demande-group -->
        <?php endforeach; endif; ?>
    </div><!-- /.tab-valider -->

    <!-- ══════════════════════════════════════
         TAB 3 : STATISTIQUES
    ══════════════════════════════════════ -->
    <div class="lab-tab-pane" id="tab-stats">

        <!-- Résumé chiffré en haut -->
        <div class="row g-3 mb-4">
            <?php
            $statsCards = [
                ['Validés aujourd\'hui', $nb_valides_auj, 'bi-check2-all', '#10b981', '#ecfdf5'],
                ['Rejetés aujourd\'hui', $nb_rejetes_auj, 'bi-x-circle', '#ef4444', '#fef2f2'],
                ['En cours (techniciens)', $nb_en_cours, 'bi-hourglass-split', '#3b82f6', '#eff6ff'],
                ['Taux de rejet 7j', $taux_rejet.'%', 'bi-percent', '#f43f5e', '#fff1f2'],
                ['Délai moyen 7j', $delai_7j.'h', 'bi-clock-history', '#8b5cf6', '#f5f3ff'],
                ['Examens soumis', $stats_bio['soumis_total'] ?? 0, 'bi-arrow-up-circle', '#f59e0b', '#fffbeb'],
            ];
            foreach ($statsCards as [$label, $val, $icon, $color, $bg]):
            ?>
            <div class="col-6 col-md-4 col-xl-2">
                <div class="stat-panel d-flex align-items-center gap-3 p-3" style="background:<?= $bg ?>;border-left:4px solid <?= $color ?>;">
                    <i class="bi <?= $icon ?>" style="font-size:1.6rem;color:<?= $color ?>;flex-shrink:0;"></i>
                    <div>
                        <div style="font-size:.69rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.4px;"><?= $label ?></div>
                        <div style="font-size:1.4rem;font-weight:800;color:<?= $color ?>;"><?= $val ?></div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="stats-grid">
            <!-- Graphe activité 7 jours -->
            <div class="stat-panel">
                <div class="stat-panel-title">
                    <i class="bi bi-bar-chart-line" style="color:#6366f1;"></i>
                    Examens validés — 7 derniers jours
                </div>
                <canvas id="chartParJour" height="220"></canvas>
            </div>

            <!-- Répartition par catégorie -->
            <div class="stat-panel">
                <div class="stat-panel-title">
                    <i class="bi bi-pie-chart" style="color:#f59e0b;"></i>
                    Répartition par catégorie (aujourd'hui)
                </div>
                <?php if (empty($stats_bio['par_categorie'])): ?>
                <p class="text-muted text-center mt-4">Aucune donnée pour aujourd'hui.</p>
                <?php else: ?>
                <canvas id="chartCategorie" height="220"></canvas>
                <?php endif; ?>
            </div>

            <!-- Activité récente -->
            <div class="stat-panel">
                <div class="stat-panel-title">
                    <i class="bi bi-activity" style="color:#10b981;"></i>
                    Activité récente (10 dernières actions)
                </div>
                <?php if (empty($stats_bio['activite_recente'])): ?>
                <p class="text-muted text-center mt-4">Aucune activité récente.</p>
                <?php else: foreach ($stats_bio['activite_recente'] as $act):
                    $isVal = $act['statut'] === 'VALIDE';
                ?>
                <div class="activite-row">
                    <div class="activite-icon <?= $isVal ? 'ai-valide' : 'ai-rejete' ?>">
                        <i class="bi <?= $isVal ? 'bi-check-circle-fill' : 'bi-x-circle-fill' ?>"></i>
                    </div>
                    <div class="activite-info">
                        <div class="activite-nom"><?= htmlspecialchars($act['nom_examen']) ?></div>
                        <div class="activite-sub"><?= htmlspecialchars($act['patient_nom'].' '.$act['patient_prenom']) ?></div>
                    </div>
                    <div class="activite-time"><?= !empty($act['date_validation']) ? date('d/m H:i', strtotime($act['date_validation'])) : '—' ?></div>
                </div>
                <?php endforeach; endif; ?>
            </div>

            <!-- Tableau catégories + taux completion -->
            <div class="stat-panel">
                <div class="stat-panel-title">
                    <i class="bi bi-table" style="color:#0891b2;"></i>
                    Avancement par catégorie (aujourd'hui)
                </div>
                <?php if (empty($stats_bio['par_categorie'])): ?>
                <p class="text-muted text-center mt-4">Aucune donnée pour aujourd'hui.</p>
                <?php else: ?>
                <div class="table-responsive">
                <table class="table table-sm align-middle" style="font-size:.83rem;">
                    <thead>
                        <tr style="background:#f8fafc;">
                            <th class="fw-bold text-muted" style="font-size:.7rem;text-transform:uppercase;letter-spacing:.5px;">Catégorie</th>
                            <th class="fw-bold text-muted" style="font-size:.7rem;text-transform:uppercase;letter-spacing:.5px;text-align:center;">Total</th>
                            <th class="fw-bold text-muted" style="font-size:.7rem;text-transform:uppercase;letter-spacing:.5px;text-align:center;">Validés</th>
                            <th class="fw-bold text-muted" style="font-size:.7rem;text-transform:uppercase;letter-spacing:.5px;">Avancement</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($stats_bio['par_categorie'] as $cat):
                        $pct = ($cat['nb'] > 0) ? round($cat['nb_valides'] / $cat['nb'] * 100) : 0;
                        $barColor = $pct >= 80 ? '#10b981' : ($pct >= 50 ? '#f59e0b' : '#ef4444');
                    ?>
                    <tr>
                        <td class="fw-semibold"><?= htmlspecialchars($cat['categorie'] ?: 'AUTRE') ?></td>
                        <td class="text-center fw-bold"><?= $cat['nb'] ?></td>
                        <td class="text-center fw-bold" style="color:#10b981;"><?= $cat['nb_valides'] ?></td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div style="flex:1;background:#e2e8f0;border-radius:4px;height:8px;overflow:hidden;">
                                    <div style="width:<?= $pct ?>%;height:100%;background:<?= $barColor ?>;border-radius:4px;transition:width .4s;"></div>
                                </div>
                                <span style="font-size:.72rem;font-weight:700;color:<?= $barColor ?>;min-width:32px;"><?= $pct ?>%</span>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                </div>
                <?php endif; ?>
            </div>
        </div><!-- /.stats-grid -->
    </div><!-- /.tab-stats -->

</div><!-- /.lab-body -->
</main>

<!-- ══ MODAL DISPATCH ══ -->
<div class="modal fade" id="modalDispatch" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius:18px;">
            <div class="modal-header" style="background:linear-gradient(135deg,#6366f1,#4f46e5);color:#fff;border-radius:18px 18px 0 0;">
                <h5 class="modal-title fw-bold"><i class="bi bi-people me-2"></i>Dispatcher les examens
                    <small id="dispatchPatientName" class="fw-normal opacity-75 ms-2"></small>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="padding:22px;">
                <div id="dispatchLoading" class="text-center py-4">
                    <div class="spinner-border text-primary"></div>
                    <p class="mt-2 text-muted">Chargement…</p>
                </div>
                <div id="dispatchContent" style="display:none;">
                    <p class="text-muted small mb-3"><i class="bi bi-info-circle me-1"></i>Assignez chaque examen à un technicien.</p>
                    <div id="dispatchExamensList"></div>
                </div>
            </div>
            <div class="modal-footer border-0" style="padding:14px 22px;">
                <button type="button" class="btn btn-outline-secondary rounded-pill" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-primary rounded-pill px-4 fw-bold" id="btnConfirmDispatch" onclick="confirmerDispatch()">
                    <i class="bi bi-send me-1"></i>Confirmer
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ══ MODAL REJET ══ -->
<div class="modal fade" id="modalRejet" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius:18px;">
            <div class="modal-header" id="rejetModalHeader" style="background:#fef2f2;border-radius:18px 18px 0 0;border-bottom:1px solid #fecaca;">
                <h5 class="modal-title fw-bold text-danger" id="rejetModalTitle">
                    <i class="bi bi-x-circle me-2"></i>Rejeter l'examen
                    <small id="rejetExamenName" class="fw-normal text-muted ms-2"></small>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="padding:22px;">
                <div class="alert alert-warning border-0 rounded-3 small mb-3">
                    <i class="bi bi-exclamation-triangle me-1"></i>
                    Le technicien devra recommencer l'analyse. Il verra votre motif.
                </div>
                <label class="form-label fw-semibold">Motif <span class="text-danger">*</span></label>
                <textarea id="motifRejet" class="form-control rounded-3" rows="3"
                          placeholder="Ex : Échantillon hémolysé, résultat incohérent…"></textarea>
                <input type="hidden" id="rejetExamenId">
            </div>
            <div class="modal-footer border-0" style="padding:14px 22px;">
                <button type="button" class="btn btn-outline-secondary rounded-pill" data-bs-dismiss="modal">Annuler</button>
                <button type="button" id="btnConfirmRejet" class="btn btn-danger rounded-pill px-4 fw-bold" onclick="confirmerRejet()">
                    <i class="bi bi-x-circle me-1"></i>Confirmer
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ══ MODAL N° ORDRE ══ -->
<div class="modal fade" id="modalNumOrdre" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered" style="max-width:400px;">
        <div class="modal-content border-0 shadow-lg" style="border-radius:18px;overflow:hidden;">
            <div class="modal-header border-0 pb-2"
                 style="background:linear-gradient(135deg,#0e7490,#0891b2);color:#fff;padding:18px 22px 14px;">
                <div>
                    <h5 class="modal-title fw-bold mb-0"><i class="bi bi-hash me-2"></i>Numéro d'ordre</h5>
                    <small class="opacity-75" id="ordreModalPatient">—</small>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="padding:20px 22px;">
                <label class="form-label fw-semibold" style="font-size:.84rem;">Numéro <span class="text-danger">*</span></label>
                <input type="text" id="inputNumOrdre" class="form-control fw-bold text-center"
                       style="font-size:1.1rem;letter-spacing:2px;font-family:monospace;border-radius:10px;border:2px solid #e2e8f0;"
                       placeholder="000-000" maxlength="20" oninput="formatNumOrdre(this)">
                <div class="invalid-feedback" id="ordreErreur">Format : XXX-XXX (ex : 001-042)</div>
                <small class="text-muted d-block mt-1 mb-3" style="font-size:.76rem;">Format : <strong>XXX-XXX</strong></small>
                <div class="d-flex flex-wrap gap-1" id="ordreChips"></div>
            </div>
            <div class="modal-footer border-0" style="padding:10px 22px 16px;">
                <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">Annuler</button>
                <button type="button" id="btnSauvegarderOrdre" class="btn rounded-pill px-4 fw-bold text-white"
                        style="background:#0e7490;" onclick="sauvegarderNumeroOrdre()">
                    <i class="bi bi-check2 me-1"></i>Enregistrer
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ══ TOAST ══ -->
<div id="labToast" class="toast-lab toast align-items-center border-0" role="alert" aria-live="assertive" aria-atomic="true">
    <div class="d-flex">
        <div class="toast-body" id="labToastBody">Message</div>
        <button type="button" class="btn-close me-2 m-auto" data-bs-dismiss="toast"></button>
    </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<style>
@keyframes pulse-green { 0%,100%{opacity:1} 50%{opacity:.3} }
</style>

<script>
const BASE = '<?= BASE_URL ?>';

/* ── Horloge ── */
(function tick() {
    const el = document.getElementById('lab-clock');
    if (!el) return;
    const n = new Date(), p = x => String(x).padStart(2,'0');
    el.textContent = p(n.getHours())+':'+p(n.getMinutes())+':'+p(n.getSeconds());
    setTimeout(tick, 1000);
})();

/* ── Tabs ── */
function switchTab(name) {
    document.querySelectorAll('.lab-tab-btn').forEach(b => b.classList.remove('active'));
    document.querySelectorAll('.lab-tab-pane').forEach(p => p.classList.remove('active'));
    document.getElementById('tab-'+name+'-btn').classList.add('active');
    document.getElementById('tab-'+name).classList.add('active');
}

/* ── Filtres ── */
function filtrerDemandes() {
    const statut   = document.getElementById('filtreStatut').value;
    const priorite = document.getElementById('filtrePriorite').value;
    const rech     = document.getElementById('recherchePatient').value.toLowerCase();
    document.querySelectorAll('.demande-row').forEach(row => {
        let ok = true;
        if (statut   && row.dataset.statut   !== statut)   ok = false;
        if (priorite && row.dataset.priorite !== priorite) ok = false;
        if (rech     && !row.dataset.patient.includes(rech)) ok = false;
        row.style.display = ok ? '' : 'none';
    });
}

/* ── Toast ── */
function showToast(msg, ok = true) {
    const t = document.getElementById('labToast');
    t.className = 'toast-lab toast align-items-center border-0 text-white ' + (ok ? 'bg-success' : 'bg-danger');
    document.getElementById('labToastBody').textContent = msg;
    new bootstrap.Toast(t, {delay: 4500}).show();
}

/* ── Accordéon groupe ── */
function toggleGroup(bodyId) {
    const body = document.getElementById(bodyId);
    const chev = document.getElementById('chevron-'+bodyId);
    if (!body) return;
    body.classList.toggle('collapsed');
    if (chev) chev.style.transform = body.classList.contains('collapsed') ? 'rotate(-90deg)' : 'rotate(0deg)';
}

/* ══════════════════════════
   DISPATCH
══════════════════════════ */
let _dispatchDemandeId = null;

async function ouvrirDispatch(demandeId, patientName) {
    _dispatchDemandeId = demandeId;
    document.getElementById('dispatchPatientName').textContent = '— '+patientName;
    document.getElementById('dispatchLoading').style.display = '';
    document.getElementById('dispatchContent').style.display = 'none';
    new bootstrap.Modal(document.getElementById('modalDispatch')).show();
    try {
        const [examens, techs] = await Promise.all([
            fetch(BASE+'laboratoire/examens-dispatch/'+demandeId).then(r => r.json()),
            fetch(BASE+'laboratoire/get-techniciens').then(r => r.json())
        ]);
        renderDispatch(examens, techs);
    } catch {
        document.getElementById('dispatchLoading').innerHTML = '<p class="text-danger">Erreur de chargement.</p>';
    }
}

function renderDispatch(examens, techs) {
    const rows = examens.map(ex => {
        const opts = '<option value="">— Choisir —</option>' +
            techs.map(t => `<option value="${t.id}" ${t.id==ex.technicien_id?'selected':''}>${t.nom_complet} (${t.charge_actuelle})</option>`).join('');
        const badge = ex.statut==='ASSIGNEE' ? '<span class="badge bg-info-subtle text-info ms-1" style="font-size:.63rem;">Déjà assigné</span>' : '';
        return `<div class="disp-row">
            <div class="flex-grow-1"><div class="disp-name">${ex.nom_examen} ${badge}</div><div class="disp-cat">${ex.categorie}</div></div>
            <select class="disp-sel" data-examen-id="${ex.id}">${opts}</select>
        </div>`;
    }).join('');
    document.getElementById('dispatchExamensList').innerHTML = rows || '<p class="text-muted">Aucun examen.</p>';
    document.getElementById('dispatchLoading').style.display = 'none';
    document.getElementById('dispatchContent').style.display = '';
}

async function confirmerDispatch() {
    const assignments = {};
    let hasAny = false;
    document.querySelectorAll('.disp-sel').forEach(s => {
        if (s.value) { assignments[s.dataset.examenId] = parseInt(s.value); hasAny = true; }
    });
    if (!hasAny) { showToast('Assignez au moins un examen.', false); return; }
    const btn = document.getElementById('btnConfirmDispatch');
    btn.disabled = true; btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Envoi…';
    try {
        const res  = await fetch(BASE+'laboratoire/dispatcher/'+_dispatchDemandeId, {
            method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({assignments})
        });
        const data = await res.json();
        bootstrap.Modal.getInstance(document.getElementById('modalDispatch')).hide();
        showToast(data.message || (data.success ? 'Dispatch effectué.' : 'Erreur.'), data.success);
        if (data.success) setTimeout(() => location.reload(), 1500);
    } catch { showToast('Erreur réseau.', false); }
    finally { btn.disabled=false; btn.innerHTML='<i class="bi bi-send me-1"></i>Confirmer'; }
}

/* ══════════════════════════
   VALIDER EXAMEN INDIVIDUEL
══════════════════════════ */
async function validerExamen(examenId, groupId, btn) {
    if (!confirm('Valider définitivement cet examen ?')) return;
    btn.disabled = true; btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>';
    const commentaire = (document.getElementById('comment-'+examenId)?.value || '').trim();
    try {
        const res  = await fetch(BASE+'laboratoire/valider-examen/'+examenId, {
            method:'POST', headers:{'Content-Type':'application/json'},
            body:JSON.stringify({commentaire})
        });
        const data = await res.json();
        if (data.success) {
            showToast(data.tous_valides ? '✅ Tous les examens validés — Médecin notifié !' : 'Examen validé.', true);
            removeExamenCard(examenId, groupId);
        } else {
            showToast(data.message || 'Erreur.', false);
            btn.disabled=false; btn.innerHTML='<i class="bi bi-check-circle"></i>Valider';
        }
    } catch { showToast('Erreur réseau.', false); btn.disabled=false; btn.innerHTML='<i class="bi bi-check-circle"></i>Valider'; }
}

/* ══════════════════════════
   VALIDER TOUTE UNE DEMANDE (batch)
══════════════════════════ */
async function validerDemandeBatch(demandeId, btn) {
    const nb = document.querySelectorAll(`#group-${demandeId} .val-card`).length;
    if (!confirm(`Valider les ${nb} examen(s) de cette demande en une fois ?`)) return;
    btn.disabled = true; btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Validation…';
    try {
        const res  = await fetch(BASE+'laboratoire/valider-demande/'+demandeId, {
            method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({})
        });
        const data = await res.json();
        if (data.success) {
            showToast(`✅ ${data.message}`, true);
            // Supprimer tout le groupe
            const groupEl = document.getElementById('group-'+demandeId);
            if (groupEl) { groupEl.style.transition='opacity .35s'; groupEl.style.opacity='0'; setTimeout(() => groupEl.remove(), 380); }
            // Mettre à jour les compteurs
            setTimeout(() => {
                const total = document.querySelectorAll('.val-card').length;
                const badge = document.getElementById('badge-tab-valider');
                if (badge) badge.textContent = total;
                const kpi = document.getElementById('kpi-a-valider');
                if (kpi) kpi.textContent = total;
                if (total === 0) {
                    document.getElementById('tab-valider').innerHTML = `
                        <div class="empty-state" style="background:#fff;border-radius:18px;box-shadow:0 4px 16px rgba(0,0,0,.07);">
                            <i class="bi bi-patch-check"></i>
                            <p class="fw-semibold mb-1">Aucun examen en attente de validation</p>
                            <small>Tous les examens ont été traités.</small>
                        </div>`;
                }
            }, 420);
        } else {
            showToast(data.message || 'Erreur.', false);
            btn.disabled=false; btn.innerHTML=`<i class="bi bi-check2-all"></i> Valider tout (${nb})`;
        }
    } catch { showToast('Erreur réseau.', false); btn.disabled=false; btn.innerHTML=`<i class="bi bi-check2-all"></i> Valider tout (${nb})`; }
}

/* ── Supprimer une carte examen ── */
function removeExamenCard(examenId, groupId) {
    const card = document.getElementById('val-card-'+examenId);
    if (!card) return;
    const gid = groupId ?? card.dataset.group;
    card.classList.add('removing');
    setTimeout(() => {
        card.remove();
        const groupEl = document.getElementById('group-'+gid);
        if (groupEl) {
            const rem = groupEl.querySelectorAll('.val-card').length;
            if (rem === 0) { groupEl.remove(); }
            else {
                const cb = document.getElementById('count-'+gid);
                if (cb) cb.textContent = rem+' examen'+(rem>1?'s':'');
            }
        }
        const total = document.querySelectorAll('.val-card').length;
        const badge = document.getElementById('badge-tab-valider');
        if (badge) badge.textContent = total;
        const kpi = document.getElementById('kpi-a-valider');
        if (kpi) kpi.textContent = total;
        if (total === 0) {
            document.getElementById('tab-valider').innerHTML = `
                <div class="empty-state" style="background:#fff;border-radius:18px;box-shadow:0 4px 16px rgba(0,0,0,.07);">
                    <i class="bi bi-patch-check"></i>
                    <p class="fw-semibold mb-1">Aucun examen en attente de validation</p>
                    <small>Tous les examens ont été traités.</small>
                </div>`;
        }
    }, 380);
}

/* ══════════════════════════
   REJET / REFAIRE
══════════════════════════ */
let _rejetMode = 'rejeter';

function ouvrirRejet(examenId, examenName) {
    _rejetMode = 'rejeter';
    document.getElementById('rejetExamenId').value = examenId;
    document.getElementById('rejetExamenName').textContent = examenName;
    document.getElementById('motifRejet').value = '';
    document.getElementById('motifRejet').placeholder = 'Ex: Échantillon hémolysé, résultat incohérent…';
    document.getElementById('rejetModalHeader').style.background = '#fef2f2';
    document.getElementById('rejetModalTitle').className = 'modal-title fw-bold text-danger';
    const btn = document.getElementById('btnConfirmRejet');
    btn.className = 'btn btn-danger rounded-pill px-4 fw-bold';
    btn.innerHTML = '<i class="bi bi-x-circle me-1"></i>Confirmer le rejet';
    new bootstrap.Modal(document.getElementById('modalRejet')).show();
}

function ouvrirRefaire(examenId, examenName) {
    _rejetMode = 'refaire';
    document.getElementById('rejetExamenId').value = examenId;
    document.getElementById('rejetExamenName').textContent = examenName;
    document.getElementById('motifRejet').value = '';
    document.getElementById('motifRejet').placeholder = 'Ex: Résultat incohérent avec la clinique…';
    document.getElementById('rejetModalHeader').style.background = '#fffbeb';
    document.getElementById('rejetModalTitle').className = 'modal-title fw-bold text-warning';
    const btn = document.getElementById('btnConfirmRejet');
    btn.className = 'btn btn-warning rounded-pill px-4 fw-bold';
    btn.innerHTML = '<i class="bi bi-arrow-repeat me-1"></i>Demander un refaire';
    new bootstrap.Modal(document.getElementById('modalRejet')).show();
}

async function confirmerRejet() {
    const examenId = document.getElementById('rejetExamenId').value;
    const motif    = document.getElementById('motifRejet').value.trim();
    if (!motif) { document.getElementById('motifRejet').classList.add('is-invalid'); return; }
    document.getElementById('motifRejet').classList.remove('is-invalid');
    try {
        const res  = await fetch(BASE+'laboratoire/rejeter-examen/'+examenId, {
            method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({motif})
        });
        const data = await res.json();
        bootstrap.Modal.getInstance(document.getElementById('modalRejet')).hide();
        showToast(data.message || (data.success ? 'Traité.' : 'Erreur.'), data.success);
        if (data.success) {
            const card = document.getElementById('val-card-'+examenId);
            removeExamenCard(examenId, card?.dataset.group ?? null);
        }
    } catch { showToast('Erreur réseau.', false); }
}

/* ══════════════════════════
   N° ORDRE
══════════════════════════ */
let _ordreDemandeId = null;

function ouvrirModalOrdre(demandeId, patientName, currentOrdre) {
    _ordreDemandeId = demandeId;
    document.getElementById('ordreModalPatient').textContent = patientName;
    const input = document.getElementById('inputNumOrdre');
    input.value = currentOrdre || '';
    input.classList.remove('is-invalid');
    const chips = document.getElementById('ordreChips');
    chips.innerHTML = '';
    _suggestions(currentOrdre).forEach(s => {
        const b = document.createElement('button');
        b.type='button'; b.className='btn btn-sm btn-outline-secondary rounded-pill px-3';
        b.style.cssText='font-size:.7rem;font-family:monospace;';
        b.textContent=s; b.onclick=() => { input.value=s; input.classList.remove('is-invalid'); };
        chips.appendChild(b);
    });
    document.getElementById('btnSauvegarderOrdre').disabled = false;
    document.getElementById('btnSauvegarderOrdre').innerHTML = '<i class="bi bi-check2 me-1"></i>Enregistrer';
    new bootstrap.Modal(document.getElementById('modalNumOrdre')).show();
    setTimeout(() => input.focus(), 350);
}

function formatNumOrdre(input) {
    let v = input.value.toUpperCase().replace(/[^A-Z0-9-]/g,'');
    const parts = v.split('-');
    if (parts.length === 1 && v.length > 3) v = v.slice(0,3)+'-'+v.slice(3);
    if (parts.length >= 2) v = parts[0].slice(0,6)+'-'+parts.slice(1).join('').replace(/-/g,'').slice(0,6);
    input.value = v; input.classList.remove('is-invalid');
}

function _suggestions(current) {
    if (current && /^[A-Z0-9]+-[A-Z0-9]+$/i.test(current)) {
        const [pre, suf] = current.split('-'); const n = parseInt(suf, 10);
        if (!isNaN(n)) return [pre+'-'+String(Math.max(1,n-1)).padStart(suf.length,'0'), current, pre+'-'+String(n+1).padStart(suf.length,'0')].filter((v,i,a)=>a.indexOf(v)===i);
    }
    const m = String(new Date().getMonth()+1).padStart(2,'0');
    return ['001-001','LAB-001',m+'-001','BIO-001'];
}

async function sauvegarderNumeroOrdre() {
    const input = document.getElementById('inputNumOrdre');
    const val   = input.value.trim().toUpperCase();
    if (!/^[A-Z0-9]{1,6}-[A-Z0-9]{1,6}$/.test(val)) { input.classList.add('is-invalid'); return; }
    const btn = document.getElementById('btnSauvegarderOrdre');
    btn.disabled=true; btn.innerHTML='<span class="spinner-border spinner-border-sm me-1"></span>Enregistrement…';
    try {
        const res  = await fetch(BASE+'laboratoire/numero-ordre/'+_ordreDemandeId, {
            method:'POST', headers:{'Content-Type':'application/json','X-Requested-With':'XMLHttpRequest'},
            body:JSON.stringify({numero_ordre:val})
        });
        const data = await res.json();
        if (data.success) {
            bootstrap.Modal.getInstance(document.getElementById('modalNumOrdre')).hide();
            showToast('✅ '+data.message, true);
            const label = document.getElementById('ordre-label-'+_ordreDemandeId);
            if (label) { label.textContent=val; label.closest('.btn-num-ordre')?.classList.add('has-ordre'); }
        } else { showToast('❌ '+(data.message||'Erreur'), false); btn.disabled=false; btn.innerHTML='<i class="bi bi-check2 me-1"></i>Enregistrer'; }
    } catch { showToast('Erreur réseau.', false); btn.disabled=false; btn.innerHTML='<i class="bi bi-check2 me-1"></i>Enregistrer'; }
}

/* ══════════════════════════
   CHARTS (Tab Statistiques)
══════════════════════════ */
(function initCharts() {
    // Données PHP → JS
    const parJour = <?= json_encode($stats_bio['par_jour'] ?? []) ?>;
    const parCat  = <?= json_encode($stats_bio['par_categorie'] ?? []) ?>;

    // ── Graphe 7 jours ──
    const ctxJ = document.getElementById('chartParJour');
    if (ctxJ && parJour.length) {
        // Remplir les 7 derniers jours même si absents de la BDD
        const days = [];
        for (let i = 6; i >= 0; i--) {
            const d = new Date(); d.setDate(d.getDate()-i);
            days.push(d.toISOString().slice(0,10));
        }
        const jMap = {};
        parJour.forEach(r => { jMap[r.jour] = {nb: parseInt(r.nb_valides)||0, urg: parseInt(r.nb_urgents)||0}; });
        const labels   = days.map(d => { const dt=new Date(d+'T00:00:00'); return dt.toLocaleDateString('fr-FR',{weekday:'short',day:'numeric'}); });
        const nbData   = days.map(d => jMap[d]?.nb || 0);
        const urgData  = days.map(d => jMap[d]?.urg || 0);

        new Chart(ctxJ, {
            type: 'bar',
            data: {
                labels,
                datasets: [
                    { label:'Validés', data:nbData, backgroundColor:'rgba(99,102,241,.75)', borderColor:'#6366f1', borderRadius:6 },
                    { label:'dont urgents', data:urgData, backgroundColor:'rgba(239,68,68,.65)', borderColor:'#ef4444', borderRadius:6 },
                ]
            },
            options: {
                responsive:true, maintainAspectRatio:false,
                plugins:{ legend:{ labels:{ font:{size:12}, color:'#64748b' } } },
                scales: {
                    y:{ beginAtZero:true, grid:{color:'#f1f5f9'}, ticks:{color:'#94a3b8'}, min:0 },
                    x:{ grid:{display:false}, ticks:{color:'#64748b'} }
                }
            }
        });
    }

    // ── Camembert catégories ──
    const ctxC = document.getElementById('chartCategorie');
    if (ctxC && parCat.length) {
        const catLabels = parCat.map(r => r.categorie || 'AUTRE');
        const catData   = parCat.map(r => parseInt(r.nb)||0);
        const palette   = ['#6366f1','#f59e0b','#10b981','#ef4444','#0891b2','#8b5cf6','#f43f5e','#84cc16'];

        new Chart(ctxC, {
            type: 'doughnut',
            data: {
                labels: catLabels,
                datasets:[{ data:catData, backgroundColor:palette.slice(0,catData.length), borderWidth:2, borderColor:'#fff' }]
            },
            options: {
                responsive:true, maintainAspectRatio:false,
                plugins:{ legend:{ position:'bottom', labels:{ font:{size:11}, color:'#64748b', padding:14 } } }
            }
        });
    }
})();

/* ── Tab initial via querystring ── */
<?php if (isset($_GET['tab']) && in_array($_GET['tab'], ['valider','stats','demandes'])): ?>
switchTab('<?= htmlspecialchars($_GET['tab']) ?>');
<?php endif; ?>

/* ── Auto-refresh 90s ── */
setTimeout(() => location.reload(), 90000);
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
