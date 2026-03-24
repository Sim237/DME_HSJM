<?php
/* ============================================================
 * FICHIER : config/config.php
 * Configuration centrale — toutes les valeurs locales viennent
 * du fichier .env (jamais commité sur git)
 *
 * ⚠️  NE JAMAIS hardcoder DB_PASS, BASE_URL ici !
 *     Chaque développeur édite son propre fichier .env
 * ============================================================ */

date_default_timezone_set('Africa/Douala');

// --- Chargeur .env ---
function loadEnv($file) {
    if (!file_exists($file)) {
        die('
        <div style="font-family:sans-serif;padding:30px;background:#fff3cd;
                    border:2px solid #ffc107;border-radius:8px;max-width:600px;margin:60px auto;">
            <h2 style="color:#856404;">⚠️ Fichier .env introuvable</h2>
            <p>Copiez <code>.env.example</code> en <code>.env</code> à la racine du projet :</p>
            <pre style="background:#f8f9fa;padding:12px;border-radius:4px;font-size:14px;">cp .env.example .env</pre>
            <p>Puis éditez <code>.env</code> avec vos identifiants locaux.</p>
        </div>');
    }
    $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || strpos($line, '#') === 0) continue;
        if (preg_match('/^([A-Za-z0-9_]+)\s*=\s*(.*)$/', $line, $matches)) {
            putenv($matches[1] . '=' . $matches[2]);
            $_ENV[$matches[1]] = $matches[2];
        }
    }
}

// --- Charger le .env depuis la racine du projet ---
loadEnv(__DIR__ . '/../.env');

// --- Constantes (toutes depuis .env, jamais hardcodées ici) ---
define('BASE_URL',    getenv('BASE_URL')  ?: 'http://localhost:8080/');
define('DB_HOST',     getenv('DB_HOST')   ?: 'localhost');
define('DB_NAME',     getenv('DB_NAME')   ?: 'dme_hospital');
define('DB_USER',     getenv('DB_USER')   ?: 'root');
define('DB_PASS',     getenv('DB_PASS')   ?: '');
define('APP_ENV',     getenv('APP_ENV')   ?: 'development');
define('APP_DEBUG',   getenv('APP_DEBUG') === 'true');
define('UPLOAD_PATH', __DIR__ . '/../assets/uploads/');

// --- Configuration générale ---
$config = [
    'app_name' => 'DME Hospital — HSJM',
    'version'  => '1.0.0',
    'lang'     => 'fr',
    'debug'    => APP_DEBUG,
    'env'      => APP_ENV,
];
?>
