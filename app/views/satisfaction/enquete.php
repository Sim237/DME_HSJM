<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enquête de Satisfaction - DME Hospital</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow">
                <div class="card-header bg-primary text-white text-center">
                    <h2><i class="fas fa-star"></i> Enquête de Satisfaction</h2>
                    <p class="mb-0">Votre avis nous aide à améliorer nos services</p>
                </div>
                
                <div class="card-body p-4">
                    <form method="POST" action="<?= BASE_URL ?>satisfaction/enquete">
                        <input type="hidden" name="patient_id" value="<?= $_GET['patient'] ?? '' ?>">
                        <input type="hidden" name="consultation_id" value="<?= $_GET['consultation'] ?? '' ?>">
                        
                        <!-- Note globale -->
                        <div class="mb-4">
                            <label class="form-label h5">Comment évaluez-vous votre expérience globale ?</label>
                            <div class="rating-stars" data-rating="note_globale">
                                <?php for($i = 1; $i <= 5; $i++): ?>
                                    <i class="fas fa-star star" data-value="<?= $i ?>"></i>
                                <?php endfor; ?>
                            </div>
                            <input type="hidden" name="note_globale" required>
                        </div>
                        
                        <!-- Note accueil -->
                        <div class="mb-4">
                            <label class="form-label h6">Qualité de l'accueil</label>
                            <div class="rating-stars" data-rating="note_accueil">
                                <?php for($i = 1; $i <= 5; $i++): ?>
                                    <i class="fas fa-star star" data-value="<?= $i ?>"></i>
                                <?php endfor; ?>
                            </div>
                            <input type="hidden" name="note_accueil" required>
                        </div>
                        
                        <!-- Note attente -->
                        <div class="mb-4">
                            <label class="form-label h6">Temps d'attente</label>
                            <div class="rating-stars" data-rating="note_attente">
                                <?php for($i = 1; $i <= 5; $i++): ?>
                                    <i class="fas fa-star star" data-value="<?= $i ?>"></i>
                                <?php endfor; ?>
                            </div>
                            <input type="hidden" name="note_attente" required>
                        </div>
                        
                        <!-- Note médecin -->
                        <div class="mb-4">
                            <label class="form-label h6">Qualité des soins médicaux</label>
                            <div class="rating-stars" data-rating="note_medecin">
                                <?php for($i = 1; $i <= 5; $i++): ?>
                                    <i class="fas fa-star star" data-value="<?= $i ?>"></i>
                                <?php endfor; ?>
                            </div>
                            <input type="hidden" name="note_medecin" required>
                        </div>
                        
                        <!-- Commentaires -->
                        <div class="mb-4">
                            <label class="form-label h6">Commentaires (optionnel)</label>
                            <textarea name="commentaires" class="form-control" rows="4" 
                                      placeholder="Partagez votre expérience, suggestions d'amélioration..."></textarea>
                        </div>
                        
                        <!-- Recommandation -->
                        <div class="mb-4">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="recommandation" id="recommandation">
                                <label class="form-check-label h6" for="recommandation">
                                    Je recommanderais DME Hospital à mes proches
                                </label>
                            </div>
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-paper-plane"></i> Envoyer mon évaluation
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.rating-stars {
    font-size: 2rem;
    margin: 10px 0;
}

.star {
    color: #ddd;
    cursor: pointer;
    transition: color 0.2s;
    margin-right: 5px;
}

.star:hover,
.star.active {
    color: #ffc107;
}

.star.active {
    color: #ff6b35;
}

.card {
    border: none;
    border-radius: 15px;
}

.form-label {
    color: #495057;
    font-weight: 600;
}
</style>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const ratingContainers = document.querySelectorAll('.rating-stars');
    
    ratingContainers.forEach(container => {
        const stars = container.querySelectorAll('.star');
        const inputName = container.dataset.rating;
        const hiddenInput = document.querySelector(`input[name="${inputName}"]`);
        
        stars.forEach((star, index) => {
            star.addEventListener('click', function() {
                const rating = index + 1;
                hiddenInput.value = rating;
                
                // Mettre à jour l'affichage des étoiles
                stars.forEach((s, i) => {
                    if (i < rating) {
                        s.classList.add('active');
                    } else {
                        s.classList.remove('active');
                    }
                });
            });
            
            star.addEventListener('mouseover', function() {
                stars.forEach((s, i) => {
                    if (i <= index) {
                        s.style.color = '#ffc107';
                    } else {
                        s.style.color = '#ddd';
                    }
                });
            });
        });
        
        container.addEventListener('mouseleave', function() {
            const currentRating = hiddenInput.value;
            stars.forEach((s, i) => {
                if (i < currentRating) {
                    s.style.color = '#ff6b35';
                } else {
                    s.style.color = '#ddd';
                }
            });
        });
    });
});
</script>

</body>
</html>