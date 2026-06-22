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

<!-- En-tête officiel bilingue -->
<?= entete_hopital() ?>
  <div style="text-align:center; flex:1;">
    <img src="<?= BASE_URL ?>public/images/logo_ordre_malte.png" alt="Logo" class="hosp-logo">
  </div>
  <div class="hosp-right">
    Service : <span class="field-val"><?= $fd('service') ?></span><br><br>
    À <?= $fd('lieu_signature', 'Njombé') ?> le
    <span class="field-val"><?= $fd('date_signature') ?: $today ?></span>
  </div>
</div>

<!-- Titre du document -->
<div class="doc-title">
  Certificat de Genre de Mort<br>
  <span style="font-size:11pt; letter-spacing:.5px;">Certificate of Cause of Death</span>
</div>

<!-- Corps principal -->
<div style="margin: 18px 0; font-size:11.5pt; line-height:2.2;">

  <div class="field-row">
    <span class="field-label">Je soussigné /&nbsp;<em>I, the undersigned</em> :</span>
    <span class="field-val xwide"><?= $fd('soussigne') ?></span>
  </div>

  <div class="field-row">
    <span class="field-label">certifie que /&nbsp;<em>hereby certify that</em> :</span>
    <span class="field-val xwide"><?= $fd('defunt_nom') ?></span>
  </div>

  <div class="field-row">
    <span class="field-label">Âge / <em>Age</em> :</span>
    <span class="field-val"><?= $patAge ?></span>&nbsp;ans
    &nbsp;&nbsp;&nbsp;
    <span class="field-label">Sexe / <em>Sex</em> :</span>
    <span class="field-val"><?= $patSexe ?></span>
  </div>

  <div class="field-row">
    <span class="field-label">est décédé(e) le /&nbsp;<em>passed away on</em> :</span>
    <span class="field-val wide"><?= $fd('date_deces') ?></span>
    &nbsp;&nbsp;&nbsp;
    <span class="field-label">à /&nbsp;<em>at</em> :</span>
    <span class="field-val"><?= $fd('heure_deces') ?></span>
  </div>

</div>

<!-- Section : Genre de mort -->
<div class="section-header">Genre de mort / Nature of death</div>

<div style="display:flex; gap:32px; flex-wrap:wrap; margin: 12px 0 18px; font-size:11pt; line-height:2;">
  <div>
    <?= $radio('genre_mort', 'affection_naturelle') ?>
    &nbsp;Affection naturelle <em style="font-size:9.5pt;">(Natural cause)</em>
  </div>
  <div>
    <?= $radio('genre_mort', 'mort_suspecte') ?>
    &nbsp;Mort suspecte <em style="font-size:9.5pt;">(Suspicious death)</em>
  </div>
  <div>
    <?= $radio('genre_mort', 'mort_violente') ?>
    &nbsp;Mort violente <em style="font-size:9.5pt;">(Violent death)</em>
  </div>
  <div>
    <?= $radio('genre_mort', 'mort_inconnue') ?>
    &nbsp;Cause inconnue <em style="font-size:9.5pt;">(Unknown cause)</em>
  </div>
</div>

<!-- Cause principale -->
<div class="section-header">Cause principale / Primary cause of death</div>

<div style="margin: 10px 0 18px; font-size:11pt; line-height:2.4;">
  <span class="field-val full"><?= $fd('cause_deces') ?></span>
</div>

<!-- Diagnostic -->
<div class="section-header">Diagnostic éventuel / Possible diagnosis</div>

<div style="margin: 10px 0 18px; font-size:11pt; line-height:2.4;">
  <span class="field-val full"><?= $fd('diagnostic') ?></span>
  <span class="field-val full" style="margin-top:8px;">&nbsp;</span>
  <span class="field-val full" style="margin-top:8px;">&nbsp;</span>
</div>

<!-- Texte officiel bilingue -->
<div style="margin: 18px 0; font-size:10.5pt; line-height:1.8; border:1px solid #888; padding:12px 16px; border-radius:4px;">
  <p style="margin:0 0 6px;">
    Son corps (ne) présente (pas) un danger pour la population et peut être transporté à son lieu d'origine conformément aux lois en vigueur.
  </p>
  <p style="margin:0 0 10px; font-style:italic; color:#333; font-size:9.5pt;">
    His body is (not) free from any obvious contamination for the population hence can be transported to place of origin.
  </p>
  <p style="margin:0 0 6px;">
    En foi de quoi le présent certificat est délivré pour servir et valoir ce que de droit.
  </p>
  <p style="margin:0; font-style:italic; color:#333; font-size:9.5pt;">
    This serves as a testimonial.
  </p>
</div>

<!-- Signature -->
<?php
$_sigMed = $formData['signature_medecin'] ?? '';
$_sigMedName = $fd('soussigne');
?>
<div style="text-align:right; margin-top:30px;">
  <div style="display:inline-block; min-width:280px; text-align:center;">
    <?php if ($_sigMed && str_starts_with($_sigMed, 'data:image')): ?>
      <img src="<?= htmlspecialchars($_sigMed) ?>"
           style="max-height:80px; max-width:260px; display:block; margin:0 auto 4px;">
    <?php else: ?>
      <div style="height:80px;"></div>
    <?php endif; ?>
    <div style="border-bottom:1.5px solid #000; margin-bottom:5px;"></div>
    <div style="font-size:10pt; font-weight:bold;">
      <?= $fd('soussigne') ?: 'Cachet et Signature du Médecin' ?>
    </div>
    <div style="font-size:8.5pt; color:#444; font-style:italic; margin-top:2px;">
      Stamp &amp; Signature of the Doctor
    </div>
  </div>
</div>

<div class="footer-note">
  Hôpital Saint-Jean de Malte — Ordre de Malte — B.P. 56 Njombé, République du Cameroun
  — Ce certificat est délivré à titre officiel et fait foi devant toute autorité compétente.
</div>

</div></body></html>
