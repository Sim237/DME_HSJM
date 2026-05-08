<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<!-- IMPORTATION DES ICONES ET CSS ADDITIONNELS -->
<link rel="stylesheet" href="<?= BASE_URL ?>public/css/bootstrap-icons.css">

<style>
    :root {
        --med-bg: #f4f7f9;
        --med-primary: #1a4a8e;
        --med-danger: #dc3545;
        --med-success: #198754;
        --med-warning: #fd7e14;
        --med-info: #0d6efd;
    }

    body { background-color: var(--med-bg); font-family: 'Segoe UI', sans-serif; }

    /* Topbar Styling */
    .top-nav {
        background: var(--med-primary);
        padding: 12px 40px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        display: flex; justify-content: space-between; align-items: center;
        position: sticky; top: 0; z-index: 1000; color: white;
    }

    /* Dashboard Main Container */
    .dashboard-content { padding: 30px; max-width: 1600px; margin: 0 auto; }

    /* Med Cards */
    .med-card {
        background: white; border: none; border-radius: 20px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.05); margin-bottom: 25px;
        overflow: hidden;
    }
    .card-header-custom {
        padding: 15px 25px; border-bottom: 1px solid #f1f5f9;
        display: flex; justify-content: space-between; align-items: center;
    }

    /* Stats Widgets */
    .stat-widget { padding: 20px; display: flex; align-items: center; gap: 15px; border-bottom: 4px solid transparent; }
    .stat-icon { width: 50px; height: 50px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; }
    .stat-val { font-size: 1.8rem; font-weight: 800; line-height: 1; display: block; }
    .stat-label { font-size: 0.75rem; font-weight: 700; color: #64748b; text-transform: uppercase; }

    /* Table Styling */
    .table-custom thead th {
        background: #f8fafc; color: var(--med-primary);
        font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px;
        padding: 15px; border: none;
    }
    .table-custom td { padding: 15px; vertical-align: middle; border-bottom: 1px solid #f1f5f9; }

    /* Status Badges */
    .status-badge {
        font-size: 0.7rem; font-weight: 800; padding: 5px 12px; border-radius: 50px;
        display: inline-flex; align-items: center; gap: 5px;
    }
    .status-ready { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
    .status-waiting { background: #e0f2fe; color: #0369a1; border: 1px solid #bae6fd; }

    /* Ajout d'une couleur spécifique pour les RDV si vous voulez varier du bleu */
.status-confirmed { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
.status-pending-rdv { background: #fff7ed; color: #9a3412; border: 1px solid #ffedd5; }

    /* Animations */
    .pulse-urgent { animation: pulse-red 2s infinite; }
    @keyframes pulse-red { 0% { opacity: 1; } 50% { opacity: 0.5; } 100% { opacity: 1; } }

    .btn-hosp-pulse { animation: pulse-orange 2s infinite; background-color: var(--med-warning) !important; color: white; border: none; font-weight: bold; }
    @keyframes pulse-orange { 0% { box-shadow: 0 0 0 0 rgba(253, 126, 20, 0.4); } 70% { box-shadow: 0 0 0 10px rgba(253, 126, 20, 0); } 100% { box-shadow: 0 0 0 0 rgba(253, 126, 20, 0); } }

    /* Badge CRH urgence */
    .crh-badge { animation: pulse-crh 2s infinite; }
    @keyframes pulse-crh { 0% { box-shadow: 0 0 0 0 rgba(220, 53, 69, 0.4); } 70% { box-shadow: 0 0 0 8px rgba(220, 53, 69, 0); } 100% { box-shadow: 0 0 0 0 rgba(220, 53, 69, 0); } }
</style>

<!-- TOPBAR -->
<nav class="top-nav no-print">
    <div class="d-flex align-items-center gap-3">
        <div class="bg-white p-2 rounded shadow-sm"><img src="<?= BASE_URL ?>public/images/logo_ordre_malte.png" style="height: 35px;"></div>
        <div>
            <h5 class="fw-bold mb-0">Hôpital <span style="color: #ffd700;">DME</span></h5>
            <small class="opacity-75">Service <?= $_SESSION['nom_service'] ?? 'Médecine' ?> • Dr. <?= $_SESSION['user_nom'] ?></small>
        </div>
    </div>
    <div class="d-flex align-items-center gap-4">
        <div id="liveClock" class="fw-bold fs-5">00:00:00</div>
        <a href="<?= BASE_URL ?>logout" class="btn btn-light rounded-circle"><i class="bi bi-power text-danger"></i></a>
    </div>
</nav>

<div class="dashboard-content">

    <!-- 1. WIDGETS DE PILOTAGE -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="med-card stat-widget" style="border-bottom-color: var(--med-info);">
                <div class="stat-icon bg-primary bg-opacity-10 text-primary"><i class="bi bi-people-fill"></i></div>
                <div><span class="stat-number"><?= is_array($patients_assignes) ? count($patients_assignes) : 0 ?></span><span class="stat-label">En attente</span></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="med-card stat-widget" style="border-bottom-color: var(--med-success);">
                <div class="stat-icon bg-success bg-opacity-10 text-success"><i class="bi bi-flask-fill"></i></div>
                <div><span class="stat-number"><?= is_array($resultats_prets) ? count($resultats_prets) : 0 ?></span><span class="stat-label">Bilans Prêts</span></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="med-card stat-widget" style="border-bottom-color: var(--med-danger);">
                <div class="stat-icon bg-danger bg-opacity-10 text-danger"><i class="bi bi-camera-video"></i></div>
                <div><span class="stat-number">2</span><span class="stat-label">Télémédecine</span></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="med-card stat-widget" style="border-bottom-color: var(--med-warning);">
                <div class="stat-icon bg-warning bg-opacity-10 text-warning"><i class="bi bi-check2-square"></i></div>
                <div><span class="stat-number"><?= is_array($mes_taches) ? count(array_filter($mes_taches, fn($t) => !$t['is_done'])) : 0 ?></span><span class="stat-label">À faire</span></div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- COLONNE GAUCHE (8/12) -->
        <div class="col-lg-8">

            <!-- 2. CENTRE DE RÉSULTATS (LABO) -->
            <div class="med-card">
                <div class="card-header-custom">
                    <h5 class="mb-0 fw-bold text-primary"><i class="bi bi-flask me-2"></i>Centre de Résultats & Bilans</h5>
                    <span class="badge bg-success rounded-pill"><?= is_array($resultats_prets) ? count($resultats_prets) : 0 ?> nouveau(x)</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-custom align-middle">
                        <thead>
                            <tr><th>Examen</th><th>Patient</th><th>Statut</th><th class="text-end">Action</th></tr>
                        </thead>
                        <tbody>
                            <?php if(!empty($resultats_prets)): foreach($resultats_prets as $res): ?>
                                <tr class="table-success bg-opacity-10">
                                    <td><strong><?= htmlspecialchars($res['nom_examen']) ?></strong></td>
                                    <td><?= htmlspecialchars($res['nom'].' '.$res['prenom']) ?></td>
                                    <td><span class="status-badge status-ready"><i class="bi bi-check-circle-fill"></i> ANALYSE TERMINÉE</span></td>
                                    <td class="text-end">
                                        <button class="btn btn-primary btn-sm rounded-pill px-4"
                                                onclick="openResultat('<?= $res['id'] ?>', '<?= addslashes($res['nom'].' '.$res['prenom']) ?>', '<?= addslashes($res['nom_examen']) ?>', '<?= addslashes($res['resultat']) ?>')">
                                            Consulter résultats
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; else: ?>
                                <tr><td colspan="4" class="text-center py-4 text-muted small italic">Aucun résultat à valider.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- 3. FILE D'ATTENTE (S'efface après consultation) -->
            <div class="med-card">
                <div class="card-header-custom">
                    <h5 class="mb-0 fw-bold"><i class="bi bi-person-lines-fill me-2"></i>Patients en salle d'attente</h5>
                </div>
                <div class="table-responsive">
                    <table class="table table-custom align-middle">
                        <thead>
                            <tr><th>Patient</th><th>Motif</th><th>Statut</th><th class="text-end">Action</th></tr>
                        </thead>
                        <tbody>
                            <?php if(!empty($patients_assignes)): foreach($patients_assignes as $p):
                                $prio = $p['niveau_gravite'] ?? 'P3-STABLE';
                                $badgeColor = str_contains($prio, 'P1') ? 'bg-danger' : (str_contains($prio, 'P2') ? 'bg-warning text-dark' : 'bg-success');
                            ?>
                                <tr>
                                    <td>
                                        <div class="fw-bold"><?= strtoupper($p['nom']) ?> <?= $p['prenom'] ?></div>
                                        <small class="text-muted"><?= $p['dossier_numero'] ?></small>
                                    </td>
                                    <td><span class="badge <?= $badgeColor ?> rounded-pill me-2"><?= $prio ?></span><small class="text-muted"><?= htmlspecialchars($p['motif_plainte'] ?? 'Consultation') ?></small></td>
                                    <td><span class="status-badge status-waiting"><i class="bi bi-clock"></i> En attente</span></td>
                                    <td class="text-end">
                                        <?php if (!empty($isPediatrie)): ?>
                                        <a href="<?= BASE_URL ?>consultation-ped/formulaire/<?= (int)$p['id'] ?>" class="btn btn-primary btn-sm rounded-pill px-4">Consulter</a>
                                        <?php else: ?>
                                        <a href="<?= BASE_URL ?>consultation/formulaire?patient_id=<?= $p['id'] ?>&type=EXTERNE&etape=1" class="btn btn-primary btn-sm rounded-pill px-4">Consulter</a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; else: ?>
                                <tr><td colspan="4" class="text-center py-5 text-muted small italic"><i class="bi bi-check2-circle text-success fs-3 d-block mb-2"></i>Tous les patients ont été reçus.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- ================= SECTION MES PATIENTS DU SERVICE ================= -->
<div class="med-card">
    <div class="card-header-custom">
        <h5 class="mb-0 fw-bold text-dark">
            <i class="bi bi-people-fill me-2 text-primary"></i>Patients Externes
        </h5>
        <a href="<?= BASE_URL ?>patients/mes-patients" class="btn btn-sm btn-outline-primary rounded-pill px-3">
            <i class="bi bi-arrow-right me-1"></i>Voir plus
        </a>
    </div>
    <div class="table-responsive">
        <table class="table table-custom align-middle">
            <thead>
                <tr>
                    <th>Patient</th>
                    <th>Statut</th>
                    <th>Dernière hospitalisation</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if(!empty($mes_patients_service)): foreach($mes_patients_service as $mp):
                    $sortieDate = $mp['date_sortie_effective'] ? date('d/m/Y', strtotime($mp['date_sortie_effective'])) : null;
                    $isHospEnCours = ($mp['statut_hosp'] === 'en_cours');
                    $isSorti = ($mp['statut'] === 'SORTIE' || $mp['statut_hosp'] === 'termine');
                    // Badge couleur selon statut
                    if ($isHospEnCours) { $badgeCls = 'bg-primary'; $badgeTxt = 'Hospitalisé'; }
                    elseif ($isSorti)   { $badgeCls = 'bg-secondary'; $badgeTxt = 'Sorti'; }
                    else                { $badgeCls = 'bg-success'; $badgeTxt = 'Externe'; }
                ?>
                    <tr>
                        <td>
                            <div class="fw-bold"><?= strtoupper($mp['nom']) ?> <?= $mp['prenom'] ?></div>
                            <small class="text-muted"><?= $mp['dossier_numero'] ?></small>
                        </td>
                        <td>
                            <span class="status-badge <?= $isHospEnCours ? 'status-waiting' : 'status-ready' ?>">
                                <?= $badgeTxt ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($sortieDate): ?>
                                <small class="text-muted">Sorti le <?= $sortieDate ?></small>
                            <?php elseif ($isHospEnCours): ?>
                                <small class="text-success fw-bold">En cours</small>
                            <?php else: ?>
                                <small class="text-muted">—</small>
                            <?php endif; ?>
                        </td>
                        <td class="text-end">
                            <a href="<?= BASE_URL ?>patients/dossier/<?= $mp['id'] ?>"
                               class="btn btn-sm btn-outline-primary rounded-pill px-3 me-1">
                                <i class="bi bi-folder2-open me-1"></i>Dossier
                            </a>
                            <?php if ($mp['hosp_id'] && $isSorti): ?>
                                <a href="<?= BASE_URL ?>formulaire/crh/<?= $mp['hosp_id'] ?>"
                                   class="btn btn-sm btn-outline-danger rounded-pill px-2">
                                    <i class="bi bi-pencil-square me-1"></i>CRH
                                </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; else: ?>
                    <tr>
                        <td colspan="4" class="text-center py-4 text-muted small">
                            <i class="bi bi-person-x d-block mb-2 fs-3 opacity-25"></i>
                            Aucun patient dans votre service.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ================= SECTION PATIENTS HOSPITALISÉS ================= -->
<div class="med-card">
    <div class="card-header-custom">
        <h5 class="mb-0 fw-bold text-primary">
            <i class="bi bi-hospital me-2"></i>Patients Hospitalisés du Service
        </h5>
        <span class="badge bg-primary rounded-pill px-3">
            <?= count($patients_hospitalises) ?> Patient(s)
        </span>
    </div>
    <div class="table-responsive">
        <table class="table table-custom align-middle">
            <thead>
                <tr>
                    <th>Chambre / Lit</th>
                    <th>Patient</th>
                    <th>Date d'entrée</th>
                    <th class="text-end">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if(!empty($patients_hospitalises)): foreach($patients_hospitalises as $hosp): ?>
                    <tr>
                        <td>
                            <div class="d-flex align-items-center">
                                <div class="bg-primary bg-opacity-10 text-primary rounded p-2 me-3">
                                    <i class="bi bi-door-closed-fill"></i>
                                </div>
                                <div>
                                    <span class="fw-bold text-dark">Ch. <?= htmlspecialchars($hosp['nom_chambre']) ?></span><br>
                                    <small class="text-muted">Lit : <?= htmlspecialchars($hosp['nom_lit']) ?></small>
                                </div>
                            </div>
                        </td>
                        <td>
                            <strong><?= strtoupper($hosp['nom']) ?> <?= $hosp['prenom'] ?></strong><br>
                            <small class="text-muted"><?= $hosp['dossier_numero'] ?></small>
                        </td>
                        <td>
                            <small class="text-muted">Admis le :</small><br>
                            <span class="small fw-bold"><?= date('d/m/Y', strtotime($hosp['date_admission'])) ?></span>
                        </td>
                        <td class="text-end">
                            <div class="btn-group">
                                <a href="<?= BASE_URL ?>patients/dossier/<?= $hosp['id'] ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3 me-2">
                                    <i class="bi bi-folder2-open me-1"></i> Dossier
                                </a>
                                <a href="<?= BASE_URL ?>hospitalisation/observations-evolution/<?= $hosp['id'] ?>" class="btn btn-sm btn-primary rounded-pill px-3">
                                    <i class="bi bi-pencil-square me-1"></i> Suivi
                                </a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; else: ?>
                    <tr>
                        <td colspan="4" class="text-center py-5 text-muted small italic">
                            <i class="bi bi-bed d-block mb-2 fs-3 opacity-25"></i>
                            Aucun patient hospitalisé dans votre service actuellement.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ================= SECTION CRH À RÉDIGER ================= -->
<?php if (!empty($crh_en_attente)): ?>
<div class="med-card border-danger border-2">
    <div class="card-header-custom" style="background: #fff5f5;">
        <h5 class="mb-0 fw-bold text-danger">
            <i class="bi bi-file-earmark-medical me-2"></i>Comptes-rendus d'hospitalisation à rédiger
        </h5>
        <span class="badge bg-danger rounded-pill crh-badge"><?= count($crh_en_attente) ?> en attente</span>
    </div>
    <div class="table-responsive">
        <table class="table table-custom align-middle">
            <thead>
                <tr>
                    <th>Patient</th>
                    <th>Admis le</th>
                    <th>Sorti le</th>
                    <th>Motif</th>
                    <th class="text-end">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($crh_en_attente as $crh): ?>
                    <tr>
                        <td>
                            <strong><?= strtoupper($crh['nom']) ?> <?= $crh['prenom'] ?></strong><br>
                            <small class="text-muted"><?= $crh['dossier_numero'] ?></small>
                        </td>
                        <td><small><?= date('d/m/Y', strtotime($crh['date_admission'])) ?></small></td>
                        <td><small class="text-danger fw-bold"><?= date('d/m/Y', strtotime($crh['date_sortie_effective'])) ?></small></td>
                        <td><small class="text-muted"><?= htmlspecialchars(substr($crh['motif_hospitalisation'] ?? '—', 0, 50)) ?></small></td>
                        <td class="text-end">
                            <a href="<?= BASE_URL ?>formulaire/crh/<?= $crh['hosp_id'] ?>"
                               class="btn btn-sm btn-danger rounded-pill px-3 fw-bold">
                                <i class="bi bi-pencil-square me-1"></i> Rédiger CRH
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<!-- ══ SUIVI DES BILANS DEMANDÉS ══ -->
<style>
    .bilan-card { background: white; border-radius: 16px; border: 1px solid #e2e8f0; overflow: hidden; margin-bottom: 24px; box-shadow: 0 2px 8px rgba(0,0,0,.04); }
    .bilan-header { padding: 16px 20px; border-bottom: 1px solid #f1f5f9; display: flex; align-items: center; justify-content: space-between; }
    .bilan-table { width: 100%; border-collapse: collapse; }
    .bilan-table thead th { background: #f8fafc; color: #64748b; font-size: .7rem; font-weight: 700; text-transform: uppercase; letter-spacing: .8px; padding: 10px 16px; border-bottom: 1px solid #e2e8f0; }
    .bilan-table tbody tr { border-bottom: 1px solid #f1f5f9; transition: background .15s; }
    .bilan-table tbody tr:hover { background: #fafbff; }
    .bilan-table tbody td { padding: 12px 16px; font-size: .88rem; }
    .bilan-table tbody tr.nouveau { background: #fffbeb; border-left: 3px solid #f59e0b; }

    .badge-labo  { background: #e0f2fe; color: #0369a1; font-size: .72rem; font-weight: 700; padding: 3px 10px; border-radius: 20px; }
    .badge-radio { background: #f3e8ff; color: #7c3aed; font-size: .72rem; font-weight: 700; padding: 3px 10px; border-radius: 20px; }

    .statut-chip { display: inline-flex; align-items: center; gap: 5px; padding: 4px 10px; border-radius: 20px; font-size: .75rem; font-weight: 700; }
    .chip-attente  { background:#fef3c7; color:#92400e; }
    .chip-analyse  { background:#dbeafe; color:#1d4ed8; }
    .chip-pret     { background:#dcfce7; color:#166534; }
    .chip-interprete { background:#d1fae5; color:#065f46; }
    .chip-default  { background:#f1f5f9; color:#475569; }

    .alerte-dot { width:8px; height:8px; background:#ef4444; border-radius:50%; display:inline-block; animation: pulse-alert .8s infinite; }
    @keyframes pulse-alert { 0%,100%{transform:scale(1)} 50%{transform:scale(1.4)} }

    .btn-bilan { display:inline-flex; align-items:center; gap:5px; padding:5px 12px; border-radius:8px; font-size:.75rem; font-weight:700; border:none; cursor:pointer; transition:all .15s; text-decoration:none; }
    .btn-b-voir    { background:#e0f2fe; color:#0369a1; } .btn-b-voir:hover    { background:#bae6fd; color:#0369a1; }
    .btn-b-comment { background:#f0fdf4; color:#166534; } .btn-b-comment:hover { background:#bbf7d0; color:#166534; }
    .btn-b-rdv     { background:#fef3c7; color:#92400e; } .btn-b-rdv:hover     { background:#fde68a; color:#92400e; }
    .btn-b-attente { background:#f1f5f9; color:#94a3b8; cursor:default; }

    .patient-chip { font-size:.8rem; font-weight:700; color:#334155; }
    .patient-ref  { font-size:.72rem; color:#94a3b8; }

    /* Modals */
    .modal-bilan .modal-content { border-radius:20px; border:0; box-shadow:0 25px 50px rgba(0,0,0,.15); }
    .modal-bilan .modal-header  { border-bottom:1px solid #f1f5f9; padding:20px 24px; }
    .modal-bilan .modal-footer  { border-top:1px solid #f1f5f9; padding:16px 24px; }
    .result-row { background:#f8fafc; border-radius:10px; padding:12px 16px; margin-bottom:8px; }
    .result-val { font-size:1.4rem; font-weight:900; }
    .result-val.anormal { color:#dc2626; }
    .result-val.normal  { color:#16a34a; }
    .commentaire-item { background:#f8fafc; border-radius:10px; padding:10px 14px; margin-bottom:8px; border-left:3px solid #3b82f6; }
    .commentaire-item .c-meta { font-size:.72rem; color:#94a3b8; margin-bottom:4px; }
    .commentaire-item .c-text { font-size:.85rem; color:#334155; }
</style>

<div class="bilan-card">
    <div class="bilan-header">
        <div class="d-flex align-items-center gap-3">
            <h5 class="mb-0 fw-bold" style="color:#1e293b"><i class="bi bi-clipboard2-pulse me-2 text-primary"></i>Suivi des Bilans Demandés</h5>
            <?php if (!empty($nouveaux_resultats) && $nouveaux_resultats > 0): ?>
                <span class="badge bg-danger rounded-pill"><?= $nouveaux_resultats ?> nouveau<?= $nouveaux_resultats > 1 ? 'x' : '' ?></span>
            <?php endif; ?>
        </div>
        <small class="text-muted"><?= count($suivi_bilans ?? []) ?> bilan(s) en cours</small>
    </div>

    <div class="table-responsive">
        <table class="bilan-table">
            <thead>
                <tr>
                    <th>Patient</th>
                    <th>Type</th>
                    <th>Examen / Zone</th>
                    <th>Statut</th>
                    <th>Résultat</th>
                    <th style="text-align:right">Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php if (!empty($suivi_bilans)): foreach ($suivi_bilans as $b):
                $isDisponible = in_array($b['statut'], ['RESULTATS_PRETS','VALIDES','interprete','termine']);
                $isNouveau    = $isDisponible && (int)($b['nb_commentaires'] ?? 0) === 0;
                $isAnormal    = !empty($b['anormal']);
                $patId = (int)($b['patient_id'] ?? 0);
                $patNom = strtoupper($b['patient_nom'] ?? '?') . ' ' . ($b['patient_prenom'] ?? '');
                $patRef = htmlspecialchars($b['dossier_numero'] ?? '');

                // Chip statut
                $chips = [
                    'EN_ATTENTE'             => ['chip-attente',   'bi-clock-history',      'En attente'],
                    'PRELEVEMENTS_EFFECTUES' => ['chip-analyse',   'bi-droplet-half',        'Prélevé'],
                    'EN_ANALYSE'             => ['chip-analyse',   'bi-gear-wide-connected', 'En analyse'],
                    'RESULTATS_PRETS'        => ['chip-pret',      'bi-check-circle-fill',   'Résultat prêt'],
                    'VALIDES'                => ['chip-pret',      'bi-patch-check-fill',    'Validé'],
                    'en_cours'               => ['chip-analyse',   'bi-gear-wide-connected', 'En cours'],
                    'termine'                => ['chip-interprete','bi-check-circle-fill',   'Terminé'],
                    'interprete'             => ['chip-interprete','bi-star-fill',           'Interprété'],
                ];
                [$cCls, $cIcon, $cTxt] = $chips[$b['statut']] ?? ['chip-default','bi-dash','Inconnu'];
            ?>
                <tr class="<?= $isNouveau ? 'nouveau' : '' ?>">
                    <td>
                        <?php if ($isNouveau): ?><span class="alerte-dot me-1"></span><?php endif; ?>
                        <span class="patient-chip"><?= htmlspecialchars($patNom) ?></span><br>
                        <span class="patient-ref"><?= $patRef ?></span>
                    </td>
                    <td>
                        <?php if ($b['type'] === 'Labo'): ?>
                            <span class="badge-labo"><i class="bi bi-droplet me-1"></i>Labo</span>
                        <?php else: ?>
                            <span class="badge-radio"><i class="bi bi-radioactive me-1"></i>Radio</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <strong><?= htmlspecialchars($b['label']) ?></strong>
                        <?php if ((int)($b['nb_commentaires'] ?? 0) > 0): ?>
                            <br><small style="color:#64748b"><i class="bi bi-chat-text me-1"></i><?= $b['nb_commentaires'] ?> commentaire(s)</small>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="statut-chip <?= $cCls ?>">
                            <i class="bi <?= $cIcon ?>"></i> <?= $cTxt ?>
                        </span>
                    </td>
                    <td>
                        <?php if ($isDisponible && $b['type'] === 'Labo' && $b['valeur_numerique'] !== null): ?>
                            <span class="fw-bold <?= $isAnormal ? 'text-danger' : 'text-success' ?>">
                                <?= $b['valeur_numerique'] ?> <?= htmlspecialchars($b['unite'] ?? '') ?>
                            </span>
                            <?php if ($isAnormal): ?><i class="bi bi-exclamation-triangle-fill text-danger ms-1"></i><?php endif; ?>
                        <?php elseif ($isDisponible): ?>
                            <span style="color:#16a34a;font-size:.78rem;font-weight:700"><i class="bi bi-check2 me-1"></i>Disponible</span>
                        <?php else: ?>
                            <span style="color:#94a3b8;font-size:.78rem">—</span>
                        <?php endif; ?>
                    </td>
                    <td style="text-align:right">
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
                                <i class="bi bi-chat-text"></i> Commenter
                            </button>
                            <button class="btn-bilan btn-b-rdv"
                                    onclick="ouvrirRdv(<?= $patId ?>, '<?= htmlspecialchars(addslashes($patNom), ENT_QUOTES) ?>', '<?= htmlspecialchars(addslashes($b['label']), ENT_QUOTES) ?>')">
                                <i class="bi bi-calendar-plus"></i> RDV
                            </button>
                        <?php else: ?>
                            <span class="btn-bilan btn-b-attente"><i class="bi bi-hourglass-split"></i> En attente</span>
                        <?php endif; ?>
                        </div>
                    </td>
                </tr>
            <?php endforeach; else: ?>
                <tr><td colspan="6" class="text-center py-5 text-muted small">
                    <i class="bi bi-clipboard2 d-block fs-2 mb-2 opacity-25"></i>Aucun bilan en cours.
                </td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ══ MODAL : RÉSULTATS LABO ══ -->
<div class="modal fade modal-bilan" id="modalResultatsLabo" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold"><i class="bi bi-flask me-2 text-primary"></i>Résultats du Bilan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4" id="resultatsLaboBody">
                <div class="text-center py-4"><div class="spinner-border text-primary"></div></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light rounded-pill" data-bs-dismiss="modal">Fermer</button>
                <button class="btn btn-success rounded-pill" id="btnCommentDepuisResultat" onclick="commenterDepuisResultat()">
                    <i class="bi bi-chat-text me-1"></i>Ajouter un commentaire
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ══ MODAL : COMMENTER BILAN ══ -->
<div class="modal fade modal-bilan" id="modalCommenter" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold"><i class="bi bi-chat-text me-2 text-success"></i>Commenter le Résultat</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <input type="hidden" id="c_demande_id">
                <input type="hidden" id="c_type_bilan">
                <input type="hidden" id="c_patient_id">
                <div class="alert alert-light border rounded-3 mb-3 small" id="c_bilan_info"></div>
                <label class="form-label small fw-bold text-muted text-uppercase">Commentaire / Interprétation clinique</label>
                <textarea id="c_commentaire" class="form-control" rows="5"
                          placeholder="Entrez votre analyse des résultats, conduite à tenir, modifications thérapeutiques..."></textarea>
                <div id="commentaires_existants" class="mt-3"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light rounded-pill" data-bs-dismiss="modal">Annuler</button>
                <button class="btn btn-success rounded-pill fw-bold" onclick="enregistrerCommentaire()">
                    <i class="bi bi-check2 me-1"></i>Enregistrer
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ══ MODAL : PROGRAMMER RDV ══ -->
<div class="modal fade modal-bilan" id="modalRdvBilan" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold"><i class="bi bi-calendar-plus me-2 text-warning"></i>Programmer un RDV</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <input type="hidden" id="rdv_patient_id">
                <div class="alert alert-warning border-0 rounded-3 mb-3 small" id="rdv_info"></div>
                <div class="mb-3">
                    <label class="form-label small fw-bold text-muted text-uppercase">Titre du RDV</label>
                    <input type="text" id="rdv_titre" class="form-control" value="Présentation résultats bilans">
                </div>
                <div class="row g-3 mb-3">
                    <div class="col-6">
                        <label class="form-label small fw-bold text-muted text-uppercase">Date et heure</label>
                        <input type="datetime-local" id="rdv_date_debut" class="form-control" required>
                    </div>
                    <div class="col-6">
                        <label class="form-label small fw-bold text-muted text-uppercase">Fin (optionnel)</label>
                        <input type="datetime-local" id="rdv_date_fin" class="form-control">
                    </div>
                </div>
                <div class="mb-2">
                    <label class="form-label small fw-bold text-muted text-uppercase">Notes</label>
                    <textarea id="rdv_notes" class="form-control" rows="2" placeholder="Examens à apporter, instructions..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light rounded-pill" data-bs-dismiss="modal">Annuler</button>
                <button class="btn btn-warning rounded-pill fw-bold text-dark" onclick="enregistrerRdv()">
                    <i class="bi bi-calendar-check me-1"></i>Confirmer le RDV
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// ── DONNÉES contexte commentaire depuis modal résultats ──
let _currentBilanCtx = {};

function voirResultatsLabo(demandeId) {
    document.getElementById('resultatsLaboBody').innerHTML =
        '<div class="text-center py-4"><div class="spinner-border text-primary"></div></div>';
    new bootstrap.Modal(document.getElementById('modalResultatsLabo')).show();

    fetch('<?= BASE_URL ?>medecin/resultats-bilan?id=' + demandeId)
        .then(r => r.json())
        .then(data => {
            if (!data.success) { document.getElementById('resultatsLaboBody').innerHTML = '<p class="text-danger">Erreur chargement.</p>'; return; }

            let html = '';
            if (data.examens.length === 0) {
                html = '<p class="text-muted text-center py-3">Aucun résultat enregistré pour cette demande.</p>';
            } else {
                const meta = data.examens[0];
                html += `<div class="d-flex align-items-center gap-3 mb-4 p-3 bg-light rounded-3">
                    <i class="bi bi-person-circle fs-4 text-primary"></i>
                    <div><div class="fw-bold">${meta.patient_nom || ''} ${meta.patient_prenom || ''}</div>
                    <small class="text-muted">Dr. ${meta.medecin_nom || ''} • Statut : ${meta.statut || ''}</small></div></div>`;

                data.examens.forEach(ex => {
                    const anormal = ex.anormal == 1;
                    const valCls  = anormal ? 'anormal' : 'normal';
                    html += `<div class="result-row">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <div class="fw-bold">${ex.nom_examen || '—'}</div>
                                <small class="text-muted">${ex.categorie || ''}</small>
                            </div>
                            <span class="badge rounded-pill ${anormal ? 'bg-danger' : 'bg-success'}">${anormal ? 'ANORMAL' : 'NORMAL'}</span>
                        </div>`;
                    if (ex.valeur_numerique !== null) {
                        html += `<div class="mt-2 result-val ${valCls}">${ex.valeur_numerique} <small style="font-size:.7em">${ex.unite||''}</small></div>
                            <small class="text-muted">Norme : ${ex.valeur_normale_min||'?'}–${ex.valeur_normale_max||'?'} ${ex.unite||''}</small>`;
                    } else if (ex.resultat) {
                        html += `<p class="mt-2 mb-0 small">${ex.resultat}</p>`;
                    }
                    if (ex.interpretation) html += `<p class="mt-1 mb-0 small text-primary"><i class="bi bi-chat-quote me-1"></i>${ex.interpretation}</p>`;
                    html += '</div>';
                });
            }

            // Commentaires existants
            if (data.commentaires && data.commentaires.length > 0) {
                html += '<h6 class="fw-bold mt-4 mb-2 text-muted text-uppercase" style="font-size:.72rem;letter-spacing:.8px">Commentaires médecin</h6>';
                data.commentaires.forEach(c => {
                    html += `<div class="commentaire-item">
                        <div class="c-meta">Dr. ${c.medecin_nom} — ${new Date(c.created_at).toLocaleDateString('fr-FR')}</div>
                        <div class="c-text">${c.commentaire}</div></div>`;
                });
            }

            document.getElementById('resultatsLaboBody').innerHTML = html;
            // Stocker contexte pour commentaire depuis modal résultats
            _currentBilanCtx = { demandeId: demandeId, type: 'LABO' };
        });
}

function commenterDepuisResultat() {
    bootstrap.Modal.getInstance(document.getElementById('modalResultatsLabo'))?.hide();
    // On déclenche avec le contexte stocké
    // Besoin du patient_id – récupéré dans les données fetchées
    fetch('<?= BASE_URL ?>medecin/resultats-bilan?id=' + _currentBilanCtx.demandeId)
        .then(r => r.json()).then(data => {
            if (data.examens && data.examens[0]) {
                // On n'a pas directement patient_id dans cette réponse → chercher dans le DOM
                ouvrirCommenter(_currentBilanCtx.demandeId, 'LABO', 0,
                    (data.examens[0].patient_nom || '') + ' ' + (data.examens[0].patient_prenom || ''),
                    data.examens[0].nom_examen || 'Bilan');
            }
        });
}

function ouvrirCommenter(demandeId, typeBilan, patientId, patNom, examNom) {
    document.getElementById('c_demande_id').value = demandeId;
    document.getElementById('c_type_bilan').value = typeBilan;
    document.getElementById('c_patient_id').value = patientId;
    document.getElementById('c_commentaire').value = '';
    document.getElementById('c_bilan_info').innerHTML =
        `<strong>${patNom}</strong> — <em>${examNom}</em>`;

    // Charger les commentaires existants
    fetch('<?= BASE_URL ?>medecin/resultats-bilan?id=' + demandeId)
        .then(r => r.json()).then(data => {
            let html = '';
            if (data.commentaires && data.commentaires.length > 0) {
                html = '<h6 class="small text-muted fw-bold text-uppercase mb-2" style="letter-spacing:.7px">Commentaires précédents</h6>';
                data.commentaires.forEach(c => {
                    html += `<div class="commentaire-item">
                        <div class="c-meta">Dr. ${c.medecin_nom} — ${new Date(c.created_at).toLocaleDateString('fr-FR')}</div>
                        <div class="c-text">${c.commentaire}</div></div>`;
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

    fetch('<?= BASE_URL ?>medecin/commenter-bilan', { method:'POST', body:fd })
        .then(r => r.json()).then(data => {
            if (data.success) {
                bootstrap.Modal.getInstance(document.getElementById('modalCommenter'))?.hide();
                // Toast + reload léger
                const toast = document.createElement('div');
                toast.style.cssText = 'position:fixed;bottom:20px;right:20px;background:#16a34a;color:white;padding:12px 20px;border-radius:12px;font-weight:700;z-index:9999;box-shadow:0 8px 24px rgba(0,0,0,.2)';
                toast.innerHTML = '<i class="bi bi-check2-circle me-2"></i>Commentaire enregistré.';
                document.body.appendChild(toast);
                setTimeout(() => { toast.remove(); location.reload(); }, 2000);
            } else {
                alert('Erreur : ' + (data.message || 'Inconnue'));
            }
        });
}

function ouvrirRdv(patientId, patNom, examNom) {
    document.getElementById('rdv_patient_id').value = patientId;
    document.getElementById('rdv_info').innerHTML =
        `<i class="bi bi-person-fill me-2"></i><strong>${patNom}</strong> — ${examNom}`;
    document.getElementById('rdv_titre').value = `Présentation résultats — ${examNom}`;
    // Pré-remplir date = demain à 9h
    const d = new Date(); d.setDate(d.getDate()+1); d.setHours(9,0,0);
    document.getElementById('rdv_date_debut').value = d.toISOString().slice(0,16);
    new bootstrap.Modal(document.getElementById('modalRdvBilan')).show();
}

function enregistrerRdv() {
    const fd = new FormData();
    fd.append('patient_id',  document.getElementById('rdv_patient_id').value);
    fd.append('titre',       document.getElementById('rdv_titre').value);
    fd.append('date_debut',  document.getElementById('rdv_date_debut').value);
    fd.append('date_fin',    document.getElementById('rdv_date_fin').value);
    fd.append('notes',       document.getElementById('rdv_notes').value);

    if (!fd.get('date_debut')) { alert('Veuillez choisir une date.'); return; }

    fetch('<?= BASE_URL ?>medecin/programmer-rdv-bilan', { method:'POST', body:fd })
        .then(r => r.json()).then(data => {
            if (data.success) {
                bootstrap.Modal.getInstance(document.getElementById('modalRdvBilan'))?.hide();
                const toast = document.createElement('div');
                toast.style.cssText = 'position:fixed;bottom:20px;right:20px;background:#d97706;color:white;padding:12px 20px;border-radius:12px;font-weight:700;z-index:9999;box-shadow:0 8px 24px rgba(0,0,0,.2)';
                toast.innerHTML = '<i class="bi bi-calendar-check me-2"></i>RDV programmé avec succès !';
                document.body.appendChild(toast);
                setTimeout(() => toast.remove(), 3000);
            } else {
                alert('Erreur : ' + (data.message || 'Inconnue'));
            }
        });
}
</script>

<!-- SECTION : DOSSIERS PARTAGÉS (Dashboard Médecin) -->
<div class="med-card shadow-sm border-0">
    <div class="card-header-custom bg-info text-white rounded-top-4 py-3">
        <h6 class="mb-0 fw-bold"><i class="bi bi-share me-2"></i>Dossiers Partagés</h6>
    </div>
    <div class="card-body p-3">
        <!-- Navigation des onglets -->
        <ul class="nav nav-pills nav-justified mb-3" id="partageTab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active rounded-pill fw-bold" data-bs-toggle="pill" data-bs-target="#reçus" type="button">
                    <i class="bi bi-inbox me-2"></i>Reçus
                    <span class="badge bg-light text-primary ms-2"><?= is_array($dossiers_reçus) ? count($dossiers_reçus) : 0 ?></span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link rounded-pill fw-bold" data-bs-toggle="pill" data-bs-target="#envoyés" type="button">
                    <i class="bi bi-send me-2"></i>Envoyés
                    <span class="badge bg-light text-primary ms-2"><?= is_array($dossiers_envoyés) ? count($dossiers_envoyés) : 0 ?></span>
                </button>
            </li>
        </ul>

        <!-- Contenu des onglets -->
        <div class="tab-content">
            <!-- DOSSIERS REÇUS -->
            <div class="tab-pane fade show active" id="reçus" role="tabpanel">
                <?php if(!empty($dossiers_reçus) && is_array($dossiers_reçus)): foreach($dossiers_reçus as $r): ?>
                    <div class="alert alert-light border mb-2 d-flex justify-content-between align-items-center">
                        <div>
                            <strong class="text-primary"><?= htmlspecialchars($r['nom'].' '.$r['prenom']) ?></strong><br>
                            <small class="text-muted"><i class="bi bi-person-fill"></i> Envoyé par Dr. <?= htmlspecialchars($r['expediteur_nom']) ?></small>
                        </div>
                        <a href="<?= BASE_URL ?>patients/dossier/<?= $r['patient_id'] ?>" class="btn btn-sm btn-info text-white rounded-pill px-3">
                            <i class="bi bi-eye"></i> Consulter
                        </a>
                    </div>
                <?php endforeach; else: ?>
                    <div class="text-center py-4 text-muted small italic">Aucun dossier reçu.</div>
                <?php endif; ?>
            </div>

            <!-- DOSSIERS ENVOYÉS -->
            <div class="tab-pane fade" id="envoyés" role="tabpanel">
                <?php if(!empty($dossiers_envoyés) && is_array($dossiers_envoyés)): foreach($dossiers_envoyés as $e): ?>
                    <div class="alert alert-light border mb-2">
                        <strong class="text-dark"><?= htmlspecialchars($e['nom'].' '.$e['prenom']) ?></strong><br>
                        <small class="text-muted"><i class="bi bi-send-check"></i> Partagé à Dr. <?= htmlspecialchars($e['destinataire_nom']) ?></small>
                    </div>
                <?php endforeach; else: ?>
                    <div class="text-center py-4 text-muted small italic">Aucun dossier envoyé.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

            <!-- ================= SECTION MES RENDEZ-VOUS ================= -->
<div class="med-card">
    <div class="card-header-custom">
        <h5 class="mb-0 fw-bold text-primary"><i class="bi bi-calendar2-check me-2"></i>Mes Rendez-vous à venir</h5>
        <a href="<?= BASE_URL ?>agenda" class="btn btn-sm btn-outline-primary rounded-pill px-3">Voir l'agenda</a>
    </div>
    <div class="table-responsive">
        <table class="table table-custom align-middle">
            <thead>
                <tr>
                    <th>Date & Heure</th>
                    <th>Patient</th>
                    <th>Motif</th>
                    <th class="text-end">Statut</th>
                </tr>
            </thead>
            <tbody>
                <?php if(!empty($mes_rdv)): foreach($mes_rdv as $rdv): ?>
                    <tr>
                        <td>
                            <div class="fw-bold text-dark">
                                <i class="bi bi-clock text-primary me-2"></i>
                                <?= date('d/m', strtotime($rdv['date_rdv'])) ?> à <?= date('H:i', strtotime($rdv['date_rdv'])) ?>
                            </div>
                        </td>
                        <td>
                            <strong><?= strtoupper($rdv['nom']) ?> <?= $rdv['prenom'] ?></strong><br>
                            <small class="text-muted"><?= $rdv['dossier_numero'] ?></small>
                        </td>
                        <td><small><?= htmlspecialchars($rdv['motif']) ?></small></td>
                        <td class="text-end">
                            <?php
                                $statusClass = ($rdv['statut'] == 'CONFIRME') ? 'bg-success' : 'bg-warning text-dark';
                            ?>
                            <span class="badge <?= $statusClass ?> rounded-pill px-3">
                                <?= $rdv['statut'] ?>
                            </span>
                        </td>
                    </tr>
                <?php endforeach; else: ?>
                    <tr>
                        <td colspan="4" class="text-center py-4 text-muted small italic">
                            <i class="bi bi-calendar-x d-block mb-2 fs-3 opacity-50"></i>
                            Aucun rendez-vous programmé pour le moment.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
        </div>

        <!-- COLONNE DROITE (4/12) -->
        <div class="col-lg-4">

            <!-- 4. CONSULTATIONS RÉCENTES + HOSPITALISER 1H -->
            <div class="med-card">
                <div class="card-header-custom bg-dark text-white"><h6 class="mb-0">Consultations Récentes (Aujourd'hui)</h6></div>
                <div class="p-3">
                    <?php if(!empty($patients_consultes)): foreach($patients_consultes as $hc): ?>
                        <div class="d-flex justify-content-between align-items-center mb-3 p-3 bg-light rounded-4 border">
                            <div>
                                <div class="fw-bold small"><?= $hc['nom'] ?> <?= $hc['prenom'] ?></div>
                                <small class="text-muted"><i class="bi bi-clock me-1"></i><?= date('H:i', strtotime($hc['date_consultation'])) ?></small>
                            </div>
                            <div class="d-flex gap-2">
                                <?php if($hc['can_hospitaliser'] && $hc['statut_hosp'] == 'AUCUN'): ?>
                                    <button class="btn btn-sm btn-hosp-pulse rounded-pill px-3" onclick="hospitaliser(<?= $hc['consult_id'] ?>)">Hosp.</button>
                                <?php elseif($hc['statut_hosp'] != 'AUCUN'): ?>
                                    <span class="badge bg-success rounded-pill"><i class="bi bi-check"></i> Transmis</span>
                                <?php endif; ?>
                                <button class="btn btn-sm btn-outline-primary rounded-circle" onclick="window.location.href='<?= BASE_URL ?>patients/dossier/<?= $hc['patient_id'] ?>'"><i class="bi bi-folder2-open"></i></button>
                            </div>
                        </div>
                    <?php endforeach; else: ?>
                        <p class="text-center text-muted py-3 small">Aucune consultation récente.</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- 5. TO-DO LIST PERSO -->
            <div class="med-card">
                <div class="card-header-custom">
                    <h6 class="mb-0">Mes Rappels / Notes</h6>
                    <button class="btn btn-sm btn-primary rounded-circle" onclick="document.getElementById('todoIn').focus()"><i class="bi bi-plus"></i></button>
                </div>
                <div class="p-3">
                    <div class="input-group mb-3">
                        <input type="text" id="todoIn" class="form-control form-control-sm border-0 bg-light" placeholder="Note rapide...">
                        <button class="btn btn-primary btn-sm" onclick="addTask()">OK</button>
                    </div>
                    <div id="todoList">
                        <?php if(!empty($mes_taches)): foreach($mes_taches as $t): ?>
                            <div class="d-flex align-items-center justify-content-between mb-2 p-2 rounded hover-bg-light">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" <?= $t['is_done'] ? 'checked' : '' ?> onchange="toggleTask(<?= $t['id'] ?>)">
                                    <label class="form-check-label small <?= $t['is_done'] ? 'text-decoration-line-through text-muted' : '' ?>"><?= htmlspecialchars($t['label']) ?></label>
                                </div>
                                <i class="bi bi-trash text-muted cursor-pointer" onclick="deleteTask(<?= $t['id'] ?>)"></i>
                            </div>
                        <?php endforeach; endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- MODAL : VALIDATION BILAN LABO -->
<div class="modal fade" id="modalResultat" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header bg-primary text-white border-0 p-4">
                <h5 class="modal-title fw-bold">Validation du Résultat Médical</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="<?= BASE_URL ?>consultation/confirmer-diagnostic" method="POST">
                <input type="hidden" name="resultat_id" id="val-res-id">
                <div class="modal-body p-4">
                    <div class="p-4 bg-light rounded-4 mb-4 border">
                        <div class="row">
                            <div class="col-md-6"><small class="text-muted d-block fw-bold">Patient</small><p id="val-res-patient" class="fw-bold mb-0">---</p></div>
                            <div class="col-md-6 text-end"><small class="text-muted d-block fw-bold">Examen</small><p id="val-res-examen" class="fw-bold mb-0 text-primary">---</p></div>
                        </div>
                        <hr class="my-3">
                        <small class="text-muted d-block fw-bold mb-2">Valeur technique :</small>
                        <div id="val-res-data" class="fs-4 fw-bold text-dark bg-white p-3 rounded border border-primary border-opacity-25">---</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Conclusion Médicale / Action Thérapeutique</label>
                        <textarea name="diagnostic_complement" class="form-control rounded-4 shadow-sm" rows="5" placeholder="En fonction de ce résultat, quel est votre diagnostic ou changement de traitement ?" required></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light border-0 p-4">
                    <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Fermer</button>
                    <button type="submit" class="btn btn-success rounded-pill px-5 shadow-sm fw-bold">Valider & Intégrer au Dossier</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Horloge
    setInterval(() => { document.getElementById('liveClock').innerText = new Date().toLocaleTimeString('fr-FR'); }, 1000);

    // Fonction d'ouverture modale Labo (Bootstrap 5)
    function openResultat(id, patient, examen, resultat) {
        document.getElementById('val-res-id').value = id;
        document.getElementById('val-res-patient').innerText = patient;
        document.getElementById('val-res-examen').innerText = examen;
        document.getElementById('val-res-data').innerText = resultat;

        var myModal = new bootstrap.Modal(document.getElementById('modalResultat'));
        myModal.show();
    }

    // Action Hospitaliser
    function hospitaliser(consultId) {
        if(!confirm('Confirmer la demande d\'hospitalisation immédiate ?')) return;
        fetch('<?= BASE_URL ?>dashboard/hospitaliser', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'consult_id=' + consultId
        }).then(res => res.json()).then(data => {
            if(data.success) { alert('Demande transmise aux infirmiers.'); location.reload(); }
            else { alert('Erreur : ' + data.message); }
        });
    }

    // Tâches AJAX
    function addTask() {
        const label = document.getElementById('todoIn').value;
        if(!label) return;
        fetch('<?= BASE_URL ?>dashboard/add-task', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'label=' + encodeURIComponent(label)
        }).then(() => location.reload());
    }

    function toggleTask(id) {
        fetch('<?= BASE_URL ?>dashboard/toggle-task', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'id=' + id
        });
    }

    function deleteTask(id) {
        if(!confirm('Supprimer ?')) return;
        fetch('<?= BASE_URL ?>dashboard/delete-task', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'id=' + id
        }).then(() => location.reload());
    }
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>