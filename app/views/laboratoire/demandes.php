<?php
require_once __DIR__ . '/../layouts/header.php';
// Sécurisation : on s'assure que la variable est un tableau
$demandes = $demandes ?? [];
?>

<div class="container-fluid">
    <div class="row">
        <?php require_once __DIR__ . '/../layouts/sidebar.php'; ?>
        
        <main class="col-md-10 ms-sm-auto px-md-4">
            <div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2"><i class="bi bi-activity"></i> Laboratoire - Demandes</h1>
                
                <!-- Filtres -->
                <div class="btn-group shadow-sm">
                    <button type="button" class="btn btn-outline-primary active" onclick="filtrerExamens('all', this)">
                        Tous
                    </button>
                    <button type="button" class="btn btn-outline-warning" onclick="filtrerExamens('EN_ATTENTE', this)">
                        <i class="bi bi-hourglass-split"></i> En attente
                    </button>
                    <button type="button" class="btn btn-outline-info" onclick="filtrerExamens('EN_COURS', this)">
                        <i class="bi bi-play-circle"></i> En cours
                    </button>
                    <button type="button" class="btn btn-outline-success" onclick="filtrerExamens('TERMINE', this)">
                        <i class="bi bi-check-circle"></i> Terminés
                    </button>
                </div>
            </div>
            
            <div class="card shadow-sm border-0">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" id="tableExamens">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3">N°</th>
                                    <th>Patient</th>
                                    <th>Type d'examen</th>
                                    <th>Date demande</th>
                                    <th>Urgence</th>
                                    <th>Statut</th>
                                    <th class="text-end pe-3">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($demandes) > 0): ?>
                                    <?php foreach ($demandes as $demande): ?>
                                    <tr data-statut="<?= $demande['statut'] ?>">
                                        <td class="ps-3">
                                            <span class="badge bg-light text-dark border">
                                                #<?= str_pad($demande['id'], 5, '0', STR_PAD_LEFT) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="fw-bold"><?= htmlspecialchars($demande['nom'] . ' ' . $demande['prenom']) ?></div>
                                            <small class="text-muted">
                                                <i class="bi bi-folder"></i> <?= htmlspecialchars($demande['dossier_numero'] ?? '-') ?>
                                            </small>
                                        </td>
                                        <td>
                                            <div class="fw-bold text-primary">Demande laboratoire</div>
                                            <span class="badge bg-secondary rounded-pill" style="font-size: 0.7em;">
                                                <?= $demande['nb_examens'] ?> examen(s)
                                            </span>
                                        </td>
                                        <td>
                                            <?= date('d/m/Y', strtotime($demande['date_creation'])) ?><br>
                                            <small class="text-muted"><?= date('H:i', strtotime($demande['date_creation'])) ?></small>
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-secondary border">Normal</span>
                                        </td>
                                        <td>
                                            <?php
                                            $statutClass = [
                                                'EN_ATTENTE' => 'warning text-dark',
                                                'PRELEVEMENTS_EFFECTUES' => 'info text-dark', 
                                                'EN_ANALYSE' => 'info text-dark',
                                                'RESULTATS_PRETS' => 'success',
                                                'VALIDES' => 'success'
                                            ];
                                            $class = $statutClass[$demande['statut']] ?? 'secondary';
                                            $label = str_replace('_', ' ', $demande['statut']);
                                            ?>
                                            <span class="badge bg-<?= $class ?>">
                                                <?= ucfirst(strtolower($label)) ?>
                                            </span>
                                        </td>
                                        <td class="text-end pe-3">
                                            <div class="btn-group btn-group-sm">
                                                <a href="<?= BASE_URL ?>laboratoire/traitement/<?= $demande['id'] ?>" class="btn btn-outline-primary" title="Traiter la demande">
                                                    <i class="bi bi-play-fill"></i> Traiter
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" class="text-center text-muted py-5">
                                            <i class="bi bi-clipboard-x display-1 opacity-25"></i>
                                            <p class="mt-3 fs-5">Aucune demande d'examen trouvée</p>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
function filtrerExamens(statut, btn) {
    const rows = document.querySelectorAll('#tableExamens tbody tr');
    
    rows.forEach(row => {
        if (statut === 'all' || row.dataset.statut === statut) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
    
    // Mise à jour visuelle des boutons
    document.querySelectorAll('.btn-group .btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
}

function changerStatut(id, nouveauStatut) {
    if (confirm('Voulez-vous vraiment changer le statut de cet examen ?')) {
        // Assurez-vous d'avoir la route correspondante dans votre routeur
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '<?= BASE_URL ?>laboratoire/update-statut';
        
        const inputId = document.createElement('input');
        inputId.type = 'hidden';
        inputId.name = 'examen_id';
        inputId.value = id;
        
        const inputStatut = document.createElement('input');
        inputStatut.type = 'hidden';
        inputStatut.name = 'statut';
        inputStatut.value = nouveauStatut;
        
        form.appendChild(inputId);
        form.appendChild(inputStatut);
        document.body.appendChild(form);
        form.submit();
    }
}
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>