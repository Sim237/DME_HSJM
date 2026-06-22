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

require_once __DIR__ . '/UnifiedController.php';
require_once __DIR__ . '/../services/FacturationService.php';
require_once __DIR__ . '/../services/AuditService.php';

class FacturationController extends UnifiedController {
    private $facturationService;
    private $audit;

    public function __construct() {
        parent::__construct();
        $this->facturationService = new FacturationService();
        $this->audit = new AuditService();
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
            // ── Audit : facture générée ──
            try {
                $this->audit->log('CREATE', 'factures', (int)$facture_id,
                    "Facture générée pour la consultation #" . (int)$consultation_id, null,
                    ['consultation_id' => (int)$consultation_id, 'facture_id' => (int)$facture_id]);
            } catch (Exception $e) { error_log('[Facturation::generer] Audit: ' . $e->getMessage()); }
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
        
        $mode = $_POST['mode_paiement'] ?? 'especes';
        $sql = "UPDATE factures SET statut = 'payee', date_paiement = CURDATE(), mode_paiement = :mode
                WHERE id = :id";
        $stmt = $db->prepare($sql);
        $stmt->execute([
            ':id' => $id,
            ':mode' => $mode
        ]);

        // ── Audit : paiement de facture enregistré ──
        try {
            $fRow = $db->prepare("SELECT f.montant_total, f.patient_id, p.nom, p.prenom
                                  FROM factures f JOIN patients p ON p.id = f.patient_id WHERE f.id = ?");
            $fRow->execute([$id]);
            $f = $fRow->fetch(PDO::FETCH_ASSOC);
            $pNom    = $f ? trim(($f['nom'] ?? '') . ' ' . ($f['prenom'] ?? '')) : '';
            $montant = $f['montant_total'] ?? null;
            $desc = "Facture #$id réglée"
                  . ($montant !== null ? ' — ' . number_format((float)$montant, 0, ',', ' ') . ' FCFA' : '')
                  . ' (' . $mode . ')' . ($pNom ? " · patient : $pNom" : '');
            $this->audit->log('UPDATE', 'factures', (int)$id, $desc, null, [
                'patient'       => $pNom,
                'montant'       => $montant,
                'mode_paiement' => $mode,
            ]);
        } catch (Exception $e) { error_log('[Facturation::marquerPayee] Audit: ' . $e->getMessage()); }

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