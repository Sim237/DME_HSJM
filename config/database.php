<?php
class Database {
    private $host = "localhost";
    private $db_name = "dme_hospital";
    private $username = "root";
    private $password = "Franck@2903";
    public $conn;

    public function getConnection() {
        $this->conn = null;
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name,
                $this->username,
                $this->password
            );
            $this->conn->exec("set names utf8");
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $e) {
            echo "Erreur de connexion: " . $e->getMessage();
        }
        return $this->conn;
    }
}
?>