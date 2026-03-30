<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<style>
    .paper-sheet {
        background-color: white;
        width: 21cm;
        min-height: 29.7cm;
        padding: 1.5cm 2cm;
        margin: 20px auto;
        box-shadow: 0 4px 15px rgba(0,0,0,0.15);
        font-family: "Times New Roman", Times, serif;
        color: #000;
        line-height: 1.5;
        position: relative;
    }
    .form-dotted {
        border: none; border-bottom: 1px dotted #000;
        background: transparent; padding: 0 5px; outline: none;
        font-weight: bold; color: #0d6efd;
    }
    .header-table { width: 100%; margin-bottom: 20px; }
    .logo-img { height: 80px; }
    .hosp-info { text-align: right; font-size: 0.85rem; line-height: 1.2; font-weight: bold; }
    .doc-title { text-align: center; margin: 30px 0; }
    .doc-title h2 { font-weight: 900; margin-bottom: 0; font-size: 1.6rem; }
    .doc-title p { font-style: italic; font-size: 1.1rem; }
    .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 20px; }
    .section-title {
        font-weight: bold; text-align: center; background: #f8f9fa;
        padding: 5px; margin: 20px 0 10px;
        border-top: 1px solid #000; border-bottom: 1px solid #000;
    }
    .text-block {
        width: 100%; min-height: 150px; border: 1px solid #eee;
        padding: 10px; margin-bottom: 15px;
        font-family: inherit; font-size: 1.1rem;
    }
    .label-eng { font-style: italic; color: #444; font-weight: normal; font-size: 0.9rem; }

    /* Zone signature canvas */
    #signatureCanvas {
        border: 1px dashed #aaa; background: #fafafa;
        cursor: crosshair; touch-action: none;
        width: 300px; height: 100px; display: block;
    }
    .sig-preview { max-width: 300px; max-height: 100px; }

    @media print {
        .no-print, .action-bar { display: none !important; }
        .paper-sheet { margin: 0; box-shadow: none; width: 100%; padding: 1cm; }
        .form-dotted { color: black; border-bottom: 1px solid #000; }
        .text-block { border: none; }
        body { background: white; }
    }
</style>

<div class="container-fluid bg-light pb-5">
    <!-- BARRE D'ACTIONS -->
    <div class="d-flex justify-content-between align-items-center py-3 px-4 bg-white border-bottom shadow-sm no-print action-bar">
        <a href="<?= BASE_URL ?>patients/dossier/<?= $patient['id'] ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Retour au dossier
        </a>
        <div class="d-flex gap-2">
            <button class="btn btn-primary" onclick="window.print()"><i class="bi bi-printer"></i> Imprimer</button>
            <button type="submit" form="formCRH" class="btn btn-success px-4"><i class="bi bi-save"></i> Enregistrer &amp; Signer</button>
        </div>
    </div>

    <div class="paper-sheet">
        <form id="formCRH" action="<?= BASE_URL ?>formulaire/sauvegarder-crh" method="POST">
            <input type="hidden" name="patient_id"       value="<?= (int)$patient['id'] ?>">
            <input type="hidden" name="hospitalisation_id" value="<?= (int)($hosp['id'] ?? $hosp['hosp_id'] ?? 0) ?>">
            <input type="hidden" name="signature_canvas" id="signatureInput">

            <!-- EN-TÊTE -->
            <table class="header-table">
                <tr>
                    <td>
                        <img src="<?= BASE_URL ?>public/images/logo_ordre_malte.png" class="logo-img" alt="Logo">
                        <div class="fw-bold small mt-1">HÔPITAL SAINT-JEAN DE MALTE</div>
                    </td>
                    <td class="hosp-info">
                        HÔPITAL SAINT-JEAN DE MALTE<br>
                        BP.: 56 NJOMBE - CAMEROUN<br>
                        Tél.: (237) 697 09 29 92<br>
                        233 21 10 22
                    </td>
                </tr>
            </table>

            <div class="doc-title">
                <h2>COMPTE-RENDU D'HOSPITALISATION</h2>
                <p>HOSPITALISATION REPORT</p>
            </div>

            <!-- IDENTITÉ PATIENT -->
            <div class="info-grid">
                <div>
                    Nom : <input type="text" class="form-dotted" style="width:70%;" value="<?= htmlspecialchars($patient['nom']) ?>" readonly>
                    <br><span class="label-eng">Name</span>
                </div>
                <div>
                    Prénom : <input type="text" class="form-dotted" style="width:70%;" value="<?= htmlspecialchars($patient['prenom']) ?>" readonly>
                    <br><span class="label-eng">Surname</span>
                </div>
            </div>

            <div class="info-grid">
                <div>
                    Age : <input type="text" class="form-dotted" style="width:50px;" value="<?= $age ?>"> ans
                </div>
                <div>
                    Date et lieu de naissance :
                    <input type="text" name="date_lieu_naiss" class="form-dotted" style="width:60%;"
                           value="<?= date('d/m/Y', strtotime($patient['date_naissance'])) ?>">
                </div>
            </div>

            <!-- INFOS MÉDICALES -->
            <div class="mt-3">
                Date d'entrée :
                <input type="date" name="date_entree" class="form-dotted" style="width:180px;"
                       value="<?= $hosp['date_admission'] ?? '' ?>"><br>
                Diagnostic d'entrée :
                <input type="text" name="diag_entree" class="form-dotted" style="width:70%;"
                       value="<?= htmlspecialchars($hosp['motif_hospitalisation'] ?? '') ?>"><br>
                Médecin traitant / <span class="label-eng">Attending physician</span> :
                <input type="text" name="medecin_traitant" class="form-dotted" style="width:50%;"
                       value="Dr. <?= htmlspecialchars($_SESSION['user_nom'] ?? '') ?>">
            </div>

            <!-- COMPTE RENDU ÉVOLUTION -->
            <div class="section-title">
                Compte rendu du traitement et de l'évolution /
                <span class="label-eng">Report of treatment and evolution</span>
            </div>
            <textarea name="evolution" class="form-control text-block"
                      placeholder="Décrire le séjour, les examens pratiqués, les soins reçus..."></textarea>

            <div class="mt-3">
                Date de sortie :
                <input type="date" name="date_sortie" class="form-dotted" style="width:180px;"
                       value="<?= $hosp['date_sortie_effective'] ? date('Y-m-d', strtotime($hosp['date_sortie_effective'])) : '' ?>">
            </div>

            <div class="mt-3 fw-bold">
                Diagnostic de sortie / <span class="label-eng">Discharge diagnostic :</span>
            </div>
            <textarea name="diag_sortie" class="form-control text-block" style="min-height:80px;"></textarea>

            <div class="mt-3 fw-bold">
                Traitement prescrit à la sortie / <span class="label-eng">Prescribed treatment at discharge :</span>
            </div>
            <textarea name="traitement_sortie" class="form-control text-block" style="min-height:100px;"></textarea>

            <div class="mt-3">
                Rendez-vous / <span class="label-eng">Appointment :</span>
                <input type="text" name="rendez_vous" class="form-dotted" style="width:60%;">
            </div>

            <!-- FOOTER SIGNATURE -->
            <div class="d-flex justify-content-between align-items-end mt-5">
                <div>
                    Njombé, le/ <span class="label-eng">The</span>
                    <input type="text" name="date_signature" class="form-dotted"
                           value="<?= date('d/m/Y') ?>" style="width:120px;">
                </div>

                <div class="text-center no-print" style="width:320px;">
                    <strong>Signature électronique</strong>
                    <?php if (!empty($signature) && !empty($signature['signature_data'])): ?>
                        <!-- Signature enregistrée dans le profil -->
                        <div class="mt-2 mb-1">
                            <img src="<?= $signature['signature_data'] ?>" class="sig-preview border rounded" alt="Signature">
                        </div>
                        <small class="text-muted">Signature du profil chargée automatiquement</small>
                        <input type="hidden" name="signature_canvas" id="signatureInput"
                               value="<?= htmlspecialchars($signature['signature_data']) ?>">
                    <?php else: ?>
                        <div class="mt-2">
                            <canvas id="signatureCanvas" width="300" height="100"></canvas>
                        </div>
                        <div class="d-flex gap-2 justify-content-center mt-1">
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="clearSignature()">Effacer</button>
                        </div>
                        <small class="text-muted">Dessinez votre signature ci-dessus</small>
                    <?php endif; ?>
                    <div class="fw-bold mt-2">Dr. <?= htmlspecialchars($_SESSION['user_nom'] ?? '') ?></div>
                </div>

                <!-- Zone imprimée : affiche signature en image -->
                <div class="text-center d-none d-print-block" style="width:320px;">
                    <strong>Signature</strong>
                    <?php if (!empty($signature) && !empty($signature['signature_data'])): ?>
                        <div><img src="<?= $signature['signature_data'] ?>" style="max-width:280px;max-height:80px;" alt=""></div>
                    <?php else: ?>
                        <div style="height:60px;"></div>
                    <?php endif; ?>
                    <div class="fw-bold">Dr. <?= htmlspecialchars($_SESSION['user_nom'] ?? '') ?></div>
                </div>
            </div>

        </form>
    </div>
</div>

<script>
// === Pad de signature (canvas) ===
(function () {
    const canvas = document.getElementById('signatureCanvas');
    if (!canvas) return; // Si signature profil utilisée, pas de canvas

    const ctx = canvas.getContext('2d');
    let drawing = false;
    let lastX = 0, lastY = 0;

    function getPos(e) {
        const rect = canvas.getBoundingClientRect();
        const src = e.touches ? e.touches[0] : e;
        return { x: src.clientX - rect.left, y: src.clientY - rect.top };
    }

    canvas.addEventListener('mousedown',  e => { drawing = true; const p = getPos(e); lastX = p.x; lastY = p.y; });
    canvas.addEventListener('touchstart', e => { e.preventDefault(); drawing = true; const p = getPos(e); lastX = p.x; lastY = p.y; }, { passive: false });

    function draw(e) {
        if (!drawing) return;
        if (e.type === 'touchmove') e.preventDefault();
        const p = getPos(e);
        ctx.beginPath();
        ctx.moveTo(lastX, lastY);
        ctx.lineTo(p.x, p.y);
        ctx.strokeStyle = '#000'; ctx.lineWidth = 2; ctx.lineCap = 'round';
        ctx.stroke();
        lastX = p.x; lastY = p.y;
    }

    canvas.addEventListener('mousemove',  draw);
    canvas.addEventListener('touchmove',  draw, { passive: false });
    canvas.addEventListener('mouseup',   () => drawing = false);
    canvas.addEventListener('touchend',  () => drawing = false);

    window.clearSignature = function () {
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        document.getElementById('signatureInput').value = '';
    };

    // Avant soumission du formulaire, capturer le canvas en base64
    document.getElementById('formCRH').addEventListener('submit', function () {
        document.getElementById('signatureInput').value = canvas.toDataURL('image/png');
    });
})();
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
