<?php
require_once __DIR__ . '/../models/Patient.php';
require_once __DIR__ . '/../models/Registre.php';
require_once __DIR__ . '/../../config/database.php';

class DataService {
    private static $instance = null;
    private $patientModel;
    private $registreModel;
    private $db;
    
    private function __construct() {
        global $pdo;
        $this->db = $pdo;
        $this->patientModel = new Patient();
        $this->registreModel = new Registre();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->db;
    }
    
    // Données unifiées pour le dashboard
    public function getDashboardData() {
        return [
            'patients' => [
                'total' => $this->patientModel->count(),
                'hospitalises' => $this->patientModel->countHospitalises()
            ],
            'registres' => $this->registreModel->getStatistiques()
        ];
    }
    
    // Recherche globale unifiée
    public function globalSearch($query) {
        $results = [];
        
        // Recherche patients
        try {
            $patients = $this->patientModel->search($query);
            foreach ($patients as $patient) {
                $results[] = [
                    'type' => 'PATIENT',
                    'id' => $patient['id'],
                    'label' => $patient['nom'] . ' ' . $patient['prenom'],
                    'subtext' => $patient['dossier_numero']
                ];
            }
        } catch (Exception $e) {}
        
        return $results;
    }
    
    // Données patient complètes
    public function getPatientComplet($patient_id) {
        $patient = $this->patientModel->getById($patient_id);
        if (!$patient) return null;
        
        return [
            'patient' => $patient,
            'consultations' => $this->patientModel->getConsultations($patient_id, 10),
            'parametres' => $this->patientModel->getParametres($patient_id, 5)
        ];
    }
}
?>