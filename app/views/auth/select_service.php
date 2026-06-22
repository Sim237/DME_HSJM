<?php
/* ── Mapping service name → icône + couleur ── */
function svc_icon(string $nom): array {
    $n = strtolower($nom);
    if (strpos($n, 'urgence')  !== false) return ['bi-lightning-charge-fill', '#e11d48', 'Salle des urgences'];
    if (strpos($n, 'accueil')  !== false) return ['bi-person-vcard-fill',     '#475569', 'Admission · Secrétariat'];
    if (strpos($n, 'consul')   !== false) return ['bi-stethoscope',            '#1d6fe0', 'Médecine générale'];
    if (strpos($n, 'hospit')   !== false) return ['bi-hospital-fill',          '#0d9488', 'Soins continus'];
    if (strpos($n, 'labo')     !== false) return ['bi-eyedropper-fill',        '#7c3aed', 'Analyses biologiques'];
    if (strpos($n, 'pharma')   !== false) return ['bi-capsule-fill',           '#d97706', 'Dispensation'];
    if (strpos($n, 'imagerie') !== false
     || strpos($n, 'radio')    !== false) return ['bi-radioactive',            '#0ea5e9', 'Radiologie · Écho'];
    if (strpos($n, 'matern')   !== false) return ['bi-gender-female',          '#db2777', 'Gynéco-obstétrique'];
    if (strpos($n, 'p\xc3\xa9di') !== false
     || strpos($n, 'pedi')     !== false) return ['bi-balloon-heart-fill',     '#16a34a', 'Soins pédiatriques'];
    if (strpos($n, 'bloc')     !== false) return ['bi-scissors',               '#dc2626', 'Bloc opératoire'];
    if (strpos($n, 'r\xc3\xa9anim') !== false
     || strpos($n, 'reanim')   !== false) return ['bi-activity',               '#ef4444', 'Soins intensifs'];
    if (strpos($n, 'param')    !== false
     || strpos($n, 'b1')       !== false
     || strpos($n, 'b2')       !== false) return ['bi-clipboard-pulse-fill',   '#059669', 'Bureau des paramètres'];
    if (strpos($n, 'kiosque')  !== false) return ['bi-display-fill',           '#6366f1', 'Borne patient'];
    return ['bi-buildings-fill', '#2563eb', 'Unité de soin'];
}

/* ── Données utilisateur temporaire ── */
$tu      = $_SESSION['temp_user'] ?? [];
$prenom  = htmlspecialchars($tu['prenom'] ?? '');
$nom     = htmlspecialchars($tu['nom']    ?? '');
$initiales = strtoupper(
    substr($tu['prenom'] ?? '?', 0, 1) . substr($tu['nom'] ?? '', 0, 1)
);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Sélection du service — SimCare+</title>
<link rel="stylesheet" href="<?= BASE_URL ?>public/css/bootstrap-icons.min.css">
<link rel="stylesheet" href="<?= BASE_URL ?>public/css/fonts.css">
<style>
/* ══════════════════════════════════════════════════════════════
   SimCare+ · Service selection — aurora soft
══════════════════════════════════════════════════════════════ */
*, *::before, *::after { box-sizing: border-box; }
html, body { margin: 0; padding: 0; height: 100%; }

:root {
  --primary:    #1d6fe0;
  --primary-2:  #0ea5e9;
  --ring:       rgba(29,111,224,.28);
  --ink:        #0f172a;
  --ink-2:      #475569;
  --ink-3:      #8da2bd;
  --line:       rgba(15,23,42,.08);
  --surface:    #ffffff;
  --surface-2:  #f4f8fd;
  --page-bg:
    radial-gradient(1100px 800px at 12%  8%, #c7e0ff 0%, transparent 55%),
    radial-gradient(900px  700px at 92% 12%, #d9c9ff 0%, transparent 50%),
    radial-gradient(1000px 900px at 78% 96%, #b6f3ff 0%, transparent 55%),
    linear-gradient(135deg, #eef4ff 0%, #f6f1ff 100%);
}

body {
  font-family: 'Plus Jakarta Sans', system-ui, sans-serif;
  color: var(--ink); -webkit-font-smoothing: antialiased;
  min-height: 100vh;
  background: var(--page-bg); background-attachment: fixed;
  display: flex; align-items: center; justify-content: center;
  padding: 28px 20px; position: relative;
}

/* Orbs */
.orbs { position: fixed; inset: 0; pointer-events: none; z-index: 0; overflow: hidden; }
.orb  { position: absolute; border-radius: 50%; filter: blur(56px); opacity: .46; }

/* ── Panel ── */
.panel {
  position: relative; z-index: 1;
  width: 100%; max-width: 780px;
  background: rgba(255,255,255,.78);
  backdrop-filter: blur(24px) saturate(140%);
  -webkit-backdrop-filter: blur(24px) saturate(140%);
  border: 1px solid rgba(255,255,255,.8);
  border-radius: 28px;
  padding: 38px 40px 32px;
  box-shadow: 0 20px 60px rgba(15,40,90,.14), inset 0 1px 0 rgba(255,255,255,.7);
}

/* ── Brand ── */
.brandrow {
  display: flex; align-items: center; gap: 13px;
  justify-content: center; margin-bottom: 20px;
}
.logo-wrap {
  width: 52px; height: 52px; border-radius: 15px; flex-shrink: 0;
  background: #fff; border: 1px solid rgba(29,111,224,.15);
  display: flex; align-items: center; justify-content: center;
  box-shadow: 0 4px 14px rgba(29,111,224,.16);
}
.logo-wrap img { width: 38px; height: 38px; object-fit: contain; }
.brand-name { font-size: 22px; font-weight: 800; letter-spacing: -.02em; }
.brand-name .plus { color: var(--primary); }

/* ── Head ── */
.head { text-align: center; margin-bottom: 22px; }
.head h1 { margin: 0; font-size: 26px; font-weight: 800; letter-spacing: -.025em; }
.head p  { margin: 6px 0 0; color: var(--ink-2); font-size: 14px; font-weight: 500; }
.user-pill {
  display: inline-flex; align-items: center; gap: 8px; margin-top: 14px;
  background: var(--surface-2); border: 1px solid var(--line); border-radius: 999px;
  padding: 6px 14px 6px 7px; font-size: 13px; font-weight: 600; color: var(--ink-2);
}
.user-pill .av {
  width: 26px; height: 26px; border-radius: 50%;
  background: linear-gradient(135deg, var(--primary), var(--primary-2));
  color: #fff; display: flex; align-items: center; justify-content: center;
  font-size: 10px; font-weight: 800; flex-shrink: 0;
}
.user-pill b { color: var(--ink); }

/* ── Error ── */
.alert-err {
  background: rgba(239,68,68,.09); color: #b91c1c;
  border: 1px solid rgba(239,68,68,.22); border-radius: 13px;
  padding: 12px 16px; margin-bottom: 18px; font-size: 13.5px;
  font-weight: 600; display: flex; align-items: center; gap: 9px;
}

/* ── Search ── */
.field-row {
  display: flex; align-items: center; justify-content: space-between;
  margin-bottom: 10px;
}
.field-row .lbl { font-size: 11px; font-weight: 800; letter-spacing: .14em;
  text-transform: uppercase; color: var(--ink-3); }
.field-row .hint-txt { font-size: 12px; font-weight: 600; color: var(--ink-3);
  transition: color .15s; }
.field-row .hint-txt.has { color: var(--primary); }

.search {
  display: flex; align-items: center; gap: 10px;
  background: var(--surface-2); border: 1.5px solid var(--line);
  border-radius: 13px; padding: 11px 15px; margin-bottom: 14px;
  transition: border-color .15s, box-shadow .15s;
}
.search:focus-within { border-color: var(--primary); box-shadow: 0 0 0 4px var(--ring); }
.search i { color: var(--ink-3); font-size: 16px; }
.search input { flex: 1; border: none; background: transparent; outline: none;
  font-family: inherit; font-size: 14px; color: var(--ink); }
.search input::placeholder { color: var(--ink-3); }

/* ── Grid ── */
.grid {
  display: grid; grid-template-columns: repeat(3, 1fr); gap: 11px;
  max-height: 320px; overflow-y: auto; padding: 3px; margin: 0 -3px;
}
.grid::-webkit-scrollbar { width: 7px; }
.grid::-webkit-scrollbar-thumb { background: #cdd9ea; border-radius: 8px; }

.tile {
  position: relative; text-align: left; cursor: pointer;
  font-family: inherit; background: var(--surface);
  border: 1.5px solid var(--line); border-radius: 15px;
  padding: 15px 14px; display: flex; flex-direction: column; gap: 9px;
  transition: transform .12s, border-color .15s, box-shadow .2s;
}
.tile:hover { transform: translateY(-2px);
  border-color: #bcd2f2; box-shadow: 0 8px 22px rgba(15,40,90,.1); }
.tile.sel {
  border-color: var(--primary);
  background: linear-gradient(160deg, #f0f6ff, #ffffff);
  box-shadow: 0 0 0 4px var(--ring);
}
.tile.hidden { display: none; }

.tile .ic {
  width: 42px; height: 42px; border-radius: 12px;
  display: flex; align-items: center; justify-content: center;
  font-size: 20px; color: #fff;
  background: var(--tc);
  box-shadow: 0 6px 14px rgba(0,0,0,.14);
}
.tile .nm   { font-size: 14.5px; font-weight: 800; letter-spacing: -.01em; line-height: 1.2; }
.tile .meta { display: flex; align-items: center; gap: 6px;
  font-size: 11px; font-weight: 600; color: var(--ink-3); }
.tile .meta .dot { width: 5px; height: 5px; border-radius: 50%; background: var(--tc); flex-shrink: 0; }
.tile .check {
  position: absolute; top: 11px; right: 11px;
  width: 22px; height: 22px; border-radius: 50%;
  background: var(--primary); color: #fff;
  display: flex; align-items: center; justify-content: center; font-size: 12px;
  opacity: 0; transform: scale(.4); transition: all .18s cubic-bezier(.34,1.56,.64,1);
}
.tile.sel .check { opacity: 1; transform: scale(1); }

.empty { grid-column: 1/-1; text-align: center; color: var(--ink-3);
  padding: 30px 0; font-size: 14px; font-weight: 600; }

/* ── Footer ── */
.footer { margin-top: 20px; }

.confirm {
  position: relative; overflow: hidden;
  width: 100%; display: flex; align-items: center; justify-content: center; gap: 10px;
  font-family: inherit; font-size: 15px; font-weight: 800; cursor: pointer;
  border: none; border-radius: 15px; padding: 16px; color: #fff;
  background: linear-gradient(135deg, var(--primary), var(--primary-2));
  box-shadow: 0 10px 26px rgba(29,111,224,.35);
  transition: filter .15s, transform .1s, opacity .2s;
  letter-spacing: .01em;
}
.confirm::after {
  content: ''; position: absolute; top: 0; left: -120%; width: 60%; height: 100%;
  background: linear-gradient(100deg, transparent, rgba(255,255,255,.38), transparent);
  transform: skewX(-18deg); animation: sheen 4s ease-in-out infinite;
}
@keyframes sheen { 0%,14%{left:-120%;} 42%,100%{left:170%;} }
.confirm:hover:not(:disabled) { filter: brightness(1.06); }
.confirm:active:not(:disabled) { transform: translateY(1px); }
.confirm:disabled { opacity: .42; cursor: not-allowed;
  box-shadow: none; background: #9fb3cc; }
.confirm:disabled::after { display: none; }

.btn-logout {
  display: flex; align-items: center; justify-content: center;
  gap: 8px; margin-top: 14px;
  background: none; border: none; cursor: pointer; font-family: inherit;
  color: var(--ink-3); font-size: 13px; font-weight: 600; padding: 6px; width: 100%;
  transition: color .15s;
}
.btn-logout:hover { color: #e11d48; }

/* ── Responsive ── */
@media (max-width: 680px) {
  .panel { padding: 28px 22px 26px; }
  .grid  { grid-template-columns: repeat(2, 1fr); max-height: 290px; }
  .head h1 { font-size: 22px; }
}
@media (max-width: 420px) {
  .grid { grid-template-columns: 1fr 1fr; }
}
</style>
</head>
<body>

<div class="orbs" id="orbs"></div>

<div class="panel">

  <!-- Brand -->
  <div class="brandrow">
    <div class="logo-wrap">
      <img src="<?= BASE_URL ?>public/images/simcare_plus_logo.svg" alt="SimCare+">
    </div>
    <div class="brand-name">Sim<span class="plus">Care+</span></div>
  </div>

  <!-- Head -->
  <div class="head">
    <h1>Sélection du service</h1>
    <p>Choisissez votre unité de travail pour cette session</p>
    <div class="user-pill">
      <span class="av"><?= $initiales ?></span>
      Connecté&nbsp;: <b><?= $prenom ?> <?= $nom ?></b>
    </div>
  </div>

  <!-- Bandeau GARDE ACTIVE -->
  <?php if (!empty($gardeActive)): ?>
  <div style="background:linear-gradient(135deg,#1e3a5f,#1d4ed8);color:#fff;border-radius:14px;
              padding:12px 18px;display:flex;align-items:center;gap:12px;margin-bottom:16px;
              box-shadow:0 4px 18px rgba(29,78,216,.35);">
      <span style="width:10px;height:10px;border-radius:50%;background:#34d399;
                   display:inline-block;flex-shrink:0;animation:gpulse 1.6s ease infinite;"></span>
      <style>@keyframes gpulse{0%,100%{opacity:1}50%{opacity:.4}}</style>
      <div>
          <div style="font-weight:800;font-size:.88rem;">Garde active — Accès universel</div>
          <?php if (!empty($gardeInfos)): ?>
          <div style="font-size:.75rem;opacity:.8;margin-top:2px;">
              <?= htmlspecialchars(ucfirst($gardeInfos['type_garde'] ?? '')) ?> ·
              Fin : <?= date('d/m H:i', strtotime($gardeInfos['date_fin'])) ?>
          </div>
          <?php endif; ?>
      </div>
      <span style="margin-left:auto;font-size:.75rem;opacity:.75;">Tous les services sont accessibles</span>
  </div>
  <?php endif; ?>

  <!-- Erreur accès refusé -->
  <?php if (isset($error)): ?>
  <div class="alert-err">
    <i class="bi bi-shield-exclamation"></i>
    <?= htmlspecialchars($error) ?>
  </div>
  <?php endif; ?>

  <!-- Formulaire -->
  <form method="POST" action="<?= BASE_URL ?>verify-service" id="svcForm">
    <?php echo CsrfService::field(); ?>
    <input type="hidden" name="service_id" id="selectedServiceId">

    <!-- Recherche -->
    <div class="field-row">
      <span class="lbl">Unité de soin</span>
      <span class="hint-txt" id="hintTxt">Sélectionnez un service</span>
    </div>
    <div class="search">
      <i class="bi bi-search"></i>
      <input type="text" id="searchQ" placeholder="Rechercher un service…" autocomplete="off">
    </div>

    <!-- Grille des services -->
    <div class="grid" id="grid">
      <?php
        $tuServiceId = (int)($_SESSION['temp_user']['service_id'] ?? 0);
        $isAdmin     = in_array(strtoupper($_SESSION['temp_user']['role'] ?? ''), ['ADMIN','SUPER_ADMIN']);
        foreach ($services as $s):
            [$icon, $color, $meta] = svc_icon($s['nom_service']);
            $isOwn      = ((int)$s['id'] === $tuServiceId);
            $accessible = $isOwn || $isAdmin || !empty($gardeActive);
      ?>
      <div class="tile<?= $accessible ? '' : ' tile-locked' ?>"
           data-id="<?= (int)$s['id'] ?>"
           data-name="<?= htmlspecialchars($s['nom_service']) ?>"
           style="--tc:<?= $color ?><?= !$accessible ? ';opacity:.45;filter:grayscale(.6);cursor:not-allowed;' : '' ?>"
           <?= !$accessible ? 'title="Non affecté à ce service"' : '' ?>>
        <span class="check"><i class="bi bi-check-lg"></i></span>
        <span class="ic"><i class="bi <?= $icon ?>"></i></span>
        <span class="nm"><?= htmlspecialchars($s['nom_service']) ?></span>
        <span class="meta"><span class="dot"></span><?= $meta ?></span>
        <?php if ($isOwn): ?>
        <span style="position:absolute;top:7px;right:8px;font-size:.6rem;font-weight:800;
                     color:<?= $color ?>;opacity:.7;">MON SERVICE</span>
        <?php endif; ?>
      </div>
      <?php endforeach; ?>
      <div class="empty" id="emptyMsg" style="display:none;">
        <i class="bi bi-search"></i>&nbsp; Aucun service trouvé
      </div>
    </div>

    <!-- Footer -->
    <div class="footer">
      <button type="submit" class="confirm" id="confirmBtn" disabled>
        <i class="bi bi-box-arrow-in-right"></i>
        <span id="confirmTxt">Confirmer et entrer</span>
      </button>
      <a href="<?= BASE_URL ?>logout" class="btn-logout">
        <i class="bi bi-arrow-left-circle"></i> Annuler et se déconnecter
      </a>
    </div>

  </form>
</div>

<script>
/* ── Orbs décoratifs ── */
(function () {
  const palette = [['#60a5fa','10%','8%','360px'],['#a78bfa','82%','10%','300px'],['#5eead4','70%','88%','320px']];
  const wrap = document.getElementById('orbs');
  palette.forEach(function ([c, l, t, s]) {
    const o = document.createElement('div');
    o.className = 'orb';
    o.style.cssText = 'background:' + c + ';left:' + l + ';top:' + t + ';width:' + s + ';height:' + s;
    wrap.appendChild(o);
  });
})();

/* ── Logique de sélection et recherche ── */
(function () {
  const grid       = document.getElementById('grid');
  const searchQ    = document.getElementById('searchQ');
  const confirmBtn = document.getElementById('confirmBtn');
  const confirmTxt = document.getElementById('confirmTxt');
  const hintTxt    = document.getElementById('hintTxt');
  const hiddenId   = document.getElementById('selectedServiceId');
  const emptyMsg   = document.getElementById('emptyMsg');
  let   selected   = null;

  function tiles() { return Array.from(grid.querySelectorAll('.tile')); }

  function pick(id, name) {
    if (selected === id) {
      selected = null;
      hiddenId.value    = '';
      confirmBtn.disabled = true;
      confirmTxt.textContent = 'Confirmer et entrer';
      hintTxt.textContent = 'Sélectionnez un service';
      hintTxt.classList.remove('has');
    } else {
      selected = id;
      hiddenId.value    = id;
      confirmBtn.disabled = false;
      confirmTxt.innerHTML = 'Entrer dans <strong>' + name + '</strong>';
      hintTxt.textContent = '✓ ' + name;
      hintTxt.classList.add('has');
    }
    tiles().forEach(function (t) {
      t.classList.toggle('sel', t.dataset.id === selected);
    });
  }

  grid.addEventListener('click', function (e) {
    const t = e.target.closest('.tile');
    if (t && !t.classList.contains('tile-locked')) pick(t.dataset.id, t.dataset.name);
  });

  grid.addEventListener('dblclick', function (e) {
    const t = e.target.closest('.tile');
    if (t && !t.classList.contains('tile-locked') && !confirmBtn.disabled) {
      pick(t.dataset.id, t.dataset.name);
      document.getElementById('svcForm').submit();
    }
  });

  searchQ.addEventListener('input', function () {
    const term = this.value.trim().toLowerCase();
    let visible = 0;
    tiles().forEach(function (t) {
      const match = t.dataset.name.toLowerCase().includes(term);
      t.classList.toggle('hidden', !match);
      if (match) visible++;
    });
    emptyMsg.style.display = (visible === 0) ? 'block' : 'none';
  });

  searchQ.focus();
})();
</script>

</body>
</html>
