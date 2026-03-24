<?php

require_once __DIR__ . '/../../config/database.php';

class FamilleService {
    private $db;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }
    
    public function ajouterMembre($patient_id, $data) {
        $stmt = $this->db->prepare("
            INSERT INTO famille_membres (patient_id, nom, prenom, relation, telephone, email, contact_urgence) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        return $stmt->execute([
            $patient_id, $data['nom'], $data['prenom'], $data['relation'],
            $data['telephone'], $data['email'], $data['contact_urgence'] ?? false
        ]);
    }
    
    public function getMembres($patient_id) {
        $stmt = $this->db->prepare("
            SELECT * FROM famille_membres 
            WHERE patient_id = ? 
            ORDER BY contact_urgence DESC, nom ASC
        ");
        $stmt->execute([$patient_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function planifierVisite($patient_id, $data) {
        $stmt = $this->db->prepare("
            INSERT INTO famille_visites (patient_id, visiteur_id, nom_visiteur, relation, date_visite, duree_minutes, notes) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        return $stmt->execute([
            $patient_id, $data['visiteur_id'] ?? null, $data['nom_visiteur'],
            $data['relation'], $data['date_visite'], $data['duree_minutes'] ?? 30, $data['notes'] ?? ''
        ]);
    }
    
    public function getVisites($patient_id, $date_debut = null, $date_fin = null) {
        $sql = "
            SELECT v.*, fm.nom as membre_nom, fm.prenom as membre_prenom
            FROM famille_visites v
            LEFT JOIN famille_membres fm ON fm.id = v.visiteur_id
            WHERE v.patient_id = ?
        ";
        $params = [$patient_id];
        
        if ($date_debut) {
            $sql .= " AND v.date_visite >= ?";
            $params[] = $date_debut;
        }
        if ($date_fin) {
            $sql .= " AND v.date_visite <= ?";
            $params[] = $date_fin;
        }
        
        $sql .= " ORDER BY v.date_visite DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function autoriserVisite($visite_id, $autorise = true) {
        $stmt = $this->db->prepare("UPDATE famille_visites SET autorise = ? WHERE id = ?");
        return $stmt->execute([$autorise, $visite_id]);
    }
    
    public function getVisitesAujourdhui() {
        $stmt = $this->db->prepare("
            SELECT v.*, p.nom as patient_nom, p.prenom as patient_prenom
            FROM famille_visites v
            JOIN patients p ON p.id = v.patient_id
            WHERE DATE(v.date_visite) = CURDATE() AND v.autorise = 1
            ORDER BY v.date_visite ASC
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}