<?php
/**
 * SimCare+ — Dossier Médical Électronique (DME)
 * Copyright (c) 2024-2026 Franck Simeni. Tous droits réservés.
 * Développé pour la gestion hospitalière, et le bien être numérique des patients.
 *
 * Toute reproduction, modification ou distribution de ce logiciel,
 * en tout ou en partie, sans autorisation écrite préalable de l'auteur
 * est strictement interdite et constitue une contrefaçon.
 *
 * Protected under OAPI Agreement — Annexe VII · Berne Convention
 */

// Initialisation des variables
$patient = $patient ?? [];
$consultation = $consultation_data ?? [];
$type_consultation = $_GET['type'] ?? $consultation['type'] ?? 'EXTERNE';

include __DIR__ . '/../../layouts/header.php';
?>

<div class="container-fluid">
    <div class="row">


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
                        <!-- Champ caché pour maintenir la compatibilité BDD -->
                        <input type="hidden" name="hypotheses_diagnostiques" value="<?php echo htmlspecialchars($consultation['hypotheses_diagnostiques'] ?? ''); ?>">

                        <!-- Diagnostic Principal — champ de saisie libre -->
                        <div class="mb-4">
                            <label for="diagnostic_principal" class="form-label fw-bold">
                                <i class="fas fa-check-circle text-success"></i> Diagnostic Principal (CIM-10) * ou de Travail
                            </label>
                            <input type="text"
                                   class="form-control"
                                   id="diagnostic_principal"
                                   name="diagnostic_principal"
                                   value="<?php echo htmlspecialchars($consultation['diagnostic_principal'] ?? ''); ?>"
                                   placeholder="Ex: B50 - Paludisme à Plasmodium falciparum, ou description libre...">
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

<?php include __DIR__ . '/../../layouts/footer.php'; ?>