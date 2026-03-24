<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/dme_hospital/app/views/layouts/header.php';
$demande = $demande ?? [];
$examens = $examens ?? [];

// Vérifier que les données sont valides
if (empty($demande)) {
    echo '<div class="alert alert-danger">Demande introuvable</div>';
    exit;
}
?>

<style>
.result-card {
    border: none;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.08);
    margin-bottom: 20px;
}
.result-input {
    font-size: 1.1rem;
    font-weight: 600;
    text-align: center;
}
.normal-range {
    background: #e8f5e8;
    border: 1px solid #28a745;
}
.abnormal-range {
    background: #ffeaa7;
    border: 1px solid #fdcb6e;
}
.critical-range {
    background: #fab1a0;
    border: 1px solid #e17055;
}
</style>

<div class="container-fluid">
    <div class="row">
        <?php require_once $_SERVER['DOCUMENT_ROOT'] . '/dme_hospital/app/views/layouts/sidebar.php'; ?>
        
        <main class="col-md-10 ms-sm-auto px-md-4">
            <div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2"><i class="bi bi-pencil-square text-success"></i> Saisie des Résultats</h1>
                <div class="btn-group">
                    <a href="<?= BASE_URL ?>laboratoire" class="btn btn-secondary">
                        <i class="bi bi-arrow-left"></i> Retour
                    </a>
                    <button class="btn btn-info" onclick="previsualiser()">
                        <i class="bi bi-eye"></i> Prévisualiser
                    </button>
                </div>
            </div>

            <!-- Info Patient -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <div class="row">
                        <div class="col-md-8">
                            <h5 class="mb-0">
                                <i class="bi bi-person-circle"></i>
                                <?= htmlspecialchars($demande['nom'] . ' ' . $demande['prenom']) ?>
                            </h5>
                            <small>Dossier: <?= htmlspecialchars($demande['dossier_numero']) ?> | 
                                   Âge: <?= date_diff(date_create($demande['date_naissance']), date_create('now'))->y ?> ans |
                                   Sexe: <?= $demande['sexe'] ?>
                            </small>
                        </div>
                        <div class="col-md-4 text-end">
                            <div>Médecin: Dr. <?= htmlspecialchars($demande['medecin_nom'] . ' ' . $demande['medecin_prenom']) ?></div>
                            <small>Demande #<?= $demande['id'] ?> - <?= date('d/m/Y H:i', strtotime($demande['date_creation'])) ?></small>
                        </div>
                    </div>
                </div>
            </div>

            <form action="<?= BASE_URL ?>laboratoire/sauvegarder-resultats" method="POST" id="formResultats" onsubmit="console.log('Submitting form with data:', new FormData(this)); return true;">
                <input type="hidden" name="demande_id" value="<?= $demande['id'] ?>">
                
                <!-- Examens par catégorie -->
                <?php 
                $categories = [];
                foreach ($examens as $examen) {
                    $categories[$examen['categorie']][] = $examen;
                }
                ?>
                
                <?php foreach ($categories as $categorie => $examensCategorie): ?>
                <div class="result-card">
                    <div class="card-header bg-light">
                        <h6 class="mb-0 text-primary">
                            <i class="bi bi-folder"></i> <?= $categorie ?>
                            <span class="badge bg-secondary ms-2"><?= count($examensCategorie) ?> examen(s)</span>
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="row g-4">
                            <?php foreach ($examensCategorie as $examen): ?>
                            <div class="col-md-6">
                                <div class="border rounded p-3 h-100">
                                    <div class="d-flex justify-content-between align-items-start mb-3">
                                        <div>
                                            <h6 class="fw-bold text-dark"><?= htmlspecialchars($examen['nom']) ?></h6>
                                            <small class="text-muted">
                                                <?= $examen['type_prelevement'] ?> | 
                                                Délai: <?= $examen['delai_rendu_heures'] ?>h
                                                <?= $examen['a_jeun_requis'] ? ' | À jeun requis' : '' ?>
                                            </small>
                                        </div>
                                        <?php if ($examen['urgent']): ?>
                                            <span class="badge bg-danger">URGENT</span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Résultat</label>
                                        <div class="input-group">
                                            <input type="text" 
                                                   class="form-control result-input" 
                                                   name="resultats[<?= $examen['id'] ?>][resultat]"
                                                   id="resultat_<?= $examen['id'] ?>"
                                                   data-min="<?= $examen['valeur_normale_min'] ?? '' ?>"
                                                   data-max="<?= $examen['valeur_normale_max'] ?? '' ?>"
                                                   data-unite="<?= $examen['unite'] ?? '' ?>"
                                                   onchange="verifierNorme(this)"
                                                   placeholder="Saisir le résultat">
                                            <?php if ($examen['unite']): ?>
                                                <span class="input-group-text"><?= htmlspecialchars($examen['unite']) ?></span>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <?php if ($examen['valeur_normale_min'] && $examen['valeur_normale_max']): ?>
                                        <small class="text-muted">
                                            Valeurs normales: <?= $examen['valeur_normale_min'] ?> - <?= $examen['valeur_normale_max'] ?> <?= $examen['unite'] ?>
                                        </small>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Valeur numérique (si applicable)</label>
                                        <input type="number" 
                                               step="0.001" 
                                               class="form-control" 
                                               name="resultats[<?= $examen['id'] ?>][valeur_numerique]"
                                               id="valeur_<?= $examen['id'] ?>"
                                               onchange="synchroniserValeur(<?= $examen['id'] ?>)">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Interprétation</label>
                                        <textarea class="form-control" 
                                                  name="resultats[<?= $examen['id'] ?>][interpretation]"
                                                  rows="2" 
                                                  placeholder="Commentaire technique..."></textarea>
                                    </div>
                                    
                                    <div class="form-check">
                                        <input class="form-check-input" 
                                               type="checkbox" 
                                               name="resultats[<?= $examen['id'] ?>][controle_qualite]"
                                               id="qc_<?= $examen['id'] ?>">
                                        <label class="form-check-label text-success" for="qc_<?= $examen['id'] ?>">
                                            <i class="bi bi-shield-check"></i> Contrôle qualité validé
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
                
                <!-- Notes générales -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="mb-0">Notes du technicien</h6>
                    </div>
                    <div class="card-body">
                        <textarea class="form-control" 
                                  name="notes_technicien" 
                                  rows="3" 
                                  placeholder="Observations générales, difficultés rencontrées, recommandations..."></textarea>
                    </div>
                </div>
                
                <!-- Actions -->
                <div class="card">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-8">
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" id="validationTechnique" required>
                                    <label class="form-check-label fw-bold" for="validationTechnique">
                                        Je certifie avoir effectué tous les contrôles techniques requis
                                    </label>
                                </div>
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" name="demander_validation_biologiste" id="validationBio">
                                    <label class="form-check-label" for="validationBio">
                                        Demander validation du biologiste (résultats critiques)
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-4 text-end">
                                <button type="button" class="btn btn-outline-secondary me-2" onclick="sauvegarderBrouillon()">
                                    <i class="bi bi-save"></i> Sauvegarder brouillon
                                </button>
                                <button type="submit" class="btn btn-success btn-lg">
                                    <i class="bi bi-check-circle"></i> Valider les résultats
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </main>
    </div>
</div>

<script>
function verifierNorme(input) {
    const valeur = parseFloat(input.value);
    const min = parseFloat(input.dataset.min);
    const max = parseFloat(input.dataset.max);
    
    input.classList.remove('normal-range', 'abnormal-range', 'critical-range');
    
    if (!isNaN(valeur) && !isNaN(min) && !isNaN(max)) {
        if (valeur >= min && valeur <= max) {
            input.classList.add('normal-range');
        } else if (valeur < min * 0.5 || valeur > max * 2) {
            input.classList.add('critical-range');
            if (confirm('Valeur critique détectée. Voulez-vous demander une validation du biologiste ?')) {
                document.getElementById('validationBio').checked = true;
            }
        } else {
            input.classList.add('abnormal-range');
        }
        
        // Synchroniser avec le champ valeur numérique
        const examenId = input.name.match(/\[(\d+)\]/)[1];
        document.getElementById('valeur_' + examenId).value = valeur;
    }
}

function synchroniserValeur(examenId) {
    const valeurNum = document.getElementById('valeur_' + examenId).value;
    const resultatText = document.getElementById('resultat_' + examenId);
    
    if (valeurNum && !resultatText.value) {
        resultatText.value = valeurNum;
        verifierNorme(resultatText);
    }
}

function sauvegarderBrouillon() {
    const formData = new FormData(document.getElementById('formResultats'));
    formData.append('brouillon', '1');
    
    fetch('<?= BASE_URL ?>laboratoire/sauvegarder-resultats', {
        method: 'POST',
        body: formData
    }).then(response => {
        if (response.ok) {
            alert('Brouillon sauvegardé');
        }
    });
}

function previsualiser() {
    const resultats = {};
    let hasResults = false;
    
    document.querySelectorAll('.result-input').forEach(input => {
        if (input.value.trim()) {
            const examenId = input.name.match(/\[(\d+)\]/)[1];
            resultats[examenId] = input.value;
            hasResults = true;
        }
    });
    
    if (!hasResults) {
        setTimeout(() => {
            alert('Aucun résultat saisi pour la prévisualisation');
        }, 100);
        return;
    }
    
    // Créer une fenêtre de prévisualisation
    let previewContent = '<h3>Prévisualisation des résultats</h3><table border="1" style="width:100%; border-collapse:collapse;">';
    previewContent += '<tr><th>Examen</th><th>Résultat</th></tr>';
    
    document.querySelectorAll('.result-input').forEach(input => {
        if (input.value.trim()) {
            const examenName = input.closest('.border').querySelector('h6').textContent;
            previewContent += `<tr><td>${examenName}</td><td>${input.value}</td></tr>`;
        }
    });
    
    previewContent += '</table>';
    
    const previewWindow = window.open('', 'preview', 'width=600,height=400,scrollbars=yes');
    previewWindow.document.write(`
        <html>
            <head><title>Prévisualisation</title></head>
            <body style="font-family: Arial, sans-serif; padding: 20px;">
                ${previewContent}
                <br><button onclick="window.close()">Fermer</button>
            </body>
        </html>
    `);
    previewWindow.document.close();
}

// Validation avant soumission
document.getElementById('formResultats').addEventListener('submit', function(e) {
    console.log('Form submitted');
    
    const resultatsRemplis = document.querySelectorAll('.result-input').length;
    const resultatsAvecValeur = Array.from(document.querySelectorAll('.result-input')).filter(input => input.value.trim() !== '').length;
    
    console.log('Résultats remplis:', resultatsAvecValeur, 'sur', resultatsRemplis);
    
    if (resultatsAvecValeur === 0) {
        e.preventDefault();
        alert('Veuillez saisir au moins un résultat');
        return;
    }
    
    if (!document.getElementById('validationTechnique').checked) {
        e.preventDefault();
        alert('Veuillez certifier avoir effectué les contrôles techniques');
        return;
    }
    
    console.log('Form validation passed');
});

// Auto-sauvegarde toutes les 2 minutes
setInterval(sauvegarderBrouillon, 120000);
</script>

<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/dme_hospital/app/views/layouts/footer.php'; ?>