<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<style>
    .sidebar { display: none !important; }
    main { margin-left: 0 !important; width: 100% !important; background: #f1f5f9 !important; min-height: 100vh; padding: 2rem; }
    .prio-btn { padding: 18px 24px; border-radius: 16px; cursor: pointer; border: 3px solid transparent; transition: all 0.2s; }
    .prio-btn:hover { transform: translateY(-2px); }
</style>

<main>
    <div class="container-fluid">
        <div class="d-flex align-items-center mb-4 gap-3">
            <a href="<?= BASE_URL ?>urgences" class="btn btn-outline-secondary rounded-pill">
                <i class="bi bi-arrow-left"></i>
            </a>
            <div>
                <h4 class="fw-bold mb-0"><i class="bi bi-heart-pulse-fill text-danger me-2"></i>Triage IAO</h4>
                <small class="text-muted">
                    <?= htmlspecialchars($adm['nom'] . ' ' . $adm['prenom']) ?>
                    &bull; <?= !empty($adm['date_naissance']) ? date_diff(date_create($adm['date_naissance']), date_create('now'))->y . ' ans' : '?' ?>
                    &bull; <?= $adm['sexe'] == 'F' ? 'Féminin' : 'Masculin' ?>
                    &bull; N° <?= htmlspecialchars($adm['dossier_numero'] ?? '') ?>
                </small>
            </div>
        </div>

        <form action="<?= BASE_URL ?>urgences/save-triage" method="POST">
            <input type="hidden" name="admission_id" value="<?= $adm['id'] ?>">

            <div class="row g-4">
                <!-- GLASGOW -->
                <div class="col-lg-4">
                    <div class="card border-0 shadow-sm rounded-4 h-100">
                        <div class="card-body p-4">
                            <h5 class="fw-bold mb-4"><i class="bi bi-brain text-primary me-2"></i>Score de Glasgow</h5>

                            <div class="mb-3">
                                <label class="form-label small fw-bold text-uppercase text-muted">Ouverture des Yeux</label>
                                <select class="form-select gcs-calc" name="gcs_y" onchange="updateGCS()">
                                    <option value="4">4 – Spontanée</option>
                                    <option value="3">3 – Au bruit</option>
                                    <option value="2">2 – À la douleur</option>
                                    <option value="1">1 – Nulle</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-bold text-uppercase text-muted">Réponse Verbale</label>
                                <select class="form-select gcs-calc" name="gcs_v" onchange="updateGCS()">
                                    <option value="5">5 – Orientée</option>
                                    <option value="4">4 – Confuse</option>
                                    <option value="3">3 – Inappropriée</option>
                                    <option value="2">2 – Incompréhensible</option>
                                    <option value="1">1 – Nulle</option>
                                </select>
                            </div>
                            <div class="mb-4">
                                <label class="form-label small fw-bold text-uppercase text-muted">Réponse Motrice</label>
                                <select class="form-select gcs-calc" name="gcs_m" onchange="updateGCS()">
                                    <option value="6">6 – Obéit aux ordres</option>
                                    <option value="5">5 – Localise la douleur</option>
                                    <option value="4">4 – Retrait</option>
                                    <option value="3">3 – Flexion anormale</option>
                                    <option value="2">2 – Extension</option>
                                    <option value="1">1 – Nulle</option>
                                </select>
                            </div>

                            <div class="text-center bg-primary bg-opacity-10 p-3 rounded-4">
                                <span class="d-block text-muted small fw-bold">SCORE GCS TOTAL</span>
                                <h1 class="fw-black mb-0 text-primary" id="gcs_display">15/15</h1>
                                <small class="text-muted" id="gcs_interpretation">Normal</small>
                                <input type="hidden" name="gcs_total" id="gcs_input" value="15">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- CONSTANTES -->
                <div class="col-lg-4">
                    <div class="card border-0 shadow-sm rounded-4 h-100">
                        <div class="card-body p-4">
                            <h5 class="fw-bold mb-4"><i class="bi bi-activity text-success me-2"></i>Paramètres Vitaux</h5>
                            <div class="row g-3">
                                <div class="col-6">
                                    <label class="form-label small fw-bold text-uppercase text-muted">Temp. (°C)</label>
                                    <input type="number" step="0.1" name="temp" class="form-control"
                                           placeholder="37.0" value="<?= htmlspecialchars($triageData['temperature'] ?? '') ?>">
                                </div>
                                <div class="col-6">
                                    <label class="form-label small fw-bold text-uppercase text-muted">SpO2 (%)</label>
                                    <input type="number" name="spo2" class="form-control"
                                           placeholder="98" value="<?= htmlspecialchars($triageData['spo2'] ?? '') ?>">
                                </div>
                                <div class="col-6">
                                    <label class="form-label small fw-bold text-uppercase text-muted">TA Syst. (mmHg)</label>
                                    <input type="number" name="sys" class="form-control"
                                           placeholder="120" value="<?= htmlspecialchars($triageData['tension_sys'] ?? '') ?>">
                                </div>
                                <div class="col-6">
                                    <label class="form-label small fw-bold text-uppercase text-muted">TA Diast. (mmHg)</label>
                                    <input type="number" name="dia" class="form-control"
                                           placeholder="80" value="<?= htmlspecialchars($triageData['tension_dia'] ?? '') ?>">
                                </div>
                                <div class="col-6">
                                    <label class="form-label small fw-bold text-uppercase text-muted">Pouls (BPM)</label>
                                    <input type="number" name="pouls" class="form-control"
                                           placeholder="80" value="<?= htmlspecialchars($triageData['pouls'] ?? '') ?>">
                                </div>
                                <div class="col-6">
                                    <label class="form-label small fw-bold text-uppercase text-muted">FR (c/min)</label>
                                    <input type="number" name="freq_resp" class="form-control"
                                           placeholder="16" value="<?= htmlspecialchars($triageData['frequence_resp'] ?? '') ?>">
                                </div>
                                <div class="col-12">
                                    <label class="form-label small fw-bold text-uppercase text-muted">Motif / Plainte principale</label>
                                    <textarea name="motif" class="form-control" rows="3"
                                              placeholder="Symptômes décrits par le patient..."><?= htmlspecialchars($triageData['motif_plainte'] ?? '') ?></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- PRIORITÉ + MÉDECIN -->
                <div class="col-lg-4">
                    <div class="card border-0 shadow-sm rounded-4 h-100">
                        <div class="card-body p-4">
                            <h5 class="fw-bold mb-4"><i class="bi bi-shield-exclamation text-warning me-2"></i>Niveau de Triage</h5>

                            <div class="d-grid gap-3 mb-4">
                                <label class="prio-btn d-flex align-items-center gap-3"
                                       style="background:#fff5f5;border-color:#ef4444;" onclick="setPrio(this,'P1-VITAL')">
                                    <input type="radio" name="niveau_priorite" value="P1-VITAL" class="d-none" <?= ($triageData['niveau_gravite'] ?? '') === '1-ROUGE' ? 'checked' : '' ?>>
                                    <span style="width:12px;height:12px;background:#ef4444;border-radius:50%;flex-shrink:0;"></span>
                                    <div>
                                        <strong class="text-danger d-block">P1 - VITAL</strong>
                                        <small class="text-muted">Déchocage immédiat</small>
                                    </div>
                                </label>
                                <label class="prio-btn d-flex align-items-center gap-3"
                                       style="background:#fffaf5;border-color:#f97316;" onclick="setPrio(this,'P2-URGENT')">
                                    <input type="radio" name="niveau_priorite" value="P2-URGENT" class="d-none" <?= ($triageData['niveau_gravite'] ?? '') === '2-ORANGE' ? 'checked' : '' ?>>
                                    <span style="width:12px;height:12px;background:#f97316;border-radius:50%;flex-shrink:0;"></span>
                                    <div>
                                        <strong style="color:#f97316;" class="d-block">P2 - URGENT</strong>
                                        <small class="text-muted">Prise en charge &lt; 20 min</small>
                                    </div>
                                </label>
                                <label class="prio-btn d-flex align-items-center gap-3"
                                       style="background:#fefce8;border-color:#eab308;" onclick="setPrio(this,'P3-STABLE')">
                                    <input type="radio" name="niveau_priorite" value="P3-STABLE" class="d-none" checked>
                                    <span style="width:12px;height:12px;background:#eab308;border-radius:50%;flex-shrink:0;"></span>
                                    <div>
                                        <strong class="text-warning d-block">P3 - STABLE</strong>
                                        <small class="text-muted">Attente tolérable</small>
                                    </div>
                                </label>
                                <label class="prio-btn d-flex align-items-center gap-3"
                                       style="background:#f0fdf4;border-color:#22c55e;" onclick="setPrio(this,'P4-MINEUR')">
                                    <input type="radio" name="niveau_priorite" value="P4-MINEUR" class="d-none">
                                    <span style="width:12px;height:12px;background:#22c55e;border-radius:50%;flex-shrink:0;"></span>
                                    <div>
                                        <strong class="text-success d-block">P4 - MINEUR</strong>
                                        <small class="text-muted">Non urgent</small>
                                    </div>
                                </label>
                            </div>

                            <div>
                                <label class="form-label small fw-bold text-uppercase text-muted">Attribuer au Médecin</label>
                                <select name="medecin_id" class="form-select">
                                    <option value="">— Non assigné —</option>
                                    <?php foreach ($medecins as $med): ?>
                                        <option value="<?= $med['id'] ?>"
                                                <?= ($adm['medecin_id'] ?? '') == $med['id'] ? 'selected' : '' ?>>
                                            Dr <?= htmlspecialchars($med['nom'] . ' ' . $med['prenom']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-flex gap-3 justify-content-end mt-4">
                <a href="<?= BASE_URL ?>urgences" class="btn btn-outline-secondary rounded-pill px-4">Annuler</a>
                <button type="submit" class="btn btn-primary btn-lg rounded-pill px-5 shadow">
                    <i class="bi bi-check-circle-fill me-2"></i>VALIDER LE TRIAGE
                </button>
            </div>
        </form>
    </div>
</main>

<script>
// Mise à jour GCS
function updateGCS() {
    let total = 0;
    document.querySelectorAll('.gcs-calc').forEach(s => total += parseInt(s.value));
    document.getElementById('gcs_display').textContent = total + '/15';
    document.getElementById('gcs_input').value = total;
    const interp = total >= 13 ? 'Normal' : total >= 9 ? 'Altéré' : total >= 6 ? 'Coma modéré' : 'Coma profond';
    document.getElementById('gcs_interpretation').textContent = interp;
}

// Sélection niveau priorité
function setPrio(el, val) {
    document.querySelectorAll('.prio-btn').forEach(b => b.style.opacity = '0.6');
    el.style.opacity = '1';
    el.style.transform = 'scale(1.02)';
    const radio = el.querySelector('input[type="radio"]');
    if (radio) radio.checked = true;
}

// Init
document.querySelectorAll('.prio-btn').forEach(b => {
    b.style.opacity = '0.6';
    b.addEventListener('click', () => {
        document.querySelectorAll('.prio-btn').forEach(x => { x.style.opacity = '0.6'; x.style.transform = ''; });
        b.style.opacity = '1';
        b.style.transform = 'scale(1.02)';
    });
});
// Activer le P3 par défaut
const defaultPrio = document.querySelector('.prio-btn:nth-child(3)');
if (defaultPrio) { defaultPrio.style.opacity = '1'; }
</script>
<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
