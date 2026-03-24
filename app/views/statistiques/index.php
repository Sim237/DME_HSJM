<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div class="container-fluid">
    <div class="row">
        <?php require_once __DIR__ . '/../layouts/sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2"><i class="bi bi-graph-up"></i> Statistiques Avancées</h1>
                <button class="btn btn-outline-primary" onclick="exporterExcel()">
                    <i class="bi bi-file-excel"></i> Export Excel
                </button>
            </div>

            <!-- KPIs -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card bg-primary text-white">
                        <div class="card-body">
                            <h4 id="totalPatients">0</h4>
                            <p class="mb-0">Patients</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-success text-white">
                        <div class="card-body">
                            <h4 id="caTotal">0 FCFA</h4>
                            <p class="mb-0">CA Total</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-warning text-white">
                        <div class="card-body">
                            <h4 id="stockAlertes">0</h4>
                            <p class="mb-0">Alertes Stock</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-info text-white">
                        <div class="card-body">
                            <h4 id="consultationsMois">0</h4>
                            <p class="mb-0">Consultations/Mois</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Graphiques -->
            <div class="row">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header">
                            <h5>Chiffre d'Affaires Mensuel</h5>
                        </div>
                        <div class="card-body">
                            <canvas id="caChart" height="100"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header">
                            <h5>Top Services</h5>
                        </div>
                        <div class="card-body">
                            <canvas id="servicesChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Charger les données et créer les graphiques
    fetch(`${BASE_URL}statistiques/donnees`)
        .then(response => response.json())
        .then(data => {
            // Mettre à jour les KPIs
            document.getElementById('totalPatients').textContent = data.kpis?.total_patients || 0;
            document.getElementById('caTotal').textContent = (data.kpis?.ca_total || 0) + ' FCFA';
            document.getElementById('stockAlertes').textContent = data.kpis?.stock_alertes || 0;
            document.getElementById('consultationsMois').textContent = data.kpis?.consultations_mois || 0;
            
            // Créer les graphiques
            if (data.ca_mensuel) {
                new Chart(document.getElementById('caChart'), {
                    type: 'line',
                    data: {
                        labels: data.ca_mensuel.map(d => `${d.mois}/${d.annee}`),
                        datasets: [{
                            label: 'CA',
                            data: data.ca_mensuel.map(d => d.total),
                            borderColor: '#007bff',
                            tension: 0.4
                        }]
                    }
                });
            }
            
            if (data.top_services) {
                new Chart(document.getElementById('servicesChart'), {
                    type: 'doughnut',
                    data: {
                        labels: data.top_services.map(s => s.libelle),
                        datasets: [{
                            data: data.top_services.map(s => s.total),
                            backgroundColor: ['#007bff', '#28a745', '#ffc107', '#dc3545', '#6f42c1']
                        }]
                    }
                });
            }
        });
});

function exporterExcel() {
    window.open(`${BASE_URL}statistiques/export-excel`, '_blank');
}
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>