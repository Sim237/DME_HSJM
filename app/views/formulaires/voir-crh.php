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
 require_once __DIR__ . '/../layouts/header.php'; ?>

<style>
    /* ── Base ── */
    * { box-sizing: border-box; }
    body { background: #e8ecf0; }

    .paper-sheet {
        background: white;
        width: 21cm;
        padding: 1.8cm 2cm 2cm;
        margin: 24px auto;
        box-shadow: 0 6px 24px rgba(0,0,0,.14);
        font-family: "Times New Roman", Times, serif;
        color: #000;
        font-size: 11pt;
        line-height: 1.55;
    }

    /* En-tête */
    .header-table { width: 100%; margin-bottom: 16px; border-collapse: collapse; }
    .logo-img { height: 70px; }
    .hosp-info { text-align: right; font-size: 9pt; line-height: 1.4; font-weight: bold; vertical-align: top; }

    /* Titre */
    .doc-title { text-align: center; margin: 18px 0 14px; }
    .doc-title h2 { font-weight: 900; margin-bottom: 2px; font-size: 15pt; letter-spacing: .3px; }
    .doc-title p  { font-style: italic; font-size: 10pt; margin: 0; color: #444; }

    /* Grille info */
    .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px 20px; margin-bottom: 10px; }
    .info-grid > div { font-size: 10.5pt; }

    /* Étiquette anglaise */
    .label-eng { font-style: italic; color: #555; font-weight: normal; font-size: 9pt; }

    /* Valeur de champ (soulignée) */
    .field-val {
        font-weight: bold;
        border-bottom: 1px solid #555;
        display: inline-block;
        min-width: 180px;
        padding: 0 4px 1px;
        vertical-align: baseline;
    }

    /* Section title */
    .section-title {
        font-weight: bold; text-align: center;
        background: #f4f4f4; padding: 5px 8px;
        margin: 14px 0 8px;
        border-top: 1.5px solid #000; border-bottom: 1.5px solid #000;
        font-size: 10.5pt;
    }

    /* Bloc texte — s'adapte au contenu, PAS de hauteur fixe */
    .text-block {
        width: 100%;
        min-height: 36px;       /* juste assez pour voir le bloc si vide */
        overflow: visible;      /* jamais de scrollbar */
        border: 1px solid #ddd;
        border-radius: 3px;
        padding: 8px 10px;
        margin-bottom: 10px;
        white-space: pre-wrap;
        word-break: break-word;
        font-size: 10.5pt;
        line-height: 1.5;
        page-break-inside: avoid;
    }

    /* Signature */
    .sig-wrap { display: flex; justify-content: space-between; align-items: flex-end; margin-top: 24px; }
    .sig-block {
        position: relative;
        width: 260px;
        height: 150px;
        text-align: center;
    }
    .sig-block .sig-cachet {
        position: absolute; top: 0; left: 0;
        width: 100%; height: 100%; object-fit: contain;
    }
    .sig-block .sig-signature {
        position: absolute; bottom: 6px; left: 50%;
        transform: translateX(-50%);
        width: 70%; max-height: 90px; object-fit: contain; z-index: 2;
    }

    /* ── IMPRESSION A4 ── */
    @media print {
        @page {
            size: A4 portrait;
            margin: 1.2cm 1.5cm;
        }

        body, html { background: white !important; margin: 0 !important; padding: 0 !important; }
        .no-print, .action-bar { display: none !important; }

        .paper-sheet {
            width: 100% !important;
            margin: 0 !important;
            padding: 0 !important;
            box-shadow: none !important;
            font-size: 10pt !important;
        }

        /* Blocs texte : jamais de scrollbar ni de hauteur fixe, tout le contenu visible */
        .text-block {
            border: none !important;
            border-bottom: 1px solid #ccc !important;
            border-radius: 0 !important;
            padding: 4px 0 !important;
            margin-bottom: 6px !important;
            min-height: 0 !important;
            overflow: visible !important;
            font-size: 10pt !important;
        }

        .section-title {
            margin: 10px 0 6px !important;
            padding: 3px 6px !important;
            font-size: 10pt !important;
        }

        .doc-title { margin: 10px 0 8px !important; }
        .doc-title h2 { font-size: 13pt !important; }
        .info-grid { gap: 6px 16px !important; margin-bottom: 6px !important; }

        .sig-block { height: 120px !important; }
        .sig-wrap  { margin-top: 16px !important; }

        /* Éviter les coupures de page dans les sections importantes */
        .info-grid, .section-title, .sig-wrap { page-break-inside: avoid; }
        .text-block { page-break-inside: auto; }
    }
</style>

<div class="container-fluid bg-light pb-5">
    <!-- BARRE D'ACTIONS -->
    <div class="d-flex justify-content-between align-items-center py-3 px-4 bg-white border-bottom shadow-sm no-print action-bar">
        <a href="<?= BASE_URL ?>patients/dossier/<?= $patient['id'] ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Retour au dossier
        </a>
        <div class="d-flex gap-2">
            <?php if ($crh['signe']): ?>
                <span class="badge bg-success rounded-pill px-3 py-2 align-self-center">
                    <i class="bi bi-patch-check-fill me-1"></i> Document signé
                </span>
            <?php endif; ?>
            <button class="btn btn-primary" onclick="window.print()"><i class="bi bi-printer"></i> Imprimer</button>
        </div>
    </div>

    <div class="paper-sheet">
        <!-- EN-TÊTE -->
        <table class="header-table">
            <tr>
                <td>
                    <img src="<?= BASE_URL ?>public/images/logo_ordre_malte.png" class="logo-img" alt="Logo">
                    <div class="fw-bold small mt-1">HÔPITAL SAINT-JEAN DE MALTE</div>
                </td>
                <td class="hosp-info">
                    HÔPITAL SAINT-JEAN DE MALTE<br>
                    BP.: 56 NJOMBE - CAMEROUN<br>
                    Tél.: (237) 697 09 29 92<br>
                    233 21 10 22
                </td>
            </tr>
        </table>

        <div class="doc-title">
            <h2>COMPTE-RENDU D'HOSPITALISATION</h2>
            <p>HOSPITALISATION REPORT</p>
        </div>

        <!-- IDENTITÉ PATIENT -->
        <div class="info-grid">
            <div>Nom : <span class="field-val"><?= htmlspecialchars($patient['nom']) ?></span><br><span class="label-eng">Name</span></div>
            <div>Prénom : <span class="field-val"><?= htmlspecialchars($patient['prenom']) ?></span><br><span class="label-eng">Surname</span></div>
        </div>
        <div class="info-grid">
            <div>Age : <span class="field-val"><?= $age ?> ans</span></div>
            <div>Date de naissance : <span class="field-val"><?= date('d/m/Y', strtotime($patient['date_naissance'])) ?></span></div>
        </div>

        <!-- INFOS MÉDICALES -->
        <div class="mt-3">
            Date d'entrée : <span class="field-val"><?= $crh['date_entree'] ? date('d/m/Y', strtotime($crh['date_entree'])) : '—' ?></span><br>
            Diagnostic d'entrée : <span class="field-val"><?= htmlspecialchars($crh['diag_entree'] ?? '—') ?></span><br>
            Médecin traitant : <span class="field-val">Dr. <?= htmlspecialchars($crh['medecin_nom'] . ' ' . $crh['medecin_prenom']) ?></span>
        </div>

        <!-- ÉVOLUTION -->
        <div class="section-title">
            Compte rendu du traitement et de l'évolution /
            <span class="label-eng">Report of treatment and evolution</span>
        </div>
        <div class="text-block"><?= nl2br(htmlspecialchars($crh['evolution'] ?? '')) ?></div>

        <div style="margin-bottom:8px;">
            Date de sortie : <span class="field-val"><?= $crh['date_sortie'] ? date('d/m/Y', strtotime($crh['date_sortie'])) : '—' ?></span>
        </div>

        <div style="font-weight:bold;margin-bottom:4px;">Diagnostic de sortie / <span class="label-eng">Discharge diagnostic :</span></div>
        <div class="text-block"><?= nl2br(htmlspecialchars($crh['diag_sortie'] ?? '')) ?></div>

        <div style="font-weight:bold;margin-bottom:4px;">Traitement prescrit à la sortie / <span class="label-eng">Prescribed treatment at discharge :</span></div>
        <div class="text-block"><?= nl2br(htmlspecialchars($crh['traitement_sortie'] ?? '')) ?></div>

        <div style="margin-bottom:8px;">
            Rendez-vous / <span class="label-eng">Appointment :</span>
            <span class="field-val" style="min-width:300px;"><?= htmlspecialchars($crh['rendez_vous'] ?? '—') ?></span>
        </div>

        <!-- SIGNATURE -->
        <div class="sig-wrap">
            <div style="font-size:10.5pt;">
                Njombé, le&nbsp;/ <span class="label-eng">The</span>
                <span class="field-val"><?= htmlspecialchars($crh['date_signature'] ?? date('d/m/Y')) ?></span>
            </div>
            <div style="text-align:center;">
                <div style="font-weight:bold;font-size:10.5pt;margin-bottom:4px;">Signature &amp; Cachet</div>
                <?php if (!empty($crh['signature_data']) || !empty($crh['cachet_data'])): ?>
                    <div class="sig-block">
                        <?php if (!empty($crh['cachet_data'])): ?>
                            <img src="<?= htmlspecialchars($crh['cachet_data']) ?>" class="sig-cachet" alt="Cachet">
                        <?php endif; ?>
                        <?php if (!empty($crh['signature_data'])): ?>
                            <img src="<?= htmlspecialchars($crh['signature_data']) ?>" class="sig-signature" alt="Signature">
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div style="width:260px;height:80px;border-bottom:1.5px solid #000;"></div>
                <?php endif; ?>
                <div style="font-weight:bold;font-size:10.5pt;margin-top:4px;">Dr. <?= htmlspecialchars($crh['medecin_prenom'] . ' ' . $crh['medecin_nom']) ?></div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
