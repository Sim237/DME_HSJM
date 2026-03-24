<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kiosque d'Accueil - DME Hospital</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-primary">

<div class="container-fluid vh-100 d-flex align-items-center justify-content-center">
    <div class="row w-100">
        <div class="col-md-8 mx-auto">
            <div class="card shadow-lg">
                <div class="card-header bg-white text-center py-4">
                    <h1 class="text-primary mb-0">
                        <i class="fas fa-hospital"></i> DME Hospital
                    </h1>
                    <h3 class="text-muted">Kiosque d'Accueil Automatique</h3>
                </div>
                
                <div class="card-body p-5">
                    <div id="welcomeScreen">
                        <div class="text-center mb-5">
                            <i class="fas fa-hand-paper text-primary" style="font-size: 5rem;"></i>
                            <h2 class="mt-3">Bienvenue !</h2>
                            <p class="lead">Effectuez votre check-in automatique</p>
                        </div>
                        
                        <div class="row g-4">
                            <div class="col-md-6">
                                <div class="card h-100 border-primary checkin-option" onclick="showCheckinForm()">
                                    <div class="card-body text-center">
                                        <i class="fas fa-user-check text-primary mb-3" style="font-size: 3rem;"></i>
                                        <h4>Check-in</h4>
                                        <p>J'ai un rendez-vous</p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="card h-100 border-info checkin-option" onclick="showUrgenceInfo()">
                                    <div class="card-body text-center">
                                        <i class="fas fa-ambulance text-danger mb-3" style="font-size: 3rem;"></i>
                                        <h4>Urgence</h4>
                                        <p>Besoin de soins immédiats</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Formulaire Check-in -->
                    <div id="checkinForm" style="display: none;">
                        <div class="text-center mb-4">
                            <h3><i class="fas fa-user-check"></i> Check-in</h3>
                            <p>Veuillez saisir vos informations</p>
                        </div>
                        
                        <form id="checkinFormData">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Nom</label>
                                    <input type="text" name="nom" class="form-control form-control-lg" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Prénom</label>
                                    <input type="text" name="prenom" class="form-control form-control-lg" required>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Numéro de dossier (optionnel)</label>
                                    <input type="text" name="dossier_numero" class="form-control form-control-lg" placeholder="P-2024-00001">
                                </div>
                            </div>
                            
                            <div class="d-grid gap-2 mt-4">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="fas fa-check"></i> Confirmer le Check-in
                                </button>
                                <button type="button" class="btn btn-outline-secondary" onclick="showWelcome()">
                                    <i class="fas fa-arrow-left"></i> Retour
                                </button>
                            </div>
                        </form>
                    </div>
                    
                    <!-- Résultat Check-in -->
                    <div id="checkinResult" style="display: none;">
                        <div class="text-center">
                            <i class="fas fa-check-circle text-success mb-3" style="font-size: 5rem;"></i>
                            <h2 class="text-success">Check-in réussi !</h2>
                            <div class="alert alert-info mt-4">
                                <h4>Votre numéro de file : <span id="numeroFile" class="badge bg-primary fs-3"></span></h4>
                                <p class="mb-0">Veuillez patienter, vous serez appelé(e) prochainement</p>
                            </div>
                            
                            <button class="btn btn-primary btn-lg mt-3" onclick="showWelcome()">
                                <i class="fas fa-home"></i> Nouveau Check-in
                            </button>
                        </div>
                    </div>
                    
                    <!-- Info Urgence -->
                    <div id="urgenceInfo" style="display: none;">
                        <div class="text-center">
                            <i class="fas fa-exclamation-triangle text-danger mb-3" style="font-size: 5rem;"></i>
                            <h2 class="text-danger">Urgence Médicale</h2>
                            <div class="alert alert-danger mt-4">
                                <h4>Rendez-vous immédiatement aux Urgences</h4>
                                <p class="mb-0">Bâtiment A - Rez-de-chaussée</p>
                                <p class="mb-0">Ou appelez le personnel au poste d'accueil</p>
                            </div>
                            
                            <button class="btn btn-primary btn-lg mt-3" onclick="showWelcome()">
                                <i class="fas fa-arrow-left"></i> Retour
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.checkin-option {
    cursor: pointer;
    transition: all 0.3s;
}

.checkin-option:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
}

.form-control-lg {
    font-size: 1.2rem;
    padding: 1rem;
}

.btn-lg {
    padding: 1rem 2rem;
    font-size: 1.2rem;
}
</style>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function showWelcome() {
    document.getElementById('welcomeScreen').style.display = 'block';
    document.getElementById('checkinForm').style.display = 'none';
    document.getElementById('checkinResult').style.display = 'none';
    document.getElementById('urgenceInfo').style.display = 'none';
}

function showCheckinForm() {
    document.getElementById('welcomeScreen').style.display = 'none';
    document.getElementById('checkinForm').style.display = 'block';
}

function showUrgenceInfo() {
    document.getElementById('welcomeScreen').style.display = 'none';
    document.getElementById('urgenceInfo').style.display = 'block';
}

document.getElementById('checkinFormData').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    fetch('<?= BASE_URL ?>kiosque/checkin', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById('numeroFile').textContent = data.numero;
            document.getElementById('checkinForm').style.display = 'none';
            document.getElementById('checkinResult').style.display = 'block';
        } else {
            alert('Erreur: ' + data.message);
        }
    })
    .catch(error => {
        alert('Erreur technique. Veuillez contacter l\'accueil.');
    });
});

// Auto-retour à l'écran d'accueil après 30 secondes d'inactivité
let inactivityTimer;
function resetTimer() {
    clearTimeout(inactivityTimer);
    inactivityTimer = setTimeout(showWelcome, 30000);
}

document.addEventListener('click', resetTimer);
document.addEventListener('keypress', resetTimer);
resetTimer();
</script>

</body>
</html>