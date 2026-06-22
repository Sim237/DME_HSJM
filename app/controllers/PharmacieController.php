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
FICHIER : app/controllers/PharmacieController.php
CONTRÔLEUR DE GESTION DU CIRCUIT DU MÉDICAMENT
============================================================================ */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../models/Medicament.php';
require_once __DIR__ . '/../services/PharmacieService.php';
require_once __DIR__ . '/../services/AuditService.php';
require_once __DIR__ . '/UnifiedController.php';

class PharmacieController extends UnifiedController {
    private $db;
    private $medicamentModel;
    private $pharmacieService;
    private $audit;

    public function __construct() {
        parent::__construct();
        $database = new Database();
        $this->db = $database->getConnection();
        $this->medicamentModel = new Medicament();
        $this->pharmacieService = new PharmacieService();
        $this->audit = new AuditService();
    }

    /**
     * Dashboard Principal de la Pharmacie
     */
    public function index() {
        $this->auth->requirePermission('pharmacie', 'read');

        // 1. Stock Faible
        $sqlLow = "SELECT id, nom AS designation, forme, dosage, quantite AS quantite_stock, seuil_alerte
                   FROM medicaments
                   WHERE quantite <= seuil_alerte
                   ORDER BY quantite ASC";
        $low_stock = $this->db->query($sqlLow)->fetchAll(PDO::FETCH_ASSOC);

        // 2. Ordonnances — filtre date (défaut = aujourd'hui) + recherche
        $filterDate = $_GET['date'] ?? date('Y-m-d');
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $filterDate)) $filterDate = date('Y-m-d');
        $searchQ    = trim($_GET['q'] ?? '');
        $isToday    = ($filterDate === date('Y-m-d'));

        // Statuts affichés : si aujourd'hui → en attente seulement, sinon tout (historique)
        $statutFilter = $isToday
            ? "op.statut IN ('EN_ATTENTE','SIGNEE','EN_COURS')"
            : "op.statut IN ('EN_ATTENTE','SIGNEE','EN_COURS','TERMINEE','PARTIEL','COMPLET')";

        $sqlParams = [':filter_date' => $filterDate];
        $searchWhere = '';
        if ($searchQ !== '') {
            $searchWhere = "AND (p.nom LIKE :q1 OR p.prenom LIKE :q2
                               OR p.dossier_numero LIKE :q3
                               OR CONCAT(u.nom,' ',u.prenom) LIKE :q4)";
            $sq = '%' . $searchQ . '%';
            $sqlParams[':q1'] = $sq;
            $sqlParams[':q2'] = $sq;
            $sqlParams[':q3'] = $sq;
            $sqlParams[':q4'] = $sq;
        }

        $queryOrders = "SELECT op.id, op.statut, op.date_creation,
                               p.id as patient_id, p.nom as patient_nom, p.prenom as patient_prenom,
                               p.dossier_numero, p.numero_compte_sage,
                               u.id as medecin_id, u.nom as medecin_nom, u.prenom as medecin_prenom,
                               u.role as medecin_role, u.specialite as medecin_specialite,
                               COUNT(om.id) as nb_medicaments
                        FROM ordonnances_pharmacie op
                        JOIN patients p ON p.id = op.patient_id
                        JOIN users u ON u.id = op.medecin_id
                        LEFT JOIN ordonnance_medicaments om ON om.ordonnance_id = op.id
                        WHERE $statutFilter
                          AND DATE(op.date_creation) = :filter_date
                          $searchWhere
                        GROUP BY op.id
                        ORDER BY FIELD(op.statut,'SIGNEE','EN_COURS','EN_ATTENTE','PARTIEL','COMPLET','TERMINEE'),
                                 op.date_creation DESC";
        $stmtOrders = $this->db->prepare($queryOrders);
        $stmtOrders->execute($sqlParams);
        $pending_orders = $stmtOrders->fetchAll(PDO::FETCH_ASSOC);

        // 3. KPIs
        $total_refs      = $this->db->query("SELECT COUNT(*) FROM medicaments")->fetchColumn();
        $total_alerte    = $this->db->query("SELECT COUNT(*) FROM medicaments WHERE quantite <= seuil_alerte")->fetchColumn() ?: 0;
        $processed_today = $this->db->query(
            "SELECT COUNT(*) FROM ordonnances_pharmacie WHERE statut = 'TERMINEE' AND DATE(date_traitement) = CURDATE()"
        )->fetchColumn() ?: 0;

        // 4. Historique des ordonnances délivrées (TERMINEE) — filtre date optionnel
        $histDate  = $_GET['hist_date'] ?? null;
        $histLimit = 30;
        if ($histDate && preg_match('/^\d{4}-\d{2}-\d{2}$/', $histDate)) {
            $histWhere = "AND DATE(op.date_traitement) = :hist_date";
        } else {
            $histDate  = null;
            $histWhere = "";
        }
        $sqlHist = "SELECT op.id, op.date_creation, op.date_traitement, op.statut,
                           p.nom AS patient_nom, p.prenom AS patient_prenom,
                           p.dossier_numero, p.numero_compte_sage,
                           u.nom AS medecin_nom, u.prenom AS medecin_prenom,
                           u.role AS medecin_role, u.specialite AS medecin_specialite,
                           ph.nom AS pharmacien_nom, ph.prenom AS pharmacien_prenom,
                           COUNT(om.id) AS nb_medicaments
                    FROM ordonnances_pharmacie op
                    JOIN patients p  ON p.id  = op.patient_id
                    JOIN users u     ON u.id  = op.medecin_id
                    LEFT JOIN users ph ON ph.id = op.pharmacien_id
                    LEFT JOIN ordonnance_medicaments om ON om.ordonnance_id = op.id AND (om.ligne_annulee IS NULL OR om.ligne_annulee = 0)
                    WHERE op.statut = 'TERMINEE'
                    $histWhere
                    GROUP BY op.id
                    ORDER BY op.date_traitement DESC
                    LIMIT $histLimit";
        $stmtHist = $this->db->prepare($sqlHist);
        if ($histDate) {
            $stmtHist->bindValue(':hist_date', $histDate);
        }
        $stmtHist->execute();
        $historique = $stmtHist->fetchAll(PDO::FETCH_ASSOC);

        $hist_total = $this->db->query("SELECT COUNT(*) FROM ordonnances_pharmacie WHERE statut = 'TERMINEE'")->fetchColumn() ?: 0;

        // Passer les variables de filtre à la vue
        $dash_filterDate = $filterDate;
        $dash_searchQ    = $searchQ;
        $dash_isToday    = $isToday;

        require_once __DIR__ . '/../views/pharmacie/dashboard.php';
    }

    /**
     * Affiche l'inventaire complet des médicaments
     */
    public static function getFamillesMedicaments(): array {
        return [
            'Antibiotiques', 'Antiparasitaires', 'Antipaludéens', 'Antalgiques',
            'Anti-inflammatoires', 'Antihypertenseurs', 'Antidiabétiques',
            'Antiviraux', 'Antifongiques', 'Gastro-entérologie', 'Cardiologie',
            'Neurologie / Psychiatrie', 'Dermatologie', 'Ophtalmologie',
            'Gynécologie / Obstétrique', 'Pédiatrie', 'Vitamines / Suppléments',
            'Solutés / Perfusion', 'Autres',
        ];
    }

    public function stock() {
        $this->auth->requirePermission('pharmacie', 'read');
        $familles    = self::getFamillesMedicaments();
        $medicaments = $this->db->query("SELECT id, nom AS designation, forme, dosage, famille, quantite, seuil_alerte, prix_unitaire FROM medicaments ORDER BY famille ASC, nom ASC")->fetchAll(PDO::FETCH_ASSOC);
        require_once __DIR__ . '/../views/pharmacie/stock.php';
    }

    /**
     * Liste des ordonnances signées à traiter (Appelé par la route 'pharmacie/ordonnances')
     */
    public function ordonnances() {
        $this->auth->requirePermission('pharmacie', 'read');

        $query = "SELECT op.id, op.statut, op.date_creation, op.notes,
                         p.id as patient_id, p.nom as patient_nom, p.prenom as patient_prenom,
                         p.dossier_numero, p.date_naissance, p.allergies,
                         p.type_client, p.circuit,
                         u.id as medecin_id, u.nom as medecin_nom, u.prenom as medecin_prenom,
                         u.role as prescripteur_role,
                         s.nom_service as nom_service,
                         COUNT(om.id) as nb_medicaments
                  FROM ordonnances_pharmacie op
                  JOIN patients p ON p.id = op.patient_id
                  JOIN users u ON u.id = op.medecin_id
                  LEFT JOIN services s ON s.id = p.service_id
                  LEFT JOIN ordonnance_medicaments om ON om.ordonnance_id = op.id
                  WHERE op.statut IN ('EN_ATTENTE', 'SIGNEE', 'EN_COURS')
                  GROUP BY op.id
                  ORDER BY FIELD(op.statut,'SIGNEE','EN_COURS','EN_ATTENTE'), op.date_creation DESC";

        $pending_orders = $this->db->query($query)->fetchAll(PDO::FETCH_ASSOC);
        require_once __DIR__ . '/../views/pharmacie/ordonnances.php';
    }

    /**
     * Détail d'une ordonnance pour préparation (Appelé par la route 'pharmacie/traitement/ID')
     */
    public function traitement($id) {
        $this->auth->requirePermission('pharmacie', 'read');

        // 1. Entête ordonnance depuis ordonnances_pharmacie
        $stmt = $this->db->prepare(
            "SELECT op.id, op.statut, op.date_creation, op.date_traitement, op.notes, op.consultation_id,
                    p.id as patient_id, p.nom as patient_nom, p.prenom as patient_prenom,
                    p.allergies, p.dossier_numero, p.date_naissance,
                    u.id as medecin_id, u.nom as medecin_nom, u.prenom as medecin_prenom,
                    u.role as prescripteur_role,
                    ph.nom as pharmacien_nom, ph.prenom as pharmacien_prenom
             FROM ordonnances_pharmacie op
             JOIN patients p    ON p.id  = op.patient_id
             JOIN users u       ON u.id  = op.medecin_id
             LEFT JOIN users ph ON ph.id = op.pharmacien_id
             WHERE op.id = ?"
        );
        $stmt->execute([$id]);
        $ordonnance = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$ordonnance) {
            header('Location: ' . BASE_URL . 'pharmacie?error=introuvable');
            exit;
        }

        // 2. Lignes de médicaments depuis ordonnance_medicaments
        $colCheck = $this->db->query("SHOW COLUMNS FROM ordonnance_medicaments LIKE 'nom_medicament'")->rowCount();
        $nomCol = $colCheck > 0 ? "COALESCE(om.nom_medicament, m.nom, 'Médicament')" : "COALESCE(m.nom, 'Médicament')";

        $stmtLines = $this->db->prepare(
            "SELECT om.*, $nomCol as designation_stock,
                    m.quantite as stock_actuel, m.forme, m.dosage, m.unite,
                    m.prix_unitaire as prix_dme,
                    sm.ar_prix_ven as prix_sage, sm.ar_ref as sage_ref, sm.ar_unite as sage_unite
             FROM ordonnance_medicaments om
             LEFT JOIN medicaments m ON m.id = om.medicament_id
             LEFT JOIN sage_medicaments_map sm ON sm.medicament_id = m.id AND sm.statut_map != 'IGNORE'
             WHERE om.ordonnance_id = ?"
        );
        $stmtLines->execute([$id]);
        $lignes = $stmtLines->fetchAll(PDO::FETCH_ASSOC);

        // Calculer le total facturé SAGE pour cette ordonnance
        $totalSage = 0;
        foreach ($lignes as $l) {
            if (!empty($l['prix_sage']) && $l['prix_sage'] > 0) {
                $totalSage += (float)$l['prix_sage'] * (int)($l['quantite'] ?? 1);
            }
        }

        require_once __DIR__ . '/../views/pharmacie/traitement.php';
    }

    /**
     * Modification d'une ligne d'ordonnance par le pharmacien.
     *
     * Comportement (chaînable) :
     *   - L'ancienne ligne est barrée (ligne_annulee = 1)
     *   - Une nouvelle ligne est créée avec remplace_ligne_id pointant vers l'ancienne
     *   - La nouvelle ligne reste modifiable à son tour, autant de fois que nécessaire
     *     (chaîne de versions : v1 → v2 → v3 → …)
     *
     * Le seul blocage est si la ligne courante est déjà annulée (donc remplacée
     * par une version plus récente — il faut alors modifier la dernière version).
     */
    public function modifierLigne(int $ligneId): void {
        header('Content-Type: application/json');

        try {
            $this->auth->requirePermission('pharmacie', 'write');

            $nom       = trim($_POST['nom_medicament'] ?? '');
            $posologie = trim($_POST['posologie']      ?? '');
            $duree     = trim($_POST['duree']          ?? '');
            $note      = trim($_POST['note_pharmacien']?? '');
            $ordId     = (int)($_POST['ordonnance_id'] ?? 0);

            if (!$nom || !$ordId) {
                echo json_encode(['success' => false, 'message' => 'Données manquantes.']); exit;
            }

            // 1. Vérifier que la ligne appartient bien à l'ordonnance et n'est pas déjà annulée
            // Utilise COALESCE pour être compatible même si la colonne ligne_annulee n'existe pas encore
            $stmt = $this->db->prepare(
                "SELECT * FROM ordonnance_medicaments WHERE id = ? AND ordonnance_id = ?"
            );
            $stmt->execute([$ligneId, $ordId]);
            $ligne = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$ligne) {
                echo json_encode(['success' => false, 'message' => 'Ligne introuvable.']); exit;
            }
            // Une ligne barrée a été remplacée par une version plus récente.
            // → on doit modifier la version la plus récente, pas une ancienne.
            if (isset($ligne['ligne_annulee']) && $ligne['ligne_annulee']) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Cette ligne a déjà été remplacée. Modifiez plutôt la version la plus récente (ligne en vert).'
                ]); exit;
            }

            $this->db->beginTransaction();

            // 2. Marquer l'ancienne ligne comme annulée (si la colonne existe)
            try {
                $this->db->prepare("UPDATE ordonnance_medicaments SET ligne_annulee = 1 WHERE id = ?")
                         ->execute([$ligneId]);
            } catch (\Exception $colErr) {
                // Colonne ligne_annulee absente → migration non exécutée
                $this->db->rollBack();
                echo json_encode([
                    'success' => false,
                    'message' => 'Migration SQL requise : exécutez database/migration_pharmacien_edit.sql dans phpMyAdmin.'
                ]);
                exit;
            }

            // 3. Créer la nouvelle ligne (remplaçante)
            // Les colonnes remplace_ligne_id et note_pharmacien peuvent être absentes
            // → on les inclut uniquement si elles existent
            $colonnes = $this->db->query("SHOW COLUMNS FROM ordonnance_medicaments")->fetchAll(PDO::FETCH_COLUMN);
            $hasReplace = in_array('remplace_ligne_id', $colonnes);
            $hasNote    = in_array('note_pharmacien', $colonnes);

            $fields = "(ordonnance_id, medicament_id, nom_medicament, quantite, posologie, duree, disponible";
            $placeholders = "(?, ?, ?, ?, ?, ?, ?";
            $values = [
                $ordId,
                $ligne['medicament_id'],
                $nom,
                $ligne['quantite'] ?? 1,
                $posologie ?: $ligne['posologie'],
                $duree     ?: $ligne['duree'],
                $ligne['disponible'],
            ];
            if ($hasReplace) { $fields .= ', remplace_ligne_id'; $placeholders .= ', ?'; $values[] = $ligneId; }
            if ($hasNote)    { $fields .= ', note_pharmacien';   $placeholders .= ', ?'; $values[] = $note ?: null; }
            $fields       .= ')';
            $placeholders .= ')';

            $this->db->prepare("INSERT INTO ordonnance_medicaments $fields VALUES $placeholders")
                     ->execute($values);

            $this->db->commit();
            echo json_encode(['success' => true, 'message' => 'Ligne modifiée avec succès.']);

        } catch (\Exception $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            echo json_encode(['success' => false, 'message' => 'Erreur : ' . $e->getMessage()]);
        }
        exit;
    }

    /**
     * Action : Valider la délivrance partielle ou complète et décrémenter le stock
     * (Appelé par la route 'pharmacie/delivrer')
     *
     * POST attendus :
     *   - id                       : ID de l'ordonnance
     *   - lignes_delivrees[]       : IDs des lignes ordonnance_medicaments cochées (= délivrées)
     *   - lignes_non_delivrees[]   : IDs des lignes décochées (= non délivrées)
     *
     * Comportement :
     *   - Lignes délivrées → décrémente le stock + delivre = 1
     *   - Lignes non délivrées → delivre = 0 (stock non touché)
     *   - Compatibilité ascendante : si aucun tableau envoyé, toutes les lignes sont délivrées
     */

    /* ─────────────────────────────────────────────────────────────────
     * POST pharmacie/declarer-rupture
     * Toggle hors_stock sur une ligne d'ordonnance.
     * Si on passe en rupture : delivre = 0 (impossible de délivrer).
     * ───────────────────────────────────────────────────────────────── */
    public function declarerRupture(): void {
        header('Content-Type: application/json');
        $this->auth->requirePermission('pharmacie', 'write');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Méthode non autorisée.']); exit;
        }

        $ligneId   = (int)($_POST['ligne_id']   ?? 0);
        $horsStock = (int)($_POST['hors_stock']  ?? 0); // 0 ou 1

        if (!$ligneId) {
            echo json_encode(['success' => false, 'message' => 'ID de ligne invalide.']); exit;
        }

        try {
            // Vérifier que la ligne existe et n'est pas annulée
            $stmt = $this->db->prepare(
                "SELECT om.id, om.ordonnance_id, om.ligne_annulee, om.hors_stock,
                        op.statut AS ord_statut
                 FROM ordonnance_medicaments om
                 JOIN ordonnances_pharmacie op ON op.id = om.ordonnance_id
                 WHERE om.id = ?"
            );
            $stmt->execute([$ligneId]);
            $ligne = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$ligne) {
                echo json_encode(['success' => false, 'message' => 'Ligne introuvable.']); exit;
            }
            if (!empty($ligne['ligne_annulee'])) {
                echo json_encode(['success' => false, 'message' => 'Cette ligne est annulée.']); exit;
            }
            if ($ligne['ord_statut'] === 'TERMINEE') {
                echo json_encode(['success' => false, 'message' => 'Ordonnance déjà terminée.']); exit;
            }

            // Mettre à jour hors_stock + delivre si rupture
            if ($horsStock) {
                $this->db->prepare(
                    "UPDATE ordonnance_medicaments SET hors_stock = 1, delivre = 0 WHERE id = ?"
                )->execute([$ligneId]);
            } else {
                $this->db->prepare(
                    "UPDATE ordonnance_medicaments SET hors_stock = 0 WHERE id = ?"
                )->execute([$ligneId]);
            }

            echo json_encode(['success' => true, 'hors_stock' => $horsStock]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    public function delivrer() {
        header('Content-Type: application/json');
        $this->auth->requirePermission('pharmacie', 'write');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;

        $ordonnance_id = $_POST['id'] ?? null;
        // IDs cochés / décochés envoyés par le frontend (string → int + filtrage)
        $idsDelivrees    = array_map('intval', (array)($_POST['lignes_delivrees']     ?? []));
        $idsNonDelivrees = array_map('intval', (array)($_POST['lignes_non_delivrees'] ?? []));
        $idsDelivrees    = array_filter($idsDelivrees, fn($x) => $x > 0);
        $idsNonDelivrees = array_filter($idsNonDelivrees, fn($x) => $x > 0);

        try {
            $this->db->beginTransaction();

            // 0. Vérifier que l'ordonnance existe et son statut
            $stmtOrd = $this->db->prepare(
                "SELECT id, statut FROM ordonnances_pharmacie WHERE id = ?"
            );
            $stmtOrd->execute([$ordonnance_id]);
            $ord = $stmtOrd->fetch(PDO::FETCH_ASSOC);
            if (!$ord) throw new Exception("Ordonnance introuvable.");
            if ($ord['statut'] === 'TERMINEE') throw new Exception("Cette ordonnance a déjà été délivrée.");

            // Log si délivrance sans signature
            if ($ord['statut'] !== 'SIGNEE') {
                error_log("[Pharmacie] Délivrance sans signature : ordonnance_id=$ordonnance_id, pharmacien=" . ($_SESSION['user_id'] ?? 'inconnu'));
            }

            // Vérifier que la colonne `delivre` existe (compat. avant migration)
            $hasDelivreCol = (int)$this->db->query(
                "SELECT COUNT(*) FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE()
                   AND TABLE_NAME = 'ordonnance_medicaments'
                   AND COLUMN_NAME = 'delivre'"
            )->fetchColumn() > 0;

            // 1. Charger les lignes (verrouillées FOR UPDATE — anti race condition stock)
            $stmtLines = $this->db->prepare(
                "SELECT om.id, om.medicament_id, om.quantite, om.nom_medicament,
                        m.quantite AS stock_actuel, m.nom AS nom_stock
                 FROM ordonnance_medicaments om
                 LEFT JOIN medicaments m ON m.id = om.medicament_id
                 WHERE om.ordonnance_id = ?
                   AND COALESCE(om.ligne_annulee, 0) = 0
                 FOR UPDATE"
            );
            $stmtLines->execute([$ordonnance_id]);
            $lignes = $stmtLines->fetchAll(PDO::FETCH_ASSOC);

            $modeChecklist = !empty($idsDelivrees) || !empty($idsNonDelivrees);

            // PASSE 1 : vérifier tous les stocks AVANT de toucher quoi que ce soit
            $ruptures = [];
            foreach ($lignes as $ligne) {
                $ligneId     = (int)$ligne['id'];
                $estDelivree = $modeChecklist ? in_array($ligneId, $idsDelivrees, true) : true;
                if ($estDelivree && !empty($ligne['medicament_id'])) {
                    $stockActuel = (int)$ligne['stock_actuel'];
                    $qte         = (int)$ligne['quantite'];
                    if ($stockActuel < $qte) {
                        $ruptures[] = ($ligne['nom_medicament'] ?: $ligne['nom_stock'] ?: "Méd. #" . $ligne['medicament_id'])
                                    . " (stock: $stockActuel, demandé: $qte)";
                    }
                }
            }
            // Blocage global si rupture : AUCUNE décrémentation n'a eu lieu
            if (!empty($ruptures)) {
                $this->db->rollBack();
                echo json_encode([
                    'success' => false,
                    'message' => 'Stock insuffisant pour : ' . implode(' | ', $ruptures)
                              . '. Décochez ces médicaments avant de relancer.',
                ]);
                exit;
            }

            // PASSE 2 : tout est OK → décrémenter et marquer
            $compteurs = ['delivre' => 0, 'non_delivre' => 0];
            foreach ($lignes as $ligne) {
                $ligneId     = (int)$ligne['id'];
                $estDelivree = $modeChecklist ? in_array($ligneId, $idsDelivrees, true) : true;

                if ($estDelivree) {
                    if (!empty($ligne['medicament_id'])) {
                        $this->db->prepare(
                            "UPDATE medicaments SET quantite = quantite - ? WHERE id = ?"
                        )->execute([$ligne['quantite'], $ligne['medicament_id']]);
                    }
                    if ($hasDelivreCol) {
                        $this->db->prepare(
                            "UPDATE ordonnance_medicaments SET delivre = 1 WHERE id = ?"
                        )->execute([$ligneId]);
                    }
                    $compteurs['delivre']++;
                } else {
                    if ($hasDelivreCol) {
                        $this->db->prepare(
                            "UPDATE ordonnance_medicaments SET delivre = 0 WHERE id = ?"
                        )->execute([$ligneId]);
                    }
                    $compteurs['non_delivre']++;
                }
            }

            // 3. Clôturer l'ordonnance
            $this->db->prepare(
                "UPDATE ordonnances_pharmacie
                 SET statut = 'TERMINEE', date_traitement = NOW(), pharmacien_id = ?
                 WHERE id = ?"
            )->execute([$_SESSION['user_id'], $ordonnance_id]);

            $this->db->commit();

            $msg = $compteurs['non_delivre'] > 0
                ? "Délivrance effectuée — {$compteurs['delivre']} médicament(s) délivré(s), {$compteurs['non_delivre']} non délivré(s)."
                : "Délivrance effectuée — {$compteurs['delivre']} médicament(s) délivré(s).";

            // ── Audit : délivrance de médicaments ──
            try {
                $pRow = $this->db->prepare(
                    "SELECT p.id, p.nom, p.prenom FROM ordonnances_pharmacie op
                     JOIN patients p ON p.id = op.patient_id WHERE op.id = ?"
                );
                $pRow->execute([$ordonnance_id]);
                $pat  = $pRow->fetch(PDO::FETCH_ASSOC);
                $pNom = $pat ? trim(($pat['nom'] ?? '') . ' ' . ($pat['prenom'] ?? '')) : '';
                $desc = "Médicaments délivrés — {$compteurs['delivre']} délivré(s)"
                      . ($compteurs['non_delivre'] > 0 ? ", {$compteurs['non_delivre']} non délivré(s)" : '')
                      . ($pNom ? " · patient : $pNom" : '');
                $this->audit->log('UPDATE', 'ordonnances_pharmacie', (int)$ordonnance_id, $desc, null, [
                    'patient'      => $pNom,
                    'delivres'     => $compteurs['delivre'],
                    'non_delivres' => $compteurs['non_delivre'],
                    'pharmacien'   => ($_SESSION['user_prenom'] ?? '') . ' ' . ($_SESSION['user_nom'] ?? ''),
                ]);
            } catch (Exception $e) {
                error_log('[PharmacieController::delivrer] Audit ignoré : ' . $e->getMessage());
            }

            echo json_encode([
                'success'        => true,
                'message'        => $msg,
                'delivres'       => $compteurs['delivre'],
                'non_delivres'   => $compteurs['non_delivre'],
            ]);
        } catch (Exception $e) {
            if ($this->db->inTransaction()) $this->db->rollback();
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    /**
     * Action : Réapprovisionnement (Appelé par la route 'pharmacie/approvisionnement')
     */
    public function approvisionnement() {
        $this->auth->requirePermission('pharmacie', 'write');
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $stmt = $this->db->prepare("UPDATE medicaments SET quantite = quantite + ? WHERE id = ?");
            $stmt->execute([$_POST['quantite_ajoutee'], $_POST['medicament_id']]);
            header('Location: ' . BASE_URL . 'pharmacie?success=appro');
            exit;
        }
    }

    public function updateMedicament() {
        $this->auth->requirePermission('pharmacie', 'write');
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $sql = "UPDATE medicaments SET nom = ?, prix_unitaire = ?, seuil_alerte = ? WHERE id = ?";
            $this->db->prepare($sql)->execute([$_POST['designation'], $_POST['prix_unitaire'], $_POST['seuil_alerte'], $_POST['id']]);
            header('Location: ' . BASE_URL . 'pharmacie/stock?success=updated');
            exit;
        }
    }

    public function searchMedicaments() {
        header('Content-Type: application/json');
        $term    = '%' . ($_GET['term'] ?? '') . '%';
        $famille = trim($_GET['famille'] ?? '');

        if ($famille) {
            $stmt = $this->db->prepare(
                "SELECT id, nom, forme, dosage, famille, quantite as stock_actuel
                 FROM medicaments WHERE nom LIKE ? AND famille = ? LIMIT 15"
            );
            $stmt->execute([$term, $famille]);
        } else {
            $stmt = $this->db->prepare(
                "SELECT id, nom, forme, dosage, famille, quantite as stock_actuel
                 FROM medicaments WHERE nom LIKE ? LIMIT 15"
            );
            $stmt->execute([$term]);
        }
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    /**
     * Import stock depuis un fichier CSV/Excel (via export CSV depuis Excel)
     * Format attendu colonnes : code, nom, forme, dosage, quantite, unite, seuil_alerte, prix_unitaire
     * Logique : UPSERT — crée si absent (par code), met à jour la quantité si présent
     */
    public function importStock() {
        header('Content-Type: application/json');

        if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], ['ADMIN', 'PHARMACIEN'])) {
            echo json_encode(['success' => false, 'message' => 'Accès non autorisé']); exit;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_FILES['fichier'])) {
            echo json_encode(['success' => false, 'message' => 'Aucun fichier reçu']); exit;
        }

        $file = $_FILES['fichier'];
        if ($file['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(['success' => false, 'message' => 'Erreur upload']); exit;
        }

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['csv', 'txt'])) {
            echo json_encode(['success' => false, 'message' => 'Format accepté : CSV uniquement (exporté depuis Excel)']); exit;
        }

        $mode    = $_POST['mode'] ?? 'upsert'; // 'upsert' | 'replace_qty' | 'add_qty'
        $created = 0; $updated = 0; $errors = [];

        try {
            $handle = fopen($file['tmp_name'], 'r');
            // Détecter séparateur (virgule ou point-virgule)
            $firstLine = fgets($handle);
            rewind($handle);
            $sep = (substr_count($firstLine, ';') > substr_count($firstLine, ',')) ? ';' : ',';

            // Lire l'en-tête
            $header = fgetcsv($handle, 0, $sep);
            $header = array_map(fn($h) => strtolower(trim(str_replace(["\xEF\xBB\xBF", '"'], '', $h))), $header);

            $colMap = [];
            $expected = ['code', 'nom', 'forme', 'dosage', 'quantite', 'unite', 'seuil_alerte', 'prix_unitaire'];
            foreach ($expected as $col) {
                $idx = array_search($col, $header);
                $colMap[$col] = ($idx !== false) ? $idx : null;
            }

            if ($colMap['nom'] === null) {
                echo json_encode(['success' => false, 'message' => "Colonne 'nom' introuvable dans l'en-tête. Colonnes trouvées : " . implode(', ', $header)]);
                fclose($handle); exit;
            }

            $stmtFind   = $this->db->prepare("SELECT id, quantite FROM medicaments WHERE code = ? OR nom = ? LIMIT 1");
            $stmtInsert = $this->db->prepare("INSERT INTO medicaments (code, nom, forme, dosage, quantite, unite, seuil_alerte, prix_unitaire) VALUES (?,?,?,?,?,?,?,?)");
            $stmtUpdate = $this->db->prepare("UPDATE medicaments SET quantite = ?, forme = COALESCE(?,forme), dosage = COALESCE(?,dosage), unite = COALESCE(?,unite), seuil_alerte = COALESCE(?,seuil_alerte), prix_unitaire = COALESCE(?,prix_unitaire), updated_at = NOW() WHERE id = ?");
            $stmtAddQty = $this->db->prepare("UPDATE medicaments SET quantite = quantite + ?, updated_at = NOW() WHERE id = ?");

            $row = 1;
            while (($data = fgetcsv($handle, 0, $sep)) !== false) {
                $row++;
                $get = fn($col) => isset($colMap[$col], $data[$colMap[$col]]) ? trim($data[$colMap[$col]]) : null;

                $nom = $get('nom');
                if (empty($nom)) continue;

                $code  = $get('code')  ?: null;
                $qte   = is_numeric($get('quantite'))  ? (int)$get('quantite')  : 0;
                $seuil = is_numeric($get('seuil_alerte')) ? (int)$get('seuil_alerte') : 10;
                $prix  = is_numeric($get('prix_unitaire'))? (float)$get('prix_unitaire') : 0;

                $stmtFind->execute([$code, $nom]);
                $existing = $stmtFind->fetch(PDO::FETCH_ASSOC);

                if ($existing) {
                    if ($mode === 'add_qty') {
                        $stmtAddQty->execute([$qte, $existing['id']]);
                    } else {
                        $stmtUpdate->execute([$qte, $get('forme'), $get('dosage'), $get('unite'), $seuil, $prix, $existing['id']]);
                    }
                    $updated++;
                } else {
                    try {
                        $stmtInsert->execute([$code, $nom, $get('forme'), $get('dosage'), $qte, $get('unite'), $seuil, $prix]);
                        $created++;
                    } catch (\PDOException $e) {
                        $errors[] = "Ligne $row : " . $e->getMessage();
                    }
                }
            }
            fclose($handle);

            echo json_encode([
                'success' => true,
                'created' => $created,
                'updated' => $updated,
                'errors'  => $errors,
                'message' => "$created créé(s), $updated mis à jour.",
            ]);
        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    /**
     * Télécharger le template CSV vide pour import
     */
    public function downloadTemplate() {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="template_medicaments.csv"');
        echo "\xEF\xBB\xBF"; // BOM UTF-8 pour Excel
        $out = fopen('php://output', 'w');
        fputcsv($out, ['code','nom','forme','dosage','quantite','unite','seuil_alerte','prix_unitaire'], ';');
        fputcsv($out, ['MED001','Paracétamol 500mg','Comprimé','500mg','100','cp','20','50'], ';');
        fputcsv($out, ['MED002','Amoxicilline 1g','Gélule','1g','60','gél','15','120'], ';');
        fclose($out);
        exit;
    }
}