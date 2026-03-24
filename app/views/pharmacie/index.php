<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div class="container-fluid bg-light" style="min-height: 100vh;">
    <div class="row">
        <?php require_once __DIR__ . '/../layouts/sidebar.php'; ?>

        <main class="col-md-10 ms-sm-auto px-md-4">
            <div class="d-flex justify-content-between align-items-center py-4 border-bottom mb-4">
                <h2 class="fw-bold"><i class="bi bi-capsule-pill text-pink me-2"></i>Gestion Pharmacie</h2>
                <div class="badge bg-dark p-2">Stock global : <?= $total_items ?> références</div>
            </div>

            <div class="row g-4">
                <!-- 1. ALERTES STOCK -->
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm rounded-4">
                        <div class="card-header bg-white fw-bold"><i class="bi bi-exclamation-triangle text-danger me-2"></i>Ruptures de stock</div>
                        <div class="list-group list-group-flush">
                            <?php foreach($low_stock as $item): ?>
                                <div class="list-group-item d-flex justify-content-between align-items-center">
                                    <small><?= $item['designation'] ?></small>
                                    <span class="badge bg-danger"><?= $item['quantite_stock'] ?> restants</span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- 2. ORDONNANCES À PRÉPARER (e-Prescription) -->
                <div class="col-md-8">
                    <div class="card border-0 shadow-sm rounded-4">
                        <div class="card-header bg-white fw-bold d-flex justify-content-between">
                            <span><i class="bi bi-qr-code me-2 text-primary"></i>Ordonnances Numériques Entrantes</span>
                            <span class="badge bg-primary"><?= count($pending_orders) ?> nouvelles</span>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead>
                                    <tr class="small text-muted">
                                        <th>PATIENT</th>
                                        <th>MÉDECIN</th>
                                        <th>CONTENU</th>
                                        <th>STATUT</th>
                                        <th class="text-end">ACTION</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($pending_orders as $ord): ?>
                                    <tr>
                                        <td><strong><?= $ord['patient_nom'] ?></strong></td>
                                        <td>Dr. <?= $ord['medecin_nom'] ?></td>
                                        <td><small><?= $ord['nombre_lignes'] ?> médicaments</small></td>
                                        <td><span class="badge bg-warning text-dark">Prête pour délivrance</span></td>
                                        <td class="text-end">
                                            <button class="btn btn-sm btn-primary rounded-pill px-3" onclick="processOrder(<?= $ord['id'] ?>)">
                                                Délivrer
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>