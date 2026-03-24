<?php
// On empêche le rendu du header standard pour l'impression
define('HEADER_RENDERED', true);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Ordonnance_<?= $prescription['id'] ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">

    <style>
        /* ============ STYLE ÉCRAN ============ */
        body { background: #f0f2f5; padding-top: 30px; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .print-wrapper {
            max-width: 850px; margin: 0 auto; background: #fff;
            padding: 50px; border-radius: 8px; box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }

        /* ============ STYLE IMPRESSION (FIX 1 PAGE) ============ */
        @media print {
            @page { size: A4; margin: 15mm; }
            body { background: #fff !important; padding: 0 !important; }
            .no-print { display: none !important; }

            /* On force l'ordonnance à ignorer les marges et hauteurs du site */
            html, body, .print-wrapper { height: auto !important; min-height: auto !important; overflow: visible !important; }
            .print-wrapper { width: 100% !important; max-width: none !important; margin: 0 !important; padding: 0 !important; box-shadow: none !important; border: none !important; }

            /* Cacher tout ce qui n'est pas l'ordonnance si injecté par erreur */
            body > *:not(.print-wrapper) { display: none !important; }
        }

        /* Elements Ordonnance */
        .header-hospital { text-align: center; border-bottom: 2px solid #0d6efd; margin-bottom: 25px; padding-bottom: 15px; }
        .header-hospital h1 { color: #0d6efd; font-weight: 800; margin: 0; font-size: 32px; }
        .prescription-title { font-size: 22px; font-weight: bold; border-bottom: 2px solid #000; margin: 25px 0 15px; }
        .med-item { border: 1px solid #eee; padding: 12px; margin-bottom: 10px; border-radius: 6px; page-break-inside: avoid; }

        /* Zone Signature & Cachet */
        .signature-area {
            position: relative;
            width: 320px;
            height: 160px;
            margin-top: 30px;
            float: right;
            text-align: center;
        }
        .img-cachet {
            position: absolute;
            width: 140px;
            left: 20px;
            top: 10px;
            opacity: 0.7; /* Transparence pour le réalisme */
            z-index: 1;
        }
        .img-signature {
            position: relative;
            width: 200px;
            z-index: 2;
        }
    </style>
</head>
<body>

<div class="print-wrapper">
    <!-- Navigation No-Print -->
    <div class="no-print d-flex justify-content-between mb-4 border-bottom pb-3">
        <div>
            <button class="btn btn-secondary" onclick="window.history.back()">
                <i class="bi bi-arrow-left"></i> Retour au dossier
            </button>
        </div>
        <div>
            <?php if($prescription['statut'] == 'EN_ATTENTE'): ?>
                <button class="btn btn-success btn-lg" id="btnSigner">
                    <i class="bi bi-pen"></i> Signer et envoyer en pharmacie
                </button>
            <?php else: ?>
                <span class="badge bg-success p-2 me-2"><i class="bi bi-check-circle"></i> Transmis à la pharmacie</span>
                <button class="btn btn-primary btn-lg" onclick="window.print()">
                    <i class="bi bi-printer"></i> Imprimer
                </button>
            <?php endif; ?>
        </div>
    </div>

    <!-- En-tête Hôpital -->
    <div class="header-hospital">
        <h1>DME HOSPITAL</h1>
        <p class="mb-0">Centre Hospitalier Universitaire</p>
        <p class="small text-muted">Yaoundé, Cameroun | Contact: +237 6XX XX XX XX</p>
    </div>

    <!-- Meta Infos -->
    <div class="row mb-4">
        <div class="col-7">
            <h6 class="text-primary fw-bold">PATIENT</h6>
            <div class="fs-5 fw-bold"><?= htmlspecialchars($prescription['patient_nom'] . ' ' . $prescription['patient_prenom']) ?></div>
            <div>Dossier N°: <?= htmlspecialchars($prescription['dossier_numero']) ?></div>
            <div>Âge: <?= date_diff(date_create($prescription['date_naissance']), date_create('now'))->y ?> ans</div>
        </div>
        <div class="col-5 text-end">
            <div class="fw-bold">Ordonnance N° #<?= str_pad($prescription['id'], 6, '0', STR_PAD_LEFT) ?></div>
            <div>Date: <?= date('d/m/Y à H:i', strtotime($prescription['date_prescription'])) ?></div>
            <div class="mt-2 fw-bold">Dr. <?= htmlspecialchars($prescription['medecin_nom'] . ' ' . $prescription['medecin_prenom']) ?></div>
        </div>
    </div>

    <div class="prescription-title">PRESCRIPTION</div>

    <!-- Liste Médicaments -->
    <div class="med-list">
        <?php foreach ($medicaments as $i => $med): ?>
        <div class="med-item">
            <div class="fw-bold"><?= ($i+1) ?>. <?= htmlspecialchars($med['medicament_nom']) ?> (<?= htmlspecialchars($med['dosage']) ?>)</div>
            <div class="small">Forme: <?= htmlspecialchars($med['forme']) ?> | Durée: <?= htmlspecialchars($med['duree'] ?: '-') ?></div>
            <div class="mt-1"><strong>Posologie:</strong> <?= nl2br(htmlspecialchars($med['posologie'])) ?></div>
        </div>
        <?php endforeach; ?>
    </div>

    <?php if(isset($prescription['recommandations']) && !empty($prescription['recommandations'])): ?>
    <div class="mt-3 p-2 bg-light border-start border-4 border-warning">
        <strong>Conseils :</strong> <?= nl2br(htmlspecialchars($prescription['recommandations'])) ?>
    </div>
    <?php endif; ?>

    <!-- Zone Authentification (Signature et Cachet) -->
    <div class="clearfix">
        <div class="signature-area">
            <?php if($prescription['statut'] != 'EN_ATTENTE'): ?>
                <p class="small mb-0 text-muted">Document signé électroniquement</p>

                <!-- Cachet -->
                <?php if(!empty($prescription['cachet_path'])): ?>
                    <img src="<?= BASE_URL . $prescription['cachet_path'] ?>" class="img-cachet">
                <?php endif; ?>

                <!-- Signature -->
                <?php if(!empty($prescription['signature_path'])): ?>
                    <img src="<?= BASE_URL . $prescription['signature_path'] ?>" class="img-signature">
                <?php endif; ?>

                <div class="mt-1 fw-bold border-top border-dark">Dr. <?= htmlspecialchars($prescription['medecin_nom']) ?></div>
            <?php else: ?>
                <div style="height: 100px; border: 1px dashed #ccc; padding-top: 40px;" class="text-muted">
                    En attente de signature
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="mt-5 text-center text-muted small border-top pt-2">
        Valable 3 mois | Document généré par DME Hospital
    </div>
</div>

<script>
document.getElementById('btnSigner')?.addEventListener('click', function() {
    if(!confirm("Confirmer la signature et l'envoi à la pharmacie ?")) return;

    const btn = this;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Transmission...';

    fetch('<?= BASE_URL ?>prescription/signer-et-envoyer', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'id=<?= $prescription['id'] ?>'
    })
    .then(res => res.json())
    .then(data => {
        if(data.success) {
            alert("✅ Ordonnance signée et transmise à la pharmacie.");
            location.reload();
        } else {
            alert("Erreur: " + data.message);
            btn.disabled = false;
        }
    })
    .catch(() => {
        alert("Erreur réseau");
        btn.disabled = false;
    });
});
</script>

</body>
</html>