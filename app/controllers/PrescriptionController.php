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
   FICHIER : app/controllers/PrescriptionController.php
   ============================================================================ */
require_once __DIR__ . '/../models/Prescription.php';
require_once __DIR__ . '/../models/Medicament.php';
require_once __DIR__ . '/../models/Patient.php';
require_once __DIR__ . '/../services/SignatureService.php';
require_once __DIR__ . '/../services/AuditService.php';

class PrescriptionController {
    private $prescriptionModel;
    private $medicamentModel;
    private $patientModel;
    private $sigService;
    private $audit;

    public function __construct() {
        $this->prescriptionModel = new Prescription();
        $this->medicamentModel   = new Medicament();
        $this->patientModel      = new Patient();
        $this->sigService        = new SignatureService();
        $this->audit             = new AuditService();

        if (session_status() === PHP_SESSION_NONE) session_start();
    }

    /** Liste toutes les prescriptions */
    public function index() {
        $prescriptions = $this->prescriptionModel->getAll();
        require_once __DIR__ . '/../views/prescriptions/ordonnance.php';
    }

    /** Formulaire de création d'ordonnance */
    public function create() {
        $patient_id = $_GET['patient_id'] ?? null;
        if (!$patient_id) { header('Location: ' . BASE_URL . 'patients'); exit; }

        $patient    = $this->patientModel->getById($patient_id);
        $medicaments = $this->medicamentModel->getAll();

        if (!$patient) { header('Location: ' . BASE_URL . 'patients?error=patient_not_found'); exit; }

        require_once __DIR__ . '/../views/prescriptions/create.php';
    }

    /** Sauvegarde une nouvelle prescription */
    public function save() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . 'patients'); exit;
        }

        $patient_id      = $_POST['patient_id'] ?? null;
        $medicaments     = json_decode($_POST['medicaments'] ?? '[]', true);
        $recommandations = $_POST['notes'] ?? $_POST['recommandations'] ?? '';

        if (!$patient_id || empty($medicaments)) {
            header('Location: ' . BASE_URL . 'prescription/create?patient_id=' . $patient_id . '&error=invalid_data');
            exit;
        }

        $prescription_id = $this->prescriptionModel->create([
            'patient_id'      => $patient_id,
            'medecin_id'      => $_SESSION['user_id'] ?? 1,
            'consultation_id' => null,
            'medicaments'     => $medicaments,
            'recommandations' => $recommandations,
        ]);

        if ($prescription_id) {
            // ── Audit : prescription créée ──
            try {
                $pInfo = $this->patientModel->getById($patient_id);
                $pNom  = $pInfo ? trim(($pInfo['nom'] ?? '') . ' ' . ($pInfo['prenom'] ?? '')) : '';
                $noms  = array_map(fn($m) => ($m['nom'] ?? '?')
                            . (!empty($m['quantite']) ? ' ×' . $m['quantite'] : ''), $medicaments);
                $desc  = 'Ordonnance créée — ' . count($medicaments) . ' médicament(s)';
                if ($pNom) $desc .= ' pour ' . $pNom . ' (#' . $patient_id . ')';
                $desc .= ' : ' . mb_substr(implode(', ', $noms), 0, 200);

                $this->audit->log('CREATE', 'ordonnances', $prescription_id, $desc, null, [
                    'patient_id'      => $patient_id,
                    'patient'         => $pNom,
                    'nb_medicaments'  => count($medicaments),
                    'medicaments'     => $noms,
                    'recommandations' => mb_substr($recommandations, 0, 200),
                ]);
            } catch (Exception $e) {
                error_log('[PrescriptionController::save] Audit ignoré : ' . $e->getMessage());
            }
            header('Location: ' . BASE_URL . 'prescription/print?id=' . $prescription_id . '&success=1');
        } else {
            header('Location: ' . BASE_URL . 'prescription/create?patient_id=' . $patient_id . '&error=save_failed');
        }
        exit;
    }

    /**
     * Formulaire de modification d'une ordonnance existante
     */
    public function edit($id) {
        $id = (int)$id;
        $prescription = $this->prescriptionModel->getById($id);
        if (!$prescription) {
            header('Location: ' . BASE_URL . 'patients?error=prescription_not_found'); exit;
        }
        if ($prescription['statut'] === 'TERMINEE') {
            header('Location: ' . BASE_URL . 'prescription/print?id=' . $id . '&error=already_dispensed'); exit;
        }

        $role = strtoupper($_SESSION['user_role'] ?? '');
        $userId = (int)($_SESSION['user_id'] ?? 0);
        $canEdit = ($userId === (int)$prescription['medecin_id'])
                || in_array($role, ['ADMIN', 'ADMINISTRATEUR']);
        if (!$canEdit) {
            header('Location: ' . BASE_URL . 'prescription/print?id=' . $id . '&error=forbidden'); exit;
        }

        $medicaments     = $this->medicamentModel->getAll();
        $lignesActuelles = $this->prescriptionModel->getMedicaments($id);

        // Normaliser pour pré-remplissage JS
        $lignesActuelles = array_map(function($m) {
            return [
                'id'        => (int)($m['medicament_id'] ?? 0),
                'nom'       => $m['nom_medicament'] ?? $m['nom'] ?? '',
                'dosage'    => $m['dosage']    ?? '',
                'forme'     => $m['forme']     ?? '',
                'posologie' => $m['posologie'] ?? '',
                'duree'     => $m['duree']     ?? '',
                'quantite'  => (int)($m['quantite_prescrite'] ?? $m['quantite'] ?? 1),
            ];
        }, $lignesActuelles);

        require_once __DIR__ . '/../views/prescriptions/edit.php';
    }

    /**
     * Sauvegarde les modifications d'une ordonnance
     */
    public function updatePrescription($id) {
        $id = (int)$id;
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . 'prescription/print?id=' . $id); exit;
        }

        $prescription = $this->prescriptionModel->getById($id);
        if (!$prescription || $prescription['statut'] === 'TERMINEE') {
            header('Location: ' . BASE_URL . 'patients?error=forbidden'); exit;
        }

        $role   = strtoupper($_SESSION['user_role'] ?? '');
        $userId = (int)($_SESSION['user_id'] ?? 0);
        $canEdit = ($userId === (int)$prescription['medecin_id'])
                || in_array($role, ['ADMIN', 'ADMINISTRATEUR']);
        if (!$canEdit) {
            header('Location: ' . BASE_URL . 'prescription/print?id=' . $id . '&error=forbidden'); exit;
        }

        $medicaments = json_decode($_POST['medicaments'] ?? '[]', true);
        if (empty($medicaments)) {
            header('Location: ' . BASE_URL . 'prescription/edit/' . $id . '&error=no_meds'); exit;
        }

        $ok = $this->prescriptionModel->update($id, [
            'recommandations' => $_POST['notes'] ?? '',
            'medicaments'     => $medicaments,
        ]);

        if ($ok) {
            header('Location: ' . BASE_URL . 'prescription/print?id=' . $id . '&success=updated');
        } else {
            header('Location: ' . BASE_URL . 'prescription/edit/' . $id . '?error=save_failed');
        }
        exit;
    }

    /**
     * Vue impression / signature de l'ordonnance
     * Charge signature, cachet, numero_ordre et specialite du médecin
     */
    public function print() {
        $id = $_GET['id'] ?? null;
        if (!$id) { header('Location: ' . BASE_URL . 'patients'); exit; }

        $prescription = $this->prescriptionModel->getById($id);
        $medicaments  = $this->prescriptionModel->getMedicaments($id);
        $hopital      = $this->prescriptionModel->getHopitalSettings();

        if (!$prescription) {
            header('Location: ' . BASE_URL . 'patients?error=prescription_not_found'); exit;
        }

        // Normaliser recommandations
        if (!isset($prescription['recommandations'])) {
            $prescription['recommandations'] = $prescription['notes'] ?? '';
        }

        // Charger signature + cachet + infos médecin
        $sigInfo = $this->sigService->getMedecinSignatureInfo((int)$prescription['medecin_id']);
        $prescription = array_merge($prescription, $sigInfo);

        // Vérifier si déjà signé (pour afficher infos d'archive)
        $signatureDoc = $this->sigService->isDocumentSigned('ORDONNANCE', (int)$id);

        // Pour ordonnances par ordre : charger la signature du médecin signataire
        // même si l'ordonnance n'est pas encore signée numériquement
        $isParOrdre = ($prescription['type_ordonnance'] ?? '') === 'PAR_ORDRE';
        if ($isParOrdre && !$prescription['signature_src']) {
            // Pré-charger la signature du médecin désigné pour affichage visuel
            $sigInfoMedecin = $this->sigService->getMedecinSignatureInfo((int)$prescription['medecin_id']);
            $prescription['signature_src'] = $sigInfoMedecin['signature_src'] ?? '';
            $prescription['cachet_src']    = $sigInfoMedecin['cachet_src']    ?? '';
            if (empty($prescription['numero_ordre'])) $prescription['numero_ordre'] = $sigInfoMedecin['numero_ordre'] ?? '';
        }

        // Normaliser champs patient
        if (isset($prescription['nom'])    && !isset($prescription['patient_nom']))    $prescription['patient_nom']    = $prescription['nom'];
        if (isset($prescription['prenom']) && !isset($prescription['patient_prenom'])) $prescription['patient_prenom'] = $prescription['prenom'];

        // Normaliser médicaments
        $medicaments = array_map(function($m) {
            $m['medicament_nom'] = $m['nom_medicament'] ?? $m['nom'] ?? 'Médicament inconnu';
            $m['forme']          = $m['forme']    ?? '';
            $m['dosage']         = $m['dosage']   ?? '';
            $m['posologie']      = $m['posologie'] ?? '';
            $m['duree']          = $m['duree']    ?? '';
            return $m;
        }, $medicaments);

        require_once __DIR__ . '/../views/prescriptions/impression.php';
    }

    /**
     * API AJAX : signe l'ordonnance, l'enregistre dans documents_signes
     * et met à jour le statut + hash sur ordonnances_pharmacie
     */
    public function signerEtEnvoyer() {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']); return;
        }

        $id         = (int)($_POST['id'] ?? 0);
        $sessionUserId = (int)($_SESSION['user_id'] ?? 0);
        $sessionRole   = strtoupper($_SESSION['user_role'] ?? '');

        if (!$id || !$sessionUserId) {
            echo json_encode(['success' => false, 'message' => 'Données invalides']); return;
        }

        try {
            $db = (new Database())->getConnection();

            // Vérifier l'ordonnance dans ordonnances_pharmacie
            $stmt = $db->prepare("SELECT op.id, op.statut, op.medecin_id, op.type_ordonnance FROM ordonnances_pharmacie op WHERE op.id = ?");
            $stmt->execute([$id]);
            $ord = $stmt->fetch(PDO::FETCH_ASSOC);

            // Pour les ordonnances par ordre, le médecin_id de l'ordonnance est utilisé
            $medecin_id = (in_array($sessionRole, ['INFIRMIER','MAJOR']) && ($ord['type_ordonnance'] ?? '') === 'PAR_ORDRE')
                          ? (int)$ord['medecin_id']
                          : $sessionUserId;

            if (!$ord) {
                echo json_encode(['success' => false, 'message' => 'Ordonnance introuvable']); return;
            }
            if ($ord['statut'] === 'SIGNEE') {
                echo json_encode(['success' => false, 'message' => 'Déjà signée']); return;
            }

            // Signer via le service (crée l'entrée dans documents_signes)
            $hash = $this->sigService->signDocument('ORDONNANCE', $id, $medecin_id);

            // Mettre à jour ordonnances_pharmacie avec hash + statut SIGNEE
            $db->prepare("
                UPDATE ordonnances_pharmacie
                SET statut = 'SIGNEE', signature_hash = ?, signed_at = NOW(), signe_par = ?
                WHERE id = ?
            ")->execute([$hash, $medecin_id, $id]);

            echo json_encode([
                'success'    => true,
                'hash_short' => strtoupper(substr($hash, 0, 12)),
                'signed_at'  => date('d/m/Y H:i'),
            ]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * Archive des documents signés
     */
    public function archives() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: ' . BASE_URL . 'login'); exit;
        }

        $db         = (new Database())->getConnection();
        $medecin_id = (int)$_SESSION['user_id'];
        $role       = $_SESSION['user_role'] ?? '';

        $type_filtre = $_GET['type']    ?? 'ORDONNANCE';
        $date_filtre = $_GET['date']    ?? '';
        $patient_q   = $_GET['patient'] ?? '';

        $where  = "o.statut = 'SIGNEE'";
        $params = [];

        // Cloisonnement : les médecins ne voient que leurs ordonnances
        if (!in_array($role, ['ADMIN', 'DIRECTEUR'])) {
            $where .= " AND o.medecin_id = ?";
            $params[] = $medecin_id;
        }
        if ($date_filtre) {
            $where .= " AND DATE(COALESCE(o.signed_at, o.date_creation)) = ?";
            $params[] = $date_filtre;
        }
        if ($patient_q) {
            $where .= " AND (p.nom LIKE ? OR p.prenom LIKE ? OR p.dossier_numero LIKE ?)";
            $params[] = "%$patient_q%";
            $params[] = "%$patient_q%";
            $params[] = "%$patient_q%";
        }

        $stmt = $db->prepare("
            SELECT
                o.id, o.date_creation, o.signed_at, o.signature_hash, o.statut,
                p.nom  AS patient_nom, p.prenom AS patient_prenom, p.dossier_numero,
                u.nom  AS medecin_nom, u.prenom AS medecin_prenom, u.specialite,
                COUNT(om.id) AS nb_medicaments
            FROM ordonnances_pharmacie o
            JOIN patients p   ON o.patient_id  = p.id
            JOIN users u      ON o.medecin_id  = u.id
            LEFT JOIN ordonnance_medicaments om ON om.ordonnance_id = o.id
            WHERE $where
            GROUP BY o.id
            ORDER BY COALESCE(o.signed_at, o.date_creation) DESC
            LIMIT 200
        ");
        $stmt->execute($params);
        $documents = $stmt->fetchAll(PDO::FETCH_ASSOC);

        require_once __DIR__ . '/../views/prescriptions/archives.php';
    }

    /** Vérification du stock AJAX */
    public function checkStock() {
        header('Content-Type: application/json');
        $medicament_id = $_POST['medicament_id'] ?? null;
        if ($medicament_id) {
            $stock = $this->medicamentModel->getStock($medicament_id);
            echo json_encode(['disponible' => $stock > 0, 'quantite' => $stock]);
        }
    }

    /** Historique des prescriptions d'un patient (AJAX) */
    public function history() {
        header('Content-Type: application/json');
        $patient_id = $_GET['patient_id'] ?? null;
        if ($patient_id) {
            echo json_encode($this->prescriptionModel->getByPatient($patient_id));
        }
    }

    // ─── PRESCRIPTIONS PAR ORDRE (Infirmier / Major) ─────────────────────────

    /**
     * Formulaire de prescription par ordre
     * L'infirmier choisit le patient, le médecin signataire et les médicaments.
     */
    public function parOrdreCreate() {
        $role = strtoupper($_SESSION['user_role'] ?? '');
        if (!in_array($role, ['INFIRMIER', 'MAJOR', 'ADMIN', 'ADMINISTRATEUR'])) {
            header('Location: ' . BASE_URL . 'dashboard'); exit;
        }

        $db        = (new Database())->getConnection();
        $serviceId = (int)($_SESSION['service_id'] ?? 0);
        $patient_id = $_GET['patient_id'] ?? null;

        // Médecins avec ou sans signature
        $medecins = $db->query("
            SELECT u.id, u.nom, u.prenom, u.specialite,
                   (ms.signature_image IS NOT NULL) AS has_signature
            FROM users u
            LEFT JOIN medecin_signatures ms ON ms.medecin_id = u.id AND ms.is_active = 1
            WHERE u.role IN ('MEDECIN','ADMIN','ADMINISTRATEUR') AND u.actif = 1
            ORDER BY has_signature DESC, u.nom, u.prenom
        ")->fetchAll(PDO::FETCH_ASSOC);

        // Patients hospitalisés dans le service
        $stmtP = $db->prepare("
            SELECT p.id, p.nom, p.prenom, p.dossier_numero,
                   l.nom_lit, c.nom_chambre
            FROM patients p
            JOIN hospitalisations h ON p.id = h.patient_id
            LEFT JOIN lits l ON h.lit_id = l.id
            LEFT JOIN chambres c ON l.chambre_id = c.id
            WHERE h.service_id = ? AND h.statut = 'en_cours'
            ORDER BY p.nom, p.prenom
        ");
        $stmtP->execute([$serviceId]);
        $patients_service = $stmtP->fetchAll(PDO::FETCH_ASSOC);

        // Patient pré-sélectionné ?
        $patient = null;
        if ($patient_id) {
            $patient = $this->patientModel->getById($patient_id);
        }

        $medicaments = $this->medicamentModel->getAll();

        require_once __DIR__ . '/../views/prescriptions/par_ordre.php';
    }

    /**
     * Enregistrement d'une prescription par ordre
     */
    public function parOrdreSave() {
        $role = strtoupper($_SESSION['user_role'] ?? '');
        if (!in_array($role, ['INFIRMIER', 'MAJOR', 'ADMIN', 'ADMINISTRATEUR'])) {
            header('Location: ' . BASE_URL . 'dashboard'); exit;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . 'prescription/par-ordre'); exit;
        }

        $patient_id      = (int)($_POST['patient_id']  ?? 0);
        $medecin_id      = (int)($_POST['medecin_id']  ?? 0);
        $medicaments     = json_decode($_POST['medicaments'] ?? '[]', true);
        $recommandations = trim($_POST['notes'] ?? '');

        if (!$patient_id || !$medecin_id || empty($medicaments)) {
            header('Location: ' . BASE_URL . 'prescription/par-ordre?patient_id=' . $patient_id . '&error=invalid_data');
            exit;
        }

        $prescription_id = $this->prescriptionModel->create([
            'patient_id'                => $patient_id,
            'medecin_id'                => $medecin_id,
            'consultation_id'           => null,
            'medicaments'               => $medicaments,
            'recommandations'           => $recommandations,
            'type_ordonnance'           => 'PAR_ORDRE',
            'infirmier_prescripteur_id' => (int)($_SESSION['user_id'] ?? 0),
        ]);

        if ($prescription_id) {
            header('Location: ' . BASE_URL . 'prescription/print?id=' . $prescription_id . '&success=1');
        } else {
            header('Location: ' . BASE_URL . 'prescription/par-ordre?patient_id=' . $patient_id . '&error=save_failed');
        }
        exit;
    }
}
