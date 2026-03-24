<?php
/* ============================================================================
FICHIER : app/views/laboratoire/traitement.php
============================================================================ */
require_once __DIR__ . '/../layouts/header.php';

// Sécurisation : $demande et $examens sont fournis par le LaboratoireController
if (!isset($demande) || !$demande) {
    echo "<div class='alert alert-danger m-4'>Erreur : La demande est introuvable ou les données sont corrompues.</div>";
    return;
}
?>

<div class="container-fluid bg-light" style="min-height: 100vh;">
    <div class="row">
        <?php require_once __DIR__ . '/../layouts/sidebar.php'; ?>

        <main class="col-md-10 ms-sm-auto px-md-4">
            <div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h3 fw-bold"><i class="bi bi-flask text-primary"></i> Traitement de la Demande</h1>
                <a href="<?= BASE_URL ?>laboratoire" class="btn btn-outline-secondary btn-sm rounded-pill">
                    <i class="bi bi-arrow-left"></i> Retour au Dashboard
                </a>
            </div>

            <!-- FICHE PATIENT (Header Bleu) -->
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
                <div class="card-header bg-info text-white p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 fw-bold">
                            <i class="bi bi-person-circle me-2"></i>
                            PATIENT : <?= htmlspecialchars($demande['nom'] . ' ' . $demande['prenom']) ?>
                        </h5>
                        <span class="badge bg-white text-info fw-bold px-3"><?= htmlspecialchars($demande['dossier_numero']) ?></span>
                    </div>
                </div>
                <div class="card-body bg-white p-4">
                    <div class="row g-4">
                        <div class="col-md-4 border-end">
                            <label class="text-muted small text-uppercase fw-bold d-block mb-1">Médecin Prescripteur</label>
                            <div class="fw-bold text-dark">Dr. <?= htmlspecialchars($demande['medecin_nom'] . ' ' . $demande['medecin_prenom']) ?></div>
                        </div>
                        <div class="col-md-4 border-end">
                            <label class="text-muted small text-uppercase fw-bold d-block mb-1">Date de la demande</label>
                            <div class="fw-bold text-dark"><?= date('d/m/Y à H:i', strtotime($demande['date_creation'])) ?></div>
                        </div>
                        <div class="col-md-4">
                            <label class="text-muted small text-uppercase fw-bold d-block mb-1">Statut Actuel</label>
                            <span class="badge bg-warning text-dark text-uppercase px-3"><?= str_replace('_', ' ', $demande['statut']) ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- LISTE DES EXAMENS À RÉALISER -->
            <form action="<?= BASE_URL ?>laboratoire/traiter-examens" method="POST">
                <input type="hidden" name="demande_id" value="<?= $demande['id'] ?>">

                <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                    <div class="card-header bg-primary text-white py-3">
                        <h6 class="mb-0 fw-bold"><i class="bi bi-list-check me-2"></i>Examens à réaliser et prélèvements</h6>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="bg-light">
                                    <tr class="small text-uppercase text-muted">
                                        <th class="ps-4">Examen</th>
                                        <th>Catégorie</th>
                                        <th>Prélèvement</th>
                                        <th>Délai</th>
                                        <th>Priorité</th>
                                        <th>Statut</th>
                                        <th class="text-end pe-4">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($examens as $examen): ?>
                                    <tr class="<?= $examen['urgent'] ? 'table-danger-subtle' : '' ?>">
                                        <td class="ps-4 fw-bold">
                                            <?= htmlspecialchars($examen['nom']) ?>
                                            <?php if ($examen['a_jeun_requis']): ?>
                                                <div class="text-warning small mt-1"><i class="bi bi-clock-history"></i> À jeun requis</div>
                                            <?php endif; ?>
                                        </td>
                                        <td><span class="badge bg-secondary-subtle text-secondary border"><?= $examen['categorie'] ?></span></td>
                                        <td><i class="bi bi-droplet-fill text-danger me-1"></i><?= $examen['type_prelevement'] ?></td>
                                        <td><?= $examen['delai_rendu_heures'] ?>h</td>
                                        <td>
                                            <?php if($examen['urgent']): ?>
                                                <span class="badge bg-danger animate__animated animate__pulse animate__infinite">URGENT</span>
                                            <?php else: ?>
                                                <span class="badge bg-success">Normal</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <select name="statuts[<?= $examen['id'] ?>]" class="form-select form-select-sm rounded-pill border-primary">
                                                <option value="EN_ATTENTE" <?= $examen['statut'] == 'EN_ATTENTE' ? 'selected' : '' ?>>En attente</option>
                                                <option value="PRELEVE" <?= $examen['statut'] == 'PRELEVE' ? 'selected' : '' ?>>Prélevé</option>
                                                <option value="EN_COURS" <?= $examen['statut'] == 'EN_COURS' ? 'selected' : '' ?>>En cours</option>
                                                <option value="TERMINE" <?= $examen['statut'] == 'TERMINE' ? 'selected' : '' ?>>Terminé</option>
                                            </select>
                                        </td>
                                        <td class="text-end pe-4">
                                            <div class="btn-group">
                                                <a href="<?= BASE_URL ?>laboratoire/saisie-resultats/<?= $demande['id'] ?>" class="btn btn-sm btn-outline-primary" title="Saisir résultats">
                                                    <i class="bi bi-pencil-square"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="card-footer bg-white p-4 border-top">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <label class="form-label fw-bold small text-muted">NOTES DU TECHNICIEN / INCIDENTS :</label>
                                <textarea name="notes" class="form-control border-light-subtle shadow-sm" rows="2" placeholder="Ex: Hémolyse, Prélèvement difficile..."><?= htmlspecialchars($demande['notes'] ?? '') ?></textarea>
                            </div>
                            <div class="col-md-4 text-end">
                                <button type="submit" class="btn btn-primary btn-lg rounded-pill px-5 shadow">
                                    Mettre à jour la demande
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </main>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>