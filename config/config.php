<?php
/* ============================================================
 * FICHIER : config/config.php
 * Configuration centrale — charge les variables depuis .env
 * Chaque développeur a son propre .env (non commité sur git)
 * ============================================================ */

date_default_timezone_set('Africa/Douala');

define('BASE_URL', 'http://localhost:8080/dme_hospital/');
define('UPLOAD_PATH', __DIR__ . '/../assets/uploads/');

// ==================================================================
// loadEnv function
function loadEnv($file) {
    if (!file_exists($file)) {
        error_log("loadEnv: .env file not found: " . $file);
        return;
    }
    $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || strpos($line, '#') === 0) continue;
        if (preg_match('/^([A-Za-z0-9_]+)\\s*=\\s*(.*)$/', $line, $matches)) {
            $value = $matches[2];
            putenv($matches[1] . '=' . $value);
        }
    }
}

// CONFIGURATION DE LA BASE DE DONNÉES (C'est ce qu'il manquait !)
// --- Charger le .env ---
loadEnv(__DIR__ . '/../.env');

// --- Définir les constantes depuis .env (TOUT définir avant d'utiliser) ---
define('DB_HOST',     getenv('DB_HOST')     ?: 'localhost');
define('DB_NAME',     getenv('DB_NAME')     ?: 'dme_hospital');
define('DB_USER',     getenv('DB_USER')     ?: 'root');
define('DB_PASS',     getenv('DB_PASS')     ?: '');
define('APP_ENV',     getenv('APP_ENV')     ?: 'development');
define('APP_DEBUG',   getenv('APP_DEBUG')   === 'true');   // ← doit être avant $config

// --- $config APRÈS les define() ---
$config = [
    'app_name' => 'DME Hospital — HSJM',
    'version'  => '1.0.0',
    'lang'     => 'fr',
    'debug'    => APP_DEBUG,   // ✅ APP_DEBUG est maintenant connu
    'env'      => APP_ENV,
];
?>

