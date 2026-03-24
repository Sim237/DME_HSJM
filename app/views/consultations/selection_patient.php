<?php include __DIR__ . '/../layouts/header.php'; ?>

<div class="container-fluid">
    <div class="row">
        <?php include __DIR__ . '/../layouts/sidebar.php'; ?>
        
        <main class="col-md-10 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2"><i class="fas fa-stethoscope"></i> Nouvelle Consultation</h1>
            </div>

            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="card-title mb-4">Sélectionner un patient</h5>
                    
                    <div class="row mb-4">
                        <div class="col-md-8">
                            <div class="search-box">
                                <input type="text" 
                                       class="form-control form-control-lg" 
                                       id="searchPatient" 
                                       placeholder="Rechercher par nom, prénom ou numéro de dossier..."
                                       autocomplete="off">
                                <div id="searchResults" class="search-results"></div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <button class="btn btn-primary btn-lg w-100" onclick="showNewPatientModal()">
                                <i class="fas fa-user-plus"></i> Nouveau Patient
                            </button>
                        </div>
                    </div>

                    <div id="patientsList" class="patients-list">
                        <!-- Les résultats de recherche s'afficheront ici -->
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
document.getElementById('searchPatient').addEventListener('input', function(e) {
    const query = e.target.value;
    const resultsDiv = document.getElementById('patientsList');
    
    if (query.length < 2) {
        resultsDiv.innerHTML = '';
        return;
    }

    // Utilisation de l'URL correcte définie dans votre routeur
    // Assurez-vous que BASE_URL est défini en JS ou remplacez-le
    const baseUrl = "<?php echo BASE_URL; ?>"; 
    
    fetch(`${baseUrl}consultation/search-patients?q=${encodeURIComponent(query)}`)
        .then(response => response.json())
        .then(patients => {
            let html = '<div class="list-group">';
            
            if (patients.length === 0) {
                html += '<div class="list-group-item text-center">Aucun patient trouvé</div>';
            } else {
                patients.forEach(p => {
                    html += `
                        <a href="${baseUrl}consultation/dossier-patient/${p.id}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-1">${p.nom} ${p.prenom}</h6>
                                <small class="text-muted">Dossier: ${p.dossier_numero} | Né(e) le: ${p.date_naissance}</small>
                            </div>
                            <span class="badge bg-primary rounded-pill">Sélectionner</span>
                        </a>
                    `;
                });
            }
            html += '</div>';
            resultsDiv.innerHTML = html;
        })
        .catch(error => console.error('Erreur:', error));
});

function showNewPatientModal() {
    // Redirection vers la création de patient ou ouverture modale
    window.location.href = "<?php echo BASE_URL; ?>patients/nouveau";
}
</script>

<!-- Ajoutez ceci juste avant le footer -->
<script>
    // 1. Fonction appelée par votre bouton "Nouveau Patient"
    function showNewPatientModal() {
        // Redirection JavaScript vers la page de création que nous avons faite
        window.location.href = "<?php echo BASE_URL; ?>patients/nouveau";
    }

    // 2. Script pour la barre de recherche (Indispensable pour cette page)
    document.getElementById('searchPatient').addEventListener('input', function(e) {
        const query = e.target.value;
        const resultsDiv = document.getElementById('patientsList');
        
        // On vide les résultats si moins de 2 caractères
        if (query.length < 2) {
            resultsDiv.innerHTML = '';
            return;
        }

        // On affiche un indicateur de chargement
        resultsDiv.innerHTML = '<div class="text-center p-3"><div class="spinner-border text-primary" role="status"></div></div>';

        // Appel au serveur
        const baseUrl = "<?php echo BASE_URL; ?>"; 
        
        fetch(`${baseUrl}consultation/search-patients?q=${encodeURIComponent(query)}`)
            .then(response => response.json())
            .then(patients => {
                let html = '<div class="list-group mt-3">';
                
                if (patients.length === 0) {
                    html += '<div class="list-group-item text-center text-muted p-4">Aucun patient trouvé.</div>';
                } else {
                    patients.forEach(p => {
                        // On formate la date joliment
                        const dateNaiss = new Date(p.date_naissance).toLocaleDateString('fr-FR');
                        
                        html += `
                            <a href="${baseUrl}consultation/dossier-patient/${p.id}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center p-3 shadow-sm mb-2 border rounded">
                                <div>
                                    <h5 class="mb-1 text-primary fw-bold">${p.nom} ${p.prenom}</h5>
                                    <div class="text-muted small">
                                        <i class="fas fa-folder me-1"></i> Dossier: <strong>${p.dossier_numero}</strong> | 
                                        <i class="fas fa-birthday-cake me-1 ms-2"></i> ${dateNaiss} |
                                        <i class="fas fa-venus-mars me-1 ms-2"></i> ${p.sexe}
                                    </div>
                                </div>
                                <span class="btn btn-sm btn-primary">Sélectionner <i class="fas fa-chevron-right ms-1"></i></span>
                            </a>
                        `;
                    });
                }
                html += '</div>';
                resultsDiv.innerHTML = html;
            })
            .catch(error => {
                console.error('Erreur:', error);
                resultsDiv.innerHTML = '<div class="alert alert-danger mt-3">Une erreur est survenue lors de la recherche.</div>';
            });
    });
</script>

<?php include __DIR__ . '/../layouts/footer.php'; ?>

<?php include __DIR__ . '/../layouts/footer.php'; ?>