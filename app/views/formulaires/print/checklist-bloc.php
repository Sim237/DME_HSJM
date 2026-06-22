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
  <span style="font-size:9.5pt;">BLOC OPÉRATOIRE</span>
</div>

<div class="doc-title">Entrée au Bloc : Check List</div>

<?php
/* Helper local : affiche ☑ OUI  ☐ NON  ou  ☐ OUI  ☑ NON */
$ouiNon = function(string $key) use ($formData): string {
    $v = strtolower($formData[$key] ?? '');
    $isOui = in_array($v, ['oui', 'o', '1', 'true', 'yes'], true);
    $ouiBox = $isOui ? '☑' : '☐';
    $nonBox = $isOui ? '☐' : '☑';
    return "<span>{$ouiBox}&nbsp;OUI &nbsp; {$nonBox}&nbsp;NON</span>";
};
?>

<!-- SECTION 1 : Identification -->
<div class="section-header">1. Identification</div>
<div class="field-row">
  <span class="field-label">Nom :</span>
  <span class="field-val wide"><?= $patNom ?></span>
  &nbsp;&nbsp;
  <span class="field-label">Âge :</span>
  <span class="field-val"><?= $patAge ?></span>&nbsp;ans
  &nbsp;&nbsp;
  <span class="field-label">Sexe :</span>
  <span class="field-val"><?= $patSexe ?></span>
</div>
<div class="field-row">
  <span class="field-label">Diagnostic :</span>
  <span class="field-val xwide"><?= $fd('diagnostic') ?></span>
</div>

<!-- SECTION 2 : Modalités pratiques -->
<div class="section-header">2. Modalités pratiques</div>
<table style="width:100%; border-collapse:collapse; font-size:10pt; margin-bottom:8px;">
  <thead>
    <tr style="background:#f0f0f0;">
      <th style="text-align:left; padding:4px 8px; border:1px solid #aaa;">Élément</th>
      <th style="text-align:center; padding:4px 8px; border:1px solid #aaa; width:160px;">OUI / NON</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td style="padding:4px 8px; border:1px solid #aaa;">Dossier médical complet</td>
      <td style="text-align:center; padding:4px 8px; border:1px solid #aaa;"><?= $ouiNon('check_dossier') ?></td>
    </tr>
    <tr>
      <td style="padding:4px 8px; border:1px solid #aaa;">Service informé</td>
      <td style="text-align:center; padding:4px 8px; border:1px solid #aaa;"><?= $ouiNon('check_informe') ?></td>
    </tr>
    <tr>
      <td style="padding:4px 8px; border:1px solid #aaa;">
        Type de chirurgie :
        <span style="margin-left:12px;"><?= $radio('type_chir', 'propre') ?> Propre</span>
        <span style="margin-left:12px;"><?= $radio('type_chir', 'sale') ?> Sale</span>
      </td>
      <td style="padding:4px 8px; border:1px solid #aaa; text-align:center;">
        <span class="field-val"><?= $fd('type_chir') ?></span>
      </td>
    </tr>
  </tbody>
</table>

<!-- SECTION 3 : Préparation -->
<div class="section-header">3. Préparation du patient</div>
<table style="width:100%; border-collapse:collapse; font-size:10pt; margin-bottom:8px;">
  <thead>
    <tr style="background:#f0f0f0;">
      <th style="text-align:left; padding:4px 8px; border:1px solid #aaa;">Élément</th>
      <th style="text-align:center; padding:4px 8px; border:1px solid #aaa; width:160px;">OUI / NON</th>
    </tr>
  </thead>
  <tbody>
    <?php
    $prepItems = [
        'consentement'   => 'Consentement signé',
        'consult_anesth' => 'Consultation anesthésique',
        'visite_anesth'  => 'Visite pré-anesthésique',
        'a_jeun'         => 'À jeun',
        'cathe_vesical'  => 'Cathétérisme vésical',
        'toilette'       => 'Toilette pré-opératoire',
        'site_op'        => 'Préparation du site opératoire',
    ];
    foreach ($prepItems as $k => $label): ?>
    <tr>
      <td style="padding:4px 8px; border:1px solid #aaa;"><?= htmlspecialchars($label) ?></td>
      <td style="text-align:center; padding:4px 8px; border:1px solid #aaa;"><?= $ouiNon($k) ?></td>
    </tr>
    <?php endforeach; ?>
    <tr>
      <td style="padding:4px 8px; border:1px solid #aaa;" colspan="2">
        <strong>Préparation colique :</strong>
        &nbsp; <?= $ouiNon('lavemenent') ?> Lavement
        &nbsp;&nbsp; <?= $ouiNon('sng_irrig') ?> SNG / Irrigation
        &nbsp;&nbsp; <?= $ouiNon('x_prep') ?> X-Prep
      </td>
    </tr>
  </tbody>
</table>

<!-- SECTION 4 : Bilans pré-op -->
<div class="section-header">4. Bilans pré-opératoires</div>
<?php
$bilans = ['NFS','TP','TCK','GS-Rh','Urée','Créat','LAV','Ionogramme','Radiographies','Echographie','TDM'];
$bilanKeys = ['nfs','tp','tck','gs_rh','uree','creat','lav','ionogramme','radio','echo','tdm'];
?>
<div style="display:flex; flex-wrap:wrap; gap:8px 24px; margin-bottom:10px;">
  <?php foreach ($bilans as $i => $b): ?>
  <span><?= $cb($bilanKeys[$i], '1') ?> <?= htmlspecialchars($b) ?></span>
  <?php endforeach; ?>
</div>
<div class="field-row">
  <span class="field-label">Poches de sang :</span>
  <span class="field-val"><?= $fd('poches') ?></span>
</div>

<!-- SECTION 5 : Paramètres -->
<div class="section-header">5. Paramètres vitaux</div>
<div style="display:flex; gap:30px; flex-wrap:wrap; margin-bottom:14px;">
  <div class="field-row">
    <span class="field-label">TA :</span>
    <span class="field-val"><?= $fd('param_ta') ?></span>
  </div>
  <div class="field-row">
    <span class="field-label">Pouls :</span>
    <span class="field-val"><?= $fd('param_pouls') ?></span>
  </div>
  <div class="field-row">
    <span class="field-label">Temp :</span>
    <span class="field-val"><?= $fd('param_temp') ?></span>&nbsp;°C
  </div>
  <div class="field-row">
    <span class="field-label">Poids :</span>
    <span class="field-val"><?= $fd('param_poids') ?></span>&nbsp;kg
  </div>
</div>

<div style="margin-top:auto; text-align:right; padding-top:20px;">
  <div style="display:inline-block; min-width:220px; text-align:center;">
    <div style="height:60px; border-bottom:1px solid #000; margin-bottom:6px;"></div>
    <div>Signature Major / Infirmier</div>
  </div>
</div>

</div></body></html>
