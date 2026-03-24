<div class="app-wrapper">
    <?php require_once __DIR__ . '/../layouts/sidebar.php'; ?>
    <main class="main-content w-100 p-4">
        <h2 class="fw-bold mb-4">Mon Activité Médicale</h2>

        <ul class="nav nav-pills mb-4 bg-white p-2 rounded-pill shadow-sm d-inline-flex" id="pills-tab" role="tablist">
            <li class="nav-item">
                <button class="nav-link active rounded-pill" data-bs-toggle="pill" data-bs-target="#pills-today">En attente (Aujourd'hui)</button>
            </li>
            <li class="nav-item">
                <button class="nav-link rounded-pill" data-bs-toggle="pill" data-bs-target="#pills-history">Historique complet</button>
            </li>
        </ul>

        <div class="tab-content" id="pills-tabContent">
            <!-- PATIENTS DU JOUR -->
            <div class="tab-pane fade show active" id="pills-today">
                <div class="card border-0 shadow-sm rounded-4">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th>Priorité</th>
                                    <th>Patient</th>
                                    <th>Motif</th>
                                    <th>Heure Arrivée</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($patients_attente as $p): ?>
                                    <tr>
                                        <td>
                                            <span class="badge bg-<?= $p['niveau_gravite'] == '1-ROUGE' ? 'danger' : ($p['niveau_gravite'] == '2-ORANGE' ? 'warning' : 'success') ?>">
                                                <?= $p['niveau_gravite'] ?>
                                            </span>
                                        </td>
                                        <td><strong><?= $p['nom'].' '.$p['prenom'] ?></strong></td>
                                        <td><small><?= $p['motif_plainte'] ?></small></td>
                                        <td><?= date('H:i', strtotime($p['date_triage'])) ?></td>
                                        <td><a href="<?= BASE_URL ?>consultation/ouvrir/<?= $p['patient_id'] ?>" class="btn btn-sm btn-primary rounded-pill">Ouvrir Dossier</a></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- HISTORIQUE COMPLET -->
            <div class="tab-pane fade" id="pills-history">
                <div class="card border-0 shadow-sm rounded-4">
                    <div class="p-3 border-bottom">
                        <input type="text" class="form-control form-control-sm w-25" placeholder="Rechercher dans mes patients...">
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Patient</th>
                                    <th>Diagnostic</th>
                                    <th>Traitement</th>
                                    <th>Dossier</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($historique_total as $h): ?>
                                    <tr>
                                        <td><?= date('d/m/Y', strtotime($h['date_consultation'])) ?></td>
                                        <td><strong><?= $h['nom'].' '.$h['prenom'] ?></strong></td>
                                        <td class="small"><?= substr($h['diagnostic_principal'], 0, 50) ?>...</td>
                                        <td class="small"><?= substr($h['traitement_prescrit'], 0, 50) ?>...</td>
                                        <td><a href="<?= BASE_URL ?>patients/dossier/<?= $h['patient_id'] ?>" class="btn btn-sm btn-light border rounded-circle"><i class="bi bi-folder2-open"></i></a></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>