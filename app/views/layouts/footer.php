<!-- JAVASCRIPT LOCAL -->
    <script src="<?= BASE_URL ?>public/js/jquery.min.js"></script>
    <script src="<?= BASE_URL ?>public/js/bootstrap.bundle.min.js"></script>
    <script src="<?= BASE_URL ?>public/js/main.js"></script>
    <!-- Module Dictée Vocale (auto-init sur .consultation-form) -->
    <script src="<?= BASE_URL ?>public/js/dictation.js"></script>

    <!-- Scripts spécifiques à certaines pages (si besoin) -->
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
 if (isset($extra_js)): ?>
        <script src="<?= BASE_URL ?>public/js/<?= $extra_js ?>"></script>
    <?php endif; ?>

    <!-- ═══ AUTOSAVE — FORMULAIRE CONSULTATION ═══ -->
    <script>
    (function () {
        'use strict';

        // S'active uniquement sur les pages de consultation (7 étapes)
        const form = document.querySelector('form[action*="consultation/sauvegarder"]');
        if (!form) return;

        const BASE = (typeof BASE_URL !== 'undefined') ? BASE_URL : '/dme_hospital/';
        const INTERVAL_MS  = 30000; // sauvegarde périodique toutes les 30 s
        const DEBOUNCE_MS  = 6000;  // sauvegarde 6 s après la dernière frappe

        /* ── Indicateur visuel ────────────────────────────────── */
        const pill = document.createElement('div');
        pill.id = 'as-pill';
        Object.assign(pill.style, {
            position:'fixed', bottom:'24px', right:'24px',
            background:'#1e293b', color:'#fff',
            borderRadius:'30px', padding:'8px 16px',
            fontSize:'.74rem', fontWeight:'700',
            display:'none', alignItems:'center', gap:'8px',
            zIndex:'9999', boxShadow:'0 4px 20px rgba(0,0,0,.28)',
            transition:'opacity .35s ease', opacity:'1',
            fontFamily:'Inter,system-ui,sans-serif',
        });
        pill.innerHTML =
            '<span id="as-dot" style="width:8px;height:8px;border-radius:50%;flex-shrink:0;background:#22c55e"></span>' +
            '<span id="as-txt"></span>';
        document.body.appendChild(pill);

        let _hideTimer;
        function showPill(msg, color) {
            pill.style.display   = 'flex';
            pill.style.opacity   = '1';
            document.getElementById('as-dot').style.background = color || '#22c55e';
            document.getElementById('as-txt').textContent = msg;
            clearTimeout(_hideTimer);
            _hideTimer = setTimeout(function () {
                pill.style.opacity = '0';
                setTimeout(function () { pill.style.display = 'none'; }, 350);
            }, 3500);
        }

        /* ── Envoi AJAX ───────────────────────────────────────── */
        let _saving = false;
        async function doAutosave() {
            if (_saving) return;
            _saving = true;
            showPill('Sauvegarde en cours…', '#f59e0b');
            try {
                const fd  = new FormData(form);
                const res = await fetch(BASE + 'consultation/autosave', { method: 'POST', body: fd });
                const json = await res.json();
                if (json.session_expired) {
                    showPill('Session expirée — données conservées', '#ef4444');
                } else if (json.success) {
                    showPill('Sauvegardé à ' + json.saved_at, '#22c55e');
                } else {
                    showPill('Erreur de sauvegarde', '#ef4444');
                }
            } catch (e) {
                showPill('Erreur réseau', '#ef4444');
            } finally {
                _saving = false;
            }
        }

        /* ── Sauvegarde périodique ────────────────────────────── */
        setInterval(doAutosave, INTERVAL_MS);

        /* ── Sauvegarde après la dernière frappe/sélection ───── */
        let _debounce;
        function onInput() {
            clearTimeout(_debounce);
            _debounce = setTimeout(doAutosave, DEBOUNCE_MS);
        }
        form.addEventListener('input',  onInput);
        form.addEventListener('change', onInput);

        /* ── Sauvegarde immédiate à la fermeture / navigation ── */
        window.addEventListener('beforeunload', function () {
            if (navigator.sendBeacon) {
                navigator.sendBeacon(BASE + 'consultation/autosave', new FormData(form));
            }
        });

        /* ── Bannière de restauration (si $brouillon_restored injecté par PHP) ── */
        const restoredAt = document.getElementById('brouillon-restored-at');
        if (restoredAt) {
            showPill('Brouillon restauré du ' + restoredAt.dataset.time, '#6366f1');
        }
    })();
    </script>

    <!-- ═══ CLOCHE DE NOTIFICATIONS UNIVERSELLE ═══ -->
    <?php
    // Inclus uniquement si user connecté ET pas sur la page de login/logout
    if (!empty($_SESSION['user_id']) && !defined('NC_BELL_RENDERED')) {
        define('NC_BELL_RENDERED', true);
        require_once __DIR__ . '/notification_bell.php';
    }
    ?>

    <!-- ═══ WIDGET MESSAGERIE MÉDECINS ═══ -->
    <?php
    if (!empty($_SESSION['user_id']) && ($_SESSION['user_role'] ?? '') === 'MEDECIN' && !defined('CHAT_WIDGET_RENDERED')) {
        define('CHAT_WIDGET_RENDERED', true);
        require_once __DIR__ . '/../chat/widget.php';
    }
    ?>
    <!-- ═══ PIED DE PAGE COPYRIGHT ═══ -->
    <footer style="text-align:center;padding:14px 20px;font-size:.75rem;color:#94a3b8;
                   border-top:1px solid #e2e8f0;background:#f8fafc;margin-top:auto;">
        &copy; 2026 <strong style="color:#1e293b;">SimCare+</strong>
        &nbsp;·&nbsp; Développé par <strong style="color:#1e293b;">Franck Simeni</strong>
        &nbsp;·&nbsp; Tous droits réservés
    </footer>
</body>
</html>