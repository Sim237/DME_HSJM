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
   SUIVI DES GAVAGES — Néonatologie
   Formulaire de saisie + historique horodaté avec auteur de chaque saisie
============================================================================ */
require_once __DIR__ . '/../layouts/header.php';
$patient       = $patient       ?? [];
$age           = $age           ?? '—';
$gavages       = $gavages       ?? [];
$dernier_poids = $dernier_poids ?? null;
$total_jour    = $total_jour    ?? ['lm' => 0, 'la' => 0, 'nb' => 0];
?>
<style>
    .sidebar, nav.sidebar { display:none !important; }
    main, .col-md-10, .ms-sm-auto { margin-left:0 !important; width:100% !important; flex:0 0 100% !important; max-width:100% !important; padding:0 !important; }
    body { background:#f0f7f9; font-family:'Segoe UI',system-ui,sans-serif; }

    .gav-topbar { background:linear-gradient(135deg,#0e7490,#06b6d4); padding:14px 28px; display:flex; align-items:center; justify-content:space-between; position:sticky; top:0; z-index:100; box-shadow:0 4px 18px rgba(6,182,212,.3); flex-wrap:wrap; gap:8px; }
    .gav-topbar .t { font-size:1.05rem; font-weight:800; color:#fff; display:flex; align-items:center; gap:9px; }
    .gav-tb-btn { display:inline-flex; align-items:center; gap:6px; padding:8px 16px; border-radius:10px; font-weight:700; font-size:.8rem; text-decoration:none; border:none; cursor:pointer; background:rgba(255,255,255,.2); color:#fff; border:1px solid rgba(255,255,255,.35); }
    .gav-tb-btn:hover { background:rgba(255,255,255,.35); color:#fff; }

    .gav-wrap { max-width:1100px; margin:0 auto; padding:22px 26px 60px; }

    .pat-card { background:linear-gradient(135deg,#06b6d4,#22d3ee); color:#fff; border-radius:16px; padding:14px 20px; margin-bottom:18px; display:flex; align-items:center; gap:14px; box-shadow:0 4px 18px rgba(6,182,212,.25); flex-wrap:wrap; }
    .pat-name { font-size:1.1rem; font-weight:800; }
    .pat-meta { font-size:.78rem; opacity:.88; }
    .pat-totaux { margin-left:auto; display:flex; gap:10px; flex-wrap:wrap; }
    .tot-chip { background:rgba(255,255,255,.22); border-radius:11px; padding:7px 14px; text-align:center; }
    .tot-chip .v { font-size:1.05rem; font-weight:900; line-height:1; }
    .tot-chip .l { font-size:.6rem; font-weight:700; text-transform:uppercase; opacity:.85; }

    .gav-section { background:#fff; border-radius:16px; box-shadow:0 2px 12px rgba(6,182,212,.06); border:1px solid #e0f0f3; margin-bottom:18px; overflow:hidden; }
    .gav-sec-head { padding:13px 20px; font-weight:800; font-size:.82rem; text-transform:uppercase; letter-spacing:.5px; color:#0e7490; background:#f0fdff; border-bottom:2px solid #cffafe; display:flex; align-items:center; gap:8px; }
    .gav-sec-body { padding:18px 20px; }

    .gav-label { font-size:.7rem; font-weight:800; color:#64748b; text-transform:uppercase; letter-spacing:.4px; margin-bottom:5px; display:block; }
    .gav-input { width:100%; padding:9px 12px; border:1.5px solid #e2e8f0; border-radius:10px; font-size:.88rem; color:#1e293b; outline:none; background:#fff; transition:border .15s; }
    .gav-input:focus { border-color:#06b6d4; box-shadow:0 0 0 3px rgba(6,182,212,.12); }
    .gav-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:14px; }
    @media(max-width:820px){ .gav-grid { grid-template-columns:1fr 1fr; } }

    .gav-bloc-lait { background:#fffbeb; border:1.5px solid #fde68a; border-radius:12px; padding:14px 16px; margin-top:14px; }
    .gav-bloc-lait .ttl { font-size:.72rem; font-weight:800; text-transform:uppercase; color:#b45309; margin-bottom:10px; }
    .gav-lait-grid { display:grid; grid-template-columns:1fr 1fr 1fr; gap:14px; align-items:end; }
    @media(max-width:680px){ .gav-lait-grid { grid-template-columns:1fr; } }
    .gav-total-box { background:#fff; border:1.5px dashed #fbbf24; border-radius:10px; padding:9px 12px; text-align:center; }
    .gav-total-box .n { font-size:1.2rem; font-weight:900; color:#b45309; }
    .gav-total-box .l { font-size:.62rem; font-weight:700; text-transform:uppercase; color:#92400e; }

    .gav-suffix { position:relative; }
    .gav-suffix .u { position:absolute; right:12px; top:50%; transform:translateY(-50%); font-size:.7rem; color:#94a3b8; font-weight:700; pointer-events:none; }
    .gav-suffix .gav-input { padding-right:40px; }

    .btn-gav-save { background:linear-gradient(135deg,#0e7490,#06b6d4); color:#fff; border:none; border-radius:11px; padding:12px 28px; font-weight:800; font-size:.88rem; cursor:pointer; margin-top:16px; display:inline-flex; align-items:center; gap:8px; box-shadow:0 4px 14px rgba(6,182,212,.3); }
    .btn-gav-save:hover { opacity:.92; transform:translateY(-1px); }

    .gav-table { width:100%; border-collapse:collapse; }
    .gav-table th { font-size:.66rem; color:#94a3b8; text-transform:uppercase; letter-spacing:.5px; text-align:left; padding:9px 12px; background:#f8fafc; border-bottom:2px solid #e8edf5; white-space:nowrap; }
    .gav-table td { padding:9px 12px; border-bottom:1px solid #f1f5f9; font-size:.84rem; color:#334155; vertical-align:middle; }
    .gav-table tr:hover td { background:#f8fafc; }
    .gav-date-sep td { background:#f0fdff !important; font-weight:800; font-size:.74rem; color:#0e7490; text-transform:uppercase; letter-spacing:.5px; padding:7px 12px; }
    .lait-badge { display:inline-block; font-size:.74rem; font-weight:800; padding:2px 9px; border-radius:20px; }
    .lait-lm { background:#fce7f3; color:#9d174d; }
    .lait-la { background:#e0f2fe; color:#075985; }
    .lait-tot { background:#fef3c7; color:#92400e; }
    .user-chip { display:inline-flex; align-items:center; gap:6px; background:#f1f5f9; border-radius:20px; padding:3px 10px 3px 4px; font-size:.72rem; font-weight:700; color:#475569; white-space:nowrap; }
    .user-chip .av { width:20px; height:20px; border-radius:50%; background:linear-gradient(135deg,#06b6d4,#3b82f6); color:#fff; font-size:.58rem; font-weight:900; display:flex; align-items:center; justify-content:center; }
    .gav-empty { text-align:center; padding:36px 20px; color:#94a3b8; }
    .gav-empty i { font-size:2.2rem; display:block; margin-bottom:8px; color:#cbd5e1; }
</style>

<main>
<div class="gav-topbar">
    <div class="t"><i class="bi bi-droplet-fill"></i> Fiche de Gavage</div>
    <div style="display:flex;gap:8px;">
        <a href="<?= BASE_URL ?>neonatologie/examen/<?= (int)($patient['id'] ?? 0) ?>" class="gav-tb-btn"><i class="bi bi-clipboard2-plus"></i> Examen néonatal</a>
        <a href="<?= BASE_URL ?>neonatologie" class="gav-tb-btn"><i class="bi bi-arrow-left"></i> Néonatologie</a>
    </div>
</div>

<div class="gav-wrap">

    <!-- Bandeau patient + totaux du jour -->
    <div class="pat-card">
        <div>
            <div class="pat-name"><?= htmlspecialchars(strtoupper($patient['nom'] ?? '') . ' ' . ($patient['prenom'] ?? '')) ?></div>
            <div class="pat-meta">
                N° <?= htmlspecialchars($patient['dossier_numero'] ?? '—') ?>
                &bull; Âge : <?= htmlspecialchars($age) ?>
                &bull; <?= strtoupper($patient['sexe'] ?? '') === 'F' ? 'Fille' : 'Garçon' ?>
            </div>
        </div>
        <div class="pat-totaux">
            <div class="tot-chip"><div class="v"><?= (int)$total_jour['nb'] ?></div><div class="l">Gavages auj.</div></div>
            <div class="tot-chip"><div class="v"><?= rtrim(rtrim(number_format($total_jour['lm'], 1, '.', ''), '0'), '.') ?></div><div class="l">LM ml auj.</div></div>
            <div class="tot-chip"><div class="v"><?= rtrim(rtrim(number_format($total_jour['la'], 1, '.', ''), '0'), '.') ?></div><div class="l">LA ml auj.</div></div>
            <div class="tot-chip"><div class="v"><?= rtrim(rtrim(number_format($total_jour['lm'] + $total_jour['la'], 1, '.', ''), '0'), '.') ?></div><div class="l">Total ml auj.</div></div>
        </div>
    </div>

    <?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success rounded-4 border-0 shadow-sm">
        <i class="bi bi-check-circle-fill me-2"></i>Gavage enregistré.
    </div>
    <?php elseif (isset($_GET['error'])): ?>
    <div class="alert alert-danger rounded-4 border-0 shadow-sm">
        <i class="bi bi-x-circle-fill me-2"></i>Erreur lors de l'enregistrement. Veuillez réessayer.
    </div>
    <?php endif; ?>

    <!-- Formulaire de saisie -->
    <div class="gav-section">
        <div class="gav-sec-head"><i class="bi bi-plus-circle"></i> Nouveau gavage</div>
        <div class="gav-sec-body">
            <form method="POST" action="<?= BASE_URL ?>neonatologie/gavage-sauvegarder">
                <input type="hidden" name="patient_id" value="<?= (int)($patient['id'] ?? 0) ?>">
                <div class="gav-grid">
                    <div>
                        <label class="gav-label">Date</label>
                        <input type="date" name="date_gavage" class="gav-input" value="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div>
                        <label class="gav-label">Heure</label>
                        <input type="time" name="heure_gavage" class="gav-input" value="<?= date('H:i') ?>" required>
                    </div>
                    <div>
                        <label class="gav-label">Poids du jour</label>
                        <div class="gav-suffix">
                            <input type="number" name="poids_jour" class="gav-input" step="0.001" min="0" max="15"
                                   placeholder="Ex : 2.350" value="<?= $dernier_poids !== null ? htmlspecialchars($dernier_poids) : '' ?>">
                            <span class="u">kg</span>
                        </div>
                    </div>
                    <div>
                        <label class="gav-label">Résidus</label>
                        <input type="text" name="residus" class="gav-input" placeholder="Ex : 2 cc clairs, bilieux…">
                    </div>
                </div>

                <!-- Gavage : lait maternel + lait artificiel -->
                <div class="gav-bloc-lait">
                    <div class="ttl"><i class="bi bi-droplet-half me-1"></i>Gavage — quantités administrées</div>
                    <div class="gav-lait-grid">
                        <div>
                            <label class="gav-label" style="color:#9d174d;">Lait maternel</label>
                            <div class="gav-suffix">
                                <input type="number" name="lait_maternel_ml" id="gavLM" class="gav-input" step="0.5" min="0" placeholder="0" oninput="majTotalGavage()">
                                <span class="u">ml</span>
                            </div>
                        </div>
                        <div>
                            <label class="gav-label" style="color:#075985;">Lait artificiel</label>
                            <div class="gav-suffix">
                                <input type="number" name="lait_artificiel_ml" id="gavLA" class="gav-input" step="0.5" min="0" placeholder="0" oninput="majTotalGavage()">
                                <span class="u">ml</span>
                            </div>
                        </div>
                        <div class="gav-total-box">
                            <div class="n" id="gavTotal">0</div>
                            <div class="l">Total ml</div>
                        </div>
                    </div>
                </div>

                <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;">
                    <button type="submit" class="btn-gav-save"><i class="bi bi-check-circle-fill"></i> Enregistrer le gavage</button>
                    <small class="text-muted" style="font-size:.74rem;">
                        <i class="bi bi-person-check me-1"></i>
                        Saisie enregistrée au nom de
                        <strong><?= htmlspecialchars(trim(($_SESSION['user_prenom'] ?? '') . ' ' . ($_SESSION['user_nom'] ?? ''))) ?></strong>
                    </small>
                </div>
            </form>
        </div>
    </div>

    <!-- Historique -->
    <div class="gav-section">
        <div class="gav-sec-head">
            <i class="bi bi-clock-history"></i> Historique des gavages
            <span style="margin-left:auto;font-weight:600;font-size:.72rem;color:#94a3b8;text-transform:none;letter-spacing:0;"><?= count($gavages) ?> saisie(s)</span>
        </div>
        <?php if (empty($gavages)): ?>
        <div class="gav-empty">
            <i class="bi bi-droplet"></i>
            Aucun gavage enregistré pour ce nouveau-né.
        </div>
        <?php else: ?>
        <div style="overflow-x:auto;">
            <table class="gav-table">
                <thead>
                    <tr>
                        <th>Heure</th>
                        <th>Poids du jour</th>
                        <th>Résidus</th>
                        <th>Lait maternel</th>
                        <th>Lait artificiel</th>
                        <th>Total</th>
                        <th>Saisi par</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $dateCourante = null;
                    foreach ($gavages as $g):
                        // Séparateur par jour
                        if ($g['date_gavage'] !== $dateCourante):
                            $dateCourante = $g['date_gavage'];
                            $estAuj = ($dateCourante === date('Y-m-d'));
                    ?>
                    <tr class="gav-date-sep">
                        <td colspan="7">
                            <i class="bi bi-calendar3 me-1"></i>
                            <?= $estAuj ? "Aujourd'hui — " : '' ?><?= date('d/m/Y', strtotime($dateCourante)) ?>
                        </td>
                    </tr>
                    <?php endif;
                        $lm  = $g['lait_maternel_ml']   !== null ? (float)$g['lait_maternel_ml']   : null;
                        $la  = $g['lait_artificiel_ml'] !== null ? (float)$g['lait_artificiel_ml'] : null;
                        $tot = ($lm ?? 0) + ($la ?? 0);
                        $fmt = fn($v) => rtrim(rtrim(number_format($v, 1, '.', ''), '0'), '.');
                        $uInit = strtoupper(substr($g['user_prenom'] ?? '', 0, 1) . substr($g['user_nom'] ?? '', 0, 1)) ?: '?';
                        $uNom  = trim(($g['user_prenom'] ?? '') . ' ' . ($g['user_nom'] ?? ''));
                    ?>
                    <tr>
                        <td><strong><?= date('H:i', strtotime($g['heure_gavage'])) ?></strong></td>
                        <td><?= $g['poids_jour'] !== null ? '<strong>' . $fmt((float)$g['poids_jour']) . '</strong> kg' : '<span class="text-muted">—</span>' ?></td>
                        <td><?= $g['residus'] !== null && $g['residus'] !== '' ? htmlspecialchars($g['residus']) : '<span class="text-muted">—</span>' ?></td>
                        <td><?= $lm !== null ? '<span class="lait-badge lait-lm">' . $fmt($lm) . ' ml</span>' : '<span class="text-muted">—</span>' ?></td>
                        <td><?= $la !== null ? '<span class="lait-badge lait-la">' . $fmt($la) . ' ml</span>' : '<span class="text-muted">—</span>' ?></td>
                        <td><span class="lait-badge lait-tot"><?= $fmt($tot) ?> ml</span></td>
                        <td>
                            <span class="user-chip" title="<?= htmlspecialchars($uNom) ?>">
                                <span class="av"><?= htmlspecialchars($uInit) ?></span>
                                <?= htmlspecialchars($uNom ?: 'Inconnu') ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

</div>
</main>

<script>
function majTotalGavage() {
    const lm = parseFloat(document.getElementById('gavLM').value) || 0;
    const la = parseFloat(document.getElementById('gavLA').value) || 0;
    const t  = lm + la;
    document.getElementById('gavTotal').textContent = (t % 1 === 0) ? t : t.toFixed(1);
}
</script>
