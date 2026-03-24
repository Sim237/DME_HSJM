<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<!-- LIBRAIRIES CORNERSTONE (MÉDICAL STANDARDS) -->
<script src="https://cdn.jsdelivr.net/npm/cornerstone-core@2.6.1/dist/cornerstone.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/dicom-parser@1.8.21/dist/dicomParser.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/cornerstone-wado-image-loader@4.13.2/dist/cornerstoneWADOImageLoader.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/cornerstone-math@0.1.9/dist/cornerstoneMath.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/cornerstone-tools@6.0.10/dist/cornerstoneTools.min.js"></script>

<style>
    body { background: #000; color: #fff; overflow: hidden; font-family: 'Segoe UI', sans-serif; }
    .sidebar { display: none !important; }
    main { margin-left: 0 !important; width: 100% !important; padding: 0 !important; }

    .viewer-layout { display: flex; height: 100vh; }

    /* Panneau d'outils latéral */
    .tools-panel { width: 260px; background: #1a1a1a; border-right: 1px solid #333; padding: 20px; display: flex; flex-direction: column; }
    .tool-btn {
        background: #2a2a2a; border: 1px solid #444; color: #ccc;
        padding: 12px; border-radius: 10px; margin-bottom: 8px;
        width: 100%; text-align: left; transition: 0.2s;
    }
    .tool-btn:hover, .tool-btn.active { background: #0d6efd; color: #fff; border-color: #0d6efd; }
    .tool-btn i { margin-right: 10px; }

    /* Zone d'image */
    .viewport-area { flex-grow: 1; position: relative; background: #000; }
    #dicomViewport { width: 100%; height: 100%; }

    /* Overlays */
    .overlay { position: absolute; padding: 15px; pointer-events: none; color: #00ff41; font-family: monospace; font-size: 13px; }
    .top-left { top: 0; left: 0; }
    .bottom-right { bottom: 0; right: 0; text-align: right; }

    /* Panel Rapport */
    .report-drawer {
        position: fixed; bottom: 0; left: 260px; right: 0;
        background: rgba(26,26,26,0.95); border-top: 2px solid #0d6efd;
        padding: 20px; transform: translateY(100%); transition: 0.4s; z-index: 1000;
    }
    .report-drawer.open { transform: translateY(0); }
</style>

<div class="viewer-layout">
    <div class="tools-panel">
        <div class="mb-4">
            <img src="<?= BASE_URL ?>public/images/logo_ordre_malte.png" style="height: 30px; filter: grayscale(1) brightness(2);">
            <h6 class="mt-3 fw-bold text-primary text-uppercase small">Viewer Diagnostic</h6>
        </div>

        <div class="patient-info mb-4 p-3 rounded bg-dark border-start border-3 border-primary">
            <div class="small text-muted text-uppercase fw-bold">Patient</div>
            <div class="fw-bold"><?= strtoupper($examen['nom']) ?> <?= $examen['prenom'] ?></div>
            <div class="small opacity-50"><?= $examen['type_imagerie'] ?> - <?= $examen['partie_code'] ?></div>
        </div>

        <div class="flex-grow-1">
            <button class="tool-btn active" id="btn-pan" onclick="activateTool('Pan')"><i class="bi bi-arrows-move"></i> Déplacer</button>
            <button class="tool-btn" id="btn-zoom" onclick="activateTool('Zoom')"><i class="bi bi-zoom-in"></i> Zoom</button>
            <button class="tool-btn" id="btn-wwwc" onclick="activateTool('Wwwc')"><i class="bi bi-brightness-high"></i> Contraste / Lulminosité</button>
            <button class="tool-btn" id="btn-length" onclick="activateTool('Length')"><i class="bi bi-ruler"></i> Mesure Linéaire</button>
            <button class="tool-btn" onclick="cornerstone.reset(document.getElementById('dicomViewport'))"><i class="bi bi-arrow-counterclockwise"></i> Réinitialiser</button>
        </div>

        <div class="mt-auto">
            <button class="btn btn-primary w-100 mb-2 rounded-pill fw-bold" onclick="document.getElementById('reportDrawer').classList.toggle('open')">
                <i class="bi bi-pencil-square"></i> RÉDIGER RAPPORT
            </button>
            <a href="<?= BASE_URL ?>imagerie" class="btn btn-outline-danger w-100 rounded-pill btn-sm">FERMER</a>
        </div>
    </div>

    <div class="viewport-area">
        <div id="dicomViewport"></div>

        <!-- Overlays de données -->
        <div class="overlay top-left">
            DME HOSPITAL - Unité Imagerie<br>
            Examen: <?= $examen['type_imagerie'] ?><br>
            Zone: <?= $examen['partie_code'] ?>
        </div>
        <div class="overlay bottom-right">
            L/H: <span id="val-lh">--</span> / <span id="val-lc">--</span><br>
            Zoom: <span id="val-zoom">1.0</span>x<br>
            Date: <?= date('d/m/Y H:i', strtotime($examen['date_creation'])) ?>
        </div>
    </div>
</div>

<!-- TIROIR DE RAPPORT -->
<div class="report-drawer" id="reportDrawer">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="mb-0 text-primary fw-bold">COMPTE-RENDU RADIOLOGIQUE</h5>
        <button class="btn-close btn-close-white" onclick="document.getElementById('reportDrawer').classList.remove('open')"></button>
    </div>
    <div class="row">
        <div class="col-md-7">
            <textarea id="interp-text" class="form-control bg-dark text-white border-secondary" rows="5" placeholder="Interprétation détaillée..."></textarea>
        </div>
        <div class="col-md-5">
            <input type="text" id="concl-text" class="form-control bg-dark text-white border-secondary mb-3" placeholder="Conclusion diagnostique finale">
            <button class="btn btn-primary btn-lg w-100 shadow" onclick="saveAll()">VALIDER ET TRANSMETTRE AU DOSSIER</button>
        </div>
    </div>
</div>

<script>
    const EXAMEN_ID = '<?= $examen['id'] ?>';
    const FETCH_URL = '<?= BASE_URL ?>imagerie/fetchDicom/' + EXAMEN_ID;

    // Initialisation Cornerstone
    cornerstoneWADOImageLoader.external.cornerstone = cornerstone;
    cornerstoneWADOImageLoader.external.dicomParser = dicomParser;

    const element = document.getElementById('dicomViewport');
    cornerstone.enable(element);

    // Chargement de l'image
    async function initViewer() {
        try {
            const imageId = "wadouri:" + FETCH_URL;
            const image = await cornerstone.loadAndCacheImage(imageId);
            cornerstone.displayImage(element, image);

            // Initialisation des outils
            cornerstoneTools.init();
            activateTool('Pan');

            // Mise à jour des overlays lors des changements
            element.addEventListener('cornerstoneimagerendered', function(e) {
                const viewport = cornerstone.getViewport(element);
                document.getElementById('val-lh').innerText = Math.round(viewport.voi.windowWidth);
                document.getElementById('val-lc').innerText = Math.round(viewport.voi.windowCenter);
                document.getElementById('val-zoom').innerText = viewport.scale.toFixed(2);
            });

            // GÉNÉRER LA MINIATURE AUTOMATIQUEMENT SI ELLE N'EXISTE PAS
            <?php if(!$examen['fichier_preview']): ?>
                setTimeout(saveAutoThumbnail, 2000);
            <?php endif; ?>

        } catch(err) {
            console.error(err);
            alert("Erreur lors de l'accès au fichier DICOM.");
        }
    }

    function activateTool(name) {
        const tools = ['Pan', 'Zoom', 'Wwwc', 'Length'];
        tools.forEach(t => {
            cornerstoneTools.addTool(cornerstoneTools[t + "Tool"]);
            cornerstoneTools.setToolPassive(t);
        });
        cornerstoneTools.setToolActive(name, { mouseButtonMask: 1 });

        document.querySelectorAll('.tool-btn').forEach(b => b.classList.remove('active'));
        document.getElementById('btn-' + name.toLowerCase()).classList.add('active');
    }

    function saveAutoThumbnail() {
        const canvas = element.querySelector('canvas');
        if(!canvas) return;
        const dataURL = canvas.toDataURL('image/jpeg', 0.8);
        const fd = new FormData();
        fd.append('imagerie_id', EXAMEN_ID);
        fd.append('image_data', dataURL);
        fetch('<?= BASE_URL ?>imagerie/saveThumbnail', { method: 'POST', body: fd });
    }

    function saveAll() {
        const fd = new FormData();
        fd.append('imagerie_id', EXAMEN_ID);
        fd.append('interpretation', document.getElementById('interp-text').value);
        fd.append('conclusion', document.getElementById('concl-text').value);

        fetch('<?= BASE_URL ?>imagerie/save-interpretation', { method: 'POST', body: fd })
        .then(res => res.json())
        .then(data => {
            if(data.success) {
                alert("Rapport transmis au dossier patient.");
                window.location.href = '<?= BASE_URL ?>imagerie';
            }
        });
    }

    document.addEventListener('DOMContentLoaded', initViewer);
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>