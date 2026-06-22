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
<div class="doc-title">Attestation de Sortie Contre Avis Médical</div>

<div class="section-header">Identification du signataire</div>

<div class="field-row">
  <span class="field-label">Nom :</span>
  <span class="field-val wide"><?= $fd('signataire_nom') ?></span>
  &nbsp;&nbsp;
  <span class="field-label">Qualité / Lien de parenté :</span>
  <span class="field-val wide"><?= $fd('lien_parente') ?></span>
</div>

<div class="field-row">
  <span class="field-label">Adresse :</span>
  <span class="field-val xwide"><?= $fd('adresse') ?></span>
</div>

<div class="field-row">
  <span class="field-label">CNI N° :</span>
  <span class="field-val wide"><?= $fd('cni_num') ?></span>
  &nbsp;&nbsp;
  <span class="field-label">Délivrée le :</span>
  <span class="field-val"><?= $fd('cni_date') ?></span>
</div>

<div class="section-header">Décision</div>

<p style="font-size:12pt; font-weight:bold; margin:16px 0;">
  DÉCIDE DE <span class="field-val wide"><?= $fd('choix_action') ?></span>
</p>

<div class="field-row">
  <span class="field-label">Patient :</span>
  <span class="field-val wide"><?= $patNom ?></span>
  &nbsp;&nbsp;
  <span class="field-label">Âge :</span>
  <span class="field-val"><?= $patAge ?></span>&nbsp;ans
</div>

<div style="border:2px solid #000; padding:12px 16px; margin:18px 0; font-weight:bold; font-size:11pt; text-align:center;">
  Sur ma propre initiative et contre l'avis du médecin traitant.
</div>

<p>
  Je déclare avoir été informé(e) des risques que cette décision fait courir au malade et dégage par la présente l'Hôpital Saint-Jean de Malte et son personnel de toute responsabilité quant aux conséquences qui pourraient en résulter.
</p>

<p style="margin-top:24px;">
  Fait à Njombé, le <span class="field-val"><?= $fd('date_signature') ?></span>
</p>

<div style="margin-top:30px; text-align:right;">
  <div style="display:inline-block; min-width:220px; text-align:center;">
    <div style="height:70px; border:1px solid #000; margin-bottom:6px;"></div>
    <div>Signature du signataire</div>
  </div>
</div>

</div></body></html>
