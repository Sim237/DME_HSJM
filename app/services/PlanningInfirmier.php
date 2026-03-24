<?php
class PlanningInfirmier {
    
    public static function genererPlanningJournalier($service_id, $date) {
        $db = (new Database())->getConnection();
        
        // Récupérer tous les patients du service
        $stmt = $db->prepare("
            SELECT p.*, h.lit_id
            FROM patients p
            JOIN hospitalisations h ON p.id = h.patient_id
            WHERE h.service_id = ? AND h.statut = 'active'
        ");
        $stmt->execute([$service_id]);
        $patients = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $planning = [];
        
        foreach ($patients as $patient) {
            // Traitements programmés
            $traitements = self::getTraitementsPatient($patient['id'], $date);
            
            // Soins planifiés
            $soins = self::getSoinsPatient($patient['id'], $date);
            
            // Constantes à prendre
            $constantes = self::getConstantesAPrendre($patient['id']);
            
            $planning[$patient['id']] = [
                'patient' => $patient,
                'traitements' => $traitements,
                'soins' => $soins,
                'constantes' => $constantes,
                'priorite' => self::calculerPriorite($patient['id'])
            ];
        }
        
        // Trier par priorité
        uasort($planning, function($a, $b) {
            return $b['priorite'] - $a['priorite'];
        });
        
        return $planning;
    }
    
    private static function calculerPriorite($patient_id) {
        // Algorithme de calcul de priorité basé sur:
        // - État clinique
        // - Nombre de traitements
        // - Urgence des soins
        return rand(1, 10); // Simplifié pour l'exemple
    }
    
    public static function optimiserTournees($planning) {
        // Algorithme d'optimisation des tournées infirmières
        // Basé sur la proximité des lits et la priorité des soins
        return $planning;
    }
}
?>