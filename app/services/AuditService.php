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

/* ============================================================================
FICHIER : app/services/AuditService.php
SERVICE D'AUDIT ET DE TRAÇABILITÉ MÉDICALE — version enrichie
============================================================================ */

class AuditService {
    private $db;
    private static $schemaChecked = false;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();

        // Sécurité : Démarrer la session si elle n'existe pas pour récupérer les IDs
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $this->ensureSchema();
    }

    /* ────────────────────────────────────────────────────────────────────────
     * MIGRATION AUTO : ajoute les colonnes module + description si absentes.
     * Exécutée au plus une fois par processus (flag statique), erreurs avalées.
     * ──────────────────────────────────────────────────────────────────────── */
    private function ensureSchema() {
        if (self::$schemaChecked) return;
        self::$schemaChecked = true;
        try {
            $cols = $this->db->query("SHOW COLUMNS FROM audit_logs")->fetchAll(PDO::FETCH_COLUMN);
            if (!in_array('module', $cols)) {
                $this->db->exec("ALTER TABLE audit_logs ADD COLUMN module VARCHAR(60) NULL AFTER table_name");
            }
            if (!in_array('description', $cols)) {
                $this->db->exec("ALTER TABLE audit_logs ADD COLUMN description VARCHAR(500) NULL AFTER record_id");
            }
        } catch (Exception $e) {
            error_log('[AuditService] ensureSchema: ' . $e->getMessage());
        }
    }

    /* ────────────────────────────────────────────────────────────────────────
     * CARTOGRAPHIE DES MODULES : table_name → libellé lisible + icône + catégorie
     * ──────────────────────────────────────────────────────────────────────── */
    public static function moduleMap() {
        return [
            // ── Clinique ──────────────────────────────────────────────────
            'patients'                    => ['Dossier patient',   'bi-person-vcard',      'clinical'],
            'consultations'               => ['Consultation',      'bi-clipboard2-pulse',  'clinical'],
            'consultations_pediatriques'  => ['Consultation pédiatrique', 'bi-emoji-smile','clinical'],
            'consultations_gyneco'        => ['Consultation gynéco','bi-gender-female',    'clinical'],
            'ordonnances'                 => ['Prescription',      'bi-capsule',           'clinical'],
            'ordonnances_pharmacie'       => ['Prescription',      'bi-capsule',           'clinical'],
            'ordonnance_medicaments'      => ['Prescription',      'bi-capsule',           'clinical'],
            'prescriptions'               => ['Prescription',      'bi-capsule',           'clinical'],
            'prescriptions_hospitalisation'=>['Prescription hosp.','bi-capsule-pill',      'clinical'],
            'demandes_laboratoire'        => ['Laboratoire',       'bi-flask',             'clinical'],
            'demande_examens'             => ['Laboratoire',       'bi-flask',             'clinical'],
            'examens_laboratoire'         => ['Laboratoire',       'bi-flask',             'clinical'],
            'demandes_imagerie'           => ['Imagerie',          'bi-radioactive',       'clinical'],
            'hospitalisations'            => ['Hospitalisation',   'bi-hospital',          'clinical'],
            'soins_hospitalisation'       => ['Soins',             'bi-bandaid',           'clinical'],
            'reevaluations_hospitalisees' => ['Réévaluation',      'bi-arrow-repeat',      'clinical'],
            'urgences_patients'           => ['Urgences',          'bi-truck-front',       'clinical'],
            'bilans'                      => ['Bilan',             'bi-clipboard-data',    'clinical'],
            'bulletins_examens'           => ['Bulletin examens',  'bi-file-medical',      'clinical'],
            'documents_patient'           => ['Document patient',  'bi-file-earmark-medical','clinical'],
            'transfusions'                => ['Transfusion',       'bi-droplet-half',      'clinical'],
            'banque_sang'                 => ['Banque de sang',    'bi-droplet',           'clinical'],
            'interventions'               => ['Bloc opératoire',   'bi-scissors',          'clinical'],
            // ── Logistique / Stock ───────────────────────────────────────
            'medicaments'                 => ['Stock pharmacie',   'bi-box-seam',          'logistic'],
            'lits'                        => ['Gestion des lits',  'bi-hospital',          'logistic'],
            'chambres'                    => ['Chambres',          'bi-door-open',         'logistic'],
            // ── Facturation ──────────────────────────────────────────────
            'factures'                    => ['Facturation',       'bi-receipt',           'finance'],
            'facturation'                 => ['Facturation',       'bi-receipt',           'finance'],
            'paiements'                   => ['Paiement',          'bi-cash-coin',         'finance'],
            // ── Agenda ───────────────────────────────────────────────────
            'rdv_specialistes'            => ['Rendez-vous',       'bi-calendar-check',    'agenda'],
            'agenda_medical'              => ['Agenda',            'bi-calendar3',         'agenda'],
            // ── Administration ───────────────────────────────────────────
            'users'                       => ['Utilisateurs',      'bi-people',            'admin'],
            'services'                    => ['Services',          'bi-building',          'admin'],
            'role_permissions'            => ['Permissions',       'bi-shield-lock',       'admin'],
            'system_config'               => ['Configuration',     'bi-gear',              'system'],
            'examens_laboratoire_cat'     => ['Catalogue labo',    'bi-list-check',        'admin'],
        ];
    }

    /** Retourne [label, icon, categorie] pour une table donnée. */
    public static function moduleInfo($table_name, $action = null) {
        $map = self::moduleMap();
        if ($table_name && isset($map[$table_name])) {
            return ['label' => $map[$table_name][0], 'icon' => $map[$table_name][1], 'categorie' => $map[$table_name][2]];
        }
        // Actions d'authentification
        if (in_array($action, ['LOGIN','LOGOUT','LOGIN_FAIL','LOGIN_ATTEMPT'])) {
            return ['label' => 'Authentification', 'icon' => 'bi-box-arrow-in-right', 'categorie' => 'auth'];
        }
        if ($action === 'ACCESS_DENIED' || $action === 'ANONYMIZE_PATIENT') {
            return ['label' => 'Sécurité', 'icon' => 'bi-shield-exclamation', 'categorie' => 'security'];
        }
        if ($action === 'EXPORT') {
            return ['label' => 'Export', 'icon' => 'bi-download', 'categorie' => 'admin'];
        }
        return ['label' => $table_name ?: 'Système', 'icon' => 'bi-hdd-stack', 'categorie' => 'system'];
    }

    /**
     * Enregistre une action précise (Audit Trail) — compatible ascendant.
     * Actions : CREATE, UPDATE, DELETE, READ, LOGIN, LOGOUT…
     */
    public function logAction($action, $table_name, $record_id = null, $old_values = null, $new_values = null) {
        return $this->writeLog($action, $table_name, $record_id, null, $old_values, $new_values, null);
    }

    /**
     * Enregistrement enrichi avec description lisible + module explicite.
     *
     * @param string      $action       CREATE/UPDATE/DELETE/READ/…
     * @param string      $table_name   Table technique concernée
     * @param int|null    $record_id    Identifiant de l'enregistrement
     * @param string|null $description  Phrase lisible ("Consultation finalisée — diagnostic : …")
     * @param array|null  $old_values   Valeurs avant
     * @param array|null  $new_values   Valeurs après
     * @param string|null $module       Libellé module (auto si null)
     */
    public function log($action, $table_name, $record_id = null, $description = null,
                        $old_values = null, $new_values = null, $module = null) {
        return $this->writeLog($action, $table_name, $record_id, $description, $old_values, $new_values, $module);
    }

    /** Écriture réelle en base avec repli si colonnes manquantes. */
    private function writeLog($action, $table_name, $record_id, $description, $old_values, $new_values, $module) {
        $user_id    = $_SESSION['user_id'] ?? null;
        $service_id = $_SESSION['service_id'] ?? null;
        $ip_address = $this->getClientIP();
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';

        if ($module === null) {
            $module = self::moduleInfo($table_name, $action)['label'];
        }

        try {
            $sql = "INSERT INTO audit_logs
                        (user_id, service_id, action, table_name, module, record_id, description,
                         old_values, new_values, ip_address, user_agent)
                    VALUES (:user_id, :service_id, :action, :table_name, :module, :record_id, :description,
                            :old_values, :new_values, :ip_address, :user_agent)";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                ':user_id'     => $user_id,
                ':service_id'  => $service_id,
                ':action'      => $action,
                ':table_name'  => $table_name,
                ':module'      => $module,
                ':record_id'   => $record_id,
                ':description' => $description ? mb_substr($description, 0, 500) : null,
                ':old_values'  => $old_values ? json_encode($old_values, JSON_UNESCAPED_UNICODE) : null,
                ':new_values'  => $new_values ? json_encode($new_values, JSON_UNESCAPED_UNICODE) : null,
                ':ip_address'  => $ip_address,
                ':user_agent'  => $user_agent
            ]);
        } catch (PDOException $e) {
            // Repli si les colonnes module/description n'existent pas encore
            try {
                $sql = "INSERT INTO audit_logs
                            (user_id, service_id, action, table_name, record_id, old_values, new_values, ip_address, user_agent)
                        VALUES (:user_id, :service_id, :action, :table_name, :record_id, :old_values, :new_values, :ip_address, :user_agent)";
                $stmt = $this->db->prepare($sql);
                // Injecter la description dans new_values pour ne pas la perdre
                if ($description) {
                    $new_values = is_array($new_values) ? array_merge(['info' => $description], $new_values) : ['info' => $description];
                }
                return $stmt->execute([
                    ':user_id'    => $user_id,
                    ':service_id' => $service_id,
                    ':action'     => $action,
                    ':table_name' => $table_name,
                    ':record_id'  => $record_id,
                    ':old_values' => $old_values ? json_encode($old_values, JSON_UNESCAPED_UNICODE) : null,
                    ':new_values' => $new_values ? json_encode($new_values, JSON_UNESCAPED_UNICODE) : null,
                    ':ip_address' => $ip_address,
                    ':user_agent' => $user_agent
                ]);
            } catch (PDOException $e2) {
                error_log('[AuditService] writeLog: ' . $e2->getMessage());
                return false;
            }
        }
    }

    /**
     * Spécifique pour tracer la LECTURE d'un dossier patient
     */
    public function logRead($table_name, $record_id, $details = "") {
        return $this->log('READ', $table_name, $record_id, $details ?: null, null, null);
    }

    /**
     * Enregistre les tentatives de connexion (pour la sécurité)
     */
   public function logLogin($username, $success, $service_id = null, $failure_reason = null) {
    $sql = "INSERT INTO login_attempts (username, service_id, ip_address, success, failure_reason)
            VALUES (:username, :service_id, :ip_address, :success, :failure_reason)";

    $stmt = $this->db->prepare($sql);

    return $stmt->execute([
        ':username'       => $username,
        ':service_id'     => $service_id,
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
        $sql = "SELECT COUNT(*) as failed_attempts
                FROM login_attempts
                WHERE username = (SELECT username FROM users WHERE id = :user_id)
                AND success = FALSE
                AND created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':user_id' => $user_id]);
        $failed = $stmt->fetch(PDO::FETCH_ASSOC)['failed_attempts'];

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

    /**
     * Analyse un user-agent → navigateur + OS + type d'appareil (statique, réutilisable par la vue).
     */
    public static function parseUserAgent($ua) {
        $ua = $ua ?: '';
        $browser = 'Inconnu'; $os = 'Inconnu'; $device = 'desktop';

        // OS
        if (preg_match('/Windows NT 10/i', $ua))      $os = 'Windows 10/11';
        elseif (preg_match('/Windows NT 6\.3/i', $ua)) $os = 'Windows 8.1';
        elseif (preg_match('/Windows/i', $ua))         $os = 'Windows';
        elseif (preg_match('/Android/i', $ua))       { $os = 'Android'; $device = 'mobile'; }
        elseif (preg_match('/iPhone|iPad|iOS/i', $ua)){ $os = 'iOS'; $device = preg_match('/iPad/i',$ua)?'tablet':'mobile'; }
        elseif (preg_match('/Mac OS X/i', $ua))        $os = 'macOS';
        elseif (preg_match('/Linux/i', $ua))           $os = 'Linux';

        // Navigateur (ordre important : Edge/Chrome contiennent "Safari")
        if (preg_match('/Edg\//i', $ua))            $browser = 'Edge';
        elseif (preg_match('/OPR|Opera/i', $ua))    $browser = 'Opera';
        elseif (preg_match('/Chrome/i', $ua))       $browser = 'Chrome';
        elseif (preg_match('/Firefox/i', $ua))      $browser = 'Firefox';
        elseif (preg_match('/Safari/i', $ua))       $browser = 'Safari';

        return ['browser' => $browser, 'os' => $os, 'device' => $device];
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
