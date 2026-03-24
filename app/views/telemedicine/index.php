<?php
$title = "Télémédecine";
include __DIR__ . '/../layouts/header.php';
?>

<div class="main-content">
    <div class="telemedicine-header">
        <div class="header-content">
            <div class="header-icon">
                <i class="fas fa-video"></i>
            </div>
            <div class="header-text">
                <h1>Télémédecine</h1>
                <p>Consultations médicales à distance avec Google Meet</p>
            </div>
        </div>
        <div class="header-actions">
            <a href="<?php echo BASE_URL; ?>" class="back-btn">
                <i class="fas fa-arrow-left"></i> Retour au tableau de bord
            </a>
            <div class="stat-card">
                <i class="fas fa-users"></i>
                <span><?= count($patients) ?> Patients</span>
            </div>
        </div>
    </div>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success modern-alert">
            <i class="fas fa-check-circle"></i>
            <?= $_SESSION['success']; unset($_SESSION['success']); ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-error modern-alert">
            <i class="fas fa-exclamation-circle"></i>
            <?= $_SESSION['error']; unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['meeting_url'])): ?>
        <div class="meeting-success">
            <div class="success-icon">
                <i class="fas fa-video"></i>
            </div>
            <h3>Réunion créée avec succès !</h3>
            <div class="meeting-link">
                <input type="text" value="<?= htmlspecialchars($_GET['meeting_url']) ?>" readonly id="meetingUrl">
                <button onclick="copyMeetingUrl()" class="copy-btn">
                    <i class="fas fa-copy"></i>
                </button>
            </div>
            <a href="<?= htmlspecialchars($_GET['meeting_url']) ?>" target="_blank" class="join-meeting-btn">
                <i class="fas fa-external-link-alt"></i> Rejoindre la réunion
            </a>
        </div>
    <?php endif; ?>

    <?php if (!isset($_SESSION['google_access_token'])): ?>
        <!-- Section supprimée pour le mode démonstration -->
    <?php endif; ?>

    <div class="telemedicine-container">
        <div class="demo-notice">
            <i class="fas fa-info-circle"></i>
            <strong>Mode Démonstration</strong> - Les réunions générées sont simulées pour les tests
        </div>
        
        <form method="POST" action="<?php echo BASE_URL; ?>telemedicine/create" class="telemedicine-form">
            <div class="form-step active" data-step="1">
                <div class="step-header">
                    <div class="step-number">1</div>
                    <h3>Sélection du patient</h3>
                </div>
                
                <div class="patient-search-container">
                    <div class="search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" id="patient_search" placeholder="Rechercher un patient..." autocomplete="off">
                    </div>
                </div>

                <div class="patient-select-container">
                    <select name="patient_id" id="patient_id" required>
                        <option value="">-- Sélectionner un patient --</option>
                        <?php foreach ($patients as $patient): ?>
                            <option value="<?= $patient['id'] ?>" data-email="<?= $patient['email'] ?>" data-phone="<?= $patient['telephone'] ?>">
                                <?= htmlspecialchars($patient['nom'] . ' ' . $patient['prenom']) ?>
                                (<?= htmlspecialchars($patient['dossier_numero']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div id="patient_info" class="patient-card" style="display: none;">
                    <div class="patient-avatar">
                        <i class="fas fa-user"></i>
                    </div>
                    <div class="patient-details">
                        <h4 id="patient_name"></h4>
                        <div class="patient-contact">
                            <div class="contact-item">
                                <i class="fas fa-envelope"></i>
                                <span id="patient_email"></span>
                            </div>
                            <div class="contact-item">
                                <i class="fas fa-phone"></i>
                                <span id="patient_phone"></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-step" data-step="2">
                <div class="step-header">
                    <div class="step-number">2</div>
                    <h3>Planification de la consultation</h3>
                </div>
                
                <div class="scheduling-grid">
                    <div class="date-time-container">
                        <div class="input-group">
                            <label for="date">
                                <i class="fas fa-calendar-alt"></i>
                                Date de consultation
                            </label>
                            <input type="date" name="date" id="date" required min="<?= date('Y-m-d') ?>">
                        </div>
                        
                        <div class="input-group">
                            <label for="time">
                                <i class="fas fa-clock"></i>
                                Heure
                            </label>
                            <input type="time" name="time" id="time" required>
                        </div>
                    </div>

                    <div class="duration-container">
                        <label for="duration">
                            <i class="fas fa-hourglass-half"></i>
                            Durée de la consultation
                        </label>
                        <div class="duration-options">
                            <input type="radio" name="duration" value="15" id="dur15">
                            <label for="dur15" class="duration-option">15 min</label>
                            
                            <input type="radio" name="duration" value="30" id="dur30" checked>
                            <label for="dur30" class="duration-option">30 min</label>
                            
                            <input type="radio" name="duration" value="45" id="dur45">
                            <label for="dur45" class="duration-option">45 min</label>
                            
                            <input type="radio" name="duration" value="60" id="dur60">
                            <label for="dur60" class="duration-option">60 min</label>
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-navigation">
                <button type="button" class="nav-btn prev-btn" onclick="previousStep()" style="display: none;">
                    <i class="fas fa-arrow-left"></i> Précédent
                </button>
                <button type="button" class="nav-btn next-btn" onclick="nextStep()">
                    Suivant <i class="fas fa-arrow-right"></i>
                </button>
                <button type="submit" class="create-meeting-btn" style="display: none;">
                    <i class="fas fa-video"></i> Créer la réunion Google Meet
                </button>
            </div>
        </form>
    </div>
</div>

<style>
.demo-notice {
    background: linear-gradient(135deg, #ffeaa7 0%, #fab1a0 100%);
    color: #2d3436;
    padding: 1rem;
    border-radius: 10px;
    margin-bottom: 2rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-weight: 500;
}

.telemedicine-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 2rem;
    border-radius: 15px;
    margin-bottom: 2rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.header-content {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.header-icon {
    background: rgba(255,255,255,0.2);
    padding: 1rem;
    border-radius: 50%;
    font-size: 2rem;
}

.header-text h1 {
    margin: 0;
    font-size: 2.5rem;
    font-weight: 700;
}

.header-text p {
    margin: 0.5rem 0 0 0;
    opacity: 0.9;
}

.header-stats {
    display: flex;
    gap: 1rem;
}

.header-actions {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.back-btn {
    background: rgba(255,255,255,0.2);
    color: white;
    padding: 0.75rem 1.5rem;
    border-radius: 25px;
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    transition: all 0.3s;
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255,255,255,0.3);
}

.back-btn:hover {
    background: rgba(255,255,255,0.3);
    transform: translateY(-2px);
    color: white;
}

.stat-card {
    background: rgba(255,255,255,0.15);
    padding: 1rem;
    border-radius: 10px;
    text-align: center;
    backdrop-filter: blur(10px);
}

.stat-card i {
    display: block;
    font-size: 1.5rem;
    margin-bottom: 0.5rem;
}

.modern-alert {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 1rem;
    border-radius: 10px;
    margin-bottom: 1rem;
    border: none;
}

.alert-success {
    background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
    color: white;
}

.alert-error {
    background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
    color: white;
}

.meeting-success {
    background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%);
    padding: 2rem;
    border-radius: 15px;
    text-align: center;
    margin-bottom: 2rem;
}

.success-icon {
    background: #4CAF50;
    color: white;
    width: 80px;
    height: 80px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2rem;
    margin: 0 auto 1rem;
}

.meeting-link {
    display: flex;
    gap: 0.5rem;
    margin: 1rem 0;
    max-width: 500px;
    margin-left: auto;
    margin-right: auto;
}

.meeting-link input {
    flex: 1;
    padding: 0.75rem;
    border: 2px solid #ddd;
    border-radius: 8px;
    font-family: monospace;
}

.copy-btn {
    background: #2196F3;
    color: white;
    border: none;
    padding: 0.75rem 1rem;
    border-radius: 8px;
    cursor: pointer;
}

.join-meeting-btn {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 1rem 2rem;
    border-radius: 25px;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    margin-top: 1rem;
    transition: transform 0.2s;
}

.join-meeting-btn:hover {
    transform: translateY(-2px);
}

.auth-required {
    background: linear-gradient(135deg, #ffecd2 0%, #fcb69f 100%);
    padding: 3rem;
    border-radius: 15px;
    text-align: center;
    margin-bottom: 2rem;
}

.auth-icon {
    background: #DB4437;
    color: white;
    width: 100px;
    height: 100px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 3rem;
    margin: 0 auto 1rem;
}

.google-auth-btn {
    background: #DB4437;
    color: white;
    padding: 1rem 2rem;
    border-radius: 25px;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    margin-top: 1rem;
    transition: all 0.3s;
}

.google-auth-btn:hover {
    background: #c23321;
    transform: translateY(-2px);
}

.telemedicine-container {
    background: white;
    border-radius: 15px;
    padding: 2rem;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
}

.form-step {
    display: none;
    animation: fadeIn 0.5s;
}

.form-step.active {
    display: block;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}

.step-header {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 2rem;
}

.step-number {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    width: 50px;
    height: 50px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    font-weight: bold;
}

.search-box {
    position: relative;
    margin-bottom: 1rem;
}

.search-box i {
    position: absolute;
    left: 1rem;
    top: 50%;
    transform: translateY(-50%);
    color: #666;
}

.search-box input {
    width: 100%;
    padding: 1rem 1rem 1rem 3rem;
    border: 2px solid #e0e0e0;
    border-radius: 25px;
    font-size: 1rem;
    transition: all 0.3s;
}

.search-box input:focus {
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.patient-select-container select {
    width: 100%;
    padding: 1rem;
    border: 2px solid #e0e0e0;
    border-radius: 10px;
    font-size: 1rem;
    background: white;
}

.patient-card {
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    color: white;
    padding: 1.5rem;
    border-radius: 15px;
    margin-top: 1rem;
    display: flex;
    align-items: center;
    gap: 1rem;
}

.patient-avatar {
    background: rgba(255,255,255,0.2);
    width: 60px;
    height: 60px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
}

.patient-details h4 {
    margin: 0 0 0.5rem 0;
    font-size: 1.2rem;
}

.patient-contact {
    display: flex;
    gap: 1rem;
}

.contact-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    opacity: 0.9;
}

.scheduling-grid {
    display: grid;
    gap: 2rem;
}

.date-time-container {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
}

.input-group label {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 0.5rem;
    font-weight: 600;
    color: #333;
}

.input-group input {
    width: 100%;
    padding: 1rem;
    border: 2px solid #e0e0e0;
    border-radius: 10px;
    font-size: 1rem;
    transition: all 0.3s;
}

.input-group input:focus {
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.duration-container label {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 1rem;
    font-weight: 600;
    color: #333;
}

.duration-options {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 0.5rem;
}

.duration-options input[type="radio"] {
    display: none;
}

.duration-option {
    padding: 1rem;
    border: 2px solid #e0e0e0;
    border-radius: 10px;
    text-align: center;
    cursor: pointer;
    transition: all 0.3s;
    background: white;
}

.duration-options input[type="radio"]:checked + .duration-option {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-color: #667eea;
}

.form-navigation {
    display: flex;
    justify-content: space-between;
    margin-top: 2rem;
    padding-top: 2rem;
    border-top: 1px solid #e0e0e0;
}

.nav-btn, .create-meeting-btn {
    padding: 1rem 2rem;
    border: none;
    border-radius: 25px;
    font-size: 1rem;
    cursor: pointer;
    transition: all 0.3s;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.prev-btn {
    background: #f5f5f5;
    color: #666;
}

.next-btn, .create-meeting-btn {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.nav-btn:hover, .create-meeting-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.2);
}

@media (max-width: 768px) {
    .telemedicine-header {
        flex-direction: column;
        text-align: center;
        gap: 1rem;
    }
    
    .header-stats {
        justify-content: center;
    }
    
    .date-time-container {
        grid-template-columns: 1fr;
    }
    
    .duration-options {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .patient-contact {
        flex-direction: column;
        gap: 0.5rem;
    }
}
</style>

<script>
let currentStep = 1;
const totalSteps = 2;

function nextStep() {
    if (currentStep === 1) {
        const patientSelect = document.getElementById('patient_id');
        if (!patientSelect.value) {
            alert('Veuillez sélectionner un patient');
            return;
        }
        
        const selectedOption = patientSelect.options[patientSelect.selectedIndex];
        const email = selectedOption.dataset.email;
        if (!email) {
            alert('Le patient sélectionné n\'a pas d\'adresse email');
            return;
        }
    }
    
    if (currentStep < totalSteps) {
        document.querySelector(`[data-step="${currentStep}"]`).classList.remove('active');
        currentStep++;
        document.querySelector(`[data-step="${currentStep}"]`).classList.add('active');
        
        updateNavigation();
    }
}

function previousStep() {
    if (currentStep > 1) {
        document.querySelector(`[data-step="${currentStep}"]`).classList.remove('active');
        currentStep--;
        document.querySelector(`[data-step="${currentStep}"]`).classList.add('active');
        
        updateNavigation();
    }
}

function updateNavigation() {
    const prevBtn = document.querySelector('.prev-btn');
    const nextBtn = document.querySelector('.next-btn');
    const createBtn = document.querySelector('.create-meeting-btn');
    
    prevBtn.style.display = currentStep > 1 ? 'flex' : 'none';
    nextBtn.style.display = currentStep < totalSteps ? 'flex' : 'none';
    createBtn.style.display = currentStep === totalSteps ? 'flex' : 'none';
}

function copyMeetingUrl() {
    const urlInput = document.getElementById('meetingUrl');
    urlInput.select();
    document.execCommand('copy');
    
    const copyBtn = document.querySelector('.copy-btn');
    const originalText = copyBtn.innerHTML;
    copyBtn.innerHTML = '<i class="fas fa-check"></i>';
    copyBtn.style.background = '#4CAF50';
    
    setTimeout(() => {
        copyBtn.innerHTML = originalText;
        copyBtn.style.background = '#2196F3';
    }, 2000);
}

document.addEventListener('DOMContentLoaded', function() {
    const patientSearch = document.getElementById('patient_search');
    const patientSelect = document.getElementById('patient_id');
    const patientInfo = document.getElementById('patient_info');
    const patientName = document.getElementById('patient_name');
    const patientEmail = document.getElementById('patient_email');
    const patientPhone = document.getElementById('patient_phone');
    
    patientSearch.addEventListener('input', function() {
        const query = this.value.toLowerCase();
        const options = patientSelect.querySelectorAll('option');
        
        options.forEach(option => {
            if (option.value === '') return;
            const text = option.textContent.toLowerCase();
            option.style.display = text.includes(query) ? 'block' : 'none';
        });
    });
    
    patientSelect.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        
        if (this.value) {
            const name = selectedOption.textContent.split('(')[0].trim();
            const email = selectedOption.dataset.email || 'Non renseigné';
            const phone = selectedOption.dataset.phone || 'Non renseigné';
            
            patientName.textContent = name;
            patientEmail.textContent = email;
            patientPhone.textContent = phone;
            patientInfo.style.display = 'flex';
        } else {
            patientInfo.style.display = 'none';
        }
    });
    
    // Set default time to current time + 1 hour
    const now = new Date();
    now.setHours(now.getHours() + 1);
    const timeString = now.toTimeString().slice(0, 5);
    document.getElementById('time').value = timeString;
});
</script>

<?php include __DIR__ . '/../layouts/footer.php'; ?>