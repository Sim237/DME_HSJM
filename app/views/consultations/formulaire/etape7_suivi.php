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
                $numero = 7;
                include __DIR__ . '/progress_bar.php';
            ?>

            <form action="<?= BASE_URL ?>consultation/sauvegarder" method="POST">

                <!-- === CHAMPS CACHÉS INDISPENSABLES === -->
                <input type="hidden" name="etape_actuelle" value="7"> <!-- C'est la dernière étape ! -->
                <input type="hidden" name="patient_id" value="<?php echo $patient['id']; ?>">
                <input type="hidden" name="type" value="<?php echo htmlspecialchars($type_consultation); ?>">
                <!-- ==================================== -->

                <div class="card shadow-sm mb-3">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0"><i class="fas fa-calendar-check me-2"></i> SUIVI ET FINALISATION</h5>
                    </div>
                    <div class="card-body">
                        <!-- Notes de suivi -->
                        <div class="mb-4">
                            <label for="notes_suivi" class="form-label fw-bold">
                                <i class="fas fa-notes-medical text-success"></i> Notes de Suivi
                            </label>
                            <textarea class="form-control"
                                      id="notes_suivi"
                                      name="notes_suivi"
                                      rows="4"
                                      placeholder="Informations importantes pour le suivi ultérieur..."><?php echo htmlspecialchars($consultation['notes_suivi'] ?? ''); ?></textarea>
                        </div>

                        <!-- Planifier un rendez-vous -->
                        <div class="mb-4 bg-light p-3 rounded border">
                            <h6 class="border-bottom pb-2 mb-3">
                                <i class="fas fa-calendar-alt text-primary"></i> Planifier un Rendez-vous de Suivi
                            </h6>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="date_prochain_rdv" class="form-label small fw-bold">Date du prochain RDV</label>
                                    <input type="date"
                                           class="form-control"
                                           id="date_prochain_rdv"
                                           name="date_suivi"
                                           value="<?php echo htmlspecialchars($consultation['date_suivi'] ?? ''); ?>"
                                           min="<?php echo date('Y-m-d'); ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">Motif (Optionnel)</label>
                                    <input type="text" class="form-control" placeholder="Ex: Contrôle cicatrisation">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Validation finale -->
                <div class="card shadow-sm mb-5 border-success">
                    <div class="card-header bg-dark text-white">
                        <h5 class="mb-0"><i class="fas fa-check-circle me-2"></i> TERMINER LA CONSULTATION</h5>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-success d-flex align-items-center">
                            <i class="fas fa-info-circle fs-3 me-3"></i>
                            <div>
                                <strong>Consultation prête à être finalisée.</strong><br>
                                En cliquant sur "Terminer", toutes les données seront enregistrées définitivement dans le dossier du patient.
                            </div>
                        </div>

                        <div class="d-flex justify-content-between align-items-center mt-4">
                            <a href="<?php echo BASE_URL; ?>consultation/formulaire?patient_id=<?php echo $patient['id']; ?>&type=<?php echo $type_consultation; ?>&etape=6" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-2"></i> Précédent
                            </a>

                            <button type="submit" class="btn btn-success btn-lg px-5 rounded-pill shadow" onclick="this.disabled=true; this.form.submit();">
    <i class="bi bi-check-circle-fill me-2"></i> Terminer et Enregistrer
</button>
                        </div>
                    </div>
                </div>
            </form>
        </main>
    </div>
</div>

<?php include __DIR__ . '/../../layouts/footer.php'; ?>