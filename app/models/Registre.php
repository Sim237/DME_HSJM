<?php
require_once __DIR__ . '/../../config/database.php';

class Registre {
    private $db;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }
    
    // Registres de don de sang
    public function addDonneurSang($data) {
        $sql = "INSERT INTO registre_donneurs_sang 
                (nom, prenom, date_naissance, sexe, groupe_sanguin, rhesus, 
                 telephone, email, adresse, date_inscription, statut) 
                VALUES 
                (:nom, :prenom, :date_naissance, :sexe, :groupe_sanguin, :rhesus,
                 :telephone, :email, :adresse, NOW(), 'ACTIF')";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($data);
    }
    
    public function getDonneursSang($limit = null) {
        $sql = "SELECT * FROM registre_donneurs_sang WHERE statut = 'ACTIF' ORDER BY nom, prenom";
        if ($limit) $sql .= " LIMIT :limit";
        
        $stmt = $this->db->prepare($sql);
        if ($limit) $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Registres cellules souches
    public function addDonneurCSH($data) {
        $sql = "INSERT INTO registre_donneurs_csh 
                (nom, prenom, date_naissance, sexe, hla_typing, telephone, email, 
                 adresse, date_inscription, statut) 
                VALUES 
                (:nom, :prenom, :date_naissance, :sexe, :hla_typing, :telephone, 
                 :email, :adresse, NOW(), 'ACTIF')";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($data);
    }
    
    public function addReceveurCSH($data) {
        $sql = "INSERT INTO registre_receveurs_csh 
                (nom, prenom, date_naissance, sexe, hla_typing, pathologie, 
                 urgence, telephone, email, date_inscription, statut) 
                VALUES 
                (:nom, :prenom, :date_naissance, :sexe, :hla_typing, :pathologie,
                 :urgence, :telephone, :email, NOW(), 'EN_ATTENTE')";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($data);
    }
    
    // Registres maladies chroniques
    public function addMaladieChronique($data) {
        $sql = "INSERT INTO registre_maladies_chroniques 
                (patient_id, type_maladie, date_diagnostic, stade, traitement_actuel, 
                 medecin_referent, date_inscription) 
                VALUES 
                (:patient_id, :type_maladie, :date_diagnostic, :stade, :traitement_actuel,
                 :medecin_referent, NOW())";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($data);
    }
    
    public function getMaladiesChroniques($type = null) {
        $sql = "SELECT rmc.*, p.nom, p.prenom, p.dossier_numero 
                FROM registre_maladies_chroniques rmc 
                JOIN patients p ON rmc.patient_id = p.id";
        
        if ($type) {
            $sql .= " WHERE rmc.type_maladie = :type";
        }
        
        $sql .= " ORDER BY p.nom, p.prenom";
        
        $stmt = $this->db->prepare($sql);
        if ($type) $stmt->bindValue(':type', $type);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Statistiques
    public function getStatistiques() {
        $stats = [];
        
        try {
            // Donneurs de sang
            $sql = "SELECT COUNT(*) as total FROM registre_donneurs_sang WHERE statut = 'ACTIF'";
            $stmt = $this->db->query($sql);
            $stats['donneurs_sang'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        } catch (PDOException $e) {
            $stats['donneurs_sang'] = 0;
        }
        
        try {
            // Donneurs CSH
            $sql = "SELECT COUNT(*) as total FROM registre_donneurs_csh WHERE statut = 'ACTIF'";
            $stmt = $this->db->query($sql);
            $stats['donneurs_csh'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        } catch (PDOException $e) {
            $stats['donneurs_csh'] = 0;
        }
        
        try {
            // Receveurs CSH
            $sql = "SELECT COUNT(*) as total FROM registre_receveurs_csh WHERE statut = 'EN_ATTENTE'";
            $stmt = $this->db->query($sql);
            $stats['receveurs_csh'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        } catch (PDOException $e) {
            $stats['receveurs_csh'] = 0;
        }
        
        try {
            // Maladies chroniques
            $sql = "SELECT type_maladie, COUNT(*) as total FROM registre_maladies_chroniques GROUP BY type_maladie";
            $stmt = $this->db->query($sql);
            $stats['maladies_chroniques'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $stats['maladies_chroniques'] = [];
        }
        
        return $stats;
    }
    
    // Méthodes pour recherche unifiée
    public function searchDonneursSang($query) {
        try {
            $sql = "SELECT * FROM registre_donneurs_sang 
                    WHERE (nom LIKE :query OR prenom LIKE :query) 
                    AND statut = 'ACTIF' LIMIT 5";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':query' => "%$query%"]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }
    
    public function getMaladiesChroniquesPatient($patient_id) {
        try {
            $sql = "SELECT * FROM registre_maladies_chroniques WHERE patient_id = :patient_id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':patient_id' => $patient_id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }
}
?>