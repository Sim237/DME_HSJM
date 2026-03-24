<?php

class NotificationResultatService {
    private $db;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }
    
    public function notifierResultatDisponible($demande_id) {
        try {
            // Récupérer infos demande et médecin
            $stmt = $this->db->prepare("
                SELECT d.*, c.medecin_id, c.patient_id, p.nom, p.prenom
                FROM demandes_laboratoire d
                JOIN consultations c ON d.consultation_id = c.id
                JOIN patients p ON c.patient_id = p.id
                WHERE d.id = ?
            ");
            $stmt->execute([$demande_id]);
            $demande = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$demande) return false;
            
            // Compter examens terminés
            $stmt = $this->db->prepare("
                SELECT COUNT(*) as nb_termines
                FROM demande_examens 
                WHERE demande_id = ? AND statut = 'TERMINE'
            ");
            $stmt->execute([$demande_id]);
            $nb_termines = $stmt->fetch()['nb_termines'];
            
            // Créer notification
            $titre = "Résultats disponibles - {$demande['nom']} {$demande['prenom']}";
            $message = "{$nb_termines} examen(s) terminé(s) et disponible(s) pour consultation";
            
            $stmt = $this->db->prepare("
                INSERT INTO notifications_medecin (medecin_id, patient_id, type, titre, message, demande_id)
                VALUES (?, ?, 'RESULTATS_LABO', ?, ?, ?)
            ");
            $stmt->execute([
                $demande['medecin_id'],
                $demande['patient_id'],
                $titre,
                $message,
                $demande_id
            ]);
            
            // Mettre à jour statut demande
            $stmt = $this->db->prepare("
                UPDATE demandes_laboratoire 
                SET statut = 'RESULTATS_PRETS', date_resultats = NOW() 
                WHERE id = ?
            ");
            $stmt->execute([$demande_id]);
            
            // Copier résultats dans historique patient
            $this->copierResultatsDansHistorique($demande_id);
            
            return true;
            
        } catch (Exception $e) {
            error_log("Erreur notification résultat: " . $e->getMessage());
            return false;
        }
    }
    
    private function copierResultatsDansHistorique($demande_id) {
        // Récupérer examens terminés avec résultats
        $stmt = $this->db->prepare("
            SELECT de.*, el.nom as nom_examen, el.valeur_normale_min, el.valeur_normale_max, el.unite,
                   d.consultation_id, c.patient_id, c.medecin_id
            FROM demande_examens de
            JOIN examens_laboratoire el ON de.examen_id = el.id
            JOIN demandes_laboratoire d ON de.demande_id = d.id
            JOIN consultations c ON d.consultation_id = c.id
            WHERE de.demande_id = ? AND de.statut = 'TERMINE' AND de.resultat IS NOT NULL
        ");
        $stmt->execute([$demande_id]);
        $examens = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($examens as $examen) {
            // Vérifier si anormal
            $anormal = false;
            if ($examen['valeur_numerique'] && $examen['valeur_normale_min'] && $examen['valeur_normale_max']) {
                $anormal = ($examen['valeur_numerique'] < $examen['valeur_normale_min'] || 
                           $examen['valeur_numerique'] > $examen['valeur_normale_max']);
            }
            
            $stmt = $this->db->prepare("
                INSERT INTO patient_resultats_labo 
                (patient_id, demande_id, examen_id, nom_examen, resultat, valeur_numerique, unite, 
                 valeur_normale_min, valeur_normale_max, interpretation, anormal, medecin_prescripteur_id)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $examen['patient_id'],
                $demande_id,
                $examen['examen_id'],
                $examen['nom_examen'],
                $examen['resultat'],
                $examen['valeur_numerique'],
                $examen['unite'],
                $examen['valeur_normale_min'],
                $examen['valeur_normale_max'],
                $examen['interpretation'],
                $anormal,
                $examen['medecin_id']
            ]);
        }
    }
    
    public function getNotificationsMedecin($medecin_id, $non_lues_seulement = false) {
        $sql = "SELECT * FROM notifications_medecin WHERE medecin_id = ?";
        if ($non_lues_seulement) {
            $sql .= " AND lu = FALSE";
        }
        $sql .= " ORDER BY date_creation DESC LIMIT 20";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$medecin_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function marquerLue($notification_id) {
        $stmt = $this->db->prepare("UPDATE notifications_medecin SET lu = TRUE WHERE id = ?");
        return $stmt->execute([$notification_id]);
    }
}