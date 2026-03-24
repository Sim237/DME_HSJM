<?php include __DIR__ . '/../layouts/header.php'; ?>

<div class="container-fluid">
    <div class="row">
        <?php include __DIR__ . '/../layouts/sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-0">
            <div class="main-content">
                <?php require_once __DIR__ . '/../layouts/topbar.php'; ?>
                
                <div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-4">
                    <h1 class="h2"><i class="bi bi-bank text-danger me-2"></i>Banques de Sang</h1>
                    <div class="btn-group">
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#compatibiliteModal">
                            <i class="bi bi-search me-1"></i>Vérifier Compatibilité
                        </button>
                    </div>
                </div>

                <!-- Alertes stock faible -->
                <?php if (!empty($alertes)): ?>
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        <strong>Stock faible :</strong> 
                        <?php foreach ($alertes as $alerte): ?>
                            <?php echo $alerte['groupe_sanguin'] . $alerte['rhesus']; ?> (<?php echo $alerte['quantite_ml']; ?>ml), 
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <!-- Stock actuel -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Stock Disponible</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <?php foreach ($stock as $groupe): ?>
                                <div class="col-md-3 mb-3">
                                    <div class="card border-<?php echo $groupe['quantite_ml'] < 1000 ? 'danger' : 'success'; ?>">
                                        <div class="card-body text-center">
                                            <h2 class="text-<?php echo $groupe['quantite_ml'] < 1000 ? 'danger' : 'success'; ?>">
                                                <?php echo $groupe['groupe_sanguin'] . $groupe['rhesus']; ?>
                                            </h2>
                                            <p class="mb-1"><?php echo $groupe['quantite_poches']; ?> poches</p>
                                            <p class="mb-0"><?php echo $groupe['quantite_ml']; ?> ml</p>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Modal Vérification Compatibilité -->
<div class="modal fade" id="compatibiliteModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Vérifier Compatibilité Sanguine</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="compatibiliteForm">
                    <div class="row">
                        <div class="col-md-6">
                            <label class="form-label">Groupe sanguin receveur</label>
                            <select class="form-select" name="groupe_receveur" required>
                                <option value="">Sélectionner</option>
                                <option value="A">A</option>
                                <option value="B">B</option>
                                <option value="AB">AB</option>
                                <option value="O">O</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Rhésus receveur</label>
                            <select class="form-select" name="rhesus_receveur" required>
                                <option value="">Sélectionner</option>
                                <option value="+">Positif (+)</option>
                                <option value="-">Négatif (-)</option>
                            </select>
                        </div>
                    </div>
                    <div class="mt-3">
                        <button type="submit" class="btn btn-primary">Vérifier</button>
                    </div>
                </form>
                <div id="resultatsCompatibilite" class="mt-3"></div>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('compatibiliteForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const groupe = document.querySelector('[name="groupe_receveur"]').value;
    const rhesus = document.querySelector('[name="rhesus_receveur"]').value;
    
    if (!groupe || !rhesus) return;
    
    fetch('<?php echo BASE_URL; ?>registres/verifier-compatibilite?groupe=' + groupe + '&rhesus=' + rhesus)
        .then(response => response.json())
        .then(data => {
            let html = '';
            if (data.length === 0) {
                html = '<div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-2"></i><strong>Aucun sang compatible disponible</strong> pour ' + groupe + rhesus + '</div>';
            } else {
                html = '<div class="alert alert-success"><i class="bi bi-check-circle me-2"></i><strong>Sang compatible disponible :</strong></div>';
                html += '<div class="list-group">';
                data.forEach(item => {
                    html += '<div class="list-group-item d-flex justify-content-between align-items-center">';
                    html += '<span>' + item.groupe_sanguin + item.rhesus + '</span>';
                    html += '<span class="badge bg-primary">' + item.quantite_poches + ' poches (' + item.quantite_ml + 'ml)</span>';
                    html += '</div>';
                });
                html += '</div>';
            }
            document.getElementById('resultatsCompatibilite').innerHTML = html;
        });
});
</script>

<?php include __DIR__ . '/../layouts/footer.php'; ?>