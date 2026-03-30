<?php
/* ============================================================================
FICHIER : app/controllers/LitController.php
CONTRÔLEUR DE GESTION DES LITS ET SORTIES (BILLET DE SORTIE)
============================================================================ */

require_once __DIR__ . '/UnifiedController.php';
require_once __DIR__ . '/../services/AuditService.php';

class LitController extends UnifiedController {

    public function __construct() {
        parent::__construct();
    }

    public function gestion() {
        $this->auth->requirePermission('hospitalisation', 'read');
        $db = (new Database())->getConnection();

        $sql = "SELECT s.nom_service, c.nom_chambre, l.*, p.nom, p.prenom, p.sexe
                FROM services s
                JOIN chambres c ON s.id = c.service_id
                JOIN lits l ON c.id = l.chambre_id
                LEFT JOIN patients p ON l.patient_id = p.id
                ORDER BY s.nom_service, c.nom_chambre, l.nom_lit";

        $res = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        $plan = [];
        foreach ($res as $row) {
            $plan[$row['nom_service']][$row['nom_chambre']][] = $row;
        }

        $stats = [
            'total' => $db->query("SELECT COUNT(*) FROM lits")->fetchColumn(),
            'occupes' => $db->query("SELECT COUNT(*) FROM lits WHERE statut = 'OCCUPE'")->fetchColumn(),
            'libres' => $db->query("SELECT COUNT(*) FROM lits WHERE statut IN ('DISPONIBLE', 'LIBRE')")->fetchColumn()
        ];

        require_once __DIR__ . '/../views/lits/dashboard.php';
    }

    public function getPatientsAdmissibles() {
        header('Content-Type: application/json');
        $q = $_GET['q'] ?? '';
        $db = (new Database())->getConnection();
        $stmt = $db->prepare("SELECT id, nom, prenom, dossier_numero FROM patients WHERE (nom LIKE :q OR prenom LIKE :q) AND statut != 'HOSPITALISE' LIMIT 10");
        $stmt->execute([':q' => "%$q%"]);
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        exit;
    }

    public function confirmerAdmission() {
        header('Content-Type: application/json');
        $db = (new Database())->getConnection();
        try {
            $db->beginTransaction();
            $db->prepare("UPDATE lits SET statut = 'OCCUPE', patient_id = ? WHERE id = ?")->execute([$_POST['patient_id'], $_POST['lit_id']]);
            $db->prepare("UPDATE patients SET statut = 'HOSPITALISE' WHERE id = ?")->execute([$_POST['patient_id']]);
            $db->prepare("INSERT INTO mouvements_lits (patient_id, lit_id, type_mouvement, user_id) VALUES (?, ?, 'ADMISSION', ?)")
               ->execute([$_POST['patient_id'], $_POST['lit_id'], $_SESSION['user_id']]);
            $db->commit();
            echo json_encode(['success' => true]);
        } catch (Exception $e) { $db->rollBack(); echo json_encode(['success' => false, 'message' => $e->getMessage()]); }
        exit;
    }

    /**
     * ACTION : DÉCHARGER (SORTIE)
     * Libère le lit (le passe en nettoyage) et marque le patient comme sorti
     */
    public function dechargerPatient() {
        header('Content-Type: application/json');
        $db = (new Database())->getConnection();
        $patient_id = $_POST['patient_id'];
        $lit_id = $_POST['lit_id'];

        try {
            $db->beginTransaction();

            // 1. On passe le lit en statut NETTOYAGE (Sécurité hospitalière)
            $db->prepare("UPDATE lits SET statut = 'NETTOYAGE', patient_id = NULL WHERE id = ?")->execute([$lit_id]);

            // 2. On marque le patient comme SORTI
            $db->prepare("UPDATE patients SET statut = 'SORTIE' WHERE id = ?")->execute([$patient_id]);

            // 3. Clôturer l'hospitalisation en cours
            $db->prepare("UPDATE hospitalisations SET statut = 'termine', date_sortie_effective = NOW() WHERE patient_id = ? AND statut = 'en_cours'")
               ->execute([$patient_id]);

            // Récupérer l'id de l'hospitalisation clôturée pour l'envoyer au frontend
            $stmtH = $db->prepare("SELECT id, medecin_responsable FROM hospitalisations WHERE patient_id = ? AND statut = 'termine' ORDER BY date_sortie_effective DESC LIMIT 1");
            $stmtH->execute([$patient_id]);
            $hosp = $stmtH->fetch(PDO::FETCH_ASSOC);

            // 4. Log du mouvement
            $db->prepare("INSERT INTO mouvements_lits (patient_id, lit_id, type_mouvement, user_id) VALUES (?, ?, 'SORTIE', ?)")
               ->execute([$patient_id, $lit_id, $_SESSION['user_id']]);

            $db->commit();
            echo json_encode([
                'success'           => true,
                'hospitalisation_id' => $hosp['id'] ?? null,
                'patient_id'        => $patient_id,
            ]);
        } catch (Exception $e) { $db->rollBack(); echo json_encode(['success' => false, 'message' => $e->getMessage()]); }
        exit;
    }

    /**
     * VUE : BILLET DE SORTIE (Prêt à imprimer)
     */
    public function billetSortie($patient_id) {
        $db = (new Database())->getConnection();

        // Récupérer les infos de l'admission et de la sortie
        $sql = "SELECT p.*, ml.date_mouvement as date_sortie, u.nom as staff_nom
                FROM patients p
                LEFT JOIN mouvements_lits ml ON p.id = ml.patient_id AND ml.type_mouvement = 'SORTIE'
                LEFT JOIN users u ON ml.user_id = u.id
                WHERE p.id = ?
                ORDER BY ml.date_mouvement DESC LIMIT 1";

        $stmt = $db->prepare($sql);
        $stmt->execute([$patient_id]);
        $billet = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$billet) die("Aucun mouvement de sortie trouvé pour ce patient.");

        require_once __DIR__ . '/../views/lits/billet_sortie.php';
    }
}