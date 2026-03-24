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
     MODALE : AJOUT MÉDICAMENT (RECHERCHE + MANUEL)
     ========================================================================= -->
<div class="modal fade" id="modalAjouterMedicament" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: 25px;">
            <div class="modal-header border-0 p-4">
                <h5 class="fw-bold mb-0">Ajouter à la prescription</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4 pt-0">
                <form id="formModalMed">
                    <!-- Zone Recherche Stock -->
                    <div id="search-box-container">
                        <label class="form-label-custom">Rechercher en Pharmacie</label>
                        <div class="search-container mb-2">
                            <input type="text" id="med-search-input" class="form-control input-custom" placeholder="Commencez à taper le nom...">
                            <div id="list-results" class="d-none"></div>
                        </div>
                        <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none" id="btn-toggle-manual">
                            <i class="bi bi-pencil-square"></i> Absent du stock ? Saisie manuelle
                        </button>
                    </div>

                    <!-- Zone Saisie Manuelle (Cachée) -->
                    <div id="manual-input-box" class="p-3 rounded-4 mb-3 d-none" style="background: #fffbeb; border: 1px solid #fde68a;">
                        <div class="d-flex justify-content-between mb-2">
                            <label class="form-label-custom text-warning-dark mb-0">Nom du médicament libre</label>
                            <button type="button" class="btn-close btn-sm" id="btn-close-manual"></button>
                        </div>
                        <input type="text" id="manual-med-name" class="form-control input-custom" placeholder="Nom + Dosage">
                    </div>

                    <!-- Médicament sélectionné (Alerte Bleue) -->
                    <div id="selected-med-info" class="alert alert-primary d-none mb-3 border-0 rounded-4">
                        <div class="d-flex justify-content-between align-items-center">
                            <div><strong id="display-name"></strong><br><small id="display-detail"></small></div>
                            <span id="display-stock" class="badge bg-white text-primary"></span>
                        </div>
                        <input type="hidden" id="hidden-med-id">
                        <input type="hidden" id="hidden-med-nom">
                    </div>

                    <!-- Posologie -->
                    <div class="row g-3 mt-2">
                        <div class="col-md-6">
                            <label class="form-label-custom">Posologie</label>
                            <input type="text" id="in-poso" class="form-control input-custom" placeholder="ex: 1 cp matin/soir">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label-custom">Durée</label>
                            <input type="text" id="in-duree" class="form-control input-custom" placeholder="ex: 5 jours">
                        </div>
                        <div class="col-12">
                            <label class="form-label-custom">Quantité totale</label>
                            <input type="number" id="in-qte" class="form-control input-custom" placeholder="ex: 2">
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer border-0 p-4">
                <button type="button" class="btn btn-primary w-100 rounded-pill py-3 fw-bold shadow" onclick="ajouterMedicamentAction()">
                    AJOUTER À L'ORDONNANCE
                </button>
            </div>
        </div>
    </div>
</div>

<script>
let medicamentsPrescription = []; // État global de l'ordonnance

document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('med-search-input');
    const resultsBox = document.getElementById('list-results');
    const manualBox = document.getElementById('manual-input-box');
    const searchContainer = document.getElementById('search-box-container');
    const infoBox = document.getElementById('selected-med-info');

    // 1. RECHERCHE AJAX DYNAMIQUE
    searchInput.addEventListener('input', function() {
        let q = this.value;
        if(q.length < 2) { resultsBox.classList.add('d-none'); return; }

        fetch(`<?= BASE_URL ?>pharmacie/search-medicaments?term=${q}`)
            .then(res => res.json())
            .then(data => {
                resultsBox.innerHTML = '';
                if(data.length > 0) {
                    data.forEach(med => {
                        const div = document.createElement('div');
                        div.className = 'result-item';
                        div.innerHTML = `
                            <div><strong>${med.nom}</strong><br><small class="text-muted">${med.forme} - ${med.dosage}</small></div>
                            <span class="badge ${med.stock_actuel > 0 ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger'}">${med.stock_actuel} dispo</span>
                        `;
                        div.onclick = () => {
                            document.getElementById('hidden-med-id').value = med.id;
                            document.getElementById('hidden-med-nom').value = med.nom;
                            document.getElementById('display-name').innerText = med.nom;
                            document.getElementById('display-detail').innerText = `${med.forme} - ${med.dosage}`;
                            document.getElementById('display-stock').innerText = med.stock_actuel + " en stock";
                            infoBox.classList.remove('d-none');
                            resultsBox.classList.add('d-none');
                            searchInput.value = '';
                        };
                        resultsBox.appendChild(div);
                    });
                    resultsBox.classList.remove('d-none');
                }
            });
    });

    // 2. TOGGLE MANUEL / STOCK
    document.getElementById('btn-toggle-manual').onclick = () => {
        searchContainer.classList.add('d-none');
        manualBox.classList.remove('d-none');
        infoBox.classList.add('d-none');
        document.getElementById('hidden-med-id').value = '';
    };

    document.getElementById('btn-close-manual').onclick = () => {
        manualBox.classList.add('d-none');
        searchContainer.classList.remove('d-none');
    };
});

// 3. ACTION : AJOUTER AU TABLEAU
function ajouterMedicamentAction() {
    const medId = document.getElementById('hidden-med-id').value;
    const medNom = medId ? document.getElementById('hidden-med-nom').value : document.getElementById('manual-med-name').value;
    const poso = document.getElementById('in-poso').value;
    const duree = document.getElementById('in-duree').value;
    const qte = document.getElementById('in-qte').value;

    if(!medNom || !poso || !qte) { alert("Veuillez remplir les informations du médicament."); return; }

    const item = { id: medId, nom: medNom, posologie: poso, duree: duree, quantite: qte };
    medicamentsPrescription.push(item);

    renderTable();

    // Fermer et reset
    bootstrap.Modal.getInstance(document.getElementById('modalAjouterMedicament')).hide();
    document.getElementById('formModalMed').reset();
    document.getElementById('selected-med-info').classList.add('d-none');
}

// 4. RENDU DU TABLEAU
function renderTable() {
    const tbody = document.getElementById('listeMedicaments');
    const empty = document.getElementById('emptyState');

    if(medicamentsPrescription.length === 0) {
        tbody.innerHTML = '';
        empty.classList.remove('d-none');
        return;
    }

    empty.classList.add('d-none');
    tbody.innerHTML = medicamentsPrescription.map((m, index) => `
        <tr>
            <td class="ps-4">
                <div class="fw-bold">${m.nom}</div>
                <small class="text-muted">${m.id ? 'Identifié en stock' : 'Saisie manuelle'}</small>
                <input type="hidden" name="medicaments[${index}][medicament_id]" value="${m.id}">
                <input type="hidden" name="medicaments[${index}][nom_medicament]" value="${m.nom}">
                <input type="hidden" name="medicaments[${index}][posologie]" value="${m.posologie}">
                <input type="hidden" name="medicaments[${index}][duree]" value="${m.duree}">
                <input type="hidden" name="medicaments[${index}][quantite]" value="${m.quantite}">
            </td>
            <td>${m.posologie}</td>
            <td>${m.duree}</td>
            <td><strong>${m.quantite}</strong></td>
            <td class="text-center">
                <button type="button" class="btn btn-sm btn-outline-danger border-0" onclick="removeMed(${index})">
                    <i class="bi bi-trash"></i>
                </button>
            </td>
        </tr>
    `).join('');
}

function removeMed(index) {
    medicamentsPrescription.splice(index, 1);
    renderTable();
}
</script>

<?php include __DIR__ . '/../../layouts/footer.php'; ?>