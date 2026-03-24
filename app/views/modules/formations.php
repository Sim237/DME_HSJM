<!DOCTYPE html>
<html>
<head>
    <title>Formations Personnel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-3">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3>Formations Personnel</h3>
            <?php if(isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                <a href="/modules/formation/creer" class="btn btn-primary">Créer Formation</a>
            <?php endif; ?>
        </div>
        
        <div class="row">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5>Formations Disponibles</h5>
                    </div>
                    <div class="card-body">
                        <?php foreach($formations as $formation): ?>
                            <div class="border-bottom py-3">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6><?= $formation['titre'] ?></h6>
                                        <p class="text-muted mb-1"><?= $formation['description'] ?></p>
                                        <div class="small">
                                            <span class="badge bg-secondary"><?= $formation['categorie'] ?></span>
                                            <span class="badge bg-info"><?= $formation['duree_heures'] ?>h</span>
                                            <?php if($formation['obligatoire']): ?>
                                                <span class="badge bg-danger">Obligatoire</span>
                                            <?php endif; ?>
                                        </div>
                                        <?php if($formation['prochaine_session']): ?>
                                            <div class="small text-success mt-1">
                                                Prochaine session: <?= date('d/m/Y', strtotime($formation['prochaine_session'])) ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <?php if($formation['sessions_disponibles'] > 0): ?>
                                        <button class="btn btn-sm btn-outline-primary" 
                                                onclick="inscrire(<?= $formation['id'] ?>)">
                                            S'inscrire
                                        </button>
                                    <?php else: ?>
                                        <span class="text-muted">Aucune session</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5>Mes Formations</h5>
                    </div>
                    <div class="card-body">
                        <?php foreach($mesFormations as $formation): ?>
                            <div class="border-bottom py-2">
                                <strong><?= $formation['titre'] ?></strong>
                                <div class="small text-muted">
                                    <?= date('d/m/Y', strtotime($formation['date_debut'])) ?>
                                </div>
                                <div>
                                    <?php
                                    $badges = [
                                        'inscrit' => 'bg-warning',
                                        'present' => 'bg-info', 
                                        'absent' => 'bg-danger',
                                        'valide' => 'bg-success'
                                    ];
                                    ?>
                                    <span class="badge <?= $badges[$formation['statut']] ?>">
                                        <?= ucfirst($formation['statut']) ?>
                                    </span>
                                    <?php if($formation['note']): ?>
                                        <span class="small">Note: <?= $formation['note'] ?>/20</span>
                                    <?php endif; ?>
                                    <?php if($formation['certificat_genere']): ?>
                                        <span class="badge bg-success">Certifié</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <div class="card mt-3">
                    <div class="card-header">
                        <h6>Statistiques</h6>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-6">
                                <h4 class="text-primary"><?= count($mesFormations) ?></h4>
                                <small>Formations suivies</small>
                            </div>
                            <div class="col-6">
                                <h4 class="text-success">
                                    <?= count(array_filter($mesFormations, fn($f) => $f['certificat_genere'])) ?>
                                </h4>
                                <small>Certifiées</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function inscrire(formationId) {
            // Simuler inscription à la première session disponible
            fetch(`/modules/formation/inscrire/${formationId}`, {
                method: 'POST'
            }).then(r => r.json()).then(result => {
                if (result.success) {
                    alert('Inscription réussie!');
                    location.reload();
                } else {
                    alert('Erreur: Plus de places disponibles');
                }
            });
        }
    </script>
</body>
</html>