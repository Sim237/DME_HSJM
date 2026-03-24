<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div class="app-wrapper">
    <?php require_once __DIR__ . '/../layouts/sidebar.php'; ?>

    <main class="main-content w-100 p-4">
        <h2 class="fw-bold mb-4">Ordonnances à traiter</h2>

        <div class="card border-0 shadow-sm rounded-4">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light py-3">
                        <tr class="small text-uppercase">
                            <th class="ps-4">Patient</th>
                            <th>Service</th>
                            <th>Prescripteur</th>
                            <th>Date/Heure</th>
                            <th class="text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($pending_orders as $ord): ?>
                        <tr>
                            <td class="ps-4">
                                <div class="fw-bold"><?= $ord['patient_nom'] ?> <?= $ord['patient_prenom'] ?></div>
                                <small class="text-muted">ID: <?= $ord['qr_code_token'] ?? 'N/A' ?></small>
                            </td>
                            <td><span class="badge bg-info-subtle text-info">Médecine</span></td>
                            <td>Dr. <?= $ord['medecin_nom'] ?></td>
                            <td class="small"><?= date('d/m/Y H:i', strtotime($ord['date_creation'])) ?></td>
                            <td class="text-end pe-4">
                                <a href="<?= BASE_URL ?>pharmacie/traitement/<?= $ord['id'] ?>" class="btn btn-primary btn-sm rounded-pill px-3 fw-bold">
                                    Traiter
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>