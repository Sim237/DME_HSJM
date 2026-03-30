<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../models/Patient.php';
require_once __DIR__ . '/../services/SignatureService.php';

class FormulaireController {

    /**
     * Affiche un formulaire générique (bulletin, certificat, etc.)
     */
    public function creer($type, $patient_id) {
        $patientModel = new Patient();
        $patient = $patientModel->getById($patient_id);

        $age = date_diff(date_create($patient['date_naissance']), date_create('now'))->y;

        $viewPath = __DIR__ . '/../views/formulaires/' . $type . '.php';
        if (file_exists($viewPath)) {
            require_once $viewPath;
        } else {
            echo "Interface non trouvée.";
        }
    }

    /**
     * Ouvre le formulaire CRH pré-rempli à partir de l'ID d'hospitalisation
     */
    public function crh($hosp_id) {
        $db = (new Database())->getConnection();

        // Récupérer l'hospitalisation et le patient
        $stmt = $db->prepare("
            SELECT h.*, p.*, p.id as patient_id,
                   h.id as hosp_id,
                   c.nom_chambre, l.nom_lit
            FROM hospitalisations h
            JOIN patients p ON h.patient_id = p.id
            LEFT JOIN lits l ON h.lit_id = l.id
            LEFT JOIN chambres c ON l.chambre_id = c.id
            WHERE h.id = ?
        ");
        $stmt->execute([$hosp_id]);
        $hosp = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$hosp) {
            die("Hospitalisation introuvable.");
        }

        $patient = $hosp;
        $patient['id'] = $hosp['patient_id'];

        $age = date_diff(date_create($patient['date_naissance']), date_create('now'))->y;

        // Signature du médecin
        $sigService = new SignatureService();
        $signature  = $sigService->getSignature($_SESSION['user_id']);

        require_once __DIR__ . '/../views/formulaires/compte-rendu-hosp.php';
    }

    /**
     * Sauvegarde le CRH (et applique la signature si fournie)
     */
    public function sauvegarderCRH() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . 'dashboard');
            exit;
        }

        $db = (new Database())->getConnection();

        $patient_id       = (int) $_POST['patient_id'];
        $hosp_id          = !empty($_POST['hospitalisation_id']) ? (int) $_POST['hospitalisation_id'] : null;
        $medecin_id       = (int) $_SESSION['user_id'];
        $date_entree      = $_POST['date_entree']      ?? null;
        $diag_entree      = htmlspecialchars($_POST['diag_entree'] ?? '');
        $evolution        = htmlspecialchars($_POST['evolution'] ?? '');
        $date_sortie      = $_POST['date_sortie']      ?? null;
        $diag_sortie      = htmlspecialchars($_POST['diag_sortie'] ?? '');
        $traitement_sortie = htmlspecialchars($_POST['traitement_sortie'] ?? '');
        $rendez_vous      = htmlspecialchars($_POST['rendez_vous'] ?? '');
        $date_signature   = $_POST['date_signature']   ?? date('Y-m-d');
        $signe            = 0;
        $signature_data   = null;

        // Signature via canvas (base64) si présente
        if (!empty($_POST['signature_canvas']) && strlen($_POST['signature_canvas']) > 100) {
            $sigService     = new SignatureService();
            $signature_data = $sigService->resizeSignature($_POST['signature_canvas']);
            $signe          = 1;
        }

        $stmt = $db->prepare("
            INSERT INTO comptes_rendus_hosp
                (patient_id, hospitalisation_id, medecin_id, date_entree, diag_entree,
                 evolution, date_sortie, diag_sortie, traitement_sortie, rendez_vous,
                 date_signature, signe, signature_data)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $patient_id, $hosp_id, $medecin_id,
            $date_entree ?: null,
            $diag_entree, $evolution,
            $date_sortie ?: null,
            $diag_sortie, $traitement_sortie, $rendez_vous,
            $date_signature, $signe, $signature_data,
        ]);

        $crh_id = $db->lastInsertId();

        // Enregistrer dans documents_signes si signé
        if ($signe) {
            $sigService = new SignatureService();
            $sigService->signDocument('CRH', $crh_id, $medecin_id);
        }

        header('Location: ' . BASE_URL . 'patients/dossier/' . $patient_id . '?crh=saved');
        exit;
    }

    /**
     * Affiche un CRH existant (consultation + impression)
     */
    public function voirCRH($crh_id) {
        $db = (new Database())->getConnection();

        $stmt = $db->prepare("
            SELECT crh.*, p.nom, p.prenom, p.date_naissance, p.sexe, p.dossier_numero,
                   u.nom as medecin_nom, u.prenom as medecin_prenom
            FROM comptes_rendus_hosp crh
            JOIN patients p ON crh.patient_id = p.id
            JOIN users u ON crh.medecin_id = u.id
            WHERE crh.id = ?
        ");
        $stmt->execute([$crh_id]);
        $crh = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$crh) die("Compte-rendu introuvable.");

        // Reconstruit $patient et $hosp pour la vue
        $patient = [
            'id'             => $crh['patient_id'],
            'nom'            => $crh['nom'],
            'prenom'         => $crh['prenom'],
            'date_naissance' => $crh['date_naissance'],
            'sexe'           => $crh['sexe'],
            'dossier_numero' => $crh['dossier_numero'],
        ];
        $hosp = [
            'id'                  => $crh['hospitalisation_id'],
            'date_admission'      => $crh['date_entree'],
            'date_sortie_effective' => $crh['date_sortie'],
            'motif_hospitalisation' => $crh['diag_entree'],
        ];
        $age = date_diff(date_create($patient['date_naissance']), date_create('now'))->y;

        require_once __DIR__ . '/../views/formulaires/voir-crh.php';
    }
}
