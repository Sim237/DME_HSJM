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

        $sql = "SELECT s.nom_service, s.id AS lit_service_id, c.nom_chambre, l.*,
                       p.nom, p.prenom, p.sexe,
                       h.service_id        AS patient_service_id,
                       sp.nom_service      AS patient_service_nom
                FROM services s
                JOIN chambres c  ON s.id   = c.service_id
                JOIN lits l      ON c.id   = l.chambre_id
                LEFT JOIN patients p ON l.patient_id = p.id
                LEFT JOIN hospitalisations h ON h.patient_id = p.id AND h.statut = 'en_cours'
                LEFT JOIN services sp ON h.service_id = sp.id
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

    /**
     * AJAX : retourne le lit_id en cours pour un patient hospitalisé
     */
    public function getLitPatient() {
        header('Content-Type: application/json');
        $db = (new Database())->getConnection();
        $patient_id = (int)($_GET['patient_id'] ?? 0);
        $stmt = $db->prepare("SELECT h.lit_id FROM hospitalisations h WHERE h.patient_id = ? AND h.statut = 'en_cours' ORDER BY h.id DESC LIMIT 1");
        $stmt->execute([$patient_id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        echo json_encode(['lit_id' => $row ? (int)$row['lit_id'] : null]);
        exit;
    }

    /**
     * AJAX : liste des lits disponibles (pour le modal Admettre au Lit dans le dossier)
     */
    public function getLitsDisponibles() {
        header('Content-Type: application/json');
        $db = (new Database())->getConnection();
        $stmt = $db->query("
            SELECT l.id, l.nom_lit, c.nom_chambre, s.nom_service, s.id AS service_id
            FROM lits l
            JOIN chambres c ON l.chambre_id = c.id
            JOIN services s ON c.service_id = s.id
            WHERE l.statut IN ('DISPONIBLE','LIBRE')
            ORDER BY s.nom_service, c.nom_chambre, l.nom_lit
        ");
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        exit;
    }

    public function confirmerAdmission() {
        header('Content-Type: application/json');
        $db = (new Database())->getConnection();
        try {
            $db->beginTransaction();

            $patient_id = (int)$_POST['patient_id'];
            $lit_id     = (int)$_POST['lit_id'];
            $motif      = trim($_POST['motif_hospitalisation'] ?? '');

            // Récupérer le service du lit
            $stmtSvc = $db->prepare("SELECT c.service_id FROM lits l JOIN chambres c ON l.chambre_id = c.id WHERE l.id = ?");
            $stmtSvc->execute([$lit_id]);
            $svcRow = $stmtSvc->fetch(PDO::FETCH_ASSOC);
            $service_id = $svcRow ? (int)$svcRow['service_id'] : (int)($_SESSION['service_id'] ?? 0);

            // Créer l'entrée dans la table hospitalisations
            $db->prepare("INSERT INTO hospitalisations (patient_id, service_id, lit_id, date_admission, statut) VALUES (?, ?, ?, NOW(), 'en_cours')")
               ->execute([$patient_id, $service_id, $lit_id]);

            // Marquer le lit comme occupé
            $db->prepare("UPDATE lits SET statut = 'OCCUPE', occupied_by_patient_id = ? WHERE id = ?")->execute([$patient_id, $lit_id]);

            // Marquer le patient comme hospitalisé
            $db->prepare("UPDATE patients SET statut = 'HOSPITALISE', statut_hosp = 'HOSPITALISE' WHERE id = ?")->execute([$patient_id]);

            // Traçabilité mouvement lit
            $db->prepare("INSERT INTO mouvements_lits (patient_id, lit_id, type_mouvement, user_id) VALUES (?, ?, 'ADMISSION', ?)")
               ->execute([$patient_id, $lit_id, $_SESSION['user_id']]);

            $db->commit();
            echo json_encode(['success' => true, 'message' => 'Patient admis au lit avec succès.']);
        } catch (Exception $e) {
            $db->rollBack();
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
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
            $db->prepare("UPDATE lits SET statut = 'NETTOYAGE', occupied_by_patient_id = NULL WHERE id = ?")->execute([$lit_id]);

            // 2. On marque le patient comme SORTI (tous les champs de statut)
            $db->prepare("
                UPDATE patients
                SET statut          = 'SORTIE',
                    statut_parcours = 'SORTI',
                    statut_hosp     = 'AUCUN',
                    medecin_id      = NULL
                WHERE id = ?
            ")->execute([$patient_id]);

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
     * AJAX : Changer le statut d'un lit (DISPONIBLE / NETTOYAGE / MAINTENANCE)
     * Réservé aux infirmiers / major / admin du service. Lit occupé interdit.
     */
    public function changerStatut() {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']); exit;
        }
        $role = strtoupper($_SESSION['user_role'] ?? '');
        $rolesOk = ['INFIRMIER','INFIRMIER_CONSULTANT','INFIRMIER_MAJOR','MAJOR','MAJOR_INFIRMIER','MEDECIN','ADMIN','ADMINISTRATEUR'];
        if (!in_array($role, $rolesOk)) {
            echo json_encode(['success' => false, 'message' => 'Accès non autorisé']); exit;
        }

        $lit_id  = (int)($_POST['lit_id'] ?? 0);
        $statut  = strtoupper(trim($_POST['statut'] ?? ''));
        $statutsOk = ['DISPONIBLE','NETTOYAGE','MAINTENANCE'];
        if (!$lit_id || !in_array($statut, $statutsOk)) {
            echo json_encode(['success' => false, 'message' => 'Données invalides']); exit;
        }

        try {
            $db = (new Database())->getConnection();
            $stmt = $db->prepare("SELECT l.id, l.statut, l.occupied_by_patient_id,
                                         c.service_id, c.nom_chambre, l.nom_lit
                                  FROM lits l JOIN chambres c ON c.id = l.chambre_id WHERE l.id = ?");
            $stmt->execute([$lit_id]);
            $lit = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$lit) { echo json_encode(['success' => false, 'message' => 'Lit introuvable']); exit; }

            if (!empty($lit['occupied_by_patient_id']) || strtoupper($lit['statut']) === 'OCCUPE') {
                echo json_encode(['success' => false, 'message' => 'Lit occupé : déchargez le patient d\'abord.']); exit;
            }

            // Restriction service (sauf admin)
            if (!in_array($role, ['ADMIN','ADMINISTRATEUR'])) {
                $userSvc = (int)($_SESSION['service_id'] ?? 0);
                if ($userSvc && (int)$lit['service_id'] !== $userSvc) {
                    echo json_encode(['success' => false, 'message' => 'Ce lit n\'appartient pas à votre service.']); exit;
                }
            }

            $db->prepare("UPDATE lits SET statut = ? WHERE id = ?")->execute([$statut, $lit_id]);

            try {
                (new AuditService())->log('UPDATE', 'lits', $lit_id,
                    'Statut du lit ' . trim(($lit['nom_chambre'] ?? '') . ' · ' . ($lit['nom_lit'] ?? '')) . " → $statut",
                    ['statut' => $lit['statut']], ['statut' => $statut]);
            } catch (\Throwable $e) {}

            echo json_encode(['success' => true, 'statut' => $statut]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
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