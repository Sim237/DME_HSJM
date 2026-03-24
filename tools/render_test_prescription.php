<?php
// Script de test pour rendre une ordonnance sans passer par la base de données
chdir(__DIR__ . '/..'); // positionner le cwd sur la racine du projet
if (!defined('BASE_URL')) define('BASE_URL', '/dme_hospital/');
$config = ['app_name' => 'DME Hospital - Test'];

// Prescription factice
$prescription = [
    'id' => 1,
    'date_prescription' => date('Y-m-d H:i:s'),
    'medecin_nom' => 'House',
    'medecin_prenom' => 'Gregory',
    'specialite' => 'Médecine Interne',
    'nom' => 'MEBARA',
    'prenom' => 'Jean',
    'patient_nom' => 'MEBARA',
    'patient_prenom' => 'Jean',
    'dossier_numero' => 'P-2023-00002',
    'date_naissance' => '1990-11-07',
    'sexe' => 'M',
    'recommandations' => "Prendre beaucoup d'eau. Repos."
];

// Médicaments factices
$medicaments = [
    [
        'nom_medicament' => 'Amoxicilline',
        'medicament_nom' => 'Amoxicilline',
        'forme' => 'Gélule',
        'dosage' => '1g',
        'posologie' => '1 g toutes les 8 heures',
        'duree' => '7 jours',
        'frequence' => '3/jour',
        'observations' => ''
    ],
    [
        'nom_medicament' => 'Paracétamol',
        'medicament_nom' => 'Paracétamol',
        'forme' => 'Gélule',
        'dosage' => '50mg',
        'posologie' => '1 comprimé si douleur',
        'duree' => '',
        'frequence' => '',
        'observations' => ''
    ],
    [
        'nom_medicament' => 'Ceftriaxone',
        'medicament_nom' => 'Ceftriaxone',
        'forme' => 'Injectable',
        'dosage' => '40mg',
        'posologie' => 'IM une fois par jour',
        'duree' => '3 jours',
        'frequence' => '1/jour',
        'observations' => ''
    ]
];

// Rendu
ob_start();
require 'app/views/prescriptions/impression.php';
$html = ob_get_clean();

$outDir = 'backups';
if (!is_dir($outDir)) mkdir($outDir, 0755, true);
$outFile = $outDir . '/test_prescription_output.html';
file_put_contents($outFile, $html);

echo "Rendered output saved to: $outFile\n";
