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
<?php require_once __DIR__ . '/partials/admin_nav.php'; ?>

<?php
// Compute quick stats from the current page's $patients array
// (full counts would require controller; fall back gracefully)
$cnt_actifs      = 0;
$cnt_hospitalise = 0;
$cnt_inactifs    = 0;
foreach ($patients as $_p) {
    if (!$_p['actif'])                          $cnt_inactifs++;
    elseif ($_p['statut'] === 'HOSPITALISE')    $cnt_hospitalise++;
    else                                        $cnt_actifs++;
}
?>

<style>
/* ─────────────────────────────────────────────────────────────
   ADMIN PATIENTS — feuille de style
───────────────────────────────────────────────────────────── */

/* ── Stats bar ── */
.stat-pill {
    display: flex; align-items: center; gap: .75rem;
    background: #fff; border: 1px solid #e2e8f0; border-radius: 14px;
    padding: .85rem 1.4rem; min-width: 160px;
    box-shadow: 0 1px 4px rgba(0,0,0,.05); transition: box-shadow .2s;
}
.stat-pill:hover { box-shadow: 0 4px 14px rgba(0,0,0,.1); }
.stat-pill .stat-icon {
    width: 44px; height: 44px; border-radius: 12px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.2rem; flex-shrink: 0;
}
.stat-pill .stat-value { font-size: 1.5rem; font-weight: 800; line-height: 1; }
.stat-pill .stat-label { font-size: .72rem; color: #64748b; font-weight: 600; text-transform: uppercase; letter-spacing: .5px; margin-top: 2px; }

/* ── Avatar circle ── */
.avatar-circle {
    width: 42px; height: 42px; border-radius: 12px;
    display: flex; align-items: center; justify-content: center;
    font-size: .85rem; font-weight: 800; flex-shrink: 0;
    color: #fff; text-transform: uppercase;
    box-shadow: 0 2px 6px rgba(0,0,0,.15);
}

/* ── Action icon buttons ── */
.btn-action {
    width: 34px; height: 34px; border-radius: 8px;
    display: inline-flex; align-items: center; justify-content: center;
    font-size: .85rem; padding: 0; border-width: 1px;
    transition: all .15s;
}
.btn-action:hover { transform: translateY(-1px); box-shadow: 0 3px 8px rgba(0,0,0,.14); }

/* ── Chips ── */
.chip {
    display: inline-flex; align-items: center; gap: .3rem;
    border-radius: 20px; padding: .25rem .75rem;
    font-size: .75rem; font-weight: 600; line-height: 1.4;
    white-space: nowrap;
}

/* ── Filter card ── */
.filter-card {
    background: #fff; border: 1px solid #e2e8f0;
    border-radius: 14px; box-shadow: 0 1px 4px rgba(0,0,0,.05);
}

/* ── Patients table ── */
.patients-table-card {
    background: #fff; border: 1px solid #e2e8f0;
    border-radius: 14px; box-shadow: 0 2px 8px rgba(0,0,0,.05);
    overflow: hidden;
}

.pat-table { width: 100%; border-collapse: collapse; }

.pat-table thead tr {
    background: #f8fafc;
    border-bottom: 2px solid #e2e8f0;
}
.pat-table thead th {
    font-size: .72rem; font-weight: 700;
    text-transform: uppercase; letter-spacing: .6px;
    color: #94a3b8; padding: .9rem 1rem;
    white-space: nowrap;
}
.pat-table thead th:first-child { padding-left: 1.5rem; }
.pat-table thead th:last-child  { padding-right: 1.5rem; text-align: right; }

.pat-table tbody tr {
    border-bottom: 1px solid #f1f5f9;
    transition: background .12s;
}
.pat-table tbody tr:last-child { border-bottom: none; }
.pat-table tbody tr:hover { background: #f8faff; }
.pat-table tbody tr.inactive { opacity: .55; }

.pat-table tbody td {
    padding: .85rem 1rem;
    vertical-align: middle;
    font-size: .85rem;
    color: #1e293b;
}
.pat-table tbody td:first-child { padding-left: 1.5rem; }
.pat-table tbody td:last-child  { padding-right: 1.5rem; }

/* ── Dossier badge ── */
.dossier-badge {
    display: inline-block;
    background: #f1f5f9; color: #334155;
    border: 1px solid #e2e8f0;
    border-radius: 8px; padding: .3rem .7rem;
    font-size: .78rem; font-weight: 700;
    letter-spacing: .3px; white-space: nowrap;
}
</style>

<div class="admin-main">

    <!-- ══════════════════════════════════════════════════════════
         TOPBAR
    ═══════════════════════════════════════════════════════════ -->
    <div class="admin-topbar">
        <div>
            <div class="admin-topbar-title">
                Gestion des Patients
                <small>Administration — données non médicales</small>
            </div>
        </div>
        <div class="adm-topbar-actions">
            <div class="adm-clock-pill">
                <span class="adm-clock-dot"></span>
                <span class="adm-clock-time">--:--:--</span>
            </div>
            <a href="<?= BASE_URL ?>patients/nouveau" class="adm-topbtn adm-topbtn-primary" style="text-decoration:none;">
                <i class="bi bi-person-plus-fill"></i> Nouveau patient
            </a>
        </div>
    </div>

    <div class="admin-page-content">

        <!-- ALERTES -->
        <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show border-0 rounded-3 shadow-sm mb-4" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i>
            <?= $_GET['success'] === 'updated' ? 'Patient mis à jour avec succès.' : 'Opération effectuée avec succès.' ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <!-- ══════════════════════════════════════════════════════
             STATS BAR
        ═══════════════════════════════════════════════════════ -->
        <div class="d-flex flex-wrap gap-3 mb-4">
            <!-- Total -->
            <div class="stat-pill">
                <div class="stat-icon" style="background:#eff6ff;">
                    <i class="bi bi-people-fill text-primary"></i>
                </div>
                <div>
                    <div class="stat-value text-primary"><?= number_format($total_patients) ?></div>
                    <div class="stat-label">Total patients</div>
                </div>
            </div>
            <!-- Actifs (page courante — indicatif) -->
            <div class="stat-pill">
                <div class="stat-icon" style="background:#f0fdf4;">
                    <i class="bi bi-person-check-fill text-success"></i>
                </div>
                <div>
                    <div class="stat-value text-success"><?= $cnt_actifs ?></div>
                    <div class="stat-label">Actifs</div>
                </div>
            </div>
            <!-- Hospitalisés -->
            <div class="stat-pill">
                <div class="stat-icon" style="background:#fff1f2;">
                    <i class="bi bi-hospital-fill" style="color:#dc2626;"></i>
                </div>
                <div>
                    <div class="stat-value" style="color:#dc2626;"><?= $cnt_hospitalise ?></div>
                    <div class="stat-label">Hospitalisés</div>
                </div>
            </div>
            <!-- Désactivés -->
            <div class="stat-pill">
                <div class="stat-icon" style="background:#f8fafc;">
                    <i class="bi bi-person-x-fill text-secondary"></i>
                </div>
                <div>
                    <div class="stat-value text-secondary"><?= $cnt_inactifs ?></div>
                    <div class="stat-label">Désactivés</div>
                </div>
            </div>
            <!-- Pages -->
            <?php if ($total_pages > 1): ?>
            <div class="stat-pill ms-auto" style="min-width:auto; background:#f8fafc; border-style:dashed;">
                <div class="stat-icon" style="background:#fff;">
                    <i class="bi bi-layout-text-sidebar-reverse text-muted"></i>
                </div>
                <div>
                    <div class="stat-value text-muted" style="font-size:1.1rem;"><?= $page ?> / <?= $total_pages ?></div>
                    <div class="stat-label">Page</div>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- ══════════════════════════════════════════════════════
             FILTRES & RECHERCHE
        ═══════════════════════════════════════════════════════ -->
        <div class="filter-card p-3 mb-4">
            <form method="GET" action="<?= BASE_URL ?>admin/patients" class="row g-2 align-items-end">
                <div class="col-md-4 col-lg-4">
                    <label class="form-label small fw-semibold text-muted mb-1">
                        <i class="bi bi-search me-1"></i>Recherche
                    </label>
                    <input type="text" name="search" class="form-control form-control-sm rounded-pill"
                           placeholder="Nom, prénom, n° dossier, téléphone…"
                           value="<?= htmlspecialchars($search) ?>">
                </div>
                <div class="col-md-2 col-lg-2">
                    <label class="form-label small fw-semibold text-muted mb-1">
                        <i class="bi bi-toggles me-1"></i>Statut
                    </label>
                    <select name="filtre" class="form-select form-select-sm rounded-pill">
                        <option value="tous"        <?= $filtre === 'tous'        ? 'selected' : '' ?>>Tous</option>
                        <option value="actif"       <?= $filtre === 'actif'       ? 'selected' : '' ?>>Actifs</option>
                        <option value="inactif"     <?= $filtre === 'inactif'     ? 'selected' : '' ?>>Désactivés</option>
                        <option value="hospitalise" <?= $filtre === 'hospitalise' ? 'selected' : '' ?>>Hospitalisés</option>
                    </select>
                </div>
                <div class="col-md-3 col-lg-3">
                    <label class="form-label small fw-semibold text-muted mb-1">
                        <i class="bi bi-building me-1"></i>Service
                    </label>
                    <select name="service_id" class="form-select form-select-sm rounded-pill">
                        <option value="">Tous les services</option>
                        <?php foreach ($services as $svc): ?>
                            <option value="<?= $svc['id'] ?>" <?= $service_id == $svc['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($svc['nom_service']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-auto d-flex gap-2">
                    <button class="btn btn-sm btn-primary rounded-pill px-3 fw-semibold" type="submit">
                        <i class="bi bi-funnel-fill me-1"></i>Filtrer
                    </button>
                    <?php if ($search || $filtre !== 'tous' || $service_id): ?>
                    <a href="<?= BASE_URL ?>admin/patients" class="btn btn-sm btn-outline-secondary rounded-pill px-3">
                        <i class="bi bi-x-circle me-1"></i>Réinitialiser
                    </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- ══════════════════════════════════════════════════════
             LISTE PATIENTS
        ═══════════════════════════════════════════════════════ -->
        <div class="patients-table-card mb-4">

            <?php if (empty($patients)): ?>
            <div class="text-center py-5 text-muted">
                <i class="bi bi-people display-1 d-block mb-3 opacity-25"></i>
                <p class="fs-5 mb-1 fw-semibold">Aucun patient trouvé</p>
                <small>Modifiez vos critères de recherche</small>
            </div>

            <?php else: ?>
            <?php
            $avatarColors = ['#3b82f6','#8b5cf6','#10b981','#f59e0b','#ef4444','#06b6d4','#ec4899','#6366f1'];
            $ci = 0;

            $typeMap = [
                'PAYANT_COMPTANT'    => ['label' => 'Comptant',   'bg' => '#dcfce7', 'color' => '#15803d'],
                'ASSURANCE'          => ['label' => 'Assurance',  'bg' => '#dbeafe', 'color' => '#1d4ed8'],
                'BON_PRISE_EN_CHARGE'=> ['label' => 'BPC',        'bg' => '#cffafe', 'color' => '#0e7490'],
                'FAMILLE_PHP'        => ['label' => 'Fam. PHP',   'bg' => '#ffedd5', 'color' => '#c2410c'],
                'AGENTS_PHP'         => ['label' => 'Agt. PHP',   'bg' => '#fef9c3', 'color' => '#a16207'],
            ];
            ?>
            <div class="table-responsive">
            <table class="pat-table">
                <thead>
                    <tr>
                        <th style="width:130px;">Dossier</th>
                        <th>Patient</th>
                        <th style="width:150px;">Contact</th>
                        <th style="width:180px;">Service</th>
                        <th style="width:120px;">Type</th>
                        <th style="width:130px;">Statut</th>
                        <th style="width:160px; text-align:right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($patients as $p):
                    $age      = !empty($p['date_naissance'])
                                ? date_diff(date_create($p['date_naissance']), date_create('now'))->y
                                : null;
                    $initials = strtoupper(mb_substr($p['nom'],0,1) . mb_substr($p['prenom'],0,1));
                    $avatarBg = $avatarColors[$ci % count($avatarColors)];
                    $ci++;
                    $tc = $typeMap[$p['type_client'] ?? ''] ?? ['label' => '—', 'bg' => '#f1f5f9', 'color' => '#64748b'];
                    $pJson = htmlspecialchars(json_encode($p, JSON_UNESCAPED_UNICODE) ?: '{}');
                ?>
                <tr class="<?= !$p['actif'] ? 'inactive' : '' ?>">

                    <!-- Dossier -->
                    <td>
                        <span class="dossier-badge"><?= htmlspecialchars($p['dossier_numero']) ?></span>
                    </td>

                    <!-- Patient -->
                    <td>
                        <div class="d-flex align-items-center gap-3">
                            <div class="avatar-circle" style="background:<?= $avatarBg ?>;"><?= $initials ?></div>
                            <div>
                                <div class="fw-bold" style="font-size:.9rem;color:#0f172a;line-height:1.3;">
                                    <?= htmlspecialchars($p['nom'] . ' ' . $p['prenom']) ?>
                                </div>
                                <div class="d-flex align-items-center gap-1 mt-1">
                                    <?php if ($age !== null): ?>
                                    <span class="chip" style="background:#f1f5f9;color:#475569;font-size:.72rem;"><?= $age ?> ans</span>
                                    <?php endif; ?>
                                    <?php if ($p['sexe'] === 'M'): ?>
                                    <span class="chip" style="background:#eff6ff;color:#1d4ed8;font-size:.72rem;"><i class="bi bi-gender-male"></i> M</span>
                                    <?php else: ?>
                                    <span class="chip" style="background:#fdf2f8;color:#9d174d;font-size:.72rem;"><i class="bi bi-gender-female"></i> F</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </td>

                    <!-- Contact -->
                    <td>
                        <?php if (!empty($p['telephone'])): ?>
                        <div class="fw-semibold" style="font-size:.85rem;color:#334155;">
                            <i class="bi bi-telephone me-1 text-muted"></i><?= htmlspecialchars($p['telephone']) ?>
                        </div>
                        <?php else: ?>
                        <span class="text-muted">—</span>
                        <?php endif; ?>
                        <?php if (!empty($p['email'])): ?>
                        <div class="text-truncate mt-1" style="font-size:.75rem;color:#94a3b8;max-width:140px;">
                            <?= htmlspecialchars($p['email']) ?>
                        </div>
                        <?php endif; ?>
                    </td>

                    <!-- Service -->
                    <td>
                        <span class="chip" style="background:#f0f4ff;color:#4f46e5;">
                            <i class="bi bi-building"></i>
                            <?= htmlspecialchars($p['nom_service'] ?? 'Non assigné') ?>
                        </span>
                    </td>

                    <!-- Type -->
                    <td>
                        <span class="chip" style="background:<?= $tc['bg'] ?>;color:<?= $tc['color'] ?>;">
                            <?= $tc['label'] ?>
                        </span>
                    </td>

                    <!-- Statut -->
                    <td>
                        <?php if (!$p['actif']): ?>
                            <span class="chip" style="background:#f1f5f9;color:#64748b;">
                                <i class="bi bi-slash-circle"></i> Désactivé
                            </span>
                        <?php elseif ($p['statut'] === 'HOSPITALISE'): ?>
                            <span class="chip" style="background:#fef2f2;color:#dc2626;">
                                <i class="bi bi-hospital-fill"></i> Hospitalisé
                            </span>
                        <?php else: ?>
                            <span class="chip" style="background:#f0fdf4;color:#16a34a;">
                                <i class="bi bi-check-circle-fill"></i> Actif
                            </span>
                        <?php endif; ?>
                    </td>

                    <!-- Actions -->
                    <td style="text-align:right;">
                        <div class="d-flex gap-1 justify-content-end">
                            <button class="btn-action btn btn-outline-primary"
                                    title="Modifier" data-bs-toggle="tooltip" data-bs-placement="top"
                                    onclick="ouvrirEdition(<?= $pJson ?>)">
                                <i class="bi bi-pencil-fill"></i>
                            </button>
                            <a href="<?= BASE_URL ?>patients/dossier/<?= $p['id'] ?>"
                               class="btn-action btn btn-outline-secondary"
                               title="Voir le dossier" data-bs-toggle="tooltip" data-bs-placement="top">
                                <i class="bi bi-folder2-open"></i>
                            </a>
                            <button class="btn-action btn <?= $p['actif'] ? 'btn-outline-warning' : 'btn-outline-success' ?>"
                                    title="<?= $p['actif'] ? 'Désactiver' : 'Réactiver' ?>"
                                    data-bs-toggle="tooltip" data-bs-placement="top"
                                    onclick="toggleActif(<?= $p['id'] ?>, this)">
                                <i class="bi <?= $p['actif'] ? 'bi-person-x-fill' : 'bi-person-check-fill' ?>"></i>
                            </button>
                            <button class="btn-action btn btn-outline-danger"
                                    title="Supprimer définitivement"
                                    data-bs-toggle="tooltip" data-bs-placement="top"
                                    onclick="ouvrirSuppression(<?= $p['id'] ?>, '<?= htmlspecialchars(addslashes($p['nom'].' '.$p['prenom'])) ?>')">
                                <i class="bi bi-trash3-fill"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            </div><!-- /.table-responsive -->
            <?php endif; ?>

            <!-- ── PAGINATION ────────────────────────────────── -->
            <?php if ($total_pages > 1): ?>
            <div class="d-flex justify-content-between align-items-center px-4 py-3"
                 style="border-top:1px solid #f1f5f9;background:#fafafa;">
                <small class="text-muted">
                    Affichage <?= $offset + 1 ?>–<?= min($offset + $limit, $total_patients) ?>
                    sur <?= number_format($total_patients) ?> patients
                </small>
                <nav>
                    <ul class="pagination pagination-sm mb-0 gap-1">
                        <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                            <a class="page-link rounded-pill px-2"
                               href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>">
                                <i class="bi bi-chevron-left"></i>
                            </a>
                        </li>
                        <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                        <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                            <a class="page-link rounded-pill"
                               href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"><?= $i ?></a>
                        </li>
                        <?php endfor; ?>
                        <li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>">
                            <a class="page-link rounded-pill px-2"
                               href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>">
                                <i class="bi bi-chevron-right"></i>
                            </a>
                        </li>
                    </ul>
                </nav>
            </div>
            <?php endif; ?>

        </div><!-- /.patients-table-card -->

        <!-- ════════════════════════════════════════════════════════════
             SECTION DOUBLONS
             ════════════════════════════════════════════════════════════ -->
        <div class="card border-0 shadow-sm rounded-3 mt-4">
            <div class="card-header bg-white border-0 pt-3 pb-2 d-flex align-items-center justify-content-between">
                <div>
                    <h6 class="fw-bold mb-0 text-danger">
                        <i class="bi bi-people-fill me-2"></i>Détection des doublons
                    </h6>
                    <small class="text-muted">Patients ayant le même nom + prénom enregistrés plusieurs fois</small>
                </div>
                <button class="btn btn-sm btn-outline-danger rounded-pill px-3"
                        onclick="chargerDoublons()" id="btnDoublons">
                    <i class="bi bi-search me-1"></i>Rechercher les doublons
                </button>
            </div>
            <div class="card-body p-0" id="doublonsZone" style="display:none;">
                <div id="doublonsContent" class="p-3"></div>
            </div>
        </div>

    </div><!-- /.admin-page-content -->
</div><!-- /.admin-main -->

<script>
function chargerDoublons() {
    const zone    = document.getElementById('doublonsZone');
    const content = document.getElementById('doublonsContent');
    const btn     = document.getElementById('btnDoublons');

    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Analyse en cours…';
    btn.disabled  = true;
    zone.style.display = 'block';
    content.innerHTML  = '';

    fetch('<?= BASE_URL ?>admin/patients/doublons', { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
    .then(r => r.json())
    .then(data => {
        btn.innerHTML = '<i class="bi bi-search me-1"></i>Actualiser';
        btn.disabled  = false;

        if (!data.success) { content.innerHTML = '<p class="text-danger p-3">' + (data.message||'Erreur') + '</p>'; return; }
        if (!data.groups || data.groups.length === 0) {
            content.innerHTML = `
                <div class="text-center py-5 text-muted">
                    <i class="bi bi-check-circle-fill text-success display-4 d-block mb-3"></i>
                    <p class="fw-600 mb-0">Aucun doublon détecté ✓</p>
                    <small>Tous les noms de patients sont uniques en base de données.</small>
                </div>`;
            return;
        }

        let html = `<div class="p-3">
            <div class="alert alert-warning border-0 rounded-3 mb-3">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                <strong>${data.groups.length}</strong> groupe(s) de doublons détectés.
                Gardez le dossier principal et supprimez les duplicates.
            </div>`;

        data.groups.forEach(group => {
            html += `<div class="border rounded-3 mb-3 overflow-hidden">
                <div class="px-3 py-2 fw-700" style="background:#fef2f2;font-size:.85rem;color:#991b1b;">
                    <i class="bi bi-person-exclamation me-1"></i>
                    ${escH(group.nom)} ${escH(group.prenom)}
                    <span class="badge bg-danger ms-2">${group.patients.length} dossiers</span>
                </div>
                <table class="table table-sm mb-0">
                    <thead style="background:#f8fafc;">
                        <tr>
                            <th class="ps-3 py-2" style="font-size:.72rem;text-transform:uppercase;letter-spacing:.5px;color:#64748b;">N° Dossier</th>
                            <th style="font-size:.72rem;text-transform:uppercase;letter-spacing:.5px;color:#64748b;">Date naissance</th>
                            <th style="font-size:.72rem;text-transform:uppercase;letter-spacing:.5px;color:#64748b;">Téléphone</th>
                            <th style="font-size:.72rem;text-transform:uppercase;letter-spacing:.5px;color:#64748b;">Consultations</th>
                            <th style="font-size:.72rem;text-transform:uppercase;letter-spacing:.5px;color:#64748b;">Créé le</th>
                            <th class="pe-3 text-end" style="font-size:.72rem;text-transform:uppercase;letter-spacing:.5px;color:#64748b;">Action</th>
                        </tr>
                    </thead>
                    <tbody>`;

            group.patients.forEach((p, idx) => {
                const isPrimary = idx === 0; // le plus ancien = principal
                html += `<tr class="${isPrimary ? 'table-success' : ''}">
                    <td class="ps-3 fw-700">
                        ${escH(p.dossier_numero)}
                        ${isPrimary ? '<span class="badge bg-success ms-1" style="font-size:.65rem;">Principal</span>' : ''}
                    </td>
                    <td>${p.date_naissance || '—'}</td>
                    <td>${escH(p.telephone || '—')}</td>
                    <td><span class="badge bg-primary-subtle text-primary rounded-pill">${p.nb_consultations}</span></td>
                    <td style="font-size:.8rem;">${p.created_at || '—'}</td>
                    <td class="pe-3 text-end">
                        ${isPrimary
                            ? '<span class="badge bg-success-subtle text-success rounded-pill px-2">Conserver</span>'
                            : `<button class="btn btn-sm btn-outline-danger rounded-pill px-2"
                                       onclick="supprimerDoublon(${p.id}, '${escH(p.dossier_numero)}')">
                                   <i class="bi bi-trash3 me-1"></i>Supprimer
                               </button>`
                        }
                    </td>
                </tr>`;
            });

            html += `</tbody></table></div>`;
        });

        html += '</div>';
        content.innerHTML = html;
    })
    .catch(() => {
        btn.innerHTML = '<i class="bi bi-search me-1"></i>Réessayer';
        btn.disabled  = false;
        content.innerHTML = '<p class="text-danger p-3">Erreur réseau.</p>';
    });
}

function supprimerDoublon(id, dossier) {
    if (!confirm(`⚠️ Supprimer définitivement le dossier ${dossier} ?\n\nTous les données médicales associées seront perdues.\nCette action est irréversible.`)) return;

    fetch('<?= BASE_URL ?>admin/patients/supprimer/' + id, {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => r.json())
    .then(d => {
        if (d.success) {
            // Retirer la ligne du tableau
            const btns = document.querySelectorAll(`button[onclick*="supprimerDoublon(${id},"]`);
            btns.forEach(b => {
                const tr = b.closest('tr');
                if (tr) { tr.style.opacity='0'; tr.style.transition='opacity .3s'; setTimeout(()=>tr.remove(),320); }
            });
            // Toast
            const t = document.createElement('div');
            t.style.cssText = 'position:fixed;bottom:1.5rem;right:1.5rem;z-index:9999;background:#1e293b;color:#fff;border-radius:12px;padding:.7rem 1.2rem;font-size:.82rem;font-weight:600;display:flex;align-items:center;gap:.5rem;box-shadow:0 4px 20px rgba(0,0,0,.3)';
            t.innerHTML = '<i class="bi bi-check-circle-fill text-success"></i> ' + (d.message || 'Dossier supprimé');
            document.body.appendChild(t);
            setTimeout(() => t.remove(), 3000);
        } else {
            alert('❌ ' + (d.message || 'Erreur lors de la suppression.'));
        }
    })
    .catch(() => alert('Erreur réseau.'));
}

function escH(s) {
    if (!s) return '';
    return String(s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[c]));
}
</script>

<!-- ═══════════════════════════════════════════════════════════
     MODAL ÉDITION PATIENT (données administratives uniquement)
════════════════════════════════════════════════════════════════ -->
<div class="modal fade" id="modalEditPatient" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-0 pb-0" style="background:linear-gradient(135deg,#1e40af,#3b82f6);border-radius:16px 16px 0 0;">
                <div>
                    <h5 class="modal-title text-white fw-bold mb-0">
                        <i class="bi bi-person-gear me-2"></i>Modifier le patient
                    </h5>
                    <p class="text-white opacity-75 small mb-0" id="editSubtitle">—</p>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <form id="formEditPatient" method="POST">
                <input type="hidden" name="_patient_id" id="editPatientId">
                <div class="modal-body px-4 py-3">

                    <!-- ── Identité ── -->
                    <div class="mb-3 pb-2 border-bottom">
                        <h6 class="fw-bold text-primary mb-3"><i class="bi bi-person-badge me-2"></i>Identité</h6>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label small fw-semibold">Nom <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="nom" id="editNom" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-semibold">Prénom <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="prenom" id="editPrenom" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-semibold">Date de naissance <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" name="date_naissance" id="editDateNaissance" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-semibold">Sexe <span class="text-danger">*</span></label>
                                <select class="form-select" name="sexe" id="editSexe" required>
                                    <option value="M">Masculin</option>
                                    <option value="F">Féminin</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-semibold">Profession</label>
                                <input type="text" class="form-control" name="profession" id="editProfession">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-semibold">Situation maritale</label>
                                <select class="form-select" name="situation_matrimoniale" id="editSituation">
                                    <option value="">— Non renseigné —</option>
                                    <option value="celibataire">Célibataire</option>
                                    <option value="marie">Marié(e)</option>
                                    <option value="divorce">Divorcé(e)</option>
                                    <option value="veuf">Veuf/Veuve</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- ── Contact ── -->
                    <div class="mb-3 pb-2 border-bottom">
                        <h6 class="fw-bold text-primary mb-3"><i class="bi bi-telephone me-2"></i>Coordonnées</h6>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label small fw-semibold">Téléphone</label>
                                <input type="text" class="form-control" name="telephone" id="editTelephone">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-semibold">Email</label>
                                <input type="email" class="form-control" name="email" id="editEmail">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-semibold">Ville</label>
                                <input type="text" class="form-control" name="ville" id="editVille">
                            </div>
                            <div class="col-12">
                                <label class="form-label small fw-semibold">Adresse complète</label>
                                <input type="text" class="form-control" name="adresse" id="editAdresse">
                            </div>
                        </div>
                    </div>

                    <!-- ── Contact d'urgence ── -->
                    <div class="mb-3 pb-2 border-bottom">
                        <h6 class="fw-bold text-primary mb-3"><i class="bi bi-exclamation-triangle me-2"></i>Contact d'urgence</h6>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label small fw-semibold">Nom du contact</label>
                                <input type="text" class="form-control" name="contact_nom" id="editContactNom">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-semibold">Téléphone du contact</label>
                                <input type="text" class="form-control" name="contact_telephone" id="editContactTel">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-semibold">Tél. urgence direct</label>
                                <input type="text" class="form-control" name="telephone_urgence" id="editTelUrgence">
                            </div>
                        </div>
                    </div>

                    <!-- ── Facturation ── -->
                    <div>
                        <h6 class="fw-bold text-primary mb-3"><i class="bi bi-credit-card me-2"></i>Prise en charge / Facturation</h6>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label small fw-semibold">Type de prise en charge</label>
                                <select class="form-select" name="type_client" id="editTypeClient"
                                        onchange="toggleAssurance(this.value)">
                                    <option value="">— Non renseigné —</option>
                                    <option value="PAYANT_COMPTANT">Payant comptant</option>
                                    <option value="BON_PRISE_EN_CHARGE">Bon de prise en charge</option>
                                    <option value="ASSURANCE">Assurance</option>
                                    <option value="FAMILLE_PHP">Famille PHP</option>
                                    <option value="AGENTS_PHP">Agents PHP</option>
                                </select>
                            </div>
                            <div class="col-md-4" id="blockAssurance" style="display:none;">
                                <label class="form-label small fw-semibold">Nom de l'assurance</label>
                                <input type="text" class="form-control" name="nom_assurance" id="editNomAssurance">
                            </div>
                            <div class="col-md-4" id="blockNumAssure" style="display:none;">
                                <label class="form-label small fw-semibold">N° Assuré</label>
                                <input type="text" class="form-control" name="numero_assure" id="editNumeroAssure">
                            </div>
                        </div>
                    </div>

                </div><!-- /.modal-body -->

                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-4"
                            data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-5 fw-semibold">
                        <i class="bi bi-save me-2"></i>Enregistrer
                    </button>
                </div>
            </form>

        </div>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════════
     MODAL CONFIRMATION SUPPRESSION PATIENT
════════════════════════════════════════════════════════════════ -->
<div class="modal fade" id="modalSupprimer" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-0" style="background:linear-gradient(135deg,#dc2626,#ef4444);border-radius:16px 16px 0 0;">
                <h5 class="modal-title text-white fw-bold">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>Suppression définitive
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body px-4 py-4">
                <div class="alert alert-danger border-0 rounded-3 mb-3">
                    <strong><i class="bi bi-exclamation-triangle-fill me-2"></i>Action irréversible</strong><br>
                    <small>Toutes les données médicales du patient seront supprimées définitivement : consultations, hospitalisations, ordonnances, bilans, documents.</small>
                </div>
                <p class="mb-2">Vous êtes sur le point de supprimer :</p>
                <div class="fw-bold fs-5 text-danger mb-3" id="suppNomPatient">—</div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">
                        Tapez <strong>SUPPRIMER</strong> pour confirmer :
                    </label>
                    <input type="text" id="confirmSupprText" class="form-control"
                           placeholder="SUPPRIMER" autocomplete="off">
                </div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-outline-secondary rounded-pill px-4"
                        data-bs-dismiss="modal">Annuler</button>
                <button type="button" id="btnConfirmerSuppression"
                        class="btn btn-danger rounded-pill px-5 fw-semibold" disabled
                        onclick="confirmerSuppression()">
                    <i class="bi bi-trash3-fill me-2"></i>Supprimer définitivement
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Toast notifications -->
<div class="toast-container position-fixed bottom-0 end-0 p-3">
    <div id="toastMsg" class="toast align-items-center text-white border-0" role="alert">
        <div class="d-flex">
            <div class="toast-body fw-semibold" id="toastBody">—</div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    </div>
</div>

<script>
/* BASE_URL est déjà déclaré dans header.php — ne pas le redéclarer */
let modal, modalSuppr, toastEl, toast;
let _supprimerPatientId = null;

document.addEventListener('DOMContentLoaded', function() {
    // ── Bootstrap components ──────────────────────────────────────────
    modal      = new bootstrap.Modal(document.getElementById('modalEditPatient'), {backdrop: 'static'});
    modalSuppr = new bootstrap.Modal(document.getElementById('modalSupprimer'));
    toastEl    = document.getElementById('toastMsg');
    toast      = new bootstrap.Toast(toastEl, { delay: 3500 });

    // Initialise Bootstrap tooltips
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function(el) {
        new bootstrap.Tooltip(el);
    });

    // ── Soumission AJAX du formulaire d'édition ───────────────────────
    document.getElementById('formEditPatient').addEventListener('submit', function(e) {
        e.preventDefault();
        const form = this;
        const btn  = form.querySelector('[type="submit"]');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Enregistrement…';

        fetch(form.action, {
            method: 'POST',
            body: new FormData(form),
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => r.json())
        .then(data => {
            modal.hide();
            toastEl.classList.remove('bg-danger', 'bg-success');
            toastEl.classList.add(data.success ? 'bg-success' : 'bg-danger');
            document.getElementById('toastBody').textContent = data.message;
            toast.show();
            if (data.success) setTimeout(() => location.reload(), 1200);
        })
        .catch(() => {
            toastEl.classList.remove('bg-success');
            toastEl.classList.add('bg-danger');
            document.getElementById('toastBody').textContent = 'Erreur de communication.';
            toast.show();
        })
        .finally(() => {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-save me-2"></i>Enregistrer';
        });
    });

    // ── Activer le bouton quand "SUPPRIMER" est tapé ──────────────────
    document.getElementById('confirmSupprText').addEventListener('input', function() {
        document.getElementById('btnConfirmerSuppression').disabled = (this.value.trim() !== 'SUPPRIMER');
    });
});

// Ouvrir le modal avec les données du patient
function ouvrirEdition(p) {
    if (!p || !p.id) return;
    document.getElementById('editSubtitle').textContent  = (p.dossier_numero || '') + ' — ' + (p.nom || '') + ' ' + (p.prenom || '');
    document.getElementById('editPatientId').value       = p.id;
    document.getElementById('editNom').value             = p.nom                    || '';
    document.getElementById('editPrenom').value          = p.prenom                 || '';
    document.getElementById('editDateNaissance').value   = p.date_naissance         || '';
    document.getElementById('editSexe').value            = p.sexe                   || 'M';
    document.getElementById('editProfession').value      = p.profession             || '';
    document.getElementById('editSituation').value       = p.situation_matrimoniale || '';
    document.getElementById('editTelephone').value       = p.telephone              || '';
    document.getElementById('editEmail').value           = p.email                  || '';
    document.getElementById('editVille').value           = p.ville                  || '';
    document.getElementById('editAdresse').value         = p.adresse                || '';
    document.getElementById('editContactNom').value      = p.contact_nom            || '';
    document.getElementById('editContactTel').value      = p.contact_telephone      || '';
    document.getElementById('editTelUrgence').value      = p.telephone_urgence      || '';
    document.getElementById('editTypeClient').value      = p.type_client            || '';
    document.getElementById('editNomAssurance').value    = p.nom_assurance          || '';
    document.getElementById('editNumeroAssure').value    = p.numero_assure          || '';
    toggleAssurance(p.type_client || '');

    document.getElementById('formEditPatient').action = BASE_URL + 'admin/patients/update/' + p.id;
    modal.show();
}

// Afficher/masquer champs assurance
function toggleAssurance(val) {
    const show = (val === 'ASSURANCE' || val === 'BON_PRISE_EN_CHARGE');
    document.getElementById('blockAssurance').style.display = show ? '' : 'none';
    document.getElementById('blockNumAssure').style.display = show ? '' : 'none';
}

// Ouvrir le modal de suppression patient
function ouvrirSuppression(id, nom) {
    _supprimerPatientId = id;
    document.getElementById('suppNomPatient').textContent = nom;
    document.getElementById('confirmSupprText').value = '';
    document.getElementById('btnConfirmerSuppression').disabled = true;
    modalSuppr.show();
}

// Confirmer la suppression
function confirmerSuppression() {
    if (!_supprimerPatientId) return;
    const btn = document.getElementById('btnConfirmerSuppression');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Suppression…';

    fetch(BASE_URL + 'admin/patients/delete/' + _supprimerPatientId, {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => r.json())
    .then(data => {
        modalSuppr.hide();
        toastEl.classList.remove('bg-success', 'bg-danger');
        toastEl.classList.add(data.success ? 'bg-success' : 'bg-danger');
        document.getElementById('toastBody').textContent = data.message;
        toast.show();
        if (data.success) setTimeout(() => location.reload(), 1500);
    })
    .catch(() => {
        toastEl.classList.add('bg-danger');
        document.getElementById('toastBody').textContent = 'Erreur de communication.';
        toast.show();
    })
    .finally(() => {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-trash3-fill me-2"></i>Supprimer définitivement';
    });
}

// Toggle actif/inactif
function toggleActif(id, btn) {
    if (!confirm('Confirmer cette action ?')) return;
    fetch(BASE_URL + 'admin/patients/toggle-actif/' + id, {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => r.json())
    .then(data => {
        toastEl.classList.remove('bg-danger','bg-success');
        toastEl.classList.add(data.success ? 'bg-success' : 'bg-danger');
        document.getElementById('toastBody').textContent = data.message;
        toast.show();
        if (data.success) setTimeout(() => location.reload(), 1000);
    });
}
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
