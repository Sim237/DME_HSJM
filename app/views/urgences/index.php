<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<style>
    .app-wrapper { display: flex; width: 100%; min-height: 100vh; background-color: #f1f5f9; }
    .main-content { flex-grow: 1; padding: 2rem; }

    .emergency-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 25px; }

    /* Animation des Cartes Flip */
    .flip-card { background-color: transparent; width: 100%; height: 320px; perspective: 1000px; }
    .flip-card-inner { position: relative; width: 100%; height: 100%; transition: transform 0.6s; transform-style: preserve-3d; cursor: pointer; }
    .flip-card:hover .flip-card-inner { transform: rotateY(180deg); }
    .card-front, .card-back { position: absolute; width: 100%; height: 100%; -webkit-backface-visibility: hidden; backface-visibility: hidden; border-radius: 20px; padding: 20px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); }

    /* Style Recto */
    .card-front { background: white; border-left: 12px solid #cbd5e1; display: flex; flex-direction: column; }
    .priority-P1 .card-front { border-left-color: #ef4444; background: #fff5f5; }
    .priority-P2 .card-front { border-left-color: #f97316; }
    .priority-P3 .card-front { border-left-color: #eab308; }

    /* Style Verso (Graphiques) */
    .card-back { background: #1e293b; color: white; transform: rotateY(180deg); display: flex; flex-direction: column; }
    .chart-container { height: 70px; margin-bottom: 10px; }
    .chart-label { font-size: 0.65rem; text-transform: uppercase; color: #94a3b8; font-weight: bold; margin-bottom: 5px; }

    .pulse-danger { animation: pulse-red 1.5s infinite; }
    @keyframes pulse-red { 0% { opacity: 1; } 50% { opacity: 0.5; } 100% { opacity: 1; } }
</style>

<div class="app-wrapper">
    <?php require_once __DIR__ . '/../layouts/sidebar.php'; ?>

    <main class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="fw-bold text-dark mb-0">Pilotage des Urgences</h2>
                <div class="d-flex gap-2 mt-1">
                    <span class="badge bg-danger">VITAL: <?= $stats['P1'] ?></span>
                    <span class="badge bg-warning text-dark">ATTENTE MÉD: <?= $stats['waiting_med'] ?></span>
                </div>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-outline-danger rounded-pill" onclick="location.href='<?= BASE_URL ?>urgences/nouvelle-admission'">
                    <i class="bi bi-stack"></i> Aflux Massif
                </button>
                <button class="btn btn-primary rounded-pill px-4 shadow" data-bs-toggle="modal" data-bs-target="#modalFastAdmission">
                    <i class="bi bi-plus-lg"></i> Admission Rapide
                </button>
            </div>
        </div>

        <div class="emergency-grid">
            <?php foreach($admissions as $adm):
                $pClass = 'priority-'.substr($adm['niveau_priorite'], 0, 2);
            ?>
            <div class="flip-card <?= $pClass ?>">
                <div class="flip-card-inner">
                    <!-- RECTO : INFOS CLINIQUES -->
                    <div class="card-front">
                        <div class="d-flex justify-content-between mb-3">
                            <span class="badge bg-dark">BOX <?= $adm['box_id'] ?? 'ATTENTE' ?></span>
                            <small class="text-muted fw-bold"><?= date('H:i', strtotime($adm['date_entree'])) ?></small>
                        </div>
                        <h5 class="fw-bold mb-1 text-dark"><?= htmlspecialchars($adm['nom'].' '.$adm['prenom']) ?></h5>
                        <p class="small text-danger fw-bold mb-3"><?= htmlspecialchars($adm['motif_plainte'] ?? 'En attente de tri IAO...') ?></p>

                        <div class="row g-2 text-center bg-light rounded-3 p-2 mt-auto">
                            <div class="col-4 border-end"><small class="d-block text-muted">GCS</small><strong><?= $adm['gcs_total'] ?? '--' ?></strong></div>
                            <div class="col-4 border-end"><small class="d-block text-muted">Pouls</small><strong class="text-primary">85</strong></div>
                            <div class="col-4"><small class="d-block text-muted">SpO2</small><strong class="text-success">98%</strong></div>
                        </div>

                        <div class="mt-3 d-flex gap-2">
                            <a href="<?= BASE_URL ?>urgences/triage/<?= $adm['id'] ?>" class="btn btn-outline-dark btn-sm flex-grow-1 rounded-pill">Triage</a>
                            <a href="<?= BASE_URL ?>consultation/ouvrir/<?= $adm['patient_id'] ?>" class="btn btn-primary btn-sm flex-grow-1 rounded-pill">Dossier</a>
                        </div>
                    </div>

                    <!-- VERSO : GRAPHIQUES DE TENDANCE -->
                    <div class="card-back">
                        <h6 class="text-center mb-3 small fw-bold">SURVEILLANCE TENDANCES</h6>

                        <div class="chart-label">Pouls (BPM)</div>
                        <div class="chart-container"><canvas id="hr-<?= $adm['id'] ?>"></canvas></div>

                        <div class="chart-label">Tension Systolique</div>
                        <div class="chart-container"><canvas id="bp-<?= $adm['id'] ?>"></canvas></div>

                        <div class="text-center mt-auto">
                            <span class="badge bg-primary-subtle text-info small">Cliquer pour retourner</span>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </main>
</div>

<?php include __DIR__ . '/modal_admission.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    <?php foreach($admissions as $adm): ?>
        renderSparkline('hr-<?= $adm['id'] ?>', <?= json_encode(array_column($adm['vitals_history'] ?? [], 'pouls')) ?>, '#3b82f6');
        renderSparkline('bp-<?= $adm['id'] ?>', <?= json_encode(array_column($adm['vitals_history'] ?? [], 'tension_sys')) ?>, '#ef4444');
    <?php endforeach; ?>
});

function renderSparkline(id, data, color) {
    const ctx = document.getElementById(id);
    if(!ctx) return;
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: data.map((_, i) => i),
            datasets: [{ data: data, borderColor: color, borderWidth: 2, pointRadius: 0, fill: true, backgroundColor: color + '10', tension: 0.4 }]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: { x: { display: false }, y: { display: false } }
        }
    });
}
</script>
<?php require_once __DIR__ . '/../layouts/footer.php'; ?>