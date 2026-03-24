<?php
class AlerteMedicaleService {
    private $db;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }
    
    public function verifierInteractions($patient_id, $medicaments) {
        $alertes = [];
        
        // Vérifier les interactions entre médicaments
        for ($i = 0; $i < count($medicaments); $i++) {
            for ($j = $i + 1; $j < count($medicaments); $j++) {
                $interaction = $this->getInteraction($medicaments[$i], $medicaments[$j]);
                if ($interaction) {
                    $alertes[] = [
                        'type' => 'interaction',
                        'niveau' => $this->mapGraviteToUrgence($interaction['niveau_gravite']),
                        'titre' => 'Interaction médicamenteuse détectée',
                        'message' => "Interaction {$interaction['niveau_gravite']} entre {$medicaments[$i]} et {$medicaments[$j]}: {$interaction['description']}",
                        'medicament' => $medicaments[$i] . ' + ' . $medicaments[$j],
                        'recommandation' => $interaction['recommandation']
                    ];
                }
            }
        }
        
        // Vérifier les allergies du patient
        $allergies = $this->getAllergiesPatient($patient_id);
        foreach ($medicaments as $medicament) {
            foreach ($allergies as $allergie) {
                if (stripos($medicament, $allergie['allergene']) !== false) {
                    $alertes[] = [
                        'type' => 'allergie',
                        'niveau' => $this->mapGraviteToUrgence($allergie['gravite']),
                        'titre' => 'ALLERGIE DÉTECTÉE',
                        'message' => "Patient allergique à {$allergie['allergene']} (gravité: {$allergie['gravite']}). Symptômes: {$allergie['symptomes']}",
                        'medicament' => $medicament
                    ];
                }
            }
        }
        
        return $alertes;
    }
    
    private function getInteraction($med1, $med2) {
        $sql = "SELECT * FROM interactions_medicamenteuses 
                WHERE (medicament_1 LIKE :med1 AND medicament_2 LIKE :med2)
                   OR (medicament_1 LIKE :med2 AND medicament_2 LIKE :med1)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':med1' => "%$med1%",
            ':med2' => "%$med2%"
        ]);
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    private function getAllergiesPatient($patient_id) {
        $sql = "SELECT * FROM allergies_patients WHERE patient_id = :patient_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':patient_id' => $patient_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function creerAlerte($patient_id, $type, $niveau, $titre, $message, $medicament = null) {
        $sql = "INSERT INTO alertes_medicales (patient_id, type_alerte, niveau_urgence, titre, message, medicament_concerne, created_by)
                VALUES (:patient_id, :type, :niveau, :titre, :message, :medicament, :created_by)";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':patient_id' => $patient_id,
            ':type' => $type,
            ':niveau' => $niveau,
            ':titre' => $titre,
            ':message' => $message,
            ':medicament' => $medicament,
            ':created_by' => $_SESSION['user_id'] ?? null
        ]);
    }
    
    public function getAlertesActives($patient_id) {
        $sql = "SELECT * FROM alertes_medicales 
                WHERE patient_id = :patient_id AND statut = 'active'
                ORDER BY niveau_urgence DESC, created_at DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':patient_id' => $patient_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    private function mapGraviteToUrgence($gravite) {
        $mapping = [
            'leger' => 'info',
            'legere' => 'info',
            'modere' => 'attention',
            'moderee' => 'attention',
            'grave' => 'danger',
            'severe' => 'danger',
            'contre_indique' => 'critique',
            'anaphylaxie' => 'critique'
        ];
        
        return $mapping[$gravite] ?? 'attention';
    }
    
    public function ajouterAllergie($patient_id, $type, $allergene, $gravite, $symptomes = null) {
        $sql = "INSERT INTO allergies_patients (patient_id, type_allergie, allergene, gravite, symptomes, date_detection)
                VALUES (:patient_id, :type, :allergene, :gravite, :symptomes, CURDATE())";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':patient_id' => $patient_id,
            ':type' => $type,
            ':allergene' => $allergene,
            ':gravite' => $gravite,
            ':symptomes' => $symptomes
        ]);
    }
}
?>