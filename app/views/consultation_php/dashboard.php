<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<style>
    :root { --php-color: #7c3aed; --php-light: #ede9fe; }
    body { background:#f5f3ff; font-family:'Inter',system-ui,sans-serif; color:#1e293b; }
    main { margin-left:0!important; width:100%!important; }
    .sidebar { display:none!important; }

    .topbar { background:white; padding:20px 32px; border-bottom:1px solid #ede9fe; position:sticky; top:0; z-index:100; }
    .php-badge { background:var(--php-color); color:white; padding:5px 14px; border-radius:100px; font-weight:800; font-size:.72rem; }

    .patient-row { background:white; border-radius:16px; border:1px solid #ede9fe; padding:20px 24px; margin-bottom:12px; transition:all .2s; }
    .patient-row:hover { border-color:var(--php-color); box-shadow:0 4px 16px rgba(124,58,237,.1); }

    .vital-chip { background:#f5f3ff; border:1px solid #ddd6fe; border-radius:8px; padding:4px 10px; font-size:.72rem; font-weight:700; color:#5b21b6; }

    .btn-consulter { background:var(--php-color); color:white; padding:10px 24px; border-radius:12px; font-weight:700; border:none; font-size:.88rem; transition:.2s; }
    .btn-consulter:hover { background:#6d28d9; color:white; transform:translateY(-1px); }

    .history-row { background:white; border-radius:12px; border:1px solid #ede9fe; padding:12px 16px; margin-bottom:8px; font-size:.83rem; }
    .type-badge { font-size:.67rem; font-weight:800; padding:3px 8px; border-radius:100px; background:var(--php-light); color:var(--php-color); }
</style>

<div class="topbar">
    <div class="d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center gap-3">
            <span class="php-badge"><i class="bi bi-building-fill me-1"></i>MODULE PHP</span>
            <div>
                <h5 class="mb-0 fw-bold">Consultation Externe PHP</h5>
                <small class="text-muted">Dr. <?= htmlspecialchars($_SESSION['user_nom'] ?? '') ?></small>
            </div>
        </div>
        <div class="d-flex align-items-center gap-3">
            <span id="clock" class="fw-bold" style="font-size:1.1rem; color:var(--php-color); font-family:monospace;"></span>
            <a href="<?= BASE_URL ?>dashboard" class="btn btn-light border rounded-3 px-3 py-2 fw-bold" style="font-size:.82rem;">
                <i class="bi bi-arrow-left me-1"></i>Dashboard
            </a>
            <a href="<?= BASE_URL ?>logout" class="btn btn-outline-danger rounded-3 px-3 py-2">
                <i class="bi bi-power"></i>
            </a>
        </div>
    </div>
</div>

<div class="container mt-4">
    <div class="row g-4">

        <!-- FILE D'ATTENTE -->
        <div class="col-lg-8">
            <div class="d-flex align-items-center gap-3 mb-3">
                <h5 class="fw-bold mb-0">File d'attente</h5>
                <span class="badge rounded-pill" style="background:var(--php-light); color:var(--php-color);"><?= count($patients) ?> patient(s)</span>
            </div>

            <?php if(empty($patients)): ?>
            <div class="text-center py-5" style="background:white; border-radius:20px; border:1px dashed #ddd6fe;">
                <i class="bi bi-person-check display-4 opacity-25" style="color:var(--php-color);"></i>
                <p class="text-muted mt-3 mb-0">Aucun patient PHP en attente.</p>
            </div>
            <?php else: foreach($patients as $p): ?>
            <div class="patient-row">
                <div class="d-flex align-items-start justify-content-between gap-3">
                    <div class="flex-grow-1">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <h6 class="fw-bold mb-0"><?= strtoupper(htmlspecialchars($p['nom'])) ?> <?= htmlspecialchars($p['prenom']) ?></h6>
                            <span class="type-badge"><?= $p['type_client'] === 'AGENTS_PHP' ? 'Agent PHP' : 'Famille PHP' ?></span>
                            <small class="text-muted font-monospace"><?= htmlspecialchars($p['dossier_numero']) ?></small>
                        </div>

                        <?php if(!empty($p['motif_consultation'])): ?>
                        <div class="mb-2 text-muted" style="font-size:.85rem;">
                            <i class="bi bi-chat-right-text me-1"></i><?= htmlspecialchars($p['motif_consultation']) ?>
                        </div>
                        <?php endif; ?>

                        <!-- Constantes vitales -->
                        <div class="d-flex flex-wrap gap-2 mt-2">
                            <?php if($p['temperature']): ?>
                            <span class="vital-chip"><i class="bi bi-thermometer me-1"></i><?= $p['temperature'] ?>°C</span>
                            <?php endif; ?>
                            <?php if($p['pression_arterielle_systolique']): ?>
                            <span class="vital-chip"><i class="bi bi-heart-pulse me-1"></i><?= $p['pression_arterielle_systolique'] ?>/<?= $p['pression_arterielle_diastolique'] ?> mmHg</span>
                            <?php endif; ?>
                            <?php if($p['frequence_cardiaque']): ?>
                            <span class="vital-chip"><i class="bi bi-activity me-1"></i><?= $p['frequence_cardiaque'] ?> bpm</span>
                            <?php endif; ?>
                            <?php if($p['saturation_oxygene']): ?>
                            <span class="vital-chip">SpO2 <?= $p['saturation_oxygene'] ?>%</span>
                            <?php endif; ?>
                            <?php if($p['poids']): ?>
                            <span class="vital-chip"><?= $p['poids'] ?> kg</span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="text-end flex-shrink-0">
                        <div class="text-muted mb-2" style="font-size:.72rem;">
                            <?php if($p['date_mesure']): ?>
                            <i class="bi bi-clock me-1"></i><?= date('H:i', strtotime($p['date_mesure'])) ?>
                            <?php endif; ?>
                        </div>
                        <a href="<?= BASE_URL ?>consultation/commencer?patient_id=<?= (int)$p['id'] ?>"
                           class="btn btn-consulter">
                            <i class="bi bi-clipboard2-pulse me-2"></i>Consulter
                        </a>
                    </div>
                </div>
            </div>
            <?php endforeach; endif; ?>
        </div>

        <!-- CONSULTATIONS DU JOUR -->
        <div class="col-lg-4">
            <h5 class="fw-bold mb-3">Consultations du jour</h5>
            <?php if(empty($consultations_jour)): ?>
            <p class="text-muted small">Aucune consultation aujourd'hui.</p>
            <?php else: foreach($consultations_jour as $c): ?>
            <div class="history-row d-flex justify-content-between align-items-center">
                <div>
                    <div class="fw-bold text-dark"><?= htmlspecialchars($c['nom']) ?> <?= htmlspecialchars($c['prenom']) ?></div>
                    <div class="d-flex align-items-center gap-2 mt-1">
                        <span class="type-badge"><?= $c['type_client'] === 'AGENTS_PHP' ? 'Agent' : 'Famille' ?></span>
                        <?php if(!empty($c['diagnostic_principal'])): ?>
                        <small class="text-muted"><?= htmlspecialchars(substr($c['diagnostic_principal'], 0, 30)) ?>…</small>
                        <?php endif; ?>
                    </div>
                </div>
                <small class="text-muted"><?= date('H:i', strtotime($c['created_at'])) ?></small>
            </div>
            <?php endforeach; endif; ?>
        </div>

    </div>
</div>

<script>
setInterval(() => document.getElementById('clock').textContent = new Date().toLocaleTimeString('fr-FR'), 1000);
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
