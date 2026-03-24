<?php
/* ============================================================
 * FICHIER : config/config.php
 * Configuration centrale — charge les variables depuis .env
 * Chaque développeur a son propre .env (non commité sur git)
 * ============================================================ */

date_default_timezone_set('Africa/Douala');

define('BASE_URL', 'http://localhost:8080/');
define('UPLOAD_PATH', __DIR__ . '/../assets/uploads/');

// ==================================================================
// CONFIGURATION DE LA BASE DE DONNÉES (C'est ce qu'il manquait !)
// ==================================================================
define('DB_HOST', 'localhost');
define('DB_NAME', 'dme_hospital'); // Vérifiez que c'est bien le nom exact dans phpMyAdmin
define('DB_USER', 'root');

// ATTENTION ICI :
// Si vous êtes sur XAMPP (Windows) : Laissez vide ''
// Si vous êtes sur MAMP (Mac) : Mettez 'root'
// Si vous avez défini un mot de passe personnel : Mettez-le ici
define('DB_PASS', 'root'); // Mot de passe MySQL local MAMP (défaut)

// ==================================================================
// CONFIGURATION GÉNÉRALE
// ==================================================================
$config = [
    'app_name' => 'DME Hospital — HSJM',
    'version'  => '1.0.0',
    'lang'     => 'fr',
    'debug'    => APP_DEBUG,
    'env'      => APP_ENV,
];
?>
