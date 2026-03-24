<?php
require_once __DIR__ . '/../layouts/header.php';
$patient = $patient ?? [];
?>

<div class="container-fluid">
    <div class="row">
        <?php require_once __DIR__ . '/../layouts/sidebar.php'; ?>
        
        <main class="col-md-10 ms-sm-auto px-md-4">
            <div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2"><i class="bi bi-speedometer2"></i> Prise de Paramètres</h1>
                <a href="<?php echo BASE_URL; ?>patients" class="btn btn-secondary">Retour</a>
            </div>

            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="card shadow">
                        <div class="card-header bg-info text-white">
                            <h5 class="mb-0">
                                Patient : <?= htmlspecialchars($patient['nom'] . ' ' . $patient['prenom']) ?>
                                <span class="badge bg-light text-dark float-end"><?= htmlspecialchars($patient['dossier_numero']) ?></span>
                            </h5>
                        </div>
                        <div class="card-body">
                            <form action="<?php echo BASE_URL; ?>patients/save-mesures" method="POST">
                                <input type="hidden" name="patient_id" value="<?= $patient['id'] ?>">

                                <div class="row g-3">
                                    <!-- Physique -->
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold">Poids (kg)</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="bi bi-person-standing"></i></span>
                                            <input type="number" step="0.1" name="poids" class="form-control" placeholder="ex: 70.5">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold">Taille (cm)</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="bi bi-rulers"></i></span>
                                            <input type="number" name="taille" class="form-control" placeholder="ex: 175">
                                        </div>
                                    </div>

                                    <div class="col-12"><hr></div>

                                    <!-- Constantes -->
                                    <div class="col-md-4">
                                        <label class="form-label fw-bold">Température (°C)</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="bi bi-thermometer-half text-danger"></i></span>
                                            <input type="number" step="0.1" name="temperature" class="form-control" placeholder="ex: 37.2">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-bold">Pouls (bpm)</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="bi bi-heart-pulse text-danger"></i></span>
                                            <input type="number" name="frequence_cardiaque" class="form-control" placeholder="ex: 80">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-bold">Saturation O2 (%)</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="bi bi-lungs text-primary"></i></span>
                                            <input type="number" name="spo2" class="form-control" placeholder="ex: 98">
                                        </div>
                                    </div>

                                    <!-- Tension -->
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold">Tension Systolique (Haut)</label>
                                        <input type="number" name="tension_sys" class="form-control" placeholder="ex: 120">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold">Tension Diastolique (Bas)</label>
                                        <input type="number" name="tension_dia" class="form-control" placeholder="ex: 80">
                                    </div>
                                </div>

                                <div class="mt-4 d-grid">
                                    <button type="submit" class="btn btn-primary btn-lg">
                                        <i class="bi bi-save"></i> Enregistrer les Paramètres
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>