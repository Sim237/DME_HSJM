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

<!-- ═══════════════════════════════════════════════════════════
     COMPTE-RENDU D'ÉCHOGRAPHIE OBSTÉTRICALE — 1ER TRIMESTRE
     ═══════════════════════════════════════════════════════════ -->
<?= entete_hopital() ?>
        Service de Radiologie &amp; Imagerie Médicale<br>
        BP 1234 — Tél. : 00 000 000
    </div>
    <div class="hosp-right">
        Dossier N° : <span class="field-val"><?= $dossierNo ?></span><br>
        Date : <span class="field-val"><?= $today ?></span>
    </div>
</div>

<div class="doc-title">Compte-rendu d'échographie obstétricale — 1<sup>er</sup> trimestre</div>

<!-- ENCADRÉ PATIENT -->
<div style="border:1px solid #000; padding:8px 12px; margin-bottom:14px;">
    <div class="grid-2">
        <div>
            <div class="field-row">
                <span class="field-label">Patiente :</span>&nbsp;
                <span class="field-val wide"><?= $patNom ?></span>
            </div>
            <div class="field-row">
                <span class="field-label">Âge :</span>&nbsp;
                <span class="field-val"><?= $patAge ?> ans</span>
                &nbsp;&nbsp;
                <span class="field-label">Sexe :</span>&nbsp;
                <span class="field-val"><?= $patSexe ?></span>
            </div>
        </div>
        <div>
            <div class="field-row">
                <span class="field-label">Tél. :</span>&nbsp;
                <span class="field-val wide"><?= htmlspecialchars($patient['telephone'] ?? '') ?></span>
            </div>
            <div class="field-row">
                <span class="field-label">Médecin prescripteur :</span>&nbsp;
                <span class="field-val wide"><?= htmlspecialchars($praticien) ?></span>
            </div>
        </div>
    </div>
</div>

<!-- SECTION 1 : PARAMÈTRES DE LA GROSSESSE -->
<div class="section-header">1. Paramètres de la grossesse</div>
<div class="grid-3">
    <div class="field-row">
        <span class="field-label">Date de l'examen :</span>&nbsp;
        <span class="field-val wide"><?= $fd('date_examen') ?></span>
    </div>
    <div class="field-row">
        <span class="field-label">DDR :</span>&nbsp;
        <span class="field-val wide"><?= $fd('ddr') ?></span>
    </div>
    <div class="field-row">
        <span class="field-label">Âge gestationnel :</span>&nbsp;
        <span class="field-val"><?= $fd('age_gestationnel') ?></span>&nbsp;<span class="field-label">SA</span>
    </div>
</div>

<!-- SECTION 2 : BIOMÉTRIE FŒTALE -->
<div class="section-header">2. Biométrie fœtale</div>
<table style="width:100%; border-collapse:collapse; font-size:10.5pt; margin-bottom:10px;">
    <thead>
        <tr style="background:#f0f0f0;">
            <th style="border:1px solid #888; padding:5px 8px; text-align:left;">Mesure</th>
            <th style="border:1px solid #888; padding:5px 8px; text-align:left;">Abréviation</th>
            <th style="border:1px solid #888; padding:5px 8px; text-align:center;">Valeur</th>
            <th style="border:1px solid #888; padding:5px 8px; text-align:center;">Unité</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td style="border:1px solid #888; padding:5px 8px;">Longueur cranio-caudale</td>
            <td style="border:1px solid #888; padding:5px 8px; font-weight:bold;">LCC</td>
            <td style="border:1px solid #888; padding:5px 8px; text-align:center; font-weight:bold;"><?= $fd('lcc') ?></td>
            <td style="border:1px solid #888; padding:5px 8px; text-align:center;">mm</td>
        </tr>
        <tr style="background:#fafafa;">
            <td style="border:1px solid #888; padding:5px 8px;">Diamètre bipariétal</td>
            <td style="border:1px solid #888; padding:5px 8px; font-weight:bold;">BIP</td>
            <td style="border:1px solid #888; padding:5px 8px; text-align:center; font-weight:bold;"><?= $fd('bip') ?></td>
            <td style="border:1px solid #888; padding:5px 8px; text-align:center;">mm</td>
        </tr>
        <tr>
            <td style="border:1px solid #888; padding:5px 8px;">Longueur fémorale</td>
            <td style="border:1px solid #888; padding:5px 8px; font-weight:bold;">LF</td>
            <td style="border:1px solid #888; padding:5px 8px; text-align:center; font-weight:bold;"><?= $fd('lf') ?></td>
            <td style="border:1px solid #888; padding:5px 8px; text-align:center;">mm</td>
        </tr>
    </tbody>
</table>

<!-- SECTION 3 : PLACENTA & LIQUIDE AMNIOTIQUE -->
<div class="section-header">3. Placenta &amp; Liquide amniotique</div>
<div class="grid-2">
    <div>
        <div class="field-row">
            <span class="field-label">Localisation du placenta :</span>&nbsp;
            <span class="field-val wide"><?= $fd('placenta_localisation') ?></span>
        </div>
        <div class="field-row">
            <span class="field-label">Aspect du placenta :</span>&nbsp;
            <span class="field-val wide"><?= $fd('placenta_aspect') ?></span>
        </div>
    </div>
    <div>
        <div class="field-label" style="margin-bottom:6px;">Liquide amniotique :</div>
        <div class="yn">
            <span class="yn-box"><?= $radio('liquide_amniotique', 'normal') ?> Normal</span>
            <span class="yn-box"><?= $radio('liquide_amniotique', 'diminue') ?> Diminué</span>
            <span class="yn-box"><?= $radio('liquide_amniotique', 'augmente') ?> Augmenté</span>
        </div>
    </div>
</div>

<!-- SECTION 4 : CONCLUSION -->
<div class="section-header">4. Conclusion</div>
<div style="border:1px solid #aaa; min-height:60px; padding:6px 10px; margin-bottom:12px; font-weight:bold;">
    <?= $fd('conclusion') ?>
</div>

<div class="field-row" style="margin-top:6px;">
    <span class="field-label">Date de la prochaine échographie :</span>&nbsp;
    <span class="field-val wide"><?= $fd('date_prochaine_echo') ?></span>
</div>

<!-- SIGNATURE -->
<div class="sig-grid">
    <div class="sig-block">
        <div class="sig-line"></div>
        <div>Signature de la patiente</div>
    </div>
    <div class="sig-block">
        <div class="sig-line"></div>
        <div>Dr. <?= htmlspecialchars($praticien) ?><br><small>Médecin / Échographiste</small></div>
    </div>
</div>

<div class="footer-note">
    Document généré le <?= $today ?> — Dossier médical N° <?= $dossierNo ?> — HÔPITAL SAINT-JEAN DE MALTE
</div>

</div></body></html>
