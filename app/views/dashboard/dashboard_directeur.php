<?php require_once __DIR__ . '/../layouts/header.php'; ?>
<script src="<?= BASE_URL ?>public/js/chart.umd.js"></script>

<style>
/* ── LAYOUT ──────────────────────────────────────────────────────────────── */
.sidebar { display: none !important; }
main { margin-left: 0 !important; width: 100% !important; }
body { background: #0f172a; font-family: 'Inter', system-ui, sans-serif; }

/* ── TOPBAR ──────────────────────────────────────────────────────────────── */
.dir-topbar {
    background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
    border-bottom: 1px solid rgba(255,255,255,0.06);
    padding: 14px 32px;
    position: sticky; top: 0; z-index: 1000;
}
#dir-clock { font-family: 'Courier New', monospace; font-size: 1.5rem; font-weight: 900; color: #00ff87; text-shadow: 0 0 15px rgba(0,255,135,0.4); letter-spacing: 2px; }

/* ── SECTIONS ────────────────────────────────────────────────────────────── */
.dir-content { padding: 28px 32px; }
.section-label {
    font-size: 0.68rem; font-weight: 800; text-transform: uppercase; letter-spacing: 2px;
    color: #64748b; margin-bottom: 16px; display: flex; align-items: center; gap: 8px;
}
.section-label::after { content: ''; flex: 1; height: 1px; background: rgba(255,255,255,0.06); }

/* ── KPI CARDS ───────────────────────────────────────────────────────────── */
.kpi-card {
    background: #1e293b;
    border: 1px solid rgba(255,255,255,0.06);
    border-radius: 16px; padding: 22px 24px;
    position: relative; overflow: hidden;
    transition: transform 0.2s, box-shadow 0.2s;
}
.kpi-card:hover { transform: translateY(-3px); box-shadow: 0 12px 32px rgba(0,0,0,0.3); }
.kpi-card::before {
    content: ''; position: absolute; top: 0; left: 0; right: 0; height: 3px;
}
.kpi-blue::before   { background: linear-gradient(90deg, #3b82f6, #06b6d4); }
.kpi-green::before  { background: linear-gradient(90deg, #10b981, #34d399); }
.kpi-orange::before { background: linear-gradient(90deg, #f59e0b, #fbbf24); }
.kpi-red::before    { background: linear-gradient(90deg, #ef4444, #f97316); }
.kpi-purple::before { background: linear-gradient(90deg, #8b5cf6, #a78bfa); }
.kpi-teal::before   { background: linear-gradient(90deg, #14b8a6, #06b6d4); }

.kpi-value { font-size: 2.4rem; font-weight: 900; color: #f1f5f9; line-height: 1; margin: 8px 0 4px; }
.kpi-label { font-size: 0.78rem; color: #94a3b8; font-weight: 500; }
.kpi-sub   { font-size: 0.72rem; color: #64748b; margin-top: 6px; }
.kpi-icon  { position: absolute; right: 20px; top: 50%; transform: translateY(-50%); font-size: 2.8rem; opacity: 0.08; color: #fff; }
.kpi-trend { display: inline-flex; align-items: center; gap: 4px; font-size: 0.72rem; font-weight: 700; padding: 2px 8px; border-radius: 20px; margin-top: 8px; }
.trend-up   { background: rgba(16,185,129,0.15); color: #10b981; }
.trend-down { background: rgba(239,68,68,0.15); color: #ef4444; }
.trend-flat { background: rgba(100,116,139,0.15); color: #64748b; }

/* ── GAUGE ───────────────────────────────────────────────────────────────── */
.gauge-wrap { text-align: center; padding: 10px 0; }
.gauge-pct  { font-size: 2.8rem; font-weight: 900; }
.gauge-bar  { height: 10px; border-radius: 10px; background: rgba(255,255,255,0.06); margin-top: 8px; overflow: hidden; }
.gauge-fill { height: 100%; border-radius: 10px; transition: width 1.2s cubic-bezier(0.16,1,0.3,1); }

/* ── CHART CARDS ─────────────────────────────────────────────────────────── */
.chart-card {
    background: #1e293b; border: 1px solid rgba(255,255,255,0.06);
    border-radius: 16px; padding: 20px 24px;
}
.chart-card-title { font-size: 0.82rem; font-weight: 700; color: #cbd5e1; margin-bottom: 16px; display: flex; align-items: center; gap: 8px; }
.chart-card-title .dot { width: 8px; height: 8px; border-radius: 50%; }

/* ── TABLE ───────────────────────────────────────────────────────────────── */
.dir-table { width: 100%; border-collapse: collapse; font-size: 0.82rem; }
.dir-table th {
    padding: 10px 12px; text-align: left; font-size: 0.68rem; font-weight: 700;
    text-transform: uppercase; letter-spacing: 1px; color: #64748b;
    border-bottom: 1px solid rgba(255,255,255,0.06);
}
.dir-table td { padding: 10px 12px; color: #cbd5e1; border-bottom: 1px solid rgba(255,255,255,0.04); vertical-align: middle; }
.dir-table tbody tr:hover { background: rgba(255,255,255,0.03); }
.dir-table .badge-pill { font-size: 0.68rem; padding: 2px 8px; border-radius: 20px; font-weight: 700; }

/* ── STOCK INDICATOR ─────────────────────────────────────────────────────── */
.stock-bar-wrap { display: flex; align-items: center; gap: 8px; }
.stock-bar { flex: 1; height: 6px; border-radius: 6px; background: rgba(255,255,255,0.06); overflow: hidden; }
.stock-bar-fill { height: 100%; border-radius: 6px; }

/* ── ALERT BADGE ─────────────────────────────────────────────────────────── */
.alert-dot { width: 8px; height: 8px; border-radius: 50%; display: inline-block; margin-right: 4px; }

/* ── HEATMAP HOURS ───────────────────────────────────────────────────────── */
.hour-grid { display: grid; grid-template-columns: repeat(12, 1fr); gap: 3px; }
.hour-cell {
    aspect-ratio: 1; border-radius: 4px; display: flex; flex-direction: column;
    align-items: center; justify-content: center; font-size: 0.58rem; color: rgba(255,255,255,0.5);
    transition: 0.2s; cursor: default;
}
.hour-cell:hover { transform: scale(1.2); z-index: 1; }

/* ── SCROLLBAR ───────────────────────────────────────────────────────────── */
::-webkit-scrollbar { width: 6px; height: 6px; }
::-webkit-scrollbar-track { background: #0f172a; }
::-webkit-scrollbar-thumb { background: #334155; border-radius: 3px; }
</style>

<!-- ═══════════════════════ TOPBAR ═══════════════════════════════════════ -->
<nav class="dir-topbar d-flex justify-content-between align-items-center">
    <div class="d-flex align-items-center gap-3">
        <div class="bg-white rounded-circle p-2 shadow">
            <img src="<?= BASE_URL ?>public/images/logo_ordre_malte.png" style="height: 36px;" alt="HSJM">
        </div>
        <div>
            <div class="text-white fw-bold fs-5">Hôpital Saint-Jean de Malte</div>
            <div style="font-size:0.72rem; color:#64748b;">
                Tableau de bord — Direction Générale &nbsp;|&nbsp;
                <span style="color:#94a3b8;"><?= htmlspecialchars($_SESSION['user_nom'] ?? 'Directeur') ?></span>
            </div>
        </div>
    </div>

    <div id="dir-clock">00:00:00</div>

    <div class="d-flex gap-2 align-items-center">
        <a href="<?= BASE_URL ?>directeur/patients" class="btn btn-sm rounded-pill px-3 fw-bold"
           style="background:rgba(124,58,237,.25); color:#c4b5fd; border:1px solid rgba(124,58,237,.4);">
            <i class="bi bi-people-fill me-1"></i>Analyse Patients
        </a>
        <button class="btn btn-sm btn-outline-light rounded-pill px-3 fw-bold" onclick="location.reload()">
            <i class="bi bi-arrow-clockwise"></i> Actualiser
        </button>
        <a href="<?= BASE_URL ?>logout" class="btn btn-sm btn-danger rounded-pill px-3 fw-bold">
            <i class="bi bi-power"></i> Quitter
        </a>
    </div>
</nav>

<!-- ═══════════════════════ CONTENU ══════════════════════════════════════ -->
<div class="dir-content">

    <!-- ── ROW 1 : KPI CARDS ────────────────────────────────────────────── -->
    <p class="section-label"><i class="bi bi-speedometer2"></i> Indicateurs Clés — Temps Réel</p>
    <div class="row g-3 mb-4">

        <div class="col-xl-2 col-lg-4 col-md-6">
            <div class="kpi-card kpi-blue">
                <div class="kpi-label">Total Patients</div>
                <div class="kpi-value"><?= number_format($kpi['total_patients']) ?></div>
                <div class="kpi-sub">+<?= $kpi['patients_ce_mois'] ?> ce mois</div>
                <i class="bi bi-people-fill kpi-icon"></i>
            </div>
        </div>

        <div class="col-xl-2 col-lg-4 col-md-6">
            <div class="kpi-card kpi-green">
                <div class="kpi-label">Consultations / mois</div>
                <div class="kpi-value"><?= $kpi['consultations_mois'] ?></div>
                <div class="kpi-sub"><?= date('F Y') ?></div>
                <i class="bi bi-clipboard2-pulse kpi-icon"></i>
            </div>
        </div>

        <div class="col-xl-2 col-lg-4 col-md-6">
            <div class="kpi-card kpi-orange">
                <div class="kpi-label">Hospitalisés Actifs</div>
                <div class="kpi-value"><?= $kpi['hospitalisations_actives'] ?></div>
                <div class="kpi-sub"><?= $kpi['lits_occupes'] ?> lits occupés</div>
                <i class="bi bi-hospital kpi-icon"></i>
            </div>
        </div>

        <div class="col-xl-2 col-lg-4 col-md-6">
            <div class="kpi-card kpi-<?= $kpi['taux_occupation'] > 80 ? 'red' : ($kpi['taux_occupation'] > 50 ? 'orange' : 'green') ?>">
                <div class="kpi-label">Occupation Lits</div>
                <div class="kpi-value"><?= $kpi['taux_occupation'] ?>%</div>
                <div class="gauge-bar mt-2">
                    <div class="gauge-fill" id="mainGaugeFill"
                         style="width: <?= $kpi['taux_occupation'] ?>%; background: <?= $kpi['taux_occupation'] > 80 ? '#ef4444' : ($kpi['taux_occupation'] > 50 ? '#f59e0b' : '#10b981') ?>;"></div>
                </div>
                <div class="kpi-sub mt-1"><?= $kpi['lits_occupes'] ?> / <?= $kpi['total_lits'] ?> lits</div>
                <i class="bi bi-grid-3x3-gap kpi-icon"></i>
            </div>
        </div>

        <div class="col-xl-2 col-lg-4 col-md-6">
            <div class="kpi-card kpi-purple">
                <div class="kpi-label">Attente Moy. Consultation</div>
                <div class="kpi-value">
                    <?php
                        $att = $kpi['temps_attente_moy'];
                        echo $att > 60 ? round($att/60, 1).'h' : $att.'min';
                    ?>
                </div>
                <div class="kpi-sub">30 derniers jours</div>
                <i class="bi bi-stopwatch kpi-icon"></i>
            </div>
        </div>

        <div class="col-xl-2 col-lg-4 col-md-6">
            <div class="kpi-card kpi-<?= ($kpi['medicaments_rupture'] > 0) ? 'red' : (($kpi['medicaments_alerte'] > 0) ? 'orange' : 'teal') ?>">
                <div class="kpi-label">Alertes Pharmacie</div>
                <div class="kpi-value"><?= $kpi['medicaments_rupture'] + $kpi['medicaments_alerte'] ?></div>
                <div class="kpi-sub">
                    <?= $kpi['medicaments_rupture'] ?> rupture(s) &bull; <?= $kpi['medicaments_alerte'] ?> alerte(s)
                </div>
                <i class="bi bi-capsule kpi-icon"></i>
            </div>
        </div>

    </div>

    <!-- ── ROW 2 : TAUX OCCUPATION + PATIENTS PAR MOIS ──────────────────── -->
    <div class="row g-3 mb-4">

        <!-- Occupation des lits par service (Horizontal Bar) -->
        <div class="col-xl-5 col-lg-6">
            <div class="chart-card h-100">
                <div class="chart-card-title">
                    <span class="dot" style="background:#3b82f6;"></span>
                    Taux d'Occupation des Lits par Service
                </div>
                <?php if (empty($lits_par_service)): ?>
                    <div class="text-center py-4" style="color:#64748b;">Aucune donnée disponible</div>
                <?php else: ?>
                <div class="mb-3">
                    <?php foreach ($lits_par_service as $svc):
                        $pct = (float)($svc['taux'] ?? 0);
                        $color = $pct > 80 ? '#ef4444' : ($pct > 50 ? '#f59e0b' : '#10b981');
                    ?>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-1">
                            <span style="font-size:0.78rem; color:#cbd5e1; font-weight:600;"><?= htmlspecialchars($svc['nom_service']) ?></span>
                            <span style="font-size:0.75rem; color:#94a3b8;"><?= $svc['occupes'] ?>/<?= $svc['total'] ?> — <strong style="color:<?= $color ?>;"><?= $pct ?>%</strong></span>
                        </div>
                        <div class="gauge-bar">
                            <div class="gauge-fill" style="width: <?= $pct ?>%; background: <?= $color ?>;"></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Patients enregistrés par mois (Line chart) -->
        <div class="col-xl-7 col-lg-6">
            <div class="chart-card h-100">
                <div class="chart-card-title">
                    <span class="dot" style="background:#10b981;"></span>
                    Patients Enregistrés — 12 Derniers Mois
                </div>
                <canvas id="chartPatientsMois" height="200"></canvas>
            </div>
        </div>

    </div>

    <!-- ── ROW 3 : CONSULT VS HOSP + TOP PATHOLOGIES ────────────────────── -->
    <div class="row g-3 mb-4">

        <!-- Consultations vs Hospitalisations -->
        <div class="col-xl-6">
            <div class="chart-card h-100">
                <div class="chart-card-title">
                    <span class="dot" style="background:#06b6d4;"></span>
                    Consultations vs Hospitalisations — 6 Mois
                </div>
                <canvas id="chartConsultHosp" height="220"></canvas>
            </div>
        </div>

        <!-- Top 10 Pathologies -->
        <div class="col-xl-6">
            <div class="chart-card h-100">
                <div class="chart-card-title">
                    <span class="dot" style="background:#a78bfa;"></span>
                    Top 10 Pathologies Diagnostiquées
                </div>
                <?php if (empty($top_pathologies)): ?>
                    <div class="text-center py-4" style="color:#64748b;">Aucune donnée de diagnostic disponible</div>
                <?php else: ?>
                <canvas id="chartPathologies" height="220"></canvas>
                <?php endif; ?>
            </div>
        </div>

    </div>

    <!-- ── ROW 4 : MÉDICAMENTS + HEATMAP HEURES ─────────────────────────── -->
    <div class="row g-3 mb-4">

        <!-- Top médicaments demandés -->
        <div class="col-xl-5">
            <div class="chart-card h-100">
                <div class="chart-card-title">
                    <span class="dot" style="background:#f59e0b;"></span>
                    Médicaments les Plus Demandés
                </div>
                <?php if (empty($top_medicaments)): ?>
                    <div class="text-center py-4" style="color:#64748b;">Aucune ordonnance enregistrée</div>
                <?php else: ?>
                <div>
                    <?php
                    $maxMed = max(array_column($top_medicaments, 'total_demande'));
                    foreach ($top_medicaments as $idx => $med):
                        $pct = $maxMed > 0 ? round($med['total_demande'] / $maxMed * 100) : 0;
                        $colors = ['#f59e0b','#fb923c','#f87171','#a78bfa','#34d399','#38bdf8','#818cf8','#fb7185','#4ade80','#facc15'];
                        $c = $colors[$idx % count($colors)];
                    ?>
                    <div class="mb-2">
                        <div class="d-flex justify-content-between mb-1">
                            <span style="font-size:0.78rem; color:#e2e8f0; font-weight:600; max-width:65%; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;" title="<?= htmlspecialchars($med['nom']) ?>"><?= htmlspecialchars($med['nom']) ?></span>
                            <span style="font-size:0.72rem; color:#94a3b8;"><?= $med['total_demande'] ?> unités</span>
                        </div>
                        <div class="gauge-bar">
                            <div class="gauge-fill" style="width: <?= $pct ?>%; background: <?= $c ?>;"></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Distribution horaire des consultations -->
        <div class="col-xl-7">
            <div class="chart-card h-100">
                <div class="chart-card-title">
                    <span class="dot" style="background:#38bdf8;"></span>
                    Distribution Horaire des Consultations (30 jours)
                </div>
                <canvas id="chartHeures" height="160"></canvas>
            </div>
        </div>

    </div>

    <!-- ── ROW 5 : STOCKS PHARMACIE + RÉSUMÉ SERVICES ───────────────────── -->
    <div class="row g-3 mb-4">

        <!-- Stocks pharmacie -->
        <div class="col-xl-5">
            <div class="chart-card">
                <div class="chart-card-title">
                    <span class="dot" style="background:#ef4444;"></span>
                    État des Stocks — Pharmacie
                    <?php if ($kpi['medicaments_rupture'] > 0): ?>
                        <span class="badge" style="background:rgba(239,68,68,0.2); color:#ef4444; font-size:0.65rem; padding:2px 8px; border-radius:20px;">
                            <?= $kpi['medicaments_rupture'] ?> RUPTURE(S)
                        </span>
                    <?php endif; ?>
                </div>
                <div style="max-height: 360px; overflow-y: auto;">
                    <table class="dir-table">
                        <thead>
                            <tr>
                                <th>Médicament</th>
                                <th>Stock</th>
                                <th>Seuil</th>
                                <th>Niveau</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($stocks_alerte as $m):
                                $niv = $m['niveau'];
                                $color = match($niv) {
                                    'rupture'  => '#ef4444',
                                    'critique' => '#f97316',
                                    'alerte'   => '#f59e0b',
                                    default    => '#10b981'
                                };
                                $label = match($niv) {
                                    'rupture'  => 'RUPTURE',
                                    'critique' => 'CRITIQUE',
                                    'alerte'   => 'ALERTE',
                                    default    => 'OK'
                                };
                                $maxQ = max($m['seuil_alerte'] * 3, 1);
                                $pctStock = min(round($m['quantite'] / $maxQ * 100), 100);
                            ?>
                            <tr>
                                <td>
                                    <div style="font-weight:600; color:#e2e8f0; font-size:0.8rem;"><?= htmlspecialchars($m['nom']) ?></div>
                                    <div style="font-size:0.68rem; color:#64748b;"><?= htmlspecialchars($m['forme'] ?? '') ?> <?= htmlspecialchars($m['dosage'] ?? '') ?></div>
                                </td>
                                <td>
                                    <div class="stock-bar-wrap">
                                        <div class="stock-bar">
                                            <div class="stock-bar-fill" style="width:<?= $pctStock ?>%; background:<?= $color ?>;"></div>
                                        </div>
                                        <span style="color:<?= $color ?>; font-weight:700; font-size:0.78rem; min-width:28px; text-align:right;"><?= $m['quantite'] ?></span>
                                    </div>
                                </td>
                                <td style="color:#64748b; font-size:0.78rem;"><?= $m['seuil_alerte'] ?></td>
                                <td>
                                    <span class="badge-pill" style="background:<?= $color ?>22; color:<?= $color ?>;"><?= $label ?></span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($stocks_alerte)): ?>
                            <tr><td colspan="4" class="text-center" style="color:#64748b; padding: 24px;">Tous les stocks sont à niveau normal</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Résumé par service -->
        <div class="col-xl-7">
            <div class="chart-card">
                <div class="chart-card-title">
                    <span class="dot" style="background:#8b5cf6;"></span>
                    Résumé par Service — Activité du Mois
                </div>
                <div style="overflow-x: auto;">
                    <table class="dir-table">
                        <thead>
                            <tr>
                                <th>Service</th>
                                <th>Patients</th>
                                <th>Consultations</th>
                                <th>Hosp. Actives</th>
                                <th>Occupation Lits</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($resume_services as $s):
                                $occ = ($s['total_lits'] > 0) ? round(($s['lits_occupes'] / $s['total_lits']) * 100) : 0;
                                $occColor = $occ > 80 ? '#ef4444' : ($occ > 50 ? '#f59e0b' : '#10b981');
                            ?>
                            <tr>
                                <td>
                                    <span style="font-weight:700; color:#e2e8f0;"><?= htmlspecialchars($s['nom_service']) ?></span>
                                </td>
                                <td>
                                    <span style="font-weight:700; color:#38bdf8;"><?= $s['nb_patients'] ?></span>
                                </td>
                                <td>
                                    <span style="color:#10b981; font-weight:600;"><?= $s['nb_consult'] ?></span>
                                </td>
                                <td>
                                    <?php if ($s['nb_hosp_actives'] > 0): ?>
                                        <span class="badge-pill" style="background:rgba(249,115,22,0.2); color:#fb923c;"><?= $s['nb_hosp_actives'] ?></span>
                                    <?php else: ?>
                                        <span style="color:#475569;">—</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($s['total_lits'] > 0): ?>
                                    <div class="stock-bar-wrap" style="width: 120px;">
                                        <div class="stock-bar">
                                            <div class="stock-bar-fill" style="width:<?= $occ ?>%; background:<?= $occColor ?>;"></div>
                                        </div>
                                        <span style="color:<?= $occColor ?>; font-size:0.75rem; font-weight:700;"><?= $occ ?>%</span>
                                    </div>
                                    <?php else: ?>
                                        <span style="color:#475569; font-size:0.75rem;">N/A</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>

    <!-- ── FOOTER ─────────────────────────────────────────────────────────── -->
    <div class="text-center mt-2 mb-4" style="color:#334155; font-size:0.7rem;">
        Hôpital Saint-Jean de Malte — Njombé, Cameroun &nbsp;|&nbsp;
        Dernière mise à jour : <?= date('d/m/Y à H:i:s') ?> &nbsp;|&nbsp;
        DME v2026
    </div>

</div><!-- /dir-content -->

<!-- ═══════════════════════ JAVASCRIPT ═══════════════════════════════════ -->
<script>
// ── Horloge ────────────────────────────────────────────────────────────────
function updateClock() {
    document.getElementById('dir-clock').textContent = new Date().toLocaleTimeString('fr-FR');
}
setInterval(updateClock, 1000);
updateClock();

// ── Données PHP → JS ───────────────────────────────────────────────────────
const patientsMoisData   = <?= json_encode($patients_par_mois) ?>;
const consultHospData    = <?= json_encode($consult_vs_hosp) ?>;
const pathologiesData    = <?= json_encode($top_pathologies) ?>;
const heuresData         = <?= json_encode($admissions_par_heure) ?>;

// Palettes cohérentes
const PALETTE = ['#3b82f6','#10b981','#f59e0b','#ef4444','#8b5cf6','#06b6d4','#f97316','#34d399','#a78bfa','#fb923c'];

// ── CHART : Patients par mois ──────────────────────────────────────────────
if (patientsMoisData.length > 0) {
    new Chart(document.getElementById('chartPatientsMois'), {
        type: 'line',
        data: {
            labels: patientsMoisData.map(d => d.label),
            datasets: [{
                label: 'Nouveaux patients',
                data: patientsMoisData.map(d => d.nb),
                borderColor: '#10b981',
                backgroundColor: 'rgba(16,185,129,0.08)',
                fill: true, tension: 0.4, pointRadius: 5,
                pointBackgroundColor: '#10b981',
                pointBorderColor: '#0f172a', pointBorderWidth: 2
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false }, tooltip: { mode: 'index' } },
            scales: {
                x: { grid: { color: 'rgba(255,255,255,0.04)' }, ticks: { color: '#64748b', font: { size: 11 } } },
                y: { grid: { color: 'rgba(255,255,255,0.04)' }, ticks: { color: '#64748b', precision: 0 } }
            }
        }
    });
}

// ── CHART : Consultations vs Hospitalisations ──────────────────────────────
if (consultHospData.length > 0) {
    new Chart(document.getElementById('chartConsultHosp'), {
        type: 'bar',
        data: {
            labels: consultHospData.map(d => d.label),
            datasets: [
                {
                    label: 'Consultations',
                    data: consultHospData.map(d => d.nb_consult),
                    backgroundColor: 'rgba(6,182,212,0.7)',
                    borderColor: '#06b6d4', borderWidth: 1, borderRadius: 6
                },
                {
                    label: 'Hospitalisations',
                    data: consultHospData.map(d => d.nb_hosp),
                    backgroundColor: 'rgba(249,115,22,0.7)',
                    borderColor: '#f97316', borderWidth: 1, borderRadius: 6
                }
            ]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { labels: { color: '#94a3b8', font: { size: 11 } } }
            },
            scales: {
                x: { grid: { color: 'rgba(255,255,255,0.04)' }, ticks: { color: '#64748b' } },
                y: { grid: { color: 'rgba(255,255,255,0.04)' }, ticks: { color: '#64748b', precision: 0 } }
            }
        }
    });
}

// ── CHART : Top Pathologies ────────────────────────────────────────────────
if (pathologiesData.length > 0) {
    new Chart(document.getElementById('chartPathologies'), {
        type: 'bar',
        data: {
            labels: pathologiesData.map(d => d.pathologie.length > 28 ? d.pathologie.slice(0, 26) + '…' : d.pathologie),
            datasets: [{
                label: 'Cas diagnostiqués',
                data: pathologiesData.map(d => d.nb),
                backgroundColor: PALETTE.map(c => c + 'cc'),
                borderColor: PALETTE,
                borderWidth: 1, borderRadius: 6
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            plugins: { legend: { display: false } },
            scales: {
                x: { grid: { color: 'rgba(255,255,255,0.04)' }, ticks: { color: '#64748b', precision: 0 } },
                y: { grid: { display: false }, ticks: { color: '#cbd5e1', font: { size: 11 } } }
            }
        }
    });
}

// ── CHART : Distribution Horaire ───────────────────────────────────────────
(function() {
    // Build 24-hour array from sparse data
    const hourMap = {};
    heuresData.forEach(d => { hourMap[parseInt(d.heure)] = parseInt(d.nb); });
    const labels24 = Array.from({length: 24}, (_, i) => i + 'h');
    const data24   = Array.from({length: 24}, (_, i) => hourMap[i] || 0);
    const maxVal   = Math.max(...data24, 1);

    new Chart(document.getElementById('chartHeures'), {
        type: 'bar',
        data: {
            labels: labels24,
            datasets: [{
                label: 'Consultations',
                data: data24,
                backgroundColor: data24.map(v => {
                    const intensity = v / maxVal;
                    return `rgba(56,189,248,${0.2 + intensity * 0.8})`;
                }),
                borderColor: 'rgba(56,189,248,0.5)',
                borderWidth: 1, borderRadius: 4
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false }, tooltip: {
                callbacks: { title: ctx => ctx[0].label + ':00 — ' + ctx[0].label + ':59' }
            }},
            scales: {
                x: { grid: { color: 'rgba(255,255,255,0.04)' }, ticks: { color: '#64748b', font: { size: 10 } } },
                y: { grid: { color: 'rgba(255,255,255,0.04)' }, ticks: { color: '#64748b', precision: 0 } }
            }
        }
    });
})();

// ── Actualisation KPIs en temps réel (toutes les 60 secondes) ────────────
function refreshKpis() {
    fetch('<?= BASE_URL ?>dashboard/kpi-directeur')
        .then(r => r.json())
        .then(d => {
            const taux = d.total_lits > 0 ? ((d.lits_occupes / d.total_lits) * 100).toFixed(1) : 0;
            // Mise à jour silencieuse des valeurs KPI si les éléments existent
            document.querySelectorAll('[data-kpi]').forEach(el => {
                const key = el.dataset.kpi;
                if (d[key] !== undefined) el.textContent = d[key];
            });
        })
        .catch(() => {});
}
setInterval(refreshKpis, 60000);

// ── Auto-refresh complet toutes les 5 minutes ─────────────────────────────
setTimeout(() => location.reload(), 300000);
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
