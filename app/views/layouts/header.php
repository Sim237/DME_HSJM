<?php
// Sécurité : Si BASE_URL n'est pas défini, on le définit par défaut
if (!defined('BASE_URL')) {
    define('BASE_URL', '/dme_hospital/');
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $config['app_name'] ?? 'DME Hospital'; ?></title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

    <!-- Custom Styles -->
    <link href="<?php echo BASE_URL; ?>public/css/style.css" rel="stylesheet">
    <link href="<?php echo BASE_URL; ?>public/css/responsive.css" rel="stylesheet">
    <link href="<?php echo BASE_URL; ?>public/css/dashboard.css" rel="stylesheet">
    <link href="<?php echo BASE_URL; ?>public/css/consultation-forms.css" rel="stylesheet">
    <link href="<?php echo BASE_URL; ?>public/css/fix.css" rel="stylesheet">
</head>
<body>

<?php
// Indique que l'en-tête HTML principal a été rendu pour éviter les inclusions multiples
if (!defined('HEADER_RENDERED')) {
    define('HEADER_RENDERED', true);
}
?>
