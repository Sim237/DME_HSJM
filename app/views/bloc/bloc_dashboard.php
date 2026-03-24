<main class="col-md-10 ms-sm-auto px-md-4">
    <h2 class="fw-bold my-4">Tableau de Bord Bloc Opératoire</h2>

    <!-- VUE DES SALLES (Temps Réel) -->
    <div class="row g-4 mb-5">
        <?php foreach($salles as $salle): ?>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 <?= $salle['statut'] === 'OCCUPEE' ? 'bg-danger text-white' : 'bg-success text-white' ?>">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <h5 class="fw-bold"><?= $salle['nom_salle'] ?></h5>
                        <i class="bi bi-door-<?= $salle['statut'] === 'OCCUPEE' ? 'closed' : 'open' ?> fs-2"></i>
                    </div>
                    <hr class="opacity-25">
                    <?php if($salle['statut'] === 'OCCUPEE'): ?>
                        <div class="small">
                            <strong>Patient:</strong> <?= $salle['patient_courant'] ?><br>
                            <strong>Chirurgien:</strong> <?= $salle['chirurgien_nom'] ?><br>
                            <strong>Acte:</strong> <?= $salle['diag_op'] ?>
                        </div>
                    <?php else: ?>
                        <p class="mb-0">Libre - Prête pour intervention</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- LISTE DES ATTENTES -->
    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-header bg-white fw-bold">Demandes en attente de programmation</div>
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>Patient</th>
                        <th>Chirurgien</th>
                        <th>Anesthésie</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($attentes as $att): ?>
                    <tr>
                        <td><?= $att['nom'] ?></td>
                        <td>Dr. <?= $att['chirurgien_nom'] ?></td>
                        <td><?= $att['type_anesthesie'] ?></td>
                        <td>
                            <a href="<?= BASE_URL ?>bloc/programmer/<?= $att['id'] ?>" class="btn btn-sm btn-primary">Programmer</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>