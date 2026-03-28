<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - DME Hospital</title>
    <!-- Icônes Bootstrap -->
    <!--<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">-->
    <!-- Google Fonts -->
    <!--<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;800&display=swap" rel="stylesheet">-->

     <link rel="stylesheet" href="<?= BASE_URL ?>public/css/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>public/css/fonts.css">


    <style>
        :root {
            --glass-bg: rgba(255, 255, 255, 0.4);
            --glass-border: rgba(255, 255, 255, 0.2);
            --primary-blue: #4f46e5;
            --text-dark: #1e293b;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            /* Fond animé dynamique aux couleurs douces */
            background: linear-gradient(-45deg, #667eea, #764ba2, #2dd4bf, #3b82f6);
            background-size: 400% 400%;
            animation: gradientBG 15s ease infinite;
            overflow: hidden;
            position: relative;
        }

        @keyframes gradientBG {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        /* Effet Gouttes d'eau/Bulles en arrière-plan */
        .drop {
            position: absolute;
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(5px);
            border-radius: 50%;
            z-index: 1;
            animation: float 20s infinite linear;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        @keyframes float {
            0% { transform: translateY(110vh) scale(0.8); opacity: 0; }
            10% { opacity: 1; }
            90% { opacity: 1; }
            100% { transform: translateY(-20vh) scale(1.2); opacity: 0; }
        }

        .glass-card {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: 35px;
            padding: 40px;
            width: 90%;
            max-width: 420px;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.1);
            text-align: center;
            position: relative;
            z-index: 10;
        }

        .logo-box {
            background: white;
            width: 70px;
            height: 70px;
            border-radius: 20px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
            box-shadow: 0 10px 20px rgba(0,0,0,0.05);
            color: var(--primary-blue);
            font-size: 2rem;
        }

        .login-header h2 {
            margin: 0;
            color: var(--text-dark);
            font-weight: 800;
        }

        .login-header p {
            color: #64748b;
            font-size: 0.9rem;
            margin-bottom: 30px;
        }

        .form-group {
            text-align: left;
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            font-size: 0.75rem;
            font-weight: 800;
            color: var(--text-dark);
            margin-bottom: 8px;
            margin-left: 5px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .input-glass {
            background: rgba(255, 255, 255, 0.5);
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 15px;
            padding: 14px 18px;
            width: 100%;
            outline: none;
            transition: all 0.3s ease;
            font-size: 1rem;
            color: var(--text-dark);
        }

        .input-glass:focus {
            background: rgba(255, 255, 255, 0.9);
            border-color: var(--primary-blue);
            box-shadow: 0 0 20px rgba(79, 70, 229, 0.1);
        }

        .btn-modern {
            background: var(--text-dark);
            color: white;
            border: none;
            padding: 16px;
            border-radius: 15px;
            width: 100%;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-top: 10px;
        }

        .btn-modern:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.2);
            background: #000;
        }

        .roles-info {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.2);
            font-size: 0.8rem;
            color: #64748b;
        }

        .error-msg {
            background: rgba(239, 68, 68, 0.1);
            color: #b91c1c;
            padding: 12px;
            border-radius: 12px;
            margin-bottom: 20px;
            font-size: 0.85rem;
            border: 1px solid rgba(239, 68, 68, 0.2);
        }
    </style>
</head>
<body>
    <!-- Bulles d'eau décoratives -->
    <div class="drop" style="width:120px; height:120px; left:5%; animation-delay: 0s;"></div>
    <div class="drop" style="width:80px; height:80px; left:15%; animation-delay: 4s;"></div>
    <div class="drop" style="width:100px; height:100px; right:10%; animation-delay: 2s;"></div>
    <div class="drop" style="width:50px; height:50px; right:25%; animation-delay: 7s;"></div>

    <div class="glass-card">
        <div class="login-header">
            <div class="logo-box">
                <i class="bi bi-hospital"></i>
            </div>
            <h2>DME Hospital</h2>
            <p>Portail de gestion clinique sécurisé</p>
        </div>

        <?php if (isset($error)): ?>
            <div class="error-msg">
                <i class="bi bi-exclamation-circle-fill me-2"></i> <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="<?= BASE_URL ?>login">
            <div class="form-group">
                <label>Identifiant</label>
                <input type="text" name="identifiant" class="input-glass" placeholder="Nom d'utilisateur ou Email" required>
            </div>

            <div class="form-group">
                <label>Mot de passe</label>
                <input type="password" name="password" class="input-glass" placeholder="••••••••" required>
            </div>

            <button type="submit" class="btn-modern">
                ACCÉDER À MA SESSION <i class="bi bi-arrow-right"></i>
            </button>
        </form>

        <div class="roles-info">
            <p><i class="bi bi-shield-lock-fill me-1"></i> Connexion sécurisée SSL 256-bit</p>
        </div>
    </div>
</body>
</html>