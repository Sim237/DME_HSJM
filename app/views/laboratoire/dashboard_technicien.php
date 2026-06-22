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
.sidebar, .main-sidebar { display:none !important; }
main, .main-content { margin-left:0 !important; width:100% !important; }
body { background:#f0f4f8; font-family:'Plus Jakarta Sans',sans-serif; }

/* Header */
.tech-header {
    background:linear-gradient(135deg,#0f4c75 0%,#1b6ca8 100%);
    color:#fff; padding:14px 28px;
    display:flex; align-items:center; justify-content:space-between;
    position:sticky; top:0; z-index:100;
    box-shadow:0 4px 12px rgba(0,0,0,.15);
}
#tech-clock { font-family:'Courier New',monospace; font-size:1.8rem; font-weight:bold;
    color:#00e5ff; text-shadow:0 0 12px rgba(0,229,255,.5); letter-spacing:2px; }

/* KPIs */
.kpi-wrap { display:flex; gap:14px; padding:18px 28px 8px; flex-wrap:wrap; }
.kpi-card { flex:1; min-width:160px; background:#fff; border-radius:16px; padding:18px 20px;
    box-shadow:0 4px 12px rgba(0,0,0,.06); display:flex; align-items:center; gap:14px;
    border-bottom:4px solid #e2e8f0; }
.kpi-card.c-assign  { border-color:#6366f1; }
.kpi-card.c-rejete  { border-color:#ef4444; }
.kpi-card.c-valide  { border-color:#10b981; }
.kpi-icon { width:46px; height:46px; border-radius:12px; display:flex; align-items:center;
    justify-content:center; font-size:1.3rem; flex-shrink:0; }
.c-assign .kpi-icon { background:#eef2ff; color:#6366f1; }
.c-rejete .kpi-icon { background:#fef2f2; color:#ef4444; }
.c-valide .kpi-icon { background:#ecfdf5; color:#10b981; }
.kpi-label { font-size:.73rem; color:#64748b; font-weight:600; text-transform:uppercase; }
.kpi-value { font-size:1.7rem; font-weight:800; line-height:1; color:#0f172a; }

/* Corps */
.tech-body { padding:12px 28px 40px; }

/* Sections */
.section-card { background:#fff; border-radius:18px; box-shadow:0 4px 16px rgba(0,0,0,.07);
    overflow:hidden; margin-bottom:24px; }
.section-header { padding:16px 22px; border-bottom:1px solid #f1f5f9;
    display:flex; align-items:center; gap:10px; }
.section-header h5 { font-weight:800; font-size:1rem; color:#0f172a; margin:0; flex:1; }

/* Cartes examen */
.examen-card { border:1px solid #e2e8f0; border-radius:14px; margin:16px;
    transition:box-shadow .2s; overflow:hidden; }
.examen-card:hover { box-shadow:0 8px 20px rgba(0,0,0,.08); }
.examen-card.urgent-card { border-color:#fca5a5; border-left:5px solid #ef4444; }
.examen-card.rejete-card { border-color:#fca5a5; background:#fff5f5; }

.examen-head { padding:14px 18px; background:#f8fafc;
    display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:8px; }
.examen-nom { font-weight:800; font-size:.98rem; color:#0f172a; }
.examen-cat { font-size:.75rem; color:#64748b; font-weight:600; background:#f1f5f9;
    padding:3px 10px; border-radius:20px; }
.examen-body { padding:16px 18px; }

/* Valeurs normales */
.normal-badge { font-size:.72rem; color:#6b7280; background:#f9fafb;
    border:1px solid #e5e7eb; border-radius:8px; padding:2px 8px; display:inline-block; margin-top:4px; }

/* Formulaire résultats */
.result-form { background:#f8fafc; border-radius:12px; padding:16px; margin-top:12px; }
.result-form .form-label { font-size:.78rem; font-weight:700; color:#374151; margin-bottom:6px; }
.result-form input, .result-form textarea, .result-form select {
    border:1.5px solid #e2e8f0; border-radius:10px; padding:10px 14px; font-size:.88rem; }
.result-form input:focus, .result-form textarea:focus {
    border-color:#1b6ca8; box-shadow:0 0 0 3px rgba(27,108,168,.1); outline:none; }

/* ── Multi-paramètres ─────────────────────────────────── */
.mp-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
    gap: 12px;
    margin-bottom: 10px;
}
.mp-field { display: flex; flex-direction: column; gap: 4px; }
.mp-label {
    font-size: .75rem; font-weight: 700; color: #374151;
    display: flex; flex-direction: column; gap: 1px;
}
.mp-range { font-size: .68rem; font-weight: 400; color: #6b7280; }
.mp-input { border: 1.5px solid #e2e8f0 !important; border-radius: 8px !important; }
.mp-input:focus { border-color: #1b6ca8 !important; box-shadow: 0 0 0 3px rgba(27,108,168,.1) !important; }
.mp-input.is-normal { border-color: #10b981 !important; background: #f0fdf4 !important; }
.mp-input.is-abnormal { border-color: #ef4444 !important; background: #fff1f2 !important; }
.mp-indicator { font-size: .67rem; font-weight: 600; height: 16px; line-height: 16px; }
.mp-indicator.normal  { color: #10b981; }
.mp-indicator.abnormal { color: #ef4444; }

/* Boutons */
.btn-soumettre { background:linear-gradient(135deg,#10b981,#059669); color:#fff;
    border:none; border-radius:10px; padding:10px 24px; font-weight:700; font-size:.88rem;
    cursor:pointer; transition:.2s; display:flex; align-items:center; gap:7px; }
.btn-soumettre:hover { transform:translateY(-2px); box-shadow:0 6px 14px rgba(16,185,129,.3); }
.btn-soumettre:disabled { opacity:.6; cursor:not-allowed; transform:none; }
.btn-recommencer { background:linear-gradient(135deg,#f59e0b,#d97706); color:#fff;
    border:none; border-radius:10px; padding:9px 20px; font-weight:700; font-size:.85rem;
    cursor:pointer; transition:.2s; }
.btn-recommencer:hover { transform:translateY(-2px); }

/* Statut badges */
.badge-assignee  { background:#eef2ff; color:#4338ca; }
.badge-soumis    { background:#fef9c3; color:#854d0e; }
.badge-valide    { background:#dcfce7; color:#166534; }
.badge-rejete    { background:#fee2e2; color:#dc2626; }

/* Toast */
.toast-labo { position:fixed; bottom:24px; right:24px; z-index:9999;
    min-width:300px; max-width:420px; }

/* Historique */
.hist-row { padding:12px 18px; border-bottom:1px solid #f1f5f9; display:flex; align-items:center; gap:12px; }
.hist-row:last-child { border-bottom:none; }
.hist-examen { font-weight:700; font-size:.88rem; color:#0f172a; }
.hist-detail { font-size:.8rem; color:#64748b; }

/* Empty state */
.empty-box { padding:50px 20px; text-align:center; color:#94a3b8; }
.empty-box i { font-size:3rem; opacity:.3; display:block; margin-bottom:10px; }
</style>

<main>
<!-- ══ HEADER ══ -->
<div class="tech-header">
    <div class="d-flex align-items:center gap-3">
        <div>
            <h5 class="mb-0 fw-bold"><i class="bi bi-eyedropper me-2" style="color:#00e5ff;"></i>POSTE TECHNICIEN</h5>
            <small class="opacity-75">
                <?= htmlspecialchars(($_SESSION['user_nom'] ?? '') . ' ' . ($_SESSION['user_prenom'] ?? '')) ?>
                &nbsp;·&nbsp; Laboratoire Central
            </small>
        </div>
    </div>
    <div id="tech-clock">00:00:00</div>
    <div class="d-flex gap-2">
        <a href="<?= BASE_URL ?>laboratoire/historique" class="btn btn-sm btn-outline-light rounded-pill px-3">
            <i class="bi bi-clock-history me-1"></i>Historique
        </a>
        <a href="<?= BASE_URL ?>logout" class="btn btn-danger btn-sm rounded-circle p-2"><i class="bi bi-power"></i></a>
    </div>
</div>

<!-- ══ KPI ══ -->
<div class="kpi-wrap">
    <div class="kpi-card c-assign">
        <div class="kpi-icon"><i class="bi bi-flask"></i></div>
        <div>
            <div class="kpi-label">À traiter</div>
            <div class="kpi-value"><?= count($examens_assignes) ?></div>
        </div>
    </div>
    <div class="kpi-card c-rejete">
        <div class="kpi-icon"><i class="bi bi-arrow-counterclockwise"></i></div>
        <div>
            <div class="kpi-label">À recommencer</div>
            <div class="kpi-value" style="color:#ef4444;"><?= count($examens_rejetes) ?></div>
        </div>
    </div>
    <div class="kpi-card c-valide">
        <div class="kpi-icon"><i class="bi bi-check-circle"></i></div>
        <div>
            <div class="kpi-label">Validés (7j)</div>
            <div class="kpi-value" style="color:#10b981;"><?= count($examens_valides) ?></div>
        </div>
    </div>
</div>

<div class="tech-body">

<!-- ════════ EXAMENS REJETÉS (priorité : à voir en premier) ════════ -->
<?php if (!empty($examens_rejetes)): ?>
<div class="section-card">
    <div class="section-header" style="background:#fff5f5;border-bottom:2px solid #fca5a5;">
        <i class="bi bi-exclamation-triangle-fill text-danger fs-5"></i>
        <h5 class="text-danger">Examens rejetés — à recommencer</h5>
        <span class="badge bg-danger ms-auto"><?= count($examens_rejetes) ?></span>
    </div>
    <?php foreach ($examens_rejetes as $ex): ?>
    <div class="examen-card rejete-card" id="card-rejete-<?= $ex['id'] ?>">
        <div class="examen-head">
            <div>
                <div class="examen-nom">
                    <?php if ($ex['urgent']): ?><span class="badge bg-danger me-1">URGENT</span><?php endif; ?>
                    <?= htmlspecialchars($ex['nom_examen']) ?>
                </div>
                <small class="text-muted"><?= htmlspecialchars($ex['patient_nom'] . ' ' . $ex['patient_prenom']) ?>
                    &nbsp;·&nbsp; <?= htmlspecialchars($ex['dossier_numero']) ?></small>
            </div>
            <span class="examen-cat"><?= htmlspecialchars($ex['categorie']) ?></span>
        </div>
        <div class="examen-body">
            <div class="alert alert-danger py-2 mb-3 rounded-3" style="font-size:.85rem;">
                <i class="bi bi-x-circle-fill me-2"></i>
                <strong>Motif du rejet :</strong> <?= htmlspecialchars($ex['motif_rejet'] ?? 'Non précisé') ?>
                <span class="text-muted ms-2">— Dr <?= htmlspecialchars($ex['valideur_nom'] . ' ' . $ex['valideur_prenom']) ?></span>
            </div>
            <!-- Formulaire de re-saisie -->
            <div class="result-form" id="form-rejete-<?= $ex['id'] ?>">
                <div class="fw-semibold mb-3 text-danger"><i class="bi bi-arrow-counterclockwise me-1"></i>Nouvelle saisie</div>
                <?php if (!empty($ex['sous_params'])): ?>
                <div class="mp-grid" id="mp-<?= $ex['id'] ?>">
                    <?php foreach ($ex['sous_params'] as $sp): ?>
                    <div class="mp-field">
                        <label class="mp-label">
                            <?= htmlspecialchars($sp['nom']) ?>
                            <?php if ($sp['valeur_normale_min'] !== null && $sp['valeur_normale_max'] !== null): ?>
                            <span class="mp-range"><?= $sp['valeur_normale_min'] ?>–<?= $sp['valeur_normale_max'] ?> <?= htmlspecialchars($sp['unite'] ?? '') ?></span>
                            <?php endif; ?>
                        </label>
                        <div class="input-group input-group-sm">
                            <input type="number" step="0.001"
                                   id="param-<?= $ex['id'] ?>-<?= $sp['id'] ?>"
                                   class="form-control mp-input"
                                   data-param-id="<?= $sp['id'] ?>"
                                   data-param-code="<?= htmlspecialchars($sp['code'] ?? $sp['nom']) ?>"
                                   data-param-unite="<?= htmlspecialchars($sp['unite'] ?? '') ?>"
                                   data-min="<?= $sp['valeur_normale_min'] ?? '' ?>"
                                   data-max="<?= $sp['valeur_normale_max'] ?? '' ?>"
                                   placeholder="—"
                                   oninput="checkParamRange(this, <?= $ex['id'] ?>)">
                            <?php if (!empty($sp['unite'])): ?>
                            <span class="input-group-text" style="font-size:.78rem;"><?= htmlspecialchars($sp['unite']) ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="mp-indicator" id="ind-<?= $ex['id'] ?>-<?= $sp['id'] ?>"></div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <div class="mt-2">
                    <label class="form-label small text-muted">Interprétation globale <em>(optionnel)</em></label>
                    <input type="text" id="interp-<?= $ex['id'] ?>" class="form-control form-control-sm rounded-3"
                           placeholder="Ex: Ionogramme dans les limites normales…">
                </div>
                <?php else: ?>
                <div class="row g-3">
                    <div class="col-md-5">
                        <label class="form-label">Résultat <span class="text-danger">*</span></label>
                        <input type="text" id="res-<?= $ex['id'] ?>" class="form-control"
                               placeholder="Ex: Positif, 12.5, Normal…">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Valeur numérique <small class="text-muted">(si applicable)</small></label>
                        <input type="number" step="0.001" id="num-<?= $ex['id'] ?>" class="form-control" placeholder="0.000">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Interprétation</label>
                        <input type="text" id="interp-<?= $ex['id'] ?>" class="form-control" placeholder="Ex: Valeur dans les normes…">
                    </div>
                </div>
                <?php endif; ?>
                <div class="d-flex justify-content-end mt-3">
                    <button class="btn-soumettre"
                            onclick="soumettreResultat(<?= $ex['id'] ?>, this, <?= !empty($ex['sous_params']) ? 'true' : 'false' ?>)">
                        <i class="bi bi-send-fill"></i> Resoumettre
                    </button>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- ════════ EXAMENS ASSIGNÉS ════════ -->
<div class="section-card">
    <div class="section-header">
        <i class="bi bi-flask-fill text-primary fs-5"></i>
        <h5>Mes examens à traiter</h5>
        <span class="badge bg-primary ms-auto"><?= count($examens_assignes) ?></span>
        <button class="btn btn-sm btn-outline-secondary rounded-pill ms-2" onclick="location.reload()">
            <i class="bi bi-arrow-clockwise"></i>
        </button>
    </div>

    <?php if (empty($examens_assignes)): ?>
    <div class="empty-box">
        <i class="bi bi-check2-all text-success d-block mb-2" style="font-size:3rem;opacity:.5;"></i>
        <p class="fw-semibold text-muted">Aucun examen assigné pour le moment</p>
        <small>Le laborantin vous assignera de nouveaux examens dès réception des demandes.</small>
    </div>
    <?php else: ?>

    <?php
    // Grouper par catégorie
    $parCat = [];
    foreach ($examens_assignes as $ex) { $parCat[$ex['categorie']][] = $ex; }
    ?>

    <?php foreach ($parCat as $categorie => $examens): ?>
    <div style="padding:12px 18px 0;">
        <div class="d-flex align-items-center gap-2 mb-2">
            <i class="bi bi-tag-fill text-primary" style="font-size:.85rem;"></i>
            <span class="fw-bold text-uppercase" style="font-size:.78rem;color:#1b6ca8;letter-spacing:.5px;">
                <?= htmlspecialchars($categorie) ?>
            </span>
            <span class="badge bg-primary-subtle text-primary rounded-pill" style="font-size:.7rem;"><?= count($examens) ?></span>
        </div>
    </div>

    <?php foreach ($examens as $ex): ?>
    <div class="examen-card <?= $ex['urgent'] ? 'urgent-card' : '' ?>" id="card-<?= $ex['id'] ?>">
        <div class="examen-head">
            <div>
                <div class="examen-nom">
                    <?php if ($ex['urgent']): ?>
                    <span class="badge bg-danger me-1" style="font-size:.7rem;">URGENT</span>
                    <?php endif; ?>
                    <?= htmlspecialchars($ex['nom_examen']) ?>
                </div>
                <div class="mt-1">
                    <small class="fw-semibold text-dark">
                        <i class="bi bi-person me-1"></i><?= htmlspecialchars($ex['patient_nom'] . ' ' . $ex['patient_prenom']) ?>
                    </small>
                    <small class="text-muted ms-2"><?= htmlspecialchars($ex['dossier_numero']) ?></small>
                    <?php if ($ex['date_naissance']): ?>
                    <small class="text-muted ms-2">· <?= date('d/m/Y', strtotime($ex['date_naissance'])) ?></small>
                    <?php endif; ?>
                </div>
            </div>
            <div class="text-end">
                <span class="examen-cat"><?= htmlspecialchars($ex['categorie']) ?></span>
                <div class="mt-1">
                    <small class="text-muted">Assigné : <?= date('d/m H:i', strtotime($ex['date_assignation'])) ?></small>
                </div>
            </div>
        </div>

        <div class="examen-body">
            <?php if (!empty($ex['observations'])): ?>
            <div class="alert alert-info py-2 mb-3 rounded-3" style="font-size:.83rem;">
                <i class="bi bi-info-circle me-1"></i><strong>Note du médecin :</strong>
                <?= htmlspecialchars($ex['observations']) ?>
            </div>
            <?php endif; ?>

            <!-- Infos prélèvement -->
            <div class="d-flex flex-wrap gap-3 mb-3">
                <div>
                    <small class="text-muted d-block">Type prélèvement</small>
                    <span class="badge bg-secondary-subtle text-secondary rounded-pill">
                        <i class="bi bi-droplet me-1"></i><?= htmlspecialchars($ex['type_prelevement'] ?? '—') ?>
                    </span>
                </div>
                <?php if ($ex['valeur_normale_min'] !== null && $ex['valeur_normale_max'] !== null): ?>
                <div>
                    <small class="text-muted d-block">Valeurs normales</small>
                    <span class="normal-badge">
                        <?= $ex['valeur_normale_min'] ?> – <?= $ex['valeur_normale_max'] ?> <?= htmlspecialchars($ex['unite'] ?? '') ?>
                    </span>
                </div>
                <?php endif; ?>
                <?php if ($ex['a_jeun']): ?>
                <div>
                    <span class="badge bg-warning-subtle text-warning rounded-pill">
                        <i class="bi bi-egg-fried me-1"></i>À jeun requis
                    </span>
                </div>
                <?php endif; ?>
            </div>

            <!-- Formulaire saisie résultat -->
            <div class="result-form" id="form-<?= $ex['id'] ?>">
                <?php if (!empty($ex['sous_params'])): ?>
                <!-- ── Formulaire multi-paramètres ── -->
                <div class="mp-grid" id="mp-<?= $ex['id'] ?>">
                    <?php foreach ($ex['sous_params'] as $sp): ?>
                    <div class="mp-field">
                        <label class="mp-label">
                            <?= htmlspecialchars($sp['nom']) ?>
                            <?php if ($sp['valeur_normale_min'] !== null && $sp['valeur_normale_max'] !== null): ?>
                            <span class="mp-range"><?= $sp['valeur_normale_min'] ?>–<?= $sp['valeur_normale_max'] ?> <?= htmlspecialchars($sp['unite'] ?? '') ?></span>
                            <?php endif; ?>
                        </label>
                        <div class="input-group input-group-sm">
                            <input type="number" step="0.001"
                                   id="param-<?= $ex['id'] ?>-<?= $sp['id'] ?>"
                                   class="form-control mp-input"
                                   data-param-id="<?= $sp['id'] ?>"
                                   data-param-code="<?= htmlspecialchars($sp['code'] ?? $sp['nom']) ?>"
                                   data-param-unite="<?= htmlspecialchars($sp['unite'] ?? '') ?>"
                                   data-min="<?= $sp['valeur_normale_min'] ?? '' ?>"
                                   data-max="<?= $sp['valeur_normale_max'] ?? '' ?>"
                                   placeholder="—"
                                   oninput="checkParamRange(this, <?= $ex['id'] ?>)">
                            <?php if (!empty($sp['unite'])): ?>
                            <span class="input-group-text" style="font-size:.78rem;"><?= htmlspecialchars($sp['unite']) ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="mp-indicator" id="ind-<?= $ex['id'] ?>-<?= $sp['id'] ?>"></div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <div class="mt-2">
                    <label class="form-label small text-muted">Interprétation globale <em>(optionnel)</em></label>
                    <input type="text" id="interp-<?= $ex['id'] ?>" class="form-control form-control-sm rounded-3"
                           placeholder="Ex: Ionogramme dans les limites normales…">
                </div>
                <?php else: ?>
                <!-- ── Formulaire standard mono-valeur ── -->
                <div class="row g-3">
                    <div class="col-md-5">
                        <label class="form-label">Résultat <span class="text-danger">*</span></label>
                        <input type="text" id="res-<?= $ex['id'] ?>" class="form-control"
                               placeholder="Ex: Positif, 12.5, Normal…">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Valeur numérique <small class="text-muted">(si applicable)</small></label>
                        <div class="input-group">
                            <input type="number" step="0.001" id="num-<?= $ex['id'] ?>" class="form-control"
                                   placeholder="0.000">
                            <?php if (!empty($ex['unite'])): ?>
                            <span class="input-group-text" style="font-size:.8rem;border-radius:0 10px 10px 0;">
                                <?= htmlspecialchars($ex['unite']) ?>
                            </span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Interprétation</label>
                        <input type="text" id="interp-<?= $ex['id'] ?>" class="form-control"
                               placeholder="Ex: Valeur dans les normes…">
                    </div>
                </div>
                <?php endif; ?>
                <div class="d-flex justify-content-end mt-3">
                    <button class="btn-soumettre"
                            onclick="soumettreResultat(<?= $ex['id'] ?>, this, <?= !empty($ex['sous_params']) ? 'true' : 'false' ?>)">
                        <i class="bi bi-send-fill"></i> Soumettre pour validation
                    </button>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
    <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- ════════ HISTORIQUE (7 derniers jours) ════════ -->
<?php if (!empty($examens_valides)): ?>
<div class="section-card">
    <div class="section-header">
        <i class="bi bi-clock-history text-success fs-5"></i>
        <h5 class="text-success">Mes examens validés (7 derniers jours)</h5>
        <span class="badge bg-success ms-auto"><?= count($examens_valides) ?></span>
        <button class="btn btn-sm btn-outline-secondary rounded-pill ms-2"
                type="button" data-bs-toggle="collapse" data-bs-target="#collapseHistorique">
            <i class="bi bi-chevron-down me-1"></i>Afficher / Masquer
        </button>
    </div>
    <div class="collapse show" id="collapseHistorique">
        <?php foreach ($examens_valides as $h): ?>
        <div class="hist-row">
            <i class="bi bi-check-circle-fill text-success"></i>
            <div class="flex-grow-1">
                <div class="hist-examen"><?= htmlspecialchars($h['nom_examen']) ?></div>
                <div class="hist-detail">
                    <?= htmlspecialchars($h['patient_nom'] . ' ' . $h['patient_prenom']) ?>
                    &nbsp;·&nbsp;
                    Résultat : <strong><?= htmlspecialchars($h['resultat'] ?? '—') ?></strong>
                    <?php if ($h['valeur_numerique'] !== null): ?>
                    (<?= $h['valeur_numerique'] ?>)
                    <?php endif; ?>
                </div>
            </div>
            <div class="text-end">
                <small class="text-muted"><?= date('d/m/Y H:i', strtotime($h['date_validation'])) ?></small>
                <div><small class="text-success">Validé par <?= htmlspecialchars($h['valideur_nom'] . ' ' . $h['valideur_prenom']) ?></small></div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

</div><!-- /.tech-body -->
</main>

<!-- Toast -->
<div class="toast-labo">
    <div id="toastTech" class="toast text-white border-0 rounded-3" role="alert">
        <div class="d-flex">
            <div class="toast-body fw-semibold" id="toastTechBody">—</div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    </div>
</div>

<script>
const BASE_URL_TECH = '<?= BASE_URL ?>';

// Horloge
(function tick() {
    const el = document.getElementById('tech-clock');
    if (!el) return;
    const n = new Date(), p = n => String(n).padStart(2,'0');
    el.textContent = p(n.getHours())+':'+p(n.getMinutes())+':'+p(n.getSeconds());
    setTimeout(tick, 1000);
})();

function showToast(msg, ok = true) {
    const el = document.getElementById('toastTech');
    el.classList.remove('bg-success','bg-danger');
    el.classList.add(ok ? 'bg-success' : 'bg-danger');
    document.getElementById('toastTechBody').textContent = msg;
    bootstrap.Toast.getOrCreateInstance(el, { delay: 4000 }).show();
}

/** Vérifie la plage normale d'un champ et met à jour l'indicateur */
function checkParamRange(inp, examenId) {
    const val = parseFloat(inp.value);
    const min = parseFloat(inp.dataset.min);
    const max = parseFloat(inp.dataset.max);
    const ind = document.getElementById('ind-' + examenId + '-' + inp.dataset.paramId);
    inp.classList.remove('is-normal', 'is-abnormal');
    if (ind) { ind.className = 'mp-indicator'; ind.textContent = ''; }
    if (!inp.value.trim() || isNaN(val)) return;
    if (!isNaN(min) && !isNaN(max)) {
        if (val >= min && val <= max) {
            inp.classList.add('is-normal');
            if (ind) { ind.classList.add('normal'); ind.textContent = '✓ Normal'; }
        } else {
            inp.classList.add('is-abnormal');
            if (ind) { ind.classList.add('abnormal'); ind.textContent = '⚠ Hors norme'; }
        }
    }
}

function soumettreResultat(examenId, btn, isMultiParam = false) {
    let resultat = '', valeurNum = '', sousParams = [];
    const interpretation = (document.getElementById('interp-' + examenId) || {}).value?.trim() ?? '';

    if (isMultiParam) {
        const grid = document.getElementById('mp-' + examenId);
        if (grid) {
            grid.querySelectorAll('.mp-input').forEach(inp => {
                sousParams.push({
                    id: parseInt(inp.dataset.paramId),
                    valeur_numerique: inp.value.trim()
                });
            });
        }
        const filled = sousParams.filter(sp => sp.valeur_numerique !== '');
        if (!filled.length) { showToast('Veuillez saisir au moins une valeur.', false); return; }
        // Résumé auto
        resultat = filled.map(sp => {
            const inp = grid?.querySelector(`[data-param-id="${sp.id}"]`);
            return (inp?.dataset.paramCode || '') + ': ' + sp.valeur_numerique
                 + (inp?.dataset.paramUnite ? ' ' + inp.dataset.paramUnite : '');
        }).join(' | ');
    } else {
        resultat  = (document.getElementById('res-' + examenId) || {}).value?.trim() ?? '';
        valeurNum = (document.getElementById('num-' + examenId) || {}).value?.trim() ?? '';
        if (!resultat) { showToast('Le champ "Résultat" est obligatoire.', false); return; }
    }

    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Envoi…';

    fetch(BASE_URL_TECH + 'laboratoire/soumettre-resultats-technicien', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            examen_id:        examenId,
            resultat:         resultat,
            valeur_numerique: valeurNum,
            interpretation:   interpretation,
            sous_params:      sousParams
        })
    })
    .then(r => r.json())
    .then(d => {
        showToast(d.message, d.success);
        if (d.success) {
            const card = document.getElementById('card-' + examenId)
                      || document.getElementById('card-rejete-' + examenId);
            if (card) {
                card.style.opacity = '.5';
                card.style.pointerEvents = 'none';
                const head = card.querySelector('.examen-head');
                if (head) head.insertAdjacentHTML('beforeend',
                    '<span class="badge bg-warning-subtle text-warning ms-2"><i class="bi bi-hourglass me-1"></i>En attente de validation</span>');
            }
        }
    })
    .catch(() => showToast('Erreur de communication.', false))
    .finally(() => {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-send-fill"></i> Soumettre pour validation';
    });
}

// Auto-refresh toutes les 60 secondes
setTimeout(() => location.reload(), 60000);
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
