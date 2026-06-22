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
<div class="doc-title">Fiche de Référence</div>

<div class="section-header">Identification du malade</div>

<div class="field-row">
  <span class="field-label">NOM ET PRÉNOM :</span>
  <span class="field-val xwide"><?= $patNom ?></span>
</div>

<div class="grid-2">
  <div class="field-row">
    <span class="field-label">TÉL :</span>
    <span class="field-val wide"><?= $fd('tel_patient') ?></span>
  </div>
  <div class="field-row">
    <span class="field-label">ÂGE :</span>
    <span class="field-val"><?= $patAge ?></span>&nbsp;ans
  </div>
</div>

<div class="field-row">
  <span class="field-label">RÉSIDENCE :</span>
  <span class="field-val xwide"><?= $fd('residence') ?></span>
</div>

<div class="grid-2">
  <div class="field-row">
    <span class="field-label">PERSONNE À PRÉVENIR :</span>
    <span class="field-val wide"><?= $fd('contact_urgence') ?></span>
  </div>
  <div class="field-row">
    <span class="field-label">TÉL :</span>
    <span class="field-val wide"><?= $fd('tel_urgence') ?></span>
  </div>
</div>

<div class="section-header">Motif de référence</div>

<div style="border:1px solid #aaa; min-height:80px; padding:8px; margin-bottom:12px;">
  <?= $fd('motif') ?>
</div>

<div class="field-row">
  <span class="field-label">LIEU DE LA RÉFÉRENCE :</span>
  <span class="field-val xwide"><?= $fd('lieu_reference') ?></span>
</div>

<div class="sig-grid" style="margin-top:40px;">
  <div class="sig-block">
    <div class="sig-line"></div>
    <div>Nom et Signature du référent</div>
  </div>
  <div class="sig-block">
    <div class="sig-line"></div>
    <div>
      Date et Heure :
      <span class="field-val"><?= $fd('date_ref') ?></span>
      <span class="field-val"><?= $fd('heure_ref') ?></span>
    </div>
  </div>
</div>

</div></body></html>
