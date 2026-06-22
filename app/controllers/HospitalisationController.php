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
require_once __DIR__ . '/../services/DataService.php';

class HospitalisationController {
    private $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    /**
     * Guard : vérifie qu'un INFIRMIER/MAJOR_INFIRMIER n'accède qu'aux patients
     * hospitalisés dans son propre service.
     *
     * @param int  $patient_id   ID du patient cible
     * @param bool $jsonMode     Si true, renvoie une erreur JSON au lieu de rediriger
     * @return bool  true = accès autorisé, false = accès bloqué (après redirect/json)
     */
    private function _guardInfirmierService(int $patient_id, bool $jsonMode = false): bool {
        $role      = $_SESSION['user_role']    ?? '';
        $serviceId = (int)($_SESSION['service_id'] ?? 0);

        // Seuls les infirmiers sont soumis à la restriction de service
        $rolesRestreints = ['INFIRMIER', 'MAJOR_INFIRMIER'];
        if (!in_array($role, $rolesRestreints) || $serviceId === 0) {
            return true; // Médecins, admins, etc. → pas de restriction
        }

        // Vérifier qu'il existe une hospitalisation active pour ce patient dans ce service
        $stmt = $this->db->prepare(
            "SELECT id FROM hospitalisations
              WHERE patient_id = ? AND service_id = ? AND statut = 'en_cours'
              LIMIT 1"
        );
        $stmt->execute([$patient_id, $serviceId]);

        if ($stmt->fetchColumn()) {
            return true; // Patient bien dans le service de l'infirmier
        }

        // Accès refusé
        if ($jsonMode) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Accès refusé : ce patient n\'appartient pas à votre service.']);
            exit;
        }

        // Redirection vers le dashboard avec message d'erreur
        $_SESSION['flash_error'] = 'Accès refusé : ce patient n\'est pas hospitalisé dans votre service.';
        header('Location: ' . BASE_URL . 'dashboard');
        exit;
    }

    public function index() {
        $userRole  = $_SESSION['user_role']    ?? '';
        $serviceId = (int)($_SESSION['service_id'] ?? 0);

        // Rôles avec vue globale (tous les services)
        $rolesGlobaux = ['ADMIN', 'DIRECTEUR', 'MEDECIN_CHEF', 'MAJOR'];

        if (in_array($userRole, $rolesGlobaux) || $serviceId === 0) {
            // Vue globale : tous les patients hospitalisés
            $sql    = "SELECT p.id AS pid, p.nom, p.prenom, p.date_naissance, p.dossier_numero, p.sexe,
                              h.id AS hosp_id, h.patient_id, h.service_id AS service_origine_id,
                              h.lit_id, h.date_admission, h.motif_hospitalisation,
                              s.nom_service AS service_nom, l.nom_lit AS lit_numero
                       FROM patients p
                       JOIN hospitalisations h ON p.id = h.patient_id
                       LEFT JOIN services s ON h.service_id = s.id
                       LEFT JOIN lits l ON h.lit_id = l.id
                       WHERE h.statut IN ('en_cours')
                       ORDER BY h.date_admission DESC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
        } else {
            // Vue restreinte : uniquement les patients du service de l'utilisateur
            $sql    = "SELECT p.id AS pid, p.nom, p.prenom, p.date_naissance, p.dossier_numero, p.sexe,
                              h.id AS hosp_id, h.patient_id, h.service_id AS service_origine_id,
                              h.lit_id, h.date_admission, h.motif_hospitalisation,
                              s.nom_service AS service_nom, l.nom_lit AS lit_numero
                       FROM patients p
                       JOIN hospitalisations h ON p.id = h.patient_id
                       LEFT JOIN services s ON h.service_id = s.id
                       LEFT JOIN lits l ON h.lit_id = l.id
                       WHERE h.statut IN ('en_cours')
                         AND h.service_id = ?
                       ORDER BY h.date_admission DESC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$serviceId]);
        }

        $patients_hospitalises = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Indicateur pour la vue (afficher ou non le filtre "tous les services")
        $vueGlobale = in_array($userRole, $rolesGlobaux) || $serviceId === 0;

        // Services cliniques pour les sélecteurs (admission + transfert)
        $servicesCliniques = [];
        try {
            $servicesCliniques = $this->db->query(
                "SELECT id, nom_service FROM services
                  WHERE categorie = 'CLINIQUE'
                    AND nom_service NOT LIKE 'Paramètres%'
                    AND nom_service NOT LIKE 'Consultation%'
                  ORDER BY nom_service ASC"
            )->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            // Fallback si colonne categorie absente
            try {
                $servicesCliniques = $this->db->query(
                    "SELECT id, nom_service FROM services
                      WHERE id IN (1,2,3,4,5,6,7)
                      ORDER BY nom_service ASC"
                )->fetchAll(PDO::FETCH_ASSOC);
            } catch (Exception $e2) {}
        }

        // Médecins pour le sélecteur d'admission
        $medecins = [];
        try {
            $medecins = $this->db->query(
                "SELECT id, nom, prenom FROM users
                  WHERE role IN ('MEDECIN','INFIRMIER_CONSULTANT')
                    AND actif = 1
                  ORDER BY nom, prenom ASC"
            )->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {}

        require_once __DIR__ . '/../views/hospitalisation/index.php';
    }

    /**
     * Admission directe d'un patient hospitalisé
     * POST /hospitalisation/admettre
     */
    public function admettre() {
        ob_start();
        $json = ['success' => false, 'message' => 'Erreur inconnue'];

        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Méthode non autorisée');
            }

            $patient_id   = (int)($_POST['patient_id']       ?? 0);
            $service_id   = (int)($_POST['service_id']       ?? 0);
            $lit_id       = (int)($_POST['lit_id']           ?? 0) ?: null;
            $medecin_id   = (int)($_POST['medecin_id']       ?? 0) ?: ($_SESSION['user_id'] ?? null);
            $motif        = trim($_POST['motif']              ?? '');
            $diagnostic   = trim($_POST['diagnostic']        ?? '');
            $date_admis   = trim($_POST['date_admission']    ?? '');
            $date_sortie  = trim($_POST['date_sortie_prevue'] ?? '');

            if (!$patient_id) throw new Exception('Veuillez sélectionner un patient.');
            if (!$service_id) throw new Exception('Veuillez sélectionner un service.');
            if (!$motif)      throw new Exception('Le motif d\'hospitalisation est requis.');

            // Vérifier que le patient n'est pas déjà hospitalisé
            $chk = $this->db->prepare(
                "SELECT id FROM hospitalisations WHERE patient_id = ? AND statut IN ('en_cours') LIMIT 1"
            );
            $chk->execute([$patient_id]);
            if ($chk->fetch()) {
                throw new Exception('Ce patient est déjà hospitalisé dans un service actif.');
            }

            $this->db->beginTransaction();

            $dateAdmis  = !empty($date_admis)  ? date('Y-m-d H:i:s', strtotime($date_admis))  : date('Y-m-d H:i:s');
            $dateSortie = !empty($date_sortie) ? date('Y-m-d', strtotime($date_sortie)) : null;

            // Créer l'hospitalisation
            $this->db->prepare("
                INSERT INTO hospitalisations
                    (patient_id, service_id, lit_id, medecin_responsable,
                     motif_hospitalisation, diagnostic_entree,
                     date_admission, date_sortie_prevue, statut)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'en_cours')
            ")->execute([
                $patient_id, $service_id, $lit_id, $medecin_id,
                $motif, $diagnostic ?: null,
                $dateAdmis, $dateSortie,
            ]);
            $hosp_id = $this->db->lastInsertId();

            // Occuper le lit si sélectionné
            if ($lit_id) {
                $this->db->prepare(
                    "UPDATE lits SET statut = 'OCCUPE', occupied_by_patient_id = ? WHERE id = ?"
                )->execute([$patient_id, $lit_id]);
            }

            // Mettre à jour le statut du patient
            $this->db->prepare(
                "UPDATE patients SET statut = 'HOSPITALISE', statut_hosp = 'HOSPITALISE', service_id = ? WHERE id = ?"
            )->execute([$service_id, $patient_id]);

            $this->db->commit();
            $json = [
                'success' => true,
                'message' => 'Admission enregistrée avec succès.',
                'hosp_id' => (int)$hosp_id,
            ];

        } catch (Exception $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            $json = ['success' => false, 'message' => $e->getMessage()];
        }

        ob_end_clean();
        header('Content-Type: application/json');
        echo json_encode($json);
        exit;
    }

    /**
     * Création d'un nouveau patient + admission immédiate sur lit
     * (Cas d'usage : patients hospitalisés AVANT le lancement du logiciel)
     * POST /hospitalisation/creer-et-admettre
     */
    public function creerEtAdmettre() {
        ob_start();
        $json = ['success' => false, 'message' => 'Erreur inconnue'];

        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Méthode non autorisée');
            }

            // ── Données patient ──
            $nom    = strtoupper(trim($_POST['nom']    ?? ''));
            $prenom = trim($_POST['prenom']             ?? '');
            $sexe   = trim($_POST['sexe']               ?? '');
            $ddn    = trim($_POST['date_naissance']     ?? '');
            $tel    = trim($_POST['telephone']          ?? '');
            $gs     = trim($_POST['groupe_sanguin']     ?? '') ?: null;
            $type   = trim($_POST['type_client']        ?? 'PAYANT_COMPTANT');
            $allowed_types = ['BON_PRISE_EN_CHARGE','PAYANT_COMPTANT','ASSURANCE','FAMILLE_PHP','AGENTS_PHP'];
            if (!in_array($type, $allowed_types)) $type = 'PAYANT_COMPTANT';

            // ── Données hospitalisation ──
            $service_id  = (int)($_POST['service_id']        ?? 0);
            $lit_id      = (int)($_POST['lit_id']            ?? 0) ?: null;
            $medecin_id  = (int)($_POST['medecin_id']        ?? 0) ?: ($_SESSION['user_id'] ?? null);
            $motif       = trim($_POST['motif']              ?? '');
            $diagnostic  = trim($_POST['diagnostic']         ?? '');
            $date_admis  = trim($_POST['date_admission']     ?? '');
            $date_sortie = trim($_POST['date_sortie_prevue'] ?? '');

            // ── Validations ──
            if (!$nom)        throw new Exception('Le nom du patient est requis.');
            if (!$prenom)     throw new Exception('Le prénom du patient est requis.');
            if (!$sexe)       throw new Exception('Le sexe du patient est requis.');
            if (!$service_id) throw new Exception('Veuillez sélectionner un service.');
            if (!$motif)      throw new Exception("Le motif d'hospitalisation est requis.");

            // Date de naissance : fallback si vide
            if (empty($ddn)) $ddn = '1900-01-01';

            $this->db->beginTransaction();

            // ── 1. Générer le numéro de dossier ──
            $annee2  = date('y');
            $prefix  = "HSJM$annee2";
            $stmtMax = $this->db->prepare(
                "SELECT COALESCE(MAX(CAST(SUBSTRING(dossier_numero, 7) AS UNSIGNED)), 0)
                 FROM patients WHERE dossier_numero LIKE ? FOR UPDATE"
            );
            $stmtMax->execute(["$prefix%"]);
            $next_id        = (int)$stmtMax->fetchColumn() + 1;
            $dossier_numero = $prefix . str_pad($next_id, 4, '0', STR_PAD_LEFT);

            // ── 2. Créer le patient ──
            $this->db->prepare("
                INSERT INTO patients
                    (dossier_numero, nom, prenom, date_naissance, sexe,
                     telephone, groupe_sanguin, type_client,
                     statut, statut_hosp, statut_parcours, circuit, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'HOSPITALISE', 'HOSPITALISE', 'HOSPITALISE', 'STANDARD', NOW())
            ")->execute([
                $dossier_numero, $nom, $prenom, $ddn, $sexe,
                $tel ?: null, $gs, $type,
            ]);
            $patient_id = (int)$this->db->lastInsertId();

            // ── 3. Créer l'hospitalisation ──
            $dateAdmis  = !empty($date_admis)  ? date('Y-m-d H:i:s', strtotime($date_admis))  : date('Y-m-d H:i:s');
            $dateSortie = !empty($date_sortie) ? date('Y-m-d', strtotime($date_sortie)) : null;

            $this->db->prepare("
                INSERT INTO hospitalisations
                    (patient_id, service_id, lit_id, medecin_responsable,
                     motif_hospitalisation, diagnostic_entree,
                     date_admission, date_sortie_prevue, statut)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'en_cours')
            ")->execute([
                $patient_id, $service_id, $lit_id, $medecin_id,
                $motif, $diagnostic ?: null,
                $dateAdmis, $dateSortie,
            ]);

            // ── 4. Occuper le lit ──
            if ($lit_id) {
                $this->db->prepare(
                    "UPDATE lits SET statut = 'OCCUPE', occupied_by_patient_id = ? WHERE id = ?"
                )->execute([$patient_id, $lit_id]);
            }

            // ── 5. Mettre à jour le service du patient ──
            $this->db->prepare(
                "UPDATE patients SET service_id = ? WHERE id = ?"
            )->execute([$service_id, $patient_id]);

            $this->db->commit();

            ob_end_clean();
            header('Content-Type: application/json');
            echo json_encode([
                'success'        => true,
                'message'        => "Patient $nom $prenom créé et admis (dossier $dossier_numero).",
                'patient_id'     => $patient_id,
                'dossier_numero' => $dossier_numero,
            ]);
            exit;

        } catch (Exception $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            ob_end_clean();
            error_log('[creerEtAdmettre] ' . $e->getMessage());
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            exit;
        }
    }

    /**
     * Changement de lit intra-service
     * POST /hospitalisation/changer-lit
     */
    public function changerLit() {
        ob_start();
        $json = ['success' => false, 'message' => 'Erreur inconnue'];

        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Méthode non autorisée');
            }

            $hosp_id        = (int)($_POST['hosp_id']        ?? 0);
            $nouveau_lit_id = (int)($_POST['nouveau_lit_id'] ?? 0);
            $ancien_lit_id  = (int)($_POST['ancien_lit_id']  ?? 0) ?: null;

            if (!$hosp_id || !$nouveau_lit_id) {
                throw new Exception('Données manquantes (hospitalisation ou lit).');
            }

            $this->db->beginTransaction();

            // Verrouiller le lit avec FOR UPDATE pour éviter la double occupation concurrente
            $stmtLit = $this->db->prepare(
                "SELECT id, statut FROM lits WHERE id = ? LIMIT 1 FOR UPDATE"
            );
            $stmtLit->execute([$nouveau_lit_id]);
            $lit = $stmtLit->fetch(PDO::FETCH_ASSOC);
            if (!$lit) throw new Exception('Lit introuvable.');
            if (strtoupper(trim($lit['statut'])) !== 'DISPONIBLE') {
                throw new Exception('Ce lit vient d\'être attribué à un autre patient. Veuillez en choisir un autre.');
            }

            // Récupérer patient_id et lit actuel depuis l'hospitalisation (verrouillé aussi)
            $stmtH = $this->db->prepare(
                "SELECT patient_id, lit_id FROM hospitalisations WHERE id = ? AND statut = 'en_cours' LIMIT 1 FOR UPDATE"
            );
            $stmtH->execute([$hosp_id]);
            $hosp = $stmtH->fetch(PDO::FETCH_ASSOC);
            if (!$hosp) throw new Exception('Hospitalisation active introuvable.');

            $patient_id   = (int)$hosp['patient_id'];
            $vieux_lit_id = (int)($hosp['lit_id'] ?: $ancien_lit_id);

            // Libérer l'ancien lit
            if ($vieux_lit_id) {
                $this->db->prepare(
                    "UPDATE lits SET statut = 'DISPONIBLE', occupied_by_patient_id = NULL WHERE id = ?"
                )->execute([$vieux_lit_id]);
            }

            // Occuper le nouveau lit
            $this->db->prepare(
                "UPDATE lits SET statut = 'OCCUPE', occupied_by_patient_id = ? WHERE id = ?"
            )->execute([$patient_id, $nouveau_lit_id]);

            // Mettre à jour l'hospitalisation
            $this->db->prepare(
                "UPDATE hospitalisations SET lit_id = ? WHERE id = ?"
            )->execute([$nouveau_lit_id, $hosp_id]);

            $this->db->commit();
            $json = ['success' => true, 'message' => 'Patient déplacé vers le nouveau lit avec succès.'];

        } catch (Exception $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            $json = ['success' => false, 'message' => $e->getMessage()];
        }

        ob_end_clean();
        header('Content-Type: application/json');
        echo json_encode($json);
        exit;
    }

    /**
     * Transfert d'un patient hospitalisé vers un autre service
     * POST /hospitalisation/transferer/{patient_id}
     */
    public function transferer(int $patientId) {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
            return;
        }

        $service_dest_id = (int)($_POST['service_id'] ?? 0);
        $lit_dest_id     = (int)($_POST['lit_id'] ?? 0) ?: null;
        $motif           = trim($_POST['motif'] ?? '');
        $infirmier_id    = $_SESSION['user_id'] ?? null;

        if (!$service_dest_id) {
            echo json_encode(['success' => false, 'message' => 'Service de destination requis']);
            return;
        }

        try {
            $this->db->beginTransaction();

            // 1. Trouver l'hospitalisation active
            $stmtH = $this->db->prepare(
                "SELECT id, service_id, lit_id FROM hospitalisations
                  WHERE patient_id = ? AND statut IN ('en_cours')
                  ORDER BY date_admission DESC LIMIT 1"
            );
            $stmtH->execute([$patientId]);
            $hosp = $stmtH->fetch(PDO::FETCH_ASSOC);

            if (!$hosp) {
                $this->db->rollBack();
                echo json_encode(['success' => false, 'message' => 'Aucune hospitalisation active trouvée']);
                return;
            }

            $old_hosp_id      = $hosp['id'];
            $service_orig_id  = $hosp['service_id'];
            $old_lit_id       = $hosp['lit_id'];

            // 2. Clôturer l'ancienne hospitalisation
            $this->db->prepare(
                "UPDATE hospitalisations
                    SET statut = 'termine', type_sortie = 'transfert',
                        date_sortie_effective = NOW()
                  WHERE id = ?"
            )->execute([$old_hosp_id]);

            // 3. Libérer l'ancien lit
            if ($old_lit_id) {
                $this->db->prepare(
                    "UPDATE lits SET statut = 'DISPONIBLE', occupied_by_patient_id = NULL
                      WHERE id = ?"
                )->execute([$old_lit_id]);
            }

            // 4. Créer la nouvelle hospitalisation dans le service de destination
            $this->db->prepare(
                "INSERT INTO hospitalisations
                    (patient_id, service_id, lit_id, medecin_responsable, motif_hospitalisation,
                     date_admission, statut)
                 VALUES (?, ?, ?, ?, ?, NOW(), 'en_cours')"
            )->execute([
                $patientId,
                $service_dest_id,
                $lit_dest_id,
                $_SESSION['user_id'] ?? null,
                $motif ?: 'Transfert de service'
            ]);
            $new_hosp_id = $this->db->lastInsertId();

            // 5. Occuper le nouveau lit si sélectionné
            if ($lit_dest_id) {
                $this->db->prepare(
                    "UPDATE lits SET statut = 'OCCUPE',
                            occupied_by_patient_id = ?,
                            occupied_since = NOW()
                      WHERE id = ?"
                )->execute([$patientId, $lit_dest_id]);
            }

            // 6. Mettre à jour le service du patient
            // statut_hosp = 'A_HOSPITALISER' pour que l'infirmier du service destination
            // voie le patient dans sa file "Patients à installer" et confirme la réception.
            $this->db->prepare(
                "UPDATE patients SET service_id = ?, statut_hosp = 'A_HOSPITALISER', statut_parcours = 'HOSPITALISE' WHERE id = ?"
            )->execute([$service_dest_id, $patientId]);

            // 7. Enregistrer dans transferts_patients
            $this->db->prepare(
                "INSERT INTO transferts_patients
                    (patient_id, service_origine_id, service_destination_id,
                     lit_destination_id, hospitalisation_id, motif, infirmier_id, date_transfert)
                 VALUES (?, ?, ?, ?, ?, ?, ?, NOW())"
            )->execute([
                $patientId,
                $service_orig_id,
                $service_dest_id,
                $lit_dest_id,
                $new_hosp_id,
                $motif,
                $infirmier_id
            ]);

            $this->db->commit();

            // 8. Notifier les médecins du service de destination
            try {
                // Nom du patient
                $stmtPat = $this->db->prepare(
                    "SELECT nom, prenom FROM patients WHERE id = ? LIMIT 1"
                );
                $stmtPat->execute([$patientId]);
                $pat = $stmtPat->fetch(PDO::FETCH_ASSOC);
                $nomPatient = $pat ? strtoupper($pat['nom']) . ' ' . ucfirst(strtolower($pat['prenom'])) : "Patient #$patientId";

                // Nom du service de destination
                $stmtSrv = $this->db->prepare(
                    "SELECT nom_service FROM services WHERE id = ? LIMIT 1"
                );
                $stmtSrv->execute([$service_dest_id]);
                $srv = $stmtSrv->fetch(PDO::FETCH_ASSOC);
                $nomServiceDest = $srv['nom_service'] ?? 'Votre service';

                // Médecins actifs du service de destination
                $stmtMed = $this->db->prepare(
                    "SELECT id FROM users
                      WHERE service_id = ?
                        AND role IN ('MEDECIN','MEDECIN_CHEF','SPECIALISTE','GYNECO','PEDIATRE','CHIRURGIEN')
                        AND actif = 1"
                );
                $stmtMed->execute([$service_dest_id]);
                $medecins = $stmtMed->fetchAll(PDO::FETCH_COLUMN);

                if (!empty($medecins)) {
                    $stmtNotif = $this->db->prepare(
                        "INSERT INTO notifications_medecin
                            (medecin_id, patient_id, type, titre, message, demande_id, lu)
                         VALUES (?, ?, 'AUTRE', ?, ?, NULL, 0)"
                    );
                    $titre   = "Transfert entrant : $nomPatient";
                    $message = "Le patient $nomPatient a été transféré dans le service « $nomServiceDest »."
                             . ($motif ? " Motif : $motif." : '');
                    foreach ($medecins as $medecinId) {
                        $stmtNotif->execute([$medecinId, $patientId, $titre, $message]);
                    }
                }
            } catch (Exception $eNotif) {
                // Les notifications ne doivent pas faire échouer le transfert
                error_log("Notification transfert erreur: " . $eNotif->getMessage());
            }

            echo json_encode(['success' => true, 'message' => 'Transfert effectué avec succès']);

        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Transfert patient erreur: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Erreur : ' . $e->getMessage()]);
        }
    }

    /**
     * JSON POST : Libérer le lit d'un patient hospitalisé (sortie / clôture hospit)
     * POST /hospitalisation/liberer-lit/{patient_id}
     * Body JSON : { motif_sortie: string }
     */
    public function libererLit(int $patientId): void {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Méthode non autorisée.']); exit;
        }

        $input       = json_decode(file_get_contents('php://input'), true);
        $motifRaw    = trim($input['motif_sortie'] ?? 'guerison');

        // Mapping labels → valeurs ENUM(guerison, amelioration, transfert, deces, fuite, abandon)
        $motifMap = [
            'Sortie normale'                       => 'guerison',
            'Sortie contre avis médical'           => 'abandon',
            'Transfert vers autre établissement'   => 'transfert',
            'Évasion de l\'hôpital'                => 'fuite',
            'Décès'                                => 'deces',
            // valeurs ENUM directes acceptées telles quelles
            'guerison'    => 'guerison',
            'amelioration'=> 'amelioration',
            'transfert'   => 'transfert',
            'deces'       => 'deces',
            'fuite'       => 'fuite',
            'abandon'     => 'abandon',
        ];
        $motifSortie = $motifMap[$motifRaw] ?? 'guerison';

        try {
            // 1. Trouver l'hospitalisation active du patient
            $stmtH = $this->db->prepare(
                "SELECT id, lit_id, service_id FROM hospitalisations
                  WHERE patient_id = ? AND statut = 'en_cours'
                  ORDER BY date_admission DESC LIMIT 1"
            );
            $stmtH->execute([$patientId]);
            $hosp = $stmtH->fetch(PDO::FETCH_ASSOC);

            if (!$hosp) {
                echo json_encode(['success' => false, 'message' => 'Aucune hospitalisation active trouvée.']); exit;
            }

            $this->db->beginTransaction();

            // 2. Clôturer l'hospitalisation
            $this->db->prepare(
                "UPDATE hospitalisations
                    SET statut               = 'termine',
                        type_sortie          = ?,
                        date_sortie_effective = NOW()
                  WHERE id = ?"
            )->execute([$motifSortie, $hosp['id']]);

            // 3. Libérer le lit
            if ($hosp['lit_id']) {
                $this->db->prepare(
                    "UPDATE lits
                        SET statut                  = 'DISPONIBLE',
                            occupied_by_patient_id  = NULL,
                            occupied_since          = NULL
                      WHERE id = ?"
                )->execute([$hosp['lit_id']]);
            }

            // 4. Mettre à jour le statut du patient
            $this->db->prepare(
                "UPDATE patients
                    SET statut_hosp     = 'SORTIE_RECENTE',
                        statut_parcours = 'SORTI'
                  WHERE id = ?"
            )->execute([$patientId]);

            // 5. Clôturer l'entrée urgences_patients pour éviter toute réapparition dans "À Hospitaliser"
            $this->db->prepare(
                "UPDATE urgences_patients
                    SET statut = 'TERMINE', heure_prise_charge = COALESCE(heure_prise_charge, NOW())
                  WHERE patient_id = ? AND statut = 'HOSPITALISE'"
            )->execute([$patientId]);

            $this->db->commit();
            echo json_encode([
                'success' => true,
                'message' => 'Le patient a été sorti et le lit est libéré.',
            ]);

        } catch (Exception $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            error_log("libererLit erreur: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Erreur : ' . $e->getMessage()]);
        }
        exit;
    }

    /**
     * POST /hospitalisation/readmettre/{hosp_id}
     * Réadmet un patient dont la sortie a été faite par erreur :
     *   - Remet l'hospitalisation en statut 'en_cours'
     *   - Réoccupe le lit d'origine s'il est disponible, sinon lit_id = NULL
     *   - Remet le patient en statut HOSPITALISE
     */
    public function readmettre(int $hospId): void {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Méthode non autorisée.']); exit;
        }

        $input  = json_decode(file_get_contents('php://input'), true) ?? [];
        $motif  = trim($input['motif'] ?? 'Réadmission suite à sortie par erreur');

        try {
            // 1. Récupérer l'hospitalisation terminée
            $stmtH = $this->db->prepare(
                "SELECT h.*, p.nom, p.prenom
                 FROM hospitalisations h
                 JOIN patients p ON h.patient_id = p.id
                 WHERE h.id = ? AND h.statut = 'termine'
                 LIMIT 1"
            );
            $stmtH->execute([$hospId]);
            $hosp = $stmtH->fetch(PDO::FETCH_ASSOC);

            if (!$hosp) {
                echo json_encode(['success' => false, 'message' => 'Hospitalisation introuvable ou déjà active.']); exit;
            }

            // 2. Vérifier que le patient n'a pas déjà une hospitalisation active
            $stmtActif = $this->db->prepare(
                "SELECT id FROM hospitalisations WHERE patient_id = ? AND statut = 'en_cours' LIMIT 1"
            );
            $stmtActif->execute([$hosp['patient_id']]);
            if ($stmtActif->fetchColumn()) {
                echo json_encode(['success' => false, 'message' => 'Ce patient est déjà hospitalisé.']); exit;
            }

            $this->db->beginTransaction();

            // 3. Vérifier si le lit d'origine est encore disponible (FOR UPDATE = verrou anti-doublon)
            $litId = $hosp['lit_id'];
            if ($litId) {
                $stmtLit = $this->db->prepare("SELECT statut FROM lits WHERE id = ? LIMIT 1 FOR UPDATE");
                $stmtLit->execute([$litId]);
                $litStatut = $stmtLit->fetchColumn();
                if ($litStatut !== 'DISPONIBLE') {
                    $litId = null; // Lit pris → réadmission sans lit, à assigner manuellement
                }
            }

            // 4. Réouvrir l'hospitalisation
            $this->db->prepare(
                "UPDATE hospitalisations
                    SET statut                = 'en_cours',
                        type_sortie           = NULL,
                        date_sortie_effective = NULL,
                        date_sortie_prevue    = NULL,
                        lit_id                = ?,
                        motif_hospitalisation = CONCAT(COALESCE(motif_hospitalisation,''), ' | ', ?)
                  WHERE id = ?"
            )->execute([$litId ?: $hosp['lit_id'], $motif, $hospId]);

            // 5. Réoccuper le lit si disponible
            if ($litId) {
                $this->db->prepare(
                    "UPDATE lits
                        SET statut                 = 'OCCUPE',
                            occupied_by_patient_id = ?,
                            occupied_since         = NOW()
                      WHERE id = ?"
                )->execute([$hosp['patient_id'], $litId]);
            }

            // 6. Remettre le patient en HOSPITALISE
            $this->db->prepare(
                "UPDATE patients
                    SET statut          = 'HOSPITALISE',
                        statut_hosp     = 'HOSPITALISE',
                        statut_parcours = 'HOSPITALISE',
                        service_id      = ?
                  WHERE id = ?"
            )->execute([$hosp['service_id'], $hosp['patient_id']]);

            $this->db->commit();

            $msg = 'Patient réadmis avec succès.';
            if (!$litId && $hosp['lit_id']) {
                $msg .= ' Le lit d\'origine était occupé — veuillez en assigner un nouveau.';
            }
            echo json_encode(['success' => true, 'message' => $msg, 'lit_libre' => (bool)$litId]);

        } catch (Exception $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            error_log('readmettre erreur: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Erreur : ' . $e->getMessage()]);
        }
        exit;
    }

    /**
     * Assigne un lit à un patient déjà hospitalisé (sans créer de nouvelle hospitalisation).
     * Utile lorsque le patient a été admis sans lit défini, et qu'il faut maintenant
     * lui en attribuer un.
     *
     * POST /hospitalisation/assigner-lit/{patientId}
     * Body : { lit_id: int }
     */
    public function assignerLit(int $patientId): void {
        ob_start();
        $json = ['success' => false, 'message' => 'Erreur inconnue'];

        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Méthode non autorisée');
            }

            $body  = json_decode(file_get_contents('php://input'), true) ?: [];
            $litId = (int)($body['lit_id'] ?? $_POST['lit_id'] ?? 0);

            if (!$patientId) throw new Exception('Patient invalide.');
            if (!$litId)     throw new Exception('Veuillez sélectionner un lit.');

            // 1. Trouver l'hospitalisation en cours du patient
            $stmt = $this->db->prepare(
                "SELECT id, service_id, lit_id FROM hospitalisations
                 WHERE patient_id = ? AND statut IN ('en_cours')
                 ORDER BY id DESC LIMIT 1"
            );
            $stmt->execute([$patientId]);
            $hosp = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$hosp) throw new Exception("Aucune hospitalisation active pour ce patient.");
            if (!empty($hosp['lit_id'])) {
                throw new Exception("Ce patient occupe déjà un lit. Pour le déplacer, utilisez « Transférer ».");
            }

            // 2. Vérifier que le lit existe, est libre et appartient au bon service
            $stmtL = $this->db->prepare(
                "SELECT l.id, l.nom_lit, l.statut, c.service_id, c.nom_chambre
                 FROM lits l
                 LEFT JOIN chambres c ON l.chambre_id = c.id
                 WHERE l.id = ?"
            );
            $stmtL->execute([$litId]);
            $lit = $stmtL->fetch(PDO::FETCH_ASSOC);
            if (!$lit) throw new Exception("Lit introuvable.");
            $statutLit = strtoupper(trim($lit['statut']));
            if (!in_array($statutLit, ['DISPONIBLE','LIBRE','AVAILABLE','FREE'])) {
                throw new Exception("Ce lit n'est pas disponible (statut : {$lit['statut']}).");
            }
            if ((int)$lit['service_id'] !== (int)$hosp['service_id']) {
                throw new Exception("Le lit appartient à un service différent. Utilisez « Transférer » pour changer de service.");
            }

            $this->db->beginTransaction();

            // 3. Mettre à jour l'hospitalisation
            $this->db->prepare(
                "UPDATE hospitalisations SET lit_id = ? WHERE id = ?"
            )->execute([$litId, (int)$hosp['id']]);

            // 4. Marquer le lit comme occupé
            $this->db->prepare(
                "UPDATE lits SET statut = 'OCCUPE', occupied_by_patient_id = ? WHERE id = ?"
            )->execute([$patientId, $litId]);

            // 5. Audit
            try {
                require_once __DIR__ . '/../services/AuditService.php';
                (new AuditService())->logAction(
                    'UPDATE', 'hospitalisations', (int)$hosp['id'], null,
                    "Lit attribué : {$lit['nom_chambre']} — {$lit['nom_lit']} (patient #$patientId)"
                );
            } catch (\Throwable $e) { /* ignorer si AuditService manquant */ }

            $this->db->commit();
            $json = [
                'success' => true,
                'message' => "✅ Patient admis sur le lit {$lit['nom_lit']} ({$lit['nom_chambre']}).",
                'lit'     => [
                    'id'         => (int)$lit['id'],
                    'nom_lit'    => $lit['nom_lit'],
                    'nom_chambre'=> $lit['nom_chambre'],
                ],
            ];
        } catch (Exception $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            $json = ['success' => false, 'message' => $e->getMessage()];
        }

        ob_end_clean();
        header('Content-Type: application/json');
        echo json_encode($json);
        exit;
    }

    /**
     * Retourne les lits disponibles d'un service (AJAX JSON)
     * GET /hospitalisation/lits-disponibles?service_id=X
     */
    public function litsDisponibles() {
        header('Content-Type: application/json');
        $service_id = (int)($_GET['service_id'] ?? 0);
        if (!$service_id) {
            echo json_encode([]);
            return;
        }
        try {
            // Le service est sur chambres.service_id (pas directement sur lits)
            // On accepte aussi l.service_id comme fallback si la colonne existe
            $stmt = $this->db->prepare(
                "SELECT l.id, l.nom_lit, c.nom_chambre
                 FROM lits l
                 LEFT JOIN chambres c ON l.chambre_id = c.id
                 WHERE (c.service_id = ? OR l.service_id = ?)
                   AND UPPER(TRIM(l.statut)) IN ('DISPONIBLE', 'LIBRE', 'AVAILABLE', 'FREE')
                 ORDER BY c.nom_chambre, l.nom_lit ASC"
            );
            $stmt->execute([$service_id, $service_id]);
            $lits = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Débogage temporaire : si toujours vide, renvoyer tous les statuts distincts
            if (empty($lits) && isset($_GET['debug'])) {
                $d = $this->db->prepare(
                    "SELECT DISTINCT l.statut, c.service_id, l.service_id as lit_service_id
                     FROM lits l LEFT JOIN chambres c ON l.chambre_id = c.id LIMIT 20"
                );
                $d->execute();
                echo json_encode(['debug' => $d->fetchAll(PDO::FETCH_ASSOC)]);
                return;
            }

            echo json_encode($lits);
        } catch (Exception $e) {
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    public function dossier($patient_id) {
        // Dossier complet du patient hospitalisé
        $patient = $this->getPatientHospitalise($patient_id);
        $traitements = $this->getTraitementsActifs($patient_id);
        $constantes = $this->getConstantesRecentes($patient_id);
        $prescriptions = $this->getPrescriptionsHospitalisation($patient_id);

        require_once __DIR__ . '/../views/hospitalisation/dossier.php';
    }

    public function administrerTraitement() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = json_decode(file_get_contents('php://input'), true);

            $stmt = $this->db->prepare("INSERT INTO administrations_medicaments
                (prescription_id, patient_id, medicament_id, dose_administree, heure_administration, infirmier_id, observations)
                VALUES (?, ?, ?, ?, NOW(), ?, ?)
            ");

            $success = $stmt->execute([
                $data['prescription_id'],
                $data['patient_id'],
                $data['medicament_id'],
                $data['dose'],
                $_SESSION['user_id'],
                $data['observations'] ?? ''
            ]);

            header('Content-Type: application/json');
            echo json_encode(['success' => $success]);
        }
    }

    public function ajouterConstantes() {
        ob_start();
        $isAjax = false;

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            ob_end_clean();
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
            exit;
        }

        try {
            // Détection JSON (AJAX) vs formulaire classique
            $rawBody = file_get_contents('php://input');
            $data    = $rawBody ? json_decode($rawBody, true) : null;
            $isAjax  = !empty($data);
            $p       = $isAjax ? $data : $_POST;

            $patient_id = (int)($p['patient_id'] ?? 0);
            if (!$patient_id) {
                throw new Exception('Patient introuvable (ID manquant). Veuillez recharger la page.');
            }

            $this->db->prepare("
                INSERT INTO patient_parametres
                    (patient_id, temperature,
                     pression_arterielle_systolique, pression_arterielle_diastolique,
                     frequence_cardiaque, saturation_oxygene,
                     glycemie, frequence_respiratoire,
                     diurese, sous_oxygene, debit_oxygene,
                     observations, plaintes, date_mesure, user_id)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?)
            ")->execute([
                $patient_id,
                !empty($p['temperature'])          ? (float)$p['temperature']          : null,
                !empty($p['tension_sys'])           ? (int)$p['tension_sys']            : null,
                !empty($p['tension_dia'])           ? (int)$p['tension_dia']            : null,
                !empty($p['frequence_cardiaque'])   ? (int)$p['frequence_cardiaque']    : null,
                !empty($p['spo2'])                  ? (int)$p['spo2']                   : null,
                !empty($p['glycemie'])              ? (float)$p['glycemie']             : null,
                !empty($p['frequence_respiratoire'])? (int)$p['frequence_respiratoire'] : null,
                !empty($p['diurese'])               ? (int)$p['diurese']                : null,
                !empty($p['sous_oxygene'])          ? 1 : 0,
                !empty($p['debit_oxygene'])         ? (float)$p['debit_oxygene']        : null,
                !empty($p['observations'])          ? trim($p['observations'])          : null,
                !empty($p['plaintes'])              ? trim($p['plaintes'])              : null,
                $_SESSION['user_id'] ?? null,
            ]);

            ob_end_clean();

            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'message' => 'Paramètres enregistrés avec succès.']);
            } else {
                header('Location: ' . BASE_URL . 'hospitalisation/suivi/' . $patient_id . '?success=constantes_ajoutees');
            }
            exit;

        } catch (Exception $e) {
            ob_end_clean();
            error_log('[ajouterConstantes] ' . $e->getMessage());
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            } else {
                $pid = (int)($_POST['patient_id'] ?? 0);
                header('Location: ' . BASE_URL . 'hospitalisation/suivi/' . $pid . '?error=' . urlencode($e->getMessage()));
            }
            exit;
        }
    }
    /* ══════════════════════════════════════════════════════════════════
     * POST hospitalisation/update-constantes  (JSON)
     * Modifie une fiche de paramètres existante (patient_parametres)
     * Réservé : auteur de la fiche, ADMIN, MEDECIN, MAJOR_INFIRMIER
     * ══════════════════════════════════════════════════════════════════ */
    public function updateConstantes(): void {
        header('Content-Type: application/json');
        try {
            $raw  = file_get_contents('php://input');
            $data = $raw ? json_decode($raw, true) : null;
            if (!$data) throw new \Exception('Données invalides.');

            $id = (int)($data['id'] ?? 0);
            if (!$id) throw new \Exception('Identifiant de fiche manquant.');

            // Vérifier existence + droits
            $stmt = $this->db->prepare("SELECT user_id FROM patient_parametres WHERE id = ?");
            $stmt->execute([$id]);
            $fiche = $stmt->fetch(\PDO::FETCH_ASSOC);
            if (!$fiche) throw new \Exception('Fiche introuvable.');

            $userId   = (int)($_SESSION['user_id'] ?? 0);
            $userRole = strtoupper($_SESSION['user_role'] ?? '');
            $peutEditer = ($fiche['user_id'] == $userId)
                       || in_array($userRole, ['ADMIN','MEDECIN','MAJOR_INFIRMIER','MEDECIN_CHEF']);
            if (!$peutEditer) throw new \Exception('Modification non autorisée pour ce compte.');

            $this->db->prepare("
                UPDATE patient_parametres SET
                    temperature                     = ?,
                    frequence_cardiaque             = ?,
                    saturation_oxygene              = ?,
                    pression_arterielle_systolique  = ?,
                    pression_arterielle_diastolique = ?,
                    frequence_respiratoire          = ?,
                    glycemie                        = ?,
                    diurese                         = ?,
                    sous_oxygene                    = ?,
                    debit_oxygene                   = ?,
                    observations                    = ?,
                    plaintes                        = ?
                WHERE id = ?
            ")->execute([
                !empty($data['temperature'])            ? (float)str_replace(',','.',$data['temperature'])   : null,
                !empty($data['frequence_cardiaque'])     ? (int)$data['frequence_cardiaque']                 : null,
                !empty($data['spo2'])                    ? (int)$data['spo2']                                : null,
                !empty($data['tension_sys'])             ? (int)$data['tension_sys']                         : null,
                !empty($data['tension_dia'])             ? (int)$data['tension_dia']                         : null,
                !empty($data['frequence_respiratoire'])  ? (int)$data['frequence_respiratoire']              : null,
                !empty($data['glycemie'])                ? (float)str_replace(',','.',$data['glycemie'])     : null,
                !empty($data['diurese'])                 ? (int)$data['diurese']                             : null,
                isset($data['sous_oxygene'])             ? (int)$data['sous_oxygene']                        : 0,
                !empty($data['debit_oxygene'])           ? (float)$data['debit_oxygene']                    : null,
                trim($data['observations'] ?? '') ?: null,
                trim($data['plaintes']     ?? '') ?: null,
                $id,
            ]);

            echo json_encode(['success' => true, 'message' => 'Fiche modifiée avec succès.']);
        } catch (\Exception $e) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

 public function planifierSoin() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $db = (new Database())->getConnection();

        // ── Migration auto des colonnes conditionnelles ──
        $this->_migrerColonnesConditionnelles($db);

        // 1. Récupération des données de base
        $admission_id = $_POST['admission_id'];
        $patient_id   = $_POST['patient_id'];
        $type_soin    = $_POST['type_soin'];
        $description  = $_POST['description'];
        $condition    = $_POST['condition_application'] ?? null;
        $date_prevue  = $_POST['date_prevue'];

        // 2. Champs conditionnels
        $avec_condition   = !empty($_POST['avec_condition']);
        $parametre        = $avec_condition ? (trim($_POST['parametre_surveille']    ?? '')) ?: null : null;
        $valeur_cible     = $avec_condition && isset($_POST['valeur_cible'])    ? (float)$_POST['valeur_cible']    : null;
        $operateur        = $avec_condition ? (trim($_POST['operateur_condition'] ?? '')) ?: null : null;
        $action           = $avec_condition ? (trim($_POST['action_si_atteint']  ?? '')) ?: null : null;
        $nouveau_trait    = ($action === 'CHANGER') ? (trim($_POST['nouveau_traitement'] ?? '')) ?: null : null;
        $freq_h           = $avec_condition && !empty($_POST['frequence_surveillance_h'])
                            ? (int)$_POST['frequence_surveillance_h'] : null;

        $stmt = $db->prepare("
            INSERT INTO soins_hospitalisation
            (admission_id, user_id_planificateur, type_soin, description,
             condition_application, date_prevue, statut,
             parametre_surveille, valeur_cible, operateur_condition,
             action_si_atteint, nouveau_traitement, frequence_surveillance_h)
            VALUES (?, ?, ?, ?, ?, ?, 'PLANIFIE', ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $admission_id,
            $_SESSION['user_id'],
            $type_soin,
            $description,
            $condition ?: null,
            $date_prevue,
            $parametre,
            $valeur_cible,
            $operateur,
            $action,
            $nouveau_trait,
            $freq_h,
        ]);

        if (!empty($_POST['patient_id'])) {
            header('Location: ' . BASE_URL . 'hospitalisation/suivi/' . $_POST['patient_id'] . '?success=soin_planifie');
        } else {
            header('Location: ' . BASE_URL . 'hospitalisation');
        }
        exit;
    }
}

/**
 * Migration auto : ajouter les colonnes conditionnelles si absentes
 */
private function _migrerColonnesConditionnelles(\PDO $db): void {
    try {
        $cols = array_column($db->query("SHOW COLUMNS FROM soins_hospitalisation")->fetchAll(\PDO::FETCH_ASSOC), 'Field');
        $toAdd = [];
        if (!in_array('parametre_surveille',     $cols)) $toAdd[] = "ADD COLUMN parametre_surveille     VARCHAR(50)  DEFAULT NULL";
        if (!in_array('valeur_cible',            $cols)) $toAdd[] = "ADD COLUMN valeur_cible            DECIMAL(7,2) DEFAULT NULL";
        if (!in_array('operateur_condition',     $cols)) $toAdd[] = "ADD COLUMN operateur_condition     VARCHAR(5)   DEFAULT NULL";
        if (!in_array('action_si_atteint',       $cols)) $toAdd[] = "ADD COLUMN action_si_atteint       VARCHAR(20)  DEFAULT NULL";
        if (!in_array('nouveau_traitement',      $cols)) $toAdd[] = "ADD COLUMN nouveau_traitement      TEXT         DEFAULT NULL";
        if (!in_array('frequence_surveillance_h',$cols)) $toAdd[] = "ADD COLUMN frequence_surveillance_h INT         DEFAULT NULL";
        if (!in_array('condition_atteinte',      $cols)) $toAdd[] = "ADD COLUMN condition_atteinte      TINYINT(1)   DEFAULT 0";
        if (!in_array('date_condition_atteinte', $cols)) $toAdd[] = "ADD COLUMN date_condition_atteinte DATETIME     DEFAULT NULL";
        if (!in_array('note_execution',          $cols)) $toAdd[] = "ADD COLUMN note_execution          TEXT         DEFAULT NULL";
        if (!empty($toAdd)) {
            $db->exec("ALTER TABLE soins_hospitalisation " . implode(', ', $toAdd));
        }
        // Étendre l'enum statut si nécessaire
        try {
            $db->exec("ALTER TABLE soins_hospitalisation MODIFY COLUMN statut
                ENUM('PLANIFIE','REALISE','ANNULE','RETARD','SUPPRIME','STOPPE','REMPLACE') DEFAULT 'PLANIFIE'");
        } catch (\Throwable $e) { /* déjà bon */ }
    } catch (\Throwable $e) { /* silencieux */ }
}

/**
 * GET /hospitalisation/verifier-condition/{soin_id}
 * Vérifie si la condition paramétrique d'un soin est atteinte.
 */
public function verifierConditionSoin(int $soinId): void {
    header('Content-Type: application/json');
    $db = $this->db;
    $this->_migrerColonnesConditionnelles($db);

    try {
        // Charger le soin + patient_id via hospitalisation
        $stmt = $db->prepare("
            SELECT sh.*,
                   h.patient_id
            FROM soins_hospitalisation sh
            JOIN hospitalisations h ON h.id = sh.admission_id
            WHERE sh.id = ? LIMIT 1
        ");
        $stmt->execute([$soinId]);
        $soin = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$soin) {
            echo json_encode(['success' => false, 'message' => 'Soin introuvable.']); exit;
        }

        // Pas de condition définie → validation normale
        if (empty($soin['parametre_surveille'])) {
            echo json_encode(['success' => true, 'avec_condition' => false]); exit;
        }

        $patientId = (int)$soin['patient_id'];

        // Correspondance clé → colonne patient_parametres
        $parametresMap = [
            'temperature'             => 'temperature',
            'pouls'                   => 'frequence_cardiaque',
            'tension_sys'             => 'pression_arterielle_systolique',
            'tension_dia'             => 'pression_arterielle_diastolique',
            'saturation_o2'           => 'saturation_oxygene',
            'frequence_respiratoire'  => 'frequence_respiratoire',
            'glycemie'                => 'glycemie',
        ];
        $labelMap = [
            'temperature'             => 'Température (°C)',
            'pouls'                   => 'Fréquence cardiaque (bpm)',
            'tension_sys'             => 'TA Systolique (mmHg)',
            'tension_dia'             => 'TA Diastolique (mmHg)',
            'saturation_o2'           => 'SpO2 (%)',
            'frequence_respiratoire'  => 'FR (/min)',
            'glycemie'                => 'Glycémie (g/L)',
        ];

        $col = $parametresMap[$soin['parametre_surveille']] ?? null;
        if (!$col) {
            echo json_encode(['success' => true, 'avec_condition' => false, 'message' => 'Paramètre inconnu.']); exit;
        }

        // Récupérer la dernière mesure
        $stmtV = $db->prepare("SELECT `$col` AS valeur, date_mesure FROM patient_parametres
                                WHERE patient_id = ? AND `$col` IS NOT NULL
                                ORDER BY date_mesure DESC LIMIT 1");
        $stmtV->execute([$patientId]);
        $mesure = $stmtV->fetch(\PDO::FETCH_ASSOC);

        if (!$mesure || $mesure['valeur'] === null) {
            echo json_encode([
                'success'       => true,
                'avec_condition'=> true,
                'atteinte'      => false,
                'valeur_actuelle'=> null,
                'label_param'   => $labelMap[$soin['parametre_surveille']] ?? $soin['parametre_surveille'],
                'valeur_cible'  => $soin['valeur_cible'],
                'operateur'     => $soin['operateur_condition'],
                'action'        => $soin['action_si_atteint'],
                'nouveau_traitement' => $soin['nouveau_traitement'],
                'frequence_h'   => $soin['frequence_surveillance_h'],
                'date_mesure'   => null,
                'message'       => 'Aucune mesure disponible pour ce paramètre.',
            ]);
            exit;
        }

        $valeurActuelle = (float)$mesure['valeur'];
        $valeurCible    = (float)$soin['valeur_cible'];
        $op             = $soin['operateur_condition'];

        $atteinte = match($op) {
            '<='  => $valeurActuelle <= $valeurCible,
            '>='  => $valeurActuelle >= $valeurCible,
            '='   => abs($valeurActuelle - $valeurCible) < 0.01,
            '<'   => $valeurActuelle < $valeurCible,
            '>'   => $valeurActuelle > $valeurCible,
            default => false,
        };

        echo json_encode([
            'success'           => true,
            'avec_condition'    => true,
            'atteinte'          => $atteinte,
            'valeur_actuelle'   => $valeurActuelle,
            'label_param'       => $labelMap[$soin['parametre_surveille']] ?? $soin['parametre_surveille'],
            'valeur_cible'      => $soin['valeur_cible'],
            'operateur'         => $op,
            'action'            => $soin['action_si_atteint'],
            'nouveau_traitement'=> $soin['nouveau_traitement'],
            'frequence_h'       => $soin['frequence_surveillance_h'],
            'date_mesure'       => $mesure['date_mesure'],
        ]);
    } catch (\Throwable $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

/**
 * POST /hospitalisation/appliquer-action-condition
 * Applique l'action (STOPPER ou CHANGER) quand la condition est atteinte.
 */
public function appliquerActionCondition(): void {
    header('Content-Type: application/json');
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['success' => false, 'message' => 'POST requis']); exit;
    }

    $db     = $this->db;
    $soinId = (int)($_POST['soin_id'] ?? 0);
    $action = trim($_POST['action'] ?? ''); // STOPPER ou CHANGER
    $note   = trim($_POST['note']   ?? '');

    if (!$soinId || !in_array($action, ['STOPPER','CHANGER'])) {
        echo json_encode(['success' => false, 'message' => 'Paramètres invalides.']); exit;
    }

    try {
        $db->beginTransaction();

        // Charger le soin
        $soin = $db->prepare("SELECT * FROM soins_hospitalisation WHERE id = ? LIMIT 1");
        $soin->execute([$soinId]);
        $soin = $soin->fetch(\PDO::FETCH_ASSOC);
        if (!$soin) throw new \Exception('Soin introuvable.');

        if ($action === 'STOPPER') {
            // Marquer comme STOPPE + marquer condition atteinte
            $db->prepare("UPDATE soins_hospitalisation
                SET statut = 'STOPPE',
                    date_realisee = NOW(),
                    user_id_executant = ?,
                    note_execution = ?,
                    condition_atteinte = 1,
                    date_condition_atteinte = NOW()
                WHERE id = ?")
               ->execute([$_SESSION['user_id'], $note ?: 'Condition paramétrique atteinte — soin stoppé.', $soinId]);

            echo json_encode(['success' => true, 'action' => 'STOPPE', 'message' => 'Soin stoppé — condition paramétrique atteinte.']);

        } else { // CHANGER
            $nouveauTraitement = trim($_POST['nouveau_traitement'] ?? $soin['nouveau_traitement'] ?? '');
            if (!$nouveauTraitement) throw new \Exception('Le nouveau traitement est requis.');

            // 1. Marquer l'ancien soin comme REMPLACE
            $db->prepare("UPDATE soins_hospitalisation
                SET statut = 'REMPLACE',
                    date_realisee = NOW(),
                    user_id_executant = ?,
                    note_execution = ?,
                    condition_atteinte = 1,
                    date_condition_atteinte = NOW()
                WHERE id = ?")
               ->execute([$_SESSION['user_id'], $note ?: 'Condition atteinte — traitement changé.', $soinId]);

            // 2. Créer le nouveau soin
            $db->prepare("INSERT INTO soins_hospitalisation
                (admission_id, user_id_planificateur, type_soin, description,
                 date_prevue, statut, condition_application)
                VALUES (?, ?, ?, ?, NOW(), 'PLANIFIE', ?)")
               ->execute([
                   $soin['admission_id'],
                   $_SESSION['user_id'],
                   $soin['type_soin'],
                   $nouveauTraitement,
                   'Changement suite à condition paramétrique atteinte (remplace soin #' . $soinId . ')',
               ]);
            $newId = $db->lastInsertId();

            echo json_encode(['success' => true, 'action' => 'REMPLACE', 'new_soin_id' => $newId,
                              'message' => 'Traitement changé — nouveau soin créé.']);
        }

        $db->commit();
    } catch (\Throwable $e) {
        if ($db->inTransaction()) $db->rollBack();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

    private function getPatientHospitalise($patient_id) {
        $stmt = $this->db->prepare("SELECT p.*, h.*, s.nom_service as service_nom, l.numero as lit_numero
            FROM patients p
            JOIN hospitalisations h ON p.id = h.patient_id
            LEFT JOIN services s ON h.service_id = s.id
            LEFT JOIN lits l ON h.lit_id = l.id
            WHERE p.id = ? AND h.statut = 'en_cours'
        ");
        $stmt->execute([$patient_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    private function getTraitementsActifs($patient_id) {
        $stmt = $this->db->prepare("SELECT ph.*, m.nom as medicament_nom, m.forme, m.dosage
            FROM prescriptions_hospitalisation ph
            JOIN medicaments m ON ph.medicament_id = m.id
            WHERE ph.patient_id = ? AND ph.statut = 'en_cours'
            ORDER BY ph.heure_debut
        ");
        $stmt->execute([$patient_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function getConstantesRecentes($patient_id) {
       $stmt = $this->db->prepare("SELECT * FROM patient_parametres
        WHERE patient_id = ?
        ORDER BY date_mesure DESC
        LIMIT 10
    ");
        $stmt->execute([$patient_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function getPrescriptionsHospitalisation($patient_id) {
        $stmt = $this->db->prepare("SELECT ph.*, m.nom as medicament_nom, u.nom as medecin_nom
            FROM prescriptions_hospitalisation ph
            JOIN medicaments m ON ph.medicament_id = m.id
            JOIN users u ON ph.medecin_id = u.id
            WHERE ph.patient_id = ?
            ORDER BY ph.date_prescription DESC
        ");
        $stmt->execute([$patient_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Affiche la grille vide pour planifier
public function planifierSoins($patient_id) {
    // ── Restriction service ──
    $this->_guardInfirmierService((int)$patient_id);

    $db = (new Database())->getConnection();
    // 1. Récupérer les infos de base du patient
    require_once 'app/models/Patient.php';
    $patientModel = new Patient();
    $patient = $patientModel->getById($patient_id);
    $age = date_diff(date_create($patient['date_naissance']), date_create('now'))->y;

    // 2. Récupérer l'hospitalisation active pour pré-remplir la localisation
    $stmt = $db->prepare("SELECT h.*, s.nom_service, l.nom_lit, c.nom_chambre
        FROM hospitalisations h
        JOIN services s ON h.service_id = s.id
        LEFT JOIN lits l ON h.lit_id = l.id
        LEFT JOIN chambres c ON l.chambre_id = c.id
        WHERE h.patient_id = ? AND h.statut = 'en_cours'
        LIMIT 1
    ");
    $stmt->execute([$patient_id]);
    $loc = $stmt->fetch(PDO::FETCH_ASSOC);

    // 3. Charger les médicaments de la dernière ordonnance active du patient
    $ordonnanceMeds  = [];
    $ordonnanceId    = null;
    $hasOrdonnance   = false;

    // 3a. Déterminer les colonnes disponibles dans ordonnance_medicaments (isolé du reste)
    $nomCol   = "COALESCE(m.nom,'Médicament')";
    $posCol   = "''"; $dureeCol = "''"; $voieCol = "''";
    try {
        $cols = $db->query("SHOW COLUMNS FROM ordonnance_medicaments")->fetchAll(PDO::FETCH_COLUMN);
        if (in_array('nom_medicament',     $cols)) $nomCol   = 'om.nom_medicament';
        if (in_array('posologie',          $cols)) $posCol   = 'om.posologie';
        if (in_array('duree',              $cols)) $dureeCol = 'om.duree';
        if (in_array('voie_administration',$cols)) $voieCol  = 'om.voie_administration';
        elseif (in_array('voie',           $cols)) $voieCol  = 'om.voie';
    } catch (Exception $e) {
        error_log('[planifierSoins] SHOW COLUMNS ordonnance_medicaments: ' . $e->getMessage());
    }

    // 3b. Requête ordonnance + médicaments
    try {
        $stmtOrd = $db->prepare("
            SELECT op.id as ordonnance_id
            FROM ordonnances_pharmacie op
            WHERE op.patient_id = ?
              AND op.statut NOT IN ('ANNULEE','annulee','REFUSEE','refusee')
            ORDER BY op.id DESC LIMIT 1
        ");
        $stmtOrd->execute([$patient_id]);
        $ordRow = $stmtOrd->fetch(PDO::FETCH_ASSOC);
        if ($ordRow) {
            $ordonnanceId   = (int)$ordRow['ordonnance_id'];
            $stmtMeds = $db->prepare("
                SELECT om.id,
                       $nomCol   AS nom,
                       $posCol   AS posologie,
                       $dureeCol AS duree,
                       $voieCol  AS voie
                FROM ordonnance_medicaments om
                LEFT JOIN medicaments m ON om.medicament_id = m.id
                WHERE om.ordonnance_id = ?
                ORDER BY om.id ASC
            ");
            $stmtMeds->execute([$ordonnanceId]);
            $ordonnanceMeds = $stmtMeds->fetchAll(PDO::FETCH_ASSOC);
            $hasOrdonnance  = !empty($ordonnanceMeds);
        }
    } catch (Exception $e) {
        error_log('[planifierSoins] ordonnance query failed: ' . $e->getMessage());
        $ordonnanceMeds = [];
    }
    $ordonnanceMedsJson = json_encode($ordonnanceMeds, JSON_UNESCAPED_UNICODE);

    // 4. Plans de traitement des 3 dernières consultations médicales complètes
    //    (tous les champs : diagnostic, plan, traitement non-méd., surveillance, suivi)
    $dernierPlanTraitement  = null; // compat. ancienne vue (1ère consultation)
    $dernieresConsultations = [];
    try {
        $stmtCons = $db->prepare("
            SELECT c.id,
                   c.date_consultation,
                   c.motif,
                   c.type_consultation,
                   c.diagnostic_principal,
                   c.hypotheses_diagnostiques,
                   c.plan_traitement,
                   c.traitement_non_medicamenteux,
                   c.surveillance,
                   c.notes_suivi,
                   c.date_suivi,
                   c.devenir,
                   c.resume_syndromique,
                   u.nom       AS medecin_nom,
                   u.prenom    AS medecin_prenom,
                   u.specialite AS medecin_specialite
            FROM consultations c
            LEFT JOIN users u ON c.medecin_id = u.id
            WHERE c.patient_id = ?
              AND (
                    (c.plan_traitement              IS NOT NULL AND c.plan_traitement              <> '')
                 OR (c.traitement_non_medicamenteux IS NOT NULL AND c.traitement_non_medicamenteux <> '')
                 OR (c.diagnostic_principal         IS NOT NULL AND c.diagnostic_principal         <> '')
                 OR (c.surveillance                 IS NOT NULL AND c.surveillance                 <> '')
              )
            ORDER BY c.date_consultation DESC, c.id DESC
            LIMIT 3
        ");
        $stmtCons->execute([$patient_id]);
        $dernieresConsultations = $stmtCons->fetchAll(PDO::FETCH_ASSOC);
        $dernierPlanTraitement  = $dernieresConsultations[0] ?? null; // compat.
    } catch (Exception $e) {
        error_log('[planifierSoins] consultations query failed: ' . $e->getMessage());
        $dernieresConsultations = [];
        $dernierPlanTraitement  = null;
    }

    // 4b. Fallback : si aucun médicament trouvé via patient_id, chercher via consultation_id
    //     (couvre le cas où l'ordonnance a un patient_id différent ou n'a pas été indexée)
    if (empty($ordonnanceMeds) && !empty($dernieresConsultations)) {
        try {
            $consIds = array_column($dernieresConsultations, 'id');
            $in = implode(',', array_fill(0, count($consIds), '?'));
            $stmtOrdFb = $db->prepare("
                SELECT op.id AS ordonnance_id
                FROM ordonnances_pharmacie op
                WHERE op.consultation_id IN ($in)
                  AND op.statut NOT IN ('ANNULEE','annulee','REFUSEE','refusee')
                ORDER BY op.id DESC LIMIT 1
            ");
            $stmtOrdFb->execute($consIds);
            $ordRowFb = $stmtOrdFb->fetch(PDO::FETCH_ASSOC);
            if ($ordRowFb) {
                $ordonnanceId = (int)$ordRowFb['ordonnance_id'];
                $cols = $db->query("SHOW COLUMNS FROM ordonnance_medicaments")->fetchAll(PDO::FETCH_COLUMN);
                $nomCol   = in_array('nom_medicament',    $cols) ? 'om.nom_medicament'    : "COALESCE(m.nom,'Médicament')";
                $posCol   = in_array('posologie',         $cols) ? 'om.posologie'         : "''";
                $dureeCol = in_array('duree',             $cols) ? 'om.duree'             : "''";
                $voieCol  = in_array('voie_administration',$cols) ? 'om.voie_administration'
                          : (in_array('voie', $cols)             ? 'om.voie'              : "''");
                $stmtMedsFb = $db->prepare("
                    SELECT om.id, $nomCol AS nom, $posCol AS posologie,
                           $dureeCol AS duree, $voieCol AS voie
                    FROM ordonnance_medicaments om
                    LEFT JOIN medicaments m ON om.medicament_id = m.id
                    WHERE om.ordonnance_id = ?
                    ORDER BY om.id ASC
                ");
                $stmtMedsFb->execute([$ordonnanceId]);
                $ordonnanceMeds     = $stmtMedsFb->fetchAll(PDO::FETCH_ASSOC);
                $hasOrdonnance      = !empty($ordonnanceMeds);
                $ordonnanceMedsJson = json_encode($ordonnanceMeds, JSON_UNESCAPED_UNICODE);
            }
        } catch (Exception $e) {
            error_log('[planifierSoins] fallback ordonnance by consultation_id: ' . $e->getMessage());
        }
    }

    // 5. Conduites à tenir des 3 dernières réévaluations
    $dernieresReevaluations = [];
    try {
        $stmtRev = $db->prepare("
            SELECT r.id, r.date_reevaluation, r.heure_reevaluation,
                   r.conduite_tenir, r.diagnostic_jour,
                   r.traitement_non_medicamenteux, r.note_evolution,
                   u.nom AS medecin_nom, u.prenom AS medecin_prenom
            FROM reevaluations_hospitalisees r
            JOIN users u ON r.medecin_id = u.id
            WHERE r.patient_id = ?
              AND (r.conduite_tenir IS NOT NULL AND r.conduite_tenir <> '')
            ORDER BY r.date_reevaluation DESC, r.heure_reevaluation DESC
            LIMIT 3
        ");
        $stmtRev->execute([$patient_id]);
        $dernieresReevaluations = $stmtRev->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) { $dernieresReevaluations = []; }

    require_once 'app/views/hospitalisation/planifier_soins.php';
}

// ════════════════════════════════════════════════════════════════
//  HÉBERGEMENT DANS UN SERVICE EXTERNE
// ════════════════════════════════════════════════════════════════

/**
 * GET /hospitalisation/get-lits-disponibles?service_id=X
 * Retourne les lits libres d'un service donné (pour le sélecteur AJAX).
 */
public function getLitsDisponibles(): void {
    header('Content-Type: application/json');
    $service_id = (int)($_GET['service_id'] ?? 0);
    if (!$service_id) { echo json_encode([]); exit; }
    $db = $this->db;
    try {
        $stmt = $db->prepare("
            SELECT l.id, l.nom_lit, c.nom_chambre, c.id AS chambre_id
            FROM lits l
            JOIN chambres c ON l.chambre_id = c.id
            WHERE c.service_id = ?
              AND l.statut IN ('LIBRE','DISPONIBLE')
            ORDER BY c.nom_chambre, l.nom_lit
        ");
        $stmt->execute([$service_id]);
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    } catch (PDOException $e) { echo json_encode([]); }
    exit;
}

/**
 * POST /hospitalisation/installer-lit-externe
 * L'infirmier du service d'appartenance installe son patient dans un lit d'un autre service.
 */
public function installerLitExterne(): void {
    header('Content-Type: application/json');

    $patient_id = (int)($_POST['patient_id'] ?? 0);
    $new_lit_id = (int)($_POST['lit_id']     ?? 0);

    if (!$patient_id || !$new_lit_id) {
        echo json_encode(['success' => false, 'message' => 'Données manquantes.']); exit;
    }

    // Vérifier que l'infirmier connecté appartient au service du patient
    $this->_guardInfirmierService($patient_id, true);

    $db = $this->db;
    try {
        $db->beginTransaction();

        // 1. Hospitalisation active
        $stmtH = $db->prepare(
            "SELECT id, lit_id, service_id FROM hospitalisations
              WHERE patient_id = ? AND statut = 'en_cours' LIMIT 1"
        );
        $stmtH->execute([$patient_id]);
        $hosp = $stmtH->fetch(PDO::FETCH_ASSOC);
        if (!$hosp) throw new Exception('Aucune hospitalisation active trouvée.');

        // 2. Vérifier disponibilité et verrouiller le lit contre la double occupation
        $stmtL = $db->prepare("
            SELECT l.id, l.statut, c.service_id, s.nom_service,
                   c.nom_chambre, l.nom_lit
            FROM lits l
            JOIN chambres c ON l.chambre_id = c.id
            JOIN services s ON c.service_id = s.id
            WHERE l.id = ?
            FOR UPDATE
        ");
        $stmtL->execute([$new_lit_id]);
        $litData = $stmtL->fetch(PDO::FETCH_ASSOC);
        if (!$litData) throw new Exception('Lit introuvable.');
        if (!in_array(strtoupper($litData['statut'] ?? ''), ['LIBRE', 'DISPONIBLE'])) {
            throw new Exception('Ce lit vient d\'être attribué à un autre patient.');
        }

        $newServiceHeberg = (int)$litData['service_id'];

        // 3. Libérer l'ancien lit si présent
        $old_lit_id = (int)($hosp['lit_id'] ?? 0);
        if ($old_lit_id && $old_lit_id !== $new_lit_id) {
            $db->prepare(
                "UPDATE lits SET statut = 'LIBRE',
                         occupied_by_patient_id = NULL,
                         occupied_since = NULL
                 WHERE id = ?"
            )->execute([$old_lit_id]);
        }

        // 4. Occuper le nouveau lit
        $db->prepare(
            "UPDATE lits SET statut = 'OCCUPE',
                     occupied_by_patient_id = ?,
                     occupied_since = NOW()
             WHERE id = ?"
        )->execute([$patient_id, $new_lit_id]);

        // 5. Mettre à jour l'hospitalisation (service_hebergement_id si la colonne existe)
        $hospCols = $db->query("SHOW COLUMNS FROM hospitalisations")->fetchAll(PDO::FETCH_COLUMN);
        if (in_array('service_hebergement_id', $hospCols)) {
            // Même service d'appartenance → hebergement = null, sinon = nouveau service
            $hebergId = ($newServiceHeberg === (int)$hosp['service_id']) ? null : $newServiceHeberg;
            $db->prepare(
                "UPDATE hospitalisations SET lit_id = ?, service_hebergement_id = ? WHERE id = ?"
            )->execute([$new_lit_id, $hebergId, $hosp['id']]);
        } else {
            $db->prepare(
                "UPDATE hospitalisations SET lit_id = ? WHERE id = ?"
            )->execute([$new_lit_id, $hosp['id']]);
        }

        $db->commit();

        echo json_encode([
            'success'     => true,
            'message'     => 'Patient installé avec succès.',
            'nom_service' => $litData['nom_service'],
            'nom_chambre' => $litData['nom_chambre'],
            'nom_lit'     => $litData['nom_lit'],
        ]);

    } catch (Exception $e) {
        $db->rollBack();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// ════════════════════════════════════════════════════════════════
//  MODIFICATION D'UN SOIN PLANIFIÉ (avant exécution)
// ════════════════════════════════════════════════════════════════

/**
 * Récupère les détails d'un soin planifié pour pré-remplir le modal d'édition.
 * GET /hospitalisation/get-soin/{id} → JSON
 */
public function getSoin(int $id): void {
    header('Content-Type: application/json');
    try {
        $db = (new Database())->getConnection();
        $stmt = $db->prepare("
            SELECT s.*, h.patient_id
            FROM soins_hospitalisation s
            JOIN hospitalisations h ON s.admission_id = h.id
            WHERE s.id = ?
        ");
        $stmt->execute([$id]);
        $soin = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$soin) {
            echo json_encode(['success' => false, 'message' => 'Soin introuvable.']);
            exit;
        }
        echo json_encode(['success' => true, 'soin' => $soin]);
    } catch (\Throwable $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

/**
 * Modifie un soin déjà planifié (avant exécution).
 * POST /hospitalisation/modifier-soin/{id}
 *
 * Champs modifiables : type_soin, description, condition_application, date_prevue
 * Restrictions :
 *   - Le soin doit être en statut PLANIFIE (pas REALISE / ANNULE)
 *   - Réservé aux infirmiers et admins
 */
public function modifierSoin(int $id): void {
    header('Content-Type: application/json');

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['success' => false, 'message' => 'Méthode invalide']); exit;
    }

    $userRole = strtoupper($_SESSION['user_role'] ?? '');
    $autorise = in_array($userRole, ['INFIRMIER','INFIRMIER_CONSULTANT','MAJOR','ADMIN','ADMINISTRATEUR','DIRECTEUR']);
    if (!$autorise) {
        echo json_encode(['success' => false, 'message' => 'Accès refusé.']); exit;
    }

    try {
        $db = (new Database())->getConnection();

        // 1. Vérifier que le soin existe et est encore modifiable
        $stmt = $db->prepare("SELECT * FROM soins_hospitalisation WHERE id = ?");
        $stmt->execute([$id]);
        $soin = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$soin) {
            echo json_encode(['success' => false, 'message' => 'Soin introuvable.']); exit;
        }
        if ($soin['statut'] !== 'PLANIFIE') {
            echo json_encode([
                'success' => false,
                'message' => "Ce soin a déjà été {$soin['statut']} — impossible de le modifier."
            ]); exit;
        }

        // 2. Lire les nouvelles valeurs
        $description = trim($_POST['description']           ?? '');
        $heure       = trim($_POST['heure']                 ?? '');
        $condition   = trim($_POST['condition_application'] ?? '');
        $type        = trim($_POST['type_soin']             ?? $soin['type_soin']);

        if ($description === '') {
            echo json_encode(['success' => false, 'message' => 'La description est obligatoire.']); exit;
        }
        if ($heure === '' || !preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $heure)) {
            echo json_encode(['success' => false, 'message' => 'Heure invalide (format attendu : HH:MM).']); exit;
        }

        // 3. Conserver la date d'origine, ne mettre à jour que l'heure
        $datePrevue = date('Y-m-d', strtotime($soin['date_prevue']));
        $newDateTime = $datePrevue . ' ' . $heure . ':00';

        // 4. UPDATE
        $stmtU = $db->prepare("
            UPDATE soins_hospitalisation
            SET type_soin = ?,
                description = ?,
                condition_application = ?,
                date_prevue = ?
            WHERE id = ? AND statut = 'PLANIFIE'
        ");
        $stmtU->execute([
            $type ?: $soin['type_soin'],
            $description,
            $condition !== '' ? $condition : null,
            $newDateTime,
            $id,
        ]);

        // 5. Audit
        try {
            require_once __DIR__ . '/../services/AuditService.php';
            (new AuditService())->logAction(
                'UPDATE', 'soins_hospitalisation', $id, null,
                "Soin planifié modifié (admission #{$soin['admission_id']})"
            );
        } catch (\Throwable $e) { /* ignorer */ }

        echo json_encode([
            'success' => true,
            'message' => '✅ Soin modifié avec succès.',
            'soin' => [
                'id' => $id,
                'description' => $description,
                'heure' => $heure,
                'condition_application' => $condition,
                'type_soin' => $type,
            ],
        ]);
    } catch (\Throwable $e) {
        echo json_encode(['success' => false, 'message' => 'Erreur : ' . $e->getMessage()]);
    }
    exit;
}

// (méthode supprimerSoin existante plus bas — voir ligne ~1264 — gère déjà la suppression
// avec vérification du service. L'amélioration "statut = PLANIFIE" y est ajoutée directement.)

// Affiche la checklist avec boutons de validation
// Supprimez tout autre bloc "public function checklist" pour ne garder que celui-ci :
public function checklist($hosp_id) {
    $db = (new Database())->getConnection();

    // 1. Récupérer les informations de l'hospitalisation et du patient
    $stmtPatient = $db->prepare("SELECT p.nom, p.prenom, h.date_admission as date_plan
        FROM hospitalisations h
        JOIN patients p ON h.patient_id = p.id
        WHERE h.id = ?
    ");
    $stmtPatient->execute([$hosp_id]);
    $patient = $stmtPatient->fetch(PDO::FETCH_ASSOC);

    if (!$patient) {
        die("Erreur : Hospitalisation introuvable.");
    }

    // 2. Récupérer la liste des soins du jour
    $stmtSoins = $db->prepare("SELECT * FROM soins_hospitalisation WHERE admission_id = ? ORDER BY date_prevue ASC");
    $stmtSoins->execute([$hosp_id]);
    $soins = $stmtSoins->fetchAll(PDO::FETCH_ASSOC);

    $plan_id = $hosp_id; // compatibilité vue
    // 3. Charger la vue
    require_once 'app/views/hospitalisation/checklist.php';
}

// Validation AJAX d'une ligne de soin
public function validerSoinItem() {
    header('Content-Type: application/json');

    // Récupérer le soin_id (la vue envoie 'soin_id', certains anciens appels 'id')
    $soinId = (int)($_POST['soin_id'] ?? $_POST['id'] ?? 0);
    if (!$soinId) { echo json_encode(['success' => false, 'message' => 'ID soin manquant']); exit; }

    // ── Restriction service : vérifier que le soin appartient au service de l'infirmier ──
    $role      = $_SESSION['user_role']    ?? '';
    $serviceId = (int)($_SESSION['service_id'] ?? 0);
    if (in_array($role, ['INFIRMIER', 'MAJOR_INFIRMIER']) && $serviceId > 0) {
        $stmtCheck = $this->db->prepare(
            "SELECT h.service_id FROM soins_hospitalisation sh
              JOIN hospitalisations h ON sh.admission_id = h.id
             WHERE sh.id = ? LIMIT 1"
        );
        $stmtCheck->execute([$soinId]);
        $row = $stmtCheck->fetch(PDO::FETCH_ASSOC);
        if (!$row || (int)$row['service_id'] !== $serviceId) {
            echo json_encode(['success' => false, 'message' => 'Accès refusé : soin hors de votre service.']);
            exit;
        }
    }

    $note = trim($_POST['note'] ?? '');
    $stmt = $this->db->prepare(
        "UPDATE soins_hospitalisation
            SET statut = 'REALISE', date_realisee = NOW(),
                user_id_executant = ?, note_execution = ?
          WHERE id = ?"
    );
    $success = $stmt->execute([$_SESSION['user_id'], $note, $soinId]);

    echo json_encode(['success' => $success]);
}

// Suppression définitive d'un soin planifié
public function supprimerSoin(int $id): void {
    header('Content-Type: application/json');
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
        return;
    }

    // Lire le motif depuis le corps JSON
    $input  = json_decode(file_get_contents('php://input'), true) ?? [];
    $motif  = trim($input['motif'] ?? '');
    if ($motif === '') {
        echo json_encode(['success' => false, 'message' => 'Le motif de suppression est obligatoire.']);
        return;
    }

    // ── Restriction service ──
    $role      = $_SESSION['user_role']    ?? '';
    $serviceId = (int)($_SESSION['service_id'] ?? 0);
    if (in_array($role, ['INFIRMIER', 'MAJOR_INFIRMIER']) && $serviceId > 0) {
        $stmtCheck = $this->db->prepare(
            "SELECT h.service_id FROM soins_hospitalisation sh
              JOIN hospitalisations h ON sh.admission_id = h.id
             WHERE sh.id = ? LIMIT 1"
        );
        $stmtCheck->execute([$id]);
        $row = $stmtCheck->fetch(PDO::FETCH_ASSOC);
        if (!$row || (int)$row['service_id'] !== $serviceId) {
            echo json_encode(['success' => false, 'message' => 'Accès refusé : soin hors de votre service.']);
            return;
        }
    }
    try {
        $db = (new Database())->getConnection();

        // Vérifier que le soin est encore en statut PLANIFIE
        $stmtChk = $db->prepare("SELECT statut FROM soins_hospitalisation WHERE id = ?");
        $stmtChk->execute([$id]);
        $statut = $stmtChk->fetchColumn();
        if ($statut === false) {
            echo json_encode(['success' => false, 'message' => 'Soin introuvable.']);
            return;
        }
        if ($statut !== 'PLANIFIE') {
            echo json_encode([
                'success' => false,
                'message' => "Ce soin est déjà en statut « $statut » — suppression impossible."
            ]);
            return;
        }

        // Suppression logique : on marque SUPPRIME au lieu de DELETE
        $stmt = $db->prepare("
            UPDATE soins_hospitalisation
            SET statut             = 'SUPPRIME',
                motif_suppression  = ?,
                supprime_par       = ?,
                date_suppression   = NOW()
            WHERE id = ? AND statut = 'PLANIFIE'
        ");
        $stmt->execute([$motif, $_SESSION['user_id'], $id]);

        if ($stmt->rowCount() > 0) {
            echo json_encode(['success' => true, 'message' => 'Soin marqué comme supprimé.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Soin introuvable ou déjà traité.']);
        }
    } catch (\PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Erreur base de données : ' . $e->getMessage()]);
    }
}

public function surveillance($patient_id) {
    require_once 'app/models/Patient.php';
    $patientModel = new Patient();
    $patient = $patientModel->getById($patient_id);
    $age = date_diff(date_create($patient['date_naissance']), date_create('now'))->y;

    require_once __DIR__ . '/../views/hospitalisation/soins_surveillance.php';
}

public function saveSurveillance() {
    // Logique de sauvegarde des colonnes multiples (boucle sur les tableaux postés)
    // Redirection vers le dossier patient
    header('Location: ' . BASE_URL . 'patients/dossier/' . $_POST['patient_id'] . '?success=1');
}

public function surveillanceIntensive($patient_id) {
    require_once 'app/models/Patient.php';
    $patientModel = new Patient();
    $patient = $patientModel->getById($patient_id);
    $age = date_diff(date_create($patient['date_naissance']), date_create('now'))->y;

    require_once __DIR__ . '/../views/hospitalisation/surveillance_intensive.php';
}

public function saveSI() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: ' . BASE_URL . 'dashboard');
        exit;
    }

    $db = (new Database())->getConnection();
    $patient_id = (int)($_POST['patient_id'] ?? 0);
    $diag = trim($_POST['diag'] ?? '');
    $obs = $_POST['obs'] ?? [];

    if ($patient_id && !empty($obs['date'])) {
        $stmt = $db->prepare("
            INSERT INTO surveillance_intensive_obs
            (patient_id, date_obs, heure_obs, ta, pouls, temperature, respiration,
             diurese, conscience, aspiration, soins, observations, staff, diag, infirmier_id)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $count = count($obs['date']);
        for ($i = 0; $i < $count; $i++) {
            $pouls = !empty($obs['pouls'][$i]) ? (int)$obs['pouls'][$i] : null;
            $temp  = !empty($obs['temp'][$i])  ? (float)$obs['temp'][$i] : null;
            $resp  = !empty($obs['resp'][$i])  ? (int)$obs['resp'][$i] : null;

            $stmt->execute([
                $patient_id,
                $obs['date'][$i]      ?? '',
                $obs['heure'][$i]     ?? '',
                $obs['ta'][$i]        ?? '',
                $pouls,
                $temp,
                $resp,
                $obs['diurese'][$i]   ?? '',
                $obs['conscience'][$i] ?? '',
                $obs['asp'][$i]       ?? '',
                $obs['soins'][$i]     ?? '',
                $obs['obs'][$i]       ?? '',
                $obs['staff'][$i]     ?? '',
                $diag,
                $_SESSION['user_id']
            ]);
        }
    }

    header('Location: ' . BASE_URL . 'hospitalisation/suivi/' . $patient_id . '?success=si_saved');
    exit;
}

public function ficheTransfusionnelle($patient_id) {
    require_once 'app/models/Patient.php';
    $patientModel = new Patient();
    $patient = $patientModel->getById($patient_id);

    if (!$patient) {
        header('Location: ' . BASE_URL . 'dashboard?error=patient_introuvable');
        exit;
    }

    $age = date_diff(date_create($patient['date_naissance']), date_create('now'))->y;

    // Récupérer infos hospitalisation active (service, lit)
    $db = (new Database())->getConnection();
    $stmtH = $db->prepare("SELECT s.nom_service, l.nom_lit
        FROM hospitalisations h
        LEFT JOIN services s ON h.service_id = s.id
        LEFT JOIN lits l ON h.lit_id = l.id
        WHERE h.patient_id = ? AND h.statut = 'en_cours'
        LIMIT 1");
    $stmtH->execute([$patient_id]);
    $hosp = $stmtH->fetch(PDO::FETCH_ASSOC);
    $patient['nom_service'] = $hosp['nom_service'] ?? '-';
    $patient['nom_lit']     = $hosp['nom_lit'] ?? '-';

    require_once __DIR__ . '/../views/hospitalisation/fiche_transfusionnelle.php';
}

public function saveTransfusion() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: ' . BASE_URL . 'dashboard'); exit;
    }
    $patient_id = (int)($_POST['patient_id'] ?? 0);
    if (!$patient_id) {
        header('Location: ' . BASE_URL . 'dashboard'); exit;
    }
    $db = (new Database())->getConnection();

    // ── Migration auto : créer les tables si absentes ───────────────
    try {
        $db->exec("CREATE TABLE IF NOT EXISTS patient_transfusions (
            id                  INT AUTO_INCREMENT PRIMARY KEY,
            patient_id          INT          NOT NULL,
            user_id             INT          DEFAULT NULL,
            groupe_sanguin      VARCHAR(10)  DEFAULT NULL,
            indication          VARCHAR(100) DEFAULT NULL,
            diagnostic          TEXT         DEFAULT NULL,
            taux_hb             DECIMAL(5,2) DEFAULT NULL,
            medecin_prescripteur VARCHAR(150) DEFAULT NULL,
            groupe_verifie      VARCHAR(10)  DEFAULT NULL,
            rhesus              VARCHAR(20)  DEFAULT NULL,
            num_compat          VARCHAR(100) DEFAULT NULL,
            consentement        VARCHAR(50)  DEFAULT NULL,
            rai                 VARCHAR(50)  DEFAULT NULL,
            observations_finales TEXT        DEFAULT NULL,
            created_at          DATETIME     DEFAULT NOW(),
            INDEX idx_patient (patient_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $db->exec("CREATE TABLE IF NOT EXISTS patient_transfusion_poches (
            id              INT AUTO_INCREMENT PRIMARY KEY,
            transfusion_id  INT          NOT NULL,
            date_trans      VARCHAR(20)  DEFAULT NULL,
            heure_debut     VARCHAR(10)  DEFAULT NULL,
            heure_fin       VARCHAR(10)  DEFAULT NULL,
            type_produit    VARCHAR(50)  DEFAULT NULL,
            num_poche       VARCHAR(100) DEFAULT NULL,
            groupe          VARCHAR(10)  DEFAULT NULL,
            volume          DECIMAL(7,1) DEFAULT NULL,
            temp_avant      DECIMAL(5,1) DEFAULT NULL,
            ta_avant        VARCHAR(20)  DEFAULT NULL,
            pouls           INT          DEFAULT NULL,
            temp_apres      DECIMAL(5,1) DEFAULT NULL,
            ta_apres        VARCHAR(20)  DEFAULT NULL,
            reaction        VARCHAR(100) DEFAULT NULL,
            infirmier       VARCHAR(100) DEFAULT NULL,
            INDEX idx_trans (transfusion_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    } catch (\Throwable $e) { /* silencieux */ }

    // ── Insertion de la fiche ────────────────────────────────────────
    try {
        $db->beginTransaction();

        $stmtF = $db->prepare("INSERT INTO patient_transfusions
            (patient_id, user_id, groupe_sanguin, indication, diagnostic, taux_hb,
             medecin_prescripteur, groupe_verifie, rhesus, num_compat,
             consentement, rai, observations_finales, created_at)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?, NOW())");
        $stmtF->execute([
            $patient_id,
            $_SESSION['user_id'] ?? null,
            trim($_POST['groupe_sanguin']      ?? ''),
            trim($_POST['indication']          ?? ''),
            trim($_POST['diagnostic']          ?? ''),
            $_POST['taux_hb'] !== '' ? (float)$_POST['taux_hb'] : null,
            trim($_POST['medecin_prescripteur'] ?? ''),
            trim($_POST['groupe_verifie']      ?? ''),
            trim($_POST['rhesus']              ?? ''),
            trim($_POST['num_compat']          ?? ''),
            trim($_POST['consentement']        ?? ''),
            trim($_POST['rai']                 ?? ''),
            trim($_POST['observations_finales'] ?? ''),
        ]);
        $transfusion_id = (int)$db->lastInsertId();

        // ── Poches ──────────────────────────────────────────────────
        $poches = $_POST['trans'] ?? [];
        $dates  = $poches['date']        ?? [];
        $n      = count($dates);
        if ($n > 0) {
            $stmtP = $db->prepare("INSERT INTO patient_transfusion_poches
                (transfusion_id, date_trans, heure_debut, heure_fin, type_produit,
                 num_poche, groupe, volume, temp_avant, ta_avant, pouls,
                 temp_apres, ta_apres, reaction, infirmier)
                VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
            for ($i = 0; $i < $n; $i++) {
                $stmtP->execute([
                    $transfusion_id,
                    trim($poches['date'][$i]         ?? ''),
                    trim($poches['heure_debut'][$i]  ?? ''),
                    trim($poches['heure_fin'][$i]    ?? ''),
                    trim($poches['type_produit'][$i] ?? ''),
                    trim($poches['num_poche'][$i]    ?? ''),
                    trim($poches['groupe'][$i]       ?? ''),
                    $poches['volume'][$i] !== '' ? (float)$poches['volume'][$i] : null,
                    $poches['temp_avant'][$i] !== '' ? (float)$poches['temp_avant'][$i] : null,
                    trim($poches['ta_avant'][$i]     ?? ''),
                    $poches['pouls'][$i] !== '' ? (int)$poches['pouls'][$i] : null,
                    $poches['temp_apres'][$i] !== '' ? (float)$poches['temp_apres'][$i] : null,
                    trim($poches['ta_apres'][$i]     ?? ''),
                    trim($poches['reaction'][$i]     ?? ''),
                    trim($poches['infirmier'][$i]    ?? ''),
                ]);
            }
        }
        $db->commit();
    } catch (\Throwable $e) {
        $db->rollBack();
        error_log('[saveTransfusion] ' . $e->getMessage());
    }

    header('Location: ' . BASE_URL . 'hospitalisation/suivi/' . $patient_id . '?success=transfusion_saved');
    exit;
}

public function observationsEvolution($patient_id) {
    $db = (new Database())->getConnection();

    // 1. Récupération infos patient
    require_once 'app/models/Patient.php';
    $patientModel = new Patient();
    $patient = $patientModel->getById($patient_id);

    // 2. Calcul âge
    $age = $patient ? date_diff(date_create($patient['date_naissance']), date_create('now'))->y : 'N/A';

    // 3. Récupérer les observations
    $stmt = $db->prepare("SELECT o.*, u.nom as user_nom, u.role
                          FROM observations_evolution o
                          JOIN users u ON o.user_id = u.id
                          WHERE o.patient_id = ?
                          ORDER BY o.date_obs DESC");
    $stmt->execute([$patient_id]);
    $observations = $stmt->fetchAll(PDO::FETCH_ASSOC);

    require_once __DIR__ . '/../views/hospitalisation/observations_evolution.php';
}

public function saveObservation() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $db = (new Database())->getConnection();
        $stmt = $db->prepare("INSERT INTO observations_evolution (patient_id, user_id, contenu) VALUES (?, ?, ?)");

        $success = $stmt->execute([
            $_POST['patient_id'],
            $_SESSION['user_id'], // L'ID du médecin/infirmier connecté
            $_POST['contenu']
        ]);

        if ($success) {
            header('Location: ' . BASE_URL . 'hospitalisation/observations-evolution/' . $_POST['patient_id']);
        } else {
            echo "Erreur lors de l'enregistrement.";
        }
    }
}

public function deleteObservation($id, $patient_id) {
    // Vérification de sécurité (seul l'auteur ou un admin peut supprimer)
    // $this->auth->requirePermission('hospitalisation', 'delete');

    $db = (new Database())->getConnection();
    $stmt = $db->prepare("DELETE FROM observations_evolution WHERE id = ?");
    $success = $stmt->execute([$id]);

    if ($success) {
        header('Location: ' . BASE_URL . 'hospitalisation/observations-evolution/' . $patient_id . '?success=deleted');
    } else {
        header('Location: ' . BASE_URL . 'hospitalisation/observations-evolution/' . $patient_id . '?error=1');
    }
    exit;
}

/**
 * Action déclenchée par l'infirmier pour installer un patient sur un lit
 */
public function validerInstallation() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $db = (new Database())->getConnection();

        $admission_id = $_POST['admission_id'] ?? null; // ID de la demande (consultation ou urgence)
        $patient_id_direct = (int)($_POST['patient_id'] ?? 0); // ID patient transmis directement depuis la vue
        $lit_id = $_POST['lit_id'] ?? null;
        $infirmier_id = $_SESSION['user_id'];
        $infirmier_service_id = $_SESSION['service_id'] ?? null; // Le service de l'infirmier connecté

        if (!$lit_id) {
            die("Erreur : Veuillez sélectionner un lit.");
        }

        if (!$admission_id && !$patient_id_direct) {
            die("Erreur : Données d'installation incomplètes.");
        }

        try {
            $db->beginTransaction();

            // 1. Résolution du patient
            // Priorité : patient_id transmis directement (fiable pour les transferts)
            if ($patient_id_direct > 0) {
                $stmtP = $db->prepare("SELECT id, service_id FROM patients WHERE id = ?");
                $stmtP->execute([$patient_id_direct]);
                $patRow = $stmtP->fetch(PDO::FETCH_ASSOC);
                if (!$patRow) throw new Exception("Patient introuvable (id=$patient_id_direct).");
                $patient_id = $patRow['id'];
                // Pour le service final : hospitalisation active si elle existe, sinon service courant
                $infos = ['service_id' => $infirmier_service_id];
            } else {
                // Fallback : résolution via admission_id (consultation ou urgence)
                $stmt = $db->prepare("SELECT patient_id, service_id FROM consultations WHERE id = ?");
                $stmt->execute([$admission_id]);
                $infos = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$infos) {
                    // Tentative via la table urgences_patients
                    $stmt = $db->prepare("SELECT patient_id FROM urgences_patients WHERE id = ?");
                    $stmt->execute([$admission_id]);
                    $infos = $stmt->fetch(PDO::FETCH_ASSOC);
                    // Aux urgences, le service_id n'est pas toujours dans l'admission,
                    // donc on s'assurera d'utiliser celui de l'infirmier plus bas.
                }

                if (!$infos) {
                    throw new Exception("Impossible de retrouver le patient (admission_id=$admission_id).");
                }

                $patient_id = $infos['patient_id'];
            }

            // --- DETERMINATION DU SERVICE (SECURITE ANTI-NULL) ---
            // On prend le service de l'infirmier (destination réelle), sinon celui de la demande
            $final_service_id = $infirmier_service_id ?: (!empty($infos['service_id']) ? $infos['service_id'] : null);

            if (empty($final_service_id)) {
                throw new Exception("Le service de destination est introuvable. Veuillez vérifier votre affectation.");
            }

            // 2. Créer ou réutiliser l'hospitalisation active
            // Pour un patient transféré, une hospitalisation en_cours existe déjà —
            // on la met à jour (lit) plutôt que d'en créer une doublon.
            $stmtExist = $db->prepare(
                "SELECT id, lit_id FROM hospitalisations
                  WHERE patient_id = ? AND statut = 'en_cours'
                  ORDER BY date_admission DESC LIMIT 1"
            );
            $stmtExist->execute([$patient_id]);
            $existingHosp = $stmtExist->fetch(PDO::FETCH_ASSOC);

            if ($existingHosp) {
                // Patient transféré : libérer l'ancien lit si différent
                if ($existingHosp['lit_id'] && $existingHosp['lit_id'] != $lit_id) {
                    $db->prepare("UPDATE lits SET statut = 'DISPONIBLE', occupied_by_patient_id = NULL WHERE id = ?")
                       ->execute([$existingHosp['lit_id']]);
                }
                // Mettre à jour le lit dans l'hospitalisation existante
                $db->prepare("UPDATE hospitalisations SET lit_id = ? WHERE id = ?")
                   ->execute([$lit_id, $existingHosp['id']]);
            } else {
                // Admission standard : créer l'hospitalisation
                $db->prepare("INSERT INTO hospitalisations (patient_id, service_id, lit_id, date_admission, statut)
                              VALUES (?, ?, ?, NOW(), 'en_cours')")
                   ->execute([$patient_id, $final_service_id, $lit_id]);
            }

            // 3. Mettre à jour le statut du LIT (Occupé)
            $db->prepare("UPDATE lits SET statut = 'OCCUPE', occupied_by_patient_id = ? WHERE id = ?")
               ->execute([$patient_id, $lit_id]);

            // 4. Mettre à jour le statut du PATIENT
            $db->prepare("UPDATE patients SET statut_hosp = 'HOSPITALISE', statut = 'HOSPITALISE' WHERE id = ?")
               ->execute([$patient_id]);

            $db->commit();
            header('Location: ' . BASE_URL . 'dashboard?success=installation_reussie');
            exit;

        } catch (Exception $e) {
            $db->rollBack();
            die("Erreur lors de l'installation : " . $e->getMessage());
        }
    }
}

public function savePlan() {
    $db = (new Database())->getConnection();
    try {
        $db->beginTransaction();

        $patient_id = (int)($_POST['patient_id'] ?? 0);

        // ── 1. Récupérer l'hospitalisation en cours du patient ──
        $stmtH = $db->prepare(
            "SELECT id FROM hospitalisations WHERE patient_id = ? AND statut = 'en_cours' ORDER BY id DESC LIMIT 1"
        );
        $stmtH->execute([$patient_id]);
        $hosp = $stmtH->fetch(PDO::FETCH_ASSOC);
        $admission_id = $hosp ? (int)$hosp['id'] : null;

        // ── 2. Enregistrer les soins planifiés (ajout aux soins existants, sans écraser) ──
        $nbSoinsCrees = 0;
        $stmtS = $db->prepare(
            "INSERT INTO soins_hospitalisation
                (admission_id, user_id_planificateur, type_soin, description, condition_application, date_prevue, statut)
             VALUES (?, ?, ?, ?, ?, ?, 'PLANIFIE')"
        );
        foreach (($_POST['soins'] ?? []) as $categorie => $data) {
            if (isset($data['heure'])) {
                foreach ($data['heure'] as $index => $heure) {
                    if (!empty($heure) && !empty($data['desc'][$index])) {
                        $condition  = $data['condition'][$index] ?? null;
                        $description = $data['desc'][$index];
                        // Durée en jours (1 = 24h, 2 = 48h, etc.)
                        $dureeJours = max(1, min(30, (int)($data['duree'][$index] ?? 1)));
                        // Décalage de jour pour les lignes auto générées par la fréquence
                        // (ex: jour_offset=1 → dose à l'heure indiquée mais le lendemain)
                        $jourOffset = max(0, min(30, (int)($data['jour_offset'][$index] ?? 0)));
                        // date de début : utilise la valeur envoyée (modal "Mettre à jour"), sinon aujourd'hui
                        $rawDateDebut = trim($data['date_debut'][$index] ?? '');
                        $dateBase = ($rawDateDebut !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $rawDateDebut))
                            ? $rawDateDebut
                            : date('Y-m-d');
                        // Appliquer le décalage de jour (pour les doses cross-day générées par fréquence)
                        $dateBase = date('Y-m-d', strtotime("+{$jourOffset} days", strtotime($dateBase)));

                        for ($jour = 0; $jour < $dureeJours; $jour++) {
                            $datePrevue = date('Y-m-d', strtotime("+{$jour} days", strtotime($dateBase))) . ' ' . $heure . ':00';
                            $stmtS->execute([
                                $admission_id,
                                $_SESSION['user_id'],
                                $categorie,
                                $description,
                                $condition ?: null,
                                $datePrevue,
                            ]);
                            $nbSoinsCrees++;
                        }
                    }
                }
            }
        }

        // ── 2b. Notifier les infirmiers du service ──
        if ($nbSoinsCrees > 0 && $admission_id) {
            try {
                require_once __DIR__ . '/../services/NotificationCenter.php';
                $center = new NotificationCenter($db);

                // Récupérer le service de l'hospitalisation + nom du patient
                $stmtInfo = $db->prepare("
                    SELECT h.service_id, p.nom AS p_nom, p.prenom AS p_prenom
                    FROM hospitalisations h
                    JOIN patients p ON p.id = h.patient_id
                    WHERE h.id = ?
                ");
                $stmtInfo->execute([$admission_id]);
                $info = $stmtInfo->fetch(PDO::FETCH_ASSOC);

                if ($info && $info['service_id']) {
                    $center->notifyByService((int)$info['service_id'], [
                        'category' => NotificationCenter::CAT_SOIN,
                        'title'    => '🩺 Nouveaux soins planifiés',
                        'message'  => "Patient {$info['p_nom']} {$info['p_prenom']} — planification enregistrée ($nbSoinsCrees entrée(s) sur plusieurs jours)",
                        'link'     => 'hospitalisation/executer-soins/' . (int)$admission_id,
                        'priority' => 'high',
                        'meta'     => ['admission_id' => (int)$admission_id, 'patient_id' => (int)$patient_id],
                    ], ['INFIRMIER', 'INFIRMIER_CONSULTANT', 'MAJOR_INFIRMIER', 'MAJOR']);
                }
            } catch (\Throwable $e) {
                error_log('[savePlan] notify infirmiers: ' . $e->getMessage());
            }
        }

        // ── 3. Générer une ordonnance pour les médicaments hors ordonnance ──
        $horsMedsJson = $_POST['hors_ordonnance_meds'] ?? '[]';
        $horsMeds     = json_decode($horsMedsJson, true);

        if (!empty($horsMeds) && is_array($horsMeds)) {
            // Filtrer les entrées vides
            $horsMeds = array_filter($horsMeds, fn($m) => !empty(trim($m['nom'] ?? '')));
        }

        if (!empty($horsMeds)) {
            // Détecter les colonnes disponibles
            $colsOrd = $db->query("SHOW COLUMNS FROM ordonnances_pharmacie")->fetchAll(PDO::FETCH_COLUMN);
            $colsMed = $db->query("SHOW COLUMNS FROM ordonnance_medicaments")->fetchAll(PDO::FETCH_COLUMN);

            // ── 3a. Créer l'ordonnance ──
            $ordFields  = ['patient_id'];
            $ordVals    = [$patient_id];
            $ordHolders = ['?'];

            // statut
            if (in_array('statut', $colsOrd)) {
                $ordFields[] = 'statut'; $ordVals[] = 'EN_ATTENTE'; $ordHolders[] = '?';
            }
            // note / notes
            $noteCol = in_array('notes', $colsOrd) ? 'notes' : (in_array('note', $colsOrd) ? 'note' : null);
            if ($noteCol) {
                $ordFields[] = $noteCol;
                $ordVals[]   = 'Générée automatiquement — médicaments hors ordonnance (planification des soins)';
                $ordHolders[] = '?';
            }
            // prescripteur / medecin / user
            foreach (['prescripteur_id', 'medecin_id', 'user_id'] as $col) {
                if (in_array($col, $colsOrd)) {
                    $ordFields[] = $col; $ordVals[] = (int)$_SESSION['user_id']; $ordHolders[] = '?';
                    break;
                }
            }
            // date de création
            foreach (['date_creation', 'created_at', 'date'] as $col) {
                if (in_array($col, $colsOrd)) {
                    $ordFields[] = $col; $ordVals[] = date('Y-m-d H:i:s'); $ordHolders[] = '?';
                    break;
                }
            }

            $db->prepare(
                "INSERT INTO ordonnances_pharmacie (" . implode(',', $ordFields) . ")
                 VALUES (" . implode(',', $ordHolders) . ")"
            )->execute($ordVals);
            $newOrdId = (int)$db->lastInsertId();

            // ── 3b. Insérer chaque médicament hors ordonnance ──
            foreach ($horsMeds as $hm) {
                $nom = trim($hm['nom'] ?? '');
                if (!$nom) continue;

                $medFields  = ['ordonnance_id'];
                $medVals    = [$newOrdId];
                $medHolders = ['?'];

                // nom_medicament
                if (in_array('nom_medicament', $colsMed)) {
                    $medFields[] = 'nom_medicament'; $medVals[] = $nom; $medHolders[] = '?';
                }
                // posologie (on met la catégorie de soin comme contexte)
                if (in_array('posologie', $colsMed)) {
                    $posologie = 'Selon planification soins';
                    if (!empty($hm['heure']))    $posologie .= ' — ' . $hm['heure'];
                    if (!empty($hm['condition'])) $posologie .= ' (' . $hm['condition'] . ')';
                    $medFields[] = 'posologie'; $medVals[] = $posologie; $medHolders[] = '?';
                }
                // voie d'administration déduite de la catégorie
                if (in_array('voie_administration', $colsMed)) {
                    $voieMap = ['PER_OS' => 'Per os', 'IV' => 'Intra-veineuse', 'IM' => 'Intra-musculaire', 'SC' => 'Sous-cutanée'];
                    $voie    = $voieMap[$hm['categorie'] ?? ''] ?? ($hm['categorie'] ?? '');
                    if ($voie) { $medFields[] = 'voie_administration'; $medVals[] = $voie; $medHolders[] = '?'; }
                }

                $db->prepare(
                    "INSERT INTO ordonnance_medicaments (" . implode(',', $medFields) . ")
                     VALUES (" . implode(',', $medHolders) . ")"
                )->execute($medVals);
            }

        }

        $db->commit();

        // Construire l'URL de redirection
        $nbHors = !empty($horsMeds) ? count($horsMeds) : 0;
        $redirectParam = $nbHors > 0
            ? 'plan_valide_avec_ordo&nb_hors=' . $nbHors
            : 'plan_valide';

        // Si la requête vient du modal "Mettre à jour" dans suivi, revenir au suivi
        $redirectTo = trim($_POST['redirect_to'] ?? '');
        if ($redirectTo !== '' && preg_match('#^[a-zA-Z0-9/_-]+$#', $redirectTo)) {
            header('Location: ' . BASE_URL . $redirectTo . '?success=' . $redirectParam);
        } else {
            header('Location: ' . BASE_URL . 'dashboard?success=' . $redirectParam);
        }
        exit;
    } catch (Exception $e) {
        $db->rollBack();
        die("Erreur lors de la sauvegarde du plan de soins : " . $e->getMessage());
    }
}

/**
 * Affiche la liste des soins à cocher pour un plan donné
 */
public function executerSoins($hosp_id) {
    $db = (new Database())->getConnection();

    // Récupérer les soins + infos patient + infos exécutant + suppresseur
    try {
        $stmt = $db->prepare("
            SELECT sh.*,
                   p.id as patient_id, p.nom, p.prenom, p.dossier_numero,
                   h.id as plan_id, h.service_id,
                   s.nom_service,
                   ue.nom as executant_nom, ue.prenom as executant_prenom,
                   us.nom as supprime_par_nom, us.prenom as supprime_par_prenom
            FROM soins_hospitalisation sh
            JOIN hospitalisations h ON sh.admission_id = h.id
            JOIN patients p ON h.patient_id = p.id
            LEFT JOIN services s ON h.service_id = s.id
            LEFT JOIN users ue ON sh.user_id_executant = ue.id
            LEFT JOIN users us ON sh.supprime_par = us.id
            WHERE sh.admission_id = ?
            ORDER BY sh.date_prevue ASC
        ");
        $stmt->execute([$hosp_id]);
        $soins = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (\PDOException $e) {
        // Fallback si les colonnes de suppression n'existent pas encore (migration non appliquée)
        $stmt = $db->prepare("
            SELECT sh.*,
                   p.id as patient_id, p.nom, p.prenom, p.dossier_numero,
                   h.id as plan_id, h.service_id,
                   s.nom_service,
                   ue.nom as executant_nom, ue.prenom as executant_prenom
            FROM soins_hospitalisation sh
            JOIN hospitalisations h ON sh.admission_id = h.id
            JOIN patients p ON h.patient_id = p.id
            LEFT JOIN services s ON h.service_id = s.id
            LEFT JOIN users ue ON sh.user_id_executant = ue.id
            WHERE sh.admission_id = ?
            ORDER BY sh.date_prevue ASC
        ");
        $stmt->execute([$hosp_id]);
        $soins = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    $plan_id = $hosp_id;

    if (!$soins) {
        header('Location: ' . BASE_URL . 'dashboard?error=plan_vide');
        exit;
    }

    require_once __DIR__ . '/../views/hospitalisation/executer_soins.php';
}

/**
 * AJAX — Sauvegarde d'une évaluation de la douleur (HAS 2022)
 * Route : POST hospitalisation/save-douleur
 */
public function sauvegarderEvaluationDouleur() {
    header('Content-Type: application/json');
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
        exit;
    }

    $data         = json_decode(file_get_contents('php://input'), true) ?? [];
    $patient_id   = (int)($data['patient_id']   ?? 0);
    $admission_id = !empty($data['admission_id']) ? (int)$data['admission_id'] : null;
    $infirmier_id = $_SESSION['user_id'] ?? 0;

    // Validation minimale
    $profil   = $data['profil']   ?? '';
    $echelle  = $data['echelle']  ?? '';
    $score    = isset($data['score']) ? (float)$data['score'] : null;
    $scoreMax = isset($data['score_max']) ? (float)$data['score_max'] : null;

    $profilsOk  = ['ADULTE_COMM', 'ADULTE_NON_COMM', 'PEDIATRIQUE'];
    $echellesOk = ['EVN','EN','EVA','EVS','ALGOPLUS','DOLOPLUS2','BPS_NI','EVENDOL','FLACC','FPS_R'];

    if (!$patient_id || !in_array($profil, $profilsOk) || !in_array($echelle, $echellesOk) || $score === null) {
        echo json_encode(['success' => false, 'message' => 'Données incomplètes ou invalides.']);
        exit;
    }

    // Calcul de la sévérité
    $ratio = $scoreMax > 0 ? $score / $scoreMax : 0;
    if ($score == 0) {
        $severite = 'ABSENT';
    } elseif ($ratio <= 0.3) {
        $severite = 'LEGERE';
    } elseif ($ratio <= 0.6) {
        $severite = 'MODEREE';
    } else {
        $severite = 'INTENSE';
    }

    // Seuils spéciaux ALGOPLUS : >1 = modéré
    if ($echelle === 'ALGOPLUS' && $score <= 1) $severite = 'LEGERE';
    if ($echelle === 'ALGOPLUS' && $score == 0) $severite = 'ABSENT';

    try {
        $db   = (new Database())->getConnection();
        $stmt = $db->prepare("
            INSERT INTO evaluations_douleur
                (patient_id, admission_id, infirmier_id, profil, echelle,
                 score, score_max, severite, items_json,
                 localisation, caracteristiques, action_prise, note_infirmier, contexte)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'AVANT_SOIN')
        ");
        $stmt->execute([
            $patient_id,
            $admission_id,
            $infirmier_id,
            $profil,
            $echelle,
            $score,
            $scoreMax,
            $severite,
            !empty($data['items']) ? json_encode($data['items']) : null,
            $data['localisation']      ?? null,
            $data['caracteristiques']  ?? null,
            $data['action_prise']      ?? null,
            $data['note']              ?? null,
        ]);

        echo json_encode([
            'success'    => true,
            'eval_id'    => $db->lastInsertId(),
            'severite'   => $severite,
            'score'      => $score,
            'score_max'  => $scoreMax,
            'echelle'    => $echelle,
        ]);
    } catch (Exception $e) {
        error_log('[Douleur] ' . $e->getMessage());
        // Si la table n'existe pas encore, on renvoie succès partiel pour ne pas bloquer l'infirmier
        echo json_encode([
            'success'  => true,
            'eval_id'  => 0,
            'severite' => $severite,
            'score'    => $score,
            'score_max'=> $scoreMax,
            'echelle'  => $echelle,
            '_warning' => 'Table evaluations_douleur manquante — migrer la DB.',
        ]);
    }
    exit;
}

/**
 * Enregistre les soins cochés (soumission du formulaire complet)
 */
public function validerExecution() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;
    $db = (new Database())->getConnection();
    $soins_faits  = $_POST['soins_faits'] ?? [];
    $admission_id = (int)($_POST['admission_id'] ?? 0);
    $patient_id   = (int)($_POST['patient_id'] ?? 0);
    $infirmier_id = $_SESSION['user_id'];

    try {
        $db->beginTransaction();
        foreach ($soins_faits as $id_soin) {
            $db->prepare("UPDATE soins_hospitalisation
                SET statut = 'REALISE', date_realisee = NOW(), user_id_executant = ?
                WHERE id = ? AND statut IN ('PLANIFIE','RETARD')")
               ->execute([$infirmier_id, $id_soin]);
        }
        $db->commit();
        // Retourner au suivi du patient
        if ($patient_id) {
            header('Location: ' . BASE_URL . 'hospitalisation/suivi/' . $patient_id . '?success=soins_enregistres');
        } else {
            header('Location: ' . BASE_URL . 'dashboard?success=soins_enregistres');
        }
    } catch (Exception $e) {
        $db->rollBack();
        die("Erreur : " . $e->getMessage());
    }
    exit;
}

/**
 * AJAX — Enregistrer un commentaire infirmier sur un soin
 */
public function sauvegarderCommentaireSoin(): void {
    header('Content-Type: application/json');
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['success' => false, 'message' => 'Méthode invalide']); exit;
    }

    $data       = json_decode(file_get_contents('php://input'), true) ?? [];
    $soinId     = (int)($data['soin_id']    ?? 0);
    $commentaire = trim($data['commentaire'] ?? '');

    if (!$soinId) {
        echo json_encode(['success' => false, 'message' => 'Soin introuvable']); exit;
    }

    try {
        // Sécurité : ne pas écraser un "Rayé:" ou "CORR." existant
        $check = $this->db->prepare(
            "SELECT note_execution, statut FROM soins_hospitalisation WHERE id = ?"
        );
        $check->execute([$soinId]);
        $soin = $check->fetch(\PDO::FETCH_ASSOC);

        if (!$soin) {
            echo json_encode(['success' => false, 'message' => 'Soin introuvable']); exit;
        }

        $existingNote = $soin['note_execution'] ?? '';
        $isRaye = str_starts_with($existingNote, 'Rayé') || str_starts_with($existingNote, 'CORR.');

        if ($isRaye) {
            echo json_encode(['success' => false, 'message' => 'Impossible de commenter un soin rayé']); exit;
        }

        $this->db->prepare(
            "UPDATE soins_hospitalisation SET note_execution = ? WHERE id = ?"
        )->execute([$commentaire ?: null, $soinId]);

        echo json_encode(['success' => true]);
    } catch (\PDOException $e) {
        error_log('[sauvegarderCommentaireSoin] ' . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Erreur base de données']);
    }
    exit;
}

/**
 * AJAX — Cocher/décocher un soin individuellement (sauvegarde partielle)
 */
public function cocherSoin() {
    header('Content-Type: application/json');
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['success'=>false]); exit; }
    $data = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $soin_id  = (int)($data['soin_id'] ?? 0);
    $cocher   = !empty($data['cocher']); // true = cocher, false = décocher
    $infirmier_id = $_SESSION['user_id'];
    try {
        $db = (new Database())->getConnection();
        if ($cocher) {
            // ── Vérifier que la date_prevue est atteinte avant d'autoriser l'exécution ──
            $stmtCheck = $db->prepare("SELECT date_prevue FROM soins_hospitalisation WHERE id = ?");
            $stmtCheck->execute([$soin_id]);
            $row = $stmtCheck->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                $datePrevue = substr($row['date_prevue'], 0, 16); // 'YYYY-MM-DD HH:MM'
                if ($datePrevue > date('Y-m-d H:i')) {
                    echo json_encode([
                        'success' => false,
                        'message' => 'Ce soin n\'est pas encore disponible. Il sera exécutable le '
                            . date('d/m/Y à H:i', strtotime($row['date_prevue'])) . '.'
                    ]);
                    exit;
                }
            }
            $db->prepare("UPDATE soins_hospitalisation
                SET statut = 'REALISE', date_realisee = NOW(), user_id_executant = ?
                WHERE id = ? AND statut IN ('PLANIFIE','RETARD')")
               ->execute([$infirmier_id, $soin_id]);
        } else {
            $db->prepare("UPDATE soins_hospitalisation
                SET statut = 'PLANIFIE', date_realisee = NULL, user_id_executant = NULL
                WHERE id = ? AND statut = 'REALISE'")
               ->execute([$soin_id]);
        }
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

/**
 * AJAX — Rayer un soin (ANNULE) et créer la correction
 */
public function rayerSoin() {
    header('Content-Type: application/json');
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['success'=>false]); exit; }
    $data = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $soin_id      = (int)($data['soin_id'] ?? 0);
    $motif        = trim($data['motif'] ?? '');
    $correction   = trim($data['correction'] ?? '');
    $infirmier_id = $_SESSION['user_id'];
    try {
        $db = (new Database())->getConnection();
        $db->beginTransaction();

        // 1. Récupérer le soin original
        $orig = $db->prepare("SELECT * FROM soins_hospitalisation WHERE id = ?");
        $orig->execute([$soin_id]);
        $soin = $orig->fetch(PDO::FETCH_ASSOC);
        if (!$soin) throw new Exception("Soin introuvable");

        // 2. Marquer l'original ANNULE
        $db->prepare("UPDATE soins_hospitalisation
            SET statut = 'ANNULE', note_execution = ?, user_id_executant = ?
            WHERE id = ?")
           ->execute(["Rayé : $motif", $infirmier_id, $soin_id]);

        // 3. Créer la ligne corrigée si une correction est fournie
        $new_id = null;
        if ($correction !== '') {
            $db->prepare("INSERT INTO soins_hospitalisation
                (admission_id, user_id_planificateur, type_soin, description,
                 condition_application, date_prevue, statut, note_execution)
                VALUES (?, ?, ?, ?, ?, ?, 'PLANIFIE', ?)")
               ->execute([
                   $soin['admission_id'],
                   $infirmier_id,
                   $soin['type_soin'],
                   $correction,
                   $soin['condition_application'],
                   $soin['date_prevue'],
                   'CORR. de #' . $soin_id,
               ]);
            $new_id = $db->lastInsertId();
        }

        $db->commit();
        echo json_encode(['success' => true, 'new_id' => $new_id]);
    } catch (Exception $e) {
        if (isset($db)) $db->rollBack();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

/**
 * AJAX — Stopper un médicament : annule ce soin + toutes les autres occurrences du même
 * médicament (même description, même jour, même admission) encore PLANIFIÉ.
 */
public function stopperSoin() {
    header('Content-Type: application/json');
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['success'=>false]); exit; }
    $data         = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $soin_id      = (int)($data['soin_id'] ?? 0);
    $motif        = trim($data['motif'] ?? '');
    $infirmier_id = $_SESSION['user_id'];
    if (!$motif) { echo json_encode(['success'=>false, 'message'=>'Motif obligatoire']); exit; }
    try {
        $db = (new Database())->getConnection();

        // Récupérer le soin de référence
        $orig = $db->prepare("SELECT * FROM soins_hospitalisation WHERE id = ?");
        $orig->execute([$soin_id]);
        $soin = $orig->fetch(PDO::FETCH_ASSOC);
        if (!$soin) throw new Exception("Soin introuvable");

        // Annuler toutes les occurrences du même médicament ce jour-là (même admission, même description, statut PLANIFIE)
        $stmt = $db->prepare("UPDATE soins_hospitalisation
            SET statut = 'ANNULE', note_execution = ?, user_id_executant = ?
            WHERE admission_id = ?
              AND description   = ?
              AND DATE(date_prevue) = DATE(?)
              AND statut NOT IN ('REALISE', 'ANNULE', 'SUPPRIME')");
        $stmt->execute([
            'Stoppé : ' . $motif,
            $infirmier_id,
            $soin['admission_id'],
            $soin['description'],
            $soin['date_prevue'],
        ]);
        $nb = $stmt->rowCount();

        echo json_encode(['success' => true, 'nb_stoppes' => $nb]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// Dans HospitalisationController.php
public function suivi($patient_id) {
    // ── Restriction service : un infirmier ne peut accéder qu'à ses patients ──
    $this->_guardInfirmierService((int)$patient_id);

    $db = (new Database())->getConnection();

    // ── Migration auto : ajouter les colonnes de suppression logique si absentes ──
    try {
        $cols = $db->query("SHOW COLUMNS FROM soins_hospitalisation LIKE 'supprime_par'")->fetchAll();
        if (empty($cols)) {
            $db->exec("ALTER TABLE soins_hospitalisation
                MODIFY COLUMN statut ENUM('PLANIFIE','REALISE','ANNULE','RETARD','SUPPRIME') DEFAULT 'PLANIFIE'");
            $db->exec("ALTER TABLE soins_hospitalisation
                ADD COLUMN motif_suppression TEXT DEFAULT NULL AFTER note_major,
                ADD COLUMN supprime_par INT DEFAULT NULL AFTER motif_suppression,
                ADD COLUMN date_suppression DATETIME DEFAULT NULL AFTER supprime_par");
        }
    } catch (\Throwable $e) { /* silencieux */ }

    // 1. Récupération des infos globales du dossier
    // On joint avec 'patients' pour avoir le nom/prénom/dossier
    // + service_hebergement (lit physique différent du service administratif)
    $hospCols = $db->query("SHOW COLUMNS FROM hospitalisations")->fetchAll(PDO::FETCH_COLUMN);
    $hasHebergCol = in_array('service_hebergement_id', $hospCols);
    $hebergJoin  = $hasHebergCol
        ? "LEFT JOIN services sh ON h.service_hebergement_id = sh.id"
        : "";
    $hebergSel   = $hasHebergCol
        ? ", sh.nom_service AS service_hebergement_nom, h.service_hebergement_id, c.nom_chambre AS chambre_hebergement"
        : ", NULL AS service_hebergement_nom, NULL AS service_hebergement_id, c.nom_chambre AS chambre_hebergement";

    $stmt = $db->prepare("SELECT h.id, p.id as patient_id, p.nom, p.prenom, p.dossier_numero,
        s.nom_service as service_nom, l.nom_lit as lit_numero,
        h.service_id AS service_appartenance_id
        $hebergSel
        FROM patients p
        JOIN hospitalisations h ON p.id = h.patient_id
        LEFT JOIN services s ON h.service_id = s.id
        LEFT JOIN lits l ON h.lit_id = l.id
        LEFT JOIN chambres c ON l.chambre_id = c.id
        $hebergJoin
        WHERE p.id = ? AND h.statut = 'en_cours'
        LIMIT 1
    ");
    $stmt->execute([$patient_id]);
    $dossier = $stmt->fetch(PDO::FETCH_ASSOC);

    // Si pas d'hospitalisation active, chercher sans filtre statut (patient sorti ou archivé)
    if (!$dossier) {
        $stmtFallback = $db->prepare("SELECT h.id, p.id as patient_id, p.nom, p.prenom, p.dossier_numero,
            s.nom_service as service_nom, l.nom_lit as lit_numero,
            h.service_id AS service_appartenance_id,
            NULL AS service_hebergement_nom, NULL AS service_hebergement_id,
            c.nom_chambre AS chambre_hebergement
            FROM patients p
            JOIN hospitalisations h ON p.id = h.patient_id
            LEFT JOIN services s ON h.service_id = s.id
            LEFT JOIN lits l ON h.lit_id = l.id
            LEFT JOIN chambres c ON l.chambre_id = c.id
            WHERE p.id = ?
            ORDER BY h.id DESC LIMIT 1
        ");
        $stmtFallback->execute([$patient_id]);
        $dossier = $stmtFallback->fetch(PDO::FETCH_ASSOC);
    }

    // Liste de tous les services cliniques (pour le sélecteur "Héberger dans un autre service")
    $tous_services = [];
    try {
        $stmtSvcs = $db->query("
            SELECT s.id, s.nom_service
            FROM services s
            INNER JOIN chambres c ON c.service_id = s.id
            INNER JOIN lits l     ON l.chambre_id = c.id
            WHERE l.statut IN ('LIBRE','DISPONIBLE')
            GROUP BY s.id, s.nom_service
            ORDER BY s.nom_service
        ");
        $tous_services = $stmtSvcs->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) { $tous_services = []; }

    // Fallback ultime si le patient n'existe pas du tout
    if (!$dossier) {
        $dossier = [
            'id' => 0, 'patient_id' => $patient_id,
            'nom' => 'Patient', 'prenom' => 'Inconnu',
            'dossier_numero' => '---', 'service_nom' => '', 'lit_numero' => '',
        ];
    }

    $patient = [
        'id'             => $dossier['patient_id'] ?? $patient_id,
        'nom'            => $dossier['nom']            ?? 'Inconnu',
        'prenom'         => $dossier['prenom']         ?? '',
        'dossier_numero' => $dossier['dossier_numero'] ?? '---',
        'date_naissance' => $dossier['date_naissance'] ?? '',
        'sexe'           => $dossier['sexe']           ?? 'M',
    ];

    // 2. Récupérer les dernières constantes — UNION de patient_parametres + parametres_vitaux
    //    pour couvrir les anciens enregistrements (Hospitalisation model) ET les nouveaux
    $dernieres_constantes = [];
    try {
        $stmtLast = $db->prepare("
            (SELECT temperature,
                    pression_arterielle_systolique,
                    pression_arterielle_diastolique,
                    pression_arterielle_systolique  AS tension_sys,
                    pression_arterielle_diastolique AS tension_dia,
                    frequence_cardiaque,
                    saturation_oxygene,
                    glycemie, frequence_respiratoire,
                    diurese, sous_oxygene, debit_oxygene,
                    observations, date_mesure
             FROM patient_parametres
             WHERE patient_id = ?
             ORDER BY date_mesure DESC LIMIT 1)
            UNION ALL
            (SELECT temperature,
                    pression_arterielle_systolique,
                    pression_arterielle_diastolique,
                    pression_arterielle_systolique  AS tension_sys,
                    pression_arterielle_diastolique AS tension_dia,
                    frequence_cardiaque,
                    saturation_oxygene,
                    NULL AS glycemie,
                    NULL AS frequence_respiratoire,
                    NULL AS diurese,
                    NULL AS sous_oxygene,
                    NULL AS debit_oxygene,
                    observation AS observations,
                    date_mesure
             FROM parametres_vitaux
             WHERE patient_id = ?
             ORDER BY date_mesure DESC LIMIT 1)
            ORDER BY date_mesure DESC LIMIT 1
        ");
        $stmtLast->execute([$patient_id, $patient_id]);
        $dernieres_constantes = $stmtLast->fetch(PDO::FETCH_ASSOC) ?: [];
    } catch (PDOException $e) {
        // parametres_vitaux peut ne pas exister — fallback simple sur patient_parametres
        try {
            $stmtFb = $db->prepare("SELECT *,
                pression_arterielle_systolique AS tension_sys,
                pression_arterielle_diastolique AS tension_dia
                FROM patient_parametres WHERE patient_id = ?
                ORDER BY date_mesure DESC LIMIT 1");
            $stmtFb->execute([$patient_id]);
            $dernieres_constantes = $stmtFb->fetch(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e2) {
            $dernieres_constantes = [];
        }
    }

    // 3. Récupérer l'historique pour les graphiques — UNION des deux tables
    $constantes = [];
    try {
        $stmtHist = $db->prepare("
            SELECT date_mesure, temperature,
                   pression_arterielle_systolique AS tension_sys,
                   pression_arterielle_diastolique AS tension_dia,
                   frequence_cardiaque
            FROM patient_parametres
            WHERE patient_id = ?
            UNION ALL
            SELECT date_mesure, temperature,
                   pression_arterielle_systolique AS tension_sys,
                   pression_arterielle_diastolique AS tension_dia,
                   frequence_cardiaque
            FROM parametres_vitaux
            WHERE patient_id = ?
            ORDER BY date_mesure ASC LIMIT 20
        ");
        $stmtHist->execute([$patient_id, $patient_id]);
        $constantes = $stmtHist->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        try {
            $stmtHist2 = $db->prepare("SELECT date_mesure, temperature,
                pression_arterielle_systolique AS tension_sys,
                pression_arterielle_diastolique AS tension_dia,
                frequence_cardiaque
                FROM patient_parametres WHERE patient_id = ?
                ORDER BY date_mesure ASC LIMIT 20");
            $stmtHist2->execute([$patient_id]);
            $constantes = $stmtHist2->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e2) {
            $constantes = [];
        }
    }

    // 4. Récupérer les soins (via l'ID d'hospitalisation) + nom du suppresseur
    $tous_les_soins = [];
    if (!empty($dossier['id'])) {
        try {
            $stmtSoins = $db->prepare("
                SELECT sh.*,
                       us.nom as supprime_par_nom, us.prenom as supprime_par_prenom
                FROM soins_hospitalisation sh
                LEFT JOIN users us ON sh.supprime_par = us.id
                WHERE sh.admission_id = ?
                ORDER BY sh.date_prevue ASC
            ");
            $stmtSoins->execute([$dossier['id']]);
            $tous_les_soins = $stmtSoins->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            // Fallback si les colonnes de suppression n'existent pas encore (migration non appliquée)
            $stmtSoins = $db->prepare("SELECT * FROM soins_hospitalisation WHERE admission_id = ? ORDER BY date_prevue ASC");
            $stmtSoins->execute([$dossier['id']]);
            $tous_les_soins = $stmtSoins->fetchAll(PDO::FETCH_ASSOC);
        }
    }

    // 5. Récupérer les observations de surveillance intensive
    $si_data = [];
    try {
        $stmtSI = $db->prepare("SELECT * FROM surveillance_intensive_obs WHERE patient_id = ? ORDER BY created_at DESC LIMIT 30");
        $stmtSI->execute([$patient_id]);
        $si_data = $stmtSI->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // Table may not exist yet — silently ignore
        $si_data = [];
    }

    // 6b. Récupérer l'historique complet des fiches de paramètres (avec observations)
    $historique_parametres = [];
    try {
        $stmtParamHist = $db->prepare("
            SELECT pp.*,
                   u.nom       AS infirmier_nom,
                   u.prenom    AS infirmier_prenom,
                   u.role      AS infirmier_role
            FROM patient_parametres pp
            LEFT JOIN users u ON pp.user_id = u.id
            WHERE pp.patient_id = ?
            ORDER BY pp.date_mesure DESC
            LIMIT 50
        ");
        $stmtParamHist->execute([$patient_id]);
        $historique_parametres = $stmtParamHist->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $historique_parametres = [];
    }

    // 6. Récupérer les formulaires remplis pour ce patient
    $formulaires_remplis = [];
    try {
        require_once __DIR__ . '/FormulaireController.php';
        $stmtFD = $db->prepare("
            SELECT fd.id, fd.titre, fd.type_formulaire, fd.statut,
                   fd.date_creation, fd.date_modification,
                   u.nom AS user_nom, u.prenom AS user_prenom, u.role AS user_role
            FROM formulaires_data fd
            JOIN users u ON fd.user_id = u.id
            WHERE fd.patient_id = ?
            ORDER BY COALESCE(fd.date_modification, fd.date_creation) DESC
            LIMIT 50
        ");
        $stmtFD->execute([$patient_id]);
        $formulaires_remplis = $stmtFD->fetchAll(\PDO::FETCH_ASSOC);
    } catch (\PDOException $e) {
        $formulaires_remplis = [];
    }

    // 7. Réévaluations médicales récentes (10 dernières)
    $reevaluations_medecin = [];
    try {
        $stmtRev = $db->prepare("
            SELECT r.id, r.date_reevaluation, r.heure_reevaluation,
                   r.evolution_globale, r.note_evolution,
                   r.diagnostic_jour,  r.code_cim10,
                   r.conduite_tenir,   r.traitement_non_medicamenteux,
                   r.plaintes_jour,
                   u.nom AS medecin_nom, u.prenom AS medecin_prenom, u.role AS medecin_role
            FROM reevaluations_hospitalisees r
            JOIN users u ON r.medecin_id = u.id
            WHERE r.patient_id = ?
            ORDER BY r.date_reevaluation DESC, r.heure_reevaluation DESC
            LIMIT 10
        ");
        $stmtRev->execute([$patient_id]);
        $reevaluations_medecin = $stmtRev->fetchAll(PDO::FETCH_ASSOC);

        foreach ($reevaluations_medecin as &$rev) {
            $rid = (int)$rev['id'];

            $stmtBilans = $db->prepare("
                SELECT type, intitule, urgence, note
                FROM reevaluation_bilans
                WHERE reevaluation_id = ? ORDER BY type, id
            ");
            $stmtBilans->execute([$rid]);
            $rev['bilans'] = $stmtBilans->fetchAll(PDO::FETCH_ASSOC);

            $stmtMeds = $db->prepare("
                SELECT nom_medicament, posologie, voie_administration, frequence, duree, notes
                FROM reevaluation_medicaments
                WHERE reevaluation_id = ? ORDER BY id
            ");
            $stmtMeds->execute([$rid]);
            $rev['medicaments'] = $stmtMeds->fetchAll(PDO::FETCH_ASSOC);
        }
        unset($rev);
    } catch (PDOException $e) {
        $reevaluations_medecin = [];
    }

    // 8. Évaluations de la douleur (20 dernières)
    $evaluations_douleur = [];
    try {
        $stmtDoul = $db->prepare("
            SELECT ed.id, ed.date_evaluation, ed.profil, ed.echelle,
                   ed.score, ed.score_max, ed.severite, ed.contexte,
                   ed.localisation, ed.caracteristiques,
                   ed.action_prise, ed.note_infirmier,
                   u.nom AS inf_nom, u.prenom AS inf_prenom
            FROM evaluations_douleur ed
            LEFT JOIN users u ON ed.infirmier_id = u.id
            WHERE ed.patient_id = ?
            ORDER BY ed.date_evaluation DESC
            LIMIT 20
        ");
        $stmtDoul->execute([$patient_id]);
        $evaluations_douleur = $stmtDoul->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) { /* table absente — non bloquant */ }

    // 9. Fiches transfusionnelles du patient
    $transfusions = [];
    try {
        $stmtTr = $db->prepare("
            SELECT t.*,
                   u.nom AS auteur_nom, u.prenom AS auteur_prenom
            FROM patient_transfusions t
            LEFT JOIN users u ON t.user_id = u.id
            WHERE t.patient_id = ?
            ORDER BY t.created_at DESC
        ");
        $stmtTr->execute([$patient_id]);
        $transfusions = $stmtTr->fetchAll(PDO::FETCH_ASSOC);

        if (!empty($transfusions)) {
            $stmtPoch = $db->prepare("
                SELECT * FROM patient_transfusion_poches
                WHERE transfusion_id = ?
                ORDER BY id ASC
            ");
            foreach ($transfusions as &$tr) {
                $stmtPoch->execute([(int)$tr['id']]);
                $tr['poches'] = $stmtPoch->fetchAll(PDO::FETCH_ASSOC);
            }
            unset($tr);
        }
    } catch (\Throwable $e) { /* table absente — non bloquant */ }

    // 10. Chargement de la vue
    require_once __DIR__ . '/../views/hospitalisation/suivi.php';
}

/* ================================================================
   RÉÉVALUATION MÉDICALE HOSPITALISÉ — Ronde / Visite au chevet
   ================================================================ */

public function reevaluation($patient_id) {
    $db = (new Database())->getConnection();

    require_once __DIR__ . '/../models/Patient.php';
    $patientModel = new Patient();
    $patient = $patientModel->getById($patient_id);
    if (!$patient) {
        header('Location: ' . BASE_URL . 'hospitalisation'); exit;
    }

    $age = ($patient['date_naissance'] ?? null)
        ? date_diff(date_create($patient['date_naissance']), date_create('now'))->y
        : 'N/A';

    // Hospitalisation en cours
    $stmtH = $db->prepare("
        SELECT h.*, l.nom_lit, c.nom_chambre, s.nom_service
        FROM hospitalisations h
        LEFT JOIN lits l      ON h.lit_id = l.id
        LEFT JOIN chambres c  ON l.chambre_id = c.id
        LEFT JOIN services s  ON h.service_id = s.id
        WHERE h.patient_id = ? AND h.statut = 'en_cours'
        ORDER BY h.id DESC LIMIT 1
    ");
    $stmtH->execute([$patient_id]);
    $hospitalisation = $stmtH->fetch(PDO::FETCH_ASSOC) ?: [];

    // ── Mode édition : charger une réévaluation existante si ?edit=ID ────
    $reeval_to_edit = null;
    $reeval_meds_edit  = [];
    $reeval_bilans_edit = [];
    $editId = (int)($_GET['edit'] ?? 0);
    $userId = (int)($_SESSION['user_id'] ?? 0);
    $userRole = strtoupper($_SESSION['user_role'] ?? '');
    $isAdmin = in_array($userRole, ['ADMIN','ADMINISTRATEUR','DIRECTEUR','MEDECIN_CHEF']);

    if ($editId > 0) {
        try {
            $stmtE = $db->prepare("
                SELECT * FROM reevaluations_hospitalisees
                WHERE id = ? AND patient_id = ?
            ");
            $stmtE->execute([$editId, $patient_id]);
            $reeval_to_edit = $stmtE->fetch(PDO::FETCH_ASSOC) ?: null;

            // Vérifier les droits d'édition (auteur OU admin/directeur)
            if ($reeval_to_edit && !$isAdmin && (int)$reeval_to_edit['medecin_id'] !== $userId) {
                $reeval_to_edit = null; // pas le droit
                $_SESSION['flash_error'] = "Vous ne pouvez modifier que vos propres réévaluations.";
            }

            if ($reeval_to_edit) {
                // Charger les médicaments et bilans liés
                $stmtMed = $db->prepare("SELECT * FROM reevaluation_medicaments WHERE reevaluation_id = ?");
                $stmtMed->execute([$editId]);
                $reeval_meds_edit = $stmtMed->fetchAll(PDO::FETCH_ASSOC);

                $stmtB = $db->prepare("SELECT * FROM reevaluation_bilans WHERE reevaluation_id = ?");
                $stmtB->execute([$editId]);
                $reeval_bilans_edit = $stmtB->fetchAll(PDO::FETCH_ASSOC);
            }
        } catch (PDOException $e) {
            $reeval_to_edit = null;
        }
    }

    // Historique des réévaluations (20 dernières)
    $reevaluations = [];
    try {
        $stmtR = $db->prepare("
            SELECT r.*,
                   u.nom  AS medecin_nom,
                   u.prenom AS medecin_prenom,
                   (SELECT COUNT(*) FROM reevaluation_medicaments rm WHERE rm.reevaluation_id = r.id) AS nb_medicaments,
                   (SELECT COUNT(*) FROM reevaluation_bilans      rb WHERE rb.reevaluation_id = r.id) AS nb_bilans
            FROM reevaluations_hospitalisees r
            JOIN users u ON r.medecin_id = u.id
            WHERE r.patient_id = ?
            ORDER BY r.date_reevaluation DESC, r.heure_reevaluation DESC
            LIMIT 20
        ");
        $stmtR->execute([$patient_id]);
        $reevaluations = $stmtR->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // Table pas encore créée — migration non exécutée
        $reevaluations = [];
    }

    // Dernières constantes vitales
    $dernieres_constantes = [];
    try {
        $stmtC = $db->prepare("
            SELECT * FROM patient_parametres
            WHERE patient_id = ?
            ORDER BY id DESC LIMIT 1
        ");
        $stmtC->execute([$patient_id]);
        $dernieres_constantes = $stmtC->fetch(PDO::FETCH_ASSOC) ?: [];
    } catch (PDOException $e) {}

    require_once __DIR__ . '/../views/hospitalisation/reevaluation.php';
}

public function saveReevaluation() {
    header('Content-Type: application/json');
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['success' => false, 'message' => 'Méthode invalide']); exit;
    }

    $db        = (new Database())->getConnection();
    $userId    = (int)($_SESSION['user_id'] ?? 0);
    $patientId = (int)($_POST['patient_id'] ?? 0);
    $hospId    = (int)($_POST['hospitalisation_id'] ?? 0);

    if (!$patientId || !$userId) {
        echo json_encode(['success' => false, 'message' => 'Données manquantes']); exit;
    }

    try {
        $db->beginTransaction();

        // 1. INSERT réévaluation principale
        $stmt = $db->prepare("
            INSERT INTO reevaluations_hospitalisees
                (hospitalisation_id, patient_id, medecin_id,
                 date_reevaluation, heure_reevaluation,
                 plaintes_jour, histoire_maladie,
                 examen_general, examen_cardiovasculaire, examen_respiratoire,
                 examen_digestif, examen_neurologique, examen_osteoarticulaire, examen_autres,
                 evolution_globale, note_evolution,
                 diagnostic_jour, code_cim10, conduite_tenir, traitement_non_medicamenteux)
            VALUES (?, ?, ?,
                    CURDATE(), CURTIME(),
                    ?, ?,
                    ?, ?, ?,
                    ?, ?, ?, ?,
                    ?, ?,
                    ?, ?, ?, ?)
        ");
        $stmt->execute([
            $hospId ?: null,
            $patientId,
            $userId,
            trim($_POST['plaintes_jour']                   ?? '') ?: null,
            trim($_POST['histoire_maladie']                ?? '') ?: null,
            trim($_POST['examen_general']                  ?? '') ?: null,
            trim($_POST['examen_cardiovasculaire']         ?? '') ?: null,
            trim($_POST['examen_respiratoire']             ?? '') ?: null,
            trim($_POST['examen_digestif']                 ?? '') ?: null,
            trim($_POST['examen_neurologique']             ?? '') ?: null,
            trim($_POST['examen_osteoarticulaire']         ?? '') ?: null,
            trim($_POST['examen_autres']                   ?? '') ?: null,
            $_POST['evolution_globale'] ?? 'STATUO_QUO',
            trim($_POST['note_evolution']                  ?? '') ?: null,
            trim($_POST['diagnostic_jour']                 ?? '') ?: null,
            trim($_POST['code_cim10']                      ?? '') ?: null,
            trim($_POST['conduite_tenir']                  ?? '') ?: null,
            trim($_POST['traitement_non_medicamenteux']    ?? '') ?: null,
        ]);
        $reevalId = (int)$db->lastInsertId();

        // 2. Médicaments + ordonnance formelle pour la pharmacie
        $medicaments = array_filter($_POST['medicaments'] ?? [], fn($m) => trim($m['nom'] ?? '') !== '');
        if (!empty($medicaments)) {
            // 2a. Stocker dans reevaluation_medicaments
            $stmtMed = $db->prepare("
                INSERT INTO reevaluation_medicaments
                    (reevaluation_id, medicament_id, nom_medicament,
                     posologie, voie_administration, frequence, duree, quantite)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            foreach ($medicaments as $med) {
                $stmtMed->execute([
                    $reevalId,
                    $med['id']        ? (int)$med['id'] : null,
                    trim($med['nom']),
                    trim($med['posologie'] ?? ''),
                    trim($med['voie']      ?? ''),
                    trim($med['frequence'] ?? ''),
                    trim($med['duree']     ?? ''),
                    max(1, (int)($med['quantite'] ?? 1)),
                ]);
            }

            // 2b. Créer une ordonnance formelle pour la pharmacie
            $stmtOrd = $db->prepare("
                INSERT INTO ordonnances_pharmacie
                    (patient_id, medecin_id, consultation_id,
                     date_creation, statut, type_ordonnance, notes)
                VALUES (?, ?, NULL, NOW(), 'SIGNEE', 'NORMALE', ?)
            ");
            $stmtOrd->execute([
                $patientId,
                $userId,
                'Réévaluation médicale du ' . date('d/m/Y') . ' — Patient hospitalisé',
            ]);
            $prescriptionId = (int)$db->lastInsertId();

            // 2c. Lignes de l'ordonnance
            $stmtLigne = $db->prepare("
                INSERT INTO ordonnance_medicaments
                    (ordonnance_id, medicament_id, nom_medicament, posologie, duree, quantite)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            foreach ($medicaments as $med) {
                $poso  = trim($med['posologie'] ?? '');
                $voie  = trim($med['voie']      ?? '');
                $freq  = trim($med['frequence'] ?? '');
                $full  = implode(' — ', array_filter([$poso, $freq, $voie ? 'Voie ' . $voie : '']));
                $stmtLigne->execute([
                    $prescriptionId,
                    $med['id'] ? (int)$med['id'] : null,
                    trim($med['nom']),
                    $full,
                    trim($med['duree'] ?? ''),
                    max(1, (int)($med['quantite'] ?? 1)),
                ]);
            }

            // 2d. Lier la prescription à la réévaluation
            $db->prepare("UPDATE reevaluations_hospitalisees SET prescription_id = ? WHERE id = ?")
               ->execute([$prescriptionId, $reevalId]);
        }

        // 3. Bilans → gérés séparément via POST hospitalisation/creer-bilans-reeval

        $db->commit();
        echo json_encode(['success' => true, 'reevaluation_id' => $reevalId]);

    } catch (PDOException $e) {
        $db->rollBack();
        error_log('saveReevaluation error: ' . $e->getMessage());
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

/**
 * POST hospitalisation/creer-bilans-reeval
 * Body JSON : { patient_id, reevaluation_id, bilans_labo:[{id,nom,urgent,a_jeun,instructions}], bilans_imagerie:[...] }
 * Crée demandes_laboratoire + demande_examens sans passer par FormData.
 */
public function creerBilansReeval(): void {
    header('Content-Type: application/json');
    $input = json_decode(file_get_contents('php://input'), true) ?? [];

    $patientId    = (int)($input['patient_id']    ?? 0);
    $reevalId     = (int)($input['reevaluation_id'] ?? 0);
    $bilansLabo   = $input['bilans_labo']    ?? [];
    $bilansImg    = $input['bilans_imagerie'] ?? [];
    $userId       = (int)($_SESSION['user_id'] ?? 0);

    if (!$patientId || !$userId) {
        echo json_encode(['success' => false, 'message' => 'Paramètres manquants']); return;
    }

    $results = ['labo_created' => 0, 'img_created' => 0, 'demande_id' => null];

    try {
        $db = (new Database())->getConnection();

        /* ── Laboratoire ── */
        if (!empty($bilansLabo)) {
            $hasUrgent = !empty(array_filter($bilansLabo, fn($e) => !empty($e['urgent'])));
            $db->prepare("
                INSERT INTO demandes_laboratoire (patient_id, medecin_id, statut, urgence, date_creation)
                VALUES (?, ?, 'EN_ATTENTE', ?, NOW())
            ")->execute([$patientId, $userId, $hasUrgent ? 'URGENT' : 'NORMAL']);
            $demandeId = (int)$db->lastInsertId();
            $results['demande_id'] = $demandeId;

            $stmtDE = $db->prepare("
                INSERT INTO demande_examens (demande_id, examen_id, urgent, a_jeun, instructions, statut)
                VALUES (?, ?, ?, ?, ?, 'EN_ATTENTE')
            ");
            $noms = [];
            foreach ($bilansLabo as $ex) {
                $exId = (int)($ex['id'] ?? 0);
                if ($exId > 0) {
                    $stmtDE->execute([
                        $demandeId, $exId,
                        !empty($ex['urgent'])  ? 1 : 0,
                        !empty($ex['a_jeun'])  ? 1 : 0,
                        trim($ex['instructions'] ?? '')
                    ]);
                    $results['labo_created']++;
                }
                if (!empty($ex['nom'])) $noms[] = $ex['nom'];
            }

            /* Traçabilité */
            if ($reevalId && !empty($noms)) {
                try {
                    $db->prepare("
                        INSERT INTO reevaluation_bilans (reevaluation_id, type, intitule, urgence, note, demande_id)
                        VALUES (?, 'LABO', ?, ?, NULL, ?)
                    ")->execute([$reevalId, implode(', ', $noms), $hasUrgent ? 1 : 0, $demandeId]);
                } catch (\Throwable $e) {}
            }
        }

        /* ── Imagerie ── */
        foreach ($bilansImg as $img) {
            $typeImg = trim(($img['modalite'] ?? '') . ' ' . ($img['partie_corps'] ?? $img['zone'] ?? ''));
            if (!$typeImg) $typeImg = trim($img['intitule'] ?? $img['label'] ?? '');
            if (!$typeImg) continue;
            $db->prepare("
                INSERT INTO demandes_imagerie (patient_id, medecin_id, statut, urgence, date_creation, type_imagerie)
                VALUES (?, ?, 'EN_ATTENTE', ?, NOW(), ?)
            ")->execute([$patientId, $userId,
                (!empty($img['urgence']) && $img['urgence'] === 'URGENT') ? 'URGENT' : 'NORMAL',
                $typeImg]);
            $results['img_created']++;
        }

        echo json_encode(['success' => true] + $results);

    } catch (\Throwable $e) {
        error_log('[creerBilansReeval] ' . $e->getMessage());
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

public function searchMedicamentReeval() {
    header('Content-Type: application/json');
    $q = trim($_GET['q'] ?? '');
    if (strlen($q) < 2) { echo json_encode([]); exit; }
    $db   = (new Database())->getConnection();
    $stmt = $db->prepare("SELECT id, nom, forme, dosage FROM medicaments WHERE nom LIKE ? ORDER BY nom ASC LIMIT 12");
    $stmt->execute(['%' . $q . '%']);
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    exit;
}

/**
 * Met à jour une réévaluation existante.
 *
 * Comportement :
 *   - UPDATE de la fiche principale (champs textuels + évolution)
 *   - DELETE + RE-INSERT de reevaluation_medicaments et reevaluation_bilans
 *     (plus simple que diff)
 *   - Si une ordonnance liée existe ET n'a PAS encore été délivrée,
 *     ses lignes sont remplacées pour matcher les nouveaux médicaments
 *   - Si l'ordonnance est déjà TERMINEE, elle reste intacte (avertissement
 *     dans la réponse)
 *
 * Permissions : seul l'auteur ou un admin peut modifier.
 */
public function updateReevaluation(int $id) {
    header('Content-Type: application/json');
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['success' => false, 'message' => 'Méthode invalide']); exit;
    }

    $db        = (new Database())->getConnection();
    $userId    = (int)($_SESSION['user_id'] ?? 0);
    $userRole  = strtoupper($_SESSION['user_role'] ?? '');
    $isAdmin   = in_array($userRole, ['ADMIN','ADMINISTRATEUR','DIRECTEUR','MEDECIN_CHEF']);

    try {
        // 1. Charger la réévaluation existante et vérifier les droits
        $stmt = $db->prepare("SELECT * FROM reevaluations_hospitalisees WHERE id = ?");
        $stmt->execute([$id]);
        $reeval = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$reeval) {
            echo json_encode(['success' => false, 'message' => 'Réévaluation introuvable.']); exit;
        }
        if (!$isAdmin && (int)$reeval['medecin_id'] !== $userId) {
            echo json_encode([
                'success' => false,
                'message' => 'Vous ne pouvez modifier que vos propres réévaluations.'
            ]); exit;
        }

        $db->beginTransaction();

        // 2. UPDATE de la fiche principale
        $db->prepare("
            UPDATE reevaluations_hospitalisees SET
                plaintes_jour                = ?,
                histoire_maladie             = ?,
                examen_general               = ?,
                examen_cardiovasculaire      = ?,
                examen_respiratoire          = ?,
                examen_digestif              = ?,
                examen_neurologique          = ?,
                examen_osteoarticulaire      = ?,
                examen_autres                = ?,
                evolution_globale            = ?,
                note_evolution               = ?,
                diagnostic_jour              = ?,
                code_cim10                   = ?,
                conduite_tenir               = ?,
                traitement_non_medicamenteux = ?
            WHERE id = ?
        ")->execute([
            trim($_POST['plaintes_jour']                ?? '') ?: null,
            trim($_POST['histoire_maladie']             ?? '') ?: null,
            trim($_POST['examen_general']               ?? '') ?: null,
            trim($_POST['examen_cardiovasculaire']      ?? '') ?: null,
            trim($_POST['examen_respiratoire']          ?? '') ?: null,
            trim($_POST['examen_digestif']              ?? '') ?: null,
            trim($_POST['examen_neurologique']          ?? '') ?: null,
            trim($_POST['examen_osteoarticulaire']      ?? '') ?: null,
            trim($_POST['examen_autres']                ?? '') ?: null,
            $_POST['evolution_globale'] ?? 'STATUO_QUO',
            trim($_POST['note_evolution']               ?? '') ?: null,
            trim($_POST['diagnostic_jour']              ?? '') ?: null,
            trim($_POST['code_cim10']                   ?? '') ?: null,
            trim($_POST['conduite_tenir']               ?? '') ?: null,
            trim($_POST['traitement_non_medicamenteux'] ?? '') ?: null,
            $id,
        ]);

        // 3. Médicaments : DELETE + RE-INSERT
        $medicaments = array_filter($_POST['medicaments'] ?? [], fn($m) => trim($m['nom'] ?? '') !== '');

        $db->prepare("DELETE FROM reevaluation_medicaments WHERE reevaluation_id = ?")->execute([$id]);
        if (!empty($medicaments)) {
            $stmtMed = $db->prepare("
                INSERT INTO reevaluation_medicaments
                    (reevaluation_id, medicament_id, nom_medicament,
                     posologie, voie_administration, frequence, duree, quantite)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            foreach ($medicaments as $med) {
                $stmtMed->execute([
                    $id,
                    !empty($med['id']) ? (int)$med['id'] : null,
                    trim($med['nom']),
                    trim($med['posologie'] ?? ''),
                    trim($med['voie']      ?? ''),
                    trim($med['frequence'] ?? ''),
                    trim($med['duree']     ?? ''),
                    max(1, (int)($med['quantite'] ?? 1)),
                ]);
            }
        }

        // 3b. Mettre à jour l'ordonnance liée SI elle existe et n'est PAS terminée
        $ordonnanceWarning = null;
        if (!empty($reeval['prescription_id'])) {
            $stmtOrd = $db->prepare("SELECT statut FROM ordonnances_pharmacie WHERE id = ?");
            $stmtOrd->execute([$reeval['prescription_id']]);
            $ordRow = $stmtOrd->fetch(PDO::FETCH_ASSOC);

            if ($ordRow && $ordRow['statut'] !== 'TERMINEE') {
                // Remplacer les lignes de l'ordonnance
                $db->prepare("DELETE FROM ordonnance_medicaments WHERE ordonnance_id = ?")
                   ->execute([$reeval['prescription_id']]);

                if (!empty($medicaments)) {
                    $stmtLigne = $db->prepare("
                        INSERT INTO ordonnance_medicaments
                            (ordonnance_id, medicament_id, nom_medicament, posologie, duree, quantite)
                        VALUES (?, ?, ?, ?, ?, ?)
                    ");
                    foreach ($medicaments as $med) {
                        $poso = trim($med['posologie'] ?? '');
                        $voie = trim($med['voie']      ?? '');
                        $freq = trim($med['frequence'] ?? '');
                        $full = implode(' — ', array_filter([$poso, $freq, $voie ? 'Voie ' . $voie : '']));
                        $stmtLigne->execute([
                            (int)$reeval['prescription_id'],
                            !empty($med['id']) ? (int)$med['id'] : null,
                            trim($med['nom']),
                            $full,
                            trim($med['duree'] ?? ''),
                            max(1, (int)($med['quantite'] ?? 1)),
                        ]);
                    }
                }
                // Repasser l'ordonnance en SIGNEE pour qu'elle soit re-traitée par la pharmacie
                $db->prepare("UPDATE ordonnances_pharmacie SET statut = 'SIGNEE', notes = ? WHERE id = ?")
                   ->execute(['Réévaluation modifiée le ' . date('d/m/Y H:i'), (int)$reeval['prescription_id']]);
            } elseif ($ordRow && $ordRow['statut'] === 'TERMINEE') {
                $ordonnanceWarning = "L'ordonnance précédemment générée a déjà été délivrée par la pharmacie. "
                                   . "Les modifications de médicaments ne sont pas répercutées sur la pharmacie. "
                                   . "Pour prescrire de nouveaux médicaments, créez une nouvelle réévaluation.";
            }
        } elseif (!empty($medicaments)) {
            // Pas d'ordonnance précédente → en créer une nouvelle
            $stmtOrdNew = $db->prepare("
                INSERT INTO ordonnances_pharmacie
                    (patient_id, medecin_id, consultation_id,
                     date_creation, statut, type_ordonnance, notes)
                VALUES (?, ?, NULL, NOW(), 'SIGNEE', 'NORMALE', ?)
            ");
            $stmtOrdNew->execute([
                (int)$reeval['patient_id'],
                $userId,
                'Réévaluation médicale du ' . date('d/m/Y') . ' (modifiée) — Patient hospitalisé',
            ]);
            $newPrescriptionId = (int)$db->lastInsertId();

            $stmtLigneNew = $db->prepare("
                INSERT INTO ordonnance_medicaments
                    (ordonnance_id, medicament_id, nom_medicament, posologie, duree, quantite)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            foreach ($medicaments as $med) {
                $poso = trim($med['posologie'] ?? '');
                $voie = trim($med['voie']      ?? '');
                $freq = trim($med['frequence'] ?? '');
                $full = implode(' — ', array_filter([$poso, $freq, $voie ? 'Voie ' . $voie : '']));
                $stmtLigneNew->execute([
                    $newPrescriptionId,
                    !empty($med['id']) ? (int)$med['id'] : null,
                    trim($med['nom']),
                    $full,
                    trim($med['duree'] ?? ''),
                    max(1, (int)($med['quantite'] ?? 1)),
                ]);
            }
            $db->prepare("UPDATE reevaluations_hospitalisees SET prescription_id = ? WHERE id = ?")
               ->execute([$newPrescriptionId, $id]);
        }

        // 4. Bilans : DELETE + RE-INSERT
        $bilans = array_filter($_POST['bilans'] ?? [], fn($b) => trim($b['intitule'] ?? '') !== '');

        $db->prepare("DELETE FROM reevaluation_bilans WHERE reevaluation_id = ?")->execute([$id]);
        if (!empty($bilans)) {
            $stmtBilan = $db->prepare("
                INSERT INTO reevaluation_bilans
                    (reevaluation_id, type, intitule, urgence, note)
                VALUES (?, ?, ?, ?, ?)
            ");
            foreach ($bilans as $b) {
                $type     = in_array($b['type'] ?? '', ['LABO','IMAGERIE']) ? $b['type'] : 'LABO';
                $isUrgent = !empty($b['urgent']) ? 1 : 0;
                $stmtBilan->execute([$id, $type, trim($b['intitule']), $isUrgent, trim($b['note'] ?? '') ?: null]);
            }
        }

        // 5. Audit
        try {
            require_once __DIR__ . '/../services/AuditService.php';
            (new AuditService())->logAction(
                'UPDATE', 'reevaluations_hospitalisees', $id, null,
                "Modification réévaluation médicale du patient #{$reeval['patient_id']}"
            );
        } catch (\Throwable $e) { /* ignorer si AuditService manquant */ }

        $db->commit();

        $msg = 'Réévaluation modifiée avec succès.';
        if ($ordonnanceWarning) {
            $msg .= ' ⚠ ' . $ordonnanceWarning;
        }

        echo json_encode([
            'success'  => true,
            'message'  => $msg,
            'reeval_id'=> $id,
            'warning'  => $ordonnanceWarning,
        ]);
    } catch (\Throwable $e) {
        if ($db->inTransaction()) $db->rollBack();
        error_log('updateReevaluation error: ' . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Erreur : ' . $e->getMessage()]);
    }
    exit;
}

/**
 * GET /hospitalisation/suivi-rapide/{patient_id}
 * Résumé JSON des activités infirmières du jour pour le cockpit médecin.
 */
public function suiviRapide(int $patient_id): void {
    header('Content-Type: application/json');

    // Accès : tout rôle non-infirmier autorisé ; infirmier uniquement son service
    if (!$this->_guardInfirmierService($patient_id, true)) {
        exit;
    }

    $db = $this->db;

    try {
        // 1. Infos patient + hospitalisation active
        $stmtPat = $db->prepare("
            SELECT p.nom, p.prenom, p.dossier_numero,
                   h.id AS hosp_id, h.date_admission, h.motif_hospitalisation,
                   s.nom_service, l.nom_lit, c.nom_chambre
            FROM patients p
            JOIN hospitalisations h ON h.patient_id = p.id AND h.statut = 'en_cours'
            LEFT JOIN services s ON s.id = h.service_id
            LEFT JOIN lits l ON l.id = h.lit_id
            LEFT JOIN chambres c ON c.id = l.chambre_id
            WHERE p.id = ?
            LIMIT 1
        ");
        $stmtPat->execute([$patient_id]);
        $pat = $stmtPat->fetch(PDO::FETCH_ASSOC);
        if (!$pat) {
            echo json_encode(['success' => false, 'message' => 'Aucune hospitalisation active trouvée.']);
            exit;
        }

        $hospId = (int)$pat['hosp_id'];

        // 2. Soins du jour (PLANIFIE / REALISE / ANNULE / RETARD)
        $today = date('Y-m-d');
        $stmtSoins = $db->prepare("
            SELECT sh.description AS intitule_soin,
                   sh.type_soin,
                   sh.statut,
                   sh.date_prevue,
                   sh.date_realisee,
                   u.nom AS infirmier_nom
            FROM soins_hospitalisation sh
            LEFT JOIN users u ON u.id = sh.user_id_executant
            WHERE sh.admission_id = ?
              AND (DATE(sh.date_prevue) = ? OR DATE(sh.date_realisee) = ?)
            ORDER BY COALESCE(sh.date_realisee, sh.date_prevue) ASC
            LIMIT 30
        ");
        $stmtSoins->execute([$hospId, $today, $today]);
        $soins = $stmtSoins->fetchAll(PDO::FETCH_ASSOC);

        // 3. Dernières constantes — table patient_parametres
        $constantes = null;
        try {
            $stmtConst = $db->prepare("
                SELECT pression_arterielle_systolique  AS tension_systolique,
                       pression_arterielle_diastolique AS tension_diastolique,
                       frequence_cardiaque             AS pouls,
                       temperature,
                       saturation_oxygene              AS saturation_o2,
                       frequence_respiratoire,
                       glycemie,
                       NULL                            AS poids,
                       date_mesure
                FROM patient_parametres
                WHERE patient_id = ?
                ORDER BY date_mesure DESC
                LIMIT 1
            ");
            $stmtConst->execute([$patient_id]);
            $constantes = $stmtConst->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (\Throwable $e) { /* ignorer si table absente */ }

        // 4. Notes / observations infirmières récentes (48h)
        $notes = [];
        try {
            $stmtNotes = $db->prepare("
                SELECT oe.texte, oe.created_at, u.nom AS auteur
                FROM observations_evolution oe
                LEFT JOIN users u ON u.id = oe.user_id
                WHERE oe.patient_id = ?
                  AND oe.created_at >= DATE_SUB(NOW(), INTERVAL 48 HOUR)
                ORDER BY oe.created_at DESC
                LIMIT 5
            ");
            $stmtNotes->execute([$patient_id]);
            $notes = $stmtNotes->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Throwable $e) { /* ignorer si table absente */ }

        // 5. Résumé compteurs soins
        $total    = count($soins);
        $realises = count(array_filter($soins, fn($s) => ($s['statut'] ?? '') === 'REALISE'));
        $retards  = count(array_filter($soins, fn($s) => ($s['statut'] ?? '') === 'RETARD'));
        $annules  = count(array_filter($soins, fn($s) => ($s['statut'] ?? '') === 'ANNULE'));

        echo json_encode([
            'success'    => true,
            'patient'    => $pat,
            'soins'      => $soins,
            'constantes' => $constantes,
            'notes'      => $notes,
            'stats'      => [
                'total'    => $total,
                'realises' => $realises,
                'retards'  => $retards,
                'annules'  => $annules,
                'planifies'=> $total - $realises - $retards - $annules,
            ],
        ]);
    } catch (\Throwable $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

/* ================================================================
   RÉÉVALUATIONS RAPIDE — endpoint AJAX pour la modal du dashboard
   GET hospitalisation/reevaluations-rapide/{patient_id}
   ================================================================ */
public function reevaluationsRapide(int $patient_id): void {
    header('Content-Type: application/json');

    if (!$this->_guardInfirmierService($patient_id, true)) {
        exit;
    }

    $db = $this->db;

    try {
        // Vérifier que le patient est bien hospitalisé
        $stmtPat = $db->prepare("
            SELECT p.nom, p.prenom,
                   h.date_admission, s.nom_service
            FROM patients p
            JOIN hospitalisations h ON h.patient_id = p.id AND h.statut = 'en_cours'
            LEFT JOIN services s ON s.id = h.service_id
            WHERE p.id = ?
            LIMIT 1
        ");
        $stmtPat->execute([$patient_id]);
        $pat = $stmtPat->fetch(PDO::FETCH_ASSOC);
        if (!$pat) {
            echo json_encode(['success' => false, 'message' => 'Aucune hospitalisation active trouvée.']);
            exit;
        }

        // Réévaluations (20 dernières)
        $stmtRev = $db->prepare("
            SELECT r.id, r.date_reevaluation, r.heure_reevaluation,
                   r.evolution_globale, r.note_evolution,
                   r.diagnostic_jour, r.code_cim10,
                   r.conduite_tenir, r.traitement_non_medicamenteux,
                   r.plaintes_jour,
                   u.nom AS medecin_nom, u.prenom AS medecin_prenom
            FROM reevaluations_hospitalisees r
            JOIN users u ON r.medecin_id = u.id
            WHERE r.patient_id = ?
            ORDER BY r.date_reevaluation DESC, r.heure_reevaluation DESC
            LIMIT 20
        ");
        $stmtRev->execute([$patient_id]);
        $reevaluations = $stmtRev->fetchAll(PDO::FETCH_ASSOC);

        foreach ($reevaluations as &$rev) {
            $rid = (int)$rev['id'];
            $stmtB = $db->prepare("SELECT type, intitule, urgence, note FROM reevaluation_bilans WHERE reevaluation_id = ? ORDER BY type, id");
            $stmtB->execute([$rid]);
            $rev['bilans'] = $stmtB->fetchAll(PDO::FETCH_ASSOC);

            $stmtM = $db->prepare("SELECT nom_medicament, posologie, voie_administration, frequence, duree FROM reevaluation_medicaments WHERE reevaluation_id = ? ORDER BY id");
            $stmtM->execute([$rid]);
            $rev['medicaments'] = $stmtM->fetchAll(PDO::FETCH_ASSOC);
        }
        unset($rev);

        echo json_encode([
            'success'       => true,
            'patient'       => $pat,
            'reevaluations' => $reevaluations,
        ]);
    } catch (\Throwable $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

}

