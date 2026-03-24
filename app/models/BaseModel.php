<?php
require_once __DIR__ . '/../../config/database.php';

class BaseModel {
    protected $db;
    protected $table;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }
    
    // Méthodes communes à tous les modèles
    public function findById($id) {
        $sql = "SELECT * FROM {$this->table} WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function getAll($limit = null, $offset = null) {
        $sql = "SELECT * FROM {$this->table}";
        if ($limit) {
            $sql .= " LIMIT :limit";
            if ($offset) $sql .= " OFFSET :offset";
        }
        
        $stmt = $this->db->prepare($sql);
        if ($limit) {
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            if ($offset) $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function count($where = null) {
        $sql = "SELECT COUNT(*) as total FROM {$this->table}";
        if ($where) $sql .= " WHERE $where";
        
        $stmt = $this->db->query($sql);
        return $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    }
    
    public function delete($id) {
        $sql = "DELETE FROM {$this->table} WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }
    
    // Recherche globale unifiée
    public function search($query, $fields = ['nom', 'prenom']) {
        $conditions = [];
        $params = [];
        
        foreach ($fields as $field) {
            $conditions[] = "$field LIKE :query";
        }
        
        $sql = "SELECT * FROM {$this->table} WHERE " . implode(' OR ', $conditions);
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':query' => "%$query%"]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>