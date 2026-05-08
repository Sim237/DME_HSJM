<?php
/* ============================================================================
   FICHIER : app/controllers/AccueilPhpController.php
   ACCUEIL PHP — Structure partenaire, circuit dédié
   ============================================================================ */

require_once __DIR__ . '/UnifiedController.php';

class AccueilPhpController extends UnifiedController {

    public function __construct() {
        parent::__construct();
    }

    public function index() {
        $db = (new Database())->getConnection();

        $rdvs = $db->query("
            SELECT p.nom, p.prenom, p.dossier_numero, r.id, r.date_debut as date_rdv, r.titre as motif
            FROM agenda_medical r
            JOIN patients p ON r.patient_id = p.id
            WHERE DATE(r.date_debut) = CURDATE()
              AND p.statut_parcours = 'ACCUEIL'
              AND p.circuit = 'PHP'
            ORDER BY r.date_debut ASC
        ")->fetchAll(PDO::FETCH_ASSOC);

        require_once __DIR__ . '/../views/accueil_php/dashboard.php';
    }

    public function enregistrerPatient() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . 'accueil-php');
            exit;
        }

        $db = (new Database())->getConnection();

        try {
            $db->beginTransaction();

            // Génération numéro dossier PHP
            $annee2 = date('y'); // 2 chiffres : 26
            $prefix = "HSJM$annee2";
            $stmtCount = $db->prepare("SELECT COUNT(*) as total FROM patients WHERE dossier_numero LIKE ?");
            $stmtCount->execute(["$prefix%"]);
            $result = $stmtCount->fetch(PDO::FETCH_ASSOC);
            $next_id = $result['total'] + 1;
            $dossier_numero = $prefix . str_pad($next_id, 4, '0', STR_PAD_LEFT);

            // Validation type_client
            $allowed_types = ['FAMILLE_PHP', 'AGENTS_PHP'];
            $type_client = in_array($_POST['type_client'] ?? '', $allowed_types)
                           ? $_POST['type_client'] : 'FAMILLE_PHP';

            // Insertion patient circuit PHP
            $stmt = $db->prepare("
                INSERT INTO patients (
                    dossier_numero, nom, prenom, date_naissance, sexe,
                    telephone, adresse, profession, situation_matrimoniale,
                    groupe_sanguin, contact_nom, contact_telephone,
                    type_client, circuit, statut_parcours, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'PHP', 'ACCUEIL', NOW())
            ");
            $stmt->execute([
                $dossier_numero,
                strtoupper(trim($_POST['nom'])),
                trim($_POST['prenom']),
                $_POST['date_naissance'],
                $_POST['sexe'],
                $_POST['telephone'] ?? '',
                $_POST['adresse'] ?? '',
                $_POST['profession'] ?? '',
                $_POST['situation_matrimoniale'] ?? 'celibataire',
                $_POST['groupe_sanguin'] ?? '',
                $_POST['contact_nom'] ?? '',
                $_POST['contact_telephone'] ?? '',
                $type_client,
            ]);

            $patient_id = $db->lastInsertId();

            // Génération numéro d'ordre (même séquence globale)
            $db->query("UPDATE config_sequence SET last_number = last_number + 1, last_date = CURDATE() WHERE id = 1");
            $resSeq = $db->query("SELECT last_number FROM config_sequence WHERE id = 1")->fetch(PDO::FETCH_ASSOC);
            $num_ordre = $resSeq['last_number'];

            $db->prepare("UPDATE patients SET numero_ordre = ?, statut_parcours = 'PARAMETRES', date_mise_en_parametres = NOW() WHERE id = ?")
               ->execute([$num_ordre, $patient_id]);

            $db->commit();

            header('Location: ' . BASE_URL . 'accueil-php?success=1&ticket=' . $num_ordre . '&dossier=' . $dossier_numero);
            exit;

        } catch (Exception $e) {
            $db->rollBack();
            error_log("Erreur AccueilPhpController : " . $e->getMessage());
            header('Location: ' . BASE_URL . 'accueil-php?error=1');
            exit;
        }
    }

    public function commencerVisite($id) {
        $db = (new Database())->getConnection();

        $db->query("UPDATE config_sequence SET last_number = last_number + 1, last_date = CURDATE() WHERE id = 1");
        $res = $db->query("SELECT last_number FROM config_sequence WHERE id = 1")->fetch();
        $num = $res['last_number'];

        $db->prepare("UPDATE patients SET statut_parcours = 'PARAMETRES', numero_ordre = ?, date_mise_en_parametres = NOW() WHERE id = ? AND circuit = 'PHP'")
           ->execute([$num, $id]);

        header('Location: ' . BASE_URL . 'accueil-php?success=visite');
        exit;
    }
}
