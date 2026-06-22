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

require_once __DIR__ . '/../services/CsrfService.php';
require_once __DIR__ . '/../services/PredictionStockService.php';

class PharmacieStocksController
{
    private PDO $db;
    private PredictionStockService $pred;

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (empty($_SESSION['user_id'])) {
            header('Location: ' . BASE_URL . 'auth/login');
            exit;
        }
        $database   = new Database();
        $this->db   = $database->getConnection();
        $this->pred = new PredictionStockService($this->db);
    }

    // ─────────────────────────────────────────────────────────────────
    //  DASHBOARD
    // ─────────────────────────────────────────────────────────────────
    public function dashboard(): void
    {
        $stats      = $this->pred->getDashboardStats();
        $topRisques = $this->pred->getTopRisques(25);
        $alertes    = $this->pred->getAlertesActives(50);
        $mouvements = $this->pred->getMouvementsRecents(7);

        require_once __DIR__ . '/../views/pharmacie/stocks_intelligents.php';
    }

    // ─────────────────────────────────────────────────────────────────
    //  AJAX : LANCER L'ANALYSE
    // ─────────────────────────────────────────────────────────────────
    public function runAnalyse(): void
    {
        ob_start();
        header('Content-Type: application/json; charset=utf-8');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            ob_clean();
            echo json_encode(['ok' => false, 'error' => 'POST requis']);
            exit;
        }
        if (!CsrfService::validate()) {
            http_response_code(403);
            ob_clean();
            echo json_encode(['ok' => false, 'error' => 'CSRF invalide']);
            exit;
        }

        try {
            $result = $this->pred->generateAlertes();
            ob_clean();
            echo json_encode($result);
        } catch (Exception $e) {
            error_log('[PharmacieStocksController::runAnalyse] ' . $e->getMessage());
            http_response_code(500);
            ob_clean();
            echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
        }
        exit;
    }

    // ─────────────────────────────────────────────────────────────────
    //  AJAX : ACQUITTER UNE ALERTE
    // ─────────────────────────────────────────────────────────────────
    public function acquitterAlerte(): void
    {
        ob_start();
        header('Content-Type: application/json; charset=utf-8');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            ob_clean(); echo json_encode(['ok' => false, 'error' => 'POST requis']); exit;
        }
        if (!CsrfService::validate()) {
            http_response_code(403);
            ob_clean(); echo json_encode(['ok' => false, 'error' => 'CSRF invalide']); exit;
        }

        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            ob_clean(); echo json_encode(['ok' => false, 'error' => 'ID invalide']); exit;
        }

        $ok = $this->pred->acquitterAlerte($id);
        ob_clean();
        echo json_encode(['ok' => $ok]);
        exit;
    }

    // ─────────────────────────────────────────────────────────────────
    //  AJAX : STATS RAFRAICHISSEMENT
    // ─────────────────────────────────────────────────────────────────
    public function getStatsJson(): void
    {
        ob_start();
        header('Content-Type: application/json; charset=utf-8');
        ob_clean();
        echo json_encode($this->pred->getDashboardStats());
        exit;
    }

    // ─────────────────────────────────────────────────────────────────
    //  EXPORT CSV BON DE COMMANDE
    // ─────────────────────────────────────────────────────────────────
    public function exportCommande(): void
    {
        $commande = $this->pred->getCommandeSuggestion();

        $filename = 'bon_commande_pharmacie_' . date('Ymd_Hi') . '.csv';
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $fp = fopen('php://output', 'w');
        fprintf($fp, chr(0xEF) . chr(0xBB) . chr(0xBF)); // UTF-8 BOM

        fputcsv($fp, [
            'Réf. SAGE', 'Nom médicament', 'Stock actuel', 'Seuil alerte',
            'Conso/j (30j)', 'Jours restants', 'Niveau risque',
            'Qté à commander', 'Prix unitaire (FCFA)', 'Montant estimé (FCFA)',
        ], ';');

        $totalMontant = 0;
        foreach ($commande as $row) {
            $prix    = (float)($row['prix_sage'] ?: $row['prix_unitaire'] ?: 0);
            $montant = $prix * (int)$row['quantite_commande'];
            $totalMontant += $montant;
            fputcsv($fp, [
                $row['ar_ref_sage'] ?? '',
                $row['nom'],
                $row['stock_actuel'],
                0,
                number_format((float)$row['consommation_j'], 2, ',', ''),
                $row['jours_restants'] !== null ? number_format((float)$row['jours_restants'], 1, ',', '') : 'N/A',
                $row['niveau'],
                $row['quantite_commande'],
                number_format($prix, 0, ',', ' '),
                number_format($montant, 0, ',', ' '),
            ], ';');
        }

        fputcsv($fp, [], ';');
        fputcsv($fp, ['', '', '', '', '', '', '', 'TOTAL ESTIMÉ', '', number_format($totalMontant, 0, ',', ' ')], ';');
        fclose($fp);
        exit;
    }
}
