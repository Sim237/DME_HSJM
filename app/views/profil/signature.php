<?php
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../../services/SignatureService.php';

$signatureService = new SignatureService();
$medecin_id = $_SESSION['user_id'];
$signature = $signatureService->getSignature($medecin_id);
?>

<link rel="stylesheet" href="<?= BASE_URL ?>public/css/signature.css">

<div class="container-fluid">
    <div class="row">
        <?php include __DIR__ . '/../layouts/sidebar.php'; ?>
        
        <main class="col-md-10 ms-sm-auto px-md-4">
            <div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2"><i class="fas fa-signature"></i> Ma Signature Électronique</h1>
            </div>

            <div class="row">
                <div class="col-md-6 mb-4">
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-pen-fancy"></i> Signature</h5>
                        </div>
                        <div class="card-body">
                            <ul class="nav nav-tabs mb-3" role="tablist">
                                <li class="nav-item">
                                    <a class="nav-link active" data-bs-toggle="tab" href="#draw">Dessiner</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" data-bs-toggle="tab" href="#upload">Scanner/Upload</a>
                                </li>
                            </ul>
                            
                            <div class="tab-content">
                                <div class="tab-pane fade show active" id="draw">
                                    <canvas id="signatureCanvas" width="500" height="200" style="border: 2px solid #ddd; border-radius: 8px; cursor: crosshair;"></canvas>
                                    <div class="mt-3">
                                        <button type="button" class="btn btn-secondary btn-sm" onclick="clearSignature()">
                                            <i class="fas fa-eraser"></i> Effacer
                                        </button>
                                    </div>
                                </div>
                                
                                <div class="tab-pane fade" id="upload">
                                    <div class="mb-3">
                                        <label class="form-label">Télécharger signature scannée</label>
                                        <input type="file" class="form-control" id="signatureFile" accept="image/*">
                                        <small class="text-muted">L'image sera automatiquement redimensionnée à 400x150px</small>
                                    </div>
                                    <div id="signaturePreview" class="mt-2" style="display:none;">
                                        <img id="previewImg" style="max-width: 100%; border: 1px solid #ddd; padding: 10px;">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mt-3">
                                <button type="button" class="btn btn-primary" onclick="saveSignature()">
                                    <i class="fas fa-save"></i> Enregistrer
                                </button>
                            </div>
                            
                            <?php if ($signature && $signature['signature_image']): ?>
                            <div class="mt-3">
                                <p class="text-muted">Signature actuelle:</p>
                                <img src="<?= $signature['signature_image'] ?>" style="max-width: 100%; border: 1px solid #ddd; padding: 10px;">
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 mb-4">
                    <div class="card">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0"><i class="fas fa-stamp"></i> Cachet</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label">Télécharger cachet</label>
                                <input type="file" class="form-control" id="cachetFile" accept="image/*">
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">N° Ordre</label>
                                <input type="text" class="form-control" id="numeroOrdre" value="<?= htmlspecialchars($signature['numero_ordre'] ?? '') ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Spécialité</label>
                                <input type="text" class="form-control" id="specialite" value="<?= htmlspecialchars($signature['specialite'] ?? '') ?>">
                            </div>
                            
                            <?php if ($signature && $signature['cachet_image']): ?>
                            <div class="mt-3">
                                <img src="<?= $signature['cachet_image'] ?>" style="max-width: 200px; border: 1px solid #ddd; padding: 10px;">
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
const canvas = document.getElementById('signatureCanvas');
const ctx = canvas.getContext('2d');
let isDrawing = false;
let lastX = 0, lastY = 0;
let uploadedSignature = null;

canvas.addEventListener('mousedown', (e) => {
    isDrawing = true;
    [lastX, lastY] = [e.offsetX, e.offsetY];
});

canvas.addEventListener('mousemove', (e) => {
    if (!isDrawing) return;
    ctx.beginPath();
    ctx.moveTo(lastX, lastY);
    ctx.lineTo(e.offsetX, e.offsetY);
    ctx.strokeStyle = '#000';
    ctx.lineWidth = 2;
    ctx.lineCap = 'round';
    ctx.stroke();
    [lastX, lastY] = [e.offsetX, e.offsetY];
});

canvas.addEventListener('mouseup', () => isDrawing = false);
canvas.addEventListener('mouseout', () => isDrawing = false);

function clearSignature() {
    ctx.clearRect(0, 0, canvas.width, canvas.height);
}

// Gestion upload signature
document.getElementById('signatureFile').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(event) {
            const img = new Image();
            img.onload = function() {
                // Redimensionner à 400x150
                const resizeCanvas = document.createElement('canvas');
                resizeCanvas.width = 400;
                resizeCanvas.height = 150;
                const resizeCtx = resizeCanvas.getContext('2d');
                resizeCtx.fillStyle = 'white';
                resizeCtx.fillRect(0, 0, 400, 150);
                resizeCtx.drawImage(img, 0, 0, 400, 150);
                uploadedSignature = resizeCanvas.toDataURL('image/png');
                
                document.getElementById('previewImg').src = uploadedSignature;
                document.getElementById('signaturePreview').style.display = 'block';
            };
            img.src = event.target.result;
        };
        reader.readAsDataURL(file);
    }
});

function saveSignature() {
    const formData = new FormData();
    
    // Utiliser signature uploadée ou dessinée
    const signatureData = uploadedSignature || canvas.toDataURL('image/png');
    formData.append('signature', signatureData);
    formData.append('numero_ordre', document.getElementById('numeroOrdre').value);
    formData.append('specialite', document.getElementById('specialite').value);
    
    const cachetFile = document.getElementById('cachetFile').files[0];
    if (cachetFile) {
        const reader = new FileReader();
        reader.onload = (e) => {
            formData.append('cachet', e.target.result);
            sendData(formData);
        };
        reader.readAsDataURL(cachetFile);
    } else {
        sendData(formData);
    }
}

function sendData(formData) {
    fetch('<?= BASE_URL ?>profil/save-signature', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            Swal.fire('Succès', 'Signature enregistrée', 'success').then(() => location.reload());
        } else {
            Swal.fire('Erreur', data.message, 'error');
        }
    });
}
</script>

<?php include __DIR__ . '/../layouts/footer.php'; ?>
