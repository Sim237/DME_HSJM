<?php
/**
 * SimCare+ — Dossier Médical Électronique (DME)
 * Copyright (c) 2024-2026 Franck Simeni. Tous droits réservés.
 * Développé pour la gestion hospitalière, et le bien être numérique des patients.
 *
 * Toute reproduction, modification ou distribution de ce logiciel,
 * en tout ou en partie, sans autorisation écrite préalable de l'auteur
 * est strictement interdite et constitue une contrefaçon.
 *
 * Protected under OAPI Agreement — Annexe VII · Berne Convention
 */

require_once __DIR__ . '/../layouts/header.php';
$adminPage = 'laboratoire';
require_once __DIR__ . '/partials/admin_nav.php';

// Indexer les catégories par nom pour lookup rapide
$catMap = [];
foreach ($categories as $cat) {
    $catMap[$cat['nom']] = $cat;
}

// Compter examens par catégorie
$catCounts = [];
foreach ($categories as $cat) $catCounts[$cat['nom']] = 0;
foreach ($examens as $e) {
    $c = $e['categorie'] ?? 'AUTRE';
    if (isset($catCounts[$c])) $catCounts[$c]++;
    else $catCounts[$c] = ($catCounts[$c] ?? 0) + 1;
}

$totalExamens = count($examens);
$disponibles  = count(array_filter($examens, fn($e) => $e['disponible']));
?>

        <!-- TOPBAR -->
        <div class="admin-topbar">
            <div class="admin-topbar-title">
                Gestion Laboratoire
                <small>Catalogue des examens et catégories</small>
            </div>
            <div class="adm-topbar-actions">
                <div class="adm-clock-pill">
                    <span class="adm-clock-dot"></span>
                    <span class="adm-clock-time">--:--:--</span>
                </div>
                <button class="adm-topbtn adm-topbtn-ghost" onclick="openCatModal()">
                    <i class="bi bi-tags"></i> Catégories
                </button>
                <button class="adm-topbtn adm-topbtn-primary" onclick="openExamenModal()">
                    <i class="bi bi-plus-circle-fill"></i> Nouvel examen
                </button>
            </div>
        </div>

        <div class="admin-page-content">

            <!-- ── STATS ── -->
            <div class="row g-3 mb-4">
                <div class="col-6 col-md-3">
                    <div class="adm-stat" style="--adm-accent:#6366f1;">
                        <div class="adm-stat-val"><?= $totalExamens ?></div>
                        <div class="adm-stat-lbl">Total examens</div>
                        <i class="bi bi-flask-fill adm-stat-ico"></i>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="adm-stat" style="--adm-accent:#22c55e;">
                        <div class="adm-stat-val"><?= $disponibles ?></div>
                        <div class="adm-stat-lbl">Disponibles</div>
                        <i class="bi bi-check-circle-fill adm-stat-ico"></i>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="adm-stat" style="--adm-accent:#ef4444;">
                        <div class="adm-stat-val"><?= $totalExamens - $disponibles ?></div>
                        <div class="adm-stat-lbl">Indisponibles</div>
                        <i class="bi bi-x-circle-fill adm-stat-ico"></i>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="adm-stat" style="--adm-accent:#3b82f6;">
                        <div class="adm-stat-val"><?= count($categories) ?></div>
                        <div class="adm-stat-lbl">Catégories</div>
                        <i class="bi bi-tags-fill adm-stat-ico"></i>
                    </div>
                </div>
            </div>

            <!-- ── CATÉGORIES ── -->
            <div class="row g-2 mb-4">
                <?php foreach ($categories as $cat):
                    $nb  = $catCounts[$cat['nom']] ?? 0;
                    $col = $cat['couleur'] ?? 'secondary';
                    $ico = $cat['icone']   ?? 'grid-fill';
                ?>
                <div class="col-6 col-md-2">
                    <button class="btn btn-light border rounded-3 w-100 py-2 text-start cat-filter-btn" data-cat="<?= htmlspecialchars($cat['nom']) ?>" style="font-size:.78rem;">
                        <i class="bi bi-<?= htmlspecialchars($ico) ?> me-1 text-<?= $col ?>"></i>
                        <span class="fw-bold"><?= htmlspecialchars($cat['nom']) ?></span>
                        <span class="badge bg-secondary-subtle text-secondary ms-1" style="font-size:.65rem;"><?= $nb ?></span>
                    </button>
                </div>
                <?php endforeach; ?>
                <div class="col-6 col-md-2">
                    <button class="btn btn-dark rounded-3 w-100 py-2 text-start cat-filter-btn active" data-cat="" style="font-size:.78rem;">
                        <i class="bi bi-list-ul me-1"></i>
                        <span class="fw-bold">Tous</span>
                        <span class="badge bg-light text-dark ms-1" style="font-size:.65rem;"><?= $totalExamens ?></span>
                    </button>
                </div>
            </div>

            <!-- ── FILTRE texte ── -->
            <div class="card border-0 shadow-sm rounded-4 mb-3">
                <div class="card-body p-3">
                    <div class="row g-2 align-items-end">
                        <div class="col-md-4">
                            <input type="text" id="searchExamen" class="form-control form-control-sm rounded-3"
                                   placeholder="Rechercher un examen…" oninput="filterExamens()">
                        </div>
                        <div class="col-md-3">
                            <select id="filterPrelevement" class="form-select form-select-sm rounded-3" onchange="filterExamens()">
                                <option value="">Tout prélèvement</option>
                                <option>SANG</option>
                                <option>URINE</option>
                                <option>SELLES</option>
                                <option>LCR</option>
                                <option>AUTRE</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select id="filterDispo" class="form-select form-select-sm rounded-3" onchange="filterExamens()">
                                <option value="">Tous</option>
                                <option value="1">Disponibles</option>
                                <option value="0">Indisponibles</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ── TABLEAU ── -->
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" id="examenTable" style="font-size:.82rem;">
                            <thead style="background:#f8fafc;">
                                <tr class="text-muted" style="font-size:.7rem;text-transform:uppercase;letter-spacing:.06em;">
                                    <th class="ps-4 py-3">Examen</th>
                                    <th class="py-3">Catégorie</th>
                                    <th class="py-3">Prélèvement</th>
                                    <th class="py-3 text-center">Délai</th>
                                    <th class="py-3 text-center">À jeun</th>
                                    <th class="py-3 text-center">Prix</th>
                                    <th class="py-3 text-center">Statut</th>
                                    <th class="py-3 text-end pe-4">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($examens as $e):
                                    $bc = $catMap[$e['categorie']]['couleur'] ?? 'secondary';
                                ?>
                                <tr data-cat="<?= htmlspecialchars($e['categorie']) ?>"
                                    data-prel="<?= htmlspecialchars($e['type_prelevement']) ?>"
                                    data-dispo="<?= (int)$e['disponible'] ?>">
                                    <td class="ps-4">
                                        <div class="fw-bold"><?= htmlspecialchars($e['nom']) ?></div>
                                        <?php if (!empty($e['code'])): ?>
                                        <small class="text-muted font-monospace"><?= htmlspecialchars($e['code']) ?></small>
                                        <?php endif; ?>
                                        <?php if ($e['valeur_normale_min'] !== null || $e['valeur_normale_max'] !== null): ?>
                                        <small class="text-muted d-block">
                                            Réf: <?= $e['valeur_normale_min'] ?? '?' ?> – <?= $e['valeur_normale_max'] ?? '?' ?> <?= htmlspecialchars($e['unite'] ?? '') ?>
                                        </small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?= $bc ?>-subtle text-<?= $bc ?> border border-<?= $bc ?>-subtle fw-bold" style="font-size:.67rem;">
                                            <?= $e['categorie'] ?>
                                        </span>
                                    </td>
                                    <td class="text-muted"><?= $e['type_prelevement'] ?></td>
                                    <td class="text-center">
                                        <span class="badge bg-light text-dark border" style="font-size:.72rem;">
                                            <?= $e['delai_rendu_heures'] ?> h
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <?= $e['a_jeun_requis'] ? '<i class="bi bi-check-circle-fill text-warning"></i>' : '<span class="text-muted">—</span>' ?>
                                    </td>
                                    <td class="text-center fw-bold">
                                        <?= number_format((float)$e['prix'], 0, ',', ' ') ?> <small class="text-muted fw-normal">FCFA</small>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($e['disponible']): ?>
                                        <span class="badge bg-success-subtle text-success border border-success-subtle fw-bold" style="font-size:.67rem;">Disponible</span>
                                        <?php else: ?>
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle fw-bold" style="font-size:.67rem;">Indisponible</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end pe-4">
                                        <div class="d-flex gap-1 justify-content-end">
                                            <button class="btn btn-sm btn-light border rounded-pill px-2"
                                                    onclick='openEditExamen(<?= json_encode($e) ?>)'
                                                    title="Modifier">
                                                <i class="bi bi-pencil-square"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-danger rounded-pill px-2"
                                                    onclick='deleteExamen(<?= (int)$e["id"] ?>, "<?= addslashes($e['nom']) ?>")'
                                                    title="Supprimer">
                                                <i class="bi bi-trash3"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div><!-- /admin-page-content -->
    </div>
</div>

<!-- ── MODAL EXAMEN ── -->
<div class="modal fade" id="examenModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <form id="formExamen">
                <input type="hidden" id="exId" name="id">
                <div class="modal-header border-0 pb-0 px-4 pt-4">
                    <h5 class="fw-bold" id="exModalTitle">Nouvel examen</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label fw-bold small">CODE</label>
                            <input type="text" name="code" id="exCode" class="form-control" placeholder="BIO001">
                        </div>
                        <div class="col-md-9">
                            <label class="form-label fw-bold small">NOM *</label>
                            <input type="text" name="nom" id="exNom" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold small">CATÉGORIE</label>
                            <select name="categorie" id="exCat" class="form-select">
                                <?php foreach ($categories as $cat): ?>
                                <option value="<?= htmlspecialchars($cat['nom']) ?>"><?= htmlspecialchars($cat['nom']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold small">PRÉLÈVEMENT</label>
                            <select name="type_prelevement" id="exPrel" class="form-select">
                                <option>SANG</option>
                                <option>URINE</option>
                                <option>SELLES</option>
                                <option>LCR</option>
                                <option>AUTRE</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold small">DÉLAI (heures)</label>
                            <input type="number" name="delai_rendu_heures" id="exDelai" class="form-control" min="1" value="24">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold small">PRIX (FCFA)</label>
                            <input type="number" name="prix" id="exPrix" class="form-control" min="0" step="0.01" value="0">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold small">UNITÉ</label>
                            <input type="text" name="unite" id="exUnite" class="form-control" placeholder="g/L, UI/L…">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold small">VALEUR NORMALE MIN</label>
                            <input type="number" name="valeur_normale_min" id="exVmin" class="form-control" step="0.001">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold small">VALEUR NORMALE MAX</label>
                            <input type="number" name="valeur_normale_max" id="exVmax" class="form-control" step="0.001">
                        </div>
                        <div class="col-md-6 d-flex align-items-center gap-4 pt-2">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="a_jeun_requis" id="exJeun" role="switch">
                                <label class="form-check-label fw-bold small" for="exJeun">À jeun requis</label>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="disponible" id="exDispo" role="switch" checked>
                                <label class="form-check-label fw-bold small" for="exDispo">Disponible</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0 px-4 pb-4">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-5 fw-bold">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ── MODAL CATÉGORIES ── -->
<div class="modal fade" id="catModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-0 pb-0 px-4 pt-4">
                <h5 class="fw-bold"><i class="bi bi-tags me-2 text-primary"></i>Catégories d'examens</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body px-4 pb-0">
                <!-- Liste des catégories existantes -->
                <div class="list-group list-group-flush mb-3 rounded-3 border" id="catList">
                    <?php foreach ($categories as $cat):
                        $col = $cat['couleur'] ?? 'secondary';
                        $ico = $cat['icone']   ?? 'grid-fill';
                        $nb  = $catCounts[$cat['nom']] ?? 0;
                    ?>
                    <div class="list-group-item d-flex align-items-center gap-2 px-3 py-2" id="cat-row-<?= $cat['id'] ?>">
                        <i class="bi bi-<?= htmlspecialchars($ico) ?> text-<?= $col ?>" style="font-size:1rem;width:20px;"></i>
                        <span class="fw-bold flex-grow-1" style="font-size:.85rem;"><?= htmlspecialchars($cat['nom']) ?></span>
                        <span class="badge bg-secondary-subtle text-secondary border" style="font-size:.68rem;"><?= $nb ?> examens</span>
                        <?php if ($nb == 0): ?>
                        <button class="btn btn-sm btn-outline-danger rounded-pill px-2 py-0"
                                onclick="deleteCat(<?= $cat['id'] ?>, '<?= addslashes($cat['nom']) ?>')"
                                title="Supprimer">
                            <i class="bi bi-trash3" style="font-size:.75rem;"></i>
                        </button>
                        <?php else: ?>
                        <button class="btn btn-sm btn-light rounded-pill px-2 py-0" disabled title="Catégorie utilisée">
                            <i class="bi bi-lock" style="font-size:.75rem;"></i>
                        </button>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
                <!-- Formulaire nouvelle catégorie -->
                <form id="formCat" class="border rounded-3 p-3 bg-light mb-3">
                    <p class="fw-bold small mb-2 text-uppercase text-muted">Nouvelle catégorie</p>
                    <div class="row g-2">
                        <div class="col-md-5">
                            <label class="form-label fw-bold small">NOM *</label>
                            <input type="text" name="nom" id="catNom" class="form-control form-control-sm text-uppercase"
                                   placeholder="Ex : VIROLOGIE" required maxlength="50"
                                   oninput="this.value=this.value.toUpperCase()">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold small">COULEUR</label>
                            <select name="couleur" id="catCouleur" class="form-select form-select-sm">
                                <option value="primary">Bleu</option>
                                <option value="success">Vert</option>
                                <option value="danger">Rouge</option>
                                <option value="warning">Orange</option>
                                <option value="info">Cyan</option>
                                <option value="secondary" selected>Gris</option>
                                <option value="dark">Noir</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold small">ICÔNE</label>
                            <input type="text" name="icone" id="catIcone" class="form-control form-control-sm"
                                   placeholder="grid-fill" value="grid-fill">
                            <div class="form-text" style="font-size:.65rem;">
                                <a href="https://icons.getbootstrap.com" target="_blank">Bootstrap Icons</a>
                            </div>
                        </div>
                        <div class="col-12 d-flex justify-content-end">
                            <button type="submit" class="btn btn-primary btn-sm rounded-pill px-4 fw-bold">
                                <i class="bi bi-plus-circle me-1"></i>Ajouter
                            </button>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer border-0 pt-0 px-4 pb-4">
                <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Fermer</button>
            </div>
        </div>
    </div>
</div>

<!-- TOAST -->
<div class="toast-container position-fixed bottom-0 end-0 p-3">
    <div id="labToast" class="toast align-items-center border-0 text-white" role="alert">
        <div class="d-flex">
            <div class="toast-body fw-bold" id="labToastMsg"></div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    </div>
</div>

<style>
.cat-filter-btn { transition: all .15s; }
.cat-filter-btn.active { background: #0f172a !important; color: #fff !important; border-color: #0f172a !important; }
</style>

<script>
let examenModal, catModal;
document.addEventListener('DOMContentLoaded', () => {
    examenModal = new bootstrap.Modal(document.getElementById('examenModal'));
    catModal    = new bootstrap.Modal(document.getElementById('catModal'));

    document.getElementById('formCat').onsubmit = function(e) {
        e.preventDefault();
        fetch('<?= BASE_URL ?>admin/save-categorie', { method: 'POST', body: new FormData(this) })
            .then(r => r.json())
            .then(d => {
                if (d.success) { catModal.hide(); showLabToast('Catégorie ajoutée.'); setTimeout(() => location.reload(), 800); }
                else showLabToast(d.message || 'Erreur', false);
            });
    };

    // Cat filter buttons
    document.querySelectorAll('.cat-filter-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.cat-filter-btn').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            window._activeCat = this.dataset.cat;
            filterExamens();
        });
    });

    document.getElementById('formExamen').onsubmit = function(e) {
        e.preventDefault();
        fetch('<?= BASE_URL ?>admin/save-examen', { method: 'POST', body: new FormData(this) })
            .then(r => r.json())
            .then(d => {
                if (d.success) { examenModal.hide(); showLabToast('Examen enregistré.'); setTimeout(() => location.reload(), 900); }
                else showLabToast(d.message || 'Erreur', false);
            });
    };
});

window._activeCat = '';

function filterExamens() {
    const q    = document.getElementById('searchExamen').value.toLowerCase();
    const prel = document.getElementById('filterPrelevement').value;
    const dispo= document.getElementById('filterDispo').value;
    const cat  = window._activeCat;

    document.querySelectorAll('#examenTable tbody tr').forEach(tr => {
        const text  = tr.textContent.toLowerCase();
        const tcat  = tr.dataset.cat  || '';
        const tprel = tr.dataset.prel || '';
        const tdispo= tr.dataset.dispo || '';

        const ok = (!q    || text.includes(q))
                && (!cat  || tcat  === cat)
                && (!prel || tprel === prel)
                && (!dispo|| tdispo === dispo);
        tr.style.display = ok ? '' : 'none';
    });
}

function openCatModal() {
    catModal.show();
}

function deleteCat(id, nom) {
    if (!confirm('Supprimer la catégorie "' + nom + '" ?')) return;
    fetch('<?= BASE_URL ?>admin/delete-categorie/' + id, { method: 'POST' })
        .then(r => r.json())
        .then(d => {
            if (d.success) {
                document.getElementById('cat-row-' + id)?.remove();
                showLabToast('Catégorie supprimée.');
                setTimeout(() => location.reload(), 800);
            } else {
                showLabToast(d.message || 'Erreur', false);
            }
        });
}

function openExamenModal() {
    document.getElementById('formExamen').reset();
    document.getElementById('exId').value = '';
    document.getElementById('exModalTitle').textContent = 'Nouvel examen';
    document.getElementById('exDispo').checked = true;
    examenModal.show();
}

function openEditExamen(e) {
    document.getElementById('exId').value   = e.id;
    document.getElementById('exCode').value = e.code  || '';
    document.getElementById('exNom').value  = e.nom   || '';
    document.getElementById('exCat').value  = e.categorie        || 'BIOCHIMIE';
    document.getElementById('exPrel').value = e.type_prelevement || 'SANG';
    document.getElementById('exDelai').value= e.delai_rendu_heures || 24;
    document.getElementById('exPrix').value = e.prix  || 0;
    document.getElementById('exUnite').value= e.unite || '';
    document.getElementById('exVmin').value = e.valeur_normale_min !== null ? e.valeur_normale_min : '';
    document.getElementById('exVmax').value = e.valeur_normale_max !== null ? e.valeur_normale_max : '';
    document.getElementById('exJeun').checked = !!parseInt(e.a_jeun_requis);
    document.getElementById('exDispo').checked = !!parseInt(e.disponible);
    document.getElementById('exModalTitle').textContent = 'Modifier : ' + e.nom;
    examenModal.show();
}

function deleteExamen(id, nom) {
    if (!confirm('Supprimer l\'examen "' + nom + '" ?')) return;
    fetch('<?= BASE_URL ?>admin/delete-examen/' + id, { method: 'POST' })
        .then(r => r.json())
        .then(d => {
            if (d.success) { showLabToast('Examen supprimé.'); setTimeout(() => location.reload(), 900); }
            else showLabToast(d.message || 'Erreur', false);
        });
}

function showLabToast(msg, ok = true) {
    const t = document.getElementById('labToast');
    t.className = 'toast align-items-center border-0 text-white ' + (ok ? 'bg-success' : 'bg-danger');
    document.getElementById('labToastMsg').textContent = msg;
    bootstrap.Toast.getOrCreateInstance(t).show();
}
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
