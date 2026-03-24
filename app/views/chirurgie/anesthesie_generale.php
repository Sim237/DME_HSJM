<div class="container-fluid p-4">
    <div class="card shadow border-0 rounded-4">
        <div class="card-header bg-primary text-white py-3">
            <h5 class="mb-0"><i class="bi bi-mask me-2"></i> PROTOCOLE D'ANESTHÉSIE GÉNÉRALE</h5>
        </div>
        <form action="<?= BASE_URL ?>chirurgie/terminer" method="POST">
            <input type="hidden" name="patient_id" value="<?= $patient['id'] ?>">
            <input type="hidden" name="type_anesth" value="generale">
            <input type="hidden" name="anesth_id" value="<?= $_GET['anesth_id'] ?>">

            <div class="card-body">
                <!-- Evaluation Pré-op -->
                <div class="row g-3 mb-4 p-3 bg-light rounded-3">
                    <div class="col-md-3">
                        <label class="small fw-bold">Score ASA</label>
                        <select name="asa" class="form-select">
                            <option>ASA 1</option><option>ASA 2</option><option>ASA 3</option><option>ASA 4</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="small fw-bold">Mallampati</label>
                        <select name="mallampati" class="form-select">
                            <option value="1">Classe I</option><option value="2">Classe II</option>
                            <option value="3">Classe III</option><option value="4">Classe IV</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="small fw-bold">Allergies connues</label>
                        <input type="text" class="form-control" value="<?= $patient['allergies'] ?>" readonly>
                    </div>
                </div>

                <!-- Technique -->
                <h6 class="fw-bold border-bottom pb-2">Induction & Intubation</h6>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="small">Hypnotique</label>
                        <input type="text" name="tech[hypnotique]" class="form-control" placeholder="ex: Propofol">
                    </div>
                    <div class="col-md-4">
                        <label class="small">Curare</label>
                        <input type="text" name="tech[curare]" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label class="small">Taille Sonde d'intubation</label>
                        <input type="text" name="tech[sonde]" class="form-control">
                    </div>
                </div>

                <div class="mt-4">
                    <label class="fw-bold">Entretien / Gaz</label>
                    <div class="d-flex gap-3 mt-2">
                        <div class="form-check"><input type="checkbox" name="tech[sevoflurane]" class="form-check-input"> Sevorane</div>
                        <div class="form-check"><input type="checkbox" name="tech[desflurane]" class="form-check-input"> Desflurane</div>
                    </div>
                </div>
            </div>
            <div class="card-footer bg-white border-0 p-4">
                <button type="submit" class="btn btn-primary btn-lg w-100 rounded-pill shadow">
                    <i class="bi bi-send-check me-2"></i> Valider et Transmettre au Bloc
                </button>
            </div>
        </form>
    </div>
</div>