<!-- Widget Alertes Médicales -->
<div class="alertes-medicales-widget" id="alertesMedicales" style="display: none;">
    <div class="alert-container">
        <div class="alert-header">
            <h6><i class="bi bi-exclamation-triangle"></i> Alertes Médicales</h6>
            <button class="btn-close-alerts" onclick="fermerAlertes()">×</button>
        </div>
        <div class="alert-content" id="alerteContent"></div>
        <div class="alert-actions">
            <button class="btn btn-sm btn-outline-secondary" onclick="ignorerAlertes()">Ignorer</button>
            <button class="btn btn-sm btn-primary" onclick="confirmerPrescription()">Confirmer malgré tout</button>
        </div>
    </div>
</div>

<!-- Modal Allergies Patient -->
<div class="modal fade" id="allergiesModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Allergies du Patient</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="allergiesList"></div>
                
                <hr>
                <h6>Ajouter une allergie</h6>
                <form id="allergieForm">
                    <div class="row">
                        <div class="col-md-6">
                            <select class="form-select form-select-sm" name="type_allergie">
                                <option value="medicament">Médicament</option>
                                <option value="alimentaire">Alimentaire</option>
                                <option value="environnementale">Environnementale</option>
                                <option value="autre">Autre</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <select class="form-select form-select-sm" name="gravite">
                                <option value="legere">Légère</option>
                                <option value="moderee">Modérée</option>
                                <option value="severe">Sévère</option>
                                <option value="anaphylaxie">Anaphylaxie</option>
                            </select>
                        </div>
                    </div>
                    <div class="mt-2">
                        <input type="text" class="form-control form-control-sm" name="allergene" placeholder="Allergène" required>
                    </div>
                    <div class="mt-2">
                        <textarea class="form-control form-control-sm" name="symptomes" placeholder="Symptômes observés" rows="2"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm mt-2">Ajouter</button>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
.alertes-medicales-widget {
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    z-index: 1060;
    width: 90%;
    max-width: 600px;
}

.alert-container {
    background: white;
    border-radius: 12px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.3);
    border: 3px solid #dc3545;
}

.alert-header {
    background: #dc3545;
    color: white;
    padding: 1rem;
    border-radius: 12px 12px 0 0;
    display: flex;
    justify-content: between;
    align-items: center;
}

.btn-close-alerts {
    background: none;
    border: none;
    color: white;
    font-size: 1.5rem;
    cursor: pointer;
    margin-left: auto;
}

.alert-content {
    padding: 1rem;
    max-height: 400px;
    overflow-y: auto;
}

.alert-item {
    padding: 0.75rem;
    margin-bottom: 0.5rem;
    border-radius: 8px;
    border-left: 4px solid;
}

.alert-critique {
    background: #f8d7da;
    border-left-color: #dc3545;
}

.alert-danger {
    background: #fff3cd;
    border-left-color: #ffc107;
}

.alert-attention {
    background: #d1ecf1;
    border-left-color: #17a2b8;
}

.alert-actions {
    padding: 1rem;
    border-top: 1px solid #dee2e6;
    display: flex;
    gap: 0.5rem;
    justify-content: flex-end;
}

.allergie-item {
    padding: 0.5rem;
    background: #f8f9fa;
    border-radius: 6px;
    margin-bottom: 0.5rem;
    border-left: 3px solid;
}

.allergie-severe { border-left-color: #dc3545; }
.allergie-moderee { border-left-color: #ffc107; }
.allergie-legere { border-left-color: #28a745; }
.allergie-anaphylaxie { border-left-color: #6f42c1; }
</style>

<script>
class AlertesMedicales {
    constructor() {
        this.currentPatientId = null;
        this.currentMedicaments = [];
    }
    
    async verifierPrescription(patientId, medicaments) {
        this.currentPatientId = patientId;
        this.currentMedicaments = medicaments;
        
        try {
            const response = await fetch(`${BASE_URL}alertes/verifier`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    patient_id: patientId,
                    medicaments: medicaments
                })
            });
            
            const alertes = await response.json();
            
            if (alertes.length > 0) {
                this.afficherAlertes(alertes);
                return false; // Bloquer la prescription
            }
            
            return true; // Pas d'alertes, continuer
            
        } catch (error) {
            console.error('Erreur vérification alertes:', error);
            return true; // En cas d'erreur, ne pas bloquer
        }
    }
    
    afficherAlertes(alertes) {
        const widget = document.getElementById('alertesMedicales');
        const content = document.getElementById('alerteContent');
        
        content.innerHTML = alertes.map(alerte => `
            <div class="alert-item alert-${alerte.niveau}">
                <div class="fw-bold">${alerte.titre}</div>
                <div class="small">${alerte.message}</div>
                ${alerte.medicament ? `<div class="small text-muted mt-1">Médicament: ${alerte.medicament}</div>` : ''}
                ${alerte.recommandation ? `<div class="small text-info mt-1">Recommandation: ${alerte.recommandation}</div>` : ''}
            </div>
        `).join('');
        
        widget.style.display = 'block';
        
        // Son d'alerte
        this.jouerSonAlerte();
    }
    
    async chargerAllergies(patientId) {
        try {
            const response = await fetch(`${BASE_URL}patients/${patientId}/allergies`);
            const allergies = await response.json();
            
            const list = document.getElementById('allergiesList');
            if (allergies.length === 0) {
                list.innerHTML = '<p class="text-muted">Aucune allergie connue</p>';
            } else {
                list.innerHTML = allergies.map(allergie => `
                    <div class="allergie-item allergie-${allergie.gravite}">
                        <div class="fw-bold">${allergie.allergene}</div>
                        <div class="small">Type: ${allergie.type_allergie} | Gravité: ${allergie.gravite}</div>
                        ${allergie.symptomes ? `<div class="small text-muted">${allergie.symptomes}</div>` : ''}
                    </div>
                `).join('');
            }
            
        } catch (error) {
            console.error('Erreur chargement allergies:', error);
        }
    }
    
    jouerSonAlerte() {
        // Son d'alerte critique
        const audioContext = new (window.AudioContext || window.webkitAudioContext)();
        const oscillator = audioContext.createOscillator();
        const gainNode = audioContext.createGain();
        
        oscillator.connect(gainNode);
        gainNode.connect(audioContext.destination);
        
        oscillator.frequency.setValueAtTime(800, audioContext.currentTime);
        oscillator.frequency.setValueAtTime(600, audioContext.currentTime + 0.1);
        oscillator.frequency.setValueAtTime(800, audioContext.currentTime + 0.2);
        
        gainNode.gain.setValueAtTime(0.3, audioContext.currentTime);
        gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.3);
        
        oscillator.start(audioContext.currentTime);
        oscillator.stop(audioContext.currentTime + 0.3);
    }
}

// Instance globale
const alertesMedicales = new AlertesMedicales();

function fermerAlertes() {
    document.getElementById('alertesMedicales').style.display = 'none';
}

function ignorerAlertes() {
    // Marquer les alertes comme ignorées
    fermerAlertes();
}

function confirmerPrescription() {
    // Confirmer malgré les alertes
    fermerAlertes();
    // Continuer le processus de prescription
    if (window.confirmerPrescriptionCallback) {
        window.confirmerPrescriptionCallback();
    }
}

// Formulaire ajout allergie
document.getElementById('allergieForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    formData.append('patient_id', alertesMedicales.currentPatientId);
    
    try {
        const response = await fetch(`${BASE_URL}patients/allergies/add`, {
            method: 'POST',
            body: formData
        });
        
        if (response.ok) {
            this.reset();
            alertesMedicales.chargerAllergies(alertesMedicales.currentPatientId);
        }
        
    } catch (error) {
        console.error('Erreur ajout allergie:', error);
    }
});
</script>