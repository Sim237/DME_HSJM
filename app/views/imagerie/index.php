<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div class="container-fluid">
    <div class="row">
        <?php require_once __DIR__ . '/../layouts/sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <!-- En-tête -->
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2"><i class="bi bi-camera"></i> Imagerie Médicale</h1>
                <button class="btn btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#uploadModal">
                    <i class="bi bi-upload"></i> Compléter un examen
                </button>
            </div>

            <!-- Filtres de recherche -->
            <div class="row mb-4 g-2">
                <div class="col-md-3">
                    <select class="form-select shadow-sm" id="filterType">
                        <option value="">Tous les types d'images</option>
                        <option value="radiographie">Radiographie</option>
                        <option value="scanner">Scanner</option>
                        <option value="irm">IRM</option>
                        <option value="echographie">Échographie</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select class="form-select shadow-sm" id="filterStatut">
                        <option value="">Tous les statuts</option>
                        <option value="EN_ATTENTE">En attente (Prescription)</option>
                        <option value="termine">Terminé (Image reçue)</option>
                        <option value="interprete">Interprété (Rapport fait)</option>
                    </select>
                </div>
            </div>

            <!-- Grille d'examens -->
            <div class="row" id="examGrid">
                <?php if (empty($examens)): ?>
                    <div class="col-12 text-center py-5">
                        <i class="bi bi-inbox text-muted display-1 opacity-25"></i>
                        <p class="text-muted mt-3">Aucun examen d'imagerie dans la liste.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($examens as $examen): ?>
                    <div class="col-md-4 col-xl-3 mb-4 exam-item"
                         data-type="<?= strtolower($examen['type_examen']) ?>"
                         data-statut="<?= $examen['statut'] ?>">
                        <div class="card h-100 shadow-sm border-0 position-relative">

                            <!-- Badge Urgence -->
                            <?php if($examen['urgence'] === 'URGENT'): ?>
                                <div class="position-absolute top-0 start-0 m-2">
                                    <span class="badge bg-danger animate__animated animate__flash animate__infinite">URGENT</span>
                                </div>
                            <?php endif; ?>

                            <!-- Header de la carte -->
                            <div class="card-header bg-white d-flex justify-content-between border-0 pt-3 text-end">
                                <span class="badge rounded-pill <?=
                                    $examen['statut'] === 'termine' ? 'bg-success' :
                                    ($examen['statut'] === 'interprete' ? 'bg-info' : 'bg-warning text-dark')
                                ?>">
                                    <i class="bi bi-circle-fill me-1 small"></i>
                                    <?= htmlspecialchars(str_replace('_', ' ', ucfirst($examen['statut']))) ?>
                                </span>
                            </div>

                            <div class="card-body">
                                <!-- Preview de l'image -->
                                <div class="mb-3">
                                    <?php if (!empty($examen['fichier_preview'])): ?>
                                        <img src="<?= BASE_URL ?>assets/uploads/previews/<?= $examen['fichier_preview'] ?>"
                                             class="img-fluid rounded border shadow-sm" style="width: 100%; height: 160px; object-fit: cover;">
                                    <?php else: ?>
                                        <div class="bg-light rounded d-flex align-items-center justify-content-center border" style="height: 160px;">
                                            <i class="bi bi-file-earmark-medical text-muted display-4 opacity-50"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <h6 class="fw-bold mb-1 text-truncate"><?= htmlspecialchars($examen['nom'] . ' ' . $examen['prenom']) ?></h6>
                                <p class="small text-muted mb-2">
                                    <i class="bi bi-geo-alt"></i> <strong><?= htmlspecialchars($examen['partie_corps']) ?></strong><br>
                                    <i class="bi bi-calendar-event"></i> <?= date('d/m/Y H:i', strtotime($examen['date_creation'])) ?><br>
                                    <i class="bi bi-person-badge"></i> Dr. <?= htmlspecialchars($examen['medecin_nom']) ?>
                                </p>

                                <!-- Affichage de la conclusion si interprété -->
                                <?php if (!empty($examen['conclusion'])): ?>
                                    <div class="alert alert-info py-1 px-2 mb-3" style="font-size: 0.75rem;">
                                        <strong>Conclusion:</strong> <?= htmlspecialchars($examen['conclusion']) ?>
                                    </div>
                                <?php endif; ?>

                                <div class="btn-group w-100">
                                    <?php if (!empty($examen['fichier_dicom'])): ?>
                                        <a href="<?= BASE_URL ?>imagerie/viewer/<?= $examen['id'] ?>" class="btn btn-primary btn-sm shadow-sm">
                                            <i class="bi bi-eye"></i> Visualiser
                                        </a>
                                    <?php endif; ?>
                                    <button class="btn btn-outline-secondary btn-sm" onclick="showDetails(<?= $examen['id'] ?>)">
                                        <i class="bi bi-info-circle"></i> Détails
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </main>
    </div>
</div>

<!-- Modal Upload IMAGERIE (DICOM ou IMAGE) -->
<div class="modal fade" id="uploadModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="bi bi-clipboard2-pulse me-2"></i> Finalisation de l'examen radiologique</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="uploadForm" enctype="multipart/form-data">
                <div class="modal-body p-4">
                    <!-- Sélection de l'examen -->
                    <div class="mb-4">
                        <label class="form-label fw-bold">Sélectionner la demande du médecin</label>
                        <select class="form-select shadow-sm" name="imagerie_id" id="imagerie_selector" required onchange="showDoctorNotes(this)">
                            <option value="">-- Choisir un patient en attente --</option>
                            <?php foreach ($examens as $examen): ?>
                                <?php if ($examen['statut'] === 'EN_ATTENTE' || empty($examen['fichier_dicom'])): ?>
                                    <option value="<?= $examen['id'] ?>" data-notes="<?= htmlspecialchars($examen['observations'] ?? 'Aucune note particulière laissée par le médecin.') ?>">
                                        <?= htmlspecialchars($examen['nom']) ?> - <?= htmlspecialchars($examen['type_examen']) ?> (<?= htmlspecialchars($examen['partie_corps']) ?>)
                                    </option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Affichage dynamique des notes du médecin -->
                    <div id="doctorNotesBox" class="alert alert-warning d-none border-0 shadow-sm mb-4">
                        <h6 class="fw-bold small text-uppercase"><i class="bi bi-chat-left-text-fill me-2"></i> Indications cliniques du prescripteur :</h6>
                        <hr class="my-2">
                        <p id="doctorNotesContent" class="mb-0 small italic"></p>
                    </div>

                    <div class="row">
                        <!-- Fichier (DICOM ou IMAGE) -->
                        <div class="col-md-12 mb-3">
                            <label class="form-label fw-bold">Fichier d'imagerie (.dcm, .jpg, .png)</label>
                            <input type="file" class="form-control" name="dicom_file" accept=".dcm,.dicom,.jpg,.jpeg,.png" required>
                            <div class="form-text small">Vous pouvez uploader un fichier DICOM haute définition ou une image standard.</div>
                        </div>

                        <!-- Interprétation pour le radiologue -->
                        <div class="col-md-12 mb-3">
                            <label class="form-label fw-bold">Compte-rendu technique / Interprétation</label>
                            <textarea class="form-control shadow-sm" name="interpretation" rows="4" placeholder="Description anatomique et technique des clichés..."></textarea>
                        </div>

                        <!-- Conclusion pour le radiologue -->
                        <div class="col-md-12 mb-3">
                            <label class="form-label fw-bold">Conclusion diagnostique</label>
                            <input type="text" name="conclusion" class="form-control shadow-sm" placeholder="Ex: Suspicion de kyste ovarien, Absence de fracture...">
                        </div>
                    </div>

                    <!-- Statut d'envoi -->
                    <div id="uploadStatus" class="d-none mt-3">
                        <div class="progress" style="height: 10px;">
                            <div class="progress-bar progress-bar-striped progress-bar-animated" style="width: 100%"></div>
                        </div>
                        <p class="text-center small mt-2 fw-bold text-primary">Transfert sécurisé du fichier et du rapport...</p>
                    </div>
                </div>
                <div class="modal-footer bg-light border-0">
                    <button type="button" class="btn btn-secondary px-4 rounded-pill" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary px-5 rounded-pill shadow" id="btnUploadSubmit">
                        <i class="bi bi-check-lg"></i> Enregistrer et Terminer
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// DÉFINITION DE LA BASE_URL POUR LE JAVASCRIPT
const BASE_URL = '<?= BASE_URL ?>';

/**
 * Affiche les indications du médecin prescripteur dans la modale
 */
function showDoctorNotes(select) {
    const box = document.getElementById('doctorNotesBox');
    const content = document.getElementById('doctorNotesContent');
    const selectedOption = select.options[select.selectedIndex];

    if(selectedOption.value !== "") {
        content.innerText = selectedOption.getAttribute('data-notes');
        box.classList.remove('d-none');
        box.classList.add('animate__animated', 'animate__fadeIn');
    } else {
        box.classList.add('d-none');
    }
}

/**
 * Gestion de l'upload AJAX
 */
document.getElementById('uploadForm').addEventListener('submit', function(e) {
    e.preventDefault();

    const btnSubmit = document.getElementById('btnUploadSubmit');
    const statusDiv = document.getElementById('uploadStatus');
    const formData = new FormData(this);

    // Feedback visuel
    btnSubmit.disabled = true;
    btnSubmit.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Envoi...';
    statusDiv.classList.remove('d-none');

    fetch(`${BASE_URL}imagerie/upload`, {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) throw new Error('Erreur réseau ou serveur');
        return response.json();
    })
    .then(data => {
        if (data.success) {
            alert('✅ Examen complété et transmis au dossier patient.');
            location.reload();
        } else {
            alert('❌ Erreur : ' + data.message);
            btnSubmit.disabled = false;
            btnSubmit.innerHTML = '<i class="bi bi-check-lg"></i> Enregistrer et Terminer';
            statusDiv.classList.add('d-none');
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        alert('Erreur technique lors du transfert. Vérifiez la taille du fichier.');
        btnSubmit.disabled = false;
        btnSubmit.innerHTML = '<i class="bi bi-check-lg"></i> Enregistrer et Terminer';
        statusDiv.classList.add('d-none');
    });
});

/**
 * Filtrage dynamique des cartes
 */
document.getElementById('filterType').addEventListener('change', filterExams);
document.getElementById('filterStatut').addEventListener('change', filterExams);

function filterExams() {
    const type = document.getElementById('filterType').value.toLowerCase();
    const statut = document.getElementById('filterStatut').value;
    const items = document.querySelectorAll('.exam-item');

    items.forEach(item => {
        const matchType = type === "" || item.dataset.type === type;
        const matchStatut = statut === "" || item.dataset.statut === statut;

        if (matchType && matchStatut) {
            item.style.display = 'block';
            item.classList.add('animate__animated', 'animate__fadeIn');
        } else {
            item.style.display = 'none';
        }
    });
}

/**
 * Détails de l'examen
 */
function showDetails(id) {
    // Peut être remplacé par une modale de lecture seule détaillée
    window.location.href = `${BASE_URL}imagerie/viewer/${id}`;
}
</script>

<!-- Ajout d'animations CSS via CDN pour le dynamisme -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>