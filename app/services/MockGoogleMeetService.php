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

class MockGoogleMeetService {
    public function createMeeting($data) {
        // Simulation d'une réunion Google Meet pour les tests
        $meetingId = 'meet-' . uniqid();
        $meetingUrl = "https://meet.google.com/{$meetingId}";
        
        // Log de la réunion simulée
        error_log("Réunion simulée créée: " . json_encode([
            'url' => $meetingUrl,
            'patient' => $data['summary'],
            'date' => $data['start'],
            'duration' => $data['duration']
        ]));
        
        return $meetingUrl;
    }
    
    public function getAuthUrl() {
        return '#'; // Pas d'authentification nécessaire en mode test
    }
}
?>