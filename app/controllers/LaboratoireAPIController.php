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


require_once __DIR__ . '/../services/LaboratoireAPI.php';

class LaboratoireAPIController {
    
    public function getResultats() {
        $patient_id = $_GET['patient_id'] ?? null;
        $resultats = LaboratoireAPI::getResultatsTempsReel($patient_id);
        echo json_encode($resultats);
    }
    
    public function getResultatsPatient($patient_id) {
        $resultats = LaboratoireAPI::getResultatsPatient($patient_id);
        echo json_encode($resultats);
    }
    
    public function ajouterResultat() {
        $response = LaboratoireAPI::ajouterResultat();
        echo json_encode($response);
    }
    
    public function getAlertes() {
        $alertes = LaboratoireAPI::getAlertesCritiques();
        echo json_encode($alertes);
    }
    
    public function getStatistiques() {
        $stats = LaboratoireAPI::getStatistiques();
        echo json_encode($stats);
    }
    
    public function streamResultats() {
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        header('Connection: keep-alive');
        
        // Boucle pour envoyer les données en temps réel
        while (true) {
            $resultats = LaboratoireAPI::getResultatsTempsReel();
            $alertes = LaboratoireAPI::getAlertesCritiques();
            
            $data = [
                'resultats' => $resultats,
                'alertes' => $alertes,
                'timestamp' => time()
            ];
            
            echo "data: " . json_encode($data) . "\n\n";
            
            if (connection_aborted()) break;
            
            sleep(5); // Actualisation toutes les 5 secondes
        }
    }
}
?>