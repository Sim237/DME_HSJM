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
/* Styles spécifiques Perfucode */
.perfu-title-band {
    background: #0ea5e9;
    color: white;
    text-align: center;
    font-weight: 900;
    font-size: 18pt;
    letter-spacing: 3px;
    text-transform: uppercase;
    padding: 8px 0 6px;
    margin-bottom: 4px;
    border-radius: 2px;
}
.perfu-subtitle {
    text-align: center;
    font-size: 9pt;
    color: #555;
    margin-bottom: 14px;
    font-style: italic;
}
.perfu-ligne-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 14px;
}
.perfu-ligne-table td {
    padding: 4px 8px;
    font-size: 11pt;
    vertical-align: bottom;
}
.perfu-ligne-table .num-cell {
    width: 28px;
    font-weight: 900;
    color: #0ea5e9;
    font-size: 12pt;
    text-align: center;
}
.perfu-dotted-line {
    display: block;
    border-bottom: 1px dotted #555;
    min-width: 340px;
    height: 22px;
    font-weight: bold;
    padding: 0 4px;
}
.perfu-info-grid {
    display: grid;
    grid-template-columns: 1fr 1fr 1fr;
    gap: 14px;
    margin-bottom: 14px;
}
.perfu-info-block { }
.perfu-info-block .label {
    font-size: 8pt;
    text-transform: uppercase;
    color: #555;
    letter-spacing: .5px;
    margin-bottom: 2px;
}
.perfu-info-block .val {
    border-bottom: 1px solid #000;
    min-height: 20px;
    font-weight: bold;
    font-size: 11pt;
    padding: 0 4px;
}
.perfu-medic-box {
    border: 1.5px solid #0ea5e9;
    border-radius: 4px;
    padding: 6px 12px;
    margin-bottom: 14px;
    background: #f0f9ff;
}
.perfu-medic-box .label {
    font-size: 8pt;
    text-transform: uppercase;
    color: #0369a1;
    font-weight: 700;
    letter-spacing: .5px;
}
.perfu-medic-box .val {
    font-size: 12pt;
    font-weight: bold;
    margin-top: 2px;
}
</style>
<?= entete_hopital() ?>
    <div class="hosp-right">
        Dossier N° : <span class="field-val"><?= $dossierNo ?></span><br>
        Date : <span class="field-val"><?= $today ?></span>
    </div>
</div>

<!-- TITRE PERFUCODE -->
<div class="perfu-title-band">PERFUCODE</div>
<div class="perfu-subtitle">Fiche de prescription et de suivi de perfusion</div>

<!-- INFORMATIONS PATIENT + HORAIRES -->
<div class="perfu-info-grid">
    <div class="perfu-info-block">
        <div class="label">Patient</div>
        <div class="val"><?= $patNom ?></div>
    </div>
    <div class="perfu-info-block">
        <div class="label">Heure de début</div>
        <div class="val"><?= $fd('debut_heure') ?></div>
    </div>
    <div class="perfu-info-block">
        <div class="label">Heure de fin</div>
        <div class="val"><?= $fd('fin_heure') ?></div>
    </div>
</div>

<!-- MÉDICATION PRINCIPALE -->
<div class="perfu-medic-box">
    <div class="label">Médication / Soluté principal</div>
    <div class="val"><?= $fd('medication', '— non renseigné —') ?></div>
</div>

<!-- LIGNES DE PERFUSION -->
<div class="section-header">Lignes de perfusion</div>
<table class="perfu-ligne-table">
    <?php for ($i = 1; $i <= 5; $i++): ?>
    <tr>
        <td class="num-cell"><?= $i ?>.</td>
        <td>
            <span class="perfu-dotted-line"><?= $fd('ligne_' . $i) ?></span>
        </td>
    </tr>
    <?php endfor; ?>
</table>

<!-- SIGNATURE INFIRMIER -->
<div class="sig-grid">
    <div class="sig-block">
        <div class="sig-line"></div>
        <div>
            Infirmier(ère) :<br>
            <strong><?= $fd('infirmier') ?></strong>
        </div>
    </div>
    <div class="sig-block">
        <div class="sig-line"></div>
        <div>
            Médecin prescripteur<br>
            <small>Cachet &amp; Signature</small>
        </div>
    </div>
</div>

<div class="footer-note">
    PERFUCODE — Hôpital Saint-Jean de Malte — Njombé, Cameroun — Dossier N° <?= $dossierNo ?> — Généré le <?= $today ?>
</div>

</div></body></html>
