<?php
require_once __DIR__ . '/../services/PrintService.php';
require_once __DIR__ . '/../models/Patient.php';

class PrintController {
    private $printService;
    private $patientModel;
    
    public function __construct() {
        $this->printService = new PrintService();
        $this->patientModel = new Patient();
    }
    
    public function patientCard($patientId) {
        $patient = $this->patientModel->getById($patientId);
        
        if (!$patient) {
            http_response_code(404);
            echo "Patient non trouvé";
            return;
        }
        
        $html = $this->printService->printPatientCard($patient);
        
        echo "
        <!DOCTYPE html>
        <html>
        <head>
            <title>Carte Patient - {$patient['nom']} {$patient['prenom']}</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; }
                .patient-card-print { 
                    border: 2px solid #333; 
                    padding: 20px; 
                    width: 400px; 
                    margin: 0 auto;
                    border-radius: 10px;
                }
                .patient-info h3 { margin: 0; color: #2c3e50; }
                .codes { display: flex; justify-content: space-between; margin-top: 20px; }
                .barcode, .qrcode { text-align: center; }
                @media print {
                    body { margin: 0; }
                    .patient-card-print { border: 1px solid #000; }
                }
            </style>
        </head>
        <body>
            {$html}
            <script>window.print();</script>
        </body>
        </html>";
    }
    
    public function ordonnance($consultationId) {
        // Simulation - récupération consultation et patient
        $consultation = ['id' => $consultationId, 'prescription' => 'Paracétamol 1g x3/jour'];
        $patient = $this->patientModel->getById(1); // Simulation
        
        $html = $this->printService->printOrdonnance($consultation, $patient);
        
        echo "
        <!DOCTYPE html>
        <html>
        <head>
            <title>Ordonnance #{$consultationId}</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; }
                .ordonnance-print { max-width: 600px; margin: 0 auto; }
                header { display: flex; justify-content: space-between; border-bottom: 2px solid #333; padding-bottom: 10px; }
                .codes-header { display: flex; gap: 20px; }
                .barcode-small svg { width: 100px; height: 30px; }
                .qr-small img { width: 60px; height: 60px; }
                .prescription { margin-top: 30px; }
                .prescription-content { border: 1px solid #ddd; padding: 20px; min-height: 200px; }
                @media print {
                    body { margin: 0; }
                }
            </style>
        </head>
        <body>
            {$html}
            <script>window.print();</script>
        </body>
        </html>";
    }
    
    public function generateBarcode() {
        if (!isset($_GET['data'])) {
            echo "Données manquantes";
            return;
        }
        
        header('Content-Type: image/svg+xml');
        echo $this->printService->generateBarcode($_GET['data']);
    }
    
    public function printMedicalSummary($patientId) {
        $patient = $this->patientModel->getById($patientId);
        
        if (!$patient) {
            http_response_code(404);
            echo "Patient non trouvé";
            return;
        }
        
        $qrData = json_encode([
            'id' => $patient['id'],
            'nom' => $patient['nom'],
            'prenom' => $patient['prenom'],
            'dossier' => $patient['dossier_numero']
        ]);
        $qrcode = $this->printService->generateQRCode($qrData);
        $barcode = $this->printService->generateBarcode($patient['dossier_numero']);
        
        echo "
        <!DOCTYPE html>
        <html>
        <head>
            <title>Résumé Médical - {$patient['nom']} {$patient['prenom']}</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; }
                .header { display: flex; justify-content: space-between; border-bottom: 2px solid #333; padding-bottom: 10px; }
                .patient-info { margin: 20px 0; }
                .codes { display: flex; gap: 20px; margin: 20px 0; }
                .vital-signs { display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; margin: 20px 0; }
                .vital-box { border: 1px solid #ddd; padding: 10px; text-align: center; }
                @media print { body { margin: 0; } }
            </style>
        </head>
        <body>
            <div class='header'>
                <div>
                    <h1>DME Hospital</h1>
                    <h2>Résumé Médical</h2>
                </div>
                <div class='codes'>
                    <div>{$barcode}</div>
                    <div>{$qrcode}</div>
                </div>
            </div>
            
            <div class='patient-info'>
                <h3>{$patient['nom']} {$patient['prenom']}</h3>
                <p><strong>Dossier:</strong> {$patient['dossier_numero']}</p>
                <p><strong>Né(e) le:</strong> {$patient['date_naissance']}</p>
                <p><strong>Sexe:</strong> {$patient['sexe']}</p>
                <p><strong>Groupe sanguin:</strong> {$patient['groupe_sanguin']}</p>
            </div>
            
            <div class='vital-signs'>
                <div class='vital-box'>
                    <h4>Température</h4>
                    <p>-- °C</p>
                </div>
                <div class='vital-box'>
                    <h4>Tension</h4>
                    <p>--/-- mmHg</p>
                </div>
                <div class='vital-box'>
                    <h4>Pouls</h4>
                    <p>-- bpm</p>
                </div>
                <div class='vital-box'>
                    <h4>Poids</h4>
                    <p>-- kg</p>
                </div>
            </div>
            
            <div>
                <h4>Antécédents médicaux:</h4>
                <p>{$patient['antecedents_medicaux']}</p>
                
                <h4>Allergies:</h4>
                <p>{$patient['allergies']}</p>
            </div>
            
            <script>window.print();</script>
        </body>
        </html>";
    }
}
?>