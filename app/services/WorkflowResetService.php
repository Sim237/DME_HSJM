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
 * WorkflowResetService — gestion centralisée des "remises à zéro quotidiennes"
 *
 * Responsabilités :
 *   1. Génération atomique du prochain numéro d'ordre (ticket) avec reset
 *      automatique à 1 dès qu'on change de jour (réutilisable accueil std + PHP).
 *   2. Nettoyage des files d'attente "paramètres" : tout patient resté en
 *      statut PARAMETRES depuis plus de 24h est sorti de la file
 *      (statut_parcours basculé sur ABSENT_24H).
 *
 * Usage :
 *   $svc = new WorkflowResetService($db);
 *   $svc->cleanWaitingListsIfStale();
 *   $num = $svc->getNextOrderNumber(); // reset auto en début de journée
 */
class WorkflowResetService
{
    private PDO $db;

    public function __construct(PDO $db) {
        $this->db = $db;
    }

    // ──────────────────────────────────────────────────────────────
    //  COMPTEUR DE TICKETS — RESET 24H
    // ──────────────────────────────────────────────────────────────

    /**
     * Génère le prochain numéro d'ordre de manière atomique.
     *
     * Si la dernière émission date d'avant aujourd'hui, le compteur
     * repart à 1. Sinon, on incrémente normalement.
     *
     * Implémentation : UPDATE atomique avec CASE + LAST_INSERT_ID() pour
     * récupérer la valeur exacte attribuée à cette session, même si
     * d'autres requêtes concurrentes incrémentent en parallèle.
     *
     * Pas de transaction → safe à appeler dans ou hors d'une transaction
     * existante du code appelant.
     *
     * @return int Le numéro attribué (1 le premier patient du jour)
     */
    public function getNextOrderNumber(): int {
        // S'assurer que la ligne id=1 existe
        $exists = (int)$this->db->query("SELECT COUNT(*) FROM config_sequence WHERE id = 1")
                                 ->fetchColumn();
        if (!$exists) {
            $this->db->prepare(
                "INSERT INTO config_sequence (id, last_number, last_date) VALUES (1, 0, NULL)"
            )->execute();
        }

        // UPDATE atomique :
        //   - si last_date = aujourd'hui → incrémente
        //   - sinon (NULL ou date différente) → repart à 1
        // LAST_INSERT_ID(expression) retourne expression et stocke pour SELECT LAST_INSERT_ID()
        $this->db->exec("
            UPDATE config_sequence
            SET last_number = LAST_INSERT_ID(
                    CASE
                        WHEN last_date = CURDATE() THEN last_number + 1
                        ELSE 1
                    END
                ),
                last_date = CURDATE()
            WHERE id = 1
        ");

        // Récupère la valeur attribuée à CETTE connexion uniquement
        return (int)$this->db->query("SELECT LAST_INSERT_ID()")->fetchColumn();
    }

    // ──────────────────────────────────────────────────────────────
    //  FILE D'ATTENTE PARAMÈTRES — NETTOYAGE 24H
    // ──────────────────────────────────────────────────────────────

    /**
     * Nettoie la file d'attente des bureaux paramètres :
     * tout patient resté en statut PARAMETRES dont la date de mise en file
     * n'est PAS aujourd'hui (ou est NULL pour d'anciens enregistrements)
     * est marqué comme "absent" et retiré de la file.
     *
     * Critère utilisé (par ordre de priorité) :
     *   1. `date_mise_en_parametres` (colonne dédiée — la plus fiable)
     *   2. `updated_at` (fallback)
     *   3. `created_at` (dernier recours)
     *
     * Comportement : on retire de la file tout patient dont
     *   DATE(colonne_choisie) != CURDATE()
     * Cela inclut les patients de jours antérieurs ET ceux dont
     * la date est NULL (anciens enregistrements avant la migration).
     *
     * Idempotent : peut être appelé plusieurs fois sans effet de bord.
     *
     * @return int Nombre de patients retirés
     */
    public function cleanWaitingListsIfStale(): int {
        try {
            // Vérifier si les colonnes nécessaires existent
            $cols = $this->db->query("SHOW COLUMNS FROM patients")
                              ->fetchAll(PDO::FETCH_COLUMN);

            if (!in_array('statut_parcours', $cols, true)) {
                return 0;
            }

            // Construire la liste de colonnes timestamp à utiliser via COALESCE
            // (priorité : date_mise_en_parametres > updated_at > created_at)
            $candidates = ['date_mise_en_parametres', 'updated_at', 'created_at'];
            $available = array_filter($candidates, fn($c) => in_array($c, $cols, true));
            if (empty($available)) {
                return 0;
            }
            $tsExpr = 'COALESCE(' . implode(', ', $available) . ')';

            // Mise à jour : tous les patients en file PARAMETRES dont la date
            // de mise en file n'est pas aujourd'hui sont retirés.
            // ATTENTION : on retire AUSSI ceux dont COALESCE = NULL
            // (anciens enregistrements sans aucune date — corruption ou avant migration)
            $stmt = $this->db->prepare(
                "UPDATE patients
                 SET statut_parcours = 'ABSENT_24H'
                 WHERE statut_parcours = 'PARAMETRES'
                   AND ($tsExpr IS NULL OR DATE($tsExpr) <> CURDATE())"
            );
            $stmt->execute();
            $nb = $stmt->rowCount();

            if ($nb > 0) {
                error_log("[WorkflowResetService] $nb patient(s) retiré(s) de la file PARAMETRES (pas du jour)");
            }
            return $nb;
        } catch (\Throwable $e) {
            error_log('[WorkflowResetService] cleanWaitingListsIfStale failed: ' . $e->getMessage());
            return 0;
        }
    }
}
