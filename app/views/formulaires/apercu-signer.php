<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Aperçu & Signature — <?= htmlspecialchars($soumission['titre']) ?></title>
<link rel="stylesheet" href="<?= BASE_URL ?>public/css/bootstrap-icons.min.css">
<style>
*, *::before, *::after { box-sizing: border-box; }
html, body {
    margin: 0; padding: 0; height: 100%;
    font-family: 'Segoe UI', system-ui, sans-serif;
    background: #f0f4f8;
    display: flex; flex-direction: column;
    overflow: hidden;
}

/* ── TOPBAR ─────────────────────────────────────────────── */
.ap-topbar {
    background: linear-gradient(135deg, #0f172a 0%, #1e3a5f 100%);
    color: white;
    padding: 12px 20px;
    display: flex; align-items: center; gap: 14px;
    flex-shrink: 0;
    box-shadow: 0 2px 12px rgba(0,0,0,.3);
    z-index: 100;
}
.ap-back {
    background: rgba(255,255,255,.12); border: 1.5px solid rgba(255,255,255,.25);
    color: white; border-radius: 8px; padding: 6px 14px;
    font-size: .82rem; font-weight: 700; cursor: pointer;
    display: inline-flex; align-items: center; gap: 5px;
    text-decoration: none; transition: background .15s;
}
.ap-back:hover { background: rgba(255,255,255,.22); color: white; }
.ap-info { flex: 1; min-width: 0; }
.ap-info .titre { font-size: 1rem; font-weight: 800; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.ap-info .sub   { font-size: .75rem; opacity: .72; margin-top: 2px; }
.ap-status-badge {
    padding: 5px 14px; border-radius: 20px;
    font-size: .75rem; font-weight: 800; white-space: nowrap;
    flex-shrink: 0;
}
.st-soumis { background: #dbeafe; color: #1d4ed8; }
.st-vu     { background: #fef9c3; color: #92400e; }
.st-signe  { background: #dcfce7; color: #15803d; }
.st-refuse { background: #fee2e2; color: #dc2626; }

/* ── DOCUMENT FRAME ─────────────────────────────────────── */
.ap-frame-wrap {
    flex: 1;
    overflow: hidden;
    background: #d1d5db;
}
.ap-frame-wrap iframe {
    width: 100%; height: 100%;
    border: none; display: block;
}
.ap-no-data {
    display: flex; flex-direction: column; align-items: center;
    justify-content: center; height: 100%;
    color: #94a3b8; text-align: center;
}
.ap-no-data i { font-size: 4rem; opacity: .3; margin-bottom: 12px; }

/* ── PANNEAU SIGNATURE ──────────────────────────────────── */
.ap-sign-panel {
    background: white;
    border-top: 2px solid #e2e8f0;
    padding: 14px 20px;
    display: flex; align-items: center; gap: 20px;
    flex-shrink: 0;
    box-shadow: 0 -4px 16px rgba(0,0,0,.07);
    flex-wrap: wrap;
}
.ap-sign-label {
    font-size: .78rem; font-weight: 700; color: #475569; margin-bottom: 5px;
}
.ap-canvas-wrap { position: relative; display: inline-block; }
#apSigCanvas {
    display: block;
    border: 1.5px solid #cbd5e1; border-radius: 10px;
    cursor: crosshair; touch-action: none;
    background: #f8fafc;
}
#apSigCanvas.signed { border-color: #0d9488; background: #f0fdfa; }
.ap-clear-btn {
    position: absolute; top: 4px; right: 4px;
    background: rgba(255,255,255,.9); border: 1px solid #e2e8f0;
    border-radius: 6px; padding: 2px 8px;
    font-size: .68rem; color: #64748b; cursor: pointer;
    font-weight: 600;
}
.ap-clear-btn:hover { background: #fee2e2; color: #dc2626; border-color: #fca5a5; }

.ap-sign-actions { display: flex; flex-direction: column; gap: 8px; min-width: 150px; }

.btn-ap {
    display: inline-flex; align-items: center; justify-content: center; gap: 7px;
    padding: 10px 20px; border-radius: 10px;
    font-size: .85rem; font-weight: 800; cursor: pointer;
    border: none; transition: .15s; width: 100%;
}
.btn-ap-sign   { background: #0d9488; color: white; }
.btn-ap-sign:hover   { background: #0f766e; }
.btn-ap-sign:disabled { background: #94a3b8; cursor: not-allowed; }
.btn-ap-refuse { background: #f1f5f9; color: #64748b; border: 1.5px solid #e2e8f0; }
.btn-ap-refuse:hover { background: #fee2e2; color: #dc2626; border-color: #fca5a5; }
.btn-ap-print  { background: #f8fafc; color: #475569; border: 1.5px solid #e2e8f0; }
.btn-ap-print:hover  { background: #e0f2fe; color: #0369a1; }

.ap-sign-hint {
    font-size: .7rem; color: #94a3b8; margin-top: 4px; text-align: center;
}

/* ── OVERLAY SUCCÈS ─────────────────────────────────────── */
.ap-overlay {
    position: fixed; inset: 0; z-index: 9999;
    background: rgba(15,23,42,.6);
    display: none; align-items: center; justify-content: center;
}
.ap-overlay.show { display: flex; animation: fadeIn .25s ease; }
@keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
.ap-overlay-box {
    background: white; border-radius: 18px;
    padding: 40px 50px; text-align: center;
    box-shadow: 0 20px 60px rgba(0,0,0,.3);
    max-width: 380px;
}
.ap-overlay-box .big-icon { font-size: 3.5rem; display: block; margin-bottom: 14px; }
.ap-overlay-box h3 { margin: 0 0 8px; font-size: 1.2rem; font-weight: 900; color: #1e293b; }
.ap-overlay-box p { margin: 0; font-size: .88rem; color: #64748b; }
</style>
</head>
<body>

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

$statut    = $soumission['statut'];
$isPending = in_array($statut, ['SOUMIS','VU']);
$badgeCls  = match($statut) {
    'SOUMIS' => 'st-soumis', 'VU' => 'st-vu',
    'SIGNE'  => 'st-signe',  'REFUSE' => 'st-refuse', default => 'st-vu'
};
$labStatut = match($statut) {
    'SOUMIS' => 'Nouveau', 'VU' => 'Vu', 'SIGNE' => 'Signé', 'REFUSE' => 'Refusé', default => $statut
};
$medNomComplet = trim(($medecin['nom'] ?? '') . ' ' . ($medecin['prenom'] ?? ''));
$patNom = htmlspecialchars($soumission['pat_nom'] . ' ' . $soumission['pat_prenom']);
$infNom = htmlspecialchars($soumission['inf_nom'] . ' ' . $soumission['inf_prenom']);
$iframeUrl = $formulaire_data_id
    ? BASE_URL . 'formulaire/imprimer/' . $slug . '/' . $formulaire_data_id . '?noprint=1'
    : null;
?>

<!-- Topbar -->
<div class="ap-topbar">
    <a href="javascript:history.back()" class="ap-back">
        <i class="bi bi-arrow-left"></i> Retour
    </a>
    <div class="ap-info">
        <div class="titre"><i class="bi bi-file-earmark-text me-1"></i><?= htmlspecialchars($soumission['titre']) ?></div>
        <div class="sub">
            <i class="bi bi-person-fill me-1"></i><?= $patNom ?>
            &nbsp;·&nbsp;
            <i class="bi bi-nurse me-1"></i>Inf. <?= $infNom ?>
            &nbsp;·&nbsp;
            <i class="bi bi-clock me-1"></i><?= date('d/m/Y à H:i', strtotime($soumission['date_soumission'])) ?>
        </div>
    </div>
    <?php if ($formulaire_data_id && !$isPending): ?>
    <a href="<?= BASE_URL ?>formulaire/imprimer/<?= $slug ?>/<?= $formulaire_data_id ?>"
       target="_blank" class="ap-back" style="background:rgba(255,255,255,.2);">
        <i class="bi bi-printer-fill"></i> Imprimer
    </a>
    <?php endif; ?>
    <span class="ap-status-badge <?= $badgeCls ?>"><?= $labStatut ?></span>
</div>

<!-- Document dans un iframe -->
<div class="ap-frame-wrap">
    <?php if ($iframeUrl): ?>
    <iframe src="<?= htmlspecialchars($iframeUrl) ?>"
            title="Aperçu du formulaire"
            id="apDocFrame"></iframe>
    <?php else: ?>
    <div class="ap-no-data">
        <i class="bi bi-file-earmark-x"></i>
        <div style="font-size:1rem;font-weight:700;color:#475569;margin-bottom:6px;">
            Aucune donnée enregistrée
        </div>
        <div class="small">Le formulaire n'a pas encore été sauvegardé.</div>
    </div>
    <?php endif; ?>
</div>

<!-- Panneau signature (uniquement pour les formulaires en attente) -->
<?php if ($isPending): ?>
<div class="ap-sign-panel">
    <!-- Pad de signature -->
    <div>
        <div class="ap-sign-label">
            <i class="bi bi-pen-fill me-1" style="color:#0d9488;"></i>
            Signature du Dr <?= htmlspecialchars($medNomComplet) ?>
        </div>
        <div class="ap-canvas-wrap">
            <canvas id="apSigCanvas" width="360" height="110"></canvas>
            <button class="ap-clear-btn" onclick="apClear()">Effacer</button>
        </div>
        <div class="ap-sign-hint">Signez dans le cadre ci-dessus avec votre souris ou votre doigt</div>
    </div>

    <!-- Boutons -->
    <div class="ap-sign-actions">
        <button class="btn-ap btn-ap-sign" id="btnSigner" onclick="apSigner()" disabled>
            <i class="bi bi-pen-fill"></i> Signer & Valider
        </button>
        <button class="btn-ap btn-ap-refuse" onclick="apRefuser()">
            <i class="bi bi-x-lg"></i> Refuser
        </button>
        <?php if ($formulaire_data_id): ?>
        <a href="<?= BASE_URL ?>formulaire/imprimer/<?= $slug ?>/<?= $formulaire_data_id ?>"
           target="_blank" class="btn-ap btn-ap-print" style="text-decoration:none;">
            <i class="bi bi-printer"></i> Imprimer
        </a>
        <?php endif; ?>
    </div>
</div>
<?php elseif ($formulaire_data_id): ?>
<!-- Formulaire déjà traité : juste le bouton imprimer -->
<div class="ap-sign-panel" style="justify-content:flex-end;">
    <a href="<?= BASE_URL ?>formulaire/imprimer/<?= $slug ?>/<?= $formulaire_data_id ?>"
       target="_blank" class="btn-ap btn-ap-print" style="text-decoration:none;width:auto;">
        <i class="bi bi-printer"></i> Imprimer le document
    </a>
</div>
<?php endif; ?>

<!-- Overlay confirmation -->
<div class="ap-overlay" id="apOverlay">
    <div class="ap-overlay-box" id="apOverlayBox">
        <span class="big-icon" id="apOverlayIcon">✅</span>
        <h3 id="apOverlayTitle">Formulaire signé</h3>
        <p id="apOverlayMsg">La signature a été apposée sur le document.</p>
    </div>
</div>

<script>
/* ── Données injectées ──────────────────────────────── */
const _FS_ID      = <?= (int)$fs_id ?>;
const _FD_ID      = <?= $formulaire_data_id ?: 0 ?>;
const _SLUG       = <?= json_encode($slug) ?>;
const _BASE_URL   = <?= json_encode(BASE_URL) ?>;

/* ── Canvas signature ───────────────────────────────── */
const canvas  = document.getElementById('apSigCanvas');
const ctx     = canvas.getContext('2d');
let   drawing = false;
let   hasSig  = false;

ctx.strokeStyle = '#1e293b';
ctx.lineWidth   = 2.2;
ctx.lineCap     = 'round';
ctx.lineJoin    = 'round';

function pos(e) {
    const r = canvas.getBoundingClientRect();
    const src = e.touches ? e.touches[0] : e;
    return { x: src.clientX - r.left, y: src.clientY - r.top };
}

canvas.addEventListener('mousedown',  e => { drawing = true; ctx.beginPath(); const p = pos(e); ctx.moveTo(p.x, p.y); });
canvas.addEventListener('mousemove',  e => { if (!drawing) return; const p = pos(e); ctx.lineTo(p.x, p.y); ctx.stroke(); markSigned(); });
canvas.addEventListener('mouseup',    () => { drawing = false; });
canvas.addEventListener('mouseleave', () => { drawing = false; });

canvas.addEventListener('touchstart', e => { e.preventDefault(); drawing = true; ctx.beginPath(); const p = pos(e); ctx.moveTo(p.x, p.y); }, {passive:false});
canvas.addEventListener('touchmove',  e => { e.preventDefault(); if (!drawing) return; const p = pos(e); ctx.lineTo(p.x, p.y); ctx.stroke(); markSigned(); }, {passive:false});
canvas.addEventListener('touchend',   () => { drawing = false; });

function markSigned() {
    if (!hasSig) {
        hasSig = true;
        canvas.classList.add('signed');
        document.getElementById('btnSigner').disabled = false;
    }
}

function apClear() {
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    hasSig = false;
    canvas.classList.remove('signed');
    document.getElementById('btnSigner').disabled = true;
}

/* ── Signer ─────────────────────────────────────────── */
function apSigner() {
    if (!hasSig) { alert('Veuillez signer dans le cadre avant de valider.'); return; }
    const btn = document.getElementById('btnSigner');
    btn.disabled = true;
    btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Enregistrement…';

    const sig = canvas.toDataURL('image/png');

    fetch(_BASE_URL + 'formulaire/action-signer', {
        method : 'POST',
        headers: { 'Content-Type': 'application/json' },
        body   : JSON.stringify({
            id                : _FS_ID,
            action            : 'signer',
            note              : '',
            signature         : sig,
            formulaire_data_id: _FD_ID
        })
    })
    .then(r => r.json())
    .then(d => {
        if (!d.success) { alert(d.message || 'Erreur serveur.'); btn.disabled = false; btn.innerHTML = '<i class="bi bi-pen-fill"></i> Signer & Valider'; return; }

        // Recharger l'iframe pour afficher la signature
        const frame = document.getElementById('apDocFrame');
        if (frame) frame.src = frame.src; // force reload

        showOverlay('✅', 'Formulaire signé !', 'La signature a été apposée sur le document.');
        setTimeout(() => {
            window.location.href = _BASE_URL + 'hospitalisation/formulaires-a-signer';
        }, 2200);
    })
    .catch(() => { alert('Erreur réseau.'); btn.disabled = false; btn.innerHTML = '<i class="bi bi-pen-fill"></i> Signer & Valider'; });
}

/* ── Refuser ─────────────────────────────────────────── */
function apRefuser() {
    const note = prompt('Motif du refus (optionnel) :');
    if (note === null) return; // annulé

    fetch(_BASE_URL + 'formulaire/action-signer', {
        method : 'POST',
        headers: { 'Content-Type': 'application/json' },
        body   : JSON.stringify({ id: _FS_ID, action: 'refuser', note })
    })
    .then(r => r.json())
    .then(d => {
        if (!d.success) { alert(d.message || 'Erreur'); return; }
        showOverlay('❌', 'Formulaire refusé', note ? 'Motif : ' + note : 'Le formulaire a été refusé.');
        setTimeout(() => {
            window.location.href = _BASE_URL + 'hospitalisation/formulaires-a-signer';
        }, 2200);
    })
    .catch(() => alert('Erreur réseau.'));
}

/* ── Overlay ─────────────────────────────────────────── */
function showOverlay(icon, title, msg) {
    document.getElementById('apOverlayIcon').textContent  = icon;
    document.getElementById('apOverlayTitle').textContent = title;
    document.getElementById('apOverlayMsg').textContent   = msg;
    document.getElementById('apOverlay').classList.add('show');
}
</script>

</body>
</html>
