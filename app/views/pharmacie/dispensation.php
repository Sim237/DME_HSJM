<?php
require_once __DIR__ . '/../../layouts/header.php';
$prescriptions = $prescriptions ?? [];
?>

<div class="container-fluid">
    <div class="row">
        <?php require_once __DIR__ . '/../../layouts/sidebar.php'; ?>
        
        <main class="col-md-10 ms-sm-auto px-md-4">
            <div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2"><i class="bi bi-capsule-pill"></i> Dispensation des Médicaments</h1>
            </div>
            
            <!-- Filtres -->
            <div class="card mb-3">
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-search"></i></span>
                                <input type="text" class="form-control" id="searchPrescription" placeholder="Rechercher par patient, N° ordonnance...">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <select class="form-select" id="filtreStatut">
                                <option value="">Tous les statuts</option>
                                <option value="EN_ATTENTE" selected>En attente</option>
                                <option value="PARTIEL">Dispensé partiellement</option>
                                <option value="COMPLET">Dispensé complètement</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Liste des prescriptions -->
            <div class="card">
                <div class="card-body">
                    <?php if (count($prescriptions) > 0): ?>
                    <div class="list-group">
                        <?php foreach ($prescriptions as $prescr): ?>
                        <div class="list-group-item">
                            <div class="row align-items-center">
                                <div class="col-md-6">
                                    <h6 class="mb-1">
                                        Ordonnance #<?= str_pad($prescr['id'], 6, '0', STR_PAD_LEFT) ?>
                                        <?php
                                        $statutBadge = [
                                            'EN_ATTENTE' => 'warning',
                                            'PARTIEL' => 'info',
                                            'COMPLET' => 'success'
                                        ];
                                        $statut = $prescr['statut_dispensation'] ?? 'EN_ATTENTE';
                                        ?>
                                        <span class="badge bg-<?= $statutBadge[$statut] ?>">
                                            <?= str_replace('_', ' ', $statut) ?>
                                        </span>
                                    </h6>
                                    <p class="mb-1">
                                        <strong>Patient:</strong> <?= htmlspecialchars($prescr['patient_nom'] . ' ' . $prescr['patient_prenom']) ?>
                                    </p>
                                    <small class="text-muted">
                                        Date: <?= date('d/m/Y H:i', strtotime($prescr['date_prescription'])) ?> | 
                                        Médecin: Dr. <?= htmlspecialchars($prescr['medecin_nom']) ?>
                                    </small>
                                </div>
                                <div class="col-md-3 text-center">
                                    <p class="mb-0"><strong><?= $prescr['nb_medicaments'] ?></strong> médicament(s)</p>
                                    <?php if (isset($prescr['medicaments_dispenses'])): ?>
                                    <small class="text-muted">
                                        <?= $prescr['medicaments_dispenses'] ?> / <?= $prescr['nb_medicaments'] ?> dispensés
                                    </small>
                                    <?php endif; ?>
                                </div>
                                <div class="col-md-3 text-end">
                                    <div class="btn-group">
                                        <button class="btn btn-primary" onclick="dispenser(<?= $prescr['id'] ?>)">
                                            <i class="bi bi-check-circle"></i> Dispenser
                                        </button>
                                        <a href="index.php?page=prescription&action=print&id=<?= $prescr['id'] ?>" 
                                           class="btn btn-outline-secondary" target="_blank">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                    <div class="text-center text-muted py-4">
                        <i class="bi bi-inbox display-4"></i>
                        <p class="mt-2">Aucune prescription en attente</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Modal Dispensation -->
<div class="modal fade" id="modalDispensation" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">Dispensation de l'Ordonnance</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="formDispensation" onsubmit="validerDispensation(event)">
                <div class="modal-body" id="contenuDispensation">
                    <!-- Sera rempli dynamiquement -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-success">Valider la Dispensation</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function dispenser(prescriptionId) {
    fetch(`index.php?page=pharmacie&action=getPrescription&id=${prescriptionId}`)
    .then(response => response.json())
    .then(data => {
        const contenu = document.getElementById('contenuDispensation');
        
        let html = `
            <input type="hidden" name="prescription_id" value="${prescriptionId}">
            <div class="alert alert-info">
                <strong>Patient:</strong> ${data.patient_nom}<br>
                <strong>Ordonnance N°:</strong> #${String(data.id).padStart(6, '0')}
            </div>
            <table class="table">
                <thead>
                    <tr>
                        <th>Médicament</th>
                        <th>Posologie</th>
                        <th>Stock</th>
                        <th>Quantité</th>
                        <th>Dispenser</th>
                    </tr>
                </thead>
                <tbody>
        `;
        
        data.medicaments.forEach((med, index) => {
            const stockDispo = med.stock_disponible > 0;
            html += `
                <tr>
                    <td>
                        <strong>${med.nom}</strong><br>
                        <small>${med.forme} ${med.dosage}</small>
                    </td>
                    <td>${med.posologie}</td>
                    <td>
                        <span class="badge bg-${stockDispo ? 'success' : 'danger'}">
                            ${med.stock_disponible}
                        </span>
                    </td>
                    <td>
                        <input type="number" class="form-control form-control-sm" 
                               name="quantite[${med.id}]" 
                               min="0" max="${med.stock_disponible}"
                               ${!stockDispo ? 'disabled' : ''}>
                    </td>
                    <td class="text-center">
                        <input type="checkbox" class="form-check-input" 
                               name="dispense[${med.id}]" 
                               value="1" 
                               ${!stockDispo ? 'disabled' : ''}>
                    </td>
                </tr>
            `;
        });
        
        html += '</tbody></table>';
        
        html += `
            <div class="mb-3">
                <label class="form-label">Observations</label>
                <textarea class="form-control" name="observations" rows="2"></textarea>
            </div>
        `;
        
        contenu.innerHTML = html;
        
        const modal = new bootstrap.Modal(document.getElementById('modalDispensation'));
        modal.show();
    });
}

function validerDispensation(event) {
    event.preventDefault();
    const formData = new FormData(event.target);
    
    fetch('index.php?page=pharmacie&action=validerDispensation', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Dispensation enregistrée avec succès!');
            location.reload();
        } else {
            alert('Erreur: ' + (data.message || 'Échec de la dispensation'));
        }
    });
}
</script>

<?php require_once __DIR__ . '/../../layouts/footer.php'; ?>