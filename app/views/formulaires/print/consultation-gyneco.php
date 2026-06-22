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
@page { size: A4 portrait; margin: 1cm 1.5cm; }
.gy-section-title {
    font-weight: 900; font-size: 9pt; text-transform: uppercase;
    letter-spacing: .5px; color: #db2777;
    border-bottom: 1.5px solid #fbcfe8; margin: 12px 0 6px;
    padding-bottom: 3px;
}
.gy-grid2 { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 6px; }
.gy-grid3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 10px; margin-bottom: 6px; }
.gy-grid4 { display: grid; grid-template-columns: 1fr 1fr 1fr 1fr; gap: 8px; margin-bottom: 6px; }
.gy-field { margin-bottom: 6px; font-size: 10pt; }
.gy-label { font-size: 8pt; font-weight: 700; color: #64748b; text-transform: uppercase;
    letter-spacing: .3px; display: block; margin-bottom: 2px; }
.gy-val { border-bottom: 1px solid #000; min-height: 16px; padding: 0 3px;
    font-weight: 700; display: block; font-size: 10pt; min-width: 60px; }
.gy-val-long { border-bottom: 1px solid #ccc; min-height: 14px; display: block;
    font-size: 10pt; padding: 2px 3px; margin-bottom: 2px; }
.constante-box {
    border: 1px solid #e2e8f0; border-radius: 4px; padding: 4px 8px;
    text-align: center; background: #f9f9f9;
}
.constante-label { font-size: 7.5pt; color: #64748b; font-weight: 700; }
.constante-val   { font-size: 10pt; font-weight: 900; color: #1e293b; }
.radio-line { display: inline-flex; align-items: center; gap: 5px; margin-right: 10px; font-size: 9.5pt; }
.ordo-table { width: 100%; border-collapse: collapse; font-size: 9pt; margin-top: 6px; }
.ordo-table th { background: #fdf2f8; border: 1px solid #fbcfe8; padding: 5px 7px;
    font-size: 8pt; font-weight: 700; color: #9d174d; text-transform: uppercase; }
.ordo-table td { border: 1px solid #e2e8f0; padding: 4px 7px; }
.ordo-table tr:nth-child(even) td { background: #fdf2f8; }
</style>

<!-- EN-TÊTE -->
<?= entete_hopital() ?>
    <div style="text-align:center;flex:1;">
        <img src="<?= BASE_URL ?>public/images/logo_ordre_malte.png" alt="Logo" class="hosp-logo">
    </div>
    <div class="hosp-right">
        Dossier N° : <strong><?= $dossierNo ?></strong><br>
        Date : <strong><?= $fd('date_consult') ?: $today ?></strong><br>
        Service : Gynécologie-Obstétrique
    </div>
</div>

<!-- TITRE -->
<div class="doc-title" style="font-size:13pt;border-color:#db2777;color:#1e293b;">
    Consultation Gynécologique
</div>

<!-- IDENTITÉ PATIENT -->
<div style="background:#fdf2f8;border:1px solid #fbcfe8;border-radius:6px;padding:8px 14px;margin-bottom:10px;">
    <div class="gy-grid3" style="margin:0;">
        <div>
            <span class="gy-label">Patiente</span>
            <span style="font-weight:900;font-size:11pt;"><?= $patNom ?></span>
        </div>
        <div>
            <span class="gy-label">Âge / DDN</span>
            <span style="font-weight:700;"><?= $patAge ?> ans
                <?php if (!empty($patient['date_naissance'])): ?>
                — <?= date('d/m/Y', strtotime($patient['date_naissance'])) ?>
                <?php endif; ?>
            </span>
        </div>
        <div>
            <span class="gy-label">Gynécologue</span>
            <span style="font-weight:700;"><?= $fd('medecin_gyneco') ?></span>
        </div>
    </div>
</div>

<!-- ═══ MOTIF ═══════════════════════════════════════════════════════ -->
<div class="gy-section-title"><i>●</i> Motif de consultation</div>
<div class="gy-grid2">
    <div class="gy-field">
        <span class="gy-label">Motif principal</span>
        <span class="gy-val"><?= $fd('motif') ?></span>
    </div>
    <div class="gy-field">
        <span class="gy-label">Date</span>
        <span class="gy-val"><?= $fd('date_consult') ?: $today ?></span>
    </div>
</div>
<?php if ($fd('plaintes')): ?>
<div class="gy-field">
    <span class="gy-label">Plaintes et symptômes</span>
    <span class="gy-val-long"><?= nl2br($fd('plaintes')) ?></span>
</div>
<?php endif; ?>

<!-- ═══ ANTÉCÉDENTS GYNÉCO-OBSTÉTRICAUX ════════════════════════════ -->
<div class="gy-section-title"><i>●</i> Antécédents gynéco-obstétricaux</div>
<div class="gy-grid4">
    <div class="constante-box">
        <div class="constante-label">G (Gestité)</div>
        <div class="constante-val"><?= $fd('gestite') ?: '—' ?></div>
    </div>
    <div class="constante-box">
        <div class="constante-label">P (Parité)</div>
        <div class="constante-val"><?= $fd('parite') ?: '—' ?></div>
    </div>
    <div class="constante-box">
        <div class="constante-label">A (Avortements)</div>
        <div class="constante-val"><?= $fd('avortements') ?: '—' ?></div>
    </div>
    <div class="constante-box">
        <div class="constante-label">DDR</div>
        <div class="constante-val" style="font-size:9pt;">
            <?php
            $ddr = $formData['ddr'] ?? '';
            echo $ddr ? date('d/m/Y', strtotime($ddr)) : '—';
            ?>
        </div>
    </div>
</div>
<div class="gy-grid3">
    <div class="gy-field">
        <span class="gy-label">Âge grossesse (SA)</span>
        <span class="gy-val"><?= $fd('age_grossesse_sa') ?></span>
    </div>
    <div class="gy-field">
        <span class="gy-label">Contraception</span>
        <span class="gy-val"><?= $fd('contraception') ?></span>
    </div>
    <div class="gy-field">
        <span class="gy-label">Cycle / Ménarche</span>
        <span class="gy-val"><?= $fd('cycle_menstruel') ?> / <?= $fd('menarche') ?></span>
    </div>
</div>

<!-- Sérologies -->
<div style="margin-bottom:6px;font-size:9.5pt;">
    <strong>Sérologies :</strong> &nbsp;
    Groupe : <span class="field-val"><?= $fd('groupe_sanguin') ?></span> &nbsp;
    VIH/LAV : <span class="field-val"><?= $fd('lav') ?></span> &nbsp;
    AgHBs : <span class="field-val"><?= $fd('aghbs') ?></span> &nbsp;
    Syphilis : <span class="field-val"><?= $fd('syphilis') ?></span>
</div>

<div class="gy-grid2">
    <div class="gy-field">
        <span class="gy-label">ATCD médicaux</span>
        <span class="gy-val-long"><?= nl2br($fd('atcd_medicaux')) ?: '&nbsp;' ?></span>
    </div>
    <div class="gy-field">
        <span class="gy-label">ATCD chirurgicaux / obstétricaux</span>
        <span class="gy-val-long"><?= nl2br($fd('atcd_chirurgicaux')) ?: '&nbsp;' ?></span>
    </div>
</div>

<!-- ═══ EXAMEN CLINIQUE ═════════════════════════════════════════════ -->
<div class="gy-section-title"><i>●</i> Examen clinique</div>

<!-- Constantes -->
<div style="display:grid;grid-template-columns:repeat(6,1fr);gap:6px;margin-bottom:8px;">
    <?php foreach ([
        ['TA (mmHg)','ta'], ['Pouls (bpm)','pouls'],
        ['T° (°C)','temperature'], ['Poids (kg)','poids'],
        ['Taille (cm)','taille'], ['IMC','imc']
    ] as [$lbl,$key]): ?>
    <div class="constante-box">
        <div class="constante-label"><?= $lbl ?></div>
        <div class="constante-val"><?= $fd($key) ?: '—' ?></div>
    </div>
    <?php endforeach; ?>
</div>

<div class="gy-grid3" style="margin-bottom:6px;">
    <div class="gy-field">
        <span class="gy-label">État général</span>
        <span class="gy-val"><?= $fd('etat_general') ?></span>
    </div>
    <div class="gy-field">
        <span class="gy-label">Conjonctives</span>
        <span class="gy-val"><?= $fd('conjonctives') ?></span>
    </div>
    <div class="gy-field">
        <span class="gy-label">Oedèmes</span>
        <span class="gy-val"><?= $fd('oedemes') ?></span>
    </div>
</div>

<div class="gy-grid2">
    <div class="gy-field">
        <span class="gy-label">Examen des seins</span>
        <span class="gy-val-long"><?= nl2br($fd('seins')) ?: '&nbsp;' ?></span>
    </div>
    <div class="gy-field">
        <span class="gy-label">Examen au spéculum</span>
        <span class="gy-val-long"><?= nl2br($fd('speculum')) ?: '&nbsp;' ?></span>
    </div>
    <div class="gy-field">
        <span class="gy-label">Col utérin</span>
        <span class="gy-val"><?= $fd('col_uterus') ?></span>
    </div>
    <div class="gy-field">
        <span class="gy-label">Toucher vaginal (TV)</span>
        <span class="gy-val-long"><?= nl2br($fd('tv')) ?: '&nbsp;' ?></span>
    </div>
</div>
<div class="gy-grid2">
    <div class="gy-field">
        <span class="gy-label">Annexes</span>
        <span class="gy-val-long"><?= nl2br($fd('annexes')) ?: '&nbsp;' ?></span>
    </div>
    <div style="display:grid;grid-template-columns:1fr 1fr 1fr 1fr;gap:5px;">
        <?php foreach ([['BDCF','bdcf'],['H.U.','hu'],['Présentation','presentation'],['L.A.','liquide_amniotique']] as [$l,$k]): ?>
        <div class="constante-box">
            <div class="constante-label"><?= $l ?></div>
            <div class="constante-val" style="font-size:9pt;"><?= $fd($k) ?: '—' ?></div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- ═══ DIAGNOSTIC ══════════════════════════════════════════════════ -->
<div class="gy-section-title"><i>●</i> Diagnostic &amp; Conduite à tenir</div>
<div class="gy-grid2">
    <div class="gy-field">
        <span class="gy-label">Diagnostic / Impression clinique</span>
        <?php for ($i=0;$i<3;$i++): ?>
        <span class="gy-val-long"><?= $i===0 ? nl2br($fd('diagnostic')) : '&nbsp;' ?></span>
        <?php endfor; ?>
    </div>
    <div class="gy-field">
        <span class="gy-label">Conduite à tenir</span>
        <?php for ($i=0;$i<3;$i++): ?>
        <span class="gy-val-long"><?= $i===0 ? nl2br($fd('conduite_tenir')) : '&nbsp;' ?></span>
        <?php endfor; ?>
    </div>
</div>
<?php if ($fd('observations')): ?>
<div class="gy-field">
    <span class="gy-label">Observations</span>
    <span class="gy-val-long"><?= nl2br($fd('observations')) ?></span>
</div>
<?php endif; ?>

<!-- ═══ BILANS ══════════════════════════════════════════════════════ -->
<?php
$bilansLabo = $formData['bilans_labo'] ?? [];
$bilansImg  = $formData['bilans_imagerie'] ?? [];
$bilansLabo = array_filter($bilansLabo, fn($b) => !empty($b['examen']));
$bilansImg  = array_filter($bilansImg,  fn($b) => !empty($b['zone']));
if ($bilansLabo || $bilansImg || $fd('notes_bilans')):
?>
<div class="gy-section-title"><i>●</i> Bilans paracliniques demandés</div>
<?php if ($fd('notes_bilans')): ?>
<div class="gy-field" style="margin-bottom:6px;">
    <span class="gy-val-long"><?= nl2br($fd('notes_bilans')) ?></span>
</div>
<?php endif; ?>
<?php if ($bilansLabo): ?>
<table class="ordo-table" style="margin-bottom:8px;">
    <thead><tr><th>Examen</th><th>Catégorie</th><th>Instructions</th><th>Urgence</th></tr></thead>
    <tbody>
    <?php foreach ($bilansLabo as $bl): ?>
    <tr>
        <td><?= htmlspecialchars($bl['examen'] ?? '') ?></td>
        <td><?= htmlspecialchars($bl['categorie'] ?? '') ?></td>
        <td><?= htmlspecialchars($bl['instructions'] ?? '') ?></td>
        <td><?= htmlspecialchars($bl['urgence'] ?? '') ?></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php endif; ?>
<?php if ($bilansImg): ?>
<table class="ordo-table">
    <thead><tr><th>Type d'imagerie</th><th>Zone / Indication</th><th>Précisions</th></tr></thead>
    <tbody>
    <?php foreach ($bilansImg as $im): ?>
    <tr>
        <td><?= htmlspecialchars($im['type'] ?? '') ?></td>
        <td><?= htmlspecialchars($im['zone'] ?? '') ?></td>
        <td><?= htmlspecialchars($im['precisions'] ?? '') ?></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php endif; ?>
<?php endif; ?>

<!-- ═══ ORDONNANCE ═════════════════════════════════════════════════ -->
<?php
$ordonnance = array_filter($formData['ordonnance'] ?? [], fn($m) => !empty($m['medicament']));
if ($ordonnance || $fd('plan_traitement')):
?>
<div class="gy-section-title"><i>●</i> Traitement prescrit</div>
<?php if ($fd('plan_traitement')): ?>
<div class="gy-field" style="margin-bottom:6px;">
    <span class="gy-label">Plan de traitement</span>
    <span class="gy-val-long"><?= nl2br($fd('plan_traitement')) ?></span>
</div>
<?php endif; ?>
<?php if ($ordonnance): ?>
<table class="ordo-table">
    <thead><tr><th>Médicament</th><th>Dosage</th><th>Voie</th><th>Fréquence</th><th>Durée</th></tr></thead>
    <tbody>
    <?php foreach ($ordonnance as $med): ?>
    <tr>
        <td style="font-weight:bold;"><?= htmlspecialchars($med['medicament'] ?? '') ?></td>
        <td><?= htmlspecialchars($med['dosage'] ?? '') ?></td>
        <td><?= htmlspecialchars($med['voie'] ?? '') ?></td>
        <td><?= htmlspecialchars($med['frequence'] ?? '') ?></td>
        <td><?= htmlspecialchars($med['duree'] ?? '') ?></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php endif; ?>
<?php if ($fd('instructions_patient')): ?>
<div class="gy-field" style="margin-top:5px;">
    <span class="gy-label">Instructions au patient</span>
    <span class="gy-val-long"><?= nl2br($fd('instructions_patient')) ?></span>
</div>
<?php endif; ?>
<?php endif; ?>

<!-- ═══ PROCHAIN RDV ════════════════════════════════════════════════ -->
<?php if ($fd('prochain_rdv')): ?>
<div style="background:#fdf2f8;border:1px solid #fbcfe8;border-radius:6px;padding:6px 14px;margin-top:8px;font-size:9.5pt;">
    <strong>Prochain rendez-vous :</strong>
    <?= date('d/m/Y', strtotime($formData['prochain_rdv'])) ?>
</div>
<?php endif; ?>

<!-- ═══ SIGNATURES ══════════════════════════════════════════════════ -->
<?php
$sigMed = $formData['signature_medecin'] ?? '';
$medNom = $fd('medecin_gyneco') ?: $praticien;
?>
<div class="sig-grid" style="margin-top:20px;">
    <div class="sig-block">
        <div class="sig-line"></div>
        <div style="font-size:8.5pt;color:#64748b;">Signature de la patiente — <?= $patNom ?></div>
    </div>
    <div class="sig-block">
        <?php if ($sigMed && str_starts_with($sigMed, 'data:image')): ?>
            <img src="<?= htmlspecialchars($sigMed) ?>"
                 style="max-height:70px;max-width:220px;display:block;margin:0 auto 4px;">
        <?php else: ?>
            <div class="sig-line"></div>
        <?php endif; ?>
        <div style="font-size:8.5pt;font-weight:bold;"><?= htmlspecialchars($medNom) ?></div>
        <div style="font-size:7.5pt;color:#64748b;">Gynécologue — Obstétricien</div>
    </div>
</div>

<div class="footer-note">
    Consultation du <?= $fd('date_consult') ?: $today ?> — <?= $patNom ?> — Dossier N° <?= $dossierNo ?>
    — Hôpital Saint-Jean de Malte — Service Gynécologie-Obstétrique
</div>

</div></body></html>
