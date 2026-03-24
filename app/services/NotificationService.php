<?php
class NotificationService {

    public function sendSMS($phone, $message) {
        // Simulation d'envoi SMS
        error_log("SMS envoyé à {$phone}: {$message}");
        return true;
    }

    public function sendEmail($email, $subject, $message) {
        // Envoi d'email simple
        $headers = "From: noreply@dmehospital.com\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";

        return mail($email, $subject, $message, $headers);
    }

    public function sendAppointmentReminder($patient, $appointment) {
        $date = date('d/m/Y à H:i', strtotime($appointment['date_rdv']));

        // SMS
        if ($patient['telephone']) {
            $smsMessage = "Rappel RDV DME Hospital le {$date}. Merci de confirmer votre présence.";
            $this->sendSMS($patient['telephone'], $smsMessage);
        }

        // Email
        if ($patient['email']) {
            $subject = "Rappel de rendez-vous - DME Hospital";
            $emailMessage = "
            <h2>Rappel de rendez-vous</h2>
            <p>Bonjour {$patient['prenom']} {$patient['nom']},</p>
            <p>Nous vous rappelons votre rendez-vous prévu le <strong>{$date}</strong>.</p>
            <p>Merci de vous présenter 15 minutes avant l'heure prévue.</p>
            <p>Cordialement,<br>L'équipe DME Hospital</p>
            ";
            $this->sendEmail($patient['email'], $subject, $emailMessage);
        }
    }

    public function sendResultsNotification($patient, $results) {
        // SMS
        if ($patient['telephone']) {
            $smsMessage = "Vos résultats d'examens sont disponibles. Consultez votre espace patient ou contactez-nous.";
            $this->sendSMS($patient['telephone'], $smsMessage);
        }

        // Email
        if ($patient['email']) {
            $subject = "Résultats d'examens disponibles - DME Hospital";
            $emailMessage = "
            <h2>Résultats d'examens</h2>
            <p>Bonjour {$patient['prenom']} {$patient['nom']},</p>
            <p>Vos résultats d'examens sont maintenant disponibles.</p>
            <p>Vous pouvez les consulter en vous connectant à votre espace patient ou en contactant notre secrétariat.</p>
            <p>Cordialement,<br>L'équipe DME Hospital</p>
            ";
            $this->sendEmail($patient['email'], $subject, $emailMessage);
        }
    }

    public function scheduleReminders() {
        // Planification des rappels automatiques
        require_once __DIR__ . '/../models/Patient.php';

        $patientModel = new Patient();

        // Rappels RDV (24h avant)
        $tomorrow = date('Y-m-d', strtotime('+1 day'));
        $appointments = $this->getAppointmentsForDate($tomorrow);

        foreach ($appointments as $appointment) {
            $patient = $patientModel->getById($appointment['patient_id']);
            if ($patient) {
                $this->sendAppointmentReminder($patient, $appointment);
            }
        }
    }

    private function getAppointmentsForDate($date) {
        // Simulation - récupération des RDV
        return [
            [
                'id' => 1,
                'patient_id' => 1,
                'date_rdv' => $date . ' 09:00:00',
                'type' => 'consultation'
            ]
        ];
    }

    /**
     * Notify nurses for hospitalisation request
     */
    public function notifyHospitaliser($patient_id, $medecin_id) {
        $db = (new Database())->getConnection();

        $stmt = $db->prepare("SELECT u.id, u.nom, u.prenom FROM users u WHERE u.role = 'INFIRMIER'");
        $stmt->execute();
        $infirmieres = $stmt->fetchAll();

        $patient_stmt = $db->prepare("SELECT nom, prenom FROM patients WHERE id = ?");
        $patient_stmt->execute([$patient_id]);
        $patient = $patient_stmt->fetch();

        $medecin_stmt = $db->prepare("SELECT nom FROM users WHERE id = ?");
        $medecin_stmt->execute([$medecin_id]);
        $medecin = $medecin_stmt->fetchColumn();

        foreach ($infirmieres as $inf) {
            $message = "🚨 À HOSPITALISER: {$patient['nom']} {$patient['prenom']} (Dr. {$medecin}) - Dashboard clignotant!";

            // Insert DB notification
            $db->prepare("INSERT INTO notifications_medecin (medecin_id, patient_id, type, titre, message, lu) VALUES (?, ?, 'HOSPITALISER', 'À Hospitaliser Urgent', ?, 0)")
               ->execute([$inf['id'], $patient_id, $message]);

            // Optional SMS/email
            if ($inf['telephone']) $this->sendSMS($inf['telephone'], $message);
        }
    }
}
?>

