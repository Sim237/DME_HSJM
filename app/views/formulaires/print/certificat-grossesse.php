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
<div class="doc-title">Certificat de Grossesse</div>

<div style="margin: 30px 0; font-size:11.5pt; line-height:2.4;">
  <p>
    Je soussigné, <span class="field-val wide"><?= $fd('praticien_nom') ?: htmlspecialchars($praticien ?? '') ?></span>
    certifie avoir fait subir le
    <span class="field-val"><?= $fd('date_examen') ?></span>
    à Madame <span class="field-val wide"><?= $patNom ?></span>
    l'examen général et obstétrical prévu par la loi.
  </p>

  <p>
    Je certifie qu'elle est enceinte de
    <span class="field-val wide"><?= $fd('duree_grossesse') ?></span>
    et qu'elle accouchera vers le
    <span class="field-val"><?= $fd('date_accouchement_prevue') ?></span>.
  </p>

  <p>
    En foi de quoi le présent certificat lui est délivré pour servir et valoir ce que de droit.
  </p>
</div>

<div class="sig-grid" style="margin-top:40px;">
  <div class="sig-block">
    <p>Nyombé, le <span class="field-val"><?= $fd('date_certificat') ?></span></p>
  </div>
  <div class="sig-block">
    <div class="sig-line"></div>
    <div>Le Praticien / Dr. <?= htmlspecialchars($praticien ?? '') ?></div>
  </div>
</div>

</div></body></html>
