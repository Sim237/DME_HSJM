<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accès refusé - DME Hospital</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>public/css/style.css">
    <style>
        body {
            background: var(--bg-light);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: var(--font-family);
        }
        
        .error-container {
            text-align: center;
            max-width: 500px;
            padding: 2rem;
        }
        
        .error-code {
            font-size: 6rem;
            font-weight: bold;
            color: var(--danger-color);
            margin-bottom: 1rem;
        }
        
        .error-title {
            font-size: 2rem;
            color: var(--text-primary);
            margin-bottom: 1rem;
        }
        
        .error-message {
            color: var(--text-secondary);
            margin-bottom: 2rem;
            line-height: 1.6;
        }
        
        .btn-back {
            display: inline-block;
            padding: 0.75rem 1.5rem;
            background: var(--primary-color);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            transition: background-color 0.3s;
        }
        
        .btn-back:hover {
            background: var(--primary-dark);
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-code">403</div>
        <h1 class="error-title">Accès refusé</h1>
        <p class="error-message">
            Vous n'avez pas les permissions nécessaires pour accéder à cette page.<br>
            Votre rôle actuel : <strong><?= $_SESSION['user_role'] ?? 'Non connecté' ?></strong>
        </p>
        <a href="<?= BASE_URL ?>" class="btn-back">Retour au tableau de bord</a>
    </div>
</body>
</html>