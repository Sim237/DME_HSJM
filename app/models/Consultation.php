<?php
require_once __DIR__ . '/../../config/database.php';

class Consultation {
    private $db;

    // Dans dme_hospital\app\models\Consultation.php

    public function __construct() {
        // Anciennement : global $db; $this->db = $db;

        // CORRECTION : Instanciation correcte de la connexion
        $database = new Database();
        $this->db = $database->getConnection();
    }

    public function create($data) {
        try {
            // Nettoyer les chaînes vides en NULL (important pour les champs DATE)
            $data = $this->cleanEmptyStrings($data);

            $sql = "INSERT INTO consultations
                    (patient_id, medecin_id, type_consultation, type, date_consultation, motif_consultation,
                     histoire_maladie, automedication, complement_anamnese,
                     temperature, tension_arterielle, frequence_cardiaque, poids, taille,
                     examen_physique, resume_syndromique,
                     hypotheses_diagnostiques, diagnostic_principal, diagnostics_differentiels,
                     examens_paracliniques, plan_traitement, traitement_non_medicamenteux,
                     surveillance, date_suivi)
                    VALUES
                    (:patient_id, :medecin_id, :type_consultation, :type, :date_consultation, :motif_consultation,
                     :histoire_maladie, :automedication, :complement_anamnese,
                     :temperature, :tension_arterielle, :frequence_cardiaque, :poids, :taille,
                     :examen_physique, :resume_syndromique,
                     :hypotheses_diagnostiques, :diagnostic_principal, :diagnostics_differentiels,
                     :examens_paracliniques, :plan_traitement, :traitement_non_medicamenteux,
                     :surveillance, :date_suivi)";

            $stmt = $this->db->prepare($sql);

            $result = $stmt->execute([
                ':patient_id' => $data['patient_id'] ?? null,
                ':medecin_id' => $data['medecin_id'] ?? 1,
                ':type_consultation' => strtolower(trim($data['type'] ?? 'externe')),
                ':type' => $data['type'] ?? 'GENERALE',
                ':date_consultation' => $data['date_consultation'] ?? date('Y-m-d H:i:s'),
                ':motif_consultation' => $data['motif_consultation'] ?? null,
                ':histoire_maladie' => $data['histoire_maladie'] ?? null,
                ':automedication' => $data['automedication'] ?? null,
                ':complement_anamnese' => $data['complement_anamnese'] ?? null,
                ':temperature' => $data['temperature'] ?? null,
                ':tension_arterielle' => $data['tension_arterielle'] ?? null,
                ':frequence_cardiaque' => $data['frequence_cardiaque'] ?? null,
                ':poids' => $data['poids'] ?? null,
                ':taille' => $data['taille'] ?? null,
                ':examen_physique' => $data['examen_physique'] ?? null,
                ':resume_syndromique' => $data['resume_syndromique'] ?? null,
                ':hypotheses_diagnostiques' => $data['hypotheses_diagnostiques'] ?? null,
                ':diagnostic_principal' => $data['diagnostic_principal'] ?? null,
                ':diagnostics_differentiels' => $data['diagnostics_differentiels'] ?? null,
                ':examens_paracliniques' => $data['examens_paracliniques'] ?? null,
                ':plan_traitement' => $data['plan_traitement'] ?? null,
                ':traitement_non_medicamenteux' => $data['traitement_non_medicamenteux'] ?? null,
                ':surveillance' => $data['surveillance'] ?? null,
                ':date_suivi' => $data['date_suivi'] ?? null
            ]);

            if ($result) {
                return $this->db->lastInsertId();
            }
            $err = $stmt->errorInfo();
            error_log("Erreur création consultation : " . implode(' | ', $err));
            return false;
        } catch (Exception $e) {
            error_log("Exception création consultation: " . $e->getMessage());
            return false;
        }
    }

    public function getById($id) {
        $sql = "SELECT c.*,
                p.nom as patient_nom, p.prenom as patient_prenom, p.dossier_numero,
                u.nom as medecin_nom, u.prenom as medecin_prenom
                FROM consultations c
                JOIN patients p ON c.patient_id = p.id
                JOIN users u ON c.medecin_id = u.id
                WHERE c.id = :id";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getRecent($limit = 10) {
        $sql = "SELECT c.id, c.date_consultation, c.type, c.motif_consultation, c.diagnostic_principal,
                p.nom as patient_nom, p.prenom as patient_prenom, p.dossier_numero,
                u.nom as medecin_nom, u.prenom as medecin_prenom
                FROM consultations c
                JOIN patients p ON c.patient_id = p.id
                JOIN users u ON c.medecin_id = u.id
                ORDER BY c.date_consultation DESC
                LIMIT :limit";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function countToday() {
        $sql = "SELECT COUNT(*) as total FROM consultations
                WHERE DATE(date_consultation) = CURDATE()";
        $stmt = $this->db->query($sql);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'];
    }

    public function getStatsGraph($periode) {
        $days = $periode === '30days' ? 30 : 7;

        $sql = "SELECT DATE(date_consultation) as date, COUNT(*) as total
                FROM consultations
                WHERE date_consultation >= DATE_SUB(NOW(), INTERVAL :days DAY)
                GROUP BY DATE(date_consultation)
                ORDER BY date";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':days' => $days]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function update($id, $data) {
        // Nettoyer les chaînes vides en NULL
        $data = $this->cleanEmptyStrings($data);

        $sql = "UPDATE consultations SET
                type_consultation = :type_consultation,
                type = :type,
                motif_consultation = :motif_consultation,
                histoire_maladie = :histoire_maladie,
                automedication = :automedication,
                complement_anamnese = :complement_anamnese,
                temperature = :temperature,
                tension_arterielle = :tension_arterielle,
                frequence_cardiaque = :frequence_cardiaque,
                poids = :poids,
                taille = :taille,
                examen_physique = :examen_physique,
                resume_syndromique = :resume_syndromique,
                hypotheses_diagnostiques = :hypotheses_diagnostiques,
                diagnostic_principal = :diagnostic_principal,
                diagnostics_differentiels = :diagnostics_differentiels,
                examens_paracliniques = :examens_paracliniques,
                plan_traitement = :plan_traitement,
                traitement_non_medicamenteux = :traitement_non_medicamenteux,
                surveillance = :surveillance,
                date_suivi = :date_suivi
                WHERE id = :id";

        $stmt = $this->db->prepare($sql);

        $success = $stmt->execute([
            ':motif_consultation' => $data['motif_consultation'] ?? null,
            ':histoire_maladie' => $data['histoire_maladie'] ?? null,
            ':automedication' => $data['automedication'] ?? null,
            ':complement_anamnese' => $data['complement_anamnese'] ?? null,
            ':temperature' => $data['temperature'] ?? null,
            ':tension_arterielle' => $data['tension_arterielle'] ?? null,
            ':frequence_cardiaque' => $data['frequence_cardiaque'] ?? null,
            ':poids' => $data['poids'] ?? null,
            ':taille' => $data['taille'] ?? null,
            ':examen_physique' => $data['examen_physique'] ?? null,
            ':resume_syndromique' => $data['resume_syndromique'] ?? null,
            ':hypotheses_diagnostiques' => $data['hypotheses_diagnostiques'] ?? null,
            ':diagnostic_principal' => $data['diagnostic_principal'] ?? null,
            ':diagnostics_differentiels' => $data['diagnostics_differentiels'] ?? null,
            ':examens_paracliniques' => $data['examens_paracliniques'] ?? null,
            ':plan_traitement' => $data['plan_traitement'] ?? null,
            ':traitement_non_medicamenteux' => $data['traitement_non_medicamenteux'] ?? null,
            ':type_consultation' => strtolower(trim($data['type'] ?? 'externe')),
            ':type' => $data['type'] ?? 'GENERALE',
            ':surveillance' => $data['surveillance'] ?? null,
            ':date_suivi' => $data['date_suivi'] ?? null,
            ':id' => $id
        ]);

        if (!$success) {
            $err = $stmt->errorInfo();
            error_log("Erreur mise à jour consultation (ID={$id}) : " . implode(' | ', $err));
            return false;
        }

        return true;
    }

    // app/models/Consultation.php

public function save($data) {
    $db = (new Database())->getConnection();
    $sql = "INSERT INTO consultations (patient_id, medecin_id, motif_consultation, examen_physique, diagnostic_principal, traitement_prescrit, date_consultation)
            VALUES (:pid, :mid, :motif, :examen, :diag, :traitement, NOW())";

    $stmt = $db->prepare($sql);
    $stmt->execute([
        ':pid' => $data['patient_id'],
        ':mid' => $data['medecin_id'],
        ':motif' => $data['motif'],
        ':examen' => $data['examen_physique'],
        ':diag' => $data['diagnostic'],
        ':traitement' => $data['traitement']
    ]);

    return $db->lastInsertId();
}

    /**
     * Nettoie les chaînes vides en NULL pour les champs DATE/optionnels
     */
    private function cleanEmptyStrings($data) {
        $cleaned = [];
        foreach ($data as $key => $value) {
            if ($value === '' || $value === '0000-00-00') {
                $cleaned[$key] = null;
            } else {
                $cleaned[$key] = $value;
            }
        }
        return $cleaned;
    }
}
?>