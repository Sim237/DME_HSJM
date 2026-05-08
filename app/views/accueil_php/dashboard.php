<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<style>
    :root { --php-color: #7c3aed; --php-light: #ede9fe; }
    body { background:#f5f3ff; font-family:'Inter',system-ui,sans-serif; color:#1e293b; }
    main { margin-left:0!important; width:100%!important; }
    .sidebar { display:none!important; }

    .page-header { padding:32px 0 20px; background:white; border-bottom:1px solid #ede9fe; margin-bottom:0; }
    .php-badge { background:var(--php-color); color:white; padding:5px 14px; border-radius:100px; font-weight:800; font-size:.72rem; letter-spacing:.06em; }

    .metric-card { background:white; border-radius:20px; padding:24px; border:1px solid #ede9fe; box-shadow:0 4px 6px rgba(0,0,0,.03); transition:all .3s; }
    .metric-card:hover { transform:translateY(-4px); box-shadow:0 12px 20px rgba(0,0,0,.07); }

    .data-card { background:white; border-radius:24px; padding:28px; border:1px solid #ede9fe; }

    .table-modern { width:100%; border-collapse:separate; border-spacing:0 10px; }
    .table-modern thead th { color:#64748b; font-weight:700; text-transform:uppercase; font-size:.72rem; padding:0 18px; border:none; }
    .table-modern tbody tr { background:#f5f3ff; transition:.15s; }
    .table-modern tbody tr td { padding:18px; border:none; }
    .table-modern tbody tr td:first-child { border-radius:14px 0 0 14px; }
    .table-modern tbody tr td:last-child { border-radius:0 14px 14px 0; }
    .table-modern tbody tr:hover { background:#ede9fe; }

    .btn-add { background:var(--php-color); color:white; padding:12px 24px; border-radius:14px; font-weight:700; border:none; transition:.2s; }
    .btn-add:hover { background:#6d28d9; color:white; }
    .btn-visit { background:#f3e8ff; color:var(--php-color); border:none; padding:9px 18px; border-radius:11px; font-weight:700; font-size:.84rem; transition:.2s; }
    .btn-visit:hover { background:var(--php-color); color:white; }

    #searchResults { border-radius:14px; overflow:hidden; border:none; margin-top:8px; }
</style>

<div class="page-header">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center gap-3">
                <span class="php-badge"><i class="bi bi-building-fill me-1"></i>MODULE PHP</span>
                <div>
                    <h4 class="mb-0 fw-bold">Accueil PHP</h4>
                    <small class="text-muted">Bonjour, <?= htmlspecialchars($_SESSION['user_nom'] ?? '') ?></small>
                </div>
            </div>
            <div class="d-flex align-items-center gap-3">
                <span class="fw-bold d-none d-md-block" id="liveTime" style="font-size:1.2rem; color:var(--php-color);"></span>
                <button class="btn btn-add" data-bs-toggle="modal" data-bs-target="#modalNouveauPatient">
                    <i class="bi bi-person-plus-fill me-2"></i>Nouveau Patient PHP
                </button>
                <a href="<?= BASE_URL ?>logout" class="btn btn-outline-danger rounded-3 px-3 py-2">
                    <i class="bi bi-power fs-5"></i>
                </a>
            </div>
        </div>
    </div>
</div>

<div class="container mt-4">

    <?php if(isset($_GET['success'])): ?>
    <div class="alert border-0 shadow-sm rounded-4 d-flex align-items-center p-4 mb-4" style="background:#f3e8ff; border-left:4px solid var(--php-color)!important;">
        <div class="rounded-circle p-3 me-3" style="background:white;">
            <i class="bi bi-check-lg fs-3" style="color:var(--php-color);"></i>
        </div>
        <div>
            <h5 class="fw-bold mb-1" style="color:var(--php-color);">Patient PHP enregistré !</h5>
            <?php if(isset($_GET['ticket'])): ?>
            <p class="mb-0">Dossier : <strong><?= htmlspecialchars($_GET['dossier'] ?? '') ?></strong> |
               Ticket : <span class="badge fw-bold rounded-pill px-3" style="background:var(--php-color);">#<?= (int)$_GET['ticket'] ?></span>
            </p>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- STATS -->
    <div class="row g-4 mb-4">
        <?php
        $db = (new Database())->getConnection();
        $nb_attente = (int)$db->query("SELECT COUNT(*) FROM patients WHERE circuit='PHP' AND statut_parcours='PARAMETRES'")->fetchColumn();
        $nb_jour    = (int)$db->query("SELECT COUNT(*) FROM patients WHERE circuit='PHP' AND DATE(created_at)=CURDATE()")->fetchColumn();
        $nb_total   = (int)$db->query("SELECT COUNT(*) FROM patients WHERE circuit='PHP'")->fetchColumn();
        ?>
        <div class="col-md-4">
            <div class="metric-card d-flex align-items-center gap-3">
                <div class="rounded-circle p-3" style="background:var(--php-light);">
                    <i class="bi bi-calendar-check fs-3" style="color:var(--php-color);"></i>
                </div>
                <div>
                    <h3 class="mb-0 fw-bold"><?= count($rdvs) ?></h3>
                    <small class="text-muted fw-semibold">RDV du jour</small>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="metric-card d-flex align-items-center gap-3">
                <div class="rounded-circle p-3" style="background:#dcfce7;">
                    <i class="bi bi-person-check fs-3 text-success"></i>
                </div>
                <div>
                    <h3 class="mb-0 fw-bold"><?= $nb_jour ?></h3>
                    <small class="text-muted fw-semibold">Enregistrés aujourd'hui</small>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="metric-card d-flex align-items-center gap-3">
                <div class="rounded-circle p-3" style="background:#fef3c7;">
                    <i class="bi bi-clock-history fs-3 text-warning"></i>
                </div>
                <div>
                    <h3 class="mb-0 fw-bold"><?= $nb_attente ?></h3>
                    <small class="text-muted fw-semibold">En attente aux paramètres</small>
                </div>
            </div>
        </div>
    </div>

    <!-- RECHERCHE -->
    <div class="position-relative mb-5" style="max-width:720px; margin-left:auto; margin-right:auto;">
        <div class="d-flex align-items-center gap-2 p-2 bg-white rounded-pill shadow-sm border" style="border-color:#ddd6fe!important;">
            <input type="text" id="phpSearch" class="border-0 flex-grow-1 px-3 py-2 bg-transparent" placeholder="Rechercher un dossier PHP (Nom, N°, Tél.)..." style="outline:none; font-size:1rem;">
            <button class="btn rounded-pill px-4 py-2 fw-bold text-white" style="background:var(--php-color);">
                <i class="bi bi-search"></i>
            </button>
        </div>
        <div id="phpSearchResults" class="list-group mt-2 shadow-lg d-none position-absolute w-100" style="z-index:1000;"></div>
    </div>

    <!-- RDV / FILE D'ATTENTE -->
    <div class="data-card">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h5 class="fw-bold mb-0">
                <i class="bi bi-list-stars me-2" style="color:var(--php-color);"></i>
                File d'attente PHP
            </h5>
            <span class="badge border px-3" style="background:var(--php-light); color:var(--php-color); font-size:.78rem;"><?= date('d/m/Y') ?></span>
        </div>

        <div class="table-responsive">
            <table class="table-modern">
                <thead>
                    <tr>
                        <th>Heure</th>
                        <th>Patient</th>
                        <th>Type client</th>
                        <th>Motif</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($rdvs)): ?>
                    <tr><td colspan="5" class="text-center py-5 text-muted">
                        <i class="bi bi-calendar-x display-4 opacity-25"></i><br>
                        Aucun rendez-vous PHP pour le moment.
                    </td></tr>
                    <?php else: foreach($rdvs as $r): ?>
                    <tr>
                        <td><span class="fw-bold" style="color:var(--php-color);"><?= date('H:i', strtotime($r['date_rdv'])) ?></span></td>
                        <td>
                            <div class="fw-bold"><?= strtoupper(htmlspecialchars($r['nom'])) ?> <?= htmlspecialchars($r['prenom']) ?></div>
                            <small class="text-muted font-monospace"><?= htmlspecialchars($r['dossier_numero']) ?></small>
                        </td>
                        <td>
                            <span class="badge" style="background:var(--php-light); color:var(--php-color); font-size:.72rem;">PHP</span>
                        </td>
                        <td><span class="text-muted"><?= htmlspecialchars($r['motif'] ?? '') ?></span></td>
                        <td class="text-end">
                            <button class="btn-visit" onclick="startPhpVisit(<?= (int)$r['id'] ?>)">
                                Lancer <i class="bi bi-arrow-right-short"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include __DIR__ . '/modal_nouveau_patient.php'; ?>

<script>
setInterval(() => document.getElementById('liveTime').textContent = new Date().toLocaleTimeString('fr-FR'), 1000);

// Recherche AJAX
const phpSearch = document.getElementById('phpSearch');
const phpResults = document.getElementById('phpSearchResults');
phpSearch.addEventListener('input', function() {
    const q = this.value.trim();
    if (q.length < 2) { phpResults.classList.add('d-none'); return; }
    fetch('<?= BASE_URL ?>consultation/search-patients?q=' + encodeURIComponent(q) + '&circuit=PHP')
        .then(r => r.json())
        .then(data => {
            let html = data.length ? data.map(p => `
                <a href="<?= BASE_URL ?>accueil-php/commencer-visite/${p.id}" class="list-group-item list-group-item-action d-flex justify-content-between p-3 border-0 border-bottom">
                    <div>
                        <div class="fw-bold">${p.nom} ${p.prenom}</div>
                        <small class="text-muted">${p.dossier_numero} • ${p.telephone || ''}</small>
                    </div>
                    <span class="badge rounded-pill px-3 py-2 align-self-center" style="background:#f3e8ff;color:#7c3aed;">Démarrer</span>
                </a>`).join('') : '<div class="list-group-item text-muted p-3">Aucun dossier trouvé.</div>';
            phpResults.innerHTML = html;
            phpResults.classList.remove('d-none');
        });
});
document.addEventListener('click', e => { if(!phpSearch.contains(e.target)) phpResults.classList.add('d-none'); });

function startPhpVisit(id) {
    if(confirm('Confirmer l\'arrivée du patient PHP et générer son ticket ?'))
        window.location.href = '<?= BASE_URL ?>accueil-php/commencer-visite/' + id;
}
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
