<?php
/* ============================================================================
   FICHIER : Patient.php
   Modèle pour la gestion des patients
   ============================================================================ */
require_once __DIR__ . '/../../config/database.php';

class Patient {
    private $db;
    protected $table = 'patients';

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    // --- CRÉATION ---

    public function create($data) {
        try {
            $dossier_numero = $this->genererNumeroDossier();

            $sql = "INSERT INTO patients
                    (dossier_numero, nom, prenom, date_naissance, sexe,
                     telephone, email, adresse, groupe_sanguin,
                     contact_nom, contact_telephone,
                     antecedents_medicaux, allergies,
                     statut, actif, created_at)
                    VALUES
                    (:dossier_numero, :nom, :prenom, :date_naissance, :sexe,
                     :telephone, :email, :adresse, :groupe_sanguin,
                     :contact_nom, :contact_telephone,
                     :antecedents_medicaux, :allergies,
                     :statut, 1, NOW())";

            $stmt = $this->db->prepare($sql);

            $result = $stmt->execute([
                ':dossier_numero' => $dossier_numero,
                ':nom' => strtoupper($data['nom']),
                ':prenom' => ucwords(strtolower($data['prenom'])),
                ':date_naissance' => $data['date_naissance'],
                ':sexe' => $data['sexe'],
                ':telephone' => $data['telephone'] ?? null,
                ':email' => $data['email'] ?? null,
                ':adresse' => $data['adresse'] ?? null,
                ':groupe_sanguin' => $data['groupe_sanguin'] ?? null,
                ':contact_nom' => $data['contact_nom'] ?? null,
                ':contact_telephone' => $data['contact_telephone'] ?? null,
                ':antecedents_medicaux' => $data['antecedents_medicaux'] ?? null,
                ':allergies' => $data['allergies'] ?? null,
                ':statut' => $data['statut'] ?? 'EXTERNE'
            ]);

            if ($result) {
                return $this->db->lastInsertId();
            }
            return false;

        } catch (Exception $e) {
            error_log("Erreur création patient: " . $e->getMessage());
            return false;
        }
    }

    // --- LECTURE ---

    public function getById($id) {
        $sql = "SELECT * FROM patients WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getAll($limit = null, $offset = null) {
        $sql = "SELECT * FROM patients WHERE actif = 1 ORDER BY nom, prenom";

        if ($limit !== null) {
            $sql .= " LIMIT :limit";
            if ($offset !== null) {
                $sql .= " OFFSET :offset";
            }
        }

        $stmt = $this->db->prepare($sql);

        if ($limit !== null) {
            $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
            if ($offset !== null) {
                $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
            }
        }

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function search($query) {
        $sql = "SELECT * FROM patients
                WHERE (nom LIKE :query
                   OR prenom LIKE :query
                   OR dossier_numero LIKE :query)
                   AND actif = 1
                ORDER BY nom, prenom
                LIMIT 20";

        $stmt = $this->db->prepare($sql);
        $searchTerm = '%' . $query . '%';
        $stmt->execute([':query' => $searchTerm]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // --- MISE À JOUR & SUPPRESSION ---

    public function update($id, $data) {
        $sql = "UPDATE patients SET
                nom = :nom, prenom = :prenom, date_naissance = :date_naissance,
                sexe = :sexe, telephone = :telephone, email = :email,
                adresse = :adresse, groupe_sanguin = :groupe_sanguin,
                contact_nom = :contact_nom, contact_telephone = :contact_telephone,
                antecedents_medicaux = :antecedents_medicaux, allergies = :allergies,
                statut = :statut
                WHERE id = :id";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            ':nom' => strtoupper($data['nom']),
            ':prenom' => ucwords(strtolower($data['prenom'])),
            ':date_naissance' => $data['date_naissance'],
            ':sexe' => $data['sexe'],
            ':telephone' => $data['telephone'] ?? null,
            ':email' => $data['email'] ?? null,
            ':adresse' => $data['adresse'] ?? null,
            ':groupe_sanguin' => $data['groupe_sanguin'] ?? null,
            ':contact_nom' => $data['contact_nom'] ?? null,
            ':contact_telephone' => $data['contact_telephone'] ?? null,
            ':antecedents_medicaux' => $data['antecedents_medicaux'] ?? null,
            ':allergies' => $data['allergies'] ?? null,
            ':statut' => $data['statut'] ?? 'EXTERNE',
            ':id' => $id
        ]);
    }

    public function delete($id) {
        $sql = "UPDATE patients SET actif = 0 WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }


    /**
     * Enregistre les constantes vitales
     */
    public function addParametres($data) {
        $sql = "INSERT INTO patient_parametres
                (patient_id, user_id, poids, taille, temperature,
                 pression_arterielle_systolique, pression_arterielle_diastolique,
                 frequence_cardiaque, saturation_oxygene, date_mesure)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";

        try {
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                $data['patient_id'],
                $data['user_id'],
                $data['poids'],
                $data['taille'],
                $data['temperature'],
                $data['tension_sys'],
                $data['tension_dia'],
                $data['frequence_cardiaque'],
                $data['saturation_oxygene']
            ]);
        } catch (Exception $e) {
            error_log("Erreur addParametres: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Récupère les dernières constantes
     */
    public function getParametres($patient_id, $limit = 10) {
        $sql = "SELECT * FROM patient_parametres
                WHERE patient_id = :pid
                ORDER BY date_mesure DESC
                LIMIT :lim";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':pid', $patient_id, PDO::PARAM_INT);
            $stmt->bindValue(':lim', (int)$limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur SQL getParametres : " . $e->getMessage());
            return [];
        }
    }

    public function getEvolutionParametre($patient_id, $parametre, $jours = 30) {
        $column_map = [
            'temperature' => 'temperature',
            'tension' => 'pression_arterielle_systolique',
            'frequence' => 'frequence_cardiaque',
            'poids' => 'poids'
        ];

        $column = $column_map[$parametre] ?? 'temperature';

        $sql = "SELECT DATE(date_mesure) as date, AVG($column) as valeur
                FROM patient_parametres
                WHERE patient_id = :patient_id
                  AND date_mesure >= DATE_SUB(NOW(), INTERVAL :jours DAY)
                GROUP BY DATE(date_mesure)
                ORDER BY date";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':patient_id' => $patient_id,
            ':jours' => (int)$jours
        ]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // --- CONSULTATIONS ---

    public function getConsultations($patient_id, $limit = null) {
        $sql = "SELECT c.*, u.nom as medecin_nom, u.prenom as medecin_prenom
                FROM consultations c
                JOIN users u ON c.medecin_id = u.id
                WHERE c.patient_id = :patient_id
                ORDER BY c.date_consultation DESC";
        if ($limit) { $sql .= " LIMIT :limit"; }

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':patient_id', $patient_id, PDO::PARAM_INT);
        if ($limit) { $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT); }

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // --- UTILITAIRES PRIVÉS ---

    private function genererNumeroDossier() {
        $annee = date('Y');
        $sql = "SELECT COUNT(*) as total FROM patients WHERE YEAR(created_at) = :annee";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':annee' => $annee]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        $numero = $result['total'] + 1;
        return 'HSJM-' . $annee . '-' . str_pad($numero, 5, '0', STR_PAD_LEFT);
    }

    // À ajouter dans app/models/Patient.php

/**
 * Compte le nombre total de patients actifs
 */
public function count() {
    $sql = "SELECT COUNT(*) as total FROM patients WHERE actif = 1";
    $stmt = $this->db->query($sql);
    return $stmt->fetch(PDO::FETCH_ASSOC)['total'];
}

/**
 * Compte le nombre de patients actuellement hospitalisés
 */
public function countHospitalises() {
    // On considère qu'un patient est hospitalisé si son statut est 'HOSPITALISE'
    $sql = "SELECT COUNT(*) as total FROM patients WHERE statut = 'HOSPITALISE' AND actif = 1";
    $stmt = $this->db->query($sql);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result['total'] ?? 0;
}
}