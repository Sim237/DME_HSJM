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

// Page standalone — pas de header/footer global
define('HEADER_RENDERED', true);

$isSigned   = ($prescription['statut'] === 'SIGNEE');
$isParOrdre = ($prescription['type_ordonnance'] ?? '') === 'PAR_ORDRE';
$hasSig     = !empty($prescription['signature_src']);
$hasCachet  = !empty($prescription['cachet_src']);
$hopital    = $hopital ?? ['nom_hopital' => 'HÔPITAL SAINT-JEAN DE MALTE', 'adresse' => 'BP 56 Njombé, Cameroun', 'telephone' => '+237 697 09 29 92'];
$numero_ord = !empty($prescription['numero_ordre']) ? $prescription['numero_ordre'] : null;
$specialite = !empty($prescription['specialite'])   ? $prescription['specialite']   : null;
$patientId  = $prescription['patient_id'] ?? null;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Ordonnance N°<?= str_pad($prescription['id'], 6, '0', STR_PAD_LEFT) ?></title>
<link rel="stylesheet" href="<?= BASE_URL ?>public/css/bootstrap-icons.css">
<style>
/* ── Reset ─────────────────────────────────────────────────────────── */
*, *::before, *::after { box-sizing: border-box; }
html, body { margin: 0; padding: 0; }
body {
    font-family: 'Segoe UI', system-ui, Arial, sans-serif;
    font-size: 13px;
    color: #1e293b;
    background: #eef1f6;
    min-height: 100vh;
}

/* ── TOPBAR ─────────────────────────────────────────────────────────── */
.topbar {
    position: sticky; top: 0; z-index: 200;
    display: flex; align-items: center; justify-content: space-between;
    padding: 0 28px; height: 56px;
    background: #fff;
    border-bottom: 1px solid #e2e8f0;
    box-shadow: 0 1px 6px rgba(0,0,0,.07);
}
.topbar-left  { display: flex; align-items: center; gap: 8px; }
.topbar-right { display: flex; align-items: center; gap: 8px; }

.tb-btn {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 7px 16px; border-radius: 8px; font-size: .8rem;
    font-weight: 600; cursor: pointer; border: none;
    text-decoration: none; transition: all .15s;
    white-space: nowrap;
}
.tb-ghost  { background: #f1f5f9; color: #475569; }
.tb-ghost:hover  { background: #e2e8f0; }
.tb-primary { background: #2563eb; color: #fff; box-shadow: 0 2px 8px rgba(37,99,235,.25); }
.tb-primary:hover { background: #1d4ed8; transform: translateY(-1px); }
.tb-success { background: #16a34a; color: #fff; box-shadow: 0 2px 8px rgba(22,163,74,.2); }
.tb-success:hover { background: #15803d; transform: translateY(-1px); }
.tb-signed  {
    display: inline-flex; align-items: center; gap: 6px;
    background: #f0fdf4; color: #15803d;
    border: 1.5px solid #86efac; border-radius: 8px;
    padding: 6px 14px; font-size: .8rem; font-weight: 700;
}

/* ── PAGE A5 ────────────────────────────────────────────────────────── */
.a4-wrap { padding: 28px 0 60px; }

.a4 {
    width: 148mm;
    min-height: 210mm;
    background: #fff;
    margin: 0 auto;
    padding: 11mm 13mm 10mm;
    box-shadow: 0 6px 40px rgba(0,0,0,.13);
    border-radius: 4px;
    position: relative;
    display: flex;
    flex-direction: column;
    gap: 0;
}

/* ── BADGE SIGNÉ (watermark-like) ───────────────────────────────────── */
.badge-signe {
    position: absolute; top: 18mm; right: 16mm;
    background: #dcfce7; border: 1.5px solid #16a34a;
    color: #15803d; border-radius: 6px;
    padding: 4px 12px; font-size: 9px; font-weight: 800;
    letter-spacing: .6px; text-transform: uppercase;
    transform: rotate(-3deg); z-index: 10;
}

/* ── EN-TÊTE ────────────────────────────────────────────────────────── */
.ord-header {
    display: flex;
    align-items: center;
    gap: 16px;
    padding-bottom: 10px;
    border-bottom: 3px double #1e40af;
    margin-bottom: 12px;
}
.hospital-emblem {
    width: 64px; height: 64px;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
}
.hospital-emblem img { width: 64px; height: 64px; object-fit: contain; }
.hospital-info { flex: 1; }
.hospital-name { font-size: 14px; font-weight: 800; color: #1e40af; letter-spacing: .3px; line-height: 1.35; }
.hospital-sub  { font-size: 10px; color: #64748b; margin-top: 3px; line-height: 1.7; }

/* ── BANDEAU PATIENT ────────────────────────────────────────────────── */
.patient-band {
    display: flex; justify-content: space-between; align-items: center;
    background: #f0f4ff;
    border: 1px solid #c7d2fe;
    border-radius: 8px;
    padding: 10px 14px;
    margin-bottom: 14px;
}
.patient-left {}
.patient-fullname { font-size: 15px; font-weight: 800; color: #1e293b; }
.patient-meta     { font-size: 10px; color: #64748b; margin-top: 2px; }

.ord-ref-block { text-align: right; }
.ord-number    { font-size: 13px; font-weight: 800; color: #1e40af; }
.ord-date      { font-size: 10px; color: #64748b; margin-top: 2px; }
.ord-signed-at { font-size: 9.5px; color: #15803d; font-weight: 600; margin-top: 2px; }

/* ── ℞ SECTION ──────────────────────────────────────────────────────── */
.rx-row {
    display: flex; align-items: center; gap: 10px;
    margin: 6px 0 10px;
}
.rx-symbol {
    font-size: 30px; font-weight: 900;
    font-style: italic; color: #1e40af;
    line-height: 1; flex-shrink: 0;
}
.rx-divider {
    flex: 1; height: 1px;
    background: linear-gradient(to right, #c7d2fe, #e2e8f0);
}
.rx-label {
    font-size: 10px; font-weight: 700;
    text-transform: uppercase; letter-spacing: 1.2px;
    color: #64748b; flex-shrink: 0;
}

/* ── MÉDICAMENTS ────────────────────────────────────────────────────── */
.med-list { display: flex; flex-direction: column; gap: 6px; margin-bottom: 12px; }

.med-item {
    display: grid;
    grid-template-columns: 28px 1fr;
    gap: 0 10px;
    align-items: flex-start;
    padding: 10px 12px;
    background: #f8faff;
    border: 1px solid #e0e7ff;
    border-left: 4px solid #2563eb;
    border-radius: 0 8px 8px 0;
    page-break-inside: avoid;
}
.med-num {
    width: 24px; height: 24px; border-radius: 50%;
    background: #2563eb; color: #fff;
    font-size: 11px; font-weight: 800;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0; margin-top: 1px;
}
.med-body {}
.med-name {
    font-size: 13px; font-weight: 800; color: #1e293b;
    display: flex; flex-wrap: wrap; align-items: baseline; gap: 4px;
}
.med-dosage { font-size: 11px; font-weight: 400; color: #64748b; }
.med-forme  { font-size: 11px; font-weight: 400; color: #94a3b8; font-style: italic; }
.med-poso   { margin-top: 5px; font-size: 12px; color: #1e293b; }
.med-poso-label {
    display: inline-block; background: #eff6ff; color: #1d4ed8;
    border-radius: 4px; font-size: 9.5px; font-weight: 700;
    padding: 1px 6px; margin-right: 4px;
}
.med-duree  {
    margin-top: 4px; font-size: 11px; color: #64748b;
    display: flex; align-items: center; gap: 4px;
}
.med-duree::before {
    content: ''; display: inline-block; width: 12px; height: 12px;
    background: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='%2394a3b8' viewBox='0 0 16 16'%3E%3Cpath d='M8 3.5a.5.5 0 0 0-1 0V9a.5.5 0 0 0 .252.434l3.5 2a.5.5 0 0 0 .496-.868L8 8.71z'/%3E%3Cpath d='M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16m7-8A7 7 0 1 1 1 8a7 7 0 0 1 14 0'/%3E%3C/svg%3E") center/contain no-repeat;
    flex-shrink: 0;
}

/* ── CONSEILS ───────────────────────────────────────────────────────── */
.conseils {
    background: #fffbeb;
    border: 1px solid #fde68a;
    border-left: 4px solid #f59e0b;
    border-radius: 0 8px 8px 0;
    padding: 8px 12px;
    font-size: 11px;
    page-break-inside: avoid;
}
.conseils-title { font-weight: 700; color: #92400e; margin-bottom: 3px; }

/* ── SIGNATURE ZONE ─────────────────────────────────────────────────── */
.sig-zone {
    margin-top: auto;
    padding-top: 20px;
    display: flex;
    justify-content: flex-end;
}
.sig-box {
    width: 210px;
    text-align: center;
}
.sig-visual {
    height: 100px;
    border-radius: 8px;
    display: flex; align-items: center; justify-content: center;
    position: relative;
    margin-bottom: 6px;
}
.sig-pending-box {
    width: 100%; height: 100%;
    border: 2px dashed #cbd5e1;
    border-radius: 8px;
    display: flex; flex-direction: column; align-items: center; justify-content: center;
    color: #94a3b8; font-size: 11px; gap: 4px;
}
.img-cachet {
    position: absolute; left: 0; top: 0;
    width: 80px; height: 80px; object-fit: contain;
    opacity: .75; z-index: 1;
}
.img-signature {
    max-width: 160px; max-height: 90px; object-fit: contain;
    z-index: 2; position: relative; margin-left: 20px;
}
.sig-name {
    border-top: 1.5px solid #1e293b;
    padding-top: 5px;
    font-size: 11.5px; font-weight: 700; color: #1e293b;
}
.sig-sub { font-size: 9.5px; color: #64748b; font-weight: 400; margin-top: 1px; }

/* ── PIED DE PAGE ───────────────────────────────────────────────────── */
.ord-footer {
    border-top: 1px solid #e2e8f0;
    padding-top: 7px;
    margin-top: 10px;
    display: flex;
    justify-content: space-between;
    align-items: flex-end;
    font-size: 9px; color: #94a3b8;
}
.hash-code {
    font-family: 'Courier New', monospace;
    font-size: 8px;
    background: #f1f5f9;
    border: 1px solid #e2e8f0;
    padding: 2px 7px; border-radius: 4px; color: #475569;
    letter-spacing: .5px;
}

/* ── IMPRESSION ─────────────────────────────────────────────────────── */
@media print {
    @page {
        size: A5 portrait;
        /* Marges suffisantes pour que les en-têtes/pieds navigateur
           tombent hors de la zone utile — l'idéal reste de les désactiver
           dans "Plus de paramètres → En-têtes et pieds de page" */
        margin: 0mm;
    }
    body  { background: #fff !important; padding: 0 !important; }
    .topbar, .no-print, .print-hint { display: none !important; }
    .a4-wrap { padding: 0 !important; }
    .a4 {
        box-shadow: none !important; border-radius: 0;
        width: 100% !important;
        min-height: 0 !important;
        margin: 0; padding: 8mm 12mm 6mm;
    }
    /* Compactage A5 */
    .ord-header   { padding-bottom: 6px; margin-bottom: 8px; gap: 10px; }
    .hospital-emblem { width: 46px; height: 46px; }
    .hospital-emblem img { width: 46px; height: 46px; }
    .hospital-name { font-size: 11px; }
    .hospital-sub  { font-size: 9px; }
    .patient-band  { padding: 7px 10px; margin-bottom: 10px; }
    .patient-fullname { font-size: 12px; }
    .patient-meta  { font-size: 9px; }
    .rx-row        { margin: 4px 0 6px; }
    .rx-symbol     { font-size: 22px; }
    .med-list      { gap: 4px; margin-bottom: 8px; }
    .med-item      { padding: 6px 10px; }
    .med-name      { font-size: 11px; }
    .med-poso      { font-size: 10px; margin-top: 3px; }
    .med-duree     { font-size: 9px; margin-top: 2px; }
    .sig-zone      { padding-top: 10px; }
    .sig-visual    { height: 70px; }
    .sig-box       { width: 170px; }
    .ord-footer    { padding-top: 5px; margin-top: 6px; font-size: 8px; }
    .med-item, .conseils { page-break-inside: avoid; }
}
</style>
</head>
<body>

<!-- ══ TOPBAR ════════════════════════════════════════════════════════════════ -->
<div class="topbar no-print">
    <div class="topbar-left">
        <?php if ($patientId): ?>
        <a href="<?= BASE_URL ?>patients/dossier/<?= (int)$patientId ?>" class="tb-btn tb-ghost">
            <i class="bi bi-arrow-left"></i> Dossier
        </a>
        <?php else: ?>
        <button class="tb-btn tb-ghost" onclick="history.back()">
            <i class="bi bi-arrow-left"></i> Retour
        </button>
        <?php endif; ?>
        <a href="<?= BASE_URL ?>prescription/archives" class="tb-btn tb-ghost">
            <i class="bi bi-archive"></i> Archives
        </a>
    </div>

    <div style="font-size:.78rem;font-weight:600;color:#94a3b8;">
        Ordonnance N°<?= str_pad($prescription['id'], 6, '0', STR_PAD_LEFT) ?>
    </div>

    <div class="topbar-right">
        <?php
            $sessionRole_imp = strtoupper($_SESSION['user_role'] ?? '');
            $sessionUser_imp = (int)($_SESSION['user_id'] ?? 0);
            $canEdit = ($prescription['statut'] !== 'TERMINEE')
                    && (($isParOrdre && $sessionUser_imp === (int)($prescription['infirmier_prescripteur_id'] ?? -1))
                        || $sessionUser_imp === (int)($prescription['medecin_id'] ?? -1)
                        || in_array($sessionRole_imp, ['ADMIN','ADMINISTRATEUR']));
        ?>
        <?php if ($canEdit && !$isParOrdre): ?>
        <a href="<?= BASE_URL ?>prescription/edit/<?= (int)$prescription['id'] ?>" class="tb-btn tb-ghost"
           title="<?= $isSigned ? 'Modifier réinitialisera la signature' : 'Modifier l\'ordonnance' ?>">
            <i class="bi bi-pencil-square"></i> Modifier
        </a>
        <?php endif; ?>

        <?php if (!$isSigned && !$isParOrdre): ?>
        <button class="tb-btn tb-success" id="btnSigner"
                <?= !$hasSig ? 'title="Aucune signature configurée — voir votre profil"' : '' ?>>
            <i class="bi bi-patch-check-fill"></i> Valider avec signature
        </button>
        <?php elseif ($isSigned): ?>
        <div class="tb-signed">
            <i class="bi bi-shield-check-fill"></i> Signé &amp; transmis
        </div>
        <?php elseif ($isParOrdre): ?>
        <div class="tb-signed" style="background:#fef9c3;border-color:#fde68a;color:#854d0e;">
            <i class="bi bi-person-badge-fill"></i> Ordonnance Par Ordre
        </div>
        <?php endif; ?>
        <button class="tb-btn tb-primary" onclick="printA5()">
            <i class="bi bi-printer-fill"></i> Imprimer
        </button>
    </div>
</div>

<!-- ══ PAGE A4 ════════════════════════════════════════════════════════════════ -->
<div class="a4-wrap">
<div class="a4">

    <!-- EN-TÊTE HÔPITAL -->
    <div class="ord-header">
        <div class="hospital-emblem">
            <img src="<?= BASE_URL ?>public/images/logo_ordre_malte.png" alt="Logo Hôpital"
                 onerror="this.style.display='none'">
        </div>
        <div class="hospital-info">
            <div class="hospital-name">
                ORDRE DE MALTE —
                <?= htmlspecialchars($hopital['nom_hopital'] ?? 'HÔPITAL SAINT-JEAN DE MALTE') ?>
            </div>
            <div class="hospital-sub">
                <?= htmlspecialchars($hopital['adresse'] ?? 'B.P.: 56 NJOMBÉ') ?>
                &nbsp;·&nbsp; Tél : <?= htmlspecialchars($hopital['telephone'] ?? '(237) 697 09 29 92') ?>
                <?php if (!empty($hopital['email'])): ?>
                &nbsp;·&nbsp; <?= htmlspecialchars($hopital['email']) ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- BANDEAU PATIENT -->
    <div class="patient-band">
        <div class="patient-left">
            <div class="patient-fullname">
                <?= htmlspecialchars(strtoupper($prescription['patient_nom'] ?? '') . ' ' . ($prescription['patient_prenom'] ?? '')) ?>
            </div>
            <div class="patient-meta">
                Dossier N° <?= htmlspecialchars($prescription['dossier_numero'] ?? '—') ?>
                <?php if (!empty($prescription['date_naissance'])): ?>
                &nbsp;·&nbsp; <?= date_diff(date_create($prescription['date_naissance']), date_create('today'))->y ?> ans
                <?php endif; ?>
                <?php if (!empty($prescription['sexe'])): ?>
                &nbsp;·&nbsp; <?= $prescription['sexe'] === 'M' ? 'Masculin' : 'Féminin' ?>
                <?php endif; ?>
            </div>
        </div>
        <div class="ord-ref-block">
            <div class="ord-number">Ordonnance N° <?= str_pad($prescription['id'], 6, '0', STR_PAD_LEFT) ?></div>
            <div class="ord-date">Le <?= date('d/m/Y', strtotime($prescription['date_creation'])) ?></div>
            <?php if ($isSigned && !empty($prescription['signed_at'])): ?>
            <div class="ord-signed-at"><i class="bi bi-check-circle-fill me-1"></i>Signée le <?= date('d/m/Y à H:i', strtotime($prescription['signed_at'])) ?></div>
            <?php endif; ?>
        </div>
    </div>

    <!-- ℞ MÉDICAMENTS -->
    <div class="rx-row">
        <span class="rx-symbol">℞</span>
        <span class="rx-label">Prescription médicale</span>
        <div class="rx-divider"></div>
    </div>

    <div class="med-list">
        <?php foreach ($medicaments as $i => $med): ?>
        <div class="med-item">
            <div class="med-num"><?= $i + 1 ?></div>
            <div class="med-body">
                <div class="med-name">
                    <?= htmlspecialchars($med['medicament_nom']) ?>
                    <?php if (!empty($med['dosage'])): ?>
                    <span class="med-dosage">— <?= htmlspecialchars($med['dosage']) ?></span>
                    <?php endif; ?>
                    <?php if (!empty($med['forme'])): ?>
                    <span class="med-forme">(<?= htmlspecialchars($med['forme']) ?>)</span>
                    <?php endif; ?>
                </div>
                <?php if (!empty($med['posologie'])): ?>
                <div class="med-poso">
                    <span class="med-poso-label">Posologie</span>
                    <?= htmlspecialchars($med['posologie']) ?>
                </div>
                <?php endif; ?>
                <?php if (!empty($med['duree'])): ?>
                <div class="med-duree">Durée : <?= htmlspecialchars($med['duree']) ?></div>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- CONSEILS -->
    <?php $notesTxt = $prescription['notes'] ?? $prescription['recommandations'] ?? ''; ?>
    <?php if (!empty($notesTxt)): ?>
    <div class="conseils">
        <div class="conseils-title"><i class="bi bi-info-circle me-1"></i>Conseils &amp; précautions</div>
        <?= nl2br(htmlspecialchars($notesTxt)) ?>
    </div>
    <?php endif; ?>

    <!-- ZONE SIGNATURE -->
    <div class="sig-zone">
        <div class="sig-box" style="<?= $isParOrdre ? 'width:240px;' : '' ?>">
            <?php if ($isParOrdre): ?>
            <!-- Mention par ordre au-dessus de la signature -->
            <div style="font-size:9px;color:#92400e;background:#fef9c3;border:1px solid #fde68a;
                        border-radius:5px;padding:3px 8px;text-align:center;margin-bottom:6px;font-weight:700;letter-spacing:.4px;">
                P/O — Inf.&nbsp;<?= htmlspecialchars(($prescription['infirmier_prenom'] ?? '') . ' ' . ($prescription['infirmier_nom'] ?? '')) ?>
            </div>
            <?php endif; ?>
            <div class="sig-visual">
                <?php if ($isSigned || ($isParOrdre && ($hasSig || $hasCachet))): ?>
                    <?php if ($hasCachet): ?>
                    <img src="<?= htmlspecialchars($prescription['cachet_src']) ?>" class="img-cachet" alt="Cachet">
                    <?php endif; ?>
                    <?php if ($hasSig): ?>
                    <img src="<?= htmlspecialchars($prescription['signature_src']) ?>" class="img-signature" alt="Signature">
                    <?php else: ?>
                    <div style="font-size:11px;color:#16a34a;font-weight:700;">
                        <i class="bi bi-check-circle-fill"></i><br><?= $isParOrdre ? 'Validé par ordre' : 'Signé numériquement' ?>
                    </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="sig-pending-box">
                        <i class="bi bi-pen" style="font-size:1.4rem;"></i>
                        En attente de signature
                    </div>
                <?php endif; ?>
            </div>
            <div class="sig-name">
                Dr. <?= htmlspecialchars(($prescription['medecin_prenom'] ?? '') . ' ' . ($prescription['medecin_nom'] ?? '')) ?>
                <?php if ($specialite): ?>
                <div class="sig-sub"><?= htmlspecialchars($specialite) ?></div>
                <?php endif; ?>
                <?php if ($numero_ord): ?>
                <div class="sig-sub">Ordre N° <?= htmlspecialchars($numero_ord) ?></div>
                <?php endif; ?>
                <?php if ($isParOrdre): ?>
                <div class="sig-sub" style="color:#92400e;margin-top:2px;">
                    Prescrit par ordre · Inf. <?= htmlspecialchars(($prescription['infirmier_prenom'] ?? '') . ' ' . ($prescription['infirmier_nom'] ?? '')) ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- PIED DE PAGE -->
    <div class="ord-footer">
        <div>Valable 3 mois · <?= htmlspecialchars($hopital['nom_hopital'] ?? '') ?></div>
        <div style="text-align:right;">
            <?php if ($isSigned && !empty($prescription['signature_hash'])): ?>
            <div>Réf. vérification :</div>
            <div class="hash-code"><?= strtoupper(substr($prescription['signature_hash'], 0, 24)) ?>…</div>
            <?php else: ?>
            <span style="color:#cbd5e1;">Document non signé</span>
            <?php endif; ?>
        </div>
    </div>

</div><!-- /a4 -->
</div><!-- /a4-wrap -->

<script>
document.getElementById('btnSigner')?.addEventListener('click', function () {
    if (!confirm('Valider cette ordonnance avec votre signature ?\n\nElle sera transmise immédiatement à la pharmacie.')) return;
    const btn = this;
    btn.disabled = true;
    btn.innerHTML = '<span style="display:inline-block;width:14px;height:14px;border:2px solid rgba(255,255,255,.4);border-top-color:#fff;border-radius:50%;animation:spin .7s linear infinite;margin-right:6px;vertical-align:middle;"></span>Signature…';

    fetch('<?= BASE_URL ?>prescription/signer-et-envoyer', {
        method : 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body   : 'id=<?= (int)$prescription['id'] ?>'
    })
    .then(r => r.json())
    .then(d => {
        if (d.success) {
            location.reload();
        } else {
            alert('Erreur : ' + (d.message || 'Inconnue'));
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-patch-check-fill"></i> Valider avec signature';
        }
    })
    .catch(() => {
        alert('Erreur réseau');
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-patch-check-fill"></i> Valider avec signature';
    });
});
</script>
<style>
@keyframes spin { to { transform: rotate(360deg); } }
</style>

<!-- BANDEAU HINT A5 (masqué à l'impression) -->
<div class="print-hint no-print" id="printHint" style="
    position:fixed;bottom:18px;left:50%;transform:translateX(-50%);
    background:#1e293b;color:#fff;border-radius:10px;
    padding:10px 20px;font-size:.78rem;display:none;
    box-shadow:0 4px 20px rgba(0,0,0,.3);z-index:9999;
    display:flex;align-items:center;gap:14px;max-width:560px;">
    <span style="font-size:1.1rem;">🖨️</span>
    <span>Dans la boîte d'impression : <strong>Plus de paramètres</strong> →
        <strong>Format du papier : A5</strong> &amp;
        décocher <strong>En-têtes et pieds de page</strong></span>
    <button onclick="document.getElementById('printHint').style.display='none'"
        style="background:transparent;border:none;color:#94a3b8;font-size:1rem;cursor:pointer;flex-shrink:0;">✕</button>
</div>

<script>
function printA5() {
    document.getElementById('printHint').style.display = 'flex';
    setTimeout(() => window.print(), 300);
}
</script>
</body>
</html>
