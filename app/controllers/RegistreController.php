<?php
require_once __DIR__ . '/UnifiedController.php';
require_once __DIR__ . '/../models/Registre.php';

class RegistreController extends UnifiedController {
    private $registreModel;
    
    public function __construct() {
        parent::__construct();
        $this->registreModel = new Registre();
    }
    
    public function index() {
        $stats = $this->getUnifiedStats();
        require_once __DIR__ . '/../views/registres/index.php';
    }
    
    // Donneurs de sang
    public function donneursSang() {
        try {
            $donneurs = $this->registreModel->getDonneursSang();
        } catch (Exception $e) {
            $donneurs = [];
        }
        require_once __DIR__ . '/../views/registres/donneurs_sang.php';
    }
    
    public function banquesSang() {
        require_once __DIR__ . '/../models/BanqueSang.php';
        $banqueModel = new BanqueSang();
        $stock = $banqueModel->getStock();
        $alertes = $banqueModel->getAlertes();
        require_once __DIR__ . '/../views/registres/banques_sang.php';
    }
    
    public function donneursCsh() {
        require_once __DIR__ . '/../views/registres/donneurs_csh.php';
    }
    
    public function receveursCsh() {
        require_once __DIR__ . '/../views/registres/receveurs_csh.php';
    }
    
    public function cancers() {
        try {
            $patients = $this->registreModel->getMaladiesChroniques('CANCER');
        } catch (Exception $e) {
            $patients = [];
        }
        $type = 'Cancers';
        require_once __DIR__ . '/../views/registres/maladie_detail.php';
    }
    
    public function diabetiques() {
        try {
            $patients = $this->registreModel->getMaladiesChroniques('DIABETE');
        } catch (Exception $e) {
            $patients = [];
        }
        $type = 'Diabétiques';
        require_once __DIR__ . '/../views/registres/maladie_detail.php';
    }
    
    public function hypertendus() {
        try {
            $patients = $this->registreModel->getMaladiesChroniques('HYPERTENSION');
        } catch (Exception $e) {
            $patients = [];
        }
        $type = 'Hypertendus';
        require_once __DIR__ . '/../views/registres/maladie_detail.php';
    }
    
    public function ajouterDonneurSang() {
        if ($_POST) {
            $data = [
                ':nom' => strtoupper($_POST['nom']),
                ':prenom' => ucwords($_POST['prenom']),
                ':date_naissance' => $_POST['date_naissance'],
                ':sexe' => $_POST['sexe'],
                ':groupe_sanguin' => $_POST['groupe_sanguin'],
                ':rhesus' => $_POST['rhesus'],
                ':telephone' => $_POST['telephone'],
                ':email' => $_POST['email'] ?? null,
                ':adresse' => $_POST['adresse'] ?? null
            ];
            
            if ($this->registreModel->addDonneurSang($data)) {
                // Mise à jour automatique de la banque de sang
                require_once __DIR__ . '/../models/BanqueSang.php';
                $banqueModel = new BanqueSang();
                $banqueModel->ajouterSang($_POST['groupe_sanguin'], $_POST['rhesus']);
                
                header('Location: ' . BASE_URL . 'registres/donneurs-sang?success=1');
                exit;
            }
        }
        require_once __DIR__ . '/../views/registres/ajouter_donneur_sang.php';
    }
    
    public function ajouterDonneurCsh() {
        if ($_POST) {
            $data = [
                ':nom' => strtoupper($_POST['nom']),
                ':prenom' => ucwords($_POST['prenom']),
                ':date_naissance' => $_POST['date_naissance'],
                ':sexe' => $_POST['sexe'],
                ':hla_typing' => $_POST['hla_typing'] ?? null,
                ':telephone' => $_POST['telephone'],
                ':email' => $_POST['email'] ?? null,
                ':adresse' => $_POST['adresse'] ?? null
            ];
            
            if ($this->registreModel->addDonneurCSH($data)) {
                header('Location: ' . BASE_URL . 'registres/donneurs-csh?success=1');
                exit;
            }
        }
        require_once __DIR__ . '/../views/registres/ajouter_donneur_csh.php';
    }
    
    public function ajouterMaladieChronique() {
        if ($_POST) {
            $data = [
                ':patient_id' => $_POST['patient_id'],
                ':type_maladie' => $_POST['type_maladie'],
                ':date_diagnostic' => $_POST['date_diagnostic'],
                ':stade' => $_POST['stade'] ?? null,
                ':traitement_actuel' => $_POST['traitement_actuel'] ?? null,
                ':medecin_referent' => $_POST['medecin_referent'] ?? null
            ];
            
            if ($this->registreModel->addMaladieChronique($data)) {
                header('Location: ' . BASE_URL . 'registres?success=1');
                exit;
            }
        }
        
        // Récupérer la liste des patients
        require_once __DIR__ . '/../models/Patient.php';
        $patientModel = new Patient();
        $patients = $patientModel->getAll();
        
        require_once __DIR__ . '/../views/registres/ajouter_maladie_chronique.php';
    }
    
    public function ajouterReceveurCsh() {
        if ($_POST) {
            $data = [
                ':nom' => strtoupper($_POST['nom']),
                ':prenom' => ucwords($_POST['prenom']),
                ':date_naissance' => $_POST['date_naissance'],
                ':sexe' => $_POST['sexe'],
                ':hla_typing' => $_POST['hla_typing'] ?? null,
                ':pathologie' => $_POST['pathologie'],
                ':urgence' => $_POST['urgence'],
                ':telephone' => $_POST['telephone'],
                ':email' => $_POST['email'] ?? null
            ];
            
            if ($this->registreModel->addReceveurCSH($data)) {
                header('Location: ' . BASE_URL . 'registres/receveurs-csh?success=1');
                exit;
            }
        }
        require_once __DIR__ . '/../views/registres/ajouter_receveur_csh.php';
    }
    
    public function verifierCompatibilite() {
        require_once __DIR__ . '/../models/BanqueSang.php';
        $banqueModel = new BanqueSang();
        
        $groupe = $_GET['groupe'] ?? '';
        $rhesus = $_GET['rhesus'] ?? '';
        
        $compatibles = $banqueModel->verifierCompatibilite($groupe, $rhesus);
        
        header('Content-Type: application/json');
        echo json_encode($compatibles);
        exit;
    }
}
?>