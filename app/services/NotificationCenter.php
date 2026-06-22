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

/**
 * NotificationCenter — Service unifié de gestion des notifications utilisateurs.
 *
 * Architecture :
 *   - Utilise la table `notifications` (étendue avec category, link, meta, icon)
 *   - Permet la création ciblée (un utilisateur), par rôle, par service
 *   - Polling AJAX léger (le client interroge toutes les 20-30s)
 *
 * Usage :
 *   $center = new NotificationCenter();
 *   $center->notify($userId, [
 *       'category' => 'BILAN',
 *       'title'    => 'Résultat disponible',
 *       'message'  => 'Hémogramme du patient X est validé',
 *       'link'     => '/dme_hospital/laboratoire/imprimer/123',
 *       'priority' => 'high',
 *       'icon'     => 'bi-flask',
 *   ]);
 *
 *   $center->notifyByRole('PHARMACIEN', [
 *       'category' => 'PHARMACIE',
 *       'title'    => 'Nouvelle ordonnance',
 *       'message'  => '...',
 *   ]);
 *
 *   $center->notifyByService($serviceId, [...]);
 */
class NotificationCenter
{
    private PDO $db;

    /**
     * Catégories standardisées (utilisées pour le filtrage et le style)
     */
    public const CAT_CONSULTATION = 'CONSULTATION';
    public const CAT_SOIN         = 'SOIN';
    public const CAT_BILAN        = 'BILAN';        // résultat labo prêt
    public const CAT_LABO         = 'LABO';         // demande labo entrante
    public const CAT_IMAGERIE     = 'IMAGERIE';
    public const CAT_PHARMACIE    = 'PHARMACIE';
    public const CAT_RDV          = 'RDV';
    public const CAT_HOSPIT       = 'HOSPIT';
    public const CAT_TRANSFERT    = 'TRANSFERT';
    public const CAT_URGENCE      = 'URGENCE';
    public const CAT_INFO         = 'INFO';

    public function __construct(?PDO $db = null) {
        $this->db = $db ?? (new Database())->getConnection();
    }

    // ──────────────────────────────────────────────────────────────
    //  CRÉATION
    // ──────────────────────────────────────────────────────────────

    /**
     * Crée une notification pour UN utilisateur précis.
     *
     * Champs supportés dans $data :
     *   - category  (string, default 'INFO')
     *   - title     (string, requis)
     *   - message   (string, requis)
     *   - link      (string, optionnel — URL relative ex: 'laboratoire/...')
     *   - priority  (low|normal|high|urgent — default 'normal')
     *   - type      (info|warning|danger|success — default 'info')
     *   - icon      (classe Bootstrap Icons — ex 'bi-flask')
     *   - meta      (array — sera JSON-encodé)
     *
     * @return int|false ID de la notification créée, ou false en cas d'échec
     */
    public function notify(int $userId, array $data) {
        if (empty($data['title']) || empty($data['message'])) {
            return false;
        }

        // Détection des colonnes disponibles (rétrocompatibilité avant migration v2)
        $cols = $this->getNotifColumns();

        // Anti-doublon : si une notification IDENTIQUE (même user, même titre,
        // même link si dispo) existe déjà non-lue depuis moins de 10 minutes,
        // on ne la duplique pas.
        if (!empty($data['link']) && in_array('link', $cols, true)) {
            $stmtChk = $this->db->prepare(
                "SELECT id FROM notifications
                 WHERE user_id = ? AND title = ? AND link = ?
                   AND is_read = 0
                   AND created_at > NOW() - INTERVAL 10 MINUTE
                 LIMIT 1"
            );
            $stmtChk->execute([$userId, $data['title'], $data['link']]);
            $existing = $stmtChk->fetchColumn();
            if ($existing) return (int)$existing;
        } else {
            // Sans colonne link, on déduplique sur (user, title, message) seulement
            $stmtChk = $this->db->prepare(
                "SELECT id FROM notifications
                 WHERE user_id = ? AND title = ? AND message = ?
                   AND is_read = 0
                   AND created_at > NOW() - INTERVAL 10 MINUTE
                 LIMIT 1"
            );
            $stmtChk->execute([$userId, $data['title'], $data['message']]);
            $existing = $stmtChk->fetchColumn();
            if ($existing) return (int)$existing;
        }

        $fields = ['user_id', 'type', 'title', 'message'];
        $values = [
            $userId,
            $data['type']    ?? $this->typeFromPriority($data['priority'] ?? 'normal'),
            mb_substr($data['title'],   0, 250, 'UTF-8'),
            $data['message'],
        ];

        $optionals = [
            'priority' => $data['priority'] ?? 'normal',
            'category' => $data['category'] ?? self::CAT_INFO,
            'link'     => $data['link']     ?? null,
            'icon'     => $data['icon']     ?? $this->iconFromCategory($data['category'] ?? self::CAT_INFO),
            'meta'     => isset($data['meta']) ? json_encode($data['meta'], JSON_UNESCAPED_UNICODE) : null,
        ];
        foreach ($optionals as $col => $val) {
            if (in_array($col, $cols, true)) {
                $fields[] = $col;
                $values[] = $val;
            }
        }

        $placeholders = array_fill(0, count($fields), '?');
        $sql = "INSERT INTO notifications (" . implode(',', $fields) . ")
                VALUES (" . implode(',', $placeholders) . ")";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($values);
            return (int)$this->db->lastInsertId();
        } catch (\PDOException $e) {
            error_log('[NotificationCenter] notify() failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Notifie tous les utilisateurs ayant un rôle donné (ex: PHARMACIEN).
     *
     * @return int Nombre de notifications créées
     */
    public function notifyByRole(string $role, array $data): int {
        $stmt = $this->db->prepare(
            "SELECT id FROM users WHERE role = ? AND COALESCE(actif, 1) = 1"
        );
        $stmt->execute([$role]);
        $count = 0;
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if ($this->notify((int)$row['id'], $data)) $count++;
        }
        return $count;
    }

    /**
     * Notifie tous les utilisateurs d'un service donné, optionnellement filtrés par rôles.
     *
     * @param int      $serviceId
     * @param array    $data       Voir notify()
     * @param string[] $roles      Si fourni, ne notifie que les users avec un de ces rôles
     * @return int Nombre de notifications créées
     */
    public function notifyByService(int $serviceId, array $data, array $roles = []): int {
        $sql = "SELECT id FROM users WHERE service_id = ? AND COALESCE(actif, 1) = 1";
        $params = [$serviceId];
        if (!empty($roles)) {
            $placeholders = implode(',', array_fill(0, count($roles), '?'));
            $sql .= " AND role IN ($placeholders)";
            $params = array_merge($params, $roles);
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        $count = 0;
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if ($this->notify((int)$row['id'], $data)) $count++;
        }
        return $count;
    }

    // ──────────────────────────────────────────────────────────────
    //  LECTURE
    // ──────────────────────────────────────────────────────────────

    /**
     * Récupère les notifications non lues d'un utilisateur.
     */
    public function getUnreadForUser(int $userId, int $limit = 20): array {
        $cols = $this->getNotifColumns();
        $hasLink     = in_array('link',     $cols, true);
        $hasCategory = in_array('category', $cols, true);
        $hasIcon     = in_array('icon',     $cols, true);
        $hasMeta     = in_array('meta',     $cols, true);
        $hasPriority = in_array('priority', $cols, true);

        $select = "id, type, title, message, is_read, created_at";
        if ($hasCategory) $select .= ", category";
        if ($hasLink)     $select .= ", link";
        if ($hasIcon)     $select .= ", icon";
        if ($hasMeta)     $select .= ", meta";
        if ($hasPriority) $select .= ", priority";

        $stmt = $this->db->prepare(
            "SELECT $select FROM notifications
             WHERE user_id = ? AND is_read = 0
             ORDER BY " . ($hasPriority ? "FIELD(priority,'urgent','high','normal','low')," : "") . "
                      created_at DESC
             LIMIT $limit"
        );
        $stmt->execute([$userId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Décoder meta JSON si présent
        if ($hasMeta) {
            foreach ($rows as &$r) {
                if (!empty($r['meta'])) {
                    $decoded = json_decode($r['meta'], true);
                    $r['meta'] = is_array($decoded) ? $decoded : null;
                }
            }
            unset($r);
        }
        return $rows;
    }

    /** Compte les notifications non lues. */
    public function countUnread(int $userId): int {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0"
        );
        $stmt->execute([$userId]);
        return (int)$stmt->fetchColumn();
    }

    /** Récupère l'historique récent (lues + non lues) — pour affichage panel "Tout voir". */
    public function getRecent(int $userId, int $limit = 50): array {
        $cols = $this->getNotifColumns();
        $select = "id, type, title, message, is_read, created_at";
        foreach (['category','link','icon','meta','priority'] as $c) {
            if (in_array($c, $cols, true)) $select .= ", $c";
        }
        $stmt = $this->db->prepare(
            "SELECT $select FROM notifications
             WHERE user_id = ?
             ORDER BY created_at DESC
             LIMIT $limit"
        );
        $stmt->execute([$userId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (in_array('meta', $cols, true)) {
            foreach ($rows as &$r) {
                if (!empty($r['meta'])) {
                    $decoded = json_decode($r['meta'], true);
                    $r['meta'] = is_array($decoded) ? $decoded : null;
                }
            }
        }
        return $rows;
    }

    // ──────────────────────────────────────────────────────────────
    //  MARQUAGE COMME LU
    // ──────────────────────────────────────────────────────────────

    public function markAsRead(int $notifId, int $userId): bool {
        $stmt = $this->db->prepare(
            "UPDATE notifications SET is_read = 1
             WHERE id = ? AND user_id = ? AND is_read = 0"
        );
        $stmt->execute([$notifId, $userId]);
        return $stmt->rowCount() > 0;
    }

    public function markAllAsRead(int $userId): int {
        $stmt = $this->db->prepare(
            "UPDATE notifications SET is_read = 1
             WHERE user_id = ? AND is_read = 0"
        );
        $stmt->execute([$userId]);
        return $stmt->rowCount();
    }

    // ──────────────────────────────────────────────────────────────
    //  HELPERS
    // ──────────────────────────────────────────────────────────────

    private function getNotifColumns(): array {
        static $cache = null;
        if ($cache === null) {
            try {
                $cache = $this->db->query("SHOW COLUMNS FROM notifications")
                                  ->fetchAll(PDO::FETCH_COLUMN);
            } catch (\Throwable $e) {
                $cache = [];
            }
        }
        return $cache;
    }

    private function typeFromPriority(string $priority): string {
        return match (strtolower($priority)) {
            'urgent' => 'danger',
            'high'   => 'warning',
            'low'    => 'info',
            default  => 'info',
        };
    }

    /** Icône par défaut selon la catégorie */
    private function iconFromCategory(string $category): string {
        return match (strtoupper($category)) {
            'CONSULTATION' => 'bi-clipboard2-pulse',
            'SOIN'         => 'bi-heart-pulse',
            'BILAN'        => 'bi-file-earmark-medical',
            'LABO'         => 'bi-flask',
            'IMAGERIE'     => 'bi-camera-video',
            'PHARMACIE'    => 'bi-capsule',
            'RDV'          => 'bi-calendar-check',
            'HOSPIT'       => 'bi-hospital',
            'TRANSFERT'    => 'bi-arrow-left-right',
            'URGENCE'      => 'bi-exclamation-triangle-fill',
            default        => 'bi-bell',
        };
    }
}
