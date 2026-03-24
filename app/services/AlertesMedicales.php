<?php
class AlertesMedicales {
    
    public static function verifierAlertes($patient_id) {
        $db = (new Database())->getConnection();
        $alertes = [];
        
        // Constantes critiques
        $stmt = $db->prepare("
            SELECT * FROM constantes_vitales 
            WHERE patient_id = ? 
            ORDER BY date_mesure DESC LIMIT 1
        ");
        $stmt->execute([$patient_id]);
        $constantes = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($constantes) {
            if ($constantes['temperature'] > 39) {
                $alertes[] = [
                    'type' => 'CRITIQUE',
                    'message' => "Hyperthermie sévère: {$constantes['temperature']}°C",
                    'action' => 'Contrôle médical immédiat'
                ];
            }
            
            if ($constantes['tension_systolique'] > 180) {
                $alertes[] = [
                    'type' => 'URGENT',
                    'message' => "Hypertension sévère: {$constantes['tension_systolique']}/{$constantes['tension_diastolique']}",
                    'action' => 'Traitement antihypertenseur'
                ];
            }
            
            if ($constantes['saturation_o2'] < 90) {
                $alertes[] = [
                    'type' => 'CRITIQUE',
                    'message' => "Désaturation: {$constantes['saturation_o2']}%",
                    'action' => 'Oxygénothérapie urgente'
                ];
            }
        }
        
        // Traitements en retard
        $stmt = $db->prepare("
            SELECT ph.*, m.nom as medicament
            FROM prescriptions_hospitalisation ph
            JOIN medicaments m ON ph.medicament_id = m.id
            WHERE ph.patient_id = ? AND ph.statut = 'active'
            AND NOT EXISTS (
                SELECT 1 FROM administrations_medicaments am 
                WHERE am.prescription_id = ph.id 
                AND DATE(am.heure_administration) = CURDATE()
            )
        ");
        $stmt->execute([$patient_id]);
        $retards = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($retards as $retard) {
            $alertes[] = [
                'type' => 'ATTENTION',
                'message' => "Traitement non administré: {$retard['medicament']}",
                'action' => 'Vérifier administration'
            ];
        }
        
        return $alertes;
    }
    
    public static function envoyerNotification($alerte, $destinataires) {
        // Système de notification (SMS, email, push)
        foreach ($destinataires as $dest) {
            // Implémentation notification
        }
    }
}
?>