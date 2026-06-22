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
<div class="doc-title">Certificat d'Hospitalisation</div>

<div style="margin: 30px 0; font-size:11.5pt; line-height:2.2;">
  <p>
    Je soussigné <span class="field-val wide"><?= $fd('praticien_nom') ?: htmlspecialchars($praticien ?? '') ?></span>
    certifie que
  </p>
  <p>
    <span class="field-val xwide"><?= $patNom ?></span>
  </p>
  <div class="field-row">
    <span class="field-label">Âge :</span>
    <span class="field-val"><?= $patAge ?></span>&nbsp;ans
    &nbsp;&nbsp;
    <span class="field-label">Profession :</span>
    <span class="field-val wide"><?= $fd('profession') ?></span>
  </div>
  <div class="field-row">
    <span class="field-label">est hospitalisé(e)</span>
    <span class="field-val xwide"><?= $fd('periode_hosp') ?></span>
  </div>
</div>

<p style="margin-top:20px;">
  En foi de quoi ce certificat est délivré pour servir et valoir ce que de droit.
</p>

<p style="margin-top:24px;">
  Nyombé, le <span class="field-val"><?= $fd('date_certificat') ?></span>
</p>

<div style="margin-top:40px; text-align:right;">
  <div style="display:inline-block; min-width:220px; text-align:center;">
    <div style="height:70px; border-bottom:1px solid #000; margin-bottom:6px;"></div>
    <div>Cachet et Signature / Dr. <?= htmlspecialchars($praticien ?? '') ?></div>
  </div>
</div>

<div class="footer-note">
  Hôpital Saint-Jean de Malte — Ordre de Malte France — B.P. 56 Njombé, République du Cameroun — Ce certificat est délivré à la demande de l'intéressé(e).
</div>

</div></body></html>
