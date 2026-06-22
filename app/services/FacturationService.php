<?php
class FacturationService {
    private $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    /**
     * Génère une facture pour une consultation.
     * Protection anti-doublon : si une facture existe déjà pour cette consultation,
     * retourne l'ID existant sans créer un doublon.
     */
    public function genererFactureConsultation($consultation_id) {
        $consultation_id = (int)$consultation_id;

        // ── Protection anti-doublon ─────────────────────────────────────────
        $stmtExist = $this->db->prepare(
            "SELECT id FROM factures WHERE consultation_id = ? LIMIT 1"
        );
        $stmtExist->execute([$consultation_id]);
        $existingId = $stmtExist->fetchColumn();
        if ($existingId) return (int)$existingId;

        // ── Récupérer la consultation ───────────────────────────────────────
        $stmt = $this->db->prepare(
            "SELECT c.*, p.nom, p.prenom, p.type_client
             FROM consultations c
             JOIN patients p ON c.patient_id = p.id
             WHERE c.id = ?"
        );
        $stmt->execute([$consultation_id]);
        $consultation = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$consultation) return false;

        $this->db->beginTransaction();
        try {
            // ── Numéro de facture (avec FOR UPDATE anti-race) ───────────────
            $stmtNum = $this->db->prepare(
                "SELECT COALESCE(MAX(id), 0) FROM factures FOR UPDATE"
            );
            $stmtNum->execute();
            $numero = 'FAC-' . date('Y') . '-' . str_pad($this->getNextFactureNumber(), 6, '0', STR_PAD_LEFT);

            // ── Créer la facture ────────────────────────────────────────────
            $stmtIns = $this->db->prepare(
                "INSERT INTO factures
                    (numero_facture, patient_id, consultation_id, statut, date_facture, montant_ht, montant_ttc)
                 VALUES (?, ?, ?, 'EN_ATTENTE', CURDATE(), 0, 0)"
            );
            $stmtIns->execute([$numero, $consultation['patient_id'], $consultation_id]);
            $facture_id = (int)$this->db->lastInsertId();

            // ── Ajouter les lignes ──────────────────────────────────────────
            $this->ajouterLigneConsultation($facture_id, $consultation);
            $this->ajouterLignesExamens($facture_id, $consultation_id);
            $this->ajouterLignesMedicaments($facture_id, $consultation_id);

            // ── Recalculer le total ─────────────────────────────────────────
            $this->calculerTotalFacture($facture_id);

            $this->db->commit();
            return $facture_id;

        } catch (Exception $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            error_log('[FacturationService] ' . $e->getMessage());
            return false;
        }
    }

    private function ajouterLigneConsultation($facture_id, $consultation) {
        $stmt = $this->db->prepare("SELECT * FROM tarifs WHERE code = ? LIMIT 1");
        $stmt->execute(['CONS_GEN']);
        $tarif = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$tarif) return;

        $this->insererLigne($facture_id, $tarif['id'], 1, (float)$tarif['prix'], 'Consultation médicale');
    }

    /**
     * Lignes examens : récupère depuis demandes_laboratoire ET demandes_imagerie
     * (la table `examens` est vide — les demandes transitent par ces deux tables).
     */
    private function ajouterLignesExamens($facture_id, $consultation_id) {
        // Examens labo
        $stmtLabo = $this->db->prepare(
            "SELECT dl.type_examen, COUNT(*) AS nb
             FROM demandes_laboratoire dl
             WHERE dl.consultation_id = ?
               AND dl.statut NOT IN ('ANNULE','ANNULEE')
             GROUP BY dl.type_examen"
        );
        $stmtLabo->execute([$consultation_id]);
        foreach ($stmtLabo->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $code  = $this->mapExamenToTarif($row['type_examen'] ?? 'labo');
            $tarif = $this->getTarif($code) ?: $this->getTarif('EXAMEN_LABO');
            if ($tarif) {
                $this->insererLigne($facture_id, $tarif['id'], (int)$row['nb'],
                    (float)$tarif['prix'], 'Examen labo : ' . ($row['type_examen'] ?? 'Biologie'));
            }
        }

        // Examens imagerie
        $stmtImg = $this->db->prepare(
            "SELECT di.type_examen, COUNT(*) AS nb
             FROM demandes_imagerie di
             WHERE di.consultation_id = ?
               AND di.statut NOT IN ('ANNULE','ANNULEE')
             GROUP BY di.type_examen"
        );
        $stmtImg->execute([$consultation_id]);
        foreach ($stmtImg->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $code  = $this->mapExamenToTarif($row['type_examen'] ?? 'imagerie');
            $tarif = $this->getTarif($code) ?: $this->getTarif('EXAMEN_IMAGERIE');
            if ($tarif) {
                $this->insererLigne($facture_id, $tarif['id'], (int)$row['nb'],
                    (float)$tarif['prix'], 'Imagerie : ' . ($row['type_examen'] ?? 'Radiologie'));
            }
        }
    }

    private function ajouterLignesMedicaments($facture_id, $consultation_id) {
        // Médicaments prescrits via les ordonnances de cette consultation
        $stmt = $this->db->prepare(
            "SELECT om.nom_medicament, om.quantite,
                    COALESCE(m.prix_unitaire, 0) AS prix_unitaire
             FROM ordonnances_pharmacie op
             JOIN ordonnance_medicaments om ON om.ordonnance_id = op.id
             LEFT JOIN medicaments m ON m.id = om.medicament_id
             WHERE op.consultation_id = ?
               AND COALESCE(om.ligne_annulee, 0) = 0"
        );
        $stmt->execute([$consultation_id]);
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $med) {
            $qte  = max(1, (int)$med['quantite']);
            $prix = (float)$med['prix_unitaire'];
            if ($prix <= 0) continue; // médicaments sans tarif ignorés

            // Pas de tarif_id pour les médicaments → on insère en ligne libre
            $stmtLigne = $this->db->prepare(
                "INSERT INTO facture_lignes
                    (facture_id, description, quantite, prix_unitaire, montant)
                 VALUES (?, ?, ?, ?, ?)"
            );
            $stmtLigne->execute([
                $facture_id,
                'Médicament : ' . ($med['nom_medicament'] ?? ''),
                $qte,
                $prix,
                $qte * $prix,
            ]);
        }
    }

    /**
     * Insère une ligne de facture avec montant = quantite × prix_unitaire.
     */
    private function insererLigne(int $facture_id, int $tarif_id, int $qte, float $prix, string $desc = '') {
        // Tenter avec tarif_id (contrainte FK), fallback sans si la colonne n'existe pas
        try {
            $stmt = $this->db->prepare(
                "INSERT INTO facture_lignes
                    (facture_id, tarif_id, description, quantite, prix_unitaire, montant)
                 VALUES (?, ?, ?, ?, ?, ?)"
            );
            $stmt->execute([$facture_id, $tarif_id, $desc, $qte, $prix, $qte * $prix]);
        } catch (Exception $e) {
            // Fallback : sans tarif_id (table sans cette colonne)
            $stmt = $this->db->prepare(
                "INSERT INTO facture_lignes
                    (facture_id, description, quantite, prix_unitaire, montant)
                 VALUES (?, ?, ?, ?, ?)"
            );
            $stmt->execute([$facture_id, $desc, $qte, $prix, $qte * $prix]);
        }
    }

    /**
     * Recalcule le total en sommant quantite × prix_unitaire (et non montant stocké,
     * pour corriger les anciens enregistrements mal calculés).
     */
    private function calculerTotalFacture($facture_id) {
        $stmt = $this->db->prepare(
            "SELECT SUM(quantite * prix_unitaire) AS total
             FROM facture_lignes WHERE facture_id = ?"
        );
        $stmt->execute([$facture_id]);
        $total = (float)($stmt->fetchColumn() ?? 0);

        // Synchroniser aussi la colonne montant de chaque ligne
        $this->db->prepare(
            "UPDATE facture_lignes SET montant = quantite * prix_unitaire WHERE facture_id = ?"
        )->execute([$facture_id]);

        $this->db->prepare(
            "UPDATE factures SET montant_ht = ?, montant_ttc = ? WHERE id = ?"
        )->execute([$total, $total, $facture_id]);
    }

    private function getTarif(string $code): ?array {
        $stmt = $this->db->prepare("SELECT * FROM tarifs WHERE code = ? LIMIT 1");
        $stmt->execute([$code]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    private function getNextFactureNumber(): int {
        $stmt = $this->db->prepare(
            "SELECT COALESCE(MAX(CAST(SUBSTRING_INDEX(numero_facture, '-', -1) AS UNSIGNED)), 0) + 1
             FROM factures WHERE YEAR(date_facture) = YEAR(CURDATE())"
        );
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }

    private function mapExamenToTarif(string $type): string {
        $map = [
            'radiographie' => 'RADIO_THOR',
            'scanner'      => 'SCAN_CRANE',
            'irm'          => 'IRM_CRANE',
            'echographie'  => 'ECHO_ABD',
            'nfs'          => 'EXAMEN_LABO',
            'bilan'        => 'EXAMEN_LABO',
        ];
        return $map[strtolower($type)] ?? 'EXAMEN_LABO';
    }

    public function genererPDF($facture_id) {
        $stmt = $this->db->prepare(
            "SELECT f.*, p.nom, p.prenom, p.adresse, p.telephone
             FROM factures f JOIN patients p ON f.patient_id = p.id WHERE f.id = ?"
        );
        $stmt->execute([$facture_id]);
        $facture = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$facture) return '';

        $stmtL = $this->db->prepare(
            "SELECT fl.*, COALESCE(t.libelle, fl.description) AS libelle
             FROM facture_lignes fl
             LEFT JOIN tarifs t ON fl.tarif_id = t.id
             WHERE fl.facture_id = ?"
        );
        $stmtL->execute([$facture_id]);
        $lignes = $stmtL->fetchAll(PDO::FETCH_ASSOC);

        return $this->genererHTMLFacture($facture, $lignes);
    }

    private function genererHTMLFacture($facture, $lignes) {
        $rows = '';
        foreach ($lignes as $l) {
            $montant = (float)$l['quantite'] * (float)$l['prix_unitaire'];
            $rows .= '<tr>
                <td style="border:1px solid #ddd;padding:10px;">'
                    . htmlspecialchars($l['libelle'] ?? '', ENT_QUOTES, 'UTF-8') . '</td>
                <td style="border:1px solid #ddd;padding:10px;text-align:center;">'
                    . (int)$l['quantite'] . '</td>
                <td style="border:1px solid #ddd;padding:10px;text-align:right;">'
                    . number_format($l['prix_unitaire'], 0, ',', ' ') . ' FCFA</td>
                <td style="border:1px solid #ddd;padding:10px;text-align:right;">'
                    . number_format($montant, 0, ',', ' ') . ' FCFA</td>
            </tr>';
        }

        return '<div style="font-family:Arial;max-width:800px;margin:0 auto;">
            <div style="text-align:center;margin-bottom:30px;">
                <h1>HSJM — SimCare+</h1><p>Hôpital Saint Jean de Malte</p>
            </div>
            <div style="display:flex;justify-content:space-between;margin-bottom:30px;">
                <div><h3>FACTURE</h3>
                    <p><strong>N° :</strong> ' . htmlspecialchars($facture['numero_facture'], ENT_QUOTES, 'UTF-8') . '</p>
                    <p><strong>Date :</strong> ' . date('d/m/Y', strtotime($facture['date_facture'])) . '</p>
                </div>
                <div><h4>Patient</h4>
                    <p>' . htmlspecialchars($facture['nom'] . ' ' . $facture['prenom'], ENT_QUOTES, 'UTF-8') . '</p>
                    <p>' . htmlspecialchars($facture['adresse'] ?? '', ENT_QUOTES, 'UTF-8') . '</p>
                    <p>' . htmlspecialchars($facture['telephone'] ?? '', ENT_QUOTES, 'UTF-8') . '</p>
                </div>
            </div>
            <table style="width:100%;border-collapse:collapse;margin-bottom:30px;">
                <thead><tr style="background:#f5f5f5;">
                    <th style="border:1px solid #ddd;padding:10px;text-align:left;">Description</th>
                    <th style="border:1px solid #ddd;padding:10px;text-align:center;">Qté</th>
                    <th style="border:1px solid #ddd;padding:10px;text-align:right;">Prix unit.</th>
                    <th style="border:1px solid #ddd;padding:10px;text-align:right;">Total</th>
                </tr></thead>
                <tbody>' . $rows . '</tbody>
                <tfoot><tr style="background:#f5f5f5;font-weight:bold;">
                    <td colspan="3" style="border:1px solid #ddd;padding:10px;text-align:right;">TOTAL TTC</td>
                    <td style="border:1px solid #ddd;padding:10px;text-align:right;">'
                    . number_format($facture['montant_ttc'], 0, ',', ' ') . ' FCFA</td>
                </tr></tfoot>
            </table>
            <div style="text-align:center;margin-top:50px;"><p><em>Merci de votre confiance</em></p></div>
        </div>';
    }
}
