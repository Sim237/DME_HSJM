<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div class="app-wrapper" style="display: flex;">
    <?php require_once __DIR__ . '/../layouts/sidebar.php'; ?>

    <main class="main-content flex-grow-1 p-4">
        <div class="d-flex justify-content-between mb-4">
            <h3>Évolution Clinique : <?= $patient['nom'] ?></h3>
            <button class="btn btn-secondary btn-sm" onclick="history.back()">Retour</button>
        </div>

        <div class="row g-4">
            <!-- Courbe Température -->
            <div class="col-md-6">
                <div class="card border-0 shadow-sm p-3 rounded-4">
                    <h6 class="fw-bold"><i class="bi bi-thermometer-half text-danger"></i> Température (°C)</h6>
                    <canvas id="tempChart"></canvas>
                </div>
            </div>
            <!-- Courbe Tension -->
            <div class="col-md-6">
                <div class="card border-0 shadow-sm p-3 rounded-4">
                    <h6 class="fw-bold"><i class="bi bi-heart-pulse text-primary"></i> Tension Artérielle (mmHg)</h6>
                    <canvas id="tensionChart"></canvas>
                </div>
            </div>
        </div>
    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Logique Température
new Chart(document.getElementById('tempChart'), {
    type: 'line',
    data: {
        labels: <?= json_encode($labels_dates) ?>,
        datasets: [{
            label: 'Temp.',
            data: <?= json_encode($values_temp) ?>,
            borderColor: '#dc3545',
            fill: true,
            backgroundColor: 'rgba(220, 53, 69, 0.1)',
            tension: 0.4
        }]
    }
});

// Logique Tension
new Chart(document.getElementById('tensionChart'), {
    type: 'line',
    data: {
        labels: <?= json_encode($labels_dates) ?>,
        datasets: [
            { label: 'SYS', data: <?= json_encode($values_sys) ?>, borderColor: '#0d6efd', tension: 0.1 },
            { label: 'DIA', data: <?= json_encode($values_dia) ?>, borderColor: '#0dcaf0', tension: 0.1 }
        ]
    }
});
</script>