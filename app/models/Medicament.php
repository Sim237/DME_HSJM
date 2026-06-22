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

class Medicament {
    private $db;
    
     public function __construct() {
        // CORRECTION : On remplace global $db par l'instanciation de la classe Database
        $database = new Database();
        $this->db = $database->getConnection();
    }
    
    public function getAll() {
        $sql = "SELECT * FROM medicaments ORDER BY nom";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getById($id) {
        $sql = "SELECT * FROM medicaments WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function getStock($medicament_id) {
        $sql = "SELECT quantite FROM medicaments WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $medicament_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $result['quantite'] : 0;
    }
    
    public function getStockFaible() {
        $sql = "SELECT * FROM medicaments 
                WHERE quantite <= seuil_alerte AND quantite > 0
                ORDER BY quantite ASC";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function create($data) {
        // Générer un code unique
        $code = $this->genererCode();
        
        $sql = "INSERT INTO medicaments 
                (code, nom, forme, dosage, quantite, unite, seuil_alerte)
                VALUES
                (:code, :nom, :forme, :dosage, :quantite, :unite, :seuil_alerte)";
        
        $stmt = $this->db->prepare($sql);
        
        return $stmt->execute([
            ':code' => $code,
            ':nom' => $data['nom'],
            ':forme' => $data['forme'],
            ':dosage' => $data['dosage'],
            ':quantite' => $data['quantite'],
            ':unite' => $data['unite'],
            ':seuil_alerte' => $data['seuil_alerte']
        ]);
    }
    
    public function updateStock($medicament_id, $quantite, $operation = 'add') {
        if ($operation === 'add') {
            $sql = "UPDATE medicaments SET quantite = quantite + :quantite WHERE id = :id";
        } else {
            $sql = "UPDATE medicaments SET quantite = quantite - :quantite WHERE id = :id AND quantite >= :quantite";
        }
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':quantite' => $quantite,
            ':id' => $medicament_id
        ]);
    }
    
    private function genererCode() {
        $sql = "SELECT COUNT(*) as total FROM medicaments";
        $stmt = $this->db->query($sql);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $numero = $result['total'] + 1;
        return 'MED' . str_pad($numero, 5, '0', STR_PAD_LEFT);
    }
}
?>