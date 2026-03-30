<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<style>
    .paper-sheet {
        background-color: white; width: 21cm; min-height: 29.7cm;
        padding: 1.5cm 2cm; margin: 20px auto;
        box-shadow: 0 4px 15px rgba(0,0,0,0.15);
        font-family: "Times New Roman", Times, serif; color: #000; line-height: 1.5;
    }
    .header-table { width: 100%; margin-bottom: 20px; }
    .logo-img { height: 80px; }
    .hosp-info { text-align: right; font-size: 0.85rem; line-height: 1.2; font-weight: bold; }
    .doc-title { text-align: center; margin: 30px 0; }
    .doc-title h2 { font-weight: 900; margin-bottom: 0; font-size: 1.6rem; }
    .doc-title p { font-style: italic; font-size: 1.1rem; }
    .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 20px; }
    .section-title {
        font-weight: bold; text-align: center; background: #f8f9fa; padding: 5px;
        margin: 20px 0 10px; border-top: 1px solid #000; border-bottom: 1px solid #000;
    }
    .text-block { width: 100%; min-height: 100px; border: 1px solid #eee; padding: 10px; margin-bottom: 15px; white-space: pre-wrap; }
    .label-eng { font-style: italic; color: #444; font-weight: normal; font-size: 0.9rem; }
    .field-val { font-weight: bold; border-bottom: 1px solid #ccc; display: inline-block; min-width: 200px; padding: 0 5px; }
    @media print {
        .no-print, .action-bar { display: none !important; }
        .paper-sheet { margin: 0; box-shadow: none; width: 100%; padding: 1cm; }
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
            <?php if ($crh['signe']): ?>
                <span class="badge bg-success rounded-pill px-3 py-2 align-self-center">
                    <i class="bi bi-patch-check-fill me-1"></i> Document signé
                </span>
            <?php endif; ?>
            <button class="btn btn-primary" onclick="window.print()"><i class="bi bi-printer"></i> Imprimer</button>
        </div>
    </div>

    <div class="paper-sheet">
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
            <div>Nom : <span class="field-val"><?= htmlspecialchars($patient['nom']) ?></span><br><span class="label-eng">Name</span></div>
            <div>Prénom : <span class="field-val"><?= htmlspecialchars($patient['prenom']) ?></span><br><span class="label-eng">Surname</span></div>
        </div>
        <div class="info-grid">
            <div>Age : <span class="field-val"><?= $age ?> ans</span></div>
            <div>Date de naissance : <span class="field-val"><?= date('d/m/Y', strtotime($patient['date_naissance'])) ?></span></div>
        </div>

        <!-- INFOS MÉDICALES -->
        <div class="mt-3">
            Date d'entrée : <span class="field-val"><?= $crh['date_entree'] ? date('d/m/Y', strtotime($crh['date_entree'])) : '—' ?></span><br>
            Diagnostic d'entrée : <span class="field-val"><?= htmlspecialchars($crh['diag_entree'] ?? '—') ?></span><br>
            Médecin traitant : <span class="field-val">Dr. <?= htmlspecialchars($crh['medecin_nom'] . ' ' . $crh['medecin_prenom']) ?></span>
        </div>

        <!-- ÉVOLUTION -->
        <div class="section-title">
            Compte rendu du traitement et de l'évolution /
            <span class="label-eng">Report of treatment and evolution</span>
        </div>
        <div class="text-block"><?= nl2br(htmlspecialchars($crh['evolution'] ?? '')) ?></div>

        <div class="mt-3">
            Date de sortie : <span class="field-val"><?= $crh['date_sortie'] ? date('d/m/Y', strtotime($crh['date_sortie'])) : '—' ?></span>
        </div>

        <div class="mt-3 fw-bold">Diagnostic de sortie / <span class="label-eng">Discharge diagnostic :</span></div>
        <div class="text-block" style="min-height:60px;"><?= nl2br(htmlspecialchars($crh['diag_sortie'] ?? '')) ?></div>

        <div class="mt-3 fw-bold">Traitement prescrit à la sortie / <span class="label-eng">Prescribed treatment at discharge :</span></div>
        <div class="text-block" style="min-height:80px;"><?= nl2br(htmlspecialchars($crh['traitement_sortie'] ?? '')) ?></div>

        <div class="mt-3">
            Rendez-vous / <span class="label-eng">Appointment :</span>
            <span class="field-val"><?= htmlspecialchars($crh['rendez_vous'] ?? '—') ?></span>
        </div>

        <!-- SIGNATURE -->
        <div class="d-flex justify-content-between align-items-end mt-5">
            <div>
                Njombé, le/ <span class="label-eng">The</span>
                <span class="field-val"><?= htmlspecialchars($crh['date_signature'] ?? date('d/m/Y')) ?></span>
            </div>
            <div class="text-center" style="width:300px;">
                <strong>Signature</strong>
                <?php if (!empty($crh['signature_data'])): ?>
                    <div><img src="<?= $crh['signature_data'] ?>" style="max-width:280px;max-height:80px;" alt="Signature"></div>
                <?php else: ?>
                    <div style="height:60px;border-bottom:1px solid #000;"></div>
                <?php endif; ?>
                <div class="fw-bold mt-1">Dr. <?= htmlspecialchars($crh['medecin_nom'] . ' ' . $crh['medecin_prenom']) ?></div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
