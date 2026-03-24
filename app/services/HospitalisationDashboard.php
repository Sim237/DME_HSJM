<?php
class HospitalisationDashboard {
    
    public static function getDashboardData() {
        $db = (new Database())->getConnection();
        
        return [
            'alertes_critiques' => self::getAlertesCritiques($db),
            'patients_prioritaires' => self::getPatientsPrioritaires($db),
            'occupancy_rate' => self::getTauxOccupation($db),
            'constantes_anormales' => self::getConstantesAnormales($db),
            'traitements_dus' => self::getTraitementsDus($db)
        ];
    }
    
    private static function getAlertesCritiques($db) {
        $stmt = $db->prepare("
            SELECT p.nom, p.prenom, cv.temperature, cv.tension_systolique, cv.date_mesure
            FROM constantes_vitales cv
            JOIN patients p ON cv.patient_id = p.id
            JOIN hospitalisations h ON p.id = h.patient_id
            WHERE h.statut = 'active' 
            AND (cv.temperature > 39 OR cv.temperature < 35 OR cv.tension_systolique > 180 OR cv.tension_systolique < 90)
            AND cv.date_mesure > DATE_SUB(NOW(), INTERVAL 2 HOUR)
            ORDER BY cv.date_mesure DESC
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    private static function getPatientsPrioritaires($db) {
        $stmt = $db->prepare("
            SELECT p.*, h.service_id, COUNT(sp.id) as soins_en_attente
            FROM patients p
            JOIN hospitalisations h ON p.id = h.patient_id
            LEFT JOIN soins_planifies sp ON p.id = sp.patient_id AND sp.statut = 'planifie'
            WHERE h.statut = 'active'
            GROUP BY p.id
            HAVING soins_en_attente > 2
            ORDER BY soins_en_attente DESC
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    private static function getTauxOccupation($db) {
        $stmt = $db->prepare("
            SELECT s.nom, COUNT(h.id) as occupes, s.capacite,
            ROUND((COUNT(h.id) / s.capacite) * 100, 1) as taux_occupation
            FROM services s
            LEFT JOIN hospitalisations h ON s.id = h.service_id AND h.statut = 'active'
            GROUP BY s.id
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>