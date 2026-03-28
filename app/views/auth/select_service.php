<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sélection du Service - DME Hospital</title>
    <!--<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">-->
    <!--<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;800&display=swap" rel="stylesheet">-->

     <!-- CHARGEMENT LOCAL -->
    <link rel="stylesheet" href="<?= BASE_URL ?>public/css/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>public/css/fonts.css">
    <style>
        :root {
            --glass-bg: rgba(255, 255, 255, 0.4);
            --glass-border: rgba(255, 255, 255, 0.3);
            --primary-blue: #4f46e5;
            --text-dark: #1e293b;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(-45deg, #2dd4bf, #3b82f6, #6366f1, #2dd4bf);
            background-size: 400% 400%;
            animation: gradientBG 15s ease infinite;
            overflow: hidden;
        }

        @keyframes gradientBG {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        /* Particules d'eau */
        .drop {
            position: absolute;
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(5px);
            border-radius: 50%;
            z-index: 1;
            animation: float 20s infinite linear;
        }

        @keyframes float {
            0% { transform: translateY(110vh) scale(0.8); opacity: 0; }
            10% { opacity: 0.8; }
            100% { transform: translateY(-20vh) scale(1.2); opacity: 0; }
        }

        .glass-card {
            background: var(--glass-bg);
            backdrop-filter: blur(25px);
            -webkit-backdrop-filter: blur(25px);
            border: 1px solid var(--glass-border);
            border-radius: 40px;
            padding: 50px 40px;
            width: 90%;
            max-width: 440px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.15);
            text-align: center;
            position: relative;
            z-index: 10;
        }

        .icon-wrapper {
            position: relative;
            margin-bottom: 25px;
        }

        .icon-circle {
            background: var(--primary-blue);
            width: 70px;
            height: 70px;
            border-radius: 24px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 2rem;
            box-shadow: 0 10px 25px rgba(79, 70, 229, 0.4);
            transform: rotate(-10deg);
        }

        h3 {
            color: var(--text-dark);
            font-weight: 800;
            font-size: 1.8rem;
            margin-bottom: 8px;
        }

        .subtitle {
            color: #475569;
            font-size: 0.95rem;
            margin-bottom: 40px;
            opacity: 0.8;
        }

        .form-group {
            text-align: left;
            margin-bottom: 30px; /* Espace avant le bouton */
        }

        .form-label {
            display: block;
            font-size: 0.85rem;
            font-weight: 800;
            color: var(--text-dark);
            margin-bottom: 15px; /* CORRECTION : Espace entre label et select */
            margin-left: 5px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .input-glass {
            background: rgba(255, 255, 255, 0.6);
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 18px;
            padding: 16px 20px;
            width: 100%;
            outline: none;
            transition: all 0.3s ease;
            font-size: 1rem;
            color: var(--text-dark);
            font-weight: 600;
            cursor: pointer;
            appearance: none;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%23475569' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='m6 8 4 4 4-4'/%3e%3c/svg%3e");
            background-position: right 15px center;
            background-repeat: no-repeat;
            background-size: 18px;
        }

        .input-glass:focus {
            background: rgba(255, 255, 255, 0.9);
            border-color: var(--primary-blue);
            box-shadow: 0 10px 20px -5px rgba(79, 70, 229, 0.2);
        }

        .btn-modern {
            background: #0f172a;
            color: white;
            border: none;
            padding: 18px;
            border-radius: 18px;
            width: 100%;
            font-weight: 700;
            font-size: 1rem;
            letter-spacing: 1px;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }

        .btn-modern:hover {
            background: #000;
            transform: translateY(-3px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.2);
        }

        .btn-logout {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-top: 25px;
            color: #64748b;
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 600;
            transition: 0.3s;
        }

        .btn-logout:hover { color: #dc2626; }
    </style>
</head>
<body>
    <!-- Bulles d'eau -->
    <div class="drop" style="width:100px; height:100px; left:15%; animation-delay: 0s;"></div>
    <div class="drop" style="width:150px; height:150px; right:10%; animation-delay: 2s;"></div>
    <div class="drop" style="width:60px; height:60px; left:40%; animation-delay: 5s;"></div>

    <div class="glass-card">
        <div class="icon-wrapper">
            <div class="icon-circle">
                <i class="bi bi-geo-alt-fill"></i>
            </div>
        </div>

        <h3>Confirmation</h3>
        <p class="subtitle">Veuillez valider votre unité de travail</p>

        <form action="<?= BASE_URL ?>verify-service" method="POST">
            <div class="form-group">
                <label class="form-label">Unité de soin</label>
                <select name="service_id" class="input-glass" required>
                    <option value="">-- Choisir un service --</option>
                    <?php foreach($services as $s): ?>
                        <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['nom_service']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <button type="submit" class="btn-modern">
                CONFIRMER ET ENTRER
            </button>

            <a href="<?= BASE_URL ?>logout" class="btn-logout">
                <i class="bi bi-arrow-left-circle"></i> Annuler et se déconnecter
            </a>
        </form>
    </div>
</body>
</html>