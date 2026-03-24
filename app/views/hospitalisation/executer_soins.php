<?php require_once __DIR__ . '/../layouts/header.php'; ?>
<div class="container py-5">
    <div class="card border-0 shadow-lg rounded-4 overflow-hidden">
        <div class="card-header bg-primary text-white p-4">
            <h4 class="mb-0">Exécution des Soins : <?= $soins[0]['nom'] ?></h4>
            <small>Cochez les soins effectués et cliquez sur Terminé</small>
        </div>
        <form action="<?= BASE_URL ?>hospitalisation/valider-execution" method="POST">
            <div class="card-body p-4">
                <div class="list-group">
                    <?php foreach($soins as $s): ?>
                        <label class="list-group-item d-flex justify-content-between align-items-center p-3">
                            <div class="d-flex align-items-center">
                                <input class="form-check-input me-3" type="checkbox" name="soins_faits[]" value="<?= $s['id'] ?>" <?= $s['execute'] ? 'checked disabled' : '' ?>>
                                <div>
                                    <span class="badge bg-dark me-2"><?= substr($s['heure'], 0, 5) ?></span>
                                    <span class="fw-bold"><?= $s['soin_description'] ?></span>
                                    <small class="text-muted d-block"><?= $s['categorie'] ?></small>
                                </div>
                            </div>
                            <?php if($s['execute']): ?>
                                <span class="badge bg-success">Effectué</span>
                            <?php endif; ?>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="card-footer bg-light p-4 text-end">
                <a href="<?= BASE_URL ?>dashboard" class="btn btn-link text-muted">Annuler</a>
                <button type="submit" class="btn btn-success btn-lg px-5 rounded-pill shadow">TERMINÉ</button>
            </div>
        </form>
    </div>
</div>
<?php require_once __DIR__ . '/../layouts/footer.php'; ?>