<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<!-- LIENS EXTERNES POUR LES ICÔNES ET LE DYNAMISME -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>

<style>
    /* 1. CONFIGURATION COCKPIT (SANS SIDEBAR) */
    .sidebar { display: none !important; }
    main { margin-left: 0 !important; width: 100% !important; background: #f4f7f9; min-height: 100vh; font-family: 'Plus Jakarta Sans', sans-serif; color: #334155; }

    /* 2. HEADER INSTITUTIONNEL (BLEU ROI) */
    .cockpit-header {
        background: #1a4a8e;
        color: white;
        padding: 12px 30px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        position: sticky; top: 0; z-index: 1000;
    }
    #clock { font-family: monospace; font-size: 1.8rem; font-weight: bold; color: #00ff41; text-shadow: 0 0 10px rgba(0, 255, 65, 0.3); }

    /* 3. WIDGETS STATISTIQUES SOFT */
    .status-bar { display: flex; gap: 20px; padding: 25px 30px 10px; }
    .stat-pill {
        background: white; border-radius: 16px; padding: 20px; flex: 1;
        display: flex; align-items: center; gap: 15px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.05); border-bottom: 5px solid #ddd;
        transition: transform 0.2s;
    }
    .stat-pill:hover { transform: translateY(-3px); }
    .stat-pill.waiting { border-bottom-color: #0d6efd; } /* Bleu */
    .stat-pill.urgent { border-bottom-color: #dc3545; }  /* Rouge */
    .stat-pill.done { border-bottom-color: #198754; }    /* Vert */

    .stat-icon { width: 50px; height: 50px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.8rem; }
    .stat-number { font-size: 2.2rem; font-weight: 800; color: #1e293b; line-height: 1; }
    .stat-label { font-size: 0.8rem; font-weight: 700; color: #64748b; text-transform: uppercase; }

    /* 4. BARRE DE RECHERCHE ET FILTRES */
    .filter-section { background: white; margin: 15px 30px; padding: 15px; border-radius: 12px; border: 1px solid #e2e8f0; }

    /* 5. GRILLE D'EXAMENS */
    .exam-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 20px; padding: 10px 30px 50px; }
    .exam-card {
        background: white; border-radius: 20px; border: 1px solid #e2e8f0; overflow: hidden;
        transition: 0.3s; position: relative; display: flex; flex-direction: column;
    }
    .exam-card:hover { box-shadow: 0 15px 30px rgba(0,0,0,0.1); transform: translateY(-5px); }

    .prio-flash { position: absolute; top: 15px; right: 15px; z-index: 10; }

    .exam-preview {
        height: 180px; background: #2d3748; display: flex; align-items: center;
        justify-content: center; color: rgba(255,255,255,0.2); font-size: 4rem;
        position: relative; overflow: hidden;
    }
    .exam-preview img { width: 100%; height: 100%; object-fit: cover; }

    .card-info { padding: 20px; flex-grow: 1; }
    .patient-name { font-weight: 800; font-size: 1.1rem; color: #1e293b; margin-bottom: 5px; }
    .exam-type { color: #1a4a8e; font-weight: 700; font-size: 0.85rem; text-transform: uppercase; }

    /* Boutons */
    .btn-action-main { background: #1a4a8e; color: white; border-radius: 10px; font-weight: 700; border: none; padding: 10px; width: 100%; transition: 0.3s; text-decoration: none; display: block; text-align: center;}
    .btn-action-main:hover { background: #000; color: white; }

    .empty-state { text-align: center; padding: 100px 0; color: #94a3b8; }
</style>

<main>
    <!-- TOP BAR (COULEURS ORDRE DE MALTE) -->
    <div class="cockpit-header d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center gap-3">
            <div class="bg-white p-2 rounded shadow-sm">
                <img src="<?= BASE_URL ?>public/images/logo_ordre_malte.png" style="height: 45px;" onerror="this.style.display='none'">
            </div>
            <div>
                <h4 class="mb-0 fw-bold">COCKPIT <span style="color: #ffc107;">IMAGERIE</span></h4>
                <small class="text-white-50 fw-bold">Unité de Radiologie & Diagnostic • HSJM</small>
            </div>
        </div>

        <div id="clock">00:00:00</div>

        <div class="d-flex gap-3">
            <button class="btn btn-danger fw-bold rounded-pill px-4 shadow" data-bs-toggle="modal" data-bs-target="#uploadModal">
                <i class="bi bi-upload me-2"></i>COMPLÉTER EXAMEN
            </button>
            <a href="<?= BASE_URL ?>logout" class="btn btn-light rounded-circle shadow-sm"><i class="bi bi-power text-danger fs-5"></i></a>
        </div>
    </div>

    <!-- STATISTIQUES KPI DYNAMIQUES -->
    <div class="status-bar">
        <div class="stat-pill waiting">
            <div class="stat-icon" style="background: #eef4ff;"><i class="bi bi-hourglass-split text-primary"></i></div>
            <div>
                <span class="stat-number"><?= $stats['en_attente'] ?? 0 ?></span>
                <span class="stat-label">En attente</span>
            </div>
        </div>
        <div class="stat-pill urgent">
            <div class="stat-icon" style="background: #fff5f5;"><i class="bi bi-exclamation-triangle-fill text-danger"></i></div>
            <div>
                <span class="stat-number text-danger"><?= $stats['a_interpreter'] ?? 0 ?></span>
                <span class="stat-label">À interpréter</span>
            </div>
        </div>
        <div class="stat-pill done">
            <div class="stat-icon" style="background: #f0fff4;"><i class="bi bi-check-circle-fill text-success"></i></div>
            <div>
                <span class="stat-number text-success"><?= $stats['termines'] ?? 0 ?></span>
                <span class="stat-label">Terminés (Jour)</span>
            </div>
        </div>
    </div>

    <!-- BARRE DE FILTRES LIVE -->
    <div class="filter-section d-flex gap-3 align-items-center shadow-sm">
        <div class="input-group" style="width: 350px;">
            <span class="input-group-text bg-light border-0"><i class="bi bi-search"></i></span>
            <input type="text" id="patientSearch" class="form-control border-0 bg-light" placeholder="Rechercher un patient ou N° dossier...">
        </div>
        <select class="form-select border-0 bg-light" style="width: 220px;" id="modalityFilter">
            <option value="">Toutes modalités</option>
            <option value="radiographie">Radiographie</option>
            <option value="scanner">Scanner</option>
            <option value="irm">IRM</option>
            <option value="echographie">Échographie</option>
        </select>
        <span class="text-muted small ms-auto fst-italic"><i class="bi bi-record-fill text-danger animate-pulse"></i> Monitoring en direct</span>
    </div>

    <!-- GRILLE DES EXAMENS RÉELLE -->
    <div class="exam-grid" id="examContainer">
        <?php if(empty($examens)): ?>
            <div class="col-12 empty-state">
                <i class="bi bi-camera-reels display-1 opacity-25"></i>
                <h4 class="mt-3">Aucun examen dans la file d'attente</h4>
                <p>Les prescriptions des médecins apparaîtront ici en temps réel.</p>
            </div>
        <?php else: foreach($examens as $ex):
            $status = $ex['statut'];
            $prioColor = (isset($ex['urgence']) && $ex['urgence'] == 'URGENT') ? 'danger' : 'warning';
        ?>

        <button onclick="confirmDelete(<?= $ex['id'] ?>)"
            class="btn btn-link text-danger position-absolute"
            style="top: 10px; left: 10px; z-index: 20; padding: 0;">
        <i class="bi bi-trash3-fill"></i>
    </button>
            <div class="exam-card animate__animated animate__fadeIn"
                 data-name="<?= strtolower($ex['nom'] . ' ' . $ex['prenom'] . ' ' . $ex['dossier_numero']) ?>"
                 data-type="<?= strtolower($ex['type_imagerie'] ?? '') ?>">

                  <button onclick="confirmDelete(<?= $ex['id'] ?>)"
            class="btn btn-link text-danger position-absolute"
            style="top: 10px; left: 10px; z-index: 20; padding: 0;">
        <i class="bi bi-trash3-fill"></i>
    </button>
                <?php if(isset($ex['urgence']) && $ex['urgence'] == 'URGENT'): ?>
                    <div class="prio-flash badge bg-danger animate__animated animate__pulse animate__infinite shadow">URGENT</div>
                <?php endif; ?>

                <div class="exam-preview">
                    <?php if(!empty($ex['fichier_preview'])): ?>
                        <img src="<?= BASE_URL ?>assets/uploads/previews/<?= $ex['fichier_preview'] ?>" alt="Aperçu">
                    <?php else: ?>
                        <i class="bi bi-camera-fill"></i>
                    <?php endif; ?>
                </div>

                <div class="card-info">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <span class="exam-type"><?= htmlspecialchars($ex['type_imagerie'] ?? 'Examen') ?></span>
                        <span class="badge rounded-pill bg-<?= $status == 'EN_ATTENTE' ? 'warning text-dark' : 'success' ?> small">
                            <?= strtoupper($status) ?>
                        </span>
                    </div>
                    <div class="patient-name"><?= strtoupper($ex['nom']) ?> <?= $ex['prenom'] ?></div>
                    <div class="small text-muted mb-3">
                        <i class="bi bi-geo-alt-fill text-primary"></i> <?= htmlspecialchars($ex['partie_code'] ?? 'Non précisé') ?><br>
                        <i class="bi bi-person-badge"></i> Dr. <?= htmlspecialchars($ex['medecin_nom']) ?>
                    </div>

                    <div class="mt-auto">
                        <?php if($status == 'EN_ATTENTE'): ?>
                            <button class="btn-action-main" onclick="openUpload(<?= $ex['id'] ?>)">RÉALISER L'EXAMEN</button>
                        <?php else: ?>
                            <a href="<?= BASE_URL ?>imagerie/viewer/<?= $ex['id'] ?>" class="btn-action-main text-decoration-none">VISUALISER</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

        <?php endforeach; endif; ?>
    </div>
</main>

<!-- MODALE D'UPLOAD INCLUSE -->
<?php include __DIR__ . '/modal_upload.php'; ?>

<script>
    // 1. Horloge temps réel
    function startClock() {
        const clock = document.getElementById('clock');
        setInterval(() => {
            const now = new Date();
            clock.innerText = now.toLocaleTimeString('fr-FR');
        }, 1000);
    }

    // 2. Filtrage dynamique sans rechargement
    const patientSearch = document.getElementById('patientSearch');
    const modalityFilter = document.getElementById('modalityFilter');

    function filterCards() {
        const nameQuery = patientSearch.value.toLowerCase();
        const typeQuery = modalityFilter.value.toLowerCase();

        document.querySelectorAll('.exam-card').forEach(card => {
            const nameMatch = card.dataset.name.includes(nameQuery);
            const typeMatch = typeQuery === "" || card.dataset.type === typeQuery;
            card.style.display = (nameMatch && typeMatch) ? 'flex' : 'none';
        });
    }

    patientSearch.addEventListener('input', filterCards);
    modalityFilter.addEventListener('change', filterCards);

    // 3. Ouverture modale upload
    function openUpload(id) {
        document.getElementById('imagerie_selector').value = id;
        new bootstrap.Modal(document.getElementById('uploadModal')).show();
    }

    // 4. Gestion AJAX Upload (Sécurité pour éviter le JSON brut)
    document.querySelector('#uploadModal form')?.addEventListener('submit', function(e) {
        e.preventDefault();
        const btn = this.querySelector('button[type="submit"]');
        const formData = new FormData(this);

        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Traitement...';

        fetch('<?= BASE_URL ?>imagerie/upload', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if(data.success) {
                alert("✅ Examen enregistré avec succès !");
                location.reload();
            } else {
                alert("❌ Erreur : " + data.message);
                btn.disabled = false;
                btn.innerText = 'ENREGISTRER ET TRANSMETTRE';
            }
        })
        .catch(err => {
            console.error(err);
            alert("Erreur technique lors de l'envoi.");
            btn.disabled = false;
        });
    });

    function confirmDelete(id) {
    if (confirm("⚠️ Attention : Voulez-vous vraiment supprimer cet examen et ses images définitivement ?")) {
        fetch('<?= BASE_URL ?>imagerie/delete/' + id, {
            method: 'DELETE', // Ou POST selon votre préférence
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // On rafraîchit la page pour mettre à jour les compteurs et la grille
                location.reload();
            } else {
                alert("Erreur : " + data.message);
            }
        })
        .catch(err => alert("Erreur technique lors de la suppression."));
    }
}

    document.addEventListener('DOMContentLoaded', startClock);
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>