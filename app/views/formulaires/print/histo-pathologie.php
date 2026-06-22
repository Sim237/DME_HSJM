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
     DEMANDE D'EXAMEN HISTO-PATHOLOGIQUE
     ═══════════════════════════════════════════════════════════ -->
<?= entete_hopital() ?>
        Service d'Anatomie &amp; Cytologie Pathologiques<br>
        BP 1234 — Tél. : 00 000 000
    </div>
    <div class="hosp-right">
        Dossier N° : <span class="field-val"><?= $dossierNo ?></span><br>
        Date : <span class="field-val"><?= $today ?></span>
    </div>
</div>

<div class="doc-title">Demande d'examen histo-pathologique</div>

<!-- SECTION 1 : LE PRATICIEN -->
<div class="section-header">1. Le praticien demandeur</div>
<div class="grid-2">
    <div class="field-row">
        <span class="field-label">Nom du praticien :</span>&nbsp;
        <span class="field-val wide"><?= $fd('praticien_nom') ?: htmlspecialchars($praticien) ?></span>
    </div>
    <div class="field-row">
        <span class="field-label">Service / Unité :</span>&nbsp;
        <span class="field-val wide"><?= $fd('service') ?></span>
    </div>
</div>

<!-- SECTION 2 : PATIENT -->
<div class="section-header">2. Identification du patient</div>
<div class="grid-2">
    <div>
        <div class="field-row">
            <span class="field-label">Nom &amp; Prénom :</span>&nbsp;
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
            <span class="field-label">N° de dossier :</span>&nbsp;
            <span class="field-val wide"><?= $dossierNo ?></span>
        </div>
    </div>
</div>

<!-- SECTION 3 : RENSEIGNEMENTS CLINIQUES -->
<div class="section-header">3. Renseignements cliniques</div>
<div class="grid-2" style="margin-bottom:10px;">
    <div>
        <div class="field-row">
            <span class="field-label">Nature du prélèvement :</span>&nbsp;
            <span class="field-val wide"><?= $fd('nature_prelevement') ?></span>
        </div>
        <div class="field-row">
            <span class="field-label">Localisation :</span>&nbsp;
            <span class="field-val wide"><?= $fd('localisation') ?></span>
        </div>
    </div>
    <div>
        <div class="field-row">
            <span class="field-label">Date du prélèvement :</span>&nbsp;
            <span class="field-val wide"><?= $fd('date_prelevement') ?></span>
        </div>
    </div>
</div>

<div style="margin-bottom:10px;">
    <div class="field-label" style="margin-bottom:4px;">Contexte clinique :</div>
    <div style="border:1px solid #aaa; min-height:60px; padding:6px 10px;">
        <?= $fd('contexte_clinique') ?>
    </div>
</div>

<!-- SECTION 4 : TYPE D'EXAMEN -->
<div class="section-header">4. Type d'examen demandé</div>
<div style="margin-bottom:12px; display:flex; gap:30px; flex-wrap:wrap;">
    <label style="display:flex; align-items:center; gap:6px; cursor:default;">
        <?= $cb('type_examen', 'examen_standard') ?>
        <span>Examen anatomopathologique standard</span>
    </label>
    <label style="display:flex; align-items:center; gap:6px; cursor:default;">
        <?= $cb('type_examen', 'biopsie_extemporanee') ?>
        <span>Biopsie extemporanée</span>
    </label>
    <label style="display:flex; align-items:center; gap:6px; cursor:default;">
        <?= $cb('type_examen', 'cytoponction') ?>
        <span>Cytoponction</span>
    </label>
</div>

<!-- SECTION 5 : ANTÉCÉDENTS -->
<div class="section-header">5. Antécédents pertinents</div>
<div style="border:1px solid #aaa; min-height:55px; padding:6px 10px; margin-bottom:12px;">
    <?= $fd('antecedents') ?>
</div>

<!-- SIGNATURE -->
<div class="sig-grid">
    <div class="sig-block">
        <div class="sig-line"></div>
        <div>Dr. <?= htmlspecialchars($fd('praticien_nom') ?: $praticien) ?><br><small>Praticien demandeur</small></div>
    </div>
    <div class="sig-block">
        <div class="sig-line"></div>
        <div>Date de réception<br><small>Cachet du laboratoire</small></div>
    </div>
</div>

<div class="footer-note">
    Document généré le <?= $today ?> — Dossier médical N° <?= $dossierNo ?> — HÔPITAL SAINT-JEAN DE MALTE
</div>

</div></body></html>
