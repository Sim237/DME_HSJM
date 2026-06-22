<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Mot de passe oublié — SimCare+</title>
<link rel="stylesheet" href="<?= BASE_URL ?>public/css/bootstrap-icons.min.css">
<link rel="stylesheet" href="<?= BASE_URL ?>public/css/fonts.css">
<style>
*, *::before, *::after { box-sizing: border-box; }
html, body { margin: 0; padding: 0; height: 100%; }
:root {
  --primary: #2563eb; --sky: #38bdf8; --navy: #0f172a;
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
.card {
  width: 100%; max-width: 460px;
  background: var(--glass-bg);
  backdrop-filter: blur(22px) saturate(150%); -webkit-backdrop-filter: blur(22px) saturate(150%);
  border: 1px solid var(--glass-brd); border-radius: 26px;
  box-shadow: var(--glass-shadow); padding: 44px 42px;
}
.card-badge {
  width: 54px; height: 54px; border-radius: 16px; margin-bottom: 20px;
  display: flex; align-items: center; justify-content: center; font-size: 24px;
  background: linear-gradient(135deg, rgba(37,99,235,.15), rgba(56,189,248,.1));
  border: 1px solid rgba(37,99,235,.22); color: var(--primary);
}
.eyebrow { font-size: 11.5px; font-weight: 700; letter-spacing: .18em;
  text-transform: uppercase; color: var(--primary); }
h2 { font-size: 26px; font-weight: 800; letter-spacing: -.025em; margin: 8px 0 6px; }
.sub { font-size: 14px; color: var(--text-mute); margin-bottom: 26px; }
.field { margin-bottom: 18px; }
.field > label { display: block; font-size: 11.5px; font-weight: 700; color: var(--text-mute);
  letter-spacing: .05em; text-transform: uppercase; margin-bottom: 7px; }
.input-wrap { display: flex; align-items: center; gap: 10px;
  background: var(--field-bg); border: 1.5px solid var(--field-brd);
  border-radius: 14px; padding: 0 14px; height: 50px;
  backdrop-filter: blur(8px); transition: border-color .15s, box-shadow .15s; }
.input-wrap:focus-within { border-color: var(--primary); box-shadow: 0 0 0 4px rgba(37,99,235,.14); }
.input-wrap > i { color: var(--text-mute); font-size: 17px; }
.input-wrap > input { flex: 1; border: none; background: transparent; outline: none;
  font-family: inherit; font-size: 15px; color: var(--text); font-weight: 500; height: 100%; }
.input-wrap > input::placeholder { color: var(--text-dim); }
.btn-submit { position: relative; overflow: hidden; width: 100%; padding: 15px; border-radius: 16px;
  border: none; cursor: pointer; font-family: inherit; font-size: 15px; font-weight: 700;
  background: linear-gradient(135deg, var(--primary), var(--sky)); color: #fff;
  box-shadow: 0 8px 22px rgba(37,99,235,.32); display: flex; align-items: center;
  justify-content: center; gap: 9px; transition: box-shadow .2s, transform .12s; margin-top: 6px; }
.btn-submit:hover { box-shadow: 0 10px 30px rgba(37,99,235,.48); }
.btn-submit:active { transform: scale(.97); }
.btn-submit::after { content: ''; position: absolute; top: 0; left: -120%; width: 60%; height: 100%;
  background: linear-gradient(100deg, transparent, rgba(255,255,255,.4), transparent);
  transform: skewX(-18deg); animation: sheen 4s ease-in-out infinite; }
@keyframes sheen { 0%,14%{left:-120%;} 42%,100%{left:170%;} }
.alert { padding: 14px 16px; border-radius: 12px; margin-bottom: 20px;
  font-size: 13.5px; font-weight: 600; display: flex; align-items: flex-start; gap: 10px; }
.alert-err { background: rgba(239,68,68,.1); color: #b91c1c; border: 1px solid rgba(239,68,68,.22); }
.alert-ok  { background: rgba(34,197,94,.1); color: #166534; border: 1px solid rgba(34,197,94,.25); }
/* Boîte code */
.code-box {
  background: linear-gradient(135deg, #1e3a8a, #1e40af);
  border-radius: 18px; padding: 24px; text-align: center; margin: 20px 0; color: #fff;
}
.code-box .label { font-size: 12px; font-weight: 700; text-transform: uppercase;
  letter-spacing: .14em; color: rgba(255,255,255,.65); margin-bottom: 12px; }
.code-box .code  { font-size: 36px; font-weight: 900; letter-spacing: .22em;
  font-variant-numeric: tabular-nums; color: #fff; margin: 0 0 10px; }
.code-box .exp  { font-size: 12px; color: rgba(255,255,255,.55); }
.code-box .warn { font-size: 13px; color: #fcd34d; margin-top: 12px;
  padding-top: 12px; border-top: 1px solid rgba(255,255,255,.15); }
/* Retour login */
.back { display: flex; align-items: center; gap: 8px; margin-top: 22px;
  font-size: 13px; color: var(--text-mute); text-decoration: none; font-weight: 600; }
.back i { font-size: 15px; }
.back:hover { color: var(--primary); }
.divider { height: 1px; background: var(--hairline); margin: 18px 0; }
.link-reset { font-size: 13px; color: var(--primary); font-weight: 700;
  text-decoration: none; display: flex; align-items: center; gap: 6px; }
.link-reset:hover { text-decoration: underline; }
</style>
</head>
<body>
<div class="orbs" id="orbs"></div>
<div class="stage">
  <div class="card">

    <div class="card-badge"><i class="bi bi-key"></i></div>
    <div class="eyebrow">Espace soignant</div>
    <h2>Mot de passe oublié</h2>

    <?php if ($success && $token): ?>
      <!-- ══ Succès : afficher le code ══ -->
      <p class="sub">Votre code de réinitialisation est valable <strong>1 heure</strong>.</p>
      <div class="code-box">
        <div class="label"><i class="bi bi-lock-fill"></i> &nbsp;Code de réinitialisation</div>
        <div class="code"><?= htmlspecialchars($token) ?></div>
        <div class="exp">Expire le <?= date('d/m/Y à H:i', time() + 3600) ?></div>
        <div class="warn"><i class="bi bi-exclamation-triangle-fill"></i>&nbsp;
          Notez ce code maintenant — il ne sera plus affiché.</div>
      </div>
      <a href="<?= BASE_URL ?>reset-password" class="link-reset">
        <i class="bi bi-arrow-right-circle"></i>
        Utiliser ce code pour réinitialiser mon mot de passe
      </a>

    <?php elseif ($success && !$token): ?>
      <!-- ══ Compte non trouvé (discret) ══ -->
      <div class="alert alert-ok">
        <i class="bi bi-info-circle-fill" style="flex-shrink:0;margin-top:1px"></i>
        <span><?= htmlspecialchars($success) ?> Si vous ne trouvez aucun code,
        contactez l'administrateur système.</span>
      </div>

    <?php else: ?>
      <!-- ══ Formulaire de demande ══ -->
      <p class="sub">Saisissez votre identifiant ou email. Un code de réinitialisation sera généré.</p>

      <?php if ($error): ?>
      <div class="alert alert-err">
        <i class="bi bi-exclamation-circle-fill" style="flex-shrink:0;margin-top:1px"></i>
        <?= htmlspecialchars($error) ?>
      </div>
      <?php endif; ?>

      <form method="POST" action="<?= BASE_URL ?>forgot-password">
        <?php echo CsrfService::field(); ?>
        <div class="field">
          <label>Identifiant ou email</label>
          <div class="input-wrap">
            <i class="bi bi-person"></i>
            <input type="text" name="identifiant" placeholder="Votre nom d'utilisateur ou email"
                   autocomplete="username" autofocus required>
          </div>
        </div>
        <button type="submit" class="btn-submit">
          <i class="bi bi-send"></i> Générer mon code
        </button>
      </form>
    <?php endif; ?>

    <div class="divider"></div>
    <a href="<?= BASE_URL ?>login" class="back">
      <i class="bi bi-arrow-left"></i> Retour à la connexion
    </a>
  </div>
</div>
<script>
(function () {
  const orbs = [['#60a5fa','12%','10%','320px'],['#a78bfa','82%','12%','280px'],['#5eead4','72%','88%','300px']];
  const wrap = document.getElementById('orbs');
  orbs.forEach(function([c,l,t,s]){ const o=document.createElement('div'); o.className='orb';
    o.style.cssText='background:'+c+';left:'+l+';top:'+t+';width:'+s+';height:'+s; wrap.appendChild(o); });
})();
</script>
</body>
</html>
