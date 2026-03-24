<?php

require_once __DIR__ . '/../../config/database.php';

class ChatMedicalService {
    private $db;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }
    
    public function envoyerMessage($expediteur_id, $destinataire_id, $message, $patient_id = null, $urgent = false) {
        $stmt = $this->db->prepare("
            INSERT INTO chat_medical (expediteur_id, destinataire_id, patient_id, message, urgent) 
            VALUES (?, ?, ?, ?, ?)
        ");
        return $stmt->execute([$expediteur_id, $destinataire_id, $patient_id, $message, $urgent]);
    }
    
    public function getConversations($user_id) {
        if (!$this->db) return [];
        
        try {
            $stmt = $this->db->prepare("
                SELECT DISTINCT 
                    CASE WHEN expediteur_id = ? THEN destinataire_id ELSE expediteur_id END as contact_id,
                    u.nom, u.prenom, u.role,
                    MAX(created_at) as derniere_activite,
                    COUNT(CASE WHEN destinataire_id = ? AND lu = 0 THEN 1 END) as non_lus
                FROM chat_medical c
                JOIN users u ON u.id = CASE WHEN expediteur_id = ? THEN destinataire_id ELSE expediteur_id END
                WHERE expediteur_id = ? OR destinataire_id = ?
                GROUP BY contact_id
                ORDER BY derniere_activite DESC
            ");
            $stmt->execute([$user_id, $user_id, $user_id, $user_id, $user_id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }
    
    public function getMessages($user1_id, $user2_id, $limit = 50) {
        $stmt = $this->db->prepare("
            SELECT c.*, u.nom, u.prenom, p.nom as patient_nom, p.prenom as patient_prenom
            FROM chat_medical c
            JOIN users u ON u.id = c.expediteur_id
            LEFT JOIN patients p ON p.id = c.patient_id
            WHERE (expediteur_id = ? AND destinataire_id = ?) OR (expediteur_id = ? AND destinataire_id = ?)
            ORDER BY created_at DESC LIMIT ?
        ");
        $stmt->execute([$user1_id, $user2_id, $user2_id, $user1_id, $limit]);
        return array_reverse($stmt->fetchAll(PDO::FETCH_ASSOC));
    }
    
    public function marquerLu($user_id, $contact_id) {
        $stmt = $this->db->prepare("
            UPDATE chat_medical SET lu = 1 
            WHERE destinataire_id = ? AND expediteur_id = ? AND lu = 0
        ");
        return $stmt->execute([$user_id, $contact_id]);
    }
    
    public function getMessagesUrgents($user_id) {
        if (!$this->db) return [];
        
        try {
            $stmt = $this->db->prepare("
                SELECT c.*, u.nom, u.prenom 
                FROM chat_medical c
                JOIN users u ON u.id = c.expediteur_id
                WHERE destinataire_id = ? AND urgent = 1 AND lu = 0
                ORDER BY created_at DESC
            ");
            $stmt->execute([$user_id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }
}