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

class Database {
    private $host;
    private $db_name;
    private $username;
    private $password;
    public $conn;

    /** Singleton PDO — une seule connexion TCP par cycle de requête PHP */
    private static ?PDO $_sharedPdo = null;

    public function __construct() {
        $this->host     = DB_HOST;
        $this->db_name  = DB_NAME;
        $this->username = DB_USER;
        $this->password = DB_PASS;
    }

    public function getConnection(): PDO {
        if (self::$_sharedPdo !== null) {
            return self::$_sharedPdo;
        }
        try {
            $pdo = new PDO(
                "mysql:host={$this->host};dbname={$this->db_name};charset=utf8mb4",
                $this->username,
                $this->password,
                [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]
            );
            self::$_sharedPdo = $pdo;
            $this->conn = $pdo;
        } catch (PDOException $e) {
            error_log('[Database] Connexion échouée : ' . $e->getMessage());
            http_response_code(503);
            die('Service temporairement indisponible. Veuillez réessayer dans quelques instants.');
        }
        return self::$_sharedPdo;
    }

    /** Réinitialise la connexion partagée (tests / reconnexion forcée) */
    public static function resetConnection(): void {
        self::$_sharedPdo = null;
    }
}