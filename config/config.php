<?php
// Ne pas démarrer la session ici car elle est déjà démarrée dans index.php
date_default_timezone_set('Africa/Douala');

define('BASE_URL', 'http://localhost:8080/dme_hospital/');
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
define('DB_PASS', 'root');

// ==================================================================
// CONFIGURATION GÉNÉRALE
// ==================================================================
$config = [
    'app_name' => 'DME Hospital',
    'version' => '1.0.0',
    'lang' => 'fr',
    'debug' => true
];
?>