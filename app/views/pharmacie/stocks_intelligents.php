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
$csrfToken = CsrfService::getToken();

function niveauBadge(string $n): string {
    return match ($n) {
        'CRITIQUE' => '<span style="background:#fee2e2;color:#dc2626;border:1px solid #fca5a5;padding:3px 10px;border-radius:20px;font-size:.7rem;font-weight:700">● CRITIQUE</span>',
        'DANGER'   => '<span style="background:#fff7ed;color:#d97706;border:1px solid #fde68a;padding:3px 10px;border-radius:20px;font-size:.7rem;font-weight:700">● DANGER</span>',
        'WARNING'  => '<span style="background:#fefce8;color:#ca8a04;border:1px solid #fef08a;padding:3px 10px;border-radius:20px;font-size:.7rem;font-weight:700">● ATTENTION</span>',
        default    => '<span style="background:#dbeafe;color:#1d4ed8;border:1px solid #93c5fd;padding:3px 10px;border-radius:20px;font-size:.7rem;font-weight:700">● INFO</span>',
    };
}

function joursBar(?float $j): string {
    if ($j === null) return '<span style="color:#9ca3af;font-size:.8rem">N/A</span>';
    $j = (float)$j;
    $pct = min(100, max(2, ($j / 30) * 100));
    if ($j <= 3)       $col = '#dc2626';
    elseif ($j <= 7)   $col = '#d97706';
    elseif ($j <= 14)  $col = '#ca8a04';
    else               $col = '#16a34a';
    return '<div style="display:flex;align-items:center;gap:8px">
        <div style="flex:1;height:5px;background:#f3f4f6;border-radius:3px;min-width:60px">
          <div style="width:'.round($pct).'%;height:5px;background:'.$col.';border-radius:3px"></div>
        </div>
        <span style="font-size:.78rem;font-weight:800;color:'.$col.';min-width:36px;text-align:right">'.ceil($j).'j</span>
    </div>';
}

$totalMontantCommande = 0;
foreach (($topRisques ?? []) as $r) $totalMontantCommande += (float)($r['montant_estime'] ?? 0);

$ls = $stats['last_sync']   ?? null;
$sc = $stats['sage_config'] ?? null;
?>
<style>
:root {
    --red:#dc2626;--red-dark:#b91c1c;--red-soft:#fee2e2;--red-mid:#fca5a5;
    --blue:#1d4ed8;--blue-soft:#dbeafe;--blue-mid:#93c5fd;
    --white:#ffffff;--bg:#fafafa;--border:#f0f0f0;--text:#1a1a2e;--muted:#6b7280;
}
.sidebar{display:none!important}
main,.col-md-10,.ms-sm-auto{margin-left:0!important;width:100%!important;flex:0 0 100%!important;max-width:100%!important;padding:0!important}
body{background:var(--bg);font-family:'Segoe UI',system-ui,sans-serif;color:var(--text)}

/* ── TOPBAR ── */
.ph-topbar{background:linear-gradient(135deg,var(--red-dark) 0%,var(--red) 60%,#ef4444 100%);padding:14px 28px;display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;z-index:100;box-shadow:0 4px 20px rgba(220,38,38,.35)}
.ph-logo-text{font-size:1.2rem;font-weight:900;color:#fff;letter-spacing:-.3px}
.ph-logo-text span{color:#fde68a}
.ph-subtitle{font-size:.68rem;color:rgba(255,255,255,.65);font-weight:600;text-transform:uppercase;letter-spacing:1.2px;margin-top:2px}
.ph-btn{display:inline-flex;align-items:center;gap:6px;padding:8px 18px;border-radius:10px;font-weight:700;font-size:.78rem;text-decoration:none;border:none;cursor:pointer;transition:all .18s}
.ph-btn-outline{background:rgba(255,255,255,.18);color:#fff;border:1px solid rgba(255,255,255,.35)}
.ph-btn-outline:hover{background:rgba(255,255,255,.3);color:#fff}
.ph-btn-primary{background:#fff;color:var(--red)}
.ph-btn-primary:hover{background:#fff5f5;color:var(--red-dark)}
.ph-btn-success{background:#16a34a;color:#fff}
.ph-btn-success:hover{background:#15803d;color:#fff}
.ph-btn-danger{background:rgba(0,0,0,.2);color:#fca5a5;border:1px solid rgba(255,255,255,.2)}

/* ── MAIN ── */
.ph-main{padding:24px 28px}

/* ── KPI ── */
.kpi-grid{display:grid;grid-template-columns:repeat(5,1fr);gap:16px;margin-bottom:20px}
@media(max-width:1100px){.kpi-grid{grid-template-columns:repeat(3,1fr)}}
.kpi-card{background:var(--white);border-radius:18px;padding:22px 24px;border:1px solid var(--border);position:relative;overflow:hidden;box-shadow:0 2px 12px rgba(0,0,0,.06);transition:transform .2s,box-shadow .2s}
.kpi-card:hover{transform:translateY(-2px);box-shadow:0 8px 24px rgba(0,0,0,.1)}
.kpi-card::after{content:'';position:absolute;top:0;left:0;bottom:0;width:4px;border-radius:18px 0 0 18px}
.kpi-card.kpi-red::after{background:var(--red)}
.kpi-card.kpi-blue::after{background:var(--blue)}
.kpi-card.kpi-green::after{background:#16a34a}
.kpi-card.kpi-amber::after{background:#d97706}
.kpi-card.kpi-purple::after{background:#7c3aed}
.kpi-value{font-size:2.2rem;font-weight:900;line-height:1;margin-bottom:5px}
.kpi-red .kpi-value{color:var(--red)}
.kpi-blue .kpi-value{color:var(--blue)}
.kpi-green .kpi-value{color:#16a34a}
.kpi-amber .kpi-value{color:#d97706}
.kpi-purple .kpi-value{color:#7c3aed}
.kpi-label{font-size:.7rem;color:var(--muted);font-weight:700;text-transform:uppercase;letter-spacing:.9px}
.kpi-sub{font-size:.68rem;color:#9ca3af;margin-top:2px}
.kpi-icon{position:absolute;right:16px;top:50%;transform:translateY(-50%);font-size:2.6rem;opacity:.07}

/* ── PANELS ── */
.ph-grid{display:grid;grid-template-columns:1fr 340px;gap:20px}
@media(max-width:900px){.ph-grid{grid-template-columns:1fr}}
.ph-panel{background:var(--white);border-radius:20px;border:1px solid var(--border);overflow:hidden;box-shadow:0 2px 12px rgba(0,0,0,.05)}
.ph-panel-header{padding:15px 22px;border-bottom:2px solid var(--red-soft);display:flex;align-items:center;justify-content:space-between;background:linear-gradient(90deg,#fff5f5 0%,#fff 100%)}
.ph-panel-title{font-size:.78rem;font-weight:800;color:var(--red);text-transform:uppercase;letter-spacing:.8px;display:flex;align-items:center;gap:7px}

/* ── TABLE ── */
.ph-table{width:100%;border-collapse:collapse}
.ph-table thead th{background:#fff5f5;color:var(--red);font-size:.68rem;font-weight:800;text-transform:uppercase;letter-spacing:1px;padding:11px 16px;border-bottom:1px solid var(--red-soft)}
.ph-table tbody tr{border-bottom:1px solid #fafafa;transition:background .12s}
.ph-table tbody tr:hover{background:#fff5f5}
.ph-table tbody td{padding:12px 16px;color:#374151;font-size:.85rem}

/* ── ALERTES ── */
.alert-item{padding:12px 18px;border-bottom:1px solid #fafafa;display:flex;align-items:flex-start;gap:10px;transition:background .12s}
.alert-item:hover{background:#fff5f5}
.alert-item:last-child{border-bottom:none}
.ai-crit{border-left:3px solid var(--red)}
.ai-dang{border-left:3px solid #d97706}
.ai-warn{border-left:3px solid #ca8a04}
.ai-info{border-left:3px solid var(--blue)}
.alert-med-name{font-size:.85rem;font-weight:700;color:var(--text)}
.alert-med-info{font-size:.7rem;color:#9ca3af;margin-top:2px}

/* ── BARRE SAGE ── */
.sage-bar{background:#fffbeb;border:1px solid #fde68a;border-radius:14px;padding:12px 20px;margin-bottom:20px;display:flex;align-items:center;gap:12px;flex-wrap:wrap}
.sage-ok{background:#f0fdf4;border-color:#86efac}

/* ── FOOTER ── */
.ph-panel-footer{padding:13px 20px;border-top:1px solid var(--red-soft);text-align:center;background:#fff5f5}
.ph-panel-footer a{color:var(--red);font-size:.8rem;font-weight:700;text-decoration:none}

/* ── EMPTY ── */
.empty-state{padding:48px 20px;text-align:center;color:#9ca3af}
.empty-state i{font-size:2.2rem;opacity:.2;margin-bottom:10px;display:block}
.empty-state span{font-size:.83rem;font-weight:600}

/* ── MINI-CHART ── */
.mini-chart{display:flex;align-items:flex-end;gap:3px;height:36px;padding:0 18px 12px}
.mini-chart-bar{flex:1;border-radius:3px 3px 0 0;background:var(--red-soft);min-height:4px;transition:background .2s}
.mini-chart-bar:hover{background:var(--red)}

#toastCont{position:fixed;bottom:1.5rem;right:1.5rem;z-index:9999}
</style>

<!-- ═══════════════════ TOPBAR ═══════════════════ -->
<div class="ph-topbar">
    <div>
        <div class="ph-logo-text">Pharmacie <span>HSJM</span> — <span style="color:#fde68a">Stocks IA</span></div>
        <div class="ph-subtitle">Prédiction ruptures · Alertes automatiques · Intégration SAGE 100</div>
    </div>

    <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">
        <button class="ph-btn ph-btn-primary" id="btnAnalyse" onclick="lancerAnalyse()">
            <i class="bi bi-lightning-charge-fill"></i> Lancer l'analyse
        </button>
        <a href="<?= BASE_URL ?>pharmacie/stocks/export-commande" class="ph-btn ph-btn-success" id="btnExport">
            <i class="bi bi-download"></i> Bon de commande
        </a>
        <a href="<?= BASE_URL ?>pharmacie/sage" class="ph-btn ph-btn-outline">
            <i class="bi bi-arrow-repeat"></i> Sync SAGE
        </a>
        <a href="<?= BASE_URL ?>pharmacie" class="ph-btn ph-btn-outline">
            <i class="bi bi-arrow-left"></i> Retour
        </a>
        <a href="<?= BASE_URL ?>logout" class="ph-btn ph-btn-danger"><i class="bi bi-power"></i></a>
    </div>
</div>

<!-- ═══════════════════ MAIN ═══════════════════ -->
<div class="ph-main">

    <!-- ── KPI ── -->
    <div class="kpi-grid">
        <div class="kpi-card kpi-blue">
            <div class="kpi-value"><?= number_format((int)($stats['total_meds'] ?? 0)) ?></div>
            <div class="kpi-label">Médicaments</div>
            <div class="kpi-sub">en stock</div>
            <i class="bi bi-capsule kpi-icon" style="color:var(--blue)"></i>
        </div>
        <div class="kpi-card kpi-red">
            <div class="kpi-value"><?= number_format((int)($stats['rupture_totale'] ?? 0)) ?></div>
            <div class="kpi-label">En Rupture</div>
            <div class="kpi-sub">stock = 0</div>
            <i class="bi bi-x-circle kpi-icon" style="color:var(--red)"></i>
        </div>
        <div class="kpi-card kpi-amber">
            <div class="kpi-value"><?= number_format((int)($stats['sous_seuil'] ?? 0)) ?></div>
            <div class="kpi-label">Sous Seuil</div>
            <div class="kpi-sub">≤ seuil alerte</div>
            <i class="bi bi-exclamation-triangle kpi-icon" style="color:#d97706"></i>
        </div>
        <div class="kpi-card kpi-red">
            <div class="kpi-value"><?= number_format((int)($stats['alertes_critique'] ?? 0) + (int)($stats['alertes_danger'] ?? 0)) ?></div>
            <div class="kpi-label">Alertes Actives</div>
            <div class="kpi-sub">critique + danger</div>
            <i class="bi bi-bell kpi-icon" style="color:var(--red)"></i>
        </div>
        <div class="kpi-card kpi-green">
            <div class="kpi-value" style="font-size:1.4rem"><?= number_format((float)($stats['valeur_stock'] ?? 0), 0, ',', ' ') ?> F</div>
            <div class="kpi-label">Valeur Stock</div>
            <div class="kpi-sub">prix unitaire × qté</div>
            <i class="bi bi-wallet2 kpi-icon" style="color:#16a34a"></i>
        </div>
    </div>

    <!-- ── BARRE SAGE ── -->
    <?php if ($sc): ?>
    <div class="sage-bar <?= ($sc['actif'] ?? 0) ? 'sage-ok' : '' ?>">
        <i class="bi bi-plug-fill" style="color:<?= ($sc['actif'] ?? 0) ? '#16a34a' : '#d97706' ?>;font-size:1.1rem"></i>
        <div style="flex:1">
            <span style="font-weight:700;font-size:.82rem;color:<?= ($sc['actif'] ?? 0) ? '#166534' : '#92400e' ?>">
                <?= ($sc['actif'] ?? 0) ? 'SAGE 100 connecté' : 'SAGE 100 inactif' ?>
            </span>
            <span style="font-size:.75rem;color:#6b7280;margin-left:10px">
                Type : <?= htmlspecialchars($sc['type_source'] ?? '—') ?>
            </span>
            <?php if ($ls): ?>
            <span style="font-size:.75rem;color:#6b7280;margin-left:10px">
                · Sync : <strong><?= date('d/m/Y H:i', strtotime($ls['created_at'])) ?></strong>
                · <?= (int)($ls['meds_stock_maj'] ?? 0) ?> méd. mis à jour
                · <span style="font-weight:700;color:<?= $ls['statut'] === 'OK' ? '#16a34a' : '#dc2626' ?>"><?= $ls['statut'] ?></span>
            </span>
            <?php endif; ?>
        </div>
        <?php if (!($sc['actif'] ?? 0)): ?>
        <a href="<?= BASE_URL ?>pharmacie/sage/config" class="ph-btn ph-btn-outline" style="background:#d97706;border-color:#d97706;font-size:.75rem;padding:6px 14px">
            <i class="bi bi-gear"></i> Configurer
        </a>
        <?php endif; ?>
    </div>
    <?php else: ?>
    <div class="sage-bar">
        <i class="bi bi-plug" style="color:#d97706;font-size:1.1rem"></i>
        <span style="font-size:.82rem;color:#92400e;flex:1">
            <strong>SAGE 100 non configuré.</strong>
            Les stocks affichés proviennent de la base DME uniquement.
        </span>
        <a href="<?= BASE_URL ?>pharmacie/sage/config" class="ph-btn ph-btn-outline" style="background:#d97706;border-color:#d97706;font-size:.75rem;padding:6px 14px">
            <i class="bi bi-gear"></i> Configurer
        </a>
    </div>
    <?php endif; ?>

    <!-- ── GRID PRINCIPAL ── -->
    <div class="ph-grid">

        <!-- TABLE TOP RISQUES -->
        <div class="ph-panel">
            <div class="ph-panel-header">
                <span class="ph-panel-title"><i class="bi bi-graph-down-arrow"></i> Top 25 — Risques de Rupture</span>
                <?php if (!empty($topRisques)): ?>
                <span style="background:var(--red-soft);color:var(--red);border:1px solid var(--red-mid);padding:3px 12px;border-radius:20px;font-size:.7rem;font-weight:800">
                    <?= count($topRisques) ?> médicaments
                </span>
                <?php endif; ?>
            </div>

            <?php if (empty($topRisques)): ?>
            <div class="empty-state">
                <i class="bi bi-check-circle-fill" style="color:#16a34a;opacity:1"></i>
                <span>Aucun médicament à risque détecté.<br>Lancez une analyse pour identifier les ruptures potentielles.</span>
            </div>
            <?php else: ?>
            <div style="overflow-x:auto">
                <table class="ph-table">
                    <thead>
                        <tr>
                            <th>Médicament</th>
                            <th style="text-align:center">Stock</th>
                            <th style="text-align:center">Conso/j</th>
                            <th style="min-width:140px">Jours restants</th>
                            <th style="text-align:center">Niveau</th>
                            <th style="text-align:center">À commander</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($topRisques as $r): ?>
                        <tr>
                            <td>
                                <div style="font-weight:700;max-width:230px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"
                                     title="<?= htmlspecialchars($r['nom'] ?? '') ?>">
                                    <?= htmlspecialchars($r['nom'] ?? '—') ?>
                                </div>
                                <?php if (!empty($r['ar_ref_sage'])): ?>
                                <div style="font-size:.68rem;color:#9ca3af;margin-top:2px"><?= htmlspecialchars($r['ar_ref_sage']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td style="text-align:center">
                                <span style="font-weight:900;font-size:1rem;color:<?= (int)$r['stock_actuel'] <= 0 ? 'var(--red)' : ((int)$r['stock_actuel'] <= (int)($r['seuil_alerte'] ?? 10) ? '#d97706' : '#16a34a') ?>">
                                    <?= number_format((int)$r['stock_actuel']) ?>
                                </span>
                            </td>
                            <td style="text-align:center;color:var(--muted);font-size:.82rem">
                                <?= (float)$r['consommation_j'] > 0 ? number_format((float)$r['consommation_j'], 2) : '<span style="color:#d1d5db">—</span>' ?>
                            </td>
                            <td><?= joursBar(isset($r['jours_restants']) && $r['jours_restants'] !== null ? (float)$r['jours_restants'] : null) ?></td>
                            <td style="text-align:center"><?= niveauBadge($r['niveau'] ?? 'INFO') ?></td>
                            <td style="text-align:center">
                                <?php if ((int)($r['quantite_commande'] ?? 0) > 0): ?>
                                <span style="background:var(--blue-soft);color:var(--blue);border:1px solid var(--blue-mid);padding:3px 10px;border-radius:20px;font-size:.75rem;font-weight:700">
                                    <?= number_format((int)$r['quantite_commande']) ?>
                                </span>
                                <?php else: ?>
                                <span style="color:#d1d5db">—</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php if ($totalMontantCommande > 0): ?>
            <div class="ph-panel-footer">
                Montant total estimé de la commande :
                <strong style="color:var(--red);margin-left:6px"><?= number_format($totalMontantCommande, 0, ',', ' ') ?> FCFA</strong>
            </div>
            <?php endif; ?>
            <?php endif; ?>
        </div>

        <!-- PANNEAU ALERTES -->
        <div class="ph-panel" style="display:flex;flex-direction:column">
            <div class="ph-panel-header">
                <span class="ph-panel-title">
                    <i class="bi bi-bell-fill"></i> Alertes Actives
                    <?php if (!empty($alertes)): ?>
                    <span style="background:var(--red);color:#fff;padding:2px 8px;border-radius:20px;font-size:.68rem"><?= count($alertes) ?></span>
                    <?php endif; ?>
                </span>
                <?php if (!empty($alertes)): ?>
                <span style="font-size:.68rem;color:var(--muted)">Cliquez ✓ pour acquitter</span>
                <?php endif; ?>
            </div>

            <div style="flex:1;overflow-y:auto;max-height:460px">
                <?php if (empty($alertes)): ?>
                <div class="empty-state">
                    <i class="bi bi-shield-check" style="color:#16a34a;opacity:1"></i>
                    <span>Aucune alerte active.<br>Lancez une analyse d'abord.</span>
                </div>
                <?php else: ?>
                <?php foreach ($alertes as $a):
                    $cls = match($a['niveau']) {
                        'CRITIQUE' => 'ai-crit',
                        'DANGER'   => 'ai-dang',
                        'WARNING'  => 'ai-warn',
                        default    => 'ai-info',
                    };
                ?>
                <div class="alert-item <?= $cls ?>" id="alerte-<?= $a['id'] ?>">
                    <div style="flex:1;min-width:0">
                        <div class="alert-med-name" style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
                            <?= htmlspecialchars($a['nom'] ?? '—') ?>
                        </div>
                        <div class="alert-med-info"><?= htmlspecialchars($a['message'] ?? '') ?></div>
                        <div style="margin-top:4px"><?= niveauBadge($a['niveau'] ?? 'INFO') ?></div>
                    </div>
                    <button onclick="acquitter(<?= $a['id'] ?>)"
                            style="background:none;border:1px solid #e5e7eb;border-radius:8px;padding:4px 10px;cursor:pointer;color:var(--muted);flex-shrink:0;transition:all .15s"
                            title="Acquitter" onmouseover="this.style.background='#f0fdf4';this.style.color='#16a34a'" onmouseout="this.style.background='none';this.style.color='var(--muted)'">
                        <i class="bi bi-check2"></i>
                    </button>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Mini graphique dispensation -->
            <?php if (!empty($mouvements)): ?>
            <div style="border-top:1px solid var(--border);padding:12px 18px 0">
                <div style="font-size:.7rem;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.8px;margin-bottom:8px">
                    <i class="bi bi-bar-chart-fill me-1" style="color:var(--red)"></i> Dispensation 7 derniers jours
                </div>
                <?php
                $maxQ = max(array_column($mouvements, 'qte_dispensee'));
                ?>
                <div style="display:flex;align-items:flex-end;gap:4px;height:40px;margin-bottom:8px">
                    <?php foreach ($mouvements as $mv):
                        $h = $maxQ > 0 ? max(4, round(($mv['qte_dispensee'] / $maxQ) * 32)) : 4;
                    ?>
                    <div style="flex:1;display:flex;flex-direction:column;align-items:center;gap:2px">
                        <div style="width:100%;height:<?= $h ?>px;background:var(--red-soft);border-radius:3px 3px 0 0;min-height:4px"
                             title="<?= date('j/m', strtotime($mv['jour'])) ?> : <?= $mv['qte_dispensee'] ?> unités"></div>
                        <div style="font-size:.6rem;color:#9ca3af"><?= date('j/m', strtotime($mv['jour'])) ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <div class="ph-panel-footer">
                <a href="<?= BASE_URL ?>pharmacie/stock"><i class="bi bi-box-seam me-1"></i>Voir l'inventaire complet</a>
            </div>
        </div>

    </div><!-- /ph-grid -->
</div><!-- /ph-main -->

<!-- ── TOAST ── -->
<div id="toastCont">
    <div id="toast" class="toast align-items-center border-0 text-white" role="alert" style="min-width:280px">
        <div class="d-flex">
            <div class="toast-body fw-bold" id="toastBody"></div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    </div>
</div>

<script>
const CSRF = '<?= htmlspecialchars($csrfToken, ENT_QUOTES) ?>';

function showToast(msg, ok = true) {
    const t = document.getElementById('toast');
    t.className = 'toast align-items-center border-0 text-white bg-' + (ok ? 'success' : 'danger');
    document.getElementById('toastBody').textContent = msg;
    new bootstrap.Toast(t, {delay: 4000}).show();
}

function lancerAnalyse() {
    const btn = document.getElementById('btnAnalyse');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Analyse en cours…';

    fetch('<?= BASE_URL ?>pharmacie/stocks/analyse', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: '_csrf_token=' + encodeURIComponent(CSRF),
    })
    .then(r => r.json())
    .then(d => {
        if (d.ok) {
            showToast('Analyse terminée — ' + d.analyses + ' analysés · ' + d.generes + ' alertes créées · ' + d.mis_a_jour + ' mises à jour');
            setTimeout(() => location.reload(), 1800);
        } else {
            showToast('Erreur : ' + (d.error ?? 'Inconnue'), false);
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-lightning-charge-fill"></i> Lancer l\'analyse';
        }
    })
    .catch(() => {
        showToast('Erreur réseau', false);
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-lightning-charge-fill"></i> Lancer l\'analyse';
    });
}

function acquitter(id) {
    fetch('<?= BASE_URL ?>pharmacie/stocks/acquitter', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: '_csrf_token=' + encodeURIComponent(CSRF) + '&id=' + id,
    })
    .then(r => r.json())
    .then(d => {
        if (d.ok) {
            const el = document.getElementById('alerte-' + id);
            if (el) { el.style.opacity = '0'; el.style.transition = '.3s'; setTimeout(() => el.remove(), 300); }
            showToast('Alerte acquittée.');
        } else {
            showToast('Impossible d\'acquitter.', false);
        }
    });
}
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
