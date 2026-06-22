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

$kpi                 = $kpi                 ?? ['patients'=>0,'soins_jour'=>0,'retards'=>0,'realises'=>0,'taux'=>0,'annules'=>0];
$nomService          = $nomService          ?? 'Service';
$soins_par_tranche   = $soins_par_tranche   ?? [];
$soins_alertes       = $soins_alertes       ?? [];
$activite_infirmiers = $activite_infirmiers ?? [];
$patients_service    = $patients_service    ?? [];
$equipe              = $equipe              ?? [];
$patients_a_installer= $patients_a_installer?? [];
$lits_libres         = $lits_libres         ?? [];

$icons_s  = ['REALISE'=>'bi-check-circle-fill','PLANIFIE'=>'bi-clock','RETARD'=>'bi-exclamation-triangle-fill','ANNULE'=>'bi-x-circle-fill'];
$labels_s = ['REALISE'=>'Réalisé','PLANIFIE'=>'Planifié','RETARD'=>'Retard','ANNULE'=>'Annulé'];
?>
<style>
    .sidebar { display:none !important; }
    main { margin-left:0 !important; width:100% !important; background:#f1f5f9; min-height:100vh; font-family:'Inter',system-ui,sans-serif; }

    /* ── Topbar ── */
    .nav-major {
        background:#1e293b; padding:12px 28px; color:white;
        box-shadow:0 4px 15px rgba(0,0,0,.15);
        position:sticky; top:0; z-index:1000;
        display:flex; align-items:center; justify-content:space-between; gap:16px;
    }
    .major-clock { font-family:monospace; font-size:1.5rem; font-weight:800; color:#34d399; }

    /* ── KPI Cards ── */
    .kpi-card {
        background:#fff; border-radius:16px; padding:18px 20px;
        box-shadow:0 2px 12px rgba(0,0,0,.06);
        border-left:5px solid transparent;
    }
    .kpi-card .kpi-val { font-size:2.1rem; font-weight:900; line-height:1; }
    .kpi-card .kpi-lbl { font-size:.7rem; text-transform:uppercase; font-weight:700; color:#64748b; letter-spacing:.8px; }
    .kpi-patients { border-color:#3b82f6; }
    .kpi-soins    { border-color:#8b5cf6; }
    .kpi-retards  { border-color:#f43f5e; }
    .kpi-realises { border-color:#10b981; }
    .kpi-annules  { border-color:#f59e0b; }
    .kpi-taux     { border-color:#0ea5e9; }
    .taux-bar  { height:6px; border-radius:99px; background:#e2e8f0; overflow:hidden; margin-top:6px; }
    .taux-fill { height:100%; border-radius:99px; background:linear-gradient(90deg,#10b981,#3b82f6); }

    /* ── Tabs ── */
    .major-tabs { display:flex; gap:2px; padding:0 20px; overflow-x:auto; white-space:nowrap; }
    .major-tab {
        padding:10px 18px; border:none; background:transparent;
        color:#64748b; font-weight:700; font-size:.83rem; cursor:pointer;
        border-bottom:3px solid transparent; transition:.2s; white-space:nowrap;
        display:inline-flex; align-items:center; gap:6px;
    }
    .major-tab.active { color:#1e293b; border-bottom-color:#3b82f6; }
    .major-tab-pane { display:none; }
    .major-tab-pane.active { display:block; }

    /* ── Soin Row ── */
    .soin-row {
        background:#fff; border-radius:12px; padding:12px 16px;
        display:flex; align-items:center; gap:10px; margin-bottom:7px;
        box-shadow:0 1px 4px rgba(0,0,0,.05); border-left:4px solid #e2e8f0;
        flex-wrap:wrap;
    }
    .soin-row.REALISE  { border-left-color:#10b981; }
    .soin-row.PLANIFIE { border-left-color:#3b82f6; }
    .soin-row.RETARD   { border-left-color:#f43f5e; background:#fff5f5; }
    .soin-row.ANNULE   { border-left-color:#f59e0b; background:#fffbeb; opacity:.8; }

    .statut-pill {
        display:inline-flex; align-items:center; gap:4px;
        padding:3px 10px; border-radius:20px; font-size:.68rem; font-weight:700; white-space:nowrap;
    }
    .pill-REALISE  { background:#d1fae5; color:#065f46; }
    .pill-PLANIFIE { background:#dbeafe; color:#1e40af; }
    .pill-RETARD   { background:#fee2e2; color:#991b1b; }
    .pill-ANNULE   { background:#fef3c7; color:#92400e; }

    /* ── Tranche header ── */
    .tranche-header {
        display:flex; align-items:center; gap:10px;
        padding:10px 18px; border-radius:12px; margin-bottom:10px; margin-top:18px;
        font-weight:800; font-size:.85rem; color:#fff;
    }
    .tranche-count { font-size:.73rem; background:rgba(255,255,255,.25); padding:2px 8px; border-radius:10px; }

    /* ── Cocher toggle ── */
    .cocher-toggle {
        width:28px; height:28px; border-radius:8px; border:2px solid #e2e8f0;
        background:#f8fafc; cursor:pointer; display:flex; align-items:center; justify-content:center;
        transition:.15s; flex-shrink:0;
    }
    .cocher-toggle.checked { background:#10b981; border-color:#10b981; color:#fff; }
    .cocher-toggle:hover { transform:scale(1.1); }

    /* ── Infirmier row ── */
    .inf-row { background:#fff; border-radius:12px; padding:16px 20px; box-shadow:0 1px 4px rgba(0,0,0,.05); margin-bottom:8px; }

    /* ── Patient card ── */
    .pat-card { background:#fff; border-radius:14px; padding:16px 20px; box-shadow:0 2px 10px rgba(0,0,0,.06); margin-bottom:10px; }
    .vital-chip {
        display:inline-flex; flex-direction:column; align-items:center;
        background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px;
        padding:5px 11px; min-width:68px;
    }
    .vital-chip .v-val { font-size:.93rem; font-weight:800; }
    .vital-chip .v-lbl { font-size:.58rem; color:#94a3b8; font-weight:600; text-transform:uppercase; }
    .vital-chip.alert-hi { border-color:#fca5a5; background:#fff5f5; }
    .vital-chip.alert-lo { border-color:#93c5fd; background:#eff6ff; }

    /* ── Installation card ── */
    .install-card {
        background:#fff; border-radius:14px; padding:16px 20px;
        box-shadow:0 2px 10px rgba(0,0,0,.06); margin-bottom:10px;
        border-left:5px solid #f59e0b;
    }

    /* ── Empty state ── */
    .empty-state { text-align:center; padding:40px; color:#94a3b8; }
    .empty-state i { font-size:2.5rem; margin-bottom:10px; display:block; }
</style>

<div class="nav-major cockpit-nav">
    <div class="d-flex align-items-center gap-3">
        <img src="<?= BASE_URL ?>public/images/logo_ordre_malte.png" alt="Logo" style="height:40px; filter:brightness(0) invert(1);">
        <div>
            <div class="fw-bold fs-5">Supervision Major</div>
            <div class="opacity-75" style="font-size:.8rem;"><?= htmlspecialchars($nomService) ?></div>
        </div>
    </div>
    <div class="d-flex align-items-center gap-3">
        <div class="major-clock" id="majorClock">00:00:00</div>
        <a href="<?= BASE_URL ?>dashboard?vue=infirmier" class="btn btn-sm btn-warning fw-bold rounded-pill px-3"
           title="Accéder à l'interface infirmier de votre service" style="font-size:.8rem;white-space:nowrap;">
            <i class="bi bi-heart-pulse-fill me-1"></i>Vue Infirmier
        </a>
        <button onclick="ouvrirProfilModal()" class="btn btn-outline-light rounded-circle p-2" title="Mon profil">
            <i class="bi bi-person-circle fs-5"></i>
        </button>
        <a href="<?= BASE_URL ?>logout" class="btn btn-outline-danger rounded-circle p-2" title="Déconnexion">
            <i class="bi bi-power fs-5"></i>
        </a>
    </div>
</div>

<div class="container-fluid p-4">

    <!-- ═══ KPI ═══ -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-4 col-xl-2">
            <div class="kpi-card kpi-patients">
                <div class="kpi-lbl"><i class="bi bi-people-fill me-1 text-primary"></i>Hospitalisés</div>
                <div class="kpi-val text-primary"><?= $kpi['patients'] ?></div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <div class="kpi-card kpi-soins">
                <div class="kpi-lbl"><i class="bi bi-clipboard2-pulse-fill me-1" style="color:#8b5cf6;"></i>Soins du jour</div>
                <div class="kpi-val" style="color:#8b5cf6;"><?= $kpi['soins_jour'] ?></div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <div class="kpi-card kpi-realises">
                <div class="kpi-lbl"><i class="bi bi-check-circle-fill me-1 text-success"></i>Réalisés</div>
                <div class="kpi-val text-success"><?= $kpi['realises'] ?></div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <div class="kpi-card kpi-retards">
                <div class="kpi-lbl"><i class="bi bi-exclamation-triangle-fill me-1 text-danger"></i>Retards</div>
                <div class="kpi-val text-danger"><?= $kpi['retards'] ?></div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <div class="kpi-card kpi-annules">
                <div class="kpi-lbl"><i class="bi bi-x-circle-fill me-1 text-warning"></i>Annulés</div>
                <div class="kpi-val text-warning"><?= $kpi['annules'] ?></div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <div class="kpi-card kpi-taux">
                <div class="kpi-lbl"><i class="bi bi-speedometer2 me-1 text-info"></i>Taux réalisation</div>
                <div class="kpi-val text-info"><?= $kpi['taux'] ?>%</div>
                <div class="taux-bar"><div class="taux-fill" style="width:<?= $kpi['taux'] ?>%;"></div></div>
            </div>
        </div>
    </div>

    <!-- ═══ TABS ═══ -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="card-header bg-white border-bottom px-0 pt-3 pb-0">
            <div class="major-tabs">
                <button class="major-tab active" onclick="switchTab('plan')" id="tab-plan">
                    <i class="bi bi-calendar2-check"></i> Plan du jour
                </button>
                <button class="major-tab" onclick="switchTab('alertes')" id="tab-alertes">
                    <i class="bi bi-bell-fill"></i> Alertes
                    <?php if ($kpi['retards'] + $kpi['annules'] > 0): ?>
                    <span class="badge bg-danger"><?= $kpi['retards'] + $kpi['annules'] ?></span>
                    <?php endif; ?>
                </button>
                <button class="major-tab" onclick="switchTab('equipe')" id="tab-equipe">
                    <i class="bi bi-person-lines-fill"></i> Équipe
                </button>
                <button class="major-tab" onclick="switchTab('patients')" id="tab-patients">
                    <i class="bi bi-heart-pulse-fill"></i> Patients
                </button>
                <button class="major-tab" onclick="switchTab('installation')" id="tab-installation">
                    <i class="bi bi-hospital"></i> Installation
                    <?php if (!empty($patients_a_installer)): ?>
                    <span class="badge bg-warning text-dark"><?= count($patients_a_installer) ?></span>
                    <?php endif; ?>
                </button>
            </div>
        </div>

        <div class="card-body p-4">

            <!-- ─── TAB 1 : Plan du jour ─── -->
            <div class="major-tab-pane active" id="pane-plan">
                <?php
                $colors_tranche = ['matin'=>'#f59e0b','apres_midi'=>'#3b82f6','nuit'=>'#6366f1'];
                $total_soins = 0;
                foreach ($soins_par_tranche as $key => $tranche):
                    if (empty($tranche['soins'])) continue;
                    $total_soins += count($tranche['soins']);
                ?>
                <div class="tranche-header" style="background:<?= $colors_tranche[$key] ?>;">
                    <i class="bi <?= $tranche['icon'] ?> fs-5"></i>
                    <?= htmlspecialchars($tranche['label']) ?>
                    <span class="tranche-count"><?= count($tranche['soins']) ?> soins</span>
                </div>
                <?php foreach ($tranche['soins'] as $soin):
                    $estFait = $soin['statut'] === 'REALISE';
                    $actionnable = in_array($soin['statut'], ['PLANIFIE','RETARD']);
                ?>
                <div class="soin-row <?= htmlspecialchars($soin['statut']) ?>" id="soin-row-<?= $soin['id'] ?>">
                    <!-- Cocher toggle -->
                    <?php if (!($soin['statut'] === 'ANNULE')): ?>
                    <button class="cocher-toggle <?= $estFait ? 'checked' : '' ?>"
                            onclick="cocherSoin(<?= $soin['id'] ?>, <?= $estFait ? 'false' : 'true' ?>)"
                            title="<?= $estFait ? 'Marquer non réalisé' : 'Marquer réalisé' ?>">
                        <i class="bi bi-check2 <?= $estFait ? '' : 'opacity-0' ?>"></i>
                    </button>
                    <?php else: ?>
                    <div style="width:28px; flex-shrink:0;"></div>
                    <?php endif; ?>

                    <div style="min-width:52px; text-align:center; flex-shrink:0;">
                        <div class="fw-bold text-muted" style="font-size:.78rem;"><?= date('H:i', strtotime($soin['date_prevue'])) ?></div>
                    </div>
                    <div style="flex:1; min-width:120px;">
                        <div class="fw-bold text-truncate" style="font-size:.88rem;">
                            <?= htmlspecialchars($soin['patient_nom'].' '.$soin['patient_prenom']) ?>
                            <span class="text-muted fw-normal ms-1" style="font-size:.75rem;"><?= htmlspecialchars($soin['dossier_numero']) ?></span>
                        </div>
                        <div class="text-muted" style="font-size:.78rem;">
                            <i class="bi bi-geo-alt me-1"></i><?= htmlspecialchars(($soin['nom_chambre'] ?? '?').' — '.($soin['nom_lit'] ?? '?')) ?>
                        </div>
                    </div>
                    <div style="min-width:130px; flex-shrink:0;">
                        <div class="fw-semibold" style="font-size:.83rem;"><?= htmlspecialchars($soin['type_soin']) ?></div>
                        <?php if ($soin['description']): ?>
                        <div class="text-muted text-truncate" style="font-size:.72rem; max-width:180px;"><?= htmlspecialchars($soin['description']) ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="text-muted" style="min-width:100px; font-size:.76rem; flex-shrink:0;">
                        <?php if ($soin['reassigne_nom']): ?>
                            <i class="bi bi-arrow-right-circle text-info"></i> <?= htmlspecialchars($soin['reassigne_nom']) ?>
                        <?php elseif ($soin['executant_nom']): ?>
                            <i class="bi bi-person-check text-success"></i> <?= htmlspecialchars($soin['executant_nom']) ?>
                        <?php elseif ($soin['planificateur_nom']): ?>
                            <i class="bi bi-person text-muted"></i> <?= htmlspecialchars($soin['planificateur_nom']) ?>
                        <?php endif; ?>
                    </div>
                    <span class="statut-pill pill-<?= $soin['statut'] ?>" id="pill-<?= $soin['id'] ?>">
                        <i class="bi <?= $icons_s[$soin['statut']] ?? 'bi-circle' ?>"></i>
                        <?= $labels_s[$soin['statut']] ?? $soin['statut'] ?>
                    </span>
                    <div class="d-flex gap-1 ms-auto flex-shrink-0">
                        <?php if ($actionnable): ?>
                        <button class="btn btn-sm btn-outline-secondary rounded-3 py-1 px-2" style="font-size:.7rem;"
                                title="Réassigner"
                                onclick="openReassign(<?= $soin['id'] ?>, '<?= htmlspecialchars(addslashes($soin['patient_nom'].' '.$soin['patient_prenom'])) ?>')">
                            <i class="bi bi-arrow-right-circle"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-danger rounded-3 py-1 px-2" style="font-size:.7rem;"
                                title="Rayer / Annuler"
                                onclick="openRayer(<?= $soin['id'] ?>, '<?= htmlspecialchars(addslashes($soin['patient_nom'].' '.$soin['patient_prenom'])) ?>')">
                            <i class="bi bi-x-circle"></i>
                        </button>
                        <?php endif; ?>
                        <button class="btn btn-sm btn-outline-warning rounded-3 py-1 px-2" style="font-size:.7rem;"
                                title="Annoter"
                                onclick="openAnnoter(<?= $soin['id'] ?>, '<?= htmlspecialchars(addslashes($soin['note_major'] ?? '')) ?>')">
                            <i class="bi bi-pencil-square"></i>
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php endforeach; ?>
                <?php if ($total_soins === 0): ?>
                <div class="empty-state"><i class="bi bi-calendar2-x"></i>Aucun soin planifié pour aujourd'hui.</div>
                <?php endif; ?>
            </div>

            <!-- ─── TAB 2 : Alertes ─── -->
            <div class="major-tab-pane" id="pane-alertes">
                <?php if (empty($soins_alertes)): ?>
                <div class="empty-state"><i class="bi bi-check2-all text-success"></i>Aucune alerte active. Tout est en ordre.</div>
                <?php else: ?>
                <?php foreach ($soins_alertes as $a): ?>
                <div class="soin-row <?= htmlspecialchars($a['statut']) ?>">
                    <div style="min-width:56px; text-align:center; flex-shrink:0;">
                        <div class="fw-bold" style="font-size:.78rem;color:#f43f5e;"><?= date('H:i', strtotime($a['date_prevue'])) ?></div>
                        <div style="font-size:.62rem;color:#94a3b8;"><?= date('d/m', strtotime($a['date_prevue'])) ?></div>
                    </div>
                    <div style="flex:1; min-width:120px;">
                        <div class="fw-bold text-truncate" style="font-size:.88rem;">
                            <?= htmlspecialchars($a['patient_nom'].' '.$a['patient_prenom']) ?>
                        </div>
                        <div class="text-muted" style="font-size:.76rem;">
                            <i class="bi bi-geo-alt me-1"></i><?= htmlspecialchars(($a['nom_chambre'] ?? '?').' — '.($a['nom_lit'] ?? '?')) ?>
                        </div>
                    </div>
                    <div style="min-width:130px; flex-shrink:0;">
                        <div class="fw-semibold" style="font-size:.83rem;"><?= htmlspecialchars($a['type_soin']) ?></div>
                    </div>
                    <span class="statut-pill pill-<?= $a['statut'] ?>">
                        <i class="bi <?= $icons_s[$a['statut']] ?? 'bi-circle' ?>"></i>
                        <?= $labels_s[$a['statut']] ?? $a['statut'] ?>
                    </span>
                    <div class="d-flex gap-1 ms-auto flex-shrink-0">
                        <button class="btn btn-sm btn-outline-primary rounded-3 py-1 px-2" style="font-size:.72rem;"
                                onclick="openReassign(<?= $a['id'] ?>, '<?= htmlspecialchars(addslashes($a['patient_nom'].' '.$a['patient_prenom'])) ?>')">
                            <i class="bi bi-arrow-right-circle me-1"></i>Réassigner
                        </button>
                        <button class="btn btn-sm btn-outline-secondary rounded-3 py-1 px-2" style="font-size:.72rem;"
                                onclick="openAnnoter(<?= $a['id'] ?>, '<?= htmlspecialchars(addslashes($a['note_major'] ?? '')) ?>')">
                            <i class="bi bi-pencil-square"></i>
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- ─── TAB 3 : Équipe ─── -->
            <div class="major-tab-pane" id="pane-equipe">
                <?php if (empty($activite_infirmiers)): ?>
                <div class="empty-state"><i class="bi bi-people"></i>Aucune activité enregistrée pour l'équipe aujourd'hui.</div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr class="small text-uppercase text-muted">
                                <th>Infirmier</th>
                                <th class="text-center">Total</th>
                                <th class="text-center text-success">Réalisés</th>
                                <th class="text-center text-primary">En attente</th>
                                <th class="text-center text-danger">Retards</th>
                                <th class="text-center text-warning">Annulés</th>
                                <th>Performance</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($activite_infirmiers as $inf):
                            $perf = ($inf['total'] > 0) ? round($inf['realises'] / $inf['total'] * 100) : 0;
                            $perf_color = $perf >= 80 ? '#10b981' : ($perf >= 50 ? '#f59e0b' : '#f43f5e');
                        ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div style="width:34px;height:34px;border-radius:8px;background:#3b82f6;color:#fff;font-weight:700;font-size:.8rem;display:flex;align-items:center;justify-content:center;">
                                        <?= strtoupper(substr($inf['nom'],0,1).substr($inf['prenom'],0,1)) ?>
                                    </div>
                                    <div>
                                        <div class="fw-bold" style="font-size:.88rem;"><?= htmlspecialchars($inf['nom'].' '.$inf['prenom']) ?></div>
                                        <div style="font-size:.7rem;color:#94a3b8;"><?= $inf['role'] ?></div>
                                    </div>
                                </div>
                            </td>
                            <td class="text-center fw-bold"><?= (int)$inf['total'] ?></td>
                            <td class="text-center fw-bold text-success"><?= (int)$inf['realises'] ?></td>
                            <td class="text-center fw-bold text-primary"><?= (int)$inf['en_attente'] ?></td>
                            <td class="text-center fw-bold text-danger"><?= (int)$inf['retards'] ?></td>
                            <td class="text-center fw-bold text-warning"><?= (int)$inf['annules'] ?></td>
                            <td style="min-width:120px;">
                                <div class="d-flex align-items-center gap-2">
                                    <div style="flex:1; height:8px; border-radius:99px; background:#e2e8f0; overflow:hidden;">
                                        <div style="width:<?= $perf ?>%; height:100%; background:<?= $perf_color ?>; border-radius:99px;"></div>
                                    </div>
                                    <span style="font-size:.78rem; font-weight:800; color:<?= $perf_color ?>;"><?= $perf ?>%</span>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>

            <!-- ─── TAB 4 : Patients ─── -->
            <div class="major-tab-pane" id="pane-patients">
                <?php if (empty($patients_service)): ?>
                <div class="empty-state"><i class="bi bi-person-x"></i>Aucun patient hospitalisé dans ce service.</div>
                <?php else: ?>
                <?php foreach ($patients_service as $p):
                    $age_p   = $p['date_naissance'] ? (int)date_diff(date_create($p['date_naissance']), date_create())->y : null;
                    $duree   = $p['date_admission']  ? (int)date_diff(date_create($p['date_admission']), date_create())->days : 0;
                    $s_total = ($p['soins_restants'] ?? 0) + ($p['soins_faits'] ?? 0);
                    $prog_p  = $s_total > 0 ? round($p['soins_faits'] / $s_total * 100) : 0;
                    $ta_alert   = ($p['ta_sys']      && ($p['ta_sys']      > 160 || $p['ta_sys']      < 90))  ? 'alert-hi' : '';
                    $fc_alert   = ($p['fc']          && ($p['fc']          > 100 || $p['fc']          < 50))  ? ($p['fc'] > 100 ? 'alert-hi' : 'alert-lo') : '';
                    $spo2_alert = ($p['spo2']        && $p['spo2']         < 95)                              ? 'alert-lo' : '';
                    $temp_alert = ($p['temperature'] && ($p['temperature'] > 38.5 || $p['temperature'] < 36)) ? 'alert-hi' : '';
                ?>
                <div class="pat-card">
                    <div class="d-flex align-items-start gap-3 flex-wrap">
                        <div style="min-width:170px; flex:1;">
                            <div class="fw-bold fs-6"><?= htmlspecialchars($p['nom'].' '.$p['prenom']) ?></div>
                            <div class="text-muted" style="font-size:.76rem;">
                                <?= htmlspecialchars($p['dossier_numero']) ?><?php if ($age_p !== null): ?> • <?= $age_p ?> ans<?php endif; ?>
                            </div>
                            <div class="mt-1" style="font-size:.8rem;">
                                <i class="bi bi-geo-alt me-1 text-primary"></i>
                                <strong><?= htmlspecialchars($p['nom_chambre'] ?? '—') ?></strong> — <?= htmlspecialchars($p['nom_lit'] ?? '—') ?>
                            </div>
                            <div class="text-muted" style="font-size:.72rem;">
                                Admis il y a <?= $duree ?> j<?php if ($p['medecin_responsable']): ?> • Dr. <?= htmlspecialchars($p['medecin_responsable']) ?><?php endif; ?>
                            </div>
                        </div>
                        <div class="d-flex flex-wrap gap-2 align-items-center">
                            <?php if ($p['temperature']): ?>
                            <div class="vital-chip <?= $temp_alert ?>"><span class="v-val"><?= number_format($p['temperature'],1) ?>°</span><span class="v-lbl">Temp</span></div>
                            <?php endif; ?>
                            <?php if ($p['ta_sys'] && $p['ta_dia']): ?>
                            <div class="vital-chip <?= $ta_alert ?>"><span class="v-val"><?= $p['ta_sys'] ?>/<?= $p['ta_dia'] ?></span><span class="v-lbl">TA</span></div>
                            <?php endif; ?>
                            <?php if ($p['fc']): ?>
                            <div class="vital-chip <?= $fc_alert ?>"><span class="v-val"><?= $p['fc'] ?></span><span class="v-lbl">FC bpm</span></div>
                            <?php endif; ?>
                            <?php if ($p['spo2']): ?>
                            <div class="vital-chip <?= $spo2_alert ?>"><span class="v-val"><?= $p['spo2'] ?>%</span><span class="v-lbl">SpO2</span></div>
                            <?php endif; ?>
                            <?php if ($p['fr']): ?>
                            <div class="vital-chip"><span class="v-val"><?= $p['fr'] ?></span><span class="v-lbl">FR</span></div>
                            <?php endif; ?>
                            <?php if ($p['glycemie']): ?>
                            <div class="vital-chip"><span class="v-val"><?= $p['glycemie'] ?></span><span class="v-lbl">Glyc.</span></div>
                            <?php endif; ?>
                            <?php if (!$p['temperature'] && !$p['ta_sys'] && !$p['fc'] && !$p['spo2']): ?>
                            <span class="text-muted" style="font-size:.78rem;"><i class="bi bi-dash-circle me-1"></i>Pas de constantes</span>
                            <?php endif; ?>
                        </div>
                        <div style="min-width:130px; text-align:center; flex-shrink:0;">
                            <div style="font-size:.68rem; text-transform:uppercase; color:#94a3b8; font-weight:700;">Soins du jour</div>
                            <div class="fw-bold" style="font-size:1.1rem;">
                                <span class="text-success"><?= (int)$p['soins_faits'] ?></span>
                                <span class="text-muted">/<?= $s_total ?></span>
                            </div>
                            <?php if ($s_total > 0): ?>
                            <div style="height:6px; border-radius:99px; background:#e2e8f0; margin-top:4px; overflow:hidden;">
                                <div style="width:<?= $prog_p ?>%; height:100%; background:#10b981; border-radius:99px;"></div>
                            </div>
                            <?php endif; ?>
                        </div>
                        <!-- Actions -->
                        <div class="d-flex flex-column gap-2 flex-shrink-0">
                            <button class="btn btn-sm btn-primary rounded-3 fw-semibold"
                                    onclick="openPlanifier(<?= $p['hosp_id'] ?>, <?= $p['id'] ?>, '<?= htmlspecialchars(addslashes($p['nom'].' '.$p['prenom'])) ?>')"
                                    style="font-size:.75rem;">
                                <i class="bi bi-plus-circle me-1"></i>Planifier soin
                            </button>
                            <a href="<?= BASE_URL ?>prescription/par-ordre?patient_id=<?= $p['id'] ?>"
                               class="btn btn-sm rounded-3 fw-semibold"
                               style="font-size:.75rem;background:#f59e0b;color:#fff;border:none;">
                                <i class="bi bi-pen-fill me-1"></i>Ordonnance P/O
                            </a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- ─── TAB 5 : Installation ─── -->
            <div class="major-tab-pane" id="pane-installation">
                <?php if (empty($patients_a_installer)): ?>
                <div class="empty-state"><i class="bi bi-hospital text-success"></i>Aucun patient en attente d'installation.</div>
                <?php else: ?>
                <div class="alert alert-warning border-0 rounded-3 mb-4">
                    <i class="bi bi-info-circle me-2"></i>
                    <strong><?= count($patients_a_installer) ?> patient(s)</strong> ont été orientés vers ce service et attendent d'être installés sur un lit.
                </div>
                <?php foreach ($patients_a_installer as $pi):
                    $age_i = $pi['date_naissance'] ? (int)date_diff(date_create($pi['date_naissance']), date_create())->y : null;
                ?>
                <div class="install-card">
                    <div class="d-flex align-items-center gap-3 flex-wrap">
                        <div style="flex:1; min-width:160px;">
                            <div class="fw-bold fs-6"><?= htmlspecialchars($pi['nom'].' '.$pi['prenom']) ?></div>
                            <div class="text-muted" style="font-size:.76rem;">
                                <?= htmlspecialchars($pi['dossier_numero']) ?><?php if ($age_i !== null): ?> • <?= $age_i ?> ans<?php endif; ?>
                                <?php if ($pi['sexe']): ?> • <?= $pi['sexe'] ?><?php endif; ?>
                            </div>
                            <?php if ($pi['medecin_nom']): ?>
                            <div style="font-size:.78rem; margin-top:4px;">
                                <i class="bi bi-person-fill text-primary me-1"></i>Dr. <?= htmlspecialchars($pi['medecin_nom']) ?>
                            </div>
                            <?php endif; ?>
                        </div>
                        <div>
                            <span class="badge bg-warning text-dark px-3 py-2 rounded-pill">
                                <i class="bi bi-hourglass-split me-1"></i>En attente d'installation
                            </span>
                        </div>
                        <div class="ms-auto flex-shrink-0">
                            <?php if (!empty($lits_libres)): ?>
                            <button class="btn btn-success fw-bold rounded-3"
                                    onclick="openInstaller(<?= $pi['id'] ?>, '<?= htmlspecialchars(addslashes($pi['nom'].' '.$pi['prenom'])) ?>')">
                                <i class="bi bi-hospital me-2"></i>Installer sur un lit
                            </button>
                            <?php else: ?>
                            <span class="badge bg-danger px-3 py-2 rounded-pill">
                                <i class="bi bi-x-circle me-1"></i>Aucun lit disponible
                            </span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>

                <!-- Résumé des lits -->
                <div class="mt-4">
                    <h6 class="fw-bold text-muted mb-3 small text-uppercase">
                        <i class="bi bi-hospital me-2"></i>Lits disponibles — <?= count($lits_libres) ?> libre(s)
                    </h6>
                    <?php if (empty($lits_libres)): ?>
                    <p class="text-muted"><i class="bi bi-dash-circle me-1"></i>Tous les lits du service sont occupés.</p>
                    <?php else: ?>
                    <div class="d-flex flex-wrap gap-2">
                        <?php foreach ($lits_libres as $lit): ?>
                        <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-3 py-2 rounded-3" style="font-size:.78rem;">
                            <i class="bi bi-hospital me-1"></i><?= htmlspecialchars($lit['nom_chambre'].' — '.$lit['nom_lit']) ?>
                        </span>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- ═══ MODAL : Réassignation ═══ -->
<div class="modal fade" id="modalReassign" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold"><i class="bi bi-arrow-right-circle me-2 text-primary"></i>Réassigner le soin</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted mb-3" id="reassignPatientName"></p>
                <input type="hidden" id="reassignSoinId">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Assigner à</label>
                    <select class="form-select" id="reassignInfirmier">
                        <option value="">— Choisir un infirmier —</option>
                        <?php foreach ($equipe as $inf_e): ?>
                        <option value="<?= $inf_e['id'] ?>"><?= htmlspecialchars($inf_e['nom'].' '.$inf_e['prenom']) ?> (<?= $inf_e['role'] ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Note (optionnelle)</label>
                    <textarea class="form-control" id="reassignNote" rows="2" placeholder="Motif…"></textarea>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button class="btn btn-secondary rounded-3" data-bs-dismiss="modal">Annuler</button>
                <button class="btn btn-primary rounded-3 fw-bold" onclick="submitReassign()">
                    <i class="bi bi-check2 me-1"></i>Confirmer
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ═══ MODAL : Rayer / Annuler ═══ -->
<div class="modal fade" id="modalRayer" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold"><i class="bi bi-x-circle me-2 text-danger"></i>Rayer le soin</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted mb-3" id="rayerPatientName"></p>
                <input type="hidden" id="rayerSoinId">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Motif de suppression <span class="text-danger">*</span></label>
                    <textarea class="form-control" id="rayerMotif" rows="2" placeholder="Ex: Patient transféré, contre-indication…" required></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Correction / Remplacement (optionnel)</label>
                    <input type="text" class="form-control" id="rayerCorrection" placeholder="Nouveau soin à planifier à la place…">
                </div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button class="btn btn-secondary rounded-3" data-bs-dismiss="modal">Annuler</button>
                <button class="btn btn-danger rounded-3 fw-bold" onclick="submitRayer()">
                    <i class="bi bi-x-circle me-1"></i>Rayer ce soin
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ═══ MODAL : Annotation ═══ -->
<div class="modal fade" id="modalAnnoter" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold"><i class="bi bi-pencil-square me-2 text-warning"></i>Annotation Major</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="annoterSoinId">
                <textarea class="form-control" id="annoterNote" rows="4" placeholder="Votre note de supervision…"></textarea>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button class="btn btn-secondary rounded-3" data-bs-dismiss="modal">Annuler</button>
                <button class="btn btn-warning rounded-3 fw-bold" onclick="submitAnnoter()">
                    <i class="bi bi-save me-1"></i>Enregistrer
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ═══ MODAL : Planifier un soin ═══ -->
<div class="modal fade" id="modalPlanifier" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold"><i class="bi bi-plus-circle me-2 text-primary"></i>Planifier un soin</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="fw-semibold text-primary mb-3" id="planifierPatientName"></p>
                <input type="hidden" id="planifierAdmId">
                <input type="hidden" id="planifierPatId">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Type de soin <span class="text-danger">*</span></label>
                        <select class="form-select" id="planifierType">
                            <option value="">— Choisir —</option>
                            <option value="Prise de constantes">Prise de constantes</option>
                            <option value="Administration médicament">Administration médicament</option>
                            <option value="Changement perfusion">Changement perfusion</option>
                            <option value="Pansement">Pansement</option>
                            <option value="Injection IV">Injection IV</option>
                            <option value="Injection IM">Injection IM</option>
                            <option value="Injection SC">Injection SC</option>
                            <option value="Sondage urinaire">Sondage urinaire</option>
                            <option value="Aspiration trachéale">Aspiration trachéale</option>
                            <option value="Bilan sanguin">Bilan sanguin</option>
                            <option value="Nursing">Nursing</option>
                            <option value="Mobilisation">Mobilisation</option>
                            <option value="Alimentation assistée">Alimentation assistée</option>
                            <option value="Autre">Autre</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Date et heure prévues <span class="text-danger">*</span></label>
                        <input type="datetime-local" class="form-control" id="planifierDate">
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">Description / Détails</label>
                        <textarea class="form-control" id="planifierDesc" rows="2" placeholder="Ex: Amoxicilline 500mg IV, renouvellement J3…"></textarea>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">Condition d'application</label>
                        <input type="text" class="form-control" id="planifierCondition" placeholder="Ex: Si température > 38.5°C, Si douleur EVA > 5…">
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button class="btn btn-secondary rounded-3" data-bs-dismiss="modal">Annuler</button>
                <button class="btn btn-primary rounded-3 fw-bold" onclick="submitPlanifier()">
                    <i class="bi bi-calendar-plus me-1"></i>Planifier
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ═══ MODAL : Installer sur un lit ═══ -->
<div class="modal fade" id="modalInstaller" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold"><i class="bi bi-hospital me-2 text-success"></i>Installer le patient</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="fw-semibold text-primary mb-3" id="installerPatientName"></p>
                <input type="hidden" id="installerPatId">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Lit d'accueil <span class="text-danger">*</span></label>
                    <select class="form-select" id="installerLit">
                        <option value="">— Choisir un lit disponible —</option>
                        <?php foreach ($lits_libres as $lit): ?>
                        <option value="<?= $lit['id'] ?>"><?= htmlspecialchars($lit['nom_chambre'].' — '.$lit['nom_lit']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Motif d'hospitalisation</label>
                    <textarea class="form-control" id="installerMotif" rows="2" placeholder="Ex: Observation post-op, Pneumonie bactérienne…"></textarea>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button class="btn btn-secondary rounded-3" data-bs-dismiss="modal">Annuler</button>
                <button class="btn btn-success rounded-3 fw-bold" onclick="submitInstaller()">
                    <i class="bi bi-hospital me-1"></i>Confirmer l'installation
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ═══ Toast feedback ═══ -->
<div class="position-fixed bottom-0 end-0 p-3" style="z-index:9999;">
    <div id="majorToast" class="toast align-items-center border-0" role="alert">
        <div class="d-flex">
            <div class="toast-body fw-semibold" id="majorToastMsg"></div>
            <button type="button" class="btn-close me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    </div>
</div>

<script>
const BASE_URL_JS = '<?= BASE_URL ?>';

// ── Horloge ──
(function tick() {
    document.getElementById('majorClock').textContent = new Date().toLocaleTimeString('fr-FR');
    setTimeout(tick, 1000);
})();

// ── Toast helper ──
function showToast(msg, ok = true) {
    const el = document.getElementById('majorToast');
    el.className = 'toast align-items-center border-0 text-white ' + (ok ? 'bg-success' : 'bg-danger');
    document.getElementById('majorToastMsg').textContent = msg;
    bootstrap.Toast.getOrCreateInstance(el, { delay: 3000 }).show();
}

// ── Tabs ──
function switchTab(tab) {
    document.querySelectorAll('.major-tab').forEach(b => b.classList.remove('active'));
    document.querySelectorAll('.major-tab-pane').forEach(p => p.classList.remove('active'));
    document.getElementById('tab-' + tab).classList.add('active');
    document.getElementById('pane-' + tab).classList.add('active');
}

// ── Cocher / Décocher soin ──
function cocherSoin(soinId, cocher) {
    fetch(BASE_URL_JS + 'hospitalisation/cocher-soin', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ soin_id: soinId, cocher: cocher })
    })
    .then(r => r.json())
    .then(d => {
        if (d.success) {
            // Mettre à jour visuellement sans rechargement
            const row = document.getElementById('soin-row-' + soinId);
            const btn = row ? row.querySelector('.cocher-toggle') : null;
            const pill = document.getElementById('pill-' + soinId);
            if (cocher) {
                row?.classList.remove('PLANIFIE','RETARD'); row?.classList.add('REALISE');
                btn?.classList.add('checked');
                btn?.querySelector('i')?.classList.remove('opacity-0');
                btn?.setAttribute('onclick', 'cocherSoin('+soinId+', false)');
                if (pill) { pill.className = 'statut-pill pill-REALISE'; pill.innerHTML = '<i class="bi bi-check-circle-fill"></i> Réalisé'; }
            } else {
                row?.classList.remove('REALISE'); row?.classList.add('PLANIFIE');
                btn?.classList.remove('checked');
                btn?.querySelector('i')?.classList.add('opacity-0');
                btn?.setAttribute('onclick', 'cocherSoin('+soinId+', true)');
                if (pill) { pill.className = 'statut-pill pill-PLANIFIE'; pill.innerHTML = '<i class="bi bi-clock"></i> Planifié'; }
            }
            showToast(cocher ? 'Soin marqué réalisé.' : 'Soin remis en planifié.');
        } else { showToast('Erreur lors de la mise à jour.', false); }
    })
    .catch(() => showToast('Erreur réseau.', false));
}

// ── Réassignation ──
function openReassign(soinId, patientName) {
    document.getElementById('reassignSoinId').value = soinId;
    document.getElementById('reassignPatientName').textContent = 'Patient : ' + patientName;
    document.getElementById('reassignNote').value = '';
    document.getElementById('reassignInfirmier').value = '';
    new bootstrap.Modal(document.getElementById('modalReassign')).show();
}
function submitReassign() {
    const soinId      = document.getElementById('reassignSoinId').value;
    const infirmierId = document.getElementById('reassignInfirmier').value;
    const note        = document.getElementById('reassignNote').value;
    if (!infirmierId) { showToast('Veuillez choisir un infirmier.', false); return; }
    fetch(BASE_URL_JS + 'major/reassigner', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ soin_id: soinId, infirmier_id: infirmierId, note })
    })
    .then(r => r.json())
    .then(d => {
        if (d.success) {
            bootstrap.Modal.getInstance(document.getElementById('modalReassign')).hide();
            showToast('Soin réassigné avec succès.');
            setTimeout(() => location.reload(), 1200);
        } else { showToast(d.message || 'Erreur.', false); }
    });
}

// ── Rayer ──
function openRayer(soinId, patientName) {
    document.getElementById('rayerSoinId').value = soinId;
    document.getElementById('rayerPatientName').textContent = 'Patient : ' + patientName;
    document.getElementById('rayerMotif').value = '';
    document.getElementById('rayerCorrection').value = '';
    new bootstrap.Modal(document.getElementById('modalRayer')).show();
}
function submitRayer() {
    const soinId     = document.getElementById('rayerSoinId').value;
    const motif      = document.getElementById('rayerMotif').value.trim();
    const correction = document.getElementById('rayerCorrection').value.trim();
    if (!motif) { showToast('Le motif est obligatoire.', false); return; }
    fetch(BASE_URL_JS + 'hospitalisation/rayer-soin', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ soin_id: soinId, motif, correction })
    })
    .then(r => r.json())
    .then(d => {
        if (d.success) {
            bootstrap.Modal.getInstance(document.getElementById('modalRayer')).hide();
            showToast('Soin rayé.');
            setTimeout(() => location.reload(), 1200);
        } else { showToast(d.message || 'Erreur.', false); }
    });
}

// ── Annotation ──
function openAnnoter(soinId, existingNote) {
    document.getElementById('annoterSoinId').value = soinId;
    document.getElementById('annoterNote').value = existingNote;
    new bootstrap.Modal(document.getElementById('modalAnnoter')).show();
}
function submitAnnoter() {
    const soinId = document.getElementById('annoterSoinId').value;
    const note   = document.getElementById('annoterNote').value;
    fetch(BASE_URL_JS + 'major/annoter', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ soin_id: soinId, note })
    })
    .then(r => r.json())
    .then(d => {
        if (d.success) {
            bootstrap.Modal.getInstance(document.getElementById('modalAnnoter')).hide();
            showToast('Annotation enregistrée.');
        } else { showToast(d.message || 'Erreur.', false); }
    });
}

// ── Planifier soin ──
function openPlanifier(admId, patId, patientName) {
    document.getElementById('planifierAdmId').value  = admId;
    document.getElementById('planifierPatId').value  = patId;
    document.getElementById('planifierPatientName').textContent = patientName;
    document.getElementById('planifierType').value = '';
    document.getElementById('planifierDesc').value = '';
    document.getElementById('planifierCondition').value = '';
    // Pré-remplir avec maintenant + 1h
    const now = new Date(); now.setHours(now.getHours() + 1, 0, 0, 0);
    document.getElementById('planifierDate').value = now.toISOString().slice(0,16);
    new bootstrap.Modal(document.getElementById('modalPlanifier')).show();
}
function submitPlanifier() {
    const admId     = document.getElementById('planifierAdmId').value;
    const patId     = document.getElementById('planifierPatId').value;
    const type_soin = document.getElementById('planifierType').value;
    const date_prevue = document.getElementById('planifierDate').value;
    const description = document.getElementById('planifierDesc').value;
    const condition   = document.getElementById('planifierCondition').value;
    if (!type_soin)   { showToast('Choisissez un type de soin.', false); return; }
    if (!date_prevue) { showToast('Indiquez la date prévue.', false); return; }

    const fd = new FormData();
    fd.append('admission_id', admId);
    fd.append('patient_id',   patId);
    fd.append('type_soin',    type_soin);
    fd.append('date_prevue',  date_prevue);
    fd.append('description',  description);
    fd.append('condition_application', condition);

    fetch(BASE_URL_JS + 'hospitalisation/add-soin', { method: 'POST', body: fd })
    .then(r => {
        if (r.ok || r.redirected) {
            bootstrap.Modal.getInstance(document.getElementById('modalPlanifier')).hide();
            showToast('Soin planifié avec succès !');
            setTimeout(() => location.reload(), 1200);
        } else { showToast('Erreur lors de la planification.', false); }
    })
    .catch(() => showToast('Erreur réseau.', false));
}

// ── Installer patient ──
function openInstaller(patId, patientName) {
    document.getElementById('installerPatId').value = patId;
    document.getElementById('installerPatientName').textContent = patientName;
    document.getElementById('installerLit').value = '';
    document.getElementById('installerMotif').value = '';
    new bootstrap.Modal(document.getElementById('modalInstaller')).show();
}
function submitInstaller() {
    const patId = document.getElementById('installerPatId').value;
    const litId = document.getElementById('installerLit').value;
    const motif = document.getElementById('installerMotif').value;
    if (!litId) { showToast('Choisissez un lit disponible.', false); return; }

    fetch(BASE_URL_JS + 'lits/confirmer-admission', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ patient_id: patId, lit_id: litId, motif_hospitalisation: motif })
    })
    .then(r => r.json())
    .then(d => {
        if (d.success) {
            bootstrap.Modal.getInstance(document.getElementById('modalInstaller')).hide();
            showToast('Patient installé avec succès !');
            setTimeout(() => location.reload(), 1500);
        } else { showToast(d.message || 'Erreur lors de l\'installation.', false); }
    })
    .catch(() => showToast('Erreur réseau.', false));
}
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
