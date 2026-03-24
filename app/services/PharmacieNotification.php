// Notification temps réel pour la pharmacie
class PharmacieNotification {
    static function nouvelleOrdonnance($ordonnance_id, $patient_nom) {
        // Notification navigateur
        echo "<script>
        if ('Notification' in window && Notification.permission === 'granted') {
            new Notification('Nouvelle ordonnance', {
                body: 'Patient: {$patient_nom}',
                icon: '" . BASE_URL . "public/images/pharmacy-icon.png',
                tag: 'ordonnance-{$ordonnance_id}'
            });
        }
        
        // Son d'alerte
        const audio = new Audio('" . BASE_URL . "public/sounds/notification.mp3');
        audio.play().catch(() => {});
        
        // Badge notification
        const badge = document.querySelector('.pharmacy-badge');
        if (badge) {
            badge.textContent = parseInt(badge.textContent || 0) + 1;
            badge.style.display = 'inline';
        }
        </script>";
    }
    
    static function stockFaible($medicament_nom, $quantite) {
        echo "<script>
        if ('Notification' in window && Notification.permission === 'granted') {
            new Notification('⚠️ Stock faible', {
                body: '{$medicament_nom}: {$quantite} unités restantes',
                icon: '" . BASE_URL . "public/images/warning-icon.png'
            });
        }
        </script>";
    }
}