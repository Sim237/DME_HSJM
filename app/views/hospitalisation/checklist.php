<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div class="container-fluid">
    <div class="row">
        <?php require_once __DIR__ . '/../layouts/sidebar.php'; ?>

        <main class="col-md-10 ms-sm-auto px-md-4 py-4">
            <div class="d-flex justify-content-between align-items-center border-bottom pb-2">
                <!-- Utilisation de ?? 'Inconnu' pour éviter le Warning -->
                <h4 class="fw-bold mb-0">
                    <i class="bi bi-check2-all text-success me-2"></i>
                    CHECKLIST SOINS : <?= htmlspecialchars(($patient['nom'] ?? 'Patient') . ' ' . ($patient['prenom'] ?? '')) ?>
                </h4>
                <a href="<?= BASE_URL ?>dashboard" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-arrow-left"></i> Retour
                </a>
            </div>

            <div class="list-group mt-4 shadow-sm">
                <?php if (empty($soins)): ?>
                    <div class="alert alert-info text-center">Aucun soin n'a été planifié pour ce patient.</div>
                <?php else: ?>
                    <?php foreach($soins as $s): ?>
                        <div class="list-group-item d-flex justify-content-between align-items-center p-3">
                            <div class="<?= $s['execute'] ? 'text-decoration-line-through text-muted' : '' ?>">
                                <span class="badge bg-dark me-2"><?= substr($s['heure'], 0, 5) ?></span>
                                <span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25 me-2"><?= $s['categorie'] ?></span>
                                <strong class="fs-5"><?= htmlspecialchars($s['soin_description']) ?></strong>
                            </div>
                            <div>
                                <?php if(!$s['execute']): ?>
                                    <button class="btn btn-success rounded-pill px-4" onclick="validerSoin(<?= $s['id'] ?>)">
                                        <i class="bi bi-check-lg"></i> Valider
                                    </button>
                                <?php else: ?>
                                    <span class="badge bg-success-subtle text-success p-2">
                                        <i class="bi bi-person-check me-1"></i> Fait à <?= date('H:i', strtotime($s['date_execution'])) ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </main>
    </div>
</div>

<script>
function validerSoin(id) {
    if(!confirm("Confirmer la réalisation de ce soin ?")) return;

    // Utilisation de FormData pour correspondre à votre contrôleur
    const params = new URLSearchParams();
    params.append('id', id);

    fetch('<?= BASE_URL ?>hospitalisation/valider-soin', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: params
    })
    .then(response => response.json())
    .then(data => {
        if(data.success) {
            location.reload();
        } else {
            alert("Erreur lors de la validation");
        }
    })
    .catch(err => console.error("Erreur:", err));
}
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>