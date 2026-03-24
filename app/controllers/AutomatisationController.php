<?php

require_once __DIR__ . '/../services/RappelsAutomatiques.php';
require_once __DIR__ . '/../services/ScoresGravite.php';
require_once __DIR__ . '/../services/PlanningOptimise.php';
require_once __DIR__ . '/../services/AlertesPredictives.php';

class AutomatisationController {
    
    public function executerTachesAutomatiques() {
        header('Content-Type: application/json');
        
        $resultats = [
            'rappels_traitements' => RappelsAutomatiques::genererRappelsTraitements(),
            'rappels_constantes' => RappelsAutomatiques::genererRappelsConstantes(),
            'scores_calcules' => $this->calculerScoresPatients(),
            'alertes_predictives' => $this->analyserTendancesPatients(),
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        echo json_encode($resultats);
    }
    
    public function genererPlanningService() {
        $service_id = $_GET['service_id'] ?? 1;
        $date = $_GET['date'] ?? date('Y-m-d');
        $infirmieres = $_GET['infirmieres'] ?? ['Infirmière 1', 'Infirmière 2', 'Infirmière 3'];
        
        if (is_string($infirmieres)) {
            $infirmieres = explode(',', $infirmieres);
        }
        
        $planning = PlanningOptimise::genererPlanningService($service_id, $date, $infirmieres);
        
        header('Content-Type: application/json');
        echo json_encode($planning);
    }
    
    public function calculerScorePatient() {
        try {
            $patient_id = $_GET['patient_id'] ?? null;
            $type_score = $_GET['type'] ?? 'NEWS';
            
            if (!$patient_id) {
                http_response_code(400);
                echo json_encode(['error' => 'Patient ID requis']);
                return;
            }
            
            $score = null;
            
            switch ($type_score) {
                case 'NEWS':
                    $score = ScoresGravite::calculerScoreNEWS($patient_id);
                    break;
                case 'GLASGOW':
                    $ouverture = $_GET['ouverture_yeux'] ?? 4;
                    $verbale = $_GET['reponse_verbale'] ?? 5;
                    $motrice = $_GET['reponse_motrice'] ?? 6;
                    $score = ScoresGravite::calculerScoreGlasgow($patient_id, $ouverture, $verbale, $motrice);
                    break;
                case 'CHARLSON':
                    $score = ScoresGravite::calculerScoreCharlson($patient_id);
                    break;
                default:
                    $score = ['error' => 'Type de score non reconnu'];
            }
            
            header('Content-Type: application/json');
            echo json_encode($score);
        } catch (Exception $e) {
            header('Content-Type: application/json');
            http_response_code(500);
            echo json_encode(['error' => 'Erreur serveur: ' . $e->getMessage()]);
        }
    }
    
    public function analyserPatient() {
        try {
            $patient_id = $_GET['patient_id'] ?? null;
            
            if (!$patient_id) {
                http_response_code(400);
                echo json_encode(['error' => 'Patient ID requis']);
                return;
            }
            
            $analyse = [
                'tendances' => AlertesPredictives::analyserTendances($patient_id),
                'duree_sejour' => AlertesPredictives::predireDureeSejour($patient_id),
                'anomalies' => AlertesPredictives::detecterAnomalies($patient_id),
                'score_news' => ScoresGravite::calculerScoreNEWS($patient_id)
            ];
            
            header('Content-Type: application/json');
            echo json_encode($analyse);
        } catch (Exception $e) {
            header('Content-Type: application/json');
            http_response_code(500);
            echo json_encode(['error' => 'Erreur serveur: ' . $e->getMessage()]);
        }
    }
    
    public function dashboardAutomatisation() {
        require_once __DIR__ . '/../views/automatisation/dashboard.php';
    }
    
    private function calculerScoresPatients() {
        $db = (new Database())->getConnection();
        
        // Récupérer patients hospitalisés
        $stmt = $db->prepare("
            SELECT DISTINCT h.patient_id 
            FROM hospitalisations h 
            WHERE h.statut = 'active'
        ");
        $stmt->execute();
        $patients = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        $scores_calcules = 0;
        foreach ($patients as $patient_id) {
            $score = ScoresGravite::calculerScoreNEWS($patient_id);
            if ($score) $scores_calcules++;
        }
        
        return $scores_calcules;
    }
    
    private function analyserTendancesPatients() {
        $db = (new Database())->getConnection();
        
        $stmt = $db->prepare("
            SELECT DISTINCT h.patient_id 
            FROM hospitalisations h 
            WHERE h.statut = 'active'
        ");
        $stmt->execute();
        $patients = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        $alertes_generees = 0;
        foreach ($patients as $patient_id) {
            $alertes = AlertesPredictives::analyserTendances($patient_id);
            if ($alertes) $alertes_generees += count($alertes);
        }
        
        return $alertes_generees;
    }
}
?>