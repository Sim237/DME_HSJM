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
   FICHIER : app/controllers/PediatricConsultationController.php
   Gestion de la consultation pédiatrique (nourrisson et enfant)
   Accès réservé aux médecins du service Pédiatrie & Néonatologie
   ============================================================================ */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../models/Patient.php';

class PediatricConsultationController {

    private $db;

    public function __construct() {
        $this->db = (new Database())->getConnection();
        $this->requireAuth();
    }

    /** Vérifie que l'utilisateur est connecté et appartient au service pédiatrie */
    private function requireAuth() {
        if (empty($_SESSION['user_id'])) {
            header('Location: ' . BASE_URL . 'login');
            exit;
        }
    }

    /** Vérifie que le médecin appartient au service pédiatrie/néonatologie */
    private function isPediatricService(): bool {
        $nom = strtolower($_SESSION['nom_service'] ?? '');
        return stripos($nom, 'pédiatrie') !== false
            || stripos($nom, 'pediatrie') !== false
            || stripos($nom, 'néonatologie') !== false
            || stripos($nom, 'neonatologie') !== false
            || stripos($nom, 'ped') !== false
            || stripos($nom, 'neo') !== false;
    }

    private function accessDenied() {
        http_response_code(403);
        require_once __DIR__ . '/../views/errors/403.php';
        exit;
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  AFFICHAGE DU FORMULAIRE (multi-étapes)
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * GET consultation-ped/formulaire?patient_id=X[&step=N]
     * Affiche le formulaire pédiatrique multi-étapes
     */
    public function formulaire($patient_id) {
        if (!$this->isPediatricService() && ($_SESSION['role'] ?? '') !== 'ADMIN') {
            $this->accessDenied();
        }

        $patientModel = new Patient();
        $patient = $patientModel->getById($patient_id);
        if (!$patient) {
            die("Patient introuvable.");
        }

        // Calculer l'âge
        $age_annees = 0;
        $age_mois_total = 0;
        if (!empty($patient['date_naissance'])) {
            $naissance = new DateTime($patient['date_naissance']);
            $now       = new DateTime();
            $diff      = $naissance->diff($now);
            $age_annees     = $diff->y;
            $age_mois_total = $diff->y * 12 + $diff->m;
        }

        $step = max(1, min(6, (int)($_GET['step'] ?? 1)));

        // Charger un brouillon existant depuis la session
        $sessionKey = 'ped_consult_' . $patient_id;
        $draft = $_SESSION[$sessionKey] ?? [];

        // Catalogue de médicaments pour l'autocomplétion de l'ordonnance (étape 6)
        $medicaments = [];
        try {
            $medicaments = $this->db->query(
                "SELECT id, nom, dosage, forme, quantite FROM medicaments
                 WHERE disponible IS NULL OR disponible = 1 ORDER BY nom ASC"
            )->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            try { $medicaments = $this->db->query("SELECT id, nom FROM medicaments ORDER BY nom ASC")->fetchAll(PDO::FETCH_ASSOC); }
            catch (\Throwable $e2) { $medicaments = []; }
        }

        require_once __DIR__ . '/../views/consultation_ped/formulaire.php';
    }

    /**
     * POST consultation-ped/sauvegarder-etape
     * Sauvegarde une étape dans la session et redirige vers l'étape suivante
     */
    public function sauvegarderEtape() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . 'dashboard');
            exit;
        }

        if (!$this->isPediatricService() && ($_SESSION['role'] ?? '') !== 'ADMIN') {
            $this->accessDenied();
        }

        $patient_id = (int)($_POST['patient_id'] ?? 0);
        $step       = (int)($_POST['current_step'] ?? 1);
        $next_step  = (int)($_POST['next_step'] ?? ($step + 1));
        $action     = $_POST['action'] ?? 'next'; // next | finish

        $sessionKey = 'ped_consult_' . $patient_id;
        if (!isset($_SESSION[$sessionKey])) {
            $_SESSION[$sessionKey] = [];
        }

        // Merge les données de cette étape dans le brouillon
        $fields = $this->getStepFields($step);
        foreach ($fields as $field) {
            if (isset($_POST[$field])) {
                $val = $_POST[$field];
                // JSON pour les tableaux (checkboxes multiples)
                if (is_array($val)) {
                    $_SESSION[$sessionKey][$field] = json_encode($val);
                } else {
                    $_SESSION[$sessionKey][$field] = $val;
                }
            }
            // Pour les checkboxes booléens non cochés
            if (in_array($field, ['env_milda','env_deparasitage']) && !isset($_POST[$field])) {
                $_SESSION[$sessionKey][$field] = '0';
            }
        }

        if ($action === 'finish') {
            // Sauvegarder en base de données
            $id = $this->saveToDatabase($patient_id, $_SESSION[$sessionKey]);
            // Créer l'ordonnance pharmacie + les demandes d'imagerie éventuelles
            $this->savePrescriptionsImaging($patient_id);
            unset($_SESSION[$sessionKey]);
            header('Location: ' . BASE_URL . 'consultation-ped/voir/' . $id);
            exit;
        }

        header('Location: ' . BASE_URL . 'consultation-ped/formulaire/' . $patient_id . '?step=' . $next_step);
        exit;
    }

    /**
     * Retourne les noms des champs POST pour chaque étape
     */
    private function getStepFields(int $step): array {
        switch ($step) {
            case 1: return [
                'age_annees','age_mois','ethnie','religion','contact_parents',
                'motif_consultation','hma'
            ];
            case 2: return [
                'atcds_hospitalisations','atcds_pathologies_chroniques','atcds_pathologies_autres',
                'atcds_chirurgicaux','atcds_transfusions','atcds_gsrh','atcds_electrophorese',
                'atcds_allergies','atcds_vaccinations','atcds_immunoallerg_autres',
                'env_milda','env_deparasitage','env_eau_boisson','env_autres',
                'alim_0_6_mois','alim_6_24_mois','alim_actuelle','alim_autres'
            ];
            case 3: return [
                'dev_tenue_tete','dev_assis_sans_soutien','dev_ramper','dev_debout_sans_soutien',
                'dev_marche_autonome','dev_motricite_autres','dev_fine_regard','dev_fine_autres',
                'dev_social_visage_mere','dev_social_reconnait','dev_social_fuit_etranger','dev_social_autres',
                'dev_lang_gazouillis','dev_lang_babillage_mono','dev_lang_babillage_bi',
                'dev_lang_phrases','dev_lang_autres','dev_scolarise',
                'fam_mere_age','fam_mere_profession','fam_mere_pathologies','fam_mere_autres',
                'fam_pere_age','fam_pere_profession','fam_pere_pathologies','fam_pere_autres',
                'fam_fratrie_rang','fam_fratrie_sante','fam_fratrie_autres'
            ];
            case 4: return [
                'es_general','es_snc','es_cardio','es_respiratoire',
                'es_digestif','es_urogenital','es_locomoteur'
            ];
            case 5: return [
                'ep_etat_general','ep_conscience',
                'ep_temperature','ep_fr','ep_spo2','ep_fc','ep_ta','ep_trc',
                'ep_poids','ep_taille','ep_pb','ep_pc','ep_imc','ep_parametres_autres',
                'ep_tete_cou_fontanelle','ep_tete_cou_conjonctives','ep_tete_cou_scleres',
                'ep_tete_cou_ganglions','ep_tete_cou_orl','ep_tete_cou_autres',
                'ep_thorax_inspection','ep_thorax_palpation','ep_thorax_percussion',
                'ep_thorax_auscultation','ep_thorax_autres',
                'ep_abdomen_inspection','ep_abdomen_palpation','ep_abdomen_percussion',
                'ep_abdomen_auscultation','ep_abdomen_hernies','ep_abdomen_autres',
                'ep_oge',
                'ep_tr_marge','ep_tr_tonicite','ep_tr_ampoule','ep_tr_doigtier','ep_tr_autres',
                'ep_membres_deformations','ep_membres_inflammatoires','ep_membres_oedemes','ep_membres_autres',
                'ep_neuro_fonctions','ep_neuro_paires','ep_neuro_meninges','ep_neuro_focalisation','ep_neuro_autres'
            ];
            case 6: return [
                'resume_syndromique','diag_positif','diag_differentiels',
                'bilan_diagnostique','bilan_etiologique','bilan_retentissement','bilan_terrain',
                'pec_buts','pec_non_pharmacologique','pec_pharmacologique',
                'pec_orthopedique','pec_chirurgical',
                'surveillance','surveillance_autres'
            ];
            default: return [];
        }
    }

    /**
     * Sauvegarde toutes les données en base de données
     */
    private function saveToDatabase(int $patient_id, array $data): int {
        $medecin_id = (int)$_SESSION['user_id'];
        $service_id = (int)($_SESSION['service_id'] ?? 0) ?: null;

        // Grouper les champs topographiques en JSON
        $ep_tete_cou = json_encode([
            'fontanelle'  => $data['ep_tete_cou_fontanelle']  ?? '',
            'conjonctives'=> $data['ep_tete_cou_conjonctives']?? '',
            'scleres'     => $data['ep_tete_cou_scleres']     ?? '',
            'ganglions'   => $data['ep_tete_cou_ganglions']   ?? '',
            'orl'         => $data['ep_tete_cou_orl']         ?? '',
            'autres'      => $data['ep_tete_cou_autres']      ?? '',
        ]);
        $ep_thorax = json_encode([
            'inspection'   => $data['ep_thorax_inspection']   ?? '',
            'palpation'    => $data['ep_thorax_palpation']    ?? '',
            'percussion'   => $data['ep_thorax_percussion']   ?? '',
            'auscultation' => $data['ep_thorax_auscultation'] ?? '',
            'autres'       => $data['ep_thorax_autres']       ?? '',
        ]);
        $ep_abdomen = json_encode([
            'inspection'   => $data['ep_abdomen_inspection']  ?? '',
            'palpation'    => $data['ep_abdomen_palpation']   ?? '',
            'percussion'   => $data['ep_abdomen_percussion']  ?? '',
            'auscultation' => $data['ep_abdomen_auscultation']?? '',
            'hernies'      => $data['ep_abdomen_hernies']     ?? '',
            'autres'       => $data['ep_abdomen_autres']      ?? '',
        ]);
        $ep_tr = json_encode([
            'marge'    => $data['ep_tr_marge']    ?? '',
            'tonicite' => $data['ep_tr_tonicite'] ?? '',
            'ampoule'  => $data['ep_tr_ampoule']  ?? '',
            'doigtier' => $data['ep_tr_doigtier'] ?? '',
            'autres'   => $data['ep_tr_autres']   ?? '',
        ]);
        $ep_membres = json_encode([
            'deformations'   => $data['ep_membres_deformations']   ?? '',
            'inflammatoires' => $data['ep_membres_inflammatoires'] ?? '',
            'oedemes'        => $data['ep_membres_oedemes']        ?? '',
            'autres'         => $data['ep_membres_autres']         ?? '',
        ]);
        $ep_neuro = json_encode([
            'fonctions'   => $data['ep_neuro_fonctions']   ?? '',
            'paires'      => $data['ep_neuro_paires']      ?? '',
            'meninges'    => $data['ep_neuro_meninges']    ?? '',
            'focalisation'=> $data['ep_neuro_focalisation']?? '',
            'autres'      => $data['ep_neuro_autres']      ?? '',
        ]);

        $n = function(?string $v): ?float {
            return ($v !== null && $v !== '') ? (float)$v : null;
        };
        $ni = function(?string $v): ?int {
            return ($v !== null && $v !== '') ? (int)$v : null;
        };

        $stmt = $this->db->prepare("
            INSERT INTO consultations_pediatriques
                (patient_id, medecin_id, service_id, date_consultation, heure_consultation,
                 age_annees, age_mois, ethnie, religion, contact_parents,
                 motif_consultation, hma,
                 atcds_hospitalisations, atcds_pathologies_chroniques, atcds_pathologies_autres,
                 atcds_chirurgicaux, atcds_transfusions, atcds_gsrh, atcds_electrophorese,
                 atcds_allergies, atcds_vaccinations, atcds_immunoallerg_autres,
                 env_milda, env_deparasitage, env_eau_boisson, env_autres,
                 alim_0_6_mois, alim_6_24_mois, alim_actuelle, alim_autres,
                 dev_tenue_tete, dev_assis_sans_soutien, dev_ramper, dev_debout_sans_soutien,
                 dev_marche_autonome, dev_motricite_autres, dev_fine_regard, dev_fine_autres,
                 dev_social_visage_mere, dev_social_reconnait, dev_social_fuit_etranger, dev_social_autres,
                 dev_lang_gazouillis, dev_lang_babillage_mono, dev_lang_babillage_bi,
                 dev_lang_phrases, dev_lang_autres, dev_scolarise,
                 fam_mere_age, fam_mere_profession, fam_mere_pathologies, fam_mere_autres,
                 fam_pere_age, fam_pere_profession, fam_pere_pathologies, fam_pere_autres,
                 fam_fratrie_rang, fam_fratrie_sante, fam_fratrie_autres,
                 es_general, es_snc, es_cardio, es_respiratoire, es_digestif, es_urogenital, es_locomoteur,
                 ep_etat_general, ep_conscience,
                 ep_temperature, ep_fr, ep_spo2, ep_fc, ep_ta, ep_trc,
                 ep_poids, ep_taille, ep_pb, ep_pc, ep_imc, ep_parametres_autres,
                 ep_tete_cou, ep_thorax, ep_abdomen, ep_oge, ep_tr, ep_membres, ep_neuro,
                 resume_syndromique, diag_positif, diag_differentiels,
                 bilan_diagnostique, bilan_etiologique, bilan_retentissement, bilan_terrain,
                 pec_buts, pec_non_pharmacologique, pec_pharmacologique,
                 pec_orthopedique, pec_chirurgical,
                 surveillance, surveillance_autres, statut)
            VALUES
                (?,?,?,CURDATE(),CURTIME(),
                 ?,?,?,?,?,
                 ?,?,
                 ?,?,?,
                 ?,?,?,?,
                 ?,?,?,
                 ?,?,?,?,
                 ?,?,?,?,
                 ?,?,?,?,
                 ?,?,?,?,
                 ?,?,?,?,
                 ?,?,?,
                 ?,?,?,
                 ?,?,?,?,
                 ?,?,?,?,
                 ?,?,?,
                 ?,?,?,?,?,?,?,
                 ?,?,
                 ?,?,?,?,?,?,
                 ?,?,?,?,?,?,
                 ?,?,?,?,?,?,?,
                 ?,?,?,
                 ?,?,?,?,
                 ?,?,?,
                 ?,?,
                 ?,?,'termine')
        ");

        $stmt->execute([
            $patient_id, $medecin_id, $service_id,
            $ni($data['age_annees'] ?? null),
            $ni($data['age_mois'] ?? null),
            $data['ethnie'] ?? null,
            $data['religion'] ?? null,
            $data['contact_parents'] ?? null,

            $data['motif_consultation'] ?? null,
            $data['hma'] ?? null,

            $data['atcds_hospitalisations'] ?? null,
            is_string($data['atcds_pathologies_chroniques'] ?? null) ? $data['atcds_pathologies_chroniques'] : json_encode($data['atcds_pathologies_chroniques'] ?? []),
            $data['atcds_pathologies_autres'] ?? null,
            $data['atcds_chirurgicaux'] ?? null,
            $data['atcds_transfusions'] ?? null,
            $data['atcds_gsrh'] ?? null,
            $data['atcds_electrophorese'] ?? null,
            $data['atcds_allergies'] ?? null,
            $data['atcds_vaccinations'] ?? null,
            $data['atcds_immunoallerg_autres'] ?? null,

            (int)($data['env_milda'] ?? 0),
            (int)($data['env_deparasitage'] ?? 0),
            $data['env_eau_boisson'] ?? null,
            $data['env_autres'] ?? null,

            $data['alim_0_6_mois'] ?? null,
            $data['alim_6_24_mois'] ?? null,
            $data['alim_actuelle'] ?? null,
            $data['alim_autres'] ?? null,

            $ni($data['dev_tenue_tete'] ?? null),
            $ni($data['dev_assis_sans_soutien'] ?? null),
            $ni($data['dev_ramper'] ?? null),
            $ni($data['dev_debout_sans_soutien'] ?? null),
            $ni($data['dev_marche_autonome'] ?? null),
            $data['dev_motricite_autres'] ?? null,
            $ni($data['dev_fine_regard'] ?? null),
            $data['dev_fine_autres'] ?? null,
            $ni($data['dev_social_visage_mere'] ?? null),
            $ni($data['dev_social_reconnait'] ?? null),
            $ni($data['dev_social_fuit_etranger'] ?? null),
            $data['dev_social_autres'] ?? null,
            $ni($data['dev_lang_gazouillis'] ?? null),
            $ni($data['dev_lang_babillage_mono'] ?? null),
            $ni($data['dev_lang_babillage_bi'] ?? null),
            $ni($data['dev_lang_phrases'] ?? null),
            $data['dev_lang_autres'] ?? null,
            $data['dev_scolarise'] ?? null,

            $ni($data['fam_mere_age'] ?? null),
            $data['fam_mere_profession'] ?? null,
            $data['fam_mere_pathologies'] ?? null,
            $data['fam_mere_autres'] ?? null,
            $ni($data['fam_pere_age'] ?? null),
            $data['fam_pere_profession'] ?? null,
            $data['fam_pere_pathologies'] ?? null,
            $data['fam_pere_autres'] ?? null,
            $data['fam_fratrie_rang'] ?? null,
            $data['fam_fratrie_sante'] ?? null,
            $data['fam_fratrie_autres'] ?? null,

            is_string($data['es_general'] ?? null) ? $data['es_general'] : json_encode($data['es_general'] ?? []),
            is_string($data['es_snc'] ?? null) ? $data['es_snc'] : json_encode($data['es_snc'] ?? []),
            is_string($data['es_cardio'] ?? null) ? $data['es_cardio'] : json_encode($data['es_cardio'] ?? []),
            is_string($data['es_respiratoire'] ?? null) ? $data['es_respiratoire'] : json_encode($data['es_respiratoire'] ?? []),
            is_string($data['es_digestif'] ?? null) ? $data['es_digestif'] : json_encode($data['es_digestif'] ?? []),
            is_string($data['es_urogenital'] ?? null) ? $data['es_urogenital'] : json_encode($data['es_urogenital'] ?? []),
            is_string($data['es_locomoteur'] ?? null) ? $data['es_locomoteur'] : json_encode($data['es_locomoteur'] ?? []),

            $data['ep_etat_general'] ?? null,
            $data['ep_conscience'] ?? null,
            $n($data['ep_temperature'] ?? null),
            $ni($data['ep_fr'] ?? null),
            $n($data['ep_spo2'] ?? null),
            $ni($data['ep_fc'] ?? null),
            $data['ep_ta'] ?? null,
            $data['ep_trc'] ?? null,
            $n($data['ep_poids'] ?? null),
            $n($data['ep_taille'] ?? null),
            $n($data['ep_pb'] ?? null),
            $n($data['ep_pc'] ?? null),
            $n($data['ep_imc'] ?? null),
            $data['ep_parametres_autres'] ?? null,

            $ep_tete_cou, $ep_thorax, $ep_abdomen,
            $data['ep_oge'] ?? null,
            $ep_tr, $ep_membres, $ep_neuro,

            $data['resume_syndromique'] ?? null,
            $data['diag_positif'] ?? null,
            $data['diag_differentiels'] ?? null,

            is_string($data['bilan_diagnostique'] ?? null) ? $data['bilan_diagnostique'] : json_encode($data['bilan_diagnostique'] ?? []),
            is_string($data['bilan_etiologique'] ?? null) ? $data['bilan_etiologique'] : json_encode($data['bilan_etiologique'] ?? []),
            is_string($data['bilan_retentissement'] ?? null) ? $data['bilan_retentissement'] : json_encode($data['bilan_retentissement'] ?? []),
            is_string($data['bilan_terrain'] ?? null) ? $data['bilan_terrain'] : json_encode($data['bilan_terrain'] ?? []),

            $data['pec_buts'] ?? null,
            $data['pec_non_pharmacologique'] ?? null,
            $data['pec_pharmacologique'] ?? null,
            $data['pec_orthopedique'] ?? null,
            $data['pec_chirurgical'] ?? null,

            is_string($data['surveillance'] ?? null) ? $data['surveillance'] : json_encode($data['surveillance'] ?? []),
            $data['surveillance_autres'] ?? null,
        ]);

        $consultation_id = (int)$this->db->lastInsertId();

        // ── Sortie de la file d'attente : le patient a été consulté ──
        // (mirroir de ConsultationController::finaliserConsultation)
        try {
            $cols = $this->db->query("SHOW COLUMNS FROM patients")->fetchAll(PDO::FETCH_COLUMN);
            $sets = [];
            if (in_array('statut_parcours', $cols))   $sets[] = "statut_parcours = 'SORTI'";
            if (in_array('statut_hosp', $cols))       $sets[] = "statut_hosp = 'AUCUN'";
            if (in_array('parametres_requis', $cols)) $sets[] = "parametres_requis = 0";
            if ($sets) {
                $this->db->prepare("UPDATE patients SET " . implode(', ', $sets) . " WHERE id = ?")
                         ->execute([$patient_id]);
            }
        } catch (\Throwable $e) {
            error_log('[PediatricConsultation] sortie file: ' . $e->getMessage());
        }

        return $consultation_id;
    }

    /**
     * Crée l'ordonnance pharmacie et les demandes d'imagerie saisies à l'étape 6.
     * Lit les champs JSON `ordonnance_json` et `imagerie_json` du POST.
     * Robuste : n'interrompt jamais la finalisation de la consultation.
     */
    private function savePrescriptionsImaging(int $patient_id): void {
        $medecin_id = (int)($_SESSION['user_id'] ?? 0) ?: null;

        // ── 1. Ordonnance médicamenteuse ──────────────────────────────
        try {
            $meds = json_decode($_POST['ordonnance_json'] ?? '[]', true);
            if (is_array($meds) && count($meds) > 0) {
                $this->db->prepare(
                    "INSERT INTO ordonnances_pharmacie (patient_id, medecin_id, statut, type_ordonnance, date_creation)
                     VALUES (?, ?, 'EN_ATTENTE', 'NORMALE', NOW())"
                )->execute([$patient_id, $medecin_id]);
                $ordId = (int)$this->db->lastInsertId();

                $stmtMed = $this->db->prepare(
                    "INSERT INTO ordonnance_medicaments
                        (ordonnance_id, medicament_id, nom_medicament, quantite, posologie, duree)
                     VALUES (?, ?, ?, ?, ?, ?)"
                );
                foreach ($meds as $m) {
                    $nom = trim($m['nom'] ?? '');
                    if ($nom === '') continue;
                    $mid = !empty($m['medicament_id']) ? (int)$m['medicament_id'] : null;
                    $stmtMed->execute([
                        $ordId, $mid, $nom,
                        (int)($m['quantite'] ?? 1),
                        trim($m['posologie'] ?? ''),
                        trim($m['duree'] ?? ''),
                    ]);
                }
            }
        } catch (\Throwable $e) {
            error_log('[PediatricConsultation] ordonnance: ' . $e->getMessage());
        }

        // ── 2. Demandes d'imagerie ────────────────────────────────────
        try {
            $imgs = json_decode($_POST['imagerie_json'] ?? '[]', true);
            if (is_array($imgs) && count($imgs) > 0) {
                $tableImg = (int)$this->db->query("SHOW TABLES LIKE 'demandes_imagerie'")->rowCount() > 0
                          ? 'demandes_imagerie' : null;
                if ($tableImg) {
                    $stmtImg = $this->db->prepare(
                        "INSERT INTO demandes_imagerie
                            (patient_id, consultation_id, medecin_id, type_examen, partie_corps,
                             description, urgence, statut, avec_contraste, date_creation)
                         VALUES (?, NULL, ?, ?, ?, ?, ?, 'EN_ATTENTE', ?, NOW())"
                    );
                    foreach ($imgs as $im) {
                        $modalite = trim($im['modalite'] ?? '');
                        $partie   = trim($im['partie'] ?? '');
                        if ($modalite === '' && $partie === '') continue;
                        $stmtImg->execute([
                            $patient_id, $medecin_id,
                            $modalite ?: 'autre',
                            $partie,
                            trim($im['indication'] ?? ''),
                            !empty($im['urgence']) ? 'URGENT' : 'NORMAL',
                            !empty($im['contraste']) ? 1 : 0,
                        ]);
                    }
                }
            }
        } catch (\Throwable $e) {
            error_log('[PediatricConsultation] imagerie: ' . $e->getMessage());
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  CONSULTATION DE LA FICHE
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * GET consultation-ped/voir/{id}
     */
    public function voir(int $id) {
        $stmt = $this->db->prepare("
            SELECT cp.*, p.nom, p.prenom, p.date_naissance, p.sexe, p.dossier_numero,
                   NULL AS lieu_naissance, p.profession, p.adresse,
                   u.nom AS medecin_nom, u.prenom AS medecin_prenom,
                   u.specialite AS medecin_specialite,
                   s.nom_service
            FROM consultations_pediatriques cp
            JOIN patients p ON cp.patient_id = p.id
            JOIN users u ON cp.medecin_id = u.id
            LEFT JOIN services s ON cp.service_id = s.id
            WHERE cp.id = ?
        ");
        $stmt->execute([$id]);
        $consult = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$consult) die("Consultation introuvable.");

        // Décoder les JSON
        $jsonFields = [
            'atcds_pathologies_chroniques',
            'es_general','es_snc','es_cardio','es_respiratoire',
            'es_digestif','es_urogenital','es_locomoteur',
            'ep_tete_cou','ep_thorax','ep_abdomen','ep_tr','ep_membres','ep_neuro',
            'bilan_diagnostique','bilan_etiologique','bilan_retentissement','bilan_terrain',
            'surveillance',
        ];
        foreach ($jsonFields as $f) {
            if (!empty($consult[$f]) && is_string($consult[$f])) {
                $decoded = json_decode($consult[$f], true);
                $consult[$f] = is_array($decoded) ? $decoded : [];
            } else {
                $consult[$f] = [];
            }
        }

        // ── Ordonnance + imagerie liées (mêmes patient & jour que la consultation) ──
        $ordonnancePed = null; $ordoMeds = []; $imageriePed = [];
        try {
            $dateC = substr($consult['date_consultation'] ?? '', 0, 10) ?: date('Y-m-d');
            $stmtO = $this->db->prepare(
                "SELECT id, statut, date_creation FROM ordonnances_pharmacie
                 WHERE patient_id = ? AND DATE(date_creation) = ?
                 ORDER BY id DESC LIMIT 1"
            );
            $stmtO->execute([$consult['patient_id'], $dateC]);
            $ordonnancePed = $stmtO->fetch(PDO::FETCH_ASSOC) ?: null;
            if ($ordonnancePed) {
                $stmtM = $this->db->prepare(
                    "SELECT nom_medicament, posologie, duree, quantite FROM ordonnance_medicaments
                     WHERE ordonnance_id = ? AND (ligne_annulee IS NULL OR ligne_annulee = 0)"
                );
                $stmtM->execute([$ordonnancePed['id']]);
                $ordoMeds = $stmtM->fetchAll(PDO::FETCH_ASSOC);
            }
            if ((int)$this->db->query("SHOW TABLES LIKE 'demandes_imagerie'")->rowCount() > 0) {
                $stmtI = $this->db->prepare(
                    "SELECT type_examen, partie_corps, description, urgence, statut FROM demandes_imagerie
                     WHERE patient_id = ? AND DATE(date_creation) = ?
                     ORDER BY id DESC"
                );
                $stmtI->execute([$consult['patient_id'], $dateC]);
                $imageriePed = $stmtI->fetchAll(PDO::FETCH_ASSOC);
            }
        } catch (\Throwable $e) { error_log('[PediatricConsultation] voir ordo/img: ' . $e->getMessage()); }

        require_once __DIR__ . '/../views/consultation_ped/voir.php';
    }

    /**
     * GET consultation-ped/liste/{patient_id}
     */
    public function liste(int $patient_id) {
        $stmt = $this->db->prepare("
            SELECT cp.id, cp.date_consultation, cp.motif_consultation, cp.diag_positif, cp.statut,
                   u.nom AS medecin_nom, u.prenom AS medecin_prenom
            FROM consultations_pediatriques cp
            JOIN users u ON cp.medecin_id = u.id
            WHERE cp.patient_id = ?
            ORDER BY cp.date_consultation DESC
        ");
        $stmt->execute([$patient_id]);
        $consultations = $stmt->fetchAll(PDO::FETCH_ASSOC);

        header('Content-Type: application/json');
        echo json_encode($consultations);
        exit;
    }
}
