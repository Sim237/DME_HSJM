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


class CompressionMiddleware {
    
    public static function enable() {
        if (!ob_start('ob_gzhandler')) {
            ob_start();
        }
        
        header('Content-Encoding: gzip');
        header('Vary: Accept-Encoding');
    }
    
    public static function compressOutput($buffer) {
        $buffer = preg_replace('/\s+/', ' ', $buffer);
        $buffer = preg_replace('/>\s+</', '><', $buffer);
        return $buffer;
    }
}
