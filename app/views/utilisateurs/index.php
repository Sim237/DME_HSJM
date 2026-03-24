<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div class="container-fluid bg-light" style="min-height: 100vh;">
    <div class="row">
        <?php require_once __DIR__ . '/../layouts/sidebar.php'; ?>

        <main class="col-md-10 ms-sm-auto px-md-4">
            <div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-4 border-bottom">
                <h1 class="h3 fw-bold text-dark"><i class="bi bi-people-fill me-2"></i>Personnel de l'Hôpital</h1>
                <button class="btn btn-primary shadow-sm rounded-pill px-4" onclick="openModal()">
                    <i class="bi bi-person-plus-fill me-2"></i>Nouvel Utilisateur
                </button>
            </div>

            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="ps-4">Utilisateur</th>
                                    <th>Rôle</th>
                                    <th>Service</th>
                                    <th>Authentification</th>
                                    <th>Statut</th>
                                    <th class="text-end pe-4">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $user): ?>
                                <tr>
                                    <td class="ps-4">
                                        <div class="fw-bold"><?= htmlspecialchars($user['nom'] . ' ' . $user['prenom']) ?></div>
                                        <small class="text-muted">@<?= htmlspecialchars($user['username']) ?></small>
                                    </td>
                                    <td><span class="badge bg-light text-dark border"><?= $user['role'] ?></span></td>
                                    <td><div class="fw-bold text-primary"><?= $user['nom_service'] ?? 'Non assigné' ?></div></td>
                                    <td>
                                        <?php if($user['signature_path']): ?><i class="bi bi-pen-fill text-success" title="Signature OK"></i><?php endif; ?>
                                        <?php if($user['cachet_path']): ?><i class="bi bi-patch-check-fill text-info" title="Cachet OK"></i><?php endif; ?>
                                    </td>
                                    <td><span class="badge bg-<?= $user['statut'] == 1 ? 'success' : 'danger' ?>"><?= $user['statut'] == 1 ? 'Actif' : 'Inactif' ?></span></td>
                                    <td class="text-end pe-4">
                                        <button class="btn btn-sm btn-light border" onclick='editUser(<?= json_encode($user) ?>)'><i class="bi bi-pencil-square"></i></button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<div class="modal fade" id="userModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <form id="formUser" enctype="multipart/form-data">
                <input type="hidden" name="id" id="userId">
                <div class="modal-header border-0 pb-0"><h5 class="fw-bold" id="modalTitle">Configuration Utilisateur</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6"><label class="form-label fw-bold small">NOM</label><input type="text" name="nom" id="nom" class="form-control" required></div>
                        <div class="col-md-6"><label class="form-label fw-bold small">PRÉNOM</label><input type="text" name="prenom" id="prenom" class="form-control" required></div>
                          <div class="col-md-12">
        <label class="form-label fw-bold small">ADRESSE EMAIL</label>
        <input type="email" name="email" id="email" class="form-control" placeholder="exemple@hospital.com">
    </div>
                        <div class="col-md-6"><label class="form-label fw-bold small">LOGIN</label><input type="text" name="username" id="username" class="form-control" required></div>
                        <div class="col-md-6"><label class="form-label fw-bold small">SERVICE</label>
                            <select name="service_id" id="service_id" class="form-select" required>
                                <option value="">-- Sélectionner --</option>
                                <?php foreach($services as $s): ?><option value="<?= $s['id'] ?>"><?= $s['nom_service'] ?></option><?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6"><label class="form-label fw-bold small">RÔLE</label>
                            <select name="role" id="role" class="form-select" required onchange="toggleSignatureFields()">
                                <option value="ADMIN">Administrateur</option>
    <option value="MEDECIN">Médecin</option>
    <option value="INFIRMIER">Infirmier</option>
    <option value="SECRETAIRE">Sécretaire (Accueil)</option> <!-- Ajoutez cette ligne -->
    <option value="PARAMETRES">Infirmier_P (Paramètres)</option>
    <option value="SECRETAIRE">Sécretaire (Accueil)</option>
    <option value="LABORANTIN">Laborantin</option>
    <option value="PHARMACIEN">Pharmacien</option>
                            </select>
                        </div>
                        <div class="col-md-6"><label class="form-label fw-bold small">MOT DE PASSE</label><input type="password" name="password" id="password" class="form-control"><small id="pwdHelp" class="text-muted d-none">Vide = inchangé</small></div>
                        <div class="col-12 p-3 bg-light rounded-3" id="signatureZone">
                            <div class="row">
                                <div class="col-md-6"><label class="small fw-bold">Signature (PNG)</label><input type="file" name="signature" class="form-control form-control-sm"></div>
                                <div class="col-md-6"><label class="small fw-bold">Cachet</label><input type="file" name="cachet" class="form-control form-control-sm"></div>
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

<script>
let modal;
document.addEventListener('DOMContentLoaded', function() { modal = new bootstrap.Modal(document.getElementById('userModal')); });

function toggleSignatureFields() {
    const role = document.getElementById('role').value;
    document.getElementById('signatureZone').style.display = (role === 'MEDECIN' || role === 'ADMIN') ? 'block' : 'none';
}

function openModal() {
    document.getElementById('formUser').reset();
    document.getElementById('userId').value = '';
    document.getElementById('modalTitle').innerText = "Nouvel Utilisateur";
    document.getElementById('pwdHelp').classList.add('d-none');
    toggleSignatureFields();
    modal.show();
}

function editUser(user) {
    // 1. Remplissage des champs de base
    document.getElementById('userId').value = user.id;
    document.getElementById('nom').value = user.nom;
    document.getElementById('prenom').value = user.prenom;
    document.getElementById('username').value = user.username;
    document.getElementById('email').value = user.email || ''; // Affiche l'email ou vide
    document.getElementById('role').value = user.role;

    // 2. Sélection du service (on passe l'ID numérique)
    if (user.service_id) {
        document.getElementById('service_id').value = user.service_id;
    }

    // 3. Ajustement visuel de la modale
    document.getElementById('modalTitle').innerText = "Modifier l'utilisateur : " + user.nom;

    // Afficher l'aide pour le mot de passe (optionnel en modif)
    const helpPwd = document.getElementById('pwdHelp');
    if(helpPwd) helpPwd.classList.remove('d-none');

    // 4. Gestion de la zone Signature/Cachet selon le rôle
    toggleSignatureFields();

    // 5. Affichage de la modale
    modal.show();
}

document.getElementById('formUser').onsubmit = function(e) {
    e.preventDefault();
    fetch('<?= BASE_URL ?>utilisateurs/save', { method: 'POST', body: new FormData(this) })
    .then(res => res.json())
    .then(data => { if(data.success) location.reload(); else alert("Erreur: " + data.message); })
    .catch(err => alert("Erreur technique de connexion"));
};
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>