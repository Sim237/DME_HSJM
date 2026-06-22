<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Résultats — <?= htmlspecialchars(($demande['nom'] ?? '') . ' ' . ($demande['prenom'] ?? '')) ?></title>
    <style>
        * { box-sizing: border-box; }
        body { background: #e8e8e8; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; font-size: 12px; color: #000; margin: 0; }

        /* --- Barre d'actions --- */
        .action-bar {
            position: sticky; top: 0; z-index: 1000;
            background: rgba(255,255,255,0.95); backdrop-filter: blur(5px);
            padding: 10px 24px; border-bottom: 1px solid #ddd;
            display: flex; justify-content: space-between; align-items: center;
            box-shadow: 0 2px 8px rgba(0,0,0,.1);
        }
        .action-bar .btn {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 7px 18px; border-radius: 6px; font-size: 13px;
            font-weight: 600; cursor: pointer; border: none; text-decoration: none;
        }
        .btn-primary { background: #0891b2; color: #fff; }
        .btn-secondary { background: #6c757d; color: #fff; }
        .btn-primary:hover { background: #0e7490; }
        .btn-secondary:hover { background: #5a6268; }

        /* --- Feuille A4 --- */
        .paper-sheet {
            background: white; width: 21cm; min-height: 27cm;
            padding: 1.2cm 1.5cm; margin: 20px auto;
            box-shadow: 0 4px 15px rgba(0,0,0,0.18);
            position: relative;
        }

        /* --- En-tête hôpital --- */
        .hospital-header {
            display: flex; justify-content: space-between; align-items: flex-start;
            border-bottom: 2px solid #333; padding-bottom: 12px; margin-bottom: 0;
        }
        .logo-area { display: flex; align-items: center; gap: 12px; }
        .logo-area img { height: 70px; }
        .hospital-name h5 { font-weight: 800; margin: 0; color: #333; letter-spacing: 1px; font-size: 14px; }
        .hospital-name .sub { font-size: 11px; font-weight: 700; }
        .hospital-name .addr { font-size: 10px; color: #666; }
        .dept-checkboxes {
            border: 1.5px solid #000; padding: 8px 12px;
            border-radius: 4px; font-size: 11px; background: #f8f9fa;
        }
        .dept-checkboxes label { display: flex; align-items: center; gap: 5px; font-weight: 700; margin-bottom: 3px; cursor: default; }
        .dept-checkboxes input[type=checkbox] { width: 13px; height: 13px; cursor: default; }

        /* --- Titre --- */
        .form-title { text-align: center; margin: 18px 0 14px; }
        .form-title h2 { font-weight: 900; text-decoration: underline; text-underline-offset: 8px; font-size: 1.7rem; margin: 0; }
        .form-title .subtitle { font-size: 0.85rem; font-weight: 700; color: #0891b2; text-transform: uppercase; letter-spacing: 1px; margin-top: 4px; }

        /* --- Info patient --- */
        .form-dotted {
            display: inline-block; border-bottom: 1px dotted #444;
            padding: 0 4px; font-weight: 600; color: #0d6efd; min-width: 120px;
        }
        .patient-info-grid {
            display: grid; grid-template-columns: 1fr 1fr;
            gap: 6px 20px; margin-bottom: 16px; line-height: 1.9; font-size: 12px;
        }

        /* --- Grille principale --- */
        .main-content-grid {
            display: grid; grid-template-columns: 38% 62%;
            border: 2px solid #000; min-height: 550px;
        }
        .grid-col { padding: 12px; display: flex; flex-direction: column; }
        .grid-col-left  { border-right: 2px solid #000; background: #fff; }
        .grid-col-right { background: #fdfdfd; }
        .col-header {
            text-align: center; font-weight: bold; text-decoration: underline;
            margin-bottom: 14px; text-transform: uppercase; font-size: 0.85rem;
        }

        /* --- Liste examens (colonne gauche) --- */
        .exam-list-item {
            display: flex; align-items: flex-start; gap: 6px;
            padding: 5px 0; border-bottom: 1px dotted #ccc; font-size: 11px;
        }
        .exam-list-item:last-child { border-bottom: none; }
        .exam-num { color: #888; min-width: 18px; font-size: 10px; }
        .exam-name { font-weight: 600; flex: 1; }
        .badge-urgent { background: #fee2e2; color: #991b1b; font-size: 9px; padding: 1px 5px; border-radius: 8px; font-weight: 700; }
        .badge-cat { background: #e0f2fe; color: #0369a1; font-size: 9px; padding: 1px 5px; border-radius: 8px; font-weight: 600; }

        /* --- Résultats (colonne droite) --- */
        .cat-title {
            font-size: 11px; font-weight: 800; text-transform: uppercase;
            color: #0891b2; border-bottom: 1px solid #0891b2;
            padding-bottom: 3px; margin: 10px 0 6px; letter-spacing: .5px;
        }
        .cat-title:first-child { margin-top: 0; }
        .results-table { width: 100%; border-collapse: collapse; margin-bottom: 10px; font-size: 11px; }
        .results-table th { background: #f0f9ff; font-weight: 700; padding: 4px 6px; border: 1px solid #b0d8e8; font-size: 10px; text-transform: uppercase; color: #0369a1; }
        .results-table td { border: 1px solid #dde; padding: 4px 6px; vertical-align: top; }
        .results-table tr.abnormal td { background: #fffbeb; font-weight: 700; }
        .results-table tr.critical td { background: #fef2f2; color: #991b1b; font-weight: 700; }
        .results-table .td-name { font-weight: 600; }
        .results-table .td-value { text-align: center; min-width: 55px; }
        .results-table .td-unit { text-align: center; color: #555; min-width: 40px; }
        .results-table .td-ref { color: #555; min-width: 70px; text-align: center; }
        .anormal-flag { color: #dc2626; font-size: 10px; margin-left: 3px; font-weight: 700; }
        .no-result { color: #aaa; font-style: italic; }

        /* --- Signatures --- */
        .signature-row {
            display: flex; justify-content: space-between; align-items: flex-end;
            margin-top: auto; padding-top: 12px; border-top: 1px solid #eee;
            font-size: 11px;
        }
        .sig-block { text-align: center; }
        .sig-line { border-bottom: 1px solid #999; width: 120px; height: 35px; margin: 0 auto 4px; }

        /* --- Pièces jointes --- */
        .pj-section { margin-top: 10px; padding: 8px; background: #f8faff; border: 1px solid #dde; border-radius: 4px; font-size: 11px; }
        .pj-section strong { font-size: 10px; text-transform: uppercase; color: #555; }
        .pj-list { margin: 5px 0 0; padding: 0; list-style: none; }
        .pj-list li { padding: 2px 0; display: flex; align-items: center; gap: 5px; }
        .pj-list li::before { content: '📎'; font-size: 10px; }

        /* --- Footer --- */
        .doc-footer {
            display: flex; justify-content: space-between;
            font-size: 10px; color: #555; margin-top: 12px;
            padding-top: 8px; border-top: 1px solid #eee;
        }
        .confidential { text-align: center; margin-top: 12px; font-size: 9px; color: #888; }

        /* --- Statut badge --- */
        .statut-badge { font-size: 9px; font-weight: 700; padding: 2px 7px; border-radius: 10px; }
        .statut-valide  { background: #dcfce7; color: #166534; }
        .statut-soumis  { background: #e0f2fe; color: #0369a1; }
        .statut-attente { background: #fef9c3; color: #854d0e; }

        @media print {
            .action-bar { display: none !important; }
            body { background: white; }
            .paper-sheet { margin: 0; box-shadow: none; width: 100%; padding: .8cm 1cm; }
        }
    </style>
</head>
<body>

<?php
// Calcul de l'âge
$age = 'N/A';
if (!empty($demande['date_naissance'])) {
    $age = (int)(new DateTime())->diff(new DateTime($demande['date_naissance']))->y;
}

// Grouper résultats par catégorie
$par_categorie = [];
foreach ($resultats as $r) {
    $par_categorie[$r['categorie'] ?? 'AUTRE'][] = $r;
}

$statutBadge = match(strtoupper($demande['statut'] ?? '')) {
    'VALIDES','VALIDE' => ['statut-valide', 'Validé'],
    'SOUMIS','RESULTATS_PRETS' => ['statut-soumis', 'Prêt'],
    default => ['statut-attente', $demande['statut'] ?? 'En attente'],
};
?>

<!-- BARRE D'ACTIONS -->
<div class="action-bar no-print">
    <div style="display:flex;align-items:center;gap:10px;">
        <a href="javascript:history.back()" class="btn btn-secondary">
            &#8592; Retour
        </a>
        <span style="font-size:13px;font-weight:700;color:#0891b2;">
            Résultats — <?= htmlspecialchars(($demande['nom'] ?? '') . ' ' . ($demande['prenom'] ?? '')) ?>
        </span>
        <span class="statut-badge <?= $statutBadge[0] ?>"><?= $statutBadge[1] ?></span>
    </div>
    <div style="display:flex;gap:8px;">
        <button class="btn btn-primary" onclick="window.print()">
            &#128438; Imprimer
        </button>
        <button class="btn btn-secondary" onclick="window.close()">
            &#10005; Fermer
        </button>
    </div>
</div>

<!-- FEUILLE A4 -->
<div class="paper-sheet">

    <!-- EN-TÊTE HÔPITAL -->
    <div class="hospital-header">
        <?php require_once __DIR__ . '/../partials/entete_hopital.php'; echo entete_hopital(['compact' => true]); ?>
        <div class="dept-checkboxes">
            <label><input type="checkbox" disabled> RADIOLOGIE</label>
            <label><input type="checkbox" disabled> ECHOGRAPHIE</label>
            <label><input type="checkbox" checked disabled> LABORATOIRE</label>
        </div>
    </div>

    <!-- TITRE -->
    <div class="form-title">
        <h2>BULLETIN D'EXAMENS</h2>
        <div class="subtitle">Résultats d'Analyses de Laboratoire</div>
    </div>

    <!-- INFO PATIENT -->
    <div class="patient-info-grid">
        <div>
            Nom et Prénom :
            <span class="form-dotted"><?= htmlspecialchars(($demande['nom'] ?? '') . ' ' . ($demande['prenom'] ?? '')) ?></span>
        </div>
        <div>
            Médecin prescripteur :
            <span class="form-dotted">Dr. <?= htmlspecialchars(trim(($demande['medecin_nom'] ?? '') . ' ' . ($demande['medecin_prenom'] ?? ''))) ?></span>
        </div>
        <div>
            Âge : <span class="form-dotted" style="min-width:40px;"><?= $age ?></span> ans
            &nbsp;&nbsp; Sexe :
            <span class="form-dotted" style="min-width:70px;"><?= ($demande['sexe'] ?? '') === 'M' ? 'Masculin' : 'Féminin' ?></span>
        </div>
        <div>
            Date prélèvement :
            <span class="form-dotted"><?= !empty($demande['date_prelevement']) ? date('d/m/Y', strtotime($demande['date_prelevement'])) : 'Non renseignée' ?></span>
        </div>
        <div>
            N° Dossier :
            <span class="form-dotted"><?= htmlspecialchars($demande['dossier_numero'] ?? '') ?></span>
        </div>
        <div>
            Date résultats :
            <span class="form-dotted"><?= $demande['date_resultats'] ? date('d/m/Y H:i', strtotime($demande['date_resultats'])) : date('d/m/Y') ?></span>
        </div>
    </div>

    <!-- GRILLE PRINCIPALE -->
    <div class="main-content-grid">

        <!-- COLONNE GAUCHE : Liste des examens demandés -->
        <div class="grid-col grid-col-left">
            <div class="col-header">Examens demandés</div>
            <?php
            $idx = 1;
            foreach ($par_categorie as $cat => $items):
            ?>
                <div style="font-size:10px;font-weight:700;color:#888;text-transform:uppercase;margin:6px 0 3px;letter-spacing:.4px;"><?= htmlspecialchars($cat) ?></div>
                <?php foreach ($items as $r): ?>
                <div class="exam-list-item">
                    <span class="exam-num"><?= $idx++ ?>.</span>
                    <span class="exam-name"><?= htmlspecialchars($r['nom_examen']) ?></span>
                    <?php if ($r['urgent']): ?><span class="badge-urgent">URGENT</span><?php endif; ?>
                </div>
                <?php endforeach; ?>
            <?php endforeach; ?>

            <?php if (empty($resultats)): ?>
                <div class="no-result">Aucun examen enregistré.</div>
            <?php endif; ?>

            <!-- Notes technicien -->
            <?php if (!empty($demande['notes_technicien'])): ?>
            <div style="margin-top:10px;padding:7px;background:#f0fdf4;border-left:3px solid #16a34a;border-radius:0 4px 4px 0;font-size:10px;">
                <div style="font-weight:700;color:#15803d;margin-bottom:3px;">Notes technicien</div>
                <?= nl2br(htmlspecialchars($demande['notes_technicien'])) ?>
            </div>
            <?php endif; ?>

            <!-- Signature médecin -->
            <div class="signature-row">
                <div>
                    Date : <span class="form-dotted" style="min-width:80px;"><?= date('d/m/Y') ?></span>
                </div>
                <div class="sig-block">
                    <div style="display:flex;align-items:flex-end;justify-content:center;gap:4px;height:55px;margin-bottom:2px;">
                        <?php if (!empty($signatureMedecinSrc)): ?>
                        <img src="<?= $signatureMedecinSrc ?>" alt="Signature"
                             style="max-height:50px;max-width:110px;object-fit:contain;object-position:bottom;">
                        <?php endif; ?>
                        <?php if (!empty($cachetMedecinSrc)): ?>
                        <img src="<?= $cachetMedecinSrc ?>" alt="Cachet"
                             style="max-height:50px;max-width:50px;object-fit:contain;object-position:bottom;">
                        <?php endif; ?>
                    </div>
                    <div class="sig-line" style="margin:0 auto 3px;"></div>
                    <div style="font-size:10px;color:#555;">Signature &amp; Cachet</div>
                    <div style="font-size:9px;color:#888;margin-top:2px;">
                        Dr. <?= htmlspecialchars(trim(($demande['medecin_nom'] ?? '') . ' ' . ($demande['medecin_prenom'] ?? ''))) ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- COLONNE DROITE : Résultats -->
        <div class="grid-col grid-col-right">
            <div class="col-header">Résultats / Compte-rendu</div>

            <?php if (!empty($par_categorie)): ?>
                <?php foreach ($par_categorie as $cat => $items): ?>
                <div class="cat-title"><?= htmlspecialchars($cat) ?></div>
                <table class="results-table">
                    <thead>
                        <tr>
                            <th>Examen</th>
                            <th>Résultat</th>
                            <th>Unité</th>
                            <th>Valeurs réf.</th>
                            <th>Interprétation</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $r):
                            $valAffichee = $r['valeur_numerique'] !== null && $r['valeur_numerique'] !== ''
                                ? $r['valeur_numerique']
                                : ($r['resultat'] ?? '');
                            $isCritical = $r['anormal'] && $r['valeur_numerique'] !== null
                                && $r['valeur_normale_min'] !== null
                                && ($r['valeur_numerique'] < $r['valeur_normale_min'] * 0.7
                                    || ($r['valeur_normale_max'] && $r['valeur_numerique'] > $r['valeur_normale_max'] * 1.5));
                            $rowClass = $isCritical ? 'critical' : ($r['anormal'] ? 'abnormal' : '');
                        ?>
                        <tr class="<?= $rowClass ?>">
                            <td class="td-name">
                                <?= htmlspecialchars($r['nom_examen']) ?>
                                <?php if ($r['anormal']): ?><span class="anormal-flag">&#9650;</span><?php endif; ?>
                            </td>
                            <td class="td-value">
                                <?php if ($valAffichee !== '' && $valAffichee !== null): ?>
                                    <?= htmlspecialchars((string)$valAffichee) ?>
                                <?php else: ?>
                                    <span class="no-result">—</span>
                                <?php endif; ?>
                            </td>
                            <td class="td-unit"><?= htmlspecialchars($r['unite'] ?? '') ?: '—' ?></td>
                            <td class="td-ref">
                                <?php if ($r['valeur_normale_min'] !== null && $r['valeur_normale_max'] !== null): ?>
                                    <?= $r['valeur_normale_min'] ?> – <?= $r['valeur_normale_max'] ?>
                                <?php else: ?>—<?php endif; ?>
                            </td>
                            <td><?= nl2br(htmlspecialchars($r['interpretation'] ?? '')) ?: '<span class="no-result">—</span>' ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="no-result" style="margin-top:20px;text-align:center;">Aucun résultat saisi pour cette demande.</div>
            <?php endif; ?>

            <?php if (!empty($piecesJointes)): ?>
            <div class="pj-section">
                <strong>Pièces jointes</strong>
                <ul class="pj-list">
                    <?php foreach ($piecesJointes as $pj): ?>
                    <li>
                        <a href="<?= BASE_URL ?>laboratoire/piece-jointe/<?= (int)$pj['id'] ?>" target="_blank" class="no-print">
                            <?= htmlspecialchars($pj['fichier_nom_original'] ?? $pj['fichier_chemin']) ?>
                        </a>
                        <span class="print-only"><?= htmlspecialchars($pj['fichier_nom_original'] ?? $pj['fichier_chemin']) ?></span>
                        <span style="color:#aaa;font-size:9px;">(<?= $pj['fichier_type'] ?? '' ?>)</span>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>

            <!-- Signature labo -->
            <div class="signature-row">
                <div>
                    Date résultats :<br>
                    <span class="form-dotted" style="min-width:80px;">
                        <?= $demande['date_resultats'] ? date('d/m/Y', strtotime($demande['date_resultats'])) : '..../../....' ?>
                    </span>
                </div>
                <div class="sig-block">
                    <div class="sig-line"></div>
                    <div style="font-size:10px;color:#555;">Signature &amp; Cachet</div>
                    <div style="font-size:9px;color:#888;margin-top:2px;">
                        <?= htmlspecialchars(trim(($demande['technicien_nom'] ?? '') . ' ' . ($demande['technicien_prenom'] ?? ''))) ?: 'Technicien' ?>
                    </div>
                </div>
            </div>
        </div>

    </div><!-- /main-content-grid -->

    <!-- PIED DE PAGE -->
    <div class="doc-footer">
        <div>
            <strong>Technicien :</strong> <?= htmlspecialchars(trim(($demande['technicien_nom'] ?? 'Non assigné') . ' ' . ($demande['technicien_prenom'] ?? ''))) ?><br>
            <strong>Biologiste :</strong> <?= htmlspecialchars($demande['biologiste_nom'] ?? 'Non validé') ?>
        </div>
        <div style="text-align:right;">
            <strong>N° Demande :</strong> <?= (int)$demande['id'] ?><br>
            <strong>Statut :</strong> <span class="statut-badge <?= $statutBadge[0] ?>"><?= $statutBadge[1] ?></span>
        </div>
    </div>

    <div class="confidential">
        Ce document est confidentiel et destiné exclusivement au patient et au médecin prescripteur.<br>
        SimCare+ — Système de Gestion de l'Hôpital Saint-Jean de Malte
    </div>

</div><!-- /paper-sheet -->

<script>
if (window.location.search.includes('auto_print=1')) {
    window.onload = () => setTimeout(() => window.print(), 400);
}
</script>
</body>
</html>
