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
   DASHBOARD SSPI — Surveillance post-interventionnelle
============================================================================ */
require_once __DIR__ . '/../layouts/header.php';
$patients_sspi = $patients_sspi ?? [];
$sorties_jour  = $sorties_jour  ?? [];
?>
<style>
/* ── Reset layout ── */
body { background:#f0f4f8; font-family:'Inter','Segoe UI',sans-serif; color:#1e293b; }
.sidebar, nav.sidebar { display:none !important; }
main,.col-md-10,.ms-sm-auto {
    margin-left:0!important; width:100%!important; max-width:100%!important;
    flex:0 0 100%!important; padding:0!important;
}

/* ── Top bar ── */
.sspi-top {
    background:#fff; padding:12px 26px;
    display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:10px;
    position:sticky; top:0; z-index:50;
    border-bottom:1px solid #e2e8f0; box-shadow:0 2px 14px rgba(0,0,0,.06);
}
.sspi-top .brand { display:flex; align-items:center; gap:11px; }
.sspi-top .brand .ico {
    width:38px; height:38px; border-radius:11px; flex-shrink:0;
    background:linear-gradient(135deg,#f59e0b,#fbbf24);
    display:flex; align-items:center; justify-content:center; color:#fff; font-size:1.05rem;
}
.sspi-top .brand .title { font-size:.98rem; font-weight:800; color:#1e293b; line-height:1.2; }
.sspi-top .brand .sub   { font-size:.69rem; color:#64748b; font-weight:500; }
.sspi-btn {
    padding:7px 14px; border-radius:9px; font-weight:600; font-size:.78rem;
    text-decoration:none; border:1.5px solid #e2e8f0; cursor:pointer;
    background:#fff; color:#475569; transition:all .15s;
    display:inline-flex; align-items:center; gap:6px;
}
.sspi-btn:hover { background:#f1f5f9; color:#1e293b; border-color:#cbd5e1; }

/* ── Wrapper ── */
.wrap { max-width:1200px; margin:0 auto; padding:24px 26px 70px; }

/* ── KPIs ── */
.kpis { display:grid; grid-template-columns:repeat(3,1fr); gap:14px; margin-bottom:26px; }
@media(max-width:640px){ .kpis { grid-template-columns:1fr 1fr; } }
.kpi {
    background:#fff; border:1px solid #e2e8f0; border-radius:16px;
    padding:16px 20px; box-shadow:0 2px 10px rgba(0,0,0,.05);
    display:flex; align-items:center; gap:14px;
}
.kpi .kpi-ico {
    width:44px; height:44px; border-radius:12px; flex-shrink:0;
    display:flex; align-items:center; justify-content:center; font-size:1.2rem;
}
.kpi .kpi-ico.amber  { background:#fef3c7; color:#d97706; }
.kpi .kpi-ico.green  { background:#f0fdf4; color:#16a34a; }
.kpi .kpi-ico.slate  { background:#f1f5f9; color:#64748b; }
.kpi .v { font-size:1.75rem; font-weight:900; color:#1e293b; line-height:1; }
.kpi .l { font-size:.68rem; text-transform:uppercase; font-weight:700; color:#94a3b8; letter-spacing:.6px; margin-top:3px; }

/* ── Section title ── */
.sec-title {
    font-size:.74rem; font-weight:800; text-transform:uppercase; letter-spacing:1px;
    color:#64748b; margin:0 0 14px; display:flex; align-items:center; gap:8px;
}
.sec-title::after { content:''; flex:1; height:1px; background:#e2e8f0; }

/* ── Patient grid ── */
.pat-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(320px,1fr)); gap:16px; margin-bottom:32px; }

.pat-card {
    background:#fff; border:1px solid #e2e8f0; border-left:5px solid #f59e0b;
    border-radius:16px; padding:18px 20px;
    box-shadow:0 2px 12px rgba(0,0,0,.06);
    transition:transform .15s, box-shadow .15s;
}
.pat-card:hover { transform:translateY(-2px); box-shadow:0 8px 24px rgba(0,0,0,.10); }
.pat-card.alerte { border-left-color:#ef4444; background:#fffafa; }

.pat-nom { font-weight:800; font-size:.98rem; color:#1e293b; }
.pat-meta { font-size:.75rem; color:#64748b; margin-top:3px; }

.pat-row {
    display:flex; align-items:center; justify-content:space-between;
    margin-top:12px; gap:8px; flex-wrap:wrap;
}

/* Aldrete badge */
.ald-badge {
    font-size:.82rem; font-weight:800; padding:5px 13px; border-radius:20px;
    display:inline-flex; align-items:center; gap:5px;
}
.ald-ok   { background:#f0fdf4; color:#15803d; border:1.5px solid #86efac; }
.ald-mid  { background:#fffbeb; color:#b45309; border:1.5px solid #fde68a; }
.ald-bad  { background:#fef2f2; color:#b91c1c; border:1.5px solid #fecaca; }
.ald-none { background:#f8fafc; color:#94a3b8; border:1.5px dashed #cbd5e1; }

.duree {
    font-size:.74rem; font-weight:700; color:#64748b;
    display:flex; align-items:center; gap:5px;
}
.duree.long { color:#dc2626; }

/* Action buttons row */
.pat-actions { display:flex; gap:8px; margin-top:14px; }
.btn-surveiller {
    flex:1; display:inline-flex; align-items:center; justify-content:center; gap:7px;
    background:linear-gradient(135deg,#6366f1,#818cf8); color:#fff;
    font-weight:700; font-size:.78rem; padding:9px 14px;
    border-radius:10px; text-decoration:none; transition:opacity .15s;
}
.btn-surveiller:hover { opacity:.88; color:#fff; }
.btn-dossier {
    display:inline-flex; align-items:center; gap:6px;
    padding:9px 14px; border-radius:10px; text-decoration:none;
    background:#fff; border:1.5px solid #e2e8f0; color:#475569;
    font-weight:700; font-size:.78rem; white-space:nowrap; flex-shrink:0;
    transition:all .15s;
}
.btn-dossier:hover { background:#f1f5f9; border-color:#cbd5e1; color:#1e293b; }

/* ── Empty state ── */
.empty {
    text-align:center; padding:52px 20px; background:#fff;
    border:1.5px dashed #e2e8f0; border-radius:18px; color:#94a3b8; margin-bottom:32px;
}
.empty i { font-size:2.2rem; display:block; margin-bottom:10px; color:#cbd5e1; }
.empty strong { display:block; color:#64748b; font-size:.9rem; }

/* ── History table ── */
.hist-table {
    width:100%; border-collapse:collapse; background:#fff;
    border-radius:16px; overflow:hidden; box-shadow:0 2px 10px rgba(0,0,0,.05);
}
.hist-table th {
    font-size:.65rem; color:#94a3b8; text-transform:uppercase; letter-spacing:.5px;
    text-align:left; padding:10px 16px; background:#f8fafc; border-bottom:2px solid #f1f5f9;
}
.hist-table td { padding:10px 16px; border-bottom:1px solid #f1f5f9; font-size:.84rem; color:#374151; }
.hist-table tr:last-child td { border-bottom:none; }
.hist-table tr:hover td { background:#fafafa; }
</style>

<!-- TOP BAR -->
<div class="sspi-top">
    <div class="brand">
        <div class="ico"><i class="bi bi-heart-pulse-fill"></i></div>
        <div>
            <div class="title">SSPI — Surveillance Post-Interventionnelle</div>
            <div class="sub">Salle de réveil · <?= count($patients_sspi) ?> patient(s) en surveillance</div>
        </div>
    </div>
    <div style="display:flex;gap:8px;">
        <a href="<?= BASE_URL ?>bloc" class="sspi-btn"><i class="bi bi-grid-1x2-fill"></i>Bloc opératoire</a>
        <a href="<?= BASE_URL ?>dashboard" class="sspi-btn"><i class="bi bi-arrow-left"></i>Retour</a>
    </div>
</div>

<div class="wrap">

    <?php if (isset($_GET['success']) && $_GET['success'] === 'sortie'): ?>
    <div style="background:#f0fdf4;border:1.5px solid #86efac;border-radius:14px;
                padding:14px 20px;margin-bottom:22px;display:flex;align-items:center;gap:12px;">
        <i class="bi bi-check-circle-fill" style="color:#16a34a;font-size:1.3rem;flex-shrink:0;"></i>
        <span><strong style="color:#15803d;">Sortie de SSPI enregistrée</strong>
        <span style="color:#166534;"> — le patient a été orienté.</span></span>
    </div>
    <?php endif; ?>

    <!-- KPIs -->
    <div class="kpis">
        <div class="kpi">
            <div class="kpi-ico amber"><i class="bi bi-activity"></i></div>
            <div>
                <div class="v"><?= count($patients_sspi) ?></div>
                <div class="l">En surveillance</div>
            </div>
        </div>
        <div class="kpi">
            <div class="kpi-ico green"><i class="bi bi-check-circle"></i></div>
            <div>
                <div class="v"><?= count(array_filter($patients_sspi, fn($p) => ($p['dernier_aldrete'] ?? null) !== null && $p['dernier_aldrete'] >= 9)) ?></div>
                <div class="l">Sortie possible (Aldrete ≥ 9)</div>
            </div>
        </div>
        <div class="kpi">
            <div class="kpi-ico slate"><i class="bi bi-box-arrow-right"></i></div>
            <div>
                <div class="v"><?= count($sorties_jour) ?></div>
                <div class="l">Sorties aujourd'hui</div>
            </div>
        </div>
    </div>

    <!-- Patients en SSPI -->
    <div class="sec-title"><i class="bi bi-activity"></i>Patients en salle de réveil</div>

    <?php if (empty($patients_sspi)): ?>
    <div class="empty">
        <i class="bi bi-moon-stars"></i>
        <strong>Aucun patient en SSPI actuellement</strong>
        <span style="font-size:.8rem;margin-top:6px;display:block;">
            Les patients apparaissent ici dès la fin de leur intervention (enregistrement du CRO).
        </span>
    </div>
    <?php else: ?>
    <div class="pat-grid">
        <?php foreach ($patients_sspi as $p):
            $ald    = $p['dernier_aldrete'];
            $min    = (int)($p['minutes_sspi'] ?? 0);
            $duree  = $min >= 60 ? floor($min / 60) . 'h' . str_pad($min % 60, 2, '0', STR_PAD_LEFT) : $min . ' min';
            $alerte = ($min > 120) || ($ald !== null && $ald < 6);
            if ($ald === null)    { $aldCls = 'ald-none'; $aldTxt = 'Aucun relevé'; $aldIco = 'bi-dash-circle'; }
            elseif ($ald >= 9)   { $aldCls = 'ald-ok';   $aldTxt = "Aldrete $ald/10"; $aldIco = 'bi-check-circle-fill'; }
            elseif ($ald >= 6)   { $aldCls = 'ald-mid';  $aldTxt = "Aldrete $ald/10"; $aldIco = 'bi-exclamation-circle-fill'; }
            else                 { $aldCls = 'ald-bad';  $aldTxt = "Aldrete $ald/10"; $aldIco = 'bi-exclamation-triangle-fill'; }
        ?>
        <div class="pat-card <?= $alerte ? 'alerte' : '' ?>">
            <div class="pat-nom">
                <?= htmlspecialchars(strtoupper($p['nom'] ?? '') . ' ' . ($p['prenom'] ?? '')) ?>
                <?php if ($alerte): ?>
                <i class="bi bi-exclamation-triangle-fill text-danger ms-1"
                   title="Surveillance prolongée ou score bas" style="font-size:.85rem;"></i>
                <?php endif; ?>
            </div>
            <div class="pat-meta">
                <?= htmlspecialchars($p['dossier_numero'] ?? '') ?>
                · <?= htmlspecialchars($p['type_intervention'] ?: ($p['diagnostique_op'] ?? 'Intervention')) ?>
                <?php if (!empty($p['chirurgien_nom'])): ?> · Dr <?= htmlspecialchars($p['chirurgien_nom']) ?><?php endif; ?>
            </div>
            <div class="pat-row">
                <span class="ald-badge <?= $aldCls ?>">
                    <i class="bi <?= $aldIco ?>"></i><?= $aldTxt ?>
                </span>
                <span class="duree <?= $min > 120 ? 'long' : '' ?>">
                    <i class="bi bi-stopwatch"></i> <?= $duree ?> · <?= (int)$p['nb_releves'] ?> relevé(s)
                </span>
            </div>
            <div class="pat-actions">
                <a href="<?= BASE_URL ?>bloc/sspi/<?= (int)$p['prog_id'] ?>" class="btn-surveiller">
                    <i class="bi bi-clipboard2-pulse-fill"></i> Surveiller / Score d'Aldrete
                </a>
                <a href="<?= BASE_URL ?>patients/dossier/<?= (int)$p['patient_id'] ?>"
                   class="btn-dossier" title="Ouvrir le dossier patient">
                    <i class="bi bi-folder2-open"></i> Dossier
                </a>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Sorties du jour -->
    <?php if (!empty($sorties_jour)): ?>
    <div class="sec-title" style="margin-top:10px;"><i class="bi bi-box-arrow-right"></i>Sorties de SSPI aujourd'hui</div>
    <table class="hist-table">
        <thead>
            <tr><th>Patient</th><th>Dossier</th><th>Aldrete à la sortie</th><th>Heure</th></tr>
        </thead>
        <tbody>
            <?php foreach ($sorties_jour as $s): ?>
            <tr>
                <td><strong><?= htmlspecialchars(strtoupper($s['nom'] ?? '') . ' ' . ($s['prenom'] ?? '')) ?></strong></td>
                <td><?= htmlspecialchars($s['dossier_numero'] ?? '') ?></td>
                <td>
                    <?= $s['aldrete_sortie'] !== null
                        ? '<strong style="color:#15803d;">' . (int)$s['aldrete_sortie'] . '/10</strong>'
                        : '—' ?>
                </td>
                <td><?= $s['heure_sortie'] ? date('H:i', strtotime($s['heure_sortie'])) : '—' ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>

</div>

<script>
/* Rafraîchissement auto toutes les 60 s */
setTimeout(() => location.reload(), 60000);
</script>
