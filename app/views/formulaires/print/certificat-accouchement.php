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
<div class="doc-title">Certificat d'Accouchement</div>

<div style="margin: 30px 0; font-size:11.5pt; line-height:2.4;">
  <p>
    Je soussigné <span class="field-val wide"><?= $fd('praticien_nom') ?: htmlspecialchars($praticien ?? '') ?></span>
    Certifie que
  </p>

  <div class="field-row">
    <span class="field-label">Madame / Mlle :</span>
    <span class="field-val xwide"><?= $patNom ?></span>
  </div>
  <div class="field-row">
    <span class="field-label">Âge :</span>
    <span class="field-val"><?= $patAge ?></span>&nbsp;ans
    &nbsp;&nbsp;
    <span class="field-label">Profession :</span>
    <span class="field-val wide"><?= $fd('profession') ?></span>
  </div>
  <div class="field-row">
    <span class="field-label">Domicile :</span>
    <span class="field-val xwide"><?= $fd('domicile') ?></span>
  </div>

  <p style="margin-top:10px;">
    A accouché à la Maternité de l'Hôpital St Jean de Malte le
    <span class="field-val"><?= $fd('date_accouchement') ?></span>
  </p>

  <div class="field-row">
    <span class="field-label">D'un enfant (</span>
    <span class="field-val"><?= $fd('etat_enfant') ?></span>
    <span class="field-label">) de sexe</span>
    <span class="field-val"><?= $fd('sexe_enfant') ?></span>
  </div>

  <p style="margin-top:14px;">
    En foi de quoi ce certificat est délivré pour servir et valoir ce que de droit.
  </p>
</div>

<div class="sig-grid" style="margin-top:30px;">
  <div class="sig-block">
    <p>Njombé, le <span class="field-val"><?= $fd('date_certificat') ?></span></p>
  </div>
  <div class="sig-block">
    <div class="sig-line"></div>
    <div>Cachet et Signature / Dr. <?= htmlspecialchars($praticien ?? '') ?></div>
  </div>
</div>

<div class="footer-note">
  Hôpital Saint-Jean de Malte — Ordre de Malte — B.P. 56 Njombé, République du Cameroun — Ce certificat est établi à la demande de l'intéressée.
</div>

</div></body></html>
