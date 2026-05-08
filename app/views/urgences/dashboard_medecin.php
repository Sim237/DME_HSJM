<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<link rel="stylesheet" href="<?= BASE_URL ?>public/css/bootstrap-icons.css">

<style>
    .sidebar { display: none !important; }
    #wrapper, .main-wrapper { margin: 0 !important; padding: 0 !important; width: 100% !important; }
    main { margin-left: 0 !important; width: 100% !important; min-height: 100vh; background: #f4f7f9 !important; }

    .cockpit-header {
        background: #1a4a8e; height: 70px;
        display: flex; justify-content: space-between; align-items: center;
        padding: 0 25px; color: white; box-shadow: 0 2px 10px rgba(0,0,0,0.2);
    }
    #digital-clock { font-family: monospace; font-size: 2rem; font-weight: bold; color: #00ff41; text-shadow: 0 0 10px rgba(0,255,65,0.4); }

    .stats-container { display: flex; flex-direction: row; gap: 20px; padding: 20px 25px; }
    .stat-box { background: white; flex: 1; padding: 18px 20px; border-radius: 12px;
        display: flex; align-items: center; gap: 15px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); border-bottom: 5px solid #ddd; }
    .stat-box.p1  { border-bottom-color: #dc3545; }
    .stat-box.p2  { border-bottom-color: #fd7e14; }
    .stat-box.wait{ border-bottom-color: #0d6efd; }
    .stat-box.p3  { border-bottom-color: #198754; }
    .stat-icon-circle { width: 50px; height: 50px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; }
    .stat-info .num  { font-size: 2rem; font-weight: 800; display: block; line-height: 1; color: #222; }
    .stat-info .label{ font-size: 0.72rem; font-weight: 700; color: #777; text-transform: uppercase; }

    .table-container { margin: 0 25px 30px; background: white; border-radius: 15px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.05); overflow: hidden; border: 1px solid #e0e0e0; }
    .table-emergency { width: 100%; border-collapse: collapse; }
    .table-emergency th { background: #f8f9fa; color: #1a4a8e; padding: 14px 15px; text-align: left;
        font-size: 0.8rem; text-transform: uppercase; border-bottom: 2px solid #eee; }
    .table-emergency td { padding: 14px 15px; border-bottom: 1px solid #f0f0f0; vertical-align: middle; }
    .table-emergency tr:hover td { background: #f8faff; }

    .vitals-row { display: flex; gap: 8px; flex-wrap: wrap; }
    .v-block { background: #f8f9fa; padding: 4px 8px; border-radius: 5px; text-align: center; min-width: 65px; border: 1px solid #eee; }
    .v-block strong { display: block; font-size: 0.9rem; color: #333; }
    .v-block small  { font-size: 0.58rem; color: #999; text-transform: uppercase; font-weight: bold; }

    .btn-examine { background: #1a4a8e; color: white; padding: 7px 14px; border-radius: 6px;
        text-decoration: none; font-weight: 600; font-size: 0.82rem; white-space: nowrap; }
    .btn-examine:hover { background: #163d78; color: white; }

    .prio-badge { display: inline-block; padding: 3px 10px; border-radius: 20px; font-size: 0.7rem; font-weight: 800; }
    .prio-1 { background:#fee2e2; color:#dc2626; }
    .prio-2 { background:#ffedd5; color:#ea580c; }
    .prio-3 { background:#fef9c3; color:#ca8a04; }
    .prio-4 { background:#dcfce7; color:#16a34a; }
    .prio-5 { background:#dbeafe; color:#2563eb; }

    .empty-msg { text-align: center; padding: 80px 0; color: #bbb; }
</style>

<main>
    <!-- HEADER COCKPIT -->
    <div class="cockpit-header">
        <div style="display:flex;align-items:center;gap:15px;">
            <div style="background:white;padding:5px 8px;border-radius:6px;">
                <img src="<?= BASE_URL ?>public/images/simcare_plus_logo.svg"
                     style="height:38px;width:auto;" alt="SimCare+">
            </div>
            <div>
                <h4 style="margin:0;font-weight:800;letter-spacing:1px;">COCKPIT <span style="color:#ffc107;">URGENCES</span></h4>
                <small style="opacity:0.7;font-size:0.68rem;">Dr <?= htmlspecialchars($_SESSION['user_nom'] ?? '') ?></small>
            </div>
        </div>

        <div id="digital-clock">00:00:00</div>

        <div style="display:flex;align-items:center;gap:12px;">
            <button class="btn btn-warning fw-bold rounded-pill px-4"
                    data-bs-toggle="modal" data-bs-target="#modalFastAdmission">
                <i class="bi bi-plus-circle-fill me-1"></i> NOUVELLE ADMISSION
            </button>
            <a href="<?= BASE_URL ?>logout" style="width:42px;height:42px;background:white;color:#dc3545;border-radius:50%;display:flex;align-items:center;justify-content:center;text-decoration:none;" title="Déconnexion">
                <i class="bi bi-power fs-5"></i>
            </a>
        </div>
    </div>

    <!-- STATS -->
    <div class="stats-container">
        <div class="stat-box p1">
            <div class="stat-icon-circle" style="background:#fff5f5;color:#dc3545;"><i class="bi bi-exclamation-octagon-fill"></i></div>
            <div class="stat-info"><span class="num"><?= $stats['P1'] ?></span><span class="label">P1 - Déchocage</span></div>
        </div>
        <div class="stat-box p2">
            <div class="stat-icon-circle" style="background:#fff9f0;color:#fd7e14;"><i class="bi bi-lightning-charge-fill"></i></div>
            <div class="stat-info"><span class="num"><?= $stats['P2'] ?></span><span class="label">P2 - Urgences</span></div>
        </div>
        <div class="stat-box wait">
            <div class="stat-icon-circle" style="background:#eef4ff;color:#0d6efd;"><i class="bi bi-person-badge-fill"></i></div>
            <div class="stat-info"><span class="num"><?= $stats['waiting_med'] ?></span><span class="label">Sans tri IAO</span></div>
        </div>
        <div class="stat-box p3">
            <div class="stat-icon-circle" style="background:#f0fff4;color:#198754;"><i class="bi bi-shield-check"></i></div>
            <div class="stat-info"><span class="num"><?= $stats['P3'] ?></span><span class="label">P3+ Stables</span></div>
        </div>
    </div>

    <!-- MONITORING TABLE -->
    <div class="table-container">
        <table class="table-emergency">
            <thead>
                <tr>
                    <th style="width:90px;">Triage</th>
                    <th>Patient</th>
                    <th>Constantes IAO</th>
                    <th style="text-align:center;">Bilans</th>
                    <th>Présence</th>
                    <th>Médecin</th>
                    <th style="text-align:right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($admissions)): ?>
                    <tr>
                        <td colspan="7" class="empty-msg">
                            <i class="bi bi-person-add" style="font-size:3rem;"></i>
                            <p class="mt-2">Aucun patient aux urgences actuellement.</p>
                        </td>
                    </tr>
                <?php else: foreach($admissions as $adm):
                    $n   = (int)($adm['niveau_triage'] ?? 3);
                    $age = !empty($adm['date_naissance'])
                           ? date_diff(date_create($adm['date_naissance']), date_create('now'))->y
                           : '?';
                    $duree = '--';
                    if (!empty($adm['heure_arrivee'])) {
                        $diff  = date_diff(date_create($adm['heure_arrivee']), date_create('now'));
                        $duree = ($diff->h > 0 ? $diff->h . 'h ' : '') . $diff->i . 'm';
                    }
                ?>
                    <tr>
                        <td>
                            <span class="prio-badge prio-<?= $n ?>">
                                <?= htmlspecialchars($adm['niveau_label'] ?? 'P3') ?>
                            </span>
                        </td>
                        <td>
                            <strong><?= htmlspecialchars(strtoupper($adm['nom']) . ' ' . $adm['prenom']) ?></strong><br>
                            <small class="text-muted"><?= $age ?> ans &bull; <?= htmlspecialchars($adm['dossier_numero'] ?? '') ?></small>
                            <?php if (!empty($adm['motif_plainte'])): ?>
                                <br><small class="text-danger"><?= htmlspecialchars(substr($adm['motif_plainte'], 0, 50)) ?></small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="vitals-row">
                                <div class="v-block"><small>GCS</small><strong><?= $adm['score_glasgow'] ?? '--' ?></strong></div>
                                <div class="v-block"><small>TA</small><strong><?= ($adm['tension_sys'] ?? '--') . '/' . ($adm['tension_dia'] ?? '--') ?></strong></div>
                                <div class="v-block"><small>FC</small><strong class="text-danger"><?= $adm['pouls'] ?? '--' ?></strong></div>
                                <div class="v-block"><small>SpO2</small><strong class="text-info"><?= $adm['spo2'] ? $adm['spo2'] . '%' : '--' ?></strong></div>
                            </div>
                        </td>
                        <td style="text-align:center;">
                            <?php if (($adm['nb_bilans_dispo'] ?? 0) > 0): ?>
                                <span class="badge bg-primary rounded-pill">
                                    <i class="bi bi-flask-fill"></i> <?= $adm['nb_bilans_dispo'] ?>
                                </span>
                            <?php else: ?>
                                <i class="bi bi-clock-history text-muted"></i>
                            <?php endif; ?>
                        </td>
                        <td>
                            <i class="bi bi-stopwatch me-1 text-muted"></i><?= $duree ?>
                        </td>
                        <td>
                            <?php if (!empty($adm['medecin_nom'])): ?>
                                <small class="text-success"><i class="bi bi-person-check-fill me-1"></i>Dr <?= htmlspecialchars($adm['medecin_nom']) ?></small>
                            <?php else: ?>
                                <small class="text-warning"><i class="bi bi-clock me-1"></i>Non assigné</small>
                            <?php endif; ?>
                        </td>
                        <td style="text-align:right;">
                            <div class="d-flex gap-1 justify-content-end">
                                <a href="<?= BASE_URL ?>consultation/formulaire?patient_id=<?= $adm['patient_id'] ?>&type=EXTERNE&etape=1&urgence_id=<?= $adm['id'] ?>"
                                   class="btn-examine">
                                    <i class="bi bi-clipboard-pulse"></i> Examiner
                                </a>
                                <button class="btn btn-sm btn-outline-secondary rounded"
                                        onclick="ouvrirOrientation(<?= $adm['id'] ?>, '<?= htmlspecialchars(addslashes($adm['nom'] . ' ' . $adm['prenom'])) ?>')"
                                        title="Orienter / Sortie">
                                    <i class="bi bi-arrow-right-circle"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</main>

<!-- MODAL ADMISSION RAPIDE -->
<div class="modal fade" id="modalFastAdmission" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius:20px;border:none;">
            <div class="modal-header border-0 p-4">
                <h5 class="fw-bold"><i class="bi bi-person-plus-fill text-danger me-2"></i>Admission Urgence Rapide</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="<?= BASE_URL ?>urgences/save-single" method="POST">
                <div class="modal-body p-4 pt-0">
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-uppercase">Nom du Patient</label>
                        <input type="text" name="nom" class="form-control form-control-lg bg-light border-0" placeholder="Nom (ou INCONNU)" required>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-4">
                            <label class="form-label fw-bold small text-uppercase">Prénom</label>
                            <input type="text" name="prenom" class="form-control bg-light border-0" placeholder="Prénom">
                        </div>
                        <div class="col-4">
                            <label class="form-label fw-bold small text-uppercase">Sexe</label>
                            <select name="sexe" class="form-select bg-light border-0">
                                <option value="M">Masculin</option>
                                <option value="F">Féminin</option>
                            </select>
                        </div>
                        <div class="col-4">
                            <label class="form-label fw-bold small text-uppercase">Âge ~</label>
                            <input type="number" name="age_approx" class="form-control bg-light border-0" placeholder="30">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-uppercase">Motif d'admission</label>
                        <textarea name="motif" class="form-control bg-light border-0" rows="2" placeholder="Motif principal..."></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <button type="submit" class="btn btn-danger btn-lg w-100 fw-bold rounded-pill shadow">
                        ADMETTRE IMMÉDIATEMENT
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL ORIENTATION / SORTIE -->
<div class="modal fade" id="modalOrientation" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius:20px;border:none;">
            <div class="modal-header border-0 p-4">
                <h5 class="fw-bold"><i class="bi bi-arrow-right-circle-fill text-primary me-2"></i>Orientation Patient</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="<?= BASE_URL ?>urgences/transferer" method="POST">
                <input type="hidden" name="admission_id" id="orientAdmId">
                <div class="modal-body p-4 pt-0">
                    <p class="text-muted mb-4">Patient : <strong id="orientPatientName"></strong></p>

                    <div class="mb-4">
                        <label class="form-label fw-bold">Décision</label>
                        <div class="d-grid gap-2">
                            <div class="form-check border rounded-3 p-3">
                                <input class="form-check-input" type="radio" name="decision" id="decSortie" value="SORTIE" checked onchange="toggleService(false)">
                                <label class="form-check-label fw-bold" for="decSortie">
                                    <i class="bi bi-door-open-fill me-2 text-success"></i> Sortie / Retour domicile
                                </label>
                            </div>
                            <div class="form-check border rounded-3 p-3">
                                <input class="form-check-input" type="radio" name="decision" id="decHosp" value="HOSPITALISATION" onchange="toggleService(true)">
                                <label class="form-check-label fw-bold" for="decHosp">
                                    <i class="bi bi-hospital-fill me-2 text-warning"></i> Hospitalisation
                                </label>
                            </div>
                            <div class="form-check border rounded-3 p-3">
                                <input class="form-check-input" type="radio" name="decision" id="decConsult" value="CONSULTER" onchange="toggleService(true)">
                                <label class="form-check-label fw-bold" for="decConsult">
                                    <i class="bi bi-person-lines-fill me-2 text-primary"></i> Référer consultation externe
                                </label>
                            </div>
                        </div>
                    </div>

                    <div id="serviceBlock" style="display:none;" class="mb-3">
                        <label class="form-label fw-bold">Service de destination</label>
                        <select name="service_id" class="form-select">
                            <?php
                            $servicesOrient = (new Database())->getConnection()
                                ->query("SELECT id, nom FROM services ORDER BY nom")->fetchAll(PDO::FETCH_ASSOC);
                            foreach ($servicesOrient as $svc):
                            ?>
                                <option value="<?= $svc['id'] ?>"><?= htmlspecialchars($svc['nom']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Notes / Compte rendu</label>
                        <textarea name="notes" class="form-control" rows="2" placeholder="Informations pour l'équipe de réception..."></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <button type="submit" class="btn btn-primary btn-lg w-100 fw-bold rounded-pill shadow">
                        VALIDER L'ORIENTATION
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function updateClock() {
    document.getElementById('digital-clock').innerText = new Date().toLocaleTimeString('fr-FR');
}
setInterval(updateClock, 1000);
updateClock();

function ouvrirOrientation(admId, nom) {
    document.getElementById('orientAdmId').value   = admId;
    document.getElementById('orientPatientName').textContent = nom;
    document.getElementById('decSortie').checked   = true;
    document.getElementById('serviceBlock').style.display = 'none';
    new bootstrap.Modal(document.getElementById('modalOrientation')).show();
}

function toggleService(show) {
    document.getElementById('serviceBlock').style.display = show ? 'block' : 'none';
}
</script>
<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
