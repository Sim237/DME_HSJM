<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div class="container-fluid">
    <div class="row">
        <?php require_once __DIR__ . '/../layouts/sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2"><i class="bi bi-camera-video"></i> Télémédecine</h1>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#sessionModal">
                    <i class="bi bi-plus"></i> Nouvelle Session
                </button>
            </div>

            <div class="row">
                <?php foreach ($sessions as $session): ?>
                <div class="col-md-6 mb-4">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between">
                            <span><?= $session['nom'] . ' ' . $session['prenom'] ?></span>
                            <span class="badge bg-<?= 
                                $session['statut'] === 'active' ? 'success' : 
                                ($session['statut'] === 'planifiee' ? 'warning' : 'secondary') 
                            ?>">
                                <?= ucfirst($session['statut']) ?>
                            </span>
                        </div>
                        <div class="card-body">
                            <p><strong>Médecin:</strong> Dr. <?= $session['medecin_nom'] ?></p>
                            <p><strong>Date:</strong> <?= date('d/m/Y H:i', strtotime($session['date_debut'])) ?></p>
                            <?php if ($session['duree_minutes']): ?>
                                <p><strong>Durée:</strong> <?= $session['duree_minutes'] ?> min</p>
                            <?php endif; ?>
                            
                            <?php if ($session['statut'] === 'planifiee' || $session['statut'] === 'active'): ?>
                                <a href="<?= BASE_URL ?>telemedecine/room/<?= $session['id'] ?>" class="btn btn-success">
                                    <i class="bi bi-camera-video"></i> Rejoindre
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </main>
    </div>
</div>

<!-- Modal Nouvelle Session -->
<div class="modal fade" id="sessionModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Nouvelle Session Télémédecine</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="sessionForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Patient</label>
                        <select class="form-select" name="patient_id" required>
                            <option value="">Sélectionner un patient</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Date/Heure</label>
                        <input type="datetime-local" class="form-control" name="date_debut" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">Créer Session</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.getElementById('sessionForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    fetch(`${BASE_URL}telemedecine/create-session`, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        }
    });
});
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>