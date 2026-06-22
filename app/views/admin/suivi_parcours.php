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
$adminPage = 'suivi_parcours';
require_once __DIR__ . '/partials/admin_nav.php';

// ── Libellés & config des étapes du parcours ────────────────────────────────
$etapes = [
    'ACCUEIL'               => ['label'=>'Accueil',            'color'=>'#6366f1','bg'=>'#eef2ff','icon'=>'bi-door-open-fill'],
    'PARAMETRES'            => ['label'=>'Paramètres',         'color'=>'#0891b2','bg'=>'#e0f2fe','icon'=>'bi-thermometer-half'],
    'PARAMETRES_MATERNITE'  => ['label'=>'Param. Maternité',   'color'=>'#ec4899','bg'=>'#fce7f3','icon'=>'bi-heart-pulse-fill'],
    'ATTENTE_CONSULTATION'  => ['label'=>'Attente consult.',   'color'=>'#f59e0b','bg'=>'#fffbeb','icon'=>'bi-hourglass-split'],
    'EN_CONSULTATION'       => ['label'=>'En consultation',    'color'=>'#10b981','bg'=>'#d1fae5','icon'=>'bi-person-video2'],
    'URGENCES'              => ['label'=>'Urgences',           'color'=>'#ef4444','bg'=>'#fee2e2','icon'=>'bi-lightning-charge-fill'],
    'HOSPITALISE'           => ['label'=>'Hospitalisé',        'color'=>'#7c3aed','bg'=>'#ede9fe','icon'=>'bi-hospital-fill'],
    'ABSENT_24H'            => ['label'=>'Absent 24h',         'color'=>'#94a3b8','bg'=>'#f1f5f9','icon'=>'bi-clock-history'],
    'SORTI'                 => ['label'=>'Sorti',              'color'=>'#64748b','bg'=>'#f8fafc','icon'=>'bi-box-arrow-right'],
    'SORTIE_RECENTE'        => ['label'=>'Sortie récente',     'color'=>'#84cc16','bg'=>'#f7fee7','icon'=>'bi-check-circle-fill'],
];

// Pipeline ordonné pour l'affichage
$pipeline = ['ACCUEIL','PARAMETRES','PARAMETRES_MATERNITE','ATTENTE_CONSULTATION',
             'EN_CONSULTATION','URGENCES','HOSPITALISE','ABSENT_24H','SORTI'];

function minutesEnTexte(int $min): string {
    if ($min < 1)  return 'À l\'instant';
    if ($min < 60) return $min . ' min';
    $h = intdiv($min, 60); $m = $min % 60;
    return $h . 'h' . ($m > 0 ? str_pad($m,2,'0',STR_PAD_LEFT) : '');
}
?>
<style>
:root {
    --sp-radius: 14px;
    --sp-shadow: 0 2px 16px rgba(0,0,0,.07);
}
body { background:#f0f4f8; }

/* ── Pipeline chips ── */
.pipe-bar {
    display:flex; gap:6px; flex-wrap:wrap; margin-bottom:24px;
}
.pipe-chip {
    display:flex; align-items:center; gap:6px;
    padding:7px 14px; border-radius:20px; cursor:pointer;
    font-size:.78rem; font-weight:700; border:2px solid transparent;
    transition:.15s; user-select:none;
}
.pipe-chip.active { border-color:currentColor; box-shadow:0 0 0 3px rgba(0,0,0,.08); }
.pipe-chip .chip-count {
    background:rgba(0,0,0,.12); border-radius:20px;
    padding:1px 7px; font-size:.72rem;
}

/* ── KPI cards ── */
.kpi-row { display:grid; grid-template-columns:repeat(auto-fill,minmax(160px,1fr)); gap:14px; margin-bottom:24px; }
.kpi-card {
    background:#fff; border-radius:var(--sp-radius); padding:16px;
    box-shadow:var(--sp-shadow); display:flex; flex-direction:column; gap:4px;
}
.kpi-num { font-size:1.9rem; font-weight:900; line-height:1; }
.kpi-lbl { font-size:.72rem; font-weight:700; text-transform:uppercase; letter-spacing:.6px; color:#64748b; }

/* ── Table patients ── */
.sp-table-wrap {
    background:#fff; border-radius:var(--sp-radius); box-shadow:var(--sp-shadow);
    overflow:hidden;
}
.sp-table-head {
    padding:16px 20px; border-bottom:1px solid #f1f5f9;
    display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:10px;
}
.sp-table-title { font-weight:900; font-size:1rem; color:#1e293b; display:flex; align-items:center; gap:8px; }
.sp-search {
    padding:7px 14px; border:1.5px solid #e2e8f0; border-radius:20px;
    font-size:.82rem; outline:none; width:220px;
}
.sp-search:focus { border-color:#6366f1; }

table.sp-tbl { width:100%; border-collapse:collapse; font-size:.82rem; }
table.sp-tbl th {
    background:#f8fafc; padding:10px 14px; text-align:left;
    font-size:.72rem; font-weight:800; text-transform:uppercase; letter-spacing:.5px;
    color:#64748b; border-bottom:1.5px solid #e2e8f0; white-space:nowrap;
}
table.sp-tbl td { padding:10px 14px; border-bottom:1px solid #f1f5f9; vertical-align:middle; }
table.sp-tbl tbody tr:hover { background:#f8fafc; }
table.sp-tbl tbody tr:last-child td { border-bottom:none; }

/* Badges statut */
.sp-badge {
    display:inline-flex; align-items:center; gap:5px;
    padding:3px 10px; border-radius:20px; font-size:.72rem; font-weight:700;
    white-space:nowrap;
}

/* Durée */
.sp-dur { font-size:.75rem; color:#94a3b8; white-space:nowrap; }
.sp-dur.warn { color:#f59e0b; font-weight:700; }
.sp-dur.danger { color:#ef4444; font-weight:700; }

/* Bouton réorienter */
.btn-reor {
    background:#6366f1; color:#fff; border:none; border-radius:8px;
    padding:5px 12px; font-size:.75rem; font-weight:700; cursor:pointer;
    white-space:nowrap; transition:.15s;
}
.btn-reor:hover { background:#4f46e5; }

/* ── Modal réorientation ── */
.reor-modal-bg {
    display:none; position:fixed; inset:0; background:rgba(0,0,0,.45);
    z-index:1050; align-items:center; justify-content:center;
}
.reor-modal-bg.show { display:flex; }
.reor-modal {
    background:#fff; border-radius:18px; width:500px; max-width:95vw;
    box-shadow:0 24px 80px rgba(0,0,0,.2); overflow:hidden;
}
.reor-modal-head {
    background:linear-gradient(135deg,#6366f1,#8b5cf6);
    color:#fff; padding:18px 22px;
    display:flex; align-items:center; justify-content:space-between;
}
.reor-modal-head h5 { margin:0; font-size:.95rem; font-weight:800; }
.reor-modal-body { padding:22px; display:flex; flex-direction:column; gap:14px; }
.reor-label { font-size:.78rem; font-weight:700; color:#475569; margin-bottom:4px; }
.reor-select, .reor-textarea {
    width:100%; padding:9px 12px; border:1.5px solid #e2e8f0; border-radius:10px;
    font-size:.85rem; outline:none;
}
.reor-select:focus, .reor-textarea:focus { border-color:#6366f1; }
.reor-modal-foot {
    padding:14px 22px; border-top:1px solid #f1f5f9;
    display:flex; justify-content:flex-end; gap:8px;
}
.btn-cancel { background:#f1f5f9; border:none; border-radius:8px; padding:8px 18px; font-size:.82rem; font-weight:600; cursor:pointer; }
.btn-save   { background:#6366f1; color:#fff; border:none; border-radius:8px; padding:8px 20px; font-size:.82rem; font-weight:700; cursor:pointer; }

/* Refresh badge */
#refreshBadge {
    position:fixed; top:70px; right:20px; background:#10b981; color:#fff;
    padding:5px 14px; border-radius:20px; font-size:.75rem; font-weight:700;
    display:none; z-index:900;
}

/* Filtre service */
.svc-filter {
    padding:7px 12px; border:1.5px solid #e2e8f0; border-radius:20px;
    font-size:.82rem; outline:none;
}
.svc-filter:focus { border-color:#6366f1; }

/* ── Responsive ── */
@media (max-width:768px) {
    .kpi-row { grid-template-columns:repeat(2,1fr); }
    .pipe-bar { gap:4px; }
    .pipe-chip { padding:5px 10px; font-size:.72rem; }
    table.sp-tbl th, table.sp-tbl td { padding:8px 10px; font-size:.76rem; }
    /* Masquer colonnes secondaires sur mobile */
    table.sp-tbl th:nth-child(9),
    table.sp-tbl td:nth-child(9) { display:none; } /* Dernière activité */
    table.sp-tbl th:nth-child(1),
    table.sp-tbl td:nth-child(1) { display:none; } /* # */
    .sp-search { width:150px; }
    .sp-table-head { flex-direction:column; align-items:flex-start; gap:8px; }
    .btn-reor span { display:none; }
    .btn-reor { padding:5px 8px; }
}
@media (max-width:480px) {
    .kpi-row { grid-template-columns:repeat(2,1fr); gap:8px; }
    table.sp-tbl th:nth-child(6),
    table.sp-tbl td:nth-child(6) { display:none; } /* Médecin */
    table.sp-tbl th:nth-child(7),
    table.sp-tbl td:nth-child(7) { display:none; } /* Arrivée */
}
</style>

<div class="container-fluid" style="max-width:1400px;padding:24px 16px;">

    <!-- ── Titre + refresh ── -->
    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;margin-bottom:22px;">
        <div>
            <h2 style="font-weight:900;font-size:1.4rem;margin:0;color:#1e293b;">
                <i class="bi bi-map-fill me-2" style="color:#6366f1;"></i>
                Suivi du Parcours Patient
            </h2>
            <div style="font-size:.78rem;color:#64748b;margin-top:3px;">
                Temps réel · Mis à jour le <?= date('d/m/Y à H:i:s') ?>
                <span id="countdownBadge" style="margin-left:8px;background:#f1f5f9;padding:2px 10px;
                      border-radius:20px;font-weight:700;color:#6366f1;"></span>
            </div>
        </div>
        <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
            <select class="svc-filter" id="filterCircuit" onchange="applyFilters()">
                <option value="">Tous les circuits</option>
                <option value="STANDARD">Standard (B1/B2)</option>
                <option value="PHP">PHP</option>
                <option value="MATERNITE">Maternité</option>
            </select>
            <select class="svc-filter" id="filterService" onchange="applyFilters()">
                <option value="">Tous les services</option>
                <?php foreach($services as $svc): ?>
                <option value="<?= $svc['id'] ?>"><?= htmlspecialchars($svc['nom_service']) ?></option>
                <?php endforeach; ?>
            </select>
            <button onclick="location.reload()" style="background:#6366f1;color:#fff;border:none;
                    border-radius:20px;padding:7px 18px;font-size:.8rem;font-weight:700;cursor:pointer;">
                <i class="bi bi-arrow-clockwise me-1"></i> Actualiser
            </button>
        </div>
    </div>

    <!-- ── KPI ── -->
    <div class="kpi-row">
        <?php
        $actifs = array_filter($patients, fn($p) => in_array($p['statut_parcours'],
            ['ACCUEIL','PARAMETRES','PARAMETRES_MATERNITE','ATTENTE_CONSULTATION','EN_CONSULTATION','URGENCES']));
        $hosp   = array_filter($patients, fn($p) => $p['statut_parcours'] === 'HOSPITALISE');
        $sortis = array_filter($patients, fn($p) => in_array($p['statut_parcours'],['SORTI','SORTIE_RECENTE']));
        $urg    = array_filter($patients, fn($p) => $p['statut_parcours'] === 'URGENCES');
        $kpis   = [
            ['num'=>count($actifs), 'lbl'=>'En parcours', 'color'=>'#6366f1'],
            ['num'=>$compteurs['ATTENTE_CONSULTATION']??0, 'lbl'=>'En attente', 'color'=>'#f59e0b'],
            ['num'=>$compteurs['EN_CONSULTATION']??0,      'lbl'=>'En consult.','color'=>'#10b981'],
            ['num'=>count($urg),   'lbl'=>'Urgences',  'color'=>'#ef4444'],
            ['num'=>count($hosp),  'lbl'=>'Hospitalisés','color'=>'#7c3aed'],
            ['num'=>count($sortis),'lbl'=>'Sortis auj.','color'=>'#64748b'],
            ['num'=>count($patients),'lbl'=>'Total jour','color'=>'#0891b2'],
        ];
        foreach($kpis as $k):
        ?>
        <div class="kpi-card">
            <div class="kpi-num" style="color:<?= $k['color'] ?>"><?= $k['num'] ?></div>
            <div class="kpi-lbl"><?= $k['lbl'] ?></div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- ── Pipeline chips (filtres visuels) ── -->
    <div class="pipe-bar" id="pipeBar">
        <div class="pipe-chip active" data-statut="" onclick="filterPipe(this,'')"
             style="background:#f1f5f9;color:#1e293b;">
            <i class="bi bi-grid-3x3-gap-fill"></i> Tous
            <span class="chip-count"><?= count($patients) ?></span>
        </div>
        <?php foreach($pipeline as $st):
            if(!isset($etapes[$st])) continue;
            $cfg = $etapes[$st];
            $cnt = $compteurs[$st] ?? 0;
        ?>
        <div class="pipe-chip" data-statut="<?= $st ?>" onclick="filterPipe(this,'<?= $st ?>')"
             style="background:<?= $cfg['bg'] ?>;color:<?= $cfg['color'] ?>;">
            <i class="bi <?= $cfg['icon'] ?>"></i>
            <?= $cfg['label'] ?>
            <span class="chip-count"><?= $cnt ?></span>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- ── Table ── -->
    <div class="sp-table-wrap" style="overflow-x:auto;">
        <div class="sp-table-head">
            <div class="sp-table-title">
                <i class="bi bi-people-fill" style="color:#6366f1;"></i>
                <span id="tableCount"><?= count($patients) ?> patients</span>
            </div>
            <input type="search" class="sp-search" id="searchBox"
                   placeholder="Rechercher nom, dossier…" oninput="applyFilters()">
        </div>
        <div style="overflow-x:auto;">
        <table class="sp-tbl" id="spTable">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Patient</th>
                    <th>Dossier</th>
                    <th>Étape actuelle</th>
                    <th>Service</th>
                    <th>Médecin</th>
                    <th>Arrivée</th>
                    <th>Durée</th>
                    <th>Dernière activité</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody id="spBody">
            <?php
            $rowNum = 0;
            foreach($patients as $p):
                $rowNum++;
                $st     = $p['statut_parcours'] ?? '';
                $cfg    = $etapes[$st] ?? ['label'=>$st,'color'=>'#94a3b8','bg'=>'#f1f5f9','icon'=>'bi-question-circle'];
                $min    = (int)($p['minutes_depuis_creation'] ?? 0);
                $durCls = $min > 180 ? 'danger' : ($min > 60 ? 'warn' : '');
                $age    = $p['date_naissance']
                    ? floor((time() - strtotime($p['date_naissance'])) / 31557600)
                    : '?';

                // Dernière activité texte
                $acts = [];
                if ($p['heure_derniere_consult']) $acts[] = 'Consult. '.$p['heure_derniere_consult'];
                if ($p['heure_dernier_labo'])     $acts[] = 'Labo '.$p['heure_dernier_labo'];
                if ($p['nom_lit'])                $acts[] = $p['nom_chambre'].' · '.$p['nom_lit'];
                $actTxt = implode(' · ', $acts) ?: '—';

                $rowData = json_encode([
                    'id'             => (int)$p['id'],
                    'nom'            => htmlspecialchars($p['nom'].' '.$p['prenom'], ENT_QUOTES),
                    'statut_parcours'=> $st,
                    'service_id'     => (int)($p['service_id'] ?? 0),
                    'medecin_id'     => (int)($p['medecin_id'] ?? 0),
                ]);
            ?>
            <?php
            // Normaliser le circuit pour le filtre
            $circuitNorm = ($st === 'PARAMETRES_MATERNITE') ? 'MATERNITE' : strtoupper($p['circuit'] ?? 'STANDARD');
            ?>
            <tr data-statut="<?= htmlspecialchars($st) ?>"
                data-service="<?= (int)($p['service_id']??0) ?>"
                data-circuit="<?= htmlspecialchars($circuitNorm) ?>"
                data-search="<?= strtolower(htmlspecialchars($p['nom'].' '.$p['prenom'].' '.$p['dossier_numero'])) ?>">
                <td style="color:#94a3b8;font-size:.75rem;"><?= $rowNum ?></td>
                <td>
                    <div style="font-weight:800;color:#1e293b;"><?= htmlspecialchars($p['nom'].' '.$p['prenom']) ?></div>
                    <div style="font-size:.72rem;color:#64748b;"><?= $age ?> ans · <?= $p['sexe'] ?? '?' ?></div>
                </td>
                <td style="font-family:monospace;font-weight:700;color:#475569;"><?= htmlspecialchars($p['dossier_numero'] ?? '') ?></td>
                <td>
                    <span class="sp-badge" style="background:<?= $cfg['bg'] ?>;color:<?= $cfg['color'] ?>;">
                        <i class="bi <?= $cfg['icon'] ?>"></i>
                        <?= $cfg['label'] ?>
                    </span>
                    <?php
                    // Précision du circuit pour les étapes "Paramètres" et "Accueil"
                    $circuit = strtoupper($p['circuit'] ?? '');
                    if (in_array($st, ['PARAMETRES','PARAMETRES_MATERNITE','ACCUEIL','ATTENTE_CONSULTATION'])):
                        if ($st === 'PARAMETRES_MATERNITE'):
                    ?>
                        <span style="display:block;margin-top:3px;font-size:.68rem;
                                     color:#ec4899;font-weight:700;">
                            <i class="bi bi-heart-fill"></i> Maternité
                        </span>
                    <?php elseif ($circuit === 'PHP'): ?>
                        <span style="display:block;margin-top:3px;font-size:.68rem;
                                     color:#7c3aed;font-weight:700;">
                            <i class="bi bi-shield-fill"></i> PHP
                        </span>
                    <?php elseif (!empty($p['nom_service'])): ?>
                        <span style="display:block;margin-top:3px;font-size:.68rem;
                                     color:#64748b;font-weight:600;">
                            <i class="bi bi-building"></i> <?= htmlspecialchars($p['nom_service']) ?>
                        </span>
                    <?php endif; endif; ?>
                </td>
                <td style="font-size:.78rem;color:#475569;"><?= htmlspecialchars($p['nom_service'] ?? '—') ?></td>
                <td style="font-size:.78rem;color:#475569;"><?= htmlspecialchars($p['medecin_nom'] ?? '—') ?></td>
                <td style="font-size:.75rem;color:#64748b;white-space:nowrap;">
                    <?= $p['created_at'] ? date('H:i', strtotime($p['created_at'])) : '—' ?>
                </td>
                <td><span class="sp-dur <?= $durCls ?>"><?= minutesEnTexte($min) ?></span></td>
                <td style="font-size:.75rem;color:#64748b;"><?= $actTxt ?></td>
                <td>
                    <div style="display:flex;gap:6px;align-items:center;">
                        <?php if (($_SESSION['user_role'] ?? '') !== 'COORDONNATEUR_SOINS'): ?>
                        <button class="btn-reor" onclick='ouvrirReor(<?= $rowData ?>)'>
                            <i class="bi bi-arrow-left-right me-1"></i>Réorienter
                        </button>
                        <?php endif; ?>
                        <a href="<?= BASE_URL ?>patients/dossier/<?= $p['id'] ?>"
                           title="Ouvrir dossier" target="_blank"
                           style="color:#6366f1;font-size:1rem;padding:4px 6px;">
                            <i class="bi bi-folder2-open"></i>
                        </a>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if(empty($patients)): ?>
            <tr><td colspan="10" style="text-align:center;padding:40px;color:#94a3b8;">
                <i class="bi bi-inbox" style="font-size:2rem;display:block;margin-bottom:8px;"></i>
                Aucun patient actif aujourd'hui.
            </td></tr>
            <?php endif; ?>
            </tbody>
        </table>
        </div>
    </div>
</div>

<!-- ── Modal réorientation ── -->
<div class="reor-modal-bg" id="reorBg" onclick="if(event.target===this)fermerReor()">
    <div class="reor-modal">
        <div class="reor-modal-head">
            <h5><i class="bi bi-arrow-left-right me-2"></i>Réorienter le patient</h5>
            <button onclick="fermerReor()" style="background:rgba(255,255,255,.2);border:none;color:#fff;
                    border-radius:8px;padding:4px 10px;cursor:pointer;font-size:1rem;">✕</button>
        </div>
        <div class="reor-modal-body">
            <input type="hidden" id="reorPatientId">
            <div style="background:#f8fafc;border-radius:10px;padding:10px 14px;
                        font-weight:700;font-size:.85rem;color:#1e293b;" id="reorPatientName"></div>

            <div>
                <div class="reor-label">Étape / Statut parcours</div>
                <select class="reor-select" id="reorStatut">
                    <?php foreach($etapes as $st => $cfg): ?>
                    <option value="<?= $st ?>"><?= $cfg['label'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <div class="reor-label">Service de destination</div>
                <select class="reor-select" id="reorService">
                    <option value="">— Conserver le service actuel —</option>
                    <?php foreach($services as $svc): ?>
                    <option value="<?= $svc['id'] ?>"><?= htmlspecialchars($svc['nom_service']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <div class="reor-label">Médecin assigné</div>
                <select class="reor-select" id="reorMedecin">
                    <option value="">— Aucun / Conserver —</option>
                    <?php foreach($medecins as $med): ?>
                    <option value="<?= $med['id'] ?>">
                        Dr <?= htmlspecialchars($med['nom'].' '.$med['prenom']) ?>
                        <?= $med['specialite'] ? '· '.$med['specialite'] : '' ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <div class="reor-label">Note / Motif (optionnel)</div>
                <textarea class="reor-textarea" id="reorNote" rows="2"
                          placeholder="Ex : patient réorienté vers chirurgie suite à bilan…"></textarea>
            </div>
        </div>
        <div class="reor-modal-foot">
            <button class="btn-cancel" onclick="fermerReor()">Annuler</button>
            <button class="btn-save" id="btnSauveReor" onclick="sauvegarderReor()">
                <i class="bi bi-check2-circle me-1"></i> Valider la réorientation
            </button>
        </div>
    </div>
</div>

<!-- Toast -->
<div id="reorToast" style="display:none;position:fixed;bottom:24px;right:24px;padding:13px 20px;
     border-radius:14px;font-weight:700;font-size:.88rem;z-index:9999;
     box-shadow:0 8px 28px rgba(0,0,0,.18);align-items:center;gap:8px;"></div>

<div id="refreshBadge">Actualisation…</div>

<script>
const BASE = '<?= BASE_URL ?>';

/* ── Filtre pipeline ── */
let activeStatut = '', activeService = 0;

function filterPipe(el, statut) {
    document.querySelectorAll('.pipe-chip').forEach(c => c.classList.remove('active'));
    el.classList.add('active');
    activeStatut = statut;
    applyFilters();
}

function applyFilters() {
    activeService = parseInt(document.getElementById('filterService').value) || 0;
    const activeCircuit = document.getElementById('filterCircuit').value;
    const q = document.getElementById('searchBox').value.toLowerCase().trim();
    let visible = 0;
    document.querySelectorAll('#spBody tr[data-statut]').forEach(row => {
        const matchSt  = !activeStatut  || row.dataset.statut   === activeStatut;
        const matchSvc = !activeService || parseInt(row.dataset.service) === activeService;
        const matchCir = !activeCircuit || row.dataset.circuit  === activeCircuit;
        const matchQ   = !q || row.dataset.search.includes(q);
        const show     = matchSt && matchSvc && matchCir && matchQ;
        row.style.display = show ? '' : 'none';
        if (show) visible++;
    });
    document.getElementById('tableCount').textContent = visible + ' patient' + (visible > 1 ? 's' : '');
}

/* ── Modal réorientation ── */
function ouvrirReor(data) {
    document.getElementById('reorPatientId').value  = data.id;
    document.getElementById('reorPatientName').textContent = data.nom;
    document.getElementById('reorStatut').value  = data.statut_parcours || '';
    document.getElementById('reorService').value = data.service_id || '';
    document.getElementById('reorMedecin').value = data.medecin_id || '';
    document.getElementById('reorNote').value    = '';
    document.getElementById('reorBg').classList.add('show');
}
function fermerReor() {
    document.getElementById('reorBg').classList.remove('show');
}

function sauvegarderReor() {
    const btn = document.getElementById('btnSauveReor');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Enregistrement…';

    const body = {
        patient_id:      parseInt(document.getElementById('reorPatientId').value),
        statut_parcours: document.getElementById('reorStatut').value,
        service_id:      document.getElementById('reorService').value || null,
        medecin_id:      document.getElementById('reorMedecin').value || null,
        note:            document.getElementById('reorNote').value,
    };

    fetch(BASE + 'admin/reorienter-patient', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(body),
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            fermerReor();
            showToast('✓ ' + data.patient + ' → ' + data.nouveau, '#16a34a');
            setTimeout(() => location.reload(), 1800);
        } else {
            showToast('Erreur : ' + (data.message || 'Inconnue'), '#dc2626');
        }
    })
    .catch(() => showToast('Erreur réseau.', '#dc2626'))
    .finally(() => {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-check2-circle me-1"></i> Valider la réorientation';
    });
}

/* ── Toast ── */
function showToast(msg, bg) {
    const t = document.getElementById('reorToast');
    t.style.background = bg;
    t.style.color = '#fff';
    t.innerHTML = msg;
    t.style.display = 'flex';
    setTimeout(() => { t.style.transition='opacity .5s'; t.style.opacity='0';
        setTimeout(() => { t.style.display='none'; t.style.opacity='1'; }, 500); }, 4000);
}

/* ── Countdown auto-refresh (60s) ── */
let countdown = 60;
const badge = document.getElementById('countdownBadge');
function tick() {
    badge.textContent = 'Actualisation dans ' + countdown + 's';
    if (--countdown <= 0) {
        document.getElementById('refreshBadge').style.display = 'flex';
        location.reload();
    }
}
tick();
setInterval(tick, 1000);

/* Fermer modal avec Échap */
document.addEventListener('keydown', e => { if (e.key === 'Escape') fermerReor(); });
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
