<?php

require_once __DIR__ . '/UnifiedController.php';
require_once __DIR__ . '/../services/ChatMedicalService.php';
require_once __DIR__ . '/../services/FamilleService.php';
require_once __DIR__ . '/../services/FormationService.php';

class ModulesController extends UnifiedController {
    private $chatService;
    private $familleService;
    private $formationService;
    
    public function __construct() {
        parent::__construct();
        $this->chatService = new ChatMedicalService();
        $this->familleService = new FamilleService();
        $this->formationService = new FormationService();
    }
    
    // === CHAT MÉDICAL ===
    public function chat() {
        $conversations = $this->chatService->getConversations($_SESSION['user_id']);
        $urgents = $this->chatService->getMessagesUrgents($_SESSION['user_id']);
        
        $this->render('modules/chat', [
            'conversations' => $conversations,
            'urgents' => $urgents
        ]);
    }
    
    public function chatConversation($contact_id) {
        $messages = $this->chatService->getMessages($_SESSION['user_id'], $contact_id);
        $this->chatService->marquerLu($_SESSION['user_id'], $contact_id);
        
        header('Content-Type: application/json');
        echo json_encode($messages);
    }
    
    public function chatEnvoyer() {
        $data = json_decode(file_get_contents('php://input'), true);
        $result = $this->chatService->envoyerMessage(
            $_SESSION['user_id'],
            $data['destinataire_id'],
            $data['message'],
            $data['patient_id'] ?? null,
            $data['urgent'] ?? false
        );
        
        header('Content-Type: application/json');
        echo json_encode(['success' => $result]);
    }
    
    // === GESTION FAMILLE ===
    public function famille($patient_id = null) {
        if (!$patient_id) {
            $this->render('modules/famille-liste');
            return;
        }
        
        $membres = $this->familleService->getMembres($patient_id);
        $visites = $this->familleService->getVisites($patient_id);
        
        $this->render('modules/famille', [
            'patient_id' => $patient_id,
            'membres' => $membres,
            'visites' => $visites
        ]);
    }
    
    public function familleAjouter() {
        $data = $_POST;
        $result = $this->familleService->ajouterMembre($data['patient_id'], $data);
        
        if ($result) {
            $this->redirect('/modules/famille/' . $data['patient_id']);
        } else {
            $this->render('modules/famille', ['error' => 'Erreur lors de l\'ajout']);
        }
    }
    
    public function visitePlanifier() {
        $data = $_POST;
        $result = $this->familleService->planifierVisite($data['patient_id'], $data);
        
        header('Content-Type: application/json');
        echo json_encode(['success' => $result]);
    }
    
    public function visitesAujourdhui() {
        $visites = $this->familleService->getVisitesAujourdhui();
        
        header('Content-Type: application/json');
        echo json_encode($visites);
    }
    
    // === FORMATION PERSONNEL ===
    public function formations() {
        $formations = $this->formationService->getFormationsDisponibles($_SESSION['user_id']);
        $mesFormations = $this->formationService->getFormationsUtilisateur($_SESSION['user_id']);
        
        $this->render('modules/formations', [
            'formations' => $formations,
            'mesFormations' => $mesFormations
        ]);
    }
    
    public function formationInscrire($session_id) {
        $result = $this->formationService->inscrireUtilisateur($session_id, $_SESSION['user_id']);
        
        header('Content-Type: application/json');
        echo json_encode(['success' => $result]);
    }
    
    public function formationCreer() {
        if ($_SESSION['role'] !== 'admin') {
            $this->redirect('/modules/formations');
            return;
        }
        
        if ($_POST) {
            $result = $this->formationService->creerFormation($_POST);
            if ($result) {
                $this->redirect('/modules/formations');
                return;
            }
        }
        
        $this->render('modules/formation-creer');
    }
}