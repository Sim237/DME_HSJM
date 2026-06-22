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

require_once __DIR__ . '/../layouts/header.php';

$consultation    = $consultation    ?? [];
$patient         = $patient         ?? [];
$antecedents     = $antecedents     ?? [];

$age = !empty($patient['date_naissance'])
    ? date_diff(date_create($patient['date_naissance']), date_create('today'))->y . ' ans'
    : 'N/A';

// Fusionner antécédents : priorité à la consultation (données saisies), puis patient, puis table antecedents
// NOTE : utiliser ?: (elvis) et non ?? pour que les chaînes vides soient traitées comme absentes
$atcd_allergies = (!empty($consultation['atcd_allergies']) ? $consultation['atcd_allergies'] : null)
               ?: (!empty($patient['allergies']) ? $patient['allergies'] : null)
               ?: '';
$atcd_med       = (!empty($consultation['atcd_medicaux']) ? $consultation['atcd_medicaux'] : null)
               ?: (!empty($patient['antecedents_medicaux']) ? $patient['antecedents_medicaux'] : null)
               ?: '';
$atcd_chir      = (!empty($consultation['atcd_chirurgicaux']) ? $consultation['atcd_chirurgicaux'] : null)
               ?: '';
$atcd_fam       = (!empty($consultation['atcd_familiaux']) ? $consultation['atcd_familiaux'] : null)
               ?: '';

// Enrichir depuis la table antecedents si encore vide
if (empty($atcd_allergies) && !empty($antecedents['allergique'])) {
    $atcd_allergies = implode(', ', array_column($antecedents['allergique'], 'description'));
}
if (empty($atcd_med) && !empty($antecedents['medical'])) {
    $atcd_med = implode(' | ', array_column($antecedents['medical'], 'description'));
}
if (empty($atcd_chir) && !empty($antecedents['chirurgical'])) {
    $atcd_chir = implode(' | ', array_column($antecedents['chirurgical'], 'description'));
}
if (empty($atcd_fam) && !empty($antecedents['familial'])) {
    $atcd_fam = implode(' | ', array_column($antecedents['familial'], 'description'));
}

// Antécédents toxicologiques et médicamenteux (saisis en consultation)
$atcd_toxico    = (!empty($consultation['atcd_toxicologiques']) ? $consultation['atcd_toxicologiques'] : null) ?: '';
$atcd_medicam   = (!empty($consultation['atcd_medicamenteux'])  ? $consultation['atcd_medicamenteux']  : null) ?: '';

$hasAtcd = $atcd_allergies || $atcd_med || $atcd_chir || $atcd_fam || $atcd_toxico || $atcd_medicam;

// Antécédents gynécologiques (JSON)
$atcd_gyneco = [];
if (!empty($consultation['atcd_gyneco'])) {
    $atcd_gyneco = json_decode($consultation['atcd_gyneco'], true) ?: [];
}
$hasGyneco = !empty($atcd_gyneco) && ($patient['sexe'] ?? '') === 'F';

// Score orientation
require_once __DIR__ . '/../../services/HospitalisationService.php';
$age_val = !empty($patient['date_naissance'])
    ? (new DateTime())->diff(new DateTime($patient['date_naissance']))->y
    : null;
$analyse = HospitalisationService::analyserCriteresHospitalisation($consultation, $age_val);
$niveau  = $analyse['recommandation']['niveau'] ?? 'suivi_ambulatoire';
$score   = $analyse['score_risque'] ?? 0;

$orientationConfig = [
    'hospitalisation_urgente'    => ['color'=>'#dc2626','bg'=>'#fef2f2','icon'=>'bi-exclamation-octagon-fill','label'=>'Hospitalisation d\'Urgence'],
    'hospitalisation_recommandee'=> ['color'=>'#d97706','bg'=>'#fffbeb','icon'=>'bi-exclamation-triangle-fill','label'=>'Hospitalisation Recommandée'],
    'suivi_ambulatoire'          => ['color'=>'#059669','bg'=>'#f0fdf4','icon'=>'bi-check-circle-fill','label'=>'Suivi Ambulatoire'],
];
$ori = $orientationConfig[$niveau] ?? $orientationConfig['suivi_ambulatoire'];
?>

<link rel="stylesheet" href="<?= BASE_URL ?>public/css/bootstrap-icons.css">

<style>
/* ── Reset & Base ── */
*, *::before, *::after { box-sizing: border-box; }
body { background: #f1f5f9; font-family: 'Inter', sans-serif; color: #1e293b; }
.sidebar { display: none !important; }
main, .col-md-10, .ms-sm-auto {
    margin-left: 0 !important; width: 100% !important;
    flex: 0 0 100% !important; max-width: 100% !important; padding: 0 !important;
}

/* ── Topbar ── */
.rc-topbar {
    background: #fff;
    border-bottom: 1px solid #e2e8f0;
    padding: 10px 24px;
    position: sticky; top: 0; z-index: 1000;
    box-shadow: 0 1px 8px rgba(0,0,0,.06);
    display: flex; justify-content: space-between; align-items: center;
}
.rc-topbar-logo { display: flex; align-items: center; gap: 12px; }
.rc-topbar-logo img { height: 32px; }
.rc-topbar-title { font-weight: 700; font-size: .95rem; color: #1e293b; }
.rc-topbar-sub   { font-size: .72rem; color: #94a3b8; margin-top: 1px; }

/* ── Layout ── */
.rc-page  { max-width: 1140px; margin: 0 auto; padding: 24px 16px 60px; }

/* ── Patient Hero ── */
.rc-hero {
    background: linear-gradient(135deg, #0f172a 0%, #1e3a5f 100%);
    border-radius: 20px;
    padding: 28px 32px;
    margin-bottom: 24px;
    color: #fff;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 16px;
    position: relative;
    overflow: hidden;
}
.rc-hero::before {
    content: '';
    position: absolute; right: -60px; top: -60px;
    width: 220px; height: 220px;
    border-radius: 50%;
    background: rgba(255,255,255,.04);
}
.rc-hero-name   { font-size: 1.7rem; font-weight: 800; letter-spacing: -.5px; margin: 0; }
.rc-hero-dossier{ font-size: .78rem; color: rgba(255,255,255,.55); margin-bottom: 6px; font-weight: 500; }
.rc-hero-badge  { display: inline-flex; align-items: center; gap: 6px; padding: 5px 14px; border-radius: 20px; font-size: .72rem; font-weight: 700; margin-top: 8px; }
.rc-hero-date   { text-align: right; }
.rc-hero-date-label { font-size: .72rem; color: rgba(255,255,255,.5); }
.rc-hero-date-val   { font-size: 1rem; font-weight: 700; }
.rc-hero-age        { font-size: .9rem; color: #38bdf8; font-weight: 600; margin-top: 4px; }

/* ── Vitals Bar ── */
.rc-vitals { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; margin-bottom: 24px; }
.rc-vital {
    background: #fff; border-radius: 14px; padding: 16px 20px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 2px 6px rgba(0,0,0,.04);
    display: flex; flex-direction: column; gap: 4px;
}
.rc-vital-label { font-size: .65rem; font-weight: 700; text-transform: uppercase; letter-spacing: .8px; color: #94a3b8; }
.rc-vital-val   { font-size: 1.4rem; font-weight: 800; line-height: 1; }
.rc-vital-unit  { font-size: .75rem; font-weight: 500; }
@media (max-width: 640px) { .rc-vitals { grid-template-columns: repeat(2,1fr); } }

/* ── Card ── */
.rc-card {
    background: #fff; border-radius: 16px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 2px 10px rgba(0,0,0,.04);
    margin-bottom: 16px; overflow: hidden;
}
.rc-card-head {
    padding: 13px 20px;
    background: #f8fafc;
    border-bottom: 1px solid #f1f5f9;
    display: flex; align-items: center; gap: 10px;
    font-size: .72rem; font-weight: 800; text-transform: uppercase; letter-spacing: .8px;
}
.rc-card-body { padding: 20px; }
.rc-card-body p { margin-bottom: 10px; font-size: .9rem; line-height: 1.6; color: #334155; }
.rc-card-body p:last-child { margin-bottom: 0; }
.rc-label { font-weight: 700; color: #475569; margin-right: 6px; }

/* ── Symptoms chips ── */
.rc-chips { display: flex; flex-wrap: wrap; gap: 7px; margin-top: 10px; }
.rc-chip  {
    padding: 4px 12px; border-radius: 20px; font-size: .78rem; font-weight: 600;
    background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0;
}
.rc-chip-system {
    background: #ede9fe; color: #5b21b6; border-color: #ddd6fe;
    font-size: .8rem; font-weight: 700;
}

/* ── Antécédents grid ── */
.rc-atcd-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
@media (max-width: 640px) { .rc-atcd-grid { grid-template-columns: 1fr; } }
.rc-atcd-item {
    border-radius: 10px; padding: 14px 16px;
    border-left: 3px solid transparent;
    font-size: .85rem;
}
.rc-atcd-label { font-weight: 700; font-size: .72rem; text-transform: uppercase; letter-spacing: .5px; margin-bottom: 6px; display: flex; align-items: center; gap: 6px; }
.rc-atcd-text  { color: #475569; line-height: 1.5; }
.atcd-allergy  { background: #fff5f5; border-color: #fca5a5; }
.atcd-allergy  .rc-atcd-label { color: #dc2626; }
.atcd-medical  { background: #eff6ff; border-color: #93c5fd; }
.atcd-medical  .rc-atcd-label { color: #1d4ed8; }
.atcd-chir     { background: #f0fdf4; border-color: #86efac; }
.atcd-chir     .rc-atcd-label { color: #15803d; }
.atcd-fam      { background: #faf5ff; border-color: #c4b5fd; }
.atcd-fam      .rc-atcd-label { color: #6d28d9; }

/* ── Diagnostic ── */
.rc-diag {
    background: linear-gradient(135deg,#fef2f2,#fff5f5);
    border: 1px solid #fecaca;
    border-radius: 12px; padding: 18px 20px;
    margin-bottom: 16px;
}
.rc-diag-code { font-size: 1.1rem; font-weight: 800; color: #dc2626; letter-spacing: .3px; }
.rc-diag-diff { font-size: .82rem; color: #64748b; margin-top: 6px; }

/* ── Traitement médicaments ── */
.rc-med-list { display: flex; flex-direction: column; gap: 8px; }
.rc-med-item {
    display: flex; justify-content: space-between; align-items: flex-start;
    padding: 12px 16px; border-radius: 10px;
    background: #f8fafc; border: 1px solid #e2e8f0;
}
.rc-med-name  { font-weight: 700; font-size: .9rem; color: #1e293b; }
.rc-med-detail{ font-size: .78rem; color: #64748b; margin-top: 2px; }
.rc-med-stock { font-size: .75rem; font-weight: 700; padding: 3px 10px; border-radius: 20px; }
.stock-ok   { background: #dcfce7; color: #15803d; }
.stock-none { background: #fee2e2; color: #dc2626; }
.stock-man  { background: #fef9c3; color: #92400e; }

/* ── Score ── */
.rc-score-bar { background: #e2e8f0; border-radius: 20px; height: 8px; overflow: hidden; margin: 12px 0 6px; }
.rc-score-fill { height: 100%; border-radius: 20px; transition: width .6s ease; }

/* ── Orientation ── */
.rc-orientation {
    border-radius: 14px; padding: 20px;
    display: flex; align-items: center; gap: 16px;
    margin-bottom: 16px; border: 1px solid;
}
.rc-ori-icon { font-size: 2rem; flex-shrink: 0; }
.rc-ori-title { font-weight: 800; font-size: 1rem; margin: 0; }
.rc-ori-sub   { font-size: .8rem; opacity: .7; margin-top: 2px; }

/* ── Action buttons ── */
.rc-action-btns { display: flex; flex-direction: column; gap: 10px; }
.rc-btn-action {
    border: none; border-radius: 12px; padding: 14px 20px;
    font-weight: 700; font-size: .88rem; cursor: pointer;
    display: flex; align-items: center; gap: 10px;
    transition: transform .15s, box-shadow .15s;
    text-decoration: none;
}
.rc-btn-action:hover { transform: translateY(-2px); box-shadow: 0 6px 16px rgba(0,0,0,.12); }
.rc-btn-urgence  { background: #dc2626; color: #fff; }
.rc-btn-prog     { background: #f59e0b; color: #fff; }
.rc-btn-ambu     { background: #10b981; color: #fff; }

/* ── Print ── */
@media print {
    .no-print { display: none !important; }
    .rc-page  { padding: 0; max-width: 100%; }
    .rc-hero  { background: #1e293b !important; -webkit-print-color-adjust: exact; }
    .rc-card  { box-shadow: none; page-break-inside: avoid; }
}
</style>

<!-- TOP BAR -->
<div class="rc-topbar no-print">
    <div class="rc-topbar-logo">
        <img src="<?= BASE_URL ?>public/images/logo_ordre_malte.png" onerror="this.style.display='none'">
        <div>
            <div class="rc-topbar-title">Synthèse Consultation</div>
            <div class="rc-topbar-sub">Hôpital Saint-Jean de Malte — Njombé</div>
        </div>
    </div>
    <div class="d-flex gap-2 align-items-center">
        <button onclick="window.print()" class="btn btn-sm btn-outline-secondary rounded-pill px-3">
            <i class="bi bi-printer me-1"></i> Imprimer
        </button>
        <?php
        $canEdit = in_array($_SESSION['user_role'] ?? '', ['ADMIN','ADMINISTRATEUR','DIRECTEUR','MEDECIN'])
                   && ((int)($consultation['medecin_id'] ?? 0) === (int)($_SESSION['user_id'] ?? -1)
                       || in_array($_SESSION['user_role'] ?? '', ['ADMIN','ADMINISTRATEUR','DIRECTEUR']));
        if ($canEdit):
        ?>
        <a href="<?= BASE_URL ?>consultation/modifier/<?= $consultation['id'] ?>"
           class="btn btn-sm btn-warning rounded-pill px-3 fw-bold no-print"
           style="color:#1e293b"
           onclick="return confirm('Modifier cette consultation ?\n\nVous allez revenir au formulaire. Toutes les étapes seront pré-remplies avec les données existantes.')">
            <i class="bi bi-pencil-square me-1"></i> Modifier
        </a>
        <?php endif; ?>
        <a href="<?= BASE_URL ?>consultation/cloturer/<?= $consultation['id'] ?>"
           class="btn btn-sm btn-success rounded-pill px-4 fw-bold">
            <i class="bi bi-check2-circle me-1"></i> QUITTER
        </a>
    </div>
</div>

<div class="rc-page">

    <!-- ── HERO PATIENT ── -->
    <div class="rc-hero">
        <div>
            <div class="rc-hero-dossier">
                <i class="bi bi-folder2-open me-1"></i>
                Dossier <?= htmlspecialchars($patient['dossier_numero'] ?? '') ?>
                &nbsp;·&nbsp; Dr.&nbsp;<?= htmlspecialchars($consultation['medecin_nom'] ?? '') ?>
            </div>
            <h1 class="rc-hero-name">
                <?= strtoupper(htmlspecialchars($patient['nom'] ?? '')) ?>
                <?= htmlspecialchars($patient['prenom'] ?? '') ?>
            </h1>
            <?php
            $typeLabel = strtoupper($consultation['type'] ?? 'EXTERNE');
            $typeBg = ($typeLabel === 'INTERNE') ? 'rgba(251,191,36,.2)' : 'rgba(56,189,248,.2)';
            $typeColor = ($typeLabel === 'INTERNE') ? '#fbbf24' : '#38bdf8';
            ?>
            <span class="rc-hero-badge" style="background:<?= $typeBg ?>; color:<?= $typeColor ?>;">
                <i class="bi bi-<?= $typeLabel === 'INTERNE' ? 'hospital' : 'person-walking' ?>"></i>
                <?= $typeLabel === 'INTERNE' ? 'Hospitalisé' : 'Ambulatoire' ?>
            </span>
        </div>
        <div class="rc-hero-date">
            <div class="rc-hero-date-label">Consultation du</div>
            <div class="rc-hero-date-val">
                <?= date('d/m/Y', strtotime($consultation['date_consultation'] ?? 'now')) ?>
            </div>
            <div style="font-size:.8rem;color:rgba(255,255,255,.5);">
                à <?= date('H:i', strtotime($consultation['date_consultation'] ?? 'now')) ?>
            </div>
            <div class="rc-hero-age"><?= $age ?> / <?= $patient['sexe'] ?? 'N/A' ?></div>
        </div>
    </div>

    <!-- ── CONSTANTES VITALES ── -->
    <div class="rc-vitals">
        <div class="rc-vital">
            <span class="rc-vital-label">Température</span>
            <span class="rc-vital-val" style="color:#ef4444;">
                <?= $consultation['temperature'] ?? '--' ?><span class="rc-vital-unit">°C</span>
            </span>
        </div>
        <div class="rc-vital">
            <span class="rc-vital-label">Tension</span>
            <span class="rc-vital-val" style="color:#3b82f6;">
                <?php
                    if (!empty($consultation['tension_non_prenable'])) {
                        echo '<span style="color:#92400e;font-size:.85rem;" title="Tension non prenable">⚠ NP</span>';
                    } else {
                        $sys = $consultation['tension_systolique'] ?? null;
                        $dia = $consultation['tension_diastolique'] ?? null;
                        echo ($sys && $dia) ? "$sys/$dia" : '--/--';
                    }
                ?>
                <span class="rc-vital-unit">mmHg</span>
            </span>
        </div>
        <div class="rc-vital">
            <span class="rc-vital-label">FC</span>
            <span class="rc-vital-val" style="color:#10b981;">
                <?= $consultation['frequence_cardiaque'] ?? '--' ?>
                <span class="rc-vital-unit">bpm</span>
            </span>
        </div>
        <?php if (!empty($consultation['frequence_respiratoire'])): ?>
        <div class="rc-vital">
            <span class="rc-vital-label">FR</span>
            <span class="rc-vital-val" style="color:#0ea5e9;">
                <?= (int)$consultation['frequence_respiratoire'] ?>
                <span class="rc-vital-unit">/min</span>
            </span>
        </div>
        <?php endif; ?>
        <?php if (!empty($consultation['pouls'])): ?>
        <div class="rc-vital">
            <span class="rc-vital-label">Pouls</span>
            <span class="rc-vital-val" style="color:#8b5cf6;">
                <?= (int)$consultation['pouls'] ?>
                <span class="rc-vital-unit">bpm</span>
            </span>
        </div>
        <?php endif; ?>
        <div class="rc-vital">
            <span class="rc-vital-label">Poids</span>
            <span class="rc-vital-val" style="color:#1e293b;">
                <?= $consultation['poids'] ?? '--' ?>
                <span class="rc-vital-unit">kg</span>
            </span>
        </div>
    </div>

    <?php
    // ── TA Bras gauche / droit (si renseignées séparément) ──
    $hasTaBras = !empty($consultation['ta_bras_gauche_systolique']) || !empty($consultation['ta_bras_droit_systolique']);
    if ($hasTaBras && empty($consultation['tension_non_prenable'])):
    ?>
    <div style="display:flex;gap:14px;flex-wrap:wrap;justify-content:center;margin:8px 0 16px;font-size:.82rem;">
        <?php if (!empty($consultation['ta_bras_gauche_systolique'])): ?>
        <span style="background:#eff6ff;color:#1e40af;padding:5px 14px;border-radius:18px;font-weight:600;">
            🫲 TA bras gauche : <strong><?= (int)$consultation['ta_bras_gauche_systolique'] ?>/<?= (int)$consultation['ta_bras_gauche_diastolique'] ?></strong>
        </span>
        <?php endif; ?>
        <?php if (!empty($consultation['ta_bras_droit_systolique'])): ?>
        <span style="background:#eff6ff;color:#1e40af;padding:5px 14px;border-radius:18px;font-weight:600;">
            🫱 TA bras droit : <strong><?= (int)$consultation['ta_bras_droit_systolique'] ?>/<?= (int)$consultation['ta_bras_droit_diastolique'] ?></strong>
        </span>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <?php if (!empty($consultation['tension_non_prenable'])): ?>
    <div style="text-align:center;margin:8px 0 16px;font-size:.82rem;">
        <span style="background:#fffbeb;color:#92400e;padding:6px 16px;border-radius:18px;font-weight:600;border:1px solid #fde68a;">
            <i class="bi bi-exclamation-triangle-fill me-1"></i>
            Tension non prenable
            <?php if (!empty($consultation['motif_tension_non_prenable'])): ?>
                — <?= htmlspecialchars($consultation['motif_tension_non_prenable']) ?>
            <?php endif; ?>
        </span>
    </div>
    <?php endif; ?>

    <div class="row g-4">

        <!-- ════ COLONNE GAUCHE ════ -->
        <div class="col-lg-7">

            <!-- ANAMNÈSE -->
            <div class="rc-card">
                <div class="rc-card-head" style="color:#0ea5e9;">
                    <i class="bi bi-chat-left-quote-fill"></i> Anamnèse
                </div>
                <div class="rc-card-body">
                    <p>
                        <span class="rc-label">Motif :</span>
                        <?= htmlspecialchars($consultation['motif_consultation'] ?? '—') ?>
                    </p>
                    <p>
                        <span class="rc-label">Histoire de la maladie :</span><br>
                        <?= nl2br(htmlspecialchars($consultation['histoire_maladie'] ?? '—')) ?>
                    </p>
                    <?php if (!empty($consultation['automedication'])): ?>
                    <p>
                        <span class="rc-label">Automédication :</span>
                        <?= nl2br(htmlspecialchars($consultation['automedication'])) ?>
                    </p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- ENQUÊTE SYSTÉMIQUE -->
            <?php if (!empty($consultation['systeme_principal']) || !empty($consultation['symptomes_systemiques']) || !empty($consultation['commentaires_systemiques'])): ?>
            <div class="rc-card">
                <div class="rc-card-head" style="color:#7c3aed;">
                    <i class="bi bi-heart-pulse-fill"></i> Enquête Systémique
                </div>
                <div class="rc-card-body">
                    <?php if (!empty($consultation['systeme_principal'])): ?>
                    <p class="mb-2">
                        <span class="rc-label">Système :</span>
                        <span class="rc-chip rc-chip-system">
                            <?= htmlspecialchars($consultation['systeme_principal']) ?>
                        </span>
                    </p>
                    <?php endif; ?>
                    <?php if (!empty($consultation['symptomes_systemiques'])): ?>
                    <div class="rc-chips">
                        <?php foreach (explode(',', $consultation['symptomes_systemiques']) as $s):
                            $s = trim($s); if (!$s) continue; ?>
                        <span class="rc-chip"><?= htmlspecialchars($s) ?></span>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($consultation['commentaires_systemiques'])): ?>
                    <p class="mb-0 mt-2" style="font-size:.85rem;color:#374151;background:#f5f3ff;border-left:3px solid #7c3aed;border-radius:6px;padding:8px 12px;">
                        <i class="bi bi-plus-circle-fill me-1" style="color:#7c3aed;"></i>
                        <strong style="color:#6d28d9;">Autre(s) :</strong>
                        <?= nl2br(htmlspecialchars($consultation['commentaires_systemiques'])) ?>
                    </p>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- ANTÉCÉDENTS -->
            <?php if ($hasAtcd): ?>
            <div class="rc-card">
                <div class="rc-card-head" style="color:#b45309;">
                    <i class="bi bi-journal-medical"></i> Antécédents du patient
                </div>
                <div class="rc-card-body">
                    <div class="rc-atcd-grid">
                        <?php if ($atcd_allergies): ?>
                        <div class="rc-atcd-item atcd-allergy" style="grid-column: 1/-1;">
                            <div class="rc-atcd-label">
                                <i class="bi bi-exclamation-triangle-fill"></i> Allergies
                            </div>
                            <div class="rc-atcd-text"><?= nl2br(htmlspecialchars($atcd_allergies)) ?></div>
                        </div>
                        <?php endif; ?>
                        <?php if ($atcd_med): ?>
                        <div class="rc-atcd-item atcd-medical">
                            <div class="rc-atcd-label">
                                <i class="bi bi-heart-pulse"></i> Médicaux
                            </div>
                            <div class="rc-atcd-text"><?= nl2br(htmlspecialchars($atcd_med)) ?></div>
                        </div>
                        <?php endif; ?>
                        <?php if ($atcd_chir): ?>
                        <div class="rc-atcd-item atcd-chir">
                            <div class="rc-atcd-label">
                                <i class="bi bi-scissors"></i> Chirurgicaux
                            </div>
                            <div class="rc-atcd-text"><?= nl2br(htmlspecialchars($atcd_chir)) ?></div>
                        </div>
                        <?php endif; ?>
                        <?php if ($atcd_fam): ?>
                        <div class="rc-atcd-item atcd-fam" style="grid-column: <?= ($atcd_med && $atcd_chir) ? '1/-1' : 'auto' ?>;">
                            <div class="rc-atcd-label">
                                <i class="bi bi-people"></i> Familiaux
                            </div>
                            <div class="rc-atcd-text"><?= nl2br(htmlspecialchars($atcd_fam)) ?></div>
                        </div>
                        <?php endif; ?>
                        <?php if ($atcd_toxico): ?>
                        <div class="rc-atcd-item" style="border-left:3px solid #f97316; background:#fff7ed;">
                            <div class="rc-atcd-label" style="color:#c2410c;">
                                <i class="bi bi-exclamation-octagon"></i> Toxicologiques
                            </div>
                            <div class="rc-atcd-text"><?= nl2br(htmlspecialchars($atcd_toxico)) ?></div>
                        </div>
                        <?php endif; ?>
                        <?php if ($atcd_medicam): ?>
                        <div class="rc-atcd-item" style="border-left:3px solid #14b8a6; background:#f0fdfa;">
                            <div class="rc-atcd-label" style="color:#0f766e;">
                                <i class="bi bi-capsule"></i> Médicamenteux
                            </div>
                            <div class="rc-atcd-text"><?= nl2br(htmlspecialchars($atcd_medicam)) ?></div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php else: ?>
            <div class="rc-card">
                <div class="rc-card-head" style="color:#94a3b8;">
                    <i class="bi bi-journal-medical"></i> Antécédents
                </div>
                <div class="rc-card-body">
                    <p class="text-muted small mb-0 fst-italic">
                        <i class="bi bi-info-circle me-1"></i>
                        Aucun antécédent connu enregistré pour ce patient.
                    </p>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($hasGyneco): ?>
            <div class="rc-card" style="border-top: 3px solid #ec4899;">
                <div class="rc-card-head" style="color:#be185d; background: linear-gradient(135deg,#fdf2f8,#fce7f3);">
                    <i class="bi bi-gender-female"></i> Antécédents Gynécologiques
                </div>
                <div class="rc-card-body">
                    <div style="display:grid; grid-template-columns: repeat(3,1fr); gap:10px 20px;">
                        <?php
                        $gynecoLabels = [
                            'ddr'                 => 'DDR',
                            'menarche'            => 'Ménarche',
                            'cycle'               => 'Cycle',
                            'duree_regles'        => 'Durée règles',
                            'gestite'             => 'Gestité (G)',
                            'parite'              => 'Parité (P)',
                            'avortements'         => 'Avortements / FC',
                            'grossesse_actuelle'  => 'Grossesse actuelle',
                            'terme_sa'            => 'Terme (SA)',
                            'contraception'       => 'Contraception',
                            'allaitement'         => 'Allaitement',
                            'derniere_gyneco'     => 'Dern. consul. gynéco',
                            'derniere_frottis'    => 'Dern. frottis',
                            'mammographie'        => 'Dern. mammographie',
                        ];
                        foreach ($gynecoLabels as $k => $label):
                            $val = $atcd_gyneco[$k] ?? '';
                            if ($val === '' || $val === null) continue;
                            // Formatter les dates
                            if (in_array($k, ['ddr','derniere_gyneco','derniere_frottis','mammographie'])
                                && preg_match('/^\d{4}-\d{2}-\d{2}$/', $val)) {
                                $val = date('d/m/Y', strtotime($val));
                            }
                            if ($k === 'menarche') $val .= ' ans';
                            if ($k === 'duree_regles') $val .= ' jours';
                            if ($k === 'terme_sa') $val .= ' SA';
                        ?>
                        <div>
                            <div style="font-size:.72rem; font-weight:700; color:#9d174d; text-transform:uppercase; letter-spacing:.3px;">
                                <?= htmlspecialchars($label) ?>
                            </div>
                            <div style="font-size:.88rem; color:#1e293b; margin-top:2px;">
                                <?= htmlspecialchars($val) ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php if (!empty($atcd_gyneco['notes'])): ?>
                    <div style="margin-top:10px; padding-top:8px; border-top:1px solid #fce7f3;">
                        <div style="font-size:.72rem; font-weight:700; color:#9d174d; text-transform:uppercase;">Notes</div>
                        <div style="font-size:.88rem; color:#1e293b; margin-top:2px; white-space:pre-line;">
                            <?= nl2br(htmlspecialchars($atcd_gyneco['notes'])) ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- EXAMEN PHYSIQUE -->
            <div class="rc-card">
                <div class="rc-card-head" style="color:#f59e0b;">
                    <i class="bi bi-person-check-fill"></i> Examen Physique
                </div>
                <div class="rc-card-body">
                    <p class="mb-0" style="white-space: pre-line;">
                        <?= nl2br(htmlspecialchars($consultation['examen_physique'] ?? '—')) ?>
                    </p>
                </div>
            </div>

        </div><!-- /col gauche -->

        <!-- ════ COLONNE DROITE ════ -->
        <div class="col-lg-5">

            <!-- DIAGNOSTIC -->
            <div class="rc-card" style="border-top: 4px solid #dc2626;">
                <div class="rc-card-head" style="color:#dc2626;">
                    <i class="bi bi-bullseye"></i> Diagnostic
                </div>
                <div class="rc-card-body">
                    <div class="rc-diag">
                        <div class="rc-diag-code">
                            <?= htmlspecialchars($consultation['diagnostic_principal'] ?? '—') ?>
                        </div>
                        <?php if (!empty($consultation['hypotheses_diagnostiques'])): ?>
                        <div class="rc-diag-diff">
                            <i class="bi bi-arrow-right me-1"></i>
                            <?= htmlspecialchars($consultation['hypotheses_diagnostiques']) ?>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($consultation['diagnostics_differentiels'])): ?>
                        <div class="rc-diag-diff mt-2">
                            <strong style="color:#94a3b8;font-size:.7rem;">DIFF. :</strong>
                            <?= htmlspecialchars($consultation['diagnostics_differentiels']) ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- TRAITEMENT -->
            <div class="rc-card" style="border-top: 4px solid #10b981;">
                <div class="rc-card-head" style="color:#10b981;">
                    <i class="bi bi-capsule-pill"></i> Traitement prescrit
                </div>
                <div class="rc-card-body">
                    <?php if (!empty($consultation['plan_traitement'])): ?>
                    <p class="mb-3 small" style="background:#f0fdf4;border-radius:8px;padding:10px 12px;color:#166534;border-left:3px solid #10b981;">
                        <?= nl2br(htmlspecialchars($consultation['plan_traitement'])) ?>
                    </p>
                    <?php endif; ?>
                    <?php if (!empty($consultation['traitement_non_medicamenteux'])): ?>
                    <p class="small mb-0">
                        <span class="rc-label">Non médicamenteux :</span>
                        <?= nl2br(htmlspecialchars($consultation['traitement_non_medicamenteux'])) ?>
                    </p>
                    <?php endif; ?>
                    <?php if (empty($consultation['plan_traitement']) && empty($consultation['traitement_non_medicamenteux'])): ?>
                    <p class="text-muted small fst-italic mb-0">Aucun traitement saisi.
                        <?php if (!empty($prescription)): ?>
                        <span class="text-success fw-semibold ms-1"><i class="bi bi-check-circle-fill"></i> Ordonnance médicamenteuse enregistrée ci-dessous.</span>
                        <?php endif; ?>
                    </p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- PLAN DE SURVEILLANCE -->
            <?php if (!empty($consultation['surveillance'])): ?>
            <div class="rc-card" style="border-top: 4px solid #f59e0b;">
                <div class="rc-card-head" style="color:#d97706;">
                    <i class="bi bi-activity"></i> Plan de surveillance
                </div>
                <div class="rc-card-body">
                    <p class="small mb-0"
                       style="background:#fffbeb;border-radius:8px;padding:12px 14px;color:#92400e;border-left:3px solid #f59e0b;white-space:pre-line;line-height:1.7;">
                        <?= nl2br(htmlspecialchars($consultation['surveillance'])) ?>
                    </p>
                </div>
            </div>
            <?php endif; ?>

            <!-- EXAMENS PARACLINIQUES -->
            <?php if (!empty($consultation['examens_paracliniques'])): ?>
            <div class="rc-card" style="border-top: 4px solid #0891b2;">
                <div class="rc-card-head" style="color:#0891b2;">
                    <i class="bi bi-flask-fill"></i> Examens paracliniques demandés
                </div>
                <div class="rc-card-body">
                    <p class="small mb-0"
                       style="background:#ecfeff;border-radius:8px;padding:10px 12px;color:#164e63;border-left:3px solid #0891b2;white-space:pre-line;">
                        <?= nl2br(htmlspecialchars($consultation['examens_paracliniques'])) ?>
                    </p>
                </div>
            </div>
            <?php endif; ?>

            <!-- TDR (Tests de Diagnostic Rapide réalisés sur place) -->
            <?php
            $tdrList = [];
            if (!empty($consultation['tests_tdr'])) {
                $decoded = json_decode($consultation['tests_tdr'], true);
                if (is_array($decoded)) $tdrList = $decoded;
            }
            if (!empty($tdrList)):
                $tdrStyles = [
                    'POSITIF' => ['bg'=>'#fee2e2','fg'=>'#991b1b','icon'=>'🟥','label'=>'Positif'],
                    'NEGATIF' => ['bg'=>'#dcfce7','fg'=>'#166534','icon'=>'🟩','label'=>'Négatif'],
                    'DOUTEUX' => ['bg'=>'#fef3c7','fg'=>'#92400e','icon'=>'🟨','label'=>'Douteux'],
                    'VALEUR'  => ['bg'=>'#dbeafe','fg'=>'#1e40af','icon'=>'📊','label'=>'Valeur'],
                ];
            ?>
            <div class="rc-card" style="border-top: 4px solid #ea580c;">
                <div class="rc-card-head" style="color:#ea580c;">
                    <i class="bi bi-eyedropper"></i> Tests de Diagnostic Rapide (TDR) — réalisés sur place
                </div>
                <div class="rc-card-body">
                    <div style="display:flex;flex-direction:column;gap:8px;">
                    <?php foreach ($tdrList as $tdr):
                        $st = $tdrStyles[$tdr['resultat'] ?? ''] ?? ['bg'=>'#f1f5f9','fg'=>'#64748b','icon'=>'❓','label'=>($tdr['resultat'] ?? '?')];
                        $typeLabel = $tdr['type_label'] ?? $tdr['type'] ?? 'TDR';
                    ?>
                    <div style="display:flex;align-items:center;gap:12px;padding:8px 12px;background:#fff7ed;border-radius:8px;border:1px solid #fed7aa;">
                        <div style="font-size:1.3rem;"><?= $st['icon'] ?></div>
                        <div style="flex:1;">
                            <div style="font-weight:700;color:#9a3412;font-size:.9rem;"><?= htmlspecialchars($typeLabel) ?></div>
                            <div style="display:flex;flex-wrap:wrap;gap:8px;margin-top:3px;font-size:.78rem;align-items:center;">
                                <span style="background:<?= $st['bg'] ?>;color:<?= $st['fg'] ?>;padding:2px 9px;border-radius:11px;font-weight:700;">
                                    <?= htmlspecialchars($st['label']) ?><?= !empty($tdr['valeur']) ? ' — ' . htmlspecialchars($tdr['valeur']) : '' ?>
                                </span>
                                <?php if (!empty($tdr['heure'])): ?>
                                <span class="text-muted"><i class="bi bi-clock me-1"></i><?= htmlspecialchars($tdr['heure']) ?></span>
                                <?php endif; ?>
                                <?php if (!empty($tdr['note'])): ?>
                                <span class="text-muted fst-italic">— <?= htmlspecialchars($tdr['note']) ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- ORDONNANCE — Signature & Transmission -->
            <?php if (!empty($prescription)): ?>
            <div class="rc-card" style="border-top: 4px solid #6366f1;">
                <div class="rc-card-head" style="color:#6366f1;">
                    <i class="bi bi-file-earmark-medical-fill"></i> Ordonnance
                    <?php
                    $ordStatut = $prescription['statut'] ?? 'EN_ATTENTE';
                    $statutBadges = [
                        'EN_ATTENTE' => ['bg'=>'#fff7ed','color'=>'#c2410c','label'=>'En attente de signature'],
                        'SIGNEE'     => ['bg'=>'#f0fdf4','color'=>'#15803d','label'=>'Signée'],
                        'EN_COURS'   => ['bg'=>'#eff6ff','color'=>'#1d4ed8','label'=>'En cours de préparation'],
                        'TERMINEE'   => ['bg'=>'#f0fdf4','color'=>'#15803d','label'=>'Délivrée'],
                    ];
                    $sb = $statutBadges[$ordStatut] ?? $statutBadges['EN_ATTENTE'];
                    ?>
                    <span style="float:right;font-size:.7rem;font-weight:800;padding:3px 10px;border-radius:20px;background:<?= $sb['bg'] ?>;color:<?= $sb['color'] ?>;">
                        <?= $sb['label'] ?>
                    </span>
                </div>
                <div class="rc-card-body">
                    <?php if (!empty($medicaments_prescrits)): ?>
                    <ul class="list-unstyled mb-3" style="font-size:.85rem;">
                        <?php foreach ($medicaments_prescrits as $mp): ?>
                        <li style="padding:5px 0;border-bottom:1px solid #f1f5f9;display:flex;gap:8px;align-items:flex-start;">
                            <i class="bi bi-capsule" style="color:#6366f1;margin-top:2px;flex-shrink:0;"></i>
                            <span>
                                <strong><?= htmlspecialchars($mp['nom_medicament'] ?? '—') ?></strong>
                                <?php if (!empty($mp['posologie'])): ?><br><span style="color:#64748b;font-size:.78rem;"><?= htmlspecialchars($mp['posologie']) ?><?= !empty($mp['duree']) ? ' — ' . htmlspecialchars($mp['duree']) : '' ?></span><?php endif; ?>
                            </span>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                    <?php endif; ?>
                    <?php if ($ordStatut === 'SIGNEE' || $ordStatut === 'TERMINEE'): ?>
                    <a href="<?= BASE_URL ?>prescription/print?id=<?= (int)$prescription['id'] ?>"
                       target="_blank"
                       class="btn btn-sm btn-outline-success w-100 rounded-pill">
                        <i class="bi bi-check-circle-fill me-1"></i> Voir l'ordonnance signée
                    </a>
                    <?php else: ?>
                    <a href="<?= BASE_URL ?>prescription/print?id=<?= (int)$prescription['id'] ?>"
                       target="_blank"
                       class="btn btn-sm w-100 rounded-pill fw-bold text-white"
                       style="background:linear-gradient(135deg,#6366f1,#4f46e5);">
                        <i class="bi bi-pen-fill me-1"></i> Imprimer &amp; Signer l'ordonnance
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            <?php else: ?>
            <!-- Aucune ordonnance — proposer d'en créer une via modification -->
            <div class="rc-card" style="border-top: 4px solid #e2e8f0;">
                <div class="rc-card-head" style="color:#94a3b8;">
                    <i class="bi bi-file-earmark-medical"></i> Ordonnance médicamenteuse
                </div>
                <div class="rc-card-body text-center py-3">
                    <p class="text-muted small mb-2">Aucune ordonnance enregistrée pour cette consultation.</p>
                    <?php
                    $userRole = $_SESSION['user_role'] ?? '';
                    $userId   = (int)($_SESSION['user_id'] ?? 0);
                    $isAdmin  = in_array($userRole, ['ADMIN', 'ADMINISTRATEUR', 'DIRECTEUR']);
                    $canEdit  = $isAdmin || (int)($consultation['medecin_id'] ?? 0) === $userId;
                    ?>
                    <?php if ($canEdit): ?>
                    <a href="<?= BASE_URL ?>consultation/modifier/<?= (int)($consultation['id']) ?>" class="btn btn-sm btn-outline-primary rounded-pill px-4">
                        <i class="bi bi-pencil me-1"></i> Modifier la consultation pour ajouter une ordonnance
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- BULLETIN EXAMENS LABORATOIRE -->
            <?php
            $examensLabo = array_filter($examens_list ?? [], function($e) {
                return strtolower($e['type_examen'] ?? '') === 'laboratoire';
            });
            $bulletinLaboSigne = $bulletins_labo[0] ?? null;
            if (!empty($examensLabo) || $bulletinLaboSigne):
            ?>
            <div class="rc-card" style="border-top: 4px solid #0891b2;">
                <div class="rc-card-head" style="color:#0891b2;">
                    <i class="bi bi-flask-fill"></i> Bulletin Laboratoire
                    <?php if ($bulletinLaboSigne): ?>
                    <span style="float:right;font-size:.7rem;font-weight:800;padding:3px 10px;border-radius:20px;background:#f0fdf4;color:#15803d;">
                        Enregistré
                    </span>
                    <?php else: ?>
                    <span style="float:right;font-size:.7rem;font-weight:800;padding:3px 10px;border-radius:20px;background:#fff7ed;color:#c2410c;">
                        À signer
                    </span>
                    <?php endif; ?>
                </div>
                <div class="rc-card-body">
                    <?php if (!empty($examensLabo)): ?>
                    <ul class="list-unstyled mb-3" style="font-size:.85rem;">
                        <?php foreach ($examensLabo as $examen): ?>
                        <li style="padding:5px 0;border-bottom:1px solid #f1f5f9;display:flex;gap:8px;align-items:flex-start;">
                            <i class="bi bi-droplet" style="color:#0891b2;margin-top:2px;flex-shrink:0;"></i>
                            <span>
                                <strong><?= htmlspecialchars($examen['noms_examens'] ?? 'Examens demandés') ?></strong>
                                <br><span style="color:#64748b;font-size:.78rem;">
                                    <?= date('d/m/Y', strtotime($examen['date_demande'])) ?>
                                    — <em><?= htmlspecialchars($examen['statut'] ?? 'En attente') ?></em>
                                </span>
                            </span>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                    <?php elseif ($bulletinLaboSigne && !empty($bulletinLaboSigne['examens'])): ?>
                    <?php
                        // Fallback : afficher les noms depuis le JSON du bulletin si la jointure SQL n'a rien retourné
                        $nomsFromBulletin = json_decode($bulletinLaboSigne['examens'], true) ?? [];
                    ?>
                    <?php if (!empty($nomsFromBulletin)): ?>
                    <ul class="list-unstyled mb-3" style="font-size:.85rem;">
                        <?php foreach ($nomsFromBulletin as $nomEx): ?>
                        <li style="padding:5px 0;border-bottom:1px solid #f1f5f9;display:flex;gap:8px;align-items:flex-start;">
                            <i class="bi bi-droplet" style="color:#0891b2;margin-top:2px;flex-shrink:0;"></i>
                            <span><strong><?= htmlspecialchars($nomEx) ?></strong></span>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                    <?php endif; ?>
                    <?php endif; ?>

                    <?php if ($bulletinLaboSigne): ?>
                    <div class="d-flex gap-2">
                        <?php $isSigne = !empty($bulletinLaboSigne['signe']); ?>
                        <a href="<?= BASE_URL ?>formulaire/voir-bulletin-examens/<?= (int)$bulletinLaboSigne['id'] ?>"
                           target="_blank"
                           class="btn btn-sm rounded-pill flex-grow-1 <?= $isSigne ? 'btn-outline-success' : 'btn-outline-primary' ?>">
                            <?php if ($isSigne): ?>
                                <i class="bi bi-check-circle-fill me-1"></i> Voir le Bulletin signé
                            <?php else: ?>
                                <i class="bi bi-pen-fill me-1"></i> Voir / Signer le Bulletin
                            <?php endif; ?>
                        </a>
                        <a href="<?= BASE_URL ?>formulaire/creer/bulletin-examens/<?= $patient['id'] ?>?type=labo&consultation_id=<?= $consultation['id'] ?>"
                           target="_blank"
                           class="btn btn-sm btn-outline-secondary rounded-pill px-3"
                           title="Créer un nouveau bulletin">
                            <i class="bi bi-plus"></i>
                        </a>
                    </div>
                    <?php else: ?>
                    <a href="<?= BASE_URL ?>formulaire/creer/bulletin-examens/<?= $patient['id'] ?>?type=labo&consultation_id=<?= $consultation['id'] ?>"
                       target="_blank"
                       class="btn btn-sm w-100 rounded-pill fw-bold text-white"
                       style="background:linear-gradient(135deg,#0891b2,#0284c7);">
                        <i class="bi bi-pen-fill me-1"></i> Imprimer &amp; Signer le Bulletin Laboratoire
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- BULLETIN EXAMENS IMAGERIE MÉDICALE -->
            <?php
            $examensImagerie = array_filter($examens_list ?? [], function($e) {
                return strtolower($e['type_examen'] ?? '') === 'imagerie';
            });
            $bulletinImgSigne = $bulletins_imagerie[0] ?? null;
            if (!empty($examensImagerie) || $bulletinImgSigne):
            ?>
            <div class="rc-card" style="border-top: 4px solid #7c3aed;">
                <div class="rc-card-head" style="color:#7c3aed;">
                    <i class="bi bi-x-ray"></i> Bulletin Imagerie Médicale
                    <?php if ($bulletinImgSigne): ?>
                    <span style="float:right;font-size:.7rem;font-weight:800;padding:3px 10px;border-radius:20px;background:#f0fdf4;color:#15803d;">
                        Enregistré
                    </span>
                    <?php else: ?>
                    <span style="float:right;font-size:.7rem;font-weight:800;padding:3px 10px;border-radius:20px;background:#fff7ed;color:#c2410c;">
                        À signer
                    </span>
                    <?php endif; ?>
                </div>
                <div class="rc-card-body">
                    <?php if (!empty($examensImagerie)): ?>
                    <ul class="list-unstyled mb-3" style="font-size:.85rem;">
                        <?php foreach ($examensImagerie as $examen): ?>
                        <li style="padding:5px 0;border-bottom:1px solid #f1f5f9;display:flex;gap:8px;align-items:flex-start;">
                            <i class="bi bi-radioactive" style="color:#7c3aed;margin-top:2px;flex-shrink:0;"></i>
                            <span>
                                <strong><?= htmlspecialchars($examen['noms_examens'] ?? 'Examens demandés') ?></strong>
                                <br><span style="color:#64748b;font-size:.78rem;">
                                    <?= date('d/m/Y', strtotime($examen['date_demande'])) ?>
                                    — <em><?= htmlspecialchars($examen['statut'] ?? 'En attente') ?></em>
                                </span>
                            </span>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                    <?php endif; ?>

                    <?php if ($bulletinImgSigne): ?>
                    <div class="d-flex gap-2">
                        <a href="<?= BASE_URL ?>formulaire/voir-bulletin-examens/<?= (int)$bulletinImgSigne['id'] ?>"
                           target="_blank"
                           class="btn btn-sm btn-outline-success rounded-pill flex-grow-1">
                            <i class="bi bi-check-circle-fill me-1"></i> Voir le Bulletin signé
                        </a>
                        <a href="<?= BASE_URL ?>formulaire/creer/bulletin-examens/<?= $patient['id'] ?>?type=imagerie&consultation_id=<?= $consultation['id'] ?>"
                           target="_blank"
                           class="btn btn-sm btn-outline-secondary rounded-pill px-3"
                           title="Créer un nouveau bulletin">
                            <i class="bi bi-plus"></i>
                        </a>
                    </div>
                    <?php else: ?>
                    <a href="<?= BASE_URL ?>formulaire/creer/bulletin-examens/<?= $patient['id'] ?>?type=imagerie&consultation_id=<?= $consultation['id'] ?>"
                       target="_blank"
                       class="btn btn-sm w-100 rounded-pill fw-bold text-white"
                       style="background:linear-gradient(135deg,#7c3aed,#6d28d9);">
                        <i class="bi bi-pen-fill me-1"></i> Imprimer &amp; Signer le Bulletin Imagerie
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- ORIENTATION PATIENT -->
            <div class="rc-card">
                <div class="rc-card-head" style="color:#475569;">
                    <i class="bi bi-signpost-split-fill"></i> Orientation Patient
                </div>
                <div class="rc-card-body">

                    <!-- Score bar -->
                    <?php
                    $scoreColor = $score >= 7 ? '#dc2626' : ($score >= 4 ? '#f59e0b' : '#10b981');
                    $scorePct   = min(100, $score * 10);
                    ?>
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:4px;">
                        <span style="font-size:.72rem;font-weight:700;color:#64748b;text-transform:uppercase;">Score de risque</span>
                        <span style="font-size:1.1rem;font-weight:800;color:<?= $scoreColor ?>;"><?= $score ?>/10</span>
                    </div>
                    <div class="rc-score-bar">
                        <div class="rc-score-fill" style="width:<?= $scorePct ?>%;background:<?= $scoreColor ?>;"></div>
                    </div>

                    <!-- Orientation box -->
                    <div class="rc-orientation" style="background:<?= $ori['bg'] ?>;border-color:<?= $ori['color'] ?>20;margin-top:14px;">
                        <i class="bi <?= $ori['icon'] ?> rc-ori-icon" style="color:<?= $ori['color'] ?>;"></i>
                        <div>
                            <p class="rc-ori-title" style="color:<?= $ori['color'] ?>;">
                                <?= $ori['label'] ?>
                            </p>
                            <p class="rc-ori-sub" style="color:<?= $ori['color'] ?>;">
                                <?= $analyse['recommandation']['message'] ?? '' ?>
                            </p>
                        </div>
                    </div>

                    <!-- Boutons décision -->
                    <div class="rc-action-btns no-print mt-3">
                        <button class="rc-btn-action rc-btn-urgence"
                                onclick="prendreDecision('hospitalisation_urgente')">
                            <i class="bi bi-exclamation-octagon-fill"></i>
                            Hospitalisation d'Urgence
                        </button>
                        <button class="rc-btn-action rc-btn-prog"
                                onclick="prendreDecision('hospitalisation_programmee')">
                            <i class="bi bi-calendar-plus"></i>
                            Programmer l'Hospitalisation
                        </button>
                        <button class="rc-btn-action rc-btn-ambu"
                                onclick="prendreDecision('suivi_ambulatoire')">
                            <i class="bi bi-person-walking"></i>
                            Suivi Ambulatoire
                        </button>
                    </div>
                </div>
            </div>

        </div><!-- /col droite -->
    </div><!-- /row -->
</div><!-- /rc-page -->

<!-- MODAL DÉCISION -->
<div class="modal fade" id="modalDecision" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-xl rounded-4" style="overflow:hidden;">
            <div class="modal-header border-0 px-4 pt-4 pb-2">
                <h5 class="fw-bold mb-0" id="modalDecisionTitle">Confirmer l'orientation</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body px-4 pb-4">
                <div id="modalDecisionMsg" class="alert alert-light border-0 rounded-3 small mb-4 py-3"></div>

                <!-- Sélecteur service destination (hospitalisations uniquement) -->
                <div id="serviceDestDiv" style="display:none;" class="mb-3">
                    <label class="form-label small fw-bold text-uppercase text-danger">
                        <i class="bi bi-hospital me-1"></i>
                        Service de destination <span class="text-danger">*</span>
                    </label>
                    <select id="serviceDestHosp" class="form-select border-2 rounded-3">
                        <option value="">-- Choisir un service --</option>
                        <?php foreach (($servicesCliniques ?? []) as $s): ?>
                        <option value="<?= (int)$s['id'] ?>"><?= htmlspecialchars($s['nom_service']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <div class="form-text text-muted">
                        <i class="bi bi-info-circle me-1"></i>
                        Le patient sera orienté vers ce service après hospitalisation.
                    </div>
                </div>

                <label class="form-label small fw-bold text-muted text-uppercase">
                    Justification médicale
                </label>
                <textarea id="justificationDecision"
                          class="form-control border-2 rounded-3" rows="3"
                          placeholder="Saisir la raison médicale (optionnel)..."></textarea>
                <div class="d-flex gap-2 mt-4">
                    <button type="button" class="btn btn-light rounded-pill px-4 flex-grow-1"
                            data-bs-dismiss="modal">Annuler</button>
                    <button type="button" class="btn btn-primary rounded-pill px-4 flex-grow-1 fw-bold"
                            onclick="confirmerDecision()">
                        <i class="bi bi-check2 me-1"></i> Confirmer
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let decisionEnCours = '';
const decisionLabels = {
    'hospitalisation_urgente':    '🔴 Hospitalisation d\'Urgence',
    'hospitalisation_programmee': '🟡 Programmer l\'Hospitalisation',
    'suivi_ambulatoire':          '🟢 Suivi Ambulatoire',
};

const decisionsHospitalisation = ['hospitalisation_urgente', 'hospitalisation_programmee', 'hospitalisation_recommandee'];

function prendreDecision(decision) {
    decisionEnCours = decision;
    document.getElementById('modalDecisionMsg').innerHTML =
        `Action sélectionnée : <strong>${decisionLabels[decision] || decision}</strong>`;

    // Afficher le sélecteur de service uniquement pour les hospitalisations
    const isHosp = decisionsHospitalisation.includes(decision);
    const serviceDestDiv = document.getElementById('serviceDestDiv');
    serviceDestDiv.style.display = isHosp ? 'block' : 'none';
    // Réinitialiser la sélection
    if (isHosp) document.getElementById('serviceDestHosp').value = '';

    new bootstrap.Modal(document.getElementById('modalDecision')).show();
}

function confirmerDecision() {
    const motif     = document.getElementById('justificationDecision').value;
    const serviceId = document.getElementById('serviceDestHosp').value;
    const isHosp    = decisionsHospitalisation.includes(decisionEnCours);

    // Validation : service obligatoire pour hospitalisation
    if (isHosp && !serviceId) {
        document.getElementById('serviceDestHosp').classList.add('is-invalid');
        document.getElementById('serviceDestHosp').focus();
        return;
    }
    document.getElementById('serviceDestHosp').classList.remove('is-invalid');

    const btn = document.querySelector('#modalDecision .btn-primary');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Enregistrement...';

    const payload = {
        consultation_id: <?= (int)($consultation['id'] ?? 0) ?>,
        decision:        decisionEnCours,
        justification:   motif
    };
    if (isHosp && serviceId) payload.service_id = parseInt(serviceId, 10);

    fetch('<?= BASE_URL ?>consultation/decision-hospitalisation', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            bootstrap.Modal.getInstance(document.getElementById('modalDecision')).hide();
            window.location.href = '<?= BASE_URL ?>patients/dossier/<?= (int)($patient['id'] ?? 0) ?>?success=decision_saved';
        } else {
            alert('Erreur : ' + (data.message || 'Impossible d\'enregistrer la décision.'));
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-check2 me-1"></i> Confirmer';
        }
    })
    .catch(() => {
        alert('Erreur réseau.');
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-check2 me-1"></i> Confirmer';
    });
}
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>