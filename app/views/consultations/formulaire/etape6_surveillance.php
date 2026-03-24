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
                $numero = 6;
                include __DIR__ . '/progress_bar.php';
            ?>

            <form action="<?php echo BASE_URL; ?>consultation/sauvegarder" method="POST">

                <!-- === CHAMPS CACHÉS INDISPENSABLES === -->
                <input type="hidden" name="etape_actuelle" value="6">
                <input type="hidden" name="patient_id" value="<?php echo $patient['id']; ?>">
                <input type="hidden" name="type" value="<?php echo htmlspecialchars($type_consultation); ?>">
                <!-- ==================================== -->

                <div class="card shadow-sm mb-3">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0"><i class="fas fa-chart-line me-2"></i> SURVEILLANCE</h5>
                    </div>
                    <div class="card-body">
                        <!-- Plan de surveillance -->
                        <div class="mb-4">
                            <label for="surveillance" class="form-label fw-bold">
                                <i class="fas fa-eye text-info"></i> Plan de Surveillance <span class="text-danger">*</span>
                            </label>
                            <textarea class="form-control"
                                      id="surveillance"
                                      name="surveillance"
                                      rows="6"
                                      required
                                      placeholder="Décrivez les éléments à surveiller :
- Signes cliniques à surveiller
- Paramètres à contrôler (température, TA...)
- Signes d'alerte nécessitant une réévaluation urgente"><?php echo htmlspecialchars($consultation['surveillance'] ?? ''); ?></textarea>
                        </div>

                        <!-- Éléments spécifiques pour hospitalisés -->
                        <?php if(strtoupper($type_consultation) == 'INTERNE'): ?>
                        <div class="alert alert-warning border-start border-warning border-4">
                            <h6 class="text-warning"><i class="fas fa-hospital me-2"></i> Patient Hospitalisé</h6>
                            <p class="mb-1 small">Pensez à inclure :</p>
                            <ul class="mb-0 small">
                                <li>Fréquence des prises de constantes</li>
                                <li>Bilan hydrique / Diurèse</li>
                                <li>Surveillance des voies veineuses</li>
                            </ul>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Boutons de navigation -->
                <div class="card shadow-sm mb-5">
                    <div class="card-body d-flex justify-content-between">
                        <a href="<?php echo BASE_URL; ?>consultation/formulaire?patient_id=<?php echo $patient['id']; ?>&type=<?php echo $type_consultation; ?>&etape=5" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-2"></i> Précédent
                        </a>
                        <button type="submit" class="btn btn-info text-white px-4">
                            Suivant <i class="fas fa-arrow-right ms-2"></i>
                        </button>
                    </div>
                </div>
            </form>
        </main>
    </div>
</div>

<?php include __DIR__ . '/../../layouts/footer.php'; ?>