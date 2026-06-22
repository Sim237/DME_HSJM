<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Changement de mot de passe — SimCare+</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>public/css/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>public/css/fonts.css">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            min-height: 100vh;
            background: linear-gradient(135deg, #0f172a 0%, #1e3a8a 50%, #1e40af 100%);
            display: flex; align-items: center; justify-content: center;
            font-family: 'Inter', sans-serif;
        }
        .card {
            background: rgba(255,255,255,.97);
            border-radius: 24px;
            padding: 44px 40px;
            width: 100%; max-width: 440px;
            box-shadow: 0 32px 80px rgba(0,0,0,.35);
        }
        .icon-lock {
            width: 64px; height: 64px; border-radius: 18px;
            background: linear-gradient(135deg, #f97316, #ef4444);
            display: flex; align-items: center; justify-content: center;
            font-size: 1.8rem; color: #fff; margin: 0 auto 20px;
            box-shadow: 0 8px 24px rgba(239,68,68,.35);
        }
        h1 { font-size: 1.45rem; font-weight: 800; color: #0f172a; text-align:center; margin-bottom: 6px; }
        .subtitle { font-size: .85rem; color: #64748b; text-align: center; margin-bottom: 28px; line-height:1.5; }
        .alert-warning {
            background: #fff7ed; border: 1.5px solid #fed7aa;
            border-radius: 12px; padding: 12px 16px;
            color: #92400e; font-size: .82rem; margin-bottom: 20px;
            display: flex; align-items: flex-start; gap: 8px;
        }
        .alert-danger {
            background: #fef2f2; border: 1.5px solid #fecaca;
            border-radius: 12px; padding: 12px 16px;
            color: #991b1b; font-size: .82rem; margin-bottom: 20px;
        }
        .form-group { margin-bottom: 18px; }
        label { display: block; font-size: .8rem; font-weight: 700; color: #374151; margin-bottom: 6px; }
        .input-wrap { position: relative; }
        input[type="password"], input[type="text"] {
            width: 100%; padding: 12px 44px 12px 14px;
            border: 1.5px solid #e2e8f0; border-radius: 12px;
            font-size: .9rem; color: #1e293b;
            transition: border-color .15s, box-shadow .15s;
            outline: none;
        }
        input:focus { border-color: #ef4444; box-shadow: 0 0 0 3px rgba(239,68,68,.12); }
        .toggle-pwd {
            position: absolute; right: 12px; top: 50%; transform: translateY(-50%);
            background: none; border: none; cursor: pointer; color: #94a3b8; font-size: 1rem;
        }
        .strength-bar { height: 4px; border-radius: 2px; margin-top: 6px; background: #e2e8f0; overflow: hidden; }
        .strength-fill { height: 100%; border-radius: 2px; transition: width .3s, background .3s; width: 0; }
        .strength-label { font-size: .7rem; margin-top: 3px; }
        .requirements { font-size: .72rem; color: #64748b; margin-top: 8px; display: flex; flex-direction: column; gap: 2px; }
        .req { display: flex; align-items: center; gap: 5px; }
        .req.ok { color: #16a34a; }
        .req.fail { color: #94a3b8; }
        .btn-submit {
            width: 100%; padding: 14px;
            background: linear-gradient(135deg, #f97316, #ef4444);
            color: #fff; border: none; border-radius: 12px;
            font-size: .95rem; font-weight: 700; cursor: pointer;
            margin-top: 6px; transition: opacity .15s, transform .1s;
            box-shadow: 0 4px 16px rgba(239,68,68,.35);
        }
        .btn-submit:hover { opacity: .92; }
        .btn-submit:active { transform: scale(.98); }
        .logout-link {
            display: block; text-align: center; margin-top: 18px;
            font-size: .8rem; color: #94a3b8; text-decoration: none;
        }
        .logout-link:hover { color: #ef4444; }
    </style>
</head>
<body>
<div class="card">
    <div class="icon-lock"><i class="bi bi-shield-lock-fill"></i></div>
    <h1>Changement de mot de passe</h1>
    <p class="subtitle">
        L'administrateur vous demande de définir un nouveau mot de passe<br>avant de continuer.
    </p>

    <div class="alert-warning">
        <i class="bi bi-exclamation-triangle-fill" style="flex-shrink:0;margin-top:1px;"></i>
        <span>Vous ne pouvez pas accéder à l'application tant que vous n'avez pas changé votre mot de passe.</span>
    </div>

    <?php if (!empty($error)): ?>
    <div class="alert-danger"><i class="bi bi-x-circle-fill me-2"></i><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="<?= BASE_URL ?>changer-mot-de-passe" id="formChangePwd" autocomplete="off">
        <?= CsrfService::field() ?>

        <div class="form-group">
            <label>Nouveau mot de passe</label>
            <div class="input-wrap">
                <input type="password" name="new_password" id="newPwd" required
                       placeholder="Minimum 8 caractères" oninput="checkStrength(this.value)">
                <button type="button" class="toggle-pwd" onclick="toggleVisibility('newPwd', this)">
                    <i class="bi bi-eye"></i>
                </button>
            </div>
            <div class="strength-bar"><div class="strength-fill" id="strengthFill"></div></div>
            <div class="strength-label" id="strengthLabel" style="color:#94a3b8;"></div>
            <div class="requirements" id="reqList">
                <div class="req fail" id="req-len"><i class="bi bi-circle"></i> 8 caractères minimum</div>
                <div class="req fail" id="req-upp"><i class="bi bi-circle"></i> Une majuscule</div>
                <div class="req fail" id="req-num"><i class="bi bi-circle"></i> Un chiffre</div>
            </div>
        </div>

        <div class="form-group">
            <label>Confirmer le mot de passe</label>
            <div class="input-wrap">
                <input type="password" name="confirm_password" id="confirmPwd" required
                       placeholder="Répéter le mot de passe" oninput="checkMatch()">
                <button type="button" class="toggle-pwd" onclick="toggleVisibility('confirmPwd', this)">
                    <i class="bi bi-eye"></i>
                </button>
            </div>
            <div id="matchMsg" style="font-size:.72rem;margin-top:4px;"></div>
        </div>

        <button type="submit" class="btn-submit" id="btnSubmit">
            <i class="bi bi-check-circle-fill me-2"></i>Définir le nouveau mot de passe
        </button>
    </form>

    <a href="<?= BASE_URL ?>logout" class="logout-link">
        <i class="bi bi-box-arrow-left me-1"></i>Se déconnecter
    </a>
</div>

<script>
function toggleVisibility(id, btn) {
    const inp = document.getElementById(id);
    const isText = inp.type === 'text';
    inp.type = isText ? 'password' : 'text';
    btn.innerHTML = isText ? '<i class="bi bi-eye"></i>' : '<i class="bi bi-eye-slash"></i>';
}

function checkStrength(val) {
    const fill  = document.getElementById('strengthFill');
    const label = document.getElementById('strengthLabel');
    let score = 0;
    if (val.length >= 8)         { score++; setReq('req-len', true); } else setReq('req-len', false);
    if (/[A-Z]/.test(val))       { score++; setReq('req-upp', true); } else setReq('req-upp', false);
    if (/[0-9]/.test(val))       { score++; setReq('req-num', true); } else setReq('req-num', false);
    if (/[^A-Za-z0-9]/.test(val)) score++;
    const pct = score * 25;
    const colors = ['#ef4444','#f97316','#eab308','#22c55e'];
    const labels = ['Très faible','Faible','Moyen','Fort','Très fort'];
    fill.style.width = pct + '%';
    fill.style.background = colors[score - 1] || '#e2e8f0';
    label.textContent = val.length ? labels[score] || '' : '';
    label.style.color = colors[score - 1] || '#94a3b8';
}

function setReq(id, ok) {
    const el = document.getElementById(id);
    el.className = 'req ' + (ok ? 'ok' : 'fail');
    el.querySelector('i').className = 'bi bi-' + (ok ? 'check-circle-fill' : 'circle');
}

function checkMatch() {
    const a = document.getElementById('newPwd').value;
    const b = document.getElementById('confirmPwd').value;
    const m = document.getElementById('matchMsg');
    if (!b) { m.textContent = ''; return; }
    if (a === b) { m.style.color = '#16a34a'; m.textContent = '✓ Les mots de passe correspondent'; }
    else         { m.style.color = '#ef4444'; m.textContent = '✗ Les mots de passe ne correspondent pas'; }
}

document.getElementById('formChangePwd').addEventListener('submit', function(e) {
    const btn = document.getElementById('btnSubmit');
    btn.disabled = true;
    btn.innerHTML = '<span style="display:inline-block;width:16px;height:16px;border:2px solid #fff;border-top-color:transparent;border-radius:50%;animation:spin .7s linear infinite;vertical-align:middle;margin-right:8px;"></span>Enregistrement…';
});
</script>
<style>@keyframes spin{to{transform:rotate(360deg)}}</style>
</body>
</html>
