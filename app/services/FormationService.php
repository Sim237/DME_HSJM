<?php

require_once __DIR__ . '/../../config/database.php';

class FormationService {
    private $db;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }
    
    public function creerFormation($data) {
        $stmt = $this->db->prepare("
            INSERT INTO formations (titre, description, categorie, duree_heures, obligatoire) 
            VALUES (?, ?, ?, ?, ?)
        ");
        return $stmt->execute([
            $data['titre'], $data['description'], $data['categorie'],
            $data['duree_heures'], $data['obligatoire'] ?? false
        ]);
    }
    
    public function planifierSession($formation_id, $data) {
        $stmt = $this->db->prepare("
            INSERT INTO formation_sessions (formation_id, formateur_id, date_debut, date_fin, lieu, places_max) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        return $stmt->execute([
            $formation_id, $data['formateur_id'], $data['date_debut'],
            $data['date_fin'], $data['lieu'], $data['places_max'] ?? 20
        ]);
    }
    
    public function inscrireUtilisateur($session_id, $user_id) {
        // Vérifier places disponibles
        $stmt = $this->db->prepare("
            SELECT places_max, COUNT(i.id) as inscrits
            FROM formation_sessions s
            LEFT JOIN formation_inscriptions i ON i.session_id = s.id
            WHERE s.id = ?
            GROUP BY s.id
        ");
        $stmt->execute([$session_id]);
        $session = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($session && $session['inscrits'] >= $session['places_max']) {
            return false; // Plus de places
        }
        
        $stmt = $this->db->prepare("
            INSERT INTO formation_inscriptions (session_id, user_id) 
            VALUES (?, ?)
        ");
        return $stmt->execute([$session_id, $user_id]);
    }
    
    public function getFormationsDisponibles($user_id = null) {
        $sql = "
            SELECT f.*, 
                   COUNT(fs.id) as sessions_disponibles,
                   MIN(fs.date_debut) as prochaine_session
            FROM formations f
            LEFT JOIN formation_sessions fs ON fs.formation_id = f.id AND fs.statut = 'planifie'
        ";
        
        if ($user_id) {
            $sql .= "
                LEFT JOIN formation_inscriptions fi ON fi.session_id = fs.id AND fi.user_id = ?
                WHERE fi.id IS NULL
            ";
        }
        
        $sql .= " GROUP BY f.id ORDER BY f.obligatoire DESC, f.titre ASC";
        
        $stmt = $this->db->prepare($sql);
        if ($user_id) {
            $stmt->execute([$user_id]);
        } else {
            $stmt->execute();
        }
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getFormationsUtilisateur($user_id) {
        $stmt = $this->db->prepare("
            SELECT f.titre, fs.date_debut, fs.date_fin, fi.statut, fi.note, fi.certificat_genere
            FROM formation_inscriptions fi
            JOIN formation_sessions fs ON fs.id = fi.session_id
            JOIN formations f ON f.id = fs.formation_id
            WHERE fi.user_id = ?
            ORDER BY fs.date_debut DESC
        ");
        $stmt->execute([$user_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function validerPresence($session_id, $user_id, $note = null) {
        $stmt = $this->db->prepare("
            UPDATE formation_inscriptions 
            SET statut = 'valide', note = ?, certificat_genere = 1
            WHERE session_id = ? AND user_id = ?
        ");
        return $stmt->execute([$note, $session_id, $user_id]);
    }
}