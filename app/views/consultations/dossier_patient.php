<?php
require_once __DIR__ . '/../layouts/header.php';

// --- Fonctions et Initialisation ---
function getInitials($nom, $prenom) {
    return strtoupper(substr($nom, 0, 1) . substr($prenom, 0, 1));
}

$patient = $patient ?? [];
$parametres = $patient['parametres'] ?? null;
// Calcul de l'âge
$age = 'N/A';
if (!empty($patient['date_naissance'])) {
    $age = date_diff(date_create($patient['date_naissance']), date_create('now'))->y . ' ans';
}
?>

<!-- STYLE MODERNE -->
<style>
    /* Carte Profil à Gauche */
    .profile-card {
        border: none;
        border-radius: 16px;
        background: #fff;
        overflow: hidden;
        box-shadow: 0 10px 30px rgba(0,0,0,0.08);
    }
    .profile-header {
        background: linear-gradient(135deg, #0d6efd, #0dcaf0);
        height: 110px;
    }
    .avatar-wrapper {
        margin-top: -60px;
        display: flex;
        justify-content: center;
    }
    .avatar-circle {
        width: 110px;
        height: 110px;
        border-radius: 50%;
        background: #fff;
        border: 5px solid #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2.5rem;
        font-weight: 800;
        color: #0d6efd;
        box-shadow: 0 5px 15px rgba(0,0,0,0.15);
    }

    /* Cartes Paramètres Vitaux */
    .vital-box {
        background: #fff;
        border-radius: 12px;
        padding: 18px;
        border-left: 5px solid #ccc;
        box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        transition: transform 0.2s, box-shadow 0.2s;
        height: 100%;
    }
    .vital-box:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 20px rgba(0,0,0,0.1);
    }
    .vital-box.temp { border-color: #dc3545; }
    .vital-box.tension { border-color: #0d6efd; }
    .vital-box.pouls { border-color: #198754; }
    .vital-box.poids { border-color: #ffc107; }

    .vital-label { font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.5px; color: #6c757d; font-weight: 600; }
    .vital-value { font-size: 1.8rem; font-weight: 700; color: #212529; }
    .vital-unit { font-size: 0.9rem; color: #adb5bd; font-weight: 500; }

    /* Onglets */
    .nav-tabs { border-bottom: 2px solid #e9ecef; }
    .nav-tabs .nav-link {
        border: none;
        color: #6c757d;
        font-weight: 600;
        padding: 15px 25px;
        background: transparent;
        transition: all 0.3s;
    }
    .nav-tabs .nav-link:hover { color: #0d6efd; background: rgba(13, 110, 253, 0.05); }
    .nav-tabs .nav-link.active {
        color: #0d6efd;
        border-bottom: 3px solid #0d6efd;
        background: transparent;
    }
    .tab-content {
        background: #fff;
        padding: 30px;
        border-radius: 0 0 16px 16px;
        box-shadow: 0 5px 20px rgba(0,0,0,0.03);
    }

    /* Info List */
    .info-item {
        padding: 12px 0;
        border-bottom: 1px solid #f1f5f9;
        display: flex;
        align-items: center;
    }
    .info-item:last-child { border-bottom: none; }
    .info-icon {
        width: 36px;
        height: 36px;
        background: #f8f9fa;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 15px;
        color: #0d6efd;
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
                        <h2 class="fw-bold text-dark mb-1">Dossier Médical</h2>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb mb-0">
                                <li class="breadcrumb-item"><a href="<?= BASE_URL ?>patients" class="text-decoration-none">Patients</a></li>
                                <li class="breadcrumb-item active">Dossier N° <?= htmlspecialchars($patient['dossier_numero']) ?></li>
                            </ol>
                        </nav>
                    </div>
                    <a href="<?= BASE_URL ?>patients" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left"></i> Retour
                    </a>
                </div>

                <!-- BARRE D'ACTIONS -->
                <div class="row g-2">
                    <div class="col-auto">
                        <!-- Nouvelle Consultation -->
                        <form action="<?= BASE_URL ?>consultation/commencer" method="POST" style="display:inline;">
                            <input type="hidden" name="patient_id" value="<?= $patient['id'] ?>">
                            <input type="hidden" name="type_consultation" value="externe">
                            <button type="submit" class="btn btn-primary shadow-sm">
                                <i class="bi bi-plus-lg me-2"></i> Nouvelle Consultation
                            </button>
                        </form>
                    </div>
                    <div class="col-auto">
                        <!-- Ajouter une Ordonnance -->
                        <a href="<?= BASE_URL ?>prescription/create?patient_id=<?= $patient['id'] ?>" class="btn btn-success shadow-sm">
                            <i class="bi bi-capsule me-2"></i> Ajouter une Ordonnance
                        </a>
                    </div>
                    <div class="col-auto">
                        <!-- Demander un Bilan -->
                        <button type="button" class="btn btn-info shadow-sm" data-bs-toggle="modal" data-bs-target="#modalBilan">
                            <i class="bi bi-clipboard-check me-2"></i> Demander un Bilan
                        </button>
                    </div>
                </div>
            </div>

            <div class="row g-4">

                <!-- COLONNE GAUCHE : IDENTITÉ (30%) -->
                <div class="col-lg-3">
                    <!-- Carte Profil -->
                    <div class="profile-card mb-4 position-sticky" style="top: 20px;">
                        <div class="profile-header"></div>
                        <div class="avatar-wrapper">
                            <div class="avatar-circle">
                                <?= getInitials($patient['nom'], $patient['prenom']) ?>
                            </div>
                        </div>
                        <div class="card-body text-center mt-2 px-4 pb-4">
                            <h4 class="fw-bold mb-1"><?= htmlspecialchars($patient['nom'] . ' ' . $patient['prenom']) ?></h4>
                            <span class="badge bg-light text-dark border px-3 py-2 rounded-pill">
                                <?= htmlspecialchars($patient['dossier_numero']) ?>
                            </span>

                            <div class="mt-4 text-start">
                                <div class="info-item">
                                    <div class="info-icon"><i class="bi bi-cake2"></i></div>
                                    <div>
                                        <small class="text-muted d-block">Âge</small>
                                        <strong><?= $age ?></strong>
                                    </div>
                                </div>
                                <div class="info-item">
                                    <div class="info-icon"><i class="bi bi-gender-ambiguous"></i></div>
                                    <div>
                                        <small class="text-muted d-block">Sexe</small>
                                        <strong><?= $patient['sexe'] === 'M' ? 'Masculin' : 'Féminin' ?></strong>
                                    </div>
                                </div>
                                <div class="info-item">
                                    <div class="info-icon"><i class="bi bi-telephone"></i></div>
                                    <div>
                                        <small class="text-muted d-block">Téléphone</small>
                                        <strong><?= htmlspecialchars($patient['telephone'] ?: 'Non renseigné') ?></strong>
                                    </div>
                                </div>
                                <div class="info-item">
                                    <div class="info-icon text-danger bg-danger bg-opacity-10"><i class="bi bi-droplet-fill"></i></div>
                                    <div>
                                        <small class="text-muted d-block">Groupe Sanguin</small>
                                        <strong><?= $patient['groupe_sanguin'] ?: 'Inconnu' ?></strong>
                                    </div>
                                </div>
                            </div>

                            <div class="d-grid mt-4">
                                <a href="<?= BASE_URL ?>patients/mesures/<?= $patient['id'] ?>" class="btn btn-outline-primary rounded-pill">
                                    <i class="bi bi-activity me-2"></i> Saisir Constantes
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- COLONNE DROITE : CLINIQUE (70%) -->
                <div class="col-lg-9">

                    <!-- 1. Constantes Vitales (Haut de page) -->
                    <div class="row g-3 mb-4">
                        <div class="col-md-3 col-6">
                            <div class="vital-box temp">
                                <div class="vital-label">Température</div>
                                <div class="vital-value"><?= $parametres['temperature'] ?? '--' ?> <span class="vital-unit">°C</span></div>
                            </div>
                        </div>
                        <div class="col-md-3 col-6">
                            <div class="vital-box tension">
                                <div class="vital-label">Tension</div>
                                <div class="vital-value">
                                    <?= isset($parametres['pression_arterielle_systolique']) ? $parametres['pression_arterielle_systolique'].'/'.$parametres['pression_arterielle_diastolique'] : '--/--' ?>
                                    <span class="vital-unit">mmHg</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 col-6">
                            <div class="vital-box pouls">
                                <div class="vital-label">Pouls</div>
                                <div class="vital-value"><?= $parametres['frequence_cardiaque'] ?? '--' ?> <span class="vital-unit">bpm</span></div>
                            </div>
                        </div>
                        <div class="col-md-3 col-6">
                            <div class="vital-box poids">
                                <div class="vital-label">Poids</div>
                                <div class="vital-value"><?= $parametres['poids'] ?? '--' ?> <span class="vital-unit">kg</span></div>
                            </div>
                        </div>
                    </div>

                    <!-- 2. Système d'Onglets -->
                    <div class="bg-white rounded-4 shadow-sm overflow-hidden">
                        <ul class="nav nav-tabs px-4 pt-2" id="myTab" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="consultations-tab" data-bs-toggle="tab" data-bs-target="#consultations" type="button">
                                    <i class="bi bi-journal-text me-2"></i> Consultations
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="antecedents-tab" data-bs-toggle="tab" data-bs-target="#antecedents" type="button">
                                    <i class="bi bi-clock-history me-2"></i> Antécédents
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="prescriptions-tab" data-bs-toggle="tab" data-bs-target="#prescriptions" type="button">
                                    <i class="bi bi-capsule me-2"></i> Ordonnances
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="examens-tab" data-bs-toggle="tab" data-bs-target="#examens" type="button">
                                    <i class="bi bi-flask me-2"></i> Examens
                                </button>
                            </li>
                        </ul>

                        <div class="tab-content" id="myTabContent">

                            <!-- Onglet Consultations -->
                            <div class="tab-pane fade show active" id="consultations" role="tabpanel">
                                <?php if(!empty($consultations)): ?>
                                    <div class="list-group list-group-flush">
                                        <?php foreach($consultations as $c): ?>
                                            <a href="#" class="list-group-item list-group-item-action border-0 border-bottom py-3">
                                                <div class="d-flex w-100 justify-content-between align-items-center mb-2">
                                                    <h6 class="mb-0 text-primary fw-bold">
                                                        <i class="bi bi-clipboard-pulse me-2"></i>
                                                        <?= htmlspecialchars($c['motif_consultation'] ?? 'Consultation') ?>
                                                    </h6>
                                                    <span class="badge bg-light text-secondary border">
                                                        <?= date('d/m/Y', strtotime($c['date_consultation'])) ?>
                                                    </span>
                                                </div>
                                                <p class="mb-2 text-dark opacity-75"><?= htmlspecialchars($c['diagnostic_principal'] ?? '') ?></p>
                                                <small class="text-muted"><i class="bi bi-person-badge me-1"></i> Dr. <?= htmlspecialchars($c['medecin_nom'] ?? 'Inconnu') ?></small>
                                            </a>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <div class="text-center py-5">
                                        <div class="mb-3 text-muted opacity-25">
                                            <i class="bi bi-journal-plus display-3"></i>
                                        </div>
                                        <h5 class="text-muted">Aucune consultation</h5>
                                        <p class="text-muted small">L'historique des consultations apparaîtra ici.</p>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- Onglet Antécédents -->
                            <div class="tab-pane fade" id="antecedents" role="tabpanel">
                                <div class="row g-4">
                                    <div class="col-md-6">
                                        <div class="p-4 rounded-3 h-100" style="background-color: #fff5f5; border: 1px solid #fed7d7;">
                                            <h6 class="text-danger fw-bold mb-3 d-flex align-items-center">
                                                <i class="bi bi-exclamation-triangle-fill me-2 fs-5"></i> Allergies
                                            </h6>
                                            <p class="mb-0 text-dark"><?= nl2br(htmlspecialchars($patient['allergies'] ?: 'Aucune allergie signalée.')) ?></p>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="p-4 rounded-3 h-100" style="background-color: #ebf8ff; border: 1px solid #bee3f8;">
                                            <h6 class="text-primary fw-bold mb-3 d-flex align-items-center">
                                                <i class="bi bi-file-medical-fill me-2 fs-5"></i> Antécédents Médicaux
                                            </h6>
                                            <p class="mb-0 text-dark"><?= nl2br(htmlspecialchars($patient['antecedents_medicaux'] ?: 'Rien à signaler.')) ?></p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Onglet Prescriptions -->
                            <div class="tab-pane fade" id="prescriptions" role="tabpanel">
                                <div class="text-center py-5">
                                    <div class="mb-3 text-muted opacity-25">
                                        <i class="bi bi-capsule-pill display-3"></i>
                                    </div>
                                    <h5 class="text-muted">Aucune prescription</h5>
                                </div>
                            </div>

                            <!-- Onglet Examens -->
                            <div class="tab-pane fade" id="examens" role="tabpanel">
                                <div class="text-center py-5">
                                    <div class="mb-3 text-muted opacity-25">
                                        <i class="bi bi-clipboard-data display-3"></i>
                                    </div>
                                    <h5 class="text-muted">Aucun examen</h5>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- MODAL DEMANDER UN BILAN -->
<div class="modal fade" id="modalBilan" tabindex="-1" role="dialog" aria-labelledby="modalBilanLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalBilanLabel">Demander un Bilan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Sélection du type de bilan -->
                <div class="mb-4">
                    <label class="form-label fw-bold">Type de Bilan</label>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-check">
                                <input class="form-check-input bilan-type-radio" type="radio" name="typeBilan" id="typeLaboratoire" value="laboratoire" checked>
                                <label class="form-check-label" for="typeLaboratoire">
                                    <i class="bi bi-flask me-2"></i> <strong>Bilan de Laboratoire</strong>
                                    <small class="d-block text-muted">Analyses sanguines, biochimie, etc.</small>
                                </label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check">
                                <input class="form-check-input bilan-type-radio" type="radio" name="typeBilan" id="typeImagerie" value="imagerie">
                                <label class="form-check-label" for="typeImagerie">
                                    <i class="bi bi-image me-2"></i> <strong>Bilan d'Imagerie</strong>
                                    <small class="d-block text-muted">Radiographie, Échographie, Scanner</small>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Formulaire Laboratoire -->
                <div id="formLaboratoire" style="display: block;">
                    <form action="<?= BASE_URL ?>bilan/save" method="POST" id="formBilanLab">
                        <input type="hidden" name="patient_id" value="<?= $patient['id'] ?>">
                        <input type="hidden" name="type_bilan" value="laboratoire">

                        <div class="mb-3">
                            <label for="examenLab" class="form-label">Type d'Examen</label>
                            <select class="form-select" name="examen_id" id="examenLab" required>
                                <option value="">-- Sélectionner un examen --</option>
                                <option value="hemogramme">Hémogramme (NFS)</option>
                                <option value="bilan_metabolique">Bilan Métabolique</option>
                                <option value="bilan_lipidique">Bilan Lipidique</option>
                                <option value="fonction_hepatique">Fonction Hépatique</option>
                                <option value="fonction_renale">Fonction Rénale</option>
                                <option value="groupe_sanguin">Groupe Sanguin</option>
                                <option value="coagulation">Bilan de Coagulation</option>
                                <option value="autre_lab">Autre (à spécifier)</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="urgenceLab" class="form-label">Niveau d'Urgence</label>
                            <select class="form-select" name="urgence" id="urgenceLab" required>
                                <option value="NORMAL">Normal</option>
                                <option value="URGENT">Urgent</option>
                                <option value="TRES_URGENT">Très Urgent</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="observationsLab" class="form-label">Observations/Indications</label>
                            <textarea class="form-control" name="observations" id="observationsLab" rows="3" placeholder="Contexte clinique, symptômes particuliers, etc."></textarea>
                        </div>

                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-check-circle me-2"></i> Valider la Demande de Laboratoire
                        </button>
                    </form>
                </div>

                <!-- Formulaire Imagerie -->
                <div id="formImagerie" style="display: none;">
                    <form action="<?= BASE_URL ?>bilan/save" method="POST" id="formBilanImg">
                        <input type="hidden" name="patient_id" value="<?= $patient['id'] ?>">
                        <input type="hidden" name="type_bilan" value="imagerie">

                        <div class="mb-3">
                            <label for="typeImagerie" class="form-label">Type d'Imagerie</label>
                            <select class="form-select" name="type_imagerie" id="typeImagerie" required onchange="updatePartieCode()">
                                <option value="">-- Sélectionner une imagerie --</option>
                                <option value="radiographie">Radiographie</option>
                                <option value="echographie">Échographie</option>
                                <option value="scanner">Scanner (TDM)</option>
                                <option value="irm">IRM</option>
                                <option value="autre_imagerie">Autre (à spécifier)</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="partieCode" class="form-label">Zone/Organe à examiner</label>
                            <div id="partieCodeDiv">
                                <input type="text" class="form-control" name="partie_code" id="partieCode" placeholder="Ex: Radio de la main droite, Scanner pulmonaire, etc." required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="urgenceImg" class="form-label">Niveau d'Urgence</label>
                            <select class="form-select" name="urgence" id="urgenceImg" required>
                                <option value="NORMAL">Normal</option>
                                <option value="URGENT">Urgent</option>
                                <option value="TRES_URGENT">Très Urgent</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="observationsImg" class="form-label">Indications Cliniques</label>
                            <textarea class="form-control" name="observations" id="observationsImg" rows="3" placeholder="Raison de l'imagerie, symptômes, antécédents pertinents..."></textarea>
                        </div>

                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-check-circle me-2"></i> Valider la Demande d'Imagerie
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Script pour toggle entre Laboratoire et Imagerie -->
<script>
document.querySelectorAll('.bilan-type-radio').forEach(radio => {
    radio.addEventListener('change', function() {
        if (this.value === 'laboratoire') {
            document.getElementById('formLaboratoire').style.display = 'block';
            document.getElementById('formImagerie').style.display = 'none';
        } else {
            document.getElementById('formLaboratoire').style.display = 'none';
            document.getElementById('formImagerie').style.display = 'block';
        }
    });
});

function updatePartieCode() {
    // Peut être complété avec des prédéfinis selon le type d'imagerie
}
</script>

<?php include __DIR__ . '/../layouts/footer.php'; ?>