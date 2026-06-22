<?php
if (session_status() === PHP_SESSION_NONE) session_start();
$flash = $_SESSION['garde_flash'] ?? null;
unset($_SESSION['garde_flash']);
$csrf = CsrfService::field();
$token = CsrfService::getToken();

$typeLabels = [
    'nuit'          => ['Nuit',           '#1e3a5f', '#dbeafe'],
    'weekend'       => ['Week-end',        '#3b0764', '#ede9fe'],
    'jour_ferie'    => ['Jour férié',      '#78350f', '#fef3c7'],
    'remplacement'  => ['Remplacement',    '#134e4a', '#d1fae5'],
    'urgence'       => ['Urgence',         '#7f1d1d', '#fee2e2'],
];
$statutBadge = [
    'en_cours'  => ['En cours',   '#065f46', '#d1fae5'],
    'planifie'  => ['Planifié',   '#1e40af', '#dbeafe'],
    'termine'   => ['Terminé',    '#374151', '#f3f4f6'],
    'annule'    => ['Annulé',     '#6b7280', '#f9fafb'],
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Gestion des gardes — SimCare+</title>
<link rel="stylesheet" href="<?= BASE_URL ?>public/css/bootstrap.min.css">
<link rel="stylesheet" href="<?= BASE_URL ?>public/css/bootstrap-icons.min.css">
<link rel="stylesheet" href="<?= BASE_URL ?>public/css/fonts.css">
<style>
*{box-sizing:border-box;}
body{font-family:'Plus Jakarta Sans',sans-serif;background:#f1f5f9;margin:0;}
.gd-header{background:linear-gradient(135deg,#1e3a5f 0%,#1d4ed8 100%);
           padding:20px 32px;display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;}
.gd-header h1{color:#fff;font-size:1.25rem;font-weight:800;margin:0;display:flex;align-items:center;gap:10px;}
.gd-back{color:rgba(255,255,255,.8);font-size:.82rem;text-decoration:none;display:flex;align-items:center;gap:5px;font-weight:600;border:1px solid rgba(255,255,255,.3);padding:6px 14px;border-radius:20px;transition:.15s;}
.gd-back:hover{background:rgba(255,255,255,.15);color:#fff;}
.gd-body{max-width:1200px;margin:0 auto;padding:28px 20px;}
.gd-card{background:#fff;border-radius:16px;box-shadow:0 2px 12px rgba(0,0,0,.07);margin-bottom:24px;overflow:hidden;}
.gd-card-head{padding:16px 22px;border-bottom:1px solid #e2e8f0;display:flex;align-items:center;justify-content:space-between;gap:10px;}
.gd-card-title{font-size:.9rem;font-weight:800;color:#0f172a;display:flex;align-items:center;gap:8px;}
.gd-badge{display:inline-flex;align-items:center;gap:4px;padding:3px 10px;border-radius:20px;font-size:.72rem;font-weight:700;}
.gd-table{width:100%;border-collapse:collapse;font-size:.82rem;}
.gd-table th{background:#f8fafc;color:#64748b;font-weight:700;font-size:.72rem;text-transform:uppercase;letter-spacing:.4px;padding:10px 14px;text-align:left;border-bottom:1px solid #e2e8f0;}
.gd-table td{padding:12px 14px;border-bottom:1px solid #f1f5f9;vertical-align:middle;}
.gd-table tr:last-child td{border-bottom:none;}
.gd-table tr:hover td{background:#fafcff;}
.gd-empty{padding:40px;text-align:center;color:#94a3b8;font-size:.85rem;}
.gd-btn{display:inline-flex;align-items:center;gap:6px;padding:6px 14px;border-radius:8px;font-size:.78rem;font-weight:700;cursor:pointer;border:none;transition:.12s;}
.gd-btn-danger{background:#fef2f2;color:#b91c1c;border:1px solid #fecaca;}
.gd-btn-danger:hover{background:#fee2e2;}
.gd-btn-warning{background:#fffbeb;color:#92400e;border:1px solid #fde68a;}
.gd-btn-warning:hover{background:#fef3c7;}
.gd-btn-primary{background:#1d4ed8;color:#fff;}
.gd-btn-primary:hover{background:#1e40af;}
.gd-btn-edit{background:#eff6ff;color:#1d4ed8;border:1px solid #bfdbfe;}
.gd-btn-edit:hover{background:#dbeafe;}

/* Modal modifier */
.gd-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:9000;align-items:center;justify-content:center;}
.gd-overlay.open{display:flex;}
.gd-modal{background:#fff;border-radius:18px;box-shadow:0 20px 60px rgba(0,0,0,.25);width:100%;max-width:540px;overflow:hidden;animation:slideUp .2s ease;}
@keyframes slideUp{from{transform:translateY(24px);opacity:0;}to{transform:translateY(0);opacity:1;}}
.gd-modal-head{background:linear-gradient(135deg,#1e3a5f,#1d4ed8);padding:18px 22px;display:flex;align-items:center;justify-content:space-between;}
.gd-modal-head h2{color:#fff;font-size:1rem;font-weight:800;margin:0;display:flex;align-items:center;gap:8px;}
.gd-modal-close{background:none;border:none;color:rgba(255,255,255,.8);font-size:1.4rem;cursor:pointer;line-height:1;padding:4px 6px;}
.gd-modal-close:hover{color:#fff;}
.gd-modal-body{padding:22px;}
.gd-modal-actions{padding:0 22px 20px;display:flex;gap:10px;justify-content:flex-end;}
.gd-btn-actions{display:flex;gap:6px;flex-wrap:wrap;}

/* Form */
.gd-form-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:14px;padding:20px 22px;}
.gd-label{font-size:.78rem;font-weight:700;color:#374151;display:block;margin-bottom:5px;}
.gd-input{width:100%;padding:9px 12px;border:1.5px solid #e2e8f0;border-radius:9px;font-size:.85rem;color:#0f172a;outline:none;transition:.15s;}
.gd-input:focus{border-color:#1d4ed8;box-shadow:0 0 0 3px rgba(29,78,216,.1);}
.gd-form-actions{padding:0 22px 20px;display:flex;gap:10px;justify-content:flex-end;}

/* Pulse animation pour en cours */
@keyframes pulse-dot{0%,100%{opacity:1;}50%{opacity:.4;}}
.pulse{display:inline-block;width:8px;height:8px;border-radius:50%;background:#10b981;animation:pulse-dot 1.4s ease-in-out infinite;margin-right:4px;}

/* Flash message */
.gd-flash{padding:12px 18px;border-radius:10px;font-size:.85rem;font-weight:600;margin-bottom:20px;display:flex;align-items:center;gap:8px;}
.gd-flash.success{background:#d1fae5;color:#065f46;border:1px solid #6ee7b7;}
.gd-flash.error{background:#fee2e2;color:#b91c1c;border:1px solid #fca5a5;}
</style>
</head>
<body>

<div class="gd-header">
    <h1><i class="bi bi-shield-lock-fill"></i> Gestion des Gardes Médicales</h1>
    <a href="<?= BASE_URL ?>dashboard" class="gd-back"><i class="bi bi-arrow-left"></i> Tableau de bord</a>
</div>

<div class="gd-body">

<?php if ($flash): ?>
<div class="gd-flash <?= $flash['type'] ?>">
    <i class="bi bi-<?= $flash['type']==='success'?'check-circle-fill':'exclamation-triangle-fill' ?>"></i>
    <?= htmlspecialchars($flash['msg']) ?>
</div>
<?php endif; ?>

<!-- ══ GARDES EN COURS ══ -->
<div class="gd-card">
    <div class="gd-card-head">
        <span class="gd-card-title">
            <span class="pulse"></span> Gardes en cours
            <span class="gd-badge" style="background:#d1fae5;color:#065f46;"><?= count($gardesEnCours) ?></span>
        </span>
    </div>
    <?php if (empty($gardesEnCours)): ?>
    <div class="gd-empty"><i class="bi bi-moon-stars" style="font-size:1.5rem;display:block;margin-bottom:8px;"></i>Aucune garde active en ce moment.</div>
    <?php else: ?>
    <table class="gd-table">
        <thead><tr><th>Médecin</th><th>Type</th><th>Début</th><th>Fin prévue</th><th>Notes</th><th>Action</th></tr></thead>
        <tbody>
        <?php foreach ($gardesEnCours as $g):
            [$tl,$tc,$tbg] = $typeLabels[$g['type_garde']] ?? ['—','#64748b','#f1f5f9'];
        ?>
        <tr>
            <td>
                <strong><?= htmlspecialchars(strtoupper($g['nom']).' '.$g['prenom']) ?></strong><br>
                <small style="color:#64748b;"><?= htmlspecialchars($g['medecin_role']) ?></small>
            </td>
            <td><span class="gd-badge" style="background:<?= $tbg ?>;color:<?= $tc ?>;"><?= $tl ?></span></td>
            <td><?= date('d/m/Y H:i', strtotime($g['date_debut'])) ?></td>
            <td><?= date('d/m/Y H:i', strtotime($g['date_fin'])) ?></td>
            <td><small style="color:#64748b;"><?= htmlspecialchars($g['notes'] ?? '—') ?></small></td>
            <td>
                <div class="gd-btn-actions">
                    <button class="gd-btn gd-btn-edit" onclick="gardeModifier(<?= $g['id'] ?>,<?= $g['medecin_id'] ?>,'<?= addslashes($g['type_garde']) ?>','<?= date('Y-m-d\TH:i', strtotime($g['date_debut'])) ?>','<?= date('Y-m-d\TH:i', strtotime($g['date_fin'])) ?>','<?= addslashes($g['notes'] ?? '') ?>')">
                        <i class="bi bi-pencil"></i> Modifier
                    </button>
                    <button class="gd-btn gd-btn-danger" onclick="gardeTerminer(<?= $g['id'] ?>)">
                        <i class="bi bi-stop-circle"></i> Terminer
                    </button>
                </div>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<!-- ══ PLANIFIER UNE GARDE ══ -->
<div class="gd-card">
    <div class="gd-card-head">
        <span class="gd-card-title"><i class="bi bi-calendar-plus"></i> Planifier une garde</span>
    </div>
    <form method="POST" action="<?= BASE_URL ?>admin/gardes/creer">
        <?= $csrf ?>
        <div class="gd-form-grid">
            <div>
                <label class="gd-label">Médecin *</label>
                <select name="medecin_id" class="gd-input" required>
                    <option value="">— Sélectionner —</option>
                    <?php foreach ($medecins as $m): ?>
                    <option value="<?= $m['id'] ?>"><?= htmlspecialchars(strtoupper($m['nom']).' '.$m['prenom']) ?> — <?= htmlspecialchars($m['role']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="gd-label">Type de garde *</label>
                <select name="type_garde" class="gd-input" required>
                    <?php foreach ($typeLabels as $k => [$l,$c,$bg]): ?>
                    <option value="<?= $k ?>"><?= $l ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="gd-label">Début *</label>
                <input type="datetime-local" name="date_debut" class="gd-input" required>
            </div>
            <div>
                <label class="gd-label">Fin *</label>
                <input type="datetime-local" name="date_fin" class="gd-input" required>
            </div>
            <div style="grid-column:1/-1;">
                <label class="gd-label">Notes (optionnel)</label>
                <input type="text" name="notes" class="gd-input" placeholder="Ex : Remplacement Dr. Martin, nuit du 24/06…">
            </div>
        </div>
        <div class="gd-form-actions">
            <button type="submit" class="gd-btn gd-btn-primary"><i class="bi bi-check-lg"></i> Enregistrer la garde</button>
        </div>
    </form>
</div>

<!-- ══ GARDES PLANIFIÉES ══ -->
<?php if (!empty($gardesPlanifiees)): ?>
<div class="gd-card">
    <div class="gd-card-head">
        <span class="gd-card-title"><i class="bi bi-calendar-week"></i> Gardes planifiées
            <span class="gd-badge" style="background:#dbeafe;color:#1e40af;"><?= count($gardesPlanifiees) ?></span>
        </span>
    </div>
    <table class="gd-table">
        <thead><tr><th>Médecin</th><th>Type</th><th>Début</th><th>Fin</th><th>Planifié par</th><th>Actions</th></tr></thead>
        <tbody>
        <?php foreach ($gardesPlanifiees as $g):
            [$tl,$tc,$tbg] = $typeLabels[$g['type_garde']] ?? ['—','#64748b','#f1f5f9'];
        ?>
        <tr>
            <td><strong><?= htmlspecialchars(strtoupper($g['nom']).' '.$g['prenom']) ?></strong></td>
            <td><span class="gd-badge" style="background:<?= $tbg ?>;color:<?= $tc ?>;"><?= $tl ?></span></td>
            <td><?= date('d/m/Y H:i', strtotime($g['date_debut'])) ?></td>
            <td><?= date('d/m/Y H:i', strtotime($g['date_fin'])) ?></td>
            <td><small><?= htmlspecialchars(trim(($g['createur_nom']??'').' '.($g['createur_prenom']??'')) ?: '—') ?></small></td>
            <td>
                <div class="gd-btn-actions">
                    <button class="gd-btn gd-btn-edit" onclick="gardeModifier(<?= $g['id'] ?>,<?= $g['medecin_id'] ?>,'<?= addslashes($g['type_garde']) ?>','<?= date('Y-m-d\TH:i', strtotime($g['date_debut'])) ?>','<?= date('Y-m-d\TH:i', strtotime($g['date_fin'])) ?>','<?= addslashes($g['notes'] ?? '') ?>')">
                        <i class="bi bi-pencil"></i> Modifier
                    </button>
                    <button class="gd-btn gd-btn-warning" onclick="gardeAnnuler(<?= $g['id'] ?>)">
                        <i class="bi bi-x-circle"></i> Annuler
                    </button>
                </div>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<!-- ══ HISTORIQUE ══ -->
<div class="gd-card">
    <div class="gd-card-head">
        <span class="gd-card-title"><i class="bi bi-clock-history"></i> Historique (30 derniers jours)</span>
    </div>
    <?php if (empty($historique)): ?>
    <div class="gd-empty">Aucun historique disponible.</div>
    <?php else: ?>
    <table class="gd-table">
        <thead><tr><th>Médecin</th><th>Type</th><th>Début</th><th>Fin</th><th>Statut</th></tr></thead>
        <tbody>
        <?php foreach ($historique as $g):
            [$tl,$tc,$tbg] = $typeLabels[$g['type_garde']] ?? ['—','#64748b','#f1f5f9'];
            [$sl,$sc,$sbg] = $statutBadge[$g['statut']] ?? ['—','#64748b','#f1f5f9'];
        ?>
        <tr>
            <td><?= htmlspecialchars(strtoupper($g['nom']).' '.$g['prenom']) ?></td>
            <td><span class="gd-badge" style="background:<?= $tbg ?>;color:<?= $tc ?>;"><?= $tl ?></span></td>
            <td><?= date('d/m/Y H:i', strtotime($g['date_debut'])) ?></td>
            <td><?= date('d/m/Y H:i', strtotime($g['date_fin'])) ?></td>
            <td><span class="gd-badge" style="background:<?= $sbg ?>;color:<?= $sc ?>;"><?= $sl ?></span></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

</div><!-- /gd-body -->

<!-- ══ MODAL MODIFIER ══ -->
<div class="gd-overlay" id="modalModifier">
    <div class="gd-modal">
        <div class="gd-modal-head">
            <h2><i class="bi bi-pencil-square"></i> Modifier la garde</h2>
            <button class="gd-modal-close" onclick="closeModifier()">&times;</button>
        </div>
        <div class="gd-modal-body">
            <input type="hidden" id="mod_garde_id">
            <div class="gd-form-grid" style="padding:0 0 0 0;">
                <div>
                    <label class="gd-label">Médecin *</label>
                    <select id="mod_medecin_id" class="gd-input">
                        <option value="">— Sélectionner —</option>
                        <?php foreach ($medecins as $m): ?>
                        <option value="<?= $m['id'] ?>"><?= htmlspecialchars(strtoupper($m['nom']).' '.$m['prenom']) ?> — <?= htmlspecialchars($m['role']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="gd-label">Type de garde *</label>
                    <select id="mod_type_garde" class="gd-input">
                        <?php foreach ($typeLabels as $k => [$l,$c,$bg]): ?>
                        <option value="<?= $k ?>"><?= $l ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="gd-label">Début *</label>
                    <input type="datetime-local" id="mod_date_debut" class="gd-input">
                </div>
                <div>
                    <label class="gd-label">Fin *</label>
                    <input type="datetime-local" id="mod_date_fin" class="gd-input">
                </div>
                <div style="grid-column:1/-1;">
                    <label class="gd-label">Notes</label>
                    <input type="text" id="mod_notes" class="gd-input" placeholder="Notes optionnelles…">
                </div>
            </div>
            <div id="mod_error" style="display:none;margin-top:12px;padding:10px 14px;background:#fee2e2;color:#b91c1c;border-radius:8px;font-size:.82rem;font-weight:600;"></div>
        </div>
        <div class="gd-modal-actions">
            <button class="gd-btn gd-btn-secondary" onclick="closeModifier()">Annuler</button>
            <button class="gd-btn gd-btn-primary" onclick="gardeModifierSauvegarder()">
                <i class="bi bi-check-lg"></i> Enregistrer
            </button>
        </div>
    </div>
</div>

<script>
const CSRF = '<?= $token ?>';
const BURL = '<?= BASE_URL ?>';

function gardeTerminer(id) {
    if (!confirm('Terminer cette garde maintenant ?\nLe médecin perdra immédiatement son accès étendu.')) return;
    fetch(BURL + 'admin/gardes/terminer', {
        method: 'POST',
        headers: {'Content-Type':'application/x-www-form-urlencoded'},
        body: '_csrf_token=' + encodeURIComponent(CSRF) + '&garde_id=' + id
    }).then(r => r.json()).then(d => { if (d.ok) location.reload(); else alert(d.error); });
}

function gardeAnnuler(id) {
    if (!confirm('Annuler cette garde planifiée ?')) return;
    fetch(BURL + 'admin/gardes/annuler', {
        method: 'POST',
        headers: {'Content-Type':'application/x-www-form-urlencoded'},
        body: '_csrf_token=' + encodeURIComponent(CSRF) + '&garde_id=' + id
    }).then(r => r.json()).then(d => { if (d.ok) location.reload(); else alert(d.error); });
}

function gardeModifier(id, medecinId, type, debut, fin, notes) {
    document.getElementById('mod_garde_id').value   = id;
    document.getElementById('mod_medecin_id').value = medecinId;
    document.getElementById('mod_type_garde').value = type;
    document.getElementById('mod_date_debut').value = debut;
    document.getElementById('mod_date_fin').value   = fin;
    document.getElementById('mod_notes').value      = notes;
    document.getElementById('mod_error').style.display = 'none';
    document.getElementById('modalModifier').classList.add('open');
}

function closeModifier() {
    document.getElementById('modalModifier').classList.remove('open');
}

function gardeModifierSauvegarder() {
    const id        = document.getElementById('mod_garde_id').value;
    const medecinId = document.getElementById('mod_medecin_id').value;
    const debut     = document.getElementById('mod_date_debut').value;
    const fin       = document.getElementById('mod_date_fin').value;
    const type      = document.getElementById('mod_type_garde').value;
    const notes     = document.getElementById('mod_notes').value;
    const errEl     = document.getElementById('mod_error');

    if (!medecinId || !debut || !fin) {
        errEl.textContent = 'Veuillez remplir tous les champs obligatoires.';
        errEl.style.display = 'block'; return;
    }
    if (new Date(fin) <= new Date(debut)) {
        errEl.textContent = 'La date de fin doit être après la date de début.';
        errEl.style.display = 'block'; return;
    }

    const body = '_csrf_token=' + encodeURIComponent(CSRF)
        + '&garde_id='   + encodeURIComponent(id)
        + '&medecin_id=' + encodeURIComponent(medecinId)
        + '&date_debut=' + encodeURIComponent(debut)
        + '&date_fin='   + encodeURIComponent(fin)
        + '&type_garde=' + encodeURIComponent(type)
        + '&notes='      + encodeURIComponent(notes);

    fetch(BURL + 'admin/gardes/modifier', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body
    }).then(r => r.json()).then(d => {
        if (d.ok) { location.reload(); }
        else {
            errEl.textContent = d.error || 'Une erreur est survenue.';
            errEl.style.display = 'block';
        }
    }).catch(() => {
        errEl.textContent = 'Erreur réseau, veuillez réessayer.';
        errEl.style.display = 'block';
    });
}

// Fermer le modal en cliquant l'overlay
document.getElementById('modalModifier').addEventListener('click', function(e) {
    if (e.target === this) closeModifier();
});

// Pré-remplir date_debut = maintenant, date_fin = +12h
(function() {
    const now   = new Date();
    const later = new Date(now.getTime() + 12 * 3600 * 1000);
    const fmt   = d => d.toISOString().slice(0,16);
    document.querySelector('[name="date_debut"]').value = fmt(now);
    document.querySelector('[name="date_fin"]').value   = fmt(later);
})();
</script>
</body>
</html>
