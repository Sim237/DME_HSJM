<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Résultats d'Analyses - <?= htmlspecialchars($demande['nom'] . ' ' . $demande['prenom']) ?></title>
    <style>
        @media print {
            .no-print { display: none; }
            body { margin: 0; }
        }
        body { font-family: Arial, sans-serif; font-size: 12px; }
        .header { text-align: center; border-bottom: 2px solid #333; padding-bottom: 10px; margin-bottom: 20px; }
        .patient-info { background: #f8f9fa; padding: 15px; margin-bottom: 20px; border-radius: 5px; }
        .results-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .results-table th, .results-table td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        .results-table th { background-color: #f2f2f2; font-weight: bold; }
        .abnormal { background-color: #fff3cd; font-weight: bold; }
        .critical { background-color: #f8d7da; font-weight: bold; color: #721c24; }
        .footer { margin-top: 30px; font-size: 10px; color: #666; }
    </style>
</head>
<body>
    <div class="no-print" style="margin-bottom: 20px;">
        <button onclick="window.print()" class="btn btn-primary">Imprimer</button>
        <button onclick="window.close()" class="btn btn-secondary">Fermer</button>
    </div>

    <div class="header">
        <h1>LABORATOIRE D'ANALYSES MÉDICALES</h1>
        <h2>DME HOSPITAL</h2>
        <p>Résultats d'Analyses Biologiques</p>
    </div>

    <div class="patient-info">
        <div style="display: flex; justify-content: space-between;">
            <div>
                <strong>Patient:</strong> <?= htmlspecialchars($demande['nom'] . ' ' . $demande['prenom']) ?><br>
                <strong>Dossier:</strong> <?= htmlspecialchars($demande['dossier_numero']) ?><br>
                <strong>Date de naissance:</strong> <?= date('d/m/Y', strtotime($demande['date_naissance'])) ?><br>
                <strong>Sexe:</strong> <?= $demande['sexe'] ?>
            </div>
            <div>
                <strong>Médecin prescripteur:</strong> Dr. <?= htmlspecialchars($demande['medecin_nom'] . ' ' . $demande['medecin_prenom']) ?><br>
                <strong>Date de prélèvement:</strong> <?= $demande['date_prelevement'] ? date('d/m/Y H:i', strtotime($demande['date_prelevement'])) : 'Non renseignée' ?><br>
                <strong>Date des résultats:</strong> <?= date('d/m/Y H:i', strtotime($demande['date_resultats'])) ?><br>
                <strong>N° Demande:</strong> <?= $demande['id'] ?>
            </div>
        </div>
    </div>

    <?php if (!empty($resultats)): ?>
        <?php 
        // Grouper par catégorie
        $categories = [];
        foreach ($resultats as $resultat) {
            $categories[$resultat['categorie'] ?? 'AUTRE'][] = $resultat;
        }
        ?>

        <?php foreach ($categories as $categorie => $resultatsCategorie): ?>
        <h3><?= $categorie ?></h3>
        <table class="results-table">
            <thead>
                <tr>
                    <th>Examen</th>
                    <th>Résultat</th>
                    <th>Unité</th>
                    <th>Valeurs de référence</th>
                    <th>Interprétation</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($resultatsCategorie as $resultat): ?>
                <tr class="<?= $resultat['anormal'] ? 'abnormal' : '' ?>">
                    <td><?= htmlspecialchars($resultat['nom_examen']) ?></td>
                    <td>
                        <?php if ($resultat['valeur_numerique']): ?>
                            <?= $resultat['valeur_numerique'] ?>
                        <?php else: ?>
                            <?= htmlspecialchars($resultat['resultat']) ?>
                        <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($resultat['unite']) ?></td>
                    <td>
                        <?php if ($resultat['valeur_normale_min'] && $resultat['valeur_normale_max']): ?>
                            <?= $resultat['valeur_normale_min'] ?> - <?= $resultat['valeur_normale_max'] ?>
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </td>
                    <td><?= nl2br(htmlspecialchars($resultat['interpretation'])) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endforeach; ?>

        <?php if (!empty($demande['notes'])): ?>
        <div style="margin-top: 20px; padding: 10px; background: #f8f9fa; border-radius: 5px;">
            <strong>Notes du laboratoire:</strong><br>
            <?= nl2br(htmlspecialchars($demande['notes'])) ?>
        </div>
        <?php endif; ?>

    <?php else: ?>
        <p>Aucun résultat disponible pour cette demande.</p>
    <?php endif; ?>

    <div class="footer">
        <div style="display: flex; justify-content: space-between; margin-top: 40px;">
            <div>
                <strong>Technicien:</strong> <?= htmlspecialchars($demande['technicien_nom'] ?? 'Non assigné') ?><br>
                <strong>Biologiste:</strong> <?= htmlspecialchars($demande['biologiste_nom'] ?? 'Non validé') ?>
            </div>
            <div>
                <strong>Date d'impression:</strong> <?= date('d/m/Y H:i') ?><br>
                <strong>Statut:</strong> <?= $demande['statut'] ?>
            </div>
        </div>
        
        <div style="text-align: center; margin-top: 20px; font-size: 9px;">
            Ce document est confidentiel et destiné exclusivement au patient et au médecin prescripteur.
        </div>
    </div>

    <script>
        // Auto-print si demandé
        if (window.location.search.includes('auto_print=1')) {
            window.onload = function() {
                setTimeout(function() {
                    window.print();
                }, 500);
            };
        }
    </script>
</body>
</html>