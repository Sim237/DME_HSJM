<?php
/* ============================================================================
FICHIER : app/controllers/ParametresController.php
CONTRÔLEUR DES BUREAUX DE PARAMÈTRES (VITAL SIGNS)
============================================================================ */

require_once __DIR__ . '/UnifiedController.php';

class ParametresController extends UnifiedController {

    public function __construct() {
        parent::__construct();
    }

    /**
     * Dashboard de prise des paramètres avec filtrage Pair/Impair
     */
    public function index() {
        // 1. Vérification des permissions
        $this->auth->requirePermission('parametres', 'read');

        $db = (new Database())->getConnection();
        $userId = $_SESSION['user_id'];

        // 2. Détection du bureau (Défini lors de la sélection du service à la connexion)
        $bureauId = $_SESSION['bureau_id'] ?? 1;

        // Logique de répartition : Bureau 1 = Pairs, Bureau 2 = Impairs
        $filtreLogic = ($bureauId == 1) ? " (numero_ordre % 2 = 0) " : " (numero_ordre % 2 <> 0) ";

        // 3. Récupération des patients en attente de paramètres
        $sqlAttente = "SELECT * FROM patients
                       WHERE statut_parcours = 'PARAMETRES'
                       AND $filtreLogic
                       ORDER BY numero_ordre ASC";

        $stmtA = $db->prepare($sqlAttente);
        $stmtA->execute();
        $patients_attente = $stmtA->fetchAll(PDO::FETCH_ASSOC);

        // 4. Récupération des patients déjà traités aujourd'hui par cet infirmier
        $sqlReçus = "SELECT p.*, pv.date_mesure
                     FROM patients p
                     JOIN patient_parametres pv ON p.id = pv.patient_id
                     WHERE pv.user_id = ?
                     AND DATE(pv.date_mesure) = CURDATE()
                     ORDER BY pv.date_mesure DESC";

        $stmtR = $db->prepare($sqlReçus);
        $stmtR->execute([$userId]);
        $patients_reçus = $stmtR->fetchAll(PDO::FETCH_ASSOC);

        // Données pour la vue
        $bureauLabel = ($bureauId == 1) ? "BUREAU 1 (Numéros Pairs)" : "BUREAU 2 (Numéros Impairs)";
        $bureauTheme = ($bureauId == 1) ? "primary" : "info";

        require_once __DIR__ . '/../views/parametres/dashboard.php';
    }

    public function save() {
    $db = (new Database())->getConnection();

    try {
        $db->beginTransaction();

        // 1. Récupération sécurisée des données (évite les Warning Undefined Key)
        $patient_id = $_POST['patient_id'];
        $temp   = $_POST['temp'] ?? null;
        $sys    = $_POST['sys'] ?? null;
        $dia    = $_POST['dia'] ?? null;
        $pouls  = $_POST['pouls'] ?? null;
        $spo2   = $_POST['spo2'] ?? null;
        $poids  = $_POST['poids'] ?? null;
        $taille = $_POST['taille'] ?? null; // Maintenant défini grâce au HTML ajouté
        $motif  = $_POST['motif'] ?? '';
        $service_id = $_POST['service_id'];
        $medecin_id = $_POST['medecin_id'];

        // 2. Enregistrement des paramètres vitaux dans l'historique
        $sqlV = "INSERT INTO patient_parametres (
                    patient_id, user_id, temperature,
                    pression_arterielle_systolique, pression_arterielle_diastolique,
                    frequence_cardiaque, poids, taille, saturation_oxygene,
                    motif_consultation, date_mesure
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";

        $stmtV = $db->prepare($sqlV);
        $stmtV->execute([
            $patient_id, $_SESSION['user_id'], $temp,
            $sys, $dia, $pouls, $poids, $taille, $spo2, $motif
        ]);

        // 3. Mise à jour de la fiche patient (Statut, Service et Médecin affecté)
        // C'est ici que 'medecin_id' posait problème s'il n'existait pas en SQL
        $sqlP = "UPDATE patients SET
                    statut_parcours = 'ATTENTE_CONSULTATION',
                    service_id = ?,
                    medecin_id = ?
                 WHERE id = ?";

        $stmtP = $db->prepare($sqlP);
        $stmtP->execute([$service_id, $medecin_id, $patient_id]);

        $db->commit();

        // Redirection avec succès
        header('Location: ' . BASE_URL . 'parametres?success=1');
        exit;

    } catch (Exception $e) {
        $db->rollBack();
        error_log("Erreur ParametresController::save : " . $e->getMessage());
        die("Erreur technique lors de la sauvegarde : " . $e->getMessage());
    }
}
}