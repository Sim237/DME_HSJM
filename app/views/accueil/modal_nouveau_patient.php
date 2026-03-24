<!-- MODALE NOUVEAU PATIENT - DESIGN SOFT & PREMIUM -->
<div class="modal fade" id="modalNouveauPatient" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 24px; overflow: hidden;">

            <!-- Header avec dégradé léger -->
            <div class="modal-header border-0 p-4" style="background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);">
                <div class="d-flex align-items-center">
                    <div class="rounded-circle bg-primary bg-opacity-10 p-3 me-3">
                        <i class="bi bi-person-plus-fill text-primary fs-4"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold text-dark">Nouveau Dossier Patient</h5>
                        <small class="text-muted">Remplissez les informations pour créer le ticket d'admission</small>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form action="<?= BASE_URL ?>accueil/enregistrer-patient" method="POST" id="formNouveauPatient">
                <div class="modal-body p-4">

                    <!-- Navigation interne Soft -->
                    <ul class="nav nav-pills mb-4 bg-light p-1 rounded-3" id="pills-tab" role="tablist">
                        <li class="nav-item flex-fill" role="presentation">
                            <button class="nav-link active w-100 rounded-3 fw-600" id="pills-civil-tab" data-bs-toggle="pill" data-bs-target="#pills-civil" type="button" role="tab">
                                <i class="bi bi-card-checklist me-2"></i>État Civil
                            </button>
                        </li>
                        <li class="nav-item flex-fill" role="presentation">
                            <button class="nav-link w-100 rounded-3 fw-600" id="pills-contact-tab" data-bs-toggle="pill" data-bs-target="#pills-contact" type="button" role="tab">
                                <i class="bi bi-geo-alt me-2"></i>Contact & Social
                            </button>
                        </li>
                        <li class="nav-item flex-fill" role="presentation">
                            <button class="nav-link w-100 rounded-3 fw-600" id="pills-urgence-tab" data-bs-toggle="pill" data-bs-target="#pills-urgence" type="button" role="tab">
                                <i class="bi bi-shield-exclamation me-2"></i>Urgence
                            </button>
                        </li>
                    </ul>

                    <div class="tab-content" id="pills-tabContent">

                        <!-- VOLET 1 : ÉTAT CIVIL -->
                        <div class="tab-pane fade show active" id="pills-civil" role="tabpanel">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-secondary">NOM <span class="text-danger">*</span></label>
                                    <input type="text" name="nom" class="form-control input-custom" placeholder="ex: MEBARA" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-secondary">PRÉNOM <span class="text-danger">*</span></label>
                                    <input type="text" name="prenom" class="form-control input-custom" placeholder="ex: Jean" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-secondary">DATE DE NAISSANCE <span class="text-danger">*</span></label>
                                    <input type="date" name="date_naissance" class="form-control input-custom" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-secondary">SEXE <span class="text-danger">*</span></label>
                                    <div class="d-flex gap-3 mt-1">
                                        <div class="form-check custom-radio">
                                            <input class="form-check-input" type="radio" name="sexe" id="sexeM" value="M" checked>
                                            <label class="form-check-label" for="sexeM">Masculin</label>
                                        </div>
                                        <div class="form-check custom-radio">
                                            <input class="form-check-input" type="radio" name="sexe" id="sexeF" value="F">
                                            <label class="form-check-label" for="sexeF">Féminin</label>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label small fw-bold text-secondary">GROUPE SANGUIN (Optionnel)</label>
                                    <select name="groupe_sanguin" class="form-select input-custom">
                                        <option value="">Inconnu</option>
                                        <?php foreach(['A+','A-','B+','B-','AB+','AB-','O+','O-'] as $g) echo "<option value='$g'>$g</option>"; ?>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- VOLET 2 : CONTACT & SOCIAL -->
                        <div class="tab-pane fade" id="pills-contact" role="tabpanel">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-secondary">TÉLÉPHONE <span class="text-danger">*</span></label>
                                    <input type="tel" name="telephone" class="form-control input-custom" placeholder="6xx xx xx xx" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-secondary">PROFESSION</label>
                                    <input type="text" name="profession" class="form-control input-custom" placeholder="ex: Enseignant">
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label small fw-bold text-secondary">ADRESSE / VILLE</label>
                                    <input type="text" name="adresse" class="form-control input-custom" placeholder="ex: Njombé, Quartier 3">
                                </div>
                                <div class="col-md-12">
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
                        <div class="tab-pane fade" id="pills-urgence" role="tabpanel">
                            <div class="p-3 rounded-4 mb-3" style="background: #fff5f5; border: 1px solid #fed7d7;">
                                <h6 class="text-danger fw-bold mb-3 small"><i class="bi bi-exclamation-triangle me-2"></i>PERSONNE À PRÉVENIR</h6>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label small fw-bold">NOM DU CONTACT</label>
                                        <input type="text" name="contact_nom" class="form-control input-custom border-danger border-opacity-25" placeholder="Nom complet">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-bold">TÉLÉPHONE URGENCE</label>
                                        <input type="tel" name="contact_telephone" class="form-control input-custom border-danger border-opacity-25" placeholder="Numéro joignable">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- Footer avec actions claires -->
                <div class="modal-footer border-0 p-4" style="background: #f8fafc;">
                    <button type="button" class="btn btn-link text-muted fw-bold text-decoration-none" data-bs-dismiss="modal">Fermer</button>
                    <button type="submit" class="btn btn-primary px-5 py-3 rounded-pill shadow fw-bold">
                        ENREGISTRER & LANCER LA VISITE <i class="bi bi-arrow-right ms-2"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    /* Styles spécifiques pour la modale Soft */
    .input-custom {
        border-radius: 12px !important;
        border: 2px solid #edf2f7 !important;
        padding: 12px 16px !important;
        font-weight: 500 !important;
        background-color: #f8fafc !important;
        transition: all 0.2s ease !important;
    }
    .input-custom:focus {
        border-color: #3b82f6 !important;
        background-color: #fff !important;
        box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1) !important;
    }
    .nav-pills .nav-link { color: #64748b; font-size: 0.9rem; }
    .nav-pills .nav-link.active { background-color: #fff !important; color: #3b82f6 !important; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
    .fw-600 { font-weight: 600; }
</style>