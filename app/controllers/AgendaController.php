<?php
require_once __DIR__ . '/UnifiedController.php';

class AgendaController extends UnifiedController {
    
    public function index() {
        $this->auth->requirePermission('consultations', 'read');
        require_once __DIR__ . '/../views/agenda/index.php';
    }
    
    public function getEvents() {
        $start = $_GET['start'] ?? date('Y-m-01');
        $end = $_GET['end'] ?? date('Y-m-t');
        $medecin_id = $_GET['medecin_id'] ?? $_SESSION['user_id'];
        
        $database = new Database();
        $db = $database->getConnection();
        
        $sql = "SELECT a.*, p.nom, p.prenom, u.nom as medecin_nom, u.prenom as medecin_prenom
                FROM agenda_medical a
                LEFT JOIN patients p ON a.patient_id = p.id
                LEFT JOIN users u ON a.medecin_id = u.id
                WHERE a.date_debut >= :start AND a.date_fin <= :end";
        
        if ($this->auth->getUserRole() !== 'ADMIN') {
            $sql .= " AND a.medecin_id = :medecin_id";
        }
        
        $stmt = $db->prepare($sql);
        $params = [':start' => $start, ':end' => $end];
        if ($this->auth->getUserRole() !== 'ADMIN') {
            $params[':medecin_id'] = $medecin_id;
        }
        
        $stmt->execute($params);
        $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $calendar_events = [];
        foreach ($events as $event) {
            $title = $event['titre'];
            if ($event['nom']) {
                $title .= ' - ' . $event['nom'] . ' ' . $event['prenom'];
            }
            
            $calendar_events[] = [
                'id' => $event['id'],
                'title' => $title,
                'start' => $event['date_debut'],
                'end' => $event['date_fin'],
                'backgroundColor' => $event['couleur'],
                'borderColor' => $event['couleur'],
                'extendedProps' => [
                    'type' => $event['type_rdv'],
                    'statut' => $event['statut'],
                    'salle' => $event['salle'],
                    'description' => $event['description'],
                    'patient' => $event['nom'] ? $event['nom'] . ' ' . $event['prenom'] : null
                ]
            ];
        }
        
        header('Content-Type: application/json');
        echo json_encode($calendar_events);
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