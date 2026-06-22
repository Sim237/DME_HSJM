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
 require_once __DIR__ . '/../layouts/header.php'; ?>

<div class="container-fluid">
    <div class="row">
        <?php require_once __DIR__ . '/../layouts/sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <div>
                    <h1 class="h2 mb-0"><i class="bi bi-hospital me-2"></i>Hospitalisation</h1>
                    <?php if (!($vueGlobale ?? false)): ?>
                    <div class="d-flex align-items-center gap-2 mt-1">
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1" style="font-size:.75rem;">
                            <i class="bi bi-funnel-fill me-1"></i>
                            <?= htmlspecialchars($_SESSION['nom_service'] ?? 'Votre service') ?> uniquement
                        </span>
                    </div>
                    <?php else: ?>
                    <div class="d-flex align-items-center gap-2 mt-1">
                        <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-2 py-1" style="font-size:.75rem;">
                            <i class="bi bi-globe me-1"></i>Vue globale — tous les services
                        </span>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="btn-toolbar mb-2 mb-md-0 gap-2">
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalAdmission">
                        <i class="bi bi-person-check-fill me-1"></i>Admettre un patient existant
                    </button>
                    <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalCreerHosp">
                        <i class="bi bi-person-plus-fill me-1"></i>Créer &amp; Hospitaliser
                    </button>
                </div>
            </div>

            <!-- Statistiques rapides -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card bg-primary text-white">
                        <div class="card-body">
                            <h5><?= count($patients_hospitalises) ?></h5>
                            <p class="mb-0">Patients hospitalisés</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-success text-white">
                        <div class="card-body">
                            <h5><?= count(array_filter($patients_hospitalises, fn($p) => $p['service_nom'] === 'Médecine Interne')) ?></h5>
                            <p class="mb-0">Médecine Interne</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-warning text-white">
                        <div class="card-body">
                            <h5><?= count(array_filter($patients_hospitalises, fn($p) => $p['service_nom'] === 'Chirurgie')) ?></h5>
                            <p class="mb-0">Chirurgie</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-info text-white">
                        <div class="card-body">
                            <h5><?= count(array_filter($patients_hospitalises, fn($p) => $p['service_nom'] === 'Urgences')) ?></h5>
                            <p class="mb-0">Urgences</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Liste des patients hospitalisés -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Patients Hospitalisés</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Patient</th>
                                    <th>Service</th>
                                    <th>Lit</th>
                                    <th>Admission</th>
                                    <th>Durée</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($patients_hospitalises as $patient): ?>
                                <?php
                                    $pid   = (int)($patient['patient_id'] ?? $patient['pid'] ?? 0);
                                    $hospId = (int)($patient['hosp_id'] ?? 0);
                                    $nomComplet = htmlspecialchars($patient['nom'] . ' ' . $patient['prenom']);
                                    $serviceOrigId = (int)($patient['service_origine_id'] ?? 0);
                                ?>
                                <tr>
                                    <td>
                                        <strong><?= $nomComplet ?></strong><br>
                                        <small class="text-muted"><?= $patient['date_naissance'] ?></small>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary"><?= htmlspecialchars($patient['service_nom'] ?? 'Non assigné') ?></span>
                                    </td>
                                    <td>
                                        <span class="badge bg-info"><?= htmlspecialchars($patient['lit_numero'] ?? 'Non assigné') ?></span>
                                    </td>
                                    <td><?= date('d/m/Y H:i', strtotime($patient['date_admission'])) ?></td>
                                    <td>
                                        <?php
                                        $duree = (new DateTime())->diff(new DateTime($patient['date_admission']));
                                        echo $duree->days . ' jour(s)';
                                        ?>
                                    </td>
                                    <?php $aLit = !empty($patient['lit_numero']); ?>
                                    <td>
                                        <div class="btn-group btn-group-sm flex-wrap">
                                            <a href="<?= BASE_URL ?>hospitalisation/dossier/<?= $pid ?>" class="btn btn-outline-primary">
                                                <i class="bi bi-folder"></i> Dossier
                                            </a>
                                            <a href="<?= BASE_URL ?>hospitalisation/reevaluation/<?= $pid ?>" class="btn btn-primary fw-bold">
                                                <i class="bi bi-clipboard2-pulse-fill"></i> Réévaluer
                                            </a>
                                            <button class="btn btn-outline-success" onclick="administrerTraitement(<?= $pid ?>)">
                                                <i class="bi bi-capsule"></i> Traitement
                                            </button>
                                            <button class="btn btn-outline-info" onclick="ajouterConstantes(<?= $pid ?>)">
                                                <i class="bi bi-heart-pulse"></i> Constantes
                                            </button>
                                            <?php if (!$aLit): ?>
                                            <!-- Patient arrivé par transfert externe sans lit assigné -->
                                            <button class="btn btn-outline-danger fw-bold"
                                                    title="Ce patient n'a pas encore de lit — cliquez pour lui en assigner un"
                                                    onclick="ouvrirModalTransfert(<?= $pid ?>, <?= $hospId ?>, '<?= addslashes($nomComplet) ?>', <?= $serviceOrigId ?>, true)">
                                                <i class="bi bi-hospital me-1"></i> Assigner lit
                                            </button>
                                            <?php endif; ?>
                                            <button class="btn btn-outline-warning"
                                                    onclick="ouvrirModalTransfert(<?= $pid ?>, <?= $hospId ?>, '<?= addslashes($nomComplet) ?>', <?= $serviceOrigId ?>)">
                                                <i class="bi bi-arrow-left-right"></i> Transférer
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════════════
     MODAL — NOUVELLE ADMISSION DIRECTE
═══════════════════════════════════════════════════════════════ -->
<div class="modal fade" id="modalAdmission" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg" style="border-radius:18px;overflow:hidden">

            <!-- En-tête -->
            <div class="modal-header border-0 p-4"
                 style="background:linear-gradient(135deg,#0f4c81,#1d6db5)">
                <div>
                    <h5 class="modal-title fw-bold text-white mb-0">
                        <i class="bi bi-hospital-fill me-2"></i>Nouvelle Admission
                    </h5>
                    <div class="text-white opacity-75 small mt-1">
                        Enregistrement et assignation de lit
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <form id="formAdmission" novalidate>
            <div class="modal-body p-0">

                <!-- ── SECTION 1 : PATIENT ───────────────────────────── -->
                <div class="px-4 pt-4 pb-3" style="background:#f8fafc;border-bottom:1px solid #e2e8f0">
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <div class="rounded-circle d-flex align-items-center justify-content-center"
                             style="width:28px;height:28px;background:#0f4c81;flex-shrink:0">
                            <span class="text-white fw-bold" style="font-size:.7rem">1</span>
                        </div>
                        <span class="fw-bold text-uppercase" style="font-size:.72rem;letter-spacing:.6px;color:#0f4c81">
                            Identification du patient
                        </span>
                    </div>

                    <!-- Recherche patient -->
                    <div class="position-relative">
                        <label class="form-label fw-semibold small text-muted mb-1">
                            <i class="bi bi-search me-1"></i>Rechercher un patient
                            <span class="text-danger">*</span>
                        </label>
                        <input type="text" id="admPatientSearch"
                               class="form-control form-control-lg border-2"
                               placeholder="Nom, prénom ou N° dossier…"
                               autocomplete="off"
                               style="border-radius:10px">
                        <input type="hidden" id="admPatientId" name="patient_id">

                        <!-- Dropdown résultats -->
                        <div id="admPatientDropdown"
                             class="position-absolute w-100 bg-white border rounded-3 shadow-sm"
                             style="top:calc(100% + 4px);z-index:1080;max-height:240px;overflow-y:auto;display:none">
                        </div>
                    </div>

                    <!-- Badge patient sélectionné -->
                    <div id="admPatientBadge" class="mt-2" style="display:none">
                        <div class="d-inline-flex align-items-center gap-2 rounded-pill px-3 py-1"
                             style="background:#dbeafe;border:1px solid #93c5fd;font-size:.82rem">
                            <i class="bi bi-person-check-fill text-primary"></i>
                            <span id="admPatientLabel" class="fw-semibold text-primary"></span>
                            <button type="button" class="btn-close btn-close btn-sm"
                                    style="font-size:.6rem" onclick="resetPatient()"></button>
                        </div>
                    </div>
                </div>

                <!-- ── SECTION 2 : SERVICE + LIT ─────────────────────── -->
                <div class="px-4 pt-3 pb-3" style="border-bottom:1px solid #e2e8f0">
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <div class="rounded-circle d-flex align-items-center justify-content-center"
                             style="width:28px;height:28px;background:#0f4c81;flex-shrink:0">
                            <span class="text-white fw-bold" style="font-size:.7rem">2</span>
                        </div>
                        <span class="fw-bold text-uppercase" style="font-size:.72rem;letter-spacing:.6px;color:#0f4c81">
                            Affectation service &amp; lit
                        </span>
                    </div>

                    <div class="row g-3">
                        <!-- Service -->
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small text-muted mb-1">
                                <i class="bi bi-building me-1"></i>Service de destination
                                <span class="text-danger">*</span>
                            </label>
                            <select id="admServiceId" name="service_id"
                                    class="form-select border-2"
                                    style="border-radius:10px"
                                    onchange="chargerLitsAdmission(this.value)">
                                <option value="">— Choisir un service —</option>
                                <?php foreach(($servicesCliniques ?? []) as $s): ?>
                                <option value="<?= (int)$s['id'] ?>">
                                    <?= htmlspecialchars($s['nom_service']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Lit -->
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small text-muted mb-1">
                                <i class="bi bi-hospital me-1"></i>Lit d'admission
                                <span class="text-muted fw-normal">(optionnel)</span>
                            </label>
                            <select id="admLitId" name="lit_id"
                                    class="form-select border-2"
                                    style="border-radius:10px" disabled>
                                <option value="">— Choisir un service d'abord —</option>
                            </select>
                            <div id="admLitsLoader" class="form-text d-none">
                                <span class="spinner-border spinner-border-sm me-1 text-primary"></span>
                                Chargement des lits disponibles…
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ── SECTION 3 : MÉDECIN + DATES ──────────────────── -->
                <div class="px-4 pt-3 pb-3" style="border-bottom:1px solid #e2e8f0">
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <div class="rounded-circle d-flex align-items-center justify-content-center"
                             style="width:28px;height:28px;background:#0f4c81;flex-shrink:0">
                            <span class="text-white fw-bold" style="font-size:.7rem">3</span>
                        </div>
                        <span class="fw-bold text-uppercase" style="font-size:.72rem;letter-spacing:.6px;color:#0f4c81">
                            Responsabilité médicale &amp; dates
                        </span>
                    </div>

                    <div class="row g-3">
                        <!-- Médecin responsable -->
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small text-muted mb-1">
                                <i class="bi bi-person-badge me-1"></i>Médecin responsable
                            </label>
                            <select id="admMedecinId" name="medecin_id"
                                    class="form-select border-2" style="border-radius:10px">
                                <option value="">— Sélectionner —</option>
                                <?php foreach(($medecins ?? []) as $m): ?>
                                <option value="<?= (int)$m['id'] ?>">
                                    Dr <?= htmlspecialchars($m['nom'] . ' ' . $m['prenom']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Date d'admission -->
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small text-muted mb-1">
                                <i class="bi bi-calendar-check me-1"></i>Date d'admission
                                <span class="text-danger">*</span>
                            </label>
                            <input type="datetime-local" id="admDateAdmission" name="date_admission"
                                   class="form-control border-2" style="border-radius:10px"
                                   value="<?= date('Y-m-d\TH:i') ?>">
                        </div>

                        <!-- Date sortie prévue -->
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small text-muted mb-1">
                                <i class="bi bi-calendar-x me-1"></i>Sortie prévue
                                <span class="text-muted fw-normal">(optionnel)</span>
                            </label>
                            <input type="date" id="admDateSortie" name="date_sortie_prevue"
                                   class="form-control border-2" style="border-radius:10px"
                                   min="<?= date('Y-m-d') ?>">
                        </div>
                    </div>
                </div>

                <!-- ── SECTION 4 : MOTIF + DIAGNOSTIC ───────────────── -->
                <div class="px-4 pt-3 pb-4">
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <div class="rounded-circle d-flex align-items-center justify-content-center"
                             style="width:28px;height:28px;background:#0f4c81;flex-shrink:0">
                            <span class="text-white fw-bold" style="font-size:.7rem">4</span>
                        </div>
                        <span class="fw-bold text-uppercase" style="font-size:.72rem;letter-spacing:.6px;color:#0f4c81">
                            Informations cliniques
                        </span>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small text-muted mb-1">
                                <i class="bi bi-clipboard2-pulse me-1"></i>Motif d'hospitalisation
                                <span class="text-danger">*</span>
                            </label>
                            <textarea id="admMotif" name="motif"
                                      class="form-control border-2" rows="3"
                                      style="border-radius:10px;resize:vertical"
                                      placeholder="Décrivez le motif principal d'admission…"></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small text-muted mb-1">
                                <i class="bi bi-file-medical me-1"></i>Diagnostic d'entrée
                                <span class="text-muted fw-normal">(optionnel)</span>
                            </label>
                            <textarea id="admDiagnostic" name="diagnostic"
                                      class="form-control border-2" rows="3"
                                      style="border-radius:10px;resize:vertical"
                                      placeholder="Diagnostic provisoire à l'entrée…"></textarea>
                        </div>
                    </div>
                </div>

            </div><!-- /.modal-body -->

            <!-- Pied de page -->
            <div class="modal-footer border-0 px-4 pb-4 pt-0 d-flex justify-content-between align-items-center">
                <div id="admErreur" class="text-danger small fw-semibold" style="display:none">
                    <i class="bi bi-exclamation-triangle-fill me-1"></i>
                    <span id="admErreurMsg"></span>
                </div>
                <div class="ms-auto d-flex gap-2">
                    <button type="button" class="btn btn-light rounded-pill px-4"
                            data-bs-dismiss="modal">Annuler</button>
                    <button type="button" id="admBtnSubmit"
                            class="btn btn-primary btn-lg rounded-pill fw-bold shadow px-5"
                            onclick="sauvegarderAdmission()">
                        <i class="bi bi-hospital-fill me-1"></i>ADMETTRE
                    </button>
                </div>
            </div>
            </form>

        </div>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════════════
     MODAL TRANSFERT PATIENT — 2 étapes
     Étape 1 : choisir le type (interne / externe)
     Étape 2A : transfert interne — changer de lit (même service)
     Étape 2B : transfert externe — envoyer vers un autre service
═══════════════════════════════════════════════════════════════ -->
<div class="modal fade" id="modalTransfert" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow rounded-4" style="overflow:hidden;">

            <!-- En-tête -->
            <div class="modal-header border-0 px-4 pt-4 pb-2"
                 style="background:linear-gradient(135deg,#f59e0b,#d97706);">
                <div>
                    <h5 class="fw-bold mb-0 text-white">
                        <i class="bi bi-arrow-left-right me-2"></i>Transfert de patient
                    </h5>
                    <small class="text-white opacity-75" id="transfertSousTitre">
                        Choisissez le type de transfert
                    </small>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body px-4 pb-4">

                <!-- Bandeau patient -->
                <div class="alert alert-warning border-0 rounded-3 small mb-3 py-2 d-flex align-items-center gap-2">
                    <i class="bi bi-person-fill fs-5"></i>
                    <strong id="transfertNomPatient">—</strong>
                    <span id="transfertLitActuelBadge" class="ms-auto badge bg-secondary"></span>
                </div>

                <!-- ── ÉTAPE 1 : Choix du type ── -->
                <div id="transfertStep1">
                    <p class="text-muted small mb-3 fw-semibold">
                        <i class="bi bi-signpost-2 me-1"></i>
                        Sélectionnez le type de transfert à effectuer :
                    </p>
                    <div class="row g-3">

                        <!-- Transfert interne -->
                        <div class="col-md-6">
                            <div class="card border-2 border-primary rounded-4 p-4 text-center h-100"
                                 role="button" style="cursor:pointer;transition:all .15s"
                                 onmouseover="this.style.background='#eff6ff'"
                                 onmouseout="this.style.background=''"
                                 onclick="choisirTransfertInterne()">
                                <div class="mb-2" style="font-size:2.4rem;">🛏️</div>
                                <h6 class="fw-bold text-primary mb-1">Transfert interne</h6>
                                <p class="text-muted small mb-0">
                                    Changer de lit au sein du <strong>même service</strong>
                                </p>
                            </div>
                        </div>

                        <!-- Transfert externe -->
                        <div class="col-md-6">
                            <div class="card border-2 border-danger rounded-4 p-4 text-center h-100"
                                 role="button" style="cursor:pointer;transition:all .15s"
                                 onmouseover="this.style.background='#fff5f5'"
                                 onmouseout="this.style.background=''"
                                 onclick="choisirTransfertExterne()">
                                <div class="mb-2" style="font-size:2.4rem;">🏥</div>
                                <h6 class="fw-bold text-danger mb-1">Transfert externe</h6>
                                <p class="text-muted small mb-0">
                                    Envoyer vers un <strong>autre service</strong>
                                </p>
                            </div>
                        </div>

                    </div>

                    <div class="text-end mt-3">
                        <button type="button" class="btn btn-light rounded-pill px-4"
                                data-bs-dismiss="modal">Annuler</button>
                    </div>
                </div>

                <!-- ── ÉTAPE 2A : Transfert interne ── -->
                <div id="transfertStepInterne" class="d-none">

                    <div class="alert alert-primary border-0 rounded-3 small py-2 mb-3">
                        <i class="bi bi-info-circle me-1"></i>
                        Le patient reste dans le <strong>même service</strong>, seul son lit change.
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-uppercase text-muted">
                            Nouveau lit <span class="text-danger">*</span>
                        </label>
                        <select id="interneNouveauLit" class="form-select border-2 rounded-3" disabled>
                            <option value="">Chargement des lits disponibles…</option>
                        </select>
                        <div id="interneLitsLoader" class="form-text text-muted d-none">
                            <span class="spinner-border spinner-border-sm me-1"></span> Chargement…
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-uppercase text-muted">Motif</label>
                        <textarea id="interneMotif" class="form-control border-2 rounded-3" rows="2"
                                  placeholder="Raison du changement de lit (optionnel)…"></textarea>
                    </div>

                    <div class="d-flex gap-2 mt-4">
                        <button type="button" class="btn btn-light rounded-pill px-3"
                                onclick="retourStep1()">
                            <i class="bi bi-arrow-left me-1"></i> Retour
                        </button>
                        <button type="button" id="btnConfirmerInterne"
                                class="btn btn-primary rounded-pill px-4 flex-grow-1 fw-bold"
                                onclick="confirmerTransfertInterne()">
                            <i class="bi bi-check2 me-1"></i> Confirmer le changement de lit
                        </button>
                    </div>
                </div>

                <!-- ── ÉTAPE 2B : Transfert externe ── -->
                <div id="transfertStepExterne" class="d-none">

                    <div class="alert alert-info border-0 rounded-3 small py-2 mb-3">
                        <i class="bi bi-info-circle me-1"></i>
                        L'infirmière du service de destination se chargera d'assigner un lit au patient.
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-uppercase text-muted">
                            Service de destination <span class="text-danger">*</span>
                        </label>
                        <select id="externeServiceId" class="form-select border-2 rounded-3">
                            <option value="">-- Choisir un service --</option>
                            <?php foreach(($servicesCliniques ?? []) as $s): ?>
                            <option value="<?= (int)$s['id'] ?>"><?= htmlspecialchars($s['nom_service']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-uppercase text-muted">
                            Motif du transfert
                        </label>
                        <textarea id="externeMotif" class="form-control border-2 rounded-3" rows="2"
                                  placeholder="Raison du transfert (optionnel)…"></textarea>
                    </div>

                    <div class="d-flex gap-2 mt-4">
                        <button type="button" class="btn btn-light rounded-pill px-3"
                                onclick="retourStep1()">
                            <i class="bi bi-arrow-left me-1"></i> Retour
                        </button>
                        <button type="button" id="btnConfirmerExterne"
                                class="btn btn-danger rounded-pill px-4 flex-grow-1 fw-bold"
                                onclick="confirmerTransfertExterne()">
                            <i class="bi bi-check2 me-1"></i> Confirmer le transfert
                        </button>
                    </div>
                </div>

            </div><!-- /.modal-body -->
        </div>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════════════
     MODAL — CRÉER PATIENT + HOSPITALISER DIRECTEMENT
     (Pour patients déjà hospitalisés avant le lancement du logiciel)
═══════════════════════════════════════════════════════════════ -->
<div class="modal fade" id="modalCreerHosp" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius:18px;overflow:hidden;max-height:90vh;display:flex;flex-direction:column">

            <!-- En-tête -->
            <div class="modal-header border-0 p-4"
                 style="background:linear-gradient(135deg,#065f46,#059669)">
                <div>
                    <h5 class="modal-title fw-bold text-white mb-0">
                        <i class="bi bi-person-plus-fill me-2"></i>Créer un patient &amp; l'hospitaliser
                    </h5>
                    <div class="text-white opacity-75 small mt-1">
                        Pour les patients hospitalisés <strong>avant le lancement du logiciel</strong> — crée le dossier et l'installe directement sur un lit
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <form id="formCreerHosp" novalidate
                  style="flex:1 1 auto;min-height:0;overflow:hidden;display:flex;flex-direction:column">
            <div class="modal-body p-0" style="overflow-y:auto;flex:1 1 auto">

                <!-- ── ALERTE CONTEXT ─────────────────────────────────── -->
                <div class="px-4 pt-3 pb-2">
                    <div class="alert alert-warning border-0 rounded-3 py-2 px-3 mb-0 d-flex align-items-center gap-2" style="font-size:.82rem">
                        <i class="bi bi-info-circle-fill text-warning fs-5 flex-shrink-0"></i>
                        <div>
                            <strong>Attention :</strong> Si ce patient est <u>déjà enregistré</u> dans le système,
                            utilisez plutôt le bouton <strong>"Admettre un patient existant"</strong> pour éviter les doublons.
                        </div>
                    </div>
                </div>

                <!-- ── SECTION A : ÉTAT CIVIL ─────────────────────────── -->
                <div class="px-4 pt-3 pb-3" style="border-bottom:1px solid #e2e8f0">
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <div class="rounded-circle d-flex align-items-center justify-content-center"
                             style="width:28px;height:28px;background:#065f46;flex-shrink:0">
                            <span class="text-white fw-bold" style="font-size:.7rem">A</span>
                        </div>
                        <span class="fw-bold text-uppercase" style="font-size:.72rem;letter-spacing:.6px;color:#065f46">
                            État civil du patient
                        </span>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small text-muted mb-1">
                                Nom <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="nom" id="chNom"
                                   class="form-control border-2 text-uppercase" style="border-radius:10px"
                                   placeholder="DUPONT" autocomplete="off">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small text-muted mb-1">
                                Prénom <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="prenom" id="chPrenom"
                                   class="form-control border-2" style="border-radius:10px"
                                   placeholder="Jean" autocomplete="off">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small text-muted mb-1">
                                Sexe <span class="text-danger">*</span>
                            </label>
                            <select name="sexe" class="form-select border-2" style="border-radius:10px">
                                <option value="">— Choisir —</option>
                                <option value="M">Masculin</option>
                                <option value="F">Féminin</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small text-muted mb-1">
                                Date de naissance
                                <span class="text-muted fw-normal">(ou estimation)</span>
                            </label>
                            <input type="date" name="date_naissance"
                                   class="form-control border-2" style="border-radius:10px"
                                   max="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small text-muted mb-1">
                                Téléphone
                                <span class="text-muted fw-normal">(optionnel)</span>
                            </label>
                            <input type="tel" name="telephone"
                                   class="form-control border-2" style="border-radius:10px"
                                   placeholder="6X XX XX XX XX">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small text-muted mb-1">
                                Groupe sanguin
                                <span class="text-muted fw-normal">(optionnel)</span>
                            </label>
                            <select name="groupe_sanguin" class="form-select border-2" style="border-radius:10px">
                                <option value="">— Non renseigné —</option>
                                <option value="A+">A+</option><option value="A-">A-</option>
                                <option value="B+">B+</option><option value="B-">B-</option>
                                <option value="AB+">AB+</option><option value="AB-">AB-</option>
                                <option value="O+">O+</option><option value="O-">O-</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small text-muted mb-1">
                                Type de prise en charge
                            </label>
                            <select name="type_client" class="form-select border-2" style="border-radius:10px">
                                <option value="PAYANT_COMPTANT" selected>Payant comptant</option>
                                <option value="ASSURANCE">Assurance</option>
                                <option value="BON_PRISE_EN_CHARGE">Bon de prise en charge</option>
                                <option value="FAMILLE_PHP">Famille PHP</option>
                                <option value="AGENTS_PHP">Agent PHP</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- ── SECTION B : SERVICE + LIT ─────────────────────── -->
                <div class="px-4 pt-3 pb-3" style="border-bottom:1px solid #e2e8f0">
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <div class="rounded-circle d-flex align-items-center justify-content-center"
                             style="width:28px;height:28px;background:#065f46;flex-shrink:0">
                            <span class="text-white fw-bold" style="font-size:.7rem">B</span>
                        </div>
                        <span class="fw-bold text-uppercase" style="font-size:.72rem;letter-spacing:.6px;color:#065f46">
                            Affectation service &amp; lit
                        </span>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small text-muted mb-1">
                                <i class="bi bi-building me-1"></i>Service <span class="text-danger">*</span>
                            </label>
                            <select id="chServiceId" name="service_id"
                                    class="form-select border-2" style="border-radius:10px"
                                    onchange="chChargerLits(this.value)">
                                <option value="">— Choisir un service —</option>
                                <?php foreach(($servicesCliniques ?? []) as $s): ?>
                                <option value="<?= (int)$s['id'] ?>">
                                    <?= htmlspecialchars($s['nom_service']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small text-muted mb-1">
                                <i class="bi bi-hospital me-1"></i>Lit
                                <span class="text-muted fw-normal">(optionnel)</span>
                            </label>
                            <select id="chLitId" name="lit_id"
                                    class="form-select border-2" style="border-radius:10px" disabled>
                                <option value="">— Choisir un service d'abord —</option>
                            </select>
                            <div id="chLitsLoader" class="form-text d-none">
                                <span class="spinner-border spinner-border-sm me-1 text-success"></span>
                                Chargement des lits disponibles…
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ── SECTION C : MÉDECIN + DATES + MOTIF ───────────── -->
                <div class="px-4 pt-3 pb-4">
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <div class="rounded-circle d-flex align-items-center justify-content-center"
                             style="width:28px;height:28px;background:#065f46;flex-shrink:0">
                            <span class="text-white fw-bold" style="font-size:.7rem">C</span>
                        </div>
                        <span class="fw-bold text-uppercase" style="font-size:.72rem;letter-spacing:.6px;color:#065f46">
                            Informations cliniques &amp; dates
                        </span>
                    </div>

                    <div class="row g-3">
                        <!-- Médecin -->
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small text-muted mb-1">
                                <i class="bi bi-person-badge me-1"></i>Médecin responsable
                            </label>
                            <select name="medecin_id" class="form-select border-2" style="border-radius:10px">
                                <option value="">— Sélectionner —</option>
                                <?php foreach(($medecins ?? []) as $m): ?>
                                <option value="<?= (int)$m['id'] ?>">
                                    Dr <?= htmlspecialchars($m['nom'] . ' ' . $m['prenom']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Date d'admission — peut être antidatée -->
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small text-muted mb-1">
                                <i class="bi bi-calendar-check me-1"></i>Date d'admission
                                <span class="text-danger">*</span>
                                <span class="badge bg-warning text-dark ms-1" style="font-size:.6rem">antidatable</span>
                            </label>
                            <input type="datetime-local" id="chDateAdmission" name="date_admission"
                                   class="form-control border-2" style="border-radius:10px"
                                   value="<?= date('Y-m-d\TH:i') ?>"
                                   max="<?= date('Y-m-d\TH:i') ?>">
                            <div class="form-text text-muted" style="font-size:.7rem">
                                <i class="bi bi-info-circle me-1"></i>Saisir la vraie date si le patient est déjà hospitalisé
                            </div>
                        </div>

                        <!-- Date sortie prévue -->
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small text-muted mb-1">
                                <i class="bi bi-calendar-x me-1"></i>Sortie prévue
                                <span class="text-muted fw-normal">(optionnel)</span>
                            </label>
                            <input type="date" name="date_sortie_prevue"
                                   class="form-control border-2" style="border-radius:10px">
                        </div>

                        <!-- Motif -->
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small text-muted mb-1">
                                <i class="bi bi-clipboard2-pulse me-1"></i>Motif d'hospitalisation
                                <span class="text-danger">*</span>
                            </label>
                            <textarea name="motif" class="form-control border-2" rows="3"
                                      style="border-radius:10px;resize:vertical"
                                      placeholder="Décrivez le motif principal d'admission…"></textarea>
                        </div>

                        <!-- Diagnostic -->
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small text-muted mb-1">
                                <i class="bi bi-file-medical me-1"></i>Diagnostic d'entrée
                                <span class="text-muted fw-normal">(optionnel)</span>
                            </label>
                            <textarea name="diagnostic" class="form-control border-2" rows="3"
                                      style="border-radius:10px;resize:vertical"
                                      placeholder="Diagnostic provisoire à l'entrée…"></textarea>
                        </div>
                    </div>
                </div>

            </div><!-- /.modal-body -->

            <!-- Pied de page -->
            <div class="modal-footer border-0 px-4 pb-4 pt-0 d-flex justify-content-between align-items-center"
                 style="background:#f8fafc">
                <div id="chErreur" class="text-danger small fw-semibold d-none">
                    <i class="bi bi-exclamation-triangle-fill me-1"></i>
                    <span id="chErreurMsg"></span>
                </div>
                <div class="ms-auto d-flex gap-2">
                    <button type="button" class="btn btn-light rounded-pill px-4"
                            data-bs-dismiss="modal">Annuler</button>
                    <button type="button" id="chBtnSubmit"
                            class="btn btn-success btn-lg rounded-pill fw-bold shadow px-5"
                            onclick="sauvegarderCreerHosp()">
                        <i class="bi bi-person-plus-fill me-1"></i>CRÉER &amp; HOSPITALISER
                    </button>
                </div>
            </div>
            </form>

        </div>
    </div>
</div>

<script>
const BASE_URL_HOSP = '<?= BASE_URL ?>';
let transfertPatientId    = null;
let transfertHospId       = null;
let transfertServiceOrigId = 0;

// ─────────────────────────────────────────────────────────────
//  NAVIGATION RAPIDE
// ─────────────────────────────────────────────────────────────
function administrerTraitement(patientId) {
    window.location.href = BASE_URL_HOSP + `hospitalisation/dossier/${patientId}#traitements`;
}
function ajouterConstantes(patientId) {
    window.location.href = BASE_URL_HOSP + `hospitalisation/dossier/${patientId}#constantes`;
}

// ─────────────────────────────────────────────────────────────
//  RECHERCHE PATIENT — autocomplete
// ─────────────────────────────────────────────────────────────
let admSearchTimer = null;

document.addEventListener('DOMContentLoaded', function () {
    const input    = document.getElementById('admPatientSearch');
    const dropdown = document.getElementById('admPatientDropdown');
    if (!input) return;

    input.addEventListener('input', function () {
        clearTimeout(admSearchTimer);
        const q = this.value.trim();
        if (q.length < 2) { dropdown.style.display = 'none'; return; }
        admSearchTimer = setTimeout(() => rechercherPatients(q), 280);
    });

    // Fermer le dropdown en cliquant ailleurs
    document.addEventListener('click', e => {
        if (!input.contains(e.target) && !dropdown.contains(e.target)) {
            dropdown.style.display = 'none';
        }
    });

    // Réinitialiser le formulaire à l'ouverture du modal
    document.getElementById('modalAdmission').addEventListener('show.bs.modal', function () {
        resetFormAdmission();
    });
});

function rechercherPatients(q) {
    const dropdown = document.getElementById('admPatientDropdown');
    dropdown.innerHTML = '<div class="p-3 text-center text-muted small"><span class="spinner-border spinner-border-sm me-1"></span>Recherche…</div>';
    dropdown.style.display = 'block';

    fetch(BASE_URL_HOSP + 'consultation/search-patients?q=' + encodeURIComponent(q))
        .then(r => r.json())
        .then(patients => {
            if (!patients.length) {
                dropdown.innerHTML = '<div class="p-3 text-center text-muted small"><i class="bi bi-search me-1"></i>Aucun résultat</div>';
                return;
            }
            dropdown.innerHTML = patients.map(p => {
                const age = p.date_naissance
                    ? new Date().getFullYear() - new Date(p.date_naissance).getFullYear() + ' ans'
                    : '';
                const sexe = p.sexe ? (p.sexe.toUpperCase().startsWith('M') ? '♂' : '♀') : '';
                return `<div class="adm-patient-item px-3 py-2 d-flex align-items-center gap-2"
                              style="cursor:pointer;border-bottom:1px solid #f1f5f9;font-size:.84rem"
                              onmouseenter="this.style.background='#f0f9ff'"
                              onmouseleave="this.style.background=''"
                              onclick="selectionnerPatient(${p.id}, '${escJs(p.nom)}', '${escJs(p.prenom)}', '${escJs(p.dossier_numero || '')}')">
                    <div class="rounded-circle bg-primary d-flex align-items-center justify-content-center flex-shrink-0"
                         style="width:34px;height:34px;font-size:.75rem;font-weight:700;color:#fff">
                        ${escHtml(p.nom).charAt(0)}${escHtml(p.prenom).charAt(0)}
                    </div>
                    <div class="flex-grow-1 overflow-hidden">
                        <div class="fw-semibold text-truncate">${escHtml(p.nom)} ${escHtml(p.prenom)}</div>
                        <div class="text-muted" style="font-size:.73rem">
                            N° ${escHtml(p.dossier_numero || '—')}
                            ${sexe ? ' · ' + sexe : ''}
                            ${age ? ' · ' + age : ''}
                        </div>
                    </div>
                </div>`;
            }).join('');
        })
        .catch(() => {
            dropdown.innerHTML = '<div class="p-3 text-center text-danger small">Erreur de recherche</div>';
        });
}

function selectionnerPatient(id, nom, prenom, dossier) {
    document.getElementById('admPatientId').value   = id;
    document.getElementById('admPatientSearch').value = '';
    document.getElementById('admPatientDropdown').style.display = 'none';
    document.getElementById('admPatientLabel').textContent =
        nom.toUpperCase() + ' ' + prenom + (dossier ? '  —  N° ' + dossier : '');
    document.getElementById('admPatientBadge').style.display = '';
    document.getElementById('admPatientSearch').style.display = 'none';
}

function resetPatient() {
    document.getElementById('admPatientId').value = '';
    document.getElementById('admPatientLabel').textContent = '';
    document.getElementById('admPatientBadge').style.display = 'none';
    document.getElementById('admPatientSearch').style.display = '';
    document.getElementById('admPatientSearch').value = '';
    document.getElementById('admPatientSearch').focus();
}

// ─────────────────────────────────────────────────────────────
//  CHARGEMENT LITS — admission (séparé du sélecteur transfert)
// ─────────────────────────────────────────────────────────────
function chargerLitsAdmission(serviceId) {
    const select = document.getElementById('admLitId');
    const loader = document.getElementById('admLitsLoader');

    if (!serviceId) {
        select.innerHTML = '<option value="">— Choisir un service d\'abord —</option>';
        select.disabled = true;
        return;
    }

    loader.classList.remove('d-none');
    select.disabled = true;
    select.innerHTML = '';

    fetch(BASE_URL_HOSP + 'hospitalisation/lits-disponibles?service_id=' + serviceId)
        .then(r => r.json())
        .then(lits => {
            loader.classList.add('d-none');
            select.innerHTML = '<option value="">— Aucun lit (non assigné) —</option>';
            if (!Array.isArray(lits) || lits.length === 0) {
                select.innerHTML += '<option value="" disabled>⚠ Aucun lit disponible dans ce service</option>';
            } else {
                lits.forEach(l => {
                    const label = l.nom_chambre ? `${l.nom_lit}  (${l.nom_chambre})` : l.nom_lit;
                    select.innerHTML += `<option value="${l.id}">${label}</option>`;
                });
            }
            select.disabled = false;
        })
        .catch(() => {
            loader.classList.add('d-none');
            select.innerHTML = '<option value="">Erreur de chargement</option>';
        });
}

// ─────────────────────────────────────────────────────────────
//  SOUMISSION DU FORMULAIRE
// ─────────────────────────────────────────────────────────────
function sauvegarderAdmission() {
    // Validation côté client
    const patientId = document.getElementById('admPatientId').value;
    const serviceId = document.getElementById('admServiceId').value;
    const motif     = document.getElementById('admMotif').value.trim();

    const errDiv = document.getElementById('admErreur');
    const errMsg = document.getElementById('admErreurMsg');

    const showErr = msg => {
        errMsg.textContent = msg;
        errDiv.style.display = '';
        errDiv.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    };

    if (!patientId)  { showErr('Veuillez sélectionner un patient.');             return; }
    if (!serviceId)  { showErr('Veuillez sélectionner un service.');             return; }
    if (!motif)      { showErr('Le motif d\'hospitalisation est obligatoire.');  return; }

    errDiv.style.display = 'none';

    const btn = document.getElementById('admBtnSubmit');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Admission en cours…';

    const fd = new FormData(document.getElementById('formAdmission'));

    fetch(BASE_URL_HOSP + 'hospitalisation/admettre', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                bootstrap.Modal.getInstance(document.getElementById('modalAdmission'))?.hide();
                // Toast succès
                const toast = document.createElement('div');
                toast.className = 'position-fixed bottom-0 end-0 p-3';
                toast.style.zIndex = 9999;
                toast.innerHTML = `<div class="toast show align-items-center text-white bg-success border-0 rounded-3 shadow">
                    <div class="d-flex">
                        <div class="toast-body fw-semibold">
                            <i class="bi bi-check-circle-fill me-2"></i>${data.message}
                        </div>
                    </div></div>`;
                document.body.appendChild(toast);
                setTimeout(() => location.reload(), 1200);
            } else {
                showErr(data.message || 'Erreur lors de l\'admission.');
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-hospital-fill me-1"></i>ADMETTRE';
            }
        })
        .catch(() => {
            showErr('Erreur réseau. Veuillez réessayer.');
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-hospital-fill me-1"></i>ADMETTRE';
        });
}

function resetFormAdmission() {
    resetPatient();
    document.getElementById('admServiceId').value = '';
    document.getElementById('admLitId').innerHTML = '<option value="">— Choisir un service d\'abord —</option>';
    document.getElementById('admLitId').disabled  = true;
    document.getElementById('admMedecinId').value = '';
    document.getElementById('admDateAdmission').value = new Date().toISOString().slice(0, 16);
    document.getElementById('admDateSortie').value = '';
    document.getElementById('admMotif').value = '';
    document.getElementById('admDiagnostic').value = '';
    document.getElementById('admErreur').style.display = 'none';
    const btn = document.getElementById('admBtnSubmit');
    btn.disabled = false;
    btn.innerHTML = '<i class="bi bi-hospital-fill me-1"></i>ADMETTRE';
}

// ── Utilitaires ─────────────────────────────────────────────
function escHtml(s) { return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }
function escJs(s)   { return String(s).replace(/\\/g,'\\\\').replace(/'/g,"\\'"); }

// ─────────────────────────────────────────────────────────────
//  MODAL TRANSFERT — logique 2 étapes
// ─────────────────────────────────────────────────────────────

/**
 * Ouvre le modal de transfert.
 * @param {number}  patientId     ID patient
 * @param {number}  hospId        ID ligne hospitalisations (en_cours)
 * @param {string}  nom           Nom affiché
 * @param {number}  serviceOrigId Service actuel du patient
 * @param {boolean} autoInterne   Si true → passe directement à l'étape interne (lit non assigné)
 */
function ouvrirModalTransfert(patientId, hospId, nom, serviceOrigId, autoInterne = false) {
    transfertPatientId     = patientId;
    transfertHospId        = hospId;
    transfertServiceOrigId = serviceOrigId;

    document.getElementById('transfertNomPatient').textContent = nom;

    // Filtrer les options du service externe (exclure le service actuel)
    const selExt = document.getElementById('externeServiceId');
    Array.from(selExt.options).forEach(opt => {
        opt.hidden = (opt.value !== '' && parseInt(opt.value) === serviceOrigId);
    });
    selExt.value = '';
    selExt.classList.remove('is-invalid');

    // Réinitialiser à l'étape 1
    _showTransfertStep('step1');

    const modal = new bootstrap.Modal(document.getElementById('modalTransfert'));
    modal.show();

    // Si déclenchement direct "Assigner lit" → aller à l'étape interne
    if (autoInterne) {
        setTimeout(() => choisirTransfertInterne(), 300);
    }
}

/** Retourne à l'étape de choix */
function retourStep1() {
    _showTransfertStep('step1');
    // Reset boutons
    _resetBtn('btnConfirmerInterne', '<i class="bi bi-check2 me-1"></i> Confirmer le changement de lit');
    _resetBtn('btnConfirmerExterne', '<i class="bi bi-check2 me-1"></i> Confirmer le transfert');
}

/** Sélection : transfert interne */
function choisirTransfertInterne() {
    _showTransfertStep('interne');
    document.getElementById('transfertSousTitre').textContent = 'Transfert interne — Changement de lit';
    document.getElementById('interneMotif').value = '';
    document.getElementById('interneNouveauLit').classList.remove('is-invalid');

    const select = document.getElementById('interneNouveauLit');
    const loader = document.getElementById('interneLitsLoader');
    select.innerHTML = '<option value="">Chargement…</option>';
    select.disabled = true;
    loader.classList.remove('d-none');

    fetch(BASE_URL_HOSP + 'hospitalisation/lits-disponibles?service_id=' + transfertServiceOrigId)
        .then(r => r.json())
        .then(lits => {
            loader.classList.add('d-none');
            select.innerHTML = '<option value="">-- Choisir un nouveau lit --</option>';
            if (!Array.isArray(lits) || lits.length === 0) {
                select.innerHTML += '<option disabled>⚠ Aucun lit disponible dans ce service</option>';
            } else {
                lits.forEach(l => {
                    const label = l.nom_chambre ? `${l.nom_lit} (${l.nom_chambre})` : l.nom_lit;
                    select.innerHTML += `<option value="${l.id}">${escHtml(label)}</option>`;
                });
            }
            select.disabled = false;
        })
        .catch(() => {
            loader.classList.add('d-none');
            select.innerHTML = '<option value="">Erreur de chargement</option>';
        });
}

/** Sélection : transfert externe */
function choisirTransfertExterne() {
    _showTransfertStep('externe');
    document.getElementById('transfertSousTitre').textContent = 'Transfert externe — Autre service';
    document.getElementById('externeMotif').value = '';
    document.getElementById('externeServiceId').classList.remove('is-invalid');
}

/** Confirme le changement de lit (interne) */
function confirmerTransfertInterne() {
    const litId = document.getElementById('interneNouveauLit').value;
    if (!litId) {
        document.getElementById('interneNouveauLit').classList.add('is-invalid');
        return;
    }
    document.getElementById('interneNouveauLit').classList.remove('is-invalid');

    const btn = document.getElementById('btnConfirmerInterne');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Déplacement…';

    const fd = new FormData();
    fd.append('hosp_id',         transfertHospId);
    fd.append('nouveau_lit_id',  litId);
    fd.append('motif',           document.getElementById('interneMotif').value);

    fetch(BASE_URL_HOSP + 'hospitalisation/changer-lit', {
        method: 'POST',
        body: fd,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            bootstrap.Modal.getInstance(document.getElementById('modalTransfert')).hide();
            _afficherToast(data.message, 'success');
            setTimeout(() => location.reload(), 1400);
        } else {
            alert('Erreur : ' + (data.message || 'Impossible de changer de lit.'));
            _resetBtn('btnConfirmerInterne', '<i class="bi bi-check2 me-1"></i> Confirmer le changement de lit');
        }
    })
    .catch(() => {
        alert('Erreur réseau.');
        _resetBtn('btnConfirmerInterne', '<i class="bi bi-check2 me-1"></i> Confirmer le changement de lit');
    });
}

/** Confirme le transfert vers un autre service (externe) */
function confirmerTransfertExterne() {
    const serviceId = document.getElementById('externeServiceId').value;
    if (!serviceId) {
        document.getElementById('externeServiceId').classList.add('is-invalid');
        document.getElementById('externeServiceId').focus();
        return;
    }
    document.getElementById('externeServiceId').classList.remove('is-invalid');

    const btn = document.getElementById('btnConfirmerExterne');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Transfert…';

    const fd = new FormData();
    fd.append('service_id', serviceId);
    fd.append('motif',      document.getElementById('externeMotif').value);

    fetch(BASE_URL_HOSP + 'hospitalisation/transferer/' + transfertPatientId, {
        method: 'POST',
        body: fd,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            bootstrap.Modal.getInstance(document.getElementById('modalTransfert')).hide();
            _afficherToast(data.message, 'success');
            setTimeout(() => location.reload(), 1400);
        } else {
            alert('Erreur : ' + (data.message || 'Transfert impossible.'));
            _resetBtn('btnConfirmerExterne', '<i class="bi bi-check2 me-1"></i> Confirmer le transfert');
        }
    })
    .catch(() => {
        alert('Erreur réseau.');
        _resetBtn('btnConfirmerExterne', '<i class="bi bi-check2 me-1"></i> Confirmer le transfert');
    });
}

// ── Helpers internes ─────────────────────────────────────────
function _showTransfertStep(step) {
    const steps = { step1: 'transfertStep1', interne: 'transfertStepInterne', externe: 'transfertStepExterne' };
    Object.entries(steps).forEach(([k, id]) => {
        document.getElementById(id).classList.toggle('d-none', k !== step);
    });
    if (step === 'step1') {
        document.getElementById('transfertSousTitre').textContent = 'Choisissez le type de transfert';
    }
}

function _resetBtn(id, html) {
    const btn = document.getElementById(id);
    if (!btn) return;
    btn.disabled = false;
    btn.innerHTML = html;
}

function _afficherToast(message, type = 'success') {
    const bg = type === 'success' ? 'bg-success' : 'bg-danger';
    const wrap = document.createElement('div');
    wrap.className = 'position-fixed bottom-0 end-0 p-3';
    wrap.style.zIndex = '9999';
    wrap.innerHTML = `<div class="toast show align-items-center text-white ${bg} border-0 rounded-3 shadow">
        <div class="d-flex">
            <div class="toast-body">
                <i class="bi bi-check-circle me-2"></i>${escHtml(message)}
            </div>
        </div>
    </div>`;
    document.body.appendChild(wrap);
    setTimeout(() => wrap.remove(), 3000);
}

// ─────────────────────────────────────────────────────────────
//  MODAL "CRÉER & HOSPITALISER" — lits disponibles
// ─────────────────────────────────────────────────────────────
function chChargerLits(serviceId) {
    const select = document.getElementById('chLitId');
    const loader = document.getElementById('chLitsLoader');

    if (!serviceId) {
        select.innerHTML = '<option value="">— Choisir un service d\'abord —</option>';
        select.disabled  = true;
        return;
    }

    loader.classList.remove('d-none');
    select.disabled  = true;
    select.innerHTML = '';

    fetch(BASE_URL_HOSP + 'hospitalisation/lits-disponibles?service_id=' + serviceId)
        .then(r => r.json())
        .then(lits => {
            loader.classList.add('d-none');
            select.innerHTML = '<option value="">— Aucun lit (non assigné) —</option>';
            if (!Array.isArray(lits) || lits.length === 0) {
                select.innerHTML += '<option value="" disabled>⚠ Aucun lit disponible dans ce service</option>';
            } else {
                lits.forEach(l => {
                    const label = l.nom_chambre ? `${l.nom_lit}  (${l.nom_chambre})` : l.nom_lit;
                    select.innerHTML += `<option value="${l.id}">${escHtml(label)}</option>`;
                });
            }
            select.disabled = false;
        })
        .catch(() => {
            loader.classList.add('d-none');
            select.innerHTML = '<option value="">Erreur de chargement</option>';
        });
}

// ─────────────────────────────────────────────────────────────
//  MODAL "CRÉER & HOSPITALISER" — soumission AJAX
// ─────────────────────────────────────────────────────────────
function sauvegarderCreerHosp() {
    const form   = document.getElementById('formCreerHosp');
    const errDiv = document.getElementById('chErreur');
    const errMsg = document.getElementById('chErreurMsg');
    const btn    = document.getElementById('chBtnSubmit');

    const showErr = msg => {
        errMsg.textContent = msg;
        errDiv.classList.remove('d-none');
        errDiv.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    };

    // Récupérer les valeurs
    const nom       = form.querySelector('[name="nom"]').value.trim();
    const prenom    = form.querySelector('[name="prenom"]').value.trim();
    const sexe      = form.querySelector('[name="sexe"]').value;
    const serviceId = document.getElementById('chServiceId').value;
    const motif     = form.querySelector('[name="motif"]').value.trim();

    // Validation JS
    if (!nom)       { showErr('Le nom du patient est requis.');         return; }
    if (!prenom)    { showErr('Le prénom du patient est requis.');      return; }
    if (!sexe)      { showErr('Le sexe du patient est requis.');        return; }
    if (!serviceId) { showErr('Veuillez sélectionner un service.');     return; }
    if (!motif)     { showErr("Le motif d'hospitalisation est requis."); return; }

    errDiv.classList.add('d-none');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Enregistrement…';

    fetch(BASE_URL_HOSP + 'hospitalisation/creer-et-admettre', {
        method: 'POST',
        body: new FormData(form)
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            bootstrap.Modal.getInstance(document.getElementById('modalCreerHosp')).hide();

            // Toast de succès avec le numéro de dossier
            const toast = document.createElement('div');
            toast.className = 'position-fixed bottom-0 end-0 p-3';
            toast.style.zIndex = 9999;
            toast.innerHTML = `
                <div class="toast show align-items-center text-white bg-success border-0 rounded-3 shadow" style="min-width:320px">
                    <div class="d-flex">
                        <div class="toast-body fw-semibold">
                            <i class="bi bi-check-circle-fill me-2"></i>
                            ${escHtml(data.message)}
                        </div>
                        <button type="button" class="btn-close btn-close-white me-2 m-auto"
                                onclick="this.closest('.toast').remove()"></button>
                    </div>
                </div>`;
            document.body.appendChild(toast);
            setTimeout(() => location.reload(), 1500);
        } else {
            showErr(data.message || 'Erreur lors de l\'enregistrement.');
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-person-plus-fill me-1"></i>CRÉER &amp; HOSPITALISER';
        }
    })
    .catch(() => {
        showErr('Erreur réseau. Veuillez réessayer.');
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-person-plus-fill me-1"></i>CRÉER &amp; HOSPITALISER';
    });
}

// Réinitialiser le modal Créer & Hospitaliser à l'ouverture
document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById('modalCreerHosp');
    if (!modal) return;
    modal.addEventListener('show.bs.modal', function () {
        document.getElementById('formCreerHosp').reset();
        document.getElementById('chDateAdmission').value = new Date().toISOString().slice(0, 16);
        const litSel = document.getElementById('chLitId');
        litSel.innerHTML = '<option value="">— Choisir un service d\'abord —</option>';
        litSel.disabled  = true;
        document.getElementById('chErreur').classList.add('d-none');
        const btn = document.getElementById('chBtnSubmit');
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-person-plus-fill me-1"></i>CRÉER &amp; HOSPITALISER';
    });
});
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>