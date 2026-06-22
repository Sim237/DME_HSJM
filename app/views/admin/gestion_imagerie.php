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

/* ============================================================================
   ADMIN — GESTION IMAGERIE MÉDICALE
   Catalogue des examens d'imagerie + suivi des demandes
============================================================================ */
require_once __DIR__ . '/../layouts/header.php';
$adminPage = 'imagerie';
require_once __DIR__ . '/partials/admin_nav.php';

$examens_imagerie  = $examens_imagerie  ?? [];
$stats_imagerie    = $stats_imagerie    ?? ['jour' => 0, 'attente' => 0, 'termine_jour' => 0];
$demandes_recentes = $demandes_recentes ?? [];

$totalExams  = count($examens_imagerie);
$disponibles = count(array_filter($examens_imagerie, fn($e) => $e['disponible']));

// Modalités : libellé, couleur, icône
$modalites = [
    'radiographie' => ['Radiographie', '#3b82f6', 'bi-radioactive'],
    'scanner'      => ['Scanner',      '#8b5cf6', 'bi-circle-square'],
    'irm'          => ['IRM',          '#ec4899', 'bi-magnet-fill'],
    'echographie'  => ['Échographie',  '#10b981', 'bi-soundwave'],
    'mammographie' => ['Mammographie', '#f59e0b', 'bi-gender-female'],
    'autre'        => ['Autre',        '#64748b', 'bi-grid-fill'],
];
$modInfo = fn($t) => $modalites[strtolower($t ?? '')] ?? $modalites['autre'];

// Statuts demandes : libellé + couleurs
function statutImagerieBadge($s) {
    $u = strtoupper($s ?? '');
    if (in_array($u, ['TERMINE','VALIDE','INTERPRETE'])) return ['Terminé',    '#f0fdf4', '#15803d'];
    if ($u === 'EN_COURS')                               return ['En cours',   '#eff6ff', '#1d4ed8'];
    if ($u === 'PROGRAMME')                              return ['Programmé',  '#f5f3ff', '#6d28d9'];
    if ($u === 'ANNULE')                                 return ['Annulé',     '#f8fafc', '#94a3b8'];
    return ['En attente', '#fffbeb', '#b45309'];
}
?>

        <!-- TOPBAR -->
        <div class="admin-topbar">
            <div class="admin-topbar-title">
                Gestion Imagerie Médicale
                <small>Catalogue des examens et suivi des demandes</small>
            </div>
            <div class="adm-topbar-actions">
                <div class="adm-clock-pill">
                    <span class="adm-clock-dot"></span>
                    <span class="adm-clock-time">--:--:--</span>
                </div>
                <a href="<?= BASE_URL ?>imagerie" class="adm-topbtn adm-topbtn-ghost" target="_blank">
                    <i class="bi bi-box-arrow-up-right"></i> Module imagerie
                </a>
                <button class="adm-topbtn adm-topbtn-primary" onclick="openImgExamModal()">
                    <i class="bi bi-plus-circle-fill"></i> Nouvel examen
                </button>
            </div>
        </div>

        <div class="admin-page-content">

            <!-- ── STATS ── -->
            <div class="row g-3 mb-4">
                <div class="col-6 col-md-3">
                    <div class="adm-stat" style="--adm-accent:#6366f1;">
                        <div class="adm-stat-val"><?= $totalExams ?></div>
                        <div class="adm-stat-lbl">Examens au catalogue</div>
                        <i class="bi bi-radioactive adm-stat-ico"></i>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="adm-stat" style="--adm-accent:#22c55e;">
                        <div class="adm-stat-val"><?= $disponibles ?></div>
                        <div class="adm-stat-lbl">Disponibles</div>
                        <i class="bi bi-check-circle-fill adm-stat-ico"></i>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="adm-stat" style="--adm-accent:#f59e0b;">
                        <div class="adm-stat-val"><?= $stats_imagerie['attente'] ?></div>
                        <div class="adm-stat-lbl">Demandes en attente</div>
                        <i class="bi bi-hourglass-split adm-stat-ico"></i>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="adm-stat" style="--adm-accent:#3b82f6;">
                        <div class="adm-stat-val"><?= $stats_imagerie['jour'] ?></div>
                        <div class="adm-stat-lbl">Demandes aujourd'hui</div>
                        <i class="bi bi-calendar-day adm-stat-ico"></i>
                    </div>
                </div>
            </div>

            <!-- ── CATALOGUE ── -->
            <div class="adm-section-title"><i class="bi bi-card-list"></i> Catalogue des examens</div>
            <div class="adm-card mb-4">
                <div class="adm-card-header" style="flex-wrap:wrap;gap:10px;">
                    <span><i class="bi bi-radioactive me-2"></i>Examens d'imagerie (<?= $totalExams ?>)</span>
                    <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                        <!-- Filtre modalité -->
                        <div id="imgModFilters" style="display:flex;gap:5px;flex-wrap:wrap;">
                            <button type="button" class="img-mod-chip active" data-mod="" onclick="setModFilter(this)"
                                    style="--chip-color:#334155;">Toutes</button>
                            <?php foreach ($modalites as $val => [$lbl, $col, $ico]): ?>
                            <button type="button" class="img-mod-chip" data-mod="<?= $val ?>" onclick="setModFilter(this)"
                                    style="--chip-color:<?= $col ?>;">
                                <i class="bi <?= $ico ?>"></i> <?= $lbl ?>
                            </button>
                            <?php endforeach; ?>
                        </div>
                        <input type="search" id="imgSearch" placeholder="Rechercher…" oninput="filtrerImgExams()"
                               style="border:1px solid #e2e8f0;border-radius:9px;padding:6px 12px;font-size:.8rem;width:180px;outline:none;">
                    </div>
                </div>
                <style>
                    .img-mod-chip {
                        border:1.5px solid #e2e8f0; background:#fff; color:#64748b;
                        font-size:.72rem; font-weight:700; padding:4px 11px; border-radius:20px;
                        cursor:pointer; transition:all .15s; display:inline-flex; align-items:center; gap:5px;
                    }
                    .img-mod-chip:hover { border-color:var(--chip-color); color:var(--chip-color); }
                    .img-mod-chip.active {
                        background:var(--chip-color); border-color:var(--chip-color); color:#fff;
                    }
                </style>
                <div style="overflow-x:auto;">
                    <table class="adm-table" id="imgExamTable">
                        <thead>
                            <tr>
                                <th>Code</th><th>Examen</th><th>Modalité</th><th>Partie du corps</th>
                                <th>Prix (FCFA)</th><th>Contraste</th><th>Délai</th><th>Statut</th><th style="text-align:right;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($examens_imagerie)): ?>
                            <tr><td colspan="9" class="text-center text-muted py-4">Aucun examen au catalogue.</td></tr>
                            <?php endif; ?>
                            <?php foreach ($examens_imagerie as $e):
                                [$mLbl, $mCol, $mIco] = $modInfo($e['type_examen']);
                            ?>
                            <tr data-search="<?= htmlspecialchars(strtolower(($e['code'] ?? '') . ' ' . $e['nom'] . ' ' . $mLbl . ' ' . ($e['partie_corps'] ?? ''))) ?>"
                                data-mod="<?= htmlspecialchars(strtolower($e['type_examen'] ?? 'autre')) ?>">
                                <td><span style="font-family:monospace;font-size:.78rem;color:#64748b;"><?= htmlspecialchars($e['code'] ?? '—') ?></span></td>
                                <td><strong><?= htmlspecialchars($e['nom']) ?></strong></td>
                                <td>
                                    <span style="display:inline-flex;align-items:center;gap:5px;background:<?= $mCol ?>14;color:<?= $mCol ?>;
                                                 font-size:.72rem;font-weight:700;padding:3px 10px;border-radius:20px;">
                                        <i class="bi <?= $mIco ?>"></i><?= $mLbl ?>
                                    </span>
                                </td>
                                <td><?= htmlspecialchars($e['partie_corps'] ?? '—') ?></td>
                                <td><strong><?= number_format((float)$e['prix'], 0, ',', ' ') ?></strong></td>
                                <td><?= $e['avec_contraste'] ? '<span style="color:#7c3aed;font-weight:700;font-size:.75rem;">Possible</span>' : '<span class="text-muted" style="font-size:.75rem;">Non</span>' ?></td>
                                <td style="font-size:.8rem;"><?= (int)$e['delai_rendu_heures'] ?> h</td>
                                <td>
                                    <?= $e['disponible']
                                        ? '<span style="background:#f0fdf4;color:#15803d;font-size:.7rem;font-weight:700;padding:3px 10px;border-radius:20px;">Disponible</span>'
                                        : '<span style="background:#fef2f2;color:#b91c1c;font-size:.7rem;font-weight:700;padding:3px 10px;border-radius:20px;">Indisponible</span>' ?>
                                </td>
                                <td style="text-align:right;white-space:nowrap;">
                                    <button class="adm-action-btn" title="Modifier"
                                            onclick='openImgExamModal(<?= json_encode($e, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <button class="adm-action-btn" title="Supprimer" style="color:#dc2626;"
                                            onclick="supprimerImgExam(<?= (int)$e['id'] ?>, '<?= htmlspecialchars(addslashes($e['nom'])) ?>')">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- ── DEMANDES RÉCENTES ── -->
            <div class="adm-section-title"><i class="bi bi-clock-history"></i> Dernières demandes d'imagerie</div>
            <div class="adm-card mb-5">
                <div style="overflow-x:auto;">
                    <table class="adm-table">
                        <thead>
                            <tr><th>Patient</th><th>Examen</th><th>Médecin</th><th>Urgence</th><th>Statut</th><th>Date</th></tr>
                        </thead>
                        <tbody>
                            <?php if (empty($demandes_recentes)): ?>
                            <tr><td colspan="6" class="text-center text-muted py-4">Aucune demande d'imagerie.</td></tr>
                            <?php endif; ?>
                            <?php foreach ($demandes_recentes as $d):
                                [$sLbl, $sBg, $sCol] = statutImagerieBadge($d['statut']);
                                $exLbl = $d['type_imagerie'] ?: ucfirst($d['type_examen'] ?? 'Imagerie');
                                if (!empty($d['partie_corps'])) $exLbl .= ' — ' . $d['partie_corps'];
                            ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars(strtoupper($d['patient_nom'] ?? '') . ' ' . ($d['patient_prenom'] ?? '')) ?></strong>
                                    <div style="font-size:.7rem;color:#94a3b8;"><?= htmlspecialchars($d['dossier_numero'] ?? '') ?></div>
                                </td>
                                <td><?= htmlspecialchars($exLbl) ?></td>
                                <td style="font-size:.82rem;"><?= htmlspecialchars(trim(($d['medecin_nom'] ?? '') . ' ' . ($d['medecin_prenom'] ?? '')) ?: '—') ?></td>
                                <td>
                                    <?= strtoupper($d['urgence'] ?? '') === 'URGENT'
                                        ? '<span style="background:#fef2f2;color:#dc2626;font-size:.7rem;font-weight:800;padding:3px 9px;border-radius:20px;">URGENT</span>'
                                        : '<span class="text-muted" style="font-size:.75rem;">Normal</span>' ?>
                                </td>
                                <td><span style="background:<?= $sBg ?>;color:<?= $sCol ?>;font-size:.7rem;font-weight:700;padding:3px 10px;border-radius:20px;"><?= $sLbl ?></span></td>
                                <td style="font-size:.78rem;color:#64748b;"><?= $d['date_creation'] ? date('d/m/Y H:i', strtotime($d['date_creation'])) : '—' ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div><!-- /admin-page-content -->

<!-- ══ MODAL EXAMEN IMAGERIE — autonome, sans dépendance Bootstrap ══ -->
<style>
    #modalImgExam { display:none; position:fixed; inset:0; z-index:5000; }
    #modalImgExam.open { display:flex; align-items:center; justify-content:center; padding:20px; }
    .imgx-backdrop { position:absolute; inset:0; background:rgba(15,23,42,.45); backdrop-filter:blur(4px); }
    .imgx-card {
        position:relative; z-index:1; width:100%; max-width:540px;
        background:#fff; border-radius:20px; overflow:hidden;
        box-shadow:0 30px 80px rgba(0,0,0,.3);
        max-height:92vh; display:flex; flex-direction:column;
        animation:imgxIn .25s cubic-bezier(.2,.9,.3,1.15) both;
    }
    @keyframes imgxIn { from { opacity:0; transform:translateY(24px) scale(.96); } to { opacity:1; transform:none; } }
    .imgx-head {
        display:flex; align-items:center; justify-content:space-between;
        padding:18px 24px; background:linear-gradient(135deg,#3b82f6,#6366f1);
    }
    .imgx-head h5 { margin:0; color:#fff; font-weight:800; font-size:1rem; display:flex; align-items:center; gap:9px; }
    .imgx-close {
        width:32px; height:32px; border:none; border-radius:9px; cursor:pointer;
        background:rgba(255,255,255,.18); color:#fff; font-size:1rem;
        display:flex; align-items:center; justify-content:center; transition:.15s;
    }
    .imgx-close:hover { background:rgba(255,255,255,.32); }
    .imgx-body { padding:22px 24px; overflow-y:auto; }
    .imgx-grid { display:grid; grid-template-columns:1fr 1fr; gap:14px 14px; }
    .imgx-field.full { grid-column:1 / -1; }
    .imgx-label {
        display:block; font-size:.68rem; font-weight:800; text-transform:uppercase;
        letter-spacing:.6px; color:#64748b; margin-bottom:5px;
    }
    .imgx-label .req { color:#dc2626; }
    .imgx-input, .imgx-select {
        width:100%; border:1.5px solid #e2e8f0; border-radius:11px;
        padding:9px 13px; font-size:.86rem; color:#1e293b; outline:none;
        background:#f8fafc; transition:border-color .15s, background .15s, box-shadow .15s;
    }
    .imgx-input:focus, .imgx-select:focus {
        border-color:#6366f1; background:#fff; box-shadow:0 0 0 3px rgba(99,102,241,.12);
    }
    .imgx-input::placeholder { color:#b6c2d4; }
    /* Toggles doux */
    .imgx-toggles { display:flex; gap:10px; margin-top:16px; flex-wrap:wrap; }
    .imgx-toggle {
        flex:1; min-width:200px; display:flex; align-items:center; gap:10px;
        border:1.5px solid #e2e8f0; border-radius:12px; padding:10px 14px;
        cursor:pointer; font-size:.8rem; font-weight:700; color:#475569;
        transition:.15s; user-select:none; background:#f8fafc;
    }
    .imgx-toggle:hover { border-color:#c7d2fe; }
    .imgx-toggle input { display:none; }
    .imgx-toggle .tg {
        width:36px; height:20px; border-radius:20px; background:#cbd5e1;
        position:relative; flex-shrink:0; transition:background .2s;
    }
    .imgx-toggle .tg::after {
        content:''; position:absolute; top:2px; left:2px; width:16px; height:16px;
        border-radius:50%; background:#fff; transition:transform .2s;
        box-shadow:0 1px 3px rgba(0,0,0,.25);
    }
    .imgx-toggle input:checked + .tg { background:#22c55e; }
    .imgx-toggle input:checked + .tg::after { transform:translateX(16px); }
    .imgx-toggle input:checked ~ .lbl { color:#15803d; }
    .imgx-foot {
        display:flex; justify-content:flex-end; gap:10px;
        padding:16px 24px; border-top:1px solid #f1f5f9; background:#fafbfd;
    }
    .imgx-btn {
        padding:10px 22px; border-radius:11px; font-size:.84rem; font-weight:700;
        border:none; cursor:pointer; transition:.15s; display:inline-flex; align-items:center; gap:7px;
    }
    .imgx-btn-ghost { background:#f1f5f9; color:#475569; }
    .imgx-btn-ghost:hover { background:#e2e8f0; }
    .imgx-btn-primary {
        background:linear-gradient(135deg,#3b82f6,#6366f1); color:#fff;
        box-shadow:0 4px 14px rgba(99,102,241,.35);
    }
    .imgx-btn-primary:hover { transform:translateY(-1px); box-shadow:0 6px 18px rgba(99,102,241,.45); }
    @media (max-width:560px) { .imgx-grid { grid-template-columns:1fr; } }
</style>

<div id="modalImgExam">
    <div class="imgx-backdrop" onclick="fermerImgExamModal()"></div>
    <div class="imgx-card">
        <div class="imgx-head">
            <h5 id="imgExamModalTitle"><i class="bi bi-radioactive"></i> Nouvel examen d'imagerie</h5>
            <button type="button" class="imgx-close" onclick="fermerImgExamModal()"><i class="bi bi-x-lg"></i></button>
        </div>
        <form id="formImgExam" onsubmit="return sauverImgExam(event)" style="display:flex;flex-direction:column;min-height:0;">
            <div class="imgx-body">
                <input type="hidden" name="id" id="imgExamId" value="0">
                <div class="imgx-grid">
                    <div class="imgx-field full">
                        <label class="imgx-label">Nom de l'examen <span class="req">*</span></label>
                        <input type="text" name="nom" id="imgExamNom" class="imgx-input" required placeholder="Ex : Radiographie thorax">
                    </div>
                    <div class="imgx-field">
                        <label class="imgx-label">Code</label>
                        <input type="text" name="code" id="imgExamCode" class="imgx-input" placeholder="Ex : RX-THOR">
                    </div>
                    <div class="imgx-field">
                        <label class="imgx-label">Modalité</label>
                        <select name="type_examen" id="imgExamType" class="imgx-select">
                            <?php foreach ($modalites as $val => [$lbl]): ?>
                            <option value="<?= $val ?>"><?= $lbl ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="imgx-field">
                        <label class="imgx-label">Partie du corps</label>
                        <input type="text" name="partie_corps" id="imgExamPartie" class="imgx-input" placeholder="Thorax, Crâne, Abdomen…">
                    </div>
                    <div class="imgx-field">
                        <label class="imgx-label">Prix (FCFA)</label>
                        <input type="number" name="prix" id="imgExamPrix" class="imgx-input" min="0" step="500" value="0">
                    </div>
                    <div class="imgx-field full">
                        <label class="imgx-label">Délai de rendu (heures)</label>
                        <input type="number" name="delai_rendu_heures" id="imgExamDelai" class="imgx-input" min="1" value="24">
                    </div>
                </div>
                <div class="imgx-toggles">
                    <label class="imgx-toggle">
                        <input type="checkbox" name="avec_contraste" id="imgExamContraste">
                        <span class="tg"></span>
                        <span class="lbl"><i class="bi bi-droplet-half me-1"></i>Contraste possible</span>
                    </label>
                    <label class="imgx-toggle">
                        <input type="checkbox" name="disponible" id="imgExamDispo" checked>
                        <span class="tg"></span>
                        <span class="lbl"><i class="bi bi-check-circle me-1"></i>Disponible</span>
                    </label>
                </div>
            </div>
            <div class="imgx-foot">
                <button type="button" class="imgx-btn imgx-btn-ghost" onclick="fermerImgExamModal()">Annuler</button>
                <button type="submit" class="imgx-btn imgx-btn-primary" id="btnSaveImgExam">
                    <i class="bi bi-save"></i> Enregistrer
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openImgExamModal(exam = null) {
    document.getElementById('imgExamModalTitle').innerHTML = exam
        ? '<i class="bi bi-pencil-square me-2"></i>Modifier l\'examen'
        : '<i class="bi bi-radioactive me-2"></i>Nouvel examen d\'imagerie';
    document.getElementById('imgExamId').value        = exam?.id ?? 0;
    document.getElementById('imgExamCode').value      = exam?.code ?? '';
    document.getElementById('imgExamNom').value       = exam?.nom ?? '';
    document.getElementById('imgExamType').value      = exam?.type_examen ?? 'radiographie';
    document.getElementById('imgExamPartie').value    = exam?.partie_corps ?? '';
    document.getElementById('imgExamPrix').value      = exam?.prix ? parseFloat(exam.prix) : 0;
    document.getElementById('imgExamDelai').value     = exam?.delai_rendu_heures ?? 24;
    document.getElementById('imgExamContraste').checked = !!parseInt(exam?.avec_contraste ?? 0);
    document.getElementById('imgExamDispo').checked     = exam ? !!parseInt(exam.disponible) : true;
    document.getElementById('modalImgExam').classList.add('open');
    document.body.style.overflow = 'hidden';
    setTimeout(() => document.getElementById('imgExamNom').focus(), 120);
}

function fermerImgExamModal() {
    document.getElementById('modalImgExam').classList.remove('open');
    document.body.style.overflow = '';
}

/* Fermer avec Échap */
document.addEventListener('keydown', e => {
    if (e.key === 'Escape') fermerImgExamModal();
});

function sauverImgExam(e) {
    e.preventDefault();
    const btn = document.getElementById('btnSaveImgExam');
    btn.disabled = true;
    btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Enregistrement…';
    fetch('<?= BASE_URL ?>admin/save-examen-imagerie', {
        method: 'POST',
        body: new FormData(document.getElementById('formImgExam'))
    })
    .then(r => r.json())
    .then(d => {
        if (d.success) { location.reload(); }
        else {
            alert('Erreur : ' + (d.message || 'Impossible d\'enregistrer.'));
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-save"></i> Enregistrer';
        }
    })
    .catch(() => {
        alert('Erreur réseau.');
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-save me-1"></i>Enregistrer';
    });
    return false;
}

function supprimerImgExam(id, nom) {
    if (!confirm('Supprimer l\'examen « ' + nom + ' » du catalogue ?')) return;
    fetch('<?= BASE_URL ?>admin/delete-examen-imagerie/' + id)
        .then(r => r.json())
        .then(d => {
            if (d.success) location.reload();
            else alert('Erreur : ' + (d.message || 'Suppression impossible.'));
        })
        .catch(() => alert('Erreur réseau.'));
}

/* ── Filtres : recherche texte + modalité ── */
let modFilterActive = '';

function setModFilter(btn) {
    modFilterActive = btn.dataset.mod;
    document.querySelectorAll('.img-mod-chip').forEach(c => c.classList.toggle('active', c === btn));
    filtrerImgExams();
}

function filtrerImgExams() {
    const q = document.getElementById('imgSearch').value.toLowerCase().trim();
    document.querySelectorAll('#imgExamTable tbody tr[data-search]').forEach(tr => {
        const matchQ = !q || tr.dataset.search.includes(q);
        const matchM = !modFilterActive || tr.dataset.mod === modFilterActive;
        tr.style.display = (matchQ && matchM) ? '' : 'none';
    });
}
</script>
