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
 require_once __DIR__ . '/../layouts/header.php'; ?>

<style>
    /* ── LAYOUT ── */
    .sidebar { display: none !important; }
    main, .col-md-10, .ms-sm-auto {
        margin-left: 0 !important; width: 100% !important;
        flex: 0 0 100% !important; max-width: 100% !important; padding: 0 !important;
    }
    body { background: #0f172a; }

    /* ── TOPBAR ── */
    .stk-topbar {
        background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
        border-bottom: 1px solid rgba(255,255,255,.06);
        padding: 14px 28px;
        display: flex; align-items: center; justify-content: space-between;
        position: sticky; top: 0; z-index: 100;
    }
    .stk-title { font-size: 1.1rem; font-weight: 800; color: white; }
    .stk-title small { color: #64748b; font-size: .75rem; font-weight: 600; display: block; }

    .stk-btn {
        display: inline-flex; align-items: center; gap: 7px;
        padding: 8px 18px; border-radius: 10px; font-weight: 700;
        font-size: .78rem; text-decoration: none; border: none; cursor: pointer; transition: all .2s;
    }
    .stk-btn-primary { background: #2563eb; color: white; }
    .stk-btn-primary:hover { background: #1d4ed8; color: white; transform: translateY(-1px); }
    .stk-btn-outline { background: rgba(255,255,255,.05); color: #cbd5e1; border: 1px solid rgba(255,255,255,.1); }
    .stk-btn-outline:hover { background: rgba(255,255,255,.1); color: white; }
    .stk-btn-green  { background: rgba(16,185,129,.15); color: #6ee7b7; border: 1px solid rgba(16,185,129,.25); }
    .stk-btn-green:hover  { background: rgba(16,185,129,.25); color: #a7f3d0; }
    .stk-btn-amber  { background: rgba(245,158,11,.15); color: #fcd34d; border: 1px solid rgba(245,158,11,.25); }
    .stk-btn-amber:hover  { background: rgba(245,158,11,.25); color: #fde68a; }

    /* ── MAIN CONTENT ── */
    .stk-main { padding: 24px 28px; }

    /* ── STATS BAR ── */
    .stats-bar {
        display: grid; grid-template-columns: repeat(4,1fr); gap: 14px; margin-bottom: 22px;
    }
    .stat-box {
        background: #1e293b; border-radius: 14px; padding: 16px 20px;
        border: 1px solid rgba(255,255,255,.06);
    }
    .stat-box-val { font-size: 1.8rem; font-weight: 900; line-height: 1; }
    .stat-box-lbl { font-size: .72rem; color: #64748b; font-weight: 700; text-transform: uppercase; letter-spacing: .8px; margin-top: 4px; }

    /* ── TABLE CARD ── */
    .stk-card {
        background: #1e293b; border-radius: 20px;
        border: 1px solid rgba(255,255,255,.06); overflow: hidden;
    }
    .stk-card-header {
        padding: 16px 22px; border-bottom: 1px solid rgba(255,255,255,.06);
        display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap;
    }
    .search-box {
        background: rgba(0,0,0,.2); border: 1px solid rgba(255,255,255,.1);
        border-radius: 10px; padding: 8px 14px; color: white; font-size: .85rem; width: 260px;
    }
    .search-box::placeholder { color: #475569; }
    .search-box:focus { outline: none; border-color: rgba(59,130,246,.5); }

    /* ── TABLE ── */
    .stk-table { width: 100%; border-collapse: collapse; }
    .stk-table thead th {
        background: rgba(0,0,0,.2); color: #475569;
        font-size: .7rem; font-weight: 700; text-transform: uppercase; letter-spacing: 1px;
        padding: 12px 20px; border-bottom: 1px solid rgba(255,255,255,.05);
        white-space: nowrap;
    }
    .stk-table tbody tr { border-bottom: 1px solid rgba(255,255,255,.04); transition: background .15s; }
    .stk-table tbody tr:hover { background: rgba(255,255,255,.03); }
    .stk-table tbody td { padding: 13px 20px; color: #cbd5e1; font-size: .88rem; }
    .med-name { font-weight: 700; color: #f1f5f9; }
    .med-meta { font-size: .75rem; color: #475569; }

    .badge-stock-ok      { background: rgba(16,185,129,.15); color: #6ee7b7; padding: 4px 12px; border-radius: 20px; font-weight: 800; font-size: .8rem; }
    .badge-stock-low     { background: rgba(245,158,11,.15); color: #fcd34d; padding: 4px 12px; border-radius: 20px; font-weight: 800; font-size: .8rem; }
    .badge-stock-rupture { background: rgba(239,68,68,.15); color: #f87171; padding: 4px 12px; border-radius: 20px; font-weight: 800; font-size: .8rem; animation: pulse-r .8s infinite; }
    @keyframes pulse-r { 50%{opacity:.5} }

    .btn-edit-row {
        background: rgba(59,130,246,.1); color: #60a5fa; border: 1px solid rgba(59,130,246,.2);
        padding: 5px 12px; border-radius: 8px; font-size: .78rem; font-weight: 700; cursor: pointer;
        transition: all .2s;
    }
    .btn-edit-row:hover { background: rgba(59,130,246,.2); color: #93c5fd; }

    /* ── MODALS ── */
    .dark-modal .modal-content {
        background: #1e293b; border: 1px solid rgba(255,255,255,.08); border-radius: 20px; color: #e2e8f0;
    }
    .dark-modal .modal-header { border-bottom: 1px solid rgba(255,255,255,.08); padding: 20px 24px; }
    .dark-modal .modal-footer { border-top: 1px solid rgba(255,255,255,.08); padding: 16px 24px; }
    .dark-modal .modal-title  { font-weight: 800; color: white; }
    .dark-modal .btn-close    { filter: invert(1) opacity(.5); }
    .dark-modal .form-label   { font-size: .78rem; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: .5px; }
    .dark-modal .form-control, .dark-modal .form-select {
        background: rgba(0,0,0,.2); border: 1px solid rgba(255,255,255,.1); color: white;
        border-radius: 10px; padding: 10px 14px;
    }
    .dark-modal .form-control:focus, .dark-modal .form-select:focus {
        background: rgba(0,0,0,.3); border-color: rgba(59,130,246,.5); box-shadow: none; color: white;
    }
    .dark-modal .form-control::placeholder { color: #475569; }

    /* Import zone */
    .import-zone {
        border: 2px dashed rgba(59,130,246,.3); border-radius: 14px;
        padding: 30px; text-align: center; cursor: pointer; transition: all .2s;
    }
    .import-zone:hover { border-color: rgba(59,130,246,.6); background: rgba(59,130,246,.05); }
    .import-zone input[type=file] { display: none; }
    .import-zone .iz-icon { font-size: 2.5rem; color: #3b82f6; opacity: .6; margin-bottom: 10px; }
    .import-zone .iz-text { color: #64748b; font-size: .85rem; }
    .import-zone .iz-file { color: #60a5fa; font-weight: 700; font-size: .9rem; margin-top: 6px; }

    /* Toast */
    #toastContainer { position: fixed; bottom: 24px; right: 24px; z-index: 9999; display: flex; flex-direction: column; gap: 8px; }
    .toast-msg {
        background: #1e293b; color: #e2e8f0; border-radius: 12px; padding: 12px 18px;
        font-size: .85rem; font-weight: 600; min-width: 240px;
        border-left: 4px solid #3b82f6; box-shadow: 0 8px 24px rgba(0,0,0,.4);
        animation: slide-in .3s ease;
    }
    .toast-msg.success { border-color: #10b981; }
    .toast-msg.error   { border-color: #ef4444; }
    @keyframes slide-in { from{transform:translateX(100%);opacity:0} to{transform:translateX(0);opacity:1} }
</style>

<?php
// Stats calculées
$total   = count($medicaments);
$alerts  = count(array_filter($medicaments, fn($m) => $m['quantite'] <= ($m['seuil_alerte'] ?? 10) && $m['quantite'] > 0));
$ruptures = count(array_filter($medicaments, fn($m) => $m['quantite'] == 0));
$valeur  = array_sum(array_map(fn($m) => $m['quantite'] * ($m['prix_unitaire'] ?? 0), $medicaments));
?>

<main>
    <!-- TOPBAR -->
    <div class="stk-topbar">
        <div>
            <div class="stk-title"><i class="bi bi-box-seam-fill me-2"></i>Inventaire du Stock
                <small>Pharmacie Centrale &bull; HSJM</small>
            </div>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <!-- IMPORT -->
            <button class="stk-btn stk-btn-amber" data-bs-toggle="modal" data-bs-target="#modalImport">
                <i class="bi bi-upload"></i> IMPORTER
            </button>
            <!-- EXPORT -->
            <button class="stk-btn stk-btn-green" onclick="exportCSV()">
                <i class="bi bi-download"></i> EXPORTER
            </button>
            <!-- TEMPLATE -->
            <a href="<?= BASE_URL ?>pharmacie/download-template" class="stk-btn stk-btn-outline">
                <i class="bi bi-file-earmark-spreadsheet"></i> MODÈLE CSV
            </a>
            <!-- ENTRÉE STOCK -->
            <button class="stk-btn stk-btn-primary" data-bs-toggle="modal" data-bs-target="#modalEntreeStock">
                <i class="bi bi-plus-lg"></i> ENTRÉE STOCK
            </button>
            <a href="<?= BASE_URL ?>pharmacie" class="stk-btn stk-btn-outline">
                <i class="bi bi-arrow-left"></i> RETOUR
            </a>
        </div>
    </div>

    <div class="stk-main">

        <!-- STATS BAR -->
        <div class="stats-bar">
            <div class="stat-box">
                <div class="stat-box-val" style="color:#93c5fd"><?= $total ?></div>
                <div class="stat-box-lbl">Références</div>
            </div>
            <div class="stat-box">
                <div class="stat-box-val" style="color:#fcd34d"><?= $alerts ?></div>
                <div class="stat-box-lbl">En Alerte</div>
            </div>
            <div class="stat-box">
                <div class="stat-box-val" style="color:#f87171"><?= $ruptures ?></div>
                <div class="stat-box-lbl">Ruptures</div>
            </div>
            <div class="stat-box">
                <div class="stat-box-val" style="color:#6ee7b7; font-size:1.3rem"><?= number_format($valeur, 0, ',', ' ') ?> F</div>
                <div class="stat-box-lbl">Valeur Totale</div>
            </div>
        </div>

        <!-- TABLE -->
        <div class="stk-card">
            <div class="stk-card-header">
                <span style="color:#94a3b8;font-size:.85rem;font-weight:700">
                    <i class="bi bi-table me-2"></i>Stock détaillé
                </span>
                <div class="d-flex gap-2 flex-wrap align-items-center">
                    <select id="filterFamille" onchange="applyFilters()"
                            style="background:rgba(0,0,0,.2);border:1px solid rgba(255,255,255,.1);color:#cbd5e1;border-radius:10px;padding:7px 12px;font-size:.8rem;font-weight:600;">
                        <option value="">Toutes les familles</option>
                        <?php foreach ($familles as $f): ?>
                        <option value="<?= htmlspecialchars($f) ?>"><?= htmlspecialchars($f) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <input type="text" class="search-box" id="searchInput" placeholder="🔍  Rechercher un médicament..." oninput="applyFilters()" style="width:220px;">
                </div>
            </div>
            <div class="table-responsive">
                <table class="stk-table" id="stockTable">
                    <thead>
                        <tr>
                            <th>Désignation</th>
                            <th>Famille</th>
                            <th>Forme</th>
                            <th>Dosage</th>
                            <th style="text-align:center">Quantité</th>
                            <th>Seuil Alerte</th>
                            <th style="text-align:right">P.U (FCFA)</th>
                            <th style="text-align:center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($medicaments as $m):
                            $qty   = (int)$m['quantite'];
                            $seuil = (int)($m['seuil_alerte'] ?? 10);
                            if ($qty === 0)        $cls = 'badge-stock-rupture';
                            elseif ($qty <= $seuil) $cls = 'badge-stock-low';
                            else                    $cls = 'badge-stock-ok';
                        ?>
                        <tr data-famille="<?= htmlspecialchars($m['famille'] ?? '') ?>">
                            <td>
                                <div class="med-name"><?= htmlspecialchars($m['designation'] ?? $m['nom'] ?? '') ?></div>
                            </td>
                            <td>
                                <?php if (!empty($m['famille'])): ?>
                                <span style="background:rgba(59,130,246,.15);color:#93c5fd;border:1px solid rgba(59,130,246,.25);
                                             padding:3px 9px;border-radius:20px;font-size:.72rem;font-weight:700;white-space:nowrap;">
                                    <?= htmlspecialchars($m['famille']) ?>
                                </span>
                                <?php else: ?>
                                <span style="color:#475569;font-size:.8rem;">—</span>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($m['forme'] ?? '') ?></td>
                            <td><?= htmlspecialchars($m['dosage'] ?? '') ?></td>
                            <td style="text-align:center">
                                <span class="<?= $cls ?>"><?= $qty ?></span>
                            </td>
                            <td style="color:#64748b"><?= $seuil ?></td>
                            <td style="text-align:right;font-weight:700;color:#f1f5f9">
                                <?= number_format((float)($m['prix_unitaire'] ?? 0), 0, ',', ' ') ?>
                            </td>
                            <td style="text-align:center">
                                <button class="btn-edit-row"
                                    onclick="openEdit(<?= (int)$m['id'] ?>, '<?= htmlspecialchars(addslashes($m['designation'] ?? $m['nom'] ?? ''), ENT_QUOTES) ?>', <?= (float)($m['prix_unitaire'] ?? 0) ?>, <?= $seuil ?>)">
                                    <i class="bi bi-pencil me-1"></i>Modifier
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div><!-- /stk-main -->
</main>

<!-- ══ MODAL ENTRÉE STOCK ══ -->
<div class="modal fade dark-modal" id="modalEntreeStock" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-plus-circle me-2"></i>Entrée de Stock</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="<?= BASE_URL ?>pharmacie/approvisionnement">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label">Médicament</label>
                        <select name="medicament_id" class="form-select" required>
                            <option value="">-- Sélectionner --</option>
                            <?php foreach ($medicaments as $m): ?>
                                <option value="<?= (int)$m['id'] ?>"><?= htmlspecialchars($m['designation'] ?? $m['nom'] ?? '') ?> — Stock: <?= $m['quantite'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Quantité à ajouter</label>
                        <input type="number" name="quantite_ajoutee" class="form-control" min="1" required placeholder="Ex: 50">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-link text-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="stk-btn stk-btn-primary">
                        <i class="bi bi-check2-circle me-1"></i>Valider l'entrée
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ══ MODAL MODIFIER MÉDICAMENT ══ -->
<div class="modal fade dark-modal" id="modalEdit" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-pencil me-2"></i>Modifier le Médicament</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="<?= BASE_URL ?>pharmacie/update-medicament">
                <input type="hidden" id="editId" name="id">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label">Désignation</label>
                        <input type="text" id="editNom" name="designation" class="form-control" required>
                    </div>
                    <div class="row g-3">
                        <div class="col-6">
                            <label class="form-label">Prix Unitaire (FCFA)</label>
                            <input type="number" id="editPrix" name="prix_unitaire" class="form-control" step="0.01" min="0">
                        </div>
                        <div class="col-6">
                            <label class="form-label">Seuil d'alerte</label>
                            <input type="number" id="editSeuil" name="seuil_alerte" class="form-control" min="0">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-link text-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="stk-btn stk-btn-primary">
                        <i class="bi bi-check2 me-1"></i>Enregistrer
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ══ MODAL IMPORT ══ -->
<div class="modal fade dark-modal" id="modalImport" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-upload me-2"></i>Importer le Stock depuis Excel/CSV</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <!-- Info -->
                <div style="background:rgba(59,130,246,.08);border:1px solid rgba(59,130,246,.2);border-radius:12px;padding:14px 18px;margin-bottom:20px;font-size:.85rem;color:#93c5fd;">
                    <i class="bi bi-info-circle me-2"></i>
                    Format requis : <strong>CSV</strong> (exporté depuis Excel). Colonnes attendues :
                    <code style="background:rgba(0,0,0,.3);padding:2px 8px;border-radius:6px;font-size:.8rem;margin:0 4px;">code, nom, forme, dosage, quantite, unite, seuil_alerte, prix_unitaire</code><br>
                    <a href="<?= BASE_URL ?>pharmacie/download-template" class="text-warning fw-bold" style="text-decoration:none">
                        <i class="bi bi-file-earmark-arrow-down me-1"></i>Télécharger le modèle CSV
                    </a>
                </div>

                <!-- Mode import -->
                <div class="mb-3">
                    <label class="form-label" style="color:#64748b;font-size:.78rem;font-weight:700;text-transform:uppercase">Mode d'import</label>
                    <select id="importMode" class="form-select" style="background:rgba(0,0,0,.2);border:1px solid rgba(255,255,255,.1);color:white;border-radius:10px">
                        <option value="upsert">Mettre à jour + créer (recommandé)</option>
                        <option value="replace_qty">Remplacer la quantité</option>
                        <option value="add_qty">Ajouter à la quantité existante</option>
                    </select>
                </div>

                <!-- Zone drop -->
                <div class="import-zone" id="importZone" onclick="document.getElementById('importFile').click()">
                    <input type="file" id="importFile" accept=".csv,.txt">
                    <div class="iz-icon"><i class="bi bi-file-earmark-spreadsheet-fill"></i></div>
                    <div class="iz-text">Glissez votre fichier ici ou <strong style="color:#3b82f6">cliquez pour parcourir</strong></div>
                    <div class="iz-file" id="importFileName"></div>
                </div>

                <!-- Résultat -->
                <div id="importResult" style="display:none;margin-top:16px;padding:14px;border-radius:12px;font-size:.88rem;"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-link text-secondary" data-bs-dismiss="modal">Fermer</button>
                <button class="stk-btn stk-btn-primary" id="btnImport" onclick="doImport()" disabled>
                    <i class="bi bi-upload me-1"></i>Lancer l'import
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Toast container -->
<div id="toastContainer"></div>

<script>
// ── RECHERCHE + FILTRE FAMILLE ──
function applyFilters() {
    const q   = (document.getElementById('searchInput').value || '').toLowerCase();
    const fam = document.getElementById('filterFamille').value;
    document.querySelectorAll('#stockTable tbody tr').forEach(tr => {
        const matchQ   = !q   || tr.textContent.toLowerCase().includes(q);
        const matchFam = !fam || (tr.dataset.famille || '') === fam;
        tr.style.display = (matchQ && matchFam) ? '' : 'none';
    });
}

// ── MODAL MODIFIER ──
function openEdit(id, nom, prix, seuil) {
    document.getElementById('editId').value    = id;
    document.getElementById('editNom').value   = nom;
    document.getElementById('editPrix').value  = prix;
    document.getElementById('editSeuil').value = seuil;
    new bootstrap.Modal(document.getElementById('modalEdit')).show();
}

// ── EXPORT CSV ──
function exportCSV() {
    const rows = [['Désignation','Forme','Dosage','Quantité','Seuil','Prix Unitaire']];
    document.querySelectorAll('#stockTable tbody tr').forEach(tr => {
        if (tr.style.display === 'none') return;
        const cells = tr.querySelectorAll('td');
        rows.push([
            cells[0]?.textContent.trim(),
            cells[1]?.textContent.trim(),
            cells[2]?.textContent.trim(),
            cells[3]?.textContent.trim(),
            cells[4]?.textContent.trim(),
            cells[5]?.textContent.trim().replace(/\s/g,'')
        ]);
    });
    const csv = '\uFEFF' + rows.map(r => r.map(v => '"' + (v||'').replace(/"/g,'""') + '"').join(';')).join('\n');
    const blob = new Blob([csv], {type: 'text/csv;charset=utf-8;'});
    const a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = 'stock_pharmacie_' + new Date().toISOString().slice(0,10) + '.csv';
    a.click();
    showToast('Export terminé', 'success');
}

// ── IMPORT ──
document.getElementById('importFile').addEventListener('change', function() {
    const name = this.files[0]?.name || '';
    document.getElementById('importFileName').textContent = name ? '📄 ' + name : '';
    document.getElementById('btnImport').disabled = !name;
});

// Drag & drop
const zone = document.getElementById('importZone');
zone.addEventListener('dragover', e => { e.preventDefault(); zone.style.borderColor='rgba(59,130,246,.8)'; });
zone.addEventListener('dragleave', () => { zone.style.borderColor=''; });
zone.addEventListener('drop', e => {
    e.preventDefault();
    zone.style.borderColor = '';
    const file = e.dataTransfer.files[0];
    if (file) {
        const dt = new DataTransfer();
        dt.items.add(file);
        document.getElementById('importFile').files = dt.files;
        document.getElementById('importFileName').textContent = '📄 ' + file.name;
        document.getElementById('btnImport').disabled = false;
    }
});

function doImport() {
    const file = document.getElementById('importFile').files[0];
    if (!file) return;

    const btn = document.getElementById('btnImport');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Import en cours...';

    const fd = new FormData();
    fd.append('fichier', file);
    fd.append('mode', document.getElementById('importMode').value);

    fetch('<?= BASE_URL ?>pharmacie/import-stock', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            const div = document.getElementById('importResult');
            div.style.display = 'block';
            if (data.success) {
                div.style.background = 'rgba(16,185,129,.1)';
                div.style.border = '1px solid rgba(16,185,129,.25)';
                div.style.color = '#6ee7b7';
                div.innerHTML = `<i class="bi bi-check-circle-fill me-2"></i><strong>Import réussi !</strong> ${data.message}` +
                    (data.errors?.length ? `<br><small style="color:#fcd34d">${data.errors.join('<br>')}</small>` : '');
                showToast('Import réussi : ' + data.message, 'success');
                setTimeout(() => location.reload(), 2500);
            } else {
                div.style.background = 'rgba(239,68,68,.1)';
                div.style.border = '1px solid rgba(239,68,68,.25)';
                div.style.color = '#f87171';
                div.innerHTML = `<i class="bi bi-x-circle-fill me-2"></i>${data.message}`;
                showToast('Erreur : ' + data.message, 'error');
            }
        })
        .catch(() => showToast('Erreur réseau', 'error'))
        .finally(() => {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-upload me-1"></i>Lancer l\'import';
        });
}

// ── TOAST ──
function showToast(msg, type='info') {
    const t = document.createElement('div');
    t.className = 'toast-msg ' + type;
    t.textContent = msg;
    document.getElementById('toastContainer').appendChild(t);
    setTimeout(() => t.remove(), 4000);
}
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
