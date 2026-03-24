<?php
class PrintService {
    
    public function generateBarcode($data, $type = 'CODE128') {
        // Génération simple de code-barres en SVG
        $width = 2;
        $height = 50;
        $bars = $this->encodeData($data);
        
        $svg = '<svg width="' . (count($bars) * $width) . '" height="' . $height . '" xmlns="http://www.w3.org/2000/svg">';
        
        $x = 0;
        foreach ($bars as $bar) {
            if ($bar == 1) {
                $svg .= '<rect x="' . $x . '" y="0" width="' . $width . '" height="' . $height . '" fill="black"/>';
            }
            $x += $width;
        }
        
        $svg .= '<text x="' . (count($bars) * $width / 2) . '" y="' . ($height + 15) . '" text-anchor="middle" font-size="12">' . $data . '</text>';
        $svg .= '</svg>';
        
        return $svg;
    }
    
    public function generateQRCode($data) {
        // Génération simple de QR code
        $size = 200;
        $url = "https://api.qrserver.com/v1/create-qr-code/?size={$size}x{$size}&data=" . urlencode($data);
        return '<img src="' . $url . '" alt="QR Code" width="' . $size . '" height="' . $size . '">';
    }
    
    public function printPatientCard($patient) {
        $barcode = $this->generateBarcode($patient['dossier_numero']);
        $qrData = json_encode([
            'id' => $patient['id'],
            'nom' => $patient['nom'],
            'prenom' => $patient['prenom'],
            'dossier' => $patient['dossier_numero']
        ]);
        $qrcode = $this->generateQRCode($qrData);
        
        return "
        <div class='patient-card-print'>
            <h2>DME Hospital - Carte Patient</h2>
            <div class='patient-info'>
                <h3>{$patient['nom']} {$patient['prenom']}</h3>
                <p>Dossier: {$patient['dossier_numero']}</p>
                <p>Né(e) le: {$patient['date_naissance']}</p>
            </div>
            <div class='codes'>
                <div class='barcode'>{$barcode}</div>
                <div class='qrcode'>{$qrcode}</div>
            </div>
        </div>";
    }
    
    public function printOrdonnance($consultation, $patient) {
        $barcode = $this->generateBarcode('ORD-' . $consultation['id']);
        $qrData = "Ordonnance #{$consultation['id']} - {$patient['nom']} {$patient['prenom']}";
        $qrcode = $this->generateQRCode($qrData);
        
        return "
        <div class='ordonnance-print'>
            <header>
                <h1>DME Hospital</h1>
                <div class='codes-header'>
                    <div class='barcode-small'>{$barcode}</div>
                    <div class='qr-small'>{$qrcode}</div>
                </div>
            </header>
            <div class='patient-details'>
                <h3>Patient: {$patient['nom']} {$patient['prenom']}</h3>
                <p>Dossier: {$patient['dossier_numero']}</p>
                <p>Date: " . date('d/m/Y') . "</p>
            </div>
            <div class='prescription'>
                <h4>Prescription:</h4>
                <div class='prescription-content'>{$consultation['prescription']}</div>
            </div>
        </div>";
    }
    
    private function encodeData($data) {
        // Encodage simple pour code-barres (simulation)
        $bars = [];
        for ($i = 0; $i < strlen($data); $i++) {
            $char = ord($data[$i]);
            for ($j = 0; $j < 8; $j++) {
                $bars[] = ($char >> $j) & 1;
            }
        }
        return $bars;
    }
}
?>