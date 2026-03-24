<?php
require_once __DIR__ . '/UnifiedController.php';

class AgendaController extends UnifiedController {

    public function index() {
        $this->auth->requirePermission('consultations', 'read');
        require_once __DIR__ . '/../views/agenda/index.php';
    }

    public function getEvents() {
    header('Content-Type: application/json');
    $db = (new Database())->getConnection();

    $stmt = $db->prepare("SELECT * FROM agenda_medical WHERE medecin_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $events = [];
    foreach($rows as $row) {
        // Couleurs dynamiques Soft
        $color = '#0d6efd'; // Bleu
        if($row['type_rdv'] == 'urgence') $color = '#dc3545'; // Rouge
        if($row['type_rdv'] == 'suivi') $color = '#198754';   // Vert
        if($row['type_rdv'] == 'intervention') $color = '#6f42c1'; // Violet

        $events[] = [
            'id' => $row['id'],
            'title' => $row['titre'],
            'start' => $row['date_debut'],
            'end' => $row['date_fin'],
            'backgroundColor' => $color,
            'borderColor' => $color
        ];
    }
    echo json_encode($events);
}

    public function save() {
        $this->auth->requirePermission('consultations', 'write');

        $data = [
            'medecin_id' => $_POST['medecin_id'] ?? $_SESSION['user_id'],
            'patient_id' => $_POST['patient_id'] ?: null,
            'type_rdv' => $_POST['type_rdv'],
            'titre' => $_POST['titre'],
            'description' => $_POST['description'] ?? '',
            'date_debut' => $_POST['date_debut'],
            'date_fin' => $_POST['date_fin'],
            'salle' => $_POST['salle'] ?? '',
            'couleur' => $_POST['couleur'] ?? '#007bff'
        ];

        $database = new Database();
        $db = $database->getConnection();

        if (!empty($_POST['id'])) {
            // Modification
            $sql = "UPDATE agenda_medical SET
                    medecin_id = :medecin_id, patient_id = :patient_id, type_rdv = :type_rdv,
                    titre = :titre, description = :description, date_debut = :date_debut,
                    date_fin = :date_fin, salle = :salle, couleur = :couleur
                    WHERE id = :id";
            $data[':id'] = $_POST['id'];
        } else {
            // Création
            $sql = "INSERT INTO agenda_medical (medecin_id, patient_id, type_rdv, titre, description, date_debut, date_fin, salle, couleur)
                    VALUES (:medecin_id, :patient_id, :type_rdv, :titre, :description, :date_debut, :date_fin, :salle, :couleur)";
        }

        $stmt = $db->prepare($sql);
        $success = $stmt->execute($data);

        echo json_encode(['success' => $success]);
    }

    public function delete($id) {
        $this->auth->requirePermission('consultations', 'delete');

        $database = new Database();
        $db = $database->getConnection();

        $sql = "DELETE FROM agenda_medical WHERE id = :id";
        $stmt = $db->prepare($sql);
        $success = $stmt->execute([':id' => $id]);

        echo json_encode(['success' => $success]);
    }
}
?>