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
require_once __DIR__ . '/../services/SignatureService.php';

class FormulaireController {

    /* ══════════════════════════════════════════════════════════════════
     * Migration auto : table formulaires_data
     * ══════════════════════════════════════════════════════════════════ */
    private static function migrateFormulaireData(\PDO $db): void {
        try {
            $db->exec("CREATE TABLE IF NOT EXISTS formulaires_data (
                id INT AUTO_INCREMENT PRIMARY KEY,
                patient_id INT NOT NULL,
                hosp_id INT DEFAULT NULL,
                user_id INT NOT NULL,
                service_id INT DEFAULT NULL,
                type_formulaire VARCHAR(60) NOT NULL,
                titre VARCHAR(120) NOT NULL,
                data JSON NOT NULL,
                statut ENUM('BROUILLON','SOUMIS','SIGNE') DEFAULT 'BROUILLON',
                date_creation DATETIME DEFAULT CURRENT_TIMESTAMP,
                date_modification DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
                KEY(patient_id), KEY(user_id), KEY(type_formulaire)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        } catch (\Throwable $e) {}
    }

    /* ══════════════════════════════════════════════════════════════════
     * POST formulaire/sauvegarder-generique
     * Body JSON : { slug, patient_id, hosp_id, data:{...} }
     * ══════════════════════════════════════════════════════════════════ */
    public function sauvegarderGenerique(): void {
        header('Content-Type: application/json');
        $input      = json_decode(file_get_contents('php://input'), true) ?? [];
        $slug       = trim($input['slug']       ?? '');
        $patient_id = (int)($input['patient_id'] ?? 0);
        $hosp_id    = (int)($input['hosp_id']    ?? 0) ?: null;
        $data       = $input['data'] ?? [];
        $statut     = in_array($input['statut'] ?? '', ['BROUILLON','SOUMIS']) ? $input['statut'] : 'SOUMIS';

        if (!$slug || !$patient_id || empty($data)) {
            echo json_encode(['success' => false, 'message' => 'Données incomplètes']); return;
        }

        $titre      = self::titreDuSlug($slug);
        $user_id    = (int)($_SESSION['user_id']    ?? 0);
        $service_id = (int)($_SESSION['service_id'] ?? 0) ?: null;

        try {
            $db = (new Database())->getConnection();
            self::migrateFormulaireData($db);

            // Upsert : si brouillon existant pour ce patient+type+user → UPDATE
            $stmtEx = $db->prepare("SELECT id FROM formulaires_data
                WHERE patient_id=? AND type_formulaire=? AND user_id=? AND statut='BROUILLON' LIMIT 1");
            $stmtEx->execute([$patient_id, $slug, $user_id]);
            $existing = $stmtEx->fetchColumn();

            if ($existing) {
                $db->prepare("UPDATE formulaires_data SET data=?, statut=?, hosp_id=? WHERE id=?")
                   ->execute([json_encode($data), $statut, $hosp_id, $existing]);
                $formulaire_id = $existing;
            } else {
                $db->prepare("INSERT INTO formulaires_data
                    (patient_id, hosp_id, user_id, service_id, type_formulaire, titre, data, statut)
                    VALUES (?,?,?,?,?,?,?,?)")
                   ->execute([$patient_id, $hosp_id, $user_id, $service_id, $slug, $titre, json_encode($data), $statut]);
                $formulaire_id = $db->lastInsertId();
            }

            // ── Sauvegarde spécifique consultation-gyneco → table consultations ──
            if ($slug === 'consultation-gyneco' && $statut === 'SOUMIS') {
                try {
                    // Extraction des champs clés
                    $ta     = $data['ta'] ?? '';
                    $taParts = explode('/', $ta);
                    $taSys  = isset($taParts[0]) && is_numeric(trim($taParts[0])) ? (int)trim($taParts[0]) : null;
                    $taDia  = isset($taParts[1]) && is_numeric(trim($taParts[1])) ? (int)trim($taParts[1]) : null;

                    // Ordonnance → texte lisible
                    $ordo = '';
                    if (!empty($data['ordonnance']) && is_array($data['ordonnance'])) {
                        $lignes = [];
                        foreach ($data['ordonnance'] as $m) {
                            $nom = trim($m['medicament'] ?? $m['nom'] ?? '');
                            if ($nom) {
                                $lignes[] = $nom
                                    . (!empty($m['dosage'])    ? ' '   . $m['dosage']    : '')
                                    . (!empty($m['voie'])      ? ' ('  . $m['voie'] . ')': '')
                                    . (!empty($m['frequence']) ? ' — ' . $m['frequence'] : '')
                                    . (!empty($m['duree'])     ? ' — ' . $m['duree']     : '');
                            }
                        }
                        $ordo = implode("\n", $lignes);
                    }

                    // Examen physique résumé
                    $examParts = [];
                    if (!empty($data['ta']))          $examParts[] = 'TA : '          . $data['ta'];
                    if (!empty($data['temperature'])) $examParts[] = 'T° : '          . $data['temperature'] . '°C';
                    if (!empty($data['poids']))       $examParts[] = 'Poids : '        . $data['poids'] . ' kg';
                    if (!empty($data['taille']))      $examParts[] = 'Taille : '       . $data['taille'] . ' cm';
                    if (!empty($data['seins']))       $examParts[] = 'Seins : '        . $data['seins'];
                    if (!empty($data['speculum']))    $examParts[] = 'Spéculum : '     . $data['speculum'];
                    if (!empty($data['col_uterus']))  $examParts[] = 'Col utérin : '   . $data['col_uterus'];
                    $examPhysique = implode(' | ', $examParts);

                    $dateConsult = $data['date_consult'] ?? date('Y-m-d');

                    // Migration ENUM : ajouter 'gyneco' si absent
                    try {
                        $eRow = $db->query("SHOW COLUMNS FROM consultations LIKE 'type_consultation'")->fetch(\PDO::FETCH_ASSOC);
                        if ($eRow && str_starts_with(strtolower($eRow['Type']), 'enum')) {
                            preg_match_all("/'([^']+)'/", $eRow['Type'], $eM);
                            if (!in_array('gyneco', $eM[1] ?? [])) {
                                $all = array_merge($eM[1], ['gyneco','pediatrie','urgence','specialiste']);
                                $db->exec("ALTER TABLE consultations MODIFY COLUMN type_consultation ENUM(" . implode(',', array_map(fn($v)=>"'$v'", $all)) . ")");
                            }
                        }
                    } catch (\Throwable $eMig) {}

                    // Upsert : éviter les doublons (même patient + médecin + jour)
                    $stmtChk = $db->prepare(
                        "SELECT id FROM consultations
                         WHERE patient_id=? AND medecin_id=? AND DATE(date_consultation)=?
                         AND type_consultation='gyneco' LIMIT 1"
                    );
                    $stmtChk->execute([$patient_id, $user_id, $dateConsult]);
                    $existingCons = $stmtChk->fetchColumn();

                    if ($existingCons) {
                        $db->prepare(
                            "UPDATE consultations SET
                                motif_consultation=?, histoire_maladie=?,
                                atcd_medicaux=?, atcd_chirurgicaux=?,
                                temperature=?, tension_systolique=?, tension_diastolique=?,
                                frequence_cardiaque=?, poids=?, taille=?,
                                examen_physique=?, diagnostic_principal=?,
                                plan_traitement=?, notes_suivi=?
                             WHERE id=?"
                        )->execute([
                            $data['motif']           ?? null,
                            $data['plaintes']         ?? null,
                            $data['atcd_medicaux']    ?? null,
                            $data['atcd_chirurgicaux']?? null,
                            !empty($data['temperature']) ? (float)$data['temperature'] : null,
                            $taSys, $taDia,
                            !empty($data['pouls'])  ? (int)$data['pouls']  : null,
                            !empty($data['poids'])  ? (float)$data['poids']: null,
                            !empty($data['taille']) ? (float)$data['taille']:null,
                            $examPhysique ?: null,
                            $data['diagnostic']       ?? null,
                            $data['conduite_tenir']   ?? null,
                            $data['note_cloture']     ?? null,
                            $existingCons,
                        ]);
                    } else {
                        $db->prepare(
                            "INSERT INTO consultations
                                (patient_id, medecin_id, type_consultation, date_consultation,
                                 motif_consultation, histoire_maladie,
                                 atcd_medicaux, atcd_chirurgicaux,
                                 temperature, tension_systolique, tension_diastolique,
                                 frequence_cardiaque, poids, taille,
                                 examen_physique, diagnostic_principal,
                                 plan_traitement, notes_suivi)
                             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)"
                        )->execute([
                            $patient_id,  $user_id,  'gyneco',  $dateConsult,
                            $data['motif']            ?? null,
                            $data['plaintes']          ?? null,
                            $data['atcd_medicaux']     ?? null,
                            $data['atcd_chirurgicaux'] ?? null,
                            !empty($data['temperature']) ? (float)$data['temperature'] : null,
                            $taSys, $taDia,
                            !empty($data['pouls'])  ? (int)$data['pouls']   : null,
                            !empty($data['poids'])  ? (float)$data['poids'] : null,
                            !empty($data['taille']) ? (float)$data['taille']: null,
                            $examPhysique ?: null,
                            $data['diagnostic']        ?? null,
                            $data['conduite_tenir']    ?? null,
                            $data['note_cloture']      ?? null,
                        ]);
                    }
                } catch (\PDOException $eCons) {
                    // On log l'erreur mais on ne bloque pas la réponse
                    error_log('Gyneco→consultations: ' . $eCons->getMessage());
                }
            }

            echo json_encode([
                'success'       => true,
                'formulaire_id' => $formulaire_id,
                'print_url'     => BASE_URL . 'formulaire/imprimer/' . $slug . '/' . $formulaire_id
            ]);
        } catch (\PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /* ══════════════════════════════════════════════════════════════════
     * GET formulaire/imprimer/{slug}/{formulaire_id}
     * Charge le template papier original avec les données sauvegardées
     * ══════════════════════════════════════════════════════════════════ */
    public function imprimer(string $slug, int $formulaire_id): void {
        $db = (new Database())->getConnection();
        self::migrateFormulaireData($db);

        $stmt = $db->prepare("SELECT fd.*, p.nom, p.prenom, p.date_naissance, p.sexe,
                                     p.telephone, p.adresse, p.dossier_numero,
                                     u.nom as user_nom, u.prenom as user_prenom, u.specialite
                              FROM formulaires_data fd
                              JOIN patients p ON fd.patient_id = p.id
                              JOIN users   u ON fd.user_id     = u.id
                              WHERE fd.id = ? AND fd.type_formulaire = ?");
        $stmt->execute([$formulaire_id, $slug]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        if (!$row) { die("Formulaire introuvable."); }

        $formData  = json_decode($row['data'], true) ?? [];
        $patient   = [
            'id'              => $row['patient_id'],
            'nom'             => $row['nom'],
            'prenom'          => $row['prenom'],
            'date_naissance'  => $row['date_naissance'],
            'sexe'            => $row['sexe'],
            'telephone'       => $row['telephone'],
            'adresse'         => $row['adresse'],
            'dossier_numero'  => $row['dossier_numero'],
        ];
        $praticien = $row['user_nom'] . ' ' . $row['user_prenom'];
        $titre     = $row['titre'];
        $age       = $row['date_naissance'] ? date_diff(date_create($row['date_naissance']), date_create('now'))->y : 0;

        // Charger le template papier
        $viewFile = __DIR__ . '/../views/formulaires/print/' . $slug . '.php';
        if (!file_exists($viewFile)) {
            $viewFile = __DIR__ . '/../views/formulaires/' . $slug . '.php';
        }
        if (!file_exists($viewFile)) { die("Template introuvable pour : $slug"); }

        define('PRINT_MODE', true);

        // Capture du rendu pour y injecter la signature médecin si présente
        ob_start();
        require_once $viewFile;
        $html = ob_get_clean();

        // --- Injecter bloc signature médecin si elle existe ---
        $sigMed  = $formData['signature_medecin']      ?? '';
        $nomMed  = htmlspecialchars($formData['nom_medecin_signataire'] ?? '');
        $dateMed = htmlspecialchars($formData['date_signature_medecin'] ?? '');
        if ($sigMed && str_starts_with($sigMed, 'data:image')) {
            $sigBlock = '
<div style="margin-top:28px;padding-top:16px;border-top:2px dashed #0d9488;page-break-inside:avoid;display:flex;justify-content:flex-end;">
  <div style="text-align:center;min-width:220px;">
    <img src="' . htmlspecialchars($sigMed) . '"
         style="max-height:75px;max-width:210px;display:block;margin:0 auto 6px;">
    <div style="border-top:1px solid #333;padding-top:4px;font-size:9pt;font-weight:bold;">
      Dr ' . $nomMed . '
    </div>
    <div style="font-size:8pt;color:#555;">' . $dateMed . '</div>
  </div>
</div>';
            // Injecter juste avant la fermeture du paper-sheet
            $html = preg_replace('#</div>\s*</body>\s*</html>#i',
                $sigBlock . '</div></body></html>', $html, 1);
        }

        echo $html;

        // Auto-print sauf si appelé depuis apercu-signer (?noprint=1)
        if (empty($_GET['noprint'])) {
            echo '<script>window.onload=()=>{window.print();}</script>';
        }
    }

    /* ══════════════════════════════════════════════════════════════════
     * GET formulaire/recap-gyneco/{formulaire_id}
     * Page récap moderne post-consultation gynécologique
     * ══════════════════════════════════════════════════════════════════ */
    public function recapGyneco(int $formulaire_id): void {
        $db = (new Database())->getConnection();
        self::migrateFormulaireData($db);

        $stmt = $db->prepare("SELECT fd.*, p.nom, p.prenom, p.date_naissance, p.sexe,
                                     p.telephone, p.adresse, p.dossier_numero,
                                     u.nom as user_nom, u.prenom as user_prenom, u.specialite
                              FROM formulaires_data fd
                              JOIN patients p ON fd.patient_id = p.id
                              JOIN users   u ON fd.user_id     = u.id
                              WHERE fd.id = ? AND fd.type_formulaire = 'consultation-gyneco'");
        $stmt->execute([$formulaire_id]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        if (!$row) { die("Consultation introuvable."); }

        $formData  = json_decode($row['data'], true) ?? [];
        $patient   = [
            'id'             => $row['patient_id'],
            'nom'            => $row['nom'],
            'prenom'         => $row['prenom'],
            'date_naissance' => $row['date_naissance'],
            'dossier_numero' => $row['dossier_numero'],
            'sexe'           => $row['sexe'],
            'telephone'      => $row['telephone'],
        ];

        // Signature du médecin
        $signatureB64 = null; $cachetB64 = null;
        $medecinNom   = 'Dr. ' . trim($row['user_prenom'] . ' ' . $row['user_nom']);
        $medecinSpec  = $row['specialite'] ?? '';
        $medecinOrdre = '';
        try {
            require_once __DIR__ . '/../services/SignatureService.php';
            $sigSvc  = new SignatureService();
            $sigRaw  = $sigSvc->getSignature((int)$row['user_id']);
            $sigFull = $sigSvc->getMedecinSignatureInfo((int)$row['user_id']);
            $signatureB64 = $sigRaw['signature_image'] ?? null;
            $cachetB64    = $sigRaw['cachet_image']    ?? null;
            $medecinSpec  = $sigFull['specialite']     ?? $medecinSpec;
            $medecinOrdre = $sigFull['numero_ordre']   ?? '';
        } catch (\Exception $e) {}

        require_once __DIR__ . '/../views/layouts/header.php';
        require_once __DIR__ . '/../views/formulaires/recap-gyneco.php';
        require_once __DIR__ . '/../views/layouts/footer.php';
    }

    /**
     * Créer un bulletin d'examens pour un patient
     * URL : formulaire/creer/bulletin-examens/{patient_id}?type=labo|imagerie&consultation_id=X
     */
    public function creerBulletinExamens($patient_id) {
        $db           = (new Database())->getConnection();
        $patientModel = new Patient();
        $patient      = $patientModel->getById($patient_id);

        if (!$patient) { die("Patient introuvable."); }

        $age             = date_diff(date_create($patient['date_naissance']), date_create('now'))->y;
        $type            = $_GET['type'] ?? 'labo';
        $consultation_id = !empty($_GET['consultation_id']) ? (int)$_GET['consultation_id'] : null;

        // ── Liaison rétroactive imagerie (sans limite de temps) ───────────────
        // La liaison dans ConsultationController est limitée à 4h ; ici on rattache
        // toutes les demandes du patient créées le même jour que la consultation.
        if ($consultation_id && $type === 'imagerie') {
            try {
                $db->prepare("
                    UPDATE demandes_imagerie
                    SET consultation_id = ?
                    WHERE patient_id = ?
                      AND consultation_id IS NULL
                      AND DATE(date_creation) = (
                          SELECT DATE(date_consultation) FROM consultations WHERE id = ? LIMIT 1
                      )
                ")->execute([$consultation_id, (int)$patient_id, $consultation_id]);
            } catch (\Exception $e) {
                error_log('[creerBulletinExamens] Liaison rétro imagerie: ' . $e->getMessage());
            }
        }

        $examens_demandes = [];

        if ($consultation_id) {
            try {
                if ($type === 'labo') {

                    // ── Stratégie 1 : via demande_examens (junction table) ──────────
                    $stmt = $db->prepare("
                        SELECT el.nom AS nom_examen, el.categorie,
                               COALESCE(de.urgent, 0)  AS urgent,
                               COALESCE(de.a_jeun, 0)  AS a_jeun,
                               de.instructions,
                               MIN(dl.date_creation)   AS date_creation
                        FROM demandes_laboratoire dl
                        JOIN demande_examens de ON de.demande_id = dl.id
                        JOIN examens_laboratoire el ON de.examen_id = el.id
                        WHERE dl.consultation_id = ?
                        GROUP BY el.nom, el.categorie, de.urgent, de.a_jeun, de.instructions
                        ORDER BY date_creation ASC, el.nom ASC
                    ");
                    $stmt->execute([$consultation_id]);
                    $examens_demandes = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    // ── Stratégie 2 : examen_id direct sur demandes_laboratoire ─────
                    // (cas où le module sauvegarde l'examen dans dl.examen_id sans
                    //  passer par demande_examens)
                    if (empty($examens_demandes)) {
                        $stmt2 = $db->prepare("
                            SELECT DISTINCT el.nom AS nom_examen, el.categorie,
                                   0 AS urgent, 0 AS a_jeun, NULL AS instructions
                            FROM demandes_laboratoire dl
                            JOIN examens_laboratoire el ON el.id = dl.examen_id
                            WHERE dl.consultation_id = ?
                            ORDER BY el.nom ASC
                        ");
                        $stmt2->execute([$consultation_id]);
                        $examens_demandes = $stmt2->fetchAll(PDO::FETCH_ASSOC);
                    }

                    // ── Stratégie 3 : bulletin déjà enregistré → lire examens JSON ──
                    if (empty($examens_demandes)) {
                        $stmt3 = $db->prepare("
                            SELECT examens FROM bulletins_examens
                            WHERE consultation_id = ? AND type = 'laboratoire'
                            ORDER BY date_creation DESC LIMIT 1
                        ");
                        $stmt3->execute([$consultation_id]);
                        $bul = $stmt3->fetch(PDO::FETCH_ASSOC);
                        if ($bul && !empty($bul['examens'])) {
                            $noms = json_decode($bul['examens'], true) ?: [];
                            foreach ($noms as $nom) {
                                $examens_demandes[] = [
                                    'nom_examen'  => $nom,
                                    'categorie'   => '',
                                    'urgent'      => 0,
                                    'a_jeun'      => 0,
                                    'instructions'=> null,
                                ];
                            }
                        }
                    }

                    // ── Stratégie 4 : fallback patient + date ───────────────────────
                    // (demandes sans consultation_id mais créées le même jour)
                    if (empty($examens_demandes)) {
                        $stmt4 = $db->prepare("
                            SELECT el.nom AS nom_examen, el.categorie,
                                   COALESCE(de.urgent, 0) AS urgent,
                                   COALESCE(de.a_jeun, 0) AS a_jeun,
                                   de.instructions,
                                   MIN(dl.date_creation) AS date_creation
                            FROM demandes_laboratoire dl
                            JOIN demande_examens de ON de.demande_id = dl.id
                            JOIN examens_laboratoire el ON de.examen_id = el.id
                            WHERE dl.patient_id = (
                                      SELECT patient_id FROM consultations WHERE id = ? LIMIT 1
                                  )
                              AND DATE(dl.date_creation) = (
                                      SELECT DATE(date_consultation) FROM consultations WHERE id = ? LIMIT 1
                                  )
                            GROUP BY el.nom, el.categorie, de.urgent, de.a_jeun, de.instructions
                            ORDER BY date_creation ASC, el.nom ASC
                        ");
                        $stmt4->execute([$consultation_id, $consultation_id]);
                        $examens_demandes = $stmt4->fetchAll(PDO::FETCH_ASSOC);
                    }

                } else {
                    // ── Imagerie ─────────────────────────────────────────────────────
                    // Requête simplifiée : colonnes brutes, assemblage en PHP
                    $stmtImg = $db->prepare("
                        SELECT id, consultation_id, patient_id,
                               type_examen, type_imagerie,
                               partie_corps, partie_code,
                               urgence, observations, description,
                               avec_contraste, date_creation
                        FROM demandes_imagerie
                        WHERE consultation_id = ?
                        ORDER BY date_creation ASC
                    ");
                    $stmtImg->execute([$consultation_id]);
                    $rawImg = $stmtImg->fetchAll(PDO::FETCH_ASSOC);

                    // Fallback 1 : même patient + même date que la consultation
                    if (empty($rawImg)) {
                        $stmtFb = $db->prepare("
                            SELECT di.id, di.consultation_id, di.patient_id,
                                   di.type_examen, di.type_imagerie,
                                   di.partie_corps, di.partie_code,
                                   di.urgence, di.observations, di.description,
                                   di.avec_contraste, di.date_creation
                            FROM demandes_imagerie di
                            WHERE di.patient_id = (
                                SELECT patient_id FROM consultations WHERE id = ? LIMIT 1
                            )
                            AND DATE(di.date_creation) = (
                                SELECT DATE(date_consultation) FROM consultations WHERE id = ? LIMIT 1
                            )
                            ORDER BY di.date_creation ASC
                        ");
                        $stmtFb->execute([$consultation_id, $consultation_id]);
                        $rawImg = $stmtFb->fetchAll(PDO::FETCH_ASSOC);
                    }

                    // Fallback 2 : examens non encore liés du même patient (les 30 derniers jours)
                    // Utile si la date de l'examen diffère de celle de la consultation
                    if (empty($rawImg)) {
                        $stmtFb2 = $db->prepare("
                            SELECT di.id, di.consultation_id, di.patient_id,
                                   di.type_examen, di.type_imagerie,
                                   di.partie_corps, di.partie_code,
                                   di.urgence, di.observations, di.description,
                                   di.avec_contraste, di.date_creation
                            FROM demandes_imagerie di
                            WHERE di.patient_id = ?
                              AND di.consultation_id IS NULL
                            ORDER BY di.date_creation DESC
                            LIMIT 20
                        ");
                        $stmtFb2->execute([(int)$patient_id]);
                        $rawImg = $stmtFb2->fetchAll(PDO::FETCH_ASSOC);
                    }

                    // Assemblage en PHP (évite les problèmes de CONCAT/COALESCE complexe)
                    foreach ($rawImg as $row) {
                        $typeVal  = ($row['type_examen'] && $row['type_examen'] !== 'autre')
                                    ? $row['type_examen']
                                    : ($row['type_imagerie'] ?? 'Imagerie');
                        $zoneVal  = (!empty(trim($row['partie_corps'] ?? '')))
                                    ? $row['partie_corps']
                                    : ($row['partie_code'] ?? 'Zone non précisée');
                        $nomExamen = ucfirst(strtolower($typeVal)) . ' — ' . $zoneVal;

                        $examens_demandes[] = [
                            'nom_examen'    => $nomExamen,
                            'urgence'       => $row['urgence'] ?? 'NORMAL',
                            'categorie'     => $row['description'] ?? $row['observations'] ?? '',
                            'avec_contraste'=> $row['avec_contraste'] ?? 0,
                            'modalite'      => strtolower($typeVal),
                        ];
                    }
                }
            } catch (Exception $e) {
                error_log('[creerBulletinExamens] Erreur imagerie: ' . $e->getMessage());
                // Affichage debug visible pendant développement
                $debug_error_imagerie = $e->getMessage();
            }
        }

        require_once __DIR__ . '/../views/formulaires/bulletin-examens.php';
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

        // Signature + cachet du médecin
        $sigService   = new SignatureService();
        $sigRaw       = $sigService->getSignature($_SESSION['user_id']); // base64 depuis medecin_signatures
        $sigFull      = $sigService->getMedecinSignatureInfo($_SESSION['user_id']);

        // Pour l'AFFICHAGE (preview) : URL ou base64
        $signatureSrc  = $sigFull['signature_src'] ?? null;
        $cachetSrc     = $sigFull['cachet_src']    ?? null;

        // Pour le FORMULAIRE (soumission) : toujours le base64 depuis la DB
        // (évite d'envoyer une URL que resizeBase64() ne sait pas traiter)
        $signatureB64  = $sigRaw['signature_image'] ?? null;  // data:image/png;base64,...
        $cachetB64     = $sigRaw['cachet_image']    ?? null;

        // Si base64 absent mais URL disponible (fallback : convertir l'URL en base64)
        if (empty($signatureB64) && !empty($signatureSrc)) {
            // Construire le chemin local à partir de l'URL (fonctionne que ce soit http:// ou relatif)
            $urlPath    = parse_url($signatureSrc, PHP_URL_PATH) ?: $signatureSrc;
            $localPath  = rtrim($_SERVER['DOCUMENT_ROOT'], '/\\') . '/' . ltrim($urlPath, '/');
            $content    = @file_get_contents($localPath);
            if ($content) $signatureB64 = 'data:image/png;base64,' . base64_encode($content);
        }
        if (empty($cachetB64) && !empty($cachetSrc)) {
            $urlPath   = parse_url($cachetSrc, PHP_URL_PATH) ?: $cachetSrc;
            $localPath = rtrim($_SERVER['DOCUMENT_ROOT'], '/\\') . '/' . ltrim($urlPath, '/');
            $content   = @file_get_contents($localPath);
            if ($content) $cachetB64 = 'data:image/png;base64,' . base64_encode($content);
        }

        $medecinNom   = trim(($_SESSION['user_prenom'] ?? '') . ' ' . ($_SESSION['user_nom'] ?? ''));
        $medecinSpec  = $sigFull['specialite']   ?? '';
        $medecinOrdre = $sigFull['numero_ordre'] ?? '';

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
        $rawHospId        = !empty($_POST['hospitalisation_id']) ? (int) $_POST['hospitalisation_id'] : null;

        // Vérifier que l'hospitalisation_id existe réellement (évite la violation de FK)
        $hosp_id = null;
        if ($rawHospId) {
            $chk = $db->prepare("SELECT id FROM hospitalisations WHERE id = ?");
            $chk->execute([$rawHospId]);
            $hosp_id = $chk->fetchColumn() ? $rawHospId : null;
        }
        // Si non trouvé par ID direct, chercher l'hospitalisation en cours du patient
        if (!$hosp_id) {
            $chk2 = $db->prepare("SELECT id FROM hospitalisations WHERE patient_id = ? AND statut = 'en_cours' ORDER BY id DESC LIMIT 1");
            $chk2->execute([$patient_id]);
            $hosp_id = $chk2->fetchColumn() ?: null;
        }
        $medecin_id       = (int) $_SESSION['user_id'];
        // Normalise DD/MM/YYYY ou DD-MM-YYYY → YYYY-MM-DD pour MySQL
        $toSqlDate = function(?string $d): ?string {
            if (empty($d)) return null;
            if (preg_match('#^(\d{2})[/\-](\d{2})[/\-](\d{4})$#', trim($d), $m)) {
                return $m[3] . '-' . $m[2] . '-' . $m[1];
            }
            return $d;
        };

        $date_entree      = $toSqlDate($_POST['date_entree']    ?? null);
        $diag_entree      = $_POST['diag_entree'] ?? '';
        $evolution        = $_POST['evolution'] ?? '';
        $date_sortie      = $toSqlDate($_POST['date_sortie']    ?? null);
        $diag_sortie      = $_POST['diag_sortie'] ?? '';
        $traitement_sortie = $_POST['traitement_sortie'] ?? '';
        $rendez_vous      = $_POST['rendez_vous'] ?? '';
        $date_signature   = $toSqlDate($_POST['date_signature'] ?? null) ?? date('Y-m-d');
        $signe            = 0;
        $signature_data   = null;
        $sigService       = null;   // initialisé ici, partagé par la closure ci-dessous

        // ── Helper : résoudre URL ou base64 → toujours base64 ──────────────────
        $resolveToBase64 = function(string $raw, string $dbField, int $uid) use (&$sigService): string {
            if (empty($raw)) return '';
            // Déjà une base64 valide
            if (str_starts_with($raw, 'data:')) return $raw;
            // C'est une URL → chercher la base64 en BDD d'abord
            $origUrl    = $raw;
            $sigService = $sigService ?? new SignatureService();
            $sigRawDb   = $sigService->getSignature($uid);
            $b64        = $sigRawDb[$dbField] ?? '';
            if (!empty($b64)) return $b64;
            // Fallback : lire le fichier sur disque
            $urlPath   = parse_url($origUrl, PHP_URL_PATH) ?: $origUrl;
            $localPath = rtrim($_SERVER['DOCUMENT_ROOT'], '/\\') . '/' . ltrim($urlPath, '/');
            $content   = @file_get_contents($localPath);
            return $content ? 'data:image/png;base64,' . base64_encode($content) : '';
        };

        // Signature
        $rawSig    = $resolveToBase64($_POST['signature_canvas'] ?? '', 'signature_image', $medecin_id);
        // Cachet
        $rawCachet = $resolveToBase64($_POST['cachet_data'] ?? '', 'cachet_image', $medecin_id);

        if (!empty($rawSig) && strlen($rawSig) > 100) {
            $sigService     = $sigService ?? new SignatureService();
            $signature_data = $sigService->resizeBase64($rawSig, 420, 160);
            $signe          = 1;
        }
        $cachet_data_val = (!empty($rawCachet) && strlen($rawCachet) > 100)
            ? (($sigService ?? new SignatureService())->resizeBase64($rawCachet, 220, 220))
            : null;

        $stmt = $db->prepare("
            INSERT INTO comptes_rendus_hosp
                (patient_id, hospitalisation_id, medecin_id, date_entree, diag_entree,
                 evolution, date_sortie, diag_sortie, traitement_sortie, rendez_vous,
                 date_signature, signe, signature_data, cachet_data)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $patient_id, $hosp_id, $medecin_id,
            $date_entree ?: null,
            $diag_entree, $evolution,
            $date_sortie ?: null,
            $diag_sortie, $traitement_sortie, $rendez_vous,
            $date_signature, $signe, $signature_data, $cachet_data_val,
        ]);

        $crh_id = $db->lastInsertId();

        // Enregistrer dans documents_signes si signé
        if ($signe) {
            $sigService = new SignatureService();
            $sigService->signDocument('RAPPORT', $crh_id, $medecin_id);
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

    /**
     * Sauvegarde du bulletin d'examens
     */
    public function sauvegarderBulletinExamens() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . 'dashboard');
            exit;
        }

        $db = (new Database())->getConnection();
        $patient_id = (int) $_POST['patient_id'];
        $consultation_id = !empty($_POST['consultation_id']) ? (int) $_POST['consultation_id'] : null;
        $type_bulletin = $_POST['type_bulletin'] ?? 'labo';

        // Récupérer les examens (filtrer les vides)
        $examens = array_filter($_POST['examens'] ?? [], function($e) {
            return !empty(trim($e));
        });

        // Données du bulletin
        $data = [
            'patient_id' => $patient_id,
            'consultation_id' => $consultation_id,
            'type_bulletin' => $type_bulletin,
            'examens' => json_encode($examens),
            'profession' => $_POST['profession'] ?? '',
            'service' => $_POST['service'] ?? '',
            'chambre' => $_POST['chambre'] ?? '',
            'lit' => $_POST['lit'] ?? '',
            'renseignements' => $_POST['renseignements'] ?? '',
            'resultats' => $_POST['resultats'] ?? '',
            'radio' => isset($_POST['radio']) ? 1 : 0,
            'echo' => isset($_POST['echo']) ? 1 : 0,
            'labo' => isset($_POST['labo']) ? 1 : 0,
            'medecin_id' => $_SESSION['user_id'] ?? 1,
            'date_creation' => date('Y-m-d H:i:s'),
            'statut' => 'BROUILLON'
        ];

        // Insérer dans bulletins_examens (table à créer si elle n'existe pas)
        try {
            $stmt = $db->prepare("
                INSERT INTO bulletins_examens
                (patient_id, consultation_id, type_bulletin, examens, profession, service, chambre, lit,
                 renseignements, resultats, radio, echo, labo, medecin_id, date_creation, statut)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute(array_values($data));
            $bulletin_id = $db->lastInsertId();

            // Rediriger vers la page d'impression/signature
            header('Location: ' . BASE_URL . 'formulaire/voir-bulletin-examens/' . $bulletin_id);
            exit;
        } catch (Exception $e) {
            // Si la table n'existe pas, créer un message d'erreur
            error_log('Erreur sauvegarde bulletin: ' . $e->getMessage());
            header('Location: ' . BASE_URL . 'patients/dossier/' . $patient_id . '?error=bulletin_save_failed');
            exit;
        }
    }

    /**
     * Affiche un bulletin d'examens existant (consultation + impression)
     */
    public function voirBulletinExamens($bulletin_id) {
        $db = (new Database())->getConnection();

        $stmt = $db->prepare("
            SELECT b.*, p.nom, p.prenom, p.date_naissance, p.sexe, p.dossier_numero,
                   u.nom as medecin_nom, u.prenom as medecin_prenom,
                   u.signature_path, u.cachet_path
            FROM bulletins_examens b
            JOIN patients p ON b.patient_id = p.id
            JOIN users u ON b.medecin_id = u.id
            WHERE b.id = ?
        ");
        $stmt->execute([$bulletin_id]);
        $bulletin = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$bulletin) die("Bulletin introuvable.");

        // Reconstruit $patient
        $patient = [
            'id'             => $bulletin['patient_id'],
            'nom'            => $bulletin['nom'],
            'prenom'         => $bulletin['prenom'],
            'date_naissance' => $bulletin['date_naissance'],
            'sexe'           => $bulletin['sexe'],
            'dossier_numero' => $bulletin['dossier_numero'],
        ];
        $age = date_diff(date_create($patient['date_naissance']), date_create('now'))->y;

        // Décoder les examens
        $examens = json_decode($bulletin['examens'] ?? '[]', true);

        // ── Fallback : si JSON vide, reconstruire depuis les demandes liées ──
        if (empty($examens) && !empty($bulletin['consultation_id'])) {
            try {
                $cid = (int)$bulletin['consultation_id'];

                // Essai 1 : via demande_examens
                $stmtFb = $db->prepare("
                    SELECT DISTINCT el.nom
                    FROM demandes_laboratoire dl
                    JOIN demande_examens de ON de.demande_id = dl.id
                    JOIN examens_laboratoire el ON de.examen_id = el.id
                    WHERE dl.consultation_id = ?
                    ORDER BY el.nom ASC
                ");
                $stmtFb->execute([$cid]);
                $rows = $stmtFb->fetchAll(PDO::FETCH_COLUMN);

                // Essai 2 : via dl.examen_id direct
                if (empty($rows)) {
                    $stmtFb2 = $db->prepare("
                        SELECT DISTINCT el.nom
                        FROM demandes_laboratoire dl
                        JOIN examens_laboratoire el ON el.id = dl.examen_id
                        WHERE dl.consultation_id = ?
                        ORDER BY el.nom ASC
                    ");
                    $stmtFb2->execute([$cid]);
                    $rows = $stmtFb2->fetchAll(PDO::FETCH_COLUMN);
                }

                if (!empty($rows)) {
                    $examens = $rows;
                    // Persister pour les prochaines ouvertures
                    $db->prepare("UPDATE bulletins_examens SET examens = ? WHERE id = ?")
                       ->execute([json_encode(array_values($rows)), (int)$bulletin['id']]);
                }
            } catch (Exception $e) {
                error_log('[voirBulletin] fallback examens: ' . $e->getMessage());
            }
        }

        // Si encore vide mais demande_id connu, charger via demande_id
        if (empty($examens) && !empty($bulletin['demande_id'])) {
            try {
                $stmtFb3 = $db->prepare("
                    SELECT DISTINCT el.nom
                    FROM demande_examens de
                    JOIN examens_laboratoire el ON de.examen_id = el.id
                    WHERE de.demande_id = ?
                    ORDER BY el.nom ASC
                ");
                $stmtFb3->execute([(int)$bulletin['demande_id']]);
                $rows3 = $stmtFb3->fetchAll(PDO::FETCH_COLUMN);
                if (!empty($rows3)) {
                    $examens = $rows3;
                    $db->prepare("UPDATE bulletins_examens SET examens = ? WHERE id = ?")
                       ->execute([json_encode(array_values($rows3)), (int)$bulletin['id']]);
                }
            } catch (Exception $e) {}
        }

        // Si $examens est un tableau de chaînes (noms seuls), le laisser tel quel
        // La vue voir-bulletin-examens.php attend soit un array de strings
        // soit un array de ['nom_examen' => ...] — on normalise en strings ici
        if (!empty($examens) && is_array($examens[0])) {
            $examens = array_column($examens, 'nom_examen');
        }

        // Si bulletin auto-créé avec demande_id, charger les résultats live
        $resultats_live = [];
        if (!empty($bulletin['demande_id'])) {
            try {
                require_once __DIR__ . '/../services/LaboratoireService.php';
                $laboSvc = new LaboratoireService();
                $raw = $laboSvc->getExamensParDemande((int)$bulletin['demande_id']);
                $resultats_live = array_map(fn($e) => [
                    'nom_examen'         => $e['nom'] ?? $e['nom_examen'] ?? '',
                    'categorie'          => $e['categorie'] ?? 'AUTRE',
                    'resultat'           => $e['resultat'] ?? '',
                    'valeur_numerique'   => $e['valeur_numerique'] ?? null,
                    'unite'              => $e['unite'] ?? '',
                    'valeur_normale_min' => $e['valeur_normale_min'] ?? null,
                    'valeur_normale_max' => $e['valeur_normale_max'] ?? null,
                    'interpretation'     => $e['interpretation'] ?? '',
                    'anormal'            => (bool)($e['anormal'] ?? false),
                ], $raw);
            } catch (Exception $e) {
                error_log('[voirBulletin] ' . $e->getMessage());
            }
        }

        require_once __DIR__ . '/../views/formulaires/voir-bulletin-examens.php';
    }

    public function signerBulletin(int $bulletin_id): void {
        header('Content-Type: application/json');
        try {
            $db = (new Database())->getConnection();

            // Ajouter colonnes signature_path/cachet_path si absentes
            foreach (['signature_path VARCHAR(500) NULL', 'cachet_path VARCHAR(500) NULL'] as $col) {
                $name = explode(' ', $col)[0];
                $chk = $db->query("SHOW COLUMNS FROM bulletins_examens LIKE '$name'")->rowCount();
                if (!$chk) $db->exec("ALTER TABLE bulletins_examens ADD COLUMN $col");
            }

            $stmt = $db->prepare("SELECT b.id, b.signe, b.medecin_id, u.signature_path, u.cachet_path
                FROM bulletins_examens b JOIN users u ON b.medecin_id = u.id WHERE b.id = ?");
            $stmt->execute([$bulletin_id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$row) { echo json_encode(['success' => false, 'message' => 'Bulletin introuvable']); return; }
            if ($row['signe']) { echo json_encode(['success' => false, 'message' => 'Bulletin déjà signé']); return; }

            $db->prepare("UPDATE bulletins_examens
                SET signe = 1, date_signature = NOW(), statut = 'SIGNE',
                    signature_path = ?, cachet_path = ?
                WHERE id = ?")
               ->execute([$row['signature_path'], $row['cachet_path'], $bulletin_id]);

            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /* ══════════════════════════════════════════════════════════════════
     * Catalogue des formulaires accessibles aux infirmiers
     * (exclut : bulletin-examens, compte-rendu-hosp, voir-*)
     * ══════════════════════════════════════════════════════════════════ */
    public static function catalogue(): array {
        return [
            ['slug' => 'consentement',          'titre' => 'Consentement éclairé',           'icon' => 'bi-pen',              'color' => '#6366f1'],
            ['slug' => 'fiche-reference',        'titre' => 'Fiche de référence',             'icon' => 'bi-arrow-up-right-circle','color' => '#0d9488'],
            ['slug' => 'prise-en-charge',        'titre' => 'Prise en charge',                'icon' => 'bi-shield-check',     'color' => '#2563eb'],
            ['slug' => 'sortie-contre-avis',     'titre' => 'Sortie contre avis médical',     'icon' => 'bi-door-open',        'color' => '#dc2626'],
            ['slug' => 'certificat-hospitalisation','titre'=>'Certificat d\'hospitalisation', 'icon' => 'bi-file-earmark-medical','color' => '#7c3aed'],
            ['slug' => 'checklist-bloc',         'titre' => 'Checklist bloc opératoire',      'icon' => 'bi-list-check',       'color' => '#0891b2'],
            ['slug' => 'certificat-grossesse',   'titre' => 'Certificat de grossesse',        'icon' => 'bi-heart-pulse',      'color' => '#db2777'],
            ['slug' => 'certificat-accouchement','titre' => 'Certificat d\'accouchement',     'icon' => 'bi-hospital',         'color' => '#059669'],
            ['slug' => 'echo-obstetricale',      'titre' => 'Écho obstétricale',              'icon' => 'bi-activity',         'color' => '#7c3aed'],
            ['slug' => 'echo-obstetricale-t2-t3','titre' => 'Écho obstétricale T2/T3',        'icon' => 'bi-activity',         'color' => '#7c3aed'],
            ['slug' => 'echo-pelvienne',         'titre' => 'Écho pelvienne',                 'icon' => 'bi-activity',         'color' => '#9333ea'],
            ['slug' => 'histo-pathologie',       'titre' => 'Histo-pathologie',               'icon' => 'bi-eyedropper',       'color' => '#b45309'],
            ['slug' => 'ligature-trompes',       'titre' => 'Ligature des trompes',           'icon' => 'bi-clipboard2-pulse', 'color' => '#be185d'],
            ['slug' => 'parametres-nouveau-ne',  'titre' => 'Paramètres nouveau-né',          'icon' => 'bi-person-fill',      'color' => '#0369a1'],
            ['slug' => 'traitement-arv-nn',      'titre' => 'Traitement ARV NN',              'icon' => 'bi-capsule',          'color' => '#0f766e'],
            ['slug' => 'partogramme',             'titre' => 'Partogramme',                    'icon' => 'bi-graph-up-arrow',   'color' => '#be123c'],
            ['slug' => 'consultation-gyneco',        'titre' => 'Consultation gynécologique',        'icon' => 'bi-heart-pulse-fill',   'color' => '#db2777'],
            ['slug' => 'fiche-suivi-cpn',           'titre' => 'Fiche suivi femme enceinte (CPN)',  'icon' => 'bi-table',              'color' => '#0d9488'],
            ['slug' => 'fiche-postpartum-immediat', 'titre' => 'Surveillance post partum immédiat', 'icon' => 'bi-clock-history',      'color' => '#7c3aed'],
            ['slug' => 'certificat-genre-mort',     'titre' => 'Certificat de genre de mort',       'icon' => 'bi-file-earmark-text',  'color' => '#475569'],
            ['slug' => 'perfucode',                 'titre' => 'Perfucode',                         'icon' => 'bi-droplet-fill',       'color' => '#0ea5e9'],
            ['slug' => 'info-mere-travail-enfant',  'titre' => 'Info mère, travail et enfant',      'icon' => 'bi-person-heart',       'color' => '#f59e0b'],
            ['slug' => 'consentement-peridurale',   'titre' => 'Consentement péridurale',           'icon' => 'bi-file-earmark-check', 'color' => '#8b5cf6'],
        ];
    }

    /* ══════════════════════════════════════════════════════════════════
     * Ouvre un formulaire infirmier avec contexte patient + bouton
     * "Soumettre au médecin"
     * URL : formulaire/creer/{slug}/{patient_id}?hosp_id=X
     * ══════════════════════════════════════════════════════════════════ */
    public function creer(string $slug, $patient_id): void {
        $db           = (new Database())->getConnection();
        $patientModel = new Patient();
        $patient      = $patientModel->getById((int)$patient_id);
        if (!$patient) { die("Patient introuvable."); }

        // Âge du patient (utilisé par plusieurs vues de formulaires)
        $age = !empty($patient['date_naissance'])
            ? date_diff(date_create($patient['date_naissance']), date_create('now'))->y
            : 0;

        $hosp_id    = (int)($_GET['hosp_id'] ?? 0);
        $from_suivi = $hosp_id > 0 || isset($_GET['from_suivi']);

        // Récupérer médecin responsable de l'hospitalisation
        $medecin_id = null;
        if ($hosp_id) {
            try {
                $stmtH = $db->prepare("SELECT medecin_responsable, service_id FROM hospitalisations WHERE id = ? LIMIT 1");
                $stmtH->execute([$hosp_id]);
                $hosp = $stmtH->fetch(PDO::FETCH_ASSOC);
                if ($hosp && is_numeric($hosp['medecin_responsable'])) {
                    $medecin_id = (int)$hosp['medecin_responsable'];
                }
                if (!$medecin_id) {
                    // Fallback : médecin du service
                    $stmtM = $db->prepare("SELECT id FROM users WHERE service_id = ? AND role IN ('MEDECIN','CHIRURGIEN','GENERALISTE','ANESTHESISTE') LIMIT 1");
                    $stmtM->execute([$hosp['service_id'] ?? 0]);
                    $medecin_id = $stmtM->fetchColumn() ?: null;
                }
            } catch (\Exception $e) {}
        }

        // Récupérer le nom complet du médecin responsable (pour les champs "Médecin traitant")
        $medecin_nom = '';
        if ($medecin_id) {
            try {
                $stmtMN = $db->prepare("SELECT CONCAT('Dr. ', prenom, ' ', nom) FROM users WHERE id = ? LIMIT 1");
                $stmtMN->execute([$medecin_id]);
                $medecin_nom = $stmtMN->fetchColumn() ?: '';
            } catch (\Exception $e) {}
        }
        // Fallback : si l'utilisateur actuel est médecin, utiliser son nom
        $rolesmedecins = ['MEDECIN','CHIRURGIEN','GENERALISTE','ANESTHESISTE','GYNECO','PEDIATRE','SAGE_FEMME'];
        if (!$medecin_nom && in_array($_SESSION['role'] ?? '', $rolesmedecins)) {
            $medecin_nom = 'Dr. ' . trim(($_SESSION['user_prenom'] ?? '') . ' ' . ($_SESSION['user_nom'] ?? ''));
        }

        // ── Rôle de l'utilisateur connecté ──────────────────────────────────
        $current_user_role       = $_SESSION['role'] ?? '';
        $current_user_id         = (int)($_SESSION['user_id'] ?? 0);
        $current_user_is_medecin = in_array($current_user_role, $rolesmedecins);

        // ── Liste des médecins du service (pour select si non-médecin) ───────
        $medecins_service = [];
        try {
            $svc = (int)($_SESSION['service_id'] ?? 0);
            // Cherche d'abord dans le service, puis tous si vide
            $sqlMeds = "SELECT id,
                               CONCAT('Dr. ', prenom, ' ', nom) AS nom_complet,
                               role, specialite
                        FROM users
                        WHERE role IN ('" . implode("','", $rolesmedecins) . "')
                        " . ($svc ? "AND (service_id = $svc OR service_id IS NULL)" : "") . "
                        ORDER BY nom, prenom";
            $stmtMeds = $db->query($sqlMeds);
            $medecins_service = $stmtMeds->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Exception $e) { $medecins_service = []; }

        // Migration auto table formulaires_soumis
        try {
            $db->exec("CREATE TABLE IF NOT EXISTS formulaires_soumis (
                id INT AUTO_INCREMENT PRIMARY KEY,
                patient_id INT NOT NULL, hosp_id INT DEFAULT NULL,
                infirmier_id INT NOT NULL, medecin_id INT DEFAULT NULL,
                service_id INT DEFAULT NULL,
                type_formulaire VARCHAR(60) NOT NULL, titre VARCHAR(120) NOT NULL,
                statut ENUM('SOUMIS','VU','SIGNE','REFUSE') DEFAULT 'SOUMIS',
                date_soumission DATETIME DEFAULT CURRENT_TIMESTAMP,
                date_action DATETIME DEFAULT NULL, note_medecin TEXT DEFAULT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        } catch (\Exception $e) {}

        // Fichier de vue : chercher d'abord la version moderne, puis l'originale
        $viewFile = __DIR__ . '/../views/formulaires/modern/' . $slug . '.php';
        if (!file_exists($viewFile)) {
            $viewFile = __DIR__ . '/../views/formulaires/' . $slug . '.php';
        }
        if (!file_exists($viewFile)) {
            header('Location: ' . BASE_URL . 'hospitalisation/suivi/' . $patient_id . '?error=formulaire_introuvable');
            exit;
        }

        // Pour consultation-gyneco : pré-remplir $formData depuis SESSION (comme le formulaire standard)
        $formData = [];
        if ($slug === 'consultation-gyneco') {
            $formData = $_SESSION['consultation_gyneco_temp'] ?? [];
            // Si SESSION vide, essayer de charger un brouillon existant depuis formulaires_data
            if (empty($formData)) {
                try {
                    $stmtDraft = $db->prepare(
                        "SELECT data FROM formulaires_data
                         WHERE patient_id=? AND type_formulaire='consultation-gyneco'
                         AND user_id=? AND statut='BROUILLON'
                         ORDER BY id DESC LIMIT 1"
                    );
                    $stmtDraft->execute([(int)$patient['id'], (int)($_SESSION['user_id'] ?? 0)]);
                    $draftRaw = $stmtDraft->fetchColumn();
                    if ($draftRaw) $formData = json_decode($draftRaw, true) ?? [];
                } catch (\Exception $eDraft) {}
            }
        }

        require_once $viewFile;

        /* ── Injection : intercepteur de sauvegarde JSON + UX moderne ──────── */
        ?>
<style>
/* ── Barre de progression globale ── */
#_fd_progress {
    position: fixed; top: 0; left: 0; right: 0; height: 3px; z-index: 99998;
    background: #e9ecef;
}
#_fd_progress_bar {
    height: 100%; width: 0; transition: width .4s ease;
    background: linear-gradient(90deg, #0d9488, #0d6efd);
}
/* ── Badge statut (en haut à droite, sous les toasts) ── */
#_fd_status {
    position: fixed; top: 16px; left: 50%; transform: translateX(-50%);
    z-index: 99997; font-size: .8rem; font-weight: 600;
    padding: 4px 14px; border-radius: 20px; display: none;
    background: #fff3cd; color: #856404; border: 1px solid #ffc107;
    box-shadow: 0 2px 8px rgba(0,0,0,.12);
}
</style>
<div id="_fd_progress"><div id="_fd_progress_bar"></div></div>
<div id="_fd_status"><i class="bi bi-pencil-fill me-1"></i> Brouillon non enregistré</div>
<script>
(function(){
    const BASE = <?= json_encode(BASE_URL) ?>;
    const SLUG = <?= json_encode($slug) ?>;
    const PID  = <?= (int)$patient['id'] ?>;
    const HID  = <?= (int)$hosp_id ?> || null;

    /* ── toast ─────────────────────────────────────────────── */
    function toast(msg, type){
        let box = document.getElementById('_fd_toast');
        if(!box){
            box = document.createElement('div');
            box.id = '_fd_toast';
            box.style.cssText='position:fixed;top:18px;right:18px;z-index:99999;display:flex;flex-direction:column;gap:8px;';
            document.body.appendChild(box);
        }
        const t = document.createElement('div');
        t.className = 'alert alert-'+(type||'info')+' shadow py-2 px-3 mb-0 d-flex align-items-center gap-2';
        t.style.cssText='min-width:260px;animation:slideIn .25s ease;';
        t.innerHTML='<i class="bi bi-'+(type==='success'?'check-circle-fill':type==='danger'?'exclamation-triangle-fill':'info-circle-fill')+'"></i>'+msg;
        box.appendChild(t);
        setTimeout(()=>{ t.style.opacity='0'; t.style.transition='opacity .3s'; setTimeout(()=>t.remove(),350); }, 4000);
    }

    /* ── sérialise un <form> en objet plat ──────────────────── */
    function serializeForm(form){
        const data = {};
        const fd = new FormData(form);
        for(let [k,v] of fd.entries()){
            const key = k.replace(/\[\]$/,'');
            if(data[key]!==undefined){
                if(!Array.isArray(data[key])) data[key]=[data[key]];
                data[key].push(v);
            } else { data[key]=v; }
        }
        form.querySelectorAll('input[type=checkbox]').forEach(cb=>{
            const k=cb.name.replace(/\[\]$/,'');
            if(k && !(k in data)) data[k]='0';
        });
        return data;
    }

    /* ── progression du formulaire ──────────────────────────── */
    function updateProgress(form){
        const inputs = [...form.querySelectorAll('input:not([type=hidden]):not([readonly]), textarea, select')];
        if(!inputs.length) return;
        const filled = inputs.filter(el => el.value && el.value.trim() !== '' && el.value !== '0').length;
        const pct = Math.round((filled / inputs.length) * 100);
        document.getElementById('_fd_progress_bar').style.width = pct + '%';
    }

    /* ── statut brouillon ────────────────────────────────────── */
    function setUnsaved(){
        const s = document.getElementById('_fd_status');
        if(s){ s.style.display='block'; }
    }
    function setSaved(){
        const s = document.getElementById('_fd_status');
        if(s){ s.innerHTML='<i class="bi bi-check-circle-fill me-1 text-success"></i> Enregistré'; s.style.background='#d1e7dd'; s.style.color='#0a3622'; s.style.borderColor='#a3cfbb'; }
    }

    /* ── envoi vers sauvegarder-generique ───────────────────── */
    async function saveForm(form, btn, statut='SOUMIS'){
        const orig = btn ? btn.innerHTML : '';
        if(btn){ btn.disabled=true; btn.innerHTML='<span class="spinner-border spinner-border-sm me-1"></span> Enregistrement…'; }
        try {
            const resp = await fetch(BASE+'formulaire/sauvegarder-generique',{
                method:'POST',
                headers:{'Content-Type':'application/json','X-Requested-With':'XMLHttpRequest'},
                body: JSON.stringify({slug:SLUG, patient_id:PID, hosp_id:HID, data:serializeForm(form), statut})
            });
            const r = await resp.json();
            if(r.success){
                setSaved();
                if(statut==='SOUMIS'){
                    toast('Formulaire enregistré ! Ouverture de l\'aperçu imprimable…','success');
                    setTimeout(()=>window.open(r.print_url,'_blank'), 700);
                    if(btn){ btn.disabled=false; btn.innerHTML='<i class="bi bi-check-circle-fill me-1"></i> Enregistré'; }
                } else {
                    // Brouillon silencieux
                    if(btn){ btn.disabled=false; btn.innerHTML=orig; }
                }
            } else {
                toast(r.message||'Erreur d\'enregistrement','danger');
                if(btn){ btn.disabled=false; btn.innerHTML=orig; }
            }
        } catch(err){
            toast('Erreur réseau : '+err.message,'danger');
            if(btn){ btn.disabled=false; btn.innerHTML=orig; }
        }
    }

    /* ── auto-save brouillon toutes les 90s ─────────────────── */
    let autoSaveTimer = null;
    function scheduleAutoSave(form){
        clearTimeout(autoSaveTimer);
        autoSaveTimer = setTimeout(()=>{ saveForm(form, null, 'BROUILLON'); }, 90000);
    }

    /* ── init ────────────────────────────────────────────────── */
    document.addEventListener('DOMContentLoaded',()=>{
        const forms = [...document.querySelectorAll('form')].filter(f=>f.id && f.id.startsWith('form'));
        forms.forEach(form=>{
            // Progress initial
            updateProgress(form);

            // Suivi des modifications
            form.addEventListener('input', ()=>{ updateProgress(form); setUnsaved(); scheduleAutoSave(form); });
            form.addEventListener('change', ()=>{ updateProgress(form); setUnsaved(); scheduleAutoSave(form); });

            // Soumission → sauvegarde JSON
            form.addEventListener('submit', e=>{
                e.preventDefault();
                const btn = document.querySelector('[type=submit][form="'+form.id+'"]')
                          || form.querySelector('[type=submit]');
                saveForm(form, btn, 'SOUMIS');
            });
        });
    });
})();
</script>
<?php
        // Injecter le FAB "Soumettre au médecin" via JS si contexte suivi
        if ($from_suivi): ?>
<style>
#fab-soumettre {
    position: fixed; bottom: 28px; right: 28px; z-index: 9999;
    background: linear-gradient(135deg,#0f766e,#0d9488);
    color: #fff; border: none; border-radius: 50px;
    padding: 13px 24px; font-size: .92rem; font-weight: 700;
    box-shadow: 0 6px 24px rgba(13,148,136,.45);
    cursor: pointer; display: flex; align-items: center; gap: 10px;
    transition: transform .15s, box-shadow .15s;
}
#fab-soumettre:hover { transform: translateY(-2px); box-shadow: 0 10px 32px rgba(13,148,136,.55); }
#fab-soumettre:active { transform: scale(.97); }
</style>
<button id="fab-soumettre" onclick="soumettreAuMedecin()">
    <i class="bi bi-send-fill"></i> Soumettre au médecin
</button>
<script>
function soumettreAuMedecin() {
    const btn = document.getElementById('fab-soumettre');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Envoi…';
    fetch('<?= BASE_URL ?>formulaire/soumettre-medecin', {
        method : 'POST',
        headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        body   : JSON.stringify({
            patient_id : <?= (int)$patient['id'] ?>,
            hosp_id    : <?= (int)$hosp_id ?>,
            medecin_id : <?= (int)($medecin_id ?? 0) ?>,
            slug       : <?= json_encode($slug) ?>,
            titre      : <?= json_encode(self::titreDuSlug($slug)) ?>
        })
    })
    .then(r => r.json())
    .then(d => {
        if (d.success) {
            btn.style.background = 'linear-gradient(135deg,#059669,#10b981)';
            btn.innerHTML = '<i class="bi bi-check-circle-fill me-1"></i> Transmis au médecin !';
            setTimeout(() => {
                window.location.href = '<?= BASE_URL ?>hospitalisation/suivi/' + <?= (int)$patient_id ?>;
            }, 1800);
        } else {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-send-fill"></i> Soumettre au médecin';
            alert(d.message || 'Erreur lors de la transmission.');
        }
    })
    .catch(() => {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-send-fill"></i> Soumettre au médecin';
        alert('Erreur réseau.');
    });
}
</script>
<?php endif;
    }

    private static function titreDuSlug(string $slug): string {
        foreach (self::catalogue() as $f) {
            if ($f['slug'] === $slug) return $f['titre'];
        }
        return ucfirst(str_replace('-', ' ', $slug));
    }

    /* ══════════════════════════════════════════════════════════════════
     * AJAX — Enregistre la soumission + notifie le médecin
     * POST formulaire/soumettre-medecin
     * ══════════════════════════════════════════════════════════════════ */
    public function soumettreMedecin(): void {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']); return;
        }

        $input      = json_decode(file_get_contents('php://input'), true) ?? [];
        $patient_id = (int)($input['patient_id'] ?? 0);
        $hosp_id    = (int)($input['hosp_id']    ?? 0) ?: null;
        $medecin_id = (int)($input['medecin_id'] ?? 0) ?: null;
        $slug       = trim($input['slug']  ?? '');
        $titre      = trim($input['titre'] ?? self::titreDuSlug($slug));
        $inf_id     = (int)($_SESSION['user_id']   ?? 0);
        $service_id = (int)($_SESSION['service_id'] ?? 0);

        if (!$patient_id || !$slug) {
            echo json_encode(['success' => false, 'message' => 'Données incomplètes']); return;
        }

        try {
            $db = (new Database())->getConnection();

            // Migration auto
            $db->exec("CREATE TABLE IF NOT EXISTS formulaires_soumis (
                id INT AUTO_INCREMENT PRIMARY KEY,
                patient_id INT NOT NULL, hosp_id INT DEFAULT NULL,
                infirmier_id INT NOT NULL, medecin_id INT DEFAULT NULL,
                service_id INT DEFAULT NULL,
                type_formulaire VARCHAR(60) NOT NULL, titre VARCHAR(120) NOT NULL,
                statut ENUM('SOUMIS','VU','SIGNE','REFUSE') DEFAULT 'SOUMIS',
                date_soumission DATETIME DEFAULT CURRENT_TIMESTAMP,
                date_action DATETIME DEFAULT NULL, note_medecin TEXT DEFAULT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

            // Enregistrer la soumission
            $stmt = $db->prepare("
                INSERT INTO formulaires_soumis
                    (patient_id, hosp_id, infirmier_id, medecin_id, service_id, type_formulaire, titre)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$patient_id, $hosp_id, $inf_id, $medecin_id, $service_id, $slug, $titre]);

            // Récupérer nom du patient
            $stmtP = $db->prepare("SELECT nom, prenom FROM patients WHERE id = ?");
            $stmtP->execute([$patient_id]);
            $pat = $stmtP->fetch(PDO::FETCH_ASSOC);
            $nomPat = ($pat['nom'] ?? '') . ' ' . ($pat['prenom'] ?? '');

            // Récupérer nom de l'infirmier
            $stmtI = $db->prepare("SELECT nom, prenom FROM users WHERE id = ?");
            $stmtI->execute([$inf_id]);
            $inf = $stmtI->fetch(PDO::FETCH_ASSOC);
            $nomInf = ($inf['nom'] ?? '') . ' ' . ($inf['prenom'] ?? '');

            // Notifier le médecin si connu
            if ($medecin_id) {
                $db->prepare("
                    INSERT INTO notifications
                        (user_id, type, category, title, message, link, icon, priority)
                    VALUES (?, 'info', 'FORMULAIRE', ?, ?, ?, 'bi-file-earmark-check-fill', 'normal')
                ")->execute([
                    $medecin_id,
                    "Formulaire à signer : $titre",
                    "L'infirmier(e) $nomInf a soumis le formulaire « $titre » pour le patient $nomPat.",
                    "hospitalisation/formulaires-a-signer"
                ]);
            } else {
                // Notifier tous les médecins du service
                // Notifier tous les médecins du service (ou de l'hôpital si service_id=0)
                $sqlNotif = $service_id
                    ? "SELECT id FROM users WHERE service_id = ? AND role IN ('MEDECIN','CHIRURGIEN','GENERALISTE','ANESTHESISTE','GYNECO','PEDIATRE','SAGE_FEMME')"
                    : "SELECT id FROM users WHERE role IN ('MEDECIN','CHIRURGIEN','GENERALISTE','ANESTHESISTE','GYNECO','PEDIATRE','SAGE_FEMME')";
                $stmtMeds = $db->prepare($sqlNotif);
                $stmtMeds->execute($service_id ? [$service_id] : []);
                foreach ($stmtMeds->fetchAll(PDO::FETCH_COLUMN) as $mid) {
                    $db->prepare("
                        INSERT INTO notifications
                            (user_id, type, category, title, message, link, icon, priority)
                        VALUES (?, 'info', 'FORMULAIRE', ?, ?, ?, 'bi-file-earmark-check-fill', 'normal')
                    ")->execute([
                        $mid,
                        "Formulaire à signer : $titre",
                        "L'infirmier(e) $nomInf a soumis le formulaire « $titre » pour le patient $nomPat.",
                        "hospitalisation/formulaires-a-signer"
                    ]);
                }
            }

            echo json_encode(['success' => true, 'message' => 'Formulaire transmis au médecin.']);
        } catch (\PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Erreur BDD : ' . $e->getMessage()]);
        }
    }

    /* ══════════════════════════════════════════════════════════════════
     * Page : liste des formulaires à signer (médecin)
     * GET hospitalisation/formulaires-a-signer
     * ══════════════════════════════════════════════════════════════════ */
    public function formulairesASigner(): void {
        $db         = (new Database())->getConnection();
        $medecin_id = (int)($_SESSION['user_id'] ?? 0);
        $service_id = (int)($_SESSION['service_id'] ?? 0);

        try {
            $db->exec("CREATE TABLE IF NOT EXISTS formulaires_soumis (
                id INT AUTO_INCREMENT PRIMARY KEY,
                patient_id INT NOT NULL, hosp_id INT DEFAULT NULL,
                infirmier_id INT NOT NULL, medecin_id INT DEFAULT NULL,
                service_id INT DEFAULT NULL,
                type_formulaire VARCHAR(60) NOT NULL, titre VARCHAR(120) NOT NULL,
                statut ENUM('SOUMIS','VU','SIGNE','REFUSE') DEFAULT 'SOUMIS',
                date_soumission DATETIME DEFAULT CURRENT_TIMESTAMP,
                date_action DATETIME DEFAULT NULL, note_medecin TEXT DEFAULT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

            $stmt = $db->prepare("
                SELECT fs.*,
                       p.nom  AS patient_nom, p.prenom AS patient_prenom, p.dossier_numero,
                       u.nom  AS infirmier_nom, u.prenom AS infirmier_prenom,
                       s.nom_service,
                       /* Récupère l'ID du formulaire_data le plus récent pour afficher le bon doc */
                       (SELECT fd2.id
                        FROM formulaires_data fd2
                        WHERE fd2.patient_id      = fs.patient_id
                          AND fd2.type_formulaire  = fs.type_formulaire
                        ORDER BY fd2.id DESC
                        LIMIT 1
                       ) AS formulaire_data_id
                FROM formulaires_soumis fs
                JOIN patients p ON fs.patient_id    = p.id
                JOIN users u    ON fs.infirmier_id  = u.id
                LEFT JOIN services s ON fs.service_id = s.id
                WHERE fs.statut IN ('SOUMIS','VU')
                  AND (
                      fs.medecin_id = ?               /* assigné spécifiquement à ce médecin */
                      OR fs.medecin_id IS NULL         /* non assigné → visible par tous les médecins */
                      OR fs.service_id = ?             /* même service */
                      OR fs.service_id IS NULL         /* service non précisé → tous les médecins */
                      OR fs.service_id = 0
                  )
                ORDER BY fs.date_soumission DESC
                LIMIT 100
            ");
            $stmt->execute([$medecin_id, $service_id]);
            $formulaires = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Marquer comme VU (ceux que ce médecin peut voir)
            $db->prepare("UPDATE formulaires_soumis SET statut = 'VU'
                          WHERE statut = 'SOUMIS'
                            AND (medecin_id = ? OR medecin_id IS NULL OR service_id = ? OR service_id IS NULL OR service_id = 0)")
               ->execute([$medecin_id, $service_id]);

        } catch (\PDOException $e) {
            $formulaires = [];
        }

        require_once __DIR__ . '/../views/formulaires/liste-a-signer.php';
    }

    /* ══════════════════════════════════════════════════════════════════
     * GET formulaire/apercu-signer/{fs_id}
     * Page de visualisation + signature électronique pour le médecin
     * ══════════════════════════════════════════════════════════════════ */
    public function apercuSigner(int $fs_id): void {
        $db = (new Database())->getConnection();

        // Charger la soumission + patient + infirmier
        $stmt = $db->prepare("
            SELECT fs.*,
                   p.nom AS pat_nom, p.prenom AS pat_prenom,
                   p.date_naissance, p.dossier_numero,
                   u.nom AS inf_nom, u.prenom AS inf_prenom
            FROM formulaires_soumis fs
            JOIN patients p ON fs.patient_id = p.id
            JOIN users   u ON fs.infirmier_id = u.id
            WHERE fs.id = ?
        ");
        $stmt->execute([$fs_id]);
        $soumission = $stmt->fetch(\PDO::FETCH_ASSOC);
        if (!$soumission) { http_response_code(404); die("Soumission introuvable."); }

        $slug = $soumission['type_formulaire'];

        // Trouver le formulaire_data le plus récent pour ce patient + type
        $stmtD = $db->prepare("
            SELECT id FROM formulaires_data
            WHERE patient_id = ? AND type_formulaire = ?
            ORDER BY id DESC LIMIT 1
        ");
        $stmtD->execute([$soumission['patient_id'], $slug]);
        $formulaire_data_id = (int)($stmtD->fetchColumn() ?: 0);

        // Infos médecin connecté
        $medecin_id  = (int)($_SESSION['user_id'] ?? 0);
        $stmtM = $db->prepare("SELECT nom, prenom, specialite, role FROM users WHERE id = ?");
        $stmtM->execute([$medecin_id]);
        $medecin = $stmtM->fetch(\PDO::FETCH_ASSOC) ?: [];

        require_once __DIR__ . '/../views/formulaires/apercu-signer.php';
    }

    /* ══════════════════════════════════════════════════════════════════
     * AJAX — Marquer un formulaire comme signé ou refusé
     * POST formulaire/action-signer
     * ══════════════════════════════════════════════════════════════════ */
    public function actionSigner(): void {
        header('Content-Type: application/json');
        $input              = json_decode(file_get_contents('php://input'), true) ?? [];
        $id                 = (int)($input['id']                 ?? 0);
        $action             = $input['action']                   ?? ''; // 'signer' | 'refuser'
        $note               = trim($input['note']                ?? '');
        $signature          = $input['signature']                ?? ''; // base64 PNG
        $formulaire_data_id = (int)($input['formulaire_data_id'] ?? 0);

        if (!$id || !in_array($action, ['signer','refuser'])) {
            echo json_encode(['success' => false, 'message' => 'Données invalides']); return;
        }
        try {
            $db     = (new Database())->getConnection();
            $statut = $action === 'signer' ? 'SIGNE' : 'REFUSE';
            $db->prepare("UPDATE formulaires_soumis SET statut=?, date_action=NOW(), note_medecin=? WHERE id=?")
               ->execute([$statut, $note ?: null, $id]);

            // Sauvegarder la signature électronique dans formulaires_data
            if ($action === 'signer' && $formulaire_data_id
                && $signature && str_starts_with($signature, 'data:image')) {
                $medecin_id = (int)($_SESSION['user_id'] ?? 0);
                $stmtM = $db->prepare("SELECT nom, prenom, specialite FROM users WHERE id = ?");
                $stmtM->execute([$medecin_id]);
                $med    = $stmtM->fetch(\PDO::FETCH_ASSOC) ?: [];
                $medNom = trim(($med['nom'] ?? '') . ' ' . ($med['prenom'] ?? ''));

                $stmtD = $db->prepare("SELECT data FROM formulaires_data WHERE id = ?");
                $stmtD->execute([$formulaire_data_id]);
                $rawData  = $stmtD->fetchColumn();
                $dataArr  = $rawData ? (json_decode($rawData, true) ?? []) : [];

                $dataArr['signature_medecin']       = $signature;
                $dataArr['nom_medecin_signataire']  = $medNom;
                $dataArr['date_signature_medecin']  = date('d/m/Y');

                $db->prepare("UPDATE formulaires_data SET data=?, statut='SIGNE' WHERE id=?")
                   ->execute([json_encode($dataArr, JSON_UNESCAPED_UNICODE), $formulaire_data_id]);
            }

            echo json_encode(['success' => true]);
        } catch (\PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /* ══════════════════════════════════════════════════════════════════
     * POST formulaire/upload-document-ajax
     * Upload AJAX d'un fichier attaché au dossier patient
     * Body : multipart/form-data  { patient_id, categorie, description, document }
     * ══════════════════════════════════════════════════════════════════ */
    public function uploadDocumentAjax(): void {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_FILES['document'])) {
            echo json_encode(['success' => false, 'message' => 'Requête invalide']); return;
        }

        $patient_id  = (int)($_POST['patient_id'] ?? 0);
        $categorie   = htmlspecialchars($_POST['categorie']   ?? 'Gynécologie');
        $description = htmlspecialchars($_POST['description'] ?? '');

        if (!$patient_id) {
            echo json_encode(['success' => false, 'message' => 'patient_id manquant']); return;
        }

        $file = $_FILES['document'];
        $ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        // Extensions autorisées (dcm = DICOM)
        $allowed = ['jpg','jpeg','png','gif','pdf','doc','docx','xls','xlsx','mp4','mov','dcm'];
        if (!in_array($ext, $allowed)) {
            echo json_encode(['success' => false, 'message' => 'Type de fichier non autorisé']); return;
        }
        // Taille max : 100 Mo pour DICOM, 20 Mo pour les autres
        $maxSize = ($ext === 'dcm') ? 100 * 1024 * 1024 : 20 * 1024 * 1024;
        $maxLabel = ($ext === 'dcm') ? '100 Mo' : '20 Mo';
        if ($file['size'] > $maxSize) {
            echo json_encode(['success' => false, 'message' => "Fichier trop volumineux (max $maxLabel)"]); return;
        }

        $upload_dir = __DIR__ . '/../../public/uploads/documents/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0750, true);

        $filename = 'DOC_' . $patient_id . '_' . time() . '_' . rand(100,999) . '.' . $ext;

        if (!move_uploaded_file($file['tmp_name'], $upload_dir . $filename)) {
            echo json_encode(['success' => false, 'message' => 'Erreur lors du déplacement du fichier']); return;
        }

        try {
            $db = (new Database())->getConnection();
            // Auto-create table si absente
            $db->exec("CREATE TABLE IF NOT EXISTS patient_documents (
                id INT AUTO_INCREMENT PRIMARY KEY,
                patient_id INT NOT NULL,
                nom_fichier VARCHAR(255) NOT NULL,
                chemin_fichier VARCHAR(255) NOT NULL,
                type_mime VARCHAR(100),
                categorie VARCHAR(100) DEFAULT 'Autre',
                description TEXT,
                date_upload DATETIME DEFAULT CURRENT_TIMESTAMP,
                KEY(patient_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

            $stmt = $db->prepare("INSERT INTO patient_documents
                (patient_id, nom_fichier, chemin_fichier, type_mime, categorie, description, date_upload)
                VALUES (?,?,?,?,?,?,NOW())");
            $stmt->execute([$patient_id, $file['name'], $filename, $file['type'], $categorie, $description]);
            $doc_id = $db->lastInsertId();

            echo json_encode([
                'success'   => true,
                'doc_id'    => $doc_id,
                'nom'       => $file['name'],
                'chemin'    => $filename,
                'categorie' => $categorie,
                'url'       => BASE_URL . 'public/uploads/documents/' . $filename,
                'ext'       => $ext,
                'taille'    => $file['size'],
            ]);
        } catch (\PDOException $e) {
            @unlink($upload_dir . $filename);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /* ══════════════════════════════════════════════════════════════════
     * GET formulaire/documents-patient/{patient_id}
     * Liste JSON des documents du patient (filtrables par categorie)
     * ══════════════════════════════════════════════════════════════════ */
    public function getDocumentsPatient(int $patient_id): void {
        header('Content-Type: application/json');
        $categorie = $_GET['categorie'] ?? '';
        try {
            $db  = (new Database())->getConnection();
            $sql = "SELECT id, nom_fichier, chemin_fichier, type_mime, categorie, description, date_upload
                    FROM patient_documents
                    WHERE patient_id = ?
                    " . ($categorie ? "AND categorie = ?" : "") . "
                    ORDER BY date_upload DESC LIMIT 100";
            $params = $categorie ? [$patient_id, $categorie] : [$patient_id];
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $docs = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            // Ajouter URL et infos d'extension
            foreach ($docs as &$d) {
                $d['url'] = BASE_URL . 'public/uploads/documents/' . $d['chemin_fichier'];
                $d['ext'] = strtolower(pathinfo($d['chemin_fichier'], PATHINFO_EXTENSION));
                $d['est_image'] = in_array($d['ext'], ['jpg','jpeg','png','gif','webp']);
                $d['date_fmt']  = (new \DateTime($d['date_upload']))->format('d/m/Y H:i');
            }
            echo json_encode(['success' => true, 'documents' => $docs]);
        } catch (\PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage(), 'documents' => []]);
        }
    }

    /* ══════════════════════════════════════════════════════════════════
     * POST formulaire/supprimer-brouillon
     * Body JSON : { formulaire_id }
     * Seul l'auteur peut supprimer son propre brouillon (statut BROUILLON)
     * ══════════════════════════════════════════════════════════════════ */
    public function supprimerBrouillon(): void {
        header('Content-Type: application/json');
        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        $formulaire_id = (int)($input['formulaire_id'] ?? 0);
        $user_id       = (int)($_SESSION['user_id'] ?? 0);

        if (!$formulaire_id || !$user_id) {
            echo json_encode(['success' => false, 'message' => 'Paramètres invalides']); return;
        }

        try {
            $db = (new Database())->getConnection();

            /* Vérifier que le formulaire appartient à l'utilisateur et est bien un brouillon */
            $stmt = $db->prepare(
                "SELECT id FROM formulaires_data
                 WHERE id = ? AND user_id = ? AND statut = 'BROUILLON' LIMIT 1"
            );
            $stmt->execute([$formulaire_id, $user_id]);
            if (!$stmt->fetchColumn()) {
                echo json_encode(['success' => false, 'message' => 'Brouillon introuvable ou non autorisé']); return;
            }

            $db->prepare("DELETE FROM formulaires_data WHERE id = ?")->execute([$formulaire_id]);
            echo json_encode(['success' => true, 'message' => 'Brouillon supprimé']);
        } catch (\PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
}

