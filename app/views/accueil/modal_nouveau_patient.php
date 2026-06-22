<!-- =====================================================================
     MODALE ANCIEN PATIENT — Recherche, consultation et nouvelle visite
     ===================================================================== -->
<div class="modal fade" id="modalAncienPatient" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 24px; overflow: hidden;">

            <!-- Header -->
            <div class="modal-header border-0 p-4" style="background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);">
                <div class="d-flex align-items-center gap-3 w-100">
                    <div class="rounded-circle bg-success bg-opacity-15 p-3 flex-shrink-0">
                        <i class="bi bi-person-check-fill text-success fs-4"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h5 class="modal-title fw-bold text-dark mb-0" id="ancienPatientTitre">Patient existant</h5>
                        <div class="d-flex align-items-center gap-2 mt-1">
                            <span class="badge bg-dark rounded-pill px-3" id="ancienDossierBadge">—</span>
                            <small class="text-muted" id="ancienInfoVisite"></small>
                        </div>
                    </div>
                    <button type="button" class="btn-close flex-shrink-0" data-bs-dismiss="modal"></button>
                </div>
            </div>

            <!-- Loader -->
            <div id="ancienPatientLoader" class="text-center py-5 d-none">
                <div class="spinner-border text-success" role="status"></div>
                <div class="text-muted mt-2 small">Chargement du dossier…</div>
            </div>

            <form action="<?= BASE_URL ?>accueil/nouvelle-visite" method="POST" id="formAncienPatient">
                <?php
/**
 * SimCare+ — Dossier Médical Électronique (DME)
 * Copyright (c) 2024-2026 Franck Simeni. Tous droits réservés.
 * Développé pour la gestion hospitalière, et le bien être numérique des patients.
 *
 * Toute reproduction, modification ou distribution de ce logiciel,
 * en tout ou en partie, sans autorisation écrite préalable de l'auteur
 * est strictement interdite et constitue une contrefaçon.
 *
 * Protected under OAPI Agreement — Annexe VII · Berne Convention
 */
 if (!class_exists('CsrfService')) require_once __DIR__ . '/../../services/CsrfService.php'; echo CsrfService::field(); ?>
                <input type="hidden" name="patient_id" id="ancienPatientId">

                <div class="modal-body p-4" id="ancienPatientBody">

                    <!-- Onglets -->
                    <ul class="nav nav-pills mb-4 bg-light p-1 rounded-3" role="tablist">
                        <li class="nav-item flex-fill" role="presentation">
                            <button class="nav-link active w-100 rounded-3 fw-600 small" data-bs-toggle="pill" data-bs-target="#ancienCivil" type="button">
                                <i class="bi bi-card-checklist me-1"></i> État Civil
                            </button>
                        </li>
                        <li class="nav-item flex-fill" role="presentation">
                            <button class="nav-link w-100 rounded-3 fw-600 small" data-bs-toggle="pill" data-bs-target="#ancienContact" type="button">
                                <i class="bi bi-geo-alt me-1"></i> Contact
                            </button>
                        </li>
                        <li class="nav-item flex-fill" role="presentation">
                            <button class="nav-link w-100 rounded-3 fw-600 small" data-bs-toggle="pill" data-bs-target="#ancienUrgence" type="button">
                                <i class="bi bi-shield-exclamation me-1"></i> Urgence
                            </button>
                        </li>
                        <li class="nav-item flex-fill" role="presentation">
                            <button class="nav-link w-100 rounded-3 fw-600 small" data-bs-toggle="pill" data-bs-target="#ancienFacturation" type="button">
                                <i class="bi bi-cash-coin me-1"></i> Facturation
                            </button>
                        </li>
                    </ul>

                    <div class="tab-content">

                        <!-- VOLET 1 : ÉTAT CIVIL -->
                        <div class="tab-pane fade show active" id="ancienCivil">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-secondary">NOM <span class="text-danger">*</span></label>
                                    <input type="text" name="nom" id="ancienNom" class="form-control input-custom text-uppercase" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-secondary">PRÉNOM <span class="text-danger">*</span></label>
                                    <input type="text" name="prenom" id="ancienPrenom" class="form-control input-custom" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-secondary">DATE DE NAISSANCE</label>
                                    <input type="text" id="ancienDdnAffichage" class="form-control input-custom bg-light" readonly>
                                    <small class="text-muted">Non modifiable</small>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-secondary">SEXE</label>
                                    <input type="text" id="ancienSexeAffichage" class="form-control input-custom bg-light" readonly>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-secondary">GROUPE SANGUIN</label>
                                    <select name="groupe_sanguin" id="ancienGroupeSanguin" class="form-select input-custom">
                                        <option value="">Inconnu</option>
                                        <?php foreach(['A+','A-','B+','B-','AB+','AB-','O+','O-'] as $g) echo "<option value='$g'>$g</option>"; ?>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-secondary">SITUATION MATRIMONIALE</label>
                                    <select name="situation_matrimoniale" id="ancienSituation" class="form-select input-custom">
                                        <option value="celibataire">Célibataire</option>
                                        <option value="marie">Marié(e)</option>
                                        <option value="divorce">Divorcé(e)</option>
                                        <option value="veuf">Veuf/Veuve</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- VOLET 2 : CONTACT -->
                        <div class="tab-pane fade" id="ancienContact">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-secondary">TÉLÉPHONE <span class="text-danger">*</span></label>
                                    <input type="tel" name="telephone" id="ancienTelephone" class="form-control input-custom" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-secondary">PROFESSION</label>
                                    <input type="text" name="profession" id="ancienProfession" class="form-control input-custom">
                                </div>
                                <div class="col-12">
                                    <label class="form-label small fw-bold text-secondary">ADRESSE / QUARTIER</label>
                                    <input type="text" name="adresse" id="ancienAdresse" class="form-control input-custom">
                                </div>
                            </div>
                        </div>

                        <!-- VOLET 3 : URGENCE -->
                        <div class="tab-pane fade" id="ancienUrgence">
                            <div class="p-3 rounded-4" style="background: #fff5f5; border: 1px solid #fed7d7;">
                                <h6 class="text-danger fw-bold mb-3 small"><i class="bi bi-exclamation-triangle me-2"></i>PERSONNE À PRÉVENIR</h6>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label small fw-bold">NOM DU CONTACT</label>
                                        <input type="text" name="contact_nom" id="ancienContactNom" class="form-control input-custom border-danger border-opacity-25">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-bold">TÉLÉPHONE URGENCE</label>
                                        <input type="tel" name="contact_telephone" id="ancienContactTel" class="form-control input-custom border-danger border-opacity-25">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- VOLET 4 : FACTURATION -->
                        <div class="tab-pane fade" id="ancienFacturation">
                            <label class="form-label small fw-bold text-secondary">TYPE DE CLIENT <span class="text-danger">*</span></label>
                            <div class="d-flex flex-wrap gap-2 mt-1 mb-3">
                                <?php foreach([
                                    ['PAYANT_COMPTANT','cash-coin','text-success','Payant Comptant'],
                                    ['BON_PRISE_EN_CHARGE','file-medical','text-primary','Bon prise en charge'],
                                    ['ASSURANCE','shield-check','text-info','Assurance'],
                                    ['FAMILLE_PHP','house-heart','text-warning','Famille PHP'],
                                    ['AGENTS_PHP','person-badge','text-secondary','Agents PHP'],
                                ] as [$val,$ico,$cls,$lbl]): ?>
                                <div class="form-check type-client-option">
                                    <input class="form-check-input" type="radio" name="type_client" id="ancienTc<?= $val ?>" value="<?= $val ?>">
                                    <label class="form-check-label" for="ancienTc<?= $val ?>">
                                        <i class="bi bi-<?= $ico ?> me-1 <?= $cls ?>"></i> <?= $lbl ?>
                                    </label>
                                </div>
                                <?php endforeach; ?>
                            </div>

                            <!-- Bloc Assurance -->
                            <div id="ancienBlocAssurance" style="display:none;" class="mt-2">
                                <div class="row g-3 p-3 rounded-3 border border-info-subtle bg-info bg-opacity-10">
                                    <div class="col-12" style="font-size:.75rem;font-weight:700;color:#0891b2;">
                                        <i class="bi bi-shield-check me-1"></i>Informations d'assurance (obligatoires)
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-bold">NOM DE L'ASSURANCE <span class="text-danger">*</span></label>
                                        <input type="text" name="nom_assurance" id="ancienNomAssurance" class="form-control input-custom" placeholder="ex: CNPS, Allianz…">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-bold">N° ASSURÉ <span class="text-danger">*</span></label>
                                        <input type="text" name="numero_assure" id="ancienNumeroAssure" class="form-control input-custom" placeholder="ex: ASS-2024-00123">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-bold">ORGANISME PAYEUR</label>
                                        <input type="text" name="organisme_payeur" id="ancienOrganismeAssur" class="form-control input-custom" placeholder="ex: CNPS">
                                    </div>
                                </div>
                            </div>

                            <!-- Bloc BON DE PRISE EN CHARGE -->
                            <div id="ancienBlocBon" style="display:none;" class="mt-2">
                                <div class="row g-3 p-3 rounded-3" style="border:1.5px solid #93c5fd;background:#eff6ff;">
                                    <div class="col-12" style="font-size:.75rem;font-weight:700;color:#1d4ed8;">
                                        <i class="bi bi-file-medical me-1"></i>Bon de prise en charge (obligatoire)
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-bold">N° DU BON <span class="text-danger">*</span></label>
                                        <input type="text" name="numero_bon" id="ancienNumeroBon" class="form-control input-custom" placeholder="ex: BON-2026-00123">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-bold">ORGANISME ÉMETTEUR <span class="text-danger">*</span></label>
                                        <input type="text" name="organisme_payeur" id="ancienOrganismeBon" class="form-control input-custom" placeholder="ex: Ministère de la Santé">
                                    </div>
                                </div>
                            </div>

                            <!-- Bloc PHP — Agent -->
                            <div id="ancienBlocAgentPhp" style="display:none;" class="mt-2">
                                <div class="row g-3 p-3 rounded-3" style="background:#fffbeb;border:1.5px solid #fde68a;">
                                    <div class="col-12" style="font-size:.75rem;font-weight:700;color:#92400e;">
                                        <i class="bi bi-person-badge me-1"></i>Informations Agent PHP
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-bold">MATRICULE <span class="text-danger">*</span></label>
                                        <input type="text" name="matricule_agent" id="ancienMatricule" class="form-control input-custom" placeholder="ex: PHP-2024-0123">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-bold">INFIRMERIE D'ORIGINE</label>
                                        <input type="text" name="infirmerie_origine" id="ancienInfirmerie" class="form-control input-custom" placeholder="ex: Infirmerie de Njombé">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- ══ SERVICE DE DESTINATION (sauf PHP) ═══════════════════ -->
                        <div id="blocServiceDestAnc" class="mt-3 p-3 rounded-3"
                             style="background:#f0fdf4;border:1.5px solid #86efac;">
                            <label class="form-label small fw-bold" style="color:#15803d;">
                                <i class="bi bi-hospital me-1"></i>SERVICE DE DESTINATION
                            </label>
                            <select name="service_id_destination" id="serviceIdDestAnc"
                                    class="form-select" style="border-radius:12px;">
                                <option value="">— Parcours standard (bureau paramètres B1/B2) —</option>
                                <?php
                                try {
                                    $dbSvcAnc = (new Database())->getConnection();
                                    $svcsAnc  = $dbSvcAnc->query(
                                        "SELECT id, nom_service FROM services
                                          WHERE nom_service NOT LIKE '%Accueil%'
                                            AND nom_service NOT LIKE '%Param%'
                                            AND nom_service NOT LIKE '%Administ%'
                                            AND nom_service NOT LIKE '%Laboratoire%'
                                            AND nom_service NOT LIKE '%Imagerie%'
                                            AND nom_service NOT LIKE '%Banque%'
                                          ORDER BY nom_service ASC"
                                    )->fetchAll(PDO::FETCH_ASSOC);
                                    foreach ($svcsAnc as $sa):
                                ?>
                                <option value="<?= $sa['id'] ?>"><?= htmlspecialchars($sa['nom_service']) ?></option>
                                <?php endforeach; } catch (\Throwable $e) {} ?>
                            </select>
                            <small class="text-muted mt-1 d-block" style="font-size:.78rem;">
                                <i class="bi bi-info-circle me-1"></i>
                                Accès direct à la file d'un service. Laissez vide pour le parcours habituel.
                            </small>
                        </div>

                    </div><!-- /tab-content -->
                </div><!-- /modal-body -->

                <!-- Footer -->
                <div class="modal-footer border-0 p-4 d-flex justify-content-between align-items-center" style="background: #f8fafc;">
                    <button type="button" class="btn btn-link text-muted fw-bold text-decoration-none" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-success px-5 py-3 rounded-pill shadow fw-bold fs-6">
                        <i class="bi bi-play-circle-fill me-2"></i> DÉMARRER LA VISITE
                    </button>
                </div>
            </form>

        </div>
    </div>
</div>

<script>
(function () {
    // ── Toggle blocs selon type_client (ancien patient) ──────────────────────
    function toggleAncienAssurance() {
        const val      = document.querySelector('#formAncienPatient input[name="type_client"]:checked')?.value;
        const isAssur  = val === 'ASSURANCE';
        const isBon    = val === 'BON_PRISE_EN_CHARGE';
        const isAgent  = val === 'AGENTS_PHP';
        const isPhpAnc = val === 'FAMILLE_PHP' || val === 'AGENTS_PHP';

        const blocAssur = document.getElementById('ancienBlocAssurance');
        if (blocAssur) blocAssur.style.display = isAssur ? 'block' : 'none';

        const blocBon = document.getElementById('ancienBlocBon');
        if (blocBon) blocBon.style.display = isBon ? 'block' : 'none';

        const blocAgent = document.getElementById('ancienBlocAgentPhp');
        if (blocAgent) blocAgent.style.display = isAgent ? 'block' : 'none';

        // Masquer service selector pour PHP
        const blocSvc = document.getElementById('blocServiceDestAnc');
        if (blocSvc) blocSvc.style.display = isPhpAnc ? 'none' : 'block';
    }
    document.querySelectorAll('#ancienFacturation input[name="type_client"]').forEach(r =>
        r.addEventListener('change', toggleAncienAssurance)
    );

    // ── Charger et afficher les données du patient ────────────────────────────
    window.ouvrirAncienPatient = function(id) {
        const modal  = new bootstrap.Modal(document.getElementById('modalAncienPatient'));
        const loader = document.getElementById('ancienPatientLoader');
        const body   = document.getElementById('ancienPatientBody');
        const form   = document.getElementById('formAncienPatient');

        // Afficher loader, cacher body
        body.classList.add('d-none');
        loader.classList.remove('d-none');
        form.querySelector('.modal-footer') && (form.querySelector('.modal-footer').classList.add('d-none'));
        modal.show();

        fetch('<?= BASE_URL ?>accueil/get-patient/' + id)
            .then(r => r.json())
            .then(p => {
                if (!p) { alert('Patient introuvable.'); modal.hide(); return; }

                // En-tête
                document.getElementById('ancienPatientId').value    = p.id;
                document.getElementById('ancienPatientTitre').textContent = p.nom + ' ' + p.prenom;
                document.getElementById('ancienDossierBadge').textContent = p.dossier_numero;
                document.getElementById('ancienInfoVisite').textContent   =
                    'Enregistré le ' + new Date(p.created_at).toLocaleDateString('fr-FR')
                    + (p.nb_consultations > 0 ? ' · ' + p.nb_consultations + ' consultation(s)' : ' · Aucune consultation');

                // Onglet Civil
                document.getElementById('ancienNom').value    = p.nom    || '';
                document.getElementById('ancienPrenom').value = p.prenom || '';
                document.getElementById('ancienDdnAffichage').value = p.date_naissance
                    ? new Date(p.date_naissance).toLocaleDateString('fr-FR') : '';
                document.getElementById('ancienSexeAffichage').value = p.sexe === 'M' ? 'Masculin' : 'Féminin';
                document.getElementById('ancienGroupeSanguin').value   = p.groupe_sanguin || '';
                document.getElementById('ancienSituation').value       = p.situation_matrimoniale || 'celibataire';

                // Onglet Contact
                document.getElementById('ancienTelephone').value  = p.telephone  || '';
                document.getElementById('ancienProfession').value = p.profession || '';
                document.getElementById('ancienAdresse').value    = p.adresse    || '';

                // Onglet Urgence
                document.getElementById('ancienContactNom').value = p.contact_nom       || '';
                document.getElementById('ancienContactTel').value = p.contact_telephone || '';

                // Onglet Facturation
                const tc = p.type_client || 'PAYANT_COMPTANT';
                const radio = document.getElementById('ancienTc' + tc);
                if (radio) { radio.checked = true; }
                else { const def = document.getElementById('ancienTcPAYANT_COMPTANT'); if(def) def.checked = true; }
                document.getElementById('ancienNomAssurance').value   = p.nom_assurance      || '';
                document.getElementById('ancienNumeroAssure').value   = p.numero_assure      || '';
                const ancienOrgAssur = document.getElementById('ancienOrganismeAssur');
                if (ancienOrgAssur) ancienOrgAssur.value = p.organisme_payeur || '';
                const ancienNumeroBon = document.getElementById('ancienNumeroBon');
                if (ancienNumeroBon) ancienNumeroBon.value = p.numero_bon || '';
                const ancienOrgBon = document.getElementById('ancienOrganismeBon');
                if (ancienOrgBon) ancienOrgBon.value = tc === 'BON_PRISE_EN_CHARGE' ? (p.organisme_payeur || '') : '';
                const ancienMat = document.getElementById('ancienMatricule');
                if (ancienMat) ancienMat.value = p.matricule_agent || '';
                const ancienInf = document.getElementById('ancienInfirmerie');
                if (ancienInf) ancienInf.value = p.infirmerie_origine || '';
                toggleAncienAssurance();

                // Afficher le formulaire
                loader.classList.add('d-none');
                body.classList.remove('d-none');
                form.querySelector('.modal-footer')?.classList.remove('d-none');
            })
            .catch(() => { alert('Erreur réseau.'); modal.hide(); });
    };
})();
</script>

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
                <?php if (!class_exists('CsrfService')) require_once __DIR__ . '/../../services/CsrfService.php'; echo CsrfService::field(); ?>
                <input type="hidden" name="_skip_doublon_check" id="_skip_doublon_check" value="0">
                <div class="modal-body p-4">

                    <!-- ══ RECHERCHE DOUBLON ══════════════════════════════════════ -->
                    <div class="mb-4 p-3 rounded-3" style="background:#f0f9ff;border:1.5px solid #bae6fd;">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <i class="bi bi-search text-primary"></i>
                            <span class="fw-bold small text-primary">Vérification doublon</span>
                            <span class="text-muted small ms-1">— Tapez le nom et prénom pour voir si le patient existe déjà</span>
                        </div>
                        <div class="d-flex gap-2">
                            <input type="text" id="dbNom" class="form-control input-custom" placeholder="Nom…" style="max-width:180px;">
                            <input type="text" id="dbPrenom" class="form-control input-custom" placeholder="Prénom…" style="max-width:180px;">
                            <input type="tel" id="dbTel" class="form-control input-custom" placeholder="Tél (optionnel)…">
                        </div>
                        <!-- Résultats -->
                        <div id="doublonResults" class="mt-2" style="display:none;"></div>
                    </div>

                    <!-- ══ WIZARD STEPPER ════════════════════════════════════════ -->
                    <div class="wz-stepper d-flex align-items-center mb-4">
                        <div class="wz-step-ind wz-active" id="wz-indicator-1">
                            <div class="wz-circle">1</div>
                            <div class="wz-step-label">État Civil</div>
                        </div>
                        <div class="wz-line flex-grow-1"></div>
                        <div class="wz-step-ind" id="wz-indicator-2">
                            <div class="wz-circle">2</div>
                            <div class="wz-step-label">Contact &amp; Urgence</div>
                        </div>
                        <div class="wz-line flex-grow-1"></div>
                        <div class="wz-step-ind" id="wz-indicator-3">
                            <div class="wz-circle">3</div>
                            <div class="wz-step-label">Facturation</div>
                        </div>
                    </div>

                    <!-- STEP 1 : ÉTAT CIVIL -->
                    <div id="wz-step-1" class="wz-step-pane">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-secondary">NOM <span class="text-danger">*</span></label>
                                <input type="text" name="nom" class="form-control input-custom" placeholder="ex: MEBARA" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-secondary">PRÉNOM <span class="text-danger">*</span></label>
                                <input type="text" name="prenom" class="form-control input-custom" placeholder="ex: Jean" required>
                            </div>
                                <!-- DATE DE NAISSANCE / ÂGE ESTIMATIF -->
                                <div class="col-md-6">
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                        <label class="form-label small fw-bold text-secondary mb-0">
                                            DATE DE NAISSANCE <span class="text-danger" id="npDobRequired">*</span>
                                        </label>
                                        <!-- Toggle bascule -->
                                        <div class="d-flex rounded-pill overflow-hidden border" style="font-size:.72rem;flex-shrink:0">
                                            <button type="button" id="npBtnDate"
                                                    onclick="npSetDobMode('date')"
                                                    class="px-3 py-1 border-0 fw-bold bg-primary text-white"
                                                    style="transition:.15s;line-height:1.4">
                                                <i class="bi bi-calendar3 me-1"></i>Date
                                            </button>
                                            <button type="button" id="npBtnAge"
                                                    onclick="npSetDobMode('age')"
                                                    class="px-3 py-1 border-0 fw-bold bg-white text-muted"
                                                    style="transition:.15s;line-height:1.4">
                                                <i class="bi bi-123 me-1"></i>Âge estimatif
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Mode Date -->
                                    <div id="npFieldDate">
                                        <input type="date" name="date_naissance" id="npInputDdn"
                                               class="form-control input-custom" required>
                                    </div>

                                    <!-- Mode Âge -->
                                    <div id="npFieldAge" style="display:none">
                                        <div class="input-group">
                                            <input type="number" name="age_estimatif" id="npInputAge"
                                                   class="form-control input-custom"
                                                   placeholder="Ex : 45" min="0" max="120"
                                                   oninput="npEstimateDob(this.value)"
                                                   style="border-right:none!important">
                                            <span class="input-group-text fw-bold text-muted"
                                                  style="border-radius:0 12px 12px 0;border:2px solid #edf2f7;border-left:none;background:#f8fafc">
                                                ans
                                            </span>
                                        </div>
                                        <input type="hidden" name="" id="npHiddenDdn">
                                        <div id="npDobEstimate" class="mt-1 small text-muted" style="font-size:.75rem"></div>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-secondary">SEXE <span class="text-danger">*</span></label>
                                    <div class="d-flex gap-3 mt-1">
                                        <div class="form-check custom-radio">
                                            <input class="form-check-input" type="radio" name="sexe" id="sexeM" value="M" checked
                                                   onchange="npToggleFemmeEnceinte()">
                                            <label class="form-check-label" for="sexeM">Masculin</label>
                                        </div>
                                        <div class="form-check custom-radio">
                                            <input class="form-check-input" type="radio" name="sexe" id="sexeF" value="F"
                                                   onchange="npToggleFemmeEnceinte()">
                                            <label class="form-check-label" for="sexeF">Féminin</label>
                                        </div>
                                    </div>
                                </div>
                                <!-- Patiente enceinte — visible uniquement si Sexe = F, OBLIGATOIRE -->
                                <div class="col-12" id="blocFemmeEnceinte" style="display:none;">
                                    <div class="p-3 rounded-3" style="background:#fdf2f8;border:1.5px solid #f9a8d4;">
                                        <div class="fw-bold mb-2" style="color:#9d174d;font-size:.85rem;">
                                            <i class="bi bi-heart-pulse-fill me-1"></i>
                                            Patiente enceinte ?&nbsp;<span class="text-danger">*</span>
                                        </div>
                                        <div class="d-flex gap-2">
                                            <!-- OUI -->
                                            <label class="fe-card flex-fill" id="feOuiCard" for="npFemmeEnceinte_oui" style="cursor:pointer;">
                                                <input type="radio" name="femme_enceinte" value="1"
                                                       id="npFemmeEnceinte_oui"
                                                       onchange="npOnFemmeEnceinte()"
                                                       style="position:absolute;opacity:0;pointer-events:none;">
                                                <div class="fe-card-inner text-center py-3 px-2 rounded-3 h-100" id="feOuiBox">
                                                    <i class="bi bi-heart-pulse-fill d-block mb-1" style="font-size:1.4rem;color:#db2777;"></i>
                                                    <div class="fw-bold" style="font-size:.85rem;color:#9d174d;">OUI</div>
                                                    <div style="font-size:.68rem;color:#be185d;margin-top:2px;">→ Bureau Maternité</div>
                                                </div>
                                            </label>
                                            <!-- NON -->
                                            <label class="fe-card flex-fill" id="feNonCard" for="npFemmeEnceinte_non" style="cursor:pointer;">
                                                <input type="radio" name="femme_enceinte" value="0"
                                                       id="npFemmeEnceinte_non"
                                                       onchange="npOnFemmeEnceinte()"
                                                       style="position:absolute;opacity:0;pointer-events:none;">
                                                <div class="fe-card-inner text-center py-3 px-2 rounded-3 h-100" id="feNonBox">
                                                    <i class="bi bi-person-check d-block mb-1" style="font-size:1.4rem;color:#6b7280;"></i>
                                                    <div class="fw-bold" style="font-size:.85rem;color:#374151;">NON</div>
                                                    <div style="font-size:.68rem;color:#6b7280;margin-top:2px;">→ Bureau B1 / B2</div>
                                                </div>
                                            </label>
                                        </div>
                                        <div id="feErreur" style="display:none;color:#dc2626;font-size:.75rem;margin-top:6px;">
                                            <i class="bi bi-exclamation-circle me-1"></i>Veuillez indiquer si la patiente est enceinte.
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

                    <!-- STEP 2 : CONTACT & URGENCE -->
                    <div id="wz-step-2" class="wz-step-pane" style="display:none;">
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
                            <div class="col-md-12">
                                <div class="p-3 rounded-3" style="background:#f5f3ff;border:1.5px solid #ddd6fe;">
                                    <label class="form-label small fw-bold mb-1" style="color:#5b21b6;">
                                        <i class="bi bi-database-fill me-1" style="color:#7c3aed;"></i>
                                        NUMÉRO DE COMPTE SAGE
                                        <span class="text-muted fw-normal ms-1">(optionnel)</span>
                                    </label>
                                    <input type="text" name="numero_compte_sage" class="form-control input-custom"
                                           placeholder="ex: CPT-2024-00456" style="border-color:#c4b5fd!important;">
                                    <div class="small mt-1" style="color:#7c3aed;opacity:.7;">
                                        Numéro de compte issu du logiciel de comptabilité Sage
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- Personne à prévenir -->
                        <div class="p-3 rounded-4 mt-3" style="background:#fff5f5;border:1px solid #fed7d7;">
                            <h6 class="text-danger fw-bold mb-3 small">
                                <i class="bi bi-exclamation-triangle me-2"></i>PERSONNE À PRÉVENIR
                            </h6>
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

                    <!-- STEP 3 : FACTURATION -->
                    <div id="wz-step-3" class="wz-step-pane" style="display:none;">
                        <label class="form-label small fw-bold text-secondary">TYPE DE CLIENT <span class="text-danger">*</span></label>
                        <div class="d-flex flex-wrap gap-2 mt-1">
                            <div class="form-check type-client-option">
                                <input class="form-check-input" type="radio" name="type_client" id="tcPayant" value="PAYANT_COMPTANT" checked>
                                <label class="form-check-label" for="tcPayant">
                                    <i class="bi bi-cash-coin me-1 text-success"></i> Payant Comptant
                                </label>
                            </div>
                            <div class="form-check type-client-option">
                                <input class="form-check-input" type="radio" name="type_client" id="tcBon" value="BON_PRISE_EN_CHARGE">
                                <label class="form-check-label" for="tcBon">
                                    <i class="bi bi-file-medical me-1 text-primary"></i> Bon prise en charge
                                </label>
                            </div>
                            <div class="form-check type-client-option">
                                <input class="form-check-input" type="radio" name="type_client" id="tcAssurance" value="ASSURANCE">
                                <label class="form-check-label" for="tcAssurance">
                                    <i class="bi bi-shield-check me-1 text-info"></i> Assurance
                                </label>
                            </div>
                            <div class="form-check type-client-option" id="tcPhpTriggerBox" style="border-color:#7c3aed;">
                                <input class="form-check-input" type="radio" id="tcPhpTrigger" name="_php_trigger" value="1"
                                       style="accent-color:#7c3aed;">
                                <label class="form-check-label" for="tcPhpTrigger" style="color:#7c3aed;font-weight:700;">
                                    <i class="bi bi-building-fill me-1"></i> PHP
                                    <i class="bi bi-chevron-down ms-1" id="tcPhpChevron" style="font-size:.75rem;"></i>
                                </label>
                            </div>
                        </div>

                        <!-- ══ SERVICE DE DESTINATION (sauf PHP / femme enceinte) ══════════ -->
                        <div id="blocServiceDestNvx" class="mt-3 p-3 rounded-3"
                             style="background:#f0fdf4;border:1.5px solid #86efac;">
                            <label class="form-label small fw-bold" style="color:#15803d;">
                                <i class="bi bi-hospital me-1"></i>SERVICE DE DESTINATION
                                <span class="badge ms-1" style="background:#e0f2fe;color:#0369a1;font-size:.65rem;font-weight:700;">OPTIONNEL</span>
                            </label>
                            <select name="service_id_destination" id="serviceIdDestNvx"
                                    class="form-select input-custom">
                                <option value="">— Parcours standard (bureau paramètres B1/B2) —</option>
                                <?php
                                try {
                                    $dbSvcDest = (new Database())->getConnection();
                                    $svcsDest  = $dbSvcDest->query(
                                        "SELECT id, nom_service FROM services
                                          WHERE nom_service NOT LIKE '%Accueil%'
                                            AND nom_service NOT LIKE '%Param%'
                                            AND nom_service NOT LIKE '%Administ%'
                                            AND nom_service NOT LIKE '%Laboratoire%'
                                            AND nom_service NOT LIKE '%Imagerie%'
                                            AND nom_service NOT LIKE '%Banque%'
                                          ORDER BY nom_service ASC"
                                    )->fetchAll(PDO::FETCH_ASSOC);
                                    foreach ($svcsDest as $sv):
                                ?>
                                <option value="<?= $sv['id'] ?>"><?= htmlspecialchars($sv['nom_service']) ?></option>
                                <?php endforeach; } catch (\Throwable $e) {} ?>
                            </select>
                            <div class="mt-2 d-flex align-items-start gap-2">
                                <i class="bi bi-info-circle-fill mt-1"
                                   style="color:#15803d;font-size:.8rem;flex-shrink:0;"></i>
                                <small class="text-muted" style="font-size:.78rem;">
                                    Le patient entre <strong>directement</strong> dans la file du service.
                                    Les constantes vitales seront saisies par le médecin pendant la consultation.
                                </small>
                            </div>
                        </div>

                        <!-- Sous-choix PHP -->
                        <div id="blocPhpChoix" style="display:none;" class="mt-2 p-3 rounded-3 border"
                             style="border-color:#ede9fe;background:#f5f3ff;">
                            <small class="fw-bold d-block mb-2" style="color:#7c3aed;">
                                <i class="bi bi-info-circle me-1"></i> Précisez la catégorie PHP :
                            </small>
                            <div class="d-flex gap-3 flex-wrap">
                                <div class="form-check type-client-option" style="border-color:#7c3aed;">
                                    <input class="form-check-input" type="radio" name="type_client" id="tcFamillePhp" value="FAMILLE_PHP"
                                           style="accent-color:#7c3aed;">
                                    <label class="form-check-label fw-bold" for="tcFamillePhp" style="color:#7c3aed;">
                                        <i class="bi bi-house-heart-fill me-1"></i> Famille PHP
                                    </label>
                                </div>
                                <div class="form-check type-client-option" style="border-color:#7c3aed;">
                                    <input class="form-check-input" type="radio" name="type_client" id="tcAgentsPhp" value="AGENTS_PHP"
                                           style="accent-color:#7c3aed;" onchange="toggleAgentPhpFields()">
                                    <label class="form-check-label fw-bold" for="tcAgentsPhp" style="color:#7c3aed;">
                                        <i class="bi bi-person-badge-fill me-1"></i> Agent PHP
                                    </label>
                                </div>
                            </div>
                            <!-- Champs Agent PHP -->
                            <div id="blocAgentPhp" style="display:none;" class="mt-3 p-3 rounded-3"
                                 style="background:#f5f3ff;border:1.5px solid #ddd6fe;">
                                <small class="d-block fw-bold mb-2" style="color:#7c3aed;">
                                    <i class="bi bi-person-vcard me-1"></i> Informations Agent PHP
                                </small>
                                <div class="row g-2">
                                    <div class="col-md-6">
                                        <label class="form-label small fw-bold text-secondary">MATRICULE <span class="text-danger">*</span></label>
                                        <input type="text" name="matricule_agent" id="matricule_agent"
                                               class="form-control input-custom" placeholder="ex: PHP-2024-0123"
                                               style="border-color:#c4b5fd!important;">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-bold text-secondary">INFIRMERIE D'ORIGINE</label>
                                        <input type="text" name="infirmerie_origine" id="infirmerie_origine"
                                               class="form-control input-custom" placeholder="ex: Infirmerie de Njombé"
                                               style="border-color:#c4b5fd!important;">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Champs assurance -->
                        <div id="blocAssurance" style="display:none;" class="mt-3">
                            <div class="row g-3 p-3 rounded-3 border border-info-subtle bg-info bg-opacity-10">
                                <div class="col-12">
                                    <div style="font-size:.75rem;font-weight:700;color:#0891b2;margin-bottom:6px;">
                                        <i class="bi bi-shield-check me-1"></i>Informations d'assurance (obligatoires)
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-secondary">NOM DE L'ASSURANCE <span class="text-danger">*</span></label>
                                    <input type="text" name="nom_assurance" id="nom_assurance" class="form-control input-custom"
                                           placeholder="ex: CNPS, Allianz, Sanlam...">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-secondary">NUMÉRO DE L'ASSURÉ <span class="text-danger">*</span></label>
                                    <input type="text" name="numero_assure" id="numero_assure" class="form-control input-custom"
                                           placeholder="ex: ASS-2024-00123">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-secondary">ORGANISME PAYEUR</label>
                                    <input type="text" name="organisme_payeur" id="organisme_payeur_assur" class="form-control input-custom"
                                           placeholder="Même que l'assurance si non précisé">
                                </div>
                            </div>
                        </div>

                        <!-- Champs bon de prise en charge -->
                        <div id="blocBon" style="display:none;" class="mt-3">
                            <div class="row g-3 p-3 rounded-3"
                                 style="border:1.5px solid #93c5fd;background:#eff6ff;">
                                <div class="col-12">
                                    <div style="font-size:.75rem;font-weight:700;color:#1d4ed8;margin-bottom:6px;">
                                        <i class="bi bi-file-medical me-1"></i>Bon de prise en charge (obligatoire)
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-secondary">N° DU BON <span class="text-danger">*</span></label>
                                    <input type="text" name="numero_bon" id="numero_bon" class="form-control input-custom"
                                           placeholder="ex: BON-2026-00123">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-secondary">ORGANISME ÉMETTEUR <span class="text-danger">*</span></label>
                                    <input type="text" name="organisme_payeur" id="organisme_payeur_bon" class="form-control input-custom"
                                           placeholder="ex: Ministère de la Santé, ONG...">
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- Footer wizard navigation -->
                <div class="modal-footer border-0 p-4" style="background: #f8fafc;">
                    <button type="button" class="btn btn-link text-muted fw-bold text-decoration-none" data-bs-dismiss="modal">Fermer</button>
                    <div class="d-flex gap-2 ms-auto">
                        <button type="button" id="wzBtnPrev" class="btn btn-outline-secondary rounded-pill px-4"
                                style="display:none;" onclick="wizardPrev()">
                            <i class="bi bi-arrow-left me-1"></i> Précédent
                        </button>
                        <button type="button" id="wzBtnNext" class="btn btn-primary rounded-pill px-4 fw-bold"
                                onclick="wizardNext()">
                            Suivant <i class="bi bi-arrow-right ms-1"></i>
                        </button>
                        <button type="submit" id="wzBtnSubmit"
                                class="btn btn-primary px-5 py-2 rounded-pill shadow fw-bold"
                                style="display:none;">
                            <i class="bi bi-check2-circle me-1"></i> ENREGISTRER &amp; LANCER
                        </button>
                    </div>
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
    .fw-600 { font-weight: 600; }
    .type-client-option { background: #f8fafc; border: 2px solid #e2e8f0; border-radius: 10px; padding: 8px 14px; transition: all .15s; cursor: pointer; }
    .type-client-option:has(.form-check-input:checked) { border-color: #3b82f6; background: #eff6ff; }
    .type-client-option label { cursor: pointer; font-size: .88rem; font-weight: 600; }
    /* ── Wizard Stepper ────────────────────────────────────────────── */
    .wz-stepper { position: relative; }
    .wz-step-ind { display:flex; flex-direction:column; align-items:center; position:relative; z-index:1; flex-shrink:0; }
    .wz-circle { width:36px; height:36px; border-radius:50%; border:2.5px solid #e2e8f0; background:#fff;
                 display:flex; align-items:center; justify-content:center; font-weight:700; font-size:.85rem;
                 color:#94a3b8; transition:all .3s ease; }
    .wz-step-ind.wz-active .wz-circle { border-color:#3b82f6; background:#3b82f6; color:#fff; box-shadow:0 0 0 4px rgba(59,130,246,.15); }
    .wz-step-ind.wz-done .wz-circle { border-color:#10b981; background:#10b981; color:#fff; }
    .wz-step-label { font-size:.7rem; font-weight:600; color:#94a3b8; margin-top:5px; white-space:nowrap; text-align:center; }
    .wz-step-ind.wz-active .wz-step-label { color:#3b82f6; }
    .wz-step-ind.wz-done .wz-step-label { color:#10b981; }
    .wz-line { height:2.5px; background:#e2e8f0; flex-grow:1; margin:0 6px; margin-bottom:20px; border-radius:2px; transition:background .3s; }
    .wz-step-pane { animation: wzFadeIn .22s ease; }
    @keyframes wzFadeIn { from { opacity:0; transform:translateX(10px); } to { opacity:1; transform:translateX(0); } }
    /* ── Cartes OUI / NON (femme enceinte) ── */
    .fe-card { position: relative; }
    .fe-card-inner {
        border: 2px solid #e5e7eb;
        background: #fff;
        transition: border-color .18s, background .18s, box-shadow .18s;
    }
    .fe-card-inner:hover { border-color: #f9a8d4; background: #fff7f9; }
    .fe-card.fe-selected-oui .fe-card-inner {
        border-color: #db2777; background: #fdf2f8;
        box-shadow: 0 0 0 3px rgba(219,39,119,.12);
    }
    .fe-card.fe-selected-non .fe-card-inner {
        border-color: #6b7280; background: #f9fafb;
        box-shadow: 0 0 0 3px rgba(107,114,128,.12);
    }
    #blocFemmeEnceinte .fe-card-inner.is-invalid-fe { border-color: #dc2626 !important; }
</style>
<script>
(function () {

    // ══ RECHERCHE DOUBLON ═══════════════════════════════════════════════════════
    let doublonTimer = null;

    function lancerRechercheDoublon() {
        clearTimeout(doublonTimer);
        doublonTimer = setTimeout(function() {
            const nom = document.getElementById('dbNom').value.trim();
            const pre = document.getElementById('dbPrenom').value.trim();
            const tel = document.getElementById('dbTel').value.trim();
            const box = document.getElementById('doublonResults');

            if (nom.length < 2 && tel.length < 6) { box.style.display = 'none'; return; }

            box.style.display = 'block';
            box.innerHTML = '<div class="text-muted small py-1"><span class="spinner-border spinner-border-sm me-2"></span>Recherche…</div>';

            const params = new URLSearchParams({ nom, prenom: pre, telephone: tel });
            fetch('<?= BASE_URL ?>accueil/search-doublon?' + params)
                .then(r => r.json())
                .then(data => {
                    if (!data.length) {
                        box.innerHTML = '<div class="d-flex align-items-center gap-2 text-success small py-1"><i class="bi bi-check-circle-fill"></i> Aucun doublon trouvé — vous pouvez créer le dossier.</div>';
                        return;
                    }

                    const calcAge = dob => {
                        if (!dob) return '';
                        const d = new Date(dob);
                        const age = Math.floor((Date.now() - d) / 3.15576e10);
                        return age + ' ans';
                    };

                    const statutColors = {
                        'ACCUEIL':'secondary','PARAMETRES':'info','ATTENTE_CONSULTATION':'warning',
                        'EN_CONSULTATION':'success','HOSPITALISE':'danger','SORTI':'dark'
                    };

                    const rows = data.map(p => `
                        <div class="d-flex align-items-center justify-content-between p-2 rounded-3 mb-1"
                             style="background:#fff;border:1.5px solid #fbbf24;">
                            <div class="d-flex align-items-center gap-3">
                                <div class="rounded-circle bg-warning bg-opacity-15 d-flex align-items-center justify-content-center fw-bold text-warning"
                                     style="width:38px;height:38px;font-size:.85rem;flex-shrink:0;">
                                    ${p.nom.charAt(0)}${p.prenom.charAt(0)}
                                </div>
                                <div>
                                    <div class="fw-bold" style="font-size:.85rem;">${p.nom} ${p.prenom}</div>
                                    <div class="text-muted" style="font-size:.75rem;">
                                        <span class="badge bg-secondary rounded-pill me-1">${p.dossier_numero}</span>
                                        ${calcAge(p.date_naissance)}
                                        ${p.telephone ? '· 📞 ' + p.telephone : ''}
                                        · ${p.nb_consultations} consultation(s)
                                    </div>
                                </div>
                            </div>
                            <a href="<?= BASE_URL ?>patients/dossier/${p.id}"
                               class="btn btn-sm btn-warning fw-bold rounded-pill px-3" target="_blank">
                               <i class="bi bi-folder2-open me-1"></i>Ouvrir
                            </a>
                        </div>`).join('');

                    box.innerHTML = `
                        <div class="rounded-3 p-2" style="background:#fffbeb;border:1.5px solid #fde68a;">
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <i class="bi bi-exclamation-triangle-fill text-warning"></i>
                                <span class="fw-bold small text-warning">
                                    ${data.length} patient(s) similaire(s) trouvé(s) — vérifiez avant de créer un nouveau dossier
                                </span>
                            </div>
                            ${rows}
                        </div>`;
                })
                .catch(() => { box.style.display = 'none'; });
        }, 500);
    }

    ['dbNom','dbPrenom','dbTel'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.addEventListener('input', lancerRechercheDoublon);
    });

    // ── Sélecteur service destination ──────────────────────────────────────────
    function updateServiceDestUI() {
        const tc      = document.querySelector('#formNouveauPatient input[name="type_client"]:checked')?.value ?? '';
        const isPhp   = tc === 'FAMILLE_PHP' || tc === 'AGENTS_PHP';
        const isFe    = document.getElementById('npFemmeEnceinte_oui')?.checked;
        const bloc    = document.getElementById('blocServiceDestNvx');
        const sel     = document.getElementById('serviceIdDestNvx');
        const hidden  = !isPhp && !isFe;
        if (bloc) bloc.style.display = hidden ? 'block' : 'none';
        if (sel)  sel.required = hidden;
    }

    // Auto-sélection service pédiatrie quand âge ≤ 15
    // (exposée sur window car appelée depuis des handlers globaux/inline hors de cette IIFE)
    window.npAutoSelectServiceForAge = function(age) {
        const sel = document.getElementById('serviceIdDestNvx');
        if (!sel || age === null || age === '') return;
        const ageInt = parseInt(age, 10);
        if (isNaN(ageInt) || ageInt > 15 || ageInt < 0) return;
        // Chercher l'option contenant "pédiatr" (insensible à la casse)
        for (const opt of sel.options) {
            if (/p[eé]diatr/i.test(opt.text)) {
                sel.value = opt.value;
                return;
            }
        }
    };

    // ── Affichage conditionnel Assurance / PHP ─────────────────────────────────
    function updateTypeClientUI() {
        const tc       = document.querySelector('#formNouveauPatient input[name="type_client"]:checked')?.value ?? '';
        const isAssur  = tc === 'ASSURANCE';
        const isBon    = tc === 'BON_PRISE_EN_CHARGE';
        const isPhp    = tc === 'FAMILLE_PHP' || tc === 'AGENTS_PHP';
        const phpTrig  = document.getElementById('tcPhpTrigger');

        // Assurance
        const blocAssur = document.getElementById('blocAssurance');
        if (blocAssur) {
            blocAssur.style.display = isAssur ? 'block' : 'none';
            document.getElementById('nom_assurance').required   = isAssur;
            document.getElementById('numero_assure').required   = isAssur;
        }

        // Bon de prise en charge
        const blocBon = document.getElementById('blocBon');
        if (blocBon) {
            blocBon.style.display = isBon ? 'block' : 'none';
            const nb = document.getElementById('numero_bon');
            const op = document.getElementById('organisme_payeur_bon');
            if (nb) nb.required = isBon;
            if (op) op.required = isBon;
        }

        // PHP sub-panel
        const blocPhp = document.getElementById('blocPhpChoix');
        if (blocPhp) {
            blocPhp.style.display = isPhp ? 'block' : 'none';
        }

        // Synchroniser le trigger PHP (visuel coché si PHP actif)
        if (phpTrig) phpTrig.checked = isPhp;

        // Chevron
        const chev = document.getElementById('tcPhpChevron');
        if (chev) chev.className = isPhp ? 'bi bi-chevron-up ms-1' : 'bi bi-chevron-down ms-1';

        // Mettre à jour le sélecteur de service
        updateServiceDestUI();
    }

    // Afficher/masquer les champs spécifiques Agent PHP
    window.toggleAgentPhpFields = function() {
        const isAgent = document.getElementById('tcAgentsPhp')?.checked;
        const bloc = document.getElementById('blocAgentPhp');
        if (bloc) bloc.style.display = isAgent ? 'block' : 'none';
        const mat = document.getElementById('matricule_agent');
        if (mat) mat.required = isAgent;
    };

    // Quand Famille PHP est sélectionné → masquer les champs Agent
    document.getElementById('tcFamillePhp')?.addEventListener('change', function() {
        if (this.checked) {
            const bloc = document.getElementById('blocAgentPhp');
            if (bloc) bloc.style.display = 'none';
            const mat = document.getElementById('matricule_agent');
            if (mat) mat.required = false;
        }
    });

    // Quand le trigger PHP est cliqué → montrer le sous-panel et pré-cocher Famille PHP
    const phpTrig = document.getElementById('tcPhpTrigger');
    if (phpTrig) {
        phpTrig.addEventListener('change', function () {
            if (this.checked) {
                // Décoche les autres type_client, coche FAMILLE_PHP par défaut
                document.querySelectorAll('#formNouveauPatient input[name="type_client"]').forEach(r => r.checked = false);
                const famPhp = document.getElementById('tcFamillePhp');
                if (famPhp) { famPhp.checked = true; }
                updateTypeClientUI();
            }
        });
    }

    // Quand on sélectionne un type_client normal (non-PHP) → cacher le panel PHP
    document.querySelectorAll('#formNouveauPatient input[name="type_client"]').forEach(function (radio) {
        radio.addEventListener('change', function () {
            const isPhp = this.value === 'FAMILLE_PHP' || this.value === 'AGENTS_PHP';
            if (!isPhp) {
                // Décoche le trigger PHP
                const pt = document.getElementById('tcPhpTrigger');
                if (pt) pt.checked = false;
            }
            updateTypeClientUI();
        });
    });

    // Validation : empêcher soumission sans sous-choix PHP explicite
    // + Vérification doublon côté serveur avant soumission
    document.getElementById('formNouveauPatient')?.addEventListener('submit', async function (e) {
        // 1. Vérification PHP
        const phpTrigChecked = document.getElementById('tcPhpTrigger')?.checked;
        const tcVal = document.querySelector('#formNouveauPatient input[name="type_client"]:checked')?.value;
        if (phpTrigChecked && (!tcVal || (tcVal !== 'FAMILLE_PHP' && tcVal !== 'AGENTS_PHP'))) {
            e.preventDefault();
            document.getElementById('blocPhpChoix').style.display = 'block';
            document.getElementById('blocPhpChoix').scrollIntoView({ behavior: 'smooth' });
            alert('Veuillez choisir : Famille PHP ou Agent PHP');
            return;
        }

        // 2. Vérification doublon (sauf si déjà confirmé)
        const skipCheck = document.getElementById('_skip_doublon_check');
        if (skipCheck && skipCheck.value === '1') return; // déjà confirmé → laisser passer

        const nom  = (document.querySelector('#formNouveauPatient input[name="nom"]')?.value || '').trim();
        const pre  = (document.querySelector('#formNouveauPatient input[name="prenom"]')?.value || '').trim();
        const ddn  = (document.querySelector('#formNouveauPatient input[name="date_naissance"]')?.value || '').trim();
        const tel  = (document.querySelector('#formNouveauPatient input[name="telephone"]')?.value || '').trim();

        if (nom.length >= 2) {
            e.preventDefault(); // On stoppe le submit le temps du check AJAX

            try {
                const params = new URLSearchParams({ nom, prenom: pre, telephone: tel, date_naissance: ddn });
                const r = await fetch('<?= BASE_URL ?>accueil/check-doublon-final?' + params);
                const data = await r.json();

                if (data.found && data.patients && data.patients.length > 0) {
                    // Afficher le modal de confirmation doublon
                    afficherModalDoublon(data.patients, nom, pre);
                } else {
                    // Pas de doublon → soumettre
                    if (skipCheck) skipCheck.value = '1';
                    this.submit();
                }
            } catch (err) {
                // En cas d'erreur réseau → laisser passer (ne pas bloquer l'accueil)
                if (skipCheck) skipCheck.value = '1';
                this.submit();
            }
        }
    });

    // Init et reset à la fermeture de la modale
    const modal = document.getElementById('modalNouveauPatient');
    if (modal) {
        modal.addEventListener('show.bs.modal', function() {
            updateTypeClientUI();
            updateServiceDestUI();
            if (window.wizardGoTo) wizardGoTo(1);
        });
        modal.addEventListener('hidden.bs.modal', function () {
            document.getElementById('blocAssurance').style.display = 'none';
            document.getElementById('blocBon').style.display       = 'none';
            document.getElementById('blocPhpChoix').style.display  = 'none';
            document.getElementById('nom_assurance').required  = false;
            document.getElementById('numero_assure').required  = false;
            const nbr = document.getElementById('numero_bon');
            if (nbr) nbr.required = false;
            const pt = document.getElementById('tcPhpTrigger');
            if (pt) pt.checked = false;
            const def = document.getElementById('tcPayant');
            if (def) def.checked = true;
            npSetDobMode('date');
            if (window.wizardGoTo) wizardGoTo(1);
        });
    }
})();

// ── Wizard Navigation ──────────────────────────────────────────────────────
let _wzCurrentStep = 1;
const _WZ_TOTAL    = 3;

window.wizardGoTo = function(step) {
    if (step > _wzCurrentStep && !_wzValidateStep(_wzCurrentStep)) return;
    if (step < 1 || step > _WZ_TOTAL) return;

    for (let i = 1; i <= _WZ_TOTAL; i++) {
        const pane = document.getElementById('wz-step-' + i);
        const ind  = document.getElementById('wz-indicator-' + i);
        if (pane) pane.style.display = (i === step) ? 'block' : 'none';
        if (ind)  {
            ind.classList.remove('wz-active', 'wz-done');
            if (i < step)  ind.classList.add('wz-done');
            if (i === step) ind.classList.add('wz-active');
        }
    }
    // Update connector lines
    document.querySelectorAll('#modalNouveauPatient .wz-line').forEach((ln, idx) => {
        ln.style.background = (idx + 1 < step) ? '#10b981' : '#e2e8f0';
    });

    _wzCurrentStep = step;

    const prev   = document.getElementById('wzBtnPrev');
    const next   = document.getElementById('wzBtnNext');
    const submit = document.getElementById('wzBtnSubmit');
    if (prev)   prev.style.display   = step > 1 ? '' : 'none';
    if (next)   next.style.display   = step < _WZ_TOTAL ? '' : 'none';
    if (submit) submit.style.display = step === _WZ_TOTAL ? '' : 'none';
};

window.wizardNext = function() { wizardGoTo(_wzCurrentStep + 1); };
window.wizardPrev = function() { wizardGoTo(_wzCurrentStep - 1); };

function _wzValidateStep(step) {
    if (step === 1) {
        const nom = document.querySelector('#formNouveauPatient input[name="nom"]');
        const pre = document.querySelector('#formNouveauPatient input[name="prenom"]');
        let ok = true;
        [nom, pre].forEach(el => {
            if (el && !el.value.trim()) { el.classList.add('is-invalid'); if(ok) el.focus(); ok = false; }
            else if (el) el.classList.remove('is-invalid');
        });
        if (!ok) return false;
        const ageVisible = document.getElementById('npFieldAge')?.style.display !== 'none';
        if (ageVisible) {
            const age = document.getElementById('npInputAge');
            if (age && !age.value) { age.classList.add('is-invalid'); age.focus(); return false; }
            if (age) age.classList.remove('is-invalid');
        } else {
            const ddn = document.getElementById('npInputDdn');
            if (ddn && !ddn.value) { ddn.classList.add('is-invalid'); ddn.focus(); return false; }
            if (ddn) ddn.classList.remove('is-invalid');
        }
        // Vérification obligatoire : femme enceinte ? (si sexe = F)
        if (document.getElementById('sexeF')?.checked) {
            const ouiChecked = document.getElementById('npFemmeEnceinte_oui')?.checked;
            const nonChecked = document.getElementById('npFemmeEnceinte_non')?.checked;
            if (!ouiChecked && !nonChecked) {
                const err = document.getElementById('feErreur');
                if (err) err.style.display = 'block';
                document.querySelectorAll('#blocFemmeEnceinte .fe-card-inner').forEach(el => el.classList.add('is-invalid-fe'));
                document.getElementById('blocFemmeEnceinte')?.scrollIntoView({ behavior: 'smooth', block: 'center' });
                return false;
            }
        }
        return true;
    }
    if (step === 2) {
        const tel = document.querySelector('#formNouveauPatient input[name="telephone"]');
        if (tel && !tel.value.trim()) { tel.classList.add('is-invalid'); tel.focus(); return false; }
        if (tel) tel.classList.remove('is-invalid');
        return true;
    }
    if (step === 3) {
        // Le service de destination est optionnel :
        //   - renseigné  → routage direct vers ce service (ATTENTE_CONSULTATION)
        //   - vide       → parcours standard via bureau paramètres B1/B2 (parité pair/impair)
        const selSvc = document.getElementById('serviceIdDestNvx');
        if (selSvc) selSvc.classList.remove('is-invalid');
        return true;
    }
    return true;
}

// ── Bascule Date / Âge estimatif ──────────────────────────────────────────
function npSetDobMode(mode) {
    const isDate    = (mode === 'date');
    const fieldDate = document.getElementById('npFieldDate');
    const fieldAge  = document.getElementById('npFieldAge');
    const inputDdn  = document.getElementById('npInputDdn');
    const inputAge  = document.getElementById('npInputAge');
    const hiddenDdn = document.getElementById('npHiddenDdn');
    const btnDate   = document.getElementById('npBtnDate');
    const btnAge    = document.getElementById('npBtnAge');

    fieldDate.style.display = isDate ? '' : 'none';
    fieldAge.style.display  = isDate ? 'none' : '';

    // Styles boutons
    btnDate.className = 'px-3 py-1 border-0 fw-bold ' + (isDate  ? 'bg-primary text-white' : 'bg-white text-muted');
    btnAge.className  = 'px-3 py-1 border-0 fw-bold ' + (!isDate ? 'bg-primary text-white' : 'bg-white text-muted');

    if (isDate) {
        inputDdn.name     = 'date_naissance';
        inputDdn.required = true;
        if (hiddenDdn) hiddenDdn.name = '';
        if (inputAge)  { inputAge.value = ''; inputAge.required = false; }
        document.getElementById('npDobEstimate').textContent = '';
    } else {
        inputDdn.name     = '';
        inputDdn.required = false;
        inputDdn.value    = '';
        if (hiddenDdn) hiddenDdn.name = 'date_naissance';
        if (inputAge)  inputAge.required = true;
    }
}

// Afficher/masquer le bloc "Patiente enceinte ?" selon le sexe sélectionné
window.npToggleFemmeEnceinte = function() {
    const isFemme = document.getElementById('sexeF')?.checked;
    const bloc    = document.getElementById('blocFemmeEnceinte');
    if (bloc) bloc.style.display = isFemme ? 'block' : 'none';
    if (!isFemme) {
        // Réinitialiser la sélection si on repasse à Masculin
        const oui = document.getElementById('npFemmeEnceinte_oui');
        const non = document.getElementById('npFemmeEnceinte_non');
        if (oui) oui.checked = false;
        if (non) non.checked = false;
        document.getElementById('feOuiCard')?.classList.remove('fe-selected-oui');
        document.getElementById('feNonCard')?.classList.remove('fe-selected-non');
        document.getElementById('feErreur')?.style && (document.getElementById('feErreur').style.display = 'none');
        document.querySelectorAll('#blocFemmeEnceinte .fe-card-inner').forEach(el => el.classList.remove('is-invalid-fe'));
    }
};

// Mettre à jour l'apparence visuelle quand OUI ou NON est cliqué
window.npOnFemmeEnceinte = function() {
    const ouiChecked = document.getElementById('npFemmeEnceinte_oui')?.checked;
    const ouiCard    = document.getElementById('feOuiCard');
    const nonCard    = document.getElementById('feNonCard');
    if (ouiCard) ouiCard.classList.toggle('fe-selected-oui', !!ouiChecked);
    if (nonCard) nonCard.classList.toggle('fe-selected-non', !ouiChecked);
    // Effacer le message d'erreur dès qu'une option est choisie
    const err = document.getElementById('feErreur');
    if (err) err.style.display = 'none';
    document.querySelectorAll('#blocFemmeEnceinte .fe-card-inner').forEach(el => el.classList.remove('is-invalid-fe'));
    // Masquer le service selector si enceinte = OUI
    updateServiceDestUI();
};

function npEstimateDob(val) {
    const hiddenDdn = document.getElementById('npHiddenDdn');
    const lbl       = document.getElementById('npDobEstimate');
    const age = parseInt(val, 10);
    if (!isNaN(age) && age > 0 && age <= 120) {
        const year = new Date().getFullYear() - age;
        hiddenDdn.value = year + '-01-01';
        lbl.innerHTML = '<i class="bi bi-info-circle me-1"></i>Date retenue : <strong>01/01/' + year + '</strong>';
        npAutoSelectServiceForAge(age);
    } else {
        hiddenDdn.value = '';
        lbl.textContent = '';
    }
}

// Auto-sélection service quand la date de naissance (champ date) change
(function() {
    const ddnInput = document.getElementById('npInputDdn');
    if (ddnInput) {
        ddnInput.addEventListener('change', function() {
            if (!this.value) return;
            const dob = new Date(this.value);
            const ageCalc = Math.floor((Date.now() - dob) / 3.15576e10);
            npAutoSelectServiceForAge(ageCalc);
        });
    }
})();

/* ── Gestion doublon avant enregistrement ──────────────────────────────── */
function afficherModalDoublon(patients, nom, prenom) {
    let rows = '';
    patients.forEach(p => {
        const age = p.date_naissance
            ? (new Date().getFullYear() - new Date(p.date_naissance).getFullYear()) + ' ans'
            : '—';
        rows += `<tr>
            <td class="fw-700 text-primary">${escHtml(p.dossier_numero)}</td>
            <td>${escHtml(p.nom)} ${escHtml(p.prenom)}</td>
            <td>${age}</td>
            <td>${escHtml(p.telephone || '—')}</td>
            <td><span class="badge bg-primary-subtle text-primary rounded-pill">${p.nb_consultations} consult.</span></td>
            <td>
                <a href="<?= BASE_URL ?>patients/dossier/${p.id}" target="_blank"
                   class="btn btn-sm btn-outline-primary rounded-pill px-2" style="font-size:.75rem;">
                    Ouvrir le dossier
                </a>
            </td>
        </tr>`;
    });

    // Injecter le contenu
    document.getElementById('doublonNomPrenom').textContent = nom + ' ' + prenom;
    document.getElementById('doublonTableBody').innerHTML = rows;

    // Afficher le modal
    const m = new bootstrap.Modal(document.getElementById('modalConfirmDoublon'));
    m.show();
}

function forcerEnregistrement() {
    bootstrap.Modal.getInstance(document.getElementById('modalConfirmDoublon'))?.hide();
    document.getElementById('_skip_doublon_check').value = '1';
    document.getElementById('formNouveauPatient').submit();
}

function escHtml(s) {
    if (!s) return '';
    return String(s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[c]));
}
</script>

<!-- ══ MODAL CONFIRMATION DOUBLON ══════════════════════════════════════════ -->
<div class="modal fade" id="modalConfirmDoublon" tabindex="-1" data-bs-backdrop="static" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content border-0 shadow-lg rounded-4">
      <div class="modal-header border-0" style="background:linear-gradient(135deg,#b45309,#d97706);border-radius:16px 16px 0 0;">
        <div class="d-flex align-items-center gap-2">
          <div style="width:40px;height:40px;background:rgba(255,255,255,.2);border-radius:12px;display:flex;align-items:center;justify-content:center;">
            <i class="bi bi-exclamation-triangle-fill text-white fs-5"></i>
          </div>
          <div>
            <h5 class="text-white fw-bold mb-0" style="font-size:.95rem;">Patient déjà enregistré ?</h5>
            <p class="text-white opacity-75 mb-0" style="font-size:.78rem;">
              Un ou plusieurs dossiers similaires ont été trouvés pour
              <strong id="doublonNomPrenom"></strong>
            </p>
          </div>
        </div>
        <button type="button" class="btn-close btn-close-white ms-auto" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-4">
        <div class="alert alert-warning border-0 rounded-3 mb-3" style="font-size:.85rem;">
          <i class="bi bi-info-circle-fill me-2"></i>
          Vérifiez si l'un de ces dossiers correspond au patient que vous souhaitez enregistrer.
          Si c'est le cas, ouvrez le dossier existant au lieu d'en créer un nouveau.
        </div>
        <div class="table-responsive">
          <table class="table table-sm table-hover align-middle">
            <thead style="background:#f8fafc;font-size:.72rem;text-transform:uppercase;letter-spacing:.5px;color:#64748b;">
              <tr>
                <th class="py-2 ps-2">N° Dossier</th>
                <th>Nom Prénom</th>
                <th>Âge</th>
                <th>Téléphone</th>
                <th>Consultations</th>
                <th></th>
              </tr>
            </thead>
            <tbody id="doublonTableBody"></tbody>
          </table>
        </div>
      </div>
      <div class="modal-footer border-0 d-flex justify-content-between">
        <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">
          <i class="bi bi-x-circle me-1"></i> Annuler
        </button>
        <button type="button" class="btn btn-warning rounded-pill px-4 fw-bold"
                onclick="forcerEnregistrement()">
          <i class="bi bi-person-plus-fill me-2"></i>
          C'est un nouveau patient — Enregistrer quand même
        </button>
      </div>
    </div>
  </div>
</div>

<!-- =====================================================================
     MODALE MODIFICATION PATIENT — édition complète par la secrétaire
     ===================================================================== -->
<div class="modal fade" id="modalModifierPatient" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg" style="border-radius:24px;overflow:hidden;">

            <!-- Header -->
            <div class="modal-header border-0 p-4"
                 style="background:linear-gradient(135deg,#eff6ff 0%,#dbeafe 100%);">
                <div class="d-flex align-items-center gap-3 w-100">
                    <div class="rounded-circle p-3 flex-shrink-0"
                         style="background:rgba(37,99,235,.12);">
                        <i class="bi bi-pencil-square fs-4" style="color:#2563eb;"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h5 class="modal-title fw-bold text-dark mb-0" id="editPatientTitre">Modifier le dossier</h5>
                        <div class="d-flex align-items-center gap-2 mt-1 flex-wrap">
                            <span class="badge rounded-pill px-3"
                                  style="background:#2563eb;color:white;" id="editDossierBadge">—</span>
                            <small class="text-muted" id="editPatientMeta"></small>
                        </div>
                    </div>
                    <button type="button" class="btn-close flex-shrink-0" data-bs-dismiss="modal"></button>
                </div>
            </div>

            <!-- Loader -->
            <div id="editPatientLoader" class="text-center py-5 d-none">
                <div class="spinner-border" style="color:#2563eb;" role="status"></div>
                <div class="text-muted mt-2 small">Chargement des données…</div>
            </div>

            <div id="editPatientBody" class="d-none">
                <div class="modal-body p-4">

                    <!-- Onglets -->
                    <ul class="nav nav-pills mb-4 p-1 rounded-3" style="background:#f1f5f9;" role="tablist">
                        <li class="nav-item flex-fill" role="presentation">
                            <button class="nav-link active w-100 rounded-3 fw-600 small"
                                    data-bs-toggle="pill" data-bs-target="#editTabCivil" type="button">
                                <i class="bi bi-person-vcard me-1"></i> Identité
                            </button>
                        </li>
                        <li class="nav-item flex-fill" role="presentation">
                            <button class="nav-link w-100 rounded-3 fw-600 small"
                                    data-bs-toggle="pill" data-bs-target="#editTabContact" type="button">
                                <i class="bi bi-telephone me-1"></i> Contact
                            </button>
                        </li>
                        <li class="nav-item flex-fill" role="presentation">
                            <button class="nav-link w-100 rounded-3 fw-600 small"
                                    data-bs-toggle="pill" data-bs-target="#editTabUrgence" type="button">
                                <i class="bi bi-shield-exclamation me-1"></i> Urgence
                            </button>
                        </li>
                        <li class="nav-item flex-fill" role="presentation">
                            <button class="nav-link w-100 rounded-3 fw-600 small"
                                    data-bs-toggle="pill" data-bs-target="#editTabFacturation" type="button">
                                <i class="bi bi-cash-coin me-1"></i> Facturation
                            </button>
                        </li>
                    </ul>

                    <form id="formModifierPatient">
                    <input type="hidden" name="patient_id" id="editPatientId">

                    <div class="tab-content">

                        <!-- ── TAB 1 : IDENTITÉ ────────────────────────────── -->
                        <div class="tab-pane fade show active" id="editTabCivil">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-secondary">NOM <span class="text-danger">*</span></label>
                                    <input type="text" name="nom" id="editNom"
                                           class="form-control" style="border-radius:10px;"
                                           placeholder="NOM en majuscules" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-secondary">PRÉNOM <span class="text-danger">*</span></label>
                                    <input type="text" name="prenom" id="editPrenom"
                                           class="form-control" style="border-radius:10px;" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-bold text-secondary">DATE DE NAISSANCE</label>
                                    <input type="date" name="date_naissance" id="editDateNaissance"
                                           class="form-control" style="border-radius:10px;">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-bold text-secondary">SEXE</label>
                                    <div class="d-flex gap-3 mt-1">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="sexe" id="editSexeM" value="M"
                                                   onchange="editToggleFemmeEnceinte()">
                                            <label class="form-check-label fw-semibold" for="editSexeM">
                                                <i class="bi bi-gender-male text-primary me-1"></i>Masculin
                                            </label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="sexe" id="editSexeF" value="F"
                                                   onchange="editToggleFemmeEnceinte()">
                                            <label class="form-check-label fw-semibold" for="editSexeF">
                                                <i class="bi bi-gender-female text-danger me-1"></i>Féminin
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-bold text-secondary">GROUPE SANGUIN</label>
                                    <select name="groupe_sanguin" id="editGroupeSanguin"
                                            class="form-select" style="border-radius:10px;">
                                        <option value="">Inconnu</option>
                                        <?php foreach(['A+','A-','B+','B-','AB+','AB-','O+','O-'] as $g): ?>
                                        <option value="<?= $g ?>"><?= $g ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-bold text-secondary">SITUATION MATRIMONIALE</label>
                                    <select name="situation_matrimoniale" id="editSituation"
                                            class="form-select" style="border-radius:10px;">
                                        <option value="celibataire">Célibataire</option>
                                        <option value="marie">Marié(e)</option>
                                        <option value="divorce">Divorcé(e)</option>
                                        <option value="veuf">Veuf / Veuve</option>
                                    </select>
                                </div>

                                <!-- Patiente enceinte ? (uniquement si Sexe=F) — cartes OUI/NON -->
                                <div class="col-12" id="editBlocFemmeEnceinte" style="display:none;">
                                    <div class="p-3 rounded-3" style="background:#fdf2f8;border:1.5px solid #f9a8d4;">
                                        <div class="fw-bold mb-2" style="color:#9d174d;font-size:.85rem;">
                                            <i class="bi bi-heart-pulse-fill me-1"></i>
                                            Patiente enceinte ?
                                        </div>
                                        <div class="d-flex gap-2">
                                            <!-- OUI -->
                                            <label class="fe-card flex-fill" id="editFeOuiCard" for="editFemmeEnceinte_oui" style="cursor:pointer;">
                                                <input type="radio" name="femme_enceinte" value="1"
                                                       id="editFemmeEnceinte_oui"
                                                       onchange="editOnFemmeEnceinte()"
                                                       style="position:absolute;opacity:0;pointer-events:none;">
                                                <div class="fe-card-inner text-center py-3 px-2 rounded-3 h-100">
                                                    <i class="bi bi-heart-pulse-fill d-block mb-1" style="font-size:1.4rem;color:#db2777;"></i>
                                                    <div class="fw-bold" style="font-size:.85rem;color:#9d174d;">OUI</div>
                                                    <div style="font-size:.68rem;color:#be185d;margin-top:2px;">→ Bureau Maternité</div>
                                                </div>
                                            </label>
                                            <!-- NON -->
                                            <label class="fe-card flex-fill" id="editFeNonCard" for="editFemmeEnceinte_non" style="cursor:pointer;">
                                                <input type="radio" name="femme_enceinte" value="0"
                                                       id="editFemmeEnceinte_non"
                                                       onchange="editOnFemmeEnceinte()"
                                                       style="position:absolute;opacity:0;pointer-events:none;">
                                                <div class="fe-card-inner text-center py-3 px-2 rounded-3 h-100">
                                                    <i class="bi bi-person-check d-block mb-1" style="font-size:1.4rem;color:#6b7280;"></i>
                                                    <div class="fw-bold" style="font-size:.85rem;color:#374151;">NON</div>
                                                    <div style="font-size:.68rem;color:#6b7280;margin-top:2px;">→ Bureau B1 / B2</div>
                                                </div>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- ── TAB 2 : CONTACT ─────────────────────────────── -->
                        <div class="tab-pane fade" id="editTabContact">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-secondary">TÉLÉPHONE PRINCIPAL <span class="text-danger">*</span></label>
                                    <input type="tel" name="telephone" id="editTelephone"
                                           class="form-control" style="border-radius:10px;"
                                           placeholder="+237 6XX XXX XXX">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-secondary">TÉLÉPHONE SECONDAIRE</label>
                                    <input type="tel" name="telephone_urgence" id="editTelephoneUrgence"
                                           class="form-control" style="border-radius:10px;"
                                           placeholder="Numéro secondaire…">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-secondary">ADRESSE E-MAIL</label>
                                    <input type="email" name="email" id="editEmail"
                                           class="form-control" style="border-radius:10px;"
                                           placeholder="exemple@email.com">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-secondary">PROFESSION</label>
                                    <input type="text" name="profession" id="editProfession"
                                           class="form-control" style="border-radius:10px;"
                                           placeholder="Enseignant, Commerçant…">
                                </div>
                                <div class="col-md-8">
                                    <label class="form-label small fw-bold text-secondary">ADRESSE / QUARTIER</label>
                                    <input type="text" name="adresse" id="editAdresse"
                                           class="form-control" style="border-radius:10px;"
                                           placeholder="Rue, quartier, commune…">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-bold text-secondary">VILLE</label>
                                    <input type="text" name="ville" id="editVille"
                                           class="form-control" style="border-radius:10px;"
                                           placeholder="Yaoundé, Douala…">
                                </div>
                            </div>
                        </div>

                        <!-- ── TAB 3 : URGENCE ─────────────────────────────── -->
                        <div class="tab-pane fade" id="editTabUrgence">
                            <div class="p-3 rounded-4 mb-3"
                                 style="background:#fff5f5;border:1px solid #fed7d7;">
                                <h6 class="text-danger fw-bold mb-3 small">
                                    <i class="bi bi-exclamation-triangle me-2"></i>PERSONNE À CONTACTER EN CAS D'URGENCE
                                </h6>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label small fw-bold">NOM ET PRÉNOM</label>
                                        <input type="text" name="contact_nom" id="editContactNom"
                                               class="form-control" style="border-radius:10px;border-color:#fed7d7;"
                                               placeholder="Nom complet du contact">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-bold">TÉLÉPHONE D'URGENCE</label>
                                        <input type="tel" name="contact_telephone" id="editContactTel"
                                               class="form-control" style="border-radius:10px;border-color:#fed7d7;"
                                               placeholder="+237 6XX XXX XXX">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- ── TAB 4 : FACTURATION ─────────────────────────── -->
                        <div class="tab-pane fade" id="editTabFacturation">
                            <div class="mb-3">
                                <label class="form-label small fw-bold text-secondary">TYPE DE CLIENT <span class="text-danger">*</span></label>
                                <div class="d-flex flex-wrap gap-2 mt-1">
                                    <?php foreach([
                                        ['PAYANT_COMPTANT','cash-coin','text-success','Payant Comptant'],
                                        ['BON_PRISE_EN_CHARGE','file-medical','text-primary','Bon prise en charge'],
                                        ['ASSURANCE','shield-check','text-info','Assurance'],
                                        ['FAMILLE_PHP','house-heart','text-warning','Famille PHP'],
                                        ['AGENTS_PHP','person-badge','text-secondary','Agents PHP'],
                                    ] as [$val,$ico,$cls,$lbl]): ?>
                                    <div class="form-check border rounded-3 px-3 py-2"
                                         style="cursor:pointer;transition:.15s;">
                                        <input class="form-check-input" type="radio"
                                               name="type_client" id="editTc<?= $val ?>"
                                               value="<?= $val ?>"
                                               onchange="editUpdateTypeClient()">
                                        <label class="form-check-label fw-semibold small"
                                               for="editTc<?= $val ?>" style="cursor:pointer;">
                                            <i class="bi bi-<?= $ico ?> me-1 <?= $cls ?>"></i>
                                            <?= $lbl ?>
                                        </label>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <!-- Assurance -->
                            <div id="editBlocAssurance" style="display:none;" class="mb-3">
                                <div class="row g-3 p-3 rounded-3"
                                     style="background:#f0f9ff;border:1.5px solid #bae6fd;">
                                    <div class="col-md-6">
                                        <label class="form-label small fw-bold">NOM DE L'ASSURANCE</label>
                                        <input type="text" name="nom_assurance" id="editNomAssurance"
                                               class="form-control" style="border-radius:10px;"
                                               placeholder="ex: CNPS, Allianz…">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-bold">N° ASSURÉ</label>
                                        <input type="text" name="numero_assure" id="editNumeroAssure"
                                               class="form-control" style="border-radius:10px;"
                                               placeholder="ex: ASS-2024-00123">
                                    </div>
                                </div>
                            </div>

                            <!-- Bon de prise en charge -->
                            <div id="editBlocBon" style="display:none;" class="mb-3">
                                <div class="row g-3 p-3 rounded-3"
                                     style="border:1.5px solid #93c5fd;background:#eff6ff;">
                                    <div class="col-12" style="font-size:.75rem;font-weight:700;color:#1d4ed8;">
                                        <i class="bi bi-file-medical me-1"></i>Bon de prise en charge (obligatoire)
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-bold">N° DU BON <span class="text-danger">*</span></label>
                                        <input type="text" name="numero_bon" id="editNumeroBon"
                                               class="form-control" style="border-radius:10px;"
                                               placeholder="ex: BON-2026-00123">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-bold">ORGANISME ÉMETTEUR <span class="text-danger">*</span></label>
                                        <input type="text" name="organisme_payeur" id="editOrganismeBon"
                                               class="form-control" style="border-radius:10px;"
                                               placeholder="ex: Ministère de la Santé">
                                    </div>
                                </div>
                            </div>

                            <!-- Agents PHP -->
                            <div id="editBlocAgentPhp" style="display:none;" class="mb-3">
                                <div class="row g-3 p-3 rounded-3"
                                     style="background:#fffbeb;border:1.5px solid #fde68a;">
                                    <div class="col-md-6">
                                        <label class="form-label small fw-bold">MATRICULE AGENT</label>
                                        <input type="text" name="matricule_agent" id="editMatricule"
                                               class="form-control" style="border-radius:10px;"
                                               placeholder="ex: PHP-2024-001">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-bold">INFIRMERIE D'ORIGINE</label>
                                        <input type="text" name="infirmerie_origine" id="editInfirmerie"
                                               class="form-control" style="border-radius:10px;"
                                               placeholder="ex: Infirmerie Ouest">
                                    </div>
                                </div>
                            </div>

                            <!-- N° Compte SAGE (tous types) -->
                            <div class="mt-3 p-3 rounded-3"
                                 style="background:#f8fafc;border:1.5px dashed #e2e8f0;">
                                <label class="form-label small fw-bold text-secondary">
                                    <i class="bi bi-calculator me-1"></i>N° COMPTE SAGE (comptabilité)
                                </label>
                                <input type="text" name="numero_compte_sage" id="editNumeroCompteSage"
                                       class="form-control" style="border-radius:10px;max-width:300px;"
                                       placeholder="Référence comptable…">
                            </div>
                        </div>

                    </div><!-- /tab-content -->
                    </form><!-- /formModifierPatient -->

                </div><!-- /modal-body -->

                <!-- Footer -->
                <div class="modal-footer border-0 px-4 pb-4"
                     style="background:#f8fafc;">
                    <div class="d-flex justify-content-between align-items-center w-100">
                        <button type="button" class="btn btn-link text-muted fw-bold text-decoration-none"
                                data-bs-dismiss="modal">
                            <i class="bi bi-x-lg me-1"></i>Annuler
                        </button>
                        <div class="d-flex gap-2 align-items-center">
                            <!-- Message retour -->
                            <div id="editSaveMsg" style="font-size:.85rem;display:none;"></div>
                            <button type="button" id="editBtnSave"
                                    onclick="saveModificationPatient()"
                                    class="btn px-5 py-2 fw-bold rounded-pill shadow-sm"
                                    style="background:#2563eb;color:white;min-width:180px;">
                                <i class="bi bi-check2-circle me-2"></i>Enregistrer
                            </button>
                        </div>
                    </div>
                </div>
            </div><!-- /editPatientBody -->

        </div>
    </div>
</div>

<script>
/* ═══════════════════════════════════════════════════════════════════
   MODAL MODIFIER PATIENT — Logique JS
   ═══════════════════════════════════════════════════════════════════ */

/* Ouvrir le modal et charger les données */
window.ouvrirEditionPatient = function(id) {
    const modal  = new bootstrap.Modal(document.getElementById('modalModifierPatient'));
    const loader = document.getElementById('editPatientLoader');
    const body   = document.getElementById('editPatientBody');

    body.classList.add('d-none');
    loader.classList.remove('d-none');
    modal.show();

    fetch('<?= BASE_URL ?>accueil/get-patient/' + id)
        .then(r => r.json())
        .then(p => {
            if (!p) { alert('Patient introuvable.'); modal.hide(); return; }

            // En-tête
            document.getElementById('editPatientId').value = p.id;
            document.getElementById('editPatientTitre').textContent = p.nom + ' ' + p.prenom;
            document.getElementById('editDossierBadge').textContent = p.dossier_numero;
            const nbConsult = parseInt(p.nb_consultations) || 0;
            document.getElementById('editPatientMeta').textContent =
                'Créé le ' + new Date(p.created_at).toLocaleDateString('fr-FR')
                + ' · ' + nbConsult + ' consultation' + (nbConsult > 1 ? 's' : '');

            /* ── Identité ── */
            document.getElementById('editNom').value    = p.nom    || '';
            document.getElementById('editPrenom').value = p.prenom || '';
            document.getElementById('editDateNaissance').value = p.date_naissance || '';

            const sexe = p.sexe || 'M';
            const radioSexe = document.getElementById(sexe === 'F' ? 'editSexeF' : 'editSexeM');
            if (radioSexe) radioSexe.checked = true;
            editToggleFemmeEnceinte();

            document.getElementById('editGroupeSanguin').value = p.groupe_sanguin || '';
            document.getElementById('editSituation').value     = p.situation_matrimoniale || 'celibataire';
            // Préremplir les cartes OUI/NON selon la valeur enregistrée
            const ouiEdit = document.getElementById('editFemmeEnceinte_oui');
            const nonEdit = document.getElementById('editFemmeEnceinte_non');
            if (p.sexe === 'F') {
                if (p.femme_enceinte == 1) {
                    if (ouiEdit) ouiEdit.checked = true;
                    document.getElementById('editFeOuiCard')?.classList.add('fe-selected-oui');
                    document.getElementById('editFeNonCard')?.classList.remove('fe-selected-non');
                } else if (p.femme_enceinte == 0) {
                    if (nonEdit) nonEdit.checked = true;
                    document.getElementById('editFeNonCard')?.classList.add('fe-selected-non');
                    document.getElementById('editFeOuiCard')?.classList.remove('fe-selected-oui');
                }
            }

            /* ── Contact ── */
            document.getElementById('editTelephone').value        = p.telephone        || '';
            document.getElementById('editTelephoneUrgence').value = p.telephone_urgence || '';
            document.getElementById('editEmail').value            = p.email            || '';
            document.getElementById('editProfession').value       = p.profession       || '';
            document.getElementById('editAdresse').value          = p.adresse          || '';
            document.getElementById('editVille').value            = p.ville            || '';

            /* ── Urgence ── */
            document.getElementById('editContactNom').value = p.contact_nom       || '';
            document.getElementById('editContactTel').value = p.contact_telephone || '';

            /* ── Facturation ── */
            const tc    = p.type_client || 'PAYANT_COMPTANT';
            const radio = document.getElementById('editTc' + tc);
            if (radio) radio.checked = true;
            else {
                const def = document.getElementById('editTcPAYANT_COMPTANT');
                if (def) def.checked = true;
            }
            document.getElementById('editNomAssurance').value    = p.nom_assurance      || '';
            document.getElementById('editNumeroAssure').value    = p.numero_assure      || '';
            document.getElementById('editNumeroBon').value       = p.numero_bon         || '';
            document.getElementById('editOrganismeBon').value    = p.organisme_payeur   || '';
            document.getElementById('editMatricule').value       = p.matricule_agent    || '';
            document.getElementById('editInfirmerie').value      = p.infirmerie_origine || '';
            document.getElementById('editNumeroCompteSage').value= p.numero_compte_sage || '';
            editUpdateTypeClient();

            // Reset message
            const msg = document.getElementById('editSaveMsg');
            if (msg) msg.style.display = 'none';

            loader.classList.add('d-none');
            body.classList.remove('d-none');
        })
        .catch(() => { alert('Erreur réseau lors du chargement.'); modal.hide(); });
};

/* Toggle bloc femme enceinte (édition) */
function editToggleFemmeEnceinte() {
    const isFemme = document.getElementById('editSexeF')?.checked;
    const bloc    = document.getElementById('editBlocFemmeEnceinte');
    if (bloc) bloc.style.display = isFemme ? 'block' : 'none';
    if (!isFemme) {
        const oui = document.getElementById('editFemmeEnceinte_oui');
        const non = document.getElementById('editFemmeEnceinte_non');
        if (oui) oui.checked = false;
        if (non) non.checked = false;
        document.getElementById('editFeOuiCard')?.classList.remove('fe-selected-oui');
        document.getElementById('editFeNonCard')?.classList.remove('fe-selected-non');
    }
}

/* Mise à jour visuelle des cartes OUI/NON (édition) */
function editOnFemmeEnceinte() {
    const ouiChecked = document.getElementById('editFemmeEnceinte_oui')?.checked;
    document.getElementById('editFeOuiCard')?.classList.toggle('fe-selected-oui', !!ouiChecked);
    document.getElementById('editFeNonCard')?.classList.toggle('fe-selected-non', !ouiChecked);
}

/* Toggle blocs facturation */
function editUpdateTypeClient() {
    const tc = document.querySelector('#formModifierPatient input[name="type_client"]:checked')?.value || '';
    document.getElementById('editBlocAssurance').style.display  = tc === 'ASSURANCE'          ? 'block' : 'none';
    document.getElementById('editBlocBon').style.display        = tc === 'BON_PRISE_EN_CHARGE' ? 'block' : 'none';
    document.getElementById('editBlocAgentPhp').style.display   = tc === 'AGENTS_PHP'         ? 'block' : 'none';
}

/* Enregistrement via AJAX */
window.saveModificationPatient = function() {
    const form = document.getElementById('formModifierPatient');
    const btn  = document.getElementById('editBtnSave');
    const msg  = document.getElementById('editSaveMsg');

    // Validation rapide
    const nom    = document.getElementById('editNom').value.trim();
    const prenom = document.getElementById('editPrenom').value.trim();
    if (!nom || !prenom) {
        showEditMsg('Nom et prénom sont obligatoires.', 'danger');
        document.querySelector('[data-bs-target="#editTabCivil"]')?.click();
        return;
    }

    // Désactiver le bouton
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Enregistrement…';

    const fd = new FormData(form);

    fetch('<?= BASE_URL ?>accueil/modifier-patient', {
        method : 'POST',
        body   : fd,
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            showEditMsg('<i class="bi bi-check-circle-fill me-1"></i>' + data.message, 'success');
            btn.innerHTML = '<i class="bi bi-check2-circle me-2"></i>Enregistré !';
            btn.style.background = '#059669';

            // Mettre à jour le titre du modal
            document.getElementById('editPatientTitre').textContent =
                (data.patient?.nom || '') + ' ' + (data.patient?.prenom || '');

            // Fermer auto après 1.8s
            setTimeout(() => {
                bootstrap.Modal.getInstance(document.getElementById('modalModifierPatient'))?.hide();
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-check2-circle me-2"></i>Enregistrer';
                btn.style.background = '#2563eb';
            }, 1800);
        } else {
            showEditMsg('<i class="bi bi-x-circle-fill me-1"></i>' + (data.message || 'Erreur inconnue'), 'danger');
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-check2-circle me-2"></i>Enregistrer';
        }
    })
    .catch(() => {
        showEditMsg('Erreur réseau. Vérifiez votre connexion.', 'danger');
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-check2-circle me-2"></i>Enregistrer';
    });
};

function showEditMsg(text, type) {
    const msg = document.getElementById('editSaveMsg');
    if (!msg) return;
    msg.style.display = '';
    msg.style.color   = type === 'success' ? '#059669' : '#dc2626';
    msg.style.fontWeight = '600';
    msg.innerHTML = text;
}
</script>
