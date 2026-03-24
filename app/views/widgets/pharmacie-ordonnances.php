<?php
// Widget ordonnances en attente pour la sidebar
require_once __DIR__ . '/../../services/PharmacieService.php';

$pharmacieService = new PharmacieService();
$ordonnances_attente = $pharmacieService->getOrdonnancesEnAttente();
$nb_ordonnances = count($ordonnances_attente);
?>

<div class="card border-warning mb-3" id="widget-pharmacie">
    <div class="card-header bg-warning text-dark d-flex justify-content-between align-items-center">
        <h6 class="mb-0"><i class="bi bi-capsule"></i> Pharmacie</h6>
        <?php if ($nb_ordonnances > 0): ?>
            <span class="badge bg-danger pharmacy-badge"><?= $nb_ordonnances ?></span>
        <?php endif; ?>
    </div>
    <div class="card-body p-2">
        <?php if ($nb_ordonnances > 0): ?>
            <div class="alert alert-warning py-2 mb-2">
                <strong><?= $nb_ordonnances ?></strong> ordonnance(s) en attente
            </div>
            <div class="list-group list-group-flush">
                <?php foreach (array_slice($ordonnances_attente, 0, 3) as $ord): ?>
                <div class="list-group-item p-2 border-0">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <small class="fw-bold"><?= htmlspecialchars($ord['nom'] . ' ' . $ord['prenom']) ?></small><br>
                            <small class="text-muted"><?= date('H:i', strtotime($ord['date_creation'])) ?></small>
                        </div>
                        <a href="<?= BASE_URL ?>pharmacie/traitement/<?= $ord['id'] ?>" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-arrow-right"></i>
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php if ($nb_ordonnances > 3): ?>
                <div class="text-center mt-2">
                    <a href="<?= BASE_URL ?>pharmacie/ordonnances" class="btn btn-sm btn-warning">
                        Voir toutes (<?= $nb_ordonnances ?>)
                    </a>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div class="text-center text-muted">
                <i class="bi bi-check-circle display-6"></i><br>
                <small>Aucune ordonnance en attente</small>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
// Actualisation automatique toutes les 30 secondes
setInterval(function() {
    fetch('<?= BASE_URL ?>pharmacie/widget-ordonnances')
        .then(r => r.text())
        .then(html => {
            document.getElementById('widget-pharmacie').outerHTML = html;
        })
        .catch(console.error);
}, 30000);

// Demander permission notifications
if ('Notification' in window && Notification.permission === 'default') {
    Notification.requestPermission();
}
</script>