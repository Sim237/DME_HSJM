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

<!-- Google Fonts & Icones -->
<!-- Animation library -->

<style>
    :root {
        --primary-color: #2563eb;
        --secondary-color: #64748b;
        --bg-color: #f8fafc;
        --surface-color: #ffffff;
        --text-main: #0f172a;
        --soft-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.04), 0 4px 6px -4px rgba(0, 0, 0, 0.04);
    }

    body {
        background-color: var(--bg-color);
        font-family: 'Plus Jakarta Sans', sans-serif;
        color: var(--text-main);
    }

    /* Override sidebar margin if existing */
    main { margin-left: 0 !important; width: 100% !important; }

    /* --- Header Style --- */
    .page-header {
        padding: 40px 0 25px;
        background: white;
        border-bottom: 1px solid #e2e8f0;
        margin-bottom: 0;
    }

    .header-title h2 {
        font-weight: 800;
        color: #0f172a;
        letter-spacing: -1px;
    }

    /* --- Metric Cards --- */
    .metric-card {
        background: white;
        border-radius: 20px;
        padding: 24px;
        border: 1px solid #f1f5f9;
        box-shadow: var(--soft-shadow);
        transition: all 0.3s ease;
    }
    .metric-card:hover { transform: translateY(-5px); box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.08); }

    /* --- Search Input Style --- */
    .search-section {
        max-width: 800px;
        margin: -35px auto 40px;
        position: relative;
        z-index: 10;
    }

    .search-input-group {
        background: white;
        padding: 8px;
        border-radius: 100px;
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
        display: flex;
        align-items: center;
        border: 1px solid #e2e8f0;
    }

    .search-input-group input {
        border: none;
        padding: 12px 25px;
        width: 100%;
        font-size: 1.1rem;
        font-weight: 500;
        outline: none;
    }

    .search-icon-btn {
        background: var(--primary-color);
        color: white;
        width: 54px;
        height: 54px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        border: none;
        transition: 0.3s;
    }

    /* --- Buttons --- */
    .btn-add-patient {
        background: #0f172a;
        color: white;
        padding: 14px 28px;
        border-radius: 14px;
        font-weight: 600;
        border: none;
        transition: 0.3s;
    }
    .btn-add-patient:hover { background: #334155; transform: scale(1.02); color: white; }

    .btn-logout {
        width: 54px; height: 54px;
        background: white;
        color: #ef4444;
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        border: 1px solid #e2e8f0;
        text-decoration: none;
        transition: all 0.3s;
    }
    .btn-logout:hover { background: #fef2f2; transform: translateY(-2px); }

    .btn-rdv-specialiste {
        background: linear-gradient(135deg, #7c3aed, #a855f7);
        color: white;
        padding: 14px 22px;
        border-radius: 14px;
        font-weight: 600;
        border: none;
        transition: 0.3s;
        white-space: nowrap;
    }
    .btn-rdv-specialiste:hover { opacity: 0.88; transform: scale(1.02); color: white; }

    #modalRdvSpecialiste .modal-header {
        background: linear-gradient(135deg, #7c3aed, #a855f7);
        color: white;
        border-radius: 16px 16px 0 0;
    }
    #modalRdvSpecialiste .modal-content { border-radius: 16px; border: none; }
    .quota-badge { font-size: .8rem; font-weight: 700; padding: 4px 12px; border-radius: 20px; }
    .quota-ok   { background: #dcfce7; color: #15803d; }
    .quota-warn { background: #fef9c3; color: #854d0e; }
    .quota-full { background: #fee2e2; color: #b91c1c; }

    /* --- Table Design --- */
    .data-card {
        background: white;
        border-radius: 24px;
        padding: 30px;
        box-shadow: var(--soft-shadow);
        border: 1px solid #f1f5f9;
    }

    .table-modern {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0 12px;
    }

    .table-modern thead th {
        color: var(--secondary-color);
        font-weight: 700;
        text-transform: uppercase;
        font-size: 0.75rem;
        padding: 0 20px;
        border: none;
    }

    .table-modern tbody tr {
        background: #f8fafc;
        transition: 0.2s;
    }

    .table-modern tbody tr td { padding: 20px; border: none; }
    .table-modern tbody tr td:first-child { border-radius: 15px 0 0 15px; }
    .table-modern tbody tr td:last-child { border-radius: 0 15px 15px 0; }

    .table-modern tbody tr:hover { background: #f1f5f9; transform: scale(1.005); }

    .btn-action-visit {
        background: #dcfce7;
        color: #15803d;
        border: none;
        padding: 10px 20px;
        border-radius: 12px;
        font-weight: 700;
        font-size: 0.85rem;
        transition: 0.3s;
    }
    .btn-action-visit:hover { background: #15803d; color: white; }

    /* --- Search Results Dropdown --- */
    #searchResults {
        border-radius: 15px;
        overflow: hidden;
        border: none;
        margin-top: 10px;
    }

    /* --- Quota Panel --- */
    .quota-panel-card {
        border: 1px solid #f1f5f9;
        border-radius: 16px;
        padding: 16px 18px;
        background: white;
        height: 100%;
        transition: box-shadow .2s;
    }
    .quota-panel-card:hover { box-shadow: 0 8px 20px -4px rgba(0,0,0,.08); }
    .quota-panel-title {
        font-size: .92rem;
        font-weight: 700;
        color: #0f172a;
        margin-bottom: 2px;
    }
    .quota-panel-jours {
        font-size: .73rem;
        color: #94a3b8;
        margin-bottom: 10px;
    }
    .qp-row { margin-bottom: 8px; }
    .qp-label {
        font-size: .72rem;
        font-weight: 600;
        color: #64748b;
        margin-bottom: 3px;
    }
    .qp-bar { height: 6px; border-radius: 99px; background: #f1f5f9; overflow: hidden; }
    .qp-bar-fill { height: 100%; border-radius: 99px; transition: width .4s; }
    .qp-count { font-size: .72rem; font-weight: 700; }
    .qp-ok    { color: #15803d; }
    .qp-warn  { color: #854d0e; }
    .qp-full  { color: #b91c1c; }
    .qp-fill-ok   { background: #22c55e; }
    .qp-fill-warn { background: #f59e0b; }
    .qp-fill-full { background: #ef4444; }
</style>

<!-- HEADER SECTION -->
<div class="page-header">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <small class="text-primary fw-bold text-uppercase" style="letter-spacing: 2px;">Tableau de bord</small>
                <h2 class="mb-0">Bonjour, <?= $_SESSION['user_nom'] ?> 👋</h2>
            </div>

            <div class="d-flex align-items-center gap-3">
                <div class="text-end d-none d-md-block me-2">
                    <span class="d-block fw-bold fs-5 text-dark" id="liveTime">00:00:00</span>
                    <small class="text-muted fw-semibold"><?= date('l d F Y') ?></small>
                </div>
                <button class="btn btn-rdv-specialiste shadow-sm" data-bs-toggle="modal" data-bs-target="#modalRdvSpecialiste">
                    <i class="bi bi-calendar2-plus me-2"></i> RDV Spécialiste
                </button>
                <button class="btn btn-add-patient shadow-sm" data-bs-toggle="modal" data-bs-target="#modalNouveauPatient">
                    <i class="bi bi-person-plus-fill me-2"></i> Nouveau Patient
                </button>
                <a href="<?= BASE_URL ?>logout" class="btn btn-logout shadow-sm" title="Se déconnecter">
                    <i class="bi bi-power fs-4"></i>
                </a>
            </div>
        </div>
    </div>
</div>

<div class="container mt-4">

    <!-- NOTIFICATION DE SUCCÈS -->
    <?php if(isset($_GET['ticket'])): ?>
    <?php
        $isPediatrie   = !empty($_GET['pediatrie']);
        $isVisite      = (($_GET['success'] ?? '') === 'visite_demarree');
        $alertBg       = $isPediatrie ? '#eff6ff' : '#f0fdf4';
        $alertBorder   = $isPediatrie ? '#3b82f6' : '#22c55e';
        $iconClass     = $isPediatrie ? 'bi-heart-pulse-fill text-primary' : 'bi-check-circle-fill text-success';
        $titleColor    = $isPediatrie ? 'text-primary' : 'text-success';
        if ($isVisite) {
            $titre = 'Nouvelle visite démarrée !';
            $sousTitre = 'Le patient a été redirigé vers les paramètres.';
        } elseif ($isPediatrie) {
            $titre = 'Patient pédiatrique enregistré !';
            $sousTitre = 'Envoyé automatiquement dans la file d\'attente de tous les médecins pédiatres.';
        } else {
            $titre = 'Patient enregistré avec succès !';
            $sousTitre = 'Envoyé au bureau des paramètres.';
        }
    ?>
        <div class="alert border-0 shadow-sm rounded-4 d-flex align-items-center p-4 mb-4"
             style="background:<?= $alertBg ?>; border-left:4px solid <?= $alertBorder ?> !important;">
            <div class="rounded-circle bg-white p-3 me-4 shadow-sm flex-shrink-0">
                <i class="bi <?= $iconClass ?> fs-3"></i>
            </div>
            <div class="flex-grow-1">
                <h5 class="fw-bold mb-1 <?= $titleColor ?>">
                    <?= $titre ?>
                    <?php if ($isPediatrie): ?>
                    <span class="badge ms-2 rounded-pill" style="background:#3b82f6;font-size:.65rem;vertical-align:middle;">
                        <i class="bi bi-star-fill me-1"></i>PÉDIATRIE
                    </span>
                    <?php endif; ?>
                </h5>
                <p class="mb-0 text-dark">
                    <?= $sousTitre ?> &nbsp;|&nbsp;
                    Dossier : <strong><?= htmlspecialchars($_GET['dossier'] ?? '') ?></strong> &nbsp;|&nbsp;
                    Ticket : <span class="badge bg-dark fs-6 rounded-pill px-3"><?= (int)$_GET['ticket'] ?></span>
                </p>
            </div>
        </div>
    <?php elseif(isset($_GET['error'])): ?>
        <div class="alert alert-danger border-0 shadow-sm rounded-4 p-4 mb-4">
            <i class="bi bi-exclamation-circle-fill me-2"></i>
            <?php if (!empty($_SESSION['flash_error'])): ?>
                <?= $_SESSION['flash_error'] ?>
                <?php unset($_SESSION['flash_error']); ?>
            <?php else: ?>
                Une erreur est survenue. Veuillez réessayer.
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- STATS RAPIDES -->
    <div class="row g-4 mb-5">
        <div class="col-md-4">
            <div class="metric-card d-flex align-items-center gap-3">
                <div class="rounded-circle bg-primary bg-opacity-10 p-3">
                    <i class="bi bi-calendar-check text-primary fs-3"></i>
                </div>
                <div>
                    <h3 class="mb-0 fw-bold"><?= count($rdvs) ?></h3>
                    <small class="text-muted fw-semibold">Rendez-vous du jour</small>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="metric-card d-flex align-items-center gap-3">
                <div class="rounded-circle bg-success bg-opacity-10 p-3">
                    <i class="bi bi-people text-success fs-3"></i>
                </div>
                <div>
                    <h3 class="mb-0 fw-bold">--</h3>
                    <small class="text-muted fw-semibold">Admissions totales</small>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="metric-card d-flex align-items-center gap-3">
                <div class="rounded-circle bg-warning bg-opacity-10 p-3">
                    <i class="bi bi-clock-history text-warning fs-3"></i>
                </div>
                <div>
                    <h3 class="mb-0 fw-bold">Bureau Tri</h3>
                    <small class="text-muted fw-semibold">Distribution active</small>
                </div>
            </div>
        </div>
    </div>

    <!-- ZONE DE RECHERCHE DYNAMIQUE -->
    <div class="search-section">
        <div class="search-input-group">
            <input type="text" id="mainSearch" placeholder="Rechercher un dossier (Nom, N°, Téléphone)..." autocomplete="off">
            <button class="search-icon-btn shadow-sm">
                <i class="bi bi-search"></i>
            </button>
        </div>
        <!-- Résultats AJAX -->
        <div id="searchResults" class="list-group mt-2 shadow-lg d-none position-absolute w-100" style="z-index: 1000;"></div>
    </div>

    <!-- PANNEAU QUOTAS SPÉCIALISTES -->
    <div class="data-card mb-4" id="quotasPanel">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h5 class="fw-bold mb-0">
                <i class="bi bi-bar-chart-line me-2" style="color:#7c3aed;"></i>
                Quotas spécialistes
            </h5>
            <div class="d-flex align-items-center gap-2">
                <input type="date" id="quotasDatePicker" class="form-control form-control-sm rounded-pill"
                       value="<?= date('Y-m-d') ?>" style="width:160px;">
                <button class="btn btn-sm rounded-pill px-3 fw-semibold"
                        style="background:#f1f5f9; color:#475569;"
                        onclick="loadQuotas()">
                    <i class="bi bi-arrow-clockwise me-1"></i>Actualiser
                </button>
            </div>
        </div>
        <div id="quotasContainer">
            <div class="text-center py-4 text-muted">
                <div class="spinner-border spinner-border-sm me-2" style="color:#7c3aed;"></div>
                Chargement des quotas&hellip;
            </div>
        </div>
    </div>

    <!-- LISTE DES RENDEZ-VOUS -->
    <div class="data-card">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h5 class="fw-bold mb-0"><i class="bi bi-list-stars me-2 text-primary"></i>File d'attente des rendez-vous</h5>
            <span class="badge bg-light text-muted border px-3">Date : <?= date('d/m/Y') ?></span>
        </div>

        <div class="table-responsive">
            <table class="table-modern">
                <thead>
                    <tr>
                        <th>Heure</th>
                        <th>Patient</th>
                        <th>Motif de la visite</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($rdvs)): ?>
                        <tr>
                            <td colspan="4" class="text-center py-5 text-muted">
                                <i class="bi bi-calendar-x fs-1 opacity-25"></i><br>
                                Aucun rendez-vous programmé pour le moment.
                            </td>
                        </tr>
                    <?php else: foreach($rdvs as $r): ?>
                        <tr>
                            <td><span class="fw-bold text-primary"><?= date('H:i', strtotime($r['date_rdv'])) ?></span></td>
                            <td>
                                <div class="fw-bold"><?= strtoupper($r['nom']) ?> <?= $r['prenom'] ?></div>
                                <small class="text-muted"><?= $r['dossier_numero'] ?></small>
                            </td>
                            <td><span class="text-secondary"><?= $r['motif'] ?></span></td>
                            <td class="text-end">
                                <button class="btn-action-visit" onclick="startVisit(<?= $r['id'] ?>)">
                                    Lancer Visite <i class="bi bi-arrow-right-short ms-1"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Inclusion de la modale soft -->
<?php include __DIR__ . '/modal_nouveau_patient.php'; ?>

<!-- MODALE RDV SPÉCIALISTE -->
<div class="modal fade" id="modalRdvSpecialiste" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content shadow-lg">
      <div class="modal-header py-3 px-4">
        <h5 class="modal-title fw-bold">
          <i class="bi bi-calendar2-plus me-2"></i> Prendre un RDV chez le Sp&eacute;cialiste
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-4">
        <!-- Recherche patient -->
        <div class="mb-4">
          <label class="form-label fw-semibold">
            <i class="bi bi-search me-1"></i> Rechercher le patient
          </label>
          <div class="position-relative">
            <input type="text" id="spSearchPatient" class="form-control form-control-lg rounded-3"
                   placeholder="Nom, num&eacute;ro de dossier ou t&eacute;l&eacute;phone..." autocomplete="off">
            <div id="spSearchResults" class="list-group position-absolute w-100 shadow-lg d-none"
                 style="z-index:1055; top:100%; border-radius:12px; overflow:hidden;"></div>
          </div>
          <div id="spPatientSelected" class="d-none mt-3 p-3 rounded-3"
               style="background:#faf5ff; border:1px solid #a855f7;">
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <div class="fw-bold" id="spPatientNom"></div>
                <small class="text-muted" id="spPatientInfo"></small>
              </div>
              <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill"
                      onclick="spResetPatient()"><i class="bi bi-x"></i> Changer</button>
            </div>
            <input type="hidden" id="spPatientId" value="">
          </div>
        </div>
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label fw-semibold"><i class="bi bi-person-badge me-1"></i> Sp&eacute;cialiste</label>
            <select id="spSpecialisteSelect" class="form-select form-select-lg rounded-3">
              <option value="">&mdash; Choisir un sp&eacute;cialiste &mdash;</option>
            </select>
            <small class="text-muted mt-1 d-block" id="spJoursConsult"></small>
          </div>
          <div class="col-md-6">
            <label class="form-label fw-semibold"><i class="bi bi-calendar3 me-1"></i> Date du RDV</label>
            <input type="date" id="spDateRdv" class="form-control form-control-lg rounded-3"
                   min="<?= date('Y-m-d') ?>">
            <div id="spQuotaInfo" class="mt-2 d-none"></div>
          </div>
          <div class="col-md-4">
            <label class="form-label fw-semibold"><i class="bi bi-clock me-1"></i> Heure <small class="text-muted">(optionnel)</small></label>
            <input type="time" id="spHeureRdv" class="form-control rounded-3">
          </div>
          <div class="col-md-8">
            <label class="form-label fw-semibold"><i class="bi bi-chat-text me-1"></i> Motif</label>
            <input type="text" id="spMotif" class="form-control rounded-3"
                   placeholder="Ex : douleur abdominale, contr&ocirc;le annuel...">
          </div>
        </div>
        <div id="spQuotaAlert" class="alert alert-warning rounded-3 mt-3 d-none">
          <i class="bi bi-exclamation-triangle-fill me-2"></i>
          <strong>Quota atteint</strong> pour ce sp&eacute;cialiste &agrave; cette date. Vous pouvez tout de m&ecirc;me enregistrer le RDV.
        </div>
      </div>
      <div class="modal-footer px-4 pb-4 border-0">
        <button type="button" class="btn btn-light rounded-3 px-4" data-bs-dismiss="modal">Annuler</button>
        <button type="button" id="spBtnSubmit"
                class="btn px-4 fw-bold rounded-3 text-white"
                style="background:linear-gradient(135deg,#7c3aed,#a855f7);"
                onclick="spEnregistrerRdv()">
          <i class="bi bi-check-circle me-2"></i> Enregistrer le RDV
        </button>
      </div>
    </div>
  </div>
</div>

<script>
    // 1. Horloge Dynamique
    function updateTime() {
        const now = new Date();
        document.getElementById('liveTime').innerText = now.toLocaleTimeString('fr-FR');
    }
    setInterval(updateTime, 1000);
    updateTime();

    // 2. Recherche Dynamique AJAX
    const searchInput = document.getElementById('mainSearch');
    const resultsBox = document.getElementById('searchResults');

    searchInput.addEventListener('input', function() {
        const query = this.value.trim();
        if (query.length < 2) {
            resultsBox.classList.add('d-none');
            return;
        }

        fetch('<?= BASE_URL ?>consultation/search-patients?q=' + encodeURIComponent(query))
            .then(res => res.json())
            .then(data => {
                if (data.length > 0) {
                    let html = '';
                    data.forEach(p => {
                        const age = p.date_naissance
                            ? Math.floor((Date.now() - new Date(p.date_naissance)) / 3.15576e10) + ' ans'
                            : '';
                        html += `
                        <div class="list-group-item d-flex justify-content-between align-items-center p-3 border-0 border-bottom">
                            <div class="flex-grow-1" style="min-width:0;">
                                <div class="fw-bold text-dark">${p.nom} ${p.prenom}</div>
                                <small class="text-muted">${p.dossier_numero}${age ? ' · ' + age : ''} · ${p.telephone || 'Pas de tél'}</small>
                            </div>
                            <div class="d-flex gap-2 flex-shrink-0 ms-3">
                                <button type="button"
                                    onclick="ouvrirEditionPatient(${p.id}); document.getElementById('searchResults').classList.add('d-none');"
                                    class="btn btn-sm px-3 fw-semibold rounded-pill"
                                    style="background:#eff6ff;color:#2563eb;border:1px solid #bfdbfe;white-space:nowrap;">
                                    <i class="bi bi-pencil-square me-1"></i>Modifier
                                </button>
                                <button type="button"
                                    onclick="ouvrirAncienPatient(${p.id}); document.getElementById('searchResults').classList.add('d-none'); document.getElementById('mainSearch').value='';"
                                    class="btn btn-sm px-3 fw-semibold rounded-pill"
                                    style="background:#f0fdf4;color:#16a34a;border:1px solid #bbf7d0;white-space:nowrap;">
                                    <i class="bi bi-person-check me-1"></i>Nouvelle visite
                                </button>
                            </div>
                        </div>`;
                    });
                    resultsBox.innerHTML = html;
                    resultsBox.classList.remove('d-none');
                } else {
                    resultsBox.innerHTML = `
                        <div class="list-group-item p-3 text-center">
                            <div class="text-muted mb-2">Aucun dossier trouvé pour "<strong>${query}</strong>"</div>
                            <button type="button" class="btn btn-sm btn-primary rounded-pill px-4"
                                onclick="document.getElementById('searchResults').classList.add('d-none');
                                         document.getElementById('mainSearch').value='';
                                         new bootstrap.Modal(document.getElementById('modalNouveauPatient')).show();">
                                <i class="bi bi-person-plus-fill me-1"></i> Créer un nouveau dossier
                            </button>
                        </div>`;
                    resultsBox.classList.remove('d-none');
                }
            });
    });

    // Fermer les résultats si on clique ailleurs
    document.addEventListener('click', (e) => {
        if (!searchInput.contains(e.target)) resultsBox.classList.add('d-none');
    });

    // 3. Action Commencer la visite
    function startVisit(id) {
        if(confirm("Confirmer l'arrivée du patient et générer son ticket de rang ?")) {
            window.location.href = "<?= BASE_URL ?>accueil/commencer-visite/" + id;
        }
    }

    // =====================================================================
    // MODULE RDV SPÉCIALISTE
    // =====================================================================
    document.getElementById('modalRdvSpecialiste').addEventListener('show.bs.modal', function () {
        fetch('<?= BASE_URL ?>specialiste/get-specialistes')
            .then(r => r.json())
            .then(data => {
                const sel = document.getElementById('spSpecialisteSelect');
                sel.innerHTML = '<option value="">— Choisir un spécialiste —</option>';
                data.forEach(sp => {
                    const opt = document.createElement('option');
                    opt.value = sp.id;
                    opt.dataset.jours = sp.jours_consultation;
                    opt.dataset.quota = sp.quota_max;
                    opt.textContent = sp.specialite.replace(/_/g,' ') + ' — ' + sp.prenom + ' ' + sp.nom;
                    sel.appendChild(opt);
                });
            });
        spReset();
    });

    let spSearchTimer;
    document.getElementById('spSearchPatient').addEventListener('input', function () {
        clearTimeout(spSearchTimer);
        const q = this.value.trim();
        if (q.length < 2) { document.getElementById('spSearchResults').classList.add('d-none'); return; }
        spSearchTimer = setTimeout(() => {
            fetch('<?= BASE_URL ?>consultation/search-patients?q=' + encodeURIComponent(q))
                .then(r => r.json())
                .then(data => {
                    const box = document.getElementById('spSearchResults');
                    if (!data.length) {
                        box.innerHTML = '<div class="list-group-item text-muted p-3">Aucun dossier trouvé.</div>';
                    } else {
                        box.innerHTML = data.map(p => `
                            <button type="button" class="list-group-item list-group-item-action p-3 border-0 border-bottom"
                                    onclick="spSelectPatient(${p.id},'${p.nom} ${p.prenom}','${p.dossier_numero} \u2022 ${p.telephone||''}')">
                                <div class="fw-bold text-dark">${p.nom} ${p.prenom}</div>
                                <small class="text-muted">${p.dossier_numero} \u2022 ${p.telephone||'\u2014'}</small>
                            </button>`).join('');
                    }
                    box.classList.remove('d-none');
                });
        }, 300);
    });

    document.addEventListener('click', e => {
        if (!e.target.closest('#spSearchPatient') && !e.target.closest('#spSearchResults'))
            document.getElementById('spSearchResults').classList.add('d-none');
    });

    function spSelectPatient(id, nom, info) {
        document.getElementById('spPatientId').value = id;
        document.getElementById('spPatientNom').textContent = nom;
        document.getElementById('spPatientInfo').textContent = info;
        document.getElementById('spPatientSelected').classList.remove('d-none');
        document.getElementById('spSearchPatient').closest('.position-relative').style.display = 'none';
        document.getElementById('spSearchResults').classList.add('d-none');
    }

    function spResetPatient() {
        document.getElementById('spPatientId').value = '';
        document.getElementById('spPatientSelected').classList.add('d-none');
        document.getElementById('spSearchPatient').closest('.position-relative').style.display = '';
        document.getElementById('spSearchPatient').value = '';
    }

    document.getElementById('spSpecialisteSelect').addEventListener('change', function () {
        const opt = this.options[this.selectedIndex];
        const jours = opt.dataset.jours || '';
        document.getElementById('spJoursConsult').textContent = jours
            ? '\uD83D\uDCC5 Consultations : ' + jours.replace(/,/g, ', ') : '';
        spCheckQuota();
    });

    document.getElementById('spDateRdv').addEventListener('change', spCheckQuota);

    function spCheckQuota() {
        const spId = document.getElementById('spSpecialisteSelect').value;
        const date = document.getElementById('spDateRdv').value;
        const quotaInfo  = document.getElementById('spQuotaInfo');
        const quotaAlert = document.getElementById('spQuotaAlert');
        if (!spId || !date) { quotaInfo.classList.add('d-none'); quotaAlert.classList.add('d-none'); return; }
        fetch(`<?= BASE_URL ?>specialiste/check-quota?specialiste_id=${spId}&date=${date}`)
            .then(r => r.json())
            .then(data => {
                if (!data.success) return;
                quotaInfo.classList.remove('d-none');

                function badge(dispo, quota) {
                    if (dispo === 0)   return `<span class="quota-badge quota-full">Complet (${quota})</span>`;
                    if (dispo <= 3)    return `<span class="quota-badge quota-warn">${dispo} libre / ${quota}</span>`;
                    return               `<span class="quota-badge quota-ok">${dispo} libre / ${quota}</span>`;
                }

                quotaInfo.innerHTML = `
                    <div class="d-flex flex-wrap gap-2 mt-1">
                        <div class="d-flex align-items-center gap-1">
                            <small class="text-muted fw-semibold">Payant Comptant</small>
                            ${badge(data.dispo_payant_comptant, data.quota_payant_comptant)}
                        </div>
                        <div class="d-flex align-items-center gap-1">
                            <small class="text-muted fw-semibold">Famille PHP</small>
                            ${badge(data.dispo_famille_php, data.quota_famille_php)}
                        </div>
                        <div class="d-flex align-items-center gap-1">
                            <small class="text-muted fw-semibold">Agent PHP</small>
                            ${badge(data.dispo_agent_php, data.quota_agent_php)}
                        </div>
                        <div class="d-flex align-items-center gap-1">
                            <small class="text-muted fw-semibold">Hospitalisation</small>
                            ${badge(data.dispo_hospitalisation, data.quota_hospitalisation)}
                        </div>
                    </div>`;

                // Alerte si TOUS les circuits sont complets
                const totalDispo = data.dispo_payant_comptant + data.dispo_famille_php
                                 + data.dispo_agent_php + data.dispo_hospitalisation;
                if (totalDispo <= 0) {
                    quotaAlert.classList.remove('d-none');
                } else {
                    quotaAlert.classList.add('d-none');
                }
            });
    }

    function spEnregistrerRdv() {
        const patientId     = document.getElementById('spPatientId').value;
        const specialisteId = document.getElementById('spSpecialisteSelect').value;
        const dateRdv       = document.getElementById('spDateRdv').value;
        const heureRdv      = document.getElementById('spHeureRdv').value;
        const motif         = document.getElementById('spMotif').value;

        if (!patientId)     { alert('Veuillez s\u00E9lectionner un patient.'); return; }
        if (!specialisteId) { alert('Veuillez choisir un sp\u00E9cialiste.'); return; }
        if (!dateRdv)       { alert('Veuillez choisir une date.'); return; }

        const btn = document.getElementById('spBtnSubmit');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Enregistrement...';

        const fd = new FormData();
        fd.append('patient_id',     patientId);
        fd.append('specialiste_id', specialisteId);
        fd.append('date_rdv',       dateRdv);
        fd.append('heure_rdv',      heureRdv);
        fd.append('motif',          motif);

        fetch('<?= BASE_URL ?>specialiste/store-rdv-accueil', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    bootstrap.Modal.getInstance(document.getElementById('modalRdvSpecialiste')).hide();
                    spReset();
                    const toast = document.createElement('div');
                    toast.className = 'alert alert-success border-0 shadow position-fixed bottom-0 end-0 m-4 rounded-4 d-flex align-items-center gap-3';
                    toast.style.zIndex = 9999;
                    toast.innerHTML = `<i class="bi bi-check-circle-fill fs-4"></i>
                        <div><strong>RDV enregistr\u00E9 !</strong><br><small>${data.message}</small></div>`;
                    document.body.appendChild(toast);
                    setTimeout(() => toast.remove(), 4000);
                } else {
                    alert('Erreur : ' + data.message);
                }
            })
            .finally(() => {
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-check-circle me-2"></i> Enregistrer le RDV';
            });
    }

    function spReset() {
        spResetPatient();
        document.getElementById('spSpecialisteSelect').value = '';
        document.getElementById('spJoursConsult').textContent = '';
        document.getElementById('spDateRdv').value = '';
        document.getElementById('spHeureRdv').value = '';
        document.getElementById('spMotif').value = '';
        const qi = document.getElementById('spQuotaInfo');
        qi.classList.add('d-none');
        qi.innerHTML = '';
        document.getElementById('spQuotaAlert').classList.add('d-none');
    }

    // =====================================================================
    // PANNEAU QUOTAS SPÉCIALISTES
    // =====================================================================
    function loadQuotas() {
        const date = document.getElementById('quotasDatePicker').value;
        if (!date) return;
        document.getElementById('quotasContainer').innerHTML =
            '<div class="text-center py-4 text-muted"><div class="spinner-border spinner-border-sm me-2" style="color:#7c3aed;"></div>Chargement&hellip;</div>';

        fetch('<?= BASE_URL ?>specialiste/quotas-du-jour?date=' + encodeURIComponent(date))
            .then(r => r.json())
            .then(data => {
                const container = document.getElementById('quotasContainer');
                if (!data.length) {
                    container.innerHTML = '<div class="text-muted text-center py-3">Aucun spécialiste actif.</div>';
                    return;
                }

                function qClass(dispo, quota) {
                    if (dispo === 0)         return 'full';
                    if (dispo <= Math.max(1, Math.floor(quota * .15))) return 'warn';
                    return 'ok';
                }
                function barPct(pris, quota) {
                    return quota > 0 ? Math.min(100, Math.round(pris / quota * 100)) : 0;
                }
                function circuitRow(label, pris, quota, dispo) {
                    const cls = qClass(dispo, quota);
                    const pct = barPct(pris, quota);
                    const counts = dispo > 0
                        ? `<span class="qp-count qp-${cls}">${dispo}&nbsp;libre / ${quota}</span>`
                        : `<span class="qp-count qp-full">Complet (${quota})</span>`;
                    return `<div class="qp-row">
                        <div class="d-flex justify-content-between align-items-center qp-label">
                            <span>${label}</span>${counts}
                        </div>
                        <div class="qp-bar"><div class="qp-bar-fill qp-fill-${cls}" style="width:${pct}%"></div></div>
                    </div>`;
                }

                let html = '<div class="row g-3">';
                data.forEach(sp => {
                    html += `<div class="col-sm-6 col-xl-3">
                        <div class="quota-panel-card">
                            <div class="quota-panel-title">${sp.specialite}</div>
                            <div class="quota-panel-jours"><i class="bi bi-calendar3 me-1"></i>${sp.jours.replace(/,/g, ', ')}</div>
                            ${circuitRow('Payant Comptant (40%)', sp.p_payant, sp.q_payant, sp.d_payant)}
                            ${circuitRow('Famille PHP (25%)',     sp.p_fam,    sp.q_fam,    sp.d_fam)}
                            ${circuitRow('Agent PHP (25%)',       sp.p_agent,  sp.q_agent,  sp.d_agent)}
                            ${circuitRow('Hospitalisation (10%)', sp.p_hosp,   sp.q_hosp,   sp.d_hosp)}
                        </div>
                    </div>`;
                });
                html += '</div>';
                container.innerHTML = html;
            })
            .catch(() => {
                document.getElementById('quotasContainer').innerHTML =
                    '<div class="text-danger text-center py-3"><i class="bi bi-exclamation-circle me-1"></i>Erreur de chargement des quotas.</div>';
            });
    }

    document.getElementById('quotasDatePicker').addEventListener('change', loadQuotas);
    loadQuotas();                         // Chargement initial
    setInterval(loadQuotas, 60000);       // Rafraîchissement auto toutes les 60 s
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
