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
 require_once __DIR__ . '/../../layouts/header.php'; ?>
<style>
/* ═══════════════════════════════════════════════════════════
   PARTOGRAMME — Design System
   ═══════════════════════════════════════════════════════════ */
:root {
    --pt-red:    #be123c;
    --pt-red-lt: #fff1f2;
    --pt-dark:   #1e293b;
    --pt-muted:  #64748b;
    --border:    #e2e8f0;
}
body { background: #f0f4f8; font-family: 'Inter', sans-serif; }

/* ── Topbar ── */
.pt-topbar {
    position: sticky; top: 0; z-index: 200;
    background: white; border-bottom: 1px solid var(--border);
    padding: 10px 20px; display: flex; justify-content: space-between;
    align-items: center; box-shadow: 0 2px 8px rgba(0,0,0,.07);
}
.btn-back {
    display:inline-flex;align-items:center;gap:6px;padding:8px 14px;
    border-radius:8px;border:1.5px solid var(--border);background:white;
    color:#475569;font-weight:600;font-size:.85rem;text-decoration:none;transition:.2s;
}
.btn-back:hover{background:#f8fafc;color:var(--pt-dark);}
.btn-save {
    display:inline-flex;align-items:center;gap:6px;padding:9px 20px;
    border-radius:8px;border:none;background:var(--pt-red);color:white;
    font-weight:700;font-size:.88rem;cursor:pointer;transition:.2s;
}
.btn-save:hover{background:#9f1239;}

/* ── Patient banner ── */
.pt-banner {
    background: linear-gradient(135deg,#be123c,#e11d48);
    border-radius:16px;padding:18px 24px;color:white;
    margin-bottom:20px;display:flex;justify-content:space-between;
    align-items:center;flex-wrap:wrap;gap:12px;
}
.pt-badge {
    background:rgba(255,255,255,.18);border-radius:8px;
    padding:5px 12px;font-size:.8rem;font-weight:600;
}

/* ── Onglets ── */
.pt-tabs { display:flex;gap:4px;margin-bottom:16px;background:white;
    padding:6px;border-radius:12px;box-shadow:0 2px 8px rgba(0,0,0,.06); }
.pt-tab {
    flex:1;padding:10px 8px;border-radius:8px;border:none;background:transparent;
    font-weight:600;font-size:.85rem;color:var(--pt-muted);cursor:pointer;transition:.2s;
}
.pt-tab.active { background:var(--pt-red);color:white;box-shadow:0 2px 8px rgba(190,18,60,.35); }
.pt-pane { display:none; }
.pt-pane.active { display:block; }

/* ── Section cards ── */
.pt-section {
    background:white;border-radius:12px;
    box-shadow:0 2px 8px rgba(0,0,0,.06);
    margin-bottom:14px;overflow:hidden;
}
.pt-section-head {
    background:var(--pt-red-lt);padding:11px 18px;
    font-weight:700;font-size:.82rem;text-transform:uppercase;
    letter-spacing:.5px;display:flex;align-items:center;gap:8px;
    border-bottom:2px solid var(--pt-red);color:var(--pt-red);
}
.pt-section-body { padding:18px; }

/* ── Form controls ── */
.form-label{font-weight:600;font-size:.78rem;color:var(--pt-muted);
    text-transform:uppercase;letter-spacing:.4px;margin-bottom:4px;}
.form-control,.form-select{
    border:1.5px solid var(--border);border-radius:8px;
    font-size:.9rem;padding:9px 12px;transition:.2s;
}
.form-control:focus,.form-select:focus{
    border-color:var(--pt-red);
    box-shadow:0 0 0 3px rgba(190,18,60,.12);outline:none;
}

/* ── Grille de saisie temporelle (12 colonnes = 12 heures) ── */
.pt-grid-wrap { overflow-x:auto; }
.pt-grid {
    border-collapse:collapse;
    min-width:700px;width:100%;
    font-size:.78rem;
}
.pt-grid th {
    background:#f8fafc;border:1px solid #cbd5e1;
    padding:5px 4px;text-align:center;font-weight:700;
    color:var(--pt-muted);white-space:nowrap;
}
.pt-grid td { border:1px solid #cbd5e1;padding:2px 3px; }
.pt-grid .row-label {
    background:#f8fafc;font-weight:600;font-size:.75rem;
    color:var(--pt-dark);padding:5px 8px;white-space:nowrap;
    min-width:130px;
}
.pt-grid input[type=text],
.pt-grid input[type=number] {
    width:100%;border:none;outline:none;
    text-align:center;font-size:.8rem;
    background:transparent;padding:3px 0;
    color:var(--pt-dark);font-weight:600;
}
.pt-grid input:focus { background:#fffbeb; }
.pt-grid .row-bcf input { color:#be123c; }
.pt-grid .row-col input { color:#0369a1; }

/* ── Graphique BCF Canvas ── */
.chart-container {
    background:white;border:1px solid var(--border);
    border-radius:10px;overflow:hidden;position:relative;
}
canvas.pt-chart { display:block;width:100%;cursor:crosshair;touch-action:none; }
.chart-legend {
    display:flex;gap:16px;align-items:center;flex-wrap:wrap;
    padding:8px 12px;border-top:1px solid var(--border);
    font-size:.75rem;font-weight:600;color:var(--pt-muted);
}
.legend-dot { width:10px;height:10px;border-radius:50%;display:inline-block; }

/* ── Hint bar above charts ── */
.chart-hint {
    display:flex;align-items:center;gap:8px;
    background:#f8fafc;border:1px dashed #cbd5e1;border-radius:8px;
    padding:8px 12px;margin-bottom:10px;font-size:.78rem;color:var(--pt-muted);
}
.chart-hint i { font-size:1.1rem;color:var(--pt-red);flex-shrink:0; }

/* ── OUI/NON compact ── */
.oui-non-sm { display:inline-flex;gap:6px;align-items:center; }
.oui-non-sm label {
    display:inline-flex;align-items:center;gap:4px;cursor:pointer;
    font-size:.85rem;font-weight:600;color:var(--pt-muted);
}
.oui-non-sm input[type=checkbox] { width:14px;height:14px; }

/* ── Checkbox PTME ── */
.ptme-box {
    display:flex;align-items:center;gap:6px;
    padding:6px 10px;border:1.5px solid var(--border);border-radius:8px;
    font-weight:700;font-size:.85rem;cursor:pointer;transition:.2s;
}
.ptme-box:has(input:checked){ border-color:var(--pt-red);background:var(--pt-red-lt);color:var(--pt-red); }

/* ── Suivi mère/enfant ── */
.suivi-table{border-collapse:collapse;width:100%;font-size:.75rem;}
.suivi-table th{background:#f1f5f9;border:1px solid #cbd5e1;padding:5px 6px;
    text-align:center;font-weight:700;color:var(--pt-dark);}
.suivi-table td{border:1px solid #cbd5e1;padding:2px 4px;vertical-align:middle;}
.suivi-table .cat-label{background:#fff1f2;font-weight:700;color:var(--pt-red);
    font-size:.72rem;padding:4px 8px;text-align:left;}
.suivi-table .sub-label{background:#f8fafc;font-size:.72rem;
    color:var(--pt-muted);padding:3px 8px;font-style:italic;}
.suivi-table input[type=text]{width:100%;border:none;outline:none;
    text-align:center;font-size:.75rem;background:transparent;padding:2px;}
.suivi-table input:focus{background:#f0fdf4;}

/* ── Pill radio ── */
.pill-group{display:flex;gap:6px;flex-wrap:wrap;}
.pill-radio{position:relative;}
.pill-radio input[type=radio]{position:absolute;opacity:0;width:0;height:0;}
.pill-radio label{
    display:inline-flex;align-items:center;gap:5px;
    padding:6px 14px;border-radius:20px;cursor:pointer;
    font-weight:600;font-size:.82rem;
    border:2px solid var(--border);background:white;color:var(--pt-muted);transition:.2s;
}
.pill-radio input:checked + label{border-color:var(--pt-red);background:var(--pt-red-lt);color:var(--pt-red);}

.pt-wrap { max-width:1100px;margin:0 auto;padding:18px 16px 100px; }

@media print{
    .pt-topbar,.pt-tabs{display:none!important;}
    body{background:white!important;}
    .pt-pane{display:block!important;}
    .pt-section{box-shadow:none;border:1px solid var(--border);page-break-inside:avoid;}
}
</style>

<!-- ── Topbar ─────────────────────────────────────────────────── -->
<div class="pt-topbar">
    <div class="d-flex align-items-center gap-3">
        <?php if ($from_suivi): ?>
        <a href="<?= BASE_URL ?>hospitalisation/suivi/<?= (int)$patient['id'] ?>" class="btn-back">
            <i class="bi bi-arrow-left"></i> Retour
        </a>
        <?php else: ?>
        <a href="<?= BASE_URL ?>patients/dossier/<?= (int)$patient['id'] ?>" class="btn-back">
            <i class="bi bi-arrow-left"></i> Retour
        </a>
        <?php endif; ?>
        <div style="font-weight:800;font-size:.95rem;color:var(--pt-dark);">
            <i class="bi bi-graph-up-arrow" style="color:var(--pt-red);"></i>
            Partogramme
        </div>
    </div>
    <div class="d-flex gap-2">
        <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill" onclick="window.print()">
            <i class="bi bi-printer"></i> Imprimer
        </button>
        <button type="submit" form="formPartogramme" class="btn-save">
            <i class="bi bi-save2-fill"></i> Enregistrer
        </button>
    </div>
</div>

<div class="pt-wrap">

<!-- ── Bannière patient ─────────────────────────────────────── -->
<div class="pt-banner">
    <div>
        <div style="font-size:1.2rem;font-weight:800;">
            <?= htmlspecialchars(($patient['nom'] ?? '') . ' ' . ($patient['prenom'] ?? '')) ?>
        </div>
        <div style="font-size:.83rem;opacity:.88;margin-top:4px;">
            <?= $age ?> ans &nbsp;·&nbsp; Dossier : <?= htmlspecialchars($patient['dossier_numero'] ?? '—') ?>
        </div>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <span class="pt-badge"><i class="bi bi-calendar3 me-1"></i><?= date('d/m/Y') ?></span>
        <span class="pt-badge"><i class="bi bi-heart-pulse me-1"></i>Obstétrique</span>
    </div>
</div>

<form id="formPartogramme" action="#" method="POST">
    <input type="hidden" name="patient_id" value="<?= (int)$patient['id'] ?>">
    <?php if ($hosp_id): ?><input type="hidden" name="hosp_id" value="<?= (int)$hosp_id ?>"><?php endif; ?>

    <!-- ── Onglets ─────────────────────────────────────────── -->
    <div class="pt-tabs">
        <button type="button" class="pt-tab active" onclick="showTab('tab-admission')">
            <i class="bi bi-clipboard2-pulse me-1"></i>Admission & Travail
        </button>
        <button type="button" class="pt-tab" onclick="showTab('tab-partograph')">
            <i class="bi bi-graph-up-arrow me-1"></i>Graphiques
        </button>
        <button type="button" class="pt-tab" onclick="showTab('tab-accouchement')">
            <i class="bi bi-hospital me-1"></i>Accouchement
        </button>
        <button type="button" class="pt-tab" onclick="showTab('tab-postpartum')">
            <i class="bi bi-calendar-check me-1"></i>Post-Partum
        </button>
    </div>

    <!-- ══════════════════════════════════════════════════════
         ONGLET 1 — ADMISSION
    ══════════════════════════════════════════════════════ -->
    <div class="pt-pane active" id="tab-admission">

        <!-- PTME -->
        <div class="pt-section">
            <div class="pt-section-head"><i class="bi bi-shield-plus"></i> PTME — Sérologies</div>
            <div class="pt-section-body">
                <div class="d-flex gap-3 flex-wrap">
                    <label class="ptme-box">
                        <input type="checkbox" name="ptme_hiv" value="1"> H.I.V
                    </label>
                    <label class="ptme-box">
                        <input type="checkbox" name="ptme_aghbs" value="1"> AgHBs
                    </label>
                    <label class="ptme-box">
                        <input type="checkbox" name="ptme_gsrh" value="1"> GSRH
                    </label>
                </div>
            </div>
        </div>

        <!-- Admission -->
        <div class="pt-section">
            <div class="pt-section-head"><i class="bi bi-person-plus"></i> Identification & Admission</div>
            <div class="pt-section-body">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Gestité</label>
                        <input type="number" name="geste" class="form-control" placeholder="ex: 2" min="1" max="20">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Parité</label>
                        <input type="number" name="parite" class="form-control" placeholder="ex: 1" min="0" max="20">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Âge gestationnel (SA)</label>
                        <input type="number" name="age_gestationnel" class="form-control"
                               placeholder="semaines" min="22" max="45">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">N° de Dossier</label>
                        <input type="text" name="dossier_no" class="form-control"
                               value="<?= htmlspecialchars($patient['dossier_numero'] ?? '') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Date d'admission</label>
                        <input type="date" name="date_admission" class="form-control"
                               value="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Heure d'admission</label>
                        <input type="time" name="heure_admission" class="form-control"
                               value="<?= date('H:i') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Rupture des membranes</label>
                        <input type="text" name="rupture_membranes"
                               class="form-control" placeholder="Spontanée / Artificielle / Intacte">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Heure rupture</label>
                        <input type="time" name="heure_rupture" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Médecin / Accoucheur</label>
                        <input type="text" name="medecin_accoucheur" class="form-control"
                               value="<?= htmlspecialchars($medecin_nom ?? '') ?>">
                    </div>
                </div>
            </div>
        </div>

        <!-- Saisie grille BCF -->
        <div class="pt-section">
            <div class="pt-section-head"><i class="bi bi-heart-pulse"></i> Bruits du Cœur Fœtal (BCF) — bpm</div>
            <div class="pt-section-body">
                <p class="text-muted small mb-2">Saisissez la valeur BCF pour chaque heure (normal : 120–160 bpm)</p>
                <div class="pt-grid-wrap">
                <table class="pt-grid">
                    <thead><tr>
                        <th class="row-label">Paramètre</th>
                        <?php for($h=1;$h<=12;$h++): ?><th>H<?=$h?></th><?php endfor; ?>
                    </tr></thead>
                    <tbody>
                    <tr style="background:#fafafa;">
                        <td class="row-label" style="font-style:italic;color:#64748b;">Heure réelle</td>
                        <?php for($h=1;$h<=12;$h++): ?>
                        <td><input type="time" name="heure_reelle[<?=$h?>]" style="width:100%;border:none;outline:none;text-align:center;font-size:.72rem;background:transparent;padding:2px 0;"></td>
                        <?php endfor; ?>
                    </tr>
                    <tr class="row-bcf">
                        <td class="row-label">BCF (bpm)</td>
                        <?php for($h=1;$h<=12;$h++): ?>
                        <td><input type="number" name="bcf[<?=$h?>]" placeholder="—"
                                   min="80" max="200" oninput="updateBCFChart()"></td>
                        <?php endfor; ?>
                    </tr>
                    <tr>
                        <td class="row-label">Liquide amniotique</td>
                        <?php for($h=1;$h<=12;$h++): ?>
                        <td><input type="text" name="liquide[<?=$h?>]" placeholder=""
                             title="I=Intact, C=Clair, T=Teinté, S=Sanglant"></td>
                        <?php endfor; ?>
                    </tr>
                    <tr>
                        <td class="row-label">Modelage de la tête</td>
                        <?php for($h=1;$h<=12;$h++): ?>
                        <td><input type="text" name="chevauche[<?=$h?>]" placeholder=""
                             title="0=Absent, +=Présent, ++=Important"></td>
                        <?php endfor; ?>
                    </tr>
                    </tbody>
                </table>
                </div>
                <div style="font-size:.73rem;color:var(--pt-muted);margin-top:6px;">
                    <strong>Liquide :</strong> I=Intact · C=Clair · T=Teinté · S=Sanglant &nbsp;|&nbsp;
                    <strong>Chevauchement :</strong> 0=Absent · +=Présent · ++=Important
                </div>
            </div>
        </div>

        <!-- Dilatation col & Descente -->
        <div class="pt-section">
            <div class="pt-section-head"><i class="bi bi-bar-chart-steps"></i> Dilatation du col (cm) & Descente de la tête</div>
            <div class="pt-section-body">
                <div class="pt-grid-wrap">
                <table class="pt-grid">
                    <thead><tr>
                        <th class="row-label">Paramètre</th>
                        <?php for($h=1;$h<=12;$h++): ?><th>H<?=$h?></th><?php endfor; ?>
                    </tr></thead>
                    <tbody>
                    <tr class="row-col">
                        <td class="row-label">Col — dilatation X (cm)</td>
                        <?php for($h=1;$h<=12;$h++): ?>
                        <td><input type="number" name="col[<?=$h?>]" placeholder="—"
                                   min="0" max="10" step="0.5" oninput="updateColChart()"></td>
                        <?php endfor; ?>
                    </tr>
                    <tr>
                        <td class="row-label" style="color:#7c3aed;">Descente tête O (0–5)</td>
                        <?php for($h=1;$h<=12;$h++): ?>
                        <td><input type="number" name="descente[<?=$h?>]" placeholder="—" min="0" max="5" step="1"
                                   oninput="updateDescenteChart()" style="color:#7c3aed;"></td>
                        <?php endfor; ?>
                    </tr>
                    </tbody>
                </table>
                </div>
            </div>
        </div>

        <!-- Contractions -->
        <div class="pt-section">
            <div class="pt-section-head"><i class="bi bi-activity"></i> Contractions (nombre / 10 min)</div>
            <div class="pt-section-body">
                <div class="pt-grid-wrap">
                <table class="pt-grid">
                    <thead><tr>
                        <th class="row-label">Paramètre</th>
                        <?php for($h=1;$h<=12;$h++): ?><th>H<?=$h?></th><?php endfor; ?>
                    </tr></thead>
                    <tbody>
                    <tr>
                        <td class="row-label">Contractions / 10 min</td>
                        <?php for($h=1;$h<=12;$h++): ?>
                        <td><input type="number" name="contractions[<?=$h?>]"
                                   placeholder="—" min="0" max="7"></td>
                        <?php endfor; ?>
                    </tr>
                    <tr>
                        <td class="row-label">Oxytocine U/L</td>
                        <?php for($h=1;$h<=12;$h++): ?>
                        <td><input type="text" name="ocytocine_u[<?=$h?>]" placeholder=""></td>
                        <?php endfor; ?>
                    </tr>
                    <tr>
                        <td class="row-label">Gouttes / min</td>
                        <?php for($h=1;$h<=12;$h++): ?>
                        <td><input type="text" name="ocytocine_g[<?=$h?>]" placeholder=""></td>
                        <?php endfor; ?>
                    </tr>
                    <tr>
                        <td class="row-label">Médicaments / Liquide IV</td>
                        <?php for($h=1;$h<=12;$h++): ?>
                        <td><input type="text" name="medicaments[<?=$h?>]" placeholder=""></td>
                        <?php endfor; ?>
                    </tr>
                    </tbody>
                </table>
                </div>
            </div>
        </div>

        <!-- TA, Poids, Température, Urine -->
        <div class="pt-section">
            <div class="pt-section-head"><i class="bi bi-thermometer-half"></i> Surveillance maternelle</div>
            <div class="pt-section-body">
                <div class="pt-grid-wrap">
                <table class="pt-grid">
                    <thead><tr>
                        <th class="row-label">Paramètre</th>
                        <?php for($h=1;$h<=12;$h++): ?><th>H<?=$h?></th><?php endfor; ?>
                    </tr></thead>
                    <tbody>
                    <tr>
                        <td class="row-label">Pouls • (bpm)</td>
                        <?php for($h=1;$h<=12;$h++): ?>
                        <td><input type="number" name="pouls[<?=$h?>]" placeholder="—" min="40" max="200"
                                   oninput="updatePoulsTaChart()"></td>
                        <?php endfor; ?>
                    </tr>
                    <tr>
                        <td class="row-label">TA ↑↓ (mmHg)</td>
                        <?php for($h=1;$h<=12;$h++): ?>
                        <td><input type="text" name="ta[<?=$h?>]" placeholder="—"
                                   oninput="updatePoulsTaChart()"></td>
                        <?php endfor; ?>
                    </tr>
                    <tr>
                        <td class="row-label">Température (°C)</td>
                        <?php for($h=1;$h<=12;$h++): ?>
                        <td><input type="text" name="temp[<?=$h?>]" placeholder="—"></td>
                        <?php endfor; ?>
                    </tr>
                    <tr>
                        <td class="row-label">Urine — Protéinurie</td>
                        <?php for($h=1;$h<=12;$h++): ?>
                        <td><input type="text" name="prot[<?=$h?>]" placeholder=""></td>
                        <?php endfor; ?>
                    </tr>
                    <tr>
                        <td class="row-label">Urine — Cétone</td>
                        <?php for($h=1;$h<=12;$h++): ?>
                        <td><input type="text" name="cetone[<?=$h?>]" placeholder=""></td>
                        <?php endfor; ?>
                    </tr>
                    <tr>
                        <td class="row-label">Urine — Volume (mL)</td>
                        <?php for($h=1;$h<=12;$h++): ?>
                        <td><input type="number" name="volume[<?=$h?>]" placeholder=""></td>
                        <?php endfor; ?>
                    </tr>
                    </tbody>
                </table>
                </div>
            </div>
        </div>
    </div><!-- /tab-admission -->

    <!-- ══════════════════════════════════════════════════════
         ONGLET 2 — GRAPHIQUES
    ══════════════════════════════════════════════════════ -->
    <div class="pt-pane" id="tab-partograph">

        <div class="pt-section">
            <div class="pt-section-head"><i class="bi bi-heart-pulse"></i> Courbe BCF (60–220 bpm)</div>
            <div class="pt-section-body">
                <div class="chart-hint">
                    <i class="bi bi-mouse2"></i>
                    <span><strong>Clic gauche</strong> sur le graphique pour placer un point BCF &nbsp;·&nbsp;
                          <strong>Clic droit</strong> pour effacer un point &nbsp;·&nbsp;
                          Les valeurs saisies dans l'onglet <em>Admission</em> se tracent aussi automatiquement.</span>
                </div>
                <div class="chart-container">
                    <canvas id="bcfCanvas" class="pt-chart" height="300"></canvas>
                    <div class="chart-legend">
                        <span><span class="legend-dot" style="background:#be123c;"></span> BCF (bpm)</span>
                        <span><span class="legend-dot" style="background:#059669;border-radius:0;height:3px;width:14px;"></span> Zone normale : 120–160 bpm</span>
                        <span style="color:#94a3b8;font-size:.72rem;">Cliquez sur le graphique pour saisir les valeurs</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Liquide amniotique + Modelage : lignes OMS entre BCF et dilatation -->
        <div class="pt-section">
            <div class="pt-section-head"><i class="bi bi-droplet-half"></i> Liquide amniotique &amp; Modelage de la tête</div>
            <div class="pt-section-body" style="padding:10px 18px;">
                <div class="pt-grid-wrap">
                <table class="pt-grid">
                    <thead><tr>
                        <th class="row-label">Paramètre</th>
                        <?php for($h=1;$h<=12;$h++): ?><th>H<?=$h?></th><?php endfor; ?>
                    </tr></thead>
                    <tbody>
                    <tr>
                        <td class="row-label">Liquide amniotique</td>
                        <?php for($h=1;$h<=12;$h++): ?>
                        <td><span id="g_liquide_<?=$h?>" class="pt-grid-mirror" style="font-size:.78rem;font-weight:700;display:block;text-align:center;padding:3px 0;">—</span></td>
                        <?php endfor; ?>
                    </tr>
                    <tr>
                        <td class="row-label">Modelage de la tête</td>
                        <?php for($h=1;$h<=12;$h++): ?>
                        <td><span id="g_chevauche_<?=$h?>" class="pt-grid-mirror" style="font-size:.78rem;font-weight:700;display:block;text-align:center;padding:3px 0;">—</span></td>
                        <?php endfor; ?>
                    </tr>
                    </tbody>
                </table>
                </div>
                <div style="font-size:.7rem;color:var(--pt-muted);margin-top:5px;">
                    <strong>Liquide :</strong> I=Intact · C=Clair · T=Teinté · S=Sanglant &nbsp;|&nbsp;
                    <strong>Modelage :</strong> 0=Absent · +=Présent · ++=Important &nbsp;|&nbsp;
                    <em>Les valeurs se mettent à jour depuis l'onglet Admission.</em>
                </div>
            </div>
        </div>

        <div class="pt-section">
            <div class="pt-section-head"><i class="bi bi-graph-up-arrow"></i> Courbe de Dilatation du col (0–10 cm) &amp; Descente de la tête (0–5)</div>
            <div class="pt-section-body">
                <div class="chart-hint">
                    <i class="bi bi-mouse2"></i>
                    <span><strong>Clic gauche</strong> sur le graphique pour placer la dilatation (cm) &nbsp;·&nbsp;
                          <strong>Clic droit</strong> pour effacer &nbsp;·&nbsp;
                          Lignes OMS d'<span style="color:#f59e0b;font-weight:700;">Alerte</span> et
                          d'<span style="color:#dc2626;font-weight:700;">Action</span> — 1 cm/h &nbsp;·&nbsp;
                          <span style="color:#7c3aed;font-weight:700;">Descente (O)</span> tracée automatiquement depuis la saisie.</span>
                </div>
                <div class="chart-container">
                    <canvas id="colCanvas" class="pt-chart" height="340"></canvas>
                    <div class="chart-legend">
                        <span><span class="legend-dot" style="background:#0369a1;"></span> Dilatation du col — X (cm)</span>
                        <span><span class="legend-dot" style="background:#7c3aed;border-radius:50%;border:2px solid #7c3aed;background:white;width:10px;height:10px;display:inline-block;"></span> Descente de la tête — O (0–5)</span>
                        <span><span class="legend-dot" style="background:#f59e0b;"></span> Alerte (1 cm/h)</span>
                        <span><span class="legend-dot" style="background:#dc2626;"></span> Action (+4h)</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="pt-section">
            <div class="pt-section-head"><i class="bi bi-heart-pulse"></i> Pouls (•) et Tension Artérielle (↑↓) — 60–180</div>
            <div class="pt-section-body">
                <div class="chart-hint">
                    <i class="bi bi-info-circle"></i>
                    <span>Les valeurs sont lues depuis la saisie de l'onglet <em>Admission</em> (Pouls et TA) et tracées automatiquement.
                          <strong style="color:#be123c;">●</strong> Pouls &nbsp;·&nbsp;
                          <strong style="color:#0369a1;">↑↓</strong> Tension artérielle (systolique / diastolique)</span>
                </div>
                <div class="chart-container">
                    <canvas id="poulsTaCanvas" class="pt-chart" height="280"></canvas>
                    <div class="chart-legend">
                        <span><span class="legend-dot" style="background:#be123c;"></span> Pouls (bpm) — point •</span>
                        <span style="display:inline-flex;align-items:center;gap:4px;">
                            <span style="font-size:1rem;color:#0369a1;font-weight:900;line-height:1;">↑↓</span>
                            <span style="font-size:.75rem;font-weight:600;color:#64748b;">TA (mmHg) — flèches haut/bas</span>
                        </span>
                    </div>
                </div>
            </div>
        </div>

    </div><!-- /tab-partograph -->

    <!-- ══════════════════════════════════════════════════════
         ONGLET 3 — ACCOUCHEMENT
    ══════════════════════════════════════════════════════ -->
    <div class="pt-pane" id="tab-accouchement">

        <div class="pt-section">
            <div class="pt-section-head"><i class="bi bi-hospital"></i> Naissance & Délivrance</div>
            <div class="pt-section-body">
                <div class="row g-3">
                    <div class="col-md-3"><label class="form-label">Enfant né le</label>
                        <input type="date" name="enfant_ne_le" class="form-control" value="<?= date('Y-m-d') ?>"></div>
                    <div class="col-md-3"><label class="form-label">Heure de naissance</label>
                        <input type="time" name="enfant_ne_heure" class="form-control"></div>
                    <div class="col-md-3"><label class="form-label">Délivrance placenta à</label>
                        <input type="time" name="delivrance_heure" class="form-control"></div>
                    <div class="col-md-3"><label class="form-label">Poids NN (g)</label>
                        <input type="number" name="poids_nn" class="form-control" placeholder="grammes" min="300" max="6000"></div>
                    <div class="col-md-3"><label class="form-label">Thorax PC (cm)</label>
                        <input type="number" name="thorax_pc" class="form-control" step="0.1"></div>
                    <div class="col-md-3"><label class="form-label">Thorax PT (cm)</label>
                        <input type="number" name="thorax_pt" class="form-control" step="0.1"></div>
                    <div class="col-md-3"><label class="form-label">Sexe de l'enfant</label>
                        <select name="sexe_nn" class="form-select">
                            <option value="">— choisir —</option>
                            <option value="M">Masculin</option>
                            <option value="F">Féminin</option>
                        </select>
                    </div>
                    <div class="col-md-3"><label class="form-label">Taille (cm)</label>
                        <input type="number" name="taille_nn" class="form-control" step="0.5"></div>
                </div>
            </div>
        </div>

        <div class="pt-section">
            <div class="pt-section-head"><i class="bi bi-stopwatch"></i> Travail</div>
            <div class="pt-section-body">
                <div class="row g-3">
                    <div class="col-md-4"><label class="form-label">Phase de latence (I) — heures</label>
                        <input type="number" name="phase_latence" class="form-control" step="0.5" min="0"></div>
                    <div class="col-md-4"><label class="form-label">Phase active (II) — heures</label>
                        <input type="number" name="phase_active" class="form-control" step="0.5" min="0"></div>
                    <div class="col-md-4"><label class="form-label">Durée totale (I+II) — heures</label>
                        <input type="number" name="duree_totale" class="form-control" step="0.5" min="0"></div>
                </div>
            </div>
        </div>

        <div class="pt-section">
            <div class="pt-section-head"><i class="bi bi-clipboard2-pulse"></i> Type d'accouchement</div>
            <div class="pt-section-body">
                <div class="row g-3">
                    <div class="col-md-12">
                        <label class="form-label">Voie d'accouchement</label>
                        <div class="pill-group">
                            <?php foreach(['Normal','Difficile','Traumatique'] as $v): ?>
                            <div class="pill-radio">
                                <input type="radio" name="type_accouchement" id="acc_<?=$v?>" value="<?=$v?>">
                                <label for="acc_<?=$v?>"><?=$v?></label>
                            </div>
                            <?php endforeach; ?>
                            <div class="pill-radio">
                                <input type="radio" name="type_accouchement" id="acc_ces" value="Césarienne">
                                <label for="acc_ces"><i class="bi bi-plus-circle"></i> Césarienne</label>
                            </div>
                            <div class="pill-radio">
                                <input type="radio" name="type_accouchement" id="acc_vent" value="Ventouse">
                                <label for="acc_vent">Ventouse</label>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6" id="cesarienne_type_row" style="display:none;">
                        <label class="form-label">Type de césarienne</label>
                        <select name="cesarienne_type" class="form-select">
                            <option value="">— choisir —</option>
                            <option value="transverse">Transverse</option>
                            <option value="classique">Classique</option>
                            <option value="autre">Autre</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Déchirure</label>
                        <div class="pill-group">
                            <?php foreach(['1er degré'=>'1er','2e degré'=>'2e','3e degré'=>'3e'] as $label=>$val): ?>
                            <div class="pill-radio">
                                <input type="radio" name="dechirure" id="dech_<?=$val?>" value="<?=$val?>">
                                <label for="dech_<?=$val?>"><?=$label?></label>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Épisiotomie</label>
                        <div class="pill-group">
                            <?php foreach(['Médiane','Médio-latérale gauche','Médio-latérale droite','Aucune'] as $v): ?>
                            <div class="pill-radio">
                                <input type="radio" name="episiotomie" id="epi_<?=md5($v)?>" value="<?=$v?>">
                                <label for="epi_<?=md5($v)?>"><?=$v?></label>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- APGAR -->
        <div class="pt-section">
            <div class="pt-section-head"><i class="bi bi-stars"></i> Score d'APGAR</div>
            <div class="pt-section-body">
                <div class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label">APGAR à 1 min</label>
                        <input type="number" name="apgar_1min" class="form-control"
                               min="0" max="10" oninput="colorApgar(this)">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">APGAR à 5 min</label>
                        <input type="number" name="apgar_5min" class="form-control"
                               min="0" max="10" oninput="colorApgar(this)">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">APGAR à 10 min</label>
                        <input type="number" name="apgar_10min" class="form-control"
                               min="0" max="10" oninput="colorApgar(this)">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Aspect</label>
                        <div class="pill-group">
                            <div class="pill-radio">
                                <input type="radio" name="aspect_nn" id="asp_n" value="Normal">
                                <label for="asp_n" style="color:#059669;border-color:#059669;">Normal</label>
                            </div>
                            <div class="pill-radio">
                                <input type="radio" name="aspect_nn" id="asp_a" value="Anormal">
                                <label for="asp_a" style="color:#dc2626;border-color:#dc2626;">Anormal</label>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Explications si anormal</label>
                        <input type="text" name="aspect_explications" class="form-control">
                    </div>
                </div>
            </div>
        </div>

        <!-- Soins NN -->
        <div class="pt-section">
            <div class="pt-section-head"><i class="bi bi-heart"></i> Soins immédiats du nouveau-né</div>
            <div class="pt-section-body">
                <div class="row g-3">
                    <?php
                    $soinsNN = [
                        'bebe_rechauffe'   => 'Bébé réchauffé',
                        'vit_k1'           => 'Vit K1 administrée',
                        'soins_cordon'     => 'Soins du cordon',
                    ];
                    foreach($soinsNN as $name => $label): ?>
                    <div class="col-md-4">
                        <label class="form-label"><?= $label ?></label>
                        <div class="d-flex gap-3">
                            <label style="display:flex;align-items:center;gap:5px;font-weight:600;font-size:.88rem;cursor:pointer;">
                                <input type="radio" name="<?=$name?>" value="Oui"> Oui
                            </label>
                            <label style="display:flex;align-items:center;gap:5px;font-weight:600;font-size:.88rem;cursor:pointer;">
                                <input type="radio" name="<?=$name?>" value="Non" checked> Non
                            </label>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

    </div><!-- /tab-accouchement -->

    <!-- ══════════════════════════════════════════════════════
         ONGLET 4 — POST-PARTUM (J0→J3)
    ══════════════════════════════════════════════════════ -->
    <div class="pt-pane" id="tab-postpartum">

        <!-- Post-partum immédiat -->
        <div class="pt-section">
            <div class="pt-section-head"><i class="bi bi-clipboard2-heart"></i> Post-partum immédiat</div>
            <div class="pt-section-body">
                <div class="row g-3">
                    <div class="col-md-3"><label class="form-label">Utérus</label>
                        <input type="text" name="pp_uterus" class="form-control" placeholder="Tonique / Mou"></div>
                    <div class="col-md-3"><label class="form-label">Épisiotomie</label>
                        <input type="text" name="pp_episiotomie" class="form-control" placeholder="Bon état / Suintant"></div>
                    <div class="col-md-3"><label class="form-label">TA post-partum</label>
                        <input type="text" name="pp_ta" class="form-control" placeholder="ex: 120/80"></div>
                    <div class="col-md-3"><label class="form-label">Hct</label>
                        <input type="text" name="pp_hct" class="form-control" placeholder="%"></div>
                    <div class="col-md-6"><label class="form-label">État du périnée</label>
                        <input type="text" name="pp_perinee" class="form-control"></div>
                    <div class="col-md-6"><label class="form-label">Jour</label>
                        <input type="date" name="pp_jour" class="form-control" value="<?= date('Y-m-d') ?>"></div>
                </div>
            </div>
        </div>

        <!-- Suivi mère J0→J3 -->
        <div class="pt-section">
            <div class="pt-section-head"><i class="bi bi-person-heart"></i> Suivi de la Mère (J0 → J3)</div>
            <div class="pt-section-body">
                <div style="overflow-x:auto;">
                <table class="suivi-table">
                    <thead>
                        <tr>
                            <th colspan="2" style="min-width:150px;">MÈRE</th>
                            <th>J0 M</th><th>J0 S</th>
                            <th>J1 M</th><th>J1 S</th>
                            <th>J2 M</th><th>J2 S</th>
                            <th>J3 M</th><th>J3 S</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                    $mereSuivi = [
                        ['T (°C)', 'mere_temp'],
                        ['Pouls', 'mere_pouls'],
                        ['TA', 'mere_ta'],
                        ['État général', 'mere_etat'],
                        ['Mamelons — Normaux', 'mama_normal'],
                        ['Mamelons — Fissurés', 'mama_fissures'],
                        ['Mamelons — Ombiliqués', 'mama_ombiliques'],
                        ['Seins — Normaux', 'seins_normal'],
                        ['Seins — Douloureux', 'seins_douloureux'],
                        ['Utérus', 'mere_uterus'],
                        ['Lochies — Fétides', 'lochies_fetides'],
                        ['Lochies — Normales', 'lochies_normales'],
                        ['Périnée', 'perinee'],
                        ['Jambes — Normales', 'jambes_normal'],
                        ['Jambes — Anormales', 'jambes_anorm'],
                        ['Traitements', 'traitements'],
                    ];
                    foreach($mereSuivi as [$label, $key]): ?>
                    <tr>
                        <td colspan="2" class="sub-label"><?= $label ?></td>
                        <?php for($j=0;$j<4;$j++): foreach(['M','S'] as $ms): ?>
                        <td><input type="text" name="<?=$key?>[J<?=$j?>_<?=$ms?>]"></td>
                        <?php endforeach; endfor; ?>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                </div>
            </div>
        </div>

        <!-- Suivi enfant J0→J3 -->
        <div class="pt-section">
            <div class="pt-section-head"><i class="bi bi-person-fill"></i> Suivi de l'Enfant (J0 → J3)</div>
            <div class="pt-section-body">
                <div style="overflow-x:auto;">
                <table class="suivi-table">
                    <thead>
                        <tr>
                            <th colspan="2" style="min-width:160px;">ENFANT</th>
                            <th>J0 M</th><th>J0 S</th>
                            <th>J1 M</th><th>J1 S</th>
                            <th>J2 M</th><th>J2 S</th>
                            <th>J3 M</th><th>J3 S</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                    $enfantSuivi = [
                        ['Température (°C)', 'enf_temp'],
                        ['Fontanelle — Bombée', 'font_bombee'],
                        ['Fontanelle — Enfoncée', 'font_enfoncee'],
                        ['Fontanelle — Normale', 'font_normale'],
                        ['Éveil — Oui', 'eveil_oui'],
                        ['Éveil — Non', 'eveil_non'],
                        ['Yeux — Blancs', 'yeux_blancs'],
                        ['Yeux — Rouges', 'yeux_rouges'],
                        ['Soins du cordon', 'cordon_soins'],
                        ['Peau — Cyanose', 'peau_cyanose'],
                        ['Peau — Sèche', 'peau_seche'],
                        ['Peau — Normale', 'peau_normale'],
                        ['Nombril — Normal', 'nombril_normal'],
                        ['Nombril — Infecté', 'nombril_infecte'],
                        ['Poids (g)', 'enf_poids'],
                        ['Respiration', 'enf_resp'],
                        ['Selles — Normales', 'selles_normal'],
                        ['Selles — Méconium', 'selles_meco'],
                        ['Selles — Couleur', 'selles_couleur'],
                        ['Tétée — Bien', 'tetee_bien'],
                        ['Tétée — Mal', 'tetee_mal'],
                    ];
                    foreach($enfantSuivi as [$label, $key]): ?>
                    <tr>
                        <td colspan="2" class="sub-label"><?= $label ?></td>
                        <?php for($j=0;$j<4;$j++): foreach(['M','S'] as $ms): ?>
                        <td><input type="text" name="<?=$key?>[J<?=$j?>_<?=$ms?>]"></td>
                        <?php endforeach; endfor; ?>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                </div>
            </div>
        </div>

        <!-- Note de départ -->
        <div class="pt-section">
            <div class="pt-section-head"><i class="bi bi-journal-text"></i> Note de départ</div>
            <div class="pt-section-body">
                <textarea name="note_depart" class="form-control" rows="4"
                          placeholder="Recommandations, traitement de sortie, RDV de suivi..."></textarea>
            </div>
        </div>

    </div><!-- /tab-postpartum -->

</form>
</div><!-- /pt-wrap -->

<script>
/* ══════════════════════════════════════════════════════════════
   PARTOGRAMME — Graphiques interactifs
   ══════════════════════════════════════════════════════════════ */

/* ── Synchroniser les lignes miroir Liquide + Modelage ──────── */
function syncMirrorRows() {
    for (let h = 1; h <= 12; h++) {
        const liq = document.querySelector(`[name="liquide[${h}]"]`);
        const chv = document.querySelector(`[name="chevauche[${h}]"]`);
        const gl  = document.getElementById('g_liquide_'  + h);
        const gc  = document.getElementById('g_chevauche_' + h);
        if (gl) gl.textContent = (liq && liq.value.trim()) ? liq.value.trim() : '—';
        if (gc) gc.textContent = (chv && chv.value.trim()) ? chv.value.trim() : '—';
    }
    /* Re-synchroniser en temps réel si l'onglet est actif */
    document.querySelectorAll('[name^="liquide["], [name^="chevauche["]').forEach(el => {
        el.addEventListener('input', () => {
            if (isGraphTab()) syncMirrorRows();
        }, { once: false });
    });
}

/* ── Onglets ─────────────────────────────────────────────────── */
function showTab(id) {
    document.querySelectorAll('.pt-pane').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.pt-tab').forEach(t => t.classList.remove('active'));
    document.getElementById(id).classList.add('active');
    event.currentTarget.classList.add('active');
    if (id === 'tab-partograph') { drawBCF(); drawCol(); drawPoulsTa(); syncMirrorRows(); }
}

/* ── Type accouchement → afficher césarienne ─────────────────── */
document.querySelectorAll('[name="type_accouchement"]').forEach(r => {
    r.addEventListener('change', function() {
        document.getElementById('cesarienne_type_row').style.display =
            this.value === 'Césarienne' ? 'block' : 'none';
    });
});

/* ── APGAR color ─────────────────────────────────────────────── */
function colorApgar(el) {
    const v = parseInt(el.value);
    el.style.color      = v >= 7 ? '#059669' : v >= 4 ? '#d97706' : '#dc2626';
    el.style.borderColor = v >= 7 ? '#059669' : v >= 4 ? '#d97706' : '#dc2626';
}

/* ══════════════════════════════════════════════════════════════
   MOTEUR DE GRAPHIQUES INTERACTIFS
   ══════════════════════════════════════════════════════════════ */

/* ── Configuration des deux graphiques ── */
const BCF_CFG = {
    id: 'bcfCanvas', name: 'bcf', unit: 'bpm',
    minY: 80, maxY: 200, step: 5,          /* OMS : 80–200 bpm */
    pad: { top: 28, right: 28, bottom: 38, left: 54 },
    color: '#be123c',
    hoverBg: 'rgba(190,18,60,.07)',
    gridStep: 20,
};
const COL_CFG = {
    id: 'colCanvas', name: 'col', unit: 'cm',
    minY: 0, maxY: 10, step: 0.5,
    pad: { top: 28, right: 58, bottom: 38, left: 44 }, /* right élargi : axe descente */
    color: '#0369a1',
    hoverBg: 'rgba(3,105,161,.07)',
    gridStep: 1,
};

/* ── État du survol (par graphique) ─────────────────────────── */
const hover = { bcf: null, col: null };   // { hour:int, value:float } | null

/* ── Lire/écrire les valeurs dans les inputs de la grille ───── */
function getVals(name) {
    const out = [];
    for (let h = 1; h <= 12; h++) {
        const el = document.querySelector(`[name="${name}[${h}]"]`);
        out.push(el && el.value !== '' ? parseFloat(el.value) : null);
    }
    return out;
}
function setVal(name, hour, value) {
    const el = document.querySelector(`[name="${name}[${hour}]"]`);
    if (!el) return;
    el.value = (value === null) ? '' : value;
    el.dispatchEvent(new Event('input', { bubbles: true }));
}

/* ── Conversions pixel ↔ logique ────────────────────────────── */
function hourToX(cfg, W, h) {
    const cW = W - cfg.pad.left - cfg.pad.right;
    return cfg.pad.left + (h / 12) * cW;
}
function valToY(cfg, H, v) {
    const cH = H - cfg.pad.top - cfg.pad.bottom;
    return cfg.pad.top + cH * (1 - (v - cfg.minY) / (cfg.maxY - cfg.minY));
}
function xToHour(cfg, W, px) {
    const cW = W - cfg.pad.left - cfg.pad.right;
    return Math.round((px - cfg.pad.left) / cW * 12);
}
function yToVal(cfg, H, py) {
    const cH = H - cfg.pad.top - cfg.pad.bottom;
    const raw = cfg.maxY - (py - cfg.pad.top) / cH * (cfg.maxY - cfg.minY);
    const snapped = Math.round(raw / cfg.step) * cfg.step;
    return Math.max(cfg.minY, Math.min(cfg.maxY, +snapped.toFixed(2)));
}
function inChart(cfg, W, H, px, py) {
    return px >= cfg.pad.left && px <= W - cfg.pad.right
        && py >= cfg.pad.top  && py <= H - cfg.pad.bottom;
}
function canvasPt(canvas, evt) {
    const r = canvas.getBoundingClientRect();
    return {
        x: (evt.clientX - r.left) * (canvas.width  / r.width),
        y: (evt.clientY - r.top)  * (canvas.height / r.height),
    };
}

/* ── Fond de grille ─────────────────────────────────────────── */
function drawGrid(ctx, cfg, W, H) {
    const { pad, minY, maxY, gridStep } = cfg;
    const cW = W - pad.left - pad.right;
    const cH = H - pad.top  - pad.bottom;

    /* lignes horizontales */
    ctx.lineWidth = 0.7; ctx.strokeStyle = '#e2e8f0';
    ctx.fillStyle = '#94a3b8'; ctx.font = '10px system-ui'; ctx.textAlign = 'right';
    for (let v = minY; v <= maxY; v += gridStep) {
        const y = valToY(cfg, H, v);
        ctx.beginPath(); ctx.moveTo(pad.left, y); ctx.lineTo(W - pad.right, y); ctx.stroke();
        ctx.fillText(v, pad.left - 5, y + 3.5);
    }
    /* lignes verticales */
    ctx.textAlign = 'center';
    for (let h = 0; h <= 12; h++) {
        const x = pad.left + (h / 12) * cW;
        ctx.beginPath(); ctx.moveTo(x, pad.top); ctx.lineTo(x, H - pad.bottom); ctx.stroke();
        if (h > 0) ctx.fillText('H' + h, x, H - pad.bottom + 15);
    }
}

/* ── Badge de survol (bulle valeur) ─────────────────────────── */
function drawBadge(ctx, text, x, y, W, color) {
    ctx.font = 'bold 11px system-ui';
    const tw = ctx.measureText(text).width;
    const bw = tw + 14, bh = 20;
    let bx = x + 12, by = y - bh / 2;
    if (bx + bw > W - 20) bx = x - bw - 12;
    if (by < 4) by = 4;

    /* fond arrondi */
    ctx.fillStyle = color;
    ctx.beginPath();
    const r = 5;
    ctx.moveTo(bx + r, by);
    ctx.lineTo(bx + bw - r, by);
    ctx.arcTo(bx + bw, by, bx + bw, by + r, r);
    ctx.lineTo(bx + bw, by + bh - r);
    ctx.arcTo(bx + bw, by + bh, bx + bw - r, by + bh, r);
    ctx.lineTo(bx + r, by + bh);
    ctx.arcTo(bx, by + bh, bx, by + bh - r, r);
    ctx.lineTo(bx, by + r);
    ctx.arcTo(bx, by, bx + r, by, r);
    ctx.closePath(); ctx.fill();

    ctx.fillStyle = '#fff'; ctx.textAlign = 'left';
    ctx.fillText(text, bx + 7, by + 14);
}

/* ── Moteur de dessin générique ─────────────────────────────── */
function renderChart(cfg, extraFn) {
    const canvas = document.getElementById(cfg.id);
    if (!canvas) return;
    const ctx = canvas.getContext('2d');
    const W = canvas.offsetWidth; canvas.width = W;
    const H = canvas.height;
    const { pad, color, name, unit } = cfg;
    const cW = W - pad.left - pad.right;
    const cH = H - pad.top  - pad.bottom;
    const hov = hover[name];

    ctx.clearRect(0, 0, W, H);

    /* fond de grille */
    drawGrid(ctx, cfg, W, H);

    /* zones / lignes spécifiques (normale BCF, alerte/action Col) */
    if (extraFn) extraFn(ctx, cfg, W, H);

    /* surlignage de la colonne survolée */
    if (hov) {
        const hx = hourToX(cfg, W, hov.hour);
        ctx.fillStyle = cfg.hoverBg;
        ctx.fillRect(hx - cW / 24, pad.top, cW / 12, cH);
    }

    /* courbe */
    const vals = getVals(name);
    ctx.strokeStyle = color; ctx.lineWidth = 2.5; ctx.lineJoin = 'round';
    ctx.setLineDash([]);
    ctx.beginPath();
    let started = false;
    vals.forEach((v, i) => {
        if (v === null) return;
        const x = hourToX(cfg, W, i + 1);
        const y = valToY(cfg, H, v);
        started ? ctx.lineTo(x, y) : ctx.moveTo(x, y);
        started = true;
    });
    ctx.stroke();

    /* points + labels de valeur */
    vals.forEach((v, i) => {
        if (v === null) return;
        const x = hourToX(cfg, W, i + 1);
        const y = valToY(cfg, H, v);

        /* cercle blanc bordé */
        ctx.beginPath(); ctx.arc(x, y, 5.5, 0, Math.PI * 2);
        ctx.fillStyle = 'white'; ctx.fill();
        ctx.beginPath(); ctx.arc(x, y, 5.5, 0, Math.PI * 2);
        ctx.strokeStyle = color; ctx.lineWidth = 2.2; ctx.stroke();

        /* étiquette */
        ctx.fillStyle = color; ctx.font = 'bold 9px system-ui'; ctx.textAlign = 'center';
        const label = unit === 'cm' ? v : v;
        const ly = (y - 12 < pad.top + 10) ? y + 16 : y - 10;
        ctx.fillText(label, x, ly);
    });

    /* curseur de survol (crosshair + badge) */
    if (hov) {
        const hx = hourToX(cfg, W, hov.hour);
        const hy = valToY(cfg, H, hov.value);

        ctx.setLineDash([5, 4]);
        ctx.strokeStyle = color + 'aa'; ctx.lineWidth = 1;
        ctx.beginPath(); ctx.moveTo(hx, pad.top); ctx.lineTo(hx, H - pad.bottom); ctx.stroke();
        ctx.beginPath(); ctx.moveTo(pad.left, hy); ctx.lineTo(W - pad.right, hy); ctx.stroke();
        ctx.setLineDash([]);

        /* point fantôme */
        ctx.beginPath(); ctx.arc(hx, hy, 7, 0, Math.PI * 2);
        ctx.fillStyle = color + '40'; ctx.fill();
        ctx.strokeStyle = color; ctx.lineWidth = 2; ctx.stroke();

        /* badge valeur */
        const badge = `H${hov.hour}  ${hov.value} ${unit}`;
        drawBadge(ctx, badge, hx, hy, W, color);
    }
}

/* ── Extras BCF : zone normale 120–160 ──────────────────────── */
function bcfExtras(ctx, cfg, W, H) {
    const y120 = valToY(cfg, H, 120);
    const y160 = valToY(cfg, H, 160);
    ctx.fillStyle = 'rgba(5,150,105,.09)';
    ctx.fillRect(cfg.pad.left, y160, W - cfg.pad.left - cfg.pad.right, y120 - y160);
    /* lignes pointillées de la zone */
    [120, 160].forEach(v => {
        const y = valToY(cfg, H, v);
        ctx.setLineDash([6, 5]); ctx.strokeStyle = '#059669'; ctx.lineWidth = 1;
        ctx.beginPath(); ctx.moveTo(cfg.pad.left, y); ctx.lineTo(W - cfg.pad.right, y); ctx.stroke();
        ctx.setLineDash([]);
    });
}

/* ── Extras Col : lignes OMS Alerte + Action ─────────────────── */
function colExtras(ctx, cfg, W, H) {
    /* Alerte : 4 cm à H1 → 10 cm à H7  (1 cm/heure — OMS) */
    const ax1 = hourToX(cfg, W, 1), ay1 = valToY(cfg, H, 4);
    const ax2 = hourToX(cfg, W, 7), ay2 = valToY(cfg, H, 10);
    ctx.setLineDash([8, 5]); ctx.lineWidth = 2.5; ctx.strokeStyle = '#f59e0b';
    ctx.beginPath(); ctx.moveTo(ax1, ay1); ctx.lineTo(ax2, ay2); ctx.stroke();
    ctx.fillStyle = '#f59e0b'; ctx.font = 'bold 10px system-ui'; ctx.textAlign = 'left';
    ctx.fillText('⚠ Alerte', ax1 + 4, ay1 - 5);

    /* Action : 4 cm à H5 → 10 cm à H11  (4 heures à droite de l'alerte — OMS) */
    const bx1 = hourToX(cfg, W, 5), by1 = valToY(cfg, H, 4);
    const bx2 = hourToX(cfg, W, 11), by2 = valToY(cfg, H, 10);
    ctx.strokeStyle = '#dc2626';
    ctx.beginPath(); ctx.moveTo(bx1, by1); ctx.lineTo(bx2, by2); ctx.stroke();
    ctx.fillStyle = '#dc2626';
    ctx.fillText('🚨 Action', bx1 + 4, by1 - 5);
    ctx.setLineDash([]);
}

/* ── Descente de la tête en superposition sur le graphe col ──── */
function drawDescente() {
    const canvas = document.getElementById('colCanvas');
    if (!canvas) return;
    const ctx = canvas.getContext('2d');
    const W = canvas.width, H = canvas.height;
    const cfg = COL_CFG;
    const cH = H - cfg.pad.top - cfg.pad.bottom;

    /* Axe Y droit : descente 0–5 */
    const dMinY = 0, dMaxY = 5;
    const dValToY = v => cfg.pad.top + cH * (1 - (v - dMinY) / (dMaxY - dMinY));
    const rightX  = W - cfg.pad.right + 8;

    /* Labels axe droit */
    ctx.fillStyle = '#7c3aed'; ctx.font = '9px system-ui'; ctx.textAlign = 'left';
    for (let v = 0; v <= 5; v++) {
        ctx.fillText(v, rightX, dValToY(v) + 3.5);
    }
    /* Label axe */
    ctx.save();
    ctx.translate(W - 10, cfg.pad.top + cH / 2);
    ctx.rotate(Math.PI / 2);
    ctx.textAlign = 'center'; ctx.font = 'bold 8px system-ui'; ctx.fillStyle = '#7c3aed';
    ctx.fillText('Descente (O)', 0, 0);
    ctx.restore();

    /* Récupérer les valeurs de descente */
    const vals = [];
    for (let h = 1; h <= 12; h++) {
        const el = document.querySelector(`[name="descente[${h}]"]`);
        vals.push(el && el.value !== '' ? parseFloat(el.value) : null);
    }

    /* Courbe pointillée */
    ctx.strokeStyle = '#7c3aed'; ctx.lineWidth = 2; ctx.setLineDash([4, 3]);
    ctx.beginPath();
    let started = false;
    vals.forEach((v, i) => {
        if (v === null) return;
        const x = hourToX(cfg, W, i + 1), y = dValToY(v);
        started ? ctx.lineTo(x, y) : ctx.moveTo(x, y);
        started = true;
    });
    ctx.stroke(); ctx.setLineDash([]);

    /* Marqueurs "O" */
    vals.forEach((v, i) => {
        if (v === null) return;
        const x = hourToX(cfg, W, i + 1), y = dValToY(v);
        ctx.beginPath(); ctx.arc(x, y, 6, 0, Math.PI * 2);
        ctx.strokeStyle = '#7c3aed'; ctx.lineWidth = 2; ctx.stroke();
        ctx.fillStyle = '#7c3aed'; ctx.font = 'bold 9px system-ui'; ctx.textAlign = 'center';
        const ly = (y - 14 < cfg.pad.top + 8) ? y + 16 : y - 10;
        ctx.fillText(v, x, ly);
    });
}

/* ── Graphique Pouls + TA combiné (60–180) ───────────────────── */
function drawPoulsTa() {
    const canvas = document.getElementById('poulsTaCanvas');
    if (!canvas) return;
    const ctx = canvas.getContext('2d');
    const W = canvas.offsetWidth; canvas.width = W;
    const H = canvas.height;
    const pad = { top: 28, right: 28, bottom: 38, left: 44 };
    const minY = 60, maxY = 180, gridStep = 10;
    const cW = W - pad.left - pad.right;
    const cH = H - pad.top  - pad.bottom;

    const hourX = h => pad.left + (h / 12) * cW;
    const valY  = v => pad.top + cH * (1 - (v - minY) / (maxY - minY));

    ctx.clearRect(0, 0, W, H);

    /* Grille */
    ctx.lineWidth = 0.7; ctx.strokeStyle = '#e2e8f0';
    ctx.fillStyle = '#94a3b8'; ctx.font = '10px system-ui'; ctx.textAlign = 'right';
    for (let v = minY; v <= maxY; v += gridStep) {
        const y = valY(v);
        ctx.beginPath(); ctx.moveTo(pad.left, y); ctx.lineTo(W - pad.right, y); ctx.stroke();
        if (v % 20 === 0) ctx.fillText(v, pad.left - 5, y + 3.5);
    }
    ctx.textAlign = 'center';
    for (let h = 0; h <= 12; h++) {
        const x = pad.left + (h / 12) * cW;
        ctx.beginPath(); ctx.moveTo(x, pad.top); ctx.lineTo(x, H - pad.bottom); ctx.stroke();
        if (h > 0) ctx.fillText('H' + h, x, H - pad.bottom + 15);
    }

    /* Lire pouls */
    const pouls = [];
    for (let h = 1; h <= 12; h++) {
        const el = document.querySelector(`[name="pouls[${h}]"]`);
        pouls.push(el && el.value !== '' ? parseFloat(el.value) : null);
    }
    /* Lire TA */
    const taSys = [], taDia = [];
    for (let h = 1; h <= 12; h++) {
        const el = document.querySelector(`[name="ta[${h}]"]`);
        if (el && el.value) {
            const p = el.value.split('/');
            taSys.push(p[0] && !isNaN(p[0]) ? parseFloat(p[0]) : null);
            taDia.push(p[1] && !isNaN(p[1]) ? parseFloat(p[1]) : null);
        } else { taSys.push(null); taDia.push(null); }
    }

    /* TA : flèches ↑↓ (bleu) */
    for (let i = 0; i < 12; i++) {
        const sys = taSys[i], dia = taDia[i];
        if (sys === null && dia === null) continue;
        const x = hourX(i + 1);
        ctx.strokeStyle = '#0369a1'; ctx.fillStyle = '#0369a1'; ctx.lineWidth = 2;
        if (sys !== null && dia !== null) {
            const ys = valY(sys), yd = valY(dia);
            ctx.beginPath(); ctx.moveTo(x, ys + 2); ctx.lineTo(x, yd - 2); ctx.stroke();
            /* flèche haut (systolique) */
            ctx.beginPath(); ctx.moveTo(x, ys - 7); ctx.lineTo(x - 4, ys + 1); ctx.lineTo(x + 4, ys + 1); ctx.closePath(); ctx.fill();
            /* flèche bas (diastolique) */
            ctx.beginPath(); ctx.moveTo(x, yd + 7); ctx.lineTo(x - 4, yd - 1); ctx.lineTo(x + 4, yd - 1); ctx.closePath(); ctx.fill();
        } else if (sys !== null) {
            ctx.beginPath(); ctx.arc(x, valY(sys), 4, 0, Math.PI * 2); ctx.fill();
        }
    }

    /* Pouls : ligne + points rouges (•) */
    ctx.strokeStyle = '#be123c'; ctx.lineWidth = 2.5; ctx.setLineDash([]);
    ctx.beginPath();
    let started = false;
    pouls.forEach((v, i) => {
        if (v === null) return;
        const x = hourX(i + 1), y = valY(v);
        started ? ctx.lineTo(x, y) : ctx.moveTo(x, y); started = true;
    });
    ctx.stroke();
    pouls.forEach((v, i) => {
        if (v === null) return;
        const x = hourX(i + 1), y = valY(v);
        ctx.beginPath(); ctx.arc(x, y, 5, 0, Math.PI * 2);
        ctx.fillStyle = '#be123c'; ctx.fill();
        ctx.font = 'bold 8px system-ui'; ctx.textAlign = 'center'; ctx.fillStyle = '#be123c';
        const ly = (y - 12 < pad.top + 8) ? y + 14 : y - 8;
        ctx.fillText(v, x, ly);
    });
}

/* ── Points d'entrée publics (appelés depuis oninput) ────────── */
function drawBCF() { renderChart(BCF_CFG, bcfExtras); }
function drawCol() { renderChart(COL_CFG, colExtras); drawDescente(); }
function updateBCFChart()     { if (isGraphTab()) drawBCF(); }
function updateColChart()     { if (isGraphTab()) drawCol(); }
function updateDescenteChart(){ if (isGraphTab()) drawCol(); }
function updatePoulsTaChart() { if (isGraphTab()) drawPoulsTa(); }
function isGraphTab() { return document.getElementById('tab-partograph').classList.contains('active'); }

/* ══════════════════════════════════════════════════════════════
   INTERACTION SOURIS : clic pour poser un point, clic-droit pour effacer
   ══════════════════════════════════════════════════════════════ */
function attachEvents(cfg, drawFn) {
    const canvas = document.getElementById(cfg.id);
    if (!canvas) return;
    const hk = cfg.name;

    canvas.addEventListener('mousemove', e => {
        const { x, y } = canvasPt(canvas, e);
        if (!inChart(cfg, canvas.width, canvas.height, x, y)) {
            if (hover[hk]) { hover[hk] = null; drawFn(); }
            return;
        }
        const hour  = Math.max(1, Math.min(12, xToHour(cfg, canvas.width, x)));
        const value = yToVal(cfg, canvas.height, y);
        /* Ne redessiner que si la cellule change → évite le scintillement */
        if (!hover[hk] || hover[hk].hour !== hour || hover[hk].value !== value) {
            hover[hk] = { hour, value };
            drawFn();
        }
    });

    canvas.addEventListener('mouseleave', () => {
        hover[hk] = null;
        drawFn();
    });

    /* Clic gauche : placer / mettre à jour la valeur */
    canvas.addEventListener('click', e => {
        const { x, y } = canvasPt(canvas, e);
        if (!inChart(cfg, canvas.width, canvas.height, x, y)) return;
        const hour  = Math.max(1, Math.min(12, xToHour(cfg, canvas.width, x)));
        const value = yToVal(cfg, canvas.height, y);
        setVal(cfg.name, hour, value);
        drawFn();
    });

    /* Clic droit : effacer le point de cette heure */
    canvas.addEventListener('contextmenu', e => {
        e.preventDefault();
        const { x } = canvasPt(canvas, e);
        if (x < cfg.pad.left || x > canvas.width - cfg.pad.right) return;
        const hour = Math.max(1, Math.min(12, xToHour(cfg, canvas.width, x)));
        setVal(cfg.name, hour, null);
        hover[hk] = null;
        drawFn();
    });

    /* Support tactile (mobile) */
    canvas.addEventListener('touchstart', e => {
        e.preventDefault();
        const touch = e.touches[0];
        const { x, y } = canvasPt(canvas, touch);
        if (!inChart(cfg, canvas.width, canvas.height, x, y)) return;
        const hour  = Math.max(1, Math.min(12, xToHour(cfg, canvas.width, x)));
        const value = yToVal(cfg, canvas.height, y);
        setVal(cfg.name, hour, value);
        hover[hk] = { hour, value };
        drawFn();
    }, { passive: false });
}

attachEvents(BCF_CFG, drawBCF);
attachEvents(COL_CFG, drawCol); /* drawCol inclut drawDescente */

/* Redessiner si la fenêtre est redimensionnée */
window.addEventListener('resize', () => {
    if (isGraphTab()) { drawBCF(); drawCol(); drawPoulsTa(); }
});
</script>

<?php require_once __DIR__ . '/../../layouts/footer.php'; ?>
