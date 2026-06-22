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

<style>
/* Styles spécifiques Info Mère-Travail-Enfant */
.mte-header-band {
    background: #f59e0b;
    color: white;
    text-align: center;
    font-weight: 900;
    font-size: 11pt;
    text-transform: uppercase;
    letter-spacing: 1.5px;
    padding: 6px 10px;
    margin-bottom: 10px;
    border-radius: 2px;
}
.mte-diag-box {
    border: 1.5px solid #f59e0b;
    border-radius: 3px;
    padding: 5px 10px;
    margin-bottom: 12px;
    min-height: 28px;
    background: #fffbeb;
}
.mte-diag-box .lbl {
    font-size: 7.5pt;
    text-transform: uppercase;
    color: #92400e;
    font-weight: 700;
    letter-spacing: .5px;
}
.mte-diag-box .val {
    font-size: 11pt;
    font-weight: bold;
}
.mte-gp-grid {
    display: grid;
    grid-template-columns: 2fr 1fr 1fr 1fr 1.5fr;
    gap: 10px;
    margin-bottom: 12px;
    align-items: end;
}
.mte-gp-block .lbl {
    font-size: 7.5pt;
    text-transform: uppercase;
    color: #555;
    font-weight: 700;
    letter-spacing: .4px;
    margin-bottom: 2px;
}
.mte-gp-block .val {
    border-bottom: 1px solid #000;
    min-height: 20px;
    font-weight: bold;
    font-size: 11pt;
    padding: 0 4px;
    display: block;
}
.radio-row {
    display: flex;
    gap: 18px;
    align-items: center;
    flex-wrap: wrap;
    margin-bottom: 4px;
}
.radio-item {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    font-size: 10.5pt;
}
.mte-section-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
    margin-bottom: 10px;
}
.mte-field-block { margin-bottom: 10px; }
.mte-field-block .lbl {
    font-size: 8pt;
    text-transform: uppercase;
    color: #555;
    font-weight: 700;
    letter-spacing: .4px;
    margin-bottom: 4px;
    display: block;
}
.accouche-boxes {
    display: flex;
    gap: 20px;
    margin-bottom: 6px;
}
.accouche-box {
    display: flex;
    align-items: center;
    gap: 8px;
    border: 1.5px solid #aaa;
    border-radius: 4px;
    padding: 5px 12px;
    font-size: 11pt;
    font-weight: bold;
}
.accouche-box.selected {
    border-color: #f59e0b;
    background: #fffbeb;
}
.diag-final-box {
    border: 1px solid #aaa;
    min-height: 55px;
    padding: 6px 10px;
    margin-bottom: 14px;
    font-weight: bold;
    font-size: 11pt;
}
</style>
<?= entete_hopital() ?>
    <div class="hosp-right">
        Dossier N° : <span class="field-val"><?= $dossierNo ?></span><br>
        Date : <span class="field-val"><?= $today ?></span>
    </div>
</div>

<!-- TITRE -->
<div class="doc-title" style="font-size:13pt;">Information sur la Mère, le Travail et l'Enfant</div>

<!-- DIAGNOSTIC D'ENTRÉE -->
<div class="mte-diag-box">
    <div class="lbl">Diagnostic (admission)</div>
    <div class="val"><?= $fd('diagnostic_top', '&nbsp;') ?></div>
</div>

<!-- INFORMATIONS GÉNÉRALES -->
<div class="section-header">1. Informations générales</div>

<div class="mte-gp-grid">
    <div class="mte-gp-block">
        <div class="lbl">Nom de la mère</div>
        <span class="val"><?= $patNom ?></span>
    </div>
    <div class="mte-gp-block">
        <div class="lbl">Âge</div>
        <span class="val"><?= $fd('mere_age', (string)$patAge) ?></span>
    </div>
    <div class="mte-gp-block">
        <div class="lbl">G</div>
        <span class="val"><?= $fd('gestite') ?></span>
    </div>
    <div class="mte-gp-block">
        <div class="lbl">P</div>
        <span class="val"><?= $fd('parite') ?></span>
    </div>
    <div class="mte-gp-block">
        <div class="lbl">Âge grossesse (SA)</div>
        <span class="val"><?= $fd('age_grossesse') ?></span>
    </div>
</div>

<div class="field-row">
    <span class="field-label">BDCF (Battements du cœur fœtal) :</span>&nbsp;
    <span class="field-val wide"><?= $fd('bdcf') ?></span>
</div>

<!-- SÉROLOGIES -->
<div class="section-header">2. Sérologies</div>
<div class="mte-section-grid">
    <div class="mte-field-block">
        <span class="lbl">LAV (VIH) :</span>
        <div class="radio-row">
            <span class="radio-item"><?= $radio('lav', 'positif') ?> <strong>Positif</strong></span>
            <span class="radio-item"><?= $radio('lav', 'negatif') ?> Négatif</span>
        </div>
    </div>
    <div class="mte-field-block">
        <span class="lbl">AgHBs (Hépatite B) :</span>
        <div class="radio-row">
            <span class="radio-item"><?= $radio('aghbs', 'positif') ?> <strong>Positif</strong></span>
            <span class="radio-item"><?= $radio('aghbs', 'negatif') ?> Négatif</span>
        </div>
    </div>
</div>

<!-- ÉTAT DES MEMBRANES -->
<div class="section-header">3. État des membranes &amp; Liquide amniotique</div>
<div class="mte-section-grid">
    <div class="mte-field-block">
        <span class="lbl">Membranes :</span>
        <div class="radio-row" style="margin-top:4px;">
            <span class="radio-item"><?= $radio('membranes', 'intact') ?> Intact</span>
            <span class="radio-item"><?= $radio('membranes', 'rompu_moins6h') ?> Rompu &lt; 6 h</span>
            <span class="radio-item"><?= $radio('membranes', 'rompu_moins12h') ?> Rompu &lt; 12 h</span>
            <span class="radio-item"><?= $radio('membranes', 'rompu_plus24h') ?> Rompu &gt; 24 h</span>
        </div>
    </div>
    <div class="mte-field-block">
        <span class="lbl">LA (Liquide amniotique) :</span>
        <div class="radio-row" style="margin-top:4px;">
            <span class="radio-item"><?= $radio('liquide_amniotique', 'clair') ?> Clair</span>
            <span class="radio-item"><?= $radio('liquide_amniotique', 'jaunatre') ?> Jaunâtre</span>
            <span class="radio-item"><?= $radio('liquide_amniotique', 'verdatre') ?> Verdâtre</span>
            <span class="radio-item"><?= $radio('liquide_amniotique', 'meconial') ?> Méconial</span>
        </div>
    </div>
</div>

<!-- ACCOUCHEMENT -->
<div class="section-header">4. Mode d'accouchement</div>
<div class="accouche-boxes">
    <div class="accouche-box <?= ($formData['accouchement'] ?? '') === 'vb' ? 'selected' : '' ?>">
        <?= $radio('accouchement', 'vb') ?>&nbsp; Voie basse (VB)
    </div>
    <div class="accouche-box <?= ($formData['accouchement'] ?? '') === 'cs' ? 'selected' : '' ?>">
        <?= $radio('accouchement', 'cs') ?>&nbsp; Césarienne (CS)
    </div>
</div>

<!-- DIAGNOSTIC FINAL -->
<div class="section-header">5. Diagnostic final</div>
<div class="diag-final-box"><?= $fd('diagnostic_bottom', '&nbsp;') ?></div>

<!-- SIGNATURE -->
<div class="sig-grid">
    <div class="sig-block">
        <div class="sig-line"></div>
        <div>
            Sage-femme / Médecin<br>
            <small>Nom &amp; Signature</small>
        </div>
    </div>
    <div class="sig-block">
        <div class="sig-line"></div>
        <div>
            Date &amp; Heure d'accouchement<br>
            <small>à compléter</small>
        </div>
    </div>
</div>

<div class="footer-note">
    Information sur la Mère, le Travail et l'Enfant — Hôpital Saint-Jean de Malte — Njombé, Cameroun
    — Dossier N° <?= $dossierNo ?> — Généré le <?= $today ?>
</div>

</div></body></html>
