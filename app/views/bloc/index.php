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
require_once __DIR__ . '/../../controllers/BlocController.php';
require_once __DIR__ . '/../../services/CsrfService.php';
$salles        = $salles        ?? [];
$interventions = $interventions ?? [];
$file_attente  = $file_attente  ?? [];
$kpi           = $kpi           ?? ['salles_libres'=>0,'salles_total'=>0,'prog_jour'=>0,'en_cours'=>0,'en_sspi'=>0,'termine_jour'=>0,'attente'=>0];
$userRole      = $_SESSION['user_role'] ?? '';
$userNom       = trim(($_SESSION['user_prenom'] ?? '') . ' ' . ($_SESSION['user_nom'] ?? ''));
$isMedecin     = in_array($userRole, ['MEDECIN','ADMIN']);
?>
<style>
/* ── RESET LAYOUT ── */
.sidebar, nav.sidebar { display:none !important; }
main, .col-md-10, .ms-sm-auto { margin-left:0 !important; width:100% !important; flex:0 0 100% !important; max-width:100% !important; padding:0 !important; }
body { background:#0f172a; font-family:'Segoe UI',system-ui,sans-serif; color:#1e293b; }

/* ── TOPBAR ── */
.bloc-topbar {
    background:linear-gradient(135deg,#1e1b4b 0%,#312e81 40%,#4c1d95 100%);
    padding:0 28px;
    height:68px;
    display:flex; align-items:center; justify-content:space-between;
    position:sticky; top:0; z-index:200;
    box-shadow:0 4px 24px rgba(0,0,0,.4);
    border-bottom:1px solid rgba(255,255,255,.08);
}
.bloc-brand { display:flex; align-items:center; gap:14px; }
.bloc-brand-icon {
    width:44px; height:44px; border-radius:14px;
    background:linear-gradient(135deg,#6366f1,#a855f7);
    display:flex; align-items:center; justify-content:center;
    font-size:1.3rem; color:#fff;
    box-shadow:0 4px 14px rgba(99,102,241,.5);
}
.bloc-brand-text .title { font-size:1.1rem; font-weight:900; color:#fff; line-height:1.1; }
.bloc-brand-text .sub   { font-size:.68rem; color:rgba(255,255,255,.55); font-weight:600; text-transform:uppercase; letter-spacing:1px; }
.bloc-clock {
    font-size:1.5rem; font-weight:900; color:#fff; letter-spacing:2px;
    background:rgba(255,255,255,.08); border:1px solid rgba(255,255,255,.15);
    padding:6px 20px; border-radius:12px; font-variant-numeric:tabular-nums;
}
.bloc-actions { display:flex; align-items:center; gap:8px; }
.tb-btn {
    display:inline-flex; align-items:center; gap:6px;
    padding:8px 14px; border-radius:10px; font-weight:700; font-size:.78rem;
    text-decoration:none; border:1px solid rgba(255,255,255,.2);
    background:rgba(255,255,255,.1); color:#fff; cursor:pointer; transition:all .15s;
}
.tb-btn:hover { background:rgba(255,255,255,.2); color:#fff; }
.tb-btn.primary { background:linear-gradient(135deg,#6366f1,#8b5cf6); border-color:transparent; box-shadow:0 4px 14px rgba(99,102,241,.4); }
.tb-btn.primary:hover { opacity:.9; }
.tb-btn.danger  { background:rgba(239,68,68,.25); border-color:rgba(239,68,68,.4); }
.tb-btn.danger:hover { background:rgba(239,68,68,.45); }

/* ── MAIN ── */
.bloc-body { background:#f0f4f8; min-height:calc(100vh - 68px); padding:24px 28px; }

/* ── FLASH ── */
.bloc-flash { background:#dcfce7; border:1px solid #86efac; border-radius:14px; padding:12px 20px; margin-bottom:20px; color:#166534; font-weight:700; font-size:.88rem; display:flex; align-items:center; gap:10px; }

/* ── KPI ── */
.kpi-grid { display:grid; grid-template-columns:repeat(6,1fr); gap:14px; margin-bottom:24px; }
@media(max-width:1100px){ .kpi-grid{ grid-template-columns:repeat(3,1fr);} }
.kpi-card {
    background:#fff; border-radius:18px; padding:18px 20px;
    border:1px solid #e8edf3; box-shadow:0 2px 12px rgba(15,23,42,.06);
    position:relative; overflow:hidden; transition:transform .15s,box-shadow .15s;
}
.kpi-card:hover { transform:translateY(-2px); box-shadow:0 8px 24px rgba(15,23,42,.1); }
.kpi-card::before { content:''; position:absolute; top:0; left:0; right:0; height:3px; }
.kpi-card.k-green::before  { background:linear-gradient(90deg,#10b981,#34d399); }
.kpi-card.k-amber::before  { background:linear-gradient(90deg,#f59e0b,#fbbf24); }
.kpi-card.k-blue::before   { background:linear-gradient(90deg,#3b82f6,#60a5fa); }
.kpi-card.k-red::before    { background:linear-gradient(90deg,#ef4444,#f87171); }
.kpi-card.k-violet::before { background:linear-gradient(90deg,#8b5cf6,#a78bfa); }
.kpi-card.k-slate::before  { background:linear-gradient(90deg,#475569,#94a3b8); }
.kpi-icon { font-size:1.6rem; opacity:.12; position:absolute; right:14px; top:50%; transform:translateY(-50%); }
.kpi-value { font-size:2.2rem; font-weight:900; line-height:1; }
.kpi-card.k-green  .kpi-value { color:#059669; }
.kpi-card.k-amber  .kpi-value { color:#d97706; }
.kpi-card.k-blue   .kpi-value { color:#2563eb; }
.kpi-card.k-red    .kpi-value { color:#dc2626; }
.kpi-card.k-violet .kpi-value { color:#7c3aed; }
.kpi-card.k-slate  .kpi-value { color:#475569; }
.kpi-label { font-size:.65rem; color:#94a3b8; font-weight:800; text-transform:uppercase; letter-spacing:.6px; margin-top:5px; }

/* ── LAYOUT GRID ── */
.bloc-grid { display:grid; grid-template-columns:320px 1fr; gap:20px; align-items:start; }
@media(max-width:1000px){ .bloc-grid{ grid-template-columns:1fr; } }

/* ── PANELS ── */
.panel { background:#fff; border-radius:20px; overflow:hidden; box-shadow:0 2px 16px rgba(15,23,42,.07); border:1px solid #e8edf3; }
.panel-head {
    padding:16px 22px; display:flex; align-items:center; justify-content:space-between;
    border-bottom:1px solid #f1f5f9;
}
.panel-head-title { font-weight:800; font-size:.88rem; display:flex; align-items:center; gap:8px; }
.panel-body { padding:16px; }

/* ── SALLES ── */
.salle-card {
    border-radius:14px; padding:14px 16px; margin-bottom:10px;
    display:flex; align-items:center; gap:14px; position:relative;
    border:1.5px solid transparent; transition:all .15s; cursor:default;
}
.salle-card.disponible  { background:linear-gradient(135deg,#f0fdf4,#dcfce7); border-color:#86efac; }
.salle-card.occupee     { background:linear-gradient(135deg,#fef2f2,#fee2e2); border-color:#fca5a5; }
.salle-card.nettoyage   { background:linear-gradient(135deg,#fffbeb,#fef3c7); border-color:#fde68a; }
.salle-card.maintenance { background:linear-gradient(135deg,#f8fafc,#f1f5f9); border-color:#cbd5e1; }
.salle-dot { width:10px; height:10px; border-radius:50%; flex-shrink:0; }
.disponible  .salle-dot { background:#10b981; box-shadow:0 0 0 3px rgba(16,185,129,.25); animation:pulse-g 2s infinite; }
.occupee     .salle-dot { background:#ef4444; box-shadow:0 0 0 3px rgba(239,68,68,.25); animation:pulse-r 1.5s infinite; }
.nettoyage   .salle-dot { background:#f59e0b; }
.maintenance .salle-dot { background:#94a3b8; }
@keyframes pulse-g { 0%,100%{box-shadow:0 0 0 3px rgba(16,185,129,.25)} 50%{box-shadow:0 0 0 6px rgba(16,185,129,.1)} }
@keyframes pulse-r { 0%,100%{box-shadow:0 0 0 3px rgba(239,68,68,.25)} 50%{box-shadow:0 0 0 6px rgba(239,68,68,.1)} }
.salle-info { flex:1; min-width:0; }
.salle-nom  { font-weight:800; font-size:.88rem; color:#0f172a; }
.salle-type { font-size:.72rem; color:#64748b; margin-top:1px; }
.salle-status-badge { font-size:.65rem; font-weight:800; padding:3px 10px; border-radius:20px; text-transform:uppercase; }
.disponible  .salle-status-badge { background:#dcfce7; color:#166534; }
.occupee     .salle-status-badge { background:#fee2e2; color:#991b1b; }
.nettoyage   .salle-status-badge { background:#fef3c7; color:#92400e; }
.maintenance .salle-status-badge { background:#f1f5f9; color:#475569; }
.salle-gear-btn {
    width:30px; height:30px; border-radius:9px; border:1px solid rgba(0,0,0,.08);
    background:rgba(255,255,255,.7); color:#64748b; display:flex; align-items:center;
    justify-content:center; cursor:pointer; font-size:.85rem; transition:all .15s; flex-shrink:0;
}
.salle-gear-btn:hover { background:#fff; color:#312e81; border-color:#312e81; }

/* ── PROGRAMME TABLE ── */
.prog-table { width:100%; border-collapse:collapse; }
.prog-table th { background:#f8fafc; color:#64748b; font-size:.68rem; font-weight:800; text-transform:uppercase; letter-spacing:.5px; padding:11px 16px; border-bottom:2px solid #f1f5f9; text-align:left; }
.prog-table td { padding:13px 16px; border-bottom:1px solid #f8fafc; vertical-align:middle; font-size:.84rem; }
.prog-table tr:last-child td { border-bottom:none; }
.prog-table tr:hover td { background:#fafbfc; }
.prog-table .empty-row td { text-align:center; padding:36px; color:#cbd5e1; }

.st-pill { font-size:.68rem; font-weight:800; padding:4px 12px; border-radius:20px; display:inline-flex; align-items:center; gap:5px; }
.act-link {
    display:inline-flex; align-items:center; gap:5px; font-size:.76rem; font-weight:800;
    padding:6px 14px; border-radius:20px; text-decoration:none; border:none; cursor:pointer;
    transition:all .15s;
}
.act-demarrer  { background:#dc2626; color:#fff; box-shadow:0 3px 10px rgba(220,38,38,.35); }
.act-demarrer:hover  { background:#b91c1c; color:#fff; transform:scale(1.04); }
.act-cockpit   { background:#fef2f2; color:#dc2626; border:1.5px solid #fca5a5; }
.act-cockpit:hover   { background:#fee2e2; color:#dc2626; }
.act-reveil    { background:#fffbeb; color:#b45309; border:1.5px solid #fde68a; }
.act-reveil:hover    { background:#fef3c7; }
.act-voir      { background:#f8fafc; color:#475569; border:1.5px solid #e2e8f0; }
.act-voir:hover      { background:#f1f5f9; }
.act-modifier  { background:#eff6ff; color:#1d4ed8; border:1.5px solid #bfdbfe; }
.act-modifier:hover  { background:#dbeafe; color:#1e40af; }
.act-annuler   { background:#fef2f2; color:#dc2626; border:1.5px solid #fecaca; }
.act-annuler:hover   { background:#fee2e2; color:#b91c1c; }
.act-prog      { background:linear-gradient(135deg,#4f46e5,#7c3aed); color:#fff; box-shadow:0 3px 10px rgba(79,70,229,.3); }
.act-prog:hover      { opacity:.9; color:#fff; transform:scale(1.04); }

/* ── FILE ATTENTE ── */
.attente-card {
    display:flex; align-items:center; gap:14px; padding:14px 16px;
    border-radius:14px; border:1.5px solid #fde68a; background:linear-gradient(135deg,#fffbeb,#fef9ee);
    margin-bottom:10px; transition:all .15s;
}
.attente-card:hover { border-color:#f59e0b; background:#fef3c7; }
.attente-avatar {
    width:40px; height:40px; border-radius:12px; flex-shrink:0;
    background:linear-gradient(135deg,#f59e0b,#fbbf24);
    display:flex; align-items:center; justify-content:center;
    font-weight:900; color:#fff; font-size:.85rem;
}
.attente-info { flex:1; min-width:0; }
.attente-nom  { font-weight:800; font-size:.88rem; color:#0f172a; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.attente-meta { font-size:.73rem; color:#92400e; margin-top:2px; }

/* ── MENU STATUT ── */
#salleMenu { position:fixed; z-index:9500; background:#fff; border:1px solid #e2e8f0; border-radius:14px; box-shadow:0 16px 40px rgba(0,0,0,.2); padding:8px; display:none; min-width:190px; }
#salleMenu .menu-title { font-size:.68rem; color:#94a3b8; font-weight:800; text-transform:uppercase; padding:6px 10px 4px; letter-spacing:.5px; }
#salleMenu button { display:flex; align-items:center; gap:9px; width:100%; border:none; background:none; text-align:left; padding:9px 12px; border-radius:9px; font-size:.83rem; font-weight:600; cursor:pointer; }
#salleMenu button:hover { background:#f1f5f9; }

/* ── EMPTY ── */
.empty-bloc { display:flex; flex-direction:column; align-items:center; padding:40px 20px; color:#94a3b8; }
.empty-bloc i { font-size:2.8rem; margin-bottom:12px; opacity:.35; }
.empty-bloc p { font-size:.85rem; margin:0; }
</style>

<main>
<!-- ══ TOPBAR ══ -->
<div class="bloc-topbar">
    <div class="bloc-brand">
        <div class="bloc-brand-icon"><i class="bi bi-scissors"></i></div>
        <div class="bloc-brand-text">
            <div class="title">Bloc Opératoire</div>
            <div class="sub">Suivi per-opératoire · HSJM</div>
        </div>
    </div>

    <div class="bloc-clock" id="blocClock"><?= date('H:i:s') ?></div>

    <div class="bloc-actions">
        <a href="<?= BASE_URL ?>bloc/sspi-dashboard" class="tb-btn" style="background:linear-gradient(135deg,#d97706,#f59e0b);border-color:transparent;">
            <i class="bi bi-heart-pulse-fill"></i> SSPI
            <?php if (($kpi['en_sspi'] ?? 0) > 0): ?>
            <span style="background:#fff;color:#d97706;font-size:.68rem;font-weight:900;padding:1px 7px;border-radius:20px;margin-left:3px;"><?= (int)$kpi['en_sspi'] ?></span>
            <?php endif; ?>
        </a>
        <button onclick="ouvrirModalProgrammerDirect()" class="tb-btn primary">
            <i class="bi bi-calendar-plus-fill"></i> Nouvelle Intervention
        </button>
        <button onclick="ouvrirProfilModal()" class="tb-btn" title="Mon profil" style="border:none;">
            <i class="bi bi-person-circle"></i>
            <span><?= htmlspecialchars($userNom) ?></span>
        </button>
        <a href="<?= BASE_URL ?>dashboard" class="tb-btn"><i class="bi bi-arrow-left"></i> Retour</a>
        <a href="<?= BASE_URL ?>logout" class="tb-btn danger" onclick="return confirm('Confirmer la déconnexion ?')">
            <i class="bi bi-box-arrow-right"></i> Déconnexion
        </a>
    </div>
</div>

<!-- ══ BODY ══ -->
<div class="bloc-body">

    <?php if (isset($_GET['success']) && $_GET['success']==='termine'): ?>
    <div class="bloc-flash"><i class="bi bi-check-circle-fill"></i> Intervention terminée — patient sorti de la salle de réveil avec succès.</div>
    <?php endif; ?>

    <!-- KPI -->
    <div class="kpi-grid">
        <div class="kpi-card k-green">
            <i class="bi bi-door-open kpi-icon"></i>
            <div class="kpi-value"><?= $kpi['salles_libres'] ?>/<span style="font-size:1.1rem"><?= $kpi['salles_total'] ?></span></div>
            <div class="kpi-label">Salles libres</div>
        </div>
        <div class="kpi-card k-amber">
            <i class="bi bi-hourglass-split kpi-icon"></i>
            <div class="kpi-value"><?= $kpi['attente'] ?></div>
            <div class="kpi-label">File d'attente</div>
        </div>
        <div class="kpi-card k-blue">
            <i class="bi bi-calendar2-check kpi-icon"></i>
            <div class="kpi-value"><?= $kpi['prog_jour'] ?></div>
            <div class="kpi-label">Programmées</div>
        </div>
        <div class="kpi-card k-red">
            <i class="bi bi-activity kpi-icon"></i>
            <div class="kpi-value"><?= $kpi['en_cours'] ?></div>
            <div class="kpi-label">En cours</div>
        </div>
        <div class="kpi-card k-violet">
            <i class="bi bi-heart-pulse kpi-icon"></i>
            <div class="kpi-value"><?= $kpi['en_sspi'] ?></div>
            <div class="kpi-label">En réveil</div>
        </div>
        <div class="kpi-card k-slate">
            <i class="bi bi-check2-all kpi-icon"></i>
            <div class="kpi-value"><?= $kpi['termine_jour'] ?></div>
            <div class="kpi-label">Terminées (j)</div>
        </div>
    </div>

    <div class="bloc-grid">
        <!-- ── COLONNE SALLES ── -->
        <div>
            <div class="panel">
                <div class="panel-head">
                    <div class="panel-head-title" style="color:#312e81;">
                        <i class="bi bi-building-fill" style="font-size:1rem;"></i> Salles d'opération
                    </div>
                    <span style="font-size:.72rem;background:#f1f5f9;color:#475569;padding:3px 10px;border-radius:20px;font-weight:700;">
                        <?= $kpi['salles_libres'] ?>/<?= $kpi['salles_total'] ?> disponibles
                    </span>
                </div>
                <div class="panel-body">
                    <?php if (empty($salles)): ?>
                    <div class="empty-bloc"><i class="bi bi-building-slash"></i><p>Aucune salle configurée.</p></div>
                    <?php else: foreach ($salles as $salle):
                        $st  = strtolower($salle['statut'] ?? 'disponible');
                        $cls = in_array($st,['disponible','libre']) ? 'disponible' : ($st==='occupee' ? 'occupee' : ($st==='nettoyage' ? 'nettoyage' : 'maintenance'));
                        $canEdit = ($cls !== 'occupee');
                    ?>
                    <div class="salle-card <?= $cls ?>">
                        <div class="salle-dot"></div>
                        <div class="salle-info">
                            <div class="salle-nom"><i class="bi bi-door-closed me-1" style="opacity:.6"></i><?= htmlspecialchars($salle['nom_salle']) ?></div>
                            <div class="salle-type"><?= htmlspecialchars($salle['type_salle'] ?? 'Chirurgie') ?></div>
                            <div class="mt-1"><span class="salle-status-badge"><?= strtoupper($salle['statut']) ?></span></div>
                        </div>
                        <?php if ($canEdit): ?>
                        <button class="salle-gear-btn" title="Changer le statut"
                                onclick="ouvrirSalleMenu(this,<?= (int)$salle['id'] ?>,'<?= addslashes(htmlspecialchars($salle['nom_salle'])) ?>')">
                            <i class="bi bi-sliders2"></i>
                        </button>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; endif; ?>
                </div>
            </div>

            <?php if (!empty($file_attente)): ?>
            <!-- File d'attente compacte dans la colonne salles -->
            <div class="panel mt-4">
                <div class="panel-head">
                    <div class="panel-head-title" style="color:#d97706;">
                        <i class="bi bi-hourglass-split"></i> File d'attente
                        <span style="background:#fef3c7;color:#92400e;font-size:.65rem;padding:2px 8px;border-radius:20px;margin-left:6px;"><?= count($file_attente) ?></span>
                    </div>
                </div>
                <div class="panel-body">
                    <?php foreach ($file_attente as $req):
                        $initials = strtoupper(substr($req['nom'],0,1).substr($req['prenom'],0,1));
                    ?>
                    <div class="attente-card">
                        <div class="attente-avatar"><?= $initials ?></div>
                        <div class="attente-info">
                            <div class="attente-nom"><?= htmlspecialchars(strtoupper($req['nom']).' '.$req['prenom']) ?></div>
                            <div class="attente-meta"><i class="bi bi-person-badge me-1"></i>Dr. <?= htmlspecialchars($req['chirurgien_nom']) ?> · <?= date('d/m H:i', strtotime($req['date_demande'])) ?></div>
                        </div>
                        <button class="act-link act-prog"
                                onclick="openProgModal(<?= (int)$req['id'] ?>,'<?= addslashes(htmlspecialchars($req['nom'].' '.$req['prenom'])) ?>')">
                            <i class="bi bi-calendar-plus"></i> Prog.
                        </button>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- ── COLONNE PROGRAMME ── -->
        <div>
            <div class="panel">
                <div class="panel-head">
                    <div class="panel-head-title" style="color:#312e81;">
                        <i class="bi bi-calendar2-week" style="font-size:1rem;"></i>
                        Programme opératoire — <?= date('d/m/Y') ?>
                    </div>
                    <?php if (!empty($kpi['en_cours'])): ?>
                    <span style="display:inline-flex;align-items:center;gap:5px;background:#fee2e2;color:#dc2626;font-size:.7rem;font-weight:800;padding:4px 12px;border-radius:20px;">
                        <span style="width:7px;height:7px;background:#dc2626;border-radius:50%;animation:pulse-r 1s infinite;"></span>
                        <?= $kpi['en_cours'] ?> EN COURS
                    </span>
                    <?php endif; ?>
                </div>

                <?php if (empty($interventions)): ?>
                <div class="empty-bloc" style="padding:60px 20px;">
                    <i class="bi bi-calendar-x"></i>
                    <p>Aucune intervention programmée aujourd'hui.</p>
                    <?php if (empty($file_attente)): ?>
                    <p style="font-size:.78rem;color:#cbd5e1;margin-top:6px;">La file d'attente est vide.</p>
                    <?php endif; ?>
                </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="prog-table">
                        <thead>
                            <tr>
                                <th>Heure</th>
                                <th>Patient</th>
                                <th>Intervention</th>
                                <th>Salle</th>
                                <th>Statut</th>
                                <th style="text-align:right">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($interventions as $int):
                            [$lbl,$col,$ic] = BlocController::statutInfo($int['statut']);
                            $st = strtoupper($int['statut']);
                        ?>
                        <tr>
                            <td>
                                <span style="background:#ede9fe;color:#5b21b6;font-weight:800;font-size:.82rem;padding:4px 10px;border-radius:8px;">
                                    <?= substr($int['heure_debut'],0,5) ?>
                                </span>
                            </td>
                            <td>
                                <div style="display:flex;align-items:center;gap:10px;">
                                    <div style="width:34px;height:34px;border-radius:10px;background:linear-gradient(135deg,#6366f1,#8b5cf6);display:flex;align-items:center;justify-content:center;color:#fff;font-weight:800;font-size:.72rem;flex-shrink:0;">
                                        <?= strtoupper(substr($int['nom'],0,1).substr($int['prenom'],0,1)) ?>
                                    </div>
                                    <div>
                                        <div style="font-weight:800;font-size:.86rem;color:#0f172a;"><?= htmlspecialchars(strtoupper($int['nom']).' '.$int['prenom']) ?></div>
                                        <div style="font-size:.72rem;color:#94a3b8;"><?= htmlspecialchars($int['dossier_numero'] ?? '') ?></div>
                                    </div>
                                </div>
                            </td>
                            <td style="max-width:200px;">
                                <span style="font-size:.8rem;color:#475569;"><?= htmlspecialchars(mb_strimwidth($int['diagnostique_op'] ?? '—',0,45,'…')) ?></span>
                            </td>
                            <td>
                                <span style="background:#1e1b4b;color:#c4b5fd;font-size:.72rem;font-weight:800;padding:4px 10px;border-radius:8px;">
                                    <?= htmlspecialchars($int['salle_nom']) ?>
                                </span>
                            </td>
                            <td>
                                <span class="st-pill" style="background:<?= $col ?>;color:#fff;">
                                    <i class="bi <?= $ic ?>"></i><?= $lbl ?>
                                </span>
                            </td>
                            <td style="text-align:right;">
                                <?php if ($st==='PROGRAMME'): ?>
                                    <div style="display:flex;gap:6px;justify-content:flex-end;flex-wrap:wrap;">
                                    <a href="<?= BASE_URL ?>bloc/demarrer/<?= (int)$int['id'] ?>"
                                       class="act-link act-demarrer"
                                       onclick="return confirm('Démarrer cette intervention pour <?= addslashes(htmlspecialchars($int['nom'].' '.$int['prenom'])) ?> ?')">
                                        <i class="bi bi-play-fill"></i> Démarrer
                                    </a>
                                    <button class="act-link act-modifier"
                                        onclick='ouvrirModifier(<?= htmlspecialchars(json_encode([
                                            "id"               => (int)$int["id"],
                                            "salle_id"         => (int)$int["salle_id"],
                                            "date_intervention"=> $int["date_intervention"],
                                            "heure_debut"      => substr($int["heure_debut"],0,5),
                                            "diagnostique_op"  => $int["diagnostique_op"] ?? "",
                                            "patient"          => strtoupper($int["nom"])." ".$int["prenom"],
                                            "chirurgien_id"    => (int)($int["chirurgien_id"] ?? 0),
                                            "anesthesiste_id"  => (int)($int["anesthesiste_id"] ?? 0),
                                            "equipe_json"      => $int["equipe_json"] ?? "[]",
                                        ]), ENT_QUOTES) ?>)'>
                                        <i class="bi bi-pencil-fill"></i> Modifier
                                    </button>
                                    <button class="act-link act-annuler"
                                        onclick="annulerInter(<?= (int)$int['id'] ?>,'<?= addslashes(htmlspecialchars($int['nom'].' '.$int['prenom'])) ?>')">
                                        <i class="bi bi-x-circle-fill"></i> Annuler
                                    </button>
                                    </div>
                                <?php elseif ($st==='EN_COURS'): ?>
                                    <a href="<?= BASE_URL ?>bloc/monitoring/<?= (int)$int['id'] ?>" class="act-link act-cockpit">
                                        <i class="bi bi-activity"></i> Cockpit
                                    </a>
                                <?php elseif ($st==='EN_SSPI'): ?>
                                    <a href="<?= BASE_URL ?>bloc/sspi/<?= (int)$int['id'] ?>" class="act-link act-reveil">
                                        <i class="bi bi-heart-pulse"></i> Réveil
                                    </a>
                                <?php else: ?>
                                    <a href="<?= BASE_URL ?>bloc/sspi/<?= (int)$int['id'] ?>" class="act-link act-voir">
                                        <i class="bi bi-eye"></i> Voir
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>

            <?php if (empty($file_attente)): ?>
            <div class="panel mt-4" style="border:1.5px dashed #e2e8f0;box-shadow:none;background:transparent;">
                <div class="empty-bloc" style="padding:28px;">
                    <i class="bi bi-inbox" style="font-size:2rem;"></i>
                    <p>Aucun patient en file d'attente</p>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div><!-- end bloc-body -->

<?php if (!empty($ouvertures)): ?>
<!-- ══ FICHES D'OUVERTURE DE SALLE ══ -->
<div style="max-width:1400px;margin:0 auto;padding:0 20px 30px;">
    <div style="background:#fff;border-radius:18px;border:1px solid #e2e8f0;box-shadow:0 2px 12px rgba(0,0,0,.06);overflow:hidden;">
        <div style="padding:14px 20px;border-bottom:1px solid #f1f5f9;display:flex;align-items:center;justify-content:space-between;background:linear-gradient(135deg,#fafafa,#f8fafc);">
            <div style="display:flex;align-items:center;gap:8px;font-size:.82rem;font-weight:800;color:#374151;text-transform:uppercase;letter-spacing:.5px;">
                <i class="bi bi-clipboard2-check-fill" style="color:#4f46e5;font-size:1rem;"></i>
                Fiches d'ouverture de salle — <?= date('d/m/Y') ?>
                <span style="background:#ede9fe;color:#4f46e5;font-size:.7rem;font-weight:700;padding:2px 9px;border-radius:20px;"><?= count($ouvertures) ?> fiche<?= count($ouvertures)>1?'s':'' ?></span>
            </div>
        </div>
        <div style="overflow-x:auto;">
        <table style="width:100%;border-collapse:collapse;font-size:.82rem;">
            <thead>
                <tr style="background:#f8fafc;">
                    <th style="padding:10px 16px;text-align:left;font-size:.7rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.4px;border-bottom:1px solid #e2e8f0;">Heure</th>
                    <th style="padding:10px 16px;text-align:left;font-size:.7rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.4px;border-bottom:1px solid #e2e8f0;">Patient</th>
                    <th style="padding:10px 16px;text-align:left;font-size:.7rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.4px;border-bottom:1px solid #e2e8f0;">Salle</th>
                    <th style="padding:10px 16px;text-align:left;font-size:.7rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.4px;border-bottom:1px solid #e2e8f0;">Intervention</th>
                    <th style="padding:10px 16px;text-align:left;font-size:.7rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.4px;border-bottom:1px solid #e2e8f0;">Rempli par</th>
                    <th style="padding:10px 16px;text-align:left;font-size:.7rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.4px;border-bottom:1px solid #e2e8f0;">Statut alerte</th>
                    <th style="padding:10px 16px;text-align:right;font-size:.7rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.4px;border-bottom:1px solid #e2e8f0;">Action</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($ouvertures as $ouv):
                // Compter anomalies (fonctionnalité ou présence = non)
                $checklist = json_decode($ouv['checklist_json'] ?? '{}', true) ?: [];
                $anomalies = 0;
                foreach ($checklist as $item) {
                    if (($item['presence'] ?? '') === 'non' || ($item['fonctionnalite'] ?? '') === 'non') $anomalies++;
                }
            ?>
            <tr style="border-bottom:1px solid #f1f5f9;transition:background .12s;" onmouseover="this.style.background='#fafafa'" onmouseout="this.style.background=''">
                <td style="padding:12px 16px;">
                    <span style="background:#ede9fe;color:#4f46e5;font-weight:800;font-size:.78rem;padding:3px 9px;border-radius:7px;">
                        <?= date('H:i', strtotime($ouv['created_at'])) ?>
                    </span>
                </td>
                <td style="padding:12px 16px;">
                    <div style="display:flex;align-items:center;gap:9px;">
                        <div style="width:32px;height:32px;border-radius:9px;background:linear-gradient(135deg,#6366f1,#8b5cf6);display:flex;align-items:center;justify-content:center;color:#fff;font-weight:800;font-size:.68rem;flex-shrink:0;">
                            <?= strtoupper(substr($ouv['patient_nom'],0,1).substr($ouv['patient_prenom'],0,1)) ?>
                        </div>
                        <div>
                            <div style="font-weight:800;color:#0f172a;"><?= htmlspecialchars(strtoupper($ouv['patient_nom']).' '.$ouv['patient_prenom']) ?></div>
                            <div style="font-size:.7rem;color:#94a3b8;"><?= htmlspecialchars($ouv['dossier_numero']) ?></div>
                        </div>
                    </div>
                </td>
                <td style="padding:12px 16px;">
                    <span style="background:#1e1b4b;color:#c4b5fd;font-size:.72rem;font-weight:800;padding:3px 9px;border-radius:7px;">
                        <?= htmlspecialchars($ouv['nom_salle']) ?>
                    </span>
                </td>
                <td style="padding:12px 16px;max-width:180px;">
                    <span style="font-size:.78rem;color:#475569;"><?= htmlspecialchars(mb_strimwidth($ouv['diagnostique_op']??'—',0,40,'…')) ?></span>
                </td>
                <td style="padding:12px 16px;">
                    <div style="display:flex;align-items:center;gap:7px;">
                        <div style="width:28px;height:28px;border-radius:8px;background:linear-gradient(135deg,#f0fdf4,#dcfce7);display:flex;align-items:center;justify-content:center;font-size:.65rem;font-weight:800;color:#16a34a;flex-shrink:0;">
                            <?= strtoupper(substr($ouv['user_prenom']??'?',0,1).substr($ouv['user_nom']??'',0,1)) ?>
                        </div>
                        <div style="font-size:.78rem;font-weight:600;color:#1e293b;">
                            <?= htmlspecialchars(($ouv['user_prenom']??'').' '.($ouv['user_nom']??'Inconnu')) ?>
                        </div>
                    </div>
                </td>
                <td style="padding:12px 16px;">
                    <?php if ($anomalies > 0): ?>
                    <span style="background:#fef2f2;color:#dc2626;border:1.5px solid #fecaca;font-size:.72rem;font-weight:700;padding:3px 10px;border-radius:20px;">
                        <i class="bi bi-exclamation-triangle-fill me-1"></i><?= $anomalies ?> anomalie<?= $anomalies>1?'s':'' ?>
                    </span>
                    <?php else: ?>
                    <span style="background:#f0fdf4;color:#16a34a;border:1.5px solid #86efac;font-size:.72rem;font-weight:700;padding:3px 10px;border-radius:20px;">
                        <i class="bi bi-check-circle-fill me-1"></i>Tout OK
                    </span>
                    <?php endif; ?>
                </td>
                <td style="padding:12px 16px;text-align:right;">
                    <button onclick='voirFiche(<?= htmlspecialchars(json_encode([
                        "patient"    => strtoupper($ouv['patient_nom']).' '.$ouv['patient_prenom'],
                        "dossier"    => $ouv['dossier_numero'],
                        "salle"      => $ouv['nom_salle'],
                        "inter"      => $ouv['diagnostique_op'] ?? '—',
                        "heure_op"   => substr($ouv['heure_debut'],0,5),
                        "remplit_par"=> ($ouv['user_prenom']??'').' '.($ouv['user_nom']??'Inconnu'),
                        "heure"      => date('H:i', strtotime($ouv['created_at'])),
                        "date_fiche" => $ouv['date_fiche'] ?? '',
                        "heure_ouv"  => $ouv['heure_ouverture'] ?? '',
                        "salle_id"   => $ouv['salle_identite'] ?? '',
                        "grpe_on"    => $ouv['heure_groupe_on'] ?? '',
                        "grpe_off"   => $ouv['heure_groupe_off'] ?? '',
                        "transmis"   => $ouv['transmis_arret'] ?? '',
                        "signature"  => $ouv['signature_responsable'] ?? '',
                        "checklist"  => $checklist,
                        "anomalies"  => $anomalies,
                    ]), ENT_QUOTES) ?>)'
                    style="display:inline-flex;align-items:center;gap:5px;padding:6px 13px;border-radius:9px;font-size:.75rem;font-weight:700;cursor:pointer;border:1.5px solid #bfdbfe;background:#eff6ff;color:#1d4ed8;transition:all .15s;"
                    onmouseover="this.style.background='#dbeafe'" onmouseout="this.style.background='#eff6ff'">
                        <i class="bi bi-eye-fill"></i> Voir la fiche
                    </button>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
    </div>
</div>
<?php endif; ?>

</main>

<!-- ══ MODAL FICHE OUVERTURE ══ -->
<div class="modal fade" id="modalFicheOuverture" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content border-0 shadow-lg" style="border-radius:20px;overflow:hidden;">
      <div class="modal-header text-white border-0" style="background:linear-gradient(135deg,#1e1b4b,#4f46e5);padding:18px 24px;">
        <div>
          <h5 class="modal-title fw-bold mb-0"><i class="bi bi-clipboard2-check-fill me-2"></i>Fiche d'ouverture de salle</h5>
          <p class="mb-0 small opacity-75" id="ficheSubtitle"></p>
        </div>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-0" style="max-height:75vh;overflow-y:auto;">

        <!-- Infos générales -->
        <div style="padding:16px 20px;background:#f8fafc;border-bottom:1px solid #e2e8f0;">
          <div class="row g-3" id="ficheInfos"></div>
        </div>

        <!-- Tableau checklist -->
        <div style="padding:16px 20px;">
          <p style="font-size:.72rem;font-weight:800;text-transform:uppercase;letter-spacing:.5px;color:#64748b;margin-bottom:10px;"><i class="bi bi-check2-all me-1"></i>Vérification des équipements</p>
          <div class="table-responsive">
          <table style="width:100%;border-collapse:collapse;font-size:.8rem;" id="ficheTable">
            <thead>
              <tr style="background:#f1f5f9;">
                <th style="padding:8px 12px;text-align:left;border:1px solid #e2e8f0;font-size:.7rem;color:#64748b;font-weight:700;text-transform:uppercase;">Équipement</th>
                <th style="padding:8px 12px;text-align:center;border:1px solid #e2e8f0;font-size:.7rem;color:#64748b;font-weight:700;text-transform:uppercase;">Présence</th>
                <th style="padding:8px 12px;text-align:center;border:1px solid #e2e8f0;font-size:.7rem;color:#64748b;font-weight:700;text-transform:uppercase;">Fonctionnalité</th>
                <th style="padding:8px 12px;text-align:center;border:1px solid #e2e8f0;font-size:.7rem;color:#64748b;font-weight:700;text-transform:uppercase;">Propreté</th>
                <th style="padding:8px 12px;text-align:left;border:1px solid #e2e8f0;font-size:.7rem;color:#64748b;font-weight:700;text-transform:uppercase;">Observations</th>
              </tr>
            </thead>
            <tbody id="ficheChecklist"></tbody>
          </table>
          </div>
        </div>

        <!-- Fin session -->
        <div style="padding:12px 20px 18px;background:#f8fafc;border-top:1px solid #e2e8f0;" id="ficheFinSession"></div>
      </div>
      <div class="modal-footer border-0 px-4 pb-3 pt-2">
        <button class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Fermer</button>
        <button class="btn rounded-pill px-4 fw-bold text-white" onclick="window.print()"
                style="background:linear-gradient(135deg,#4f46e5,#7c3aed);">
          <i class="bi bi-printer-fill me-1"></i>Imprimer
        </button>
      </div>
    </div>
  </div>
</div>

<!-- ══ MODAL PROGRAMMER DIRECT ══ -->
<div class="modal fade" id="modalProgrammerDirect" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" style="max-width:520px">
    <div class="modal-content border-0 shadow-lg" style="border-radius:24px;overflow:hidden;">
      <div class="modal-header border-0" style="background:linear-gradient(135deg,#1e1b4b,#4f46e5,#7c3aed);padding:22px 28px 18px;">
        <div>
          <h5 class="modal-title text-white fw-bold mb-0"><i class="bi bi-calendar-plus-fill me-2"></i>Nouvelle Intervention</h5>
          <p class="text-white mb-0" style="opacity:.7;font-size:.78rem;margin-top:3px;">Programmer directement depuis le bloc opératoire</p>
        </div>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body px-4 py-3">

        <!-- Recherche patient -->
        <div class="mb-3">
          <label class="small fw-bold text-muted mb-1"><i class="bi bi-search me-1"></i>Patient</label>
          <input type="text" id="pdPatientSearch" class="form-control rounded-3"
                 placeholder="Rechercher par nom, prénom ou n° dossier…" autocomplete="off">
          <div id="pdPatientResults" style="border:1px solid #e2e8f0;border-radius:12px;margin-top:4px;max-height:180px;overflow-y:auto;display:none;background:#fff;box-shadow:0 8px 20px rgba(0,0,0,.1);"></div>
          <input type="hidden" id="pdPatientId">
          <div id="pdPatientSelected" style="display:none;margin-top:8px;background:#ede9fe;border-radius:12px;padding:10px 14px;display:flex;align-items:center;gap:10px;">
            <div style="width:36px;height:36px;border-radius:10px;background:linear-gradient(135deg,#6366f1,#8b5cf6);display:flex;align-items:center;justify-content:center;color:#fff;font-weight:800;font-size:.78rem;flex-shrink:0;" id="pdAvatar">??</div>
            <div><div id="pdSelectedName" style="font-weight:800;font-size:.88rem;color:#3730a3;"></div><div id="pdSelectedDossier" style="font-size:.73rem;color:#6366f1;"></div></div>
            <button type="button" onclick="resetPatientPD()" style="margin-left:auto;border:none;background:rgba(99,102,241,.15);border-radius:8px;width:26px;height:26px;color:#6366f1;cursor:pointer;" title="Changer"><i class="bi bi-x-lg"></i></button>
          </div>
        </div>

        <div class="row g-3 mb-3">
          <div class="col-md-6">
            <label class="small fw-bold text-muted mb-1"><i class="bi bi-building me-1"></i>Salle d'opération</label>
            <select id="pdSalleId" class="form-select rounded-3" required>
              <option value="">— Sélectionner —</option>
              <?php foreach ($salles as $s): if (strtolower($s['statut'])==='occupee') continue; ?>
              <option value="<?= (int)$s['id'] ?>"><?= htmlspecialchars($s['nom_salle']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-3">
            <label class="small fw-bold text-muted mb-1"><i class="bi bi-calendar3 me-1"></i>Date</label>
            <input type="date" id="pdDate" class="form-control rounded-3" value="<?= date('Y-m-d') ?>" required>
          </div>
          <div class="col-md-3">
            <label class="small fw-bold text-muted mb-1"><i class="bi bi-clock me-1"></i>Heure</label>
            <input type="time" id="pdHeure" class="form-control rounded-3" required>
          </div>
        </div>

        <div class="mb-3">
          <label class="small fw-bold text-muted mb-1"><i class="bi bi-clipboard2-pulse me-1"></i>Diagnostic / Intervention prévue</label>
          <textarea id="pdDiag" class="form-control rounded-3" rows="2" placeholder="Ex: Appendicectomie, Herniotomie, Cholécystectomie…" required></textarea>
        </div>

        <!-- Intervenants -->
        <div style="background:#f8fafc;border:1.5px solid #e2e8f0;border-radius:14px;padding:14px 16px;margin-bottom:14px;">
          <div style="font-size:.72rem;font-weight:800;color:#64748b;text-transform:uppercase;letter-spacing:.5px;margin-bottom:10px;">
            <i class="bi bi-people-fill me-1"></i>Équipe intervenante
          </div>
          <div class="row g-2">
            <div class="col-md-6">
              <label class="small fw-bold text-muted mb-1"><i class="bi bi-person-badge me-1" style="color:#6366f1"></i>Chirurgien</label>
              <select id="pdChirurgienId" class="form-select form-select-sm rounded-3">
                <option value="">— Sélectionner —</option>
                <?php foreach (($medecins ?? []) as $m): ?>
                <option value="<?= (int)$m['id'] ?>"
                    <?= ((int)$m['id'] === (int)($_SESSION['user_id'] ?? 0)) ? 'selected' : '' ?>>
                  Dr. <?= htmlspecialchars($m['prenom'].' '.$m['nom']) ?>
                </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6">
              <label class="small fw-bold text-muted mb-1"><i class="bi bi-capsule me-1" style="color:#8b5cf6"></i>Anesthésiste</label>
              <select id="pdAnesthId" class="form-select form-select-sm rounded-3">
                <option value="">— À désigner —</option>
                <?php foreach (($medecins ?? []) as $m): ?>
                <option value="<?= (int)$m['id'] ?>">Dr. <?= htmlspecialchars($m['prenom'].' '.$m['nom']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6">
              <label class="small fw-bold text-muted mb-1"><i class="bi bi-clipboard2-pulse me-1" style="color:#10b981"></i>Infirmier(ère) circulant(e)</label>
              <select id="pdInfCircId" class="form-select form-select-sm rounded-3">
                <option value="">— À désigner —</option>
                <?php foreach (($infirmiers_bloc ?? []) as $i): ?>
                <option value="<?= (int)$i['id'] ?>"><?= htmlspecialchars(strtoupper($i['nom']).' '.$i['prenom']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6">
              <label class="small fw-bold text-muted mb-1"><i class="bi bi-scissors me-1" style="color:#f59e0b"></i>Instrumentiste</label>
              <select id="pdInstrId" class="form-select form-select-sm rounded-3">
                <option value="">— À désigner —</option>
                <?php foreach (($infirmiers_bloc ?? []) as $i): ?>
                <option value="<?= (int)$i['id'] ?>"><?= htmlspecialchars(strtoupper($i['nom']).' '.$i['prenom']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
        </div>

        <div id="pdMsg" style="display:none;font-size:.83rem;font-weight:700;padding:10px 14px;border-radius:10px;"></div>
      </div>
      <div class="modal-footer border-0 px-4 pb-4 pt-0">
        <button type="button" class="btn btn-light rounded-pill px-4 fw-semibold" data-bs-dismiss="modal">Annuler</button>
        <button type="button" id="pdSubmitBtn" class="btn text-white rounded-pill px-5 fw-bold"
                style="background:linear-gradient(135deg,#4f46e5,#7c3aed);box-shadow:0 4px 14px rgba(79,70,229,.4);"
                onclick="soumettreInterventionDirecte()">
            <i class="bi bi-check2-circle me-1"></i>Programmer
        </button>
      </div>
    </div>
  </div>
</div>

<!-- ══ MENU STATUT SALLE ══ -->
<div id="salleMenu">
    <div class="menu-title" id="salleMenuTitle">Salle</div>
    <button onclick="setSalle('DISPONIBLE')"><i class="bi bi-check-circle-fill" style="color:#10b981"></i> Disponible</button>
    <button onclick="setSalle('NETTOYAGE')"><i class="bi bi-droplet-half" style="color:#f59e0b"></i> En nettoyage</button>
    <button onclick="setSalle('MAINTENANCE')"><i class="bi bi-tools" style="color:#64748b"></i> En maintenance</button>
</div>

<!-- ══ MODAL PROGRAMMER ══ -->
<div class="modal fade" id="modalProgrammer" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" style="max-width:480px">
    <div class="modal-content border-0 shadow-lg" style="border-radius:24px;overflow:hidden;">
      <div class="modal-header border-0 pb-0" style="background:linear-gradient(135deg,#312e81,#6366f1,#8b5cf6);padding:24px 28px 20px;">
        <div>
            <h5 class="modal-title text-white fw-bold mb-0"><i class="bi bi-calendar-plus me-2"></i>Programmer au bloc</h5>
            <p class="text-white mb-0" style="opacity:.7;font-size:.8rem;margin-top:3px;">Planifier une intervention chirurgicale</p>
        </div>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form action="<?= BASE_URL ?>bloc/confirmer-programmation" method="POST">
        <?= CsrfService::field() ?>
        <input type="hidden" name="demande_id" id="prog_demande_id">
        <div class="modal-body px-4 pt-3 pb-2">
          <div class="mb-3">
            <label class="small fw-bold text-muted mb-1">Patient</label>
            <input type="text" id="prog_patient_name" class="form-control bg-light rounded-3 fw-bold" readonly>
          </div>
          <div class="row g-3 mb-3">
            <div class="col-md-6">
              <label class="small fw-bold text-muted mb-1">Salle d'opération</label>
              <select name="salle_id" class="form-select rounded-3" required>
                <?php foreach ($salles as $s): if (strtolower($s['statut'])==='occupee') continue; ?>
                <option value="<?= (int)$s['id'] ?>"><?= htmlspecialchars($s['nom_salle']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6">
              <label class="small fw-bold text-muted mb-1">Heure prévue</label>
              <input type="time" name="heure" class="form-control rounded-3" required>
            </div>
          </div>
          <div class="mb-3">
            <label class="small fw-bold text-muted mb-1">Date</label>
            <input type="date" name="date" class="form-control rounded-3" value="<?= date('Y-m-d') ?>" required>
          </div>
          <div class="mb-3">
            <label class="small fw-bold text-muted mb-1">Diagnostic / Intervention prévue</label>
            <textarea name="diagnostic" class="form-control rounded-3" rows="3" placeholder="Ex: Appendicectomie, Herniotomie…" required></textarea>
          </div>
        </div>
        <div class="modal-footer border-0 pt-0 px-4 pb-4">
          <button type="button" class="btn btn-light rounded-pill px-4 fw-semibold" data-bs-dismiss="modal">Annuler</button>
          <button type="submit" class="btn text-white rounded-pill px-5 fw-bold"
                  style="background:linear-gradient(135deg,#4f46e5,#7c3aed);box-shadow:0 4px 14px rgba(79,70,229,.4);">
              <i class="bi bi-check2-circle me-1"></i>Confirmer
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
/* ── Modal Programmer Direct ── */
function ouvrirModalProgrammerDirect() {
    document.getElementById('pdPatientId').value = '';
    document.getElementById('pdPatientSearch').value = '';
    document.getElementById('pdPatientResults').innerHTML = '';
    document.getElementById('pdPatientSelected').style.display = 'none';
    document.getElementById('pdMsg').style.display = 'none';
    document.getElementById('pdDate').value = new Date().toISOString().slice(0,10);
    document.getElementById('pdHeure').value = '';
    document.getElementById('pdDiag').value = '';
    const btn = document.getElementById('pdSubmitBtn');
    btn.disabled = false;
    btn.innerHTML = '<i class="bi bi-check2-circle me-1"></i>Programmer';
    new bootstrap.Modal(document.getElementById('modalProgrammerDirect')).show();
    setTimeout(() => document.getElementById('pdPatientSearch').focus(), 300);
}

/* Recherche patient pour modal direct */
let pdTimer = null;
document.addEventListener('DOMContentLoaded', () => {
    const inp = document.getElementById('pdPatientSearch');
    if (!inp) return;
    inp.addEventListener('input', function(){
        clearTimeout(pdTimer);
        const q = this.value.trim();
        const box = document.getElementById('pdPatientResults');
        if (q.length < 2) { box.style.display='none'; return; }
        pdTimer = setTimeout(() => {
            fetch(BB + 'consultation/search-patients?q=' + encodeURIComponent(q))
                .then(r => r.json())
                .then(data => {
                    if (!data.length) { box.innerHTML='<div style="padding:12px;color:#94a3b8;font-size:.83rem;text-align:center;">Aucun patient trouvé</div>'; box.style.display='block'; return; }
                    box.innerHTML = data.map(p => {
                        const ini = ((p.nom||'?')[0]+(p.prenom||'')[0]).toUpperCase();
                        return `<div onclick="selectPatientPD(${p.id},'${(p.nom||'').toUpperCase().replace(/'/g,"\\'")}','${(p.prenom||'').replace(/'/g,"\\'")}','${(p.dossier_numero||'').replace(/'/g,"\\'")}','${ini}')"
                            style="display:flex;align-items:center;gap:10px;padding:10px 14px;cursor:pointer;border-bottom:1px solid #f1f5f9;"
                            onmouseover="this.style.background='#f5f3ff'" onmouseout="this.style.background=''">
                            <div style="width:32px;height:32px;border-radius:9px;background:linear-gradient(135deg,#6366f1,#8b5cf6);color:#fff;font-weight:800;font-size:.72rem;display:flex;align-items:center;justify-content:center;">${ini}</div>
                            <div><strong>${(p.nom||'').toUpperCase()} ${p.prenom||''}</strong><br><small style="color:#94a3b8;">${p.dossier_numero||''}</small></div>
                        </div>`;
                    }).join('');
                    box.style.display = 'block';
                }).catch(() => { box.style.display='none'; });
        }, 280);
    });
    document.addEventListener('click', e => {
        if (!e.target.closest('#pdPatientResults') && !e.target.closest('#pdPatientSearch'))
            document.getElementById('pdPatientResults').style.display = 'none';
    });
});

function selectPatientPD(id, nom, prenom, dossier, ini) {
    document.getElementById('pdPatientId').value = id;
    document.getElementById('pdPatientSearch').value = nom + ' ' + prenom;
    document.getElementById('pdPatientResults').style.display = 'none';
    document.getElementById('pdAvatar').textContent = ini;
    document.getElementById('pdSelectedName').textContent = nom + ' ' + prenom;
    document.getElementById('pdSelectedDossier').textContent = dossier ? 'Dossier : ' + dossier : '';
    const sel = document.getElementById('pdPatientSelected');
    sel.style.display = 'flex';
}

function resetPatientPD() {
    document.getElementById('pdPatientId').value = '';
    document.getElementById('pdPatientSearch').value = '';
    document.getElementById('pdPatientSelected').style.display = 'none';
    document.getElementById('pdPatientSearch').focus();
}

function soumettreInterventionDirecte() {
    const patId = document.getElementById('pdPatientId').value;
    const salle = document.getElementById('pdSalleId').value;
    const date  = document.getElementById('pdDate').value;
    const heure = document.getElementById('pdHeure').value;
    const diag  = document.getElementById('pdDiag').value.trim();
    const msg   = document.getElementById('pdMsg');
    const btn   = document.getElementById('pdSubmitBtn');

    if (!patId)  { showPdMsg('Veuillez sélectionner un patient.', 'error'); return; }
    if (!salle)  { showPdMsg('Veuillez choisir une salle.', 'error'); return; }
    if (!date || !heure) { showPdMsg('Date et heure obligatoires.', 'error'); return; }
    if (!diag)   { showPdMsg('Veuillez saisir le diagnostic / intervention prévue.', 'error'); return; }

    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Enregistrement…';

    const fd = new FormData();
    fd.append('patient_id',      patId);
    fd.append('salle_id',        salle);
    fd.append('date',            date);
    fd.append('heure',           heure);
    fd.append('diagnostic',      diag);
    fd.append('chirurgien_id',   document.getElementById('pdChirurgienId').value);
    fd.append('anesthesiste_id', document.getElementById('pdAnesthId').value);
    fd.append('inf_circulant_id',document.getElementById('pdInfCircId').value);
    fd.append('instrumentiste_id',document.getElementById('pdInstrId').value);
    fd.append('_csrf_token',     CSRF);

    fetch(BB + 'bloc/programmer-direct', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(d => {
            if (d.success) {
                showPdMsg('Intervention programmée avec succès !', 'success');
                btn.innerHTML = '<i class="bi bi-check2-all me-1"></i>Programmé !';
                setTimeout(() => { bootstrap.Modal.getInstance(document.getElementById('modalProgrammerDirect')).hide(); location.reload(); }, 1500);
            } else {
                showPdMsg(d.message || 'Erreur lors de la programmation.', 'error');
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-check2-circle me-1"></i>Programmer';
            }
        })
        .catch(() => { showPdMsg('Erreur réseau.', 'error'); btn.disabled = false; btn.innerHTML = '<i class="bi bi-check2-circle me-1"></i>Programmer'; });
}

function showPdMsg(text, type) {
    const el = document.getElementById('pdMsg');
    el.style.cssText = type === 'success'
        ? 'display:block;background:#dcfce7;color:#166534;border:1px solid #86efac;font-size:.83rem;font-weight:700;padding:10px 14px;border-radius:10px;'
        : 'display:block;background:#fee2e2;color:#991b1b;border:1px solid #fca5a5;font-size:.83rem;font-weight:700;padding:10px 14px;border-radius:10px;';
    el.innerHTML = (type === 'success' ? '<i class="bi bi-check-circle-fill me-2"></i>' : '<i class="bi bi-x-circle-fill me-2"></i>') + text;
}

/* Horloge */
(function tick(){
    const d=new Date();
    const p=n=>String(n).padStart(2,'0');
    document.getElementById('blocClock').textContent=p(d.getHours())+':'+p(d.getMinutes())+':'+p(d.getSeconds());
    setTimeout(tick,1000);
})();

/* Modal programmer */
const BB='<?= BASE_URL ?>';
const CSRF='<?= CsrfService::getToken() ?>';
function openProgModal(id,name){
    document.getElementById('prog_demande_id').value=id;
    document.getElementById('prog_patient_name').value=name;
    new bootstrap.Modal(document.getElementById('modalProgrammer')).show();
}

/* Menu statut salle */
let curSalle=null;
const sMenu=document.getElementById('salleMenu');
function ouvrirSalleMenu(btn,id,nom){
    curSalle=id;
    document.getElementById('salleMenuTitle').textContent=nom;
    const r=btn.getBoundingClientRect();
    sMenu.style.display='block';
    let l=r.right-sMenu.offsetWidth; if(l<8)l=8;
    sMenu.style.left=l+'px'; sMenu.style.top=(r.bottom+6)+'px';
}
function setSalle(st){
    if(!curSalle)return;
    const fd=new FormData();
    fd.append('salle_id',curSalle); fd.append('statut',st); fd.append('_csrf_token',CSRF);
    sMenu.style.display='none';
    fetch(BB+'bloc/changer-statut-salle',{method:'POST',body:fd})
        .then(r=>r.json())
        .then(j=>{ if(j.success) location.reload(); else alert(j.message||'Erreur'); })
        .catch(()=>alert('Erreur réseau'));
}
document.addEventListener('click',e=>{
    if(!e.target.closest('#salleMenu')&&!e.target.closest('.salle-gear-btn')) sMenu.style.display='none';
});

/* ── Modifier intervention ── */
function ouvrirModifier(data) {
    document.getElementById('modId').value    = data.id;
    document.getElementById('modPatient').textContent = data.patient;
    document.getElementById('modSalle').value = data.salle_id;
    document.getElementById('modDate').value  = data.date_intervention;
    document.getElementById('modHeure').value = data.heure_debut;
    document.getElementById('modDiag').value  = data.diagnostique_op;
    document.getElementById('modErr').classList.add('d-none');

    // Pré-remplir chirurgien et anesthésiste
    document.getElementById('modChir').value   = data.chirurgien_id   || '';
    document.getElementById('modAnesth').value = data.anesthesiste_id || '';

    // Pré-remplir infirmier circulant et instrumentiste depuis equipe_json
    document.getElementById('modInfCirc').value = '';
    document.getElementById('modInstr').value   = '';
    try {
        const equipe = typeof data.equipe_json === 'string' ? JSON.parse(data.equipe_json) : (data.equipe_json || []);
        equipe.forEach(m => {
            if (m.role === 'Infirmier circulant') document.getElementById('modInfCirc').value = m.id;
            if (m.role === 'Instrumentiste')       document.getElementById('modInstr').value   = m.id;
        });
    } catch(e) {}

    new bootstrap.Modal(document.getElementById('modalModifier')).show();
}

async function sauvegarderModif() {
    const btn = document.getElementById('modSaveBtn');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Enregistrement…';
    const body = '_csrf_token='         + encodeURIComponent(CSRF)
               + '&id='                 + encodeURIComponent(document.getElementById('modId').value)
               + '&salle_id='           + encodeURIComponent(document.getElementById('modSalle').value)
               + '&date_intervention='  + encodeURIComponent(document.getElementById('modDate').value)
               + '&heure_debut='        + encodeURIComponent(document.getElementById('modHeure').value)
               + '&diagnostique_op='    + encodeURIComponent(document.getElementById('modDiag').value)
               + '&chirurgien_id='      + encodeURIComponent(document.getElementById('modChir').value)
               + '&anesthesiste_id='    + encodeURIComponent(document.getElementById('modAnesth').value)
               + '&inf_circulant_id='   + encodeURIComponent(document.getElementById('modInfCirc').value)
               + '&instrumentiste_id='  + encodeURIComponent(document.getElementById('modInstr').value);
    try {
        const r = await fetch(BB + 'bloc/modifier', { method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body });
        const d = await r.json();
        if (d.ok) { location.reload(); }
        else {
            const el = document.getElementById('modErr');
            el.textContent = d.error || 'Erreur';
            el.classList.remove('d-none');
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-check2 me-1"></i>Enregistrer';
        }
    } catch(e) {
        document.getElementById('modErr').textContent = 'Erreur réseau';
        document.getElementById('modErr').classList.remove('d-none');
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-check2 me-1"></i>Enregistrer';
    }
}

/* ── Annuler intervention ── */
async function annulerInter(id, patient) {
    if (!confirm('Annuler l\'intervention de ' + patient + ' ?\nElle retournera en file d\'attente.')) return;
    try {
        const r = await fetch(BB + 'bloc/annuler', {
            method:'POST',
            headers:{'Content-Type':'application/x-www-form-urlencoded'},
            body: '_csrf_token=' + encodeURIComponent(CSRF) + '&id=' + id
        });
        const d = await r.json();
        if (d.ok) location.reload();
        else alert('Erreur : ' + (d.error || 'inconnue'));
    } catch(e) { alert('Erreur réseau'); }
}

function voirFiche(d) {
    // Subtitle
    document.getElementById('ficheSubtitle').textContent =
        d.patient + ' · ' + d.salle + ' · ' + d.heure;

    // Infos générales
    const infos = [
        ['Patient',       d.patient],
        ['Dossier',       d.dossier],
        ['Salle',         d.salle],
        ['Intervention',  d.inter],
        ['Heure prévue',  d.heure_op],
        ['Date fiche',    d.date_fiche],
        ['Heure ouverture', d.heure_ouv],
        ['Identité salle', d.salle_id],
        ['Groupe ON',     d.grpe_on],
        ['Groupe OFF',    d.grpe_off],
        ['Rempli par',    d.remplit_par],
        ['Heure soumis',  d.heure],
    ].filter(r => r[1]);
    document.getElementById('ficheInfos').innerHTML = infos.map(r =>
        `<div class="col-6 col-md-4">
            <div style="font-size:.65rem;font-weight:700;text-transform:uppercase;letter-spacing:.4px;color:#94a3b8;">${r[0]}</div>
            <div style="font-size:.82rem;font-weight:600;color:#1e293b;margin-top:2px;">${r[1]}</div>
        </div>`
    ).join('');

    // Checklist table
    const LABELS = {
        eclairage_ambiance:'Ambiance', eclairage_scialytique:'Scialytique',
        table_mouvements:'Mouvements', table_accessoires:'Accessoires (table op.)',
        bistouri_generateur:'Générateur', bistouri_plaque:'Plaque',
        bistouri_pedale:'Pédale', bistouri_cordon:'Cordon pédale',
        asp_generateur:'Générateur (aspiration)', asp_bocaux:'Bocaux', asp_accessoires:'Accessoires (bocal-générateur)',
        air_splits:'Splits',
        mat_ampli:'Ampli de brillance', mat_colone_video:'Colonne vidéo', mat_negatoscope:'Négatoscope',
    };
    const GROUPS = [
        {label:'Éclairages',        keys:['eclairage_ambiance','eclairage_scialytique']},
        {label:'Table d\'opération',keys:['table_mouvements','table_accessoires']},
        {label:'Bistouri électrique',keys:['bistouri_generateur','bistouri_plaque','bistouri_pedale','bistouri_cordon']},
        {label:'Système aspiration',keys:['asp_generateur','asp_bocaux','asp_accessoires']},
        {label:'Air',               keys:['air_splits']},
        {label:'Matériel spécifique',keys:['mat_ampli','mat_colone_video','mat_negatoscope']},
    ];
    const tbody = document.getElementById('ficheChecklist');
    tbody.innerHTML = '';
    const cl = d.checklist || {};
    const hasData = Object.keys(cl).length > 0;
    if (!hasData) {
        tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted py-3" style="font-size:.8rem;">Aucune donnée de checklist</td></tr>';
    } else {
        const badge = val => {
            if (!val) return '<span style="color:#cbd5e1;font-size:.72rem;">—</span>';
            const ok = val === 'oui';
            const color = ok?'#16a34a':'#dc2626', bg=ok?'#f0fdf4':'#fef2f2', bd=ok?'#86efac':'#fca5a5';
            return `<span style="background:${bg};color:${color};border:1.5px solid ${bd};font-size:.72rem;font-weight:700;padding:2px 8px;border-radius:7px;">${ok?'✓ Oui':'✗ Non'}</span>`;
        };
        GROUPS.forEach(g => {
            const hdr = document.createElement('tr');
            hdr.innerHTML = `<td colspan="5" style="background:#f0f4f8;font-weight:800;font-size:.78rem;color:#374151;padding:8px 12px;border:1px solid #e2e8f0;">
                <i class="bi bi-chevron-right me-1" style="font-size:.7rem;color:#6366f1"></i>${g.label}</td>`;
            tbody.appendChild(hdr);
            g.keys.forEach(k => {
                const item = cl[k] || {};
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td style="padding:7px 12px 7px 22px;font-size:.79rem;color:#475569;border:1px solid #e2e8f0;">— ${LABELS[k]||k}</td>
                    <td style="padding:7px 12px;text-align:center;border:1px solid #e2e8f0;">${badge(item.presence)}</td>
                    <td style="padding:7px 12px;text-align:center;border:1px solid #e2e8f0;">${badge(item.fonctionnalite)}</td>
                    <td style="padding:7px 12px;text-align:center;border:1px solid #e2e8f0;">${badge(item.proprete)}</td>
                    <td style="padding:7px 12px;font-size:.75rem;color:#64748b;border:1px solid #e2e8f0;">${item.obs||'—'}</td>`;
                tbody.appendChild(tr);
            });
        });
    }

    // Fin de session
    const fin = [];
    if (d.transmis)  fin.push(`<div><span style="font-size:.7rem;font-weight:700;text-transform:uppercase;color:#94a3b8;">Transmission arrêt groupe</span><br><span style="font-size:.8rem;color:#1e293b;">${d.transmis}</span></div>`);
    if (d.signature) fin.push(`<div><span style="font-size:.7rem;font-weight:700;text-transform:uppercase;color:#94a3b8;">Responsable</span><br><span style="font-size:.8rem;font-weight:600;color:#1e293b;">${d.signature}</span></div>`);
    if (d.anomalies > 0) fin.push(`<div style="margin-top:6px;"><span style="background:#fef2f2;color:#dc2626;border:1.5px solid #fecaca;font-size:.75rem;font-weight:700;padding:4px 12px;border-radius:20px;"><i class="bi bi-exclamation-triangle-fill me-1"></i>${d.anomalies} anomalie${d.anomalies>1?'s':''} détectée${d.anomalies>1?'s':''}</span></div>`);
    document.getElementById('ficheFinSession').innerHTML = fin.length
        ? `<p style="font-size:.7rem;font-weight:800;text-transform:uppercase;letter-spacing:.5px;color:#64748b;margin-bottom:10px;"><i class="bi bi-power me-1"></i>Fin de session</p><div style="display:flex;gap:24px;flex-wrap:wrap;">${fin.join('')}</div>`
        : '';

    new bootstrap.Modal(document.getElementById('modalFicheOuverture')).show();
}
</script>

<!-- ══ MODAL MODIFIER INTERVENTION ══ -->
<div class="modal fade" id="modalModifier" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" style="max-width:480px">
    <div class="modal-content border-0 shadow-lg" style="border-radius:20px;overflow:hidden">
      <div class="modal-header border-0 text-white" style="background:linear-gradient(135deg,#1e1b4b,#4f46e5);padding:20px 24px;">
        <div>
          <h5 class="modal-title fw-bold mb-0"><i class="bi bi-pencil-fill me-2"></i>Modifier l'intervention</h5>
          <p class="mb-0 fw-semibold" id="modPatient" style="opacity:.8;font-size:.82rem;margin-top:3px;"></p>
        </div>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-4">
        <input type="hidden" id="modId">
        <div class="row g-3 mb-3">
          <div class="col-6">
            <label class="form-label fw-semibold small text-muted">Date</label>
            <input type="date" class="form-control rounded-3" id="modDate">
          </div>
          <div class="col-6">
            <label class="form-label fw-semibold small text-muted">Heure</label>
            <input type="time" class="form-control rounded-3" id="modHeure">
          </div>
        </div>
        <div class="mb-3">
          <label class="form-label fw-semibold small text-muted">Salle d'opération</label>
          <select class="form-select rounded-3" id="modSalle">
            <?php foreach ($salles as $s): ?>
            <option value="<?= (int)$s['id'] ?>"><?= htmlspecialchars($s['nom_salle']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="mb-3">
          <label class="form-label fw-semibold small text-muted">Diagnostic / Acte prévu</label>
          <textarea class="form-control rounded-3" id="modDiag" rows="2"></textarea>
        </div>
        <hr class="my-3">
        <p class="fw-bold small text-uppercase text-muted mb-2" style="letter-spacing:.5px;"><i class="bi bi-people-fill me-1"></i>Équipe opératoire</p>
        <div class="row g-3 mb-3">
          <div class="col-6">
            <label class="form-label fw-semibold small text-muted">Chirurgien</label>
            <select class="form-select form-select-sm rounded-3" id="modChir">
              <option value="">— Non assigné —</option>
              <?php foreach ($medecins as $m): ?>
              <option value="<?= (int)$m['id'] ?>"><?= htmlspecialchars(strtoupper($m['nom']).' '.$m['prenom']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-6">
            <label class="form-label fw-semibold small text-muted">Anesthésiste</label>
            <select class="form-select form-select-sm rounded-3" id="modAnesth">
              <option value="">— Non assigné —</option>
              <?php foreach ($medecins as $m): ?>
              <option value="<?= (int)$m['id'] ?>"><?= htmlspecialchars(strtoupper($m['nom']).' '.$m['prenom']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="row g-3 mb-2">
          <div class="col-6">
            <label class="form-label fw-semibold small text-muted">Inf. circulant (bloc)</label>
            <select class="form-select form-select-sm rounded-3" id="modInfCirc">
              <option value="">— Non assigné —</option>
              <?php foreach ($infirmiers_bloc as $inf): ?>
              <option value="<?= (int)$inf['id'] ?>"><?= htmlspecialchars(strtoupper($inf['nom']).' '.$inf['prenom']) ?> <small>(<?= htmlspecialchars($inf['nom_service']) ?>)</small></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-6">
            <label class="form-label fw-semibold small text-muted">Instrumentiste (bloc)</label>
            <select class="form-select form-select-sm rounded-3" id="modInstr">
              <option value="">— Non assigné —</option>
              <?php foreach ($infirmiers_bloc as $inf): ?>
              <option value="<?= (int)$inf['id'] ?>"><?= htmlspecialchars(strtoupper($inf['nom']).' '.$inf['prenom']) ?> <small>(<?= htmlspecialchars($inf['nom_service']) ?>)</small></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div id="modErr" class="alert alert-danger d-none py-2 px-3 small mb-0"></div>
      </div>
      <div class="modal-footer border-0 pt-0 px-4 pb-4">
        <button class="btn btn-light rounded-pill px-4 fw-semibold" data-bs-dismiss="modal">Fermer</button>
        <button id="modSaveBtn" class="btn text-white rounded-pill px-5 fw-bold" onclick="sauvegarderModif()"
                style="background:linear-gradient(135deg,#4f46e5,#7c3aed);box-shadow:0 4px 14px rgba(79,70,229,.4);">
          <i class="bi bi-check2 me-1"></i>Enregistrer
        </button>
      </div>
    </div>
  </div>
</div>
<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
