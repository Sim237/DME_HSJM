<?php
require_once __DIR__ . '/../layouts/header.php';

// Sécurisation des variables
$total_refs = $total_refs ?? 0;
$total_alerte = $total_alerte ?? 0;
$processed_today = $processed_today ?? 0;
$pending_count = $pending_count ?? 0;
$pending_orders = $pending_orders ?? [];
$low_stock = $low_stock ?? [];
?>

<!-- IMPORT DES ICONES ET POLICES -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">

<style>
    /* Configuration Full Width */
    .sidebar { display: none !important; }
    main { margin-left: 0 !important; width: 100% !important; background: #f4f7f9; min-height: 100vh; font-family: 'Plus Jakarta Sans', sans-serif; color: #334155; }

    /* Header Institutionnel */
    .cockpit-header { background: #1a4a8e; color: white; padding: 12px 30px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }

    /* L'horloge Néon */
    #clock {
        font-family: 'Courier New', monospace;
        font-size: 2.2rem;
        font-weight: bold;
        color: #00ff41;
        text-shadow: 0 0 15px rgba(0, 255, 65, 0.6);
    }

    /* Style des boutons Header */
    .btn-header { font-weight: 700; border-radius: 50px; padding: 8px 20px; transition: 0.3s; border: none; font-size: 0.85rem; display: flex; align-items: center; gap: 8px; }
    .btn-sage { background: #1e40af; color: white; border: 1px solid rgba(255,255,255,0.2); }
    .btn-sage:hover { background: #1e3a8a; transform: translateY(-2px); }
    .btn-profile { background: white; color: #1a4a8e; opacity: 0.9; }
    .btn-profile:hover { opacity: 1; transform: translateY(-2px); }

    /* Cartes KPI */
    .stat-pill { background: white; border-radius: 20px; padding: 20px; flex: 1; display: flex; align-items: center; gap: 15px; box-shadow: 0 4px 6px rgba(0,0,0,0.03); border-bottom: 5px solid #ddd; }

    /* Table & Conteneur */
    .monitoring-container { background: white; border-radius: 24px; margin: 15px 25px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); border: 1px solid #e0e0e0; overflow: hidden; }
    .table-ph th { background: #f8fafc; color: #1a4a8e; padding: 15px; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 1px; }

    .timer-alert { background: #fff1f2; color: #dc3545; padding: 5px 12px; border-radius: 50px; font-weight: 800; animation: blink 2s infinite; }
    @keyframes blink { 50% { opacity: 0.4; } }
</style>

<main>
    <!-- BARRE SUPÉRIEURE -->
    <div class="cockpit-header d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center gap-3">
            <div class="bg-white p-2 rounded-circle shadow-sm"><img src="<?= BASE_URL ?>public/images/logo_ordre_malte.png" style="height: 35px;"></div>
            <div>
                <h4 class="mb-0 fw-bold">PHARMACIE <span style="color: #ffc107;">CENTRALE</span></h4>
                <small class="text-white-50 fw-bold">Unité Logistique • HSJM</small>
            </div>
        </div>

        <!-- HORLOGE DYNAMIQUE -->
        <div id="clock">00:00:00</div>

        <div class="d-flex gap-2 align-items-center">
            <button class="btn btn-header btn-sage" onclick="location.href='<?= BASE_URL ?>pharmacie/sage-sync'">
                <i class="bi bi-cloud-check"></i> SYNCHRO SAGE
            </button>
            <a href="<?= BASE_URL ?>pharmacie/stock" class="btn btn-header btn-light shadow-sm">
                <i class="bi bi-box-seam"></i> INVENTAIRE
            </a>
            <a href="<?= BASE_URL ?>profil" class="btn btn-header btn-profile shadow-sm">
                <i class="bi bi-person-circle"></i> PROFIL
            </a>
            <a href="<?= BASE_URL ?>logout" class="btn btn-danger rounded-circle p-2 shadow-sm"><i class="bi bi-power"></i></a>
        </div>
    </div>

    <!-- WIDGETS KPI -->
    <div class="d-flex gap-4 p-4">
        <div class="stat-pill" style="border-bottom-color: #1a4a8e;">
            <div><span class="fs-2 fw-bold d-block"><?= $total_refs ?></span><small class="fw-bold text-muted uppercase">Références</small></div>
        </div>
        <div class="stat-pill" style="border-bottom-color: #dc3545;">
            <div><span class="fs-2 fw-bold d-block text-danger"><?= $total_alerte ?></span><small class="fw-bold text-muted uppercase">En Alerte</small></div>
        </div>
        <div class="stat-pill" style="border-bottom-color: #198754;">
            <div><span class="fs-2 fw-bold d-block text-success"><?= $processed_today ?></span><small class="fw-bold text-muted uppercase">Traitées / Jour</small></div>
        </div>
        <div class="stat-pill" style="border-bottom-color: #ffd700;">
            <div><span class="fs-2 fw-bold d-block"><?= $pending_count ?></span><small class="fw-bold text-muted uppercase">En Attente</small></div>
        </div>
    </div>

    <div class="row mx-2 g-4">
        <!-- LISTE DES ORDONNANCES -->
        <div class="col-lg-9">
            <div class="monitoring-container m-0">
                <div class="p-3 bg-light border-bottom d-flex justify-content-between align-items-center">
                    <span class="fw-bold small text-muted uppercase"><i class="bi bi-megaphone me-2"></i>Flux e-Prescriptions</span>
                    <span class="badge bg-primary px-3 rounded-pill">LIVE</span>
                </div>
                <table class="table table-ph align-middle mb-0">
                    <thead>
                        <tr><th>Patient</th><th>Prescripteur</th><th>Attente</th><th class="text-end">Actions</th></tr>
                    </thead>
                    <tbody>
                        <?php if(empty($pending_orders)): ?>
                            <tr><td colspan="4" class="text-center py-5 text-muted fst-italic">Aucune ordonnance en attente de traitement.</td></tr>
                        <?php else: foreach($pending_orders as $ord): ?>
                            <tr>
                                <td>
                                    <div class="fw-bold text-dark"><?= strtoupper($ord['nom'] ?? 'Inconnu') ?> <?= $ord['prenom'] ?? '' ?></div>
                                    <small class="text-muted"><?= $ord['dossier_numero'] ?></small>
                                </td>
                                <td><span class="text-primary fw-bold">Dr. <?= $ord['medecin_nom'] ?></span></td>
                                <td>
                                    <?php if(($ord['minutes_attente'] ?? 0) >= 15): ?>
                                        <span class="timer-alert"><i class="bi bi-alarm-fill"></i> <?= $ord['minutes_attente'] ?> min</span>
                                    <?php else: ?>
                                        <span class="badge bg-light text-dark border px-3"><?= $ord['minutes_attente'] ?? 0 ?> min</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <a href="<?= BASE_URL ?>pharmacie/traitement/<?= $ord['id'] ?>" class="btn btn-primary btn-sm rounded-pill px-4 fw-bold shadow-sm">PRÉPARER</a>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- ALERTES DE STOCK -->
        <div class="col-lg-3">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="card-header bg-white py-3 border-bottom"><h6 class="fw-bold text-danger mb-0"><i class="bi bi-exclamation-triangle"></i> Ruptures & Alertes</h6></div>
                <div class="list-group list-group-flush">
                    <?php if(empty($low_stock)): ?>
                        <div class="p-4 text-center small text-muted">Stock optimal.</div>
                    <?php else: foreach($low_stock as $m): ?>
                        <div class="list-group-item d-flex justify-content-between align-items-center p-3">
                            <div>
                                <small class="fw-bold d-block text-dark"><?= htmlspecialchars($m['nom'] ?? 'Produit') ?></small>
                                <small class="text-muted"><?= $m['dosage'] ?? '' ?> • <?= $m['forme'] ?? '' ?></small>
                            </div>
                            <span class="badge bg-danger rounded-pill"><?= $m['quantite'] ?? 0 ?></span>
                        </div>
                    <?php endforeach; endif; ?>
                </div>
                <div class="card-footer bg-light border-0 text-center"><a href="<?= BASE_URL ?>pharmacie/stock" class="small fw-bold text-decoration-none">Voir l'inventaire</a></div>
            </div>
        </div>
    </div>
</main>

<!-- SCRIPTS JAVASCRIPT -->
<script>
    // FONCTION DE MISE À JOUR DE L'HORLOGE
    function startClock() {
        function update() {
            const now = new Date();
            const hours = String(now.getHours()).padStart(2, '0');
            const minutes = String(now.getMinutes()).padStart(2, '0');
            const seconds = String(now.getSeconds()).padStart(2, '0');

            const clockElement = document.getElementById('clock');
            if (clockElement) {
                clockElement.textContent = `${hours}:${minutes}:${seconds}`;
            }
        }

        update(); // Appel immédiat
        setInterval(update, 1000); // Mise à jour chaque seconde
    }

    // Lancer l'horloge une fois que le DOM est prêt
    document.addEventListener('DOMContentLoaded', startClock);

    // Auto-refresh pour les nouvelles ordonnances (toutes les 2 minutes)
    setTimeout(() => { location.reload(); }, 120000);
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>