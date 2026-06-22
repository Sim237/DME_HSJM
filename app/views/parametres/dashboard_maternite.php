<?php
/**
 * SimCare+ — Dossier Médical Électronique (DME)
 * Copyright (c) 2024-2026 Franck Simeni. Tous droits réservés.
 * Développé pour la gestion hospitalière, et le bien être numérique des patients.
 *
 * Toute reproduction, modification ou distribution de ce logiciel,
 * en tout ou en partie, sans autorisation écrite préalable de l'auteur
 * est strictement interdite et constitue une contrefaçon.
 *
 * Protected under OAPI Agreement — Annexe VII · Berne Convention
 */
 require_once __DIR__ . '/../layouts/header.php'; ?>

<style>
:root { --mat-color: #db2777; --mat-lt: #fdf2f8; --mat-border: #f9a8d4; }
body  { background: #fdf2f8; }

.header-mat {
    background: linear-gradient(135deg, #831843 0%, #db2777 60%, #ec4899 100%);
    color: white; padding: 22px 0; margin-bottom: 28px;
    box-shadow: 0 4px 20px rgba(219,39,119,.25);
}
.mat-badge { background: rgba(255,255,255,.18); border: 1.5px solid rgba(255,255,255,.3);
    padding: 5px 14px; border-radius: 20px; font-weight: 800; font-size: .75rem; }

.patient-card {
    background: white; border-radius: 18px; border: 1.5px solid #fce7f3;
    padding: 18px; transition: all .2s; cursor: pointer;
    box-shadow: 0 2px 8px rgba(219,39,119,.06);
}
.patient-card:hover { transform: translateY(-3px); border-color: var(--mat-color); box-shadow: 0 8px 24px rgba(219,39,119,.15); }
.ticket-mat { width: 46px; height: 46px; background: #fce7f3; color: var(--mat-color);
    border-radius: 12px; display: flex; align-items: center; justify-content: center;
    font-weight: 900; font-size: 1.1rem; flex-shrink: 0; }

.history-item { background: white; padding: 14px; border-radius: 14px; margin-bottom: 8px;
    border-left: 4px solid #ec4899; box-shadow: 0 1px 4px rgba(0,0,0,.04); }

/* Modal */
.modal-content { border-radius: 24px; border: none; overflow: hidden; }
.input-mat { background: #fdf2f8; border: 2px solid #fce7f3; border-radius: 12px;
    padding: 12px; font-weight: 600; width: 100%; transition: .2s; }
.input-mat:focus { border-color: var(--mat-color); background: white; outline: none;
    box-shadow: 0 0 0 3px rgba(219,39,119,.12); }

.param-card { background: white; border-radius: 14px; border: 1.5px solid #fce7f3;
    padding: 16px; text-align: center; }
.param-card .param-icon { font-size: 1.6rem; margin-bottom: 6px; display: block; }
.param-card label { font-size: .72rem; font-weight: 800; text-transform: uppercase;
    letter-spacing: .5px; color: #9d174d; display: block; margin-bottom: 8px; }
.param-card input { text-align: center; font-size: 1.3rem; font-weight: 800;
    color: #831843; border-radius: 12px; }
</style>

<!-- Header -->
<div class="header-mat">
    <div class="container d-flex justify-content-between align-items-center">
        <div>
            <span class="mat-badge mb-2 d-inline-block">
                <i class="bi bi-heart-pulse-fill me-2"></i>PARAMÈTRES MATERNITÉ
            </span>
            <h2 class="fw-bold mb-0 mt-1" style="font-size:1.4rem;">Bureau de Prise en Charge — Maternité</h2>
        </div>
        <div class="d-flex align-items-center gap-3">
            <div class="text-end me-2">
                <span class="fw-bold d-block fs-5" id="liveClock">00:00:00</span>
                <small style="opacity:.75"><?= date('d F Y') ?></small>
            </div>
            <a href="<?= BASE_URL ?>logout" class="btn btn-outline-light rounded-circle p-2">
                <i class="bi bi-power fs-5"></i>
            </a>
        </div>
    </div>
</div>

<?php if (!empty($_GET['success'])): ?>
<div class="container mb-3">
    <div class="alert border-0 fw-semibold d-flex align-items-center gap-2"
         style="background:#fce7f3;color:#9d174d;border-radius:12px;">
        <i class="bi bi-check-circle-fill fs-5"></i>
        Patiente envoyée en consultation avec succès.
    </div>
</div>
<?php endif; ?>

<div class="container">
    <div class="row g-4">

        <!-- FILE D'ATTENTE -->
        <div class="col-lg-8">
            <div class="d-flex align-items-center gap-3 mb-3">
                <h5 class="fw-bold mb-0">
                    Patientes en attente
                    <span class="badge ms-2 rounded-pill" style="background:#fce7f3;color:#db2777;">
                        <?= count($patients_attente) ?>
                    </span>
                </h5>
                <?php if (!empty($patients_attente)): ?>
                <span class="badge rounded-pill" style="background:#fce7f3;color:#9d174d;font-size:.72rem;">
                    <i class="bi bi-heart-pulse me-1"></i>Circuit Maternité
                </span>
                <?php endif; ?>
            </div>

            <!-- Recherche -->
            <div class="position-relative mb-3">
                <i class="bi bi-search position-absolute text-muted"
                   style="top:50%;left:13px;transform:translateY(-50%);pointer-events:none"></i>
                <input type="text" id="searchPat" class="input-mat ps-5"
                       placeholder="Rechercher par nom, prénom, N° dossier…"
                       oninput="filtrerPatientes(this.value)"
                       style="border-radius:50px!important;">
            </div>

            <div class="row g-3" id="patGrid">
                <?php if (empty($patients_attente)): ?>
                <div class="col-12 text-center py-5">
                    <i class="bi bi-heart-pulse" style="font-size:3rem;color:#f9a8d4;display:block;margin-bottom:12px;"></i>
                    <p class="text-muted fw-semibold">Aucune patiente en attente pour le moment.</p>
                </div>
                <?php else: foreach ($patients_attente as $p):
                    $age = 0;
                    if (!empty($p['date_naissance'])) {
                        $age = (int)date_diff(date_create($p['date_naissance']), date_create('now'))->y;
                    }
                ?>
                <div class="col-md-6 pat-item"
                     data-search="<?= strtolower($p['nom'].' '.$p['prenom'].' '.$p['dossier_numero']) ?>">
                    <div class="patient-card d-flex align-items-center gap-3"
                         onclick="ouvrirModal(<?= $p['id'] ?>, '<?= addslashes($p['nom'].' '.$p['prenom']) ?>', <?= $age ?>)">
                        <div class="ticket-mat">#<?= $p['numero_ordre'] ?></div>
                        <div class="flex-grow-1">
                            <div class="fw-bold text-dark" style="font-size:.92rem;"><?= strtoupper($p['nom']) ?> <?= $p['prenom'] ?></div>
                            <small class="text-muted"><?= $p['dossier_numero'] ?>
                                <?php if (!empty($p['date_naissance'])): ?>
                                · <?= $age ?> ans
                                <?php endif; ?>
                            </small>
                        </div>
                        <i class="bi bi-chevron-right" style="color:#db2777;"></i>
                    </div>
                </div>
                <?php endforeach; endif; ?>
            </div>
        </div>

        <!-- HISTORIQUE -->
        <div class="col-lg-4">
            <h5 class="fw-bold mb-3">Traitées aujourd'hui</h5>
            <?php if (empty($patients_reçus)): ?>
            <div class="text-center text-muted small py-4">
                <i class="bi bi-clock-history d-block mb-2" style="font-size:1.8rem;opacity:.3;"></i>
                Aucune patiente traitée aujourd'hui
            </div>
            <?php else: foreach ($patients_reçus as $pr): ?>
            <div class="history-item d-flex justify-content-between align-items-center">
                <div>
                    <div class="fw-bold text-dark small"><?= $pr['nom'] ?> <?= $pr['prenom'] ?></div>
                    <small class="text-muted"><i class="bi bi-clock me-1"></i><?= date('H:i', strtotime($pr['date_mesure'])) ?></small>
                </div>
                <i class="bi bi-check-circle-fill fs-5" style="color:#ec4899;"></i>
            </div>
            <?php endforeach; endif; ?>
        </div>

    </div>
</div>

<!-- ── MODALE SAISIE PARAMÈTRES MATERNITÉ ─────────────────────────────── -->
<div class="modal fade" id="modalMat" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content shadow-lg">

            <div class="modal-header border-0 p-4"
                 style="background:linear-gradient(135deg,#831843,#db2777);color:white;">
                <div>
                    <h5 class="modal-title fw-bold mb-0">
                        <i class="bi bi-heart-pulse-fill me-2"></i>Paramètres de :
                        <span id="modPatientName"></span>
                    </h5>
                    <small id="modPatientAge" style="opacity:.8;"></small>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <form action="<?= BASE_URL ?>parametres-maternite/save" method="POST">
                <input type="hidden" name="patient_id" id="modPatientId">

                <div class="modal-body p-4">

                    <!-- 4 paramètres maternité -->
                    <div class="row g-3 mb-4">

                        <!-- Tension artérielle -->
                        <div class="col-md-6">
                            <div class="param-card">
                                <span class="param-icon">🩺</span>
                                <label>Tension Artérielle (mmHg)</label>
                                <div class="d-flex align-items-center gap-2">
                                    <input type="number" name="sys" class="input-mat"
                                           placeholder="120" min="50" max="250" style="text-align:center;font-size:1.2rem;font-weight:800;color:#831843;">
                                    <span class="fw-black" style="font-size:1.3rem;color:#db2777;">/</span>
                                    <input type="number" name="dia" class="input-mat"
                                           placeholder="80" min="30" max="150" style="text-align:center;font-size:1.2rem;font-weight:800;color:#831843;">
                                </div>
                            </div>
                        </div>

                        <!-- Température -->
                        <div class="col-md-6">
                            <div class="param-card">
                                <span class="param-icon">🌡️</span>
                                <label>Température (°C)</label>
                                <div class="position-relative">
                                    <input type="text" inputmode="decimal" pattern="[0-9]+([.,][0-9]*)?" name="temp" class="input-mat"
                                           placeholder="37.0"
                                           oninput="checkTemp(this.value)"
                                           style="text-align:center;font-size:1.4rem;font-weight:800;color:#831843;padding-right:40px;">
                                    <span class="position-absolute top-50 end-0 translate-middle-y me-3 fw-bold text-muted pe-none">°C</span>
                                </div>
                                <div id="tempBadge" style="min-height:24px;margin-top:4px;"></div>
                            </div>
                        </div>

                        <!-- Saturation -->
                        <div class="col-md-6">
                            <div class="param-card">
                                <span class="param-icon">💧</span>
                                <label>Saturation SpO₂ (%)</label>
                                <input type="number" step="0.1" name="spo2" class="input-mat"
                                       placeholder="98" min="70" max="100"
                                       style="text-align:center;font-size:1.4rem;font-weight:800;color:#831843;">
                            </div>
                        </div>

                        <!-- Poids -->
                        <div class="col-md-6">
                            <div class="param-card">
                                <span class="param-icon">⚖️</span>
                                <label>Poids (kg)</label>
                                <div class="position-relative">
                                    <input type="number" step="0.1" name="poids" class="input-mat"
                                           placeholder="65" min="20" max="200"
                                           style="text-align:center;font-size:1.4rem;font-weight:800;color:#831843;padding-right:40px;">
                                    <span class="position-absolute top-50 end-0 translate-middle-y me-3 fw-bold text-muted pe-none">kg</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Motif -->
                    <div>
                        <label class="form-label fw-bold" style="font-size:.8rem;color:#9d174d;text-transform:uppercase;">
                            <i class="bi bi-chat-left-text me-1"></i>Motif / Observations
                        </label>
                        <textarea name="motif" class="input-mat" rows="2"
                                  placeholder="Ex: douleurs, CU régulières, perte de liquide…"></textarea>
                    </div>
                </div>

                <div class="modal-footer border-0 p-4">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-4"
                            data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn rounded-pill px-5 py-2 fw-bold shadow"
                            style="background:linear-gradient(135deg,#831843,#db2777);color:white;">
                        <i class="bi bi-send-fill me-2"></i>Envoyer en consultation
                    </button>
                </div>
            </form>

        </div>
    </div>
</div>

<script>
// Horloge
setInterval(() => document.getElementById('liveClock').innerText = new Date().toLocaleTimeString('fr-FR'), 1000);

// Ouvrir la modale
function ouvrirModal(id, nom, age) {
    document.getElementById('modPatientId').value  = id;
    document.getElementById('modPatientName').textContent = nom;
    document.getElementById('modPatientAge').textContent  = age ? age + ' ans' : '';
    // Reset fields
    document.querySelectorAll('#modalMat input:not([type=hidden])').forEach(el => el.value = '');
    document.querySelectorAll('#modalMat textarea').forEach(el => el.value = '');
    document.getElementById('tempBadge').innerHTML = '';
    new bootstrap.Modal(document.getElementById('modalMat')).show();
}

// Badge température
function checkTemp(val) {
    const badge = document.getElementById('tempBadge');
    const t = parseFloat(val);
    if (isNaN(t) || !val) { badge.innerHTML = ''; return; }
    if      (t >= 39.5) badge.innerHTML = '<span class="badge bg-danger">🚨 Hyperthermie sévère</span>';
    else if (t >= 38.5) badge.innerHTML = '<span class="badge bg-warning text-dark">⚠️ Fièvre élevée</span>';
    else if (t >= 37.5) badge.innerHTML = '<span class="badge" style="background:#fb923c;color:white;">🌡️ Fièvre</span>';
    else if (t < 36)    badge.innerHTML = '<span class="badge bg-info text-dark">❄️ Hypothermie</span>';
    else                badge.innerHTML = '<span class="badge bg-success">✓ Normal</span>';
}

// Filtre recherche
function filtrerPatientes(q) {
    q = q.toLowerCase();
    document.querySelectorAll('.pat-item').forEach(item => {
        item.style.display = (!q || item.dataset.search.includes(q)) ? '' : 'none';
    });
}
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
