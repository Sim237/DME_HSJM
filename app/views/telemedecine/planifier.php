<?php include __DIR__ . '/../layouts/header.php'; ?>

<div class="main-content">
    <div class="container-fluid p-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="mb-1"><i class="fas fa-calendar-plus text-primary me-2"></i>Planifier Consultation</h2>
                <p class="text-muted mb-0">Nouvelle consultation télémédecine</p>
            </div>
            <a href="<?= BASE_URL ?>telemedecine" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-2"></i>Retour
            </a>
        </div>

        <div class="row">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-video me-2"></i>Nouvelle Consultation Télémédecine</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="<?= BASE_URL ?>telemedecine/planifier">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Patient *</label>
                                        <select name="patient_id" class="form-select" required>
                                            <option value="">Sélectionner un patient</option>
                                            <option value="1">DUPONT Marie</option>
                                            <option value="2">MARTIN Jean</option>
                                            <option value="3">BERNARD Sophie</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Type de consultation *</label>
                                        <select name="type" class="form-select" required>
                                            <option value="video">Vidéo</option>
                                            <option value="audio">Audio seulement</option>
                                            <option value="chat">Chat textuel</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Date *</label>
                                        <input type="date" name="date_consultation_date" class="form-control" 
                                               value="<?= date('Y-m-d') ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Heure *</label>
                                        <input type="time" name="date_consultation_time" class="form-control" 
                                               value="<?= date('H:i') ?>" required>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Motif de consultation *</label>
                                <textarea name="motif" class="form-control" rows="3" required 
                                          placeholder="Décrivez le motif de la consultation..."></textarea>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Durée (minutes)</label>
                                        <select name="duree_minutes" class="form-select">
                                            <option value="15">15 minutes</option>
                                            <option value="30" selected>30 minutes</option>
                                            <option value="45">45 minutes</option>
                                            <option value="60">1 heure</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Priorité</label>
                                        <select name="priorite" class="form-select">
                                            <option value="normale">Normale</option>
                                            <option value="urgente">Urgente</option>
                                            <option value="programmee">Programmée</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="envoyer_notification" 
                                           id="notification" checked>
                                    <label class="form-check-label" for="notification">
                                        Envoyer une notification au patient
                                    </label>
                                </div>
                            </div>

                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Planifier
                                </button>
                                <button type="button" class="btn btn-success" onclick="planifierEtDemarrer()">
                                    <i class="fas fa-play me-2"></i>Planifier et Démarrer
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <!-- Informations patient -->
                <div class="card mb-3">
                    <div class="card-header">
                        <h6><i class="fas fa-user me-2"></i>Informations Patient</h6>
                    </div>
                    <div class="card-body" id="patient-info">
                        <p class="text-muted">Sélectionnez un patient pour voir ses informations</p>
                    </div>
                </div>

                <!-- Consultations récentes -->
                <div class="card">
                    <div class="card-header">
                        <h6><i class="fas fa-history me-2"></i>Consultations Récentes</h6>
                    </div>
                    <div class="card-body">
                        <div class="list-group list-group-flush">
                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <small class="text-muted">14/01/2024</small><br>
                                    <strong>Consultation générale</strong>
                                </div>
                                <span class="badge bg-success">Terminée</span>
                            </div>
                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <small class="text-muted">10/01/2024</small><br>
                                    <strong>Suivi traitement</strong>
                                </div>
                                <span class="badge bg-success">Terminée</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Combiner date et heure avant soumission
document.querySelector('form').addEventListener('submit', function(e) {
    const date = document.querySelector('input[name="date_consultation_date"]').value;
    const time = document.querySelector('input[name="date_consultation_time"]').value;
    
    if (date && time) {
        // Créer un champ caché avec la datetime complète
        const datetime = date + ' ' + time + ':00';
        const hiddenInput = document.createElement('input');
        hiddenInput.type = 'hidden';
        hiddenInput.name = 'date_consultation';
        hiddenInput.value = datetime;
        this.appendChild(hiddenInput);
    }
});

// Planifier et démarrer immédiatement
function planifierEtDemarrer() {
    const form = document.querySelector('form');
    const hiddenInput = document.createElement('input');
    hiddenInput.type = 'hidden';
    hiddenInput.name = 'demarrer_maintenant';
    hiddenInput.value = '1';
    form.appendChild(hiddenInput);
    form.submit();
}

// Charger infos patient
document.querySelector('select[name="patient_id"]').addEventListener('change', function() {
    const patientId = this.value;
    if (patientId) {
        // Simuler le chargement des infos patient
        const infos = {
            '1': {nom: 'DUPONT Marie', age: '45 ans', tel: '06.12.34.56.78'},
            '2': {nom: 'MARTIN Jean', age: '62 ans', tel: '06.87.65.43.21'},
            '3': {nom: 'BERNARD Sophie', age: '38 ans', tel: '06.11.22.33.44'}
        };
        
        if (infos[patientId]) {
            document.getElementById('patient-info').innerHTML = `
                <p><strong>Nom:</strong> ${infos[patientId].nom}</p>
                <p><strong>Âge:</strong> ${infos[patientId].age}</p>
                <p><strong>Téléphone:</strong> ${infos[patientId].tel}</p>
                <p><strong>Dernière consultation:</strong> 14/01/2024</p>
            `;
        }
    } else {
        document.getElementById('patient-info').innerHTML = 
            '<p class="text-muted">Sélectionnez un patient pour voir ses informations</p>';
    }
});
</script>

<?php include __DIR__ . '/../layouts/sidebar.php'; ?>
<?php include __DIR__ . '/../layouts/footer.php'; ?>