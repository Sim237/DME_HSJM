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


// CONFIGURATION PERFORMANCE

// Cache Redis
define('CACHE_ENABLED', true);
define('CACHE_TTL', 3600); // 1 heure

// CDN Configuration
define('CDN_ENABLED', false);
define('CDN_URL', 'https://cdn.example.com');

// Pagination
define('DEFAULT_PER_PAGE', 20);
define('MAX_PER_PAGE', 100);

// Compression
define('COMPRESSION_ENABLED', false);
define('COMPRESSION_LEVEL', 6);

// Images
define('IMAGE_CACHE_ENABLED', true);
define('IMAGE_MAX_WIDTH', 1920);
define('IMAGE_QUALITY', 85);
