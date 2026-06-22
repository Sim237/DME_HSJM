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
 * NotificationCenterController — Endpoints AJAX pour le composant cloche universel.
 *
 * Routes :
 *   GET  notifications/poll              → JSON {count, items[]}
 *   POST notifications/mark-read/{id}    → JSON {success}
 *   POST notifications/mark-all-read     → JSON {success, count}
 *   GET  notifications/all               → vue HTML "toutes les notifications"
 */
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../services/NotificationCenter.php';

class NotificationCenterController
{
    private NotificationCenter $center;

    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $this->center = new NotificationCenter();
    }

    /**
     * Polling : retourne le compteur + les N dernières non lues.
     * Appelé toutes les 20-30s par le composant cloche.
     */
    public function poll(): void {
        header('Content-Type: application/json; charset=utf-8');
        $userId = (int)($_SESSION['user_id'] ?? 0);
        if (!$userId) { echo json_encode(['count' => 0, 'items' => []]); return; }

        try {
            $items = $this->center->getUnreadForUser($userId, 20);
            $count = $this->center->countUnread($userId);

            // Ajouter un timestamp ISO 8601 + un libellé "il y a X minutes"
            foreach ($items as &$it) {
                $ts = strtotime($it['created_at']);
                $diff = time() - $ts;
                if      ($diff < 60)    $it['relative'] = "à l'instant";
                elseif ($diff < 3600)  $it['relative'] = floor($diff / 60) . ' min';
                elseif ($diff < 86400) $it['relative'] = floor($diff / 3600) . ' h';
                else                    $it['relative'] = floor($diff / 86400) . ' j';
                $it['created_iso'] = date('c', $ts);
            }
            unset($it);

            echo json_encode([
                'count' => $count,
                'items' => $items,
                'ts'    => time(),
            ]);
        } catch (\Throwable $e) {
            error_log('[NotificationCenter] poll: ' . $e->getMessage());
            echo json_encode(['count' => 0, 'items' => [], 'error' => 'server_error']);
        }
    }

    public function markRead(int $id): void {
        header('Content-Type: application/json; charset=utf-8');
        $userId = (int)($_SESSION['user_id'] ?? 0);
        if (!$userId || $_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false]); return;
        }
        $ok = $this->center->markAsRead($id, $userId);
        echo json_encode(['success' => $ok]);
    }

    public function markAllRead(): void {
        header('Content-Type: application/json; charset=utf-8');
        $userId = (int)($_SESSION['user_id'] ?? 0);
        if (!$userId || $_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false]); return;
        }
        $n = $this->center->markAllAsRead($userId);
        echo json_encode(['success' => true, 'count' => $n]);
    }

    public function listAll(): void {
        $userId = (int)($_SESSION['user_id'] ?? 0);
        if (!$userId) { header('Location: ' . BASE_URL . 'login'); exit; }
        $notifications = $this->center->getRecent($userId, 100);
        require_once __DIR__ . '/../views/notifications/all.php';
    }
}
