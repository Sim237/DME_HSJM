<?php
$title = "Impression & Notifications";
include __DIR__ . '/../layouts/header.php';
?>

<div class="main-content">
    <div class="demo-header">
        <h1><i class="fas fa-print"></i> Impression Avancée & Notifications</h1>
        <p>Codes-barres, QR codes et rappels automatiques</p>
    </div>

    <div class="demo-grid">
        <!-- Impression -->
        <div class="demo-section">
            <h3><i class="fas fa-barcode"></i> Impression Avancée</h3>
            
            <div class="demo-buttons">
                <a href="<?php echo BASE_URL; ?>print/patient-card/1" target="_blank" class="demo-btn">
                    <i class="fas fa-id-card"></i> Carte Patient avec QR Code
                </a>
                
                <a href="<?php echo BASE_URL; ?>print/ordonnance/1" target="_blank" class="demo-btn">
                    <i class="fas fa-prescription"></i> Ordonnance avec Code-barres
                </a>
                
                <a href="<?php echo BASE_URL; ?>print/barcode?data=P-2024-00001" target="_blank" class="demo-btn">
                    <i class="fas fa-barcode"></i> Générer Code-barres
                </a>
                
                <a href="<?php echo BASE_URL; ?>print/qrcode?data=Patient123" target="_blank" class="demo-btn">
                    <i class="fas fa-qrcode"></i> Générer QR Code
                </a>
            </div>
        </div>

        <!-- Notifications -->
        <div class="demo-section">
            <h3><i class="fas fa-bell"></i> Notifications Automatiques</h3>
            
            <div class="notification-form">
                <h4>Test Rappel RDV</h4>
                <form id="reminderForm">
                    <select name="patient_id" required>
                        <option value="1">Patient Test</option>
                    </select>
                    <input type="datetime-local" name="date_rdv" required>
                    <input type="hidden" name="type" value="appointment">
                    <button type="submit" class="demo-btn">
                        <i class="fas fa-calendar"></i> Envoyer Rappel RDV
                    </button>
                </form>
            </div>
            
            <div class="notification-form">
                <h4>Test Notification Résultats</h4>
                <form id="resultsForm">
                    <select name="patient_id" required>
                        <option value="1">Patient Test</option>
                    </select>
                    <input type="hidden" name="type" value="results">
                    <button type="submit" class="demo-btn">
                        <i class="fas fa-file-medical"></i> Notifier Résultats
                    </button>
                </form>
            </div>
            
            <div class="demo-buttons">
                <button onclick="scheduleReminders()" class="demo-btn">
                    <i class="fas fa-clock"></i> Programmer Rappels Auto
                </button>
                
                <button onclick="testSMS()" class="demo-btn">
                    <i class="fas fa-sms"></i> Test SMS
                </button>
                
                <button onclick="testEmail()" class="demo-btn">
                    <i class="fas fa-envelope"></i> Test Email
                </button>
            </div>
        </div>
    </div>
    
    <div id="result" class="result-display"></div>
</div>

<style>
.demo-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 2rem;
    border-radius: 15px;
    margin-bottom: 2rem;
    text-align: center;
}

.demo-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 2rem;
    margin-bottom: 2rem;
}

.demo-section {
    background: white;
    padding: 2rem;
    border-radius: 15px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}

.demo-section h3 {
    color: #2c3e50;
    margin-bottom: 1.5rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.demo-buttons {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.demo-btn {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 1rem;
    border: none;
    border-radius: 10px;
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    cursor: pointer;
    transition: all 0.3s;
}

.demo-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.2);
}

.notification-form {
    background: #f8f9fa;
    padding: 1rem;
    border-radius: 10px;
    margin-bottom: 1rem;
}

.notification-form h4 {
    margin: 0 0 1rem 0;
    color: #495057;
}

.notification-form select,
.notification-form input {
    width: 100%;
    padding: 0.5rem;
    margin-bottom: 0.5rem;
    border: 1px solid #ddd;
    border-radius: 5px;
}

.result-display {
    background: #e9ecef;
    padding: 1rem;
    border-radius: 10px;
    min-height: 50px;
    display: none;
}

@media (max-width: 768px) {
    .demo-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
document.getElementById('reminderForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    
    fetch('<?php echo BASE_URL; ?>notifications/send-reminder', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        showResult(data.message, data.success ? 'success' : 'error');
    });
});

document.getElementById('resultsForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    
    fetch('<?php echo BASE_URL; ?>notifications/send-reminder', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        showResult(data.message, data.success ? 'success' : 'error');
    });
});

function scheduleReminders() {
    fetch('<?php echo BASE_URL; ?>notifications/schedule-reminders')
    .then(response => response.json())
    .then(data => {
        showResult(data.message, 'success');
    });
}

function testSMS() {
    const phone = prompt('Numéro de téléphone:');
    if (phone) {
        fetch(`<?php echo BASE_URL; ?>notifications/test-sms?phone=${phone}&message=Test SMS DME Hospital`)
        .then(response => response.json())
        .then(data => {
            showResult('SMS de test envoyé (simulé)', 'success');
        });
    }
}

function testEmail() {
    const email = prompt('Adresse email:');
    if (email) {
        fetch(`<?php echo BASE_URL; ?>notifications/test-email?email=${email}&subject=Test&message=Test email DME Hospital`)
        .then(response => response.json())
        .then(data => {
            showResult('Email de test envoyé', data.success ? 'success' : 'error');
        });
    }
}

function showResult(message, type) {
    const result = document.getElementById('result');
    result.textContent = message;
    result.className = 'result-display ' + type;
    result.style.display = 'block';
    
    setTimeout(() => {
        result.style.display = 'none';
    }, 5000);
}
</script>

<?php include __DIR__ . '/../layouts/footer.php'; ?>