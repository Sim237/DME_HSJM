<?php
class ApiController {
    public function getUsersByService() {
        header('Content-Type: application/json');
        $db = (new Database())->getConnection();

        $service_id = $_GET['service'] ?? 0;
        $role = $_GET['role'] ?? '';

        $stmt = $db->prepare("SELECT id, nom, prenom FROM users WHERE service_id = ? AND role = ? AND statut = 1");
        $stmt->execute([$service_id, $role]);
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    }
}