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

/*
 * Partiel réutilisable : pad de signature électronique
 * ──────────────────────────────────────────────────────
 * Variables à définir AVANT d'inclure ce fichier :
 *   $__sp_field    (string)  nom du champ hidden, ex: 'signature_medecin'  [requis]
 *   $__sp_label    (string)  label affiché, ex: 'Médecin traitant'         [requis]
 *   $__sp_subname  (string)  nom affiché sous la signature (texte)         [optionnel]
 *   $__sp_existing (string)  data-URI base64 si signature déjà sauvegardée [optionnel]
 *   $__sp_color    (string)  couleur du trait (#1e293b par défaut)          [optionnel]
 *   $__sp_required (bool)    champ requis ?                                 [optionnel]
 */
$__sp_field    = $__sp_field    ?? 'signature';
$__sp_label    = $__sp_label    ?? 'Signature';
$__sp_subname  = $__sp_subname  ?? '';
$__sp_existing = $__sp_existing ?? ($formData[$__sp_field] ?? '');
$__sp_color    = $__sp_color    ?? '#1e293b';
$__sp_required = $__sp_required ?? false;
$__sp_id       = 'sp_' . preg_replace('/[^a-z0-9]/i', '_', $__sp_field);
?>

<div class="sig-pad-container" id="<?= $__sp_id ?>_wrap">

    <div class="sig-pad-toolbar">
        <span class="sig-pad-label">
            <i class="bi bi-pen-fill"></i>
            <?= htmlspecialchars($__sp_label) ?>
            <?php if ($__sp_required): ?>
                <span style="color:#dc2626;">*</span>
            <?php endif; ?>
        </span>
        <div class="sig-pad-actions">
            <button type="button" class="sig-btn-clear"
                    onclick="spClear('<?= $__sp_id ?>')"
                    title="Effacer la signature">
                <i class="bi bi-trash3"></i> Effacer
            </button>
            <span class="sig-status" id="<?= $__sp_id ?>_status">
                <?= $__sp_existing ? '<i class="bi bi-check-circle-fill" style="color:#059669;"></i> Signature enregistrée' : '<i class="bi bi-pencil"></i> Dessinez votre signature' ?>
            </span>
        </div>
    </div>

    <div class="sig-pad-canvas-wrap">
        <canvas id="<?= $__sp_id ?>_canvas"
                class="sig-pad-canvas"
                data-color="<?= htmlspecialchars($__sp_color) ?>"
                data-field="<?= htmlspecialchars($__sp_id) ?>"
                width="520" height="140">
        </canvas>
        <?php if ($__sp_existing): ?>
        <img id="<?= $__sp_id ?>_preview"
             src="<?= htmlspecialchars($__sp_existing) ?>"
             class="sig-pad-preview"
             alt="Signature">
        <?php else: ?>
        <div class="sig-pad-hint" id="<?= $__sp_id ?>_hint">
            <i class="bi bi-cursor-text" style="font-size:1.4rem;opacity:.3;"></i>
            <span>Signez ici</span>
        </div>
        <?php endif; ?>
    </div>

    <?php if ($__sp_subname): ?>
    <div class="sig-pad-subname"><?= htmlspecialchars($__sp_subname) ?></div>
    <?php endif; ?>

    <input type="hidden"
           name="<?= htmlspecialchars($__sp_field) ?>"
           id="<?= $__sp_id ?>_input"
           value="<?= htmlspecialchars($__sp_existing) ?>">
</div>

<?php /* ── CSS (injecté une seule fois via classe guard) ──────────────── */ ?>
<?php if (!defined('_SP_CSS_LOADED')): define('_SP_CSS_LOADED', true); ?>
<style>
.sig-pad-container {
    border: 1.5px solid #e2e8f0;
    border-radius: 10px;
    overflow: hidden;
    background: #fff;
}
.sig-pad-toolbar {
    display: flex; align-items: center; justify-content: space-between;
    padding: 8px 14px;
    background: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
    gap: 10px;
}
.sig-pad-label {
    font-weight: 700; font-size: .8rem;
    text-transform: uppercase; letter-spacing: .4px;
    color: #475569; display: flex; align-items: center; gap: 6px;
}
.sig-pad-actions {
    display: flex; align-items: center; gap: 12px;
}
.sig-btn-clear {
    display: inline-flex; align-items: center; gap: 4px;
    padding: 4px 12px; border-radius: 6px;
    border: 1.5px solid #fecaca; background: #fff5f5;
    color: #dc2626; font-size: .78rem; font-weight: 600;
    cursor: pointer; transition: .15s;
}
.sig-btn-clear:hover { background: #fee2e2; }
.sig-status {
    font-size: .76rem; color: #64748b;
    display: flex; align-items: center; gap: 4px;
}
.sig-pad-canvas-wrap {
    position: relative; height: 140px; cursor: crosshair;
    background: #fafeff;
}
.sig-pad-canvas {
    position: absolute; top: 0; left: 0;
    width: 100%; height: 100%;
    touch-action: none;
}
.sig-pad-preview {
    position: absolute; top: 0; left: 0;
    width: 100%; height: 100%;
    object-fit: contain; pointer-events: none;
}
.sig-pad-hint {
    position: absolute; top: 50%; left: 50%;
    transform: translate(-50%, -50%);
    display: flex; flex-direction: column; align-items: center;
    gap: 4px; color: #94a3b8; font-size: .8rem;
    pointer-events: none;
}
.sig-pad-subname {
    text-align: center;
    padding: 7px 12px;
    font-size: .82rem; font-weight: 600; color: #475569;
    border-top: 1px dashed #e2e8f0;
    background: #f8fafc;
}
/* Indique si la zone est active */
.sig-pad-canvas-wrap:hover .sig-pad-canvas {
    background: #f0fdfa;
}
</style>
<?php endif; ?>

<?php /* ── JS (injecté une seule fois) ─────────────────────────────────── */ ?>
<?php if (!defined('_SP_JS_LOADED')): define('_SP_JS_LOADED', true); ?>
<script>
(function() {
    const pads = {}; // registre des pads actifs

    function spInit(id) {
        if (pads[id]) return;
        const canvas = document.getElementById(id + '_canvas');
        if (!canvas) return;
        const ctx    = canvas.getContext('2d');
        const input  = document.getElementById(id + '_input');
        const hint   = document.getElementById(id + '_hint');
        const status = document.getElementById(id + '_status');
        const color  = canvas.dataset.color || '#1e293b';
        const preview= canvas.parentElement.querySelector('.sig-pad-preview');

        let drawing  = false;
        let hasMark  = !!input.value;

        // Si une signature existante est chargée, masquer le canvas
        if (hasMark && preview) {
            canvas.style.opacity = '0';
        }

        function resize() {
            const rect = canvas.getBoundingClientRect();
            // Préserver le contenu si déjà tracé
            const tmp = hasMark && !preview ? ctx.getImageData(0,0,canvas.width,canvas.height) : null;
            canvas.width  = rect.width  || 520;
            canvas.height = rect.height || 140;
            if (tmp) ctx.putImageData(tmp, 0, 0);
        }
        resize();

        function getPos(e) {
            const r = canvas.getBoundingClientRect();
            const src = e.touches ? e.touches[0] : e;
            return { x: src.clientX - r.left, y: src.clientY - r.top };
        }

        function startDraw(e) {
            // Si signature prévisualisée → l'effacer pour recommencer
            if (preview && canvas.style.opacity === '0') {
                canvas.style.opacity = '1';
                preview.style.display = 'none';
                ctx.clearRect(0,0,canvas.width,canvas.height);
                hasMark = false;
            }
            if (hint) hint.style.display = 'none';
            drawing = true;
            ctx.beginPath();
            const p = getPos(e);
            ctx.moveTo(p.x, p.y);
            e.preventDefault();
        }
        function draw(e) {
            if (!drawing) return;
            ctx.strokeStyle = color;
            ctx.lineWidth   = 2.2;
            ctx.lineCap     = 'round';
            ctx.lineJoin    = 'round';
            const p = getPos(e);
            ctx.lineTo(p.x, p.y);
            ctx.stroke();
            ctx.beginPath();
            ctx.moveTo(p.x, p.y);
            hasMark = true;
            e.preventDefault();
        }
        function endDraw(e) {
            if (!drawing) return;
            drawing = false;
            saveData();
            e.preventDefault();
        }

        function saveData() {
            if (!hasMark) return;
            input.value = canvas.toDataURL('image/png');
            if (status) status.innerHTML = '<i class="bi bi-check-circle-fill" style="color:#059669;"></i> Signature enregistrée';
            // Déclencher un event input pour que le formulaire détecte le changement
            input.dispatchEvent(new Event('input', {bubbles: true}));
        }

        canvas.addEventListener('mousedown',  startDraw);
        canvas.addEventListener('mousemove',  draw);
        canvas.addEventListener('mouseup',    endDraw);
        canvas.addEventListener('mouseleave', function(e){ if(drawing){ drawing=false; saveData(); }});
        canvas.addEventListener('touchstart', startDraw, {passive:false});
        canvas.addEventListener('touchmove',  draw,      {passive:false});
        canvas.addEventListener('touchend',   endDraw,   {passive:false});

        pads[id] = { ctx, canvas, input, hint, status, hasMark: ()=>hasMark,
                     clear: function(){
                         ctx.clearRect(0,0,canvas.width,canvas.height);
                         hasMark = false;
                         input.value = '';
                         if (hint) hint.style.display = '';
                         if (status) status.innerHTML = '<i class="bi bi-pencil"></i> Dessinez votre signature';
                         if (preview) { preview.style.display = 'none'; canvas.style.opacity = '1'; }
                         input.dispatchEvent(new Event('input', {bubbles: true}));
                     }
                   };
    }

    // Exposer globalement
    window.spClear = function(id) {
        if (!pads[id]) spInit(id);
        if (pads[id]) pads[id].clear();
    };

    // Init au chargement
    function initAll() {
        document.querySelectorAll('.sig-pad-canvas').forEach(c => {
            spInit(c.dataset.field || c.id.replace('_canvas',''));
        });
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initAll);
    } else {
        initAll();
    }
})();
</script>
<?php endif; ?>
