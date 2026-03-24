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

        // 2. Ordonnances en attente (Utilisé pour les compteurs)
        $queryOrders = "SELECT o.*, p.nom as patient_nom, p.prenom as patient_prenom,
                           p.dossier_numero, -- <--- CETTE LIGNE ÉTAIT MANQUANTE
                           u.nom as medecin_nom
                    FROM ordonnances_pharmacie o
                    JOIN patients p ON o.patient_id = p.id
                    JOIN users u ON o.medecin_id = u.id
                    WHERE o.statut IN ('SIGNEE', 'EN_ATTENTE')
                    ORDER BY o.date_creation DESC";
        $pending_orders = $this->db->query($queryOrders)->fetchAll(PDO::FETCH_ASSOC);

        $total_refs = $this->db->query("SELECT COUNT(*) FROM medicaments")->fetchColumn();
        $conso_totale = $this->db->query("SELECT COUNT(*) FROM ordonnances_pharmacie WHERE statut = 'TRAITEE' AND DATE(date_traitement) = CURDATE()")->fetchColumn() ?: 0;

        require_once __DIR__ . '/../views/pharmacie/dashboard.php';
    }

    /**
     * Affiche l'inventaire complet des médicaments
     */
    public function stock() {
        $this->auth->requirePermission('pharmacie', 'read');
        $medicaments = $this->db->query("SELECT id, nom AS designation, forme, dosage, quantite, seuil_alerte, prix_unitaire FROM medicaments ORDER BY nom ASC")->fetchAll(PDO::FETCH_ASSOC);
        require_once __DIR__ . '/../views/pharmacie/stock.php';
    }

    /**
     * Liste des ordonnances signées à traiter (Appelé par la route 'pharmacie/ordonnances')
     */
    public function ordonnances() {
        $this->auth->requirePermission('pharmacie', 'read');

        $query = "SELECT o.*, p.nom as patient_nom, p.prenom as patient_prenom, p.dossier_numero, u.nom as medecin_nom
                  FROM ordonnances_pharmacie o
                  JOIN patients p ON o.patient_id = p.id
                  JOIN users u ON o.medecin_id = u.id
                  WHERE o.statut = 'SIGNEE'
                  ORDER BY o.date_creation DESC";

        $pending_orders = $this->db->query($query)->fetchAll(PDO::FETCH_ASSOC);
        require_once __DIR__ . '/../views/pharmacie/ordonnances.php';
    }

    /**
     * Détail d'une ordonnance pour préparation (Appelé par la route 'pharmacie/traitement/ID')
     */
    public function traitement($id) {
    $this->auth->requirePermission('pharmacie', 'read');
    $db = (new Database())->getConnection();

    // 1. Récupérer l'entête de l'ordonnance et les allergies du patient
    $stmt = $db->prepare("SELECT o.*, p.nom as patient_nom, p.prenom as patient_prenom,
                                 p.allergies, p.dossier_numero, p.date_naissance
                          FROM ordonnances_pharmacie o
                          JOIN patients p ON o.patient_id = p.id
                          WHERE o.id = ?");
    $stmt->execute([$id]);
    $ordonnance = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$ordonnance) {
        header('Location: ' . BASE_URL . 'pharmacie?error=introuvable');
        exit;
    }

    // 2. Récupérer les VRAIES lignes de médicaments saisies en consultation
    $stmtLines = $db->prepare("SELECT ol.*, m.nom as designation_stock, m.quantite as stock_actuel
                               FROM ordonnance_medicaments ol
                               LEFT JOIN medicaments m ON ol.medicament_id = m.id
                               WHERE ol.ordonnance_id = ?");
    $stmtLines->execute([$id]);
    $lignes = $stmtLines->fetchAll(PDO::FETCH_ASSOC);

    // 3. Envoyer tout ça à la vue
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

                // 1. Récupérer les médicaments pour baisser le stock
                $stmtLines = $this->db->prepare("SELECT medicament_id, quantite FROM ordonnance_medicaments WHERE ordonnance_id = ?");
                $stmtLines->execute([$ordonnance_id]);
                $lignes = $stmtLines->fetchAll(PDO::FETCH_ASSOC);

                foreach ($lignes as $ligne) {
                    $stmtUpdate = $this->db->prepare("UPDATE medicaments SET quantite = quantite - ? WHERE id = ? AND quantite >= ?");
                    $stmtUpdate->execute([$ligne['quantite'], $ligne['medicament_id'], $ligne['quantite']]);
                    if ($stmtUpdate->rowCount() === 0) throw new Exception("Stock insuffisant pour un produit.");
                }

                // 2. Clôturer l'ordonnance
                $stmtFinal = $this->db->prepare("UPDATE ordonnances_pharmacie SET statut = 'TRAITEE', date_traitement = NOW(), pharmacien_id = ? WHERE id = ?");
                $stmtFinal->execute([$_SESSION['user_id'], $ordonnance_id]);

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
}