<?php
/* Vue : app/views/admin/licences.php
   Variables : $licences (array), $licenceActive (array|null)
*/
$licences      = $licences      ?? [];
$licenceActive = $licenceActive ?? null;

$today    = date('Y-m-d');
require_once __DIR__ . '/../../services/CsrfService.php';
$csrf     = htmlspecialchars(CsrfService::getToken());

// Calcul statut global
if ($licenceActive) {
    $joursRestants = (int)((strtotime($licenceActive['date_fin']) - strtotime($today)) / 86400);
    $statutClass   = $joursRestants <= 30 ? 'warning' : 'ok';
} else {
    $joursRestants = 0;
    $statutClass   = 'expired';
}
?>
<style>
.lic-banner { border-radius:16px; padding:20px 24px; display:flex; align-items:center; gap:16px; margin-bottom:24px; }
.lic-banner.ok      { background:linear-gradient(135deg,#d1fae5,#a7f3d0); border:1px solid #6ee7b7; }
.lic-banner.warning { background:linear-gradient(135deg,#fef9c3,#fde68a); border:1px solid #fbbf24; }
.lic-banner.expired { background:linear-gradient(135deg,#fee2e2,#fecaca); border:1px solid #fca5a5; }
.lic-banner-icon { width:52px; height:52px; border-radius:15px; display:flex; align-items:center; justify-content:center; font-size:1.5rem; flex-shrink:0; }
.lic-banner.ok      .lic-banner-icon { background:rgba(16,185,129,.2); color:#065f46; }
.lic-banner.warning .lic-banner-icon { background:rgba(245,158,11,.2); color:#78350f; }
.lic-banner.expired .lic-banner-icon { background:rgba(239,68,68,.2); color:#7f1d1d; }
.lic-banner-title { font-size:1rem; font-weight:800; }
.lic-banner.ok      .lic-banner-title { color:#065f46; }
.lic-banner.warning .lic-banner-title { color:#78350f; }
.lic-banner.expired .lic-banner-title { color:#7f1d1d; }
.lic-banner-sub { font-size:.78rem; opacity:.8; margin-top:2px; }
.lic-key-badge { font-family:monospace; font-size:.9rem; font-weight:800; background:rgba(0,0,0,.08); padding:4px 12px; border-radius:8px; letter-spacing:.5px; }

.lic-card { background:#fff; border-radius:16px; box-shadow:0 2px 10px rgba(0,0,0,.06); border:1px solid #e2e8f0; margin-bottom:22px; overflow:hidden; }
.lic-card-head { padding:14px 20px; font-weight:800; font-size:.82rem; text-transform:uppercase; letter-spacing:.5px; background:#f8fafc; border-bottom:2px solid #e2e8f0; display:flex; align-items:center; gap:8px; color:#1e293b; }
.lic-card-body { padding:20px; }

.lic-form-grid { display:grid; grid-template-columns:1fr 1fr; gap:14px; }
.lic-label { font-size:.7rem; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:.4px; display:block; margin-bottom:5px; }
.lic-input { width:100%; padding:10px 13px; border:1.5px solid #e2e8f0; border-radius:10px; font-size:.88rem; color:#1e293b; outline:none; transition:border .15s; box-sizing:border-box; }
.lic-input:focus { border-color:#3b82f6; box-shadow:0 0 0 3px rgba(59,130,246,.1); }
.lic-input.mono { font-family:monospace; font-size:.92rem; letter-spacing:.5px; font-weight:700; }

.lic-key-wrap { position:relative; }
.lic-key-wrap .lic-input { padding-right:110px; }
.lic-gen-btn { position:absolute; right:8px; top:50%; transform:translateY(-50%); padding:5px 12px; border:1.5px solid #e2e8f0; border-radius:8px; background:#f8fafc; font-size:.72rem; font-weight:700; cursor:pointer; color:#1d4ed8; transition:.15s; white-space:nowrap; }
.lic-gen-btn:hover { background:#eff6ff; border-color:#3b82f6; }

.lic-submit-btn { background:#1d4ed8; color:#fff; border:none; border-radius:11px; padding:11px 24px; font-size:.85rem; font-weight:700; cursor:pointer; display:inline-flex; align-items:center; gap:7px; transition:.15s; margin-top:4px; }
.lic-submit-btn:hover { background:#1e40af; }
.lic-submit-btn:disabled { opacity:.55; cursor:not-allowed; }

.lic-table { width:100%; border-collapse:collapse; font-size:.83rem; }
.lic-table th { background:#f8fafc; color:#64748b; font-weight:700; padding:10px 14px; text-align:left; border-bottom:2px solid #e2e8f0; font-size:.72rem; text-transform:uppercase; letter-spacing:.4px; white-space:nowrap; }
.lic-table td { padding:12px 14px; border-bottom:1px solid #f1f5f9; vertical-align:middle; }
.lic-table tr:last-child td { border-bottom:none; }
.lic-table tr:hover td { background:#f8fafc; }

.lic-status { display:inline-flex; align-items:center; gap:5px; padding:4px 10px; border-radius:20px; font-size:.72rem; font-weight:700; white-space:nowrap; }
.lic-status.active   { background:#dcfce7; color:#166534; }
.lic-status.expired  { background:#fee2e2; color:#991b1b; }
.lic-status.soon     { background:#fef9c3; color:#713f12; }
.lic-status.revoked  { background:#f1f5f9; color:#64748b; }
.lic-status.future   { background:#eff6ff; color:#1d4ed8; }
.lic-status-dot { width:7px; height:7px; border-radius:50%; background:currentColor; }

.lic-cle-cell { font-family:monospace; font-weight:700; font-size:.86rem; color:#1e293b; letter-spacing:.3px; }
.lic-action-btn { padding:5px 11px; border-radius:8px; border:1.5px solid transparent; font-size:.74rem; font-weight:700; cursor:pointer; transition:.15s; }
.btn-revoke  { background:#fef2f2; color:#dc2626; border-color:#fca5a5; }
.btn-revoke:hover  { background:#fee2e2; }
.btn-restore { background:#f0fdf4; color:#16a34a; border-color:#86efac; }
.btn-restore:hover { background:#dcfce7; }
.btn-delete  { background:#f8fafc; color:#64748b; border-color:#e2e8f0; }
.btn-delete:hover  { background:#fee2e2; color:#dc2626; border-color:#fca5a5; }

.lic-progress { height:5px; background:#e2e8f0; border-radius:4px; margin-top:4px; overflow:hidden; }
.lic-progress-bar { height:100%; border-radius:4px; transition:width .4s; }

.lic-alert { padding:10px 15px; border-radius:10px; font-size:.82rem; font-weight:600; margin-bottom:16px; display:none; }
.lic-alert.success { background:#f0fdf4; border:1px solid #86efac; color:#166534; }
.lic-alert.error   { background:#fef2f2; border:1px solid #fca5a5; color:#991b1b; }
</style>

<!-- TOPBAR -->
<div class="admin-topbar">
    <div class="admin-topbar-title">
        Gestion des licences
        <small>Clés d'activation et durées de validité de SimCare+</small>
    </div>
    <div class="adm-topbar-actions">
        <div class="adm-clock-pill"><span class="adm-clock-dot"></span><span class="adm-clock-time">--:--:--</span></div>
    </div>
</div>

<div class="admin-page-content">

    <!-- STATUT LICENCE ACTIVE -->
    <div class="lic-banner <?= $statutClass ?>">
        <div class="lic-banner-icon">
            <?php if ($statutClass === 'ok'): ?>
                <i class="bi bi-patch-check-fill"></i>
            <?php elseif ($statutClass === 'warning'): ?>
                <i class="bi bi-exclamation-triangle-fill"></i>
            <?php else: ?>
                <i class="bi bi-x-octagon-fill"></i>
            <?php endif; ?>
        </div>
        <div style="flex:1">
            <?php if ($licenceActive): ?>
                <div class="lic-banner-title">
                    <?= $joursRestants <= 30 ? 'Licence bientôt expirée' : 'Licence active et valide' ?>
                    &nbsp;&bull;&nbsp;
                    <span class="lic-key-badge"><?= htmlspecialchars($licenceActive['cle']) ?></span>
                </div>
                <div class="lic-banner-sub">
                    Valide du <strong><?= date('d/m/Y', strtotime($licenceActive['date_debut'])) ?></strong>
                    au <strong><?= date('d/m/Y', strtotime($licenceActive['date_fin'])) ?></strong>
                    — <strong><?= $joursRestants ?> jour<?= $joursRestants > 1 ? 's' : '' ?> restant<?= $joursRestants > 1 ? 's' : '' ?></strong>
                    <?= $licenceActive['description'] ? ' · ' . htmlspecialchars($licenceActive['description']) : '' ?>
                </div>
            <?php else: ?>
                <div class="lic-banner-title">Aucune licence active</div>
                <div class="lic-banner-sub">L'application est en mode restreint. Créez une nouvelle licence ci-dessous.</div>
            <?php endif; ?>
        </div>
        <?php if ($licenceActive && $joursRestants > 0): ?>
        <div style="text-align:right;flex-shrink:0;">
            <div style="font-size:2rem;font-weight:900;line-height:1;"><?= $joursRestants ?></div>
            <div style="font-size:.7rem;font-weight:700;opacity:.7;">jours restants</div>
        </div>
        <?php endif; ?>
    </div>

    <!-- FORMULAIRE CRÉATION -->
    <div class="lic-card">
        <div class="lic-card-head"><i class="bi bi-plus-circle-fill" style="color:#1d4ed8"></i> Créer une nouvelle licence</div>
        <div class="lic-card-body">
            <div id="licAlert" class="lic-alert"></div>
            <div class="lic-form-grid" style="grid-template-columns:1fr auto auto;">
                <!-- Clé -->
                <div>
                    <label class="lic-label">Clé de licence *</label>
                    <div class="lic-key-wrap">
                        <input type="text" id="licCle" class="lic-input mono" placeholder="XXXX-XXXX-XXXX-XXXX ou clé personnalisée" maxlength="128">
                        <button type="button" class="lic-gen-btn" onclick="genererCle()">
                            <i class="bi bi-arrow-repeat me-1"></i>Générer
                        </button>
                    </div>
                </div>
                <!-- Date début -->
                <div>
                    <label class="lic-label">Date de début *</label>
                    <input type="date" id="licDebut" class="lic-input" value="<?= $today ?>">
                </div>
                <!-- Date fin -->
                <div>
                    <label class="lic-label">Date de fin *</label>
                    <input type="date" id="licFin" class="lic-input" min="<?= $today ?>">
                </div>
            </div>

            <!-- Durées rapides -->
            <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:10px;">
                <span style="font-size:.72rem;font-weight:700;color:#64748b;align-self:center;">Durée rapide :</span>
                <?php foreach([
                    ['30 jours',  30], ['3 mois', 90], ['6 mois', 180],
                    ['1 an',     365], ['2 ans',  730], ['Illimité (10 ans)', 3650]
                ] as [$lbl, $jours]): ?>
                <button type="button" class="lic-gen-btn" style="position:static;transform:none;"
                        onclick="setDuree(<?= $jours ?>)"><?= $lbl ?></button>
                <?php endforeach; ?>
            </div>

            <div style="margin-top:14px;">
                <label class="lic-label">Description (optionnel)</label>
                <input type="text" id="licDesc" class="lic-input" placeholder="Ex : Licence annuelle HSJM, renouvellement 2025…" maxlength="255">
            </div>

            <div style="margin-top:18px;display:flex;align-items:center;gap:14px;flex-wrap:wrap;">
                <button id="licSubmitBtn" onclick="creerLicence()" class="lic-submit-btn">
                    <i class="bi bi-key-fill"></i> Créer la licence
                </button>
                <div id="licDureeLabel" style="font-size:.8rem;color:#64748b;"></div>
            </div>
        </div>
    </div>

    <!-- TABLEAU DES LICENCES -->
    <div class="lic-card">
        <div class="lic-card-head">
            <i class="bi bi-list-ul" style="color:#1d4ed8"></i> Historique des licences
            <span style="font-size:.72rem;font-weight:400;color:#94a3b8;margin-left:auto;"><?= count($licences) ?> licence<?= count($licences) > 1 ? 's' : '' ?></span>
        </div>
        <div class="lic-card-body" style="padding:0;">
            <?php if (empty($licences)): ?>
            <div style="text-align:center;padding:40px;color:#94a3b8;">
                <i class="bi bi-key" style="font-size:2rem;display:block;margin-bottom:8px;"></i>
                Aucune licence enregistrée. Créez votre première licence ci-dessus.
            </div>
            <?php else: ?>
            <div style="overflow-x:auto;">
            <table class="lic-table">
                <thead>
                    <tr>
                        <th>Clé</th>
                        <th>Description</th>
                        <th>Début</th>
                        <th>Fin</th>
                        <th>Durée</th>
                        <th>Statut</th>
                        <th>Créé par</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="licTableBody">
                <?php foreach ($licences as $lic):
                    $debut = $lic['date_debut'];
                    $fin   = $lic['date_fin'];
                    $dureeJ = (int)((strtotime($fin) - strtotime($debut)) / 86400);

                    if (!$lic['actif']) {
                        $st = 'revoked'; $stLabel = 'Révoquée';
                    } elseif ($fin < $today) {
                        $st = 'expired'; $stLabel = 'Expirée';
                    } elseif ($debut > $today) {
                        $st = 'future';  $stLabel = 'À venir';
                    } elseif ((strtotime($fin) - strtotime($today)) / 86400 <= 30) {
                        $st = 'soon';    $stLabel = 'Expire bientôt';
                    } else {
                        $st = 'active';  $stLabel = 'Active';
                    }

                    $pct = 0;
                    if ($debut <= $today && $fin >= $today && $dureeJ > 0) {
                        $pct = min(100, (int)(((strtotime($today) - strtotime($debut)) / ($dureeJ * 86400)) * 100));
                    }
                    $barColor = match($st) { 'active'=>'#22c55e','soon'=>'#eab308','expired'=>'#ef4444','revoked'=>'#94a3b8',default=>'#3b82f6' };
                    $creePar = trim(strtoupper($lic['cree_par_nom'] ?? '') . ' ' . ($lic['cree_par_prenom'] ?? ''));
                ?>
                <tr id="licRow<?= $lic['id'] ?>">
                    <td class="lic-cle-cell" style="max-width:220px;">
                        <div style="display:flex;align-items:center;gap:7px;">
                            <span><?= htmlspecialchars($lic['cle']) ?></span>
                            <button onclick="copierCle('<?= htmlspecialchars($lic['cle']) ?>', this)"
                                    style="background:none;border:none;cursor:pointer;color:#94a3b8;font-size:.78rem;padding:2px 4px;border-radius:5px;"
                                    title="Copier la clé">
                                <i class="bi bi-clipboard"></i>
                            </button>
                        </div>
                    </td>
                    <td style="color:#475569;font-size:.8rem;max-width:180px;">
                        <?= htmlspecialchars($lic['description'] ?: '—') ?>
                    </td>
                    <td style="white-space:nowrap;"><?= date('d/m/Y', strtotime($debut)) ?></td>
                    <td style="white-space:nowrap;"><?= date('d/m/Y', strtotime($fin)) ?></td>
                    <td style="min-width:90px;">
                        <div style="font-size:.8rem;font-weight:700;color:#1e293b;">
                            <?= $dureeJ >= 365 ? round($dureeJ/365, 1) . ' an(s)' : $dureeJ . ' j' ?>
                        </div>
                        <?php if ($st === 'active' || $st === 'soon'): ?>
                        <div class="lic-progress">
                            <div class="lic-progress-bar" style="width:<?= $pct ?>%;background:<?= $barColor ?>;"></div>
                        </div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="lic-status <?= $st ?>">
                            <span class="lic-status-dot"></span><?= $stLabel ?>
                        </span>
                    </td>
                    <td style="font-size:.8rem;color:#64748b;"><?= htmlspecialchars($creePar ?: '—') ?></td>
                    <td>
                        <div style="display:flex;gap:6px;flex-wrap:wrap;">
                        <?php if ($lic['actif']): ?>
                            <button class="lic-action-btn btn-revoke"
                                    onclick="revoquerLicence(<?= $lic['id'] ?>, this)"
                                    title="Désactiver cette licence">
                                <i class="bi bi-slash-circle"></i> Révoquer
                            </button>
                        <?php else: ?>
                            <button class="lic-action-btn btn-restore"
                                    onclick="reactiverLicence(<?= $lic['id'] ?>, this)"
                                    title="Réactiver cette licence">
                                <i class="bi bi-check-circle"></i> Réactiver
                            </button>
                        <?php endif; ?>
                            <button class="lic-action-btn btn-delete"
                                    onclick="supprimerLicence(<?= $lic['id'] ?>, this)"
                                    title="Supprimer définitivement">
                                <i class="bi bi-trash3"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            </div>
            <?php endif; ?>
        </div>
    </div>

</div><!-- /admin-page-content -->

<script>
const LIC_BASE  = '<?= BASE_URL ?>';
const LIC_CSRF  = '<?= $csrf ?>';

/* ── Durée rapide ──────────────────────────────────────────────────── */
function setDuree(jours) {
    const debut = document.getElementById('licDebut').value || '<?= $today ?>';
    const d = new Date(debut);
    d.setDate(d.getDate() + jours);
    const fin = d.toISOString().split('T')[0];
    document.getElementById('licFin').value = fin;
    updateDureeLabel();
}

function updateDureeLabel() {
    const d1 = document.getElementById('licDebut').value;
    const d2 = document.getElementById('licFin').value;
    const el = document.getElementById('licDureeLabel');
    if (!d1 || !d2 || d2 <= d1) { el.textContent = ''; return; }
    const j = Math.round((new Date(d2) - new Date(d1)) / 86400000);
    const ans = Math.floor(j / 365);
    const mois = Math.floor((j % 365) / 30);
    let label = j + ' jours';
    if (ans > 0) label += ` (${ans} an${ans>1?'s':''}${mois>0?' '+mois+' mois':''})`;
    else if (mois > 0) label += ` (${mois} mois)`;
    el.textContent = '→ ' + label;
}
document.getElementById('licDebut')?.addEventListener('change', updateDureeLabel);
document.getElementById('licFin')?.addEventListener('change', updateDureeLabel);

/* ── Générer une clé ───────────────────────────────────────────────── */
function genererCle() {
    fetch(LIC_BASE + 'admin/licences/generer-cle')
        .then(r => r.json())
        .then(d => { if (d.cle) document.getElementById('licCle').value = d.cle; });
}

/* ── Créer une licence ─────────────────────────────────────────────── */
function creerLicence() {
    const cle   = document.getElementById('licCle').value.trim();
    const debut = document.getElementById('licDebut').value;
    const fin   = document.getElementById('licFin').value;
    const desc  = document.getElementById('licDesc').value.trim();
    const btn   = document.getElementById('licSubmitBtn');

    if (!cle)   { licShowAlert('La clé de licence est obligatoire.', false); return; }
    if (!debut || !fin) { licShowAlert('Les dates sont obligatoires.', false); return; }
    if (fin <= debut)   { licShowAlert('La date de fin doit être après la date de début.', false); return; }

    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Création…';

    const fd = new FormData();
    fd.append('_csrf_token', LIC_CSRF);
    fd.append('cle',         cle);
    fd.append('date_debut',  debut);
    fd.append('date_fin',    fin);
    fd.append('description', desc);

    fetch(LIC_BASE + 'admin/licences/creer', { method:'POST', body:fd })
        .then(r => r.json())
        .then(data => {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-key-fill"></i> Créer la licence';
            if (data.success) {
                licShowAlert(data.message, true);
                document.getElementById('licCle').value  = '';
                document.getElementById('licDesc').value = '';
                document.getElementById('licFin').value  = '';
                document.getElementById('licDureeLabel').textContent = '';
                setTimeout(() => location.reload(), 1800);
            } else {
                licShowAlert(data.message || 'Erreur.', false);
            }
        })
        .catch(() => {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-key-fill"></i> Créer la licence';
            licShowAlert('Erreur réseau.', false);
        });
}

/* ── Révoquer ──────────────────────────────────────────────────────── */
function revoquerLicence(id, btn) {
    if (!confirm('Révoquer cette licence ? Elle ne sera plus utilisable.')) return;
    actionLicence(id, 'revoquer', btn, 'Révocation…');
}

/* ── Réactiver ─────────────────────────────────────────────────────── */
function reactiverLicence(id, btn) {
    actionLicence(id, 'reactiver', btn, 'Réactivation…');
}

/* ── Supprimer ─────────────────────────────────────────────────────── */
function supprimerLicence(id, btn) {
    if (!confirm('Supprimer définitivement cette licence ? Cette action est irréversible.')) return;
    actionLicence(id, 'supprimer', btn, 'Suppression…');
}

function actionLicence(id, action, btn, label) {
    btn.disabled = true;
    btn.textContent = label;
    const fd = new FormData();
    fd.append('_csrf_token', LIC_CSRF);
    fetch(LIC_BASE + 'admin/licences/' + action + '/' + id, { method:'POST', body:fd })
        .then(r => r.json())
        .then(d => {
            if (d.success) {
                if (action === 'supprimer') {
                    const row = document.getElementById('licRow' + id);
                    row.style.opacity = '0';
                    row.style.transition = '.3s';
                    setTimeout(() => row.remove(), 350);
                } else {
                    setTimeout(() => location.reload(), 600);
                }
            } else {
                btn.disabled = false;
                btn.textContent = action === 'revoquer' ? 'Révoquer' : 'Réactiver';
                alert(d.message || 'Erreur.');
            }
        })
        .catch(() => { btn.disabled = false; btn.textContent = action; });
}

/* ── Copier la clé ─────────────────────────────────────────────────── */
function copierCle(cle, btn) {
    navigator.clipboard.writeText(cle).then(() => {
        btn.innerHTML = '<i class="bi bi-clipboard-check" style="color:#16a34a"></i>';
        setTimeout(() => btn.innerHTML = '<i class="bi bi-clipboard"></i>', 2000);
    });
}

/* ── Alert formulaire ──────────────────────────────────────────────── */
function licShowAlert(msg, ok) {
    const el = document.getElementById('licAlert');
    el.className = 'lic-alert ' + (ok ? 'success' : 'error');
    el.innerHTML = (ok ? '<i class="bi bi-check-circle-fill me-1"></i>' : '<i class="bi bi-exclamation-circle-fill me-1"></i>') + msg;
    el.style.display = 'block';
    if (ok) setTimeout(() => el.style.display = 'none', 3500);
}
</script>
