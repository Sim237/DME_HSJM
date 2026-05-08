<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Ordonnance N°<?= str_pad($ordonnance['id'], 6, '0', STR_PAD_LEFT) ?></title>
    <style>
        @page { size: A4; margin: 0; }
        body { background: #e0e0e0; font-family: 'Times New Roman', serif; }

        .page-a4 {
            background: white; width: 210mm; min-height: 297mm;
            margin: 20px auto; padding: 20mm 22mm; box-shadow: 0 0 10px rgba(0,0,0,0.1);
            position: relative;
        }

        /* En-tête */
        .header { display: flex; justify-content: space-between; border-bottom: 2px solid #1a4a8e; padding-bottom: 15px; margin-bottom: 20px; }
        .header-left .hopital-nom { font-size: 16pt; font-weight: bold; color: #1a4a8e; }
        .header-left .hopital-info { font-size: 9pt; color: #555; }
        .header-right { text-align: right; }
        .header-right .ord-num { font-size: 11pt; font-weight: bold; color: #1a4a8e; }

        /* Médecin */
        .medecin-bloc { background: #f8fafc; border-left: 4px solid #1a4a8e; padding: 10px 15px; margin-bottom: 20px; }
        .medecin-nom { font-size: 13pt; font-weight: bold; }
        .medecin-info { font-size: 9pt; color: #666; }

        /* Patient */
        .patient-bloc { display: flex; justify-content: space-between; margin-bottom: 25px; }
        .patient-left label { font-size: 8pt; color: #888; font-weight: bold; text-transform: uppercase; }
        .patient-left .patient-nom { font-size: 13pt; font-weight: bold; }
        .patient-left .patient-detail { font-size: 9pt; color: #555; }
        .patient-right { text-align: right; font-size: 9pt; color: #555; }

        /* Rx */
        .rx-symbol { font-size: 36pt; font-weight: bold; color: #1a4a8e; margin-bottom: 10px; }

        /* Médicaments */
        .med-item { margin-bottom: 20px; }
        .med-item .med-numero { font-size: 9pt; color: #888; font-weight: bold; margin-bottom: 3px; }
        .med-item .med-nom { font-size: 13pt; font-weight: bold; text-decoration: underline; }
        .med-item .med-dosage { font-size: 10pt; color: #444; }
        .med-item .med-posologie { font-size: 11pt; margin-top: 4px; }
        .med-item .med-qte { font-size: 9pt; color: #666; font-style: italic; }
        .med-item .med-duree { font-size: 10pt; color: #333; }

        .separator { border: none; border-top: 1px dashed #ccc; margin: 15px 0; }

        /* Footer */
        .footer { position: absolute; bottom: 20mm; left: 22mm; right: 22mm; }
        .signature-bloc { display: flex; justify-content: flex-end; margin-top: 40px; }
        .signature-zone { text-align: center; width: 180px; }
        .signature-line { border-top: 1px solid #333; margin-top: 60px; padding-top: 5px; font-size: 9pt; }

        .watermark { position: absolute; top: 50%; left: 50%; transform: translate(-50%,-50%) rotate(-45deg);
            font-size: 80pt; color: rgba(26,74,142,0.04); font-weight: bold; pointer-events: none; }

        @media print {
            body { background: white; }
            .page-a4 { margin: 0; box-shadow: none; width: 100%; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>

<div class="no-print" style="text-align:center;padding:15px;background:#1a4a8e;color:white;">
    <strong>Ordonnance N°<?= str_pad($ordonnance['id'], 6, '0', STR_PAD_LEFT) ?></strong>
    &nbsp;&nbsp;|&nbsp;&nbsp;
    <button onclick="window.print()" style="background:white;color:#1a4a8e;border:none;padding:6px 20px;border-radius:20px;font-weight:bold;cursor:pointer;">
        🖨 Imprimer
    </button>
</div>

<div class="page-a4">
    <div class="watermark">Rx</div>

    <!-- EN-TÊTE -->
    <div class="header">
        <div class="header-left">
            <div class="hopital-nom">Hôpital Saint-Jean de Malte</div>
            <div class="hopital-info">Njombé, Région du Littoral, Cameroun<br>Tel : +237 XXX XXX XXX</div>
        </div>
        <div class="header-right">
            <div class="ord-num">ORDONNANCE N°<?= str_pad($ordonnance['id'], 6, '0', STR_PAD_LEFT) ?></div>
            <div style="font-size:9pt;color:#555;">Le <?= date('d/m/Y', strtotime($ordonnance['date_creation'])) ?></div>
        </div>
    </div>

    <!-- MÉDECIN -->
    <div class="medecin-bloc">
        <div class="medecin-nom">Dr <?= htmlspecialchars($ordonnance['med_prenom'] . ' ' . $ordonnance['med_nom']) ?></div>
        <div class="medecin-info">Médecin — Service des Consultations</div>
    </div>

    <!-- PATIENT -->
    <div class="patient-bloc">
        <div class="patient-left">
            <label>Patient</label>
            <div class="patient-nom"><?= htmlspecialchars(strtoupper($ordonnance['pat_nom']) . ' ' . $ordonnance['pat_prenom']) ?></div>
            <div class="patient-detail">
                Dossier : <?= htmlspecialchars($ordonnance['dossier_numero'] ?? '') ?>
                &bull; <?= !empty($ordonnance['date_naissance']) ? date_diff(date_create($ordonnance['date_naissance']), date_create('today'))->y . ' ans' : '?' ?>
                &bull; <?= $ordonnance['sexe'] === 'F' ? 'Féminin' : 'Masculin' ?>
            </div>
        </div>
        <div class="patient-right">
            <div>Date : <?= date('d/m/Y') ?></div>
        </div>
    </div>

    <!-- Rx + Médicaments -->
    <div class="rx-symbol">Rx</div>

    <?php foreach ($medicaments as $i => $med): ?>
    <div class="med-item">
        <div class="med-numero">Médicament <?= $i + 1 ?></div>
        <div class="med-nom"><?= htmlspecialchars($med['nom_medicament']) ?>
            <?php if (!empty($med['dosage'])): ?>
            <span style="font-size:10pt;font-weight:normal;text-decoration:none;"> – <?= htmlspecialchars($med['dosage']) ?></span>
            <?php endif; ?>
            <?php if (!empty($med['forme'])): ?>
            <span style="font-size:10pt;font-weight:normal;text-decoration:none;">(<?= htmlspecialchars($med['forme']) ?>)</span>
            <?php endif; ?>
        </div>
        <div class="med-posologie">
            <?php if (!empty($med['posologie'])): ?>
            Posologie : <?= htmlspecialchars($med['posologie']) ?>
            <?php endif; ?>
        </div>
        <div class="med-duree">
            <?php if (!empty($med['duree'])): ?>
            Durée : <?= htmlspecialchars($med['duree']) ?>
            <?php endif; ?>
            <?php if (!empty($med['quantite'])): ?>
            &mdash; Quantité : <?= htmlspecialchars($med['quantite']) ?>
            <?php endif; ?>
        </div>
    </div>
    <?php if ($i < count($medicaments) - 1): ?>
    <hr class="separator">
    <?php endif; ?>
    <?php endforeach; ?>

    <?php if (empty($medicaments)): ?>
    <p style="color:#aaa;font-style:italic;">Aucun médicament prescrit.</p>
    <?php endif; ?>

    <!-- SIGNATURE -->
    <div class="signature-bloc">
        <div class="signature-zone">
            <div style="font-size:9pt;color:#888;margin-bottom:5px;">Cachet et Signature du Médecin</div>
            <div style="height:70px;border:1px dashed #ccc;border-radius:8px;"></div>
            <div class="signature-line">Dr <?= htmlspecialchars($ordonnance['med_prenom'] . ' ' . $ordonnance['med_nom']) ?></div>
        </div>
    </div>
</div>
</body>
</html>
