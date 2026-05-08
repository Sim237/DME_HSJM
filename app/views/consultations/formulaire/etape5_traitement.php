<?php
/* ============================================================================
   FICHIER : etape5_traitement.php
   LOGIQUE : Recherche Pharmacie + Saisie Manuelle + Gestion de l'Ordonnance
   ============================================================================ */

$patient = $patient ?? [];
$consultation = $consultation_data ?? [];
$type_consultation = $_GET['type'] ?? $consultation['type'] ?? 'EXTERNE';

include __DIR__ . '/../../layouts/header.php';
?>

<style>
    /* Design Global Focus Mode */
    .sidebar { display: none !important; }
    main { margin-left: 0 !important; width: 100% !important; background: #f8fafc; min-height: 100vh; }

    .consultation-form { max-width: 1100px; margin: 0 auto; padding-bottom: 50px; }

    /* Style des cartes */
    .card-modern { border: none; border-radius: 20px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); background: white; margin-bottom: 25px; }
    .card-header-custom { padding: 20px 25px; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center; }

    /* Zone de recherche dans la modale */
    .search-container { position: relative; }
    #list-results {
        position: absolute; top: 100%; left: 0; right: 0;
        background: white; border-radius: 12px;
        box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        z-index: 2000; max-height: 250px; overflow-y: auto;
        border: 1px solid #e2e8f0;
    }
    .result-item {
        padding: 12px 15px; cursor: pointer; border-bottom: 1px solid #f1f5f9;
        display: flex; justify-content: space-between; align-items: center;
    }
    .result-item:hover { background: #f8fafc; color: #2563eb; }

    .input-custom { background: #f8fafc; border: 2px solid #e2e8f0; border-radius: 12px; padding: 12px; font-weight: 600; width: 100%; transition: 0.3s; }
    .input-custom:focus { border-color: #3b82f6; background: white; outline: none; box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1); }

    .form-label-custom { font-weight: 700; font-size: 0.8rem; color: #64748b; text-transform: uppercase; margin-bottom: 8px; display: block; }

    /* Table Ordonnance */
    .table-prescription thead th { background: #f8fafc; color: #64748b; font-size: 0.75rem; text-transform: uppercase; padding: 15px; border: none; }
    .table-prescription td { padding: 15px; vertical-align: middle; border-bottom: 1px solid #f1f5f9; }
</style>

<div class="container-fluid">
    <div class="consultation-form">
        <?php $numero = 5; include __DIR__ . '/progress_bar.php'; ?>

        <form action="<?= BASE_URL ?>consultation/sauvegarder" method="POST" id="mainForm">
            <!-- Champs cachés obligatoires -->
            <input type="hidden" name="etape_actuelle" value="5">
            <input type="hidden" name="patient_id" value="<?= $patient['id']; ?>">
            <input type="hidden" name="type" value="<?= htmlspecialchars($type_consultation); ?>">

            <!-- 1. PLAN DE TRAITEMENT -->
            <div class="card card-modern">
                <div class="card-header-custom bg-primary text-white" style="border-radius: 20px 20px 0 0;">
                    <h5 class="mb-0 fw-bold"><i class="bi bi-clipboard-pulse me-2"></i>STRATÉGIE THÉRAPEUTIQUE</h5>
                </div>
                <div class="card-body p-4">
                    <label class="form-label-custom">Plan de Traitement Global <span class="text-danger">*</span></label>
                    <textarea class="form-control input-custom" name="plan_traitement" rows="3" required placeholder="Décrivez la prise en charge globale..."><?= htmlspecialchars($consultation['plan_traitement'] ?? ''); ?></textarea>
                </div>
            </div>

            <!-- 2. ORDONNANCE -->
            <div class="card card-modern">
                <div class="card-header-custom bg-white">
                    <h5 class="mb-0 fw-bold text-dark"><i class="bi bi-capsule text-primary me-2"></i>ORDONNANCE MÉDICALE</h5>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-outline-primary btn-sm rounded-pill" onclick="loadKitModal()"><i class="bi bi-box-seam me-1"></i> Kits</button>
                        <button type="button" class="btn btn-success btn-sm rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#modalAjouterMedicament">
                            <i class="bi bi-plus-lg me-1"></i> AJOUTER UN MÉDICAMENT
                        </button>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-prescription mb-0">
                            <thead>
                                <tr>
                                    <th class="ps-4">Médicament</th>
                                    <th>Posologie</th>
                                    <th>Durée</th>
                                    <th>Qté</th>
                                    <th class="text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody id="listeMedicaments">
                                <!-- Rempli dynamiquement en JS -->
                            </tbody>
                        </table>
                    </div>
                    <div id="emptyState" class="text-center py-5 text-muted">
                        <i class="bi bi-capsule-pill display-4 opacity-25"></i>
                        <p class="mt-2">Aucun médicament dans l'ordonnance</p>
                    </div>
                </div>
            </div>

            <!-- 3. NON MÉDICAMENTEUX -->
            <div class="card card-modern">
                <div class="card-body p-4">
                    <label class="form-label-custom">Conseils & Traitement non médicamenteux</label>
                    <textarea class="form-control input-custom" name="traitement_non_medicamenteux" rows="3" placeholder="Repos, régime, kiné..."><?= htmlspecialchars($consultation['traitement_non_medicamenteux'] ?? ''); ?></textarea>
                </div>
            </div>

            <!-- NAVIGATION -->
            <div class="d-flex justify-content-between mt-4">
                <a href="<?= BASE_URL ?>consultation/formulaire?patient_id=<?= $patient['id'] ?>&etape=4" class="btn btn-light rounded-pill px-4">
                    <i class="bi bi-arrow-left me-2"></i> Précédent
                </a>
                <button type="submit" class="btn btn-primary rounded-pill px-5 fw-bold">
                    SUIVANT : SURVEILLANCE <i class="bi bi-arrow-right ms-2"></i>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- =========================================================================
     MODALE : AJOUT MÉDICAMENT — VERSION MODERNE
     ========================================================================= -->
<div class="modal fade" id="modalAjouterMedicament" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content" style="border-radius:20px; border:none; overflow:hidden;">

            <div class="modal-header border-0 px-4 py-3" style="background:linear-gradient(135deg,#1e40af,#3b82f6);">
                <div class="d-flex align-items-center gap-3 text-white">
                    <div style="background:rgba(255,255,255,.15);border-radius:10px;padding:8px 12px;">
                        <i class="bi bi-capsule-pill fs-4"></i>
                    </div>
                    <div>
                        <h5 class="fw-bold mb-0">Ajouter à la prescription</h5>
                        <small class="opacity-75">Recherche pharmacie ou saisie libre</small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body p-0">
                <form id="formModalMed">

                    <!-- Onglets -->
                    <ul class="nav nav-pills gap-2 p-3 pb-0 bg-light border-bottom">
                        <li class="nav-item">
                            <button type="button" class="nav-link active rounded-pill px-4" id="tab-stock-btn"
                                    onclick="switchPrescTab('stock')">
                                <i class="bi bi-search me-1"></i> Pharmacie (stock)
                            </button>
                        </li>
                        <li class="nav-item">
                            <button type="button" class="nav-link rounded-pill px-4" id="tab-libre-btn"
                                    onclick="switchPrescTab('libre')">
                                <i class="bi bi-pencil me-1"></i> Saisie libre
                            </button>
                        </li>
                    </ul>

                    <div class="p-4">

                        <!-- ── PANE PHARMACIE ── -->
                        <div id="pane-stock">
                            <div class="search-container mb-3" style="position:relative;">
                                <div class="input-group">
                                    <span class="input-group-text bg-white border-end-0">
                                        <i class="bi bi-search text-primary"></i>
                                    </span>
                                    <input type="text" id="med-search-input"
                                           class="form-control border-start-0 ps-0 input-custom"
                                           placeholder="Tapez : paracétamol, amoxicilline, quinine..."
                                           autocomplete="off">
                                </div>
                                <div id="list-results" class="d-none shadow-lg rounded-3 border mt-1"
                                     style="position:absolute;width:100%;z-index:2000;max-height:260px;overflow-y:auto;background:#fff;"></div>
                            </div>

                            <!-- Méd sélectionné -->
                            <div id="selected-med-info" class="d-none mb-3 p-3 rounded-3"
                                 style="background:linear-gradient(135deg,#eff6ff,#dbeafe);border:1.5px solid #93c5fd;">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <div class="fw-bold text-primary fs-6" id="display-name"></div>
                                        <small class="text-muted" id="display-detail"></small>
                                    </div>
                                    <div class="text-end">
                                        <span id="display-stock" class="badge fs-6"></span><br>
                                        <button type="button" class="btn btn-sm btn-link text-muted p-0 mt-1"
                                                onclick="resetMedSelection()">
                                            <i class="bi bi-x-circle"></i> Changer
                                        </button>
                                    </div>
                                </div>
                                <input type="hidden" id="hidden-med-id">
                                <input type="hidden" id="hidden-med-nom">
                            </div>

                            <div id="stock-empty-hint" class="text-center py-3 text-muted small">
                                <i class="bi bi-search fs-3 d-block mb-2 opacity-25"></i>
                                Tapez au moins 2 caractères pour rechercher
                            </div>
                        </div>

                        <!-- ── PANE LIBRE ── -->
                        <div id="pane-libre" class="d-none">
                            <div class="p-3 rounded-3 mb-3" style="background:#fffbeb;border:1px solid #fde68a;">
                                <label class="form-label-custom text-warning">
                                    <i class="bi bi-pencil-square me-1"></i> Nom du médicament (hors stock)
                                </label>
                                <input type="text" id="manual-med-name" class="form-control input-custom"
                                       placeholder="Ex: Chloroquine 250mg, Vitamine C 1g...">
                                <small class="text-muted mt-1 d-block">
                                    <i class="bi bi-info-circle me-1"></i>
                                    Ce médicament ne sera pas déduit du stock de la pharmacie.
                                </small>
                            </div>
                        </div>

                        <!-- ── POSOLOGIE COMMUNE ── -->
                        <div class="border-top pt-3">
                            <div class="fw-semibold text-dark mb-3 small text-uppercase">
                                <i class="bi bi-clock me-1 text-primary"></i> Posologie &amp; Durée
                            </div>
                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label-custom">Posologie <span class="text-danger">*</span></label>
                                    <input type="text" id="in-poso" class="form-control input-custom"
                                           placeholder="ex: 1 comprimé matin et soir">
                                    <!-- Suggestions rapides -->
                                    <div class="d-flex flex-wrap gap-1 mt-2">
                                        <?php foreach (['1 cp matin', '1 cp matin et soir', '1 cp 3×/j', '½ cp matin', '2 cp matin et soir', '1 cp au coucher', '1 ampoule IM/j'] as $s): ?>
                                        <span class="badge bg-light text-dark border rounded-pill px-3 py-1"
                                              style="cursor:pointer;font-size:.75rem;"
                                              onclick="document.getElementById('in-poso').value='<?= $s ?>'">
                                            <?= $s ?>
                                        </span>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label-custom">Durée</label>
                                    <div class="input-group">
                                        <input type="number" id="in-duree-nb" class="form-control input-custom"
                                               placeholder="5" min="1" max="365">
                                        <select id="in-duree-unit" class="form-select input-custom" style="max-width:95px;">
                                            <option value="jours">jours</option>
                                            <option value="semaines">sem.</option>
                                            <option value="mois">mois</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label-custom">Quantité <span class="text-danger">*</span></label>
                                    <input type="number" id="in-qte" class="form-control input-custom"
                                           placeholder="ex: 30" min="1">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label-custom">Voie</label>
                                    <select id="in-voie" class="form-select input-custom">
                                        <option value="orale">Orale (PO)</option>
                                        <option value="injectable">Injectable (IM/IV)</option>
                                        <option value="topique">Topique</option>
                                        <option value="inhalation">Inhalation</option>
                                        <option value="rectale">Rectale</option>
                                        <option value="sublinguale">Sublinguale</option>
                                    </select>
                                </div>
                                <div class="col-12">
                                    <label class="form-label-custom">Instructions spéciales <span class="fw-normal text-muted">(optionnel)</span></label>
                                    <input type="text" id="in-instructions" class="form-control input-custom"
                                           placeholder="Ex: à prendre pendant les repas, éviter le soleil...">
                                </div>
                            </div>
                        </div>

                    </div><!-- /p-4 -->
                </form>
            </div>

            <div class="modal-footer border-0 px-4 pb-4 pt-2 gap-2">
                <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-primary rounded-pill px-5 fw-bold shadow-sm"
                        onclick="ajouterMedicamentAction()">
                    <i class="bi bi-plus-circle me-2"></i>AJOUTER À L'ORDONNANCE
                </button>
            </div>
        </div>
    </div>
</div>

<script>
let medicamentsPrescription = [];
let currentPrescTab = 'stock';

function switchPrescTab(tab) {
    currentPrescTab = tab;
    document.getElementById('pane-stock').classList.toggle('d-none', tab !== 'stock');
    document.getElementById('pane-libre').classList.toggle('d-none', tab !== 'libre');
    document.getElementById('tab-stock-btn').classList.toggle('active', tab === 'stock');
    document.getElementById('tab-libre-btn').classList.toggle('active', tab === 'libre');
    if (tab === 'libre') document.getElementById('manual-med-name')?.focus();
    else document.getElementById('med-search-input')?.focus();
}

function resetMedSelection() {
    document.getElementById('hidden-med-id').value  = '';
    document.getElementById('hidden-med-nom').value = '';
    document.getElementById('selected-med-info').classList.add('d-none');
    document.getElementById('stock-empty-hint').classList.remove('d-none');
    document.getElementById('med-search-input').value = '';
    document.getElementById('med-search-input').focus();
}

document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('med-search-input');
    const resultsBox  = document.getElementById('list-results');

    let searchTimer;
    searchInput.addEventListener('input', function() {
        clearTimeout(searchTimer);
        const q = this.value.trim();
        if (q.length < 2) { resultsBox.classList.add('d-none'); return; }

        resultsBox.innerHTML = '<div class="p-3 text-center text-muted"><span class="spinner-border spinner-border-sm"></span> Recherche...</div>';
        resultsBox.classList.remove('d-none');

        searchTimer = setTimeout(() => {
            fetch(`<?= BASE_URL ?>pharmacie/search-medicaments?term=${encodeURIComponent(q)}`)
                .then(r => r.json())
                .then(data => {
                    if (!data.length) {
                        resultsBox.innerHTML = '<div class="p-3 text-center text-muted small">Aucun résultat — essayez la saisie libre</div>';
                        return;
                    }
                    resultsBox.innerHTML = data.map(med => `
                        <div class="result-item" onclick='selectMed(${JSON.stringify(med)})'>
                            <div>
                                <div class="fw-bold">${med.nom}</div>
                                <small class="text-muted">${med.forme ?? ''} ${med.dosage ?? ''}</small>
                            </div>
                            <span class="badge ${med.stock_actuel > 0 ? 'bg-success' : 'bg-danger'}">
                                ${med.stock_actuel > 0 ? med.stock_actuel + ' en stock' : 'Rupture'}
                            </span>
                        </div>`).join('');
                })
                .catch(() => { resultsBox.innerHTML = '<div class="p-3 text-danger small">Erreur réseau</div>'; });
        }, 300);
    });

    document.addEventListener('click', e => {
        if (!searchInput.contains(e.target) && !resultsBox.contains(e.target))
            resultsBox.classList.add('d-none');
    });
});

function selectMed(med) {
    document.getElementById('hidden-med-id').value  = med.id;
    document.getElementById('hidden-med-nom').value = med.nom;
    document.getElementById('display-name').textContent   = med.nom;
    document.getElementById('display-detail').textContent = ((med.forme ?? '') + ' ' + (med.dosage ?? '')).trim();
    const badge = document.getElementById('display-stock');
    if (med.stock_actuel > 0) {
        badge.textContent = med.stock_actuel + ' en stock';
        badge.className   = 'badge bg-success fs-6';
    } else {
        badge.textContent = 'Rupture de stock';
        badge.className   = 'badge bg-danger fs-6';
    }
    document.getElementById('selected-med-info').classList.remove('d-none');
    document.getElementById('stock-empty-hint').classList.add('d-none');
    document.getElementById('list-results').classList.add('d-none');
    document.getElementById('med-search-input').value = '';
    document.getElementById('in-poso').focus();
}

function ajouterMedicamentAction() {
    let medId = '', medNom = '';

    if (currentPrescTab === 'stock') {
        medId  = document.getElementById('hidden-med-id').value;
        medNom = document.getElementById('hidden-med-nom').value;
        if (!medNom) { showPrescAlert('Veuillez sélectionner un médicament dans la liste.'); return; }
    } else {
        medNom = (document.getElementById('manual-med-name').value || '').trim();
        if (!medNom) { showPrescAlert('Veuillez saisir le nom du médicament.'); return; }
    }

    const poso  = document.getElementById('in-poso').value.trim();
    const nb    = document.getElementById('in-duree-nb').value;
    const unit  = document.getElementById('in-duree-unit').value;
    const duree = nb ? nb + ' ' + unit : '';
    const qte   = document.getElementById('in-qte').value;
    const voie  = document.getElementById('in-voie').value;
    const instr = document.getElementById('in-instructions').value.trim();

    if (!poso) { showPrescAlert('Veuillez saisir la posologie.'); return; }
    if (!qte)  { showPrescAlert('Veuillez indiquer la quantité totale.'); return; }

    medicamentsPrescription.push({ id:medId, nom:medNom, posologie:poso, duree, quantite:qte, voie, instructions:instr });
    renderTable();

    bootstrap.Modal.getInstance(document.getElementById('modalAjouterMedicament')).hide();
    document.getElementById('formModalMed').reset();
    document.getElementById('selected-med-info').classList.add('d-none');
    document.getElementById('stock-empty-hint').classList.remove('d-none');
    document.getElementById('hidden-med-id').value  = '';
    document.getElementById('hidden-med-nom').value = '';
    switchPrescTab('stock');
}

function showPrescAlert(msg) {
    const d = document.createElement('div');
    d.className = 'alert alert-warning alert-dismissible fade show rounded-3 mx-4 mb-0';
    d.innerHTML = `<i class="bi bi-exclamation-triangle me-2"></i>${msg}<button type="button" class="btn-close" data-bs-dismiss="alert"></button>`;
    document.querySelector('#modalAjouterMedicament .modal-body').prepend(d);
    setTimeout(() => d.remove(), 4000);
}

const voieIcon = { orale:'🟢', injectable:'🔵', topique:'🟡', inhalation:'🟣', rectale:'🟠', sublinguale:'⚪' };

function renderTable() {
    const tbody = document.getElementById('listeMedicaments');
    const empty = document.getElementById('emptyState');
    if (!medicamentsPrescription.length) {
        tbody.innerHTML = ''; empty.classList.remove('d-none'); return;
    }
    empty.classList.add('d-none');
    tbody.innerHTML = medicamentsPrescription.map((m, i) => `
        <tr>
            <td class="ps-4">
                <div class="fw-bold">${m.nom}</div>
                <small class="text-muted">${m.id ? '✅ Stock pharmacie' : '✏️ Saisie manuelle'}
                    ${m.voie ? ' · ' + (voieIcon[m.voie]||'') + ' ' + m.voie : ''}
                </small>
                ${m.instructions ? `<br><small class="text-info fst-italic"><i class="bi bi-info-circle me-1"></i>${m.instructions}</small>` : ''}
                <input type="hidden" name="medicaments[${i}][medicament_id]" value="${m.id}">
                <input type="hidden" name="medicaments[${i}][nom_medicament]"  value="${m.nom}">
                <input type="hidden" name="medicaments[${i}][posologie]"        value="${m.posologie}">
                <input type="hidden" name="medicaments[${i}][duree]"            value="${m.duree}">
                <input type="hidden" name="medicaments[${i}][quantite]"         value="${m.quantite}">
                <input type="hidden" name="medicaments[${i}][voie]"             value="${m.voie}">
                <input type="hidden" name="medicaments[${i}][instructions]"     value="${m.instructions}">
            </td>
            <td>${m.posologie}</td>
            <td>${m.duree || '—'}</td>
            <td><strong>${m.quantite}</strong></td>
            <td class="text-center">
                <button type="button" class="btn btn-sm btn-outline-danger rounded-circle border-0"
                        onclick="removeMed(${i})" title="Supprimer">
                    <i class="bi bi-trash"></i>
                </button>
            </td>
        </tr>`).join('');
}

function removeMed(index) {
    medicamentsPrescription.splice(index, 1);
    renderTable();
}
</script>

<?php include __DIR__ . '/../../layouts/footer.php'; ?>