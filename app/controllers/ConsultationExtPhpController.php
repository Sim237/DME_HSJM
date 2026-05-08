<?php
/* ============================================================================
   FICHIER : app/controllers/ConsultationExtPhpController.php
   CONSULTATION EXTERNE PHP — Médecin du circuit PHP
   ============================================================================ */

require_once __DIR__ . '/UnifiedController.php';

class ConsultationExtPhpController extends UnifiedController {

    public function __construct() {
        parent::__construct();
    }

    /**
     * Liste des patients PHP en attente de consultation
     */
    public function index() {
        $db = (new Database())->getConnection();
        $medecin_id = $_SESSION['user_id'];

        // Patients PHP assignés à ce médecin en attente
        $stmt = $db->prepare("
            SELECT p.*,
                   pp.temperature, pp.pression_arterielle_systolique, pp.pression_arterielle_diastolique,
                   pp.frequence_cardiaque, pp.poids, pp.taille, pp.saturation_oxygene,
                   pp.motif_consultation, pp.date_mesure,
                   s.nom_service
            FROM patients p
            LEFT JOIN patient_parametres pp ON pp.patient_id = p.id
                AND pp.id = (SELECT MAX(id) FROM patient_parametres WHERE patient_id = p.id)
            LEFT JOIN services s ON p.service_id = s.id
            WHERE p.circuit = 'PHP'
              AND p.statut_parcours = 'ATTENTE_CONSULTATION'
              AND p.medecin_id = ?
            ORDER BY pp.date_mesure ASC
        ");
        $stmt->execute([$medecin_id]);
        $patients = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Consultations PHP du jour (terminées)
        $stmt_done = $db->prepare("
            SELECT p.nom, p.prenom, p.dossier_numero, p.type_client, c.created_at, c.diagnostic_principal
            FROM consultations c
            JOIN patients p ON c.patient_id = p.id
            WHERE p.circuit = 'PHP'
              AND c.medecin_id = ?
              AND DATE(c.created_at) = CURDATE()
            ORDER BY c.created_at DESC
            LIMIT 20
        ");
        $stmt_done->execute([$medecin_id]);
        $consultations_jour = $stmt_done->fetchAll(PDO::FETCH_ASSOC);

        require_once __DIR__ . '/../views/consultation_php/dashboard.php';
    }
}
