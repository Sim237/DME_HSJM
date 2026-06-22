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


class LaboratoireService {
    private $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    public function getExamensDisponibles($categorie = null) {
        $sql = "SELECT * FROM examens_laboratoire WHERE disponible = 1";
        if ($categorie) {
            $sql .= " AND categorie = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$categorie]);
        } else {
            $sql .= " ORDER BY categorie, nom";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
        }
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function creerDemandeExamens($consultation_id, $examens) {
        try {
            $this->db->beginTransaction();

            // Récupérer patient_id et medecin_id depuis la consultation
            $stmtC = $this->db->prepare("SELECT patient_id, medecin_id FROM consultations WHERE id = ?");
            $stmtC->execute([$consultation_id]);
            $consult = $stmtC->fetch(PDO::FETCH_ASSOC);
            $patient_id = $consult['patient_id'] ?? null;
            $medecin_id = $consult['medecin_id'] ?? null;

            // Créer la demande
            $stmt = $this->db->prepare("
                INSERT INTO demandes_laboratoire (consultation_id, patient_id, medecin_id, statut, date_creation)
                VALUES (?, ?, ?, 'EN_ATTENTE', NOW())
            ");
            $stmt->execute([$consultation_id, $patient_id, $medecin_id]);
            $demande_id = $this->db->lastInsertId();

            // Ajouter les examens
            foreach ($examens as $examen) {
                $urgent = ($examen['urgent'] === 'true' || $examen['urgent'] === true || $examen['urgent'] === 1) ? 1 : 0;
                $a_jeun = ($examen['a_jeun'] === 'true' || $examen['a_jeun'] === true || $examen['a_jeun'] === 1) ? 1 : 0;

                $stmt = $this->db->prepare("
                    INSERT INTO demande_examens (demande_id, examen_id, urgent, a_jeun, instructions)
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $demande_id,
                    $examen['examen_id'],
                    $urgent,
                    $a_jeun,
                    $examen['instructions'] ?? ''
                ]);
            }

            $this->db->commit();

            // ── Notifier les laborantins/techniciens du laboratoire ──
            try {
                require_once __DIR__ . '/NotificationCenter.php';
                $center = new NotificationCenter($this->db);

                // Récupérer le nom du patient et du médecin pour le message
                $stmtInfo = $this->db->prepare("
                    SELECT p.nom AS p_nom, p.prenom AS p_prenom, p.dossier_numero,
                           u.nom AS m_nom, u.prenom AS m_prenom
                    FROM patients p, users u
                    WHERE p.id = ? AND u.id = ?
                ");
                $stmtInfo->execute([$patient_id, $medecin_id]);
                $info = $stmtInfo->fetch(PDO::FETCH_ASSOC);

                $hasUrgent = false;
                foreach ($examens as $e) {
                    if (($e['urgent'] ?? false) === true || ($e['urgent'] ?? '') === 'true' || ($e['urgent'] ?? 0) == 1) {
                        $hasUrgent = true; break;
                    }
                }

                $titre = $hasUrgent ? '⚡ Demande LABO urgente' : 'Nouvelle demande LABO';
                $msg = "Patient {$info['p_nom']} {$info['p_prenom']} — Dr. {$info['m_nom']} — "
                     . count($examens) . " examen(s) à effectuer";

                foreach (['LABORANTIN', 'TECHNICIEN_LABO', 'SECRETAIRE_LABO'] as $role) {
                    $center->notifyByRole($role, [
                        'category' => NotificationCenter::CAT_LABO,
                        'title'    => $titre,
                        'message'  => $msg,
                        'link'     => 'laboratoire',
                        'priority' => $hasUrgent ? 'urgent' : 'high',
                        'meta'     => ['demande_id' => (int)$demande_id, 'patient_id' => (int)$patient_id],
                    ]);
                }
            } catch (\Throwable $e) {
                error_log('[LaboratoireService] notify failed: ' . $e->getMessage());
            }

            return $demande_id;

        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    public function getDemandesEnAttente() {
    $stmt = $this->db->prepare("
        SELECT d.*, p.nom, p.prenom, p.dossier_numero,
               u.nom as medecin_nom, u.prenom as medecin_prenom,
               t.nom as technicien_nom, t.prenom as technicien_prenom,
               COUNT(de.id) as nb_examens,
               -- COALESCE permet d'avoir 0 au lieu de vide/null
               COALESCE(SUM(CASE WHEN de.urgent = 1 THEN 1 ELSE 0 END), 0) as nb_urgents
        FROM demandes_laboratoire d
        JOIN patients p ON d.patient_id = p.id
        JOIN users u ON d.medecin_id = u.id
        LEFT JOIN users t ON d.technicien_id = t.id
        LEFT JOIN demande_examens de ON d.id = de.demande_id
        WHERE d.statut IN (
            'EN_ATTENTE', 'PRELEVEMENTS_EFFECTUES', 'EN_ANALYSE',
            'DISPATCHE', 'RESULTATS_SOUMIS'
        )
        GROUP BY d.id
        ORDER BY d.date_creation ASC
    ");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

    public function getStatistiques() {
        $stats = [];

        // Demandes par statut
        $stmt = $this->db->prepare("
            SELECT statut, COUNT(*) as nb
            FROM demandes_laboratoire
            WHERE DATE(date_creation) = CURDATE()
            GROUP BY statut
        ");
        $stmt->execute();
        $stats['demandes_jour'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Examens urgents
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as nb_urgents
            FROM demande_examens de
            JOIN demandes_laboratoire d ON de.demande_id = d.id
            WHERE de.urgent = 1 AND d.statut != 'VALIDES'
        ");
        $stmt->execute();
        $stats['urgents'] = $stmt->fetch()['nb_urgents'];

        // Délai moyen
        $stmt = $this->db->prepare("
            SELECT AVG(TIMESTAMPDIFF(HOUR, d.date_creation, d.date_resultats)) as delai_moyen
            FROM demandes_laboratoire d
            WHERE d.statut = 'VALIDES' AND DATE(d.date_resultats) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
        ");
        $stmt->execute();
        $stats['delai_moyen'] = round($stmt->fetch()['delai_moyen'] ?? 0, 1);

        return $stats;
    }

   public function getDemandeComplete($demande_id) {
    $stmt = $this->db->prepare("
        SELECT d.*,
               p.nom, p.prenom, p.dossier_numero, p.date_naissance, p.sexe,
               u.nom as medecin_nom, u.prenom as medecin_prenom,
               t.nom as technicien_nom, t.prenom as technicien_prenom
        FROM demandes_laboratoire d
        LEFT JOIN patients p ON d.patient_id = p.id
        LEFT JOIN users u ON d.medecin_id = u.id
        LEFT JOIN users t ON d.technicien_id = t.id
        WHERE d.id = ?
    ");
    $stmt->execute([$demande_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    return $result ? $result : false;
}

    public function getExamensParDemande($demande_id) {
        $stmt = $this->db->prepare("
            SELECT de.*, el.nom, el.categorie, el.type_prelevement, el.delai_rendu_heures,
                   el.unite, el.valeur_normale_min, el.valeur_normale_max, el.a_jeun_requis
            FROM demande_examens de
            JOIN examens_laboratoire el ON de.examen_id = el.id
            WHERE de.demande_id = ?
            ORDER BY el.categorie, el.nom
        ");
        $stmt->execute([$demande_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function sauvegarderResultats($data) {
        try {
            $this->db->beginTransaction();

            $demande_id      = $data['demande_id'];
            $resultats       = $data['resultats'] ?? [];
            $technicien_id   = $_SESSION['user_id'] ?? 1;
            $isBrouillon     = !empty($data['brouillon']);
            $nbPiecesJointes = (int)($data['_nb_pieces_jointes'] ?? 0);

            // Filtrer les examens "vides" : ne traiter que ceux ayant au
            // moins une donnée (résultat texte, valeur numérique ou interprétation).
            $resultatsValides = [];
            foreach ($resultats as $examen_id => $r) {
                $hasData = !empty(trim((string)($r['resultat'] ?? '')))
                        || (isset($r['valeur_numerique']) && $r['valeur_numerique'] !== '')
                        || !empty(trim((string)($r['interpretation'] ?? '')));
                if ($hasData) $resultatsValides[$examen_id] = $r;
            }

            // ✅ Validation assouplie : on accepte la sauvegarde si AU MOINS UNE
            // des conditions est vraie : résultats saisis, pièces jointes,
            // ou mode brouillon. Le technicien peut donc valider quand seuls
            // les fichiers PDF/images font foi (pas besoin de tout saisir).
            if (empty($resultatsValides) && $nbPiecesJointes === 0 && !$isBrouillon) {
                return [
                    'success' => false,
                    'message' => 'Aucun résultat saisi et aucune pièce jointe. Au moins une donnée est requise pour valider.'
                ];
            }

            foreach ($resultatsValides as $examen_id => $resultat) {
                // Vérifier que l'examen existe
                $stmt = $this->db->prepare("SELECT id FROM demande_examens WHERE id = ? AND demande_id = ?");
                $stmt->execute([$examen_id, $demande_id]);
                if (!$stmt->fetch()) {
                    continue; // Ignorer les examens inexistants
                }

                // Mettre à jour l'examen
                $stmt = $this->db->prepare("
                    UPDATE demande_examens
                    SET resultat = ?, valeur_numerique = ?, interpretation = ?, statut = 'SOUMIS', date_soumission = NOW()
                    WHERE id = ?
                ");
                $valNum = $resultat['valeur_numerique'] ?? null;
                $valNum = ($valNum !== null && $valNum !== '') ? (float)$valNum : null;
                $stmt->execute([
                    $resultat['resultat'] ?? '',
                    $valNum,
                    $resultat['interpretation'] ?? '',
                    $examen_id
                ]);

                // Ajouter aux résultats patient
                $this->ajouterResultatPatient($demande_id, $examen_id, $resultat);
            }

            // Compter les examens sans résultat (utile pour le statut et le message)
            $nbEnAttente = 0;

            // Mettre à jour le statut de la demande en validation finale.
            // Règle : si TOUS les examens ont un résultat (SOUMIS ou VALIDE) →
            //   RESULTATS_PRETS (le biologiste peut valider, demande quitte la file).
            // Sinon → EN_ANALYSE (résultats partiels, demande reste dans la file
            //   d'attente pour que le technicien complète les examens manquants).
            if (!$isBrouillon) {
                $stmtCount = $this->db->prepare("
                    SELECT COUNT(*) FROM demande_examens
                    WHERE demande_id = ? AND statut NOT IN ('SOUMIS','VALIDE')
                ");
                $stmtCount->execute([$demande_id]);
                $nbEnAttente = (int)$stmtCount->fetchColumn();

                if ($nbEnAttente === 0) {
                    // Tous les résultats sont disponibles → prêt pour validation biologiste
                    $this->db->prepare("
                        UPDATE demandes_laboratoire
                        SET statut = 'RESULTATS_PRETS', date_resultats = NOW(), technicien_id = ?
                        WHERE id = ?
                    ")->execute([$technicien_id, $demande_id]);
                } else {
                    // Résultats partiels → reste visible dans la file d'attente
                    $this->db->prepare("
                        UPDATE demandes_laboratoire
                        SET statut = 'EN_ANALYSE', technicien_id = ?
                        WHERE id = ? AND statut NOT IN ('RESULTATS_PRETS','VALIDES')
                    ")->execute([$technicien_id, $demande_id]);
                }
            }

            $this->db->commit();

            // Mettre à jour le bulletin auto-créé avec les résultats partiels ou complets
            if (!$isBrouillon) {
                $this->majBulletinResultats($demande_id);
            }

            // Notifier le médecin :
            //  - si TOUS les résultats sont prêts : notification "Résultats complets"
            //  - si résultats partiels : notification "Résultats partiels disponibles"
            if (!$isBrouillon) {
                $this->notifierMedecin($demande_id, $nbEnAttente === 0);
            }

            $msg = $isBrouillon
                ? 'Brouillon enregistré.'
                : 'Résultats sauvegardés — ' . count($resultatsValides) . ' examen(s) renseigné(s)'
                  . ($nbEnAttente > 0 ? " ($nbEnAttente examen(s) encore en attente)" : ' — Tous les résultats sont disponibles')
                  . ($nbPiecesJointes > 0 ? ", $nbPiecesJointes pièce(s) jointe(s)" : '')
                  . '.';

            return ['success' => true, 'message' => $msg];

        } catch (Exception $e) {
            $this->db->rollback();
            error_log('Erreur sauvegarde résultats: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Erreur lors de la sauvegarde: ' . $e->getMessage()];
        }
    }

    public function validerResultats($demande_id, $biologiste_id) {
        try {
            $stmt = $this->db->prepare("
                UPDATE demandes_laboratoire
                SET statut = 'VALIDES', biologiste_id = ?
                WHERE id = ?
            ");
            $stmt->execute([$biologiste_id, $demande_id]);

            return ['success' => true];

        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function mettreAJourStatuts($data) {
        try {
            $this->db->beginTransaction();

            $demande_id = $data['demande_id'];
            $statuts = $data['statuts'] ?? [];
            $notes = $data['notes'] ?? '';

            foreach ($statuts as $examen_id => $statut) {
                $stmt = $this->db->prepare("UPDATE demande_examens SET statut = ? WHERE id = ?");
                $stmt->execute([$statut, $examen_id]);
            }

            if ($notes) {
                $stmt = $this->db->prepare("UPDATE demandes_laboratoire SET notes = ? WHERE id = ?");
                $stmt->execute([$notes, $demande_id]);
            }

            $this->db->commit();
            return ['success' => true];

        } catch (Exception $e) {
            $this->db->rollback();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function validerPrelevement($examen_id, $technicien_id) {
        try {
            $stmt = $this->db->prepare("
                UPDATE demande_examens
                SET statut = 'PRELEVE', date_prelevement = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$examen_id]);

            return ['success' => true];

        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    private function ajouterResultatPatient($demande_id, $examen_id, $resultat) {
        // Récupérer infos demande et examen
        $stmt = $this->db->prepare("
            SELECT d.*, c.patient_id, c.medecin_id, el.nom as nom_examen, el.unite,
                   el.valeur_normale_min, el.valeur_normale_max
            FROM demandes_laboratoire d
            JOIN consultations c ON d.consultation_id = c.id
            JOIN demande_examens de ON d.id = de.demande_id
            JOIN examens_laboratoire el ON de.examen_id = el.id
            WHERE d.id = ? AND de.id = ?
        ");
        $stmt->execute([$demande_id, $examen_id]);
        $info = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$info) return;

        // Vérifier si anormal
        $anormal = false;
        if ($resultat['valeur_numerique'] && $info['valeur_normale_min'] && $info['valeur_normale_max']) {
            $valeur = floatval($resultat['valeur_numerique']);
            $anormal = ($valeur < $info['valeur_normale_min'] || $valeur > $info['valeur_normale_max']);
        }

        $stmt = $this->db->prepare("
            INSERT INTO patient_resultats_labo
            (patient_id, demande_id, examen_id, nom_examen, resultat, valeur_numerique,
             unite, valeur_normale_min, valeur_normale_max, interpretation, anormal, medecin_prescripteur_id)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $info['patient_id'],
            $demande_id,
            $examen_id,
            $info['nom_examen'],
            $resultat['resultat'] ?? '',
            ($resultat['valeur_numerique'] !== null && $resultat['valeur_numerique'] !== '') ? (float)$resultat['valeur_numerique'] : null,
            $info['unite'],
            $info['valeur_normale_min'],
            $info['valeur_normale_max'],
            $resultat['interpretation'] ?? '',
            $anormal ? 1 : 0,
            $info['medecin_id']
        ]);
    }

    /**
     * @param bool $complet true = tous les résultats prêts, false = résultats partiels
     */
    private function notifierMedecin($demande_id, bool $complet = true) {
        try {
            require_once __DIR__ . '/NotificationCenter.php';
            $stmt = $this->db->prepare("
                SELECT d.medecin_id, d.patient_id,
                       p.nom AS p_nom, p.prenom AS p_prenom, p.dossier_numero
                FROM demandes_laboratoire d
                JOIN patients p ON p.id = d.patient_id
                WHERE d.id = ?
            ");
            $stmt->execute([$demande_id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($row && $row['medecin_id']) {
                $titre   = $complet
                    ? 'Résultats labo disponibles'
                    : 'Résultats partiels — bilan en cours';
                $message = "Patient {$row['p_nom']} {$row['p_prenom']} (#{$row['dossier_numero']}) — "
                         . ($complet ? 'tous les résultats sont prêts.' : 'résultats partiels disponibles, bilan incomplet.');

                (new NotificationCenter($this->db))->notify((int)$row['medecin_id'], [
                    'category' => NotificationCenter::CAT_BILAN,
                    'title'    => $titre,
                    'message'  => $message,
                    'link'     => 'laboratoire/imprimer-resultats/' . (int)$demande_id,
                    'priority' => $complet ? 'high' : 'normal',
                    'meta'     => ['demande_id' => (int)$demande_id],
                ]);
            }
        } catch (\Throwable $e) {
            error_log('[LaboratoireService] notify medecin: ' . $e->getMessage());
        }

        // Service legacy (email/SMS) — uniquement pour les résultats complets
        if ($complet) {
            try {
                require_once __DIR__ . '/NotificationResultatService.php';
                (new NotificationResultatService())->notifierResultatDisponible($demande_id);
            } catch (\Throwable $e) { /* ignorer */ }
        }
    }

    public function getExamensParCategorie() {
        $stmt = $this->db->prepare("
            SELECT categorie, COUNT(*) as nb_examens
            FROM examens_laboratoire
            WHERE disponible = 1
            GROUP BY categorie
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function verifierDisponibiliteExamen($examen_id) {
        $stmt = $this->db->prepare("SELECT disponible, nom FROM examens_laboratoire WHERE id = ?");
        $stmt->execute([$examen_id]);
        $examen = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$examen) {
            return ['disponible' => false, 'message' => 'Examen introuvable'];
        }

        if (!$examen['disponible']) {
            return ['disponible' => false, 'message' => "Examen {$examen['nom']} temporairement indisponible"];
        }

        return ['disponible' => true, 'message' => 'Examen disponible'];
    }

    public function getResultatsParDemande($demande_id) {
        $stmt = $this->db->prepare("
            SELECT prl.*, el.nom as nom_examen, el.unite, el.valeur_normale_min, el.valeur_normale_max
            FROM patient_resultats_labo prl
            LEFT JOIN examens_laboratoire el ON prl.examen_id = el.id
            WHERE prl.demande_id = ?
            ORDER BY el.categorie, el.nom
        ");
        $stmt->execute([$demande_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function majBulletinResultats(int $demande_id): void {
        try {
            // Trouver le bulletin lié à cette demande
            $stmt = $this->db->prepare("SELECT id FROM bulletins_examens WHERE demande_id = ? AND type = 'laboratoire' LIMIT 1");
            $stmt->execute([$demande_id]);
            $bulletin = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$bulletin) return;

            // Récupérer les résultats structurés
            $examens = $this->getExamensParDemande($demande_id);
            $lignes = [];
            foreach ($examens as $e) {
                $nom = $e['nom'] ?? $e['nom_examen'] ?? '';
                $res = $e['resultat'] ?? '';
                $unite = $e['unite'] ?? '';
                $lignes[] = $nom . ($res !== '' ? ': ' . $res . ($unite ? ' ' . $unite : '') : '');
            }
            $texte = implode("\n", $lignes);

            $this->db->prepare("UPDATE bulletins_examens SET resultats = ?, statut = 'RESULTATS_RECUS' WHERE id = ?")
                ->execute([$texte, $bulletin['id']]);
        } catch (Exception $e) {
            error_log('[MajBulletin] ' . $e->getMessage());
        }
    }
}