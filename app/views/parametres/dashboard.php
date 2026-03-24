<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<!-- On force le chargement de Bootstrap JS au cas où il manquerait dans le footer -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

<style>
    :root {
        --bureau-color: <?= ($bureauId == 1) ? '#2563eb' : '#0891b2' ?>;
        --soft-bg: #f8fafc;
    }
    body { background-color: var(--soft-bg); font-family: 'Plus Jakarta Sans', sans-serif; color: #1e293b; }
    main { margin-left: 0 !important; width: 100% !important; }

    .header-bureau { background: white; padding: 25px 0; border-bottom: 1px solid #e2e8f0; margin-bottom: 30px; }
    .bureau-badge { background: var(--bureau-color); color: white; padding: 6px 16px; border-radius: 100px; font-weight: 800; font-size: 0.75rem; }

    /* Carte Patient - On s'assure qu'elle ressemble à un bouton cliquable */
    .patient-card {
        background: white; border-radius: 20px; border: 1px solid #e2e8f0; padding: 20px;
        transition: all 0.2s ease; cursor: pointer; position: relative;
        box-shadow: 0 4px 6px rgba(0,0,0,0.02);
    }
    .patient-card:hover {
        transform: translateY(-4px);
        border-color: var(--bureau-color);
        box-shadow: 0 10px 20px rgba(0,0,0,0.05);
    }
    .patient-card:active { transform: scale(0.98); }

    .ticket-number {
        width: 50px; height: 50px; background: #f1f5f9; color: var(--bureau-color);
        border-radius: 14px; display: flex; align-items: center; justify-content: center;
        font-weight: 800; font-size: 1.2rem;
    }

    /* Modal Styling */
    .modal-content { border-radius: 28px; border: none; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); }
    .modal-header { border-bottom: 1px solid #f1f5f9; padding: 25px 30px; }
    .form-label { font-weight: 700; font-size: 0.8rem; color: #64748b; text-transform: uppercase; margin-bottom: 8px; }
    .input-custom {
        background: #f8fafc; border: 2px solid #e2e8f0; border-radius: 12px; padding: 12px;
        font-weight: 600; width: 100%; transition: 0.3s;
    }
    .input-custom:focus { border-color: var(--bureau-color); background: white; outline: none; }

    .history-item { background: white; padding: 15px; border-radius: 15px; margin-bottom: 10px; border-left: 4px solid #10b981; }
</style>

<div class="header-bureau">
    <div class="container d-flex justify-content-between align-items-center">
        <div>
            <span class="bureau-badge text-uppercase mb-2 d-inline-block">
                <i class="bi bi-pc-display-horizontal me-2"></i><?= $bureauLabel ?>
            </span>
            <h2 class="fw-800 mb-0">Poste de Tri & Paramètres</h2>
        </div>
        <div class="d-flex align-items-center gap-3">
            <div class="text-end me-3">
                <span class="fw-bold d-block fs-5" id="liveClock">00:00:00</span>
                <small class="text-muted fw-semibold"><?= date('d F Y') ?></small>
            </div>
            <a href="<?= BASE_URL ?>logout" class="btn btn-outline-danger rounded-circle p-2"><i class="bi bi-power fs-5"></i></a>
        </div>
    </div>
</div>

<div class="container">
    <div class="row g-4">
        <!-- LISTE D'ATTENTE -->
        <div class="col-lg-8">
            <h5 class="fw-bold mb-4">Patients en attente <span class="badge bg-primary bg-opacity-10 text-primary ms-2"><?= count($patients_attente) ?></span></h5>

            <div class="row g-3">
                <?php if(empty($patients_attente)): ?>
                    <div class="col-12 text-center py-5">
                        <i class="bi bi-person-check text-muted display-1 opacity-25"></i>
                        <p class="text-muted mt-3">Aucun patient dans votre file d'attente.</p>
                    </div>
                <?php else: foreach($patients_attente as $p): ?>
                    <div class="col-md-6">
                        <!-- LA CARTE ENTIÈRE DÉCLENCHE LA MODALE -->
                        <div class="patient-card d-flex align-items-center gap-3"
                             onclick="showForm(<?= $p['id'] ?>, '<?= addslashes($p['nom'] . ' ' . $p['prenom']) ?>')">
                            <div class="ticket-number">#<?= $p['numero_ordre'] ?></div>
                            <div class="flex-grow-1">
                                <h6 class="fw-bold mb-0 text-dark"><?= strtoupper($p['nom']) ?> <?= $p['prenom'] ?></h6>
                                <small class="text-muted"><?= $p['dossier_numero'] ?></small>
                            </div>
                            <div class="text-primary fs-4"><i class="bi bi-plus-circle-fill"></i></div>
                        </div>
                    </div>
                <?php endforeach; endif; ?>
            </div>
        </div>

        <!-- HISTORIQUE -->
        <div class="col-lg-4">
            <h5 class="fw-bold mb-4">Traités aujourd'hui</h5>
            <?php foreach($patients_reçus as $pr): ?>
                <div class="history-item shadow-sm d-flex justify-content-between align-items-center">
                    <div>
                        <div class="fw-bold text-dark small"><?= $pr['nom'] ?> <?= $pr['prenom'] ?></div>
                        <small class="text-muted"><i class="bi bi-clock me-1"></i><?= date('H:i', strtotime($pr['date_mesure'])) ?></small>
                    </div>
                    <i class="bi bi-check-circle-fill text-success fs-5"></i>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- MODALE DE SAISIE -->
<div class="modal fade" id="modalSaisie" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0 p-4">
                <h5 class="modal-title fw-bold">Paramètres de : <span id="displayPatientName" class="text-primary"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="<?= BASE_URL ?>parametres/save" method="POST">
                <input type="hidden" name="patient_id" id="formPatientId">
                <div class="modal-body p-4 pt-0">

                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Température (°C)</label>
                            <input type="number" step="0.1" name="temp" class="input-custom" placeholder="37.0" required>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label">Tension Artérielle (mmHg)</label>
                            <div class="d-flex align-items-center gap-2">
                                <input type="number" name="sys" class="input-custom" placeholder="120" required>
                                <span class="fw-bold">/</span>
                                <input type="number" name="dia" class="input-custom" placeholder="80" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Pouls (bpm)</label>
                            <input type="number" name="pouls" class="input-custom" placeholder="80">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">SpO2 (%)</label>
                            <input type="number" name="spo2" class="input-custom" placeholder="98">
                        </div>

                        <div class="col-md-4">
    <label class="form-label">Taille (cm)</label>
    <input type="number" name="taille" class="input-custom" placeholder="170">
</div>
                        <div class="col-md-4">
                            <label class="form-label">Poids (kg)</label>
                            <input type="number" step="0.1" name="poids" class="input-custom" placeholder="70">
                        </div>

                        <div class="col-12 mt-4">
                            <label class="form-label text-primary">Motif de consultation</label>
                            <textarea name="motif" class="input-custom" rows="2" placeholder="Symptômes décrits..." required></textarea>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Service de destination</label>
                            <select name="service_id" class="input-custom" required>
                                <option value="1">Médecine Générale</option>
                                <option value="2">Chirurgie</option>
                                <option value="4">Maternité</option>
                                <option value="5">Pédiatrie</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Attribuer au Médecin</label>
                            <select name="medecin_id" class="input-custom" required>
                                <?php
                                    $db = (new Database())->getConnection();
                                    $meds = $db->query("SELECT id, nom FROM users WHERE role = 'MEDECIN' AND statut = 1")->fetchAll();
                                    foreach($meds as $m) echo "<option value='".$m['id']."'>Dr. ".$m['nom']."</option>";
                                ?>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 p-4">
                    <button type="submit" class="btn btn-primary w-100 rounded-pill py-3 fw-bold shadow">
                        VALIDER ET ENVOYER AU MÉDECIN
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Horloge
    setInterval(() => {
        document.getElementById('liveClock').innerText = new Date().toLocaleTimeString('fr-FR');
    }, 1000);

    // Initialisation forcée de la modale
    const monModalSaisie = new bootstrap.Modal(document.getElementById('modalSaisie'));

    function showForm(id, name) {
        document.getElementById('formPatientId').value = id;
        document.getElementById('displayPatientName').innerText = name;
        monModalSaisie.show();
    }
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>