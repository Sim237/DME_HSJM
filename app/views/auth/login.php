<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - DME Hospital</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>public/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }
        
        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="50" cy="50" r="1" fill="%23ffffff" opacity="0.1"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>') repeat;
            pointer-events: none;
        }
        
        .login-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            padding: 3rem;
            border-radius: 24px;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.15);
            width: 100%;
            max-width: 420px;
            text-align: center;
            position: relative;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .login-header {
            margin-bottom: 2.5rem;
        }
        
        .logo {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3);
        }
        
        .logo i {
            font-size: 2rem;
            color: white;
        }
        
        .login-header h1 {
            color: #1a202c;
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .login-header p {
            color: #718096;
            font-size: 0.95rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
            text-align: left;
            position: relative;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #2d3748;
            font-weight: 500;
            font-size: 0.9rem;
        }
        
        .input-wrapper {
            position: relative;
        }
        
        .input-wrapper i {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #a0aec0;
            font-size: 1.1rem;
        }
        
        .form-group input {
            width: 100%;
            padding: 1rem 1rem 1rem 3rem;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: #f7fafc;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #667eea;
            background: white;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .form-group input:focus + i {
            color: #667eea;
        }
        
        .btn-login {
            width: 100%;
            padding: 1rem;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .btn-login::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s;
        }
        
        .btn-login:hover::before {
            left: 100%;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3);
        }
        
        .error-message {
            background: linear-gradient(135deg, #fed7d7, #feb2b2);
            color: #c53030;
            padding: 1rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            border-left: 4px solid #e53e3e;
            font-weight: 500;
        }
        
        .roles-info {
            margin-top: 2rem;
            padding: 1.5rem;
            background: linear-gradient(135deg, #f7fafc, #edf2f7);
            border-radius: 16px;
            font-size: 0.85rem;
            color: #4a5568;
            border: 1px solid #e2e8f0;
        }
        
        .roles-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 0.5rem;
            margin-top: 1rem;
        }
        
        .role-badge {
            background: white;
            padding: 0.5rem;
            border-radius: 8px;
            font-size: 0.75rem;
            font-weight: 500;
            color: #667eea;
            border: 1px solid #e2e8f0;
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .login-container {
            animation: fadeInUp 0.6s ease-out;
        }
        
        @media (max-width: 480px) {
            .login-container {
                margin: 1rem;
                padding: 2rem;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <div class="logo">
                <i class="fas fa-hospital"></i>
            </div>
            <h1>DME Hospital</h1>
            <p>Système de gestion hospitalière</p>
        </div>
        
        <?php if (isset($error)): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-triangle"></i>
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="<?= BASE_URL ?>login">
            <div class="form-group">
                <label for="identifiant">Email ou nom d'utilisateur</label>
                <div class="input-wrapper">
                    <input type="text" id="identifiant" name="identifiant" required>
                    <i class="fas fa-user"></i>
                </div>
            </div>
            
            <div class="form-group">
                <label for="password">Mot de passe</label>
                <div class="input-wrapper">
                    <input type="password" id="password" name="password" required>
                    <i class="fas fa-lock"></i>
                </div>
            </div>
            
            <button type="submit" class="btn-login">
                <i class="fas fa-sign-in-alt" style="margin-right: 0.5rem;"></i>
                Se connecter
            </button>
        </form>
        
        <div class="roles-info">
            <strong><i class="fas fa-users"></i> Rôles disponibles</strong>
            <div class="roles-grid">
                <div class="role-badge">Administrateur</div>
                <div class="role-badge">Médecin</div>
                <div class="role-badge">Infirmier</div>
                <div class="role-badge">Accueil</div>
                <div class="role-badge">Pharmacien</div>
                <div class="role-badge">Laborantin</div>
                <div class="role-badge">Gestionnaire</div>
            </div>
        </div>
    </div>
</body>
</html>