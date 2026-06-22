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
/* ══════════════════════════════════════════════════════════════
   HELPERS PARTOGRAMME
   ══════════════════════════════════════════════════════════════ */

/* Valeur brute d'une cellule temporelle  bcf[3] */
$tsRaw = function(string $key, int $h) use ($formData) {
    $v = $formData[$key . '[' . $h . ']'] ?? '';
    return $v !== '' ? $v : null;
};

/* Valeur affichée (HTML-safe, tiret si vide) */
$ts = function(string $key, int $h) use ($tsRaw): string {
    $v = $tsRaw($key, $h);
    return $v !== null ? htmlspecialchars((string)$v) : '';
};

/* Cellule suivi mère/enfant  mere_temp[J0_M] */
$sv = function(string $key, string $jour, string $ms) use ($formData): string {
    $v = $formData[$key . '[J' . $jour . '_' . $ms . ']'] ?? '';
    return htmlspecialchars((string)$v);
};

/* Case PTME cochée / vide */
$ptme = function(string $key) use ($formData): string {
    return ($formData[$key] ?? '0') === '1' ? '☑' : '☐';
};

/* Formater une date stockée en Y-m-d ou Y-m-d H:i:s → d/m/Y */
$fdate = function(?string $d): string {
    if (!$d) return '—';
    try { return (new DateTime($d))->format('d/m/Y'); }
    catch (\Throwable $e) { return $d; }
};

/* Formater une heure H:i:s → H:i */
$ftime = function(?string $t): string {
    if (!$t) return '—';
    return substr($t, 0, 5);
};

/* ══════════════════════════════════════════════════════════════
   GÉNÉRATION SVG — GRAPHIQUE BCF
   ══════════════════════════════════════════════════════════════ */
function svgBCF(array $formData, callable $tsRaw): string {
    $W = 800; $H = 220;
    $pL = 52; $pR = 18; $pT = 22; $pB = 32;
    $cW = $W - $pL - $pR;
    $cH = $H - $pT - $pB;
    $minV = 80; $maxV = 200; /* OMS : 80–200 bpm */

    $hourX = fn($h) => $pL + ($h / 12) * $cW;
    $valY  = fn($v) => $pT + $cH * (1 - ($v - $minV) / ($maxV - $minV));

    /* Récupérer les valeurs */
    $vals = [];
    for ($h = 1; $h <= 12; $h++) {
        $v = $tsRaw('bcf', $h);
        $vals[$h] = ($v !== null && is_numeric($v)) ? (float)$v : null;
    }

    $svg  = '<svg viewBox="0 0 ' . $W . ' ' . $H . '" xmlns="http://www.w3.org/2000/svg" '
          . 'style="width:100%;height:auto;display:block;border:1px solid #ccc;border-radius:4px;">';

    /* Fond blanc */
    $svg .= '<rect width="' . $W . '" height="' . $H . '" fill="#fff"/>';

    /* Zone normale 120-160 */
    $y120 = $valY(120); $y160 = $valY(160);
    $svg .= '<rect x="' . $pL . '" y="' . round($y160, 1) . '" '
          . 'width="' . $cW . '" height="' . round($y120 - $y160, 1) . '" '
          . 'fill="rgba(5,150,105,0.10)"/>';

    /* Lignes horizontales + labels Y */
    for ($v = $minV; $v <= $maxV; $v += 20) {
        $y = round($valY($v), 1);
        $bold = ($v === 120 || $v === 160);
        $stroke = $bold ? '#059669' : '#e2e8f0';
        $dash   = $bold ? 'stroke-dasharray="5,4"' : '';
        $svg .= '<line x1="' . $pL . '" y1="' . $y . '" x2="' . ($W - $pR) . '" y2="' . $y . '" '
              . 'stroke="' . $stroke . '" stroke-width="' . ($bold ? '1.2' : '0.7') . '" ' . $dash . '/>';
        $svg .= '<text x="' . ($pL - 5) . '" y="' . ($y + 4) . '" '
              . 'font-size="9" text-anchor="end" fill="' . ($bold ? '#059669' : '#94a3b8') . '" '
              . 'font-weight="' . ($bold ? 'bold' : 'normal') . '">' . $v . '</text>';
    }

    /* Lignes verticales + labels H */
    for ($h = 0; $h <= 12; $h++) {
        $x = round($hourX($h), 1);
        $svg .= '<line x1="' . $x . '" y1="' . $pT . '" x2="' . $x . '" y2="' . ($H - $pB) . '" '
              . 'stroke="#e2e8f0" stroke-width="0.7"/>';
        if ($h > 0) {
            $svg .= '<text x="' . $x . '" y="' . ($H - $pB + 14) . '" '
                  . 'font-size="9" text-anchor="middle" fill="#94a3b8">H' . $h . '</text>';
        }
    }

    /* Label axe Y */
    $svg .= '<text x="11" y="' . ($pT + $cH / 2) . '" font-size="8" fill="#64748b" '
          . 'text-anchor="middle" transform="rotate(-90,11,' . ($pT + $cH / 2) . ')">bpm</text>';

    /* Courbe */
    $pts = [];
    foreach ($vals as $h => $v) {
        if ($v !== null) $pts[] = round($hourX($h), 1) . ',' . round($valY($v), 1);
    }
    if (count($pts) >= 2) {
        $svg .= '<polyline points="' . implode(' ', $pts) . '" fill="none" '
              . 'stroke="#be123c" stroke-width="2.5" stroke-linejoin="round" stroke-linecap="round"/>';
    }

    /* Points + étiquettes */
    foreach ($vals as $h => $v) {
        if ($v === null) continue;
        $x = round($hourX($h), 1);
        $y = round($valY($v), 1);
        $svg .= '<circle cx="' . $x . '" cy="' . $y . '" r="4" fill="white" stroke="#be123c" stroke-width="2"/>';
        $ly = ($y - 12 < $pT + 8) ? $y + 14 : $y - 8;
        $svg .= '<text x="' . $x . '" y="' . $ly . '" font-size="8.5" text-anchor="middle" '
              . 'fill="#be123c" font-weight="bold">' . $v . '</text>';
    }

    /* Légende */
    $lx = $pL + 6; $ly2 = $H - 4;
    $svg .= '<rect x="' . $lx . '" y="' . ($ly2 - 8) . '" width="14" height="6" fill="rgba(5,150,105,0.15)" stroke="#059669" stroke-width="0.8"/>';
    $svg .= '<text x="' . ($lx + 18) . '" y="' . $ly2 . '" font-size="8" fill="#059669">Zone normale 120–160 bpm (OMS : 80–200)</text>';
    $svg .= '<circle cx="' . ($lx + 140) . '" cy="' . ($ly2 - 3) . '" r="3.5" fill="white" stroke="#be123c" stroke-width="1.5"/>';
    $svg .= '<text x="' . ($lx + 148) . '" y="' . $ly2 . '" font-size="8" fill="#be123c">BCF mesuré</text>';

    $svg .= '</svg>';
    return $svg;
}

/* ══════════════════════════════════════════════════════════════
   GÉNÉRATION SVG — GRAPHIQUE DILATATION DU COL
   ══════════════════════════════════════════════════════════════ */
function svgCol(array $formData, callable $tsRaw): string {
    $W = 800; $H = 240;
    $pL = 38; $pR = 18; $pT = 22; $pB = 32;
    $cW = $W - $pL - $pR;
    $cH = $H - $pT - $pB;
    $minV = 0; $maxV = 10;

    $hourX = fn($h) => $pL + ($h / 12) * $cW;
    $valY  = fn($v) => $pT + $cH * (1 - ($v - $minV) / ($maxV - $minV));

    $vals = [];
    for ($h = 1; $h <= 12; $h++) {
        $v = $tsRaw('col', $h);
        $vals[$h] = ($v !== null && is_numeric($v)) ? (float)$v : null;
    }

    $svg  = '<svg viewBox="0 0 ' . $W . ' ' . $H . '" xmlns="http://www.w3.org/2000/svg" '
          . 'style="width:100%;height:auto;display:block;border:1px solid #ccc;border-radius:4px;">';

    $svg .= '<rect width="' . $W . '" height="' . $H . '" fill="#fff"/>';

    /* Lignes horizontales */
    for ($v = 0; $v <= 10; $v++) {
        $y = round($valY($v), 1);
        $svg .= '<line x1="' . $pL . '" y1="' . $y . '" x2="' . ($W - $pR) . '" y2="' . $y . '" '
              . 'stroke="#e2e8f0" stroke-width="0.7"/>';
        $svg .= '<text x="' . ($pL - 5) . '" y="' . ($y + 3.5) . '" '
              . 'font-size="9" text-anchor="end" fill="#94a3b8">' . $v . '</text>';
    }

    /* Lignes verticales */
    for ($h = 0; $h <= 12; $h++) {
        $x = round($hourX($h), 1);
        $svg .= '<line x1="' . $x . '" y1="' . $pT . '" x2="' . $x . '" y2="' . ($H - $pB) . '" '
              . 'stroke="#e2e8f0" stroke-width="0.7"/>';
        if ($h > 0) {
            $svg .= '<text x="' . $x . '" y="' . ($H - $pB + 14) . '" '
                  . 'font-size="9" text-anchor="middle" fill="#94a3b8">H' . $h . '</text>';
        }
    }

    /* Label axe Y */
    $svg .= '<text x="11" y="' . ($pT + $cH / 2) . '" font-size="8" fill="#64748b" '
          . 'text-anchor="middle" transform="rotate(-90,11,' . ($pT + $cH / 2) . ')">cm</text>';

    /* Ligne d'Alerte OMS : 4 cm à H1 → 10 cm à H7  (1 cm/heure) */
    $ax1 = round($hourX(1), 1); $ay1 = round($valY(4), 1);
    $ax2 = round($hourX(7), 1); $ay2 = round($valY(10), 1);
    $svg .= '<line x1="' . $ax1 . '" y1="' . $ay1 . '" x2="' . $ax2 . '" y2="' . $ay2 . '" '
          . 'stroke="#f59e0b" stroke-width="2" stroke-dasharray="8,5"/>';
    $svg .= '<text x="' . ($ax1 + 3) . '" y="' . ($ay1 - 4) . '" '
          . 'font-size="8.5" fill="#d97706" font-weight="bold">⚠ Alerte</text>';

    /* Ligne d'Action OMS : 4 cm à H5 → 10 cm à H11  (4h à droite de l'alerte) */
    $bx1 = round($hourX(5), 1); $by1 = round($valY(4), 1);
    $bx2 = round($hourX(11), 1); $by2 = round($valY(10), 1);
    $svg .= '<line x1="' . $bx1 . '" y1="' . $by1 . '" x2="' . $bx2 . '" y2="' . $by2 . '" '
          . 'stroke="#dc2626" stroke-width="2" stroke-dasharray="8,5"/>';
    $svg .= '<text x="' . ($bx1 + 3) . '" y="' . ($by1 - 4) . '" '
          . 'font-size="8.5" fill="#dc2626" font-weight="bold">🚨 Action</text>';

    /* Courbe dilatation */
    $pts = [];
    foreach ($vals as $h => $v) {
        if ($v !== null) $pts[] = round($hourX($h), 1) . ',' . round($valY($v), 1);
    }
    if (count($pts) >= 2) {
        $svg .= '<polyline points="' . implode(' ', $pts) . '" fill="none" '
              . 'stroke="#0369a1" stroke-width="2.5" stroke-linejoin="round" stroke-linecap="round"/>';
    }

    /* Points + étiquettes */
    foreach ($vals as $h => $v) {
        if ($v === null) continue;
        $x = round($hourX($h), 1);
        $y = round($valY($v), 1);
        $svg .= '<circle cx="' . $x . '" cy="' . $y . '" r="4" fill="white" stroke="#0369a1" stroke-width="2"/>';
        $ly = ($y - 12 < $pT + 8) ? $y + 14 : $y - 8;
        $svg .= '<text x="' . $x . '" y="' . $ly . '" font-size="8.5" text-anchor="middle" '
              . 'fill="#0369a1" font-weight="bold">' . $v . '</text>';
    }

    /* Légende */
    $lx = $pL + 6; $ly2 = $H - 4;
    $svg .= '<circle cx="' . ($lx + 5) . '" cy="' . ($ly2 - 3) . '" r="3.5" fill="white" stroke="#0369a1" stroke-width="1.5"/>';
    $svg .= '<text x="' . ($lx + 14) . '" y="' . $ly2 . '" font-size="8" fill="#0369a1">Dilatation du col</text>';
    $svg .= '<line x1="' . ($lx + 110) . '" y1="' . ($ly2 - 3) . '" x2="' . ($lx + 126) . '" y2="' . ($ly2 - 3) . '" '
          . 'stroke="#f59e0b" stroke-width="2" stroke-dasharray="5,3"/>';
    $svg .= '<text x="' . ($lx + 130) . '" y="' . $ly2 . '" font-size="8" fill="#d97706">Ligne d\'alerte</text>';
    $svg .= '<line x1="' . ($lx + 215) . '" y1="' . ($ly2 - 3) . '" x2="' . ($lx + 231) . '" y2="' . ($ly2 - 3) . '" '
          . 'stroke="#dc2626" stroke-width="2" stroke-dasharray="5,3"/>';
    $svg .= '<text x="' . ($lx + 235) . '" y="' . $ly2 . '" font-size="8" fill="#dc2626">Ligne d\'action</text>';

    $svg .= '</svg>';
    return $svg;
}

/* Vérifier si au moins un point BCF ou Col existe */
$hasBCF = false;
$hasCol = false;
for ($h = 1; $h <= 12; $h++) {
    if ($tsRaw('bcf', $h) !== null) $hasBCF = true;
    if ($tsRaw('col', $h) !== null) $hasCol = true;
}
?>

<style>
/* ── PAYSAGE ── */
@page { size: A4 landscape; margin: .7cm 1cm; }

.paper-sheet {
    width: 27.3cm !important;
    min-height: 18.6cm !important;
    padding: 0 !important;
    box-shadow: none !important;
    font-size: 8pt;
    line-height: 1.25;
    display: block !important;
}

/* ── En-tête ── */
.pt-header {
    display: grid;
    grid-template-columns: 52px 1fr auto;
    align-items: center;
    gap: 10px;
    border-bottom: 2.5px solid #000;
    padding-bottom: 5px;
    margin-bottom: 7px;
}
.pt-header img { height: 44px; }
.pt-header-title {
    text-align: center;
    font-size: 13pt;
    font-weight: 900;
    text-transform: uppercase;
    letter-spacing: 1px;
}
.pt-header-right { text-align: right; font-size: 7.5pt; font-weight: bold; line-height: 1.6; }

/* ── PTME ── */
.ptme-band {
    display: flex;
    align-items: center;
    gap: 18px;
    border: 1.5px solid #dc2626;
    border-radius: 3px;
    padding: 3px 10px;
    margin-bottom: 6px;
    font-size: 8.5pt;
    font-weight: bold;
}
.ptme-title { color: #dc2626; font-size: 9pt; margin-right: 4px; }
.ptme-item  { display: flex; align-items: center; gap: 4px; }

/* ── Identification ── */
.id-band {
    display: grid;
    grid-template-columns: repeat(5, 1fr);
    gap: 4px 10px;
    border: 1px solid #000;
    padding: 5px 8px;
    margin-bottom: 7px;
    background: #f9f9f9;
    font-size: 8pt;
}
.id-cell  { display: flex; align-items: baseline; gap: 3px; }
.id-label { font-weight: bold; white-space: nowrap; }
.id-val   { border-bottom: 1px solid #666; min-width: 55px; font-weight: bold; }

/* ── Titre de section ── */
.sec-title {
    font-weight: bold;
    font-size: 8pt;
    text-transform: uppercase;
    background: #1e293b;
    color: white;
    padding: 3px 8px;
    margin: 5px 0 3px;
    letter-spacing: .4px;
    display: flex;
    align-items: center;
    gap: 6px;
}

/* ── Tableaux de surveillance ── */
.surv-table {
    border-collapse: collapse;
    width: 100%;
    font-size: 7pt;
    table-layout: fixed;
    margin-bottom: 4px;
}
.surv-table th {
    background: #f1f5f9;
    border: 1px solid #888;
    padding: 3px 2px;
    text-align: center;
    font-weight: bold;
    white-space: nowrap;
}
.surv-table td {
    border: 1px solid #888;
    padding: 2px;
    text-align: center;
    font-weight: bold;
    font-size: 7pt;
    white-space: nowrap;
    overflow: hidden;
}
.surv-table .row-label {
    text-align: left;
    font-weight: bold;
    background: #f8fafc;
    padding: 2px 5px;
    font-size: 6.8pt;
    word-break: break-word;
    white-space: normal;
}
.surv-table .bcf-row td:not(.row-label) { color: #be123c; }
.surv-table .col-row td:not(.row-label) { color: #0369a1; }

/* ── Deux colonnes ── */
.two-col { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 5px; }

/* ── Tableaux suivi J0–J3 ── */
.suivi-table { border-collapse: collapse; width: 100%; font-size: 6.8pt; table-layout: fixed; }
.suivi-table th {
    background: #f1f5f9; border: 1px solid #888;
    padding: 2px; text-align: center; font-weight: bold;
}
.suivi-table td { border: 1px solid #888; padding: 1px 3px; text-align: center; }
.suivi-table .row-label {
    text-align: left; font-size: 6.5pt; padding: 1px 4px;
    background: #fafafa; white-space: normal; word-break: break-word;
}

/* ── Accouchement grid ── */
.acc-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 5px; margin-bottom: 5px; }
.acc-field .acc-label { font-weight: bold; font-size: 6.8pt; color: #555; margin-bottom: 1px; }
.acc-field .acc-val   { border-bottom: 1px solid #000; min-height: 13px; font-weight: bold; font-size: 8pt; padding: 0 2px; }

/* ── APGAR ── */
.apgar-good { color: #059669; font-weight: 900; }
.apgar-warn { color: #d97706; font-weight: 900; }
.apgar-bad  { color: #dc2626; font-weight: 900; }

/* ── Graphiques SVG ── */
.chart-box {
    border: 1px solid #e2e8f0;
    border-radius: 5px;
    overflow: hidden;
    margin-bottom: 10px;
}
.chart-box-title {
    font-weight: 700;
    font-size: 8pt;
    text-transform: uppercase;
    background: #1e293b;
    color: white;
    padding: 4px 10px;
    letter-spacing: .4px;
}

/* ── Signatures ── */
.sig-row { display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-top: 8px; }
.sig-box { text-align: center; }
.sig-line { height: 40px; border-bottom: 1px solid #000; margin-bottom: 3px; }

/* ── Saut de page ── */
.page-break { page-break-before: always; }

@media print {
    html, body { background: white !important; }
    .paper-sheet { margin: 0; }
}
</style>

<!-- ════════════════════════════════════════════════════════════════
     PAGE 1 — SURVEILLANCE DU TRAVAIL
     ════════════════════════════════════════════════════════════════ -->
<div class="pt-header">
    <img src="<?= BASE_URL ?>public/images/logo_ordre_malte.png" alt="Logo">
    <div>
        <div class="pt-header-title">Partogramme — OMS</div>
        <div style="text-align:center;font-size:7.5pt;color:#555;margin-top:2px;">
            Surveillance du travail et de l'accouchement
        </div>
    </div>
<?= entete_hopital() ?>
        Service de Gynécologie-Obstétrique<br>
        Date : <span style="border-bottom:1px solid #000;padding:0 10px;"><?= $today ?></span>
    </div>
</div>

<!-- PTME -->
<div class="ptme-band">
    <span class="ptme-title">⚕ PTME — Sérologies :</span>
    <span class="ptme-item"><span style="font-size:11pt;"><?= $ptme('ptme_hiv') ?></span>&nbsp;H.I.V</span>
    <span class="ptme-item"><span style="font-size:11pt;"><?= $ptme('ptme_aghbs') ?></span>&nbsp;AgHBs</span>
    <span class="ptme-item"><span style="font-size:11pt;"><?= $ptme('ptme_gsrh') ?></span>&nbsp;GSRH</span>
    <span style="margin-left:auto;font-size:7.5pt;color:#333;">
        N° Dossier : <strong><?= $fd('dossier_no') ?: $dossierNo ?></strong>
    </span>
</div>

<!-- IDENTIFICATION -->
<div class="id-band">
    <div class="id-cell">
        <span class="id-label">Patiente :</span>
        <span class="id-val"><?= $patNom ?></span>
    </div>
    <div class="id-cell">
        <span class="id-label">Âge :</span>
        <span class="id-val"><?= $patAge ?> ans</span>
    </div>
    <div class="id-cell">
        <span class="id-label">Gestité :</span>
        <span class="id-val"><?= $fd('geste') ?></span>
        &nbsp;<span class="id-label">Parité :</span>
        <span class="id-val"><?= $fd('parite') ?></span>
    </div>
    <div class="id-cell">
        <span class="id-label">AG :</span>
        <span class="id-val"><?= $fd('age_gestationnel') ?></span>&nbsp;SA
    </div>
    <div class="id-cell">
        <span class="id-label">Médecin :</span>
        <span class="id-val"><?= $fd('medecin_accoucheur') ?></span>
    </div>
    <div class="id-cell">
        <span class="id-label">Admission :</span>
        <span class="id-val"><?= $fdate($fd('date_admission')) ?></span>
    </div>
    <div class="id-cell">
        <span class="id-label">Heure :</span>
        <span class="id-val"><?= $ftime($fd('heure_admission')) ?></span>
    </div>
    <div class="id-cell">
        <span class="id-label">Rupture memb. :</span>
        <span class="id-val"><?= $fd('rupture_membranes') ?></span>
    </div>
    <div class="id-cell">
        <span class="id-label">Heure rupture :</span>
        <span class="id-val"><?= $ftime($fd('heure_rupture')) ?></span>
    </div>
    <div class="id-cell">
        <span class="id-label">Tél. :</span>
        <span class="id-val"><?= htmlspecialchars($patient['telephone'] ?? '') ?></span>
    </div>
</div>

<!-- BCF + LIQUIDE + CHEVAUCHEMENT -->
<div class="sec-title">🫀 Bruits du Cœur Fœtal (BCF) — Liquide amniotique — Chevauchement</div>
<table class="surv-table">
    <thead><tr>
        <th style="width:112px;text-align:left;">Paramètre</th>
        <?php for($h=1;$h<=12;$h++): ?><th>H<?=$h?></th><?php endfor; ?>
    </tr></thead>
    <tbody>
    <tr class="bcf-row">
        <td class="row-label">BCF (bpm)</td>
        <?php for($h=1;$h<=12;$h++): ?><td><?= $ts('bcf',$h) ?></td><?php endfor; ?>
    </tr>
    <tr>
        <td class="row-label">Liquide amniotique</td>
        <?php for($h=1;$h<=12;$h++): ?><td><?= $ts('liquide',$h) ?></td><?php endfor; ?>
    </tr>
    <tr>
        <td class="row-label">Chevauchement</td>
        <?php for($h=1;$h<=12;$h++): ?><td><?= $ts('chevauche',$h) ?></td><?php endfor; ?>
    </tr>
    </tbody>
</table>
<div style="font-size:6.2pt;color:#666;margin-bottom:5px;">
    <strong>Liquide :</strong> I=Intact · C=Clair · T=Teinté · S=Sanglant &nbsp;|&nbsp;
    <strong>Chevauchement :</strong> 0=Absent · +=Présent · ++=Important
</div>

<!-- DILATATION COL + DESCENTE -->
<div class="sec-title">⬆ Dilatation du col (cm) &amp; Descente de la tête</div>
<table class="surv-table">
    <thead><tr>
        <th style="width:112px;text-align:left;">Paramètre</th>
        <?php for($h=1;$h<=12;$h++): ?><th>H<?=$h?></th><?php endfor; ?>
    </tr></thead>
    <tbody>
    <tr class="col-row">
        <td class="row-label">Col — dilatation (cm)</td>
        <?php for($h=1;$h<=12;$h++): ?><td><?= $ts('col',$h) ?></td><?php endfor; ?>
    </tr>
    <tr>
        <td class="row-label">Descente de la tête (1–5)</td>
        <?php for($h=1;$h<=12;$h++): ?><td><?= $ts('descente',$h) ?></td><?php endfor; ?>
    </tr>
    </tbody>
</table>

<!-- CONTRACTIONS + OXYTOCINE -->
<div class="sec-title">📊 Contractions — Oxytocine — Médicaments</div>
<table class="surv-table">
    <thead><tr>
        <th style="width:112px;text-align:left;">Paramètre</th>
        <?php for($h=1;$h<=12;$h++): ?><th>H<?=$h?></th><?php endfor; ?>
    </tr></thead>
    <tbody>
    <tr>
        <td class="row-label">Contractions / 10 min</td>
        <?php for($h=1;$h<=12;$h++): ?><td><?= $ts('contractions',$h) ?></td><?php endfor; ?>
    </tr>
    <tr>
        <td class="row-label">Oxytocine U/L</td>
        <?php for($h=1;$h<=12;$h++): ?><td><?= $ts('ocytocine_u',$h) ?></td><?php endfor; ?>
    </tr>
    <tr>
        <td class="row-label">Gouttes / min</td>
        <?php for($h=1;$h<=12;$h++): ?><td><?= $ts('ocytocine_g',$h) ?></td><?php endfor; ?>
    </tr>
    <tr>
        <td class="row-label">Médicaments / IV</td>
        <?php for($h=1;$h<=12;$h++): ?><td style="font-size:6pt;"><?= $ts('medicaments',$h) ?></td><?php endfor; ?>
    </tr>
    </tbody>
</table>

<!-- SURVEILLANCE MATERNELLE -->
<div class="sec-title">🌡 Surveillance maternelle</div>
<table class="surv-table">
    <thead><tr>
        <th style="width:112px;text-align:left;">Paramètre</th>
        <?php for($h=1;$h<=12;$h++): ?><th>H<?=$h?></th><?php endfor; ?>
    </tr></thead>
    <tbody>
    <?php foreach([
        ['Pouls (bpm)',         'pouls'],
        ['TA (mmHg)',           'ta'],
        ['Température (°C)',    'temp'],
        ['Protéinurie',         'prot'],
        ['Cétone urinaire',     'cetone'],
        ['Volume urine (mL)',   'volume'],
    ] as [$label, $key]): ?>
    <tr>
        <td class="row-label"><?= $label ?></td>
        <?php for($h=1;$h<=12;$h++): ?><td><?= $ts($key,$h) ?></td><?php endfor; ?>
    </tr>
    <?php endforeach; ?>
    </tbody>
</table>

<!-- ════════════════════════════════════════════════════════════════
     PAGE 2 — GRAPHIQUES BCF & DILATATION DU COL
     ════════════════════════════════════════════════════════════════ -->
<div class="page-break"></div>

<!-- En-tête page 2 -->
<div class="pt-header">
    <img src="<?= BASE_URL ?>public/images/logo_ordre_malte.png" alt="Logo">
    <div>
        <div class="pt-header-title">Partogramme — Graphiques</div>
        <div style="text-align:center;font-size:7.5pt;color:#555;margin-top:2px;">
            Courbes BCF et dilatation du col — OMS
        </div>
    </div>
    <div class="pt-header-right">
        Patiente : <strong><?= $patNom ?></strong><br>
        Dossier N° <strong><?= $dossierNo ?></strong>
    </div>
</div>

<!-- GRAPHIQUE BCF -->
<div class="chart-box">
    <div class="chart-box-title">🫀 Courbe des Bruits du Cœur Fœtal (BCF) — 60 à 220 bpm &nbsp;|&nbsp; Zone normale : 120–160 bpm</div>
    <div style="padding:8px 8px 4px;">
        <?php if ($hasBCF): ?>
            <?= svgBCF($formData, $tsRaw) ?>
        <?php else: ?>
            <?= svgBCF($formData, fn($k,$h) => null) /* Afficher le graphique vide avec les références */ ?>
            <div style="text-align:center;font-size:7.5pt;color:#94a3b8;margin-top:4px;font-style:italic;">
                Aucune valeur BCF enregistrée — le graphique affiche uniquement les lignes de référence
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- GRAPHIQUE COL -->
<div class="chart-box">
    <div class="chart-box-title">📈 Courbe de Dilatation du col (0–10 cm) &nbsp;|&nbsp; Lignes OMS d'Alerte et d'Action</div>
    <div style="padding:8px 8px 4px;">
        <?php if ($hasCol): ?>
            <?= svgCol($formData, $tsRaw) ?>
        <?php else: ?>
            <?= svgCol($formData, fn($k,$h) => null) ?>
            <div style="text-align:center;font-size:7.5pt;color:#94a3b8;margin-top:4px;font-style:italic;">
                Aucune valeur de dilatation enregistrée — lignes OMS d'alerte et d'action affichées à titre de référence
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Rappel des valeurs en tableau compact -->
<div class="two-col" style="margin-top:10px;">
    <div>
        <div class="sec-title" style="font-size:7pt;padding:2px 8px;">BCF — Valeurs horaires (bpm)</div>
        <table class="surv-table">
            <tr>
                <?php for($h=1;$h<=12;$h++): ?>
                <th style="font-size:7pt;">H<?=$h?></th>
                <?php endfor; ?>
            </tr>
            <tr class="bcf-row">
                <?php for($h=1;$h<=12;$h++): ?>
                <td style="font-size:7.5pt;"><?= $ts('bcf',$h) ?: '—' ?></td>
                <?php endfor; ?>
            </tr>
        </table>
    </div>
    <div>
        <div class="sec-title" style="font-size:7pt;padding:2px 8px;">Col — Valeurs horaires (cm)</div>
        <table class="surv-table">
            <tr>
                <?php for($h=1;$h<=12;$h++): ?>
                <th style="font-size:7pt;">H<?=$h?></th>
                <?php endfor; ?>
            </tr>
            <tr class="col-row">
                <?php for($h=1;$h<=12;$h++): ?>
                <td style="font-size:7.5pt;"><?= $ts('col',$h) ?: '—' ?></td>
                <?php endfor; ?>
            </tr>
        </table>
    </div>
</div>

<!-- ════════════════════════════════════════════════════════════════
     PAGE 3 — ACCOUCHEMENT & POST-PARTUM
     ════════════════════════════════════════════════════════════════ -->
<div class="page-break"></div>

<!-- En-tête page 3 -->
<div class="pt-header">
    <img src="<?= BASE_URL ?>public/images/logo_ordre_malte.png" alt="Logo">
    <div>
        <div class="pt-header-title">Partogramme — Accouchement &amp; Post-Partum</div>
    </div>
    <div class="pt-header-right">
        Patiente : <strong><?= $patNom ?></strong><br>
        Dossier N° <strong><?= $dossierNo ?></strong>
    </div>
</div>

<!-- NAISSANCE & DÉLIVRANCE -->
<div class="sec-title">🏥 Naissance &amp; Délivrance</div>
<div class="acc-grid">
    <div class="acc-field">
        <div class="acc-label">Enfant né le</div>
        <div class="acc-val"><?= $fdate($fd('enfant_ne_le')) ?></div>
    </div>
    <div class="acc-field">
        <div class="acc-label">Heure de naissance</div>
        <div class="acc-val"><?= $ftime($fd('enfant_ne_heure')) ?></div>
    </div>
    <div class="acc-field">
        <div class="acc-label">Délivrance placenta à</div>
        <div class="acc-val"><?= $ftime($fd('delivrance_heure')) ?></div>
    </div>
    <div class="acc-field">
        <div class="acc-label">Poids NN (g)</div>
        <div class="acc-val"><?= $fd('poids_nn') ?></div>
    </div>
    <div class="acc-field">
        <div class="acc-label">Sexe</div>
        <div class="acc-val"><?= $fd('sexe_nn') === 'M' ? 'Masculin' : ($fd('sexe_nn') === 'F' ? 'Féminin' : $fd('sexe_nn')) ?></div>
    </div>
    <div class="acc-field">
        <div class="acc-label">Taille (cm)</div>
        <div class="acc-val"><?= $fd('taille_nn') ?></div>
    </div>
    <div class="acc-field">
        <div class="acc-label">PC (cm)</div>
        <div class="acc-val"><?= $fd('thorax_pc') ?></div>
    </div>
    <div class="acc-field">
        <div class="acc-label">PT (cm)</div>
        <div class="acc-val"><?= $fd('thorax_pt') ?></div>
    </div>
</div>

<!-- TRAVAIL -->
<div class="sec-title">⏱ Travail</div>
<div class="acc-grid">
    <div class="acc-field">
        <div class="acc-label">Phase de latence (I) — h</div>
        <div class="acc-val"><?= $fd('phase_latence') ?></div>
    </div>
    <div class="acc-field">
        <div class="acc-label">Phase active (II) — h</div>
        <div class="acc-val"><?= $fd('phase_active') ?></div>
    </div>
    <div class="acc-field">
        <div class="acc-label">Durée totale (I+II) — h</div>
        <div class="acc-val"><?= $fd('duree_totale') ?></div>
    </div>
    <div class="acc-field"></div>
</div>

<!-- TYPE D'ACCOUCHEMENT + APGAR côte à côte -->
<div class="two-col">
    <div>
        <div class="sec-title">🩺 Type d'accouchement</div>
        <?php $typeAcc = $fd('type_accouchement'); ?>
        <div style="display:flex;gap:10px;flex-wrap:wrap;margin:4px 0 3px;font-size:8pt;font-weight:bold;">
            <?php foreach(['Normal','Difficile','Traumatique','Césarienne','Ventouse'] as $opt): ?>
            <span><?= $typeAcc === $opt ? '☑' : '☐' ?> <?= htmlspecialchars($opt) ?></span>
            <?php endforeach; ?>
        </div>
        <?php if($typeAcc === 'Césarienne' && $fd('cesarienne_type')): ?>
        <div style="font-size:7.5pt;margin-bottom:3px;">Type césarienne : <strong><?= $fd('cesarienne_type') ?></strong></div>
        <?php endif; ?>
        <div style="font-size:7.5pt;margin-bottom:2px;">
            <strong>Déchirure :</strong>&nbsp;
            <?php foreach(['1er'=>'1er degré','2e'=>'2e degré','3e'=>'3e degré'] as $v=>$label): ?>
            <?= $fd('dechirure')===$v?'☑':'☐' ?> <?= $label ?>&nbsp;
            <?php endforeach; ?>
        </div>
        <div style="font-size:7.5pt;">
            <strong>Épisiotomie :</strong>&nbsp;
            <?php foreach(['Médiane','Médio-latérale gauche','Médio-latérale droite','Aucune'] as $v): ?>
            <?= $fd('episiotomie')===$v?'☑':'☐' ?> <?= $v ?>&nbsp;
            <?php endforeach; ?>
        </div>
    </div>
    <div>
        <div class="sec-title">⭐ Score d'APGAR</div>
        <?php
        $a1  = $fd('apgar_1min');  $a1i  = is_numeric($a1)  ? (int)$a1  : -1;
        $a5  = $fd('apgar_5min');  $a5i  = is_numeric($a5)  ? (int)$a5  : -1;
        $a10 = $fd('apgar_10min'); $a10i = is_numeric($a10) ? (int)$a10 : -1;
        $ac  = fn($v) => $v >= 7 ? 'apgar-good' : ($v >= 4 ? 'apgar-warn' : ($v >= 0 ? 'apgar-bad' : ''));
        ?>
        <div style="display:flex;gap:24px;margin:5px 0 4px;align-items:flex-end;">
            <div style="text-align:center;">
                <div style="font-size:6.5pt;color:#555;font-weight:bold;">À 1 min</div>
                <div class="<?= $ac($a1i) ?>" style="font-size:20pt;line-height:1.1;"><?= $a1 ?: '—' ?></div>
            </div>
            <div style="text-align:center;">
                <div style="font-size:6.5pt;color:#555;font-weight:bold;">À 5 min</div>
                <div class="<?= $ac($a5i) ?>" style="font-size:20pt;line-height:1.1;"><?= $a5 ?: '—' ?></div>
            </div>
            <div style="text-align:center;">
                <div style="font-size:6.5pt;color:#555;font-weight:bold;">À 10 min</div>
                <div class="<?= $ac($a10i) ?>" style="font-size:20pt;line-height:1.1;"><?= $a10 ?: '—' ?></div>
            </div>
        </div>
        <div style="font-size:8pt;margin-bottom:3px;">
            Aspect : <strong><?= $fd('aspect_nn') ?></strong>
            <?php if($fd('aspect_explications')): ?> — <?= $fd('aspect_explications') ?><?php endif; ?>
        </div>
        <div style="display:flex;gap:12px;font-size:7.5pt;font-weight:bold;">
            <?php foreach(['bebe_rechauffe'=>'Bébé réchauffé','vit_k1'=>'Vit K1','soins_cordon'=>'Soins cordon'] as $k=>$label): ?>
            <span><?= $label ?> : <span style="color:<?= $fd($k)==='Oui'?'#059669':'#dc2626' ?>;"><?= $fd($k) ?: '—' ?></span></span>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- POST-PARTUM IMMÉDIAT -->
<div class="sec-title">💊 Post-partum immédiat</div>
<div class="acc-grid">
    <div class="acc-field"><div class="acc-label">Utérus</div><div class="acc-val"><?= $fd('pp_uterus') ?></div></div>
    <div class="acc-field"><div class="acc-label">Épisiotomie</div><div class="acc-val"><?= $fd('pp_episiotomie') ?></div></div>
    <div class="acc-field"><div class="acc-label">TA post-partum</div><div class="acc-val"><?= $fd('pp_ta') ?></div></div>
    <div class="acc-field"><div class="acc-label">Hct</div><div class="acc-val"><?= $fd('pp_hct') ?></div></div>
    <div class="acc-field" style="grid-column:span 2;"><div class="acc-label">État du périnée</div><div class="acc-val"><?= $fd('pp_perinee') ?></div></div>
    <div class="acc-field"><div class="acc-label">Jour</div><div class="acc-val"><?= $fdate($fd('pp_jour')) ?></div></div>
    <div class="acc-field"></div>
</div>

<!-- SUIVI MÈRE + ENFANT J0–J3 -->
<div class="two-col">
    <!-- MÈRE -->
    <div>
        <div class="sec-title" style="font-size:7.5pt;">👩 Suivi de la Mère (J0 → J3)</div>
        <table class="suivi-table">
            <thead><tr>
                <th style="text-align:left;width:100px;">MÈRE</th>
                <th>J0 M</th><th>J0 S</th><th>J1 M</th><th>J1 S</th>
                <th>J2 M</th><th>J2 S</th><th>J3 M</th><th>J3 S</th>
            </tr></thead>
            <tbody>
            <?php foreach([
                ['T (°C)','mere_temp'],['Pouls','mere_pouls'],['TA','mere_ta'],
                ['État général','mere_etat'],['Mamelons — Normaux','mama_normal'],
                ['Mamelons — Fissurés','mama_fissures'],['Mamelons — Ombiliqués','mama_ombiliques'],
                ['Seins — Normaux','seins_normal'],['Seins — Douloureux','seins_douloureux'],
                ['Utérus','mere_uterus'],['Lochies — Fétides','lochies_fetides'],
                ['Lochies — Normales','lochies_normales'],['Périnée','perinee'],
                ['Jambes — Normales','jambes_normal'],['Jambes — Anormales','jambes_anorm'],
                ['Traitements','traitements'],
            ] as [$label,$key]): ?>
            <tr>
                <td class="row-label"><?= htmlspecialchars($label) ?></td>
                <?php for($j=0;$j<4;$j++): foreach(['M','S'] as $ms): ?>
                <td><?= $sv($key,(string)$j,$ms) ?></td>
                <?php endforeach; endfor; ?>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- ENFANT -->
    <div>
        <div class="sec-title" style="font-size:7.5pt;">👶 Suivi de l'Enfant (J0 → J3)</div>
        <table class="suivi-table">
            <thead><tr>
                <th style="text-align:left;width:110px;">ENFANT</th>
                <th>J0 M</th><th>J0 S</th><th>J1 M</th><th>J1 S</th>
                <th>J2 M</th><th>J2 S</th><th>J3 M</th><th>J3 S</th>
            </tr></thead>
            <tbody>
            <?php foreach([
                ['Température (°C)','enf_temp'],['Fontanelle — Bombée','font_bombee'],
                ['Fontanelle — Enfoncée','font_enfoncee'],['Fontanelle — Normale','font_normale'],
                ['Éveil — Oui','eveil_oui'],['Éveil — Non','eveil_non'],
                ['Yeux — Blancs','yeux_blancs'],['Yeux — Rouges','yeux_rouges'],
                ['Soins du cordon','cordon_soins'],['Peau — Cyanose','peau_cyanose'],
                ['Peau — Sèche','peau_seche'],['Peau — Normale','peau_normale'],
                ['Nombril — Normal','nombril_normal'],['Nombril — Infecté','nombril_infecte'],
                ['Poids (g)','enf_poids'],['Respiration','enf_resp'],
                ['Selles — Normales','selles_normal'],['Selles — Méconium','selles_meco'],
                ['Selles — Couleur','selles_couleur'],['Tétée — Bien','tetee_bien'],
                ['Tétée — Mal','tetee_mal'],
            ] as [$label,$key]): ?>
            <tr>
                <td class="row-label"><?= htmlspecialchars($label) ?></td>
                <?php for($j=0;$j<4;$j++): foreach(['M','S'] as $ms): ?>
                <td><?= $sv($key,(string)$j,$ms) ?></td>
                <?php endforeach; endfor; ?>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- NOTE DE DÉPART -->
<?php if($fd('note_depart')): ?>
<div class="sec-title">📋 Note de départ / Recommandations</div>
<div style="border:1px solid #aaa;min-height:24px;padding:4px 8px;font-size:8pt;font-weight:bold;">
    <?= nl2br($fd('note_depart')) ?>
</div>
<?php endif; ?>

<!-- SIGNATURES -->
<div class="sig-row">
    <div class="sig-box">
        <div class="sig-line"></div>
        <div style="font-size:7.5pt;">Signature de la patiente / représentant légal</div>
    </div>
    <div class="sig-box">
        <div class="sig-line"></div>
        <div style="font-size:8pt;font-weight:bold;">
            <?= htmlspecialchars($fd('medecin_accoucheur') ?: ('Dr. ' . $praticien)) ?><br>
            <span style="font-weight:normal;font-size:7.5pt;">Médecin accoucheur / Sage-femme</span>
        </div>
    </div>
</div>

<div class="footer-note">
    Partogramme généré le <?= $today ?> — Dossier N° <?= $dossierNo ?> — <?= $patNom ?> — HÔPITAL SAINT-JEAN DE MALTE
</div>

</div></body></html>
