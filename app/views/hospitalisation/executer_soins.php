<?php require_once __DIR__ . '/../layouts/header.php';
// Calculs globaux
$total    = count($soins);
$realises = count(array_filter($soins, fn($s) => $s['statut'] === 'REALISE'));
$annules  = count(array_filter($soins, fn($s) => $s['statut'] === 'ANNULE'));
$actifs   = $total - $annules;
$pct      = $actifs > 0 ? round($realises / $actifs * 100) : 0;

// Grouper par type_soin
$grouped = [];
foreach ($soins as $s) {
    $grouped[$s['type_soin']][] = $s;
}

$patient_nom = strtoupper($soins[0]['nom'] ?? '') . ' ' . ($soins[0]['prenom'] ?? '');
$dossier_num = $soins[0]['dossier_numero'] ?? '';
$service_nom = $soins[0]['nom_service'] ?? '';
$admission_id = $soins[0]['admission_id'] ?? $plan_id;

// On retrouve le patient_id depuis l'admission
$patient_id_val = 0;
foreach ($soins as $s) {
    if (!empty($s['patient_id'])) { $patient_id_val = $s['patient_id']; break; }
}
?>

<style>
:root { --clr-ok:#16a34a; --clr-warn:#d97706; --clr-err:#dc2626; --clr-blue:#2563eb; }
body { background:#f1f5f9; }
.sidebar { display:none !important; }
main { margin-left:0 !important; width:100% !important; }

/* ── Header ── */
.exec-header {
    background:linear-gradient(135deg,#1e40af,#2563eb);
    color:#fff; padding:20px 32px;
    display:flex; align-items:center; justify-content:space-between;
    flex-wrap:wrap; gap:12px;
}
.exec-header .pat-info h4 { margin:0; font-size:1.15rem; font-weight:800; }
.exec-header .pat-info small { opacity:.8; font-size:.8rem; }

/* ── Barre de progression ── */
.progress-bar-wrap { background:rgba(255,255,255,.2); border-radius:8px; height:8px; overflow:hidden; min-width:200px; }
.progress-bar-fill { height:100%; border-radius:8px; background:#4ade80; transition:width .4s; }

/* ── Corps ── */
.exec-body { max-width:960px; margin:28px auto; padding:0 16px; }

/* ── Groupe de soins ── */
.soin-group { background:#fff; border-radius:14px; box-shadow:0 2px 10px rgba(0,0,0,.06);
    margin-bottom:20px; overflow:hidden; }
.soin-group-header {
    padding:10px 18px; background:#f8fafc; border-bottom:1px solid #e2e8f0;
    display:flex; align-items:center; gap:8px;
    font-size:.78rem; font-weight:800; text-transform:uppercase; letter-spacing:.06em; color:#475569;
}
.soin-group-header .count-badge { margin-left:auto; }

/* ── Ligne de soin ── */
.soin-row {
    display:flex; align-items:center; gap:12px;
    padding:14px 18px; border-bottom:1px solid #f1f5f9;
    transition:background .15s;
    position:relative;
}
.soin-row:last-child { border-bottom:none; }
.soin-row:hover { background:#fafbfc; }
.soin-row.realise  { background:#f0fdf4; }
.soin-row.annule   { background:#fef2f2; opacity:.65; }
.soin-row.annule .soin-desc { text-decoration:line-through; color:#94a3b8; }

/* Checkbox custom */
.soin-check {
    width:22px; height:22px; border-radius:6px; border:2px solid #cbd5e1;
    appearance:none; cursor:pointer; flex-shrink:0; transition:all .15s;
    display:flex; align-items:center; justify-content:center;
}
.soin-check:checked { background:var(--clr-ok); border-color:var(--clr-ok); }
.soin-check:checked::after { content:'✓'; color:#fff; font-size:.8rem; font-weight:900; }
.soin-check:disabled { cursor:default; opacity:.7; }

/* Heure badge */
.heure-badge {
    background:#1e293b; color:#fff; border-radius:20px;
    padding:3px 10px; font-size:.72rem; font-weight:700;
    flex-shrink:0; font-family:monospace;
}
.heure-badge.retard { background:#dc2626; }

/* Contenu */
.soin-content { flex:1; min-width:0; }
.soin-desc { font-weight:600; font-size:.9rem; color:#1e293b; }
.soin-cond { display:inline-block; margin-top:3px; padding:1px 8px;
    border-radius:20px; background:#fef9c3; border:1px solid #fde68a;
    color:#92400e; font-size:.7rem; font-weight:600; }
.soin-meta { font-size:.7rem; color:#64748b; margin-top:4px; }
.soin-meta .by { font-weight:600; color:#2563eb; }

/* Badges statut */
.badge-realise { background:#dcfce7; color:var(--clr-ok); border-radius:20px;
    padding:3px 10px; font-size:.7rem; font-weight:700; }
.badge-annule  { background:#fee2e2; color:var(--clr-err); border-radius:20px;
    padding:3px 10px; font-size:.7rem; font-weight:700; }
.badge-corr    { background:#ede9fe; color:#7c3aed; border-radius:20px;
    padding:3px 10px; font-size:.7rem; font-weight:700; }

/* Bouton Rayer */
.btn-rayer {
    background:none; border:1.5px dashed #fca5a5; color:#dc2626; border-radius:8px;
    padding:4px 10px; font-size:.7rem; font-weight:700; cursor:pointer;
    transition:all .15s; flex-shrink:0;
}
.btn-rayer:hover { background:#fee2e2; }

/* Modal de rayure */
.rayer-panel {
    display:none; background:#fff7f7; border:1px solid #fecaca;
    border-radius:10px; margin:0 18px 12px 52px; padding:14px;
}
.rayer-panel.open { display:block; }

/* Footer sticky */
.exec-footer {
    position:sticky; bottom:0; background:#fff;
    border-top:1px solid #e2e8f0; padding:14px 24px;
    display:flex; align-items:center; justify-content:space-between;
    flex-wrap:wrap; gap:10px; z-index:100;
    box-shadow:0 -4px 20px rgba(0,0,0,.07);
    max-width:960px; margin:0 auto;
    border-radius:14px 14px 0 0;
}
.btn-valider {
    background:var(--clr-ok); color:#fff; border:none; border-radius:30px;
    padding:10px 32px; font-weight:800; font-size:.95rem; cursor:pointer;
    transition:filter .15s;
}
.btn-valider:hover { filter:brightness(1.1); }
.btn-valider:disabled { background:#94a3b8; cursor:not-allowed; }

/* Toast notification */
#toast {
    position:fixed; bottom:80px; right:20px; background:#1e293b; color:#fff;
    border-radius:10px; padding:10px 18px; font-size:.82rem;
    opacity:0; pointer-events:none; transition:opacity .3s; z-index:9999;
}
#toast.show { opacity:1; }
</style>

<!-- HEADER -->
<div class="exec-header">
    <div class="pat-info">
        <h4><i class="bi bi-clipboard2-pulse-fill me-2"></i><?= htmlspecialchars($patient_nom) ?></h4>
        <small>
            <?= htmlspecialchars($dossier_num) ?>
            <?php if($service_nom): ?> &bull; <?= htmlspecialchars($service_nom) ?><?php endif; ?>
            &bull; <?= date('d/m/Y') ?>
        </small>
    </div>
    <div class="d-flex flex-column align-items-end gap-2">
        <div class="d-flex align-items-center gap-10" style="gap:12px;">
            <span style="font-size:.82rem;opacity:.85;"><?= $realises ?>/<?= $actifs ?> soins réalisés</span>
            <span style="font-size:1.1rem;font-weight:800;"><?= $pct ?>%</span>
        </div>
        <div class="progress-bar-wrap" style="min-width:220px;">
            <div class="progress-bar-fill" id="globalBar" style="width:<?= $pct ?>%"></div>
        </div>
    </div>
</div>

<!-- CORPS -->
<div class="exec-body">

<form id="formExecution" action="<?= BASE_URL ?>hospitalisation/valider-execution" method="POST">
    <input type="hidden" name="admission_id" value="<?= htmlspecialchars($admission_id) ?>">
    <input type="hidden" name="patient_id"   value="<?= htmlspecialchars($patient_id_val) ?>">

<?php foreach ($grouped as $typeSoin => $lignes): ?>
<?php
    $nbReal = count(array_filter($lignes, fn($s)=>$s['statut']==='REALISE'));
    $nbAnn  = count(array_filter($lignes, fn($s)=>$s['statut']==='ANNULE'));
    $nbTot  = count($lignes);
?>
<div class="soin-group">
    <div class="soin-group-header">
        <i class="bi bi-tag-fill"></i>
        <?= htmlspecialchars($typeSoin) ?>
        <span class="count-badge">
            <span class="badge-realise"><?= $nbReal ?>/<?= $nbTot-$nbAnn ?></span>
        </span>
    </div>

    <?php foreach ($lignes as $s):
        $isRealise = $s['statut'] === 'REALISE';
        $isAnnule  = $s['statut'] === 'ANNULE';
        $isPlanifie= !$isRealise && !$isAnnule;
        $heure     = substr($s['date_prevue'] ?? '', 11, 5) ?: '--:--';
        $now       = date('H:i');
        $enRetard  = $isPlanifie && $heure !== '--:--' && $heure < $now;
        $isCorr    = !empty($s['note_execution']) && str_starts_with($s['note_execution'], 'CORR.');
    ?>
    <div class="soin-row <?= $isRealise?'realise':($isAnnule?'annule':'') ?>" id="row-<?= $s['id'] ?>">

        <!-- Checkbox -->
        <input type="checkbox"
               class="soin-check"
               name="soins_faits[]"
               value="<?= $s['id'] ?>"
               id="chk-<?= $s['id'] ?>"
               <?= $isRealise  ? 'checked' : '' ?>
               <?= $isAnnule   ? 'disabled' : '' ?>
               onchange="cocherSoin(<?= $s['id'] ?>, this.checked)">

        <!-- Heure -->
        <span class="heure-badge <?= $enRetard?'retard':'' ?>"><?= $heure ?></span>

        <!-- Contenu -->
        <div class="soin-content">
            <div class="soin-desc"><?= htmlspecialchars($s['description'] ?? '') ?></div>
            <?php if (!empty($s['condition_application'])): ?>
            <span class="soin-cond">⚡ <?= htmlspecialchars($s['condition_application']) ?></span>
            <?php endif; ?>
            <div class="soin-meta">
                <?php if ($isRealise && !empty($s['executant_nom'])): ?>
                    <i class="bi bi-person-check-fill text-success"></i>
                    Réalisé par <span class="by"><?= htmlspecialchars($s['executant_nom'].' '.$s['executant_prenom']) ?></span>
                    <?php if(!empty($s['date_realisee'])): ?>
                        à <?= date('H:i', strtotime($s['date_realisee'])) ?>
                    <?php endif; ?>
                <?php elseif ($isAnnule): ?>
                    <i class="bi bi-slash-circle text-danger"></i>
                    Rayé — <?= htmlspecialchars($s['note_execution'] ?? '') ?>
                <?php elseif ($isCorr): ?>
                    <span class="badge-corr">CORRECTION</span>
                <?php elseif ($enRetard): ?>
                    <i class="bi bi-clock text-danger"></i> <span class="text-danger fw-semibold">En retard</span>
                <?php endif; ?>
            </div>
        </div>

        <!-- Badges droite -->
        <div class="d-flex align-items-center gap-2 flex-shrink-0">
            <?php if ($isRealise): ?>
                <span class="badge-realise"><i class="bi bi-check2"></i> Effectué</span>
            <?php elseif ($isAnnule): ?>
                <span class="badge-annule"><i class="bi bi-x"></i> Rayé</span>
            <?php else: ?>
                <!-- Bouton Rayer (seulement pour soins actifs) -->
                <button type="button" class="btn-rayer" onclick="ouvrirRayer(<?= $s['id'] ?>)">
                    <i class="bi bi-pencil-slash"></i> Rayer
                </button>
            <?php endif; ?>
        </div>
    </div>

    <!-- Panel rayure inline (caché par défaut) -->
    <?php if ($isPlanifie): ?>
    <div class="rayer-panel" id="panel-rayer-<?= $s['id'] ?>">
        <div class="fw-semibold text-danger mb-2" style="font-size:.82rem;">
            <i class="bi bi-pencil-slash me-1"></i>Rayer et corriger — <?= htmlspecialchars($s['description']) ?>
        </div>
        <div class="row g-2">
            <div class="col-md-4">
                <label class="form-label" style="font-size:.75rem;">Motif de la rayure</label>
                <input type="text" class="form-control form-control-sm"
                       id="motif-<?= $s['id'] ?>" placeholder="ex: erreur de dosage, doublon…">
            </div>
            <div class="col-md-6">
                <label class="form-label" style="font-size:.75rem;">Ligne corrigée <small class="text-muted">(laisser vide si annulation simple)</small></label>
                <input type="text" class="form-control form-control-sm"
                       id="corr-<?= $s['id'] ?>" placeholder="Nouvelle description corrigée…"
                       value="<?= htmlspecialchars($s['description'] ?? '') ?>">
            </div>
            <div class="col-md-2 d-flex align-items-end gap-1">
                <button type="button" class="btn btn-danger btn-sm w-100" onclick="confirmerRayer(<?= $s['id'] ?>)">
                    <i class="bi bi-check2"></i> Valider
                </button>
                <button type="button" class="btn btn-light btn-sm" onclick="fermerRayer(<?= $s['id'] ?>)">
                    <i class="bi bi-x"></i>
                </button>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php endforeach; ?>
</div>
<?php endforeach; ?>

<!-- FOOTER -->
<div class="exec-footer">
    <div class="d-flex align-items-center gap-3">
        <a href="<?= BASE_URL ?>hospitalisation/suivi/<?= $patient_id_val ?>" class="btn btn-light btn-sm rounded-pill">
            <i class="bi bi-arrow-left"></i> Retour
        </a>
        <span id="footerStatus" class="text-muted" style="font-size:.8rem;">
            <span id="footerRealises"><?= $realises ?></span>/<?= $actifs ?> soins réalisés
        </span>
    </div>
    <button type="submit" class="btn-valider" id="btnValider">
        <i class="bi bi-check2-all me-2"></i>Valider et enregistrer
    </button>
</div>

</form>
</div><!-- /exec-body -->

<div id="toast"></div>

<script>
const BASE = '<?= BASE_URL ?>';
let pendingAjax = 0;

// ── Afficher toast ──
function showToast(msg, ok=true) {
    const t = document.getElementById('toast');
    t.textContent = msg;
    t.style.background = ok ? '#16a34a' : '#dc2626';
    t.classList.add('show');
    setTimeout(() => t.classList.remove('show'), 2500);
}

// ── Mettre à jour la barre de progression ──
function updateProgress() {
    const total  = document.querySelectorAll('.soin-check:not(:disabled)').length;
    const coches = document.querySelectorAll('.soin-check:checked:not(:disabled)').length;
    const pct    = total > 0 ? Math.round(coches / total * 100) : 0;
    document.getElementById('globalBar').style.width = pct + '%';
    document.getElementById('footerRealises').textContent = coches;
}

// ── Cocher/décocher un soin (AJAX auto-save) ──
function cocherSoin(soinId, cocher) {
    const row = document.getElementById('row-' + soinId);
    row.style.opacity = '.5';

    fetch(BASE + 'hospitalisation/cocher-soin', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({ soin_id: soinId, cocher: cocher })
    })
    .then(r => r.json())
    .then(res => {
        row.style.opacity = '1';
        if (res.success) {
            row.classList.toggle('realise', cocher);
            if (cocher) {
                row.querySelector('.soin-meta').innerHTML =
                    '<i class="bi bi-person-check-fill text-success"></i> Réalisé maintenant';
            }
            updateProgress();
            showToast(cocher ? '✓ Soin enregistré' : 'Soin remis en attente');
        } else {
            // Annuler le changement visuel
            document.getElementById('chk-' + soinId).checked = !cocher;
            showToast('Erreur : ' + (res.message || 'inconnue'), false);
        }
    })
    .catch(() => {
        row.style.opacity = '1';
        document.getElementById('chk-' + soinId).checked = !cocher;
        showToast('Erreur réseau', false);
    });
}

// ── Ouvrir/fermer le panel de rayure ──
function ouvrirRayer(id) {
    document.querySelectorAll('.rayer-panel.open').forEach(p => p.classList.remove('open'));
    document.getElementById('panel-rayer-' + id).classList.add('open');
}
function fermerRayer(id) {
    document.getElementById('panel-rayer-' + id).classList.remove('open');
}

// ── Confirmer la rayure ──
function confirmerRayer(soinId) {
    const motif  = document.getElementById('motif-' + soinId).value.trim();
    const corr   = document.getElementById('corr-' + soinId).value.trim();
    if (!motif) { alert('Veuillez indiquer le motif de la rayure.'); return; }

    fetch(BASE + 'hospitalisation/rayer-soin', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({ soin_id: soinId, motif: motif, correction: corr })
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            // Barrer la ligne originale
            const row = document.getElementById('row-' + soinId);
            row.classList.add('annule');
            row.querySelector('.soin-desc').style.textDecoration = 'line-through';
            row.querySelector('.soin-check').disabled = true;
            row.querySelector('.soin-check').checked  = false;
            row.querySelector('.btn-rayer')?.remove();
            row.querySelector('.soin-meta').innerHTML =
                '<i class="bi bi-slash-circle text-danger"></i> Rayé — ' + motif;

            fermerRayer(soinId);
            updateProgress();
            showToast('Ligne rayée' + (corr ? ' + correction ajoutée' : ''));

            // Recharger après 1,5s pour afficher la ligne corrigée
            if (res.new_id) setTimeout(() => location.reload(), 1500);
        } else {
            showToast('Erreur : ' + (res.message || 'inconnue'), false);
        }
    })
    .catch(() => showToast('Erreur réseau', false));
}
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
