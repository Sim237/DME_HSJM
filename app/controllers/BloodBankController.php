<?php
require_once __DIR__ . '/UnifiedController.php';
require_once __DIR__ . '/../services/AuditService.php';

class BloodBankController extends UnifiedController {

    public function index() {
        $this->auth->requirePermission('laboratoire', 'read');
        $db = (new Database())->getConnection();

        // 1. Stock actuel
        $stock = $db->query("SELECT * FROM blood_stock ORDER BY groupe_sanguin, rhesus")->fetchAll(PDO::FETCH_ASSOC);

        // 2. Statistiques par source (Formatées pour le graphique)
        $raw_stats = $db->query("SELECT source_destination, SUM(quantite) as total FROM blood_movements WHERE type_mouvement = 'ENTREE' GROUP BY source_destination")->fetchAll(PDO::FETCH_ASSOC);

        $stats_source = ['DONNEUR_VOLONTAIRE' => 0, 'FAMILLE' => 0, 'PERSONNEL' => 0];
        foreach($raw_stats as $row) {
            $stats_source[$row['source_destination']] = (int)$row['total'];
        }

        // 3. Consommation totale (Dernier mois)
        $conso_totale = $db->query("SELECT SUM(quantite) FROM blood_movements WHERE type_mouvement = 'SORTIE'")->fetchColumn() ?: 0;

        // 4. Registre des donneurs
        $donneurs = $db->query("SELECT * FROM blood_donors ORDER BY date_inscription DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);

        // 5. Demandes de transfusion en attente
        $demandes_transfusion = $db->query("
            SELECT tr.*, p.nom, p.prenom, p.dossier_numero
            FROM transfusion_requests tr
            JOIN patients p ON tr.patient_id = p.id
            WHERE tr.statut = 'EN_ATTENTE'
            ORDER BY tr.date_demande DESC
        ")->fetchAll(PDO::FETCH_ASSOC);

        require_once __DIR__ . '/../views/banque_sang/dashboard.php';
    }

    public function checkStock() {
        header('Content-Type: application/json');
        $db = (new Database())->getConnection();
        $groupe = $_POST['groupe'] ?? '';
        $rhesus = $_POST['rhesus'] ?? '';
        $quantite = intval($_POST['quantite'] ?? 0);

        $stmt = $db->prepare("SELECT quantite_poches FROM blood_stock WHERE groupe_sanguin = ? AND rhesus = ?");
        $stmt->execute([$groupe, $rhesus]);
        $res = $stmt->fetch();
        $dispo = $res['quantite_poches'] ?? 0;

        if ($dispo >= $quantite) {
            echo json_encode(['status' => 'available', 'dispo' => $dispo]);
        } else {
            $stmtD = $db->prepare("SELECT code_donneur, telephone, ville FROM blood_donors WHERE groupe_sanguin = ? AND rhesus = ? AND statut = 'APTE' LIMIT 3");
            $stmtD->execute([$groupe, $rhesus]);
            echo json_encode(['status' => 'insufficient', 'dispo' => $dispo, 'potential_donors' => $stmtD->fetchAll(PDO::FETCH_ASSOC)]);
        }
    }

    public function saveRequest() {
        header('Content-Type: application/json');
        $db = (new Database())->getConnection();
        $sql = "INSERT INTO transfusion_requests (patient_id, medecin_id, groupe_requis, rhesus_requis, quantite_demandee, notes_famille, statut) VALUES (?, ?, ?, ?, ?, ?, 'EN_ATTENTE')";
        $success = $db->prepare($sql)->execute([$_POST['patient_id'], $_SESSION['user_id'], $_POST['groupe'], $_POST['rhesus'], $_POST['quantite'], $_POST['fd_nom'] ?? null]);
        echo json_encode(['success' => $success]);
    }

    public function saveDon() {
        $db = (new Database())->getConnection();
        $db->prepare("UPDATE blood_stock SET quantite_poches = quantite_poches + ? WHERE groupe_sanguin = ? AND rhesus = ?")->execute([$_POST['quantite'], $_POST['groupe'], $_POST['rhesus']]);
        $db->prepare("INSERT INTO blood_movements (groupe, rhesus, type_mouvement, quantite, source_destination) VALUES (?, ?, 'ENTREE', ?, ?)")->execute([$_POST['groupe'], $_POST['rhesus'], $_POST['quantite'], $_POST['source']]);

        if(!isset($_POST['is_anonyme'])) {
            $db->prepare("INSERT INTO blood_donors (code_donneur, nom, prenom, telephone, ville, groupe_sanguin, rhesus, date_inscription) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())")
               ->execute(['DON-'.time(), $_POST['nom'], $_POST['prenom'], $_POST['telephone'], $_POST['ville'], $_POST['groupe'], $_POST['rhesus']]);
        }
        header('Location: ' . BASE_URL . 'banque-sang?success=1');
    }

    public function sortieStock() {
        $db = (new Database())->getConnection();
        $db->prepare("UPDATE blood_stock SET quantite_poches = GREATEST(0, quantite_poches - ?) WHERE groupe_sanguin = ? AND rhesus = ?")->execute([$_POST['quantite'], $_POST['groupe'], $_POST['rhesus']]);
        $db->prepare("INSERT INTO blood_movements (groupe, rhesus, type_mouvement, quantite, source_destination) VALUES (?, ?, 'SORTIE', ?, ?)")->execute([$_POST['groupe'], $_POST['rhesus'], $_POST['quantite'], $_POST['motif']]);
        header('Location: ' . BASE_URL . 'banque-sang?success=1');
    }

    public function markUnavailable() {
    header('Content-Type: application/json');
    $request_id = $_POST['id'] ?? null;

    if (!$request_id) {
        echo json_encode(['success' => false, 'message' => 'ID manquant']);
        return;
    }

    $db = (new Database())->getConnection();

    // 1. Récupérer les détails de la demande (Patient, Groupe, Rhésus)
    $stmt = $db->prepare("SELECT tr.*, p.nom, p.prenom FROM transfusion_requests tr JOIN patients p ON tr.patient_id = p.id WHERE tr.id = ?");
    $stmt->execute([$request_id]);
    $req = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$req) {
        echo json_encode(['success' => false, 'message' => 'Demande introuvable']);
        return;
    }

    // 2. Mettre à jour le statut de la demande
    $db->prepare("UPDATE transfusion_requests SET statut = 'ANNULE' WHERE id = ?")->execute([$request_id]);

    // 3. Créer la notification pour le dossier patient
    $typeSanguin = $req['groupe_requis'] . $req['rhesus_requis'];
    $msg = "ALERTE SANG : Le groupe " . $typeSanguin . " est actuellement INDISPONIBLE en banque. Procédure d'urgence : Veuillez solliciter la famille pour des dons ou contacter les donneurs enregistrés ci-dessous.";

    $stmtNotif = $db->prepare("INSERT INTO patient_notifications (patient_id, message, type) VALUES (?, ?, 'ALERTE')");
    $stmtNotif->execute([$req['patient_id'], $msg]);

    // 4. RECHERCHE STRICTE : Uniquement les donneurs du MÊME GROUPE et MÊME RHÉSUS
    $stmtD = $db->prepare("SELECT nom, prenom, telephone, code_donneur FROM blood_donors
                          WHERE groupe_sanguin = ?
                          AND rhesus = ?
                          AND statut = 'APTE'
                          AND is_anonyme = 0
                          LIMIT 10");

    $stmtD->execute([$req['groupe_requis'], $req['rhesus_requis']]);
    $potential_donors = $stmtD->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'group' => $typeSanguin,
        'donors' => $potential_donors
    ]);
}

// Dans app/controllers/BloodBankController.php

public function deliverRequest() {
    header('Content-Type: application/json');
    $request_id = $_POST['id'];
    $db = (new Database())->getConnection();

    try {
        $db->beginTransaction();

        // 1. Récupérer les infos de la demande (Groupe, Rhésus, Quantité)
        $stmt = $db->prepare("SELECT * FROM transfusion_requests WHERE id = ?");
        $stmt->execute([$request_id]);
        $req = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$req) throw new Exception("Demande introuvable.");

        // 2. Vérifier si le stock est suffisant au moment du clic
        $stmtStock = $db->prepare("SELECT quantite_poches FROM blood_stock WHERE groupe_sanguin = ? AND rhesus = ?");
        $stmtStock->execute([$req['groupe_requis'], $req['rhesus_requis']]);
        $stockActual = $stmtStock->fetchColumn();

        if ($stockActual < $req['quantite_demandee']) {
            throw new Exception("Stock insuffisant pour délivrer cette demande.");
        }

        // 3. Soustraire les poches du stock
        $stmtUpdate = $db->prepare("UPDATE blood_stock SET quantite_poches = quantite_poches - ? WHERE groupe_sanguin = ? AND rhesus = ?");
        $stmtUpdate->execute([$req['quantite_demandee'], $req['groupe_requis'], $req['rhesus_requis']]);

        // 4. Enregistrer le mouvement de sortie
        $stmtMove = $db->prepare("INSERT INTO blood_movements (groupe, rhesus, type_mouvement, quantite, source_destination, reference_id, date_mouvement) VALUES (?, ?, 'SORTIE', ?, 'PATIENT_TRANSFUSION', ?, NOW())");
        $stmtMove->execute([$req['groupe_requis'], $req['rhesus_requis'], $req['quantite_demandee'], $req['patient_id']]);

        // 5. Mettre à jour le statut de la demande
        $db->prepare("UPDATE transfusion_requests SET statut = 'COMPLETE' WHERE id = ?")->execute([$request_id]);

        $db->commit();
        echo json_encode(['success' => true, 'message' => 'Poches délivrées et stock mis à jour.']);

    } catch (Exception $e) {
        $db->rollBack();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
}