<?php include __DIR__ . '/../layouts/header.php'; ?>

<div class="container-fluid">
    <div class="row">
        <?php include __DIR__ . '/../layouts/sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-0">
            <div class="main-content">
                <?php require_once __DIR__ . '/../layouts/topbar.php'; ?>
                
                <div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-4">
                    <h1 class="h2"><i class="bi bi-droplet text-danger me-2"></i>Donneurs de Sang</h1>
                    <a href="<?php echo BASE_URL; ?>registres/ajouter-donneur-sang" class="btn btn-primary">
                        <i class="bi bi-plus me-1"></i>Nouveau Donneur
                    </a>
                </div>

                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Nom</th>
                                        <th>Prénom</th>
                                        <th>Groupe Sanguin</th>
                                        <th>Téléphone</th>
                                        <th>Dernière Donation</th>
                                        <th>Statut</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($donneurs)): ?>
                                        <tr>
                                            <td colspan="6" class="text-center text-muted">
                                                Aucun donneur enregistré
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($donneurs as $donneur): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($donneur['nom']); ?></td>
                                                <td><?php echo htmlspecialchars($donneur['prenom']); ?></td>
                                                <td>
                                                    <span class="badge bg-danger">
                                                        <?php echo $donneur['groupe_sanguin'] . $donneur['rhesus']; ?>
                                                    </span>
                                                </td>
                                                <td><?php echo htmlspecialchars($donneur['telephone']); ?></td>
                                                <td><?php echo $donneur['derniere_donation'] ?? 'Jamais'; ?></td>
                                                <td>
                                                    <span class="badge bg-success"><?php echo $donneur['statut']; ?></span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<?php include __DIR__ . '/../layouts/footer.php'; ?>