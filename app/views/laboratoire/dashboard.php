<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/dme_hospital/app/views/layouts/header.php';
$demandes = $demandes ?? [];
$statistiques = $statistiques ?? [];
?>

<style>
.lab-card {
    border: none;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.08);
    transition: transform 0.2s;
}
.lab-card:hover { transform: translateY(-2px); }
.priority-urgent { border-left: 4px solid #dc3545; }
.priority-normal { border-left: 4px solid #28a745; }
.stat-card { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
</style>

<div class="container-fluid">
    <div class="row">
        <?php require_once $_SERVER['DOCUMENT_ROOT'] . '/dme_hospital/app/views/layouts/sidebar.php'; ?>

        <main class="col-md-10 ms-sm-auto px-md-4">
            <div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2"><i class="bi bi-flask text-primary"></i> Laboratoire - Dashboard</h1>
                <div class="btn-group">
                    <button class="btn btn-outline-primary" onclick="refreshDashboard()">
                        <i class="bi bi-arrow-clockwise"></i> Actualiser
                    </button>
                    <a href="<?= BASE_URL ?>laboratoire/planning" class="btn btn-outline-info">
                        <i class="bi bi-calendar3"></i> Planning
                    </a>
                    <a href="<?= BASE_URL ?>laboratoire/controle-qualite" class="btn btn-outline-warning">
                        <i class="bi bi-shield-check"></i> Contrôle Qualité
                    </a>
                </div>
            </div>

            <!-- Statistiques -->
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <div class="card stat-card text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="card-title">Demandes Aujourd'hui</h6>
                                    <h3 class="mb-0"><?= array_sum(array_column($statistiques['demandes_jour'] ?? [], 'nb')) ?></h3>
                                </div>
                                <i class="bi bi-clipboard-data display-6 opacity-75"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-danger text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="card-title">Examens Urgents</h6>
                                    <h3 class="mb-0"><?= $statistiques['urgents'] ?? 0 ?></h3>
                                </div>
                                <i class="bi bi-exclamation-triangle display-6 opacity-75"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-success text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="card-title">Délai Moyen</h6>
                                    <h3 class="mb-0"><?= $statistiques['delai_moyen'] ?? 0 ?>h</h3>
                                </div>
                                <i class="bi bi-clock display-6 opacity-75"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-info text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="card-title">Taux Qualité</h6>
                                    <h3 class="mb-0">98.5%</h3>
                                </div>
                                <i class="bi bi-award display-6 opacity-75"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filtres -->
            <div class="card mb-4">
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <select class="form-select" id="filtreStatut" onchange="filtrerDemandes()">
                                <option value="">Tous les statuts</option>
                                <option value="EN_ATTENTE">En attente</option>
                                <option value="PRELEVEMENTS_EFFECTUES">Prélèvements effectués</option>
                                <option value="EN_ANALYSE">En analyse</option>
                                <option value="RESULTATS_PRETS">Résultats prêts</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select class="form-select" id="filtrePriorite" onchange="filtrerDemandes()">
                                <option value="">Toutes priorités</option>
                                <option value="urgent">Urgent uniquement</option>
                                <option value="normal">Normal uniquement</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <input type="text" class="form-control" id="recherchePatient" placeholder="Rechercher patient..." onkeyup="filtrerDemandes()">
                        </div>
                        <div class="col-md-3">
                            <button class="btn btn-primary w-100" onclick="assignerTechnicienMasse()">
                                <i class="bi bi-person-plus"></i> Assigner Technicien
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Liste des demandes -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Demandes en cours (<?= count($demandes) ?>)</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" id="tableDemandes">
                            <thead class="table-light">
                                <tr>
                                    <th><input type="checkbox" id="selectAll" onchange="toggleSelectAll()"></th>
                                    <th>Priorité</th>
                                    <th>Patient</th>
                                    <th>Examens</th>
                                    <th>Médecin</th>
                                    <th>Statut</th>
                                    <th>Délai</th>
                                    <th>Technicien</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($demandes) > 0): ?>
                                    <?php foreach ($demandes as $demande): ?>
                                    <tr class="demande-row <?= $demande['nb_urgents'] > 0 ? 'priority-urgent' : 'priority-normal' ?>"
                                        data-statut="<?= $demande['statut'] ?>"
                                        data-priorite="<?= $demande['nb_urgents'] > 0 ? 'urgent' : 'normal' ?>"
                                        data-patient="<?= strtolower($demande['nom'] . ' ' . $demande['prenom']) ?>">
                                        <td>
                                            <input type="checkbox" class="demande-checkbox" value="<?= $demande['id'] ?>">
                                        </td>
                                        <td>
                                            <?php if ($demande['nb_urgents'] > 0): ?>
                                                <span class="badge bg-danger">
                                                    <i class="bi bi-exclamation-triangle"></i> URGENT
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-success">Normal</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="fw-bold"><?= htmlspecialchars($demande['nom'] . ' ' . $demande['prenom']) ?></div>
                                            <small class="text-muted"><?= htmlspecialchars($demande['dossier_numero']) ?></small>
                                        </td>
                                        <td>
                                            <span class="badge bg-info"><?= $demande['nb_examens'] ?> examen(s)</span>
                                            <?php if ($demande['nb_urgents'] > 0): ?>
                                                <br><small class="text-danger"><?= $demande['nb_urgents'] ?> urgent(s)</small>
                                            <?php endif; ?>
                                        </td>
                                        <td>Dr. <?= htmlspecialchars($demande['medecin_nom'] . ' ' . $demande['medecin_prenom']) ?></td>
                                        <td>
                                            <?php
                                            $statutClass = [
                                                'EN_ATTENTE' => 'warning',
                                                'PRELEVEMENTS_EFFECTUES' => 'info',
                                                'EN_ANALYSE' => 'primary',
                                                'RESULTATS_PRETS' => 'success'
                                            ];
                                            $class = $statutClass[$demande['statut']] ?? 'secondary';
                                            ?>
                                            <span class="badge bg-<?= $class ?>">
                                                <?= str_replace('_', ' ', $demande['statut']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php
                                            $heures = round((time() - strtotime($demande['date_creation'])) / 3600, 1);
                                            $delaiClass = $heures > ($demande['delai_min'] ?? 24) ? 'text-danger' : 'text-success';
                                            ?>
                                            <span class="<?= $delaiClass ?>"><?= $heures ?>h</span>
                                        </td>
                                        <td>
                                            <?php if (!empty($demande['technicien_nom'])): ?>
                                                <small><?= htmlspecialchars($demande['technicien_nom']) ?></small>
                                            <?php else: ?>
                                                <button class="btn btn-sm btn-outline-primary" onclick="assignerTechnicien(<?= $demande['id'] ?>)">
                                                    <i class="bi bi-person-plus"></i>
                                                </button>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <a href="<?= BASE_URL ?>laboratoire/traitement/<?= $demande['id'] ?>"
   class="btn btn-primary btn-sm rounded-pill px-3">
   <i class="bi bi- eye me-1"></i> Traiter
</a>
                                                <?php if ($demande['statut'] == 'RESULTATS_PRETS'): ?>
                                                <a href="<?= BASE_URL ?>laboratoire/saisie-resultats/<?= $demande['id'] ?>" class="btn btn-outline-success" title="Saisir résultats">
                                                    <i class="bi bi-pencil-square"></i>
                                                </a>
                                                <button class="btn btn-outline-warning" onclick="validerResultats(<?= $demande['id'] ?>)" title="Valider">
                                                    <i class="bi bi-check-circle"></i>
                                                </button>
                                                <?php elseif (in_array($demande['statut'], ['PRELEVEMENTS_EFFECTUES', 'EN_ANALYSE'])): ?>
                                                <a href="<?= BASE_URL ?>laboratoire/saisie-resultats/<?= $demande['id'] ?>" class="btn btn-outline-success" title="Saisir résultats">
                                                    <i class="bi bi-pencil-square"></i>
                                                </a>
                                                <?php endif; ?>
                                                <a href="<?= BASE_URL ?>laboratoire/imprimer/<?= $demande['id'] ?>" target="_blank" class="btn btn-outline-secondary" title="Imprimer">
                                                    <i class="bi bi-printer"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="9" class="text-center py-5 text-muted">
                                            <i class="bi bi-clipboard-check display-4 opacity-25"></i>
                                            <p class="mt-3">Aucune demande en cours.</p>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
function filtrerDemandes() {
    const statut = document.getElementById('filtreStatut').value;
    const priorite = document.getElementById('filtrePriorite').value;
    const recherche = document.getElementById('recherchePatient').value.toLowerCase();

    document.querySelectorAll('.demande-row').forEach(row => {
        let visible = true;

        if (statut && row.dataset.statut !== statut) visible = false;
        if (priorite && row.dataset.priorite !== priorite) visible = false;
        if (recherche && !row.dataset.patient.includes(recherche)) visible = false;

        row.style.display = visible ? '' : 'none';
    });
}

function toggleSelectAll() {
    const selectAll = document.getElementById('selectAll');
    document.querySelectorAll('.demande-checkbox').forEach(cb => {
        cb.checked = selectAll.checked;
    });
}

function assignerTechnicien(demandeId) {
    // Modal d'assignation technicien
    const techniciens = ['Dr. Martin', 'Dr. Dubois', 'Dr. Leroy'];
    const technicien = prompt('Assigner à quel technicien ?\n' + techniciens.join('\n'));

    if (technicien) {
        fetch('<?= BASE_URL ?>laboratoire/assigner-technicien', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `demande_id=${demandeId}&technicien_id=1`
        }).then(() => location.reload());
    }
}

function validerResultats(demandeId) {
    if (confirm('Valider définitivement ces résultats ?')) {
        fetch('<?= BASE_URL ?>laboratoire/valider-resultats', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `demande_id=${demandeId}`
        }).then(() => location.reload());
    }
}

function refreshDashboard() {
    location.reload();
}

// Auto-refresh toutes les 30 secondes
setInterval(refreshDashboard, 30000);
</script>

<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/dme_hospital/app/views/layouts/footer.php'; ?>