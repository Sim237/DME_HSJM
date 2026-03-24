<?php
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