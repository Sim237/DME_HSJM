<?php require_once __DIR__ . '/../layouts/header.php'; ?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

<style>
    .app-wrapper { display: flex; width: 100%; min-height: 100vh; background-color: #f8fafc; }
    .main-content { flex-grow: 1; padding: 2rem; }
    .stock-card { background: #fff; border-radius: 16px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); overflow: hidden; }
    .table thead th { background-color: #f1f5f9; color: #475569; font-weight: 700; text-transform: uppercase; font-size: 0.75rem; padding: 1rem 1.5rem; }
    .table tbody td { padding: 1rem 1.5rem; vertical-align: middle; border-bottom: 1px solid #f1f5f9; }
    .btn-edit { background-color: #eff6ff; color: #2563eb; border: 1px solid #dbeafe; padding: 5px 12px; border-radius: 8px; font-size: 0.8rem; font-weight: 600; transition: 0.2s; }
    .btn-edit:hover { background-color: #2563eb; color: white; }
    .badge-soft-danger { background: #fee2e2; color: #dc2626; padding: 4px 8px; border-radius: 6px; font-size: 0.7rem; font-weight: bold; }
</style>

<div class="app-wrapper">
    <?php require_once __DIR__ . '/../layouts/sidebar.php'; ?>

    <main class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="fw-bold">Inventaire du Stock</h2>
            <button class="btn btn-primary shadow-sm rounded-pill" data-bs-toggle="modal" data-bs-target="#modalEntreeStock">
                <i class="bi bi-plus-lg"></i> Entrée de Stock
            </button>
        </div>

        <div class="stock-card">
            <div class="p-4 border-bottom">
                <input type="text" id="searchInput" class="form-control rounded-3" placeholder="Rechercher un médicament...">
            </div>

            <div class="table-responsive">
                <table class="table mb-0" id="stockTable">
                    <thead>
                        <tr>
                            <th>Désignation</th>
                            <th>Forme</th>
                            <th>Dosage</th>
                            <th class="text-center">Stock</th>
                            <th>Prix Unit.</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($medicaments as $m):
                            $is_low = ($m['quantite'] <= ($m['seuil_alerte'] ?? 10));
                        ?>
                        <tr>
                            <td class="med-name"><strong><?= htmlspecialchars($m['designation']) ?></strong><br>
                                <?php if($is_low): ?><span class="badge badge-soft-danger">STOCK FAIBLE</span><?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($m['forme']) ?></td>
                            <td><?= htmlspecialchars($m['dosage']) ?></td>
                            <td class="text-center fw-bold <?= $is_low ? 'text-danger' : 'text-success' ?>"><?= $m['quantite'] ?></td>
                            <td><?= number_format($m['prix_unitaire'], 0, ',', ' ') ?> FCFA</td>
                            <td class="text-end">
                                <!-- Bouton Modif qui passe les données en attributs HTML data- -->
                                <button class="btn-edit"
                                        data-bs-toggle="modal"
                                        data-bs-target="#modalEditMed"
                                        onclick="fillEditModal(<?= htmlspecialchars(json_encode($m)) ?>)">
                                    <i class="bi bi-pencil"></i> Modif.
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<!-- ================= MODALE : MODIFIER UN MÉDICAMENT ================= -->
<div class="modal fade" id="modalEditMed" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-0">
                <h5 class="fw-bold">Modifier le produit</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="<?= BASE_URL ?>pharmacie/update-medicament" method="POST">
                <input type="hidden" name="id" id="edit_id">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">DÉSIGNATION</label>
                        <input type="text" name="designation" id="edit_nom" class="form-control" required>
                    </div>
                    <div class="row g-3">
                        <div class="col-6">
                            <label class="form-label small fw-bold">PRIX UNITAIRE (FCFA)</label>
                            <input type="number" name="prix_unitaire" id="edit_prix" class="form-control" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-bold">SEUIL D'ALERTE</label>
                            <input type="number" name="seuil_alerte" id="edit_seuil" class="form-control" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="submit" class="btn btn-primary w-100 py-2">Enregistrer les modifications</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/modal_entree_stock.php'; ?>

<script>
    // Fonction pour remplir la modale d'édition
    function fillEditModal(med) {
        document.getElementById('edit_id').value = med.id;
        document.getElementById('edit_nom').value = med.designation;
        document.getElementById('edit_prix').value = med.prix_unitaire;
        document.getElementById('edit_seuil').value = med.seuil_alerte;
    }

    // Recherche dynamique
    document.getElementById('searchInput').addEventListener('keyup', function() {
        let q = this.value.toLowerCase();
        document.querySelectorAll('#stockTable tbody tr').forEach(tr => {
            tr.style.display = tr.innerText.toLowerCase().includes(q) ? '' : 'none';
        });
    });
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>