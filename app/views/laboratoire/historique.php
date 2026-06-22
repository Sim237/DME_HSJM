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

$historique = $historique ?? [];
$dateDebut  = $dateDebut  ?? date('Y-m-d', strtotime('-30 days'));
$dateFin    = $dateFin    ?? date('Y-m-d');
$statut     = $statut     ?? '';

$statutLabels = [
    'EN_ATTENTE'             => ['label' => 'En attente',         'color' => '#f59e0b', 'bg' => '#fff7ed'],
    'DISPATCHE'              => ['label' => 'Dispatché',           'color' => '#0891b2', 'bg' => '#ecfeff'],
    'RESULTATS_SOUMIS'       => ['label' => 'Résultats soumis',   'color' => '#3b82f6', 'bg' => '#eff6ff'],
    'VALIDES'                => ['label' => 'Validés',             'color' => '#16a34a', 'bg' => '#f0fdf4'],
    'PRELEVEMENTS_EFFECTUES' => ['label' => 'Prélèvements',        'color' => '#7c3aed', 'bg' => '#f5f3ff'],
    'EN_ANALYSE'             => ['label' => 'En analyse',          'color' => '#2563eb', 'bg' => '#eff6ff'],
    'RESULTATS_PRETS'        => ['label' => 'Résultats prêts',     'color' => '#16a34a', 'bg' => '#f0fdf4'],
];

$nbTotal   = count($historique);
$nbValides = count(array_filter($historique, fn($d) => $d['statut'] === 'VALIDES'));
$nbUrgents = array_sum(array_column($historique, 'nb_urgents'));
$tauxValid = $nbTotal > 0 ? round($nbValides / $nbTotal * 100) : 0;
?>

<style>
/* ── Cockpit mode — no sidebar ── */
.sidebar, #sidebar, nav.sidebar { display: none !important; }
main, .main-content, #main-content { margin-left: 0 !important; width: 100% !important; padding: 0 !important; }
body { background: #f0f4f8; font-family: 'Segoe UI', system-ui, sans-serif; color: #1e293b; margin: 0; }

/* ── Header ── */
.hist-header {
    background: linear-gradient(135deg, #0f4c75 0%, #1b6ca8 100%);
    color: #fff; padding: 0 30px; height: 68px;
    display: flex; align-items: center; justify-content: space-between;
    position: sticky; top: 0; z-index: 200;
    box-shadow: 0 4px 20px rgba(0,0,0,.20);
}
.hist-header-left { display: flex; align-items: center; gap: 16px; }
.hist-title {
    font-size: 1.05rem; font-weight: 800; letter-spacing: .3px;
}
.hist-title span { color: #67e8f9; }
.hist-btn {
    display: inline-flex; align-items: center; gap: 7px;
    padding: 7px 18px; border-radius: 50px; font-size: .8rem; font-weight: 700;
    border: none; cursor: pointer; text-decoration: none; transition: all .18s;
    white-space: nowrap;
}
.hist-btn-back   { background: rgba(255,255,255,.15); color: #fff; border: 1px solid rgba(255,255,255,.3); }
.hist-btn-back:hover { background: rgba(255,255,255,.25); color: #fff; }
.hist-btn-print  { background: rgba(255,255,255,.12); color: #fff; border: 1px solid rgba(255,255,255,.2); }
.hist-btn-print:hover { background: rgba(255,255,255,.22); color: #fff; }
.hist-badge-count {
    background: rgba(255,255,255,.2); border: 1px solid rgba(255,255,255,.3);
    border-radius: 20px; padding: 2px 12px; font-size: .78rem; font-weight: 700; color: #fff;
}

/* ── Body ── */
.hist-body { padding: 24px 30px 40px; max-width: 1500px; margin: 0 auto; }

/* ── Filtre ── */
.filtre-card {
    background: #fff; border-radius: 16px;
    box-shadow: 0 2px 12px rgba(0,0,0,.06);
    border: 1px solid #e2e8f0;
    padding: 18px 22px; margin-bottom: 20px;
}
.filtre-card label { font-size: .72rem; font-weight: 700; color: #64748b;
                     text-transform: uppercase; letter-spacing: .5px; display: block; margin-bottom: 4px; }
.filtre-input, .filtre-select {
    width: 100%; padding: 8px 12px; border-radius: 10px;
    border: 1.5px solid #e2e8f0; font-size: .83rem; color: #1e293b;
    outline: none; background: #f8fafc; transition: border .15s;
    font-family: inherit;
}
.filtre-input:focus, .filtre-select:focus { border-color: #1b6ca8; background: #fff; }
.btn-filtrer {
    padding: 8px 20px; border-radius: 10px; border: none;
    background: linear-gradient(135deg, #0f4c75, #1b6ca8);
    color: #fff; font-size: .83rem; font-weight: 700; cursor: pointer;
    display: inline-flex; align-items: center; gap: 6px; transition: all .15s;
}
.btn-filtrer:hover { opacity: .9; transform: translateY(-1px); }
.btn-reset {
    padding: 8px 16px; border-radius: 10px; border: 1.5px solid #e2e8f0;
    background: #fff; color: #64748b; font-size: .83rem; font-weight: 600;
    cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 6px;
    transition: background .15s;
}
.btn-reset:hover { background: #f8fafc; color: #374151; }

/* ── KPI Strip ── */
.kpi-strip { display: flex; gap: 14px; margin-bottom: 20px; flex-wrap: wrap; }
.kpi-box {
    flex: 1; min-width: 140px; background: #fff; border-radius: 14px;
    padding: 16px 20px; text-align: center;
    box-shadow: 0 2px 10px rgba(0,0,0,.05); border-bottom: 4px solid #e2e8f0;
}
.kpi-box.kpi-total  { border-color: #3b82f6; }
.kpi-box.kpi-valid  { border-color: #16a34a; }
.kpi-box.kpi-urg    { border-color: #ef4444; }
.kpi-box.kpi-taux   { border-color: #0891b2; }
.kpi-num   { font-size: 2rem; font-weight: 800; line-height: 1.1; }
.kpi-lbl   { font-size: .7rem; font-weight: 700; color: #94a3b8; text-transform: uppercase; margin-top: 2px; }

/* ── Tableau ── */
.hist-card {
    background: #fff; border-radius: 16px;
    box-shadow: 0 2px 12px rgba(0,0,0,.06); border: 1px solid #e2e8f0;
    overflow: hidden;
}
.hist-card-head {
    padding: 14px 20px; border-bottom: 1px solid #f0f2f8;
    display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px;
}
.hist-card-title { font-size: .88rem; font-weight: 700; color: #1e293b; display: flex; align-items: center; gap: 8px; }
.search-wrap { position: relative; }
.search-wrap i { position: absolute; left: 11px; top: 50%; transform: translateY(-50%);
                 color: #94a3b8; font-size: .85rem; pointer-events: none; }
.hist-search {
    padding: 7px 12px 7px 34px; border-radius: 10px;
    border: 1.5px solid #e2e8f0; font-size: .8rem; color: #1e293b;
    outline: none; background: #f8fafc; width: 220px; transition: border .15s;
}
.hist-search:focus { border-color: #1b6ca8; background: #fff; }

table.hist-table { width: 100%; border-collapse: collapse; }
table.hist-table thead tr {
    background: #f8fafc;
}
table.hist-table thead th {
    padding: 11px 14px; font-size: .68rem; font-weight: 800;
    text-transform: uppercase; letter-spacing: .6px; color: #64748b;
    border-bottom: 1px solid #e2e8f0; white-space: nowrap;
}
table.hist-table tbody tr {
    border-bottom: 1px solid #f1f5f9; transition: background .12s;
}
table.hist-table tbody tr:hover { background: #f8fafc; }
table.hist-table tbody td { padding: 12px 14px; font-size: .82rem; }

.pat-name   { font-weight: 700; font-size: .85rem; }
.pat-num    { font-size: .72rem; color: #94a3b8; }
.stat-pill  { display: inline-block; padding: 3px 10px; border-radius: 20px;
              font-size: .7rem; font-weight: 700; }
.urg-badge  { background: #fef2f2; color: #dc2626; border: 1px solid #fca5a5;
              border-radius: 20px; padding: 2px 8px; font-size: .68rem; font-weight: 700; }
.act-btn {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 5px 12px; border-radius: 8px; font-size: .75rem; font-weight: 600;
    text-decoration: none; border: 1.5px solid; transition: all .14s; cursor: pointer;
}
.act-btn-view   { color: #1b6ca8; border-color: #bfdbfe; background: #eff6ff; }
.act-btn-view:hover   { background: #dbeafe; color: #1d4ed8; }
.act-btn-print  { color: #64748b; border-color: #e2e8f0; background: #f8fafc; }
.act-btn-print:hover  { background: #f1f5f9; color: #374151; }

.empty-state {
    text-align: center; padding: 60px 20px; color: #94a3b8;
}
.empty-state i { font-size: 3rem; opacity: .2; display: block; margin-bottom: 12px; }
</style>

<!-- ── HEADER ─────────────────────────────────────────────────────────── -->
<div class="hist-header">
    <div class="hist-header-left">
        <a href="<?= BASE_URL ?>laboratoire" class="hist-btn hist-btn-back">
            <i class="bi bi-arrow-left"></i> Retour cockpit
        </a>
        <div>
            <div class="hist-title">
                <i class="bi bi-clock-history me-2" style="color:#67e8f9;"></i>
                HISTORIQUE <span>LABORATOIRE</span>
            </div>
            <div style="font-size:.7rem;color:rgba(255,255,255,.6);margin-top:1px;">
                <?= date('d/m/Y', strtotime($dateDebut)) ?> — <?= date('d/m/Y', strtotime($dateFin)) ?>
            </div>
        </div>
    </div>
    <div style="display:flex;align-items:center;gap:10px;">
        <span class="hist-badge-count"><?= $nbTotal ?> demande<?= $nbTotal > 1 ? 's' : '' ?></span>
        <button onclick="window.print()" class="hist-btn hist-btn-print">
            <i class="bi bi-printer"></i> Imprimer
        </button>
    </div>
</div>

<!-- ── BODY ──────────────────────────────────────────────────────────── -->
<div class="hist-body">

    <!-- Filtres -->
    <div class="filtre-card">
        <form method="GET" action="">
            <div style="display:flex;gap:14px;align-items:flex-end;flex-wrap:wrap;">
                <div style="flex:1;min-width:140px;">
                    <label><i class="bi bi-calendar3 me-1"></i>Date début</label>
                    <input type="date" name="debut" value="<?= htmlspecialchars($dateDebut) ?>" class="filtre-input">
                </div>
                <div style="flex:1;min-width:140px;">
                    <label><i class="bi bi-calendar3-range me-1"></i>Date fin</label>
                    <input type="date" name="fin" value="<?= htmlspecialchars($dateFin) ?>" class="filtre-input">
                </div>
                <div style="flex:1;min-width:160px;">
                    <label><i class="bi bi-funnel me-1"></i>Statut</label>
                    <select name="statut" class="filtre-select">
                        <option value="">Tous les statuts</option>
                        <option value="EN_ATTENTE"             <?= $statut === 'EN_ATTENTE'             ? 'selected' : '' ?>>En attente</option>
                        <option value="DISPATCHE"              <?= $statut === 'DISPATCHE'              ? 'selected' : '' ?>>Dispatché</option>
                        <option value="EN_ANALYSE"             <?= $statut === 'EN_ANALYSE'             ? 'selected' : '' ?>>En analyse</option>
                        <option value="RESULTATS_SOUMIS"       <?= $statut === 'RESULTATS_SOUMIS'       ? 'selected' : '' ?>>Résultats soumis</option>
                        <option value="RESULTATS_PRETS"        <?= $statut === 'RESULTATS_PRETS'        ? 'selected' : '' ?>>Résultats prêts</option>
                        <option value="VALIDES"                <?= $statut === 'VALIDES'                ? 'selected' : '' ?>>Validés</option>
                        <option value="PRELEVEMENTS_EFFECTUES" <?= $statut === 'PRELEVEMENTS_EFFECTUES' ? 'selected' : '' ?>>Prélèvements effectués</option>
                    </select>
                </div>
                <div style="display:flex;gap:8px;padding-bottom:0;">
                    <button type="submit" class="btn-filtrer">
                        <i class="bi bi-funnel-fill"></i> Filtrer
                    </button>
                    <a href="<?= BASE_URL ?>laboratoire/historique" class="btn-reset">
                        <i class="bi bi-arrow-counterclockwise"></i> Réinitialiser
                    </a>
                </div>
            </div>
        </form>
    </div>

    <!-- KPIs -->
    <div class="kpi-strip">
        <div class="kpi-box kpi-total">
            <div class="kpi-num" style="color:#3b82f6;"><?= $nbTotal ?></div>
            <div class="kpi-lbl">Total demandes</div>
        </div>
        <div class="kpi-box kpi-valid">
            <div class="kpi-num" style="color:#16a34a;"><?= $nbValides ?></div>
            <div class="kpi-lbl">Dossiers validés</div>
        </div>
        <div class="kpi-box kpi-urg">
            <div class="kpi-num" style="color:#ef4444;"><?= $nbUrgents ?></div>
            <div class="kpi-lbl">Examens urgents</div>
        </div>
        <div class="kpi-box kpi-taux">
            <div class="kpi-num" style="color:#0891b2;"><?= $tauxValid ?>%</div>
            <div class="kpi-lbl">Taux de validation</div>
        </div>
    </div>

    <!-- Tableau -->
    <div class="hist-card">
        <div class="hist-card-head">
            <div class="hist-card-title">
                <i class="bi bi-table" style="color:#1b6ca8;"></i>
                Résultats
                <span style="background:#eff6ff;color:#1d4ed8;padding:2px 10px;border-radius:20px;font-size:.72rem;font-weight:700;">
                    <?= $nbTotal ?>
                </span>
            </div>
            <div class="search-wrap">
                <i class="bi bi-search"></i>
                <input type="text" id="searchHisto" class="hist-search"
                       placeholder="Rechercher patient…"
                       oninput="filterHisto(this.value)">
            </div>
        </div>

        <?php if (empty($historique)): ?>
        <div class="empty-state">
            <i class="bi bi-inbox"></i>
            <p style="font-size:1.1rem;font-weight:700;margin-bottom:4px;">Aucune demande sur cette période</p>
            <p style="font-size:.82rem;">Modifiez les filtres pour élargir la recherche.</p>
        </div>
        <?php else: ?>
        <div style="overflow-x:auto;">
            <table class="hist-table">
                <thead>
                    <tr>
                        <th style="padding-left:20px;">Date</th>
                        <th>Patient</th>
                        <th>Médecin</th>
                        <th style="text-align:center;">Examens</th>
                        <th style="text-align:center;">Progression</th>
                        <th style="text-align:center;">Urgents</th>
                        <th>Statut</th>
                        <th style="text-align:center;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($historique as $d):
                    $sl     = $statutLabels[$d['statut']] ?? ['label' => $d['statut'], 'color' => '#64748b', 'bg' => '#f1f5f9'];
                    $nom    = htmlspecialchars($d['nom'] . ' ' . $d['prenom']);
                    $progW  = $d['nb_examens'] > 0 ? round($d['nb_valides'] / $d['nb_examens'] * 100) : 0;
                ?>
                <tr class="histo-row"
                    data-search="<?= strtolower(htmlspecialchars($d['nom'] . ' ' . $d['prenom'] . ' ' . $d['dossier_numero'] . ' ' . ($d['medecin_nom'] ?? ''))) ?>">
                    <td style="padding-left:20px;">
                        <div style="font-weight:700;font-size:.85rem;"><?= date('d/m/Y', strtotime($d['date_creation'])) ?></div>
                        <div style="font-size:.72rem;color:#94a3b8;"><?= date('H:i', strtotime($d['date_creation'])) ?></div>
                    </td>
                    <td>
                        <div class="pat-name"><?= $nom ?></div>
                        <div class="pat-num"><?= htmlspecialchars($d['dossier_numero']) ?></div>
                    </td>
                    <td>
                        <?php if (!empty($d['medecin_nom'])): ?>
                            <span style="font-size:.82rem;">Dr. <?= htmlspecialchars($d['medecin_nom'] . ' ' . ($d['medecin_prenom'] ?? '')) ?></span>
                        <?php else: ?>
                            <span style="color:#94a3b8;">—</span>
                        <?php endif; ?>
                    </td>
                    <td style="text-align:center;">
                        <span style="background:#f1f5f9;color:#1e293b;padding:3px 10px;border-radius:20px;font-size:.75rem;font-weight:700;">
                            <?= (int)$d['nb_examens'] ?>
                        </span>
                    </td>
                    <td style="text-align:center;">
                        <div style="display:flex;align-items:center;gap:8px;justify-content:center;">
                            <div style="width:64px;height:6px;background:#e2e8f0;border-radius:4px;overflow:hidden;">
                                <div style="width:<?= $progW ?>%;height:100%;background:#16a34a;border-radius:4px;"></div>
                            </div>
                            <span style="font-size:.75rem;font-weight:700;color:<?= $progW === 100 ? '#16a34a' : '#64748b' ?>;">
                                <?= $d['nb_valides'] ?>/<?= $d['nb_examens'] ?>
                            </span>
                        </div>
                    </td>
                    <td style="text-align:center;">
                        <?php if ((int)$d['nb_urgents'] > 0): ?>
                            <span class="urg-badge"><i class="bi bi-exclamation-triangle-fill me-1"></i><?= $d['nb_urgents'] ?> urg.</span>
                        <?php else: ?>
                            <span style="color:#94a3b8;font-size:.78rem;">—</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="stat-pill" style="background:<?= $sl['bg'] ?>;color:<?= $sl['color'] ?>;">
                            <?= $sl['label'] ?>
                        </span>
                    </td>
                    <td style="text-align:center;">
                        <div style="display:flex;gap:6px;justify-content:center;">
                            <a href="<?= BASE_URL ?>laboratoire/traitement/<?= $d['id'] ?>"
                               class="act-btn act-btn-view" title="Voir détails">
                                <i class="bi bi-eye"></i> Voir
                            </a>
                            <a href="<?= BASE_URL ?>laboratoire/imprimer/<?= $d['id'] ?>"
                               target="_blank" class="act-btn act-btn-print" title="Imprimer">
                                <i class="bi bi-printer"></i>
                            </a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

</div>

<script>
function filterHisto(query) {
    const q = query.toLowerCase().trim();
    document.querySelectorAll('.histo-row').forEach(row => {
        row.style.display = (!q || row.dataset.search.includes(q)) ? '' : 'none';
    });
}
// Focus search on load
document.addEventListener('DOMContentLoaded', () => {
    document.getElementById('searchHisto')?.focus();
});
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
