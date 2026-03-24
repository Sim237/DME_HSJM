<?php

class HospitalisationService {

    public static function analyserCriteresHospitalisation($consultation, $age = null) {
        $criteres = [];
        $score_risque = 0;

        // Critères Anamnestiques
        if (self::detecterPertePoidsRapide($consultation)) {
            $criteres[] = ['type' => 'anamnestique', 'critere' => 'Perte de poids rapide (>2kg/semaine)', 'gravite' => 'elevee'];
            $score_risque += 3;
        }

        if (self::detecterRefusAlimentation($consultation)) {
            $criteres[] = ['type' => 'anamnestique', 'critere' => 'Refus de manger/boire', 'gravite' => 'critique'];
            $score_risque += 4;
        }

        if (self::detecterMalaises($consultation)) {
            $criteres[] = ['type' => 'anamnestique', 'critere' => 'Lipothymie/malaises orthostatiques', 'gravite' => 'elevee'];
            $score_risque += 3;
        }

        if (self::detecterFatigue($consultation)) {
            $criteres[] = ['type' => 'anamnestique', 'critere' => 'Fatigabilité/épuisement', 'gravite' => 'moderee'];
            $score_risque += 2;
        }

        // Critères Cliniques
        $imc_critique = self::verifierIMC($consultation, $age);
        if ($imc_critique) {
            $criteres[] = ['type' => 'clinique', 'critere' => $imc_critique, 'gravite' => 'critique'];
            $score_risque += 4;
        }

        if (self::detecterTroublesCognitifs($consultation)) {
            $criteres[] = ['type' => 'clinique', 'critere' => 'Ralentissement idéique/confusion', 'gravite' => 'elevee'];
            $score_risque += 3;
        }

        $anomalie_cardiaque = self::verifierFrequenceCardiaque($consultation);
        if ($anomalie_cardiaque) {
            $criteres[] = ['type' => 'clinique', 'critere' => $anomalie_cardiaque, 'gravite' => 'critique'];
            $score_risque += 4;
        }

        $anomalie_tension = self::verifierTensionArterielle($consultation);
        if ($anomalie_tension) {
            $criteres[] = ['type' => 'clinique', 'critere' => $anomalie_tension, 'gravite' => 'critique'];
            $score_risque += 4;
        }

        $anomalie_temperature = self::verifierTemperature($consultation);
        if ($anomalie_temperature) {
            $criteres[] = ['type' => 'clinique', 'critere' => $anomalie_temperature, 'gravite' => 'elevee'];
            $score_risque += 3;
        }

        // Déterminer la recommandation
        $recommandation = self::determinerRecommandation($score_risque, $criteres);

        return [
            'criteres' => $criteres,
            'score_risque' => $score_risque,
            'recommandation' => $recommandation
        ];
    }

    private static function detecterPertePoidsRapide($consultation) {
        $motif = strtolower($consultation['motif_consultation'] ?? '');
        $histoire = strtolower($consultation['histoire_maladie'] ?? '');
        return strpos($motif . $histoire, 'perte de poids') !== false ||
               strpos($motif . $histoire, 'amaigrissement') !== false;
    }

    private static function detecterRefusAlimentation($consultation) {
        $motif = strtolower($consultation['motif_consultation'] ?? '');
        $histoire = strtolower($consultation['histoire_maladie'] ?? '');
        return strpos($motif . $histoire, 'refuse') !== false &&
               (strpos($motif . $histoire, 'manger') !== false || strpos($motif . $histoire, 'boire') !== false);
    }

    private static function detecterMalaises($consultation) {
        $motif = strtolower($consultation['motif_consultation'] ?? '');
        $histoire = strtolower($consultation['histoire_maladie'] ?? '');
        $examen = strtolower($consultation['examen_physique'] ?? '');
        return strpos($motif . $histoire . $examen, 'malaise') !== false ||
               strpos($motif . $histoire . $examen, 'lipothymie') !== false ||
               strpos($motif . $histoire . $examen, 'vertige') !== false;
    }

    private static function detecterFatigue($consultation) {
        $motif = strtolower($consultation['motif_consultation'] ?? '');
        $histoire = strtolower($consultation['histoire_maladie'] ?? '');
        return strpos($motif . $histoire, 'fatigue') !== false ||
               strpos($motif . $histoire, 'épuisement') !== false ||
               strpos($motif . $histoire, 'asthénie') !== false;
    }

    private static function verifierIMC($consultation, $age) {
        $poids = floatval($consultation['poids'] ?? 0);
        $taille = floatval($consultation['taille'] ?? 0);

        if ($poids <= 0 || $taille <= 0) return false;

        $imc = $poids / (($taille/100) * ($taille/100));

        if ($age >= 17 && $imc < 14) {
            return "IMC critique < 14 kg/m² (IMC: " . round($imc, 1) . ")";
        } elseif ($age >= 15 && $age <= 16 && $imc < 13.2) {
            return "IMC critique < 13.2 kg/m² (IMC: " . round($imc, 1) . ")";
        } elseif ($age >= 13 && $age <= 14 && $imc < 12.7) {
            return "IMC critique < 12.7 kg/m² (IMC: " . round($imc, 1) . ")";
        }

        return false;
    }

    private static function detecterTroublesCognitifs($consultation) {
        $examen = strtolower($consultation['examen_physique'] ?? '');
        return strpos($examen, 'confusion') !== false ||
               strpos($examen, 'ralentissement') !== false ||
               strpos($examen, 'trouble cognitif') !== false;
    }

    private static function verifierFrequenceCardiaque($consultation) {
        $fc = intval($consultation['frequence_cardiaque'] ?? 0);

        if ($fc > 0 && $fc < 40) {
            return "Bradycardie extrême < 40/min (FC: {$fc}/min)";
        } elseif ($fc > 120) {
            return "Tachycardie > 120/min (FC: {$fc}/min)";
        }

        return false;
    }

    private static function verifierTensionArterielle($consultation) {
        $systolique = intval($consultation['tension_systolique'] ?? 0);
        $diastolique = intval($consultation['tension_diastolique'] ?? 0);

        if ($systolique > 0 && $systolique < 80) {
            return "Hypotension systolique < 80 mmHg (TA: {$systolique}/{$diastolique})";
        } elseif ($systolique < 80 && $diastolique < 50) {
            return "Hypotension sévère < 80/50 mmHg (TA: {$systolique}/{$diastolique})";
        }

        return false;
    }

    private static function verifierTemperature($consultation) {
        $temp = floatval($consultation['temperature'] ?? 0);

        if ($temp > 0 && $temp < 35.5) {
            return "Hypothermie < 35.5°C (T°: {$temp}°C)";
        } elseif ($temp > 38.5) {
            return "Hyperthermie > 38.5°C (T°: {$temp}°C)";
        }

        return false;
    }

    private static function determinerRecommandation($score, $criteres) {
        $critiques = array_filter($criteres, function($c) { return $c['gravite'] === 'critique'; });

        if (count($critiques) >= 2 || $score >= 8) {
            return [
                'niveau' => 'hospitalisation_urgente',
                'message' => 'HOSPITALISATION URGENTE RECOMMANDÉE',
                'couleur' => 'danger',
                'justification' => 'Critères critiques multiples détectés'
            ];
        } elseif (count($critiques) >= 1 || $score >= 5) {
            return [
                'niveau' => 'hospitalisation_recommandee',
                'message' => 'HOSPITALISATION FORTEMENT RECOMMANDÉE',
                'couleur' => 'warning',
                'justification' => 'Critères de gravité présents'
            ];
        } elseif ($score >= 3) {
            return [
                'niveau' => 'surveillance_renforcee',
                'message' => 'SURVEILLANCE RENFORCÉE NÉCESSAIRE',
                'couleur' => 'info',
                'justification' => 'Facteurs de risque identifiés'
            ];
        } else {
            return [
                'niveau' => 'suivi_ambulatoire',
                'message' => 'SUIVI AMBULATOIRE POSSIBLE',
                'couleur' => 'success',
                'justification' => 'Pas de critères d\'hospitalisation'
            ];
        }
    }

    public static function enregistrerDecisionHospitalisation($consultation_id, $decision, $medecin_id, $justification = '') {
        try {
            $db = DataService::getInstance()->getConnection();

            $stmt = $db->prepare("
                INSERT INTO decisions_hospitalisation
                (consultation_id, medecin_id, decision, justification, date_decision)
                VALUES (?, ?, ?, ?, NOW())
                ON DUPLICATE KEY UPDATE
                decision = VALUES(decision),
                justification = VALUES(justification),
                date_decision = NOW()
            ");

            return $stmt->execute([$consultation_id, $medecin_id, $decision, $justification]);
        } catch (Exception $e) {
            error_log("Erreur enregistrement décision hospitalisation: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Nurse assigns lit after hospitaliser request
     */
    public static function assignLitNurse($patient_id, $service_id, $lit_id, $infirmier_id) {
        try {
            $db = DataService::getInstance()->getConnection();
            $db->beginTransaction();

            // Create hospitalisation
            $stmtH = $db->prepare("INSERT INTO hospitalisations (patient_id, service_id, lit_id, statut, infirmier_admission, date_admission) VALUES (?, ?, ?, 'active', ?, NOW())");
            $stmtH->execute([$patient_id, $service_id, $lit_id, $infirmier_id]);
            $hosp_id = $db->lastInsertId();

            // Update lit occupancy
            $db->prepare("UPDATE lits SET occupied_by_patient_id = ?, occupied_since = NOW() WHERE id = ?")
               ->execute([$patient_id, $lit_id]);

            // Update urgences_admissions a_hospitaliser = 0
            $db->prepare("UPDATE urgences_admissions SET a_hospitaliser = 0 WHERE patient_id = ?")
               ->execute([$patient_id]);

            $db->commit();
            return $hosp_id;
        } catch (Exception $e) {
            $db->rollBack();
            error_log("Erreur assign lit nurse: " . $e->getMessage());
            return false;
        }
    }
}
