<?php
require_once __DIR__ . '/../layouts/header.php';

// Sécurisation des variables
$demandes       = $demandes       ?? [];
$statistiques   = $statistiques   ?? [];

$nb_demandes    = array_sum(array_column($statistiques['demandes_jour'] ?? [], 'nb'));
$nb_urgents     = $statistiques['urgents']     ?? 0;
$delai_moyen    = $statistiques['delai_moyen'] ?? 0;
$taux_qualite   = $statistiques['taux_qualite'] ?? 98.5;
?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">

<style>
    /* ── Mode Cockpit : masquer sidebar et étirer le main ── */
    .sidebar        { display: none !important; }
    main, .main-content { margin-left: 0 !important; width: 100% !important; }
    body            { background: #f0f4f8; font-family: 'Plus Jakarta Sans', sans-serif; color: #1e293b; }

    /* ── Header institutionnel ── */
    .cockpit-header {
        background: linear-gradient(135deg, #0f4c75 0%, #1b6ca8 100%);
        color: white;
        padding: 12px 30px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        position: sticky; top: 0; z-index: 100;
    }

    /* ── Horloge néon ── */
    #lab-clock {
        font-family: 'Courier New', monospace;
        font-size: 2.2rem;
        font-weight: bold;
        color: #00e5ff;
        text-shadow: 0 0 15px rgba(0, 229, 255, 0.6);
        letter-spacing: 3px;
    }

    /* ── Boutons header ── */
    .btn-lab-header { font-weight: 700; border-radius: 50px; padding: 8px 20px; font-size: 0.82rem; display: inline-flex; align-items: center; gap: 7px; transition: all .25s; border: none; }
    .btn-lab-planning   { background: #1565c0; color: #fff; border: 1px solid rgba(255,255,255,.2); }
    .btn-lab-planning:hover  { background: #0d47a1; transform: translateY(-2px); color:#fff; }
    .btn-lab-qualite    { background: #f59e0b; color: #1e293b; }
    .btn-lab-qualite:hover   { background: #d97706; transform: translateY(-2px); color:#1e293b; }
    .btn-lab-profile    { background: #fff; color: #0f4c75; opacity: .9; }
    .btn-lab-profile:hover   { opacity: 1; transform: translateY(-2px); }

    /* ── KPI Cards ── */
    .kpi-wrap   { display: flex; gap: 16px; padding: 20px 28px 8px; flex-wrap: wrap; }
    .kpi-card   { flex: 1; min-width: 180px; background: #fff; border-radius: 18px; padding: 20px 22px;
                  box-shadow: 0 4px 12px rgba(0,0,0,.06); display: flex; align-items: center; gap: 16px;
                  border-bottom: 5px solid #e2e8f0; transition: transform .2s; }
    .kpi-card:hover { transform: translateY(-3px); }
    .kpi-card.kpi-demandes { border-color: #6366f1; }
    .kpi-card.kpi-urgent   { border-color: #ef4444; }
    .kpi-card.kpi-delai    { border-color: #10b981; }
    .kpi-card.kpi-qualite  { border-color: #00bcd4; }
    .kpi-icon   { width: 52px; height: 52px; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; flex-shrink: 0; }
    .kpi-demandes .kpi-icon { background: #eef2ff; color: #6366f1; }
    .kpi-urgent   .kpi-icon { background: #fef2f2; color: #ef4444; }
    .kpi-delai    .kpi-icon { background: #ecfdf5; color: #10b981; }
    .kpi-qualite  .kpi-icon { background: #e0f7fa; color: #00bcd4; }
    .kpi-label  { font-size: .78rem; color: #64748b; font-weight: 600; text-transform: uppercase; letter-spacing: .5px; }
    .kpi-value  { font-size: 1.9rem; font-weight: 800; line-height: 1.1; color: #0f172a; }

    /* ── Zone principale ── */
    .lab-body   { padding: 12px 28px 30px; }

    /* ── Filtres ── */
    .filter-bar { background: #fff; border-radius: 16px; padding: 16px 20px; margin-bottom: 16px;
                  box-shadow: 0 2px 8px rgba(0,0,0,.05); display: flex; gap: 12px; flex-wrap: wrap; align-items: center; }
    .filter-bar .form-select,
    .filter-bar .form-control { border-radius: 10px; border: 1.5px solid #e2e8f0; font-size: .875rem; padding: 8px 14px; }
    .filter-bar .form-select:focus,
    .filter-bar .form-control:focus { border-color: #1b6ca8; box-shadow: 0 0 0 3px rgba(27,108,168,.12); }
    .btn-assigner { background: #0f4c75; color: #fff; border-radius: 10px; padding: 9px 20px; font-weight: 700; font-size: .875rem; border: none; display: flex; align-items: center; gap: 6px; transition: .2s; margin-left: auto; }
    .btn-assigner:hover { background: #0a3554; }

    /* ── Table ── */
    .table-card { background: #fff; border-radius: 18px; box-shadow: 0 4px 16px rgba(0,0,0,.07); overflow: hidden; }
    .table-card-header { padding: 16px 22px; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center; }
    .table-card-header h5 { font-weight: 800; font-size: 1rem; color: #0f172a; margin: 0; }
    .lab-table th { background: #f8fafc; color: #1b6ca8; padding: 13px 16px; font-size: .72rem; text-transform: uppercase; letter-spacing: .8px; font-weight: 700; border-bottom: 2px solid #e2e8f0; }
    .lab-table td { padding: 14px 16px; vertical-align: middle; border-bottom: 1px solid #f8fafc; }
    .lab-table tbody tr:hover { background: #f8fafc; }
    .lab-table tbody tr.row-urgent { border-left: 4px solid #ef4444; }
    .lab-table tbody tr.row-normal { border-left: 4px solid #10b981; }

    /* ── Badges statut ── */
    .badge-statut { padding: 5px 11px; border-radius: 20px; font-size: .72rem; font-weight: 700; }
    .badge-en-attente     { background: #fef9c3; color: #854d0e; }
    .badge-prelevements   { background: #e0f2fe; color: #075985; }
    .badge-en-analyse     { background: #dbeafe; color: #1e40af; }
    .badge-resultats-prets{ background: #dcfce7; color: #166534; }

    /* ── Boutons action ── */
    .btn-traiter { background: #0f4c75; color: #fff; border-radius: 8px; padding: 6px 14px; font-size: .8rem; font-weight: 700; border: none; transition: .2s; }
    .btn-traiter:hover { background: #0a3554; color: #fff; }
    .btn-saisir  { border: 1.5px solid #10b981; color: #10b981; border-radius: 8px; padding: 5px 10px; background: transparent; font-size: .8rem; transition: .2s; }
    .btn-saisir:hover  { background: #10b981; color: #fff; }
    .btn-valider { border: 1.5px solid #f59e0b; color: #f59e0b; border-radius: 8px; padding: 5px 10px; background: transparent; font-size: .8rem; transition: .2s; }
    .btn-valider:hover { background: #f59e0b; color: #fff; }
    .btn-imprimer{ border: 1.5px solid #94a3b8; color: #64748b; border-radius: 8px; padding: 5px 10px; background: transparent; font-size: .8rem; transition: .2s; }
    .btn-imprimer:hover { background: #94a3b8; color: #fff; }

    /* ── Délai critique (clignotant) ── */
    .delai-critique { color: #ef4444; font-weight: 800; animation: blink-delay 1.8s infinite; }
    @keyframes blink-delay { 50% { opacity: .4; } }

    /* ── Empty state ── */
    .empty-state { padding: 60px 20px; text-align: center; color: #94a3b8; }
    .empty-state i { font-size: 3.5rem; opacity: .3; display: block; margin-bottom: 12px; }
</style>

<main>

    <!-- ══ HEADER COCKPIT ══ -->
    <div class="cockpit-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div class="d-flex align-items-center gap-3">
            <div class="bg-white p-2 rounded-circle shadow-sm">
                <img src="<?= BASE_URL ?>public/images/logo_ordre_malte.png" style="height:35px;" alt="Logo">
            </div>
            <div>
                <h4 class="mb-0 fw-bold">LABORATOIRE <span style="color:#00e5ff;">CENTRAL</span></h4>
                <small class="opacity-75 fw-semibold">Unité d'Analyses Médicales • HSJM</small>
            </div>
        </div>

        <!-- Horloge -->
        <div id="lab-clock">00:00:00</div>

        <!-- Actions -->
        <div class="d-flex gap-2 align-items-center flex-wrap">
            <a href="<?= BASE_URL ?>laboratoire/planning" class="btn btn-lab-header btn-lab-planning">
                <i class="bi bi-calendar3"></i> PLANNING
            </a>
            <a href="<?= BASE_URL ?>laboratoire/controle-qualite" class="btn btn-lab-header btn-lab-qualite">
                <i class="bi bi-shield-check"></i> CONTRÔLE QUALITÉ
            </a>
            <a href="<?= BASE_URL ?>profil" class="btn btn-lab-header btn-lab-profile shadow-sm">
                <i class="bi bi-person-circle"></i> PROFIL
            </a>
            <a href="<?= BASE_URL ?>logout" class="btn btn-danger rounded-circle p-2 shadow-sm">
                <i class="bi bi-power"></i>
            </a>
        </div>
    </div>

    <!-- ══ KPI ══ -->
    <div class="kpi-wrap">
        <div class="kpi-card kpi-demandes">
            <div class="kpi-icon"><i class="bi bi-clipboard-data"></i></div>
            <div>
                <div class="kpi-label">Demandes Aujourd'hui</div>
                <div class="kpi-value"><?= $nb_demandes ?></div>
            </div>
        </div>
        <div class="kpi-card kpi-urgent">
            <div class="kpi-icon"><i class="bi bi-exclamation-triangle"></i></div>
            <div>
                <div class="kpi-label">Examens Urgents</div>
                <div class="kpi-value" style="color:#ef4444;"><?= $nb_urgents ?></div>
            </div>
        </div>
        <div class="kpi-card kpi-delai">
            <div class="kpi-icon"><i class="bi bi-clock-history"></i></div>
            <div>
                <div class="kpi-label">Délai Moyen</div>
                <div class="kpi-value"><?= $delai_moyen ?>h</div>
            </div>
        </div>
        <div class="kpi-card kpi-qualite">
            <div class="kpi-icon"><i class="bi bi-award"></i></div>
            <div>
                <div class="kpi-label">Taux Qualité</div>
                <div class="kpi-value" style="color:#00bcd4;"><?= $taux_qualite ?>%</div>
            </div>
        </div>
    </div>

    <!-- ══ CORPS ══ -->
    <div class="lab-body">

        <!-- Filtres -->
        <div class="filter-bar">
            <select class="form-select" style="max-width:200px;" id="filtreStatut" onchange="filtrerDemandes()">
                <option value="">Tous les statuts</option>
                <option value="EN_ATTENTE">En attente</option>
                <option value="PRELEVEMENTS_EFFECTUES">Prélèvements effectués</option>
                <option value="EN_ANALYSE">En analyse</option>
                <option value="RESULTATS_PRETS">Résultats prêts</option>
            </select>
            <select class="form-select" style="max-width:190px;" id="filtrePriorite" onchange="filtrerDemandes()">
                <option value="">Toutes priorités</option>
                <option value="urgent">Urgent uniquement</option>
                <option value="normal">Normal uniquement</option>
            </select>
            <input type="text" class="form-control" style="max-width:220px;"
                   id="recherchePatient" placeholder="🔍 Rechercher patient..."
                   onkeyup="filtrerDemandes()">
            <button class="btn-assigner" onclick="assignerTechnicienMasse()">
                <i class="bi bi-person-plus"></i> Assigner Technicien
            </button>
        </div>

        <!-- Table des demandes -->
        <div class="table-card">
            <div class="table-card-header">
                <h5><i class="bi bi-flask text-primary me-2"></i>Demandes en cours
                    <span class="badge bg-primary ms-2" style="font-size:.75rem;"><?= count($demandes) ?></span>
                </h5>
                <button class="btn btn-sm btn-outline-secondary" onclick="location.reload()">
                    <i class="bi bi-arrow-clockwise"></i> Actualiser
                </button>
            </div>
            <div class="table-responsive">
                <table class="table lab-table align-middle mb-0" id="tableDemandes">
                    <thead>
                        <tr>
                            <th><input type="checkbox" id="selectAll" onchange="toggleSelectAll()"></th>
                            <th>Priorité</th>
                            <th>Patient</th>
                            <th>Examens</th>
                            <th>Médecin</th>
                            <th>Statut</th>
                            <th>Délai</th>
                            <th>Technicien</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($demandes) > 0): ?>
                            <?php foreach ($demandes as $demande): ?>
                            <?php
                                $isUrgent = $demande['nb_urgents'] > 0;
                                $heures   = round((time() - strtotime($demande['date_creation'])) / 3600, 1);
                                $delaiMax = $demande['delai_min'] ?? 24;
                                $delaiKo  = $heures > $delaiMax;
                                $statutMap = [
                                    'EN_ATTENTE'              => ['label' => 'En Attente',              'class' => 'badge-en-attente'],
                                    'PRELEVEMENTS_EFFECTUES'  => ['label' => 'Prélèvements effectués',  'class' => 'badge-prelevements'],
                                    'EN_ANALYSE'              => ['label' => 'En Analyse',               'class' => 'badge-en-analyse'],
                                    'RESULTATS_PRETS'         => ['label' => 'Résultats prêts',          'class' => 'badge-resultats-prets'],
                                ];
                                $statut = $statutMap[$demande['statut']] ?? ['label' => $demande['statut'], 'class' => 'bg-secondary text-white'];
                            ?>
                            <tr class="demande-row <?= $isUrgent ? 'row-urgent' : 'row-normal' ?>"
                                data-statut="<?= $demande['statut'] ?>"
                                data-priorite="<?= $isUrgent ? 'urgent' : 'normal' ?>"
                                data-patient="<?= strtolower($demande['nom'] . ' ' . $demande['prenom']) ?>">

                                <td><input type="checkbox" class="demande-checkbox" value="<?= $demande['id'] ?>"></td>

                                <td>
                                    <?php if ($isUrgent): ?>
                                        <span class="badge bg-danger"><i class="bi bi-exclamation-triangle me-1"></i>URGENT</span>
                                    <?php else: ?>
                                        <span class="badge bg-success">Normal</span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <div class="fw-bold"><?= htmlspecialchars($demande['nom'] . ' ' . $demande['prenom']) ?></div>
                                    <small class="text-muted"><?= htmlspecialchars($demande['dossier_numero']) ?></small>
                                </td>

                                <td>
                                    <span class="badge bg-info text-dark"><?= $demande['nb_examens'] ?> examen(s)</span>
                                    <?php if ($isUrgent): ?>
                                        <br><small class="text-danger fw-bold"><?= $demande['nb_urgents'] ?> urgent(s)</small>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <span class="fw-semibold">Dr. <?= htmlspecialchars($demande['medecin_nom'] . ' ' . $demande['medecin_prenom']) ?></span>
                                </td>

                                <td>
                                    <span class="badge-statut <?= $statut['class'] ?>"><?= $statut['label'] ?></span>
                                </td>

                                <td>
                                    <span class="<?= $delaiKo ? 'delai-critique' : 'text-success fw-bold' ?>">
                                        <?= $heures ?>h
                                    </span>
                                </td>

                                <td>
                                    <?php if (!empty($demande['technicien_nom'])): ?>
                                        <span class="badge bg-light text-dark border"><?= htmlspecialchars($demande['technicien_nom']) ?></span>
                                    <?php else: ?>
                                        <button class="btn btn-sm btn-outline-primary rounded-pill px-3"
                                                onclick="assignerTechnicien(<?= $demande['id'] ?>)">
                                            <i class="bi bi-person-plus"></i>
                                        </button>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <div class="d-flex gap-1 flex-wrap">
                                        <a href="<?= BASE_URL ?>laboratoire/traitement/<?= $demande['id'] ?>"
                                           class="btn-traiter">
                                            <i class="bi bi-eye me-1"></i>Traiter
                                        </a>
                                        <?php if (in_array($demande['statut'], ['RESULTATS_PRETS', 'PRELEVEMENTS_EFFECTUES', 'EN_ANALYSE'])): ?>
                                        <a href="<?= BASE_URL ?>laboratoire/saisie-resultats/<?= $demande['id'] ?>"
                                           class="btn-saisir" title="Saisir résultats">
                                            <i class="bi bi-pencil-square"></i>
                                        </a>
                                        <?php endif; ?>
                                        <?php if ($demande['statut'] === 'RESULTATS_PRETS'): ?>
                                        <button class="btn-valider" onclick="validerResultats(<?= $demande['id'] ?>)" title="Valider">
                                            <i class="bi bi-check-circle"></i>
                                        </button>
                                        <?php endif; ?>
                                        <a href="<?= BASE_URL ?>laboratoire/imprimer/<?= $demande['id'] ?>"
                                           target="_blank" class="btn-imprimer" title="Imprimer">
                                            <i class="bi bi-printer"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="9">
                                    <div class="empty-state">
                                        <i class="bi bi-clipboard-check"></i>
                                        <p class="fw-semibold mb-1">Aucune demande en cours</p>
                                        <small>Les nouvelles demandes apparaîtront ici automatiquement.</small>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div><!-- /.lab-body -->

</main>

<script>
// ── Horloge ──
(function tickClock() {
    const el = document.getElementById('lab-clock');
    if (!el) return;
    const now = new Date();
    const pad = n => String(n).padStart(2, '0');
    el.textContent = pad(now.getHours()) + ':' + pad(now.getMinutes()) + ':' + pad(now.getSeconds());
    setTimeout(tickClock, 1000);
})();

// ── Filtres ──
function filtrerDemandes() {
    const statut    = document.getElementById('filtreStatut').value;
    const priorite  = document.getElementById('filtrePriorite').value;
    const recherche = document.getElementById('recherchePatient').value.toLowerCase();

    document.querySelectorAll('.demande-row').forEach(row => {
        let ok = true;
        if (statut    && row.dataset.statut   !== statut)    ok = false;
        if (priorite  && row.dataset.priorite !== priorite)  ok = false;
        if (recherche && !row.dataset.patient.includes(recherche)) ok = false;
        row.style.display = ok ? '' : 'none';
    });
}

// ── Sélection tout ──
function toggleSelectAll() {
    const checked = document.getElementById('selectAll').checked;
    document.querySelectorAll('.demande-checkbox').forEach(cb => cb.checked = checked);
}

// ── Assigner technicien individuel ──
function assignerTechnicien(demandeId) {
    const technicien = prompt('Nom du technicien à assigner :');
    if (!technicien) return;
    fetch('<?= BASE_URL ?>laboratoire/assigner-technicien', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `demande_id=${demandeId}&technicien_id=1`
    }).then(() => location.reload());
}

// ── Assigner en masse ──
function assignerTechnicienMasse() {
    const selected = [...document.querySelectorAll('.demande-checkbox:checked')].map(cb => cb.value);
    if (selected.length === 0) { alert('Veuillez sélectionner au moins une demande.'); return; }
    const technicien = prompt(`Assigner ${selected.length} demande(s) à quel technicien ?`);
    if (!technicien) return;
    alert(`${selected.length} demande(s) assignée(s) à : ${technicien}`);
}

// ── Valider résultats ──
function validerResultats(demandeId) {
    if (!confirm('Valider définitivement ces résultats ? Cette action est irréversible.')) return;
    fetch('<?= BASE_URL ?>laboratoire/valider-resultats', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `demande_id=${demandeId}`
    }).then(() => location.reload());
}

// ── Auto-refresh toutes les 30 secondes ──
setTimeout(() => location.reload(), 30000);
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
