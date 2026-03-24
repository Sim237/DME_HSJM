<?php include __DIR__ . '/../layouts/header.php'; ?>

<div class="container-fluid">
    <div class="row">
        <?php include __DIR__ . '/../layouts/sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-0">
            <div class="main-content">
                <?php require_once __DIR__ . '/../layouts/topbar.php'; ?>
                
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-4">
                    <div>
                        <h1 class="h2 text-gradient mb-1">
                            <i class="bi bi-journal-medical me-2"></i>REGISTRES
                        </h1>
                        <p class="text-muted mb-0">Gestion des registres de dons et maladies</p>
                    </div>
                    <div class="btn-group">
                        <button type="button" class="btn btn-primary dropdown-toggle" data-bs-toggle="dropdown">
                            <i class="bi bi-plus me-1"></i>Nouveau
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>registres/ajouter-donneur-sang">
                                <i class="bi bi-droplet me-2"></i>Donneur de sang
                            </a></li>
                            <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>registres/ajouter-donneur-csh">
                                <i class="bi bi-heart-pulse me-2"></i>Donneur CSH
                            </a></li>
                            <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>registres/ajouter-receveur-csh">
                                <i class="bi bi-person-heart me-2"></i>Receveur CSH
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>registres/ajouter-maladie-chronique">
                                <i class="bi bi-clipboard2-data me-2"></i>Maladie chronique
                            </a></li>
                        </ul>
                    </div>
                </div>

                <!-- 1. Registres de Don de tissus humains -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-heart text-danger me-2"></i>1. Registres de Don de Tissus Humains</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <!-- Don de sang -->
                            <div class="col-md-6">
                                <div class="card border-danger">
                                    <div class="card-header bg-danger text-white">
                                        <h6 class="mb-0"><i class="bi bi-droplet me-2"></i>a. Registre de don de sang 🩸</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="list-group list-group-flush">
                                            <a href="<?php echo BASE_URL; ?>registres/donneurs-sang" class="list-group-item list-group-item-action">
                                                <i class="bi bi-person-plus me-2"></i>Donneurs de sang
                                            </a>
                                            <a href="<?php echo BASE_URL; ?>registres/banques-sang" class="list-group-item list-group-item-action">
                                                <i class="bi bi-bank me-2"></i>Banques de sang
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Cellules souches -->
                            <div class="col-md-6">
                                <div class="card border-success">
                                    <div class="card-header bg-success text-white">
                                        <h6 class="mb-0"><i class="bi bi-heart-pulse me-2"></i>b. Cellules souches hématopoïétiques</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="list-group list-group-flush">
                                            <a href="<?php echo BASE_URL; ?>registres/donneurs-csh" class="list-group-item list-group-item-action">
                                                <i class="bi bi-person-check me-2"></i>Donneurs CSH
                                            </a>
                                            <a href="<?php echo BASE_URL; ?>registres/receveurs-csh" class="list-group-item list-group-item-action">
                                                <i class="bi bi-person-heart me-2"></i>Receveurs CSH
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 2. Registres de maladies chroniques -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-clipboard2-data text-primary me-2"></i>2. Registres de Maladies Chroniques et Rares</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <a href="<?php echo BASE_URL; ?>registres/diabetiques" class="text-decoration-none">
                                    <div class="card border-primary hover-shadow">
                                        <div class="card-body text-center">
                                            <i class="bi bi-activity display-6 text-primary mb-2"></i>
                                            <h6>Diabétiques</h6>
                                        </div>
                                    </div>
                                </a>
                            </div>
                            <div class="col-md-4">
                                <a href="<?php echo BASE_URL; ?>registres/hypertendus" class="text-decoration-none">
                                    <div class="card border-danger hover-shadow">
                                        <div class="card-body text-center">
                                            <i class="bi bi-heart-pulse display-6 text-danger mb-2"></i>
                                            <h6>Hypertendus</h6>
                                        </div>
                                    </div>
                                </a>
                            </div>
                            <div class="col-md-4">
                                <a href="<?php echo BASE_URL; ?>registres/cancers" class="text-decoration-none">
                                    <div class="card border-warning hover-shadow">
                                        <div class="card-body text-center">
                                            <i class="bi bi-shield-exclamation display-6 text-warning mb-2"></i>
                                            <h6>Cancers</h6>
                                        </div>
                                    </div>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<?php include __DIR__ . '/../layouts/footer.php'; ?>