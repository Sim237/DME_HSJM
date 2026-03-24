<?php
// Modal Evolution - Graphs constantes over time (reuse patients/evolution.php logic)
?>
<div class="modal fade" id="modalEvolution" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header bg-primary text-white border-0 p-4">
                <h5 class="modal-title fw-bold"><i class="bi bi-graph-up me-2"></i>Évolution Clinique</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <div class="row g-4 p-4">
                    <div class="col-md-6">
                        <div class="card border-0 shadow-sm p-4">
                            <h6 class="fw-bold mb-3 text-danger">Température</h6>
                            <canvas id="chartTemp"></canvas>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card border-0 shadow-sm p-4">
                            <h6 class="fw-bold mb-3 text-primary">Tension Artérielle</h6>
                            <canvas id="chartTension"></canvas>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="card border-0 shadow-sm p-4">
                            <h6 class="fw-bold mb-3 text-success">Pouls & SpO2</h6>
                            <canvas id="chartVitals"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
let evolutionData = {};
let patientName = '';

function openEvolution(patient_id, name) {
    patientName = name;
    document.querySelector('#modalEvolution .modal-title').innerHTML = `Évolution: ${name}`;

    fetch(`${BASE_URL}dashboard/getEvolutionData?patient_id=${patient_id}`)
    .then(res => res.json())
    .then(data => {
        evolutionData = data.reverse(); // Chrono order
        renderEvolutionCharts();
        new bootstrap.Modal(document.getElementById('modalEvolution')).show();
    });
}

function renderEvolutionCharts() {
    const labels = evolutionData.map(d => new Date(d.date_mesure).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'}));

    // Temp chart
    new Chart(document.getElementById('chartTemp'), {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Temp (°C)',
                data: evolutionData.map(d => d.temperature),
                borderColor: '#dc3545',
                backgroundColor: 'rgba(220,53,69,0.1)',
                tension: 0.4,
                fill: true
            }]
        },
        options: { responsive: true, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: false } } }
    });

    // Tension
    new Chart(document.getElementById('chartTension'), {
        type: 'line',
        data: {
            labels: labels,
            datasets: [
                { label: 'SYS', data: evolutionData.map(d => d.tension_sys), borderColor: '#0d6efd', tension: 0.3 },
                { label: 'DIA', data: evolutionData.map(d => d.tension_dia), borderColor: '#6c757d', tension: 0.3 }
            ]
        },
        options: { responsive: true, scales: { y: { beginAtZero: false } } }
    });

    // Vitals
    new Chart(document.getElementById('chartVitals'), {
        type: 'line',
        data: {
            labels: labels,
            datasets: [
                { label: 'Pouls', data: evolutionData.map(d => d.pouls), borderColor: '#198754', yAxisID: 'y' },
                { label: 'SpO2 %', data: evolutionData.map(d => d.spo2), borderColor: '#fd7e14', yAxisID: 'y1' }
            ]
        },
        options: {
            responsive: true,
            scales: {
                y: { type: 'linear', display: true, position: 'left', beginAtZero: false },
                y1: { type: 'linear', display: true, position: 'right', beginAtZero: false, max: 100 }
            }
        }
    });
}
</script>
