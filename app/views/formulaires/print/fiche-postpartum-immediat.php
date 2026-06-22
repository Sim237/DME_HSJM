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

<style>
/* ── FORMAT PAYSAGE A4 ───────────────────────────────────── */
@page { size: A4 landscape; margin: .7cm 1cm; }

html, body { background: white; }

.paper-sheet {
    width: 27.3cm;
    min-height: unset;
    padding: .5cm .7cm .6cm;
    font-size: 8pt;
    line-height: 1.3;
}

/* ── EN-TÊTE ────────────────────────────────────────────── */
.hosp-header { margin-bottom: 6px; }
.hosp-left, .hosp-right { font-size: 7.5pt; }
.doc-title { font-size: 12pt; margin: 6px 0 8px; border-bottom-width: 1.5px; }

/* ── ENCADRÉ PATIENT ────────────────────────────────────── */
.patient-box {
    border: 1px solid #000;
    padding: 4px 8px;
    margin-bottom: 8px;
    display: flex;
    gap: 20px;
    flex-wrap: wrap;
    font-size: 8pt;
}
.patient-box .field-label { font-weight: normal; }
.patient-box .field-val { font-size: 8pt; min-width: 100px; }

/* ── TABLEAU PRINCIPAL ──────────────────────────────────── */
.surv-table {
    border-collapse: collapse;
    width: 100%;
    font-size: 7.5pt;
    table-layout: fixed;
}
.surv-table th {
    border: 1px solid #000;
    padding: 3px 2px;
    text-align: center;
    font-weight: bold;
    background: #e8e8e8;
    white-space: nowrap;
}
.surv-table th.th-group-h1  { background: #e8dff7; }
.surv-table th.th-group-h23 { background: #dbe8f7; }
.surv-table th.th-group-h46 { background: #d8f0e6; }
.surv-table th.th-transfert  { background: #fef9c3; font-size: 6.5pt; line-height: 1.2; }
.surv-table th.th-obs        { background: #f1f5f9; }

.surv-table td {
    border: 1px solid #000;
    padding: 2px 1px;
    text-align: center;
    vertical-align: middle;
    height: 14px;
}
.surv-table td.group-label {
    font-weight: 900;
    font-size: 7pt;
    text-transform: uppercase;
    writing-mode: vertical-rl;
    text-orientation: mixed;
    transform: rotate(180deg);
    text-align: center;
    padding: 4px 2px;
    width: 14px;
}
.surv-table td.group-label-mere { background: #f3e8ff; color: #6d28d9; }
.surv-table td.group-label-nne  { background: #dbeafe; color: #1d4ed8; }

.surv-table td.row-label {
    text-align: left;
    padding: 2px 4px;
    font-weight: 600;
    white-space: nowrap;
    background: #fafafa;
    width: 110px;
}
.surv-table tr.row-mere td.row-label { border-left: 2.5px solid #7c3aed; }
.surv-table tr.row-nne  td.row-label { border-left: 2.5px solid #1d4ed8; }

.surv-table td.val-mere { background: #fdf4ff; }
.surv-table td.val-nne  { background: #eff6ff; }
.surv-table td.val-transfert { background: #fefce8; }
.surv-table td.val-obs  { text-align: left; padding: 2px 3px; }

/* ── PIED DE PAGE SIGNATURE ─────────────────────────────── */
.sig-grid-3 {
    display: grid;
    grid-template-columns: 1fr 1fr 1fr;
    gap: 24px;
    margin-top: 16px;
}
.sig-block { text-align: center; }
.sig-line  { height: 40px; border-bottom: 1px solid #000; margin-bottom: 4px; }
.sig-label { font-size: 7.5pt; }

@media print {
    .paper-sheet { margin: 0; box-shadow: none; }
}
</style>

<!-- ── EN-TÊTE HÔPITAL ───────────────────────────────────── -->
<div class="hosp-header">
    <div class="hosp-left">
        HÔPITAL SAINT-JEAN DE MALTE<br>
        Service de Maternité<br>
        Tél. : 697 092 992
    </div>
    <div style="text-align:center; flex:1;">
        <div class="doc-title" style="border:none; margin:0; font-size:13pt; font-weight:900; text-transform:uppercase; letter-spacing:.5px;">
            Fiche de Surveillance Post Partum Immédiat
        </div>
        <div style="font-size:7.5pt; color:#444; margin-top:2px;">Salle de travail</div>
    </div>
    <div class="hosp-right">
        Dossier N° : <span class="field-val"><?= $dossierNo ?></span><br>
        Date : <span class="field-val"><?= $fd('date_surveillance') ?: $today ?></span>
    </div>
</div>

<!-- ── ENCADRÉ PATIENT ───────────────────────────────────── -->
<div class="patient-box">
    <div class="field-row" style="margin:0;">
        <span class="field-label">Nom &amp; Prénom :</span>&nbsp;
        <span class="field-val wide"><?= $fd('nom_patiente') ?: $patNom ?></span>
    </div>
    <div class="field-row" style="margin:0;">
        <span class="field-label">Âge :</span>&nbsp;
        <span class="field-val"><?= $patAge ?> ans</span>
    </div>
    <div class="field-row" style="margin:0;">
        <span class="field-label">Heure d'accouchement :</span>&nbsp;
        <span class="field-val"><?= $fd('heure_accouchement') ?></span>
    </div>
    <div class="field-row" style="margin:0;">
        <span class="field-label">Sage-femme / Infirmière :</span>&nbsp;
        <span class="field-val wide"><?= $fd('sage_femme') ?></span>
    </div>
</div>

<?php
/* ── Helpers ────────────────────────────────────────────── */
$cols = ['15min','30min','45min','60min','1h30','2h','2h30','3h','4h','5h','6h'];

$mereLignes = [
    ['État général',              'mere_etat_general'],
    ['État des conjonctives',     'mere_conjonctives'],
    ['Température',               'mere_temperature'],
    ['TA (tension artérielle)',   'mere_ta'],
    ['Pouls',                     'mere_pouls'],
    ['Globe de sécurité',         'mere_globe'],
    ['Saignement',                'mere_saignement'],
    ['Mise au sein',              'mere_mise_au_sein'],
];

$nneLignes = [
    ['Collyre',    'nne_collyre'],
    ['Coloration', 'nne_coloration'],
    ['Respiration','nne_respiration'],
    ['Réactivité', 'nne_reactivite'],
    ['Succion',    'nne_succion'],
    ['Vit K',      'nne_vitk'],
    ['Émargement', 'nne_emargement'],
];

$totalMere = count($mereLignes);
$totalNne  = count($nneLignes);

/* Lire une valeur de cellule */
$cell = function(string $key) use ($formData): string {
    $v = $formData[$key] ?? '';
    return htmlspecialchars((string)$v);
};
?>

<!-- ── TABLEAU PRINCIPAL ─────────────────────────────────── -->
<table class="surv-table">
    <thead>
        <tr>
            <th colspan="2" rowspan="2" style="width:124px;">Paramètre</th>
            <th colspan="4" class="th-group-h1">1ère heure</th>
            <th colspan="4" class="th-group-h23">2ème – 3ème heure</th>
            <th colspan="3" class="th-group-h46">4ème – 6ème heure</th>
            <th rowspan="2" class="th-transfert" style="width:52px;">TRANSFERT<br>SUITE DE COUCHES</th>
            <th rowspan="2" class="th-obs" style="min-width:90px;">Observations</th>
        </tr>
        <tr>
            <th class="th-group-h1">15 min</th>
            <th class="th-group-h1">30 min</th>
            <th class="th-group-h1">45 min</th>
            <th class="th-group-h1">60 min</th>
            <th class="th-group-h23">1h30</th>
            <th class="th-group-h23">2h</th>
            <th class="th-group-h23">2h30</th>
            <th class="th-group-h23">3h</th>
            <th class="th-group-h46">4h</th>
            <th class="th-group-h46">5h</th>
            <th class="th-group-h46">6h</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($mereLignes as $i => [$label, $key]): ?>
        <tr class="row-mere">
            <?php if ($i === 0): ?>
            <td class="group-label group-label-mere" rowspan="<?= $totalMere ?>">MÈRE</td>
            <?php endif; ?>
            <td class="row-label"><?= htmlspecialchars($label) ?></td>
            <?php foreach ($cols as $col): ?>
            <td class="val-mere"><?= $cell($key . '_' . $col) ?></td>
            <?php endforeach; ?>
            <td class="val-transfert"><?= $cell($key . '_transfert') ?></td>
            <td class="val-obs"><?= $cell($key . '_obs') ?></td>
        </tr>
        <?php endforeach; ?>

        <?php foreach ($nneLignes as $i => [$label, $key]): ?>
        <tr class="row-nne">
            <?php if ($i === 0): ?>
            <td class="group-label group-label-nne" rowspan="<?= $totalNne ?>">NNE</td>
            <?php endif; ?>
            <td class="row-label"><?= htmlspecialchars($label) ?></td>
            <?php foreach ($cols as $col): ?>
            <td class="val-nne"><?= $cell($key . '_' . $col) ?></td>
            <?php endforeach; ?>
            <td class="val-transfert"><?= $cell($key . '_transfert') ?></td>
            <td class="val-obs"><?= $cell($key . '_obs') ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<?php if (!empty($formData['observations_generales'])): ?>
<!-- ── OBSERVATIONS GÉNÉRALES ───────────────────────────── -->
<div style="margin-top:10px; border:1px solid #000; padding:4px 8px; font-size:7.5pt;">
    <strong>Observations générales :</strong> <?= htmlspecialchars($formData['observations_generales']) ?>
</div>
<?php endif; ?>

<!-- ── SIGNATURES ────────────────────────────────────────── -->
<div class="sig-grid-3">
    <div class="sig-block">
        <div class="sig-line"></div>
        <div class="sig-label">Sage-femme / Infirmière<br><?= $fd('sage_femme') ?></div>
    </div>
    <div class="sig-block">
        <div class="sig-line"></div>
        <div class="sig-label">Infirmière de garde</div>
    </div>
    <div class="sig-block">
        <div class="sig-line"></div>
        <div class="sig-label">Chef de service</div>
    </div>
</div>

<div class="footer-note">
    Document généré le <?= $today ?> — Dossier médical N° <?= $dossierNo ?> — HÔPITAL SAINT-JEAN DE MALTE
</div>

</div></body></html>
