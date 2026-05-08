<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<style>
    .sidebar { display: none !important; }
    main { margin-left: 0 !important; width: 100% !important; background: #f1f5f9 !important; min-height: 100vh; }

    .surv-header { background: linear-gradient(135deg, #1a4a8e, #2563eb); color: white; padding: 20px 30px; }
    .prio-badge-lg { display: inline-block; padding: 5px 18px; border-radius: 30px; font-weight: 800; font-size: 0.85rem; }

    .vital-card { background: white; border-radius: 16px; padding: 20px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
    .vital-value { font-size: 2rem; font-weight: 800; display: block; line-height: 1; }
    .vital-label { font-size: 0.68rem; text-transform: uppercase; color: #94a3b8; font-weight: 700; }

    .timeline-item { padding: 12px 16px; background: white; border-radius: 12px; margin-bottom: 10px;
        border-left: 4px solid #3b82f6; box-shadow: 0 2px 8px rgba(0,0,0,0.04); }

    .vitals-form { background: white; border-radius: 16px; padding: 24px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
    .vitals-form .form-control { background: #f8fafc; border: 2px solid #e2e8f0; border-radius: 10px; font-weight: 600; }
    .vitals-form .form-control:focus { border-color: #3b82f6; background: white; box-shadow: none; }
</style>

<main>
    <!-- HEADER -->
    <div class="surv-header d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center gap-3">
            <a href="<?= BASE_URL ?>urgences" class="btn btn-light btn-sm rounded-circle p-2">
                <i class="bi bi-arrow-left"></i>
            </a>
            <div>
                <h4 class="mb-0 fw-bold">
                    <?= htmlspecialchars($adm['nom'] . ' ' . $adm['prenom']) ?>
                    <span class="fs-6 fw-normal opacity-75 ms-2">
                        <?= !empty($adm['date_naissance']) ? date_diff(date_create($adm['date_naissance']), date_create('now'))->y . ' ans' : '' ?>
                    </span>
                </h4>
                <small class="opacity-75">
                    <?= htmlspecialchars($adm['dossier_numero'] ?? '') ?>
                    &bull; <?= htmlspecialchars($adm['motif_plainte'] ?? $adm['motif_admission'] ?? 'Motif non renseigné') ?>
                </small>
            </div>
        </div>
        <div class="d-flex align-items-center gap-3">
            <?php
            $n      = (int)($adm['niveau_triage'] ?? 3);
            $colors = ['1'=>'#fee2e2','2'=>'#ffedd5','3'=>'#fef9c3','4'=>'#dcfce7','5'=>'#dbeafe'];
            $texts  = ['1'=>'#dc2626','2'=>'#ea580c','3'=>'#ca8a04','4'=>'#16a34a','5'=>'#2563eb'];
            $labels = ['1'=>'P1-VITAL','2'=>'P2-URGENT','3'=>'P3-STABLE','4'=>'P4-MINEUR','5'=>'P5-SURV'];
            ?>
            <span class="prio-badge-lg" style="background:<?= $colors[$n] ?? '#fef9c3' ?>;color:<?= $texts[$n] ?? '#ca8a04' ?>;">
                <?= $labels[$n] ?? 'P3-STABLE' ?>
            </span>
            <div class="text-end">
                <span class="d-block fw-bold fs-5" id="live-clock">--:--:--</span>
                <small class="opacity-75"><?= date('d/m/Y') ?></small>
            </div>
            <a href="<?= BASE_URL ?>urgences/triage/<?= $adm['id'] ?>" class="btn btn-warning btn-sm rounded-pill">
                <i class="bi bi-heart-pulse me-1"></i>Retriage
            </a>
            <a href="<?= BASE_URL ?>consultation/formulaire?patient_id=<?= $adm['patient_id'] ?>&type=EXTERNE&etape=1"
               class="btn btn-light btn-sm rounded-pill">
                <i class="bi bi-clipboard-pulse me-1"></i>Consultation
            </a>
        </div>
    </div>

    <div class="container-fluid p-4">
        <div class="row g-4">

            <!-- COLONNE GAUCHE : Constantes + Formulaire nouveau relevé -->
            <div class="col-lg-8">

                <!-- Dernières constantes -->
                <h6 class="fw-bold text-uppercase text-muted mb-3" style="font-size:.7rem;letter-spacing:1px;">
                    <i class="bi bi-activity me-2"></i>Constantes actuelles (dernier relevé)
                </h6>
                <?php
                $last = !empty($historique) ? $historique[0] : [];
                $showVal = fn($v, $unit='') => !empty($v) ? $v . $unit : '<span class="text-muted">--</span>';
                ?>
                <div class="row g-3 mb-4">
                    <div class="col-6 col-md-3">
                        <div class="vital-card text-center">
                            <span class="vital-value text-danger"><?= $last['tension_sys'] ?? '--' ?>/<?= $last['tension_dia'] ?? '--' ?></span>
                            <span class="vital-label">Tension (mmHg)</span>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="vital-card text-center">
                            <span class="vital-value text-primary"><?= $last['pouls'] ?? '--' ?></span>
                            <span class="vital-label">FC (BPM)</span>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="vital-card text-center">
                            <span class="vital-value text-success"><?= $last['spo2'] ? $last['spo2'] . '%' : '--' ?></span>
                            <span class="vital-label">SpO2</span>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="vital-card text-center">
                            <span class="vital-value text-warning"><?= $last['temperature'] ? $last['temperature'] . '°' : '--' ?></span>
                            <span class="vital-label">Température</span>
                        </div>
                    </div>
                </div>

                <!-- Graphique historique -->
                <?php if (count($historique) >= 2): ?>
                <div class="card border-0 shadow-sm rounded-4 mb-4">
                    <div class="card-body p-4">
                        <h6 class="fw-bold mb-3"><i class="bi bi-graph-up me-2 text-primary"></i>Évolution des constantes</h6>
                        <canvas id="vitalChart" style="max-height:220px;"></canvas>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Formulaire nouveau relevé de constantes -->
                <div class="vitals-form">
                    <h6 class="fw-bold mb-4"><i class="bi bi-plus-circle-fill text-success me-2"></i>Fiche de Paramètres — Nouveau relevé</h6>
                    <form id="formParametres">
                        <input type="hidden" name="patient_id" value="<?= $adm['patient_id'] ?>">

                        <!-- Signes vitaux -->
                        <p class="fw-semibold text-primary mb-2" style="font-size:.72rem;letter-spacing:.06em;text-transform:uppercase;">Signes Vitaux</p>
                        <div class="row g-3 mb-3">
                            <div class="col-md-2 col-4">
                                <label class="form-label small fw-bold text-uppercase text-muted" style="font-size:.65rem;">Temp. °C</label>
                                <input type="number" step="0.1" min="34" max="42" name="temp" class="form-control" placeholder="37.0">
                            </div>
                            <div class="col-md-2 col-4">
                                <label class="form-label small fw-bold text-uppercase text-muted" style="font-size:.65rem;">TA Syst.</label>
                                <input type="number" min="50" max="300" name="sys" class="form-control" placeholder="120">
                            </div>
                            <div class="col-md-2 col-4">
                                <label class="form-label small fw-bold text-uppercase text-muted" style="font-size:.65rem;">TA Diast.</label>
                                <input type="number" min="30" max="200" name="dia" class="form-control" placeholder="80">
                            </div>
                            <div class="col-md-2 col-4">
                                <label class="form-label small fw-bold text-uppercase text-muted" style="font-size:.65rem;">Pouls (bpm)</label>
                                <input type="number" min="20" max="300" name="pouls" class="form-control" placeholder="80">
                            </div>
                            <div class="col-md-2 col-4">
                                <label class="form-label small fw-bold text-uppercase text-muted" style="font-size:.65rem;">SpO2 %</label>
                                <input type="number" min="50" max="100" name="spo2" class="form-control" placeholder="98">
                            </div>
                            <div class="col-md-2 col-4">
                                <label class="form-label small fw-bold text-uppercase text-muted" style="font-size:.65rem;">FR (/min)</label>
                                <input type="number" min="5" max="60" name="freq_resp" class="form-control" placeholder="16">
                            </div>
                        </div>

                        <hr class="my-3">

                        <!-- Biologie au lit -->
                        <p class="fw-semibold text-primary mb-2" style="font-size:.72rem;letter-spacing:.06em;text-transform:uppercase;">Biologie au Lit</p>
                        <div class="row g-3 mb-3">
                            <div class="col-md-3 col-6">
                                <label class="form-label small fw-bold text-uppercase text-muted" style="font-size:.65rem;">Glycémie (g/L)</label>
                                <input type="number" step="0.01" min="0" max="30" name="glycemie" class="form-control" placeholder="0.90">
                            </div>
                            <div class="col-md-3 col-6">
                                <label class="form-label small fw-bold text-uppercase text-muted" style="font-size:.65rem;">Diurèse (mL/24h)</label>
                                <input type="number" min="0" max="10000" name="diurese" class="form-control" placeholder="1500">
                            </div>
                        </div>

                        <hr class="my-3">

                        <!-- Oxygénothérapie -->
                        <p class="fw-semibold text-primary mb-2" style="font-size:.72rem;letter-spacing:.06em;text-transform:uppercase;">Oxygénothérapie</p>
                        <div class="row g-3 mb-3 align-items-center">
                            <div class="col-auto">
                                <div class="form-check form-switch mt-1">
                                    <input class="form-check-input" type="checkbox" id="toggleO2Surv" name="sous_oxygene" value="1"
                                           onchange="document.getElementById('champO2Surv').style.display=this.checked?'block':'none'; if(!this.checked) document.getElementById('debitO2Surv').value='';">
                                    <label class="form-check-label fw-semibold" for="toggleO2Surv">Sous oxygène</label>
                                </div>
                            </div>
                            <div class="col-md-3 col-6" id="champO2Surv" style="display:none;">
                                <label class="form-label small fw-bold text-uppercase text-muted" style="font-size:.65rem;">Débit O2 (L/min)</label>
                                <input type="number" step="0.5" min="0" max="15" name="debit_oxygene" id="debitO2Surv" class="form-control" placeholder="2">
                            </div>
                        </div>

                        <hr class="my-3">

                        <!-- Observations -->
                        <p class="fw-semibold text-primary mb-2" style="font-size:.72rem;letter-spacing:.06em;text-transform:uppercase;">Observations</p>
                        <textarea name="observations" class="form-control" rows="2" placeholder="État général, comportement, plaintes, remarques…"></textarea>

                        <div class="d-flex justify-content-end mt-3">
                            <button type="button" class="btn btn-success rounded-pill px-4 fw-bold" onclick="sauvegarderParametres()">
                                <i class="bi bi-check2 me-2"></i>Enregistrer la fiche
                            </button>
                        </div>
                    </form>
                    <div id="saveMsg" class="text-center mt-2" style="display:none;"></div>
                </div>
            </div>

            <!-- COLONNE DROITE : Timeline + Bilans -->
            <div class="col-lg-4">

                <!-- Bilans -->
                <?php if (!empty($bilans)): ?>
                <div class="mb-4">
                    <h6 class="fw-bold text-uppercase text-muted mb-3" style="font-size:.7rem;letter-spacing:1px;">
                        <i class="bi bi-flask-fill me-2 text-warning"></i>Bilans demandés
                    </h6>
                    <?php foreach ($bilans as $b):
                        $isDispo = ($b['statut'] === 'TERMINE');
                    ?>
                        <div class="timeline-item" style="border-left-color: <?= $isDispo ? '#22c55e' : '#f97316' ?>;">
                            <div class="d-flex justify-content-between">
                                <span class="fw-bold small"><?= htmlspecialchars($b['examens_noms'] ?? 'Bilan') ?></span>
                                <span class="badge <?= $isDispo ? 'bg-success' : 'bg-warning' ?>">
                                    <?= $isDispo ? 'Disponible' : 'En cours' ?>
                                </span>
                            </div>
                            <small class="text-muted"><?= date('H:i', strtotime($b['date_creation'])) ?></small>
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <!-- Historique constantes -->
                <h6 class="fw-bold text-uppercase text-muted mb-3" style="font-size:.7rem;letter-spacing:1px;">
                    <i class="bi bi-clock-history me-2"></i>Historique des relevés
                </h6>
                <?php if (empty($historique)): ?>
                    <p class="text-muted small">Aucun relevé enregistré.</p>
                <?php else: ?>
                    <?php foreach ($historique as $h): ?>
                        <div class="timeline-item">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <small class="fw-bold text-muted"><?= date('d/m H:i', strtotime($h['date_mesure'])) ?></small>
                            </div>
                            <div class="d-flex gap-3" style="font-size:.82rem;">
                                <?php if (!empty($h['tension_sys'])): ?>
                                    <span><strong><?= $h['tension_sys'] ?>/<?= $h['tension_dia'] ?></strong> <small class="text-muted">mmHg</small></span>
                                <?php endif; ?>
                                <?php if (!empty($h['pouls'])): ?>
                                    <span><strong class="text-danger"><?= $h['pouls'] ?></strong> <small class="text-muted">FC</small></span>
                                <?php endif; ?>
                                <?php if (!empty($h['spo2'])): ?>
                                    <span><strong class="text-success"><?= $h['spo2'] ?>%</strong> <small class="text-muted">SpO2</small></span>
                                <?php endif; ?>
                                <?php if (!empty($h['temperature'])): ?>
                                    <span><strong class="text-warning"><?= $h['temperature'] ?>°</strong></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>

                <!-- Orientation rapide -->
                <div class="card border-0 shadow-sm rounded-4 mt-4">
                    <div class="card-body p-4">
                        <h6 class="fw-bold mb-3"><i class="bi bi-arrow-right-circle-fill text-primary me-2"></i>Orientation</h6>
                        <form action="<?= BASE_URL ?>urgences/transferer" method="POST">
                            <input type="hidden" name="admission_id" value="<?= $adm['id'] ?>">
                            <div class="mb-3">
                                <select name="decision" class="form-select" onchange="toggleServiceSurv(this.value)">
                                    <option value="SORTIE">Sortie / Retour domicile</option>
                                    <option value="HOSPITALISATION">Hospitalisation</option>
                                    <option value="CONSULTER">Référer consultation</option>
                                </select>
                            </div>
                            <div id="serviceSurvBlock" style="display:none;" class="mb-3">
                                <select name="service_id" class="form-select">
                                    <?php foreach ($services as $svc): ?>
                                        <option value="<?= $svc['id'] ?>"><?= htmlspecialchars($svc['nom']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary w-100 rounded-pill fw-bold">
                                Valider l'orientation
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<script src="<?= BASE_URL ?>public/js/chart.umd.min.js"></script>
<script>
// Horloge
setInterval(() => {
    document.getElementById('live-clock').textContent = new Date().toLocaleTimeString('fr-FR');
}, 1000);
document.getElementById('live-clock').textContent = new Date().toLocaleTimeString('fr-FR');

// Graphique historique
<?php if (count($historique) >= 2):
    $histRev = array_reverse($historique);
    $labels = array_map(fn($h) => date('H:i', strtotime($h['date_mesure'])), $histRev);
    $pouls  = array_column($histRev, 'pouls');
    $ta     = array_column($histRev, 'tension_sys');
    $spo2   = array_column($histRev, 'spo2');
?>
const ctx = document.getElementById('vitalChart').getContext('2d');
new Chart(ctx, {
    type: 'line',
    data: {
        labels: <?= json_encode($labels) ?>,
        datasets: [
            { label: 'FC (BPM)', data: <?= json_encode($pouls) ?>, borderColor: '#3b82f6', backgroundColor: '#3b82f620', tension: 0.4, fill: false },
            { label: 'TA Syst', data: <?= json_encode($ta) ?>, borderColor: '#ef4444', backgroundColor: '#ef444420', tension: 0.4, fill: false },
            { label: 'SpO2', data: <?= json_encode($spo2) ?>, borderColor: '#22c55e', backgroundColor: '#22c55e20', tension: 0.4, fill: false },
        ]
    },
    options: {
        responsive: true, maintainAspectRatio: false,
        plugins: { legend: { position: 'bottom', labels: { font: { size: 11 }, padding: 15 } } },
        scales: { y: { beginAtZero: false } }
    }
});
<?php endif; ?>

// Sauvegarder constantes via AJAX
function sauvegarderParametres() {
    const form = document.getElementById('formParametres');
    const data = new FormData(form);

    fetch('<?= BASE_URL ?>urgences/save-parametres', { method: 'POST', body: data })
        .then(r => r.json())
        .then(res => {
            const msg = document.getElementById('saveMsg');
            msg.style.display = 'block';
            if (res.success) {
                msg.innerHTML = '<span class="text-success fw-bold"><i class="bi bi-check-circle-fill me-1"></i>Enregistré à ' + res.time + '</span>';
                form.reset();
                setTimeout(() => location.reload(), 1500);
            } else {
                msg.innerHTML = '<span class="text-danger">Erreur : ' + (res.message || 'Inconnue') + '</span>';
            }
        })
        .catch(() => {
            document.getElementById('saveMsg').innerHTML = '<span class="text-danger">Erreur réseau.</span>';
        });
}

function toggleServiceSurv(val) {
    document.getElementById('serviceSurvBlock').style.display = (val !== 'SORTIE') ? 'block' : 'none';
}
</script>
<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
