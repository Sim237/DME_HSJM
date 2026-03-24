<?php

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
