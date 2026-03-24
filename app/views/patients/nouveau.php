<!-- Dans app/views/accueil/dashboard.php ou nouveau.php -->
<div class="modal fade" id="modalNouveauPatient" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header bg-primary text-white p-4">
                <h5 class="modal-title fw-bold"><i class="bi bi-person-plus-fill me-2"></i>Enregistrement Nouveau Dossier Patient</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="<?= BASE_URL ?>accueil/enregistrer-patient" method="POST">
                <div class="modal-body p-4 bg-light">
                    <div class="row g-4">
                        <!-- SECTION : Identité -->
                        <div class="col-md-8">
                            <div class="card border-0 shadow-sm rounded-3 h-100">
                                <div class="card-body p-4">
                                    <h6 class="fw-bold text-primary mb-4 border-bottom pb-2">ÉTAT CIVIL</h6>
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label small fw-bold">NOM <span class="text-danger">*</span></label>
                                            <input type="text" name="nom" class="form-control form-control-lg border-2" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label small fw-bold">PRÉNOM <span class="text-danger">*</span></label>
                                            <input type="text" name="prenom" class="form-control form-control-lg border-2" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label small fw-bold">DATE DE NAISSANCE <span class="text-danger">*</span></label>
                                            <input type="date" name="date_naissance" class="form-control border-2" required>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label small fw-bold">SEXE <span class="text-danger">*</span></label>
                                            <select name="sexe" class="form-select border-2" required>
                                                <option value="M">Masculin</option>
                                                <option value="F">Féminin</option>
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label small fw-bold">GROUPE SANGUIN</label>
                                            <select name="groupe_sanguin" class="form-select border-2">
                                                <option value="">Inconnu</option>
                                                <option value="A+">A+</option><option value="A-">A-</option>
                                                <option value="B+">B+</option><option value="B-">B-</option>
                                                <option value="AB+">AB+</option><option value="AB-">AB-</option>
                                                <option value="O+">O+</option><option value="O-">O-</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label small fw-bold">TÉLÉPHONE <span class="text-danger">*</span></label>
                                            <input type="tel" name="telephone" class="form-control border-2" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label small fw-bold">ADRESSE / QUARTIER</label>
                                            <input type="text" name="adresse" class="form-control border-2">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- SECTION : Infos Complémentaires -->
                        <div class="col-md-4">
                            <div class="card border-0 shadow-sm rounded-3 mb-3">
                                <div class="card-body">
                                    <h6 class="fw-bold text-primary mb-3">SITUATION</h6>
                                    <label class="form-label small fw-bold">PROFESSION</label>
                                    <input type="text" name="profession" class="form-control mb-3 border-2">
                                    <label class="form-label small fw-bold">SITUATION MATRIMONIALE</label>
                                    <select name="situation_matrimoniale" class="form-select border-2">
                                        <option value="celibataire">Célibataire</option>
                                        <option value="marie">Marié(e)</option>
                                        <option value="divorce">Divorcé(e)</option>
                                        <option value="veuf">Veuf/Veuve</option>
                                    </select>
                                </div>
                            </div>
                            <div class="card border-0 shadow-sm rounded-3 bg-primary text-white">
                                <div class="card-body">
                                    <h6 class="fw-bold mb-3"><i class="bi bi-telephone-outbound me-2"></i>CONTACT URGENCE</h6>
                                    <input type="text" name="contact_nom" class="form-control form-control-sm mb-2" placeholder="Nom du contact">
                                    <input type="tel" name="contact_telephone" class="form-control form-control-sm" placeholder="Téléphone">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-white p-4">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-5 shadow">
                        <i class="bi bi-check2-circle me-2"></i>Enregistrer et Commencer la visite
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>