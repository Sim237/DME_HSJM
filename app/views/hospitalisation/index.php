<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div class="container-fluid">
    <div class="row">
        <?php require_once __DIR__ . '/../layouts/sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2"><i class="bi bi-hospital me-2"></i>Hospitalisation</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalAdmission">
                        <i class="bi bi-plus-circle me-1"></i>Nouvelle Admission
                    </button>
                </div>
            </div>

            <!-- Statistiques rapides -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card bg-primary text-white">
                        <div class="card-body">
                            <h5><?= count($patients_hospitalises) ?></h5>
                            <p class="mb-0">Patients hospitalisés</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-success text-white">
                        <div class="card-body">
                            <h5><?= count(array_filter($patients_hospitalises, fn($p) => $p['service_nom'] === 'Médecine Interne')) ?></h5>
                            <p class="mb-0">Médecine Interne</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-warning text-white">
                        <div class="card-body">
                            <h5><?= count(array_filter($patients_hospitalises, fn($p) => $p['service_nom'] === 'Chirurgie')) ?></h5>
                            <p class="mb-0">Chirurgie</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-info text-white">
                        <div class="card-body">
                            <h5><?= count(array_filter($patients_hospitalises, fn($p) => $p['service_nom'] === 'Urgences')) ?></h5>
                            <p class="mb-0">Urgences</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Liste des patients hospitalisés -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Patients Hospitalisés</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Patient</th>
                                    <th>Service</th>
                                    <th>Lit</th>
                                    <th>Admission</th>
                                    <th>Durée</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($patients_hospitalises as $patient): ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($patient['nom'] . ' ' . $patient['prenom']) ?></strong><br>
                                        <small class="text-muted"><?= $patient['date_naissance'] ?></small>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary"><?= htmlspecialchars($patient['service_nom'] ?? 'Non assigné') ?></span>
                                    </td>
                                    <td>
                                        <span class="badge bg-info"><?= htmlspecialchars($patient['lit_numero'] ?? 'Non assigné') ?></span>
                                    </td>
                                    <td><?= date('d/m/Y H:i', strtotime($patient['date_admission'])) ?></td>
                                    <td>
                                        <?php 
                                        $duree = (new DateTime())->diff(new DateTime($patient['date_admission']));
                                        echo $duree->days . ' jour(s)';
                                        ?>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="/hospitalisation/dossier/<?= $patient['patient_id'] ?>" class="btn btn-outline-primary">
                                                <i class="bi bi-folder"></i> Dossier
                                            </a>
                                            <button class="btn btn-outline-success" onclick="administrerTraitement(<?= $patient['patient_id'] ?>)">
                                                <i class="bi bi-capsule"></i> Traitement
                                            </button>
                                            <button class="btn btn-outline-info" onclick="ajouterConstantes(<?= $patient['patient_id'] ?>)">
                                                <i class="bi bi-heart-pulse"></i> Constantes
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Modal Nouvelle Admission -->
<div class="modal fade" id="modalAdmission" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Nouvelle Admission</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="formAdmission">
                    <div class="row">
                        <div class="col-md-6">
                            <label class="form-label">Patient</label>
                            <select class="form-select" name="patient_id" required>
                                <option value="">Sélectionner un patient</option>
                                <!-- Charger via AJAX -->
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Service</label>
                            <select class="form-select" name="service_id" required>
                                <option value="1">Médecine Interne</option>
                                <option value="2">Chirurgie</option>
                                <option value="3">Pédiatrie</option>
                                <option value="4">Cardiologie</option>
                                <option value="5">Urgences</option>
                            </select>
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-md-12">
                            <label class="form-label">Motif d'admission</label>
                            <textarea class="form-control" name="motif_admission" rows="3" required></textarea>
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-md-12">
                            <label class="form-label">Diagnostic d'admission</label>
                            <textarea class="form-control" name="diagnostic_admission" rows="2"></textarea>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-primary" onclick="sauvegarderAdmission()">Admettre</button>
            </div>
        </div>
    </div>
</div>

<script>
function administrerTraitement(patientId) {
    window.location.href = `/hospitalisation/dossier/${patientId}#traitements`;
}

function ajouterConstantes(patientId) {
    window.location.href = `/hospitalisation/dossier/${patientId}#constantes`;
}

function sauvegarderAdmission() {
    const form = document.getElementById('formAdmission');
    const formData = new FormData(form);
    
    fetch('/hospitalisation/admettre', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Erreur lors de l\'admission');
        }
    });
}
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>