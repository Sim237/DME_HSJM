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

/* ================================================================
   RÉCAPITULATIF COMPLET DOSSIER PATIENT — IMPRESSION A4 PAYSAGE
   Accessible : GET /patients/imprimer-recap/{id}
================================================================ */

// Calcul âge
$age = '—';
if (!empty($patient['date_naissance'])) {
    $age = (int)(new DateTime())->diff(new DateTime($patient['date_naissance']))->y . ' ans';
}

// Libellés
$catLabels = [
    'PER_OS'       => 'Per Os',
    'IV'           => 'Intra-Veineuse',
    'IM'           => 'Intra-Musculaire',
    'SC'           => 'Sous-Cutanée',
    'IR'           => 'Intra-Rectale',
    'NURSING'      => 'Nursing',
    'ALIMENTATION' => 'Alimentation',
    'SURVEILLANCE' => 'Surveillance',
];
$statutLabels = [
    'PLANIFIE' => ['label'=>'Planifié',   'color'=>'#2563eb'],
    'REALISE'  => ['label'=>'Réalisé',    'color'=>'#16a34a'],
    'ANNULE'   => ['label'=>'Annulé',     'color'=>'#dc2626'],
    'RETARD'   => ['label'=>'En retard',  'color'=>'#d97706'],
];
$atcdLabels = [
    'medical'            => 'Médicaux',
    'chirurgical'        => 'Chirurgicaux',
    'familial'           => 'Familiaux',
    'gyneco_obstetrique' => 'Gynéco-obstétricaux',
    'allergique'         => 'Allergiques',
];

function pr_val($v, string $fallback = '—'): string {
    return (isset($v) && $v !== '' && $v !== null) ? htmlspecialchars((string)$v) : $fallback;
}
function pr_date($d, string $format = 'd/m/Y'): string {
    if (!$d) return '—';
    try { return (new DateTime($d))->format($format); } catch(\Exception $e) { return '—'; }
}
function pr_datetime($d): string { return pr_date($d, 'd/m/Y à H:i'); }

$nomComplet = strtoupper($patient['nom'] ?? '') . ' ' . ucfirst(strtolower($patient['prenom'] ?? ''));
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Récapitulatif — <?= htmlspecialchars($nomComplet) ?></title>
<style>
/* ═══════════════════════════════════════════════════
   STYLES GÉNÉRAUX + PRINT
═══════════════════════════════════════════════════ */
* { box-sizing: border-box; margin: 0; padding: 0; }

body {
    font-family: 'Segoe UI', Arial, sans-serif;
    font-size: 9pt;
    color: #1e293b;
    background: #f0f4f8;
}

@page {
    size: A4 landscape;
    margin: 12mm 10mm 14mm 10mm;
}

@media print {
    body { background: white; }
    .no-print { display: none !important; }
    .page-break { page-break-before: always; }
    .avoid-break { page-break-inside: avoid; }
    table { page-break-inside: auto; }
    tr    { page-break-inside: avoid; page-break-after: auto; }
    thead { display: table-header-group; }
    tfoot { display: table-footer-group; }
}

/* ─── Bouton impression (masqué à l'impression) ─── */
.print-toolbar {
    position: fixed; top: 0; left: 0; right: 0; z-index: 1000;
    background: #1e293b; color: white;
    padding: 10px 20px;
    display: flex; align-items: center; justify-content: space-between;
    gap: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,.3);
}
.print-toolbar h2 { font-size: 13px; font-weight: 600; }
.btn-print {
    background: #2563eb; color: white; border: none;
    padding: 8px 22px; border-radius: 8px; cursor: pointer;
    font-size: 12px; font-weight: 700;
    display: flex; align-items: center; gap: 6px;
}
.btn-print:hover { background: #1d4ed8; }
.btn-back {
    background: rgba(255,255,255,.12); color: white; border: 1px solid rgba(255,255,255,.25);
    padding: 7px 16px; border-radius: 8px; cursor: pointer;
    font-size: 12px; text-decoration: none; display: flex; align-items: center; gap: 5px;
}
.btn-back:hover { background: rgba(255,255,255,.2); }

/* ─── Document ─── */
.document {
    max-width: 277mm;
    margin: 56px auto 20px;
    background: white;
    padding: 0;
}
@media print {
    .document { margin: 0; max-width: 100%; }
    .print-toolbar { display: none; }
}

/* ─── En-tête de page (répété sur chaque page via fixed) ─── */
.page-header {
    background: linear-gradient(135deg, #0f172a 0%, #1e3a8a 100%);
    color: white;
    padding: 10px 16px 8px;
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 0;
}
.page-header .hosp-name { font-size: 12pt; font-weight: 800; letter-spacing: .5px; }
.page-header .hosp-sub  { font-size: 7.5pt; opacity: .8; margin-top: 1px; }
.page-header .doc-title { font-size: 11pt; font-weight: 700; text-align: center; }
.page-header .doc-sub   { font-size: 7.5pt; opacity: .8; text-align: center; margin-top: 2px; }
.page-header .doc-meta  { font-size: 7.5pt; text-align: right; opacity: .85; }

/* ─── Bandeau patient ─── */
.patient-band {
    background: #eff6ff;
    border-bottom: 2px solid #2563eb;
    padding: 8px 16px;
    display: flex;
    gap: 24px;
    align-items: center;
    flex-wrap: wrap;
}
.patient-band .pat-name {
    font-size: 13pt; font-weight: 800; color: #1e3a8a; white-space: nowrap;
}
.patient-band .pat-info { display: flex; gap: 18px; flex-wrap: wrap; }
.info-chip {
    display: flex; flex-direction: column;
}
.info-chip .lbl { font-size: 6.5pt; font-weight: 700; text-transform: uppercase; color: #64748b; }
.info-chip .val { font-size: 8.5pt; font-weight: 600; color: #1e293b; }

/* ─── Sections ─── */
.section {
    padding: 8px 16px 6px;
    border-bottom: 1px solid #f1f5f9;
}
.section-title {
    font-size: 8.5pt; font-weight: 800; text-transform: uppercase;
    letter-spacing: .6px; color: #1e3a8a;
    border-left: 3px solid #2563eb;
    padding-left: 7px;
    margin-bottom: 6px;
    display: flex; align-items: center; gap: 6px;
}
.section-title .count-badge {
    font-size: 6.5pt; background: #dbeafe; color: #1e3a8a;
    border-radius: 20px; padding: 0 6px; font-weight: 700;
}

/* ─── Tableaux ─── */
table { width: 100%; border-collapse: collapse; font-size: 8pt; }
th {
    background: #f8fafc; color: #475569;
    font-size: 7pt; font-weight: 700; text-transform: uppercase; letter-spacing: .4px;
    padding: 4px 6px; text-align: left;
    border-bottom: 2px solid #e2e8f0;
}
td { padding: 4px 6px; border-bottom: 1px solid #f1f5f9; vertical-align: top; }
tr:last-child td { border-bottom: none; }
tr:nth-child(even) td { background: #fafafa; }

/* ─── Constantes vitales ─── */
.constante-header { background: #1e3a8a !important; color: white !important; }

/* ─── Badge statut ─── */
.badge {
    display: inline-block; padding: 1px 7px; border-radius: 20px;
    font-size: 6.5pt; font-weight: 700; white-space: nowrap;
}
.badge-done    { background: #dcfce7; color: #166534; }
.badge-pending { background: #fef3c7; color: #92400e; }
.badge-cancel  { background: #fee2e2; color: #991b1b; }
.badge-urgent  { background: #fee2e2; color: #991b1b; }
.badge-normal  { background: #dbeafe; color: #1e3a8a; }

/* ─── Résultat labo anormal ─── */
.res-abnormal { color: #dc2626; font-weight: 700; }
.res-normal   { color: #16a34a; }

/* ─── Bloc consultation ─── */
.consult-block {
    border: 1px solid #e2e8f0; border-radius: 8px;
    margin-bottom: 8px; overflow: hidden;
}
.consult-header {
    background: #f8fafc; padding: 5px 10px;
    display: flex; justify-content: space-between; align-items: center;
    border-bottom: 1px solid #e2e8f0;
}
.consult-header .ch-date { font-size: 8pt; font-weight: 700; color: #1e3a8a; }
.consult-header .ch-med  { font-size: 7.5pt; color: #475569; }
.consult-grid {
    display: grid; grid-template-columns: 1fr 1fr 1fr;
    gap: 0;
}
.cg-cell {
    padding: 5px 8px; border-right: 1px solid #f1f5f9;
}
.cg-cell:last-child { border-right: none; }
.cg-label { font-size: 6.5pt; font-weight: 700; text-transform: uppercase; color: #64748b; margin-bottom: 2px; }
.cg-value { font-size: 7.5pt; color: #1e293b; line-height: 1.4; }

/* ─── Constante anomalie ─── */
.val-alert { color: #dc2626; font-weight: 700; }

/* ─── Footer ─── */
.doc-footer {
    padding: 6px 16px 4px;
    display: flex; justify-content: space-between; align-items: center;
    border-top: 1px solid #e2e8f0;
    font-size: 7pt; color: #94a3b8;
    background: #f8fafc;
}

/* ─── Espace vide ─── */
.empty-row td { color: #94a3b8; font-style: italic; text-align: center; }
</style>
</head>
<body>

<!-- ══ BARRE D'OUTILS (masquée à l'impression) ══ -->
<div class="print-toolbar no-print">
    <div style="display:flex;align-items:center;gap:16px;">
        <a href="javascript:history.back()" class="btn-back">← Retour</a>
        <h2>Récapitulatif complet — <?= htmlspecialchars($nomComplet) ?></h2>
    </div>
    <div style="display:flex;align-items:center;gap:10px;">
        <span style="font-size:11px;opacity:.7;">Format A4 paysage · Impression multi-pages</span>
        <button class="btn-print" onclick="window.print()">
            <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M6 9V2h12v7M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2M6 14h12v8H6z"/></svg>
            Imprimer / Exporter PDF
        </button>
    </div>
</div>

<!-- ══ DOCUMENT ══ -->
<div class="document">

<!-- ── EN-TÊTE ── -->
<div class="page-header">
    <div>
        <?php require_once __DIR__ . '/../partials/entete_hopital.php'; echo entete_hopital(['compact' => true]); ?>
        <div class="hosp-sub">Service Médical · Dossier Médical Électronique</div>
    </div>
    <div>
        <div class="doc-title">RÉCAPITULATIF MÉDICAL COMPLET</div>
        <div class="doc-sub">Document confidentiel — Usage médical exclusif</div>
    </div>
    <div class="doc-meta">
        Imprimé le <?= date('d/m/Y à H:i') ?><br>
        Par : <?= $imprimente_par ? htmlspecialchars(($imprimente_par['prenom']??'').' '.($imprimente_par['nom']??'')) : '—' ?><br>
        <?php if ($imprimente_par && !empty($imprimente_par['specialite'])): ?>
        <?= htmlspecialchars($imprimente_par['specialite']) ?>
        <?php endif; ?>
    </div>
</div>

<!-- ── BANDEAU PATIENT ── -->
<div class="patient-band">
    <div class="pat-name"><?= htmlspecialchars($nomComplet) ?></div>
    <div class="pat-info">
        <div class="info-chip"><span class="lbl">N° Dossier</span><span class="val"><?= pr_val($patient['dossier_numero']) ?></span></div>
        <div class="info-chip"><span class="lbl">Né(e) le</span><span class="val"><?= pr_date($patient['date_naissance']) ?> (<?= $age ?>)</span></div>
        <div class="info-chip"><span class="lbl">Sexe</span><span class="val"><?= $patient['sexe'] === 'M' ? 'Masculin' : 'Féminin' ?></span></div>
        <div class="info-chip"><span class="lbl">Groupe sanguin</span><span class="val"><?= pr_val($patient['groupe_sanguin']) ?></span></div>
        <div class="info-chip"><span class="lbl">Téléphone</span><span class="val"><?= pr_val($patient['telephone']) ?></span></div>
        <?php if (!empty($patient['allergies'])): ?>
        <div class="info-chip"><span class="lbl" style="color:#dc2626;">⚠ Allergies</span><span class="val" style="color:#dc2626;font-weight:700;"><?= pr_val($patient['allergies']) ?></span></div>
        <?php endif; ?>
        <?php if ($hospitalisation): ?>
        <div class="info-chip"><span class="lbl">Service</span><span class="val"><?= pr_val($hospitalisation['nom_service']) ?></span></div>
        <div class="info-chip"><span class="lbl">Chambre / Lit</span><span class="val"><?= pr_val($hospitalisation['nom_chambre']) ?> / <?= pr_val($hospitalisation['nom_lit']) ?></span></div>
        <div class="info-chip"><span class="lbl">Admis le</span><span class="val"><?= pr_date($hospitalisation['date_admission']) ?></span></div>
        <?php endif; ?>
    </div>
</div>

<!-- ════════════════════════════════════════════════
     SECTION 1 — ANTÉCÉDENTS & ALLERGIES
════════════════════════════════════════════════ -->
<?php if (!empty($antecedents) || !empty($patient['antecedents_medicaux']) || !empty($patient['allergies'])): ?>
<div class="section avoid-break">
    <div class="section-title">
        📋 Antécédents &amp; Allergies
        <span class="count-badge"><?= array_sum(array_map('count', $antecedents)) ?> entrée(s)</span>
    </div>
    <table>
        <thead>
            <tr>
                <th style="width:130px;">Catégorie</th>
                <th>Description</th>
                <th style="width:90px;">Date</th>
            </tr>
        </thead>
        <tbody>
            <?php
            // Depuis la table antecedents
            foreach ($antecedents as $type => $items) {
                foreach ($items as $idx => $atcd):
            ?>
            <tr>
                <?php if ($idx === 0): ?>
                <td rowspan="<?= count($items) ?>" style="font-weight:700;color:#1e3a8a;vertical-align:top;">
                    <?= htmlspecialchars($atcdLabels[$type] ?? ucfirst($type)) ?>
                </td>
                <?php endif; ?>
                <td><?= pr_val($atcd['description']) ?></td>
                <td><?= pr_date($atcd['date_survenue']) ?></td>
            </tr>
            <?php endforeach; } ?>
            <?php
            // Depuis les champs snapshot de la consultation si table vide
            if (empty($antecedents)):
                $snapFields = [
                    'Médicaux'      => $patient['antecedents_medicaux'] ?? '',
                    'Chirurgicaux'  => $patient['antecedents_chirurgicaux'] ?? '',
                    'Familiaux'     => $patient['antecedents_familiaux'] ?? '',
                    'Allergies'     => $patient['allergies'] ?? '',
                ];
                $hasSnap = array_filter($snapFields);
                if ($hasSnap): foreach ($snapFields as $lbl => $val): if ($val): ?>
            <tr>
                <td style="font-weight:700;color:#1e3a8a;"><?= $lbl ?></td>
                <td colspan="2"><?= nl2br(pr_val($val)) ?></td>
            </tr>
            <?php endif; endforeach; else: ?>
            <tr class="empty-row"><td colspan="3">Aucun antécédent enregistré</td></tr>
            <?php endif; endif; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<!-- ════════════════════════════════════════════════
     SECTION 2 — CONSTANTES VITALES
════════════════════════════════════════════════ -->
<?php if (!empty($constantes)): ?>
<div class="section avoid-break">
    <div class="section-title">
        📈 Constantes Vitales
        <span class="count-badge"><?= count($constantes) ?> mesure(s)</span>
    </div>
    <table>
        <thead>
            <tr class="constante-header">
                <th style="color:white;background:#1e3a8a;">Date / Heure</th>
                <th style="color:white;background:#1e3a8a;">T° (°C)</th>
                <th style="color:white;background:#1e3a8a;">TA (mmHg)</th>
                <th style="color:white;background:#1e3a8a;">Pouls (bpm)</th>
                <th style="color:white;background:#1e3a8a;">SpO₂ (%)</th>
                <th style="color:white;background:#1e3a8a;">FR (/min)</th>
                <th style="color:white;background:#1e3a8a;">Poids (kg)</th>
                <th style="color:white;background:#1e3a8a;">IMC</th>
                <th style="color:white;background:#1e3a8a;">Relevé par</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($constantes as $c):
            $imc = ($c['poids'] && $c['taille'] && $c['taille'] > 0) ? round($c['poids'] / (($c['taille']/100)**2), 1) : null;
            $tAlert = $c['temperature'] && ($c['temperature'] > 37.5 || $c['temperature'] < 36);
            $pAlert = $c['frequence_cardiaque'] && ($c['frequence_cardiaque'] > 100 || $c['frequence_cardiaque'] < 50);
            $sAlert = $c['saturation_oxygene'] && $c['saturation_oxygene'] < 95;
        ?>
        <tr>
            <td><?= pr_datetime($c['date_mesure']) ?></td>
            <td class="<?= $tAlert ? 'val-alert' : '' ?>"><?= pr_val($c['temperature']) ?></td>
            <td><?php
                $sys = $c['pression_arterielle_systolique'] ?? null;
                $dia = $c['pression_arterielle_diastolique'] ?? null;
                if ($sys || $dia) echo pr_val($sys) . ' / ' . pr_val($dia);
                else echo '—';
            ?></td>
            <td class="<?= $pAlert ? 'val-alert' : '' ?>"><?= pr_val($c['frequence_cardiaque']) ?></td>
            <td class="<?= $sAlert ? 'val-alert' : '' ?>"><?= pr_val($c['saturation_oxygene']) ?></td>
            <td><?= pr_val($c['frequence_respiratoire'] ?? null) ?></td>
            <td><?= pr_val($c['poids']) ?></td>
            <td><?= $imc ?? '—' ?></td>
            <td><?= pr_val(trim(($c['inf_prenom']??'').' '.($c['inf_nom']??''))) ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<!-- ════════════════════════════════════════════════
     SECTION 3 — CONSULTATIONS
════════════════════════════════════════════════ -->
<?php if (!empty($consultations)): ?>
<div class="section">
    <div class="section-title">
        🩺 Consultations Médicales
        <span class="count-badge"><?= count($consultations) ?> consultation(s)</span>
    </div>

    <?php foreach ($consultations as $ci => $c): ?>
    <div class="consult-block avoid-break" <?= $ci > 0 ? 'style="margin-top:6px;"' : '' ?>>
        <div class="consult-header">
            <div>
                <span class="ch-date">
                    <?= pr_datetime($c['date_consultation']) ?>
                    — <?= $c['type_consultation'] === 'interne' ? 'Interne' : 'Externe' ?>
                </span>
            </div>
            <div class="ch-med">
                Dr <?= pr_val(trim(($c['med_prenom']??'').' '.($c['med_nom']??''))) ?>
                <?php if (!empty($c['specialite'])): ?> · <?= htmlspecialchars($c['specialite']) ?><?php endif; ?>
            </div>
            <div>
                <span class="badge <?= $c['statut']==='terminee'||$c['statut']==='validee' ? 'badge-done' : 'badge-pending' ?>">
                    <?= ucfirst($c['statut'] ?? '—') ?>
                </span>
                <?php if (!empty($c['priorite']) && $c['priorite'] !== 'NORMALE'): ?>
                <span class="badge badge-urgent"><?= htmlspecialchars($c['priorite']) ?></span>
                <?php endif; ?>
            </div>
        </div>

        <?php if (!empty($c['systeme_principal']) || !empty($c['symptomes_systemiques'])): ?>
        <div style="padding:5px 10px; background:#f5f3ff; border-bottom:1px solid #ede9fe; display:flex; align-items:center; flex-wrap:wrap; gap:4px;">
            <span style="font-size:6.5pt;font-weight:800;text-transform:uppercase;color:#7c3aed;margin-right:4px;">💜 Enquête systémique</span>
            <?php if (!empty($c['systeme_principal'])): ?>
            <span style="font-size:7pt;font-weight:700;color:#7c3aed;background:#ede9fe;padding:1px 7px;border-radius:20px;margin-right:4px;">
                <?= htmlspecialchars($c['systeme_principal']) ?>
            </span>
            <?php endif; ?>
            <?php if (!empty($c['symptomes_systemiques'])): ?>
            <?php foreach (explode(',', $c['symptomes_systemiques']) as $sym): $sym = trim($sym); if ($sym): ?>
            <span style="font-size:7pt;background:#ede9fe;color:#5b21b6;padding:1px 6px;border-radius:20px;"><?= htmlspecialchars($sym) ?></span>
            <?php endif; endforeach; ?>
            <?php endif; ?>
            <?php if (!empty($c['commentaires_systemiques'])): ?>
            <span style="font-size:7pt;color:#64748b;font-style:italic;margin-left:4px;"><?= htmlspecialchars($c['commentaires_systemiques']) ?></span>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <div class="consult-grid">
            <!-- Col 1 -->
            <div>
                <?php if (!empty($c['motif_consultation'])): ?>
                <div class="cg-cell">
                    <div class="cg-label">Motif de consultation</div>
                    <div class="cg-value"><?= nl2br(pr_val($c['motif_consultation'])) ?></div>
                </div>
                <?php endif; ?>
                <?php if (!empty($c['histoire_maladie'])): ?>
                <div class="cg-cell" style="border-top:1px solid #f1f5f9;">
                    <div class="cg-label">Histoire de la maladie</div>
                    <div class="cg-value"><?= nl2br(pr_val($c['histoire_maladie'])) ?></div>
                </div>
                <?php endif; ?>
                <?php if (!empty($c['examen_physique'])): ?>
                <div class="cg-cell" style="border-top:1px solid #f1f5f9;">
                    <div class="cg-label">Examen physique</div>
                    <div class="cg-value"><?= nl2br(pr_val($c['examen_physique'])) ?></div>
                </div>
                <?php endif; ?>
                <!-- Constantes de la consultation -->
                <?php
                $hasCst = $c['temperature'] || $c['frequence_cardiaque'] || $c['tension_systolique'];
                if ($hasCst):
                ?>
                <div class="cg-cell" style="border-top:1px solid #f1f5f9;">
                    <div class="cg-label">Constantes (consultation)</div>
                    <div class="cg-value">
                        <?php if ($c['temperature']): ?>T° <?= pr_val($c['temperature']) ?>°C &nbsp; <?php endif; ?>
                        <?php if ($c['frequence_cardiaque']): ?>FC <?= pr_val($c['frequence_cardiaque']) ?> bpm &nbsp; <?php endif; ?>
                        <?php if ($c['tension_systolique']): ?>TA <?= pr_val($c['tension_systolique']) ?>/<?= pr_val($c['tension_diastolique']) ?> mmHg &nbsp; <?php endif; ?>
                        <?php if ($c['poids']): ?>Poids <?= pr_val($c['poids']) ?> kg<?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Col 2 -->
            <div>
                <?php if (!empty($c['resume_syndromique'])): ?>
                <div class="cg-cell">
                    <div class="cg-label">Résumé syndromique</div>
                    <div class="cg-value"><?= nl2br(pr_val($c['resume_syndromique'])) ?></div>
                </div>
                <?php endif; ?>
                <?php if (!empty($c['hypotheses_diagnostiques'])): ?>
                <div class="cg-cell" style="border-top:1px solid #f1f5f9;">
                    <div class="cg-label">Hypothèses diagnostiques</div>
                    <div class="cg-value"><?= nl2br(pr_val($c['hypotheses_diagnostiques'])) ?></div>
                </div>
                <?php endif; ?>
                <?php if (!empty($c['diagnostic_principal'])): ?>
                <div class="cg-cell" style="border-top:1px solid #f1f5f9;background:#f0fdf4;">
                    <div class="cg-label" style="color:#166534;">Diagnostic principal</div>
                    <div class="cg-value" style="font-weight:700;color:#166534;"><?= nl2br(pr_val($c['diagnostic_principal'])) ?></div>
                </div>
                <?php endif; ?>
                <?php if (!empty($c['diagnostics_differentiels'])): ?>
                <div class="cg-cell" style="border-top:1px solid #f1f5f9;">
                    <div class="cg-label">Diagnostics différentiels</div>
                    <div class="cg-value"><?= nl2br(pr_val($c['diagnostics_differentiels'])) ?></div>
                </div>
                <?php endif; ?>
                <?php
                // Antécédents snapshot de la consultation
                $atcdSnap = array_filter([
                    'Médicaux'     => $c['atcd_medicaux'] ?? '',
                    'Chirurgicaux' => $c['atcd_chirurgicaux'] ?? '',
                    'Familiaux'    => $c['atcd_familiaux'] ?? '',
                    'Allergies'    => $c['atcd_allergies'] ?? '',
                ]);
                if (!empty($atcdSnap)): ?>
                <div class="cg-cell" style="border-top:1px solid #f1f5f9;">
                    <div class="cg-label">Antécédents (saisis à la consultation)</div>
                    <div class="cg-value">
                    <?php foreach ($atcdSnap as $lbl => $val): ?>
                        <strong><?= $lbl ?> :</strong> <?= nl2br(pr_val($val)) ?><br>
                    <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Col 3 -->
            <div>
                <?php if (!empty($c['plan_traitement'])): ?>
                <div class="cg-cell" style="background:#fffbeb;">
                    <div class="cg-label" style="color:#92400e;">Plan de traitement</div>
                    <div class="cg-value"><?= nl2br(pr_val($c['plan_traitement'])) ?></div>
                </div>
                <?php endif; ?>
                <?php if (!empty($c['traitement_non_medicamenteux'])): ?>
                <div class="cg-cell" style="border-top:1px solid #f1f5f9;">
                    <div class="cg-label">Traitement non médicamenteux</div>
                    <div class="cg-value"><?= nl2br(pr_val($c['traitement_non_medicamenteux'])) ?></div>
                </div>
                <?php endif; ?>
                <?php if (!empty($c['surveillance'])): ?>
                <div class="cg-cell" style="border-top:1px solid #f1f5f9;">
                    <div class="cg-label">Surveillance</div>
                    <div class="cg-value"><?= nl2br(pr_val($c['surveillance'])) ?></div>
                </div>
                <?php endif; ?>
                <?php if (!empty($c['examens_paracliniques'])): ?>
                <div class="cg-cell" style="border-top:1px solid #f1f5f9;">
                    <div class="cg-label">Examens para-cliniques demandés</div>
                    <div class="cg-value"><?= nl2br(pr_val($c['examens_paracliniques'])) ?></div>
                </div>
                <?php endif; ?>
                <?php if (!empty($c['notes_suivi'])): ?>
                <div class="cg-cell" style="border-top:1px solid #f1f5f9;">
                    <div class="cg-label">Notes de suivi</div>
                    <div class="cg-value"><?= nl2br(pr_val($c['notes_suivi'])) ?></div>
                </div>
                <?php endif; ?>
                <?php if (!empty($c['date_prochain_rdv'])): ?>
                <div class="cg-cell" style="border-top:1px solid #f1f5f9;">
                    <div class="cg-label">Prochain RDV</div>
                    <div class="cg-value" style="font-weight:700;"><?= pr_date($c['date_prochain_rdv']) ?></div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- ════════════════════════════════════════════════
     SECTION 4 — ORDONNANCES / TRAITEMENTS
════════════════════════════════════════════════ -->
<?php if (!empty($ordonnances)): ?>
<div class="section page-break">
    <div class="section-title">
        💊 Ordonnances &amp; Traitements Prescrits
        <span class="count-badge"><?= count($ordonnances) ?> ordonnance(s)</span>
    </div>

    <?php foreach ($ordonnances as $oi => $ord): ?>
    <div class="avoid-break" style="margin-bottom:8px;">
        <table>
            <thead>
                <tr>
                    <th colspan="5" style="background:#1e3a8a;color:white;font-size:7.5pt;">
                        Ordonnance du <?= pr_datetime($ord['date_creation']) ?>
                        — Dr <?= pr_val(trim(($ord['med_prenom']??'').' '.($ord['med_nom']??''))) ?>
                        <?php if (!empty($ord['statut'])): ?>
                        <span class="badge <?= $ord['statut']==='delivree' ? 'badge-done' : 'badge-pending' ?>" style="margin-left:8px;">
                            <?= ucfirst($ord['statut']) ?>
                        </span>
                        <?php endif; ?>
                    </th>
                </tr>
                <tr>
                    <th>Médicament</th>
                    <th>Dosage</th>
                    <th>Posologie / Fréquence</th>
                    <th>Durée</th>
                    <th>Instructions</th>
                </tr>
            </thead>
            <tbody>
            <?php if (!empty($ord['medicaments'])): ?>
                <?php foreach ($ord['medicaments'] as $med): ?>
                <tr>
                    <td style="font-weight:600;"><?= pr_val($med['nom_med'] ?? $med['nom_medicament'] ?? null) ?></td>
                    <td><?= pr_val($med['dosage'] ?? $med['dosage_ref'] ?? null) ?></td>
                    <td><?= pr_val($med['posologie'] ?? null) ?></td>
                    <td><?= pr_val($med['duree'] ?? null) ?></td>
                    <td><?= pr_val($med['instructions'] ?? null) ?></td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr class="empty-row"><td colspan="5">Détail des médicaments non disponible</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- ════════════════════════════════════════════════
     SECTION 5 — BILANS LABORATOIRE
════════════════════════════════════════════════ -->
<?php if (!empty($bilans_labo)): ?>
<div class="section page-break">
    <div class="section-title">
        🧪 Bilans de Laboratoire
        <span class="count-badge"><?= count($bilans_labo) ?> demande(s)</span>
    </div>

    <?php foreach ($bilans_labo as $demandeId => $demande): ?>
    <div class="avoid-break" style="margin-bottom:8px;">
        <table>
            <thead>
                <tr>
                    <th colspan="7" style="background:#0f172a;color:white;font-size:7.5pt;">
                        Demande #<?= $demandeId ?> — <?= pr_datetime($demande['info']['date']) ?>
                        <?php if ($demande['info']['medecin']): ?>
                        — Dr <?= pr_val($demande['info']['medecin']) ?>
                        <?php endif; ?>
                        <?php if (!empty($demande['info']['urgence']) && $demande['info']['urgence'] !== 'NORMAL'): ?>
                        <span class="badge badge-urgent" style="margin-left:6px;"><?= htmlspecialchars($demande['info']['urgence']) ?></span>
                        <?php endif; ?>
                    </th>
                </tr>
                <tr>
                    <th style="width:160px;">Examen</th>
                    <th style="width:70px;">Catégorie</th>
                    <th style="width:80px;">Statut</th>
                    <th style="width:90px;">Résultat</th>
                    <th style="width:70px;">Valeur num.</th>
                    <th style="width:100px;">Norme</th>
                    <th>Interprétation</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($demande['examens'] as $ex):
                $isAbnormal = false;
                if ($ex['valeur_numerique'] !== null && $ex['valeur_numerique'] !== '') {
                    if ($ex['valeur_normale_min'] !== null && $ex['valeur_numerique'] < $ex['valeur_normale_min']) $isAbnormal = true;
                    if ($ex['valeur_normale_max'] !== null && $ex['valeur_numerique'] > $ex['valeur_normale_max']) $isAbnormal = true;
                }
                $stBadge = match($ex['statut_exam']) {
                    'VALIDE','SOUMIS' => 'badge-done',
                    'EN_ATTENTE','EN_COURS','ASSIGNEE','PRELEVE' => 'badge-pending',
                    'REJETE' => 'badge-cancel',
                    default => 'badge-normal',
                };
            ?>
            <tr>
                <td style="font-weight:600;"><?= pr_val($ex['nom_examen']) ?></td>
                <td><span class="badge badge-normal"><?= pr_val($ex['categorie']) ?></span></td>
                <td><span class="badge <?= $stBadge ?>"><?= pr_val($ex['statut_exam']) ?></span></td>
                <td><?= pr_val($ex['resultat']) ?></td>
                <td class="<?= $isAbnormal ? 'res-abnormal' : ($ex['valeur_numerique'] !== null ? 'res-normal' : '') ?>">
                    <?= $ex['valeur_numerique'] !== null ? pr_val($ex['valeur_numerique']).' '.pr_val($ex['unite'],'') : '—' ?>
                    <?= $isAbnormal ? ' ⚠' : '' ?>
                </td>
                <td>
                    <?php if ($ex['valeur_normale_min'] !== null || $ex['valeur_normale_max'] !== null): ?>
                    <?= pr_val($ex['valeur_normale_min'],'') ?> – <?= pr_val($ex['valeur_normale_max'],'') ?> <?= pr_val($ex['unite'],'') ?>
                    <?php else: ?>—<?php endif; ?>
                </td>
                <td><?= pr_val($ex['interpretation']) ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- ════════════════════════════════════════════════
     SECTION 6 — BILANS IMAGERIE
════════════════════════════════════════════════ -->
<?php if (!empty($bilans_imagerie)): ?>
<div class="section avoid-break">
    <div class="section-title">
        🩻 Bilans d'Imagerie
        <span class="count-badge"><?= count($bilans_imagerie) ?> demande(s)</span>
    </div>
    <table>
        <thead>
            <tr>
                <th>Date demande</th>
                <th>Type examen</th>
                <th>Partie du corps</th>
                <th>Statut</th>
                <th>Résultat / Compte-rendu</th>
                <th>Demandé par</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($bilans_imagerie as $img):
            $stImg = match(strtolower($img['statut'])) {
                'termine' => 'badge-done',
                'en_attente' => 'badge-pending',
                'annule' => 'badge-cancel',
                default => 'badge-normal',
            };
        ?>
        <tr>
            <td><?= pr_datetime($img['date_creation']) ?></td>
            <td style="font-weight:600;"><?= pr_val($img['type_examen']) ?></td>
            <td><?= pr_val($img['partie_corps']) ?></td>
            <td><span class="badge <?= $stImg ?>"><?= pr_val($img['statut']) ?></span></td>
            <td><?= pr_val($img['resultat'] ?? $img['compte_rendu'] ?? null) ?></td>
            <td>Dr <?= pr_val(trim(($img['med_prenom']??'').' '.($img['med_nom']??''))) ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<!-- ════════════════════════════════════════════════
     SECTION 7 — SOINS EFFECTUÉS (HOSPITALISÉS)
════════════════════════════════════════════════ -->
<?php if (!empty($soins_realises)): ?>
<div class="section page-break">
    <div class="section-title">
        🏥 Soins Effectués (Hospitalisation)
        <span class="count-badge"><?= count($soins_realises) ?> soin(s)</span>
    </div>
    <table>
        <thead>
            <tr>
                <th>Date prévue</th>
                <th>Catégorie</th>
                <th>Description</th>
                <th>Condition</th>
                <th>Statut</th>
                <th>Date réalisée</th>
                <th>Exécuté par</th>
                <th>Note d'exécution</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($soins_realises as $s):
            $stSoin = $statutLabels[$s['statut']] ?? ['label'=>$s['statut'],'color'=>'#64748b'];
        ?>
        <tr>
            <td><?= pr_datetime($s['date_prevue']) ?></td>
            <td><span class="badge badge-normal"><?= htmlspecialchars($catLabels[$s['type_soin']] ?? $s['type_soin'] ?? '—') ?></span></td>
            <td style="font-weight:600;"><?= pr_val($s['description']) ?></td>
            <td style="font-style:italic;color:#d97706;"><?= pr_val($s['condition_application']) ?></td>
            <td>
                <span style="color:<?= $stSoin['color'] ?>;font-weight:700;font-size:7pt;">
                    <?= $stSoin['label'] ?>
                </span>
            </td>
            <td><?= $s['date_realisee'] ? pr_datetime($s['date_realisee']) : '—' ?></td>
            <td><?= pr_val(trim(($s['exec_prenom']??'').' '.($s['exec_nom']??''))) ?></td>
            <td><?= pr_val($s['note_execution']) ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<!-- ════════════════════════════════════════════════
     SECTION 7b — RÉÉVALUATIONS HOSPITALISÉES
════════════════════════════════════════════════ -->
<?php if (!empty($reevaluations)): ?>
<?php
$evolutionCfg = [
    'FAVORABLE'              => ['bg'=>'#dcfce7','color'=>'#166534','label'=>'Favorable'],
    'AMELIORATION_PARTIELLE' => ['bg'=>'#fef9c3','color'=>'#854d0e','label'=>'Amélioration partielle'],
    'STATUO_QUO'             => ['bg'=>'#dbeafe','color'=>'#1e3a8a','label'=>'Statuo quo'],
    'NON_FAVORABLE'          => ['bg'=>'#fee2e2','color'=>'#991b1b','label'=>'Non favorable'],
    'AGGRAVATION'            => ['bg'=>'#fee2e2','color'=>'#7f1d1d','label'=>'Aggravation'],
    'CRITIQUE'               => ['bg'=>'#fce7f3','color'=>'#9d174d','label'=>'Critique'],
];
?>
<div class="section page-break">
    <div class="section-title">
        🔄 Réévaluations Hospitalisées
        <span class="count-badge"><?= count($reevaluations) ?> réévaluation(s)</span>
    </div>
    <?php foreach ($reevaluations as $ri => $rev):
        $evo = $evolutionCfg[$rev['evolution_globale']] ?? ['bg'=>'#f1f5f9','color'=>'#475569','label'=>($rev['evolution_globale'] ?? '—')];
    ?>
    <div class="consult-block avoid-break" <?= $ri > 0 ? 'style="margin-top:6px;"' : '' ?>>
        <div class="consult-header">
            <div>
                <span class="ch-date">
                    <?= pr_date($rev['date_reevaluation']) ?> à <?= substr($rev['heure_reevaluation'] ?? '00:00',0,5) ?>
                </span>
            </div>
            <div class="ch-med">
                Dr <?= pr_val(trim(($rev['med_prenom']??'').' '.($rev['med_nom']??''))) ?>
                <?php if (!empty($rev['med_specialite'])): ?> · <?= htmlspecialchars($rev['med_specialite']) ?><?php endif; ?>
            </div>
            <div>
                <span class="badge" style="background:<?= $evo['bg'] ?>;color:<?= $evo['color'] ?>;font-weight:700;">
                    <?= $evo['label'] ?>
                </span>
            </div>
        </div>
        <div class="consult-grid">
            <!-- Col 1 : Plaintes + examens -->
            <div>
                <?php if (!empty($rev['plaintes_jour'])): ?>
                <div class="cg-cell">
                    <div class="cg-label">Plaintes du jour</div>
                    <div class="cg-value"><?= nl2br(pr_val($rev['plaintes_jour'])) ?></div>
                </div>
                <?php endif; ?>
                <?php if (!empty($rev['examen_general'])): ?>
                <div class="cg-cell" style="border-top:1px solid #f1f5f9;">
                    <div class="cg-label">Examen général</div>
                    <div class="cg-value"><?= nl2br(pr_val($rev['examen_general'])) ?></div>
                </div>
                <?php endif; ?>
                <?php if (!empty($rev['examen_cardiovasculaire'])): ?>
                <div class="cg-cell" style="border-top:1px solid #f1f5f9;">
                    <div class="cg-label">Cardiovasculaire</div>
                    <div class="cg-value"><?= nl2br(pr_val($rev['examen_cardiovasculaire'])) ?></div>
                </div>
                <?php endif; ?>
                <?php if (!empty($rev['examen_respiratoire'])): ?>
                <div class="cg-cell" style="border-top:1px solid #f1f5f9;">
                    <div class="cg-label">Respiratoire</div>
                    <div class="cg-value"><?= nl2br(pr_val($rev['examen_respiratoire'])) ?></div>
                </div>
                <?php endif; ?>
            </div>
            <!-- Col 2 : Examens spécialisés + évolution -->
            <div>
                <?php if (!empty($rev['examen_digestif'])): ?>
                <div class="cg-cell">
                    <div class="cg-label">Digestif</div>
                    <div class="cg-value"><?= nl2br(pr_val($rev['examen_digestif'])) ?></div>
                </div>
                <?php endif; ?>
                <?php if (!empty($rev['examen_neurologique'])): ?>
                <div class="cg-cell" style="border-top:1px solid #f1f5f9;">
                    <div class="cg-label">Neurologique</div>
                    <div class="cg-value"><?= nl2br(pr_val($rev['examen_neurologique'])) ?></div>
                </div>
                <?php endif; ?>
                <?php if (!empty($rev['examen_osteoarticulaire'])): ?>
                <div class="cg-cell" style="border-top:1px solid #f1f5f9;">
                    <div class="cg-label">Ostéo-articulaire</div>
                    <div class="cg-value"><?= nl2br(pr_val($rev['examen_osteoarticulaire'])) ?></div>
                </div>
                <?php endif; ?>
                <?php if (!empty($rev['note_evolution'])): ?>
                <div class="cg-cell" style="border-top:1px solid #f1f5f9;">
                    <div class="cg-label">Note d'évolution</div>
                    <div class="cg-value"><?= nl2br(pr_val($rev['note_evolution'])) ?></div>
                </div>
                <?php endif; ?>
            </div>
            <!-- Col 3 : Diagnostic + conduite -->
            <div>
                <?php if (!empty($rev['diagnostic_jour'])): ?>
                <div class="cg-cell" style="background:#f0fdf4;">
                    <div class="cg-label" style="color:#166534;">Diagnostic du jour</div>
                    <div class="cg-value" style="font-weight:700;color:#166534;">
                        <?= pr_val($rev['diagnostic_jour']) ?>
                        <?php if (!empty($rev['code_cim10'])): ?> <span style="font-weight:400;color:#64748b;">(<?= htmlspecialchars($rev['code_cim10']) ?>)</span><?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
                <?php if (!empty($rev['conduite_tenir'])): ?>
                <div class="cg-cell" style="border-top:1px solid #f1f5f9;background:#fffbeb;">
                    <div class="cg-label" style="color:#92400e;">Conduite à tenir</div>
                    <div class="cg-value"><?= nl2br(pr_val($rev['conduite_tenir'])) ?></div>
                </div>
                <?php endif; ?>
                <?php if (!empty($rev['traitement_non_medicamenteux'])): ?>
                <div class="cg-cell" style="border-top:1px solid #f1f5f9;">
                    <div class="cg-label">Traitement non médicamenteux</div>
                    <div class="cg-value"><?= nl2br(pr_val($rev['traitement_non_medicamenteux'])) ?></div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- ════════════════════════════════════════════════
     SECTION 8 — SOINS INFIRMIERS (PLAN DE SOINS)
════════════════════════════════════════════════ -->
<?php if (!empty($soins_details)): ?>
<div class="section avoid-break">
    <div class="section-title">
        📋 Plan de Soins Infirmiers (Soins Réalisés)
        <span class="count-badge"><?= count(array_filter($soins_details, fn($s) => $s['execute'])) ?> réalisé(s) / <?= count($soins_details) ?></span>
    </div>
    <table>
        <thead>
            <tr>
                <th>Date plan</th>
                <th>Heure</th>
                <th>Catégorie</th>
                <th>Soin</th>
                <th>Réalisé</th>
                <th>Date exécution</th>
                <th>Infirmier(e)</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($soins_details as $sd): ?>
        <tr>
            <td><?= pr_date($sd['date_plan'] ?? null) ?></td>
            <td style="font-weight:700;"><?= pr_val($sd['heure']) ?></td>
            <td><span class="badge badge-normal"><?= htmlspecialchars($catLabels[$sd['categorie']] ?? $sd['categorie']) ?></span></td>
            <td><?= pr_val($sd['soin_description']) ?></td>
            <td>
                <?php if ($sd['execute']): ?>
                    <span class="badge badge-done">✓ Réalisé</span>
                <?php else: ?>
                    <span class="badge badge-pending">En attente</span>
                <?php endif; ?>
            </td>
            <td><?= $sd['date_execution'] ? pr_datetime($sd['date_execution']) : '—' ?></td>
            <td><?= pr_val(trim(($sd['inf_prenom']??'').' '.($sd['inf_nom']??''))) ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<!-- ════════════════════════════════════════════════
     SECTION 9 — OBSERVATIONS INFIRMIÈRES / ÉVOLUTION
════════════════════════════════════════════════ -->
<?php if (!empty($observations)): ?>
<div class="section page-break">
    <div class="section-title">
        📝 Observations Infirmières &amp; Évolution
        <span class="count-badge"><?= count($observations) ?> observation(s)</span>
    </div>
    <table>
        <thead>
            <tr>
                <th style="width:130px;">Date / Heure</th>
                <th style="width:140px;">Auteur</th>
                <th style="width:70px;">Rôle</th>
                <th>Observation</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($observations as $obs): ?>
        <tr>
            <td><?= pr_datetime($obs['date_obs']) ?></td>
            <td><?= pr_val(trim(($obs['user_prenom']??'').' '.($obs['user_nom']??''))) ?></td>
            <td><span class="badge badge-normal" style="font-size:6pt;"><?= pr_val($obs['role'] ?? null) ?></span></td>
            <td style="line-height:1.5;"><?= nl2br(pr_val($obs['contenu'])) ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<!-- ══ PIED DE PAGE ══ -->
<div class="doc-footer no-print">
    <span>Document généré le <?= date('d/m/Y à H:i') ?> · HSJM · Dossier <?= pr_val($patient['dossier_numero']) ?></span>
    <span>Confidentiel — Usage médical exclusif</span>
</div>

</div><!-- /document -->

<script>
// Lancer l'impression automatiquement si ?autoprint=1
if (new URLSearchParams(location.search).get('autoprint') === '1') {
    window.addEventListener('load', () => setTimeout(() => window.print(), 600));
}
</script>
</body>
</html>
