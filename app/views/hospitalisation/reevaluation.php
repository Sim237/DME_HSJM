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
    :root {
        --rv-primary: #1e40af;
        --rv-light:   #eff6ff;
        --rv-border:  #bfdbfe;
        --rv-dark:    #1e3a8a;
    }

    body { background: #f1f5f9; }

    /* ── TOPBAR ─────────────────────────────────────────────── */
    .rv-topbar {
        background: linear-gradient(135deg, #1e3a8a 0%, #1e40af 60%, #2563eb 100%);
        color: white; padding: 14px 28px;
        display: flex; align-items: center; justify-content: space-between;
        position: sticky; top: 0; z-index: 1000;
        box-shadow: 0 4px 20px rgba(30,64,175,.35);
    }
    .rv-back-btn {
        width: 36px; height: 36px; border-radius: 10px;
        background: rgba(255,255,255,.15); color: white;
        display: flex; align-items: center; justify-content: center;
        text-decoration: none; transition: .15s; border: 1px solid rgba(255,255,255,.2);
        flex-shrink: 0;
    }
    .rv-back-btn:hover { background: rgba(255,255,255,.25); color: white; }
    .rv-patient-name  { font-size: 1rem; font-weight: 800; letter-spacing: .2px; }
    .rv-patient-sub   { font-size: .73rem; opacity: .8; margin-top: 1px; }
    .rv-badge-svc {
        background: rgba(255,255,255,.15); border: 1px solid rgba(255,255,255,.25);
        font-size: .68rem; font-weight: 700; padding: 4px 12px; border-radius: 20px;
        letter-spacing: .4px;
    }
    .rv-save-btn {
        background: white; color: var(--rv-primary);
        font-weight: 800; font-size: .86rem;
        padding: 10px 24px; border-radius: 12px; border: none;
        display: flex; align-items: center; gap: 8px;
        cursor: pointer; transition: .15s;
        box-shadow: 0 2px 8px rgba(0,0,0,.15);
    }
    .rv-save-btn:hover { transform: translateY(-1px); box-shadow: 0 4px 16px rgba(0,0,0,.2); }
    .rv-save-btn:disabled { opacity: .6; cursor: not-allowed; transform: none; }

    /* ── LAYOUT ─────────────────────────────────────────────── */
    .rv-content { padding: 24px 28px; max-width: 1600px; margin: 0 auto; }

    /* ── SECTION CARDS ──────────────────────────────────────── */
    .rv-card {
        background: white; border-radius: 16px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 2px 8px rgba(0,0,0,.04);
        margin-bottom: 18px; overflow: hidden;
    }
    .rv-card-header {
        padding: 14px 20px; background: #f8fafc;
        border-bottom: 1px solid #e2e8f0;
        display: flex; align-items: center; gap: 10px;
        cursor: default;
    }
    .rv-card-header.collapsible { cursor: pointer; user-select: none; }
    .rv-card-header.collapsible:hover { background: #f1f5f9; }
    .rv-sec-icon {
        width: 30px; height: 30px; border-radius: 9px;
        display: flex; align-items: center; justify-content: center;
        font-size: .95rem; flex-shrink: 0;
    }
    .rv-sec-title { font-size: .9rem; font-weight: 800; color: #1e293b; }
    .rv-tag {
        font-size: .65rem; font-weight: 800; padding: 3px 9px;
        border-radius: 20px; text-transform: uppercase; letter-spacing: .4px;
    }
    .rv-card-body { padding: 20px; }
    .rv-card-body.no-pad { padding: 0; }

    /* ── TEXTAREA ───────────────────────────────────────────── */
    .rv-textarea {
        width: 100%; border: 1px solid #e2e8f0; border-radius: 10px;
        padding: 11px 14px; font-size: .87rem; resize: vertical;
        font-family: inherit; transition: border-color .15s, box-shadow .15s;
        line-height: 1.5;
    }
    .rv-textarea:focus {
        border-color: var(--rv-primary); outline: none;
        box-shadow: 0 0 0 3px rgba(30,64,175,.1);
    }
    .rv-textarea::placeholder { color: #b0bec5; }

    /* ── EXAM TABS ──────────────────────────────────────────── */
    .exam-pills { display: flex; gap: 6px; flex-wrap: wrap; margin-bottom: 14px; }
    .exam-pill {
        font-size: .78rem; font-weight: 700; padding: 6px 14px; border-radius: 8px;
        border: 1px solid #e2e8f0; background: white; color: #475569;
        cursor: pointer; transition: .15s;
    }
    .exam-pill:hover  { background: var(--rv-light); color: var(--rv-primary); border-color: var(--rv-border); }
    .exam-pill.active { background: var(--rv-primary); color: white; border-color: var(--rv-primary); }
    .btn-normal {
        font-size: .72rem; font-weight: 700; padding: 4px 12px; border-radius: 6px;
        background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0;
        cursor: pointer; transition: .15s; margin-bottom: 8px;
    }
    .btn-normal:hover { background: #dcfce7; }

    /* ── ÉVOLUTION GLOBALE ──────────────────────────────────── */
    .evol-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; }
    @media(max-width:576px) { .evol-grid { grid-template-columns: repeat(2,1fr); } }

    .evol-card {
        border-radius: 14px; padding: 16px 12px; cursor: pointer;
        border: 2px solid transparent; text-align: center;
        transition: all .15s; position: relative; overflow: hidden;
    }
    .evol-card:hover { transform: translateY(-2px); }
    .evol-card input[type="radio"] { position: absolute; opacity: 0; width: 0; height: 0; }

    .ev-favorable       { background: #f0fdf4; border-color: #bbf7d0; }
    .ev-amelioration    { background: #f0fdfa; border-color: #99f6e4; }
    .ev-statuo          { background: #fffbeb; border-color: #fde68a; }
    .ev-nonfavorable    { background: #fff7ed; border-color: #fed7aa; }
    .ev-aggravation     { background: #fff5f5; border-color: #fca5a5; }
    .ev-critique        { background: #fff1f2; border-color: #fda4af; animation: pulse-crit 2s infinite; }

    .evol-card.selected.ev-favorable    { background: #dcfce7; border-color: #16a34a; box-shadow: 0 4px 16px rgba(22,163,74,.22); }
    .evol-card.selected.ev-amelioration { background: #ccfbf1; border-color: #0d9488; box-shadow: 0 4px 16px rgba(13,148,136,.22); }
    .evol-card.selected.ev-statuo       { background: #fef3c7; border-color: #d97706; box-shadow: 0 4px 16px rgba(217,119,6,.22); }
    .evol-card.selected.ev-nonfavorable { background: #ffedd5; border-color: #ea580c; box-shadow: 0 4px 16px rgba(234,88,12,.22); }
    .evol-card.selected.ev-aggravation  { background: #fee2e2; border-color: #dc2626; box-shadow: 0 4px 16px rgba(220,38,38,.22); }
    .evol-card.selected.ev-critique     { background: #ffe4e6; border-color: #be123c; box-shadow: 0 4px 16px rgba(190,18,60,.3); }

    @keyframes pulse-crit {
        0%,100% { box-shadow: 0 0 0 0 rgba(190,18,60,.2); }
        50%      { box-shadow: 0 0 0 8px rgba(190,18,60,.0); }
    }
    .evol-emoji { font-size: 1.8rem; display: block; margin-bottom: 6px; line-height: 1; }
    .evol-label { font-size: .75rem; font-weight: 800; text-transform: uppercase; letter-spacing: .4px; }
    .evol-sub   { font-size: .67rem; color: #64748b; margin-top: 3px; }

    /* ── CIM-10 ─────────────────────────────────────────────── */
    .autocomplete-wrap { position: relative; }
    .autocomplete-list {
        position: absolute; z-index: 999; background: white;
        border: 1px solid #e2e8f0; border-radius: 12px;
        box-shadow: 0 8px 24px rgba(0,0,0,.12);
        max-height: 220px; overflow-y: auto; width: 100%; top: calc(100% + 4px);
    }
    .ac-item {
        padding: 9px 14px; cursor: pointer; font-size: .84rem;
        border-bottom: 1px solid #f1f5f9; transition: background .1s;
    }
    .ac-item:hover { background: var(--rv-light); }
    .ac-item:last-child { border-bottom: none; }
    .cim-code { font-weight: 800; color: var(--rv-primary); font-size: .78rem; margin-right: 6px; }

    /* ── MEDICATION TABLE ───────────────────────────────────── */
    .med-table { width: 100%; border-collapse: collapse; }
    .med-table thead th {
        background: #f8fafc; color: #64748b;
        font-size: .69rem; font-weight: 700; text-transform: uppercase; letter-spacing: .7px;
        padding: 10px 10px; border-bottom: 1px solid #e2e8f0;
    }
    .med-table tbody td { padding: 7px 7px; vertical-align: middle; border-bottom: 1px solid #f1f5f9; }
    .med-table tbody tr:last-child td { border-bottom: none; }
    .med-input {
        border: 1px solid #e2e8f0; border-radius: 8px;
        padding: 6px 9px; font-size: .82rem; width: 100%;
        font-family: inherit; transition: .15s;
    }
    .med-input:focus { border-color: var(--rv-primary); outline: none; box-shadow: 0 0 0 2px rgba(30,64,175,.1); }
    .btn-del { width: 28px; height: 28px; border-radius: 8px; border: none; background: #fee2e2; color: #dc2626; display: flex; align-items: center; justify-content: center; cursor: pointer; font-size: .85rem; }
    .btn-del:hover { background: #fca5a5; }
    .btn-add-row {
        font-size: .78rem; font-weight: 700; padding: 7px 16px; border-radius: 9px;
        background: var(--rv-light); color: var(--rv-primary);
        border: 1px solid var(--rv-border); cursor: pointer; transition: .15s;
        display: inline-flex; align-items: center; gap: 5px;
    }
    .btn-add-row:hover { background: #dbeafe; }

    /* ── BILANS (labo/imagerie sub-blocks) ─────────────────── */
    .rv-bilan-block { border-radius: 10px; padding: 16px; margin-bottom: 16px; }
    .rv-bilan-block.labo    { background: #f8fafc; border: 1px solid #e2e8f0; }
    .rv-bilan-block.imagerie{ background: #f0f7ff; border: 1px solid #93c5fd; }

    /* ── INFO PANEL (right column) ──────────────────────────── */
    .info-panel {
        background: white; border-radius: 16px;
        border: 1px solid #e2e8f0; overflow: hidden; margin-bottom: 16px;
        box-shadow: 0 2px 8px rgba(0,0,0,.04);
    }
    .info-panel-hd {
        padding: 12px 16px;
        background: linear-gradient(135deg, #1e3a8a, #1e40af);
        color: white; font-size: .86rem; font-weight: 800;
    }
    .info-panel-hd.teal { background: linear-gradient(135deg, #0f766e, #0d9488); }
    .info-panel-hd.slate { background: linear-gradient(135deg, #334155, #475569); }
    .info-row {
        display: flex; justify-content: space-between; align-items: center;
        padding: 7px 16px; border-bottom: 1px solid #f1f5f9;
        font-size: .82rem;
    }
    .info-row:last-child { border-bottom: none; }

    /* ── HISTORY TIMELINE ───────────────────────────────────── */
    .history-entry {
        background: white; border-radius: 12px; border: 1px solid #e2e8f0;
        margin-bottom: 10px; overflow: hidden;
        box-shadow: 0 1px 4px rgba(0,0,0,.04); transition: .15s;
    }
    .history-entry:hover { border-color: var(--rv-border); }
    .he-header {
        padding: 11px 14px; display: flex; align-items: center;
        gap: 10px; cursor: pointer;
    }
    .he-body { padding: 12px 14px; border-top: 1px solid #f1f5f9; display: none; }
    .he-body.open { display: block; }
    .he-date  { font-size: .8rem; font-weight: 700; color: #1e293b; }
    .he-hour  { font-size: .72rem; color: #94a3b8; margin-left: 4px; }
    .he-doc   { font-size: .72rem; color: #64748b; }
    .evol-pill {
        font-size: .67rem; font-weight: 800; padding: 3px 9px;
        border-radius: 20px; text-transform: uppercase; white-space: nowrap;
    }
    .ep-FAVORABLE         { background: #dcfce7; color: #166534; }
    .ep-AMELIORATION_PARTIELLE { background: #ccfbf1; color: #134e4a; }
    .ep-STATUO_QUO        { background: #fef3c7; color: #92400e; }
    .ep-NON_FAVORABLE     { background: #ffedd5; color: #9a3412; }
    .ep-AGGRAVATION       { background: #fee2e2; color: #991b1b; }
    .ep-CRITIQUE          { background: #ffe4e6; color: #be123c; }

    .he-detail-lbl { font-size: .67rem; font-weight: 700; text-transform: uppercase; color: #94a3b8; letter-spacing: .5px; margin-bottom: 3px; }
    .he-detail-val { font-size: .82rem; color: #334155; }

    /* Complaint chips */
    .complaint-chip {
        font-size: .73rem; font-weight: 600; padding: 4px 11px; border-radius: 20px;
        background: #f1f5f9; border: 1px solid #e2e8f0; color: #475569;
        cursor: pointer; transition: .15s;
    }
    .complaint-chip:hover { background: var(--rv-light); color: var(--rv-primary); border-color: var(--rv-border); }
</style>

<?php
$evolCfg = [
    'FAVORABLE'            => ['emoji' => '✅', 'label' => 'Favorable',            'sub' => 'Évolution positive',      'cls' => 'ev-favorable'],
    'AMELIORATION_PARTIELLE'=> ['emoji'=> '📈', 'label' => 'Amélioration partielle','sub' => 'Tendance positive',       'cls' => 'ev-amelioration'],
    'STATUO_QUO'           => ['emoji' => '➡️',  'label' => 'Statuo quo clinique',  'sub' => 'Pas de changement',       'cls' => 'ev-statuo'],
    'NON_FAVORABLE'        => ['emoji' => '📉', 'label' => 'Non favorable',         'sub' => 'Tendance négative',       'cls' => 'ev-nonfavorable'],
    'AGGRAVATION'          => ['emoji' => '⚠️',  'label' => 'Aggravation',           'sub' => 'Détérioration clinique',  'cls' => 'ev-aggravation'],
    'CRITIQUE'             => ['emoji' => '🆘', 'label' => 'Critique',              'sub' => 'État critique urgent',    'cls' => 'ev-critique'],
];
$evolLabels = array_map(fn($c) => $c['label'], $evolCfg);
$hasHosp    = !empty($hospitalisation);
$pid        = (int)($patient['id'] ?? 0);
$hospId     = (int)($hospitalisation['id'] ?? 0);
?>

<!-- ── TOPBAR ──────────────────────────────────────────────── -->
<div class="rv-topbar no-print">
    <div class="d-flex align-items-center gap-3">
        <a href="<?= BASE_URL ?>dashboard" class="rv-back-btn" title="Retour au tableau de bord">
            <i class="bi bi-arrow-left"></i>
        </a>
        <div>
            <div class="rv-patient-name">
                <?= strtoupper(htmlspecialchars($patient['nom'] ?? '')) ?>
                <?= htmlspecialchars($patient['prenom'] ?? '') ?>
                <span style="opacity:.6; font-weight:400; font-size:.78rem; margin-left:6px;">
                    <?= htmlspecialchars($patient['dossier_numero'] ?? '') ?>
                </span>
            </div>
            <div class="rv-patient-sub">
                <i class="bi bi-clipboard2-pulse me-1"></i>Réévaluation médicale &nbsp;·&nbsp;
                <?php if ($hasHosp): ?>
                    <i class="bi bi-hospital me-1"></i><?= htmlspecialchars($hospitalisation['nom_service'] ?? '') ?>
                    &nbsp;·&nbsp; <?= htmlspecialchars($hospitalisation['nom_chambre'] ?? '') ?> /
                    Lit <?= htmlspecialchars($hospitalisation['nom_lit'] ?? '—') ?>
                <?php endif; ?>
                &nbsp;·&nbsp; <?= date('d/m/Y') ?>
            </div>
        </div>
        <?php if ($hasHosp): ?>
        <span class="rv-badge-svc d-none d-lg-inline">
            <i class="bi bi-hospital me-1"></i><?= htmlspecialchars($hospitalisation['nom_service'] ?? '') ?>
        </span>
        <?php endif; ?>
    </div>
    <div class="d-flex align-items-center gap-3">
        <div style="font-family:monospace; font-size:.88rem; background:rgba(255,255,255,.1); padding:6px 14px; border-radius:8px;">
            <span id="rvClock">--:--:--</span>
        </div>
        <button type="button" class="rv-save-btn" id="btnSave" onclick="soumettreReevaluation()">
            <i class="bi bi-check-lg"></i>
            <span id="btnSaveLabel"><?= !empty($reeval_to_edit) ? 'Enregistrer la modification' : 'Sauvegarder' ?></span>
        </button>
    </div>
</div>

<!-- Alertes succès / erreur -->
<div id="rvToast" style="display:none; position:fixed; bottom:24px; right:24px; z-index:9999;
     padding:14px 22px; border-radius:14px; font-weight:700; font-size:.88rem;
     box-shadow:0 8px 28px rgba(0,0,0,.18); min-width:260px;"></div>

<?php if (!empty($_SESSION['flash_error'])): ?>
<div style="margin:14px 28px; padding:12px 18px; background:#fef2f2; border-left:4px solid #dc2626; border-radius:8px; color:#991b1b; font-size:.88rem;">
    <i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($_SESSION['flash_error']) ?>
</div>
<?php unset($_SESSION['flash_error']); endif; ?>

<?php if (!empty($reeval_to_edit)): ?>
<!-- ── BANDEAU MODE ÉDITION ─────────────────────────────────────── -->
<div style="margin:14px 28px 0; padding:14px 20px;
            background:linear-gradient(135deg,#fef3c7,#fde68a);
            border-left:5px solid #f59e0b; border-radius:12px;
            display:flex; align-items:center; gap:14px; flex-wrap:wrap;">
    <i class="bi bi-pencil-square" style="font-size:1.6rem; color:#92400e;"></i>
    <div style="flex:1; min-width:240px;">
        <div style="font-weight:800; font-size:.95rem; color:#92400e;">
            Mode modification — Réévaluation #<?= (int)$reeval_to_edit['id'] ?>
        </div>
        <div style="font-size:.8rem; color:#78350f; margin-top:2px;">
            Faite le <?= date('d/m/Y à H:i', strtotime($reeval_to_edit['date_reevaluation'].' '.$reeval_to_edit['heure_reevaluation'])) ?>.
            Vos modifications remplaceront le contenu actuel.
            <?php if (!empty($reeval_to_edit['prescription_id'])): ?>
            <strong>L'ordonnance liée sera remise en attente</strong> en pharmacie si elle n'a pas encore été délivrée.
            <?php endif; ?>
        </div>
    </div>
    <a href="<?= BASE_URL ?>hospitalisation/reevaluation/<?= (int)$pid ?>"
       class="btn btn-sm btn-outline-secondary rounded-pill px-3 fw-semibold">
        <i class="bi bi-x-lg me-1"></i>Annuler la modification
    </a>
</div>
<?php endif; ?>

<div class="rv-content">

    <form id="formReeval" class="consultation-form" autocomplete="off">
    <input type="hidden" name="patient_id"         value="<?= $pid ?>">
    <input type="hidden" name="hospitalisation_id"  value="<?= $hospId ?>">
    <?php if (!empty($reeval_to_edit)): ?>
    <input type="hidden" name="_edit_id" id="reevalEditId" value="<?= (int)$reeval_to_edit['id'] ?>">
    <?php endif; ?>

    <div class="row g-4">

        <!-- ══════════════════════════════════════════════════
             COLONNE PRINCIPALE (8/12)
        ══════════════════════════════════════════════════ -->
        <div class="col-xl-8">

            <!-- 1. PLAINTES DU JOUR ──────────────────────── -->
            <div class="rv-card">
                <div class="rv-card-header">
                    <div class="rv-sec-icon" style="background:#fee2e2; color:#dc2626;">
                        <i class="bi bi-chat-right-text-fill"></i>
                    </div>
                    <span class="rv-sec-title">Plaintes du jour</span>
                    <span class="rv-tag ms-auto" style="background:#fee2e2; color:#dc2626;">Requis</span>
                </div>
                <div class="rv-card-body">
                    <div class="d-flex flex-wrap gap-2 mb-3">
                        <?php foreach (['Douleur','Fièvre','Toux','Dyspnée','Nausées','Vomissements','Diarrhée','Vertiges','Céphalées','Palpitations','Insomnie','Œdèmes','Prurit','Asthénie'] as $c): ?>
                        <button type="button" class="complaint-chip" onclick="addChip('plaintes_jour','<?= $c ?>')">
                            + <?= $c ?>
                        </button>
                        <?php endforeach; ?>
                    </div>
                    <textarea name="plaintes_jour" class="rv-textarea" rows="3"
                              placeholder="Décrivez les plaintes et symptômes du patient aujourd'hui..."></textarea>
                </div>
            </div>

            <!-- 2. HISTOIRE DE LA MALADIE (opt.) ────────── -->
            <div class="rv-card">
                <div class="rv-card-header collapsible" onclick="toggleBody('histoire-body','histoire-chevron')">
                    <div class="rv-sec-icon" style="background:#e0f2fe; color:#0369a1;">
                        <i class="bi bi-journal-text"></i>
                    </div>
                    <span class="rv-sec-title">Histoire de la maladie</span>
                    <span class="rv-tag ms-2" style="background:#e0f2fe; color:#0369a1;">Optionnel</span>
                    <i class="bi bi-chevron-down ms-auto text-muted" id="histoire-chevron" style="transition:.2s;"></i>
                </div>
                <div class="rv-card-body" id="histoire-body" style="display:none;">
                    <textarea name="histoire_maladie" class="rv-textarea" rows="4"
                              placeholder="Résumé de l'évolution depuis l'admission ou depuis la dernière réévaluation. Principaux événements cliniques, examens réalisés, traitements modifiés..."></textarea>
                </div>
            </div>

            <!-- 3. EXAMEN PHYSIQUE ──────────────────────── -->
            <div class="rv-card">
                <div class="rv-card-header">
                    <div class="rv-sec-icon" style="background:#f3e8ff; color:#7c3aed;">
                        <i class="bi bi-heart-pulse-fill"></i>
                    </div>
                    <span class="rv-sec-title">Examen physique</span>
                </div>
                <div class="rv-card-body">
                    <div class="exam-pills" id="examPills">
                        <?php foreach ([
                            ['general',          'Général',         'bi-person-fill'],
                            ['cardiovasculaire',  'Cardio-vasculaire','bi-heart-fill'],
                            ['respiratoire',      'Respiratoire',    'bi-lungs-fill'],
                            ['digestif',          'Digestif',        'bi-capsule-pill'],
                            ['neurologique',      'Neurologique',    'bi-cpu'],
                            ['osteoarticulaire',  'Ostéo-articulaire','bi-person-arms-up'],
                            ['autres',            'Autres',          'bi-three-dots'],
                        ] as [$sys, $lbl, $ico]): ?>
                        <button type="button"
                                class="exam-pill <?= $sys === 'general' ? 'active' : '' ?>"
                                onclick="switchExam(this, 'ep-<?= $sys ?>')">
                            <i class="bi <?= $ico ?> me-1"></i><?= $lbl ?>
                        </button>
                        <?php endforeach; ?>
                    </div>

                    <?php foreach ([
                        ['general',         'État général, conscience, teint, hydratation, temp. cutanée, conjonctives...'],
                        ['cardiovasculaire', 'Auscultation cardiaque, rythme, souffles, pouls, TA, TRC, signes d\'insuffisance cardiaque...'],
                        ['respiratoire',     'Murmure vésiculaire, râles crépitants/sibilants, FR, SaO₂, signes de détresse respiratoire...'],
                        ['digestif',         'Abdomen souple/distendu, douleurs, hépato/splénomégalie, ascite, transit, vomissements...'],
                        ['neurologique',     'Conscience (Glasgow), orientation, réflexes, ROT, déficit moteur/sensitif, paires crâniennes...'],
                        ['osteoarticulaire', 'Mobilité articulaire, douleurs musculo-squelettiques, inflammation locale, déformation...'],
                        ['autres',           'Peau et phanères, aires ganglionnaires, examen ORL, génito-urinaire, remarques diverses...'],
                    ] as [$sys, $ph]): ?>
                    <div id="ep-<?= $sys ?>" class="exam-panel" <?= $sys !== 'general' ? 'style="display:none;"' : '' ?>>
                        <div class="d-flex justify-content-end mb-2">
                            <button type="button" class="btn-normal" onclick="setNormal('<?= $sys ?>')">
                                <i class="bi bi-check2-circle me-1"></i>Normal
                            </button>
                        </div>
                        <textarea name="examen_<?= $sys ?>" id="ta-<?= $sys ?>"
                                  class="rv-textarea" rows="4"
                                  placeholder="<?= htmlspecialchars($ph) ?>"></textarea>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- 4. ÉVOLUTION GLOBALE ────────────────────── -->
            <div class="rv-card" style="border-left: 4px solid var(--rv-primary);">
                <div class="rv-card-header">
                    <div class="rv-sec-icon" style="background:var(--rv-light); color:var(--rv-primary);">
                        <i class="bi bi-graph-up-arrow"></i>
                    </div>
                    <span class="rv-sec-title">Évolution globale de l'état du patient</span>
                    <span class="rv-tag ms-auto" style="background:var(--rv-light); color:var(--rv-primary);">Requis</span>
                </div>
                <div class="rv-card-body">
                    <div class="evol-grid" id="evolGrid">
                        <?php foreach ($evolCfg as $val => $cfg): ?>
                        <label class="evol-card <?= $cfg['cls'] ?>" id="evol-<?= $val ?>"
                               onclick="selectEvol('<?= $val ?>')">
                            <input type="radio" name="evolution_globale" value="<?= $val ?>">
                            <span class="evol-emoji"><?= $cfg['emoji'] ?></span>
                            <div class="evol-label"><?= $cfg['label'] ?></div>
                            <div class="evol-sub"><?= $cfg['sub'] ?></div>
                        </label>
                        <?php endforeach; ?>
                    </div>
                    <div class="mt-3">
                        <label class="form-label small fw-bold text-muted" style="font-size:.72rem; text-transform:uppercase; letter-spacing:.5px;">
                            Note complémentaire sur l'évolution
                        </label>
                        <textarea name="note_evolution" class="rv-textarea" rows="2"
                                  placeholder="Précisez l'évolution, les changements observés depuis la dernière ronde..."></textarea>
                    </div>
                </div>
            </div>

            <!-- 5. DIAGNOSTIC DU JOUR (CIM-10) ─────────── -->
            <div class="rv-card">
                <div class="rv-card-header">
                    <div class="rv-sec-icon" style="background:#dbeafe; color:#1d4ed8;">
                        <i class="bi bi-clipboard2-check-fill"></i>
                    </div>
                    <span class="rv-sec-title">Diagnostic du jour (CIM-10)</span>
                </div>
                <div class="rv-card-body">
                    <input type="hidden" name="code_cim10" id="hiddenCim10">
                    <div class="autocomplete-wrap">
                        <input type="text" id="cim10Input" name="diagnostic_jour"
                               class="rv-textarea" style="resize:none; height:auto;"
                               placeholder="Tapez le diagnostic ou un code CIM-10 (ex: J18, K29.7...)..."
                               oninput="doCim10Search(this.value)" autocomplete="off"
                               data-no-dictee="1">
                        <div id="cim10List" class="autocomplete-list" style="display:none;"></div>
                    </div>
                    <div id="cim10Tag" class="mt-2" style="display:none;">
                        <span id="cim10TagText" class="badge rounded-pill px-3 py-2"
                              style="background:#dbeafe; color:#1d4ed8; font-size:.8rem;"></span>
                        <button type="button" class="btn btn-sm text-danger ms-1" onclick="clearCim10()">
                            <i class="bi bi-x-circle"></i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- 6. CONDUITE À TENIR ─────────────────────── -->
            <div class="rv-card">
                <div class="rv-card-header">
                    <div class="rv-sec-icon" style="background:#f0fdf4; color:#16a34a;">
                        <i class="bi bi-list-check"></i>
                    </div>
                    <span class="rv-sec-title">Conduite à tenir / Plan thérapeutique</span>
                </div>
                <div class="rv-card-body">
                    <textarea name="conduite_tenir" class="rv-textarea" rows="3"
                              placeholder="Décisions thérapeutiques, soins à surveiller, prochaine étape, décision de sortie ou transfert..."></textarea>
                </div>
            </div>

            <!-- 7. TRAITEMENT NON MÉDICAMENTEUX ───────────── -->
            <div class="rv-card">
                <div class="rv-card-header">
                    <div class="rv-sec-icon" style="background:#fef9c3; color:#854d0e;">
                        <i class="bi bi-heart-pulse"></i>
                    </div>
                    <span class="rv-sec-title">Traitement non médicamenteux</span>
                    <span class="rv-tag ms-auto" style="background:#fef9c3; color:#854d0e;">Mesures hygiéno-diététiques & soins</span>
                </div>
                <div class="rv-card-body">
                    <!-- Chips de sélection rapide -->
                    <div class="mb-3">
                        <p class="small text-muted mb-2 fw-semibold text-uppercase" style="font-size:.7rem; letter-spacing:.5px;">Sélection rapide (cliquer pour ajouter)</p>
                        <div class="d-flex flex-wrap gap-2">
                            <?php foreach ([
                                ['🛏️', 'Repos au lit'],
                                ['🚫', 'Repos strict (alitement total)'],
                                ['🚿', 'Nursing renforcé'],
                                ['💧', 'Hydratation orale encouragée'],
                                ['🫁', 'Oxygénothérapie aux lunettes'],
                                ['😷', 'Oxygénothérapie au masque'],
                                ['🥗', 'Régime sans sel'],
                                ['🍽️', 'Régime diabétique'],
                                ['⚖️', 'Régime hypocalorique'],
                                ['🚫🍽️', 'Diète absolue (NPO)'],
                                ['🦾', 'Kinésithérapie / Rééducation'],
                                ['🚶', 'Mobilisation progressive'],
                                ['🪑', 'Position demi-assise'],
                                ['🩹', 'Pansement quotidien'],
                                ['📏', 'Surveillance paramètres vitaux rapprochée'],
                                ['🔌', 'Scope cardiaque'],
                                ['🧲', 'Contention élastique'],
                                ['💉', 'Voie veineuse périphérique maintenue'],
                                ['🧴', 'Soins de bouche'],
                                ['🛡️', 'Prévention escarres'],
                            ] as [$ico, $txt]): ?>
                            <button type="button"
                                    onclick="addTNM('<?= addslashes($txt) ?>')"
                                    style="font-size:.73rem; padding:4px 10px; border-radius:20px; border:1px solid #d97706; background:#fefce8; color:#92400e; cursor:pointer; transition:.15s; white-space:nowrap;"
                                    onmouseover="this.style.background='#fef08a'" onmouseout="this.style.background='#fefce8'">
                                <?= $ico ?> <?= $txt ?>
                            </button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <textarea name="traitement_non_medicamenteux" id="taNonMed" class="rv-textarea" rows="4"
                              placeholder="Ex : Repos au lit, régime sans sel, kinésithérapie respiratoire, nursing renforcé, prévention d'escarres, oxygénothérapie..."></textarea>
                </div>
            </div>

            <!-- 8. PRESCRIPTION DU JOUR ─────────────────── -->
            <div class="rv-card">
                <div class="rv-card-header">
                    <div class="rv-sec-icon" style="background:#fef2f2; color:#dc2626;">
                        <i class="bi bi-capsule-pill"></i>
                    </div>
                    <span class="rv-sec-title">Traitement du jour (Prescription)</span>
                    <span class="rv-tag ms-auto" style="background:#fef2f2; color:#dc2626;">Ordonnance pharmacie</span>
                </div>
                <div class="rv-card-body no-pad">
                    <div class="table-responsive">
                        <table class="med-table">
                            <thead>
                                <tr>
                                    <th style="width:26%; padding-left:16px;">Médicament</th>
                                    <th style="width:18%">Posologie</th>
                                    <th style="width:13%">Voie</th>
                                    <th style="width:17%">Fréquence</th>
                                    <th style="width:14%">Durée</th>
                                    <th style="width:7%">Qté</th>
                                    <th style="width:5%"></th>
                                </tr>
                            </thead>
                            <tbody id="medTbody"></tbody>
                        </table>
                    </div>
                    <div style="padding:14px 16px; border-top:1px solid #f1f5f9;">
                        <button type="button" class="btn-add-row" onclick="addMedRow()">
                            <i class="bi bi-plus-lg"></i>Ajouter un médicament
                        </button>
                    </div>
                </div>
            </div>

            <!-- 8. DEMANDES DE BILANS ───────────────────── -->
            <div class="rv-card">
                <div class="rv-card-header">
                    <div class="rv-sec-icon" style="background:#e0f2fe; color:#0369a1;">
                        <i class="bi bi-flask-fill"></i>
                    </div>
                    <span class="rv-sec-title">Demandes de bilans</span>
                    <span class="rv-tag ms-auto" style="background:#e0f2fe; color:#0369a1;">Labo & Imagerie</span>
                </div>
                <div class="rv-card-body">

                    <!-- ── Bloc Laboratoire ── -->
                    <div class="rv-bilan-block labo">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="mb-0 fw-bold"><i class="bi bi-flask me-2 text-info"></i>Examens de Laboratoire</h6>
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#rvModalExamen">
                                    <i class="bi bi-plus-lg me-1"></i>Ajouter un examen
                                </button>
                                <button type="button" class="btn btn-success btn-sm fw-bold" id="btnRvEnvLabo" onclick="rvEnvoyerLabo()" style="display:none;">
                                    <i class="bi bi-send-fill me-1"></i>Envoyer au Labo
                                </button>
                            </div>
                        </div>
                        <div id="rvAlertesLabo"></div>
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm mb-0 bg-white" id="rvTableLabo">
                                <thead class="table-light">
                                    <tr><th>Examen</th><th>Catégorie</th><th>Prélèvement</th><th>Délai</th><th>Urgence</th><th></th></tr>
                                </thead>
                                <tbody id="rvListeLabo"></tbody>
                            </table>
                            <div id="rvEmptyLabo" class="text-center text-muted py-3 small">
                                <i class="bi bi-flask d-block mb-1 fs-4 opacity-50"></i>
                                Aucun examen ajouté. Cliquez sur "Ajouter un examen".
                            </div>
                        </div>
                    </div>

                    <!-- ── Bloc Imagerie ── -->
                    <div class="rv-bilan-block imagerie">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="mb-0 fw-bold text-primary"><i class="bi bi-image me-2"></i>Imagerie / Radiologie</h6>
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#rvModalImagerie">
                                    <i class="bi bi-plus-lg me-1"></i>Demande imagerie
                                </button>
                                <button type="button" class="btn btn-danger btn-sm fw-bold" id="btnRvEnvRadio" onclick="rvEnvoyerRadio()" style="display:none;">
                                    <i class="bi bi-send-fill me-1"></i>Envoyer en Radiologie
                                </button>
                            </div>
                        </div>
                        <div id="rvAlertesRadio"></div>
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm mb-0 bg-white" id="rvTableImagerie">
                                <thead class="table-primary">
                                    <tr><th>Modalité</th><th>Partie du corps</th><th>Urgence</th><th>Indication</th><th></th></tr>
                                </thead>
                                <tbody id="rvListeImagerie"></tbody>
                            </table>
                            <div id="rvEmptyImagerie" class="text-center text-muted py-3 small">
                                <i class="bi bi-image d-block mb-1 fs-4 opacity-50"></i>
                                Aucune demande. Cliquez sur "Demande imagerie".
                            </div>
                        </div>
                    </div>

                </div>
            </div>

        </div>

        <!-- ══════════════════════════════════════════════════
             COLONNE LATÉRALE (4/12)
        ══════════════════════════════════════════════════ -->
        <div class="col-xl-4">

            <!-- Infos patient & hospitalisation -->
            <div class="info-panel">
                <div class="info-panel-hd">
                    <i class="bi bi-person-fill me-2"></i>Informations patient
                </div>
                <?php
                $ageStr = ($age !== 'N/A') ? $age . ' ans' : 'N/A';
                $sexeStr = match($patient['sexe'] ?? '') { 'M' => 'Masculin', 'F' => 'Féminin', default => '—' };
                ?>
                <div class="info-row"><span class="text-muted">Âge</span><strong><?= $ageStr ?></strong></div>
                <div class="info-row"><span class="text-muted">Sexe</span><strong><?= $sexeStr ?></strong></div>
                <div class="info-row">
                    <span class="text-muted">Dossier</span>
                    <span class="font-monospace" style="font-size:.8rem;"><?= htmlspecialchars($patient['dossier_numero'] ?? '—') ?></span>
                </div>
                <?php if ($hasHosp): ?>
                <div class="info-row"><span class="text-muted">Service</span><strong><?= htmlspecialchars($hospitalisation['nom_service'] ?? '—') ?></strong></div>
                <div class="info-row">
                    <span class="text-muted">Chambre / Lit</span>
                    <strong><?= htmlspecialchars($hospitalisation['nom_chambre'] ?? '') ?> / <?= htmlspecialchars($hospitalisation['nom_lit'] ?? '—') ?></strong>
                </div>
                <div class="info-row">
                    <span class="text-muted">Admis le</span>
                    <strong><?= date('d/m/Y', strtotime($hospitalisation['date_admission'])) ?></strong>
                </div>
                <?php
                $nbJours = max(0, (int)round((time() - strtotime($hospitalisation['date_admission'])) / 86400));
                $jourCls = $nbJours > 10 ? 'color:#dc2626' : ($nbJours > 5 ? 'color:#d97706' : 'color:#16a34a');
                ?>
                <div class="info-row">
                    <span class="text-muted">Durée hospit.</span>
                    <strong style="<?= $jourCls ?>"><?= $nbJours ?> jour<?= $nbJours > 1 ? 's' : '' ?></strong>
                </div>
                <?php endif; ?>
                <div style="padding:12px 16px; display:flex; gap:8px;">
                    <a href="<?= BASE_URL ?>patients/dossier/<?= $pid ?>"
                       class="btn btn-sm btn-outline-primary rounded-pill flex-grow-1 fw-bold" style="font-size:.77rem;">
                        <i class="bi bi-folder2-open me-1"></i>Dossier complet
                    </a>
                    <?php if ($hasHosp): ?>
                    <a href="<?= BASE_URL ?>hospitalisation/suivi/<?= $pid ?>"
                       class="btn btn-sm btn-outline-secondary rounded-pill fw-bold" style="font-size:.77rem;" title="Fiche de suivi">
                        <i class="bi bi-activity"></i>
                    </a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Dernières constantes -->
            <?php if (!empty($dernieres_constantes)): $c = $dernieres_constantes; ?>
            <div class="info-panel">
                <div class="info-panel-hd teal">
                    <i class="bi bi-thermometer-half me-2"></i>Dernières constantes vitales
                </div>
                <?php if (!empty($c['temperature'])): ?>
                <div class="info-row">
                    <span class="text-muted"><i class="bi bi-thermometer me-1"></i>Temp.</span>
                    <strong style="color:<?= $c['temperature'] > 37.5 ? '#dc2626' : '#16a34a' ?>">
                        <?= $c['temperature'] ?>°C <?= $c['temperature'] > 37.5 ? '🔴' : '🟢' ?>
                    </strong>
                </div>
                <?php endif; ?>
                <?php if (!empty($c['pression_arterielle_systolique'])): ?>
                <div class="info-row">
                    <span class="text-muted"><i class="bi bi-heart-pulse me-1"></i>TA</span>
                    <strong><?= $c['pression_arterielle_systolique'] ?>/<?= $c['pression_arterielle_diastolique'] ?> mmHg</strong>
                </div>
                <?php endif; ?>
                <?php if (!empty($c['frequence_cardiaque'])): ?>
                <div class="info-row">
                    <span class="text-muted"><i class="bi bi-activity me-1"></i>FC</span>
                    <strong><?= $c['frequence_cardiaque'] ?> bpm</strong>
                </div>
                <?php endif; ?>
                <?php if (!empty($c['saturation_oxygene'])): ?>
                <div class="info-row">
                    <span class="text-muted">SpO₂</span>
                    <strong style="color:<?= $c['saturation_oxygene'] < 95 ? '#dc2626' : '#16a34a' ?>">
                        <?= $c['saturation_oxygene'] ?>%
                    </strong>
                </div>
                <?php endif; ?>
                <?php if (!empty($c['poids'])): ?>
                <div class="info-row"><span class="text-muted">Poids</span><strong><?= $c['poids'] ?> kg</strong></div>
                <?php endif; ?>
                <?php if (!empty($c['date_mesure'])): ?>
                <div style="padding:8px 16px 10px; font-size:.71rem; color:#94a3b8;">
                    <i class="bi bi-clock me-1"></i>Mesurées le <?= date('d/m/Y à H:i', strtotime($c['date_mesure'])) ?>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <!-- Historique des réévaluations -->
            <div class="info-panel">
                <div class="info-panel-hd slate" style="display:flex; align-items:center; justify-content:space-between;">
                    <span><i class="bi bi-clock-history me-2"></i>Historique des réévaluations</span>
                    <span style="background:rgba(255,255,255,.2); font-size:.72rem; padding:2px 9px; border-radius:12px;">
                        <?= count($reevaluations) ?>
                    </span>
                </div>
                <div style="padding:12px; max-height:560px; overflow-y:auto;">
                    <?php if (empty($reevaluations)): ?>
                    <div style="text-align:center; padding:28px 16px;">
                        <i class="bi bi-journal-x" style="font-size:2rem; opacity:.18; display:block; margin-bottom:8px; color:#94a3b8;"></i>
                        <p style="color:#94a3b8; font-size:.82rem; margin:0;">Aucune réévaluation enregistrée.</p>
                    </div>
                    <?php else: foreach ($reevaluations as $idx => $r): ?>
                    <div class="history-entry">
                        <div class="he-header" onclick="toggleHist('he-<?= $r['id'] ?>')">
                            <div class="flex-grow-1">
                                <div>
                                    <span class="he-date"><?= date('d/m/Y', strtotime($r['date_reevaluation'])) ?></span>
                                    <span class="he-hour"><?= substr($r['heure_reevaluation'], 0, 5) ?></span>
                                </div>
                                <div class="he-doc">
                                    Dr. <?= htmlspecialchars($r['medecin_prenom'] . ' ' . $r['medecin_nom']) ?>
                                </div>
                            </div>
                            <div class="d-flex align-items-center gap-2 flex-shrink-0">
                                <span class="evol-pill ep-<?= $r['evolution_globale'] ?>">
                                    <?= $evolLabels[$r['evolution_globale']] ?? $r['evolution_globale'] ?>
                                </span>
                                <i class="bi bi-chevron-down text-muted" style="font-size:.7rem;"></i>
                            </div>
                        </div>
                        <div class="he-body" id="he-<?= $r['id'] ?>">
                            <?php if (!empty($r['plaintes_jour'])): ?>
                            <div class="mb-3">
                                <div class="he-detail-lbl">Plaintes</div>
                                <div class="he-detail-val"><?= nl2br(htmlspecialchars($r['plaintes_jour'])) ?></div>
                            </div>
                            <?php endif; ?>

                            <?php
                            // Champs d'examen physique à afficher avec leur libellé
                            $examensPhysiques = [
                                'examen_general'          => 'Examen général',
                                'examen_cardiovasculaire' => 'Cardiovasculaire',
                                'examen_respiratoire'     => 'Respiratoire',
                                'examen_digestif'         => 'Digestif',
                                'examen_neurologique'     => 'Neurologique',
                                'examen_osteoarticulaire' => 'Ostéo-articulaire',
                                'examen_autres'           => 'Autres',
                            ];
                            $hasExamen = false;
                            foreach ($examensPhysiques as $key => $_) {
                                if (!empty($r[$key])) { $hasExamen = true; break; }
                            }
                            if ($hasExamen): ?>
                            <div class="mb-3">
                                <div class="he-detail-lbl">
                                    <i class="bi bi-stethoscope me-1" style="color:#0369a1;"></i>Examen physique
                                </div>
                                <div style="background:#f0f9ff;border-radius:8px;padding:10px 12px;border-left:3px solid #0ea5e9;margin-top:4px;">
                                    <?php foreach ($examensPhysiques as $key => $label): ?>
                                    <?php if (!empty($r[$key])): ?>
                                    <div style="margin-bottom:6px;">
                                        <span style="font-size:.72rem;font-weight:700;text-transform:uppercase;color:#0369a1;letter-spacing:.5px;"><?= $label ?></span>
                                        <div style="font-size:.82rem;color:#1e293b;margin-top:2px;"><?= nl2br(htmlspecialchars($r[$key])) ?></div>
                                    </div>
                                    <?php endif; ?>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <?php endif; ?>

                            <?php if (!empty($r['diagnostic_jour'])): ?>
                            <div class="mb-3">
                                <div class="he-detail-lbl">Diagnostic</div>
                                <div class="he-detail-val" style="color:#1d4ed8;">
                                    <?php if (!empty($r['code_cim10'])): ?>
                                    <span style="font-weight:800; font-size:.78rem;">[<?= htmlspecialchars($r['code_cim10']) ?>]</span>
                                    <?php endif; ?>
                                    <?= htmlspecialchars($r['diagnostic_jour']) ?>
                                </div>
                            </div>
                            <?php endif; ?>
                            <?php if (!empty($r['note_evolution'])): ?>
                            <div class="mb-3">
                                <div class="he-detail-lbl">Note évolution</div>
                                <div class="he-detail-val"><?= nl2br(htmlspecialchars($r['note_evolution'])) ?></div>
                            </div>
                            <?php endif; ?>
                            <?php if (!empty($r['conduite_tenir'])): ?>
                            <div class="mb-3">
                                <div class="he-detail-lbl">Conduite à tenir</div>
                                <div class="he-detail-val"><?= nl2br(htmlspecialchars($r['conduite_tenir'])) ?></div>
                            </div>
                            <?php endif; ?>
                            <?php if (!empty($r['traitement_non_medicamenteux'])): ?>
                            <div class="mb-3">
                                <div class="he-detail-lbl">
                                    <i class="bi bi-heart-pulse me-1" style="color:#854d0e;"></i>Traitement non médicamenteux
                                </div>
                                <div class="he-detail-val"><?= nl2br(htmlspecialchars($r['traitement_non_medicamenteux'])) ?></div>
                            </div>
                            <?php endif; ?>
                            <div class="d-flex align-items-center gap-3 flex-wrap">
                                <?php if ($r['nb_medicaments'] > 0): ?>
                                <small style="color:#dc2626; font-weight:700;">
                                    <i class="bi bi-capsule-pill me-1"></i><?= $r['nb_medicaments'] ?> médicament<?= $r['nb_medicaments'] > 1 ? 's' : '' ?>
                                </small>
                                <?php endif; ?>
                                <?php if ($r['nb_bilans'] > 0): ?>
                                <small style="color:#0369a1; font-weight:700;">
                                    <i class="bi bi-flask me-1"></i><?= $r['nb_bilans'] ?> bilan<?= $r['nb_bilans'] > 1 ? 's' : '' ?>
                                </small>
                                <?php endif; ?>
                                <?php if ($r['prescription_id']): ?>
                                <a href="<?= BASE_URL ?>consultation/imprimer-ordonnance/<?= (int)$r['prescription_id'] ?>"
                                   target="_blank"
                                   class="btn btn-sm btn-outline-primary rounded-pill fw-bold"
                                   style="font-size:.72rem; padding:3px 12px;">
                                    <i class="bi bi-printer me-1"></i>Ordonnance
                                </a>
                                <?php endif; ?>
                                <?php
                                // Bouton MODIFIER : visible uniquement pour l'auteur ou un admin/directeur
                                $currentUserId = (int)($_SESSION['user_id'] ?? 0);
                                $currentRole   = strtoupper($_SESSION['user_role'] ?? '');
                                $canEditReeval = ((int)$r['medecin_id'] === $currentUserId)
                                              || in_array($currentRole, ['ADMIN','ADMINISTRATEUR','DIRECTEUR','MEDECIN_CHEF']);
                                if ($canEditReeval):
                                ?>
                                <a href="<?= BASE_URL ?>hospitalisation/reevaluation/<?= (int)$r['patient_id'] ?>?edit=<?= (int)$r['id'] ?>"
                                   class="btn btn-sm btn-outline-warning rounded-pill fw-bold ms-auto"
                                   style="font-size:.72rem; padding:3px 12px;"
                                   title="Modifier cette réévaluation">
                                    <i class="bi bi-pencil-fill me-1"></i>Modifier
                                </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; endif; ?>
                </div>
            </div>

        </div>
    </div>
    </form>
</div>

<!-- Floating save button (small screens) -->
<div class="d-xl-none" style="position:fixed; bottom:22px; right:22px; z-index:999;">
    <button onclick="soumettreReevaluation()"
            style="width:58px; height:58px; border-radius:50%; background:var(--rv-primary); color:white; border:none; font-size:1.4rem; box-shadow:0 6px 20px rgba(30,64,175,.4); cursor:pointer;">
        <i class="bi bi-check-lg"></i>
    </button>
</div>

<script>
// ── Horloge ───────────────────────────────────────────────────
setInterval(() => {
    document.getElementById('rvClock').textContent = new Date().toLocaleTimeString('fr-FR');
}, 1000);

// ── Toggle collapsible sections ───────────────────────────────
function toggleBody(bodyId, chevronId) {
    const el = document.getElementById(bodyId);
    const ch = document.getElementById(chevronId);
    const shown = el.style.display !== 'none';
    el.style.display = shown ? 'none' : 'block';
    if (ch) ch.style.transform = shown ? '' : 'rotate(180deg)';
}

// ── Toggle history entries ────────────────────────────────────
function toggleHist(id) {
    const el = document.getElementById(id);
    el.classList.toggle('open');
}

// ── Exam tabs ─────────────────────────────────────────────────
function switchExam(btn, panelId) {
    document.querySelectorAll('.exam-pill').forEach(b => b.classList.remove('active'));
    document.querySelectorAll('.exam-panel').forEach(p => p.style.display = 'none');
    btn.classList.add('active');
    document.getElementById(panelId).style.display = 'block';
}

const NORMALS = {
    general:          'État général conservé. Conscient, orienté temporo-spatialement. Bien hydraté. Conjonctives rosées. Pas de pâleur ni ictère.',
    cardiovasculaire: 'Bruits du cœur réguliers, bien frappés, sans souffle audible. Pouls bien perçu, symétrique. TA stable. Pas d\'œdèmes des membres inférieurs. TRC < 3s.',
    respiratoire:     'Murmure vésiculaire présent bilatéral. Pas de râles crépitants ni sibilants. Fréquence respiratoire normale. Saturation en O₂ satisfaisante. Pas de signes de détresse respiratoire.',
    digestif:         'Abdomen souple, indolore à la palpation. Pas de défense ni de contracture. Pas d\'hépatomégalie ni de splénomégalie. Pas d\'ascite. Transit intestinal conservé.',
    neurologique:     'Conscience normale (Glasgow 15). Orienté dans le temps et l\'espace. Pas de déficit moteur ou sensitif. ROT présents et symétriques. Pas de signe de Babinski. Nerfs crâniens intacts.',
    osteoarticulaire: 'Pas de douleur articulaire significative. Mobilité articulaire conservée. Pas d\'inflammation locale, de tuméfaction ni de déformation.',
    autres:           'Peau et muqueuses de couleur normale, sans lésion visible. Pas d\'adénopathie périphérique palpable. Examen ORL sans particularités. Pas de signe clinique supplémentaire notable.',
};
function setNormal(sys) {
    const ta = document.getElementById('ta-' + sys);
    if (!ta || !NORMALS[sys]) return;
    // Ajoute à la suite du texte existant au lieu de le remplacer
    ta.value = ta.value.trim()
        ? ta.value.trimEnd() + '\n' + NORMALS[sys]
        : NORMALS[sys];
    ta.focus();
    // Place le curseur à la fin
    ta.selectionStart = ta.selectionEnd = ta.value.length;
}

// ── Traitement non médicamenteux ─────────────────────────────
function addTNM(text) {
    const ta = document.getElementById('taNonMed');
    if (!ta) return;
    ta.value = ta.value.trim()
        ? ta.value.trimEnd() + '\n• ' + text
        : '• ' + text;
    ta.focus();
    ta.selectionStart = ta.selectionEnd = ta.value.length;
}

// ── Complaint chips ───────────────────────────────────────────
function addChip(fieldName, text) {
    const ta = document.querySelector(`textarea[name="${fieldName}"]`);
    const curr = ta.value.trim();
    ta.value = curr ? curr + ', ' + text : text;
    ta.focus();
}

// ── Évolution globale ─────────────────────────────────────────
function selectEvol(val) {
    document.querySelectorAll('.evol-card').forEach(c => c.classList.remove('selected'));
    const card = document.getElementById('evol-' + val);
    if (card) {
        card.classList.add('selected');
        card.querySelector('input[type="radio"]').checked = true;
    }
}

// ── CIM-10 search ─────────────────────────────────────────────
let _cimTimer = null;
function doCim10Search(q) {
    clearTimeout(_cimTimer);
    const list = document.getElementById('cim10List');
    if (q.length < 2) { list.style.display = 'none'; return; }
    _cimTimer = setTimeout(() => {
        fetch('<?= BASE_URL ?>consultation/search-cim10?q=' + encodeURIComponent(q))
            .then(r => r.json())
            .then(data => {
                if (!data || !data.length) { list.style.display = 'none'; return; }
                list.innerHTML = data.slice(0, 10).map(d =>
                    `<div class="ac-item" onclick="selectCim10('${esc(d.code)}','${esc(d.libelle)}')">
                        <span class="cim-code">${d.code}</span>${d.libelle}
                     </div>`
                ).join('');
                list.style.display = 'block';
            }).catch(() => list.style.display = 'none');
    }, 300);
}
function selectCim10(code, libelle) {
    document.getElementById('hiddenCim10').value  = code;
    document.getElementById('cim10Input').value   = '[' + code + '] ' + libelle;
    document.getElementById('cim10List').style.display = 'none';
    document.getElementById('cim10Tag').style.display  = 'flex';
    document.getElementById('cim10Tag').style.alignItems = 'center';
    document.getElementById('cim10TagText').textContent = code + ' — ' + libelle;
}
function clearCim10() {
    document.getElementById('hiddenCim10').value  = '';
    document.getElementById('cim10Input').value   = '';
    document.getElementById('cim10Tag').style.display = 'none';
}
document.addEventListener('click', e => {
    if (!e.target.closest('#cim10Input') && !e.target.closest('#cim10List'))
        document.getElementById('cim10List').style.display = 'none';
});

// ── Medications ───────────────────────────────────────────────
let _medIdx = 0, _acTimer = null;

function addMedRow() {
    const idx = _medIdx++;
    const tr  = document.createElement('tr');
    tr.id     = 'mr-' + idx;
    tr.innerHTML = `
        <td style="padding-left:14px; position:relative;">
            <input type="text" class="med-input" name="medicaments[${idx}][nom]"
                   placeholder="Médicament..." autocomplete="off"
                   oninput="doMedAc(this,${idx})" onfocus="doMedAc(this,${idx})">
            <input type="hidden" name="medicaments[${idx}][id]" id="mid-${idx}">
            <div class="autocomplete-list" id="mac-${idx}" style="display:none;"></div>
        </td>
        <td><input type="text" class="med-input" name="medicaments[${idx}][posologie]" placeholder="ex: 500mg"></td>
        <td>
            <select class="med-input" name="medicaments[${idx}][voie]">
                <option value="">Voie</option>
                <option value="PO">PO (oral)</option>
                <option value="IV">IV</option>
                <option value="IM">IM</option>
                <option value="SC">SC</option>
                <option value="PERF">Perfusion</option>
                <option value="SL">Sublingual</option>
                <option value="TOPIQUE">Topique</option>
                <option value="INHAL">Inhalation</option>
            </select>
        </td>
        <td>
            <select class="med-input" name="medicaments[${idx}][frequence]">
                <option value="">—</option>
                <option>1×/j</option><option>2×/j</option><option>3×/j</option><option>4×/j</option>
                <option>Matin</option><option>Soir</option><option>Matin et soir</option>
                <option>Toutes les 8h</option><option>Toutes les 6h</option>
                <option>Si besoin</option><option>Continue</option>
            </select>
        </td>
        <td><input type="text" class="med-input" name="medicaments[${idx}][duree]" placeholder="ex: 5j"></td>
        <td><input type="number" class="med-input" name="medicaments[${idx}][quantite]" value="1" min="1" style="width:50px;"></td>
        <td>
            <button type="button" class="btn-del" onclick="document.getElementById('mr-${idx}').remove()" title="Supprimer">
                <i class="bi bi-trash3"></i>
            </button>
        </td>
    `;
    document.getElementById('medTbody').appendChild(tr);
    tr.querySelector('input[type="text"]').focus();
    // Activer la dictée sur les champs texte de la nouvelle ligne
    if (typeof window.dicteeAttach === 'function') window.dicteeAttach(tr);
}

function doMedAc(input, idx) {
    clearTimeout(_acTimer);
    const q   = input.value.trim();
    const box = document.getElementById('mac-' + idx);
    if (q.length < 2) { box.style.display = 'none'; return; }
    _acTimer = setTimeout(() => {
        fetch('<?= BASE_URL ?>hospitalisation/search-medicament-reeval?q=' + encodeURIComponent(q))
            .then(r => r.json()).then(data => {
                if (!data.length) { box.style.display = 'none'; return; }
                box.innerHTML = data.map(m =>
                    `<div class="ac-item" onclick="pickMed(${idx},${m.id},'${esc(m.nom)} ${esc(m.dosage||'')}')">
                        <strong>${m.nom}</strong>
                        <span style="color:#64748b; font-size:.8em;"> ${m.forme||''} ${m.dosage||''}</span>
                     </div>`
                ).join('');
                box.style.display = 'block';
                const closer = e => {
                    if (!box.contains(e.target) && e.target !== input) {
                        box.style.display = 'none';
                        document.removeEventListener('click', closer);
                    }
                };
                document.addEventListener('click', closer);
            });
    }, 250);
}

function pickMed(idx, id, nom) {
    document.querySelector(`[name="medicaments[${idx}][nom]"]`).value = nom.trim();
    document.getElementById('mid-' + idx).value = id;
    document.getElementById('mac-' + idx).style.display = 'none';
}

// ── Bilans — Laboratoire ─────────────────────────────────────
let rvExamensLabo    = [];
let rvExamensDispos  = [];
const rvSelected     = new Map(); /* id → exam object — persiste à travers les filtres */

document.addEventListener('DOMContentLoaded', () => rvChargerExamensDispos());

function rvChargerExamensDispos() {
    fetch('<?= BASE_URL ?>laboratoire/examens-disponibles')
        .then(r => r.json())
        .then(data => {
            rvExamensDispos = data;
            const catSel = document.getElementById('rvCatExamen');
            const cats   = [...new Set(data.map(e => e.categorie).filter(Boolean))].sort();
            cats.forEach(c => catSel.insertAdjacentHTML('beforeend', `<option value="${c}">${c}</option>`));
            rvChargerExamens();
        })
        .catch(() => {});
}

function rvChargerExamens() {
    const cat    = document.getElementById('rvCatExamen').value;
    const search = (document.getElementById('rvSearchExamen')?.value || '').toLowerCase().trim();
    const list   = document.getElementById('rvExamensList');

    /* Mémoriser les IDs déjà cochés avant de re-rendre */
    const checkedIds = new Set([...document.querySelectorAll('.rv-exam-check:checked')].map(cb => cb.value));

    let filtered = cat ? rvExamensDispos.filter(e => e.categorie === cat) : rvExamensDispos;
    if (search) filtered = filtered.filter(e => e.nom.toLowerCase().includes(search));

    const countBadge = document.getElementById('rvExamenCount');
    if (countBadge) countBadge.textContent = filtered.length + ' examen' + (filtered.length > 1 ? 's' : '');

    if (filtered.length === 0) {
        list.innerHTML = '<p class="text-muted small text-center py-4 mb-0"><i class="bi bi-search me-1"></i>Aucun examen trouvé</p>';
        rvMajCompteurSelection();
        return;
    }

    list.innerHTML = filtered.map(ex => {
        const dataStr  = JSON.stringify(ex).replace(/"/g, '&quot;');
        const aJeun    = ex.a_jeun_requis
            ? '<span class="badge bg-warning text-dark ms-1" style="font-size:.6rem">À jeun</span>' : '';
        const isChecked = rvSelected.has(ex.id);
        const rowBg    = isChecked ? 'background:#eff6ff;' : '';
        return `
        <div class="rv-exam-row d-flex align-items-start gap-2 px-2 py-2"
             style="border-bottom:1px solid #f1f5f9;cursor:pointer;transition:background .12s;border-radius:6px;${rowBg}"
             onclick="rvToggleRow(this)">
            <input class="form-check-input flex-shrink-0 rv-exam-check" type="checkbox"
                   id="rvEx_${ex.id}" value="${ex.id}"
                   data-examen="${dataStr}"
                   ${isChecked ? 'checked' : ''}
                   style="margin-top:3px;cursor:pointer"
                   onclick="event.stopPropagation();rvToggleSelected(this)">
            <label class="mb-0 w-100" for="rvEx_${ex.id}" style="cursor:pointer;pointer-events:none">
                <div class="fw-semibold" style="font-size:.85rem">${ex.nom}</div>
                <div class="text-muted d-flex align-items-center flex-wrap gap-1" style="font-size:.72rem">
                    <span class="badge bg-secondary" style="font-size:.62rem">${ex.categorie || '—'}</span>
                    <span>${ex.type_prelevement || ''}</span>
                    <span>•</span>
                    <span>Délai : <strong>${ex.delai_rendu_heures}h</strong></span>
                    ${aJeun}
                </div>
            </label>
        </div>`;
    }).join('');
    rvRefreshSelectedPanel();
}

function rvToggleRow(row) {
    const cb = row.querySelector('.rv-exam-check');
    if (!cb) return;
    cb.checked = !cb.checked;
    rvRowHighlight(row, cb.checked);
    rvMajCompteurSelection();
}

function rvRowHighlight(row, checked) {
    row.style.background = checked ? '#eff6ff' : '';
}

/* ── Gestion Map de sélection ── */
function rvToggleSelected(cb) {
    const ex = JSON.parse(cb.dataset.examen.replace(/&quot;/g, '"'));
    const row = cb.closest('.rv-exam-row');
    if (cb.checked) {
        rvSelected.set(ex.id, ex);
        if (row) row.style.background = '#eff6ff';
    } else {
        rvSelected.delete(ex.id);
        if (row) row.style.background = '';
    }
    rvRefreshSelectedPanel();
}

function rvRefreshSelectedPanel() {
    const panel = document.getElementById('rvSelectedPanel');
    const empty = document.getElementById('rvSelectedEmpty');
    const count = document.getElementById('rvSelectedCount');
    if (!panel) return;
    const items = [...rvSelected.values()];
    if (count) count.textContent = items.length > 0 ? `${items.length} sélectionné${items.length>1?'s':''}` : '';
    /* Vider sauf le message vide */
    panel.querySelectorAll('.rv-sel-item').forEach(el => el.remove());
    if (items.length === 0) {
        if (empty) empty.style.display = 'block';
        return;
    }
    if (empty) empty.style.display = 'none';
    items.forEach(ex => {
        const div = document.createElement('div');
        div.className = 'rv-sel-item d-flex align-items-center justify-content-between gap-2 px-2 py-1 mb-1 rounded';
        div.style.cssText = 'background:white;border:1px solid #86efac;font-size:.82rem;';
        div.innerHTML = `
            <div><span class="fw-semibold">${ex.nom}</span>
            <span class="badge bg-secondary ms-1" style="font-size:.6rem">${ex.categorie||''}</span></div>
            <button type="button" class="btn btn-sm btn-outline-danger rounded-pill flex-shrink-0"
                    style="font-size:.7rem;padding:1px 7px;" onclick="rvRetirerSelected(${ex.id})">
                <i class="bi bi-x-lg"></i>
            </button>`;
        panel.appendChild(div);
    });
}

function rvRetirerSelected(exId) {
    rvSelected.delete(exId);
    /* Décocher la checkbox si visible */
    const cb = document.querySelector(`#rvEx_${exId}`);
    if (cb) { cb.checked = false; const row = cb.closest('.rv-exam-row'); if (row) row.style.background = ''; }
    rvRefreshSelectedPanel();
}

function rvMajCompteurSelection() { rvRefreshSelectedPanel(); }

function rvDeselectAll() {
    rvSelected.clear();
    document.querySelectorAll('.rv-exam-check').forEach(cb => {
        cb.checked = false;
        const row = cb.closest('.rv-exam-row'); if (row) row.style.background = '';
    });
    rvRefreshSelectedPanel();
}

function rvAjouterExamen(e) {
    e.preventDefault();
    const f = e.target;
    if (rvSelected.size === 0) {
        const list = document.getElementById('rvExamensList');
        list.style.border = '1.5px solid #dc2626';
        setTimeout(() => { list.style.border = '1.5px solid #dee2e6'; }, 1800);
        return;
    }
    const isUrgent     = f.urgent.checked;
    const isAJeun      = f.a_jeun.checked;
    const instructions = f.instructions.value;

    rvSelected.forEach(ex => {
        if (!rvExamensLabo.find(x => x.id === ex.id)) {
            rvExamensLabo.push({
                id: ex.id, nom: ex.nom, categorie: ex.categorie,
                type_prelevement: ex.type_prelevement, delai_rendu_heures: ex.delai_rendu_heures,
                urgent: isUrgent, a_jeun: isAJeun, instructions
            });
        }
    });

    rvSelected.clear();
    rvAfficherLabo();
    bootstrap.Modal.getInstance(document.getElementById('rvModalExamen')).hide();
    f.reset();
}

// Reset du modal à l'ouverture
document.getElementById('rvModalExamen')?.addEventListener('show.bs.modal', () => {
    rvSelected.clear();
    document.getElementById('rvSearchExamen').value = '';
    document.getElementById('rvCatExamen').value    = '';
    rvChargerExamens();
    rvRefreshSelectedPanel();
});

function rvAfficherLabo() {
    const tbody = document.getElementById('rvListeLabo');
    const empty = document.getElementById('rvEmptyLabo');
    const btn   = document.getElementById('btnRvEnvLabo');
    if (!rvExamensLabo.length) {
        tbody.innerHTML = ''; empty.style.display = 'block'; btn.style.display = 'none'; return;
    }
    empty.style.display = 'none'; btn.style.display = 'inline-block';
    tbody.innerHTML = rvExamensLabo.map((ex, i) => `
        <tr>
            <td class="fw-bold">${ex.nom}</td>
            <td><span class="badge bg-secondary">${ex.categorie}</span></td>
            <td class="small">${ex.type_prelevement}</td>
            <td class="small">${ex.delai_rendu_heures}h</td>
            <td>${ex.urgent ? '<span class="badge bg-danger">URGENT</span>' : '<span class="badge bg-success">Normal</span>'}${ex.a_jeun ? '<br><small class="text-warning">À jeun</small>' : ''}</td>
            <td class="text-center">
                <button type="button" class="btn btn-sm btn-outline-danger" onclick="rvRetirerLabo(${i})">
                    <i class="bi bi-trash3"></i>
                </button>
            </td>
        </tr>`).join('');
}

function rvRetirerLabo(i) { rvExamensLabo.splice(i, 1); rvAfficherLabo(); }

function rvEnvoyerLabo() {
    if (!rvExamensLabo.length) return;
    const btn = document.getElementById('btnRvEnvLabo');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" style="width:12px;height:12px;"></span>Envoi...';
    fetch('<?= BASE_URL ?>laboratoire/creer-demande-consultation', {
        method: 'POST', headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ patient_id: <?= (int)$pid ?>, examens: rvExamensLabo })
    })
    .then(r => r.json())
    .then(d => {
        if (d.success) {
            showToast('✅ Demande(s) labo envoyée(s) !', '#16a34a');
            rvExamensLabo = []; rvAfficherLabo();
            btn.innerHTML = '<i class="bi bi-check-lg me-1"></i>Envoyé';
        } else {
            showToast('❌ ' + (d.message || 'Erreur labo'), '#dc2626');
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-send-fill me-1"></i>Envoyer au Labo';
        }
    })
    .catch(() => {
        showToast('❌ Erreur réseau (labo).', '#dc2626');
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-send-fill me-1"></i>Envoyer au Labo';
    });
}

// ── Bilans — Imagerie ─────────────────────────────────────────
let rvDemandesImagerie = [];

const rvPartiesCorps = {
    radiographie: ['Thorax','Abdomen','Crâne','Rachis cervical','Rachis dorsal','Rachis lombaire','Bassin','Épaule droite','Épaule gauche','Bras droit','Bras gauche','Avant-bras droit','Avant-bras gauche','Main droite','Main gauche','Cuisse droite','Cuisse gauche','Jambe droite','Jambe gauche','Pied droit','Pied gauche','Cheville droite','Cheville gauche','Genou droit','Genou gauche'],
    echographie:  ['Abdomen total','Pelvis','Obstétricale','Thyroïde','Sein droit','Sein gauche','Rein droit','Rein gauche','Foie','Vésicule biliaire','Rate','Prostate','Col utérin','Tendons','Paroi abdominale'],
    scanner:      ['Crâne','Thorax','Abdomen-Pelvis','Thorax-Abdomen-Pelvis','Rachis cervical','Rachis dorsal','Rachis lombaire','Bassin','Membre supérieur droit','Membre supérieur gauche','Membre inférieur droit','Membre inférieur gauche','Corps entier'],
    irm:          ['Crâne','Rachis cervical','Rachis dorsal','Rachis lombaire','Genou droit','Genou gauche','Épaule droite','Épaule gauche','Hanche droite','Hanche gauche','Poignet droit','Poignet gauche','Pelvis','Sein droit','Sein gauche','Abdomen','Corps entier'],
    mammographie: ['Sein droit','Sein gauche','Bilatéral'],
    autre:        ['À préciser dans les instructions']
};
const rvIconeModalite = { radiographie:'🩻', echographie:'🔊', scanner:'💻', irm:'🧲', mammographie:'🔬', autre:'📋' };

function rvUpdatePartiesCorps() {
    const m   = document.getElementById('rvModaliteImg').value;
    const sel = document.getElementById('rvPartieCorpsImg');
    sel.innerHTML = '<option value="">— Choisir la partie —</option>';
    (rvPartiesCorps[m] || []).forEach(p => sel.insertAdjacentHTML('beforeend', `<option value="${p}">${p}</option>`));
    const info = document.getElementById('rvInfoImagerie');
    info.innerHTML = (m === 'scanner' || m === 'irm')
        ? `<div class="alert alert-warning py-2 small"><i class="bi bi-info-circle me-1"></i>Pour le <strong>${m.toUpperCase()}</strong>, vérifiez la créatinine et les allergies au produit de contraste si injection prévue.</div>`
        : '';
}

function rvAjouterImagerie(e) {
    e.preventDefault();
    const f          = e.target;
    const modalite   = f.modalite.value;
    const partie     = f.partie_corps.value;
    const indication = f.indication.value.trim();
    if (!modalite || !partie || !indication) {
        alert('Veuillez renseigner la modalité, la partie du corps et l\'indication clinique.'); return;
    }
    rvDemandesImagerie.push({
        modalite, partie_corps: partie,
        urgence: f.urgence.value, cote: f.cote.value,
        incidence: f.incidence.value,
        avec_contraste: f.avec_contraste.checked,
        indication, instructions: f.instructions.value
    });
    rvAfficherImagerie();
    bootstrap.Modal.getInstance(document.getElementById('rvModalImagerie')).hide();
    f.reset();
    document.getElementById('rvInfoImagerie').innerHTML = '';
    document.getElementById('rvPartieCorpsImg').innerHTML = '<option value="">— Sélectionner d\'abord la modalité —</option>';
}

function rvAfficherImagerie() {
    const tbody = document.getElementById('rvListeImagerie');
    const empty = document.getElementById('rvEmptyImagerie');
    const btn   = document.getElementById('btnRvEnvRadio');
    if (!rvDemandesImagerie.length) {
        tbody.innerHTML = ''; empty.style.display = 'block'; btn.style.display = 'none'; return;
    }
    empty.style.display = 'none'; btn.style.display = 'inline-block';
    const urgBadge = { NORMAL: 'bg-success', URGENT: 'bg-danger', TRES_URGENT: 'bg-dark' };
    tbody.innerHTML = rvDemandesImagerie.map((d, i) => `
        <tr>
            <td class="fw-bold">${rvIconeModalite[d.modalite]||'📋'} ${d.modalite.charAt(0).toUpperCase()+d.modalite.slice(1)}</td>
            <td>${d.partie_corps}${d.cote ? ` <small class="text-muted">(${d.cote})</small>` : ''}${d.incidence ? ` <span class="badge bg-secondary ms-1" style="font-size:.65rem">${d.incidence}</span>` : ''}${d.avec_contraste ? ' <span class="badge bg-info text-dark ms-1">+Contraste</span>' : ''}</td>
            <td><span class="badge ${urgBadge[d.urgence]||'bg-secondary'}">${d.urgence}</span></td>
            <td class="small text-muted">${d.indication.substring(0,55)}${d.indication.length>55?'…':''}</td>
            <td class="text-center">
                <button type="button" class="btn btn-sm btn-outline-danger" onclick="rvRetirerImagerie(${i})">
                    <i class="bi bi-trash3"></i>
                </button>
            </td>
        </tr>`).join('');
}

function rvRetirerImagerie(i) { rvDemandesImagerie.splice(i, 1); rvAfficherImagerie(); }

function rvEnvoyerRadio() {
    if (!rvDemandesImagerie.length) return;
    const btn = document.getElementById('btnRvEnvRadio');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" style="width:12px;height:12px;"></span>Envoi...';
    fetch('<?= BASE_URL ?>imagerie/creer-demande-consultation', {
        method: 'POST', headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            patient_id: <?= (int)$pid ?>, consultation_id: null,
            medecin_id: <?= (int)($_SESSION['user_id'] ?? 0) ?>,
            demandes: rvDemandesImagerie
        })
    })
    .then(r => r.json())
    .then(d => {
        if (d.success) {
            showToast(`✅ ${d.count || rvDemandesImagerie.length} demande(s) imagerie envoyée(s) !`, '#16a34a');
            rvDemandesImagerie = []; rvAfficherImagerie();
            btn.innerHTML = '<i class="bi bi-check-lg me-1"></i>Envoyé';
        } else {
            showToast('❌ ' + (d.message || 'Erreur imagerie'), '#dc2626');
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-send-fill me-1"></i>Envoyer en Radiologie';
        }
    })
    .catch(() => {
        showToast('❌ Erreur réseau (imagerie).', '#dc2626');
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-send-fill me-1"></i>Envoyer en Radiologie';
    });
}

// ── Mode édition : récupérer l'ID si on modifie ─────────────────
const REEVAL_EDIT_ID = parseInt(document.getElementById('reevalEditId')?.value || '0', 10);

// ── Submit ────────────────────────────────────────────────────
function soumettreReevaluation() {
    // Validation
    const evolOk = document.querySelector('input[name="evolution_globale"]:checked');
    if (!evolOk) {
        showToast('⚠️ Veuillez sélectionner l\'évolution globale du patient.', '#d97706');
        document.getElementById('evolGrid').scrollIntoView({ behavior: 'smooth', block: 'center' });
        return;
    }
    const plaintes = document.querySelector('textarea[name="plaintes_jour"]').value.trim();
    if (!plaintes) {
        showToast('⚠️ Veuillez renseigner les plaintes du jour.', '#d97706');
        document.querySelector('textarea[name="plaintes_jour"]').focus();
        return;
    }

    const isEdit = REEVAL_EDIT_ID > 0;
    const btn = document.getElementById('btnSave');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" style="width:14px;height:14px;"></span>'
                  + (isEdit ? 'Modification...' : 'Enregistrement...');

    const fd = new FormData(document.getElementById('formReeval'));

    const url = isEdit
        ? '<?= BASE_URL ?>hospitalisation/update-reevaluation/' + REEVAL_EDIT_ID
        : '<?= BASE_URL ?>hospitalisation/save-reevaluation';

    /* Snapshot des bilans AVANT envoi (ils seront vidés après) */
    const bilansLaboSnapshot    = [...rvExamensLabo];
    const bilansImgSnapshot     = [...rvDemandesImagerie];

    fetch(url, { method: 'POST', body: fd })
        .then(r => r.json())
        .then(async data => {
            if (data.success) {
                /* ── Envoyer les bilans via endpoint dédié ── */
                if (bilansLaboSnapshot.length > 0 || bilansImgSnapshot.length > 0) {
                    try {
                        const br = await fetch('<?= BASE_URL ?>hospitalisation/creer-bilans-reeval', {
                            method : 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body   : JSON.stringify({
                                patient_id     : <?= (int)$pid ?>,
                                reevaluation_id: data.reevaluation_id || 0,
                                bilans_labo    : bilansLaboSnapshot,
                                bilans_imagerie: bilansImgSnapshot
                            })
                        });
                        const bd = await br.json();
                        data._debug_labo_count = bd.labo_created ?? 0;
                        data._debug_img_count  = bd.img_created  ?? 0;
                        data._debug_demande_id = bd.demande_id   ?? null;
                    } catch(e2) { console.error('[bilans reeval fetch]', e2); }
                }
                rvExamensLabo      = []; rvAfficherLabo();
                rvDemandesImagerie = []; rvAfficherImagerie();

                const successMsg = isEdit
                    ? '✅ Réévaluation modifiée avec succès !'
                    : '✅ Réévaluation enregistrée avec succès !';
                showToast(successMsg + (data.warning ? '\n⚠ ' + data.warning : ''), '#16a34a');

                if (data.bulletin_id && !isEdit) {
                    // Un bulletin d'examens a été créé → proposer signature
                    setTimeout(() => ouvrirModalSignatureBulletin(data.bulletin_id), 900);
                } else {
                    setTimeout(() => {
                        window.location.href = '<?= BASE_URL ?>hospitalisation/reevaluation/<?= $pid ?>?success=' + (isEdit ? 'modified' : '1');
                    }, isEdit && data.warning ? 3500 : 1200);
                }
            } else {
                showToast('❌ Erreur : ' + (data.message || 'Inconnue'), '#dc2626');
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-check-lg"></i><span>' + (isEdit ? 'Enregistrer la modification' : 'Sauvegarder') + '</span>';
            }
        })
        .catch(() => {
            showToast('❌ Erreur réseau.', '#dc2626');
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-check-lg"></i><span>' + (isEdit ? 'Enregistrer la modification' : 'Sauvegarder') + '</span>';
        });
}

// ════════════════════════════════════════════════════════════════
// MODE ÉDITION — pré-remplissage automatique des champs au chargement
// ════════════════════════════════════════════════════════════════
<?php if (!empty($reeval_to_edit)): ?>
(function preFillEditMode() {
    const reeval = <?= json_encode($reeval_to_edit, JSON_UNESCAPED_UNICODE) ?>;
    const meds   = <?= json_encode($reeval_meds_edit, JSON_UNESCAPED_UNICODE) ?>;
    const bilans = <?= json_encode($reeval_bilans_edit, JSON_UNESCAPED_UNICODE) ?>;

    // Helper sécurisé pour set la valeur d'un champ
    const setVal = (selector, value) => {
        const el = document.querySelector(selector);
        if (el && value !== null && value !== undefined) el.value = value;
    };

    // Champs textarea simples
    setVal('textarea[name="plaintes_jour"]',                reeval.plaintes_jour);
    setVal('textarea[name="histoire_maladie"]',             reeval.histoire_maladie);
    setVal('textarea[name="examen_general"]',               reeval.examen_general);
    setVal('textarea[name="examen_cardiovasculaire"]',      reeval.examen_cardiovasculaire);
    setVal('textarea[name="examen_respiratoire"]',          reeval.examen_respiratoire);
    setVal('textarea[name="examen_digestif"]',              reeval.examen_digestif);
    setVal('textarea[name="examen_neurologique"]',          reeval.examen_neurologique);
    setVal('textarea[name="examen_osteoarticulaire"]',      reeval.examen_osteoarticulaire);
    setVal('textarea[name="examen_autres"]',                reeval.examen_autres);
    setVal('textarea[name="note_evolution"]',               reeval.note_evolution);
    setVal('textarea[name="conduite_tenir"]',               reeval.conduite_tenir);
    setVal('textarea[name="traitement_non_medicamenteux"]', reeval.traitement_non_medicamenteux);

    // Diagnostic + CIM-10
    setVal('input[name="diagnostic_jour"]', reeval.diagnostic_jour);
    if (reeval.code_cim10) {
        setVal('input[name="code_cim10"]', reeval.code_cim10);
        const tag = document.getElementById('cim10Tag');
        const tagText = document.getElementById('cim10TagText');
        if (tag && tagText) {
            tagText.textContent = '[' + reeval.code_cim10 + '] ' + (reeval.diagnostic_jour || '');
            tag.style.display = 'inline-block';
        }
    }

    // Évolution globale (radio)
    if (reeval.evolution_globale) {
        const radio = document.querySelector('input[name="evolution_globale"][value="' + reeval.evolution_globale + '"]');
        if (radio) {
            radio.checked = true;
            // Marquer la card comme sélectionnée
            const card = document.getElementById('evol-' + reeval.evolution_globale);
            if (card) {
                document.querySelectorAll('.evol-card').forEach(c => c.classList.remove('selected'));
                card.classList.add('selected');
            }
        }
    }

    // Médicaments — injecter dans rvMedicaments si la fonction existe
    if (meds.length > 0 && typeof rvMedicaments !== 'undefined') {
        meds.forEach(m => {
            rvMedicaments.push({
                id:        m.medicament_id || null,
                nom:       m.nom_medicament,
                posologie: m.posologie || '',
                voie:      m.voie_administration || '',
                frequence: m.frequence || '',
                duree:     m.duree || '',
                quantite:  parseInt(m.quantite || 1, 10),
            });
        });
        if (typeof rvAfficherMedicaments === 'function') rvAfficherMedicaments();
    }

    // Bilans — séparer LABO et IMAGERIE
    bilans.forEach(b => {
        if (b.type === 'LABO' && typeof rvExamensLabo !== 'undefined') {
            rvExamensLabo.push({
                intitule: b.intitule, urgent: !!parseInt(b.urgence), note: b.note || ''
            });
        } else if (b.type === 'IMAGERIE' && typeof rvDemandesImagerie !== 'undefined') {
            rvDemandesImagerie.push({
                intitule: b.intitule, urgent: !!parseInt(b.urgence), note: b.note || ''
            });
        }
    });
    if (typeof rvAfficherLabo === 'function')      rvAfficherLabo();
    if (typeof rvAfficherImagerie === 'function')  rvAfficherImagerie();

    // Toast d'info
    setTimeout(() => {
        showToast('✏️ Réévaluation chargée — modifiez puis cliquez sur Enregistrer', '#0369a1');
    }, 300);
})();
<?php endif; ?>

function showToast(msg, bg, duration) {
    const t = document.getElementById('rvToast');
    t.textContent = msg;
    t.style.background = bg;
    t.style.color = 'white';
    t.style.display = 'block';
    setTimeout(() => t.style.display = 'none', duration || 4000);
}

function esc(s) {
    return (s || '').replace(/\\/g,'\\\\').replace(/'/g,"\\'").replace(/\r?\n/g,' ');
}

// Afficher le message de succès si redirigé avec ?success=1
<?php if (isset($_GET['success'])): ?>
showToast('✅ Réévaluation enregistrée ! Ordonnance transmise à la pharmacie.', '#16a34a');
<?php endif; ?>
</script>

<!-- ══════════════════════════════════════════════════════════════
     MODAL — DEMANDE D'EXAMEN DE LABORATOIRE (réévaluation)
══════════════════════════════════════════════════════════════ -->
<div class="modal fade" id="rvModalExamen" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title"><i class="bi bi-flask-fill me-2"></i>Demander un Examen de Laboratoire</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="rvFormExamen" onsubmit="rvAjouterExamen(event)">
                <div class="modal-body">
                    <div class="row g-3">

                        <!-- Colonne gauche : recherche + liste -->
                        <div class="col-md-7">
                            <div class="row g-2 mb-2">
                                <div class="col-md-5">
                                    <label class="form-label fw-semibold small text-uppercase text-muted">Catégorie</label>
                                    <select class="form-select form-select-sm" id="rvCatExamen" onchange="rvChargerExamens()">
                                        <option value="">Toutes</option>
                                    </select>
                                </div>
                                <div class="col-md-7">
                                    <label class="form-label fw-semibold small text-uppercase text-muted">Recherche</label>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text bg-white"><i class="bi bi-search text-muted"></i></span>
                                        <input type="text" class="form-control border-start-0" id="rvSearchExamen"
                                               placeholder="Tapez pour filtrer…" oninput="rvChargerExamens()" autocomplete="off">
                                        <button type="button" class="btn btn-outline-secondary" onclick="document.getElementById('rvSearchExamen').value='';rvChargerExamens()">
                                            <i class="bi bi-x-lg"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="d-flex align-items-center justify-content-between mb-1">
                                <span class="fw-semibold small text-uppercase text-muted">Examens disponibles</span>
                                <span id="rvExamenCount" class="badge bg-info text-white" style="font-size:.7rem"></span>
                            </div>
                            <div id="rvExamensList"
                                 style="height:280px;overflow-y:auto;border:1.5px solid #dee2e6;border-radius:.5rem;padding:4px;">
                                <p class="text-muted small text-center py-4 mb-0"><i class="bi bi-hourglass-split me-1"></i>Chargement…</p>
                            </div>
                        </div>

                        <!-- Colonne droite : sélectionnés + options -->
                        <div class="col-md-5">
                            <div class="d-flex align-items-center justify-content-between mb-1">
                                <span class="fw-semibold small text-uppercase text-muted">Sélectionnés</span>
                                <span id="rvSelectedCount" class="badge bg-success text-white" style="font-size:.7rem"></span>
                            </div>
                            <div id="rvSelectedPanel"
                                 style="height:280px;overflow-y:auto;border:1.5px solid #86efac;border-radius:.5rem;padding:6px;background:#f0fdf4;">
                                <p class="text-muted small text-center py-4 mb-0" id="rvSelectedEmpty">
                                    <i class="bi bi-arrow-left me-1"></i>Cochez des examens à gauche
                                </p>
                            </div>
                            <div class="mt-2">
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="checkbox" name="urgent" id="rvUrgentLabo">
                                    <label class="form-check-label text-danger fw-bold" for="rvUrgentLabo">
                                        <i class="bi bi-exclamation-triangle-fill"></i> URGENT
                                    </label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="checkbox" name="a_jeun" id="rvAJeun">
                                    <label class="form-check-label" for="rvAJeun">
                                        <i class="bi bi-clock"></i> À jeun
                                    </label>
                                </div>
                            </div>
                            <div class="mt-2">
                                <label class="form-label fw-semibold small text-uppercase text-muted">Instructions</label>
                                <textarea name="instructions" class="form-control form-control-sm" rows="3"
                                          placeholder="Instructions pour le laboratoire…"></textarea>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-info text-white fw-bold">
                        <i class="bi bi-plus-lg me-1"></i>Ajouter les examens sélectionnés
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ══════════════════════════════════════════════════════════════
     MODAL — DEMANDE D'IMAGERIE / RADIOLOGIE (réévaluation)
══════════════════════════════════════════════════════════════ -->
<div class="modal fade" id="rvModalImagerie" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header text-white" style="background:#1d4ed8;">
                <h5 class="modal-title"><i class="bi bi-image me-2"></i>Demande d'Imagerie Médicale</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="rvFormImagerie" onsubmit="rvAjouterImagerie(event)">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Modalité <span class="text-danger">*</span></label>
                            <select class="form-select" id="rvModaliteImg" name="modalite" required onchange="rvUpdatePartiesCorps()">
                                <option value="">— Choisir la modalité —</option>
                                <option value="radiographie">🩻 Radiographie</option>
                                <option value="echographie">🔊 Échographie</option>
                                <option value="scanner">💻 Scanner (TDM)</option>
                                <option value="irm">🧲 IRM</option>
                                <option value="mammographie">🔬 Mammographie</option>
                                <option value="autre">📋 Autre</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Partie du corps / Région <span class="text-danger">*</span></label>
                            <select class="form-select" id="rvPartieCorpsImg" name="partie_corps" required>
                                <option value="">— Sélectionner d'abord la modalité —</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Niveau d'urgence</label>
                            <select class="form-select" name="urgence">
                                <option value="NORMAL">Normal</option>
                                <option value="URGENT">🔴 URGENT</option>
                                <option value="TRES_URGENT">🚨 TRÈS URGENT</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Côté / Latéralité</label>
                            <select class="form-select" name="cote">
                                <option value="">Non applicable</option>
                                <option value="droit">Droit</option>
                                <option value="gauche">Gauche</option>
                                <option value="bilateral">Bilatéral</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Incidence</label>
                            <select class="form-select" name="incidence">
                                <option value="">Non précisée</option>
                                <option value="Face">Face</option>
                                <option value="Profil">Profil</option>
                                <option value="Face + Profil">Face + Profil</option>
                                <option value="Oblique">Oblique</option>
                            </select>
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="rvAvecContraste" name="avec_contraste" value="1">
                                <label class="form-check-label fw-bold" for="rvAvecContraste">
                                    💉 Avec produit de contraste
                                </label>
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold">Indication clinique <span class="text-danger">*</span></label>
                            <textarea class="form-control" name="indication" rows="2" required
                                      placeholder="Ex : Douleur thoracique, suspicion de fracture, contrôle post-opératoire..."></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold">Instructions particulières</label>
                            <textarea class="form-control" name="instructions" rows="2"
                                      placeholder="Informations complémentaires pour le radiologue..."></textarea>
                        </div>
                    </div>
                    <div id="rvInfoImagerie" class="mt-3"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-plus-lg me-1"></i>Ajouter à la liste
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ══ MODAL SIGNATURE BULLETIN ══════════════════════════════════════ -->
<div class="modal fade" id="modalSignatureBulletin" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header border-0 text-white" style="background:linear-gradient(135deg,#1e40af,#2563eb);">
                <h5 class="modal-title fw-bold">
                    <i class="bi bi-file-earmark-check-fill me-2"></i>Bulletin d'examens créé
                </h5>
            </div>
            <div class="modal-body p-4 text-center">
                <div style="width:64px;height:64px;background:#dbeafe;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;">
                    <i class="bi bi-journal-medical" style="font-size:2rem;color:#1d4ed8;"></i>
                </div>
                <h6 class="fw-bold mb-2">Le bulletin d'examens de laboratoire a été généré.</h6>
                <p class="text-muted" style="font-size:.88rem;">
                    Voulez-vous apposer votre signature électronique maintenant,<br>
                    ou signer le bulletin plus tard ?
                </p>
                <div id="bulletinSignMsg" class="mt-2" style="display:none;font-size:.82rem;"></div>
            </div>
            <div class="modal-footer border-0 pt-0 d-flex gap-2 justify-content-center pb-4">
                <button type="button" class="btn btn-outline-secondary rounded-pill px-4"
                        onclick="differerSignatureBulletin()">
                    <i class="bi bi-clock me-1"></i>Plus tard
                </button>
                <a id="btnVoirBulletin" href="#" target="_blank"
                   class="btn btn-outline-primary rounded-pill px-4">
                    <i class="bi bi-eye me-1"></i>Voir le bulletin
                </a>
                <button type="button" class="btn btn-primary rounded-pill px-4 fw-bold"
                        id="btnSignerBulletin" onclick="signerBulletinMaintenant()">
                    <i class="bi bi-pen-fill me-1"></i>Signer maintenant
                </button>
            </div>
        </div>
    </div>
</div>

<script>
let _bulletinIdCourant = null;
const _reevalRetourUrl = '<?= BASE_URL ?>hospitalisation/reevaluation/<?= $pid ?>?success=1';

function ouvrirModalSignatureBulletin(bulletinId) {
    _bulletinIdCourant = bulletinId;
    document.getElementById('bulletinSignMsg').style.display = 'none';
    document.getElementById('bulletinSignMsg').textContent   = '';
    document.getElementById('btnVoirBulletin').href = '<?= BASE_URL ?>formulaire/voir-bulletin-examens/' + bulletinId;
    const btnSigner = document.getElementById('btnSignerBulletin');
    btnSigner.disabled = false;
    btnSigner.innerHTML = '<i class="bi bi-pen-fill me-1"></i>Signer maintenant';
    new bootstrap.Modal(document.getElementById('modalSignatureBulletin')).show();
}

function differerSignatureBulletin() {
    bootstrap.Modal.getInstance(document.getElementById('modalSignatureBulletin')).hide();
    window.location.href = _reevalRetourUrl;
}

function signerBulletinMaintenant() {
    if (!_bulletinIdCourant) return;
    const btn = document.getElementById('btnSignerBulletin');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" style="width:14px;height:14px;"></span>Signature…';

    fetch('<?= BASE_URL ?>formulaire/signer-bulletin/' + _bulletinIdCourant, { method: 'POST' })
        .then(r => r.json())
        .then(data => {
            const msgEl = document.getElementById('bulletinSignMsg');
            if (data.success) {
                msgEl.style.display  = 'block';
                msgEl.className      = 'alert alert-success rounded-3 py-2 px-3 mt-2';
                msgEl.innerHTML      = '<i class="bi bi-check-circle-fill me-1"></i>Bulletin signé avec succès !';
                btn.innerHTML        = '<i class="bi bi-check-lg me-1"></i>Signé';
                setTimeout(() => {
                    bootstrap.Modal.getInstance(document.getElementById('modalSignatureBulletin')).hide();
                    window.location.href = _reevalRetourUrl;
                }, 1400);
            } else {
                msgEl.style.display  = 'block';
                msgEl.className      = 'alert alert-warning rounded-3 py-2 px-3 mt-2';
                msgEl.textContent    = data.message || 'Erreur lors de la signature.';
                btn.disabled         = false;
                btn.innerHTML        = '<i class="bi bi-pen-fill me-1"></i>Réessayer';
            }
        })
        .catch(() => {
            btn.disabled  = false;
            btn.innerHTML = '<i class="bi bi-pen-fill me-1"></i>Réessayer';
        });
}
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
