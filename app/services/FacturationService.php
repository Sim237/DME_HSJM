<?php
class FacturationService {
    private $db;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }
    
    public function genererFactureConsultation($consultation_id) {
        // Récupérer les données de consultation
        $sql = "SELECT c.*, p.nom, p.prenom FROM consultations c 
                JOIN patients p ON c.patient_id = p.id 
                WHERE c.id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $consultation_id]);
        $consultation = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$consultation) return false;
        
        // Générer numéro de facture
        $numero = 'FAC-' . date('Y') . '-' . str_pad($this->getNextFactureNumber(), 6, '0', STR_PAD_LEFT);
        
        // Créer la facture
        $sql = "INSERT INTO factures (numero_facture, patient_id, consultation_id, date_facture, montant_ht, montant_ttc)
                VALUES (:numero, :patient_id, :consultation_id, CURDATE(), 0, 0)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':numero' => $numero,
            ':patient_id' => $consultation['patient_id'],
            ':consultation_id' => $consultation_id
        ]);
        
        $facture_id = $this->db->lastInsertId();
        
        // Ajouter les lignes automatiquement
        $this->ajouterLigneConsultation($facture_id, $consultation);
        $this->ajouterLignesExamens($facture_id, $consultation_id);
        $this->ajouterLignesMedicaments($facture_id, $consultation_id);
        
        // Calculer le total
        $this->calculerTotalFacture($facture_id);
        
        return $facture_id;
    }
    
    private function ajouterLigneConsultation($facture_id, $consultation) {
        $code_tarif = 'CONS_GEN'; // Par défaut
        
        $sql = "SELECT * FROM tarifs WHERE code = :code";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':code' => $code_tarif]);
        $tarif = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($tarif) {
            $sql = "INSERT INTO facture_lignes (facture_id, tarif_id, quantite, prix_unitaire, montant)
                    VALUES (:facture_id, :tarif_id, 1, :prix, :prix)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':facture_id' => $facture_id,
                ':tarif_id' => $tarif['id'],
                ':prix' => $tarif['prix']
            ]);
        }
    }
    
    private function ajouterLignesExamens($facture_id, $consultation_id) {
        // Récupérer les examens de la consultation
        $sql = "SELECT * FROM examens WHERE consultation_id = :consultation_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':consultation_id' => $consultation_id]);
        $examens = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($examens as $examen) {
            $code_tarif = $this->mapExamenToTarif($examen['type_examen']);
            
            $sql = "SELECT * FROM tarifs WHERE code = :code";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':code' => $code_tarif]);
            $tarif = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($tarif) {
                $sql = "INSERT INTO facture_lignes (facture_id, tarif_id, quantite, prix_unitaire, montant)
                        VALUES (:facture_id, :tarif_id, 1, :prix, :prix)";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([
                    ':facture_id' => $facture_id,
                    ':tarif_id' => $tarif['id'],
                    ':prix' => $tarif['prix']
                ]);
            }
        }
    }
    
    private function calculerTotalFacture($facture_id) {
        $sql = "SELECT SUM(montant) as total FROM facture_lignes WHERE facture_id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $facture_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $total = $result['total'] ?? 0;
        
        $sql = "UPDATE factures SET montant_ht = :total, montant_ttc = :total WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':total' => $total, ':id' => $facture_id]);
    }
    
    public function genererPDF($facture_id) {
        // Récupérer les données de facture
        $sql = "SELECT f.*, p.nom, p.prenom, p.adresse, p.telephone
                FROM factures f
                JOIN patients p ON f.patient_id = p.id
                WHERE f.id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $facture_id]);
        $facture = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Récupérer les lignes
        $sql = "SELECT fl.*, t.libelle
                FROM facture_lignes fl
                JOIN tarifs t ON fl.tarif_id = t.id
                WHERE fl.facture_id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $facture_id]);
        $lignes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Générer le HTML de la facture
        $html = $this->genererHTMLFacture($facture, $lignes);
        
        return $html;
    }
    
    private function genererHTMLFacture($facture, $lignes) {
        $html = '
        <div style="font-family: Arial; max-width: 800px; margin: 0 auto;">
            <div style="text-align: center; margin-bottom: 30px;">
                <h1>DME HOSPITAL</h1>
                <p>Système de gestion hospitalière</p>
            </div>
            
            <div style="display: flex; justify-content: space-between; margin-bottom: 30px;">
                <div>
                    <h3>FACTURE</h3>
                    <p><strong>N°:</strong> ' . $facture['numero_facture'] . '</p>
                    <p><strong>Date:</strong> ' . date('d/m/Y', strtotime($facture['date_facture'])) . '</p>
                </div>
                <div>
                    <h4>Patient</h4>
                    <p>' . $facture['nom'] . ' ' . $facture['prenom'] . '</p>
                    <p>' . $facture['adresse'] . '</p>
                    <p>' . $facture['telephone'] . '</p>
                </div>
            </div>
            
            <table style="width: 100%; border-collapse: collapse; margin-bottom: 30px;">
                <thead>
                    <tr style="background: #f5f5f5;">
                        <th style="border: 1px solid #ddd; padding: 10px; text-align: left;">Description</th>
                        <th style="border: 1px solid #ddd; padding: 10px; text-align: center;">Qté</th>
                        <th style="border: 1px solid #ddd; padding: 10px; text-align: right;">Prix Unit.</th>
                        <th style="border: 1px solid #ddd; padding: 10px; text-align: right;">Total</th>
                    </tr>
                </thead>
                <tbody>';
        
        foreach ($lignes as $ligne) {
            $html .= '
                    <tr>
                        <td style="border: 1px solid #ddd; padding: 10px;">' . $ligne['libelle'] . '</td>
                        <td style="border: 1px solid #ddd; padding: 10px; text-align: center;">' . $ligne['quantite'] . '</td>
                        <td style="border: 1px solid #ddd; padding: 10px; text-align: right;">' . number_format($ligne['prix_unitaire'], 0, ',', ' ') . ' FCFA</td>
                        <td style="border: 1px solid #ddd; padding: 10px; text-align: right;">' . number_format($ligne['montant'], 0, ',', ' ') . ' FCFA</td>
                    </tr>';
        }
        
        $html .= '
                </tbody>
                <tfoot>
                    <tr style="background: #f5f5f5; font-weight: bold;">
                        <td colspan="3" style="border: 1px solid #ddd; padding: 10px; text-align: right;">TOTAL</td>
                        <td style="border: 1px solid #ddd; padding: 10px; text-align: right;">' . number_format($facture['montant_ttc'], 0, ',', ' ') . ' FCFA</td>
                    </tr>
                </tfoot>
            </table>
            
            <div style="text-align: center; margin-top: 50px;">
                <p><em>Merci de votre confiance</em></p>
            </div>
        </div>';
        
        return $html;
    }
    
    private function getNextFactureNumber() {
        $sql = "SELECT COUNT(*) + 1 as next_num FROM factures WHERE YEAR(date_facture) = YEAR(CURDATE())";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['next_num'];
    }
    
    private function mapExamenToTarif($type_examen) {
        $mapping = [
            'radiographie' => 'RADIO_THOR',
            'scanner' => 'SCAN_CRANE',
            'irm' => 'SCAN_CRANE'
        ];
        return $mapping[$type_examen] ?? 'CONS_GEN';
    }
}
?>