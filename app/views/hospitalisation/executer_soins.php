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
// Calculs globaux
$total    = count($soins);
$realises = count(array_filter($soins, fn($s) => $s['statut'] === 'REALISE'));
$annules  = count(array_filter($soins, fn($s) => $s['statut'] === 'ANNULE'));
$actifs   = $total - $annules;
$pct      = $actifs > 0 ? round($realises / $actifs * 100) : 0;

// Grouper par date puis par type_soin
$grouped_by_date = [];
foreach ($soins as $s) {
    $dateKey = substr($s['date_prevue'] ?? '', 0, 10); // YYYY-MM-DD
    if (!$dateKey) $dateKey = 'sans_date';
    $grouped_by_date[$dateKey][$s['type_soin']][] = $s;
}
ksort($grouped_by_date); // Tri chronologique

$patient_nom = strtoupper($soins[0]['nom'] ?? '') . ' ' . ($soins[0]['prenom'] ?? '');
$dossier_num = $soins[0]['dossier_numero'] ?? '';
$service_nom = $soins[0]['nom_service'] ?? '';
$admission_id = $soins[0]['admission_id'] ?? $plan_id;

// On retrouve le patient_id depuis l'admission
$patient_id_val = 0;
foreach ($soins as $s) {
    if (!empty($s['patient_id'])) { $patient_id_val = $s['patient_id']; break; }
}
?>

<style>
:root { --clr-ok:#16a34a; --clr-warn:#d97706; --clr-err:#dc2626; --clr-blue:#2563eb; }
body { background:#f1f5f9; }
.sidebar { display:none !important; }
main { margin-left:0 !important; width:100% !important; }

/* ── Header ── */
.exec-header {
    background:linear-gradient(135deg,#1e40af,#2563eb);
    color:#fff; padding:20px 32px;
    display:flex; align-items:center; justify-content:space-between;
    flex-wrap:wrap; gap:12px;
}
.exec-header .pat-info h4 { margin:0; font-size:1.15rem; font-weight:800; }
.exec-header .pat-info small { opacity:.8; font-size:.8rem; }

/* ── Barre de progression ── */
.progress-bar-wrap { background:rgba(255,255,255,.2); border-radius:8px; height:8px; overflow:hidden; min-width:200px; }
.progress-bar-fill { height:100%; border-radius:8px; background:#4ade80; transition:width .4s; }

/* ── Corps ── */
.exec-body { max-width:960px; margin:28px auto; padding:0 16px; }

/* ── Groupe de soins ── */
.soin-group { background:#fff; border-radius:14px; box-shadow:0 2px 10px rgba(0,0,0,.06);
    margin-bottom:20px; overflow:hidden; }
.soin-group-header {
    padding:10px 18px; background:#f8fafc; border-bottom:1px solid #e2e8f0;
    display:flex; align-items:center; gap:8px;
    font-size:.78rem; font-weight:800; text-transform:uppercase; letter-spacing:.06em; color:#475569;
}
.soin-group-header .count-badge { margin-left:auto; }

/* ── Ligne de soin ── */
.soin-row {
    display:flex; align-items:center; gap:12px;
    padding:14px 18px; border-bottom:1px solid #f1f5f9;
    transition:background .15s;
    position:relative;
}
.soin-row:last-child { border-bottom:none; }
.soin-row:hover { background:#fafbfc; }
.soin-row.realise  { background:#f0fdf4; }
.soin-row.annule   { background:#fef2f2; opacity:.65; }
.soin-row.annule .soin-desc { text-decoration:line-through; color:#94a3b8; }

/* Checkbox custom */
.soin-check {
    width:22px; height:22px; border-radius:6px; border:2px solid #cbd5e1;
    appearance:none; cursor:pointer; flex-shrink:0; transition:all .15s;
    display:flex; align-items:center; justify-content:center;
}
.soin-check:checked { background:var(--clr-ok); border-color:var(--clr-ok); }
.soin-check:checked::after { content:'✓'; color:#fff; font-size:.8rem; font-weight:900; }
.soin-check:disabled { cursor:default; opacity:.7; }

/* Heure badge */
.heure-badge {
    background:#1e293b; color:#fff; border-radius:20px;
    padding:3px 10px; font-size:.72rem; font-weight:700;
    flex-shrink:0; font-family:monospace;
}
.heure-badge.retard { background:#dc2626; }

/* Contenu */
.soin-content { flex:1; min-width:0; }
.soin-desc { font-weight:600; font-size:.9rem; color:#1e293b; }
.soin-cond { display:inline-block; margin-top:3px; padding:1px 8px;
    border-radius:20px; background:#fef9c3; border:1px solid #fde68a;
    color:#92400e; font-size:.7rem; font-weight:600; }
.soin-meta { font-size:.7rem; color:#64748b; margin-top:4px; }
.soin-meta .by { font-weight:600; color:#2563eb; }

/* Badges statut */
.badge-realise { background:#dcfce7; color:var(--clr-ok); border-radius:20px;
    padding:3px 10px; font-size:.7rem; font-weight:700; }
.badge-annule  { background:#fee2e2; color:var(--clr-err); border-radius:20px;
    padding:3px 10px; font-size:.7rem; font-weight:700; }
.badge-corr    { background:#ede9fe; color:#7c3aed; border-radius:20px;
    padding:3px 10px; font-size:.7rem; font-weight:700; }

/* Bouton Rayer */
.btn-rayer {
    background:none; border:1.5px dashed #fca5a5; color:#dc2626; border-radius:8px;
    padding:4px 10px; font-size:.7rem; font-weight:700; cursor:pointer;
    transition:all .15s; flex-shrink:0;
}
.btn-rayer:hover { background:#fee2e2; }

/* Bouton Stopper */
.btn-stopper {
    background:none; border:1.5px dashed #fbbf24; color:#d97706; border-radius:8px;
    padding:4px 10px; font-size:.7rem; font-weight:700; cursor:pointer;
    transition:all .15s; flex-shrink:0;
}
.btn-stopper:hover { background:#fef3c7; }

/* Panel stopper */
.stopper-panel {
    display:none; background:#fffbeb; border:1px solid #fde68a;
    border-radius:10px; margin:0 18px 12px 52px; padding:14px;
}
.stopper-panel.open { display:block; }

/* Bouton Modifier soin (icône uniquement) */
.btn-modif-soin {
    background:none; border:1.5px solid #fcd34d; color:#92400e; border-radius:8px;
    padding:4px 9px; font-size:.78rem; cursor:pointer; transition:all .15s; flex-shrink:0;
    display:inline-flex; align-items:center;
}
.btn-modif-soin:hover { background:#fef3c7; transform:translateY(-1px); }

/* Bouton Supprimer soin (icône uniquement) */
.btn-suppr-soin {
    background:none; border:1.5px solid #cbd5e1; color:#64748b; border-radius:8px;
    padding:4px 9px; font-size:.78rem; cursor:pointer; transition:all .15s; flex-shrink:0;
    display:inline-flex; align-items:center;
}
.btn-suppr-soin:hover { background:#fee2e2; color:#dc2626; border-color:#fca5a5; }

/* Modal de rayure */
.rayer-panel {
    display:none; background:#fff7f7; border:1px solid #fecaca;
    border-radius:10px; margin:0 18px 12px 52px; padding:14px;
}
.rayer-panel.open { display:block; }

/* Commentaire infirmier */
.soin-commentaire {
    display:flex; align-items:flex-start; gap:5px;
    margin-top:5px; font-size:.72rem; color:#5b21b6;
    background:#f5f3ff; border-left:3px solid #7c3aed;
    padding:3px 8px; border-radius:0 6px 6px 0;
    line-height:1.4;
}
.soin-commentaire span { flex:1; word-break:break-word; }
/* Bouton commentaire */
.btn-comment-soin {
    background:none; border:1.5px solid #ddd6fe; color:#7c3aed;
    border-radius:8px; padding:4px 8px; font-size:.78rem;
    cursor:pointer; transition:all .15s; flex-shrink:0;
}
.btn-comment-soin:hover,
.btn-comment-soin.has-comment { background:#f5f3ff; border-color:#7c3aed; color:#5b21b6; }
/* Panel de commentaire */
.comment-panel {
    background:#faf5ff; border:1px solid #ddd6fe;
    border-radius:10px; margin:0 18px 12px 52px; padding:14px;
}

/* Footer sticky */
.exec-footer {
    position:sticky; bottom:0; background:#fff;
    border-top:1px solid #e2e8f0; padding:14px 24px;
    display:flex; align-items:center; justify-content:space-between;
    flex-wrap:wrap; gap:10px; z-index:100;
    box-shadow:0 -4px 20px rgba(0,0,0,.07);
    max-width:960px; margin:0 auto;
    border-radius:14px 14px 0 0;
}
.btn-valider {
    background:var(--clr-ok); color:#fff; border:none; border-radius:30px;
    padding:10px 32px; font-weight:800; font-size:.95rem; cursor:pointer;
    transition:filter .15s;
}
.btn-valider:hover { filter:brightness(1.1); }
.btn-valider:disabled { background:#94a3b8; cursor:not-allowed; }

/* Toast notification */
#toast {
    position:fixed; bottom:80px; right:20px; background:#1e293b; color:#fff;
    border-radius:10px; padding:10px 18px; font-size:.82rem;
    opacity:0; pointer-events:none; transition:opacity .3s; z-index:9999;
}
#toast.show { opacity:1; }

/* ══ DATE GROUP — Sections collapsibles ══ */
.date-group { margin-bottom: 24px; }

.date-group-header {
    display: flex; align-items: center; justify-content: space-between;
    padding: 12px 18px; cursor: pointer; border-radius: 14px;
    margin-bottom: 12px; color: #fff; user-select: none;
    transition: filter .15s;
}
.date-group-header:hover { filter: brightness(1.08); }

.date-group-header.today   { background: linear-gradient(90deg, #065f46 0%, #059669 100%); }
.date-group-header.past    { background: linear-gradient(90deg, #1e293b 0%, #334155 100%); }
.date-group-header.future  { background: linear-gradient(90deg, #1e3a8a 0%, #1e40af 100%); }
.date-group-header.unknown { background: linear-gradient(90deg, #4b5563 0%, #6b7280 100%); }

.date-group-header .dg-left {
    display: flex; align-items: center; gap: 12px;
}
.date-group-header .dg-icon {
    width: 38px; height: 38px; border-radius: 10px;
    background: rgba(255,255,255,.15);
    display: flex; align-items: center; justify-content: center;
    font-size: 1.1rem; flex-shrink: 0;
}
.date-group-header .dg-title {
    font-size: .95rem; font-weight: 800; line-height: 1.2;
}
.date-group-header .dg-sub {
    font-size: .72rem; opacity: .75; margin-top: 2px; font-weight: 500;
}
.date-group-header .dg-right {
    display: flex; align-items: center; gap: 12px;
}
.date-group-header .dg-badge {
    background: rgba(255,255,255,.18); border-radius: 20px;
    padding: 3px 12px; font-size: .75rem; font-weight: 700;
}
.date-chevron {
    font-size: .95rem; transition: transform .25s ease;
    opacity: .8;
}
.date-group.collapsed .date-chevron { transform: rotate(-90deg); }
.date-group-body { /* toujours visible par défaut */ }
.date-group.collapsed .date-group-body { display: none; }

/* ══ DOULEUR — Bannière ══ */
.douleur-banner {
    max-width:960px; margin:16px auto 0; padding:0 16px;
}
.douleur-required {
    background:#fff7ed; border:2px dashed #f97316;
    border-radius:12px; padding:13px 20px;
    display:flex; align-items:center; gap:14px; cursor:pointer;
    transition:all .2s;
}
.douleur-required:hover { background:#ffedd5; border-color:#ea580c; }
.douleur-done {
    background:#f0fdf4; border:2px solid #22c55e;
    border-radius:12px; padding:13px 20px;
    display:flex; align-items:center; gap:14px;
}
.douleur-icon { font-size:1.6rem; flex-shrink:0; }
.douleur-sev-bar {
    flex:1; height:8px; background:#e2e8f0; border-radius:20px; overflow:hidden;
}
.douleur-sev-fill { height:100%; border-radius:20px; transition:width .5s; }

/* ══ DOULEUR — Modal ══ */
.modal-douleur .modal-content {
    border:none; border-radius:20px;
    box-shadow:0 30px 60px rgba(0,0,0,.18);
}
.modal-douleur .modal-header {
    background:linear-gradient(135deg,#1e40af,#7c3aed);
    border-radius:20px 20px 0 0; border:none; padding:20px 28px;
}

/* Steps indicator */
.steps-indicator { display:flex; align-items:center; gap:0; padding:18px 28px 0; }
.step-dot {
    width:32px; height:32px; border-radius:50%; display:flex; align-items:center;
    justify-content:center; font-weight:800; font-size:.8rem; flex-shrink:0;
    transition:all .3s;
}
.step-dot.active { background:#1e40af; color:#fff; }
.step-dot.done   { background:#22c55e; color:#fff; }
.step-dot.pending{ background:#e2e8f0; color:#94a3b8; }
.step-line { flex:1; height:3px; background:#e2e8f0; margin:0 4px; }
.step-line.done { background:#22c55e; }
.step-label { font-size:.68rem; text-align:center; color:#64748b; font-weight:600; margin-top:4px; }

/* Profil cards */
.profil-card {
    border:2px solid #e2e8f0; border-radius:14px; padding:18px 14px;
    text-align:center; cursor:pointer; transition:all .2s; background:#fff;
}
.profil-card:hover { border-color:#1e40af; background:#eff6ff; transform:translateY(-2px); }
.profil-card.selected { border-color:#1e40af; background:#eff6ff;
    box-shadow:0 0 0 3px rgba(30,64,175,.15); }
.profil-card .profil-icon { font-size:2.2rem; display:block; margin-bottom:8px; }
.profil-card .profil-name { font-weight:800; font-size:.85rem; color:#1e293b; }
.profil-card .profil-hint { font-size:.7rem; color:#64748b; margin-top:3px; }

/* Échelle tabs */
.echelle-tab {
    border:1.5px solid #e2e8f0; border-radius:10px; padding:10px 14px;
    cursor:pointer; transition:all .2s; background:#fff; font-size:.82rem;
    display:flex; align-items:center; gap:8px;
}
.echelle-tab:hover  { border-color:#1e40af; background:#eff6ff; }
.echelle-tab.active { border-color:#1e40af; background:#1e40af; color:#fff; }
.echelle-tab .echelle-badge {
    font-size:.65rem; font-weight:800; padding:1px 7px;
    border-radius:20px; background:rgba(0,0,0,.12); flex-shrink:0;
}

/* Slider douleur */
.pain-slider-wrap { position:relative; padding:0 4px; }
.pain-slider {
    -webkit-appearance:none; width:100%; height:8px; border-radius:20px; outline:none;
    background:linear-gradient(to right,#22c55e,#eab308 50%,#ef4444);
    cursor:pointer;
}
.pain-slider::-webkit-slider-thumb {
    -webkit-appearance:none; width:24px; height:24px; border-radius:50%;
    background:#fff; border:3px solid #1e40af;
    box-shadow:0 2px 8px rgba(0,0,0,.2); cursor:pointer;
}
.pain-value-display {
    font-size:3rem; font-weight:900; text-align:center;
    line-height:1; margin:10px 0 4px;
    transition:color .3s;
}
.pain-label-display { text-align:center; font-size:.82rem; font-weight:700;
    letter-spacing:.5px; text-transform:uppercase; }

/* EVS radios */
.evs-option { display:flex; align-items:center; gap:12px; padding:10px 14px;
    border:2px solid #e2e8f0; border-radius:10px; cursor:pointer; transition:all .2s; }
.evs-option:hover { border-color:#1e40af; }
.evs-option.selected { border-color:#1e40af; background:#eff6ff; }
.evs-option input[type=radio] { display:none; }
.evs-dot { width:16px; height:16px; border-radius:50%; flex-shrink:0; }

/* ALGOPLUS / FLACC items */
.algo-item {
    display:flex; align-items:flex-start; gap:10px; padding:10px 14px;
    border:1.5px solid #e2e8f0; border-radius:10px; cursor:pointer;
    transition:all .15s; margin-bottom:6px; background:#fff;
}
.algo-item:hover  { border-color:#f97316; background:#fff7ed; }
.algo-item.ticked { border-color:#f97316; background:#fff7ed; }
.algo-item input  { display:none; }
.algo-tick { width:20px; height:20px; border:2px solid #cbd5e1; border-radius:5px;
    flex-shrink:0; display:flex; align-items:center; justify-content:center; margin-top:1px; }
.algo-item.ticked .algo-tick { background:#f97316; border-color:#f97316; color:#fff; font-size:.8rem; font-weight:900; }

/* FLACC sub-score */
.flacc-row { margin-bottom:10px; }
.flacc-row label { font-size:.78rem; font-weight:700; color:#374151; margin-bottom:4px; display:block; }
.flacc-options { display:flex; gap:6px; }
.flacc-opt {
    flex:1; border:1.5px solid #e2e8f0; border-radius:8px; padding:6px 8px;
    cursor:pointer; text-align:center; font-size:.72rem; font-weight:600;
    transition:all .15s; background:#fff;
}
.flacc-opt:hover   { border-color:#1e40af; }
.flacc-opt.picked  { border-color:#1e40af; background:#1e40af; color:#fff; }

/* FPS-R faces */
.fps-faces { display:flex; gap:8px; justify-content:center; flex-wrap:wrap; margin:8px 0; }
.fps-face {
    width:56px; cursor:pointer; text-align:center;
    border:2px solid #e2e8f0; border-radius:10px; padding:6px 4px;
    transition:all .15s; background:#fff;
}
.fps-face:hover   { border-color:#1e40af; transform:scale(1.05); }
.fps-face.picked  { border-color:#1e40af; background:#eff6ff; transform:scale(1.08); }
.fps-face .face-emoji { font-size:1.6rem; display:block; }
.fps-face .face-score { font-size:.68rem; font-weight:800; color:#1e293b; }

/* Severity colors */
.sev-ABSENT  { color:#16a34a; } .bg-sev-ABSENT  { background:#22c55e; }
.sev-LEGERE  { color:#2563eb; } .bg-sev-LEGERE  { background:#3b82f6; }
.sev-MODEREE { color:#d97706; } .bg-sev-MODEREE { background:#f59e0b; }
.sev-INTENSE { color:#dc2626; } .bg-sev-INTENSE { background:#ef4444; }
</style>

<!-- HEADER -->
<div class="exec-header">
    <div class="pat-info">
        <h4><i class="bi bi-clipboard2-pulse-fill me-2"></i><?= htmlspecialchars($patient_nom) ?></h4>
        <small>
            <?= htmlspecialchars($dossier_num) ?>
            <?php if($service_nom): ?> &bull; <?= htmlspecialchars($service_nom) ?><?php endif; ?>
            &bull; <?= date('d/m/Y') ?>
        </small>
    </div>
    <div class="d-flex flex-column align-items-end gap-2">
        <div class="d-flex align-items-center gap-10" style="gap:12px;">
            <span style="font-size:.82rem;opacity:.85;"><?= $realises ?>/<?= $actifs ?> soins réalisés</span>
            <span style="font-size:1.1rem;font-weight:800;"><?= $pct ?>%</span>
        </div>
        <div class="progress-bar-wrap" style="min-width:220px;">
            <div class="progress-bar-fill" id="globalBar" style="width:<?= $pct ?>%"></div>
        </div>
    </div>
</div>

<!-- ══ BANNIÈRE ÉVALUATION DOULEUR ══ -->
<div class="douleur-banner">
    <!-- État : non évaluée (cliquable) -->
    <div id="douleurRequired" class="douleur-required" onclick="ouvrirModalDouleur()">
        <span class="douleur-icon">⚠️</span>
        <div class="flex-grow-1">
            <div class="fw-bold" style="color:#ea580c;font-size:.92rem">
                Évaluation de la douleur requise avant la validation
            </div>
            <div class="small text-muted mt-1">
                Cliquez ici pour évaluer la douleur du patient (échelles HAS 2022)
            </div>
        </div>
        <button type="button" class="btn btn-warning rounded-pill fw-bold px-4"
                style="font-size:.82rem;white-space:nowrap">
            <i class="bi bi-heart-pulse-fill me-1"></i> Évaluer maintenant
        </button>
    </div>

    <!-- État : évaluée (affiché après AJAX) -->
    <div id="douleurDone" class="douleur-done" style="display:none">
        <span class="douleur-icon">✅</span>
        <div class="flex-grow-1">
            <div class="fw-bold" style="color:#15803d;font-size:.92rem" id="douleurDoneTitle">
                Douleur évaluée
            </div>
            <div class="d-flex align-items-center gap-3 mt-2">
                <div class="douleur-sev-bar" style="max-width:180px">
                    <div class="douleur-sev-fill" id="douleurSevFill" style="width:0%"></div>
                </div>
                <span id="douleurSevLabel" class="fw-bold small"></span>
            </div>
        </div>
        <button type="button" class="btn btn-outline-success btn-sm rounded-pill"
                onclick="ouvrirModalDouleur()" style="font-size:.75rem">
            <i class="bi bi-pencil"></i> Réévaluer
        </button>
    </div>
</div>

<!-- CORPS -->
<div class="exec-body">

<form id="formExecution" action="<?= BASE_URL ?>hospitalisation/valider-execution" method="POST">
    <input type="hidden" name="admission_id"    value="<?= htmlspecialchars($admission_id) ?>">
    <input type="hidden" name="patient_id"      value="<?= htmlspecialchars($patient_id_val) ?>">
    <input type="hidden" name="douleur_eval_id" id="douleurEvalId" value="">

<?php
$today     = date('Y-m-d');
$yesterday = date('Y-m-d', strtotime('-1 day'));
$tomorrow  = date('Y-m-d', strtotime('+1 day'));
$frDays    = ['Sunday'=>'Dimanche','Monday'=>'Lundi','Tuesday'=>'Mardi',
              'Wednesday'=>'Mercredi','Thursday'=>'Jeudi','Friday'=>'Vendredi','Saturday'=>'Samedi'];
$frMonths  = ['January'=>'janvier','February'=>'février','March'=>'mars','April'=>'avril',
              'May'=>'mai','June'=>'juin','July'=>'juillet','August'=>'août',
              'September'=>'septembre','October'=>'octobre','November'=>'novembre','December'=>'décembre'];
function fmtDateFR(string $d): string {
    global $frDays, $frMonths;
    $ts  = strtotime($d);
    $day = date('l', $ts); $mon = date('F', $ts);
    return ($frDays[$day] ?? $day).' '.date('d', $ts).' '.($frMonths[$mon] ?? $mon).' '.date('Y', $ts);
}

foreach ($grouped_by_date as $dateKey => $grouped):
    // ── Label & classe couleur ──
    if ($dateKey === 'sans_date') {
        $dgTitle = 'Date non définie'; $dgSub = ''; $dgClass = 'unknown'; $dgIcon = 'bi-question-circle';
    } elseif ($dateKey === $today) {
        $dgTitle = "Aujourd'hui"; $dgSub = date('d/m/Y'); $dgClass = 'today'; $dgIcon = 'bi-calendar-check-fill';
    } elseif ($dateKey === $yesterday) {
        $dgTitle = 'Hier'; $dgSub = date('d/m/Y', strtotime($dateKey)); $dgClass = 'past'; $dgIcon = 'bi-calendar-x-fill';
    } elseif ($dateKey < $today) {
        $dgTitle = fmtDateFR($dateKey); $dgSub = 'Date passée'; $dgClass = 'past'; $dgIcon = 'bi-calendar-minus-fill';
    } elseif ($dateKey === $tomorrow) {
        $dgTitle = 'Demain'; $dgSub = date('d/m/Y', strtotime($dateKey)); $dgClass = 'future'; $dgIcon = 'bi-calendar-plus-fill';
    } else {
        $dgTitle = fmtDateFR($dateKey); $dgSub = 'À venir'; $dgClass = 'future'; $dgIcon = 'bi-calendar-event-fill';
    }
    // ── Compteurs de la journée ──
    $dgReal = $dgAnn = $dgTot = 0;
    foreach ($grouped as $lignes) {
        foreach ($lignes as $s) {
            if ($s['statut'] !== 'ANNULE') $dgTot++;
            if ($s['statut'] === 'REALISE') $dgReal++;
            if ($s['statut'] === 'ANNULE')  $dgAnn++;
        }
    }
    $dgId = 'dg-' . md5($dateKey);
    // Futures dates repliées par défaut
    $dgCollapsed = ($dateKey !== 'sans_date' && $dateKey > $today) ? 'collapsed' : '';
?>

<!-- ══ DATE GROUP : <?= $dateKey ?> ══ -->
<div class="date-group <?= $dgCollapsed ?>" id="<?= $dgId ?>">
    <div class="date-group-header <?= $dgClass ?>" onclick="toggleDateGroup('<?= $dgId ?>')">
        <div class="dg-left">
            <div class="dg-icon"><i class="bi <?= $dgIcon ?>"></i></div>
            <div>
                <div class="dg-title"><?= htmlspecialchars($dgTitle) ?></div>
                <?php if ($dgSub): ?>
                <div class="dg-sub"><?= htmlspecialchars($dgSub) ?></div>
                <?php endif; ?>
            </div>
        </div>
        <div class="dg-right">
            <span class="dg-badge">
                <?= $dgReal ?>/<?= $dgTot ?> réalisé<?= $dgReal > 1 ? 's' : '' ?>
            </span>
            <i class="bi bi-chevron-down date-chevron"></i>
        </div>
    </div>

    <div class="date-group-body">
    <?php foreach ($grouped as $typeSoin => $lignes): ?>
    <?php
        $nbReal = count(array_filter($lignes, fn($s)=>$s['statut']==='REALISE'));
        $nbAnn  = count(array_filter($lignes, fn($s)=>$s['statut']==='ANNULE'));
        $nbTot  = count($lignes);
    ?>
    <div class="soin-group">
        <div class="soin-group-header">
            <i class="bi bi-tag-fill"></i>
            <?= htmlspecialchars($typeSoin) ?>
            <span class="count-badge">
                <span class="badge-realise"><?= $nbReal ?>/<?= $nbTot-$nbAnn ?></span>
            </span>
        </div>

    <?php foreach ($lignes as $s):
        $isRealise  = $s['statut'] === 'REALISE';
        $isAnnule   = $s['statut'] === 'ANNULE';
        $isSupprime = $s['statut'] === 'SUPPRIME';
        $isPlanifie = !$isRealise && !$isAnnule && !$isSupprime;
        $isStoppe   = $isAnnule && str_starts_with($s['note_execution'] ?? '', 'Stoppé');
        $heure      = substr($s['date_prevue'] ?? '', 11, 5) ?: '--:--';
        // Comparer date+heure complètes pour ne pas marquer "retard" un soin du lendemain
        $datePrevueStr = substr($s['date_prevue'] ?? '', 0, 16); // 'YYYY-MM-DD HH:MM'
        $nowStr     = date('Y-m-d H:i');
        $enRetard   = $isPlanifie && $datePrevueStr !== '' && $datePrevueStr < $nowStr;
        // Soin futur : pas encore exécutable (date_prevue pas encore atteinte)
        $isFutur    = $isPlanifie && $datePrevueStr !== '' && $datePrevueStr > $nowStr;
        $isCorr     = !empty($s['note_execution']) && str_starts_with($s['note_execution'], 'CORR.');
    ?>
    <div class="soin-row <?= $isRealise?'realise':($isAnnule?'annule':($isSupprime?'supprime':'')) ?>"
         id="row-<?= $s['id'] ?>"
         <?= $isSupprime ? 'style="opacity:.52;background:#f8f9fa;pointer-events:none;"' : '' ?>>
    <?php if ($isSupprime): ?>
    <div style="position:absolute;top:6px;right:8px;z-index:2;">
        <span style="font-size:.68rem;background:#dc3545;color:#fff;padding:2px 7px;border-radius:20px;font-weight:700;">
            <i class="bi bi-trash3-fill me-1"></i>SUPPRIMÉ
        </span>
    </div>
    <?php endif; ?>

        <!-- Checkbox -->
        <input type="checkbox"
               class="soin-check"
               name="soins_faits[]"
               value="<?= $s['id'] ?>"
               id="chk-<?= $s['id'] ?>"
               <?= $isRealise  ? 'checked' : '' ?>
               <?= ($isAnnule || $isFutur) ? 'disabled' : '' ?>
               <?= $isFutur ? 'title="Ce soin n\'est pas encore disponible"' : '' ?>
               onchange="cocherSoin(<?= $s['id'] ?>, this.checked)">

        <!-- Heure -->
        <span class="heure-badge <?= $enRetard?'retard':'' ?>"><?= $heure ?></span>

        <!-- Contenu -->
        <div class="soin-content">
            <div class="soin-desc"><?= htmlspecialchars($s['description'] ?? '') ?></div>
            <?php if (!empty($s['condition_application'])): ?>
            <span class="soin-cond">⚡ <?= htmlspecialchars($s['condition_application']) ?></span>
            <?php endif; ?>
            <?php
            // Afficher le commentaire infirmier (distinct de "Rayé:" et "CORR.")
            $hasComment = !empty($s['note_execution'])
                       && !str_starts_with($s['note_execution'], 'Rayé')
                       && !str_starts_with($s['note_execution'], 'CORR.');
            if ($hasComment): ?>
            <div class="soin-commentaire" id="comment-display-<?= $s['id'] ?>">
                <i class="bi bi-chat-text-fill" style="color:#7c3aed;font-size:.75rem;"></i>
                <span><?= htmlspecialchars($s['note_execution']) ?></span>
            </div>
            <?php else: ?>
            <div class="soin-commentaire" id="comment-display-<?= $s['id'] ?>" style="display:none;">
                <i class="bi bi-chat-text-fill" style="color:#7c3aed;font-size:.75rem;"></i>
                <span></span>
            </div>
            <?php endif; ?>
            <div class="soin-meta">
                <?php if ($isSupprime): ?>
                    <i class="bi bi-trash3-fill text-danger"></i>
                    Supprimé par <span class="by"><?= htmlspecialchars(($s['supprime_par_nom'] ?? '?').' '.($s['supprime_par_prenom'] ?? '')) ?></span>
                    <?php if(!empty($s['date_suppression'])): ?>
                        le <?= date('d/m/Y à H:i', strtotime($s['date_suppression'])) ?>
                    <?php endif; ?>
                    — <em><?= htmlspecialchars($s['motif_suppression'] ?? '') ?></em>
                <?php elseif ($isRealise && !empty($s['executant_nom'])): ?>
                    <i class="bi bi-person-check-fill text-success"></i>
                    Réalisé par <span class="by"><?= htmlspecialchars($s['executant_nom'].' '.$s['executant_prenom']) ?></span>
                    <?php if(!empty($s['date_realisee'])): ?>
                        à <?= date('H:i', strtotime($s['date_realisee'])) ?>
                    <?php endif; ?>
                <?php elseif ($isAnnule): ?>
                    <i class="bi bi-slash-circle text-danger"></i>
                    Rayé — <?= htmlspecialchars($s['note_execution'] ?? '') ?>
                <?php elseif ($isCorr): ?>
                    <span class="badge-corr">CORRECTION</span>
                <?php elseif ($isFutur): ?>
                    <i class="bi bi-lock text-secondary" style="font-size:.75rem;"></i>
                    <span class="text-secondary fw-semibold" style="font-size:.75rem;">
                        Disponible le <?= date('d/m', strtotime($s['date_prevue'])) ?> à <?= $heure ?>
                    </span>
                <?php elseif ($enRetard): ?>
                    <i class="bi bi-clock text-danger"></i> <span class="text-danger fw-semibold">En retard</span>
                <?php endif; ?>
            </div>
        </div>

        <!-- Badges droite -->
        <div class="d-flex align-items-center gap-2 flex-shrink-0">
            <?php if ($isRealise): ?>
                <!-- Bouton commentaire sur les soins réalisés -->
                <button type="button"
                        class="btn-comment-soin <?= $hasComment ? 'has-comment' : '' ?>"
                        title="<?= $hasComment ? 'Modifier le commentaire' : 'Ajouter un commentaire' ?>"
                        onclick="ouvrirCommentaire(<?= $s['id'] ?>, <?= htmlspecialchars(json_encode($hasComment ? $s['note_execution'] : ''), ENT_QUOTES) ?>)">
                    <i class="bi bi-chat-text<?= $hasComment ? '-fill' : '' ?>"></i>
                </button>
                <span class="badge-realise"><i class="bi bi-check2"></i> Effectué</span>
            <?php elseif ($isAnnule): ?>
                <span class="badge-annule"><i class="bi bi-<?= $isStoppe ? 'stop-circle' : 'x' ?>"></i> <?= $isStoppe ? 'Stoppé' : 'Rayé' ?></span>
            <?php elseif ($isSupprime): ?>
                <!-- rien : la ligne est grisée, non interactive -->
            <?php elseif ($isFutur): ?>
                <!-- Soin futur : badge cadenas uniquement, aucune action possible -->
                <span style="font-size:.72rem;background:#f1f5f9;color:#64748b;border:1px solid #e2e8f0;
                             padding:3px 10px;border-radius:20px;font-weight:600;">
                    <i class="bi bi-hourglass-split me-1"></i>À venir
                </span>
            <?php else: ?>
                <!-- Bouton commentaire sur les soins en attente / en retard -->
                <button type="button"
                        class="btn-comment-soin <?= $hasComment ? 'has-comment' : '' ?>"
                        title="<?= $hasComment ? 'Modifier le commentaire' : 'Ajouter un commentaire' ?>"
                        onclick="ouvrirCommentaire(<?= $s['id'] ?>, <?= htmlspecialchars(json_encode($hasComment ? $s['note_execution'] : ''), ENT_QUOTES) ?>)">
                    <i class="bi bi-chat-text<?= $hasComment ? '-fill' : '' ?>"></i>
                </button>
                <!-- Bouton Modifier (édition de l'heure / description avant exécution) -->
                <button type="button" class="btn-modif-soin"
                        title="Modifier ce soin"
                        onclick='ouvrirModifSoin(<?= htmlspecialchars(json_encode([
                            "id" => (int)$s["id"],
                            "type_soin" => $s["type_soin"] ?? "",
                            "description" => $s["description"] ?? "",
                            "heure" => $heure,
                            "condition" => $s["condition_application"] ?? "",
                        ]), ENT_QUOTES) ?>)'>
                    <i class="bi bi-pencil-fill"></i>
                </button>
                <!-- Bouton Supprimer — ouvre modal de motif -->
                <button type="button" class="btn-suppr-soin"
                        title="Supprimer ce soin"
                        onclick="ouvrirModalSuppression(<?= (int)$s['id'] ?>, <?= htmlspecialchars(json_encode($s['description'] ?? ''), ENT_QUOTES) ?>)">
                    <i class="bi bi-trash"></i>
                </button>
                <!-- Bouton Rayer (seulement pour soins actifs) -->
                <button type="button" class="btn-rayer" onclick="ouvrirRayer(<?= $s['id'] ?>)">
                    <i class="bi bi-pencil-slash"></i> Rayer
                </button>
                <!-- Bouton Stopper (arrête ce soin + toutes les occurrences du même médicament ce jour) -->
                <button type="button" class="btn-stopper"
                        onclick="ouvrirStopper(<?= $s['id'] ?>, <?= htmlspecialchars(json_encode($s['description'] ?? ''), ENT_QUOTES) ?>)"
                        title="Stopper ce médicament (toutes les prises de la journée)">
                    <i class="bi bi-stop-circle"></i> Stopper
                </button>
            <?php endif; ?>
        </div>
    </div>

    <!-- Panel rayure inline (caché par défaut) -->
    <?php if ($isPlanifie): ?>
    <div class="rayer-panel" id="panel-rayer-<?= $s['id'] ?>">
        <div class="fw-semibold text-danger mb-2" style="font-size:.82rem;">
            <i class="bi bi-pencil-slash me-1"></i>Rayer et corriger — <?= htmlspecialchars($s['description']) ?>
        </div>
        <div class="row g-2">
            <div class="col-md-4">
                <label class="form-label" style="font-size:.75rem;">Motif de la rayure</label>
                <input type="text" class="form-control form-control-sm"
                       id="motif-<?= $s['id'] ?>" placeholder="ex: erreur de dosage, doublon…">
            </div>
            <div class="col-md-6">
                <label class="form-label" style="font-size:.75rem;">Ligne corrigée <small class="text-muted">(laisser vide si annulation simple)</small></label>
                <input type="text" class="form-control form-control-sm"
                       id="corr-<?= $s['id'] ?>" placeholder="Nouvelle description corrigée…"
                       value="<?= htmlspecialchars($s['description'] ?? '') ?>">
            </div>
            <div class="col-md-2 d-flex align-items-end gap-1">
                <button type="button" class="btn btn-danger btn-sm w-100" onclick="confirmerRayer(<?= $s['id'] ?>)">
                    <i class="bi bi-check2"></i> Valider
                </button>
                <button type="button" class="btn btn-light btn-sm" onclick="fermerRayer(<?= $s['id'] ?>)">
                    <i class="bi bi-x"></i>
                </button>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Panel stopper inline (caché par défaut) -->
    <?php if ($isPlanifie): ?>
    <div class="stopper-panel" id="panel-stopper-<?= $s['id'] ?>">
        <div class="fw-semibold text-warning mb-2" style="font-size:.82rem;color:#d97706!important;">
            <i class="bi bi-stop-circle me-1"></i>Stopper le médicament — <span class="stopper-desc"><?= htmlspecialchars($s['description']) ?></span>
        </div>
        <p style="font-size:.75rem;color:#78716c;margin-bottom:8px;">
            <i class="bi bi-info-circle me-1"></i>Toutes les prises de ce médicament prévues <strong>aujourd'hui</strong> seront annulées.
        </p>
        <div class="row g-2">
            <div class="col-md-8">
                <label class="form-label" style="font-size:.75rem;">Motif de l'arrêt <small class="text-muted">(obligatoire)</small></label>
                <input type="text" class="form-control form-control-sm"
                       id="motif-stopper-<?= $s['id'] ?>" placeholder="ex: décision médicale, intolérance, doublon…">
            </div>
            <div class="col-md-4 d-flex align-items-end gap-1">
                <button type="button" class="btn btn-warning btn-sm w-100" style="color:#fff;"
                        onclick="confirmerStopper(<?= $s['id'] ?>)">
                    <i class="bi bi-check2"></i> Confirmer l'arrêt
                </button>
                <button type="button" class="btn btn-light btn-sm" onclick="fermerStopper(<?= $s['id'] ?>)">
                    <i class="bi bi-x"></i>
                </button>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Panel commentaire infirmier -->
    <?php if (!$isSupprime && !$isAnnule): ?>
    <div class="comment-panel" id="panel-comment-<?= $s['id'] ?>" style="display:none;">
        <div class="fw-semibold mb-2" style="font-size:.82rem;color:#7c3aed;">
            <i class="bi bi-chat-text-fill me-1"></i>Commentaire — <?= htmlspecialchars($s['description']) ?>
        </div>
        <div class="d-flex gap-2 align-items-end">
            <div style="flex:1;">
                <textarea class="form-control form-control-sm"
                          id="comment-text-<?= $s['id'] ?>"
                          rows="2"
                          placeholder="Ajouter une observation, note d'exécution, précision…"
                          maxlength="500"><?= htmlspecialchars($hasComment ? $s['note_execution'] : '') ?></textarea>
                <div style="font-size:.68rem;color:#94a3b8;margin-top:2px;">Visible chez le médecin dans le suivi infirmier.</div>
            </div>
            <div class="d-flex flex-column gap-1">
                <button type="button" class="btn btn-sm"
                        style="background:#7c3aed;color:#fff;white-space:nowrap;"
                        onclick="sauvegarderCommentaire(<?= $s['id'] ?>)">
                    <i class="bi bi-check2 me-1"></i>Enregistrer
                </button>
                <button type="button" class="btn btn-light btn-sm"
                        onclick="fermerCommentaire(<?= $s['id'] ?>)">
                    <i class="bi bi-x me-1"></i>Annuler
                </button>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php endforeach; // end lignes (soin-row) ?>
    </div><!-- /soin-group -->
    <?php endforeach; // end type_soin ?>
    </div><!-- /date-group-body -->
</div><!-- /date-group -->
<?php endforeach; // end date ?>

<!-- FOOTER -->
<div class="exec-footer">
    <div class="d-flex align-items-center gap-3">
        <a href="<?= BASE_URL ?>hospitalisation/suivi/<?= $patient_id_val ?>" class="btn btn-light btn-sm rounded-pill">
            <i class="bi bi-arrow-left"></i> Retour
        </a>
        <span id="footerStatus" class="text-muted" style="font-size:.8rem;">
            <span id="footerRealises"><?= $realises ?></span>/<?= $actifs ?> soins réalisés
        </span>
    </div>
    <button type="button" class="btn-valider" id="btnValider"
            style="background:#94a3b8;cursor:not-allowed"
            onclick="soumettreFormulaire()"
            title="Évaluez d'abord la douleur du patient">
        <i class="bi bi-heart-pulse-fill me-2"></i>Évaluer la douleur d'abord
    </button>
</div>

</form>
</div><!-- /exec-body -->

<!-- ══════════════════════════════════════════════════════════════
     MODAL ÉVALUATION DE LA DOULEUR — HAS 2022
══════════════════════════════════════════════════════════════ -->
<div class="modal fade modal-douleur" id="modalDouleur" tabindex="-1" aria-hidden="true"
     data-bs-backdrop="static" data-bs-keyboard="false">
<div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
<div class="modal-content">

    <!-- Header -->
    <div class="modal-header">
        <div>
            <h5 class="modal-title fw-bold text-white mb-0">
                <i class="bi bi-heart-pulse-fill me-2"></i>Évaluation de la douleur
            </h5>
            <small class="text-white opacity-75">
                Échelles validées HAS 2022 — <?= htmlspecialchars($patient_nom) ?>
            </small>
        </div>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                id="btnFermerModal"></button>
    </div>

    <!-- Indicateur d'étapes -->
    <div class="px-4 pt-3 pb-0">
        <div class="steps-indicator">
            <div>
                <div class="step-dot active" id="stepDot1">1</div>
                <div class="step-label">Profil</div>
            </div>
            <div class="step-line" id="stepLine1"></div>
            <div>
                <div class="step-dot pending" id="stepDot2">2</div>
                <div class="step-label">Échelle</div>
            </div>
            <div class="step-line" id="stepLine2"></div>
            <div>
                <div class="step-dot pending" id="stepDot3">3</div>
                <div class="step-label">Score</div>
            </div>
        </div>
    </div>

    <div class="modal-body p-4">

    <!-- ═══ ÉTAPE 1 : PROFIL ═══ -->
    <div id="stepContent1">
        <p class="text-muted small mb-3">
            <i class="bi bi-info-circle me-1"></i>
            Sélectionnez le profil du patient pour afficher les échelles appropriées.
        </p>
        <div class="row g-3">
            <div class="col-md-4">
                <div class="profil-card" onclick="selectionnerProfil('ADULTE_COMM', this)">
                    <span class="profil-icon">🗣️</span>
                    <div class="profil-name">Adulte communiquant</div>
                    <div class="profil-hint">≥ 15 ans, capable<br>d'auto-évaluation</div>
                    <div class="mt-2">
                        <span class="badge bg-primary-subtle text-primary rounded-pill" style="font-size:.65rem">EVN · EN · EVS</span>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="profil-card" onclick="selectionnerProfil('ADULTE_NON_COMM', this)">
                    <span class="profil-icon">🧓</span>
                    <div class="profil-name">Adulte non-communiquant</div>
                    <div class="profil-hint">Confusion, démence,<br>troubles conscience</div>
                    <div class="mt-2">
                        <span class="badge bg-warning-subtle text-warning-emphasis rounded-pill" style="font-size:.65rem">ALGOPLUS · DOLOPLUS-2</span>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="profil-card" onclick="selectionnerProfil('PEDIATRIQUE', this)">
                    <span class="profil-icon">👶</span>
                    <div class="profil-name">Pédiatrique</div>
                    <div class="profil-hint">Nouveau-né<br>à 14 ans</div>
                    <div class="mt-2">
                        <span class="badge bg-success-subtle text-success rounded-pill" style="font-size:.65rem">EVENDOL · FLACC · FPS-R</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══ ÉTAPE 2 : CHOIX ÉCHELLE ═══ -->
    <div id="stepContent2" style="display:none">
        <p class="text-muted small mb-3">Choisissez l'échelle la plus adaptée à la situation clinique.</p>

        <!-- Adulte communiquant -->
        <div id="echellesADULTE_COMM" style="display:none">
            <div class="row g-2">
                <div class="col-12">
                    <div class="echelle-tab" onclick="selectionnerEchelle('EVN',10,this)"
                         data-desc="Le patient donne un chiffre de 0 à 10 verbalement">
                        <span class="echelle-badge">EVN</span>
                        <div><strong>Échelle Verbale Numérique</strong>
                            <div class="small opacity-75">Patient communique un chiffre de 0 (pas de douleur) à 10 (douleur maximale)</div>
                        </div>
                        <span class="ms-auto text-nowrap opacity-60" style="font-size:.72rem">0 – 10</span>
                    </div>
                </div>
                <div class="col-12">
                    <div class="echelle-tab" onclick="selectionnerEchelle('EN',10,this)"
                         data-desc="Le patient écrit ou indique un chiffre de 0 à 10">
                        <span class="echelle-badge">EN</span>
                        <div><strong>Échelle Numérique</strong>
                            <div class="small opacity-75">Écrite ou montrée — 0 (absent) à 10 (insupportable)</div>
                        </div>
                        <span class="ms-auto text-nowrap opacity-60" style="font-size:.72rem">0 – 10</span>
                    </div>
                </div>
                <div class="col-12">
                    <div class="echelle-tab" onclick="selectionnerEchelle('EVS',3,this)"
                         data-desc="4 niveaux verbaux : Absent / Faible / Modéré / Intense">
                        <span class="echelle-badge">EVS</span>
                        <div><strong>Échelle Verbale Simple</strong>
                            <div class="small opacity-75">Absent (0) · Faible (1) · Modéré (2) · Intense (3)</div>
                        </div>
                        <span class="ms-auto text-nowrap opacity-60" style="font-size:.72rem">0 – 3</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Adulte non-communiquant -->
        <div id="echellesADULTE_NON_COMM" style="display:none">
            <div class="row g-2">
                <div class="col-12">
                    <div class="echelle-tab" onclick="selectionnerEchelle('ALGOPLUS',5,this)"
                         data-desc="5 items comportementaux — seuil de traitement > 2">
                        <span class="echelle-badge">ALGOPLUS</span>
                        <div><strong>Algoplus</strong>
                            <div class="small opacity-75">Hétéro-évaluation rapide — 5 items binaires. Seuil : score ≥ 2</div>
                        </div>
                        <span class="ms-auto text-nowrap opacity-60" style="font-size:.72rem">0 – 5</span>
                    </div>
                </div>
                <div class="col-12">
                    <div class="echelle-tab" onclick="selectionnerEchelle('DOLOPLUS2',30,this)"
                         data-desc="10 items regroupés en 3 domaines — seuil ≥ 5">
                        <span class="echelle-badge">DOLOPLUS-2</span>
                        <div><strong>Doloplus-2</strong>
                            <div class="small opacity-75">Personnes âgées — 10 items (réactions somatiques, psychomotrices, psychosociales). Seuil : ≥ 5</div>
                        </div>
                        <span class="ms-auto text-nowrap opacity-60" style="font-size:.72rem">0 – 30</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pédiatrique -->
        <div id="échellesPEDIATRIQUE" style="display:none">
            <div class="row g-2">
                <div class="col-12">
                    <div class="echelle-tab" onclick="selectionnerEchelle('EVENDOL',15,this)"
                         data-desc="0–7 ans — 5 items cotés 0 à 3">
                        <span class="echelle-badge">EVENDOL</span>
                        <div><strong>Evendol</strong>
                            <div class="small opacity-75">Nouveau-né à 7 ans — 5 items (0–3 chacun). Seuil : ≥ 4</div>
                        </div>
                        <span class="ms-auto text-nowrap opacity-60" style="font-size:.72rem">0 – 15</span>
                    </div>
                </div>
                <div class="col-12">
                    <div class="echelle-tab" onclick="selectionnerEchelle('FLACC',10,this)"
                         data-desc="2 mois – 7 ans — 5 items cotés 0 à 2">
                        <span class="echelle-badge">FLACC</span>
                        <div><strong>FLACC</strong>
                            <div class="small opacity-75">2 mois – 7 ans — Face, Jambes, Activité, Cri, Consolabilité. Seuil : ≥ 4</div>
                        </div>
                        <span class="ms-auto text-nowrap opacity-60" style="font-size:.72rem">0 – 10</span>
                    </div>
                </div>
                <div class="col-12">
                    <div class="echelle-tab" onclick="selectionnerEchelle('FPS_R',10,this)"
                         data-desc="4–12 ans — auto-évaluation par les visages">
                        <span class="echelle-badge">FPS-R</span>
                        <div><strong>Faces Pain Scale-Revised</strong>
                            <div class="small opacity-75">4 à 12 ans — auto-évaluation par 6 visages (0, 2, 4, 6, 8, 10)</div>
                        </div>
                        <span class="ms-auto text-nowrap opacity-60" style="font-size:.72rem">0 – 10</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══ ÉTAPE 3 : SAISIE DU SCORE ═══ -->
    <div id="stepContent3" style="display:none">

        <!-- EN / EVN — Slider -->
        <div id="scoreEN_EVN" style="display:none">
            <div class="pain-value-display" id="sliderValue">0</div>
            <div class="pain-label-display" id="sliderLabel">Aucune douleur</div>
            <div class="pain-slider-wrap mt-3 mb-2">
                <input type="range" class="pain-slider" id="painSlider" min="0" max="10" step="1" value="0"
                       oninput="mettreAJourSlider(this.value)">
            </div>
            <div class="d-flex justify-content-between" style="font-size:.68rem;color:#94a3b8;font-weight:700">
                <span>0 — Absent</span><span>5 — Modéré</span><span>10 — Insupportable</span>
            </div>
        </div>

        <!-- EVS — Boutons verbaux -->
        <div id="scoreEVS" style="display:none">
            <div class="d-flex flex-column gap-2">
                <label class="evs-option" onclick="selectionnerEVS(0, this)">
                    <input type="radio" name="evs_val" value="0">
                    <span class="evs-dot" style="background:#22c55e"></span>
                    <div><strong>Absent</strong> <span class="text-muted">(0)</span>
                        <div class="small text-muted">Pas de douleur</div></div>
                </label>
                <label class="evs-option" onclick="selectionnerEVS(1, this)">
                    <input type="radio" name="evs_val" value="1">
                    <span class="evs-dot" style="background:#3b82f6"></span>
                    <div><strong>Faible</strong> <span class="text-muted">(1)</span>
                        <div class="small text-muted">Douleur légère, supportable sans traitement</div></div>
                </label>
                <label class="evs-option" onclick="selectionnerEVS(2, this)">
                    <input type="radio" name="evs_val" value="2">
                    <span class="evs-dot" style="background:#f59e0b"></span>
                    <div><strong>Modérée</strong> <span class="text-muted">(2)</span>
                        <div class="small text-muted">Douleur gênante, traitement antalgique envisageable</div></div>
                </label>
                <label class="evs-option" onclick="selectionnerEVS(3, this)">
                    <input type="radio" name="evs_val" value="3">
                    <span class="evs-dot" style="background:#ef4444"></span>
                    <div><strong>Intense</strong> <span class="text-muted">(3)</span>
                        <div class="small text-muted">Douleur forte, traitement urgent nécessaire</div></div>
                </label>
            </div>
        </div>

        <!-- ALGOPLUS — 5 items binaires -->
        <div id="scoreALGOPLUS" style="display:none">
            <p class="small text-muted mb-3">
                <i class="bi bi-info-circle me-1"></i>
                Cochez chaque item observé. Score ≥ 2 → prise en charge de la douleur recommandée.
            </p>
            <?php
            $algoItems = [
                'visage'     => ['Visage', 'Froncement des sourcils, grimaces, crispation, mâchoires serrées'],
                'regard'     => ['Regard', 'Regard fixe, hagard ou larmes'],
                'plainte'    => ['Plainte orale', 'Gémissements, cris, plainte verbale de douleur'],
                'position'   => ['Position corporelle', 'Attitude antalgique, recroquevillée ou crispée'],
                'comportement'=> ['Comportement inhabituel', 'Agitation, agressivité, prostration, refus de mobilisation'],
            ];
            foreach ($algoItems as $key => $item): ?>
            <div class="algo-item" id="algo-<?= $key ?>" onclick="toggleAlgo('<?= $key ?>', this)">
                <div class="algo-tick" id="algoTick-<?= $key ?>"></div>
                <div>
                    <div class="fw-bold" style="font-size:.85rem"><?= $item[0] ?></div>
                    <div class="small text-muted"><?= $item[1] ?></div>
                </div>
            </div>
            <?php endforeach; ?>
            <div class="text-center mt-3">
                <span class="fw-bold" style="font-size:1.1rem">Score ALGOPLUS : </span>
                <span id="algoScore" class="fw-bold" style="font-size:1.5rem;color:#1e40af">0</span>
                <span class="text-muted">/5</span>
            </div>
        </div>

        <!-- DOLOPLUS-2 — Saisie directe du total -->
        <div id="scoreDOLOPLUS2" style="display:none">
            <div class="alert alert-info rounded-3 small mb-3">
                <i class="bi bi-clipboard2-check me-1"></i>
                <strong>DOLOPLUS-2</strong> : 10 items répartis en 3 domaines (réactions somatiques, psychomotrices, psychosociales), chacun coté 0 à 3. Seuil de prise en charge : <strong>score ≥ 5</strong>.
            </div>
            <div class="pain-value-display" id="doloplusValue">0</div>
            <div class="pain-label-display" id="doloplusLabel">Aucune douleur</div>
            <div class="pain-slider-wrap mt-3 mb-2">
                <input type="range" class="pain-slider" id="doloplusSlider" min="0" max="30" step="1" value="0"
                       oninput="mettreAJourDoloplus(this.value)"
                       style="background:linear-gradient(to right,#22c55e,#eab308 17%,#ef4444 50%)">
            </div>
            <div class="d-flex justify-content-between" style="font-size:.68rem;color:#94a3b8;font-weight:700">
                <span>0</span><span>5 — Seuil</span><span>15</span><span>30 — Max</span>
            </div>
        </div>

        <!-- EVENDOL — 5 items 0-3 -->
        <div id="scoreEVENDOL" style="display:none">
            <p class="small text-muted mb-2">
                <i class="bi bi-info-circle me-1"></i>Cotez chaque item de 0 à 3. Seuil : ≥ 4.
            </p>
            <?php
            $evendolItems = [
                'e_pleure'   => 'Pleure / crie / gémit / soupire',
                'e_bouge'    => 'Bouge avec agitation / se débat',
                'e_antalgique'=> 'Position antalgique ou protège une zone',
                'e_regard'   => 'Regard expressif de douleur / angoisse',
                'e_expression'=> 'Expression faciale de douleur (grimaces)',
            ];
            foreach ($evendolItems as $key => $label): ?>
            <div class="flacc-row">
                <label><?= $label ?></label>
                <div class="flacc-options">
                    <?php for($v=0;$v<=3;$v++): ?>
                    <div class="flacc-opt" data-group="<?= $key ?>" data-val="<?= $v ?>"
                         onclick="selectionnerSubScore('evendol','<?= $key ?>',<?= $v ?>,this)">
                        <strong><?= $v ?></strong>
                    </div>
                    <?php endfor; ?>
                </div>
            </div>
            <?php endforeach; ?>
            <div class="text-center mt-2">
                <span class="fw-bold">Score EVENDOL : </span>
                <span id="evendolTotal" class="fw-bold" style="font-size:1.4rem;color:#1e40af">0</span>
                <span class="text-muted">/15</span>
            </div>
        </div>

        <!-- FLACC — 5 items 0-2 -->
        <div id="scoreFLACC" style="display:none">
            <p class="small text-muted mb-2">
                <i class="bi bi-info-circle me-1"></i>Cotez chaque item de 0 à 2. Seuil : ≥ 4.
            </p>
            <?php
            $flaccItems = [
                'f_face'    => ['Face (grimaces)', ['Détendu', 'Grimaces occasionnelles', 'Crispation constante']],
                'f_jambes'  => ['Jambes', ['Relâchées / normales', 'Agitées / tendues', 'Repliées / contractées']],
                'f_activite'=> ['Activité', ['Allongé calmement', 'Se tortille / tressaute', 'Arqué / rigide']],
                'f_cri'     => ['Cri', ['Pas de cri', 'Gémissements / plaintes', 'Cris continus / sanglots']],
                'f_console' => ['Consolabilité', ['Content / calme', 'Rassuré par le contact', 'Difficile à consoler']],
            ];
            foreach ($flaccItems as $key => $data): ?>
            <div class="flacc-row">
                <label><?= $data[0] ?></label>
                <div class="flacc-options">
                    <?php foreach($data[1] as $v => $desc): ?>
                    <div class="flacc-opt" data-group="<?= $key ?>" data-val="<?= $v ?>"
                         onclick="selectionnerSubScore('flacc','<?= $key ?>',<?= $v ?>,this)"
                         title="<?= $desc ?>">
                        <strong><?= $v ?></strong>
                        <div style="font-size:.6rem;opacity:.8;margin-top:2px;white-space:normal;line-height:1.2"><?= $desc ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endforeach; ?>
            <div class="text-center mt-2">
                <span class="fw-bold">Score FLACC : </span>
                <span id="flaccTotal" class="fw-bold" style="font-size:1.4rem;color:#1e40af">0</span>
                <span class="text-muted">/10</span>
            </div>
        </div>

        <!-- FPS-R — 6 visages -->
        <div id="scoreFPS_R" style="display:none">
            <p class="small text-muted mb-3 text-center">
                Demandez à l'enfant de montrer le visage qui correspond à sa douleur.
            </p>
            <div class="fps-faces">
                <?php
                $faces = [
                    [0,  '😊', 'Pas de douleur'],
                    [2,  '🙁', 'Un peu'],
                    [4,  '😟', 'Un peu plus'],
                    [6,  '😣', 'Encore plus'],
                    [8,  '😖', 'Beaucoup'],
                    [10, '😭', 'Maximum'],
                ];
                foreach ($faces as [$score, $emoji, $label]): ?>
                <div class="fps-face" onclick="selectionnerFPS(<?= $score ?>, this)" data-val="<?= $score ?>">
                    <span class="face-emoji"><?= $emoji ?></span>
                    <div class="face-score"><?= $score ?>/10</div>
                    <div style="font-size:.58rem;color:#64748b;margin-top:2px"><?= $label ?></div>
                </div>
                <?php endforeach; ?>
            </div>
            <div class="text-center mt-2">
                <span class="fw-bold">Score FPS-R : </span>
                <span id="fpsrScore" class="fw-bold" style="font-size:1.4rem;color:#1e40af">—</span>
                <span class="text-muted">/10</span>
            </div>
        </div>

        <!-- Champs complémentaires communs -->
        <div class="row g-2 mt-3 pt-3 border-top">
            <div class="col-md-6">
                <label class="form-label fw-semibold small">Localisation <span class="text-muted fw-normal">(optionnel)</span></label>
                <input type="text" id="douleurLocalisation" class="form-control form-control-sm rounded-3 border-0 bg-light"
                       placeholder="Ex: flanc droit, épaule gauche…">
            </div>
            <div class="col-md-6">
                <label class="form-label fw-semibold small">Caractéristiques <span class="text-muted fw-normal">(optionnel)</span></label>
                <input type="text" id="douleurCaract" class="form-control form-control-sm rounded-3 border-0 bg-light"
                       placeholder="Ex: brûlure, élancement, oppression…">
            </div>
            <div class="col-12">
                <label class="form-label fw-semibold small">Action prise / Antalgique administré <span class="text-muted fw-normal">(optionnel)</span></label>
                <input type="text" id="douleurAction" class="form-control form-control-sm rounded-3 border-0 bg-light"
                       placeholder="Ex: Paracétamol 1g IV, repositionnement, application de froid…">
            </div>
        </div>

    </div><!-- /stepContent3 -->

    </div><!-- /modal-body -->

    <div class="modal-footer border-0 px-4 pb-4 gap-2">
        <button type="button" class="btn btn-light rounded-pill" id="btnPrevStep"
                onclick="etapePrecedente()" style="display:none">
            <i class="bi bi-arrow-left me-1"></i>Précédent
        </button>
        <div class="ms-auto d-flex gap-2">
            <button type="button" class="btn btn-outline-secondary rounded-pill"
                    data-bs-dismiss="modal">Annuler</button>
            <button type="button" class="btn btn-primary rounded-pill px-4 fw-bold" id="btnNextStep"
                    onclick="etapeSuivante()">
                Suivant <i class="bi bi-arrow-right ms-1"></i>
            </button>
        </div>
    </div>

</div><!-- /modal-content -->
</div>
</div>
<!-- /MODAL DOULEUR -->

<div id="toast"></div>

<script>
const BASE = '<?= BASE_URL ?>';
let pendingAjax = 0;

/* ── Toggle section date ── */
function toggleDateGroup(id) {
    const el = document.getElementById(id);
    if (!el) return;
    el.classList.toggle('collapsed');
}

/* ════════════════════════════════════════════════════════════════
   MODULE ÉVALUATION DE LA DOULEUR
════════════════════════════════════════════════════════════════ */
const PATIENT_ID   = <?= (int)$patient_id_val ?>;
const ADMISSION_ID = <?= (int)($admission_id ?? 0) ?>;

// État courant de l'évaluation
let douleurEtat = {
    etape:    1,
    profil:   null,
    echelle:  null,
    scoreMax: null,
    score:    null,
    items:    {},
    evalId:   null,
};

// Libellés sévérité
const SEV_LABELS = {
    ABSENT:  '😌 Aucune douleur',
    LEGERE:  '🔵 Douleur légère',
    MODEREE: '🟡 Douleur modérée',
    INTENSE: '🔴 Douleur intense',
};
const SEV_COLORS = { ABSENT:'#22c55e', LEGERE:'#3b82f6', MODEREE:'#f59e0b', INTENSE:'#ef4444' };

// ── Ouvrir le modal ──
function ouvrirModalDouleur() {
    // Réinitialiser à l'étape 1 seulement si pas déjà en cours
    if (!douleurEtat.profil) resetModalDouleur();
    new bootstrap.Modal(document.getElementById('modalDouleur')).show();
}

function resetModalDouleur() {
    douleurEtat = { etape:1, profil:null, echelle:null, scoreMax:null, score:null, items:{}, evalId:null };
    allerEtape(1);
    document.querySelectorAll('.profil-card').forEach(c => c.classList.remove('selected'));
    document.querySelectorAll('.echelle-tab').forEach(t => t.classList.remove('active'));
    document.getElementById('douleurLocalisation').value = '';
    document.getElementById('douleurCaract').value = '';
    document.getElementById('douleurAction').value = '';
}

// ── Navigation entre étapes ──
function allerEtape(n) {
    [1,2,3].forEach(i => {
        document.getElementById('stepContent'+i).style.display = i===n ? '' : 'none';
        const dot = document.getElementById('stepDot'+i);
        dot.className = 'step-dot ' + (i < n ? 'done' : i===n ? 'active' : 'pending');
        if (i < 3) {
            const line = document.getElementById('stepLine'+i);
            line.className = 'step-line ' + (i < n ? 'done' : '');
        }
    });
    douleurEtat.etape = n;
    document.getElementById('btnPrevStep').style.display = n > 1 ? '' : 'none';

    const btnNext = document.getElementById('btnNextStep');
    if (n === 3) {
        btnNext.innerHTML = '<i class="bi bi-check2-circle me-1"></i>Enregistrer';
        btnNext.className = 'btn btn-success rounded-pill px-4 fw-bold';
    } else {
        btnNext.innerHTML = 'Suivant <i class="bi bi-arrow-right ms-1"></i>';
        btnNext.className = 'btn btn-primary rounded-pill px-4 fw-bold';
    }
}

function etapeSuivante() {
    if (douleurEtat.etape === 1) {
        if (!douleurEtat.profil) { showToast('Sélectionnez un profil patient.', false); return; }
        allerEtape(2);
    } else if (douleurEtat.etape === 2) {
        if (!douleurEtat.echelle) { showToast('Sélectionnez une échelle.', false); return; }
        afficherScoreUI(douleurEtat.echelle);
        allerEtape(3);
    } else if (douleurEtat.etape === 3) {
        enregistrerEvaluationDouleur();
    }
}

function etapePrecedente() {
    if (douleurEtat.etape > 1) allerEtape(douleurEtat.etape - 1);
}

// ── Sélection profil (étape 1) ──
function selectionnerProfil(profil, el) {
    document.querySelectorAll('.profil-card').forEach(c => c.classList.remove('selected'));
    el.classList.add('selected');
    douleurEtat.profil = profil;
    douleurEtat.echelle = null; douleurEtat.scoreMax = null;
}

// ── Sélection échelle (étape 2) ──
function selectionnerEchelle(echelle, scoreMax, el) {
    document.querySelectorAll('.echelle-tab').forEach(t => t.classList.remove('active'));
    el.classList.add('active');
    douleurEtat.echelle = echelle;
    douleurEtat.scoreMax = scoreMax;
    douleurEtat.score = null;
    douleurEtat.items = {};
}

// Afficher le panneau d'affichage des échelles selon profil (étape 2)
document.getElementById('stepContent2')?.addEventListener('click', () => {});
function afficherEchellesProfil() {
    ['ADULTE_COMM','ADULTE_NON_COMM'].forEach(p => {
        const el = document.getElementById('echelles'+p);
        if (el) el.style.display = douleurEtat.profil === p ? '' : 'none';
    });
    const ped = document.getElementById('échellesPEDIATRIQUE');
    if (ped) ped.style.display = douleurEtat.profil === 'PEDIATRIQUE' ? '' : 'none';
}

// Override allerEtape pour afficher les échelles au bon moment
const _allerEtapeOrig = allerEtape;
function allerEtape(n) {
    [1,2,3].forEach(i => {
        document.getElementById('stepContent'+i).style.display = i===n ? '' : 'none';
        const dot = document.getElementById('stepDot'+i);
        dot.className = 'step-dot ' + (i < n ? 'done' : i===n ? 'active' : 'pending');
        if (i < 3) document.getElementById('stepLine'+i).className = 'step-line '+(i<n?'done':'');
    });
    douleurEtat.etape = n;
    document.getElementById('btnPrevStep').style.display = n > 1 ? '' : 'none';
    const btnNext = document.getElementById('btnNextStep');
    if (n === 3) {
        btnNext.innerHTML = '<i class="bi bi-check2-circle me-1"></i>Enregistrer';
        btnNext.className = 'btn btn-success rounded-pill px-4 fw-bold';
    } else {
        btnNext.innerHTML = 'Suivant <i class="bi bi-arrow-right ms-1"></i>';
        btnNext.className = 'btn btn-primary rounded-pill px-4 fw-bold';
    }
    if (n === 2) afficherEchellesProfil();
}

// ── Score UI ──
function afficherScoreUI(echelle) {
    ['EN_EVN','EVS','ALGOPLUS','DOLOPLUS2','EVENDOL','FLACC','FPS_R'].forEach(id => {
        document.getElementById('score'+id).style.display = 'none';
    });
    const map = { EVN:'EN_EVN', EN:'EN_EVN', EVS:'EVS',
                  ALGOPLUS:'ALGOPLUS', DOLOPLUS2:'DOLOPLUS2',
                  EVENDOL:'EVENDOL', FLACC:'FLACC', FPS_R:'FPS_R' };
    const panelId = map[echelle];
    if (panelId) document.getElementById('score'+panelId).style.display = '';

    // Libellé description
    if (echelle === 'EVN' || echelle === 'EN') {
        document.getElementById('painSlider').value = 0;
        mettreAJourSlider(0);
    }
}

// Slider EVN/EN
const SEV_SLIDER = [
    {max:0,  label:'Aucune douleur',     color:'#22c55e'},
    {max:3,  label:'Douleur légère',     color:'#3b82f6'},
    {max:6,  label:'Douleur modérée',    color:'#f59e0b'},
    {max:10, label:'Douleur intense',    color:'#ef4444'},
];
function mettreAJourSlider(val) {
    val = parseInt(val);
    douleurEtat.score = val;
    const el = document.getElementById('sliderValue');
    const lbl = document.getElementById('sliderLabel');
    const sev = SEV_SLIDER.find(s => val <= s.max) || SEV_SLIDER[SEV_SLIDER.length-1];
    el.textContent = val;
    el.style.color = sev.color;
    lbl.textContent = sev.label;
    lbl.style.color = sev.color;
}

// DOLOPLUS slider
function mettreAJourDoloplus(val) {
    val = parseInt(val);
    douleurEtat.score = val;
    const el  = document.getElementById('doloplusValue');
    const lbl = document.getElementById('doloplusLabel');
    let color, label;
    if (val === 0)       { color='#22c55e'; label='Aucune douleur'; }
    else if (val < 5)    { color='#3b82f6'; label='Douleur légère (sous le seuil)'; }
    else if (val < 18)   { color='#f59e0b'; label='Douleur modérée'; }
    else                 { color='#ef4444'; label='Douleur intense'; }
    el.textContent = val; el.style.color = color;
    lbl.textContent = label; lbl.style.color = color;
}

// EVS
let evsVal = null;
function selectionnerEVS(val, el) {
    document.querySelectorAll('.evs-option').forEach(o => o.classList.remove('selected'));
    el.classList.add('selected');
    evsVal = val;
    douleurEtat.score = val;
}

// ALGOPLUS
const algoState = {};
function toggleAlgo(key, el) {
    algoState[key] = !algoState[key];
    el.classList.toggle('ticked', algoState[key]);
    const tick = document.getElementById('algoTick-'+key);
    tick.textContent = algoState[key] ? '✓' : '';
    const total = Object.values(algoState).filter(Boolean).length;
    document.getElementById('algoScore').textContent = total;
    document.getElementById('algoScore').style.color = total >= 2 ? '#ef4444' : '#1e40af';
    douleurEtat.score = total;
    douleurEtat.items = {...algoState};
}

// Sub-scores EVENDOL / FLACC
const subScores = {};
function selectionnerSubScore(echelle, group, val, el) {
    document.querySelectorAll(`.flacc-opt[data-group="${group}"]`).forEach(o => o.classList.remove('picked'));
    el.classList.add('picked');
    subScores[group] = val;
    const prefix = echelle === 'flacc' ? 'flacc' : 'evendol';
    const total = Object.values(subScores).reduce((s,v) => s+v, 0);
    document.getElementById(prefix+'Total').textContent = total;
    douleurEtat.score = total;
    douleurEtat.items = {...subScores};
}

// FPS-R
function selectionnerFPS(val, el) {
    document.querySelectorAll('.fps-face').forEach(f => f.classList.remove('picked'));
    el.classList.add('picked');
    douleurEtat.score = val;
    document.getElementById('fpsrScore').textContent = val;
}

// ── Calcul sévérité côté client ──
function calculerSeverite(echelle, score, scoreMax) {
    if (score === 0 || score === null) return 'ABSENT';
    const ratio = scoreMax > 0 ? score / scoreMax : 0;
    // Seuils spéciaux
    if (echelle === 'ALGOPLUS')  return score <= 1 ? 'LEGERE' : score <= 3 ? 'MODEREE' : 'INTENSE';
    if (echelle === 'DOLOPLUS2') return score < 5  ? 'LEGERE' : score < 18 ? 'MODEREE' : 'INTENSE';
    if (echelle === 'EVS')       return score === 1 ? 'LEGERE' : score === 2 ? 'MODEREE' : 'INTENSE';
    // Générique ratio
    if (ratio <= 0)   return 'ABSENT';
    if (ratio <= 0.3) return 'LEGERE';
    if (ratio <= 0.6) return 'MODEREE';
    return 'INTENSE';
}

// ── Enregistrer via AJAX ──
function enregistrerEvaluationDouleur() {
    // Validation score
    if (douleurEtat.score === null || douleurEtat.score === undefined) {
        showToast('Veuillez saisir ou sélectionner un score.', false);
        return;
    }

    const btn = document.getElementById('btnNextStep');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Enregistrement…';

    const payload = {
        patient_id:       PATIENT_ID,
        admission_id:     ADMISSION_ID || null,
        profil:           douleurEtat.profil,
        echelle:          douleurEtat.echelle,
        score:            douleurEtat.score,
        score_max:        douleurEtat.scoreMax,
        items:            douleurEtat.items,
        localisation:     document.getElementById('douleurLocalisation').value.trim(),
        caracteristiques: document.getElementById('douleurCaract').value.trim(),
        action_prise:     document.getElementById('douleurAction').value.trim(),
    };

    fetch(BASE + 'hospitalisation/save-douleur', {
        method:'POST',
        headers:{'Content-Type':'application/json'},
        body: JSON.stringify(payload),
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            douleurEtat.evalId = res.eval_id;
            document.getElementById('douleurEvalId').value = res.eval_id;
            const sev = res.severite || calculerSeverite(douleurEtat.echelle, douleurEtat.score, douleurEtat.scoreMax);
            afficherBanniereDouleurFaite(res.echelle, res.score, res.score_max, sev);
            bootstrap.Modal.getInstance(document.getElementById('modalDouleur')).hide();
            debloquerBoutonValider();
            showToast('✓ Évaluation douleur enregistrée');
        } else {
            showToast('Erreur : ' + (res.message || 'inconnue'), false);
        }
    })
    .catch(() => showToast('Erreur réseau lors de l\'enregistrement.', false))
    .finally(() => {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-check2-circle me-1"></i>Enregistrer';
    });
}

// ── Mise à jour de la bannière ──
function afficherBanniereDouleurFaite(echelle, score, scoreMax, severite) {
    document.getElementById('douleurRequired').style.display = 'none';
    document.getElementById('douleurDone').style.display     = '';
    const pct   = scoreMax > 0 ? Math.round(score / scoreMax * 100) : 0;
    const color = SEV_COLORS[severite] || '#3b82f6';
    document.getElementById('douleurDoneTitle').textContent =
        `${echelle} : ${score}/${scoreMax} — ${SEV_LABELS[severite] || severite}`;
    document.getElementById('douleurSevFill').style.width      = pct + '%';
    document.getElementById('douleurSevFill').style.background = color;
    document.getElementById('douleurSevLabel').textContent     = SEV_LABELS[severite] || severite;
    document.getElementById('douleurSevLabel').style.color     = color;
}

// ── Débloquer le bouton Valider ──
function debloquerBoutonValider() {
    const btn = document.getElementById('btnValider');
    btn.style.background = '';
    btn.style.cursor     = '';
    btn.title            = '';
    btn.innerHTML        = '<i class="bi bi-check2-all me-2"></i>Valider et enregistrer';
    btn._evalDone        = true;
}

// ── Soumission du formulaire (avec garde) ──
function soumettreFormulaire() {
    const btn = document.getElementById('btnValider');
    if (!btn._evalDone) {
        // Pas encore évalué → ouvrir le modal
        ouvrirModalDouleur();
        showToast('⚠️ Évaluez d\'abord la douleur du patient.', false);
        return;
    }
    document.getElementById('formExecution').submit();
}

// ── Afficher toast ──
function showToast(msg, ok=true) {
    const t = document.getElementById('toast');
    t.textContent = msg;
    t.style.background = ok ? '#16a34a' : '#dc2626';
    t.classList.add('show');
    setTimeout(() => t.classList.remove('show'), 2500);
}

// ── Mettre à jour la barre de progression ──
function updateProgress() {
    const total  = document.querySelectorAll('.soin-check:not(:disabled)').length;
    const coches = document.querySelectorAll('.soin-check:checked:not(:disabled)').length;
    const pct    = total > 0 ? Math.round(coches / total * 100) : 0;
    document.getElementById('globalBar').style.width = pct + '%';
    document.getElementById('footerRealises').textContent = coches;
}

// ── Cocher/décocher un soin (AJAX auto-save) ──
function cocherSoin(soinId, cocher) {
    const row = document.getElementById('row-' + soinId);
    row.style.opacity = '.5';

    fetch(BASE + 'hospitalisation/cocher-soin', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({ soin_id: soinId, cocher: cocher })
    })
    .then(r => r.json())
    .then(res => {
        row.style.opacity = '1';
        if (res.success) {
            row.classList.toggle('realise', cocher);
            if (cocher) {
                row.querySelector('.soin-meta').innerHTML =
                    '<i class="bi bi-person-check-fill text-success"></i> Réalisé maintenant';
            }
            updateProgress();
            showToast(cocher ? '✓ Soin enregistré' : 'Soin remis en attente');
        } else {
            // Annuler le changement visuel
            document.getElementById('chk-' + soinId).checked = !cocher;
            showToast('Erreur : ' + (res.message || 'inconnue'), false);
        }
    })
    .catch(() => {
        row.style.opacity = '1';
        document.getElementById('chk-' + soinId).checked = !cocher;
        showToast('Erreur réseau', false);
    });
}

// ── Ouvrir/fermer le panel de rayure ──
function ouvrirRayer(id) {
    document.querySelectorAll('.rayer-panel.open').forEach(p => p.classList.remove('open'));
    document.querySelectorAll('.stopper-panel.open').forEach(p => p.classList.remove('open'));
    document.getElementById('panel-rayer-' + id).classList.add('open');
}

// ── Stopper un médicament (toutes les prises du jour) ──
function ouvrirStopper(id, description) {
    document.querySelectorAll('.rayer-panel.open').forEach(p => p.classList.remove('open'));
    document.querySelectorAll('.stopper-panel.open').forEach(p => p.classList.remove('open'));
    document.querySelectorAll('.comment-panel').forEach(p => p.style.display = 'none');
    document.getElementById('panel-stopper-' + id).classList.add('open');
}

function fermerStopper(id) {
    document.getElementById('panel-stopper-' + id).classList.remove('open');
}

function confirmerStopper(soinId) {
    const motif = document.getElementById('motif-stopper-' + soinId).value.trim();
    if (!motif) { alert('Veuillez indiquer le motif de l\'arrêt.'); return; }

    fetch(BASE + 'hospitalisation/stopper-soin', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({ soin_id: soinId, motif: motif })
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            fermerStopper(soinId);
            showToast('Médicament stoppé — ' + res.nb_stoppes + ' prise(s) annulée(s)');
            setTimeout(() => location.reload(), 1200);
        } else {
            showToast('Erreur : ' + (res.message || 'inconnue'), false);
        }
    })
    .catch(() => showToast('Erreur réseau', false));
}
// ── Commentaire infirmier ────────────────────────────────────────────────────
function ouvrirCommentaire(id, existant) {
    // Fermer les autres panels ouverts
    document.querySelectorAll('.comment-panel').forEach(p => p.style.display = 'none');
    document.querySelectorAll('.rayer-panel.open').forEach(p => p.classList.remove('open'));
    document.querySelectorAll('.stopper-panel.open').forEach(p => p.classList.remove('open'));
    const panel = document.getElementById('panel-comment-' + id);
    if (!panel) return;
    const ta = document.getElementById('comment-text-' + id);
    if (ta && existant !== undefined) ta.value = existant || '';
    panel.style.display = 'block';
    if (ta) ta.focus();
}

function fermerCommentaire(id) {
    const panel = document.getElementById('panel-comment-' + id);
    if (panel) panel.style.display = 'none';
}

function sauvegarderCommentaire(id) {
    const ta   = document.getElementById('comment-text-' + id);
    const text = ta ? ta.value.trim() : '';

    fetch(BASE + 'hospitalisation/commenter-soin', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({ soin_id: id, commentaire: text })
    })
    .then(r => r.json())
    .then(res => {
        if (!res.success) { alert(res.message || 'Erreur lors de l\'enregistrement.'); return; }

        // Mettre à jour l'affichage inline
        const display = document.getElementById('comment-display-' + id);
        const btn     = document.querySelector('[onclick*="ouvrirCommentaire(' + id + ',"]') ||
                        document.querySelector('[onclick^="ouvrirCommentaire(' + id + '"]');

        if (text) {
            if (display) {
                display.style.display = 'flex';
                display.querySelector('span').textContent = text;
            }
            // Mettre le bouton en mode "a un commentaire"
            if (btn) {
                btn.classList.add('has-comment');
                btn.title = 'Modifier le commentaire';
                btn.innerHTML = '<i class="bi bi-chat-text-fill"></i>';
            }
        } else {
            if (display) display.style.display = 'none';
            if (btn) {
                btn.classList.remove('has-comment');
                btn.title = 'Ajouter un commentaire';
                btn.innerHTML = '<i class="bi bi-chat-text"></i>';
            }
        }
        fermerCommentaire(id);
    })
    .catch(() => alert('Erreur réseau.'));
}
// ─────────────────────────────────────────────────────────────────────────────

function fermerRayer(id) {
    document.getElementById('panel-rayer-' + id).classList.remove('open');
}

// ── Confirmer la rayure ──
function confirmerRayer(soinId) {
    const motif  = document.getElementById('motif-' + soinId).value.trim();
    const corr   = document.getElementById('corr-' + soinId).value.trim();
    if (!motif) { alert('Veuillez indiquer le motif de la rayure.'); return; }

    fetch(BASE + 'hospitalisation/rayer-soin', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({ soin_id: soinId, motif: motif, correction: corr })
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            // Barrer la ligne originale
            const row = document.getElementById('row-' + soinId);
            row.classList.add('annule');
            row.querySelector('.soin-desc').style.textDecoration = 'line-through';
            row.querySelector('.soin-check').disabled = true;
            row.querySelector('.soin-check').checked  = false;
            row.querySelector('.btn-rayer')?.remove();
            row.querySelector('.soin-meta').innerHTML =
                '<i class="bi bi-slash-circle text-danger"></i> Rayé — ' + motif;

            fermerRayer(soinId);
            updateProgress();
            showToast('Ligne rayée' + (corr ? ' + correction ajoutée' : ''));

            // Recharger après 1,5s pour afficher la ligne corrigée
            if (res.new_id) setTimeout(() => location.reload(), 1500);
        } else {
            showToast('Erreur : ' + (res.message || 'inconnue'), false);
        }
    })
    .catch(() => showToast('Erreur réseau', false));
}

// ════════════════════════════════════════════════════════════════
//  MODIFICATION D'UN SOIN PLANIFIÉ
// ════════════════════════════════════════════════════════════════
let modifSoinId = null;

function ouvrirModifSoin(soin) {
    modifSoinId = soin.id;
    document.getElementById('modifSoinId').value = soin.id;
    document.getElementById('modifSoinType').value = soin.type_soin || '';
    document.getElementById('modifSoinDesc').value = soin.description || '';
    document.getElementById('modifSoinHeure').value = soin.heure && soin.heure !== '--:--' ? soin.heure : '';
    document.getElementById('modifSoinCondition').value = soin.condition || '';
    document.getElementById('modifSoinError').style.display = 'none';
    new bootstrap.Modal(document.getElementById('modalModifSoin')).show();
}

function confirmerModifSoin() {
    if (!modifSoinId) return;
    const desc      = document.getElementById('modifSoinDesc').value.trim();
    const heure     = document.getElementById('modifSoinHeure').value.trim();
    const condition = document.getElementById('modifSoinCondition').value.trim();
    const type      = document.getElementById('modifSoinType').value.trim();

    const errBox = document.getElementById('modifSoinError');
    if (!desc) {
        errBox.textContent = 'La description est obligatoire.';
        errBox.style.display = 'block';
        return;
    }
    if (!heure || !/^([01]\d|2[0-3]):[0-5]\d$/.test(heure)) {
        errBox.textContent = 'L\'heure est invalide (format HH:MM).';
        errBox.style.display = 'block';
        return;
    }

    const fd = new FormData();
    fd.append('description', desc);
    fd.append('heure', heure);
    fd.append('condition_application', condition);
    fd.append('type_soin', type);

    const btn = document.getElementById('btnConfirmerModifSoin');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Enregistrement…';

    fetch(BASE + 'hospitalisation/modifier-soin/' + modifSoinId, {
        method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            // Mettre à jour la ligne sans recharger toute la page
            const row = document.getElementById('row-' + modifSoinId);
            if (row) {
                row.querySelector('.heure-badge').textContent = heure;
                row.querySelector('.soin-desc').textContent = desc;
                const condEl = row.querySelector('.soin-cond');
                if (condition) {
                    if (condEl) condEl.textContent = '⚡ ' + condition;
                    else {
                        const span = document.createElement('span');
                        span.className = 'soin-cond';
                        span.textContent = '⚡ ' + condition;
                        row.querySelector('.soin-content').appendChild(span);
                    }
                } else if (condEl) condEl.remove();
            }
            bootstrap.Modal.getInstance(document.getElementById('modalModifSoin'))?.hide();
            showToast('✅ Soin modifié.');
        } else {
            errBox.textContent = res.message || 'Erreur lors de la modification';
            errBox.style.display = 'block';
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-check-lg me-1"></i> Enregistrer';
        }
    })
    .catch(() => {
        errBox.textContent = 'Erreur réseau';
        errBox.style.display = 'block';
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-check-lg me-1"></i> Enregistrer';
    });
}

// Ouvre le modal de motif de suppression
let _supprimerSoinId = null;
function ouvrirModalSuppression(id, desc) {
    _supprimerSoinId = id;
    document.getElementById('supprimerSoinDesc').textContent = desc || '(sans description)';
    document.getElementById('motifSuppressionInput').value = '';
    document.getElementById('errMotifSuppression').style.display = 'none';
    new bootstrap.Modal(document.getElementById('modalMotifSuppression')).show();
}

function confirmerSuppressionSoin() {
    const motif = document.getElementById('motifSuppressionInput').value.trim();
    if (!motif) {
        document.getElementById('errMotifSuppression').style.display = 'block';
        return;
    }
    const btn = document.getElementById('btnConfirmerSuppression');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Suppression…';

    fetch(BASE + 'hospitalisation/supprimer-soin/' + _supprimerSoinId, {
        method : 'POST',
        headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        body   : JSON.stringify({ motif: motif })
    })
    .then(r => r.json())
    .then(res => {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-trash3 me-1"></i> Confirmer la suppression';
        if (res.success) {
            bootstrap.Modal.getInstance(document.getElementById('modalMotifSuppression'))?.hide();
            // Griser la ligne sans la retirer
            const row = document.getElementById('row-' + _supprimerSoinId);
            if (row) {
                row.style.opacity = '0.52';
                row.style.background = '#f8f9fa';
                row.style.pointerEvents = 'none';
                // Mettre à jour le meta-texte
                const meta = row.querySelector('.soin-meta');
                if (meta) meta.innerHTML = '<i class="bi bi-trash3-fill text-danger"></i> Supprimé — <em>' + motif + '</em>';
                // Remplacer les boutons droite par badge SUPPRIMÉ
                const badgesDiv = row.querySelector('.d-flex.align-items-center.gap-2.flex-shrink-0');
                if (badgesDiv) badgesDiv.innerHTML = '<span style="font-size:.68rem;background:#dc3545;color:#fff;padding:2px 8px;border-radius:20px;font-weight:700;"><i class="bi bi-trash3-fill me-1"></i>SUPPRIMÉ</span>';
            }
            showToast('✅ Soin marqué comme supprimé');
        } else {
            showToast('Erreur : ' + (res.message || 'inconnue'), false);
        }
    })
    .catch(() => {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-trash3 me-1"></i> Confirmer la suppression';
        showToast('Erreur réseau', false);
    });
}
</script>

<!-- ═══════════════════════════════════════════════════════════════
     MODAL — MODIFIER UN SOIN PLANIFIÉ
═══════════════════════════════════════════════════════════════ -->
<div class="modal fade" id="modalModifSoin" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width:540px;">
        <div class="modal-content border-0 shadow-lg" style="border-radius:16px;overflow:hidden;">
            <div class="modal-header border-0 p-4"
                 style="background:linear-gradient(135deg,#92400e,#d97706);">
                <h5 class="modal-title fw-bold text-white mb-0">
                    <i class="bi bi-pencil-fill me-2"></i>Modifier le soin planifié
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <input type="hidden" id="modifSoinId">

                <div class="mb-3">
                    <label class="form-label fw-semibold small">Catégorie / Type de soin</label>
                    <input type="text" id="modifSoinType" class="form-control"
                           placeholder="Ex: Per Os, IV, Surveillance...">
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold small">Description <span class="text-danger">*</span></label>
                    <input type="text" id="modifSoinDesc" class="form-control"
                           placeholder="Ex : Paracétamol 1g, prise tension, pansement…">
                </div>

                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label fw-semibold small">Heure <span class="text-danger">*</span></label>
                        <input type="time" id="modifSoinHeure" class="form-control">
                    </div>
                    <div class="col-md-8">
                        <label class="form-label fw-semibold small">Condition (optionnel)</label>
                        <input type="text" id="modifSoinCondition" class="form-control"
                               placeholder="Ex : si T° > 38°C, après les repas…">
                    </div>
                </div>

                <div id="modifSoinError" class="alert alert-danger mt-3 mb-0"
                     style="display:none;font-size:.85rem;border:none;border-left:3px solid #dc2626;border-radius:6px;"></div>
            </div>
            <div class="modal-footer border-0 px-4 pb-4 pt-0">
                <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Annuler</button>
                <button type="button" id="btnConfirmerModifSoin"
                        class="btn rounded-pill px-4 fw-semibold text-white"
                        style="background:linear-gradient(135deg,#92400e,#d97706);border:none;"
                        onclick="confirmerModifSoin()">
                    <i class="bi bi-check-lg me-1"></i> Enregistrer
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════════════
     MODAL — MOTIF DE SUPPRESSION D'UN SOIN
═══════════════════════════════════════════════════════════════ -->
<div class="modal fade" id="modalMotifSuppression" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width:480px;">
        <div class="modal-content border-0 shadow-lg" style="border-radius:16px;overflow:hidden;">
            <div class="modal-header border-0 p-4" style="background:linear-gradient(135deg,#7f1d1d,#dc2626);">
                <h5 class="modal-title fw-bold text-white mb-0">
                    <i class="bi bi-trash3-fill me-2"></i>Supprimer ce soin
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <!-- Rappel du soin -->
                <div class="rounded-3 p-3 mb-4" style="background:#fff5f5;border:1px solid #fecaca;">
                    <div class="small text-muted mb-1 fw-semibold text-uppercase" style="font-size:.7rem;letter-spacing:.6px;">Soin concerné</div>
                    <div class="fw-bold text-danger" id="supprimerSoinDesc">—</div>
                </div>

                <!-- Motif obligatoire -->
                <label class="form-label fw-semibold small mb-1">
                    Motif de suppression <span class="text-danger">*</span>
                </label>
                <textarea id="motifSuppressionInput"
                          class="form-control rounded-3"
                          rows="3"
                          placeholder="Ex : Erreur de saisie, soin non nécessaire, doublon…"
                          style="resize:vertical;font-size:.92rem;"
                          oninput="document.getElementById('errMotifSuppression').style.display='none'"></textarea>
                <div id="errMotifSuppression" class="text-danger small mt-1" style="display:none;">
                    <i class="bi bi-exclamation-circle me-1"></i>Le motif est obligatoire.
                </div>

                <!-- Avertissement -->
                <div class="alert alert-warning border-0 rounded-3 py-2 px-3 small d-flex gap-2 align-items-start mt-3 mb-0">
                    <i class="bi bi-info-circle-fill text-warning flex-shrink-0 mt-1"></i>
                    <span>Le soin ne sera <strong>pas supprimé</strong> de la base de données mais sera <strong>grisé</strong> avec votre nom, la date et le motif indiqué.</span>
                </div>
            </div>
            <div class="modal-footer border-0 px-4 pb-4 pt-0 gap-2">
                <button class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">Annuler</button>
                <button id="btnConfirmerSuppression"
                        class="btn text-white fw-bold rounded-pill px-4"
                        style="background:#dc2626;"
                        onclick="confirmerSuppressionSoin()">
                    <i class="bi bi-trash3 me-1"></i> Confirmer la suppression
                </button>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
