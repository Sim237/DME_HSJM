<?php
/* ============================================================
   VUE : Registre des consultations — page imprimable / PDF
   Variables attendues : $rows, $result['from'], $result['to'], $doctorNom
   ============================================================ */
$dateFrom  = $result['from'];
$dateTo    = $result['to'];
?><!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Registre Consultations — <?= htmlspecialchars($doctorNom) ?></title>
<style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: 'Segoe UI', Arial, sans-serif; font-size: 10px; color: #1e293b; background: #fff; }

    /* ── En-tête ── */
    .reg-header { display: flex; align-items: center; justify-content: space-between; padding: 16px 20px; border-bottom: 2px solid #1e40af; margin-bottom: 16px; }
    .reg-header-logo { display: flex; align-items: center; gap: 10px; }
    .reg-header-title { font-size: 15px; font-weight: 800; color: #1e40af; letter-spacing: .5px; }
    .reg-header-sub { font-size: 9px; color: #64748b; margin-top: 2px; }
    .reg-header-info { text-align: right; font-size: 9px; color: #64748b; line-height: 1.6; }
    .reg-header-info strong { color: #1e293b; }

    /* ── Méta ── */
    .reg-meta { display: flex; gap: 16px; padding: 8px 20px; background: #eff6ff; border-radius: 8px; margin: 0 20px 14px; font-size: 9px; }
    .reg-meta-item { display: flex; flex-direction: column; }
    .reg-meta-item .lbl { color: #64748b; text-transform: uppercase; letter-spacing: .5px; font-weight: 700; font-size: 8px; }
    .reg-meta-item .val { color: #1e293b; font-weight: 700; font-size: 11px; margin-top: 1px; }

    /* ── Tableau ── */
    .reg-table-wrap { padding: 0 20px; }
    table { width: 100%; border-collapse: collapse; font-size: 9px; }
    thead th {
        background: #1e40af; color: #fff;
        padding: 6px 5px; text-align: left;
        font-size: 8px; font-weight: 700; text-transform: uppercase; letter-spacing: .4px;
        white-space: nowrap;
    }
    thead th:first-child { border-radius: 6px 0 0 0; }
    thead th:last-child  { border-radius: 0 6px 0 0; }
    tbody tr:nth-child(even) td { background: #f8fafc; }
    tbody tr:hover td { background: #eff6ff; }
    tbody td { padding: 5px 5px; border-bottom: 1px solid #e2e8f0; vertical-align: top; line-height: 1.4; }
    td.center { text-align: center; }

    /* Badges */
    .badge-m { background: #dbeafe; color: #1e40af; border-radius: 4px; padding: 1px 5px; font-size: 8px; font-weight: 700; }
    .badge-f { background: #fce7f3; color: #be185d; border-radius: 4px; padding: 1px 5px; font-size: 8px; font-weight: 700; }
    .badge-vih-pos { background: #fee2e2; color: #b91c1c; border-radius: 4px; padding: 1px 5px; font-size: 8px; font-weight: 700; }
    .badge-vih-neg { background: #d1fae5; color: #065f46; border-radius: 4px; padding: 1px 5px; font-size: 8px; font-weight: 700; }
    .type-client { background: #f5f3ff; color: #5b21b6; border-radius: 4px; padding: 1px 4px; font-size: 8px; }

    /* ── Pied de page ── */
    .reg-footer { position: fixed; bottom: 0; left: 0; right: 0; padding: 6px 20px; border-top: 1px solid #e2e8f0; display: flex; justify-content: space-between; font-size: 8px; color: #94a3b8; background: #fff; }

    /* ── Impression ── */
    @media print {
        body { font-size: 8.5px; }
        .no-print { display: none !important; }
        @page { size: A4 landscape; margin: 8mm 6mm; }
        thead { display: table-header-group; }
        .reg-footer { position: fixed; }
    }

    /* ── Boutons (écran seulement) ── */
    .print-bar { padding: 12px 20px; background: #f8fafc; border-bottom: 1px solid #e2e8f0; display: flex; gap: 10px; align-items: center; }
    .btn-print { background: #1e40af; color: #fff; border: none; padding: 8px 20px; border-radius: 8px; font-weight: 700; font-size: .82rem; cursor: pointer; display: flex; align-items: center; gap: 6px; }
    .btn-print:hover { background: #1d4ed8; }
    .btn-close-pdf { background: #f1f5f9; color: #374151; border: 1px solid #e2e8f0; padding: 8px 16px; border-radius: 8px; font-weight: 600; font-size: .82rem; cursor: pointer; }
    .btn-close-pdf:hover { background: #e2e8f0; }
</style>
</head>
<body>

<!-- Barre d'impression (visible uniquement à l'écran) -->
<div class="print-bar no-print">
    <button class="btn-print" onclick="window.print()">
        🖨️ Imprimer / Enregistrer PDF
    </button>
    <button class="btn-close-pdf" onclick="window.close()">✕ Fermer</button>
    <span style="font-size:.75rem;color:#64748b;margin-left:8px">
        Pour sauvegarder en PDF : Imprimante → <strong>Enregistrer au format PDF</strong>
    </span>
</div>

<!-- En-tête -->
<div class="reg-header">
    <div class="reg-header-logo">
        <div style="width:40px;height:40px;border-radius:8px;background:#1e40af;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:900;font-size:1.1rem">H</div>
        <div>
            <div class="reg-header-title">REGISTRE DES CONSULTATIONS</div>
            <div class="reg-header-sub">Hôpital Saint-Jean de Malte — Njombé</div>
        </div>
    </div>
    <div class="reg-header-info">
        <div><strong>Médecin :</strong> <?= htmlspecialchars($doctorNom) ?></div>
        <div><strong>Période :</strong> <?= date('d/m/Y', strtotime($dateFrom)) ?> au <?= date('d/m/Y', strtotime($dateTo)) ?></div>
        <div><strong>Édité le :</strong> <?= date('d/m/Y à H:i') ?></div>
        <div><strong>Total :</strong> <?= count($rows) ?> consultation(s)</div>
    </div>
</div>

<!-- Méta stats -->
<div class="reg-meta">
    <div class="reg-meta-item">
        <span class="lbl">Total</span>
        <span class="val"><?= count($rows) ?></span>
    </div>
    <div class="reg-meta-item">
        <span class="lbl">Hommes</span>
        <span class="val"><?= count(array_filter($rows, fn($r) => $r['sexe'] === 'M')) ?></span>
    </div>
    <div class="reg-meta-item">
        <span class="lbl">Femmes</span>
        <span class="val"><?= count(array_filter($rows, fn($r) => $r['sexe'] === 'F')) ?></span>
    </div>
    <?php $vihPos = count(array_filter($rows, fn($r) => $r['statut_vih'] === 'Positif')); ?>
    <?php if($vihPos > 0): ?>
    <div class="reg-meta-item">
        <span class="lbl">VIH+</span>
        <span class="val" style="color:#dc2626"><?= $vihPos ?></span>
    </div>
    <?php endif; ?>
    <?php
    $byType = [];
    foreach($rows as $r) {
        $lbl = $r['type_client_label'];
        $byType[$lbl] = ($byType[$lbl] ?? 0) + 1;
    }
    foreach($byType as $lbl => $cnt): ?>
    <div class="reg-meta-item">
        <span class="lbl"><?= htmlspecialchars($lbl) ?></span>
        <span class="val"><?= $cnt ?></span>
    </div>
    <?php endforeach; ?>
</div>

<!-- Tableau -->
<div class="reg-table-wrap">
<table>
    <thead>
        <tr>
            <th style="width:25px">N°</th>
            <th style="width:72px">Date</th>
            <th style="width:80px">Nom</th>
            <th style="width:70px">Prénom</th>
            <th style="width:30px">Sexe</th>
            <th style="width:28px">Âge</th>
            <th style="width:42px">VIH</th>
            <th style="width:70px">Type client</th>
            <th style="width:60px">Domicile</th>
            <th style="width:70px">Contact</th>
            <th style="width:80px">Motif</th>
            <th style="width:90px">Diagnostic</th>
            <th style="width:90px">Bilans</th>
            <th>Traitements</th>
        </tr>
    </thead>
    <tbody>
    <?php if(empty($rows)): ?>
        <tr><td colspan="14" style="text-align:center;padding:20px;color:#94a3b8">Aucune consultation pour cette période.</td></tr>
    <?php else: ?>
    <?php foreach($rows as $i => $row): ?>
        <tr>
            <td class="center" style="color:#94a3b8;font-weight:700"><?= $i + 1 ?></td>
            <td><?= $row['date_consultation'] ? date('d/m/Y', strtotime($row['date_consultation'])) : '—' ?></td>
            <td style="font-weight:700"><?= htmlspecialchars(strtoupper($row['nom'] ?? '')) ?></td>
            <td><?= htmlspecialchars($row['prenom'] ?? '') ?></td>
            <td class="center">
                <?php if($row['sexe'] === 'M'): ?>
                    <span class="badge-m">M</span>
                <?php elseif($row['sexe'] === 'F'): ?>
                    <span class="badge-f">F</span>
                <?php else: echo '—'; endif; ?>
            </td>
            <td class="center"><?= $row['age'] !== null ? $row['age'].'a' : '—' ?></td>
            <td class="center">
                <?php if($row['statut_vih'] === 'Positif'): ?>
                    <span class="badge-vih-pos">+</span>
                <?php else: ?>
                    <span class="badge-vih-neg">—</span>
                <?php endif; ?>
            </td>
            <td><span class="type-client"><?= htmlspecialchars($row['type_client_label']) ?></span></td>
            <td><?= htmlspecialchars($row['ville'] ?? '—') ?></td>
            <td><?= htmlspecialchars($row['telephone'] ?? '—') ?></td>
            <td><?= htmlspecialchars(mb_strimwidth($row['motif_consultation'] ?? '—', 0, 80, '…')) ?></td>
            <td style="font-weight:600"><?= htmlspecialchars(mb_strimwidth($row['diagnostic_principal'] ?? '—', 0, 90, '…')) ?></td>
            <td style="color:#0891b2"><?= htmlspecialchars($row['bilans_labo'] ?? '—') ?></td>
            <td style="color:#7c3aed"><?= htmlspecialchars(mb_strimwidth($row['traitements_complets'] ?? '—', 0, 120, '…')) ?></td>
        </tr>
    <?php endforeach; ?>
    <?php endif; ?>
    </tbody>
</table>
</div>

<!-- Pied de page -->
<div class="reg-footer">
    <span>Registre généré par SimCare+ · <?= date('d/m/Y à H:i') ?></span>
    <span><?= htmlspecialchars($doctorNom) ?> · Période : <?= date('d/m/Y', strtotime($dateFrom)) ?> – <?= date('d/m/Y', strtotime($dateTo)) ?></span>
</div>

<script>
// Auto-print si paramètre autoprint=1
if (new URLSearchParams(window.location.search).get('autoprint') === '1') {
    window.addEventListener('load', () => setTimeout(() => window.print(), 400));
}
</script>
</body>
</html>
