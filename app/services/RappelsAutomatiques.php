<?php

class RappelsAutomatiques {
    
    public static function genererRappelsTraitements() {
        $db = (new Database())->getConnection();
        
        // Récupérer traitements dus dans les 30 prochaines minutes
        $stmt = $db->prepare("
            SELECT ph.*, p.nom, p.prenom, m.nom as medicament, s.nom as service
            FROM prescriptions_hospitalisation ph
            JOIN patients p ON ph.patient_id = p.id
            JOIN medicaments m ON ph.medicament_id = m.id
            JOIN hospitalisations h ON p.id = h.patient_id
            JOIN services s ON h.service_id = s.id
            WHERE ph.statut = 'active' 
            AND h.statut = 'active'
            AND TIME(NOW()) BETWEEN 
                SUBTIME(ph.heure_debut, '00:30:00') AND ph.heure_debut
            AND NOT EXISTS (
                SELECT 1 FROM administrations_medicaments am 
                WHERE am.prescription_id = ph.id 
                AND DATE(am.heure_administration) = CURDATE()
                AND TIME(am.heure_administration) BETWEEN ph.heure_debut AND ph.heure_fin
            )
        ");
        $stmt->execute();
        $traitements_dus = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($traitements_dus as $traitement) {
            self::envoyerRappel($traitement);
        }
        
        return $traitements_dus;
    }
    
    public static function genererRappelsConstantes() {
        $db = (new Database())->getConnection();
        
        // Patients sans constantes depuis plus de 4h
        $stmt = $db->prepare("
            SELECT p.*, h.service_id, s.nom as service
            FROM patients p
            JOIN hospitalisations h ON p.id = h.patient_id
            JOIN services s ON h.service_id = s.id
            WHERE h.statut = 'active'
            AND NOT EXISTS (
                SELECT 1 FROM constantes_vitales cv 
                WHERE cv.patient_id = p.id 
                AND cv.date_mesure > DATE_SUB(NOW(), INTERVAL 4 HOUR)
            )
        ");
        $stmt->execute();
        $patients_constantes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($patients_constantes as $patient) {
            self::envoyerRappelConstantes($patient);
        }
        
        return $patients_constantes;
    }
    
    private static function envoyerRappel($traitement) {
        $message = "🔔 RAPPEL TRAITEMENT\n";
        $message .= "Patient: {$traitement['nom']} {$traitement['prenom']}\n";
        $message .= "Médicament: {$traitement['medicament']}\n";
        $message .= "Posologie: {$traitement['posologie']}\n";
        $message .= "Heure: {$traitement['heure_debut']}\n";
        $message .= "Service: {$traitement['service']}";
        
        // Enregistrer notification
        self::creerNotification($traitement['patient_id'], 'RAPPEL_TRAITEMENT', $message);
    }
    
    private static function envoyerRappelConstantes($patient) {
        $message = "📊 RAPPEL CONSTANTES\n";
        $message .= "Patient: {$patient['nom']} {$patient['prenom']}\n";
        $message .= "Service: {$patient['service']}\n";
        $message .= "Dernières constantes > 4h";
        
        self::creerNotification($patient['id'], 'RAPPEL_CONSTANTES', $message);
    }
    
    private static function creerNotification($patient_id, $type, $message) {
        $db = (new Database())->getConnection();
        
        $stmt = $db->prepare("
            INSERT INTO notifications_automatiques 
            (patient_id, type, message, date_creation, statut) 
            VALUES (?, ?, ?, NOW(), 'active')
        ");
        $stmt->execute([$patient_id, $type, $message]);
    }
}
?>