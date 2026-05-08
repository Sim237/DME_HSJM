<?php
// ── Sécurité : accès réservé au Major ─────────────────────────────────────
if (!isset($_SESSION['logged_in']) || !in_array($_SESSION['user_role'] ?? '', ['MAJOR','ADMIN','ADMINISTRATEUR'])) {
    header('Location: ' . BASE_URL . 'login'); exit;
}
$majorNom    = ($_SESSION['user_prenom'] ?? '') . ' ' . ($_SESSION['user_nom'] ?? '');
$majorNom    = trim($majorNom) ?: 'Major';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Supervision · <?= htmlspecialchars($nomService) ?></title>
<link rel="stylesheet" href="<?= BASE_URL ?>public/css/bootstrap.min.css">
<link rel="stylesheet" href="<?= BASE_URL ?>public/css/bootstrap-icons.css">
<style>
:root {
    --maj-blue:   #0d6efd;
    --maj-teal:   #0891b2;
    --maj-amber:  #f59e0b;
    --maj-red:    #ef4444;
    --maj-green:  #22c55e;
    --maj-purple: #7c3aed;
    --maj-bg:     #f0f4f8;
}
* { box-sizing: border-box; }
body { background: var(--maj-bg); font-family: 'Segoe UI', sans-serif; min-height: 100vh; }

/* ── HEADER ── */
.maj-header {
    background: linear-gradient(135deg, #1e3a5f 0%, #0d6efd 100%);
    color: white; padding: 18px 28px;
    display: flex; align-items: center; justify-content: space-between;
    position: sticky; top: 0; z-index: 1030;
    box-shadow: 0 4px 20px rgba(0,0,0,.25);
}
.maj-header .brand { font-size: 1.1rem; font-weight: 800; letter-spacing: -.3px; }
.maj-header .sub   { font-size: .78rem; opacity: .8; }
.clock { font-size: 1.5rem; font-weight: 700; font-variant-numeric: tabular-nums; }

/* ── KPI STRIP ── */
.kpi-strip { display: flex; gap: 14px; padding: 18px 28px; flex-wrap: wrap; }
.kpi-card {
    flex: 1; min-width: 140px; max-width: 200px;
    background: white; border-radius: 16px; padding: 16px 20px;
    box-shadow: 0 2px 10px rgba(0,0,0,.05);
    display: flex; align-items: center; gap: 14px;
}
.kpi-card .ico { width: 44px; height: 44px; border-radius: 12px;
    display: flex; align-items: center; justify-content: center; font-size: 1.3rem; flex-shrink: 0; }
.kpi-card .val { font-size: 1.7rem; font-weight: 800; line-height: 1; }
.kpi-card .lbl { font-size: .7rem; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: .4px; margin-top: 2px; }
.kpi-blue   { background: #eff6ff; color: var(--maj-blue); }
.kpi-green  { background: #dcfce7; color: #15803d; }
.kpi-amber  { background: #fff7ed; color: #c2410c; }
.kpi-red    { background: #fee2e2; color: #b91c1c; }
.kpi-teal   { background: #e0f2fe; color: var(--maj-teal); }

/* ── TABS ── */
.maj-tabs { padding: 0 28px; border-bottom: 2px solid #e2e8f0; background: white; }
.maj-tabs .nav-link {
    color: #64748b; font-weight: 600; font-size: .85rem; padding: 12px 20px;
    border: none; border-bottom: 3px solid transparent; background: none;
    transition: .15s;
}
.maj-tabs .nav-link:hover { color: var(--maj-blue); }
.maj-tabs .nav-link.active { color: var(--maj-blue); border-bottom-color: var(--maj-blue); }
.maj-tabs .badge-tab { font-size: .65rem; padding: 2px 6px; border-radius: 20px; margin-left: 6px; vertical-align: middle; }

/* ── CONTENT ── */
.tab-content-area { padding: 24px 28px; }

/* ── SHIFT BLOCK ── */
.shift-block { margin-bottom: 28px; }
.shift-header {
    display: flex; align-items: center; gap: 10px;
    padding: 10px 16px; border-radius: 12px 12px 0 0;
    font-weight: 700; font-size: .85rem; color: white;
}
.shift-table { width: 100%; border-collapse: collapse; background: white;
    border-radius: 0 0 12px 12px; overflow: hidden;
    box-shadow: 0 2px 8px rgba(0,0,0,.06); }
.shift-table th { padding: 10px 14px; background: #f8fafc; font-size: .72rem;
    text-transform: uppercase; letter-spacing: .4px; color: #64748b;
    font-weight: 700; border-bottom: 1px solid #e2e8f0; }
.shift-table td { padding: 10px 14px; font-size: .82rem; border-bottom: 1px solid #f1f5f9;
    vertical-align: middle; }
.shift-table tr:last-child td { border-bottom: none; }
.shift-table tr:hover td { background: #f8fafc; }

/* ── STATUT BADGES ── */
.badge-statut { font-size: .68rem; font-weight: 700; padding: 3px 8px; border-radius: 20px; }
.s-planifie  { background: #e0f2fe; color: #0369a1; }
.s-realise   { background: #dcfce7; color: #15803d; }
.s-retard    { background: #fef3c7; color: #b45309; }
.s-annule    { background: #fee2e2; color: #991b1b; }

.cond-badge { font-size: .65rem; background: #fffbeb; color: #92400e;
    border: 1px dashed #fbbf24; border-radius: 20px; padding: 2px 7px; }

/* ── ALERT CARD ── */
.alert-card {
    background: white; border-radius: 14px; margin-bottom: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,.05);
    border-left: 4px solid transparent;
    display: flex; align-items: center; gap: 14px; padding: 14px 18px;
}
.alert-card.retard { border-left-color: var(--maj-amber); }
.alert-card.annule { border-left-color: var(--maj-red); }
.alert-card .ac-patient { font-weight: 700; font-size: .9rem; color: #1e293b; }
.alert-card .ac-soin    { font-size: .8rem; color: #475569; }
.alert-card .ac-time    { font-size: .75rem; color: #ef4444; font-weight: 600; }
.alert-card .ac-actions { margin-left: auto; display: flex; gap: 6px; flex-shrink: 0; }

/* ── INFIRMIER ROW ── */
.inf-row {
    background: white; border-radius: 14px; padding: 16px 20px;
    margin-bottom: 10px; box-shadow: 0 2px 8px rgba(0,0,0,.05);
    display: flex; align-items: center; gap: 16px; flex-wrap: wrap;
}
.inf-avatar { width: 40px; height: 40px; border-radius: 50%; background: var(--maj-blue);
    color: white; display: flex; align-items: center; justify-content: center;
    font-weight: 700; font-size: .9rem; flex-shrink: 0; }
.inf-name { font-weight: 700; font-size: .9rem; color: #1e293b; }
.inf-role { font-size: .7rem; color: #64748b; }
.inf-stats { display: flex; gap: 16px; margin-left: auto; flex-wrap: wrap; align-items: center; }
.stat-pill { display: flex; align-items: center; gap: 5px; font-size: .78rem; font-weight: 600; }
.inf-bar-wrap { flex-basis: 100%; margin-top: 8px; }
.inf-bar { height: 6px; background: #f1f5f9; border-radius: 3px; overflow: hidden; }
.inf-bar-fill { height: 100%; border-radius: 3px; background: var(--maj-green); transition: width .5s; }

/* ── PATIENT CARD ── */
.patient-row {
    background: white; border-radius: 14px; padding: 14px 18px;
    margin-bottom: 10px; box-shadow: 0 2px 8px rgba(0,0,0,.05);
    display: flex; align-items: center; gap: 14px; flex-wrap: wrap;
}
.patient-badge { font-weight: 800; font-size: .8rem; background: #eff6ff;
    color: var(--maj-blue); border-radius: 8px; padding: 4px 10px; }
.param-chip { font-size: .72rem; background: #f8fafc; border: 1px solid #e2e8f0;
    border-radius: 20px; padding: 3px 9px; color: #334155; display: inline-flex; align-items: center; gap: 4px; }
.param-chip.warn  { background: #fff7ed; border-color: #fbbf24; color: #92400e; }
.param-chip.alert { background: #fee2e2; border-color: #fca5a5; color: #991b1b; }

/* ── ACTION BUTTONS ── */
.btn-action {
    font-size: .72rem; font-weight: 700; padding: 5px 10px; border-radius: 8px; border: none; cursor: pointer; transition: .15s;
}
.btn-reassign { background: #eff6ff; color: var(--maj-blue); }
.btn-reassign:hover { background: #dbeafe; }
.btn-note     { background: #f5f3ff; color: var(--maj-purple); }
.btn-note:hover { background: #ede9fe; }
.btn-retard   { background: #fff7ed; color: #c2410c; }
.btn-retard:hover { background: #fed7aa; }

/* ── EMPTY ── */
.empty-state { text-align: center; padding: 50px 20px; color: #94a3b8; }
.empty-state i { font-size: 2.5rem; display: block; margin-bottom: 10px; }

/* ── MODAL ── */
.modal-content { border-radius: 16px; border: none; }
.modal-header  { border-radius: 16px 16px 0 0; }
</style>
</head>
<body>

<!-- ═══════════════════════ HEADER ═══════════════════════════ -->
<div class="maj-header">
    <div>
        <div class="brand"><i class="bi bi-shield-check me-2"></i>Supervision · <?= htmlspecialchars($nomService) ?></div>
        <div class="sub">Connecté en tant que : <?= htmlspecialchars($majorNom) ?> · Infirmier Major</div>
    </div>
    <div class="clock" id="liveClock">--:--:--</div>
    <div class="d-flex gap-2">
        <a href="<?= BASE_URL ?>dashboard" class="btn btn-sm btn-light fw-semibold rounded-pill">
            <i class="bi bi-house me-1"></i>Accueil
        </a>
        <button class="btn btn-sm btn-outline-light rounded-pill" onclick="location.reload()">
            <i class="bi bi-arrow-clockwise me-1"></i>Actualiser
        </button>
    </div>
</div>

<!-- ═══════════════════════ KPI STRIP ═══════════════════════════ -->
<div class="kpi-strip">
    <div class="kpi-card">
        <div class="ico kpi-blue"><i class="bi bi-people-fill"></i></div>
        <div>
            <div class="val"><?= $kpi['patients'] ?></div>
            <div class="lbl">Patients admis</div>
        </div>
    </div>
    <div class="kpi-card">
        <div class="ico kpi-teal"><i class="bi bi-clipboard2-pulse"></i></div>
        <div>
            <div class="val"><?= $kpi['soins_jour'] ?></div>
            <div class="lbl">Soins du jour</div>
        </div>
    </div>
    <div class="kpi-card">
        <div class="ico kpi-green"><i class="bi bi-check2-circle"></i></div>
        <div>
            <div class="val"><?= $kpi['realises'] ?></div>
            <div class="lbl">Réalisés</div>
        </div>
    </div>
    <div class="kpi-card">
        <div class="ico kpi-amber"><i class="bi bi-clock-history"></i></div>
        <div>
            <div class="val"><?= $kpi['retards'] ?></div>
            <div class="lbl">Retards</div>
        </div>
    </div>
    <div class="kpi-card" style="min-width:180px">
        <div class="ico kpi-<?= $kpi['taux'] >= 80 ? 'green' : ($kpi['taux'] >= 50 ? 'amber' : 'red') ?>">
            <i class="bi bi-bar-chart-fill"></i>
        </div>
        <div>
            <div class="val"><?= $kpi['taux'] ?>%</div>
            <div class="lbl">Taux de réalisation</div>
        </div>
    </div>
</div>

<!-- ═══════════════════════ TABS ═══════════════════════════════ -->
<div class="maj-tabs">
    <ul class="nav" id="majTabs" role="tablist">
        <li class="nav-item">
            <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-plan">
                <i class="bi bi-calendar2-check me-1"></i>Plan du Jour
                <span class="badge-tab badge bg-primary"><?= $kpi['soins_jour'] ?></span>
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-alertes">
                <i class="bi bi-exclamation-triangle me-1"></i>Retards & Alertes
                <?php if ($kpi['retards'] + $kpi['annules'] > 0): ?>
                <span class="badge-tab badge bg-danger"><?= $kpi['retards'] + $kpi['annules'] ?></span>
                <?php endif; ?>
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-equipe">
                <i class="bi bi-person-badge me-1"></i>Activité Équipe
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-patients">
                <i class="bi bi-heart-pulse me-1"></i>Patients du Service
                <span class="badge-tab badge bg-secondary"><?= $kpi['patients'] ?></span>
            </button>
        </li>
    </ul>
</div>

<div class="tab-content-area">
<div class="tab-content" id="majTabContent">

<!-- ═══════════════════ TAB 1 : PLAN DU JOUR ══════════════════ -->
<div class="tab-pane fade show active" id="tab-plan" role="tabpanel">
<?php foreach ($soins_par_tranche as $key => $tranche): ?>
    <?php if (empty($tranche['soins'])) continue; ?>
    <div class="shift-block">
        <div class="shift-header" style="background:<?= $tranche['color'] ?>;">
            <i class="bi <?= $tranche['icon'] ?> fs-5"></i>
            <?= $tranche['label'] ?>
            <span class="ms-auto badge bg-white text-dark"><?= count($tranche['soins']) ?> soins</span>
        </div>
        <table class="shift-table">
            <thead>
                <tr>
                    <th>Heure</th>
                    <th>Patient · Lit</th>
                    <th>Type</th>
                    <th>Description</th>
                    <th>Condition</th>
                    <th>Planifié par</th>
                    <th>Exécutant prévu</th>
                    <th>Statut</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($tranche['soins'] as $s): ?>
                <tr>
                    <td class="fw-bold text-nowrap" style="color:<?= $s['statut']==='RETARD' ? '#ef4444' : '#334155' ?>">
                        <?= date('H:i', strtotime($s['date_prevue'])) ?>
                        <?php if ($s['statut']==='RETARD'): ?><i class="bi bi-alarm-fill text-danger ms-1" title="En retard"></i><?php endif; ?>
                    </td>
                    <td>
                        <div class="fw-semibold"><?= htmlspecialchars($s['patient_nom'].' '.$s['patient_prenom']) ?></div>
                        <small class="text-muted"><?= htmlspecialchars($s['nom_chambre']??'') ?> · <?= htmlspecialchars($s['nom_lit']??'') ?></small>
                    </td>
                    <td><span class="badge bg-light text-dark border fw-semibold" style="font-size:.7rem"><?= htmlspecialchars($s['type_soin']) ?></span></td>
                    <td style="max-width:200px">
                        <?= htmlspecialchars($s['description']) ?>
                        <?php if ($s['note_major']): ?>
                            <div class="mt-1"><small class="text-purple-600" style="color:var(--maj-purple)"><i class="bi bi-chat-left-text-fill me-1"></i><?= htmlspecialchars($s['note_major']) ?></small></div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($s['condition_application']): ?>
                            <span class="cond-badge">⚡ <?= htmlspecialchars($s['condition_application']) ?></span>
                        <?php else: ?>
                            <span class="text-muted">—</span>
                        <?php endif; ?>
                    </td>
                    <td><small><?= htmlspecialchars(($s['planificateur_prenom']??'').' '.($s['planificateur_nom']??'')) ?></small></td>
                    <td>
                        <?php if ($s['user_id_reassigne'] && $s['reassigne_nom']): ?>
                            <span class="text-primary fw-semibold" style="font-size:.8rem">
                                <i class="bi bi-arrow-right-circle-fill me-1"></i><?= htmlspecialchars(($s['reassigne_prenom']??'').' '.($s['reassigne_nom'])) ?>
                            </span>
                        <?php elseif ($s['executant_nom']): ?>
                            <small><?= htmlspecialchars(($s['executant_prenom']??'').' '.$s['executant_nom']) ?></small>
                        <?php else: ?>
                            <span class="text-muted">Non assigné</span>
                        <?php endif; ?>
                    </td>
                    <td><span class="badge-statut s-<?= strtolower($s['statut']) ?>"><?= $s['statut'] ?></span></td>
                    <td class="text-nowrap">
                        <?php if (in_array($s['statut'], ['PLANIFIE','RETARD'])): ?>
                        <button class="btn-action btn-reassign" onclick="openReassign(<?= $s['id'] ?>)" title="Réassigner">
                            <i class="bi bi-person-fill-up"></i>
                        </button>
                        <button class="btn-action btn-retard" onclick="openRetard(<?= $s['id'] ?>)" title="Marquer retard">
                            <i class="bi bi-clock-history"></i>
                        </button>
                        <?php endif; ?>
                        <button class="btn-action btn-note" onclick="openNote(<?= $s['id'] ?>, '<?= htmlspecialchars(addslashes($s['note_major']??'')) ?>')" title="Annoter">
                            <i class="bi bi-chat-left-dots"></i>
                        </button>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endforeach; ?>
<?php if (empty(array_filter(array_column($soins_par_tranche, 'soins')))): ?>
    <div class="empty-state"><i class="bi bi-calendar2-x"></i>Aucun soin planifié pour aujourd'hui.</div>
<?php endif; ?>
</div>

<!-- ═══════════════════ TAB 2 : RETARDS & ALERTES ════════════ -->
<div class="tab-pane fade" id="tab-alertes" role="tabpanel">
    <?php
    $retards = array_filter($soins_alertes, fn($x) => $x['statut'] === 'RETARD');
    $annules = array_filter($soins_alertes, fn($x) => $x['statut'] === 'ANNULE');
    ?>

    <?php if (!empty($retards)): ?>
    <h6 class="fw-bold mb-3" style="color:#b45309"><i class="bi bi-clock-history me-2"></i>Soins en Retard (<?= count($retards) ?>)</h6>
    <?php foreach ($retards as $s): ?>
    <div class="alert-card retard">
        <div style="font-size:1.4rem; color:var(--maj-amber)"><i class="bi bi-clock-fill"></i></div>
        <div class="flex-grow-1">
            <div class="ac-patient"><?= htmlspecialchars($s['patient_nom'].' '.$s['patient_prenom']) ?>
                <span class="text-muted fw-normal" style="font-size:.8rem"> · <?= htmlspecialchars($s['nom_chambre']??'') ?> / <?= htmlspecialchars($s['nom_lit']??'') ?></span>
            </div>
            <div class="ac-soin"><strong><?= htmlspecialchars($s['type_soin']) ?></strong> — <?= htmlspecialchars($s['description']) ?></div>
            <?php if ($s['condition_application']): ?><div class="mt-1"><span class="cond-badge">⚡ <?= htmlspecialchars($s['condition_application']) ?></span></div><?php endif; ?>
            <div class="ac-time mt-1"><i class="bi bi-alarm me-1"></i>Prévu : <?= date('d/m H:i', strtotime($s['date_prevue'])) ?>
                <?php if ($s['note_major']): ?> · <i class="bi bi-chat-left-text"></i> <?= htmlspecialchars($s['note_major']) ?><?php endif; ?>
            </div>
        </div>
        <div class="ac-actions">
            <button class="btn-action btn-reassign" onclick="openReassign(<?= $s['id'] ?>)"><i class="bi bi-person-fill-up me-1"></i>Réassigner</button>
            <button class="btn-action btn-note" onclick="openNote(<?= $s['id'] ?>, '<?= htmlspecialchars(addslashes($s['note_major']??'')) ?>')"><i class="bi bi-chat-left-dots me-1"></i>Annoter</button>
        </div>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>

    <?php if (!empty($annules)): ?>
    <h6 class="fw-bold mb-3 mt-4" style="color:#991b1b"><i class="bi bi-x-circle me-2"></i>Soins Annulés (<?= count($annules) ?>)</h6>
    <?php foreach ($annules as $s): ?>
    <div class="alert-card annule">
        <div style="font-size:1.4rem; color:var(--maj-red)"><i class="bi bi-x-circle-fill"></i></div>
        <div class="flex-grow-1">
            <div class="ac-patient"><?= htmlspecialchars($s['patient_nom'].' '.$s['patient_prenom']) ?></div>
            <div class="ac-soin"><strong><?= htmlspecialchars($s['type_soin']) ?></strong> — <?= htmlspecialchars($s['description']) ?></div>
            <div class="ac-time mt-1"><i class="bi bi-calendar-x me-1"></i>Prévu : <?= date('d/m H:i', strtotime($s['date_prevue'])) ?>
                <?php if ($s['note_major']): ?> · <i class="bi bi-chat-left-text"></i> <?= htmlspecialchars($s['note_major']) ?><?php endif; ?>
            </div>
        </div>
        <div class="ac-actions">
            <button class="btn-action btn-note" onclick="openNote(<?= $s['id'] ?>, '<?= htmlspecialchars(addslashes($s['note_major']??'')) ?>')"><i class="bi bi-chat-left-dots me-1"></i>Annoter</button>
        </div>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>

    <?php if (empty($retards) && empty($annules)): ?>
    <div class="empty-state" style="color:#22c55e">
        <i class="bi bi-check-circle-fill" style="font-size:3rem; color:#22c55e"></i>
        <div class="fw-bold mt-2">Aucune alerte pour l'instant !</div>
        <div class="text-muted">Tous les soins sont dans les temps.</div>
    </div>
    <?php endif; ?>
</div>

<!-- ═══════════════════ TAB 3 : ACTIVITÉ ÉQUIPE ══════════════ -->
<div class="tab-pane fade" id="tab-equipe" role="tabpanel">
<?php if (empty($activite_infirmiers)): ?>
    <div class="empty-state"><i class="bi bi-people"></i>Aucune activité enregistrée aujourd'hui.</div>
<?php else: ?>
    <div class="row g-3">
    <?php foreach ($activite_infirmiers as $inf): ?>
        <?php
        $total    = max(1, (int)$inf['total']);
        $realises = (int)$inf['realises'];
        $retardsI = (int)$inf['retards'];
        $annulesI = (int)$inf['annules'];
        $attente  = (int)$inf['en_attente'];
        $taux     = round($realises / $total * 100);
        $initials = strtoupper(mb_substr($inf['prenom'],0,1).mb_substr($inf['nom'],0,1));
        ?>
        <div class="col-md-6 col-lg-4">
        <div class="inf-row flex-wrap">
            <div class="inf-avatar"><?= $initials ?></div>
            <div>
                <div class="inf-name"><?= htmlspecialchars($inf['prenom'].' '.$inf['nom']) ?></div>
                <div class="inf-role"><?= htmlspecialchars($inf['role']) ?></div>
            </div>
            <div class="inf-stats">
                <span class="stat-pill" style="color:#15803d"><i class="bi bi-check2-circle"></i><?= $realises ?> réalisés</span>
                <span class="stat-pill" style="color:#b45309"><i class="bi bi-clock-history"></i><?= $retardsI ?> retards</span>
                <span class="stat-pill" style="color:#991b1b"><i class="bi bi-x-circle"></i><?= $annulesI ?> annulés</span>
                <span class="stat-pill" style="color:#0369a1"><i class="bi bi-hourglass-split"></i><?= $attente ?> en attente</span>
            </div>
            <div class="inf-bar-wrap">
                <div class="d-flex justify-content-between mb-1" style="font-size:.72rem; color:#64748b">
                    <span>Progression</span><span class="fw-bold"><?= $taux ?>%</span>
                </div>
                <div class="inf-bar">
                    <div class="inf-bar-fill" style="width:<?= $taux ?>%; background:<?= $taux>=80 ? 'var(--maj-green)' : ($taux>=50 ? 'var(--maj-amber)' : 'var(--maj-red)') ?>"></div>
                </div>
            </div>
        </div>
        </div>
    <?php endforeach; ?>
    </div>
<?php endif; ?>
</div>

<!-- ═══════════════════ TAB 4 : PATIENTS DU SERVICE ═════════ -->
<div class="tab-pane fade" id="tab-patients" role="tabpanel">
<?php if (empty($patients_service)): ?>
    <div class="empty-state"><i class="bi bi-person-x"></i>Aucun patient hospitalisé dans ce service.</div>
<?php else: ?>
    <?php foreach ($patients_service as $p):
        $age = '';
        if ($p['date_naissance']) {
            $diff = (new DateTime())->diff(new DateTime($p['date_naissance']));
            $age  = $diff->y . ' ans';
        }
        $soinsRestants = (int)($p['soins_restants'] ?? 0);
        $soinsFaits    = (int)($p['soins_faits']    ?? 0);
        $totalSoins    = $soinsRestants + $soinsFaits;
        $taux          = $totalSoins > 0 ? round($soinsFaits / $totalSoins * 100) : 0;
    ?>
    <div class="patient-row">
        <!-- Identité -->
        <div style="min-width:180px">
            <div class="fw-bold" style="color:#1e293b"><?= htmlspecialchars($p['nom'].' '.$p['prenom']) ?></div>
            <div class="patient-badge mt-1"><?= htmlspecialchars($p['dossier_numero']) ?></div>
            <?php if ($age): ?><small class="text-muted"><?= $age ?></small><?php endif; ?>
        </div>
        <!-- Localisation -->
        <div style="min-width:120px">
            <div class="fw-semibold" style="font-size:.82rem"><i class="bi bi-door-open me-1 text-muted"></i><?= htmlspecialchars($p['nom_chambre']??'—') ?></div>
            <div style="font-size:.78rem; color:#64748b"><i class="bi bi-hospital me-1"></i>Lit : <?= htmlspecialchars($p['nom_lit']??'—') ?></div>
            <?php if ($p['date_admission']): ?><div style="font-size:.72rem; color:#94a3b8">Admis : <?= date('d/m/Y', strtotime($p['date_admission'])) ?></div><?php endif; ?>
        </div>
        <!-- Paramètres -->
        <div class="d-flex flex-wrap gap-2 flex-grow-1">
            <?php if ($p['temperature']): ?>
                <?php $tempWarn = $p['temperature'] >= 38; ?>
                <span class="param-chip <?= $tempWarn ? 'warn' : '' ?>">
                    <i class="bi bi-thermometer-half"></i><?= $p['temperature'] ?>°C
                </span>
            <?php endif; ?>
            <?php if ($p['ta_sys'] && $p['ta_dia']): ?>
                <?php $taWarn = $p['ta_sys'] > 140 || $p['ta_sys'] < 90; ?>
                <span class="param-chip <?= $taWarn ? 'warn' : '' ?>">
                    <i class="bi bi-heart-pulse"></i><?= $p['ta_sys'] ?>/<?= $p['ta_dia'] ?> mmHg
                </span>
            <?php endif; ?>
            <?php if ($p['fc']): ?>
                <?php $fcWarn = $p['fc'] > 100 || $p['fc'] < 50; ?>
                <span class="param-chip <?= $fcWarn ? 'warn' : '' ?>">
                    <i class="bi bi-activity"></i><?= $p['fc'] ?> bpm
                </span>
            <?php endif; ?>
            <?php if ($p['spo2']): ?>
                <?php $spo2Alert = $p['spo2'] < 94; ?>
                <span class="param-chip <?= $spo2Alert ? 'alert' : '' ?>">
                    <i class="bi bi-lungs"></i><?= $p['spo2'] ?>%
                </span>
            <?php endif; ?>
            <?php if ($p['glycemie']): ?>
                <span class="param-chip"><i class="bi bi-droplet"></i><?= $p['glycemie'] ?> g/L</span>
            <?php endif; ?>
            <?php if (!$p['temperature'] && !$p['ta_sys'] && !$p['fc'] && !$p['spo2']): ?>
                <span class="text-muted" style="font-size:.78rem"><i class="bi bi-dash-circle me-1"></i>Aucune constante mesurée</span>
            <?php endif; ?>
            <?php if ($p['date_derniere_mesure']): ?>
                <span class="param-chip" title="Dernière mesure"><i class="bi bi-clock-history"></i><?= date('H:i', strtotime($p['date_derniere_mesure'])) ?></span>
            <?php endif; ?>
        </div>
        <!-- Progression soins -->
        <div style="min-width:160px">
            <div class="d-flex justify-content-between mb-1" style="font-size:.72rem; color:#64748b">
                <span>Soins</span>
                <span class="fw-bold"><?= $soinsFaits ?>/<?= $totalSoins ?></span>
            </div>
            <div class="inf-bar">
                <div class="inf-bar-fill" style="width:<?= $taux ?>%; background:<?= $taux>=80 ? 'var(--maj-green)' : ($taux>=50 ? 'var(--maj-amber)' : 'var(--maj-red)') ?>"></div>
            </div>
            <?php if ($soinsRestants > 0): ?>
                <small class="text-warning fw-semibold" style="font-size:.7rem"><i class="bi bi-hourglass-split me-1"></i><?= $soinsRestants ?> restant(s)</small>
            <?php else: ?>
                <small class="text-success fw-semibold" style="font-size:.7rem"><i class="bi bi-check2-all me-1"></i>Tous faits</small>
            <?php endif; ?>
        </div>
        <!-- Accès rapide -->
        <div class="text-end">
            <a href="<?= BASE_URL ?>hospitalisation/suivi/<?= $p['id'] ?>" class="btn-action btn-reassign" style="text-decoration:none; display:inline-block; font-size:.75rem">
                <i class="bi bi-folder2-open me-1"></i>Suivi
            </a>
        </div>
    </div>
    <?php endforeach; ?>
<?php endif; ?>
</div>

</div><!-- /tab-content -->
</div><!-- /tab-content-area -->

<!-- ═══════════════════ MODAL : RÉASSIGNER ═══════════════════ -->
<div class="modal fade" id="modalReassign" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="bi bi-person-fill-up me-2"></i>Réassigner ce soin</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <input type="hidden" id="reassignSoinId">
                <div class="mb-3">
                    <label class="form-label fw-bold">Infirmier(e) à affecter</label>
                    <select class="form-select" id="reassignInfirmier">
                        <option value="">— Sélectionner —</option>
                        <?php foreach ($equipe as $e): ?>
                        <option value="<?= $e['id'] ?>"><?= htmlspecialchars($e['prenom'].' '.$e['nom']) ?> (<?= $e['role'] ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Note (optionnel)</label>
                    <textarea class="form-control" id="reassignNote" rows="3" placeholder="Motif de réassignation…"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button class="btn btn-primary" onclick="submitReassign()"><i class="bi bi-check2 me-1"></i>Confirmer</button>
            </div>
        </div>
    </div>
</div>

<!-- ═══════════════════ MODAL : ANNOTATION ═══════════════════ -->
<div class="modal fade" id="modalNote" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header" style="background:var(--maj-purple); color:white">
                <h5 class="modal-title"><i class="bi bi-chat-left-dots-fill me-2"></i>Annotation Major</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <input type="hidden" id="noteSoinId">
                <div class="mb-3">
                    <label class="form-label fw-bold">Note visible par l'équipe</label>
                    <textarea class="form-control" id="noteTexte" rows="4" placeholder="Votre annotation…"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button class="btn" style="background:var(--maj-purple);color:white" onclick="submitNote()"><i class="bi bi-check2 me-1"></i>Enregistrer</button>
            </div>
        </div>
    </div>
</div>

<!-- ═══════════════════ MODAL : MARQUER RETARD ═══════════════ -->
<div class="modal fade" id="modalRetard" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title fw-bold"><i class="bi bi-clock-history me-2"></i>Marquer en Retard</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <input type="hidden" id="retardSoinId">
                <div class="mb-3">
                    <label class="form-label fw-bold">Motif du retard</label>
                    <textarea class="form-control" id="retardNote" rows="3" placeholder="Ex: patient absent du lit, matériel manquant…"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button class="btn btn-warning fw-bold" onclick="submitRetard()"><i class="bi bi-check2 me-1"></i>Confirmer</button>
            </div>
        </div>
    </div>
</div>

<!-- ═══════════════════ TOAST ════════════════════════════════ -->
<div class="position-fixed bottom-0 end-0 p-3" style="z-index:9999">
    <div id="majToast" class="toast align-items-center text-white border-0" role="alert">
        <div class="d-flex">
            <div class="toast-body fw-semibold" id="majToastMsg">Action effectuée.</div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    </div>
</div>

<script src="<?= BASE_URL ?>public/js/bootstrap.bundle.min.js"></script>
<script>
const BASE = '<?= BASE_URL ?>';

// ── Horloge live ──────────────────────────────────────────────
function tickClock() {
    const now = new Date();
    document.getElementById('liveClock').textContent =
        String(now.getHours()).padStart(2,'0') + ':' +
        String(now.getMinutes()).padStart(2,'0') + ':' +
        String(now.getSeconds()).padStart(2,'0');
}
tickClock(); setInterval(tickClock, 1000);

// ── Toast helper ──────────────────────────────────────────────
function showToast(msg, ok = true) {
    const el  = document.getElementById('majToast');
    const msg_el = document.getElementById('majToastMsg');
    el.className = 'toast align-items-center text-white border-0 ' + (ok ? 'bg-success' : 'bg-danger');
    msg_el.textContent = msg;
    bootstrap.Toast.getOrCreateInstance(el, {delay:3000}).show();
}

// ── AJAX helper ───────────────────────────────────────────────
async function ajaxPost(url, data) {
    const r = await fetch(BASE + url, {
        method: 'POST',
        headers: {'Content-Type':'application/json'},
        body: JSON.stringify(data)
    });
    return r.json();
}

// ── RÉASSIGNER ────────────────────────────────────────────────
function openReassign(soinId) {
    document.getElementById('reassignSoinId').value = soinId;
    document.getElementById('reassignNote').value   = '';
    document.getElementById('reassignInfirmier').value = '';
    bootstrap.Modal.getOrCreateInstance(document.getElementById('modalReassign')).show();
}
async function submitReassign() {
    const soinId      = document.getElementById('reassignSoinId').value;
    const infirmierId = document.getElementById('reassignInfirmier').value;
    const note        = document.getElementById('reassignNote').value;
    if (!infirmierId) { alert('Veuillez sélectionner un infirmier.'); return; }
    const res = await ajaxPost('major/reassigner-soin', {soin_id: soinId, infirmier_id: infirmierId, note});
    bootstrap.Modal.getInstance(document.getElementById('modalReassign')).hide();
    showToast(res.success ? 'Soin réassigné avec succès.' : ('Erreur: ' + res.message), res.success);
    if (res.success) setTimeout(() => location.reload(), 1500);
}

// ── ANNOTATION ────────────────────────────────────────────────
function openNote(soinId, note) {
    document.getElementById('noteSoinId').value  = soinId;
    document.getElementById('noteTexte').value   = note;
    bootstrap.Modal.getOrCreateInstance(document.getElementById('modalNote')).show();
}
async function submitNote() {
    const soinId = document.getElementById('noteSoinId').value;
    const note   = document.getElementById('noteTexte').value.trim();
    const res    = await ajaxPost('major/annoter-soin', {soin_id: soinId, note});
    bootstrap.Modal.getInstance(document.getElementById('modalNote')).hide();
    showToast(res.success ? 'Annotation enregistrée.' : ('Erreur: ' + res.message), res.success);
    if (res.success) setTimeout(() => location.reload(), 1500);
}

// ── MARQUER RETARD ────────────────────────────────────────────
function openRetard(soinId) {
    document.getElementById('retardSoinId').value = soinId;
    document.getElementById('retardNote').value   = '';
    bootstrap.Modal.getOrCreateInstance(document.getElementById('modalRetard')).show();
}
async function submitRetard() {
    const soinId = document.getElementById('retardSoinId').value;
    const note   = document.getElementById('retardNote').value.trim();
    const res    = await ajaxPost('major/marquer-retard', {soin_id: soinId, note});
    bootstrap.Modal.getInstance(document.getElementById('modalRetard')).hide();
    showToast(res.success ? 'Soin marqué en retard.' : ('Erreur: ' + res.message), res.success);
    if (res.success) setTimeout(() => location.reload(), 1500);
}
</script>
</body>
</html>
