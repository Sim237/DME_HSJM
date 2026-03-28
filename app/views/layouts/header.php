<?php
// Vérification pour éviter les inclusions multiples
if (!defined('HEADER_RENDERED')) {
    define('HEADER_RENDERED', true);
} else {
    return; // Si déjà inclus, on s'arrête là
}

// Assurer que BASE_URL est défini
if (!defined('BASE_URL')) {
    define('BASE_URL', '/dme_hospital/');
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $config['app_name'] ?? 'DME Hospital - HSJM' ?></title>

    <!-- CHARGEMENT LOCAL (Pas de CDN) -->
    <!-- Bootstrap 5 CSS -->
    <link rel="stylesheet" href="<?= BASE_URL ?>public/css/bootstrap.min.css">
    <!-- FontAwesome (Local) -->
    <link rel="stylesheet" href="<?= BASE_URL ?>public/css/all.min.css">
    <!-- Bootstrap Icons (Local) -->
    <link rel="stylesheet" href="<?= BASE_URL ?>public/css/bootstrap-icons.min.css">
    <!-- Polices locales -->
    <link rel="stylesheet" href="<?= BASE_URL ?>public/css/fonts.css">
    <!-- Styles personnalisés -->
    <link rel="stylesheet" href="<?= BASE_URL ?>public/css/style.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>public/css/consultation-forms.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>public/css/responsive.css">

    <link rel="stylesheet" href="<?= BASE_URL ?>public/css/fontawesome/all.min.css">

    <!-- Script de base (chargé tôt si besoin) -->
    <script>
        const BASE_URL = "<?= BASE_URL ?>";
    </script>
</head>
<body>