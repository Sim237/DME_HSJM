<?php
require_once __DIR__ . '/../layouts/header.php';
$patient = $patient ?? null;
?>

<div class="container-fluid">
    <div class="row">
        <?php require_once __DIR__ . '/../layouts/sidebar.php'; ?>
        
        <main class="col-md-10 ms-sm-auto px-md-4">
            <div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Type de Consultation</h1>
                <a href="index.php?page=consultation&action=selection" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> Retour
                </a>
            </div>
            
            <?php if ($patient): ?>
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Patient: <?= htmlspecialchars($patient['nom'] . ' ' . $patient['prenom']) ?></h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <p><strong>Dossier N°:</strong> <?= htmlspecialchars($patient['dossier_numero']) ?></p>
                        </div>
                        <div class="col-md-4">
                            <p><strong>Date de naissance:</strong> <?= date('d/m/Y', strtotime($patient['date_naissance'])) ?></p>
                        </div>
                        <div class="col-md-4">
                            <p><strong>Sexe:</strong> <?= $patient['sexe'] === 'M' ? 'Masculin' : 'Féminin' ?></p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row g-4">
                <div class="col-md-6">
                    <div class="card h-100 shadow-sm consultation-type-card" onclick="demarrerConsultation('EXTERNE')">
                        <div class="card-body text-center p-5">
                            <div class="mb-4">
                                <i class="bi bi-person-walking display-1 text-info"></i>
                            </div>
                            <h3 class="card-title mb-3">Consultation Externe</h3>
                            <p class="card-text text-muted">
                                Pour les patients non hospitalisés qui viennent en consultation ambulatoire.
                            </p>
                            <button class="btn btn-info btn-lg mt-3">
                                <i class="bi bi-clipboard-plus"></i> Commencer
                            </button>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="card h-100 shadow-sm consultation-type-card" onclick="demarrerConsultation('INTERNE')">
                        <div class="card-body text-center p-5">
                            <div class="mb-4">
                                <i class="bi bi-hospital display-1 text-success"></i>
                            </div>
                            <h3 class="card-title mb-3">Consultation Interne</h3>
                            <p class="card-text text-muted">
                                Pour les patients hospitalisés actuellement dans l'établissement.
                            </p>
                            <button class="btn btn-success btn-lg mt-3">
                                <i class="bi bi-clipboard-plus"></i> Commencer
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <?php else: ?>
            <div class="alert alert-warning">
                <i class="bi bi-exclamation-triangle"></i> Aucun patient sélectionné.
            </div>
            <?php endif; ?>
        </main>
    </div>
</div>

<style>
.consultation-type-card {
    cursor: pointer;
    transition: all 0.3s ease;
    border: 2px solid transparent;
}

.consultation-type-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 16px rgba(0,0,0,0.2) !important;
    border-color: #007bff;
}
</style>

<script>
function demarrerConsultation(type) {
    const patientId = <?= $patient['id'] ?? 'null' ?>;
    if (patientId) {
        window.location.href = `index.php?page=consultation&action=formulaire&patient_id=${patientId}&type=${type}&etape=1`;
    }
}
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>