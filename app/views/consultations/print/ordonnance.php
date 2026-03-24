<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Ordonnance - <?= htmlspecialchars($ordonnance['nom'] . ' ' . $ordonnance['prenom']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome pour le symbole Rx -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        /* Configuration de l'impression A4 */
        @page {
            size: A4;
            margin: 0;
        }
        body {
            background: #e0e0e0; /* Gris pour voir la feuille à l'écran */
            font-family: 'Times New Roman', serif; /* Police médicale classique */
        }
        .page-a4 {
            background: white;
            width: 210mm;
            min-height: 297mm;
            margin: 20px auto;
            padding: 20mm;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            position: relative;
        }
        
        /* En-tête Hôpital */
        .header-hopital {
            border-bottom: 3px double #0d6efd;
            padding-bottom: 15px;
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .logo-text {
            color: #0d6efd;
            font-weight: bold;
            font-size: 24px;
            text-transform: uppercase;
        }
        
        /* Info Médecin & Patient */
        .doc-info { font-size: 14px; line-height: 1.4; }
        .patient-box {
            border: 1px solid #000;
            padding: 10px 20px;
            border-radius: 8px;
            background-color: #f9f9f9;
            margin-bottom: 30px;
        }
        
        /* Corps de l'ordonnance */
        .rx-symbol {
            font-size: 40px;
            font-style: italic;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .med-list {
            margin-left: 20px;
            font-size: 16px;
        }
        .med-item {
            margin-bottom: 20px;
            border-bottom: 1px dashed #ccc;
            padding-bottom: 10px;
        }
        .med-name {
            font-weight: bold;
            font-size: 18px;
            text-transform: uppercase;
        }
        .med-posologie {
            font-style: italic;
            color: #333;
            margin-left: 15px;
        }
        
        /* Pied de page */
        .footer-page {
            position: absolute;
            bottom: 20mm;
            left: 20mm;
            right: 20mm;
            text-align: center;
            font-size: 12px;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 10px;
        }
        
        /* Signature */
        .signature-box {
            text-align: right;
            margin-top: 50px;
            margin-right: 20px;
        }
        
        /* Mode Impression */
        @media print {
            body { background: white; }
            .page-a4 { margin: 0; box-shadow: none; width: auto; height: auto; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>

    <!-- Barre d'outils (Invisible à l'impression) -->
    <div class="container py-3 no-print text-center">
        <button onclick="window.print()" class="btn btn-primary btn-lg shadow">
            <i class="fa fa-print"></i> Imprimer / Enregistrer en PDF
        </button>
        <button onclick="window.close()" class="btn btn-secondary btn-lg shadow ms-2">Fermer</button>
    </div>

    <!-- Feuille A4 -->
    <div class="page-a4">
        
        <!-- En-tête -->
        <div class="header-hopital">
            <div>
                <div class="logo-text"><i class="fa fa-hospital"></i> <?= htmlspecialchars($hopital['nom_hopital']) ?></div>
                <div class="small"><?= htmlspecialchars($hopital['adresse']) ?></div>
                <div class="small">Tél: <?= htmlspecialchars($hopital['telephone']) ?> | Email: <?= htmlspecialchars($hopital['email']) ?></div>
            </div>
            <div class="text-end doc-info">
                <strong>Dr. <?= htmlspecialchars($ordonnance['medecin_nom'] . ' ' . $ordonnance['medecin_prenom']) ?></strong><br>
                <?= htmlspecialchars($ordonnance['specialite'] ?? 'Médecine Générale') ?><br>
                <small>Ord. N°: <?= str_pad($ordonnance['id'], 6, '0', STR_PAD_LEFT) ?></small>
            </div>
        </div>

        <!-- Date et Patient -->
        <div class="text-end mb-3">
            Le <?= date('d/m/Y', strtotime($ordonnance['date_prescription'])) ?>
        </div>

        <div class="patient-box">
            <div class="row">
                <div class="col-8">
                    <strong>NOM & PRÉNOM :</strong> <?= htmlspecialchars($ordonnance['nom'] . ' ' . $ordonnance['prenom']) ?>
                </div>
                <div class="col-4">
                    <strong>ÂGE :</strong> 
                    <?= date_diff(date_create($ordonnance['date_naissance']), date_create('today'))->y ?> ans
                    (<?= $ordonnance['sexe'] ?>)
                </div>
            </div>
        </div>

        <!-- Liste des médicaments -->
        <div class="rx-symbol">Rx</div>
        
        <div class="med-list">
            <?php if (!empty($medicaments)): ?>
                <?php foreach ($medicaments as $med): ?>
                <div class="med-item">
                    <div class="med-name">
                        <?= htmlspecialchars($med['nom_medicament']) ?>
                        <?php if(!empty($med['forme'])) echo " - <small>" . htmlspecialchars($med['forme']) . "</small>"; ?>
                        <?php if(!empty($med['dosage'])) echo " - " . htmlspecialchars($med['dosage']); ?>
                    </div>
                    <div class="med-posologie">
                        <i class="fa fa-arrow-right small"></i> <?= htmlspecialchars($med['posologie']) ?>
                        <?php if(!empty($med['duree'])) echo " (Pendant " . htmlspecialchars($med['duree']) . ")"; ?>
                        <div class="float-end fw-bold">Qté: <?= $med['quantite_prescrite'] ?></div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="text-center text-muted">Aucun médicament prescrit.</p>
            <?php endif; ?>
        </div>

        <!-- Signature -->
        <div class="signature-box">
            <p>Signature & Cachet</p>
            <br><br><br>
            <p>____________________</p>
        </div>

        <!-- Pied de page -->
        <div class="footer-page">
            <p>Ce document est une ordonnance médicale. Merci de respecter scrupuleusement les doses prescrites.</p>
            <p><?= htmlspecialchars($hopital['nom_hopital']) ?> - Système de Gestion Médical informatisé</p>
        </div>

    </div>

</body>
</html>