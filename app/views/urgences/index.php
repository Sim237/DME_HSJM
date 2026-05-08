<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<style>
/* ══════════════════════════════════════
   COCKPIT INFIRMIER URGENCES — STYLES
══════════════════════════════════════ */
body { background: #f0f4f8; }

.cockpit-nav {
    background: linear-gradient(135deg, #0f172a 0%, #1e3a5f 60%, #1e40af 100%);
    padding: 14px 28px;
    display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px;
    position: sticky; top: 0; z-index: 1050;
    box-shadow: 0 4px 20px rgba(0,0,0,0.3);
}
.cockpit-title { color: #fff; font-size: 1.05rem; font-weight: 800; display: flex; align-items: center; gap: 10px; }
.cockpit-clock { font-family: monospace; font-size: 1.4rem; font-weight: bold; color: #00ff88; text-shadow: 0 0 10px rgba(0,255,100,0.3); }
.cockpit-stats { display: flex; gap: 8px; flex-wrap: wrap; }
.stat-chip { padding: 5px 13px; border-radius: 20px; font-size: 0.75rem; font-weight: 700; display: flex; align-items: center; gap: 5px; }
.stat-chip.red   { background: rgba(239,68,68,.25);  color: #fca5a5; border: 1px solid rgba(239,68,68,.4); }
.stat-chip.amber { background: rgba(245,158,11,.25); color: #fcd34d; border: 1px solid rgba(245,158,11,.4); }
.stat-chip.blue  { background: rgba(59,130,246,.25); color: #93c5fd; border: 1px solid rgba(59,130,246,.4); }
.stat-chip.green { background: rgba(34,197,94,.25);  color: #86efac; border: 1px solid rgba(34,197,94,.4); }

.main-tabs { background: #fff; border-bottom: 2px solid #e2e8f0; position: sticky; top: 65px; z-index: 1040; overflow-x: auto; white-space: nowrap; }
.main-tabs .nav-link {
    color: #64748b; font-weight: 700; font-size: 0.83rem;
    padding: 13px 18px; border: none; border-radius: 0;
    border-bottom: 3px solid transparent; transition: all .2s;
    display: inline-flex; align-items: center; gap: 7px;
}
.main-tabs .nav-link:hover { color: #1e40af; background: #f8fafc; }
.main-tabs .nav-link.active { color: #1e40af; border-bottom-color: #1e40af; }
.tab-badge { background: #ef4444; color: #fff; border-radius: 20px; padding: 1px 7px; font-size: 0.68rem; font-weight: 800; }
.tab-badge.amber { background: #f59e0b; }
.tab-badge.blue  { background: #3b82f6; }

.tab-content-area { padding: 22px; min-height: calc(100vh - 130px); }

/* URGENCES GRID */
.emergency-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(290px, 1fr)); gap: 18px; }
.flip-card { height: 305px; perspective: 1000px; }
.flip-card-inner { position: relative; width: 100%; height: 100%; transition: transform .6s; transform-style: preserve-3d; cursor: pointer; }
.flip-card:hover .flip-card-inner { transform: rotateY(180deg); }
.card-front, .card-back { position: absolute; width: 100%; height: 100%; backface-visibility: hidden; border-radius: 14px; padding: 16px; box-shadow: 0 4px 18px rgba(0,0,0,.06); }
.card-front { background: #fff; border-left: 9px solid #cbd5e1; display: flex; flex-direction: column; }
.card-back  { background: #1e293b; color: #fff; transform: rotateY(180deg); display: flex; flex-direction: column; }
.priority-1 .card-front { border-left-color: #ef4444; background: #fff5f5; }
.priority-2 .card-front { border-left-color: #f97316; }
.priority-3 .card-front { border-left-color: #eab308; }
.priority-4 .card-front { border-left-color: #22c55e; }
.priority-5 .card-front { border-left-color: #3b82f6; }
.chart-container { height: 60px; margin-bottom: 8px; }
.triage-badge { display: inline-flex; align-items: center; gap: 4px; padding: 2px 9px; border-radius: 10px; font-size: 0.7rem; font-weight: 800; color: #fff; }
.triage-1{background:#ef4444}.triage-2{background:#f97316}.triage-3{background:#eab308;color:#1e293b}.triage-4{background:#22c55e}.triage-5{background:#3b82f6}

/* À HOSPITALISER */
.hosp-alert-bar { background: linear-gradient(135deg, #fff7ed, #fffbeb); border: 2px solid #f59e0b; border-radius: 14px; padding: 14px 18px; margin-bottom: 18px; display: flex; align-items: center; gap: 12px; animation: pulse-amber 2s infinite; }
@keyframes pulse-amber { 0%,100%{box-shadow:0 0 0 #f59e0b} 50%{box-shadow:0 0 16px rgba(245,158,11,.45)} }
.hosp-card { background: #fff; border-radius: 12px; padding: 16px; border: 1px solid #e2e8f0; box-shadow: 0 2px 8px rgba(0,0,0,.05); margin-bottom: 12px; display: flex; align-items: center; gap: 14px; transition: all .2s; }
.hosp-card:hover { box-shadow: 0 8px 24px rgba(0,0,0,.1); transform: translateY(-2px); }
.hosp-avatar { width: 48px; height: 48px; border-radius: 50%; background: linear-gradient(135deg, #f59e0b, #d97706); color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 1.05rem; flex-shrink: 0; }
.urgence-tag { background: #fef3c7; color: #92400e; border: 1px solid #fcd34d; border-radius: 8px; padding: 1px 7px; font-size: 0.68rem; font-weight: 700; }
.ext-tag    { background: #eff6ff; color: #1e40af; border: 1px solid #bfdbfe; border-radius: 8px; padding: 1px 7px; font-size: 0.68rem; font-weight: 700; }

/* SERVICES — CARD LAYOUT */
.service-section { margin-bottom: 32px; }
.service-header { background: linear-gradient(135deg, #1e293b, #334155); color: #fff; border-radius: 14px 14px 0 0; padding: 13px 20px; display: flex; align-items: center; justify-content: space-between; }
.service-cards-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 14px; padding: 16px; background: #f8fafc; border: 1px solid #e2e8f0; border-top: none; border-radius: 0 0 14px 14px; }
.pat-card {
    background: #fff; border-radius: 14px; border: 1px solid #e2e8f0;
    box-shadow: 0 2px 10px rgba(0,0,0,.05); overflow: hidden;
    transition: all .2s ease;
}
.pat-card:hover { transform: translateY(-3px); box-shadow: 0 8px 24px rgba(0,0,0,.1); border-color: #93c5fd; }
.pat-card-top { padding: 14px 16px 10px; display: flex; align-items: center; gap: 12px; }
.inf-avatar { width: 44px; height: 44px; border-radius: 50%; background: linear-gradient(135deg, #1e40af, #3b82f6); color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 0.9rem; flex-shrink: 0; }
.pat-card-info { flex-grow: 1; min-width: 0; }
.pat-card-name { font-weight: 800; font-size: .9rem; color: #1e293b; line-height: 1.2; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.pat-card-meta { font-size: .72rem; color: #64748b; margin-top: 2px; }
.pat-card-progress { padding: 0 16px 10px; }
.progress-bar-wrap { height: 6px; border-radius: 4px; background: #e2e8f0; overflow: hidden; }
.progress-fill { height: 100%; border-radius: 4px; transition: width .3s; }
.progress-fill.done { background: #22c55e; }
.progress-fill.partial { background: #f59e0b; }
.progress-fill.low { background: #ef4444; }
.pat-card-actions { padding: 10px 12px 12px; border-top: 1px solid #f1f5f9; display: flex; gap: 7px; flex-wrap: wrap; align-items: center; }
.act-btn { display: inline-flex; align-items: center; gap: 5px; padding: 7px 13px; border-radius: 10px; font-size: .78rem; font-weight: 700; text-decoration: none; border: none; cursor: pointer; transition: all .15s; white-space: nowrap; }
.act-btn-soins     { background: #16a34a; color: #fff; }
.act-btn-soins:hover { background: #15803d; color: #fff; }
.act-btn-plan      { background: #eff6ff; color: #1e40af; border: 1.5px solid #bfdbfe; }
.act-btn-plan:hover { background: #dbeafe; color: #1e40af; }
.act-btn-constantes{ background: #ecfdf5; color: #065f46; border: 1.5px solid #6ee7b7; }
.act-btn-constantes:hover { background: #d1fae5; color: #065f46; }
.act-btn-suivi     { background: #fffbeb; color: #92400e; border: 1.5px solid #fcd34d; }
.act-btn-suivi:hover { background: #fef3c7; color: #92400e; }
.act-btn-more      { background: #f1f5f9; color: #475569; margin-left: auto; padding: 7px 10px; border-radius: 10px; }
.act-btn-more:hover { background: #e2e8f0; color: #1e293b; }
.act-btn-liberer   { background: #fff1f2; color: #be123c; border: 1.5px solid #fda4af; border-radius: 10px; padding: 7px 13px; }
.act-btn-liberer:hover { background: #ffe4e6; color: #9f1239; }

/* LITS */
.lits-overview { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 14px; }
.lit-service-card { background: #fff; border-radius: 12px; padding: 18px; border: 1px solid #e2e8f0; box-shadow: 0 2px 8px rgba(0,0,0,.04); text-align: center; }
.lit-donut-wrap { position: relative; width: 80px; height: 80px; margin: 0 auto 10px; }
.lit-donut-wrap svg { transform: rotate(-90deg); }
.lit-donut-text { position: absolute; top: 50%; left: 50%; transform: translate(-50%,-50%); }
.lit-donut-text .num { font-size: 1.2rem; font-weight: 800; line-height: 1; }
.lit-donut-text .lbl { font-size: 0.52rem; color: #64748b; text-transform: uppercase; }

/* MODAL */
.modal-assign .modal-header { background: linear-gradient(135deg, #0f172a, #1e40af); color: #fff; }
.lit-select-item { display: flex; align-items: center; gap: 10px; padding: 9px 12px; border: 1px solid #e2e8f0; border-radius: 9px; margin-bottom: 7px; cursor: pointer; transition: .15s; }
.lit-select-item:hover, .lit-select-item.selected { border-color: #3b82f6; background: #eff6ff; }

.empty-state { text-align: center; padding: 60px 20px; color: #94a3b8; }
.empty-state i { font-size: 3rem; margin-bottom: 12px; display: block; }

/* Dropdown actions dans la liste patients */
.dropdown-menu { border: none !important; box-shadow: 0 8px 24px rgba(0,0,0,.12) !important; }
.dropdown-item:hover { background: #f1f5f9; }
.dropdown-header { font-size: .65rem !important; letter-spacing: .5px; text-transform: uppercase; }

/* Provenance / destination badges */
.provenance-arrow { font-size: .7rem; color: #94a3b8; }

@keyframes slideIn { from { transform: translateY(20px); opacity:0; } to { transform:translateY(0);opacity:1; } }
</style>

<!-- ══ BARRE COCKPIT ══ -->
<nav class="cockpit-nav">
    <div class="cockpit-title">
        <i class="bi bi-hospital-fill text-info fs-5"></i>
        <div>
            <div>Cockpit Infirmier — Urgences &amp; Services</div>
            <small style="opacity:.55;font-weight:400;font-size:0.7rem;"><?= htmlspecialchars($_SESSION['nom_complet'] ?? ($_SESSION['user_nom'] ?? '')) ?></small>
        </div>
    </div>
    <div class="cockpit-stats">
        <div class="stat-chip red"><i class="bi bi-exclamation-triangle-fill"></i> P1 VITAL: <?= $stats['P1'] ?></div>
        <div class="stat-chip amber"><i class="bi bi-clock-fill"></i> EN ATTENTE: <?= $stats['waiting_med'] ?></div>
        <div class="stat-chip green"><i class="bi bi-person-check-fill"></i> À HOSP.: <?= count($a_hospitaliser) ?></div>
        <div class="stat-chip blue"><i class="bi bi-bed-fill"></i> LITS LIBRES: <?= count($lits_disponibles) ?></div>
    </div>
    <div class="d-flex align-items-center gap-3">
        <div id="cockpit-clock" class="cockpit-clock">--:--:--</div>
        <a href="<?= BASE_URL ?>logout" title="Déconnecter"
           style="width:38px;height:38px;border-radius:50%;background:rgba(239,68,68,.2);border:1.5px solid rgba(239,68,68,.5);color:#fca5a5;display:flex;align-items:center;justify-content:center;text-decoration:none;transition:all .2s;flex-shrink:0;"
           onmouseover="this.style.background='rgba(239,68,68,.45)';this.style.color='#fff'"
           onmouseout="this.style.background='rgba(239,68,68,.2)';this.style.color='#fca5a5'">
            <i class="bi bi-power fs-5"></i>
        </a>
    </div>
</nav>

<!-- ══ ONGLETS ══ -->
<ul class="nav main-tabs" id="cockpitTabs">
    <li class="nav-item">
        <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tabUrgences" type="button">
            <i class="bi bi-lightning-charge-fill text-danger"></i> Urgences
            <?php if($stats['P1']>0): ?><span class="tab-badge"><?= $stats['P1'] ?></span><?php endif; ?>
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabHospitaliser" type="button">
            <i class="bi bi-house-heart-fill" style="color:#f59e0b"></i> À Hospitaliser
            <?php if(count($a_hospitaliser)>0): ?><span class="tab-badge amber"><?= count($a_hospitaliser) ?></span><?php endif; ?>
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabServices" type="button">
            <i class="bi bi-grid-3x3-gap-fill text-primary"></i> Services &amp; Soins
            <span class="tab-badge blue"><?= count($patients_hospitalises_all) ?></span>
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabLits" type="button">
            <i class="bi bi-grid-fill"></i> Lits
        </button>
    </li>
    <li class="nav-item ms-auto d-flex align-items-center gap-2 pe-3">
        <button class="btn btn-outline-danger btn-sm rounded-pill mt-2" onclick="location.href='<?= BASE_URL ?>urgences/nouvelle-admission'">
            <i class="bi bi-stack"></i> Aflux Massif
        </button>
        <button type="button" class="btn btn-primary btn-sm rounded-pill mt-2"
                onclick="new bootstrap.Modal(document.getElementById('modalFastAdmission')).show()">
            <i class="bi bi-plus-lg"></i> Admission Rapide
        </button>
    </li>
</ul>

<div class="tab-content">

<!-- ══════════════ ONGLET 1 : URGENCES ══════════════ -->
<div class="tab-pane fade show active tab-content-area" id="tabUrgences">
    <?php if(empty($admissions)): ?>
    <div class="empty-state">
        <i class="bi bi-patch-check-fill text-success"></i>
        <h5 class="text-success">Aucune urgence active</h5>
        <p>Tous les patients urgences ont été pris en charge.</p>
    </div>
    <?php else: ?>
    <div class="emergency-grid">
        <?php foreach($admissions as $adm):
            $niv = $adm['niveau_triage'] ?? '3';
            $couleurLabels = ['1'=>'ROUGE','2'=>'ORANGE','3'=>'JAUNE','4'=>'VERT','5'=>'BLEU'];
            $couleur = $couleurLabels[$niv] ?? 'JAUNE';
            $age = !empty($adm['date_naissance']) ? date_diff(date_create($adm['date_naissance']), date_create('now'))->y.'ans' : '?';
        ?>
        <div class="flip-card priority-<?= $niv ?>">
            <div class="flip-card-inner">
                <div class="card-front">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <span class="triage-badge triage-<?= $niv ?>">P<?= $niv ?> – <?= $couleur ?></span>
                        <small class="text-muted fw-bold"><?= date('H:i', strtotime($adm['heure_arrivee'])) ?></small>
                    </div>
                    <h6 class="fw-bold mb-0 text-dark"><?= htmlspecialchars(strtoupper($adm['nom']).' '.$adm['prenom']) ?></h6>
                    <small class="text-muted"><?= $age ?> • <?= $adm['sexe']=='M'?'M':'F' ?></small>
                    <p class="small text-danger fw-bold mt-2 mb-2" style="min-height:32px;font-size:.78rem"><?= htmlspecialchars($adm['motif_plainte'] ?? $adm['motif_admission'] ?? 'En attente de triage...') ?></p>

                    <div class="row g-1 text-center bg-light rounded-3 p-2 mt-auto">
                        <div class="col-3 border-end"><div style="font-size:.6rem;color:#94a3b8">GCS</div><strong class="small"><?= $adm['score_glasgow']??'--' ?></strong></div>
                        <div class="col-3 border-end"><div style="font-size:.6rem;color:#94a3b8">Pouls</div><strong class="small text-primary"><?= $adm['pouls']??'--' ?></strong></div>
                        <div class="col-3 border-end"><div style="font-size:.6rem;color:#94a3b8">SpO2</div><strong class="small text-success"><?= $adm['spo2'] ? $adm['spo2'].'%' : '--' ?></strong></div>
                        <div class="col-3"><div style="font-size:.6rem;color:#94a3b8">TA</div><strong class="small"><?= ($adm['tension_sys']&&$adm['tension_dia'])?$adm['tension_sys'].'/'.$adm['tension_dia']:'--' ?></strong></div>
                    </div>
                    <?php if(!empty($adm['nb_bilans_dispo'])): ?>
                    <div class="mt-2"><span class="badge bg-success-subtle text-success border" style="font-size:.65rem"><i class="bi bi-flask-fill"></i> <?= $adm['nb_bilans_dispo'] ?> résultat(s)</span></div>
                    <?php endif; ?>
                    <div class="mt-2 d-flex gap-2">
                        <a href="<?= BASE_URL ?>urgences/triage/<?= $adm['id'] ?>" class="btn btn-outline-dark btn-sm flex-grow-1 rounded-pill" style="font-size:.75rem">
                            <i class="bi bi-clipboard2-pulse"></i> Triage
                        </a>
                        <a href="<?= BASE_URL ?>patients/dossier/<?= $adm['patient_id'] ?>" class="btn btn-primary btn-sm flex-grow-1 rounded-pill" style="font-size:.75rem">
                            <i class="bi bi-folder2-open"></i> Dossier
                        </a>
                    </div>
                </div>
                <div class="card-back">
                    <h6 class="text-center mb-1 fw-bold text-info" style="font-size:.78rem">TENDANCES VITALES</h6>
                    <?php if(!empty($adm['medecin_nom'])): ?>
                    <div class="text-center mb-2" style="font-size:.72rem"><span class="text-muted">Médecin : </span><span class="text-info">Dr <?= htmlspecialchars($adm['medecin_nom']) ?></span></div>
                    <?php endif; ?>
                    <div style="font-size:.6rem;text-transform:uppercase;color:#94a3b8;font-weight:700;margin-bottom:3px">Pouls (BPM)</div>
                    <div class="chart-container"><canvas id="hr-<?= $adm['id'] ?>"></canvas></div>
                    <div style="font-size:.6rem;text-transform:uppercase;color:#94a3b8;font-weight:700;margin-bottom:3px">Tension Systolique</div>
                    <div class="chart-container"><canvas id="bp-<?= $adm['id'] ?>"></canvas></div>
                    <div class="text-center mt-auto">
                        <button class="btn btn-sm btn-outline-light rounded-pill px-3" style="font-size:.75rem" onclick="openOrientation(<?= $adm['id'] ?>, <?= $adm['patient_id'] ?>)">
                            <i class="bi bi-signpost-split"></i> Orienter le patient
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<!-- ══════════════ ONGLET 2 : À HOSPITALISER ══════════════ -->
<div class="tab-pane fade tab-content-area" id="tabHospitaliser">
    <?php if(!empty($a_hospitaliser)): ?>
    <div class="hosp-alert-bar">
        <i class="bi bi-exclamation-triangle-fill text-warning fs-4"></i>
        <div>
            <strong><?= count($a_hospitaliser) ?> patient(s) en attente d'installation</strong>
            <div class="small text-muted">Venant des consultations externes et des urgences — à orienter vers un lit.</div>
        </div>
    </div>
    <?php endif; ?>

    <?php if(empty($a_hospitaliser)): ?>
    <div class="empty-state">
        <i class="bi bi-check-circle-fill text-success"></i>
        <h5 class="text-success fw-bold">Aucun patient en attente d'hospitalisation</h5>
        <p class="text-muted">Tous les patients ont été installés.</p>
    </div>
    <?php else: foreach($a_hospitaliser as $h):
        $initials = strtoupper(substr($h['nom'],0,1).substr($h['prenom']??'',0,1));
        $isUrgences = !empty($h['urg_admission_id']);
    ?>
    <div class="hosp-card">
        <div class="hosp-avatar"><?= $initials ?></div>
        <div class="flex-grow-1" style="min-width:0">
            <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                <strong><?= htmlspecialchars(strtoupper($h['nom']).' '.($h['prenom']??'')) ?></strong>
                <span class="text-muted small">#<?= htmlspecialchars($h['dossier_numero']??'') ?></span>
                <?php if($isUrgences): ?>
                <span class="urgence-tag"><i class="bi bi-lightning-charge-fill"></i> URGENCES</span>
                <?php else: ?>
                <span class="ext-tag"><i class="bi bi-clipboard2-pulse"></i> CONSULTATION</span>
                <?php endif; ?>
            </div>
            <div class="small text-muted">
                Provenance : <strong><?= htmlspecialchars($h['nom_service_provenance'] ?? 'Consultation') ?></strong>
                <?php if(!empty($h['nom_service_destination'])): ?>
                &nbsp;→&nbsp;<span class="badge bg-primary-subtle text-primary border" style="font-size:.65rem"><?= htmlspecialchars($h['nom_service_destination']) ?></span>
                <?php endif; ?>
                <?php if(!empty($h['medecin_nom'])): ?>&nbsp;•&nbsp;Dr <?= htmlspecialchars($h['medecin_nom']) ?><?php endif; ?>
            </div>
            <?php if(!empty($h['diagnostic_principal'])): ?>
            <div class="small text-secondary mt-1" style="font-size:.75rem"><i class="bi bi-clipboard-pulse"></i> <?= htmlspecialchars(mb_substr($h['diagnostic_principal'],0,90)) ?><?= mb_strlen($h['diagnostic_principal'])>90?'…':'' ?></div>
            <?php endif; ?>
        </div>
        <div class="d-flex gap-2 flex-shrink-0 align-items-center">
            <button class="btn btn-success btn-sm rounded-pill px-3 shadow-sm fw-bold"
                    onclick="openAssignBed(<?= $h['id'] ?>, '<?= addslashes(strtoupper($h['nom']).' '.($h['prenom']??'')) ?>')">
                <i class="bi bi-bed-fill"></i> Installer
            </button>
            <a href="<?= BASE_URL ?>patients/dossier/<?= $h['id'] ?>" class="btn btn-outline-primary btn-sm rounded-pill px-3">
                <i class="bi bi-folder2-open"></i> Dossier
            </a>
        </div>
    </div>
    <?php endforeach; endif; ?>
</div>

<!-- ══════════════ ONGLET 3 : SERVICES & SOINS ══════════════ -->
<div class="tab-pane fade tab-content-area" id="tabServices">
    <?php if(empty($services_map)): ?>
    <div class="empty-state">
        <i class="bi bi-building"></i>
        <h5>Aucun patient hospitalisé actuellement</h5>
    </div>
    <?php else: foreach($services_map as $nomService => $patients):
        $soinsTotal = array_sum(array_column($patients,'total_soins'));
        $soinsFaits = array_sum(array_column($patients,'soins_faits'));
        $pctGlobal  = $soinsTotal>0 ? round($soinsFaits/$soinsTotal*100) : 0;
        $badgeClass = $pctGlobal==100?'success':($pctGlobal>=50?'warning':'danger');
    ?>
    <div class="service-section">
        <div class="service-header">
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-building-fill-check fs-5"></i>
                <strong style="font-size:.95rem"><?= htmlspecialchars($nomService) ?></strong>
                <span class="badge bg-white text-dark" style="font-size:.72rem"><?= count($patients) ?> patient<?= count($patients)>1?'s':'' ?></span>
            </div>
            <div class="d-flex align-items-center gap-2">
                <div style="width:90px;height:6px;border-radius:4px;background:rgba(255,255,255,.2);overflow:hidden">
                    <div style="width:<?= $pctGlobal ?>%;height:100%;background:<?= $pctGlobal==100?'#4ade80':($pctGlobal>=50?'#fbbf24':'#f87171') ?>;border-radius:4px"></div>
                </div>
                <span class="badge bg-<?= $badgeClass ?>" style="font-size:.72rem"><?= $soinsFaits ?>/<?= $soinsTotal ?> (<?= $pctGlobal ?>%)</span>
            </div>
        </div>
        <div class="service-cards-grid">
            <?php foreach($patients as $pat):
                $ini = strtoupper(substr($pat['nom'],0,1).substr($pat['prenom'],0,1));
                $pct = ($pat['total_soins']>0) ? round($pat['soins_faits']/$pat['total_soins']*100) : 0;
                $fillClass = $pct==100?'done':($pct>=50?'partial':'low');
                $litLabel = (!empty($pat['nom_chambre'])) ? htmlspecialchars($pat['nom_chambre'].' — '.($pat['nom_lit']??'')) : 'Lit non assigné';
            ?>
            <div class="pat-card">
                <!-- TOP : avatar + infos -->
                <div class="pat-card-top">
                    <div class="inf-avatar"><?= $ini ?></div>
                    <div class="pat-card-info">
                        <div class="pat-card-name"><?= htmlspecialchars(strtoupper($pat['nom']).' '.$pat['prenom']) ?></div>
                        <div class="pat-card-meta d-flex align-items-center gap-2 flex-wrap mt-1">
                            <span class="badge bg-light text-dark border" style="font-size:.66rem">
                                <i class="bi bi-door-closed me-1"></i><?= $litLabel ?>
                            </span>
                            <?php if(!empty($pat['medecin_resp'])): ?>
                            <span style="font-size:.72rem;color:#64748b"><i class="bi bi-person-badge me-1"></i>Dr <?= htmlspecialchars($pat['medecin_resp']) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <!-- Badge soins -->
                    <div class="flex-shrink-0 text-end">
                        <div class="fw-800" style="font-size:.78rem;color:<?= $pct==100?'#16a34a':($pct>=50?'#d97706':'#dc2626') ?>"><?= $pct ?>%</div>
                        <div style="font-size:.65rem;color:#94a3b8"><?= $pat['soins_faits']??0 ?>/<?= $pat['total_soins']??0 ?> soins</div>
                    </div>
                </div>
                <!-- BARRE PROGRESSION -->
                <div class="pat-card-progress">
                    <div class="progress-bar-wrap">
                        <div class="progress-fill <?= $fillClass ?>" style="width:<?= $pct ?>%"></div>
                    </div>
                </div>
                <!-- BOUTONS ACTIONS -->
                <div class="pat-card-actions">
                    <a href="<?= BASE_URL ?>hospitalisation/executer-soins/<?= $pat['hosp_id'] ?>" class="act-btn act-btn-soins">
                        <i class="bi bi-clipboard2-check-fill"></i> Soins du jour
                    </a>
                    <a href="<?= BASE_URL ?>hospitalisation/planifier-soins/<?= $pat['patient_id'] ?>" class="act-btn act-btn-plan">
                        <i class="bi bi-calendar2-plus"></i> Planifier
                    </a>
                    <a href="<?= BASE_URL ?>patients/mesures/<?= $pat['patient_id'] ?>" class="act-btn act-btn-constantes">
                        <i class="bi bi-activity"></i> Constantes
                    </a>
                    <a href="<?= BASE_URL ?>hospitalisation/suivi/<?= $pat['patient_id'] ?>" class="act-btn act-btn-suivi">
                        <i class="bi bi-heart-pulse"></i> Suivi
                    </a>
                    <!-- Libérer le lit -->
                    <button type="button" class="act-btn act-btn-liberer"
                            onclick="confirmerLiberationLit(<?= $pat['hosp_id'] ?>, '<?= addslashes(strtoupper($pat['nom']).' '.$pat['prenom']) ?>', '<?= addslashes($litLabel) ?>')"
                            title="Libérer le lit">
                        <i class="bi bi-door-open-fill"></i> Libérer le lit
                    </button>
                    <!-- Dropdown Plus -->
                    <div class="dropdown">
                        <button type="button" class="act-btn act-btn-more dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false" title="Plus d'actions">
                            <i class="bi bi-three-dots-vertical"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow border-0 rounded-3" style="min-width:210px;font-size:.82rem">
                            <li>
                                <a class="dropdown-item py-2" href="<?= BASE_URL ?>patients/dossier/<?= $pat['patient_id'] ?>">
                                    <i class="bi bi-folder2-open me-2 text-primary"></i>Dossier patient
                                </a>
                            </li>
                            <li><hr class="dropdown-divider my-1"></li>
                            <li><span class="dropdown-header" style="font-size:.68rem">FORMULAIRES</span></li>
                            <li>
                                <a class="dropdown-item py-2" href="<?= BASE_URL ?>formulaire/creer/bulletin-examens/<?= $pat['patient_id'] ?>">
                                    <i class="bi bi-file-earmark-text me-2 text-secondary"></i>Bulletin d'examens
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item py-2" href="<?= BASE_URL ?>formulaire/creer/certificat-hospitalisation/<?= $pat['patient_id'] ?>">
                                    <i class="bi bi-file-earmark-medical me-2 text-secondary"></i>Cert. d'hospitalisation
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item py-2" href="<?= BASE_URL ?>formulaire/creer/compte-rendu-hosp/<?= $pat['patient_id'] ?>">
                                    <i class="bi bi-journal-medical me-2 text-secondary"></i>Compte-rendu hosp.
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item py-2" href="<?= BASE_URL ?>formulaire/creer/consentement/<?= $pat['patient_id'] ?>">
                                    <i class="bi bi-pen me-2 text-secondary"></i>Formulaire consentement
                                </a>
                            </li>
                            <li><hr class="dropdown-divider my-1"></li>
                            <li>
                                <a class="dropdown-item py-2 text-danger fw-bold" href="<?= BASE_URL ?>hospitalisation/sortie/<?= $pat['hosp_id'] ?>">
                                    <i class="bi bi-box-arrow-right me-2"></i>Préparer sortie
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endforeach; endif; ?>
</div>

<!-- ══════════════ ONGLET 4 : LITS ══════════════ -->
<div class="tab-pane fade tab-content-area" id="tabLits">

<style>
/* ── Résumé par service ── */
.lits-overview { display:flex; flex-wrap:wrap; gap:14px; margin-bottom:32px; }
.lit-service-card {
    background:#fff; border-radius:14px; padding:16px 20px;
    box-shadow:0 2px 10px rgba(0,0,0,.07); min-width:160px;
    display:flex; flex-direction:column; align-items:center; text-align:center;
    cursor:pointer; transition:box-shadow .18s, transform .18s;
    border:2px solid transparent;
}
.lit-service-card:hover { box-shadow:0 6px 20px rgba(37,99,235,.13); transform:translateY(-2px); }
.lit-service-card.active-srv { border-color:#2563eb; }
.lit-donut-wrap { position:relative; width:80px; height:80px; margin-bottom:10px; }
.lit-donut-text { position:absolute; inset:0; display:flex; flex-direction:column;
    align-items:center; justify-content:center; }
.lit-donut-text .num { font-size:1rem; font-weight:800; line-height:1; }
.lit-donut-text .lbl { font-size:.58rem; color:#94a3b8; font-weight:600; text-transform:uppercase; }

/* ── Carte de chambre ── */
.chambre-card {
    background:#fff; border-radius:14px; box-shadow:0 2px 10px rgba(0,0,0,.06);
    overflow:hidden; margin-bottom:20px;
}
.chambre-header {
    padding:10px 16px; display:flex; align-items:center; gap:10px;
    background:#f8fafc; border-bottom:1px solid #e2e8f0;
}
.chambre-header .ch-name { font-weight:700; font-size:.88rem; }
.chambre-header .ch-type { font-size:.7rem; color:#64748b; }
.chambre-beds { display:flex; flex-wrap:wrap; gap:10px; padding:14px 16px; }

/* ── Tuile de lit ── */
.bed-tile {
    width:110px; border-radius:10px; padding:10px 10px 8px;
    display:flex; flex-direction:column; align-items:center; gap:4px;
    transition:transform .15s;
}
.bed-tile:hover { transform:scale(1.04); }
.bed-tile.libre  { background:#f0fdf4; border:1.5px solid #86efac; }
.bed-tile.occupe { background:#fef2f2; border:1.5px solid #fca5a5; }
.bed-tile.maintenance { background:#fefce8; border:1.5px solid #fde68a; }
.bed-icon { font-size:1.6rem; line-height:1; }
.bed-num  { font-size:.72rem; font-weight:700; color:#374151; }
.bed-status-dot {
    width:8px; height:8px; border-radius:50%; margin-top:2px;
}
.bed-tile.libre  .bed-status-dot { background:#22c55e; }
.bed-tile.occupe .bed-status-dot { background:#ef4444; }
.bed-tile.maintenance .bed-status-dot { background:#eab308; }
.bed-patient { font-size:.6rem; color:#64748b; text-align:center;
    max-width:100px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }

/* ── Filtre service ── */
.srv-section { margin-bottom:36px; }
.srv-section-title {
    display:flex; align-items:center; gap:10px; margin-bottom:14px;
    padding-bottom:8px; border-bottom:2px solid #e2e8f0;
}
.srv-section-title h6 { margin:0; font-weight:800; font-size:.9rem; }
</style>

    <!-- KPI par service (cliquables pour filtrer) -->
    <div class="lits-overview">
        <div class="lit-service-card active-srv" onclick="filtrerService('__all__', this)">
            <div class="lit-donut-wrap">
                <?php
                $totAll = array_sum(array_column($lits_global,'total'));
                $libAll = array_sum(array_column($lits_global,'libres'));
                $occAll = $totAll - $libAll;
                $pAll   = $totAll > 0 ? round($occAll/$totAll*100) : 0;
                $cAll   = $pAll>=90?'#ef4444':($pAll>=70?'#f59e0b':'#22c55e');
                $R=30; $C=round(2*M_PI*$R,2); $D=round($pAll/100*$C,1);
                ?>
                <svg width="80" height="80" viewBox="0 0 80 80">
                    <circle cx="40" cy="40" r="<?=$R?>" fill="none" stroke="#e2e8f0" stroke-width="10"/>
                    <circle cx="40" cy="40" r="<?=$R?>" fill="none" stroke="<?=$cAll?>" stroke-width="10"
                            stroke-dasharray="<?=$D?> <?=$C?>" stroke-linecap="round"
                            transform="rotate(-90 40 40)"/>
                </svg>
                <div class="lit-donut-text">
                    <div class="num" style="color:<?=$cAll?>"><?=$pAll?>%</div>
                    <div class="lbl">occupé</div>
                </div>
            </div>
            <div class="fw-bold small mb-1">Tout l'hôpital</div>
            <div class="text-muted" style="font-size:.72rem"><?=$libAll?> libres / <?=$totAll?> total</div>
        </div>

        <?php foreach($lits_global as $srv):
            $total  = max(1,(int)$srv['total']);
            $libres = (int)$srv['libres'];
            $occupes= $total - $libres;
            $pctOcc = round($occupes/$total*100);
            $color  = $pctOcc>=90?'#ef4444':($pctOcc>=70?'#f59e0b':'#22c55e');
            $r=30; $circ=round(2*M_PI*$r,2); $dash=round($pctOcc/100*$circ,1);
            $srvSlug = 'srv-'.preg_replace('/[^a-z0-9]/','-',strtolower($srv['nom_service']));
        ?>
        <div class="lit-service-card" onclick="filtrerService('<?=$srvSlug?>', this)" data-srv="<?=$srvSlug?>">
            <div class="lit-donut-wrap">
                <svg width="80" height="80" viewBox="0 0 80 80">
                    <circle cx="40" cy="40" r="<?=$r?>" fill="none" stroke="#e2e8f0" stroke-width="10"/>
                    <circle cx="40" cy="40" r="<?=$r?>" fill="none" stroke="<?=$color?>" stroke-width="10"
                            stroke-dasharray="<?=$dash?> <?=$circ?>" stroke-linecap="round"
                            transform="rotate(-90 40 40)"/>
                </svg>
                <div class="lit-donut-text">
                    <div class="num" style="color:<?=$color?>"><?=$pctOcc?>%</div>
                    <div class="lbl">occupé</div>
                </div>
            </div>
            <div class="fw-bold small mb-1"><?=htmlspecialchars($srv['nom_service'])?></div>
            <div class="text-muted" style="font-size:.72rem"><?=$libres?> libres / <?=$total?> total</div>
            <?php if($libres==0): ?>
                <span class="badge bg-danger mt-1" style="font-size:.6rem">COMPLET</span>
            <?php elseif($libres<=2): ?>
                <span class="badge bg-warning text-dark mt-1" style="font-size:.6rem">QUASI PLEIN</span>
            <?php else: ?>
                <span class="badge bg-success mt-1" style="font-size:.6rem"><?=$libres?> DISPO</span>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Légende -->
    <div class="d-flex gap-4 mb-4" style="font-size:.78rem;">
        <span><span style="display:inline-block;width:12px;height:12px;background:#22c55e;border-radius:3px;margin-right:5px;"></span>Disponible</span>
        <span><span style="display:inline-block;width:12px;height:12px;background:#ef4444;border-radius:3px;margin-right:5px;"></span>Occupé</span>
        <span><span style="display:inline-block;width:12px;height:12px;background:#eab308;border-radius:3px;margin-right:5px;"></span>Maintenance</span>
    </div>

    <!-- Carte dynamique : services → chambres → lits -->
    <?php foreach($lits_carte as $nomService => $chambres):
        $srvSlug = 'srv-'.preg_replace('/[^a-z0-9]/','-',strtolower($nomService));
    ?>
    <div class="srv-section" data-section="<?=$srvSlug?>">
        <div class="srv-section-title">
            <i class="bi bi-building-fill text-primary fs-5"></i>
            <h6><?=htmlspecialchars($nomService)?></h6>
            <?php
            $nbLits  = array_sum(array_map('count', $chambres));
            $nbLibres= 0;
            foreach($chambres as $chLits) foreach($chLits as $lt) if($lt['statut']!=='OCCUPE') $nbLibres++;
            ?>
            <span class="badge bg-primary ms-1"><?=count($chambres)?> ch.</span>
            <span class="badge bg-success ms-1"><?=$nbLibres?> libres</span>
            <span class="badge bg-secondary ms-1"><?=$nbLits?> lits</span>
        </div>

        <div class="row g-3">
        <?php foreach($chambres as $nomChambre => $lits):
            $nbOcc = count(array_filter($lits, fn($l)=>$l['statut']==='OCCUPE'));
            $nbTot = count($lits);
            $firstType = $lits[0]['type_chambre'] ?? '';
        ?>
        <div class="col-12 col-sm-6 col-lg-4 col-xl-3">
            <div class="chambre-card">
                <div class="chambre-header">
                    <i class="bi bi-door-closed-fill text-secondary"></i>
                    <div>
                        <div class="ch-name"><?=htmlspecialchars($nomChambre)?></div>
                        <?php if($firstType): ?>
                        <div class="ch-type"><?=htmlspecialchars($firstType)?></div>
                        <?php endif; ?>
                    </div>
                    <div class="ms-auto text-end">
                        <div style="font-size:.7rem;font-weight:700;color:<?=$nbOcc==$nbTot?'#ef4444':'#22c55e'?>">
                            <?=$nbTot-$nbOcc?>/<?=$nbTot?> libres
                        </div>
                        <!-- mini barre -->
                        <div style="width:50px;height:4px;background:#e2e8f0;border-radius:2px;margin-top:3px;">
                            <div style="width:<?=round($nbOcc/$nbTot*100)?>%;height:100%;background:<?=$nbOcc==$nbTot?'#ef4444':'#3b82f6'?>;border-radius:2px;"></div>
                        </div>
                    </div>
                </div>
                <div class="chambre-beds">
                <?php foreach($lits as $lt):
                    $isOcc  = $lt['statut'] === 'OCCUPE';
                    $isMaint= !$isOcc && $lt['statut'] !== 'DISPONIBLE' && $lt['statut'] !== 'LIBRE';
                    $cls    = $isOcc ? 'occupe' : ($isMaint ? 'maintenance' : 'libre');
                    $icon   = $isOcc ? '🛏️' : '🛌';
                    $patNom = $isOcc && !empty($lt['patient_nom'])
                        ? htmlspecialchars(strtoupper(substr($lt['patient_nom'],0,1)).'. '.($lt['patient_prenom']??''))
                        : '';
                    $title  = $isOcc ? ($lt['patient_nom'].' '.($lt['patient_prenom']??'').' — '.($lt['dossier_numero']??'')) : 'Disponible';
                ?>
                <div class="bed-tile <?=$cls?>" title="<?=htmlspecialchars($title)?>">
                    <div class="bed-icon"><?=$icon?></div>
                    <div class="bed-num"><?=htmlspecialchars($lt['nom_lit'])?></div>
                    <div class="bed-status-dot"></div>
                    <?php if($patNom): ?>
                    <div class="bed-patient"><?=$patNom?></div>
                    <?php else: ?>
                    <div class="bed-patient" style="color:#86efac;font-size:.6rem">Libre</div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
        </div>
    </div>
    <?php endforeach; ?>

</div>

<script>
function filtrerService(slug, el) {
    // Activer carte KPI
    document.querySelectorAll('.lit-service-card').forEach(c => c.classList.remove('active-srv'));
    el.classList.add('active-srv');
    // Afficher/masquer sections
    document.querySelectorAll('.srv-section').forEach(s => {
        s.style.display = (slug === '__all__' || s.dataset.section === slug) ? '' : 'none';
    });
}
</script>

</div><!-- /tab-content -->

<!-- ══ MODAL ADMISSION RAPIDE ══ -->
<?php include __DIR__ . '/modal_admission.php'; ?>

<!-- ══ MODAL ASSIGNER LIT ══ -->
<div class="modal fade modal-assign" id="modalAssignBed" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius:14px;overflow:hidden">
            <div class="modal-header py-3">
                <h5 class="modal-title fw-bold text-white"><i class="bi bi-bed-fill me-2"></i>Installer le patient</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="alert alert-warning py-2 px-3 mb-3 fw-bold rounded-3" id="assignPatientName" style="font-size:.85rem"></div>
                <input type="hidden" id="assignPatientId">

                <div class="mb-3">
                    <label class="form-label fw-bold small text-uppercase text-muted">Motif d'hospitalisation</label>
                    <input type="text" class="form-control rounded-3" id="assignMotif" placeholder="Ex: Surveillance post-opératoire...">
                </div>

                <label class="form-label fw-bold small text-uppercase text-muted mb-1">Choisir un lit</label>
                <input type="text" class="form-control form-control-sm rounded-3 mb-2" id="litSearch" placeholder="🔍 Filtrer par service, chambre...">
                <div id="litsList" style="max-height:300px;overflow-y:auto">
                    <?php foreach($lits_disponibles as $l): ?>
                    <div class="lit-select-item" data-lit-id="<?= $l['id'] ?>" data-search="<?= strtolower($l['nom_service'].' '.$l['nom_chambre'].' '.$l['nom_lit']) ?>">
                        <input type="radio" name="lit_radio" value="<?= $l['id'] ?>" id="lit<?= $l['id'] ?>">
                        <label for="lit<?= $l['id'] ?>" class="flex-grow-1 mb-0" style="cursor:pointer">
                            <strong class="small"><?= htmlspecialchars($l['nom_service']) ?></strong>
                            <span class="text-muted small"> &mdash; <?= htmlspecialchars($l['nom_chambre']) ?> &mdash; <?= htmlspecialchars($l['nom_lit']) ?></span>
                        </label>
                        <span class="badge bg-success-subtle text-success border" style="font-size:.62rem">LIBRE</span>
                    </div>
                    <?php endforeach; ?>
                    <?php if(empty($lits_disponibles)): ?>
                    <div class="text-center text-danger py-5"><i class="bi bi-exclamation-circle fs-3 d-block mb-2"></i>Aucun lit disponible actuellement</div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary rounded-pill" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-success rounded-pill px-4 shadow" id="btnValiderInstall" onclick="validerInstallation()">
                    <i class="bi bi-check-circle-fill me-1"></i> Valider l'installation
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ══ MODAL ORIENTATION URGENCE ══ -->
<div class="modal fade" id="modalOrienter" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header bg-dark text-white" style="border-radius:14px 14px 0 0">
                <h5 class="modal-title fw-bold"><i class="bi bi-signpost-split me-2"></i>Orienter le patient</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="<?= BASE_URL ?>urgences/transferer" method="POST">
                <input type="hidden" name="admission_id" id="orientAdmissionId">
                <input type="hidden" name="patient_id" id="orientPatientId">
                <div class="modal-body p-4">
                    <label class="fw-bold small text-muted text-uppercase mb-2 d-block">Décision médicale</label>
                    <div class="d-flex gap-3 mb-3">
                        <label class="flex-grow-1 p-3 border rounded-3 text-center orient-choice" style="cursor:pointer">
                            <input type="radio" name="decision" value="HOSPITALISATION" class="d-none">
                            <i class="bi bi-house-heart-fill d-block fs-3 text-primary mb-1"></i>
                            <span class="small fw-bold">Hospitaliser</span>
                        </label>
                        <label class="flex-grow-1 p-3 border rounded-3 text-center orient-choice" style="cursor:pointer">
                            <input type="radio" name="decision" value="SORTIE" class="d-none">
                            <i class="bi bi-door-open-fill d-block fs-3 text-success mb-1"></i>
                            <span class="small fw-bold">Sortie</span>
                        </label>
                    </div>
                    <div id="serviceDestBlock" style="display:none">
                        <label class="fw-bold small text-muted text-uppercase mb-1">Service de destination</label>
                        <select name="service_id" class="form-select rounded-3">
                            <option value="">-- Sélectionner --</option>
                            <option value="1">Médecine Générale</option>
                            <option value="2">Chirurgie</option>
                            <option value="4">Maternité</option>
                            <option value="5">Pédiatrie</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary rounded-pill" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-dark rounded-pill px-4"><i class="bi bi-check2 me-1"></i>Confirmer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="<?= BASE_URL ?>public/js/chart.umd.min.js"></script>
<script>
// ── Horloge ──
(function() {
    const el = document.getElementById('cockpit-clock');
    function tick() { if(el) el.textContent = new Date().toLocaleTimeString('fr-FR'); }
    tick(); setInterval(tick, 1000);
})();

// ── Sparklines ──
document.addEventListener('DOMContentLoaded', function() {
    <?php foreach($admissions as $adm): ?>
    renderSparkline('hr-<?= $adm['id'] ?>', <?= json_encode(array_values(array_filter(array_column($adm['vitals_history']??[], 'pouls')))) ?>, '#3b82f6');
    renderSparkline('bp-<?= $adm['id'] ?>', <?= json_encode(array_values(array_filter(array_column($adm['vitals_history']??[], 'tension_sys')))) ?>, '#ef4444');
    <?php endforeach; ?>
});

function renderSparkline(id, data, color) {
    const ctx = document.getElementById(id);
    if(!ctx || !data || !data.length) return;
    new Chart(ctx, {
        type: 'line',
        data: { labels: data.map((_,i)=>i), datasets: [{ data, borderColor: color, borderWidth: 2, pointRadius: 0, fill: true, backgroundColor: color+'15', tension: 0.4 }] },
        options: { responsive:true, maintainAspectRatio:false, plugins:{legend:{display:false}}, scales:{x:{display:false},y:{display:false}} }
    });
}

// ── Alerte onglet P1 ──
<?php if($stats['P1']>0): ?>
document.querySelector('[data-bs-target="#tabUrgences"]').style.color='#ef4444';
<?php endif; ?>

// ── Modale orientation ──
function openOrientation(admId, patId) {
    document.getElementById('orientAdmissionId').value = admId;
    document.getElementById('orientPatientId').value = patId;
    document.querySelectorAll('.orient-choice').forEach(l => l.classList.remove('border-primary','bg-primary-subtle'));
    document.getElementById('serviceDestBlock').style.display = 'none';
    new bootstrap.Modal(document.getElementById('modalOrienter')).show();
}
document.querySelectorAll('.orient-choice input').forEach(r => {
    r.closest('label').addEventListener('click', function() {
        document.querySelectorAll('.orient-choice').forEach(l => l.classList.remove('border-primary','bg-primary-subtle'));
        this.classList.add('border-primary','bg-primary-subtle');
        document.getElementById('serviceDestBlock').style.display = r.value==='HOSPITALISATION' ? 'block' : 'none';
    });
});

// ── Modale assignation lit ──
function openAssignBed(patientId, patientName) {
    document.getElementById('assignPatientId').value = patientId;
    document.getElementById('assignPatientName').textContent = '👤 ' + patientName;
    document.getElementById('assignMotif').value = '';
    document.querySelectorAll('[name="lit_radio"]').forEach(r => r.checked = false);
    document.querySelectorAll('.lit-select-item').forEach(i => i.classList.remove('selected'));
    document.getElementById('litSearch').value = '';
    filterLits('');
    new bootstrap.Modal(document.getElementById('modalAssignBed')).show();
}
document.getElementById('litSearch').addEventListener('input', function() { filterLits(this.value.toLowerCase()); });
function filterLits(q) {
    document.querySelectorAll('.lit-select-item').forEach(item => {
        item.style.display = (!q || item.dataset.search.includes(q)) ? '' : 'none';
    });
}
document.querySelectorAll('.lit-select-item').forEach(item => {
    item.addEventListener('click', function() {
        document.querySelectorAll('.lit-select-item').forEach(i => i.classList.remove('selected'));
        this.classList.add('selected');
        this.querySelector('input[type="radio"]').checked = true;
    });
});

function validerInstallation() {
    const patientId = document.getElementById('assignPatientId').value;
    const motif = document.getElementById('assignMotif').value;
    const litRadio = document.querySelector('[name="lit_radio"]:checked');
    if(!litRadio) { alert('Veuillez sélectionner un lit disponible.'); return; }

    const btn = document.getElementById('btnValiderInstall');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Traitement...';

    const fd = new FormData();
    fd.append('patient_id', patientId);
    fd.append('lit_id', litRadio.value);
    fd.append('motif', motif || 'Hospitalisation validée par infirmier urgences');

    fetch('<?= BASE_URL ?>urgences/valider-hospitalisation', { method:'POST', body:fd })
        .then(r => r.json())
        .then(data => {
            if(data.success) {
                bootstrap.Modal.getInstance(document.getElementById('modalAssignBed')).hide();
                showToast('✅ Patient installé avec succès !', 'success');
                setTimeout(() => location.reload(), 1800);
            } else {
                showToast('❌ ' + (data.message || 'Erreur'), 'danger');
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-check-circle-fill me-1"></i> Valider l\'installation';
            }
        });
}

function showToast(msg, type) {
    const t = document.createElement('div');
    t.className = `position-fixed bottom-0 end-0 m-4 alert alert-${type} shadow-lg rounded-3 px-4 py-3`;
    t.style.cssText = 'z-index:9999;min-width:260px;animation:slideIn .3s ease';
    t.innerHTML = msg;
    document.body.appendChild(t);
    setTimeout(() => t.remove(), 3500);
}

// ── Libérer le lit ──
function confirmerLiberationLit(hospId, patientNom, litLabel) {
    document.getElementById('liberHospId').value = hospId;
    document.getElementById('liberPatientNom').textContent = patientNom;
    document.getElementById('liberLitLabel').textContent = litLabel;
    new bootstrap.Modal(document.getElementById('modalLiberLit')).show();
}

function libererLit() {
    const hospId = document.getElementById('liberHospId').value;
    const btn = document.getElementById('btnConfirmerLiberer');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Libération...';

    const fd = new FormData();
    fd.append('hosp_id', hospId);

    fetch('<?= BASE_URL ?>urgences/liberer-lit', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            bootstrap.Modal.getInstance(document.getElementById('modalLiberLit')).hide();
            if (data.success) {
                showToast('🛏️ Lit libéré avec succès !', 'success');
                setTimeout(() => location.reload(), 1600);
            } else {
                showToast('❌ ' + (data.message || 'Erreur'), 'danger');
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-door-open-fill me-1"></i>Confirmer la libération';
            }
        });
}

// Auto-refresh 90s
setTimeout(() => location.reload(), 90000);
</script>

<!-- ══ MODAL LIBÉRER LE LIT ══ -->
<div class="modal fade" id="modalLiberLit" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width:440px">
        <div class="modal-content border-0 shadow-lg" style="border-radius:18px;overflow:hidden">
            <div class="modal-header border-0 pb-0 pt-4 px-4">
                <div class="d-flex align-items-center gap-3 w-100">
                    <div style="width:50px;height:50px;background:#fff1f2;border-radius:14px;display:flex;align-items:center;justify-content:center;flex-shrink:0">
                        <i class="bi bi-door-open-fill text-danger fs-4"></i>
                    </div>
                    <div>
                        <h5 class="fw-bold mb-0 text-dark">Libérer le lit</h5>
                        <small class="text-muted">Cette action est irréversible</small>
                    </div>
                    <button type="button" class="btn-close ms-auto" data-bs-dismiss="modal"></button>
                </div>
            </div>
            <div class="modal-body px-4 pt-3 pb-2">
                <input type="hidden" id="liberHospId">
                <div class="alert alert-warning rounded-3 py-2 px-3 mb-3" style="font-size:.85rem">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    Vous allez libérer le lit de <strong id="liberPatientNom"></strong>
                </div>
                <div class="d-flex align-items-center gap-2 p-3 bg-light rounded-3" style="font-size:.85rem">
                    <i class="bi bi-bed-fill text-secondary fs-5"></i>
                    <div>
                        <div class="text-muted" style="font-size:.7rem;text-transform:uppercase;font-weight:700">Lit concerné</div>
                        <strong id="liberLitLabel" class="text-dark"></strong>
                    </div>
                </div>
                <p class="text-muted mt-3 mb-0" style="font-size:.82rem">
                    <i class="bi bi-info-circle me-1"></i>
                    L'hospitalisation sera clôturée, le patient passera en statut "Sorti" et le lit sera immédiatement remis à disposition.
                </p>
            </div>
            <div class="modal-footer border-0 px-4 pb-4 pt-2 d-flex gap-2">
                <button type="button" class="btn btn-outline-secondary rounded-pill flex-grow-1" data-bs-dismiss="modal">Annuler</button>
                <button type="button" id="btnConfirmerLiberer" class="btn btn-danger rounded-pill flex-grow-1 fw-bold shadow-sm" onclick="libererLit()">
                    <i class="bi bi-door-open-fill me-1"></i>Confirmer la libération
                </button>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
