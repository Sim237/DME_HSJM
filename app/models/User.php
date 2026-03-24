<?php
/* ============================================================================
   FICHIER : User.php
   Modèle complet pour la gestion des utilisateurs
   ============================================================================ */
require_once __DIR__ . '/../../config/database.php';

class User {
    private $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }

   public function save($data) {
    try {
        // --- LOGIQUE CRUCIALE POUR L'EMAIL ---
        // Si l'email est vide, on force la valeur à NULL pour éviter les erreurs de doublons
        $email = (!empty($data['email'])) ? $data['email'] : null;

        if (empty($data['id'])) {
            // ==========================================
            // MODE CRÉATION (INSERT)
            // ==========================================
            $sql = "INSERT INTO users (
                        nom, prenom, username, email, telephone,
                        role, service_id, signature_path, cachet_path,
                        password, statut, created_at
                    ) VALUES (
                        :nom, :prenom, :username, :email, :telephone,
                        :role, :service_id, :signature_path, :cachet_path,
                        :password, 1, NOW()
                    )";

            $stmt = $this->db->prepare($sql);

            return $stmt->execute([
                ':nom'            => strtoupper($data['nom']),
                ':prenom'         => $data['prenom'],
                ':username'       => strtolower($data['username']),
                ':email'          => $email, // Utilise la variable nettoyée (valeur ou null)
                ':telephone'      => $data['telephone'] ?? null,
                ':role'           => $data['role'],
                ':service_id'     => $data['service_id'],
                ':signature_path' => $data['signature_path'] ?? null,
                ':cachet_path'    => $data['cachet_path'] ?? null,
                ':password'       => password_hash($data['password'], PASSWORD_DEFAULT)
            ]) ? $this->db->lastInsertId() : false;

        } else {
            // ==========================================
            // MODE MISE À JOUR (UPDATE)
            // ==========================================
            $sql = "UPDATE users SET
                    nom = :nom, prenom = :prenom, username = :username,
                    email = :email, telephone = :telephone, role = :role,
                    service_id = :service_id, statut = :statut";

            $params = [
                ':nom'        => strtoupper($data['nom']),
                ':prenom'     => $data['prenom'],
                ':username'   => strtolower($data['username']),
                ':email'      => $email, // Utilise la variable nettoyée
                ':telephone'  => $data['telephone'] ?? null,
                ':role'       => $data['role'],
                ':service_id' => $data['service_id'],
                ':statut'     => $data['statut'] ?? 1,
                ':id'         => $data['id']
            ];

            // Ajout dynamique du mot de passe s'il a été changé
            if (!empty($data['password'])) {
                $sql .= ", password = :password";
                $params[':password'] = password_hash($data['password'], PASSWORD_DEFAULT);
            }

            // Ajout dynamique des fichiers si uploadés
            if (!empty($data['signature_path'])) {
                $sql .= ", signature_path = :sig";
                $params[':sig'] = $data['signature_path'];
            }
            if (!empty($data['cachet_path'])) {
                $sql .= ", cachet_path = :cac";
                $params[':cac'] = $data['cachet_path'];
            }

            $sql .= " WHERE id = :id";

            $stmt = $this->db->prepare($sql);
            return $stmt->execute($params) ? $data['id'] : false;
        }
    } catch (Exception $e) {
        error_log("Erreur critique User::save : " . $e->getMessage());
        return false;
    }
}

    public function getAll() {
        $sql = "SELECT u.*, s.nom_service FROM users u
                LEFT JOIN services s ON u.service_id = s.id
                ORDER BY u.nom ASC, u.prenom ASC";
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById($id) {
        $sql = "SELECT u.*, s.nom_service FROM users u
                LEFT JOIN services s ON u.service_id = s.id
                WHERE u.id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function delete($id) {
        $sql = "UPDATE users SET statut = 0 WHERE id = :id";
        return $this->db->prepare($sql)->execute([':id' => $id]);
    }
}