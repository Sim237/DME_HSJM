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
<?= entete_hopital() ?>
<div class="doc-title">Formulaire de Consentement Éclairé</div>

<div style="border:1px solid #000; padding:8px 12px; margin-bottom:14px; font-size:9pt; font-style:italic;">
  Ce document est rempli par le praticien avec le concours du patient lors du premier entretien et devra être joint dans le dossier médical.
</div>

<p>
  Je soussigné(e), M/Mme <span class="field-val wide"><?= $patNom ?></span>,
  né(e) le <span class="field-val"><?= htmlspecialchars($patient['date_naissance'] ?? '') ?></span>,
  à <span class="field-val wide"><?= $fd('lieu_naissance') ?></span>,
  certifie avoir pu m'entretenir avec le Docteur
  <span class="field-val wide"><?= $fd('medecin_nom') ?></span>
  en date du <span class="field-val"><?= $fd('date_entretien') ?></span>.
</p>

<div class="section-header">Options d'intervention</div>

<div style="margin-bottom:8px;">
  <span class="check-sq checked"></span>
  &nbsp;D'une <strong>hospitalisation</strong> prévue le
  <span class="field-val"><?= $fd('date_hosp') ?></span>
  en vue d'une intervention prévue le
  <span class="field-val"><?= $fd('date_interv') ?></span>.
</div>
<div style="margin-bottom:8px;">
  <span class="check-sq checked"></span>
  &nbsp;D'une <strong>intervention</strong> prévue le
  <span class="field-val"><?= $fd('date_interv_seule') ?></span>.
</div>

<div class="section-header">Clauses du consentement</div>

<ol style="margin:0; padding-left:20px; line-height:2;">
  <li>
    <span class="check-sq checked"></span>
    &nbsp;Avoir reçu des informations suffisantes sur la nature de l'intervention, son déroulement et ses suites habituelles.
  </li>
  <li>
    <span class="check-sq checked"></span>
    &nbsp;Avoir été informé(e) des risques prévisibles, même exceptionnels, liés à l'intervention et à l'anesthésie.
  </li>
  <li>
    <span class="check-sq checked"></span>
    &nbsp;Avoir été informé(e) des alternatives thérapeutiques existantes.
  </li>
  <li>
    <span class="check-sq checked"></span>
    &nbsp;Avoir eu la possibilité de poser toutes les questions souhaitées et d'obtenir des réponses claires.
  </li>
  <li>
    <span class="check-sq checked"></span>
    &nbsp;Avoir été informé(e) que mon consentement est libre et révocable à tout moment sans justification.
  </li>
  <li>
    <span class="check-sq checked"></span>
    &nbsp;Avoir été informé(e) du coût estimatif de l'intervention s'élevant à
    <span class="field-val"><?= $fd('cout_estim') ?></span> FCFA.
  </li>
</ol>

<div style="border:2px solid #000; padding:10px 14px; margin:18px 0; text-align:center; font-weight:bold; font-size:11pt; letter-spacing:.5px;">
  POUR QUE SOIT RÉALISÉE L'INTERVENTION PRÉVUE DANS LES CONDITIONS CI-DESSUS
</div>

<p style="margin-top:20px;">
  Fait à Njombé, le <span class="field-val"><?= $fd('date_signature') ?></span>
</p>

<div class="sig-grid">
  <div class="sig-block">
    <div class="sig-line"></div>
    <div>Signature du patient</div>
  </div>
  <div class="sig-block">
    <div class="sig-line"></div>
    <div>Signature du Praticien / Dr. <?= htmlspecialchars($praticien ?? '') ?></div>
  </div>
</div>

<div class="footer-note">
  Hôpital Saint-Jean de Malte — Ordre de Malte — Njombé, Cameroun — Ce formulaire doit être conservé dans le dossier médical du patient.
</div>

</div></body></html>
