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
     FICHE DES PARAMÈTRES DU NOUVEAU-NÉ
     ═══════════════════════════════════════════════════════════ -->
<?= entete_hopital() ?>
        Service de Maternité / Néonatologie<br>
        BP 1234 — Tél. : 00 000 000
    </div>
    <div class="hosp-right">
        Dossier N° : <span class="field-val"><?= $dossierNo ?></span><br>
        Date : <span class="field-val"><?= $today ?></span>
    </div>
</div>

<div class="doc-title">Fiche des paramètres du nouveau-né</div>

<!-- ENCADRÉ MÈRE -->
<div style="border:1px solid #000; padding:8px 12px; margin-bottom:14px;">
    <div style="font-weight:bold; font-size:9pt; text-transform:uppercase; margin-bottom:6px;">Identité de la mère</div>
    <div class="grid-2">
        <div>
            <div class="field-row">
                <span class="field-label">Nom &amp; Prénom :</span>&nbsp;
                <span class="field-val wide"><?= $fd('nom_mere') ?: $patNom ?></span>
            </div>
            <div class="field-row">
                <span class="field-label">Âge :</span>&nbsp;
                <span class="field-val"><?= $patAge ?> ans</span>
                &nbsp;&nbsp;
                <span class="field-label">Tél. :</span>&nbsp;
                <span class="field-val wide"><?= htmlspecialchars($patient['telephone'] ?? '') ?></span>
            </div>
        </div>
        <div>
            <div class="field-row">
                <span class="field-label">Groupe sanguin mère :</span>&nbsp;
                <span class="field-val"><?= $fd('groupe_sanguin_mere') ?></span>
            </div>
            <div class="field-row">
                <span class="field-label">Sage-femme / Infirmière :</span>&nbsp;
                <span class="field-val wide"><?= htmlspecialchars($praticien) ?></span>
            </div>
        </div>
    </div>
</div>

<!-- SECTION 1 : IDENTITÉ DU NOUVEAU-NÉ -->
<div class="section-header">1. Identité du nouveau-né</div>
<div class="grid-3">
    <div class="field-row">
        <span class="field-label">Date de naissance :</span>&nbsp;
        <span class="field-val wide"><?= $fd('date_naissance_nn') ?></span>
    </div>
    <div class="field-row">
        <span class="field-label">Heure :</span>&nbsp;
        <span class="field-val"><?= $fd('heure_naissance') ?></span>
    </div>
    <div class="field-row">
        <span class="field-label">Terme :</span>&nbsp;
        <span class="field-val"><?= $fd('terme') ?></span>&nbsp;<span class="field-label">SA</span>
    </div>
</div>
<div class="field-row">
    <span class="field-label">Sexe :</span>&nbsp;
    <div class="yn">
        <span class="yn-box"><?= $radio('sexe_nn', 'M') ?> Masculin</span>
        <span class="yn-box"><?= $radio('sexe_nn', 'F') ?> Féminin</span>
        <span class="yn-box"><?= $radio('sexe_nn', 'indetermine') ?> Indéterminé</span>
    </div>
</div>

<!-- SECTION 2 : ANTHROPOMÉTRIE -->
<div class="section-header">2. Anthropométrie</div>
<table style="width:100%; border-collapse:collapse; font-size:10.5pt; margin-bottom:10px;">
    <thead>
        <tr style="background:#f0f0f0;">
            <th style="border:1px solid #888; padding:5px 10px; text-align:left;">Paramètre</th>
            <th style="border:1px solid #888; padding:5px 10px; text-align:center;">Valeur</th>
            <th style="border:1px solid #888; padding:5px 10px; text-align:center;">Unité</th>
            <th style="border:1px solid #888; padding:5px 10px; text-align:center;">Norme</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td style="border:1px solid #888; padding:5px 10px;">Poids de naissance</td>
            <td style="border:1px solid #888; padding:5px 10px; text-align:center; font-weight:bold;"><?= $fd('poids_naissance') ?></td>
            <td style="border:1px solid #888; padding:5px 10px; text-align:center;">g</td>
            <td style="border:1px solid #888; padding:5px 10px; text-align:center; color:#555; font-size:9pt;">2 500 – 4 000 g</td>
        </tr>
        <tr style="background:#fafafa;">
            <td style="border:1px solid #888; padding:5px 10px;">Taille</td>
            <td style="border:1px solid #888; padding:5px 10px; text-align:center; font-weight:bold;"><?= $fd('taille') ?></td>
            <td style="border:1px solid #888; padding:5px 10px; text-align:center;">cm</td>
            <td style="border:1px solid #888; padding:5px 10px; text-align:center; color:#555; font-size:9pt;">48 – 52 cm</td>
        </tr>
        <tr>
            <td style="border:1px solid #888; padding:5px 10px;">Périmètre crânien</td>
            <td style="border:1px solid #888; padding:5px 10px; text-align:center; font-weight:bold;"><?= $fd('perimetre_cranien') ?></td>
            <td style="border:1px solid #888; padding:5px 10px; text-align:center;">cm</td>
            <td style="border:1px solid #888; padding:5px 10px; text-align:center; color:#555; font-size:9pt;">33 – 37 cm</td>
        </tr>
        <tr style="background:#fafafa;">
            <td style="border:1px solid #888; padding:5px 10px;">Périmètre thoracique</td>
            <td style="border:1px solid #888; padding:5px 10px; text-align:center; font-weight:bold;"><?= $fd('perimetre_thoracique') ?></td>
            <td style="border:1px solid #888; padding:5px 10px; text-align:center;">cm</td>
            <td style="border:1px solid #888; padding:5px 10px; text-align:center; color:#555; font-size:9pt;">31 – 35 cm</td>
        </tr>
    </tbody>
</table>

<!-- SECTION 3 : SCORE D'APGAR -->
<div class="section-header">3. Score d'APGAR</div>
<table style="width:60%; border-collapse:collapse; font-size:10.5pt; margin-bottom:10px;">
    <thead>
        <tr style="background:#f0f0f0;">
            <th style="border:1px solid #888; padding:5px 10px; text-align:left;">Moment</th>
            <th style="border:1px solid #888; padding:5px 10px; text-align:center;">Score (/ 10)</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td style="border:1px solid #888; padding:5px 10px;">À 1 minute</td>
            <td style="border:1px solid #888; padding:5px 10px; text-align:center; font-weight:bold; font-size:12pt;"><?= $fd('apgar_1min') ?></td>
        </tr>
        <tr style="background:#fafafa;">
            <td style="border:1px solid #888; padding:5px 10px;">À 5 minutes</td>
            <td style="border:1px solid #888; padding:5px 10px; text-align:center; font-weight:bold; font-size:12pt;"><?= $fd('apgar_5min') ?></td>
        </tr>
    </tbody>
</table>

<!-- SECTION 4 : ACCOUCHEMENT -->
<div class="section-header">4. Accouchement</div>
<div class="grid-2">
    <div>
        <div class="field-label" style="margin-bottom:6px;">Type d'accouchement :</div>
        <div class="yn">
            <span class="yn-box"><?= $radio('type_accouchement', 'voie_basse') ?> Voie basse</span>
            <span class="yn-box"><?= $radio('type_accouchement', 'cesarienne') ?> Césarienne</span>
        </div>
    </div>
    <div>
        <div class="field-label" style="margin-bottom:6px;">État général à la naissance :</div>
        <div class="yn">
            <span class="yn-box"><?= $radio('etat_general', 'bon') ?> Bon</span>
            <span class="yn-box"><?= $radio('etat_general', 'moyen') ?> Moyen</span>
            <span class="yn-box"><?= $radio('etat_general', 'mauvais') ?> Mauvais</span>
        </div>
    </div>
</div>

<!-- SECTION 5 : DONNÉES BIOLOGIQUES -->
<div class="section-header">5. Données biologiques</div>
<div class="grid-2">
    <div class="field-row">
        <span class="field-label">Groupe sanguin mère :</span>&nbsp;
        <span class="field-val"><?= $fd('groupe_sanguin_mere') ?></span>
    </div>
    <div class="field-row">
        <span class="field-label">Groupe sanguin nouveau-né :</span>&nbsp;
        <span class="field-val"><?= $fd('groupe_sanguin_nn') ?></span>
    </div>
</div>

<!-- SECTION 6 : COMPLICATIONS / OBSERVATIONS -->
<div class="section-header">6. Complications / Observations</div>
<div style="border:1px solid #aaa; min-height:55px; padding:6px 10px; margin-bottom:12px;">
    <?= $fd('complications') ?>
</div>

<!-- SIGNATURE -->
<div class="sig-grid">
    <div class="sig-block">
        <div class="sig-line"></div>
        <div>Signature de la mère</div>
    </div>
    <div class="sig-block">
        <div class="sig-line"></div>
        <div><?= htmlspecialchars($praticien) ?><br><small>Sage-femme / Infirmier(ère)</small></div>
    </div>
</div>

<div class="footer-note">
    Document généré le <?= $today ?> — Dossier médical N° <?= $dossierNo ?> — HÔPITAL SAINT-JEAN DE MALTE
</div>

</div></body></html>
