<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<style>
    body { background: #f4f7f9; }
    .page-header { background: linear-gradient(135deg, #1a4a8e, #0099ff); color: white; padding: 30px 40px; }
    .search-box { background: white; border-radius: 16px; padding: 25px; box-shadow: 0 4px 20px rgba(0,0,0,0.05); margin-bottom: 25px; }
    .patient-card {
        background: white; border-radius: 14px; padding: 18px 22px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.04); border: 1px solid #e8eef5;
        display: flex; align-items: center; gap: 18px; transition: 0.2s;
        margin-bottom: 12px;
    }
    .patient-card:hover { box-shadow: 0 6px 20px rgba(0,0,0,0.09); transform: translateY(-1px); }
    .avatar-sm {
        width: 46px; height: 46px; border-radius: 50%;
        background: linear-gradient(135deg, #1a4a8e, #0099ff);
        color: white; display: flex; align-items: center; justify-content: center;
        font-weight: 800; font-size: 1rem; flex-shrink: 0;
    }
    .patient-info { flex: 1; min-width: 0; }
    .status-pill {
        font-size: 0.7rem; font-weight: 700; padding: 4px 12px;
        border-radius: 50px; white-space: nowrap;
    }
    .pill-hosp     { background: #e0f2fe; color: #0369a1; }
    .pill-sorti    { background: #f1f5f9; color: #64748b; }
    .pill-externe  { background: #dcfce7; color: #166534; }
    .spinner-overlay { display: none; text-align: center; padding: 40px; }
    .pagination-area { display: flex; justify-content: space-between; align-items: center; margin-top: 20px; }
</style>

<!-- HEADER -->
<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <a href="<?= BASE_URL ?>dashboard" class="btn btn-sm btn-light rounded-pill me-3 opacity-75">
                <i class="bi bi-arrow-left"></i> Tableau de bord
            </a>
            <h3 class="fw-bold d-inline mb-0">Mes Patients — <?= htmlspecialchars($_SESSION['nom_service'] ?? 'Service') ?></h3>
        </div>
        <span class="badge bg-white text-primary fs-6 px-4 py-2 rounded-pill shadow-sm fw-bold">
            <?= $total ?> patient(s)
        </span>
    </div>
</div>

<div class="container-fluid p-4">

    <!-- BARRE DE RECHERCHE -->
    <div class="search-box">
        <div class="row g-2 align-items-center">
            <div class="col-md-8">
                <div class="input-group input-group-lg">
                    <span class="input-group-text bg-light border-end-0 border-2">
                        <i class="bi bi-search text-muted"></i>
                    </span>
                    <input type="text" id="searchInput" class="form-control border-start-0 border-2 ps-0"
                           placeholder="Nom, prénom ou numéro de dossier..."
                           value="<?= htmlspecialchars($search) ?>" autocomplete="off">
                    <button class="btn btn-outline-secondary" type="button" id="clearSearch" title="Effacer">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>
            </div>
            <div class="col-md-4">
                <select id="filterStatut" class="form-select form-select-lg border-2">
                    <option value="">Tous les statuts</option>
                    <option value="HOSPITALISE">Hospitalisé</option>
                    <option value="SORTIE">Sorti</option>
                    <option value="EXTERNE">Externe</option>
                </select>
            </div>
        </div>
    </div>

    <!-- RÉSULTATS -->
    <div id="patientsList">
        <?php if (!empty($patients_liste)): ?>
            <?php foreach ($patients_liste as $p):
                $isHosp   = ($p['statut_hosp'] === 'en_cours');
                $isSorti  = ($p['statut'] === 'SORTIE' || $p['statut_hosp'] === 'termine');
                $age      = !empty($p['date_naissance']) ? date_diff(date_create($p['date_naissance']), date_create('today'))->y : '?';
                $initials = strtoupper(substr($p['nom'], 0, 1) . substr($p['prenom'], 0, 1));
                if ($isHosp)       { $pillCls = 'pill-hosp';    $pillTxt = 'Hospitalisé'; }
                elseif ($isSorti)  { $pillCls = 'pill-sorti';   $pillTxt = 'Sorti'; }
                else               { $pillCls = 'pill-externe';  $pillTxt = 'Externe'; }
            ?>
                <div class="patient-card" data-statut="<?= htmlspecialchars($p['statut'] ?? '') ?>">
                    <div class="avatar-sm"><?= $initials ?></div>
                    <div class="patient-info">
                        <div class="fw-bold text-dark"><?= strtoupper($p['nom']) ?> <?= htmlspecialchars($p['prenom']) ?></div>
                        <small class="text-muted">
                            <?= htmlspecialchars($p['dossier_numero']) ?> &bull; <?= $age ?> ans
                            <?php if ($p['date_sortie_effective']): ?>
                                &bull; Sorti le <?= date('d/m/Y', strtotime($p['date_sortie_effective'])) ?>
                            <?php elseif ($isHosp && $p['date_admission']): ?>
                                &bull; Admis le <?= date('d/m/Y', strtotime($p['date_admission'])) ?>
                            <?php endif; ?>
                        </small>
                    </div>
                    <span class="status-pill <?= $pillCls ?>"><?= $pillTxt ?></span>
                    <div class="d-flex gap-2">
                        <a href="<?= BASE_URL ?>patients/dossier/<?= $p['id'] ?>"
                           class="btn btn-sm btn-outline-primary rounded-pill px-3">
                            <i class="bi bi-folder2-open me-1"></i>Dossier
                        </a>
                        <?php if ($p['hosp_id'] && $isSorti): ?>
                            <a href="<?= BASE_URL ?>formulaire/crh/<?= $p['hosp_id'] ?>"
                               class="btn btn-sm btn-outline-danger rounded-pill px-3">
                                <i class="bi bi-pencil-square me-1"></i>CRH
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="text-center py-5 text-muted">
                <i class="bi bi-person-x fs-1 d-block mb-2 opacity-25"></i>
                Aucun patient trouvé.
            </div>
        <?php endif; ?>
    </div>

    <div class="spinner-overlay" id="loadingSpinner">
        <div class="spinner-border text-primary"></div>
        <p class="small text-muted mt-2">Recherche en cours...</p>
    </div>

    <!-- PAGINATION -->
    <?php if ($total_pages > 1): ?>
        <div class="pagination-area">
            <small class="text-muted">Page <?= $page ?> / <?= $total_pages ?> — <?= $total ?> patients</small>
            <div class="d-flex gap-2">
                <?php if ($page > 1): ?>
                    <a href="?page=<?= $page - 1 ?>&q=<?= urlencode($search) ?>"
                       class="btn btn-outline-primary rounded-pill px-4">
                        <i class="bi bi-chevron-left"></i> Préc.
                    </a>
                <?php endif; ?>
                <?php if ($page < $total_pages): ?>
                    <a href="?page=<?= $page + 1 ?>&q=<?= urlencode($search) ?>"
                       class="btn btn-primary rounded-pill px-4">
                        Suiv. <i class="bi bi-chevron-right"></i>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

</div>

<script>
(function () {
    const input      = document.getElementById('searchInput');
    const filterStat = document.getElementById('filterStatut');
    const list       = document.getElementById('patientsList');
    const spinner    = document.getElementById('loadingSpinner');
    const clearBtn   = document.getElementById('clearSearch');
    let debounceTimer;

    // Recherche AJAX
    function doSearch() {
        const q = input.value.trim();
        const s = filterStat.value;

        // Mise à jour URL sans rechargement
        const url = new URL(window.location);
        url.searchParams.set('q', q);
        if (s) url.searchParams.set('statut', s); else url.searchParams.delete('statut');
        window.history.replaceState({}, '', url);

        list.style.opacity = '0.4';
        spinner.style.display = 'block';

        fetch(`<?= BASE_URL ?>patients/mes-patients?ajax=1&q=${encodeURIComponent(q)}`)
            .then(r => r.json())
            .then(data => {
                spinner.style.display = 'none';
                list.style.opacity = '1';
                renderPatients(data.patients, s);
            })
            .catch(() => {
                spinner.style.display = 'none';
                list.style.opacity = '1';
            });
    }

    function renderPatients(patients, statutFilter) {
        if (!patients || patients.length === 0) {
            list.innerHTML = `<div class="text-center py-5 text-muted">
                <i class="bi bi-person-x fs-1 d-block mb-2 opacity-25"></i>Aucun patient trouvé.</div>`;
            return;
        }

        let html = '';
        patients.forEach(p => {
            const isHosp  = p.statut_hosp === 'en_cours';
            const isSorti = (p.statut === 'SORTIE' || p.statut_hosp === 'termine');
            let pillCls, pillTxt;
            if (isHosp)       { pillCls = 'pill-hosp';    pillTxt = 'Hospitalisé'; }
            else if (isSorti) { pillCls = 'pill-sorti';   pillTxt = 'Sorti'; }
            else              { pillCls = 'pill-externe';  pillTxt = 'Externe'; }

            if (statutFilter && p.statut !== statutFilter &&
                !(statutFilter === 'HOSPITALISE' && isHosp)) return;

            const initials = (p.nom.charAt(0) + p.prenom.charAt(0)).toUpperCase();
            const crhBtn = (p.hosp_id && isSorti)
                ? `<a href="<?= BASE_URL ?>formulaire/crh/${p.hosp_id}" class="btn btn-sm btn-outline-danger rounded-pill px-3">
                       <i class="bi bi-pencil-square me-1"></i>CRH</a>`
                : '';

            html += `
            <div class="patient-card" data-statut="${p.statut ?? ''}">
                <div class="avatar-sm">${initials}</div>
                <div class="patient-info">
                    <div class="fw-bold text-dark">${p.nom.toUpperCase()} ${p.prenom}</div>
                    <small class="text-muted">${p.dossier_numero}</small>
                </div>
                <span class="status-pill ${pillCls}">${pillTxt}</span>
                <div class="d-flex gap-2">
                    <a href="<?= BASE_URL ?>patients/dossier/${p.id}" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                        <i class="bi bi-folder2-open me-1"></i>Dossier</a>
                    ${crhBtn}
                </div>
            </div>`;
        });
        list.innerHTML = html || `<div class="text-center py-5 text-muted">Aucun résultat.</div>`;
    }

    // Filtrage local par statut (sans refaire un appel réseau)
    filterStat.addEventListener('change', () => {
        const s = filterStat.value;
        document.querySelectorAll('.patient-card').forEach(card => {
            if (!s) { card.style.display = ''; return; }
            const st = card.dataset.statut;
            card.style.display = (st === s || (s === 'HOSPITALISE' && st === 'HOSPITALISE')) ? '' : 'none';
        });
    });

    // Déclenchement avec délai sur frappe
    input.addEventListener('input', () => {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(doSearch, 350);
    });

    clearBtn.addEventListener('click', () => {
        input.value = '';
        doSearch();
    });
})();
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
