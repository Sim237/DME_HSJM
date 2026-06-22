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
require_once __DIR__ . '/../../services/AuditService.php';
$adminPage = 'logs';
require_once __DIR__ . '/partials/admin_nav.php';

// Onglet actif
$activeTab = $_GET['tab'] ?? 'overview';
if (!in_array($activeTab, ['overview','journal','users','security'])) $activeTab = 'overview';

// Préparer les données JSON pour Chart.js
$days30 = [];
for ($i = 29; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-$i days"));
    $days30[] = ['label' => date('d/m', strtotime($d)), 'nb' => $activityByDay[$d] ?? 0];
}

$heatmapMax = 1;
foreach ($heatmap as $dow => $hours) {
    foreach ($hours as $h => $v) { if ($v > $heatmapMax) $heatmapMax = $v; }
}
$dowLabels = ['Dim','Lun','Mar','Mer','Jeu','Ven','Sam'];
?>

<!-- Chart.js local -->
<script src="<?= BASE_URL ?>public/js/chart.umd.min.js"></script>

        <!-- TOPBAR -->
        <div class="admin-topbar">
            <div class="admin-topbar-title">
                Journal d'Audit
                <small>Traçabilité, statistiques et sécurité</small>
            </div>
            <div class="adm-topbar-actions">
                <div class="adm-clock-pill">
                    <span class="adm-clock-dot"></span>
                    <span class="adm-clock-time">--:--:--</span>
                </div>
                <span class="adm-topbtn adm-topbtn-ghost" style="cursor:default;">
                    <i class="bi bi-journal-text"></i><?= number_format($kpi['total']) ?> entrées
                </span>
                <?php if ($kpi['login_failures'] > 0): ?>
                <span class="adm-topbtn" style="background:rgba(239,68,68,.1);color:#dc2626;cursor:default;">
                    <i class="bi bi-shield-exclamation"></i><?= $kpi['login_failures'] ?> échecs (7j)
                </span>
                <?php endif; ?>
            </div>
        </div>

        <div class="admin-page-content">

            <!-- ONGLETS DE NAVIGATION -->
            <ul class="nav nav-tabs mb-4" id="auditTabs" role="tablist" style="border-bottom:2px solid #e2e8f0;">
                <li class="nav-item" role="presentation">
                    <button class="nav-link <?= $activeTab==='overview'?'active':'' ?> fw-semibold"
                            id="tab-overview" data-bs-toggle="tab" data-bs-target="#pane-overview"
                            type="button" role="tab" style="font-size:.85rem;">
                        <i class="bi bi-bar-chart-line-fill me-1 text-primary"></i>Vue d'ensemble
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link <?= $activeTab==='journal'?'active':'' ?> fw-semibold"
                            id="tab-journal" data-bs-toggle="tab" data-bs-target="#pane-journal"
                            type="button" role="tab" style="font-size:.85rem;">
                        <i class="bi bi-list-ul me-1 text-secondary"></i>Journal
                        <span class="badge bg-secondary-subtle text-secondary border ms-1" style="font-size:.68rem;"><?= number_format($total_rows) ?></span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link <?= $activeTab==='users'?'active':'' ?> fw-semibold"
                            id="tab-users" data-bs-toggle="tab" data-bs-target="#pane-users"
                            type="button" role="tab" style="font-size:.85rem;">
                        <i class="bi bi-people-fill me-1 text-success"></i>Utilisateurs
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link <?= $activeTab==='security'?'active':'' ?> fw-semibold"
                            id="tab-security" data-bs-toggle="tab" data-bs-target="#pane-security"
                            type="button" role="tab" style="font-size:.85rem;">
                        <i class="bi bi-shield-fill-exclamation me-1 text-danger"></i>Sécurité
                        <?php if ($kpi['login_failures'] > 0): ?>
                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle ms-1" style="font-size:.68rem;"><?= $kpi['login_failures'] ?></span>
                        <?php endif; ?>
                    </button>
                </li>
            </ul>

            <div class="tab-content" id="auditTabsContent">

<!-- ═══════════════════════════════════════════════════════════════════════════
     ONGLET : VUE D'ENSEMBLE
═══════════════════════════════════════════════════════════════════════════ -->
<div class="tab-pane fade <?= $activeTab==='overview'?'show active':'' ?>" id="pane-overview" role="tabpanel">

    <!-- KPI CARDS -->
    <div class="row g-3 mb-4">
        <?php
        $kpiCards = [
            ['label'=>'Actions totales',       'val'=>number_format($kpi['total']),         'icon'=>'journal-text',      'color'=>'primary',   'sub'=>'Depuis le début'],
            ['label'=>'Aujourd\'hui',           'val'=>number_format($kpi['today']),         'icon'=>'calendar-day',      'color'=>'info',      'sub'=>'Actions aujourd\'hui'],
            ['label'=>'7 derniers jours',       'val'=>number_format($kpi['week']),          'icon'=>'graph-up-arrow',    'color'=>'success',   'sub'=>'Actions cette semaine'],
            ['label'=>'Utilisateurs actifs (j)','val'=>$kpi['users_today'],                  'icon'=>'person-check',      'color'=>'teal',      'sub'=>'Connectés aujourd\'hui'],
            ['label'=>'Utilisateurs actifs (7j)','val'=>$kpi['users_week'],                  'icon'=>'people',            'color'=>'indigo',    'sub'=>'Actifs cette semaine'],
            ['label'=>'Échecs connexion (7j)',  'val'=>$kpi['login_failures'],               'icon'=>'shield-exclamation','color'=>'warning',   'sub'=>'Tentatives échouées'],
            ['label'=>'Accès refusés (7j)',     'val'=>$kpi['access_denied'],                'icon'=>'ban',               'color'=>'danger',    'sub'=>'ACCESS_DENIED'],
        ];
        $colorMap = [
            'primary'=>['#3b82f6','#eff6ff'],'info'=>['#06b6d4','#ecfeff'],
            'success'=>['#10b981','#f0fdf4'],'teal'=>['#14b8a6','#f0fdfa'],
            'indigo'=>['#6366f1','#eef2ff'],'warning'=>['#f59e0b','#fffbeb'],'danger'=>['#ef4444','#fef2f2'],
        ];
        foreach ($kpiCards as $card):
            [$fg,$bg] = $colorMap[$card['color']];
        ?>
        <div class="col-md-3 col-lg">
            <div class="adm-stat h-100" style="--adm-accent:<?= $fg ?>;">
                <div style="display:flex;align-items:center;gap:12px;">
                    <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0"
                         style="width:40px;height:40px;background:<?= $bg ?>;">
                        <i class="bi bi-<?= $card['icon'] ?>" style="font-size:1.1rem;color:<?= $fg ?>;"></i>
                    </div>
                    <div>
                        <div class="adm-stat-val" style="font-size:1.5rem;color:<?= $fg ?>;"><?= $card['val'] ?></div>
                        <div class="adm-stat-lbl"><?= $card['label'] ?></div>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- GRAPHIQUES LIGNE + DONUT -->
    <div class="row g-3 mb-4">
        <div class="col-lg-8">
            <div class="adm-card h-100">
                <div class="adm-card-header">
                    <span><i class="bi bi-graph-up-arrow me-2 text-primary"></i>Activité — 30 derniers jours</span>
                </div>
                <div class="adm-card-body">
                    <canvas id="chartActivityDay" height="90"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="adm-card h-100">
                <div class="adm-card-header">
                    <span><i class="bi bi-pie-chart-fill me-2 text-info"></i>Répartition des actions</span>
                </div>
                <div class="adm-card-body d-flex align-items-center justify-content-center">
                    <canvas id="chartActionDoughnut" height="160"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- HISTOGRAMME PAR HEURE + TOP MODULES -->
    <div class="row g-3 mb-4">
        <div class="col-lg-7">
            <div class="adm-card h-100">
                <div class="adm-card-header">
                    <span><i class="bi bi-clock-history me-2 text-success"></i>Activité par heure (30 derniers jours)</span>
                </div>
                <div class="adm-card-body">
                    <canvas id="chartHourBar" height="100"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="adm-card h-100">
                <div class="adm-card-header">
                    <span><i class="bi bi-table me-2 text-warning"></i>Top modules</span>
                </div>
                <div class="table-responsive">
                    <table class="adm-table" style="font-size:.8rem;">
                            <tbody>
                                <?php
                                $maxMod = $topModules[0]['nb'] ?? 1;
                                foreach ($topModules as $mod):
                                    $pct = round($mod['nb'] / $maxMod * 100);
                                ?>
                                <tr>
                                    <td class="ps-4 py-2">
                                        <code style="font-size:.75rem;color:#7c3aed;"><?= htmlspecialchars($mod['table_name']) ?></code>
                                    </td>
                                    <td class="py-2" style="width:50%;">
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="flex-grow-1 bg-light rounded" style="height:6px;">
                                                <div class="rounded" style="height:6px;width:<?= $pct ?>%;background:#7c3aed;"></div>
                                            </div>
                                            <span class="text-muted fw-bold" style="font-size:.75rem;min-width:35px;text-align:right;"><?= number_format($mod['nb']) ?></span>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                </div>
            </div>
        </div>
    </div>

    <!-- HEATMAP ACTIVITÉ (Jour × Heure) -->
    <div class="card border-0 shadow-sm rounded-4 mb-2">
        <div class="card-header bg-transparent border-0 pb-0 pt-3 px-4">
            <span class="fw-bold text-dark" style="font-size:.9rem;">
                <i class="bi bi-grid-3x3-gap-fill me-2 text-indigo" style="color:#6366f1;"></i>
                Carte de chaleur — Activité par jour et heure (90 jours)
            </span>
            <small class="text-muted ms-2" style="font-size:.72rem;">Intensité = nombre d'actions</small>
        </div>
        <div class="card-body p-3">
            <div style="overflow-x:auto;">
                <table class="mb-0" style="font-size:.68rem;border-collapse:separate;border-spacing:2px;">
                    <thead>
                        <tr>
                            <th style="width:30px;"></th>
                            <?php for ($h = 0; $h < 24; $h++): ?>
                            <th class="text-center text-muted fw-normal" style="min-width:22px;padding:1px 2px;"><?= $h ?>h</th>
                            <?php endfor; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ([1,2,3,4,5,6,0] as $dow): // Lun-Dim ?>
                        <tr>
                            <td class="text-muted pe-2 fw-semibold" style="white-space:nowrap;"><?= $dowLabels[$dow] ?></td>
                            <?php for ($h = 0; $h < 24; $h++):
                                $v   = $heatmap[$dow][$h];
                                $pct = $v / $heatmapMax;
                                // Couleur : blanc → bleu foncé
                                $r = (int)(235 - $pct * 160);
                                $g = (int)(240 - $pct * 140);
                                $b = (int)(255 - $pct * 50);
                                $bg = $v > 0 ? "rgb($r,$g,$b)" : '#f1f5f9';
                                $fg2 = $pct > 0.5 ? '#fff' : '#334155';
                            ?>
                            <td title="<?= $dowLabels[$dow] ?> <?= $h ?>h : <?= $v ?> actions"
                                style="background:<?= $bg ?>;color:<?= $fg2 ?>;border-radius:3px;text-align:center;padding:3px 2px;min-width:22px;cursor:default;">
                                <?= $v > 0 ? $v : '' ?>
                            </td>
                            <?php endfor; ?>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="d-flex align-items-center gap-1 mt-2">
                <small class="text-muted me-1" style="font-size:.68rem;">Moins</small>
                <?php for ($i = 0; $i <= 8; $i++):
                    $p = $i / 8;
                    $r = (int)(235 - $p * 160); $g = (int)(240 - $p * 140); $b = (int)(255 - $p * 50);
                ?>
                <div style="width:14px;height:14px;border-radius:2px;background:rgb(<?= $r ?>,<?= $g ?>,<?= $b ?>);"></div>
                <?php endfor; ?>
                <small class="text-muted ms-1" style="font-size:.68rem;">Plus</small>
            </div>
        </div>
    </div>

</div><!-- /pane-overview -->


<!-- ═══════════════════════════════════════════════════════════════════════════
     ONGLET : JOURNAL
═══════════════════════════════════════════════════════════════════════════ -->
<div class="tab-pane fade <?= $activeTab==='journal'?'show active':'' ?>" id="pane-journal" role="tabpanel">

    <!-- FILTRES -->
    <form method="GET" class="card border-0 shadow-sm rounded-4 mb-4">
        <input type="hidden" name="tab" value="journal">
        <div class="card-body p-3">
            <div class="row g-2 align-items-end">
                <div class="col-md-2">
                    <label class="form-label fw-bold small mb-1">Du</label>
                    <input type="date" name="date_from" class="form-control form-control-sm rounded-3"
                           value="<?= htmlspecialchars($filters['date_from']) ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-bold small mb-1">Au</label>
                    <input type="date" name="date_to" class="form-control form-control-sm rounded-3"
                           value="<?= htmlspecialchars($filters['date_to']) ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-bold small mb-1">Action</label>
                    <select name="action" class="form-select form-select-sm rounded-3">
                        <option value="ALL" <?= $filters['action']==='ALL'?'selected':'' ?>>Toutes</option>
                        <?php foreach ($actions as $a): ?>
                        <option value="<?= $a ?>" <?= $filters['action']===$a?'selected':'' ?>><?= $a ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-bold small mb-1">Module / Table</label>
                    <select name="table" class="form-select form-select-sm rounded-3">
                        <option value="ALL" <?= $filters['table']==='ALL'?'selected':'' ?>>Tous</option>
                        <?php foreach ($tables as $t):
                            $mi = AuditService::moduleInfo($t);
                            $lbl = $mi['label'] !== $t ? $mi['label'].' ('.$t.')' : $t;
                        ?>
                        <option value="<?= htmlspecialchars($t) ?>" <?= $filters['table']===$t?'selected':'' ?>><?= htmlspecialchars($lbl) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-bold small mb-1">Utilisateur</label>
                    <select name="user_id" class="form-select form-select-sm rounded-3">
                        <option value="">Tous</option>
                        <?php foreach ($users as $u): ?>
                        <option value="<?= $u['id'] ?>" <?= (string)$filters['user_id']===(string)$u['id']?'selected':'' ?>><?= htmlspecialchars($u['full_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-bold small mb-1">Recherche / IP</label>
                    <input type="text" name="search" class="form-control form-control-sm rounded-3"
                           placeholder="Utilisateur, action…" value="<?= htmlspecialchars($filters['search']) ?>">
                </div>
            </div>
            <div class="row g-2 mt-1">
                <div class="col-md-2">
                    <input type="text" name="ip" class="form-control form-control-sm rounded-3"
                           placeholder="Filtrer par IP…" value="<?= htmlspecialchars($filters['ip']) ?>">
                </div>
                <div class="col-md-10 d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-sm rounded-3 px-4">
                        <i class="bi bi-funnel me-1"></i>Filtrer
                    </button>
                    <a href="<?= BASE_URL ?>admin/logs?tab=journal" class="btn btn-light btn-sm rounded-3 border">
                        <i class="bi bi-x-circle me-1"></i>Réinitialiser
                    </a>
                    <span class="text-muted my-auto ms-2" style="font-size:.78rem;"><?= number_format($total_rows) ?> résultat(s)</span>
                </div>
            </div>
        </div>
    </form>

    <!-- TABLE JOURNAL -->
    <div class="adm-card">
        <div class="adm-card-header">
            <span><i class="bi bi-list-ul me-2 text-secondary"></i>Entrées du journal</span>
            <span class="adm-stat-lbl"><?= number_format($total_rows) ?> lignes</span>
        </div>
        <div class="table-responsive">
            <table class="adm-table audit-table" style="font-size:.82rem;">
                    <thead>
                        <tr>
                            <th style="width:30px;"></th>
                            <th style="padding-left:.5rem;">Date / Heure</th>
                            <th>Utilisateur</th>
                            <th>Action</th>
                            <th>Module</th>
                            <th>Détails</th>
                            <th style="padding-right:1.5rem;">Appareil / IP</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($logs)): ?>
                            <tr>
                                <td colspan="7" class="text-center py-5 text-muted">
                                    <i class="bi bi-journal-x d-block fs-2 mb-2 opacity-25"></i>
                                    Aucun journal pour les filtres sélectionnés.
                                </td>
                            </tr>
                        <?php else: foreach ($logs as $idx => $log):
                            // Couleurs par action
                            $actionColors = [
                                'INSERT'=>'success','CREATE'=>'success','ADD'=>'success',
                                'UPDATE'=>'primary','UPDATE_FAIL'=>'warning',
                                'DELETE'=>'danger','ANONYMIZE_PATIENT'=>'danger',
                                'READ'=>'secondary',
                                'LOGIN'=>'info','LOGOUT'=>'warning','LOGIN_FAIL'=>'danger','LOGIN_ATTEMPT'=>'warning',
                                'ACCESS_DENIED'=>'danger','EXPORT'=>'dark',
                            ];
                            $actionLabels = [
                                'INSERT'=>'Création','CREATE'=>'Création','ADD'=>'Ajout',
                                'UPDATE'=>'Modification','DELETE'=>'Suppression',
                                'READ'=>'Consultation','LOGIN'=>'Connexion','LOGOUT'=>'Déconnexion',
                                'LOGIN_FAIL'=>'Échec connexion','LOGIN_ATTEMPT'=>'Tentative','ACCESS_DENIED'=>'Accès refusé',
                                'EXPORT'=>'Export','ANONYMIZE_PATIENT'=>'Anonymisation',
                            ];
                            $ac      = $actionColors[$log['action']] ?? 'secondary';
                            $aLabel  = $actionLabels[$log['action']] ?? $log['action'];

                            // Module (libellé + icône + catégorie)
                            $mod     = AuditService::moduleInfo($log['table_name'] ?? null, $log['action']);
                            $modLbl  = !empty($log['module']) ? $log['module'] : $mod['label'];

                            // Description lisible : colonne dédiée sinon dérivée du JSON
                            // Normalisation : certaines entrées contiennent un scalaire JSON (string/number)
                            // → on force un tableau associatif pour éviter tout array_keys() sur une chaîne.
                            $oldArr  = !empty($log['old_values']) ? json_decode($log['old_values'], true) : null;
                            $newArr  = !empty($log['new_values']) ? json_decode($log['new_values'], true) : null;
                            if ($oldArr !== null && !is_array($oldArr)) $oldArr = ['valeur' => $oldArr];
                            if ($newArr !== null && !is_array($newArr)) $newArr = ['valeur' => $newArr];
                            $descTxt = $log['description'] ?? '';
                            if (!$descTxt) {
                                $src = $newArr ?? $oldArr;
                                if (is_array($src)) {
                                    if (isset($src['info']))      $descTxt = $src['info'];
                                    else $descTxt = implode(' · ', array_map(fn($k,$v)=>"$k: ".(is_scalar($v)?$v:json_encode($v,JSON_UNESCAPED_UNICODE)), array_keys($src), $src));
                                }
                            }

                            // Patient concerné : depuis la jointure (table patients) ou les valeurs JSON
                            $patLabel = '';
                            $patDossier = '';
                            if (!empty($log['pat_nom'])) {
                                $patLabel   = trim(strtoupper($log['pat_nom']) . ' ' . ($log['pat_prenom'] ?? ''));
                                $patDossier = $log['pat_dossier'] ?? '';
                            } else {
                                foreach ([$newArr, $oldArr] as $arr) {
                                    if (is_array($arr) && !empty($arr['patient'])) { $patLabel = $arr['patient']; break; }
                                }
                            }

                            // Appareil
                            $dev      = AuditService::parseUserAgent($log['user_agent'] ?? '');
                            $devIcon  = $dev['device']==='mobile'?'bi-phone':($dev['device']==='tablet'?'bi-tablet':'bi-display');

                            $hasDetail = $oldArr || $newArr || !empty($log['user_agent']) || $patLabel;
                        ?>
                            <tr class="audit-row <?= $hasDetail ? 'expandable' : '' ?>" <?= $hasDetail ? 'onclick="toggleAuditDetail('.$idx.')" style="cursor:pointer;"' : '' ?>>
                                <td class="ps-3 text-center">
                                    <?php if ($hasDetail): ?>
                                    <i class="bi bi-chevron-right audit-chevron" id="chev-<?= $idx ?>" style="font-size:.7rem;color:#94a3b8;transition:transform .15s;"></i>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="fw-bold text-dark"><?= date('d/m/Y', strtotime($log['created_at'])) ?></span>
                                    <br><small class="text-muted"><?= date('H:i:s', strtotime($log['created_at'])) ?></small>
                                </td>
                                <td>
                                    <?php if ($log['nom']): ?>
                                        <span class="fw-bold"><?= htmlspecialchars($log['prenom'].' '.$log['nom']) ?></span>
                                        <br><small class="text-muted">@<?= htmlspecialchars($log['username'] ?? '') ?></small>
                                    <?php else: ?>
                                        <span class="text-muted fst-italic"><i class="bi bi-robot me-1"></i>Système</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-<?= $ac ?>-subtle text-<?= $ac ?> border border-<?= $ac ?>-subtle fw-bold" style="font-size:.7rem;">
                                        <?= htmlspecialchars($aLabel) ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="d-inline-flex align-items-center gap-1" style="font-size:.78rem;color:#475569;font-weight:600;">
                                        <i class="bi <?= htmlspecialchars($mod['icon']) ?>" style="color:#7c3aed;"></i>
                                        <?= htmlspecialchars($modLbl) ?>
                                    </span>
                                    <?php if ($patLabel): ?>
                                    <br><span class="d-inline-flex align-items-center gap-1 mt-1" style="font-size:.72rem;background:#eff6ff;color:#1d4ed8;border:1px solid #bfdbfe;border-radius:20px;padding:1px 8px;font-weight:600;">
                                        <i class="bi bi-person-fill"></i><?= htmlspecialchars($patLabel) ?><?= $patDossier ? ' · '.htmlspecialchars($patDossier) : '' ?>
                                    </span>
                                    <?php elseif (!empty($log['record_id'])): ?>
                                    <br><small class="text-muted">#<?= (int)$log['record_id'] ?></small>
                                    <?php endif; ?>
                                </td>
                                <td style="max-width:320px;">
                                    <?php if ($descTxt): ?>
                                    <small class="text-dark" style="display:block;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;" title="<?= htmlspecialchars($descTxt) ?>">
                                        <?= htmlspecialchars(mb_substr($descTxt, 0, 90)) ?><?= mb_strlen($descTxt) > 90 ? '…' : '' ?>
                                    </small>
                                    <?php else: ?>
                                    <span class="text-muted">—</span>
                                    <?php endif; ?>
                                </td>
                                <td class="pe-4">
                                    <small class="d-inline-flex align-items-center gap-1 text-muted" title="<?= htmlspecialchars($dev['browser'].' · '.$dev['os']) ?>">
                                        <i class="bi <?= $devIcon ?>"></i><?= htmlspecialchars($dev['browser']) ?>
                                    </small>
                                    <br><small class="text-muted font-monospace"><?= htmlspecialchars($log['ip_address'] ?? '—') ?></small>
                                </td>
                            </tr>
                            <?php if ($hasDetail): ?>
                            <tr class="audit-detail-row" id="detail-<?= $idx ?>" style="display:none;">
                                <td colspan="7" style="background:#f8fafc;padding:0;">
                                    <div style="padding:16px 24px 16px 56px;">
                                        <div class="row g-3">
                                            <?php if ($oldArr || $newArr): ?>
                                            <div class="col-md-8">
                                                <div class="fw-bold text-secondary mb-2" style="font-size:.72rem;text-transform:uppercase;letter-spacing:.5px;">
                                                    <i class="bi bi-arrow-left-right me-1"></i>Détail des données
                                                </div>
                                                <table class="table table-sm mb-0" style="font-size:.78rem;">
                                                    <thead>
                                                        <tr style="border-bottom:1px solid #e2e8f0;">
                                                            <th style="width:30%;color:#94a3b8;font-weight:600;">Champ</th>
                                                            <?php if ($oldArr): ?><th style="color:#dc2626;font-weight:600;">Avant</th><?php endif; ?>
                                                            <th style="color:#16a34a;font-weight:600;"><?= $oldArr ? 'Après' : 'Valeur' ?></th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php
                                                        $allKeys = array_unique(array_merge(
                                                            $oldArr ? array_keys($oldArr) : [],
                                                            $newArr ? array_keys($newArr) : []
                                                        ));
                                                        foreach ($allKeys as $k):
                                                            $ov = $oldArr[$k] ?? null;
                                                            $nv = $newArr[$k] ?? null;
                                                            $ovS = is_scalar($ov) ? (string)$ov : ($ov===null?'':json_encode($ov,JSON_UNESCAPED_UNICODE));
                                                            $nvS = is_scalar($nv) ? (string)$nv : ($nv===null?'':json_encode($nv,JSON_UNESCAPED_UNICODE));
                                                            $changed = $oldArr && $newArr && $ovS !== $nvS;
                                                        ?>
                                                        <tr style="border-bottom:1px solid #f1f5f9;<?= $changed?'background:#fffbeb;':'' ?>">
                                                            <td class="text-muted fw-semibold"><?= htmlspecialchars($k) ?></td>
                                                            <?php if ($oldArr): ?>
                                                            <td style="color:#991b1b;<?= $changed?'text-decoration:line-through;':'' ?>"><?= htmlspecialchars(mb_substr($ovS,0,200)) ?: '—' ?></td>
                                                            <?php endif; ?>
                                                            <td style="color:#166534;font-weight:<?= $changed?'700':'400' ?>;"><?= htmlspecialchars(mb_substr($nvS,0,200)) ?: '—' ?></td>
                                                        </tr>
                                                        <?php endforeach; ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                            <?php endif; ?>
                                            <div class="col-md-4">
                                                <div class="fw-bold text-secondary mb-2" style="font-size:.72rem;text-transform:uppercase;letter-spacing:.5px;">
                                                    <i class="bi bi-info-circle me-1"></i>Contexte technique
                                                </div>
                                                <ul class="list-unstyled mb-0" style="font-size:.78rem;color:#475569;">
                                                    <?php if ($patLabel): ?>
                                                    <li class="mb-1"><i class="bi bi-person-fill me-1" style="color:#1d4ed8;"></i><strong>Patient :</strong> <span style="color:#1d4ed8;font-weight:600;"><?= htmlspecialchars($patLabel) ?><?= $patDossier ? ' ('.htmlspecialchars($patDossier).')' : '' ?></span></li>
                                                    <?php endif; ?>
                                                    <li class="mb-1"><i class="bi <?= $devIcon ?> me-1 text-muted"></i><strong>Navigateur :</strong> <?= htmlspecialchars($dev['browser']) ?></li>
                                                    <li class="mb-1"><i class="bi bi-window me-1 text-muted"></i><strong>Système :</strong> <?= htmlspecialchars($dev['os']) ?></li>
                                                    <li class="mb-1"><i class="bi bi-hdd-network me-1 text-muted"></i><strong>IP :</strong> <span class="font-monospace"><?= htmlspecialchars($log['ip_address'] ?? '—') ?></span></li>
                                                    <li class="mb-1"><i class="bi bi-tag me-1 text-muted"></i><strong>Table :</strong> <code style="color:#7c3aed;"><?= htmlspecialchars($log['table_name'] ?? '—') ?></code></li>
                                                    <li><i class="bi bi-clock-history me-1 text-muted"></i><strong>Horodatage :</strong> <?= date('d/m/Y H:i:s', strtotime($log['created_at'])) ?></li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            <?php endif; ?>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
        </div><!-- /table-responsive -->

        <script>
        function toggleAuditDetail(idx) {
            const row  = document.getElementById('detail-' + idx);
            const chev = document.getElementById('chev-' + idx);
            if (!row) return;
            const open = row.style.display !== 'none';
            row.style.display = open ? 'none' : 'table-row';
            if (chev) chev.style.transform = open ? 'rotate(0deg)' : 'rotate(90deg)';
        }
        </script>
        <style>
        .audit-row.expandable:hover { background:#f8fafc; }
        .audit-table td { vertical-align: top; }
        </style>

        <!-- PAGINATION -->
        <?php if ($total_pages > 1):
            $qBase = http_build_query(array_filter([
                'tab'       => 'journal',
                'date_from' => $filters['date_from'],
                'date_to'   => $filters['date_to'],
                'action'    => $filters['action'] !== 'ALL' ? $filters['action'] : '',
                'table'     => $filters['table'] !== 'ALL' ? $filters['table'] : '',
                'user_id'   => $filters['user_id'],
                'ip'        => $filters['ip'],
                'search'    => $filters['search'],
            ]));
        ?>
        <div class="card-footer border-0 bg-transparent p-3">
            <div class="d-flex justify-content-between align-items-center">
                <small class="text-muted">Page <?= $page ?> / <?= $total_pages ?> — <?= number_format($total_rows) ?> entrées</small>
                <nav>
                    <ul class="pagination pagination-sm mb-0">
                        <?php if ($page > 1): ?>
                        <li class="page-item"><a class="page-link rounded-3 me-1" href="?<?= $qBase ?>&page=<?= $page-1 ?>"><i class="bi bi-chevron-left"></i></a></li>
                        <?php endif; ?>
                        <?php for ($p = max(1,$page-2); $p <= min($total_pages,$page+2); $p++): ?>
                        <li class="page-item <?= $p===$page?'active':'' ?>">
                            <a class="page-link rounded-3 me-1" href="?<?= $qBase ?>&page=<?= $p ?>"><?= $p ?></a>
                        </li>
                        <?php endfor; ?>
                        <?php if ($page < $total_pages): ?>
                        <li class="page-item"><a class="page-link rounded-3" href="?<?= $qBase ?>&page=<?= $page+1 ?>"><i class="bi bi-chevron-right"></i></a></li>
                        <?php endif; ?>
                    </ul>
                </nav>
            </div>
        </div>
        <?php endif; ?>
    </div><!-- /adm-card -->

</div><!-- /pane-journal -->


<!-- ═══════════════════════════════════════════════════════════════════════════
     ONGLET : UTILISATEURS
═══════════════════════════════════════════════════════════════════════════ -->
<div class="tab-pane fade <?= $activeTab==='users'?'show active':'' ?>" id="pane-users" role="tabpanel">

    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-header bg-transparent border-0 pt-3 px-4 pb-2 d-flex align-items-center justify-content-between">
            <span class="fw-bold text-dark" style="font-size:.9rem;">
                <i class="bi bi-people-fill me-2 text-success"></i>Taux d'utilisation par utilisateur
            </span>
            <small class="text-muted">Top 30 · basé sur toutes les actions enregistrées</small>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" style="font-size:.82rem;">
                    <thead style="background:#f8fafc;">
                        <tr class="text-muted" style="font-size:.7rem;text-transform:uppercase;letter-spacing:.06em;">
                            <th class="ps-4 py-3">#</th>
                            <th class="py-3">Utilisateur</th>
                            <th class="py-3">Rôle</th>
                            <th class="py-3" style="width:180px;">Taux d'utilisation</th>
                            <th class="py-3 text-end">Actions</th>
                            <th class="py-3 text-end">Jours actifs</th>
                            <th class="py-3 text-end">Connexions</th>
                            <th class="py-3 text-center">Consultations</th>
                            <th class="py-3 pe-4">Dernière action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($userStats)): ?>
                        <tr><td colspan="9" class="text-center py-4 text-muted">Aucune donnée.</td></tr>
                        <?php else:
                        $roleColors = [
                            'ADMIN'=>'danger','MEDECIN'=>'primary','MEDECIN_CHEF'=>'primary',
                            'INFIRMIER'=>'success','SPECIALISTE'=>'info','PHARMACIEN'=>'warning',
                            'LABORANTIN'=>'secondary','SECRETAIRE'=>'dark',
                        ];
                        foreach ($userStats as $i => $us):
                            $rc = $roleColors[$us['role'] ?? ''] ?? 'secondary';
                            $taux = (float)$us['taux'];
                            $barColor = $taux >= 20 ? '#3b82f6' : ($taux >= 5 ? '#10b981' : '#94a3b8');
                        ?>
                        <tr>
                            <td class="ps-4 text-muted fw-bold" style="font-size:.75rem;"><?= $i+1 ?></td>
                            <td>
                                <?php if ($us['nom']): ?>
                                    <span class="fw-semibold"><?= htmlspecialchars($us['prenom'].' '.$us['nom']) ?></span>
                                    <br><small class="text-muted">@<?= htmlspecialchars($us['username'] ?? '') ?></small>
                                <?php else: ?>
                                    <span class="text-muted fst-italic">ID #<?= $us['user_id'] ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge bg-<?= $rc ?>-subtle text-<?= $rc ?> border border-<?= $rc ?>-subtle" style="font-size:.68rem;">
                                    <?= htmlspecialchars($us['role'] ?? '—') ?>
                                </span>
                            </td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="flex-grow-1 bg-light rounded-pill" style="height:7px;min-width:80px;">
                                        <div class="rounded-pill" style="height:7px;width:<?= min(100,$taux*3) ?>%;background:<?= $barColor ?>;transition:width .4s;"></div>
                                    </div>
                                    <span class="fw-bold" style="font-size:.75rem;min-width:40px;color:<?= $barColor ?>;"><?= $taux ?>%</span>
                                </div>
                            </td>
                            <td class="text-end fw-bold"><?= number_format($us['nb_actions']) ?></td>
                            <td class="text-end text-muted"><?= $us['jours_actifs'] ?> j</td>
                            <td class="text-end text-muted"><?= (int)$us['nb_logins'] ?></td>
                            <td class="text-center">
                                <?php
                                $medRoles = ['MEDECIN','MEDECIN_CHEF','SPECIALISTE','GYNECO','PEDIATRE','CHIRURGIEN'];
                                $isMed    = in_array($us['role'] ?? '', $medRoles);
                                $co       = $us['consult'] ?? null;
                                if ($isMed && $co && (int)$co['nb_total'] > 0):
                                ?>
                                    <span class="d-inline-block" title="Total : <?= (int)$co['nb_total'] ?> · Ce mois : <?= (int)$co['nb_mois'] ?> · Auj. : <?= (int)$co['nb_jour'] ?>">
                                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle fw-bold" style="font-size:.72rem;">
                                            <?= number_format((int)$co['nb_total']) ?>
                                        </span>
                                        <small class="d-block text-muted" style="font-size:.65rem;line-height:1.2;">
                                            <?= (int)$co['nb_mois'] ?> ce mois · <?= (int)$co['nb_jour'] ?> auj.
                                        </small>
                                        <?php if ($co['derniere_consult']): ?>
                                        <small class="d-block text-muted" style="font-size:.63rem;">
                                            dern. <?= date('d/m/Y', strtotime($co['derniere_consult'])) ?>
                                        </small>
                                        <?php endif; ?>
                                    </span>
                                <?php elseif ($isMed): ?>
                                    <small class="text-muted">0</small>
                                <?php else: ?>
                                    <small class="text-muted">—</small>
                                <?php endif; ?>
                            </td>
                            <td class="pe-4">
                                <?php if ($us['derniere_action']): ?>
                                    <small><?= date('d/m/Y H:i', strtotime($us['derniere_action'])) ?></small>
                                <?php else: ?>
                                    <small class="text-muted">—</small>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div><!-- /pane-users -->


<!-- ═══════════════════════════════════════════════════════════════════════════
     ONGLET : SÉCURITÉ
═══════════════════════════════════════════════════════════════════════════ -->
<div class="tab-pane fade <?= $activeTab==='security'?'show active':'' ?>" id="pane-security" role="tabpanel">

    <!-- KPI sécurité -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4" style="border-left:4px solid #ef4444 !important;">
                <div class="card-body p-3 d-flex align-items-center gap-3">
                    <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0"
                         style="width:42px;height:42px;background:#fef2f2;">
                        <i class="bi bi-shield-exclamation" style="font-size:1.2rem;color:#ef4444;"></i>
                    </div>
                    <div>
                        <div class="fw-bold" style="font-size:1.35rem;color:#ef4444;line-height:1;"><?= $kpi['login_failures'] ?></div>
                        <div class="text-muted" style="font-size:.72rem;">Échecs de connexion (7j)</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4" style="border-left:4px solid #f59e0b !important;">
                <div class="card-body p-3 d-flex align-items-center gap-3">
                    <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0"
                         style="width:42px;height:42px;background:#fffbeb;">
                        <i class="bi bi-ban" style="font-size:1.2rem;color:#f59e0b;"></i>
                    </div>
                    <div>
                        <div class="fw-bold" style="font-size:1.35rem;color:#f59e0b;line-height:1;"><?= $kpi['access_denied'] ?></div>
                        <div class="text-muted" style="font-size:.72rem;">Accès refusés (7j)</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4" style="border-left:4px solid #6366f1 !important;">
                <div class="card-body p-3 d-flex align-items-center gap-3">
                    <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0"
                         style="width:42px;height:42px;background:#eef2ff;">
                        <i class="bi bi-geo-alt-fill" style="font-size:1.2rem;color:#6366f1;"></i>
                    </div>
                    <div>
                        <div class="fw-bold" style="font-size:1.35rem;color:#6366f1;line-height:1;"><?= count($topIps) ?></div>
                        <div class="text-muted" style="font-size:.72rem;">IPs distinctes</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Graphique tentatives échouées + Top IPs -->
    <div class="row g-3 mb-4">
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-header bg-transparent border-0 pb-0 pt-3 px-4">
                    <span class="fw-bold text-dark" style="font-size:.9rem;">
                        <i class="bi bi-graph-down-arrow me-2 text-danger"></i>Échecs de connexion — 30 jours
                    </span>
                </div>
                <div class="card-body px-3 pb-3 pt-2">
                    <?php if (empty($loginByDay)): ?>
                    <div class="text-center text-muted py-4">
                        <i class="bi bi-check-circle d-block fs-2 mb-2 text-success opacity-50"></i>
                        Aucun échec de connexion récent.
                    </div>
                    <?php else: ?>
                    <canvas id="chartLoginFail" height="100"></canvas>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-header bg-transparent border-0 pb-0 pt-3 px-4">
                    <span class="fw-bold text-dark" style="font-size:.9rem;">
                        <i class="bi bi-geo-alt me-2 text-indigo" style="color:#6366f1;"></i>Top IPs
                    </span>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($topIps)): ?>
                    <div class="text-center text-muted py-4">Aucune donnée.</div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0" style="font-size:.78rem;">
                            <thead style="background:#f8fafc;">
                                <tr class="text-muted" style="font-size:.68rem;text-transform:uppercase;">
                                    <th class="ps-3 py-2">Adresse IP</th>
                                    <th class="py-2 text-end">Total</th>
                                    <th class="py-2 text-end pe-3">Échecs</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($topIps as $ip):
                                    $danger = (int)$ip['nb_echecs'] > 5;
                                ?>
                                <tr>
                                    <td class="ps-3 font-monospace" style="font-size:.75rem;"><?= htmlspecialchars($ip['ip_address']) ?></td>
                                    <td class="text-end"><?= $ip['nb_total'] ?></td>
                                    <td class="text-end pe-3">
                                        <?php if ((int)$ip['nb_echecs'] > 0): ?>
                                        <span class="badge bg-<?= $danger?'danger':'warning' ?>-subtle text-<?= $danger?'danger':'warning' ?> border" style="font-size:.65rem;">
                                            <?= $ip['nb_echecs'] ?> échec<?= $ip['nb_echecs']>1?'s':'' ?>
                                        </span>
                                        <?php else: ?>
                                        <span class="text-muted">0</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Dernières tentatives de connexion -->
    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-header bg-transparent border-0 pt-3 px-4 pb-2">
            <span class="fw-bold text-dark" style="font-size:.9rem;">
                <i class="bi bi-person-lock me-2 text-warning"></i>Dernières tentatives de connexion
            </span>
            <small class="text-muted ms-2">(100 dernières)</small>
        </div>
        <div class="card-body p-0">
            <?php if (empty($loginAttempts)): ?>
            <div class="text-center text-muted py-4">
                <i class="bi bi-journal-x d-block fs-2 mb-2 opacity-25"></i>
                Aucune tentative enregistrée (table login_attempts vide ou absente).
            </div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" style="font-size:.8rem;">
                    <thead style="background:#f8fafc;">
                        <tr class="text-muted" style="font-size:.7rem;text-transform:uppercase;letter-spacing:.06em;">
                            <th class="ps-4 py-3">Date / Heure</th>
                            <th class="py-3">Nom d'utilisateur</th>
                            <th class="py-3">IP</th>
                            <th class="py-3">Résultat</th>
                            <th class="py-3 pe-4">Motif d'échec</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($loginAttempts as $la): ?>
                        <tr>
                            <td class="ps-4">
                                <span class="fw-bold text-dark"><?= date('d/m/Y', strtotime($la['created_at'])) ?></span>
                                <br><small class="text-muted"><?= date('H:i:s', strtotime($la['created_at'])) ?></small>
                            </td>
                            <td>
                                <span class="font-monospace" style="font-size:.78rem;"><?= htmlspecialchars($la['username'] ?? '—') ?></span>
                            </td>
                            <td><small class="text-muted font-monospace"><?= htmlspecialchars($la['ip_address'] ?? '—') ?></small></td>
                            <td>
                                <?php if ($la['success']): ?>
                                <span class="badge bg-success-subtle text-success border border-success-subtle" style="font-size:.7rem;"><i class="bi bi-check-circle me-1"></i>Succès</span>
                                <?php else: ?>
                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle" style="font-size:.7rem;"><i class="bi bi-x-circle me-1"></i>Échec</span>
                                <?php endif; ?>
                            </td>
                            <td class="pe-4"><small class="text-muted"><?= htmlspecialchars($la['failure_reason'] ?? '—') ?></small></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>

</div><!-- /pane-security -->

            </div><!-- /tab-content -->
        </div><!-- /admin-page-content -->
    </div><!-- /admin-main -->
</div><!-- /admin-shell -->

<!-- ═══════════════════════════════════════════════════════════════════════════
     SCRIPTS CHART.JS
═══════════════════════════════════════════════════════════════════════════ -->
<script>
(function() {
    // Données PHP → JS
    const dayLabels  = <?= json_encode(array_column($days30, 'label')) ?>;
    const dayData    = <?= json_encode(array_column($days30, 'nb')) ?>;
    const hourLabels = Array.from({length:24}, (_,i) => i+'h');
    const hourData   = <?= json_encode(array_values($hourData)) ?>;
    const actionLabels = <?= json_encode(array_keys($actionBreakdown)) ?>;
    const actionData   = <?= json_encode(array_values($actionBreakdown)) ?>;

    <?php
    $days30sec = [];
    for ($i = 29; $i >= 0; $i--) {
        $d = date('Y-m-d', strtotime("-$i days"));
        $days30sec[] = ['label'=>date('d/m', strtotime($d)), 'nb'=>$loginByDay[$d] ?? 0];
    }
    ?>
    const failLabels = <?= json_encode(array_column($days30sec, 'label')) ?>;
    const failData   = <?= json_encode(array_column($days30sec, 'nb')) ?>;

    const palette = ['#3b82f6','#ef4444','#10b981','#f59e0b','#8b5cf6','#06b6d4','#f97316','#64748b','#ec4899','#14b8a6'];

    Chart.defaults.font.family = "'Inter','Segoe UI',sans-serif";
    Chart.defaults.font.size   = 11;

    // Activité par jour
    const ctxDay = document.getElementById('chartActivityDay');
    if (ctxDay) {
        new Chart(ctxDay, {
            type: 'line',
            data: {
                labels: dayLabels,
                datasets: [{
                    label: 'Actions',
                    data: dayData,
                    borderColor: '#3b82f6',
                    backgroundColor: 'rgba(59,130,246,0.08)',
                    borderWidth: 2,
                    pointRadius: 3,
                    pointBackgroundColor: '#3b82f6',
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { display: false } },
                scales: {
                    x: { grid: { display: false }, ticks: { maxTicksLimit: 10 } },
                    y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,.05)' } }
                }
            }
        });
    }

    // Activité par heure
    const ctxHour = document.getElementById('chartHourBar');
    if (ctxHour) {
        new Chart(ctxHour, {
            type: 'bar',
            data: {
                labels: hourLabels,
                datasets: [{
                    label: 'Actions',
                    data: hourData,
                    backgroundColor: hourData.map(v => {
                        const max = Math.max(...hourData, 1);
                        const a   = 0.3 + 0.7 * v / max;
                        return `rgba(16,185,129,${a.toFixed(2)})`;
                    }),
                    borderRadius: 4,
                    borderSkipped: false,
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { display: false } },
                scales: {
                    x: { grid: { display: false } },
                    y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,.05)' } }
                }
            }
        });
    }

    // Répartition doughnut
    const ctxDoughnut = document.getElementById('chartActionDoughnut');
    if (ctxDoughnut) {
        new Chart(ctxDoughnut, {
            type: 'doughnut',
            data: {
                labels: actionLabels,
                datasets: [{
                    data: actionData,
                    backgroundColor: palette,
                    borderWidth: 2,
                    hoverOffset: 6,
                }]
            },
            options: {
                responsive: true,
                cutout: '65%',
                plugins: {
                    legend: { position: 'bottom', labels: { boxWidth: 10, padding: 10, font: { size: 10 } } }
                }
            }
        });
    }

    // Échecs connexion
    const ctxFail = document.getElementById('chartLoginFail');
    if (ctxFail) {
        new Chart(ctxFail, {
            type: 'bar',
            data: {
                labels: failLabels,
                datasets: [{
                    label: 'Échecs',
                    data: failData,
                    backgroundColor: 'rgba(239,68,68,0.7)',
                    borderRadius: 3,
                    borderSkipped: false,
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { display: false } },
                scales: {
                    x: { grid: { display: false }, ticks: { maxTicksLimit: 10 } },
                    y: { beginAtZero: true, ticks: { stepSize: 1 }, grid: { color: 'rgba(0,0,0,.05)' } }
                }
            }
        });
    }
})();
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
