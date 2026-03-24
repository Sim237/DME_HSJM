<?php require_once __DIR__ . '/../layouts/header.php'; ?>
<style>
    /* SUPPRESSION SIDEBAR */
    .sidebar { display: none !important; }
    main { margin-left: 0 !important; width: 100% !important; background: #f8fafc; min-height: 100vh; }
    .nav-ph { background: #1a4a8e; color: white; padding: 15px 30px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
</style>

<nav class="nav-ph d-flex justify-content-between align-items-center">
    <div class="d-flex align-items-center gap-3">
        <div class="bg-white p-2 rounded shadow-sm"><img src="<?= BASE_URL ?>public/images/logo_ordre_malte.png" style="height: 35px;"></div>
        <h4 class="mb-0 fw-bold">INVENTAIRE DU STOCK</h4>
    </div>
    <div class="d-flex gap-2">
        <button class="btn btn-primary rounded-pill btn-sm px-3" data-bs-toggle="modal" data-bs-target="#modalEntreeStock">
            <i class="bi bi-plus-lg"></i> ENTRÉE DE STOCK
        </button>
        <a href="<?= BASE_URL ?>pharmacie" class="btn btn-outline-light rounded-pill btn-sm px-3">RETOUR COCKPIT</a>
    </div>
</nav>

<div class="container-fluid p-4">
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="p-4 bg-white border-bottom">
            <div class="input-group w-25 shadow-sm">
                <span class="input-group-text bg-white border-end-0"><i class="bi bi-search"></i></span>
                <input type="text" id="searchInput" class="form-control border-start-0" placeholder="Rechercher...">
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="stockTable">
                <thead class="table-light">
                    <tr><th>Désignation</th><th>Forme</th><th>Dosage</th><th class="text-center">Stock</th><th>P.U (FCFA)</th><th class="text-end">Actions</th></tr>
                </thead>
                <tbody>
                    <?php foreach($medicaments as $m):
                        $is_low = ($m['quantite'] <= ($m['seuil_alerte'] ?? 10));
                    ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($m['nom'] ?? $m['designation']) ?></strong></td>
                        <td><?= htmlspecialchars($m['forme']) ?></td>
                        <td><?= htmlspecialchars($m['dosage']) ?></td>
                        <td class="text-center">
                            <span class="badge <?= $is_low ? 'bg-danger' : 'bg-success' ?> rounded-pill px-3">
                                <?= $m['quantite'] ?>
                            </span>
                        </td>
                        <td class="fw-bold"><?= number_format($m['prix_unitaire'], 0, ',', ' ') ?></td>
                        <td class="text-end">
                            <button class="btn btn-sm btn-outline-primary rounded-circle"><i class="bi bi-pencil"></i></button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    document.getElementById('searchInput').addEventListener('keyup', function() {
        let q = this.value.toLowerCase();
        document.querySelectorAll('#stockTable tbody tr').forEach(tr => {
            tr.style.display = tr.innerText.toLowerCase().includes(q) ? '' : 'none';
        });
    });
</script>
<?php require_once __DIR__ . '/../layouts/footer.php'; ?>