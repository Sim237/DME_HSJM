<?php
/* ============================================================================
   FICHIER : app/models/Laboratoire.php
   Modèle complet pour la gestion du Laboratoire (SIL)
   ============================================================================ */
require_once __DIR__ . '/../../config/database.php';

class Laboratoire {
    private $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    // --- CATALOGUE & CONFIGURATION ---

    public function getCatalogue() {
        $sql = "SELECT c.*, cat.nom as categorie_nom 
                FROM lab_catalogue c 
                JOIN lab_categories cat ON c.categorie_id = cat.id 
                ORDER BY cat.nom, c.nom";
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    // --- CRÉATION & DEMANDES ---

    // Création intelligente basée sur le catalogue
    public function creerDemande($patient_id, $medecin_id, $catalogue_id, $urgence, $observations = '') {
        try {
            $this->db->beginTransaction();

            // 1. Récupérer les infos de l'examen depuis le catalogue
            $stmt = $this->db->prepare("SELECT * FROM lab_catalogue WHERE id = ?");
            $stmt->execute([$catalogue_id]);
            $infosExam = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$infosExam) throw new Exception("Examen non trouvé dans le catalogue");

            // 2. Créer l'en-tête de la demande
            $sql = "INSERT INTO examens (patient_id, medecin_id, type_examen, urgence, observations, statut, date_demande, etat_prelevement) 
                    VALUES (?, ?, ?, ?, ?, 'EN_ATTENTE', NOW(), 'NON_FAIT')";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$patient_id, $medecin_id, $infosExam['nom'], $urgence, $observations]);
            $examen_id = $this->db->lastInsertId();

            // 3. Générer les lignes de résultats attendus (Paramètres)
            $params = $this->db->prepare("SELECT * FROM lab_parametres WHERE examen_id = ? ORDER BY ordre_affichage");
            $params->execute([$catalogue_id]);
            $parametres = $params->fetchAll(PDO::FETCH_ASSOC);

            $sqlDetail = "INSERT INTO examen_details (examen_id, nom_examen, unite, valeur_normale) VALUES (?, ?, ?, ?)";
            $stmtDetail = $this->db->prepare($sqlDetail);

            foreach ($parametres as $p) {
                // TODO: Affiner les normes selon le sexe/âge du patient si besoin
                $normes = $p['valeur_min'] . ' - ' . $p['valeur_max'];
                $stmtDetail->execute([$examen_id, $p['nom'], $p['unite'], $normes]);
            }

            $this->db->commit();
            return $examen_id;

        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Erreur création demande labo : " . $e->getMessage());
            return false;
        }
    }

    // --- LECTURE & LISTES ---

    public function getAll($filter = 'all') {
        $sql = "SELECT e.*, 
                p.nom as patient_nom, p.prenom as patient_prenom, p.dossier_numero,
                u.nom as medecin_nom, u.prenom as medecin_prenom
                FROM examens e
                JOIN patients p ON e.patient_id = p.id
                LEFT JOIN users u ON e.medecin_id = u.id";
        
        if ($filter !== 'all') {
            $sql .= " WHERE e.statut = :statut";
        }
        
        $sql .= " ORDER BY e.urgence DESC, e.date_demande DESC";
        
        $stmt = $this->db->prepare($sql);
        if ($filter !== 'all') {
            $stmt->execute([':statut' => strtoupper($filter)]);
        } else {
            $stmt->execute();
        }
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById($id) {
        $sql = "SELECT e.*, 
                p.nom as patient_nom, p.prenom as patient_prenom, 
                p.dossier_numero, p.date_naissance, p.sexe,
                u.nom as medecin_nom, u.prenom as medecin_prenom
                FROM examens e
                JOIN patients p ON e.patient_id = p.id
                LEFT JOIN users u ON e.medecin_id = u.id
                WHERE e.id = :id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        $examen = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($examen) {
            $examen['details'] = $this->getDetails($id);
            // Fallback pour le nom
            if (empty($examen['nom_examen'])) {
                $examen['nom_examen'] = $examen['type_examen']; 
            }
        }
        return $examen;
    }

    public function getDetails($examen_id) {
        $stmt = $this->db->prepare("SELECT * FROM examen_details WHERE examen_id = ?");
        $stmt->execute([$examen_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // --- WORKFLOW & MISES À JOUR ---

    // Étape 1 : Valider le prélèvement
    public function validerPrelevement($id) {
        $sql = "UPDATE examens SET etat_prelevement = 'FAIT', date_prelevement = NOW(), statut = 'EN_COURS' WHERE id = ?";
        return $this->db->prepare($sql)->execute([$id]);
    }

    // Étape 2 : Saisir les résultats techniques
    public function updateResultats($data) {
        try {
            $this->db->beginTransaction();
            
            // Mise à jour de l'en-tête
            $sql = "UPDATE examens SET 
                    observations_labo = :obs,
                    technicien_id = :tech,
                    validation_biologiste = :valid_bio
                    WHERE id = :id";
            
            $validBio = isset($data['validation_biologiste']) ? 1 : 0;
            $nouveauStatut = $validBio ? 'TERMINE' : 'EN_COURS'; // Si validé bio, c'est fini

            if ($validBio) {
                $sql = "UPDATE examens SET observations_labo=:obs, technicien_id=:tech, validation_biologiste=1, statut='TERMINE', date_resultat=NOW() WHERE id=:id";
            }

            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':obs' => $data['observations_labo'],
                ':tech' => $data['technicien_id'],
                ':id' => $data['examen_id']
            ]);
            
            // Mise à jour des lignes de détails
            if (!empty($data['resultats'])) {
                $sqlDet = "UPDATE examen_details SET resultat = :res WHERE id = :id";
                $stmtDet = $this->db->prepare($sqlDet);
                
                foreach ($data['resultats'] as $detail_id => $valeurs) {
                    // $valeurs contient ['resultat' => '...']
                    $stmtDet->execute([
                        ':res' => $valeurs['resultat'],
                        ':id' => $detail_id
                    ]);
                }
            }
            
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }

    // --- STATISTIQUES ---

    public function countEnAttente() {
        $stmt = $this->db->query("SELECT COUNT(*) as total FROM examens WHERE statut = 'EN_ATTENTE'");
        return $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    }
    
    public function countUrgent() {
        $stmt = $this->db->query("SELECT COUNT(*) as total FROM examens WHERE statut = 'EN_ATTENTE' AND urgence = 1");
        return $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    }

    public function getStatsGraph($periode) {
        $days = ($periode === '30days') ? 30 : 7;
        $sql = "SELECT DATE(date_demande) as date, COUNT(*) as total
                FROM examens
                WHERE date_demande >= DATE_SUB(NOW(), INTERVAL :days DAY)
                GROUP BY DATE(date_demande)
                ORDER BY date";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':days' => $days]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>