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

/* ============================================================================
   COMPOSANT CLOCHE DE NOTIFICATIONS — partial réutilisable
   Inclure ce fichier dans le header (ou directement dans une vue) :
       <?php require_once __DIR__ . '/../layouts/notification_bell.php'; ?>
   ============================================================================
   Ce composant :
   - affiche une cloche dans la topbar avec badge clignotant (count non lus)
   - panneau dropdown avec les notifications non lues
   - polling toutes les 25s vers /notifications/poll
   - toast en bas-droite quand de nouvelles notifs arrivent (entre 2 polls)
   - lien clic → marque comme lue + redirige vers l'action
   ============================================================================ */
if (empty($_SESSION['user_id'])) return; // pas de cloche si non connecté
?>

<style>
/* ── Cloche FLOTTANTE (bas-droite, toutes pages) ──────────────── */
/* Positionnée en bas pour éviter tout conflit avec les boutons
   "Déconnexion", "Nouvelle admission", etc. typiquement en haut-droite */
.nc-bell {
    position: fixed;
    bottom: 24px; right: 24px;
    width: 56px; height: 56px;
    border-radius: 50%;
    border: none;
    background: linear-gradient(135deg, #3b82f6, #1d4ed8);
    color: #fff;
    cursor: pointer;
    display: inline-flex; align-items: center; justify-content: center;
    transition: all .2s;
    z-index: 10000;
    box-shadow: 0 8px 24px rgba(29, 78, 216, .35);
}
.nc-bell:hover {
    background: linear-gradient(135deg, #2563eb, #1e40af);
    transform: translateY(-2px) scale(1.04);
    box-shadow: 0 12px 32px rgba(29, 78, 216, .50);
}
.nc-bell .bi { font-size: 1.45rem; }
@media (max-width: 768px) {
    .nc-bell { bottom: 18px; right: 18px; width: 50px; height: 50px; }
    .nc-bell .bi { font-size: 1.3rem; }
}

.nc-badge {
    position: absolute;
    top: -4px; right: -4px;
    min-width: 22px; height: 22px;
    background: linear-gradient(135deg, #ef4444, #dc2626);
    color: #fff;
    border-radius: 11px;
    font-size: .72rem; font-weight: 900;
    display: flex; align-items: center; justify-content: center;
    padding: 0 6px;
    border: 2.5px solid #fff;
    box-shadow: 0 0 0 0 rgba(239, 68, 68, .7);
    animation: nc-pulse 2s infinite;
}
@keyframes nc-pulse {
    0%   { box-shadow: 0 0 0 0    rgba(239, 68, 68, .7); }
    50%  { box-shadow: 0 0 0 7px  rgba(239, 68, 68, 0);  }
    100% { box-shadow: 0 0 0 0    rgba(239, 68, 68, 0);  }
}
.nc-badge.hidden { display: none; }

/* ── Panneau dropdown (s'ouvre vers le HAUT depuis la cloche) ──── */
.nc-panel {
    position: fixed;
    bottom: 92px; right: 24px;
    width: 380px; max-width: calc(100vw - 32px);
    max-height: 70vh;
    background: #fff;
    border-radius: 14px;
    box-shadow: 0 16px 48px rgba(0,0,0,.22);
    border: 1px solid #e5e7eb;
    z-index: 9999;
    display: none;
    flex-direction: column;
    overflow: hidden;
    animation: nc-slidein .22s ease-out;
}
.nc-panel.open { display: flex; }
@keyframes nc-slidein {
    from { opacity: 0; transform: translateY(8px); }
    to   { opacity: 1; transform: translateY(0); }
}
@media (max-width: 768px) {
    .nc-panel { bottom: 80px; right: 14px; }
}

/* Petite flèche reliant le panneau à la cloche */
.nc-panel::after {
    content: '';
    position: absolute;
    bottom: -8px; right: 24px;
    width: 16px; height: 16px;
    background: #fff;
    border-right: 1px solid #e5e7eb;
    border-bottom: 1px solid #e5e7eb;
    transform: rotate(45deg);
}

.nc-panel-head {
    padding: 14px 18px;
    border-bottom: 1px solid #f1f5f9;
    display: flex; justify-content: space-between; align-items: center;
}
.nc-panel-title { font-weight: 800; color: #0f172a; font-size: .95rem; }
.nc-panel-mark {
    background: none; border: none;
    color: #3b82f6; font-size: .78rem; font-weight: 600;
    cursor: pointer; padding: 4px 8px; border-radius: 6px;
}
.nc-panel-mark:hover { background: #eff6ff; }

.nc-panel-body {
    flex: 1; overflow-y: auto;
}
.nc-empty {
    text-align: center; padding: 40px 20px;
    color: #94a3b8; font-size: .88rem;
}
.nc-empty .bi { font-size: 2.2rem; opacity: .4; display: block; margin-bottom: 8px; }

.nc-item {
    padding: 12px 16px;
    border-bottom: 1px solid #f1f5f9;
    cursor: pointer;
    display: flex; gap: 12px;
    transition: background .15s;
}
.nc-item:hover { background: #f8fafc; }
.nc-item:last-child { border-bottom: none; }
.nc-item-icon {
    width: 36px; height: 36px;
    border-radius: 50%;
    background: #eff6ff;
    color: #3b82f6;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
    font-size: 1rem;
}
.nc-item-icon.cat-CONSULTATION { background:#eff6ff; color:#3b82f6; }
.nc-item-icon.cat-SOIN         { background:#fef2f2; color:#ef4444; }
.nc-item-icon.cat-BILAN        { background:#f0fdf4; color:#16a34a; }
.nc-item-icon.cat-LABO         { background:#fefce8; color:#ca8a04; }
.nc-item-icon.cat-IMAGERIE     { background:#f0f9ff; color:#0284c7; }
.nc-item-icon.cat-PHARMACIE    { background:#fdf4ff; color:#a21caf; }
.nc-item-icon.cat-RDV          { background:#fdf2f8; color:#db2777; }
.nc-item-icon.cat-HOSPIT       { background:#eef2ff; color:#4f46e5; }
.nc-item-icon.cat-URGENCE      { background:#fee2e2; color:#dc2626; }
.nc-item-icon.priority-urgent  { background:#fee2e2; color:#dc2626;
                                  animation: nc-icon-pulse 1s infinite; }
@keyframes nc-icon-pulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.12); }
}

.nc-item-body { flex: 1; min-width: 0; }
.nc-item-title { font-weight: 700; color: #0f172a; font-size: .85rem; }
.nc-item-msg {
    font-size: .78rem; color: #64748b; margin-top: 2px;
    line-height: 1.4;
    overflow: hidden; text-overflow: ellipsis;
    display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;
}
.nc-item-time {
    font-size: .7rem; color: #94a3b8;
    margin-top: 4px;
}

.nc-panel-foot {
    border-top: 1px solid #f1f5f9;
    padding: 10px;
    text-align: center;
}
.nc-panel-foot a {
    color: #3b82f6; font-size: .82rem; font-weight: 600;
    text-decoration: none;
}
.nc-panel-foot a:hover { text-decoration: underline; }

/* ── Toast nouvelle notification (bas-gauche pour ne pas masquer la cloche) ── */
.nc-toast {
    position: fixed;
    bottom: 24px; left: 24px;
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 12px 40px rgba(0,0,0,.18);
    border-left: 4px solid #3b82f6;
    padding: 14px 18px 14px 14px;
    width: 360px; max-width: calc(100vw - 32px);
    z-index: 10001;
    cursor: pointer;
    transform: translateX(-120%);
    transition: transform .3s ease-out;
    display: flex; gap: 12px;
}
.nc-toast.show { transform: translateX(0); }
.nc-toast.priority-urgent { border-left-color: #dc2626; }
.nc-toast.priority-high   { border-left-color: #ea580c; }
.nc-toast .nc-item-icon { width: 40px; height: 40px; }
</style>

<!-- Bouton cloche (à insérer dans la topbar — id pour ciblage) -->
<button id="ncBell" class="nc-bell <?= $ncBellLight ?? false ? 'nc-bell-light' : '' ?>"
        type="button" onclick="ncTogglePanel(event)" title="Notifications">
    <i class="bi bi-bell-fill"></i>
    <span id="ncBadge" class="nc-badge hidden">0</span>
</button>

<!-- Panneau dropdown -->
<div id="ncPanel" class="nc-panel" onclick="event.stopPropagation();">
    <div class="nc-panel-head">
        <div class="nc-panel-title">
            <i class="bi bi-bell-fill me-1" style="color:#3b82f6;"></i> Notifications
        </div>
        <button class="nc-panel-mark" onclick="ncMarkAllRead()">
            <i class="bi bi-check2-all me-1"></i>Tout marquer lu
        </button>
    </div>
    <div class="nc-panel-body" id="ncPanelBody">
        <div class="nc-empty">
            <i class="bi bi-bell-slash"></i>
            Aucune notification
        </div>
    </div>
    <div class="nc-panel-foot">
        <a href="<?= BASE_URL ?>notifications/all">
            <i class="bi bi-clock-history me-1"></i>Voir l'historique complet
        </a>
    </div>
</div>

<!-- Audio (silencieux par défaut, déclenché à la 1ère interaction utilisateur) -->
<audio id="ncSound" preload="auto" style="display:none;">
    <source src="data:audio/wav;base64,UklGRl9vT19XQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YQAAAAA=" type="audio/wav">
</audio>

<script>
(function() {
    const NC_BURL = '<?= BASE_URL ?>';
    const NC_POLL_INTERVAL = 25000; // 25 secondes
    let ncLastIds = new Set();
    let ncFirstPoll = true;
    let ncSoundUnlocked = false;

    // Déverrouille le son après 1ère interaction (politique des navigateurs)
    document.addEventListener('click', () => { ncSoundUnlocked = true; }, { once: true });

    // Toggle du panneau
    window.ncTogglePanel = function(e) {
        e?.stopPropagation();
        const panel = document.getElementById('ncPanel');
        panel.classList.toggle('open');
        if (panel.classList.contains('open')) ncRender();
    };

    // Fermeture au clic dehors
    document.addEventListener('click', (e) => {
        const panel = document.getElementById('ncPanel');
        const bell = document.getElementById('ncBell');
        if (!panel || !bell) return;
        if (!panel.contains(e.target) && !bell.contains(e.target)) {
            panel.classList.remove('open');
        }
    });

    // Polling
    let ncItems = [];
    let ncCount = 0;

    function ncFetch() {
        fetch(NC_BURL + 'notifications/poll', { credentials: 'same-origin' })
            .then(r => r.json())
            .then(data => {
                ncItems = data.items || [];
                ncCount = data.count || 0;
                ncUpdateBadge();
                ncRender();

                // Détecter les nouveautés (pour toast)
                if (!ncFirstPoll) {
                    const newItems = ncItems.filter(i => !ncLastIds.has(i.id));
                    newItems.forEach(item => ncShowToast(item));
                }
                ncLastIds = new Set(ncItems.map(i => i.id));
                ncFirstPoll = false;
            })
            .catch(() => { /* silencieux */ });
    }

    function ncUpdateBadge() {
        const badge = document.getElementById('ncBadge');
        if (!badge) return;
        if (ncCount > 0) {
            badge.textContent = ncCount > 99 ? '99+' : ncCount;
            badge.classList.remove('hidden');
        } else {
            badge.classList.add('hidden');
        }
    }

    function ncRender() {
        const body = document.getElementById('ncPanelBody');
        if (!body) return;
        if (!ncItems.length) {
            body.innerHTML = '<div class="nc-empty"><i class="bi bi-bell-slash"></i>Aucune notification</div>';
            return;
        }
        body.innerHTML = ncItems.map(it => {
            const cat = (it.category || 'INFO').toUpperCase();
            const icon = it.icon || ncIconForCategory(cat);
            const prioClass = it.priority === 'urgent' ? 'priority-urgent' : '';
            const link = it.link ? (it.link.startsWith('http') ? it.link
                                    : (it.link.startsWith('/') ? it.link : NC_BURL + it.link.replace(/^\//,'')))
                                  : '#';
            return `
            <div class="nc-item" onclick="ncHandleClick(event, ${it.id}, '${ncEsc(link)}')">
                <div class="nc-item-icon cat-${cat} ${prioClass}">
                    <i class="bi ${icon}"></i>
                </div>
                <div class="nc-item-body">
                    <div class="nc-item-title">${ncEsc(it.title)}</div>
                    <div class="nc-item-msg">${ncEsc(it.message)}</div>
                    <div class="nc-item-time"><i class="bi bi-clock me-1"></i>${ncEsc(it.relative || '')}</div>
                </div>
            </div>`;
        }).join('');
    }

    function ncIconForCategory(cat) {
        const map = {
            'CONSULTATION':'bi-clipboard2-pulse','SOIN':'bi-heart-pulse',
            'BILAN':'bi-file-earmark-medical','LABO':'bi-flask',
            'IMAGERIE':'bi-camera-video','PHARMACIE':'bi-capsule',
            'RDV':'bi-calendar-check','HOSPIT':'bi-hospital',
            'TRANSFERT':'bi-arrow-left-right','URGENCE':'bi-exclamation-triangle-fill'
        };
        return map[cat] || 'bi-bell';
    }

    function ncEsc(s) {
        return String(s ?? '').replace(/[&<>"']/g, c => ({
            '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'
        }[c]));
    }

    window.ncHandleClick = function(e, id, link) {
        e.stopPropagation();
        // Marquer comme lu
        fetch(NC_BURL + 'notifications/mark-read/' + id, {
            method: 'POST', credentials: 'same-origin',
            headers: {'X-Requested-With':'XMLHttpRequest'}
        }).finally(() => {
            if (link && link !== '#') window.location.href = link;
            else { ncFetch(); document.getElementById('ncPanel').classList.remove('open'); }
        });
    };

    window.ncMarkAllRead = function() {
        fetch(NC_BURL + 'notifications/mark-all-read', {
            method: 'POST', credentials: 'same-origin',
            headers: {'X-Requested-With':'XMLHttpRequest'}
        }).finally(() => ncFetch());
    };

    // ── Toast ──
    function ncShowToast(item) {
        const cat = (item.category || 'INFO').toUpperCase();
        const icon = item.icon || ncIconForCategory(cat);
        const prio = item.priority || 'normal';
        const link = item.link ? (item.link.startsWith('http') ? item.link
                                  : (item.link.startsWith('/') ? item.link : NC_BURL + item.link.replace(/^\//,'')))
                                : '#';

        const toast = document.createElement('div');
        toast.className = 'nc-toast priority-' + prio;
        toast.innerHTML = `
            <div class="nc-item-icon cat-${cat}"><i class="bi ${icon}"></i></div>
            <div class="nc-item-body">
                <div class="nc-item-title">${ncEsc(item.title)}</div>
                <div class="nc-item-msg">${ncEsc(item.message)}</div>
            </div>
            <button style="background:none;border:none;color:#94a3b8;font-size:1.1rem;cursor:pointer;align-self:flex-start;"
                    onclick="event.stopPropagation();this.closest('.nc-toast').remove();">×</button>
        `;
        toast.addEventListener('click', () => {
            fetch(NC_BURL + 'notifications/mark-read/' + item.id, {
                method: 'POST', credentials: 'same-origin'
            }).finally(() => {
                if (link && link !== '#') window.location.href = link;
            });
        });
        document.body.appendChild(toast);
        // Animation d'entrée
        setTimeout(() => toast.classList.add('show'), 30);
        // Auto-fermeture après 8 secondes
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => toast.remove(), 320);
        }, 8000);

        // Son (si déverrouillé)
        if (ncSoundUnlocked) {
            try {
                const audio = document.getElementById('ncSound');
                if (audio) audio.play().catch(() => {});
            } catch(_) {}
        }
    }

    // Premier appel + polling régulier
    ncFetch();
    setInterval(ncFetch, NC_POLL_INTERVAL);
})();
</script>
