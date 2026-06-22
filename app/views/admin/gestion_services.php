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
            <div class="adm-topbar-actions">
                <div class="adm-clock-pill">
                    <span class="adm-clock-dot"></span>
                    <span class="adm-clock-time">--:--:--</span>
                </div>
                <button class="adm-topbtn adm-topbtn-primary" onclick="openServiceModal()">
                    <i class="bi bi-plus-circle-fill"></i> Nouveau Service
                </button>
            </div>
        </div>

        <div class="admin-page-content">

            <!-- STATS RAPIDES -->
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <div class="adm-stat" style="--adm-accent:#3b82f6;">
                        <div class="adm-stat-val"><?= count($services) ?></div>
                        <div class="adm-stat-lbl">Services actifs</div>
                        <i class="bi bi-building-fill adm-stat-ico"></i>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="adm-stat" style="--adm-accent:#06b6d4;">
                        <div class="adm-stat-val"><?= array_sum(array_column($services, 'nb_chambres')) ?></div>
                        <div class="adm-stat-lbl">Chambres</div>
                        <i class="bi bi-door-closed-fill adm-stat-ico"></i>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="adm-stat" style="--adm-accent:#22c55e;">
                        <div class="adm-stat-val"><?= array_sum(array_column($services, 'nb_lits')) ?></div>
                        <div class="adm-stat-lbl">Lits au total</div>
                        <i class="bi bi-grid-3x3-gap-fill adm-stat-ico"></i>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="adm-stat" style="--adm-accent:#f59e0b;">
                        <div class="adm-stat-val"><?= array_sum(array_column($services, 'nb_patients')) ?></div>
                        <div class="adm-stat-lbl">Patients actifs</div>
                        <i class="bi bi-person-fill adm-stat-ico"></i>
                    </div>
                </div>
            </div>

            <!-- TABLE DES SERVICES -->
            <div class="adm-card">
                <div class="adm-card-header">
                    <span><i class="bi bi-building-fill me-2 text-primary"></i>Liste des Services</span>
                    <span class="adm-stat-lbl" style="font-size:.67rem;"><?= count($services) ?> services</span>
                </div>
                <div class="table-responsive">
                    <table class="adm-table">
                        <thead>
                            <tr>
                                <th style="padding-left:1.5rem;">#</th>
                                <th>Nom du Service</th>
                                <th class="text-center">Chambres</th>
                                <th class="text-center">Lits</th>
                                <th class="text-center">Occupés</th>
                                <th class="text-center">Patients</th>
                                <th class="text-end" style="padding-right:1.5rem;">Actions</th>
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
                                    <td class="text-end" style="padding-right:1.5rem;">
                                        <div class="d-flex gap-1 justify-content-end">
                                            <button class="adm-action-btn"
                                                    onclick='editService(<?= json_encode(['id'=>$s['id'],'nom_service'=>$s['nom_service']]) ?>)'
                                                    title="Modifier">
                                                <i class="bi bi-pencil-square"></i>
                                            </button>
                                            <button class="adm-action-btn" style="color:#dc2626;border-color:#fecaca;"
                                                    onclick="deleteService(<?= $s['id'] ?>, '<?= addslashes($s['nom_service']) ?>')"
                                                    <?= $s['nb_chambres'] > 0 ? 'title="Des chambres sont liées à ce service"' : 'title="Supprimer"' ?>>
                                                <i class="bi bi-trash3"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if (empty($services)): ?>
                                <tr><td colspan="7" class="text-center py-5 text-muted fst-italic">Aucun service créé.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
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
