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
require_once __DIR__ . '/../../services/CsrfService.php';
$i       = $intervention ?? [];
$sspiList = $sspiList ?? [];
$dernier  = $sspiList[0] ?? null;
$termine  = $dernier && !empty($dernier['autorisation_sortie']);
?>
<style>
/* ── Reset layout ── */
body { background:#f0f4f8; font-family:'Inter','Segoe UI',sans-serif; color:#1e293b; }
.sidebar, nav.sidebar { display:none !important; }
main,.col-md-10,.ms-sm-auto {
    margin-left:0!important; width:100%!important; max-width:100%!important;
    flex:0 0 100%!important; padding:0!important;
}

/* ── Top bar ── */
.sspi-top {
    background:#fff; padding:12px 26px;
    display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:10px;
    position:sticky; top:0; z-index:50;
    border-bottom:1px solid #e2e8f0; box-shadow:0 2px 14px rgba(0,0,0,.06);
}
.sspi-top .brand { display:flex; align-items:center; gap:11px; }
.sspi-top .brand .ico {
    width:38px; height:38px; border-radius:11px; flex-shrink:0;
    background:linear-gradient(135deg,#f59e0b,#fbbf24);
    display:flex; align-items:center; justify-content:center; color:#fff; font-size:1.05rem;
}
.sspi-top .brand .title  { font-size:.98rem; font-weight:800; color:#1e293b; line-height:1.2; }
.sspi-top .brand .sub    { font-size:.69rem; color:#64748b; font-weight:500; }
.sspi-btn {
    padding:7px 14px; border-radius:9px; font-weight:600; font-size:.78rem;
    text-decoration:none; border:1.5px solid #e2e8f0; cursor:pointer;
    background:#fff; color:#475569; transition:all .15s;
    display:inline-flex; align-items:center; gap:6px;
}
.sspi-btn:hover { background:#f1f5f9; color:#1e293b; border-color:#cbd5e1; }
.sspi-btn.accent {
    background:linear-gradient(135deg,#f59e0b,#fbbf24);
    border-color:transparent; color:#fff; font-weight:700;
}
.sspi-btn.accent:hover { opacity:.88; color:#fff; }

/* ── Main wrapper ── */
.wrap { max-width:900px; margin:0 auto; padding:24px 24px 70px; }

/* ── Patient card ── */
.pat-card {
    background:#fff; border-radius:16px; padding:16px 20px; margin-bottom:20px;
    border-left:5px solid #f59e0b; box-shadow:0 2px 12px rgba(0,0,0,.07);
    display:flex; align-items:center; gap:14px;
}
.pat-card .avatar {
    width:46px; height:46px; border-radius:12px; flex-shrink:0;
    background:linear-gradient(135deg,#fef3c7,#fde68a);
    display:flex; align-items:center; justify-content:center;
    font-size:1.25rem; color:#d97706;
}
.pat-card .nm   { font-size:1.06rem; font-weight:800; color:#1e293b; }
.pat-card .meta { font-size:.77rem; color:#64748b; margin-top:3px; }
.pat-card .badge-rev {
    margin-left:auto; flex-shrink:0;
    background:#fffbeb; color:#b45309; border:1.5px solid #fde68a;
    border-radius:20px; padding:5px 13px; font-size:.72rem; font-weight:700; white-space:nowrap;
}

/* ── Section cards ── */
.s-card {
    background:#fff; border-radius:16px; border:1px solid #e2e8f0;
    box-shadow:0 2px 10px rgba(0,0,0,.05); margin-bottom:18px; overflow:hidden;
}
.s-card-head {
    padding:12px 18px; border-bottom:1px solid #f1f5f9;
    display:flex; align-items:center; justify-content:space-between;
    font-size:.8rem; font-weight:700; color:#374151;
    text-transform:uppercase; letter-spacing:.5px;
}
.s-card-head .chip {
    font-size:.69rem; font-weight:600; padding:3px 10px; border-radius:20px;
    background:#f1f5f9; color:#64748b; text-transform:none; letter-spacing:0;
}
.s-card-body { padding:18px; }

/* ── Monitoring form fields ── */
.vitaux-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(120px,1fr)); gap:12px; }
.vitaux-field label {
    display:block; font-size:.69rem; font-weight:700; color:#64748b;
    text-transform:uppercase; letter-spacing:.4px; margin-bottom:5px;
}
.vitaux-field label .req { color:#ef4444; margin-left:2px; }
.vitaux-field input {
    width:100%; padding:8px 10px; border-radius:9px;
    border:1.5px solid #e2e8f0; font-size:.88rem; font-weight:600;
    color:#1e293b; background:#fafafa; transition:border-color .15s, box-shadow .15s;
    outline:none; box-sizing:border-box;
}
.vitaux-field input:focus {
    border-color:#6366f1; background:#fff;
    box-shadow:0 0 0 3px rgba(99,102,241,.1);
}
.btn-mon {
    padding:9px 22px; background:#6366f1; color:#fff; border:none;
    border-radius:9px; font-weight:700; font-size:.82rem; cursor:pointer;
    display:inline-flex; align-items:center; gap:7px; transition:background .15s;
}
.btn-mon:hover { background:#4f46e5; }

/* ── Aldrete layout ── */
.ald-layout { display:grid; grid-template-columns:140px 1fr; gap:22px; align-items:start; }
@media(max-width:640px){ .ald-layout { grid-template-columns:1fr; } }

/* Score ring */
.ald-ring-wrap { position:sticky; top:76px; }
.ald-ring {
    width:130px; height:130px; border-radius:50%;
    background:conic-gradient(#22c55e 360deg, #e8edf3 360deg);
    display:flex; align-items:center; justify-content:center;
    box-shadow:0 4px 20px rgba(0,0,0,.1); transition:background .35s;
    position:relative;
}
.ald-ring::before {
    content:''; position:absolute; width:92px; height:92px;
    border-radius:50%; background:#fff;
}
.ald-ring-inner { position:relative; z-index:1; text-align:center; }
.ald-ring-inner .n { font-size:2rem; font-weight:900; line-height:1; transition:color .35s; }
.ald-ring-inner .d { font-size:.7rem; color:#94a3b8; font-weight:600; margin-top:1px; }
.ald-status {
    margin-top:9px; text-align:center; font-size:.71rem; font-weight:700;
    padding:4px 8px; border-radius:7px; transition:all .35s;
    background:#f0fdf4; color:#15803d;
}

/* Criteria */
.ald-item { margin-bottom:14px; }
.ald-item:last-child { margin-bottom:0; }
.ald-q { font-weight:700; font-size:.84rem; color:#374151; margin-bottom:7px; }
.ald-opts { display:flex; gap:6px; }
.ald-opts input[type=radio] { display:none; }
.ald-opts label {
    flex:1; padding:8px 6px; border:1.5px solid #e2e8f0; border-radius:10px;
    cursor:pointer; font-size:.74rem; text-align:center;
    transition:all .15s; background:#fafafa; color:#64748b; line-height:1.35;
}
.ald-opts label .val {
    display:block; font-size:1.05rem; font-weight:900; color:#334155; margin-bottom:3px;
}
.ald-opts input[type=radio]:checked + label {
    border-color:#6366f1; background:#eef2ff; color:#4338ca;
}
.ald-opts input[type=radio]:checked + label .val { color:#4338ca; }

/* Sortie row */
.sortie-row {
    display:flex; align-items:center; gap:12px;
    background:#f0fdf4; border:1.5px solid #86efac; border-radius:12px;
    padding:13px 16px; margin-top:18px; cursor:pointer; user-select:none;
}
.sortie-row input[type=checkbox] {
    width:18px; height:18px; accent-color:#16a34a; flex-shrink:0; cursor:pointer;
}
.sortie-row .sr-title { font-weight:700; font-size:.88rem; color:#15803d; }
.sortie-row .sr-sub   { font-size:.74rem; color:#166534; }

/* Orientation block */
.orient-block {
    background:#f8faff; border:1.5px solid #c7d2fe; border-radius:12px;
    padding:14px 16px; margin-top:12px;
}
.orient-head {
    font-weight:800; font-size:.76rem; text-transform:uppercase; letter-spacing:.4px;
    color:#3730a3; margin-bottom:11px; display:flex; align-items:center; gap:7px;
}
.orient-opts { display:flex; gap:10px; flex-wrap:wrap; margin-bottom:12px; }
.orient-opt {
    flex:1; min-width:155px; display:flex; align-items:center; gap:8px;
    background:#fff; border:1.5px solid #c7d2fe; border-radius:10px;
    padding:10px 12px; cursor:pointer; font-size:.82rem; font-weight:600; color:#374151;
    transition:all .15s;
}
.orient-opt:has(input:checked) { border-color:#6366f1; background:#eef2ff; color:#3730a3; }
.orient-opt input { display:none; }

/* Buttons */
.btn-save {
    width:100%; margin-top:18px; padding:13px;
    background:linear-gradient(135deg,#6366f1,#818cf8); color:#fff;
    border:none; border-radius:11px; font-weight:800; font-size:.92rem;
    cursor:pointer; transition:opacity .15s;
    display:flex; align-items:center; justify-content:center; gap:8px;
}
.btn-save:hover { opacity:.9; }

/* History tables */
.hist-wrap { overflow-x:auto; }
.hist { width:100%; border-collapse:collapse; font-size:.81rem; }
.hist th {
    font-size:.65rem; color:#94a3b8; text-transform:uppercase; letter-spacing:.5px;
    text-align:left; padding:9px 13px; border-bottom:2px solid #f1f5f9; background:#f8fafc;
    white-space:nowrap;
}
.hist td { padding:9px 13px; border-bottom:1px solid #f1f5f9; color:#374151; }
.hist tr:last-child td { border-bottom:none; }
.hist tr:hover td { background:#fafafa; }

/* Alert animation */
@keyframes pulseAlert {
    0%,100% { transform:scale(1); }
    50%      { transform:scale(1.015); box-shadow:0 0 0 6px rgba(220,38,38,.15); }
}
</style>

<!-- TOP BAR -->
<div class="sspi-top">
    <div class="brand">
        <div class="ico"><i class="bi bi-heart-pulse-fill"></i></div>
        <div>
            <div class="title">Salle de réveil — SSPI</div>
            <div class="sub">Surveillance post-interventionnelle</div>
        </div>
    </div>
    <div style="display:flex;gap:8px;flex-wrap:wrap;">
        <button onclick="toggleMonitoring()" id="btnMonitoring" class="sspi-btn accent">
            <i class="bi bi-activity"></i>Paramètres vitaux
        </button>
        <a href="<?= BASE_URL ?>bloc/sspi-dashboard" class="sspi-btn"><i class="bi bi-grid-fill"></i>Tous les patients</a>
        <a href="<?= BASE_URL ?>bloc" class="sspi-btn"><i class="bi bi-arrow-left"></i>Bloc</a>
    </div>
</div>

<div class="wrap">

    <!-- Patient banner -->
    <div class="pat-card">
        <div class="avatar"><i class="bi bi-person-fill"></i></div>
        <div>
            <div class="nm"><?= htmlspecialchars(strtoupper($i['patient_nom'] ?? '') . ' ' . ($i['patient_prenom'] ?? '')) ?></div>
            <div class="meta">
                <?= htmlspecialchars($i['dossier_numero'] ?? '') ?>
                <?php if (!empty($i['nom_salle'])): ?> · Salle : <?= htmlspecialchars($i['nom_salle']) ?><?php endif; ?>
                <?php if (!empty($i['type_intervention'])): ?> · <?= htmlspecialchars($i['type_intervention']) ?><?php endif; ?>
                <?php if (!empty($i['chirurgien'])): ?> · Dr <?= htmlspecialchars($i['chirurgien']) ?><?php endif; ?>
            </div>
        </div>
        <div class="badge-rev"><i class="bi bi-clock me-1"></i>En réveil</div>
    </div>

<?php
/* ── Données monitoring → JSON pour Chart.js ── */
$monList = $monitoringList ?? [];
$mLabels = array_map(fn($r) => date('H:i', strtotime($r['heure_relevé'])), $monList);
$mBpm    = array_map(fn($r) => $r['bpm'],    $monList);
$mSpo2   = array_map(fn($r) => $r['spo2'],   $monList);
$mTaSys  = array_map(fn($r) => $r['ta_sys'], $monList);
$mTaDia  = array_map(fn($r) => $r['ta_dia'], $monList);
$mFr     = array_map(fn($r) => $r['fr'],     $monList);
$mTemp   = array_map(fn($r) => $r['temp'],   $monList);
$progId  = (int)($i['id'] ?? 0);
?>

<!-- ═══ PANNEAU MONITORING (toggle) ═══ -->
<div id="panneauMonitoring" style="display:none;">

    <!-- Bandeau alertes -->
    <div id="bandeauAlertes" style="display:none;margin-bottom:14px;"></div>

    <!-- Formulaire nouveau relevé -->
    <div class="s-card" style="border-left:4px solid #6366f1;">
        <div class="s-card-head">
            <span><i class="bi bi-plus-circle me-2" style="color:#6366f1;"></i>Nouveau relevé de paramètres</span>
            <span class="chip">FC obligatoire</span>
        </div>
        <div class="s-card-body">
            <form id="monitoringForm" onsubmit="envoyerVitaux(event)">
                <div class="vitaux-grid">
                    <div class="vitaux-field">
                        <label>FC (bpm)<span class="req">*</span></label>
                        <input type="number" name="bpm" min="20" max="300" required placeholder="75">
                    </div>
                    <div class="vitaux-field">
                        <label>SpO₂ (%)</label>
                        <input type="number" name="spo2" min="50" max="100" placeholder="98">
                    </div>
                    <div class="vitaux-field">
                        <label>TA Sys (mmHg)</label>
                        <input type="number" name="ta_sys" min="50" max="300" placeholder="120">
                    </div>
                    <div class="vitaux-field">
                        <label>TA Dia (mmHg)</label>
                        <input type="number" name="ta_dia" min="30" max="200" placeholder="80">
                    </div>
                    <div class="vitaux-field">
                        <label>FR (c/min)</label>
                        <input type="number" name="fr" min="5" max="60" placeholder="16">
                    </div>
                    <div class="vitaux-field">
                        <label>Temp. (°C)</label>
                        <input type="number" name="temp" step="0.1" min="30" max="43" placeholder="36.8">
                    </div>
                </div>
                <div style="display:flex;align-items:center;gap:12px;margin-top:14px;">
                    <button type="submit" id="btnSauveVitaux" class="btn-mon">
                        <i class="bi bi-save"></i>Enregistrer le relevé
                    </button>
                    <span id="msgVitaux" style="font-size:.78rem;font-weight:600;display:none;"></span>
                </div>
            </form>
        </div>
    </div>

    <!-- Courbes Chart.js -->
    <div class="s-card">
        <div class="s-card-head">
            <span><i class="bi bi-graph-up me-2" style="color:#6366f1;"></i>Courbes de monitoring</span>
        </div>
        <div class="s-card-body">
            <p style="font-size:.7rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.5px;margin-bottom:6px;">
                Fréquences &amp; Saturation
            </p>
            <div style="position:relative;height:200px;margin-bottom:22px;"><canvas id="chartFreq"></canvas></div>

            <p style="font-size:.7rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.5px;margin-bottom:6px;">
                Pression Artérielle
            </p>
            <div style="position:relative;height:160px;margin-bottom:22px;"><canvas id="chartTA"></canvas></div>

            <p style="font-size:.7rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.5px;margin-bottom:6px;">
                Température
            </p>
            <div style="position:relative;height:130px;margin-bottom:8px;"><canvas id="chartTemp"></canvas></div>

            <p id="msgPasDeData" style="text-align:center;color:#94a3b8;font-size:.82rem;padding:10px 0;<?= count($monList) ? 'display:none' : '' ?>">
                <i class="bi bi-bar-chart me-1"></i>Aucun relevé — les courbes apparaîtront après le premier enregistrement.
            </p>
        </div>
    </div>

    <!-- Historique tabulaire -->
    <?php if (!empty($monList)): ?>
    <div class="s-card">
        <div class="s-card-head"><span><i class="bi bi-table me-2"></i>Historique des relevés</span></div>
        <div class="hist-wrap">
            <table class="hist" id="tblMonitoring">
                <thead>
                    <tr>
                        <th>Heure</th><th>FC (bpm)</th><th>SpO₂ (%)</th>
                        <th>TA sys</th><th>TA dia</th><th>FR</th><th>Temp</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach (array_reverse($monList) as $mv): ?>
                    <tr>
                        <td><?= date('H:i', strtotime($mv['heure_relevé'])) ?></td>
                        <td><?= $mv['bpm'] ?? '—' ?></td>
                        <td><?= $mv['spo2'] !== null ? $mv['spo2'] . '%' : '—' ?></td>
                        <td><?= $mv['ta_sys'] ?? '—' ?></td>
                        <td><?= $mv['ta_dia'] ?? '—' ?></td>
                        <td><?= $mv['fr'] ?? '—' ?></td>
                        <td><?= $mv['temp'] !== null ? $mv['temp'] . '°C' : '—' ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

</div><!-- /panneauMonitoring -->

<?php if ($termine): ?>
    <!-- Sortie déjà autorisée -->
    <div style="background:#f0fdf4;border:1.5px solid #86efac;border-radius:14px;padding:16px 20px;
                margin-bottom:18px;display:flex;align-items:center;gap:13px;">
        <i class="bi bi-check-circle-fill" style="color:#16a34a;font-size:1.5rem;flex-shrink:0;"></i>
        <div>
            <div style="font-weight:800;color:#15803d;font-size:.95rem;">Sortie autorisée</div>
            <div style="font-size:.82rem;color:#166534;">
                Dernier score d'Aldrete : <strong><?= (int)$dernier['total_aldrete'] ?>/10</strong> — Intervention terminée.
            </div>
        </div>
    </div>
<?php else: ?>

    <!-- Score d'Aldrete -->
    <div class="s-card">
        <div class="s-card-head">
            <span><i class="bi bi-clipboard2-pulse me-2" style="color:#6366f1;"></i>Score d'Aldrete</span>
            <span class="chip">Seuil de sortie ≥ 9 / 10</span>
        </div>
        <div class="s-card-body">
            <form action="<?= BASE_URL ?>bloc/sspi-sauvegarder" method="POST" id="aldreteForm">
                <?= CsrfService::field() ?>
                <input type="hidden" name="programmation_id" value="<?= (int)($i['id'] ?? 0) ?>">

                <div class="ald-layout">

                    <!-- Anneau de score -->
                    <div class="ald-ring-wrap">
                        <div class="ald-ring" id="aldRing">
                            <div class="ald-ring-inner">
                                <div class="n" id="aldTotal">10</div>
                                <div class="d">/ 10</div>
                            </div>
                        </div>
                        <div class="ald-status" id="aldStatus">✓ Sortie possible</div>
                    </div>

                    <!-- Critères -->
                    <div>
                        <?php
                        $criteres = [
                            'score_motricite'   => ['Activité motrice', [
                                0 => 'Aucun mouvement',
                                1 => '2 membres mobiles',
                                2 => '4 membres mobiles',
                            ]],
                            'score_respiration' => ['Respiration', [
                                0 => 'Apnée',
                                1 => 'Dyspnée / limitée',
                                2 => 'Respire & tousse librement',
                            ]],
                            'score_circulation' => ['Circulation (TA)', [
                                0 => '±50% pré-op',
                                1 => '±20-50% pré-op',
                                2 => '±20% pré-op',
                            ]],
                            'score_conscience'  => ['Conscience', [
                                0 => 'Aucune réponse',
                                1 => 'Réveil à l\'appel',
                                2 => 'Pleinement réveillé',
                            ]],
                            'score_saturation'  => ['Saturation O₂', [
                                0 => 'SpO₂ < 90% sous O₂',
                                1 => 'Besoin O₂ pour >90%',
                                2 => 'SpO₂ >92% air ambiant',
                            ]],
                        ];
                        foreach ($criteres as $name => [$titre, $opts]):
                        ?>
                        <div class="ald-item">
                            <div class="ald-q"><?= htmlspecialchars($titre) ?></div>
                            <div class="ald-opts">
                                <?php foreach ($opts as $val => $txt): ?>
                                <input type="radio" name="<?= $name ?>" id="<?= $name . $val ?>"
                                       value="<?= $val ?>" <?= $val === 2 ? 'checked' : '' ?> onchange="majTotal()">
                                <label for="<?= $name . $val ?>">
                                    <span class="val"><?= $val ?></span>
                                    <?= htmlspecialchars($txt) ?>
                                </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>

                </div><!-- /ald-layout -->

                <!-- Autorisation de sortie -->
                <label class="sortie-row">
                    <input type="checkbox" name="autorisation_sortie" id="autoSortie" value="1" onchange="toggleOrientation()">
                    <div>
                        <div class="sr-title"><i class="bi bi-box-arrow-right me-1"></i>Autoriser la sortie de SSPI</div>
                        <div class="sr-sub">Recommandé si score ≥ 9</div>
                    </div>
                </label>

                <!-- Orientation patient -->
                <div id="blocOrientation" class="orient-block" style="display:none;">
                    <div class="orient-head"><i class="bi bi-signpost-split-fill"></i>Orientation du patient</div>
                    <div class="orient-opts">
                        <label class="orient-opt">
                            <input type="radio" name="orientation_sortie" value="TRANSFERT" checked onchange="toggleServiceDest()">
                            <i class="bi bi-hospital" style="color:#6366f1;font-size:1.1rem;"></i>
                            <span>Transfert vers un service</span>
                        </label>
                        <label class="orient-opt">
                            <input type="radio" name="orientation_sortie" value="DOMICILE" onchange="toggleServiceDest()">
                            <i class="bi bi-house-heart" style="color:#16a34a;font-size:1.1rem;"></i>
                            <span>Retour à domicile</span>
                        </label>
                    </div>
                    <div id="zoneServiceDest">
                        <label style="font-size:.73rem;font-weight:700;color:#3730a3;text-transform:uppercase;display:block;margin-bottom:5px;">
                            Service de destination <span style="color:#dc2626;">*</span>
                        </label>
                        <select name="service_dest_id" id="serviceDestSspi" class="form-select"
                                style="border-radius:10px;border:1.5px solid #c7d2fe;background:#fff;">
                            <option value="">— Choisir le service —</option>
                            <?php foreach (($servicesCliniques ?? []) as $sv): ?>
                            <option value="<?= (int)$sv['id'] ?>"><?= htmlspecialchars($sv['nom_service']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-muted d-block mt-1" style="font-size:.72rem;">
                            <i class="bi bi-info-circle me-1"></i>Le patient apparaîtra dans « À Hospitaliser » du service choisi.
                        </small>
                    </div>
                </div>

                <button type="submit" class="btn-save">
                    <i class="bi bi-save"></i>Enregistrer le relevé Aldrete
                </button>
            </form>
        </div>
    </div>

<?php endif; ?>

    <!-- Historique Aldrete -->
    <?php if (!empty($sspiList)): ?>
    <div class="s-card">
        <div class="s-card-head"><span><i class="bi bi-clock-history me-2"></i>Relevés SSPI</span></div>
        <div class="hist-wrap">
            <table class="hist">
                <thead>
                    <tr>
                        <th>Heure</th><th>Motri.</th><th>Resp.</th><th>Circ.</th>
                        <th>Consc.</th><th>SpO₂</th><th>Total</th><th>Sortie</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($sspiList as $s): ?>
                    <tr>
                        <td><?= date('H:i', strtotime($s['date_saisie'] ?? 'now')) ?></td>
                        <td><?= $s['score_motricite'] ?></td>
                        <td><?= $s['score_respiration'] ?></td>
                        <td><?= $s['score_circulation'] ?></td>
                        <td><?= $s['score_conscience'] ?></td>
                        <td><?= $s['score_saturation'] ?></td>
                        <td>
                            <strong style="color:<?= $s['total_aldrete'] >= 9 ? '#16a34a' : '#d97706' ?>">
                                <?= $s['total_aldrete'] ?>/10
                            </strong>
                        </td>
                        <td>
                            <?= !empty($s['autorisation_sortie'])
                                ? '<span style="color:#16a34a;font-weight:700;"><i class="bi bi-check-circle-fill me-1"></i>Oui</span>'
                                : '<span style="color:#94a3b8;">Non</span>' ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

</div><!-- /wrap -->

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
/* ── Seuils SSPI ── */
const SEUILS = {
    bpm:    { label:'FC',              unite:'bpm',   bas_crit:45,  bas_warn:55,  haut_warn:100, haut_crit:120 },
    spo2:   { label:'SpO₂',           unite:'%',     bas_crit:90,  bas_warn:94,  haut_warn:null,haut_crit:null },
    ta_sys: { label:'TA Systolique',   unite:'mmHg',  bas_crit:80,  bas_warn:90,  haut_warn:140, haut_crit:180 },
    ta_dia: { label:'TA Diastolique',  unite:'mmHg',  bas_crit:50,  bas_warn:60,  haut_warn:90,  haut_crit:110 },
    fr:     { label:'Fréquence Resp.', unite:'c/min', bas_crit:8,   bas_warn:10,  haut_warn:20,  haut_crit:30  },
    temp:   { label:'Température',     unite:'°C',    bas_crit:35.0,bas_warn:36.0,haut_warn:37.5,haut_crit:38.5},
};

function evaluerValeur(champ, val) {
    const s = SEUILS[champ];
    if (!s || val === null || val === '') return null;
    const v = parseFloat(val);
    if (!isNaN(s.bas_crit)  && v <= s.bas_crit)                    return { niveau:'CRITIQUE', msg:`${s.label} critique : ${v} ${s.unite} (< ${s.bas_crit})` };
    if (!isNaN(s.haut_crit) && s.haut_crit && v >= s.haut_crit)    return { niveau:'CRITIQUE', msg:`${s.label} critique : ${v} ${s.unite} (> ${s.haut_crit})` };
    if (!isNaN(s.bas_warn)  && v < s.bas_warn)                     return { niveau:'WARNING',  msg:`${s.label} basse : ${v} ${s.unite}` };
    if (s.haut_warn && v > s.haut_warn)                            return { niveau:'WARNING',  msg:`${s.label} élevée : ${v} ${s.unite}` };
    return { niveau:'OK' };
}

/* ── Données PHP → JS ── */
const PROG_ID = <?= $progId ?>;
let mLabels = <?= json_encode(array_values($mLabels)) ?>;
let mBpm    = <?= json_encode(array_values($mBpm)) ?>;
let mSpo2   = <?= json_encode(array_values($mSpo2)) ?>;
let mTaSys  = <?= json_encode(array_values($mTaSys)) ?>;
let mTaDia  = <?= json_encode(array_values($mTaDia)) ?>;
let mFr     = <?= json_encode(array_values($mFr)) ?>;
let mTemp   = <?= json_encode(array_values($mTemp)) ?>;

/* ── Toggle panneau monitoring ── */
function toggleMonitoring() {
    const p = document.getElementById('panneauMonitoring');
    const visible = p.style.display !== 'none';
    p.style.display = visible ? 'none' : 'block';
    if (!visible) {
        setTimeout(() => initCharts(), 50);
        if (!window._vitauxListeners) {
            document.querySelectorAll('#monitoringForm input[type=number]').forEach(inp =>
                inp.addEventListener('input', () => styliserChamp(inp))
            );
            window._vitauxListeners = true;
        }
    }
}

/* ── Chart.js ── */
let chartFreq, chartTA, chartTemp;
const commonOpts = {
    responsive:true, maintainAspectRatio:false, animation:{duration:300},
    plugins:{ legend:{ labels:{ font:{size:10}, boxWidth:14 } } },
    scales:{ x:{ ticks:{font:{size:9}}, grid:{color:'#f1f5f9'} } }
};

function initCharts() {
    if (chartFreq) { chartFreq.destroy(); chartTA.destroy(); chartTemp.destroy(); }

    chartFreq = new Chart(document.getElementById('chartFreq').getContext('2d'), {
        type:'line',
        data:{ labels:mLabels, datasets:[
            { label:'FC (bpm)',   data:mBpm,  borderColor:'#ef4444', backgroundColor:'rgba(239,68,68,.07)',  tension:.35, pointRadius:4, borderWidth:2, fill:false },
            { label:'SpO₂ (%)',  data:mSpo2, borderColor:'#3b82f6', backgroundColor:'rgba(59,130,246,.07)', tension:.35, pointRadius:4, borderWidth:2, fill:false, yAxisID:'ySpo2' },
            { label:'FR (c/min)',data:mFr,   borderColor:'#10b981', backgroundColor:'rgba(16,185,129,.07)', tension:.35, pointRadius:4, borderWidth:2, fill:false }
        ]},
        options:{ ...commonOpts, scales:{
            x:{ ticks:{font:{size:9}}, grid:{color:'#f1f5f9'} },
            y:{ ticks:{font:{size:9}}, title:{display:true,text:'bpm / c/min',font:{size:9}} },
            ySpo2:{ position:'right', min:80, max:100, ticks:{font:{size:9}},
                    title:{display:true,text:'SpO₂ %',font:{size:9}}, grid:{drawOnChartArea:false} }
        }}
    });

    chartTA = new Chart(document.getElementById('chartTA').getContext('2d'), {
        type:'line',
        data:{ labels:mLabels, datasets:[
            { label:'TA Systolique', data:mTaSys, borderColor:'#f59e0b', backgroundColor:'rgba(245,158,11,.07)', tension:.35, pointRadius:4, borderWidth:2, fill:false },
            { label:'TA Diastolique',data:mTaDia, borderColor:'#fb923c', backgroundColor:'rgba(251,146,60,.07)', tension:.35, pointRadius:4, borderWidth:2, fill:false, borderDash:[5,3] }
        ]},
        options:{ ...commonOpts, scales:{
            x:{ ticks:{font:{size:9}}, grid:{color:'#f1f5f9'} },
            y:{ min:40, ticks:{font:{size:9}}, title:{display:true,text:'mmHg',font:{size:9}} }
        }}
    });

    chartTemp = new Chart(document.getElementById('chartTemp').getContext('2d'), {
        type:'line',
        data:{ labels:mLabels, datasets:[
            { label:'Température (°C)', data:mTemp, borderColor:'#8b5cf6', backgroundColor:'rgba(139,92,246,.07)', tension:.35, pointRadius:4, borderWidth:2, fill:true }
        ]},
        options:{ ...commonOpts, scales:{
            x:{ ticks:{font:{size:9}}, grid:{color:'#f1f5f9'} },
            y:{ min:34, max:42, ticks:{font:{size:9}}, title:{display:true,text:'°C',font:{size:9}} }
        }}
    });

    document.getElementById('msgPasDeData').style.display =
        (mTemp.length === 0 && mBpm.length === 0) ? '' : 'none';
}

/* ── Validation temps-réel ── */
function styliserChamp(input) {
    const res  = evaluerValeur(input.name, input.value);
    const wrap = input.closest('div');
    let hint   = wrap.querySelector('.vitaux-hint');
    if (!hint) { hint = document.createElement('small'); hint.className = 'vitaux-hint'; wrap.appendChild(hint); }
    input.style.borderColor = '';
    hint.textContent = ''; hint.style.display = 'none';
    if (!res || res.niveau === 'OK' || input.value === '') return;
    if (res.niveau === 'CRITIQUE') {
        input.style.borderColor = '#dc2626';
        hint.style.cssText = 'display:block;color:#dc2626;font-size:.7rem;font-weight:700;margin-top:3px;';
        hint.innerHTML = `<i class="bi bi-exclamation-triangle-fill me-1"></i>${res.msg}`;
    } else {
        input.style.borderColor = '#f59e0b';
        hint.style.cssText = 'display:block;color:#b45309;font-size:.7rem;font-weight:600;margin-top:3px;';
        hint.innerHTML = `<i class="bi bi-exclamation-circle me-1"></i>${res.msg}`;
    }
}

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('#monitoringForm input[type=number]').forEach(inp =>
        inp.addEventListener('input', () => styliserChamp(inp))
    );
});

/* ── Bandeau alertes ── */
function afficherAlertesVitaux(fd) {
    const champs  = ['bpm','spo2','ta_sys','ta_dia','fr','temp'];
    const alertes = champs.map(c => evaluerValeur(c, fd.get(c))).filter(r => r && r.niveau !== 'OK');
    const zone    = document.getElementById('bandeauAlertes');
    if (!alertes.length) { zone.style.display = 'none'; return; }
    const crit = alertes.some(a => a.niveau === 'CRITIQUE');
    zone.style.cssText = `display:block;border-radius:12px;padding:12px 16px;margin-bottom:14px;
        background:${crit ? 'linear-gradient(135deg,#fee2e2,#fecaca)' : 'linear-gradient(135deg,#fef3c7,#fde68a)'};
        border:2px solid ${crit ? '#dc2626' : '#f59e0b'};animation:pulseAlert .6s ease 2;`;
    zone.innerHTML = `
        <div style="display:flex;align-items:center;gap:8px;margin-bottom:6px;">
            <i class="bi bi-${crit ? 'exclamation-octagon-fill' : 'exclamation-triangle-fill'}"
               style="color:${crit ? '#dc2626' : '#b45309'};font-size:1.1rem;"></i>
            <strong style="color:${crit ? '#dc2626' : '#92400e'};">
                ${crit ? '⚠️ Valeur(s) CRITIQUE(s) détectée(s)' : 'Valeur(s) anormale(s)'}
            </strong>
        </div>
        <ul style="margin:0;padding-left:18px;font-size:.82rem;color:${crit ? '#991b1b' : '#92400e'};">
            ${alertes.map(a => `<li>${a.msg}</li>`).join('')}
        </ul>`;
}

/* ── AJAX enregistrement relevé monitoring ── */
async function envoyerVitaux(e) {
    e.preventDefault();
    const btn  = document.getElementById('btnSauveVitaux');
    const msg  = document.getElementById('msgVitaux');
    const form = document.getElementById('monitoringForm');
    btn.disabled = true; btn.textContent = '…';
    try {
        const fd   = new FormData(form);
        const resp = await fetch(BASE_URL + 'bloc/monitoring/' + PROG_ID, { method:'POST', body:fd });
        const json = await resp.json();
        if (json.success) {
            afficherAlertesVitaux(fd);
            const now = new Date();
            const hm  = now.getHours().toString().padStart(2,'0') + ':' + now.getMinutes().toString().padStart(2,'0');
            const get = k => fd.get(k) !== '' ? parseFloat(fd.get(k)) : null;
            mLabels.push(hm);
            mBpm.push(get('bpm'));   mSpo2.push(get('spo2'));
            mTaSys.push(get('ta_sys')); mTaDia.push(get('ta_dia'));
            mFr.push(get('fr'));     mTemp.push(get('temp'));
            [chartFreq, chartTA, chartTemp].forEach(c => { if (c) { c.data.labels = [...mLabels]; c.update(); } });
            chartFreq.data.datasets[0].data = [...mBpm];
            chartFreq.data.datasets[1].data = [...mSpo2];
            chartFreq.data.datasets[2].data = [...mFr];
            chartTA.data.datasets[0].data   = [...mTaSys];
            chartTA.data.datasets[1].data   = [...mTaDia];
            chartTemp.data.datasets[0].data = [...mTemp];
            [chartFreq, chartTA, chartTemp].forEach(c => { if (c) c.update(); });
            ajouterLigneHistorique(hm, fd);
            document.getElementById('msgPasDeData').style.display = 'none';
            msg.style.display = ''; msg.style.color = '#16a34a';
            msg.innerHTML = '<i class="bi bi-check-circle me-1"></i>Relevé enregistré';
            form.reset();
            setTimeout(() => { msg.style.display = 'none'; }, 3000);
        } else {
            msg.style.display = ''; msg.style.color = '#dc2626';
            msg.textContent = json.message || 'Erreur';
        }
    } catch(err) {
        msg.style.display = ''; msg.style.color = '#dc2626'; msg.textContent = 'Erreur réseau';
    }
    btn.disabled = false; btn.innerHTML = '<i class="bi bi-save me-1"></i>Enregistrer le relevé';
}

function ajouterLigneHistorique(hm, fd) {
    let tbody = document.querySelector('#tblMonitoring tbody');
    if (!tbody) {
        const wrap = document.createElement('div');
        wrap.innerHTML = `<div class="s-card"><div class="s-card-head"><span><i class="bi bi-table me-2"></i>Historique des relevés</span></div>
            <div class="hist-wrap"><table class="hist" id="tblMonitoring">
            <thead><tr><th>Heure</th><th>FC (bpm)</th><th>SpO₂ (%)</th><th>TA sys</th><th>TA dia</th><th>FR</th><th>Temp</th></tr></thead>
            <tbody></tbody></table></div></div>`;
        document.getElementById('panneauMonitoring').appendChild(wrap.firstChild);
        tbody = document.querySelector('#tblMonitoring tbody');
    }
    const g  = k => fd.get(k) || '—';
    const tr = document.createElement('tr');
    tr.innerHTML = `<td>${hm}</td><td>${g('bpm')}</td><td>${g('spo2')}%</td>
                    <td>${g('ta_sys')}</td><td>${g('ta_dia')}</td>
                    <td>${g('fr')}</td><td>${g('temp')}°C</td>`;
    tbody.insertBefore(tr, tbody.firstChild);
}

/* ── Score Aldrete & anneau ── */
function majTotal() {
    let t = 0;
    ['score_motricite','score_respiration','score_circulation','score_conscience','score_saturation'].forEach(n => {
        const sel = document.querySelector(`input[name="${n}"]:checked`);
        if (sel) t += parseInt(sel.value);
    });

    document.getElementById('aldTotal').textContent = t;

    const deg   = Math.round((t / 10) * 360);
    let   color, bg, fg, txt;
    if (t >= 9)      { color = '#22c55e'; bg = '#f0fdf4'; fg = '#15803d'; txt = '✓ Sortie possible'; }
    else if (t >= 6) { color = '#f59e0b'; bg = '#fffbeb'; fg = '#b45309'; txt = '⚠ En observation'; }
    else             { color = '#ef4444'; bg = '#fef2f2'; fg = '#dc2626'; txt = '✗ Maintien en SSPI'; }

    const ring  = document.getElementById('aldRing');
    const score = document.getElementById('aldTotal');
    const label = document.getElementById('aldStatus');
    ring.style.background  = `conic-gradient(${color} ${deg}deg, #e8edf3 ${deg}deg)`;
    score.style.color      = color;
    label.style.background = bg;
    label.style.color      = fg;
    label.textContent      = txt;
}
majTotal();

/* ── Orientation sortie ── */
function toggleOrientation() {
    const on = document.getElementById('autoSortie').checked;
    document.getElementById('blocOrientation').style.display = on ? 'block' : 'none';
}
function toggleServiceDest() {
    const transfert = document.querySelector('input[name="orientation_sortie"]:checked')?.value === 'TRANSFERT';
    document.getElementById('zoneServiceDest').style.display = transfert ? 'block' : 'none';
}

/* Validation avant soumission */
document.getElementById('aldreteForm')?.addEventListener('submit', function(e) {
    const sortie    = document.getElementById('autoSortie').checked;
    const transfert = document.querySelector('input[name="orientation_sortie"]:checked')?.value === 'TRANSFERT';
    const svc       = document.getElementById('serviceDestSspi');
    if (sortie && transfert && !svc.value) {
        e.preventDefault();
        svc.classList.add('is-invalid');
        svc.focus();
        alert('Veuillez choisir le service de destination du patient.');
    }
});
</script>
