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

/**
 * PredictionStockService
 * Analyse la consommation réelle des médicaments, prédit les ruptures
 * et génère des alertes commande automatiques.
 *
 * Sources de consommation :
 *   - ordonnance_medicaments + ordonnances_pharmacie.date_creation
 *   - administrations_medicaments.heure_administration
 * Stock de référence : medicaments.quantite (mis à jour par SageSyncService)
 */
class PredictionStockService
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
        $this->ensureSchema();
    }

    // ─────────────────────────────────────────────────────────────────
    //  SCHEMA
    // ─────────────────────────────────────────────────────────────────
    private function ensureSchema(): void
    {
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS pharmacie_alertes_stock (
                id                INT AUTO_INCREMENT PRIMARY KEY,
                medicament_id     INT NOT NULL,
                type_alerte       ENUM('STOCK_ZERO','RUPTURE_IMMINENTE','SOUS_SEUIL','COMMANDE_REQUISE') NOT NULL DEFAULT 'SOUS_SEUIL',
                niveau            ENUM('INFO','WARNING','DANGER','CRITIQUE') NOT NULL DEFAULT 'WARNING',
                stock_actuel      INT           DEFAULT 0,
                stock_sage        DECIMAL(10,2) DEFAULT NULL,
                consommation_j    DECIMAL(10,4) DEFAULT 0,
                jours_restants    DECIMAL(7,1)  DEFAULT NULL,
                seuil_alerte      INT           DEFAULT 0,
                quantite_commande INT           DEFAULT NULL,
                montant_estime    DECIMAL(12,2) DEFAULT NULL,
                message           TEXT,
                statut            ENUM('ACTIVE','ACQUITTEE','RESOLUE') DEFAULT 'ACTIVE',
                acquittee_par     INT           DEFAULT NULL,
                acquittee_at      DATETIME      DEFAULT NULL,
                created_at        DATETIME      DEFAULT CURRENT_TIMESTAMP,
                updated_at        DATETIME      DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY uq_med_type  (medicament_id, type_alerte),
                KEY idx_statut          (statut),
                KEY idx_niveau          (niveau),
                KEY idx_updated         (updated_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
    }

    // ─────────────────────────────────────────────────────────────────
    //  CONSOMMATION
    // ─────────────────────────────────────────────────────────────────

    /**
     * Consommation moyenne journalière sur les N derniers jours.
     * Combine prescriptions délivrées + administrations infirmières.
     */
    public function getConsommationMoyenne(int $medId, int $days = 30): float
    {
        // Source 1 : ordonnances dispensées
        $stmt = $this->db->prepare("
            SELECT COALESCE(SUM(om.quantite), 0)
            FROM ordonnance_medicaments om
            JOIN ordonnances_pharmacie op ON om.ordonnance_id = op.id
            WHERE om.medicament_id = ?
              AND op.date_creation >= DATE_SUB(NOW(), INTERVAL ? DAY)
              AND om.ligne_annulee = 0
        ");
        $stmt->execute([$medId, $days]);
        $qOrdo = (float)($stmt->fetchColumn() ?: 0);

        // Source 2 : administrations directes (1 administration = 1 dose)
        $stmt2 = $this->db->prepare("
            SELECT COALESCE(COUNT(*), 0)
            FROM administrations_medicaments am
            WHERE am.medicament_id = ?
              AND am.heure_administration >= DATE_SUB(NOW(), INTERVAL ? DAY)
        ");
        $stmt2->execute([$medId, $days]);
        $qAdm = (float)($stmt2->fetchColumn() ?: 0);

        return $days > 0 ? (($qOrdo + $qAdm) / $days) : 0.0;
    }

    /**
     * Historique de consommation semaine par semaine (8 semaines).
     * Utilisé pour les graphiques.
     */
    public function getConsommationSemaines(int $medId, int $weeks = 8): array
    {
        $stmt = $this->db->prepare("
            SELECT
                YEARWEEK(op.date_creation, 1)             AS semaine,
                MIN(DATE(op.date_creation))               AS debut_sem,
                COALESCE(SUM(om.quantite), 0)             AS qte
            FROM ordonnance_medicaments om
            JOIN ordonnances_pharmacie op ON om.ordonnance_id = op.id
            WHERE om.medicament_id = ?
              AND op.date_creation >= DATE_SUB(NOW(), INTERVAL ? WEEK)
              AND om.ligne_annulee = 0
            GROUP BY YEARWEEK(op.date_creation, 1)
            ORDER BY semaine ASC
        ");
        $stmt->execute([$medId, $weeks]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ─────────────────────────────────────────────────────────────────
    //  PRÉDICTION
    // ─────────────────────────────────────────────────────────────────

    public function predictRupture(int $medId): array
    {
        $stmt = $this->db->prepare("
            SELECT m.id, m.nom, m.quantite AS stock_dme,
                   COALESCE(sm.as_qte_sto, m.quantite) AS stock_sage,
                   m.seuil_alerte, m.ar_ref_sage, m.prix_unitaire,
                   m.stock_sage_at, m.prix_sage
            FROM medicaments m
            LEFT JOIN sage_medicaments_map sm
                   ON sm.medicament_id = m.id AND sm.exclure_sync = 0
            WHERE m.id = ?
        ");
        $stmt->execute([$medId]);
        $med = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$med) return [];

        $stockActuel   = max(0, (int)$med['stock_dme']);
        $stockSage     = (float)$med['stock_sage'];
        $seuilAlerte   = (int)$med['seuil_alerte'];
        $prixUnit      = (float)($med['prix_sage'] ?: $med['prix_unitaire'] ?: 0);
        $consoJ        = $this->getConsommationMoyenne($medId, 30);

        $joursRestants = ($consoJ > 0) ? ($stockActuel / $consoJ) : null;

        // Niveau de risque
        if ($stockActuel <= 0) {
            [$niveau, $type] = ['CRITIQUE', 'STOCK_ZERO'];
        } elseif ($joursRestants !== null && $joursRestants <= 3) {
            [$niveau, $type] = ['CRITIQUE', 'RUPTURE_IMMINENTE'];
        } elseif ($joursRestants !== null && $joursRestants <= 7) {
            [$niveau, $type] = ['DANGER', 'RUPTURE_IMMINENTE'];
        } elseif (($joursRestants !== null && $joursRestants <= 21)
               || ($seuilAlerte > 0 && $stockActuel <= $seuilAlerte)) {
            $type   = ($seuilAlerte > 0 && $stockActuel <= $seuilAlerte) ? 'SOUS_SEUIL' : 'COMMANDE_REQUISE';
            $niveau = 'WARNING';
        } else {
            [$niveau, $type] = ['INFO', 'COMMANDE_REQUISE'];
        }

        // Quantité à commander pour 60 jours de stock cible
        $qteCible    = (int)ceil($consoJ * 60);
        $qteCommande = max(0, $qteCible - $stockActuel);
        $montant     = $prixUnit * $qteCommande;

        return [
            'medicament_id'    => $medId,
            'nom'              => $med['nom'],
            'stock_actuel'     => $stockActuel,
            'stock_sage'       => $stockSage,
            'seuil_alerte'     => $seuilAlerte,
            'consommation_j'   => round($consoJ, 4),
            'consommation_30j' => round($consoJ * 30, 1),
            'jours_restants'   => $joursRestants !== null ? round($joursRestants, 1) : null,
            'date_rupture'     => $joursRestants !== null
                ? date('Y-m-d', strtotime('+' . (int)ceil($joursRestants) . ' days'))
                : null,
            'niveau'           => $niveau,
            'type_alerte'      => $type,
            'quantite_commande'=> $qteCommande > 0 ? $qteCommande : null,
            'montant_estime'   => $montant > 0 ? $montant : null,
            'prix_unitaire'    => $prixUnit,
            'ar_ref_sage'      => $med['ar_ref_sage'],
        ];
    }

    // ─────────────────────────────────────────────────────────────────
    //  GÉNÉRATION DES ALERTES
    // ─────────────────────────────────────────────────────────────────

    public function generateAlertes(): array
    {
        // Médicaments à analyser : sous seuil OU consommés récemment OU stock = 0
        $stmt = $this->db->query("
            SELECT DISTINCT m.id
            FROM medicaments m
            WHERE m.quantite <= 0
               OR (m.seuil_alerte > 0 AND m.quantite <= m.seuil_alerte * 3)
               OR EXISTS (
                   SELECT 1 FROM ordonnance_medicaments om
                   JOIN ordonnances_pharmacie op ON om.ordonnance_id = op.id
                   WHERE om.medicament_id = m.id
                     AND op.date_creation >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                     AND om.ligne_annulee = 0
               )
               OR EXISTS (
                   SELECT 1 FROM administrations_medicaments am
                   WHERE am.medicament_id = m.id
                     AND am.heure_administration >= DATE_SUB(NOW(), INTERVAL 30 DAY)
               )
        ");
        $medIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $generes = 0; $maj = 0;

        foreach ($medIds as $medId) {
            $pred = $this->predictRupture((int)$medId);
            if (empty($pred)) continue;
            if ($pred['niveau'] === 'INFO'
                && $pred['stock_actuel'] > $pred['seuil_alerte'] * 3
                && ($pred['jours_restants'] === null || $pred['jours_restants'] > 30)) {
                continue;
            }

            $msg = $this->buildMessage($pred);

            $stmt = $this->db->prepare("
                INSERT INTO pharmacie_alertes_stock
                    (medicament_id, type_alerte, niveau, stock_actuel, stock_sage,
                     consommation_j, jours_restants, seuil_alerte, quantite_commande,
                     montant_estime, message, statut, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'ACTIVE', NOW())
                ON DUPLICATE KEY UPDATE
                    niveau             = VALUES(niveau),
                    type_alerte        = VALUES(type_alerte),
                    stock_actuel       = VALUES(stock_actuel),
                    stock_sage         = VALUES(stock_sage),
                    consommation_j     = VALUES(consommation_j),
                    jours_restants     = VALUES(jours_restants),
                    seuil_alerte       = VALUES(seuil_alerte),
                    quantite_commande  = VALUES(quantite_commande),
                    montant_estime     = VALUES(montant_estime),
                    message            = VALUES(message),
                    statut             = IF(statut = 'RESOLUE', 'ACTIVE', statut),
                    updated_at         = NOW()
            ");
            $stmt->execute([
                $medId, $pred['type_alerte'], $pred['niveau'],
                $pred['stock_actuel'], $pred['stock_sage'], $pred['consommation_j'],
                $pred['jours_restants'], $pred['seuil_alerte'],
                $pred['quantite_commande'], $pred['montant_estime'], $msg,
            ]);

            if ($stmt->rowCount() === 1) $generes++;
            else $maj++;
        }

        $this->resolveImprovedAlertes();

        return [
            'ok'            => true,
            'analyses'      => count($medIds),
            'generes'       => $generes,
            'mis_a_jour'    => $maj,
            'generated_at'  => date('d/m/Y H:i:s'),
        ];
    }

    private function buildMessage(array $p): string
    {
        if ($p['stock_actuel'] <= 0)
            return "{$p['nom']} — RUPTURE DE STOCK (0 unité disponible)";
        if ($p['jours_restants'] !== null && $p['jours_restants'] <= 3)
            return "{$p['nom']} — Rupture dans " . ceil($p['jours_restants']) . " jour(s) (stock : {$p['stock_actuel']})";
        if ($p['jours_restants'] !== null && $p['jours_restants'] <= 7)
            return "{$p['nom']} — Rupture prévue dans " . ceil($p['jours_restants']) . " jours (stock : {$p['stock_actuel']})";
        if ($p['stock_actuel'] <= $p['seuil_alerte'])
            return "{$p['nom']} — Stock sous seuil : {$p['stock_actuel']} / {$p['seuil_alerte']} unités";
        $j = $p['jours_restants'] !== null ? ceil($p['jours_restants']) . ' jours' : 'N/A';
        return "{$p['nom']} — À surveiller : {$p['stock_actuel']} unités (~$j)";
    }

    private function resolveImprovedAlertes(): void
    {
        $this->db->exec("
            UPDATE pharmacie_alertes_stock pas
            JOIN medicaments m ON m.id = pas.medicament_id
            SET pas.statut = 'RESOLUE', pas.updated_at = NOW()
            WHERE pas.statut = 'ACTIVE'
              AND m.quantite > GREATEST(m.seuil_alerte * 3, 20)
              AND (pas.jours_restants IS NULL OR pas.jours_restants > 30)
        ");
    }

    // ─────────────────────────────────────────────────────────────────
    //  LECTURE ALERTES / STATS
    // ─────────────────────────────────────────────────────────────────

    public function getAlertesActives(int $limit = 100): array
    {
        $stmt = $this->db->prepare("
            SELECT pas.*, m.nom, m.ar_ref_sage, m.prix_unitaire,
                   sm.ar_design AS sage_design, sm.as_qte_sto AS stock_sage_map
            FROM pharmacie_alertes_stock pas
            JOIN medicaments m ON m.id = pas.medicament_id
            LEFT JOIN sage_medicaments_map sm ON sm.medicament_id = m.id AND sm.exclure_sync = 0
            WHERE pas.statut = 'ACTIVE'
            ORDER BY FIELD(pas.niveau,'CRITIQUE','DANGER','WARNING','INFO'),
                     COALESCE(pas.jours_restants, 9999) ASC
            LIMIT ?
        ");
        $stmt->execute([$limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getDashboardStats(): array
    {
        $meds = $this->db->query("
            SELECT
              COUNT(*)                                                           AS total_meds,
              COALESCE(SUM(quantite <= 0), 0)                                   AS rupture_totale,
              COALESCE(SUM(seuil_alerte > 0 AND quantite <= seuil_alerte), 0)   AS sous_seuil,
              COALESCE(SUM(quantite <= 0 OR (seuil_alerte > 0 AND quantite <= seuil_alerte * 0.5)), 0) AS critique,
              COALESCE(SUM(prix_unitaire * quantite), 0)                        AS valeur_stock
            FROM medicaments
        ")->fetch(PDO::FETCH_ASSOC);

        $alertesNiveaux = $this->db->query("
            SELECT niveau, COUNT(*) AS nb
            FROM pharmacie_alertes_stock WHERE statut = 'ACTIVE'
            GROUP BY niveau
        ")->fetchAll(PDO::FETCH_KEY_PAIR);

        $lastSync = $this->db->query("
            SELECT created_at, statut, meds_stock_maj, type_operation
            FROM sage_sync_logs ORDER BY id DESC LIMIT 1
        ")->fetch(PDO::FETCH_ASSOC);

        $sageConfig = $this->db->query("
            SELECT type_source, auto_sync_enabled, sync_interval_min, last_sync_at, actif
            FROM sage_config WHERE id = 1 LIMIT 1
        ")->fetch(PDO::FETCH_ASSOC);

        return array_merge($meds, [
            'alertes_critique' => (int)($alertesNiveaux['CRITIQUE'] ?? 0),
            'alertes_danger'   => (int)($alertesNiveaux['DANGER']   ?? 0),
            'alertes_warning'  => (int)($alertesNiveaux['WARNING']  ?? 0),
            'alertes_info'     => (int)($alertesNiveaux['INFO']     ?? 0),
            'last_sync'        => $lastSync  ?: null,
            'sage_config'      => $sageConfig ?: null,
        ]);
    }

    public function getCommandeSuggestion(): array
    {
        $stmt = $this->db->query("
            SELECT pas.medicament_id, pas.quantite_commande, pas.jours_restants,
                   pas.niveau, pas.montant_estime, pas.consommation_j,
                   m.nom, m.ar_ref_sage, m.quantite AS stock_actuel, m.prix_unitaire,
                   sm.ar_design AS sage_design, sm.ar_prix_ven AS prix_sage
            FROM pharmacie_alertes_stock pas
            JOIN medicaments m ON m.id = pas.medicament_id
            LEFT JOIN sage_medicaments_map sm ON sm.medicament_id = m.id AND sm.exclure_sync = 0
            WHERE pas.statut = 'ACTIVE' AND pas.quantite_commande > 0
            ORDER BY FIELD(pas.niveau,'CRITIQUE','DANGER','WARNING','INFO'),
                     COALESCE(pas.jours_restants, 9999) ASC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function acquitterAlerte(int $id): bool
    {
        $stmt = $this->db->prepare("
            UPDATE pharmacie_alertes_stock
            SET statut = 'ACQUITTEE', acquittee_par = ?, acquittee_at = NOW()
            WHERE id = ? AND statut = 'ACTIVE'
        ");
        $stmt->execute([$_SESSION['user_id'] ?? null, $id]);
        return $stmt->rowCount() > 0;
    }

    public function getTopRisques(int $limit = 25): array
    {
        $stmt = $this->db->prepare("
            SELECT pas.*, m.nom, m.ar_ref_sage, m.prix_unitaire,
                   sm.ar_design AS sage_design,
                   COALESCE(sm.as_qte_sto, m.quantite) AS stock_sage_map
            FROM pharmacie_alertes_stock pas
            JOIN medicaments m ON m.id = pas.medicament_id
            LEFT JOIN sage_medicaments_map sm ON sm.medicament_id = m.id AND sm.exclure_sync = 0
            WHERE pas.statut = 'ACTIVE'
            ORDER BY FIELD(pas.niveau,'CRITIQUE','DANGER','WARNING','INFO'),
                     COALESCE(pas.jours_restants, 9999) ASC
            LIMIT ?
        ");
        $stmt->execute([$limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getMouvementsRecents(int $days = 7): array
    {
        $stmt = $this->db->prepare("
            SELECT DATE(op.date_creation) AS jour,
                   COUNT(DISTINCT op.id)  AS nb_ordonnances,
                   SUM(om.quantite)       AS qte_dispensee
            FROM ordonnance_medicaments om
            JOIN ordonnances_pharmacie op ON om.ordonnance_id = op.id
            WHERE op.date_creation >= DATE_SUB(NOW(), INTERVAL ? DAY)
              AND om.ligne_annulee = 0
            GROUP BY DATE(op.date_creation)
            ORDER BY jour ASC
        ");
        $stmt->execute([$days]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
