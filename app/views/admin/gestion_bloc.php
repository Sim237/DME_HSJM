<?php
require_once __DIR__ . '/../layouts/header.php';
$adminPage = 'bloc';
require_once __DIR__ . '/partials/admin_nav.php';
?>

<!-- TOPBAR -->
<div class="admin-topbar">
    <div class="admin-topbar-title">
        Bloc Opératoire &amp; Anesthésie
        <small>Gérer les salles · suivre les interventions · accéder à la SSPI</small>
    </div>
    <div class="adm-topbar-actions">
        <a href="<?= BASE_URL ?>bloc" class="adm-topbtn adm-topbtn-ghost" target="_blank">
            <i class="bi bi-box-arrow-up-right"></i> Vue opérationnelle
        </a>
        <a href="<?= BASE_URL ?>bloc/sspi-dashboard" class="adm-topbtn adm-topbtn-ghost" target="_blank">
            <i class="bi bi-heart-pulse-fill"></i> SSPI
        </a>
        <button class="adm-topbtn adm-topbtn-primary" onclick="ouvrirModalSalle()">
            <i class="bi bi-plus-circle-fill"></i> Nouvelle Salle
        </button>
    </div>
</div>

<div class="admin-page-content">

    <!-- ── Stats ── -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="adm-stat" style="--adm-accent:#3b82f6;">
                <div class="adm-stat-val"><?= (int)$stats['total'] ?></div>
                <div class="adm-stat-lbl">Salles configurées</div>
                <i class="bi bi-scissors adm-stat-ico"></i>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="adm-stat" style="--adm-accent:#22c55e;">
                <div class="adm-stat-val"><?= (int)$stats['disponibles'] ?></div>
                <div class="adm-stat-lbl">Disponibles</div>
                <i class="bi bi-check-circle-fill adm-stat-ico"></i>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="adm-stat" style="--adm-accent:#ea580c;">
                <div class="adm-stat-val"><?= (int)$stats['interventions_jour'] ?></div>
                <div class="adm-stat-lbl">Interventions aujourd'hui</div>
                <i class="bi bi-calendar-check-fill adm-stat-ico"></i>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="adm-stat" style="--adm-accent:#f59e0b;">
                <div class="adm-stat-val"><?= (int)$stats['en_attente'] ?></div>
                <div class="adm-stat-lbl">Patients en attente</div>
                <i class="bi bi-hourglass-split adm-stat-ico"></i>
            </div>
        </div>
    </div>

    <!-- ── Table des salles ── -->
    <div class="adm-card mb-4">
        <div class="adm-card-header">
            <span><i class="bi bi-scissors me-2" style="color:#ea580c;"></i>Salles d'opération</span>
            <span style="background:#f1f5f9;border:1px solid #e2e8f0;border-radius:8px;
                         padding:2px 10px;font-size:.72rem;font-weight:700;color:#64748b;">
                <?= count($salles) ?> salle(s)
            </span>
        </div>
        <div class="table-responsive">
            <table class="adm-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Salle</th>
                        <th>Type</th>
                        <th>Statut</th>
                        <th>Interv. totales</th>
                        <th>Aujourd'hui</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($salles)): ?>
                    <tr>
                        <td colspan="7" class="text-center py-5">
                            <i class="bi bi-scissors d-block mb-2" style="font-size:2.2rem;opacity:.15;"></i>
                            <span class="text-muted">Aucune salle configurée. Créez la première salle.</span>
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($salles as $s):
                        $st = strtoupper($s['statut'] ?? 'DISPONIBLE');
                        $stMap = [
                            'DISPONIBLE'  => ['#f0fdf4','#166534','#86efac'],
                            'OCCUPEE'     => ['#fef2f2','#991b1b','#fca5a5'],
                            'NETTOYAGE'   => ['#fffbeb','#92400e','#fde68a'],
                            'MAINTENANCE' => ['#f8fafc','#475569','#cbd5e1'],
                        ];
                        [$bg,$fg,$bd] = $stMap[$st] ?? ['#f8fafc','#475569','#cbd5e1'];
                        $stLabel = ['DISPONIBLE'=>'Disponible','OCCUPEE'=>'Occupée',
                                    'NETTOYAGE'=>'Nettoyage','MAINTENANCE'=>'Maintenance'][$st] ?? $st;
                    ?>
                    <tr>
                        <td style="color:#94a3b8;font-size:.75rem;">#<?= (int)$s['id'] ?></td>
                        <td>
                            <div style="display:flex;align-items:center;gap:10px;">
                                <div style="width:36px;height:36px;border-radius:10px;background:#fff7ed;
                                            color:#ea580c;display:flex;align-items:center;justify-content:center;
                                            font-size:.95rem;flex-shrink:0;">
                                    <i class="bi bi-scissors"></i>
                                </div>
                                <strong><?= htmlspecialchars($s['nom_salle']) ?></strong>
                            </div>
                        </td>
                        <td><span style="color:#64748b;font-size:.82rem;"><?= htmlspecialchars($s['type_salle'] ?? '—') ?></span></td>
                        <td>
                            <span style="background:<?= $bg ?>;color:<?= $fg ?>;border:1px solid <?= $bd ?>;
                                         font-size:.7rem;font-weight:800;padding:3px 10px;border-radius:20px;
                                         display:inline-block;">
                                <?= $stLabel ?>
                            </span>
                        </td>
                        <td style="font-weight:700;"><?= (int)($s['total_interventions'] ?? 0) ?></td>
                        <td>
                            <?php $auj = (int)($s['interventions_jour'] ?? 0); ?>
                            <span style="font-weight:700;color:<?= $auj > 0 ? '#ea580c' : '#94a3b8' ?>;">
                                <?= $auj ?>
                            </span>
                        </td>
                        <td class="text-end">
                            <button class="adm-action-btn me-1" title="Modifier"
                                    style="color:#3b82f6;border-color:#bfdbfe;"
                                    onclick='ouvrirModalSalle(<?= htmlspecialchars(json_encode([
                                        "id"         => $s["id"],
                                        "nom_salle"  => $s["nom_salle"],
                                        "type_salle" => $s["type_salle"] ?? "",
                                        "statut"     => $s["statut"] ?? "DISPONIBLE",
                                    ]), ENT_QUOTES) ?>)'>
                                <i class="bi bi-pencil-fill"></i>
                            </button>
                            <button class="adm-action-btn" title="Supprimer"
                                    style="color:#dc2626;border-color:#fecaca;"
                                    onclick="supprimerSalle(<?= (int)$s['id'] ?>,'<?= addslashes(htmlspecialchars($s['nom_salle'])) ?>')">
                                <i class="bi bi-trash-fill"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- ── Accès rapides ── -->
    <div class="adm-section-title"><i class="bi bi-lightning-charge-fill"></i> Accès rapides</div>
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <a href="<?= BASE_URL ?>bloc" target="_blank"
               class="adm-card d-flex align-items-center gap-3 p-3 text-decoration-none"
               style="border-left:4px solid #ea580c;transition:transform .15s;"
               onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform=''">
                <div style="width:46px;height:46px;border-radius:13px;background:#fff7ed;color:#ea580c;
                            display:flex;align-items:center;justify-content:center;font-size:1.3rem;flex-shrink:0;">
                    <i class="bi bi-scissors"></i>
                </div>
                <div style="flex:1;">
                    <div style="font-weight:800;font-size:.88rem;color:#0f172a;">Vue opérationnelle du bloc</div>
                    <div style="font-size:.72rem;color:#94a3b8;">Programme du jour &amp; gestion des interventions</div>
                </div>
                <i class="bi bi-arrow-right-circle" style="color:#cbd5e1;font-size:1.2rem;"></i>
            </a>
        </div>
        <div class="col-md-4">
            <a href="<?= BASE_URL ?>bloc/sspi-dashboard" target="_blank"
               class="adm-card d-flex align-items-center gap-3 p-3 text-decoration-none"
               style="border-left:4px solid #d97706;transition:transform .15s;"
               onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform=''">
                <div style="width:46px;height:46px;border-radius:13px;background:#fef3c7;color:#d97706;
                            display:flex;align-items:center;justify-content:center;font-size:1.3rem;flex-shrink:0;">
                    <i class="bi bi-heart-pulse-fill"></i>
                </div>
                <div style="flex:1;">
                    <div style="font-weight:800;font-size:.88rem;color:#0f172a;">Salle de réveil (SSPI)</div>
                    <div style="font-size:.72rem;color:#94a3b8;">Surveillance post-interventionnelle · Aldrete</div>
                </div>
                <i class="bi bi-arrow-right-circle" style="color:#cbd5e1;font-size:1.2rem;"></i>
            </a>
        </div>
        <div class="col-md-4">
            <a href="<?= BASE_URL ?>admin/permissions"
               class="adm-card d-flex align-items-center gap-3 p-3 text-decoration-none"
               style="border-left:4px solid #6366f1;transition:transform .15s;"
               onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform=''">
                <div style="width:46px;height:46px;border-radius:13px;background:#eef2ff;color:#6366f1;
                            display:flex;align-items:center;justify-content:center;font-size:1.3rem;flex-shrink:0;">
                    <i class="bi bi-shield-fill-check"></i>
                </div>
                <div style="flex:1;">
                    <div style="font-weight:800;font-size:.88rem;color:#0f172a;">Accès &amp; Permissions</div>
                    <div style="font-size:.72rem;color:#94a3b8;">Configurer IBODE, IADE, chirurgiens</div>
                </div>
                <i class="bi bi-arrow-right-circle" style="color:#cbd5e1;font-size:1.2rem;"></i>
            </a>
        </div>
    </div>

</div><!-- /.admin-page-content -->

<!-- ═══════════════ MODAL SALLE ═══════════════ -->
<div class="modal fade" id="modalSalle" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width:500px;">
        <div class="modal-content border-0 shadow-lg" style="border-radius:18px;overflow:hidden;">

            <div class="modal-header border-0 p-4" style="background:linear-gradient(135deg,#ea580c,#f97316);">
                <div>
                    <h5 class="modal-title fw-bold text-white mb-0" id="modalSalleTitre">
                        <i class="bi bi-scissors me-2"></i>Nouvelle salle d'opération
                    </h5>
                    <div class="text-white opacity-75 small mt-1">Bloc opératoire · SimCare+</div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <form id="formSalle" novalidate>
            <div class="modal-body p-4">

                <input type="hidden" name="id" id="salleId">

                <div class="mb-3">
                    <label class="form-label fw-semibold small text-muted mb-1">
                        Nom de la salle <span class="text-danger">*</span>
                    </label>
                    <input type="text" name="nom_salle" id="salleNom"
                           class="form-control border-2" style="border-radius:10px;"
                           placeholder="ex : Salle 1 — Chirurgie générale" autocomplete="off">
                </div>

                <div class="row g-3">
                    <div class="col-md-7">
                        <label class="form-label fw-semibold small text-muted mb-1">Type de salle</label>
                        <select name="type_salle" id="salleType" class="form-select border-2" style="border-radius:10px;">
                            <option value="CHIRURGIE">Chirurgie</option>
                            <option value="AMBULATOIRE">Ambulatoire</option>
                            <option value="URGENCE">Urgence</option>
                        </select>
                    </div>
                    <div class="col-md-5">
                        <label class="form-label fw-semibold small text-muted mb-1">Statut initial</label>
                        <select name="statut" id="salleStatut" class="form-select border-2" style="border-radius:10px;">
                            <option value="DISPONIBLE">✅ Disponible</option>
                            <option value="NETTOYAGE">🧹 Nettoyage</option>
                            <option value="MAINTENANCE">🔧 Maintenance</option>
                        </select>
                    </div>
                </div>

                <div id="salleMsgErreur" class="mt-3 d-none"
                     style="background:#fef2f2;border:1px solid #fecaca;border-radius:10px;
                            padding:.75rem 1rem;font-size:.82rem;color:#991b1b;"></div>

            </div>
            <div class="modal-footer border-0 px-4 pb-4 pt-0" style="background:#f8fafc;">
                <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Annuler</button>
                <button type="button" id="salleBtnSubmit"
                        class="btn rounded-pill px-5 fw-bold text-white"
                        style="background:linear-gradient(135deg,#ea580c,#f97316);box-shadow:0 4px 12px rgba(234,88,12,.35);"
                        onclick="soumettreFormSalle()">
                    <i class="bi bi-check-circle-fill me-1"></i>Enregistrer
                </button>
            </div>
            </form>
        </div>
    </div>
</div>

<!-- Toast -->
<div id="blocToastCont" class="position-fixed bottom-0 end-0 p-3" style="z-index:9999;"></div>

<script>
(function () {
    const BURL = '<?= BASE_URL ?>';

    function toast(msg, ok = true) {
        const t = document.createElement('div');
        t.className = 'toast show align-items-center text-white border-0 rounded-3 shadow mb-2';
        t.style.cssText = `min-width:300px;background:${ok ? '#16a34a' : '#dc2626'};`;
        t.innerHTML = `<div class="d-flex">
            <div class="toast-body fw-semibold">
                <i class="bi bi-${ok ? 'check-circle-fill' : 'x-circle-fill'} me-2"></i>${msg}
            </div>
            <button class="btn-close btn-close-white me-2 m-auto"
                    onclick="this.closest('.toast').remove()"></button>
        </div>`;
        document.getElementById('blocToastCont').appendChild(t);
        setTimeout(() => t.remove(), 4000);
    }

    window.ouvrirModalSalle = function (data) {
        const el  = document.getElementById('modalSalle');
        const mod = bootstrap.Modal.getOrCreateInstance(el);

        document.getElementById('salleId').value     = data?.id         || '';
        document.getElementById('salleNom').value    = data?.nom_salle  || '';
        document.getElementById('salleType').value   = data?.type_salle || 'CHIRURGIE';
        document.getElementById('salleStatut').value = data?.statut     || 'DISPONIBLE';

        document.getElementById('modalSalleTitre').innerHTML =
            `<i class="bi bi-scissors me-2"></i>${data?.id ? 'Modifier la salle' : 'Nouvelle salle d\'opération'}`;

        document.getElementById('salleMsgErreur').classList.add('d-none');
        const btn = document.getElementById('salleBtnSubmit');
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-check-circle-fill me-1"></i>Enregistrer';

        mod.show();
    };

    window.soumettreFormSalle = function () {
        const form   = document.getElementById('formSalle');
        const errDiv = document.getElementById('salleMsgErreur');
        const btn    = document.getElementById('salleBtnSubmit');

        const nom = document.getElementById('salleNom').value.trim();
        if (!nom) {
            errDiv.textContent = 'Le nom de la salle est requis.';
            errDiv.classList.remove('d-none');
            return;
        }
        errDiv.classList.add('d-none');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Enregistrement…';

        fetch(BURL + 'admin/bloc/sauvegarder-salle', { method: 'POST', body: new FormData(form) })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    bootstrap.Modal.getInstance(document.getElementById('modalSalle')).hide();
                    toast(data.message);
                    setTimeout(() => location.reload(), 1000);
                } else {
                    errDiv.textContent = data.message || 'Erreur.';
                    errDiv.classList.remove('d-none');
                    btn.disabled = false;
                    btn.innerHTML = '<i class="bi bi-check-circle-fill me-1"></i>Enregistrer';
                }
            })
            .catch(() => {
                errDiv.textContent = 'Erreur réseau. Veuillez réessayer.';
                errDiv.classList.remove('d-none');
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-check-circle-fill me-1"></i>Enregistrer';
            });
    };

    window.supprimerSalle = function (id, nom) {
        if (!confirm(`Supprimer la salle « ${nom} » ?\n\nCette action est irréversible.`)) return;
        const fd = new FormData();
        fd.append('id', id);
        fetch(BURL + 'admin/bloc/supprimer-salle', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(data => {
                toast(data.message, data.success);
                if (data.success) setTimeout(() => location.reload(), 1000);
            })
            .catch(() => toast('Erreur réseau.', false));
    };
})();
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
