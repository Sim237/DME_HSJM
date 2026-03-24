<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div class="container-fluid">
    <div class="row">
        <?php require_once __DIR__ . '/../layouts/sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Facture <?= $facture['numero_facture'] ?></h1>
                <div class="btn-group">
                    <a href="<?= BASE_URL ?>facturation/pdf/<?= $facture['id'] ?>" class="btn btn-outline-primary" target="_blank">
                        <i class="bi bi-file-pdf"></i> PDF
                    </a>
                    <a href="<?= BASE_URL ?>facturation" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left"></i> Retour
                    </a>
                </div>
            </div>

            <div class="row">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header">
                            <h5>Détails de la facture</h5>
                        </div>
                        <div class="card-body">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <strong>Patient :</strong> <?= $facture['nom'] . ' ' . $facture['prenom'] ?>
                                </div>
                                <div class="col-md-6">
                                    <strong>Date :</strong> <?= date('d/m/Y', strtotime($facture['date_facture'])) ?>
                                </div>
                            </div>
                            
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Description</th>
                                        <th>Qté</th>
                                        <th>Prix Unit.</th>
                                        <th>Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($lignes as $ligne): ?>
                                    <tr>
                                        <td><?= $ligne['libelle'] ?></td>
                                        <td><?= $ligne['quantite'] ?></td>
                                        <td><?= number_format($ligne['prix_unitaire'], 0, ',', ' ') ?> FCFA</td>
                                        <td><?= number_format($ligne['montant'], 0, ',', ' ') ?> FCFA</td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                    <tr class="table-info">
                                        <th colspan="3">TOTAL</th>
                                        <th><?= number_format($facture['montant_ttc'], 0, ',', ' ') ?> FCFA</th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header">
                            <h5>Informations</h5>
                        </div>
                        <div class="card-body">
                            <p><strong>Statut :</strong> 
                                <span class="badge bg-<?= 
                                    $facture['statut'] === 'payee' ? 'success' : 
                                    ($facture['statut'] === 'emise' ? 'warning' : 'secondary') 
                                ?>">
                                    <?= ucfirst($facture['statut']) ?>
                                </span>
                            </p>
                            
                            <?php if ($facture['date_paiement']): ?>
                                <p><strong>Date paiement :</strong> <?= date('d/m/Y', strtotime($facture['date_paiement'])) ?></p>
                                <p><strong>Mode paiement :</strong> <?= ucfirst($facture['mode_paiement']) ?></p>
                            <?php endif; ?>
                            
                            <p><strong>Adresse :</strong><br><?= $facture['adresse'] ?></p>
                            <p><strong>Téléphone :</strong> <?= $facture['telephone'] ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>