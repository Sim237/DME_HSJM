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

/* ============================================================================
FICHIER : app/views/laboratoire/traitement.php
============================================================================ */
require_once __DIR__ . '/../layouts/header.php';

if (!isset($demande) || !$demande) {
    echo "<div class='alert alert-danger m-4'>Erreur : La demande est introuvable ou les données sont corrompues.</div>";
    return;
}
?>

<style>
/* ── Layout pleine largeur ─────────────────────────────────────── */
body { background: #f0f4f8; }

/* ── Top bar labo ──────────────────────────────────────────────── */
.labo-topbar {
    background: linear-gradient(135deg, #1a237e 0%, #283593 60%, #1565c0 100%);
    color: #fff;
    padding: 0 2rem;
    height: 62px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    position: sticky;
    top: 0;
    z-index: 1000;
    box-shadow: 0 2px 12px rgba(21,101,192,.35);
}
.labo-topbar .brand {
    display: flex;
    align-items: center;
    gap: .75rem;
    font-size: 1.05rem;
    font-weight: 700;
    letter-spacing: .3px;
}
.labo-topbar .brand-icon {
    width: 36px; height: 36px;
    background: rgba(255,255,255,.15);
    border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.1rem;
}
.labo-topbar .page-title {
    font-size: .95rem;
    font-weight: 600;
    opacity: .9;
    padding: .35rem 1rem;
    background: rgba(255,255,255,.12);
    border-radius: 20px;
    border: 1px solid rgba(255,255,255,.2);
}
.labo-topbar .btn-back {
    background: rgba(255,255,255,.15);
    border: 1px solid rgba(255,255,255,.3);
    color: #fff;
    border-radius: 8px;
    padding: .4rem 1.1rem;
    font-size: .875rem;
    font-weight: 600;
    text-decoration: none;
    transition: background .2s;
    display: flex; align-items: center; gap: .4rem;
}
.labo-topbar .btn-back:hover { background: rgba(255,255,255,.25); color: #fff; }

/* ── Patient banner ────────────────────────────────────────────── */
.patient-banner {
    background: linear-gradient(120deg, #0d47a1 0%, #1565c0 50%, #0277bd 100%);
    color: #fff;
    padding: 1.4rem 2.5rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 1rem;
}
.patient-banner .pat-avatar {
    width: 52px; height: 52px;
    background: rgba(255,255,255,.2);
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.5rem;
    border: 2px solid rgba(255,255,255,.4);
    flex-shrink: 0;
}
.patient-banner .pat-name { font-size: 1.2rem; font-weight: 700; }
.patient-banner .pat-sub  { font-size: .82rem; opacity: .85; margin-top: .15rem; }
.patient-banner .pat-dossier {
    background: #fff;
    color: #1565c0;
    font-weight: 800;
    font-size: .85rem;
    padding: .35rem 1rem;
    border-radius: 20px;
    letter-spacing: .5px;
}
.banner-meta {
    display: flex; gap: 2.5rem; flex-wrap: wrap;
}
.banner-meta .bm-item label {
    font-size: .72rem;
    text-transform: uppercase;
    letter-spacing: .8px;
    opacity: .75;
    display: block;
    margin-bottom: .1rem;
}
.banner-meta .bm-item .bm-val {
    font-weight: 600;
    font-size: .9rem;
}

/* ── Main content ──────────────────────────────────────────────── */
.labo-content { max-width: 1300px; margin: 0 auto; padding: 2rem 2rem 4rem; }

/* ── Statut badge ──────────────────────────────────────────────── */
.statut-badge {
    display: inline-block;
    padding: .3rem .9rem;
    border-radius: 20px;
    font-size: .8rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .5px;
}
.statut-en_attente  { background: #fff8e1; color: #f57f17; border: 1px solid #ffe082; }
.statut-en_cours    { background: #e3f2fd; color: #1565c0; border: 1px solid #90caf9; }
.statut-termine     { background: #e8f5e9; color: #2e7d32; border: 1px solid #a5d6a7; }

/* ── Card examens ──────────────────────────────────────────────── */
.examens-card {
    background: #fff;
    border-radius: 16px;
    box-shadow: 0 2px 16px rgba(0,0,0,.07);
    overflow: hidden;
}
.examens-card .card-head {
    background: linear-gradient(90deg, #1565c0, #0288d1);
    color: #fff;
    padding: 1rem 1.5rem;
    display: flex; align-items: center; gap: .6rem;
    font-weight: 700;
    font-size: .95rem;
}
.examens-table { width: 100%; border-collapse: collapse; }
.examens-table thead tr { background: #f8fafc; }
.examens-table thead th {
    padding: .85rem 1rem;
    font-size: .72rem;
    text-transform: uppercase;
    letter-spacing: .8px;
    color: #78909c;
    font-weight: 700;
    border-bottom: 1px solid #e8edf2;
}
.examens-table tbody tr { border-bottom: 1px solid #f1f5f9; transition: background .15s; }
.examens-table tbody tr:hover { background: #f8fafc; }
.examens-table tbody tr:last-child { border-bottom: none; }
.examens-table tbody td { padding: 1rem; vertical-align: middle; }
.exam-name { font-weight: 700; color: #1e293b; font-size: .9rem; }
.exam-sub  { font-size: .75rem; color: #94a3b8; margin-top: .15rem; }
.badge-categorie {
    background: #ede9fe; color: #6d28d9;
    font-size: .72rem; font-weight: 700;
    padding: .25rem .7rem; border-radius: 20px;
    border: 1px solid #ddd6fe;
}
.badge-urgent {
    background: #fef2f2; color: #dc2626;
    font-size: .72rem; font-weight: 800;
    padding: .25rem .7rem; border-radius: 20px;
    border: 1px solid #fecaca;
    animation: pulse-red 1.5s infinite;
}
@keyframes pulse-red {
    0%,100% { box-shadow: 0 0 0 0 rgba(220,38,38,.3); }
    50%      { box-shadow: 0 0 0 4px rgba(220,38,38,.0); }
}
.badge-normal { background: #f0fdf4; color: #15803d; font-size: .72rem; font-weight: 700; padding: .25rem .7rem; border-radius: 20px; border: 1px solid #bbf7d0; }
.prelev-chip {
    display: inline-flex; align-items: center; gap: .3rem;
    background: #fef2f2; color: #dc2626;
    font-size: .78rem; font-weight: 600;
    padding: .2rem .65rem; border-radius: 12px;
}
.statut-select {
    border: 1.5px solid #bfdbfe;
    border-radius: 8px;
    font-size: .82rem;
    font-weight: 600;
    padding: .35rem .7rem;
    color: #1e40af;
    background-color: #eff6ff;
    cursor: pointer;
    transition: border-color .2s;
}
.statut-select:focus { border-color: #1565c0; outline: none; box-shadow: 0 0 0 3px rgba(21,101,192,.12); }
.btn-saisir {
    background: linear-gradient(135deg, #1565c0, #0288d1);
    color: #fff;
    border: none;
    border-radius: 8px;
    padding: .45rem 1rem;
    font-size: .82rem;
    font-weight: 600;
    text-decoration: none;
    display: inline-flex; align-items: center; gap: .35rem;
    transition: opacity .2s;
}
.btn-saisir:hover { opacity: .88; color: #fff; }
.tr-urgent td { background: #fff5f5 !important; }

/* ── Footer form ───────────────────────────────────────────────── */
.form-footer {
    background: #fff;
    border-top: 2px solid #f1f5f9;
    padding: 1.4rem 1.8rem;
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    gap: 1.5rem;
    flex-wrap: wrap;
}
.notes-label { font-size: .78rem; font-weight: 700; text-transform: uppercase; letter-spacing: .6px; color: #64748b; margin-bottom: .4rem; }
.notes-textarea {
    border: 1.5px solid #e2e8f0;
    border-radius: 10px;
    font-size: .88rem;
    padding: .6rem .9rem;
    resize: vertical;
    width: 100%;
    transition: border-color .2s;
}
.notes-textarea:focus { border-color: #1565c0; outline: none; box-shadow: 0 0 0 3px rgba(21,101,192,.1); }
.btn-update {
    background: linear-gradient(135deg, #1565c0 0%, #0288d1 100%);
    color: #fff;
    border: none;
    border-radius: 12px;
    padding: .75rem 2rem;
    font-size: .95rem;
    font-weight: 700;
    white-space: nowrap;
    cursor: pointer;
    box-shadow: 0 4px 14px rgba(21,101,192,.35);
    transition: opacity .2s, transform .15s;
}
.btn-update:hover { opacity: .9; transform: translateY(-1px); }
</style>

<!-- ── Top bar ── -->
<div class="labo-topbar">
    <div class="brand">
        <div class="brand-icon"><i class="bi bi-flask"></i></div>
        <span>Laboratoire</span>
    </div>
    <span class="page-title"><i class="bi bi-list-check me-1"></i>Traitement de la Demande</span>
    <a href="<?= BASE_URL ?>laboratoire" class="btn-back">
        <i class="bi bi-arrow-left"></i> Retour au Dashboard
    </a>
</div>

<!-- ── Patient banner ── -->
<div class="patient-banner">
    <div class="d-flex align-items-center gap-3">
        <div class="pat-avatar"><i class="bi bi-person-fill"></i></div>
        <div>
            <div class="pat-name"><?= htmlspecialchars($demande['nom'] . ' ' . $demande['prenom']) ?></div>
            <div class="pat-sub">
                <i class="bi bi-folder2 me-1"></i><?= htmlspecialchars($demande['dossier_numero']) ?>
            </div>
        </div>
    </div>
    <div class="banner-meta">
        <div class="bm-item">
            <label>Médecin prescripteur</label>
            <div class="bm-val">Dr. <?= htmlspecialchars($demande['medecin_nom'] . ' ' . $demande['medecin_prenom']) ?></div>
        </div>
        <div class="bm-item">
            <label>Date de la demande</label>
            <div class="bm-val"><?= date('d/m/Y à H:i', strtotime($demande['date_creation'])) ?></div>
        </div>
        <div class="bm-item">
            <label>Statut</label>
            <div class="bm-val">
                <?php
                $st = strtolower($demande['statut']);
                $stLabel = str_replace('_', ' ', strtoupper($demande['statut']));
                echo "<span class='statut-badge statut-$st'>$stLabel</span>";
                ?>
            </div>
        </div>
    </div>
</div>

<!-- ── Content ── -->
<div class="labo-content">

<?php if (!empty($_GET['success'])): ?>
<div style="background:#f0fdf4;border:1px solid #86efac;color:#15803d;border-radius:12px;padding:.85rem 1.3rem;margin-bottom:1.2rem;font-weight:600;font-size:.88rem;display:flex;align-items:center;gap:.6rem;">
    <i class="bi bi-check-circle-fill"></i> <?= htmlspecialchars(urldecode($_GET['success'])) ?>
</div>
<?php endif; ?>
<?php if (!empty($_GET['error'])): ?>
<div style="background:#fef2f2;border:1px solid #fca5a5;color:#dc2626;border-radius:12px;padding:.85rem 1.3rem;margin-bottom:1.2rem;font-weight:600;font-size:.88rem;display:flex;align-items:center;gap:.6rem;">
    <i class="bi bi-exclamation-triangle-fill"></i> <?= htmlspecialchars(urldecode($_GET['error'])) ?>
</div>
<?php endif; ?>

    <form action="<?= BASE_URL ?>laboratoire/traiter-examens" method="POST">
        <input type="hidden" name="demande_id" value="<?= $demande['id'] ?>">

        <div class="examens-card">
            <div class="card-head">
                <i class="bi bi-list-check"></i>
                Examens à réaliser et prélèvements
                <span style="margin-left:.5rem;background:rgba(255,255,255,.2);padding:.15rem .6rem;border-radius:20px;font-size:.78rem;">
                    <?= count($examens) ?> examen<?= count($examens) > 1 ? 's' : '' ?>
                </span>
            </div>

            <div style="overflow-x:auto;">
                <table class="examens-table">
                    <thead>
                        <tr>
                            <th class="ps-4">Examen</th>
                            <th>Catégorie</th>
                            <th>Prélèvement</th>
                            <th>Délai</th>
                            <th>Priorité</th>
                            <th>Statut</th>
                            <th class="text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($examens as $examen): ?>
                        <?php
                        $statExam = $examen['statut'] ?? 'EN_ATTENTE';
                        $hasResult = !empty(trim((string)($examen['resultat'] ?? '')));
                        $trClass = $examen['urgent'] ? 'tr-urgent' : '';
                        ?>
                        <tr class="<?= $trClass ?>">
                            <td class="ps-4">
                                <div class="exam-name"><?= htmlspecialchars($examen['nom']) ?></div>
                                <?php if ($examen['a_jeun_requis']): ?>
                                    <div class="exam-sub"><i class="bi bi-clock-history text-warning"></i> À jeun requis</div>
                                <?php endif; ?>
                                <?php if ($hasResult): ?>
                                    <div class="exam-sub" style="color:#15803d;font-weight:700;">
                                        <i class="bi bi-check-circle-fill me-1"></i>Résultat : <?= htmlspecialchars(substr($examen['resultat'], 0, 40)) ?><?= strlen($examen['resultat']) > 40 ? '…' : '' ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td><span class="badge-categorie"><?= htmlspecialchars($examen['categorie']) ?></span></td>
                            <td>
                                <span class="prelev-chip">
                                    <i class="bi bi-droplet-fill"></i>
                                    <?= htmlspecialchars($examen['type_prelevement']) ?>
                                </span>
                            </td>
                            <td style="font-weight:600;color:#475569;"><?= $examen['delai_rendu_heures'] ?>h</td>
                            <td>
                                <?php if ($examen['urgent']): ?>
                                    <span class="badge-urgent">⚡ URGENT</span>
                                <?php else: ?>
                                    <span class="badge-normal">✓ Normal</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <select name="statuts[<?= $examen['id'] ?>]" class="statut-select">
                                    <option value="EN_ATTENTE" <?= $statExam == 'EN_ATTENTE' ? 'selected' : '' ?>>En attente</option>
                                    <option value="ASSIGNEE"  <?= $statExam == 'ASSIGNEE'   ? 'selected' : '' ?>>Assigné</option>
                                    <option value="PRELEVE"   <?= $statExam == 'PRELEVE'    ? 'selected' : '' ?>>Prélevé</option>
                                    <option value="EN_COURS"  <?= $statExam == 'EN_COURS'   ? 'selected' : '' ?>>En cours</option>
                                    <option value="SOUMIS"    <?= $statExam == 'SOUMIS'     ? 'selected' : '' ?>>Soumis</option>
                                    <option value="VALIDE"    <?= $statExam == 'VALIDE'     ? 'selected' : '' ?>>Validé</option>
                                </select>
                            </td>
                            <td class="text-end pe-4">
                                <?php if ($statExam !== 'VALIDE'): ?>
                                <a href="<?= BASE_URL ?>laboratoire/saisie-resultats/<?= $demande['id'] ?>#exam-<?= $examen['id'] ?>" class="btn-saisir">
                                    <i class="bi bi-pencil-square"></i>
                                    <?= $hasResult ? 'Modifier' : 'Saisir résultats' ?>
                                </a>
                                <?php else: ?>
                                <span style="color:#15803d;font-weight:700;font-size:.82rem;">
                                    <i class="bi bi-check-circle-fill me-1"></i>Validé
                                </span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="form-footer">
                <div style="flex:1;min-width:280px;">
                    <div class="notes-label"><i class="bi bi-journal-text me-1"></i>Notes du technicien / Incidents</div>
                    <textarea name="notes" class="notes-textarea" rows="2"
                              placeholder="Ex: Hémolyse, Prélèvement difficile…"><?= htmlspecialchars($demande['notes'] ?? '') ?></textarea>
                </div>
                <button type="submit" class="btn-update">
                    <i class="bi bi-check2-circle me-2"></i>Mettre à jour la demande
                </button>
            </div>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
