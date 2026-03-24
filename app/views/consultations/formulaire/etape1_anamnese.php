<?php
// Initialisation des variables
$patient = $patient ?? [];
$consultation = $consultation_data ?? [];

// Inclusion du header
include __DIR__ . '/../../layouts/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include __DIR__ . '/../../layouts/sidebar.php'; ?>

        <main class="col-12 px-md-4 consultation-form" style="margin-left: 0 !important;">

            <!-- Inclusion de la barre de progression (étape 1) -->
            <?php
                $numero = 1;
                $type_consultation = $_GET['type'] ?? 'EXTERNE';
                include __DIR__ . '/progress_bar.php';
            ?>

            <form action="<?php echo BASE_URL; ?>consultation/sauvegarder" method="POST" id="formAnamnese">

                <input type="hidden" name="etape_actuelle" value="1">
                <input type="hidden" name="patient_id" value="<?php echo $patient['id']; ?>">
                <input type="hidden" name="type" value="<?php echo htmlspecialchars($type_consultation); ?>">

                <div class="card shadow-sm mb-4">
                    <div class="card-header">
                        <h5><i class="fas fa-clipboard-list"></i> ANAMNÈSE</h5>
                    </div>
                    <div class="card-body">

                <div class="form-section">
                    <div class="form-section-header">
                        <div class="form-section-icon">
                            <i class="fas fa-comment-medical"></i>
                        </div>
                        <div>
                            <h6 class="form-section-title">Motif de Consultation</h6>
                            <p class="form-section-subtitle">Raison principale de la visite</p>
                        </div>
                    </div>
                    <div class="form-group-modern">
                        <label for="motif_consultation" class="required">
                            Motif Principal
                        </label>
                        <textarea class="form-control-modern textarea-modern"
                                  id="motif_consultation"
                                  name="motif_consultation"
                                  required
                                  placeholder="Décrivez le motif principal de la consultation..."><?php echo htmlspecialchars($consultation['motif_consultation'] ?? ''); ?></textarea>
                    </div>
                </div>

                <div class="form-section">
                    <div class="form-section-header">
                        <div class="form-section-icon">
                            <i class="fas fa-pills"></i>
                        </div>
                        <div>
                            <h6 class="form-section-title">Automédication</h6>
                            <p class="form-section-subtitle">Médicaments pris avant consultation</p>
                        </div>
                    </div>
                    <div class="form-group-modern">
                        <label for="automedication">
                            Médicaments Pris
                        </label>
                        <textarea class="form-control-modern textarea-modern"
                                  id="automedication"
                                  name="automedication"
                                  placeholder="Listez les médicaments pris avant la consultation..."><?php echo htmlspecialchars($consultation['automedication'] ?? ''); ?></textarea>
                    </div>
                </div>

                <div class="form-section">
                    <div class="form-section-header">
                        <div class="form-section-icon">
                            <i class="fas fa-file-medical-alt"></i>
                        </div>
                        <div>
                            <h6 class="form-section-title">Histoire de la Maladie</h6>
                            <p class="form-section-subtitle">Évolution et chronologie des symptômes</p>
                        </div>
                    </div>
                    <div class="form-group-modern">
                        <label for="histoire_maladie" class="required">
                            Évolution des Symptômes
                        </label>
                        <textarea class="form-control-modern textarea-modern"
                                  id="histoire_maladie"
                                  name="histoire_maladie"
                                  required
                                  style="min-height: 120px;"
                                  placeholder="Décrivez l'évolution des symptômes, la chronologie..."><?php echo htmlspecialchars($consultation['histoire_maladie'] ?? ''); ?></textarea>
                    </div>
                </div>

                <div class="form-section">
                    <div class="form-section-header">
                        <div class="form-section-icon">
                            <i class="fas fa-notes-medical"></i>
                        </div>
                        <div>
                            <h6 class="form-section-title">Complément d'Anamnèse</h6>
                            <p class="form-section-subtitle">Informations additionnelles</p>
                        </div>
                    </div>
                    <div class="form-group-modern">
                        <label for="complement_anamnese">
                            Informations Complémentaires
                        </label>
                        <textarea class="form-control-modern textarea-modern"
                                  id="complement_anamnese"
                                  name="complement_anamnese"
                                  placeholder="Ajoutez toute information pertinente..."><?php echo htmlspecialchars($consultation['complement_anamnese'] ?? ''); ?></textarea>
                    </div>
                </div>
                    </div>
                </div>

                <div class="form-actions">
                    <a href="<?php echo BASE_URL; ?>consultation" class="btn-secondary-modern">
                        <i class="fas fa-times"></i> Annuler
                    </a>
                    <button type="submit" class="btn-primary-modern">
                        Suivant <i class="fas fa-arrow-right"></i>
                    </button>
                </div>
            </form>

        </main>
    </div>
</div>

<?php include __DIR__ . '/../../layouts/footer.php'; ?>