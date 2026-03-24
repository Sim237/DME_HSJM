<?php
/* ============================================================
 * FICHIER : config/config.php
 * Configuration centrale — charge les variables depuis .env
 * Chaque développeur a son propre .env (non commité sur git)
 * ============================================================ */

date_default_timezone_set('Africa/Douala');

// --- Chargeur de fichier .env ---
function loadEnv($path) {
    if (!file_exists($path)) {
        die('<div style="font-family:sans-serif;padding:40px;background:#fff3cd;border:1px solid #ffc107;border-radius:8px;margin:40px auto;max-width:600px;">
            <h2 style="color:#856404;">⚠️ Fichier .env manquant</h2>
            <p>Le fichier <code>.env</code> est introuvable à la racine du projet.</p>
            <p><strong>Solution :</strong> Copier <code>.env.example</code> en <code>.env</code> et renseigner vos identifiants locaux.</p>
            <pre style="background:#f8f9fa;padding:12px;border-radius:4px;">cp .env.example .env</pre>
        </div>');
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        // Ignorer les commentaires
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') === false) continue;

        [$key, $value] = explode('=', $line, 2);
        $key   = trim($key);
        $value = trim($value);

        if (!empty($key)) {
            $_ENV[$key] = $value;
            putenv("$key=$value");
        }
    }
}

// Charger le .env depuis la racine du projet
loadEnv(__DIR__ . '/../.env');

// --- Définir les constantes depuis .env ---
define('BASE_URL',    getenv('BASE_URL')    ?: 'http://localhost:8080/');
define('DB_HOST',     getenv('DB_HOST')     ?: 'localhost');
define('DB_NAME',     getenv('DB_NAME')     ?: 'dme_hospital');
define('DB_USER',     getenv('DB_USER')     ?: 'root');
define('DB_PASS',     getenv('DB_PASS')     ?: '');
define('APP_ENV',     getenv('APP_ENV')     ?: 'development');
define('APP_DEBUG',   getenv('APP_DEBUG')   === 'true');
define('UPLOAD_PATH', __DIR__ . '/../assets/uploads/');

// --- Configuration générale de l'application ---
$config = [
    'app_name' => 'DME Hospital — HSJM',
    'version'  => '1.0.0',
    'lang'     => 'fr',
    'debug'    => APP_DEBUG,
    'env'      => APP_ENV,
];
?>
