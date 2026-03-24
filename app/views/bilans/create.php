<?php
require_once __DIR__ . '/../layouts/header.php';

$patient = $patient ?? [];
$patient_id = $_GET['patient_id'] ?? null;
$type_bilan = $_GET['type_bilan'] ?? 'laboratoire';

function getInitials($nom, $prenom) {
    return strtoupper(substr($nom, 0, 1) . substr($prenom, 0, 1));
}

// Calcul de l'âge
$age = 'N/A';
if (!empty($patient['date_naissance'])) {
    $age = date_diff(date_create($patient['date_naissance']), date_create('now'))->y . ' ans';
}
?>

<style>
    .bilan-form-container {
        max-width: 600px;
        margin: 0 auto;
    }

    .form-section {
        background: white;
        border-radius: 12px;
        padding: 25px;
        margin-bottom: 20px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    }

    .form-section h5 {
        color: #0d6efd;
        border-bottom: 2px solid #e9ecef;
        padding-bottom: 12px;
        margin-bottom: 20px;
    }

    .patient-badge {
        background: linear-gradient(135deg, #0d6efd 0%, #0dcaf0 100%);
        color: white;
        padding: 15px;
        border-radius: 12px;
        margin-bottom: 25px;
        display: flex;
        align-items: center;
        gap: 15px;
    }

    .patient-badge-avatar {
        width: 50px;
        height: 50px;
        background: white;
        border-radius: 50%;
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
                        <h2 class="fw-bold text-dark mb-1">Demande de Bilan</h2>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb mb-0">
                                <li class="breadcrumb-item"><a href="<?= BASE_URL ?>patients" class="text-decoration-none">Patients</a></li>
                                <li class="breadcrumb-item"><a href="<?= BASE_URL ?>consultation/dossier/<?= $patient_id ?>" class="text-decoration-none">Dossier</a></li>
                                <li class="breadcrumb-item active">Demande de Bilan</li>
                            </ol>
                        </nav>
                    </div>
                    <a href="<?= BASE_URL ?>consultation/dossier/<?= $patient_id ?>" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left"></i> Retour
                    </a>
                </div>
            </div>

            <?php if ($patient): ?>
            <!-- INFO PATIENT -->
            <div class="patient-badge">
                <div class="patient-badge-avatar">
                    <?= getInitials($patient['nom'], $patient['prenom']) ?>
                </div>
                <div>
                    <strong><?= htmlspecialchars($patient['nom'] . ' ' . $patient['prenom']) ?></strong>
                    <br>
                    <small class="opacity-75">
                        N° <?= htmlspecialchars($patient['dossier_numero']) ?> |
                        <?= $age ?> |
                        <?= $patient['sexe'] === 'M' ? 'Masculin' : 'Féminin' ?>
                    </small>
                </div>
            </div>

            <div class="bilan-form-container">

                <!-- FORMULAIRE BILAN LABORATOIRE -->
                <?php if ($type_bilan === 'laboratoire'): ?>
                <form action="<?= BASE_URL ?>bilan/save" method="POST" id="formBilanLaboratoire">
                    <input type="hidden" name="patient_id" value="<?= htmlspecialchars($patient_id) ?>">
                    <input type="hidden" name="type_bilan" value="laboratoire">

                    <!-- Section Examen -->
                    <div class="form-section">
                        <h5><i class="bi bi-flask me-2"></i>Sélection d'Examen</h5>

                        <div class="mb-3">
                            <label for="examenLab" class="form-label fw-bold">Type d'Examen</label>
                            <select class="form-select form-select-lg" name="examen_id" id="examenLab" required>
                                <option value="">-- Sélectionner un examen --</option>
                                <option value="1">Hémogramme (NFS - Numération Formule Sanguine)</option>
                                <option value="2">Bilan Métabolique Complet</option>
                                <option value="3">Bilan Lipidique</option>
                                <option value="4">Bilan Hépatique (Foie)</option>
                                <option value="5">Bilan Rénal (Rein)</option>
                                <option value="6">Groupe Sanguin et Rhésus</option>
                                <option value="7">Bilan de Coagulation</option>
                                <option value="8">Glycémie Veineuse</option>
                                <option value="9">Troponine (Marqueur Cardiaque)</option>
                                <option value="10">Cultures Microbiologiques</option>
                                <option value="11">Sérologie (VIH, Hépatite, Syphilis)</option>
                                <option value="12">Analyse d'Urine</option>
                                <option value="13">Analyse de Selles</option>
                                <option value="14">Autre - À Spécifier</option>
                            </select>
                            <small class="text-muted d-block mt-2">Sélectionnez le type d'examen que vous demandez</small>
                        </div>
                    </div>

                    <!-- Section Urgence et Observations -->
                    <div class="form-section">
                        <h5><i class="bi bi-info-circle me-2"></i>Détails de la Demande</h5>

                        <div class="mb-3">
                            <label for="urgenceLab" class="form-label fw-bold">Niveau d'Urgence</label>
                            <select class="form-select form-select-lg" name="urgence" id="urgenceLab" required>
                                <option value="NORMAL" selected>Routine (délai normal)</option>
                                <option value="URGENT">Urgent (24-48 heures)</option>
                                <option value="TRES_URGENT">Très Urgent (immédiat)</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="observationsLab" class="form-label fw-bold">Indications Cliniques</label>
                            <textarea class="form-control" name="observations" id="observationsLab" rows="4"
                                      placeholder="Contexte clinique, symptômes du patient, antécédents pertinents, raison de la demande..."></textarea>
                            <small class="text-muted d-block mt-2">Fournissez le contexte médical pour faciliter l'interprétation</small>
                        </div>
                    </div>

                    <!-- Boutons d'action -->
                    <div class="d-grid gap-2 mb-4">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="bi bi-check-circle me-2"></i> Demander l'Examen de Laboratoire
                        </button>
                        <a href="<?= BASE_URL ?>consultation/dossier/<?= $patient_id ?>" class="btn btn-outline-secondary">
                            Annuler
                        </a>
                    </div>
                </form>

                <!-- FORMULAIRE BILAN IMAGERIE -->
                <?php else: ?>
                <form action="<?= BASE_URL ?>bilan/save" method="POST" id="formBilanImagerie">
                    <input type="hidden" name="patient_id" value="<?= htmlspecialchars($patient_id) ?>">
                    <input type="hidden" name="type_bilan" value="imagerie">

                    <!-- Section Type d'Imagerie -->
                    <div class="form-section">
                        <h5><i class="bi bi-image me-2"></i>Type d'Imagerie</h5>

                        <div class="mb-3">
                            <label for="typeImagerie" class="form-label fw-bold">Modalité d'Imagerie</label>
                            <select class="form-select form-select-lg" name="type_imagerie" id="typeImagerie" required onchange="updatePlaceholder()">
                                <option value="">-- Sélectionner une modalité --</option>
                                <option value="radiographie">Radiographie (Rayon X)</option>
                                <option value="echographie">Échographie</option>
                                <option value="scanner">Scanner (TDM - Tomodensitométrie)</option>
                                <option value="irm">IRM (Imagerie par Résonance Magnétique)</option>
                                <option value="mammographie">Mammographie</option>
                                <option value="autre_imagerie">Autre - À Spécifier</option>
                            </select>
                            <small class="text-muted d-block mt-2">Choisissez le type d'examen d'imagerie</small>
                        </div>
                    </div>

                    <!-- Section Zone/Organe -->
                    <div class="form-section">
                        <h5><i class="bi bi-geo-alt me-2"></i>Zone/Organe à Examiner</h5>

                        <div class="mb-3">
                            <label for="partieCode" class="form-label fw-bold">Précisez la zone à examiner</label>
                            <input type="text" class="form-control form-control-lg" name="partie_code" id="partieCode" required
                                   placeholder="Ex: Radio de la main droite, Scanner pulmonaire, Échographie abdominale...">
                            <small class="text-muted d-block mt-2">Soyez précis sur la localisation anatomique</small>
                        </div>

                        <!-- Suggestions rapides -->
                        <div class="mb-3">
                            <label class="form-label small text-muted">Suggestions courantes :</label>
                            <div class="btn-group w-100" role="group">
                                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="setPartieCode('Thorax')">Thorax</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="setPartieCode('Abdomen')">Abdomen</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="setPartieCode('Bassin')">Bassin</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="setPartieCode('Crâne')">Crâne</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="setPartieCode('Colonne vertébrale')">Colonne</button>
                            </div>
                        </div>
                    </div>

                    <!-- Section Urgence et Observations -->
                    <div class="form-section">
                        <h5><i class="bi bi-info-circle me-2"></i>Détails de la Demande</h5>

                        <div class="mb-3">
                            <label for="urgenceImg" class="form-label fw-bold">Niveau d'Urgence</label>
                            <select class="form-select form-select-lg" name="urgence" id="urgenceImg" required>
                                <option value="NORMAL" selected>Routine (délai normal)</option>
                                <option value="URGENT">Urgent (24-48 heures)</option>
                                <option value="TRES_URGENT">Très Urgent (immédiat)</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="observationsImg" class="form-label fw-bold">Indications Cliniques</label>
                            <textarea class="form-control" name="observations" id="observationsImg" rows="4"
                                      placeholder="Contexte clinique, symptômes, traumatismes, antécédents pertinents, questions diagnostiques spécifiques..."></textarea>
                            <small class="text-muted d-block mt-2">Décrivez le contexte clinique et ce que vous recherchez</small>
                        </div>
                    </div>

                    <!-- Boutons d'action -->
                    <div class="d-grid gap-2 mb-4">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="bi bi-check-circle me-2"></i> Demander l'Imagerie
                        </button>
                        <a href="<?= BASE_URL ?>consultation/dossier/<?= $patient_id ?>" class="btn btn-outline-secondary">
                            Annuler
                        </a>
                    </div>
                </form>
                <?php endif; ?>
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

<script>
function setPartieCode(value) {
    document.getElementById('partieCode').value = value;
}

function updatePlaceholder() {
    const select = document.getElementById('typeImagerie');
    const input = document.getElementById('partieCode');

    const placeholders = {
        'radiographie': 'Ex: Radio du poignet droit, Radio des poumons, Radio du bassin...',
        'echographie': 'Ex: Échographie hépatique, Échographie cardiaque, Échographie obstétricale...',
        'scanner': 'Ex: Scanner thoracique, Scanner cérébral, Scanner abdominal...',
        'irm': 'Ex: IRM du genou, IRM cérébrale, IRM lombaire...',
        'mammographie': 'Ex: Mammographie bilatérale...',
        'autre_imagerie': 'Précisez le type d\'imagerie et la zone...'
    };

    input.placeholder = placeholders[select.value] || 'Précisez la zone à examiner...';
    input.value = '';
}
</script>

<?php include __DIR__ . '/../layouts/footer.php'; ?>
