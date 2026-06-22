<?php
/* ============================================================================
   FICHIER : app/controllers/ChatController.php
   MODULE DE MESSAGERIE MÉDICALE — MÉDECINS
   © SimCare+ HSJM — tout usage local uniquement
============================================================================ */

class ChatController {

    private PDO    $db;
    private int    $userId;
    private string $uploadDir;

    public function __construct() {
        /* Authentification : session PHP OU token HMAC en query param (_uid + _tok) */
        $userId = (int)($_SESSION['user_id'] ?? 0);

        if (!$userId) {
            $uid   = (int)($_GET['_uid'] ?? 0);
            $token = $_GET['_tok'] ?? '';
            if ($uid > 0 && $token !== '') {
                $chatSecret = getenv('CHAT_HMAC_SECRET') ?: 'FALLBACK_CHANGE_ME_IN_ENV';
                $h  = floor(time() / 3600);
                $ok = hash_equals(hash_hmac('sha256', $uid . '|' . $h,       $chatSecret), $token)
                   || hash_equals(hash_hmac('sha256', $uid . '|' . ($h - 1), $chatSecret), $token);
                if ($ok) $userId = $uid;
            }
        }

        if (!$userId) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Non authentifié']);
            exit;
        }

        $this->db        = (new Database())->getConnection();
        $this->userId    = $userId;
        $this->uploadDir = __DIR__ . '/../../uploads/chat/';
        if (!is_dir($this->uploadDir)) {
            @mkdir($this->uploadDir, 0755, true); /* @ = supprime le warning si permission refusée */
        }
        /* Buffer de sortie : évite que des warnings PHP polluent les réponses JSON */
        if (!ob_get_level()) ob_start();
        $this->migrate();
    }

    /* ── Migration idempotente (guard session — exécutée max 1×/session) ──── */
    private function migrate(): void
    {
        // v2 : force re-migration pour créer chat_calls + chat_call_ice
        if (!empty($_SESSION['_chat_migration_v2_ok'])) return;
        $_SESSION['_chat_migration_v2_ok'] = true;
        unset($_SESSION['_chat_migration_ok']); // invalider l'ancien flag
        try { $this->db->exec("CREATE TABLE IF NOT EXISTS `chat_conversations` (
            `id`         INT NOT NULL AUTO_INCREMENT,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $this->db->exec("CREATE TABLE IF NOT EXISTS `chat_participants` (
            `id`              INT NOT NULL AUTO_INCREMENT,
            `conversation_id` INT NOT NULL,
            `user_id`         INT NOT NULL,
            `last_read_id`    INT NOT NULL DEFAULT 0,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uq_conv_user` (`conversation_id`, `user_id`),
            KEY `idx_user` (`user_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $this->db->exec("CREATE TABLE IF NOT EXISTS `chat_messages` (
            `id`              INT NOT NULL AUTO_INCREMENT,
            `conversation_id` INT NOT NULL,
            `sender_id`       INT NOT NULL,
            `type`            ENUM('text','image','file','bilan') NOT NULL DEFAULT 'text',
            `content`         TEXT DEFAULT NULL,
            `file_path`       VARCHAR(500) DEFAULT NULL,
            `file_name`       VARCHAR(255) DEFAULT NULL,
            `file_mime`       VARCHAR(100) DEFAULT NULL,
            `bilan_id`        INT DEFAULT NULL,
            `is_deleted`      TINYINT(1) NOT NULL DEFAULT 0,
            `is_edited`       TINYINT(1) NOT NULL DEFAULT 0,
            `edited_at`       DATETIME DEFAULT NULL,
            `created_at`      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `idx_conv_id` (`conversation_id`),
            KEY `idx_sender`  (`sender_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $this->db->exec("CREATE TABLE IF NOT EXISTS `user_presence` (
            `user_id`   INT NOT NULL,
            `last_seen` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`user_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $this->db->exec("CREATE TABLE IF NOT EXISTS `chat_calls` (
            `id`              INT NOT NULL AUTO_INCREMENT,
            `conversation_id` INT NOT NULL,
            `caller_id`       INT NOT NULL,
            `callee_id`       INT NOT NULL,
            `call_type`       ENUM('audio','video') NOT NULL DEFAULT 'audio',
            `status`          ENUM('ringing','accepted','rejected','ended','missed') NOT NULL DEFAULT 'ringing',
            `sdp_offer`       MEDIUMTEXT DEFAULT NULL,
            `sdp_answer`      MEDIUMTEXT DEFAULT NULL,
            `created_at`      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at`      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `idx_callee` (`callee_id`, `status`),
            KEY `idx_caller` (`caller_id`, `status`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $this->db->exec("CREATE TABLE IF NOT EXISTS `chat_call_ice` (
            `id`         INT NOT NULL AUTO_INCREMENT,
            `call_id`    INT NOT NULL,
            `sender_id`  INT NOT NULL,
            `candidate`  TEXT NOT NULL,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `idx_call_ice` (`call_id`, `id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        } catch (\Throwable $e) {
            error_log("ChatController::migrate : " . $e->getMessage());
        }
    }

    /* ── Heartbeat (présence) ────────────────────────────────────────────── */
    public function heartbeat(): void
    {
        header('Content-Type: application/json');
        $this->touchPresence();
        echo json_encode(['ok' => true]);
        exit;
    }

    /* ── Carnet d'adresses : liste des médecins ──────────────────────────── */
    public function doctors(): void
    {
        header('Content-Type: application/json');
        $q = trim($_GET['q'] ?? '');
        $sql = "SELECT u.id, u.nom, u.prenom,
                       COALESCE(s.nom_service, '') AS nom_service,
                       CASE WHEN up.last_seen >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)
                            THEN 'online' ELSE 'offline' END AS statut
                FROM users u
                LEFT JOIN services s ON s.id = u.service_id
                LEFT JOIN user_presence up ON up.user_id = u.id
                WHERE u.role = 'MEDECIN' AND u.actif = 1 AND u.id != ?";
        $params = [$this->userId];
        if ($q !== '') {
            $sql    .= " AND (u.nom LIKE ? OR u.prenom LIKE ? OR CONCAT(u.prenom,' ',u.nom) LIKE ?)";
            $like    = "%$q%";
            $params  = array_merge($params, [$like, $like, $like]);
        }
        $sql .= " ORDER BY statut DESC, u.nom ASC LIMIT 60";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        exit;
    }

    /* ── Démarrer / récupérer une conversation ───────────────────────────── */
    public function startConv(): void
    {
        header('Content-Type: application/json');
        $otherId = (int)($_POST['other_id'] ?? 0);
        if (!$otherId) { echo json_encode(['success' => false]); exit; }

        /* Chercher conversation existante entre les deux */
        $stmt = $this->db->prepare("
            SELECT cp1.conversation_id
            FROM chat_participants cp1
            JOIN chat_participants cp2
              ON cp2.conversation_id = cp1.conversation_id AND cp2.user_id = ?
            WHERE cp1.user_id = ?
            LIMIT 1
        ");
        $stmt->execute([$otherId, $this->userId]);
        $convId = $stmt->fetchColumn();

        if (!$convId) {
            $this->db->exec("INSERT INTO chat_conversations () VALUES ()");
            $convId = (int)$this->db->lastInsertId();
            $this->db->prepare("INSERT INTO chat_participants (conversation_id, user_id) VALUES (?,?),(?,?)")
                     ->execute([$convId, $this->userId, $convId, $otherId]);
        }

        $stmtU = $this->db->prepare("
            SELECT u.id, u.nom, u.prenom,
                   COALESCE(s.nom_service,'') AS nom_service,
                   CASE WHEN up.last_seen >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)
                        THEN 'online' ELSE 'offline' END AS statut
            FROM users u
            LEFT JOIN services s ON s.id = u.service_id
            LEFT JOIN user_presence up ON up.user_id = u.id
            WHERE u.id = ?
        ");
        $stmtU->execute([$otherId]);
        $other = $stmtU->fetch(PDO::FETCH_ASSOC);

        echo json_encode(['success' => true, 'conversation_id' => (int)$convId, 'other' => $other]);
        exit;
    }

    /* ── Liste des conversations ─────────────────────────────────────────── */
    public function conversations(): void
    {
        header('Content-Type: application/json');
        $stmt = $this->db->prepare("
            SELECT c.id AS conversation_id,
                   c.updated_at,
                   u.id   AS other_id,
                   u.nom  AS other_nom,
                   u.prenom AS other_prenom,
                   COALESCE(s.nom_service,'') AS other_service,
                   CASE WHEN up.last_seen >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)
                        THEN 'online' ELSE 'offline' END AS statut,
                   lm.content   AS last_content,
                   lm.type      AS last_type,
                   lm.created_at AS last_at,
                   lm.sender_id  AS last_sender,
                   (SELECT COUNT(*)
                    FROM chat_messages cm
                    WHERE cm.conversation_id = c.id
                      AND cm.sender_id != :me1
                      AND cm.is_deleted = 0
                      AND cm.id > cp_mine.last_read_id) AS unread
            FROM chat_conversations c
            JOIN chat_participants cp_mine
              ON cp_mine.conversation_id = c.id AND cp_mine.user_id = :me2
            JOIN chat_participants cp_other
              ON cp_other.conversation_id = c.id AND cp_other.user_id != :me3
            JOIN users u ON u.id = cp_other.user_id
            LEFT JOIN services s ON s.id = u.service_id
            LEFT JOIN user_presence up ON up.user_id = u.id
            LEFT JOIN chat_messages lm ON lm.id = (
                SELECT id FROM chat_messages
                WHERE conversation_id = c.id AND is_deleted = 0
                ORDER BY id DESC LIMIT 1
            )
            ORDER BY c.updated_at DESC
            LIMIT 50
        ");
        $stmt->execute([':me1' => $this->userId, ':me2' => $this->userId, ':me3' => $this->userId]);
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        exit;
    }

    /* ── Messages d'une conversation ─────────────────────────────────────── */
    public function messages(): void
    {
        header('Content-Type: application/json');
        $convId = (int)($_GET['conv_id'] ?? 0);
        $before = (int)($_GET['before']  ?? PHP_INT_MAX);

        if (!$this->checkParticipant($convId)) {
            echo json_encode(['success' => false, 'error' => 'Accès refusé']); exit;
        }

        $stmt = $this->db->prepare("
            SELECT m.id, m.conversation_id, m.sender_id, m.type,
                   m.content, m.file_path, m.file_name, m.file_mime,
                   m.bilan_id, m.ref_id, m.is_deleted, m.is_edited, m.edited_at, m.created_at,
                   u.nom AS sender_nom, u.prenom AS sender_prenom
            FROM chat_messages m
            JOIN users u ON u.id = m.sender_id
            WHERE m.conversation_id = ? AND m.id < ?
            ORDER BY m.id DESC
            LIMIT 40
        ");
        $stmt->execute([$convId, $before]);
        $msgs = array_reverse($stmt->fetchAll(PDO::FETCH_ASSOC));

        /* Marquer comme lu */
        if (!empty($msgs)) {
            $lastId = (int)end($msgs)['id'];
            $this->db->prepare("UPDATE chat_participants
                                SET last_read_id = GREATEST(last_read_id, ?)
                                WHERE conversation_id = ? AND user_id = ?")
                     ->execute([$lastId, $convId, $this->userId]);
        }

        /* Infos sur l'interlocuteur */
        $stmtOther = $this->db->prepare("
            SELECT u.id, u.nom, u.prenom,
                   CASE WHEN up.last_seen >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)
                        THEN 'online' ELSE 'offline' END AS statut
            FROM chat_participants cp
            JOIN users u ON u.id = cp.user_id
            LEFT JOIN user_presence up ON up.user_id = u.id
            WHERE cp.conversation_id = ? AND cp.user_id != ?
            LIMIT 1
        ");
        $stmtOther->execute([$convId, $this->userId]);
        $other = $stmtOther->fetch(PDO::FETCH_ASSOC) ?: [];

        echo json_encode(['success' => true, 'messages' => $msgs, 'other' => $other, 'conv_id' => $convId]);
        exit;
    }

    /* ── Polling : nouveaux messages depuis last_id ───────────────────────── */
    public function poll(): void
    {
        header('Content-Type: application/json');
        $lastId = (int)($_GET['last_id'] ?? 0);
        $convId = (int)($_GET['conv_id'] ?? 0);

        $this->touchPresence();

        $sql    = "SELECT m.id, m.conversation_id, m.sender_id, m.type,
                          m.content, m.file_path, m.file_name, m.file_mime,
                          m.bilan_id, m.ref_id, m.is_deleted, m.is_edited, m.edited_at, m.created_at,
                          u.nom AS sender_nom, u.prenom AS sender_prenom
                   FROM chat_messages m
                   JOIN users u ON u.id = m.sender_id
                   JOIN chat_participants cp ON cp.conversation_id = m.conversation_id AND cp.user_id = ?
                   WHERE m.id > ?";
        $params = [$this->userId, $lastId];
        if ($convId > 0) { $sql .= " AND m.conversation_id = ?"; $params[] = $convId; }
        $sql .= " ORDER BY m.id ASC LIMIT 50";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $newMsgs = $stmt->fetchAll(PDO::FETCH_ASSOC);

        /* Marquer comme lu si on est dans une conv spécifique */
        if ($convId > 0 && !empty($newMsgs)) {
            $maxId = (int)max(array_column($newMsgs, 'id'));
            $this->db->prepare("UPDATE chat_participants
                                SET last_read_id = GREATEST(last_read_id, ?)
                                WHERE conversation_id = ? AND user_id = ?")
                     ->execute([$maxId, $convId, $this->userId]);
        }

        /* Total non lus (toutes convs) */
        $stmtUr = $this->db->prepare("
            SELECT COALESCE(SUM(sub.cnt), 0) AS total
            FROM (
                SELECT (SELECT COUNT(*) FROM chat_messages cm
                        WHERE cm.conversation_id = cp.conversation_id
                          AND cm.sender_id != :me
                          AND cm.is_deleted = 0
                          AND cm.id > cp.last_read_id) AS cnt
                FROM chat_participants cp
                WHERE cp.user_id = :me2
            ) sub
        ");
        $stmtUr->execute([':me' => $this->userId, ':me2' => $this->userId]);
        $totalUnread = (int)$stmtUr->fetchColumn();

        /* ── Signaux d'appel entrant (callee) ── */
        $callSignal = null;
        try {
            // Appel entrant non encore traité
            $stmtRing = $this->db->prepare("
                SELECT cc.id, cc.conversation_id, cc.caller_id, cc.call_type, cc.status, cc.sdp_offer,
                       u.nom AS caller_nom, u.prenom AS caller_prenom
                FROM chat_calls cc
                JOIN users u ON u.id = cc.caller_id
                WHERE cc.callee_id = ? AND cc.status = 'ringing'
                  AND cc.created_at >= DATE_SUB(NOW(), INTERVAL 60 SECOND)
                ORDER BY cc.id DESC LIMIT 1
            ");
            $stmtRing->execute([$this->userId]);
            $ring = $stmtRing->fetch(PDO::FETCH_ASSOC);
            if ($ring) $callSignal = ['event' => 'incoming', 'call' => $ring];

            // Réponse à notre appel sortant (caller)
            if (!$callSignal) {
                $stmtAns = $this->db->prepare("
                    SELECT id, status, sdp_answer FROM chat_calls
                    WHERE caller_id = ? AND status IN ('accepted','rejected','ended','missed')
                      AND updated_at >= DATE_SUB(NOW(), INTERVAL 10 SECOND)
                    ORDER BY updated_at DESC LIMIT 1
                ");
                $stmtAns->execute([$this->userId]);
                $ans = $stmtAns->fetch(PDO::FETCH_ASSOC);
                if ($ans) $callSignal = ['event' => $ans['status'], 'call' => $ans];
            }
        } catch (\Throwable $e) { /* non bloquant */ }

        echo json_encode([
            'success'      => true,
            'messages'     => $newMsgs,
            'total_unread' => $totalUnread,
            'call_signal'  => $callSignal,
            'ts'           => time(),
        ]);
        exit;
    }

    /* ══════════════════════════════════════════════════════════════════
       APPELS VIDÉO / AUDIO — Signalisation WebRTC via polling
    ══════════════════════════════════════════════════════════════════ */
    public function callSignal(): void
    {
        header('Content-Type: application/json');
        try {
        $raw    = file_get_contents('php://input');
        $data   = ($raw ? json_decode($raw, true) : null) ?: $_POST;
        $action = trim($data['action'] ?? '');

        switch ($action) {

            /* ── Initiateur : envoyer l'offre SDP ── */
            case 'offer':
                $convId   = (int)($data['conv_id']   ?? 0);
                $calleeId = (int)($data['callee_id'] ?? 0);
                $callType = in_array($data['call_type'] ?? '', ['audio','video'])
                            ? $data['call_type'] : 'audio';
                $sdpOffer = $data['sdp_offer'] ?? '';
                if (!$convId || !$calleeId || !$sdpOffer) {
                    echo json_encode(['success'=>false,'error'=>'Données manquantes']); exit;
                }
                // Expire les appels fantômes
                $this->db->prepare("UPDATE chat_calls SET status='missed'
                    WHERE status='ringing' AND created_at < DATE_SUB(NOW(), INTERVAL 60 SECOND)")
                    ->execute();
                // Créer l'appel
                $this->db->prepare("INSERT INTO chat_calls
                    (conversation_id,caller_id,callee_id,call_type,sdp_offer,status)
                    VALUES (?,?,?,?,?,'ringing')")
                    ->execute([$convId, $this->userId, $calleeId, $callType, $sdpOffer]);
                echo json_encode(['success'=>true,'call_id'=>(int)$this->db->lastInsertId()]);
                break;

            /* ── Récepteur : envoyer la réponse SDP ── */
            case 'answer':
                $callId    = (int)($data['call_id']    ?? 0);
                $sdpAnswer = $data['sdp_answer'] ?? '';
                if (!$callId || !$sdpAnswer) { echo json_encode(['success'=>false]); exit; }
                $this->db->prepare("UPDATE chat_calls
                    SET sdp_answer=?, status='accepted', updated_at=NOW()
                    WHERE id=? AND callee_id=? AND status='ringing'")
                    ->execute([$sdpAnswer, $callId, $this->userId]);
                echo json_encode(['success'=>true]);
                break;

            /* ── Envoi d'un candidat ICE ── */
            case 'ice':
                $callId    = (int)($data['call_id'] ?? 0);
                $candidate = $data['candidate'] ?? '';
                if (!$callId || !$candidate) { echo json_encode(['success'=>false]); exit; }
                $this->db->prepare("INSERT INTO chat_call_ice (call_id,sender_id,candidate) VALUES (?,?,?)")
                    ->execute([$callId, $this->userId, $candidate]);
                echo json_encode(['success'=>true]);
                break;

            /* ── Refus d'appel ── */
            case 'reject':
                $callId = (int)($data['call_id'] ?? 0);
                $this->db->prepare("UPDATE chat_calls SET status='rejected', updated_at=NOW()
                    WHERE id=? AND callee_id=? AND status='ringing'")
                    ->execute([$callId, $this->userId]);
                echo json_encode(['success'=>true]);
                break;

            /* ── Raccrocher ── */
            case 'end':
                $callId = (int)($data['call_id'] ?? 0);
                $this->db->prepare("UPDATE chat_calls SET status='ended', updated_at=NOW()
                    WHERE id=? AND (caller_id=? OR callee_id=?)")
                    ->execute([$callId, $this->userId, $this->userId]);
                echo json_encode(['success'=>true]);
                break;

            /* ── Poll de signalisation pendant un appel (ICE + statut) ── */
            case 'poll_call':
                $callId    = (int)($data['call_id']    ?? 0);
                $lastIceId = (int)($data['last_ice_id'] ?? 0);
                if (!$callId) { echo json_encode(['success'=>false]); exit; }

                $stmtC = $this->db->prepare("SELECT id,status,sdp_answer FROM chat_calls
                    WHERE id=? AND (caller_id=? OR callee_id=?) LIMIT 1");
                $stmtC->execute([$callId, $this->userId, $this->userId]);
                $call = $stmtC->fetch(PDO::FETCH_ASSOC);

                $stmtI = $this->db->prepare("SELECT id, candidate FROM chat_call_ice
                    WHERE call_id=? AND sender_id != ? AND id > ?
                    ORDER BY id ASC LIMIT 30");
                $stmtI->execute([$callId, $this->userId, $lastIceId]);
                $ice = $stmtI->fetchAll(PDO::FETCH_ASSOC);

                echo json_encode(['success'=>true, 'call'=>$call, 'ice'=>$ice]);
                break;

            default:
                echo json_encode(['success'=>false,'error'=>'Action inconnue']);
        }
        } catch (\Throwable $e) {
            error_log('[callSignal] ' . $e->getMessage());
            echo json_encode(['success'=>false,'error'=>$e->getMessage()]);
        }
        exit;
    }

    /* ── Envoyer un message texte / bilan ────────────────────────────────── */
    public function send(): void
    {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['success' => false]); exit; }

        $raw    = file_get_contents('php://input');
        $input  = json_decode($raw, true) ?? $_POST;
        $convId  = (int)($input['conv_id']  ?? 0);
        $content = trim($input['content']   ?? '');
        $typesValides = ['text','bilan','ordonnance','imagerie','patient'];
        $type    = in_array($input['type'] ?? 'text', $typesValides) ? $input['type'] : 'text';
        $bilanId = ($type === 'bilan')      ? (int)($input['bilan_id'] ?? 0) : null;
        $refId   = ($type !== 'text' && $type !== 'bilan') ? (int)($input['ref_id'] ?? 0) : null;

        if (!$convId || ($type === 'text' && $content === '')) {
            echo json_encode(['success' => false, 'error' => 'Données invalides']); exit;
        }
        if (!$this->checkParticipant($convId)) {
            echo json_encode(['success' => false, 'error' => 'Accès refusé']); exit;
        }

        $this->db->prepare("INSERT INTO chat_messages
            (conversation_id, sender_id, type, content, bilan_id, ref_id) VALUES (?,?,?,?,?,?)")
            ->execute([$convId, $this->userId, $type, $content ?: null, $bilanId, $refId]);
        $msgId = (int)$this->db->lastInsertId();

        $this->db->prepare("UPDATE chat_conversations SET updated_at = NOW() WHERE id = ?")
                 ->execute([$convId]);

        echo json_encode(['success' => true, 'message' => $this->fetchMessage($msgId)]);
        exit;
    }

    /* ── Upload image / fichier ──────────────────────────────────────────── */
    public function upload(): void
    {
        header('Content-Type: application/json');
        $convId = (int)($_POST['conv_id'] ?? 0);
        if (!$convId || !$this->checkParticipant($convId)) {
            echo json_encode(['success' => false, 'error' => 'Accès refusé']); exit;
        }
        if (empty($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(['success' => false, 'error' => 'Fichier invalide']); exit;
        }

        $file    = $_FILES['file'];
        $maxSize = 10 * 1024 * 1024; // 10 Mo
        if ($file['size'] > $maxSize) {
            echo json_encode(['success' => false, 'error' => 'Fichier trop volumineux (max 10 Mo)']); exit;
        }

        $mime    = mime_content_type($file['tmp_name']);
        $isImage = str_starts_with($mime, 'image/');
        $type    = $isImage ? 'image' : 'file';
        $ext     = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg','jpeg','png','gif','webp','bmp','pdf','doc','docx','xls','xlsx','txt','zip','csv'];

        if (!in_array($ext, $allowed)) {
            echo json_encode(['success' => false, 'error' => 'Type de fichier non autorisé']); exit;
        }

        $safeName = date('Ymd_His') . '_' . uniqid() . '.' . $ext;
        if (!move_uploaded_file($file['tmp_name'], $this->uploadDir . $safeName)) {
            echo json_encode(['success' => false, 'error' => 'Erreur d\'enregistrement']); exit;
        }

        $this->db->prepare("INSERT INTO chat_messages
            (conversation_id, sender_id, type, content, file_path, file_name, file_mime)
            VALUES (?,?,?,?,?,?,?)")
            ->execute([
                $convId, $this->userId, $type,
                trim($_POST['caption'] ?? '') ?: null,
                $safeName, $file['name'], $mime,
            ]);
        $msgId = (int)$this->db->lastInsertId();
        $this->db->prepare("UPDATE chat_conversations SET updated_at = NOW() WHERE id = ?")
                 ->execute([$convId]);

        echo json_encode(['success' => true, 'message' => $this->fetchMessage($msgId)]);
        exit;
    }

    /* ── Servir un fichier (protégé) ─────────────────────────────────────── */
    public function file(): void
    {
        $filename = basename($_GET['f'] ?? '');
        if (!$filename) { http_response_code(400); exit; }

        $stmt = $this->db->prepare("
            SELECT m.file_name, m.file_mime
            FROM chat_messages m
            JOIN chat_participants cp ON cp.conversation_id = m.conversation_id AND cp.user_id = ?
            WHERE m.file_path = ? AND m.is_deleted = 0
            LIMIT 1
        ");
        $stmt->execute([$this->userId, $filename]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) { http_response_code(403); exit; }

        $path = $this->uploadDir . $filename;
        if (!file_exists($path)) { http_response_code(404); exit; }

        header('Content-Type: ' . $row['file_mime']);
        header('Content-Disposition: inline; filename="' . rawurlencode($row['file_name']) . '"');
        header('Content-Length: ' . filesize($path));
        header('Cache-Control: private, max-age=3600');
        readfile($path);
        exit;
    }

    /* ── Bilans partageables ─────────────────────────────────────────────── */
    public function bilans(): void
    {
        header('Content-Type: application/json');
        $patientId = (int)($_GET['patient_id'] ?? 0);
        $sql    = "SELECT d.id, d.date_creation, d.statut,
                          p.nom AS patient_nom, p.prenom AS patient_prenom, p.dossier_numero,
                          GROUP_CONCAT(el.nom ORDER BY el.nom SEPARATOR ', ') AS examens
                   FROM demandes_laboratoire d
                   JOIN patients p ON p.id = d.patient_id
                   JOIN demande_examens de ON de.demande_id = d.id
                   JOIN examens_laboratoire el ON el.id = de.examen_id
                   WHERE d.medecin_id = ?";
        $params = [$this->userId];
        if ($patientId) { $sql .= " AND d.patient_id = ?"; $params[] = $patientId; }
        $sql .= " GROUP BY d.id ORDER BY d.date_creation DESC LIMIT 30";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        exit;
    }

    /* ── Statut en temps réel d'une liste de bilans ────────────────────────
     * GET chat/bilan-statut?ids=1,2,3
     * Retourne le statut actuel et si les résultats sont disponibles
     * ──────────────────────────────────────────────────────────────────── */
    public function bilanStatut(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        $raw = trim($_GET['ids'] ?? '');
        if (!$raw) { echo json_encode([]); exit; }

        // Valider : liste d'entiers séparés par virgules
        $ids = array_filter(array_map('intval', explode(',', $raw)));
        if (empty($ids)) { echo json_encode([]); exit; }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));

        try {
            $stmt = $this->db->prepare("
                SELECT d.id, d.statut,
                       COUNT(re.id) AS nb_resultats,
                       COUNT(de.id) AS nb_examens
                FROM demandes_laboratoire d
                LEFT JOIN demande_examens de ON de.demande_id = d.id
                LEFT JOIN resultats_examens re ON re.examen_id = de.id
                WHERE d.id IN ($placeholders)
                GROUP BY d.id
            ");
            $stmt->execute($ids);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $out = [];
            foreach ($rows as $r) {
                $statut = strtoupper($r['statut'] ?? '');
                $hasResults = (int)$r['nb_resultats'] > 0;
                $allDone    = $hasResults && ((int)$r['nb_resultats'] >= (int)$r['nb_examens']);
                $out[(int)$r['id']] = [
                    'statut'        => $r['statut'],
                    'disponible'    => $allDone || in_array($statut, ['TERMINE','TERMINÉ','DONE','VALIDATED']),
                    'partiel'       => $hasResults && !$allDone,
                    'nb_resultats'  => (int)$r['nb_resultats'],
                    'nb_examens'    => (int)$r['nb_examens'],
                ];
            }
            echo json_encode($out);
        } catch (\Throwable $e) {
            echo json_encode([]);
        }
        exit;
    }

    /* ── Supprimer un message ────────────────────────────────────────────── */
    public function deleteMsg(): void
    {
        header('Content-Type: application/json');
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $msgId = (int)($input['msg_id'] ?? 0);
        if (!$msgId) { echo json_encode(['success' => false]); exit; }

        $stmt = $this->db->prepare("UPDATE chat_messages SET is_deleted = 1
                                    WHERE id = ? AND sender_id = ?");
        $stmt->execute([$msgId, $this->userId]);
        echo json_encode(['success' => $stmt->rowCount() > 0]);
        exit;
    }

    /* ── Modifier un message ─────────────────────────────────────────────── */
    public function editMsg(): void
    {
        header('Content-Type: application/json');
        $input   = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $msgId   = (int)($input['msg_id']  ?? 0);
        $content = trim($input['content']  ?? '');
        if (!$msgId || !$content) { echo json_encode(['success' => false]); exit; }

        $stmt = $this->db->prepare("UPDATE chat_messages
                                    SET content = ?, is_edited = 1, edited_at = NOW()
                                    WHERE id = ? AND sender_id = ? AND type = 'text' AND is_deleted = 0");
        $stmt->execute([$content, $msgId, $this->userId]);
        echo json_encode(['success' => $stmt->rowCount() > 0]);
        exit;
    }

    /* ── Marquer conversation lue ────────────────────────────────────────── */
    public function markRead(): void
    {
        header('Content-Type: application/json');
        $convId = (int)($_POST['conv_id'] ?? 0);
        if ($convId) {
            $this->db->prepare("UPDATE chat_participants
                                SET last_read_id = (
                                    SELECT COALESCE(MAX(id), 0)
                                    FROM chat_messages WHERE conversation_id = ?
                                )
                                WHERE conversation_id = ? AND user_id = ?")
                     ->execute([$convId, $convId, $this->userId]);
        }
        echo json_encode(['success' => true]);
        exit;
    }

    /* ── Helpers ─────────────────────────────────────────────────────────── */
    private function checkParticipant(int $convId): bool
    {
        if (!$convId) return false;
        $stmt = $this->db->prepare("SELECT 1 FROM chat_participants WHERE conversation_id = ? AND user_id = ?");
        $stmt->execute([$convId, $this->userId]);
        return (bool)$stmt->fetchColumn();
    }

    /* ── Ordonnances récentes du médecin connecté ───────────────────────────
     * GET chat/ordonnances
     * ──────────────────────────────────────────────────────────────────── */
    public function ordonnances(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $stmt = $this->db->prepare("
                SELECT op.id, op.date_creation, op.statut, op.type_ordonnance,
                       p.nom AS patient_nom, p.prenom AS patient_prenom, p.dossier_numero,
                       GROUP_CONCAT(COALESCE(om.nom_medicament, m.nom, 'Médicament') ORDER BY om.id SEPARATOR ', ') AS medicaments
                FROM ordonnances_pharmacie op
                JOIN patients p ON p.id = op.patient_id
                LEFT JOIN ordonnance_medicaments om ON om.ordonnance_id = op.id
                LEFT JOIN medicaments m ON m.id = om.medicament_id
                WHERE op.medecin_id = ?
                GROUP BY op.id
                ORDER BY op.date_creation DESC
                LIMIT 20
            ");
            $stmt->execute([$this->userId]);
            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        } catch (\Throwable $e) { echo json_encode([]); }
        exit;
    }

    /* ── Imageries récentes du médecin connecté ─────────────────────────────
     * GET chat/imageries
     * ──────────────────────────────────────────────────────────────────── */
    public function imageries(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $stmt = $this->db->prepare("
                SELECT di.id, di.date_creation, di.statut,
                       CONCAT(UPPER(di.type_examen), ' — ', di.partie_corps) AS examen,
                       di.type_examen, di.partie_corps, di.urgence,
                       p.nom AS patient_nom, p.prenom AS patient_prenom, p.dossier_numero
                FROM demandes_imagerie di
                JOIN patients p ON p.id = di.patient_id
                WHERE di.medecin_id = ?
                ORDER BY di.date_creation DESC
                LIMIT 20
            ");
            $stmt->execute([$this->userId]);
            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        } catch (\Throwable $e) { echo json_encode([]); }
        exit;
    }

    /* ── Patients récents du médecin connecté ───────────────────────────────
     * GET chat/patients-recents
     * ──────────────────────────────────────────────────────────────────── */
    public function patientsRecents(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $stmt = $this->db->prepare("
                SELECT DISTINCT p.id, p.nom, p.prenom, p.dossier_numero,
                       p.date_naissance, p.groupe_sanguin,
                       p.statut_parcours, p.statut,
                       MAX(c.date_consultation) AS derniere_consultation
                FROM patients p
                LEFT JOIN consultations c ON c.patient_id = p.id AND c.medecin_id = ?
                WHERE (c.medecin_id = ? OR p.medecin_id = ?) AND p.actif = 1
                GROUP BY p.id
                ORDER BY derniere_consultation DESC, p.id DESC
                LIMIT 20
            ");
            $stmt->execute([$this->userId, $this->userId, $this->userId]);
            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        } catch (\Throwable $e) { echo json_encode([]); }
        exit;
    }

    private function fetchMessage(int $msgId): array
    {
        $stmt = $this->db->prepare("
            SELECT m.*, u.nom AS sender_nom, u.prenom AS sender_prenom
            FROM chat_messages m
            JOIN users u ON u.id = m.sender_id
            WHERE m.id = ?
        ");
        $stmt->execute([$msgId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    }

    private function touchPresence(): void
    {
        $this->db->prepare("INSERT INTO user_presence (user_id, last_seen) VALUES (?, NOW())
                            ON DUPLICATE KEY UPDATE last_seen = NOW()")
                 ->execute([$this->userId]);
    }
}
