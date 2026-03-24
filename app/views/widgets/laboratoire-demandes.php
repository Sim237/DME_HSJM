<?php
// Widget demandes laboratoire en attente pour la sidebar
require_once __DIR__ . '/../../services/LaboratoireService.php';

$laboratoireService = new LaboratoireService();
$demandes_attente = $laboratoireService->getDemandesEnAttente();
$nb_demandes = count($demandes_attente);
?>

<div class="card border-info mb-3" id="widget-laboratoire">
    <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
        <h6 class="mb-0"><i class="bi bi-flask"></i> Laboratoire</h6>
        <?php if ($nb_demandes > 0): ?>
            <span class="badge bg-danger lab-badge"><?= $nb_demandes ?></span>
        <?php endif; ?>
    </div>
    <div class="card-body p-2">
        <?php if ($nb_demandes > 0): ?>
            <div class="alert alert-info py-2 mb-2">
                <strong><?= $nb_demandes ?></strong> demande(s) en attente
            </div>
            <div class="list-group list-group-flush">
                <?php foreach (array_slice($demandes_attente, 0, 3) as $demande): ?>
                <div class="list-group-item p-2 border-0">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <small class="fw-bold"><?= htmlspecialchars($demande['nom'] . ' ' . $demande['prenom']) ?></small><br>
                            <small class="text-muted"><?= $demande['nb_examens'] ?> examen(s) - <?= date('H:i', strtotime($demande['date_creation'])) ?></small>
                        </div>
                        <a href="<?= BASE_URL ?>laboratoire/traitement/<?= $demande['id'] ?>" class="btn btn-sm btn-outline-info">
                            <i class="bi bi-arrow-right"></i>
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php if ($nb_demandes > 3): ?>
                <div class="text-center mt-2">
                    <a href="<?= BASE_URL ?>laboratoire" class="btn btn-sm btn-info">
                        Voir toutes (<?= $nb_demandes ?>)
                    </a>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div class="text-center text-muted">
                <i class="bi bi-check-circle display-6"></i><br>
                <small>Aucune demande en attente</small>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
// Actualisation automatique toutes les 30 secondes
setInterval(function() {
    fetch('<?= BASE_URL ?>laboratoire/widget-demandes')
        .then(r => r.text())
        .then(html => {
            document.getElementById('widget-laboratoire').outerHTML = html;
        })
        .catch(console.error);
}, 30000);
</script>