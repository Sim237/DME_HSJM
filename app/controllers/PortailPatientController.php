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

require_once __DIR__ . '/../models/Patient.php';

class PortailPatientController {
    private $db;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }
    
    public function login() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = $_POST['email'];
            $password = $_POST['password'];

            // Authentification via la table patients (email + mot_de_passe)
            $sql = "SELECT id, nom, prenom, email, mot_de_passe FROM patients
                    WHERE email = ? AND actif = 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$email]);
            $account = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($account && password_verify($password, $account['mot_de_passe'] ?? '')) {
                $_SESSION['patient_id'] = $account['id'];
                $_SESSION['patient_name'] = $account['nom'] . ' ' . $account['prenom'];
                header('Location: ' . BASE_URL . 'portail/dashboard');
            } else {
                $_SESSION['error'] = 'Email ou mot de passe incorrect';
                header('Location: ' . BASE_URL . 'portail/login');
            }
        } else {
            include __DIR__ . '/../views/portail/login.php';
        }
    }
    
    public function dashboard() {
        $this->checkAuth();
        $patientId = $_SESSION['patient_id'];
        
        $rdvs = $this->getProchainRdv($patientId);
        $traitements = $this->getTraitementsActifs($patientId);
        $rappels = $this->getRappelsDuJour($patientId);
        
        include __DIR__ . '/../views/portail/dashboard.php';
    }
    
    public function rdv() {
        $this->checkAuth();
        $patientId = $_SESSION['patient_id'];
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $sql = "INSERT INTO agenda_medical (patient_id, medecin_id, date_debut, date_fin, titre, statut, type_rdv)
                    VALUES (?, ?, ?, DATE_ADD(?, INTERVAL 30 MINUTE), ?, 'planifie', 'consultation')";
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([
                $patientId,
                $_POST['medecin_id'],
                $_POST['date_rdv'],
                $_POST['date_rdv'],
                $_POST['motif'],
            ]);
            
            if ($result) {
                $_SESSION['success'] = 'Demande de RDV envoyée';
            }
        }
        
        $medecins = $this->getMedecins();
        $rdvs = $this->getRdvPatient($patientId);
        
        include __DIR__ . '/../views/portail/rdv.php';
    }
    
    public function dossier() {
        $this->checkAuth();
        $patientId = $_SESSION['patient_id'];
        
        $patientModel = new Patient();
        $patient = $patientModel->getById($patientId);
        $consultations = $patientModel->getConsultations($patientId, 10);
        $examens = $patientModel->getExamens($patientId, 10);
        
        include __DIR__ . '/../views/portail/dossier.php';
    }
    
    private function checkAuth() {
        if (!isset($_SESSION['patient_id'])) {
            header('Location: ' . BASE_URL . 'portail/login');
            exit;
        }
    }
    
    private function updateLastLogin($patientId) {
        // No-op: last_login not tracked in current schema
    }
    
    private function getProchainRdv($patientId) {
        $sql = "SELECT r.*, u.nom as medecin_nom, u.prenom as medecin_prenom
                FROM agenda_medical r
                JOIN users u ON r.medecin_id = u.id
                WHERE r.patient_id = ? AND r.date_debut > NOW() AND r.statut != 'annule'
                ORDER BY r.date_debut LIMIT 3";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$patientId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    private function getTraitementsActifs($patientId) {
        // Récupère les médicaments actifs des prescriptions en cours
        $sql = "SELECT lp.nom_medicament, lp.posologie, lp.duree, lp.frequence,
                       p.date_prescription, p.statut
                FROM lignes_prescription lp
                JOIN prescriptions p ON lp.prescription_id = p.id
                WHERE p.patient_id = ? AND p.statut NOT IN ('ANNULEE', 'EXPIREE')
                ORDER BY p.date_prescription DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$patientId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function getRappelsDuJour($patientId) {
        // Récupère les RDV du jour comme rappels
        $sql = "SELECT r.titre as date_rappel, r.date_debut, u.nom as medecin_nom
                FROM agenda_medical r
                LEFT JOIN users u ON r.medecin_id = u.id
                WHERE r.patient_id = ? AND DATE(r.date_debut) = CURDATE()
                ORDER BY r.date_debut";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$patientId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    private function getMedecins() {
        $sql = "SELECT id, nom, prenom, specialite FROM users WHERE role = 'MEDECIN' ORDER BY nom";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    private function getRdvPatient($patientId) {
        $sql = "SELECT r.*, u.nom as medecin_nom, u.prenom as medecin_prenom
                FROM agenda_medical r
                JOIN users u ON r.medecin_id = u.id
                WHERE r.patient_id = ?
                ORDER BY r.date_debut DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$patientId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>