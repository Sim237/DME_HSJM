<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<!-- LIENS POUR LES ICÔNES ET POLICES -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>

<style>
    /* CONFIGURATION COCKPIT */
    .sidebar { display: none !important; }
    main { margin-left: 0 !important; width: 100% !important; background: #f4f7f9; min-height: 100vh; font-family: 'Plus Jakarta Sans', sans-serif; color: #334155; }

    .cockpit-header {
        background: #1a4a8e;
        color: white;
        padding: 12px 30px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        position: sticky; top: 0; z-index: 1000;
    }
    #clock { font-family: monospace; font-size: 1.8rem; font-weight: bold; color: #00ff41; }

    .status-bar { display: flex; gap: 20px; padding: 25px 30px 10px; }
    .stat-pill {
        background: white; border-radius: 16px; padding: 20px; flex: 1;
        display: flex; align-items: center; gap: 15px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.05); border-bottom: 5px solid #ddd;
    }
    .stat-pill.waiting { border-bottom-color: #0d6efd; }
    .stat-pill.urgent { border-bottom-color: #dc3545; }
    .stat-pill.done { border-bottom-color: #198754; }

    .stat-number { font-size: 2.2rem; font-weight: 800; color: #1e293b; line-height: 1; }
    .stat-label { font-size: 0.8rem; font-weight: 700; color: #64748b; text-transform: uppercase; }

    /* GRID */
    .exam-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 20px; padding: 10px 30px 50px; }
    .exam-card {
        background: white; border-radius: 20px; border: 1px solid #e2e8f0; overflow: hidden;
        transition: 0.3s; position: relative; display: flex; flex-direction: column;
    }
    .exam-card:hover { box-shadow: 0 15px 30px rgba(0,0,0,0.1); transform: translateY(-5px); }

    .exam-preview {
        height: 180px; background: #2d3748; display: flex; align-items: center;
        justify-content: center; color: rgba(255,255,255,0.2); font-size: 4rem;
    }
    .exam-preview img { width: 100%; height: 100%; object-fit: cover; }

    .card-info { padding: 20px; flex-grow: 1; }
    .patient-name { font-weight: 800; font-size: 1.1rem; color: #1e293b; margin-bottom: 5px; }

    .btn-action-main { background: #1a4a8e; color: white; border-radius: 10px; font-weight: 700; border: none; padding: 10px; width: 100%; transition: 0.3s; text-decoration: none; display: block; text-align: center;}
    .btn-action-main:hover { background: #000; color: white; }
</style>

<main>
    <div class="cockpit-header d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center gap-3">
            <div class="bg-white p-2 rounded shadow-sm">
                <img src="<?= BASE_URL ?>public/images/logo_ordre_malte.png" style="height: 45px;">
            </div>
            <h4 class="mb-0 fw-bold">COCKPIT <span style="color: #ffd700;">IMAGERIE</span></h4>
        </div>
        <div id="clock">00:00:00</div>
        <div class="d-flex gap-3">
            <button class="btn btn-danger fw-bold rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#uploadModal">
                <i class="bi bi-upload me-2"></i>COMPLÉTER EXAMEN
            </button>
            <a href="<?= BASE_URL ?>logout" class="btn btn-light rounded-circle shadow-sm"><i class="bi bi-power text-danger"></i></a>
        </div>
    </div>

    <!-- STATS -->
    <div class="status-bar">
        <div class="stat-pill waiting">
            <div class="stat-icon text-primary"><i class="bi bi-hourglass-split fs-2"></i></div>
            <div><span class="stat-number"><?= $stats['en_attente'] ?? 0 ?></span><span class="stat-label">En attente</span></div>
        </div>
        <div class="stat-pill urgent">
            <div class="stat-icon text-danger"><i class="bi bi-exclamation-triangle-fill fs-2"></i></div>
            <div><span class="stat-number text-danger"><?= $stats['a_interpreter'] ?? 0 ?></span><span class="stat-label">À interpréter</span></div>
        </div>
        <div class="stat-pill done">
            <div class="stat-icon text-success"><i class="bi bi-check-circle-fill fs-2"></i></div>
            <div><span class="stat-number text-success"><?= $stats['termines'] ?? 0 ?></span><span class="stat-label">Terminés</span></div>
        </div>
    </div>

    <!-- GRILLE -->
    <div class="exam-grid">
        <?php if(empty($examens)): ?>
            <div class="col-12 text-center py-5 text-muted">Aucun examen.</div>
        <?php else: foreach($examens as $ex):
            // CORRECTION DES CLÉS SQL ICI
            $type = $ex['type_imagerie'] ?? 'Examen';
            $partie = $ex['partie_code'] ?? 'Non précisé';
        ?>
            <div class="exam-card animate__animated animate__fadeIn">
                <div class="exam-preview">
                    <?php if(!empty($ex['fichier_preview'])): ?>
                        <img src="<?= BASE_URL ?>assets/uploads/previews/<?= $ex['fichier_preview'] ?>">
                    <?php else: ?>
                        <i class="bi bi-camera"></i>
                    <?php endif; ?>
                </div>
                <div class="card-info">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="fw-bold text-primary small text-uppercase"><?= htmlspecialchars($type) ?></span>
                        <span class="badge rounded-pill bg-<?= $ex['statut'] == 'EN_ATTENTE' ? 'warning' : 'success' ?>"><?= $ex['statut'] ?></span>
                    </div>
                    <div class="patient-name"><?= strtoupper($ex['nom']) ?> <?= $ex['prenom'] ?></div>
                    <div class="small text-muted mb-3">
                        <i class="bi bi-geo-alt"></i> <?= htmlspecialchars($partie) ?><br>
                        <i class="bi bi-person-badge"></i> Dr. <?= htmlspecialchars($ex['medecin_nom']) ?>
                    </div>
                    <?php if($ex['statut'] == 'EN_ATTENTE'): ?>
                        <button class="btn-action-main" onclick="openUpload(<?= $ex['id'] ?>, '<?= addslashes($ex['nom']) ?>')">RÉALISER</button>
                    <?php else: ?>
                        <a href="<?= BASE_URL ?>imagerie/viewer/<?= $ex['id'] ?>" class="btn-action-main">VISUALISER</a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; endif; ?>
    </div>
</main>

<!-- CORRECTION DU CHEMIN D'INCLUSION -->
<?php include __DIR__ . '/modal_upload.php'; ?>

<script>
    setInterval(() => { document.getElementById('clock').innerText = new Date().toLocaleTimeString('fr-FR'); }, 1000);
    function openUpload(id, name) {
        document.getElementById('imagerie_selector').value = id;
        new bootstrap.Modal(document.getElementById('uploadModal')).show();
    }
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>