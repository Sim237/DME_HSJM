<?php
class SurgeryController extends UnifiedController {

    // Etape 1 : Choix du type d'anesthésie (Après authentification réussie)
    public function selectionAnesthesie($patient_id) {
        $patient = $this->getPatientWithAllData($patient_id);
        require_once __DIR__ . '/../views/chirurgie/choix_anesthesie.php';
    }

    // Etape 2 : Affichage du formulaire spécifique
    public function formulaireAnesthesie($patient_id, $type) {
        $patient = $this->getPatientWithAllData($patient_id);
        $view = ($type == 'generale') ? 'anesthesie_generale.php' : 'anesthesie_rachidienne.php';
        require_once __DIR__ . '/../views/chirurgie/' . $view;
    }

    // Etape 3 : Sauvegarde et Transmission au Bloc
    public function terminerDemande() {
        $db = (new Database())->getConnection();

        try {
            $db->beginTransaction();

            // 1. Créer la demande de bloc
            $stmt = $db->prepare("INSERT INTO bloc_demandes (patient_id, chirurgien_id, anesthesiste_id, type_anesthesie, statut) VALUES (?, ?, ?, ?, 'EN_ATTENTE')");
            $stmt->execute([
                $_POST['patient_id'],
                $_SESSION['user_id'],
                $_POST['anesth_id'], // ID de l'anesthésiste qui s'est loggé en secondaire
                $_POST['type_anesth']
            ]);
            $demande_id = $db->lastInsertId();

            // 2. Enregistrer les détails techniques de l'anesthésie
            $details = json_encode($_POST['tech']);
            $stmtAn = $db->prepare("INSERT INTO bloc_anesthesie (demande_id, type_formulaire, asa_score, mallampati, donnees_techniques) VALUES (?, ?, ?, ?, ?)");
            $stmtAn->execute([
                $demande_id,
                strtoupper($_POST['type_anesth']),
                $_POST['asa'],
                $_POST['mallampati'],
                $details
            ]);

            $db->commit();
            header('Location: ' . BASE_URL . 'patients/dossier/' . $_POST['patient_id'] . '?success=transmis_au_bloc');
        } catch (Exception $e) {
            $db->rollBack();
            die("Erreur de transmission : " . $e->getMessage());
        }
    }
}