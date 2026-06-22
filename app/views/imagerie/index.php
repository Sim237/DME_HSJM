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
    /* ── COCKPIT SANS SIDEBAR ── */
    .sidebar { display: none !important; }
    main { margin-left: 0 !important; width: 100% !important; background: #f4f7f9; min-height: 100vh; font-family: 'Plus Jakarta Sans', sans-serif; color: #334155; }

    /* ── HEADER INSTITUTIONNEL ── */
    .cockpit-header {
        background: #1a4a8e; color: white; padding: 12px 30px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.1); position: sticky; top: 0; z-index: 1000;
    }
    #clock { font-family: monospace; font-size: 1.8rem; font-weight: bold; color: #00ff41; text-shadow: 0 0 10px rgba(0,255,65,.3); }

    /* ── STATS PILLS ── */
    .status-bar { display: flex; gap: 20px; padding: 20px 30px 10px; }
    .stat-pill {
        background: white; border-radius: 16px; padding: 18px 20px; flex: 1;
        display: flex; align-items: center; gap: 15px;
        box-shadow: 0 4px 6px rgba(0,0,0,.05); border-bottom: 5px solid #ddd; transition: transform .2s;
    }
    .stat-pill:hover { transform: translateY(-3px); }
    .stat-pill.waiting  { border-bottom-color: #0d6efd; }
    .stat-pill.urgent   { border-bottom-color: #dc3545; }
    .stat-pill.done     { border-bottom-color: #198754; }
    .stat-icon  { width: 50px; height: 50px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.8rem; }
    .stat-number { font-size: 2.2rem; font-weight: 800; color: #1e293b; line-height: 1; }
    .stat-label  { font-size: .8rem; font-weight: 700; color: #64748b; text-transform: uppercase; }

    /* ── BARRE FILTRE ── */
    .filter-section { background: white; margin: 12px 30px; padding: 14px 18px; border-radius: 12px; border: 1px solid #e2e8f0; }

    /* ── ONGLETS COCKPIT ── */
    .cockpit-tabs-wrap { padding: 0 30px; margin-bottom: 0; }
    .cockpit-tabs .nav-link {
        font-weight: 700; font-size: .87rem; color: #64748b; border: none;
        border-bottom: 3px solid transparent; padding: 10px 20px; background: none;
        transition: .2s; display: flex; align-items: center; gap: 7px;
    }
    .cockpit-tabs .nav-link:hover  { color: #1a4a8e; }
    .cockpit-tabs .nav-link.active { color: #1a4a8e; border-bottom-color: #1a4a8e; background: none; }
    .cockpit-tabs .nav-link .tab-badge {
        background: #e2e8f0; color: #475569; border-radius: 20px;
        padding: 1px 8px; font-size: .72rem; font-weight: 800;
    }
    .cockpit-tabs .nav-link.active .tab-badge { background: #1a4a8e; color: #fff; }
    .cockpit-tabs .nav-link.tab-urgent .tab-badge { background: #fee2e2; color: #b91c1c; }
    .cockpit-tabs .nav-link.tab-urgent.active .tab-badge { background: #dc2626; color: #fff; }
    .cockpit-tabs .nav-link.tab-done .tab-badge { background: #d1fae5; color: #065f46; }
    .cockpit-tabs .nav-link.tab-done.active .tab-badge { background: #059669; color: #fff; }
    .tab-divider { border-bottom: 2px solid #e2e8f0; margin: 0 30px; }

    /* ── GRILLES D'EXAMENS ── */
    .exam-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 18px; padding: 20px 30px 60px; }

    /* ── CARTE PATIENT GROUPÉE (radio / echo) ── */
    .patient-exam-card {
        background: white; border-radius: 18px; border: 1px solid #e2e8f0;
        overflow: hidden; transition: .3s; position: relative;
        display: flex; flex-direction: column;
        box-shadow: 0 2px 8px rgba(0,0,0,.04);
    }
    .patient-exam-card:hover { box-shadow: 0 12px 28px rgba(0,0,0,.10); transform: translateY(-4px); }
    .patient-exam-card.is-urgent { border-color: #fca5a5; border-left: 4px solid #dc2626; }

    .pec-header {
        padding: 14px 18px 10px;
        background: linear-gradient(135deg, #eff6ff 0%, #f8fafc 100%);
        border-bottom: 1px solid #e2e8f0;
    }
    .pec-header.echo-header {
        background: linear-gradient(135deg, #f0fdf4 0%, #f8fafc 100%);
    }
    .pec-modality { font-size: .7rem; font-weight: 800; text-transform: uppercase;
                    letter-spacing: .6px; color: #1a4a8e; margin-bottom: 3px; }
    .pec-modality.echo { color: #059669; }
    .pec-patient-name { font-weight: 800; font-size: 1.05rem; color: #1e293b; line-height: 1.2; }
    .pec-dossier { font-size: .72rem; color: #94a3b8; font-family: monospace; margin-top: 2px; }

    .pec-body { padding: 14px 18px; flex: 1; }
    .pec-exams-label { font-size: .7rem; font-weight: 700; color: #64748b;
                       text-transform: uppercase; letter-spacing: .5px; margin-bottom: 8px; }
    .exam-pill {
        display: inline-flex; align-items: center; gap: 5px;
        background: #eff6ff; color: #1e40af; border-radius: 8px;
        padding: 4px 10px; font-size: .75rem; font-weight: 700; margin: 2px 3px 2px 0;
    }
    .exam-pill.echo { background: #ecfdf5; color: #065f46; }
    .exam-pill-num { background: #1e40af; color: #fff; border-radius: 50%;
                     width: 16px; height: 16px; font-size: .6rem; display: inline-flex;
                     align-items: center; justify-content: center; font-weight: 800; }
    .exam-pill-num.echo { background: #059669; }

    .pec-meta { font-size: .75rem; color: #64748b; margin-top: 10px; line-height: 1.6; }

    .pec-footer { padding: 12px 18px; border-top: 1px solid #f1f5f9; }

    /* Badge urgence flottant */
    .badge-urgent-float {
        position: absolute; top: 12px; right: 12px;
        background: #dc2626; color: #fff;
        border-radius: 8px; padding: 3px 9px; font-size: .7rem; font-weight: 800;
        letter-spacing: .5px; z-index: 5;
        animation: pulse-urgence 1.5s infinite;
    }
    @keyframes pulse-urgence {
        0%,100% { box-shadow: 0 0 0 0 rgba(220,38,38,.4); }
        50%     { box-shadow: 0 0 0 6px rgba(220,38,38,0); }
    }

    /* ── CARTE TERMINÉE (ancienne design adaptée) ── */
    .done-card {
        background: white; border-radius: 18px; border: 1px solid #d1fae5;
        overflow: hidden; transition: .3s; position: relative;
        display: flex; flex-direction: column;
        box-shadow: 0 2px 8px rgba(0,0,0,.04);
    }
    .done-card:hover { box-shadow: 0 12px 28px rgba(0,0,0,.10); transform: translateY(-4px); }
    .done-card-preview {
        height: 120px; background: #1e3a5f; display: flex; align-items: center;
        justify-content: center; color: rgba(255,255,255,.3); font-size: 3rem; overflow: hidden;
    }
    .done-card-preview img { width: 100%; height: 100%; object-fit: cover; }
    .done-card-body { padding: 14px 16px; flex: 1; }
    .done-card-type { font-size: .72rem; font-weight: 800; color: #059669; text-transform: uppercase; letter-spacing: .4px; }

    /* ── ÉTAT VIDE ── */
    .empty-state { text-align: center; padding: 80px 0; color: #94a3b8; }

    /* ── BTN PRINCIPALE ── */
    .btn-realiser { background: #1a4a8e; color: white; border-radius: 10px; font-weight: 700;
                    border: none; padding: 10px; width: 100%; transition: .3s; font-size: .85rem; }
    .btn-realiser:hover { background: #0f2e5e; color: white; }
    .btn-realiser-echo { background: #059669; }
    .btn-realiser-echo:hover { background: #047857; }
</style>

<main>
    <!-- HEADER COCKPIT -->
    <div class="cockpit-header d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center gap-3">
            <div class="bg-white p-2 rounded shadow-sm">
                <img src="<?= BASE_URL ?>public/images/logo_ordre_malte.png" style="height:45px;" onerror="this.style.display='none'">
            </div>
            <div>
                <h4 class="mb-0 fw-bold">COCKPIT <span style="color:#ffc107">IMAGERIE</span></h4>
                <small class="text-white-50 fw-bold">Unité de Radiologie & Diagnostic • HSJM</small>
            </div>
        </div>
        <div id="clock">00:00:00</div>
        <div class="d-flex gap-3">
            <button class="btn btn-danger fw-bold rounded-pill px-4 shadow"
                    data-bs-toggle="modal" data-bs-target="#uploadModal">
                <i class="bi bi-upload me-2"></i>COMPLÉTER EXAMEN
            </button>
            <a href="<?= BASE_URL ?>logout" class="btn btn-light rounded-circle shadow-sm">
                <i class="bi bi-power text-danger fs-5"></i>
            </a>
        </div>
    </div>

    <!-- STATISTIQUES KPI -->
    <div class="status-bar">
        <div class="stat-pill waiting">
            <div class="stat-icon" style="background:#eef4ff"><i class="bi bi-hourglass-split text-primary"></i></div>
            <div>
                <span class="stat-number"><?= $stats['en_attente'] ?? 0 ?></span>
                <div class="stat-label">En attente</div>
            </div>
        </div>
        <div class="stat-pill urgent">
            <div class="stat-icon" style="background:#fff5f5"><i class="bi bi-exclamation-triangle-fill text-danger"></i></div>
            <div>
                <span class="stat-number text-danger"><?= $stats['urgents'] ?? 0 ?></span>
                <div class="stat-label">Urgents</div>
            </div>
        </div>
        <div class="stat-pill done">
            <div class="stat-icon" style="background:#f0fff4"><i class="bi bi-check-circle-fill text-success"></i></div>
            <div>
                <span class="stat-number text-success"><?= $stats['termines_jour'] ?? 0 ?></span>
                <div class="stat-label">Terminés (Jour)</div>
            </div>
        </div>
    </div>

    <!-- BARRE FILTRE -->
    <div class="filter-section d-flex gap-3 align-items-center shadow-sm">
        <div class="input-group" style="width:350px">
            <span class="input-group-text bg-light border-0"><i class="bi bi-search"></i></span>
            <input type="text" id="patientSearch" class="form-control border-0 bg-light"
                   placeholder="Rechercher un patient ou N° dossier…" oninput="filterCards()">
        </div>
        <span class="text-muted small ms-auto fst-italic">
            <i class="bi bi-record-fill text-danger"></i> Monitoring en direct
        </span>
    </div>

    <!-- ONGLETS COCKPIT -->
    <div class="cockpit-tabs-wrap mt-3">
        <ul class="nav cockpit-tabs border-0" id="cockpitTabs" role="tablist">
            <li class="nav-item">
                <a class="nav-link active" id="tab-radio-link" data-bs-toggle="tab"
                   href="#tabRadio" role="tab">
                    <i class="bi bi-radioactive"></i> Radiographies
                    <span class="tab-badge"><?= count($radio_groupes) ?></span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="tab-echo-link" data-bs-toggle="tab"
                   href="#tabEcho" role="tab">
                    <i class="bi bi-soundwave"></i> Échographies
                    <span class="tab-badge"><?= count($echo_groupes) ?></span>
                </a>
            </li>
            <?php if (!empty($autres_attente)): ?>
            <li class="nav-item">
                <a class="nav-link" id="tab-autres-link" data-bs-toggle="tab"
                   href="#tabAutres" role="tab">
                    <i class="bi bi-camera-reels"></i> Autres
                    <span class="tab-badge"><?= count($autres_attente) ?></span>
                </a>
            </li>
            <?php endif; ?>
            <li class="nav-item ms-auto">
                <a class="nav-link tab-done" id="tab-done-link" data-bs-toggle="tab"
                   href="#tabTerminees" role="tab">
                    <i class="bi bi-check2-circle"></i> Terminées
                    <span class="tab-badge"><?= count($examens_termines) ?></span>
                </a>
            </li>
        </ul>
    </div>
    <div class="tab-divider"></div>

    <!-- CONTENU DES ONGLETS -->
    <div class="tab-content" id="cockpitTabContent">

        <!-- ═══ ONGLET RADIOGRAPHIES ═══ -->
        <div class="tab-pane fade show active" id="tabRadio" role="tabpanel">
            <div class="exam-grid" id="gridRadio">
                <?php if (empty($radio_groupes)): ?>
                    <div class="col-12 empty-state" style="grid-column:1/-1">
                        <i class="bi bi-radioactive display-1 opacity-25"></i>
                        <h4 class="mt-3">Aucune radiographie en attente</h4>
                        <p>Les prescriptions de radiographie apparaîtront ici.</p>
                    </div>
                <?php else:
                    foreach ($radio_groupes as $pid => $groupe):
                        $pat      = $groupe['patient'];
                        $examens  = $groupe['examens'];
                        $isUrgent = array_reduce($examens, fn($carry, $e) => $carry || ($e['urgence'] === 'URGENT' || $e['urgence'] == 1), false);
                        $allIds   = implode(',', array_column($examens, 'id'));
                        $primaryId = $examens[0]['id'];
                        $datePrescr = $examens[0]['date_creation'] ?? null;
                ?>
                <div class="patient-exam-card <?= $isUrgent ? 'is-urgent' : '' ?>"
                     data-name="<?= strtolower(htmlspecialchars($pat['nom'] . ' ' . $pat['prenom'] . ' ' . $pat['dossier_numero'])) ?>">

                    <?php if ($isUrgent): ?>
                        <div class="badge-urgent-float">⚡ URGENT</div>
                    <?php endif; ?>

                    <div class="pec-header">
                        <div class="pec-modality">☢ Radiographie</div>
                        <div class="pec-patient-name"><?= strtoupper(htmlspecialchars($pat['nom'])) ?> <?= htmlspecialchars($pat['prenom']) ?></div>
                        <div class="pec-dossier"><?= htmlspecialchars($pat['dossier_numero'] ?? '') ?></div>
                    </div>

                    <div class="pec-body">
                        <div class="pec-exams-label">
                            <?= count($examens) ?> examen<?= count($examens) > 1 ? 's' : '' ?> à réaliser
                        </div>
                        <div>
                            <?php foreach ($examens as $idx => $ex): ?>
                                <span class="exam-pill">
                                    <span class="exam-pill-num"><?= $idx + 1 ?></span>
                                    <?= htmlspecialchars($ex['partie_corps']) ?>
                                </span>
                            <?php endforeach; ?>
                        </div>
                        <div class="pec-meta">
                            <i class="bi bi-person-badge text-primary"></i>
                            Dr. <?= htmlspecialchars($pat['medecin_nom']) ?><br>
                            <i class="bi bi-calendar-event text-primary"></i>
                            <?= $datePrescr ? date('d/m/Y à H:i', strtotime($datePrescr)) : '—' ?>
                        </div>
                    </div>

                    <div class="pec-footer d-flex gap-2">
                        <button class="btn-realiser flex-grow-1"
                                onclick="openUpload(<?= $primaryId ?>, '<?= htmlspecialchars(addslashes($allIds)) ?>', <?= count($examens) ?>)">
                            <i class="bi bi-camera me-1"></i>RÉALISER L'EXAMEN
                        </button>
                        <button class="btn btn-outline-danger btn-sm rounded-pill px-2"
                                title="Supprimer la demande"
                                onclick="confirmDelete(<?= $primaryId ?>)">
                            <i class="bi bi-trash3"></i>
                        </button>
                    </div>
                </div>
                <?php endforeach; endif; ?>
            </div>
        </div>

        <!-- ═══ ONGLET ÉCHOGRAPHIES ═══ -->
        <div class="tab-pane fade" id="tabEcho" role="tabpanel">
            <div class="exam-grid" id="gridEcho">
                <?php if (empty($echo_groupes)): ?>
                    <div class="col-12 empty-state" style="grid-column:1/-1">
                        <i class="bi bi-soundwave display-1 opacity-25"></i>
                        <h4 class="mt-3">Aucune échographie en attente</h4>
                        <p>Les prescriptions d'échographie apparaîtront ici.</p>
                    </div>
                <?php else:
                    foreach ($echo_groupes as $pid => $groupe):
                        $pat      = $groupe['patient'];
                        $examens  = $groupe['examens'];
                        $isUrgent = array_reduce($examens, fn($carry, $e) => $carry || ($e['urgence'] === 'URGENT' || $e['urgence'] == 1), false);
                        $allIds   = implode(',', array_column($examens, 'id'));
                        $primaryId = $examens[0]['id'];
                        $datePrescr = $examens[0]['date_creation'] ?? null;
                ?>
                <div class="patient-exam-card <?= $isUrgent ? 'is-urgent' : '' ?>"
                     data-name="<?= strtolower(htmlspecialchars($pat['nom'] . ' ' . $pat['prenom'] . ' ' . $pat['dossier_numero'])) ?>">

                    <?php if ($isUrgent): ?>
                        <div class="badge-urgent-float">⚡ URGENT</div>
                    <?php endif; ?>

                    <div class="pec-header echo-header">
                        <div class="pec-modality echo">🔊 Échographie</div>
                        <div class="pec-patient-name"><?= strtoupper(htmlspecialchars($pat['nom'])) ?> <?= htmlspecialchars($pat['prenom']) ?></div>
                        <div class="pec-dossier"><?= htmlspecialchars($pat['dossier_numero'] ?? '') ?></div>
                    </div>

                    <div class="pec-body">
                        <div class="pec-exams-label">
                            <?= count($examens) ?> examen<?= count($examens) > 1 ? 's' : '' ?> à réaliser
                        </div>
                        <div>
                            <?php foreach ($examens as $idx => $ex): ?>
                                <span class="exam-pill echo">
                                    <span class="exam-pill-num echo"><?= $idx + 1 ?></span>
                                    <?= htmlspecialchars($ex['partie_corps']) ?>
                                </span>
                            <?php endforeach; ?>
                        </div>
                        <div class="pec-meta">
                            <i class="bi bi-person-badge text-success"></i>
                            Dr. <?= htmlspecialchars($pat['medecin_nom']) ?><br>
                            <i class="bi bi-calendar-event text-success"></i>
                            <?= $datePrescr ? date('d/m/Y à H:i', strtotime($datePrescr)) : '—' ?>
                        </div>
                    </div>

                    <div class="pec-footer d-flex gap-2">
                        <button class="btn-realiser btn-realiser-echo flex-grow-1"
                                onclick="openUpload(<?= $primaryId ?>, '<?= htmlspecialchars(addslashes($allIds)) ?>', <?= count($examens) ?>)">
                            <i class="bi bi-soundwave me-1"></i>RÉALISER L'EXAMEN
                        </button>
                        <button class="btn btn-outline-danger btn-sm rounded-pill px-2"
                                title="Supprimer la demande"
                                onclick="confirmDelete(<?= $primaryId ?>)">
                            <i class="bi bi-trash3"></i>
                        </button>
                    </div>
                </div>
                <?php endforeach; endif; ?>
            </div>
        </div>

        <!-- ═══ ONGLET AUTRES (si non vide) ═══ -->
        <?php if (!empty($autres_attente)): ?>
        <div class="tab-pane fade" id="tabAutres" role="tabpanel">
            <div class="exam-grid" id="gridAutres">
                <?php foreach ($autres_attente as $pid => $groupe):
                    $pat       = $groupe['patient'];
                    $examens   = $groupe['examens'];
                    $isUrgent  = array_reduce($examens, fn($carry, $e) => $carry || ($e['urgence'] === 'URGENT' || $e['urgence'] == 1), false);
                    $allIds    = implode(',', array_column($examens, 'id'));
                    $primaryId = $examens[0]['id'];
                    $datePrescr = $examens[0]['date_creation'] ?? null;
                    $typeLabel  = ucfirst($examens[0]['type_examen'] ?? 'Autre');
                ?>
                <div class="patient-exam-card <?= $isUrgent ? 'is-urgent' : '' ?>"
                     data-name="<?= strtolower(htmlspecialchars($pat['nom'] . ' ' . $pat['prenom'] . ' ' . $pat['dossier_numero'])) ?>">

                    <?php if ($isUrgent): ?>
                        <div class="badge-urgent-float">⚡ URGENT</div>
                    <?php endif; ?>

                    <div class="pec-header" style="background:linear-gradient(135deg,#faf5ff 0%,#f8fafc 100%)">
                        <div class="pec-modality" style="color:#7c3aed">🔬 <?= htmlspecialchars($typeLabel) ?></div>
                        <div class="pec-patient-name"><?= strtoupper(htmlspecialchars($pat['nom'])) ?> <?= htmlspecialchars($pat['prenom']) ?></div>
                        <div class="pec-dossier"><?= htmlspecialchars($pat['dossier_numero'] ?? '') ?></div>
                    </div>

                    <div class="pec-body">
                        <div class="pec-exams-label"><?= count($examens) ?> examen<?= count($examens) > 1 ? 's' : '' ?> à réaliser</div>
                        <div>
                            <?php foreach ($examens as $idx => $ex): ?>
                                <span class="exam-pill" style="background:#f5f3ff;color:#5b21b6">
                                    <span class="exam-pill-num" style="background:#7c3aed"><?= $idx + 1 ?></span>
                                    <?= htmlspecialchars($ex['partie_corps']) ?>
                                </span>
                            <?php endforeach; ?>
                        </div>
                        <div class="pec-meta">
                            <i class="bi bi-person-badge" style="color:#7c3aed"></i>
                            Dr. <?= htmlspecialchars($pat['medecin_nom']) ?><br>
                            <i class="bi bi-calendar-event" style="color:#7c3aed"></i>
                            <?= $datePrescr ? date('d/m/Y à H:i', strtotime($datePrescr)) : '—' ?>
                        </div>
                    </div>

                    <div class="pec-footer d-flex gap-2">
                        <button class="btn-realiser flex-grow-1" style="background:#7c3aed"
                                onclick="openUpload(<?= $primaryId ?>, '<?= htmlspecialchars(addslashes($allIds)) ?>', <?= count($examens) ?>)">
                            <i class="bi bi-camera me-1"></i>RÉALISER L'EXAMEN
                        </button>
                        <button class="btn btn-outline-danger btn-sm rounded-pill px-2"
                                title="Supprimer la demande"
                                onclick="confirmDelete(<?= $primaryId ?>)">
                            <i class="bi bi-trash3"></i>
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- ═══ ONGLET TERMINÉES ═══ -->
        <div class="tab-pane fade" id="tabTerminees" role="tabpanel">
            <div class="exam-grid" id="gridTerminees">
                <?php if (empty($examens_termines)): ?>
                    <div class="col-12 empty-state" style="grid-column:1/-1">
                        <i class="bi bi-check-circle display-1 opacity-25"></i>
                        <h4 class="mt-3">Aucun examen terminé</h4>
                        <p>Les examens réalisés apparaîtront ici.</p>
                    </div>
                <?php else: foreach ($examens_termines as $ex):
                    $status = $ex['statut'];
                    $typeLabel = ucfirst($ex['type_examen'] ?? 'Examen');
                ?>
                <div class="done-card"
                     data-name="<?= strtolower(htmlspecialchars($ex['nom'] . ' ' . $ex['prenom'] . ' ' . $ex['dossier_numero'])) ?>">

                    <div class="done-card-preview">
                        <?php if (!empty($ex['fichier_preview'])): ?>
                            <img src="<?= BASE_URL ?>assets/uploads/previews/<?= htmlspecialchars($ex['fichier_preview']) ?>" alt="Aperçu">
                        <?php else: ?>
                            <i class="bi bi-camera-fill"></i>
                        <?php endif; ?>
                    </div>

                    <div class="done-card-body">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <span class="done-card-type"><?= htmlspecialchars($typeLabel) ?></span>
                            <span class="badge rounded-pill bg-<?= $status === 'interprete' ? 'success' : 'warning text-dark' ?> small">
                                <?= $status === 'interprete' ? 'Interprété' : 'À interpréter' ?>
                            </span>
                        </div>
                        <div class="fw-bold text-dark"><?= strtoupper(htmlspecialchars($ex['nom'])) ?> <?= htmlspecialchars($ex['prenom']) ?></div>
                        <div class="small text-muted mt-1">
                            <i class="bi bi-geo-alt-fill text-success"></i> <?= htmlspecialchars($ex['partie_corps']) ?><br>
                            <i class="bi bi-person-badge"></i> Dr. <?= htmlspecialchars($ex['medecin_nom']) ?><br>
                            <i class="bi bi-calendar-check me-1"></i>
                            <?= !empty($ex['date_resultats']) ? date('d/m/Y H:i', strtotime($ex['date_resultats'])) : '—' ?>
                        </div>
                    </div>

                    <div class="pec-footer d-flex gap-2">
                        <a href="<?= BASE_URL ?>imagerie/viewer/<?= $ex['id'] ?>"
                           class="btn btn-sm btn-success rounded-pill fw-bold flex-grow-1 text-center text-decoration-none">
                            <i class="bi bi-eye me-1"></i>Visualiser
                        </a>
                        <button class="btn btn-outline-warning btn-sm rounded-pill px-2"
                                title="Remplacer le fichier"
                                onclick="openReplace(<?= $ex['id'] ?>, '<?= htmlspecialchars(addslashes($ex['nom'].' '.$ex['prenom']), ENT_QUOTES) ?>')">
                            <i class="bi bi-arrow-repeat"></i>
                        </button>
                        <button class="btn btn-outline-danger btn-sm rounded-pill px-2"
                                title="Supprimer"
                                onclick="confirmDelete(<?= $ex['id'] ?>)">
                            <i class="bi bi-trash3"></i>
                        </button>
                    </div>
                </div>
                <?php endforeach; endif; ?>
            </div>
        </div>

    </div><!-- /tab-content -->
</main>

<!-- MODALE UPLOAD -->
<?php include __DIR__ . '/modal_upload.php'; ?>

<script>
// ── Horloge ──────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    setInterval(() => {
        const c = document.getElementById('clock');
        if (c) c.innerText = new Date().toLocaleTimeString('fr-FR');
    }, 1000);
});

// ── Filtrage par nom dans l'onglet actif ─────────────────────────────
function filterCards() {
    const q = document.getElementById('patientSearch').value.toLowerCase();
    const activePane = document.querySelector('.tab-pane.active');
    if (!activePane) return;
    activePane.querySelectorAll('[data-name]').forEach(card => {
        card.style.display = card.dataset.name.includes(q) ? '' : 'none';
    });
}

// Re-filtrer au changement d'onglet
document.querySelectorAll('[data-bs-toggle="tab"]').forEach(tab => {
    tab.addEventListener('shown.bs.tab', filterCards);
});

// ── Gestion du modal upload ──────────────────────────────────────────
let _currentImagerieId   = null;
let _currentGroupeIds    = '';

function openUpload(primaryId, groupeIds, nbExamens) {
    _currentImagerieId = primaryId;
    _currentGroupeIds  = groupeIds || String(primaryId);
    const nb = parseInt(nbExamens) || 1;

    document.getElementById('imagerie_selector').value    = primaryId;
    document.getElementById('imagerie_ids_groupe').value  = _currentGroupeIds;
    document.getElementById('nb_examens_requis').value    = nb;
    document.getElementById('upload_mode').value          = 'new';
    document.getElementById('uploadModalTitle').innerHTML = '<i class="bi bi-cloud-upload me-2"></i>Compléter l\'examen';
    document.getElementById('uploadModalSubtitle').textContent = '';
    document.getElementById('uploadModalHeader').style.background = 'linear-gradient(135deg,#1d4ed8,#2563eb)';
    document.getElementById('uploadReplaceAlert').classList.add('d-none');
    document.getElementById('uploadSubmitBtn').textContent = 'ENREGISTRER ET TRANSMETTRE';
    document.getElementById('dicom_file_input').required  = nb > 0;

    // Alerte min fichiers
    const alertMin = document.getElementById('minFilesAlert');
    if (alertMin) {
        document.getElementById('minFilesExamCount').textContent = nb;
        document.getElementById('minFilesReqCount').textContent  = nb;
        alertMin.classList.toggle('d-none', nb <= 1);
    }

    // Remettre le toggle "Terminé" à ON par défaut
    const chk = document.getElementById('marquer_termine_check');
    const hid = document.getElementById('marquer_termine_hidden');
    const wrp = document.getElementById('termineToggleWrap');
    const wrn = document.getElementById('termineOffWarning');
    if (chk) { chk.checked = true; hid.value = '1'; }
    if (wrp) { wrp.style.background = '#f0fdf4'; wrp.style.borderColor = '#bbf7d0'; }
    if (wrn) wrn.classList.add('d-none');

    bootstrap.Modal.getOrCreateInstance(document.getElementById('uploadModal')).show();
}

function openReplace(id, patientName) {
    if (!confirm('⚠️ Voulez-vous remplacer le fichier DICOM de ' + patientName + ' ?\nL\'ancien fichier sera supprimé définitivement.')) return;
    _currentImagerieId = id;
    _currentGroupeIds  = String(id);

    document.getElementById('imagerie_selector').value    = id;
    document.getElementById('imagerie_ids_groupe').value  = String(id);
    document.getElementById('upload_mode').value          = 'replace';
    document.getElementById('uploadModalTitle').innerHTML = '<i class="bi bi-arrow-repeat me-2"></i>Remplacer le fichier DICOM';
    document.getElementById('uploadModalSubtitle').textContent = patientName;
    document.getElementById('uploadModalHeader').style.background = 'linear-gradient(135deg,#92400e,#d97706)';
    document.getElementById('uploadReplaceAlert').classList.remove('d-none');
    document.getElementById('uploadSubmitBtn').textContent = 'REMPLACER ET TRANSMETTRE';
    document.getElementById('dicom_file_input').required  = true;
    bootstrap.Modal.getOrCreateInstance(document.getElementById('uploadModal')).show();
}

document.getElementById('uploadModal')?.addEventListener('hidden.bs.modal', function () {
    _currentImagerieId = null;
    _currentGroupeIds  = '';
    document.getElementById('imagerie_selector').value   = '';
    document.getElementById('imagerie_ids_groupe').value = '';
    this.querySelector('form')?.reset();
});

// ── Upload AJAX ──────────────────────────────────────────────────────
document.querySelector('#uploadModal form')?.addEventListener('submit', function (e) {
    e.preventDefault();

    if (!_currentImagerieId) {
        alert('❌ Erreur : identifiant de l\'examen introuvable. Veuillez fermer et réessayer.');
        return;
    }

    const btn      = this.querySelector('button[type="submit"]');
    const formData = new FormData(this);
    formData.set('imagerie_id',         _currentImagerieId);
    formData.set('imagerie_ids_groupe', _currentGroupeIds);

    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Traitement…';

    fetch('<?= BASE_URL ?>imagerie/upload', { method: 'POST', body: formData })
        .then(res => {
            const ct = res.headers.get('content-type') || '';
            if (!ct.includes('application/json')) {
                return res.text().then(txt => { throw new Error('Réponse non-JSON : ' + txt.substring(0, 200)); });
            }
            return res.json();
        })
        .then(data => {
            if (data.success) {
                alert('✅ Examen enregistré et transmis au médecin !');
                location.reload();
            } else {
                alert('❌ Erreur : ' + data.message);
                btn.disabled = false;
                btn.innerText = 'ENREGISTRER ET TRANSMETTRE';
            }
        })
        .catch(err => {
            console.error('Upload imagerie:', err);
            alert('❌ Erreur technique : ' + err.message);
            btn.disabled = false;
            btn.innerText = 'ENREGISTRER ET TRANSMETTRE';
        });
});

// ── Suppression ──────────────────────────────────────────────────────
function confirmDelete(id) {
    if (!confirm('⚠️ Voulez-vous vraiment supprimer cet examen et ses images définitivement ?')) return;
    fetch('<?= BASE_URL ?>imagerie/delete/' + id, { method: 'DELETE' })
        .then(r => r.json())
        .then(data => {
            if (data.success) location.reload();
            else alert('Erreur : ' + data.message);
        })
        .catch(() => alert('Erreur technique lors de la suppression.'));
}
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
