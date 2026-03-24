<?php
require_once __DIR__ . '/../layouts/header.php';
$demandes = $demandes ?? [];
?>

<div class="container-fluid">
    <div class="row">
        <?php require_once __DIR__ . '/../layouts/sidebar.php'; ?>
        
        <main class="col-md-10 ms-sm-auto px-md-4">
            <div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2"><i class="bi bi-flask"></i> Demandes d'Examens</h1>
                <div>
                    <span class="badge bg-info me-2"><?= count($demandes) ?> demande(s)</span>
                    <a href="<?= BASE_URL ?>laboratoire/planning" class="btn btn-outline-secondary">
                        <i class="bi bi-calendar"></i> Planning
                    </a>
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Date</th>
                                    <th>Patient</th>
                                    <th>Dossier</th>
                                    <th>Médecin</th>
                                    <th>Examens</th>
                                    <th>Statut</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(count($demandes) > 0): ?>
                                    <?php foreach ($demandes as $demande): ?>
                                    <tr>
                                        <td>
                                            <?= date('d/m/Y', strtotime($demande['date_creation'])) ?><br>
                                            <small class="text-muted"><?= date('H:i', strtotime($demande['date_creation'])) ?></small>
                                        </td>
                                        <td class="fw-bold"><?= htmlspecialchars($demande['nom'] . ' ' . $demande['prenom']) ?></td>
                                        <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($demande['dossier_numero']) ?></span></td>
                                        <td>Dr. <?= htmlspecialchars($demande['medecin_nom'] . ' ' . $demande['medecin_prenom']) ?></td>
                                        <td>
                                            <span class="badge bg-info"><?= $demande['nb_examens'] ?> examen(s)</span>
                                            <?php
                                            // Vérifier s'il y a des examens urgents
                                            $stmt = $GLOBALS['db']->prepare("SELECT COUNT(*) as nb_urgent FROM demande_examens WHERE demande_id = ? AND urgent = 1");
                                            $stmt->execute([$demande['id']]);
                                            $nb_urgent = $stmt->fetch()['nb_urgent'] ?? 0;
                                            if ($nb_urgent > 0):
                                            ?>
                                                <br><span class="badge bg-danger">⚡ <?= $nb_urgent ?> urgent(s)</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php
                                            $statut_class = [
                                                'EN_ATTENTE' => 'warning',
                                                'PRELEVEMENTS_EFFECTUES' => 'info', 
                                                'EN_ANALYSE' => 'primary',
                                                'RESULTATS_PRETS' => 'success'
                                            ];
                                            $statut_text = [
                                                'EN_ATTENTE' => 'En attente',
                                                'PRELEVEMENTS_EFFECTUES' => 'Prélevé',
                                                'EN_ANALYSE' => 'En analyse', 
                                                'RESULTATS_PRETS' => 'Résultats prêts'
                                            ];
                                            ?>
                                            <span class="badge bg-<?= $statut_class[$demande['statut']] ?>">
                                                <?= $statut_text[$demande['statut']] ?>
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            <div class="btn-group btn-group-sm">
                                                <a href="<?= BASE_URL ?>laboratoire/traitement/<?= $demande['id'] ?>" class="btn btn-primary">
                                                    <i class="bi bi-flask"></i> Traiter
                                                </a>
                                                <button class="btn btn-outline-secondary dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown">
                                                    <span class="visually-hidden">Toggle Dropdown</span>
                                                </button>
                                                <ul class="dropdown-menu">
                                                    <li><a class="dropdown-item" href="#"><i class="bi bi-eye"></i> Voir détails</a></li>
                                                    <li><a class="dropdown-item" href="#"><i class="bi bi-printer"></i> Étiquettes</a></li>
                                                </ul>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" class="text-center py-5 text-muted">
                                            <i class="bi bi-check2-circle display-4"></i><br>
                                            Aucune demande d'examen en attente.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>