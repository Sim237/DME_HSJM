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

require_once __DIR__ . '/../models/Setting.php';

class SettingController {
    private $settingModel;

    public function __construct() {
        $this->settingModel = new Setting();
    }

    public function index() {
        $settings = $this->settingModel->get();
        require_once __DIR__ . '/../views/parametres/index.php';
    }

    public function save() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if ($this->settingModel->update($_POST)) {
                header('Location: ' . BASE_URL . 'parametres?success=1');
            } else {
                header('Location: ' . BASE_URL . 'parametres?error=1');
            }
        }
    }
}
?>