<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<style>
    .app-wrapper { display: flex; width: 100%; min-height: 100vh; background-color: #f4f7f6; }
    .main-content { flex-grow: 1; padding: 30px; overflow-y: auto; }

    /* Header Gradient Soft */
    .module-header-soft {
        background: linear-gradient(135deg, #6366f1 0%, #a855f7 100%);
        color: white; padding: 25px; border-radius: 20px;
        margin-bottom: 30px; box-shadow: 0 10px 15px -3px rgba(99, 102, 241, 0.2);
    }

    .card-custom { border: none; border-radius: 20px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); background: #fff; }

    /* Salles d'Opération */
    .salle-badge {
        padding: 15px; border-radius: 15px; border: 1px solid #edf2f7;
        margin-bottom: 12px; transition: all 0.3s;
    }
    .salle-badge.disponible { border-left: 5px solid #10b981; background: #f0fdf4; }
    .salle-badge.occupee { border-left: 5px solid #ef4444; background: #fef2f2; }

    /* Tableaux */
    .table-soft thead th {
        background: #f8fafc; color: #64748b; font-size: 0.75rem;
        text-transform: uppercase; letter-spacing: 1px; padding: 15px; border: none;
    }
    .table-soft td { padding: 15px; vertical-align: middle; border-bottom: 1px solid #f1f5f9; }
</style>

<div class="app-wrapper">
    <!-- SIDEBAR -->
    <?php require_once __DIR__ . '/../layouts/sidebar.php'; ?>

    <main class="main-content">
        <div class="module-header-soft d-flex justify-content-between align-items-center">
            <div>
                <h2 class="fw-bold mb-1"><i class="bi bi-scissors me-2"></i>Bloc Opératoire</h2>
                <p class="mb-0 opacity-75">Gestion des salles et du programme opératoire</p>
            </div>
            <div class="text-end">
                <div class="fw-bold fs-5"><?= date('H:i') ?></div>
                <div class="small opacity-75"><?= date('d F Y') ?></div>
            </div>
        </div>

        <div class="row g-4">
            <!-- GAUCHE : ÉTAT DES SALLES -->
            <div class="col-lg-4">
                <div class="card card-custom p-4">
                    <h5 class="fw-bold mb-4 text-dark border-bottom pb-2">Salles d'Opération</h5>
                    <?php foreach ($salles as $salle): ?>
                        <div class="salle-badge <?= strtolower($salle['statut']) == 'disponible' || strtolower($salle['statut']) == 'libre' ? 'disponible' : 'occupee' ?>">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="fw-bold text-dark"><?= htmlspecialchars($salle['nom_salle']) ?></div>
                                    <small class="text-muted">Type: Chirurgie</small>
                                </div>
                                <span class="badge rounded-pill bg-<?= strtolower($salle['statut']) == 'disponible' || strtolower($salle['statut']) == 'libre' ? 'success' : 'danger' ?>">
                                    <?= strtoupper($salle['statut']) ?>
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- DROITE : PROGRAMME ET FILE D'ATTENTE -->
            <div class="col-lg-8">
                <!-- 1. Programme du jour -->
                <div class="card card-custom mb-4">
                    <div class="card-header bg-white border-0 py-3">
                        <h5 class="fw-bold mb-0 text-primary">Interventions programmées (Aujourd'hui)</h5>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-soft mb-0">
                            <thead>
                                <tr><th>Heure</th><th>Patient</th><th>Intervention</th><th>Salle</th><th>Actions</th></tr>
                            </thead>
                            <tbody>
                                <?php if(empty($interventions)): ?>
                                    <tr><td colspan="5" class="text-center py-5 text-muted small italic">Aucune opération prévue pour le moment.</td></tr>
                                <?php else: foreach($interventions as $int): ?>
                                    <tr>
                                        <td><span class="badge bg-primary-subtle text-primary"><?= date('H:i', strtotime($int['heure_debut'])) ?></span></td>
                                        <td class="fw-bold"><?= htmlspecialchars($int['nom'].' '.$int['prenom']) ?></td>
                                        <td><small><?= htmlspecialchars($int['diagnostique_op']) ?></small></td>
                                        <td><span class="badge bg-dark"><?= $int['salle_nom'] ?></span></td>
                                        <td><a href="<?= BASE_URL ?>bloc/monitoring/<?= $int['id'] ?>" class="btn btn-sm btn-outline-primary rounded-pill">Cockpit</a></td>
                                    </tr>
                                <?php endforeach; endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- 2. File d'attente (Marie Curie arrive ici) -->
                <div class="card card-custom border-start border-warning border-5">
                    <div class="card-header bg-white border-0 py-3">
                        <h5 class="fw-bold mb-0 text-warning">File d'attente (A Opérer)</h5>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-soft mb-0">
                            <thead>
                                <tr><th>Patient</th><th>Chirurgien</th><th>Date demande</th><th class="text-end">Action</th></tr>
                            </thead>
                            <tbody>
                                <?php if(empty($file_attente)): ?>
                                    <tr><td colspan="4" class="text-center py-4 text-muted small">Aucun patient en attente de transfert</td></tr>
                                <?php else: foreach($file_attente as $req): ?>
                                    <tr>
                                        <td>
                                            <div class="fw-bold"><?= htmlspecialchars($req['nom'].' '.$req['prenom']) ?></div>
                                            <small class="text-muted"><?= $req['dossier_numero'] ?></small>
                                        </td>
                                        <td>Dr. <?= htmlspecialchars($req['chirurgien_nom']) ?></td>
                                        <td class="small"><?= date('d/m H:i', strtotime($req['date_demande'])) ?></td>
                                        <td class="text-end">
                                            <button class="btn btn-warning btn-sm rounded-pill px-3 fw-bold"
                                                    onclick="openProgModal(<?= $req['id'] ?>, '<?= addslashes($req['nom'].' '.$req['prenom']) ?>')">
                                                Programmer
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<!-- Modal Programmer Intervention -->
<div class="modal fade" id="modalProgrammer" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">Programmer au Bloc</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="<?= BASE_URL ?>bloc/confirmer-programmation" method="POST">
                <input type="hidden" name="demande_id" id="prog_demande_id">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="small fw-bold">Patient</label>
                        <input type="text" id="prog_patient_name" class="form-control bg-light" readonly>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="small fw-bold">Salle</label>
                            <select name="salle_id" class="form-select" required>
                                <?php foreach($salles as $s): ?>
                                    <option value="<?= $s['id'] ?>"><?= $s['nom_salle'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="small fw-bold">Heure</label>
                            <input type="time" name="heure" class="form-control" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="small fw-bold">Date de l'intervention</label>
                        <input type="date" name="date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="small fw-bold">Diagnostic Opératoire</label>
                        <textarea name="diagnostic" class="form-control" rows="2" required></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="submit" class="btn btn-primary w-100 shadow">Confirmer la programmation</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openProgModal(id, name) {
    document.getElementById('prog_demande_id').value = id;
    document.getElementById('prog_patient_name').value = name;
    new bootstrap.Modal(document.getElementById('modalProgrammer')).show();
}
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>