<style>
    .monitor-bg { background: #000; color: #fff; min-height: 100vh; font-family: 'Courier New', monospace; }
    .stat-box { border: 2px solid #333; padding: 20px; border-radius: 10px; height: 180px; position: relative; }

    /* Couleurs monitor */
    .color-bpm { color: #00ff00; } /* Vert */
    .color-spo2 { color: #00ffff; } /* Cyan */
    .color-ta { color: #ffff00; }  /* Jaune */
    .color-fr { color: #ff00ff; }  /* Magenta */

    .big-number { font-size: 5rem; font-weight: bold; line-height: 1; }
    .label-stat { font-size: 1.2rem; text-transform: uppercase; font-weight: bold; }
    .unit-stat { font-size: 1rem; opacity: 0.7; }

    /* Animation du coeur */
    .heart-beat { animation: beat .8s infinite; }
    @keyframes beat { 0% { opacity: 1; } 50% { opacity: 0.3; } 100% { opacity: 1; } }
</style>

<div class="monitor-bg p-4">
    <!-- Header Patient -->
    <div class="d-flex justify-content-between align-items-center mb-4 border-bottom border-secondary pb-3">
        <div>
            <h3 class="mb-0"><?= strtoupper($op['nom']) ?> <?= $op['prenom'] ?></h3>
            <span class="badge bg-secondary">SALLE : <?= $op['nom_salle'] ?></span>
        </div>
        <div class="text-end">
            <h4 id="liveClock">00:00:00</h4>
            <div class="small text-warning"><?= $op['diagnostique_op'] ?></div>
        </div>
    </div>

    <div class="row g-4">
        <!-- FREQUENCE CARDIAQUE -->
        <div class="col-md-4">
            <div class="stat-box color-bpm shadow">
                <span class="label-stat"><i class="bi bi-heart-fill heart-beat"></i> HR / BPM</span>
                <div class="d-flex align-items-center justify-content-center h-100">
                    <span class="big-number" id="val-bpm">75</span>
                </div>
            </div>
        </div>

        <!-- TENSION ARTERIELLE -->
        <div class="col-md-4">
            <div class="stat-box color-ta shadow">
                <span class="label-stat">NIBP / mmHg</span>
                <div class="d-flex align-items-center justify-content-center h-100">
                    <span class="big-number" id="val-ta">120/80</span>
                </div>
            </div>
        </div>

        <!-- SATURATION O2 -->
        <div class="col-md-4">
            <div class="stat-box color-spo2 shadow">
                <span class="label-stat">SpO2 / %</span>
                <div class="d-flex align-items-center justify-content-center h-100">
                    <span class="big-number" id="val-spo2">98</span>
                </div>
            </div>
        </div>

        <!-- GRAPHIQUE LIVE -->
        <div class="col-md-8">
            <div class="card bg-dark border-secondary">
                <div class="card-body">
                    <canvas id="liveWaveform" style="height: 300px; width: 100%;"></canvas>
                </div>
            </div>
        </div>

        <!-- SAISIE DES DONNEES (FORMULAIRE RAPIDE) -->
        <div class="col-md-4">
            <div class="card bg-secondary text-white border-0 shadow">
                <div class="card-body">
                    <h6>Relevé de constantes</h6>
                    <form id="formAddVitals">
                        <input type="hidden" name="prog_id" value="<?= $op['id'] ?>">
                        <div class="row g-2">
                            <div class="col-6">
                                <input type="number" name="bpm" class="form-control mb-2" placeholder="BPM">
                                <input type="text" name="ta" class="form-control" placeholder="TA (ex: 12/8)">
                            </div>
                            <div class="col-6">
                                <input type="number" name="spo2" class="form-control mb-2" placeholder="SpO2 %">
                                <input type="number" name="fr" class="form-control" placeholder="FR">
                            </div>
                        </div>
                        <button type="submit" class="btn btn-warning w-100 mt-3 fw-bold">ENREGISTRER</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Horloge temps réel
    setInterval(() => {
        document.getElementById('liveClock').innerText = new Date().toLocaleTimeString();
    }, 1000);

    // Simulation de graphique (Onde ECG simplifiée)
    const ctx = document.getElementById('liveWaveform').getContext('2d');
    let chart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: Array(50).fill(''),
            datasets: [{
                data: Array(50).fill(0),
                borderColor: '#00ff00',
                borderWidth: 2,
                pointRadius: 0,
                tension: 0.4
            }]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            scales: { y: { display: false }, x: { display: false } },
            plugins: { legend: { display: false } }
        }
    });

    // Faire bouger le graphique
    setInterval(() => {
        chart.data.datasets[0].data.shift();
        chart.data.datasets[0].data.push(Math.random() * 10);
        chart.update('none');
    }, 200);

    // Envoi des données réelles
    document.getElementById('formAddVitals').onsubmit = function(e) {
        e.preventDefault();
        const fd = new FormData(this);
        // Mise à jour visuelle immédiate des chiffres
        document.getElementById('val-bpm').innerText = fd.get('bpm');
        document.getElementById('val-ta').innerText = fd.get('ta');
        document.getElementById('val-spo2').innerText = fd.get('spo2');

        // Appel serveur (AJAX) pour enregistrer
        fetch('<?= BASE_URL ?>bloc/add-monitoring', { method: 'POST', body: fd });
        this.reset();
    };
</script>