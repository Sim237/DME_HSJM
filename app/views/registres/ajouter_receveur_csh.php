<?php include __DIR__ . '/../layouts/header.php'; ?>

<div class="container-fluid">
    <div class="row">
        <?php include __DIR__ . '/../layouts/sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-0">
            <div class="main-content">
                <?php require_once __DIR__ . '/../layouts/topbar.php'; ?>
                
                <div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-4">
                    <h1 class="h2"><i class="bi bi-person-heart text-warning me-2"></i>Nouveau Receveur CSH</h1>
                    <a href="<?php echo BASE_URL; ?>registres/receveurs-csh" class="btn btn-secondary">
                        <i class="bi bi-arrow-left me-1"></i>Retour
                    </a>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Informations du receveur de cellules souches</h5>
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
                                        <label class="form-label">Pathologie *</label>
                                        <select class="form-select" name="pathologie" required>
                                            <option value="">Sélectionner</option>
                                            <option value="Leucémie aiguë lymphoblastique">Leucémie aiguë lymphoblastique</option>
                                            <option value="Leucémie aiguë myéloblastique">Leucémie aiguë myéloblastique</option>
                                            <option value="Leucémie chronique">Leucémie chronique</option>
                                            <option value="Lymphome">Lymphome</option>
                                            <option value="Aplasie médullaire">Aplasie médullaire</option>
                                            <option value="Myélofibrose">Myélofibrose</option>
                                            <option value="Syndrome myélodysplasique">Syndrome myélodysplasique</option>
                                            <option value="Autre">Autre</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Urgence *</label>
                                        <select class="form-select" name="urgence" required>
                                            <option value="">Sélectionner</option>
                                            <option value="FAIBLE">Faible</option>
                                            <option value="MOYENNE">Moyenne</option>
                                            <option value="HAUTE">Haute</option>
                                            <option value="CRITIQUE">Critique</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Typage HLA</label>
                                <textarea class="form-control" name="hla_typing" rows="3" 
                                          placeholder="Ex: HLA-A*02:01, HLA-B*07:02, HLA-C*07:02"></textarea>
                                <div class="form-text">Saisir les résultats du typage HLA si disponibles</div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" name="email">
                            </div>
                            
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle me-2"></i>
                                <strong>Information :</strong> Ce patient sera inscrit dans la liste d'attente pour une greffe de cellules souches hématopoïétiques.
                            </div>
                            
                            <div class="d-flex justify-content-end gap-2">
                                <a href="<?php echo BASE_URL; ?>registres/receveurs-csh" class="btn btn-secondary">Annuler</a>
                                <button type="submit" class="btn btn-warning">
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