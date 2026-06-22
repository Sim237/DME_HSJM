<?php
// Vérification pour éviter les inclusions multiples
if (!defined('HEADER_RENDERED')) {
    define('HEADER_RENDERED', true);
} else {
    return; // Si déjà inclus, on s'arrête là
}

// Assurer que BASE_URL est défini
if (!defined('BASE_URL')) {
    define('BASE_URL', '/dme_hospital/');
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $config['app_name'] ?? 'SimCare+ — HSJM' ?></title>
    <link rel="icon" type="image/svg+xml" href="<?= BASE_URL ?>public/images/simcare_plus_logo.svg">

    <!-- CHARGEMENT LOCAL (Pas de CDN) -->
    <!-- Bootstrap 5 CSS -->
    <link rel="stylesheet" href="<?= BASE_URL ?>public/css/bootstrap.min.css">
    <!-- FontAwesome (Local) -->
    <link rel="stylesheet" href="<?= BASE_URL ?>public/css/all.min.css">
    <!-- Bootstrap Icons (Local) -->
    <link rel="stylesheet" href="<?= BASE_URL ?>public/css/bootstrap-icons.min.css">
    <!-- Polices locales -->
    <link rel="stylesheet" href="<?= BASE_URL ?>public/css/fonts.css">
    <!-- Styles personnalisés -->
    <link rel="stylesheet" href="<?= BASE_URL ?>public/css/style.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>public/css/consultation-forms.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>public/css/responsive.css">

    <link rel="stylesheet" href="<?= BASE_URL ?>public/css/fontawesome/all.min.css">
    <!-- Module Dictée Vocale -->
    <link rel="stylesheet" href="<?= BASE_URL ?>public/css/dictation.css">

    <!-- Token CSRF (utilisé par l'intercepteur fetch) -->
    <?php
    if (!class_exists('CsrfService')) {
        require_once __DIR__ . '/../../services/CsrfService.php';
    }
    echo CsrfService::meta();
    ?>

    <!-- Script de base (chargé tôt si besoin) -->
    <script>
        const BASE_URL = "<?= BASE_URL ?>";

        // ── Intercepteur CSRF global ──────────────────────────────────────────
        // 1) fetch() POST/PUT/PATCH/DELETE → header X-CSRF-Token automatique
        // 2) Tous les <form method="POST"> → champ hidden _csrf_token injecté
        (function () {
            const _csrfToken = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
            if (!_csrfToken) return;

            // Intercepter fetch()
            const _origFetch = window.fetch.bind(window);
            window.fetch = function (input, init = {}) {
                const method = (init.method || 'GET').toUpperCase();
                if (['POST', 'PUT', 'PATCH', 'DELETE'].includes(method)) {
                    init.headers = Object.assign({}, init.headers, {
                        'X-CSRF-Token': _csrfToken
                    });
                }
                return _origFetch(input, init);
            };

            // Injecter champ hidden dans tous les formulaires POST (HTML classique)
            function injectCsrfForms() {
                document.querySelectorAll('form[method="POST"], form[method="post"]').forEach(function(form) {
                    if (!form.querySelector('input[name="_csrf_token"]')) {
                        const inp = document.createElement('input');
                        inp.type  = 'hidden';
                        inp.name  = '_csrf_token';
                        inp.value = _csrfToken;
                        form.prepend(inp);
                    }
                });
            }

            // Démarrer l'injection et le MutationObserver après que <body> existe
            function startCsrfObserver() {
                injectCsrfForms();

                // Observer les nouvelles modales/formulaires ajoutés dynamiquement
                if (document.body) {
                    const _obs = new MutationObserver(function(mutations) {
                        mutations.forEach(function(m) {
                            m.addedNodes.forEach(function(node) {
                                if (node.nodeType === 1) {
                                    node.querySelectorAll && node.querySelectorAll(
                                        'form[method="POST"], form[method="post"]'
                                    ).forEach(function(form) {
                                        if (!form.querySelector('input[name="_csrf_token"]')) {
                                            const inp = document.createElement('input');
                                            inp.type  = 'hidden';
                                            inp.name  = '_csrf_token';
                                            inp.value = _csrfToken;
                                            form.prepend(inp);
                                        }
                                    });
                                }
                            });
                        });
                    });
                    _obs.observe(document.body, { childList: true, subtree: true });
                }
            }

            // Attendre que <body> soit disponible
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', startCsrfObserver);
            } else {
                startCsrfObserver();
            }
        })();
    </script>
</head>
<body>

<?php
/* ── BADGE GARDE ACTIVE (fixe, visible sur toutes les pages) ── */
if (!empty($_SESSION['logged_in']) && defined('GARDE_ACTIVE') && GARDE_ACTIVE):
?>
<div id="garde-badge" style="
    position:fixed;bottom:20px;left:20px;z-index:9999;
    background:linear-gradient(135deg,#1e3a5f,#1d4ed8);
    color:#fff;border-radius:50px;padding:8px 16px 8px 12px;
    display:flex;align-items:center;gap:8px;
    box-shadow:0 4px 20px rgba(29,78,216,.45);
    font-family:'Plus Jakarta Sans',sans-serif;font-size:.78rem;font-weight:700;
    cursor:default;user-select:none;animation:garde-in .3s ease;">
    <style>
    @keyframes garde-in{from{opacity:0;transform:translateY(10px);}to{opacity:1;transform:none;}}
    @keyframes garde-pulse{0%,100%{opacity:1;}50%{opacity:.5;}}
    </style>
    <span style="width:8px;height:8px;border-radius:50%;background:#34d399;
                 display:inline-block;animation:garde-pulse 1.6s ease infinite;flex-shrink:0;"></span>
    <span>Garde active</span>
    <span style="font-weight:500;opacity:.75;font-size:.72rem;">— Accès universel</span>
</div>
<?php endif; ?>

<?php
/* ── MESSAGE DE BIENVENUE (affiché une seule fois après connexion) ── */
if (!empty($_SESSION['show_welcome']) && !empty($_SESSION['logged_in'])) {
    $heure      = (int)date('H');
    $salutation = $heure < 12 ? 'Bonjour' : ($heure < 18 ? 'Bon après-midi' : 'Bonsoir');
    $prenom     = $_SESSION['user_prenom'] ?? $_SESSION['user_nom'] ?? 'Utilisateur';
    $nom        = $_SESSION['user_nom'] ?? '';
    $role       = $_SESSION['user_role'] ?? '';
    $service    = $_SESSION['nom_service'] ?? '';

    $roleLabels = [
        'MEDECIN'       => ['label' => 'Médecin',         'icon' => 'bi-heart-pulse-fill',     'color' => '#3b82f6'],
        'INFIRMIER'     => ['label' => 'Infirmier(ère)',   'icon' => 'bi-clipboard2-pulse-fill', 'color' => '#10b981'],
        'ADMIN'         => ['label' => 'Administrateur',  'icon' => 'bi-shield-fill-check',     'color' => '#8b5cf6'],
        'ADMINISTRATEUR'=> ['label' => 'Administrateur',  'icon' => 'bi-shield-fill-check',     'color' => '#8b5cf6'],
        'SECRETAIRE'    => ['label' => 'Secrétaire',      'icon' => 'bi-person-badge-fill',     'color' => '#f59e0b'],
        'ACCUEIL'       => ['label' => 'Accueil',         'icon' => 'bi-house-heart-fill',      'color' => '#06b6d4'],
        'LABORANTIN'    => ['label' => 'Laborantin(e)',   'icon' => 'bi-flask-fill',            'color' => '#ec4899'],
        'PHARMACIEN'    => ['label' => 'Pharmacien(ne)',  'icon' => 'bi-capsule-pill',          'color' => '#f97316'],
    ];
    $rl    = $roleLabels[strtoupper($role)] ?? ['label' => $role, 'icon' => 'bi-person-fill', 'color' => '#64748b'];
    $initials = strtoupper(substr($prenom,0,1) . substr($nom,0,1));
    unset($_SESSION['show_welcome']);
?>
<!-- ══ OVERLAY BIENVENUE ══ -->
<div id="welcomeOverlay" style="
    position:fixed; inset:0; z-index:99999;
    background:rgba(15,23,42,0.72); backdrop-filter:blur(6px);
    display:flex; align-items:center; justify-content:center;
    animation:wFadeIn .35s ease;">
    <div id="welcomeCard" style="
        background:#fff; border-radius:28px; padding:44px 48px;
        text-align:center; max-width:420px; width:90%;
        box-shadow:0 32px 64px rgba(0,0,0,.35);
        animation:wSlideUp .4s cubic-bezier(.16,1,.3,1);">

        <!-- Avatar -->
        <div style="width:80px;height:80px;border-radius:50%;background:<?= $rl['color'] ?>;
                    margin:0 auto 20px;display:flex;align-items:center;justify-content:center;
                    font-size:1.9rem;font-weight:800;color:#fff;
                    box-shadow:0 8px 24px <?= $rl['color'] ?>55;">
            <?= $initials ?>
        </div>

        <!-- Salutation -->
        <p style="font-size:.85rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:1px;margin-bottom:6px">
            <?= $salutation ?> 👋
        </p>
        <h2 style="font-size:1.75rem;font-weight:900;color:#0f172a;margin-bottom:4px;line-height:1.1">
            <?= htmlspecialchars($prenom) ?>
        </h2>
        <p style="font-size:.9rem;color:#475569;font-weight:600;margin-bottom:20px">
            <?= htmlspecialchars(strtoupper($nom)) ?>
        </p>

        <!-- Badge rôle -->
        <div style="display:inline-flex;align-items:center;gap:8px;background:<?= $rl['color'] ?>15;
                    border:1.5px solid <?= $rl['color'] ?>44;border-radius:100px;
                    padding:7px 18px;margin-bottom:<?= $service ? '10px' : '24px' ?>">
            <i class="bi <?= $rl['icon'] ?>" style="color:<?= $rl['color'] ?>;font-size:1rem"></i>
            <span style="font-weight:700;font-size:.85rem;color:<?= $rl['color'] ?>"><?= htmlspecialchars($rl['label']) ?></span>
        </div>

        <?php if($service): ?>
        <!-- Service -->
        <div style="display:flex;align-items:center;justify-content:center;gap:6px;
                    background:#f8fafc;border-radius:12px;padding:10px 16px;margin-bottom:24px">
            <i class="bi bi-building-fill" style="color:#64748b;font-size:.9rem"></i>
            <span style="font-size:.82rem;font-weight:700;color:#334155"><?= htmlspecialchars($service) ?></span>
        </div>
        <?php endif; ?>

        <!-- Message -->
        <p style="font-size:.88rem;color:#64748b;line-height:1.6;margin-bottom:24px">
            Bienvenue sur <strong style="color:#0f172a">SimCare+&nbsp;HSJM</strong>.<br>
            Bonne prise en charge à tous vos patients.
        </p>

        <!-- Barre de progression auto-fermeture -->
        <div style="height:4px;background:#e2e8f0;border-radius:4px;overflow:hidden;margin-bottom:18px">
            <div id="welcomeBar" style="height:100%;width:100%;background:<?= $rl['color'] ?>;
                 border-radius:4px;transition:width 3.8s linear;"></div>
        </div>

        <button onclick="closeWelcome()" style="
            width:100%;padding:12px;border:none;border-radius:14px;
            background:<?= $rl['color'] ?>;color:#fff;font-weight:800;
            font-size:.9rem;cursor:pointer;transition:opacity .2s;"
            onmouseover="this.style.opacity='.85'" onmouseout="this.style.opacity='1'">
            Accéder à mon espace <i class="bi bi-arrow-right ms-1"></i>
        </button>
    </div>
</div>

<style>
@keyframes wFadeIn  { from{opacity:0} to{opacity:1} }
@keyframes wSlideUp { from{opacity:0;transform:translateY(30px) scale(.97)} to{opacity:1;transform:translateY(0) scale(1)} }
@keyframes wFadeOut { from{opacity:1} to{opacity:0;pointer-events:none} }
</style>
<script>
(function(){
    // Lance la barre de progression et la fermeture auto après 4s
    requestAnimationFrame(function() {
        requestAnimationFrame(function() {
            var bar = document.getElementById('welcomeBar');
            if (bar) bar.style.width = '0%';
        });
    });
    setTimeout(closeWelcome, 4200);
})();
function closeWelcome() {
    var el = document.getElementById('welcomeOverlay');
    if (!el || el._closing) return;
    el._closing = true;
    el.style.animation = 'wFadeOut .35s ease forwards';
    setTimeout(function(){ if(el.parentNode) el.parentNode.removeChild(el); }, 350);
}
</script>
<?php } ?>

<!-- ══════════════════════════════════════════════════════════
     MODAL PROFIL UNIVERSEL — disponible sur toutes les pages
══════════════════════════════════════════════════════════ -->
<?php if (!empty($_SESSION['logged_in'])): ?>
<div class="modal fade" id="modalProfilUser" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width:460px">
        <div class="modal-content border-0 shadow-lg" style="border-radius:20px;overflow:hidden">

            <!-- En-tête -->
            <div class="modal-header border-0 py-4 px-4"
                 style="background:linear-gradient(135deg,#0f172a 0%,#1e40af 100%)">
                <div class="d-flex align-items-center gap-3 w-100">
                    <div style="width:52px;height:52px;background:rgba(255,255,255,.18);border-radius:14px;
                                display:flex;align-items:center;justify-content:center;
                                font-size:1.35rem;font-weight:800;color:#fff;flex-shrink:0;">
                        <?= strtoupper(substr($_SESSION['user_prenom'] ?? 'U', 0, 1) . substr($_SESSION['user_nom'] ?? '', 0, 1)) ?>
                    </div>
                    <div>
                        <h5 class="fw-bold mb-0 text-white" style="font-size:1rem">Mon Profil</h5>
                        <small style="color:rgba(255,255,255,.6);font-size:.75rem">
                            <?= htmlspecialchars(($_SESSION['user_prenom'] ?? '') . ' ' . ($_SESSION['user_nom'] ?? '')) ?>
                            &nbsp;·&nbsp;<?= htmlspecialchars($_SESSION['user_role'] ?? '') ?>
                        </small>
                    </div>
                    <button type="button" class="btn-close btn-close-white ms-auto" data-bs-dismiss="modal"></button>
                </div>
            </div>

            <!-- Formulaire -->
            <form id="formProfilUpdate" onsubmit="profilSubmit(event)">
                <div class="modal-body px-4 pt-4 pb-2">

                    <!-- Alertes -->
                    <div id="profilOk" class="alert alert-success rounded-3 py-2 px-3 mb-3 d-none" style="font-size:.83rem">
                        <i class="bi bi-check-circle-fill me-2"></i>Profil mis à jour avec succès !
                    </div>
                    <div id="profilErr" class="alert alert-danger rounded-3 py-2 px-3 mb-3 d-none" style="font-size:.83rem">
                        <i class="bi bi-x-circle-fill me-2"></i><span id="profilErrTxt">Erreur.</span>
                    </div>

                    <!-- Nom / Prénom -->
                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <label class="form-label fw-bold text-muted" style="font-size:.7rem;text-transform:uppercase;letter-spacing:.5px">Nom</label>
                            <input type="text" name="nom" id="profilNom" class="form-control rounded-3"
                                   value="<?= htmlspecialchars($_SESSION['user_nom'] ?? '') ?>" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-bold text-muted" style="font-size:.7rem;text-transform:uppercase;letter-spacing:.5px">Prénom</label>
                            <input type="text" name="prenom" id="profilPrenom" class="form-control rounded-3"
                                   value="<?= htmlspecialchars($_SESSION['user_prenom'] ?? '') ?>" required>
                        </div>
                    </div>

                    <!-- Identifiant (lecture seule) -->
                    <div class="mb-3">
                        <label class="form-label fw-bold text-muted" style="font-size:.7rem;text-transform:uppercase;letter-spacing:.5px">Identifiant de connexion</label>
                        <input type="text" class="form-control rounded-3 bg-light text-muted"
                               value="<?= htmlspecialchars($_SESSION['username'] ?? '') ?>" disabled>
                        <small class="text-muted" style="font-size:.71rem">L'identifiant ne peut pas être modifié.</small>
                    </div>

                    <hr class="my-3">
                    <!-- Toggle changement de mot de passe -->
                    <div id="pwdToggleRow" class="d-flex align-items-center justify-content-between mb-2" style="cursor:pointer;" onclick="togglePwdSection()">
                        <p class="fw-bold mb-0 text-dark" style="font-size:.82rem">
                            <i class="bi bi-shield-lock-fill text-primary me-2"></i>Changer de mot de passe
                        </p>
                        <i class="bi bi-chevron-down text-muted" id="pwdChevron" style="transition:.2s;"></i>
                    </div>

                    <div id="pwdSection" style="display:none;">
                        <!-- Mot de passe actuel -->
                        <div class="mb-2">
                            <label class="form-label fw-bold text-muted" style="font-size:.7rem;text-transform:uppercase;letter-spacing:.5px">Mot de passe actuel</label>
                            <div class="input-group">
                                <input type="password" name="current_password" id="profilPwdCurrent"
                                       class="form-control rounded-start-3"
                                       placeholder="Votre mot de passe actuel">
                                <button type="button" class="btn btn-outline-secondary rounded-end-3"
                                        onclick="var i=document.getElementById('profilPwdCurrent');i.type=i.type==='password'?'text':'password'">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Nouveau mot de passe -->
                        <div class="mb-2">
                            <label class="form-label fw-bold text-muted" style="font-size:.7rem;text-transform:uppercase;letter-spacing:.5px">Nouveau mot de passe</label>
                            <div class="input-group">
                                <input type="password" name="new_password" id="profilPwd"
                                       class="form-control rounded-start-3"
                                       placeholder="Minimum 6 caractères">
                                <button type="button" class="btn btn-outline-secondary rounded-end-3"
                                        onclick="var i=document.getElementById('profilPwd');i.type=i.type==='password'?'text':'password'">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                        </div>
                        <div class="mb-1">
                            <label class="form-label fw-bold text-muted" style="font-size:.7rem;text-transform:uppercase;letter-spacing:.5px">Confirmer le nouveau mot de passe</label>
                            <input type="password" id="profilPwdConfirm" class="form-control rounded-3"
                                   placeholder="Répéter le nouveau mot de passe">
                        </div>
                        <!-- Force du mot de passe -->
                        <div id="pwdStrengthBar" class="mt-2" style="display:none;">
                            <div style="height:4px;border-radius:4px;background:#e2e8f0;overflow:hidden;">
                                <div id="pwdStrengthFill" style="height:100%;width:0%;transition:.3s;border-radius:4px;"></div>
                            </div>
                            <small id="pwdStrengthLabel" class="text-muted" style="font-size:.67rem;"></small>
                        </div>
                    </div>

                </div>
                <div class="modal-footer border-0 px-4 pb-4 pt-3 d-flex gap-2">
                    <button type="button" class="btn btn-outline-secondary rounded-pill flex-grow-1"
                            data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" id="btnProfilSave"
                            class="btn btn-primary rounded-pill flex-grow-1 fw-bold shadow-sm">
                        <i class="bi bi-floppy-fill me-1"></i>Enregistrer
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function ouvrirProfilModal() {
    // Réinitialiser alertes et champs
    document.getElementById('profilOk').classList.add('d-none');
    document.getElementById('profilErr').classList.add('d-none');
    if (document.getElementById('profilPwdCurrent'))  document.getElementById('profilPwdCurrent').value = '';
    document.getElementById('profilPwd').value = '';
    document.getElementById('profilPwdConfirm').value = '';
    // Replier la section mot de passe
    const sec = document.getElementById('pwdSection');
    if (sec) { sec.style.display = 'none'; document.getElementById('pwdChevron').style.transform = ''; }
    document.getElementById('pwdStrengthBar').style.display = 'none';
    new bootstrap.Modal(document.getElementById('modalProfilUser')).show();
}

function togglePwdSection() {
    const sec = document.getElementById('pwdSection');
    const chv = document.getElementById('pwdChevron');
    const open = sec.style.display !== 'none';
    sec.style.display = open ? 'none' : 'block';
    chv.style.transform = open ? '' : 'rotate(180deg)';
    if (!open) setTimeout(() => document.getElementById('profilPwdCurrent').focus(), 80);
}

// Indicateur de force du mot de passe
document.addEventListener('DOMContentLoaded', function() {
    const pwdInput = document.getElementById('profilPwd');
    if (!pwdInput) return;
    pwdInput.addEventListener('input', function() {
        const v = this.value;
        const bar = document.getElementById('pwdStrengthBar');
        const fill = document.getElementById('pwdStrengthFill');
        const lbl  = document.getElementById('pwdStrengthLabel');
        if (!v) { bar.style.display = 'none'; return; }
        bar.style.display = 'block';
        let score = 0;
        if (v.length >= 6)  score++;
        if (v.length >= 10) score++;
        if (/[A-Z]/.test(v)) score++;
        if (/[0-9]/.test(v)) score++;
        if (/[^A-Za-z0-9]/.test(v)) score++;
        const levels = [
            {pct:15, color:'#ef4444', txt:'Très faible'},
            {pct:35, color:'#f97316', txt:'Faible'},
            {pct:60, color:'#eab308', txt:'Moyen'},
            {pct:80, color:'#22c55e', txt:'Fort'},
            {pct:100,color:'#0d9488', txt:'Très fort'}
        ];
        const lv = levels[Math.min(score, 4)];
        fill.style.width = lv.pct + '%';
        fill.style.background = lv.color;
        lbl.textContent = lv.txt;
        lbl.style.color = lv.color;
    });
});

function profilSubmit(e) {
    e.preventDefault();
    const pwd        = document.getElementById('profilPwd').value;
    const pwdConfirm = document.getElementById('profilPwdConfirm').value;
    const pwdCurrent = document.getElementById('profilPwdCurrent') ? document.getElementById('profilPwdCurrent').value : '';
    const pwdSection = document.getElementById('pwdSection');
    const pwdVisible = pwdSection && pwdSection.style.display !== 'none';

    document.getElementById('profilOk').classList.add('d-none');
    document.getElementById('profilErr').classList.add('d-none');

    // Validations mot de passe
    if (pwdVisible && pwd) {
        if (!pwdCurrent) {
            document.getElementById('profilErrTxt').textContent = 'Veuillez saisir votre mot de passe actuel.';
            document.getElementById('profilErr').classList.remove('d-none');
            document.getElementById('profilPwdCurrent').focus();
            return;
        }
        if (pwd.length < 6) {
            document.getElementById('profilErrTxt').textContent = 'Le nouveau mot de passe doit contenir au moins 6 caractères.';
            document.getElementById('profilErr').classList.remove('d-none');
            return;
        }
        if (pwd !== pwdConfirm) {
            document.getElementById('profilErrTxt').textContent = 'Les mots de passe ne correspondent pas.';
            document.getElementById('profilErr').classList.remove('d-none');
            document.getElementById('profilPwdConfirm').focus();
            return;
        }
    }

    const btn = document.getElementById('btnProfilSave');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Enregistrement…';

    const fd = new FormData(document.getElementById('formProfilUpdate'));
    if (!fd.has('email'))     fd.append('email', '');
    if (!fd.has('telephone')) fd.append('telephone', '');
    // Si section mot de passe repliée, vider les champs pour ne pas envoyer
    if (!pwdVisible) { fd.delete('new_password'); fd.delete('current_password'); }

    fetch(BASE_URL + 'update-profil', { method: 'POST', body: fd })
        .then(r => {
            if (r.url && r.url.includes('error_pwd')) {
                throw new Error('Mot de passe actuel incorrect. Veuillez réessayer.');
            }
            if (r.url && r.url.includes('error=pwd_short')) {
                throw new Error('Le nouveau mot de passe doit contenir au moins 6 caractères.');
            }
            if (r.url && (r.url.includes('success=1') || r.redirected)) {
                return r.text().then(html => {
                    document.getElementById('profilOk').classList.remove('d-none');
                    btn.disabled = false;
                    btn.innerHTML = '<i class="bi bi-floppy-fill me-1"></i>Enregistrer';
                    setTimeout(() => {
                        bootstrap.Modal.getInstance(document.getElementById('modalProfilUser')).hide();
                        // Mettre à jour l'affichage du nom sans recharger si possible
                        const nom    = document.getElementById('profilNom').value;
                        const prenom = document.getElementById('profilPrenom').value;
                        document.querySelectorAll('.cs-user-name,[data-user-name]').forEach(el => el.textContent = prenom + ' ' + nom);
                    }, 1400);
                });
            }
            throw new Error('Échec de la mise à jour.');
        })
        .catch(err => {
            document.getElementById('profilErrTxt').textContent = err.message || 'Erreur serveur.';
            document.getElementById('profilErr').classList.remove('d-none');
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-floppy-fill me-1"></i>Enregistrer';
        });
}
</script>
<?php endif; ?>