<?php

class PlanningOptimise {
    
    public static function genererPlanningService($service_id, $date, $equipe_infirmieres) {
        $db = (new Database())->getConnection();
        
        // Récupérer tous les patients du service
        $patients = self::getPatientsService($service_id);
        
        // Calculer charge de travail par patient
        $charges = [];
        foreach ($patients as $patient) {
            $charges[$patient['id']] = self::calculerChargeTravail($patient['id'], $date);
        }
        
        // Optimiser répartition
        $planning = self::optimiserRepartition($charges, $equipe_infirmieres);
        
        // Générer planning horaire
        $planning_horaire = self::genererPlanningHoraire($planning, $date);
        
        return [
            'repartition' => $planning,
            'planning_horaire' => $planning_horaire,
            'charge_totale' => array_sum($charges),
            'equilibrage' => self::calculerEquilibrage($planning)
        ];
    }
    
    private static function calculerChargeTravail($patient_id, $date) {
        $db = (new Database())->getConnection();
        $charge = 0;
        
        // Traitements (5 min par administration)
        $stmt = $db->prepare("
            SELECT COUNT(*) as nb_traitements
            FROM prescriptions_hospitalisation 
            WHERE patient_id = ? AND statut = 'active'
        ");
        $stmt->execute([$patient_id]);
        $traitements = $stmt->fetch(PDO::FETCH_ASSOC)['nb_traitements'];
        $charge += $traitements * 5;
        
        // Soins planifiés
        $stmt = $db->prepare("
            SELECT SUM(duree_estimee) as duree_soins
            FROM soins_planifies 
            WHERE patient_id = ? AND DATE(heure_prevue) = ? AND statut = 'planifie'
        ");
        $stmt->execute([$patient_id, $date]);
        $soins = $stmt->fetch(PDO::FETCH_ASSOC)['duree_soins'] ?? 0;
        $charge += $soins;
        
        // Constantes (10 min par prise)
        $charge += 10; // Constantes quotidiennes
        
        // Score de gravité (plus de surveillance = plus de temps)
        $score_news = self::getDernierScore($patient_id, 'NEWS');
        if ($score_news >= 7) $charge *= 2;
        elseif ($score_news >= 5) $charge *= 1.5;
        
        return $charge;
    }
    
    private static function optimiserRepartition($charges, $infirmieres) {
        // Algorithme de répartition équilibrée
        $repartition = [];
        $charges_infirmieres = array_fill_keys($infirmieres, 0);
        
        // Trier patients par charge décroissante
        arsort($charges);
        
        foreach ($charges as $patient_id => $charge) {
            // Assigner au moins chargé
            $infirmiere_min = array_keys($charges_infirmieres, min($charges_infirmieres))[0];
            
            $repartition[$infirmiere_min][] = $patient_id;
            $charges_infirmieres[$infirmiere_min] += $charge;
        }
        
        return $repartition;
    }
    
    private static function genererPlanningHoraire($repartition, $date) {
        $planning_horaire = [];
        
        foreach ($repartition as $infirmiere => $patients) {
            $planning_horaire[$infirmiere] = [];
            
            foreach ($patients as $patient_id) {
                // Récupérer horaires traitements
                $horaires = self::getHorairesTraitements($patient_id);
                
                foreach ($horaires as $horaire) {
                    $planning_horaire[$infirmiere][$horaire['heure']][] = [
                        'patient_id' => $patient_id,
                        'action' => $horaire['action'],
                        'duree' => $horaire['duree']
                    ];
                }
            }
            
            // Trier par heure
            ksort($planning_horaire[$infirmiere]);
        }
        
        return $planning_horaire;
    }
    
    private static function getHorairesTraitements($patient_id) {
        $db = (new Database())->getConnection();
        
        $stmt = $db->prepare("
            SELECT ph.heure_debut as heure, 
                   CONCAT('Traitement: ', m.nom) as action,
                   5 as duree
            FROM prescriptions_hospitalisation ph
            JOIN medicaments m ON ph.medicament_id = m.id
            WHERE ph.patient_id = ? AND ph.statut = 'active'
            
            UNION ALL
            
            SELECT TIME(sp.heure_prevue) as heure,
                   CONCAT('Soin: ', sp.type_soin) as action,
                   sp.duree_estimee as duree
            FROM soins_planifies sp
            WHERE sp.patient_id = ? AND DATE(sp.heure_prevue) = CURDATE()
            
            ORDER BY heure
        ");
        $stmt->execute([$patient_id, $patient_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    private static function calculerEquilibrage($repartition) {
        $charges = [];
        foreach ($repartition as $infirmiere => $patients) {
            $charges[] = count($patients);
        }
        
        if (empty($charges)) {
            return ['equilibre' => 'AUCUN', 'ecart_type' => 0];
        }
        
        $moyenne = array_sum($charges) / count($charges);
        $ecart_type = sqrt(array_sum(array_map(function($x) use ($moyenne) { 
            return pow($x - $moyenne, 2); 
        }, $charges)) / count($charges));
        
        return [
            'equilibre' => $ecart_type < 1 ? 'EXCELLENT' : ($ecart_type < 2 ? 'BON' : 'MOYEN'),
            'ecart_type' => round($ecart_type, 2)
        ];
    }
    
    private static function getPatientsService($service_id) {
        $db = (new Database())->getConnection();
        
        $stmt = $db->prepare("
            SELECT p.*, h.id as hospitalisation_id
            FROM patients p
            JOIN hospitalisations h ON p.id = h.patient_id
            WHERE h.service_id = ? AND h.statut = 'active'
        ");
        $stmt->execute([$service_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    private static function getDernierScore($patient_id, $type) {
        $db = (new Database())->getConnection();
        
        $stmt = $db->prepare("
            SELECT valeur FROM scores_gravite 
            WHERE patient_id = ? AND type_score = ? 
            ORDER BY date_calcul DESC LIMIT 1
        ");
        $stmt->execute([$patient_id, $type]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result ? $result['valeur'] : 0;
    }
}
?>