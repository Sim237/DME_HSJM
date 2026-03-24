<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<style>
    .profil-container { max-width: 800px; margin: 40px auto; }
    .card-profil { border: none; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); overflow: hidden; }
    .profil-header { background: linear-gradient(135deg, #0ea5e9, #3b82f6); padding: 40px; text-align: center; color: white; }
    .profil-avatar { width: 100px; height: 100px; background: white; color: #0ea5e9; border-radius: 30px; display: flex; align-items: center; justify-content: center; font-size: 3rem; font-weight: 800; margin: 0 auto 15px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
</style>

<div class="container-fluid bg-light" style="min-height: 100vh;">
    <div class="row">
        <?php
        // L'infirmier n'a pas de sidebar dans son dashboard, on fait pareil ici si besoin
        if ($_SESSION['user_role'] !== 'INFIRMIER') {
            require_once __DIR__ . '/../layouts/sidebar.php';
        }
        ?>

        <main class="col">
            <div class="profil-container">
                <div class="d-flex mb-4">
                    <a href="<?= BASE_URL ?>" class="btn btn-link text-decoration-none text-muted">
                        <i class="bi bi-arrow-left"></i> Retour au Dashboard
                    </a>
                </div>

                <?php if(isset($_GET['success'])): ?>
                    <div class="alert alert-success rounded-pill shadow-sm mb-4"><i class="bi bi-check-circle me-2"></i> Profil mis à jour avec succès !</div>
                <?php endif; ?>

                <div class="card card-profil">
                    <div class="profil-header">
                        <div class="profil-avatar">
                            <?= strtoupper(substr($user['nom'], 0, 1) . substr($user['prenom'], 0, 1)) ?>
                        </div>
                        <h3 class="fw-bold mb-0"><?= htmlspecialchars($user['nom'] . ' ' . $user['prenom']) ?></h3>
                        <span class="badge bg-white text-primary mt-2"><?= $user['role'] ?> • Service <?= $user['nom_service'] ?></span>
                    </div>

                    <div class="card-body p-5 bg-white">
                        <form action="<?= BASE_URL ?>update-profil" method="POST">
                            <h5 class="fw-bold mb-4 text-dark border-bottom pb-2">Informations Personnelles</h5>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-muted">NOM</label>
                                    <input type="text" name="nom" class="form-control" value="<?= htmlspecialchars($user['nom']) ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-muted">PRÉNOM</label>
                                    <input type="text" name="prenom" class="form-control" value="<?= htmlspecialchars($user['prenom']) ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-muted">ADRESSE EMAIL</label>
                                    <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-muted">TÉLÉPHONE</label>
                                    <input type="text" name="telephone" class="form-control" value="<?= htmlspecialchars($user['telephone']) ?>">
                                </div>
                            </div>

                            <h5 class="fw-bold mt-5 mb-4 text-dark border-bottom pb-2">Sécurité</h5>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-muted">NOM D'UTILISATEUR</label>
                                    <input type="text" class="form-control bg-light" value="<?= $user['username'] ?>" readonly disabled>
                                    <small class="text-muted">Le login ne peut pas être modifié.</small>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-muted">NOUVEAU MOT DE PASSE</label>
                                    <input type="password" name="new_password" class="form-control" placeholder="Laisser vide pour ne pas changer">
                                </div>
                            </div>

                            <div class="mt-5">
                                <button type="submit" class="btn btn-primary btn-lg w-100 rounded-pill shadow">
                                    Enregistrer les modifications
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>