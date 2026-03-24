<!DOCTYPE html>
<html>
<head>
    <title>Chat Médical</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        * { box-sizing: border-box; }
        body { 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            margin: 0;
            padding: 20px;
        }
        .chat-wrapper {
            max-width: 1400px;
            margin: 0 auto;
            background: rgba(255,255,255,0.95);
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.1);
            backdrop-filter: blur(10px);
            overflow: hidden;
        }
        .chat-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px 30px;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .chat-header h4 {
            margin: 0;
            font-weight: 600;
        }
        .chat-container { 
            height: 75vh;
            display: flex;
        }
        .sidebar {
            width: 350px;
            background: #f8f9fa;
            border-right: 1px solid #e9ecef;
            display: flex;
            flex-direction: column;
        }
        .conversations { 
            flex: 1;
            overflow-y: auto;
            background: white;
        }
        .conversation-item {
            padding: 20px;
            border-bottom: 1px solid #f1f3f4;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 15px;
            position: relative;
        }
        .conversation-item:hover {
            background: linear-gradient(135deg, #f8f9ff 0%, #f0f4ff 100%);
            transform: translateX(5px);
        }
        .conversation-item.active {
            background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
            border-left: 5px solid #2196f3;
        }
        .avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 18px;
            box-shadow: 0 4px 15px rgba(102,126,234,0.3);
        }
        .main-chat {
            flex: 1;
            display: flex;
            flex-direction: column;
            background: white;
        }
        .empty-state {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100%;
            background: linear-gradient(135deg, #f8f9ff 0%, #f0f4ff 100%);
            color: #6c757d;
            text-align: center;
            padding: 40px;
        }
        .empty-state i {
            font-size: 5rem;
            margin-bottom: 2rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            opacity: 0.7;
        }
        .urgent-section {
            padding: 25px;
            background: linear-gradient(135deg, #fff5f5 0%, #fed7d7 100%);
            border-bottom: 1px solid #fecaca;
        }
        .urgent-badge {
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a24 100%);
            color: white;
            padding: 12px 18px;
            border-radius: 25px;
            font-size: 13px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 12px;
            animation: pulse 2s infinite;
            box-shadow: 0 4px 15px rgba(255,107,107,0.3);
        }
        @keyframes pulse {
            0% { transform: scale(1); box-shadow: 0 4px 15px rgba(255,107,107,0.3); }
            50% { transform: scale(1.02); box-shadow: 0 6px 20px rgba(255,107,107,0.4); }
            100% { transform: scale(1); box-shadow: 0 4px 15px rgba(255,107,107,0.3); }
        }
        .section-title {
            padding: 20px 25px 15px;
            font-weight: 600;
            color: #495057;
            background: #f8f9fa;
            border-bottom: 1px solid #e9ecef;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .chat-active {
            display: flex;
            flex-direction: column;
            height: 100%;
        }
        .chat-header-active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px 25px;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .chat-messages {
            flex: 1;
            padding: 25px;
            overflow-y: auto;
            background: linear-gradient(135deg, #f8f9ff 0%, #ffffff 100%);
        }
        .message {
            margin-bottom: 20px;
            display: flex;
            align-items: start;
            gap: 12px;
        }
        .message.sent {
            flex-direction: row-reverse;
        }
        .message-bubble {
            max-width: 70%;
            padding: 15px 20px;
            border-radius: 20px;
            font-size: 14px;
            line-height: 1.4;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .message.received .message-bubble {
            background: white;
            border: 1px solid #e9ecef;
        }
        .message.sent .message-bubble {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .chat-input {
            background: white;
            padding: 20px 25px;
            border-top: 1px solid #e9ecef;
        }
        .input-group {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        .form-control {
            flex: 1;
            padding: 15px 20px;
            border: 2px solid #e9ecef;
            border-radius: 25px;
            font-size: 14px;
            transition: all 0.3s ease;
        }
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102,126,234,0.1);
            outline: none;
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            padding: 15px 25px;
            border-radius: 25px;
            color: white;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(102,126,234,0.3);
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102,126,234,0.4);
        }
        .btn-outline-secondary {
            border: 2px solid #e9ecef;
            background: white;
            padding: 12px 20px;
            border-radius: 20px;
            color: #6c757d;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        .btn-outline-secondary:hover {
            border-color: #667eea;
            color: #667eea;
            transform: translateY(-1px);
        }
        .badge {
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a24 100%);
            color: white;
            padding: 6px 12px;
            border-radius: 15px;
            font-size: 11px;
            font-weight: 600;
        }
        .features-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-top: 30px;
        }
        .feature-item {
            text-align: center;
            padding: 20px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
        }
        .feature-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        }
        .feature-item i {
            font-size: 2.5rem;
            margin-bottom: 15px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
    </style>
</head>
<body>
    <div class="chat-wrapper">
        <div class="chat-header">
            <a href="<?= BASE_URL ?>" class="btn" style="background: rgba(255,255,255,0.2); color: white; border: none; margin-right: 15px;">
                <i class="fas fa-arrow-left"></i>
            </a>
            <i class="fas fa-comments fs-4"></i>
            <h4>Chat Médical Sécurisé</h4>
            <div class="ms-auto d-flex gap-3">
                <span class="badge bg-success">En ligne</span>
                <i class="fas fa-shield-alt" title="Communication sécurisée"></i>
            </div>
        </div>
        
        <div class="chat-container">
            <div class="sidebar">
                <!-- Messages Urgents -->
                <div class="urgent-section">
                    <h6 class="text-danger mb-3 fw-bold">
                        <i class="fas fa-exclamation-triangle"></i> Messages Urgents
                    </h6>
                    <div class="urgent-badge">
                        <i class="fas fa-heartbeat"></i>
                        <div>
                            <strong>Dr. Martin</strong><br>
                            <small>Patient en détresse respiratoire - Salle 12</small>
                        </div>
                    </div>
                    <div class="urgent-badge">
                        <i class="fas fa-pills"></i>
                        <div>
                            <strong>Pharmacie</strong><br>
                            <small>Rupture de stock - Adrénaline</small>
                        </div>
                    </div>
                </div>
                
                <div class="section-title">
                    <i class="fas fa-comments"></i>
                    Conversations Récentes
                </div>
                
                <div class="conversations">
                    <div class="conversation-item" data-contact="1">
                        <div class="avatar">DM</div>
                        <div class="flex-grow-1">
                            <div class="d-flex justify-content-between align-items-center">
                                <strong>Dr. Martin</strong>
                                <small class="text-muted">14:30</small>
                            </div>
                            <small class="text-muted">Cardiologue</small>
                            <div class="small text-primary mt-1">Résultats ECG disponibles</div>
                        </div>
                        <span class="badge">2</span>
                    </div>
                    
                    <div class="conversation-item" data-contact="2">
                        <div class="avatar" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">IS</div>
                        <div class="flex-grow-1">
                            <div class="d-flex justify-content-between align-items-center">
                                <strong>Inf. Sophie</strong>
                                <small class="text-muted">13:45</small>
                            </div>
                            <small class="text-muted">Service Urgences</small>
                            <div class="small text-success mt-1">Constantes prises</div>
                        </div>
                    </div>
                    
                    <div class="conversation-item" data-contact="3">
                        <div class="avatar" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);">LB</div>
                        <div class="flex-grow-1">
                            <div class="d-flex justify-content-between align-items-center">
                                <strong>Lab. Bernard</strong>
                                <small class="text-muted">12:20</small>
                            </div>
                            <small class="text-muted">Laboratoire</small>
                            <div class="small text-warning mt-1">Analyses en cours</div>
                        </div>
                        <span class="badge" style="background: linear-gradient(135deg, #ffa726 0%, #ff9800 100%);">1</span>
                    </div>
                    
                    <div class="conversation-item" data-contact="4">
                        <div class="avatar" style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);">PH</div>
                        <div class="flex-grow-1">
                            <div class="d-flex justify-content-between align-items-center">
                                <strong>Pharmacie</strong>
                                <small class="text-muted">11:15</small>
                            </div>
                            <small class="text-muted">Service Pharmacie</small>
                            <div class="small text-info mt-1">Ordonnance prête</div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="main-chat">
                <div class="empty-state" id="no-conversation">
                    <i class="fas fa-comments"></i>
                    <h4 class="mb-3">Bienvenue dans le Chat Médical</h4>
                    <p class="mb-4">Sélectionnez une conversation à gauche pour commencer<br>
                    <small class="text-muted">Communication sécurisée entre professionnels de santé</small></p>
                    
                    <div class="d-flex gap-3 mb-4">
                        <button class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i> Nouvelle conversation
                        </button>
                        <button class="btn btn-outline-secondary">
                            <i class="fas fa-search me-2"></i> Rechercher
                        </button>
                    </div>
                    
                    <div class="features-grid">
                        <div class="feature-item">
                            <i class="fas fa-shield-alt"></i>
                            <div class="fw-bold">Sécurisé</div>
                            <small class="text-muted">Chiffrement bout à bout</small>
                        </div>
                        <div class="feature-item">
                            <i class="fas fa-clock"></i>
                            <div class="fw-bold">Temps réel</div>
                            <small class="text-muted">Messages instantanés</small>
                        </div>
                        <div class="feature-item">
                            <i class="fas fa-file-medical"></i>
                            <div class="fw-bold">Contexte patient</div>
                            <small class="text-muted">Dossiers liés</small>
                        </div>
                    </div>
                </div>
                
                <div class="d-none chat-active" id="chat-active">
                    <div class="chat-header-active">
                        <div class="avatar">DM</div>
                        <div>
                            <h6 class="mb-0">Dr. Martin</h6>
                            <small class="opacity-75"><i class="fas fa-circle text-success"></i> En ligne</small>
                        </div>
                        <div class="ms-auto d-flex gap-2">
                            <button class="btn btn-sm" style="background: rgba(255,255,255,0.2); color: white; border: none;">
                                <i class="fas fa-video"></i>
                            </button>
                            <button class="btn btn-sm" style="background: rgba(255,255,255,0.2); color: white; border: none;">
                                <i class="fas fa-phone"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div class="chat-messages">
                        <div class="text-center mb-4">
                            <small class="text-muted bg-white px-3 py-1 rounded-pill">Conversation du 15 Décembre 2024</small>
                        </div>
                        
                        <div class="message received">
                            <div class="avatar" style="width: 35px; height: 35px; font-size: 12px;">DM</div>
                            <div class="message-bubble">
                                <div>Les résultats de l'ECG du patient en salle 12 sont disponibles.</div>
                                <small class="text-muted d-block mt-2">14:30</small>
                            </div>
                        </div>
                        
                        <div class="message sent">
                            <div class="avatar" style="width: 35px; height: 35px; font-size: 12px; background: linear-gradient(135deg, #28a745 0%, #20c997 100%);">Moi</div>
                            <div class="message-bubble">
                                <div>Merci, je vais examiner les résultats immédiatement.</div>
                                <small class="opacity-75 d-block mt-2">14:32</small>
                            </div>
                        </div>
                        
                        <div class="message received">
                            <div class="avatar" style="width: 35px; height: 35px; font-size: 12px;">DM</div>
                            <div class="message-bubble">
                                <div>Parfait. Le patient présente une légère arythmie. Faut-il ajuster le traitement ?</div>
                                <small class="text-muted d-block mt-2">14:35</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="chat-input">
                        <div class="input-group">
                            <input type="text" class="form-control" placeholder="Tapez votre message...">
                            <button class="btn btn-primary">
                                <i class="fas fa-paper-plane"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.querySelectorAll('.conversation-item').forEach(item => {
            item.addEventListener('click', function() {
                document.querySelectorAll('.conversation-item').forEach(i => i.classList.remove('active'));
                this.classList.add('active');
                
                document.getElementById('no-conversation').style.display = 'none';
                const chatActive = document.getElementById('chat-active');
                chatActive.classList.remove('d-none');
                chatActive.style.display = 'flex';
                chatActive.style.height = '100%';
            });
        });
        
        // Animation d'entrée
        document.querySelector('.chat-wrapper').style.transform = 'translateY(20px)';
        document.querySelector('.chat-wrapper').style.opacity = '0';
        document.querySelector('.chat-wrapper').style.transition = 'all 0.5s ease';
        
        setTimeout(() => {
            document.querySelector('.chat-wrapper').style.transform = 'translateY(0)';
            document.querySelector('.chat-wrapper').style.opacity = '1';
        }, 100);
    </script>
</body>
</html>