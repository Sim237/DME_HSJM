<?php
require_once __DIR__ . '/../../config/database.php';

class Examen {
    private $db;
    protected $table = 'examens';
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }
    
    public function countEnAttente() {
        $sql = "SELECT COUNT(*) as total FROM examens WHERE statut = 'EN_ATTENTE'";
        $stmt = $this->db->query($sql);
        return $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    }
    
    public function countUrgent() {
        // Basé sur les données, il semble qu'il n'y ait pas de colonne priorité
        // On retourne simplement le nombre d'examens en attente
        $sql = "SELECT COUNT(*) as total FROM examens WHERE statut = 'EN_ATTENTE'";
        $stmt = $this->db->query($sql);
        return $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    }
    
    public function getStatsGraph($periode) {
        $sql = "SELECT DATE(date_demande) as date, COUNT(*) as total 
                FROM examens 
                WHERE date_demande >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                GROUP BY DATE(date_demande)
                ORDER BY date";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>