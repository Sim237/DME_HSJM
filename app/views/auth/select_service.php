<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Sélection du Service - DME</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f0f4f8; height: 100vh; display: flex; align-items: center; justify-content: center; font-family: 'Inter', sans-serif; }
        .service-card { background: white; padding: 40px; border-radius: 24px; box-shadow: 0 20px 40px rgba(0,0,0,0.05); width: 100%; max-width: 450px; text-align: center; }
        .icon-box { width: 80px; height: 80px; background: #e0f2fe; color: #0ea5e9; border-radius: 20px; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px; font-size: 2rem; }
    </style>
</head>
<body>
    <div class="service-card shadow-lg">
        <div class="icon-box"><i class="bi bi-hospital"></i></div>
        <h4 class="fw-bold mb-1">Confirmation du Service</h4>
        <p class="text-muted small mb-4">Veuillez sélectionner votre service d'affectation pour cette session.</p>

        <?php if(isset($error)): ?>
            <div class="alert alert-danger small py-2"><?= $error ?></div>
        <?php endif; ?>

        <form action="<?= BASE_URL ?>verify-service" method="POST">
            <div class="mb-4 text-start">
                <label class="form-label small fw-bold">SERVICE ACTUEL</label>
                <select name="service_id" class="form-select form-select-lg border-2" required>
                    <option value="">-- Choisir un service --</option>
                    <?php foreach($services as $s): ?>
                        <option value="<?= $s['id'] ?>"><?= $s['nom_service'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="btn btn-primary btn-lg w-100 rounded-pill shadow-sm">
                Accéder au service
            </button>
            <div class="mt-3">
                <a href="<?= BASE_URL ?>logout" class="text-muted small text-decoration-none">Annuler et se déconnecter</a>
            </div>
        </form>
    </div>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
</body>
</html>