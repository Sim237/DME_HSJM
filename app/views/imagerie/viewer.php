<?php
// Sécurisation des variables pour éviter les Warnings
$examen['nom'] = $examen['nom'] ?? 'Patient';
$examen['prenom'] = $examen['prenom'] ?? 'Inconnu';
$examen['type_examen'] = $examen['type_examen'] ?? $examen['type_imagerie'] ?? 'Examen';
$examen['partie_corps'] = $examen['partie_corps'] ?? $examen['partie_code'] ?? 'Zone non spécifiée';
$examen['date_examen'] = $examen['date_examen'] ?? date('Y-m-d H:i:s');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Visualiseur DICOM - <?= htmlspecialchars($examen['nom'] . ' ' . $examen['prenom']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">

    <!-- Bibliothèques Cornerstone -->
    <script src="https://cdn.jsdelivr.net/npm/cornerstone-core@2.6.1/dist/cornerstone.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/cornerstone-math@0.1.9/dist/cornerstoneMath.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/cornerstone-tools@6.0.10/dist/cornerstoneTools.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/dicom-parser@1.8.21/dist/dicomParser.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/cornerstone-wado-image-loader@4.13.2/dist/cornerstoneWADOImageLoader.bundle.min.js"></script>

    <style>
        body { background: #1a1a1a; color: white; margin: 0; padding: 0; overflow: hidden; }
        .viewer-container { height: 100vh; display: flex; }
        .sidebar-tools { width: 250px; background: #2d2d2d; padding: 1rem; overflow-y: auto; border-right: 1px solid #444; }
        .viewer-main { flex: 1; display: flex; flex-direction: column; }
        .viewer-header { background: #333; padding: 0.5rem 1rem; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #444; }
        .viewer-canvas { flex: 1; position: relative; background: #000; cursor: crosshair; }
        .dicom-viewport { width: 100%; height: 100%; }
        .tool-group { margin-bottom: 1.5rem; }
        .tool-group h6 { color: #ffc107; margin-bottom: 0.5rem; font-size: 0.9rem; text-transform: uppercase; }
        .tool-btn { width: 100%; margin-bottom: 0.25rem; text-align: left; }
        .tool-btn.active { background: #0d6efd; color: white; border-color: #0d6efd; }
        .patient-info { background: #333; padding: 1rem; margin-bottom: 1rem; border-radius: 8px; border-left: 4px solid #0d6efd; }
    </style>
</head>
<body>
    <div class="viewer-container">
        <!-- Sidebar Outils -->
        <div class="sidebar-tools">
            <div class="patient-info">
                <h6 class="text-white"><i class="bi bi-person"></i> Patient</h6>
                <div class="fw-bold"><?= htmlspecialchars($examen['nom'] . ' ' . $examen['prenom']) ?></div>
                <div class="small text-muted"><?= htmlspecialchars($examen['type_examen']) ?></div>
                <div class="small text-muted"><?= date('d/m/Y', strtotime($examen['date_examen'])) ?></div>
            </div>

            <div class="tool-group">
                <h6><i class="bi bi-tools"></i> Navigation & Zoom</h6>
                <button class="btn btn-outline-light btn-sm tool-btn active" id="toolPan">
                    <i class="bi bi-arrows-move"></i> Déplacer
                </button>
                <button class="btn btn-outline-light btn-sm tool-btn" id="toolZoom">
                    <i class="bi bi-zoom-in"></i> Zoom
                </button>
                <button class="btn btn-outline-light btn-sm tool-btn" id="toolWwwc">
                    <i class="bi bi-brightness-high"></i> Contraste (L/H)
                </button>
            </div>

            <div class="tool-group">
                <h6><i class="bi bi-rulers"></i> Mesures</h6>
                <button class="btn btn-outline-light btn-sm tool-btn" id="toolLength">
                    <i class="bi bi-rulers"></i> Règle (Mesure)
                </button>
                <button class="btn btn-outline-light btn-sm tool-btn" id="toolAngle">
                    <i class="bi bi-triangle"></i> Angle
                </button>
                <button class="btn btn-outline-light btn-sm tool-btn" id="toolReset">
                    <i class="bi bi-arrow-clockwise"></i> Réinitialiser
                </button>
            </div>

            <div class="tool-group">
                <h6><i class="bi bi-sliders"></i> Fenêtrage manuel</h6>
                <div class="mb-2">
                    <label class="form-label small">Niveau (Center)</label>
                    <input type="range" class="form-range" id="windowCenter" min="-1000" max="1000" value="40">
                </div>
                <div class="mb-2">
                    <label class="form-label small">Largeur (Width)</label>
                    <input type="range" class="form-range" id="windowWidth" min="1" max="2000" value="400">
                </div>
            </div>
        </div>

        <!-- Zone principale -->
        <div class="viewer-main">
            <div class="viewer-header">
                <h6 class="mb-0 text-white">
                    <?= htmlspecialchars($examen['type_examen']) ?> - <?= htmlspecialchars($examen['partie_corps']) ?>
                </h6>
                <div>
                    <button class="btn btn-outline-light btn-sm me-2" onclick="toggleInterpretation()">
                        <i class="bi bi-file-earmark-text"></i> Interpréter
                    </button>
                    <a href="<?= BASE_URL ?>imagerie" class="btn btn-danger btn-sm">
                        <i class="bi bi-x-lg"></i> Fermer
                    </a>
                </div>
            </div>

            <div class="viewer-canvas">
                <div id="dicomViewport" class="dicom-viewport"></div>

                <!-- Overlay Informations -->
                <div style="position: absolute; bottom: 20px; left: 20px; background: rgba(0,0,0,0.5); padding: 10px; border-radius: 5px;">
                    <div class="small">
                        <div id="imageInfo">Initialisation du visualiseur...</div>
                        <div id="activeToolInfo">Outil : Déplacer</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Panel Interprétation -->
    <div id="interpretationPanel" style="display: none; position: fixed; bottom: 0; left: 250px; right: 0; background: #333; padding: 1.5rem; border-top: 2px solid #0d6efd; z-index: 100;">
        <h6 class="text-primary mb-3">Compte-rendu Radiologique</h6>
        <div class="row">
            <div class="col-md-6">
                <label class="small">Observations</label>
                <textarea class="form-control bg-dark text-white border-secondary" id="interpretation" rows="4"></textarea>
            </div>
            <div class="col-md-6">
                <label class="small">Conclusion</label>
                <textarea class="form-control bg-dark text-white border-secondary" id="conclusion" rows="4"></textarea>
            </div>
        </div>
        <div class="mt-3 text-end">
            <button class="btn btn-secondary btn-sm" onclick="toggleInterpretation()">Annuler</button>
            <button class="btn btn-primary btn-sm" onclick="saveInterpretation()">Enregistrer l'interprétation</button>
        </div>
    </div>

    <script>
    // FIX : Définition globale pour le JavaScript
    const BASE_URL = '<?= BASE_URL ?>';
    const EXAMEN_ID = '<?= $examen['id'] ?>';

    class DicomViewer {
        constructor() {
            this.element = document.getElementById('dicomViewport');
            this.init();
        }

        async init() {
            // 1. Initialiser Cornerstone
            cornerstone.enable(this.element);

            // 2. Configurer les outils
            cornerstoneTools.external.cornerstone = cornerstone;
            cornerstoneTools.external.cornerstoneMath = cornerstoneMath;
            cornerstoneTools.init();

            // 3. Charger le loader WADO (pour fichiers web)
            cornerstoneWADOImageLoader.external.cornerstone = cornerstone;
            cornerstoneWADOImageLoader.external.dicomParser = dicomParser;

            // 4. Charger l'image
            await this.loadExamen();

            // 5. Configurer les outils
            this.setupTools();
            this.setupEventListeners();
        }

        async loadExamen() {
            try {
                // On tente de récupérer le chemin réel du fichier DICOM via l'API
                const response = await fetch(`${BASE_URL}imagerie/dicom-data/${EXAMEN_ID}`);
                const data = await response.json();

                // Si on a un fichier réel dans la base
                if (data.imageIds && data.imageIds.length > 0) {
                    const imageId = data.imageIds[0];
                    const image = await cornerstone.loadImage(imageId);
                    cornerstone.displayImage(this.element, image);
                    this.updateImageInfo(image);
                } else {
                    this.loadDemoImage();
                }
            } catch (e) {
                console.warn("Fichier DICOM introuvable, chargement mode démo.");
                this.loadDemoImage();
            }
        }

        loadDemoImage() {
            // Création d'une mire de test si le fichier n'est pas trouvé
            const canvas = document.createElement('canvas');
            canvas.width = 512; canvas.height = 512;
            const ctx = canvas.getContext('2d');
            ctx.fillStyle = "black"; ctx.fillRect(0,0,512,512);
            ctx.strokeStyle = "white"; ctx.strokeRect(50,50,412,412);
            ctx.fillStyle = "white"; ctx.font = "20px Arial";
            ctx.fillText("MODE VISUALISATION DEMO", 120, 250);
            ctx.fillText("Fichier DICOM non chargé physiquement", 80, 280);

            const image = {
                imageId: 'demo', minPixelValue: 0, maxPixelValue: 255, slope: 1, intercept: 0,
                windowCenter: 128, windowWidth: 256, rows: 512, columns: 512, height: 512, width: 512,
                color: false, columnPixelSpacing: 1, rowPixelSpacing: 1,
                getPixelData: () => new Uint8Array(ctx.getImageData(0,0,512,512).data),
                sizeInBytes: 512*512*4
            };
            cornerstone.displayImage(this.element, image);
            document.getElementById('imageInfo').textContent = "Image de substitution (Preview)";
        }

        setupTools() {
            // Ajouter les outils Cornerstone
            cornerstoneTools.addTool(cornerstoneTools.PanTool);
            cornerstoneTools.addTool(cornerstoneTools.ZoomTool);
            cornerstoneTools.addTool(cornerstoneTools.WwwcTool);
            cornerstoneTools.addTool(cornerstoneTools.LengthTool);
            cornerstoneTools.addTool(cornerstoneTools.AngleTool);

            // Activer le Pan (Déplacement) par défaut
            cornerstoneTools.setToolActive('Pan', { mouseButtonMask: 1 });
        }

        setActiveTool(toolName, label) {
            // Désactiver tous
            const tools = ['Pan', 'Zoom', 'Wwwc', 'Length', 'Angle'];
            tools.forEach(t => cornerstoneTools.setToolPassive(t));

            // Activer le nouveau
            cornerstoneTools.setToolActive(toolName, { mouseButtonMask: 1 });

            // Update UI
            document.querySelectorAll('.tool-btn').forEach(b => b.classList.remove('active'));
            document.getElementById('tool' + toolName).classList.add('active');
            document.getElementById('activeToolInfo').textContent = 'Outil : ' + label;
        }

        setupEventListeners() {
            document.getElementById('toolPan').onclick = () => this.setActiveTool('Pan', 'Déplacer');
            document.getElementById('toolZoom').onclick = () => this.setActiveTool('Zoom', 'Zoom');
            document.getElementById('toolWwwc').onclick = () => this.setActiveTool('Wwwc', 'Contraste');
            document.getElementById('toolLength').onclick = () => this.setActiveTool('Length', 'Mesure');
            document.getElementById('toolAngle').onclick = () => this.setActiveTool('Angle', 'Angle');
            document.getElementById('toolReset').onclick = () => cornerstone.reset(this.element);

            // Fenêtrage
            document.getElementById('windowCenter').oninput = (e) => this.updateVOI();
            document.getElementById('windowWidth').oninput = (e) => this.updateVOI();
        }

        updateVOI() {
            const viewport = cornerstone.getViewport(this.element);
            viewport.voi.windowCenter = parseInt(document.getElementById('windowCenter').value);
            viewport.voi.windowWidth = parseInt(document.getElementById('windowWidth').value);
            cornerstone.setViewport(this.element, viewport);
        }

        updateImageInfo(image) {
            document.getElementById('imageInfo').textContent = `${image.width} x ${image.height} | Res : ${image.columnPixelSpacing.toFixed(2)} mm`;
        }
    }

    // Fonctions globales
    function toggleInterpretation() {
        const p = document.getElementById('interpretationPanel');
        p.style.display = (p.style.display === 'none') ? 'block' : 'none';
    }

    function saveInterpretation() {
        const formData = new FormData();
        formData.append('imagerie_id', EXAMEN_ID);
        formData.append('interpretation', document.getElementById('interpretation').value);
        formData.append('conclusion', document.getElementById('conclusion').value);

        fetch(`${BASE_URL}imagerie/save-interpretation`, { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                if(data.success) {
                    alert("Interprétation enregistrée avec succès !");
                    toggleInterpretation();
                }
            });
    }

    let viewer;
    document.addEventListener('DOMContentLoaded', () => {
        viewer = new DicomViewer();
    });
    </script>
</body>
</html>