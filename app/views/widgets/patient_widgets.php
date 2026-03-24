<?php
// Widget unifié pour afficher les informations patient
function renderPatientWidget($patient_id) {
    require_once __DIR__ . '/../../services/DataService.php';
    
    $dataService = DataService::getInstance();
    $data = $dataService->getPatientComplet($patient_id);
    
    if (!$data) return '';
    
    $patient = $data['patient'];
    ?>
    <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h6 class="mb-0">
                <i class="bi bi-person-circle me-2"></i>
                <?php echo $patient['nom'] . ' ' . $patient['prenom']; ?>
            </h6>
            <span class="badge bg-primary"><?php echo $patient['dossier_numero']; ?></span>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <small class="text-muted">Âge:</small> 
                    <?php echo date_diff(date_create($patient['date_naissance']), date_create('today'))->y; ?> ans<br>
                    <small class="text-muted">Sexe:</small> <?php echo $patient['sexe']; ?><br>
                    <small class="text-muted">Téléphone:</small> <?php echo $patient['telephone'] ?? '-'; ?>
                </div>
                <div class="col-md-6">
                    <small class="text-muted">Consultations:</small> <?php echo count($data['consultations']); ?><br>
                    <small class="text-muted">Paramètres:</small> <?php echo count($data['parametres']); ?>
                </div>
            </div>
        </div>
    </div>
    <?php
}
?>