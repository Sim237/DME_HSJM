<?php
require_once __DIR__ . '/../layouts/header.php';

$patient = $patient ?? [];
$medicaments = $medicaments ?? [];
$patient_id = $_GET['patient_id'] ?? null;

// Calcul de l'âge
$age = 'N/A';
if (!empty($patient['date_naissance'])) {
    $age = date_diff(date_create($patient['date_naissance']), date_create('now'))->y . ' ans';
}

function getInitials($nom, $prenom) {
    return strtoupper(substr($nom, 0, 1) . substr($prenom, 0, 1));
}
?>

<style>
    .medicament-item {
        border: 1px solid #e9ecef;
        border-radius: 8px;
        padding: 12px;
        margin-bottom: 10px;
        background: #f8f9fa;
        cursor: pointer;
        transition: all 0.3s;
    }

    .medicament-item:hover {
        background: #e9ecef;
        border-color: #0d6efd;
    }

    .medicament-item.selected {
        background: #e7f1ff;
        border-color: #0d6efd;
        box-shadow: 0 0 0 2px rgba(13, 110, 253, 0.1);
    }

    .prescription-item {
        background: #ffffff;
        border-left: 4px solid #0d6efd;
        padding: 15px;
        margin-bottom: 12px;
        border-radius: 6px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    }

    .prescription-item .btn-remove {
        opacity: 0;
        transition: opacity 0.3s;
    }

    .prescription-item:hover .btn-remove {
        opacity: 1;
    }

    .patient-header {
        background: linear-gradient(135deg, #0d6efd 0%, #0dcaf0 100%);
        color: white;
        padding: 20px;
        border-radius: 12px;
        margin-bottom: 30px;
    }

    .patient-info {
        display: flex;
        align-items: center;
        gap: 15px;
    }

    .avatar-small {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        background: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        color: #0d6efd;
        font-size: 1.2rem;
    }
</style>

<div class="container-fluid bg-light" style="min-height: 100vh;">
    <div class="row">
        <?php include __DIR__ . '/../layouts/sidebar.php'; ?>

        <main class="col-md-10 ms-sm-auto col-lg-10 px-md-4 py-4">

            <!-- EN-TÊTE -->
            <div class="mb-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h2 class="fw-bold text-dark mb-1">Nouvelle Ordonnance</h2>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb mb-0">
                                <li class="breadcrumb-item"><a href="<?= BASE_URL ?>patients" class="text-decoration-none">Patients</a></li>
                                <li class="breadcrumb-item"><a href="<?= BASE_URL ?>consultation/dossier/<?= $patient_id ?>" class="text-decoration-none">Dossier</a></li>
                                <li class="breadcrumb-item active">Nouvelle Ordonnance</li>
                            </ol>
                        </nav>
                    </div>
                    <a href="javascript:history.back()" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left"></i> Retour
                    </a>
                </div>
            </div>

            <?php if ($patient): ?>
            <!-- EN-TÊTE PATIENT -->
            <div class="patient-header mb-4">
                <div class="patient-info">
                    <div class="avatar-small">
                        <?= getInitials($patient['nom'], $patient['prenom']) ?>
                    </div>
                    <div>
                        <h5 class="mb-0"><?= htmlspecialchars($patient['nom'] . ' ' . $patient['prenom']) ?></h5>
                        <small class="opacity-75">
                            N° Dossier: <?= htmlspecialchars($patient['dossier_numero']) ?> |
                            Âge: <?= $age ?> |
                            Sexe: <?= $patient['sexe'] === 'M' ? 'M' : 'F' ?>
                        </small>
                    </div>
                </div>
            </div>

            <div class="row g-4">
                <!-- COLONNE GAUCHE : Sélection de médicaments -->
                <div class="col-lg-6">
                    <div class="card shadow-sm border-0">
                        <div class="card-header bg-white border-bottom">
                            <h5 class="mb-0"><i class="bi bi-capsule me-2"></i>Ajouter des Médicaments</h5>
                        </div>
                        <div class="card-body">
                            <!-- Barre de recherche -->
                            <div class="mb-3">
                                <input type="text" class="form-control form-control-lg" id="searchMedicament" placeholder="🔍 Rechercher un médicament...">
                                <small class="text-muted d-block mt-2">Cliquez sur un médicament pour l'ajouter</small>
                            </div>

                            <!-- Liste des médicaments -->
                            <div id="medicamentsList" style="max-height: 500px; overflow-y: auto;">
                                <?php if (count($medicaments) > 0): ?>
                                    <?php foreach ($medicaments as $med): ?>
                                        <div class="medicament-item" onclick="selectMedicament(<?= htmlspecialchars(json_encode($med)) ?>)">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <div>
                                                    <strong><?= htmlspecialchars($med['nom']) ?></strong>
                                                    <br>
                                                    <small class="text-muted">
                                                        <?= htmlspecialchars($med['dosage'] . ' ' . $med['forme']) ?>
                                                        <span class="badge bg-light text-secondary ms-2">Stock: <?= $med['quantite'] ?></span>
                                                    </small>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="alert alert-info">
                                        <i class="bi bi-info-circle me-2"></i>
                                        Aucun médicament disponible
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- COLONNE DROITE : Prescription -->
                <div class="col-lg-6">
                    <form action="<?= BASE_URL ?>prescription/save" method="POST" id="prescriptionForm">
                        <input type="hidden" name="patient_id" value="<?= htmlspecialchars($patient_id) ?>">

                        <div class="card shadow-sm border-0 mb-4">
                            <div class="card-header bg-white border-bottom">
                                <h5 class="mb-0"><i class="bi bi-list-check me-2"></i>Ordonnance</h5>
                            </div>
                            <div class="card-body">
                                <!-- Médicaments prescrits -->
                                <div id="prescribedMedicaments" style="min-height: 200px;">
                                    <div class="text-center text-muted py-5">
                                        <i class="bi bi-inbox display-5 opacity-25"></i>
                                        <p class="mt-2">Aucun médicament sélectionné</p>
                                    </div>
                                </div>

                                <!-- Champ caché pour stocker les médicaments -->
                                <input type="hidden" name="medicaments" id="medicamentsInput" value="[]">

                                <!-- Champ notes -->
                                <div class="mb-3 mt-4">
                                    <label for="notes" class="form-label">Notes/Remarques</label>
                                    <textarea class="form-control" name="notes" id="notes" rows="3" placeholder="Conseils, précautions, observations..."></textarea>
                                </div>

                                <!-- Boutons d'action -->
                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-primary btn-lg" id="submitBtn" disabled>
                                        <i class="bi bi-check-circle me-2"></i> Valider la Prescription
                                    </button>
                                    <a href="javascript:history.back()" class="btn btn-outline-secondary">
                                        Annuler
                                    </a>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <?php else: ?>
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    Patient non trouvé
                </div>
            <?php endif; ?>
        </main>
    </div>
</div>

<!-- Script pour la gestion des médicaments -->
<script>
let medicamentList = [];

function selectMedicament(med) {
    // Vérifier si le médicament est déjà en liste
    if (medicamentList.some(m => m.id === med.id)) {
        return;
    }

    medicamentList.push({
        id: med.id,
        nom: med.nom,
        dosage: med.dosage,
        forme: med.forme,
        posologie: '',
        duree: '',
        quantite: 1
    });

    renderPrescription();
    updateMedicamentsInput();
}

function removeMedicament(medicamentId) {
    medicamentList = medicamentList.filter(m => m.id !== medicamentId);
    renderPrescription();
    updateMedicamentsInput();
}

function updateMedicamentField(medicamentId, field, value) {
    const med = medicamentList.find(m => m.id === medicamentId);
    if (med) {
        med[field] = value;
        updateMedicamentsInput();
    }
}

function renderPrescription() {
    const container = document.getElementById('prescribedMedicaments');
    const submitBtn = document.getElementById('submitBtn');

    if (medicamentList.length === 0) {
        container.innerHTML = `
            <div class="text-center text-muted py-5">
                <i class="bi bi-inbox display-5 opacity-25"></i>
                <p class="mt-2">Aucun médicament sélectionné</p>
            </div>
        `;
        submitBtn.disabled = true;
        return;
    }

    submitBtn.disabled = false;

    let html = '';
    medicamentList.forEach(med => {
        html += `
            <div class="prescription-item">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div>
                        <strong>${med.nom}</strong>
                        <br>
                        <small class="text-muted">${med.dosage} ${med.forme}</small>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-danger btn-remove" onclick="removeMedicament(${med.id})">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
                <div class="row g-2">
                    <div class="col-md-6">
                        <label class="form-label small">Posologie</label>
                        <input type="text" class="form-control form-control-sm" placeholder="Ex: 1 cp x 3/jour"
                               value="${med.posologie}" onchange="updateMedicamentField(${med.id}, 'posologie', this.value)">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small">Durée</label>
                        <input type="text" class="form-control form-control-sm" placeholder="Ex: 7 jours, 1 mois"
                               value="${med.duree}" onchange="updateMedicamentField(${med.id}, 'duree', this.value)">
                    </div>
                </div>
            </div>
        `;
    });

    container.innerHTML = html;
}

function updateMedicamentsInput() {
    document.getElementById('medicamentsInput').value = JSON.stringify(medicamentList);
}

// Recherche de médicaments
document.getElementById('searchMedicament').addEventListener('keyup', function() {
    const query = this.value.toLowerCase();
    const items = document.querySelectorAll('.medicament-item');

    items.forEach(item => {
        const text = item.textContent.toLowerCase();
        item.style.display = text.includes(query) ? '' : 'none';
    });
});
</script>

<?php include __DIR__ . '/../layouts/footer.php'; ?>
