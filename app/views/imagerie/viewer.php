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

// ── URL de retour selon l'origine ──────────────────────────────────────────
$from    = $_GET['from'] ?? '';
$backUrl = BASE_URL . 'imagerie';

$namedRoutes = [
    'medecin'         => BASE_URL . 'dashboard',
    'urgences'        => BASE_URL . 'urgences',
    'consultation'    => BASE_URL . 'consultation-ext',
    'hospitalisation' => BASE_URL . 'hospitalisation',
];
if (isset($namedRoutes[$from])) {
    $backUrl = $namedRoutes[$from];
} elseif (!empty($from)) {
    $safe = preg_replace('/[^a-zA-Z0-9\/\-_]/', '', $from);
    if ($safe && strlen($safe) < 80) $backUrl = BASE_URL . $safe;
}

// ── Données patient / examen ────────────────────────────────────────────────
$typeExamen  = strtoupper($examen['type_examen']  ?? $examen['type_imagerie'] ?? 'IMAGERIE');
$zoneCorps   = $examen['partie_corps'] ?? $examen['partie_code'] ?? '';
$patientName = strtoupper($examen['nom']) . ' ' . ($examen['prenom'] ?? '');
$dossierId   = $examen['dossier_numero'] ?? ('ID-' . ($examen['patient_id'] ?? '?'));
$sexeLabel   = match(strtoupper($examen['sexe'] ?? '')) {
    'M', 'MASCULIN' => '♂ M', 'F', 'FEMININ' => '♀ F', default => ''
};
$ageLabel = '';
if (!empty($examen['date_naissance'])) {
    $ageLabel = date_diff(date_create($examen['date_naissance']), date_create('now'))->y . ' ans';
}
$dateExamen  = date('d/m/Y', strtotime($examen['date_creation']));
$heureExamen = date('H:i',   strtotime($examen['date_creation']));
?>
<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<script src="<?= BASE_URL ?>public/js/cornerstone/dicomParser.min.js"></script>
<script src="<?= BASE_URL ?>public/js/cornerstone/cornerstone.min.js"></script>
<script src="<?= BASE_URL ?>public/js/cornerstone/cornerstoneWADOImageLoader.bundle.min.js"></script>

<style>
/* ══════════════════════════════════════════════════════════════
   VIEWER DICOM — Barre latérale style sombre
══════════════════════════════════════════════════════════════ */
body { background:#000 !important; overflow:hidden !important; }
.sidebar { display:none !important; }
#wrapper, .main-wrapper { margin:0 !important; padding:0 !important; width:100% !important; }
main    { margin-left:0 !important; padding:0 !important; width:100% !important; overflow:hidden !important; }

.vl {                              /* root flex row */
    display:flex;
    width:100vw;
    height:100vh;
    overflow:hidden;
    background:#000;
    font-family:'Segoe UI', system-ui, sans-serif;
}

/* ════════════════════════════════════════════════
   BARRE LATÉRALE GAUCHE
════════════════════════════════════════════════ */
.vs {
    width: 230px;
    min-width: 230px;
    height: 100vh;
    background: #0d1117;
    border-right: 1px solid #21262d;
    display: flex;
    flex-direction: column;
    overflow-y: auto;
    overflow-x: hidden;
    scrollbar-width: thin;
    scrollbar-color: #21262d transparent;
}
.vs::-webkit-scrollbar { width:4px; }
.vs::-webkit-scrollbar-thumb { background:#21262d; border-radius:2px; }

/* En-tête sidebar */
.vs-head {
    padding: 14px 14px 10px;
    border-bottom: 1px solid #21262d;
    flex-shrink: 0;
}
.vs-logo { height:22px; filter:brightness(2) grayscale(.3); }
.vs-title { font-size:.65rem; font-weight:700; color:#388bfd; text-transform:uppercase;
            letter-spacing:.8px; margin-top:6px; }

/* Carte patient */
.vs-patient {
    margin: 10px 10px 0;
    background: #161b22;
    border: 1px solid #21262d;
    border-left: 3px solid #388bfd;
    border-radius: 8px;
    padding: 10px 12px;
    flex-shrink: 0;
}
.vs-patient-name { font-weight:700; font-size:.82rem; color:#e6edf3; white-space:nowrap;
                   overflow:hidden; text-overflow:ellipsis; }
.vs-patient-meta { font-size:.7rem; color:#8b949e; margin-top:3px; line-height:1.5; }
.vs-exam-badge {
    display:inline-block; margin-top:6px;
    background:#1f3158; color:#79c0ff; border:1px solid #388bfd44;
    border-radius:4px; font-size:.65rem; font-weight:700;
    padding:2px 8px; text-transform:uppercase;
}

/* Séparateur de groupe */
.vs-group-label {
    font-size:.6rem; font-weight:700; color:#8b949e; text-transform:uppercase;
    letter-spacing:.8px; padding:12px 14px 4px;
}

/* Bouton d'outil */
.vsbtn {
    display: flex;
    align-items: center;
    gap: 10px;
    width: calc(100% - 20px);
    margin: 2px 10px;
    padding: 9px 12px;
    background: transparent;
    border: 1px solid transparent;
    border-radius: 8px;
    color: #8b949e;
    font-size: .78rem;
    font-weight: 500;
    cursor: pointer;
    text-align: left;
    transition: all .15s;
    white-space: nowrap;
}
.vsbtn i { font-size: 1rem; flex-shrink:0; width:18px; text-align:center; }
.vsbtn:hover  { background:#161b22; color:#e6edf3; border-color:#21262d; }
.vsbtn.active { background:#1f3158; color:#79c0ff; border-color:#388bfd55; }
.vsbtn.on     { background:#0f2a1a; color:#3fb950; border-color:#3fb95055; }
.vsbtn.danger { background:#2a0e0e; color:#f85149; border-color:#f8514944; margin-top:4px; }
.vsbtn.danger:hover { background:#3d1515; color:#fca5a5; }
.vsbtn.primary { background:#1d4ed8; color:#fff; border-color:#1d4ed8; }
.vsbtn.primary:hover { background:#2563eb; }

/* Spacer poussant les boutons du bas */
.vs-spacer { flex:1; min-height:12px; }

.vs-bottom { padding:10px; border-top:1px solid #21262d; flex-shrink:0; }

/* ── Barre de navigation instances (bas du viewport) ── */
.v-instbar {
    position:absolute; bottom:0; left:0; right:0; z-index:20;
    background:rgba(13,17,23,.88); border-top:1px solid #21262d;
    backdrop-filter:blur(4px);
    display:flex; align-items:center; justify-content:center;
    height:40px; gap:6px; padding:0 12px;
}
.v-instbar .nb {
    width:30px; height:26px; flex-shrink:0;
    background:#21262d; border:1px solid #30363d; border-radius:4px;
    color:#8b949e; display:flex; align-items:center; justify-content:center;
    cursor:pointer; font-size:.75rem; transition:all .15s;
}
.v-instbar .nb:hover:not(:disabled) { background:#2d333b; color:#e6edf3; }
.v-instbar .nb:disabled { opacity:.3; cursor:default; }
.v-instbar .nb.play-btn { background:#1f3158; color:#388bfd; border-color:#388bfd44; width:34px; }
.v-instbar .nb.play-btn:hover { background:#1d4ed8; color:#fff; }
.inst-counter {
    background:#0d1117; border:1px solid #21262d; border-radius:4px;
    color:#e6edf3; font-family:monospace; font-size:.8rem;
    padding:2px 10px; min-width:64px; text-align:center;
}
.v-instbar .v-tool-label { color:#6b7280; font-size:.7rem; white-space:nowrap; margin-left:8px; }

/* ════════════════════════════════════════════════
   ZONE VIEWPORT
════════════════════════════════════════════════ */
.vvp {
    flex: 1 1 0;
    position: relative;
    background: #000;
    overflow: hidden;
    min-width: 0;
    height: 100vh;
}
#dicomViewport {
    position: absolute;
    top: 0; left: 0;
    width: 100%; height: 100%;
}

/* ── Loader ── */
#vLoading {
    position:absolute; inset:0; background:#000; z-index:50;
    display:flex; flex-direction:column; align-items:center;
    justify-content:center; gap:16px;
    transition: opacity .3s;
}
.lds-ring { display:inline-block; position:relative; width:58px; height:58px; }
.lds-ring div {
    box-sizing:border-box; display:block; position:absolute;
    width:46px; height:46px; margin:6px;
    border:4px solid #388bfd; border-radius:50%;
    animation:ldsring 1.1s cubic-bezier(.5,0,.5,1) infinite;
    border-color:#388bfd transparent transparent transparent;
}
.lds-ring div:nth-child(1){ animation-delay:-.45s }
.lds-ring div:nth-child(2){ animation-delay:-.30s }
.lds-ring div:nth-child(3){ animation-delay:-.15s }
@keyframes ldsring{0%{transform:rotate(0)}100%{transform:rotate(360deg)}}

/* ── Overlays 4 coins ── */
.vov {
    position:absolute; pointer-events:none;
    font-family:'Courier New', monospace; font-size:12px; line-height:1.7;
    text-shadow:1px 1px 4px rgba(0,0,0,.95); padding:12px 14px;
}
.vov.tl { top:0; left:0;  color:#e3b341; }
.vov.tr { top:0; right:0; color:#79c0ff; text-align:right; }
.vov.bl { bottom:0; left:0;  color:#56d364; }
.vov.br { bottom:0; right:0; color:#56d364; text-align:right; }

/* ── Tiroir rapport ── */
.vdrawer {
    position:fixed; bottom:0; left:230px; right:0; z-index:400;
    background:rgba(13,17,23,.97); border-top:2px solid #388bfd;
    padding:18px 24px;
    transform:translateY(100%);
    transition:transform .35s cubic-bezier(.4,0,.2,1);
    backdrop-filter:blur(8px);
}
.vdrawer.open { transform:translateY(0); }
.vdrawer label { display:block; color:#8b949e; font-size:.68rem; text-transform:uppercase;
                 font-weight:700; letter-spacing:.5px; margin-bottom:4px; }
.vdrawer .form-control {
    background:#161b22; color:#e6edf3; border-color:#21262d; font-size:.88rem;
}
.vdrawer .form-control:focus {
    background:#1c2128; border-color:#388bfd;
    box-shadow:0 0 0 2px #388bfd33; color:#fff;
}

/* Toast */
@keyframes toastIn {
    0%  { opacity:0; transform:translateY(-10px); }
    15% { opacity:1; transform:translateY(0); }
    80% { opacity:1; }
    100%{ opacity:0; }
}
</style>

<div class="vl">

    <!-- ════════════════════════════════════════
         BARRE LATÉRALE GAUCHE
    ════════════════════════════════════════ -->
    <div class="vs">

        <!-- En-tête -->
        <div class="vs-head">
            <img src="<?= BASE_URL ?>public/images/logo_ordre_malte.png" class="vs-logo" alt="">
            <div class="vs-title">Viewer Diagnostique</div>
        </div>

        <!-- Carte patient -->
        <div class="vs-patient">
            <div class="vs-patient-name"><?= htmlspecialchars($patientName) ?></div>
            <div class="vs-patient-meta">
                <?= htmlspecialchars($dossierId) ?><br>
                <?= $sexeLabel ?><?= ($sexeLabel && $ageLabel) ? ' · ' : '' ?><?= $ageLabel ?>
            </div>
            <span class="vs-exam-badge"><?= htmlspecialchars($typeExamen) ?></span>
            <?php if($zoneCorps): ?>
            <span class="vs-exam-badge" style="background:#2a1f3d;color:#d2a8ff;border-color:#7c3aed44">
                <?= htmlspecialchars($zoneCorps) ?>
            </span>
            <?php endif; ?>
            <div style="margin-top:6px;font-size:.65rem;color:#8b949e">
                <i class="bi bi-calendar3 me-1"></i><?= $dateExamen ?> <?= $heureExamen ?>
            </div>
        </div>

        <!-- ── Groupe : Navigation ── -->
        <div class="vs-group-label"><i class="bi bi-mouse2 me-1"></i>Navigation</div>
        <button class="vsbtn active" id="btn-pan"  onclick="activateTool('pan')">
            <i class="bi bi-hand-index-thumb"></i> Déplacer
        </button>
        <button class="vsbtn" id="btn-zoom" onclick="activateTool('zoom')">
            <i class="bi bi-zoom-in"></i> Zoom
        </button>
        <button class="vsbtn" id="btn-wwwc" onclick="activateTool('wwwc')">
            <i class="bi bi-brightness-alt-high"></i> Contraste / Luminosité
        </button>

        <!-- ── Groupe : Transformations ── -->
        <div class="vs-group-label"><i class="bi bi-arrow-repeat me-1"></i>Transformations</div>
        <button class="vsbtn" onclick="doRotate(-90)">
            <i class="bi bi-arrow-counterclockwise"></i> Rotation ↺ 90°
        </button>
        <button class="vsbtn" onclick="doRotate(90)">
            <i class="bi bi-arrow-clockwise"></i> Rotation ↻ 90°
        </button>
        <button class="vsbtn" id="btn-fliph" onclick="doFlipH()">
            <i class="bi bi-symmetry-vertical"></i> Miroir horizontal
        </button>
        <button class="vsbtn" id="btn-flipv" onclick="doFlipV()">
            <i class="bi bi-symmetry-horizontal"></i> Miroir vertical
        </button>

        <!-- ── Groupe : Affichage ── -->
        <div class="vs-group-label"><i class="bi bi-display me-1"></i>Affichage</div>
        <button class="vsbtn" id="btn-invert" onclick="doInvert()">
            <i class="bi bi-circle-half"></i> Inverser (négatif)
        </button>
        <button class="vsbtn" onclick="autoWindow()">
            <i class="bi bi-magic"></i> Fenêtrage auto (DICOM)
        </button>
        <button class="vsbtn" onclick="doFit()">
            <i class="bi bi-fullscreen"></i> Ajuster à l'écran
        </button>
        <button class="vsbtn" onclick="doReset()">
            <i class="bi bi-arrow-repeat"></i> Réinitialiser tout
        </button>

        <!-- ── Groupe : Fichier ── -->
        <div class="vs-group-label"><i class="bi bi-files me-1"></i>Fichiers DICOM</div>
        <button class="vsbtn" onclick="downloadPng()">
            <i class="bi bi-download"></i> Télécharger PNG
        </button>
        <button class="vsbtn" id="btn-add-file" onclick="openAddFile()" title="Ajouter un ou plusieurs fichiers à cet examen">
            <i class="bi bi-plus-circle"></i> Ajouter fichier(s)
        </button>

        <!-- Compteur instances -->
        <div style="margin:6px 10px 2px;background:#0d1117;border:1px solid #21262d;
                    border-radius:8px;padding:8px 12px;font-size:.75rem;">
            <div style="color:#8b949e;font-size:.65rem;font-weight:700;text-transform:uppercase;
                        letter-spacing:.5px;margin-bottom:4px">
                <i class="bi bi-collection me-1"></i>Instances
            </div>
            <div style="color:#e6edf3;font-family:monospace;font-size:.92rem;font-weight:700">
                <span id="vs-cur">—</span>
                <span style="color:#30363d"> / </span>
                <span id="vs-tot">—</span>
            </div>
            <div style="color:#56d364;font-size:.65rem;margin-top:2px" id="vs-fname">—</div>
        </div>

        <!-- Push bottom -->
        <div class="vs-spacer"></div>

        <!-- Bas de barre : Rapport + Quitter -->
        <div class="vs-bottom">
            <button class="vsbtn primary w-100" onclick="toggleReport()" style="width:100%;margin:0 0 6px">
                <i class="bi bi-file-medical"></i> Rédiger le rapport
            </button>
            <a href="<?= $backUrl ?>" class="vsbtn danger" style="width:100%;margin:0;text-decoration:none;display:flex;align-items:center;gap:10px">
                <i class="bi bi-box-arrow-left"></i> Quitter
            </a>
        </div>

    </div><!-- /.vs -->

    <!-- ════════════════════════════════════════
         ZONE IMAGE
    ════════════════════════════════════════ -->
    <div class="vvp">
        <div id="dicomViewport"></div>

        <!-- Loader -->
        <div id="vLoading">
            <div class="lds-ring"><div></div><div></div><div></div><div></div></div>
            <div style="color:#388bfd;font-size:.82rem;font-family:monospace;letter-spacing:.5px">
                Chargement DICOM…
            </div>
        </div>

        <!-- ── Barre de navigation instances (collée en bas du viewport) ── -->
        <div class="v-instbar" id="instBar">
            <button class="nb" id="nb-first" onclick="goFirst()" disabled title="Première instance">
                <i class="bi bi-skip-backward-fill"></i>
            </button>
            <button class="nb" id="nb-prev" onclick="goPrev()" disabled title="Précédente">
                <i class="bi bi-caret-left-fill"></i>
            </button>
            <div class="inst-counter">
                <span id="inst-cur">1</span> / <span id="inst-tot">1</span>
            </div>
            <button class="nb" id="nb-next" onclick="goNext()" disabled title="Suivante">
                <i class="bi bi-caret-right-fill"></i>
            </button>
            <button class="nb" id="nb-last" onclick="goLast()" disabled title="Dernière instance">
                <i class="bi bi-skip-forward-fill"></i>
            </button>
            <div style="width:1px;height:18px;background:#21262d;margin:0 4px"></div>
            <button class="nb play-btn" id="nb-play" onclick="toggleCine()" title="Lecture ciné">
                <i class="bi bi-play-fill" id="play-icon"></i>
            </button>
            <div class="v-tool-label" id="inst-filename" style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"></div>
        </div>

        <!-- ── Overlays 4 coins ── -->
        <div class="vov tl">
            <?= htmlspecialchars($patientName) ?><br>
            <?= htmlspecialchars($dossierId) ?><br>
            <?= $sexeLabel ?><?= ($sexeLabel && $ageLabel) ? ' · ' . $ageLabel : $ageLabel ?>
        </div>
        <div class="vov tr">
            <?= htmlspecialchars($typeExamen) ?><br>
            <?= $dateExamen ?> — <?= $heureExamen ?><br>
            <?= htmlspecialchars($zoneCorps ?: '—') ?>
        </div>
        <div class="vov bl">
            Zoom : <span id="val-zoom">—</span>x<br>
            Outil : <span id="val-tool">Déplacer</span>
        </div>
        <div class="vov br">
            WW : <span id="val-ww">—</span><br>
            WC : <span id="val-wc">—</span>
        </div>
    </div><!-- /.vvp -->

</div><!-- /.vl -->

<!-- ── Modal Ajout Fichier(s) ── -->
<div class="modal fade" id="addFileModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0" style="background:#161b22;border-radius:14px;overflow:hidden">
            <div class="modal-header border-0 px-4 pt-4 pb-2"
                 style="background:linear-gradient(135deg,#1e3a8a,#1d4ed8)">
                <h5 class="modal-title fw-bold text-white mb-0">
                    <i class="bi bi-plus-circle me-2"></i>Ajouter des fichiers DICOM
                </h5>
                <button class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body px-4 py-3">
                <p class="text-muted small mb-3">
                    Les fichiers ajoutés seront disponibles comme instances supplémentaires de cet examen.
                </p>
                <form id="addFileForm" enctype="multipart/form-data">
                    <input type="hidden" name="imagerie_id" value="<?= (int)$examen['id'] ?>">
                    <input type="hidden" name="mode" value="add">
                    <div class="mb-3">
                        <label class="form-label fw-semibold" style="color:#c9d1d9;font-size:.8rem">
                            Sélectionner les fichiers
                        </label>
                        <input type="file" name="dicom_file[]" id="addFileInput"
                               class="form-control"
                               style="background:#0d1117;color:#e6edf3;border-color:#30363d"
                               accept=".dcm,.dicom,.jpg,.jpeg,.png" multiple required>
                        <div class="mt-1" style="color:#8b949e;font-size:.72rem">
                            .dcm, .dicom, .jpg, .png — Sélection multiple possible
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer border-0 px-4 pb-4">
                <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill"
                        data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-primary fw-bold rounded-pill px-4"
                        onclick="submitAddFile()">
                    <i class="bi bi-cloud-upload me-1"></i>Uploader
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ── Tiroir Rapport ── -->
<div class="vdrawer" id="reportDrawer">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="mb-0 fw-bold" style="color:#388bfd">
            <i class="bi bi-file-medical me-2"></i>COMPTE-RENDU RADIOLOGIQUE
        </h5>
        <button class="btn-close btn-close-white" onclick="toggleReport()"></button>
    </div>
    <div class="row g-3">
        <div class="col-md-7">
            <label>Interprétation / Observations</label>
            <textarea id="interp-text" class="form-control" rows="5"
                placeholder="Décrivez les findings, anomalies, observations radiologiques…"></textarea>
        </div>
        <div class="col-md-5">
            <label>Conclusion diagnostique</label>
            <input type="text" id="concl-text" class="form-control mb-3"
                   placeholder="Ex : Fracture tibia G / Pas d'anomalie / …">
            <button class="btn btn-primary btn-lg w-100 fw-bold rounded-3" onclick="saveAll()">
                <i class="bi bi-send-check-fill me-2"></i>VALIDER ET TRANSMETTRE
            </button>
            <?php if(!empty($examen['interpretation'])): ?>
            <div class="mt-3 p-2 rounded" style="background:#161b22;border:1px solid #21262d;font-size:.76rem;color:#8b949e;">
                <div class="fw-bold mb-1" style="color:#d29922">
                    <i class="bi bi-clock-history me-1"></i>Dernier rapport enregistré
                </div>
                <?= nl2br(htmlspecialchars($examen['interpretation'])) ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
const EXAMEN_ID = <?= (int)$examen['id'] ?>;

// ── Cornerstone init ──────────────────────────────────────────────────────
cornerstoneWADOImageLoader.external.cornerstone = cornerstone;
cornerstoneWADOImageLoader.external.dicomParser  = dicomParser;

const element = document.getElementById('dicomViewport');
cornerstone.enable(element);

// ── État ──────────────────────────────────────────────────────────────────
let currentTool   = 'pan';
let isDragging    = false;
let dragStartX = 0, dragStartY = 0, vpOnDrag = null;
let _flipH = false, _flipV = false, _invert = false, _rotation = 0;

// ── Multi-instance ────────────────────────────────────────────────────────
let _files        = [];   // [{id, fichier, nom_original, ordre}]
let _currentIndex = 0;
let _cineTimer    = null;
const CINE_FPS    = 4;   // images/seconde pour la lecture ciné

const TOOL_LABELS = { pan:'Déplacer', zoom:'Zoom', wwwc:'Contraste / Luminosité' };
const CURSORS     = { pan:'grab',     zoom:'ns-resize', wwwc:'crosshair' };

// ── Interactions souris ───────────────────────────────────────────────────
element.addEventListener('mousedown', e => {
    if (e.button !== 0) return;
    isDragging = true;
    dragStartX = e.clientX; dragStartY = e.clientY;
    try { vpOnDrag = JSON.parse(JSON.stringify(cornerstone.getViewport(element))); } catch(_) {}
    if (currentTool === 'pan') element.style.cursor = 'grabbing';
    e.preventDefault();
});

window.addEventListener('mousemove', e => {
    if (!isDragging || !vpOnDrag) return;
    const dx = e.clientX - dragStartX, dy = e.clientY - dragStartY;
    try {
        const vp = cornerstone.getViewport(element);
        if      (currentTool === 'pan')  { vp.translation.x = vpOnDrag.translation.x + dx / vpOnDrag.scale; vp.translation.y = vpOnDrag.translation.y + dy / vpOnDrag.scale; }
        else if (currentTool === 'zoom') { vp.scale = Math.max(0.02, Math.min(vpOnDrag.scale * (1 - dy / 300), 30)); }
        else if (currentTool === 'wwwc') { vp.voi.windowWidth = Math.max(1, vpOnDrag.voi.windowWidth + dx * 5); vp.voi.windowCenter = vpOnDrag.voi.windowCenter - dy * 5; }
        cornerstone.setViewport(element, vp);
    } catch(_) {}
});

window.addEventListener('mouseup', () => {
    isDragging = false;
    element.style.cursor = CURSORS[currentTool] || 'default';
});

// Zoom molette
element.addEventListener('wheel', e => {
    e.preventDefault();
    try {
        const vp = cornerstone.getViewport(element);
        vp.scale = Math.max(0.02, Math.min(vp.scale * (e.deltaY < 0 ? 1.12 : 0.88), 30));
        cornerstone.setViewport(element, vp);
    } catch(_) {}
}, { passive: false });

// Double-clic → reset
element.addEventListener('dblclick', doReset);

// ── Overlays ─────────────────────────────────────────────────────────────
element.addEventListener('cornerstoneimagerendered', e => {
    const vp = e.detail.viewport;
    setText('val-zoom', vp.scale.toFixed(2));
    setText('val-ww',   Math.round(vp.voi.windowWidth));
    setText('val-wc',   Math.round(vp.voi.windowCenter));
});

window.addEventListener('resize', () => {
    try { cornerstone.resize(element, true); cornerstone.fitToWindow(element); } catch(_) {}
});

// ── Chargement DICOM — Multi-instances ───────────────────────────────────

/** Récupère la liste des fichiers via l'API JSON */
async function fetchFileList() {
    const resp = await fetch('<?= BASE_URL ?>imagerie/fichiers/' + EXAMEN_ID);
    if (!resp.ok) throw new Error('HTTP ' + resp.status);
    const data = await resp.json();
    if (data.success && data.files && data.files.length) {
        _files = data.files;
    } else {
        // Rétro-compatibilité : fichier unique via fetchDicom
        _files = [{ id: 0, fichier: 'legacy', nom_original: 'dicom.dcm', ordre: 1 }];
    }
}

/** Construit l'imageId Cornerstone pour un index donné */
function imageUrlFor(index) {
    const f = _files[index];
    if (!f) return null;
    return f.id === 0
        ? 'wadouri:<?= BASE_URL ?>imagerie/fetchDicom/' + EXAMEN_ID
        : 'wadouri:<?= BASE_URL ?>imagerie/fichier/' + f.id;
}

/** Met à jour les boutons de navigation + compteurs */
function updateNavUI() {
    const n = _files.length, i = _currentIndex;
    const fname = _files[i]?.nom_original || _files[i]?.fichier || '';

    // Afficher la barre seulement si > 1 instance
    const bar = document.getElementById('instBar');
    if (bar) bar.style.display = n > 1 ? 'flex' : 'none';

    document.getElementById('nb-first').disabled = i === 0;
    document.getElementById('nb-prev').disabled  = i === 0;
    document.getElementById('nb-next').disabled  = i >= n - 1;
    document.getElementById('nb-last').disabled  = i >= n - 1;
    document.getElementById('nb-play').disabled  = n <= 1;

    setText('inst-cur',      i + 1);
    setText('inst-tot',      n);
    setText('inst-filename', fname);
    setText('vs-cur',        i + 1);
    setText('vs-tot',        n);
    setText('vs-fname',      fname);
}

/** Charge et affiche l'instance à l'index donné */
async function loadFile(index) {
    if (index < 0 || index >= _files.length) return;
    _currentIndex = index;
    updateNavUI();

    const imageId = imageUrlFor(index);
    if (!imageId) return;
    console.log('[DICOM] Chargement instance', index + 1, ':', imageId);

    try {
        const image = await cornerstone.loadAndCacheImage(imageId);
        cornerstone.displayImage(element, image);
        applyBestWindow(image);
        requestAnimationFrame(() => {
            cornerstone.resize(element, true);
            cornerstone.fitToWindow(element);
        });
    } catch (err) {
        console.error('[DICOM] loadFile(' + index + '):', err);
        showToast('❌ Instance ' + (index + 1) + ' : ' + (err.message || err), true);
    }
}

// ── Navigation ─────────────────────────────────────────────────────────────
function goFirst() { if (_cineTimer) toggleCine(); loadFile(0); }
function goPrev()  { if (_cineTimer) toggleCine(); loadFile(Math.max(0, _currentIndex - 1)); }
function goNext()  { if (_cineTimer) toggleCine(); loadFile(Math.min(_files.length - 1, _currentIndex + 1)); }
function goLast()  { if (_cineTimer) toggleCine(); loadFile(_files.length - 1); }

// Raccourcis clavier ← → Espace
document.addEventListener('keydown', e => {
    if (e.target.tagName === 'TEXTAREA' || e.target.tagName === 'INPUT') return;
    if (e.key === 'ArrowLeft')  { e.preventDefault(); goPrev(); }
    if (e.key === 'ArrowRight') { e.preventDefault(); goNext(); }
    if (e.key === ' ')          { e.preventDefault(); toggleCine(); }
});

// ── Lecture ciné ──────────────────────────────────────────────────────────
function toggleCine() {
    if (_cineTimer) {
        clearInterval(_cineTimer);
        _cineTimer = null;
        document.getElementById('play-icon').className = 'bi bi-play-fill';
        document.getElementById('nb-play').classList.remove('on');
    } else {
        if (_files.length <= 1) return;
        document.getElementById('play-icon').className = 'bi bi-pause-fill';
        document.getElementById('nb-play').classList.add('on');
        _cineTimer = setInterval(() => {
            loadFile((_currentIndex + 1) % _files.length);
        }, Math.round(1000 / CINE_FPS));
    }
}

// ── Chargement initial ────────────────────────────────────────────────────
async function initViewer() {
    await new Promise(r => requestAnimationFrame(r));
    await new Promise(r => requestAnimationFrame(r)); // 2 frames pour layout stable
    cornerstone.resize(element, true);

    // 1. Récupérer la liste des fichiers
    try {
        await fetchFileList();
    } catch (err) {
        console.warn('[DICOM] fetchFileList échoué — mode legacy:', err);
        _files = [{ id: 0, fichier: 'legacy', nom_original: 'dicom.dcm', ordre: 1 }];
    }
    updateNavUI();

    // 2. Charger la première instance
    const imageId = imageUrlFor(0);
    console.log('[DICOM] Première instance:', imageId);

    try {
        const image = await cornerstone.loadAndCacheImage(imageId);
        cornerstone.displayImage(element, image);
        applyBestWindow(image);
        requestAnimationFrame(() => {
            cornerstone.resize(element, true);
            cornerstone.fitToWindow(element);
        });

        // Masquer le loader
        const ldr = document.getElementById('vLoading');
        if (ldr) { ldr.style.opacity = '0'; setTimeout(() => ldr.style.display = 'none', 300); }

        activateTool('pan');
        setTimeout(saveAutoThumbnail, 2500);

        // Pré-remplir rapport existant
        <?php if(!empty($examen['interpretation'])): ?>
        document.getElementById('interp-text').value = <?= json_encode($examen['interpretation']) ?>;
        <?php endif; ?>
        <?php if(!empty($examen['conclusion'])): ?>
        document.getElementById('concl-text').value = <?= json_encode($examen['conclusion']) ?>;
        <?php endif; ?>

    } catch (err) {
        console.error('[DICOM]', err);
        showError('Impossible de charger le fichier DICOM.<br><small style="opacity:.7;font-family:monospace">'
            + (err.message || String(err)) + '</small>');
    }
}

// ── Ajout de fichier(s) depuis le viewer ──────────────────────────────────
function openAddFile() {
    // Réinitialiser le formulaire
    document.getElementById('addFileInput').value = '';
    bootstrap.Modal.getOrCreateInstance(document.getElementById('addFileModal')).show();
}

function submitAddFile() {
    const input = document.getElementById('addFileInput');
    if (!input.files || input.files.length === 0) {
        showToast('⚠ Sélectionnez au moins un fichier.', true);
        return;
    }
    const fd  = new FormData(document.getElementById('addFileForm'));
    const btn = document.querySelector('#addFileModal .btn-primary');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Envoi…';

    fetch('<?= BASE_URL ?>imagerie/upload', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(d => {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-cloud-upload me-1"></i>Uploader';
            if (d.success) {
                bootstrap.Modal.getInstance(document.getElementById('addFileModal'))?.hide();
                const n = d.nb_files || 1;
                showToast('✅ ' + n + ' fichier' + (n > 1 ? 's' : '') + ' ajouté' + (n > 1 ? 's' : '') + '. Liste mise à jour.');
                // Recharger la liste et mettre à jour les boutons
                setTimeout(async () => {
                    const prevCount = _files.length;
                    try {
                        await fetchFileList();
                        updateNavUI();
                        if (_files.length > prevCount) {
                            showToast('📂 ' + _files.length + ' instance(s) disponible(s) au total.');
                        }
                    } catch(e) { console.warn('[DICOM] Rechargement liste:', e); }
                }, 600);
            } else {
                showToast('❌ ' + (d.message || 'Erreur lors de l\'upload.'), true);
            }
        })
        .catch(() => {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-cloud-upload me-1"></i>Uploader';
            showToast('❌ Erreur réseau lors de l\'upload.', true);
        });
}

// ── Fenêtrage ─────────────────────────────────────────────────────────────
function applyBestWindow(image) {
    try {
        const vp  = cornerstone.getViewport(element);
        const min = image.minPixelValue  || 0;
        const max = image.maxPixelValue  || 255;
        const rng = max - min || 256;
        vp.voi.windowWidth  = (image.windowWidth  > 0) ? image.windowWidth  : rng;
        vp.voi.windowCenter = (image.windowWidth  > 0) ? image.windowCenter : (min + rng / 2);
        vp.invert = false;
        cornerstone.setViewport(element, vp);
        cornerstone.updateImage(element);
    } catch(_) {}
}

function autoWindow() {
    try {
        const img = cornerstone.getImage(element);
        if (img) { applyBestWindow(img); requestAnimationFrame(() => cornerstone.fitToWindow(element)); }
    } catch(_) {}
}

// ── Transformations ───────────────────────────────────────────────────────
function doRotate(deg) {
    try { _rotation = ((_rotation + deg) % 360 + 360) % 360; const vp = cornerstone.getViewport(element); vp.rotation = _rotation; cornerstone.setViewport(element, vp); } catch(_) {}
}
function doFlipH() {
    try { _flipH = !_flipH; const vp = cornerstone.getViewport(element); vp.hflip = _flipH; cornerstone.setViewport(element, vp); } catch(_) {}
    document.getElementById('btn-fliph').classList.toggle('on', _flipH);
}
function doFlipV() {
    try { _flipV = !_flipV; const vp = cornerstone.getViewport(element); vp.vflip = _flipV; cornerstone.setViewport(element, vp); } catch(_) {}
    document.getElementById('btn-flipv').classList.toggle('on', _flipV);
}
function doInvert() {
    try { _invert = !_invert; const vp = cornerstone.getViewport(element); vp.invert = _invert; cornerstone.setViewport(element, vp); } catch(_) {}
    document.getElementById('btn-invert').classList.toggle('on', _invert);
}
function doFit() {
    try { cornerstone.resize(element, true); cornerstone.fitToWindow(element); } catch(_) {}
}
function doReset() {
    _rotation = 0; _flipH = false; _flipV = false; _invert = false;
    try {
        const vp = cornerstone.getViewport(element);
        vp.rotation = 0; vp.hflip = false; vp.vflip = false; vp.invert = false; vp.translation = {x:0,y:0};
        cornerstone.setViewport(element, vp);
        const img = cornerstone.getImage(element);
        if (img) applyBestWindow(img);
        requestAnimationFrame(() => cornerstone.fitToWindow(element));
    } catch(_) {}
    ['btn-fliph','btn-flipv','btn-invert'].forEach(id => document.getElementById(id)?.classList.remove('on'));
}

// ── Outil actif ────────────────────────────────────────────────────────────
function activateTool(name) {
    currentTool = name;
    element.style.cursor = CURSORS[name] || 'default';
    document.querySelectorAll('#btn-pan, #btn-zoom, #btn-wwwc').forEach(b => b.classList.remove('active'));
    document.getElementById('btn-' + name)?.classList.add('active');
    setText('val-tool', TOOL_LABELS[name] || name);
}

// ── Téléchargement PNG ────────────────────────────────────────────────────
function downloadPng() {
    const canvas = element.querySelector('canvas');
    if (!canvas) { showToast('❌ Aucune image chargée.', true); return; }
    const a = document.createElement('a');
    a.download = 'dicom_' + EXAMEN_ID + '_<?= date('Ymd') ?>.png';
    a.href = canvas.toDataURL('image/png');
    a.click();
}

// ── Miniature auto ────────────────────────────────────────────────────────
function saveAutoThumbnail() {
    const canvas = element.querySelector('canvas');
    if (!canvas) return;
    const fd = new FormData();
    fd.append('imagerie_id', EXAMEN_ID);
    fd.append('image_data', canvas.toDataURL('image/jpeg', 0.8));
    fetch('<?= BASE_URL ?>imagerie/saveThumbnail', { method:'POST', body:fd }).catch(()=>{});
}

// ── Rapport ────────────────────────────────────────────────────────────────
function toggleReport() {
    document.getElementById('reportDrawer').classList.toggle('open');
}
function saveAll() {
    const interp = document.getElementById('interp-text').value.trim();
    const concl  = document.getElementById('concl-text').value.trim();
    if (!interp) { document.getElementById('interp-text').style.borderColor = '#f85149'; document.getElementById('interp-text').focus(); return; }
    document.getElementById('interp-text').style.borderColor = '';
    const fd = new FormData();
    fd.append('imagerie_id',   EXAMEN_ID);
    fd.append('interpretation', interp);
    fd.append('conclusion',    concl);
    fetch('<?= BASE_URL ?>imagerie/save-interpretation', { method:'POST', body:fd })
        .then(r => r.json())
        .then(d => {
            if (d.success) { showToast('✅ Rapport transmis au dossier patient.'); setTimeout(() => window.location.href = '<?= $backUrl ?>', 1800); }
            else showToast('❌ ' + (d.message || 'Erreur.'), true);
        })
        .catch(() => showToast('❌ Erreur réseau.', true));
}

// ── Erreur ────────────────────────────────────────────────────────────────
function showError(html) {
    const ldr = document.getElementById('vLoading');
    if (ldr) ldr.innerHTML = `
        <div style="color:#f85149;font-size:2.5rem"><i class="bi bi-exclamation-triangle-fill"></i></div>
        <div style="color:#e6edf3;text-align:center;max-width:400px;font-size:.88rem;line-height:1.6">${html}</div>
        <a href="<?= $backUrl ?>" class="btn btn-outline-light btn-sm rounded-pill mt-3" style="font-size:.8rem">
            <i class="bi bi-arrow-left me-1"></i>Retour
        </a>`;
}

// ── Toast ─────────────────────────────────────────────────────────────────
function showToast(msg, err = false) {
    const t = document.createElement('div');
    t.style.cssText = 'position:fixed;top:16px;right:16px;z-index:9999;padding:11px 18px;border-radius:8px;font-size:.84rem;font-weight:600;box-shadow:0 4px 20px rgba(0,0,0,.6);animation:toastIn 2.2s ease forwards;'
        + (err ? 'background:#7f1d1d;color:#fca5a5;border:1px solid #991b1b;' : 'background:#0f2a1a;color:#86efac;border:1px solid #166534;');
    t.innerHTML = msg;
    document.body.appendChild(t);
    setTimeout(() => t.remove(), 2400);
}

function setText(id, v) { const e = document.getElementById(id); if(e) e.textContent = v; }

// ── Lancement ─────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', initViewer);
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
