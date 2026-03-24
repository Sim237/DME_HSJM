<?php
class FormulaireController {

    public function creer($type, $patient_id) {
    require_once __DIR__ . '/../models/Patient.php';
    $patientModel = new Patient();
    $patient = $patientModel->getById($patient_id);

    // Calcul de l'âge
    $age = date_diff(date_create($patient['date_naissance']), date_create('now'))->y;

    // Chemin de la vue
    $viewPath = __DIR__ . '/../views/formulaires/' . $type . '.php';

    if (file_exists($viewPath)) {
        require_once $viewPath;
    } else {
        echo "Interface non trouvée.";
    }
}


}