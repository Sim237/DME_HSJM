<?php
require_once __DIR__ . '/../layouts/header.php';
$adminPage = 'pharmacie';
require_once __DIR__ . '/partials/admin_nav.php';
?>

        <!-- TOPBAR -->
        <div class="admin-topbar">
            <div class="admin-topbar-title">
                Gestion Pharmacie
                <small>Médicaments, stock et import de catalogue</small>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-outline-secondary rounded-pill btn-sm px-3" onclick="downloadTemplate()">
                    <i class="bi bi-download me-1"></i>Modèle CSV
                </button>
                <button class="btn btn-outline-primary rounded-pill btn-sm px-3" onclick="document.getElementById('importZone').classList.toggle('d-none')">
                    <i class="bi bi-file-earmark-arrow-up me-1"></i>Importer Stock
                </button>
                <button class="btn btn-primary rounded-pill btn-sm px-3" onclick="openMedModal()">
                    <i class="bi bi-plus-circle me-1"></i>Nouveau médicament
                </button>
            </div>
        </div>

        <div class="admin-page-content">

            <!-- ── STATS ── -->
            <div class="row g-3 mb-4">
                <?php
                $totalMeds  = count($medicaments);
                $rupture    = count(array_filter($medicaments, fn($m) => $m['quantite'] <= 0));
                $critique   = count(array_filter($medicaments, fn($m) => $m['quantite'] > 0 && $m['quantite'] <= $m['seuil_alerte']));
                $ok         = $totalMeds - $rupture - $critique;
                ?>
                <div class="col-6 col-md-3">
                    <div class="card border-0 shadow-sm rounded-4 p-3 text-center">
                        <div class="fs-2 fw-bold text-dark"><?= $totalMeds ?></div>
                        <small class="text-muted fw-bold text-uppercase">Références</small>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card border-0 shadow-sm rounded-4 p-3 text-center">
                        <div class="fs-2 fw-bold text-success"><?= $ok ?></div>
                        <small class="text-muted fw-bold text-uppercase">Stock OK</small>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card border-0 shadow-sm rounded-4 p-3 text-center">
                        <div class="fs-2 fw-bold text-warning"><?= $critique ?></div>
                        <small class="text-muted fw-bold text-uppercase">Stock critique</small>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card border-0 shadow-sm rounded-4 p-3 text-center">
                        <div class="fs-2 fw-bold text-danger"><?= $rupture ?></div>
                        <small class="text-muted fw-bold text-uppercase">Ruptures</small>
                    </div>
                </div>
            </div>

            <!-- ── ZONE IMPORT ── -->
            <div id="importZone" class="card border-0 shadow-sm rounded-4 mb-4 d-none">
                <div class="card-body p-4">
                    <h6 class="fw-bold mb-1"><i class="bi bi-file-earmark-spreadsheet text-success me-2"></i>Import Stock depuis Excel / CSV</h6>
                    <p class="text-muted small mb-3">
                        Exportez votre fichier Excel en <strong>CSV (séparateur point-virgule)</strong>, puis importez-le ici.
                        Colonnes attendues : <code>code; nom; forme; dosage; quantite; unite; seuil_alerte; prix_unitaire</code>.
                        <a href="<?= BASE_URL ?>pharmacie/download-template" class="ms-2 fw-bold" style="font-size:.78rem;">
                            <i class="bi bi-download me-1"></i>Télécharger le modèle
                        </a>
                    </p>
                    <div class="row g-3 align-items-end">
                        <div class="col-md-5">
                            <label class="form-label fw-bold small mb-1">Fichier CSV</label>
                            <input type="file" id="csvFile" accept=".csv,.txt" class="form-control rounded-3">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold small mb-1">Mode d'import</label>
                            <select id="importMode" class="form-select rounded-3">
                                <option value="upsert">Créer + mettre à jour quantité</option>
                                <option value="add_qty">Ajouter à la quantité existante</option>
                                <option value="replace_qty">Remplacer la quantité</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button class="btn btn-success rounded-3 w-100 fw-bold" onclick="launchImport()">
                                <i class="bi bi-cloud-upload me-1"></i>Importer
                            </button>
                        </div>
                    </div>
                    <div id="importResult" class="mt-3 d-none"></div>
                </div>
            </div>

            <!-- ── FILTRE ── -->
            <div class="card border-0 shadow-sm rounded-4 mb-3">
                <div class="card-body p-3">
                    <div class="row g-2 align-items-end">
                        <div class="col-md-4">
                            <input type="text" id="searchMed" class="form-control form-control-sm rounded-3" placeholder="Rechercher un médicament…" oninput="filterTable()">
                        </div>
                        <div class="col-md-2">
                            <select id="filterStatut" class="form-select form-select-sm rounded-3" onchange="filterTable()">
                                <option value="">Tout le stock</option>
                                <option value="ok">Stock OK</option>
                                <option value="critique">Critique</option>
                                <option value="rupture">Rupture</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ── TABLEAU ── -->
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" id="medTable" style="font-size:.83rem;">
                            <thead style="background:#f8fafc;">
                                <tr class="text-muted" style="font-size:.7rem;text-transform:uppercase;letter-spacing:.06em;">
                                    <th class="ps-4 py-3">Médicament</th>
                                    <th class="py-3">Forme</th>
                                    <th class="py-3">Dosage</th>
                                    <th class="py-3 text-center">Quantité</th>
                                    <th class="py-3 text-center">Seuil alerte</th>
                                    <th class="py-3 text-center">Statut</th>
                                    <th class="py-3 text-end pe-4">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($medicaments as $m):
                                    $qte   = (int)$m['quantite'];
                                    $seuil = (int)$m['seuil_alerte'];
                                    if ($qte <= 0)             { $sc = 'danger';  $sl = 'Rupture'; $ds = 'rupture'; }
                                    elseif ($qte <= $seuil)    { $sc = 'warning'; $sl = 'Critique'; $ds = 'critique'; }
                                    else                       { $sc = 'success'; $sl = 'OK'; $ds = 'ok'; }
                                ?>
                                <tr data-statut="<?= $ds ?>">
                                    <td class="ps-4">
                                        <div class="fw-bold"><?= htmlspecialchars($m['designation'] ?? $m['nom']) ?></div>
                                        <?php if (!empty($m['code'])): ?>
                                        <small class="text-muted font-monospace"><?= htmlspecialchars($m['code']) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($m['forme'] ?? '—') ?></td>
                                    <td><?= htmlspecialchars($m['dosage'] ?? '—') ?></td>
                                    <td class="text-center fw-bold <?= $qte <= 0 ? 'text-danger' : ($qte <= $seuil ? 'text-warning' : 'text-dark') ?>">
                                        <?= $qte ?> <small class="text-muted fw-normal"><?= htmlspecialchars($m['unite'] ?? '') ?></small>
                                    </td>
                                    <td class="text-center text-muted"><?= $seuil ?></td>
                                    <td class="text-center">
                                        <span class="badge bg-<?= $sc ?>-subtle text-<?= $sc ?> border border-<?= $sc ?>-subtle fw-bold" style="font-size:.67rem;">
                                            <?= $sl ?>
                                        </span>
                                    </td>
                                    <td class="text-end pe-4">
                                        <div class="d-flex gap-1 justify-content-end">
                                            <button class="btn btn-sm btn-light border rounded-pill px-2"
                                                    onclick='openEditMed(<?= json_encode($m) ?>)'
                                                    title="Modifier">
                                                <i class="bi bi-pencil-square"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-success rounded-pill px-2"
                                                    onclick='openAppro(<?= (int)$m["id"] ?>, "<?= addslashes($m['designation'] ?? $m['nom']) ?>")'
                                                    title="Réapprovisionner">
                                                <i class="bi bi-plus-circle"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-danger rounded-pill px-2"
                                                    onclick='deleteMed(<?= (int)$m["id"] ?>, "<?= addslashes($m['designation'] ?? $m['nom']) ?>")'
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

<!-- ── MODAL MÉDICAMENT ── -->
<div class="modal fade" id="medModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <form id="formMed">
                <input type="hidden" id="medId" name="id">
                <div class="modal-header border-0 pb-0">
                    <h5 class="fw-bold" id="medModalTitle">Nouveau médicament</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label fw-bold small">CODE</label>
                            <input type="text" name="code" id="medCode" class="form-control" placeholder="MED001">
                        </div>
                        <div class="col-md-8">
                            <label class="form-label fw-bold small">NOM *</label>
                            <input type="text" name="nom" id="medNom" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small">FORME</label>
                            <input type="text" name="forme" id="medForme" class="form-control" placeholder="Comprimé, Sirop…">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small">DOSAGE</label>
                            <input type="text" name="dosage" id="medDosage" class="form-control" placeholder="500mg…">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold small">QUANTITÉ</label>
                            <input type="number" name="quantite" id="medQte" class="form-control" min="0" value="0">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold small">UNITÉ</label>
                            <input type="text" name="unite" id="medUnite" class="form-control" placeholder="cp, fl…">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold small">SEUIL ALERTE</label>
                            <input type="number" name="seuil_alerte" id="medSeuil" class="form-control" min="0" value="10">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small">PRIX UNITAIRE (FCFA)</label>
                            <input type="number" name="prix_unitaire" id="medPrix" class="form-control" min="0" step="0.01" value="0">
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-5 fw-bold">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ── MODAL APPROVISIONNEMENT ── -->
<div class="modal fade" id="approModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <form id="formAppro">
                <input type="hidden" id="approId" name="medicament_id">
                <div class="modal-header border-0 pb-0">
                    <h5 class="fw-bold">Réapprovisionner</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <p class="text-muted small mb-3" id="approNom"></p>
                    <label class="form-label fw-bold small">QUANTITÉ À AJOUTER</label>
                    <input type="number" name="quantite_ajoutee" id="approQte" class="form-control" min="1" value="10" required>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light rounded-pill" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-success rounded-pill px-4 fw-bold">Approvisionner</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- TOAST -->
<div class="toast-container position-fixed bottom-0 end-0 p-3">
    <div id="adminToast" class="toast align-items-center border-0 text-white" role="alert">
        <div class="d-flex">
            <div class="toast-body fw-bold" id="adminToastMsg"></div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    </div>
</div>

<script>
let medModal, approModal;
document.addEventListener('DOMContentLoaded', () => {
    medModal   = new bootstrap.Modal(document.getElementById('medModal'));
    approModal = new bootstrap.Modal(document.getElementById('approModal'));

    document.getElementById('formMed').onsubmit = function(e) {
        e.preventDefault();
        fetch('<?= BASE_URL ?>admin/save-medicament', { method: 'POST', body: new FormData(this) })
            .then(r => r.json())
            .then(d => {
                if (d.success) { medModal.hide(); showToast('Médicament enregistré.'); setTimeout(() => location.reload(), 900); }
                else showToast(d.message || 'Erreur', false);
            });
    };

    document.getElementById('formAppro').onsubmit = function(e) {
        e.preventDefault();
        fetch('<?= BASE_URL ?>pharmacie/approvisionnement', { method: 'POST', body: new FormData(this) })
            .then(() => { approModal.hide(); showToast('Stock mis à jour.'); setTimeout(() => location.reload(), 900); });
    };
});

function showToast(msg, ok = true) {
    const t = document.getElementById('adminToast');
    t.className = 'toast align-items-center border-0 text-white ' + (ok ? 'bg-success' : 'bg-danger');
    document.getElementById('adminToastMsg').textContent = msg;
    bootstrap.Toast.getOrCreateInstance(t).show();
}

function openMedModal() {
    document.getElementById('formMed').reset();
    document.getElementById('medId').value = '';
    document.getElementById('medModalTitle').textContent = 'Nouveau médicament';
    document.getElementById('medSeuil').value = 10;
    medModal.show();
}
function openEditMed(m) {
    document.getElementById('medId').value     = m.id;
    document.getElementById('medCode').value   = m.code    || '';
    document.getElementById('medNom').value    = m.designation || m.nom || '';
    document.getElementById('medForme').value  = m.forme   || '';
    document.getElementById('medDosage').value = m.dosage  || '';
    document.getElementById('medQte').value    = m.quantite|| 0;
    document.getElementById('medUnite').value  = m.unite   || '';
    document.getElementById('medSeuil').value  = m.seuil_alerte || 10;
    document.getElementById('medPrix').value   = m.prix_unitaire || 0;
    document.getElementById('medModalTitle').textContent = 'Modifier : ' + (m.designation || m.nom);
    medModal.show();
}
function deleteMed(id, nom) {
    if (!confirm(`Supprimer "${nom}" du stock ?\n\nCette action est irréversible.`)) return;
    const fd = new FormData();
    fd.append('id', id);
    fetch('<?= BASE_URL ?>admin/delete-medicament', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(d => {
            if (d.success) { showToast(`"${nom}" supprimé.`); setTimeout(() => location.reload(), 800); }
            else showToast(d.message || 'Erreur de suppression', false);
        });
}
function openAppro(id, nom) {
    document.getElementById('approId').value = id;
    document.getElementById('approNom').textContent = nom;
    document.getElementById('approQte').value = 10;
    approModal.show();
}

// ── Filtre tableau ──
function filterTable() {
    const q  = document.getElementById('searchMed').value.toLowerCase();
    const st = document.getElementById('filterStatut').value;
    document.querySelectorAll('#medTable tbody tr').forEach(tr => {
        const text = tr.textContent.toLowerCase();
        const ds   = tr.dataset.statut || '';
        const matchQ  = !q  || text.includes(q);
        const matchSt = !st || ds === st;
        tr.style.display = (matchQ && matchSt) ? '' : 'none';
    });
}

// ── Import CSV ──
async function launchImport() {
    const file = document.getElementById('csvFile').files[0];
    if (!file) { showToast('Sélectionnez un fichier CSV', false); return; }

    const fd = new FormData();
    fd.append('fichier', file);
    fd.append('mode', document.getElementById('importMode').value);

    const btn = event.currentTarget;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Import…';

    try {
        const res  = await fetch('<?= BASE_URL ?>pharmacie/import-stock', { method: 'POST', body: fd });
        const data = await res.json();
        const zone = document.getElementById('importResult');
        zone.classList.remove('d-none');

        if (data.success) {
            zone.innerHTML = `<div class="alert alert-success rounded-3 mb-0">
                <strong>✅ Import réussi !</strong> ${data.created} créé(s), ${data.updated} mis à jour.
                ${data.errors?.length ? '<hr><small class="text-muted">' + data.errors.join('<br>') + '</small>' : ''}
            </div>`;
            setTimeout(() => location.reload(), 2000);
        } else {
            zone.innerHTML = `<div class="alert alert-danger rounded-3 mb-0"><strong>Erreur :</strong> ${data.message}</div>`;
        }
    } catch (e) {
        showToast('Erreur réseau', false);
    }
    btn.disabled = false;
    btn.innerHTML = '<i class="bi bi-cloud-upload me-1"></i>Importer';
}

function downloadTemplate() {
    window.location.href = '<?= BASE_URL ?>pharmacie/download-template';
}
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
