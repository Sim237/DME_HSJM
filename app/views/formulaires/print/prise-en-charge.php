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
<div class="doc-title">Demande de Bon de Prise en Charge</div>

<div class="section-header">Informations de l'assuré</div>

<div class="field-row">
  <span class="field-label">Nom de l'assuré :</span>
  <span class="field-val xwide"><?= $fd('nom_assure') ?></span>
</div>

<div class="grid-3" style="margin-bottom:10px;">
  <div class="field-row">
    <span class="field-label">Entreprise :</span>
    <span class="field-val wide"><?= $fd('entreprise') ?></span>
  </div>
  <div class="field-row">
    <span class="field-label">Secteur :</span>
    <span class="field-val wide"><?= $fd('secteur') ?></span>
  </div>
  <div class="field-row">
    <span class="field-label">Matricule :</span>
    <span class="field-val"><?= $fd('matricule') ?></span>
  </div>
</div>

<div class="section-header">Informations du malade</div>

<div class="field-row">
  <span class="field-label">Nom du malade :</span>
  <span class="field-val xwide"><?= $patNom ?></span>
</div>

<div class="field-row">
  <span class="field-label">Date de naissance :</span>
  <span class="field-val"><?= htmlspecialchars($patient['date_naissance'] ?? '') ?></span>
</div>

<div class="field-row" style="align-items:center; gap:20px;">
  <span class="field-label">Lien de filiation :</span>
  <span><?= $radio('fil_salarie', '1') ?> Salarié</span>
  <span><?= $radio('fil_conjoint', '1') ?> Conjoint</span>
</div>

<div class="section-header">Nature des soins</div>

<div class="field-row">
  <span class="field-label">Consultation externe — Date :</span>
  <span class="field-val"><?= $fd('date_soins_externe') ?></span>
</div>

<div class="field-row">
  <span class="field-label">Hospitalisation — Date :</span>
  <span class="field-val"><?= $fd('date_hosp') ?></span>
</div>

<div class="section-header">Diagnostic d'entrée</div>

<div style="border:1px solid #aaa; min-height:70px; padding:8px; margin-bottom:16px;">
  <?= $fd('diagnostic') ?>
</div>

<p style="margin-top:20px;">
  À Njombé, le <span class="field-val"><?= $fd('date_signature') ?></span>
</p>

<div style="margin-top:30px; text-align:right;">
  <div style="display:inline-block; min-width:200px; text-align:center;">
    <div style="height:60px; border-bottom:1px solid #000; margin-bottom:6px;"></div>
    <div>Signature &amp; Cachet</div>
  </div>
</div>

</div></body></html>
