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
$adminPage = 'utilisateurs';
require_once __DIR__ . '/../admin/partials/admin_nav.php';
?>

<div class="admin-topbar">
    <div class="admin-topbar-title">
        Résultat de l'import Excel
        <small>Import des utilisateurs depuis un fichier Excel/CSV</small>
    </div>
    <a href="<?= BASE_URL ?>utilisateurs" class="btn btn-outline-secondary rounded-pill px-4">
        <i class="bi bi-arrow-left me-2"></i>Retour à la liste
    </a>
</div>

<div class="admin-page-content">

<?php if (!empty($stats['error'])): ?>

    <!-- ❌ Erreur globale -->
    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-4">
            <div class="alert alert-danger border-0 mb-0 rounded-3">
                <i class="bi bi-x-circle-fill me-2"></i>
                <strong>Erreur d'import :</strong> <?= htmlspecialchars($stats['error']) ?>
            </div>
            <div class="text-center mt-4">
                <a href="<?= BASE_URL ?>utilisateurs" class="btn btn-primary rounded-pill px-5">
                    <i class="bi bi-arrow-left me-2"></i>Retour
                </a>
            </div>
        </div>
    </div>

<?php else: ?>

    <!-- ✅ Statistiques -->
    <div class="row g-3 mb-3">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body text-center p-3">
                    <div class="text-muted small mb-1">Lignes traitées</div>
                    <div class="fw-bold" style="font-size:1.8rem;color:#0f172a;">
                        <?= (int)$stats['lignes'] ?>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 h-100" style="background:linear-gradient(135deg,#f0fdf4,#dcfce7);">
                <div class="card-body text-center p-3">
                    <div class="text-muted small mb-1">
                        <i class="bi bi-person-plus-fill me-1 text-success"></i>Créés
                    </div>
                    <div class="fw-bold" style="font-size:1.8rem;color:#16a34a;">
                        <?= (int)$stats['crees'] ?>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 h-100" style="background:linear-gradient(135deg,#eff6ff,#dbeafe);">
                <div class="card-body text-center p-3">
                    <div class="text-muted small mb-1">
                        <i class="bi bi-pencil-square me-1 text-primary"></i>Mis à jour
                    </div>
                    <div class="fw-bold" style="font-size:1.8rem;color:#2563eb;">
                        <?= (int)$stats['maj'] ?>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 h-100" style="background:<?= $stats['erreurs']>0 ? 'linear-gradient(135deg,#fef2f2,#fee2e2)' : 'linear-gradient(135deg,#f8fafc,#f1f5f9)' ?>;">
                <div class="card-body text-center p-3">
                    <div class="text-muted small mb-1">
                        <i class="bi bi-exclamation-triangle-fill me-1 text-<?= $stats['erreurs']>0 ? 'danger' : 'muted' ?>"></i>
                        Erreurs
                    </div>
                    <div class="fw-bold" style="font-size:1.8rem;color:<?= $stats['erreurs']>0 ? '#dc2626' : '#64748b' ?>;">
                        <?= (int)$stats['erreurs'] ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 🔑 Mots de passe générés -->
    <?php if (!empty($stats['creations'])):
        $generated = array_filter($stats['creations'], fn($c) => $c['generated']);
    ?>
    <?php if (!empty($generated)): ?>
    <div class="card border-0 shadow-sm rounded-4 mb-3">
        <div class="card-header bg-warning-subtle border-0 py-3">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-0 fw-bold text-warning-emphasis">
                        <i class="bi bi-key-fill me-2"></i>Mots de passe générés
                    </h5>
                    <small class="text-muted">
                        Notez ces mots de passe maintenant — ils ne seront plus affichés.
                    </small>
                </div>
                <button class="btn btn-sm btn-warning rounded-pill px-3" onclick="copierMotsDePasse()">
                    <i class="bi bi-clipboard me-1"></i>Tout copier
                </button>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" style="font-size:.85rem;" id="tablePwd">
                    <thead style="background:#fffbeb;">
                        <tr class="text-muted" style="font-size:.7rem;text-transform:uppercase;letter-spacing:.05em;">
                            <th class="ps-4 py-2">Utilisateur</th>
                            <th class="py-2">Username</th>
                            <th class="py-2">Mot de passe (texte clair)</th>
                            <th class="py-2 text-end pe-4">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($generated as $c): ?>
                        <tr>
                            <td class="ps-4 fw-semibold"><?= htmlspecialchars($c['nom']) ?></td>
                            <td><code class="text-primary"><?= htmlspecialchars($c['username']) ?></code></td>
                            <td>
                                <code class="bg-warning-subtle px-2 py-1 rounded font-monospace user-select-all" data-pwd>
                                    <?= htmlspecialchars($c['password']) ?>
                                </code>
                            </td>
                            <td class="text-end pe-4">
                                <button class="btn btn-sm btn-outline-warning rounded-pill px-3"
                                        onclick="copierLigne(this)" data-pwd-row>
                                    <i class="bi bi-clipboard"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>
    <?php endif; ?>

    <!-- ⚠ Détail des erreurs -->
    <?php if (!empty($stats['details'])): ?>
    <div class="card border-0 shadow-sm rounded-4 mb-3">
        <div class="card-header bg-danger-subtle border-0 py-3">
            <h5 class="mb-0 fw-bold text-danger">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                Détail des erreurs (<?= count($stats['details']) ?>)
            </h5>
            <small class="text-muted">Les lignes en erreur n'ont pas été importées.</small>
        </div>
        <div class="card-body" style="max-height:400px;overflow-y:auto;">
            <ul class="mb-0" style="font-size:.88rem;">
                <?php foreach ($stats['details'] as $d): ?>
                <li class="mb-1"><?= htmlspecialchars($d) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
    <?php endif; ?>

    <div class="d-flex justify-content-between align-items-center mt-4">
        <a href="<?= BASE_URL ?>utilisateurs/export"
           class="btn btn-outline-success rounded-pill px-4">
            <i class="bi bi-file-earmark-spreadsheet me-2"></i>Télécharger l'état actuel
        </a>
        <a href="<?= BASE_URL ?>utilisateurs"
           class="btn btn-primary rounded-pill px-5">
            <i class="bi bi-check2-circle me-2"></i>Retour à la liste
        </a>
    </div>

<?php endif; ?>

</div><!-- /admin-page-content -->

<script>
function copierLigne(btn) {
    const tr = btn.closest('tr');
    const pwd = tr.querySelector('[data-pwd]').textContent.trim();
    const username = tr.querySelector('code').textContent.trim();
    navigator.clipboard.writeText(`${username} : ${pwd}`).then(() => {
        btn.innerHTML = '<i class="bi bi-check-lg"></i>';
        setTimeout(() => btn.innerHTML = '<i class="bi bi-clipboard"></i>', 1500);
    });
}
function copierMotsDePasse() {
    const lignes = [];
    document.querySelectorAll('#tablePwd tbody tr').forEach(tr => {
        const username = tr.querySelector('code').textContent.trim();
        const pwd      = tr.querySelector('[data-pwd]').textContent.trim();
        const nom      = tr.querySelector('td').textContent.trim();
        lignes.push(`${nom} (${username}) : ${pwd}`);
    });
    navigator.clipboard.writeText(lignes.join('\n')).then(() => {
        alert('Tous les mots de passe ont été copiés dans le presse-papiers.');
    });
}
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
