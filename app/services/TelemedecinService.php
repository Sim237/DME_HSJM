<?php

class TelemedecinService {
    private $pdo;
    
    public function __construct() {
        global $pdo;
        $this->pdo = $pdo;
        
        // Si $pdo global n'existe pas, on essaie de se connecter
        if (!$this->pdo) {
            try {
                $this->pdo = new PDO(
                    "mysql:host=localhost;dbname=dme_hospital;charset=utf8",
                    "root",
                    "",
                    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
                );
            } catch (PDOException $e) {
                error_log('Erreur connexion PDO: ' . $e->getMessage());
                $this->pdo = null;
            }
        }
    }
    
    // Planifier consultation télémédecine
    public function planifierConsultation($patient_id, $medecin_id, $type, $date_consultation, $motif) {
        $room_id = 'room_' . uniqid();
        $lien_reunion = "https://meet.jit.si/" . $room_id;
        
        $stmt = $this->pdo->prepare("
            INSERT INTO telemedecine_consultations 
            (patient_id, medecin_id, type, date_consultation, room_id, lien_reunion, motif) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        
        return $stmt->execute([$patient_id, $medecin_id, $type, $date_consultation, $room_id, $lien_reunion, $motif]);
    }
    
    // Obtenir consultations du jour
    public function getConsultationsJour($medecin_id = null) {
        if (!$this->pdo) {
            return [];
        }
        
        try {
            $sql = "
                SELECT tc.*, p.nom, p.prenom, u.nom as medecin_nom
                FROM telemedecine_consultations tc
                LEFT JOIN patients p ON tc.patient_id = p.id
                LEFT JOIN users u ON tc.medecin_id = u.id
                WHERE DATE(tc.date_consultation) = CURDATE()
            ";
            
            if ($medecin_id) {
                $sql .= " AND tc.medecin_id = ?";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([$medecin_id]);
            } else {
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute();
            }
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log('Erreur getConsultationsJour: ' . $e->getMessage());
            return [];
        }
    }
    
    // Démarrer consultation
    public function demarrerConsultation($consultation_id) {
        $stmt = $this->pdo->prepare("
            UPDATE telemedecine_consultations 
            SET statut = 'en_cours' 
            WHERE id = ?
        ");
        
        return $stmt->execute([$consultation_id]);
    }
    
    // Terminer consultation avec diagnostic
    public function terminerConsultation($consultation_id, $diagnostic, $prescription = null) {
        $stmt = $this->pdo->prepare("
            UPDATE telemedecine_consultations 
            SET statut = 'termine', diagnostic = ?, prescription = ? 
            WHERE id = ?
        ");
        
        return $stmt->execute([$diagnostic, $prescription, $consultation_id]);
    }
    
    // Ajouter données surveillance
    public function ajouterSurveillance($patient_id, $medecin_id, $type_donnee, $valeur, $unite, $date_mesure) {
        // Vérifier si alerte nécessaire
        $alerte = $this->verifierAlerte($type_donnee, $valeur);
        
        $stmt = $this->pdo->prepare("
            INSERT INTO telemedecine_surveillance 
            (patient_id, medecin_id, type_donnee, valeur, unite, date_mesure, alerte) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        
        return $stmt->execute([$patient_id, $medecin_id, $type_donnee, $valeur, $unite, $date_mesure, $alerte]);
    }
    
    // Vérifier seuils d'alerte
    private function verifierAlerte($type_donnee, $valeur) {
        $seuils = [
            'tension' => ['min' => 90, 'max' => 140],
            'glycemie' => ['min' => 0.7, 'max' => 1.4],
            'temperature' => ['min' => 36, 'max' => 38],
            'frequence_cardiaque' => ['min' => 60, 'max' => 100]
        ];
        
        if (isset($seuils[$type_donnee])) {
            return $valeur < $seuils[$type_donnee]['min'] || $valeur > $seuils[$type_donnee]['max'];
        }
        
        return false;
    }
    
    // Obtenir alertes actives
    public function getAlertes($medecin_id = null) {
        if (!$this->pdo) {
            return [];
        }
        
        try {
            $sql = "
                SELECT ts.*, p.nom, p.prenom
                FROM telemedecine_surveillance ts
                LEFT JOIN patients p ON ts.patient_id = p.id
                WHERE ts.alerte = 1 AND DATE(ts.date_mesure) = CURDATE()
            ";
            
            if ($medecin_id) {
                $sql .= " AND ts.medecin_id = ?";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([$medecin_id]);
            } else {
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute();
            }
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log('Erreur getAlertes: ' . $e->getMessage());
            return [];
        }
    }
    
    // Uploader document
    public function uploaderDocument($consultation_id, $fichier, $partage_par) {
        $upload_dir = __DIR__ . '/../../uploads/telemedecine/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        $nom_fichier = time() . '_' . $fichier['name'];
        $chemin_complet = $upload_dir . $nom_fichier;
        
        if (move_uploaded_file($fichier['tmp_name'], $chemin_complet)) {
            $stmt = $this->pdo->prepare("
                INSERT INTO telemedecine_documents 
                (consultation_id, nom_fichier, chemin_fichier, type_fichier, taille_ko, partage_par) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            
            $type_fichier = $this->getTypeFichier($fichier['type']);
            $taille_ko = round($fichier['size'] / 1024);
            
            return $stmt->execute([
                $consultation_id, 
                $fichier['name'], 
                $chemin_complet, 
                $type_fichier, 
                $taille_ko, 
                $partage_par
            ]);
        }
        
        return false;
    }
    
    private function getTypeFichier($mime_type) {
        if (strpos($mime_type, 'image') !== false) return 'image';
        if (strpos($mime_type, 'pdf') !== false) return 'pdf';
        if (strpos($mime_type, 'video') !== false) return 'video';
        if (strpos($mime_type, 'audio') !== false) return 'audio';
        return 'autre';
    }
}
?>