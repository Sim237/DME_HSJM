<?php
class StockService {
    private $db;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }
    
    public function verifierAlertes() {
        $alertes = [];
        
        // Stock faible
        $sql = "SELECT * FROM stock_medicaments 
                WHERE stock_actuel <= stock_minimum AND statut = 'actif'";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $stock_faible = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($stock_faible as $medicament) {
            $alertes[] = [
                'type' => 'stock_faible',
                'niveau' => 'warning',
                'titre' => 'Stock faible',
                'message' => "Stock critique pour {$medicament['nom']} : {$medicament['stock_actuel']} unités restantes",
                'medicament_id' => $medicament['id']
            ];
        }
        
        // Médicaments expirés
        $sql = "SELECT * FROM stock_medicaments 
                WHERE date_expiration <= CURDATE() AND statut = 'actif'";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $expires = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($expires as $medicament) {
            $alertes[] = [
                'type' => 'expire',
                'niveau' => 'danger',
                'titre' => 'Médicament expiré',
                'message' => "Médicament expiré : {$medicament['nom']} (exp: {$medicament['date_expiration']})",
                'medicament_id' => $medicament['id']
            ];
        }
        
        // Médicaments bientôt expirés (30 jours)
        $sql = "SELECT * FROM stock_medicaments 
                WHERE date_expiration BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY) 
                AND statut = 'actif'";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $bientot_expires = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($bientot_expires as $medicament) {
            $alertes[] = [
                'type' => 'bientot_expire',
                'niveau' => 'warning',
                'titre' => 'Expiration proche',
                'message' => "Expiration dans moins de 30 jours : {$medicament['nom']}",
                'medicament_id' => $medicament['id']
            ];
        }
        
        return $alertes;
    }
    
    public function genererCommandeAutomatique() {
        $sql = "SELECT * FROM stock_medicaments 
                WHERE stock_actuel <= stock_minimum AND statut = 'actif'";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $medicaments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $commandes = [];
        foreach ($medicaments as $medicament) {
            $quantite_commande = $medicament['stock_maximum'] - $medicament['stock_actuel'];
            
            $commandes[] = [
                'medicament' => $medicament['nom'],
                'stock_actuel' => $medicament['stock_actuel'],
                'quantite_commande' => $quantite_commande,
                'fournisseur' => $medicament['fournisseur'],
                'prix_estime' => $quantite_commande * $medicament['prix_achat']
            ];
        }
        
        return $commandes;
    }
    
    public function enregistrerMouvement($medicament_id, $type, $quantite, $motif) {
        // Enregistrer le mouvement
        $sql = "INSERT INTO mouvements_stock (medicament_id, type_mouvement, quantite, motif, user_id)
                VALUES (:medicament_id, :type, :quantite, :motif, :user_id)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':medicament_id' => $medicament_id,
            ':type' => $type,
            ':quantite' => $quantite,
            ':motif' => $motif,
            ':user_id' => $_SESSION['user_id'] ?? 1
        ]);
        
        // Mettre à jour le stock
        $operation = $type === 'entree' ? '+' : '-';
        $sql = "UPDATE stock_medicaments 
                SET stock_actuel = stock_actuel $operation :quantite 
                WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':quantite' => $quantite,
            ':id' => $medicament_id
        ]);
        
        // Vérifier le statut
        $this->mettreAJourStatut($medicament_id);
        
        return true;
    }
    
    private function mettreAJourStatut($medicament_id) {
        $sql = "SELECT stock_actuel, date_expiration FROM stock_medicaments WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $medicament_id]);
        $medicament = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $statut = 'actif';
        
        if ($medicament['stock_actuel'] <= 0) {
            $statut = 'rupture';
        } elseif ($medicament['date_expiration'] && $medicament['date_expiration'] <= date('Y-m-d')) {
            $statut = 'perime';
        }
        
        $sql = "UPDATE stock_medicaments SET statut = :statut WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':statut' => $statut, ':id' => $medicament_id]);
    }
    
    public function getStatistiquesStock() {
        $stats = [];
        
        // Total médicaments
        $sql = "SELECT COUNT(*) as total FROM stock_medicaments WHERE statut = 'actif'";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $stats['total_medicaments'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Stock faible
        $sql = "SELECT COUNT(*) as total FROM stock_medicaments 
                WHERE stock_actuel <= stock_minimum AND statut = 'actif'";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $stats['stock_faible'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Valeur totale du stock
        $sql = "SELECT SUM(stock_actuel * prix_achat) as valeur FROM stock_medicaments WHERE statut = 'actif'";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $stats['valeur_stock'] = $stmt->fetch(PDO::FETCH_ASSOC)['valeur'] ?? 0;
        
        // Mouvements du mois
        $sql = "SELECT COUNT(*) as total FROM mouvements_stock 
                WHERE MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $stats['mouvements_mois'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        return $stats;
    }
    
    public function exporterStock() {
        $sql = "SELECT nom, stock_actuel, stock_minimum, stock_maximum, prix_achat, prix_vente, 
                       date_expiration, fournisseur, statut
                FROM stock_medicaments
                ORDER BY nom";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $medicaments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Générer CSV
        $csv = "Nom,Stock Actuel,Stock Min,Stock Max,Prix Achat,Prix Vente,Date Expiration,Fournisseur,Statut\n";
        
        foreach ($medicaments as $med) {
            $csv .= implode(',', [
                $med['nom'],
                $med['stock_actuel'],
                $med['stock_minimum'],
                $med['stock_maximum'],
                $med['prix_achat'],
                $med['prix_vente'],
                $med['date_expiration'],
                $med['fournisseur'],
                $med['statut']
            ]) . "\n";
        }
        
        return $csv;
    }
}
?>