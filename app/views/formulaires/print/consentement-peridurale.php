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

<!-- EN-TÊTE -->
<div class="hosp-header">
  <div class="hosp-left">
    <img src="<?= defined('BASE_URL') ? BASE_URL : '/dme_hospital/' ?>public/images/logo_ordre_malte.png"
<?= entete_hopital() ?>
  <div class="hosp-right">
    Njombé, le <span class="field-val wide"><?= $fd('lieu_date', $today) ?></span>
  </div>
</div>

<div style="border-top:2px solid #000; margin:10px 0 14px;"></div>

<!-- TITRE PRINCIPAL -->
<div class="doc-title">
  Analgésie péridurale — Formulaire de consentement éclairé de la patiente
</div>

<!-- SOUS-TITRE ENCADRÉ -->
<div style="border:2.5px solid #000; padding:10px 18px; text-align:center; font-weight:900; font-size:13pt; text-transform:uppercase; letter-spacing:1.5px; margin-bottom:20px;">
  DE RECEVOIR LA PÉRIDURALE
</div>

<!-- CORPS DU TEXTE -->
<p style="margin-bottom:12px;">Madame,</p>

<p style="text-align:justify; margin-bottom:12px;">
  Lors de l'accouchement, il est devenu habituel de pouvoir bénéficier d'une technique qui permet d'accoucher
  sans douleur. Cette technique, que l'on appelle <strong>péridurale</strong>, agit localement par l'administration
  d'anesthésiques, dont le dosage est adapté tout au long de l'accouchement.
</p>

<p style="text-align:justify; margin-bottom:8px;">
  Nous tenons néanmoins à vous informer des risques et inconvénients qui peuvent survenir lors d'une
  anesthésie péridurale :
</p>

<ul style="margin:0 0 12px 24px; padding:0; line-height:2; text-align:justify;">
  <li>Des maux de tête passagers ;</li>
  <li>Des maux de dos passagers et/ou des douleurs dans une jambe, des troubles de la sensibilité ou de la
      force des jambes s'améliorant rapidement ou persistant à long terme ;</li>
  <li>La survenue d'une collection de sang dans le dos ;</li>
  <li>Une infection pouvant évoluer vers une méningite ou une paraplégie (paralysie des membres inférieurs).</li>
</ul>

<p style="text-align:justify; margin-bottom:12px;">
  Ces problèmes très rares peuvent survenir même lorsque toutes les précautions ont été prises.
</p>

<p style="text-align:justify; margin-bottom:12px;">
  Merci d'avoir choisi l'<strong>Hôpital Saint Jean de Malte</strong> pour réaliser votre accouchement.
  Nous nous engageons à tout mettre en œuvre pour vous assurer la sécurité médicale de cet événement.
</p>

<p style="text-align:justify; margin-bottom:20px;">
  Suite à l'entretien d'information que j'ai eu et aux réponses qui ont été apportées à mes questions,
  j'accepte, après réflexion,
</p>

<!-- CASE À COCHER D'ACCEPTATION -->
<div style="display:flex; align-items:center; gap:10px; margin:12px 0 24px; font-weight:bold; font-size:11pt;">
  <?= $yn('accepte') ?>
  &nbsp;<strong>De recevoir la péridurale</strong>
</div>

<!-- SIGNATURES -->
<div class="section-header">Signatures</div>

<div class="field-row" style="margin-bottom:14px;">
  <span class="field-label">NOM ET PRÉNOM DE LA PATIENTE :</span>
  &nbsp;<span class="field-val xwide"><?= $fd('patient_nom', $patNom) ?></span>
</div>

<div class="field-row" style="margin-bottom:14px;">
  <span class="field-label">LIEU ET DATE :</span>
  &nbsp;<span class="field-val xwide"><?= $fd('lieu_date') ?></span>
</div>

<?php
$_sigPat = $formData['signature_patiente'] ?? '';
$_sigMed = $formData['signature_medecin']  ?? '';
$_medNom = $fd('medecin_peridurale');
?>
<div class="sig-grid" style="margin-top:30px; gap:40px;">
  <!-- Patiente -->
  <div class="sig-block">
    <?php if ($_sigPat && str_starts_with($_sigPat, 'data:image')): ?>
      <img src="<?= htmlspecialchars($_sigPat) ?>"
           style="max-height:70px; max-width:220px; display:block; margin:0 auto 6px;">
    <?php else: ?>
      <div class="sig-line"></div>
    <?php endif; ?>
    <div style="font-weight:bold; font-size:9pt; text-transform:uppercase; letter-spacing:.5px;">
      Signature de la patiente
    </div>
    <div style="font-size:8.5pt; color:#444;"><?= $fd('patient_nom', $patNom) ?></div>
  </div>
  <!-- Médecin -->
  <div class="sig-block">
    <?php if ($_sigMed && str_starts_with($_sigMed, 'data:image')): ?>
      <img src="<?= htmlspecialchars($_sigMed) ?>"
           style="max-height:70px; max-width:220px; display:block; margin:0 auto 6px;">
    <?php else: ?>
      <div class="sig-line"></div>
    <?php endif; ?>
    <div style="font-weight:bold; font-size:9pt; text-transform:uppercase; letter-spacing:.5px;">
      Signature du médecin
    </div>
    <?php if ($_medNom): ?>
    <div style="font-size:8.5pt; color:#444;"><?= $_medNom ?></div>
    <?php endif; ?>
  </div>
</div>

<!-- PIED DE PAGE -->
<div class="footer-note">
  L'Hôpital Saint-Jean de Malte est une œuvre humanitaire fonctionnant sous l'égide des Œuvres Hospitalières
  Françaises de l'Ordre de Malte, en partenariat avec le Ministère de la Santé Publique et avec les Plantations
  SPNP/SBM/PHP. — Ce formulaire doit être conservé dans le dossier médical de la patiente.
</div>

</div></body></html>
