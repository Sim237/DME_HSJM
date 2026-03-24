<?php include __DIR__ . '/../layouts/header.php'; ?>

<div class="container-fluid">
    <div class="row">
        <?php include __DIR__ . '/../layouts/sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-0">
            <div class="main-content">
                <?php require_once __DIR__ . '/../layouts/topbar.php'; ?>
                
                <div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-4">
                    <h1 class="h2"><i class="bi bi-person-heart text-warning me-2"></i>Receveurs CSH</h1>
                    <a href="<?php echo BASE_URL; ?>registres/ajouter-receveur-csh" class="btn btn-warning">
                        <i class="bi bi-plus me-1"></i>Nouveau Receveur
                    </a>
                </div>

                <div class="card">
                    <div class="card-body">
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle me-2"></i>
                            Registre des receveurs de cellules souches hématopoïétiques
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<?php include __DIR__ . '/../layouts/footer.php'; ?>