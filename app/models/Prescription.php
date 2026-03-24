<?php
/* ============================================================================
   FICHIER : app/models/Prescription.php
   Modèle pour la gestion des ordonnances (Table ordonnances_pharmacie)
   ============================================================================ */
require_once __DIR__ . '/../../config/database.php';

class Prescription {
    private $db;
    // On définit le nom de la table principale
    private $table = 'ordonnances_pharmacie';

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    /**
     * CRÉATION : Enregistre l'ordonnance et ses médicaments
     */
    public function create($data) {
        try {
            $this->db->beginTransaction();

            // 1. Insertion dans l'en-tête de l'ordonnance
            // Note: Nous utilisons 'date_creation' et 'recommandations' selon votre structure SQL corrigée
            $sql = "INSERT INTO " . $this->table . "
                    (patient_id, medecin_id, consultation_id, date_creation, statut, recommandations)
                    VALUES (:patient_id, :medecin_id, :consultation_id, NOW(), :statut, :recommandations)";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':patient_id'      => $data['patient_id'],
                ':medecin_id'      => $data['medecin_id'],
                ':consultation_id' => $data['consultation_id'] ?? null,
                ':statut'          => $data['statut'] ?? 'EN_ATTENTE',
                ':recommandations' => $data['recommandations'] ?? ($data['notes'] ?? null)
            ]);

            $ordonnance_id = $this->db->lastInsertId();

            // 2. Insertion des médicaments liés
            if (!empty($data['medicaments'])) {
                foreach ($data['medicaments'] as $med) {
                    $sqlMed = "INSERT INTO ordonnance_medicaments
                               (ordonnance_id, medicament_id, nom_medicament, posologie, duree, quantite_prescrite)
                               VALUES (:oid, :mid, :nom, :poso, :duree, :qte)";

                    $stmtMed = $this->db->prepare($sqlMed);
                    $stmtMed->execute([
                        ':oid'  => $ordonnance_id,
                        ':mid'  => $med['id'] ?? null,
                        ':nom'  => $med['nom'] ?? 'Médicament',
                        ':poso' => $med['posologie'] ?? '',
                        ':duree'=> $med['duree'] ?? '',
                        ':qte'  => $med['quantite'] ?? 1
                    ]);
                }
            }

            $this->db->commit();
            return $ordonnance_id;

        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Erreur Prescription::create : " . $e->getMessage());
            return false;
        }
    }

    /**
     * RÉCUPÉRATION : Toutes les ordonnances avec infos patients et médecins
     */
    public function getAll() {
        $sql = "SELECT o.*, pat.nom as patient_nom, pat.prenom as patient_prenom, pat.dossier_numero,
                u.nom as medecin_nom, u.prenom as medecin_prenom
                FROM " . $this->table . " o
                JOIN patients pat ON o.patient_id = pat.id
                JOIN users u ON o.medecin_id = u.id
                ORDER BY o.date_creation DESC";

        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * RÉCUPÉRATION : Une ordonnance précise par son ID
     */
    public function getById($id) {
        $sql = "SELECT o.*,
                pat.nom as patient_nom, pat.prenom as patient_prenom, pat.date_naissance, pat.sexe, pat.dossier_numero, pat.adresse,
                u.nom as medecin_nom, u.prenom as medecin_prenom, u.specialite, u.telephone as medecin_tel
                FROM " . $this->table . " o
                JOIN patients pat ON o.patient_id = pat.id
                JOIN users u ON o.medecin_id = u.id
                WHERE o.id = :id";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * RÉCUPÉRATION : Liste des médicaments d'une ordonnance
     */
    public function getMedicaments($ordonnance_id) {
        $sql = "SELECT om.*, m.forme, m.dosage
                FROM ordonnance_medicaments om
                LEFT JOIN medicaments m ON om.medicament_id = m.id
                WHERE om.ordonnance_id = :oid";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':oid' => $ordonnance_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * RÉCUPÉRATION : Historique pour un patient précis
     */
    public function getByPatient($patient_id) {
        $sql = "SELECT o.*, u.nom as medecin_nom, u.prenom as medecin_prenom
                FROM " . $this->table . " o
                JOIN users u ON o.medecin_id = u.id
                WHERE o.patient_id = :pid
                ORDER BY o.date_creation DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':pid' => $patient_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * CONFIG : Récupère les paramètres de l'hôpital pour l'impression
     */
    public function getHopitalSettings() {
        try {
            $stmt = $this->db->query("SELECT * FROM settings LIMIT 1");
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [
                'nom_hopital' => 'HÔPITAL SAINT-JEAN DE MALTE',
                'adresse' => 'BP 56 Njombé, Cameroun',
                'telephone' => '+237 697 09 29 92'
            ];
        }
    }
}