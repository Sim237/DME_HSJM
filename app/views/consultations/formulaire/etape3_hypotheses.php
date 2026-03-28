<?php
// Initialisation des variables
$patient = $patient ?? [];
$consultation = $consultation_data ?? [];
$type_consultation = $_GET['type'] ?? $consultation['type'] ?? 'EXTERNE';

include __DIR__ . '/../../layouts/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include __DIR__ . '/../../layouts/sidebar.php'; ?>

       <main class="col-12 px-md-4 consultation-form" style="margin-left: 0 !important;">

            <?php
                $numero = 3;
                include __DIR__ . '/progress_bar.php';
            ?>

            <form action="<?php echo BASE_URL; ?>consultation/sauvegarder" method="POST">

                <!-- === CHAMPS CACHÉS INDISPENSABLES === -->
                <input type="hidden" name="etape_actuelle" value="3">
                <input type="hidden" name="patient_id" value="<?php echo $patient['id']; ?>">
                <input type="hidden" name="type" value="<?php echo htmlspecialchars($type_consultation); ?>">
                <!-- ==================================== -->

                <div class="card shadow-sm mb-3">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="mb-0"><i class="fas fa-diagnoses me-2"></i> HYPOTHÈSES DIAGNOSTIQUES</h5>
                    </div>
                    <div class="card-body">
                        <!-- Hypothèses diagnostiques -->
                        <div class="mb-4">
                            <label for="hypotheses_diagnostiques" class="form-label fw-bold">
                                <i class="fas fa-lightbulb text-warning"></i> Hypothèses Diagnostiques <span class="text-danger">*</span>
                            </label>
                            <textarea class="form-control"
                                      id="hypotheses_diagnostiques"
                                      name="hypotheses_diagnostiques"
                                      rows="5"
                                      required
                                      placeholder="Listez les différentes hypothèses diagnostiques envisagées..."><?php echo htmlspecialchars($consultation['hypotheses_diagnostiques'] ?? ''); ?></textarea>
                            <small class="form-text text-muted">
                                Basé sur l'anamnèse et l'examen physique
                            </small>
                        </div>

                        <!-- Remplacer le bloc "Diagnostic Principal" par ceci -->
<div class="mb-4">
    <label class="form-label fw-bold">
        <i class="fas fa-check-circle text-success"></i> Diagnostic Principal (CIM-10) *  ou de Travail
    </label>
    <div class="position-relative">
        <input type="text" class="form-control" id="searchCim10"
               placeholder="Tapez un code (ex: B50) ou un nom (ex: Palu...)" autocomplete="off">
        <input type="hidden" name="diagnostic_principal" id="diagnostic_principal"
               value="<?php echo htmlspecialchars($consultation['diagnostic_principal'] ?? ''); ?>">

        <!-- Liste des résultats -->
        <div id="cim10Results" class="list-group position-absolute w-100 shadow" style="z-index: 1000; display:none;"></div>
    </div>
    <!-- Affichage de la sélection actuelle -->
    <div id="selectedDiag" class="form-text text-success fw-bold mt-1">
        <?php echo htmlspecialchars($consultation['diagnostic_principal'] ?? ''); ?>
    </div>
</div>
                        </div>

                        <!-- Diagnostics différentiels -->
                        <div class="mb-4">
                            <label for="diagnostics_differentiels" class="form-label fw-bold">
                                <i class="fas fa-tasks text-secondary"></i> Diagnostics Différentiels
                            </label>
                            <textarea class="form-control"
                                      id="diagnostics_differentiels"
                                      name="diagnostics_differentiels"
                                      rows="4"
                                      placeholder="Listez les diagnostics à éliminer..."><?php echo htmlspecialchars($consultation['diagnostics_differentiels'] ?? ''); ?></textarea>
                        </div>

                        <!-- Aide au diagnostic -->
                        <div class="alert alert-info border-0 bg-light text-dark">
                            <h6 class="alert-heading"><i class="fas fa-info-circle text-info"></i> Aide au diagnostic</h6>
                            <p class="mb-0 small">
                                Basez votre diagnostic sur :
                            </p>
                            <ul class="mb-0 small">
                                <li>Les données de l'anamnèse</li>
                                <li>Les résultats de l'examen physique</li>
                                <li>Le résumé syndromique</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Boutons de navigation -->
                <div class="card shadow-sm mb-5">
                    <div class="card-body d-flex justify-content-between">
                        <a href="<?php echo BASE_URL; ?>consultation/formulaire?patient_id=<?php echo $patient['id']; ?>&type=<?php echo $type_consultation; ?>&etape=2" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-2"></i> Précédent
                        </a>
                        <button type="submit" class="btn btn-warning text-dark fw-bold px-4">
                            Suivant <i class="fas fa-arrow-right ms-2"></i>
                        </button>
                    </div>
                </div>
            </form>
        </main>
    </div>
</div>

<script>
document.getElementById('searchCim10').addEventListener('input', function(e) {
    const query = e.target.value;
    const resultsDiv = document.getElementById('cim10Results');

    if (query.length < 2) { resultsDiv.style.display = 'none'; return; }

    fetch('<?= BASE_URL ?>consultation/search-cim10?q=' + encodeURIComponent(query))
        .then(res => res.json())
        .then(data => {
            resultsDiv.innerHTML = '';
            if (data.length > 0) {
                resultsDiv.style.display = 'block';
                data.forEach(item => {
                    const a = document.createElement('a');
                    a.className = 'list-group-item list-group-item-action';
                    a.innerHTML = `<strong>${item.code}</strong> - ${item.description}`;
                    a.href = '#';
                    a.onclick = (e) => {
                        e.preventDefault();
                        selectDiag(item.code + ' - ' + item.description);
                    };
                    resultsDiv.appendChild(a);
                });
            } else {
                resultsDiv.style.display = 'none';
            }
        });
});

function selectDiag(value) {
    document.getElementById('diagnostic_principal').value = value;
    document.getElementById('selectedDiag').innerText = value;
    document.getElementById('searchCim10').value = '';
    document.getElementById('cim10Results').style.display = 'none';
}
</script>
<?php include __DIR__ . '/../../layouts/footer.php'; ?>