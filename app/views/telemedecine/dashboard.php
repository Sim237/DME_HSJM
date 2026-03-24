<?php include __DIR__ . '/../layouts/header.php'; ?>

<div class="main-content">
    <div class="container-fluid p-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="mb-1"><i class="fas fa-video text-primary me-2"></i>Télémédecine</h2>
                <p class="text-muted mb-0">Consultations à distance et surveillance</p>
            </div>
            <div>
                <a href="<?= BASE_URL ?>telemedecine/planifier" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>Nouvelle Consultation
                </a>
            </div>
        </div>

        <!-- Alertes -->
        <?php if (!empty($alertes)): ?>
        <div class="alert alert-warning mb-4">
            <h5><i class="fas fa-exclamation-triangle me-2"></i>Alertes Surveillance</h5>
            <?php foreach ($alertes as $alerte): ?>
            <div class="d-flex justify-content-between align-items-center border-bottom py-2">
                <div>
                    <strong><?= $alerte['nom'] ?> <?= $alerte['prenom'] ?></strong>
                    <span class="badge bg-danger ms-2"><?= ucfirst($alerte['type_donnee']) ?></span>
                </div>
                <div>
                    <span class="text-danger fw-bold"><?= $alerte['valeur'] ?> <?= $alerte['unite'] ?></span>
                    <small class="text-muted ms-2"><?= date('H:i', strtotime($alerte['date_mesure'])) ?></small>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Consultations du jour -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-calendar-day me-2"></i>Consultations Aujourd'hui</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($consultations)): ?>
                        <div class="text-center py-4">
                            <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                            <p class="text-muted">Aucune consultation prévue aujourd'hui</p>
                        </div>
                        <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Heure</th>
                                        <th>Patient</th>
                                        <th>Type</th>
                                        <th>Motif</th>
                                        <th>Statut</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($consultations as $consultation): ?>
                                    <tr>
                                        <td><?= date('H:i', strtotime($consultation['date_consultation'])) ?></td>
                                        <td>
                                            <strong><?= $consultation['nom'] ?> <?= $consultation['prenom'] ?></strong>
                                        </td>
                                        <td>
                                            <span class="badge bg-info">
                                                <i class="fas fa-<?= $consultation['type'] === 'video' ? 'video' : ($consultation['type'] === 'audio' ? 'microphone' : 'comments') ?> me-1"></i>
                                                <?= ucfirst($consultation['type']) ?>
                                            </span>
                                        </td>
                                        <td><?= substr($consultation['motif'], 0, 50) ?>...</td>
                                        <td>
                                            <?php
                                            $badges = [
                                                'planifie' => 'bg-secondary',
                                                'en_cours' => 'bg-warning',
                                                'termine' => 'bg-success',
                                                'annule' => 'bg-danger'
                                            ];
                                            ?>
                                            <span class="badge <?= $badges[$consultation['statut']] ?>">
                                                <?= ucfirst(str_replace('_', ' ', $consultation['statut'])) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($consultation['statut'] === 'planifie'): ?>
                                            <a href="<?= BASE_URL ?>telemedecine/consultation/<?= $consultation['id'] ?>" 
                                               class="btn btn-sm btn-primary">
                                                <i class="fas fa-play me-1"></i>Démarrer
                                            </a>
                                            <?php elseif ($consultation['statut'] === 'en_cours'): ?>
                                            <a href="<?= $consultation['lien_reunion'] ?>" 
                                               target="_blank" class="btn btn-sm btn-success">
                                                <i class="fas fa-external-link-alt me-1"></i>Rejoindre
                                            </a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Actions rapides -->
        <div class="row mt-4">
            <div class="col-md-4">
                <div class="card text-center">
                    <div class="card-body">
                        <i class="fas fa-heartbeat fa-3x text-danger mb-3"></i>
                        <h5>Surveillance</h5>
                        <p class="text-muted">Suivi des paramètres vitaux</p>
                        <a href="<?= BASE_URL ?>telemedecine/surveillance" class="btn btn-danger">
                            Accéder
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-center">
                    <div class="card-body">
                        <i class="fas fa-file-medical fa-3x text-primary mb-3"></i>
                        <h5>Documents</h5>
                        <p class="text-muted">Partage de fichiers médicaux</p>
                        <a href="<?= BASE_URL ?>telemedecine/documents" class="btn btn-primary">
                            Gérer
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-center">
                    <div class="card-body">
                        <i class="fas fa-chart-line fa-3x text-success mb-3"></i>
                        <h5>Rapports</h5>
                        <p class="text-muted">Statistiques télémédecine</p>
                        <a href="<?= BASE_URL ?>telemedecine/rapports" class="btn btn-success">
                            Voir
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../layouts/sidebar.php'; ?>
<?php include __DIR__ . '/../layouts/footer.php'; ?>