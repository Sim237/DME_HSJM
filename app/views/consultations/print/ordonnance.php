<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Ordonnance — <?= htmlspecialchars(($ordonnance['nom'] ?? '') . ' ' . ($ordonnance['prenom'] ?? '')) ?></title>
    <style>
        /* ══════════════════════════════════════════════
           STYLES ÉCRAN
           ══════════════════════════════════════════════ */
        body {
            background: #e0e0e0;
            font-family: 'Times New Roman', serif;
            margin: 0; padding: 0;
        }
        .toolbar {
            background: rgba(255,255,255,.97);
            border-bottom: 1px solid #dee2e6;
            padding: 8px 20px;
            display: flex;
            gap: 8px;
            align-items: center;
            position: sticky; top: 0; z-index: 100;
        }
        .toolbar .spacer { flex: 1; }
        .page-sheet {
            background: white;
            width: 148mm;
            min-height: 210mm;
            margin: 20px auto;
            padding: 12mm 14mm;
            box-shadow: 0 0 12px rgba(0,0,0,.15);
            position: relative;
        }

        /* En-tête */
        .header-hopital {
            border-bottom: 3px double #0d6efd;
            padding-bottom: 12px;
            margin-bottom: 22px;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }
        .logo-text { color: #0d6efd; font-weight: bold; font-size: 21px; text-transform: uppercase; }
        .doc-info { font-size: 13px; line-height: 1.4; text-align: right; }

        /* Patient */
        .patient-box {
            border: 1px solid #000;
            padding: 8px 16px;
            border-radius: 6px;
            background: #f9f9f9;
            margin-bottom: 22px;
            font-size: 13px;
            display: flex;
            gap: 20px;
        }
        .patient-box > div { flex: 1; }

        /* Rx */
        .rx-symbol { font-size: 36px; font-style: italic; font-weight: bold; margin-bottom: 8px; }
        .med-list { margin-left: 14px; }
        .med-item {
            margin-bottom: 16px;
            border-bottom: 1px dashed #ccc;
            padding-bottom: 8px;
        }
        .med-name   { font-weight: bold; font-size: 15px; text-transform: uppercase; }
        .med-posologie { font-style: italic; color: #333; margin-left: 14px; font-size: 13px; line-height: 1.5; }
        .med-qte { float: right; font-weight: bold; font-size: 13px; }

        /* Signature */
        .signature-box { text-align: right; margin-top: 40px; margin-right: 20px; font-size: 13px; }

        /* Pied de page */
        .footer-page {
            position: absolute; bottom: 16mm; left: 20mm; right: 20mm;
            text-align: center; font-size: 11px; color: #666;
            border-top: 1px solid #ddd; padding-top: 8px;
        }

        /* ══════════════════════════════════════════════
           IMPRESSION — format piloté par l'imprimante
           L'utilisateur choisit le papier dans la boîte
           de dialogue : A4, A5, continu, etc.
           ══════════════════════════════════════════════ */
        @media print {
            @page {
                size: A5 portrait;
                margin: 8mm 10mm;
            }

            body { background: white !important; margin: 0; padding: 0; }

            /* Masquer l'interface */
            .toolbar { display: none !important; }

            /* La feuille épouse le papier choisi */
            .page-sheet {
                width: 100% !important;
                min-height: 0 !important;
                margin: 0 !important;
                padding: 0 !important;   /* @page margin gère les marges */
                box-shadow: none !important;
                font-size: 10pt !important;
            }

            /* En-tête : passer en colonne sur les petits formats */
            .header-hopital { flex-wrap: wrap; gap: 6pt; }
            .doc-info { font-size: 9pt; }

            /* Conserver les colonnes patient */
            .patient-box { font-size: 9pt; }

            /* Forcer le saut de page uniquement si nécessaire */
            .med-item { page-break-inside: avoid; break-inside: avoid; }

            /* Retirer la couleur du titre hôpital */
            .logo-text { color: #000 !important; }

            /* Pied de page en position relative à l'impression */
            .footer-page {
                position: relative;
                bottom: auto; left: auto; right: auto;
                margin-top: 20pt;
                border-top: 1px solid #ccc;
                padding-top: 6pt;
                font-size: 8pt;
            }
        }
    </style>
</head>
<body>

<!-- BARRE D'OUTILS (masquée à l'impression) -->
<div class="toolbar">
    <button onclick="window.print()"
            style="padding:6px 18px;background:#0d6efd;color:white;border:none;border-radius:4px;cursor:pointer;font-size:13px;">
        🖨️ Imprimer
    </button>
    <div class="spacer"></div>
    <button onclick="window.close()"
            style="padding:6px 14px;background:white;color:#6c757d;border:1px solid #6c757d;border-radius:4px;cursor:pointer;font-size:13px;">
        ✕ Fermer
    </button>
</div>

<!-- FEUILLE -->
<div class="page-sheet">

    <!-- EN-TÊTE -->
    <div class="header-hopital">
        <div>
            <div class="logo-text">🏥 <?= htmlspecialchars($hopital['nom_hopital'] ?? 'Hôpital') ?></div>
            <div style="font-size:12px;"><?= htmlspecialchars($hopital['adresse'] ?? '') ?></div>
            <div style="font-size:12px;">
                Tél : <?= htmlspecialchars($hopital['telephone'] ?? '') ?>
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
 if (!empty($hopital['email'])): ?> | <?= htmlspecialchars($hopital['email']) ?><?php endif; ?>
            </div>
        </div>
        <div class="doc-info">
            <strong>Dr. <?= htmlspecialchars(($ordonnance['medecin_nom'] ?? '') . ' ' . ($ordonnance['medecin_prenom'] ?? '')) ?></strong><br>
            <?= htmlspecialchars($ordonnance['specialite'] ?? 'Médecine Générale') ?><br>
            <small>Ord. N° : <?= str_pad($ordonnance['id'] ?? 0, 6, '0', STR_PAD_LEFT) ?></small>
        </div>
    </div>

    <!-- DATE -->
    <div style="text-align:right; margin-bottom:12px; font-size:13px;">
        Le <?= date('d/m/Y', strtotime($ordonnance['date_prescription'] ?? 'now')) ?>
    </div>

    <!-- PATIENT -->
    <div class="patient-box">
        <div>
            <strong>NOM &amp; PRÉNOM :</strong>
            <?= htmlspecialchars(($ordonnance['nom'] ?? '') . ' ' . ($ordonnance['prenom'] ?? '')) ?>
        </div>
        <div>
            <strong>ÂGE :</strong>
            <?php
                try { echo date_diff(date_create($ordonnance['date_naissance']), date_create('today'))->y . ' ans'; }
                catch (Exception $e) { echo '—'; }
            ?>
            &nbsp;(<?= htmlspecialchars($ordonnance['sexe'] ?? '') ?>)
        </div>
    </div>

    <!-- MÉDICAMENTS -->
    <div class="rx-symbol">Rx</div>

    <div class="med-list">
        <?php if (!empty($medicaments)): ?>
            <?php foreach ($medicaments as $i => $med): ?>
            <div class="med-item">
                <span class="med-qte">Qté : <?= (int)($med['quantite_prescrite'] ?? 1) ?></span>
                <div class="med-name">
                    <?= ($i + 1) ?>. <?= htmlspecialchars($med['nom_medicament'] ?? '') ?>
                    <?php if (!empty($med['forme'])):  ?> <span style="font-size:13px;font-weight:normal;">— <?= htmlspecialchars($med['forme']) ?></span><?php endif; ?>
                    <?php if (!empty($med['dosage'])): ?> — <?= htmlspecialchars($med['dosage']) ?><?php endif; ?>
                </div>
                <div class="med-posologie">
                    ➤ <?= htmlspecialchars($med['posologie'] ?? '') ?>
                    <?php if (!empty($med['duree'])): ?> (<?= htmlspecialchars($med['duree']) ?>)<?php endif; ?>
                    <?php if (!empty($med['voie'])): ?>  — voie : <?= htmlspecialchars($med['voie']) ?><?php endif; ?>
                </div>
                <?php if (!empty($med['instructions'])): ?>
                <div style="margin-left:14px;font-size:12px;color:#555;font-style:italic;">
                    ℹ <?= htmlspecialchars($med['instructions']) ?>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p style="color:#888;text-align:center;">Aucun médicament prescrit.</p>
        <?php endif; ?>
    </div>

    <!-- SIGNATURE -->
    <div class="signature-box">
        <p>Signature &amp; Cachet</p>
        <br><br><br>
        <p>____________________</p>
    </div>

    <!-- PIED DE PAGE -->
    <div class="footer-page">
        <p>Ce document est une ordonnance médicale. Merci de respecter scrupuleusement les doses prescrites.</p>
        <p><?= htmlspecialchars($hopital['nom_hopital'] ?? '') ?> — Système de Gestion Médical informatisé</p>
    </div>

</div><!-- /.page-sheet -->

</body>
</html>
