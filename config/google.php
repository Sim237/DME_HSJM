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

return [
    'google' => [
        'client_id' => 'YOUR_GOOGLE_CLIENT_ID',
        'client_secret' => 'YOUR_GOOGLE_CLIENT_SECRET',
        'redirect_uri' => 'http://localhost/dme_hospital/auth/google/callback',
        'scopes' => [
            'https://www.googleapis.com/auth/calendar',
            'https://www.googleapis.com/auth/calendar.events'
        ]
    ]
];
?>