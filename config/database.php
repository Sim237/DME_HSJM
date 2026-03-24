<?php
/* ============================================================
 * FICHIER : config/database.php
 * Connexion PDO — utilise les constantes chargées depuis .env
 * ============================================================ */

class Database {
    private $host;
    private $db_name;
    private $username;
    private $password;
    public  $conn;

    public function __construct() {
        // Lire depuis les constantes définies par config.php (.env)
        $this->host     = defined('DB_HOST') ? DB_HOST : 'localhost';
        $this->db_name  = defined('DB_NAME') ? DB_NAME : 'dme_hospital';
        $this->username = defined('DB_USER') ? DB_USER : 'root';
        $this->password = defined('DB_PASS') ? DB_PASS : '';
    }

    public function getConnection() {
        $this->conn = null;
        try {
            $this->conn = new PDO(
                "mysql:host={$this->host};dbname={$this->db_name};charset=utf8",
                $this->username,
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            if (defined('APP_DEBUG') && APP_DEBUG) {
                die('<div style="font-family:sans-serif;padding:30px;background:#f8d7da;border:1px solid #f5c2c7;border-radius:8px;margin:40px auto;max-width:600px;">
                    <h3 style="color:#842029;">❌ Erreur de connexion MySQL</h3>
                    <p><strong>Message :</strong> ' . htmlspecialchars($e->getMessage()) . '</p>
                    <p><strong>Vérifiez votre fichier <code>.env</code> :</strong></p>
                    <ul>
                        <li>DB_HOST, DB_NAME, DB_USER, DB_PASS sont-ils corrects ?</li>
                        <li>MAMP / MySQL est-il démarré ?</li>
                        <li>La base <code>' . DB_NAME . '</code> existe-t-elle dans phpMyAdmin ?</li>
                    </ul>
                </div>');
            } else {
                die('Erreur de connexion à la base de données.');
            }
        }
        return $this->conn;
    }
}
?>
