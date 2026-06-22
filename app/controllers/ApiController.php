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

class ApiController {
    public function getUsersByService() {
        header('Content-Type: application/json');
        $db = (new Database())->getConnection();

        $service_id = $_GET['service'] ?? 0;
        $role = $_GET['role'] ?? '';

        $stmt = $db->prepare("SELECT id, nom, prenom FROM users WHERE service_id = ? AND role = ? AND actif = 1");
        $stmt->execute([$service_id, $role]);
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    }
}