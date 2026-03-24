<?php
require_once __DIR__ . '/../layouts/header.php';

// Sécurisation des variables transmises par le contrôleur
$patient = $patient ?? null;
$demandes_entrantes = $demandes_entrantes ?? [];
$age = $age ?? 'N/A';
?>

<style>
    :root {
        --soft-bg: #f0f4f8;
        --card-white: #ffffff;
        --text-dark: #1e293b;
        --medical-blue: #0ea5e9;
        /* Couleurs Vitales Douces */
        --vitals-green: #10b981;
        --vitals-blue: #3b82f6;
        --vitals-amber: #f59e0b;
        --vitals-rose: #f43f5e;
    }

    body { background-color: var(--soft-bg); color: var(--text-dark); font-family: 'Inter', system-ui, sans-serif; overflow-x: hidden; }

    /* Layout Plein Écran (Pas de sidebar pour l'Anesthésie) */
    .cockpit-wrapper { width: 100%; min-height: 100vh; padding: 20px 40px; }

    /* Top Navbar Cockpit */
    .cockpit-nav {
        background: white; padding: 15px 30px; border-radius: 20px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.05); margin-bottom: 25px;
        display: flex; justify-content: space-between; align-items: center;
    }

    /* Carte de synthèse patient actif */
    .patient-hero {
        background: white; padding: 20px; border-radius: 20px;
        border-left: 8px solid var(--medical-blue);
        box-shadow: 0 10px 15px -3px rgba(0,0,0,0.05); margin-bottom: 25px;
    }
    .stat-label { font-size: 0.75rem; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; }
    .stat-value { font-size: 1.1rem; font-weight: 800; color: var(--text-dark); }
    .allergy-flash { color: var(--vitals-rose); font-weight: 900; animation: flash 2s infinite; }
    @keyframes flash { 50% { opacity: 0.3; } }

    /* Widgets Moniteur (Constantes) */
    .monitor-card {
        background: white; border: none; border-radius: 24px; padding: 25px;
        box-shadow: 0 10px 20px rgba(0,0,0,0.04); transition: transform 0.2s;
        text-align: center;
    }
    .monitor-card:hover { transform: translateY(-5px); }
    .monitor-card.fc { border-bottom: 6px solid var(--vitals-green); }
    .monitor-card.spo2 { border-bottom: 6px solid var(--vitals-blue); }
    .monitor-card.pa { border-bottom: 6px solid var(--vitals-amber); }
    .monitor-card.etco2 { border-bottom: 6px solid var(--vitals-rose); }
    .val-big { font-size: 4rem; font-weight: 900; line-height: 1; display: block; margin: 10px 0; }

    /* Grille Chronologique */
    .grid-container {
        background: white; border-radius: 24px; overflow: hidden;
        box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); border: 1px solid #e2e8f0;
    }
    .chrono-table { width: 100%; border-collapse: collapse; }
    .chrono-table th { background: #f8fafc; padding: 15px; color: #64748b; font-size: 0.8rem; text-transform: uppercase; border-bottom: 1px solid #e2e8f0; }
    .chrono-table td { padding: 15px; text-align: center; border-bottom: 1px solid #f1f5f9; }
    .row-label { text-align: left !important; font-weight: 700; background: #fbfcfd; width: 200px; padding-left: 25px !important; border-right: 1px solid #e2e8f0; }

    /* File d'attente (Demandes de Marie Curie etc.) */
    .waiting-list {
        background: white; border-radius: 24px; padding: 25px;
        box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1); border: 1px solid #fee2e2;
    }
    .table-waiting thead th { background: #fff1f2; color: #991b1b; padding: 15px; border: none; border-radius: 10px; }

    /* Boutons Actions */
    .btn-action { padding: 18px; border-radius: 15px; font-weight: 800; text-transform: uppercase; border: none; transition: 0.3s; margin-bottom: 10px; width: 100%; }
    .btn-induction { background: #3b82f6; color: white; box-shadow: 0 4px 10px rgba(59, 130, 246, 0.3); }
    .btn-incision { background: #f43f5e; color: white; box-shadow: 0 4px 10px rgba(244, 63, 94, 0.3); }
    .btn-meds { background: #f59e0b; color: white; box-shadow: 0 4px 10px rgba(245, 158, 11, 0.3); }
</style>

<div class="cockpit-wrapper">
    <!-- NAVBAR SUPÉRIEURE -->
    <nav class="cockpit-nav">
        <div class="d-flex align-items-center gap-3">
            <img src="<?= BASE_URL ?>public/images/logo_ordre_malte.png" style="height: 45px;">
            <div>
                <h4 class="fw-bold mb-0">ANESTHESIA COCKPIT</h4>
                <small class="text-muted fw-bold">Hôpital Saint-Jean de Malte • Njombé</small>
            </div>
        </div>

        <div class="d-flex align-items-center gap-4">
            <div class="text-end">
                <div class="fw-bold fs-4 text-primary" id="liveClock">00:00:00</div>
                <small class="text-muted fw-bold text-uppercase"><?= date('d F Y') ?></small>
            </div>
            <a href="<?= BASE_URL ?>logout" class="btn btn-outline-danger rounded-circle p-2" title="Déconnexion">
                <i class="bi bi-power fs-5"></i>
            </a>
        </div>
    </nav>

    <?php if ($patient): ?>
        <!-- SECTION 1 : PATIENT ACTUELLEMENT EN SALLE -->
        <div class="patient-hero">
            <div class="row align-items-center">
                <div class="col-md-3">
                    <span class="stat-label">Patient en cours d'opération</span>
                    <div class="stat-value text-primary fs-4"><?= htmlspecialchars($patient['nom'].' '.$patient['prenom']) ?></div>
                    <small class="text-muted"><?= $age ?> ans • <?= $patient['sexe'] ?></small>
                </div>
                <div class="col-md-2">
                    <span class="stat-label">Salle</span>
                    <div class="stat-value"><i class="bi bi-door-closed-fill"></i> <?= $patient['nom_salle'] ?></div>
                </div>
                <div class="col-md-2 text-center">
                    <span class="stat-label">ASA Score</span>
                    <div><span class="badge bg-warning text-dark px-3 py-2 mt-1">CLASSE II</span></div>
                </div>
                <div class="col-md-3">
                    <span class="stat-label">Alerte Allergies</span>
                    <div class="stat-value allergy-flash">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                        <?= !empty($patient['allergies']) ? htmlspecialchars($patient['allergies']) : 'AUCUNE SIGNALÉE' ?>
                    </div>
                </div>
                <div class="col-md-2 text-end">
                    <span class="badge bg-success rounded-pill px-4 py-2 shadow-sm">LIVE MONITORING</span>
                </div>
            </div>
        </div>

        <!-- SECTION 2 : MONITORAGE ET GRILLE -->
        <div class="row g-4">
            <div class="col-lg-9">
                <!-- Vitals Row -->
                <div class="row g-3 mb-4">
                    <div class="col-md-3">
                        <div class="monitor-card fc">
                            <span class="stat-label text-success">Fréq. Cardiaque</span>
                            <span class="val-big text-success">74</span>
                            <small class="text-muted">BPM</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="monitor-card spo2">
                            <span class="stat-label text-primary">Saturation O2</span>
                            <span class="val-big text-primary">98</span>
                            <small class="text-muted">% SpO2</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="monitor-card pa">
                            <span class="stat-label text-warning">Pression Art.</span>
                            <span class="val-big text-warning" style="font-size: 2.8rem;">118/76</span>
                            <small class="text-muted">mmHg (PAM 90)</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="monitor-card etco2">
                            <span class="stat-label text-danger">EtCO2</span>
                            <span class="val-big text-danger">35</span>
                            <small class="text-muted">mmHg</small>
                        </div>
                    </div>
                </div>

                <!-- Grille d'Anesthésie -->
                <div class="grid-container shadow-sm">
                    <div class="p-3 bg-light fw-bold small text-muted border-bottom">FEUILLE D'ANESTHÉSIE INFORMATISÉE</div>
                    <div class="table-responsive">
                        <table class="chrono-table">
                            <thead>
                                <tr>
                                    <th class="row-label">MÉDICAMENTS / VOIES</th>
                                    <th>08:00</th><th>08:15</th><th>08:30</th><th>08:45</th><th>09:00</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr><td class="row-label">Propofol (mg)</td><td class="fw-bold text-primary">150</td><td></td><td class="fw-bold text-primary">50</td><td></td><td></td></tr>
                                <tr><td class="row-label">Sufentanyl (µg)</td><td class="fw-bold text-primary">20</td><td></td><td></td><td class="fw-bold text-primary">10</td><td></td></tr>
                                <tr><td class="row-label fw-bold">ÉVÉNEMENTS</td><td><span class="badge bg-success rounded-pill px-3">IND</span></td><td><span class="badge bg-danger rounded-pill px-3">INC</span></td><td></td><td></td><td></td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- SECTION 3 : ACTIONS & FLUIDES (DROITE) -->
            <div class="col-lg-3">
                <div class="card border-0 shadow-sm rounded-4 p-4 mb-3 bg-primary text-white">
                    <h6 class="fw-bold small mb-3 opacity-75">BILAN DES FLUIDES (ml)</h6>
                    <div class="d-flex justify-content-between mb-2"><span>Apports</span><strong>1200</strong></div>
                    <div class="d-flex justify-content-between mb-3"><span>Pertes</span><strong>350</strong></div>
                    <div class="border-top pt-2 d-flex justify-content-between fs-5">
                        <span class="fw-bold">BALANCE</span><span class="fw-bold text-warning">+850</span>
                    </div>
                </div>

                <div class="d-grid gap-2">
                    <button class="btn-action btn-induction"><i class="bi bi-play-circle-fill fs-4 me-2"></i> Induction</button>
                    <button class="btn-action btn-incision"><i class="bi bi-activity fs-4 me-2"></i> Incision</button>
                    <button class="btn-action btn-meds"><i class="bi bi-plus-circle-fill fs-4 me-2"></i> Injecter / Dose</button>
                </div>

                <div class="alert bg-white border-danger border-2 mt-4 text-danger animate__animated animate__pulse animate__infinite">
                    <i class="bi bi-bell-fill me-2"></i> <strong>RAPPEL :</strong> Antibio à ré-injecter dans 10 min.
                </div>
            </div>
        </div>
    <?php else: ?>
        <!-- MESSAGE SI AUCUN PATIENT EN SALLE -->
        <div class="card border-0 shadow-sm rounded-4 text-center py-5 mb-5 bg-white">
            <div class="card-body">
                <i class="bi bi-person-badge-fill display-1 text-light"></i>
                <h3 class="text-muted mt-3">Aucun patient actuellement en salle d'opération</h3>
                <p class="text-muted">Sélectionnez une demande dans la liste d'attente ci-dessous pour démarrer le monitorage.</p>
            </div>
        </div>
    <?php endif; ?>

    <!-- ============================================================
         SECTION : LISTE D'ATTENTE (MARIE CURIE APPARAÎT ICI)
         ============================================================ -->
    <div class="waiting-list shadow-lg mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h5 class="fw-bold mb-0 text-dark"><i class="bi bi-hourglass-split me-2 text-warning"></i>Demandes d'anesthésie en attente</h5>
            <span class="badge bg-danger rounded-pill px-3 py-2"><?= count($demandes_entrantes) ?> PATIENT(S)</span>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr class="small text-uppercase">
                        <th class="ps-4">Identité Patient</th>
                        <th>Chirurgien Prescripteur</th>
                        <th>Date de la Demande</th>
                        <th class="text-end pe-4">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($demandes_entrantes)): ?>
                        <tr><td colspan="4" class="text-center py-5 text-muted italic">Aucun dossier en attente de préparation.</td></tr>
                    <?php else: foreach ($demandes_entrantes as $req): ?>
                        <tr class="animate__animated animate__fadeInUp">
                            <td class="ps-4">
                                <div class="fw-bold text-primary"><?= htmlspecialchars($req['nom'].' '.$req['prenom']) ?></div>
                                <small class="text-muted fw-bold">ID: <?= htmlspecialchars($req['dossier_numero']) ?></small>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border px-3">
                                    <i class="bi bi-person-fill text-primary"></i> Dr. <?= htmlspecialchars($req['chirurgien_nom'] ?? 'Admin') ?>
                                </span>
                            </td>
                            <td class="text-muted small"><?= date('d/m/Y à H:i', strtotime($req['date_demande'])) ?></td>
                            <td class="text-end pe-4">
                                <a href="<?= BASE_URL ?>formulaire/creer/consentement/<?= $req['patient_id'] ?>" class="btn btn-warning rounded-pill px-4 fw-bold shadow-sm">
                                    <i class="bi bi-pencil-square me-2"></i> Préparer le dossier
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    // Horloge dynamique
    setInterval(() => {
        document.getElementById('liveClock').innerText = new Date().toLocaleTimeString();
    }, 1000);

    // Simulation de réception de données (Animation des chiffres pour le rendu "Live")
    setInterval(() => {
        const fc = document.querySelector('.fc .val-big');
        if(fc) {
            const current = parseInt(fc.innerText);
            fc.innerText = current + (Math.random() > 0.5 ? 1 : -1);
        }
    }, 3000);
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>