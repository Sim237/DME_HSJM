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
 require_once __DIR__ . '/_print_base.php'; ?>

<?php
/* ── Helpers ─────────────────────────────────────────────── */
$fdate = function(?string $d): string {
    if (!$d) return '';
    try { return (new DateTime($d))->format('d/m/Y'); }
    catch (\Throwable $e) { return $d; }
};

$nbRows = (int)($formData['nb_rows'] ?? 10);
if ($nbRows < 1) $nbRows = 10;
if ($nbRows > 50) $nbRows = 50;

/* Construire la liste des lignes non vides */
$rows = [];
for ($i = 0; $i < $nbRows; $i++) {
    $row = [
        'nom'      => $formData["r{$i}_nom"]      ?? '',
        'ddn'      => $formData["r{$i}_ddn"]      ?? '',
        'poids_ag' => $formData["r{$i}_poids_ag"] ?? '',
        'signes'   => $formData["r{$i}_signes"]   ?? '',
        'diag'     => $formData["r{$i}_diag"]     ?? '',
        'examens'  => $formData["r{$i}_examens"]  ?? '',
        'syvh'     => $formData["r{$i}_syvh"]     ?? '',
        'tb'       => $formData["r{$i}_tb"]       ?? '',
        'presc'    => $formData["r{$i}_presc"]    ?? '',
        'lia'      => $formData["r{$i}_lia"]      ?? '',
    ];
    $rows[] = $row;
}

/* Compléter à 15 lignes minimum pour remplir la feuille */
while (count($rows) < 15) {
    $rows[] = array_fill_keys(['nom','ddn','poids_ag','signes','diag','examens','syvh','tb','presc','lia'], '');
}
?>

<style>
/* ── Paysage A4 ── */
@page { size: A4 landscape; margin: .7cm 1cm; }

.paper-sheet {
    width: 27.3cm !important;
    min-height: 18.6cm !important;
    padding: 0 !important;
    box-shadow: none !important;
    font-size: 8pt;
    display: block !important;
}

/* ── En-tête ── */
.cpn-header {
    display: grid;
    grid-template-columns: 52px 1fr auto;
    align-items: center;
    gap: 10px;
    border-bottom: 2px solid #000;
    padding-bottom: 6px;
    margin-bottom: 10px;
}
.cpn-header img { height: 46px; }
.cpn-title {
    text-align: center;
    font-size: 13pt;
    font-weight: 900;
    text-transform: uppercase;
    letter-spacing: 1px;
}
.cpn-subtitle {
    text-align: center;
    font-size: 9pt;
    color: #555;
    margin-top: 3px;
}
.cpn-header-right {
    text-align: right;
    font-size: 7.5pt;
    font-weight: bold;
    line-height: 1.7;
}

/* ── Infos session ── */
.session-band {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 8px;
    border: 1px solid #000;
    padding: 5px 10px;
    margin-bottom: 10px;
    background: #f9f9f9;
    font-size: 8pt;
}
.session-field { display: flex; align-items: baseline; gap: 4px; }
.session-label { font-weight: bold; white-space: nowrap; }
.session-val   { border-bottom: 1px solid #666; flex: 1; font-weight: bold; min-width: 60px; padding: 0 4px; }

/* ── Tableau principal ── */
.cpn-table {
    border-collapse: collapse;
    width: 100%;
    font-size: 7.5pt;
    table-layout: fixed;
}
.cpn-table th {
    border: 1.2px solid #000;
    padding: 5px 4px;
    text-align: center;
    font-weight: bold;
    background: #1e293b;
    color: white;
    font-size: 7.5pt;
    letter-spacing: .3px;
    text-transform: uppercase;
}
.cpn-table th.th-white {
    background: #f1f5f9;
    color: #1e293b;
}
.cpn-table td {
    border: 1px solid #555;
    padding: 3px 4px;
    font-size: 7.5pt;
    vertical-align: top;
    min-height: 20px;
    line-height: 1.35;
}
.cpn-table .td-num {
    text-align: center;
    font-weight: bold;
    background: #f0fdf4;
    color: #0d9488;
    font-size: 8pt;
    width: 22px;
}
.cpn-table .td-center { text-align: center; }
.cpn-table .td-syvh-pos { color: #dc2626; font-weight: bold; }
.cpn-table .td-syvh-neg { color: #059669; font-weight: bold; }

/* Lignes alternées */
.cpn-table tr:nth-child(even) > td { background-color: #fafafa; }

/* ── Pied de page ── */
.cpn-footer-band {
    display: grid;
    grid-template-columns: 1fr 1fr 1fr;
    gap: 20px;
    margin-top: 12px;
    padding-top: 8px;
    border-top: 1px solid #000;
    font-size: 7.5pt;
}
.cpn-sig-block { text-align: center; }
.cpn-sig-line  { height: 40px; border-bottom: 1px solid #000; margin-bottom: 4px; }

@media print {
    html, body { background: white !important; }
    .paper-sheet { margin: 0; }
}
</style>

<!-- ══ EN-TÊTE ══════════════════════════════════════════════════ -->
<div class="cpn-header">
    <img src="<?= BASE_URL ?>public/images/logo_ordre_malte.png" alt="Logo">
    <div>
        <div class="cpn-title">Fiche de Suivi Femme Enceinte</div>
        <div class="cpn-subtitle">
            Consultation Prénatale (CPN) — Service de Gynécologie-Obstétrique
        </div>
    </div>
<?= entete_hopital() ?>
        Date d'impression : <?= $today ?>
    </div>
</div>

<!-- ══ INFOS SESSION ════════════════════════════════════════════ -->
<div class="session-band">
    <div class="session-field">
        <span class="session-label">Date de la consultation :</span>
        <span class="session-val"><?= $fdate($formData['date_registre'] ?? '') ?: $today ?></span>
    </div>
    <div class="session-field">
        <span class="session-label">Formation sanitaire :</span>
        <span class="session-val"><?= htmlspecialchars($formData['formation_sanitaire'] ?? 'Hôpital Saint-Jean de Malte') ?></span>
    </div>
    <div class="session-field">
        <span class="session-label">Responsable / Sage-femme :</span>
        <span class="session-val"><?= htmlspecialchars($formData['responsable'] ?? '') ?></span>
    </div>
</div>

<!-- ══ TABLEAU PRINCIPAL ═════════════════════════════════════════ -->
<table class="cpn-table">
    <thead>
        <tr>
            <th style="width:22px;">N°</th>
            <th style="width:140px;">Nom et Prénoms</th>
            <th style="width:72px;">DDN</th>
            <th style="width:72px;">Poids / AG</th>
            <th style="width:170px;">Signes et Symptômes</th>
            <th style="width:130px;">Diagnostic</th>
            <th style="width:130px;">Examens</th>
            <th style="width:68px;">Syphilis / VIH</th>
            <th style="width:40px;">TB</th>
            <th style="width:155px;">Prescriptions</th>
            <th style="width:50px;">LIA</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($rows as $i => $row):
        $syvhClass = '';
        if ($row['syvh'] === 'Pos') $syvhClass = 'td-syvh-pos';
        if ($row['syvh'] === 'Neg') $syvhClass = 'td-syvh-neg';
    ?>
    <tr>
        <td class="td-num"><?= $i + 1 ?></td>
        <td style="font-weight:bold;"><?= htmlspecialchars($row['nom']) ?></td>
        <td class="td-center"><?= htmlspecialchars($row['ddn']) ?></td>
        <td class="td-center"><?= htmlspecialchars($row['poids_ag']) ?></td>
        <td><?= nl2br(htmlspecialchars($row['signes'])) ?></td>
        <td><?= nl2br(htmlspecialchars($row['diag'])) ?></td>
        <td><?= nl2br(htmlspecialchars($row['examens'])) ?></td>
        <td class="td-center <?= $syvhClass ?>"><?= htmlspecialchars($row['syvh']) ?></td>
        <td class="td-center <?= $row['tb'] === 'Pos' ? 'td-syvh-pos' : ($row['tb'] === 'Neg' ? 'td-syvh-neg' : '') ?>">
            <?= htmlspecialchars($row['tb']) ?>
        </td>
        <td><?= nl2br(htmlspecialchars($row['presc'])) ?></td>
        <td class="td-center"><?= htmlspecialchars($row['lia']) ?></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
</table>

<!-- ══ OBSERVATIONS ═════════════════════════════════════════════ -->
<?php if (!empty($formData['observations'])): ?>
<div style="margin-top:8px;font-size:7.5pt;">
    <strong>Observations :</strong> <?= nl2br(htmlspecialchars($formData['observations'])) ?>
</div>
<?php endif; ?>

<!-- ══ SIGNATURES ════════════════════════════════════════════════ -->
<div class="cpn-footer-band">
    <div class="cpn-sig-block">
        <div class="cpn-sig-line"></div>
        <div style="font-size:7pt;">Signature du chef de service</div>
    </div>
    <div class="cpn-sig-block">
        <div class="cpn-sig-line"></div>
        <div style="font-size:7pt;font-weight:bold;">
            <?= htmlspecialchars($formData['responsable'] ?? ('Dr. ' . $praticien)) ?><br>
            <span style="font-weight:normal;">Sage-femme / Responsable CPN</span>
        </div>
    </div>
    <div class="cpn-sig-block">
        <div style="font-size:7pt;font-weight:bold;text-align:left;line-height:2;">
            Total consultations : <strong><?= count(array_filter($rows, fn($r) => $r['nom'] !== '')) ?></strong><br>
            Positifs Syphilis/VIH : <strong><?= count(array_filter($rows, fn($r) => $r['syvh'] === 'Pos')) ?></strong><br>
            Positifs TB : <strong><?= count(array_filter($rows, fn($r) => $r['tb'] === 'Pos')) ?></strong>
        </div>
    </div>
</div>

<div class="footer-note">
    Document généré le <?= $today ?> — Hôpital Saint-Jean de Malte — Service de Gynécologie-Obstétrique
</div>

</div></body></html>
