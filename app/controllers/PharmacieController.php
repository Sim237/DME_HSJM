<?php
/* ============================================================================
FICHIER : app/controllers/PharmacieController.php
CONTRÔLEUR DE GESTION DU CIRCUIT DU MÉDICAMENT
============================================================================ */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../models/Medicament.php';
require_once __DIR__ . '/../services/PharmacieService.php';
require_once __DIR__ . '/UnifiedController.php';

class PharmacieController extends UnifiedController {
    private $db;
    private $medicamentModel;
    private $pharmacieService;

    public function __construct() {
        parent::__construct();
        $database = new Database();
        $this->db = $database->getConnection();
        $this->medicamentModel = new Medicament();
        $this->pharmacieService = new PharmacieService();
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

        // 2. Ordonnances en attente — depuis ordonnances_pharmacie (créées par PharmacieService)
        $queryOrders = "SELECT op.id, op.statut, op.date_creation,
                               p.id as patient_id, p.nom as patient_nom, p.prenom as patient_prenom,
                               p.dossier_numero,
                               u.id as medecin_id, u.nom as medecin_nom, u.prenom as medecin_prenom,
                               COUNT(om.id) as nb_medicaments
                        FROM ordonnances_pharmacie op
                        JOIN consultations c ON c.id = op.consultation_id
                        JOIN patients p ON p.id = c.patient_id
                        JOIN users u ON u.id = c.medecin_id
                        LEFT JOIN ordonnance_medicaments om ON om.ordonnance_id = op.id
                        WHERE op.statut IN ('EN_ATTENTE', 'EN_COURS')
                        GROUP BY op.id
                        ORDER BY op.date_creation DESC";
        $pending_orders = $this->db->query($queryOrders)->fetchAll(PDO::FETCH_ASSOC);

        $total_refs   = $this->db->query("SELECT COUNT(*) FROM medicaments")->fetchColumn();
        $conso_totale = $this->db->query(
            "SELECT COUNT(*) FROM ordonnances_pharmacie WHERE statut = 'TERMINEE' AND DATE(date_traitement) = CURDATE()"
        )->fetchColumn() ?: 0;

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
                         u.id as medecin_id, u.nom as medecin_nom, u.prenom as medecin_prenom,
                         s.nom as nom_service,
                         COUNT(om.id) as nb_medicaments
                  FROM ordonnances_pharmacie op
                  JOIN consultations c ON c.id = op.consultation_id
                  JOIN patients p ON p.id = c.patient_id
                  JOIN users u ON u.id = c.medecin_id
                  LEFT JOIN services s ON s.id = p.service_id
                  LEFT JOIN ordonnance_medicaments om ON om.ordonnance_id = op.id
                  WHERE op.statut IN ('EN_ATTENTE', 'EN_COURS')
                  GROUP BY op.id
                  ORDER BY op.date_creation DESC";

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
            "SELECT op.id, op.statut, op.date_creation, op.notes, op.consultation_id,
                    p.id as patient_id, p.nom as patient_nom, p.prenom as patient_prenom,
                    p.allergies, p.dossier_numero, p.date_naissance,
                    u.id as medecin_id, u.nom as medecin_nom, u.prenom as medecin_prenom
             FROM ordonnances_pharmacie op
             JOIN consultations c ON c.id = op.consultation_id
             JOIN patients p ON p.id = c.patient_id
             JOIN users u ON u.id = c.medecin_id
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
                    m.quantite as stock_actuel, m.forme, m.dosage, m.unite
             FROM ordonnance_medicaments om
             LEFT JOIN medicaments m ON m.id = om.medicament_id
             WHERE om.ordonnance_id = ?"
        );
        $stmtLines->execute([$id]);
        $lignes = $stmtLines->fetchAll(PDO::FETCH_ASSOC);

        require_once __DIR__ . '/../views/pharmacie/traitement.php';
    }

    /**
     * Action : Valider la délivrance et décrémenter le stock (Appelé par la route 'pharmacie/delivrer')
     */
    public function delivrer() {
        header('Content-Type: application/json');
        $this->auth->requirePermission('pharmacie', 'write');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $ordonnance_id = $_POST['id'] ?? null;
            try {
                $this->db->beginTransaction();

                // 1. Lignes de médicaments depuis ordonnance_medicaments
                $stmtLines = $this->db->prepare(
                    "SELECT medicament_id, quantite FROM ordonnance_medicaments WHERE ordonnance_id = ? AND medicament_id IS NOT NULL"
                );
                $stmtLines->execute([$ordonnance_id]);
                $lignes = $stmtLines->fetchAll(PDO::FETCH_ASSOC);

                foreach ($lignes as $ligne) {
                    $stmtUpdate = $this->db->prepare(
                        "UPDATE medicaments SET quantite = quantite - ? WHERE id = ? AND quantite >= ?"
                    );
                    $stmtUpdate->execute([$ligne['quantite'], $ligne['medicament_id'], $ligne['quantite']]);
                    if ($stmtUpdate->rowCount() === 0) throw new Exception("Stock insuffisant pour un médicament.");
                }

                // 2. Clôturer l'ordonnance dans ordonnances_pharmacie
                $this->db->prepare(
                    "UPDATE ordonnances_pharmacie SET statut = 'TERMINEE', date_traitement = NOW(), pharmacien_id = ? WHERE id = ?"
                )->execute([$_SESSION['user_id'], $ordonnance_id]);

                $this->db->commit();
                echo json_encode(['success' => true, 'message' => 'Délivrance effectuée']);
            } catch (Exception $e) {
                $this->db->rollback();
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
            exit;
        }
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
        $query = "SELECT id, nom, forme, dosage, quantite as stock_actuel FROM medicaments WHERE nom LIKE ? LIMIT 10";
        $stmt = $this->db->prepare($query);
        $stmt->execute(["%{$_GET['term']}%"]);
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