<?php

class ScoresGravite {
    
    public static function calculerScoreNEWS($patient_id) {
        try {
            $db = (new Database())->getConnection();
            
            // Récupérer dernières constantes
            $stmt = $db->prepare("
                SELECT * FROM constantes_vitales 
                WHERE patient_id = ? 
                ORDER BY date_mesure DESC LIMIT 1
            ");
            $stmt->execute([$patient_id]);
            $constantes = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$constantes) {
                return [
                    'score' => 0,
                    'niveau' => 'DONNEES_MANQUANTES',
                    'action' => 'Prendre les constantes vitales'
                ];
            }
            
            $score = 0;
            
            // Fréquence respiratoire
            $fr = $constantes['frequence_respiratoire'] ?? 0;
            if ($fr <= 8 || $fr >= 25) $score += 3;
            elseif ($fr >= 21) $score += 2;
            elseif ($fr >= 9) $score += 0;
            
            // Saturation O2
            $sat = $constantes['saturation_o2'] ?? 100;
            if ($sat <= 91) $score += 3;
            elseif ($sat <= 93) $score += 2;
            elseif ($sat <= 95) $score += 1;
            
            // Température
            $temp = $constantes['temperature'] ?? 36.5;
            if ($temp <= 35.0) $score += 3;
            elseif ($temp >= 39.1) $score += 2;
            elseif ($temp >= 38.1) $score += 1;
            
            // Tension systolique
            $tas = $constantes['tension_systolique'] ?? 120;
            if ($tas <= 90 || $tas >= 220) $score += 3;
            elseif ($tas <= 100) $score += 2;
            elseif ($tas <= 110) $score += 1;
            
            // Fréquence cardiaque
            $fc = $constantes['frequence_cardiaque'] ?? 70;
            if ($fc <= 40 || $fc >= 131) $score += 3;
            elseif ($fc >= 111) $score += 2;
            elseif ($fc >= 91) $score += 1;
            
            // Enregistrer le score
            self::enregistrerScore($patient_id, 'NEWS', $score, $constantes['date_mesure']);
            
            return [
                'score' => $score,
                'niveau' => self::getNiveauRisque($score),
                'action' => self::getActionRecommandee($score)
            ];
        } catch (Exception $e) {
            return [
                'score' => 0,
                'niveau' => 'ERREUR',
                'action' => 'Erreur de calcul: ' . $e->getMessage()
            ];
        }
    }
    
    public static function calculerScoreGlasgow($patient_id, $ouverture_yeux, $reponse_verbale, $reponse_motrice) {
        $score = $ouverture_yeux + $reponse_verbale + $reponse_motrice;
        
        self::enregistrerScore($patient_id, 'GLASGOW', $score, date('Y-m-d H:i:s'));
        
        return [
            'score' => $score,
            'niveau' => $score <= 8 ? 'CRITIQUE' : ($score <= 12 ? 'MODERE' : 'LEGER'),
            'action' => $score <= 8 ? 'Réanimation immédiate' : 'Surveillance renforcée'
        ];
    }
    
    public static function calculerScoreCharlson($patient_id) {
        $db = (new Database())->getConnection();
        
        // Récupérer antécédents du patient
        $stmt = $db->prepare("
            SELECT diagnostic FROM consultations 
            WHERE patient_id = ? 
            ORDER BY date_consultation DESC
        ");
        $stmt->execute([$patient_id]);
        $diagnostics = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        $score = 0;
        $diagnostics_text = implode(' ', $diagnostics);
        
        // Conditions avec score 1
        if (self::contientDiagnostic($diagnostics_text, ['infarctus', 'insuffisance cardiaque', 'diabète'])) {
            $score += 1;
        }
        
        // Conditions avec score 2
        if (self::contientDiagnostic($diagnostics_text, ['démence', 'maladie rénale', 'leucémie'])) {
            $score += 2;
        }
        
        // Conditions avec score 3
        if (self::contientDiagnostic($diagnostics_text, ['cirrhose', 'cancer métastatique'])) {
            $score += 3;
        }
        
        self::enregistrerScore($patient_id, 'CHARLSON', $score, date('Y-m-d H:i:s'));
        
        return [
            'score' => $score,
            'mortalite_1_an' => self::getMortaliteCharlson($score)
        ];
    }
    
    private static function getNiveauRisque($score) {
        if ($score >= 7) return 'CRITIQUE';
        if ($score >= 5) return 'ELEVE';
        if ($score >= 3) return 'MODERE';
        return 'FAIBLE';
    }
    
    private static function getActionRecommandee($score) {
        if ($score >= 7) return 'Surveillance continue + médecin senior';
        if ($score >= 5) return 'Surveillance renforcée toutes les heures';
        if ($score >= 3) return 'Surveillance toutes les 4-6h';
        return 'Surveillance standard 12h';
    }
    
    private static function contientDiagnostic($text, $conditions) {
        foreach ($conditions as $condition) {
            if (stripos($text, $condition) !== false) {
                return true;
            }
        }
        return false;
    }
    
    private static function getMortaliteCharlson($score) {
        $mortalite = [
            0 => '12%', 1 => '21%', 2 => '26%', 3 => '34%', 4 => '52%', 5 => '85%'
        ];
        return $mortalite[min($score, 5)] ?? '85%';
    }
    
    private static function enregistrerScore($patient_id, $type, $score, $date) {
        $db = (new Database())->getConnection();
        
        $stmt = $db->prepare("
            INSERT INTO scores_gravite 
            (patient_id, type_score, valeur, date_calcul) 
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$patient_id, $type, $score, $date]);
    }
}
?>