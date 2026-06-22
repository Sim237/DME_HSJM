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
   FICHIER : app/views/partials/entete_hopital.php
   En-tête hôpital CENTRALISÉ pour tous les documents imprimables.
   Source unique : table `settings` (id=1), avec repli sur les valeurs réelles.

   Usage :
     require_once __DIR__ . '/../partials/entete_hopital.php';
     echo entete_hopital();                 // bandeau complet (logo + coordonnées)
     $h = hopital_infos();                  // tableau brut si besoin
   ============================================================================ */

if (!function_exists('hopital_infos')) {
    /** Retourne les infos hôpital (settings + repli). Mise en cache statique. */
    function hopital_infos(): array {
        static $cache = null;
        if ($cache !== null) return $cache;

        $row = [];
        try {
            require_once __DIR__ . '/../../../config/database.php';
            $db = (new Database())->getConnection();
            $row = $db->query("SELECT * FROM settings WHERE id = 1")->fetch(PDO::FETCH_ASSOC) ?: [];
        } catch (\Throwable $e) { $row = []; }

        // Valeurs réelles par défaut (si settings vide ou placeholder)
        $def = [
            'organisation' => 'ORDRE DE MALTE',
            'nom_hopital'  => 'HÔPITAL SAINT-JEAN DE MALTE',
            'bp'           => 'B.P. : 56 Njombé',
            'ville'        => 'NJOMBÉ',
            'telephone'    => '(237) 697 09 29 92',
            'email'        => '',
            'logo'         => 'logo_ordre_malte.png',
        ];
        $placeholders = ['DME Hospital', '+237 000 000 000', 'contact@dme-hospital.com'];
        $val = function($k) use ($row, $def, $placeholders) {
            $v = trim((string)($row[$k] ?? ''));
            if ($v === '' || in_array($v, $placeholders, true)) return $def[$k] ?? '';
            return $v;
        };

        $base = defined('BASE_URL') ? BASE_URL : '/dme_hospital/';
        $cache = [
            'organisation' => $val('organisation'),
            'nom_hopital'  => $val('nom_hopital'),
            'bp'           => $val('bp'),
            'ville'        => $val('ville'),
            'telephone'    => $val('telephone'),
            'email'        => $val('email'),
            'logo_url'     => $base . 'public/images/' . ($val('logo') ?: 'logo_ordre_malte.png'),
        ];
        return $cache;
    }
}

if (!function_exists('entete_hopital')) {
    /**
     * Affiche le bandeau d'en-tête hôpital.
     * @param array $opts  ['logo'=>bool, 'compact'=>bool, 'align'=>'left'|'center']
     */
    function entete_hopital(array $opts = []): string {
        $h = hopital_infos();
        $showLogo = $opts['logo']   ?? true;
        $compact  = $opts['compact'] ?? false;
        $e = fn($s) => htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8');

        $coords = $e($h['bp']);
        if ($h['ville'])     $coords .= ($coords ? ' — ' : '') . $e($h['ville']);
        if ($h['telephone']) $coords .= ' — Tél. : ' . $e($h['telephone']);
        if ($h['email'])     $coords .= ' — ' . $e($h['email']);

        ob_start(); ?>
        <div class="hopital-entete" style="display:flex;align-items:center;gap:14px;<?= $compact ? '' : 'border-bottom:2px solid #000;padding-bottom:8px;' ?>margin-bottom:<?= $compact ? '6px' : '12px' ?>;">
            <?php if ($showLogo): ?>
            <img src="<?= $e($h['logo_url']) ?>" alt="Logo" style="height:<?= $compact ? '42px' : '58px' ?>;width:auto;flex-shrink:0;"
                 onerror="this.style.display='none'">
            <?php endif; ?>
            <div style="line-height:1.3;">
                <?php if ($h['organisation']): ?><div style="font-weight:800;font-size:<?= $compact ? '.78rem' : '.9rem' ?>;letter-spacing:.5px;"><?= $e($h['organisation']) ?></div><?php endif; ?>
                <div style="font-weight:800;font-size:<?= $compact ? '.88rem' : '1.02rem' ?>;"><?= $e($h['nom_hopital']) ?></div>
                <div style="font-size:<?= $compact ? '.66rem' : '.74rem' ?>;color:#444;"><?= $coords ?></div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}
