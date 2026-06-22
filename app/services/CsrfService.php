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
   FICHIER : app/services/CsrfService.php
   Protection CSRF — génération et validation de tokens
   ============================================================ */

class CsrfService {

    const TOKEN_KEY = '_csrf_token';

    /** Retourne le token CSRF de la session (le crée si absent) */
    public static function getToken(): string {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (empty($_SESSION[self::TOKEN_KEY])) {
            $_SESSION[self::TOKEN_KEY] = bin2hex(random_bytes(32));
        }
        return $_SESSION[self::TOKEN_KEY];
    }

    /** Renouvelle le token (appeler après une action sensible) */
    public static function regenerate(): string {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $_SESSION[self::TOKEN_KEY] = bin2hex(random_bytes(32));
        return $_SESSION[self::TOKEN_KEY];
    }

    /**
     * Valide le token reçu (POST body ou header X-CSRF-Token).
     * Utilise hash_equals() pour éviter les timing attacks.
     */
    public static function validate(): bool {
        $received = $_POST[self::TOKEN_KEY]
                 ?? $_SERVER['HTTP_X_CSRF_TOKEN']
                 ?? '';
        if ($received === '') return false;
        return hash_equals(self::getToken(), $received);
    }

    /** Champ hidden HTML à insérer dans les formulaires POST */
    public static function field(): string {
        return '<input type="hidden" name="' . self::TOKEN_KEY
             . '" value="' . htmlspecialchars(self::getToken(), ENT_QUOTES, 'UTF-8') . '">';
    }

    /** Balise <meta> pour les requêtes AJAX */
    public static function meta(): string {
        return '<meta name="csrf-token" content="'
             . htmlspecialchars(self::getToken(), ENT_QUOTES, 'UTF-8') . '">';
    }

    /**
     * Vérifie le token et arrête l'exécution avec 403 si invalide.
     * À appeler dans les controllers pour les routes POST sensibles.
     */
    public static function check(): void {
        if (!self::validate()) {
            http_response_code(403);
            $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
                   || !empty($_SERVER['HTTP_X_CSRF_TOKEN'])
                   || (($_SERVER['HTTP_ACCEPT'] ?? '') === 'application/json');
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Session expirée. Veuillez recharger la page.']);
            } else {
                echo '<!DOCTYPE html><html><body><h2>403 — Requête invalide</h2>'
                   . '<p>Token de sécurité manquant ou expiré. '
                   . '<a href="javascript:history.back()">Retour</a></p></body></html>';
            }
            exit;
        }
    }
}

/* Helper global utilisable directement dans les vues */
if (!function_exists('csrf_field')) {
    function csrf_field(): string { return CsrfService::field(); }
}
if (!function_exists('csrf_meta')) {
    function csrf_meta(): string { return CsrfService::meta(); }
}
