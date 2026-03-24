<?php
/* ============================================================================
   FICHIER : Lit.php
   Modèle pour la gestion des lits et de l'occupation
   ============================================================================ */
require_once __DIR__ . '/../../config/database.php';

class Lit {
    private $db;
    
    public function __construct() {
        // Instanciation correcte de la connexion
        $database = new Database();
        $this->db = $database->getConnection();
    }
    
    // Récupérer tous les lits avec le statut réel et les infos patient si occupé
    public function getAll() {
        $sql = "SELECT l.*, s.nom as service,
                CASE 
                    WHEN a.id IS NOT NULL AND a.statut = 'EN_COURS' THEN 'OCCUPE'
                    ELSE l.statut
                END as statut_reel,
                p.nom as patient_nom, p.prenom as patient_prenom,
                a.date_admission, a.id as admission_id
                FROM lits l
                LEFT JOIN services s ON l.service_id = s.id
                LEFT JOIN admissions a ON l.id = a.lit_id AND a.statut = 'EN_COURS'
                LEFT JOIN patients p ON a.patient_id = p.id
                ORDER BY s.nom, l.numero";
        
        $stmt = $this->db->query($sql);
        $lits = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Mettre à jour le statut visuel
        foreach ($lits as &$lit) {
            $lit['statut'] = $lit['statut_reel'];
        }
        
        return $lits;
    }
    
    // Récupérer un lit spécifique par son ID
    public function getById($id) {
        $sql = "SELECT * FROM lits WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Récupérer les détails d'un lit occupé (utilisé pour transfert/décharge)
    public function getDetailsLitOccupe($lit_id) {
        $sql = "SELECT l.*, s.nom as service, 
                a.id as admission_id, a.date_admission, a.patient_id
                FROM lits l
                JOIN services s ON l.service_id = s.id
                JOIN admissions a ON l.id = a.lit_id
                WHERE l.id = :id AND a.statut = 'EN_COURS'";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $lit_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // Compter le nombre de lits occupés
    public function countOccupes() {
        $sql = "SELECT COUNT(DISTINCT l.id) as total 
                FROM lits l
                JOIN admissions a ON l.id = a.lit_id
                WHERE a.statut = 'EN_COURS'";
        $stmt = $this->db->query($sql);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'];
    }
    
    // Compter le nombre de lits disponibles
    public function countDisponibles() {
        $sql = "SELECT COUNT(*) as total 
                FROM lits l
                WHERE l.statut = 'DISPONIBLE'
                AND NOT EXISTS (
                    SELECT 1 FROM admissions a 
                    WHERE a.lit_id = l.id AND a.statut = 'EN_COURS'
                )";
        $stmt = $this->db->query($sql);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'];
    }
    
    // Statistiques pour le graphique
    public function getStatsAdmissions($periode) {
        $days = $periode === '30days' ? 30 : 7;
        
        $sql = "SELECT DATE(date_admission) as date, COUNT(*) as total
                FROM admissions
                WHERE date_admission >= DATE_SUB(NOW(), INTERVAL :days DAY)
                GROUP BY DATE(date_admission)
                ORDER BY date";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':days' => $days]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>