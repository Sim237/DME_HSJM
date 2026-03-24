<?php
require_once __DIR__ . '/../layouts/header.php';
$prescriptions = $prescriptions ?? [];
?>

<div class="container-fluid">
    <div class="row">
        <?php require_once __DIR__ . '/../layouts/sidebar.php'; ?>

        <main class="col-md-10 ms-sm-auto px-md-4">
            <div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2"><i class="bi bi-file-medical"></i> Prescriptions / Ordonnances</h1>
            </div>

            <!-- Filtres -->
            <div class="card mb-3">
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-search"></i></span>
                                <input type="text" class="form-control" placeholder="Rechercher par patient..." id="searchInput" onkeyup="filtrer()">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <input type="date" class="form-control" id="dateDebut" placeholder="Date début">
                        </div>
                        <div class="col-md-3">
                            <input type="date" class="form-control" id="dateFin" placeholder="Date fin">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Liste des prescriptions -->
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover" id="tablePrescriptions">
                            <thead>
                                <tr>
                                    <th>N° Ordonnance</th>
                                    <th>Patient</th>
                                    <th>Médecin</th>
                                    <th>Date</th>
                                    <th>Nb Médicaments</th>
                                    <th>Statut</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($prescriptions) > 0): ?>
                                    <?php foreach ($prescriptions as $prescr): ?>
                                    <tr data-patient="<?= strtolower($prescr['patient_nom'] . ' ' . $prescr['patient_prenom']) ?>">
                                        <td><strong>#<?= str_pad($prescr['id'], 6, '0', STR_PAD_LEFT) ?></strong></td>
                                        <td><?= htmlspecialchars($prescr['patient_nom'] . ' ' . $prescr['patient_prenom']) ?></td>
                                        <td>Dr. <?= htmlspecialchars($prescr['medecin_nom']) ?></td>
                                        <td><?= date('d/m/Y H:i', strtotime($prescr['date_prescription'])) ?></td>
                                        <td class="text-center">
                                            <span class="badge bg-primary"><?= $prescr['nb_medicaments'] ?? 0 ?></span>
                                        </td>
                                        <td>
                                            <?php
                                            $statut = $prescr['statut_dispensation'] ?? 'EN_ATTENTE';
                                            $badgeClass = [
                                                'EN_ATTENTE' => 'warning',
                                                'PARTIEL' => 'info',
                                                'COMPLET' => 'success'
                                            ][$statut] ?? 'secondary';
                                            ?>
                                            <span class="badge bg-<?= $badgeClass ?>">
                                                <?= str_replace('_', ' ', $statut) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <a href="<?= BASE_URL ?>prescription/print?id=<?= $prescr['id'] ?>"
                                                   class="btn btn-outline-primary" target="_blank" title="Imprimer">
                                                    <i class="bi bi-printer"></i>
                                                </a>
                                                <button class="btn btn-outline-info" onclick="voirDetails(<?= $prescr['id'] ?>)" title="Détails">
                                                    <i class="bi bi-eye"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" class="text-center text-muted py-4">
                                            <i class="bi bi-inbox display-4"></i>
                                            <p class="mt-2">Aucune prescription</p>
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

<script>
function filtrer() {
    const search = document.getElementById('searchInput').value.toLowerCase();
    const rows = document.querySelectorAll('#tablePrescriptions tbody tr');

    rows.forEach(row => {
        if (!row.dataset.patient) return;
        const patient = row.dataset.patient;
        row.style.display = patient.includes(search) ? '' : 'none';
    });
}

function voirDetails(id) {
    window.location.href = `index.php?page=prescription&action=details&id=${id}`;
}
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>