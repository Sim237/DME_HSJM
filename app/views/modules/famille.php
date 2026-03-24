<!DOCTYPE html>
<html>
<head>
    <title>Gestion Famille</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-3">
        <h3>Gestion Famille - Patient #<?= $patient_id ?></h3>
        
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header d-flex justify-content-between">
                        <h5>Membres de la famille</h5>
                        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#ajouterMembreModal">Ajouter</button>
                    </div>
                    <div class="card-body">
                        <?php foreach($membres as $membre): ?>
                            <div class="border-bottom py-2">
                                <strong><?= $membre['nom'] ?> <?= $membre['prenom'] ?></strong>
                                <span class="badge bg-secondary"><?= $membre['relation'] ?></span>
                                <?php if($membre['contact_urgence']): ?>
                                    <span class="badge bg-danger">Contact urgence</span>
                                <?php endif; ?>
                                <div class="small text-muted">
                                    <?= $membre['telephone'] ?> - <?= $membre['email'] ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header d-flex justify-content-between">
                        <h5>Visites programmées</h5>
                        <button class="btn btn-sm btn-success" onclick="planifierVisite()">Planifier</button>
                    </div>
                    <div class="card-body">
                        <?php foreach($visites as $visite): ?>
                            <div class="border-bottom py-2">
                                <strong><?= $visite['nom_visiteur'] ?></strong>
                                <span class="badge bg-info"><?= $visite['relation'] ?></span>
                                <div class="small">
                                    <?= date('d/m/Y H:i', strtotime($visite['date_visite'])) ?>
                                    (<?= $visite['duree_minutes'] ?> min)
                                </div>
                                <?php if(!$visite['autorise']): ?>
                                    <span class="badge bg-warning">En attente</span>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Ajouter Membre -->
    <div class="modal fade" id="ajouterMembreModal">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="/modules/famille/ajouter">
                    <div class="modal-header">
                        <h5>Ajouter un membre</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="patient_id" value="<?= $patient_id ?>">
                        <div class="mb-3">
                            <label class="form-label">Nom</label>
                            <input type="text" class="form-control" name="nom" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Prénom</label>
                            <input type="text" class="form-control" name="prenom" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Relation</label>
                            <select class="form-control" name="relation" required>
                                <option value="pere">Père</option>
                                <option value="mere">Mère</option>
                                <option value="conjoint">Conjoint(e)</option>
                                <option value="enfant">Enfant</option>
                                <option value="frere">Frère</option>
                                <option value="soeur">Sœur</option>
                                <option value="autre">Autre</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Téléphone</label>
                            <input type="tel" class="form-control" name="telephone">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email">
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="contact_urgence" value="1">
                            <label class="form-check-label">Contact d'urgence</label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary">Ajouter</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function planifierVisite() {
            const nom = prompt('Nom du visiteur:');
            const relation = prompt('Relation:');
            const date = prompt('Date et heure (YYYY-MM-DD HH:MM):');
            
            if (nom && relation && date) {
                fetch('/modules/visite/planifier', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: `patient_id=<?= $patient_id ?>&nom_visiteur=${nom}&relation=${relation}&date_visite=${date}`
                }).then(r => r.json()).then(result => {
                    if (result.success) {
                        location.reload();
                    }
                });
            }
        }
    </script>
</body>
</html>