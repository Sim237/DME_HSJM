<?php
// Initialisation des variables
$patient = $patient ?? [];
$consultation = $consultation_data ?? [];

include __DIR__ . '/../../layouts/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include __DIR__ . '/../../layouts/sidebar.php'; ?>

        <main class="col-12 px-md-4 consultation-form" style="margin-left: 0 !important;">

            <!-- Barre de progression (Étape 2) -->
            <?php
                $numero = 2;
                $type_consultation = $_GET['type'] ?? $consultation['type'] ?? 'EXTERNE';
                include __DIR__ . '/progress_bar.php';
            ?>

            <!-- FORMULAIRE -->
            <form action="<?php echo BASE_URL; ?>consultation/sauvegarder" method="POST">

                <!-- === CHAMPS CACHÉS INDISPENSABLES (À COPIER PARTOUT) === -->
                <input type="hidden" name="etape_actuelle" value="2"> <!-- Notez que c'est l'étape 2 -->
                <input type="hidden" name="patient_id" value="<?php echo $patient['id']; ?>">
                <input type="hidden" name="type" value="<?php echo htmlspecialchars($type_consultation); ?>">
                <!-- ======================================================= -->

                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0"><i class="fas fa-stethoscope me-2"></i> EXAMEN PHYSIQUE</h5>
                    </div>
                    <div class="card-body">

                        <h6 class="text-success border-bottom pb-2 mb-3">Paramètres Vitaux (récupérés du tri)</h6>

<div class="row g-3 mb-4">
    <!-- Température -->
    <div class="col-md-3">
        <label class="form-label fw-bold">TEMPÉRATURE (°C)</label>
        <div class="input-group">
            <span class="input-group-text"><i class="bi bi-thermometer-half"></i></span>
            <input type="number" step="0.1" class="form-control" name="temperature"
                   value="<?= $consultation['temperature'] ?? $last_vitals['temperature'] ?? '' ?>">
        </div>
    </div>

    <!-- Tension Artérielle -->
    <div class="col-md-3">
        <label class="form-label fw-bold">PRESSION ARTÉRIELLE</label>
        <div class="input-group">
            <span class="input-group-text"><i class="bi bi-heart-pulse"></i></span>
            <?php
                // On pré-remplit au format SYS/DIA si les données existent
                $ta_value = "";
                if(isset($last_vitals['pression_arterielle_systolique'])) {
                    $ta_value = $last_vitals['pression_arterielle_systolique'] . '/' . $last_vitals['pression_arterielle_diastolique'];
                }
            ?>
            <input type="text" class="form-control" name="tension_arterielle"
                   value="<?= $consultation['tension_arterielle'] ?? $ta_value ?>">
        </div>
        <small class="text-muted">mmHg (Ex: 120/80)</small>
    </div>

    <!-- Fréquence Cardiaque -->
    <div class="col-md-3">
        <label class="form-label fw-bold">FRÉQUENCE CARDIAQUE</label>
        <div class="input-group">
            <span class="input-group-text"><i class="bi bi-activity"></i></span>
            <input type="number" class="form-control" name="frequence_cardiaque"
                   value="<?= $consultation['frequence_cardiaque'] ?? $last_vitals['frequence_cardiaque'] ?? '' ?>">
        </div>
        <small class="text-muted">Bpm</small>
    </div>

    <!-- Poids -->
    <div class="col-md-3">
        <label class="form-label fw-bold">POIDS (KG)</label>
        <div class="input-group">
            <span class="input-group-text"><i class="bi bi-speedometer2"></i></span>
            <input type="number" step="0.1" class="form-control" name="poids"
                   value="<?= $consultation['poids'] ?? $last_vitals['poids'] ?? '' ?>">
        </div>
    </div>
</div>

                        <h6 class="text-success border-bottom pb-2 mb-3">Examen Clinique Détaillé</h6>

                        <div class="mb-4">
                            <label class="form-label fw-bold">Résultats de l'Examen Physique <span class="text-danger">*</span></label>
                            <textarea class="form-control" name="examen_physique" rows="6" required
                                      placeholder="Décrivez l'examen tête aux pieds : État général, ORL, Cardio-pulmonaire, Abdominal, etc."><?php echo htmlspecialchars($consultation['examen_physique'] ?? ''); ?></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Résumé Syndromique</label>
                            <textarea class="form-control" name="resume_syndromique" rows="3"
                                      placeholder="Regroupement des symptômes en syndromes (ex: Syndrome grippal, Syndrome méningé...)"><?php echo htmlspecialchars($consultation['resume_syndromique'] ?? ''); ?></textarea>
                        </div>

                    </div>
                </div>

                <!-- Boutons de navigation -->
                <div class="card shadow-sm mb-5">
                    <div class="card-body d-flex justify-content-between">
                        <a href="<?php echo BASE_URL; ?>consultation" class="btn btn-outline-secondary">
                            <i class="fas fa-times me-2"></i> Annuler
                        </a>
                        <button type="submit" class="btn btn-primary px-4">
                            Suivant <i class="fas fa-arrow-right ms-2"></i>
                        </button>
                    </div>
                </div>
            </form>

        </main>
    </div>
</div>

<?php include __DIR__ . '/../../layouts/footer.php'; ?>