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
.archive-hero {
    background: linear-gradient(135deg, #1a4e96 0%, #0e2d5a 100%);
    color: #fff;
    border-radius: 16px;
    padding: 22px 28px;
    margin-bottom: 24px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.doc-row { cursor: pointer; transition: background .12s; }
.doc-row:hover { background: #f0f4ff !important; }
.hash-pill {
    font-family: monospace;
    font-size: 10px;
    background: #f1f5f9;
    border: 1px solid #e2e8f0;
    border-radius: 4px;
    padding: 1px 7px;
    color: #475569;
    letter-spacing: .3px;
}
.verify-ok   { color: #16a34a; }
.verify-fail { color: #dc2626; }
</style>

<div class="container-fluid bg-light" style="min-height:100vh;">
<div class="row">
    <?php require_once __DIR__ . '/../layouts/sidebar.php'; ?>
    <main class="col-md-10 ms-sm-auto px-md-4 pt-4 pb-5">

        <!-- HERO -->
        <div class="archive-hero">
            <div>
                <h4 class="fw-bold mb-1"><i class="bi bi-archive-fill me-2"></i>Archives — Documents Signés</h4>
                <div style="opacity:.75;font-size:.85rem;">Historique des ordonnances signées électroniquement</div>
            </div>
            <div class="text-end">
                <div style="font-size:2rem;font-weight:800;"><?= count($documents) ?></div>
                <div style="opacity:.75;font-size:.8rem;">document(s) trouvé(s)</div>
            </div>
        </div>

        <!-- FILTRES -->
        <form method="GET" class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-body p-3">
                <div class="row g-2 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label fw-bold small mb-1">Patient</label>
                        <input type="text" name="patient" class="form-control form-control-sm rounded-3"
                               placeholder="Nom, prénom ou N° dossier…"
                               value="<?= htmlspecialchars($_GET['patient'] ?? '') ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold small mb-1">Date de signature</label>
                        <input type="date" name="date" class="form-control form-control-sm rounded-3"
                               value="<?= htmlspecialchars($_GET['date'] ?? '') ?>">
                    </div>
                    <div class="col-md-3 d-flex gap-2 align-items-end">
                        <button type="submit" class="btn btn-primary btn-sm rounded-3 px-4 flex-grow-1">
                            <i class="bi bi-search me-1"></i>Filtrer
                        </button>
                        <a href="<?= BASE_URL ?>prescription/archives" class="btn btn-light btn-sm rounded-3 border">
                            <i class="bi bi-x-circle"></i>
                        </a>
                    </div>
                </div>
            </div>
        </form>

        <!-- TABLEAU -->
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" style="font-size:.83rem;">
                        <thead style="background:#f8fafc;">
                            <tr class="text-muted" style="font-size:.7rem;text-transform:uppercase;letter-spacing:.06em;">
                                <th class="ps-4 py-3">Ordonnance</th>
                                <th class="py-3">Patient</th>
                                <th class="py-3">Médecin</th>
                                <th class="py-3">Médicaments</th>
                                <th class="py-3">Date émission</th>
                                <th class="py-3">Signé le</th>
                                <th class="py-3">Code vérif.</th>
                                <th class="py-3 pe-4 text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($documents)): ?>
                            <tr>
                                <td colspan="8" class="text-center py-5 text-muted">
                                    <i class="bi bi-archive d-block fs-1 mb-2 opacity-25"></i>
                                    Aucun document signé trouvé.
                                </td>
                            </tr>
                            <?php else: foreach ($documents as $doc): ?>
                            <tr class="doc-row" onclick="window.open('<?= BASE_URL ?>prescription/print?id=<?= $doc['id'] ?>', '_blank')">
                                <td class="ps-4">
                                    <span class="fw-bold text-primary">
                                        N° <?= str_pad($doc['id'], 6, '0', STR_PAD_LEFT) ?>
                                    </span>
                                    <br>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle" style="font-size:.65rem;">
                                        <i class="bi bi-shield-check me-1"></i>Signé
                                    </span>
                                </td>
                                <td>
                                    <div class="fw-bold"><?= htmlspecialchars(strtoupper($doc['patient_nom']) . ' ' . $doc['patient_prenom']) ?></div>
                                    <small class="text-muted"><?= htmlspecialchars($doc['dossier_numero']) ?></small>
                                </td>
                                <td>
                                    <div class="fw-semibold">Dr. <?= htmlspecialchars($doc['medecin_prenom'] . ' ' . $doc['medecin_nom']) ?></div>
                                    <?php if (!empty($doc['specialite'])): ?>
                                    <small class="text-muted"><?= htmlspecialchars($doc['specialite']) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-secondary bg-opacity-10 text-secondary fw-bold">
                                        <?= (int)$doc['nb_medicaments'] ?> méd.
                                    </span>
                                </td>
                                <td>
                                    <span class="fw-semibold"><?= date('d/m/Y', strtotime($doc['date_creation'])) ?></span>
                                    <br><small class="text-muted"><?= date('H:i', strtotime($doc['date_creation'])) ?></small>
                                </td>
                                <td>
                                    <?php if (!empty($doc['signed_at'])): ?>
                                    <span class="fw-semibold text-success"><?= date('d/m/Y', strtotime($doc['signed_at'])) ?></span>
                                    <br><small class="text-muted"><?= date('H:i', strtotime($doc['signed_at'])) ?></small>
                                    <?php else: ?>
                                    <span class="text-muted">—</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($doc['signature_hash'])): ?>
                                    <span class="hash-pill"><?= strtoupper(substr($doc['signature_hash'], 0, 12)) ?>…</span>
                                    <?php else: ?>
                                    <span class="text-muted">—</span>
                                    <?php endif; ?>
                                </td>
                                <td class="pe-4 text-end" onclick="event.stopPropagation()">
                                    <div class="d-flex gap-1 justify-content-end">
                                        <a href="<?= BASE_URL ?>prescription/print?id=<?= $doc['id'] ?>"
                                           target="_blank"
                                           class="btn btn-sm btn-outline-primary rounded-pill px-2"
                                           title="Voir / Imprimer">
                                            <i class="bi bi-printer"></i>
                                        </a>
                                        <?php if (!empty($doc['signature_hash'])): ?>
                                        <button class="btn btn-sm btn-outline-success rounded-pill px-2"
                                                title="Vérifier l'authenticité"
                                                onclick="verifyDoc(<?= $doc['id'] ?>, '<?= $doc['signature_hash'] ?>')">
                                            <i class="bi bi-shield-check"></i>
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </main>
</div>
</div>

<!-- MODAL VÉRIFICATION -->
<div class="modal fade" id="verifyModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-0 pb-0">
                <h5 class="fw-bold"><i class="bi bi-shield-lock me-2 text-primary"></i>Vérification d'authenticité</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4 text-center">
                <div id="verifyResult"></div>
            </div>
        </div>
    </div>
</div>

<script>
let verifyModalInst;
document.addEventListener('DOMContentLoaded', function() {
    verifyModalInst = new bootstrap.Modal(document.getElementById('verifyModal'));
});

function verifyDoc(id, hash) {
    document.getElementById('verifyResult').innerHTML = '<div class="spinner-border text-primary"></div><div class="mt-2 text-muted">Vérification en cours…</div>';
    verifyModalInst.show();

    fetch('<?= BASE_URL ?>prescription/verify', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'id=' + id + '&hash=' + encodeURIComponent(hash)
    })
    .then(r => r.json())
    .then(d => {
        if (d.valid) {
            document.getElementById('verifyResult').innerHTML =
                '<i class="bi bi-shield-check text-success" style="font-size:3rem;"></i>' +
                '<h5 class="text-success fw-bold mt-3">Document Authentique</h5>' +
                '<p class="text-muted mb-0">Ce document n\'a pas été altéré depuis sa signature.</p>' +
                '<div class="mt-3 p-2 bg-light rounded-3"><small class="font-monospace text-secondary">' + hash.substring(0,32) + '…</small></div>';
        } else {
            document.getElementById('verifyResult').innerHTML =
                '<i class="bi bi-shield-x text-danger" style="font-size:3rem;"></i>' +
                '<h5 class="text-danger fw-bold mt-3">Vérification échouée</h5>' +
                '<p class="text-muted mb-0">Le hash ne correspond pas. Ce document pourrait avoir été modifié.</p>';
        }
    })
    .catch(() => {
        document.getElementById('verifyResult').innerHTML = '<div class="text-danger">Erreur réseau</div>';
    });
}
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
