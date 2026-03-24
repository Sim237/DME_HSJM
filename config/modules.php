<?php
// Configuration des liens entre modules
return [
    'modules' => [
        'patients' => [
            'model' => 'Patient',
            'controller' => 'PatientController',
            'links' => ['consultations', 'registres', 'hospitalisation']
        ],
        'consultations' => [
            'model' => 'Consultation', 
            'controller' => 'ConsultationController',
            'links' => ['patients', 'prescriptions', 'examens']
        ],
        'registres' => [
            'model' => 'Registre',
            'controller' => 'RegistreController', 
            'links' => ['patients']
        ],
        'pharmacie' => [
            'model' => 'Pharmacie',
            'controller' => 'PharmacieController',
            'links' => ['prescriptions', 'patients']
        ]
    ],
    
    'shared_data' => [
        'patient_info' => ['nom', 'prenom', 'dossier_numero', 'date_naissance', 'sexe'],
        'medical_info' => ['groupe_sanguin', 'allergies', 'antecedents_medicaux'],
        'contact_info' => ['telephone', 'email', 'adresse']
    ],
    
    'cross_module_actions' => [
        'view_patient_from_registre' => 'patients/dossier/{id}',
        'add_consultation_from_patient' => 'consultation?patient_id={id}',
        'view_prescriptions_from_patient' => 'pharmacie/patient/{id}'
    ]
];
?>