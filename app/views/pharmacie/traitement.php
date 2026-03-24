<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div class="app-wrapper" style="display: flex; background: #f8fafc; min-height: 100vh;">
    <?php require_once __DIR__ . '/../layouts/sidebar.php'; ?>

    <main class="main-content w-100 p-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3 class="fw-bold">Préparation Ordonnance #<?= $ordonnance['id'] ?></h3>
            <span class="badge bg-warning text-dark px-3 py-2 rounded-pill">VÉRIFICATION SÉCURITÉ</span>
        </div>

        <div class="row g-4">
            <!-- CONTENU DE LA PRESCRIPTION (DYNAMIQUE) -->
            <div class="col-md-8">
                <div class="card border-0 shadow-sm rounded-4">
                    <div class="card-header bg-white fw-bold py-3 border-bottom">
                        Patient : <?= strtoupper($ordonnance['patient_nom']) ?> <?= $ordonnance['patient_prenom'] ?>
                        <small class="text-muted ms-2">(<?= $ordonnance['dossier_numero'] ?>)</small>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="bg-light small text-muted">
                                    <tr>
                                        <th class="ps-4">MÉDICAMENT</th>
                                        <th>POSOLOGIE</th>
                                        <th>DURÉE</th>
                                        <th class="text-center">STOCK</th>
                                    </tr>
                                </thead>
                                <!-- Dans le <tbody> de traitement.php, remplacez par : -->
<tbody>
    <?php if(empty($lignes)): ?>
        <tr><td colspan="4" class="text-center py-4">Aucun médicament trouvé pour cette ordonnance.</td></tr>
    <?php else: foreach($lignes as $l): ?>
    <tr>
        <td class="ps-4">
            <strong><?= htmlspecialchars($l['nom_medicament'] ?: $l['designation_stock']) ?></strong>
            <?php if(!$l['medicament_id']): ?>
                <br><span class="badge bg-secondary" style="font-size: 0.6rem;">HORS STOCK</span>
            <?php endif; ?>
        </td>
        <td><?= htmlspecialchars($l['posologie']) ?></td>
        <td><?= htmlspecialchars($l['duree']) ?></td>
        <td class="text-center">
            <?php if($l['medicament_id']): ?>
                <span class="badge <?= ($l['stock_actuel'] > 0) ? 'bg-success' : 'bg-danger' ?>">
                    <?= $l['stock_actuel'] ?> DISPO.
                </span>
            <?php else: ?>
                <span class="text-muted small">N/A</span>
            <?php endif; ?>
        </td>
    </tr>
    <?php endforeach; endif; ?>
</tbody>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- PANNEAU LATÉRAL (ALLERGIES RÉELLES) -->
            <div class="col-md-4">
                <!-- BLOC ALLERGIES CORRIGÉ -->
                <div class="card border-0 shadow-sm rounded-4 mb-4 border-start border-5 border-danger">
                    <div class="card-body">
                        <h6 class="fw-bold text-danger"><i class="bi bi-exclamation-triangle-fill me-2"></i>ALERTES ALLERGIES</h6>
                        <p class="mb-0">
                            <?php if(!empty($ordonnance['allergies'])): ?>
                                Le patient est allergique à : <br>
                                <strong class="fs-5 text-dark"><?= nl2br(htmlspecialchars($ordonnance['allergies'])) ?></strong>
                            <?php else: ?>
                                <span class="text-success fw-bold">Aucune allergie connue pour ce patient.</span>
                            <?php endif; ?>
                        </p>
                    </div>
                </div>

                <!-- VALIDATION FINALE -->
                <div class="card border-0 shadow-sm rounded-4 p-4 bg-primary text-white">
                    <h5 class="fw-bold mb-3">Validation Finale</h5>
                    <p class="small opacity-75">En cliquant sur le bouton, vous confirmez que vous avez préparé les médicaments et vérifié les interactions.</p>
                    <button class="btn btn-light w-100 fw-bold py-3 rounded-pill shadow-sm" onclick="confirmDeliver(<?= $ordonnance['id'] ?>)">
                        <i class="bi bi-check2-circle me-2"></i>DÉLIVRER LES MÉDICAMENTS
                    </button>
                    <div class="mt-3 text-center">
                        <a href="<?= BASE_URL ?>pharmacie" class="text-white small text-decoration-none">Annuler et revenir</a>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<script>
function confirmDeliver(id) {
    if(!confirm("Valider la sortie de stock et clôturer cette ordonnance ?")) return;

    const formData = new FormData();
    formData.append('id', id);

    fetch('<?= BASE_URL ?>pharmacie/delivrer', { method: 'POST', body: formData })
    .then(res => res.json())
    .then(data => {
        if(data.success) {
            alert("✅ Ordonnance traitée avec succès !");
            window.location.href = '<?= BASE_URL ?>pharmacie';
        } else {
            alert("❌ Erreur : " + data.message);
        }
    });
}
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>