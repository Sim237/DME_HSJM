<?php
/* ============================================================================
FICHIER : app/controllers/AccueilController.php
CONTRÔLEUR DU MODULE ACCUEIL (RECEPTION)
============================================================================ */

// IMPORTANT : Charger le contrôleur parent
require_once __DIR__ . '/UnifiedController.php';

class AccueilController extends UnifiedController {

    public function __construct() {
        parent::__construct();
    }

    /**
     * Dashboard de l'accueil
     */
   public function index() {
    $db = (new Database())->getConnection();

    // On récupère les RDV de la table patient_rdv pour la date du jour
    $sql = "SELECT p.nom, p.prenom, p.dossier_numero, r.id, r.date_rdv as heure_rdv, r.motif
            FROM patient_rdv r
            JOIN patients p ON r.patient_id = p.id
            WHERE DATE(r.date_rdv) = CURDATE()
            AND p.statut_parcours = 'ACCUEIL'
            ORDER BY r.date_rdv ASC";

    $stmt = $db->query($sql);
    $rdvs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    require_once __DIR__ . '/../views/accueil/dashboard.php';
}
    /**
     * Enregistre un patient et démarre sa visite
     */
    public function savePatient() {
        // Cette méthode devrait normalement appeler PatientModel pour le store
        // Mais pour suivre votre logique de redirection immédiate :

        $db = (new Database())->getConnection();

        // Simulation de la récupération de l'ID après insertion (à adapter selon votre formulaire)
        $patient_id = $_POST['patient_id'] ?? null;

        if ($patient_id) {
            $this->commencerVisite($patient_id);
        } else {
            header('Location: ' . BASE_URL . 'accueil?error=missing_id');
        }
    }

    /**
     * Logique de début de visite avec génération du numéro d'ordre (Pair/Impair)
     */
    public function enregistrerPatient() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $db = (new Database())->getConnection();

        try {
            $db->beginTransaction();

            // --- 1. GÉNÉRATION DU NUMÉRO DE DOSSIER UNIQUE ---
            $annee = date('Y');
            // On compte combien de patients ont été créés cette année
            $stmtCount = $db->prepare("SELECT COUNT(*) as total FROM patients WHERE dossier_numero LIKE ?");
            $stmtCount->execute(["P-$annee-%"]);
            $result = $stmtCount->fetch(PDO::FETCH_ASSOC);
            $next_id = $result['total'] + 1;
            // Formatage final : P-2026-00001
            $dossier_numero = "P-" . $annee . "-" . str_pad($next_id, 5, '0', STR_PAD_LEFT);

            // --- 2. INSERTION DU PATIENT ---
            $sqlP = "INSERT INTO patients (
                        dossier_numero, nom, prenom, date_naissance, sexe,
                        telephone, adresse, profession, situation_matrimoniale,
                        groupe_sanguin, contact_nom, contact_telephone,
                        statut_parcours, created_at
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'ACCUEIL', NOW())";

            $stmtP = $db->prepare($sqlP);
            $stmtP->execute([
                $dossier_numero,
                strtoupper(trim($_POST['nom'])),
                trim($_POST['prenom']),
                $_POST['date_naissance'],
                $_POST['sexe'],
                $_POST['telephone'],
                $_POST['adresse'],
                $_POST['profession'],
                $_POST['situation_matrimoniale'] ?? 'celibataire',
                $_POST['groupe_sanguin'],
                $_POST['contact_nom'],
                $_POST['contact_telephone']
            ]);

            $patient_id = $db->lastInsertId();

            // --- 3. GÉNÉRATION DU NUMÉRO D'ORDRE (TICKET) ---
            // On incrémente le compteur journalier pour les bureaux de paramètres
            $db->query("UPDATE config_sequence SET last_number = last_number + 1, last_date = CURDATE() WHERE id = 1");
            $resSeq = $db->query("SELECT last_number FROM config_sequence WHERE id = 1")->fetch(PDO::FETCH_ASSOC);
            $num_ordre = $resSeq['last_number'];

            // Mise à jour du patient avec son numéro d'ordre et envoi aux PARAMETRES
            $stmtUpdate = $db->prepare("UPDATE patients SET numero_ordre = ?, statut_parcours = 'PARAMETRES' WHERE id = ?");
            $stmtUpdate->execute([$num_ordre, $patient_id]);

            $db->commit();

            // Redirection vers le dashboard avec le numéro de ticket en succès
            header('Location: ' . BASE_URL . 'accueil?success=patient_cree&ticket=' . $num_ordre . '&dossier=' . $dossier_numero);
            exit;

        } catch (Exception $e) {
            $db->rollBack();
            error_log("Erreur Accueil : " . $e->getMessage());
            die("Erreur d'enregistrement : " . $e->getMessage());
        }
    }
}

    public function commencerVisite($id) {
        $db = (new Database())->getConnection();
        $this->executeVisite($id, $db);
        header('Location: ' . BASE_URL . 'accueil?success=visite_demarree');
        exit;
    }

    private function executeVisite($patient_id, $db) {
        // Incrémente le compteur journalier
        $db->query("UPDATE config_sequence SET last_number = last_number + 1, last_date = CURDATE() WHERE id = 1");
        $res = $db->query("SELECT last_number FROM config_sequence WHERE id = 1")->fetch();
        $num = $res['last_number'];

        // Envoie aux paramètres
        $stmt = $db->prepare("UPDATE patients SET statut_parcours = 'PARAMETRES', numero_ordre = ? WHERE id = ?");
        $stmt->execute([$num, $patient_id]);
    }
}