<?php
require_once __DIR__ . '/../layouts/header.php';

// Sécurisation des données
$consultation = $consultation ?? [];
$patient = $patient ?? [];
$medicaments = $medicaments_prescrits ?? []; // Récupérés si le contrôleur les envoie
$prescription_id = $prescription_id ?? null; // ID pour le lien d'impression PDF

// Calcul de l'âge
$age = 'N/A';
if (!empty($patient['date_naissance'])) {
    $age = date_diff(date_create($patient['date_naissance']), date_create('today'))->y . ' ans';
}
?>

<div class="container-fluid">
    <div class="row">
        <?php require_once __DIR__ . '/../layouts/sidebar.php'; ?>

        <main class="col-md-10 ms-sm-auto px-md-4 py-4">

            <!-- EN-TÊTE ET ACTIONS -->
            <div class="d-flex justify-content-between align-items-center mb-4 no-print">
                <h2 class="h2 fw-bold text-dark"><i class="bi bi-file-medical-fill text-primary"></i> Synthèse de Consultation</h2>

                <div class="btn-group">
                    <!-- Bouton Imprimer Rapport (Cette page) -->
                    <button onclick="window.print()" class="btn btn-outline-secondary">
                        <i class="bi bi-printer"></i> Rapport
                    </button>

                    <!-- Bouton Imprimer Ordonnance (PDF A4) -->
                    <?php if ($prescription_id): ?>
                    <a href="<?= BASE_URL ?>consultation/ordonnance/print/<?= $prescription_id ?>" target="_blank" class="btn btn-warning">
                        <i class="bi bi-file-pdf"></i> Imprimer Ordonnance
                    </a>
                    <?php endif; ?>

                    <!-- Bouton Terminer -->
                   <a href="<?= BASE_URL ?>consultation/cloturer/<?= $consultation['id'] ?>" class="btn btn-success shadow-sm">
    <i class="bi bi-check-circle-fill me-2"></i> Terminer & Quitter
</a>
                </div>
            </div>

            <!-- BANDEAU PATIENT -->
            <div class="card mb-4 border-0 shadow-sm" style="border-left: 5px solid #0d6efd;">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-4">
                            <h4 class="mb-0 text-primary fw-bold">
                                <?= htmlspecialchars($patient['nom'] . ' ' . $patient['prenom']) ?>
                            </h4>
                            <small class="text-muted">Dossier: <?= htmlspecialchars($patient['dossier_numero']) ?></small>
                        </div>
                        <div class="col-md-4">
                            <div class="d-flex gap-4">
                                <div>
                                    <small class="text-muted d-block">Âge / Sexe</small>
                                    <strong><?= $age ?> / <?= $patient['sexe'] ?></strong>
                                </div>
                                <div>
                                    <small class="text-muted d-block">Date Consultation</small>
                                    <strong><?= date('d/m/Y H:i', strtotime($consultation['date_consultation'])) ?></strong>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 text-end">
                            <span class="badge bg-<?= $consultation['type'] === 'INTERNE' ? 'warning' : 'info' ?> fs-6">
                                <?= $consultation['type'] === 'INTERNE' ? 'Hospitalisation' : 'Ambulatoire' ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-4 print-layout">

                <!-- COLONNE GAUCHE : Clinique -->
                <div class="col-md-6">

                    <!-- 1. Constantes -->
                    <div class="card shadow-sm mb-3">
                        <div class="card-header bg-light fw-bold">
                            <i class="bi bi-speedometer2 me-2"></i> Paramètres Vitaux
                        </div>
                        <div class="card-body">
                            <div class="row text-center">
                                <div class="col-3 border-end">
                                    <small class="text-muted">Temp.</small>
                                    <div class="fw-bold"><?= $consultation['temperature'] ?? '-' ?>°C</div>
                                </div>
                                <div class="col-3 border-end">
                                    <small class="text-muted">Tension</small>
                                    <div class="fw-bold"><?= $consultation['tension_arterielle'] ?? '-' ?></div>
                                </div>
                                <div class="col-3 border-end">
                                    <small class="text-muted">Pouls</small>
                                    <div class="fw-bold"><?= $consultation['frequence_cardiaque'] ?? '-' ?></div>
                                </div>
                                <div class="col-3">
                                    <small class="text-muted">Poids</small>
                                    <div class="fw-bold"><?= $consultation['poids'] ?? '-' ?> kg</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 2. Anamnèse -->
                    <div class="card shadow-sm mb-3">
                        <div class="card-header bg-info text-white">
                            <h6 class="mb-0"><i class="bi bi-chat-left-text me-2"></i> Anamnèse</h6>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <strong class="text-primary">Motif :</strong>
                                <p class="mb-1"><?= nl2br(htmlspecialchars($consultation['motif_consultation'] ?? '')) ?></p>
                            </div>
                            <?php if (!empty($consultation['histoire_maladie'])): ?>
                            <div class="mb-3">
                                <strong class="text-primary">Histoire de la maladie :</strong>
                                <p class="mb-1"><?= nl2br(htmlspecialchars($consultation['histoire_maladie'])) ?></p>
                            </div>
                            <?php endif; ?>
                            <?php if (!empty($consultation['automedication'])): ?>
                            <div>
                                <strong class="text-primary">Automédication :</strong>
                                <p class="mb-0 text-muted fst-italic"><?= nl2br(htmlspecialchars($consultation['automedication'])) ?></p>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- 3. Examen Physique -->
                    <div class="card shadow-sm mb-3">
                        <div class="card-header bg-warning text-dark">
                            <h6 class="mb-0"><i class="bi bi-person-check me-2"></i> Examen Physique</h6>
                        </div>
                        <div class="card-body">
                            <p><?= nl2br(htmlspecialchars($consultation['examen_physique'] ?? 'Non renseigné')) ?></p>

                            <?php if (!empty($consultation['resume_syndromique'])): ?>
                            <div class="mt-3 p-2 bg-light rounded border-start border-warning border-4">
                                <strong>Résumé Syndromique :</strong><br>
                                <?= nl2br(htmlspecialchars($consultation['resume_syndromique'])) ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                </div>

                <!-- COLONNE DROITE : Décisionnelle -->
                <div class="col-md-6">

                    <!-- 4. Diagnostic -->
                    <div class="card shadow-sm mb-3 border-danger">
                        <div class="card-header bg-danger text-white">
                            <h6 class="mb-0"><i class="bi bi-bullseye me-2"></i> Diagnostic</h6>
                        </div>
                        <div class="card-body">
                            <h5 class="text-danger fw-bold text-uppercase mb-3">
                                <?= htmlspecialchars($consultation['diagnostic_principal'] ?? 'En cours d\'investigation') ?>
                            </h5>

                            <?php if (!empty($consultation['hypotheses_diagnostiques'])): ?>
                            <div class="mb-2">
                                <small class="fw-bold text-muted">Hypothèses :</small>
                                <p><?= nl2br(htmlspecialchars($consultation['hypotheses_diagnostiques'])) ?></p>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- 5. Traitement & Ordonnance -->
                    <div class="card shadow-sm mb-3">
                        <div class="card-header bg-success text-white d-flex justify-content-between">
                            <h6 class="mb-0"><i class="bi bi-capsule me-2"></i> Traitement</h6>
                            <?php if ($prescription_id): ?>
                                <small><i class="bi bi-check-circle"></i> Ordonnance générée</small>
                            <?php endif; ?>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($consultation['plan_traitement'])): ?>
                                <div class="mb-3">
                                    <strong>Plan global :</strong>
                                    <p><?= nl2br(htmlspecialchars($consultation['plan_traitement'])) ?></p>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($medicaments)): ?>
                                <div class="table-responsive bg-light rounded p-2">
                                    <table class="table table-sm table-borderless mb-0">
                                        <thead>
                                            <tr class="border-bottom">
                                                <th>Médicament</th>
                                                <th>Posologie</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach($medicaments as $med): ?>
                                            <tr>
                                                <td class="fw-bold"><?= htmlspecialchars($med['nom_medicament'] ?? $med['nom']) ?></td>
                                                <td><?= htmlspecialchars($med['posologie']) ?></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- 6. Aide à la Décision Hospitalisation -->
                    <?php
                    require_once __DIR__ . '/../../services/HospitalisationService.php';
                    $age_patient = isset($patient['date_naissance']) ?
                        (new DateTime())->diff(new DateTime($patient['date_naissance']))->y : null;
                    $analyse = HospitalisationService::analyserCriteresHospitalisation($consultation, $age_patient);
                    ?>
                    <div class="card shadow-sm mb-3 border-<?= $analyse['recommandation']['couleur'] ?>">
                        <div class="card-header bg-<?= $analyse['recommandation']['couleur'] ?> text-white">
                            <h6 class="mb-0"><i class="bi bi-hospital me-2"></i> Aide à la Décision - Hospitalisation</h6>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-<?= $analyse['recommandation']['couleur'] ?> mb-3">
                                <h6 class="fw-bold mb-2"><?= $analyse['recommandation']['message'] ?></h6>
                                <small><?= $analyse['recommandation']['justification'] ?> (Score: <?= $analyse['score_risque'] ?>)</small>
                            </div>

                            <?php if (!empty($analyse['criteres'])): ?>
                            <div class="mb-3">
                                <strong>Critères détectés :</strong>
                                <ul class="list-unstyled mt-2">
                                    <?php foreach($analyse['criteres'] as $critere): ?>
                                    <li class="mb-1">
                                        <span class="badge bg-<?= $critere['gravite'] === 'critique' ? 'danger' : ($critere['gravite'] === 'elevee' ? 'warning' : 'info') ?> me-2">
                                            <?= ucfirst($critere['type']) ?>
                                        </span>
                                        <?= $critere['critere'] ?>
                                    </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                            <?php endif; ?>

                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-danger btn-sm" onclick="prendreDecisionHospitalisation('hospitalisation_urgente')">
                                    <i class="bi bi-hospital"></i> Hospitaliser en urgence
                                </button>
                                <button type="button" class="btn btn-warning btn-sm" onclick="prendreDecisionHospitalisation('hospitalisation_programmee')">
                                    <i class="bi bi-calendar-plus"></i> Programmer hospitalisation
                                </button>
                                <button type="button" class="btn btn-info btn-sm" onclick="prendreDecisionHospitalisation('surveillance_renforcee')">
                                    <i class="bi bi-eye"></i> Surveillance renforcée
                                </button>
                                <button type="button" class="btn btn-success btn-sm" onclick="prendreDecisionHospitalisation('suivi_ambulatoire')">
                                    <i class="bi bi-house"></i> Suivi ambulatoire
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- 7. Paraclinique & Suivi -->
                    <div class="card shadow-sm mb-3">
                        <div class="card-header bg-dark text-white">
                            <h6 class="mb-0"><i class="bi bi-calendar-check me-2"></i> Suivi & Bilans</h6>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($consultation['examens_paracliniques'])): ?>
                            <div class="mb-3">
                                <strong class="text-info">Examens demandés :</strong>
                                <p><?= nl2br(htmlspecialchars($consultation['examens_paracliniques'])) ?></p>
                            </div>
                            <?php endif; ?>

                            <?php if (!empty($consultation['surveillance'])): ?>
                            <div class="mb-3">
                                <strong>Consignes de surveillance :</strong>
                                <p><?= nl2br(htmlspecialchars($consultation['surveillance'])) ?></p>
                            </div>
                            <?php endif; ?>

                            <?php if (!empty($consultation['date_suivi'])): ?>
                            <div class="alert alert-warning mb-0 py-2">
                                <i class="bi bi-clock-history me-2"></i>
                                Prochain RDV : <strong><?= date('d/m/Y', strtotime($consultation['date_suivi'])) ?></strong>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                </div>
            </div>

        </main>
    </div>
</div>

<!-- STYLE POUR L'IMPRESSION RAPIDE (RAPPORT COMPLET) -->
<style>
@media print {
    .no-print, .sidebar { display: none !important; }
    main { margin: 0 !important; padding: 0 !important; width: 100% !important; }
    .card { break-inside: avoid; border: 1px solid #ddd !important; box-shadow: none !important; }
    .card-header { background-color: #f0f0f0 !important; color: #000 !important; border-bottom: 1px solid #ccc; }
    body { background: white; font-size: 12px; }
    .col-md-6 { width: 50% !important; float: left; }
}
</style>

<!-- Modal pour confirmation décision hospitalisation -->
<div class="modal fade" id="modalDecisionHospitalisation" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirmer la décision</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p id="messageDecision"></p>
                <div class="mb-3">
                    <label class="form-label">Justification (optionnelle) :</label>
                    <textarea id="justificationDecision" class="form-control" rows="3" placeholder="Précisez les raisons de votre décision..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-primary" onclick="confirmerDecision()">Confirmer</button>
            </div>
        </div>
    </div>
</div>

<script>
let decisionEnCours = '';

function prendreDecisionHospitalisation(decision) {
    decisionEnCours = decision;

    const messages = {
        'hospitalisation_urgente': 'Vous allez décider d\'hospitaliser ce patient en URGENCE.',
        'hospitalisation_programmee': 'Vous allez programmer une hospitalisation pour ce patient.',
        'surveillance_renforcee': 'Vous allez mettre en place une surveillance renforcée.',
        'suivi_ambulatoire': 'Vous confirmez un suivi ambulatoire pour ce patient.'
    };

    document.getElementById('messageDecision').textContent = messages[decision];
    new bootstrap.Modal(document.getElementById('modalDecisionHospitalisation')).show();
}

function confirmerDecision() {
    const justification = document.getElementById('justificationDecision').value;

    fetch('/consultations/decision-hospitalisation', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            consultation_id: <?= $consultation['id'] ?>,
            decision: decisionEnCours,
            justification: justification
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Décision enregistrée avec succès');
            location.reload();
        } else {
            alert('Erreur lors de l\'enregistrement: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        alert('Erreur de communication');
    });

    bootstrap.Modal.getInstance(document.getElementById('modalDecisionHospitalisation')).hide();
}
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>