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

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../models/Patient.php';

class ConsultationGynecologiqueController {
    private const SESSION_KEY = 'consultation_gyneco_temp';

    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) session_start();
    }

    /**
     * S'assure que 'gyneco' (et les autres types spécialisés) figurent dans
     * l'ENUM type_consultation. Exécuté une seule fois à la première finalisation.
     */
    private static function migrateTypeConsultation(\PDO $db): void {
        try {
            $row = $db->query("SHOW COLUMNS FROM consultations LIKE 'type_consultation'")->fetch(\PDO::FETCH_ASSOC);
            if (!$row) return;
            // Extraire les valeurs déjà présentes
            // Format : enum('interne','externe') ou varchar(...)
            if (!str_starts_with(strtolower($row['Type']), 'enum')) {
                return; // colonne varchar → pas besoin de migration
            }
            preg_match_all("/'([^']+)'/", $row['Type'], $m);
            $current = $m[1] ?? [];
            $toAdd   = ['gyneco', 'pediatrie', 'urgence', 'specialiste'];
            $missing = array_diff($toAdd, $current);
            if (empty($missing)) return;
            $all    = array_merge($current, $missing);
            $quoted = implode(',', array_map(fn($v) => "'$v'", $all));
            $db->exec("ALTER TABLE consultations MODIFY COLUMN type_consultation ENUM($quoted)");
        } catch (\Throwable $e) {
            error_log('[GynécoMigration] type_consultation: ' . $e->getMessage());
        }
    }

    /**
     * AJAX POST – Merge step data into SESSION
     * Body JSON: { data: {...fields...} }
     */
    public function sauvegarderEtape(): void {
        header('Content-Type: application/json');
        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        $data  = $input['data'] ?? [];

        if (!isset($_SESSION[self::SESSION_KEY])) {
            $_SESSION[self::SESSION_KEY] = [];
        }
        // Deep merge: keep existing values, override with new ones
        $_SESSION[self::SESSION_KEY] = array_merge($_SESSION[self::SESSION_KEY], $data);

        echo json_encode(['success' => true]);
    }

    /**
     * GET AJAX – Return current SESSION data
     */
    public function charger(): void {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'data'    => $_SESSION[self::SESSION_KEY] ?? []
        ]);
    }

    /**
     * AJAX POST – Finalize the consultation:
     * 1. Merge final form data + SESSION data
     * 2. Create/update `consultations` record
     * 3. Create ordonnance via PharmacieService
     * 4. Retroactively link lab/imagerie demandes to consultation_id
     * 5. Save to `formulaires_data` (SOUMIS)
     * 6. Clear SESSION
     * 7. Return { success, formulaire_id, consultation_id }
     */
    public function finaliser(): void {
        header('Content-Type: application/json');

        $input      = json_decode(file_get_contents('php://input'), true) ?? [];
        $patient_id = (int)($input['patient_id'] ?? 0);
        $formData   = $input['data']             ?? [];

        if (!$patient_id) {
            echo json_encode(['success' => false, 'message' => 'patient_id manquant']); return;
        }

        // Merge: SESSION data + incoming final data (final wins)
        $sessionData = $_SESSION[self::SESSION_KEY] ?? [];
        $data = array_merge($sessionData, $formData);

        $user_id     = (int)($_SESSION['user_id']    ?? 0);
        $service_id  = (int)($_SESSION['service_id'] ?? 0) ?: null;
        $dateConsult = !empty($data['date_consult']) ? $data['date_consult'] : date('Y-m-d');

        try {
            $db = (new Database())->getConnection();

            // ── 1. Parse TA ──────────────────────────────────────────────────
            $ta      = $data['ta'] ?? '';
            $taParts = explode('/', $ta);
            $taSys   = isset($taParts[0]) && is_numeric(trim($taParts[0])) ? (int)trim($taParts[0]) : null;
            $taDia   = isset($taParts[1]) && is_numeric(trim($taParts[1])) ? (int)trim($taParts[1]) : null;

            $examParts = [];
            if (!empty($data['ta']))          $examParts[] = 'TA : '       . $data['ta'];
            if (!empty($data['temperature'])) $examParts[] = 'T° : '       . $data['temperature'] . '°C';
            if (!empty($data['poids']))       $examParts[] = 'Poids : '    . $data['poids'] . ' kg';
            if (!empty($data['taille']))      $examParts[] = 'Taille : '   . $data['taille'] . ' cm';
            if (!empty($data['seins']))       $examParts[] = 'Seins : '    . $data['seins'];
            if (!empty($data['speculum']))    $examParts[] = 'Spéculum : ' . $data['speculum'];
            if (!empty($data['col_uterus'])) $examParts[]  = 'Col : '      . $data['col_uterus'];
            $examPhysique = implode(' | ', $examParts);

            // ── 2. Upsert into `consultations` ────────────────────────────────
            // Migration : ajoute 'gyneco' à l'ENUM si absent
            self::migrateTypeConsultation($db);

            // Mode édition : utiliser l'ID existant directement
            $editConsultationId = (int)($data['_edit_consultation_id'] ?? 0);
            if ($editConsultationId) {
                $consultation_id = $editConsultationId;
            } else {
                $stmtChk = $db->prepare(
                    "SELECT id FROM consultations
                     WHERE patient_id=? AND medecin_id=? AND DATE(date_consultation)=?
                     AND type_consultation='gyneco' LIMIT 1"
                );
                $stmtChk->execute([$patient_id, $user_id, $dateConsult]);
                $consultation_id = (int)($stmtChk->fetchColumn() ?: 0);
            }

            $consFields = [
                $data['motif']            ?? null,
                $data['plaintes']          ?? null,
                $data['atcd_medicaux']     ?? null,
                $data['atcd_chirurgicaux'] ?? null,
                !empty($data['temperature']) ? (float)$data['temperature'] : null,
                $taSys, $taDia,
                !empty($data['pouls'])  ? (int)$data['pouls']    : null,
                !empty($data['poids'])  ? (float)$data['poids']  : null,
                !empty($data['taille']) ? (float)$data['taille'] : null,
                $examPhysique ?: null,
                $data['diagnostic']        ?? null,
                $data['conduite_tenir']    ?? null,
                $data['note_cloture']      ?? null,
            ];

            if ($consultation_id) {
                $db->prepare(
                    "UPDATE consultations SET
                        motif_consultation=?, histoire_maladie=?,
                        atcd_medicaux=?, atcd_chirurgicaux=?,
                        temperature=?, tension_systolique=?, tension_diastolique=?,
                        frequence_cardiaque=?, poids=?, taille=?,
                        examen_physique=?, diagnostic_principal=?,
                        plan_traitement=?, notes_suivi=?
                     WHERE id=?"
                )->execute(array_merge($consFields, [$consultation_id]));
            } else {
                $db->prepare(
                    "INSERT INTO consultations
                        (patient_id, medecin_id, service_id, type_consultation, date_consultation,
                         motif_consultation, histoire_maladie,
                         atcd_medicaux, atcd_chirurgicaux,
                         temperature, tension_systolique, tension_diastolique,
                         frequence_cardiaque, poids, taille,
                         examen_physique, diagnostic_principal,
                         plan_traitement, notes_suivi)
                     VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)"
                )->execute(array_merge(
                    [$patient_id, $user_id, $service_id, 'gyneco', $dateConsult],
                    $consFields
                ));
                $consultation_id = (int)$db->lastInsertId();
            }

            // ── 3. Ordonnance via PharmacieService ────────────────────────────
            $ordonnance_id = null;
            $ordoText      = '';
            $ordoArray     = $data['ordonnance'] ?? [];
            if (!is_array($ordoArray)) $ordoArray = [];

            // Filter out empty rows
            $ordoArray = array_filter($ordoArray, fn($m) => !empty(trim($m['medicament'] ?? $m['nom_medicament'] ?? '')));

            if (!empty($ordoArray) && $consultation_id) {
                try {
                    require_once __DIR__ . '/../services/PharmacieService.php';
                    $pharmSvc  = new PharmacieService();
                    $medsNorm  = [];
                    $ordoLines = [];
                    foreach ($ordoArray as $m) {
                        $nom = trim($m['medicament'] ?? $m['nom_medicament'] ?? '');
                        if (!$nom) continue;
                        $posologie = trim(($m['dosage'] ?? '') . ' — ' . ($m['frequence'] ?? ''));
                        $posologie = trim($posologie, ' —');
                        $medsNorm[] = [
                            'medicament_id'  => null,
                            'nom_medicament' => $nom,
                            'posologie'      => $posologie,
                            'duree'          => $m['duree'] ?? '',
                            'quantite'       => 1,
                            'voie'           => $m['voie'] ?? 'Oral',
                            'instructions'   => '',
                        ];
                        $ordoLines[] = $nom
                            . (!empty($m['dosage'])    ? ' ' . $m['dosage']        : '')
                            . (!empty($m['voie'])      ? ' (' . $m['voie'] . ')'   : '')
                            . (!empty($m['frequence']) ? ' — ' . $m['frequence']   : '')
                            . (!empty($m['duree'])     ? ' — ' . $m['duree']       : '');
                    }
                    if ($medsNorm) {
                        $ordonnance_id = $pharmSvc->creerOrdonnancePharmacie($consultation_id, $medsNorm);
                        $ordoText      = implode("\n", $ordoLines);
                    }
                } catch (\Throwable $ePharm) {
                    error_log('[GynécoFinaliser] Ordonnance: ' . $ePharm->getMessage());
                }
            }

            // L'ordonnance est stockée dans ordonnances_pharmacie via PharmacieService.
            // On ajoute le texte lisible au plan_traitement si la colonne existe.
            if ($ordoText) {
                try {
                    $db->prepare("UPDATE consultations SET plan_traitement=CONCAT(COALESCE(plan_traitement,''), IF(plan_traitement IS NOT NULL AND plan_traitement<>'','\n---\n',''), ?) WHERE id=?")
                       ->execute([$ordoText, $consultation_id]);
                } catch (\Throwable $e) {}
            }

            // ── 4. Retroactively link lab/imagerie ────────────────────────────
            try {
                $db->prepare(
                    "UPDATE demandes_laboratoire SET consultation_id=?
                     WHERE patient_id=? AND consultation_id IS NULL
                       AND date_creation > DATE_SUB(NOW(), INTERVAL 4 HOUR)"
                )->execute([$consultation_id, $patient_id]);

                $db->prepare(
                    "UPDATE demandes_imagerie SET consultation_id=?
                     WHERE patient_id=? AND consultation_id IS NULL
                       AND date_creation > DATE_SUB(NOW(), INTERVAL 4 HOUR)"
                )->execute([$consultation_id, $patient_id]);
            } catch (\Throwable $eLien) {
                error_log('[GynécoFinaliser] Liaison demandes: ' . $eLien->getMessage());
            }

            // ── 5. Save to formulaires_data ───────────────────────────────────
            $data['consultation_id'] = $consultation_id;
            if ($ordonnance_id) $data['ordonnance_id'] = $ordonnance_id;

            // Auto-migrate table
            try {
                $db->exec("CREATE TABLE IF NOT EXISTS formulaires_data (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    patient_id INT NOT NULL, hosp_id INT DEFAULT NULL,
                    user_id INT NOT NULL, service_id INT DEFAULT NULL,
                    type_formulaire VARCHAR(60) NOT NULL, titre VARCHAR(120) NOT NULL,
                    data JSON NOT NULL, statut ENUM('BROUILLON','SOUMIS','SIGNE') DEFAULT 'BROUILLON',
                    date_creation DATETIME DEFAULT CURRENT_TIMESTAMP,
                    date_modification DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
                    KEY(patient_id), KEY(user_id), KEY(type_formulaire)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            } catch (\Throwable $eMig) {}

            // Nettoyer les marqueurs d'édition avant sauvegarde JSON
            $editFormulaireId = (int)($data['_edit_formulaire_id'] ?? 0);
            unset($data['_edit_consultation_id'], $data['_edit_formulaire_id']);

            // Upsert formulaires_data :
            //   - mode édition → update le formulaire existant (SOUMIS)
            //   - mode création → update BROUILLON s'il existe, sinon INSERT
            if ($editFormulaireId) {
                $existing = $editFormulaireId;
            } else {
                $stmtEx = $db->prepare("SELECT id FROM formulaires_data
                    WHERE patient_id=? AND type_formulaire='consultation-gyneco'
                    AND user_id=? AND statut='BROUILLON' LIMIT 1");
                $stmtEx->execute([$patient_id, $user_id]);
                $existing = (int)($stmtEx->fetchColumn() ?: 0);
            }

            if ($existing) {
                $db->prepare("UPDATE formulaires_data SET data=?, statut='SOUMIS' WHERE id=?")
                   ->execute([json_encode($data, JSON_UNESCAPED_UNICODE), $existing]);
                $formulaire_id = $existing;
            } else {
                $db->prepare("INSERT INTO formulaires_data
                    (patient_id, user_id, service_id, type_formulaire, titre, data, statut)
                    VALUES (?,?,?,'consultation-gyneco','Consultation gynécologique',?,'SOUMIS')")
                   ->execute([$patient_id, $user_id, $service_id, json_encode($data, JSON_UNESCAPED_UNICODE)]);
                $formulaire_id = (int)$db->lastInsertId();
            }

            // ── 6. Clear SESSION ──────────────────────────────────────────────
            unset($_SESSION[self::SESSION_KEY]);

            echo json_encode([
                'success'         => true,
                'formulaire_id'   => $formulaire_id,
                'consultation_id' => $consultation_id,
            ]);

        } catch (\PDOException $e) {
            error_log('[GynécoFinaliser] PDO: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * POST pharmacie/signer-ordonnance/{ordonnance_id}
     * Marque une ordonnance comme SIGNÉE dans ordonnances_pharmacie
     */
    public function signerOrdonnance(int $ordonnance_id): void {
        header('Content-Type: application/json');
        if (!$ordonnance_id) {
            echo json_encode(['success' => false, 'message' => 'ID manquant']); return;
        }
        try {
            $db = (new Database())->getConnection();
            // Vérifier que la table et la colonne existent
            $r = $db->query("SHOW COLUMNS FROM ordonnances_pharmacie LIKE 'statut'")->fetchColumn();
            if (!$r) { echo json_encode(['success' => true, 'note' => 'col statut absent']); return; }

            $db->prepare("UPDATE ordonnances_pharmacie SET statut='SIGNE', date_modification=NOW() WHERE id=?")
               ->execute([$ordonnance_id]);
            echo json_encode(['success' => true]);
        } catch (\Throwable $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
}
