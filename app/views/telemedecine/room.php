<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consultation Vidéo - DME Hospital</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        body { background: #1a1a1a; color: white; }
        .video-container { position: relative; height: 70vh; background: #000; border-radius: 10px; overflow: hidden; }
        .local-video { position: absolute; bottom: 20px; right: 20px; width: 200px; height: 150px; border-radius: 10px; border: 2px solid #007bff; }
        .remote-video { width: 100%; height: 100%; object-fit: cover; }
        .controls { position: fixed; bottom: 20px; left: 50%; transform: translateX(-50%); }
        .control-btn { width: 60px; height: 60px; border-radius: 50%; margin: 0 10px; border: none; font-size: 1.5rem; }
        .btn-danger { background: #dc3545; }
        .btn-primary { background: #007bff; }
        .btn-warning { background: #ffc107; color: #000; }
        .chat-panel { position: fixed; right: 20px; top: 20px; width: 300px; height: 400px; background: rgba(0,0,0,0.8); border-radius: 10px; padding: 15px; }
        .chat-messages { height: 300px; overflow-y: auto; margin-bottom: 10px; }
        .status-bar { position: fixed; top: 20px; left: 20px; background: rgba(0,0,0,0.8); padding: 10px 20px; border-radius: 20px; }
    </style>
</head>
<body>
    <div class="status-bar">
        <i class="bi bi-circle-fill text-success"></i>
        <span>Consultation en cours</span>
        <span id="duration">00:00</span>
    </div>

    <div class="container-fluid p-4">
        <div class="video-container">
            <video id="remoteVideo" class="remote-video" autoplay playsinline></video>
            <video id="localVideo" class="local-video" autoplay muted playsinline></video>
        </div>
    </div>

    <div class="controls">
        <button class="control-btn btn-primary" id="toggleVideo" title="Caméra">
            <i class="bi bi-camera-video"></i>
        </button>
        <button class="control-btn btn-primary" id="toggleAudio" title="Microphone">
            <i class="bi bi-mic"></i>
        </button>
        <button class="control-btn btn-warning" id="shareScreen" title="Partager écran">
            <i class="bi bi-display"></i>
        </button>
        <button class="control-btn btn-danger" id="endCall" title="Terminer">
            <i class="bi bi-telephone-x"></i>
        </button>
    </div>

    <div class="chat-panel">
        <h6><i class="bi bi-chat"></i> Chat</h6>
        <div class="chat-messages" id="chatMessages"></div>
        <div class="input-group">
            <input type="text" class="form-control form-control-sm" id="chatInput" placeholder="Message...">
            <button class="btn btn-primary btn-sm" id="sendMessage">
                <i class="bi bi-send"></i>
            </button>
        </div>
    </div>

    <script>
    class VideoCall {
        constructor(roomId, sessionId) {
            this.roomId = roomId;
            this.sessionId = sessionId;
            this.localStream = null;
            this.remoteStream = null;
            this.peerConnection = null;
            this.isVideoEnabled = true;
            this.isAudioEnabled = true;
            this.startTime = Date.now();
            
            this.init();
        }
        
        async init() {
            try {
                // Obtenir le flux local
                this.localStream = await navigator.mediaDevices.getUserMedia({
                    video: true,
                    audio: true
                });
                
                document.getElementById('localVideo').srcObject = this.localStream;
                
                // Configuration WebRTC (simplifiée pour démo)
                this.setupPeerConnection();
                this.setupEventListeners();
                this.startDurationTimer();
                
            } catch (error) {
                console.error('Erreur accès média:', error);
                alert('Impossible d\'accéder à la caméra/microphone');
            }
        }
        
        setupPeerConnection() {
            // Configuration STUN/TURN (utiliser des serveurs réels en production)
            const configuration = {
                iceServers: [
                    { urls: 'stun:stun.l.google.com:19302' }
                ]
            };
            
            this.peerConnection = new RTCPeerConnection(configuration);
            
            // Ajouter le flux local
            this.localStream.getTracks().forEach(track => {
                this.peerConnection.addTrack(track, this.localStream);
            });
            
            // Gérer le flux distant
            this.peerConnection.ontrack = (event) => {
                document.getElementById('remoteVideo').srcObject = event.streams[0];
            };
        }
        
        setupEventListeners() {
            document.getElementById('toggleVideo').addEventListener('click', () => {
                this.toggleVideo();
            });
            
            document.getElementById('toggleAudio').addEventListener('click', () => {
                this.toggleAudio();
            });
            
            document.getElementById('shareScreen').addEventListener('click', () => {
                this.shareScreen();
            });
            
            document.getElementById('endCall').addEventListener('click', () => {
                this.endCall();
            });
            
            document.getElementById('sendMessage').addEventListener('click', () => {
                this.sendChatMessage();
            });
            
            document.getElementById('chatInput').addEventListener('keypress', (e) => {
                if (e.key === 'Enter') {
                    this.sendChatMessage();
                }
            });
        }
        
        toggleVideo() {
            this.isVideoEnabled = !this.isVideoEnabled;
            const videoTrack = this.localStream.getVideoTracks()[0];
            if (videoTrack) {
                videoTrack.enabled = this.isVideoEnabled;
            }
            
            const btn = document.getElementById('toggleVideo');
            btn.innerHTML = this.isVideoEnabled ? '<i class="bi bi-camera-video"></i>' : '<i class="bi bi-camera-video-off"></i>';
            btn.className = this.isVideoEnabled ? 'control-btn btn-primary' : 'control-btn btn-secondary';
        }
        
        toggleAudio() {
            this.isAudioEnabled = !this.isAudioEnabled;
            const audioTrack = this.localStream.getAudioTracks()[0];
            if (audioTrack) {
                audioTrack.enabled = this.isAudioEnabled;
            }
            
            const btn = document.getElementById('toggleAudio');
            btn.innerHTML = this.isAudioEnabled ? '<i class="bi bi-mic"></i>' : '<i class="bi bi-mic-mute"></i>';
            btn.className = this.isAudioEnabled ? 'control-btn btn-primary' : 'control-btn btn-secondary';
        }
        
        async shareScreen() {
            try {
                const screenStream = await navigator.mediaDevices.getDisplayMedia({
                    video: true,
                    audio: true
                });
                
                // Remplacer la piste vidéo
                const videoTrack = screenStream.getVideoTracks()[0];
                const sender = this.peerConnection.getSenders().find(s => 
                    s.track && s.track.kind === 'video'
                );
                
                if (sender) {
                    await sender.replaceTrack(videoTrack);
                }
                
                document.getElementById('localVideo').srcObject = screenStream;
                
                videoTrack.onended = () => {
                    // Revenir à la caméra
                    this.init();
                };
                
            } catch (error) {
                console.error('Erreur partage écran:', error);
            }
        }
        
        endCall() {
            if (confirm('Terminer la consultation ?')) {
                // Arrêter tous les flux
                if (this.localStream) {
                    this.localStream.getTracks().forEach(track => track.stop());
                }
                
                if (this.peerConnection) {
                    this.peerConnection.close();
                }
                
                // Notifier le serveur
                fetch(`${BASE_URL}telemedecine/end/${this.sessionId}`, {
                    method: 'POST'
                }).then(() => {
                    window.location.href = `${BASE_URL}telemedecine`;
                });
            }
        }
        
        sendChatMessage() {
            const input = document.getElementById('chatInput');
            const message = input.value.trim();
            
            if (message) {
                const chatMessages = document.getElementById('chatMessages');
                const messageDiv = document.createElement('div');
                messageDiv.className = 'mb-2';
                messageDiv.innerHTML = `<small class="text-muted">${new Date().toLocaleTimeString()}</small><br>${message}`;
                chatMessages.appendChild(messageDiv);
                chatMessages.scrollTop = chatMessages.scrollHeight;
                
                input.value = '';
            }
        }
        
        startDurationTimer() {
            setInterval(() => {
                const elapsed = Math.floor((Date.now() - this.startTime) / 1000);
                const minutes = Math.floor(elapsed / 60).toString().padStart(2, '0');
                const seconds = (elapsed % 60).toString().padStart(2, '0');
                document.getElementById('duration').textContent = `${minutes}:${seconds}`;
            }, 1000);
        }
    }
    
    // Initialiser l'appel vidéo
    const roomId = '<?= $session['room_id'] ?>';
    const sessionId = '<?= $session['id'] ?>';
    const videoCall = new VideoCall(roomId, sessionId);
    </script>
</body>
</html>