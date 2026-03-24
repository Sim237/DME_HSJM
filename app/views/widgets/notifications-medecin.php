<?php
// Widget notifications médecin pour la sidebar
require_once __DIR__ . '/../../services/NotificationResultatService.php';

$notificationService = new NotificationResultatService();
$medecin_id = $_SESSION['user_id'] ?? 0;
$notifications = $notificationService->getNotificationsMedecin($medecin_id, true); // Non lues seulement
$nb_notifications = count($notifications);
?>

<div class="card border-success mb-3" id="widget-notifications">
    <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
        <h6 class="mb-0"><i class="bi bi-bell"></i> Notifications</h6>
        <?php if ($nb_notifications > 0): ?>
            <span class="badge bg-danger notification-badge"><?= $nb_notifications ?></span>
        <?php endif; ?>
    </div>
    <div class="card-body p-2">
        <?php if ($nb_notifications > 0): ?>
            <div class="list-group list-group-flush">
                <?php foreach (array_slice($notifications, 0, 3) as $notif): ?>
                <div class="list-group-item p-2 border-0 notification-item" data-id="<?= $notif['id'] ?>">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="flex-grow-1">
                            <small class="fw-bold text-success"><?= htmlspecialchars($notif['titre']) ?></small><br>
                            <small class="text-muted"><?= htmlspecialchars($notif['message']) ?></small><br>
                            <small class="text-muted"><?= date('d/m H:i', strtotime($notif['date_creation'])) ?></small>
                        </div>
                        <div class="btn-group btn-group-sm">
                            <?php if ($notif['demande_id']): ?>
                                <a href="<?= BASE_URL ?>patients/dossier/<?= $notif['patient_id'] ?>?tab=resultats" 
                                   class="btn btn-sm btn-outline-success" 
                                   onclick="marquerLue(<?= $notif['id'] ?>)">
                                    <i class="bi bi-eye"></i>
                                </a>
                            <?php endif; ?>
                            <button class="btn btn-sm btn-outline-secondary" onclick="marquerLue(<?= $notif['id'] ?>)">
                                <i class="bi bi-check"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php if ($nb_notifications > 3): ?>
                <div class="text-center mt-2">
                    <a href="<?= BASE_URL ?>notifications" class="btn btn-sm btn-success">
                        Voir toutes (<?= $nb_notifications ?>)
                    </a>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div class="text-center text-muted">
                <i class="bi bi-check-circle display-6"></i><br>
                <small>Aucune notification</small>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function marquerLue(notificationId) {
    fetch('<?= BASE_URL ?>notifications/marquer-lue', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({id: notificationId})
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            // Retirer visuellement la notification
            const item = document.querySelector(`[data-id="${notificationId}"]`);
            if (item) item.remove();
            
            // Mettre à jour le badge
            const badge = document.querySelector('.notification-badge');
            if (badge) {
                const count = parseInt(badge.textContent) - 1;
                if (count <= 0) {
                    badge.style.display = 'none';
                } else {
                    badge.textContent = count;
                }
            }
        }
    });
}

// Actualisation automatique toutes les 60 secondes
setInterval(function() {
    fetch('<?= BASE_URL ?>notifications/widget')
        .then(r => r.text())
        .then(html => {
            document.getElementById('widget-notifications').outerHTML = html;
        })
        .catch(console.error);
}, 60000);

// Son de notification pour nouvelles notifications
let lastNotificationCount = <?= $nb_notifications ?>;
setInterval(function() {
    fetch('<?= BASE_URL ?>notifications/count')
        .then(r => r.json())
        .then(data => {
            if (data.count > lastNotificationCount) {
                // Nouvelle notification
                const audio = new Audio('<?= BASE_URL ?>public/sounds/notification.mp3');
                audio.play().catch(() => {});
                
                if (Notification.permission === 'granted') {
                    new Notification('Nouveaux résultats disponibles', {
                        body: 'Vous avez de nouveaux résultats de laboratoire',
                        icon: '<?= BASE_URL ?>public/images/lab-results-icon.png'
                    });
                }
            }
            lastNotificationCount = data.count;
        });
}, 30000);
</script>