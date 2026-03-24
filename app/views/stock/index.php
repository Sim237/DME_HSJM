<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div class="container-fluid">
    <div class="row">
        <?php require_once __DIR__ . '/../layouts/sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2"><i class="bi bi-boxes"></i> Gestion du Stock</h1>
                <div class="btn-group">
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#stockModal">
                        <i class="bi bi-plus"></i> Ajouter
                    </button>
                    <button class="btn btn-warning" onclick="genererCommande()">
                        <i class="bi bi-cart"></i> Commande Auto
                    </button>
                </div>
            </div>

            <!-- Alertes Stock -->
            <div id="alertesStock"></div>

            <!-- Liste Stock -->
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Médicament</th>
                                    <th>Stock</th>
                                    <th>Min/Max</th>
                                    <th>Statut</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="stockTableBody">
                                <!-- Données chargées via JS -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Modal Stock -->
<div class="modal fade" id="stockModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Mouvement de Stock</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="stockForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Médicament</label>
                        <select class="form-select" name="medicament_id" required>
                            <option value="">Sélectionner</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Type</label>
                        <select class="form-select" name="type_mouvement" required>
                            <option value="entree">Entrée</option>
                            <option value="sortie">Sortie</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Quantité</label>
                        <input type="number" class="form-control" name="quantite" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Motif</label>
                        <input type="text" class="form-control" name="motif">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    chargerStock();
    chargerAlertes();
});

function chargerStock() {
    fetch(`${BASE_URL}stock/liste`)
        .then(response => response.json())
        .then(data => {
            const tbody = document.getElementById('stockTableBody');
            tbody.innerHTML = data.map(item => `
                <tr>
                    <td>${item.nom}</td>
                    <td><span class="badge ${item.stock_actuel <= item.stock_minimum ? 'bg-danger' : 'bg-success'}">${item.stock_actuel}</span></td>
                    <td>${item.stock_minimum}/${item.stock_maximum}</td>
                    <td><span class="badge bg-${item.statut === 'actif' ? 'success' : 'warning'}">${item.statut}</span></td>
                    <td>
                        <button class="btn btn-sm btn-outline-primary" onclick="ajusterStock(${item.id})">
                            <i class="bi bi-pencil"></i>
                        </button>
                    </td>
                </tr>
            `).join('');
        });
}

function chargerAlertes() {
    fetch(`${BASE_URL}stock/alertes`)
        .then(response => response.json())
        .then(alertes => {
            const container = document.getElementById('alertesStock');
            container.innerHTML = alertes.map(alerte => `
                <div class="alert alert-${alerte.niveau === 'danger' ? 'danger' : 'warning'} alert-dismissible">
                    <strong>${alerte.titre}</strong> ${alerte.message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            `).join('');
        });
}

function genererCommande() {
    fetch(`${BASE_URL}stock/commande-auto`)
        .then(response => response.json())
        .then(commandes => {
            if (commandes.length === 0) {
                alert('Aucune commande nécessaire');
            } else {
                const details = commandes.map(c => `${c.medicament}: ${c.quantite_commande} unités`).join('\n');
                alert('Commandes suggérées:\n' + details);
            }
        });
}

document.getElementById('stockForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    fetch(`${BASE_URL}stock/mouvement`, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        }
    });
});
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>