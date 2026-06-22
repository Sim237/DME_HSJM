<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Rapport HSJM — <?= $date_rapport ?></title>
<style>
* { margin:0; padding:0; box-sizing:border-box; }
body { font-family: 'Segoe UI', Arial, sans-serif; font-size: 11px; color: #1a1a2e; background: #fff; }

/* Topbar impression */
.print-header {
    background: linear-gradient(135deg, #0f172a, #1e40af);
    color: white; padding: 20px 28px;
    display: flex; justify-content: space-between; align-items: center;
    margin-bottom: 24px;
}
.print-header .logo-area { display: flex; flex-direction: column; }
.print-header h1 { font-size: 18px; font-weight: 800; letter-spacing: -0.5px; }
.print-header .subtitle { font-size: 10px; opacity: .7; margin-top: 3px; }
.print-header .date-info { text-align: right; font-size: 10px; opacity: .8; }
.print-header .date-info strong { font-size: 13px; display: block; }

.content { padding: 0 28px 28px; }

/* Section */
.section { margin-bottom: 24px; break-inside: avoid; }
.section-title {
    font-size: 10px; font-weight: 800; text-transform: uppercase; letter-spacing: 1.5px;
    color: #3b82f6; border-bottom: 2px solid #e2e8f0; padding-bottom: 6px; margin-bottom: 12px;
    display: flex; align-items: center; gap: 8px;
}

/* KPI Grid */
.kpi-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; margin-bottom: 20px; }
.kpi-box {
    border: 1px solid #e2e8f0; border-radius: 10px; padding: 14px;
    background: #f8fafc; text-align: center;
}
.kpi-box .val { font-size: 24px; font-weight: 900; color: #0f172a; line-height: 1; }
.kpi-box .lbl { font-size: 9px; font-weight: 700; text-transform: uppercase; letter-spacing: .8px; color: #64748b; margin-top: 4px; }
.kpi-box.blue  { border-top: 3px solid #3b82f6; }
.kpi-box.green { border-top: 3px solid #10b981; }
.kpi-box.orange{ border-top: 3px solid #f59e0b; }
.kpi-box.purple{ border-top: 3px solid #8b5cf6; }
.kpi-box.teal  { border-top: 3px solid #06b6d4; }
.kpi-box.red   { border-top: 3px solid #ef4444; }

/* Tables */
table { width: 100%; border-collapse: collapse; font-size: 10.5px; }
thead th {
    background: #1e293b; color: white; padding: 8px 10px;
    text-align: left; font-weight: 700; font-size: 9px;
    text-transform: uppercase; letter-spacing: .8px;
}
tbody td { padding: 7px 10px; border-bottom: 1px solid #f1f5f9; }
tbody tr:nth-child(even) { background: #f8fafc; }
tbody tr:hover { background: #eff6ff; }

/* Badge */
.badge { display: inline-block; padding: 2px 8px; border-radius: 20px; font-size: 9px; font-weight: 800; }
.badge-ok      { background: #dcfce7; color: #15803d; }
.badge-warn    { background: #fef9c3; color: #854d0e; }
.badge-danger  { background: #fee2e2; color: #b91c1c; }

/* Bar */
.bar-wrap { display: flex; align-items: center; gap: 6px; }
.bar { flex: 1; height: 5px; border-radius: 5px; background: #e2e8f0; overflow: hidden; max-width: 80px; }
.bar-fill { height: 100%; border-radius: 5px; }

/* Footer */
.print-footer {
    margin-top: 28px; padding-top: 12px; border-top: 1px solid #e2e8f0;
    font-size: 9px; color: #94a3b8; text-align: center;
}

/* Boutons non imprimés */
.no-print { margin-bottom: 16px; display: flex; gap: 10px; padding: 12px 28px; background: #f1f5f9; }
.btn-print {
    background: #0f172a; color: white; border: none; padding: 10px 24px;
    border-radius: 8px; font-weight: 700; font-size: 13px; cursor: pointer;
}
.btn-close {
    background: #e2e8f0; color: #475569; border: none; padding: 10px 24px;
    border-radius: 8px; font-weight: 700; font-size: 13px; cursor: pointer;
    text-decoration: none; display: inline-flex; align-items: center;
}

@media print {
    .no-print { display: none !important; }
    body { font-size: 10px; }
    .print-header { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    thead th { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    .kpi-box { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    @page { margin: 1cm; size: A4 portrait; }
}
</style>
</head>
<body>

<!-- Barre actions (masquée à l'impression) -->
<div class="no-print">
    <button class="btn-print" onclick="window.print()">🖨️ Imprimer / Enregistrer en PDF</button>
    <a class="btn-close" href="javascript:history.back()">← Retour</a>
    <span style="color:#64748b;font-size:12px;align-self:center;">
        Astuce : dans la boîte d'impression, choisissez <strong>"Enregistrer en PDF"</strong> comme imprimante.
    </span>
</div>

<!-- En-tête rapport -->
<div class="print-header">
    <div class="logo-area">
        <h1>Hôpital Saint-Jean de Malte</h1>
        <div class="subtitle">Njombé, Cameroun — Direction Générale</div>
        <div style="margin-top:8px;font-size:11px;opacity:.9;">Rapport Statistique Complet</div>
    </div>
    <div class="date-info">
        <strong><?= $date_rapport ?></strong>
        Généré par : <?= htmlspecialchars($_SESSION['user_nom'] ?? 'Direction') ?>
        <br>DME HSJM v2026
    </div>
</div>

<div class="content">

    <!-- KPIs GLOBAUX -->
    <div class="section">
        <div class="section-title">📊 Indicateurs Clés — Synthèse Globale</div>
        <div class="kpi-grid">
            <div class="kpi-box blue">
                <div class="val"><?= number_format($kpi['total_patients']) ?></div>
                <div class="lbl">Total Patients</div>
            </div>
            <div class="kpi-box green">
                <div class="val"><?= $kpi['patients_ce_mois'] ?></div>
                <div class="lbl">Nouveaux ce mois</div>
            </div>
            <div class="kpi-box teal">
                <div class="val"><?= $kpi['consultations_mois'] ?></div>
                <div class="lbl">Consultations / mois</div>
            </div>
            <div class="kpi-box orange">
                <div class="val"><?= $kpi['hospitalisations_actives'] ?></div>
                <div class="lbl">Hospitalisés actifs</div>
            </div>
            <div class="kpi-box <?= $kpi['taux_occupation'] > 80 ? 'red' : ($kpi['taux_occupation'] > 50 ? 'orange' : 'green') ?>">
                <div class="val"><?= $kpi['taux_occupation'] ?>%</div>
                <div class="lbl">Occupation lits (<?= $kpi['lits_occupes'] ?>/<?= $kpi['total_lits'] ?>)</div>
            </div>
            <div class="kpi-box purple">
                <div class="val"><?= $kpi['rdv_aujourd_hui'] ?></div>
                <div class="lbl">RDV Spécialistes aujourd'hui</div>
            </div>
            <div class="kpi-box blue">
                <div class="val"><?= $kpi['rdv_mois'] ?></div>
                <div class="lbl">RDV Spécialistes ce mois</div>
            </div>
            <div class="kpi-box <?= count($stocks_critiques) > 5 ? 'red' : (count($stocks_critiques) > 0 ? 'orange' : 'green') ?>">
                <div class="val"><?= count($stocks_critiques) ?></div>
                <div class="lbl">Alertes stock pharmacie</div>
            </div>
        </div>
    </div>

    <!-- OCCUPATION DES LITS -->
    <div class="section">
        <div class="section-title">🏥 Occupation des Lits par Service</div>
        <table>
            <thead>
                <tr>
                    <th>Service</th>
                    <th>Total Lits</th>
                    <th>Occupés</th>
                    <th>Disponibles</th>
                    <th>Taux d'occupation</th>
                    <th>Niveau</th>
                </tr>
            </thead>
            <tbody>
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
 foreach ($lits_par_service as $s):
                    $taux = (float)($s['taux'] ?? 0);
                    $dispo = $s['total'] - $s['occupes'];
                    $bColor = $taux > 80 ? '#ef4444' : ($taux > 50 ? '#f59e0b' : '#10b981');
                    $badgeCls = $taux > 80 ? 'badge-danger' : ($taux > 50 ? 'badge-warn' : 'badge-ok');
                    $badgeLbl = $taux > 80 ? 'Critique' : ($taux > 50 ? 'Modéré' : 'Normal');
                ?>
                <tr>
                    <td><strong><?= htmlspecialchars($s['nom_service']) ?></strong></td>
                    <td><?= $s['total'] ?></td>
                    <td><strong style="color:#ef4444;"><?= $s['occupes'] ?></strong></td>
                    <td><strong style="color:#10b981;"><?= $dispo ?></strong></td>
                    <td>
                        <div class="bar-wrap">
                            <div class="bar"><div class="bar-fill" style="width:<?= $taux ?>%;background:<?= $bColor ?>;"></div></div>
                            <span style="font-weight:700;color:<?= $bColor ?>;"><?= $taux ?>%</span>
                        </div>
                    </td>
                    <td><span class="badge <?= $badgeCls ?>"><?= $badgeLbl ?></span></td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($lits_par_service)): ?>
                <tr><td colspan="6" style="text-align:center;color:#94a3b8;padding:12px;">Aucune donnée disponible</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- RDV SPÉCIALISTES -->
    <div class="section">
        <div class="section-title">📅 Rendez-vous Spécialistes — <?= date('F Y') ?></div>
        <table>
            <thead>
                <tr>
                    <th>Spécialiste</th>
                    <th>Spécialité</th>
                    <th>Total RDV</th>
                    <th>Honorés</th>
                    <th>Annulés</th>
                    <th>Taux d'honoration</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rdv_par_specialiste as $sp):
                    $taux_h = $sp['total'] > 0 ? round($sp['honores'] / $sp['total'] * 100, 1) : 0;
                    $hColor = $taux_h >= 70 ? '#10b981' : ($taux_h >= 40 ? '#f59e0b' : '#ef4444');
                ?>
                <tr>
                    <td><strong><?= htmlspecialchars($sp['prenom'] . ' ' . $sp['nom']) ?></strong></td>
                    <td><?= htmlspecialchars($sp['specialite']) ?></td>
                    <td><?= $sp['total'] ?></td>
                    <td style="color:#10b981;font-weight:700;"><?= $sp['honores'] ?></td>
                    <td style="color:#ef4444;"><?= $sp['annules'] ?></td>
                    <td>
                        <div class="bar-wrap">
                            <div class="bar"><div class="bar-fill" style="width:<?= $taux_h ?>%;background:<?= $hColor ?>;"></div></div>
                            <span style="font-weight:700;color:<?= $hColor ?>;"><?= $taux_h ?>%</span>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($rdv_par_specialiste)): ?>
                <tr><td colspan="6" style="text-align:center;color:#94a3b8;padding:12px;">Aucun spécialiste configuré</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- STOCKS PHARMACIE -->
    <div class="section">
        <div class="section-title">💊 Alertes Stock Pharmacie (<?= count($stocks_critiques) ?> produit(s) sous seuil)</div>
        <?php if (empty($stocks_critiques)): ?>
        <div style="padding:12px;background:#f0fdf4;border-radius:8px;color:#15803d;font-weight:600;">
            ✓ Tous les stocks sont à niveau normal.
        </div>
        <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Médicament</th>
                    <th>Forme</th>
                    <th>Stock actuel</th>
                    <th>Seuil alerte</th>
                    <th>Niveau critique</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($stocks_critiques as $m):
                    $is_rupture = $m['quantite'] == 0;
                    $badgeCls = $is_rupture ? 'badge-danger' : 'badge-warn';
                    $badgeLbl = $is_rupture ? 'RUPTURE' : 'ALERTE';
                ?>
                <tr>
                    <td><strong><?= htmlspecialchars($m['nom']) ?></strong></td>
                    <td><?= htmlspecialchars($m['forme'] ?? '—') ?></td>
                    <td><strong style="color:<?= $is_rupture ? '#ef4444' : '#f59e0b' ?>;"><?= $m['quantite'] ?></strong></td>
                    <td><?= $m['seuil_alerte'] ?></td>
                    <td><span class="badge <?= $badgeCls ?>"><?= $badgeLbl ?></span></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>

    <!-- FOOTER -->
    <div class="print-footer">
        Hôpital Saint-Jean de Malte &mdash; Njombé, Cameroun &nbsp;|&nbsp;
        Document généré le <?= $date_rapport ?> &nbsp;|&nbsp;
        Système DME HSJM v2026 &mdash; Confidentiel
    </div>

</div>

<script>
// Déclencher l'impression automatiquement si paramètre autoprint=1
if (new URLSearchParams(window.location.search).get('autoprint') === '1') {
    window.addEventListener('load', () => setTimeout(window.print, 500));
}
</script>
</body>
</html>
