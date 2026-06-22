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
        --hsjm-blue: #0d6efd;
        --hsjm-light-blue: #eff6ff;
        --text-dark: #1e293b;
        --bg-soft: #f8fafc;
    }

    body {
        background-color: var(--bg-soft);
        font-family: 'Plus Jakarta Sans', sans-serif;
    }

    .card-plan {
        border: none;
        border-radius: 20px;
        box-shadow: 0 10px 25px rgba(0,0,0,0.03);
        background: white;
    }

    .header-fiche {
        background: white;
        border-bottom: 2px solid #f1f5f9;
        padding: 25px 30px;
        border-radius: 20px 20px 0 0;
    }

    .patient-info-banner {
        background-color: var(--hsjm-light-blue);
        border: 1px solid #dbeafe;
        border-radius: 16px;
        padding: 20px;
        margin-bottom: 20px;
    }

    .info-group label {
        font-size: 0.75rem;
        font-weight: 700;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 5px;
        display: block;
    }

    .form-control-static {
        background-color: white !important;
        border: 1px solid #e2e8f0;
        font-weight: 600;
        color: var(--text-dark);
        border-radius: 10px;
    }

    .category-card {
        height: 100%;
        border: 1px solid #f1f5f9;
        border-radius: 16px;
        background: white;
        transition: all 0.3s ease;
        overflow: hidden;
    }

    .category-card:hover {
        border-color: var(--hsjm-blue);
        box-shadow: 0 10px 20px rgba(0,0,0,0.05);
    }

    .category-title {
        background: #f8fafc;
        padding: 12px 20px;
        border-bottom: 1px solid #f1f5f9;
        font-weight: 700;
        color: var(--text-dark);
        font-size: 0.85rem;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .category-title i { color: var(--hsjm-blue); font-size: 1.1rem; }

    .btn-add-line {
        font-size: 0.8rem;
        color: var(--hsjm-blue);
        font-weight: 700;
        text-decoration: none;
        padding: 5px 10px;
        border-radius: 8px;
        transition: 0.2s;
    }

    .btn-add-line:hover { background: var(--hsjm-light-blue); }

    .input-time-custom { max-width: 90px; font-weight: 700; text-align: center; border-right: none; border-radius: 10px 0 0 10px; }
    .input-duree-custom {
        max-width: 62px; border-radius: 0; font-size: .78rem; font-weight: 700;
        border-left: none; border-right: none;
        background: #f0fdf4; color: #15803d; cursor: pointer;
        text-align: center; padding-left: 6px; padding-right: 2px;
    }
    .input-duree-custom:focus { background: #dcfce7; outline: none; box-shadow: none; }
    .input-duree-custom option { font-weight: 700; }
    .input-desc-custom { border-radius: 0; }
    .input-cond-custom {
        max-width: 130px; border-radius: 0; font-size: .78rem;
        border-left: 2px dashed #fbbf24; background: #fffbeb; color: #92400e;
        font-style: italic;
    }
    .input-cond-custom::placeholder { color: #d97706; }
    /* Indicateur durée sur le badge row */
    .badge-duree {
        background: #dcfce7; color: #15803d; border: 1px solid #86efac;
        padding: 1px 8px; border-radius: 20px; font-weight: 700; font-size: .62rem;
        display: inline-flex; align-items: center; gap: 3px;
    }
    /* Indicateur J+N (dose sur un jour décalé) */
    .badge-jour {
        background: #eff6ff; color: #1d4ed8; border: 1px solid #93c5fd;
        padding: 1px 8px; border-radius: 20px; font-weight: 800; font-size: .62rem;
        display: inline-flex; align-items: center; gap: 3px;
    }
    /* Ligne auto cross-day : légère teinte bleue */
    .soin-ligne[data-jour-offset]:not([data-jour-offset="0"]) .input-time-custom {
        background: #eff6ff !important; color: #1d4ed8; font-weight: 700;
    }

    /* Ligne rayée */
    .soin-ligne { position: relative; }
    .soin-ligne.barree { opacity: .5; }
    .soin-ligne.barree input,
    .soin-ligne.barree .input-group input {
        text-decoration: line-through;
        background: #fee2e2 !important;
        pointer-events: none;
    }
    .badge-corr {
        background:#ede9fe; color:#7c3aed; font-size:.65rem; font-weight:700;
        border-radius:20px; padding:1px 8px; white-space:nowrap;
    }

    /* Boutons inline */
    .btn-rayer-plan {
        border: none; background: none; color: #f87171; font-size: .7rem;
        padding: 0 6px; cursor: pointer; opacity: .7; transition: opacity .15s;
        flex-shrink: 0;
    }
    .btn-rayer-plan:hover { opacity: 1; }
    .btn-suppr { border-radius: 0 10px 10px 0; }

    .btn-save-plan {
        background: var(--hsjm-blue);
        color: white;
        padding: 12px 30px;
        border-radius: 12px;
        font-weight: 700;
        border: none;
        box-shadow: 0 4px 12px rgba(13, 110, 253, 0.2);
    }

    /* ── Modal Plan Médecin ── */
    .pm-section-title {
        font-size: .7rem; font-weight: 800; text-transform: uppercase;
        letter-spacing: .7px; margin-bottom: 8px;
    }
    .pm-card {
        background: #f8fafc; border: 1px solid #e2e8f0;
        border-radius: 14px; padding: 16px 20px; margin-bottom: 12px;
    }
    .pm-card:last-child { margin-bottom: 0; }
    .pm-card-header {
        display: flex; align-items: center; justify-content: space-between;
        margin-bottom: 10px; flex-wrap: wrap; gap: 6px;
    }
    .pm-meta { font-size: .72rem; color: #64748b; }
    .pm-content {
        font-size: .9rem; color: #1e293b; white-space: pre-wrap; line-height: 1.65;
    }
    .pm-badge-plan  { background: #dbeafe; color: #1d4ed8; border-radius: 20px; padding: 2px 10px; font-size: .68rem; font-weight: 700; }
    .pm-badge-cond  { background: #d1fae5; color: #065f46; border-radius: 20px; padding: 2px 10px; font-size: .68rem; font-weight: 700; }
    .pm-badge-diag  { background: #fef9c3; color: #713f12; border-radius: 20px; padding: 2px 10px; font-size: .68rem; font-weight: 700; }
    .pm-empty { text-align: center; padding: 30px 20px; color: #94a3b8; }
    .pm-separator { border: none; border-top: 2px dashed #e2e8f0; margin: 18px 0; }

    /* ── Badges source médicament ── */
    .soin-badge-row { min-height: 16px; line-height: 1.3; }
    .badge-ordo {
        background: #d1fae5; color: #065f46;
        padding: 1px 8px; border-radius: 20px;
        font-weight: 700; font-size: .62rem;
    }
    .badge-libre {
        background: #fef3c7; color: #92400e;
        padding: 1px 8px; border-radius: 20px;
        font-weight: 700; font-size: .62rem;
    }
    .badge-posologie { color: #64748b; font-size: .62rem; margin-left: 4px; }

    /* ── Bandeau ordonnance active ── */
    .ord-banner {
        background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
        border: 1px solid #93c5fd;
        border-radius: 14px;
        padding: 14px 20px;
        margin-bottom: 22px;
    }
    .ord-banner-header {
        cursor: pointer; user-select: none;
        display: flex; justify-content: space-between; align-items: center;
    }
    .ord-med-tag {
        display: inline-flex; align-items: center; gap: 5px;
        background: white; border: 1px solid #bfdbfe;
        border-radius: 8px; padding: 4px 10px;
        font-size: .78rem; font-weight: 600; color: #1e40af;
        margin: 3px;
    }
    .ord-med-tag .voie-badge {
        font-size: .62rem; background: #dbeafe; color: #1e3a8a;
        border-radius: 10px; padding: 1px 6px;
    }

    /* ── Indicateur "autocomplete actif" sur category-title ── */
    .cat-ordo-indicator {
        margin-left: auto;
        font-size: .6rem; background: #d1fae5; color: #065f46;
        border-radius: 10px; padding: 1px 7px; font-weight: 700;
    }

    /* ── Fréquence ── */
    .input-freq-custom {
        max-width: 68px; border-radius: 0; font-size: .72rem; font-weight: 700;
        border-left: none; border-right: none;
        background: #f5f3ff; color: #7c3aed; cursor: pointer;
        text-align: center; padding-left: 3px; padding-right: 2px;
    }
    .input-freq-custom:focus { background: #ede9fe; outline: none; box-shadow: none; }
    .input-freq-custom option { font-weight: 700; }

    /* Badge fréquence sur la ligne source ET les lignes auto */
    .badge-freq {
        background: #ede9fe; color: #7c3aed; border: 1px solid #c4b5fd;
        padding: 1px 8px; border-radius: 20px; font-weight: 700; font-size: .62rem;
        display: inline-flex; align-items: center; gap: 3px;
    }
    /* Ligne auto : heure en violet pour signaler qu'elle est calculée */
    .soin-ligne[data-auto-from] .input-time-custom {
        background: #f5f3ff !important; color: #7c3aed; font-style: italic;
    }
    .soin-ligne[data-auto-from] {
        border-left: 2px solid #c4b5fd;
        border-radius: 0 10px 10px 0;
        margin-left: 8px;
    }
</style>

<div class="container-fluid py-4 px-4">
    <form action="<?= BASE_URL ?>hospitalisation/save-plan" method="POST" id="formPlanification">
        <input type="hidden" name="patient_id" value="<?= $patient['id'] ?>">
        <input type="hidden" name="ordonnance_source_id" value="<?= (int)($ordonnanceId ?? 0) ?>">
        <input type="hidden" id="hors_ordonnance_meds" name="hors_ordonnance_meds" value="[]">

        <div class="card card-plan">
            <!-- HEADER DE LA FICHE -->
            <div class="header-fiche d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-3">
                    <div class="bg-primary bg-opacity-10 p-3 rounded-circle">
                        <i class="bi bi-calendar2-check text-primary fs-4"></i>
                    </div>
                    <div>
                        <h4 class="fw-bold mb-0">Planification des Soins Quotidiens</h4>
                        <small class="text-muted">Établissement du protocole de soins pour les prochaines 24h</small>
                    </div>
                </div>
                <div class="d-flex gap-3 align-items-center">
                    <a href="<?= BASE_URL ?>dashboard" class="btn btn-light fw-semibold rounded-pill px-3">
                        <i class="bi bi-house me-1"></i>Tableau de bord
                    </a>
                    <?php
                    $isNeonat = stripos($loc['nom_service'] ?? '', 'neonat') !== false
                             || stripos($loc['nom_service'] ?? '', 'néonat') !== false;
                    $suiviUrl = $isNeonat
                        ? BASE_URL . 'neonatologie/suivi/' . $patient['id']
                        : BASE_URL . 'hospitalisation/suivi/' . $patient['id'];
                    ?>
                    <a href="<?= $suiviUrl ?>" class="btn btn-outline-secondary fw-semibold rounded-pill px-3">
                        <i class="bi bi-arrow-left me-1"></i>Suivi patient
                    </a>
                    <!-- Bouton Traitement Prescrit -->
                    <?php
                    $hasTrait = !empty($dernieresConsultations[0]['plan_traitement'])
                             || !empty($dernieresConsultations[0]['diagnostic_principal'])
                             || !empty($ordonnanceMeds);
                    ?>
                    <button type="button"
                            class="btn fw-semibold rounded-pill px-3"
                            style="background:#fff7ed;color:#c2410c;border:1.5px solid #fed7aa;"
                            onclick="ouvrirModalSoins('modalTraitementPrescrit')"
                            <?= !$hasTrait ? 'disabled title="Aucun traitement prescrit trouvé"' : '' ?>>
                        <i class="bi bi-prescription2 me-1"></i>Traitement prescrit
                        <?php if ($hasTrait): ?>
                        <span class="badge rounded-pill ms-1" style="background:#c2410c;font-size:.65rem;">
                            <?= count($ordonnanceMeds) ?: '!' ?>
                        </span>
                        <?php endif; ?>
                    </button>

                    <!-- Bouton Plan médecin -->
                    <button type="button"
                            class="btn btn-outline-primary fw-semibold rounded-pill px-3"
                            onclick="ouvrirModalSoins('modalPlanMedecin')"
                            <?= (empty($dernierPlanTraitement) && empty($dernieresReevaluations)) ? 'disabled title="Aucun plan ni conduite à tenir enregistré"' : '' ?>>
                        <i class="bi bi-clipboard2-pulse me-1"></i>Plan médecin
                        <?php if (!empty($dernierPlanTraitement) || !empty($dernieresReevaluations)): ?>
                        <span class="badge bg-primary rounded-pill ms-1" style="font-size:.65rem;">
                            <?= count($dernieresConsultations ?? [1]) + count($dernieresReevaluations) ?>
                        </span>
                        <?php endif; ?>
                    </button>
                    <button type="submit" class="btn btn-save-plan shadow">
                        <i class="bi bi-check2-circle me-2"></i>Valider la planification
                    </button>
                </div>
            </div>

            <div class="card-body p-4">

                <!-- BANNIÈRE INFOS PATIENT -->
                <div class="patient-info-banner shadow-sm">
                    <div class="row g-4">
                        <div class="col-md-3">
                            <div class="info-group">
                                <label>Patient</label>
                                <div class="fw-bold text-dark fs-5"><?= htmlspecialchars($patient['nom'] . ' ' . $patient['prenom']) ?></div>
                                <small class="text-muted">N° Dossier: <?= $patient['dossier_numero'] ?></small>
                            </div>
                        </div>
                        <div class="col-md-1 border-start border-end border-2 border-white px-4">
                            <div class="info-group">
                                <label>Âge</label>
                                <div class="fw-bold fs-5"><?= $age ?> ans</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-group">
                                <label>Service d'accueil</label>
                                <input type="text" class="form-control form-control-sm form-control-static"
                                       value="<?= htmlspecialchars($loc['nom_service'] ?? 'Non assigné') ?>" readonly>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="info-group">
                                <label>Chambre</label>
                                <input type="text" class="form-control form-control-sm form-control-static text-center"
                                       value="<?= htmlspecialchars($loc['nom_chambre'] ?? '--') ?>" readonly>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="info-group">
                                <label>Lit n°</label>
                                <input type="text" class="form-control form-control-sm form-control-static text-center"
                                       value="<?= htmlspecialchars($loc['nom_lit'] ?? '--') ?>" readonly>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ══ BANDEAU ORDONNANCE ACTIVE ══ -->
                <?php if (!empty($hasOrdonnance)): ?>
                <div class="ord-banner">
                    <div class="ord-banner-header" data-bs-toggle="collapse" data-bs-target="#ordDetails">
                        <div>
                            <i class="bi bi-prescription2 me-2 text-primary"></i>
                            <strong class="text-primary">Ordonnance active</strong>
                            <span class="badge bg-success ms-2 rounded-pill" style="font-size:.7rem;">
                                <?= count($ordonnanceMeds) ?> médicament<?= count($ordonnanceMeds) > 1 ? 's' : '' ?> prescrit<?= count($ordonnanceMeds) > 1 ? 's' : '' ?>
                            </span>
                            <span class="ms-2 text-muted" style="font-size:.78rem;">
                                — Les zones de saisie suggèrent automatiquement ces médicaments
                            </span>
                        </div>
                        <i class="bi bi-chevron-down text-primary"></i>
                    </div>
                    <div id="ordDetails" class="collapse mt-3">
                        <div class="d-flex flex-wrap gap-1">
                            <?php foreach($ordonnanceMeds as $m): ?>
                            <div class="ord-med-tag">
                                <i class="bi bi-capsule" style="font-size:.75rem;"></i>
                                <?= htmlspecialchars($m['nom'] ?? '') ?>
                                <?php if (!empty($m['posologie'])): ?>
                                    <span class="text-muted" style="font-size:.7rem;">— <?= htmlspecialchars($m['posologie']) ?></span>
                                <?php endif; ?>
                                <?php if (!empty($m['voie'])): ?>
                                    <span class="voie-badge"><?= htmlspecialchars($m['voie']) ?></span>
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="mt-2" style="font-size:.72rem;color:#64748b;">
                            <i class="bi bi-info-circle me-1"></i>
                            Les médicaments <strong>non présents dans cette liste</strong> seront signalés
                            <span class="badge-libre">⚠ Hors ordonnance</span> et une nouvelle ordonnance sera
                            automatiquement générée pour le pharmacien à la validation.
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- GRILLE DES SOINS -->
                <?php
                /**
                 * Génère le <select> de durée pour une ligne de soin.
                 */
                function dureeSelect(string $name): string {
                    $opts = [1=>'24h',2=>'48h',3=>'72h',4=>'96h',5=>'120h',7=>'168h',10=>'240h',14=>'336h'];
                    $html = '<select name="'.htmlspecialchars($name).'" class="form-control form-control-sm input-duree-custom" title="Durée du traitement en heures (ex : 24h = 1 jour)" onchange="updateDureeBadge(this)">';
                    foreach ($opts as $v => $l) {
                        $html .= '<option value="'.$v.'"'.($v===1?' selected':'').'>'.$l.'</option>';
                    }
                    $html .= '</select>';
                    return $html;
                }

                function freqSelect(string $name, int $default = 0): string {
                    $opts = [0=>'—', 1=>'1h', 2=>'2h', 3=>'3h', 4=>'4h', 6=>'6h', 8=>'8h', 12=>'12h', 24=>'24h'];
                    $html = '<select name="'.htmlspecialchars($name).'" class="form-control form-control-sm input-freq-custom" title="Fréquence de répétition (ex: toutes les 8h)" onchange="onFreqChange(this)">';
                    foreach ($opts as $v => $l) {
                        $html .= '<option value="'.$v.'"'.($v===$default?' selected':'').'>'.$l.'</option>';
                    }
                    $html .= '</select>';
                    return $html;
                }
                ?>
                <div class="row g-4">
                    <?php
                    $medCats = ['PER_OS', 'IV', 'IM', 'SC'];
                    $categories = [
                        'PER_OS'       => ['label' => 'MÉDICAMENTS PER OS',          'icon' => 'bi-capsule',       'placeholder' => 'ex: Paracétamol 1g…'],
                        'IV'           => ['label' => 'VOIE INTRA-VEINEUSE (IV)',     'icon' => 'bi-droplet-half',  'placeholder' => 'ex: Perfusion G5%…'],
                        'IM'           => ['label' => 'INTRA-MUSCULAIRE (IM)',         'icon' => 'bi-syringe',       'placeholder' => 'Description de l\'injection…'],
                        'SC'           => ['label' => 'SOUS-CUTANÉE (SC)',             'icon' => 'bi-patch-check',   'placeholder' => 'ex: Insuline, Héparine…'],
                        'NURSING'      => ['label' => 'SOINS DE NURSING',             'icon' => 'bi-heart-pulse',   'placeholder' => 'ex: Toilette, Change, Prévention escarres…'],
                        'ALIMENTATION' => ['label' => 'RÉGIME / ALIMENTATION',        'icon' => 'bi-egg-fried',     'placeholder' => 'ex: Sans sel, Mixé…'],
                        'SURVEILLANCE' => ['label' => 'SURVEILLANCE SPÉCIFIQUE',      'icon' => 'bi-eye',           'placeholder' => 'ex: Diurèse, Conscience…'],
                    ];
                    foreach($categories as $key => $cat):
                        $isMed = in_array($key, $medCats);
                    ?>
                        <div class="col-md-6 col-lg-4">
                            <div class="category-card shadow-sm">
                                <div class="category-title">
                                    <i class="bi <?= $cat['icon'] ?>"></i><?= $cat['label'] ?>
                                    <?php if ($isMed && !empty($hasOrdonnance)): ?>
                                        <span class="cat-ordo-indicator">✓ Ordo</span>
                                    <?php endif; ?>
                                </div>

                                <div class="p-3" id="container-<?= $key ?>">
                                    <!-- Ligne par défaut -->
                                    <?php if ($isMed): ?>
                                    <div class="soin-ligne mb-2">
                                        <div class="input-group shadow-sm">
                                            <input type="time" name="soins[<?= $key ?>][heure][]"
                                                   class="form-control form-control-sm input-time-custom"
                                                   onchange="onTimeChangeWithFreq(this)">
                                            <?= dureeSelect("soins[{$key}][duree][]") ?>
                                            <?= freqSelect("soins[{$key}][freq][]") ?>
                                            <input type="text"
                                                   name="soins[<?= $key ?>][desc][]"
                                                   class="form-control form-control-sm input-desc-custom med-input"
                                                   placeholder="<?= htmlspecialchars($cat['placeholder']) ?>"
                                                   list="ordonnance-meds-datalist"
                                                   data-source=""
                                                   autocomplete="off">
                                            <input type="text" name="soins[<?= $key ?>][condition][]" class="form-control form-control-sm input-cond-custom" placeholder="⚡ si fièvre…" title="Condition d'application (optionnel)">
                                            <button type="button" class="btn btn-sm btn-outline-warning btn-rayer-plan px-2" onclick="rayerLigne(this)" title="Rayer cette ligne">
                                                <i class="bi bi-pencil-slash"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-danger btn-suppr px-2" onclick="supprimerLigneAvecAuto(this)" title="Supprimer">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                        <input type="hidden" name="soins[<?= $key ?>][jour_offset][]" value="0">
                                        <div class="soin-badge-row ps-2 pt-1"></div>
                                    </div>
                                    <?php else: ?>
                                    <div class="soin-ligne mb-2">
                                        <div class="input-group shadow-sm">
                                            <input type="time" name="soins[<?= $key ?>][heure][]"
                                                   class="form-control form-control-sm input-time-custom"
                                                   onchange="onTimeChangeWithFreq(this)">
                                            <?= dureeSelect("soins[{$key}][duree][]") ?>
                                            <?= freqSelect("soins[{$key}][freq][]") ?>
                                            <input type="text" name="soins[<?= $key ?>][desc][]" class="form-control form-control-sm input-desc-custom" placeholder="<?= htmlspecialchars($cat['placeholder']) ?>">
                                            <input type="text" name="soins[<?= $key ?>][condition][]" class="form-control form-control-sm input-cond-custom" placeholder="⚡ si fièvre…" title="Condition d'application (optionnel)">
                                            <button type="button" class="btn btn-sm btn-outline-warning btn-rayer-plan px-2" onclick="rayerLigne(this)" title="Rayer cette ligne">
                                                <i class="bi bi-pencil-slash"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-danger btn-suppr px-2" onclick="supprimerLigneAvecAuto(this)" title="Supprimer">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                        <input type="hidden" name="soins[<?= $key ?>][jour_offset][]" value="0">
                                        <div class="soin-badge-row ps-2 pt-1"></div>
                                    </div>
                                    <?php endif; ?>
                                </div>

                                <div class="px-3 pb-3">
                                    <button type="button" class="btn btn-add-line"
                                            data-cat="<?= htmlspecialchars($key) ?>"
                                            data-placeholder="<?= htmlspecialchars($cat['placeholder']) ?>"
                                            onclick="addRow(this.dataset.cat, this.dataset.placeholder)">
                                        <i class="bi bi-plus-circle-dotted me-1"></i>Ajouter une ligne
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

            </div><!-- /card-body -->

            <div class="card-footer bg-light border-0 p-4 text-center">
                <p class="text-muted small mb-0">
                    <i class="bi bi-info-circle me-1"></i>
                    Après validation, ces soins apparaîtront dans la checklist quotidienne de l'infirmier(e) de service.
                    <?php if (!empty($hasOrdonnance)): ?>
                    Les médicaments hors ordonnance seront automatiquement transmis à la pharmacie.
                    <?php endif; ?>
                </p>
            </div>
        </div><!-- /card-plan -->
    </form>
</div>

<!-- Datalist global pour l'autocomplétion des médicaments ordonnés -->
<datalist id="ordonnance-meds-datalist">
    <?php foreach($ordonnanceMeds ?? [] as $m): ?>
    <option value="<?= htmlspecialchars($m['nom'] ?? '') ?>"></option>
    <?php endforeach; ?>
</datalist>

<script>
/* ════════════════════════════════════════════════════════
   Ouverture des modales médicales via Bootstrap JS API
   (plus fiable que data-bs-toggle dans certains contextes)
════════════════════════════════════════════════════════ */
function ouvrirModalSoins(id) {
    const el = document.getElementById(id);
    if (!el) { console.warn('Modal introuvable :', id); return; }
    // Priorité 1 : Bootstrap 5 API
    if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
        const m = bootstrap.Modal.getOrCreateInstance(el);
        m.show();
    }
    // Fallback : jQuery si présent
    else if (typeof $ !== 'undefined') {
        $('#' + id).modal('show');
    }
    // Fallback manuel : affichage CSS direct
    else {
        el.style.display = 'block';
        el.classList.add('show');
        document.body.classList.add('modal-open');
        // Backdrop
        const bd = document.createElement('div');
        bd.className = 'modal-backdrop fade show';
        bd.id = 'modalBackdrop_' + id;
        document.body.appendChild(bd);
        el.querySelector('[data-bs-dismiss="modal"], .btn-close')
          ?.addEventListener('click', function() {
              el.style.display = '';
              el.classList.remove('show');
              document.body.classList.remove('modal-open');
              document.getElementById('modalBackdrop_' + id)?.remove();
          }, { once: true });
    }
}

/* ════════════════════════════════════════════════════════
   Données ordonnance (injectées par PHP)
════════════════════════════════════════════════════════ */
const ORDONNANCE_MEDS = <?= $ordonnanceMedsJson ?? '[]' ?>;
const HAS_ORDONNANCE  = <?= (!empty($hasOrdonnance)) ? 'true' : 'false' ?>;
const MED_CATS        = ['PER_OS', 'IV', 'IM', 'SC'];
/* Date de début de l'hospitalisation — utilisée comme J0 pour les badges J+N */
const DATE_ADMISSION  = <?= json_encode(substr($loc['date_admission'] ?? date('Y-m-d'), 0, 10)) ?>;

/* ────── Select durée réutilisable ────── */
function buildDureeSelect(cat, duree) {
    duree = parseInt(duree) || 1;
    const opts = [[1,'24h'],[2,'48h'],[3,'72h'],[4,'96h'],[5,'120h'],[7,'168h'],[10,'240h'],[14,'336h']];
    let html = `<select name="soins[${cat}][duree][]" class="form-control form-control-sm input-duree-custom" title="Durée du traitement en heures" onchange="updateDureeBadge(this)">`;
    opts.forEach(([v, l]) => {
        html += `<option value="${v}"${v === duree ? ' selected' : ''}>${l}</option>`;
    });
    html += '</select>';
    return html;
}

/* ────── Select fréquence réutilisable ────── */
function buildFreqSelect(cat, freq) {
    freq = parseInt(freq) || 0;
    const opts = [[0,'—'],[1,'1h'],[2,'2h'],[3,'3h'],[4,'4h'],[6,'6h'],[8,'8h'],[12,'12h'],[24,'24h']];
    let html = `<select name="soins[${cat}][freq][]" class="form-control form-control-sm input-freq-custom" title="Fréquence de répétition" onchange="onFreqChange(this)">`;
    opts.forEach(([v, l]) => {
        html += `<option value="${v}"${v === freq ? ' selected' : ''}>${l}</option>`;
    });
    html += '</select>';
    return html;
}

/* ────── Badge durée sur le badge-row ────── */
function updateDureeBadge(select) {
    const d      = parseInt(select.value) || 1;
    const heures = d * 24;
    const ligne  = select.closest('.soin-ligne');
    if (!ligne) return;
    select.style.background = d === 1 ? '#f0fdf4' : '#dcfce7';
    select.style.fontWeight = d > 1   ? '900'     : '700';
    const badgeRow = ligne.querySelector('.soin-badge-row');
    if (!badgeRow) return;
    const old = badgeRow.querySelector('.badge-duree');
    if (old) old.remove();
    // Toujours afficher la durée (même 24h) pour rappel visuel
    const span = document.createElement('span');
    span.className = 'badge-duree';
    // Fin = date de début + d jours (ex: 10h00 + 24h = lendemain 09h59)
    span.innerHTML = `<i class="bi bi-clock-history"></i> ${heures}h — du ${formatDateOffset(0)} au ${formatDateOffset(d)}`;
    badgeRow.insertAdjacentElement('afterbegin', span);

    // Si une fréquence est déjà appliquée, recalculer les lignes auto
    const freqSel = ligne.querySelector('.input-freq-custom');
    if (freqSel && parseInt(freqSel.value)) applyFrequence(freqSel);
}

function formatDateOffset(n) {
    // J0 = date d'admission du patient (pas forcément aujourd'hui)
    const base = DATE_ADMISSION ? new Date(DATE_ADMISSION + 'T00:00:00') : new Date();
    base.setDate(base.getDate() + n);
    return base.toLocaleDateString('fr-FR', {day:'2-digit', month:'2-digit'});
}

/* ────── Échapper le HTML dans les chaînes JS ────── */
function escHtml(str) {
    const d = document.createElement('div');
    d.textContent = str || '';
    return d.innerHTML;
}

/* ════════════════════════════════════════════════════════
   Badge source médicament
════════════════════════════════════════════════════════ */
function updateBadge(input) {
    const ligne    = input.closest('.soin-ligne');
    const badgeRow = ligne ? ligne.querySelector('.soin-badge-row') : null;
    if (!badgeRow) return;

    const val    = input.value.trim();
    const source = input.dataset.source || '';

    if (!val || !source) { badgeRow.innerHTML = ''; return; }

    if (source === 'ordonnance') {
        const match = ORDONNANCE_MEDS.find(m => (m.nom || '').toLowerCase() === val.toLowerCase());
        let html = `<span class="badge-ordo">✓ Ordonnance</span>`;
        if (match && match.posologie) {
            html += `<span class="badge-posologie">${escHtml(match.posologie)}</span>`;
        }
        if (match && match.voie) {
            html += `<span class="badge-posologie">— ${escHtml(match.voie)}</span>`;
        }
        badgeRow.innerHTML = html;
    } else {
        badgeRow.innerHTML = `<span class="badge-libre">⚠ Hors ordonnance — sera transmis à la pharmacie</span>`;
    }
}

/* ════════════════════════════════════════════════════════
   Vérifier la source d'un médicament saisi
════════════════════════════════════════════════════════ */
function checkMedSource(input) {
    const val = input.value.trim();
    if (!val) {
        input.dataset.source = '';
        updateBadge(input);
        return;
    }
    const match = ORDONNANCE_MEDS.find(m =>
        m.nom && m.nom.toLowerCase().trim() === val.toLowerCase().trim()
    );
    input.dataset.source = match ? 'ordonnance' : 'libre';
    updateBadge(input);
}

/* ════════════════════════════════════════════════════════
   Initialiser l'autocomplétion sur un input médicament
════════════════════════════════════════════════════════ */
function initMedInput(input) {
    if (!HAS_ORDONNANCE || !ORDONNANCE_MEDS.length) return;

    input.addEventListener('input',  function() { checkMedSource(this); });
    input.addEventListener('change', function() { checkMedSource(this); });
    input.addEventListener('blur',   function() { checkMedSource(this); });
}

/* ════════════════════════════════════════════════════════
   Initialiser TOUS les inputs médicaments au chargement
════════════════════════════════════════════════════════ */
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.med-input').forEach(initMedInput);
    // Initialiser les badges durée sur les selects déjà présents (valeur > 1 si pré-rempli)
    document.querySelectorAll('.input-duree-custom').forEach(sel => {
        if (parseInt(sel.value) > 1) updateDureeBadge(sel);
    });
});

/* ════════════════════════════════════════════════════════
   Construire le HTML d'une ligne médicament
   freq = 0  → pas de répétition (ligne auto : freq forcé à 0)
════════════════════════════════════════════════════════ */
function buildMedRow(cat, placeholder, heure, desc, cond, withCorrLabel, duree, freq) {
    const corrSpan = withCorrLabel
        ? `<span class="input-group-text border-0 px-2" style="background:#ede9fe;color:#7c3aed;font-size:.7rem;border-radius:10px 0 0 10px;">CORR.</span>`
        : '';
    const timeStyle = withCorrLabel ? 'style="border-radius:0;"' : '';
    return `
        <div class="soin-ligne mb-2">
            <div class="input-group shadow-sm">
                ${corrSpan}
                <input type="time" name="soins[${cat}][heure][]"
                       class="form-control form-control-sm input-time-custom"
                       value="${heure || ''}" ${timeStyle}
                       onchange="onTimeChangeWithFreq(this)">
                ${buildDureeSelect(cat, duree || 1)}
                ${buildFreqSelect(cat, freq != null ? freq : 0)}
                <input type="text" name="soins[${cat}][desc][]"
                       class="form-control form-control-sm input-desc-custom med-input"
                       placeholder="${placeholder}"
                       list="ordonnance-meds-datalist"
                       data-source=""
                       autocomplete="off"
                       value="${escHtml(desc || '')}">
                <input type="text" name="soins[${cat}][condition][]"
                       class="form-control form-control-sm input-cond-custom"
                       placeholder="⚡ si fièvre…"
                       value="${escHtml(cond || '')}">
                <button type="button" class="btn btn-sm btn-outline-warning btn-rayer-plan px-2"
                        onclick="rayerLigne(this)" title="Rayer cette ligne">
                    <i class="bi bi-pencil-slash"></i>
                </button>
                <button type="button" class="btn btn-sm btn-outline-danger btn-suppr px-2"
                        onclick="supprimerLigneAvecAuto(this)" title="Supprimer">
                    <i class="bi bi-trash"></i>
                </button>
            </div>
            <input type="hidden" name="soins[${cat}][jour_offset][]" value="0">
            <div class="soin-badge-row ps-2 pt-1"></div>
        </div>`;
}

/* ════════════════════════════════════════════════════════
   Construire le HTML d'une ligne de soin standard (non méd.)
════════════════════════════════════════════════════════ */
function buildSoinRow(cat, placeholder, heure, desc, cond, withCorrLabel, duree, freq) {
    const corrSpan = withCorrLabel
        ? `<span class="input-group-text border-0 px-2" style="background:#ede9fe;color:#7c3aed;font-size:.7rem;border-radius:10px 0 0 10px;">CORR.</span>`
        : '';
    const timeStyle = withCorrLabel ? 'style="border-radius:0;"' : '';
    return `
        <div class="soin-ligne mb-2">
            <div class="input-group shadow-sm">
                ${corrSpan}
                <input type="time" name="soins[${cat}][heure][]"
                       class="form-control form-control-sm input-time-custom"
                       value="${heure || ''}" ${timeStyle}
                       onchange="onTimeChangeWithFreq(this)">
                ${buildDureeSelect(cat, duree || 1)}
                ${buildFreqSelect(cat, freq != null ? freq : 0)}
                <input type="text" name="soins[${cat}][desc][]"
                       class="form-control form-control-sm input-desc-custom"
                       placeholder="${placeholder}"
                       value="${escHtml(desc || '')}">
                <input type="text" name="soins[${cat}][condition][]"
                       class="form-control form-control-sm input-cond-custom"
                       placeholder="⚡ si fièvre…"
                       value="${escHtml(cond || '')}">
                <button type="button" class="btn btn-sm btn-outline-warning btn-rayer-plan px-2"
                        onclick="rayerLigne(this)" title="Rayer cette ligne">
                    <i class="bi bi-pencil-slash"></i>
                </button>
                <button type="button" class="btn btn-sm btn-outline-danger btn-suppr px-2"
                        onclick="supprimerLigneAvecAuto(this)" title="Supprimer">
                    <i class="bi bi-trash"></i>
                </button>
            </div>
            <input type="hidden" name="soins[${cat}][jour_offset][]" value="0">
            <div class="soin-badge-row ps-2 pt-1"></div>
        </div>`;
}

/* ════════════════════════════════════════════════════════
   Ajouter une ligne de soin
════════════════════════════════════════════════════════ */
function addRow(cat, placeholder) {
    const container = document.getElementById('container-' + cat);
    const wrapper   = document.createElement('div');
    const isMed     = MED_CATS.includes(cat);

    if (isMed) {
        wrapper.innerHTML = buildMedRow(cat, placeholder, '', '', '', false, 1, 0);
        const newRow = wrapper.firstElementChild;
        container.appendChild(newRow);
        newRow.querySelectorAll('.med-input').forEach(initMedInput);
        newRow.querySelector('input[type=time]').focus();
    } else {
        wrapper.innerHTML = buildSoinRow(cat, placeholder, '', '', '', false, 1, 0);
        container.appendChild(wrapper.firstElementChild);
    }
}

/* ════════════════════════════════════════════════════════
   Rayer une ligne erronée et créer la correction
════════════════════════════════════════════════════════ */
function rayerLigne(btn) {
    const ligne     = btn.closest('.soin-ligne');
    const container = ligne.parentElement;

    // Récupérer les valeurs avant de barrer
    const heure = ligne.querySelector('input[type=time]').value;
    const desc  = ligne.querySelector('.input-desc-custom').value;
    const cond  = ligne.querySelector('.input-cond-custom')?.value || '';
    const cat   = ligne.querySelector('input[type=time]')?.name?.match(/soins\[([^\]]+)\]/)?.[1]
               || container.id.replace('container-', '');
    const placeholder = ligne.querySelector('.input-desc-custom')?.placeholder || '';

    // Barrer la ligne
    ligne.classList.add('barree');
    ligne.querySelectorAll('input').forEach(i => { i.disabled = true; i.removeAttribute('name'); });
    // Retirer les boutons d'action
    ligne.querySelectorAll('button').forEach(b => b.remove());
    // Vider le badge row (si présent)
    const br = ligne.querySelector('.soin-badge-row');
    if (br) br.innerHTML = '';

    // Ajouter note visuelle
    const note = document.createElement('div');
    note.className = 'd-flex align-items-center gap-2 px-1 mb-1';
    note.innerHTML = `<span style="font-size:.65rem;color:#dc2626;font-weight:700;">
        <i class="bi bi-slash-circle me-1"></i>Ligne annulée (erreur de planification)
    </span>`;
    ligne.after(note);

    // Créer la ligne corrigée (pré-remplie)
    const isMed = MED_CATS.includes(cat);
    const wrapper = document.createElement('div');

    if (isMed) {
        wrapper.innerHTML = buildMedRow(cat, placeholder, heure, desc, cond, true);
        const corrRow = wrapper.firstElementChild;
        note.after(corrRow);
        corrRow.querySelectorAll('.med-input').forEach(inp => {
            initMedInput(inp);
            if (inp.value) checkMedSource(inp);
        });
        corrRow.querySelector('.input-desc-custom')?.focus();
    } else {
        wrapper.innerHTML = buildSoinRow(cat, placeholder, heure, desc, cond, true);
        note.after(wrapper.firstElementChild);
    }
}

/* ════════════════════════════════════════════════════════
   FRÉQUENCE INTELLIGENTE
════════════════════════════════════════════════════════ */

// Compteur pour générer des IDs de ligne uniques
let _lineIdCounter = 0;

function getLineId(ligne) {
    if (!ligne.dataset.lineId) {
        ligne.dataset.lineId = 'ln_' + (Date.now()) + '_' + (++_lineIdCounter);
    }
    return ligne.dataset.lineId;
}

/** Supprimer toutes les lignes auto issues d'une ligne source */
function removeAutoRows(sourceId, container) {
    container.querySelectorAll(`[data-auto-from="${sourceId}"]`).forEach(el => el.remove());
}

/** Badge fréquence sur la ligne source */
function setFreqBadge(ligne, freq) {
    const badgeRow = ligne.querySelector('.soin-badge-row');
    if (!badgeRow) return;
    const old = badgeRow.querySelector('.badge-freq');
    if (old) old.remove();
    if (freq > 0) {
        const span = document.createElement('span');
        span.className = 'badge-freq';
        span.innerHTML = `<i class="bi bi-arrow-repeat"></i> Toutes les ${freq}h`;
        badgeRow.insertAdjacentElement('afterbegin', span);
    }
}

/**
 * Calcule et génère les lignes auto pour une ligne source.
 * Génère toutes les occurrences dans la fenêtre 00:00 – 23:59.
 */
function applyFrequence(trigger) {
    const ligne = trigger.closest('.soin-ligne');
    if (!ligne || ligne.dataset.autoFrom) return; // ne pas re-générer depuis une ligne auto

    const container = ligne.parentElement;
    const lineId    = getLineId(ligne);

    // Nettoyer les anciennes lignes auto de cette source
    removeAutoRows(lineId, container);

    const freqSel = ligne.querySelector('.input-freq-custom');
    const timeInp = ligne.querySelector('input[type=time]');
    if (!freqSel || !timeInp) return;

    const freq    = parseInt(freqSel.value) || 0;
    const timeVal = timeInp.value;

    // Mettre à jour le badge de la ligne source
    setFreqBadge(ligne, freq);

    if (!freq || !timeVal) return;

    const parts = timeVal.split(':');
    const h = parseInt(parts[0]);
    const m = parseInt(parts[1]);
    if (isNaN(h) || isNaN(m)) return;

    // Récupérer les données de la ligne source
    const catMatch  = timeInp.name ? timeInp.name.match(/soins\[([^\]]+)\]/) : null;
    const cat       = catMatch ? catMatch[1] : '';
    const isMed     = MED_CATS.includes(cat);
    const desc      = ligne.querySelector('.input-desc-custom')?.value || '';
    const cond      = ligne.querySelector('.input-cond-custom')?.value || '';
    const duree     = parseInt(ligne.querySelector('.input-duree-custom')?.value) || 1;
    const ph        = ligne.querySelector('.input-desc-custom')?.placeholder || '';

    const startMin  = h * 60 + m;        // heure de début en minutes depuis minuit

    // ── Fenêtre temporelle : startMin → startMin + duree × 24h
    const windowMin = duree * 24 * 60;  // durée totale en minutes
    const endMin    = startMin + windowMin;

    let nextMin  = startMin + freq * 60;
    let insertRef = ligne;

    while (nextMin < endMin) {
        // Décalage en jours complets depuis minuit du J0
        const dayOffset    = Math.floor(nextMin / (24 * 60));
        const minuteInDay  = nextMin % (24 * 60);
        const nh           = Math.floor(minuteInDay / 60);
        const nm           = minuteInDay % 60;
        const timeStr      = String(nh).padStart(2,'0') + ':' + String(nm).padStart(2,'0');

        const wrapper = document.createElement('div');
        // Les lignes auto ont toujours duree=1 et freq=0 (dose unique, positionnée par jour_offset)
        if (isMed) {
            wrapper.innerHTML = buildMedRow(cat, ph, timeStr, desc, cond, false, 1, 0);
        } else {
            wrapper.innerHTML = buildSoinRow(cat, ph, timeStr, desc, cond, false, 1, 0);
        }

        const newRow = wrapper.firstElementChild;
        newRow.dataset.autoFrom   = lineId;   // lien vers la ligne source
        newRow.dataset.jourOffset = dayOffset; // décalage en jours

        // Mettre à jour le champ jour_offset déjà présent dans le template de la ligne
        // (buildMedRow / buildSoinRow injecte toujours un champ jour_offset=0 ; on l'écrase ici
        //  au lieu d'en ajouter un second, ce qui décalerait tous les indices PHP)
        const existingJourHidden = newRow.querySelector('input[name*="jour_offset"]');
        if (existingJourHidden) {
            existingJourHidden.value = dayOffset;
        } else {
            const jourHidden = document.createElement('input');
            jourHidden.type  = 'hidden';
            jourHidden.name  = `soins[${cat}][jour_offset][]`;
            jourHidden.value = dayOffset;
            newRow.appendChild(jourHidden);
        }

        insertRef.after(newRow);
        insertRef = newRow;

        // initMedInput / checkMedSource d'abord : checkMedSource appelle updateBadge
        // qui réécrit badgeRow.innerHTML — les badges J+N / freq doivent être ajoutés APRÈS
        if (isMed) {
            newRow.querySelectorAll('.med-input').forEach(inp => {
                initMedInput(inp);
                if (inp.value) checkMedSource(inp);
            });
        }

        // Badges ajoutés EN DERNIER pour ne pas être effacés par updateBadge
        const badgeRow = newRow.querySelector('.soin-badge-row');
        if (badgeRow) {
            // Badge J+N en premier (insertAdjacentElement afterbegin = avant tout)
            if (dayOffset > 0) {
                const jBadge = document.createElement('span');
                jBadge.className = 'badge-jour';
                jBadge.innerHTML = `<i class="bi bi-calendar2-plus"></i> J+${dayOffset} · ${formatDateOffset(dayOffset)}`;
                badgeRow.insertAdjacentElement('afterbegin', jBadge);
            }
        }

        nextMin += freq * 60;
    }
}

/** Appelé quand l'utilisateur change le select fréquence */
function onFreqChange(select) {
    applyFrequence(select);
}

/** Appelé quand l'heure change sur une ligne qui a déjà une fréquence */
function onTimeChangeWithFreq(input) {
    const ligne = input.closest('.soin-ligne');
    if (!ligne || ligne.dataset.autoFrom) return;
    const freqSel = ligne.querySelector('.input-freq-custom');
    if (!freqSel || !parseInt(freqSel.value)) return;
    applyFrequence(input);
}

/** Supprimer une ligne source ET toutes ses lignes auto */
function supprimerLigneAvecAuto(btn) {
    const ligne = btn.closest('.soin-ligne');
    if (!ligne) return;
    const lineId    = ligne.dataset.lineId;
    const container = ligne.parentElement;
    if (lineId) removeAutoRows(lineId, container);
    ligne.remove();
}

/* ════════════════════════════════════════════════════════
   Collecte des médicaments hors ordonnance avant soumission
════════════════════════════════════════════════════════ */
document.getElementById('formPlanification').addEventListener('submit', function() {
    // ── Normalisation avant envoi ──────────────────────────────────────────────
    // Pour les lignes SOURCE ayant une fréquence, forcer duree=1 :
    // les lignes auto gèrent déjà chaque dose à sa date exacte (via jour_offset).
    // Sans ce correctif, le backend créerait des doublons (ex: 10h J1 créé deux fois).
    document.querySelectorAll('.soin-ligne:not([data-auto-from])').forEach(function(ligne) {
        const freqSel  = ligne.querySelector('.input-freq-custom');
        const dureeSel = ligne.querySelector('.input-duree-custom');
        if (freqSel && dureeSel && parseInt(freqSel.value) > 0) {
            dureeSel.value = '1';
        }
    });

    if (!HAS_ORDONNANCE) return; // rien à faire si pas d'ordonnance

    const horsMeds = [];

    document.querySelectorAll('.med-input').forEach(function(input) {
        // Ignorer les lignes barrées (disabled)
        if (input.disabled) return;
        const val    = (input.value || '').trim();
        const source = input.dataset.source || '';
        if (val && source === 'libre') {
            const ligne    = input.closest('.soin-ligne') || input.closest('.input-group');
            const heure    = ligne ? (ligne.querySelector('input[type=time]')?.value || '') : '';
            const cond     = ligne ? (ligne.querySelector('.input-cond-custom')?.value || '') : '';
            const catMatch = input.name ? input.name.match(/soins\[([^\]]+)\]/) : null;
            const cat      = catMatch ? catMatch[1] : '';
            horsMeds.push({ nom: val, heure: heure, condition: cond, categorie: cat });
        }
    });

    document.getElementById('hors_ordonnance_meds').value = JSON.stringify(horsMeds);
});
</script>

<!-- ══ MODAL : PLAN MÉDECIN & CONDUITES À TENIR ══ -->
<!-- ══════════════════════════════════════════════════════════════
     MODALE TRAITEMENT PRESCRIT
     Donne à l'infirmier une vue claire du traitement prescrit par
     le médecin lors de la dernière consultation.
     ══════════════════════════════════════════════════════════════ -->
<div class="modal fade" id="modalTraitementPrescrit" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg" style="border-radius:18px;overflow:hidden;">

            <!-- Header -->
            <div class="modal-header border-0 py-3 px-4"
                 style="background:linear-gradient(135deg,#ea580c,#f97316);">
                <div class="d-flex align-items-center gap-3 w-100">
                    <div style="width:40px;height:40px;background:rgba(255,255,255,.2);border-radius:12px;
                                display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <i class="bi bi-prescription2 text-white fs-5"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h5 class="modal-title fw-bold text-white mb-0">Traitement Prescrit</h5>
                        <small class="text-white opacity-75">
                            <?= htmlspecialchars($patient['nom'] . ' ' . $patient['prenom']) ?>
                            &nbsp;·&nbsp; <?= htmlspecialchars($patient['dossier_numero']) ?>
                        </small>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
            </div>

            <div class="modal-body p-4">
            <?php
            $consT = $dernieresConsultations[0] ?? null;
            if (!$consT && empty($ordonnanceMeds)):
            ?>
                <div class="text-center py-4 text-muted">
                    <i class="bi bi-clipboard-x fs-1 opacity-25 d-block mb-2"></i>
                    Aucun traitement prescrit trouvé pour ce patient.
                </div>
            <?php else: ?>

                <?php if ($consT): ?>
                <!-- En-tête consultation source -->
                <div class="d-flex align-items-center gap-2 mb-3 pb-3" style="border-bottom:1px solid #f1f5f9;">
                    <i class="bi bi-stethoscope text-orange" style="color:#ea580c;"></i>
                    <span class="small text-muted">
                        Consultation du
                        <strong><?= date('d/m/Y', strtotime($consT['date_consultation'])) ?></strong>
                        <?php if (!empty($consT['medecin_nom'])): ?>
                        — Dr <?= htmlspecialchars($consT['medecin_nom'] . ' ' . $consT['medecin_prenom']) ?>
                        <?php if (!empty($consT['medecin_specialite'])): ?>
                        <span class="badge ms-1" style="background:#f1f5f9;color:#64748b;font-size:.65rem;"><?= htmlspecialchars($consT['medecin_specialite']) ?></span>
                        <?php endif; ?>
                        <?php endif; ?>
                    </span>
                </div>

                <!-- Diagnostic principal -->
                <?php if (!empty($consT['diagnostic_principal'])): ?>
                <div class="mb-3 p-3 rounded-3" style="background:#fef2f2;border-left:4px solid #dc2626;">
                    <div class="small fw-bold text-danger mb-1" style="font-size:.7rem;text-transform:uppercase;letter-spacing:.5px;">
                        <i class="bi bi-heart-pulse-fill me-1"></i>Diagnostic principal
                    </div>
                    <div class="fw-bold" style="color:#991b1b;font-size:1rem;">
                        <?= nl2br(htmlspecialchars(strtoupper($consT['diagnostic_principal']))) ?>
                    </div>
                </div>
                <?php endif; ?>
                <?php endif; ?>

                <!-- Plan de traitement (texte libre médecin) -->
                <?php if (!empty($consT['plan_traitement'])): ?>
                <div class="mb-3 p-3 rounded-3" style="background:#f0fdf4;border-left:4px solid #16a34a;">
                    <div class="small fw-bold mb-2" style="font-size:.7rem;text-transform:uppercase;letter-spacing:.5px;color:#15803d;">
                        <i class="bi bi-file-medical-fill me-1"></i>Plan de traitement prescrit
                    </div>
                    <div style="font-size:.9rem;line-height:1.7;color:#14532d;white-space:pre-wrap;"><?= htmlspecialchars($consT['plan_traitement']) ?></div>
                </div>
                <?php endif; ?>

                <!-- Ordonnance médicaments (liste structurée) -->
                <?php if (!empty($ordonnanceMeds)): ?>
                <div class="mb-3">
                    <div class="small fw-bold mb-2" style="font-size:.7rem;text-transform:uppercase;letter-spacing:.5px;color:#c2410c;">
                        <i class="bi bi-capsule-pill me-1"></i>Médicaments de l'ordonnance
                        <span class="badge rounded-pill ms-1" style="background:#fff7ed;color:#c2410c;border:1px solid #fed7aa;"><?= count($ordonnanceMeds) ?></span>
                    </div>
                    <div class="d-flex flex-column gap-2">
                        <?php foreach ($ordonnanceMeds as $i => $m): ?>
                        <div class="d-flex align-items-start gap-3 p-3 rounded-3"
                             style="background:#fff7ed;border:1px solid #fed7aa;">
                            <div class="rounded-circle fw-bold text-white d-flex align-items-center justify-content-center flex-shrink-0"
                                 style="width:28px;height:28px;background:#ea580c;font-size:.75rem;">
                                <?= $i + 1 ?>
                            </div>
                            <div class="flex-grow-1">
                                <div class="fw-semibold" style="color:#7c2d12;font-size:.92rem;">
                                    <?= htmlspecialchars($m['nom'] ?? '') ?>
                                </div>
                                <div class="d-flex flex-wrap gap-2 mt-1">
                                    <?php if (!empty($m['posologie'])): ?>
                                    <span class="badge" style="background:#fef9c3;color:#a16207;font-size:.72rem;font-weight:600;">
                                        <i class="bi bi-clock me-1"></i><?= htmlspecialchars($m['posologie']) ?>
                                    </span>
                                    <?php endif; ?>
                                    <?php if (!empty($m['voie'])): ?>
                                    <span class="badge" style="background:#dbeafe;color:#1d4ed8;font-size:.72rem;font-weight:600;">
                                        <i class="bi bi-arrow-right-circle me-1"></i><?= htmlspecialchars($m['voie']) ?>
                                    </span>
                                    <?php endif; ?>
                                    <?php if (!empty($m['duree'])): ?>
                                    <span class="badge" style="background:#f0fdf4;color:#15803d;font-size:.72rem;font-weight:600;">
                                        <i class="bi bi-calendar-range me-1"></i><?= htmlspecialchars($m['duree']) ?>
                                    </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Traitement non médicamenteux -->
                <?php if (!empty($consT['traitement_non_medicamenteux'])): ?>
                <div class="mb-3 p-3 rounded-3" style="background:#f0f9ff;border-left:4px solid #0284c7;">
                    <div class="small fw-bold mb-1" style="font-size:.7rem;text-transform:uppercase;letter-spacing:.5px;color:#0369a1;">
                        <i class="bi bi-activity me-1"></i>Traitement non médicamenteux
                    </div>
                    <div style="font-size:.88rem;color:#0c4a6e;white-space:pre-wrap;"><?= htmlspecialchars($consT['traitement_non_medicamenteux']) ?></div>
                </div>
                <?php endif; ?>

                <!-- Surveillance -->
                <?php if (!empty($consT['surveillance'])): ?>
                <div class="p-3 rounded-3" style="background:#faf5ff;border-left:4px solid #7c3aed;">
                    <div class="small fw-bold mb-1" style="font-size:.7rem;text-transform:uppercase;letter-spacing:.5px;color:#6d28d9;">
                        <i class="bi bi-eye-fill me-1"></i>Surveillance prescrite
                    </div>
                    <div style="font-size:.88rem;color:#3b0764;white-space:pre-wrap;"><?= htmlspecialchars($consT['surveillance']) ?></div>
                </div>
                <?php endif; ?>

                <!-- Aucun détail de traitement malgré consultation trouvée -->
                <?php if ($consT && empty($consT['plan_traitement']) && empty($consT['traitement_non_medicamenteux']) && empty($consT['surveillance']) && empty($ordonnanceMeds)): ?>
                <div class="alert mb-0 rounded-3 d-flex align-items-start gap-2"
                     style="background:#eff6ff;border:1px solid #bfdbfe;color:#1e40af;">
                    <i class="bi bi-info-circle-fill flex-shrink-0 mt-1"></i>
                    <div class="small">
                        Le médecin n'a pas encore saisi de plan de traitement ni d'ordonnance médicamenteuse pour ce patient.
                        Contactez le médecin responsable pour obtenir les prescriptions.
                    </div>
                </div>
                <?php endif; ?>

            <?php endif; ?>
            </div><!-- /.modal-body -->

            <div class="modal-footer border-0 px-4 pb-3" style="background:#f8fafc;">
                <small class="text-muted me-auto">
                    <i class="bi bi-info-circle me-1"></i>
                    Traitement issu de la dernière consultation médicale enregistrée.
                </small>
                <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">
                    Fermer
                </button>
            </div>
        </div>
    </div>
</div>


<div class="modal fade" id="modalPlanMedecin" tabindex="-1" aria-labelledby="modalPlanMedecinLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg" style="border-radius:20px;overflow:hidden;">

            <!-- En-tête -->
            <div class="modal-header border-0"
                 style="background:linear-gradient(135deg,#0d6efd 0%,#0ea5e9 100%);padding:20px 28px;">
                <div>
                    <h5 class="modal-title fw-bold text-white mb-0" id="modalPlanMedecinLabel">
                        <i class="bi bi-clipboard2-pulse me-2"></i>Plan médical &amp; Conduites à tenir
                    </h5>
                    <small class="text-white opacity-75">
                        <?= htmlspecialchars($patient['nom'] . ' ' . $patient['prenom']) ?>
                        &nbsp;·&nbsp; <?= htmlspecialchars($patient['dossier_numero']) ?>
                    </small>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body" style="padding:24px 28px;">

                <?php
                $hasConsults = !empty($dernieresConsultations ?? []);
                $hasRevs     = !empty($dernieresReevaluations);
                ?>

                <?php if (!$hasConsults && !$hasRevs): ?>
                <div class="pm-empty">
                    <i class="bi bi-clipboard-x" style="font-size:2.5rem;opacity:.3;display:block;margin-bottom:12px;"></i>
                    <div class="fw-semibold">Aucun plan de traitement ni conduite à tenir enregistré</div>
                    <small>Remplissez une consultation ou une réévaluation médicale pour voir les informations ici.</small>
                </div>

                <?php else: ?>

                <!-- ══ CONSULTATIONS MÉDICALES ══ -->
                <?php if ($hasConsults): ?>
                <div class="mb-3">
                    <div class="pm-section-title text-primary" style="font-size:.75rem;text-transform:uppercase;font-weight:800;letter-spacing:.5px;color:#1d4ed8;margin-bottom:10px;">
                        <i class="bi bi-stethoscope me-1"></i>
                        Plan de traitement — Consultations médicales
                        <span class="badge rounded-pill ms-1" style="background:#eff6ff;color:#1d4ed8;font-size:.65rem;"><?= count($dernieresConsultations) ?></span>
                    </div>

                    <?php foreach ($dernieresConsultations as $idx => $cons): ?>
                    <div class="pm-card mb-3" style="border-left:4px solid #0d6efd;">

                        <!-- En-tête consultation -->
                        <div class="pm-card-header">
                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                <span class="pm-badge-plan">
                                    <i class="bi bi-clipboard2-pulse me-1"></i>
                                    Consultation <?= $idx === 0 ? '<strong>la plus récente</strong>' : 'précédente' ?>
                                </span>
                                <?php if (!empty($cons['type_consultation'])): ?>
                                <span class="pm-meta" style="background:#f0fdf4;color:#16a34a;padding:2px 8px;border-radius:20px;font-size:.68rem;font-weight:700;">
                                    <?= htmlspecialchars(strtoupper($cons['type_consultation'])) ?>
                                </span>
                                <?php endif; ?>
                                <?php if (!empty($cons['motif'])): ?>
                                <span class="pm-meta">Motif : <?= htmlspecialchars($cons['motif']) ?></span>
                                <?php endif; ?>
                                <?php if (!empty($cons['devenir']) && $cons['devenir'] !== 'EN_COURS'): ?>
                                <span class="pm-meta" style="background:#fef9c3;color:#a16207;padding:2px 8px;border-radius:20px;font-size:.68rem;font-weight:700;">
                                    <?= htmlspecialchars($cons['devenir']) ?>
                                </span>
                                <?php endif; ?>
                            </div>
                            <div class="pm-meta">
                                <i class="bi bi-calendar3 me-1"></i>
                                <?= date('d/m/Y', strtotime($cons['date_consultation'])) ?>
                                <?php if (!empty($cons['medecin_nom'])): ?>
                                &nbsp;·&nbsp;
                                <i class="bi bi-person-badge me-1"></i>Dr <?= htmlspecialchars($cons['medecin_nom'] . ' ' . $cons['medecin_prenom']) ?>
                                <?php if (!empty($cons['medecin_specialite'])): ?>
                                <span class="pm-meta ms-1">(<?= htmlspecialchars($cons['medecin_specialite']) ?>)</span>
                                <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Diagnostic principal -->
                        <?php if (!empty($cons['diagnostic_principal'])): ?>
                        <div class="pm-block mb-2" style="background:#eff6ff;border-radius:8px;padding:8px 12px;">
                            <div class="pm-block-label" style="font-size:.65rem;font-weight:800;color:#1d4ed8;text-transform:uppercase;letter-spacing:.4px;margin-bottom:4px;">
                                <i class="bi bi-heart-pulse-fill me-1"></i>Diagnostic principal
                            </div>
                            <div class="pm-content" style="color:#1e3a8a;font-weight:600;"><?= nl2br(htmlspecialchars($cons['diagnostic_principal'])) ?></div>
                        </div>
                        <?php endif; ?>

                        <!-- Hypothèses diagnostiques -->
                        <?php if (!empty($cons['hypotheses_diagnostiques'])): ?>
                        <div class="mt-2 pt-2" style="border-top:1px dashed #e2e8f0;">
                            <div class="pm-block-label" style="font-size:.65rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.4px;margin-bottom:3px;">
                                <i class="bi bi-lightbulb me-1"></i>Hypothèses diagnostiques
                            </div>
                            <div class="pm-content" style="font-size:.85rem;"><?= nl2br(htmlspecialchars($cons['hypotheses_diagnostiques'])) ?></div>
                        </div>
                        <?php endif; ?>

                        <!-- Plan de traitement -->
                        <?php if (!empty($cons['plan_traitement'])): ?>
                        <div class="mt-2 pt-2" style="border-top:1px dashed #e2e8f0;">
                            <div class="pm-block-label" style="font-size:.65rem;font-weight:700;color:#1d4ed8;text-transform:uppercase;letter-spacing:.4px;margin-bottom:3px;">
                                <i class="bi bi-file-medical-fill me-1"></i>Plan de traitement
                            </div>
                            <div class="pm-content"><?= nl2br(htmlspecialchars($cons['plan_traitement'])) ?></div>
                        </div>
                        <?php endif; ?>

                        <!-- Traitement non médicamenteux -->
                        <?php if (!empty($cons['traitement_non_medicamenteux'])): ?>
                        <div class="mt-2 pt-2" style="border-top:1px dashed #e2e8f0;">
                            <div class="pm-block-label" style="font-size:.65rem;font-weight:700;color:#0891b2;text-transform:uppercase;letter-spacing:.4px;margin-bottom:3px;">
                                <i class="bi bi-activity me-1"></i>Traitement non médicamenteux
                            </div>
                            <div class="pm-content" style="font-size:.85rem;"><?= nl2br(htmlspecialchars($cons['traitement_non_medicamenteux'])) ?></div>
                        </div>
                        <?php endif; ?>

                        <!-- Surveillance -->
                        <?php if (!empty($cons['surveillance'])): ?>
                        <div class="mt-2 pt-2" style="border-top:1px dashed #e2e8f0;">
                            <div class="pm-block-label" style="font-size:.65rem;font-weight:700;color:#7c3aed;text-transform:uppercase;letter-spacing:.4px;margin-bottom:3px;">
                                <i class="bi bi-eye-fill me-1"></i>Surveillance
                            </div>
                            <div class="pm-content" style="font-size:.85rem;"><?= nl2br(htmlspecialchars($cons['surveillance'])) ?></div>
                        </div>
                        <?php endif; ?>

                        <!-- Notes de suivi + date de suivi -->
                        <?php if (!empty($cons['notes_suivi']) || !empty($cons['date_suivi'])): ?>
                        <div class="mt-2 pt-2" style="border-top:1px dashed #e2e8f0;">
                            <div class="pm-block-label" style="font-size:.65rem;font-weight:700;color:#059669;text-transform:uppercase;letter-spacing:.4px;margin-bottom:3px;">
                                <i class="bi bi-calendar-check me-1"></i>Suivi
                                <?php if (!empty($cons['date_suivi'])): ?>
                                <span class="ms-1 px-2 py-0 rounded-pill" style="background:#f0fdf4;color:#16a34a;font-size:.65rem;">
                                    RDV : <?= date('d/m/Y', strtotime($cons['date_suivi'])) ?>
                                </span>
                                <?php endif; ?>
                            </div>
                            <?php if (!empty($cons['notes_suivi'])): ?>
                            <div class="pm-content" style="font-size:.85rem;"><?= nl2br(htmlspecialchars($cons['notes_suivi'])) ?></div>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>

                    </div><!-- /.pm-card -->
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <?php if ($hasConsults && $hasRevs): ?>
                <hr class="pm-separator">
                <?php endif; ?>

                <!-- ══ CONDUITES À TENIR (RÉÉVALUATIONS) ══ -->
                <?php if ($hasRevs): ?>
                <div>
                    <div class="pm-section-title text-success" style="font-size:.75rem;text-transform:uppercase;font-weight:800;letter-spacing:.5px;color:#059669;margin-bottom:10px;">
                        <i class="bi bi-arrow-repeat me-1"></i>
                        Conduite à tenir — Réévaluations médicales récentes
                        <span class="badge rounded-pill ms-1" style="background:#f0fdf4;color:#059669;font-size:.65rem;"><?= count($dernieresReevaluations) ?></span>
                    </div>
                    <?php foreach ($dernieresReevaluations as $rev): ?>
                    <div class="pm-card mb-3" style="border-left:4px solid #10b981;">
                        <div class="pm-card-header">
                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                <span class="pm-badge-cond"><i class="bi bi-arrow-right-circle me-1"></i>Conduite à tenir</span>
                                <?php if (!empty($rev['diagnostic_jour'])): ?>
                                <span class="pm-badge-diag">
                                    <i class="bi bi-heart-pulse me-1"></i><?= htmlspecialchars($rev['diagnostic_jour']) ?>
                                </span>
                                <?php endif; ?>
                            </div>
                            <div class="pm-meta">
                                <i class="bi bi-calendar3 me-1"></i>
                                <?= date('d/m/Y', strtotime($rev['date_reevaluation'])) ?>
                                <?php if (!empty($rev['heure_reevaluation'])): ?>
                                à <?= htmlspecialchars(substr($rev['heure_reevaluation'], 0, 5)) ?>
                                <?php endif; ?>
                                <?php if (!empty($rev['medecin_nom'])): ?>
                                &nbsp;·&nbsp;
                                <i class="bi bi-person-badge me-1"></i>Dr <?= htmlspecialchars($rev['medecin_nom'] . ' ' . $rev['medecin_prenom']) ?>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="pm-content mb-2"><?= nl2br(htmlspecialchars($rev['conduite_tenir'])) ?></div>
                        <?php if (!empty($rev['traitement_non_medicamenteux'])): ?>
                        <div class="mt-2 pt-2" style="border-top:1px dashed #e2e8f0;">
                            <div class="pm-block-label" style="font-size:.65rem;font-weight:700;color:#64748b;text-transform:uppercase;margin-bottom:3px;">
                                <i class="bi bi-activity me-1"></i>Traitement non médicamenteux
                            </div>
                            <div class="pm-content" style="font-size:.85rem;"><?= nl2br(htmlspecialchars($rev['traitement_non_medicamenteux'])) ?></div>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($rev['note_evolution'])): ?>
                        <div class="mt-2 pt-2" style="border-top:1px dashed #e2e8f0;">
                            <div class="pm-block-label" style="font-size:.65rem;font-weight:700;color:#64748b;text-transform:uppercase;margin-bottom:3px;">
                                <i class="bi bi-journal-text me-1"></i>Note d'évolution
                            </div>
                            <div class="pm-content" style="font-size:.85rem;"><?= nl2br(htmlspecialchars($rev['note_evolution'])) ?></div>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <?php endif; ?>
            </div><!-- /.modal-body -->

            <div class="modal-footer border-0" style="padding:14px 28px;background:#f8fafc;">
                <small class="text-muted me-auto">
                    <i class="bi bi-info-circle me-1"></i>
                    3 dernières consultations complètes · 3 dernières réévaluations médicales.
                </small>
                <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">
                    Fermer
                </button>
            </div>
        </div>
    </div>
</div>
<!-- ══ /MODAL PLAN MÉDECIN ══ -->

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
