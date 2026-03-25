<?php
/* ============================================================================
   FICHIER : app/controllers/BilanController.php
   CONTRÔLEUR UNIQUE POUR LES DEMANDES DE LABORATOIRE ET RADIOLOGIE
   ============================================================================ */

class BilanController {

    public function save() {
        // 1. Forcer la réponse en JSON pour le JavaScript
        header('Content-Type: application/json');

        // 2. Gestion de la session
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // 3. Connexion à la base de données (Chemin absolu sécurisé)
        $dbPath = $_SERVER['DOCUMENT_ROOT'] . '/dme_hospital/config/database.php';
        if (!file_exists($dbPath)) {
            echo json_encode(['success' => false, 'message' => 'Fichier de configuration introuvable.']);
            exit;
        }
        require_once $dbPath;
        $db = (new Database())->getConnection();

        try {
            // 4. Récupération des données communes
            $patient_id = $_POST['patient_id'] ?? null;
            $type_bilan = $_POST['type_bilan'] ?? null; // 'laboratoire' ou 'imagerie'
            $medecin_id = $_SESSION['user_id'] ?? 1; // Fallback admin si session perdue
            $urgence = $_POST['urgence'] ?? 'NORMAL';
            $observations = htmlspecialchars($_POST['observations'] ?? '');

            if (!$patient_id) {
                throw new Exception("L'identifiant du patient est manquant.");
            }

            // ============================================================
            // BRANCHE A : LABORATOIRE
            // ============================================================
            if ($type_bilan === 'laboratoire') {
                $examen_id = $_POST['examen_id'] ?? null;
                if (!$examen_id) throw new Exception("Veuillez sélectionner un examen.");

                $db->beginTransaction();

                // 1. Insertion dans la table parente (demandes_laboratoire)
                $sqlLab = "INSERT INTO demandes_laboratoire
                           (patient_id, medecin_id, examen_id, urgence, observations, statut, date_creation)
                           VALUES (?, ?, ?, ?, ?, 'EN_ATTENTE', NOW())";
                $stmtLab = $db->prepare($sqlLab);
                $stmtLab->execute([$patient_id, $medecin_id, $examen_id, $urgence, $observations]);

                $demande_id = $db->lastInsertId();

                // 2. Insertion dans la table de détail (demande_examens) pour le technicien labo
                $sqlDet = "INSERT INTO demande_examens (demande_id, examen_id, urgent, statut) VALUES (?, ?, ?, 'EN_ATTENTE')";
                $isUrgent = ($urgence === 'URGENT') ? 1 : 0;
                $stmtDet = $db->prepare($sqlDet);
                $stmtDet->execute([$demande_id, $examen_id, $isUrgent]);

                $db->commit();
                echo json_encode(['success' => true, 'message' => 'Demande de laboratoire transmise.']);

            // ============================================================
            // BRANCHE B : RADIOLOGIE (IMAGERIE)
            // ============================================================
            } elseif ($type_bilan === 'imagerie') {
                $type_imagerie = $_POST['type_imagerie'] ?? null;
                $partie_code = $_POST['partie_code'] ?? null;

                if (!$type_imagerie || !$partie_code) {
                    throw new Exception("Type d'imagerie ou zone manquante.");
                }

                $sqlImg = "INSERT INTO demandes_imagerie
                           (patient_id, medecin_id, type_imagerie, partie_code, urgence, observations, statut, date_creation)
                           VALUES (?, ?, ?, ?, ?, ?, 'EN_ATTENTE', NOW())";

                $stmtImg = $db->prepare($sqlImg);
                $stmtImg->execute([
                    $patient_id,
                    $medecin_id,
                    $type_imagerie,
                    $partie_code,
                    $urgence,
                    $observations
                ]);

                echo json_encode(['success' => true, 'message' => 'Demande de radiologie transmise.']);

            } else {
                throw new Exception("Type de bilan non reconnu.");
            }

        } catch (Exception $e) {
            // En cas d'erreur, annuler les transactions si elles ont commencé
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }
}