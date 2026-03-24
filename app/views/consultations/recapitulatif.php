<?php
require_once __DIR__ . '/../layouts/header.php';

// Sécurisation des données
$consultation = $consultation ?? [];
$patient = $patient ?? [];
$medicaments = $medicaments_prescrits ?? [];
$prescription_id = $prescription_id ?? null;

$age = !empty($patient['date_naissance']) ? date_diff(date_create($patient['date_naissance']), date_create('today'))->y . ' ans' : 'N/A';
?>

<!-- IMPORT GOOGLE FONTS & ICONS -->
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

<style>
    :root {
        --med-primary: #1a4a8e;
        --med-success: #10b981;
        --med-danger: #ef4444;
        --med-warning: #f59e0b;
        --med-info: #0ea5e9;
        --bg-soft: #f4f7f9;
    }

    /* 1. CONFIGURATION PLEIN ÉCRAN (MASQUER SIDEBAR) */
    body { background-color: var(--bg-soft); font-family: 'Plus Jakarta Sans', sans-serif; }
    .sidebar { display: none !important; }
    main, .col-md-10, .ms-sm-auto { margin-left: 0 !important; width: 100% !important; flex: 0 0 100% !important; max-width: 100% !important; padding: 0 !important; }

    /* 2. LE CONTENEUR CENTRAL (MAITRISE DE LA LARGEUR) */
    .recap-wrapper {
        max-width: 1000px;
        margin: 0 auto;
        padding: 20px;
    }

    /* 3. STYLE DES CARTES SOFT */
    .section-card {
        background: white; border-radius: 20px; border: 1px solid #e2e8f0;
        box-shadow: 0 10px 25px rgba(0,0,0,0.02);
        margin-bottom: 25px; overflow: hidden;
    }

    .card-header-soft {
        padding: 15px 25px; font-weight: 800; text-transform: uppercase;
        font-size: 0.75rem; letter-spacing: 1px; display: flex; align-items: center; gap: 10px;
        background-color: #f8fafc; border-bottom: 1px solid #e2e8f0;
    }

    /* 4. VITALS DESIGN */
    .vital-pill {
        background: white; border-radius: 16px; padding: 15px; text-align: center;
        border: 1px solid #e2e8f0; box-shadow: 0 2px 4px rgba(0,0,0,0.02);
    }
    .vital-value { font-size: 1.2rem; font-weight: 800; color: #1e293b; display: block; }
    .vital-label { font-size: 0.65rem; font-weight: 700; color: #64748b; text-transform: uppercase; }

    /* 5. BOX DÉCISION (COULEURS DOUCES) */
    .decision-box {
        border-radius: 12px; padding: 20px; margin-bottom: 20px;
        border-left: 5px solid transparent; text-align: center;
    }
    .decision-hospitalisation_urgente { background: #fff5f5; border-left-color: var(--med-danger); color: #c53030; }
    .decision-hospitalisation_recommandee { background: #fffaf0; border-left-color: var(--med-warning); color: #9a3412; }
    .decision-suivi_ambulatoire { background: #f0fff4; border-left-color: var(--med-success); color: #166534; }

    /* 6. ACTIONS FIXES EN HAUT */
    .top-action-bar {
        background: white; border-bottom: 1px solid #e2e8f0; padding: 12px 0;
        position: sticky; top: 0; z-index: 1000; box-shadow: 0 2px 10px rgba(0,0,0,0.03);
    }

    /* Style spécifique pour le champ Motif dans la modale */
    .modal-textarea {
        background: #f8fafc;
        border: 2px solid #e2e8f0;
        border-radius: 12px;
        resize: none;
        padding: 12px;
        font-size: 0.9rem;
    }
    .modal-textarea:focus {
        border-color: var(--med-primary);
        background: #fff;
        box-shadow: none;
    }

    @media print {
        .no-print { display: none !important; }
        .recap-wrapper { max-width: 100%; padding: 0; }
        .section-card { box-shadow: none !important; border: 1px solid #eee !important; page-break-inside: avoid; }
    }
</style>

<!-- HEADER D'ACTION -->
<div class="top-action-bar no-print">
    <div class="container d-flex justify-content-between align-items-center" style="max-width: 1000px;">
        <div class="d-flex align-items-center gap-3">
            <img src="<?= BASE_URL ?>public/images/logo_ordre_malte.png" style="height: 35px;">
            <h6 class="mb-0 fw-bold">Synthèse Consultation</h6>
        </div>
        <div class="d-flex gap-2">
            <button onclick="window.print()" class="btn btn-outline-dark btn-sm rounded-pill px-3">Rapport</button>
            <a href="<?= BASE_URL ?>consultation/cloturer/<?= $consultation['id'] ?>" class="btn btn-success btn-sm rounded-pill px-4 fw-bold shadow">QUITTER</a>
        </div>
    </div>
</div>

<div class="recap-wrapper">

    <!-- BANDEAU PATIENT -->
    <div class="section-card p-4" style="border-left: 6px solid var(--med-primary);">
        <div class="row align-items-center">
            <div class="col-sm-7">
                <small class="text-muted fw-bold small">Dossier : <?= htmlspecialchars($patient['dossier_numero']) ?></small>
                <h3 class="fw-bold text-dark mb-0"><?= strtoupper($patient['nom']) ?> <?= $patient['prenom'] ?></h3>
                <span class="badge bg-<?= $consultation['type'] === 'INTERNE' ? 'warning text-dark' : 'info text-white' ?> mt-2 rounded-pill">
                    <?= $consultation['type'] === 'INTERNE' ? 'Hospitalisé' : 'Ambulatoire' ?>
                </span>
            </div>
            <div class="col-sm-5 text-md-end mt-3 mt-md-0">
                <div class="text-muted small">Consultation du</div>
                <div class="fw-bold"><?= date('d/m/Y à H:i', strtotime($consultation['date_consultation'])) ?></div>
                <div class="fw-bold text-primary"><?= $age ?> / <?= $patient['sexe'] ?></div>
            </div>
        </div>
    </div>

    <!-- PARAMETRES VITAUX -->
    <div class="row g-3 mb-4">
        <div class="col-3"><div class="vital-pill"><span class="vital-label">Temp.</span><span class="vital-value text-danger"><?= $consultation['temperature'] ?? '--' ?>°</span></div></div>
        <div class="col-3"><div class="vital-pill"><span class="vital-label">Tension</span><span class="vital-value text-primary"><?= $consultation['tension_arterielle'] ?? '--/--' ?></span></div></div>
        <div class="col-3"><div class="vital-pill"><span class="vital-label">Pouls</span><span class="vital-value text-success"><?= $consultation['frequence_cardiaque'] ?? '--' ?></span></div></div>
        <div class="col-3"><div class="vital-pill"><span class="vital-label">Poids</span><span class="vital-value text-dark"><?= $consultation['poids'] ?? '--' ?> kg</span></div></div>
    </div>

    <div class="row">
        <!-- COLONNE GAUCHE -->
        <div class="col-lg-6">
            <div class="section-card">
                <div class="card-header-soft text-info"><i class="bi bi-chat-left-text"></i> Anamnèse</div>
                <div class="card-body">
                    <p><strong>Motif :</strong> <?= htmlspecialchars($consultation['motif_consultation']) ?></p>
                    <p class="mb-0"><strong>Histoire :</strong> <?= nl2br(htmlspecialchars($consultation['histoire_maladie'])) ?></p>
                </div>
            </div>

            <div class="section-card">
                <div class="card-header-soft text-warning"><i class="bi bi-person-check"></i> Examen Physique</div>
                <div class="card-body">
                    <p class="mb-0"><?= nl2br(htmlspecialchars($consultation['examen_physique'])) ?></p>
                </div>
            </div>
        </div>

        <!-- COLONNE DROITE -->
        <div class="col-lg-6">
            <div class="section-card" style="border-top: 4px solid var(--med-danger);">
                <div class="card-header-soft text-danger"><i class="bi bi-bullseye"></i> Diagnostic</div>
                <div class="card-body">
                    <h5 class="fw-bold text-uppercase text-danger"><?= htmlspecialchars($consultation['diagnostic_principal']) ?></h5>
                    <p class="small text-muted mb-0"><?= htmlspecialchars($consultation['hypotheses_diagnostiques']) ?></p>
                </div>
            </div>

            <div class="section-card">
                <div class="card-header-soft text-success"><i class="bi bi-capsule"></i> Traitement</div>
                <div class="card-body">
                    <p class="fw-bold small"><?= nl2br(htmlspecialchars($consultation['plan_traitement'])) ?></p>
                </div>
            </div>

            <!-- ORIENTATION PATIENT -->
            <?php
            require_once __DIR__ . '/../../services/HospitalisationService.php';
            $age_val = !empty($patient['date_naissance']) ? (new DateTime())->diff(new DateTime($patient['date_naissance']))->y : null;
            $analyse = HospitalisationService::analyserCriteresHospitalisation($consultation, $age_val);
            ?>
            <div class="section-card">
                <div class="card-header-soft text-dark"><i class="bi bi-hospital"></i> Orientation Patient</div>
                <div class="card-body">
                    <div class="decision-box decision-<?= $analyse['recommandation']['niveau'] ?>">
                        <h6 class="fw-bold mb-1"><?= $analyse['recommandation']['message'] ?></h6>
                        <small>Score de risque : <?= $analyse['score_risque'] ?>/10</small>
                    </div>

                    <div class="d-flex justify-content-center flex-wrap gap-2 no-print">
                        <button class="btn btn-danger btn-sm rounded-pill px-3" onclick="prendreDecisionHospitalisation('hospitalisation_urgente')">Hosp. Urgence</button>
                        <button class="btn btn-warning btn-sm rounded-pill px-3" onclick="prendreDecisionHospitalisation('hospitalisation_programmee')">Programmer</button>
                        <button class="btn btn-success btn-sm rounded-pill px-3" onclick="prendreDecisionHospitalisation('suivi_ambulatoire')">Ambulatoire</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Décision avec zone de Motif -->
<div class="modal fade" id="modalDecisionHospitalisation" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 20px;">
            <div class="modal-header border-bottom-0 p-4 pb-0">
                <h5 class="fw-bold text-dark mb-0">Confirmer l'orientation ?</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4 text-center">
                <div id="messageDecision" class="alert alert-light border-0 small mb-4 py-2"></div>

                <div class="text-start">
                    <label class="form-label small fw-bold text-muted text-uppercase">Motif / Justification de la décision</label>
                    <textarea id="justificationDecision" class="form-control modal-textarea" rows="3" placeholder="Saisir la raison médicale..."></textarea>
                </div>

                <div class="d-flex justify-content-center gap-3 mt-4">
                    <button type="button" class="btn btn-light rounded-pill px-4 fw-bold" data-bs-dismiss="modal">Annuler</button>
                    <button type="button" class="btn btn-primary rounded-pill px-5 shadow fw-bold" onclick="confirmerDecision()">Confirmer</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let decisionEnCours = '';
function prendreDecisionHospitalisation(decision) {
    decisionEnCours = decision;
    const labels = {
        'hospitalisation_urgente': 'Hospitalisation d\'urgence',
        'hospitalisation_programmee': 'Programmer l\'hospitalisation',
        'suivi_ambulatoire': 'Suivi ambulatoire'
    };
    document.getElementById('messageDecision').innerHTML = `Action sélectionnée : <strong>${labels[decision]}</strong>`;
    new bootstrap.Modal(document.getElementById('modalDecisionHospitalisation')).show();
}

function confirmerDecision() {
    const motif = document.getElementById('justificationDecision').value;

    fetch('<?= BASE_URL ?>consultation/decision-hospitalisation', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            consultation_id: <?= $consultation['id'] ?>,
            decision: decisionEnCours,
            justification: motif
        })
    }).then(r => r.json()).then(data => {
        if(data.success) {
            location.reload();
        } else {
            alert('Erreur lors de l\'enregistrement.');
        }
    });
}
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>