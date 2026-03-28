<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<!-- IMPORTATION DES ICONES ET CSS ADDITIONNELS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

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
                                        <a href="<?= BASE_URL ?>consultation/formulaire?patient_id=<?= $p['id'] ?>&type=EXTERNE&etape=1" class="btn btn-primary btn-sm rounded-pill px-4">Consulter</a>
                                    </td>
                                </tr>
                            <?php endforeach; else: ?>
                                <tr><td colspan="4" class="text-center py-5 text-muted small italic"><i class="bi bi-check2-circle text-success fs-3 d-block mb-2"></i>Tous les patients ont été reçus.</td></tr>
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

<div class="med-card">
    <div class="card-header-custom">
        <h5 class="mb-0 fw-bold text-primary"><i class="bi bi-search me-2"></i>Suivi des Bilans Demandés</h5>
    </div>
    <div class="table-responsive">
        <table class="table table-custom align-middle">
            <thead>
                <tr class="small text-muted text-uppercase">
                    <th>Type</th>
                    <th>Examen / Zone</th>
                    <th>Statut</th>
                    <th class="text-end">Action</th> <!-- Nouvelle colonne -->
                </tr>
            </thead>
            <tbody>
                <?php if(!empty($suivi_bilans)): foreach($suivi_bilans as $b): ?>
                    <tr>
                        <td><span class="badge bg-light text-dark border"><?= $b['type'] ?></span></td>
                        <td><strong><?= htmlspecialchars($b['label']) ?></strong></td>
                        <td>
                            <?php if($b['statut'] == 'EN_ATTENTE'): ?>
                                <span class="text-warning small fw-bold"><i class="bi bi-clock-history"></i> Au service</span>
                            <?php else: ?>
                                <span class="text-success small fw-bold"><i class="bi bi-check-circle-fill"></i> Prêt</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end">
                            <?php if($b['statut'] != 'EN_ATTENTE'): ?>
                                <?php if($b['type'] == 'Labo'): ?>
                                    <!-- Bouton pour voir les résultats de Labo (ouvre la liste des patients ou un résumé) -->
                                    <button class="btn btn-sm btn-primary rounded-pill px-3"
                                            onclick="alert('Résultats Labo : <?= addslashes($b['label']) ?> disponibles dans le dossier.')">
                                        <i class="bi bi-eye"></i> Voir
                                    </button>
                                <?php else: ?>
                                    <!-- Bouton pour ouvrir le Viewer Radio directement -->
                                    <a href="<?= BASE_URL ?>imagerie/viewer/<?= $b['record_id'] ?>"
                                       class="btn btn-sm btn-primary rounded-pill px-3">
                                        <i class="bi bi-eye"></i> Voir
                                    </a>
                                <?php endif; ?>
                            <?php else: ?>
                                <button class="btn btn-sm btn-light rounded-pill px-3 disabled" title="En attente de traitement">
                                    <i class="bi bi-hourglass"></i>
                                </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; else: ?>
                    <tr><td colspan="4" class="text-center py-4 text-muted small">Aucun bilan en cours.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

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