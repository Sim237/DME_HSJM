<!-- Widget Notifications -->
<div class="notification-widget">
    <button class="btn btn-outline-light position-relative" id="notificationBtn">
        <i class="bi bi-bell"></i>
        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" id="notificationCount" style="display: none;">
            0
        </span>
    </button>
    
    <div class="notification-dropdown" id="notificationDropdown">
        <div class="notification-header">
            <h6>Notifications</h6>
            <button class="btn btn-sm btn-outline-secondary" onclick="markAllAsRead()">
                Tout marquer lu
            </button>
        </div>
        <div class="notification-list" id="notificationList">
            <div class="text-center p-3 text-muted">Aucune notification</div>
        </div>
    </div>
</div>

<style>
.notification-widget {
    position: relative;
}

.notification-dropdown {
    position: absolute;
    top: 100%;
    right: 0;
    width: 350px;
    background: white;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    z-index: 1050;
    display: none;
    max-height: 400px;
    overflow: hidden;
}

.notification-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem;
    border-bottom: 1px solid #dee2e6;
    background: #f8f9fa;
}

.notification-list {
    max-height: 300px;
    overflow-y: auto;
}

.notification-item {
    padding: 0.75rem 1rem;
    border-bottom: 1px solid #f1f3f4;
    cursor: pointer;
    transition: background 0.2s;
}

.notification-item:hover {
    background: #f8f9fa;
}

.notification-item.unread {
    background: #e3f2fd;
    border-left: 3px solid #2196f3;
}

.notification-item.urgent {
    border-left: 3px solid #f44336;
}

.notification-item.high {
    border-left: 3px solid #ff9800;
}

.notification-title {
    font-weight: 500;
    font-size: 0.9rem;
    margin-bottom: 0.25rem;
}

.notification-message {
    font-size: 0.8rem;
    color: #6c757d;
    margin-bottom: 0.25rem;
}

.notification-time {
    font-size: 0.75rem;
    color: #9e9e9e;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const notificationBtn = document.getElementById('notificationBtn');
    const notificationDropdown = document.getElementById('notificationDropdown');
    const notificationCount = document.getElementById('notificationCount');
    const notificationList = document.getElementById('notificationList');
    
    // Toggle dropdown
    notificationBtn.addEventListener('click', function(e) {
        e.stopPropagation();
        notificationDropdown.style.display = 
            notificationDropdown.style.display === 'block' ? 'none' : 'block';
    });
    
    // Fermer dropdown
    document.addEventListener('click', function() {
        notificationDropdown.style.display = 'none';
    });
    
    // Server-Sent Events pour notifications temps réel
    if (typeof(EventSource) !== "undefined") {
        const eventSource = new EventSource(`${BASE_URL}notifications/stream`);
        
        eventSource.onmessage = function(event) {
            const notifications = JSON.parse(event.data);
            updateNotifications(notifications);
        };
        
        eventSource.onerror = function() {
            console.log('Erreur connexion notifications');
        };
    }
    
    function updateNotifications(notifications) {
        if (notifications.length === 0) {
            notificationCount.style.display = 'none';
            notificationList.innerHTML = '<div class="text-center p-3 text-muted">Aucune notification</div>';
            return;
        }
        
        // Mettre à jour le compteur
        notificationCount.textContent = notifications.length;
        notificationCount.style.display = 'block';
        
        // Mettre à jour la liste
        notificationList.innerHTML = notifications.map(notification => `
            <div class="notification-item ${notification.is_read ? '' : 'unread'} ${notification.priority}" 
                 onclick="markAsRead(${notification.id})">
                <div class="notification-title">${notification.title}</div>
                <div class="notification-message">${notification.message}</div>
                <div class="notification-time">${formatTime(notification.created_at)}</div>
            </div>
        `).join('');
        
        // Son pour notifications urgentes
        const urgentNotifications = notifications.filter(n => n.priority === 'urgent');
        if (urgentNotifications.length > 0) {
            playNotificationSound();
        }
    }
    
    function formatTime(timestamp) {
        const date = new Date(timestamp);
        const now = new Date();
        const diff = now - date;
        
        if (diff < 60000) return 'À l\'instant';
        if (diff < 3600000) return Math.floor(diff / 60000) + ' min';
        if (diff < 86400000) return Math.floor(diff / 3600000) + ' h';
        return date.toLocaleDateString();
    }
    
    function playNotificationSound() {
        const audio = new Audio('data:audio/wav;base64,UklGRnoGAABXQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YQoGAACBhYqFbF1fdJivrJBhNjVgodDbq2EcBj+a2/LDciUFLIHO8tiJNwgZaLvt559NEAxQp+PwtmMcBjiR1/LMeSwFJHfH8N2QQAoUXrTp66hVFApGn+DyvmwhBSuBzvLZiTYIG2m98OScTgwOUarm7blmGgU7k9n1unEiBC13yO/eizEIHWq+8+OWT');
        audio.play().catch(() => {}); // Ignorer les erreurs de lecture
    }
    
    // Charger les notifications au démarrage
    loadNotifications();
});

function markAsRead(notificationId) {
    fetch(`${BASE_URL}notifications/mark-read/${notificationId}`, {
        method: 'POST'
    }).then(() => {
        loadNotifications();
    });
}

function markAllAsRead() {
    fetch(`${BASE_URL}notifications/mark-all-read`, {
        method: 'POST'
    }).then(() => {
        loadNotifications();
    });
}

function loadNotifications() {
    fetch(`${BASE_URL}notifications/get`)
        .then(response => response.json())
        .then(notifications => {
            updateNotifications(notifications);
        });
}
</script>