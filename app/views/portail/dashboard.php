<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon Espace Patient - DME Hospital</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container">
        <a class="navbar-brand" href="#"><i class="fas fa-hospital"></i> DME Hospital</a>
        <div class="navbar-nav ms-auto">
            <span class="navbar-text me-3">Bonjour, <?= $_SESSION['patient_name'] ?></span>
            <a class="nav-link" href="<?= BASE_URL ?>portail/logout">Déconnexion</a>
        </div>
    </div>
</nav>

<div class="container mt-4">
    <div class="row">
        <!-- Menu latéral -->
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title">Mon Espace</h6>
                    <div class="list-group list-group-flush">
                        <a href="<?= BASE_URL ?>portail/dashboard" class="list-group-item active">
                            <i class="fas fa-tachometer-alt me-2"></i> Tableau de bord
                        </a>
                        <a href="<?= BASE_URL ?>portail/dossier" class="list-group-item">
                            <i class="fas fa-folder-medical me-2"></i> Mon dossier
                        </a>
                        <a href="<?= BASE_URL ?>portail/rdv" class="list-group-item">
                            <i class="fas fa-calendar me-2"></i> Mes RDV
                        </a>
                        <a href="<?= BASE_URL ?>portail/traitements" class="list-group-item">
                            <i class="fas fa-pills me-2"></i> Mes traitements
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Contenu principal -->
        <div class="col-md-9">
            <!-- Prochains RDV -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5><i class="fas fa-calendar-check"></i> Mes prochains rendez-vous</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($rdvs)): ?>
                        <p class="text-muted">Aucun rendez-vous programmé</p>
                        <a href="<?= BASE_URL ?>portail/rdv" class="btn btn-primary">Prendre un RDV</a>
                    <?php else: ?>
                        <?php foreach ($rdvs as $rdv): ?>
                            <div class="d-flex justify-content-between align-items-center border-bottom py-2">
                                <div>
                                    <strong><?= date('d/m/Y à H:i', strtotime($rdv['date_rdv'])) ?></strong><br>
                                    <small>Dr. <?= $rdv['medecin_nom'] . ' ' . $rdv['medecin_prenom'] ?></small><br>
                                    <small class="text-muted"><?= $rdv['motif'] ?></small>
                                </div>
                                <span class="badge bg-<?= $rdv['statut'] == 'CONFIRME' ? 'success' : 'warning' ?>">
                                    <?= $rdv['statut'] ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Traitements en cours -->
            <div class="card mb-4">
                <div class="card-header bg-success text-white">
                    <h5><i class="fas fa-pills"></i> Mes traitements en cours</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($traitements)): ?>
                        <p class="text-muted">Aucun traitement en cours</p>
                    <?php else: ?>
                        <?php foreach ($traitements as $traitement): ?>
                            <div class="treatment-card">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h6><?= $traitement['medicament'] ?></h6>
                                        <p class="mb-1"><?= $traitement['dosage'] ?> - <?= $traitement['frequence'] ?></p>
                                        <small class="text-muted">
                                            Du <?= date('d/m/Y', strtotime($traitement['date_debut'])) ?>
                                            <?= $traitement['date_fin'] ? 'au ' . date('d/m/Y', strtotime($traitement['date_fin'])) : '' ?>
                                        </small>
                                    </div>
                                    <div class="text-end">
                                        <button class="btn btn-sm btn-outline-primary" onclick="marquerPris(<?= $traitement['id'] ?>)">
                                            <i class="fas fa-check"></i> Pris
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Rappels du jour -->
            <div class="card">
                <div class="card-header bg-warning text-dark">
                    <h5><i class="fas fa-bell"></i> Rappels d'aujourd'hui</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($rappels)): ?>
                        <p class="text-muted">Aucun rappel pour aujourd'hui</p>
                    <?php else: ?>
                        <?php foreach ($rappels as $rappel): ?>
                            <div class="alert alert-info">
                                <strong><?= $rappel['titre'] ?></strong><br>
                                <?= $rappel['message'] ?><br>
                                <small><?= date('H:i', strtotime($rappel['date_rappel'])) ?></small>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.treatment-card {
    border: 1px solid #dee2e6;
    border-radius: 8px;
    padding: 1rem;
    margin-bottom: 0.5rem;
    background: #f8f9fa;
}

.list-group-item.active {
    background-color: #0d6efd;
    border-color: #0d6efd;
}
</style>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function marquerPris(traitementId) {
    // Logique pour marquer le traitement comme pris
    alert('Traitement marqué comme pris');
}
</script>

</body>
</html>