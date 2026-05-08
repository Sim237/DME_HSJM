<?php
require_once __DIR__ . '/../layouts/header.php';
$adminPage = 'permissions';
require_once __DIR__ . '/partials/admin_nav.php';

$permLevels = [
    ''      => ['label' => 'Aucun',  'color' => '#94a3b8', 'bg' => '#f1f5f9'],
    'READ'  => ['label' => 'Lecture','color' => '#0ea5e9', 'bg' => '#e0f2fe'],
    'write' => ['label' => 'Écriture','color' => '#10b981','bg' => '#d1fae5'],
    'delete'=> ['label' => 'Suppression','color' => '#f59e0b','bg' => '#fef3c7'],
    'admin' => ['label' => 'Admin complet','color' => '#8b5cf6','bg' => '#ede9fe'],
];
$roleLabels = [
    'ADMIN'      => 'Administrateur',
    'MEDECIN'    => 'Médecin',
    'INFIRMIER'  => 'Infirmier',
    'SECRETAIRE' => 'Secrétaire',
    'LABORANTIN' => 'Laborantin',
    'PHARMACIEN' => 'Pharmacien',
    'PARAMETRES' => 'Inf. Paramètres',
    'DIRECTEUR'  => 'Directeur',
];
?>

        <!-- TOPBAR -->
        <div class="admin-topbar">
            <div class="admin-topbar-title">
                Rôles &amp; Permissions
                <small>Définir les droits d'accès par rôle et par module</small>
            </div>
            <div class="d-flex align-items-center gap-2">
                <div class="d-flex gap-1 flex-wrap">
                    <?php foreach (array_slice($permLevels, 1) as $key => $perm): ?>
                    <span class="badge rounded-pill" style="background:<?= $perm['bg'] ?>; color:<?= $perm['color'] ?>; font-size:0.68rem; padding:4px 10px;">
                        <?= $perm['label'] ?>
                    </span>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <div class="admin-page-content">

            <div class="alert alert-info border-0 rounded-4 mb-4" style="font-size:0.82rem;">
                <i class="bi bi-info-circle-fill me-2"></i>
                Cliquez sur une cellule pour faire défiler les niveaux : <strong>Aucun → Lecture → Écriture → Suppression → Admin</strong>.
                Les modifications sont enregistrées immédiatement.
            </div>

            <!-- LÉGENDE -->
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-bordered align-middle mb-0" id="permMatrix">
                            <thead>
                                <tr class="bg-dark text-white" style="font-size:0.72rem;">
                                    <th class="py-3 px-4" style="min-width:180px; background:#0f172a;">MODULE</th>
                                    <?php foreach ($roleLabels as $role => $label): ?>
                                    <th class="py-3 px-3 text-center" style="min-width:110px; background:#0f172a; white-space:nowrap;">
                                        <?= $label ?>
                                        <div style="font-size:0.6rem; color:#64748b; font-weight:400;"><?= $role ?></div>
                                    </th>
                                    <?php endforeach; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($modules as $modKey => $modLabel): ?>
                                <tr>
                                    <td class="px-4 fw-bold" style="font-size:0.82rem; background:#f8fafc; border-right:2px solid #e2e8f0;">
                                        <i class="bi bi-grid me-2 text-muted"></i><?= $modLabel ?>
                                    </td>
                                    <?php foreach ($roleLabels as $role => $rl):
                                        $curPerm = $matrix[$role][$modKey] ?? '';
                                        $permKeys = array_keys($permLevels);
                                        $curIdx = array_search($curPerm, $permKeys);
                                        if ($curIdx === false) $curIdx = 0;
                                        $pData = $permLevels[$curPerm];
                                    ?>
                                    <td class="text-center p-1">
                                        <button type="button"
                                                class="perm-cell btn btn-sm rounded-3 w-100"
                                                data-role="<?= $role ?>"
                                                data-module="<?= $modKey ?>"
                                                data-current="<?= $curPerm ?>"
                                                style="background:<?= $pData['bg'] ?>; color:<?= $pData['color'] ?>; font-size:0.7rem; font-weight:700; border:1px solid <?= $pData['color'] ?>33; padding:6px 4px; transition:0.15s;"
                                                title="Cliquer pour changer">
                                            <?= $pData['label'] ?>
                                        </button>
                                    </td>
                                    <?php endforeach; ?>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- GUIDE RÔLES -->
            <div class="row g-3 mt-3">
                <?php foreach ($roleLabels as $role => $label): ?>
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm rounded-4 p-3">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <span class="badge bg-dark text-white"><?= $role ?></span>
                            <span class="fw-bold small"><?= $label ?></span>
                        </div>
                        <div style="font-size:0.72rem; color:#64748b; line-height:1.6;">
                            <?php
                            $rolePerms = $matrix[$role] ?? [];
                            $hasPerm = array_filter($rolePerms, fn($p) => !empty($p));
                            if (empty($hasPerm)) echo 'Aucune permission configurée.';
                            else foreach ($hasPerm as $mod => $perm):
                                $p = $permLevels[$perm] ?? $permLevels[''];
                            ?>
                            <span class="me-1" style="color:<?= $p['color'] ?>;">●</span>
                            <?= htmlspecialchars($modules[$mod] ?? $mod) ?><br>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

        </div><!-- /admin-page-content -->
    </div><!-- /admin-main -->
</div><!-- /admin-shell -->

<!-- TOAST -->
<div class="toast-container">
    <div id="adminToast" class="toast align-items-center border-0 text-white" role="alert">
        <div class="d-flex">
            <div class="toast-body fw-bold" id="adminToastMsg"></div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    </div>
</div>

<script>
const PERM_LEVELS = <?= json_encode(array_keys($permLevels)) ?>;
const PERM_DATA   = <?= json_encode($permLevels) ?>;

function showToast(msg, ok = true) {
    const t = document.getElementById('adminToast');
    t.className = 'toast align-items-center border-0 text-white ' + (ok ? 'bg-success' : 'bg-danger');
    document.getElementById('adminToastMsg').textContent = msg;
    bootstrap.Toast.getOrCreateInstance(t).show();
}

document.querySelectorAll('.perm-cell').forEach(btn => {
    btn.addEventListener('click', function() {
        const role    = this.dataset.role;
        const module  = this.dataset.module;
        const current = this.dataset.current || '';

        // Cycle through levels
        const idx     = PERM_LEVELS.indexOf(current);
        const nextIdx = (idx + 1) % PERM_LEVELS.length;
        const next    = PERM_LEVELS[nextIdx];
        const pData   = PERM_DATA[next];

        // Optimistic UI update
        this.dataset.current = next;
        this.textContent = pData.label;
        this.style.background = pData.bg;
        this.style.color       = pData.color;
        this.style.border      = '1px solid ' + pData.color + '33';

        // Save to server
        const fd = new FormData();
        fd.append('role', role);
        fd.append('module', module);
        fd.append('permission', next);

        fetch('<?= BASE_URL ?>admin/save-permission', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(d => {
                if (!d.success) {
                    showToast(d.message || 'Erreur de sauvegarde', false);
                } else {
                    showToast(`${role} / ${module} → ${pData.label || 'Aucun'}`);
                }
            })
            .catch(() => showToast('Erreur réseau', false));
    });
});
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
