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
     LIGATURE DES TROMPES — CONSENTEMENT ÉCLAIRÉ
     ═══════════════════════════════════════════════════════════ -->
<?= entete_hopital() ?>
        Service de Gynécologie &amp; Obstétrique<br>
        BP 1234 — Tél. : 00 000 000
    </div>
    <div class="hosp-right">
        Dossier N° : <span class="field-val"><?= $dossierNo ?></span><br>
        Date : <span class="field-val"><?= $today ?></span>
    </div>
</div>

<div class="doc-title">Ligature des trompes — Consentement éclairé</div>
<div class="doc-subtitle">Formulaire de consentement libre et éclairé — à conserver dans le dossier médical</div>

<!-- IDENTIFICATION -->
<div style="border:1px solid #000; padding:8px 12px; margin-bottom:16px;">
    <div class="grid-2">
        <div>
            <div class="field-row">
                <span class="field-label">Patiente :</span>&nbsp;
                <span class="field-val wide"><?= $patNom ?></span>
            </div>
            <div class="field-row">
                <span class="field-label">Date de naissance :</span>&nbsp;
                <span class="field-val wide"><?= htmlspecialchars($patient['date_naissance'] ?? '') ?></span>
            </div>
            <div class="field-row">
                <span class="field-label">Lieu de naissance :</span>&nbsp;
                <span class="field-val wide"><?= $fd('lieu_naissance') ?></span>
            </div>
        </div>
        <div>
            <div class="field-row">
                <span class="field-label">Âge :</span>&nbsp;
                <span class="field-val"><?= $fd('age_patient') ?: $patAge ?> ans</span>
            </div>
            <div class="field-row">
                <span class="field-label">Nombre d'enfants :</span>&nbsp;
                <span class="field-val"><?= $fd('nb_enfants') ?></span>
            </div>
            <div class="field-row">
                <span class="field-label">Dernier accouchement :</span>&nbsp;
                <span class="field-val wide"><?= $fd('date_dernier_accouchement') ?></span>
            </div>
        </div>
    </div>
</div>

<!-- CORPS DU CONSENTEMENT -->
<div style="line-height:1.8; font-size:10.5pt;">

    <p style="margin-bottom:12px;">
        Je soussignée, Madame <strong><?= $patNom ?></strong>, née le
        <strong><?= htmlspecialchars($patient['date_naissance'] ?? '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;') ?></strong>
        à <strong><?= $fd('lieu_naissance') ?: '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' ?></strong>,
        mère de <strong><?= $fd('nb_enfants') ?: '___' ?></strong> enfant(s), dont le dernier est né le
        <strong><?= $fd('date_dernier_accouchement') ?: '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' ?></strong>,
        déclare avoir été consultée le <strong><?= $fd('date_consultation') ?: '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' ?></strong>
        par le Dr. <strong><?= htmlspecialchars($fd('praticien_nom') ?: $praticien) ?></strong>
        concernant une intervention de ligature des trompes prévue le
        <strong><?= $fd('date_intervention') ?: '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' ?></strong>.
    </p>

    <!-- INFORMATIONS REÇUES -->
    <div class="section-header">Informations reçues</div>

    <div style="margin-bottom:10px; display:flex; align-items:flex-start; gap:10px;">
        <div style="margin-top:2px;"><?= $radio('complications_info', 'oui') ?></div>
        <div>
            J'ai été informée des <strong>risques et complications possibles</strong> liés à cette intervention
            (infection, hémorragie, lésion des organes adjacents, risque anesthésique) et j'ai eu l'opportunité
            de poser toutes mes questions auxquelles il a été répondu de manière satisfaisante.
        </div>
    </div>

    <div style="margin-bottom:10px; display:flex; align-items:flex-start; gap:10px;">
        <div style="margin-top:2px;"><?= $radio('reversibilite_info', 'oui') ?></div>
        <div>
            J'ai été informée du <strong>caractère définitif et irréversible</strong> de cette méthode de
            contraception. La ligature des trompes n'est pas une méthode réversible et ne peut pas être
            considérée comme une stérilisation temporaire.
        </div>
    </div>

    <div style="margin-bottom:10px; display:flex; align-items:flex-start; gap:10px;">
        <div style="margin-top:2px;"><span class="check-sq checked"></span></div>
        <div>
            J'ai été informée des <strong>alternatives contraceptives</strong> disponibles (contraception hormonale,
            dispositif intra-utérin, préservatifs) et j'ai librement et volontairement choisi la ligature des trompes.
        </div>
    </div>

    <div style="margin-bottom:10px; display:flex; align-items:flex-start; gap:10px;">
        <div style="margin-top:2px;"><span class="check-sq checked"></span></div>
        <div>
            Je donne mon <strong>consentement libre, éclairé et révocable</strong> à la réalisation de cette
            intervention. Je confirme avoir disposé d'un délai de réflexion suffisant avant de prendre cette
            décision définitive.
        </div>
    </div>

    <div style="border:1px dashed #aaa; padding:8px 12px; margin:14px 0; font-style:italic; font-size:10pt;">
        Ce consentement peut être retiré à tout moment avant le début de l'intervention en informant l'équipe médicale.
    </div>

</div>

<!-- DATE DE SIGNATURE -->
<div class="field-row" style="margin-top:10px;">
    <span class="field-label">Fait à :</span>&nbsp;
    <span class="field-val wide"></span>
    &nbsp;&nbsp;&nbsp;
    <span class="field-label">Le :</span>&nbsp;
    <span class="field-val wide"><?= $fd('date_signature') ?: $today ?></span>
</div>

<!-- SIGNATURE -->
<div class="sig-grid">
    <div class="sig-block">
        <div class="sig-line"></div>
        <div>
            Signature de la patiente<br>
            <small>(Précédée de la mention « Lu et approuvé »)</small>
        </div>
    </div>
    <div class="sig-block">
        <div class="sig-line"></div>
        <div>
            Dr. <?= htmlspecialchars($fd('praticien_nom') ?: $praticien) ?><br>
            <small>Gynécologue / Praticien responsable</small>
        </div>
    </div>
</div>

<div class="footer-note">
    Document généré le <?= $today ?> — Dossier médical N° <?= $dossierNo ?> — HÔPITAL SAINT-JEAN DE MALTE<br>
    Ce document doit être signé en deux exemplaires : un pour le dossier médical, un remis à la patiente.
</div>

</div></body></html>
