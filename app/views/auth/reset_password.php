<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Réinitialisation du mot de passe — SimCare+</title>
<link rel="stylesheet" href="<?= BASE_URL ?>public/css/bootstrap-icons.min.css">
<link rel="stylesheet" href="<?= BASE_URL ?>public/css/fonts.css">
<style>
*, *::before, *::after { box-sizing: border-box; }
html, body { margin: 0; padding: 0; height: 100%; }
:root {
  --primary: #2563eb; --sky: #38bdf8;
  --glass-bg: rgba(255,255,255,.72); --glass-brd: rgba(255,255,255,.8);
  --glass-shadow: 0 12px 40px rgba(30,58,95,.16), inset 0 1px 0 rgba(255,255,255,.6);
  --text: #0f172a; --text-mute: #64748b; --text-dim: #94a3b8;
  --field-bg: rgba(255,255,255,.6); --field-brd: rgba(255,255,255,.72);
  --success: #22c55e; --hairline: rgba(15,23,42,.08);
  --page-bg:
    radial-gradient(1100px 800px at 12% 8%, #c7e0ff 0%, transparent 55%),
    radial-gradient(900px 700px at 92% 12%, #d9c9ff 0%, transparent 50%),
    linear-gradient(135deg, #eef4ff 0%, #f6f1ff 100%);
}
body { font-family: 'Plus Jakarta Sans', system-ui, sans-serif; color: var(--text);
  background: var(--page-bg); background-attachment: fixed;
  -webkit-font-smoothing: antialiased; min-height: 100vh; }
.orbs { position: fixed; inset: 0; overflow: hidden; pointer-events: none; z-index: 0; }
.orb  { position: absolute; border-radius: 50%; filter: blur(52px); opacity: .48; }
.stage { position: relative; z-index: 1; min-height: 100vh;
  display: flex; align-items: center; justify-content: center; padding: 24px; }
.card { width: 100%; max-width: 460px; background: var(--glass-bg);
  backdrop-filter: blur(22px) saturate(150%); -webkit-backdrop-filter: blur(22px) saturate(150%);
  border: 1px solid var(--glass-brd); border-radius: 26px;
  box-shadow: var(--glass-shadow); padding: 44px 42px; }
.card-badge { width: 54px; height: 54px; border-radius: 16px; margin-bottom: 20px;
  display: flex; align-items: center; justify-content: center; font-size: 24px;
  background: linear-gradient(135deg, rgba(37,99,235,.15), rgba(56,189,248,.1));
  border: 1px solid rgba(37,99,235,.22); color: var(--primary); }
.eyebrow { font-size: 11.5px; font-weight: 700; letter-spacing: .18em;
  text-transform: uppercase; color: var(--primary); }
h2 { font-size: 26px; font-weight: 800; letter-spacing: -.025em; margin: 8px 0 6px; }
.sub { font-size: 14px; color: var(--text-mute); margin-bottom: 26px; }
.field { margin-bottom: 16px; }
.field > label { display: block; font-size: 11.5px; font-weight: 700; color: var(--text-mute);
  letter-spacing: .05em; text-transform: uppercase; margin-bottom: 7px; }
.input-wrap { display: flex; align-items: center; gap: 10px;
  background: var(--field-bg); border: 1.5px solid var(--field-brd);
  border-radius: 14px; padding: 0 14px; height: 50px;
  backdrop-filter: blur(8px); transition: border-color .15s, box-shadow .15s; }
.input-wrap:focus-within { border-color: var(--primary); box-shadow: 0 0 0 4px rgba(37,99,235,.14); }
.input-wrap > i { color: var(--text-mute); font-size: 17px; flex-shrink: 0; }
.input-wrap > input { flex: 1; border: none; background: transparent; outline: none;
  font-family: inherit; font-size: 15px; color: var(--text); font-weight: 500; height: 100%; }
.input-wrap > input::placeholder { color: var(--text-dim); }
.input-wrap .toggle-pw { cursor: pointer; color: var(--text-dim); font-size: 17px;
  background: none; border: none; padding: 0; flex-shrink: 0; transition: color .15s; }
.input-wrap .toggle-pw:hover { color: var(--primary); }
.code-hint { font-size: 12px; color: var(--text-dim); margin-top: 6px; font-weight: 500; }
.btn-submit { position: relative; overflow: hidden; width: 100%; padding: 15px; border-radius: 16px;
  border: none; cursor: pointer; font-family: inherit; font-size: 15px; font-weight: 700;
  background: linear-gradient(135deg, var(--primary), var(--sky)); color: #fff;
  box-shadow: 0 8px 22px rgba(37,99,235,.32); display: flex; align-items: center;
  justify-content: center; gap: 9px; transition: box-shadow .2s, transform .12s; margin-top: 8px; }
.btn-submit:hover { box-shadow: 0 10px 30px rgba(37,99,235,.48); }
.btn-submit:active { transform: scale(.97); }
.btn-submit::after { content: ''; position: absolute; top: 0; left: -120%; width: 60%; height: 100%;
  background: linear-gradient(100deg, transparent, rgba(255,255,255,.4), transparent);
  transform: skewX(-18deg); animation: sheen 4s ease-in-out infinite; }
@keyframes sheen { 0%,14%{left:-120%;} 42%,100%{left:170%;} }
.alert { padding: 14px 16px; border-radius: 12px; margin-bottom: 20px;
  font-size: 13.5px; font-weight: 600; display: flex; align-items: flex-start; gap: 10px; }
.alert-err { background: rgba(239,68,68,.1); color: #b91c1c; border: 1px solid rgba(239,68,68,.22); }
/* Succès */
.success-state { text-align: center; padding: 10px 0; }
.success-state .ico { font-size: 52px; color: var(--success); margin-bottom: 16px;
  display: block; animation: pop .4s cubic-bezier(.34,1.56,.64,1); }
@keyframes pop { from{transform:scale(0);opacity:0} to{transform:scale(1);opacity:1} }
.success-state h3 { font-size: 22px; font-weight: 800; margin: 0 0 10px; }
.success-state p  { font-size: 14px; color: var(--text-mute); margin: 0 0 24px; }
.btn-login { display: inline-flex; align-items: center; gap: 8px; padding: 13px 28px;
  border-radius: 14px; font-family: inherit; font-size: 14px; font-weight: 700;
  background: linear-gradient(135deg, var(--primary), var(--sky)); color: #fff; border: none;
  cursor: pointer; text-decoration: none; box-shadow: 0 6px 18px rgba(37,99,235,.28); }
.divider { height: 1px; background: var(--hairline); margin: 18px 0; }
.back { display: flex; align-items: center; gap: 8px; margin-top: 4px;
  font-size: 13px; color: var(--text-mute); text-decoration: none; font-weight: 600; }
.back:hover { color: var(--primary); }
</style>
</head>
<body>
<div class="orbs" id="orbs"></div>
<div class="stage">
  <div class="card">

    <div class="card-badge"><i class="bi bi-shield-lock"></i></div>
    <div class="eyebrow">Espace soignant</div>

    <?php if ($success === true): ?>
      <!-- ══ Succès ══ -->
      <div class="success-state">
        <i class="bi bi-check-circle-fill ico"></i>
        <h3>Mot de passe modifié !</h3>
        <p>Votre nouveau mot de passe est actif. Vous pouvez maintenant vous connecter.</p>
        <a href="<?= BASE_URL ?>login" class="btn-login">
          <i class="bi bi-box-arrow-in-right"></i> Se connecter
        </a>
      </div>

    <?php else: ?>
      <!-- ══ Formulaire ══ -->
      <h2>Nouveau mot de passe</h2>
      <p class="sub">Saisissez le code reçu lors de votre demande, puis choisissez un nouveau mot de passe.</p>

      <?php if ($error): ?>
      <div class="alert alert-err">
        <i class="bi bi-exclamation-circle-fill" style="flex-shrink:0;margin-top:1px"></i>
        <?= htmlspecialchars($error) ?>
      </div>
      <?php endif; ?>

      <form method="POST" action="<?= BASE_URL ?>reset-password">
        <?php echo CsrfService::field(); ?>

        <div class="field">
          <label>Code de réinitialisation</label>
          <div class="input-wrap">
            <i class="bi bi-key"></i>
            <input type="text" name="code" placeholder="Ex : AB3X7K2M"
                   autocomplete="off" maxlength="8" style="text-transform:uppercase;letter-spacing:.15em;font-weight:700" required>
          </div>
          <div class="code-hint"><i class="bi bi-info-circle"></i>&nbsp;
            Code affiché lors de votre demande (8 caractères, valide 1h).</div>
        </div>

        <div class="field">
          <label>Nouveau mot de passe</label>
          <div class="input-wrap">
            <i class="bi bi-lock"></i>
            <input type="password" name="new_password" id="pwd1"
                   placeholder="Au moins 8 caractères" autocomplete="new-password" required>
            <button type="button" class="toggle-pw" onclick="togglePwd('pwd1','eye1')" tabindex="-1">
              <i class="bi bi-eye-slash" id="eye1"></i>
            </button>
          </div>
        </div>

        <div class="field">
          <label>Confirmer le mot de passe</label>
          <div class="input-wrap">
            <i class="bi bi-lock-fill"></i>
            <input type="password" name="confirm_password" id="pwd2"
                   placeholder="Répétez le mot de passe" autocomplete="new-password" required>
            <button type="button" class="toggle-pw" onclick="togglePwd('pwd2','eye2')" tabindex="-1">
              <i class="bi bi-eye-slash" id="eye2"></i>
            </button>
          </div>
        </div>

        <button type="submit" class="btn-submit">
          <i class="bi bi-check2-circle"></i> Réinitialiser le mot de passe
        </button>
      </form>

      <div class="divider"></div>
      <a href="<?= BASE_URL ?>forgot-password" class="back">
        <i class="bi bi-arrow-left"></i> Faire une nouvelle demande
      </a>
    <?php endif; ?>

  </div>
</div>
<script>
(function () {
  const orbs = [['#60a5fa','12%','10%','320px'],['#a78bfa','82%','12%','280px'],['#5eead4','72%','88%','300px']];
  const wrap = document.getElementById('orbs');
  orbs.forEach(function([c,l,t,s]){ const o=document.createElement('div'); o.className='orb';
    o.style.cssText='background:'+c+';left:'+l+';top:'+t+';width:'+s+';height:'+s; wrap.appendChild(o); });
})();
function togglePwd(id, eyeId) {
  const inp  = document.getElementById(id);
  const icon = document.getElementById(eyeId);
  const show = inp.type === 'password';
  inp.type   = show ? 'text' : 'password';
  icon.className = show ? 'bi bi-eye' : 'bi bi-eye-slash';
}
</script>
</body>
</html>
