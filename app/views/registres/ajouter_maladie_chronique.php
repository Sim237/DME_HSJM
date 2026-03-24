<?php include __DIR__ . '/../layouts/header.php'; ?>

<div class="container-fluid">
    <div class="row">
        <?php include __DIR__ . '/../layouts/sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-0">
            <div class="main-content">
                <?php require_once __DIR__ . '/../layouts/topbar.php'; ?>
                
                <div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-4">
                    <h1 class="h2"><i class="bi bi-clipboard2-data text-primary me-2"></i>Nouvelle Maladie Chronique</h1>
                    <a href="<?php echo BASE_URL; ?>registres" class="btn btn-secondary">
                        <i class="bi bi-arrow-left me-1"></i>Retour
                    </a>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Enregistrement d'une maladie chronique</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Patient *</label>
                                        <select class="form-select" name="patient_id" required>
                                            <option value="">Sélectionner un patient</option>
                                            <?php foreach ($patients as $patient): ?>
                                                <option value="<?php echo $patient['id']; ?>">
                                                    <?php echo $patient['dossier_numero'] . ' - ' . $patient['nom'] . ' ' . $patient['prenom']; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Type de maladie *</label>
                                        <select class="form-select" name="type_maladie" required>
                                            <option value="">Sélectionner</option>
                                            <option value="DIABETE">Diabète</option>
                                            <option value="HYPERTENSION">Hypertension</option>
                                            <option value="MALADIE_RENALE">Maladie Rénale chronique</option>
                                            <option value="INSUFFISANCE_CARDIAQUE">Insuffisance Cardiaque</option>
                                            <option value="INSUFFISANCE_HEPATIQUE">Insuffisance Hépatique</option>
                                            <option value="BPCO">BPCO</option>
                                            <option value="CANCER">Cancer</option>
                                            <option value="VIH">VIH</option>
                                            <option value="HEPATITE_B">Hépatite B</option>
                                            <option value="HEPATITE_C">Hépatite C</option>
                                            <option value="TUBERCULOSE">Tuberculose</option>
                                            <option value="DREPANOCYTOSE">Drépanocytose</option>
                                            <option value="AUTRE">Autre</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Date de diagnostic *</label>
                                        <input type="date" class="form-control" name="date_diagnostic" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Stade/Grade</label>
                                        <input type="text" class="form-control" name="stade" 
                                               placeholder="Ex: Type 2, Grade 1, Stade II...">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Traitement actuel</label>
                                <textarea class="form-control" name="traitement_actuel" rows="3"
                                          placeholder="Décrire le traitement en cours..."></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Médecin référent</label>
                                <input type="text" class="form-control" name="medecin_referent" 
                                       placeholder="Nom du médecin référent">
                            </div>
                            
                            <div class="d-flex justify-content-end gap-2">
                                <a href="<?php echo BASE_URL; ?>registres" class="btn btn-secondary">Annuler</a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-check me-1"></i>Enregistrer
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
// Amélioration UX avec Select2 pour la sélection de patient
document.addEventListener('DOMContentLoaded', function() {
    const patientSelect = document.querySelector('select[name="patient_id"]');
    if (patientSelect && typeof $ !== 'undefined' && $.fn.select2) {
        $(patientSelect).select2({
            placeholder: 'Rechercher un patient...',
            allowClear: true
        });
    }
});
</script>

<?php include __DIR__ . '/../layouts/footer.php'; ?>