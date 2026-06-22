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

<!-- On force le chargement de Bootstrap JS au cas où il manquerait dans le footer -->
<!--<script src="<?= BASE_URL ?>public/js/bootstrap.bundle.min.js"></script>
<link rel="stylesheet" href="<?= BASE_URL ?>public/css/bootstrap-icons.css">-->

<style>
    :root {
        --bureau-color: <?= (($_SESSION['bureau_id'] ?? 1) == 2) ? '#16a34a' : '#2563eb' ?>;
        --soft-bg: #f8fafc;
    }
    body { background-color: var(--soft-bg); font-family: 'Plus Jakarta Sans', sans-serif; color: #1e293b; }
    main { margin-left: 0 !important; width: 100% !important; }

    .header-bureau { background: white; padding: 25px 0; border-bottom: 1px solid #e2e8f0; margin-bottom: 30px; }
    .bureau-badge { background: var(--bureau-color); color: white; padding: 6px 16px; border-radius: 100px; font-weight: 800; font-size: 0.75rem; }

    /* Carte Patient - On s'assure qu'elle ressemble à un bouton cliquable */
    .patient-card {
        background: white; border-radius: 20px; border: 1px solid #e2e8f0; padding: 20px;
        transition: all 0.2s ease; cursor: pointer; position: relative;
        box-shadow: 0 4px 6px rgba(0,0,0,0.02);
    }
    .patient-card:hover {
        transform: translateY(-4px);
        border-color: var(--bureau-color);
        box-shadow: 0 10px 20px rgba(0,0,0,0.05);
    }
    .patient-card:active { transform: scale(0.98); }

    .ticket-number {
        width: 50px; height: 50px; background: #f1f5f9; color: var(--bureau-color);
        border-radius: 14px; display: flex; align-items: center; justify-content: center;
        font-weight: 800; font-size: 1.2rem;
    }

    /* Modal Styling */
    .modal-content { border-radius: 28px; border: none; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); }
    .modal-header { border-bottom: 1px solid #f1f5f9; padding: 25px 30px; }
    .form-label { font-weight: 700; font-size: 0.8rem; color: #64748b; text-transform: uppercase; margin-bottom: 8px; }
    .input-custom {
        background: #f8fafc; border: 2px solid #e2e8f0; border-radius: 12px; padding: 12px;
        font-weight: 600; width: 100%; transition: 0.3s;
    }
    .input-custom:focus { border-color: var(--bureau-color); background: white; outline: none; }

    .history-item { background: white; padding: 15px; border-radius: 15px; margin-bottom: 10px; border-left: 4px solid #10b981; }
</style>

<div class="header-bureau">
    <div class="container d-flex justify-content-between align-items-center">
        <div>
            <span class="bureau-badge text-uppercase mb-2 d-inline-block">
                <i class="bi bi-pc-display-horizontal me-2"></i><?= $bureauLabel ?>
            </span>
            <h2 class="fw-800 mb-0">Poste de Tri & Paramètres</h2>
        </div>
        <div class="d-flex align-items-center gap-3">
            <div class="text-end me-3">
                <span class="fw-bold d-block fs-5" id="liveClock">00:00:00</span>
                <small class="text-muted fw-semibold"><?= date('d F Y') ?></small>
            </div>
            <a href="<?= BASE_URL ?>logout" class="btn btn-outline-danger rounded-circle p-2"><i class="bi bi-power fs-5"></i></a>
        </div>
    </div>
</div>

<div class="container">
    <div class="row g-4">
        <!-- LISTE D'ATTENTE -->
        <div class="col-lg-8">
            <!-- TITRE + COMPTEUR -->
            <div class="d-flex align-items-center gap-3 mb-3 flex-wrap">
                <h5 class="fw-bold mb-0">
                    Patients en attente
                    <span class="badge bg-primary bg-opacity-10 text-primary ms-2" id="countBadge"><?= count($patients_attente) ?></span>
                </h5>
            </div>

            <!-- BARRE DE RECHERCHE + FILTRES -->
            <div class="p-3 rounded-3 mb-4" style="background:#f8fafc;border:1px solid #e2e8f0">
                <!-- Recherche -->
                <div class="position-relative mb-3">
                    <i class="bi bi-search position-absolute text-muted"
                       style="top:50%;left:13px;transform:translateY(-50%);font-size:.9rem;pointer-events:none"></i>
                    <input type="text" id="searchPatient"
                           class="form-control rounded-pill ps-4 pe-4 border-2"
                           placeholder="Rechercher par nom, prénom, N° dossier…"
                           oninput="applyFilters()"
                           style="font-size:.85rem;height:42px">
                    <button type="button" id="btnClearSearch"
                            onclick="document.getElementById('searchPatient').value=''; applyFilters();"
                            class="btn btn-sm position-absolute border-0 bg-transparent text-muted"
                            style="top:50%;right:8px;transform:translateY(-50%);display:none;padding:2px 6px">
                        <i class="bi bi-x-circle-fill"></i>
                    </button>
                </div>

                <!-- Filtres -->
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <small class="text-muted fw-bold me-1" style="font-size:.72rem;text-transform:uppercase;letter-spacing:.5px">
                        <i class="bi bi-funnel-fill me-1"></i>Filtres :
                    </small>

                    <!-- Filtre Sexe -->
                    <div class="d-flex rounded-pill overflow-hidden border" style="font-size:.76rem">
                        <button type="button" class="filter-btn active px-3 py-1 border-0 fw-bold"
                                data-filter="sexe" data-val="" onclick="setFilter('sexe','',this)">
                            Tous
                        </button>
                        <button type="button" class="filter-btn px-3 py-1 border-0 fw-bold"
                                data-filter="sexe" data-val="m" onclick="setFilter('sexe','m',this)"
                                style="border-left:1px solid #e2e8f0!important">
                            <i class="bi bi-gender-male text-primary me-1"></i>Hommes
                        </button>
                        <button type="button" class="filter-btn px-3 py-1 border-0 fw-bold"
                                data-filter="sexe" data-val="f" onclick="setFilter('sexe','f',this)"
                                style="border-left:1px solid #e2e8f0!important">
                            <i class="bi bi-gender-female text-danger me-1"></i>Femmes
                        </button>
                    </div>

                    <!-- Filtre Ordre -->
                    <div class="d-flex rounded-pill overflow-hidden border" style="font-size:.76rem">
                        <button type="button" class="filter-btn active px-3 py-1 border-0 fw-bold"
                                data-filter="tri" data-val="asc" onclick="setFilter('tri','asc',this)">
                            <i class="bi bi-sort-numeric-down me-1"></i>N° croissant
                        </button>
                        <button type="button" class="filter-btn px-3 py-1 border-0 fw-bold"
                                data-filter="tri" data-val="desc" onclick="setFilter('tri','desc',this)"
                                style="border-left:1px solid #e2e8f0!important">
                            <i class="bi bi-sort-numeric-up me-1"></i>N° décroissant
                        </button>
                    </div>

                    <!-- Filtre Groupe sanguin -->
                    <select id="filterGroupe" onchange="applyFilters()"
                            class="form-select rounded-pill border-2 py-1"
                            style="font-size:.76rem;height:34px;width:auto;min-width:120px">
                        <option value="">🩸 Tout groupe</option>
                        <?php foreach(['A+','A-','B+','B-','AB+','AB-','O+','O-'] as $gs): ?>
                        <option value="<?= $gs ?>"><?= $gs ?></option>
                        <?php endforeach; ?>
                    </select>

                    <!-- Bouton reset -->
                    <button type="button" onclick="resetFilters()"
                            class="btn btn-sm btn-outline-secondary rounded-pill px-3"
                            style="font-size:.76rem;height:34px" id="btnResetFilters">
                        <i class="bi bi-arrow-counterclockwise me-1"></i>Réinitialiser
                    </button>

                    <!-- Résumé filtre actif -->
                    <span id="filterSummary" class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-3 ms-auto"
                          style="font-size:.72rem;display:none"></span>
                </div>
            </div>

            <div class="row g-3" id="patientGrid">
                <?php if(empty($patients_attente)): ?>
                    <div class="col-12 text-center py-5" id="emptyState">
                        <i class="bi bi-person-check text-muted display-1 opacity-25"></i>
                        <p class="text-muted mt-3">Aucun patient dans votre file d'attente.</p>
                    </div>
                <?php else: foreach($patients_attente as $p): ?>
                    <div class="col-md-6 patient-item"
                         data-search="<?= strtolower($p['nom'].' '.$p['prenom'].' '.$p['dossier_numero']) ?>"
                         data-sexe="<?= strtolower($p['sexe'] ?? '') ?>"
                         data-groupe="<?= htmlspecialchars($p['groupe_sanguin'] ?? '') ?>"
                         data-ordre="<?= (int)($p['numero_ordre'] ?? 0) ?>">
                        <!-- LA CARTE ENTIÈRE DÉCLENCHE LA MODALE -->
                        <?php
                            $ageParam = 99;
                            if (!empty($p['date_naissance'])) {
                                $ageParam = (int)date_diff(date_create($p['date_naissance']), date_create('now'))->y;
                            }
                        ?>
                        <div class="patient-card d-flex align-items-center gap-3"
                             onclick="showForm(<?= $p['id'] ?>, '<?= addslashes($p['nom'] . ' ' . $p['prenom']) ?>', <?= $ageParam ?>)">
                            <div class="ticket-number">#<?= $p['numero_ordre'] ?></div>
                            <div class="flex-grow-1">
                                <h6 class="fw-bold mb-0 text-dark" data-name><?= strtoupper($p['nom']) ?> <?= $p['prenom'] ?></h6>
                                <small class="text-muted"><?= $p['dossier_numero'] ?>
                                    <?php if(!empty($p['sexe'])): ?>
                                    <span class="ms-1 <?= $p['sexe']=='M'?'text-primary':'text-danger' ?>">
                                        <i class="bi bi-gender-<?= $p['sexe']=='M'?'male':'female' ?>"></i>
                                    </span>
                                    <?php endif; ?>
                                    <?php if(!empty($p['groupe_sanguin'])): ?>
                                    <span class="badge ms-1" style="background:#fef2f2;color:#dc2626;font-size:.62rem"><?= $p['groupe_sanguin'] ?></span>
                                    <?php endif; ?>
                                    <?php if($ageParam <= 15): ?>
                                    <span class="badge ms-1" style="background:#eff6ff;color:#1d4ed8;font-size:.62rem;border:1px solid #bfdbfe;">
                                        <i class="bi bi-person-hearts me-1"></i>PÉDIATRIE
                                    </span>
                                    <?php endif; ?>
                                </small>
                            </div>
                            <div class="text-primary fs-4"><i class="bi bi-plus-circle-fill"></i></div>
                        </div>
                    </div>
                <?php endforeach; endif; ?>

                <!-- Message aucun résultat -->
                <div class="col-12 text-center py-5" id="noResult" style="display:none">
                    <i class="bi bi-funnel text-muted display-4 opacity-25 d-block mb-3"></i>
                    <p class="text-muted fw-semibold" id="noResultMsg">Aucun patient ne correspond aux filtres sélectionnés</p>
                </div>
            </div>
        </div>

        <!-- HISTORIQUE -->
        <div class="col-lg-4">
            <h5 class="fw-bold mb-4">Traités aujourd'hui</h5>
            <?php foreach($patients_reçus as $pr): ?>
                <div class="history-item shadow-sm d-flex justify-content-between align-items-center">
                    <div>
                        <div class="fw-bold text-dark small"><?= $pr['nom'] ?> <?= $pr['prenom'] ?></div>
                        <small class="text-muted"><i class="bi bi-clock me-1"></i><?= date('H:i', strtotime($pr['date_mesure'])) ?></small>
                    </div>
                    <i class="bi bi-check-circle-fill text-success fs-5"></i>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- MODALE DE SAISIE (Adaptative Enfant / Adulte) -->
<div class="modal fade" id="modalSaisie" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" id="modalSaisieDialog">
        <div class="modal-content">
            <div class="modal-header border-0 p-4" id="modalSaisieHeader">
                <div>
                    <h5 class="modal-title fw-bold">Paramètres de : <span id="displayPatientName" class="text-primary"></span></h5>
                    <small id="displayProfilBadge" class="text-muted"></small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="<?= BASE_URL ?>parametres/save" method="POST">
                <input type="hidden" name="patient_id" id="formPatientId">
                <input type="hidden" name="profil" id="formProfil" value="ADULTE">
                <div class="modal-body p-4 pt-0">

                    <!-- ── CHAMPS ADULTE ── -->
                    <div id="champsAdulte" class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Température (°C)</label>
                            <input type="text" inputmode="decimal" pattern="[0-9]+([.,][0-9]*)?" name="temp" id="adulte-temp" class="input-custom" placeholder="37.0" autocomplete="off">
                        </div>
                        <div class="col-md-8">
                            <label class="form-label">Tension Artérielle (mmHg)</label>
                            <div class="d-flex align-items-center gap-2">
                                <input type="number" name="sys" class="input-custom" placeholder="120">
                                <span class="fw-bold">/</span>
                                <input type="number" name="dia" class="input-custom" placeholder="80">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Pouls (bpm)</label>
                            <input type="number" name="pouls" class="input-custom" placeholder="80">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">SpO2 (%)</label>
                            <input type="number" name="spo2" class="input-custom" placeholder="98">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Taille (cm)</label>
                            <input type="number" name="taille" id="adulte-taille" class="input-custom" placeholder="170">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Poids (kg)</label>
                            <input type="number" step="0.1" name="poids" id="adulte-poids" class="input-custom" placeholder="70">
                        </div>
                    </div>

                    <!-- ── CHAMPS ENFANT ── -->
                    <div id="champsEnfant" class="d-none">
                        <div class="alert border-0 mb-4 py-2 d-flex align-items-center gap-2"
                             style="background:#f0fdf4;color:#166534;">
                            <i class="bi bi-info-circle-fill"></i>
                            <small class="fw-semibold">Protocole pédiatrique — Poids · Taille · Température</small>
                        </div>
                        <div class="row g-4">
                            <div class="col-6">
                                <label class="form-label" style="color:#059669;">
                                    <i class="bi bi-person-standing me-1"></i>Poids (kg)
                                </label>
                                <input type="number" step="0.1" min="1" max="150" name="poids" id="enfant-poids"
                                       class="input-custom text-center fw-bold"
                                       style="border:2px solid #10b981;border-radius:12px;font-size:1.3rem;height:60px;"
                                       placeholder="ex : 18.5">
                            </div>
                            <div class="col-6">
                                <label class="form-label" style="color:#059669;">
                                    <i class="bi bi-rulers me-1"></i>Taille (cm)
                                </label>
                                <input type="number" min="30" max="200" name="taille" id="enfant-taille"
                                       class="input-custom text-center fw-bold"
                                       style="border:2px solid #10b981;border-radius:12px;font-size:1.3rem;height:60px;"
                                       placeholder="ex : 110">
                            </div>
                            <div class="col-12">
                                <label class="form-label text-danger fw-bold">
                                    <i class="bi bi-thermometer-half me-1"></i>Température (°C)
                                </label>
                                <div class="position-relative">
                                    <input type="number" step="0.1" min="34" max="43" name="temp" id="enfant-temp"
                                           class="input-custom text-center fw-bold"
                                           style="border:2px solid #ef4444;border-radius:12px;font-size:2rem;height:80px;"
                                           placeholder="37.5" oninput="pmCheckFeverEnfant(this.value)">
                                    <span class="position-absolute top-50 end-0 translate-middle-y me-3 text-muted fs-4 fw-bold pe-none">°C</span>
                                </div>
                                <div id="pm-temp-badge" class="text-center mt-2" style="min-height:30px;"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Motif + Destination + Médecin -->
                    <div class="row g-3 mt-3">
                        <div class="col-12">
                            <label class="form-label text-primary">Motif de consultation</label>
                            <textarea name="motif" class="input-custom" rows="2" placeholder="Symptômes décrits..." required></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Service de destination</label>
                            <select name="service_id" id="pmServiceId" class="input-custom" required onchange="pmOnServiceChange(this)">
                                <?php
                                    $dbSvc = (new Database())->getConnection();
                                    // Limité aux services de consultation uniquement
                                    $svcs = $dbSvc->query(
                                        "SELECT id, nom_service FROM services
                                          WHERE id IN (1, 2, 3, 4, 5, 15, 20)
                                          ORDER BY nom_service ASC"
                                    )->fetchAll(PDO::FETCH_ASSOC);
                                    foreach ($svcs as $svc):
                                        $isExt = stripos($svc['nom_service'], 'extern') !== false
                                              || stripos($svc['nom_service'], 'ext')    !== false;
                                ?>
                                <option value="<?= $svc['id'] ?>" <?= $isExt ? 'data-externe="1"' : '' ?>>
                                    <?= htmlspecialchars($svc['nom_service']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Attribuer au Médecin</label>
                            <select name="medecin_id" id="pmMedecinId" class="input-custom" required>
                                <!-- Option Consultation Infirmière (visible seulement si service externe) -->
                                <option value="INFIRMIERE" id="pmOptInfirmiere" style="display:none;background:#ecfdf5;color:#065f46;font-weight:700;">
                                    🩺 Consultation Infirmière (tous les infirmiers consultants)
                                </option>
                                <?php
                                    $meds = $dbSvc->query("SELECT id, nom, prenom, role FROM users WHERE role IN ('MEDECIN','GENERALISTE','INFIRMIER_CONSULTANT') AND actif = 1 ORDER BY nom")->fetchAll(PDO::FETCH_ASSOC);
                                    foreach ($meds as $m) {
                                        $label = in_array($m['role'], ['INFIRMIER_CONSULTANT']) ? 'Inf.' : 'Dr.';
                                        echo "<option value='{$m['id']}'>{$label} {$m['nom']} {$m['prenom']}</option>";
                                    }
                                ?>
                            </select>
                        </div>
                    </div>

                </div>
                <!-- Badge consultation infirmière (affiché quand sélectionnée) -->
                <div id="pmBadgeInfirmiere" style="display:none;margin:0 1.5rem .5rem;padding:.6rem 1rem;background:#ecfdf5;border:1.5px solid #6ee7b7;border-radius:10px;font-size:.85rem;color:#065f46;">
                    <i class="bi bi-people-fill me-2"></i>Le patient sera envoyé dans la file de <strong>tous les infirmiers consultants</strong> disponibles.
                </div>

                <div class="modal-footer border-0 p-4">
                    <button type="submit" class="btn btn-primary w-100 rounded-pill py-3 fw-bold shadow" id="btnValiderParam">
                        VALIDER ET ENVOYER AU MÉDECIN
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Horloge
    setInterval(() => {
        document.getElementById('liveClock').innerText = new Date().toLocaleTimeString('fr-FR');
    }, 1000);

    // ── Consultation Infirmière ──────────────────────────────────
    function pmOnServiceChange(sel) {
        const opt     = sel.options[sel.selectedIndex];
        const isExt   = opt.dataset.externe === '1';
        const optInf  = document.getElementById('pmOptInfirmiere');
        const med     = document.getElementById('pmMedecinId');
        const badge   = document.getElementById('pmBadgeInfirmiere');

        optInf.style.display = isExt ? '' : 'none';

        // Si le service n'est plus externe et que l'option infirmière est sélectionnée, reset
        if (!isExt && med.value === 'INFIRMIERE') {
            med.value = med.options[1]?.value || '';
            badge.style.display = 'none';
        }
    }

    document.getElementById('pmMedecinId')?.addEventListener('change', function() {
        const badge = document.getElementById('pmBadgeInfirmiere');
        const btn   = document.getElementById('btnValiderParam');
        if (this.value === 'INFIRMIERE') {
            badge.style.display = 'block';
            if (btn) btn.textContent = 'VALIDER ET ENVOYER AUX INFIRMIERS';
        } else {
            badge.style.display = 'none';
            if (btn) btn.textContent = 'VALIDER ET ENVOYER AU MÉDECIN';
        }
    });

    function showForm(id, name, age) {
        document.getElementById('formPatientId').value = id;
        document.getElementById('displayPatientName').innerText = name;

        const isEnfant = (age !== undefined && age <= 15);
        const header   = document.getElementById('modalSaisieHeader');
        const badge    = document.getElementById('displayProfilBadge');
        const adulte   = document.getElementById('champsAdulte');
        const enfant   = document.getElementById('champsEnfant');
        const profil   = document.getElementById('formProfil');
        const dialog   = document.getElementById('modalSaisieDialog');

        if (isEnfant) {
            header.style.background = 'linear-gradient(135deg,#059669,#10b981)';
            header.style.color = '#fff';
            header.querySelector('.btn-close').classList.add('btn-close-white');
            badge.innerHTML = `<i class="bi bi-balloon-heart me-1"></i>Pédiatrie <span class="badge bg-white text-success fw-bold ms-1">${age} ans</span>`;
            adulte.classList.add('d-none');
            enfant.classList.remove('d-none');
            profil.value = 'ENFANT';
            dialog.classList.remove('modal-lg');
            dialog.classList.add('modal-md');
            // Désactiver les champs adultes (évite la soumission de valeurs vides)
            adulte.querySelectorAll('input,select,textarea').forEach(el => {
                el.removeAttribute('name');
                el.disabled = true;
            });
            // Réactiver les champs enfant
            enfant.querySelectorAll('input,select,textarea').forEach(el => {
                el.disabled = false;
            });
        } else {
            header.style.background = '';
            header.style.color = '';
            header.querySelector('.btn-close').classList.remove('btn-close-white');
            badge.innerHTML = age !== undefined
                ? `<i class="bi bi-person me-1"></i>Adulte <span class="badge bg-secondary fw-bold ms-1">${age} ans</span>`
                : '';
            adulte.classList.remove('d-none');
            enfant.classList.add('d-none');
            profil.value = 'ADULTE';
            dialog.classList.add('modal-lg');
            dialog.classList.remove('modal-md');
            // Désactiver les champs enfant (empêche leur soumission avec valeurs vides)
            enfant.querySelectorAll('input,select,textarea').forEach(el => {
                el.disabled = true;
            });
            // Réactiver + restaurer les noms des champs adultes
            adulte.querySelectorAll('input,select,textarea').forEach(el => el.disabled = false);
            const adultFields = {
                'adulte-temp': 'temp', 'adulte-taille': 'taille', 'adulte-poids': 'poids'
            };
            Object.entries(adultFields).forEach(([elId, fieldName]) => {
                const el = document.getElementById(elId);
                if (el) el.name = fieldName;
            });
        }

        new bootstrap.Modal(document.getElementById('modalSaisie')).show();
    }

    function pmCheckFeverEnfant(val) {
        const badge = document.getElementById('pm-temp-badge');
        const t = parseFloat(val);
        if (isNaN(t) || !val) { badge.innerHTML = ''; return; }
        if (t >= 39.5) {
            badge.innerHTML = '<span class="badge bg-danger fs-6 px-3 py-2">🚨 Hyperthermie sévère</span>';
        } else if (t >= 38.5) {
            badge.innerHTML = '<span class="badge bg-warning text-dark fs-6 px-3 py-2">⚠️ Fièvre élevée</span>';
        } else if (t >= 37.5) {
            badge.innerHTML = '<span class="badge bg-orange text-white fs-6 px-3 py-2" style="background:#f97316;">🌡️ Fièvre légère</span>';
        } else if (t < 36) {
            badge.innerHTML = '<span class="badge bg-info text-dark fs-6 px-3 py-2">❄️ Hypothermie</span>';
        } else {
            badge.innerHTML = '<span class="badge bg-success fs-6 px-3 py-2">✓ Température normale</span>';
        }
    }

    // ── État des filtres actifs ──
    const activeFilters = { sexe: '', tri: 'asc', groupe: '' };

    function setFilter(key, val, btn) {
        activeFilters[key] = val;
        // Mise à jour visuelle des boutons du groupe
        btn.closest('.d-flex').querySelectorAll('.filter-btn').forEach(b => {
            b.classList.toggle('active', b === btn);
            b.style.background = b === btn ? 'var(--bureau-color, #2563eb)' : '#fff';
            b.style.color      = b === btn ? '#fff' : '#64748b';
        });
        applyFilters();
    }

    function resetFilters() {
        document.getElementById('searchPatient').value = '';
        document.getElementById('filterGroupe').value  = '';
        activeFilters.sexe   = '';
        activeFilters.tri    = 'asc';
        activeFilters.groupe = '';
        // Reset tous les boutons
        document.querySelectorAll('.filter-btn').forEach(b => {
            const isDefault = b.dataset.val === '' || b.dataset.val === 'asc';
            b.classList.toggle('active', isDefault);
            b.style.background = isDefault ? 'var(--bureau-color, #2563eb)' : '#fff';
            b.style.color      = isDefault ? '#fff' : '#64748b';
        });
        applyFilters();
    }

    function applyFilters() {
        const q         = (document.getElementById('searchPatient').value || '').trim().toLowerCase();
        const sexeF     = activeFilters.sexe;
        const groupeF   = (document.getElementById('filterGroupe').value || '').trim();
        const triF      = activeFilters.tri;
        const items     = Array.from(document.querySelectorAll('.patient-item'));
        const badge     = document.getElementById('countBadge');
        const noRes     = document.getElementById('noResult');
        const btnClear  = document.getElementById('btnClearSearch');
        const summary   = document.getElementById('filterSummary');

        btnClear.style.display = q ? 'block' : 'none';

        // Filtre
        let visible = items.filter(item => {
            const matchSearch = !q || item.dataset.search.includes(q);
            const matchSexe   = !sexeF || item.dataset.sexe === sexeF;
            const matchGroupe = !groupeF || item.dataset.groupe === groupeF;
            const show = matchSearch && matchSexe && matchGroupe;
            item.style.display = show ? '' : 'none';

            // Surbrillance nom
            const nameEl = item.querySelector('[data-name]');
            if (nameEl) {
                if (q && show) {
                    const txt   = nameEl.textContent;
                    const regex = new RegExp('(' + q.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + ')', 'gi');
                    nameEl.innerHTML = txt.replace(regex, '<mark style="background:#fef08a;border-radius:3px;padding:0 2px">$1</mark>');
                } else {
                    nameEl.innerHTML = nameEl.textContent;
                }
            }
            return show;
        });

        // Tri
        const grid = document.getElementById('patientGrid');
        visible.sort((a, b) => {
            const oa = parseInt(a.dataset.ordre || 0);
            const ob = parseInt(b.dataset.ordre || 0);
            return triF === 'desc' ? ob - oa : oa - ob;
        }).forEach(el => grid.appendChild(el));

        badge.textContent = visible.length;
        noRes.style.display = (visible.length === 0 && items.length > 0) ? 'block' : 'none';

        // Résumé
        const parts = [];
        if (q)       parts.push('"' + q + '"');
        if (sexeF)   parts.push(sexeF === 'm' ? 'Hommes' : 'Femmes');
        if (groupeF) parts.push(groupeF);
        summary.style.display = parts.length ? 'inline-flex' : 'none';
        summary.textContent   = parts.length ? visible.length + ' résultat(s) — ' + parts.join(', ') : '';
    }

    // Init style des boutons filtres
    document.querySelectorAll('.filter-btn').forEach(b => {
        const isDefault = b.dataset.val === '' || b.dataset.val === 'asc';
        b.style.background = isDefault ? 'var(--bureau-color, #2563eb)' : '#fff';
        b.style.color      = isDefault ? '#fff' : '#64748b';
        b.style.transition = 'all .15s';
    });
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>