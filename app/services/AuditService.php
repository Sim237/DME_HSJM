<?php
/* ============================================================================
FICHIER : app/services/AuditService.php
SERVICE D'AUDIT ET DE TRAÇABILITÉ MÉDICALE
============================================================================ */

class AuditService {
    private $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();

        // Sécurité : Démarrer la session si elle n'existe pas pour récupérer les IDs
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * Enregistre une action précise dans le système (Audit Trail)
     * Actions : CREATE, UPDATE, DELETE, READ, LOGIN, LOGOUT
     */
    public function logAction($action, $table_name, $record_id = null, $old_values = null, $new_values = null) {
        $user_id = $_SESSION['user_id'] ?? null;
        $service_id = $_SESSION['service_id'] ?? null; // Indispensable pour le cloisonnement
        $ip_address = $this->getClientIP();
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';

        $sql = "INSERT INTO audit_logs (user_id, service_id, action, table_name, record_id, old_values, new_values, ip_address, user_agent)
                VALUES (:user_id, :service_id, :action, :table_name, :record_id, :old_values, :new_values, :ip_address, :user_agent)";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':user_id' => $user_id,
            ':service_id' => $service_id,
            ':action' => $action,
            ':table_name' => $table_name,
            ':record_id' => $record_id,
            ':old_values' => $old_values ? json_encode($old_values) : null,
            ':new_values' => $new_values ? json_encode($new_values) : null,
            ':ip_address' => $ip_address,
            ':user_agent' => $user_agent
        ]);
    }

    /**
     * Spécifique pour tracer la LECTURE d'un dossier patient
     */
    public function logRead($table_name, $record_id, $details = "") {
        return $this->logAction('READ', $table_name, $record_id, null, ['info' => $details]);
    }

    /**
     * Enregistre les tentatives de connexion (pour la sécurité)
     */
   public function logLogin($username, $success, $service_id = null, $failure_reason = null) {
    // 1. La requête SQL (5 colonnes)
    $sql = "INSERT INTO login_attempts (username, service_id, ip_address, success, failure_reason)
            VALUES (:username, :service_id, :ip_address, :success, :failure_reason)";

    $stmt = $this->db->prepare($sql);

    // 2. L'exécution (5 clés)
    return $stmt->execute([
        ':username'       => $username,
        ':service_id'     => $service_id, // Vérifiez que cette variable n'est pas oubliée
        ':ip_address'     => $this->getClientIP(),
        ':success'        => $success ? 1 : 0,
        ':failure_reason' => $failure_reason
    ]);
}

    /**
     * Récupère les logs pour le Dashboard Administrateur
     */
    public function getAuditLogs($filters = []) {
        $sql = "SELECT a.*, u.nom, u.prenom, u.username, s.nom_service
                FROM audit_logs a
                LEFT JOIN users u ON a.user_id = u.id
                LEFT JOIN services s ON a.service_id = s.id
                WHERE 1=1";

        $params = [];

        if (!empty($filters['service_id'])) {
            $sql .= " AND a.service_id = :service_id";
            $params[':service_id'] = $filters['service_id'];
        }

        if (!empty($filters['action'])) {
            $sql .= " AND a.action = :action";
            $params[':action'] = $filters['action'];
        }

        if (!empty($filters['user_id'])) {
            $sql .= " AND a.user_id = :user_id";
            $params[':user_id'] = $filters['user_id'];
        }

        $sql .= " ORDER BY a.created_at DESC LIMIT 1000";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Détecte les comportements suspects pour l'Admin
     */
    public function checkSuspiciousActivity($user_id) {
        // 1. Tentatives de connexion échouées dans l'heure
        $sql = "SELECT COUNT(*) as failed_attempts
                FROM login_attempts
                WHERE username = (SELECT username FROM users WHERE id = :user_id)
                AND success = FALSE
                AND created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':user_id' => $user_id]);
        $failed = $stmt->fetch(PDO::FETCH_ASSOC)['failed_attempts'];

        // 2. Nombre de suppressions ou d'accès hors-service
        $sql = "SELECT COUNT(*) as suspicious_actions
                FROM audit_logs
                WHERE user_id = :user_id
                AND (action = 'DELETE' OR (action = 'READ' AND new_values LIKE '%REFUSÉ%'))
                AND created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':user_id' => $user_id]);
        $suspicious = $stmt->fetch(PDO::FETCH_ASSOC)['suspicious_actions'];

        return [
            'is_suspicious' => ($failed > 5 || $suspicious > 3),
            'failed_count' => $failed,
            'suspicious_count' => $suspicious
        ];
    }

    /**
     * Récupère l'IP réelle de l'utilisateur
     */
    private function getClientIP() {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) return $_SERVER['HTTP_CLIENT_IP'];
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) return $_SERVER['HTTP_X_FORWARDED_FOR'];
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
}

/**
 * TRAIT Auditable : À inclure dans vos Modèles pour automatiser l'audit
 */
trait Auditable {
    protected function auditCreate($table, $id, $data) {
        (new AuditService())->logAction('CREATE', $table, $id, null, $data);
    }

    protected function auditUpdate($table, $id, $old_data, $new_data) {
        (new AuditService())->logAction('UPDATE', $table, $id, $old_data, $new_data);
    }

    protected function auditDelete($table, $id, $data) {
        (new AuditService())->logAction('DELETE', $table, $id, $data, null);
    }

    protected function auditRead($table, $id, $details = "") {
        (new AuditService())->logRead($table, $id, $details);
    }
}
?>