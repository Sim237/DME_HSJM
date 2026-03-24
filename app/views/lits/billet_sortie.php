<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Billet de Sortie - <?= $billet['nom'] ?></title>
    <style>
        body { font-family: Arial, sans-serif; padding: 40px; color: #333; }
        .billet-box { border: 2px solid #000; padding: 30px; max-width: 700px; margin: 0 auto; position: relative; }
        .header { text-align: center; border-bottom: 2px solid #000; padding-bottom: 20px; margin-bottom: 20px; }
        .hospital-name { font-weight: bold; font-size: 1.4rem; text-transform: uppercase; }
        .doc-title { font-size: 1.8rem; font-weight: 900; margin: 20px 0; text-decoration: underline; }
        .info-row { margin-bottom: 15px; font-size: 1.1rem; }
        .label { font-weight: bold; width: 180px; display: inline-block; }
        .stamp-area { margin-top: 50px; display: flex; justify-content: space-between; }
        .signature-box { text-align: center; width: 250px; }
        .footer-note { font-size: 0.8rem; text-align: center; margin-top: 50px; opacity: 0.7; }
        @media print { .no-print { display: none; } }
    </style>
</head>
<body onload="window.print()">
    <div class="billet-box">
        <div class="header">
            <div class="hospital-name">Ordre de Malte - Hôpital St-Jean de Malte</div>
            <div class="small">B.P. 56 Njombé, Cameroun</div>
            <div class="doc-title">BILLET DE SORTIE</div>
        </div>

        <div class="info-row">
            <span class="label">Patient :</span>
            <strong><?= htmlspecialchars($billet['nom'] . ' ' . $billet['prenom']) ?></strong>
        </div>
        <div class="info-row">
            <span class="label">N° de Dossier :</span>
            <?= htmlspecialchars($billet['dossier_numero']) ?>
        </div>
        <div class="info-row">
            <span class="label">Sexe / Âge :</span>
            <?= $billet['sexe'] ?> / <?= date_diff(date_create($billet['date_naissance']), date_create('now'))->y ?> ans
        </div>
        <div class="info-row">
            <span class="label">Date de Sortie :</span>
            <strong><?= date('d/m/Y à H:i', strtotime($billet['date_sortie'])) ?></strong>
        </div>

        <div class="alert-caissier" style="background: #f8f9fa; border: 1px dashed #000; padding: 10px; margin-top: 30px;">
            <p style="margin: 0; font-size: 0.9rem;"><strong>Note au caissier :</strong> Le patient est autorisé à quitter le service. Veuillez procéder à l'arrêté de compte final.</p>
        </div>

        <div class="stamp-area">
            <div class="signature-box">
                <p>Le Patient / La Famille</p>
                <div style="height: 60px;"></div>
            </div>
            <div class="signature-box">
                <p>Visa du Major de Service</p>
                <div style="height: 60px;"></div>
                <p><strong><?= htmlspecialchars($billet['staff_nom']) ?></strong></p>
            </div>
        </div>

        <div class="footer-note">
            Document généré électroniquement par le DME Hospital. Valable uniquement avec le cachet sec de l'établissement.
        </div>
    </div>

    <div class="no-print" style="text-align: center; margin-top: 20px;">
        <button onclick="window.close()" style="padding: 10px 20px; cursor: pointer;">Fermer cette fenêtre</button>
    </div>
</body>
</html>