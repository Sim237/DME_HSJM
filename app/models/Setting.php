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

class Setting {
    private $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    public function get() {
        // On récupère toujours la ligne ID=1
        $stmt = $this->db->query("SELECT * FROM settings WHERE id = 1");
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function update($data) {
        $sql = "UPDATE settings SET 
                nom_hopital = :nom,
                adresse = :adresse,
                telephone = :tel,
                email = :email,
                devise = :devise,
                prefixe_dossier = :prefixe
                WHERE id = 1";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':nom' => $data['nom_hopital'],
            ':adresse' => $data['adresse'],
            ':tel' => $data['telephone'],
            ':email' => $data['email'],
            ':devise' => $data['devise'],
            ':prefixe' => $data['prefixe_dossier']
        ]);
    }
}
?>