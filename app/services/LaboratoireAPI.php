<?php

class LaboratoireAPI {
    
    public static function getResultatsTempsReel($patient_id = null) {
        header('Content-Type: application/json');
        
        try {
            $db = (new Database())->getConnection();
            
            $where = $patient_id ? "WHERE lr.patient_id = ?" : "";
            $params = $patient_id ? [$patient_id] : [];
            
            $stmt = $db->prepare("
                SELECT 
                    lr.id,
                    lr.patient_id,
                    p.nom,
                    p.prenom,
                    lr.examen_id,
                    e.nom as examen_nom,
                    lr.valeur,
                    lr.unite,
                    lr.valeur_normale_min,
                    lr.valeur_normale_max,
                    lr.anormal,
                    lr.date_resultat,
                    lr.statut,
                    lr.technicien_id,
                    u.nom as technicien_nom
                FROM laboratoire_resultats lr
                JOIN patients p ON lr.patient_id = p.id
                JOIN examens_laboratoire e ON lr.examen_id = e.id
                LEFT JOIN users u ON lr.technicien_id = u.id
                $where
                ORDER BY lr.date_resultat DESC
                LIMIT 50
            ");
            $stmt->execute($params);
            
            $resultats = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Enrichir avec statut critique
            foreach ($resultats as &$resultat) {
                $resultat['critique'] = self::evaluerCriticite($resultat);
                $resultat['tendance'] = self::calculerTendance($resultat['patient_id'], $resultat['examen_id']);
            }
            
            return $resultats;
        } catch (Exception $e) {
            return [
                'error' => $e->getMessage(),
                'resultats' => []
            ];
        }
    }
    
    public static function getResultatsPatient($patient_id) {
        header('Content-Type: application/json');
        
        try {
            $db = (new Database())->getConnection();
            
            $stmt = $db->prepare("
                SELECT 
                    lr.*,
                    e.nom as examen_nom,
                    e.categorie,
                    u.nom as technicien_nom
                FROM laboratoire_resultats lr
                JOIN examens_laboratoire e ON lr.examen_id = e.id
                LEFT JOIN users u ON lr.technicien_id = u.id
                WHERE lr.patient_id = ?
                ORDER BY lr.date_resultat DESC
            ");
            $stmt->execute([$patient_id]);
            
            $resultats = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Grouper par examen pour historique
            $groupes = [];
            foreach ($resultats as $resultat) {
                $groupes[$resultat['examen_nom']][] = $resultat;
            }
            
            return [
                'patient_id' => $patient_id,
                'resultats_recents' => array_slice($resultats, 0, 10),
                'historique_par_examen' => $groupes,
                'alertes' => self::detecterAlertes($patient_id)
            ];
        } catch (Exception $e) {
            http_response_code(500);
            return ['error' => $e->getMessage()];
        }
    }
    
    public static function ajouterResultat() {
        header('Content-Type: application/json');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            return ['error' => 'Méthode non autorisée'];
        }
        
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            
            $required = ['patient_id', 'examen_id', 'valeur', 'unite'];
            foreach ($required as $field) {
                if (!isset($data[$field])) {
                    http_response_code(400);
                    return ['error' => "Champ requis: $field"];
                }
            }
            
            $db = (new Database())->getConnection();
            
            // Récupérer valeurs normales
            $stmt = $db->prepare("SELECT valeur_min, valeur_max FROM examens_laboratoire WHERE id = ?");
            $stmt->execute([$data['examen_id']]);
            $examen = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $anormal = 0;
            if ($examen) {
                $valeur = floatval($data['valeur']);
                if ($valeur < $examen['valeur_min'] || $valeur > $examen['valeur_max']) {
                    $anormal = 1;
                }
            }
            
            $stmt = $db->prepare("
                INSERT INTO laboratoire_resultats 
                (patient_id, examen_id, valeur, unite, valeur_normale_min, valeur_normale_max, 
                 anormal, date_resultat, technicien_id, statut, observations)
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), ?, 'valide', ?)
            ");
            
            $success = $stmt->execute([
                $data['patient_id'],
                $data['examen_id'],
                $data['valeur'],
                $data['unite'],
                $examen['valeur_min'] ?? null,
                $examen['valeur_max'] ?? null,
                $anormal,
                $_SESSION['user_id'] ?? 1,
                $data['observations'] ?? ''
            ]);
            
            if ($success) {
                $resultat_id = $db->lastInsertId();
                
                // Notifier si anormal
                if ($anormal) {
                    self::notifierResultatAnormal($resultat_id);
                }
                
                return [
                    'success' => true,
                    'resultat_id' => $resultat_id,
                    'anormal' => $anormal
                ];
            } else {
                http_response_code(500);
                return ['error' => 'Erreur lors de l\'ajout'];
            }
        } catch (Exception $e) {
            http_response_code(500);
            return ['error' => $e->getMessage()];
        }
    }
    
    public static function getAlertesCritiques() {
        header('Content-Type: application/json');
        
        try {
            $db = (new Database())->getConnection();
            
            $stmt = $db->prepare("
                SELECT 
                    lr.id,
                    p.nom,
                    p.prenom,
                    e.nom as examen,
                    lr.valeur,
                    lr.unite,
                    lr.valeur_normale_min,
                    lr.valeur_normale_max,
                    lr.date_resultat,
                    CASE 
                        WHEN lr.valeur < lr.valeur_normale_min * 0.5 OR lr.valeur > lr.valeur_normale_max * 2 THEN 'CRITIQUE'
                        WHEN lr.anormal = 1 THEN 'ANORMAL'
                        ELSE 'NORMAL'
                    END as niveau_alerte
                FROM laboratoire_resultats lr
                JOIN patients p ON lr.patient_id = p.id
                JOIN examens_laboratoire e ON lr.examen_id = e.id
                WHERE lr.anormal = 1 
                AND lr.date_resultat > DATE_SUB(NOW(), INTERVAL 24 HOUR)
                ORDER BY lr.date_resultat DESC
            ");
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            http_response_code(500);
            return ['error' => $e->getMessage()];
        }
    }
    
    public static function getStatistiques() {
        header('Content-Type: application/json');
        
        try {
            $db = (new Database())->getConnection();
            
            // Statistiques générales
            $stats = [];
            
            // Résultats aujourd'hui
            $stmt = $db->prepare("
                SELECT COUNT(*) as total FROM laboratoire_resultats 
                WHERE DATE(date_resultat) = CURDATE()
            ");
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $stats['resultats_aujourd_hui'] = $result ? $result['total'] : 0;
            
            // Résultats anormaux
            $stmt = $db->prepare("
                SELECT COUNT(*) as total FROM laboratoire_resultats 
                WHERE anormal = 1 AND DATE(date_resultat) = CURDATE()
            ");
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $stats['anormaux_aujourd_hui'] = $result ? $result['total'] : 0;
            
            // En attente de validation
            $stmt = $db->prepare("
                SELECT COUNT(*) as total FROM laboratoire_resultats 
                WHERE statut = 'en_attente'
            ");
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $stats['en_attente'] = $result ? $result['total'] : 0;
            
            // Examens par catégorie (dernières 24h)
            $stmt = $db->prepare("
                SELECT e.categorie, COUNT(*) as nombre
                FROM laboratoire_resultats lr
                JOIN examens_laboratoire e ON lr.examen_id = e.id
                WHERE lr.date_resultat > DATE_SUB(NOW(), INTERVAL 24 HOUR)
                GROUP BY e.categorie
            ");
            $stmt->execute();
            $stats['par_categorie'] = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
            
            return $stats;
        } catch (Exception $e) {
            // Retourner des valeurs par défaut en cas d'erreur
            return [
                'resultats_aujourd_hui' => 0,
                'anormaux_aujourd_hui' => 0,
                'en_attente' => 0,
                'par_categorie' => [],
                'error' => $e->getMessage()
            ];
        }
    }
    
    private static function evaluerCriticite($resultat) {
        if (!$resultat['anormal']) return 'NORMAL';
        
        $valeur = floatval($resultat['valeur']);
        $min = floatval($resultat['valeur_normale_min']);
        $max = floatval($resultat['valeur_normale_max']);
        
        if ($valeur < $min * 0.5 || $valeur > $max * 2) {
            return 'CRITIQUE';
        }
        
        return 'ANORMAL';
    }
    
    private static function calculerTendance($patient_id, $examen_id) {
        try {
            $db = (new Database())->getConnection();
            
            $stmt = $db->prepare("
                SELECT valeur FROM laboratoire_resultats 
                WHERE patient_id = ? AND examen_id = ?
                ORDER BY date_resultat DESC LIMIT 3
            ");
            $stmt->execute([$patient_id, $examen_id]);
            $valeurs = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            if (count($valeurs) < 2) return 'STABLE';
            
            $derniere = $valeurs[0];
            $precedente = $valeurs[1];
            
            $variation = (($derniere - $precedente) / $precedente) * 100;
            
            if ($variation > 10) return 'HAUSSE';
            if ($variation < -10) return 'BAISSE';
            return 'STABLE';
        } catch (Exception $e) {
            return 'INCONNU';
        }
    }
    
    private static function detecterAlertes($patient_id) {
        try {
            $db = (new Database())->getConnection();
            
            $stmt = $db->prepare("
                SELECT COUNT(*) as nb_anormaux
                FROM laboratoire_resultats 
                WHERE patient_id = ? AND anormal = 1 
                AND date_resultat > DATE_SUB(NOW(), INTERVAL 24 HOUR)
            ");
            $stmt->execute([$patient_id]);
            $nb_anormaux = $stmt->fetch(PDO::FETCH_ASSOC)['nb_anormaux'];
            
            $alertes = [];
            if ($nb_anormaux > 2) {
                $alertes[] = [
                    'type' => 'MULTIPLE_ANORMAUX',
                    'message' => "$nb_anormaux résultats anormaux en 24h",
                    'niveau' => 'ATTENTION'
                ];
            }
            
            return $alertes;
        } catch (Exception $e) {
            return [];
        }
    }
    
    private static function notifierResultatAnormal($resultat_id) {
        // Notification automatique aux médecins
        try {
            $db = (new Database())->getConnection();
            
            $stmt = $db->prepare("
                SELECT lr.*, p.nom, p.prenom, e.nom as examen_nom
                FROM laboratoire_resultats lr
                JOIN patients p ON lr.patient_id = p.id
                JOIN examens_laboratoire e ON lr.examen_id = e.id
                WHERE lr.id = ?
            ");
            $stmt->execute([$resultat_id]);
            $resultat = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($resultat) {
                $message = "Résultat anormal: {$resultat['nom']} {$resultat['prenom']} - {$resultat['examen_nom']}: {$resultat['valeur']} {$resultat['unite']}";
                
                // Insérer notification
                $stmt = $db->prepare("
                    INSERT INTO notifications_automatiques 
                    (patient_id, type, message, date_creation, statut)
                    VALUES (?, 'ALERTE_CRITIQUE', ?, NOW(), 'active')
                ");
                $stmt->execute([$resultat['patient_id'], $message]);
            }
        } catch (Exception $e) {
            // Ignorer les erreurs de notification
        }
    }
}
?>