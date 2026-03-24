<?php
require_once __DIR__ . '/../../config/database.php';

class BanqueSang {
    private $db;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }
    
    // Règles de compatibilité sanguine
    private function getCompatibilite($groupe_receveur, $rhesus_receveur) {
        $compatibilite = [
            'A+' => ['A+', 'A-', 'O+', 'O-'],
            'A-' => ['A-', 'O-'],
            'B+' => ['B+', 'B-', 'O+', 'O-'],
            'B-' => ['B-', 'O-'],
            'AB+' => ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'],
            'AB-' => ['A-', 'B-', 'AB-', 'O-'],
            'O+' => ['O+', 'O-'],
            'O-' => ['O-']
        ];
        
        return $compatibilite[$groupe_receveur . $rhesus_receveur] ?? [];
    }
    
    // Vérifier compatibilité et stock disponible
    public function verifierCompatibilite($groupe_receveur, $rhesus_receveur, $quantite_ml = 450) {
        $groupes_compatibles = $this->getCompatibilite($groupe_receveur, $rhesus_receveur);
        
        $sql = "SELECT groupe_sanguin, rhesus, quantite_ml, quantite_poches 
                FROM banque_sang 
                WHERE CONCAT(groupe_sanguin, rhesus) IN ('" . implode("','", $groupes_compatibles) . "')
                AND quantite_ml >= :quantite
                ORDER BY 
                    CASE WHEN groupe_sanguin = :groupe AND rhesus = :rhesus THEN 1 ELSE 2 END,
                    quantite_ml DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':quantite' => $quantite_ml,
            ':groupe' => $groupe_receveur,
            ':rhesus' => $rhesus_receveur
        ]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Obtenir le stock complet
    public function getStock() {
        $sql = "SELECT * FROM banque_sang ORDER BY groupe_sanguin, rhesus";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Consommer du sang (transfusion)
    public function consommerSang($groupe, $rhesus, $quantite_ml = 450) {
        $sql = "UPDATE banque_sang 
                SET quantite_ml = quantite_ml - :quantite,
                    quantite_poches = quantite_poches - 1
                WHERE groupe_sanguin = :groupe AND rhesus = :rhesus
                AND quantite_ml >= :quantite";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':quantite' => $quantite_ml,
            ':groupe' => $groupe,
            ':rhesus' => $rhesus
        ]);
    }
    
    // Ajouter du sang (donation)
    public function ajouterSang($groupe, $rhesus, $quantite_ml = 450) {
        $sql = "INSERT INTO banque_sang (groupe_sanguin, rhesus, quantite_ml, quantite_poches)
                VALUES (:groupe, :rhesus, :quantite, 1)
                ON DUPLICATE KEY UPDATE 
                    quantite_ml = quantite_ml + :quantite,
                    quantite_poches = quantite_poches + 1";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':groupe' => $groupe,
            ':rhesus' => $rhesus,
            ':quantite' => $quantite_ml
        ]);
    }
    
    // Alertes stock faible
    public function getAlertes($seuil_ml = 1000) {
        $sql = "SELECT * FROM banque_sang WHERE quantite_ml < :seuil";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':seuil' => $seuil_ml]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>