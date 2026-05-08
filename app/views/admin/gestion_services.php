<?php
require_once __DIR__ . '/../layouts/header.php';
$adminPage = 'services';
require_once __DIR__ . '/partials/admin_nav.php';
?>

        <!-- TOPBAR -->
        <div class="admin-topbar">
            <div class="admin-topbar-title">
                Gestion des Services
                <small>Créer, modifier et organiser les services hospitaliers</small>
            </div>
            <button class="btn btn-primary rounded-pill px-4 fw-bold shadow-sm" onclick="openServiceModal()">
                <i class="bi bi-plus-circle me-2"></i>Nouveau Service
            </button>
        </div>

        <div class="admin-page-content">

            <!-- STATS RAPIDES -->
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm rounded-4 p-3 text-center">
                        <div class="fs-2 fw-black text-primary"><?= count($services) ?></div>
                        <small class="text-muted fw-bold text-uppercase">Services actifs</small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm rounded-4 p-3 text-center">
                        <div class="fs-2 fw-black text-info"><?= array_sum(array_column($services, 'nb_chambres')) ?></div>
                        <small class="text-muted fw-bold text-uppercase">Chambres</small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm rounded-4 p-3 text-center">
                        <div class="fs-2 fw-black text-success"><?= array_sum(array_column($services, 'nb_lits')) ?></div>
                        <small class="text-muted fw-bold text-uppercase">Lits au total</small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm rounded-4 p-3 text-center">
                        <div class="fs-2 fw-black text-warning"><?= array_sum(array_column($services, 'nb_patients')) ?></div>
                        <small class="text-muted fw-bold text-uppercase">Patients actifs</small>
                    </div>
                </div>
            </div>

            <!-- TABLE DES SERVICES -->
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light">
                                <tr class="small text-muted text-uppercase fw-bold" style="font-size:0.72rem;">
                                    <th class="ps-4 py-3">#</th>
                                    <th class="py-3">Nom du Service</th>
                                    <th class="py-3 text-center">Chambres</th>
                                    <th class="py-3 text-center">Lits</th>
                                    <th class="py-3 text-center">Occupés</th>
                                    <th class="py-3 text-center">Patients</th>
                                    <th class="py-3 text-end pe-4">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($services as $s): ?>
                                <tr>
                                    <td class="ps-4 text-muted small"><?= $s['id'] ?></td>
                                    <td>
                                        <div class="fw-bold text-dark"><?= htmlspecialchars($s['nom_service']) ?></div>
                                    </td>
                                    <td class="text-center"><span class="badge bg-info bg-opacity-10 text-info fw-bold"><?= $s['nb_chambres'] ?></span></td>
                                    <td class="text-center"><span class="badge bg-success bg-opacity-10 text-success fw-bold"><?= $s['nb_lits'] ?></span></td>
                                    <td class="text-center">
                                        <?php $occ = (int)$s['lits_occupes']; $tot = (int)$s['nb_lits']; ?>
                                        <span class="badge bg-<?= ($tot > 0 && $occ/$tot > 0.8) ? 'danger' : 'secondary' ?> bg-opacity-10 text-<?= ($tot > 0 && $occ/$tot > 0.8) ? 'danger' : 'secondary' ?> fw-bold">
                                            <?= $occ ?>
                                        </span>
                                    </td>
                                    <td class="text-center"><span class="badge bg-warning bg-opacity-10 text-warning fw-bold"><?= $s['nb_patients'] ?></span></td>
                                    <td class="text-end pe-4">
                                        <div class="d-flex gap-1 justify-content-end">
                                            <button class="btn btn-sm btn-light border rounded-pill px-3"
                                                    onclick='editService(<?= json_encode(['id'=>$s['id'],'nom_service'=>$s['nom_service']]) ?>)'>
                                                <i class="bi bi-pencil-square"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-danger rounded-pill px-3"
                                                    onclick="deleteService(<?= $s['id'] ?>, '<?= addslashes($s['nom_service']) ?>')"
                                                    <?= $s['nb_chambres'] > 0 ? 'title="Des chambres sont liées à ce service"' : '' ?>>
                                                <i class="bi bi-trash3"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if (empty($services)): ?>
                                <tr><td colspan="7" class="text-center py-5 text-muted">Aucun service créé.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div><!-- /admin-page-content -->
    </div><!-- /admin-main -->
</div><!-- /admin-shell -->

<!-- MODAL SERVICE -->
<div class="modal fade" id="serviceModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <form id="formService">
                <input type="hidden" id="serviceId" name="id" value="">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold" id="serviceModalTitle">Nouveau Service</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <label class="form-label fw-bold small">NOM DU SERVICE</label>
                    <input type="text" name="nom_service" id="nomService" class="form-control form-control-lg"
                           placeholder="Ex: Médecine Générale, Chirurgie..." required autofocus>
                    <small class="text-muted mt-1 d-block">Ce nom sera visible par tous les utilisateurs du système.</small>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-5 fw-bold shadow">
                        <i class="bi bi-check-lg me-1"></i> Enregistrer
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- TOAST -->
<div class="toast-container">
    <div id="adminToast" class="toast align-items-center border-0" role="alert">
        <div class="d-flex">
            <div class="toast-body fw-bold" id="adminToastMsg"></div>
            <button type="button" class="btn-close me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    </div>
</div>

<script>
let serviceModal;

document.addEventListener('DOMContentLoaded', function() {
    serviceModal = new bootstrap.Modal(document.getElementById('serviceModal'));

    document.getElementById('formService').onsubmit = function(e) {
        e.preventDefault();
        fetch('<?= BASE_URL ?>admin/save-service', { method: 'POST', body: new FormData(this) })
            .then(r => r.json())
            .then(d => {
                if (d.success) {
                    serviceModal.hide();
                    showToast('Service enregistré avec succès.');
                    setTimeout(() => location.reload(), 900);
                } else showToast(d.message || 'Erreur', false);
            });
    };
});

function showToast(msg, ok = true) {
    const t = document.getElementById('adminToast');
    t.classList.remove('bg-success','bg-danger','text-white');
    t.classList.add(ok ? 'bg-success' : 'bg-danger', 'text-white');
    document.getElementById('adminToastMsg').textContent = msg;
    bootstrap.Toast.getOrCreateInstance(t).show();
}

function openServiceModal() {
    document.getElementById('formService').reset();
    document.getElementById('serviceId').value = '';
    document.getElementById('serviceModalTitle').textContent = 'Nouveau Service';
    serviceModal.show();
}

function editService(s) {
    document.getElementById('serviceId').value   = s.id;
    document.getElementById('nomService').value  = s.nom_service;
    document.getElementById('serviceModalTitle').textContent = 'Modifier : ' + s.nom_service;
    serviceModal.show();
}

function deleteService(id, nom) {
    if (!confirm(`Supprimer le service "${nom}" ?\n\nCette action est irréversible.`)) return;
    fetch('<?= BASE_URL ?>admin/delete-service/' + id, { method: 'POST' })
        .then(r => r.json())
        .then(d => {
            if (d.success) { showToast('Service supprimé.'); setTimeout(() => location.reload(), 800); }
            else showToast(d.message || 'Erreur', false);
        });
}
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
