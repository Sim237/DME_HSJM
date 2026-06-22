<?php
/**
 * SimCare+ — Dossier Médical Électronique (DME)
 * Copyright (c) 2024-2026 Franck Simeni. Tous droits réservés.
 * Développé pour la gestion hospitalière, et le bien être numérique des patients.
 *
 * Toute reproduction, modification ou distribution de ce logiciel,
 * en tout ou en partie, sans autorisation écrite préalable de l'auteur
 * est strictement interdite et constitue une contrefaçon.
 *
 * Protected under OAPI Agreement — Annexe VII · Berne Convention
 */

/* ============================================================================
   FICHIER : app/models/Prescription.php
   Modèle pour la gestion des ordonnances (Table ordonnances_pharmacie)
   ============================================================================ */
require_once __DIR__ . '/../../config/database.php';

class Prescription {
    private $db;
    private $table = 'ordonnances_pharmacie';

    /** Nom réel de la colonne "notes/recommandations" — détecté à l'init */
    private $notesCol = 'notes';
    /** TRUE si la colonne hors_stock existe dans ordonnance_medicaments */
    private $hasHorsStock = false;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->migratePrescriptionTable();
    }

    /** Migration silencieuse : ajoute les colonnes manquantes + détecte les noms réels */
    private function migratePrescriptionTable() {
        try {
            // ── ordonnances_pharmacie ──
            $cols = $this->db->query("SHOW COLUMNS FROM `ordonnances_pharmacie`")->fetchAll(PDO::FETCH_COLUMN);
            $cols = array_flip($cols); // accès O(1)

            // Détecter le vrai nom de la colonne notes/recommandations
            if (isset($cols['notes']))           $this->notesCol = 'notes';
            elseif (isset($cols['recommandations'])) $this->notesCol = 'recommandations';
            elseif (isset($cols['note']))        $this->notesCol = 'note';
            else {
                // Aucune — on la crée
                $this->db->exec("ALTER TABLE `ordonnances_pharmacie` ADD COLUMN `notes` TEXT NULL");
                $this->notesCol = 'notes';
            }

            if (!isset($cols['type_ordonnance'])) {
                $this->db->exec("ALTER TABLE `ordonnances_pharmacie` ADD COLUMN `type_ordonnance` VARCHAR(20) NOT NULL DEFAULT 'NORMALE' AFTER `statut`");
            }
            if (!isset($cols['infirmier_prescripteur_id'])) {
                $this->db->exec("ALTER TABLE `ordonnances_pharmacie` ADD COLUMN `infirmier_prescripteur_id` INT NULL AFTER `type_ordonnance`");
            }

            // ── ordonnance_medicaments ──
            $colsMed = $this->db->query("SHOW COLUMNS FROM `ordonnance_medicaments`")->fetchAll(PDO::FETCH_COLUMN);
            $colsMed = array_flip($colsMed);

            if (!isset($colsMed['hors_stock'])) {
                $this->db->exec("ALTER TABLE `ordonnance_medicaments` ADD COLUMN `hors_stock` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1 = médicament hors stock pharmacie (saisie libre)'");
                $this->hasHorsStock = true;
            } else {
                $this->hasHorsStock = true;
            }

        } catch (Exception $e) {
            error_log("Migration Prescription: " . $e->getMessage());
        }
    }

    /**
     * CRÉATION : Enregistre l'ordonnance et ses médicaments
     */
    public function create($data) {
        try {
            $this->db->beginTransaction();

            // ── 1. En-tête ordonnance ──
            $sql = "INSERT INTO `{$this->table}`
                        (patient_id, medecin_id, consultation_id, date_creation, statut,
                         `{$this->notesCol}`, type_ordonnance, infirmier_prescripteur_id)
                    VALUES
                        (:patient_id, :medecin_id, :consultation_id, NOW(), :statut,
                         :notes, :type_ordonnance, :infirmier_prescripteur_id)";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':patient_id'                => $data['patient_id'],
                ':medecin_id'                => $data['medecin_id'],
                ':consultation_id'           => $data['consultation_id'] ?? null,
                ':statut'                    => $data['statut'] ?? 'EN_ATTENTE',
                ':notes'                     => $data['recommandations'] ?? ($data['notes'] ?? null),
                ':type_ordonnance'           => $data['type_ordonnance'] ?? 'NORMALE',
                ':infirmier_prescripteur_id' => $data['infirmier_prescripteur_id'] ?? null,
            ]);

            $ordonnance_id = $this->db->lastInsertId();

            // ── 2. Médicaments ──
            if (!empty($data['medicaments'])) {
                if ($this->hasHorsStock) {
                    $sqlMed = "INSERT INTO ordonnance_medicaments
                                   (ordonnance_id, medicament_id, nom_medicament, posologie, duree, quantite, hors_stock)
                               VALUES (:oid, :mid, :nom, :poso, :duree, :qte, :hs)";
                } else {
                    $sqlMed = "INSERT INTO ordonnance_medicaments
                                   (ordonnance_id, medicament_id, nom_medicament, posologie, duree, quantite)
                               VALUES (:oid, :mid, :nom, :poso, :duree, :qte)";
                }
                $stmtMed = $this->db->prepare($sqlMed);

                foreach ($data['medicaments'] as $med) {
                    $horsStock = !empty($med['hors_stock']) ? 1 : 0;
                    $medId     = $horsStock ? null : (isset($med['id']) && $med['id'] ? (int)$med['id'] : null);
                    $params = [
                        ':oid'  => $ordonnance_id,
                        ':mid'  => $medId,
                        ':nom'  => $med['nom'] ?? 'Médicament',
                        ':poso' => $med['posologie'] ?? '',
                        ':duree'=> $med['duree'] ?? '',
                        ':qte'  => (int)($med['quantite'] ?? 1),
                    ];
                    if ($this->hasHorsStock) $params[':hs'] = $horsStock;
                    $stmtMed->execute($params);
                }
            }

            $this->db->commit();
            return $ordonnance_id;

        } catch (Exception $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
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
                FROM `{$this->table}` o
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
                u.nom as medecin_nom, u.prenom as medecin_prenom, u.specialite, u.telephone as medecin_tel,
                inf.nom as infirmier_nom, inf.prenom as infirmier_prenom, inf.role as infirmier_role
                FROM `{$this->table}` o
                JOIN patients pat ON o.patient_id = pat.id
                JOIN users u ON o.medecin_id = u.id
                LEFT JOIN users inf ON o.infirmier_prescripteur_id = inf.id
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
                FROM `{$this->table}` o
                JOIN users u ON o.medecin_id = u.id
                WHERE o.patient_id = :pid
                ORDER BY o.date_creation DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':pid' => $patient_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * MISE À JOUR : Modifie une ordonnance existante (remet en EN_ATTENTE si signée)
     */
    public function update($id, $data) {
        try {
            $this->db->beginTransaction();

            // 1. Mettre à jour l'en-tête
            $this->db->prepare("
                UPDATE `{$this->table}`
                SET `{$this->notesCol}` = ?,
                    statut = CASE WHEN statut = 'TERMINEE' THEN statut ELSE 'EN_ATTENTE' END,
                    signature_hash = NULL,
                    signed_at = NULL,
                    signe_par = NULL,
                    date_creation = date_creation
                WHERE id = ?
            ")->execute([$data['recommandations'] ?? ($data['notes'] ?? ''), $id]);

            // 2. Supprimer toutes les anciennes lignes
            $this->db->prepare("DELETE FROM ordonnance_medicaments WHERE ordonnance_id = ?")->execute([$id]);

            // 3. Réinsérer les nouvelles lignes
            if ($this->hasHorsStock) {
                $sqlMed = "INSERT INTO ordonnance_medicaments
                               (ordonnance_id, medicament_id, nom_medicament, posologie, duree, quantite, hors_stock)
                           VALUES (?, ?, ?, ?, ?, ?, ?)";
            } else {
                $sqlMed = "INSERT INTO ordonnance_medicaments
                               (ordonnance_id, medicament_id, nom_medicament, posologie, duree, quantite)
                           VALUES (?, ?, ?, ?, ?, ?)";
            }
            $stmt = $this->db->prepare($sqlMed);

            foreach ($data['medicaments'] as $med) {
                $horsStock = !empty($med['hors_stock']) ? 1 : 0;
                $medId     = $horsStock ? null : (isset($med['id']) && $med['id'] ? (int)$med['id'] : null);
                $row = [
                    $id,
                    $medId,
                    $med['nom']       ?? 'Médicament',
                    $med['posologie'] ?? '',
                    $med['duree']     ?? '',
                    (int)($med['quantite'] ?? 1),
                ];
                if ($this->hasHorsStock) $row[] = $horsStock;
                $stmt->execute($row);
            }

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            error_log("Erreur Prescription::update : " . $e->getMessage());
            return false;
        }
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
                'adresse'     => 'BP 56 Njombé, Cameroun',
                'telephone'   => '+237 697 09 29 92',
            ];
        }
    }
}
