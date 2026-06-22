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
    :root {
        --ic-primary: #0d9488;
        --ic-light:   #f0fdfa;
        --ic-border:  #99f6e4;
        --ic-dark:    #134e4a;
        --ic-hover:   #ccfbf1;
    }

    body { background: #f0fdfa; font-family: 'Segoe UI', system-ui, sans-serif; }

    /* ── TOPBAR ────────────────────────────────────────────── */
    .ic-topbar {
        background: white;
        padding: 14px 32px;
        border-bottom: 2px solid var(--ic-border);
        box-shadow: 0 2px 12px rgba(13,148,136,.08);
        position: sticky; top: 0; z-index: 1000;
        display: flex; align-items: center; justify-content: space-between;
    }
    .ic-avatar {
        width: 44px; height: 44px; border-radius: 14px;
        background: linear-gradient(135deg, #ccfbf1, #99f6e4);
        display: flex; align-items: center; justify-content: center;
        flex-shrink: 0;
    }
    .ic-role-badge {
        background: var(--ic-primary); color: white;
        font-size: .68rem; font-weight: 800; letter-spacing: .6px;
        padding: 4px 12px; border-radius: 20px; text-transform: uppercase;
    }
    .ic-service-badge {
        background: var(--ic-light); color: var(--ic-dark);
        border: 1px solid var(--ic-border);
        font-size: .68rem; font-weight: 700; letter-spacing: .5px;
        padding: 4px 12px; border-radius: 20px; text-transform: uppercase;
    }
    .ic-clock {
        font-family: 'SF Mono', 'Fira Code', monospace;
        font-size: 1rem; font-weight: 700;
        color: var(--ic-dark); letter-spacing: 1px;
        background: var(--ic-light); border: 1px solid var(--ic-border);
        padding: 5px 14px; border-radius: 10px;
    }
    .ic-action-btn {
        width: 38px; height: 38px; border-radius: 10px;
        display: flex; align-items: center; justify-content: center;
        border: 1px solid var(--ic-border); background: var(--ic-light);
        cursor: pointer; transition: all .15s; color: var(--ic-dark);
        text-decoration: none; font-size: 1rem;
    }
    .ic-action-btn:hover { background: var(--ic-hover); color: var(--ic-dark); }
    .ic-action-btn.danger { color: #ef4444; border-color: #fca5a5; background: #fff1f2; }
    .ic-action-btn.danger:hover { background: #fee2e2; }

    /* ── LAYOUT ─────────────────────────────────────────────── */
    .ic-content { padding: 28px 32px; max-width: 1600px; margin: 0 auto; }

    /* ── STAT KPI CARDS ─────────────────────────────────────── */
    .stat-ic {
        background: white; border-radius: 18px;
        padding: 20px 22px; display: flex; align-items: center; gap: 16px;
        border: 1px solid #e2e8f0; border-left-width: 4px;
        box-shadow: 0 2px 10px rgba(0,0,0,.04);
        transition: transform .15s, box-shadow .15s;
    }
    .stat-ic:hover { transform: translateY(-2px); box-shadow: 0 6px 18px rgba(0,0,0,.08); }
    .stat-ic-icon {
        width: 52px; height: 52px; border-radius: 14px;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.4rem; flex-shrink: 0;
    }
    .stat-ic-num { font-size: 2rem; font-weight: 900; line-height: 1; color: #1e293b; }
    .stat-ic-lbl { font-size: .71rem; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: .6px; margin-top: 3px; }

    /* ── CARDS ──────────────────────────────────────────────── */
    .ic-card {
        background: white; border-radius: 20px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 2px 10px rgba(0,0,0,.04);
        overflow: hidden; margin-bottom: 22px;
    }
    .ic-card-header {
        padding: 16px 22px; border-bottom: 1px solid #f1f5f9;
        display: flex; align-items: center; justify-content: space-between;
    }
    .ic-card-title {
        font-size: .94rem; font-weight: 800; color: #1e293b;
        display: flex; align-items: center; gap: 10px; margin: 0;
    }
    .ic-card-icon {
        width: 32px; height: 32px; border-radius: 9px;
        background: var(--ic-light); color: var(--ic-primary);
        display: flex; align-items: center; justify-content: center;
        font-size: 1rem; flex-shrink: 0;
    }
    .ic-count-pill {
        background: var(--ic-light); color: var(--ic-primary);
        font-size: .73rem; font-weight: 800;
        padding: 4px 12px; border-radius: 20px;
    }

    /* ── FILE D'ATTENTE ─────────────────────────────────────── */
    .patient-ic {
        padding: 14px 22px; border-bottom: 1px solid #f8fafc;
        display: flex; align-items: center; justify-content: space-between;
        gap: 14px; transition: background .15s;
    }
    .patient-ic:hover { background: #f0fdfa; }
    .patient-ic:last-child { border-bottom: none; }
    .order-bubble {
        width: 32px; height: 32px; border-radius: 10px;
        background: var(--ic-light); color: var(--ic-primary);
        font-size: .82rem; font-weight: 900;
        display: flex; align-items: center; justify-content: center; flex-shrink: 0;
    }
    .prio-chip { font-size: .68rem; font-weight: 800; padding: 3px 9px; border-radius: 20px; }
    .prio-p1 { background: #fee2e2; color: #991b1b; }
    .prio-p2 { background: #fef3c7; color: #92400e; }
    .prio-p3 { background: #dcfce7; color: #166534; }

    .btn-consulter-ic {
        background: var(--ic-primary); color: white;
        padding: 8px 20px; border-radius: 10px;
        font-weight: 700; font-size: .82rem; border: none;
        display: inline-flex; align-items: center; gap: 6px;
        text-decoration: none; transition: all .15s; white-space: nowrap;
    }
    .btn-consulter-ic:hover { background: var(--ic-dark); color: white; transform: translateY(-1px); }

    /* ── QUICK ACCESS ───────────────────────────────────────── */
    .quick-btn {
        display: flex; align-items: center; gap: 12px;
        padding: 12px 14px; border-radius: 12px;
        background: var(--ic-light); border: 1px solid var(--ic-border);
        text-decoration: none; color: var(--ic-dark);
        font-weight: 700; font-size: .86rem;
        transition: all .15s; margin-bottom: 9px;
    }
    .quick-btn:hover { background: var(--ic-hover); transform: translateX(4px); color: var(--ic-dark); box-shadow: 0 2px 8px rgba(13,148,136,.12); }
    .quick-btn-icon {
        width: 36px; height: 36px; border-radius: 10px;
        background: var(--ic-primary); color: white;
        display: flex; align-items: center; justify-content: center;
        font-size: 1rem; flex-shrink: 0;
    }
    .quick-btn-sub { font-size: .73rem; font-weight: 400; color: #64748b; margin-top: 1px; }

    /* ── BILAN TABLE ────────────────────────────────────────── */
    .bilan-table { width: 100%; border-collapse: collapse; }
    .bilan-table thead th {
        background: #f8fafc; color: #64748b;
        font-size: .69rem; font-weight: 700; text-transform: uppercase; letter-spacing: .8px;
        padding: 10px 16px; border-bottom: 1px solid #e2e8f0;
    }
    .bilan-table tbody tr { border-bottom: 1px solid #f1f5f9; transition: background .15s; }
    .bilan-table tbody tr:hover { background: #fafbff; }
    .bilan-table tbody td { padding: 12px 16px; font-size: .87rem; }
    .bilan-table tbody tr.nouveau { background: #fffbeb; border-left: 3px solid #f59e0b; }

    .badge-labo  { background: #e0f2fe; color: #0369a1; font-size: .71rem; font-weight: 700; padding: 3px 10px; border-radius: 20px; }
    .badge-radio { background: #f3e8ff; color: #7c3aed; font-size: .71rem; font-weight: 700; padding: 3px 10px; border-radius: 20px; }

    .statut-chip { display: inline-flex; align-items: center; gap: 5px; padding: 4px 10px; border-radius: 20px; font-size: .74rem; font-weight: 700; }
    .chip-attente    { background: #fef3c7; color: #92400e; }
    .chip-analyse    { background: #dbeafe; color: #1d4ed8; }
    .chip-pret       { background: #dcfce7; color: #166534; }
    .chip-interprete { background: #d1fae5; color: #065f46; }
    .chip-default    { background: #f1f5f9; color: #475569; }

    .alerte-dot { width: 8px; height: 8px; background: #ef4444; border-radius: 50%; display: inline-block; animation: pulse-alert .8s infinite; }
    @keyframes pulse-alert { 0%,100%{ transform:scale(1) } 50%{ transform:scale(1.4) } }

    .btn-bilan { display:inline-flex; align-items:center; gap:5px; padding:5px 11px; border-radius:8px; font-size:.73rem; font-weight:700; border:none; cursor:pointer; transition:all .15s; text-decoration:none; }
    .btn-b-voir    { background:#e0f2fe; color:#0369a1; } .btn-b-voir:hover    { background:#bae6fd; color:#0369a1; }
    .btn-b-comment { background:#f0fdf4; color:#166534; } .btn-b-comment:hover { background:#bbf7d0; color:#166534; }
    .btn-b-attente { background:#f1f5f9; color:#94a3b8; cursor:default; }

    /* ── RECENT CONSULTS & TODO ─────────────────────────────── */
    .consult-row {
        padding: 11px 14px; border-radius: 12px;
        background: #f8fafc; border: 1px solid #e2e8f0;
        margin-bottom: 8px; display: flex; align-items: center;
        justify-content: space-between; gap: 10px; transition: background .15s;
    }
    .consult-row:hover { background: var(--ic-light); }
    .todo-row {
        display: flex; align-items: center; justify-content: space-between;
        padding: 7px 10px; border-radius: 10px; margin-bottom: 5px;
        transition: background .1s;
    }
    .todo-row:hover { background: #f8fafc; }

    /* ── MODAL ──────────────────────────────────────────────── */
    .modal-ic .modal-content { border-radius: 20px; border: 0; box-shadow: 0 25px 50px rgba(0,0,0,.15); }
    .result-row { background: #f8fafc; border-radius: 10px; padding: 12px 16px; margin-bottom: 8px; }
    .result-val { font-size: 1.4rem; font-weight: 900; }
    .result-val.anormal { color: #dc2626; }
    .result-val.normal  { color: #16a34a; }
    .note-item { background: #f0fdfa; border-radius: 10px; padding: 10px 14px; margin-bottom: 8px; border-left: 3px solid var(--ic-primary); }
    .note-item .n-meta { font-size: .71rem; color: #94a3b8; margin-bottom: 3px; }
    .note-item .n-text { font-size: .85rem; color: #334155; }

    /* ── EMPTY STATE ────────────────────────────────────────── */
    .ic-empty { text-align: center; padding: 36px 20px; }
    .ic-empty i { font-size: 2.4rem; opacity: .18; color: var(--ic-primary); display: block; margin-bottom: 10px; }
    .ic-empty p { color: #94a3b8; font-size: .86rem; margin: 0; }

    /* ── BARRE RECHERCHE / FILTRE FILE D'ATTENTE ─────────────── */
    .file-filter-bar {
        display: flex; align-items: center; gap: 8px; flex-wrap: wrap;
        padding: 10px 22px 12px; border-bottom: 1px solid #f1f5f9;
        background: #fafcff;
    }
    .file-search-wrap { position: relative; flex: 1; min-width: 170px; max-width: 260px; }
    .file-search-wrap i {
        position: absolute; left: 10px; top: 50%; transform: translateY(-50%);
        color: #94a3b8; font-size: .82rem; pointer-events: none;
    }
    .file-search-input {
        width: 100%; padding: 7px 10px 7px 30px;
        border: 1.5px solid #e2e8f0; border-radius: 9px;
        background: #fff; font-size: .8rem; color: #1e293b; outline: none;
        transition: border-color .15s;
    }
    .file-search-input:focus { border-color: var(--ic-primary); }
    .file-search-input::placeholder { color: #b0bec5; }
    .date-filter-group { display: flex; align-items: center; gap: 5px; }
    .date-filter-btn {
        padding: 6px 12px; border-radius: 8px; border: 1.5px solid #e2e8f0;
        background: #fff; font-size: .75rem; font-weight: 700; color: #475569;
        cursor: pointer; transition: all .15s; white-space: nowrap;
    }
    .date-filter-btn:hover  { border-color: var(--ic-primary); color: var(--ic-primary); }
    .date-filter-btn.active { background: var(--ic-primary); color: #fff; border-color: var(--ic-primary); }
    .date-filter-input {
        padding: 6px 10px; border-radius: 9px; border: 1.5px solid #e2e8f0;
        font-size: .78rem; color: #1e293b; outline: none; transition: border-color .15s;
        background: #fff; cursor: pointer;
    }
    .date-filter-input:focus { border-color: var(--ic-primary); }
    .file-no-result {
        display: none; padding: 28px 22px; text-align: center;
        color: #94a3b8; font-size: .84rem;
    }
    .file-no-result i { display: block; font-size: 1.8rem; margin-bottom: 8px; opacity: .3; }
</style>

<!-- ── TOPBAR ─────────────────────────────────────────────────── -->
<div class="ic-topbar no-print">
    <div class="d-flex align-items-center gap-3">
        <div class="ic-avatar">
            <i class="bi bi-clipboard2-pulse-fill" style="color:var(--ic-primary); font-size:1.2rem;"></i>
        </div>
        <div>
            <div style="font-size:.94rem; font-weight:800; color:#1e293b;">
                <?php
                echo htmlspecialchars('Inf. ' . ($_SESSION['user_prenom'] ?? '') . ' ' . ($_SESSION['user_nom'] ?? ''));
                ?>
            </div>
            <div style="font-size:.73rem; color:#64748b;">Infirmier(ère) Consultant(e)</div>
        </div>
        <div class="d-flex gap-2 ms-2">
            <span class="ic-role-badge"><i class="bi bi-clipboard2-pulse me-1"></i>Inf. Consultant</span>
            <span class="ic-service-badge"><?= htmlspecialchars($_SESSION['nom_service'] ?? 'Service') ?></span>
        </div>
    </div>
    <div class="d-flex align-items-center gap-2">
        <div id="icClock" class="ic-clock">00:00:00</div>
        <button type="button" class="ic-action-btn" title="Mon profil" onclick="ouvrirProfilModal()">
            <i class="bi bi-person-circle"></i>
        </button>
        <a href="<?= BASE_URL ?>logout" class="ic-action-btn danger" title="Déconnexion">
            <i class="bi bi-power"></i>
        </a>
    </div>
</div>

<div class="ic-content">

    <!-- ── KPI ROW ───────────────────────────────────────────── -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="stat-ic" style="border-left-color:var(--ic-primary);">
                <div class="stat-ic-icon" style="background:#ccfbf1; color:var(--ic-primary);">
                    <i class="bi bi-people-fill"></i>
                </div>
                <div>
                    <div class="stat-ic-num"><?= count($patients_assignes) ?></div>
                    <div class="stat-ic-lbl">En attente</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-ic" style="border-left-color:#059669;">
                <div class="stat-ic-icon" style="background:#d1fae5; color:#059669;">
                    <i class="bi bi-flask-fill"></i>
                </div>
                <div>
                    <div class="stat-ic-num"><?= count($resultats_prets) ?></div>
                    <div class="stat-ic-lbl">Résultats prêts</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-ic" style="border-left-color:#2563eb;">
                <div class="stat-ic-icon" style="background:#dbeafe; color:#2563eb;">
                    <i class="bi bi-clipboard2-check-fill"></i>
                </div>
                <div>
                    <div class="stat-ic-num"><?= $nb_consultations_jour ?></div>
                    <div class="stat-ic-lbl">Consultations auj.</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-ic" style="border-left-color:#7c3aed;">
                <div class="stat-ic-icon" style="background:#ede9fe; color:#7c3aed;">
                    <i class="bi bi-activity"></i>
                </div>
                <div>
                    <div class="stat-ic-num"><?= count($suivi_bilans) ?></div>
                    <div class="stat-ic-lbl">Bilans en cours</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">

        <!-- ── COLONNE PRINCIPALE (8/12) ──────────────────────── -->
        <div class="col-lg-8">

            <!-- FILE D'ATTENTE -->
            <div class="ic-card">
                <div class="ic-card-header">
                    <h5 class="ic-card-title">
                        <div class="ic-card-icon"><i class="bi bi-person-lines-fill"></i></div>
                        Patients en attente de consultation
                    </h5>
                    <span class="ic-count-pill" id="fileCountPill"><?= count($patients_assignes) ?> patient(s)</span>
                </div>

                <?php if (!empty($patients_assignes)): ?>
                <!-- ── Barre de recherche / filtre date ── -->
                <div class="file-filter-bar">
                    <div class="file-search-wrap">
                        <i class="bi bi-search"></i>
                        <input type="text" id="fileSearchInput" class="file-search-input"
                               placeholder="Nom, dossier, motif…"
                               oninput="filtrerFile()">
                    </div>
                    <div class="date-filter-group">
                        <button class="date-filter-btn active" id="fDateAll"
                                onclick="setDateFilter('all',this)">Tous</button>
                        <button class="date-filter-btn" id="fDateAujourd"
                                onclick="setDateFilter('today',this)">Aujourd'hui</button>
                        <button class="date-filter-btn" id="fDateHier"
                                onclick="setDateFilter('yesterday',this)">Hier</button>
                        <input type="date" id="fDatePicker" class="date-filter-input"
                               title="Choisir une date" max="<?= date('Y-m-d') ?>"
                               onchange="setDateFilter('custom',this)">
                    </div>
                </div>
                <?php endif; ?>

                <?php if (empty($patients_assignes)): ?>
                <div class="ic-empty">
                    <i class="bi bi-person-check"></i>
                    <p>Tous les patients ont été reçus.<br>Bonne journée !</p>
                </div>
                <?php else: ?>
                <div id="fileAttente">
                    <?php foreach ($patients_assignes as $p):
                        $prio    = $p['niveau_gravite'] ?? 'P3-STABLE';
                        $prioCls = str_contains($prio, 'P1') ? 'prio-p1' : (str_contains($prio, 'P2') ? 'prio-p2' : 'prio-p3');
                        $ordre   = (int)($p['numero_ordre'] ?? 0);
                        $dateRow = !empty($p['created_at']) ? date('Y-m-d', strtotime($p['created_at'])) : date('Y-m-d');
                        $searchStr = strtolower(($p['nom'] ?? '').' '.($p['prenom'] ?? '').' '.($p['dossier_numero'] ?? '').' '.($p['motif_plainte'] ?? ''));
                    ?>
                    <div class="patient-ic"
                         data-search="<?= htmlspecialchars($searchStr) ?>"
                         data-date="<?= $dateRow ?>">
                        <div class="d-flex align-items-center gap-3 flex-grow-1 min-width-0">
                            <?php if ($ordre > 0): ?>
                            <div class="order-bubble"><?= $ordre ?></div>
                            <?php endif; ?>
                            <div class="min-width-0">
                                <div class="fw-bold text-truncate" style="color:#1e293b; font-size:.92rem;">
                                    <?= strtoupper(htmlspecialchars($p['nom'])) ?> <?= htmlspecialchars($p['prenom']) ?>
                                </div>
                                <div class="d-flex align-items-center gap-2 mt-1 flex-wrap">
                                    <span class="prio-chip <?= $prioCls ?>"><?= htmlspecialchars($prio) ?></span>
                                    <small class="text-muted font-monospace" style="font-size:.73rem;"><?= htmlspecialchars($p['dossier_numero'] ?? '') ?></small>
                                    <?php if (!empty($p['motif_plainte'])): ?>
                                    <small class="text-muted" style="font-size:.75rem;">— <?= htmlspecialchars(mb_substr($p['motif_plainte'], 0, 50)) ?></small>
                                    <?php endif; ?>
                                    <small class="text-muted" style="font-size:.7rem;margin-left:2px;">
                                        <i class="bi bi-calendar3 me-1"></i><?= date('d/m/Y', strtotime($dateRow)) ?>
                                    </small>
                                </div>
                            </div>
                        </div>
                        <div class="d-flex align-items-center gap-2 flex-shrink-0">
                            <a href="<?= BASE_URL ?>patients/dossier/<?= (int)$p['id'] ?>"
                               class="btn btn-sm btn-outline-secondary rounded-pill px-3"
                               style="font-size:.78rem;">
                                <i class="bi bi-folder2-open me-1"></i>Dossier
                            </a>
                            <a href="<?= BASE_URL ?>infirmier-consultant/consulter/<?= (int)$p['id'] ?>"
                               class="btn-consulter-ic">
                                <i class="bi bi-clipboard2-pulse"></i>Consulter
                            </a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <div class="file-no-result" id="fileNoResult">
                    <i class="bi bi-person-x"></i>Aucun patient ne correspond à votre recherche.
                </div>
                <?php endif; ?>
            </div>

            <!-- RÉSULTATS DISPONIBLES (si présents) -->
            <?php if (!empty($resultats_prets)): ?>
            <div class="ic-card" style="border-left: 4px solid #059669;">
                <div class="ic-card-header">
                    <h5 class="ic-card-title">
                        <div class="ic-card-icon" style="background:#d1fae5; color:#059669;"><i class="bi bi-flask-fill"></i></div>
                        Résultats de laboratoire disponibles
                    </h5>
                    <span class="badge bg-success rounded-pill px-3"><?= count($resultats_prets) ?> nouveau(x)</span>
                </div>
                <div class="table-responsive">
                    <table class="bilan-table">
                        <thead>
                            <tr><th>Examen</th><th>Patient</th><th style="text-align:right">Action</th></tr>
                        </thead>
                        <tbody>
                        <?php foreach ($resultats_prets as $res): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($res['nom_examen'] ?? '—') ?></strong>
                                    <?php if (!empty($res['categorie'])): ?><br><small class="text-muted"><?= htmlspecialchars($res['categorie']) ?></small><?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($res['nom'] . ' ' . $res['prenom']) ?></td>
                                <td style="text-align:right;">
                                    <button class="btn-bilan btn-b-voir"
                                            onclick="voirResultatsLabo(<?= (int)$res['id'] ?>)">
                                        <i class="bi bi-eye"></i> Consulter
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>

            <!-- SUIVI DES BILANS -->
            <div class="ic-card">
                <div class="ic-card-header">
                    <h5 class="ic-card-title">
                        <div class="ic-card-icon" style="background:#ede9fe; color:#7c3aed;"><i class="bi bi-clipboard2-data"></i></div>
                        Suivi des Bilans Demandés
                    </h5>
                    <small class="text-muted"><?= count($suivi_bilans) ?> bilan(s) en cours</small>
                </div>
                <div class="table-responsive">
                    <table class="bilan-table">
                        <thead>
                            <tr>
                                <th>Patient</th>
                                <th>Type</th>
                                <th>Examen</th>
                                <th>Statut</th>
                                <th style="text-align:right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (!empty($suivi_bilans)): foreach ($suivi_bilans as $b):
                            $isDisponible = in_array($b['statut'], ['RESULTATS_PRETS','VALIDES','interprete','termine']);
                            $isNouveau    = $isDisponible && (int)($b['nb_commentaires'] ?? 0) === 0;
                            $patId  = (int)($b['patient_id'] ?? 0);
                            $patNom = strtoupper($b['patient_nom'] ?? '?') . ' ' . ($b['patient_prenom'] ?? '');
                            $chips  = [
                                'EN_ATTENTE'             => ['chip-attente',   'bi-clock-history',       'En attente'],
                                'PRELEVEMENTS_EFFECTUES' => ['chip-analyse',   'bi-droplet-half',         'Prélevé'],
                                'EN_ANALYSE'             => ['chip-analyse',   'bi-gear-wide-connected',  'En analyse'],
                                'RESULTATS_PRETS'        => ['chip-pret',      'bi-check-circle-fill',    'Résultat prêt'],
                                'VALIDES'                => ['chip-pret',      'bi-patch-check-fill',     'Validé'],
                                'en_cours'               => ['chip-analyse',   'bi-gear-wide-connected',  'En cours'],
                                'termine'                => ['chip-interprete','bi-check-circle-fill',    'Terminé'],
                                'interprete'             => ['chip-interprete','bi-star-fill',            'Interprété'],
                            ];
                            [$cCls, $cIcon, $cTxt] = $chips[$b['statut']] ?? ['chip-default','bi-dash','Inconnu'];
                        ?>
                            <tr class="<?= $isNouveau ? 'nouveau' : '' ?>">
                                <td>
                                    <?php if ($isNouveau): ?><span class="alerte-dot me-1"></span><?php endif; ?>
                                    <span style="font-weight:700; color:#334155; font-size:.85rem;"><?= htmlspecialchars($patNom) ?></span><br>
                                    <span style="font-size:.71rem; color:#94a3b8;"><?= htmlspecialchars($b['dossier_numero'] ?? '') ?></span>
                                </td>
                                <td>
                                    <?php if ($b['type'] === 'Labo'): ?>
                                        <span class="badge-labo"><i class="bi bi-droplet me-1"></i>Labo</span>
                                    <?php else: ?>
                                        <span class="badge-radio"><i class="bi bi-radioactive me-1"></i>Radio</span>
                                    <?php endif; ?>
                                </td>
                                <td><strong style="font-size:.85rem;"><?= htmlspecialchars($b['label']) ?></strong></td>
                                <td>
                                    <span class="statut-chip <?= $cCls ?>">
                                        <i class="bi <?= $cIcon ?>"></i> <?= $cTxt ?>
                                    </span>
                                </td>
                                <td style="text-align:right;">
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
                                            <i class="bi bi-chat-text"></i> Note
                                        </button>
                                    <?php else: ?>
                                        <span class="btn-bilan btn-b-attente"><i class="bi bi-hourglass-split"></i> Attente</span>
                                    <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; else: ?>
                            <tr><td colspan="5" class="text-center py-5 text-muted small">
                                <i class="bi bi-clipboard2 d-block fs-2 mb-2 opacity-25"></i>Aucun bilan en cours.
                            </td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- DOSSIERS PARTAGÉS -->
            <div class="ic-card">
                <div class="ic-card-header">
                    <h5 class="ic-card-title">
                        <div class="ic-card-icon"><i class="bi bi-share"></i></div>
                        Dossiers Partagés
                    </h5>
                </div>
                <div class="p-3">
                    <ul class="nav nav-pills mb-3">
                        <li class="nav-item">
                            <button class="nav-link active rounded-pill fw-bold px-4 py-2" style="font-size:.83rem;"
                                    data-bs-toggle="pill" data-bs-target="#icReçus" type="button">
                                <i class="bi bi-inbox me-1"></i>Reçus
                                <span class="badge bg-white ms-1" style="color:var(--ic-primary); font-size:.72rem;"><?= count($dossiers_reçus) ?></span>
                            </button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link rounded-pill fw-bold px-4 py-2" style="font-size:.83rem;"
                                    data-bs-toggle="pill" data-bs-target="#icEnvoyés" type="button">
                                <i class="bi bi-send me-1"></i>Envoyés
                                <span class="badge bg-white ms-1" style="color:var(--ic-primary); font-size:.72rem;"><?= count($dossiers_envoyés) ?></span>
                            </button>
                        </li>
                    </ul>
                    <div class="tab-content">
                        <div class="tab-pane fade show active" id="icReçus">
                            <?php if (!empty($dossiers_reçus)): foreach ($dossiers_reçus as $r): ?>
                            <div class="alert alert-light border d-flex justify-content-between align-items-center mb-2 rounded-3">
                                <div>
                                    <strong style="color:var(--ic-primary);"><?= htmlspecialchars($r['nom'] . ' ' . $r['prenom']) ?></strong><br>
                                    <small class="text-muted"><i class="bi bi-person-fill me-1"></i>Envoyé par <?= htmlspecialchars($r['expediteur_nom']) ?></small>
                                </div>
                                <a href="<?= BASE_URL ?>patients/dossier/<?= (int)($r['patient_id'] ?? 0) ?>"
                                   class="btn btn-sm rounded-pill px-3 fw-bold"
                                   style="background:var(--ic-primary); color:white;">
                                    <i class="bi bi-folder2-open me-1"></i>Ouvrir
                                </a>
                            </div>
                            <?php endforeach; else: ?>
                            <div class="ic-empty"><i class="bi bi-inbox"></i><p>Aucun dossier reçu.</p></div>
                            <?php endif; ?>
                        </div>
                        <div class="tab-pane fade" id="icEnvoyés">
                            <?php if (!empty($dossiers_envoyés)): foreach ($dossiers_envoyés as $e): ?>
                            <div class="alert alert-light border mb-2 rounded-3">
                                <strong class="text-dark"><?= htmlspecialchars($e['nom'] . ' ' . $e['prenom']) ?></strong><br>
                                <small class="text-muted"><i class="bi bi-send-check me-1"></i>Partagé à <?= htmlspecialchars($e['destinataire_nom']) ?></small>
                            </div>
                            <?php endforeach; else: ?>
                            <div class="ic-empty"><i class="bi bi-send"></i><p>Aucun dossier envoyé.</p></div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <!-- ── COLONNE LATÉRALE (4/12) ───────────────────────── -->
        <div class="col-lg-4">

            <!-- ACCÈS RAPIDES -->
            <div class="ic-card">
                <div class="ic-card-header">
                    <h5 class="ic-card-title">
                        <div class="ic-card-icon" style="background:#fef3c7; color:#d97706;"><i class="bi bi-lightning-charge-fill"></i></div>
                        Accès Rapides
                    </h5>
                </div>
                <div style="padding:16px;">
                    <a href="<?= BASE_URL ?>patients/mes-patients" class="quick-btn">
                        <div class="quick-btn-icon"><i class="bi bi-search"></i></div>
                        <div>
                            <div>Rechercher un patient</div>
                            <div class="quick-btn-sub">Parcourir les dossiers existants</div>
                        </div>
                    </a>
                    <a href="<?= BASE_URL ?>consultation" class="quick-btn">
                        <div class="quick-btn-icon" style="background:#2563eb;"><i class="bi bi-clipboard2-plus"></i></div>
                        <div>
                            <div>Nouvelle consultation</div>
                            <div class="quick-btn-sub">Sélectionner un patient à consulter</div>
                        </div>
                    </a>
                    <a href="<?= BASE_URL ?>laboratoire" class="quick-btn">
                        <div class="quick-btn-icon" style="background:#7c3aed;"><i class="bi bi-flask"></i></div>
                        <div>
                            <div>Bilans laboratoire</div>
                            <div class="quick-btn-sub">Suivre les bilans demandés</div>
                        </div>
                    </a>
                    <a href="<?= BASE_URL ?>prescription/create" class="quick-btn">
                        <div class="quick-btn-icon" style="background:#dc2626;"><i class="bi bi-capsule"></i></div>
                        <div>
                            <div>Nouvelle prescription</div>
                            <div class="quick-btn-sub">Rédiger une ordonnance</div>
                        </div>
                    </a>
                    <a href="<?= BASE_URL ?>patients/mes-patients" class="quick-btn">
                        <div class="quick-btn-icon" style="background:#0891b2;"><i class="bi bi-folder2-open"></i></div>
                        <div>
                            <div>Mes dossiers patients</div>
                            <div class="quick-btn-sub">Historique et suivis</div>
                        </div>
                    </a>
                </div>
            </div>

            <!-- CONSULTATIONS DU JOUR -->
            <div class="ic-card">
                <div class="ic-card-header">
                    <h5 class="ic-card-title">
                        <div class="ic-card-icon" style="background:#dbeafe; color:#2563eb;"><i class="bi bi-clock-history"></i></div>
                        Consultations du jour
                    </h5>
                    <small class="text-muted"><?= count($patients_consultes) ?> acte(s)</small>
                </div>
                <div class="p-3">
                    <?php if (!empty($patients_consultes)): foreach ($patients_consultes as $hc): ?>
                    <div class="consult-row">
                        <div class="flex-grow-1 min-width-0">
                            <div class="fw-bold text-truncate" style="font-size:.88rem; color:#1e293b;">
                                <?= strtoupper(htmlspecialchars($hc['nom'])) ?> <?= htmlspecialchars($hc['prenom']) ?>
                            </div>
                            <small class="text-muted">
                                <i class="bi bi-clock me-1"></i><?= date('H:i', strtotime($hc['date_consultation'])) ?>
                                &nbsp;·&nbsp;<span class="font-monospace" style="font-size:.73rem;"><?= htmlspecialchars($hc['dossier_numero'] ?? '') ?></span>
                            </small>
                        </div>
                        <a href="<?= BASE_URL ?>patients/dossier/<?= (int)$hc['patient_id'] ?>"
                           class="ic-action-btn flex-shrink-0" title="Ouvrir le dossier">
                            <i class="bi bi-folder2-open"></i>
                        </a>
                    </div>
                    <?php endforeach; else: ?>
                    <div class="ic-empty">
                        <i class="bi bi-calendar-check"></i>
                        <p>Aucune consultation effectuée aujourd'hui.</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- NOTES & RAPPELS -->
            <div class="ic-card">
                <div class="ic-card-header">
                    <h5 class="ic-card-title">
                        <div class="ic-card-icon" style="background:#fef3c7; color:#d97706;"><i class="bi bi-sticky-fill"></i></div>
                        Notes &amp; Rappels
                    </h5>
                    <button class="ic-action-btn" style="width:32px;height:32px;font-size:.9rem;"
                            onclick="document.getElementById('icTodoIn').focus()" title="Ajouter une note">
                        <i class="bi bi-plus-lg"></i>
                    </button>
                </div>
                <div class="p-3">
                    <div class="input-group mb-3" style="border-radius:10px; overflow:hidden; border:1px solid var(--ic-border);">
                        <input type="text" id="icTodoIn"
                               class="form-control border-0"
                               style="background:#f8fafc; font-size:.85rem;"
                               placeholder="Ajouter une note rapide..."
                               onkeydown="if(event.key==='Enter') icAddTask()">
                        <button class="btn fw-bold" onclick="icAddTask()"
                                style="background:var(--ic-primary); color:white; border:none; font-size:.83rem; padding:0 14px;">OK</button>
                    </div>
                    <div id="icTodoList">
                        <?php if (!empty($mes_taches)): foreach ($mes_taches as $t): ?>
                        <div class="todo-row">
                            <div class="form-check mb-0">
                                <input class="form-check-input" type="checkbox"
                                       <?= $t['is_done'] ? 'checked' : '' ?>
                                       style="border-color:var(--ic-primary);"
                                       onchange="icToggleTask(<?= $t['id'] ?>)">
                                <label class="form-check-label" style="font-size:.84rem; <?= $t['is_done'] ? 'text-decoration:line-through; color:#94a3b8;' : 'color:#334155;' ?>">
                                    <?= htmlspecialchars($t['label']) ?>
                                </label>
                            </div>
                            <i class="bi bi-trash text-muted" style="cursor:pointer; font-size:.82rem;"
                               onclick="icDeleteTask(<?= $t['id'] ?>)"></i>
                        </div>
                        <?php endforeach; else: ?>
                        <p class="text-muted text-center mt-2" style="font-size:.83rem;">Aucune note. Ajoutez-en une !</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- ── MODAL : RÉSULTATS LABO ────────────────────────────────── -->
<div class="modal fade modal-ic" id="modalResultatsLabo" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header" style="padding:20px 24px; border-bottom:1px solid #f1f5f9;">
                <h5 class="modal-title fw-bold">
                    <i class="bi bi-flask me-2" style="color:var(--ic-primary);"></i>Résultats du Bilan
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4" id="resultatsLaboBody">
                <div class="text-center py-4">
                    <div class="spinner-border" style="color:var(--ic-primary);"></div>
                </div>
            </div>
            <div class="modal-footer" style="border-top:1px solid #f1f5f9; padding:16px 24px;">
                <button type="button" class="btn btn-light rounded-pill" data-bs-dismiss="modal">Fermer</button>
                <button class="btn rounded-pill fw-bold" onclick="commenterDepuisResultat()"
                        style="background:var(--ic-primary); color:white;">
                    <i class="bi bi-chat-text me-1"></i>Ajouter une note
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ── MODAL : COMMENTER / NOTE CLINIQUE ─────────────────────── -->
<div class="modal fade modal-ic" id="modalCommenter" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header" style="padding:20px 24px; border-bottom:1px solid #f1f5f9;">
                <h5 class="modal-title fw-bold">
                    <i class="bi bi-chat-text me-2" style="color:var(--ic-primary);"></i>Note clinique
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <input type="hidden" id="c_demande_id">
                <input type="hidden" id="c_type_bilan">
                <input type="hidden" id="c_patient_id">
                <div class="alert rounded-3 border mb-3 small" style="background:var(--ic-light); border-color:var(--ic-border)!important;" id="c_bilan_info"></div>
                <label class="form-label small fw-bold text-muted text-uppercase" style="letter-spacing:.6px;">Commentaire / Observation clinique</label>
                <textarea id="c_commentaire" class="form-control" rows="5"
                          style="border-color:var(--ic-border); border-radius:12px;"
                          placeholder="Observations, interprétation, conduite à tenir..."></textarea>
                <div id="commentaires_existants" class="mt-3"></div>
            </div>
            <div class="modal-footer" style="border-top:1px solid #f1f5f9; padding:16px 24px;">
                <button type="button" class="btn btn-light rounded-pill" data-bs-dismiss="modal">Annuler</button>
                <button class="btn rounded-pill fw-bold" onclick="enregistrerCommentaire()"
                        style="background:var(--ic-primary); color:white;">
                    <i class="bi bi-check2 me-1"></i>Enregistrer
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ══════════════════ MODAL PROFIL ══════════════════ -->
<div id="modalProfil"
     style="display:none;position:fixed;inset:0;z-index:9999;
            background:rgba(15,23,42,.45);backdrop-filter:blur(4px);
            display:none;align-items:center;justify-content:center;padding:16px;">
    <div style="background:#fff;border-radius:20px;width:100%;max-width:520px;
                box-shadow:0 24px 64px rgba(0,0,0,.18);overflow:hidden;
                animation:profilSlideIn .25s ease;">

        <!-- En-tête gradient -->
        <div style="background:linear-gradient(135deg,#0d9488,#0891b2);padding:28px 28px 20px;position:relative;">
            <button onclick="fermerProfilModal()"
                    style="position:absolute;top:14px;right:14px;background:rgba(255,255,255,.2);
                           border:none;border-radius:50%;width:32px;height:32px;color:#fff;
                           font-size:1rem;cursor:pointer;display:flex;align-items:center;justify-content:center;">
                <i class="bi bi-x-lg"></i>
            </button>
            <!-- Avatar initiales -->
            <div style="width:64px;height:64px;border-radius:16px;background:#fff;
                        display:flex;align-items:center;justify-content:center;
                        font-size:1.4rem;font-weight:800;color:#0d9488;margin-bottom:12px;
                        box-shadow:0 4px 16px rgba(0,0,0,.15);"
                 id="pf-avatar">NJ</div>
            <div style="font-size:1.1rem;font-weight:800;color:#fff;" id="pf-fullname">—</div>
            <div style="font-size:.75rem;color:rgba(255,255,255,.8);margin-top:4px;display:flex;gap:10px;flex-wrap:wrap;">
                <span id="pf-role-badge" style="background:rgba(255,255,255,.2);padding:2px 10px;border-radius:20px;font-weight:700;"></span>
                <span id="pf-service-badge" style="background:rgba(255,255,255,.15);padding:2px 10px;border-radius:20px;"></span>
            </div>
        </div>

        <!-- Corps scrollable -->
        <div style="padding:24px 28px;max-height:70vh;overflow-y:auto;" id="pf-body">
            <!-- Spinner chargement -->
            <div id="pf-loading" style="text-align:center;padding:30px;color:#94a3b8;">
                <div class="spinner-border spinner-border-sm" style="color:#0d9488;"></div>
                <div style="margin-top:8px;font-size:.82rem;">Chargement…</div>
            </div>

            <!-- Formulaire (caché pendant le chargement) -->
            <div id="pf-form" style="display:none;">
                <div style="font-size:.72rem;font-weight:700;text-transform:uppercase;
                             letter-spacing:.6px;color:#94a3b8;margin-bottom:14px;">
                    Informations personnelles
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px;">
                    <div>
                        <label style="font-size:.72rem;font-weight:700;color:#475569;display:block;margin-bottom:4px;">NOM</label>
                        <input type="text" id="pf-nom" class="form-control form-control-sm"
                               style="border-radius:10px;border-color:#e2e8f0;">
                    </div>
                    <div>
                        <label style="font-size:.72rem;font-weight:700;color:#475569;display:block;margin-bottom:4px;">PRÉNOM</label>
                        <input type="text" id="pf-prenom" class="form-control form-control-sm"
                               style="border-radius:10px;border-color:#e2e8f0;">
                    </div>
                    <div>
                        <label style="font-size:.72rem;font-weight:700;color:#475569;display:block;margin-bottom:4px;">EMAIL</label>
                        <input type="email" id="pf-email" class="form-control form-control-sm"
                               style="border-radius:10px;border-color:#e2e8f0;">
                    </div>
                    <div>
                        <label style="font-size:.72rem;font-weight:700;color:#475569;display:block;margin-bottom:4px;">TÉLÉPHONE</label>
                        <input type="tel" id="pf-tel" class="form-control form-control-sm"
                               style="border-radius:10px;border-color:#e2e8f0;" placeholder="+237…">
                    </div>
                </div>

                <!-- Identifiant (lecture seule) -->
                <div style="background:#f8fafc;border-radius:10px;padding:10px 14px;margin-bottom:18px;
                             display:flex;align-items:center;gap:10px;border:1px solid #e2e8f0;">
                    <i class="bi bi-at" style="color:#64748b;font-size:1rem;"></i>
                    <div>
                        <div style="font-size:.68rem;color:#94a3b8;font-weight:600;">LOGIN (non modifiable)</div>
                        <div style="font-size:.85rem;font-weight:700;color:#1e293b;" id="pf-username">—</div>
                    </div>
                </div>

                <!-- Changer mot de passe -->
                <div style="font-size:.72rem;font-weight:700;text-transform:uppercase;
                             letter-spacing:.6px;color:#94a3b8;margin-bottom:14px;">
                    Changer le mot de passe
                    <span style="font-size:.65rem;color:#b0bec5;font-weight:400;text-transform:none;"> — laisser vide pour ne pas modifier</span>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:20px;">
                    <div>
                        <label style="font-size:.72rem;font-weight:700;color:#475569;display:block;margin-bottom:4px;">MOT DE PASSE ACTUEL</label>
                        <input type="password" id="pf-pwd-current" class="form-control form-control-sm"
                               style="border-radius:10px;border-color:#e2e8f0;" placeholder="••••••••" autocomplete="current-password">
                    </div>
                    <div>
                        <label style="font-size:.72rem;font-weight:700;color:#475569;display:block;margin-bottom:4px;">NOUVEAU MOT DE PASSE</label>
                        <input type="password" id="pf-pwd-new" class="form-control form-control-sm"
                               style="border-radius:10px;border-color:#e2e8f0;" placeholder="Min. 6 caractères" autocomplete="new-password">
                    </div>
                </div>

                <!-- Message feedback -->
                <div id="pf-msg" style="display:none;border-radius:10px;padding:10px 14px;
                                         margin-bottom:14px;font-size:.8rem;font-weight:600;"></div>

                <!-- Bouton enregistrer -->
                <button onclick="sauvegarderProfil()"
                        style="width:100%;background:linear-gradient(135deg,#0d9488,#0891b2);
                               color:#fff;border:none;border-radius:12px;padding:12px;
                               font-size:.88rem;font-weight:700;cursor:pointer;
                               display:flex;align-items:center;justify-content:center;gap:8px;
                               transition:opacity .15s;"
                        id="pf-save-btn">
                    <i class="bi bi-check2-circle"></i> Enregistrer les modifications
                </button>
            </div>
        </div>
    </div>
</div>

<style>
@keyframes profilSlideIn {
    from { opacity:0; transform:translateY(-16px) scale(.97); }
    to   { opacity:1; transform:translateY(0) scale(1); }
}
</style>

<script>
// ── Horloge ──────────────────────────────────────────────────
setInterval(() => {
    document.getElementById('icClock').textContent = new Date().toLocaleTimeString('fr-FR');
}, 1000);

// ── Modal Profil ──────────────────────────────────────────────
function ouvrirProfilModal() {
    const modal = document.getElementById('modalProfil');
    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';

    // Reset
    document.getElementById('pf-loading').style.display = 'block';
    document.getElementById('pf-form').style.display    = 'none';
    document.getElementById('pf-msg').style.display     = 'none';
    document.getElementById('pf-pwd-current').value     = '';
    document.getElementById('pf-pwd-new').value         = '';

    fetch('<?= BASE_URL ?>profil/json')
        .then(r => r.json())
        .then(d => {
            if (!d.success) return;

            // Initiales avatar
            const initials = (d.prenom[0] || '') + (d.nom[0] || '');
            document.getElementById('pf-avatar').textContent       = initials.toUpperCase();
            document.getElementById('pf-fullname').textContent     = d.nom.toUpperCase() + ' ' + d.prenom;
            document.getElementById('pf-role-badge').textContent   = d.role.replace(/_/g,' ');
            document.getElementById('pf-service-badge').textContent= d.service;

            // Formulaire
            document.getElementById('pf-nom').value      = d.nom;
            document.getElementById('pf-prenom').value   = d.prenom;
            document.getElementById('pf-email').value    = d.email;
            document.getElementById('pf-tel').value      = d.telephone;
            document.getElementById('pf-username').textContent = d.username;

            document.getElementById('pf-loading').style.display = 'none';
            document.getElementById('pf-form').style.display    = 'block';
        })
        .catch(() => {
            document.getElementById('pf-loading').innerHTML =
                '<i class="bi bi-exclamation-triangle text-danger"></i><div style="margin-top:8px;font-size:.82rem;color:#dc2626;">Erreur de chargement</div>';
        });
}

function fermerProfilModal() {
    document.getElementById('modalProfil').style.display = 'none';
    document.body.style.overflow = '';
}

// Fermer en cliquant sur l'overlay
document.getElementById('modalProfil').addEventListener('click', function(e) {
    if (e.target === this) fermerProfilModal();
});

function sauvegarderProfil() {
    const btn     = document.getElementById('pf-save-btn');
    const msgDiv  = document.getElementById('pf-msg');
    const newPwd  = document.getElementById('pf-pwd-new').value.trim();
    const curPwd  = document.getElementById('pf-pwd-current').value.trim();

    if (newPwd && !curPwd) {
        afficherMsgProfil('Veuillez saisir votre mot de passe actuel.', 'error'); return;
    }

    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Enregistrement…';

    const payload = {
        nom:              document.getElementById('pf-nom').value.trim(),
        prenom:           document.getElementById('pf-prenom').value.trim(),
        email:            document.getElementById('pf-email').value.trim(),
        telephone:        document.getElementById('pf-tel').value.trim(),
        new_password:     newPwd,
        current_password: curPwd,
    };

    fetch('<?= BASE_URL ?>profil/update-json', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(payload)
    })
    .then(r => r.json())
    .then(res => {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-check2-circle"></i> Enregistrer les modifications';

        if (res.success) {
            afficherMsgProfil('Profil mis à jour avec succès !', 'success');
            document.getElementById('pf-fullname').textContent = res.nom.toUpperCase() + ' ' + res.prenom;
            document.getElementById('pf-avatar').textContent   =
                (res.prenom[0] || '') + (res.nom[0] || '');
            document.getElementById('pf-pwd-current').value = '';
            document.getElementById('pf-pwd-new').value     = '';
            // Mettre à jour le nom dans le header
            const headerName = document.querySelector('.ic-header div > div:first-child');
            if (headerName) headerName.textContent = 'Inf. ' + res.prenom + ' ' + res.nom;
        } else {
            afficherMsgProfil(res.message || 'Erreur lors de la sauvegarde.', 'error');
        }
    })
    .catch(() => {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-check2-circle"></i> Enregistrer les modifications';
        afficherMsgProfil('Erreur réseau. Veuillez réessayer.', 'error');
    });
}

function afficherMsgProfil(msg, type) {
    const div = document.getElementById('pf-msg');
    div.style.display    = 'block';
    div.textContent      = msg;
    div.style.background = type === 'success' ? '#f0fdf4' : '#fef2f2';
    div.style.color      = type === 'success' ? '#166534' : '#dc2626';
    div.style.border     = '1px solid ' + (type === 'success' ? '#bbf7d0' : '#fecaca');
    if (type === 'success') setTimeout(() => { div.style.display = 'none'; }, 3500);
}

// ── Recherche + filtre date — file d'attente ──────────────────
let _fileDateMode   = 'all';   // 'all' | 'today' | 'yesterday' | 'custom'
let _fileDateCustom = '';

const TODAY     = new Date().toISOString().slice(0,10);
const YESTERDAY = new Date(Date.now() - 86400000).toISOString().slice(0,10);

function setDateFilter(mode, el) {
    _fileDateMode = mode;
    _fileDateCustom = '';
    document.querySelectorAll('.date-filter-btn').forEach(b => b.classList.remove('active'));
    if (mode === 'custom') {
        _fileDateCustom = el.value;
    } else if (el && el.classList) {
        el.classList.add('active');
        document.getElementById('fDatePicker').value = '';
    }
    filtrerFile();
}

function filtrerFile() {
    const needle = (document.getElementById('fileSearchInput')?.value || '').trim().toLowerCase();
    const rows   = document.querySelectorAll('#fileAttente .patient-ic');
    const noRes  = document.getElementById('fileNoResult');
    const pill   = document.getElementById('fileCountPill');
    if (!rows.length) return;

    let visible = 0;
    rows.forEach(row => {
        const hay  = (row.dataset.search || '').toLowerCase();
        const date = row.dataset.date || '';
        let showSearch = needle === '' || hay.includes(needle);
        let showDate   = true;
        if (_fileDateMode === 'today')     showDate = (date === TODAY);
        if (_fileDateMode === 'yesterday') showDate = (date === YESTERDAY);
        if (_fileDateMode === 'custom')    showDate = _fileDateCustom === '' || date === _fileDateCustom;
        const show = showSearch && showDate;
        row.style.display = show ? '' : 'none';
        if (show) visible++;
    });
    if (pill) pill.textContent = visible + ' patient' + (visible > 1 ? 's' : '');
    if (noRes) noRes.style.display = (visible === 0) ? 'block' : 'none';
}

// ── Résultats labo ────────────────────────────────────────────
let _bilanCtx = {};

function voirResultatsLabo(demandeId) {
    document.getElementById('resultatsLaboBody').innerHTML =
        '<div class="text-center py-4"><div class="spinner-border" style="color:#0d9488;"></div></div>';
    new bootstrap.Modal(document.getElementById('modalResultatsLabo')).show();

    fetch('<?= BASE_URL ?>medecin/resultats-bilan?id=' + demandeId)
        .then(r => r.json())
        .then(data => {
            if (!data.success) {
                document.getElementById('resultatsLaboBody').innerHTML =
                    '<p class="text-danger text-center">Erreur lors du chargement.</p>';
                return;
            }
            let html = '';
            if (!data.examens.length) {
                html = '<p class="text-muted text-center py-4">Aucun résultat enregistré pour cette demande.</p>';
            } else {
                const meta = data.examens[0];
                html += `<div class="d-flex align-items-center gap-3 mb-4 p-3 rounded-3" style="background:var(--ic-light);">
                    <i class="bi bi-person-circle fs-4" style="color:var(--ic-primary);"></i>
                    <div>
                        <div class="fw-bold">${meta.patient_nom || ''} ${meta.patient_prenom || ''}</div>
                        <small class="text-muted">Demandé par ${meta.medecin_nom || ''} &nbsp;·&nbsp; Statut : ${meta.statut || ''}</small>
                    </div>
                </div>`;
                data.examens.forEach(ex => {
                    const anormal = ex.anormal == 1;
                    html += `<div class="result-row">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <div class="fw-bold">${ex.nom_examen || '—'}</div>
                                <small class="text-muted">${ex.categorie || ''}</small>
                            </div>
                            <span class="badge rounded-pill ${anormal ? 'bg-danger' : 'bg-success'}">${anormal ? 'ANORMAL' : 'NORMAL'}</span>
                        </div>`;
                    if (ex.valeur_numerique !== null) {
                        html += `<div class="mt-2 result-val ${anormal ? 'anormal' : 'normal'}">${ex.valeur_numerique}
                                    <small style="font-size:.7em;">${ex.unite || ''}</small></div>
                                 <small class="text-muted">Norme : ${ex.valeur_normale_min || '?'} – ${ex.valeur_normale_max || '?'} ${ex.unite || ''}</small>`;
                    } else if (ex.resultat) {
                        html += `<p class="mt-2 mb-0 small">${ex.resultat}</p>`;
                    }
                    if (ex.interpretation) {
                        html += `<p class="mt-1 mb-0 small" style="color:var(--ic-primary);">
                                    <i class="bi bi-chat-quote me-1"></i>${ex.interpretation}</p>`;
                    }
                    html += '</div>';
                });
            }
            if (data.commentaires && data.commentaires.length) {
                html += '<h6 class="fw-bold mt-4 mb-2 text-muted text-uppercase" style="font-size:.71rem; letter-spacing:.8px;">Notes cliniques</h6>';
                data.commentaires.forEach(c => {
                    html += `<div class="note-item">
                        <div class="n-meta">${c.medecin_nom} — ${new Date(c.created_at).toLocaleDateString('fr-FR')}</div>
                        <div class="n-text">${c.commentaire}</div>
                    </div>`;
                });
            }
            document.getElementById('resultatsLaboBody').innerHTML = html;
            _bilanCtx = { demandeId, type: 'LABO' };
        });
}

function commenterDepuisResultat() {
    bootstrap.Modal.getInstance(document.getElementById('modalResultatsLabo'))?.hide();
    fetch('<?= BASE_URL ?>medecin/resultats-bilan?id=' + _bilanCtx.demandeId)
        .then(r => r.json()).then(data => {
            if (data.examens && data.examens[0]) {
                ouvrirCommenter(
                    _bilanCtx.demandeId, 'LABO', 0,
                    (data.examens[0].patient_nom || '') + ' ' + (data.examens[0].patient_prenom || ''),
                    data.examens[0].nom_examen || 'Bilan'
                );
            }
        });
}

function ouvrirCommenter(demandeId, typeBilan, patientId, patNom, examNom) {
    document.getElementById('c_demande_id').value = demandeId;
    document.getElementById('c_type_bilan').value = typeBilan;
    document.getElementById('c_patient_id').value = patientId;
    document.getElementById('c_commentaire').value = '';
    document.getElementById('c_bilan_info').innerHTML =
        `<i class="bi bi-person-fill me-1" style="color:var(--ic-primary);"></i><strong>${patNom}</strong> — <em>${examNom}</em>`;

    fetch('<?= BASE_URL ?>medecin/resultats-bilan?id=' + demandeId)
        .then(r => r.json()).then(data => {
            let html = '';
            if (data.commentaires && data.commentaires.length) {
                html = '<h6 class="small text-muted fw-bold text-uppercase mb-2" style="letter-spacing:.6px;">Notes précédentes</h6>';
                data.commentaires.forEach(c => {
                    html += `<div class="note-item">
                        <div class="n-meta">${c.medecin_nom} — ${new Date(c.created_at).toLocaleDateString('fr-FR')}</div>
                        <div class="n-text">${c.commentaire}</div>
                    </div>`;
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

    fetch('<?= BASE_URL ?>medecin/commenter-bilan', { method: 'POST', body: fd })
        .then(r => r.json()).then(data => {
            if (data.success) {
                bootstrap.Modal.getInstance(document.getElementById('modalCommenter'))?.hide();
                const t = document.createElement('div');
                t.style.cssText = 'position:fixed;bottom:20px;right:20px;background:#0d9488;color:white;padding:12px 22px;border-radius:14px;font-weight:700;z-index:9999;box-shadow:0 8px 24px rgba(0,0,0,.2);';
                t.innerHTML = '<i class="bi bi-check2-circle me-2"></i>Note enregistrée avec succès.';
                document.body.appendChild(t);
                setTimeout(() => { t.remove(); location.reload(); }, 2000);
            } else {
                alert('Erreur : ' + (data.message || 'Inconnue'));
            }
        });
}

// ── Notes / To-do ─────────────────────────────────────────────
function icAddTask() {
    const label = document.getElementById('icTodoIn').value.trim();
    if (!label) return;
    fetch('<?= BASE_URL ?>dashboard/add-task', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'label=' + encodeURIComponent(label)
    }).then(() => location.reload());
}

function icToggleTask(id) {
    fetch('<?= BASE_URL ?>dashboard/toggle-task', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'id=' + id
    });
}

function icDeleteTask(id) {
    if (!confirm('Supprimer cette note ?')) return;
    fetch('<?= BASE_URL ?>dashboard/delete-task', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'id=' + id
    }).then(() => location.reload());
}
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
