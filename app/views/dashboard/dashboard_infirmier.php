<?php
require_once __DIR__ . '/../layouts/header.php';

// Sécurisation des variables transmises par le contrôleur
$a_hospitaliser = $a_hospitaliser ?? [];
$lits_service = $lits_service ?? [];
$plans_du_jour = $plans_du_jour ?? [];
$lits_global = $lits_global ?? [];
?>

<!-- IMPORT DES ICONES BOOTSTRAP ET DES ANIMATIONS -->
<!--<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">-->
<!--<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>-->

<style>
    /* 1. CONFIGURATION LAYOUT PLEIN ÉCRAN */
    .sidebar { display: none !important; }
    main { margin-left: 0 !important; width: 100% !important; background: #f4f7f9; min-height: 100vh; font-family: 'Inter', system-ui, -apple-system, sans-serif; }

    /* 2. BARRE DE NAVIGATION SUPÉRIEURE DYNAMIQUE */
    .nav-nurse {
        background: #1a4a8e;
        padding: 12px 30px;
        color: white;
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        position: sticky;
        top: 0;
        z-index: 1000;
    }

    #clock { font-family: monospace; font-size: 1.6rem; font-weight: bold; color: #00ff41; text-shadow: 0 0 10px rgba(0, 255, 100, 0.3); }

    /* 3. ALERTES HOSPITALISATION (CLIGNOTANT) */
    .alert-hosp-box {
        background: #fff9db;
        border: 2px solid #fab005;
        border-radius: 15px;
        padding: 20px;
        position: relative;
        animation: pulse-yellow 1.5s infinite;
    }
    @keyframes pulse-yellow {
        0% { box-shadow: 0 0 0px #fab005; }
        50% { box-shadow: 0 0 15px #fab005; }
        100% { box-shadow: 0 0 0px #fab005; }
    }

    /* 4. DESIGN DES CARTES DE LITS */
    .bed-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 20px; }
    .bed-card {
        background: white;
        border: 1px solid #e2e8f0;
        border-radius: 20px;
        padding: 20px;
        text-align: center;
        transition: all 0.3s ease;
        position: relative;
        box-shadow: 0 2px 4px rgba(0,0,0,0.02);
    }
    .bed-card:hover { transform: translateY(-5px); box-shadow: 0 15px 30px rgba(0,0,0,0.08); }
    .bed-occupied { border-top: 6px solid #dc3545; background: #fffcfc; }
    .bed-free { border-top: 6px solid #198754; background: #fafffa; }

    /* 5. CHECKLIST DES SOINS */
    .planning-card { background: white; border-radius: 24px; padding: 25px; box-shadow: 0 4px 20px rgba(0,0,0,0.03); }
    .task-item {
        background: #f8fafc;
        margin-bottom: 12px;
        padding: 15px;
        border-radius: 14px;
        border: 1px solid #e2e8f0;
        display: flex;
        justify-content: space-between;
        align-items: center;
        transition: 0.3s;
    }
    .task-item.completed { background: #f0fff4; border-left: 5px solid #198754; opacity: 0.8; }

    .section-title { font-weight: 800; text-transform: uppercase; font-size: 0.85rem; letter-spacing: 1px; color: #475569; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }

    .btn-execute { background: #1a4a8e; color: white; font-weight: 700; border-radius: 50px; padding: 7px 20px; border: none; font-size: 0.8rem; transition: 0.2s; }
    .btn-execute:hover { background: #0d2d5e; transform: scale(1.05); color: white; }
</style>

<!-- HEADER DU COCKPIT -->
<nav class="nav-nurse d-flex justify-content-between align-items-center">
    <div class="d-flex align-items-center gap-3">
        <div class="bg-white p-2 rounded-circle shadow-sm">
            <img src="<?= BASE_URL ?>public/images/logo_ordre_malte.png" style="height: 40px;" alt="HSJM">
        </div>
        <div>
            <h4 class="mb-0 fw-bold">UNITE DE SOINS : <?= $_SESSION['nom_service'] ?></h4>
            <small class="text-white-50">Infirmier(e) de garde : <strong><?= $_SESSION['user_nom'] ?></strong></small>
        </div>
    </div>

    <div id="clock">00:00:00</div>

    <div class="d-flex gap-3 align-items-center">
        <button class="btn btn-sm btn-outline-light rounded-pill px-3 fw-bold" onclick="location.reload()">
            <i class="bi bi-arrow-clockwise"></i> ACTUALISER
        </button>
        <a href="<?= BASE_URL ?>logout" class="btn btn-danger rounded-pill px-4 btn-sm fw-bold shadow">
            <i class="bi bi-power"></i> QUITTER
        </a>
    </div>
</nav>

<div class="container-fluid p-4">
    <div class="row g-4">

        <!-- COLONNE GAUCHE (4/12) : ADMISSIONS & CHECKLIST -->
        <div class="col-lg-4">

            <!-- 1. ALERTES D'ADMISSION (DEMANDÉES PAR LES MÉDECINS) -->
            <div class="mb-5">
                <h6 class="section-title text-danger">
                    <i class="bi bi-bell-fill animate__animated animate__swing animate__infinite"></i>
                    Demandes d'Admission Urgent
                </h6>
                <?php if (empty($a_hospitaliser)): ?>
                    <div class="p-4 text-center bg-white rounded-4 border-dashed border-2 text-muted">
                        <i class="bi bi-check2-circle fs-1 opacity-25"></i>
                        <p class="small mt-2 mb-0">Aucun patient en attente.</p>
                    </div>
                <?php else: foreach ($a_hospitaliser as $h): ?>
                    <div class="alert-hosp-box mb-3 shadow-sm">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h6 class="fw-bold text-dark mb-1"><?= strtoupper($h['nom']) ?> <?= $h['prenom'] ?></h6>
                                <small class="text-muted d-block mb-2">Dossier: <?= $h['dossier_numero'] ?></small>
                                <span class="badge bg-danger rounded-pill"><i class="bi bi-person-fill"></i> Dr. <?= $h['medecin_nom'] ?></span>
                            </div>
                            <button class="btn btn-warning fw-bold rounded-pill px-3 shadow-sm mt-1" onclick="startAdmission(<?= $h['consult_id'] ?>, '<?= addslashes($h['nom'].' '.$h['prenom']) ?>')">
                                INSTALLER
                            </button>
                        </div>
                    </div>
                <?php endforeach; endif; ?>
            </div>

            <!-- 2. CHECKLIST DES SOINS (DYNAMIQUE) -->
            <div class="planning-card border shadow-sm">
                <h6 class="section-title text-primary"><i class="bi bi-calendar2-check"></i> Checklist des Soins du Jour</h6>
                <div id="dynamic-soins-list">
                    <?php if (empty($plans_du_jour)): ?>
                        <p class="text-center py-5 text-muted small">Aucun soin planifié pour aujourd'hui.</p>
                    <?php else: foreach ($plans_du_jour as $p):
                        $isDone = ($p['total_soins'] > 0 && $p['total_soins'] == $p['soins_faits']);
                    ?>
                        <div class="task-item shadow-sm <?= $isDone ? 'completed' : '' ?>">
                            <div>
                                <small class="text-muted d-block" style="font-size: 0.7rem;">
                                    <?= $isDone ? '<span class="text-success fw-bold">✓ EFFECTUÉ</span>' : 'PLAN DE SOINS' ?>
                                </small>
                                <strong class="text-dark"><?= strtoupper($p['nom']) ?> <?= $p['prenom'] ?></strong>
                                <div class="text-muted small" style="font-size: 0.65rem;">
                                    Progression : <?= $p['soins_faits'] ?> / <?= $p['total_soins'] ?> effectués
                                </div>
                            </div>

                            <div class="d-flex gap-2">
                                <?php if ($isDone): ?>
                                    <a href="<?= BASE_URL ?>hospitalisation/executer-soins/<?= $p['plan_id'] ?>" class="btn btn-sm btn-outline-success rounded-circle" title="Voir les détails">
                                        <i class="bi bi-eye-fill"></i>
                                    </a>
                                <?php else: ?>
                                    <a href="<?= BASE_URL ?>hospitalisation/executer-soins/<?= $p['plan_id'] ?>" class="btn btn-execute shadow-sm">
                                        EXÉCUTER
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; endif; ?>
                </div>
            </div>
        </div>

        <!-- COLONNE DROITE (8/12) : ÉTAT DES LITS DU SERVICE -->
        <div class="col-lg-8">
            <div class="d-flex justify-content-between align-items-center mb-3 px-2">
                <h6 class="section-title text-dark mb-0"><i class="bi bi-grid-3x3-gap"></i> État des lits : <?= $_SESSION['nom_service'] ?></h6>
                <div class="d-flex gap-2">
                    <span class="badge bg-success rounded-pill px-3">LIBRES: <?= count(array_filter($lits_service, fn($l) => !$l['patient_id'])) ?></span>
                    <span class="badge bg-danger rounded-pill px-3">OCCUPÉS: <?= count(array_filter($lits_service, fn($l) => $l['patient_id'])) ?></span>
                </div>
            </div>

            <div class="bed-grid">
                <?php foreach ($lits_service as $l):
                    $isOccupied = !empty($l['patient_id']);
                    $nomPatient = ($isOccupied && !empty($l['nom'])) ? strtoupper($l['nom']).' '.$l['prenom'] : 'Inconnu';
                ?>
                <div class="bed-card <?= $isOccupied ? 'bed-occupied shadow-sm' : 'bed-free' ?>">
                    <i class="bi bi-person-bounding-box fs-1 opacity-25"></i>
                    <div class="fw-bold text-dark fs-5 mt-2">LIT <?= htmlspecialchars($l['nom_lit']) ?></div>
                    <small class="text-muted d-block mb-2">Chambre <?= htmlspecialchars($l['nom_chambre']) ?></small>

                    <div class="pt-3 border-top">
                        <?php if ($isOccupied): ?>
                            <!-- Zone Nom + Icône Dossier -->
                            <div class="d-flex justify-content-center align-items-center mb-3">
                                <strong class="text-danger me-2" style="font-size: 0.9rem;"><?= $nomPatient ?></strong>
                                <a href="<?= BASE_URL ?>patients/dossier/<?= $l['patient_id'] ?>"
                                   class="btn btn-sm btn-outline-primary border-0 p-1 rounded-circle"
                                   title="Ouvrir le dossier médical">
                                    <i class="bi bi-file-earmark-person-fill fs-5"></i>
                                </a>
                            </div>

                            <a href="<?= BASE_URL ?>hospitalisation/planifier-soins/<?= $l['patient_id'] ?>"
                               class="btn btn-primary btn-sm rounded-pill w-100 fw-bold shadow-sm mb-2">
                                <i class="bi bi-calendar-plus"></i> Planifier Soins
                            </a>

                            <a href="<?= BASE_URL ?>hospitalisation/suivi/<?= $l['patient_id_reel'] ?? '0' ?>"
                   class="btn btn-sm btn-info text-white rounded-pill">
                   <i class="bi bi-speedometer2"></i> Paramètres
                </a>
                            <button class="btn btn-link btn-sm text-muted text-decoration-none" onclick="libererLit(<?= $l['id'] ?>, '<?= addslashes($nomPatient) ?>')">
                                <small>Libérer le lit</small>
                            </button>
                        <?php else: ?>
                            <span class="text-success fw-bold">DISPONIBLE</span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- DISPONIBILITÉ GLOBALE DANS L'HÔPITAL -->
            <h6 class="section-title text-muted mt-5 mb-3"><i class="bi bi-hospital"></i> Disponibilité Globale des Lits Cliniques</h6>
            <div class="row g-3">
                <?php foreach($lits_global as $service):
                    $libres = $service['total'] - $service['occupes'];
                    $percent = ($service['total'] > 0) ? ($service['occupes'] / $service['total']) * 100 : 0;
                    $progressColor = ($percent > 80) ? 'bg-danger' : (($percent > 50) ? 'bg-warning' : 'bg-success');
                ?>
                <div class="col-md-4 col-lg-3">
                    <div class="card p-3 border-0 shadow-sm rounded-4 bg-white">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <small class="fw-bold text-dark" style="font-size: 0.8rem;"><?= htmlspecialchars($service['service']) ?></small>
                            <span class="badge <?= $libres > 0 ? 'bg-info bg-opacity-10 text-info' : 'bg-danger bg-opacity-10 text-danger' ?> rounded-pill">
                                <?= $libres ?> libre(s)
                            </span>
                        </div>
                        <div class="progress" style="height: 6px; border-radius: 10px; background-color: #f1f5f9;">
                            <div class="progress-bar <?= $progressColor ?>" style="width: <?= $percent ?>%"></div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

        </div>
    </div>
</div>

<!-- MODALE D'INSTALLATION (ADMISSION SUR LIT) -->
<div class="modal fade" id="modalAdmit" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 20px;">
            <div class="modal-header bg-primary text-white border-0 p-4">
                <h5 class="modal-title fw-bold"><i class="bi bi-box-arrow-in-right me-2"></i>Installation Patient</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="<?= BASE_URL ?>hospitalisation/valider-installation" method="POST">
                <input type="hidden" name="admission_id" id="admitId">
                <div class="modal-body p-4">
                    <div class="mb-4">
                        <p class="mb-1 text-muted">Patient à installer :</p>
                        <h4 id="admitPatientName" class="text-dark fw-bold">---</h4>
                    </div>
                    <div class="mb-4">
                        <label class="form-label fw-bold text-primary">SÉLECTIONNER UN LIT LIBRE DANS VOTRE UNITÉ</label>
                        <select name="lit_id" class="form-select form-select-lg border-2" required>
                            <option value="">-- Choisir un lit --</option>
                            <?php foreach($lits_service as $l): if(!$l['patient_id']): ?>
                                <option value="<?= $l['id'] ?>">Chambre <?= htmlspecialchars($l['nom_chambre']) ?> - Lit <?= htmlspecialchars($l['nom_lit']) ?></option>
                            <?php endif; endforeach; ?>
                        </select>
                    </div>
                    <div class="alert alert-info border-0 bg-light text-primary small">
                        <i class="bi bi-info-circle-fill me-2"></i> Valider l'installation marquera le patient comme hospitalisé et créera sa feuille de soins.
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <button type="submit" class="btn btn-primary btn-lg w-100 rounded-pill fw-bold shadow">CONFIRMER L'INSTALLATION</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // 1. MISE À JOUR DE L'HORLOGE
    function updateClock() {
        const now = new Date();
        document.getElementById('clock').innerText = now.toLocaleTimeString('fr-FR');
    }
    setInterval(updateClock, 1000);
    updateClock();

    // 2. OUVERTURE MODALE ADMISSION
    function startAdmission(id, name) {
        document.getElementById('admitId').value = id;
        document.getElementById('admitPatientName').innerText = name;
        new bootstrap.Modal(document.getElementById('modalAdmit')).show();
    }

    // 3. ACTION LIBÉRER UN LIT (DÉCHARGE)
    function libererLit(litId, name) {
        if(confirm("Libérer le lit de " + name + " ? Le patient sera marqué comme sorti du service.")) {
            const fd = new FormData();
            fd.append('lit_id', litId);
            fetch('<?= BASE_URL ?>lits/decharger', { method: 'POST', body: fd })
            .then(res => res.json())
            .then(data => {
                if(data.success) location.reload();
                else alert("Erreur : " + data.message);
            });
        }
    }

    // 4. AUTO-REFRESH TOUTES LES 2 MINUTES
    setTimeout(() => { location.reload(); }, 120000);
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>