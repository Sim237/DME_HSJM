<?php
/**
 * GardeService — vérifie si l'utilisateur courant a une garde active.
 * Résultat mis en cache dans la session (TTL 60s) + cache statique par requête.
 */
class GardeService
{
    private static ?bool $cache = null;

    /**
     * Renvoie true si le user a une garde en cours (date_debut <= NOW <= date_fin, statut='en_cours').
     * $userId = null → utilise $_SESSION['user_id'] ou temp_user['id']
     */
    public static function isOnDuty(?int $userId = null): bool
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        $uid = $userId
            ?? (int)($_SESSION['user_id'] ?? $_SESSION['temp_user']['id'] ?? 0);

        if (!$uid) return false;

        // Cache par requête HTTP
        if (self::$cache !== null) return self::$cache;

        // Cache session (60 s)
        $now = time();
        if (isset($_SESSION['_garde_uid'], $_SESSION['_garde_checked_at'])
            && $_SESSION['_garde_uid'] === $uid
            && ($now - (int)$_SESSION['_garde_checked_at']) < 60
        ) {
            self::$cache = !empty($_SESSION['garde_active']);
            return self::$cache;
        }

        // Vérification DB
        try {
            require_once __DIR__ . '/../../config/database.php';
            $db   = (new Database())->getConnection();
            $stmt = $db->prepare(
                "SELECT COUNT(*) FROM gardes
                  WHERE medecin_id = ?
                    AND statut     = 'en_cours'
                    AND NOW() BETWEEN date_debut AND date_fin"
            );
            $stmt->execute([$uid]);
            $active = (int)$stmt->fetchColumn() > 0;
        } catch (Exception $e) {
            $active = false;
        }

        $_SESSION['garde_active']      = $active;
        $_SESSION['_garde_checked_at'] = $now;
        $_SESSION['_garde_uid']        = $uid;
        self::$cache = $active;

        return $active;
    }

    /** Invalide le cache (appeler après démarrage/fin d'une garde). */
    public static function reset(): void
    {
        self::$cache = null;
        unset($_SESSION['garde_active'], $_SESSION['_garde_checked_at'], $_SESSION['_garde_uid']);
    }

    /** Infos de la garde en cours pour l'utilisateur connecté. */
    public static function getActiveGarde(?int $userId = null): ?array
    {
        if (!self::isOnDuty($userId)) return null;
        $uid = $userId ?? (int)($_SESSION['user_id'] ?? 0);
        try {
            require_once __DIR__ . '/../../config/database.php';
            $db   = (new Database())->getConnection();
            $stmt = $db->prepare(
                "SELECT g.*, u.nom, u.prenom, u.role
                   FROM gardes g
                   JOIN users u ON u.id = g.medecin_id
                  WHERE g.medecin_id = ?
                    AND g.statut     = 'en_cours'
                    AND NOW() BETWEEN g.date_debut AND g.date_fin
                  LIMIT 1"
            );
            $stmt->execute([$uid]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (Exception $e) { return null; }
    }
}
