<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card border-0 shadow-lg rounded-4 overflow-hidden">
                <div class="card-header bg-primary text-white p-4 d-flex justify-content-between">
                    <h4 class="mb-0 fw-bold"><i class="bi bi-heart-pulse-fill me-2"></i>Triage IAO</h4>
                    <span class="badge bg-white text-primary fs-6">Patient: <?= htmlspecialchars($adm['nom'].' '.$adm['prenom']) ?></span>
                </div>

                <form action="<?= BASE_URL ?>urgences/save-triage" method="POST">
                    <input type="hidden" name="admission_id" value="<?= $adm['id'] ?>">

                    <div class="card-body p-5">
                        <div class="row g-5">
                            <!-- Glasgow -->
                            <div class="col-md-6 border-end">
                                <h5 class="fw-bold mb-4">Calcul Score de Glasgow</h5>
                                <div class="mb-4">
                                    <label class="form-label small fw-bold">YEUX (1-4)</label>
                                    <select class="form-select form-select-lg gcs-calc" name="gcs_y" onchange="updateGCS()">
                                        <option value="4">4-Spontanée</option><option value="3">3-Bruit</option><option value="2">2-Douleur</option><option value="1">1-Nulle</option>
                                    </select>
                                </div>
                                <div class="mb-4">
                                    <label class="form-label small fw-bold">VERBALE (1-5)</label>
                                    <select class="form-select form-select-lg gcs-calc" name="gcs_v" onchange="updateGCS()">
                                        <option value="5">5-Orientée</option><option value="4">4-Confuse</option><option value="3">3-Inappropriée</option><option value="2">2-Incompréhensible</option><option value="1">1-Nulle</option>
                                    </select>
                                </div>
                                <div class="text-center bg-light p-3 rounded-4">
                                    <span class="d-block text-muted small">TOTAL GCS</span>
                                    <h1 class="fw-black mb-0" id="gcs_display">15/15</h1>
                                    <input type="hidden" name="gcs_total" id="gcs_input" value="15">
                                </div>
                            </div>

                            <!-- Constantes -->
                            <div class="col-md-6">
                                <h5 class="fw-bold mb-4">Paramètres Vitaux</h5>
                                <div class="row g-3">
                                    <div class="col-6"><label class="small fw-bold">Tension (SYS)</label><input type="number" name="sys" class="form-control" placeholder="120"></div>
                                    <div class="col-6"><label class="small fw-bold">Tension (DIA)</label><input type="number" name="dia" class="form-control" placeholder="80"></div>
                                    <div class="col-6"><label class="small fw-bold">Pouls (BPM)</label><input type="number" name="pouls" class="form-control"></div>
                                    <div class="col-6"><label class="small fw-bold">SpO2 (%)</label><input type="number" name="spo2" class="form-control"></div>
                                    <div class="col-12"><label class="small fw-bold">Motif / Plainte</label><textarea name="motif" class="form-control" rows="3"></textarea></div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-5 pt-4 border-top">
                            <h5 class="fw-bold mb-3 text-center">Niveau de Gravité</h5>
                            <div class="d-flex gap-3 justify-content-center">
                                <input type="radio" class="btn-check" name="niveau_priorite" id="p1" value="P1-VITAL">
                                <label class="btn btn-outline-danger px-4 py-3 fw-bold" for="p1">P1 - VITAL</label>
                                <input type="radio" class="btn-check" name="niveau_priorite" id="p2" value="P2-URGENT">
                                <label class="btn btn-outline-warning px-4 py-3 fw-bold" for="p2">P2 - URGENT</label>
                                <input type="radio" class="btn-check" name="niveau_priorite" id="p3" value="P3-STABLE" checked>
                                <label class="btn btn-outline-success px-4 py-3 fw-bold" for="p3">P3 - STABLE</label>
                            </div>
                        </div>
                    </div>

                    <div class="card-footer p-4 bg-light text-end">
                        <button type="submit" class="btn btn-primary btn-lg px-5 rounded-pill shadow">VALIDER LE TRIAGE</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function updateGCS() {
    let total = 0;
    document.querySelectorAll('.gcs-calc').forEach(s => total += parseInt(s.value));
    document.getElementById('gcs_display').innerText = total + "/15";
    document.getElementById('gcs_input').value = total;
}
</script>
<?php require_once __DIR__ . '/../layouts/footer.php'; ?>