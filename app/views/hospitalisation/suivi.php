<?php
require_once __DIR__ . '/../layouts/header.php';
$dossier = $dossier ?? [];
$dernieres = $dernieres_constantes ?? [];
?>


<!-- Inclusion de Chart.js pour les graphiques -->
<!--<script src="<?= BASE_URL ?>public/js/chart.umd.min.js"></script>-->

<script src="<?= BASE_URL ?>public/js/chart.umd.js"></script>

<style>
/* Style pour les valeurs anormales */
.vitals-warning {
    background-color: #fffbeb !important;
    border-color: #f59e0b !important;
    animation: pulse-orange 2s infinite;
}

.vitals-critical {
    background-color: #fee2e2 !important;
    border-color: #ef4444 !important;
    animation: pulse-red 2s infinite;
}

@keyframes pulse-red {
    0% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.7); }
    70% { box-shadow: 0 0 0 10px rgba(239, 68, 68, 0); }
    100% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0); }
}

@keyframes pulse-orange {
    0% { box-shadow: 0 0 0 0 rgba(245, 158, 11, 0.7); }
    70% { box-shadow: 0 0 0 10px rgba(245, 158, 11, 0); }
    100% { box-shadow: 0 0 0 0 rgba(245, 158, 11, 0); }
}

</style>

<div class="container-fluid">
    <div class="row">
        <?php require_once __DIR__ . '/../layouts/sidebar.php'; ?>

        <main class="col-md-10 ms-sm-auto px-md-4 mb-5">

            <!-- En-tête Patient -->
            <div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom">
                <div>
                    <h1 class="h2"><i class="bi bi-heart-pulse"></i> Suivi Hospitalisation</h1>
                    <h5 class="text-primary">
                        <?= htmlspecialchars($dossier['nom'] . ' ' . $dossier['prenom']) ?>
                        <span class="text-muted text-small">| Dossier <?= htmlspecialchars($dossier['dossier_numero']) ?></span>
                    </h5>
                </div>
                <div class="text-end">
                    <span class="badge bg-success fs-6 mb-1">
    <?= htmlspecialchars($dossier['service_nom'] ?? 'Service non défini') ?>
                    </span><br>
                    <span class="badge bg-secondary">Lit <?= htmlspecialchars($dossier['lit_numero']) ?></span>
                </div>
            </div>

            <!-- BARRE DE STATUT (Dernières constantes) -->
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <div class="card bg-dark text-white h-100">
                        <div class="card-body d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-subtitle mb-2 text-muted">Température</h6>
                                <h2 class="card-title mb-0"><?= $dernieres['temperature'] ?? '--' ?>°C</h2>
                            </div>
                            <i class="bi bi-thermometer-half fs-1 text-danger"></i>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-dark text-white h-100">
                        <div class="card-body d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-subtitle mb-2 text-muted">Tension Artérielle</h6>
                                <!-- Dans app/views/hospitalisation/suivi.php -->
<h2 class="card-title mb-0">
    <?= (isset($dernieres['pression_arterielle_systolique']) && $dernieres['pression_arterielle_systolique'] > 0)
        ? $dernieres['pression_arterielle_systolique'] . '/' . $dernieres['pression_arterielle_diastolique']
        : '--/--' ?>
</h2>
                            </div>
                            <i class="bi bi-activity fs-1 text-info"></i>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-dark text-white h-100">
                        <div class="card-body d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-subtitle mb-2 text-muted">Fréquence Cardiaque</h6>
                                <h2 class="card-title mb-0"><?= $dernieres['frequence_cardiaque'] ?? '--' ?> <small class="fs-6">bpm</small></h2>
                            </div>
                            <i class="bi bi-heart-fill fs-1 text-danger"></i>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-primary h-100" style="border-style: dashed;">
                        <div class="card-body d-flex align-items-center justify-content-center cursor-pointer"
                             data-bs-toggle="modal" data-bs-target="#modalAddConstante" style="cursor: pointer;">
                            <div class="text-center text-primary">
                                <i class="bi bi-plus-circle fs-2"></i><br>
                                <span>Nouvelle Prise</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mb-4 no-print">
    <div class="col-12">
        <div class="card p-3 shadow-sm border-0 bg-light">
            <div class="d-flex gap-2 flex-wrap">
                <a href="<?= BASE_URL ?>dashboard" class="btn btn-outline-secondary rounded-pill px-3">
                    <i class="bi bi-house-door"></i> Tableau de bord
                </a>
                <button class="btn btn-outline-primary rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#modalEditPatient">
                    <i class="bi bi-person-fill-gear"></i> Modifier le patient
                </button>
                <div class="vr mx-1"></div>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalAddConstante">
                    <i class="bi bi-plus-circle"></i> Ajouter Constantes
                </button>
                <button class="btn btn-info text-white" data-bs-toggle="modal" data-bs-target="#modalAddSoin">
                    <i class="bi bi-calendar-plus"></i> Ajouter Soin
                </button>
                <a href="<?= BASE_URL ?>hospitalisation/observations-evolution/<?= htmlspecialchars($patient['id']) ?>"
                   class="btn btn-dark rounded-pill px-3">
                    <i class="bi bi-pencil-square"></i> Note d'évolution
                </a>
                <div class="vr mx-1"></div>
                <a href="<?= BASE_URL ?>hospitalisation/surveillance-intensive/<?= htmlspecialchars($patient['id']) ?>"
                   class="btn btn-danger rounded-pill px-3">
                    <i class="bi bi-clipboard2-pulse"></i> Fiche S.I.
                </a>
                <a href="<?= BASE_URL ?>hospitalisation/fiche-transfusionnelle/<?= htmlspecialchars($patient['id']) ?>"
                   class="btn btn-warning rounded-pill px-3">
                    <i class="bi bi-droplet-half"></i> Fiche Transfusionnelle
                </a>
            </div>
        </div>
    </div>
</div>

            <!-- GRAPHIQUES -->
            <div class="row g-3 mb-4">
                <!-- Graphe Température -->
                <div class="col-md-6">
                    <div class="card shadow-sm">
                        <div class="card-header bg-white d-flex justify-content-between">
                            <h6 class="mb-0 fw-bold text-danger"><i class="bi bi-thermometer-high"></i> Évolution Température</h6>
                        </div>
                        <div class="card-body">
                            <canvas id="chartTemp" height="200"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Graphe Tension -->
                <div class="col-md-6">
                    <div class="card shadow-sm">
                        <div class="card-header bg-white">
                            <h6 class="mb-0 fw-bold text-info"><i class="bi bi-activity"></i> Évolution Tension Artérielle</h6>
                        </div>
                        <div class="card-body">
                            <canvas id="chartTension" height="200"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- GESTION DES SOINS -->
            <div class="row">
                <div class="col-md-12">
                    <div class="card shadow-sm">
                        <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                            <h5 class="mb-0"><i class="bi bi-journal-medical"></i> Planning des Soins</h5>
                            <!-- Bouton dans la vue suivi.php -->
<a href="<?= BASE_URL ?>hospitalisation/planifier-soins/<?= htmlspecialchars($dossier['patient_id'] ?? $patient['id']) ?>"
   class="btn btn-primary btn-sm">
    <i class="bi bi-calendar-plus"></i> Planifier un Soin
</a>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Heure</th>
                                            <th>Type de Soin</th>
                                            <th>Description</th>
                                            <th>Statut</th>
                                            <th class="text-end">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if(empty($soins_du_jour) && empty($tous_les_soins)): ?>
                                            <tr><td colspan="5" class="text-center py-4 text-muted">Aucun soin planifié</td></tr>
                                        <?php else: ?>
                                            <?php foreach($tous_les_soins as $soin): ?>
                                            <tr class="<?= $soin['statut'] == 'REALISE' ? 'table-success' : '' ?>">
                                                <td>
                                                    <strong><?= date('H:i', strtotime($soin['date_prevue'])) ?></strong><br>
                                                    <small class="text-muted"><?= date('d/m', strtotime($soin['date_prevue'])) ?></small>
                                                </td>
                                                <td>
                                                    <span class="badge bg-info text-dark"><?= htmlspecialchars($soin['type_soin']) ?></span>
                                                </td>
                                                <td><?= htmlspecialchars($soin['description']) ?></td>
                                                <td>
                                                    <?php if($soin['statut'] == 'PLANIFIE'): ?>
                                                        <span class="badge bg-warning text-dark">À FAIRE</span>
                                                    <?php elseif($soin['statut'] == 'REALISE'): ?>
                                                        <span class="badge bg-success">FAIT</span>
                                                        <small class="d-block text-muted" style="font-size: 0.7em">le <?= date('d/m H:i', strtotime($soin['date_realisee'])) ?></small>
                                                    <?php else: ?>
                                                        <span class="badge bg-secondary"><?= $soin['statut'] ?></span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-end">
                                                    <?php if($soin['statut'] == 'PLANIFIE'): ?>
                                                    <button class="btn btn-sm btn-success" onclick="validerSoin(<?= $soin['id'] ?>)">
                                                        <i class="bi bi-check-lg"></i> Valider
                                                    </button>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SECTION SURVEILLANCE INTENSIVE (SI DONNÉES DISPONIBLES) -->
            <?php if (!empty($si_data)): ?>
            <div class="row mt-4">
                <div class="col-12">
                    <div class="card shadow-sm border-danger">
                        <div class="card-header bg-danger text-white d-flex justify-content-between align-items-center py-3">
                            <h5 class="mb-0"><i class="bi bi-clipboard2-pulse"></i> Surveillance Intensive</h5>
                            <div class="d-flex gap-2 align-items-center">
                                <span class="badge bg-white text-danger"><?= count($si_data) ?> observation(s)</span>
                                <a href="<?= BASE_URL ?>hospitalisation/surveillance-intensive/<?= htmlspecialchars($patient['id']) ?>"
                                   class="btn btn-sm btn-outline-light rounded-pill">
                                    <i class="bi bi-plus-circle"></i> Nouvelle observation
                                </a>
                            </div>
                        </div>
                        <div class="card-body">

                            <!-- Graphiques SI -->
                            <div class="row g-3 mb-4">
                                <div class="col-md-4">
                                    <div class="card border-0 shadow-sm">
                                        <div class="card-header bg-white py-2">
                                            <small class="fw-bold text-danger"><i class="bi bi-thermometer-half"></i> Température (°C)</small>
                                        </div>
                                        <div class="card-body p-2">
                                            <canvas id="chartSITemp" height="180"></canvas>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card border-0 shadow-sm">
                                        <div class="card-header bg-white py-2">
                                            <small class="fw-bold text-primary"><i class="bi bi-heart-pulse"></i> Pouls (bpm)</small>
                                        </div>
                                        <div class="card-body p-2">
                                            <canvas id="chartSIPouls" height="180"></canvas>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card border-0 shadow-sm">
                                        <div class="card-header bg-white py-2">
                                            <small class="fw-bold text-info"><i class="bi bi-activity"></i> Tension Artérielle</small>
                                        </div>
                                        <div class="card-body p-2">
                                            <canvas id="chartSITA" height="180"></canvas>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Tableau SI -->
                            <div class="table-responsive">
                                <table class="table table-sm table-hover align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Date / Heure</th>
                                            <th>TA</th>
                                            <th>Pouls</th>
                                            <th>T°</th>
                                            <th>Resp.</th>
                                            <th>Diurèse</th>
                                            <th>Conscience</th>
                                            <th>Aspiration</th>
                                            <th>Observations</th>
                                            <th>Staff</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($si_data as $obs): ?>
                                        <tr>
                                            <td>
                                                <strong><?= htmlspecialchars($obs['date_obs'] ?? '') ?></strong>
                                                <small class="text-muted d-block"><?= htmlspecialchars($obs['heure_obs'] ?? '') ?></small>
                                            </td>
                                            <td><?= htmlspecialchars($obs['ta'] ?? '--') ?></td>
                                            <td>
                                                <?php $p = $obs['pouls'] ?? null; ?>
                                                <?php if ($p): ?>
                                                    <span class="<?= ($p > 110 || $p < 50) ? 'text-danger fw-bold' : ($p > 95 ? 'text-warning fw-bold' : '') ?>">
                                                        <?= $p ?>
                                                    </span>
                                                <?php else: ?>--<?php endif; ?>
                                            </td>
                                            <td>
                                                <?php $t = $obs['temperature'] ?? null; ?>
                                                <?php if ($t): ?>
                                                    <span class="<?= ($t >= 38.5 || $t <= 35.5) ? 'text-danger fw-bold' : ($t >= 37.8 ? 'text-warning fw-bold' : '') ?>">
                                                        <?= $t ?>°
                                                    </span>
                                                <?php else: ?>--<?php endif; ?>
                                            </td>
                                            <td><?= htmlspecialchars($obs['respiration'] ?? '--') ?></td>
                                            <td><?= htmlspecialchars($obs['diurese'] ?? '--') ?></td>
                                            <td>
                                                <?php $c = $obs['conscience'] ?? ''; ?>
                                                <?php $cBadge = ['A' => 'success', 'V' => 'warning', 'P' => 'orange', 'U' => 'danger']; ?>
                                                <span class="badge bg-<?= $cBadge[$c] ?? 'secondary' ?>"><?= htmlspecialchars($c ?: '--') ?></span>
                                            </td>
                                            <td><?= htmlspecialchars($obs['aspiration'] ?? '--') ?></td>
                                            <td style="max-width: 200px; font-size: 0.82rem;"><?= htmlspecialchars($obs['observations'] ?? '') ?></td>
                                            <td><span class="badge bg-secondary"><?= htmlspecialchars($obs['staff'] ?? '') ?></span></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>

                        </div>
                    </div>
                </div>
            </div>

            <script>
            (function() {
                const siData = <?= json_encode(array_reverse($si_data)) ?>;
                const siLabels = siData.map(d => (d.date_obs || '') + ' ' + (d.heure_obs || ''));

                // Temp chart
                new Chart(document.getElementById('chartSITemp'), {
                    type: 'line',
                    data: {
                        labels: siLabels,
                        datasets: [{
                            label: 'T° (°C)',
                            data: siData.map(d => d.temperature),
                            borderColor: '#dc3545',
                            backgroundColor: 'rgba(220,53,69,0.08)',
                            tension: 0.3, fill: true, pointRadius: 4
                        }]
                    },
                    options: {
                        responsive: true, plugins: { legend: { display: false } },
                        scales: { y: { min: 35, max: 42, title: { display: true, text: '°C' } } }
                    }
                });

                // Pouls chart
                new Chart(document.getElementById('chartSIPouls'), {
                    type: 'line',
                    data: {
                        labels: siLabels,
                        datasets: [{
                            label: 'Pouls (bpm)',
                            data: siData.map(d => d.pouls),
                            borderColor: '#0d6efd',
                            backgroundColor: 'rgba(13,110,253,0.08)',
                            tension: 0.3, fill: true, pointRadius: 4
                        }]
                    },
                    options: {
                        responsive: true, plugins: { legend: { display: false } },
                        scales: { y: { title: { display: true, text: 'bpm' } } }
                    }
                });

                // TA chart (parse "120/80" → systolique)
                const taSys = siData.map(d => {
                    if (!d.ta) return null;
                    const parts = d.ta.toString().split('/');
                    return parts[0] ? parseInt(parts[0]) : null;
                });
                const taDia = siData.map(d => {
                    if (!d.ta) return null;
                    const parts = d.ta.toString().split('/');
                    return parts[1] ? parseInt(parts[1]) : null;
                });
                new Chart(document.getElementById('chartSITA'), {
                    type: 'line',
                    data: {
                        labels: siLabels,
                        datasets: [
                            { label: 'Systolique', data: taSys, borderColor: '#0dcaf0', tension: 0.3, pointRadius: 4 },
                            { label: 'Diastolique', data: taDia, borderColor: '#0d6efd', tension: 0.3, pointRadius: 4 }
                        ]
                    },
                    options: {
                        responsive: true,
                        scales: { y: { title: { display: true, text: 'mmHg' } } }
                    }
                });
            })();
            </script>
            <?php endif; ?>

        </main>
    </div>
</div>

<?php if (isset($_GET['success']) && $_GET['success'] === 'patient_maj'): ?>
<div class="alert alert-success alert-dismissible fade show position-fixed top-0 end-0 m-3 shadow" style="z-index:9999" role="alert">
    <i class="bi bi-check-circle-fill me-2"></i>Données du patient mises à jour avec succès.
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>
<?php if (isset($_GET['error'])): ?>
<div class="alert alert-danger alert-dismissible fade show position-fixed top-0 end-0 m-3 shadow" style="z-index:9999" role="alert">
    <i class="bi bi-exclamation-triangle-fill me-2"></i>
    <?= $_GET['error'] === 'champs_requis' ? 'Nom et prénom obligatoires.' : 'Une erreur est survenue.' ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- ═══ Modal : Modifier les données personnelles du patient ═══ -->
<div class="modal fade" id="modalEditPatient" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <form action="<?= BASE_URL ?>hospitalisation/update-patient" method="POST">
                <input type="hidden" name="patient_id" value="<?= (int)($patient['id']) ?>">

                <div class="modal-header text-white" style="background:#0d6efd;">
                    <h5 class="modal-title fw-bold">
                        <i class="bi bi-person-fill-gear me-2"></i>Modifier les données du patient
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body p-4">

                    <!-- Identité -->
                    <p class="fw-bold text-primary mb-3" style="font-size:.8rem;text-transform:uppercase;letter-spacing:.06em;">
                        <i class="bi bi-person-vcard me-1"></i>Identité
                    </p>
                    <div class="row g-3 mb-4">
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Nom <span class="text-danger">*</span></label>
                            <input type="text" name="nom" class="form-control" required
                                   value="<?= htmlspecialchars($patient['nom'] ?? '') ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Prénom <span class="text-danger">*</span></label>
                            <input type="text" name="prenom" class="form-control" required
                                   value="<?= htmlspecialchars($patient['prenom'] ?? '') ?>">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-semibold">Date de naissance</label>
                            <input type="date" name="date_naissance" class="form-control"
                                   value="<?= htmlspecialchars($patient['date_naissance'] ?? '') ?>">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-semibold">Sexe</label>
                            <select name="sexe" class="form-select">
                                <option value="M" <?= ($patient['sexe'] ?? '') === 'M' ? 'selected' : '' ?>>Masculin</option>
                                <option value="F" <?= ($patient['sexe'] ?? '') === 'F' ? 'selected' : '' ?>>Féminin</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-semibold">Groupe sanguin</label>
                            <select name="groupe_sanguin" class="form-select">
                                <option value="">— Inconnu —</option>
                                <?php foreach (['A+','A-','B+','B-','AB+','AB-','O+','O-'] as $gs): ?>
                                <option value="<?= $gs ?>" <?= ($patient['groupe_sanguin'] ?? '') === $gs ? 'selected' : '' ?>><?= $gs ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Profession</label>
                            <input type="text" name="profession" class="form-control" placeholder="Optionnel"
                                   value="<?= htmlspecialchars($patient['profession'] ?? '') ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Nationalité</label>
                            <input type="text" name="nationalite" class="form-control" placeholder="Optionnel"
                                   value="<?= htmlspecialchars($patient['nationalite'] ?? '') ?>">
                        </div>
                    </div>

                    <!-- Coordonnées -->
                    <p class="fw-bold text-primary mb-3" style="font-size:.8rem;text-transform:uppercase;letter-spacing:.06em;">
                        <i class="bi bi-telephone me-1"></i>Coordonnées
                    </p>
                    <div class="row g-3 mb-4">
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Téléphone</label>
                            <input type="tel" name="telephone" class="form-control"
                                   value="<?= htmlspecialchars($patient['telephone'] ?? '') ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Email</label>
                            <input type="email" name="email" class="form-control"
                                   value="<?= htmlspecialchars($patient['email'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Adresse</label>
                            <input type="text" name="adresse" class="form-control"
                                   value="<?= htmlspecialchars($patient['adresse'] ?? '') ?>">
                        </div>
                    </div>

                    <!-- Contact d'urgence -->
                    <p class="fw-bold text-warning mb-3" style="font-size:.8rem;text-transform:uppercase;letter-spacing:.06em;">
                        <i class="bi bi-person-lines-fill me-1"></i>Contact d'urgence
                    </p>
                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Nom du contact</label>
                            <input type="text" name="contact_nom" class="form-control"
                                   value="<?= htmlspecialchars($patient['contact_nom'] ?? '') ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Téléphone du contact</label>
                            <input type="tel" name="contact_telephone" class="form-control"
                                   value="<?= htmlspecialchars($patient['contact_telephone'] ?? '') ?>">
                        </div>
                    </div>

                    <!-- Antécédents & Allergies -->
                    <p class="fw-bold text-danger mb-3" style="font-size:.8rem;text-transform:uppercase;letter-spacing:.06em;">
                        <i class="bi bi-clipboard2-heart me-1"></i>Antécédents médicaux &amp; Allergies
                    </p>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Antécédents médicaux</label>
                            <textarea name="antecedents_medicaux" class="form-control" rows="4"
                                      placeholder="HTA, diabète, chirurgies antérieures…"><?= htmlspecialchars($patient['antecedents_medicaux'] ?? '') ?></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Allergies connues</label>
                            <textarea name="allergies" class="form-control" rows="4"
                                      placeholder="Pénicilline, AINS, latex…"><?= htmlspecialchars($patient['allergies'] ?? '') ?></textarea>
                        </div>
                    </div>

                </div><!-- /modal-body -->

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary fw-bold px-4">
                        <i class="bi bi-check2-circle me-2"></i>Enregistrer les modifications
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Ajout Constantes -->
<div class="modal fade" id="modalAddConstante" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="<?= BASE_URL ?>hospitalisation/add-constantes" method="POST">
                <input type="hidden" name="admission_id" value="<?= htmlspecialchars($dossier['id'] ?? '0') ?>">
                <input type="hidden" name="patient_id" value="<?= htmlspecialchars($dossier['patient_id'] ?? '0') ?>">

                <div class="modal-header text-white" style="background:#2563eb;">
                    <h5 class="modal-title"><i class="fas fa-heartbeat me-2"></i>Fiche de Paramètres</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">

                    <!-- SECTION 1 : Signes vitaux classiques -->
                    <p class="fw-semibold text-primary mb-2" style="font-size:.8rem;letter-spacing:.06em;text-transform:uppercase;">Signes Vitaux</p>
                    <div class="row g-3 mb-3">
                        <div class="col-6 col-md-3">
                            <label class="form-label form-label-sm">Température (°C)</label>
                            <input type="number" step="0.1" min="34" max="42" class="form-control form-control-sm" name="temperature" placeholder="37.0" required>
                        </div>
                        <div class="col-6 col-md-3">
                            <label class="form-label form-label-sm">Pouls (bpm)</label>
                            <input type="number" min="20" max="300" class="form-control form-control-sm" name="frequence_cardiaque" placeholder="80">
                        </div>
                        <div class="col-6 col-md-3">
                            <label class="form-label form-label-sm">TA Systolique (mmHg)</label>
                            <input type="number" min="50" max="300" class="form-control form-control-sm" name="tension_sys" placeholder="120">
                        </div>
                        <div class="col-6 col-md-3">
                            <label class="form-label form-label-sm">TA Diastolique (mmHg)</label>
                            <input type="number" min="30" max="200" class="form-control form-control-sm" name="tension_dia" placeholder="80">
                        </div>
                        <div class="col-6 col-md-3">
                            <label class="form-label form-label-sm">SpO2 (%)</label>
                            <input type="number" min="50" max="100" class="form-control form-control-sm" name="spo2" placeholder="98">
                        </div>
                        <div class="col-6 col-md-3">
                            <label class="form-label form-label-sm">Fréquence Resp. (/min)</label>
                            <input type="number" min="5" max="60" class="form-control form-control-sm" name="frequence_respiratoire" placeholder="16">
                        </div>
                    </div>

                    <hr class="my-3">

                    <!-- SECTION 2 : Biologie rapide -->
                    <p class="fw-semibold text-primary mb-2" style="font-size:.8rem;letter-spacing:.06em;text-transform:uppercase;">Biologie au Lit</p>
                    <div class="row g-3 mb-3">
                        <div class="col-6 col-md-4">
                            <label class="form-label form-label-sm">Glycémie (g/L)</label>
                            <input type="number" step="0.01" min="0" max="30" class="form-control form-control-sm" name="glycemie" placeholder="0.90">
                        </div>
                        <div class="col-6 col-md-4">
                            <label class="form-label form-label-sm">Diurèse (mL/24h)</label>
                            <input type="number" min="0" max="10000" class="form-control form-control-sm" name="diurese" placeholder="1500">
                        </div>
                    </div>

                    <hr class="my-3">

                    <!-- SECTION 3 : Oxygénothérapie -->
                    <p class="fw-semibold text-primary mb-2" style="font-size:.8rem;letter-spacing:.06em;text-transform:uppercase;">Oxygénothérapie</p>
                    <div class="row g-3 mb-3 align-items-center">
                        <div class="col-auto">
                            <div class="form-check form-switch mt-1">
                                <input class="form-check-input" type="checkbox" id="toggleOxygene" name="sous_oxygene" value="1" onchange="toggleDebitO2(this)">
                                <label class="form-check-label fw-semibold" for="toggleOxygene">Patient sous oxygène</label>
                            </div>
                        </div>
                        <div class="col-6 col-md-3" id="champDebitO2" style="display:none;">
                            <label class="form-label form-label-sm">Débit O2 (L/min)</label>
                            <input type="number" step="0.5" min="0" max="15" class="form-control form-control-sm" name="debit_oxygene" id="debit_oxygene" placeholder="2">
                        </div>
                    </div>

                    <hr class="my-3">

                    <!-- SECTION 4 : Observations -->
                    <p class="fw-semibold text-primary mb-2" style="font-size:.8rem;letter-spacing:.06em;text-transform:uppercase;">Observations Infirmières</p>
                    <textarea class="form-control form-control-sm" name="observations" rows="3" placeholder="État général, comportement, plaintes, remarques…"></textarea>

                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-save me-1"></i>Enregistrer la Fiche</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function toggleDebitO2(checkbox) {
    document.getElementById('champDebitO2').style.display = checkbox.checked ? 'block' : 'none';
    if (!checkbox.checked) document.getElementById('debit_oxygene').value = '';
}
</script>

<!-- Modal Planification Soin -->
<div class="modal fade" id="modalAddSoin" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="<?= BASE_URL ?>hospitalisation/add-soin" method="POST">
                <input type="hidden" name="admission_id" value="<?= $dossier['id'] ?>">
                <input type="hidden" name="patient_id" value="">

                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title">Planifier un Soin</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Type de Soin</label>
                        <select class="form-select" name="type_soin" required>
                            <option value="Injection">Injection</option>
                            <option value="Perfusion">Perfusion</option>
                            <option value="Pansement">Pansement</option>
                            <option value="Prise de sang">Prise de sang</option>
                            <option value="Administration Médicament">Administration Médicament</option>
                            <option value="Surveillance">Surveillance</option>
                            <option value="Autre">Autre</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Date et Heure Prévue</label>
                        <input type="datetime-local" class="form-control" name="date_prevue" value="<?= date('Y-m-d\TH:i') ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description / Instructions</label>
                        <textarea class="form-control" name="description" rows="3" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Condition d'application <small class="text-muted">(optionnel, ex: si fièvre &gt; 38°C)</small></label>
                        <input type="text" class="form-control" name="condition_application" placeholder="ex : si fièvre, si douleur EVA > 6…">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-info text-white">Planifier</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- SCRIPT POUR LA  SURVEILLANCE AUTOMATIQUE -->

<script>
function surveillerConstantes() {
    // Récupérer les dernières valeurs depuis les cartes HTML
    const tempVal = parseFloat(document.querySelector('.vital-box.temp .vital-value').innerText);
    const spo2Val = parseFloat(document.querySelector('.vital-box.spo2 .vital-value')?.innerText || 100);
    const fcVal = parseInt(document.querySelector('.vital-box.pouls .vital-value').innerText);

    // Température > 38.5 = Critique, > 38 = Warning
    const tempBox = document.querySelector('.vital-box.temp');
    if (tempVal > 38.5) tempBox.classList.add('vitals-critical');
    else if (tempVal > 38) tempBox.classList.add('vitals-warning');

    // SpO2 < 94 = Warning, < 90 = Critique
    const spo2Box = document.querySelector('.vital-box.spo2');
    if (spo2Val < 90) spo2Box.classList.add('vitals-critical');
    else if (spo2Val < 94) spo2Box.classList.add('vitals-warning');

    // Pouls > 120 = Critique, > 100 = Warning
    const poulsBox = document.querySelector('.vital-box.pouls');
    if (fcVal > 120) poulsBox.classList.add('vitals-critical');
    else if (fcVal > 100) poulsBox.classList.add('vitals-warning');
}

// Lancer la surveillance dès le chargement
document.addEventListener('DOMContentLoaded', surveillerConstantes);
</script>

<script>
function preparerModalSoin(patientId, admissionId) {
    // Remplir les champs cachés du formulaire de la modale
    document.querySelector('#modalAddSoin input[name="patient_id"]').value = patientId;
    document.querySelector('#modalAddSoin input[name="admission_id"]').value = admissionId;

    // Ouvrir la modale manuellement
    const myModal = new bootstrap.Modal(document.getElementById('modalAddSoin'));
    myModal.show();
}
</script>

<!-- SCRIPT POUR LES GRAPHIQUES CHART.JS -->
<script>
// Préparation des données PHP pour JS
const historyData = <?= json_encode($constantes) ?>;

// Extraction des labels (Dates) et données
const labels = historyData.map(d => {
    const date = new Date(d.date_mesure);
    return date.toLocaleDateString('fr-FR', {hour: '2-digit', minute:'2-digit'});
});

const dataTemp = historyData.map(d => d.temperature);
const dataSys = historyData.map(d => d.tension_sys);
const dataDia = historyData.map(d => d.tension_dia);

// --- GRAPHIQUE TEMPERATURE ---
new Chart(document.getElementById('chartTemp'), {
    type: 'line',
    data: {
        labels: labels,
        datasets: [{
            label: 'Température (°C)',
            data: dataTemp,
            borderColor: '#dc3545', // Rouge
            backgroundColor: 'rgba(220, 53, 69, 0.1)',
            tension: 0.4,
            fill: true,
            pointRadius: 4
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: { y: { min: 35, max: 42 } }
    }
});

// --- GRAPHIQUE TENSION ---
new Chart(document.getElementById('chartTension'), {
    type: 'line',
    data: {
        labels: labels,
        datasets: [
            {
                label: 'Systolique',
                data: dataSys,
                borderColor: '#0dcaf0', // Cyan
                tension: 0.4
            },
            {
                label: 'Diastolique',
                data: dataDia,
                borderColor: '#0d6efd', // Bleu
                tension: 0.4
            }
        ]
    },
    options: {
        responsive: true
    }
});

// Fonction Validation Soin
function validerSoin(id) {
    if(confirm("Confirmer la réalisation de ce soin ?")) {
        const note = prompt("Observation éventuelle (facultatif) :");

        const formData = new FormData();
        formData.append('soin_id', id);
        formData.append('note', note);

        fetch('<?= BASE_URL ?>hospitalisation/valider-soin', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if(data.success) location.reload();
        });
    }
}

setInterval(() => {
    fetch(window.location.href)
        .then(response => response.text())
        .then(html => {
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            // Mise à jour silencieuse des cartes de constantes
            document.querySelector('.row.g-3').innerHTML = doc.querySelector('.row.g-3').innerHTML;
        });
}, 60000);
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>