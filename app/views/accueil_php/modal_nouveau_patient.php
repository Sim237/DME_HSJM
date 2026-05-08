<!-- MODALE NOUVEAU PATIENT PHP -->
<div class="modal fade" id="modalNouveauPatient" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 24px; overflow: hidden;">

            <div class="modal-header border-0 p-4" style="background: linear-gradient(135deg, #faf5ff 0%, #ede9fe 100%);">
                <div class="d-flex align-items-center">
                    <div class="rounded-circle p-3 me-3" style="background: rgba(109,40,217,0.12);">
                        <i class="bi bi-person-plus-fill fs-4" style="color:#7c3aed;"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold text-dark">Nouveau Dossier Patient PHP</h5>
                        <small class="text-muted">Structure partenaire — Circuit PHP dédié</small>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <form action="<?= BASE_URL ?>accueil-php/enregistrer-patient" method="POST" id="formNouveauPatientPhp">
                <div class="modal-body p-4">

                    <ul class="nav nav-pills mb-4 bg-light p-1 rounded-3" id="pills-tab-php" role="tablist">
                        <li class="nav-item flex-fill">
                            <button class="nav-link active w-100 rounded-3 fw-600" data-bs-toggle="pill" data-bs-target="#pills-php-civil" type="button">
                                <i class="bi bi-card-checklist me-2"></i>État Civil
                            </button>
                        </li>
                        <li class="nav-item flex-fill">
                            <button class="nav-link w-100 rounded-3 fw-600" data-bs-toggle="pill" data-bs-target="#pills-php-contact" type="button">
                                <i class="bi bi-geo-alt me-2"></i>Contact
                            </button>
                        </li>
                        <li class="nav-item flex-fill">
                            <button class="nav-link w-100 rounded-3 fw-600" data-bs-toggle="pill" data-bs-target="#pills-php-urgence" type="button">
                                <i class="bi bi-shield-exclamation me-2"></i>Urgence
                            </button>
                        </li>
                    </ul>

                    <div class="tab-content">
                        <!-- VOLET 1 : ÉTAT CIVIL -->
                        <div class="tab-pane fade show active" id="pills-php-civil">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-secondary">NOM <span class="text-danger">*</span></label>
                                    <input type="text" name="nom" class="form-control input-custom" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-secondary">PRÉNOM <span class="text-danger">*</span></label>
                                    <input type="text" name="prenom" class="form-control input-custom" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-secondary">DATE DE NAISSANCE <span class="text-danger">*</span></label>
                                    <input type="date" name="date_naissance" class="form-control input-custom" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-secondary">SEXE <span class="text-danger">*</span></label>
                                    <div class="d-flex gap-3 mt-1">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="sexe" id="phpSexeM" value="M" checked>
                                            <label class="form-check-label" for="phpSexeM">Masculin</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="sexe" id="phpSexeF" value="F">
                                            <label class="form-check-label" for="phpSexeF">Féminin</label>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label small fw-bold text-secondary">GROUPE SANGUIN</label>
                                    <select name="groupe_sanguin" class="form-select input-custom">
                                        <option value="">Inconnu</option>
                                        <?php foreach(['A+','A-','B+','B-','AB+','AB-','O+','O-'] as $g) echo "<option value='$g'>$g</option>"; ?>
                                    </select>
                                </div>
                                <!-- TYPE DE CLIENT PHP -->
                                <div class="col-md-12">
                                    <label class="form-label small fw-bold text-secondary">TYPE DE CLIENT PHP <span class="text-danger">*</span></label>
                                    <div class="d-flex flex-wrap gap-2 mt-1">
                                        <div class="form-check type-client-option-php">
                                            <input class="form-check-input" type="radio" name="type_client" id="tcFamille" value="FAMILLE_PHP" checked>
                                            <label class="form-check-label" for="tcFamille">
                                                <i class="bi bi-house-heart me-1" style="color:#7c3aed;"></i> Famille PHP
                                            </label>
                                        </div>
                                        <div class="form-check type-client-option-php">
                                            <input class="form-check-input" type="radio" name="type_client" id="tcAgents" value="AGENTS_PHP">
                                            <label class="form-check-label" for="tcAgents">
                                                <i class="bi bi-person-badge me-1" style="color:#7c3aed;"></i> Agents PHP
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- VOLET 2 : CONTACT -->
                        <div class="tab-pane fade" id="pills-php-contact">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-secondary">TÉLÉPHONE <span class="text-danger">*</span></label>
                                    <input type="tel" name="telephone" class="form-control input-custom" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-secondary">PROFESSION</label>
                                    <input type="text" name="profession" class="form-control input-custom">
                                </div>
                                <div class="col-12">
                                    <label class="form-label small fw-bold text-secondary">ADRESSE</label>
                                    <input type="text" name="adresse" class="form-control input-custom">
                                </div>
                                <div class="col-12">
                                    <label class="form-label small fw-bold text-secondary">SITUATION MATRIMONIALE</label>
                                    <select name="situation_matrimoniale" class="form-select input-custom">
                                        <option value="celibataire">Célibataire</option>
                                        <option value="marie">Marié(e)</option>
                                        <option value="divorce">Divorcé(e)</option>
                                        <option value="veuf">Veuf/Veuve</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- VOLET 3 : URGENCE -->
                        <div class="tab-pane fade" id="pills-php-urgence">
                            <div class="p-3 rounded-4 mb-3" style="background:#faf5ff; border:1px solid #ddd6fe;">
                                <h6 class="fw-bold mb-3 small" style="color:#7c3aed;"><i class="bi bi-exclamation-triangle me-2"></i>PERSONNE À PRÉVENIR</h6>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label small fw-bold">NOM DU CONTACT</label>
                                        <input type="text" name="contact_nom" class="form-control input-custom">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-bold">TÉLÉPHONE URGENCE</label>
                                        <input type="tel" name="contact_telephone" class="form-control input-custom">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

                <div class="modal-footer border-0 p-4" style="background: #f8fafc;">
                    <button type="button" class="btn btn-link text-muted fw-bold text-decoration-none" data-bs-dismiss="modal">Fermer</button>
                    <button type="submit" class="btn px-5 py-3 rounded-pill shadow fw-bold text-white" style="background:#7c3aed;">
                        ENREGISTRER &amp; LANCER LA VISITE <i class="bi bi-arrow-right ms-2"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    .input-custom { border-radius:12px!important; border:2px solid #e9d5ff!important; padding:12px 16px!important; font-weight:500!important; background:#faf5ff!important; transition:all .2s!important; }
    .input-custom:focus { border-color:#7c3aed!important; background:#fff!important; box-shadow:0 0 0 4px rgba(124,58,237,.1)!important; }
    .nav-pills .nav-link { color:#64748b; font-size:.9rem; }
    .nav-pills .nav-link.active { background:#fff!important; color:#7c3aed!important; box-shadow:0 4px 6px rgba(0,0,0,.05); }
    .fw-600 { font-weight:600; }
    .type-client-option-php { background:#faf5ff; border:2px solid #ddd6fe; border-radius:10px; padding:8px 14px; transition:all .15s; cursor:pointer; }
    .type-client-option-php:has(.form-check-input:checked) { border-color:#7c3aed; background:#ede9fe; }
    .type-client-option-php label { cursor:pointer; font-size:.88rem; font-weight:600; }
</style>
