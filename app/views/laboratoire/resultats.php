<?php
require_once __DIR__ . '/../layouts/header.php';
$examen = $examen ?? [];
$details = $examen['details'] ?? [];
// On simule le sexe pour la démo, à récupérer via $examen['sexe']
$sexe_patient = $examen['sexe'] ?? 'M'; 
?>

<div class="container-fluid">
    <div class="row">
        <?php require_once __DIR__ . '/../layouts/sidebar.php'; ?>
        
        <main class="col-md-10 ms-sm-auto px-md-4 py-4">
            
            <!-- EN-TÊTE FLUIDE -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="h3 fw-bold text-dark mb-0"><i class="bi bi-microscope text-primary"></i> Analyse Biologique</h2>
                    <span class="badge bg-secondary"><?= htmlspecialchars($examen['type_examen']) ?></span>
                    <?php if($examen['urgence']): ?><span class="badge bg-danger animate__animated animate__flash">URGENT</span><?php endif; ?>
                </div>
                <div>
                    <a href="<?= BASE_URL ?>laboratoire" class="btn btn-outline-secondary">Retour</a>
                </div>
            </div>

            <div class="row g-4">
                <!-- COLONNE GAUCHE : Workflow & Patient -->
                <div class="col-md-3">
                    <!-- Carte Patient -->
                    <div class="card shadow-sm mb-4 border-0">
                        <div class="card-body text-center">
                            <div class="display-6 mb-2"><i class="bi bi-person-circle text-muted"></i></div>
                            <h5 class="fw-bold"><?= htmlspecialchars($examen['patient_nom'] . ' ' . $examen['patient_prenom']) ?></h5>
                            <p class="text-muted small mb-0"><?= $examen['sexe'] ?> | <?= date('d/m/Y', strtotime($examen['date_naissance'])) ?></p>
                        </div>
                    </div>

                    <!-- Workflow Statut -->
                    <div class="card shadow-sm border-0">
                        <div class="card-header bg-white fw-bold">État d'avancement</div>
                        <div class="list-group list-group-flush small">
                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                <span>1. Demande créée</span>
                                <i class="bi bi-check-circle-fill text-success"></i>
                            </div>
                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                <span>2. Prélèvement</span>
                                <?php if($examen['etat_prelevement'] == 'FAIT'): ?>
                                    <span class="badge bg-success rounded-pill"><i class="bi bi-check"></i> Fait</span>
                                <?php else: ?>
                                    <button class="btn btn-sm btn-warning py-0" onclick="validerPrelevement(<?= $examen['id'] ?>)">Valider</button>
                                <?php endif; ?>
                            </div>
                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                <span>3. Analyse technique</span>
                                <i class="bi bi-hourglass-split text-primary"></i>
                            </div>
                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                <span>4. Validation Bio.</span>
                                <i class="bi bi-circle text-muted"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- COLONNE DROITE : Saisie Technique -->
                <div class="col-md-9">
                    <form action="<?= BASE_URL ?>laboratoire/sauvegarder" method="POST" id="formLabo">
                        <input type="hidden" name="examen_id" value="<?= $examen['id'] ?>">
                        
                        <div class="card shadow-sm border-0">
                            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">Résultats</h5>
                                <small>Tube requis: <span class="fw-bold text-warning">VIOLET (EDTA)</span> (Exemple)</small>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th style="width: 35%;">Paramètre</th>
                                                <th style="width: 25%;">Résultat</th>
                                                <th style="width: 15%;">Unité</th>
                                                <th style="width: 25%;">Valeurs de référence</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($details as $detail): ?>
                                                <?php 
                                                    // Extraction des normes pour JS (Format attendu: "Min - Max")
                                                    $normes = $detail['valeur_normale'];
                                                    $min = 0; $max = 0;
                                                    if(preg_match('/([0-9\.]+)\s*-\s*([0-9\.]+)/', $normes, $matches)) {
                                                        $min = $matches[1];
                                                        $max = $matches[2];
                                                    }
                                                ?>
                                            <tr>
                                                <td class="fw-bold text-secondary">
                                                    <?= htmlspecialchars($detail['nom_examen']) ?>
                                                    <input type="hidden" name="details[<?= $detail['id'] ?>][id]" value="<?= $detail['id'] ?>">
                                                </td>
                                                <td>
                                                    <div class="input-group">
                                                        <input type="number" step="0.01" 
                                                               class="form-control fw-bold result-input" 
                                                               name="details[<?= $detail['id'] ?>][resultat]" 
                                                               value="<?= htmlspecialchars($detail['resultat'] ?? '') ?>"
                                                               data-min="<?= $min ?>" 
                                                               data-max="<?= $max ?>"
                                                               oninput="checkNorme(this)">
                                                        <!-- Indicateur visuel H/L -->
                                                        <span class="input-group-text flag-indicator fw-bold"></span>
                                                    </div>
                                                </td>
                                                <td class="text-muted"><?= htmlspecialchars($detail['unite']) ?></td>
                                                <td class="small text-muted"><?= htmlspecialchars($detail['valeur_normale']) ?></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            
                            <div class="card-footer bg-light p-4">
                                <div class="mb-3">
                                    <label class="fw-bold mb-2">Conclusion / Interprétation</label>
                                    <textarea class="form-control" name="observations_labo" rows="2" placeholder="Commentaire optionnel..."><?= htmlspecialchars($examen['observations_labo'] ?? '') ?></textarea>
                                </div>
                                
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="validation_biologiste" value="1" id="validBio">
                                        <label class="form-check-label fw-bold text-success" for="validBio">
                                            Valider techniquement et biologiquement
                                        </label>
                                    </div>
                                    <button type="submit" class="btn btn-primary px-5">
                                        <i class="bi bi-save"></i> Enregistrer
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
// Fonction qui colore en rouge si hors normes
function checkNorme(input) {
    const val = parseFloat(input.value);
    const min = parseFloat(input.dataset.min);
    const max = parseFloat(input.dataset.max);
    const indicator = input.nextElementSibling; // Le span flag-indicator

    input.classList.remove('text-danger', 'is-invalid');
    indicator.textContent = '';
    indicator.className = 'input-group-text flag-indicator'; // Reset classes

    if (!isNaN(val) && (min > 0 || max > 0)) {
        if (val < min) {
            input.classList.add('text-danger', 'fw-bold');
            indicator.textContent = 'L'; // Low
            indicator.classList.add('bg-warning', 'text-dark');
        } else if (val > max) {
            input.classList.add('text-danger', 'fw-bold');
            indicator.textContent = 'H'; // High
            indicator.classList.add('bg-danger', 'text-white');
        } else {
            indicator.textContent = 'OK';
            indicator.classList.add('bg-success', 'text-white');
        }
    }
}

// Initialiser les couleurs au chargement
document.querySelectorAll('.result-input').forEach(input => checkNorme(input));

function validerPrelevement(id) {
    if(confirm("Confirmer que le prélèvement a été effectué ?")) {
        fetch('<?= BASE_URL ?>laboratoire/valider-prelevement', { // Route à créer
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'examen_id=' + id
        }).then(() => location.reload());
    }
}
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>