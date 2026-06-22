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

.labo-topbar {
    background: linear-gradient(135deg, #1a237e 0%, #283593 60%, #1565c0 100%);
    color: #fff; padding: 0 2rem; height: 62px;
    display: flex; align-items: center; justify-content: space-between;
    position: sticky; top: 0; z-index: 1000;
    box-shadow: 0 2px 12px rgba(21,101,192,.35);
}
.brand { display: flex; align-items: center; gap: .75rem; font-size: 1.05rem; font-weight: 700; }
.brand-icon { width: 36px; height: 36px; background: rgba(255,255,255,.15); border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.1rem; }
.page-badge { background: rgba(255,255,255,.12); border: 1px solid rgba(255,255,255,.2); border-radius: 20px; padding: .35rem 1rem; font-size: .9rem; font-weight: 600; }

.filter-bar {
    background: #fff; border-bottom: 1px solid #e8edf2;
    padding: .9rem 2rem; display: flex; align-items: center;
    justify-content: space-between; flex-wrap: wrap; gap: .8rem;
    position: sticky; top: 62px; z-index: 999;
    box-shadow: 0 2px 8px rgba(0,0,0,.04);
}
.count-info { font-size: .82rem; font-weight: 600; color: #64748b; }
.count-num  { color: #1565c0; font-size: 1.05rem; font-weight: 800; }

.labo-content { max-width: 1400px; margin: 0 auto; padding: 2rem 2rem 4rem; }

.table-card { background: #fff; border-radius: 16px; box-shadow: 0 2px 16px rgba(0,0,0,.07); overflow: hidden; }
.dem-table { width: 100%; border-collapse: collapse; }
.dem-table thead tr { background: #f8fafc; }
.dem-table thead th { padding: .9rem 1.1rem; font-size: .72rem; text-transform: uppercase; letter-spacing: .8px; color: #78909c; font-weight: 700; border-bottom: 2px solid #e8edf2; }
.dem-table tbody tr { border-bottom: 1px solid #f1f5f9; transition: background .15s; }
.dem-table tbody tr:hover { background: #f8fafc; }
.dem-table tbody tr:last-child { border-bottom: none; }
.dem-table tbody td { padding: 1rem 1.1rem; vertical-align: middle; }

.pat-name    { font-weight: 700; color: #1e293b; font-size: .9rem; }
.dos-badge   { background: #f1f5f9; color: #475569; font-size: .73rem; font-weight: 700; padding: .2rem .6rem; border-radius: 8px; border: 1px solid #e2e8f0; font-family: monospace; }
.med-name    { font-size: .88rem; color: #334155; font-weight: 600; }
.date-main   { font-weight: 600; color: #334155; font-size: .88rem; }
.date-sub    { font-size: .75rem; color: #94a3b8; }
.exam-count  { background: #ede9fe; color: #6d28d9; font-size: .72rem; font-weight: 700; padding: .2rem .65rem; border-radius: 20px; border: 1px solid #ddd6fe; display: inline-block; }
.badge-urgent { background: #fef2f2; color: #dc2626; font-size: .72rem; font-weight: 800; padding: .2rem .6rem; border-radius: 20px; border: 1px solid #fecaca; display: inline-block; margin-top: .2rem; animation: pulse-r 1.5s infinite; }
@keyframes pulse-r { 0%,100%{box-shadow:0 0 0 0 rgba(220,38,38,.3)}50%{box-shadow:0 0 0 4px rgba(220,38,38,0)} }

.stat-badge { font-size: .74rem; font-weight: 700; padding: .3rem .8rem; border-radius: 20px; display: inline-flex; align-items: center; gap: .3rem; }
.stat-en_attente             { background:#fff8e1; color:#f57f17; border:1px solid #ffe082; }
.stat-prelevements_effectues { background:#e3f2fd; color:#1565c0; border:1px solid #90caf9; }
.stat-en_analyse             { background:#e8f5e9; color:#2e7d32; border:1px solid #a5d6a7; }
.stat-resultats_prets        { background:#f3e8ff; color:#7c3aed; border:1px solid #e9d5ff; }

.btn-group-actions { display: flex; gap: .4rem; justify-content: flex-end; }
.btn-traiter { background: linear-gradient(135deg,#1565c0,#0288d1); color:#fff; border:none; border-radius:8px; padding:.45rem 1rem; font-size:.82rem; font-weight:700; text-decoration:none; display:inline-flex; align-items:center; gap:.35rem; transition:opacity .2s; }
.btn-traiter:hover { opacity:.88; color:#fff; }
.btn-more { background:#f1f5f9; color:#475569; border:1px solid #e2e8f0; border-radius:8px; padding:.45rem .65rem; font-size:.82rem; cursor:pointer; }
.btn-more:hover { background:#e2e8f0; }

.empty-state { text-align:center; padding:5rem 2rem; color:#94a3b8; }
.empty-state .empty-icon { font-size:3.5rem; opacity:.3; margin-bottom:1rem; }
</style>

<!-- ── Top bar ── -->
<div class="labo-topbar">
    <div class="brand">
        <div class="brand-icon"><i class="bi bi-flask"></i></div>
        <span>Laboratoire</span>
    </div>
    <span class="page-badge"><i class="bi bi-flask me-1"></i>Demandes d'examens intégrées</span>
    <a href="<?= BASE_URL ?>laboratoire" style="background:rgba(255,255,255,.15);border:1px solid rgba(255,255,255,.3);color:#fff;border-radius:8px;padding:.4rem 1rem;font-size:.875rem;font-weight:600;text-decoration:none;display:inline-flex;align-items:center;gap:.4rem;">
        <i class="bi bi-arrow-left"></i> Dashboard
    </a>
</div>

<!-- ── Sub bar ── -->
<div class="filter-bar">
    <div class="count-info">
        <span class="count-num"><?= count($demandes) ?></span> demande<?= count($demandes) > 1 ? 's' : '' ?> en attente de traitement
    </div>
    <div style="font-size:.78rem;color:#94a3b8;">
        <i class="bi bi-clock me-1"></i>Mis à jour à <?= date('H:i') ?>
    </div>
</div>

<!-- ── Content ── -->
<div class="labo-content">
    <div class="table-card">
        <div style="overflow-x:auto;">
            <table class="dem-table">
                <thead>
                    <tr>
                        <th class="ps-4">Date</th>
                        <th>Patient</th>
                        <th>Dossier</th>
                        <th>Médecin</th>
                        <th>Examens</th>
                        <th>Statut</th>
                        <th class="text-end pe-4">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($demandes) > 0): ?>
                        <?php foreach ($demandes as $d): ?>
                        <?php
                            $st = strtolower($d['statut']);
                            $stLabels = [
                                'en_attente'             => 'En attente',
                                'prelevements_effectues' => 'Prélevé',
                                'en_analyse'             => 'En analyse',
                                'resultats_prets'        => 'Résultats prêts',
                            ];
                            $stLabel = $stLabels[$st] ?? ucfirst(str_replace('_',' ',$st));

                            // Compter urgents
                            try {
                                $stmtU = (new Database())->getConnection()->prepare("SELECT COUNT(*) as nb FROM demande_examens WHERE demande_id = ? AND urgent = 1");
                                $stmtU->execute([$d['id']]);
                                $nbUrgent = $stmtU->fetch(PDO::FETCH_ASSOC)['nb'] ?? 0;
                            } catch(Exception $e) { $nbUrgent = 0; }
                        ?>
                        <tr>
                            <td class="ps-4">
                                <div class="date-main"><?= date('d/m/Y', strtotime($d['date_creation'])) ?></div>
                                <div class="date-sub"><?= date('H:i', strtotime($d['date_creation'])) ?></div>
                            </td>
                            <td>
                                <div class="pat-name"><?= htmlspecialchars($d['nom'] . ' ' . $d['prenom']) ?></div>
                            </td>
                            <td>
                                <span class="dos-badge"><?= htmlspecialchars($d['dossier_numero']) ?></span>
                            </td>
                            <td>
                                <div class="med-name">Dr. <?= htmlspecialchars($d['medecin_nom'] . ' ' . $d['medecin_prenom']) ?></div>
                            </td>
                            <td>
                                <span class="exam-count"><i class="bi bi-flask me-1"></i><?= $d['nb_examens'] ?> examen<?= $d['nb_examens'] > 1 ? 's' : '' ?></span>
                                <?php if ($nbUrgent > 0): ?>
                                    <br><span class="badge-urgent">⚡ <?= $nbUrgent ?> urgent<?= $nbUrgent > 1 ? 's' : '' ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="stat-badge stat-<?= $st ?>"><?= $stLabel ?></span>
                            </td>
                            <td class="pe-4">
                                <div class="btn-group-actions">
                                    <a href="<?= BASE_URL ?>laboratoire/traitement/<?= $d['id'] ?>" class="btn-traiter">
                                        <i class="bi bi-flask"></i> Traiter
                                    </a>
                                    <div class="dropdown">
                                        <button class="btn-more dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                            <i class="bi bi-three-dots-vertical"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <li><a class="dropdown-item" href="#"><i class="bi bi-eye me-2"></i>Voir détails</a></li>
                                            <li><a class="dropdown-item" href="#"><i class="bi bi-printer me-2"></i>Étiquettes</a></li>
                                        </ul>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7">
                                <div class="empty-state">
                                    <div class="empty-icon"><i class="bi bi-check2-circle"></i></div>
                                    <p>Aucune demande d'examen en attente</p>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
