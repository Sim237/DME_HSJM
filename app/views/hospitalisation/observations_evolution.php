<?php
require_once __DIR__ . '/../layouts/header.php';

// Sécurisation de la fonction d'initiales
if (!function_exists('getInitials')) {
    function getInitials($nom, $prenom) {
        return strtoupper(substr($nom ?? '', 0, 1) . substr($prenom ?? '', 0, 1));
    }
}

// Variables provenant du contrôleur
$patient = $patient ?? [];
$observations_history = $observations ?? [];
$age = $age ?? 'N/A';
?>

<style>
    :root {
        --obs-blue: #4f46e5;
        --obs-bg: #f8fafc;
        --doctor-color: #0ea5e9;
        --nurse-color: #10b981;
    }

    body { background-color: var(--obs-bg); font-family: 'Inter', system-ui, -apple-system, sans-serif; }

    .main-container { max-width: 1000px; margin: 0 auto; padding: 20px; }

    /* En-tête Patient Style Hero */
    .patient-hero {
        background: white;
        border-radius: 20px;
        padding: 30px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        box-shadow: 0 10px 15px -3px rgba(0,0,0,0.05);
        margin-bottom: 30px;
        border-bottom: 5px solid var(--obs-blue);
    }

    .avatar-circle-obs {
        width: 70px; height: 70px;
        background: linear-gradient(135deg, var(--obs-blue), #818cf8);
        color: white; border-radius: 15px;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.8rem; font-weight: 800;
    }

    /* Carte de Saisie Moderne */
    .input-card {
        background: white;
        border-radius: 20px;
        padding: 25px;
        box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1);
        margin-bottom: 40px;
        border: 1px solid rgba(79, 70, 229, 0.1);
    }

    .note-textarea {
        border: 1px solid #e2e8f0;
        border-radius: 15px;
        padding: 20px;
        width: 100%;
        min-height: 120px;
        resize: none;
        transition: all 0.3s;
        background: #fcfdfe;
        font-size: 1.05rem;
    }

    .note-textarea:focus {
        outline: none;
        border-color: var(--obs-blue);
        background: white;
        box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.1);
    }

    /* Timeline Section */
    .timeline { position: relative; padding-left: 40px; }
    .timeline::before {
        content: ''; position: absolute; left: 10px; top: 5px; bottom: 0;
        width: 3px; background: #e2e8f0; border-radius: 3px;
    }

    .obs-item {
        position: relative;
        margin-bottom: 30px;
        background: white;
        padding: 25px;
        border-radius: 18px;
        border: 1px solid #edf2f7;
        box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02);
        transition: transform 0.2s;
    }

    .obs-item:hover { transform: translateY(-2px); box-shadow: 0 10px 15px -3px rgba(0,0,0,0.05); }

    .obs-item::before {
        content: ''; position: absolute; left: -38px; top: 28px;
        width: 18px; height: 18px; background: white;
        border: 4px solid var(--obs-blue); border-radius: 50%; z-index: 1;
    }

    /* Badges de rôles */
    .badge-role {
        padding: 5px 14px; border-radius: 50px;
        font-weight: 700; font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.5px;
    }
    .role-doctor { background: #e0f2fe; color: #0369a1; }
    .role-nurse { background: #dcfce7; color: #15803d; }
    .role-admin { background: #ede9fe; color: #6d28d9; }

    .btn-delete-obs {
        color: #ef4444; opacity: 0.6; transition: 0.2s;
    }
    .btn-delete-obs:hover { opacity: 1; color: #dc2626; transform: scale(1.1); }

    .visa-section {
        margin-top: 15px; padding-top: 12px;
        border-top: 1px solid #f1f5f9;
        font-size: 0.85rem; color: #64748b;
    }

    @media print {
        .no-print { display: none !important; }
        .main-container { max-width: 100%; padding: 0; }
        .obs-item { border: 1px solid #e2e8f0; margin-bottom: 15px; break-inside: avoid; }
    }
</style>

<div class="main-container pb-5">

    <!-- BARRE D'ACTIONS -->
    <div class="d-flex justify-content-between align-items-center mb-4 no-print">
        <a href="<?= BASE_URL ?>patients/dossier/<?= $patient['id'] ?>" class="btn btn-sm btn-outline-secondary rounded-pill px-3">
            <i class="bi bi-arrow-left me-1"></i> Retour au dossier
        </a>
        <div class="d-flex gap-2">
            <button class="btn btn-sm btn-primary rounded-pill px-3" onclick="window.print()">
                <i class="bi bi-printer me-1"></i> Imprimer
            </button>
        </div>
    </div>

    <!-- HERO PATIENT -->
    <div class="patient-hero">
        <div class="d-flex align-items-center gap-4">
            <div class="avatar-circle-obs"><?= getInitials($patient['nom'], $patient['prenom']) ?></div>
            <div>
                <h3 class="fw-bold text-dark mb-1"><?= htmlspecialchars($patient['nom'].' '.$patient['prenom']) ?></h3>
                <div class="text-muted small">
                    <span class="badge bg-light text-dark border me-2">Dossier: <?= $patient['dossier_numero'] ?></span>
                    <span class="me-2"><i class="bi bi-calendar3"></i> <?= $age ?> ans</span>
                    <span><i class="bi bi-gender-ambiguous"></i> <?= $patient['sexe'] ?></span>
                </div>
            </div>
        </div>
        <div class="text-end no-print">
            <img src="<?= BASE_URL ?>public/images/logo_ordre_malte.png" style="height: 45px;" alt="Logo HSJM">
            <div class="fw-bold small text-primary mt-1">OBSERVATION CLINIQUE</div>
        </div>
    </div>

    <!-- BLOC NOUVELLE OBSERVATION -->
    <div class="input-card no-print">
        <form id="formNewObs" action="<?= BASE_URL ?>hospitalisation/save-observation" method="POST">
            <input type="hidden" name="patient_id" value="<?= $patient['id'] ?>">

            <div class="d-flex justify-content-between align-items-center mb-3">
                <span class="fw-bold text-dark"><i class="bi bi-pencil-square text-primary me-2"></i>Nouvelle note d'évolution</span>
                <span class="badge bg-light text-muted border"><?= date('d/m/Y - H:i') ?></span>
            </div>

            <textarea name="contenu" class="note-textarea mb-4" placeholder="Décrivez l'état du patient, les soins prodigués ou les changements de traitement..." required></textarea>

            <div class="d-flex justify-content-between align-items-center">
                <div class="small text-muted">
                    Signataire : <span class="fw-bold text-dark">Dr. <?= $_SESSION['user_nom'] ?? 'Utilisateur' ?></span>
                </div>
                <button type="submit" class="btn btn-primary px-5 py-2 rounded-pill shadow">
                    <i class="bi bi-send-fill me-2"></i>Publier la note
                </button>
            </div>
        </form>
    </div>

    <!-- FIL DES OBSERVATIONS (TIMELINE) -->
    <div class="timeline" id="observationTimeline">

        <?php if(empty($observations_history)): ?>
            <div class="text-center py-5 text-muted bg-white rounded-4 border border-dashed">
                <i class="bi bi-chat-left-dots display-3 opacity-25"></i>
                <p class="mt-3 fs-5">Aucune observation pour le moment.</p>
            </div>
        <?php else: ?>
            <?php foreach($observations_history as $obs): ?>
            <div class="obs-item shadow-sm">
                <div class="obs-meta d-flex justify-content-between align-items-center mb-3">
                    <span class="fw-bold text-dark">
                        <i class="bi bi-clock-history text-primary me-1"></i>
                        <?= date('d/m/Y à H:i', strtotime($obs['date_obs'])) ?>
                    </span>

                    <div class="d-flex align-items-center gap-3">
                        <span class="badge-role <?= ($obs['role'] == 'MEDECIN' || $obs['role'] == 'ADMIN') ? 'role-doctor' : 'role-nurse' ?>">
                            <?= htmlspecialchars($obs['role']) ?>
                        </span>

                        <!-- BOUTON SUPPRIMER -->
                        <a href="<?= BASE_URL ?>hospitalisation/delete-observation/<?= $obs['id'] ?>/<?= $patient['id'] ?>"
                           class="btn btn-sm btn-link btn-delete-obs no-print p-0"
                           onclick="return confirm('Voulez-vous vraiment supprimer cette observation définitivement ?')"
                           title="Supprimer la note">
                            <i class="bi bi-trash3-fill fs-5"></i>
                        </a>
                    </div>
                </div>

                <div class="obs-content text-dark" style="white-space: pre-wrap; line-height: 1.6; font-size: 1.05rem;">
                    <?= htmlspecialchars($obs['contenu']) ?>
                </div>

                <div class="visa-section d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-person-check me-1"></i> Intervenant : <strong><?= htmlspecialchars($obs['user_nom']) ?></strong></span>
                    <span class="opacity-50 small">ID #<?= $obs['id'] ?></span>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>

    </div>

</div>

<script>
    // Feedback visuel lors de l'envoi
    document.getElementById('formNewObs')?.addEventListener('submit', function() {
        const btn = this.querySelector('button[type="submit"]');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Envoi en cours...';
    });
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>