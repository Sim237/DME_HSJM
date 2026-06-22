<?php
/**
 * SimCare+ — Dossier Médical Électronique (DME)
 * Copyright (c) 2024-2026 Franck Simeni. Tous droits réservés.
 * Développé pour la gestion hospitalière, et le bien être numérique des patients.
 *
 * Toute reproduction, modification ou distribution de ce logiciel,
 * en tout ou en partie, sans autorisation écrite préalable de l'auteur
 * est strictement interdite et constitue une contrefaçon.
 *
 * Protected under OAPI Agreement — Annexe VII · Berne Convention
 */

/* ============================================================
 * FICHIER : config/config.php
 * Configuration centrale — toutes les valeurs locales viennent
 * du fichier .env (jamais commité sur git)
 *
 * ⚠️  NE JAMAIS hardcoder DB_PASS, BASE_URL ici !
 *     Chaque développeur édite son propre fichier .env
 * ============================================================ */

date_default_timezone_set('Africa/Douala');

// ── Configuration session ────────────────────────────────────────────────────
// Durée de vie portée à 2h (par défaut PHP = 24 min, trop court pour une consultation)
if (session_status() === PHP_SESSION_NONE) {
    $sessionLifetime = 7200; // 2 heures
    ini_set('session.gc_maxlifetime', $sessionLifetime);
    ini_set('session.cookie_lifetime', $sessionLifetime);
    // Sécurité cookies de session
    ini_set('session.cookie_httponly', '1');   // Inaccessible à JavaScript
    ini_set('session.cookie_samesite', 'Lax'); // Protection CSRF partielle
    // En production : forcer HTTPS pour les cookies
    if (getenv('APP_ENV') === 'production') {
        ini_set('session.cookie_secure', '1');
    }
}

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

// --- BASE_URL dynamique : s'adapte au protocole/host réel du navigateur ---
// En environnement CLI (cron, artisan…), on tombe sur la valeur .env
function _computeBaseUrl() {
    if (php_sapi_name() === 'cli') {
        return getenv('BASE_URL') ?: 'http://localhost/dme_hospital/';
    }
    $scheme   = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host     = $_SERVER['HTTP_HOST'] ?? 'localhost';   // inclut le port si non-standard
    // Chemin de base : on extrait le sous-dossier depuis REQUEST_URI
    // En général /dme_hospital/... → base = /dme_hospital/
    $script   = $_SERVER['SCRIPT_NAME'] ?? '/index.php';   // ex: /dme_hospital/index.php
    $base     = rtrim(dirname($script), '/\\') . '/';       // ex: /dme_hospital/
    return $scheme . '://' . $host . $base;
}

// --- Constantes (toutes depuis .env, jamais hardcodées ici) ---
define('BASE_URL',    _computeBaseUrl());
define('DB_HOST',     getenv('DB_HOST')   ?: 'localhost');
define('DB_NAME',     getenv('DB_NAME')   ?: 'dme_hospital');
define('DB_USER',     getenv('DB_USER')   ?: 'root');
define('DB_PASS',     getenv('DB_PASS')   ?: '');
define('APP_ENV',     getenv('APP_ENV')   ?: 'development');
define('APP_DEBUG',   getenv('APP_DEBUG') === 'true');
define('DEBUG_MODE',  APP_ENV !== 'production'); // Actif en dev, inactif en prod
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
