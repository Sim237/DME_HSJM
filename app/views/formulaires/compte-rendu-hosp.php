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
 require_once __DIR__ . '/../layouts/header.php'; ?>

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

    /* Zone signature */
    #signatureCanvas {
        border: 1px dashed #aaa; background: #fafafa;
        cursor: crosshair; touch-action: none;
        width: 300px; height: 100px; display: block;
    }
    /* Bloc signature superposée sur cachet */
    .sig-block {
        position: relative;
        width: 100%;
        height: 200px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .sig-block .sig-cachet {
        position: absolute;
        top: 0; left: 0;
        width: 100%; height: 100%;
        object-fit: contain;
        opacity: 1;
    }
    .sig-block .sig-signature {
        position: absolute;
        bottom: 10px; left: 50%;
        transform: translateX(-50%);
        width: 75%;
        max-height: 100px;
        object-fit: contain;
        z-index: 2;
    }

    @media print {
        @page { size: A4 portrait; margin: 0; }

        .no-print, .action-bar { display: none !important; }
        body, html, .container-fluid {
            background: white !important;
            padding: 0 !important;
            margin: 0 !important;
        }

        /* Feuille = toute la page A4, padding interne pour les marges */
        .paper-sheet {
            margin: 0 !important;
            box-shadow: none !important;
            width: 100% !important;
            padding: 1cm 1.5cm !important;
            min-height: unset !important;
        }

        /* En-tête plus compact */
        .header-table { margin-bottom: 8px !important; }
        .logo-img { height: 55px !important; }
        .doc-title { margin: 10px 0 !important; }
        .doc-title h2 { font-size: 1.2rem !important; }
        .doc-title p  { font-size: 0.9rem !important; }

        /* Grille infos patient */
        .info-grid { gap: 6px !important; margin-bottom: 6px !important; }

        /* Titres de section */
        .section-title { margin: 8px 0 4px !important; padding: 2px !important; }

        /* Textareas : hauteur min réduite à l'impression */
        .text-block {
            min-height: 60px !important;
            border: none !important;
            margin-bottom: 4px !important;
            font-size: 0.85rem !important;
            padding: 4px !important;
        }

        /* Espacements généraux */
        .mt-3 { margin-top: 6px !important; }
        .mt-5 { margin-top: 10px !important; }

        /* Champs */
        .form-dotted { color: black; border-bottom: 1px solid #000; }

        /* Bloc signature print */
        #zonePrint { display: block !important; }
        #zonePrint .sig-block { height: 150px !important; }

        /* Forcer le footer sur la même page */
        .footer-sig { page-break-inside: avoid; }
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
            <div class="d-flex justify-content-between align-items-end mt-5 footer-sig">
                <div>
                    Njombé, le/ <span class="label-eng">The</span>
                    <input type="text" name="date_signature" class="form-dotted"
                           value="<?= date('d/m/Y') ?>" style="width:120px;">
                </div>

                <!-- ── Zone Signature (écran) ── -->
                <div class="text-center no-print" style="width:320px;" id="zoneSignatureEcran">
                    <strong>Signature électronique</strong>

                    <!-- État : non signé -->
                    <div id="stateUnsigned" class="mt-3">
                        <div style="border:2px dashed #cbd5e1;border-radius:12px;padding:24px 16px;background:#f8fafc;">
                            <i class="bi bi-pen-fill fs-2 text-muted opacity-50 d-block mb-2"></i>
                            <small class="text-muted d-block mb-3">Le document n'est pas encore signé</small>
                            <?php if (!empty($signatureSrc)): ?>
                                <button type="button" class="btn btn-primary rounded-pill px-4 fw-bold shadow-sm"
                                        onclick="apposerSignature()">
                                    <i class="bi bi-pen-fill me-2"></i>Signer le document
                                </button>
                            <?php else: ?>
                                <a href="<?= BASE_URL ?>profil/signature" class="btn btn-outline-primary rounded-pill px-4 fw-semibold">
                                    <i class="bi bi-plus-circle me-2"></i>Configurer ma signature
                                </a>
                                <div class="mt-2"><small class="text-danger">Aucune signature enregistrée dans votre profil.</small></div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- État : signé -->
                    <div id="stateSigned" class="mt-2" style="display:none;">
                        <div style="border:2px solid #22c55e;border-radius:12px;padding:14px 12px 10px;background:#f0fdf4;position:relative;">
                            <span style="position:absolute;top:8px;right:10px;font-size:.7rem;background:#dcfce7;color:#16a34a;padding:2px 10px;border-radius:20px;font-weight:700;z-index:5">
                                <i class="bi bi-check-circle-fill me-1"></i>Signé
                            </span>
                            <!-- Cachet + Signature superposés -->
                            <div class="sig-block">
                                <?php if (!empty($cachetSrc)): ?>
                                <img id="previewCachet" src="<?= htmlspecialchars($cachetSrc) ?>"
                                     class="sig-cachet" alt="Cachet">
                                <?php endif; ?>
                                <?php if (!empty($signatureSrc)): ?>
                                <img id="previewSignature" src="<?= htmlspecialchars($signatureSrc) ?>"
                                     class="sig-signature" alt="Signature">
                                <?php endif; ?>
                            </div>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-secondary mt-2 rounded-pill"
                                onclick="annulerSignature()">
                            <i class="bi bi-x-circle me-1"></i>Annuler la signature
                        </button>
                    </div>

                    <input type="hidden" name="signature_canvas" id="signatureInput" value="">
                    <input type="hidden" name="cachet_data"     id="cachetInput"    value="">
                    <div class="fw-bold mt-2">Dr. <?= htmlspecialchars($medecinNom) ?></div>
                    <?php if ($medecinSpec): ?><div style="font-size:.75rem;color:#64748b"><?= htmlspecialchars($medecinSpec) ?></div><?php endif; ?>
                    <?php if ($medecinOrdre): ?><div style="font-size:.72rem;color:#94a3b8">N° <?= htmlspecialchars($medecinOrdre) ?></div><?php endif; ?>
                </div>

                <!-- Zone imprimée : signature superposée sur cachet -->
                <div class="d-none d-print-block" style="width:280px;text-align:center;" id="zonePrint">
                    <div class="sig-block" style="height:150px;">
                        <?php if (!empty($cachetSrc)): ?>
                        <img id="printCachet" src="" class="sig-cachet" alt="">
                        <?php endif; ?>
                        <?php if (!empty($signatureSrc)): ?>
                        <img id="printSignature" src="" class="sig-signature" alt="">
                        <?php endif; ?>
                    </div>
                    <div class="fw-bold" style="margin-top:4px;">Dr. <?= htmlspecialchars($medecinNom) ?></div>
                    <?php if ($medecinSpec): ?><div style="font-size:.75rem"><?= htmlspecialchars($medecinSpec) ?></div><?php endif; ?>
                </div>
            </div>

        </form>
    </div>
</div>

<script>
// ── Données signature/cachet ──────────────────────────────────────────────────
// SIG_SRC / CACHET_SRC : URL pour l'affichage (preview)
const SIG_SRC    = <?= json_encode($signatureSrc ?? '') ?>;
const CACHET_SRC = <?= json_encode($cachetSrc    ?? '') ?>;
// SIG_B64 / CACHET_B64 : base64 pour la soumission du formulaire
const SIG_B64    = <?= json_encode($signatureB64 ?? '') ?>;
const CACHET_B64 = <?= json_encode($cachetB64   ?? '') ?>;

// ── Apposer la signature ─────────────────────────────────────────────────────
function apposerSignature() {
    const hasSig = SIG_B64 || SIG_SRC;
    if (!hasSig) return;

    // Champs cachés : toujours en base64 pour que le contrôleur puisse traiter
    document.getElementById('signatureInput').value = SIG_B64 || SIG_SRC;
    if (CACHET_B64 || CACHET_SRC) document.getElementById('cachetInput').value = CACHET_B64 || CACHET_SRC;

    // Prévisualisation : URL (ou base64 en fallback)
    const ps = document.getElementById('previewSignature');
    const pc = document.getElementById('previewCachet');
    if (ps) ps.src = SIG_SRC || SIG_B64;
    if (pc) pc.src = CACHET_SRC || CACHET_B64;

    // Impression
    const prs = document.getElementById('printSignature');
    const prc = document.getElementById('printCachet');
    if (prs) prs.src = SIG_SRC || SIG_B64;
    if (prc) prc.src = CACHET_SRC || CACHET_B64;

    // Basculer l'affichage
    document.getElementById('stateUnsigned').style.display = 'none';
    document.getElementById('stateSigned').style.display   = 'block';
}

// ── Annuler la signature ──────────────────────────────────────────────────────
function annulerSignature() {
    document.getElementById('signatureInput').value = '';
    document.getElementById('cachetInput').value    = '';
    document.getElementById('stateUnsigned').style.display = 'block';
    document.getElementById('stateSigned').style.display   = 'none';
}

// ── Auto-signer avant soumission du formulaire ────────────────────────────────
document.getElementById('formCRH').addEventListener('submit', function (e) {
    // Si la signature n'est pas encore apposée et qu'elle existe dans le profil,
    // on l'applique automatiquement avant l'envoi
    const sigInput = document.getElementById('signatureInput');
    if (SIG_SRC && (!sigInput.value || sigInput.value.length < 10)) {
        apposerSignature();
    }
});
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
