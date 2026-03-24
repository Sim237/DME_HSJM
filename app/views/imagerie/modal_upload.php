<!-- MODALE UPLOAD IMAGERIE -->
<div class="modal fade" id="uploadModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 20px;">
            <div class="modal-header bg-primary text-white border-0 p-4">
                <h5 class="modal-title fw-bold"><i class="bi bi-cloud-upload me-2"></i>Compléter l'examen</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="<?= BASE_URL ?>imagerie/upload" method="POST" enctype="multipart/form-data">
                <div class="modal-body p-4">
                    <input type="hidden" name="imagerie_id" id="imagerie_selector">

                    <div class="mb-4">
                        <label class="form-label fw-bold text-primary">FICHIER (DICOM OU IMAGE)</label>
                        <input type="file" name="dicom_file" class="form-control form-control-lg border-2" accept=".dcm,.dicom,.jpg,.jpeg,.png" required>
                        <small class="text-muted">Formats acceptés : .dcm, .jpg, .png</small>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold text-primary">COMPTE-RENDU / INTERPRÉTATION</label>
                        <textarea name="interpretation" class="form-control border-2" rows="5" placeholder="Saisir vos observations ici..." required></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold text-primary">CONCLUSION DIAGNOSTIQUE</label>
                        <input type="text" name="conclusion" class="form-control border-2" placeholder="Conclusion en une phrase..." required>
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <button type="submit" class="btn btn-primary btn-lg w-100 rounded-pill fw-bold shadow">ENREGISTRER ET TRANSMETTRE</button>
                </div>
            </form>
        </div>
    </div>
</div>