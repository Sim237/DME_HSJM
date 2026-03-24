<?php

class BilanController {

    public function save() {
        // On définit l'en-tête JSON
        header('Content-Type: application/json');

        // Démarrage session
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        try {
            // =========================================================
            // LA CORRECTION EST ICI : On utilise votre classe Database existante
            // =========================================================

            // 1. On inclut le fichier de base de données qui fonctionne ailleurs
            // Le chemin remonte de 'app/controllers' vers 'config'
            $dbPath = __DIR__ . '/../../config/database.php';

            if (!file_exists($dbPath)) {
                throw new Exception("Le fichier config/database.php est introuvable sur le serveur.");
            }
            require_once $dbPath;

            // 2. On instancie la classe Database (comme dans dossier.php)
            if (class_exists('Database')) {
                $database = new Database();
                $pdo = $database->getConnection();
            } else {
                // Si pas de classe Database, on tente de récupérer la variable globale
                global $pdo;
                if (!$pdo) {
                    throw new Exception("Impossible de se connecter : Classe Database introuvable.");
                }
            }

            // =========================================================
            // FIN DE LA CONNEXION - LE RESTE EST STANDARD
            // =========================================================

            $patient_id = $_POST['patient_id'] ?? null;
            $type_bilan = $_POST['type_bilan'] ?? null;
            $user_id = $_SESSION['user_id'] ?? 1;

            if (!$patient_id) {
                echo json_encode(['success' => false, 'message' => 'Erreur : ID Patient manquant']);
                return;
            }

            if ($type_bilan === 'laboratoire') {
                $stmt = $pdo->prepare("INSERT INTO demandes_laboratoire (patient_id, medecin_id, examen_id, urgence, observations, statut, date_creation) VALUES (?, ?, ?, ?, ?, 'EN_ATTENTE', NOW())");
$stmt->execute([$patient_id, $user_id, $_POST['examen_id'], $_POST['urgence'], $_POST['observations']]);

$demande_id = $pdo->lastInsertId(); // Récupère l'ID de la demande juste créee

// AJOUTER CECI pour que le labo voit l'examen :
$stmtExamen = $pdo->prepare("INSERT INTO demande_examens (demande_id, examen_id, urgent, statut) VALUES (?, ?, ?, 'EN_ATTENTE')");
$isUrgent = ($_POST['urgence'] === 'URGENT' || $_POST['urgence'] === 'TRES_URGENT') ? 1 : 0;
$stmtExamen->execute([$demande_id, $_POST['examen_id'], $isUrgent]);

                echo json_encode(['success' => true, 'message' => 'Demande de laboratoire envoyée !']);

            } elseif ($type_bilan === 'imagerie') {
                $stmt = $pdo->prepare("
                    INSERT INTO demandes_imagerie
                    (patient_id, medecin_id, type_imagerie, partie_code, urgence, observations, statut, date_creation)
                    VALUES (?, ?, ?, ?, ?, ?, 'EN_ATTENTE', NOW())
                ");

                $stmt->execute([
                    $patient_id,
                    $user_id,
                    $_POST['type_imagerie'] ?? null,
                    $_POST['partie_code'] ?? null,
                    $_POST['urgence'] ?? 'NORMAL',
                    $_POST['observations'] ?? null
                ]);

                echo json_encode(['success' => true, 'message' => 'Demande d\'imagerie envoyée !']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Type de bilan inconnu']);
            }

        } catch (Exception $e) {
            // AFFICHER LA VRAIE ERREUR CETTE FOIS POUR DEBUGGER
            // Si ça plante encore, le message sera explicite (ex: "Table not found" ou "Access denied")
            error_log('Erreur Controller: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Erreur Technique : ' . $e->getMessage()]);
        }
    }
}
?>