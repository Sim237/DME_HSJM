<!DOCTYPE html>
<html lang="fr" data-variant="1">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Connexion — SimCare+</title>
<link rel="stylesheet" href="<?= BASE_URL ?>public/css/bootstrap-icons.min.css">
<link rel="stylesheet" href="<?= BASE_URL ?>public/css/fonts.css">
<style>
/* ══════════════════════════════════════════════════════════════════
   SimCare+ · Glass UI — Login
   Palette & tokens (glass.css inlinés — pas d'import externe)
══════════════════════════════════════════════════════════════════ */
*, *::before, *::after { box-sizing: border-box; }
html, body { margin: 0; padding: 0; height: 100%; }

:root {
  --primary:        #2563eb;
  --primary-d:      #1d4ed8;
  --sky:            #38bdf8;
  --sky-l:          #7dd3fc;
  --navy:           #0f172a;
  --success:        #22c55e;
  --glass-bg:       rgba(255,255,255,.55);
  --glass-bg-s:     rgba(255,255,255,.76);
  --glass-brd:      rgba(255,255,255,.68);
  --glass-blur:     22px;
  --glass-shadow:   0 12px 40px rgba(30,58,95,.16), inset 0 1px 0 rgba(255,255,255,.6);
  --text:           #0f172a;
  --text-mid:       #475569;
  --text-mute:      #64748b;
  --text-dim:       #94a3b8;
  --field-bg:       rgba(255,255,255,.6);
  --field-brd:      rgba(255,255,255,.72);
  --hairline:       rgba(15,23,42,.08);
  --radius:         26px;
  --page-bg:
    radial-gradient(1100px 800px at 12%  8%, #c7e0ff 0%, transparent 55%),
    radial-gradient(900px  700px at 92% 12%, #d9c9ff 0%, transparent 50%),
    radial-gradient(1000px 900px at 78% 96%, #b6f3ff 0%, transparent 55%),
    radial-gradient(900px  800px at  8% 92%, #cdfbe7 0%, transparent 55%),
    linear-gradient(135deg, #eef4ff 0%, #f6f1ff 100%);
}

body {
  font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
  color: var(--text);
  background: var(--page-bg);
  background-attachment: fixed;
  -webkit-font-smoothing: antialiased;
  min-height: 100vh;
}

/* ── Orbs décoratifs ── */
.orbs { position: fixed; inset: 0; overflow: hidden; pointer-events: none; z-index: 0; }
.orb  { position: absolute; border-radius: 50%; filter: blur(52px); opacity: .52; }

/* ── Scène ── */
.stage {
  position: relative; z-index: 1;
  min-height: 100vh;
  display: flex; align-items: center; justify-content: center;
  padding: 24px;
}

/* ── Carte split ── */
.login {
  width: 100%; max-width: 960px;
  display: grid; grid-template-columns: 1.05fr 1fr;
  overflow: hidden; border-radius: var(--radius);
  background: var(--glass-bg);
  backdrop-filter: blur(var(--glass-blur)) saturate(150%);
  -webkit-backdrop-filter: blur(var(--glass-blur)) saturate(150%);
  border: 1px solid var(--glass-brd);
  box-shadow: var(--glass-shadow);
}

/* ══ Panneau gauche : marque ══ */
.login-aside {
  position: relative;
  padding: 46px 42px;
  color: #fff;
  background:
    radial-gradient(700px 500px at 20% 10%, rgba(56,189,248,.45), transparent 60%),
    linear-gradient(150deg, #1e3a8a 0%, #0f172a 70%, #020617 100%);
  display: flex; flex-direction: column; justify-content: space-between;
  overflow: hidden; min-height: 560px;
}
/* Grille */
.login-aside::before {
  content: ''; position: absolute; inset: 0; z-index: 1;
  background-image:
    linear-gradient(rgba(56,189,248,.07) 1px, transparent 1px),
    linear-gradient(90deg, rgba(56,189,248,.07) 1px, transparent 1px);
  background-size: 42px 42px;
  -webkit-mask-image: radial-gradient(ellipse at 40% 40%, #000, transparent 75%);
          mask-image: radial-gradient(ellipse at 40% 40%, #000, transparent 75%);
}
/* Roue conique tournante */
.login-aside::after {
  content: ''; position: absolute; top: 50%; left: 50%; z-index: 0;
  width: 160%; aspect-ratio: 1; transform: translate(-50%,-50%);
  background: conic-gradient(from 0deg, transparent 0deg, rgba(56,189,248,.12) 40deg, transparent 110deg, transparent 360deg);
  animation: aside-spin 16s linear infinite; pointer-events: none;
}
@keyframes aside-spin { to { transform: translate(-50%,-50%) rotate(360deg); } }
@media (prefers-reduced-motion: reduce) { .login-aside::after { animation: none; } }

/* Brand mark */
.la-brand { position: relative; z-index: 2; display: flex; align-items: center; gap: 14px; }
.la-mark {
  width: 62px; height: 62px; border-radius: 15px; flex-shrink: 0;
  background: #0f172a;
  border: 1.5px solid rgba(14,165,233,.5);
  position: relative; overflow: hidden;
  box-shadow: 0 4px 20px rgba(0,0,0,.4), 0 0 0 3px rgba(14,165,233,.15);
}
/* Recadrage sur le cercle icône (cx=152,r=100 dans viewBox 680×340) */
.la-mark img {
  position: absolute;
  width: 210px; height: auto;
  left: -16px; top: -21px;
  flex-shrink: 0;
}
.la-brand-name { font-size: 20px; font-weight: 800; letter-spacing: -.02em; color: #fff; }
.la-brand-name span { color: var(--sky-l); }
.la-brand-sub  { font-size: 11px; color: rgba(255,255,255,.55); font-weight: 600; text-transform: uppercase; letter-spacing: .18em; margin-top: 2px; }

/* Headline */
.la-headline { position: relative; z-index: 2; }
.la-headline h1 {
  font-size: 32px; font-weight: 800; line-height: 1.13; letter-spacing: -.025em; margin: 0 0 14px; color: #fff;
}
.la-headline p { font-size: 14.5px; line-height: 1.65; color: rgba(255,255,255,.7); margin: 0; max-width: 340px; }

/* Points */
.la-points { position: relative; z-index: 2; display: flex; flex-direction: column; gap: 12px; }
.la-point {
  display: flex; align-items: center; gap: 12px;
  font-size: 13.5px; color: rgba(255,255,255,.88); font-weight: 500;
}
.la-point .pi {
  width: 34px; height: 34px; border-radius: 10px; flex-shrink: 0;
  background: rgba(56,189,248,.18); border: 1px solid rgba(56,189,248,.32);
  display: flex; align-items: center; justify-content: center;
  color: var(--sky-l); font-size: 14px;
}

/* Status pill */
.la-status {
  display: inline-flex; align-items: center; gap: 9px; margin-top: 16px;
  padding: 10px 16px; border-radius: 999px;
  background: rgba(34,197,94,.12); border: 1px solid rgba(34,197,94,.3);
  font-size: 12px; font-weight: 600; color: #bbf7d0; width: fit-content;
}
.la-status .pulse {
  width: 8px; height: 8px; border-radius: 50%; background: #22c55e;
  animation: live-pulse 2s ease-out infinite;
}
@keyframes live-pulse {
  0%   { box-shadow: 0 0 0 0 rgba(34,197,94,.55); }
  70%  { box-shadow: 0 0 0 7px rgba(34,197,94,0); }
  100% { box-shadow: 0 0 0 0 rgba(34,197,94,0); }
}

/* ══ Panneau droit : formulaire ══ */
.login-main {
  background: var(--glass-bg-s);
  backdrop-filter: blur(var(--glass-blur)) saturate(150%);
  -webkit-backdrop-filter: blur(var(--glass-blur)) saturate(150%);
  padding: 48px 46px;
  display: flex; flex-direction: column; justify-content: center;
}

.lm-badge {
  width: 50px; height: 50px; border-radius: 15px; margin-bottom: 18px;
  display: flex; align-items: center; justify-content: center; font-size: 22px;
  background: linear-gradient(135deg, rgba(37,99,235,.15), rgba(56,189,248,.1));
  border: 1px solid rgba(37,99,235,.22); color: var(--primary);
  box-shadow: 0 8px 20px rgba(37,99,235,.16);
}
.lm-head { margin-bottom: 26px; }
.lm-head .eyebrow {
  font-size: 11.5px; font-weight: 700; letter-spacing: .18em;
  text-transform: uppercase; color: var(--primary);
}
.lm-head h2 {
  font-size: 28px; font-weight: 800; letter-spacing: -.025em;
  margin: 8px 0 6px; color: var(--text);
}
.lm-head p { font-size: 14px; color: var(--text-mute); margin: 0; }

/* Champs */
.field { margin-bottom: 16px; }
.field > label {
  display: block; font-size: 11.5px; font-weight: 700; color: var(--text-mute);
  letter-spacing: .05em; text-transform: uppercase; margin-bottom: 7px;
}
.input-wrap {
  display: flex; align-items: center; gap: 10px;
  background: var(--field-bg);
  border: 1.5px solid var(--field-brd);
  border-radius: 14px; padding: 0 14px; height: 50px;
  backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(8px);
  transition: border-color .15s, box-shadow .15s;
}
.input-wrap:focus-within {
  border-color: var(--primary);
  box-shadow: 0 0 0 4px rgba(37,99,235,.14);
}
.input-wrap > i { color: var(--text-mute); font-size: 17px; flex-shrink: 0; }
.input-wrap > input {
  flex: 1; border: none; background: transparent; outline: none;
  font-family: inherit; font-size: 15px; color: var(--text);
  height: 100%; font-weight: 500;
}
.input-wrap > input::placeholder { color: var(--text-dim); }
.input-wrap .toggle-pw {
  cursor: pointer; color: var(--text-dim); font-size: 17px;
  background: none; border: none; padding: 0; flex-shrink: 0;
  transition: color .15s;
}
.input-wrap .toggle-pw:hover { color: var(--primary); }

/* Message d'erreur */
.error-msg {
  background: rgba(239,68,68,.1);
  color: #b91c1c;
  padding: 12px 16px; border-radius: 12px; margin-bottom: 20px;
  font-size: 13.5px; font-weight: 600;
  border: 1px solid rgba(239,68,68,.22);
  display: flex; align-items: center; gap: 8px;
}

/* Ligne "se souvenir / mot de passe oublié" */
.row-between {
  display: flex; align-items: center; justify-content: space-between;
  margin: 6px 0 22px;
}
.remember {
  display: flex; align-items: center; gap: 8px;
  font-size: 13px; color: var(--text-mid); font-weight: 600; cursor: pointer;
}
.remember .box {
  width: 20px; height: 20px; border-radius: 6px; background: var(--primary);
  display: flex; align-items: center; justify-content: center;
  color: #fff; font-size: 12px;
  box-shadow: 0 2px 6px rgba(37,99,235,.35);
}
.lnk { font-size: 13px; color: var(--primary); font-weight: 700; text-decoration: none; }
.lnk:hover { text-decoration: underline; }

/* Bouton */
.btn-login {
  position: relative; overflow: hidden;
  width: 100%; padding: 15px 26px; border-radius: 16px; border: none; cursor: pointer;
  font-family: inherit; font-size: 15px; font-weight: 700;
  background: linear-gradient(135deg, var(--primary), var(--sky));
  color: #fff; box-shadow: 0 8px 22px rgba(37,99,235,.32);
  display: flex; align-items: center; justify-content: center; gap: 9px;
  transition: box-shadow .2s, transform .12s;
  letter-spacing: .01em;
}
.btn-login:hover  { box-shadow: 0 10px 30px rgba(37,99,235,.48); }
.btn-login:active { transform: scale(.97); }
/* Effet sheen */
.btn-login::after {
  content: ''; position: absolute; top: 0; left: -120%; width: 60%; height: 100%;
  background: linear-gradient(100deg, transparent, rgba(255,255,255,.42), transparent);
  transform: skewX(-18deg);
  animation: sheen 3.8s ease-in-out infinite;
}
@keyframes sheen { 0%,14%{left:-120%;} 42%,100%{left:170%;} }
@media (prefers-reduced-motion: reduce) { .btn-login::after { display: none; } }

/* Ligne sécurité */
.lm-secure {
  display: flex; align-items: center; gap: 8px; justify-content: center;
  margin-top: 16px; font-size: 12px; color: var(--text-mute); font-weight: 600;
}
.lm-secure i { color: var(--success); font-size: 14px; }

/* Pied de page */
.lm-foot {
  margin-top: 22px; text-align: center;
  font-size: 12px; color: var(--text-dim); line-height: 1.65;
}
.lm-foot .sep { margin: 14px 0; height: 1px; background: var(--hairline); }
.lm-foot b { color: var(--text-mute); font-weight: 700; }

/* ══ Responsive ══ */
@media (max-width: 720px) {
  .stage { padding: 0; align-items: stretch; }
  .login { grid-template-columns: 1fr; max-width: 480px; border-radius: 0; min-height: 100vh; }
  .login-aside { min-height: auto; padding: 28px 24px 24px; }
  .la-headline h1 { font-size: 24px; }
  .la-headline p, .la-points { display: none; }
  .login-main { padding: 32px 26px 42px; }
  .lm-head h2 { font-size: 22px; }
}
@media (min-width: 721px) and (max-width: 980px) {
  .login { max-width: 720px; }
  .login-aside { padding: 36px 30px; }
  .la-headline h1 { font-size: 26px; }
  .login-main { padding: 40px 34px; }
}
</style>
</head>
<body>

<!-- Orbs décoratifs -->
<div class="orbs" id="orbs"></div>

<div class="stage">
  <div class="login">

    <!-- ══ Panneau gauche — Marque ══ -->
    <aside class="login-aside">

      <!-- Marque -->
      <div class="la-brand">
        <div class="la-mark">
          <img src="<?= BASE_URL ?>public/images/simcare_plus_logo.svg" alt="SimCare+">
        </div>
        <div>
          <div class="la-brand-name">Sim<span>Care+</span></div>
          <div class="la-brand-sub">Dossier Médical Électronique</div>
        </div>
      </div>

      <!-- Accroche -->
      <div class="la-headline">
        <h1>Le parcours de soin,<br>entièrement numérisé.</h1>
        <p>Connectez-vous pour accéder aux dossiers patients, aux consultations et au suivi hospitalier en temps réel.</p>
      </div>

      <!-- Points forts + statut -->
      <div class="la-points">
        <div class="la-point">
          <span class="pi"><i class="bi bi-shield-lock-fill"></i></span>
          Accès sécurisé &amp; tracé par profil
        </div>
        <div class="la-point">
          <span class="pi"><i class="bi bi-hdd-network-fill"></i></span>
          Données souveraines &amp; sauvegardées
        </div>
        <div class="la-point">
          <span class="pi"><i class="bi bi-clock-history"></i></span>
          Disponible 24h/24, 7j/7
        </div>
        <div class="la-status">
          <span class="pulse"></span>
          Système opérationnel · 99.9% de disponibilité
        </div>
      </div>

    </aside>

    <!-- ══ Panneau droit — Formulaire ══ -->
    <main class="login-main">

      <div class="lm-badge"><i class="bi bi-shield-lock"></i></div>

      <div class="lm-head">
        <div class="eyebrow">Espace soignant</div>
        <h2>Bon retour parmi nous</h2>
        <p>Identifiez-vous pour accéder à votre espace.</p>
      </div>

      <?php if (isset($error)): ?>
      <div class="error-msg">
        <i class="bi bi-exclamation-circle-fill"></i>
        <?= htmlspecialchars($error) ?>
      </div>
      <?php endif; ?>

      <form method="POST" action="<?= BASE_URL ?>login">
        <?php echo CsrfService::field(); ?>

        <div class="field">
          <label>Identifiant</label>
          <div class="input-wrap">
            <i class="bi bi-person"></i>
            <input type="text" name="identifiant"
                   placeholder="Nom d'utilisateur ou email"
                   autocomplete="username" required>
          </div>
        </div>

        <div class="field">
          <label>Mot de passe</label>
          <div class="input-wrap">
            <i class="bi bi-lock"></i>
            <input type="password" name="password" id="pwdInput"
                   placeholder="••••••••"
                   autocomplete="current-password" required>
            <button type="button" class="toggle-pw" id="togglePwd" tabindex="-1"
                    aria-label="Afficher/masquer le mot de passe">
              <i class="bi bi-eye-slash" id="eyeIcon"></i>
            </button>
          </div>
        </div>

        <div class="row-between">
          <label class="remember" for="rememberMe">
            <input type="checkbox" name="remember_me" id="rememberMe" value="1"
                   style="position:absolute;clip:rect(0,0,0,0);width:1px;height:1px;" checked>
            <span class="box" id="rememberBox"><i class="bi bi-check-lg" id="rememberIcon"></i></span>
            Se souvenir de moi
          </label>
          <a class="lnk" href="<?= BASE_URL ?>forgot-password">Mot de passe oublié&nbsp;?</a>
        </div>

        <button type="submit" class="btn-login">
          <i class="bi bi-box-arrow-in-right"></i>
          Se connecter
        </button>

      </form>

      <div class="lm-secure">
        <i class="bi bi-shield-check"></i>
        Connexion chiffrée SSL · Hôpital Saint-Jean de Malte
      </div>

      <div class="lm-foot">
        <div class="sep"></div>
        <b>Hôpital Saint-Jean de Malte</b> · Njombé<br>
        SimCare+ v2.0 · Conçu par Franck Rodolph Simeni
      </div>

    </main>
  </div>
</div>

<script>
/* Orbs aurora variant 1 */
(function () {
  const palette = [
    ['#60a5fa', '12%',  '10%', '340px'],
    ['#a78bfa', '82%',  '12%', '300px'],
    ['#5eead4', '72%',  '88%', '320px'],
  ];
  const wrap = document.getElementById('orbs');
  palette.forEach(function ([c, l, t, s]) {
    const o = document.createElement('div');
    o.className = 'orb';
    o.style.cssText = 'background:' + c + ';left:' + l + ';top:' + t + ';width:' + s + ';height:' + s;
    wrap.appendChild(o);
  });
})();

/* Toggle mot de passe */
document.getElementById('togglePwd').addEventListener('click', function () {
  const inp  = document.getElementById('pwdInput');
  const icon = document.getElementById('eyeIcon');
  const show = inp.type === 'password';
  inp.type   = show ? 'text' : 'password';
  icon.className = show ? 'bi bi-eye' : 'bi bi-eye-slash';
});

/* Toggle "Se souvenir de moi" */
document.getElementById('rememberMe').addEventListener('change', function () {
  const box  = document.getElementById('rememberBox');
  const icon = document.getElementById('rememberIcon');
  if (this.checked) {
    box.style.background  = 'var(--primary)';
    box.style.border      = 'none';
    icon.style.opacity    = '1';
  } else {
    box.style.background  = 'transparent';
    box.style.border      = '1.5px solid rgba(100,116,139,.4)';
    icon.style.opacity    = '0';
  }
});
</script>

</body>
</html>
