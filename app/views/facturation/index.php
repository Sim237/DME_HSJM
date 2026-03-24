<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div class="container-fluid">
    <div class="row">
        <?php require_once __DIR__ . '/../layouts/sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2"><i class="bi bi-receipt"></i> Facturation</h1>
                <button class="btn btn-primary" onclick="genererFactureAuto()">
                    <i class="bi bi-plus"></i> Générer Facture
                </button>
            </div>

            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>N° Facture</th>
                                    <th>Patient</th>
                                    <th>Date</th>
                                    <th>Montant</th>
                                    <th>Statut</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($factures as $facture): ?>
                                <tr>
                                    <td><?= $facture['numero_facture'] ?></td>
                                    <td><?= $facture['nom'] . ' ' . $facture['prenom'] ?></td>
                                    <td><?= date('d/m/Y', strtotime($facture['date_facture'])) ?></td>
                                    <td><?= number_format($facture['montant_ttc'], 0, ',', ' ') ?> FCFA</td>
                                    <td>
                                        <span class="badge bg-<?= 
                                            $facture['statut'] === 'payee' ? 'success' : 
                                            ($facture['statut'] === 'emise' ? 'warning' : 'secondary') 
                                        ?>">
                                            <?= ucfirst($facture['statut']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="<?= BASE_URL ?>facturation/voir/<?= $facture['id'] ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="<?= BASE_URL ?>facturation/pdf/<?= $facture['id'] ?>" class="btn btn-sm btn-outline-secondary" target="_blank">
                                            <i class="bi bi-file-pdf"></i>
                                        </a>
                                        <?php if ($facture['statut'] !== 'payee'): ?>
                                        <button class="btn btn-sm btn-outline-success" onclick="marquerPayee(<?= $facture['id'] ?>)">
                                            <i class="bi bi-check"></i>
                                        </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
function genererFactureAuto() {
    const consultationId = prompt('ID de la consultation :');
    if (consultationId) {
        window.location.href = `${BASE_URL}facturation/generer/${consultationId}`;
    }
}

function marquerPayee(factureId) {
    if (confirm('Marquer cette facture comme payée ?')) {
        const formData = new FormData();
        formData.append('mode_paiement', 'especes');
        
        fetch(`${BASE_URL}facturation/marquer-payee/${factureId}`, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            }
        });
    }
}
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>