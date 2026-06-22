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
     COMPTE-RENDU D'ÉCHOGRAPHIE PELVIENNE
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

<div class="doc-title">Compte-rendu d'échographie pelvienne</div>

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
            <div class="field-row">
                <span class="field-label">Adresse :</span>&nbsp;
                <span class="field-val wide"><?= htmlspecialchars($patient['adresse'] ?? '') ?></span>
            </div>
        </div>
        <div>
            <div class="field-row">
                <span class="field-label">Tél. :</span>&nbsp;
                <span class="field-val wide"><?= htmlspecialchars($patient['telephone'] ?? '') ?></span>
            </div>
            <div class="field-row">
                <span class="field-label">Date de l'examen :</span>&nbsp;
                <span class="field-val wide"><?= $fd('date_examen') ?></span>
            </div>
            <div class="field-row">
                <span class="field-label">Médecin prescripteur :</span>&nbsp;
                <span class="field-val wide"><?= htmlspecialchars($praticien) ?></span>
            </div>
        </div>
    </div>
    <div class="field-row" style="margin-top:4px;">
        <span class="field-label">Indication :</span>&nbsp;
        <span class="field-val xwide"><?= $fd('indication') ?></span>
    </div>
</div>

<!-- SECTION 1 : UTÉRUS -->
<div class="section-header">1. Utérus</div>
<div class="grid-2">
    <div>
        <div class="field-row">
            <span class="field-label">Position :</span>&nbsp;
            <span class="field-val wide"><?= $fd('uterus_position') ?></span>
        </div>
        <div class="field-row">
            <span class="field-label">Dimensions (L × l × ép.) :</span>&nbsp;
            <span class="field-val wide"><?= $fd('uterus_dimensions') ?></span>&nbsp;<span class="field-label">cm</span>
        </div>
    </div>
    <div>
        <div class="field-row">
            <span class="field-label">Épaisseur de l'endomètre :</span>&nbsp;
            <span class="field-val"><?= $fd('endometre_epaisseur') ?></span>&nbsp;<span class="field-label">mm</span>
        </div>
    </div>
</div>

<!-- SECTION 2 : OVAIRES -->
<div class="section-header">2. Ovaires</div>
<div class="grid-2">
    <div>
        <div style="font-weight:bold; margin-bottom:6px; text-decoration:underline;">Ovaire droit</div>
        <div class="field-row">
            <span class="field-label">Dimensions :</span>&nbsp;
            <span class="field-val wide"><?= $fd('ovaire_droit') ?></span>&nbsp;<span class="field-label">cm</span>
        </div>
    </div>
    <div>
        <div style="font-weight:bold; margin-bottom:6px; text-decoration:underline;">Ovaire gauche</div>
        <div class="field-row">
            <span class="field-label">Dimensions :</span>&nbsp;
            <span class="field-val wide"><?= $fd('ovaire_gauche') ?></span>&nbsp;<span class="field-label">cm</span>
        </div>
    </div>
</div>

<!-- SECTION 3 : AUTRES CONSTATATIONS -->
<div class="section-header">3. Autres constatations</div>
<div class="grid-2" style="margin-bottom:10px;">
    <div>
        <div class="field-label" style="margin-bottom:6px;">Épanchement :</div>
        <div class="yn">
            <span class="yn-box"><?= $radio('epanchement', 'oui') ?> Oui</span>
            <span class="yn-box"><?= $radio('epanchement', 'non') ?> Non</span>
        </div>
    </div>
    <div>
        <div class="field-row">
            <span class="field-label">Anomalies :</span>&nbsp;
            <span class="field-val wide"><?= $fd('anomalies') ?></span>
        </div>
    </div>
</div>

<!-- SECTION 4 : CONCLUSION -->
<div class="section-header">4. Conclusion</div>
<div style="border:1px solid #aaa; min-height:70px; padding:6px 10px; margin-bottom:12px; font-weight:bold;">
    <?= $fd('conclusion') ?>
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
