<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<!-- CHARGEMENT FORCE DES ICONES -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

<style>
    /* 1. RESET COMPLET POUR CETTE PAGE */
    .sidebar { display: none !important; }
    #wrapper, .main-wrapper { margin: 0 !important; padding: 0 !important; width: 100% !important; }

    main {
        margin-left: 0 !important;
        width: 100% !important;
        min-height: 100vh;
        background: #f4f7f9 !important;
        display: block !important;
        font-family: 'Segoe UI', Roboto, sans-serif;
    }

    /* 2. HEADER - ALIGNEMENT PARFAIT */
    .cockpit-header {
        background: #1a4a8e;
        height: 70px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0 25px;
        color: white;
        box-shadow: 0 2px 10px rgba(0,0,0,0.2);
    }

    #digital-clock {
        font-family: monospace;
        font-size: 2rem;
        font-weight: bold;
        color: #00ff41;
        text-shadow: 0 0 10px rgba(0,255,65,0.4);
    }

    .header-right {
        display: flex;
        align-items: center;
        gap: 15px;
    }

    .btn-new-admission {
        background: #dc3545;
        color: white;
        border: none;
        padding: 10px 20px;
        border-radius: 50px;
        font-weight: bold;
        display: flex;
        align-items: center;
        gap: 8px;
        text-decoration: none;
    }

    .btn-logout-circle {
        width: 45px;
        height: 45px;
        background: white;
        color: #dc3545;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        text-decoration: none;
        box-shadow: 0 2px 5px rgba(0,0,0,0.2);
    }

    /* 3. BARRE DE STATS - FORCE LE HORIZONTAL */
    .stats-container {
        display: flex;
        flex-direction: row; /* Force l'alignement en ligne */
        gap: 20px;
        padding: 25px;
        width: 100%;
    }

    .stat-box {
        background: white;
        flex: 1;
        padding: 20px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        gap: 15px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        border-bottom: 5px solid #ddd;
    }

    .stat-box.p1 { border-bottom-color: #dc3545; }
    .stat-box.p2 { border-bottom-color: #fd7e14; }
    .stat-box.wait { border-bottom-color: #0d6efd; }
    .stat-box.p3 { border-bottom-color: #198754; }

    .stat-icon-circle {
        width: 50px;
        height: 50px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
    }

    .stat-info .num { font-size: 2rem; font-weight: 800; display: block; line-height: 1; color: #222; }
    .stat-info .label { font-size: 0.75rem; font-weight: 700; color: #777; text-transform: uppercase; }

    /* 4. TABLEAU DE MONITORING */
    .table-container {
        margin: 0 25px;
        background: white;
        border-radius: 15px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.05);
        overflow: hidden;
        border: 1px solid #e0e0e0;
    }

    .table-emergency {
        width: 100%;
        border-collapse: collapse;
    }

    .table-emergency th {
        background: #f8f9fa;
        color: #1a4a8e;
        padding: 15px;
        text-align: left;
        font-size: 0.85rem;
        text-transform: uppercase;
        border-bottom: 2px solid #eee;
    }

    .table-emergency td {
        padding: 15px;
        border-bottom: 1px solid #f0f0f0;
        vertical-align: middle;
    }

    .vitals-row {
        display: flex;
        gap: 10px;
    }

    .v-block {
        background: #f8f9fa;
        padding: 5px 10px;
        border-radius: 5px;
        text-align: center;
        min-width: 70px;
        border: 1px solid #eee;
    }

    .v-block strong { display: block; font-size: 1rem; color: #333; }
    .v-block small { font-size: 0.6rem; color: #999; text-transform: uppercase; font-weight: bold; }

    .btn-examine {
        background: #1a4a8e;
        color: white;
        padding: 8px 15px;
        border-radius: 6px;
        text-decoration: none;
        font-weight: 600;
        font-size: 0.85rem;
    }

    .empty-msg { text-align: center; padding: 100px 0; color: #bbb; }
</style>

<main>
    <!-- HEADER -->
    <div class="cockpit-header">
        <div style="display: flex; align-items: center; gap: 15px;">
            <div style="background: white; padding: 5px; border-radius: 5px;">
                <img src="<?= BASE_URL ?>public/images/logo_ordre_malte.png" style="height: 40px;" onerror="this.src='https://placehold.co/40x40?text=H'">
            </div>
            <div>
                <h4 style="margin:0; font-weight:800; letter-spacing:1px;">COCKPIT <span style="color:#ffc107;">URGENCES</span></h4>
                <small style="opacity:0.8; font-size:0.7rem;">Hôpital St-Jean de Malte - Njombé</small>
            </div>
        </div>

        <div id="digital-clock">00:00:00</div>

        <div class="header-right">
            <button class="btn-new-admission" data-bs-toggle="modal" data-bs-target="#modalFastAdmission">
                <i class="bi bi-plus-circle-fill"></i> NOUVELLE ADMISSION
            </button>
            <a href="<?= BASE_URL ?>logout" class="btn-logout-circle" title="Déconnexion">
                <i class="bi bi-power fs-4"></i>
            </a>
        </div>
    </div>

    <!-- STATS -->
    <div class="stats-container">
        <div class="stat-box p1">
            <div class="stat-icon-circle" style="background:#fff5f5; color:#dc3545;"><i class="bi bi-exclamation-octagon-fill"></i></div>
            <div class="stat-info">
                <span class="num"><?= count(array_filter($admissions, fn($a) => strpos($a['niveau_priorite'], 'P1') !== false)) ?></span>
                <span class="label">P1 - Déchocage</span>
            </div>
        </div>
        <div class="stat-box p2">
            <div class="stat-icon-circle" style="background:#fff9f0; color:#fd7e14;"><i class="bi bi-lightning-charge-fill"></i></div>
            <div class="stat-info">
                <span class="num"><?= count(array_filter($admissions, fn($a) => strpos($a['niveau_priorite'], 'P2') !== false)) ?></span>
                <span class="label">P2 - Urgences</span>
            </div>
        </div>
        <div class="stat-box wait">
            <div class="stat-icon-circle" style="background:#eef4ff; color:#0d6efd;"><i class="bi bi-person-badge-fill"></i></div>
            <div class="stat-info">
                <span class="num"><?= $stats['waiting_med'] ?? 0 ?></span>
                <span class="label">Attente Médecin</span>
            </div>
        </div>
        <div class="stat-box p3">
            <div class="stat-icon-circle" style="background:#f0fff4; color:#198754;"><i class="bi bi-shield-check"></i></div>
            <div class="stat-info">
                <span class="num"><?= count(array_filter($admissions, fn($a) => strpos($a['niveau_priorite'], 'P3') !== false)) ?></span>
                <span class="label">P3 - Stables</span>
            </div>
        </div>
    </div>

    <!-- MONITORING TABLE -->
    <div class="table-container">
        <table class="table-emergency">
            <thead>
                <tr>
                    <th style="width:100px;">Tri</th>
                    <th>Patient</th>
                    <th>Monitorage / Constantes</th>
                    <th style="text-align:center;">Bilans</th>
                    <th>Présence</th>
                    <th style="text-align:right;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($admissions)): ?>
                    <tr>
                        <td colspan="6" class="empty-msg">
                            <i class="bi bi-person-add" style="font-size: 3rem;"></i>
                            <p>Aucun patient admis actuellement.</p>
                        </td>
                    </tr>
                <?php else: foreach($admissions as $adm):
                    $prio = substr($adm['niveau_priorite'], 0, 2);
                    $age = date_diff(date_create($adm['date_naissance']), date_create('now'))->y;
                ?>
                    <tr>
                        <td><span class="badge bg-<?= strtolower($prio) == 'p1' ? 'danger' : (strtolower($prio) == 'p2' ? 'warning' : 'success') ?> w-100 py-2"><?= $prio ?></span></td>
                        <td>
                            <strong><?= strtoupper($adm['nom']) ?> <?= $adm['prenom'] ?></strong><br>
                            <small class="text-muted"><?= $age ?> ans • <?= $adm['dossier_numero'] ?></small>
                        </td>
                        <td>
                            <div class="vitals-row">
                                <div class="v-block"><small>GCS</small><strong><?= $adm['score_glasgow'] ?></strong></div>
                                <div class="v-block"><small>TA</small><strong><?= $adm['tension_sys'] ?>/<?= $adm['tension_dia'] ?></strong></div>
                                <div class="v-block"><small>FC</small><strong class="text-danger"><?= $adm['pouls'] ?></strong></div>
                                <div class="v-block"><small>SpO2</small><strong class="text-info"><?= $adm['spo2'] ?>%</strong></div>
                            </div>
                        </td>
                        <td style="text-align:center;">
                            <?php if(($adm['nb_bilans_dispo'] ?? 0) > 0): ?>
                                <span class="badge bg-primary rounded-pill"><i class="bi bi-flask-fill"></i> <?= $adm['nb_bilans_dispo'] ?></span>
                            <?php else: ?>
                                <i class="bi bi-clock-history text-muted"></i>
                            <?php endif; ?>
                        </td>
                        <td>
                            <i class="bi bi-stopwatch me-1"></i>
                            <?php
                                $diff = date_diff(date_create($adm['date_entree']), date_create('now'));
                                echo ($diff->h > 0 ? $diff->h.'h ' : '') . $diff->i . 'm';
                            ?>
                        </td>
                        <td style="text-align:right;">
                            <a href="<?= BASE_URL ?>consultation/formulaire?patient_id=<?= $adm['patient_id'] ?>&type=EXTERNE&etape=1" class="btn-examine">
                                <i class="bi bi-clipboard-pulse"></i> EXAMINER
                            </a>
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
        <div class="modal-content" style="border-radius: 20px; border: none;">
            <div class="modal-header border-bottom-0 p-4">
                <h5 class="fw-bold"><i class="bi bi-person-plus-fill text-danger me-2"></i>Admission Urgence Rapide</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="<?= BASE_URL ?>urgences/save-single" method="POST">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-primary text-uppercase">Nom du Patient</label>
                        <input type="text" name="nom" class="form-control form-control-lg bg-light border-0" placeholder="Nom ou description" required>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <label class="form-label fw-bold small text-primary text-uppercase">Sexe</label>
                            <select name="sexe" class="form-select bg-light border-0">
                                <option value="M">Masculin</option><option value="F">Féminin</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-bold small text-primary text-uppercase">Âge Approx.</label>
                            <input type="number" name="age_approx" class="form-control bg-light border-0" placeholder="Ex: 30">
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top-0 p-4">
                    <button type="submit" class="btn btn-danger btn-lg w-100 fw-bold rounded-pill shadow">ADMETTRE IMMÉDIATEMENT</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function updateClock() {
        const now = new Date();
        document.getElementById('digital-clock').innerText = now.toLocaleTimeString('fr-FR');
    }
    setInterval(updateClock, 1000);
    updateClock();
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>