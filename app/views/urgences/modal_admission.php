<div class="modal fade" id="modalFastAdmission" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header bg-primary text-white border-0">
                <h5 class="modal-title fw-bold">Admission Urgences Rapide</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="<?= BASE_URL ?>urgences/save-single" method="POST">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">NOM COMPLET</label>
                        <input type="text" name="nom" class="form-control" required placeholder="Saisir nom ou description...">
                    </div>
                    <div class="row">
                        <div class="col-6">
                            <label class="form-label small fw-bold">SEXE</label>
                            <select name="sexe" class="form-select"><option value="M">Masculin</option><option value="F">Féminin</option></select>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-bold">ÂGE APPROX.</label>
                            <input type="number" name="age" class="form-control">
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="submit" class="btn btn-primary w-100 rounded-pill shadow">ADMETTRE IMMÉDIATEMENT</button>
                </div>
            </form>
        </div>
    </div>
</div>