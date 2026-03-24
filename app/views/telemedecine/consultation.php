<?php include __DIR__ . '/../layouts/header.php'; ?>

<div class="main-content">
    <div class="container-fluid p-4">
        <div class="row">
            <!-- Zone vidéo principale -->
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5><i class="fas fa-video me-2"></i>Consultation Vidéo</h5>
                    </div>
                    <div class="card-body p-0">
                        <!-- Conteneur Jitsi Meet -->
                        <div id="jitsi-container" style="height: 500px; width: 100%;"></div>
                        
                        <!-- Contrôles vidéo -->
                        <div class="p-3 bg-light border-top">
                            <div class="d-flex justify-content-center gap-3">
                                <button id="toggleAudio" class="btn btn-outline-primary">
                                    <i class="fas fa-microphone"></i>
                                </button>
                                <button id="toggleVideo" class="btn btn-outline-primary">
                                    <i class="fas fa-video"></i>
                                </button>
                                <button id="shareScreen" class="btn btn-outline-info">
                                    <i class="fas fa-desktop"></i>
                                </button>
                                <button id="endCall" class="btn btn-danger">
                                    <i class="fas fa-phone-slash"></i> Terminer
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Panneau latéral -->
            <div class="col-md-4">
                <!-- Informations patient -->
                <div class="card mb-3">
                    <div class="card-header">
                        <h6><i class="fas fa-user me-2"></i>Patient</h6>
                    </div>
                    <div class="card-body">
                        <div id="patient-info">
                            <p><strong>Nom:</strong> <span id="patient-nom">-</span></p>
                            <p><strong>Âge:</strong> <span id="patient-age">-</span></p>
                            <p><strong>Motif:</strong> <span id="consultation-motif">-</span></p>
                        </div>
                    </div>
                </div>

                <!-- Chat -->
                <div class="card mb-3">
                    <div class="card-header">
                        <h6><i class="fas fa-comments me-2"></i>Messages</h6>
                    </div>
                    <div class="card-body p-0">
                        <div id="chat-messages" style="height: 200px; overflow-y: auto;" class="p-3">
                            <!-- Messages du chat -->
                        </div>
                        <div class="border-top p-2">
                            <div class="input-group">
                                <input type="text" id="chat-input" class="form-control" placeholder="Tapez votre message...">
                                <button id="send-message" class="btn btn-primary">
                                    <i class="fas fa-paper-plane"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Partage de fichiers -->
                <div class="card">
                    <div class="card-header">
                        <h6><i class="fas fa-file-upload me-2"></i>Documents</h6>
                    </div>
                    <div class="card-body">
                        <input type="file" id="file-upload" class="form-control mb-2" multiple>
                        <button id="upload-btn" class="btn btn-sm btn-success w-100">
                            <i class="fas fa-upload me-1"></i>Partager
                        </button>
                        <div id="shared-files" class="mt-3">
                            <!-- Liste des fichiers partagés -->
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal fin de consultation -->
        <div class="modal fade" id="endConsultationModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Terminer la consultation</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <form id="end-consultation-form">
                            <div class="mb-3">
                                <label class="form-label">Diagnostic</label>
                                <textarea name="diagnostic" class="form-control" rows="3" required></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Prescription (optionnel)</label>
                                <textarea name="prescription" class="form-control" rows="3"></textarea>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="button" id="confirm-end" class="btn btn-primary">Terminer</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Jitsi Meet API -->
<script src="https://meet.jit.si/external_api.js"></script>

<script>
let jitsiApi;
const consultationId = <?= $id ?? 'null' ?>;

// Initialiser Jitsi Meet
document.addEventListener('DOMContentLoaded', function() {
    const domain = 'meet.jit.si';
    const options = {
        roomName: 'DMEHospital_Room_' + consultationId,
        width: '100%',
        height: 500,
        parentNode: document.querySelector('#jitsi-container'),
        configOverwrite: {
            startWithAudioMuted: false,
            startWithVideoMuted: false
        },
        interfaceConfigOverwrite: {
            TOOLBAR_BUTTONS: [
                'microphone', 'camera', 'desktop', 'fullscreen',
                'fodeviceselection', 'hangup', 'profile', 'chat',
                'recording', 'livestreaming', 'etherpad', 'sharedvideo',
                'settings', 'raisehand', 'videoquality', 'filmstrip'
            ]
        }
    };

    jitsiApi = new JitsiMeetExternalAPI(domain, options);

    // Événements Jitsi
    jitsiApi.addEventListener('videoConferenceJoined', () => {
        console.log('Consultation démarrée');
    });

    jitsiApi.addEventListener('videoConferenceLeft', () => {
        console.log('Consultation terminée');
    });
});

// Contrôles personnalisés
document.getElementById('toggleAudio').addEventListener('click', function() {
    jitsiApi.executeCommand('toggleAudio');
});

document.getElementById('toggleVideo').addEventListener('click', function() {
    jitsiApi.executeCommand('toggleVideo');
});

document.getElementById('shareScreen').addEventListener('click', function() {
    jitsiApi.executeCommand('toggleShareScreen');
});

document.getElementById('endCall').addEventListener('click', function() {
    $('#endConsultationModal').modal('show');
});

// Terminer consultation
document.getElementById('confirm-end').addEventListener('click', function() {
    const form = document.getElementById('end-consultation-form');
    const formData = new FormData(form);
    formData.append('consultation_id', consultationId);

    fetch('<?= BASE_URL ?>telemedecine/terminer', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            jitsiApi.dispose();
            window.location.href = '<?= BASE_URL ?>telemedecine';
        }
    });
});

// Chat en temps réel
document.getElementById('send-message').addEventListener('click', function() {
    const input = document.getElementById('chat-input');
    const message = input.value.trim();
    
    if (message) {
        // Ajouter message au chat
        const chatDiv = document.getElementById('chat-messages');
        const messageDiv = document.createElement('div');
        messageDiv.className = 'mb-2';
        messageDiv.innerHTML = `
            <small class="text-muted">${new Date().toLocaleTimeString()}</small><br>
            <strong>Médecin:</strong> ${message}
        `;
        chatDiv.appendChild(messageDiv);
        chatDiv.scrollTop = chatDiv.scrollHeight;
        
        input.value = '';
    }
});

// Upload de fichiers
document.getElementById('upload-btn').addEventListener('click', function() {
    const fileInput = document.getElementById('file-upload');
    const files = fileInput.files;
    
    if (files.length > 0) {
        const formData = new FormData();
        for (let file of files) {
            formData.append('files[]', file);
        }
        formData.append('consultation_id', consultationId);
        
        fetch('<?= BASE_URL ?>telemedecine/upload', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Actualiser la liste des fichiers
                location.reload();
            }
        });
    }
});
</script>

<?php include __DIR__ . '/../layouts/footer.php'; ?>