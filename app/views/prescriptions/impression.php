<?php
// Page d'impression standalone — pas de header/footer global
define('HEADER_RENDERED', true);
$isSigned   = ($prescription['statut'] === 'SIGNEE');
$hasSig     = !empty($prescription['signature_src']);
$hasCachet  = !empty($prescription['cachet_src']);
$hopital    = $hopital ?? ['nom_hopital' => 'HÔPITAL SAINT-JEAN DE MALTE', 'adresse' => 'BP 56 Njombé, Cameroun', 'telephone' => '+237 697 09 29 92'];
$numero_ord = !empty($prescription['numero_ordre']) ? $prescription['numero_ordre'] : null;
$specialite = !empty($prescription['specialite'])   ? $prescription['specialite']   : null;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ordonnance N°<?= str_pad($prescription['id'], 6, '0', STR_PAD_LEFT) ?></title>
    <!--<link rel="stylesheet" href="<?= BASE_URL ?>public/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>public/css/bootstrap-icons.css">-->
<style>
/* ── GÉNÉRAL ─────────────────────────────────────────────────── */
*, *::before, *::after { box-sizing: border-box; }
body {
    background: #e8ecf0;
    font-family: 'Segoe UI', Arial, sans-serif;
    font-size: 13px;
    color: #1a1a2e;
    padding: 24px 0 60px;
}

/* ── FEUILLE A4 ──────────────────────────────────────────────── */
.page {
    width: 210mm;
    min-height: 297mm;
    background: #fff;
    margin: 0 auto;
    padding: 14mm 16mm 12mm;
    box-shadow: 0 8px 40px rgba(0,0,0,.18);
    position: relative;
    display: flex;
    flex-direction: column;
}

/* ── BARRE D'OUTILS (no-print) ───────────────────────────────── */
.toolbar {
    width: 210mm;
    margin: 0 auto 14px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 8px;
}

/* ── EN-TÊTE HÔPITAL ─────────────────────────────────────────── */
.header-wrap {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    border-bottom: 3px double #1a4e96;
    padding-bottom: 8px;
    margin-bottom: 10px;
}
.hospital-name {
    font-size: 15px;
    font-weight: 800;
    color: #1a4e96;
    letter-spacing: .5px;
    line-height: 1.25;
}
.hospital-sub {
    font-size: 10px;
    color: #555;
    margin-top: 2px;
    line-height: 1.5;
}
.doctor-block {
    text-align: right;
    font-size: 11px;
    line-height: 1.55;
}
.doctor-block .doctor-name {
    font-size: 13px;
    font-weight: 700;
    color: #1a4e96;
}
.doctor-block .badge-ordre {
    display: inline-block;
    background: #eef2ff;
    color: #1a4e96;
    border: 1px solid #c7d2fe;
    border-radius: 4px;
    font-size: 9px;
    font-weight: 700;
    padding: 1px 6px;
    letter-spacing: .4px;
    margin-top: 2px;
}

/* ── META LIGNE ─────────────────────────────────────────────── */
.meta-band {
    background: #f0f4ff;
    border: 1px solid #d0d9f5;
    border-radius: 6px;
    padding: 7px 12px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
    font-size: 11px;
}
.meta-band .patient-name {
    font-size: 14px;
    font-weight: 800;
    color: #1a1a2e;
}
.meta-band .dossier {
    font-size: 10px;
    color: #64748b;
    margin-top: 1px;
}
.meta-band .ord-ref {
    text-align: right;
    font-size: 10px;
    color: #64748b;
    line-height: 1.6;
}
.meta-band .ord-num {
    font-size: 13px;
    font-weight: 700;
    color: #1a4e96;
}

/* ── SECTION RP ─────────────────────────────────────────────── */
.rx-header {
    display: flex;
    align-items: center;
    gap: 10px;
    margin: 10px 0 6px;
}
.rx-symbol {
    font-size: 26px;
    font-weight: 900;
    color: #1a4e96;
    font-style: italic;
    line-height: 1;
}
.rx-label {
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 1px;
    color: #475569;
    border-bottom: 1px solid #e2e8f0;
    flex: 1;
    padding-bottom: 3px;
}

/* ── MÉDICAMENTS ─────────────────────────────────────────────── */
.med-item {
    padding: 7px 10px;
    margin-bottom: 6px;
    border-left: 3px solid #1a4e96;
    background: #f8faff;
    border-radius: 0 4px 4px 0;
    page-break-inside: avoid;
}
.med-item .med-name {
    font-weight: 700;
    font-size: 12.5px;
    color: #1a1a2e;
}
.med-item .med-detail {
    font-size: 11px;
    color: #475569;
    margin-top: 2px;
}
.med-item .med-poso {
    font-size: 11.5px;
    margin-top: 3px;
    color: #1a1a2e;
}

/* ── CONSEILS ────────────────────────────────────────────────── */
.conseils-block {
    background: #fffbeb;
    border-left: 3px solid #f59e0b;
    padding: 6px 10px;
    border-radius: 0 4px 4px 0;
    font-size: 11px;
    margin-top: 8px;
    page-break-inside: avoid;
}

/* ── ZONE SIGNATURE ──────────────────────────────────────────── */
.signature-section {
    margin-top: auto;
    padding-top: 20px;
    display: flex;
    justify-content: flex-end;
    align-items: flex-end;
}
.sig-box {
    width: 280px;
    text-align: center;
    position: relative;
}
.sig-inner {
    position: relative;
    height: 140px;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: visible;
}
.img-cachet {
    position: absolute;
    left: -8px;
    top: 50%;
    transform: translateY(-50%);
    width: 120px;
    height: 120px;
    object-fit: contain;
    opacity: .80;
    z-index: 1;
}
.img-signature {
    position: relative;
    max-width: 220px;
    max-height: 130px;
    object-fit: contain;
    z-index: 2;
    margin-left: 50px;
    filter: contrast(1.1);
}
.sig-pending {
    width: 220px;
    height: 100px;
    border: 1.5px dashed #cbd5e1;
    border-radius: 4px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #94a3b8;
    font-size: 11px;
}
.sig-name-line {
    border-top: 1.5px solid #1a1a2e;
    padding-top: 5px;
    font-size: 12px;
    font-weight: 700;
    margin-top: 6px;
    text-align: center;
}
.sig-name-sub {
    font-size: 10px;
    color: #64748b;
    font-weight: 400;
}

/* ── BADGE SIGNÉ ─────────────────────────────────────────────── */
.signed-badge {
    position: absolute;
    top: 16mm;
    right: 16mm;
    background: #dcfce7;
    border: 1.5px solid #16a34a;
    color: #15803d;
    border-radius: 6px;
    padding: 3px 10px;
    font-size: 9px;
    font-weight: 800;
    letter-spacing: .5px;
    text-transform: uppercase;
    transform: rotate(-3deg);
    z-index: 10;
}

/* ── PIED DE PAGE ────────────────────────────────────────────── */
.footer-doc {
    border-top: 1px solid #e2e8f0;
    padding-top: 6px;
    margin-top: 10px;
    display: flex;
    justify-content: space-between;
    align-items: flex-end;
    font-size: 9px;
    color: #94a3b8;
}
.hash-code {
    font-family: monospace;
    font-size: 8px;
    background: #f1f5f9;
    border: 1px solid #e2e8f0;
    padding: 2px 6px;
    border-radius: 3px;
    color: #475569;
}

/* ── IMPRESSION ──────────────────────────────────────────────── */
@media print {
    @page { size: A4 portrait; margin: 0; }
    body  { background: #fff !important; padding: 0 !important; }
    .toolbar, .no-print { display: none !important; }
    .page {
        box-shadow: none !important;
        width: 100% !important;
        min-height: 100vh;
        padding: 14mm 16mm 12mm;
        margin: 0;
    }
    .med-item, .conseils-block { page-break-inside: avoid; }
}
</style>
</head>
<body>

<!-- ── BARRE D'OUTILS ─────────────────────────────────────────── -->
<div class="toolbar no-print">
    <div class="d-flex gap-2">
        <button class="btn btn-sm btn-outline-secondary rounded-pill px-3" onclick="window.history.back()">
            <i class="bi bi-arrow-left me-1"></i>Retour
        </button>
        <a href="<?= BASE_URL ?>prescription/archives" class="btn btn-sm btn-outline-primary rounded-pill px-3">
            <i class="bi bi-archive me-1"></i>Archives
        </a>
    </div>
    <div class="d-flex gap-2 align-items-center">
        <?php if (!$isSigned): ?>
        <button class="btn btn-success rounded-pill px-4 fw-bold" id="btnSigner" <?= !$hasSig ? 'title="Aucune signature configurée"' : '' ?>>
            <i class="bi bi-pen-fill me-2"></i>Signer &amp; Envoyer en pharmacie
        </button>
        <?php else: ?>
        <span class="badge bg-success rounded-pill px-3 py-2">
            <i class="bi bi-check-circle-fill me-1"></i>Signé &amp; transmis
        </span>
        <?php endif; ?>
        <button class="btn btn-primary rounded-pill px-4 fw-bold" onclick="window.print()">
            <i class="bi bi-printer-fill me-2"></i>Imprimer
        </button>
    </div>
</div>

<!-- ── FEUILLE A4 ──────────────────────────────────────────────── -->
<div class="page">

    <?php if ($isSigned): ?>
    <div class="signed-badge"><i class="bi bi-shield-check me-1"></i>Signé électroniquement</div>
    <?php endif; ?>

    <!-- EN-TÊTE HÔPITAL / MÉDECIN -->
    <div class="header-wrap">
        <div>
            <div class="hospital-name"><?= htmlspecialchars($hopital['nom_hopital'] ?? 'HÔPITAL SAINT-JEAN DE MALTE') ?></div>
            <div class="hospital-sub">
                <?= htmlspecialchars($hopital['adresse'] ?? 'BP 56 Njombé, Cameroun') ?><br>
                Tél : <?= htmlspecialchars($hopital['telephone'] ?? '+237 697 09 29 92') ?>
            </div>
        </div>
        <div class="doctor-block">
            <div class="doctor-name">Dr. <?= htmlspecialchars($prescription['medecin_prenom'] . ' ' . $prescription['medecin_nom']) ?></div>
            <?php if ($specialite): ?>
            <div style="font-size:11px;color:#475569;"><?= htmlspecialchars($specialite) ?></div>
            <?php endif; ?>
            <?php if ($numero_ord): ?>
            <div class="badge-ordre">ORDRE N° <?= htmlspecialchars($numero_ord) ?></div>
            <?php endif; ?>
            <?php if (!empty($prescription['medecin_tel'])): ?>
            <div style="font-size:10px;color:#64748b;margin-top:2px;">Tél : <?= htmlspecialchars($prescription['medecin_tel']) ?></div>
            <?php endif; ?>
        </div>
    </div>

    <!-- META PATIENT / ORDONNANCE -->
    <div class="meta-band">
        <div>
            <div class="patient-name">
                <?= htmlspecialchars(strtoupper($prescription['patient_nom']) . ' ' . $prescription['patient_prenom']) ?>
            </div>
            <div class="dossier">
                Dossier N° <?= htmlspecialchars($prescription['dossier_numero'] ?? '') ?>
                <?php if (!empty($prescription['date_naissance'])): ?>
                — Âge : <?= date_diff(date_create($prescription['date_naissance']), date_create('today'))->y ?> ans
                <?php endif; ?>
                <?php if (!empty($prescription['sexe'])): ?>
                (<?= $prescription['sexe'] === 'M' ? 'Masculin' : 'Féminin' ?>)
                <?php endif; ?>
            </div>
        </div>
        <div class="ord-ref">
            <div class="ord-num">Ordonnance N° <?= str_pad($prescription['id'], 6, '0', STR_PAD_LEFT) ?></div>
            <div>Le <?= date('d/m/Y', strtotime($prescription['date_creation'])) ?></div>
            <?php if ($isSigned && !empty($prescription['signed_at'])): ?>
            <div style="color:#16a34a;font-weight:600;">Signée le <?= date('d/m/Y à H:i', strtotime($prescription['signed_at'])) ?></div>
            <?php endif; ?>
        </div>
    </div>

    <!-- MÉDICAMENTS -->
    <div class="rx-header">
        <span class="rx-symbol">℞</span>
        <span class="rx-label">Prescription médicale</span>
    </div>

    <div class="med-list">
        <?php foreach ($medicaments as $i => $med): ?>
        <div class="med-item">
            <div class="med-name">
                <?= ($i + 1) ?>. <?= htmlspecialchars($med['medicament_nom']) ?>
                <?php if (!empty($med['dosage'])): ?>
                <span style="font-weight:400;font-size:11px;color:#475569;"> — <?= htmlspecialchars($med['dosage']) ?></span>
                <?php endif; ?>
                <?php if (!empty($med['forme'])): ?>
                <span style="font-weight:400;font-size:11px;color:#64748b;"> (<?= htmlspecialchars($med['forme']) ?>)</span>
                <?php endif; ?>
            </div>
            <div class="med-poso">
                <i class="bi bi-arrow-right-short text-primary"></i>
                <?= nl2br(htmlspecialchars($med['posologie'])) ?>
            </div>
            <?php if (!empty($med['duree'])): ?>
            <div class="med-detail"><i class="bi bi-clock me-1"></i>Durée : <?= htmlspecialchars($med['duree']) ?></div>
            <?php endif; ?>
            <?php if (!empty($med['quantite_prescrite'])): ?>
            <div class="med-detail"><i class="bi bi-box me-1"></i>Qté : <?= (int)$med['quantite_prescrite'] ?></div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- CONSEILS -->
    <?php if (!empty($prescription['recommandations'])): ?>
    <div class="conseils-block">
        <strong><i class="bi bi-info-circle me-1"></i>Conseils :</strong>
        <?= nl2br(htmlspecialchars($prescription['recommandations'])) ?>
    </div>
    <?php endif; ?>

    <!-- ZONE SIGNATURE -->
    <div class="signature-section">
        <div class="sig-box">
            <?php if ($isSigned): ?>
            <div class="sig-inner">
                <?php if ($hasCachet): ?>
                <img src="<?= htmlspecialchars($prescription['cachet_src']) ?>" class="img-cachet" alt="Cachet">
                <?php endif; ?>
                <?php if ($hasSig): ?>
                <img src="<?= htmlspecialchars($prescription['signature_src']) ?>" class="img-signature" alt="Signature">
                <?php endif; ?>
                <?php if (!$hasSig && !$hasCachet): ?>
                <div class="sig-pending" style="border:1.5px solid #16a34a; color:#16a34a;">
                    <i class="bi bi-check-circle me-1"></i>Signé numériquement
                </div>
                <?php endif; ?>
            </div>
            <?php else: ?>
            <div class="sig-inner">
                <div class="sig-pending">En attente de signature</div>
            </div>
            <?php endif; ?>

            <div class="sig-name-line">
                Dr. <?= htmlspecialchars($prescription['medecin_prenom'] . ' ' . $prescription['medecin_nom']) ?>
                <?php if ($specialite): ?>
                <div class="sig-name-sub"><?= htmlspecialchars($specialite) ?></div>
                <?php endif; ?>
                <?php if ($numero_ord): ?>
                <div class="sig-name-sub">ORDRE N° <?= htmlspecialchars($numero_ord) ?></div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- PIED DE PAGE -->
    <div class="footer-doc">
        <div>
            Valable 3 mois à compter de la date d'émission
            <?php if (!empty($hopital['nom_hopital'])): ?>
            · <?= htmlspecialchars($hopital['nom_hopital']) ?>
            <?php endif; ?>
        </div>
        <div style="text-align:right;">
            <?php if ($isSigned && !empty($prescription['signature_hash'])): ?>
            <div>Réf. de vérification :</div>
            <div class="hash-code"><?= strtoupper(substr($prescription['signature_hash'], 0, 20)) ?>...</div>
            <?php else: ?>
            <div style="color:#cbd5e1;">Document non signé</div>
            <?php endif; ?>
        </div>
    </div>

</div><!-- /page -->

<script src="<?= BASE_URL ?>public/js/bootstrap.bundle.min.js"></script>
<script>
document.getElementById('btnSigner')?.addEventListener('click', function () {
    if (!confirm('Confirmer la signature électronique et l\'envoi en pharmacie ?')) return;

    const btn = this;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Signature en cours…';

    fetch('<?= BASE_URL ?>prescription/signer-et-envoyer', {
        method : 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body   : 'id=<?= (int)$prescription['id'] ?>'
    })
    .then(r => r.json())
    .then(d => {
        if (d.success) {
            // Afficher le hash court puis recharger pour voir le tampon
            const msg = '✅ Ordonnance signée et transmise à la pharmacie.\n\nCode de vérification : ' + (d.hash_short || '');
            alert(msg);
            location.reload();
        } else {
            alert('Erreur : ' + (d.message || 'Inconnue'));
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-pen-fill me-2"></i>Signer & Envoyer en pharmacie';
        }
    })
    .catch(() => {
        alert('Erreur réseau');
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-pen-fill me-2"></i>Signer & Envoyer en pharmacie';
    });
});
</script>
</body>
</html>
