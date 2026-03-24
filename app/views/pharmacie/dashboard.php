<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<style>
    /* Structure de la page pour une sidebar fixe */
    .app-wrapper {
        display: flex;
        width: 100%;
        min-height: 100vh;
        background-color: #f4f7f6;
    }

    .main-content {
        flex-grow: 1;
        padding: 30px;
        overflow-y: auto;
    }

    /* Style des cartes et widgets */
    .pharmacy-card {
        border: none;
        border-radius: 20px;
        box-shadow: 0 10px 25px rgba(0,0,0,0.05);
        background: #fff;
        margin-bottom: 25px;
        transition: transform 0.2s ease-in-out;
    }
    .pharmacy-card:hover { transform: translateY(-5px); }

    .stat-widget { padding: 25px; text-align: center; }

    /* Couleurs thématiques */
    .text-pink { color: #db2777 !important; }
    .bg-soft-danger { background-color: #fff1f2; color: #e11d48; border: 1px solid #fecdd3; }
    .bg-soft-success { background-color: #f0fdf4; color: #16a34a; border: 1px solid #bbf7d0; }
    .bg-soft-info { background-color: #f0f9ff; color: #0284c7; border: 1px solid #bae6fd; }
    .bg-soft-warning { background-color: #fffbeb; color: #d97706; border: 1px solid #fef3c7; }

    /* Tableaux */
    .table-custom thead th {
        background-color: #f8fafc;
        text-transform: uppercase;
        font-size: 0.75rem;
        letter-spacing: 1px;
        color: #64748b;
        padding: 15px;
        border-bottom: 2px solid #edf2f7;
    }
    .table-custom td { padding: 15px; vertical-align: middle; }
</style>

<div class="app-wrapper">
    <!-- SIDEBAR -->
    <?php require_once __DIR__ . '/../layouts/sidebar.php'; ?>

    <!-- CONTENU PRINCIPAL -->
    <main class="main-content">

        <!-- En-tête de page -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="fw-bold text-dark mb-1">Pharmacie Centrale</h2>
                <p class="text-muted small mb-0"><i class="bi bi-hospital me-1"></i> Gestion du circuit du médicament • HSJM</p>
            </div>
            <div class="d-flex gap-2">
                <a href="<?= BASE_URL ?>pharmacie/stock" class="btn btn-outline-dark rounded-pill px-4 shadow-sm">
                    <i class="bi bi-box-seam me-2"></i> Inventaire
                </a>
                <button class="btn btn-primary rounded-pill px-4 shadow-sm" data-bs-toggle="modal" data-bs-target="#modalEntreeStock">
                    <i class="bi bi-plus-lg me-2"></i> Entrée Stock
                </button>
            </div>
        </div>

        <!-- Section Statistiques (Widgets) -->
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="card pharmacy-card stat-widget">
                    <small class="text-muted d-block mb-1 fw-bold">RÉFÉRENCES</small>
                    <h2 class="fw-bold text-dark mb-0"><?= $total_refs ?? 0 ?></h2>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card pharmacy-card stat-widget border-start border-danger border-5">
                    <small class="text-muted d-block mb-1 fw-bold">RÉF. EN ALERTE</small>
                    <h2 class="fw-bold text-danger mb-0"><?= count($low_stock ?? []) ?></h2>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card pharmacy-card stat-widget">
                    <small class="text-muted d-block mb-1 fw-bold">DÉLIVRANCES (JOUR)</small>
                    <h2 class="fw-bold text-success mb-0"><?= $conso_totale ?? 0 ?></h2>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card pharmacy-card stat-widget bg-primary text-white">
                    <small class="opacity-75 d-block mb-1 fw-bold">ORDONNANCES EN ATTENTE</small>
                    <h2 class="fw-bold mb-0"><?= count($pending_orders ?? []) ?></h2>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <!-- COLONNE GAUCHE : ORDONNANCES À PRÉPARER -->
            <div class="col-lg-8">
                <div class="card pharmacy-card h-100">
                    <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
                        <h5 class="fw-bold mb-0 text-dark">e-Prescriptions à traiter</h5>
                        <span class="badge bg-primary rounded-pill"><?= count($pending_orders ?? []) ?> nouvelles</span>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-custom hover mb-0">
                            <thead>
                                <tr>
                                    <th class="ps-4">Patient</th>
                                    <th>Prescripteur</th>
                                    <th>Heure Réception</th>
                                    <th class="text-end pe-4">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(empty($pending_orders)): ?>
                                    <tr>
                                        <td colspan="4" class="text-center py-5 text-muted small">
                                            <i class="bi bi-check2-circle display-4 d-block mb-2 opacity-25"></i>
                                            Aucune ordonnance signée en attente.
                                        </td>
                                    </tr>
                                <?php else: foreach($pending_orders as $ord): ?>
                                    <tr>
                                        <td class="ps-4">
                                            <div class="fw-bold text-dark"><?= htmlspecialchars($ord['patient_nom'].' '.$ord['patient_prenom']) ?></div>
<small class="text-muted">N° Dossier : <?= htmlspecialchars($ord['dossier_numero'] ?? 'N/A') ?></small>                                        </td>
                                        <td><span class="text-primary fw-bold small">Dr. <?= htmlspecialchars($ord['medecin_nom']) ?></span></td>
                                        <td><span class="badge bg-soft-info"><?= date('H:i', strtotime($ord['date_creation'])) ?></span></td>
                                        <td class="text-end pe-4">
                                            <a href="<?= BASE_URL ?>pharmacie/traitement/<?= $ord['id'] ?>" class="btn btn-primary btn-sm rounded-pill px-3 shadow-sm">
                                                Traiter
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- COLONNE DROITE : ALERTES DE STOCK -->
            <div class="col-lg-4">
                <div class="card pharmacy-card h-100">
                    <div class="card-header bg-white border-0 py-3">
                        <h6 class="fw-bold mb-0 text-danger"><i class="bi bi-exclamation-triangle me-2"></i>Alertes de Rupture</h6>
                    </div>
                    <div class="card-body p-0">
                        <?php if(empty($low_stock)): ?>
                            <p class="text-center py-5 text-muted small italic">Aucun produit sous le seuil critique.</p>
                        <?php else: foreach($low_stock as $m): ?>
                            <div class="d-flex justify-content-between align-items-center p-3 border-bottom mx-2">
                                <div>
                                    <div class="fw-bold small text-dark"><?= htmlspecialchars($m['designation']) ?></div>
                                    <small class="text-muted"><?= $m['dosage'] ?> - <?= $m['forme'] ?></small>
                                </div>
                                <span class="badge bg-soft-danger rounded-pill"><?= $m['quantite_stock'] ?></span>
                            </div>
                        <?php endforeach; endif; ?>
                    </div>
                    <div class="card-footer bg-white border-0 text-center py-3">
                        <a href="<?= BASE_URL ?>pharmacie/stock" class="text-decoration-none small fw-bold">
                            Voir tout l'inventaire <i class="bi bi-arrow-right"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<!-- ================= MODALE : ENTRÉE DE STOCK (APPROVISIONNEMENT) ================= -->
<div class="modal fade" id="modalEntreeStock" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header bg-primary text-white border-0">
                <h5 class="modal-title fw-bold"><i class="bi bi-cart-plus me-2"></i>Approvisionnement</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="<?= BASE_URL ?>pharmacie/approvisionnement" method="POST">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold small">Médicament à réapprovisionner</label>
                        <select name="medicament_id" class="form-select shadow-sm" required>
                            <option value="">-- Choisir le produit --</option>
                            <?php
                            $db = (new Database())->getConnection();
                            $all_meds = $db->query("SELECT id, nom, dosage FROM medicaments ORDER BY nom ASC")->fetchAll();
                            foreach($all_meds as $med): ?>
                                <option value="<?= $med['id'] ?>"><?= $med['nom'] ?> (<?= $med['dosage'] ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small">Quantité ajoutée (Unités)</label>
                        <input type="number" name="quantite_ajoutee" class="form-control shadow-sm" min="1" required placeholder="Ex: 50">
                    </div>
                </div>
                <div class="modal-footer border-0 p-4">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-5 shadow">Valider l'entrée</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>