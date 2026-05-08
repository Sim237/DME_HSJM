<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<style>
    :root { --php-color: #7c3aed; --php-light: #ede9fe; }
    body { background:#f5f3ff; font-family:'Inter',system-ui,sans-serif; color:#1e293b; }
    main { margin-left:0!important; width:100%!important; }
    .sidebar { display:none!important; }

    .header-bureau { background:white; padding:22px 0; border-bottom:1px solid #ede9fe; margin-bottom:28px; }
    .bureau-badge { background:var(--php-color); color:white; padding:6px 16px; border-radius:100px; font-weight:800; font-size:.72rem; letter-spacing:.06em; }

    .patient-card { background:white; border-radius:18px; border:1px solid #ede9fe; padding:18px; transition:all .2s; cursor:pointer; }
    .patient-card:hover { transform:translateY(-4px); border-color:var(--php-color); box-shadow:0 8px 20px rgba(124,58,237,.12); }
    .ticket-number { width:48px; height:48px; background:var(--php-light); color:var(--php-color); border-radius:12px; display:flex; align-items:center; justify-content:center; font-weight:800; font-size:1.1rem; }

    .modal-content { border-radius:28px; border:none; box-shadow:0 25px 50px rgba(0,0,0,.2); }
    .form-label { font-weight:700; font-size:.78rem; color:#64748b; text-transform:uppercase; margin-bottom:6px; }
    .input-custom { background:#f5f3ff; border:2px solid #ddd6fe; border-radius:12px; padding:11px 14px; font-weight:600; width:100%; transition:.2s; }
    .input-custom:focus { border-color:var(--php-color); background:white; outline:none; box-shadow:0 0 0 3px rgba(124,58,237,.1); }

    .history-item { background:white; padding:14px; border-radius:14px; margin-bottom:8px; border-left:4px solid var(--php-color); }
    .type-badge { font-size:.67rem; font-weight:800; padding:3px 9px; border-radius:100px; background:var(--php-light); color:var(--php-color); }
</style>

<div class="header-bureau">
    <div class="container d-flex justify-content-between align-items-center">
        <div>
            <span class="bureau-badge mb-2 d-inline-block"><i class="bi bi-building-fill me-1"></i>MODULE PHP</span>
            <h4 class="fw-bold mb-0"><?= htmlspecialchars($bureauLabel) ?></h4>
        </div>
        <div class="d-flex align-items-center gap-3">
            <span class="fw-bold d-none d-md-block" id="liveClock" style="font-size:1.2rem; color:var(--php-color);"></span>
            <a href="<?= BASE_URL ?>logout" class="btn btn-outline-danger rounded-3 px-3 py-2"><i class="bi bi-power fs-5"></i></a>
        </div>
    </div>
</div>

<?php if(isset($_GET['success'])): ?>
<div class="container mb-3">
    <div class="alert border-0 rounded-4 p-3" style="background:#f3e8ff; color:var(--php-color);">
        <i class="bi bi-check-circle-fill me-2"></i><strong>Paramètres enregistrés.</strong> Patient envoyé en consultation PHP.
    </div>
</div>
<?php endif; ?>

<div class="container">
    <div class="row g-4">

        <!-- FILE D'ATTENTE PHP -->
        <div class="col-lg-8">
            <h5 class="fw-bold mb-4">
                Patients PHP en attente
                <span class="badge ms-2 rounded-pill" style="background:var(--php-light); color:var(--php-color);"><?= count($patients_attente) ?></span>
            </h5>

            <div class="row g-3">
                <?php if(empty($patients_attente)): ?>
                <div class="col-12 text-center py-5">
                    <i class="bi bi-person-check display-4 opacity-25" style="color:var(--php-color);"></i>
                    <p class="text-muted mt-3">Aucun patient PHP dans votre file d'attente.</p>
                </div>
                <?php else: foreach($patients_attente as $p): ?>
                <div class="col-md-6">
                    <div class="patient-card d-flex align-items-center gap-3"
                         onclick="showForm(<?= (int)$p['id'] ?>, '<?= addslashes(htmlspecialchars($p['nom'] . ' ' . $p['prenom'])) ?>', '<?= htmlspecialchars($p['type_client'] ?? '') ?>')">
                        <div class="ticket-number">#<?= (int)$p['numero_ordre'] ?></div>
                        <div class="flex-grow-1">
                            <h6 class="fw-bold mb-1"><?= strtoupper(htmlspecialchars($p['nom'])) ?> <?= htmlspecialchars($p['prenom']) ?></h6>
                            <small class="text-muted d-block font-monospace"><?= htmlspecialchars($p['dossier_numero']) ?></small>
                            <span class="type-badge"><?= $p['type_client'] === 'AGENTS_PHP' ? 'Agent PHP' : 'Famille PHP' ?></span>
                        </div>
                        <i class="bi bi-plus-circle-fill fs-4" style="color:var(--php-color);"></i>
                    </div>
                </div>
                <?php endforeach; endif; ?>
            </div>
        </div>

        <!-- HISTORIQUE -->
        <div class="col-lg-4">
            <h5 class="fw-bold mb-4">Traités aujourd'hui</h5>
            <?php if(empty($patients_recus)): ?>
            <p class="text-muted small">Aucun patient traité.</p>
            <?php else: foreach($patients_recus as $pr): ?>
            <div class="history-item shadow-sm d-flex justify-content-between align-items-center">
                <div>
                    <div class="fw-bold text-dark small"><?= htmlspecialchars($pr['nom']) ?> <?= htmlspecialchars($pr['prenom']) ?></div>
                    <small class="text-muted"><i class="bi bi-clock me-1"></i><?= date('H:i', strtotime($pr['date_mesure'])) ?></small>
                </div>
                <i class="bi bi-check-circle-fill fs-5" style="color:var(--php-color);"></i>
            </div>
            <?php endforeach; endif; ?>
        </div>

    </div>
</div>

<!-- MODALE PARAMÈTRES PHP -->
<div class="modal fade" id="modalSaisiePhp" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0 p-4">
                <h5 class="modal-title fw-bold">
                    Paramètres de : <span id="displayPhpPatientName" style="color:var(--php-color);"></span>
                    <span id="displayPhpTypeClient" class="ms-2 badge rounded-pill" style="background:var(--php-light); color:var(--php-color); font-size:.72rem;"></span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="<?= BASE_URL ?>parametres-php/save" method="POST">
                <input type="hidden" name="patient_id" id="formPhpPatientId">
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
                        <div class="col-12 mt-2">
                            <label class="form-label" style="color:var(--php-color);">Motif de consultation</label>
                            <textarea name="motif" class="input-custom" rows="2" placeholder="Symptômes décrits..." required></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Service de destination</label>
                            <select name="service_id" class="input-custom" required>
                                <?php
                                $db = (new Database())->getConnection();
                                $services = $db->query("SELECT id, nom_service FROM services ORDER BY nom_service ASC")->fetchAll(PDO::FETCH_ASSOC);
                                foreach($services as $sv) {
                                    $phpFlag = stripos($sv['nom_service'], 'PHP') !== false ? ' ★' : '';
                                    echo "<option value='{$sv['id']}'>" . htmlspecialchars($sv['nom_service']) . $phpFlag . "</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Attribuer au Médecin</label>
                            <select name="medecin_id" class="input-custom" required>
                                <?php
                                $meds = $db->query("SELECT id, nom, prenom FROM users WHERE role='MEDECIN' AND actif=1 ORDER BY nom ASC")->fetchAll();
                                foreach($meds as $m) echo "<option value='{$m['id']}'>Dr. " . htmlspecialchars($m['nom'] . ' ' . $m['prenom']) . "</option>";
                                ?>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 p-4">
                    <button type="submit" class="btn w-100 rounded-pill py-3 fw-bold text-white shadow" style="background:var(--php-color);">
                        VALIDER ET ENVOYER EN CONSULTATION PHP
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
setInterval(() => document.getElementById('liveClock').textContent = new Date().toLocaleTimeString('fr-FR'), 1000);

const phpModal = new bootstrap.Modal(document.getElementById('modalSaisiePhp'));

function showForm(id, name, typeClient) {
    document.getElementById('formPhpPatientId').value = id;
    document.getElementById('displayPhpPatientName').textContent = name;
    const labels = { 'FAMILLE_PHP': 'Famille PHP', 'AGENTS_PHP': 'Agents PHP' };
    document.getElementById('displayPhpTypeClient').textContent = labels[typeClient] || typeClient;
    phpModal.show();
}
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
