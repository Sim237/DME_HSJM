<?php
require_once __DIR__ . '/UnifiedController.php';
require_once __DIR__ . '/../services/FacturationService.php';

class FacturationController extends UnifiedController {
    private $facturationService;
    
    public function __construct() {
        parent::__construct();
        $this->facturationService = new FacturationService();
    }
    
    public function index() {
        $this->auth->requirePermission('parametres', 'read');
        
        $database = new Database();
        $db = $database->getConnection();
        
        $sql = "SELECT f.*, p.nom, p.prenom 
                FROM factures f
                JOIN patients p ON f.patient_id = p.id
                ORDER BY f.created_at DESC
                LIMIT 50";
        
        $stmt = $db->prepare($sql);
        $stmt->execute();
        $factures = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        require_once __DIR__ . '/../views/facturation/index.php';
    }
    
    public function generer($consultation_id) {
        $this->auth->requirePermission('parametres', 'write');
        
        $facture_id = $this->facturationService->genererFactureConsultation($consultation_id);
        
        if ($facture_id) {
            header('Location: ' . BASE_URL . 'facturation/voir/' . $facture_id);
        } else {
            header('Location: ' . BASE_URL . 'facturation?error=generation_failed');
        }
    }
    
    public function voir($id) {
        $database = new Database();
        $db = $database->getConnection();
        
        $sql = "SELECT f.*, p.nom, p.prenom, p.adresse, p.telephone
                FROM factures f
                JOIN patients p ON f.patient_id = p.id
                WHERE f.id = :id";
        $stmt = $db->prepare($sql);
        $stmt->execute([':id' => $id]);
        $facture = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $sql = "SELECT fl.*, t.libelle
                FROM facture_lignes fl
                JOIN tarifs t ON fl.tarif_id = t.id
                WHERE fl.facture_id = :id";
        $stmt = $db->prepare($sql);
        $stmt->execute([':id' => $id]);
        $lignes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        require_once __DIR__ . '/../views/facturation/voir.php';
    }
    
    public function pdf($id) {
        $html = $this->facturationService->genererPDF($id);
        
        header('Content-Type: text/html; charset=utf-8');
        echo $html;
    }
    
    public function marquerPayee($id) {
        $this->auth->requirePermission('parametres', 'write');
        
        $database = new Database();
        $db = $database->getConnection();
        
        $sql = "UPDATE factures SET statut = 'payee', date_paiement = CURDATE(), mode_paiement = :mode
                WHERE id = :id";
        $stmt = $db->prepare($sql);
        $stmt->execute([
            ':id' => $id,
            ':mode' => $_POST['mode_paiement'] ?? 'especes'
        ]);
        
        echo json_encode(['success' => true]);
    }
    
    public function statistiques() {
        $this->auth->requirePermission('parametres', 'read');
        
        $database = new Database();
        $db = $database->getConnection();
        
        // Chiffre d'affaires mensuel
        $sql = "SELECT 
                    MONTH(date_facture) as mois,
                    YEAR(date_facture) as annee,
                    SUM(montant_ttc) as total
                FROM factures 
                WHERE statut = 'payee' AND date_facture >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
                GROUP BY YEAR(date_facture), MONTH(date_facture)
                ORDER BY annee, mois";
        $stmt = $db->prepare($sql);
        $stmt->execute();
        $ca_mensuel = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Top services
        $sql = "SELECT 
                    t.libelle,
                    SUM(fl.montant) as total,
                    COUNT(*) as nombre
                FROM facture_lignes fl
                JOIN tarifs t ON fl.tarif_id = t.id
                JOIN factures f ON fl.facture_id = f.id
                WHERE f.statut = 'payee'
                GROUP BY t.id
                ORDER BY total DESC
                LIMIT 10";
        $stmt = $db->prepare($sql);
        $stmt->execute();
        $top_services = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        header('Content-Type: application/json');
        echo json_encode([
            'ca_mensuel' => $ca_mensuel,
            'top_services' => $top_services
        ]);
    }
}
?>