<?php include __DIR__ . '/../layouts/header.php'; ?>

<div class="container-fluid">
    <div class="row">
        <?php include __DIR__ . '/../layouts/sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-0">
            <div class="main-content">
                <?php require_once __DIR__ . '/../layouts/topbar.php'; ?>
                
                <div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-4">
                    <h1 class="h2"><i class="bi bi-clipboard2-data text-primary me-2"></i><?php echo $type; ?></h1>
                </div>

                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Dossier</th>
                                        <th>Nom</th>
                                        <th>Prénom</th>
                                        <th>Date Diagnostic</th>
                                        <th>Stade</th>
                                        <th>Médecin Référent</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($patients)): ?>
                                        <tr>
                                            <td colspan="6" class="text-center text-muted">
                                                Aucun patient enregistré pour <?php echo strtolower($type); ?>
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($patients as $patient): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($patient['dossier_numero']); ?></td>
                                                <td><?php echo htmlspecialchars($patient['nom']); ?></td>
                                                <td><?php echo htmlspecialchars($patient['prenom']); ?></td>
                                                <td><?php echo $patient['date_diagnostic']; ?></td>
                                                <td><?php echo $patient['stade'] ?? '-'; ?></td>
                                                <td><?php echo $patient['medecin_referent'] ?? '-'; ?></td>
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