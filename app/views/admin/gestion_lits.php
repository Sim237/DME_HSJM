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
$adminPage = 'lits';
require_once __DIR__ . '/partials/admin_nav.php';

// Grouper les lits par service → chambre
$lits_by_service = [];
foreach ($lits as $lit) {
    $lits_by_service[$lit['nom_service']][$lit['nom_chambre']][] = $lit;
}
ksort($lits_by_service);
?>

        <!-- TOPBAR -->
        <div class="admin-topbar">
            <div class="admin-topbar-title">
                Chambres &amp; Lits
                <small>Gérer la capacité d'accueil par service</small>
            </div>
            <div class="adm-topbar-actions">
                <div class="adm-clock-pill">
                    <span class="adm-clock-dot"></span>
                    <span class="adm-clock-time">--:--:--</span>
                </div>
                <button class="adm-topbtn adm-topbtn-ghost" onclick="openChambreModal()">
                    <i class="bi bi-plus-square-fill"></i> Nouvelle Chambre
                </button>
                <button class="adm-topbtn adm-topbtn-primary" onclick="openLitModal()">
                    <i class="bi bi-plus-circle-fill"></i> Nouveau Lit
                </button>
            </div>
        </div>

        <div class="admin-page-content">

            <!-- STATS -->
            <div class="row g-3 mb-4">
                <?php
                $totalLits = count($lits);
                $occupes   = count(array_filter($lits, fn($l) => $l['statut'] === 'OCCUPE'));
                $libres    = $totalLits - $occupes;
                $tauxGlobal = $totalLits > 0 ? round($occupes / $totalLits * 100) : 0;
                ?>
                <div class="col-md-3">
                    <div class="adm-stat" style="--adm-accent:#3b82f6;">
                        <div class="adm-stat-val"><?= count($chambres) ?></div>
                        <div class="adm-stat-lbl">Chambres</div>
                        <i class="bi bi-door-closed-fill adm-stat-ico"></i>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="adm-stat" style="--adm-accent:#6366f1;">
                        <div class="adm-stat-val"><?= $totalLits ?></div>
                        <div class="adm-stat-lbl">Lits au total</div>
                        <i class="bi bi-grid-3x3-gap-fill adm-stat-ico"></i>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="adm-stat" style="--adm-accent:#ef4444;">
                        <div class="adm-stat-val"><?= $occupes ?></div>
                        <div class="adm-stat-lbl">Occupés</div>
                        <i class="bi bi-person-fill adm-stat-ico"></i>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="adm-stat" style="--adm-accent:<?= $tauxGlobal > 80 ? '#ef4444' : ($tauxGlobal > 50 ? '#f59e0b' : '#22c55e') ?>;">
                        <div class="adm-stat-val" style="color:<?= $tauxGlobal > 80 ? '#dc2626' : ($tauxGlobal > 50 ? '#d97706' : '#16a34a') ?>;"><?= $tauxGlobal ?>%</div>
                        <div class="adm-stat-lbl">Taux d'occupation</div>
                        <i class="bi bi-activity adm-stat-ico"></i>
                    </div>
                </div>
            </div>

            <!-- VUE PAR SERVICE -->
            <?php foreach ($lits_by_service as $serviceName => $chambresData): ?>
            <div class="adm-card mb-4">
                <div class="adm-card-header">
                    <span><i class="bi bi-building-fill me-2 text-primary"></i><?= htmlspecialchars($serviceName) ?></span>
                    <?php
                    $sLits = array_merge(...array_values($chambresData));
                    $sOcc  = count(array_filter($sLits, fn($l) => $l['statut'] === 'OCCUPE'));
                    $sTot  = count($sLits);
                    $sPct  = $sTot > 0 ? round($sOcc/$sTot*100) : 0;
                    ?>
                    <div class="d-flex align-items-center gap-2">
                        <small class="text-muted"><?= $sTot ?> lit(s) — <?= $sOcc ?> occupé(s)</small>
                        <div style="width:100px; height:6px; background:#f1f5f9; border-radius:4px; overflow:hidden;">
                            <div style="width:<?= $sPct ?>%; height:100%; background:<?= $sPct > 80 ? '#ef4444' : ($sPct > 50 ? '#f59e0b' : '#10b981') ?>; border-radius:4px;"></div>
                        </div>
                        <span class="fw-bold small" style="color:<?= $sPct > 80 ? '#ef4444' : ($sPct > 50 ? '#f59e0b' : '#10b981') ?>;"><?= $sPct ?>%</span>
                    </div>
                </div>
                <div class="card-body px-4 pb-4">
                    <?php foreach ($chambresData as $chambreName => $litsData): ?>
                    <?php
                    $chambre = $litsData[0]; // use first lit for chambre info
                    // Find chambre record
                    $chambreRec = null;
                    foreach ($chambres as $ch) {
                        if ($ch['nom_chambre'] === $chambreName && $ch['nom_service'] === $serviceName) {
                            $chambreRec = $ch; break;
                        }
                    }
                    ?>
                    <div class="mb-4">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <span class="badge bg-secondary bg-opacity-10 text-secondary fw-bold px-3 py-2">
                                <i class="bi bi-door-open me-1"></i><?= htmlspecialchars($chambreName) ?>
                                <?php if ($chambreRec): ?>
                                    <span class="ms-1 opacity-50 text-lowercase">(<?= strtolower($chambreRec['type_chambre']) ?>)</span>
                                <?php endif; ?>
                            </span>
                            <div class="d-flex gap-1 ms-auto">
                                <?php if ($chambreRec): ?>
                                <button class="btn btn-xs btn-light border" style="font-size:0.72rem; padding:3px 8px;"
                                        onclick='editChambre(<?= json_encode(['id'=>$chambreRec['id'],'service_id'=>$chambreRec['service_id'],'nom_chambre'=>$chambreRec['nom_chambre'],'type_chambre'=>$chambreRec['type_chambre']]) ?>)'>
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button class="btn btn-xs btn-outline-danger border" style="font-size:0.72rem; padding:3px 8px;"
                                        onclick="deleteChambre(<?= $chambreRec['id'] ?>, '<?= addslashes($chambreName) ?>')">
                                    <i class="bi bi-trash3"></i>
                                </button>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="row g-2">
                            <?php foreach ($litsData as $lit): ?>
                            <?php
                            $isOcc = $lit['statut'] === 'OCCUPE';
                            $isMaint = $lit['statut'] === 'MAINTENANCE';
                            $isNett = $lit['statut'] === 'NETTOYAGE';
                            $borderColor = $isOcc ? '#ef4444' : ($isMaint ? '#f59e0b' : ($isNett ? '#6366f1' : '#10b981'));
                            $bgColor     = $isOcc ? '#fff5f5' : ($isMaint ? '#fffbeb' : ($isNett ? '#f5f3ff' : '#f0fdf4'));
                            ?>
                            <div class="col-sm-6 col-md-4 col-lg-3">
                                <div style="border: 1px solid <?= $borderColor ?>33; border-top: 4px solid <?= $borderColor ?>; background: <?= $bgColor ?>; border-radius: 10px; padding: 12px 14px;">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <div class="fw-bold text-dark" style="font-size:0.9rem;">Lit <?= htmlspecialchars($lit['nom_lit']) ?></div>
                                            <?php if ($isOcc && !empty($lit['patient_nom'])): ?>
                                            <small class="text-danger fw-bold d-block" style="font-size:0.72rem;">
                                                <?= strtoupper($lit['patient_nom']) ?> <?= $lit['patient_prenom'] ?>
                                            </small>
                                            <small class="text-muted" style="font-size:0.65rem;"><?= $lit['dossier_numero'] ?></small>
                                            <?php else: ?>
                                            <small class="fw-bold" style="color:<?= $borderColor ?>; font-size:0.72rem;">
                                                <?= htmlspecialchars($lit['statut']) ?>
                                            </small>
                                            <?php endif; ?>
                                        </div>
                                        <div class="d-flex flex-column gap-1">
                                            <button class="btn btn-xs btn-light border" style="font-size:0.65rem; padding:2px 6px;"
                                                    onclick='editLit(<?= json_encode(['id'=>$lit['id'],'chambre_id'=>$lit['chambre_id'],'nom_lit'=>$lit['nom_lit'],'statut'=>$lit['statut']]) ?>)'>
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <?php if (!$isOcc): ?>
                                            <button class="btn btn-xs btn-outline-danger" style="font-size:0.65rem; padding:2px 6px;"
                                                    onclick="deleteLit(<?= $lit['id'] ?>, '<?= addslashes($lit['nom_lit']) ?>')">
                                                <i class="bi bi-trash3"></i>
                                            </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endforeach; ?>

            <?php if (empty($lits_by_service)): ?>
            <div class="text-center py-5 text-muted">
                <i class="bi bi-grid-3x3-gap fs-1 d-block mb-3 opacity-25"></i>
                Aucun lit enregistré. Commencez par créer des chambres puis des lits.
            </div>
            <?php endif; ?>

        </div><!-- /admin-page-content -->
    </div><!-- /admin-main -->
</div><!-- /admin-shell -->

<!-- MODAL CHAMBRE -->
<div class="modal fade" id="chambreModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <form id="formChambre">
                <input type="hidden" id="chambreId" name="id" value="">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold" id="chambreModalTitle">Nouvelle Chambre</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label fw-bold small">SERVICE</label>
                            <select name="service_id" id="chambreServiceId" class="form-select" required>
                                <option value="">-- Sélectionner un service --</option>
                                <?php foreach ($services as $s): ?>
                                <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['nom_service']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-7">
                            <label class="form-label fw-bold small">NOM / NUMÉRO DE CHAMBRE</label>
                            <input type="text" name="nom_chambre" id="nomChambre" class="form-control" placeholder="Ex: Chambre 01" required>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label fw-bold small">TYPE</label>
                            <select name="type_chambre" id="typeChambre" class="form-select">
                                <option value="COMMUNE">Commune</option>
                                <option value="DOUBLE">Double</option>
                                <option value="INDIVIDUELLE">Individuelle</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-5 fw-bold shadow">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL LIT -->
<div class="modal fade" id="litModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <form id="formLit">
                <input type="hidden" id="litId" name="id" value="">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold" id="litModalTitle">Nouveau Lit</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label fw-bold small">CHAMBRE</label>
                            <select name="chambre_id" id="litChambreId" class="form-select" required>
                                <option value="">-- Sélectionner une chambre --</option>
                                <?php foreach ($chambres as $c): ?>
                                <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['nom_service'] . ' › ' . $c['nom_chambre']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-7">
                            <label class="form-label fw-bold small">NOM DU LIT</label>
                            <input type="text" name="nom_lit" id="nomLit" class="form-control" placeholder="Ex: L01, A1..." required>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label fw-bold small">STATUT INITIAL</label>
                            <select name="statut" id="litStatut" class="form-select">
                                <option value="DISPONIBLE">Disponible</option>
                                <option value="MAINTENANCE">Maintenance</option>
                                <option value="NETTOYAGE">Nettoyage</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-5 fw-bold shadow">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- TOAST -->
<div class="toast-container">
    <div id="adminToast" class="toast align-items-center border-0" role="alert">
        <div class="d-flex">
            <div class="toast-body fw-bold" id="adminToastMsg"></div>
            <button type="button" class="btn-close me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    </div>
</div>

<script>
let chambreModal, litModal;

document.addEventListener('DOMContentLoaded', function() {
    chambreModal = new bootstrap.Modal(document.getElementById('chambreModal'));
    litModal     = new bootstrap.Modal(document.getElementById('litModal'));

    document.getElementById('formChambre').onsubmit = function(e) {
        e.preventDefault();
        fetch('<?= BASE_URL ?>admin/save-chambre', { method: 'POST', body: new FormData(this) })
            .then(r => r.json()).then(d => {
                if (d.success) { chambreModal.hide(); showToast('Chambre enregistrée.'); setTimeout(() => location.reload(), 900); }
                else showToast(d.message || 'Erreur', false);
            });
    };

    document.getElementById('formLit').onsubmit = function(e) {
        e.preventDefault();
        fetch('<?= BASE_URL ?>admin/save-lit', { method: 'POST', body: new FormData(this) })
            .then(r => r.json()).then(d => {
                if (d.success) { litModal.hide(); showToast('Lit enregistré.'); setTimeout(() => location.reload(), 900); }
                else showToast(d.message || 'Erreur', false);
            });
    };
});

function showToast(msg, ok = true) {
    const t = document.getElementById('adminToast');
    t.className = 'toast align-items-center border-0 text-white ' + (ok ? 'bg-success' : 'bg-danger');
    document.getElementById('adminToastMsg').textContent = msg;
    bootstrap.Toast.getOrCreateInstance(t).show();
}

// ── CHAMBRE ────────────────────────────────────────────────────────────────
function openChambreModal() {
    document.getElementById('formChambre').reset();
    document.getElementById('chambreId').value = '';
    document.getElementById('chambreModalTitle').textContent = 'Nouvelle Chambre';
    chambreModal.show();
}
function editChambre(c) {
    document.getElementById('chambreId').value        = c.id;
    document.getElementById('chambreServiceId').value = c.service_id;
    document.getElementById('nomChambre').value       = c.nom_chambre;
    document.getElementById('typeChambre').value      = c.type_chambre;
    document.getElementById('chambreModalTitle').textContent = 'Modifier : ' + c.nom_chambre;
    chambreModal.show();
}
function deleteChambre(id, nom) {
    if (!confirm(`Supprimer la chambre "${nom}" ?`)) return;
    fetch('<?= BASE_URL ?>admin/delete-chambre/' + id, { method: 'POST' })
        .then(r => r.json()).then(d => {
            if (d.success) { showToast('Chambre supprimée.'); setTimeout(() => location.reload(), 800); }
            else showToast(d.message || 'Erreur', false);
        });
}

// ── LIT ────────────────────────────────────────────────────────────────────
function openLitModal() {
    document.getElementById('formLit').reset();
    document.getElementById('litId').value = '';
    document.getElementById('litModalTitle').textContent = 'Nouveau Lit';
    litModal.show();
}
function editLit(l) {
    document.getElementById('litId').value        = l.id;
    document.getElementById('litChambreId').value = l.chambre_id;
    document.getElementById('nomLit').value       = l.nom_lit;
    document.getElementById('litStatut').value    = l.statut;
    document.getElementById('litModalTitle').textContent = 'Modifier : Lit ' + l.nom_lit;
    litModal.show();
}
function deleteLit(id, nom) {
    if (!confirm(`Supprimer le lit "${nom}" ?`)) return;
    fetch('<?= BASE_URL ?>admin/delete-lit/' + id, { method: 'POST' })
        .then(r => r.json()).then(d => {
            if (d.success) { showToast('Lit supprimé.'); setTimeout(() => location.reload(), 800); }
            else showToast(d.message || 'Erreur', false);
        });
}
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
