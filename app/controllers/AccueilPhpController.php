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

        // Nettoyage automatique : patients restés en file PARAMETRES depuis > 24h sont retirés
        require_once __DIR__ . '/../services/WorkflowResetService.php';
        (new WorkflowResetService($db))->cleanWaitingListsIfStale();

        $rdvs = $db->query("
            SELECT p.nom, p.prenom, p.dossier_numero, p.sexe, p.telephone,
                   p.type_client, p.groupe_sanguin,
                   r.id, r.date_debut as date_rdv, r.titre as motif
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
            $annee2 = date('y');
            $prefix = "HSJM$annee2";
            $stmtMax = $db->prepare(
                "SELECT COALESCE(MAX(CAST(SUBSTRING(dossier_numero, 7) AS UNSIGNED)), 0)
                 FROM patients WHERE dossier_numero LIKE ? FOR UPDATE"
            );
            $stmtMax->execute(["$prefix%"]);
            $next_id        = (int)$stmtMax->fetchColumn() + 1;
            $dossier_numero = $prefix . str_pad($next_id, 4, '0', STR_PAD_LEFT);

            // Validation type_client
            $allowed_types = ['FAMILLE_PHP', 'AGENTS_PHP'];
            $type_client = in_array($_POST['type_client'] ?? '', $allowed_types)
                           ? $_POST['type_client'] : 'FAMILLE_PHP';

            // Routage circuit :
            //   AGENTS_PHP  → bureau Paramètres PHP dédié    (circuit = 'PHP')
            //   FAMILLE_PHP → file B1/B2 standard par parité (circuit = 'STANDARD')
            $circuit = ($type_client === 'AGENTS_PHP') ? 'PHP' : 'STANDARD';

            $date_naissance_php = !empty($_POST['date_naissance']) ? $_POST['date_naissance'] : null;

            // Insertion patient
            $stmt = $db->prepare("
                INSERT INTO patients (
                    dossier_numero, nom, prenom, date_naissance, sexe,
                    telephone, adresse, profession, situation_matrimoniale,
                    groupe_sanguin, contact_nom, contact_telephone,
                    type_client, circuit, statut_parcours, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'ACCUEIL', NOW())
            ");
            $stmt->execute([
                $dossier_numero,
                strtoupper(trim($_POST['nom'])),
                trim($_POST['prenom']),
                $date_naissance_php,
                $_POST['sexe'],
                $_POST['telephone'] ?? '',
                $_POST['adresse'] ?? '',
                $_POST['profession'] ?? '',
                $_POST['situation_matrimoniale'] ?? 'celibataire',
                ($_POST['groupe_sanguin'] ?: null),
                $_POST['contact_nom'] ?? '',
                $_POST['contact_telephone'] ?? '',
                $type_client,
                $circuit,
            ]);

            $patient_id = $db->lastInsertId();

            // Génération numéro d'ordre — reset auto à 1 chaque jour
            require_once __DIR__ . '/../services/WorkflowResetService.php';
            $num_ordre = (new WorkflowResetService($db))->getNextOrderNumber();

            $db->prepare("UPDATE patients
                SET numero_ordre = ?, statut_parcours = 'PARAMETRES', date_mise_en_parametres = NOW()
                WHERE id = ?")
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

        // Récupérer le type_client pour déterminer le circuit correct
        $stmtP = $db->prepare("SELECT type_client FROM patients WHERE id = ?");
        $stmtP->execute([(int)$id]);
        $patient = $stmtP->fetch(PDO::FETCH_ASSOC);

        // AGENTS_PHP → bureau PHP dédié ; FAMILLE_PHP → B1/B2 standard (par parité)
        $circuit = ($patient && $patient['type_client'] === 'AGENTS_PHP') ? 'PHP' : 'STANDARD';

        // Génère le numéro d'ordre — reset auto à 1 chaque nouveau jour
        require_once __DIR__ . '/../services/WorkflowResetService.php';
        $num = (new WorkflowResetService($db))->getNextOrderNumber();

        $db->prepare("UPDATE patients
            SET statut_parcours = 'PARAMETRES', numero_ordre = ?, circuit = ?, date_mise_en_parametres = NOW()
            WHERE id = ?")
           ->execute([$num, $circuit, (int)$id]);

        header('Location: ' . BASE_URL . 'accueil-php?success=visite');
        exit;
    }
}
