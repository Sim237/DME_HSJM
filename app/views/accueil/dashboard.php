<?php require_once __DIR__ . '/../layouts/header.php'; ?>

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

    /* Modal spécialiste */
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

    <!-- NOTIFICATION DE SUCCÈS (TICKET GÉNÉRÉ) -->
    <?php if(isset($_GET['ticket'])): ?>
        <div class="alert alert-success border-0 shadow-sm rounded-4 d-flex align-items-center p-4 mb-4 animate__animated animate__fadeInDown">
            <div class="rounded-circle bg-white p-3 me-4 shadow-sm">
                <i class="bi bi-check-lg text-success fs-3"></i>
            </div>
            <div>
                <h5 class="fw-bold mb-1">Patient envoyé aux paramètres !</h5>
                <p class="mb-0">Dossier : <strong><?= $_GET['dossier'] ?></strong> |
                   Ticket de rang : <span class="badge bg-dark fs-6 rounded-pill px-3"><?= $_GET['ticket'] ?></span>
                </p>
            </div>
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

<!-- Inclusion de la modale nouveau patient -->
<?php include __DIR__ . '/modal_nouveau_patient.php'; ?>

<!-- =====================================================================
     MODALE : PRENDRE RDV CHEZ LE SPÉCIALISTE
====================================================================== -->
<div class="modal fade" id="modalRdvSpecialiste" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content shadow-lg">

      <div class="modal-header py-3 px-4">
        <h5 class="modal-title fw-bold">
          <i class="bi bi-calendar2-plus me-2"></i> Prendre un RDV chez le Spécialiste
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body p-4">
        <!-- ÉTAPE 1 : Recherche patient -->
        <div class="mb-4">
          <label class="form-label fw-semibold text-dark">
            <i class="bi bi-search me-1 text-purple"></i> Rechercher le patient
          </label>
          <div class="position-relative">
            <input type="text" id="spSearchPatient" class="form-control form-control-lg rounded-3"
                   placeholder="Nom, numéro de dossier ou téléphone..." autocomplete="off">
            <div id="spSearchResults" class="list-group position-absolute w-100 shadow-lg d-none"
                 style="z-index:1055; top:100%; border-radius:12px; overflow:hidden;"></div>
          </div>
          <!-- Patient sélectionné -->
          <div id="spPatientSelected" class="d-none mt-3 p-3 rounded-3 border border-purple"
               style="background:#faf5ff; border-color:#a855f7!important;">
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <div class="fw-bold text-dark" id="spPatientNom">—</div>
                <small class="text-muted" id="spPatientInfo">—</small>
              </div>
              <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill"
                      onclick="spResetPatient()">
                <i class="bi bi-x"></i> Changer
              </button>
            </div>
            <input type="hidden" id="spPatientId" value="">
          </div>
        </div>

        <div class="row g-3">
          <!-- ÉTAPE 2 : Spécialiste -->
          <div class="col-md-6">
            <label class="form-label fw-semibold text-dark">
              <i class="bi bi-person-badge me-1"></i> Spécialiste
            </label>
            <select id="spSpecialisteSelect" class="form-select form-select-lg rounded-3"
                    onchange="spCheckQuota()">
              <option value="">— Choisir un spécialiste —</option>
            </select>
            <small class="text-muted" id="spJoursConsult"></small>
          </div>

          <!-- ÉTAPE 3 : Date -->
          <div class="col-md-6">
            <label class="form-label fw-semibold text-dark">
              <i class="bi bi-calendar3 me-1"></i> Date du RDV
            </label>
            <input type="date" id="spDateRdv" class="form-control form-control-lg rounded-3"
                   min="<?= date('Y-m-d') ?>" onchange="spCheckQuota()">
            <!-- Quota -->
            <div id="spQuotaInfo" class="mt-2 d-none">
              <span id="spQuotaBadge" class="quota-badge quota-ok">—</span>
            </div>
          </div>

          <!-- Heure (optionnelle) -->
          <div class="col-md-4">
            <label class="form-label fw-semibold text-dark">
              <i class="bi bi-clock me-1"></i> Heure <small class="text-muted">(optionnel)</small>
            </label>
            <input type="time" id="spHeureRdv" class="form-control rounded-3">
          </div>

          <!-- Motif -->
          <div class="col-md-8">
            <label class="form-label fw-semibold text-dark">
              <i class="bi bi-chat-text me-1"></i> Motif de la consultation
            </label>
            <input type="text" id="spMotif" class="form-control rounded-3"
                   placeholder="Ex : douleur abdominale, contrôle annuel...">
          </div>
        </div>

        <!-- Alerte quota plein -->
        <div id="spQuotaAlert" class="alert alert-warning rounded-3 mt-3 d-none">
          <i class="bi bi-exclamation-triangle-fill me-2"></i>
          <strong>Quota atteint</strong> pour ce spécialiste à cette date. Vous pouvez tout de même enregistrer le RDV.
        </div>
      </div>

      <div class="modal-footer px-4 pb-4 border-0">
        <button type="button" class="btn btn-light rounded-3 px-4" data-bs-dismiss="modal">Annuler</button>
        <button type="button" class="btn px-4 fw-bold rounded-3 text-white"
                style="background:linear-gradient(135deg,#7c3aed,#a855f7);"
                onclick="spEnregistrerRdv()" id="spBtnSubmit">
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
                let html = "";
                if (data.length > 0) {
                    data.forEach(p => {
                        html += `
                        <a href="<?= BASE_URL ?>accueil/commencer-visite/${p.id}" class="list-group-item list-group-item-action d-flex justify-content-between p-3 border-0 border-bottom">
                            <div>
                                <div class="fw-bold text-dark">${p.nom} ${p.prenom}</div>
                                <small class="text-muted">${p.dossier_numero} • ${p.telephone || 'Pas de tel'}</small>
                            </div>
                            <span class="badge bg-primary bg-opacity-10 text-primary rounded-pill align-self-center px-3 py-2">Démarrer Visite</span>
                        </a>`;
                    });
                    resultsBox.innerHTML = html;
                    resultsBox.classList.remove('d-none');
                } else {
                    resultsBox.innerHTML = '<div class="list-group-item text-muted p-3">Aucun dossier trouvé.</div>';
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

    // Charger la liste des spécialistes à l'ouverture de la modale
    document.getElementById('modalRdvSpecialiste').addEventListener('show.bs.modal', function () {
        fetch('<?= BASE_URL ?>specialiste/get-specialistes')
            .then(r => r.json())
            .then(data => {
                const sel = document.getElementById('spSpecialisteSelect');
                sel.innerHTML = '<option value="">— Choisir un spécialiste —</option>';
                data.forEach(sp => {
                    sel.innerHTML += `<option value="${sp.id}"
                        data-jours="${sp.jours_consultation}"
                        data-quota="${sp.quota_max}">
                        ${sp.specialite.replace(/_/g,' ')} — ${sp.prenom} ${sp.nom}
                    </option>`;
                });
            });
        spReset();
    });

    // Recherche patient dans la modale spécialiste
    let spSearchTimer;
    document.getElementById('spSearchPatient').addEventListener('input', function () {
        clearTimeout(spSearchTimer);
        const q = this.value.trim();
        if (q.length < 2) {
            document.getElementById('spSearchResults').classList.add('d-none');
            return;
        }
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
                                    onclick="spSelectPatient(${p.id},'${p.nom} ${p.prenom}','${p.dossier_numero} • ${p.telephone||''}')">
                                <div class="fw-bold text-dark">${p.nom} ${p.prenom}</div>
                                <small class="text-muted">${p.dossier_numero} • ${p.telephone||'—'}</small>
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

    // Afficher les jours de consultation du spécialiste sélectionné
    document.getElementById('spSpecialisteSelect').addEventListener('change', function () {
        const opt = this.options[this.selectedIndex];
        const jours = opt.dataset.jours || '';
        document.getElementById('spJoursConsult').textContent = jours
            ? '📅 Consultations : ' + jours.replace(/,/g, ', ')
            : '';
        spCheckQuota();
    });

    // Vérifier le quota disponible
    function spCheckQuota() {
        const spId = document.getElementById('spSpecialisteSelect').value;
        const date = document.getElementById('spDateRdv').value;
        const quotaInfo  = document.getElementById('spQuotaInfo');
        const quotaBadge = document.getElementById('spQuotaBadge');
        const quotaAlert = document.getElementById('spQuotaAlert');

        if (!spId || !date) { quotaInfo.classList.add('d-none'); quotaAlert.classList.add('d-none'); return; }

        fetch(`<?= BASE_URL ?>specialiste/check-quota?specialiste_id=${spId}&date=${date}`)
            .then(r => r.json())
            .then(data => {
                if (data.error) return;
                quotaInfo.classList.remove('d-none');
                if (data.complet) {
                    quotaBadge.className = 'quota-badge quota-full';
                    quotaBadge.textContent = `Complet : ${data.pris}/${data.quota_max} patients`;
                    quotaAlert.classList.remove('d-none');
                } else if (data.disponible <= 5) {
                    quotaBadge.className = 'quota-badge quota-warn';
                    quotaBadge.textContent = `${data.disponible} place(s) restante(s) sur ${data.quota_max}`;
                    quotaAlert.classList.add('d-none');
                } else {
                    quotaBadge.className = 'quota-badge quota-ok';
                    quotaBadge.textContent = `${data.disponible} places disponibles sur ${data.quota_max}`;
                    quotaAlert.classList.add('d-none');
                }
            });
    }

    // Enregistrer le RDV
    function spEnregistrerRdv() {
        const patientId  = document.getElementById('spPatientId').value;
        const specialisteId = document.getElementById('spSpecialisteSelect').value;
        const dateRdv    = document.getElementById('spDateRdv').value;
        const heureRdv   = document.getElementById('spHeureRdv').value;
        const motif      = document.getElementById('spMotif').value;

        if (!patientId)     { alert('Veuillez sélectionner un patient.'); return; }
        if (!specialisteId) { alert('Veuillez choisir un spécialiste.'); return; }
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
                    // Toast succès
                    const toast = document.createElement('div');
                    toast.className = 'alert alert-success border-0 shadow position-fixed bottom-0 end-0 m-4 rounded-4 d-flex align-items-center gap-3';
                    toast.style.zIndex = 9999;
                    toast.innerHTML = `<i class="bi bi-check-circle-fill fs-4"></i>
                        <div><strong>RDV enregistré !</strong><br><small>${data.message}</small></div>`;
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
        document.getElementById('spQuotaInfo').classList.add('d-none');
        document.getElementById('spQuotaAlert').classList.add('d-none');
    }
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>