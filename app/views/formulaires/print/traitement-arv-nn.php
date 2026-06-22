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
     FICHE DE TRAITEMENT ARV DU NOUVEAU-NÉ
     PRÉVENTION DE LA TRANSMISSION MÈRE-ENFANT (PTME)
     ═══════════════════════════════════════════════════════════ -->

<style>
/* Styles spécifiques à ce document */
.banner-confidentiel {
    background: #c00;
    color: #fff;
    text-align: center;
    font-weight: bold;
    font-size: 10pt;
    letter-spacing: 1.5px;
    text-transform: uppercase;
    padding: 5px 10px;
    margin-bottom: 6px;
    border-radius: 2px;
}
.arv-table { width:100%; border-collapse:collapse; font-size:10.5pt; margin-bottom:10px; }
.arv-table th { background:#f0f0f0; border:1px solid #888; padding:5px 8px; text-align:left; }
.arv-table td { border:1px solid #888; padding:5px 8px; }
.arv-table tr:nth-child(even) td { background:#fafafa; }
</style>

<!-- BANNIÈRE CONFIDENTIELLE -->
<?= entete_hopital() ?>
        Service de Maternité / PTME<br>
        BP 1234 — Tél. : 00 000 000
    </div>
    <div class="hosp-right">
        Dossier N° : <span class="field-val"><?= $dossierNo ?></span><br>
        Date : <span class="field-val"><?= $today ?></span>
    </div>
</div>

<div class="doc-title">Fiche de traitement ARV du nouveau-né</div>
<div class="doc-subtitle">Protocole de prévention de la transmission mère-enfant du VIH (PTME)</div>

<!-- SECTION 1 : IDENTIFICATION DU NOUVEAU-NÉ -->
<div class="section-header">1. Identification du nouveau-né</div>
<div class="grid-3">
    <div class="field-row">
        <span class="field-label">Date de naissance :</span>&nbsp;
        <span class="field-val wide"><?= $fd('date_naissance_nn') ?></span>
    </div>
    <div class="field-row">
        <span class="field-label">Poids de naissance :</span>&nbsp;
        <span class="field-val"><?= $fd('poids_naissance') ?></span>&nbsp;<span class="field-label">g</span>
    </div>
    <div class="field-row">
        <span class="field-label">Terme :</span>&nbsp;
        <span class="field-val"><?= $fd('terme') ?></span>&nbsp;<span class="field-label">SA</span>
    </div>
</div>
<div class="field-row">
    <span class="field-label">Mère (dossier) :</span>&nbsp;
    <span class="field-val wide"><?= $patNom ?></span>
    &nbsp;&nbsp;
    <span class="field-label">N° dossier :</span>&nbsp;
    <span class="field-val"><?= $dossierNo ?></span>
</div>

<!-- SECTION 2 : STATUT DE LA MÈRE -->
<div class="section-header">2. Statut virologique de la mère</div>
<div class="grid-2">
    <div>
        <div class="field-label" style="margin-bottom:6px;">Statut VIH de la mère :</div>
        <div class="yn" style="margin-bottom:10px;">
            <span class="yn-box"><?= $radio('statut_mere', 'positif') ?> VIH positif</span>
            <span class="yn-box"><?= $radio('statut_mere', 'inconnu') ?> Statut inconnu</span>
        </div>
        <div class="field-row">
            <span class="field-label">Traitement ARV mère :</span>&nbsp;
            <span class="field-val wide"><?= $fd('traitement_mere') ?></span>
        </div>
    </div>
    <div>
        <div class="field-row">
            <span class="field-label">Charge virale (CV) :</span>&nbsp;
            <span class="field-val wide"><?= $fd('cv_mere') ?></span>&nbsp;<span class="field-label">cop/mL</span>
        </div>
    </div>
</div>

<!-- SECTION 3 : PROTOCOLE ARV DU NOUVEAU-NÉ -->
<div class="section-header">3. Protocole ARV du nouveau-né</div>

<div style="margin-bottom:8px;">
    <div class="field-label" style="margin-bottom:6px;">Médicament(s) prescrit(s) :</div>
    <div style="display:flex; gap:24px; flex-wrap:wrap; margin-bottom:8px;">
        <label style="display:flex; align-items:center; gap:6px; cursor:default;">
            <?= $radio('protocole_arv', 'NVP') ?>
            <span><strong>NVP</strong> (Névirapine)</span>
        </label>
        <label style="display:flex; align-items:center; gap:6px; cursor:default;">
            <?= $radio('protocole_arv', 'AZT+NVP') ?>
            <span><strong>AZT + NVP</strong></span>
        </label>
        <label style="display:flex; align-items:center; gap:6px; cursor:default;">
            <?= $radio('protocole_arv', 'autre') ?>
            <span>Autre protocole</span>
        </label>
    </div>
</div>

<table class="arv-table">
    <thead>
        <tr>
            <th>Médicament</th>
            <th>Dose prescrite</th>
            <th>Voie</th>
            <th>Fréquence</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td><strong>NVP</strong> (Névirapine)</td>
            <td style="font-weight:bold;"><?= $fd('dose_nvp') ?></td>
            <td>Orale</td>
            <td>Dose unique à la naissance</td>
        </tr>
        <tr>
            <td><strong>AZT</strong> (Zidovudine)</td>
            <td style="font-weight:bold;"><?= $fd('dose_azt') ?></td>
            <td>Orale</td>
            <td>2× / jour pendant 4–6 semaines</td>
        </tr>
    </tbody>
</table>

<div class="grid-2">
    <div class="field-row">
        <span class="field-label">Date de début du traitement :</span>&nbsp;
        <span class="field-val wide"><?= $fd('date_debut_traitement') ?></span>
    </div>
    <div class="field-row">
        <span class="field-label">Durée prévue :</span>&nbsp;
        <span class="field-val wide"><?= $fd('duree_traitement') ?></span>
    </div>
</div>

<!-- SECTION 4 : PROPHYLAXIE AU COTRIMOXAZOLE -->
<div class="section-header">4. Prophylaxie au cotrimoxazole</div>
<div class="grid-2">
    <div>
        <div class="field-label" style="margin-bottom:6px;">Cotrimoxazole prescrit :</div>
        <div class="yn">
            <span class="yn-box"><?= $radio('cotrimoxazole', 'oui') ?> Oui</span>
            <span class="yn-box"><?= $radio('cotrimoxazole', 'non') ?> Non</span>
        </div>
    </div>
    <div class="field-row">
        <span class="field-label">Date de début :</span>&nbsp;
        <span class="field-val wide"><?= $fd('date_debut_cotri') ?></span>
    </div>
</div>

<!-- SECTION 5 : OBSERVATIONS -->
<div class="section-header">5. Observations &amp; remarques cliniques</div>
<div style="border:1px solid #aaa; min-height:60px; padding:6px 10px; margin-bottom:12px;">
    <?= $fd('observations') ?>
</div>

<!-- SIGNATURE -->
<div class="sig-grid">
    <div class="sig-block">
        <div class="sig-line"></div>
        <div>
            <?= htmlspecialchars($praticien) ?><br>
            <small>Sage-femme / Médecin référent PTME</small>
        </div>
    </div>
    <div class="sig-block">
        <div class="sig-line"></div>
        <div>
            Date de signature :<br>
            <strong><?= $today ?></strong>
        </div>
    </div>
</div>

<div class="footer-note">
    Document confidentiel — VIH/SIDA — généré le <?= $today ?> — Dossier N° <?= $dossierNo ?> — HÔPITAL SAINT-JEAN DE MALTE<br>
    Ce document est soumis au secret médical. Il ne doit pas être communiqué sans le consentement de la patiente.
</div>

</div></body></html>
