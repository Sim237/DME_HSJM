<?php include __DIR__ . '/../layouts/header.php'; ?>

<div class="container-fluid">
    <div class="row">
        <?php include __DIR__ . '/../layouts/sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-0">
            <div class="main-content">
                <?php require_once __DIR__ . '/../layouts/topbar.php'; ?>
                
                <div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-4">
                    <h1 class="h2"><i class="bi bi-droplet text-danger me-2"></i>Nouveau Donneur de Sang</h1>
                    <a href="<?php echo BASE_URL; ?>registres/donneurs-sang" class="btn btn-secondary">
                        <i class="bi bi-arrow-left me-1"></i>Retour
                    </a>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Informations du donneur</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Nom *</label>
                                        <input type="text" class="form-control" name="nom" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Prénom *</label>
                                        <input type="text" class="form-control" name="prenom" required>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Date de naissance *</label>
                                        <input type="date" class="form-control" name="date_naissance" required>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Sexe *</label>
                                        <select class="form-select" name="sexe" required>
                                            <option value="">Sélectionner</option>
                                            <option value="M">Masculin</option>
                                            <option value="F">Féminin</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Téléphone *</label>
                                        <input type="tel" class="form-control" name="telephone" required>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Groupe sanguin *</label>
                                        <select class="form-select" name="groupe_sanguin" required>
                                            <option value="">Sélectionner</option>
                                            <option value="A">A</option>
                                            <option value="B">B</option>
                                            <option value="AB">AB</option>
                                            <option value="O">O</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Rhésus *</label>
                                        <select class="form-select" name="rhesus" required>
                                            <option value="">Sélectionner</option>
                                            <option value="+">Positif (+)</option>
                                            <option value="-">Négatif (-)</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" name="email">
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Adresse</label>
                                <textarea class="form-control" name="adresse" rows="3"></textarea>
                            </div>
                            
                            <div class="d-flex justify-content-end gap-2">
                                <a href="<?php echo BASE_URL; ?>registres/donneurs-sang" class="btn btn-secondary">Annuler</a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-check me-1"></i>Enregistrer
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<?php include __DIR__ . '/../layouts/footer.php'; ?>