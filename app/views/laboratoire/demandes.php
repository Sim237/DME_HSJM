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
$demandes = $demandes ?? [];
?>

<style>
body { background: #f0f4f8; }

/* ── Top bar ───────────────────────────────────────────────────── */
.labo-topbar {
    background: linear-gradient(135deg, #1a237e 0%, #283593 60%, #1565c0 100%);
    color: #fff; padding: 0 2rem; height: 62px;
    display: flex; align-items: center; justify-content: space-between;
    position: sticky; top: 0; z-index: 1000;
    box-shadow: 0 2px 12px rgba(21,101,192,.35);
}
.brand { display: flex; align-items: center; gap: .75rem; font-size: 1.05rem; font-weight: 700; }
.brand-icon {
    width: 36px; height: 36px; background: rgba(255,255,255,.15);
    border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.1rem;
}
.page-badge {
    background: rgba(255,255,255,.12); border: 1px solid rgba(255,255,255,.2);
    border-radius: 20px; padding: .35rem 1rem; font-size: .9rem; font-weight: 600;
}

/* ── Filter bar ────────────────────────────────────────────────── */
.filter-bar {
    background: #fff; border-bottom: 1px solid #e8edf2;
    padding: .9rem 2rem; display: flex; align-items: center;
    justify-content: space-between; flex-wrap: wrap; gap: .8rem;
    position: sticky; top: 62px; z-index: 999;
    box-shadow: 0 2px 8px rgba(0,0,0,.04);
}
.filter-chips { display: flex; gap: .5rem; flex-wrap: wrap; }
.chip {
    padding: .35rem .9rem; border-radius: 20px; font-size: .8rem; font-weight: 700;
    border: 2px solid transparent; cursor: pointer; transition: all .2s;
    background: #f1f5f9; color: #475569;
}
.chip:hover { background: #e2e8f0; }
.chip.active { background: #1565c0; color: #fff; border-color: #1565c0; }
.chip.c-warning.active { background: #f59e0b; color: #fff; border-color: #f59e0b; }
.chip.c-info.active    { background: #0284c7; color: #fff; border-color: #0284c7; }
.chip.c-success.active { background: #16a34a; color: #fff; border-color: #16a34a; }
.count-badge {
    background: #eff6ff; color: #1d4ed8; font-size: .78rem; font-weight: 700;
    padding: .3rem .9rem; border-radius: 20px; border: 1px solid #bfdbfe;
}

/* ── Content ───────────────────────────────────────────────────── */
.labo-content { max-width: 1400px; margin: 0 auto; padding: 2rem 2rem 4rem; }

/* ── Table card ────────────────────────────────────────────────── */
.table-card {
    background: #fff; border-radius: 16px;
    box-shadow: 0 2px 16px rgba(0,0,0,.07); overflow: hidden;
}
.dem-table { width: 100%; border-collapse: collapse; }
.dem-table thead tr { background: #f8fafc; }
.dem-table thead th {
    padding: .9rem 1.1rem; font-size: .72rem; text-transform: uppercase;
    letter-spacing: .8px; color: #78909c; font-weight: 700;
    border-bottom: 2px solid #e8edf2;
}
.dem-table tbody tr { border-bottom: 1px solid #f1f5f9; transition: background .15s; }
.dem-table tbody tr:hover { background: #f8fafc; }
.dem-table tbody tr:last-child { border-bottom: none; }
.dem-table tbody td { padding: 1rem 1.1rem; vertical-align: middle; }

/* ── Cells ─────────────────────────────────────────────────────── */
.num-badge {
    background: #f1f5f9; color: #475569; font-size: .75rem; font-weight: 700;
    padding: .25rem .6rem; border-radius: 8px; border: 1px solid #e2e8f0; font-family: monospace;
}
.pat-name { font-weight: 700; color: #1e293b; font-size: .9rem; }
.pat-dossier { font-size: .75rem; color: #94a3b8; margin-top: .1rem; }
.exam-count {
    background: #ede9fe; color: #6d28d9; font-size: .72rem; font-weight: 700;
    padding: .2rem .65rem; border-radius: 20px; border: 1px solid #ddd6fe;
    display: inline-block;
}
.date-main { font-weight: 600; color: #334155; font-size: .88rem; }
.date-sub  { font-size: .75rem; color: #94a3b8; }

.stat-badge {
    font-size: .74rem; font-weight: 700; padding: .3rem .8rem;
    border-radius: 20px; display: inline-flex; align-items: center; gap: .3rem;
}
.stat-en_attente              { background: #fff8e1; color: #f57f17; border: 1px solid #ffe082; }
.stat-prelevements_effectues  { background: #e3f2fd; color: #1565c0; border: 1px solid #90caf9; }
.stat-en_analyse              { background: #e8f5e9; color: #2e7d32; border: 1px solid #a5d6a7; }
.stat-resultats_prets         { background: #f3e8ff; color: #7c3aed; border: 1px solid #e9d5ff; }
.stat-valides                 { background: #dcfce7; color: #15803d; border: 1px solid #86efac; }

.btn-traiter {
    background: linear-gradient(135deg, #1565c0, #0288d1);
    color: #fff; border: none; border-radius: 8px;
    padding: .45rem 1rem; font-size: .82rem; font-weight: 700;
    text-decoration: none; display: inline-flex; align-items: center; gap: .35rem;
    transition: opacity .2s;
}
.btn-traiter:hover { opacity: .88; color: #fff; }

/* ── Empty state ───────────────────────────────────────────────── */
.empty-state {
    text-align: center; padding: 5rem 2rem; color: #94a3b8;
}
.empty-state .empty-icon { font-size: 3.5rem; opacity: .3; margin-bottom: 1rem; }
.empty-state p { font-size: 1rem; margin: 0; }
</style>

<!-- ── Top bar ── -->
<div class="labo-topbar">
    <div class="brand">
        <div class="brand-icon"><i class="bi bi-flask"></i></div>
        <span>Laboratoire</span>
    </div>
    <span class="page-badge"><i class="bi bi-activity me-1"></i>Demandes d'examens</span>
    <a href="<?= BASE_URL ?>laboratoire" style="background:rgba(255,255,255,.15);border:1px solid rgba(255,255,255,.3);color:#fff;border-radius:8px;padding:.4rem 1rem;font-size:.875rem;font-weight:600;text-decoration:none;display:inline-flex;align-items:center;gap:.4rem;">
        <i class="bi bi-arrow-left"></i> Dashboard
    </a>
</div>

<!-- ── Filter bar ── -->
<div class="filter-bar">
    <div class="filter-chips">
        <span class="chip active" onclick="filtrer('all', this)">Toutes</span>
        <span class="chip c-warning" onclick="filtrer('EN_ATTENTE', this)"><i class="bi bi-hourglass-split me-1"></i>En attente</span>
        <span class="chip c-info"    onclick="filtrer('EN_COURS', this)"><i class="bi bi-play-circle me-1"></i>En cours</span>
        <span class="chip c-success" onclick="filtrer('TERMINE', this)"><i class="bi bi-check-circle me-1"></i>Terminées</span>
    </div>
    <span class="count-badge" id="countBadge">
        <?= count($demandes) ?> demande<?= count($demandes) > 1 ? 's' : '' ?>
    </span>
</div>

<!-- ── Content ── -->
<div class="labo-content">
    <div class="table-card">
        <div style="overflow-x:auto;">
            <table class="dem-table" id="tableExamens">
                <thead>
                    <tr>
                        <th class="ps-4">N°</th>
                        <th>Patient</th>
                        <th>Examens</th>
                        <th>Date demande</th>
                        <th>Urgence</th>
                        <th>Statut</th>
                        <th class="text-end pe-4">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($demandes) > 0): ?>
                        <?php foreach ($demandes as $d): ?>
                        <?php
                            $st = strtolower($d['statut']);
                            $stLabel = [
                                'en_attente'             => 'En attente',
                                'prelevements_effectues' => 'Prélevé',
                                'en_analyse'             => 'En analyse',
                                'resultats_prets'        => 'Résultats prêts',
                                'valides'                => 'Validé',
                            ][$st] ?? ucfirst(str_replace('_',' ',$st));
                        ?>
                        <tr data-statut="<?= htmlspecialchars($d['statut']) ?>">
                            <td class="ps-4">
                                <span class="num-badge">#<?= str_pad($d['id'], 5, '0', STR_PAD_LEFT) ?></span>
                            </td>
                            <td>
                                <div class="pat-name"><?= htmlspecialchars($d['nom'] . ' ' . $d['prenom']) ?></div>
                                <div class="pat-dossier"><i class="bi bi-folder2 me-1"></i><?= htmlspecialchars($d['dossier_numero'] ?? '-') ?></div>
                            </td>
                            <td>
                                <span class="exam-count"><i class="bi bi-flask me-1"></i><?= $d['nb_examens'] ?> examen<?= $d['nb_examens'] > 1 ? 's' : '' ?></span>
                            </td>
                            <td>
                                <div class="date-main"><?= date('d/m/Y', strtotime($d['date_creation'])) ?></div>
                                <div class="date-sub"><?= date('H:i', strtotime($d['date_creation'])) ?></div>
                            </td>
                            <td>
                                <span style="background:#f1f5f9;color:#64748b;font-size:.75rem;font-weight:600;padding:.2rem .65rem;border-radius:12px;">Normal</span>
                            </td>
                            <td>
                                <span class="stat-badge stat-<?= $st ?>"><?= $stLabel ?></span>
                            </td>
                            <td class="text-end pe-4">
                                <a href="<?= BASE_URL ?>laboratoire/traitement/<?= $d['id'] ?>" class="btn-traiter">
                                    <i class="bi bi-play-fill"></i> Traiter
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7">
                                <div class="empty-state">
                                    <div class="empty-icon"><i class="bi bi-clipboard-x"></i></div>
                                    <p>Aucune demande d'examen trouvée</p>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function filtrer(statut, btn) {
    const rows = document.querySelectorAll('#tableExamens tbody tr[data-statut]');
    let visible = 0;
    rows.forEach(row => {
        const show = statut === 'all' || row.dataset.statut === statut;
        row.style.display = show ? '' : 'none';
        if (show) visible++;
    });
    document.getElementById('countBadge').textContent = visible + ' demande' + (visible > 1 ? 's' : '');
    document.querySelectorAll('.chip').forEach(c => c.classList.remove('active'));
    btn.classList.add('active');
}
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
