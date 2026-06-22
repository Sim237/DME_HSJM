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

require_once __DIR__ . '/../models/BaseModel.php';

class SuiviExterneController
{
    private \PDO $db;

    public function __construct()
    {
        $this->db = (new Database())->getConnection();
        $this->migrerColonnes();
    }

    // ── Migration auto des colonnes ───────────────────────────────
    private function migrerColonnes(): void
    {
        try {
            $cols = array_column(
                $this->db->query("DESCRIBE consultations")->fetchAll(\PDO::FETCH_ASSOC),
                'Field'
            );
            $toAdd = [];
            if (!in_array('statut_diagnostic',      $cols)) $toAdd[] = "ADD COLUMN statut_diagnostic     ENUM('PROVISOIRE','CONFIRME','REVISE','ANNULE') NOT NULL DEFAULT 'PROVISOIRE'";
            if (!in_array('date_confirmation_diag', $cols)) $toAdd[] = "ADD COLUMN date_confirmation_diag DATETIME NULL";
            if (!in_array('notes_revision_diag',    $cols)) $toAdd[] = "ADD COLUMN notes_revision_diag    TEXT NULL";
            if ($toAdd) $this->db->exec("ALTER TABLE consultations " . implode(', ', $toAdd));
        } catch (\Throwable $e) { error_log('[SuiviExterne] migration consultations: ' . $e->getMessage()); }

        try {
            $cols = array_column(
                $this->db->query("DESCRIBE agenda_medical")->fetchAll(\PDO::FETCH_ASSOC),
                'Field'
            );
            $toAdd = [];
            if (!in_array('bilan_demande_id', $cols)) $toAdd[] = "ADD COLUMN bilan_demande_id INT NULL";
            if (!in_array('statut_rdv',       $cols)) $toAdd[] = "ADD COLUMN statut_rdv       VARCHAR(30) NOT NULL DEFAULT 'CONFIRME'";
            if ($toAdd) $this->db->exec("ALTER TABLE agenda_medical " . implode(', ', $toAdd));
        } catch (\Throwable $e) { error_log('[SuiviExterne] migration agenda_medical: ' . $e->getMessage()); }
    }

    // ── Guard session ─────────────────────────────────────────────
    private function guard(): void
    {
        if (empty($_SESSION['user_id'])) {
            header('Location: ' . BASE_URL . 'login'); exit;
        }
    }

    // ─────────────────────────────────────────────────────────────
    //  DASHBOARD
    // ─────────────────────────────────────────────────────────────
    public function dashboard(): void
    {
        $this->guard();

        $role    = strtoupper($_SESSION['user_role'] ?? '');
        $userId  = (int)($_SESSION['user_id'] ?? 0);
        $filtre  = $_GET['filtre'] ?? 'tous';
        $search  = trim($_GET['q'] ?? '');

        // Restriction par médecin
        $whereRole = in_array($role, ['MEDECIN','DOCTOR'])
            ? "AND c.medecin_id = $userId" : '';

        // Filtre statut
        $whereStatut = match($filtre) {
            'attente_bilan' => "AND c.statut_diagnostic = 'PROVISOIRE'
                                AND dl.id IS NOT NULL
                                AND dl.statut NOT IN ('VALIDES','TERMINE')",
            'bilan_dispo'   => "AND dl.statut IN ('VALIDES','TERMINE')
                                AND c.statut_diagnostic = 'PROVISOIRE'",
            'confirme'      => "AND c.statut_diagnostic = 'CONFIRME'",
            'revise'        => "AND c.statut_diagnostic IN ('REVISE','ANNULE')",
            'sans_bilan'    => "AND dl.id IS NULL",
            default         => '',
        };

        $searchWhere = $search
            ? "AND (p.nom LIKE :q OR p.prenom LIKE :q OR p.dossier_numero LIKE :q)" : '';

        $sql = "
            SELECT
                c.id                     AS consultation_id,
                c.date_consultation,
                c.type_consultation,
                c.diagnostic_principal,
                c.statut_diagnostic,
                c.date_confirmation_diag,
                c.notes_revision_diag,
                c.devenir,
                p.id                     AS patient_id,
                p.nom, p.prenom, p.dossier_numero, p.date_naissance,
                p.statut_parcours,
                CONCAT(u.prenom,' ',u.nom) AS medecin_nom,
                c.medecin_id,
                dl.id                    AS bilan_id,
                dl.statut                AS bilan_statut,
                dl.date_resultats,
                dl.date_creation         AS bilan_demande_date,
                (SELECT COUNT(*)
                 FROM demande_examens de2
                 JOIN demandes_laboratoire dl2 ON de2.demande_id = dl2.id
                 WHERE dl2.consultation_id = c.id
                   AND de2.statut NOT IN ('VALIDE','ANNULE')) AS nb_examens_attente,
                ag.id                    AS rdv_id,
                ag.date_debut            AS rdv_date,
                ag.statut_rdv,
                ag.titre                 AS rdv_titre
            FROM consultations c
            JOIN patients p ON p.id = c.patient_id
            LEFT JOIN users u ON u.id = c.medecin_id
            LEFT JOIN demandes_laboratoire dl
                ON dl.id = (SELECT MAX(id) FROM demandes_laboratoire WHERE consultation_id = c.id)
            LEFT JOIN agenda_medical ag
                ON ag.bilan_demande_id = dl.id
               AND ag.statut_rdv = 'CONDITIONNEL'
               AND ag.id = (SELECT MAX(id) FROM agenda_medical WHERE bilan_demande_id = dl.id AND statut_rdv = 'CONDITIONNEL')
            WHERE c.type_consultation = 'EXTERNE'
            $whereRole
            $whereStatut
            $searchWhere
            ORDER BY
                CASE c.statut_diagnostic WHEN 'PROVISOIRE' THEN 0 ELSE 1 END,
                c.date_consultation DESC
            LIMIT 300
        ";

        $stmt = $this->db->prepare($sql);
        if ($search) $stmt->bindValue(':q', "%$search%");
        $stmt->execute();
        $patients_externes = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // Stats
        $stats = $this->getStats($whereRole);

        // Liste médecins pour le sélecteur de RDV
        $medecins = $this->db->query("
            SELECT id, CONCAT(prenom,' ',nom) AS nom_complet
            FROM users WHERE role IN ('MEDECIN','DOCTOR') AND actif = 1
            ORDER BY nom
        ")->fetchAll(\PDO::FETCH_ASSOC);

        $pageTitle = 'Suivi Patients Externes';
        require_once __DIR__ . '/../views/layouts/header.php';
        require_once __DIR__ . '/../views/suivi_externe/dashboard.php';
        require_once __DIR__ . '/../views/layouts/footer.php';
    }

    private function getStats(string $whereRole): array
    {
        $row = $this->db->query("
            SELECT
                COUNT(*)                                                                              AS total,
                SUM(c.statut_diagnostic = 'CONFIRME')                                                AS confirmes,
                SUM(c.statut_diagnostic IN ('REVISE','ANNULE'))                                       AS revises,
                SUM(c.statut_diagnostic = 'PROVISOIRE'
                    AND dl.id IS NOT NULL
                    AND dl.statut NOT IN ('VALIDES','TERMINE'))                                       AS en_attente_bilan,
                SUM(dl.statut IN ('VALIDES','TERMINE')
                    AND c.statut_diagnostic = 'PROVISOIRE')                                           AS bilan_disponible,
                SUM(c.statut_diagnostic = 'PROVISOIRE' AND dl.id IS NULL)                            AS sans_bilan
            FROM consultations c
            JOIN patients p ON p.id = c.patient_id
            LEFT JOIN demandes_laboratoire dl
                ON dl.id = (SELECT MAX(id) FROM demandes_laboratoire WHERE consultation_id = c.id)
            WHERE c.type_consultation = 'EXTERNE'
            $whereRole
        ")->fetch(\PDO::FETCH_ASSOC);

        return $row ?: [];
    }

    // ─────────────────────────────────────────────────────────────
    //  CONFIRMER DIAGNOSTIC
    // ─────────────────────────────────────────────────────────────
    public function confirmerDiagnostic(): void
    {
        header('Content-Type: application/json');
        $this->guard();

        $id   = (int)($_POST['consultation_id'] ?? 0);
        $note = trim($_POST['note'] ?? '');

        if (!$id) { echo json_encode(['success' => false, 'message' => 'ID manquant']); exit; }

        try {
            $this->db->prepare("
                UPDATE consultations
                SET statut_diagnostic      = 'CONFIRME',
                    date_confirmation_diag = NOW(),
                    notes_revision_diag    = ?
                WHERE id = ?
            ")->execute([$note ?: null, $id]);

            // Libérer le statut_parcours du patient si plus en attente
            $this->db->prepare("
                UPDATE patients p
                JOIN consultations c ON c.patient_id = p.id
                SET p.statut_parcours = 'CONSULTATION_TERMINEE'
                WHERE c.id = ? AND p.statut_parcours NOT IN ('HOSPITALISE','EN_CONSULTATION')
            ")->execute([$id]);

            echo json_encode(['success' => true, 'message' => 'Diagnostic confirmé avec succès.']);
        } catch (\Throwable $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    // ─────────────────────────────────────────────────────────────
    //  RÉVISER / ANNULER DIAGNOSTIC
    // ─────────────────────────────────────────────────────────────
    public function reviserDiagnostic(): void
    {
        header('Content-Type: application/json');
        $this->guard();

        $id      = (int)($_POST['consultation_id'] ?? 0);
        $nouveau = trim($_POST['nouveau_diagnostic'] ?? '');
        $note    = trim($_POST['note'] ?? '');
        $statut  = in_array($_POST['statut'] ?? '', ['REVISE','ANNULE']) ? $_POST['statut'] : 'REVISE';

        if (!$id)      { echo json_encode(['success' => false, 'message' => 'ID manquant']); exit; }
        if (!$nouveau) { echo json_encode(['success' => false, 'message' => 'Le nouveau diagnostic est obligatoire']); exit; }

        try {
            $this->db->prepare("
                UPDATE consultations
                SET statut_diagnostic      = ?,
                    diagnostic_principal   = ?,
                    notes_revision_diag    = ?,
                    date_confirmation_diag = NOW()
                WHERE id = ?
            ")->execute([$statut, $nouveau, $note ?: null, $id]);

            echo json_encode(['success' => true, 'message' => 'Diagnostic révisé avec succès.']);
        } catch (\Throwable $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    // ─────────────────────────────────────────────────────────────
    //  PLANIFIER RDV CONDITIONNEL (lié à un bilan)
    // ─────────────────────────────────────────────────────────────
    public function planifierRdvBilan(): void
    {
        header('Content-Type: application/json');
        $this->guard();

        $medecinId = (int)($_POST['medecin_id']      ?? $_SESSION['user_id'] ?? 0);
        $patientId = (int)($_POST['patient_id']       ?? 0);
        $bilanId   = (int)($_POST['bilan_id']         ?? 0);
        $titre     = trim($_POST['titre']             ?? 'Consultation résultats de bilan');
        $dateDebut = trim($_POST['date_debut']        ?? '');
        $dateFin   = trim($_POST['date_fin']          ?? '');
        $salle     = trim($_POST['salle']             ?? '');

        if (!$medecinId || !$patientId || !$dateDebut) {
            echo json_encode(['success' => false, 'message' => 'Médecin, patient et date sont obligatoires']); exit;
        }

        if (!$dateFin) {
            $dateFin = date('Y-m-d H:i:s', strtotime($dateDebut) + 1800);
        }

        try {
            $this->db->prepare("
                INSERT INTO agenda_medical
                    (medecin_id, patient_id, type_rdv, titre, description, date_debut, date_fin, salle, couleur, bilan_demande_id, statut_rdv)
                VALUES (?, ?, 'suivi', ?, ?, ?, ?, ?, '#f59e0b', ?, 'CONDITIONNEL')
            ")->execute([
                $medecinId, $patientId, $titre,
                'RDV conditionnel — en attente des résultats du bilan #' . ($bilanId ?: 'N/A'),
                $dateDebut, $dateFin,
                $salle ?: null,
                $bilanId ?: null,
            ]);

            $rdvId = $this->db->lastInsertId();

            // Mettre le patient en attente de bilan
            if ($patientId) {
                $this->db->prepare("
                    UPDATE patients SET statut_parcours = 'EN_ATTENTE_BILAN'
                    WHERE id = ? AND statut_parcours NOT IN ('HOSPITALISE','EN_CONSULTATION')
                ")->execute([$patientId]);
            }

            echo json_encode([
                'success' => true,
                'rdv_id'  => $rdvId,
                'message' => 'RDV conditionnel créé. Il sera automatiquement confirmé à la réception des résultats du bilan.',
            ]);
        } catch (\Throwable $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    // ─────────────────────────────────────────────────────────────
    //  MÉTHODE STATIQUE — appelée par LaboratoireController
    //  Activée quand un bilan est validé (résultats disponibles)
    // ─────────────────────────────────────────────────────────────
    public static function activerRdvConditionnels(\PDO $db, int $bilanId, int $patientId): void
    {
        try {
            // Confirmer les RDV en attente de ce bilan
            $db->prepare("
                UPDATE agenda_medical
                SET statut_rdv = 'CONFIRME'
                WHERE bilan_demande_id = ? AND statut_rdv = 'CONDITIONNEL'
            ")->execute([$bilanId]);

            // Mettre à jour le statut parcours patient
            $db->prepare("
                UPDATE patients SET statut_parcours = 'BILAN_DISPONIBLE'
                WHERE id = ? AND statut_parcours = 'EN_ATTENTE_BILAN'
            ")->execute([$patientId]);
        } catch (\Throwable $e) {
            error_log('[SuiviExterne] activerRdvConditionnels: ' . $e->getMessage());
        }
    }
}
