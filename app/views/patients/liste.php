<?php
// On inclut le header
require_once __DIR__ . '/../layouts/header.php';

// On s'assure que $patients est défini
$patients = $patients ?? [];
?>

<div class="container-fluid">
    <div class="row">
        <?php 
        // On inclut la sidebar
        require_once __DIR__ . '/../layouts/sidebar.php'; 
        ?>
        
        <!-- Contenu Principal -->
        <main class="col-md-10 ms-sm-auto px-md-4">
            
            <!-- En-tête de la page -->
            <div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2"><i class="bi bi-people"></i> Liste des Patients</h1>
                <!-- Bouton qui redirige vers le formulaire complet -->
                <a href="<?php echo BASE_URL; ?>patients/nouveau" class="btn btn-primary">
                    <i class="bi bi-person-plus"></i> Nouveau Patient
                </a>
            </div>
            
            <!-- Message de succès (affiché après redirection) -->
            <?php if(isset($_GET['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
                    <div class="d-flex align-items-center flex-wrap">
                        <span class="me-3"><i class="bi bi-check-circle-fill me-2"></i> Opération effectuée avec succès.</span>
                        
                        <!-- Bouton spécial : Affiché UNIQUEMENT si on vient de créer un patient (new_id existe) -->
                        <?php if(isset($_GET['new_id'])): ?>
                            <a href="<?php echo BASE_URL; ?>patients/mesures/<?= htmlspecialchars($_GET['new_id']) ?>" class="btn btn-success btn-sm">
                                <i class="bi bi-speedometer2 me-1"></i> Entrer les paramètres maintenant
                            </a>
                        <?php endif; ?>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <!-- Barre de recherche -->
            <div class="card mb-4 shadow-sm border-0">
                <div class="card-body">
                    <form method="GET" action="<?php echo BASE_URL; ?>patients" class="row g-3">
                        <div class="col-md-6">
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                                <input type="text" name="search" class="form-control border-start-0 ps-0" 
                                       placeholder="Rechercher par nom, prénom ou n° dossier..." 
                                       value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
                                <button class="btn btn-primary" type="submit">Rechercher</button>
                            </div>
                        </div>
                        <?php if(isset($_GET['search']) && !empty($_GET['search'])): ?>
                            <div class="col-auto d-flex align-items-center">
                                <a href="<?php echo BASE_URL; ?>patients" class="text-danger text-decoration-none">
                                    <i class="bi bi-x-circle"></i> Effacer le filtre
                                </a>
                            </div>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
            
            <!-- Tableau des patients -->
            <div class="card shadow-sm border-0">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3">N° Dossier</th>
                                    <th>Nom complet</th>
                                    <th>Âge</th>
                                    <th>Sexe</th>
                                    <th>Téléphone</th>
                                    <th>Statut</th>
                                    <th class="text-end pe-3">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($patients) > 0): ?>
                                    <?php foreach ($patients as $patient): ?>
                                    <tr>
                                        <td class="ps-3">
                                            <span class="badge bg-light text-dark border">
                                                <?= htmlspecialchars($patient['dossier_numero']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="fw-bold text-primary"><?= htmlspecialchars($patient['nom'] . ' ' . $patient['prenom']) ?></div>
                                            <small class="text-muted">Né(e) le <?= date('d/m/Y', strtotime($patient['date_naissance'])) ?></small>
                                        </td>
                                        <td>
                                            <?php 
                                            $age = date_diff(date_create($patient['date_naissance']), date_create('now'))->y;
                                            echo $age . ' ans';
                                            ?>
                                        </td>
                                        <td>
                                            <?php if ($patient['sexe'] === 'M'): ?>
                                                <span class="badge bg-primary-subtle text-primary rounded-pill">M</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger-subtle text-danger rounded-pill">F</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= htmlspecialchars($patient['telephone'] ?? '-') ?></td>
                                        <td>
                                            <?php 
                                            $statut = $patient['statut'] ?? 'EXTERNE';
                                            $badgeClass = ($statut === 'HOSPITALISE') ? 'danger' : 'success';
                                            $label = ($statut === 'HOSPITALISE') ? 'Hospitalisé' : 'Externe';
                                            ?>
                                            <span class="badge bg-<?= $badgeClass ?>">
                                                <?= $label ?>
                                            </span>
                                        </td>
                                        <td class="text-end pe-3">
                                            <div class="btn-group btn-group-sm">
                                                
                                                <!-- Bouton Prise de paramètres -->
                                                <a href="<?php echo BASE_URL; ?>patients/mesures/<?= $patient['id'] ?>" 
                                                   class="btn btn-outline-success" title="Prendre constantes">
                                                    <i class="bi bi-heart-pulse"></i>
                                                </a>

                                                <!-- Lien vers le dossier complet -->
                                                <a href="<?php echo BASE_URL; ?>patients/dossier/<?= $patient['id'] ?>" 
                                                   class="btn btn-outline-primary" title="Voir dossier médical">
                                                    <i class="bi bi-folder2-open"></i> Dossier
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" class="text-center py-5">
                                            <div class="text-muted opacity-50">
                                                <i class="bi bi-inbox display-1"></i>
                                                <p class="mt-3 fs-5">Aucun patient trouvé</p>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    <?php if (isset($total_pages) && $total_pages > 1): ?>
                    <div class="d-flex justify-content-center py-3 border-top">
                        <nav aria-label="Page navigation">
                            <ul class="pagination mb-0">
                                <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                                    <a class="page-link" href="?page=<?= $page - 1 ?><?= isset($_GET['search']) ? '&search='.htmlspecialchars($_GET['search']) : '' ?>">
                                        <i class="bi bi-chevron-left"></i>
                                    </a>
                                </li>
                                <?php for($i = 1; $i <= $total_pages; $i++): ?>
                                    <li class="page-item <?= ($page == $i) ? 'active' : '' ?>">
                                        <a class="page-link" href="?page=<?= $i ?><?= isset($_GET['search']) ? '&search='.htmlspecialchars($_GET['search']) : '' ?>"><?= $i ?></a>
                                    </li>
                                <?php endfor; ?>
                                <li class="page-item <?= ($page >= $total_pages) ? 'disabled' : '' ?>">
                                    <a class="page-link" href="?page=<?= $page + 1 ?><?= isset($_GET['search']) ? '&search='.htmlspecialchars($_GET['search']) : '' ?>">
                                        <i class="bi bi-chevron-right"></i>
                                    </a>
                                </li>
                            </ul>
                        </nav>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</div>

<?php 
// On inclut le footer
require_once __DIR__ . '/../layouts/footer.php'; 
?>