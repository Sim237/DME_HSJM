<?php
require_once __DIR__ . '/../../config/database.php';

class Hospitalisation {
    private $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    // Récupérer les infos complètes de l'admission (Patient + Lit + Service)
    public function getDossierAdmission($admission_id) {
        $sql = "SELECT a.*, p.nom, p.prenom, p.dossier_numero, p.date_naissance, p.sexe,
                       l.numero as lit_numero, l.chambre, s.nom as service_nom
                FROM admissions a
                JOIN patients p ON a.patient_id = p.id
                JOIN lits l ON a.lit_id = l.id
                JOIN services s ON l.service_id = s.id
                WHERE a.id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $admission_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Ajouter des constantes
    public function addConstantes($data) {
        // CORRECTION : Utilisation des noms de colonnes exacts de votre base
        // observation (sans s)
        // pression_arterielle_systolique au lieu de tension_sys
        
        $sql = "INSERT INTO parametres_vitaux 
                (admission_id, patient_id, user_id, temperature, 
                 pression_arterielle_systolique, pression_arterielle_diastolique, 
                 frequence_cardiaque, saturation_oxygene, observation, date_mesure)
                VALUES (:adm, :pat, :user, :temp, :sys, :dia, :pouls, :spo2, :obs, NOW())";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':adm' => $data['admission_id'],
            ':pat' => $data['patient_id'],
            ':user' => $data['user_id'],
            ':temp' => $data['temperature'],
            ':sys' => $data['tension_sys'], // On garde la clé du tableau $_POST
            ':dia' => $data['tension_dia'], // Idem
            ':pouls' => $data['frequence_cardiaque'],
            ':spo2' => $data['saturation_oxygene'],
            ':obs' => $data['observations'] // On mappe 'observations' (POST) vers 'observation' (SQL)
        ]);
    }

    // Récupérer l'historique des constantes (Pour les graphiques)
    public function getHistoriqueConstantes($admission_id) {
        // On alias les colonnes pour que la vue (suivi.php) continue de fonctionner sans modif
        $sql = "SELECT *, 
                pression_arterielle_systolique as tension_sys,
                pression_arterielle_diastolique as tension_dia
                FROM parametres_vitaux 
                WHERE admission_id = :id 
                ORDER BY date_mesure ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $admission_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    // Gestion des Soins
    public function addSoin($data) {
        $sql = "INSERT INTO soins_hospitalisation 
                (admission_id, user_id_planificateur, type_soin, description, date_prevue, statut)
                VALUES (:adm, :user, :type, :desc, :date, 'PLANIFIE')";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':adm' => $data['admission_id'],
            ':user' => $data['user_id'],
            ':type' => $data['type_soin'],
            ':desc' => $data['description'],
            ':date' => $data['date_prevue']
        ]);
    }

    public function getSoins($admission_id, $filtre = 'all') {
        $sql = "SELECT s.*, u.nom as planificateur_nom 
                FROM soins_hospitalisation s
                LEFT JOIN users u ON s.user_id_planificateur = u.id
                WHERE s.admission_id = :id";
        
        if ($filtre === 'jour') {
            $sql .= " AND DATE(s.date_prevue) = CURDATE()";
        }
        
        $sql .= " ORDER BY s.date_prevue ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $admission_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function validerSoin($soin_id, $user_id, $note) {
        $sql = "UPDATE soins_hospitalisation SET 
                statut = 'REALISE', 
                date_realisee = NOW(), 
                user_id_executant = :user, 
                note_execution = :note 
                WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':user' => $user_id,
            ':note' => $note,
            ':id' => $soin_id
        ]);
    }

     public function getPatientsHospitalises() {
        $sql = "SELECT p.id as patient_id, p.nom, p.prenom, p.dossier_numero, p.date_naissance, p.sexe,
                       a.id as admission_id, a.date_admission, a.motif_admission,
                       l.numero as lit_numero, l.chambre, s.nom as service_nom
                FROM patients p
                JOIN admissions a ON p.id = a.patient_id
                JOIN lits l ON a.lit_id = l.id
                JOIN services s ON l.service_id = s.id
                WHERE p.statut = 'HOSPITALISE' 
                AND a.statut = 'EN_COURS'
                ORDER BY s.nom, l.numero";
        
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>