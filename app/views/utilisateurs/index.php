<?php
require_once __DIR__ . '/../layouts/header.php';
$adminPage = 'utilisateurs';
require_once __DIR__ . '/../admin/partials/admin_nav.php';
?>

        <!-- TOPBAR -->
        <div class="admin-topbar">
            <div class="admin-topbar-title">
                Personnel de l'Hôpital
                <small>Gestion des comptes utilisateurs</small>
            </div>
            <button class="btn btn-primary rounded-pill px-4 shadow-sm" onclick="openModal()">
                <i class="bi bi-person-plus-fill me-2"></i>Nouvel Utilisateur
            </button>
        </div>

        <div class="admin-page-content">

            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" style="font-size:.85rem;">
                            <thead style="background:#f8fafc;">
                                <tr class="text-muted" style="font-size:.7rem; text-transform:uppercase; letter-spacing:.06em;">
                                    <th class="ps-4 py-3">Utilisateur</th>
                                    <th class="py-3">Rôle</th>
                                    <th class="py-3">Service</th>
                                    <th class="py-3">Authentification</th>
                                    <th class="py-3">Statut</th>
                                    <th class="py-3 text-end pe-4">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $user): ?>
                                <tr>
                                    <td class="ps-4">
                                        <div class="fw-bold"><?= htmlspecialchars($user['nom'] . ' ' . $user['prenom']) ?></div>
                                        <small class="text-muted">@<?= htmlspecialchars($user['username']) ?></small>
                                    </td>
                                    <td>
                                        <?php
                                        $roleColors = [
                                            'ADMIN'                => 'danger',
                                            'MEDECIN'              => 'primary',
                                            'INFIRMIER'            => 'info',
                                            'MAJOR'                => 'primary',
                                            'INFIRMIER_CONSULTANT' => 'info',
                                            'SECRETAIRE'           => 'secondary',
                                            'LABORANTIN'           => 'warning',
                                            'PHARMACIEN'           => 'success',
                                            'DIRECTEUR'            => 'dark',
                                            'PARAMETRES'           => 'info',
                                        ];
                                        $rc = $roleColors[$user['role']] ?? 'secondary';
                                        ?>
                                        <span class="badge bg-<?= $rc ?>-subtle text-<?= $rc ?> border border-<?= $rc ?>-subtle fw-bold" style="font-size:.7rem;">
                                            <?= htmlspecialchars($user['role']) ?>
                                        </span>
                                    </td>
                                    <td><span class="fw-semibold text-primary" style="font-size:.83rem;"><?= htmlspecialchars($user['nom_service'] ?? 'Non assigné') ?></span></td>
                                    <td>
                                        <?php if ($user['signature_path']): ?>
                                            <span class="badge bg-success-subtle text-success border border-success-subtle me-1" style="font-size:.68rem;">
                                                <i class="bi bi-pen-fill me-1"></i>Signature
                                            </span>
                                        <?php endif; ?>
                                        <?php if ($user['cachet_path']): ?>
                                            <span class="badge bg-info-subtle text-info border border-info-subtle" style="font-size:.68rem;">
                                                <i class="bi bi-patch-check-fill me-1"></i>Cachet
                                            </span>
                                        <?php endif; ?>
                                        <?php if (!$user['signature_path'] && !$user['cachet_path']): ?>
                                            <span class="text-muted" style="font-size:.78rem;">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge <?= $user['actif'] == 1 ? 'bg-success' : 'bg-danger' ?> rounded-pill">
                                            <?= $user['actif'] == 1 ? 'Actif' : 'Inactif' ?>
                                        </span>
                                    </td>
                                    <td class="text-end pe-4">
                                        <div class="d-flex gap-1 justify-content-end">
                                            <button class="btn btn-sm btn-light border rounded-pill px-2"
                                                    onclick='editUser(<?= htmlspecialchars(json_encode($user), ENT_QUOTES) ?>)'
                                                    title="Modifier">
                                                <i class="bi bi-pencil-square"></i>
                                            </button>
                                            <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                            <?php if ($user['actif'] == 1): ?>
                                            <button class="btn btn-sm btn-outline-danger rounded-pill px-2"
                                                    data-id="<?= $user['id'] ?>"
                                                    data-nom="<?= htmlspecialchars($user['nom'] . ' ' . $user['prenom']) ?>"
                                                    onclick="deleteUser(this.dataset.id, this.dataset.nom)"
                                                    title="Désactiver">
                                                <i class="bi bi-person-x-fill"></i>
                                            </button>
                                            <?php else: ?>
                                            <button class="btn btn-sm btn-outline-success rounded-pill px-2"
                                                    data-id="<?= $user['id'] ?>"
                                                    data-nom="<?= htmlspecialchars($user['nom'] . ' ' . $user['prenom']) ?>"
                                                    onclick="reactivateUser(this.dataset.id, this.dataset.nom)"
                                                    title="Réactiver">
                                                <i class="bi bi-person-check-fill"></i>
                                            </button>
                                            <?php endif; ?>
                                            <button class="btn btn-sm btn-outline-dark rounded-pill px-2"
                                                    data-id="<?= $user['id'] ?>"
                                                    data-nom="<?= htmlspecialchars($user['nom'] . ' ' . $user['prenom']) ?>"
                                                    onclick="destroyUser(this.dataset.id, this.dataset.nom)"
                                                    title="Supprimer définitivement">
                                                <i class="bi bi-trash3-fill"></i>
                                            </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div><!-- /admin-page-content -->
    </div><!-- /admin-main -->
</div><!-- /admin-shell -->

<!-- MODAL UTILISATEUR -->
<div class="modal fade" id="userModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <form id="formUser" enctype="multipart/form-data">
                <input type="hidden" name="id" id="userId">
                <div class="modal-header border-0 pb-0">
                    <h5 class="fw-bold" id="modalTitle">Configuration Utilisateur</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold small">NOM</label>
                            <input type="text" name="nom" id="nom" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small">PRÉNOM</label>
                            <input type="text" name="prenom" id="prenom" class="form-control" required>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label fw-bold small">ADRESSE EMAIL</label>
                            <input type="email" name="email" id="email" class="form-control" placeholder="exemple@hospital.com">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small">LOGIN</label>
                            <input type="text" name="username" id="username" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small">SERVICE</label>
                            <select name="service_id" id="service_id" class="form-select" required>
                                <option value="">-- Sélectionner --</option>
                                <?php foreach ($services as $s): ?>
                                <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['nom_service']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small">RÔLE</label>
                            <select name="role" id="role" class="form-select" required onchange="toggleSignatureFields()">
                                <option value="ADMIN">Administrateur</option>
                                <option value="DIRECTEUR">Directeur</option>
                                <option value="MEDECIN">Médecin</option>
                                <option value="INFIRMIER">Infirmier</option>
                                <option value="MAJOR">Infirmier Major (Chef de service)</option>
                                <option value="INFIRMIER_CONSULTANT">Infirmier Consultant</option>
                                <option value="SECRETAIRE">Secrétaire (Accueil)</option>
                                <option value="PARAMETRES">Infirmier — Paramètres</option>
                                <option value="LABORANTIN">Laborantin</option>
                                <option value="PHARMACIEN">Pharmacien</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small">MOT DE PASSE</label>
                            <input type="password" name="password" id="password" class="form-control">
                            <small id="pwdHelp" class="text-muted d-none">Laisser vide = inchangé</small>
                        </div>
                        <div class="col-12 p-3 bg-light rounded-3" id="signatureZone">
                            <div class="row">
                                <div class="col-md-6">
                                    <label class="small fw-bold">Signature (PNG)</label>
                                    <input type="file" name="signature" class="form-control form-control-sm">
                                </div>
                                <div class="col-md-6">
                                    <label class="small fw-bold">Cachet</label>
                                    <input type="file" name="cachet" class="form-control form-control-sm">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary px-5 shadow">Enregistrer l'utilisateur</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- TOAST -->
<div class="toast-container">
    <div id="adminToast" class="toast align-items-center border-0 text-white" role="alert">
        <div class="d-flex">
            <div class="toast-body fw-bold" id="adminToastMsg"></div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    </div>
</div>

<script>
let modal;
document.addEventListener('DOMContentLoaded', function() {
    modal = new bootstrap.Modal(document.getElementById('userModal'));
});

function showToast(msg, ok = true) {
    const t = document.getElementById('adminToast');
    t.className = 'toast align-items-center border-0 text-white ' + (ok ? 'bg-success' : 'bg-danger');
    document.getElementById('adminToastMsg').textContent = msg;
    bootstrap.Toast.getOrCreateInstance(t).show();
}

function toggleSignatureFields() {
    const role = document.getElementById('role').value;
    document.getElementById('signatureZone').style.display =
        (role === 'MEDECIN' || role === 'ADMIN') ? 'block' : 'none';
}

function openModal() {
    document.getElementById('formUser').reset();
    document.getElementById('userId').value = '';
    document.getElementById('modalTitle').innerText = 'Nouvel Utilisateur';
    document.getElementById('pwdHelp').classList.add('d-none');
    toggleSignatureFields();
    modal.show();
}

function editUser(user) {
    document.getElementById('userId').value    = user.id;
    document.getElementById('nom').value       = user.nom;
    document.getElementById('prenom').value    = user.prenom;
    document.getElementById('username').value  = user.username;
    document.getElementById('email').value     = user.email || '';
    document.getElementById('role').value      = user.role;
    if (user.service_id) document.getElementById('service_id').value = user.service_id;
    document.getElementById('modalTitle').innerText = 'Modifier : ' + user.nom + ' ' + user.prenom;
    document.getElementById('pwdHelp').classList.remove('d-none');
    toggleSignatureFields();
    modal.show();
}

document.getElementById('formUser').onsubmit = function(e) {
    e.preventDefault();
    fetch('<?= BASE_URL ?>utilisateurs/save', { method: 'POST', body: new FormData(this) })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                showToast('Utilisateur enregistré avec succès.');
                setTimeout(() => location.reload(), 800);
            } else {
                showToast('Erreur : ' + (data.message || 'Inconnue'), false);
            }
        })
        .catch(() => showToast('Erreur technique de connexion', false));
};

function deleteUser(id, nom) {
    if (!confirm(`Désactiver le compte de "${nom}" ?\n\nL'utilisateur ne pourra plus se connecter.`)) return;
    const fd = new FormData();
    fd.append('id', id);
    fetch('<?= BASE_URL ?>utilisateurs/delete', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(d => {
            if (d.success) {
                showToast(`Compte de "${nom}" désactivé.`);
                setTimeout(() => location.reload(), 800);
            } else {
                showToast('Erreur : ' + (d.message || 'Inconnue'), false);
            }
        });
}

function reactivateUser(id, nom) {
    if (!confirm(`Réactiver le compte de "${nom}" ?\n\nL'utilisateur pourra à nouveau se connecter.`)) return;
    const fd = new FormData();
    fd.append('id', id);
    fetch('<?= BASE_URL ?>utilisateurs/reactivate', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(d => {
            if (d.success) {
                showToast(`Compte de "${nom}" réactivé.`);
                setTimeout(() => location.reload(), 800);
            } else {
                showToast('Erreur : ' + (d.message || 'Inconnue'), false);
            }
        });
}

function destroyUser(id, nom) {
    if (!confirm(`⚠️ SUPPRESSION DÉFINITIVE\n\nSupprimer "${nom}" de la base de données ?\n\nCette action est irréversible.`)) return;
    if (!confirm(`Confirmer une dernière fois la suppression définitive de "${nom}" ?`)) return;
    const fd = new FormData();
    fd.append('id', id);
    fetch('<?= BASE_URL ?>utilisateurs/destroy', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(d => {
            if (d.success) {
                showToast(`"${nom}" supprimé définitivement.`);
                setTimeout(() => location.reload(), 800);
            } else {
                showToast(d.message || 'Erreur de suppression', false);
            }
        });
}
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
