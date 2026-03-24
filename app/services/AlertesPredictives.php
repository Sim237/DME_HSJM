<?php

class AlertesPredictives {
    
    public static function analyserTendances($patient_id) {
        try {
            $db = (new Database())->getConnection();
            
            // Récupérer historique constantes (72h)
            $stmt = $db->prepare("
                SELECT * FROM constantes_vitales 
                WHERE patient_id = ? 
                AND date_mesure > DATE_SUB(NOW(), INTERVAL 72 HOUR)
                ORDER BY date_mesure ASC
            ");
            $stmt->execute([$patient_id]);
            $constantes = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (count($constantes) < 3) {
                return [
                    [
                        'type' => 'INFO',
                        'niveau' => 'INFO',
                        'message' => 'Données insuffisantes pour l\'analyse prédictive',
                        'probabilite' => 0,
                        'action' => 'Prendre plus de constantes'
                    ]
                ];
            }
            
            $alertes = [];
            
            // Analyse tendance température
            $tendance_temp = self::calculerTendance(array_column($constantes, 'temperature'));
            if ($tendance_temp['pente'] > 0.5) {
                $alertes[] = [
                    'type' => 'PREDICTIVE',
                    'niveau' => 'ATTENTION',
                    'message' => 'Tendance hyperthermique détectée',
                    'probabilite' => self::calculerProbabilite($tendance_temp),
                    'action' => 'Surveillance température renforcée'
                ];
            }
            
            // Analyse variabilité tension
            $tensions = array_filter(array_column($constantes, 'tension_systolique'));
            if (!empty($tensions)) {
                $variabilite = self::calculerVariabilite($tensions);
                if ($variabilite > 20) {
                    $alertes[] = [
                        'type' => 'PREDICTIVE',
                        'niveau' => 'URGENT',
                        'message' => 'Instabilité tensionnelle détectée',
                        'probabilite' => min(90, $variabilite * 2),
                        'action' => 'Contrôle médical recommandé'
                    ];
                }
            }
            
            // Prédiction détérioration
            $score_deterioration = self::calculerScoreDeterioration($constantes);
            if ($score_deterioration > 70) {
                $alertes[] = [
                    'type' => 'PREDICTIVE',
                    'niveau' => 'CRITIQUE',
                    'message' => 'Risque de détérioration clinique élevé',
                    'probabilite' => $score_deterioration,
                    'action' => 'Évaluation médicale urgente'
                ];
            }
            
            // Enregistrer alertes
            foreach ($alertes as $alerte) {
                self::enregistrerAlerte($patient_id, $alerte);
            }
            
            return $alertes;
        } catch (Exception $e) {
            return [
                [
                    'type' => 'ERREUR',
                    'niveau' => 'ERREUR',
                    'message' => 'Erreur d\'analyse: ' . $e->getMessage(),
                    'probabilite' => 0,
                    'action' => 'Vérifier les données'
                ]
            ];
        }
    }

    public static function predireDureeSejour($patient_id) {
        try {
            $db = (new Database())->getConnection();
            
            // Récupérer données patient
            $stmt = $db->prepare("
                SELECT p.*, h.date_admission, h.diagnostic_admission
                FROM patients p
                JOIN hospitalisations h ON p.id = h.patient_id
                WHERE p.id = ? AND h.statut = 'active'
            ");
            $stmt->execute([$patient_id]);
            $patient = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$patient) {
                return [
                    'duree_estimee' => 3,
                    'intervalle_confiance' => [1, 5],
                    'facteurs' => ['Données insuffisantes']
                ];
            }
            
            // Calcul basé sur plusieurs facteurs
            $duree_base = 5; // jours
            
            // Âge
            $age = (new DateTime())->diff(new DateTime($patient['date_naissance']))->y;
            if ($age > 75) $duree_base += 2;
            elseif ($age > 65) $duree_base += 1;
            
            return [
                'duree_estimee' => $duree_base,
                'intervalle_confiance' => [$duree_base - 2, $duree_base + 3],
                'facteurs' => [
                    'age' => $age,
                    'diagnostic' => $patient['diagnostic_admission'] ?? 'Non spécifié'
                ]
            ];
        } catch (Exception $e) {
            return [
                'duree_estimee' => 3,
                'intervalle_confiance' => [1, 5],
                'facteurs' => ['Erreur: ' . $e->getMessage()]
            ];
        }
    }
    
    public static function detecterAnomalies($patient_id) {
        try {
            $db = (new Database())->getConnection();
            
            // Récupérer dernières constantes
            $stmt = $db->prepare("
                SELECT * FROM constantes_vitales 
                WHERE patient_id = ? 
                ORDER BY date_mesure DESC LIMIT 5
            ");
            $stmt->execute([$patient_id]);
            $constantes = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (count($constantes) < 2) {
                return [[
                    'type' => 'INFO',
                    'parametre' => 'données',
                    'gravite' => 'INFO',
                    'message' => 'Données insuffisantes pour détecter des anomalies'
                ]];
            }
            
            $anomalies = [];
            
            for ($i = 0; $i < count($constantes) - 1; $i++) {
                $actuelle = $constantes[$i];
                $precedente = $constantes[$i + 1];
                
                // Variation brutale température
                if ($actuelle['temperature'] && $precedente['temperature']) {
                    $diff_temp = abs($actuelle['temperature'] - $precedente['temperature']);
                    if ($diff_temp > 1.5) {
                        $anomalies[] = [
                            'type' => 'VARIATION_BRUTALE',
                            'parametre' => 'température',
                            'valeur_actuelle' => $actuelle['temperature'],
                            'valeur_precedente' => $precedente['temperature'],
                            'gravite' => $diff_temp > 2 ? 'CRITIQUE' : 'ATTENTION'
                        ];
                    }
                }
            }
            
            return $anomalies ?: [[
                'type' => 'INFO',
                'parametre' => 'aucune',
                'gravite' => 'INFO',
                'message' => 'Aucune anomalie détectée'
            ]];
        } catch (Exception $e) {
            return [[
                'type' => 'ERREUR',
                'parametre' => 'système',
                'gravite' => 'ERREUR',
                'message' => 'Erreur: ' . $e->getMessage()
            ]];
        }
    }
    
    private static function calculerTendance($valeurs) {
        $valeurs = array_filter($valeurs);
        if (count($valeurs) < 2) {
            return ['pente' => 0, 'correlation' => 0];
        }
        
        $n = count($valeurs);
        $x = range(1, $n);
        
        $sum_x = array_sum($x);
        $sum_y = array_sum($valeurs);
        $sum_xy = 0;
        $sum_x2 = 0;
        
        for ($i = 0; $i < $n; $i++) {
            $sum_xy += $x[$i] * $valeurs[$i];
            $sum_x2 += $x[$i] * $x[$i];
        }
        
        $denominateur = ($n * $sum_x2 - $sum_x * $sum_x);
        if ($denominateur == 0) {
            return ['pente' => 0, 'correlation' => 0];
        }
        
        $pente = ($n * $sum_xy - $sum_x * $sum_y) / $denominateur;
        
        return ['pente' => $pente, 'correlation' => abs($pente)];
    }
    
    private static function calculerVariabilite($valeurs) {
        $valeurs = array_filter($valeurs);
        if (count($valeurs) < 2) return 0;
        
        $moyenne = array_sum($valeurs) / count($valeurs);
        $variance = array_sum(array_map(function($x) use ($moyenne) { 
            return pow($x - $moyenne, 2); 
        }, $valeurs)) / count($valeurs);
        
        return sqrt($variance);
    }
    
    private static function calculerScoreDeterioration($constantes) {
        if (empty($constantes)) return 0;
        
        $score = 0;
        $derniere = end($constantes);
        
        // Facteurs de risque
        if ($derniere['temperature'] > 38.5 || $derniere['temperature'] < 36) $score += 20;
        if ($derniere['tension_systolique'] < 100) $score += 25;
        if ($derniere['frequence_cardiaque'] > 100) $score += 15;
        if ($derniere['saturation_o2'] < 95) $score += 30;
        
        return min(100, $score);
    }
    
    private static function calculerProbabilite($tendance) {
        return min(95, abs($tendance['pente']) * 30);
    }
    
    private static function enregistrerAlerte($patient_id, $alerte) {
        try {
            $db = (new Database())->getConnection();
            
            $stmt = $db->prepare("
                INSERT INTO alertes_predictives 
                (patient_id, type, niveau, message, probabilite, action, date_creation) 
                VALUES (?, ?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([
                $patient_id, 
                $alerte['type'], 
                $alerte['niveau'], 
                $alerte['message'], 
                $alerte['probabilite'], 
                $alerte['action']
            ]);
        } catch (Exception $e) {
            // Ignorer les erreurs d'insertion
        }
    }
}
?>