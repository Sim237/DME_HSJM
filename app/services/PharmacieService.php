<?php

class PharmacieService {
    private $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    public function verifierStock($medicament_id, $quantite_demandee) {
        $stmt = $this->db->prepare("SELECT nom, quantite, unite FROM medicaments WHERE id = ?");
        $stmt->execute([$medicament_id]);
        $medicament = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$medicament) {
            return ['disponible' => false, 'message' => 'Médicament introuvable'];
        }

        if ($medicament['quantite'] <= 0) {
            return [
                'disponible' => false,
                'message' => "RUPTURE DE STOCK - {$medicament['nom']} non disponible",
                'stock_actuel' => 0
            ];
        }

        if ($quantite_demandee > $medicament['quantite']) {
            return [
                'disponible' => false,
                'message' => "Stock insuffisant - {$medicament['nom']} (Demandé: {$quantite_demandee}, Stock: {$medicament['quantite']} {$medicament['unite']})",
                'stock_actuel' => $medicament['quantite']
            ];
        }

        return [
            'disponible' => true,
            'message' => 'Stock disponible',
            'stock_actuel' => $medicament['quantite']
        ];
    }

    public function getMedicamentsDisponibles() {
        $stmt = $this->db->prepare("SELECT id, nom, forme, dosage, quantite, unite FROM medicaments WHERE quantite > 0 ORDER BY nom");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function creerOrdonnancePharmacie($consultation_id, $medicaments) {
        try {
            $this->db->beginTransaction();

            // 1. Récupération des infos de consultation
            $stmtC = $this->db->prepare("SELECT patient_id, medecin_id FROM consultations WHERE id = ?");
            $stmtC->execute([$consultation_id]);
            $c = $stmtC->fetch(PDO::FETCH_ASSOC);
            if (!$c) throw new Exception("Consultation introuvable");

            // 2. Création de l'ordonnance
            $stmtO = $this->db->prepare("INSERT INTO ordonnances_pharmacie (patient_id, medecin_id, consultation_id, statut, date_creation) VALUES (?, ?, ?, 'EN_ATTENTE', NOW())");
            $stmtO->execute([$c['patient_id'], $c['medecin_id'], $consultation_id]);
            $ordonnance_id = $this->db->lastInsertId();

            // 3. Insertion de TOUS les médicaments (La boucle)
            $sqlL = "INSERT INTO ordonnance_medicaments (ordonnance_id, medicament_id, nom_medicament, quantite, posologie, duree, disponible) VALUES (?, ?, ?, ?, ?, ?, 1)";
            $stmtL = $this->db->prepare($sqlL);

            foreach ($medicaments as $m) {
                // On vérifie si c'est un ID (stock) ou un nom (manuel)
                $medId = (isset($m['medicament_id']) && is_numeric($m['medicament_id'])) ? $m['medicament_id'] : null;
                $nomMed = $m['nom_medicament'] ?? ($m['nom'] ?? 'Inconnu');

                $stmtL->execute([
                    $ordonnance_id,
                    $medId,
                    $nomMed,
                    $m['quantite'],
                    $m['posologie'],
                    $m['duree']
                ]);
            }

            $this->db->commit();
            return $ordonnance_id;
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("ERREUR PHARMACIE : " . $e->getMessage());
            return false;
        }
    }


    public function getOrdonnancesEnAttente() {
        $stmt = $this->db->prepare("
            SELECT o.*, c.patient_id, p.nom, p.prenom, p.dossier_numero,
                   u.nom as medecin_nom, u.prenom as medecin_prenom
            FROM ordonnances_pharmacie o
            JOIN consultations c ON o.consultation_id = c.id
            JOIN patients p ON c.patient_id = p.id
            JOIN users u ON c.medecin_id = u.id
            WHERE o.statut = 'EN_ATTENTE'
            ORDER BY o.date_creation DESC
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}