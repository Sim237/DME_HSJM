<?php
require_once __DIR__ . '/UnifiedController.php';

class StatistiquesController extends UnifiedController {
    
    public function index() {
        $this->auth->requirePermission('parametres', 'read');
        require_once __DIR__ . '/../views/statistiques/index.php';
    }
    
    public function donnees() {
        $database = new Database();
        $db = $database->getConnection();
        
        $data = [];
        
        // KPIs
        $data['kpis'] = [
            'total_patients' => $this->getTotalPatients($db),
            'ca_total' => $this->getCATotalMois($db),
            'stock_alertes' => $this->getStockAlertes($db),
            'consultations_mois' => $this->getConsultationsMois($db)
        ];
        
        // CA mensuel
        $data['ca_mensuel'] = $this->getCaMensuel($db);
        
        // Top services
        $data['top_services'] = $this->getTopServices($db);
        
        header('Content-Type: application/json');
        echo json_encode($data);
    }
    
    private function getTotalPatients($db) {
        $sql = "SELECT COUNT(*) as total FROM patients";
        $stmt = $db->prepare($sql);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    }
    
    private function getCATotalMois($db) {
        $sql = "SELECT COALESCE(SUM(montant_ttc), 0) as total FROM factures 
                WHERE statut = 'payee' AND MONTH(date_facture) = MONTH(CURDATE())";
        $stmt = $db->prepare($sql);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    }
    
    private function getStockAlertes($db) {
        $sql = "SELECT COUNT(*) as total FROM stock_medicaments 
                WHERE stock_actuel <= stock_minimum AND statut = 'actif'";
        $stmt = $db->prepare($sql);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    }
    
    private function getConsultationsMois($db) {
        $sql = "SELECT COUNT(*) as total FROM consultations 
                WHERE MONTH(date_consultation) = MONTH(CURDATE())";
        $stmt = $db->prepare($sql);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    }
    
    private function getCaMensuel($db) {
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
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    private function getTopServices($db) {
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
                LIMIT 5";
        $stmt = $db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function exportExcel() {
        // Export simple CSV
        $database = new Database();
        $db = $database->getConnection();
        
        $sql = "SELECT 
                    f.numero_facture,
                    p.nom,
                    p.prenom,
                    f.date_facture,
                    f.montant_ttc,
                    f.statut
                FROM factures f
                JOIN patients p ON f.patient_id = p.id
                ORDER BY f.date_facture DESC";
        
        $stmt = $db->prepare($sql);
        $stmt->execute();
        $factures = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="statistiques_' . date('Y-m-d') . '.csv"');
        
        echo "Numero,Patient,Date,Montant,Statut\n";
        foreach ($factures as $facture) {
            echo implode(',', [
                $facture['numero_facture'],
                $facture['nom'] . ' ' . $facture['prenom'],
                $facture['date_facture'],
                $facture['montant_ttc'],
                $facture['statut']
            ]) . "\n";
        }
    }
}
?>