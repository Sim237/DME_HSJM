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

// Sécurisation des variables
$total_refs      = $total_refs      ?? 0;
$total_alerte    = $total_alerte    ?? 0;
$processed_today = $processed_today ?? 0;
$pending_count   = $pending_count   ?? 0;
$pending_orders  = $pending_orders  ?? [];
$low_stock       = $low_stock       ?? [];
$historique      = $historique      ?? [];
$hist_total      = $hist_total      ?? 0;
// Filtres
$dash_filterDate = $dash_filterDate ?? date('Y-m-d');
$dash_searchQ    = $dash_searchQ    ?? '';
$dash_isToday    = $dash_isToday    ?? true;
$dash_hasFilter  = !$dash_isToday || $dash_searchQ !== '';

// Préfixe/titre selon le rôle du prescripteur
if (!function_exists('prescripteurPrefixe')) {
    function prescripteurPrefixe($role) {
        $r = strtoupper(trim($role ?? ''));
        $map = [
            'MEDECIN'     => 'Dr.',
            'GENERALISTE' => 'Dr.',
            'SPECIALISTE' => 'Dr.',
            'GYNECO'      => 'Dr.',
            'PEDIATRE'    => 'Dr.',
            'CHIRURGIEN'  => 'Dr.',
            'ANESTHESISTE'=> 'Dr.',
            'CARDIOLOGUE' => 'Dr.',
            'RADIOLOGUE'  => 'Dr.',
            'SAGE_FEMME'  => 'SF',
            'INFIRMIER'   => 'Inf.',
            'INFIRMIER_CONSULTANT' => 'Inf.',
            'INFIRMIER_MAJOR'      => 'Major',
            'MAJOR'       => 'Major',
            'INTERNE'     => 'Dr. (interne)',
        ];
        return $map[$r] ?? '';
    }
    function prescripteurRoleLabel($role) {
        $r = strtoupper(trim($role ?? ''));
        $labels = [
            'MEDECIN'=>'Médecin','GENERALISTE'=>'Médecin généraliste','SPECIALISTE'=>'Spécialiste',
            'GYNECO'=>'Gynécologue','PEDIATRE'=>'Pédiatre','CHIRURGIEN'=>'Chirurgien',
            'ANESTHESISTE'=>'Anesthésiste','CARDIOLOGUE'=>'Cardiologue','RADIOLOGUE'=>'Radiologue',
            'SAGE_FEMME'=>'Sage-femme','INFIRMIER'=>'Infirmier(ère)','INFIRMIER_CONSULTANT'=>'Infirmier consultant',
            'INFIRMIER_MAJOR'=>'Infirmier major','MAJOR'=>'Major','INTERNE'=>'Médecin interne',
        ];
        return $labels[$r] ?? ucfirst(strtolower(str_replace('_',' ', $r)));
    }
}
?>

<style>
    /* ══════════════════════════════════════════════
       PHARMACIE CENTRALE — Rouge · Blanc · Bleu
    ══════════════════════════════════════════════ */
    :root {
        --red:      #dc2626;
        --red-dark: #b91c1c;
        --red-soft: #fee2e2;
        --red-mid:  #fca5a5;
        --blue:     #1d4ed8;
        --blue-soft:#dbeafe;
        --blue-mid: #93c5fd;
        --white:    #ffffff;
        --bg:       #fafafa;
        --border:   #f0f0f0;
        --text:     #1a1a2e;
        --muted:    #6b7280;
    }

    /* ── LAYOUT ── */
    .sidebar { display: none !important; }
    main, .col-md-10, .ms-sm-auto {
        margin-left: 0 !important; width: 100% !important;
        flex: 0 0 100% !important; max-width: 100% !important; padding: 0 !important;
    }
    body { background: var(--bg); font-family: 'Segoe UI', system-ui, sans-serif; color: var(--text); }

    /* ── TOPBAR ── */
    .ph-topbar {
        background: linear-gradient(135deg, var(--red-dark) 0%, var(--red) 60%, #ef4444 100%);
        padding: 14px 28px;
        display: flex; align-items: center; justify-content: space-between;
        position: sticky; top: 0; z-index: 100;
        box-shadow: 0 4px 20px rgba(220,38,38,.35);
    }
    .ph-logo-text { font-size: 1.2rem; font-weight: 900; color: #fff; letter-spacing: -.3px; }
    .ph-logo-text span { color: #fde68a; }
    .ph-subtitle { font-size: .68rem; color: rgba(255,255,255,.65); font-weight: 600; text-transform: uppercase; letter-spacing: 1.2px; margin-top: 2px; }

    #clock {
        font-family: 'Courier New', monospace;
        font-size: 1.75rem; font-weight: 900;
        color: #fff; letter-spacing: 3px;
        text-shadow: 0 0 20px rgba(255,255,255,.3);
    }

    .ph-btn {
        display: inline-flex; align-items: center; gap: 6px;
        padding: 8px 18px; border-radius: 10px; font-weight: 700;
        font-size: .78rem; text-decoration: none; border: none; cursor: pointer; transition: all .18s;
    }
    .ph-btn-outline { background: rgba(255,255,255,.18); color: #fff; border: 1px solid rgba(255,255,255,.35); }
    .ph-btn-outline:hover { background: rgba(255,255,255,.3); color: #fff; }
    .ph-btn-danger  { background: rgba(0,0,0,.2); color: #fca5a5; border: 1px solid rgba(255,255,255,.2); }
    .ph-btn-danger:hover { background: rgba(0,0,0,.35); color: #fff; }

    /* ── CONTENU ── */
    .ph-main { padding: 24px 28px; }

    /* ── KPI CARDS ── */
    .kpi-grid { display: grid; grid-template-columns: repeat(4,1fr); gap: 16px; margin-bottom: 24px; }
    .kpi-card {
        background: var(--white); border-radius: 18px; padding: 22px 24px;
        border: 1px solid var(--border); position: relative; overflow: hidden;
        box-shadow: 0 2px 12px rgba(0,0,0,.06); transition: transform .2s, box-shadow .2s;
    }
    .kpi-card:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(0,0,0,.1); }

    /* Bande couleur gauche */
    .kpi-card::after {
        content: ''; position: absolute; top: 0; left: 0; bottom: 0;
        width: 4px; border-radius: 18px 0 0 18px;
    }
    .kpi-card.kpi-red::after    { background: var(--red); }
    .kpi-card.kpi-blue::after   { background: var(--blue); }
    .kpi-card.kpi-green::after  { background: #16a34a; }
    .kpi-card.kpi-amber::after  { background: #d97706; }

    .kpi-value { font-size: 2.5rem; font-weight: 900; line-height: 1; margin-bottom: 5px; }
    .kpi-red   .kpi-value { color: var(--red); }
    .kpi-blue  .kpi-value { color: var(--blue); }
    .kpi-green .kpi-value { color: #16a34a; }
    .kpi-amber .kpi-value { color: #d97706; }

    .kpi-label { font-size: .72rem; color: var(--muted); font-weight: 700; text-transform: uppercase; letter-spacing: .9px; }
    .kpi-icon { position: absolute; right: 18px; top: 50%; transform: translateY(-50%); font-size: 2.8rem; opacity: .07; }
    .kpi-red   .kpi-icon { color: var(--red); }
    .kpi-blue  .kpi-icon { color: var(--blue); }

    /* ── GRID PRINCIPAL ── */
    .ph-grid { display: grid; grid-template-columns: 1fr 320px; gap: 20px; }

    /* ── PANEL ── */
    .ph-panel {
        background: var(--white); border-radius: 20px;
        border: 1px solid var(--border); overflow: hidden;
        box-shadow: 0 2px 12px rgba(0,0,0,.05);
    }
    .ph-panel-header {
        padding: 15px 22px;
        border-bottom: 2px solid var(--red-soft);
        display: flex; align-items: center; justify-content: space-between;
        background: linear-gradient(90deg, #fff5f5 0%, #fff 100%);
    }
    .ph-panel-title {
        font-size: .78rem; font-weight: 800; color: var(--red);
        text-transform: uppercase; letter-spacing: .8px;
        display: flex; align-items: center; gap: 7px;
    }

    .badge-live {
        background: var(--blue); color: #fff;
        padding: 4px 12px; border-radius: 20px;
        font-size: .7rem; font-weight: 800; letter-spacing: .8px;
        animation: pulse-live 2s infinite;
    }
    @keyframes pulse-live { 0%,100%{opacity:1} 50%{opacity:.65} }

    /* ── TABLE ── */
    .ph-table { width: 100%; border-collapse: collapse; }
    .ph-table thead th {
        background: #fff5f5; color: var(--red);
        font-size: .68rem; font-weight: 800;
        text-transform: uppercase; letter-spacing: 1px;
        padding: 11px 20px; border-bottom: 1px solid var(--red-soft);
    }
    .ph-table tbody tr { border-bottom: 1px solid #fafafa; transition: background .12s; }
    .ph-table tbody tr:hover { background: #fff5f5; }
    .ph-table tbody td { padding: 13px 20px; color: #374151; }

    .patient-name { font-weight: 700; color: var(--text); font-size: .9rem; }
    .patient-ref  { font-size: .72rem; color: #9ca3af; font-weight: 500; margin-top: 1px; }
    .patient-sage {
        display: inline-flex; align-items: center; gap: 4px; margin-top: 3px;
        background: #fffbeb; border: 1px solid #fde68a;
        color: #92400e; font-size: .68rem; font-weight: 700;
        padding: 2px 8px; border-radius: 20px;
    }
    .medecin-name { color: var(--blue); font-weight: 700; font-size: .83rem; }

    .badge-signe {
        display: inline-block; margin-top: 3px;
        background: #dcfce7; border: 1px solid #86efac;
        color: #166534; font-size: .65rem; font-weight: 700;
        padding: 2px 8px; border-radius: 20px;
    }

    .timer-normal {
        background: var(--blue-soft); color: var(--blue);
        padding: 4px 12px; border-radius: 20px;
        font-size: .75rem; font-weight: 700;
        border: 1px solid var(--blue-mid);
    }
    .timer-alert {
        background: var(--red-soft); color: var(--red);
        padding: 4px 12px; border-radius: 20px;
        font-size: .75rem; font-weight: 700;
        border: 1px solid var(--red-mid);
        animation: blink-alert 1.8s infinite;
    }
    @keyframes blink-alert { 50%{opacity:.55} }

    .btn-preparer {
        background: linear-gradient(135deg, var(--red) 0%, #ef4444 100%);
        color: #fff; padding: 7px 18px; border-radius: 10px;
        font-size: .78rem; font-weight: 700; border: none;
        text-decoration: none; display: inline-flex; align-items: center; gap: 6px;
        box-shadow: 0 3px 10px rgba(220,38,38,.3); transition: all .18s;
    }
    .btn-preparer:hover { transform: translateY(-1px); box-shadow: 0 6px 18px rgba(220,38,38,.4); color: #fff; background: linear-gradient(135deg, var(--red-dark) 0%, var(--red) 100%); }

    .empty-state { padding: 48px 20px; text-align: center; color: #9ca3af; }
    .empty-state i { font-size: 2.2rem; opacity: .2; margin-bottom: 10px; display: block; }
    .empty-state span { font-size: .83rem; font-weight: 600; }

    /* ── PANEL ALERTES — header rouge ── */
    .ph-grid > .ph-panel:last-child .ph-panel-header {
        background: linear-gradient(90deg, #fff5f5 0%, #fff 100%);
    }
    .alert-item {
        padding: 12px 18px; border-bottom: 1px solid #fafafa;
        display: flex; align-items: center; justify-content: space-between; transition: background .12s;
    }
    .alert-item:hover { background: #fff5f5; }
    .alert-item:last-child { border-bottom: none; }
    .alert-med-name { font-size: .85rem; font-weight: 700; color: var(--text); }
    .alert-med-info { font-size: .7rem; color: #9ca3af; margin-top: 2px; }
    .badge-rupture {
        background: var(--red-soft); color: var(--red);
        padding: 3px 10px; border-radius: 20px;
        font-size: .74rem; font-weight: 700;
        border: 1px solid var(--red-mid); flex-shrink: 0;
    }
    .badge-ok {
        background: #dcfce7; color: #16a34a;
        padding: 3px 10px; border-radius: 20px;
        font-size: .74rem; font-weight: 700;
        border: 1px solid #86efac; flex-shrink: 0;
    }

    .ph-panel-footer {
        padding: 13px 20px; border-top: 1px solid var(--red-soft);
        text-align: center; background: #fff5f5;
    }
    .ph-panel-footer a { color: var(--red); font-size: .8rem; font-weight: 700; text-decoration: none; }
    .ph-panel-footer a:hover { color: var(--red-dark); text-decoration: underline; }

    /* ── HISTORIQUE ── */
    .hist-section { margin-top: 24px; }
    .hist-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 14px; flex-wrap: wrap; gap: 12px; }
    .hist-title {
        font-size: .8rem; font-weight: 800; color: var(--red);
        text-transform: uppercase; letter-spacing: .8px;
        display: flex; align-items: center; gap: 8px;
    }
    .hist-count {
        background: #dcfce7; color: #16a34a;
        border: 1px solid #86efac; border-radius: 20px;
        font-size: .7rem; font-weight: 700; padding: 3px 12px;
    }
    .hist-filter { display: flex; align-items: center; gap: 8px; }
    .hist-filter input[type="date"] {
        background: #fff; border: 1px solid #e5e7eb;
        color: #374151; border-radius: 10px; padding: 7px 14px;
        font-size: .82rem; font-weight: 500; outline: none;
    }
    .hist-filter input[type="date"]:focus { border-color: var(--red-mid); box-shadow: 0 0 0 3px rgba(220,38,38,.1); }
    .hist-filter button {
        background: var(--red); color: #fff;
        border: none; border-radius: 10px;
        padding: 7px 18px; font-size: .8rem; font-weight: 700; cursor: pointer; transition: all .15s;
    }
    .hist-filter button:hover { background: var(--red-dark); }
    .hist-filter .btn-reset {
        background: #fff; color: #9ca3af;
        border: 1px solid #e5e7eb; border-radius: 10px;
        padding: 7px 12px; font-size: .8rem; cursor: pointer;
    }
    .hist-filter .btn-reset:hover { background: #f9fafb; color: var(--muted); }

    .badge-termine {
        background: #dcfce7; color: #16a34a; border: 1px solid #86efac;
        padding: 3px 10px; border-radius: 20px; font-size: .68rem; font-weight: 700;
    }
    .btn-voir {
        background: var(--blue-soft); color: var(--blue); border: 1px solid var(--blue-mid);
        border-radius: 8px; padding: 5px 13px; font-size: .76rem; font-weight: 700;
        text-decoration: none; display: inline-flex; align-items: center; gap: 5px; transition: all .15s;
    }
    .btn-voir:hover { background: #bfdbfe; transform: translateY(-1px); }
    .hist-nb-meds {
        display: inline-flex; align-items: center; justify-content: center;
        background: #f3f4f6; color: var(--muted);
        border-radius: 20px; padding: 3px 12px; font-size: .76rem; font-weight: 600;
    }

    /* ── Barre de filtres ── */
    .filter-bar {
        display: flex; align-items: center; gap: 10px; flex-wrap: wrap;
        padding: 12px 18px;
        background: #fff8f8;
        border-bottom: 1px solid #fde8e8;
    }
    .filter-bar label {
        font-size: .72rem; font-weight: 700; color: var(--red);
        text-transform: uppercase; letter-spacing: .06em; white-space: nowrap;
    }
    .filter-input {
        height: 34px; border: 1.5px solid #fca5a5; border-radius: 10px;
        padding: 0 12px; font-size: .83rem; color: var(--text);
        background: #fff; outline: none; transition: border-color .15s;
    }
    .filter-input:focus { border-color: var(--red); box-shadow: 0 0 0 3px rgba(220,38,38,.1); }
    .filter-input[type="date"] { width: 150px; }
    .filter-input[type="search"], .filter-input[type="text"] { width: 220px; }
    .filter-btn-today {
        height: 34px; padding: 0 14px; border-radius: 10px; border: none;
        background: var(--red-soft); color: var(--red);
        font-size: .78rem; font-weight: 700; cursor: pointer; transition: background .15s;
        display: inline-flex; align-items: center; gap: 5px;
    }
    .filter-btn-today:hover { background: #fecaca; }
    .filter-active-badge {
        background: var(--red); color: #fff;
        font-size: .68rem; font-weight: 700;
        padding: 2px 10px; border-radius: 20px;
        display: inline-flex; align-items: center; gap: 4px;
    }
    .badge-terminee {
        display: inline-flex; align-items: center; gap: 3px;
        background: #ecfdf5; color: #15803d; border: 1px solid #86efac;
        font-size: .65rem; font-weight: 700; padding: 2px 8px; border-radius: 20px;
    }
</style>

<main>

    <!-- TOPBAR -->
    <div class="ph-topbar">
        <div>
            <div class="ph-logo-text"><i class="bi bi-capsule me-2"></i>PHARMACIE <span>CENTRALE</span></div>
            <div class="ph-subtitle">Unité Logistique &bull; HSJM</div>
        </div>

        <div id="clock">00:00:00</div>

        <div class="d-flex gap-2">
            <a href="<?= BASE_URL ?>pharmacie/sage" class="ph-btn ph-btn-outline" style="text-decoration:none;">
                <i class="bi bi-arrow-repeat"></i> SYNCHRO SAGE
            </a>
            <a href="<?= BASE_URL ?>pharmacie/stock" class="ph-btn ph-btn-outline">
                <i class="bi bi-box-seam-fill"></i> INVENTAIRE
            </a>
            <a href="<?= BASE_URL ?>pharmacie/stocks" class="ph-btn ph-btn-outline" style="background:#0d6efd;color:#fff;border-color:#0d6efd;">
                <i class="bi bi-graph-up-arrow"></i> STOCKS IA
            </a>
            <a href="<?= BASE_URL ?>profil" class="ph-btn ph-btn-outline">
                <i class="bi bi-person-circle"></i> PROFIL
            </a>
            <a href="<?= BASE_URL ?>logout" class="ph-btn ph-btn-danger">
                <i class="bi bi-power"></i>
            </a>
        </div>
    </div>

    <div class="ph-main">

        <!-- KPI -->
        <div class="kpi-grid">
            <div class="kpi-card kpi-blue">
                <div class="kpi-value"><?= $total_refs ?></div>
                <div class="kpi-label">Références</div>
                <i class="bi bi-archive kpi-icon"></i>
            </div>
            <div class="kpi-card kpi-red">
                <div class="kpi-value"><?= $total_alerte ?></div>
                <div class="kpi-label">En Alerte Stock</div>
                <i class="bi bi-exclamation-triangle kpi-icon"></i>
            </div>
            <div class="kpi-card kpi-green">
                <div class="kpi-value"><?= $processed_today ?></div>
                <div class="kpi-label">Traitées Aujourd'hui</div>
                <i class="bi bi-check2-circle kpi-icon"></i>
            </div>
            <div class="kpi-card kpi-amber">
                <div class="kpi-value"><?= count($pending_orders) ?></div>
                <div class="kpi-label">En Attente</div>
                <i class="bi bi-hourglass-split kpi-icon"></i>
            </div>
        </div>

        <!-- GRID PRINCIPAL -->
        <div class="ph-grid">

            <!-- FLUX ORDONNANCES -->
            <div class="ph-panel">
                <div class="ph-panel-header">
                    <span class="ph-panel-title"><i class="bi bi-megaphone me-2"></i>Flux e-Prescriptions</span>
                    <div style="display:flex;align-items:center;gap:8px;">
                        <?php if ($dash_hasFilter): ?>
                        <span class="filter-active-badge">
                            <i class="bi bi-funnel-fill"></i>
                            <?= $dash_isToday ? '' : date('d/m/Y', strtotime($dash_filterDate)) ?>
                            <?= $dash_searchQ ? '"'.htmlspecialchars(mb_strimwidth($dash_searchQ,0,14,'…')).'"' : '' ?>
                        </span>
                        <?php else: ?>
                        <span class="badge-live">● LIVE</span>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- ── Barre de filtres ── -->
                <form method="GET" action="" id="filterForm" class="filter-bar">
                    <label><i class="bi bi-calendar3 me-1"></i>Date</label>
                    <input type="date" name="date" class="filter-input"
                           value="<?= htmlspecialchars($dash_filterDate) ?>"
                           max="<?= date('Y-m-d') ?>"
                           onchange="this.form.submit()">

                    <label style="margin-left:6px;"><i class="bi bi-search me-1"></i>Recherche</label>
                    <div style="position:relative;display:flex;align-items:center;">
                        <i class="bi bi-search" style="position:absolute;left:10px;color:#fca5a5;font-size:.8rem;pointer-events:none;"></i>
                        <input type="search" name="q" id="liveSearch" class="filter-input"
                               placeholder="Patient, dossier, médecin…"
                               value="<?= htmlspecialchars($dash_searchQ) ?>"
                               autocomplete="off"
                               style="padding-left:30px;width:240px;">
                        <button type="button" id="clearSearch"
                                onclick="clearLiveSearch()"
                                style="position:absolute;right:8px;background:none;border:none;cursor:pointer;
                                       color:#fca5a5;font-size:.85rem;display:<?= $dash_searchQ ? 'block' : 'none' ?>;"
                                title="Effacer">
                            <i class="bi bi-x-circle-fill"></i>
                        </button>
                    </div>

                    <?php if (!$dash_isToday): ?>
                    <a href="<?= BASE_URL ?>pharmacie" class="filter-btn-today" style="text-decoration:none;background:#f1f5f9;color:#64748b;" title="Revenir à aujourd'hui">
                        <i class="bi bi-arrow-counterclockwise me-1"></i>Aujourd'hui
                    </a>
                    <?php endif; ?>

                    <span id="searchCounter" style="margin-left:auto;font-size:.75rem;color:var(--muted);font-weight:600;">
                        <?= count($pending_orders) ?> résultat<?= count($pending_orders) > 1 ? 's' : '' ?>
                    </span>
                </form>

                <table class="ph-table">
                    <thead>
                        <tr>
                            <th>Patient</th>
                            <th>Prescripteur</th>
                            <th>Attente</th>
                            <th style="text-align:right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($pending_orders)): ?>
                            <tr>
                                <td colspan="4">
                                    <div class="empty-state">
                                        <i class="bi bi-inbox"></i>
                                        <span>Aucune ordonnance en attente de traitement.</span>
                                    </div>
                                </td>
                            </tr>
                        <?php else: foreach ($pending_orders as $ord):
                            $wait = max(0, (int)round((time() - strtotime($ord['date_creation'])) / 60));
                            $isSigne = ($ord['statut'] === 'SIGNEE');
                            $searchText = strtolower(
                                ($ord['patient_nom'] ?? '') . ' ' .
                                ($ord['patient_prenom'] ?? '') . ' ' .
                                ($ord['dossier_numero'] ?? '') . ' ' .
                                ($ord['medecin_nom'] ?? '') . ' ' .
                                ($ord['medecin_prenom'] ?? '') . ' ' .
                                ($ord['numero_compte_sage'] ?? '')
                            );
                        ?>
                            <tr class="ord-row"
                                data-search="<?= htmlspecialchars($searchText) ?>"
                                style="<?= $isSigne ? 'background:rgba(16,185,129,.04);' : '' ?>">
                                <td>
                                    <div class="patient-name">
                                        <?= strtoupper($ord['patient_nom'] ?? 'Inconnu') ?> <?= htmlspecialchars($ord['patient_prenom'] ?? '') ?>
                                    </div>
                                    <div class="patient-ref"><?= htmlspecialchars($ord['dossier_numero'] ?? '') ?></div>
                                    <?php if (!empty($ord['numero_compte_sage'])): ?>
                                    <div>
                                        <span class="patient-sage">
                                            <i class="bi bi-upc-scan"></i>
                                            <?= htmlspecialchars($ord['numero_compte_sage']) ?>
                                        </span>
                                    </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php
                                        $prefixe   = prescripteurPrefixe($ord['medecin_role'] ?? '');
                                        $roleLabel = prescripteurRoleLabel($ord['medecin_role'] ?? '');
                                        $nomComplet = trim(($ord['medecin_prenom'] ?? '') . ' ' . ($ord['medecin_nom'] ?? ''));
                                    ?>
                                    <span class="medecin-name"><?= $prefixe ? htmlspecialchars($prefixe).' ' : '' ?><?= htmlspecialchars($nomComplet) ?></span>
                                    <div style="font-size:.68rem;color:#94a3b8;font-weight:600;margin-top:1px;">
                                        <i class="bi bi-person-badge me-1"></i><?= htmlspecialchars($roleLabel) ?>
                                        <?php if (!empty($ord['medecin_specialite'])): ?>· <?= htmlspecialchars(ucfirst($ord['medecin_specialite'])) ?><?php endif; ?>
                                    </div>
                                    <div style="margin-top:3px;">
                                        <?php if (in_array($ord['statut'], ['TERMINEE','PARTIEL','COMPLET'])): ?>
                                        <span class="badge-terminee">
                                            <i class="bi bi-check-circle-fill"></i> <?= strtoupper($ord['statut']) ?>
                                        </span>
                                        <?php elseif ($isSigne): ?>
                                        <span style="display:inline-flex;align-items:center;gap:4px;background:#f0fdf4;color:#15803d;border:1px solid #86efac;border-radius:20px;padding:2px 8px;font-size:.68rem;font-weight:800;">
                                            <i class="bi bi-patch-check-fill"></i> SIGNÉE
                                        </span>
                                        <?php else: ?>
                                        <span style="display:inline-flex;align-items:center;gap:4px;background:#fef3c7;color:#92400e;border:1px solid #fde68a;border-radius:20px;padding:2px 8px;font-size:.68rem;font-weight:800;">
                                            EN ATTENTE
                                        </span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <?php if ($wait >= 30): ?>
                                        <span class="timer-alert"><i class="bi bi-alarm-fill me-1"></i><?= $wait ?> min</span>
                                    <?php elseif ($wait >= 10): ?>
                                        <span class="timer-normal" style="color:#fbbf24;"><?= $wait ?> min</span>
                                    <?php else: ?>
                                        <span class="timer-normal"><?= $wait ?> min</span>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align:right">
                                    <?php if (in_array($ord['statut'], ['TERMINEE','PARTIEL','COMPLET'])): ?>
                                    <a href="<?= BASE_URL ?>pharmacie/traitement/<?= (int)$ord['id'] ?>" class="btn-voir" style="font-size:.78rem;">
                                        <i class="bi bi-eye me-1"></i>Voir
                                    </a>
                                    <?php else: ?>
                                    <a href="<?= BASE_URL ?>pharmacie/traitement/<?= (int)$ord['id'] ?>" class="btn-preparer"
                                       <?= $isSigne ? 'style="background:linear-gradient(135deg,#16a34a,#15803d);"' : '' ?>>
                                        <i class="bi bi-capsule me-1"></i>PRÉPARER
                                    </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                        <!-- Ligne "aucun résultat" (cachée par défaut, affichée par JS) -->
                        <tr id="noResultsRow" style="display:none;">
                            <td colspan="4">
                                <div class="empty-state" style="padding:30px 20px;">
                                    <i class="bi bi-search" style="color:var(--red);opacity:.3;"></i>
                                    <span>Aucune ordonnance ne correspond à votre recherche.</span>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- ALERTES STOCK -->
            <div class="ph-panel">
                <div class="ph-panel-header">
                    <span class="ph-panel-title"><i class="bi bi-exclamation-triangle-fill me-2 text-danger" style="color:#f87171!important"></i>Ruptures &amp; Alertes</span>
                </div>
                <?php if (empty($low_stock)): ?>
                    <div class="empty-state">
                        <i class="bi bi-check-circle-fill" style="color:#10b981;opacity:.5"></i>
                        <span>Stock optimal.</span>
                    </div>
                <?php else: foreach ($low_stock as $idx => $m): ?>
                    <div class="alert-item<?= $idx >= 5 ? ' alert-item-more d-none' : '' ?>">
                        <div>
                            <div class="alert-med-name"><?= htmlspecialchars($m['designation'] ?? $m['nom'] ?? 'Produit') ?></div>
                            <div class="alert-med-info"><?= htmlspecialchars($m['dosage'] ?? '') ?> &bull; <?= htmlspecialchars($m['forme'] ?? '') ?></div>
                        </div>
                        <?php $qty = (int)($m['quantite_stock'] ?? $m['quantite'] ?? 0); ?>
                        <span class="badge-rupture"><?= $qty ?></span>
                    </div>
                <?php endforeach; endif; ?>
                <?php if (count($low_stock) > 5): ?>
                <div id="alertes-voir-plus" style="padding:10px 20px;text-align:center;border-top:1px solid rgba(255,255,255,.06);">
                    <button onclick="toggleAlertes()" id="btn-voir-plus"
                        style="background:rgba(239,68,68,.12);color:#f87171;border:1px solid rgba(239,68,68,.25);
                               padding:7px 20px;border-radius:10px;font-size:.78rem;font-weight:700;cursor:pointer;width:100%;">
                        <i class="bi bi-chevron-down me-1" id="btn-voir-plus-icon"></i>
                        Voir <?= count($low_stock) - 5 ?> de plus
                    </button>
                </div>
                <?php endif; ?>
                <div class="ph-panel-footer">
                    <a href="<?= BASE_URL ?>pharmacie/stock"><i class="bi bi-box-seam me-1"></i>Voir l'inventaire complet</a>
                </div>
            </div>

        </div><!-- /ph-grid -->

        <!-- ═══════════════════════════════════════════════════════
             SECTION HISTORIQUE DES ORDONNANCES DÉLIVRÉES
             ═══════════════════════════════════════════════════════ -->
        <div class="hist-section">
            <div class="hist-header">
                <div class="d-flex align-items-center gap-3">
                    <span class="hist-title">
                        <i class="bi bi-clock-history" style="color:#10b981;font-size:1rem;"></i>
                        Historique des ordonnances délivrées
                    </span>
                    <span class="hist-count"><?= $hist_total ?> au total</span>
                </div>
                <!-- Filtre par date -->
                <form method="GET" action="" class="hist-filter" id="histFilterForm">
                    <input type="date" name="hist_date" id="histDateInput"
                           value="<?= htmlspecialchars($_GET['hist_date'] ?? '') ?>"
                           max="<?= date('Y-m-d') ?>"
                           placeholder="Filtrer par date">
                    <button type="submit">
                        <i class="bi bi-search me-1"></i>Filtrer
                    </button>
                    <?php if (!empty($_GET['hist_date'])): ?>
                    <a href="<?= BASE_URL ?>pharmacie" class="btn-reset" title="Effacer le filtre">
                        <i class="bi bi-x-lg"></i>
                    </a>
                    <?php endif; ?>
                </form>
            </div>

            <div class="ph-panel">
                <?php if (!empty($_GET['hist_date'])): ?>
                <div style="padding:10px 22px 0;font-size:.78rem;color:#64748b;font-weight:600;">
                    <i class="bi bi-funnel me-1"></i>
                    Résultats pour le <strong style="color:#a5b4fc;"><?= date('d/m/Y', strtotime($_GET['hist_date'])) ?></strong>
                    — <?= count($historique) ?> ordonnance(s)
                </div>
                <?php endif; ?>

                <table class="ph-table">
                    <thead>
                        <tr>
                            <th style="width:140px;">Date délivrance</th>
                            <th>Patient</th>
                            <th>Prescripteur</th>
                            <th>Pharmacien</th>
                            <th style="text-align:center;">Médicaments</th>
                            <th style="text-align:center;">Statut</th>
                            <th style="text-align:right;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($historique)): ?>
                        <tr>
                            <td colspan="7">
                                <div class="empty-state">
                                    <i class="bi bi-journal-x"></i>
                                    <span>
                                        <?= !empty($_GET['hist_date'])
                                            ? 'Aucune ordonnance délivrée à cette date.'
                                            : 'Aucune ordonnance délivrée pour le moment.' ?>
                                    </span>
                                </div>
                            </td>
                        </tr>
                        <?php else: foreach ($historique as $h): ?>
                        <tr>
                            <td>
                                <div style="font-weight:700;color:#6ee7b7;font-size:.88rem;">
                                    <?= $h['date_traitement'] ? date('d/m/Y', strtotime($h['date_traitement'])) : '—' ?>
                                </div>
                                <div style="font-size:.74rem;color:#475569;margin-top:2px;">
                                    <?= $h['date_traitement'] ? date('H:i', strtotime($h['date_traitement'])) : '' ?>
                                </div>
                            </td>
                            <td>
                                <div class="patient-name"><?= strtoupper(htmlspecialchars($h['patient_nom'])) ?> <?= htmlspecialchars($h['patient_prenom']) ?></div>
                                <div class="patient-ref"><?= htmlspecialchars($h['dossier_numero']) ?></div>
                                <?php if (!empty($h['numero_compte_sage'])): ?>
                                <div>
                                    <span class="patient-sage">
                                        <i class="bi bi-upc-scan"></i>
                                        <?= htmlspecialchars($h['numero_compte_sage']) ?>
                                    </span>
                                </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php $hPrefixe = prescripteurPrefixe($h['medecin_role'] ?? ''); ?>
                                <span class="medecin-name"><?= $hPrefixe ? htmlspecialchars($hPrefixe).' ' : '' ?><?= htmlspecialchars($h['medecin_prenom'] . ' ' . $h['medecin_nom']) ?></span>
                                <div style="font-size:.68rem;color:#94a3b8;font-weight:600;margin-top:1px;">
                                    <i class="bi bi-person-badge me-1"></i><?= htmlspecialchars(prescripteurRoleLabel($h['medecin_role'] ?? '')) ?>
                                </div>
                                <div style="font-size:.74rem;color:#475569;margin-top:2px;">
                                    Prescrit le <?= date('d/m/Y', strtotime($h['date_creation'])) ?>
                                </div>
                            </td>
                            <td>
                                <?php if ($h['pharmacien_nom']): ?>
                                <span style="color:#a5b4fc;font-weight:600;font-size:.85rem;">
                                    <?= htmlspecialchars($h['pharmacien_prenom'] . ' ' . $h['pharmacien_nom']) ?>
                                </span>
                                <?php else: ?>
                                <span style="color:#475569;font-size:.8rem;">—</span>
                                <?php endif; ?>
                            </td>
                            <td style="text-align:center;">
                                <span class="hist-nb-meds">
                                    <i class="bi bi-capsule me-1" style="color:#f59e0b;"></i>
                                    <?= (int)$h['nb_medicaments'] ?>
                                </span>
                            </td>
                            <td style="text-align:center;">
                                <span class="badge-termine">
                                    <i class="bi bi-check2-circle me-1"></i>Délivrée
                                </span>
                            </td>
                            <td style="text-align:right;">
                                <a href="<?= BASE_URL ?>pharmacie/traitement/<?= (int)$h['id'] ?>"
                                   class="btn-voir">
                                    <i class="bi bi-eye"></i> Voir
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>

                <?php if ($hist_total > 30 && empty($_GET['hist_date'])): ?>
                <div class="ph-panel-footer">
                    <span style="color:#475569;font-size:.8rem;font-weight:600;">
                        Affichage des 30 dernières &mdash; utilisez le filtre date pour rechercher une ordonnance spécifique
                    </span>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <!-- /HISTORIQUE -->

    </div><!-- /ph-main -->
</main>

<script>
/* ══ RECHERCHE DYNAMIQUE ════════════════════════════════════════════ */
(function initLiveSearch() {
    const input      = document.getElementById('liveSearch');
    const clearBtn   = document.getElementById('clearSearch');
    const counter    = document.getElementById('searchCounter');
    const noResults  = document.getElementById('noResultsRow');
    const allRows    = Array.from(document.querySelectorAll('tr.ord-row'));
    let   searchTimer = null;

    if (!input) return;

    function filterRows(query) {
        const q = query.trim().toLowerCase()
                       .normalize('NFD').replace(/[̀-ͯ]/g, ''); // ignore accents
        let visible = 0;

        allRows.forEach(row => {
            const text = (row.dataset.search || '')
                .normalize('NFD').replace(/[̀-ͯ]/g, '');
            const match = !q || text.includes(q);
            row.style.display = match ? '' : 'none';
            if (match) visible++;
        });

        // Compteur
        if (counter) {
            counter.textContent = visible + ' résultat' + (visible > 1 ? 's' : '');
            counter.style.color = (q && visible === 0) ? 'var(--red)' : '';
        }
        // Ligne "aucun résultat"
        if (noResults) noResults.style.display = (visible === 0 && allRows.length > 0) ? '' : 'none';

        // Bouton clear
        if (clearBtn) clearBtn.style.display = q ? 'block' : 'none';

        // Surligner les termes trouvés dans les noms patients (optionnel)
        highlightRows(q);
    }

    function highlightRows(q) {
        allRows.forEach(row => {
            const nameEl = row.querySelector('.patient-name');
            if (!nameEl) return;
            const orig = nameEl.getAttribute('data-orig') || nameEl.textContent;
            nameEl.setAttribute('data-orig', orig);
            if (!q) { nameEl.innerHTML = orig; return; }
            const re = new RegExp('(' + q.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + ')', 'gi');
            nameEl.innerHTML = orig.replace(re,
                '<mark style="background:#fef08a;color:#713f12;border-radius:2px;padding:0 2px;">$1</mark>');
        });
    }

    // Filtrage en temps réel avec debounce 120ms
    input.addEventListener('input', function () {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(() => filterRows(this.value), 120);
    });

    // Appliquer immédiatement si valeur pré-remplie (depuis URL)
    if (input.value.trim()) filterRows(input.value);
})();

window.clearLiveSearch = function() {
    const input = document.getElementById('liveSearch');
    if (input) {
        input.value = '';
        input.dispatchEvent(new Event('input'));
        input.focus();
    }
};

/* ══ HORLOGE ════════════════════════════════════════════════════════ */
(function startClock() {
    function update() {
        const n = new Date();
        const t = [n.getHours(), n.getMinutes(), n.getSeconds()]
            .map(v => String(v).padStart(2,'0')).join(':');
        const el = document.getElementById('clock');
        if (el) el.textContent = t;
    }
    update();
    setInterval(update, 1000);
})();

// Auto-refresh toutes les 2 min
setTimeout(() => location.reload(), 120000);

// Voir plus / voir moins dans la section Alertes
function toggleAlertes() {
    const hidden = document.querySelectorAll('.alert-item-more');
    const btn    = document.getElementById('btn-voir-plus');
    const icon   = document.getElementById('btn-voir-plus-icon');
    const isOpen = !hidden[0]?.classList.contains('d-none');
    hidden.forEach(el => el.classList.toggle('d-none', isOpen));
    if (isOpen) {
        icon.className = 'bi bi-chevron-down me-1';
        btn.innerHTML  = `<i class="bi bi-chevron-down me-1" id="btn-voir-plus-icon"></i> Voir ${hidden.length} de plus`;
    } else {
        icon.className = 'bi bi-chevron-up me-1';
        btn.innerHTML  = `<i class="bi bi-chevron-up me-1" id="btn-voir-plus-icon"></i> Réduire`;
    }
}
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
